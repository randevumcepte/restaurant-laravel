<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MUSTERI (QR) ASISTANI — masadaki QR'dan acilir, musteriyle konusur:
 * menu/gunun yemegi/tatli/icecek tanitimi, oneri, WiFi/saat/adres (kalip), garson cagirma.
 * Faz 1: tanitim + sohbet (siparis "garsonu cagir" ile). Rakam/menu GERCEK veriden.
 */
class MusteriAsistan
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    public function cevapla($soru, $baglam = null)
    {
        $c = $this->norm($soru);
        if ($c === '') return $this->cvp('Sizi dinliyorum. Menümüzü sorabilir, öneri isteyebilir ya da garson çağırabilirsiniz.');

        // 1) Kimlik / selam / tesekkur (musteri dostu)
        if ($this->has($c, ['sen kimsin', 'kimsin', 'adin ne', 'nesin', 'ne yapabilir', 'neler yapabilir', 'ne ise yara', 'gorevin ne'])) {
            return $this->cvp('Ben masanızın dijital asistanıyım. Menüyü tanıtabilir, öneride bulunabilir, günün yemeğini söyleyebilir ya da garson çağırabilirim. Ne yapmak istersiniz?');
        }
        if ($this->has($c, ['merhaba', 'selam', 'gunaydin', 'iyi gunler', 'iyi aksamlar', 'alo', 'hey'])) {
            return $this->cvp('Hoş geldiniz! 😊 Menümüzü mü tanıtayım yoksa bir önerim mi olsun?');
        }
        if ($this->has($c, ['tesekkur', 'sagol', 'sag ol', 'eyvallah', 'minnettar'])) {
            return $this->cvp('Rica ederim, afiyet olsun! 😊');
        }

        // 2) Garson cagir / hesap
        if ($this->has($c, ['garson', 'biri gelsin', 'cagir', 'yardim istiyorum', 'hesap', 'odeme', 'odeyecegim', 'hesabi getir', 'adisyon'])) {
            $hesap = $this->has($c, ['hesap', 'odeme', 'odeyecegim', 'hesabi getir']);
            return $this->cvp($hesap ? 'Garsonumuza hesabınızı iletmesini söyledim, birazdan yanınızda olacak. 🙋'
                : 'Garsonumuzu masanıza çağırdım, birazdan geliyor. 🙋', ['aksiyon' => 'garson_cagir']);
        }

        // 2.5) URUN OZELLIK SORUSU (tip + urun): "acili mi", "glutensiz mi", "yaninda ne gelir"...
        $tip = $this->urunSoruTipi($c);
        if ($tip) {
            $urun = $this->urunBul($c) ?: ($baglam ? $this->urunAdBul($baglam) : null);
            if ($urun) return $this->urunOzellik($urun, $tip);
            // Urun belirsizse: net urun gerektiren tipler icin sor; caprazsatis/vejetaryen genel handler'a duser
            if (in_array($tip, ['icindekiler', 'acili_mi', 'alerjen', 'et_turu', 'nasil_pisiyor', 'yaninda_ne_gelir', 'porsiyon'])) {
                return $this->cvp('Hangi ürünü merak ediyorsunuz? Ürün adını yazarsanız hemen anlatayım. 😊');
            }
        }

        // 2.6) SIPARIS NIYETI (Faz 2): "iki adana bir ayran istiyorum" -> sepet
        $sip = $this->siparisCoz($soru);
        if (!empty($sip['lines'])) {
            $verb = $this->has($c, ['istiyorum', 'isterim', 'isterdim', 'alayim', 'alabilir miyim', 'alabilirim', 'siparis', 'getir', 'getirin', 'getirir misin', 'olsun', 'verir misin', 'ver bana', 'soyle', 'soyleyin', 'ekle', 'alalim', 'rica etsem', 'bir de', 'lutfen bir', 'istiyoruz', 'alacagim']);
            if ($verb || $sip['explicitQty'] || count($sip['lines']) >= 2) {
                return $this->sepetCevap($sip['lines']);
            }
        }

        // 3) Gunun yemegi / sef onerisi
        if ($this->has($c, ['gunun yemegi', 'gunun spesiyali', 'sef onerisi', 'spesiyal', 'bugun ne var', 'ne onerirsin', 'ne tavsiye', 'oneri', 'populer', 'en cok', 'en sevilen', 'favori', 'ne yesem', 'ne yiyeyim'])) {
            return $this->oneri();
        }

        // 4) Kategori tanitim
        if ($this->has($c, ['tatli', 'tatlilar', 'ne tatli'])) return $this->kategori(['tatlilar'], 'Tatlılarımız', '🍰');
        if ($this->has($c, ['icecek', 'icecekler', 'ne icilir', 'soguk icecek', 'sicak icecek'])) return $this->kategori(['soguk icecekler', 'sicak icecekler'], 'İçeceklerimiz', '🥤');
        if ($this->has($c, ['salata', 'salatalar'])) return $this->kategori(['salatalar'], 'Salatalarımız', '🥗');
        if ($this->has($c, ['corba', 'baslangic', 'baslangiclar'])) return $this->kategori(['baslangiclar'], 'Başlangıçlarımız', '🍲');
        if ($this->has($c, ['pizza', 'pizzalar'])) return $this->kategori(['pizzalar'], 'Pizzalarımız', '🍕');
        if ($this->has($c, ['burger', 'burgerler', 'hamburger'])) return $this->kategori(['burgerler'], 'Burgerlerimiz', '🍔');
        if ($this->has($c, ['makarna', 'makarnalar', 'pasta'])) return $this->kategori(['makarnalar'], 'Makarnalarımız', '🍝');
        if ($this->has($c, ['izgara', 'kebap', 'et', 'et yemek'])) return $this->kategori(['izgaralar', 'ana yemekler'], 'Izgara ve Ana Yemekler', '🍖');
        if ($this->has($c, ['vejetaryen', 'etsiz', 'vegan'])) return $this->vejetaryen();
        if ($this->has($c, ['menu', 'ne var', 'neler var', 'yemek listesi', 'kategoriler', 'neler yapiyorsunuz'])) return $this->menu();

        // 5) Belirli urun (fiyat / nasil / icinde ne)
        $urun = $this->urunBul($c);
        if ($urun) return $this->urunTanit($urun, $c);

        // 6) Kalip kutuphanesi (wifi/saat/adres... — kimlik/sohbet disi)
        $kalip = $this->kalip($soru);
        if ($kalip) return $kalip;

        // 7) Fallback
        return $this->cvp('Bunu tam anlayamadım 🙂 Menümüzü sorabilir, "günün yemeği ne" diyebilir, öneri isteyebilir ya da garson çağırabilirsiniz.');
    }

    // -------- MENU HANDLERS --------
    protected function menu()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->pluck('ad')->all();
        if (empty($kats)) return $this->cvp('Menü şu an hazırlanıyor, birazdan hazır olacak.');
        $kartlar = array_map(fn ($k) => ['ad' => $k, 'emoji' => $this->katEmoji($k)], $kats);
        return $this->cvp('Menümüzde şu bölümler var. Hangisine bakmak istersiniz? İsterseniz üzerine dokunun, isterseniz "günün yemeği ne" diye sorun. 😊',
            ['tip' => 'kategoriler', 'kategoriler' => $kartlar]);
    }

    protected function kategori(array $normAdlar, $baslik, $emoji = '')
    {
        $urunler = DB::table('urunler')->join('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->where('urunler.aktif', 1)->where('urunler.tukendi', 0)
            ->select('urunler.id', 'urunler.ad', 'urunler.fiyat', 'urunler.aciklama', 'menu_kategorileri.ad as kat')
            ->get()->filter(fn ($u) => in_array($this->norm($u->kat), $normAdlar))->values();
        if ($urunler->isEmpty()) return $this->cvp($baslik . ' şu an listede görünmüyor.');
        $kartlar = $urunler->take(10)->map(fn ($u) => $this->kart($u->ad, $u->fiyat, $u->aciklama, $u->kat, null, [], $u->id))->all();
        $ornek = $urunler->take(3)->map(fn ($u) => $u->ad)->implode(', ');
        return $this->cvp("$emoji $baslik hazır. $ornek gibi lezzetlerimiz var; resimlere göz atıp beğendiğinizi sorabilir ya da hemen isteyebilirsiniz.",
            ['tip' => 'urunler', 'baslik' => $baslik, 'kartlar' => $kartlar]);
    }

    protected function vejetaryen()
    {
        // Et/tavuk/kofte/sucuk/kebap gecmeyen urunler
        $etli = ['et', 'tavuk', 'kofte', 'sucuk', 'kebap', 'pirzola', 'burger', 'sote', 'antrikot', 'bolonez', 'ton'];
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('tukendi', 0)
            ->select('id', 'ad', 'fiyat', 'aciklama')->get()->filter(function ($u) use ($etli) {
                $n = $this->norm($u->ad);
                foreach ($etli as $e) { if (strpos($n, $e) !== false) return false; }
                return true;
            })->values();
        if ($urunler->isEmpty()) return $this->cvp('Vejetaryen seçeneklerimiz için garsonumuza sorabilirim, çağırayım mı?');
        $kartlar = $urunler->take(8)->map(fn ($u) => $this->kart($u->ad, $u->fiyat, $u->aciklama, null, 'Etsiz', [], $u->id))->all();
        return $this->cvp('🥗 Etsiz sevenler için birkaç güzel seçeneğimiz var. Resimlere göz atabilir, detay isteyebilirsiniz.',
            ['tip' => 'urunler', 'baslik' => 'Vejetaryen', 'kartlar' => $kartlar]);
    }

    protected function oneri()
    {
        // Misafirlerin en cok tercih ettikleri (son 30 gun) — sicak dil
        $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', now()->subDays(30))->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('urun_adi', DB::raw('SUM(adet) as adet'))->groupBy('urun_adi')->orderByDesc('adet')->limit(4)->pluck('urun_adi')->all();
        if (empty($top)) {
            $top = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->inRandomOrder()->limit(3)->pluck('ad')->all();
        }
        // Isimlerden tam urun detayi (fiyat/aciklama/kategori) cek
        $rows = DB::table('urunler')->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->whereIn('urunler.ad', $top)->where('urunler.aktif', 1)
            ->select('urunler.id', 'urunler.ad', 'urunler.fiyat', 'urunler.aciklama', 'menu_kategorileri.ad as kat')
            ->get()->keyBy('ad');
        $etiketler = ['Misafir favorisi', 'Çok seviliyor', 'Şefin önerisi', 'Günün favorisi'];
        $kartlar = [];
        $i = 0;
        foreach ($top as $ad) {
            $u = $rows[$ad] ?? null;
            if (!$u) continue;
            $kartlar[] = $this->kart($u->ad, $u->fiyat, $u->aciklama, $u->kat, $etiketler[$i] ?? 'Öneri', [], $u->id);
            $i++;
        }
        $bas = $top[0] ?? 'Köfte';
        return $this->cvp("Size birkaç favorimizi önereyim. Özellikle $bas, misafirlerimizin en beğendiği lezzetlerden; gönül rahatlığıyla tavsiye ederim. Aşağıdaki önerilere göz atabilirsiniz. 😊",
            ['tip' => 'oneri', 'kartlar' => $kartlar]);
    }

    protected function urunBul($c)
    {
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->select('id', 'ad', 'fiyat', 'aciklama', 'tukendi', 'kategori_id')->get();
        $enIyi = null;
        $enSkor = 0;
        foreach ($urunler as $u) {
            $n = $this->norm($u->ad);
            if (mb_strlen($n) < 3) continue;
            if (strpos(' ' . $c . ' ', ' ' . $n . ' ') !== false || strpos($c, $n) !== false) {
                if (mb_strlen($n) > $enSkor) { $enSkor = mb_strlen($n); $enIyi = $u; }
            }
        }
        return $enIyi;
    }

    protected function urunTanit($u, $c)
    {
        if ($u->tukendi) return $this->cvp($u->ad . ' bugün maalesef tükendi. Dilerseniz benzeri için öneride bulunabilirim.');
        // Recete malzemelerinden kisa "icinde ne var"
        $malz = DB::table('receteler')->where('urun_id', $u->id)->where('tip', 'urun')->value('id');
        $icindekiler = [];
        if ($malz) {
            $icindekiler = DB::table('recete_kalemleri')->join('malzemeler', 'recete_kalemleri.malzeme_id', '=', 'malzemeler.id')
                ->where('recete_kalemleri.recete_id', $malz)->orderByDesc('recete_kalemleri.miktar')->limit(4)->pluck('malzemeler.ad')->all();
        }
        $kat = DB::table('menu_kategorileri')->where('id', $u->kategori_id ?? 0)->value('ad');
        // Akici, garson agzindan; FIYAT YOK (kart uzerinde zaten yaziyor)
        $cevap = rtrim(trim($u->ad), '.') . '.';
        if (!empty($u->aciklama)) $cevap .= ' ' . rtrim(trim($u->aciklama), '.') . '.';
        if (!empty($icindekiler)) $cevap .= ' İçinde ' . $this->dogalListe($icindekiler) . ' bulunuyor.';
        $cevap .= ' Beğendiyseniz "İstiyorum" demeniz yeterli, garsonumuzu hemen çağırayım. 😊';
        return $this->cvp($cevap, ['tip' => 'urun', 'urun_baglam' => $u->ad, 'kartlar' => [$this->kart($u->ad, $u->fiyat, $u->aciklama, $kat, null, $icindekiler, $u->id)]]);
    }

    // ==================== SIPARIS ZEKASI (Faz 2) ====================
    /** Serbest metni ürün+adet satirlarina cevirir. "iki adana bir ayran" -> [{u,adet:2},{u,adet:1}]. */
    protected function siparisCoz($metin)
    {
        $tokens = array_values(array_filter(explode(' ', $this->norm($metin)), fn ($x) => $x !== ''));
        if (empty($tokens)) return ['lines' => [], 'explicitQty' => false];
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->get(['id', 'ad', 'fiyat', 'tukendi']);
        $keys = [];
        $ilk = [];
        foreach ($urunler as $u) { $w = explode(' ', $this->norm($u->ad)); $keys[] = ['w' => $w, 'u' => $u]; $ilk[$w[0]] = ($ilk[$w[0]] ?? 0) + 1; }
        // Tek-kelime takma ad: cok-kelimeli urunun benzersiz ilk kelimesi (adana -> Adana Kebap)
        foreach ($urunler as $u) { $w = explode(' ', $this->norm($u->ad)); if (count($w) > 1 && mb_strlen($w[0]) >= 4 && ($ilk[$w[0]] ?? 0) == 1) $keys[] = ['w' => [$w[0]], 'u' => $u]; }
        usort($keys, fn ($a, $b) => count($b['w']) <=> count($a['w'])); // uzun eslesme once
        $say = ['bir' => 1, 'iki' => 2, 'uc' => 3, 'dort' => 4, 'bes' => 5, 'alti' => 6, 'yedi' => 7, 'sekiz' => 8, 'dokuz' => 9, 'on' => 10, 'birer' => 1, 'ikiser' => 2, 'yarim' => 1];
        $lines = [];
        $pending = null;
        $explicit = false;
        $n = count($tokens);
        $used = array_fill(0, $n, false);
        for ($i = 0; $i < $n; $i++) {
            if ($used[$i]) continue;
            $t = $tokens[$i];
            if (preg_match('/^\d+$/', $t)) { $pending = min(50, (int) $t); $explicit = true; continue; }
            if (isset($say[$t])) { $pending = $say[$t]; $explicit = true; continue; }
            if (in_array($t, ['tane', 'adet', 'porsiyon', 'porsiyonluk', 'bardak', 'sise', 'kadeh'])) continue;
            $match = null;
            foreach ($keys as $k) {
                $w = $k['w'];
                $len = count($w);
                if ($i + $len > $n) continue;
                $ok = true;
                for ($j = 0; $j < $len; $j++) { if (!$this->kelimeUyar($tokens[$i + $j], $w[$j])) { $ok = false; break; } }
                if ($ok) { $match = $k; break; }
            }
            if ($match) {
                $len = count($match['w']);
                $lines[] = ['u' => $match['u'], 'adet' => $pending ?? 1];
                for ($j = 0; $j < $len; $j++) $used[$i + $j] = true;
                $i += $len - 1;
                $pending = null;
            }
        }
        return ['lines' => $lines, 'explicitQty' => $explicit];
    }

    /** Token, kelimenin (kok) ekli halimi? Kisa kelimede asiri uzamayi engelle (su != sucuk). */
    protected function kelimeUyar($token, $kelime)
    {
        $lk = mb_strlen($kelime);
        $lt = mb_strlen($token);
        if ($lk === 0) return false;
        if ($token === $kelime) return true;
        $pref = $lk <= 4 ? $kelime : mb_substr($kelime, 0, max(4, $lk - 2));
        if (mb_strpos($token, $pref) !== 0) return false;
        $maxEk = $lk <= 4 ? 2 : 3;
        return $lt <= $lk + $maxEk;
    }

    /** Cozulen satirlardan sepet cevabi (onay icin). */
    protected function sepetCevap($lines)
    {
        $map = [];
        foreach ($lines as $l) {
            $id = $l['u']->id;
            if (isset($map[$id])) $map[$id]['adet'] += $l['adet'];
            else $map[$id] = ['u' => $l['u'], 'adet' => $l['adet']];
        }
        $kalemler = [];
        $toplam = 0;
        $tukendi = [];
        $ozet = [];
        foreach ($map as $m) {
            $u = $m['u'];
            $adet = (int) $m['adet'];
            if ($u->tukendi) { $tukendi[] = $u->ad; continue; }
            $kalemler[] = ['urun_id' => (int) $u->id, 'ad' => $u->ad, 'adet' => $adet, 'fiyat' => (float) $u->fiyat];
            $toplam += $u->fiyat * $adet;
            $ozet[] = $adet . ' ' . $u->ad;
        }
        if (empty($kalemler)) {
            return $this->cvp('Maalesef ' . $this->dogalListe($tukendi) . ' şu an tükendi. Başka bir şey ister misiniz?');
        }
        $metin = 'Siparişinizi aldım: ' . $this->dogalListe($ozet) . '. Toplam ' . $this->tl($toplam) . '. Onaylıyor musunuz? Aşağıdan adetleri düzenleyip "Onayla"ya dokunun. 😊';
        if (!empty($tukendi)) $metin .= ' (Not: ' . $this->dogalListe($tukendi) . ' tükendi, eklenemedi.)';
        return $this->cvp($metin, ['aksiyon' => 'sepet', 'sepet' => $kalemler, 'toplam' => $toplam]);
    }

    // ==================== URUN OZELLIK ZEKASI (Modul 1) ====================
    /** Sorunun ürün özellik tipini bul (trigger'lı). Yoksa null. */
    protected function urunSoruTipi($c)
    {
        $n = ' ' . $c . ' ';
        $tipler = [
            'caprazsatis' => ['yaninda ne onerirsin', 'yanina ne onerirsin', 'yaninda ne alayim', 'yanina ne alayim', 'ne iyi gider', 'ne yakisir', 'bununla ne alinir', 'yaninda ne guzel', 'yanina ne onerir'],
            'icindekiler' => ['icinde ne var', 'icindekiler', 'malzeme', 'neyden yapiliyor', 'neyle yapiliyor', 'icerigi ne', 'iceriginde ne', 'hangi malzeme', 'icine ne', 'neyden olusuyor', 'icerik bilgisi'],
            'acili_mi' => ['aci mi', 'acili mi', 'aci olur mu', 'baharatli mi', 'baharat var', 'aci seviyesi', 'ne kadar aci', 'cok aci mi', 'aci iceriyor', 'aci biber', 'baharat seviyesi'],
            'vejetaryen_vegan_mi' => ['vejetaryen mi', 'vegan mi', 'vejeteryan mi', 'etsiz mi', 'et var mi', 'et iceriyor', 'hayvansal mi', 'vejetaryene uygun', 'vegana uygun', 'bitkisel mi'],
            'alerjen' => ['glutensiz mi', 'gluten var', 'gluten iceriyor', 'laktozsuz mu', 'laktoz var', 'sut iceriyor', 'alerjen var', 'hangi alerjen', 'glutensiz', 'laktozsuz', 'colyak', 'sut alerjisi'],
            'et_turu' => ['et turu', 'hangi et', 'ne eti', 'dana mi', 'tavuk mu', 'kuzu mu', 'hangi etten', 'et cesidi', 'kirmizi et mi', 'beyaz et mi', 'hangi hayvan'],
            'nasil_pisiyor' => ['nasil pisiyor', 'nasil pisiriliyor', 'pisirme yontemi', 'pisirme sekli', 'izgara mi', 'firinda mi', 'mangal mi', 'tavada mi', 'kizartma mi', 'nasil hazirlaniyor'],
            'yaninda_ne_gelir' => ['yaninda ne gelir', 'yaninda ne var', 'yaninda ne geliyor', 'ne ile servis', 'neyle servis', 'garnitur', 'yaninda salata', 'yaninda pilav', 'yaninda patates', 'eslik eden', 'servisinde ne'],
            'porsiyon' => ['kac kisilik', 'kac kisi yer', 'kac kisiye yeter', 'porsiyon kac', 'tek kisilik mi', 'iki kisilik mi', 'paylasilir mi', 'ortaya yeter', 'doyurucu mu', 'paylasmaya uygun', 'gramaj'],
        ];
        foreach ($tipler as $tip => $trigs) {
            foreach ($trigs as $t) {
                if ($this->tetikUyar($n, $this->norm($t))) return $tip;
            }
        }
        return null;
    }

    /** Ürün + tip -> veriden (reçete/ad/kategori) üretilen cevap. */
    protected function urunOzellik($u, $tip)
    {
        $malz = $this->receteMalz($u->id);
        $havuz = $this->norm($u->ad . ' ' . ($u->aciklama ?? '') . ' ' . implode(' ', $malz['orij']));
        $ad = $u->ad;
        $bul = function ($grup) use ($havuz) {
            $r = [];
            foreach ($grup as $g) { if (strpos($havuz, $this->norm($g)) !== false) $r[] = $g; }
            return $r;
        };
        $cevap = '';
        switch ($tip) {
            case 'icindekiler':
                $cevap = !empty($malz['orij'])
                    ? $ad . ' içinde ' . $this->dogalListe(array_slice($malz['orij'], 0, 6)) . ' bulunuyor. Özel bir hassasiyetiniz varsa söyleyin, birlikte bakalım. 😊'
                    : $ad . ' için içerik detayını garsonumuz netleştirsin; ister misiniz?';
                break;
            case 'acili_mi':
                $cevap = !empty($bul(['aci', 'pul biber', 'isot', 'jalapeno', 'chili', 'aci sos', 'arnavut', 'adana', 'acili', 'mexican', 'baharatli']))
                    ? $ad . ' baharatlı, acımsı bir lezzettir. Acı sevmiyorsanız garsonumuza söyleyin, acısı azaltılarak hazırlanabilir.'
                    : $ad . ' belirgin acı içermez. Dilerseniz garsonumuza acılı olarak da hazırlatabiliriz.';
                break;
            case 'vejetaryen_vegan_mi':
                if (!empty($bul(['dana', 'kiyma', 'tavuk', 'kuzu', 'balik', 'hindi', 'sucuk', 'pastirma', 'jambon', 'bonfile', 'antrikot', 'kofte', 'sosis', 'ciger', 'et suyu']))) {
                    $cevap = $ad . ', et/hayvansal içerik barındırıyor, vejetaryen değildir. Etsiz seçenekler için "vejetaryen" diyebilirsiniz, hemen listeleyeyim.';
                } elseif (!empty($bul(['sut', 'peynir', 'tereyag', 'yogurt', 'yumurta', 'kaymak', 'krema']))) {
                    $cevap = $ad . ', et içermiyor (vejetaryenler tercih edebilir) ama süt/yumurta gibi hayvansal içerik olabilir; vegan iseniz garsonumuz teyit etsin.';
                } else {
                    $cevap = $ad . ', et içermiyor; vejetaryen/vegan dostu görünüyor. Yine de kesin bilgi için garsonumuz teyit edebilir.';
                }
                break;
            case 'alerjen':
                $bulgu = [];
                if (!empty($bul(['un', 'ekmek', 'bulgur', 'makarna', 'bugday', 'galeta', 'hamur', 'yufka', 'eriste']))) $bulgu[] = 'gluten';
                if (!empty($bul(['sut', 'peynir', 'tereyag', 'yogurt', 'krema', 'kaymak', 'kasar', 'mozzarella']))) $bulgu[] = 'süt ürünleri';
                if (!empty($bul(['yumurta']))) $bulgu[] = 'yumurta';
                if (!empty($bul(['fistik', 'ceviz', 'findik', 'badem', 'antep']))) $bulgu[] = 'kuruyemiş';
                $cevap = !empty($bulgu)
                    ? $ad . ' içinde ' . $this->dogalListe($bulgu) . ' bulunabilir. Çölyak veya alerjiniz varsa çapraz bulaşma açısından garsonumuz ve mutfağımız kesin bilgi versin.'
                    : $ad . ' için belirgin alerjen kaydı görünmüyor; yine de alerjiniz varsa lütfen garsonumuza danışın.';
                break;
            case 'et_turu':
                $harita = ['kiyma' => 'dana (kıyma)', 'dana' => 'dana', 'tavuk' => 'tavuk', 'kuzu' => 'kuzu', 'balik' => 'balık', 'hindi' => 'hindi'];
                $et = null;
                foreach ($harita as $k => $v) { if (strpos($havuz, $k) !== false) { $et = $v; break; } }
                $cevap = $et
                    ? $ad . ', ' . $et . ' etinden hazırlanır. Farklı bir tercihiniz varsa uygun seçenekleri önerebilirim.'
                    : $ad . ' için et türünü garsonumuz netleştirsin; ister misiniz?';
                break;
            case 'nasil_pisiyor':
                $harita = ['izgara' => 'ızgarada', 'mangal' => 'mangalda', 'tandir' => 'tandırda', 'firin' => 'fırında', 'koz' => 'közde', 'tava' => 'tavada', 'kizart' => 'kızartılarak', 'haslama' => 'haşlanarak', 'sote' => 'sotelenerek', 'wok' => 'wokta'];
                $p = null;
                foreach ($harita as $k => $v) { if (strpos($havuz, $k) !== false) { $p = $v; break; } }
                $cevap = $p
                    ? $ad . ', ' . $p . ' hazırlanır. Pişirme derecesiyle ilgili özel isteğinizi garsonumuza iletebiliriz.'
                    : $ad . ' için pişirme detayını garsonumuz netleştirsin; ister misiniz?';
                break;
            case 'yaninda_ne_gelir':
                $g = $bul(['pilav', 'patates', 'salata', 'sebze', 'cacik', 'ekmek', 'sogan', 'turp', 'közlenmis', 'közleme', 'bulgur']);
                $cevap = !empty($g)
                    ? $ad . ' yanında ' . $this->dogalListe(array_slice($g, 0, 4)) . ' servis edilir. Farklı bir yan tercih isterseniz garsonumuz yardımcı olsun.'
                    : $ad . ' için servis içeriğini garsonumuz netleştirsin; ister misiniz?';
                break;
            case 'porsiyon':
                $cevap = $ad . ' tek porsiyon olarak servis edilir. Kaç kişi paylaşacağınızı söylerseniz uygun sipariş için garsonumuz yardımcı olur.';
                break;
            case 'caprazsatis':
                $oneri = $this->eslesmeOner($u);
                $cevap = !empty($oneri)
                    ? $ad . ' yanında ' . $this->dogalListe($oneri) . ' çok yakışır. Daha hafif ya da daha doyurucu bir eşleştirme isterseniz söyleyin. 😊'
                    : $ad . ' yanına güzel bir eşleştirme için garsonumuz önerebilir; ister misiniz?';
                break;
            default:
                return $this->urunTanit($u, '');
        }
        return $this->cvp($cevap, [
            'tip' => 'urun_ozellik', 'urun_baglam' => $ad,
            'kartlar' => [$this->kart($u->ad, $u->fiyat, $u->aciklama ?? '', null, null, array_slice($malz['orij'], 0, 4), $u->id)],
        ]);
    }

    /** Ürünün reçete malzemeleri (orijinal + normalize). */
    protected function receteMalz($urunId)
    {
        $rid = DB::table('receteler')->where('urun_id', $urunId)->where('tip', 'urun')->value('id');
        if (!$rid) return ['orij' => [], 'norm' => []];
        $rows = DB::table('recete_kalemleri')->join('malzemeler', 'recete_kalemleri.malzeme_id', '=', 'malzemeler.id')
            ->where('recete_kalemleri.recete_id', $rid)->orderByDesc('recete_kalemleri.miktar')->pluck('malzemeler.ad')->all();
        return ['orij' => $rows, 'norm' => array_map(fn ($x) => $this->norm($x), $rows)];
    }

    /** Ürünü ADIYLA bul (bağlam için). */
    protected function urunAdBul($ad)
    {
        $na = $this->norm($ad);
        if ($na === '') return null;
        return DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)
            ->select('id', 'ad', 'fiyat', 'aciklama', 'tukendi', 'kategori_id')->get()
            ->first(fn ($u) => $this->norm($u->ad) === $na);
    }

    /** Çapraz satış: kategoriye göre menüden uygun eşleştirme (salata/içecek/tatlı). */
    protected function eslesmeOner($u)
    {
        $kat = $this->norm(DB::table('menu_kategorileri')->where('id', $u->kategori_id ?? 0)->value('ad'));
        $oner = [];
        if (strpos($kat, 'tatli') !== false) {
            $ic = $this->menuUrun(['sicak icecekler', 'soguk icecekler']);
            if ($ic) $oner[] = $ic;
        } else {
            $s = $this->menuUrun(['salatalar']); if ($s) $oner[] = $s;
            $ic = $this->menuUrun(['soguk icecekler', 'sicak icecekler']); if ($ic) $oner[] = $ic;
            if (count($oner) < 2) { $t = $this->menuUrun(['tatlilar']); if ($t) $oner[] = $t; }
        }
        return array_slice(array_values(array_filter($oner)), 0, 2);
    }

    protected function menuUrun(array $normKats)
    {
        $r = DB::table('urunler')->join('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->where('urunler.aktif', 1)->where('urunler.tukendi', 0)
            ->select('urunler.ad', 'menu_kategorileri.ad as kat')->get()
            ->first(fn ($x) => in_array($this->norm($x->kat), $normKats));
        return $r ? $r->ad : null;
    }

    // -------- KALIP (kimlik/sohbet disi; wifi/saat/adres...) --------
    protected function kalip($soru)
    {
        try {
            if (!Schema::hasTable('asistan_kalip')) return null;
            $liste = DB::table('asistan_kalip')->where('aktif', 1)
                ->whereNotIn('kategori', ['kimlik', 'sohbet'])->select('id', 'tetikleyiciler', 'cevap')->get();
            if ($liste->isEmpty()) return null;
            $n = ' ' . $this->norm($soru) . ' ';
            $enIyi = null;
            $enSkor = 0;
            foreach ($liste as $k) {
                foreach (preg_split('/[\r\n,;]+/', (string) $k->tetikleyiciler) as $t) {
                    $t = trim($this->norm($t));
                    if (mb_strlen($t) < 2) continue;
                    if ($this->tetikUyar($n, $t)) {
                        if (mb_strlen($t) > $enSkor) { $enSkor = mb_strlen($t); $enIyi = $k; }
                    }
                }
            }
            if (!$enIyi) return null;
            try { DB::table('asistan_kalip')->where('id', $enIyi->id)->increment('kullanim_sayisi'); } catch (\Throwable $e) {}
            $cev = (string) $enIyi->cevap;
            if (strpos($cev, '---') !== false) {
                $p = array_values(array_filter(array_map('trim', preg_split('/^\s*-{3,}\s*$/m', $cev)), fn ($x) => $x !== ''));
                $cev = empty($p) ? trim($cev) : $p[array_rand($p)];
            }
            return $this->cvp(trim($cev), ['kaynak' => 'kalip']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // -------- KART / GORSEL --------
    /** Tek bir urun kartinin veri yapisi (resim = emoji tile; gercek foto eklenince gorsel dolar). */
    protected function kart($ad, $fiyat, $aciklama = null, $kat = null, $etiket = null, $icindekiler = [], $urunId = null)
    {
        $yuklenen = $this->gorselUrl($urunId);
        $gorseller = $yuklenen ? [$yuklenen] : $this->stokGorseller($kat, $ad, 4); // yuklenen foto > stok yemek fotosu
        return [
            'ad' => (string) $ad,
            'fiyat' => (float) $fiyat,
            'fiyat_yazi' => $this->tl($fiyat),
            'aciklama' => $aciklama ? (string) $aciklama : '',
            'emoji' => $this->katEmoji($kat, $ad),
            'gorsel' => $gorseller[0] ?? null,
            'gorseller' => $gorseller,           // galeri: ayni yemegin birkac fotografi
            'etiket' => $etiket,
            'icindekiler' => array_values((array) $icindekiler),
            'urun_id' => $urunId ? (int) $urunId : null,
        ];
    }

    /** Gercek foto: urunler.gorsel dolu ise onu don, degilse null (on yuz emoji tile gosterir). */
    protected static $gorselKolonVar = null;
    protected function gorselUrl($urunId)
    {
        if (!$urunId) return null;
        try {
            if (self::$gorselKolonVar === null) self::$gorselKolonVar = Schema::hasColumn('urunler', 'gorsel');
            if (!self::$gorselKolonVar) return null;
            $g = DB::table('urunler')->where('id', $urunId)->value('gorsel');
            if (!$g) return null;
            return preg_match('#^https?://#', $g) ? $g : asset($g);
        } catch (\Throwable $e) { return null; }
    }

    /** Urun/kategoriye gore ingilizce foto anahtar kelimesi. */
    protected function katKelime($kat, $ad)
    {
        $t = $this->norm($kat . ' ' . $ad);
        $harita = [
            'izgara kofte' => 'meatballs,grill', 'kofte' => 'meatballs', 'kebap' => 'kebab', 'adana' => 'kebab', 'urfa' => 'kebab',
            'pirzola' => 'lamb,chops', 'antrikot' => 'steak', 'biftek' => 'steak', 'tavuk' => 'grilled,chicken', 'balik' => 'fish,dish',
            'izgara' => 'grill,meat', 'sote' => 'meat,stew', 'guvec' => 'casserole',
            'haydari' => 'meze,yogurt', 'humus' => 'hummus', 'sigara boregi' => 'fried,pastry', 'meze' => 'meze', 'baslangic' => 'appetizer',
            'ezogelin' => 'soup', 'mercimek' => 'lentil,soup', 'corba' => 'soup',
            'pizza' => 'pizza', 'burger' => 'burger,food', 'makarna' => 'pasta', 'bolonez' => 'pasta,bolognese', 'spagetti' => 'spaghetti',
            'sezar' => 'caesar,salad', 'salata' => 'salad',
            'baklava' => 'baklava', 'kunefe' => 'kunefe', 'sutlac' => 'rice,pudding', 'brownie' => 'brownie,chocolate',
            'dondurma' => 'ice,cream', 'kazandibi' => 'dessert', 'tatli' => 'dessert',
            'latte' => 'latte', 'espresso' => 'espresso', 'cappuccino' => 'cappuccino', 'kahve' => 'coffee', 'cay' => 'tea',
            'ayran' => 'ayran,drink', 'limonata' => 'lemonade', 'meyve suyu' => 'juice', 'kola' => 'soda,cola', 'soda' => 'soda',
            'ana yemek' => 'turkish,food', 'kahvalti' => 'breakfast',
        ];
        foreach ($harita as $k => $v) { if (strpos($t, $k) !== false) return $v; }
        return 'food,plate';
    }

    /** Panel onizlemesi icin: yuklenmis foto varsa onu, yoksa stok fotoyu don. */
    public function onizlemeGorsel($kat, $ad, $urunId = null)
    {
        return $this->gorselUrl($urunId) ?: $this->stokGorseller($kat, $ad, 1)[0];
    }

    /** Ayni yemegin birkac farkli gercek fotografi (galeri icin). Sabit lock -> hep ayni set. */
    protected function stokGorseller($kat, $ad, $n = 4)
    {
        $kw = $this->katKelime($kat, $ad);
        $seed = abs(crc32((string) $ad)) % 997;
        $out = [];
        for ($i = 0; $i < $n; $i++) $out[] = 'https://loremflickr.com/500/380/' . $kw . '?lock=' . ($seed + $i * 7 + 1);
        return $out;
    }

    /** Kategori/urun adina gore uygun emoji (foto yuklenemezse kartin gorseli). */
    protected function katEmoji($kat, $ad = '')
    {
        $t = $this->norm($kat . ' ' . $ad);
        $harita = [
            'baklava' => '🍮', 'kunefe' => '🍮', 'sutlac' => '🍮', 'brownie' => '🍫', 'dondurma' => '🍨', 'tatli' => '🍰',
            'kahve' => '☕', 'latte' => '☕', 'cay' => '🍵', 'ayran' => '🥛', 'kola' => '🥤', 'meyve suyu' => '🧃', 'limonata' => '🍋',
            'sicak icecek' => '☕', 'soguk icecek' => '🥤', 'icecek' => '🥤',
            'salata' => '🥗', 'corba' => '🍲', 'baslangic' => '🍲', 'meze' => '🫒',
            'pizza' => '🍕', 'burger' => '🍔', 'hamburger' => '🍔', 'makarna' => '🍝', 'pasta' => '🍝', 'bolonez' => '🍝',
            'kofte' => '🍖', 'kebap' => '🍢', 'tavuk' => '🍗', 'pirzola' => '🍖', 'antrikot' => '🥩', 'balik' => '🐟',
            'izgara' => '🍖', 'ana yemek' => '🍽️', 'kahvalti' => '🍳',
        ];
        foreach ($harita as $k => $e) { if (strpos($t, $k) !== false) return $e; }
        return '🍽️';
    }

    /**
     * Tetikleyici eslesmesi: cok-kelimeli tetikte HER kelime, soruda (sira fark etmeden)
     * ON-EK TOLERANSLI bulunmali. "cocuk sandalyesi" -> "cocuk sandalyeNIZ" de yakalanir.
     * Kisa kelimeler tam, uzun kelimeler son ~2 harf esnetilerek (kok) aranir.
     */
    protected function tetikUyar($n, $t)
    {
        foreach (preg_split('/\s+/', trim($t)) as $kel) {
            if ($kel === '') continue;
            $len = mb_strlen($kel);
            $pref = $len <= 4 ? $kel : mb_substr($kel, 0, max(4, $len - 2)); // kaba kok
            if (!preg_match('/(?:^| )' . preg_quote($pref, '/') . '/u', $n)) return false;
        }
        return true;
    }

    // -------- YARDIMCILAR --------
    protected function cvp($metin, $ek = [])
    {
        return array_merge(['ok' => 1, 'cevap' => $metin, 'seslendir' => true], $ek);
    }

    protected function tl($v)
    {
        // "85 TL" / "1250 TL" — binlik ayiraci YOK (sesli okumada nokta vurguyu bozuyordu), ₺ yerine TL
        return number_format((float) $v, 0, ',', '') . ' TL';
    }

    /** ["a","b","c"] -> "a, b ve c" (akici Turkce liste). */
    protected function dogalListe(array $arr)
    {
        $arr = array_values(array_filter(array_map('trim', $arr), fn ($x) => $x !== ''));
        $n = count($arr);
        if ($n === 0) return '';
        if ($n === 1) return $arr[0];
        return implode(', ', array_slice($arr, 0, $n - 1)) . ' ve ' . $arr[$n - 1];
    }

    protected function has($c, array $ks)
    {
        foreach ($ks as $k) {
            if (strpos(' ' . $c . ' ', ' ' . $this->norm($k)) !== false || strpos($c, $this->norm($k)) !== false) return true;
        }
        return false;
    }

    protected function norm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
        $s = strtr($s, $tr);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }
}

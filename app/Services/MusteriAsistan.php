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

    public function cevapla($soru)
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
        $cevap = $u->ad . ', ' . $this->tl($u->fiyat) . '.';
        if (!empty($u->aciklama)) $cevap .= ' ' . $u->aciklama;
        if (!empty($icindekiler)) $cevap .= ' İçinde ' . implode(', ', $icindekiler) . ' var.';
        $cevap .= ' Beğendiyseniz "İstiyorum" deyin, garsonumuzu hemen çağırayım. 😊';
        return $this->cvp($cevap, ['tip' => 'urun', 'kartlar' => [$this->kart($u->ad, $u->fiyat, $u->aciklama, $kat, null, $icindekiler, $u->id)]]);
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
                    if (preg_match('/(?:^| )' . preg_quote($t, '/') . '[a-z]*(?= |$)/u', $n)) {
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
        return [
            'ad' => (string) $ad,
            'fiyat' => (float) $fiyat,
            'fiyat_yazi' => $this->tl($fiyat),
            'aciklama' => $aciklama ? (string) $aciklama : '',
            'emoji' => $this->katEmoji($kat, $ad),
            'gorsel' => $this->gorselUrl($urunId),  // simdilik null -> on yuzde emoji tile
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

    /** Kategori/urun adina gore uygun emoji (foto yoksa kartin gorseli). */
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

    // -------- YARDIMCILAR --------
    protected function cvp($metin, $ek = [])
    {
        return array_merge(['ok' => 1, 'cevap' => $metin, 'seslendir' => true], $ek);
    }

    protected function tl($v)
    {
        return '₺' . number_format((float) $v, 0, ',', '.');
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

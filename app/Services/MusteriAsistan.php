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

        // 0) KUFUR / HAKARET -> saygiya davet (devam ederse on yuz kapatir)
        if ($this->kufurMu($c)) {
            return $this->cvp('Efendim, sizi saygıya davet ediyorum. Eğer böyle konuşmaya devam ederseniz maalesef görüşmeyi kapatmak zorunda kalacağım.', ['aksiyon' => 'kufur']);
        }

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

        // 2.4) SIPARIS DUZENLE (deterministik, guvenilir): "kofteyi 2 olsun" (AYARLA), "kolayi istemiyorum" (CIKAR)
        $duz = $this->siparisDuzenle($c, $soru, $baglam);
        if ($duz) return $duz;

        // ===== HAIKU NIYET COZUCU (BEYIN) — BIRINCIL: kullanici ne dediyse Haiku niyeti coz + KESIN uygula.
        // Asagidaki kelime-kurallari yalnizca BEYIN yoksa (anahtar yok/tavan dolu/basarisiz) YEDEK calisir. =====
        $beyin = $this->niyetRouter($soru, $baglam);
        if ($beyin !== null) return $beyin;

        // ---- YEDEK KELIME KURALLARI (Haiku devre disi ise) ----
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

        // 2.35) GORME istegi (siparis DEGIL): "burgerleri gormek istiyorum", "menuyu goster", "coban salataya bakalim"
        if ($this->has($c, ['gormek', 'goster', 'gorebilir', 'gorelim', 'gorsem', 'bakmak', 'bakalim', 'bakabilir', 'goz at', 'goz atalim', 'incele', 'menusu', 'menusunu', 'listesi', 'listesini'])) {
            // ONCE belirli URUN ("coban salataya bakalim" -> Coban Salata, kategori Salatalar DEGIL)
            $uv = $this->urunBul($c);
            if ($uv) return $this->urunTanit($uv, $c);
            $kv = $this->kategoriEslesme($c);
            if ($kv) return $this->kategori([$this->norm($kv->ad)], $kv->ad, $this->katEmoji($kv->ad));
            return $this->menu();
        }

        // 2.55) Belirli urun + BILGI istegi -> urunu ANLAT (siparis/kategori/oneri'den ONCE)
        //       "ezogelin corbasi hakkinda bilgi ver" -> corba kategorisi DEGIL, o urunun detayi
        $bilgiIster = $this->has($c, ['hakkinda', 'bilgi ver', 'bilgi al', 'bilgi verir', 'bilgi rica', 'anlat', 'anlatir', 'tanit', 'tanitir', 'nedir', 'ne demek', 'ozellik', 'nasil bir', 'detay', 'aciklar']);
        if ($bilgiIster) {
            $bu = $this->urunBul($c) ?: ($baglam ? $this->urunAdBul($baglam) : null);
            if ($bu) return $this->urunTanit($bu, $c);
        }

        // 2.6) SIPARIS NIYETI (Faz 2): konusarak siparis akisi (BILGI istegi ise siparis SAYMA)
        $sip = $this->siparisCoz($soru);
        // Zayif fiiller (urun VARSA siparis sayilir): getir/olsun/ekle/bir de/soyle...
        $zayifVerb = $this->has($c, ['istiyorum', 'isterim', 'isterdim', 'alayim', 'alabilir miyim', 'alabilirim', 'siparis', 'getir', 'getirin', 'getirir misin', 'olsun', 'verir misin', 'ver bana', 'ekle', 'alalim', 'rica etsem', 'bir de', 'lutfen bir', 'istiyoruz', 'alacagim', 'ekler misin']);
        // Guclu istek (urun YOKSA bile siparis niyeti; bağlamdaki urunu ekler): istiyorum/alayim/olsun...
        $gucluVerb = $this->has($c, ['istiyorum', 'isterim', 'isterdim', 'alayim', 'alabilir miyim', 'alabilirim', 'istiyoruz', 'alacagim', 'onu istiyorum', 'bunu istiyorum', 'onu alayim', 'bunu alayim', 'siparis vermek', 'siparis verecegim', 'siparis vereyim', 'siparisim var']);
        if (!$bilgiIster && !empty($sip['lines'])) {
            // FARKLI urun sayisi (ayni urunu 2 kez anmak siparis SAYILMAZ; sohbette "Adana... Adana" gibi)
            $farkliUrun = count(array_unique(array_map(fn ($l) => $l['u']->id, $sip['lines'])));
            if ($zayifVerb || $sip['explicitQty'] || $farkliUrun >= 2) {
                return $this->sepetEkleCevap($sip['lines']);
            }
        } elseif (!$bilgiIster && empty($sip['lines'])) {
            // Urun adi gecmiyor. Guclu istek + BAGLAM (son konusulan urun) varsa ONU ekle
            if ($gucluVerb && $baglam) {
                $bu = $this->urunAdBul($baglam);
                if ($bu) return $this->sepetEkleCevap([['u' => $bu, 'adet' => 1]]);
            }
            // Guclu istek ama urun/baglam yok -> nazikce sor (fallback yerine)
            if ($gucluVerb || trim($c) === 'siparis') {
                return $this->cvp('Tabii, hangi ürünü almak istersiniz? Ürün adını söylemeniz yeterli, hemen ekleyeyim. 😊', ['aksiyon' => 'siparis_basla']);
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
        // 4b) DINAMIK kategori ONCE: "ana yemeklerde ne var", "makarnalar", "tatlilara" -> ilgili kategori
        //     (genel "ne var" menusunden ONCE ki "ana yemeklerde ne var" genel menuyu acmasin)
        $kat = $this->kategoriEslesme($c);
        if ($kat) return $this->kategori([$this->norm($kat->ad)], $kat->ad, $this->katEmoji($kat->ad));
        if ($this->has($c, ['menu', 'ne var', 'neler var', 'yemek listesi', 'kategoriler', 'neler yapiyorsunuz'])) return $this->menu();

        // 5) Belirli urun (fiyat / nasil / icinde ne)
        $urun = $this->urunBul($c);
        if ($urun) return $this->urunTanit($urun, $c);

        // 6) Kalip kutuphanesi (wifi/saat/adres... — kimlik/sohbet disi)
        $kalip = $this->kalip($soru);
        if ($kalip) return $kalip;

        // 7) HAIKU EMNIYET AGI: kural+kalip kacirdi -> ogrenilen onbellek -> (gerekirse) Haiku -> ogren
        $ai = $this->haikuEmniyet($soru);
        if ($ai !== null) return $ai;

        // 8) Fallback
        return $this->cvp('Bunu tam anlayamadım 🙂 Menümüzü sorabilir, "günün yemeği ne" diyebilir, öneri isteyebilir ya da garson çağırabilirsiniz.');
    }

    /** TUM menu: her kategori + urun kartlari (musteri kendi basina inceler). */
    public function menuTam()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->orderBy('ad')->get(['id', 'ad']);
        $out = [];
        foreach ($kats as $k) {
            $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('kategori_id', $k->id)
                ->where('aktif', 1)->orderBy('ad')->get(['id', 'ad', 'fiyat', 'aciklama', 'tukendi']);
            $kartlar = $urunler->map(fn ($u) => $this->kart($u->ad, $u->fiyat, $u->aciklama, $k->ad, $u->tukendi ? 'Tükendi' : null, [], $u->id))->all();
            if (!empty($kartlar)) $out[] = ['ad' => $k->ad, 'emoji' => $this->katEmoji($k->ad), 'kartlar' => $kartlar];
        }
        return ['ok' => 1, 'kategoriler' => $out];
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

    /** Menüdeki kategori adlarından soruya eşleşeni bul (çoğul/çekim toleranslı). */
    protected function kategoriEslesme($c)
    {
        $n = ' ' . $c . ' ';
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->get(['id', 'ad']);
        $enIyi = null;
        $enSkor = 0;
        foreach ($kats as $k) {
            $kn = $this->norm($k->ad);
            $core = trim(preg_replace('/(lar|ler)(?= |$)/u', '', $kn)); // "ana yemekler" -> "ana yemek", "salatalar" -> "salata"
            if ($core === '') continue;
            if ($this->tetikUyar($n, $core) && mb_strlen($core) > $enSkor) { $enSkor = mb_strlen($core); $enIyi = $k; }
        }
        return $enIyi;
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
        // ISTAH KABARTAN garson agzi: coskulu acilis + duyusal aciklama + davetkar kapanis (FIYAT YOK; kartta yaziyor)
        $acilislar = ['Harika seçim! ', 'Nefis bir tercih! ', 'Bunu çok seveceksiniz! ', 'Favorilerimizden! ', 'Ooo, çok güzel seçtiniz! ', ''];
        $kapanislar = [
            'Sıcacık, tazecik önünüze gelsin mi? "İstiyorum" demeniz yeterli. 😊',
            'Ağzınıza layık; hemen hazırlatalım mı? "İstiyorum" deyin yeter. 😊',
            'Bir tabak nefis, değil mi? Beğendiyseniz "İstiyorum" deyin, hemen geliyor. 😋',
            'Kaçırılmaz; "İstiyorum" demeniz yeterli, garsonumuz hemen getirsin. 😊',
        ];
        $cevap = $acilislar[array_rand($acilislar)] . rtrim(trim($u->ad), '.') . '. ';
        if (!empty($u->aciklama)) $cevap .= rtrim(trim($u->aciklama), '.') . '. ';
        if (!empty($icindekiler)) $cevap .= 'İçinde ' . $this->dogalListe($icindekiler) . ' var. ';
        $cevap .= $kapanislar[array_rand($kapanislar)];
        return $this->cvp($cevap, ['tip' => 'urun', 'urun_baglam' => $u->ad, 'kartlar' => [$this->kart($u->ad, $u->fiyat, $u->aciklama, $kat, null, $icindekiler, $u->id)]]);
    }

    // ==================== SIPARIS ZEKASI (Faz 2) ====================
    /** Sipariş düzenleme: adedi AYARLA ("kofteyi 2 olsun") veya CIKAR ("kolayi istemiyorum"). Yoksa null. */
    protected function siparisDuzenle($c, $soru, $baglam)
    {
        $cikarVerb = $this->has($c, ['istemiyorum', 'istemem', 'istemedim', 'vazgectim', 'vazgeciyorum', 'cikar', 'cikart', 'kaldir', 'iptal', 'sil ', 'silin', 'almayayim', 'olmasin', 'gerek yok', 'eksilt', 'gitsin']);
        $ayarlaVerb = $this->has($c, ['olsun', 'yap ', 'yapar misin', 'yapalim', 'olacak', 'guncelle', 'degistir']);
        if (!$cikarVerb && !$ayarlaVerb) return null;
        $sip = $this->siparisCoz($soru);
        $urun = null;
        if (count($sip['lines']) === 1) $urun = $sip['lines'][0]['u'];
        elseif (empty($sip['lines']) && $baglam) $urun = $this->urunAdBul($baglam);
        if (!$urun) return null; // urun net degilse duzenleme sayma (normal akisa biraksin)
        if ($cikarVerb) {
            return $this->cvp($urun->ad . ', siparişinizden çıkardım. Başka bir arzunuz var mı? 😊',
                ['aksiyon' => 'sepet_cikar', 'cikar' => ['urun_id' => (int) $urun->id, 'ad' => $urun->ad]]);
        }
        // AYARLA -> sayi sart (yoksa "olsun" normal siparise dussun)
        $sayi = $this->sayiCoz($c);
        if ($sayi === null) return null;
        return $this->cvp($urun->ad . ' adedini ' . $sayi . ' yaptım. Başka bir arzunuz var mı? 😊',
            ['aksiyon' => 'sepet_ayarla', 'eklenen' => [['urun_id' => (int) $urun->id, 'ad' => $urun->ad, 'adet' => $sayi, 'fiyat' => (float) $urun->fiyat]]]);
    }

    /** Metinden ilk sayiyi coz (rakam veya yazi). Yoksa null. */
    protected function sayiCoz($c)
    {
        if (preg_match('/(?<!\d)(\d{1,2})(?!\d)/', $c, $m)) return (int) $m[1];
        $map = ['bir' => 1, 'iki' => 2, 'uc' => 3, 'dort' => 4, 'bes' => 5, 'alti' => 6, 'yedi' => 7, 'sekiz' => 8, 'dokuz' => 9, 'on' => 10];
        foreach ($map as $w => $v) { if (preg_match('/(?:^| )' . $w . '(?= |$)/u', ' ' . $c . ' ')) return $v; }
        return null;
    }

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

    /** Cozulen satirlar -> sepete EKLE cevabi (birikimli akis; "baska arzunuz?" diye sorar). */
    protected function sepetEkleCevap($lines)
    {
        $map = [];
        foreach ($lines as $l) {
            $id = $l['u']->id;
            if (isset($map[$id])) $map[$id]['adet'] += $l['adet'];
            else $map[$id] = ['u' => $l['u'], 'adet' => $l['adet']];
        }
        $eklenen = [];
        $tukendi = [];
        $ozet = [];
        foreach ($map as $m) {
            $u = $m['u'];
            $adet = (int) $m['adet'];
            if ($u->tukendi) { $tukendi[] = $u->ad; continue; }
            $eklenen[] = ['urun_id' => (int) $u->id, 'ad' => $u->ad, 'adet' => $adet, 'fiyat' => (float) $u->fiyat];
            $ozet[] = $adet . ' ' . $u->ad;
        }
        if (empty($eklenen)) {
            return $this->cvp('Maalesef ' . $this->dogalListe($tukendi) . ' şu an tükendi. Başka bir şey rica eder misiniz?');
        }
        $metin = 'Harika seçim, ' . $this->dogalListe($ozet) . ' ekledim. Başka bir arzunuz var mı? 😊';
        if (!empty($tukendi)) $metin = $this->dogalListe($ozet) . ' ekledim (' . $this->dogalListe($tukendi) . ' maalesef tükendi). Başka bir arzunuz var mı?';
        return $this->cvp($metin, ['aksiyon' => 'sepet_ekle', 'eklenen' => $eklenen]);
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

    // ==================== HAIKU EMNIYET AGI + OGRENEN ONBELLEK ====================
    /** Kural+kalip kacirinca: ogrenilen onbellek -> (gunluk tavan altinda) Haiku -> ogren. Yoksa null. */
    protected function haikuEmniyet($soru)
    {
        $q = trim((string) $soru);
        if (mb_strlen($q) < 2) return null;
        $key = $this->norm($q);
        if ($key === '') return null;

        // 1) Ogrenilen cevap onbelleginde var mi? (LLM'e gitmeden bedava)
        $cev = $this->ogrenilenCevap($key);
        if ($cev !== null && $cev !== '') return $this->cvp($cev, ['kaynak' => 'ogrenilen']);

        // 2) Haiku acik mi + anahtar + gunluk tavan
        if (!config('services.anthropic.sohbet_acik', true)) return null;
        $anahtar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY'));
        if ($anahtar === '') return null;
        $limit = (int) config('services.anthropic.sohbet_gunluk_limit', 80);
        if ($limit > 0 && $this->haikuGunlukSayac() >= $limit) return null;

        // 3) Haiku'ya sor (menu baglamiyla), ogren, don
        $t = $this->haikuCevap($q, $anahtar);
        if ($t === null || $t === '') return null;
        $this->ogren($key, $t);
        return $this->cvp($t, ['kaynak' => 'haiku']);
    }

    protected function haikuCevap($q, $anahtar)
    {
        $sistem = 'Sen bir RESTORANIN masasindaki dijital GARSON asistanisin. Musteriyle sicak, kibar ve KISA konusursun (en fazla iki cumle). '
            . 'TTS ile seslendirilecegin icin DUZ yaz: emoji, madde, yildiz, tirnak KULLANMA. '
            . 'Asagida MENU verildi; SADECE menudeki urun ve fiyatlari kullan, menude OLMAYAN urun ya da fiyat UYDURMA. Fiyat soylerken "lira" de. '
            . 'Musteri menu/oneri/siparis isterse yardimci ol. Menude olmayan, bilmedigin ya da wifi/adres/rezervasyon/calisma saati gibi isletmeye ozel bir sey sorulursa "garsonumuz size hemen yardimci olsun, cagirayim mi" de. '
            . 'Restoranla tamamen ilgisiz konularda kibarca menuye yonlendir. Sadece Turkce yanit ver.'
            . "\n\nMENU:\n" . $this->menuOzetMetni();
        $govde = [
            'model' => (string) (config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001'),
            'max_tokens' => 200, 'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => $q]],
        ];
        $data = $this->cagirAnthropic($anahtar, $govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        return $t !== '' ? $t : null;
    }

    protected function cagirAnthropic($anahtar, $govde)
    {
        try {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 14,
                CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $anahtar, 'anthropic-version: 2023-06-01'],
                CURLOPT_POSTFIELDS => json_encode($govde, JSON_UNESCAPED_UNICODE),
            ]);
            $yanit = curl_exec($ch);
            $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($yanit === false || $kod !== 200) return null;
            return json_decode($yanit, true);
        } catch (\Throwable $e) { return null; }
    }

    /** Menuyu kompakt metne cevir (Haiku baglami). */
    protected function menuOzetMetni()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->get(['id', 'ad']);
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('tukendi', 0)->get(['ad', 'fiyat', 'kategori_id']);
        $byKat = [];
        foreach ($urunler as $u) $byKat[(int) $u->kategori_id][] = $u->ad . ' (' . number_format((float) $u->fiyat, 0, ',', '') . ' TL)';
        $lines = [];
        foreach ($kats as $k) { if (!empty($byKat[$k->id])) $lines[] = $k->ad . ': ' . implode(', ', array_slice($byKat[$k->id], 0, 20)); }
        return implode("\n", $lines);
    }

    // --- Ogrenen onbellek (soru -> cevap; sube bazli) ---
    protected function ogrenTablo()
    {
        if (!Schema::hasTable('musteri_ai_ogrenilen')) {
            Schema::create('musteri_ai_ogrenilen', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('soru_key', 191);
                $t->text('cevap');
                $t->unsignedInteger('kullanim')->default(1);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'soru_key']);
            });
        }
    }

    protected function ogrenilenCevap($key)
    {
        try {
            $this->ogrenTablo();
            $row = DB::table('musteri_ai_ogrenilen')->where('sube_id', $this->subeId)->where('soru_key', mb_substr($key, 0, 191))->first();
            if ($row) { try { DB::table('musteri_ai_ogrenilen')->where('id', $row->id)->increment('kullanim'); } catch (\Throwable $e) {} return $row->cevap; }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function ogren($key, $cevap)
    {
        try {
            $this->ogrenTablo();
            DB::table('musteri_ai_ogrenilen')->insert(['sube_id' => $this->subeId, 'soru_key' => mb_substr($key, 0, 191), 'cevap' => $cevap, 'kullanim' => 1, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }

    /** Bugun bu sube icin uretilen (yeni) Haiku cevabi sayisi = gunluk tavan sayaci. */
    protected function haikuGunlukSayac()
    {
        try { $this->ogrenTablo(); return (int) DB::table('musteri_ai_ogrenilen')->where('sube_id', $this->subeId)->whereDate('created_at', today())->count(); }
        catch (\Throwable $e) { return 0; }
    }

    // ==================== HAIKU NIYET COZUCU (BEYIN) ====================
    /** Haiku ile niyeti coz + KESIN uygula. Siniflandirma onbellekli (repeat bedava), gunluk tavanli. */
    protected function niyetRouter($soru, $baglam)
    {
        if (!config('services.anthropic.sohbet_acik', true)) return null;
        $anahtar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY'));
        if ($anahtar === '') return null;
        $q = trim((string) $soru);
        if (mb_strlen($q) < 2) return null;
        $key = $this->norm($q) . '|' . $this->norm((string) $baglam);

        $j = $this->niyetCacheAl($key);
        if ($j === null) {
            if ($this->niyetGunlukSayac() >= (int) config('services.anthropic.sohbet_gunluk_limit', 80)) return null;
            $j = $this->niyetCozAI($q, $baglam, $anahtar);
            if (!is_array($j)) return null;
            $this->niyetCacheYaz($key, $j);
        }
        return $this->niyetUygula($j, $q, $baglam); // taze veriyle uygula
    }

    protected function niyetCozAI($q, $baglam, $anahtar)
    {
        $sistem = 'Sen bir restoran masa asistanisin. Kullanicinin mesajini SINIFLANDIR ve SADECE gecerli JSON dondur (baska hicbir sey yazma). '
            . "Urun ve kategori adlarini asagidaki MENUdeki TAM adla eslestir; menude olmayan urun UYDURMA.\n"
            . "niyet degerleri:\n"
            . 'kategori_goster (bir kategoriyi gormek/listelemek; "kategori"=menudeki kategori adi), '
            . 'urun_bilgi (bir urunun icerigi/tanitimi; "urun"=urun adi), '
            . 'siparis_ekle (urun siparis etmek; "urunler"=[{"ad","adet"}]), '
            . 'siparis_ayarla (adet degistir; "urun","adet"), '
            . 'siparis_cikar (bir urunden vazgecmek; "urun"), '
            . 'oneri (ne onerirsin/gunun yemegi), menu (tum menu), '
            . 'garson ("tip"="garson" veya "hesap"), '
            . 'bitir (siparisi tamamla / hayir baska yok), '
            . 'sohbet (selam/tesekkur/genel ya da menu disi soru; "cevap"=musteriye kisa sicak DUZ yanit, emoji ve tirnak yok, menu disi bilgiyi garsona yonlendir). '
            . 'baglam verilirse "onu/bunu/sunu" gibi ifadelerde bu urunu kullan. '
            . 'SADECE su JSON semasi: {"niyet":"","kategori":null,"urun":null,"urunler":[],"adet":null,"tip":null,"cevap":null}'
            . ($baglam ? ("\n\nbaglam (son konusulan urun): " . $baglam) : '')
            . "\n\nMENU:\n" . $this->menuOzetMetni();
        $govde = [
            'model' => (string) (config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001'),
            'max_tokens' => 320, 'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => $q]],
        ];
        $data = $this->cagirAnthropic($anahtar, $govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if (preg_match('/\{.*\}/s', $t, $m)) $t = $m[0];
        $j = json_decode($t, true);
        return is_array($j) ? $j : null;
    }

    protected function niyetUygula($j, $q, $baglam)
    {
        switch ((string) ($j['niyet'] ?? '')) {
            case 'kategori_goster':
                $kat = $this->kategoriAdBul((string) ($j['kategori'] ?? ''));
                return $kat ? $this->kategori([$this->norm($kat->ad)], $kat->ad, $this->katEmoji($kat->ad)) : $this->menu();
            case 'urun_bilgi':
                $u = $this->urunAdBul((string) ($j['urun'] ?? '')) ?: $this->urunAdBulGevsek((string) ($j['urun'] ?? '')) ?: ($baglam ? $this->urunAdBul($baglam) : null);
                return $u ? $this->urunTanit($u, $q) : null;
            case 'siparis_ekle':
                $lines = [];
                foreach ((array) ($j['urunler'] ?? []) as $it) {
                    $ad = is_array($it) ? ($it['ad'] ?? '') : $it;
                    $adet = is_array($it) ? (int) ($it['adet'] ?? 1) : 1;
                    $u = $this->urunAdBul((string) $ad) ?: $this->urunAdBulGevsek((string) $ad);
                    if ($u) $lines[] = ['u' => $u, 'adet' => max(1, $adet)];
                }
                if (!$lines) return $this->cvp('Hangi ürünü almak istersiniz? Ürün adını söylemeniz yeterli. 😊', ['aksiyon' => 'siparis_basla']);
                // "X olsun / X yap" + TEK urun -> ADEDI AYARLA (topla degil), Haiku ekle dese bile
                if (count($lines) === 1 && $this->has($this->norm($q), ['olsun', 'yap', 'olacak', 'yapalim', 'guncelle', 'degistir'])) {
                    $u = $lines[0]['u'];
                    $adet = $lines[0]['adet'];
                    return $this->cvp($u->ad . ' adedini ' . $adet . ' yaptım. Başka bir arzunuz var mı? 😊',
                        ['aksiyon' => 'sepet_ayarla', 'eklenen' => [['urun_id' => (int) $u->id, 'ad' => $u->ad, 'adet' => $adet, 'fiyat' => (float) $u->fiyat]]]);
                }
                return $this->sepetEkleCevap($lines);
            case 'siparis_ayarla':
                $u = $this->urunAdBul((string) ($j['urun'] ?? '')) ?: $this->urunAdBulGevsek((string) ($j['urun'] ?? '')) ?: ($baglam ? $this->urunAdBul($baglam) : null);
                if (!$u) return null;
                $adet = max(1, (int) ($j['adet'] ?? 1));
                return $this->cvp($u->ad . ' adedini ' . $adet . ' yaptım. Başka bir arzunuz var mı? 😊',
                    ['aksiyon' => 'sepet_ayarla', 'eklenen' => [['urun_id' => (int) $u->id, 'ad' => $u->ad, 'adet' => $adet, 'fiyat' => (float) $u->fiyat]]]);
            case 'siparis_cikar':
                $u = $this->urunAdBul((string) ($j['urun'] ?? '')) ?: $this->urunAdBulGevsek((string) ($j['urun'] ?? '')) ?: ($baglam ? $this->urunAdBul($baglam) : null);
                return $u ? $this->cvp($u->ad . ', siparişinizden çıkardım. Başka bir arzunuz var mı? 😊',
                    ['aksiyon' => 'sepet_cikar', 'cikar' => ['urun_id' => (int) $u->id, 'ad' => $u->ad]]) : null;
            case 'oneri': return $this->oneri();
            case 'menu': return $this->menu();
            case 'garson':
                $hesap = (($j['tip'] ?? '') === 'hesap');
                return $this->cvp($hesap ? 'Garsonumuza hesabınızı iletmesini söyledim, birazdan yanınızda olacak. 🙋' : 'Garsonumuzu masanıza çağırdım, birazdan geliyor. 🙋',
                    ['aksiyon' => 'garson_cagir', 'tip' => $hesap ? 'hesap' : 'garson']);
            case 'bitir': return $this->cvp('Tamamdır, siparişinizi bağlıyorum. 😊', ['aksiyon' => 'siparis_bitir']);
            case 'sohbet':
                $cev = trim((string) ($j['cevap'] ?? ''));
                return $cev !== '' ? $this->cvp($cev, ['kaynak' => 'niyet']) : null;
            default: return null; // bilinmeyen -> kelime-kural zincirine dus
        }
    }

    protected function kategoriAdBul($ad)
    {
        $na = $this->norm($ad);
        if ($na === '') return null;
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->get(['id', 'ad']);
        return $kats->first(fn ($k) => $this->norm($k->ad) === $na)
            ?: $kats->first(fn ($k) => strpos($this->norm($k->ad), $na) !== false || strpos($na, $this->norm($k->ad)) !== false);
    }

    protected function urunAdBulGevsek($ad)
    {
        $na = $this->norm($ad);
        if (mb_strlen($na) < 3) return null;
        return DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)
            ->select('id', 'ad', 'fiyat', 'aciklama', 'tukendi', 'kategori_id')->get()
            ->first(fn ($u) => strpos($this->norm($u->ad), $na) !== false || strpos($na, $this->norm($u->ad)) !== false);
    }

    protected function niyetTablo()
    {
        if (!Schema::hasTable('musteri_ai_niyet')) {
            Schema::create('musteri_ai_niyet', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('soru_key', 191);
                $t->text('niyet_json');
                $t->unsignedInteger('kullanim')->default(1);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'soru_key']);
            });
        }
    }
    protected function niyetCacheAl($key)
    {
        try {
            $this->niyetTablo();
            $row = DB::table('musteri_ai_niyet')->where('sube_id', $this->subeId)->where('soru_key', mb_substr($key, 0, 191))->first();
            if ($row) { try { DB::table('musteri_ai_niyet')->where('id', $row->id)->increment('kullanim'); } catch (\Throwable $e) {} $d = json_decode($row->niyet_json, true); return is_array($d) ? $d : null; }
        } catch (\Throwable $e) {}
        return null;
    }
    protected function niyetCacheYaz($key, $j)
    {
        try { $this->niyetTablo(); DB::table('musteri_ai_niyet')->insert(['sube_id' => $this->subeId, 'soru_key' => mb_substr($key, 0, 191), 'niyet_json' => json_encode($j, JSON_UNESCAPED_UNICODE), 'kullanim' => 1, 'created_at' => now()]); } catch (\Throwable $e) {}
    }
    protected function niyetGunlukSayac()
    {
        try { $this->niyetTablo(); return (int) DB::table('musteri_ai_niyet')->where('sube_id', $this->subeId)->whereDate('created_at', today())->count(); }
        catch (\Throwable $e) { return 0; }
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
            'gercek_foto' => (bool) $yuklenen,   // TRUE = isletmenin yukledigi gercek foto (stok/loremflickr degil)
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
        // Kelime SINIRINDA (ek toleransli) esles: " et" -> "et"/"etli"/"et yemek" evet ama "lezzeti" HAYIR.
        $hay = ' ' . $c . ' ';
        foreach ($ks as $k) {
            $kk = $this->norm($k);
            if ($kk !== '' && strpos($hay, ' ' . $kk) !== false) return true;
        }
        return false;
    }

    /** Genis kufur/hakaret tespiti (kelime sinirinda; sikayet/sikinti/malzeme gibi masum kelimeleri tetiklemez). */
    protected function kufurMu($c)
    {
        $n = ' ' . $c . ' ';
        // Kelime sinirinda (ek toleransli) eslesen net kufur/hakaret kokleri. Kisa/riskli olanlar (sik, mal, bok) ekli formda.
        $set = [
            'amk', 'amq', 'aq', 'amina', 'aminako', 'amcik', 'amcigin', 'amina koyay', 'amina kodu', 'aminakoyum',
            'anani sik', 'ananisik', 'anasini sik', 'avradini', 'avradina', 'avradinin', 'sulaleni', 'sulaleni sik', 'sikeyim seni',
            'orospu', 'orospucocu', 'orospu cocu', 'o cocugu', 'pic ', 'picler', 'piclik', 'kahpe', 'kahpelik',
            'surtuk', 'yavsak', 'yavsagi', 'pezevenk', 'gavat', 'godos', 'godoş', 'ibne', 'ibnelik', 'pust', 'kaltak', 'kevase', 'kevaşe',
            'serefsiz', 'namussuz', 'sik tir', 'siktir', 'siktir', 'sikeyim', 'sikeym', 'sikik', 'sikko', 'sikici', 'siktigim', 'sikims', 'sikimde', 'sikimsonik',
            'yarrak', 'yarrag', 'yarak', 'tasak', 'tasagi', 'gotveren', 'gotlek', 'gotunden', 'gotune koy',
            'boktan', 'bokla', 'boklu', 'bok herif', 'bok cuval', 'sicayim', 'sicarim', 'osuruk', 'osuruk',
            'salak', 'aptal', 'gerizekali', 'gerzek', 'dangalak', 'denyo', 'embesil', 'ahmak', 'dallama', 'hoduk', 'mal herif', 'mal misin', 'defol', 'gebersin', 'geber',
        ];
        foreach ($set as $k) {
            $kk = $this->norm($k);
            if ($kk === '') continue;
            if (preg_match('/(?:^| )' . preg_quote($kk, '/') . '/u', $n)) return true;
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

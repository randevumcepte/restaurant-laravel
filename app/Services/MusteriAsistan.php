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
    protected $_oneVar = null;   // urunler.one_cikan kolonlari mevcut mu (memoize)

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    /** Isletmenin "one cikan urun" kolonlari kurulu mu? (yonetim sayfasi ilk kayitta kurar) */
    protected function oneCikanKolonVar()
    {
        if ($this->_oneVar === null) {
            try { $this->_oneVar = Schema::hasColumn('urunler', 'one_cikan'); }
            catch (\Throwable $e) { $this->_oneVar = false; }
        }
        return $this->_oneVar;
    }

    /** Urun ICECEK mi? Ad + kategoriden anlar (kategori bos/yanlis olsa bile ad'dan yakalar).
why: "yemek oner" derken meyve suyu/su cikmasin. */
    protected function icecekMi($ad, $kat)
    {
        if (trim($this->norm($ad)) === 'su') return true;
        $n = ' ' . $this->norm($ad) . ' ';
        $adKok = ['meyve suyu', 'ayran', 'kola', 'fanta', 'sprite', 'soda', 'gazoz', 'limonata', 'maden suyu', 'cay', 'kahve', 'latte', 'espresso', 'cappuccino', 'mocha', 'milkshake', 'smoothie', 'kokteyl', 'kokteil', 'bira', 'sarap', 'nescafe', 'frappe', 'sahlep', 'boza', 'ice tea', 'ice coffee', 'sinalco', 'kombucha', 'sicak cikolata', 'menta'];
        foreach ($adKok as $x) { $xx = $this->norm($x); if ($xx !== '' && strpos($n, ' ' . $xx) !== false) return true; }
        $k = $this->norm($kat);
        if ($k !== '') { foreach (['icecek', 'mesrubat', 'kahve', 'cay', 'soft', 'shake', 'smoothie'] as $x) { if (strpos(' ' . $k . ' ', ' ' . $x) !== false) return true; } }
        return false;
    }

    public function cevapla($soru, $baglam = null)
    {
        $c = $this->norm($soru);
        if ($c === '') return $this->cvp('Sizi dinliyorum. Menümüzü sorabilir, öneri isteyebilir ya da garson çağırabilirsiniz.');

        // 0) KUFUR / HAKARET -> saygiya davet (devam ederse on yuz kapatir)
        if ($this->kufurMu($c)) {
            return $this->cvp('Efendim, sizi saygıya davet ediyorum. Eğer böyle konuşmaya devam ederseniz maalesef görüşmeyi kapatmak zorunda kalacağım.', ['aksiyon' => 'kufur']);
        }

        // 0.4) ACIL DURUM (EN YUKSEK ONCELIK): saglik/yangin/guvenlik/cocuk -> normal akisi BIRAK, personele ACIL alarm + kisa yonlendirme
        if ($this->acilMi($c)) {
            return $this->acilDurumCevap();
        }

        // 0.5) SIPARISI BITIR / ONAY (mutfaga gonder). Selam/tesekkur/kimlikten ONCE ki "tesekkurler sadece bunlar" bitir sayilsin.
        //      Cevap FRONTEND'de ozet+onay olarak kurulur (sepet frontend'de). Haiku sart degil.
        if ($this->has($c, ['bu kadar', 'hepsi bu', 'baska yok', 'baska bir sey yok', 'baska istemiyorum', 'baska bir sey istemiyorum', 'sadece bunlar', 'bunlar kadar', 'tamam bunlar', 'yeterli bu', 'siparisi gonder', 'siparisi tamamla', 'siparisi bitir', 'siparisi ver', 'siparisi onayla', 'mutfaga gonder', 'mutfaga ilet', 'siparisim tamam', 'siparis tamam', 'tamam gonder', 'onaylayip gonder', 'siparisimi ver', 'siparisimi gonder', 'siparisimi tamamla', 'siparisimi onayla'])) {
            return $this->cvp('Siparişinizi özetliyorum.', ['aksiyon' => 'siparis_bitir']);
        }

        // 1) Kimlik / selam / tesekkur (musteri dostu)
        if ($this->has($c, ['sen kimsin', 'kimsin', 'adin ne', 'nesin', 'ne yapabilir', 'neler yapabilir', 'ne ise yara', 'gorevin ne'])) {
            return $this->cvp('Ben masanızın dijital asistanıyım. Menüyü tanıtabilir, öneride bulunabilir, günün yemeğini söyleyebilir ya da garson çağırabilirim. Ne yapmak istersiniz?');
        }
        if ($this->has($c, ['merhaba', 'merhabalar', 'selam', 'selamlar', 'selamun aleykum', 'gunaydin', 'iyi gunler', 'iyi aksamlar', 'iyi geceler', 'iyi sabahlar', 'alo', 'hey', 'kolay gelsin', 'orada misin', 'burada misin', 'burda misin', 'musait misin', 'bakar misin', 'yardimci olur musun', 'yardimci olabilir misin', 'yardim eder misin', 'beni duyuyor musun', 'sesimi duyuyor musun', 'hazir misin', 'baslayalim', 'bir sey soracagim', 'bir sey sorabilir miyim', 'bir sey danisacagim'])) {
            // Selamdan SONRA baska istek var mi? "merhaba bugun menude ne var" -> selami AT, asil istegi isle
            $kalan = ' ' . $c . ' ';
            // UZUN ifadeleri ONCE cikar (kisa parca yanlis kalmasin)
            foreach (['selamun aleykum', 'bir sey sorabilir miyim', 'bir sey soracagim', 'bir sey danisacagim', 'yardimci olabilir misin', 'yardimci olur musun', 'yardim eder misin', 'beni duyuyor musun', 'sesimi duyuyor musun', 'orada misin', 'burada misin', 'burda misin', 'musait misin', 'bakar misin', 'hazir misin', 'kolay gelsin', 'iyi aksamlar', 'iyi gunler', 'iyi geceler', 'iyi sabahlar', 'ne haber', 'merhabalar', 'merhaba', 'selamlar', 'selam', 'gunaydin', 'nasilsiniz', 'nasilsin', 'naber', 'baslayalim', 'alo', 'hey'] as $s) {
                $kalan = str_replace(' ' . $this->norm($s) . ' ', ' ', $kalan);
            }
            $kalan = trim(preg_replace('/\s+/', ' ', $kalan));
            if (mb_strlen($kalan) < 3) {
                return $this->selamlamaCevap(); // 50 varyasyonlu sicak karsilama (rastgele)
            }
            // Selam + gercek istek -> selami dusur, ASIL istegi normal isle (asagida devam)
            $c = $kalan;
            $soru = $kalan;
        }
        if ($this->has($c, ['tesekkur', 'sagol', 'sag ol', 'eyvallah', 'minnettar'])) {
            return $this->cvp('Rica ederim, afiyet olsun! 😊');
        }

        // 2) ODEME / HESAP -> guvenli ONLINE odeme sayfasina yonlendir ("odeme yapmak istiyorum", "hesap", "kartla ode")
        if ($this->has($c, ['odeme', 'odemek', 'odeyecegim', 'odeyeyim', 'odeme yap', 'online ode', 'kartla ode', 'kart ile ode', 'telefondan ode', 'hesap', 'hesabi ode', 'hesabimi ode', 'hesabi getir', 'hesabi al', 'adisyon', 'borcum ne', 'ne kadar odeyec'])) {
            return $this->cvp('Tabii efendim, sizi güvenli ödeme sayfasına yönlendiriyorum. 💳', ['aksiyon' => 'ode']);
        }
        // 2b) GARSON cagir (odeme/hesap disi yardim)
        if ($this->has($c, ['garson', 'biri gelsin', 'cagir', 'garsonu cagir', 'yardim istiyorum', 'yardim eder'])) {
            return $this->cvp('Garsonumuzu masanıza çağırdım, birazdan geliyor. 🙋', ['aksiyon' => 'garson_cagir']);
        }
        // 2c) KAMPANYA / INDIRIM (aktif indirimleri GERCEK veriden soyler)
        if ($this->has($c, ['kampanya', 'indirim', 'firsat', 'promosyon', 'kampanyaniz', 'indiriminiz', 'avantaj', 'kupon var'])) {
            return $this->kampanyaBilgisi();
        }
        // 2d) WIFI / INTERNET (tanimliysa GERCEK sifre; yoksa nazik yonlendirme — ASLA uydurmaz)
        if ($this->has($c, ['wifi', 'wifii', 'internet', 'kablosuz', 'wireless', 'ag sifre', 'ag adi'])) {
            return $this->wifiBilgisi();
        }

        // 2.25) MENU TANITIMI (genel): "menude ne var / neler var / menuyu goster / tum menu" -> TUM kategoriler kibar garson edasiyla.
        //       niyetRouter(Haiku)'dan ONCE: "menude ne var" tek kategoriye (baslangic) saptirilmasin.
        if ($this->has($c, ['menude ne', 'menude neler', 'menu de ne', 'menuyu goster', 'menuyu tanit', 'menu tanit', 'tum menu', 'butun menu', 'komple menu', 'menuyu ac', 'menunuzde ne', 'menunuzde neler', 'nasil bir menu', 'menu nedir', 'menuye bak', 'menuyu ver', 'neler var menu', 'menude neler mevcut'])) {
            return $this->menu();
        }

        // 2.4) SIPARIS DUZENLE (deterministik, guvenilir): "kofteyi 2 olsun" (AYARLA), "kolayi istemiyorum" (CIKAR)
        $duz = $this->siparisDuzenle($c, $soru, $baglam);
        if ($duz) return $duz;

        // 2.6) SOHBET MODULU (BEDAVA, Haiku'dan ONCE): nasilsin/iltifat/ilk defa/kararsizlik/vedalasma -> sicak garson yaniti
        $sohbet = $this->sohbetCevap($c);
        if ($sohbet) return $sohbet;

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

        // 3) Gunun yemegi / sef onerisi  (Turkce yumusama: "yemegi"[tekil] vs "yemekleri"[cogul] farkli normalize olur
        //    -> "gunun yeme" ikisini de yakalar: yeme-gi / yeme-kleri / yeme-k)
        if ($this->has($c, ['gunun yeme', 'gunun spesiyal', 'gunun menu', 'gunun onerisi', 'gunun favori', 'gunun lezzet', 'sef onerisi', 'sef spesiyal', 'spesiyal', 'bugun ne var', 'bugun ne yiye', 'bugun ne yesek', 'bugun ne guzel', 'ne onerirsin', 'ne oneri', 'ne tavsiye', 'oneri', 'populer', 'en cok', 'en sevilen', 'favori', 'ne yesem', 'ne yiyeyim', 'ne yiyelim', 'ne yemeli', 'nefis ne var', 'karnim ac', 'aciktim', 'karnim cok ac', 'doyurucu bir'])) {
            return $this->oneri($c);
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

        // 7) Kural+kalip KACIRDI -> egitim icin "cozulmeyen" olarak kaydet (panelde tek tikla kalibi eklenir)
        $this->cozulmeyenKaydet($soru);

        // 8) HAIKU EMNIYET AGI: ogrenilen onbellek -> (gerekirse) Haiku -> ogren
        $ai = $this->haikuEmniyet($soru);
        if ($ai !== null) return $ai;

        // 8) Fallback — cevaplayamadi: uydurmaz, sicak "garson teyit" havuzu (50 varyasyon)
        return $this->sistemBilgiYokCevap();
    }

    /** TUM menu: her kategori + urun kartlari (musteri kendi basina inceler). */
    public function menuTam()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->orderBy('ad')->get(['id', 'ad']);
        $puanlar = $this->urunPuanlari();   // [urun_id => ['ort'=>x, 'say'=>y]]
        $out = [];
        $one = $this->oneCikanKolonVar();
        foreach ($kats as $k) {
            $cols = ['id', 'ad', 'fiyat', 'aciklama', 'tukendi'];
            if ($one) { $cols[] = 'one_cikan'; $cols[] = 'one_sira'; }
            $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('kategori_id', $k->id)
                ->where('aktif', 1)->orderBy('ad')->get($cols);
            // One cikanlar kategoride EN BASA (one_sira'ya gore)
            if ($one) $urunler = $urunler->sortByDesc(fn ($u) => !empty($u->one_cikan) ? (1000 - (int) $u->one_sira) : 0)->values();
            $kartlar = $urunler->map(function ($u) use ($k, $puanlar, $one) {
                $et = $u->tukendi ? 'Tükendi' : (($one && !empty($u->one_cikan)) ? 'Şefin Önerisi' : null);
                $kart = $this->kart($u->ad, $u->fiyat, $u->aciklama, $k->ad, $et, [], $u->id);
                if (isset($puanlar[$u->id])) { $kart['puan'] = $puanlar[$u->id]['ort']; $kart['puan_say'] = $puanlar[$u->id]['say']; }
                return $kart;
            })->all();
            if (!empty($kartlar)) $out[] = ['ad' => $k->ad, 'emoji' => $this->katEmoji($k->ad), 'kartlar' => $kartlar];
        }
        return ['ok' => 1, 'kategoriler' => $out];
    }

    /** Bu subenin urun puan ortalamalari [urun_id => ['ort'=>1.0-5.0, 'say'=>N]]. Tablo yoksa bos. */
    public function urunPuanlari()
    {
        try {
            if (!Schema::hasTable('urun_puanlari')) return [];
            $rows = DB::table('urun_puanlari')->where('sube_id', $this->subeId)
                ->selectRaw('urun_id, ROUND(AVG(puan),1) as ort, COUNT(*) as say')->groupBy('urun_id')->get();
            $map = [];
            foreach ($rows as $r) $map[$r->urun_id] = ['ort' => (float) $r->ort, 'say' => (int) $r->say];
            return $map;
        } catch (\Throwable $e) { return []; }
    }

    // -------- MENU HANDLERS --------
    protected function menu()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->orderBy('ad')->get(['id', 'ad']);
        if ($kats->isEmpty()) return $this->cvp('Menü şu an hazırlanıyor, birazdan hazır olacak.');
        $one = $this->oneCikanKolonVar();
        // KISA tanitim: kategori adlari EKRANDA kartlarla; sesli okuma uzun/can sikici olmasin
        // (eskiden her kategori icin uzun cumle kuruluyordu -> dakikalarca konusuyordu, soz kesilemiyordu).
        $kartlar = [];
        foreach ($kats as $k) {
            $kartlar[] = ['ad' => $k->ad, 'emoji' => $this->katEmoji($k->ad)];
        }
        $adlar = $kats->pluck('ad')->implode(', ');
        // Uzun anlatim yerine TEK one cikan urunu bir kez oner (upsell korunur ama kisa)
        $ozel = null;
        if ($one) {
            $ozel = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)
                ->where('tukendi', 0)->where('one_cikan', 1)->orderBy('one_sira')->value('ad');
        }
        $mesaj = 'Elbette, menümüzde şu bölümler var: ' . $adlar . '.';
        if ($ozel) $mesaj .= ' Bugün özellikle ' . $ozel . ' öneririm.';
        $mesaj .= ' Hangisine bakmak istersiniz? Ekrandan dokunabilir ya da "günün önerisi ne" diyebilirsiniz.';
        // Sesli okuma icin daha da kisa metin (kategori adlarini tek tek okumak yerine sayisini soyler)
        $sesMetni = 'Menümüzde ' . $kats->count() . ' bölüm var'
            . ($ozel ? '; bugün özellikle ' . $ozel . ' öneririm' : '')
            . '. Ekranda göstererek anlatıyorum — hangisine bakalım, dokunmanız ya da söylemeniz yeterli.';
        return $this->cvp($mesaj, ['tip' => 'kategoriler', 'kategoriler' => $kartlar, 'seslendir_metni' => $sesMetni]);
    }

    protected function kategori(array $normAdlar, $baslik, $emoji = '')
    {
        $one = $this->oneCikanKolonVar();
        $sel = ['urunler.id', 'urunler.ad', 'urunler.fiyat', 'urunler.aciklama', 'menu_kategorileri.ad as kat'];
        if ($one) { $sel[] = 'urunler.one_cikan'; $sel[] = 'urunler.one_soz'; $sel[] = 'urunler.one_ai_soz'; $sel[] = 'urunler.one_sira'; }
        $urunler = DB::table('urunler')->join('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->where('urunler.aktif', 1)->where('urunler.tukendi', 0)
            ->select($sel)->get()->filter(fn ($u) => in_array($this->norm($u->kat), $normAdlar))->values();
        if ($urunler->isEmpty()) return $this->cvp($baslik . ' şu an listede görünmüyor.');
        // One cikanlar EN BASA (one_sira'ya gore), sonra digerleri
        if ($one) $urunler = $urunler->sortByDesc(fn ($u) => !empty($u->one_cikan) ? (1000 - (int) $u->one_sira) : 0)->values();
        $kartlar = $urunler->take(12)->map(function ($u) use ($one) {
            $et = ($one && !empty($u->one_cikan)) ? 'Şefin Önerisi' : null;
            return $this->kart($u->ad, $u->fiyat, $u->aciklama, $u->kat, $et, [], $u->id);
        })->all();
        // Istah kabartici GIRIS: bu kategoride one cikan urunun AI cumlesi (yoksa ham notu) varsa onu soyle
        $vitrin = $one ? $urunler->first(fn ($u) => !empty($u->one_cikan) && (!empty($u->one_ai_soz) || !empty($u->one_soz))) : null;
        if ($vitrin) {
            $vsoz = trim((string) (($vitrin->one_ai_soz ?? '') ?: ($vitrin->one_soz ?? '')));
            return $this->cvp("$emoji " . $vsoz . ' Aşağıdakilere göz atabilirsiniz. 😊',
                ['tip' => 'urunler', 'baslik' => $baslik, 'kartlar' => $kartlar]);
        }
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

    /**
     * SOHBET MODULU (BEDAVA, Haiku'dan ONCE): sicak garson edasiyla sosyal iletisim.
     * Menu/siparis DEGIL; nasilsin/iltifat/ilk defa/kararsizlik/vedalasma. Eslesmezse null (akis devam).
     */
    protected function sohbetCevap($c)
    {
        // AI ANLAMADI / TEKRAR: "anlamadin, yanlis anladin, tekrar soyle, bir daha anlat" -> nazikce yeniden ifade iste
        if ($this->has($c, ['anlamadin', 'beni anlamadin', 'beni anlamiyorsun', 'yanlis anladin', 'yanlis anladin beni', 'onu demedim', 'onu soylemedim', 'oyle demedim', 'hayir oyle degil', 'ne dedigimi anlamadin', 'soylediklerimi anlamadin', 'tekrar soyle', 'tekrar eder misin', 'tekrarlar misin', 'bir daha soyle', 'yeniden soyle', 'tekrar anlat', 'bir daha anlat', 'anlayamadim', 'anlamadim', 'seni anlamadim', 'bir daha soyler misin', 'tekrar konusur musun', 'yeniden soyler misin', 'tekrar aciklar misin', 'biraz daha acik anlat', 'daha acik soyler misin', 'daha anlasilir soyle', 'ne demek istiyorsun', 'ne demek istedin', 'neyi kastettin', 'ne soyledigini anlamadim', 'soyledigini anlayamadim', 'bastan soyle', 'bastan anlat', 'yeniden baslayalim', 'farkli bir sey soyledim', 'baska bir sey demek istedim'])) {
            return $this->anlamadimCevap();
        }
        // ILETISIM / SES-MIKROFON PROBLEMI: "beni duyuyor musun / mikrofon calismiyor / gurultulu / ne dedin / daha yavas"
        // NOT: "sundan bir tane / bir tane daha / yanina da ondan" = BAGLAMLI SIPARIS -> buraya DAHIL DEGIL (siparis motoru isler).
        if ($this->has($c, ['beni duyuyor musun', 'beni duyabiliyor musun', 'duyuyor musun', 'sesim geliyor mu', 'sesimi aldin', 'sesim gitmedi', 'beni duymuyorsun', 'beni duymadin', 'sesimi alamadin', 'sesimi algilamiyor', 'mikrofon calismiyor', 'mikrofon iyi cekmiyor', 'mikrofon', 'sesim kesiliyor', 'ses kesiliyor', 'baglanti gidip', 'sistem beni duymuyor', 'sesli konusamiyorum', 'ortam gurultulu', 'cok ses var', 'cok gurultu', 'gurultulu', 'daha yavas soyle', 'daha yavas soyler', 'yavas soyler misin', 'ne dedin', 'son soyledigini', 'ne soyledigini kacirdim', 'ne dedigini kacirdin'])) {
            return $this->iletisimSorunuCevap();
        }
        // TEKNIK HATA / ISLEM SUPHESI: "sistem calismiyor / siparisim gorunmuyor / islem gerceklesmedi" -> VARSAYMA, garson teyit
        if ($this->has($c, ['sistem calismiyor', 'sistem hata', 'bir hata var', 'hata verdi', 'uygulama dondu', 'ekranda bir sey cikmiyor', 'siparisim gorunmuyor', 'siparis gorunmuyor', 'siparis kayboldu', 'islem gerceklesmedi', 'islem olmadi', 'sistem cevap vermiyor', 'sistem bunu gostermiyor', 'bilgi gelmedi'])) {
            return $this->teknikHataCevap();
        }
        // BILGI YOK / CELISKI / ISRAR-TAHMIN -> uydurmaz, garson teyit ( URUN/MENU/FIYAT sorulari DAHIL DEGIL; onlari veri motoru cevaplar)
        if ($this->has($c, ['bunu bilmiyor musun', 'bunun bilgisi yok', 'bu konuda bilgin yok', 'neden bilmiyorsun', 'bunun cevabini bilmiyor', 'sistemde kayitli degil', 'bu bilgiye ulasamiyor', 'bunu soyleyemiyor musun', 'tahmin et', 'yaklasik soyle', 'bilmiyorsan tahmin', 'sen bana soyle', 'garsonu cagirma sen', 'bir sekilde ogren', 'garson baska soyledi', 'garson yok dedi', 'garson bunun olmadigini', 'menude farkli yaziyor', 'az once var demistin', 'sistemde var ama garson', 'soyledigin dogru degil', 'bu bilgi yanlis', 'buradaki bilgi guncel degil', 'bu bilgi degismis'])) {
            return $this->sistemBilgiYokCevap();
        }
        // KARARSIZ MUSTERI:
        // (a) Secimi bize DEVREDERSE ("sen sec / rastgele / fark etmez") -> dogrudan ONERI motoru (bir sey sun)
        if ($this->has($c, ['sen sec', 'sen karar ver', 'sen bir sey soyle', 'sen soyle', 'sen olsan', 'rastgele bir sey', 'rastgele oner', 'fark etmez sen', 'bana bir sey sec', 'bana guzel bir sey sec', 'karar vermeme yardim', 'bana seçim yaptir', 'bana secim yaptir', 'sen sec bir'])) {
            return $this->oneri($c);
        }
        // (b) Genel kararsizlik -> ONCE tercih sor (uzun liste DOKME), 50 varyasyon
        if ($this->has($c, ['karar veremedim', 'karar veremiyor', 'kararsiz', 'ne yesem', 'ne yiyeyim', 'ne yisem', 'hangisini secsem', 'hangisini alsam', 'hangisini seceyim', 'hangisi daha iyi', 'hangisi guzel', 'ikisi arasinda kaldim', 'iki yemek arasinda', 'uc yemek arasinda', 'ucu arasinda', 'secemedim', 'secemiyorum', 'ne istedigimi bilmiyor', 'ne yiyecegimi bilmiyor', 'ne siparis edecegimi', 'aklim karisti', 'aklima gelmiyor', 'canim bir sey istiyor', 'canim cekiyor ama', 'cok fazla secenek', 'hepsi guzel gorunuyor', 'ne tavsiye', 'ne onerirsin', 'ne onerirsiniz', 'favoriniz hangisi', 'burada ne yenir', 'ne yiyeyim bilmiyor', 'bir turlu secemedim', 'bir turlu karar', 'bir seyler onerir', 'bir oneride bulun'])) {
            return $this->kararsizCevap();
        }
        // Nasilsin / hal hatir
        if ($this->has($c, ['nasilsin', 'naber', 'ne haber', 'iyi misin', 'keyifler', 'napiyorsun', 'ne yapiyorsun'])) {
            return $this->cvp($this->rastgele([
                'Çok iyiyim, sorduğunuz için teşekkürler! Bugün size güzel bir sofra kuralım mı?',
                'Turp gibiyim, sağ olun! Canınız ne çekiyor, birlikte bakalım mı?',
                'Harikayım, sizi ağırlamak için buradayım! Menüyü mü tanıtayım yoksa bir önerim mi olsun?',
            ]));
        }
        // Iltifat (MEKANA; yemek sorusuyla karismasin diye yer-odakli tetikler)
        if ($this->has($c, ['burasi guzel', 'burasi cok guzel', 'mekan guzel', 'mekaniniz guzel', 'ambiyans', 'ortam guzel', 'cok sik', 'begendim burayi', 'burayi begendim', 'burasi hos', 'guzel yer', 'harika yer', 'dekor', 'huzurlu yer'])) {
            return $this->cvp($this->rastgele([
                'Çok teşekkür ederiz, beğenmenize sevindik! Bir de lezzetlerimizi deneyin, damağınızda iz bırakır.',
                'Ne güzel söylediniz, sağ olun! İzin verirseniz keyfinizi tamamlayacak bir öneride bulunayım.',
                'Duymak çok hoş, teşekkürler! Size en sevilenlerimizden birini önereyim mi?',
            ]));
        }
        // Ilk defa geldim
        if ($this->has($c, ['ilk defa', 'ilk kez', 'ilk gelis', 'ilk seferim', 'yeni geldim', 'daha once gelmedim', 'ilk ziyaret'])) {
            return $this->cvp($this->rastgele([
                'Hoş geldiniz, aramıza katılmanıza çok sevindik! İlk kez geldiyseniz en beğenilen lezzetlerimizi önereyim mi?',
                'Ne güzel, ilk ziyaretiniz şerefine en sevilenlerimizi göstereyim mi?',
            ]));
        }
        // Kalabalik / yogun
        if ($this->has($c, ['kalabalik', 'cok dolu', 'yogunsunuz', 'yogun bugun', 'cok musteri', 'yer yokmus'])) {
            return $this->cvp('Evet, bugün ilginize doyamıyoruz, çok teşekkürler! Siz keyfinize bakın; ne arzu edersiniz, hemen ilgileneyim.');
        }
        // Sohbet edelim / sikildim
        if ($this->has($c, ['sohbet edelim', 'muhabbet edelim', 'biraz konusalim', 'canim sikildi', 'sikildim', 'laf olsun'])) {
            return $this->cvp('Memnuniyetle! Ben masanızın dijital garsonuyum; hem sohbet ederiz hem de canınızın çektiği bir şey olursa hemen ayarlarız. Ne dersiniz?');
        }
        // Guzel soz / takdir
        if ($this->has($c, ['harikasin', 'cok iyisin', 'iyi ki varsin', 'bravo', 'helal', 'cok tatlisin', 'akillisin'])) {
            return $this->cvp('Çok naziksiniz, teşekkür ederim! Sizi mutlu etmek için buradayım — ne arzu edersiniz?');
        }
        // Vedalasma
        if ($this->has($c, ['gorusuruz', 'hosca kal', 'hoscakal', 'bay bay', 'baybay', 'kendine iyi bak', 'cikiyoruz', 'gidiyoruz artik'])) {
            return $this->cvp('Bizi tercih ettiğiniz için teşekkürler, yine bekleriz! Afiyet olsun.');
        }
        // Panelden eklenen SOHBET/KIMLIK kaliplari (ChatGPT ile toplu yuklenebilir) — kodda yoksa buradan
        $sk = $this->sohbetKalip($c);
        if ($sk) return $sk;
        return null;
    }

    /** SOHBET/KIMLIK kategorisindeki kaliplari eslestir (sohbet katmaninda; genel kalip() bunlari haric tutar). */
    protected function sohbetKalip($soru)
    {
        try {
            if (!Schema::hasTable('asistan_kalip')) return null;
            $liste = DB::table('asistan_kalip')->where('aktif', 1)
                ->whereIn('kategori', ['sohbet', 'kimlik'])->select('id', 'tetikleyiciler', 'cevap')->get();
            if ($liste->isEmpty()) return null;
            $n = ' ' . $this->norm($soru) . ' ';
            $enIyi = null;
            $enSkor = 0;
            foreach ($liste as $k) {
                foreach (preg_split('/[\r\n,;]+/', (string) $k->tetikleyiciler) as $t) {
                    $t = trim($this->norm($t));
                    if (mb_strlen($t) < 2) continue;
                    if ($this->tetikUyar($n, $t) && mb_strlen($t) > $enSkor) { $enSkor = mb_strlen($t); $enIyi = $k; }
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

    protected function rastgele(array $a)
    {
        return $a[array_rand($a)];
    }

    /** AI ANLAMADI / TEKRAR ISTEGI: 50 varyasyonlu nazik "sizi yanlis anladim, tekrar soyleyin" (rastgele). */
    protected function anlamadimCevap()
    {
        return $this->cvp($this->rastgele([
            'Elbette efendim, sanırım sizi doğru anlayamadım. İsterseniz ne istediğinizi bir kez daha söyleyin, bu kez daha dikkatli dinleyeyim.',
            'Haklısınız efendim, sizi yanlış anlamış olabilirim. İsteğinizi tekrar söylerseniz birlikte yeniden değerlendirelim.',
            'Anladım efendim, önceki söylediğim cevap istediğiniz şeyle uyuşmamış olabilir. Ne istediğinizi tekrar anlatırsanız size daha doğru yardımcı olayım.',
            'Tabii efendim, sorun değil. İsteğinizi bir kez daha kendi cümlelerinizle anlatabilirsiniz, sizi dikkatle dinliyorum.',
            'Sanırım burada bir yanlış anlaşılma oldu efendim. Siz ne istediğinizi tekrar söyleyin, ben konuşmanın bu kısmını yeniden değerlendireyim.',
            'Elbette efendim. Galiba sizi istediğiniz şekilde anlayamadım. Bir kez daha anlatırsanız doğru şekilde yardımcı olmaya çalışacağım.',
            'Anladım efendim, demek istediğiniz farklıymış. Tekrar söyleyin lütfen, bu kez özellikle ne istediğinize odaklanayım.',
            'Özür dilerim efendim, sizi yanlış anlamışım. İsteğinizi tekrar belirtirseniz kaldığımız yerden devam edebiliriz.',
            'Tabii efendim, hiç sorun değil. Bir kez daha söyleyin, sizi yeniden dinliyorum.',
            'Sanırım sizi yanlış yorumladım efendim. Ne istediğinizi tekrar anlatırsanız doğru noktadan devam edelim.',
            'Haklısınız efendim, söylediğiniz şeyi farklı anlamış olabilirim. Tekrar açıklarsanız bu kez ona göre ilerleyelim.',
            'Elbette, baştan alabiliriz efendim. Ne istediğinizi tekrar söylemeniz yeterli, sizi dinliyorum.',
            'Tam olarak ne demek istediğinizi kaçırmış olabilirim efendim. Bir kez daha anlatırsanız size daha doğru yardımcı olayım.',
            'Anladım efendim. Önceki cevabımı dikkate almayalım. Siz isteğinizi yeniden söyleyin, oradan devam edelim.',
            'Sanırım konuşmanın bir kısmını yanlış anladım. Tekrar eder misiniz efendim? Bu kez daha dikkatli dinleyeceğim.',
            'Tabii efendim, tekrar anlatabilirsiniz. Özellikle istediğiniz şeyin ne olduğunu söylerseniz size daha doğru şekilde yardımcı olabilirim.',
            'Sizi doğru anlamamışım efendim. Sorun değil, yeniden başlayabiliriz. Ne yapmak istediğinizi tekrar söyleyin.',
            'Anladım efendim, farklı bir şey söylemek istemişsiniz. Tekrar ifade ederseniz neye ihtiyacınız olduğunu doğru şekilde anlayalım.',
            'Efendim, sanırım burada bir iletişim karışıklığı oldu. İsteğinizi tekrar söylerseniz hemen yeniden değerlendirelim.',
            'Tabii, sizi tekrar dinliyorum efendim. Ne söylemek istediğinizi baştan anlatabilirsiniz.',
            'Önceki cevabım aradığınız cevap değilmiş efendim. Bir kez daha anlatın, size uygun şekilde yardımcı olmaya çalışayım.',
            'Sanırım söylediğiniz ifadeyi doğru yorumlayamadım. İsterseniz daha kısa şekilde tekrar söyleyin, birlikte ilerleyelim.',
            'Elbette efendim. Yanlış anladıysam düzeltebilirsiniz. Ne istediğinizi tekrar belirtmeniz yeterli.',
            'Tam olarak neyi kastettiğinizi anlayamamış olabilirim. Biraz daha açık anlatırsanız size daha doğru yardımcı olabilirim.',
            'Anladım efendim, önceki cevabım doğru noktaya gitmemiş. İsteğinizi yeniden söyleyin, bu kez farklı şekilde değerlendirelim.',
            'Tabii efendim. Bir kez daha deneyelim. Siz ne istediğinizi söyleyin, ben dikkatlice takip edeyim.',
            'Galiba sizi farklı anladım efendim. Kusura bakmayın. Tekrar söylerseniz hemen doğru konuya geçebiliriz.',
            'Efendim, söylediklerinizde farklı bir şey kastettiğinizi anlıyorum. Bir kez daha anlatırsanız sizi doğru anlamaya çalışacağım.',
            'Sorun değil efendim, böyle durumlar olabilir. Siz isteğinizi yeniden söyleyin, birlikte çözelim.',
            'Sizi yanlış yönlendirmek istemem efendim. Bu nedenle ne istediğinizi tekrar söylerseniz doğru şekilde yardımcı olabilirim.',
            'Anladım efendim. Önceki söylediklerimi bir kenara bırakalım. Siz tekrar anlatın, konuşmayı oradan devam ettirelim.',
            'Elbette efendim, tekrar edebilirsiniz. Sizi dinliyorum ve bu kez söylediğiniz ayrıntılara özellikle dikkat edeceğim.',
            'Sanırım ne demek istediğinizi tam olarak yakalayamadım. Bir kez daha söyler misiniz efendim?',
            'Haklısınız efendim, cevap istediğiniz konuyla örtüşmemiş. Tekrar anlatırsanız doğru şekilde devam edelim.',
            'Tabii efendim, yeniden başlayabiliriz. Ne istediğinizi kendi cümlelerinizle anlatmanız yeterli.',
            'Söylediğinizi farklı yorumlamışım efendim. Tekrar söylerseniz bu kez asıl talebinize göre yardımcı olayım.',
            'Efendim, sizi yanlış anladıysam hemen düzeltebiliriz. Ne istediğinizi tekrar söyleyin, birlikte ilerleyelim.',
            'Anladım efendim. Demek ki önceki yorumum doğru değildi. Siz tekrar anlatın, ben yeniden değerlendireyim.',
            'Elbette efendim. Daha açık ifade etmek isterseniz sizi dinliyorum. İsterseniz kısa şekilde tekrar söylemeniz de yeterli.',
            'Bir yanlış anlaşılma olmuş gibi görünüyor efendim. İsteğinizi tekrar söyleyin, doğru şekilde yardımcı olmaya çalışayım.',
            'Tabii efendim, sorun değil. Bir kez daha deneyelim. Ne istediğinizi söyleyin, sizi takip ediyorum.',
            'Sanırım konuşmanın o kısmını yanlış yorumladım. Tekrar anlatırsanız kaldığımız yerden doğru şekilde devam edebiliriz.',
            'Efendim, sizi doğru anlayabilmem için bir kez daha anlatmanızı rica edeceğim. Sonrasında size uygun şekilde yardımcı olayım.',
            'Önceki cevabım sizi karşılamadıysa tekrar deneyelim efendim. Ne istediğinizi yeniden söyleyin, sizi dikkatle dinliyorum.',
            'Elbette efendim. Yanlış anlaşılmayı hemen düzeltebiliriz. Siz tekrar söyleyin, ben ona göre ilerleyeyim.',
            'Tam olarak neyi kastettiğinizi anlayamadım efendim. Biraz daha açıklarsanız size daha iyi yardımcı olabilirim.',
            'Anladım efendim, önceki söylediğim şey sizin talebiniz değilmiş. Baştan başlayalım, ne istediğinizi tekrar söyleyin.',
            'Tabii efendim. Sizi yanlış anlamış olabilirim. Tekrar anlatmanız yeterli, buradayım ve sizi dinliyorum.',
            'Sanırım bu konuşmada bir karışıklık yaşandı efendim. İsteğinizi tekrar belirtirseniz hemen doğru şekilde devam edelim.',
            'Elbette efendim, hiç sorun değil. Bir kez daha söyleyin, sizi dikkatlice dinleyeyim ve bu kez ne istediğinizi doğru anlamaya çalışayım.',
        ]));
    }

    /** SELAMLAMA: 50 farkli sicak karsilama (rastgele) — dijital garson edasi, konusmayi acik birakir. */
    protected function selamlamaCevap()
    {
        return $this->cvp($this->rastgele([
            'Merhaba, hoş geldiniz. Size yardımcı olmak için buradayım. Menü, yemekler veya restoranla ilgili merak ettiğiniz ne varsa sorabilirsiniz.',
            'Selam, hoş geldiniz. Buradayım ve sizi dinliyorum. İsterseniz menüye birlikte göz atabilir veya aklınızdaki herhangi bir şeyi sorabilirsiniz.',
            'Merhabalar, hoş geldiniz. Size yardımcı olmaktan memnuniyet duyarım. Ne hakkında bilgi almak istersiniz?',
            'İyi akşamlar, hoş geldiniz. Ben restoranımızın dijital asistanıyım. Menüden yemek seçmekten restoranla ilgili bilgi almaya kadar birçok konuda yardımcı olabilirim.',
            'İyi günler, hoş geldiniz. Buradayım, sizi dinliyorum. Nasıl yardımcı olabilirim?',
            'Günaydın, hoş geldiniz. Umarım güzel bir gün geçiriyorsunuzdur. Kahvaltıdan içeceklere kadar merak ettiğiniz her şeyi sorabilirsiniz.',
            'Kolay gelsin, hoş geldiniz. Ben buradayım, size yardımcı olabilirim. Dilerseniz menüden bir şeyler seçmenize de yardımcı olabilirim.',
            'Gayet iyiyim, teşekkür ederim. Sizi de dinliyorum. Yemekler, içecekler veya restoran hakkında ne öğrenmek isterseniz sorabilirsiniz.',
            'Teşekkür ederim, iyiyim. Buradayım ve size yardımcı olmaya hazırım. Nereden başlamak istersiniz?',
            'Buradayım, sizi duyuyorum. Merak ettiğiniz şeyi rahatça sorabilirsiniz. İsterseniz doğrudan menüden başlayabiliriz.',
            'Evet, buradayım. Size yardımcı olabilirim. Ne hakkında konuşmak veya bilgi almak istersiniz?',
            'Tabii ki, sizi dinliyorum. İsterseniz yemekler hakkında bilgi verebilir, isterseniz restoranla ilgili sorularınızı yanıtlayabilirim.',
            'Elbette, sorabilirsiniz. Size yardımcı olmak için buradayım. Aklınızdaki soruyu söylemeniz yeterli.',
            'Tabii, memnuniyetle yardımcı olurum. İsterseniz menüden seçim yaparken de size eşlik edebilirim. Ne aradığınızı söylemeniz yeterli.',
            'Hoş geldiniz. Buradayım ve sizi dinliyorum. İsterseniz sevdiğiniz yemek türünü söyleyin, menüden uygun seçeneklere birlikte bakalım.',
            'Merhaba, hoş geldiniz. Ben dijital restoran asistanınızım. Yemekler, içecekler, restoran olanakları ve daha birçok konuda yardımcı olabilirim.',
            'Selam, hoş geldiniz. Hazırım, sizi dinliyorum. Ne öğrenmek istiyorsanız doğrudan söyleyebilirsiniz.',
            'Merhabalar. Buradayım. İsterseniz menüdeki seçeneklere bakabilir, isterseniz restoranla ilgili bir konuda bilgi isteyebilirsiniz.',
            'Hoş geldiniz. Size yardımcı olabilirim. Kararsızsanız ne tarz bir yemek istediğinizi söyleyin, seçeneklere birlikte bakalım.',
            'Merhaba. Sizi dinliyorum. Yemek seçimi, içecekler veya restoranla ilgili herhangi bir konuda yardımcı olabilirim.',
            'Selam, hoş geldiniz. Buradayım ve hazırım. Aklınızda ne varsa sorabilirsiniz.',
            'İyi akşamlar. Hoş geldiniz. Güzel bir yemek seçmenize yardımcı olmamı isterseniz buradayım.',
            'Merhaba, hoş geldiniz. İsterseniz önce menüye göz atalım. Ya da doğrudan merak ettiğiniz şeyi bana sorabilirsiniz.',
            'Selamlar, hoş geldiniz. Ben buradayım. Size yemek seçimi konusunda yardımcı olabilir veya restoranla ilgili sorularınızı yanıtlayabilirim.',
            'Merhabalar, sizi dinliyorum. İsterseniz favori yemeklerinizi söyleyin, isterseniz menüdeki seçeneklerden birlikte ilerleyelim.',
            'Hoş geldiniz. Buradayım ve size yardımcı olmaya hazırım. Ne istediğinizi söylemeniz yeterli.',
            'Merhaba, hoş geldiniz. Eğer ne yiyeceğinize karar veremediyseniz, damak zevkinize göre seçim yapmanıza yardımcı olabilirim.',
            'Selam, hoş geldiniz. Burada olduğum sürece aklınıza takılanları sorabilirsiniz. Öncelikle neye bakmak istersiniz?',
            'İyi akşamlar, hoş geldiniz. Menüyle ilgili bir sorunuz varsa veya bir konuda öneri istiyorsanız sizi dinliyorum.',
            'Merhaba. Buradayım. İsterseniz yemeklerden başlayabiliriz, isterseniz restoranla ilgili başka bir konuda yardımcı olabilirim.',
            'Hoş geldiniz. Size yardımcı olmak için hazırım. Ne aradığınızı söylerseniz birlikte en uygun seçeneğe bakabiliriz.',
            'Merhabalar, hoş geldiniz. Ben buradayım ve sizi dinliyorum. İsterseniz menüdeki yemekler hakkında konuşabiliriz.',
            'Selam, hoş geldiniz. Yardımcı olmamı istediğiniz konuyu söyleyin, birlikte bakalım.',
            'Merhaba. Elbette yardımcı olabilirim. Yemek, içecek, restoran hizmetleri veya başka bir konuda sorunuz varsa sorabilirsiniz.',
            'İyi günler, hoş geldiniz. Buradayım. Size nasıl yardımcı olabileceğimi söylerseniz hemen başlayabiliriz.',
            'Merhaba, hoş geldiniz. Eğer ilk kez geliyorsanız menüdeki seçenekleri keşfetmenize de yardımcı olabilirim. Nereden başlamak istersiniz?',
            'Selam, hoş geldiniz. Hazırım. İsterseniz bugün ne yemek istediğinizi birlikte bulalım.',
            'Merhabalar. Sizi dinliyorum. Aklınızda belirli bir yemek varsa onun hakkında bilgi verebilirim.',
            'Hoş geldiniz. Buradayım ve yardımcı olmaya hazırım. İsterseniz doğrudan bir yemek adı söyleyin, size onunla ilgili bilgi vereyim.',
            'İyi akşamlar, hoş geldiniz. Menü konusunda kafanıza takılan bir şey varsa sorabilirsiniz. Elimden geldiğince yardımcı olurum.',
            'Merhaba. Buradayım. İsterseniz yemek seçimiyle başlayabiliriz veya restoran hakkında merak ettiğiniz başka bir şeyi konuşabiliriz.',
            'Selamlar, hoş geldiniz. Size eşlik etmeye hazırım. Ne aradığınızı söylerseniz menüden birlikte ilerleyebiliriz.',
            'Merhaba, hoş geldiniz. Bugün güzel bir seçim yapmanız için buradayım. Canınız ne tarz bir şey istiyor?',
            'Hoş geldiniz. Sizi dinliyorum. Hafif bir yemek, doyurucu bir ana yemek veya tatlı gibi belirli bir tercihiniz varsa söyleyebilirsiniz.',
            'Merhabalar, hoş geldiniz. İsterseniz bana nasıl bir yemek istediğinizi anlatın, size menüdeki uygun seçenekleri bulmanıza yardımcı olayım.',
            'Selam, hoş geldiniz. Buradayım. Sorunuzu veya isteğinizi doğal şekilde söyleyebilirsiniz, sizi anlamaya çalışacağım.',
            'İyi akşamlar. Hoş geldiniz. Yemek seçmekten restoranla ilgili bilgi almaya kadar birçok konuda yardımcı olabilirim. Nereden başlayalım?',
            'Merhaba, hoş geldiniz. Ben hazırım. Siz neye ihtiyacınız olduğunu söyleyin, birlikte bakalım.',
            'Selam, hoş geldiniz. Buradayım ve sizi dinliyorum. İsterseniz menüde gezmek yerine doğrudan bana ne istediğinizi söyleyebilirsiniz.',
            'Merhabalar, hoş geldiniz. Güzel bir yemek deneyimi geçirmeniz için buradayım. Ne merak ediyorsanız sorabilirsiniz, birlikte ilerleyelim.',
        ]));
    }

    /** ILETISIM/SES-MIKROFON PROBLEMI: 50 varyasyonlu nazik "sizi net alamadim, tekrar/yavas soyleyin"; ses sorununda yaziliya davet. */
    protected function iletisimSorunuCevap()
    {
        return $this->cvp($this->rastgele([
            'Efendim, sanırım sizi tam olarak anlayamadım. İsteğinizi bir kez daha söyleyebilir misiniz?',
            'Sesinizi aldım ama ne istediğinizi netleştiremedim. Bir kez daha anlatırsanız hemen yardımcı olayım.',
            'Sanırım arada bir şeyi kaçırdım. İsterseniz son söylediğinizi tekrar edebilirsiniz.',
            'Sizi yanlış anlamak istemiyorum. Ne istediğinizi bir kez daha söyleyebilir misiniz?',
            'Anladım, bir iletişim karışıklığı olmuş. Baştan tekrar ederseniz dikkatlice dinleyeyim.',
            'Sesiniz geliyor, ancak söylediğiniz kısmı net anlayamadım. Tekrar eder misiniz?',
            'Özür dilerim, bu kısmı kaçırdım. Tekrar söylerseniz hemen devam edelim.',
            'Sanırım sizi yanlış anladım. Ne demek istediğinizi yeniden söyleyin, ona göre ilerleyelim.',
            'Bir kısmını anlayamadım. Sadece son söylediğinizi tekrar etmeniz yeterli.',
            'Tabii, tekrar dinliyorum. İsteğinizi yeniden söyleyebilirsiniz.',
            'Anladım, iletişimde küçük bir karışıklık oldu. Tekrar anlatırsanız doğru şekilde yardımcı olayım.',
            'Söylediğiniz kısmı net alamadım. Biraz daha yavaş tekrar edebilir misiniz?',
            'Sanırım sesinizde bir kesilme oldu. Son söylediğinizi tekrar eder misiniz?',
            'Sizi duyuyorum fakat cümlenizin son kısmını anlayamadım. Tekrar söyleyebilir misiniz?',
            'Yanlış işlem yapmak istemiyorum. İsteğinizi bir kez daha netleştirir misiniz?',
            'Tam olarak ne istediğinizi kaçırdım. Tekrar söylerseniz hemen yardımcı olayım.',
            'Bir sorun yok, tekrar deneyebiliriz. Ne istediğinizi yeniden söylemeniz yeterli.',
            'Sizi anladığımdan emin olmak istiyorum. İsteğinizi bir kez daha ifade eder misiniz?',
            'Sanırım bu kez bağlantıda bir sorun oldu. Tekrar deneyelim.',
            'Sesinizi aldım ama bazı kelimeler anlaşılmadı. Bir kez daha söyleyebilir misiniz?',
            'Tamam, tekrar dinliyorum. Bu kez söylediğiniz isteği netleştirmeye çalışacağım.',
            'Az önceki isteğinizi doğru anlayamamış olabilirim. Yeniden söyleyin, ona göre devam edelim.',
            'Anladım efendim, tekrar edebilirsiniz. Sizi dinliyorum.',
            'Son söylediğiniz kısmı kaçırdım. Baştan anlatmanıza gerek yok, sadece son isteğinizi tekrar etmeniz yeterli.',
            'Sanırım ortam biraz gürültülü. Sesinizi biraz daha yakından veya net verirseniz daha iyi anlayabilirim.',
            'Mikrofonunuzdan gelen ses biraz kesiliyor olabilir. Bir kez daha deneyebiliriz.',
            'Sizi tam olarak duyamadım. İsterseniz daha kısa bir cümleyle tekrar söyleyin.',
            'Ne demek istediğinizi doğru anlamak için tekrar sormam gerekiyor. İsteğinizi yeniden söyleyebilir misiniz?',
            'Tamam, önceki söylediklerinizi dikkate alıyorum. Şimdi son isteğinizi tekrar alayım.',
            'Bir iletişim problemi yaşadık sanırım. Hiç sorun değil, tekrar deneyelim.',
            'Yanlış anladıysam düzeltin lütfen. Ne istediğinizi bir kez daha söyleyebilir misiniz?',
            'Söylediğiniz ifadeyi tam çözemedim. Biraz daha açık anlatabilir misiniz?',
            'Bunu doğru anlamak için bir kez daha duymam gerekiyor. Tekrar eder misiniz?',
            'Sesiniz geliyor ama kelimelerin bazıları net değil. İsterseniz biraz daha yavaş söyleyebilirsiniz.',
            'Sanırım konuşmanın bir bölümünü kaçırdım. Son söylediğinizi tekrar alabilir miyim?',
            'Tamam, sizi tekrar dinliyorum. Nereden devam etmek istediğinizi söyleyebilirsiniz.',
            'Önceki cevabım istediğiniz şey değilse sorun değil. Ne istediğinizi tekrar belirtin, hemen düzeltelim.',
            'Sizi yanlış yönlendirmek istemem. İsteğinizi tekrar söylerseniz doğru şekilde ilerleyelim.',
            'Bir kısmı anlaşılmadı. Baştan anlatmanıza gerek yok, takıldığımız kısmı tekrar etmeniz yeterli.',
            'Tamam, bu kez daha dikkatli dinliyorum. Tekrar söyleyebilirsiniz.',
            'Söylediğinizi tam olarak alamadım. İsterseniz kısa kısa söyleyebilirsiniz.',
            'Sesli iletişimde sorun yaşıyorsanız isterseniz yazılı olarak da devam edebiliriz.',
            'Bağlantı nedeniyle bazı kelimeleri kaçırmış olabilirim. Bir kez daha deneyelim.',
            'Ne istediğinizi yanlış anlamış olabilirim. Önceki isteği yok sayalım ve yeniden başlayalım.',
            'Tamam, önceki söylediğinizi tekrar etmiyorum. Yeni isteğinizi dinliyorum.',
            'Sizi anladığımdan emin olmak istiyorum. Söylediğinizi bir kez daha alabilir miyim?',
            'Sanırım ses biraz kesildi. Son cümlenizi tekrar ederseniz devam edebiliriz.',
            'Hiç sorun değil, tekrar söyleyebilirsiniz. Buradayım ve sizi dinliyorum.',
            'İletişimde bir karışıklık oldu. Yanlış bir işlem yapmamak için isteğinizi yeniden netleştirelim.',
            'Tamam, yeniden deneyelim. Ne istediğinizi söyleyin, bu kez doğru şekilde ilerleyelim.',
        ]));
    }

    /** ACIL DURUM tespiti (guclu, tek anlamli sinyaller — benign yardim/oneri ile karismaz). */
    protected function acilMi($c)
    {
        return $this->has($c, [
            'acil', 'imdat', 'yardim edin', 'cabuk yardim', 'cok acil', 'acil durum', 'hemen gelin', 'hemen birini cagir', 'hemen yardim',
            'yangin', 'duman', 'gaz kacagi', 'gaz kokusu', 'yanik kokusu', 'bir sey yaniyor', 'mutfakta yangin', 'patlama', 'patladi', 'ates cikti',
            'fenalastim', 'fenalasti', 'kotu hissediyorum', 'cok kotu oldum', 'basim donuyor', 'bayilacak', 'bayildi', 'bayildim',
            'nefes alamiyorum', 'nefesim kesiliyor', 'gogsum agriyor', 'kalbim agriyor', 'yere dusecegim', 'tansiyonum dustu', 'sekerim dustu', 'sekerim yukseldi',
            'kanama', 'kanamam var', 'yaralandi', 'yaralandim', 'doktora ihtiyac', 'ambulans', '112', 'bilinci kapali',
            'kavga', 'saldiriyor', 'tehdit ediyor', 'guvenlik cagir', 'polisi cagir', 'polis cagir', 'hirsizlik', 'calindi', 'guvende degil', 'rahatsiz ediyor',
            'cocugum kayboldu', 'cocuk kayboldu', 'cocugumu bulamiyorum', 'cocugum dustu', 'bebegim kotu', 'yasli fenalasti', 'birisi bayildi', 'birinin yardima ihtiyaci',
        ]);
    }

    /** ACIL DURUM: normal akisi birak, kisa/net yonlendirme + personele ACIL alarm (aksiyon). Hayati tehlikede 112. Sahte 'aradim' DEMEZ. */
    protected function acilDurumCevap()
    {
        $pool = [
            'Hemen restoran ekibimize haber veriyorum. Lütfen bulunduğunuz yerde kalın.',
            'Anladım, bu acil bir durum. Hemen bir garson arkadaşımızı yönlendiriyorum.',
            'Tamam, hemen yardım istiyorum. Lütfen bulunduğunuz masada kalın.',
            'Hemen ilgileniyoruz. Bir restoran çalışanımızın yanınıza gelmesini sağlıyorum.',
            'Acil durumunuzu anladım. Lütfen sakin kalın, restoran ekibimizden hemen yardım geliyor.',
            'Tamam, acil olarak ilgileniyoruz. Restoran ekibimize haber verdim.',
            'Bu acil görünüyor. Restoran ekibimize haber verdim; yakınınızdaki bir görevliye de seslenebilirsiniz.',
            'Lütfen sakin kalın. Acil durumunuz için restoran ekibimizden yardım geliyor.',
            'Bir sağlık sorunu varsa lütfen beklemeyin. Hayati tehlike varsa 112’yi arayın; ben de restoran ekibine acil haber verdim.',
            'Nefes almakta zorlanıyorsanız beklemeyin, hemen 112’yi arayın. Aynı anda restoran ekibimize acil haber verdim.',
            'Göğüs ağrısı veya ciddi nefes darlığı varsa vakit kaybetmeyin, 112’yi arayın. Restoran ekibimiz de yönlendirildi.',
            'Birisi bayıldıysa hemen 112’yi arayın ve çevrenizden yardım isteyin. Restoran ekibimize acil haber verdim.',
            'Yangın veya yoğun duman varsa güvenli şekilde uzaklaşın ve 112’yi arayın. Restoran ekibimizi de uyardım.',
            'Gaz kokusu alıyorsanız güvenli bir alana geçin, hemen restoran personeline haber verildi. Gerekirse 112’yi arayın.',
            'Bir güvenlik sorunu varsa müdahale etmeye çalışmayın, güvenli bir yere geçin. Restoran ekibimize acil haber verdim; gerekirse 112.',
            'Çocuğunuz kaybolduysa merak etmeyin, restoran ekibimize hemen haber verdim; bulunması için destek geliyor.',
            'Yanınızdaki kişi fenalaştıysa restoran ekibimize acil haber verdim. Durum ciddiyse lütfen 112’yi arayın.',
            'Kanama varsa restoran ekibimize haber verdim. Ciddi kanamada 112’yi arayın.',
            'Şu anda en önemli şey güvenliğiniz. Restoran ekibimize acil haber verdim, hemen geliyorlar.',
            'Ciddi bir durumsa benimle konuşmaya devam etmeyin; doğrudan 112’yi arayın. Restoran ekibimize de acil haber verdim.',
        ];
        return $this->cvp($this->rastgele($pool), ['aksiyon' => 'garson_cagir', 'tip' => 'acil']);
    }

    /** TEKNIK HATA / ISLEM SUPHESI: islemi TAMAMLANMIS SAYMAZ; garson teyidine yonlendirir (kritik guvenlik). */
    protected function teknikHataCevap()
    {
        return $this->cvp($this->rastgele([
            'Bir teknik aksaklık olmuş olabilir efendim. İşleminizin gerçekleşip gerçekleşmediğini varsaymadan, garson arkadaşımızdan hemen teyit ettirelim. Çağırayım mı?',
            'Özür dilerim, sistemde geçici bir sorun olabilir. Emin olmak için garson arkadaşımızı çağırayım; siparişinizi birlikte kontrol edelim.',
            'Bunu buradan doğrulayamıyorum efendim. Yanlış bilgi vermemek için garson arkadaşımız durumu hemen kontrol etsin, ister misiniz?',
            'Bir aksaklık yaşanmış olabilir. İşleminizi tamamlanmış saymak yerine garson arkadaşımızdan teyit almamız daha doğru olur. Çağırayım mı?',
            'Görünüşe göre bir sorun oldu efendim. Siparişinizin durumunu garson arkadaşımızla netleştirelim, sizi mağdur etmeyelim.',
        ]));
    }

    /** SISTEM/BILGI YOK: bilinmeyen/celiskili/israrla-istenen bilgiyi UYDURMAZ; 50 varyasyonlu "garson teyit" (rastgele). */
    protected function sistemBilgiYokCevap()
    {
        return $this->cvp($this->rastgele([
            'Efendim, bu bilgi şu anda bende görünmüyor. İsterseniz hemen garson arkadaşımızdan teyit edebiliriz.',
            'Bu konuda kesin bir bilgim yok, yanlış yönlendirmek istemem. İsterseniz garson arkadaşımızdan öğrenelim.',
            'Bu bilgi sistemimde kayıtlı görünmüyor. Size doğru bilgi verebilmek için garson arkadaşımızdan teyit edebiliriz.',
            'Şu anda bu bilgiyi doğrulayamıyorum. Tahminde bulunmak yerine garson arkadaşımızdan netleştirmemiz daha doğru olur.',
            'Bu konuda elimde doğrulanmış bir bilgi yok. İsterseniz hemen bir garson arkadaşımızı çağırabiliriz.',
            'Bu bilgiye buradan ulaşamıyorum. Yanlış bir şey söylemek istemem, dilerseniz garson arkadaşımızdan soralım.',
            'Sistemde bu bilgi görünmüyor. Doğru cevabı almak için restoran ekibimizden teyit edebiliriz.',
            'Bundan emin değilim ve sizi yanlış yönlendirmek istemem. Garson arkadaşımızdan kontrol ettirebiliriz.',
            'Bu konuda kesin cevap veremiyorum. İsterseniz sizin için garson arkadaşımızdan bilgi isteyelim.',
            'Şu anda elimde yeterli bilgi yok. Tahmin etmek yerine restoran ekibinden teyit edelim.',
            'Bu bilgi sistemimde bulunmuyor. Size yardımcı olması için garson arkadaşımızı çağırmamı ister misiniz?',
            'Buradaki bilgiler arasında bu detay yer almıyor. En doğrusu garson arkadaşımızdan teyit etmek olacaktır.',
            'Bu konuda güncel bilgiye erişemiyorum. İsterseniz garson arkadaşımızdan hemen kontrol ettirelim.',
            'Bu bilgiyi doğrulamadan cevap vermem doğru olmaz. Garson arkadaşımızdan teyit edebiliriz.',
            'Şu anda bunu kesin olarak söyleyemiyorum. İsterseniz restoran ekibinden öğrenelim.',
            'Bu detay bende görünmüyor. Yanlış bilgi vermek yerine garson arkadaşımızdan teyit edelim.',
            'Bu konuda sistemde yeterli bilgi yok. İsterseniz sizin için çalışan arkadaşlarımızdan birine soralım.',
            'Bunu tahmin ederek söylemek istemem. Doğru bilgi için garson arkadaşımızdan teyit alabiliriz.',
            'Bu bilgiye şu anda erişemiyorum. İsterseniz garson arkadaşımızı çağırıp öğrenelim.',
            'Buradan bu konuda kesin bilgi veremiyorum. Size doğru cevabı verebilmek için teyit etmemiz gerekiyor.',
            'Bu bilgi güncel olarak sistemde görünmüyor. Garson arkadaşımızdan kontrol ettirebiliriz.',
            'Sistemimde farklı bir bilgi görünüyor olsa bile fiziksel durumu buradan doğrulayamıyorum. Garson arkadaşımızdan teyit etmek daha doğru olur.',
            'Bu konuda elimde doğrulanmış bir veri yok. Yanlış yönlendirmemek için teyit edelim.',
            'Şu anda bu ürünün gerçekten mevcut olup olmadığını buradan doğrulayamıyorum. Garson arkadaşımızdan kontrol ettirebiliriz.',
            'Sistemde görünmesi, ürünün şu anda mutfakta mevcut olduğu anlamına gelmeyebilir. İsterseniz garson arkadaşımızdan teyit edelim.',
            'Bu konuda kesin konuşmam doğru olmaz. Güncel durumu restoran ekibimizden öğrenebiliriz.',
            'Bu bilgiyi şu anda doğrulayamıyorum. Dilerseniz garson arkadaşımızdan yardım isteyelim.',
            'Elimdeki bilgiler bu soruyu kesin olarak cevaplamaya yetmiyor. Garson arkadaşımızdan teyit edebiliriz.',
            'Bu detay sistemimde yer almıyor. İsterseniz hemen çalışan arkadaşlarımızdan bilgi alalım.',
            'Size yanlış bir bilgi vermek istemem. Bu nedenle tahmin etmek yerine teyit etmeyi tercih ederim.',
            'Bu konuda bilgim olmadığı için kesin bir cevap vermem doğru olmaz. Garson arkadaşımızdan öğrenebiliriz.',
            'Şu an bu bilginin güncel olup olmadığını kontrol edemiyorum. İsterseniz garson arkadaşımızdan teyit edelim.',
            'Bu bilgiye erişimim yok. Fakat isterseniz sizin için restoran ekibinden öğrenilmesini sağlayabiliriz.',
            'Sistem tarafında bu bilgi bulunmuyor. Doğru cevabı almak için garson arkadaşımızdan destek alabiliriz.',
            'Bu konuda tahminde bulunmayayım. Size net bilgi verebilecek bir arkadaşımızdan teyit edelim.',
            'Bunu kesin olarak söyleyebilmem için güncel bilgiye ihtiyacım var. Şu anda bunu doğrulayamıyorum.',
            'Bu konuda sistemde yeterli veri bulunmuyor. İsterseniz garson arkadaşımızdan yardım isteyelim.',
            'Şu an elimde bu soruyu doğrulayacak bir bilgi yok. Yanlış yönlendirmemek adına teyit edelim.',
            'Bu bilgiyi buradan göremiyorum. Garson arkadaşımızdan öğrenmek ister misiniz?',
            'Bunu bilmediğim halde biliyormuş gibi cevap vermek istemem. En doğrusu restoran ekibinden teyit etmek.',
            'Bu konuda kesin bilgi veremiyorum. İsterseniz hemen bir garson arkadaşımızdan destek alalım.',
            'Sistemde bu bilgiye rastlamadım. Güncel durumu garson arkadaşımızdan öğrenebiliriz.',
            'Bu detay benim erişebildiğim bilgiler arasında yok. İsterseniz restoran ekibine soralım.',
            'Bu bilgi için size kesin bir cevap vermek isterdim ancak şu anda doğrulayamıyorum. Garson arkadaşımızdan teyit edebiliriz.',
            'Buradan bu bilgiyi kontrol edemiyorum. Yanlış yönlendirmemek için garson arkadaşımızdan öğrenelim.',
            'Bu konuda emin değilim. Emin olmadığım bir bilgiyi kesinmiş gibi söylemek yerine teyit etmeyi tercih ederim.',
            'Sistemimde bu bilgi bulunmadığı için net cevap veremiyorum. İsterseniz çalışan arkadaşlarımızdan birine soralım.',
            'Bu bilgi anlık olarak değişebileceği için buradan kesinleştiremiyorum. Garson arkadaşımızdan güncel durumu öğrenebiliriz.',
            'Şu anda bu konuda doğrulanmış bir bilgiye sahip değilim. Dilerseniz garson arkadaşımızı çağırıp netleştirelim.',
            'Bu bilgiyi buradan doğrulayamıyorum efendim. Yanlış yönlendirmemek için isterseniz hemen garson arkadaşımızdan teyit edelim.',
        ]));
    }

    /** KARARSIZ MUSTERI: uzun liste DOKMEDEN once tercih sorar (et/tavuk, hafif/doyurucu, acili...). 50 varyasyon. */
    protected function kararsizCevap()
    {
        return $this->cvp($this->rastgele([
            'Tabii, birlikte karar verelim. Önce hafif bir şey mi yoksa doyurucu bir yemek mi istediğinizi söyleyin.',
            'Hiç sorun değil, menüde çok seçenek olunca karar vermek zor olabiliyor. Size birkaç uygun seçenek arasından yardımcı olabilirim.',
            'Karar vermenize yardımcı olayım. Et, tavuk, balık ya da başka bir şey mi düşünüyorsunuz?',
            'Elbette, sizin için seçenekleri biraz daraltabiliriz. Daha çok hafif bir yemek mi arıyorsunuz, yoksa iyice doyurucu bir şey mi?',
            'Hiç acele etmeyin. Nasıl bir şey istediğinizi biraz anlatın, size uygun seçenekleri bulalım.',
            'Bazen menüde seçenek çok olunca seçim yapmak zorlaşıyor. İsterseniz damak zevkinize göre birkaç alternatif çıkaralım.',
            'Ben yardımcı olayım. Acılı mı, acısız mı tercih edersiniz?',
            'Kararsız kaldıysanız birkaç soru sorayım, cevabınıza göre seçenekleri azaltalım.',
            'Tabii, beraber seçebiliriz. Şu anda canınız daha çok et mi, tavuk mu, yoksa farklı bir şey mi çekiyor?',
            'Sorun değil. Önce nasıl bir yemek istediğinizi belirleyelim, sonrası daha kolay.',
            'İki yemek arasında kaldıysanız ikisini de söyleyin, aralarındaki farkları anlatayım.',
            'Seçmekte zorlanıyorsanız size uygun birkaç alternatif önerebilirim. Önceliğiniz lezzet mi, hafiflik mi, doyuruculuk mu?',
            'İsterseniz seçimi bana bırakabilirsiniz. Menüdeki seçenekler arasından size uygun bir tane bulalım.',
            'Tabii, yardımcı olabilirim. Çok aç mısınız, yoksa daha hafif bir şey mi düşünüyorsunuz?',
            'Menüde kaybolmanıza gerek yok. Birkaç tercih sorayım, seçenekleri sizin için daraltayım.',
            'Ne istediğinizi tam olarak bilmiyorsanız sorun değil. Önce sevdiğiniz yemek türünü bulalım.',
            'Bana biraz nasıl bir şey aradığınızı anlatın, size uygun seçenekleri birlikte değerlendirelim.',
            'Karar vermek zor geliyorsa seçimi adım adım yapabiliriz. Önce ana yemek türünden başlayalım.',
            'İsterseniz size birkaç seçenek sunayım, aralarından hangisi daha çok hoşunuza giderse onunla devam ederiz.',
            'Bugün özel olarak canınızın çektiği bir şey var mı? Ona göre seçim yapabiliriz.',
            'İlk defa geliyorsanız karar vermeniz daha da zor olabilir. Menüdeki seçeneklere göre size uygun birkaç öneri sunabilirim.',
            'Siz seçmekte zorlanıyorsanız ben seçenekleri azaltayım. Önce etli mi yoksa etsiz mi istediğinizi söyleyin.',
            'Hepsi güzel görünüyorsa birkaç kriter belirleyelim. Böylece karar vermek çok daha kolay olur.',
            'İsterseniz damak zevkinize göre seçim yapalım. Acı seviyor musunuz?',
            'Ne yiyeceğinize karar veremediyseniz hiç sorun değil. Size birkaç alternatif çıkaralım.',
            'Ben size yardımcı olayım. Hafif, orta veya doyurucu bir şeylerden hangisine yakınsınız?',
            'Seçimi bana bırakmak isterseniz, menüdeki uygun seçenekler arasından bir öneride bulunabilirim.',
            'İki seçenek arasında kaldıysanız isimlerini söyleyin, hangisinin size daha uygun olduğunu birlikte değerlendirelim.',
            'Canınız belirli bir şey çekmiyorsa sorun değil. Nasıl bir yemek istediğinizi birkaç soruyla bulabiliriz.',
            'Biraz seçenekleri daraltalım. Ana yemek mi düşünüyorsunuz, yoksa atıştırmalık gibi daha hafif bir şey mi?',
            'Kararsız kalmanız çok normal. Önce doyurucu mu hafif mi istediğinizi belirleyelim.',
            'İsterseniz size üç farklı seçenek üzerinden yardımcı olayım. Sonrasında seçmek daha kolay olur.',
            'Ne yiyeceğinizi bilmiyorsanız bana sevdiğiniz bir iki malzeme söyleyin, ona göre seçenekleri değerlendirelim.',
            'Bugün farklı bir şey denemek mi istiyorsunuz, yoksa garanti bir seçim mi yapmak istersiniz?',
            'Size uygun bir seçim bulabiliriz. Öncelikle et, tavuk, balık veya sebze seçeneklerinden hangisine yakın olduğunuzu söyleyin.',
            'Kararı hemen vermek zorunda değilsiniz. İsterseniz önce birkaç seçeneği karşılaştıralım.',
            'Menüde çok seçenek olması bazen işi zorlaştırıyor. Ben sizin için uygun olanları öne çıkarayım.',
            'İsterseniz en çok merak ettiğiniz iki veya üç yemeği söyleyin, aralarındaki farkları anlatayım.',
            'Siz sadece nasıl bir tat istediğinizi söyleyin, gerisini birlikte bulalım.',
            'Ne yiyeceğinize karar veremediyseniz size yardımcı olmaktan memnuniyet duyarım. Önce hafif mi doyurucu mu istediğinizi belirleyelim.',
            'Bana birkaç ipucu verirseniz doğru seçeneği bulmanız çok daha kolay olur. Acı, baharatlı veya sade bir şey mi düşünüyorsunuz?',
            'Kararı bana bırakabilirsiniz. Menüdeki seçenekler arasından size uygun bir öneri yapayım.',
            'Eğer iki yemek arasında kaldıysanız ikisini de değerlendirebiliriz. Hangisinin daha doyurucu veya hafif olduğunu karşılaştırabilirim.',
            'İsterseniz damak zevkinize göre ilerleyelim. Sevmediğiniz bir malzeme var mı?',
            'Bugün klasik bir şey mi istersiniz, yoksa farklı bir lezzet denemeye açık mısınız?',
            'Hiçbir fikriniz yoksa da sorun değil. Birkaç kısa soruyla sizin için uygun seçenekleri bulabiliriz.',
            'Seçimi kolaylaştıralım. Bana ne istemediğinizi bile söyleseniz yeter, kalan seçeneklere bakabiliriz.',
            'İsterseniz menüdeki seçenekleri tek tek değerlendirmek yerine size en uygun birkaç seçeneği belirleyelim.',
            'Siz karar veremiyorsanız ben yardımcı olayım. Önce bugün ne kadar aç olduğunuzu söyleyin.',
            'Tamam, seçimi birlikte yapalım. Bana nasıl bir yemek istediğinizi biraz anlatın, menüdeki uygun seçenekleri sizin için değerlendireyim.',
        ]));
    }

    /** WIFI: subeler.wifi_sifre tanimliysa GERCEK sifreyi soyler; yoksa 50 varyasyonlu nazik yonlendirme (uydurmaz). */
    protected function wifiBilgisi()
    {
        try {
            if (Schema::hasColumn('subeler', 'wifi_sifre')) {
                $w = DB::table('subeler')->where('id', $this->subeId)->value('wifi_sifre');
                if ($w !== null && trim((string) $w) !== '') {
                    $wad = Schema::hasColumn('subeler', 'wifi_ad') ? trim((string) DB::table('subeler')->where('id', $this->subeId)->value('wifi_ad')) : '';
                    return $this->cvp('Tabii efendim, ' . ($wad !== '' ? ('Wi-Fi ağımız ' . $wad . ', şifremiz ') : 'Wi-Fi şifremiz ') . trim((string) $w) . '. Afiyet olsun!');
                }
            }
        } catch (\Throwable $e) {}
        return $this->cvp($this->rastgele([
            'Efendim, Wi-Fi ile ilgili bilgiyi buradan doğrulayamıyorum. İsterseniz size yardımcı olması için hemen garson arkadaşımızı çağırayım mı?',
            'Tabii efendim. Wi-Fi konusunda size net bilgi verebilmem için garson arkadaşımızdan destek alabiliriz. Hemen çağırmamı ister misiniz?',
            'Efendim, internet bağlantısı ve şifre bilgisi şu anda bende görünmüyor. Dilerseniz hemen bir garson arkadaşımızı masanıza yönlendirebilirim.',
            'Wi-Fi konusunda size yardımcı olabilirim ancak bağlantı veya şifre bilgisine buradan erişemiyorum. İsterseniz garson arkadaşımızı hemen çağırayım.',
            'Elbette efendim. Wi-Fi ile ilgili güncel bilgiyi restoran ekibimizden öğrenebilirsiniz. İsterseniz sizin için hemen bir garson çağırabilirim.',
            'Efendim, bu konuda yanlış bilgi vermek istemem. Wi-Fi bilgisi bende bulunmadığı için isterseniz hemen garson arkadaşımızdan öğrenmenizi sağlayabilirim.',
            'İnternet bağlantısıyla ilgili bilgi şu anda benim tarafımda mevcut değil efendim. Size yardımcı olması için bir garson arkadaşımızı çağırayım mı?',
            'Tabii efendim, Wi-Fi hakkında yardımcı olmak isterim. Ancak şifre veya bağlantı bilgisi bende kayıtlı değil. İsterseniz hemen görevli arkadaşımızı çağırabilirim.',
            'Efendim, Wi-Fi şifresini buradan göremiyorum. İsterseniz vakit kaybetmeden garson arkadaşımızı masanıza çağırayım, kendisinden öğrenebilirsiniz.',
            'İnternet konusunda size doğru bilgi vermek isterim efendim. Fakat bu bilgiye şu anda erişemiyorum. Dilerseniz hemen garson arkadaşımızdan destek isteyebilirim.',
            'Wi-Fi bağlantısıyla ilgili bilgiyi buradan teyit edemiyorum efendim. İsterseniz sizin için bir garson çağırayım, gerekli bilgiyi kendisinden alabilirsiniz.',
            'Efendim, internet hizmetiyle ilgili ayrıntılar bende görünmüyor. Size yardımcı olması için garson arkadaşımızı çağırmamı ister misiniz?',
            'Tabii efendim. Wi-Fi şifresi konusunda sizi bekletmeyelim. İsterseniz hemen bir garson arkadaşımızı masanıza yönlendirebilirim.',
            'Efendim, Wi-Fi ile ilgili kesin bir bilgiye sahip değilim. Yanlış yönlendirmek yerine isterseniz hemen garson arkadaşımızdan bilgi almanızı sağlayayım.',
            'İnternete bağlanma konusunda yardımcı olmak isterim efendim. Ancak ağ ve şifre bilgileri benim ekranımda bulunmuyor. İsterseniz bir arkadaşımızı çağırabilirim.',
            'Wi-Fi hakkında bilgi almak istediğinizi anladım efendim. Bu bilgi bende olmadığı için isterseniz hemen garson arkadaşımızdan destek alabiliriz.',
            'Efendim, Wi-Fi şifresini size buradan söyleyemiyorum çünkü bu bilgi sistemimde bulunmuyor. Dilerseniz hemen garson arkadaşımızı çağırayım.',
            'İnternet bağlantısıyla ilgili detayları restoran ekibimizden öğrenebilirsiniz efendim. İsterseniz sizin için hemen bir garson çağırabilirim.',
            'Elbette efendim. Wi-Fi konusunda sizi doğru kişiye yönlendirebilirim. Hemen garson arkadaşımızı çağırmamı ister misiniz?',
            'Efendim, bu konuda bende yeterli bilgi bulunmuyor. İsterseniz garson arkadaşımızı çağırayım, size Wi-Fi konusunda yardımcı olsun.',
            'Wi-Fi kullanımıyla ilgili bilgiyi buradan doğrulayamıyorum efendim. Dilerseniz masanıza bir garson arkadaşımızı yönlendirebilirim.',
            'İnternet şifresini öğrenmek istiyorsanız efendim, bu konuda size ekip arkadaşlarımız yardımcı olabilir. İsterseniz hemen bir garson çağırayım.',
            'Efendim, Wi-Fi ağıyla ilgili bilgiler bana tanımlı değil. İsterseniz hemen garson arkadaşımızı çağırabilir ve bağlantı konusunda yardım alabilirsiniz.',
            'Tabii efendim. Wi-Fi konusunda sizi doğru şekilde yönlendirmek için bir garson arkadaşımızdan destek alabiliriz. Çağırmamı ister misiniz?',
            'Efendim, internetle ilgili bilgiyi şu anda sistemimden göremiyorum. Size yardımcı olması için hemen bir garson arkadaşımızı çağırabilirim.',
            'Wi-Fi konusunda size kesin bilgi vermem doğru olmaz efendim. Dilerseniz hemen restoran ekibimizden bir arkadaşımızı çağırayım.',
            'Efendim, Wi-Fi bağlantısı ve şifresiyle ilgili detaylara erişimim yok. İsterseniz garson arkadaşımızı çağırıp bu konuda yardım almanızı sağlayabilirim.',
            'İnternet kullanımıyla ilgili bir sorunuz olduğunu anladım efendim. Bu konuda en doğru bilgiyi ekip arkadaşımız verebilir. İsterseniz hemen çağırayım.',
            'Wi-Fi konusunda yardımcı olmak isterim efendim fakat elimde güncel bağlantı bilgisi bulunmuyor. İsterseniz bir garson arkadaşımızı masanıza çağırabilirim.',
            'Efendim, Wi-Fi şifresi konusunda sizi yanlış yönlendirmek istemem. Bu bilgi bende olmadığı için isterseniz hemen garson arkadaşımızdan öğrenebilirsiniz.',
            'İnternete bağlanmakla ilgili yardım gerekiyorsa efendim, size bir arkadaşımız yardımcı olabilir. İsterseniz hemen garson çağırayım.',
            'Tabii efendim, Wi-Fi ile ilgili talebinizi anladım. Ancak bağlantı bilgileri benim tarafımda görünmüyor. İsterseniz hemen garson arkadaşımızı yönlendirebilirim.',
            'Efendim, Wi-Fi bilgisi restoran ekibimizin kontrolünde olabilir. İsterseniz sizi bekletmeden bir garson arkadaşımızı çağırayım.',
            'İnternet bağlantısıyla ilgili ayrıntıyı buradan göremiyorum efendim. Dilerseniz hemen garson arkadaşımızdan yardım isteyebiliriz.',
            'Wi-Fi şifresini soruyorsanız efendim, bu bilgi bende mevcut değil. İsterseniz hemen bir garson arkadaşımızı masanıza çağırabilirim.',
            'Efendim, bu konuda size en doğru bilgiyi garson arkadaşımız verebilir. İsterseniz hemen çağırayım ve yardımcı olsun.',
            'Wi-Fi konusunda yardımcı olmaya hazırım efendim. Fakat bağlantı bilgileri sistemimde olmadığı için isterseniz bir garson arkadaşımızdan destek alalım.',
            'İnternet var mı veya nasıl bağlanabileceğiniz konusunda bilgi almak istiyorsanız efendim, sizi hemen ekip arkadaşımıza yönlendirebilirim. Garson çağırmamı ister misiniz?',
            'Efendim, Wi-Fi ile ilgili bilgiyi buradan kontrol edemiyorum. Yanlış bilgi vermek yerine isterseniz hemen garson arkadaşımızı çağırayım.',
            'Elbette efendim. Wi-Fi konusunda size yardımcı olması için restoran ekibimizden bir arkadaşımızı çağırabilirim. Uygunsa hemen yönlendireyim.',
            'İnternet bağlantısı konusunda desteğe ihtiyacınız olduğunu anladım efendim. Bu konuda garson arkadaşımız size yardımcı olabilir. Çağırmamı ister misiniz?',
            'Efendim, Wi-Fi bilgileri benim erişimimde olmadığı için buradan net bir cevap veremiyorum. İsterseniz hemen garson arkadaşımızı çağırabiliriz.',
            'Wi-Fi şifresini öğrenmek için efendim, en hızlı şekilde garson arkadaşımızdan yardım alabilirsiniz. İsterseniz ben sizin için çağırayım.',
            'Tabii efendim. Bu konuyu restoran ekibimizle netleştirebiliriz. İsterseniz hemen bir garson arkadaşımızı masanıza yönlendireyim.',
            'Efendim, Wi-Fi konusunda size yardımcı olmak isterim ancak gerekli bilgi sistemimde bulunmuyor. Dilerseniz hemen garson arkadaşımızı çağırabilirim.',
            'İnternet bağlantısıyla ilgili bilgi bende olmadığı için efendim, sizi doğru kişiye yönlendirmek isterim. Garson arkadaşımızı çağırmamı ister misiniz?',
            'Wi-Fi kullanımı veya şifresiyle ilgili sorunuz varsa efendim, bu konuda ekip arkadaşımız size yardımcı olabilir. İsterseniz hemen çağırayım.',
            'Efendim, Wi-Fi bilgisine buradan ulaşamıyorum. Sizi uğraştırmadan bir garson arkadaşımızı çağırabilir ve gerekli bilgiyi kendisinden öğrenebilirsiniz.',
            'Elbette efendim. İnternet konusunda size yardımcı olması için bir garson arkadaşımızı çağırabilirim. İsterseniz hemen masanıza yönlendireyim.',
            'Efendim, Wi-Fi ile ilgili bilgiyi şu anda sistemimde göremiyorum. Yanlış bir bilgi vermek yerine size yardımcı olacak bir garson arkadaşımızı hemen çağırabilirim.',
        ]));
    }

    /** KAMPANYA/INDIRIM: aktif indirim kurallarini (GERCEK veri) musteriye sicak dille anlatir. */
    protected function kampanyaBilgisi()
    {
        try {
            if (!Schema::hasTable('indirimler')) {
                return $this->cvp('Şu anda aktif bir kampanyamız görünmüyor ama sormanız çok güzel! İsterseniz en beğenilen lezzetlerimizden önereyim.');
            }
            $bugun = now();
            $g = ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
            $satir = [];
            foreach (DB::table('indirimler')->where('sube_id', $this->subeId)->where('aktif', 1)->get() as $k) {
                if ($k->bitis && $bugun->gt(\Carbon\Carbon::parse($k->bitis)->endOfDay())) continue;
                if ($k->baslangic && $bugun->lt(\Carbon\Carbon::parse($k->baslangic)->startOfDay())) continue;
                $dv = (float) $k->deger;
                $d = $k->deger_tipi === 'yuzde' ? ('yüzde ' . $dv) : ($dv . ' lira');
                switch ($k->tip) {
                    case 'online_odeme': $satir[] = "kartla online ödemede $d indirim"; break;
                    case 'kupon': $satir[] = $k->kupon_kodu ? (strtoupper($k->kupon_kodu) . " kuponuyla $d indirim") : "$d kupon indirimi"; break;
                    case 'tutar_ustu': $satir[] = ($k->min_tutar ? ((float) $k->min_tutar . ' lira üstü siparişte ') : '') . "$d indirim"; break;
                    case 'happy_hour': $satir[] = ($k->saat_bas && $k->saat_bit ? "{$k->saat_bas} ile {$k->saat_bit} arası " : '') . "$d indirim"; break;
                    case 'gun':
                        $gunler = $k->gun_maskesi ? implode(', ', array_filter(array_map(fn ($n) => $g[(int) trim($n)] ?? '', explode(',', $k->gun_maskesi)))) : '';
                        $satir[] = ($gunler ? "$gunler günleri " : '') . "$d indirim";
                        break;
                    case 'urun': $satir[] = "seçili ürünlerde $d indirim"; break;
                    case 'dogum_gunu': $satir[] = "doğum gününüzde $d indirim"; break;
                    case 'ilk_siparis': $satir[] = "ilk siparişinizde $d indirim"; break;
                    // 'uygulama' QR-web'de pasif -> anlatma
                }
            }
            if (empty($satir)) {
                return $this->cvp('Şu anda aktif bir kampanyamız görünmüyor ama sormanız çok güzel! İsterseniz en beğenilen lezzetlerimizden önereyim.');
            }
            return $this->cvp('Tabii, güncel fırsatlarımız şöyle: ' . implode('; ', $satir) . '. Afiyet olsun!');
        } catch (\Throwable $e) {
            return $this->cvp('Kampanyalarımızı garsonumuz size en doğru şekilde anlatsın, çağırayım mı?');
        }
    }

    protected function oneri($c = '')
    {
        $ac = $this->has($c, ['karnim', 'acim', 'aciktim', 'aclik', 'doyur', 'doyurucu', 'cok yemek', 'agir bir', 'karin']); // aclik niyeti
        $anaKok = ['ana yemek', 'izgara', 'kebap', 'pizza', 'burger', 'makarna', 'pide', 'doner', 'kofte', 'tavuk', 'et', 'balik', 'durum', 'lahmacun', 'pilav', 'guvec', 'wrap', 'sandvic', 'tost'];
        $isAna = function ($kat) use ($anaKok) {
            $k = $this->norm($kat);
            if ($k === '') return false;
            foreach ($anaKok as $x) { $xx = $this->norm($x); if ($xx !== '' && strpos(' ' . $k . ' ', ' ' . $xx) !== false) return true; }
            return false;
        };

        $one = $this->oneCikanKolonVar();
        $sel = ['urunler.id', 'urunler.ad', 'urunler.fiyat', 'urunler.aciklama', 'menu_kategorileri.ad as kat'];
        if ($one) { $sel[] = 'urunler.one_cikan'; $sel[] = 'urunler.one_soz'; $sel[] = 'urunler.one_ai_soz'; $sel[] = 'urunler.one_sira'; }
        $rows = DB::table('urunler')->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->where('urunler.aktif', 1)->where('urunler.tukendi', 0)
            ->select($sel)->get();

        // ICECEK bir yemek onerisi olamaz -> ele (ad + kategoriden)
        $yemekler = $rows->reject(fn ($u) => $this->icecekMi($u->ad, $u->kat))->values();
        if ($yemekler->isEmpty()) $yemekler = $rows;

        // 1) ONCELIK: isletmenin ISARETLEDIGI one cikan yemekler (one_sira'ya gore)
        $secili = collect();
        if ($one) {
            $secili = $yemekler->filter(fn ($u) => !empty($u->one_cikan))->sortBy(fn ($u) => (int) $u->one_sira)->values();
            if ($ac) $secili = $secili->sortByDesc(fn ($u) => $isAna($u->kat) ? 1 : 0)->values();
        }
        // 2) YOKSA: populer (son 30 gun) yemekler + ana yemek onceligi
        if ($secili->isEmpty()) {
            $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', now()->subDays(30))->where('adisyon_kalemleri.durum', '!=', 'iptal')
                ->select('urun_adi', DB::raw('SUM(adet) as adet'))->groupBy('urun_adi')->orderByDesc('adet')->limit(40)->pluck('urun_adi')->all();
            $sira = [];
            foreach ($top as $ix => $ad) $sira[$this->norm($ad)] = $ix;
            $secili = $yemekler->sortByDesc(function ($u) use ($sira, $isAna, $ac) {
                $p = 0; $n = $this->norm($u->ad);
                if (isset($sira[$n])) $p += (100 - $sira[$n]);
                if ($isAna($u->kat)) $p += $ac ? 70 : 20;
                return $p;
            })->values();
        }
        $secili = $secili->take(4)->values();

        $vetiket = ['Şefin Önerisi', 'Çok seviliyor', 'Misafir favorisi', 'Doyurucu'];
        $kartlar = [];
        foreach ($secili as $i => $u) {
            $et = ($one && !empty($u->one_cikan)) ? 'Şefin Önerisi' : ($vetiket[$i] ?? 'Öneri');
            $kartlar[] = $this->kart($u->ad, $u->fiyat, $u->aciklama, $u->kat ?? null, $et, [], $u->id);
        }
        $bas = ($secili->first()->ad ?? 'Köfte');
        // Istah kabartici cumle: AI'in urettigi one_ai_soz oncelikli, yoksa ham not (one_soz)
        $ilk = $secili->first();
        $soz = ($one && $ilk) ? trim((string) (($ilk->one_ai_soz ?? '') ?: ($ilk->one_soz ?? ''))) : '';
        if ($soz !== '') $mesaj = $soz . ' Aşağıdaki lezzetlere göz atabilirsiniz. 😊';
        elseif ($ac) $mesaj = "Karnınız açsa doyurucu gider; özellikle $bas gönül rahatlığıyla tavsiye ederim. Aşağıdakilere göz atabilirsiniz. 😊";
        else $mesaj = "Size özenle seçtiğimiz birkaç lezzeti önereyim; özellikle $bas çok beğeniliyor. Aşağıdakilere göz atabilirsiniz. 😊";
        return $this->cvp($mesaj, ['tip' => 'oneri', 'baslik' => $ac ? '🍽️ Doyurucu Öneriler' : '🤖 Bugünün Önerileri', 'kartlar' => $kartlar]);
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

    // ============== ASISTAN EGITIMI: cozulmeyen soru kaydi + PDF'ten kalip cikarma ==============
    protected function cozulmeyenEnsure()
    {
        if (Schema::hasTable('asistan_cozulmeyen')) return;
        Schema::create('asistan_cozulmeyen', function ($t) {
            $t->increments('id');
            $t->string('soru_norm', 191)->unique();
            $t->text('ham')->nullable();
            $t->unsignedInteger('adet')->default(1);
            $t->timestamp('son_tarih')->nullable();
            $t->timestamps();
        });
    }

    /** Kural+kalip bulamayinca soruyu (adet sayacli) kaydet -> panelde en cok sorulan gorunur. */
    public function cozulmeyenKaydet($soru)
    {
        try {
            $ham = trim((string) $soru);
            if (mb_strlen($ham) < 2) return;
            $norm = mb_substr(trim($this->normalize($ham)), 0, 191);
            if ($norm === '') return;
            $this->cozulmeyenEnsure();
            $row = DB::table('asistan_cozulmeyen')->where('soru_norm', $norm)->first();
            if ($row) {
                DB::table('asistan_cozulmeyen')->where('id', $row->id)->update(['adet' => $row->adet + 1, 'son_tarih' => now(), 'ham' => $ham, 'updated_at' => now()]);
            } else {
                DB::table('asistan_cozulmeyen')->insert(['soru_norm' => $norm, 'ham' => $ham, 'adet' => 1, 'son_tarih' => now(), 'created_at' => now(), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {}
    }

    /** PDF (base64) -> Claude belgeyi okur -> SORU-CEVAP kaliplari (JSON). Doner: [ok(bool), veri(dizi|hata)]. */
    public function pdftenKalipCikar($base64Pdf, $mediaType = 'application/pdf')
    {
        $anahtar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY'));
        if ($anahtar === '') return [false, 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        $sistem = 'Sen bir RESTORAN icin musteri asistanina SORU-CEVAP kalibi cikaran yardimcisin. Verilen belgeden (menu, SSS, kurumsal bilgi) musterilerin soracagi olasi sorulari ve KISA cevaplari cikar. '
            . 'KURALLAR: 1) SADECE gecerli JSON DIZI dondur, baska hicbir metin yazma. '
            . '2) Her oge tam olarak: {"tetikleyiciler":"...","cevap":"...","kategori":"..."} . '
            . '3) tetikleyiciler CUMLE DEGIL, virgulle ayrilmis KISA anahtar kelime kaliplari olsun (2-5 tane, es anlamli). Ornek: "wifi sifresi, internet sifresi, kablosuz". '
            . '4) cevap kisa/net, musteriye uygun (1-2 cumle). 5) Belgede OLMAYAN bilgi UYDURMA. En fazla 40 oge.';
        $govde = [
            'model' => (string) (config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001'),
            'max_tokens' => 4000, 'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => [
                ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64Pdf]],
                ['type' => 'text', 'text' => 'Bu belgeden soru-cevap kaliplarini cikar ve SADECE JSON dizi dondur.'],
            ]]],
        ];
        $data = $this->cagirAnthropic($anahtar, $govde);
        if (!$data || empty($data['content'])) return [false, 'AI yanıt vermedi (anahtar/bakiye kontrol edin).'];
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if (preg_match('/\[.*\]/s', $t, $m)) $t = $m[0];
        $arr = json_decode($t, true);
        if (!is_array($arr)) return [false, 'AI çıktısı JSON olarak çözümlenemedi.'];
        $out = [];
        foreach ($arr as $o) {
            if (!is_array($o)) continue;
            $tet = trim((string) ($o['tetikleyiciler'] ?? ''));
            $cev = trim((string) ($o['cevap'] ?? ''));
            if ($tet === '' || $cev === '') continue;
            $out[] = ['tetikleyiciler' => mb_substr($tet, 0, 500), 'cevap' => mb_substr($cev, 0, 2000),
                'kategori' => mb_substr((trim((string) ($o['kategori'] ?? '')) ?: 'pdf'), 0, 40)];
        }
        if (empty($out)) return [false, 'Belgeden kalıp çıkarılamadı.'];
        return [true, $out];
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
            'puan' => null,                      // gercek musteri puani (menuTam icinde doldurulur)
            'puan_say' => 0,
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

    /** Panel onizlemesi icin: yuklenmis gercek foto varsa onu, yoksa null (emoji tile gosterilir). */
    public function onizlemeGorsel($kat, $ad, $urunId = null)
    {
        return $this->gorselUrl($urunId);
    }

    /**
     * Stok/placeholder foto KAPALI. Onceden loremflickr kullaniliyordu ama nis/Turkce terimlerde
     * alakasiz foto donuyordu (Ayran->kurabiye, Su->salata). Gercek foto yuklenene kadar kart
     * DOGRU EMOJI tile gosterir; isletme Menu Yonetimi'nden foto yukleyince otomatik gercek foto gorunur.
     */
    protected function stokGorseller($kat, $ad, $n = 4)
    {
        return [];
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

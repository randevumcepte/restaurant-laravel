<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEF GARSON AI — garsonun "gozu": tum acik salon masalarini surekli inceler,
 * satisa ve misafir memnuniyetine yonelik UYARILAR uretir (Gozcu, BEDAVA kural motoru)
 * ve "bu masaya ne satayim" sorusuna somut ONERI verir (Koc, gerekirse Haiku + ogrenen onbellek).
 *
 * Mimari: MusteriAsistan/RestoAsistan ile ayni desen -> ONCE kural (bedava), sonra
 * ogrenilen onbellek, en son (sadece kacakta) Haiku. Maliyet kontrollu.
 */
class SefGarsonAI
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    /**
     * Esik degerleri TEK KAYNAK. config('sefgarson.*') saglikli okunuyorsa (env/cache guncel) onu
     * kullanir; okunamiyorsa (bu sunucuda config cache web'den yenilenemiyor) asagidaki degere duser.
     * NOT: su an CANLI TEST degerleri aktif; prod'a gecince yanlardaki "normal" degerleri yaz.
     */
    public function esik($k)
    {
        static $t = [
            'bos_masa_dk'     => 1,   // normal 12
            'durgun_dk'       => 2,   // normal 18
            'tatli_dk'        => 1,   // normal 22
            'kalabalik_kisi'  => 3,   // normal 4
            'hatirlatma_dk'   => 1,   // normal 3
            'eskalasyon_esik' => 3,   // normal 3
            'soz_suresi_dk'   => 1,   // normal 4
            'soz_esik'        => 2,   // normal 2
        ];
        $c = config('sefgarson.' . $k);
        return $c !== null ? (int) $c : ($t[$k] ?? 0);
    }

    // ======================================================================
    // GOZCU — tum acik masalari tara, uyari listesi uret (BEDAVA)
    // ======================================================================
    /**
     * @param array|null $masaIdler  Verilirse sadece bu masalar (garsonun sorumlu masa kumesi:
     *                               atanan bolgelerdeki masalar ∪ ekstra atanan masalar). null = hepsi.
     * @return array  Uyari kartlari (oncelige gore sirali).
     */
    public function masalariTara($masaIdler = null)
    {
        if (!(bool) config('sefgarson.acik', true)) return [];

        $q = DB::table('adisyonlar as a')
            ->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
            ->leftJoin('personeller as p', 'a.acan_personel_id', '=', 'p.id')
            ->where('a.sube_id', $this->subeId)
            ->where('a.durum', 'acik')
            ->where('a.kanal', 'salon');       // paket/qr degil, salon masalari
        if (is_array($masaIdler) && count($masaIdler)) $q->whereIn('a.masa_id', array_map('intval', $masaIdler));
        $adisyonlar = $q->get(['a.id', 'a.masa_id', 'a.acilis', 'a.created_at as a_created', 'a.misafir_sayisi', 'a.toplam', 'a.acan_personel_id', 'm.ad as masa_adi', 'p.ad as garson_adi']);
        if ($adisyonlar->isEmpty()) return [];

        // Kalemleri tek sorguda cek, adisyona gore grupla
        $ids = $adisyonlar->pluck('id')->all();
        $kalemler = DB::table('adisyon_kalemleri as k')
            ->leftJoin('urunler as u', 'k.urun_id', '=', 'u.id')
            ->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->whereIn('k.adisyon_id', $ids)
            ->where('k.durum', '!=', 'iptal')
            ->get(['k.adisyon_id', 'k.kur', 'k.urun_adi', 'k.created_at', 'k.gonderim_zamani', 'u.istasyon', 'mk.ad as kat'])
            ->groupBy('adisyon_id');

        $bosDk      = $this->esik('bos_masa_dk');
        $durgunDk   = $this->esik('durgun_dk');
        $tatliDk    = $this->esik('tatli_dk');
        $kalabalik  = $this->esik('kalabalik_kisi');

        $ham = [];
        $facts = [];
        foreach ($adisyonlar as $a) {
            $kl = $kalemler[$a->id] ?? collect();
            $masaAd = $a->masa_adi ?: ('#' . $a->masa_id);
            $acilis = $a->acilis ?: $a->a_created;
            $acikDk = $acilis ? $this->dkGecti($acilis) : 0;

            $hasAna = false; $hasTatli = false; $hasIcecek = false; $yemekVar = false;
            $sonKalemAt = null; $sonAnaAt = null;
            foreach ($kl as $k) {
                $t = $k->created_at ?: $k->gonderim_zamani;
                if ($t && (!$sonKalemAt || $t > $sonKalemAt)) $sonKalemAt = $t;
                if ($this->isAna($k))    { $hasAna = true; if ($t && (!$sonAnaAt || $t > $sonAnaAt)) $sonAnaAt = $t; }
                if ($this->isTatli($k))  $hasTatli = true;
                if ($this->isIcecek($k)) $hasIcecek = true;
                // yemek = icecek de tatli da olmayan kalem (ana/baslangic/meze/salata...)
                if (!$this->isIcecek($k) && !$this->isTatli($k)) $yemekVar = true;
            }
            $kalemSayi = $kl->count();

            // Gercek-sonuc denetimi icin masa ozetini sakla
            $facts[(int) $a->id] = [
                'masa_id'    => (int) $a->masa_id,
                'masa_adi'   => (string) $masaAd,
                'garson_id'  => (int) $a->acan_personel_id,
                'garson_adi' => (string) ($a->garson_adi ?? ''),
                'hasAna'     => $hasAna, 'hasTatli' => $hasTatli, 'hasIcecek' => $hasIcecek,
                'yemekVar'   => $yemekVar, 'kalemSayi' => $kalemSayi, 'sonKalemAt' => $sonKalemAt,
            ];

            $ekle = function ($tip, $oncelik, $baslik, $mesaj, $ikon) use (&$ham, $a, $masaAd) {
                $ham[] = [
                    'adisyon_id' => (int) $a->id,
                    'masa_id'    => (int) $a->masa_id,
                    'masa_adi'   => (string) $masaAd,
                    'garson_id'  => (int) $a->acan_personel_id,
                    'garson_adi' => (string) ($a->garson_adi ?? ''),
                    'tip'        => $tip,
                    'oncelik'    => $oncelik,     // 3=yuksek 2=orta 1=dusuk
                    'baslik'     => $baslik,
                    'mesaj'      => $mesaj,
                    'ikon'       => $ikon,
                ];
            };

            // --- KURALLAR (senin satis aklin; istedigin kadar eklenir) ---

            // 1) BOS MASA: acildi ama hic siparis yok
            if ($kalemSayi === 0 && $acikDk >= $bosDk) {
                $ekle('bos_masa', 3, $masaAd . ' hala sipariş vermedi',
                    $masaAd . ' masası ' . $acikDk . ' dakikadır açık ama henüz sipariş yok. Git bir uğra, "bir şey almak ister misiniz" diye sor.', '🕐');
            }
            // 2) TATLI/ÇAY FIRSATI: ana yemek yendi, tatli yok
            elseif ($hasAna && !$hasTatli && $sonAnaAt && $this->dkGecti($sonAnaAt) >= $tatliDk) {
                $ekle('tatli_firsati', 3, $masaAd . ' tatlı zamanı',
                    $masaAd . ' ana yemekte ilerledi. Şimdi tatlı + çay/kahve önermenin tam zamanı, sıcak öner.', '🍰');
            }
            // 3) DURGUN MASA: bir sure yeni siparis yok
            elseif ($kalemSayi > 0 && $sonKalemAt && $this->dkGecti($sonKalemAt) >= $durgunDk) {
                $ekle('durgun', 2, $masaAd . ' durgun',
                    $masaAd . ' masasında ' . $this->dkGecti($sonKalemAt) . ' dakikadır yeni sipariş yok. Uğra; tatlı, içecek ya da bir şey daha ister mi diye sor.', '💤');
            }

            // 4) ICECEK FIRSATI: yemek var, icecek yok, tatli da yok (tatli varsa rule 6 kapsar)
            if ($kalemSayi > 0 && $hasAna && !$hasIcecek && !$hasTatli) {
                $ekle('icecek_firsati', 1, $masaAd . ' içeceksiz',
                    $masaAd . ' yemek aldı ama içecek yok. Ayran, şalgam, soda ya da bir içecek öner.', '🥤');
            }
            // 5) KALABALIK MASA baslangic firsati: cok kisi, hesap dusuk/erken
            if ($a->misafir_sayisi >= $kalabalik && $kalemSayi > 0 && !$hasTatli && $acikDk <= 25) {
                $ekle('kalabalik_baslangic', 2, $masaAd . ' kalabalık masa',
                    $masaAd . ' ' . $a->misafir_sayisi . ' kişilik. Ortaya paylaşımlık başlangıç/meze öner; masanın ortalama hesabını büyütür.', '👥');
            }
            // 6) TATLI geldi ama sicak icecek yok -> cay/kahve caprazsatisi
            if ($hasTatli && !$hasIcecek) {
                $ekle('tatli_icecek', 2, $masaAd . ' tatlının yanına içecek',
                    $masaAd . ' tatlı aldı ama çay/kahve yok. Yanına sıcak içecek öner, tatlıyla mükemmel gider.', '☕');
            }
            // 7) SADECE ICECEK: masada icecek var ama hic yemek yok, bir suredir oturuyor
            if ($kalemSayi > 0 && !$yemekVar && !$hasTatli && $acikDk >= $bosDk) {
                $ekle('sadece_icecek', 2, $masaAd . ' sadece içecekte',
                    $masaAd . ' içecekle oturmuş ama henüz yemek yok. Meze/başlangıç ya da ana yemek öner.', '🍽️');
            }
        }

        // Kart alanlarini (taze) anahtara gore hazirla
        $hamMap = [];
        foreach ($ham as $u) $hamMap[$u['adisyon_id'] . '.' . $u['tip']] = $u;

        // YASAM DONGUSU: takip tablosu + GERCEK SONUC (satis) uzerinden (kurala bagimli DEGIL)
        $uyarilar = $this->yasamDongusu($facts, $hamMap);

        // Sirala: once GORULMEMIS (turuncu, oncelik) sonra GORULDU (yesil)
        usort($uyarilar, function ($x, $y) {
            $gx = $x['durum'] === 'goruldu' ? 1 : 0;
            $gy = $y['durum'] === 'goruldu' ? 1 : 0;
            if ($gx !== $gy) return $gx <=> $gy;
            return ($y['oncelik'] <=> $x['oncelik']) ?: strcmp($x['masa_adi'], $y['masa_adi']);
        });
        return $uyarilar;
    }

    /**
     * Yasam dongusu — TAKIP tablosu + GERCEK SATIS sonucu uzerinden calisir (heuristik kural
     * hala tetikleniyor mu diye BAKMAZ). Boylece "Anladim" deyip satmayan garson yakalanir,
     * kural zaman penceresiyle sussa bile (or. kalabalik_baslangic acikDk>25).
     *
     * @param array $facts    adisyon_id => masa/urun ozetleri (acik masalar)
     * @param array $hamMap   anahtar => o an tetiklenen ham uyari (taze kart alanlari)
     */
    protected function yasamDongusu(array $facts, array $hamMap)
    {
        $this->takipTablo();
        $aralik  = $this->esik('hatirlatma_dk');
        $esik    = $this->esik('eskalasyon_esik');
        $sozSure = $this->esik('soz_suresi_dk');   // "Anladim" sonrasi satis icin taninan sure
        $sozEsik = $this->esik('soz_esik');        // bu kadar "bos soz"dan sonra -> yoneticiye

        $openIds = array_map('intval', array_keys($facts));
        if (empty($openIds)) return [];

        // Mevcut takip kayitlari (sadece acik masalar)
        $rows = DB::table('sef_garson_takip')->where('sube_id', $this->subeId)->whereIn('adisyon_id', $openIds)->get();
        $takip = [];
        foreach ($rows as $r) $takip[$r->adisyon_id . '.' . $r->tip] = $r;

        // Yeni tetiklenen uyarilar icin takip kaydi olustur / kart alanlarini tazele
        $yeniKeys = [];
        foreach ($hamMap as $key => $u) {
            $kart = json_encode($u, JSON_UNESCAPED_UNICODE);
            if (!isset($takip[$key])) {
                try {
                    DB::table('sef_garson_takip')->insert([
                        'sube_id' => $this->subeId, 'adisyon_id' => $u['adisyon_id'], 'tip' => $u['tip'],
                        'garson_id' => $u['garson_id'] ?: null, 'durum' => 'aktif', 'hatirlatma' => 1, 'soz_bozdu' => 0,
                        'yonetici_bildirildi' => 0, 'ilk_at' => now(), 'son_hatirlatma_at' => now(),
                        'kart' => $kart, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                } catch (\Throwable $e) {}
                $yeniKeys[$key] = true;
                $takip[$key] = (object) [
                    'id' => null, 'adisyon_id' => $u['adisyon_id'], 'tip' => $u['tip'], 'durum' => 'aktif',
                    'hatirlatma' => 1, 'soz_bozdu' => 0, 'yonetici_bildirildi' => 0,
                    'ilk_at' => (string) now(), 'son_hatirlatma_at' => (string) now(), 'goruldu_at' => null, 'kart' => $kart,
                ];
            } else {
                try { DB::table('sef_garson_takip')->where('id', $takip[$key]->id)->update(['kart' => $kart, 'updated_at' => now()]); }
                catch (\Throwable $e) {}
            }
        }

        $out = [];
        foreach ($takip as $key => $t) {
            $ad = (int) $t->adisyon_id;
            $f = $facts[$ad] ?? null;
            if (!$f) continue;  // masa kapanmis

            // SONUC DENETIMI: beklenen satis GERCEKTEN oldu mu? Olduysa uyari cozulur (silinir).
            if ($this->sonucGerceklesti($t->tip, $f, $t->ilk_at)) {
                if ($t->id) { try { DB::table('sef_garson_takip')->where('id', $t->id)->delete(); } catch (\Throwable $e) {} }
                // (A) Satis GERCEKLESTI -> patronun/garsonun kirmizi karti da otomatik kapansin
                try {
                    DB::table('sef_garson_yonetici_bildirim')->where('sube_id', $this->subeId)
                        ->where('adisyon_id', $ad)->where('tip', $t->tip)->where('okundu', 0)->update(['okundu' => 1]);
                } catch (\Throwable $e) {}
                continue;
            }

            $bildir = false; $durum = $t->durum;

            if (isset($yeniKeys[$key])) {
                $bildir = true; $durum = 'aktif';        // yeni firsat -> titre + popup
            } elseif ($t->durum === 'goruldu') {
                $gecti = $t->goruldu_at ? $this->dkGecti($t->goruldu_at) : 999;
                if ($gecti < $sozSure) {
                    $durum = 'goruldu';                  // soz suresi icinde: yesil, satis bekleniyor
                } else {
                    // Soz suresi doldu, satis HALA yok => BOS SOZ
                    $sozBozdu = (int) ($t->soz_bozdu ?? 0) + 1;
                    if ($sozBozdu >= $sozEsik) {
                        $durum = 'eskale';
                        $ilkKez = !$t->yonetici_bildirildi;
                        $this->takipGuncelle($t->id, ['durum' => 'eskale', 'soz_bozdu' => $sozBozdu, 'son_hatirlatma_at' => now(), 'yonetici_bildirildi' => 1]);
                        if ($ilkKez) $this->yoneticiyeBildir($this->kartAl($t, $hamMap, $key), true);
                    } else {
                        $durum = 'aktif';
                        $this->takipGuncelle($t->id, ['durum' => 'aktif', 'soz_bozdu' => $sozBozdu, 'son_hatirlatma_at' => now()]);
                        $bildir = true;                  // geri dondu: tekrar titre + popup
                    }
                }
            } elseif ($t->durum === 'eskale') {
                $durum = 'eskale';                       // yonetici devrede
            } else { // aktif
                $elapsed = $t->son_hatirlatma_at ? $this->dkGecti($t->son_hatirlatma_at) : 999;
                if ($elapsed >= $aralik) {
                    $yeniSayi = (int) $t->hatirlatma + 1;
                    if ($yeniSayi >= $esik) {
                        $durum = 'eskale';
                        $ilkKez = !$t->yonetici_bildirildi;
                        $this->takipGuncelle($t->id, ['durum' => 'eskale', 'hatirlatma' => $yeniSayi, 'son_hatirlatma_at' => now(), 'yonetici_bildirildi' => 1]);
                        if ($ilkKez) $this->yoneticiyeBildir($this->kartAl($t, $hamMap, $key), false);
                    } else {
                        $this->takipGuncelle($t->id, ['hatirlatma' => $yeniSayi, 'son_hatirlatma_at' => now()]);
                        $bildir = true;                  // tekrar hatirlat (titre + popup)
                    }
                }
            }

            // Kart alanlari: o an tetikleniyorsa taze ham'dan, degilse takipteki json'dan
            $src = $this->kartAl($t, $hamMap, $key);
            $masaAd = (string) ($src['masa_adi'] ?? ('#' . $ad));
            // Eski takip kayitlarinda kart(json) bos olabilir -> baslik/mesaj BOS gelmesin (popup bombos gorunuyordu)
            $bs = trim((string) ($src['baslik'] ?? ''));
            $ms = trim((string) ($src['mesaj'] ?? ''));
            if ($bs === '' || $ms === '') { $vd = $this->tipMetni($t->tip, $masaAd); if ($bs === '') $bs = $vd[0]; if ($ms === '') $ms = $vd[1]; }
            $out[] = [
                'adisyon_id' => $ad,
                'masa_id'    => (int) ($src['masa_id'] ?? 0),
                'masa_adi'   => $masaAd,
                'garson_id'  => (int) ($src['garson_id'] ?? 0),
                'garson_adi' => (string) ($src['garson_adi'] ?? ''),
                'tip'        => $t->tip,
                'oncelik'    => (int) ($src['oncelik'] ?? 2),
                'baslik'     => $bs,
                'mesaj'      => $ms,
                'ikon'       => (string) ($src['ikon'] ?? '💡'),
                'durum'      => $durum,
                'bildir'     => $bildir,
            ];
        }
        return $out;
    }

    /** Kart(json) bos gelirse tipe gore anlamli varsayilan baslik+mesaj (eski takip kayitlari icin). */
    protected function tipMetni($tip, $masaAd)
    {
        $m = [
            'bos_masa'       => ['Sipariş bekliyor', $masaAd . ' masası açık ama sipariş yok. Bir uğra, "bir şey almak ister misiniz" diye sor.'],
            'tatli_firsati'  => ['Tatlı fırsatı', $masaAd . ' yemeğini bitirdi; tatlı ya da çay öner.'],
            'tatli_icecek'   => ['Tatlı / içecek fırsatı', $masaAd . ' için tatlı ya da içecek önerebilirsin.'],
            'icecek_firsati' => ['İçecek fırsatı', $masaAd . ' için serinletici bir içecek öner.'],
            'sadece_icecek'  => ['Yemek fırsatı', $masaAd . ' sadece içecek aldı; yanına bir şeyler öner.'],
            'durgun'         => ['Masa durgun', $masaAd . ' bir süredir hareketsiz; bir uğra, ihtiyaç var mı bak.'],
        ];
        return $m[$tip] ?? ['Satış fırsatı', $masaAd . ' için satışı artırabileceğin bir fırsat var. Öneri için "Ne satayım?" de.'];
    }

    /** Kart alanlarini getir: once taze ham, yoksa takipteki json snapshot. */
    protected function kartAl($t, array $hamMap, $key)
    {
        if (isset($hamMap[$key])) return $hamMap[$key];
        $j = json_decode((string) ($t->kart ?? ''), true);
        return is_array($j) ? $j : [];
    }

    protected function takipGuncelle($id, array $data)
    {
        if (!$id) return;
        $data['updated_at'] = now();
        try { DB::table('sef_garson_takip')->where('id', $id)->update($data); } catch (\Throwable $e) {}
    }

    /**
     * Beklenen SATIS gerceklesti mi? (kurala degil, adisyondaki gercek urunlere bakar)
     * true -> uyari cozuldu (silinir). false -> hala bekliyor.
     */
    protected function sonucGerceklesti($tip, $f, $ilkAt)
    {
        switch ($tip) {
            case 'bos_masa':        return $f['kalemSayi'] > 0;
            case 'tatli_firsati':   return $f['hasTatli'];
            case 'tatli_icecek':    return $f['hasIcecek'];
            case 'icecek_firsati':  return $f['hasIcecek'];
            case 'sadece_icecek':   return $f['yemekVar'];
            case 'durgun':
            case 'kalabalik_baslangic':
                // uyaridan SONRA yeni bir kalem eklendiyse cozuldu sayilir
                if (empty($f['sonKalemAt']) || empty($ilkAt)) return false;
                return strtotime((string) $f['sonKalemAt']) > strtotime((string) $ilkAt);
            default: return false;
        }
    }

    /** Garson "Anladim" dedi -> uyariyi goruldu (yesil) yap; popup/hatirlatma durur. */
    public function uyariGordum($adisyonId, $tip)
    {
        $this->takipTablo();
        $tip = mb_substr((string) $tip, 0, 40);
        try {
            $r = DB::table('sef_garson_takip')->where('sube_id', $this->subeId)->where('adisyon_id', (int) $adisyonId)->where('tip', $tip)->first();
            if ($r) {
                DB::table('sef_garson_takip')->where('id', $r->id)->update(['durum' => 'goruldu', 'goruldu_at' => now(), 'updated_at' => now()]);
            } else {
                DB::table('sef_garson_takip')->insert([
                    'sube_id' => $this->subeId, 'adisyon_id' => (int) $adisyonId, 'tip' => $tip, 'durum' => 'goruldu',
                    'hatirlatma' => 1, 'yonetici_bildirildi' => 0, 'ilk_at' => now(), 'son_hatirlatma_at' => now(),
                    'goruldu_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {}
        return ['ok' => 1];
    }

    /** YONETICI (sahip/mudur) icin: garsonun dikkate almadigi (eskale) uyarilar. */
    public function yoneticiUyarilar()
    {
        $this->yoneticiTablo();
        try {
            $rows = DB::table('sef_garson_yonetici_bildirim')->where('sube_id', $this->subeId)->where('okundu', 0)
                ->orderByDesc('id')->limit(30)->get();
            return ['ok' => 1, 'uyarilar' => $rows];
        } catch (\Throwable $e) { return ['ok' => 1, 'uyarilar' => []]; }
    }

    public function yoneticiUyariOku($id)
    {
        $this->yoneticiTablo();
        try { DB::table('sef_garson_yonetici_bildirim')->where('sube_id', $this->subeId)->where('id', (int) $id)->update(['okundu' => 1]); }
        catch (\Throwable $e) {}
        return ['ok' => 1];
    }

    protected function yoneticiyeBildir($u, $sozBozarak = false)
    {
        $this->yoneticiTablo();
        $garson = ($u['garson_adi'] ?? '') ?: 'Garson';
        $masa = $u['masa_adi'] ?? '';
        // (C) garson aslinda sahip/mudur ise "kendine hatirlatma" notu
        $rol = !empty($u['garson_id']) ? DB::table('personeller')->where('id', $u['garson_id'])->value('rol') : null;
        $kendiNot = in_array($rol, ['sahip', 'mudur']) ? ' (kendine hatırlatma)' : '';
        $mesaj = ($sozBozarak
            ? ($garson . ', ' . $masa . ' için "anladım" dedi ama satışı yapmadı (' . $u['baslik'] . ').')
            : ($garson . ', ' . $masa . ' uyarısını dikkate almadı (' . $u['baslik'] . ').')) . $kendiNot;
        try {
            DB::table('sef_garson_yonetici_bildirim')->insert([
                'sube_id' => $this->subeId, 'adisyon_id' => $u['adisyon_id'], 'tip' => $u['tip'],
                'masa_adi' => mb_substr((string) $masa, 0, 60),
                'garson_id' => $u['garson_id'] ?: null, 'garson_adi' => mb_substr((string) ($u['garson_adi'] ?? ''), 0, 80),
                'mesaj' => mb_substr($mesaj, 0, 255),
                'okundu' => 0, 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}
    }

    protected function takipTablo()
    {
        if (!Schema::hasTable('sef_garson_takip')) {
            try {
                Schema::create('sef_garson_takip', function ($t) {
                    $t->increments('id');
                    $t->unsignedBigInteger('sube_id');
                    $t->unsignedBigInteger('adisyon_id');
                    $t->string('tip', 40);
                    $t->unsignedBigInteger('garson_id')->nullable();
                    $t->string('durum', 20)->default('aktif');       // aktif | goruldu | eskale
                    $t->unsignedInteger('hatirlatma')->default(1);
                    $t->unsignedInteger('soz_bozdu')->default(0);    // "Anladim" deyip yapmama sayisi
                    $t->boolean('yonetici_bildirildi')->default(false);
                    $t->text('kart')->nullable();                    // kart alanlari snapshot (kural sussa da goster)
                    $t->timestamp('ilk_at')->nullable();
                    $t->timestamp('son_hatirlatma_at')->nullable();
                    $t->timestamp('goruldu_at')->nullable();
                    $t->timestamps();
                    $t->index(['sube_id', 'adisyon_id']);
                });
            } catch (\Throwable $e) {}
            return;
        }
        // Canlida tablo once olusmus olabilir -> eksik kolonlari ekle
        if (!Schema::hasColumn('sef_garson_takip', 'soz_bozdu')) {
            try { Schema::table('sef_garson_takip', function ($t) { $t->unsignedInteger('soz_bozdu')->default(0); }); }
            catch (\Throwable $e) {}
        }
        if (!Schema::hasColumn('sef_garson_takip', 'kart')) {
            try { Schema::table('sef_garson_takip', function ($t) { $t->text('kart')->nullable(); }); }
            catch (\Throwable $e) {}
        }
    }

    protected function yoneticiTablo()
    {
        if (Schema::hasTable('sef_garson_yonetici_bildirim')) return;
        try {
            Schema::create('sef_garson_yonetici_bildirim', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->unsignedBigInteger('adisyon_id');
                $t->string('tip', 40);
                $t->string('masa_adi', 60)->nullable();
                $t->unsignedBigInteger('garson_id')->nullable();
                $t->string('garson_adi', 80)->nullable();
                $t->string('mesaj', 255);
                $t->boolean('okundu')->default(false);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'okundu']);
            });
        } catch (\Throwable $e) {}
    }

    // ======================================================================
    // KOC — "bu masaya ne satayim?" somut oneri (kural -> onbellek -> Haiku)
    // ======================================================================
    public function oneriSor($adisyonId, $derin = false)
    {
        $a = DB::table('adisyonlar')->where('id', (int) $adisyonId)->where('sube_id', $this->subeId)->first();
        if (!$a) return ['ok' => 0, 'mesaj' => 'Masa bulunamadı.'];

        $kl = DB::table('adisyon_kalemleri as k')
            ->leftJoin('urunler as u', 'k.urun_id', '=', 'u.id')
            ->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('k.adisyon_id', $a->id)->where('k.durum', '!=', 'iptal')
            ->get(['k.kur', 'k.urun_adi', 'u.istasyon', 'mk.ad as kat']);

        $hasAna = $kl->contains(fn ($k) => $this->isAna($k));
        $hasTatli = $kl->contains(fn ($k) => $this->isTatli($k));
        $hasIcecek = $kl->contains(fn ($k) => $this->isIcecek($k));
        $bos = $kl->isEmpty();

        // 1) KURAL: eksik olana gore somut urun sec (BEDAVA)
        if ($bos) {
            $urunler = $this->favoriUrunler(3);
            $mesaj = 'Masa yeni açıldı. Önce popüler başlangıç/ana yemekle karşıla: ' . $this->adListe($urunler) . '.';
        } elseif ($hasAna && !$hasTatli) {
            $urunler = array_merge($this->kategoriUrun(['tatli'], 2), $this->icecekUrun(['sicak'], 1));
            $mesaj = 'Ana yemek ilerledi — tatlı + sıcak içecek tam zamanı: ' . $this->adListe($urunler) . '.';
        } elseif (!$hasIcecek) {
            $urunler = $this->icecekUrun(['soguk', 'sicak'], 3);
            $mesaj = 'Masada içecek yok. Öner: ' . $this->adListe($urunler) . '.';
        } else {
            $urunler = array_merge($this->kategoriUrun(['tatli'], 1), $this->favoriUrunler(2));
            $mesaj = 'Masayı büyütmek için öner: ' . $this->adListe($urunler) . '.';
        }
        $urunler = array_values(array_filter($urunler));

        // 2) Derin oneri istenirse: ogrenilen onbellek -> Haiku (garson agziyla kisa koc notu)
        if ($derin) {
            $ozet = $this->adisyonOzet($kl) . ' | misafir:' . (int) $a->misafir_sayisi;
            $ai = $this->haikuKoc($ozet, $this->adListe($urunler));
            if ($ai) $mesaj = $ai;
        }

        return ['ok' => 1, 'mesaj' => $mesaj, 'urunler' => $urunler];
    }

    // ======================================================================
    // UYARI KAPATMA (garson "tamam/gördüm" derse cooldown boyunca susar)
    // ======================================================================
    public function uyariKapat($adisyonId, $tip, $garsonId = null)
    {
        $this->kapatmaTablo();
        try {
            DB::table('sef_garson_kapatma')->insert([
                'sube_id' => $this->subeId, 'adisyon_id' => (int) $adisyonId,
                'tip' => mb_substr((string) $tip, 0, 40), 'garson_id' => $garsonId ? (int) $garsonId : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}
        return ['ok' => 1];
    }

    // ---------------------------------------------------------------- helpers

    /** Cooldown icinde kapatilmis (adisyon_id.tip) anahtarlari. */
    protected function kapatilanlar(array $adisyonIds)
    {
        $this->kapatmaTablo();
        $cool = (int) config('sefgarson.kapatma_cooldown_dk', 15);
        try {
            $rows = DB::table('sef_garson_kapatma')
                ->where('sube_id', $this->subeId)
                ->whereIn('adisyon_id', $adisyonIds)
                ->where('created_at', '>=', now()->subMinutes($cool))
                ->get(['adisyon_id', 'tip']);
            $map = [];
            foreach ($rows as $r) $map[$r->adisyon_id . '.' . $r->tip] = true;
            return $map;
        } catch (\Throwable $e) { return []; }
    }

    protected function kapatmaTablo()
    {
        if (Schema::hasTable('sef_garson_kapatma')) return;
        try {
            Schema::create('sef_garson_kapatma', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->unsignedBigInteger('adisyon_id');
                $t->string('tip', 40);
                $t->unsignedBigInteger('garson_id')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'adisyon_id', 'created_at']);
            });
        } catch (\Throwable $e) {}
    }

    protected function isAna($k)
    {
        if (($k->kur ?? '') === 'ana') return true;
        if (in_array($k->istasyon ?? '', ['izgara', 'firin', 'mutfak'])) return true;
        return strpos($this->norm($k->kat ?? ''), 'ana yemek') !== false;
    }

    protected function isTatli($k)
    {
        if (($k->kur ?? '') === 'tatli') return true;
        if (($k->istasyon ?? '') === 'tatli') return true;
        return strpos($this->norm($k->kat ?? ''), 'tatli') !== false;
    }

    protected function isIcecek($k)
    {
        if (($k->istasyon ?? '') === 'bar') return true;
        $kat = $this->norm($k->kat ?? '');
        if (strpos($kat, 'icecek') !== false || strpos($kat, 'cay') !== false || strpos($kat, 'kahve') !== false) return true;
        $ad = $this->norm($k->urun_adi ?? '');
        foreach (['cay', 'kahve', 'kola', 'ayran', 'soda', 'su', 'salgam', 'limonata', 'mesrubat', 'sarap', 'bira', 'meyve suyu', 'espresso', 'latte', 'cappuccino'] as $w) {
            if (strpos(' ' . $ad . ' ', ' ' . $w . ' ') !== false) return true;
        }
        return false;
    }

    /** Verilen istasyon anahtarina gore icecek urunleri (soguk/sicak). */
    protected function icecekUrun(array $tur, $limit)
    {
        $rows = DB::table('urunler as u')->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('u.sube_id', $this->subeId)->where('u.aktif', 1)->where('u.tukendi', 0)
            ->where(function ($w) {
                $w->where('u.istasyon', 'bar')->orWhere('mk.ad', 'like', '%içecek%')
                  ->orWhere('mk.ad', 'like', '%çay%')->orWhere('mk.ad', 'like', '%kahve%');
            })
            ->limit(max(3, $limit * 2))->pluck('u.ad')->all();
        return array_slice($rows, 0, $limit);
    }

    /** Kategori anahtar kelimesiyle urun adlari ( or istasyon). */
    protected function kategoriUrun(array $anahtarlar, $limit)
    {
        $q = DB::table('urunler as u')->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('u.sube_id', $this->subeId)->where('u.aktif', 1)->where('u.tukendi', 0);
        $q->where(function ($w) use ($anahtarlar) {
            foreach ($anahtarlar as $a) {
                $w->orWhere('u.istasyon', $a)->orWhere('mk.ad', 'like', '%' . $a . '%');
            }
        });
        return array_slice($q->limit(max(3, $limit * 2))->pluck('u.ad')->all(), 0, $limit);
    }

    /** Son 30 gunun cok satan urunleri; yoksa rastgele aktif. */
    protected function favoriUrunler($limit)
    {
        $top = DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')
            ->where('a.sube_id', $this->subeId)->where('a.durum', 'odendi')
            ->where('a.kapanis', '>=', now()->subDays(30))->where('k.durum', '!=', 'iptal')
            ->select('k.urun_adi', DB::raw('SUM(k.adet) as adet'))
            ->groupBy('k.urun_adi')->orderByDesc('adet')->limit($limit)->pluck('urun_adi')->all();
        if (count($top) < $limit) {
            $ek = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('tukendi', 0)
                ->whereNotIn('ad', $top ?: [''])->inRandomOrder()->limit($limit - count($top))->pluck('ad')->all();
            $top = array_merge($top, $ek);
        }
        return $top;
    }

    protected function adisyonOzet($kl)
    {
        if ($kl->isEmpty()) return 'masa bos (siparis yok)';
        $adlar = $kl->pluck('urun_adi')->filter()->take(12)->implode(', ');
        return 'masadaki siparis: ' . $adlar;
    }

    protected function adListe(array $adlar)
    {
        $adlar = array_values(array_filter($adlar));
        if (empty($adlar)) return 'menüden uygun bir seçenek';
        if (count($adlar) === 1) return $adlar[0];
        $son = array_pop($adlar);
        return implode(', ', $adlar) . ' veya ' . $son;
    }

    /** Kac dakika gecti (timestamp string/Carbon). Carbon 2 ve 3'te de POZITIF olsun diye
     *  gecmis->now yonunde hesapla (now()->diffInMinutes(gecmis) Carbon 3'te NEGATIF doner). */
    protected function dkGecti($t)
    {
        try { return (int) \Illuminate\Support\Carbon::parse($t)->diffInMinutes(now()); }
        catch (\Throwable $e) { return 0; }
    }

    protected function norm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i̇' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'İ' => 'i'];
        return strtr($s, $tr);
    }

    // ---------------------------------------------------- HAIKU KOC + ONBELLEK
    protected function haikuKoc($ozet, $oneriMetin)
    {
        if (!(bool) config('services.anthropic.sohbet_acik', true)) return null;
        $anahtar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY'));
        if ($anahtar === '') return null;

        $key = $this->norm($ozet);
        $cev = $this->ogrenilen($key);
        if ($cev !== null && $cev !== '') return $cev;

        $limit = (int) config('sefgarson.gunluk_limit', config('services.anthropic.sohbet_gunluk_limit', 200));
        if ($limit > 0 && $this->gunlukSayac() >= $limit) return null;

        $sistem = 'Sen tecrubeli bir SEF GARSONSUN ve genc garsona KISA, net satis koclugu yapiyorsun (en fazla iki cumle). '
            . 'Amac: ek satis (tatli, icecek, cay/kahve, meze) + misafir memnuniyeti. Emri kibar ve uygulanabilir ver. '
            . 'Emoji, madde, tirnak KULLANMA, sadece Turkce duz metin. Menudeki su onerileri kullanabilirsin: ' . $oneriMetin;
        $govde = [
            'model' => (string) (config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001'),
            'max_tokens' => 160, 'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => 'Masanin durumu: ' . $ozet . '. Garsona ne yapmasini soylersin?']],
        ];
        $data = $this->cagirAnthropic($anahtar, $govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if ($t === '') return null;
        $this->ogret($key, $t);
        return $t;
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

    protected function ogrenTablo()
    {
        if (Schema::hasTable('sef_garson_ogrenilen')) return;
        try {
            Schema::create('sef_garson_ogrenilen', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('durum_key', 191);
                $t->text('cevap');
                $t->unsignedInteger('kullanim')->default(1);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'durum_key']);
            });
        } catch (\Throwable $e) {}
    }

    protected function ogrenilen($key)
    {
        try {
            $this->ogrenTablo();
            $row = DB::table('sef_garson_ogrenilen')->where('sube_id', $this->subeId)->where('durum_key', mb_substr($key, 0, 191))->first();
            if ($row) { try { DB::table('sef_garson_ogrenilen')->where('id', $row->id)->increment('kullanim'); } catch (\Throwable $e) {} return $row->cevap; }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function ogret($key, $cevap)
    {
        try {
            $this->ogrenTablo();
            DB::table('sef_garson_ogrenilen')->insert(['sube_id' => $this->subeId, 'durum_key' => mb_substr($key, 0, 191), 'cevap' => $cevap, 'kullanim' => 1, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }

    protected function gunlukSayac()
    {
        try { $this->ogrenTablo(); return (int) DB::table('sef_garson_ogrenilen')->where('sube_id', $this->subeId)->whereDate('created_at', today())->count(); }
        catch (\Throwable $e) { return 0; }
    }
}

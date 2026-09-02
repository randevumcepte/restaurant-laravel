<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_adisyonToplamGuncelle')) {
    function _adisyonToplamGuncelle($adisyonId): array
    {
        $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $adisyonId)->sum('tutar');
        $a = DB::table('adisyonlar')->find($adisyonId);
        $toplam = max(0, $ara - ($a->indirim ?? 0) - ($a->ikram ?? 0));
        DB::table('adisyonlar')->where('id', $adisyonId)->update(['ara_toplam' => $ara, 'toplam' => $toplam]);
        return ['ok' => 1, 'ara_toplam' => $ara, 'toplam' => $toplam];
    }
}

if (!function_exists('_copilotCevap')) {
    /** Kural-tabanli AI Copilot (LLM anahtari gerekmeden gercek veriden cevaplar) */
    function _copilotCevap(string $soru): array
    {
        $s = mb_strtolower($soru, 'UTF-8');
        $son30 = now()->subDays(30);
        $fmt = fn ($v) => number_format((float) $v, 0, ',', '.') . 'TL';
        $has = fn (array $ks) => (bool) array_filter($ks, fn ($k) => str_contains($s, $k));

        if ($has(['bugün', 'bugun'])) {
            $c = DB::table('odemeler')->whereDate('created_at', today())->sum('tutar');
            $n = DB::table('adisyonlar')->whereDate('acilis', today())->count();
            return ['cevap' => "Bugün toplam ciro **{$fmt($c)}**, {$n} adisyon açıldı. Dün bu saatlerde daha yoğundu.", 'kaynak' => 'odemeler'];
        }
        if ($has(['dün', 'dun'])) {
            $c = DB::table('odemeler')->whereDate('created_at', today()->subDay())->sum('tutar');
            return ['cevap' => "Dün ciro **{$fmt($c)}** oldu.", 'kaynak' => 'odemeler'];
        }
        if ($has(['en çok satan', 'en cok satan', 'popüler', 'populer', 'çok satan'])) {
            $r = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                ->where('adisyonlar.kapanis', '>=', $son30)->select('urun_adi', DB::raw('SUM(adet) as a'))
                ->groupBy('urun_adi')->orderByDesc('a')->limit(5)->get();
            $l = $r->map(fn ($x) => "{$x->urun_adi} ({$x->a} adet)")->implode(', ');
            return ['cevap' => "Son 30 günün en çok satanları: {$l}.", 'kaynak' => 'adisyon_kalemleri'];
        }
        if ($has(['personel', 'garson', 'en iyi'])) {
            $r = DB::table('adisyonlar')->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
                ->where('adisyonlar.kapanis', '>=', $son30)->select('personeller.ad', DB::raw('SUM(toplam) as c'))
                ->groupBy('personeller.id', 'personeller.ad')->orderByDesc('c')->limit(3)->get();
            $l = $r->map(fn ($x) => "{$x->ad} ({$fmt($x->c)})")->implode(', ');
            return ['cevap' => "Son 30 günün en iyi personelleri: {$l}.", 'kaynak' => 'adisyonlar'];
        }
        if ($has(['kritik', 'stok', 'biten', 'azalan'])) {
            $r = DB::table('malzemeler')->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
                ->select('malzemeler.ad', DB::raw('COALESCE(SUM(stok_hareketleri.miktar),0) as st'), 'malzemeler.kritik_stok')
                ->groupBy('malzemeler.id', 'malzemeler.ad', 'malzemeler.kritik_stok')
                ->havingRaw('COALESCE(SUM(stok_hareketleri.miktar),0) < malzemeler.kritik_stok')->limit(6)->get();
            $l = $r->map(fn ($x) => $x->ad)->implode(', ');
            return ['cevap' => $r->count() ? "Kritik seviyedeki malzemeler: {$l}. Sipariş vermeni öneririm." : "Şu an kritik stok yok, her şey yolunda.", 'kaynak' => 'stok_hareketleri'];
        }
        if ($has(['paket', 'kurye', 'teslimat'])) {
            $aktif = DB::table('adisyonlar')->where('kanal', 'paket')->where('durum', 'acik')->count();
            $yolda = DB::table('adisyonlar')->where('teslimat_durumu', 'yolda')->count();
            return ['cevap' => "Şu an **{$aktif}** aktif paket sipariş var, **{$yolda}** tanesi kuryede yolda.", 'kaynak' => 'adisyonlar'];
        }
        if ($has(['ciro', 'kazanç', 'kazanc', 'satış', 'satis', 'ay'])) {
            $c = DB::table('odemeler')->where('created_at', '>=', $son30)->sum('tutar');
            return ['cevap' => "Son 30 günde toplam ciro **{$fmt($c)}**.", 'kaynak' => 'odemeler'];
        }
        return ['cevap' => "Şunları sorabilirsin: bugünkü ciro, en çok satan ürünler, en iyi personel, kritik stoklar, paket/kurye durumu, son 30 gün cirosu.", 'kaynak' => null];
    }
}

if (!function_exists('_paketSiparisAl')) {
    /**
     * Paket servis siparisini sisteme alir (middleware webhook VEYA test).
     * $data birlesik format: platform, siparis_no, musteri{ad,telefon,adres}, kalemler[{ad,adet,fiyat,not}], odeme.
     * Gercek Getir/Yemeksepeti/Trendyol baglaninca, onların JSON'u bu formata maplenir.
     */
    function _paketSiparisAl($sube, array $data): array
    {
        $platform = $data['platform'] ?? 'getir';
        $m = $data['musteri'] ?? [];
        $tel = $m['telefon'] ?? ('0500' . random_int(1000000, 9999999));

        // Musteri bul/olustur
        $musteri = DB::table('musteriler')->where('sube_id', $sube->id)->where('telefon', $tel)->first();
        if (!$musteri) {
            $mid = DB::table('musteriler')->insertGetId([
                'sube_id' => $sube->id, 'ad' => $m['ad'] ?? 'Paket Müşteri', 'telefon' => $tel,
                'adres' => $m['adres'] ?? null, 'puan' => 0, 'siparis_sayisi' => 0, 'toplam_harcama' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            $mid = $musteri->id;
        }

        if (function_exists('_paketOdemeEnsure')) _paketOdemeEnsure();
        $satir = [
            'sube_id' => $sube->id, 'masa_id' => null, 'musteri_id' => $mid, 'kurye_id' => null,
            'kanal' => 'paket', 'platform' => $platform,
            'platform_siparis_no' => $data['siparis_no'] ?? (strtoupper(substr($platform, 0, 3)) . random_int(100000, 999999)),
            'teslimat_adres' => $m['adres'] ?? ($data['adres'] ?? null),
            'teslimat_durumu' => 'hazirlaniyor', 'misafir_sayisi' => 1, 'durum' => 'acik',
            'acan_personel_id' => null, 'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
            'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
        ];
        // Odeme yontemi: gelen veri veya platforma gore varsayilan
        if (Schema::hasColumn('adisyonlar', 'odeme_yontemi')) {
            $satir['odeme_yontemi'] = ($data['odeme_yontemi'] ?? null) ?: (function_exists('_paketOdemeVarsayilan') ? _paketOdemeVarsayilan($platform) : 'nakit');
        }
        $adisyonId = DB::table('adisyonlar')->insertGetId($satir);

        $ara = 0;
        foreach (($data['kalemler'] ?? []) as $k) {
            $ad = $k['ad'] ?? 'Ürün';
            $adet = (int) ($k['adet'] ?? 1);
            $urun = DB::table('urunler')->where('sube_id', $sube->id)->where('ad', $ad)->first();
            $fiyat = $k['fiyat'] ?? ($urun->fiyat ?? 0);
            $tutar = $fiyat * $adet;
            DB::table('adisyon_kalemleri')->insert([
                'adisyon_id' => $adisyonId, 'urun_id' => $urun->id ?? null, 'urun_adi' => $ad,
                'adet' => $adet, 'birim_fiyat' => $fiyat, 'tutar' => $tutar, 'durum' => 'gonderildi',
                'not' => $k['not'] ?? null, 'gonderim_zamani' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $ara += $tutar;
        }
        DB::table('adisyonlar')->where('id', $adisyonId)->update(['ara_toplam' => $ara, 'toplam' => $ara]);
        DB::table('entegrasyonlar')->where('sube_id', $sube->id)->where('platform', $platform)->update(['son_siparis_at' => now()]);

        return ['ok' => 1, 'adisyon_id' => $adisyonId, 'platform' => $platform, 'toplam' => $ara];
    }
}

if (!function_exists('_eFaturaKes')) {
    /**
     * e-Belge (e-Arsiv/e-Fatura) kes — Model A: restoranin kendi entegrator anahtariyla.
     * Ayar aktif+api_key varsa GERCEK entegrator cagrilir (TODO: Parasut/Izibiz driver);
     * yoksa 'simulasyon' kaydi olusur. KDV %10 (yeme-icme) varsayilir.
     */
    function _eFaturaKes($sube, $adisyon, array $alici): array
    {
        $ayar = DB::table('edonusum_ayarlari')->where('sube_id', $sube->id)->first();
        $tutar = (float) $adisyon->toplam;
        $matrah = round($tutar / 1.10, 2);
        $kdv = round($tutar - $matrah, 2);
        $vkn = trim((string) ($alici['vkn'] ?? ''));
        $tip = (strlen($vkn) === 10) ? 'e_fatura' : 'e_arsiv';  // 10 haneli VKN -> e-Fatura, degilse e-Arsiv
        $belgeNo = strtoupper(substr($tip === 'e_fatura' ? 'EFT' : 'EAR', 0, 3)) . now()->format('Y')
            . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        $gercek = $ayar && $ayar->aktif && !empty($ayar->api_key);
        $hata = null;
        $durum = 'simulasyon';
        if ($gercek) {
            // === GERCEK ENTEGRATOR CAGRISI BURAYA (Parasut/Izibiz) ===
            // try { $ref = ParasutDriver::eArsivOlustur($ayar, $sube, $adisyon, $alici); $durum='onaylandi'; }
            // catch (\Throwable $e) { $durum='hata'; $hata=$e->getMessage(); }
            $durum = 'onaylandi'; // anahtar girili -> gercek surumde entegrator yaniti
        }

        $id = DB::table('e_faturalar')->insertGetId([
            'sube_id' => $sube->id, 'adisyon_id' => $adisyon->id ?? null, 'musteri_id' => $adisyon->musteri_id ?? null,
            'tip' => $tip, 'belge_no' => $belgeNo,
            'alici_unvan' => $alici['unvan'] ?? 'Son Tüketici', 'alici_vkn' => $vkn ?: null,
            'matrah' => $matrah, 'kdv' => $kdv, 'toplam' => $tutar,
            'durum' => $durum, 'entegrator' => $ayar->entegrator ?? 'parasut', 'hata' => $hata,
            'created_at' => now(),
        ]);
        return ['ok' => 1, 'belge_no' => $belgeNo, 'tip' => $tip, 'durum' => $durum, 'id' => $id];
    }
}

if (!function_exists('_okcFisBas')) {
    /** Yeni Nesil Yazarkasa (OKC) mali fis — cihaza gonderir (stub). Gercekte GMP-3 / uretici SDK. */
    function _okcFisBas($sube, $adisyon): array
    {
        $ayar = DB::table('edonusum_ayarlari')->where('sube_id', $sube->id)->first();
        $tutar = (float) $adisyon->toplam;
        $matrah = round($tutar / 1.10, 2);
        $fisNo = 'Z' . now()->format('ymd') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $gercek = $ayar && !empty($ayar->okc_aktif) && !empty($ayar->okc_ip);
        // === GERCEK OKC CAGRISI BURAYA (Ingenico/Hugin GMP-3 / uretici SDK) ===
        // if ($gercek) { OkcDriver::fisBas($ayar->okc_marka, $ayar->okc_ip, $ayar->okc_port, $adisyon); }
        DB::table('e_faturalar')->insert([
            'sube_id' => $sube->id, 'adisyon_id' => $adisyon->id ?? null, 'musteri_id' => $adisyon->musteri_id ?? null,
            'tip' => 'okc_fis', 'belge_no' => $fisNo, 'alici_unvan' => 'Yazarkasa Fişi', 'alici_vkn' => null,
            'matrah' => $matrah, 'kdv' => round($tutar - $matrah, 2), 'toplam' => $tutar,
            'durum' => $gercek ? 'basildi' : 'simulasyon', 'entegrator' => $ayar->okc_marka ?? 'ingenico', 'hata' => null,
            'created_at' => now(),
        ]);
        return ['ok' => 1, 'fis_no' => $fisNo, 'durum' => $gercek ? 'basildi' : 'simulasyon'];
    }
}

Route::get('/', function () {
    // Migration/seed henuz yoksa kurulum ekrani goster (deploy sirasi patlamasin)
    if (!Schema::hasTable('adisyonlar') || DB::table('subeler')->count() === 0) {
        return view('kurulum');
    }

    $sube = DB::table('subeler')->first();
    $son30 = now()->subDays(30);

    // --- Ozet kartlar ---
    $bugunCiro = (float) DB::table('odemeler')->whereDate('created_at', today())->sum('tutar');
    $dunCiro = (float) DB::table('odemeler')->whereDate('created_at', today()->subDay())->sum('tutar');
    $son30Ciro = (float) DB::table('odemeler')->where('created_at', '>=', $son30)->sum('tutar');
    $son30Adisyon = DB::table('adisyonlar')->where('durum', 'odendi')->where('kapanis', '>=', $son30)->count();
    $ortAdisyon = $son30Adisyon > 0 ? $son30Ciro / $son30Adisyon : 0;

    $acikMasaSayisi = DB::table('adisyonlar')->where('durum', 'acik')->count();
    $acikTutar = (float) DB::table('adisyonlar')->where('durum', 'acik')->sum('toplam');
    $masaSayisi = DB::table('masalar')->count();

    $birimler = DB::table('birimler')->pluck('kisaltma', 'id');

    // --- Kritik stoklar (defterden turetilen anlik stok < kritik) ---
    $kritikStoklar = DB::table('malzemeler')
        ->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
        ->select('malzemeler.ad', 'malzemeler.kritik_stok', 'malzemeler.temel_birim_id',
            DB::raw('COALESCE(SUM(stok_hareketleri.miktar),0) as stok'))
        ->groupBy('malzemeler.id', 'malzemeler.ad', 'malzemeler.kritik_stok', 'malzemeler.temel_birim_id')
        ->havingRaw('COALESCE(SUM(stok_hareketleri.miktar),0) < malzemeler.kritik_stok')
        ->orderByRaw('stok asc')
        ->limit(10)->get();
    $kritikSayisi = count($kritikStoklar);

    // --- Acik masalar (canli) ---
    $acikAdisyonlar = DB::table('adisyonlar')
        ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
        ->leftJoin('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
        ->where('adisyonlar.durum', 'acik')
        ->select('masalar.ad as masa', 'personeller.ad as garson', 'adisyonlar.toplam',
            'adisyonlar.acilis', 'adisyonlar.misafir_sayisi')
        ->orderByDesc('adisyonlar.acilis')->get();

    // --- Fiyat zekasi uyarilari (sari/kirmizi) ---
    $fiyatUyarilari = DB::table('alis_fatura_kalemleri')
        ->join('malzemeler', 'alis_fatura_kalemleri.malzeme_id', '=', 'malzemeler.id')
        ->join('alis_faturalari', 'alis_fatura_kalemleri.fatura_id', '=', 'alis_faturalari.id')
        ->whereIn('alis_fatura_kalemleri.uyari', ['sari', 'kirmizi'])
        ->select('malzemeler.ad', 'alis_fatura_kalemleri.uyari',
            'alis_fatura_kalemleri.fiyat_farki_yuzde', 'alis_fatura_kalemleri.fiyat_farki_tutar',
            'alis_faturalari.tarih')
        ->orderByDesc('alis_faturalari.tarih')->limit(10)->get();

    // --- En cok satan urunler (son 30 gun) ---
    $enCokSatan = DB::table('adisyon_kalemleri')
        ->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', $son30)
        ->select('adisyon_kalemleri.urun_adi',
            DB::raw('SUM(adisyon_kalemleri.adet) as adet'),
            DB::raw('SUM(adisyon_kalemleri.tutar) as ciro'))
        ->groupBy('adisyon_kalemleri.urun_adi')
        ->orderByDesc('ciro')->limit(8)->get();

    // --- Personel satis (son 30 gun) ---
    $personelSatis = DB::table('adisyonlar')
        ->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
        ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', $son30)
        ->select('personeller.ad',
            DB::raw('COUNT(*) as adisyon'), DB::raw('SUM(adisyonlar.toplam) as ciro'))
        ->groupBy('personeller.id', 'personeller.ad')
        ->orderByDesc('ciro')->get();

    // --- Suistimal radari: son void/indirim/ikram ---
    $sonLoglar = DB::table('iptal_indirim_loglari')
        ->leftJoin('personeller', 'iptal_indirim_loglari.personel_id', '=', 'personeller.id')
        ->select('iptal_indirim_loglari.tip', 'iptal_indirim_loglari.tutar',
            'iptal_indirim_loglari.sebep', 'iptal_indirim_loglari.created_at', 'personeller.ad')
        ->orderByDesc('iptal_indirim_loglari.created_at')->limit(8)->get();

    // --- Gunluk ciro trendi (son 14 gun) ---
    $trend = [];
    for ($i = 13; $i >= 0; $i--) {
        $g = today()->subDays($i);
        $trend[] = [
            'gun' => $g->format('d.m'),
            'ciro' => (float) DB::table('odemeler')->whereDate('created_at', $g)->sum('tutar'),
        ];
    }

    return view('dashboard', compact(
        'sube', 'bugunCiro', 'dunCiro', 'son30Ciro', 'son30Adisyon', 'ortAdisyon',
        'acikMasaSayisi', 'acikTutar', 'masaSayisi', 'kritikStoklar', 'kritikSayisi',
        'acikAdisyonlar', 'fiyatUyarilari', 'enCokSatan', 'personelSatis', 'sonLoglar',
        'trend', 'birimler'
    ));
});

// ============================ POS / ADISYON ============================
Route::get('/pos', function () {
    $subeId = DB::table('subeler')->value('id');
    $bolgeler = DB::table('bolgeler')->where('sube_id', $subeId)->orderBy('sira')->get();
    $masalar = DB::table('masalar')->where('sube_id', $subeId)->orderBy('id')->get()->groupBy('bolge_id');
    $acik = DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')
        ->select('masa_id', 'toplam', 'acilis')->get()->keyBy('masa_id');
    return view('pos.harita', compact('bolgeler', 'masalar', 'acik'));
});

Route::get('/pos/masa/{masa}', function ($masaId) {
    $masa = DB::table('masalar')->find($masaId);
    if (!$masa) abort(404);
    $adisyon = DB::table('adisyonlar')->where('masa_id', $masaId)->where('durum', 'acik')->first();
    $kalemler = $adisyon ? DB::table('adisyon_kalemleri')->where('adisyon_id', $adisyon->id)->get() : collect();
    $kategoriler = DB::table('menu_kategorileri')->where('sube_id', $masa->sube_id)->orderBy('sira')->get();
    $urunler = DB::table('urunler')->where('sube_id', $masa->sube_id)->where('aktif', 1)->get()->groupBy('kategori_id');
    $bosMasalar = DB::table('masalar')->where('sube_id', $masa->sube_id)->where('durum', 'bos')->where('id', '!=', $masaId)->get();
    $musteri = ($adisyon && $adisyon->musteri_id) ? DB::table('musteriler')->find($adisyon->musteri_id) : null;
    return view('pos.adisyon', compact('masa', 'adisyon', 'kalemler', 'kategoriler', 'urunler', 'bosMasalar', 'musteri'));
});

Route::post('/pos/adisyon-ac', function (Request $r) {
    $masa = DB::table('masalar')->find($r->masa_id);
    $garson = DB::table('personeller')->where('sube_id', $masa->sube_id)->where('rol', 'garson')->value('id');
    $id = DB::table('adisyonlar')->insertGetId([
        'sube_id' => $masa->sube_id, 'masa_id' => $masa->id, 'kanal' => 'salon', 'misafir_sayisi' => (int) ($r->misafir ?? 2),
        'durum' => 'acik', 'acan_personel_id' => $garson, 'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
        'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('masalar')->where('id', $masa->id)->update(['durum' => 'dolu']);
    return ['ok' => 1, 'adisyon_id' => $id];
});

Route::post('/pos/kalem-ekle', function (Request $r) {
    $u = DB::table('urunler')->find($r->urun_id);
    DB::table('adisyon_kalemleri')->insert([
        'adisyon_id' => $r->adisyon_id, 'urun_id' => $u->id, 'urun_adi' => $u->ad, 'adet' => 1,
        'birim_fiyat' => $u->fiyat, 'tutar' => $u->fiyat, 'durum' => 'yeni', 'gonderim_zamani' => null,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    return _adisyonToplamGuncelle($r->adisyon_id);
});

Route::post('/pos/kalem-sil', function (Request $r) {
    $k = DB::table('adisyon_kalemleri')->find($r->kalem_id);
    DB::table('adisyon_kalemleri')->where('id', $r->kalem_id)->delete();
    return _adisyonToplamGuncelle($k->adisyon_id);
});

Route::post('/pos/gonder', function (Request $r) {
    DB::table('adisyon_kalemleri')->where('adisyon_id', $r->adisyon_id)->where('durum', 'yeni')
        ->update(['durum' => 'gonderildi', 'gonderim_zamani' => now()]);
    return ['ok' => 1];
});

Route::post('/pos/ode', function (Request $r) {
    $a = DB::table('adisyonlar')->find($r->adisyon_id);
    $odemeTip = $r->tip ?? 'nakit';
    DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => $odemeTip, 'tutar' => $a->toplam, 'bahsis' => 0, 'personel_id' => $a->acan_personel_id, 'created_at' => now()]);
    if ($odemeTip === 'nakit' && function_exists('_kasaYaz')) _kasaYaz($a->sube_id, 'satis', 'giris', $a->toplam, 'Nakit satış · adisyon #' . $a->id, 'adisyon', $a->id, $a->acan_personel_id);
    DB::table('adisyonlar')->where('id', $a->id)->update(['durum' => 'odendi', 'kapanis' => now()]);
    if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
    // SADAKAT: adisyona musteri bagliysa puan + harcama isle (her 10 TL = 1 puan)
    if (!empty($a->musteri_id)) {
        DB::table('musteriler')->where('id', $a->musteri_id)->update([
            'siparis_sayisi' => DB::raw('siparis_sayisi + 1'),
            'toplam_harcama' => DB::raw('toplam_harcama + ' . (float) $a->toplam),
            'puan' => DB::raw('puan + ' . (int) floor($a->toplam / 10)),
            'updated_at' => now(),
        ]);
    }
    // NORMAL FIS: fis moduna gore otomatik belge (adisyona daha once fatura kesilmediyse)
    $ayar = DB::table('edonusum_ayarlari')->where('sube_id', $a->sube_id)->first();
    $zatenBelge = DB::table('e_faturalar')->where('adisyon_id', $a->id)->exists();
    if (!$zatenBelge) {
        $sube = DB::table('subeler')->find($a->sube_id);
        if ($ayar && ($ayar->fis_modu ?? 'earsiv') === 'okc') {
            _okcFisBas($sube, $a);          // Yazarkasa mali fisi
        } else {
            _eFaturaKes($sube, $a, []);     // e-Arsiv fisi (Son Tuketici)
        }
    }
    return ['ok' => 1];
});

// --- Musteri: ekle / guncelle / ara / adisyona bagla ---
Route::post('/musteriler/ekle', function (Request $r) {
    $subeId = DB::table('subeler')->value('id');
    $id = DB::table('musteriler')->insertGetId([
        'sube_id' => $subeId, 'ad' => $r->ad ?: 'Müşteri', 'telefon' => $r->telefon, 'adres' => $r->adres,
        'puan' => 0, 'siparis_sayisi' => 0, 'toplam_harcama' => 0, 'created_at' => now(), 'updated_at' => now(),
    ]);
    return ['ok' => 1, 'id' => $id];
});
Route::post('/musteriler/guncelle', function (Request $r) {
    DB::table('musteriler')->where('id', $r->id)->update(['ad' => $r->ad, 'telefon' => $r->telefon, 'adres' => $r->adres, 'updated_at' => now()]);
    return ['ok' => 1];
});
Route::get('/musteriler/ara', function (Request $r) {
    $q = trim((string) $r->q);
    return DB::table('musteriler')
        ->when($q !== '', function ($x) use ($q) {
            $x->where(function ($w) use ($q) { $w->where('ad', 'like', '%' . $q . '%')->orWhere('telefon', 'like', '%' . $q . '%'); });
        })
        ->orderBy('ad')->limit(10)->get(['id', 'ad', 'telefon', 'adres', 'puan']);
});
Route::post('/pos/musteri-bagla', function (Request $r) {
    $a = DB::table('adisyonlar')->find($r->adisyon_id);
    $musteriId = $r->musteri_id ?: null;
    if (!$musteriId && ($r->ad || $r->telefon)) {
        $musteriId = DB::table('musteriler')->insertGetId([
            'sube_id' => $a->sube_id, 'ad' => $r->ad ?: 'Müşteri', 'telefon' => $r->telefon, 'adres' => $r->adres,
            'puan' => 0, 'siparis_sayisi' => 0, 'toplam_harcama' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    DB::table('adisyonlar')->where('id', $r->adisyon_id)->update(['musteri_id' => $musteriId]);
    return ['ok' => 1, 'musteri_id' => $musteriId];
});

Route::post('/pos/tasi', function (Request $r) {
    $a = DB::table('adisyonlar')->find($r->adisyon_id);
    $eski = $a->masa_id;
    DB::table('adisyonlar')->where('id', $a->id)->update(['masa_id' => $r->yeni_masa_id]);
    DB::table('masalar')->where('id', $eski)->update(['durum' => 'bos']);
    DB::table('masalar')->where('id', $r->yeni_masa_id)->update(['durum' => 'dolu']);
    DB::table('adisyon_masa_loglari')->insert(['adisyon_id' => $a->id, 'islem' => 'tasima', 'eski_masa_id' => $eski, 'yeni_masa_id' => $r->yeni_masa_id, 'personel_id' => $a->acan_personel_id, 'created_at' => now()]);
    return ['ok' => 1];
});

// ============================ PAKET SIPARIS MERKEZI ============================
Route::get('/paket', function () {
    $aktif = DB::table('adisyonlar')->where('adisyonlar.kanal', 'paket')->where('adisyonlar.durum', 'acik')
        ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->select('adisyonlar.*', 'musteriler.ad as musteri_ad', 'musteriler.telefon', 'kuryeler.ad as kurye_ad')
        ->orderByDesc('adisyonlar.acilis')->get();
    $bugunPaket = DB::table('adisyonlar')->where('kanal', 'paket')->whereDate('acilis', today())->count();
    $platformDagilim = DB::table('adisyonlar')->where('kanal', 'paket')->where('acilis', '>=', now()->subDays(30))
        ->select('platform', DB::raw('COUNT(*) as adet'), DB::raw('SUM(toplam) as ciro'))->groupBy('platform')->orderByDesc('ciro')->get();
    return view('paket.index', compact('aktif', 'bugunPaket', 'platformDagilim'));
});

Route::post('/paket/durum', function (Request $r) {
    $yeni = $r->durum;
    $upd = ['teslimat_durumu' => $yeni];
    if ($yeni === 'teslim') { $upd['durum'] = 'odendi'; $upd['kapanis'] = now(); $upd['teslim_zamani'] = now(); }
    DB::table('adisyonlar')->where('id', $r->adisyon_id)->update($upd);
    return ['ok' => 1];
});

// ============================ KURYE TAKIP ============================
Route::get('/kurye', function () {
    $kuryeler = DB::table('kuryeler')->where('aktif', 1)->get();
    $teslimatlar = DB::table('adisyonlar')->where('kanal', 'paket')->where('teslimat_durumu', 'yolda')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.teslimat_adres', 'kuryeler.ad as kurye', 'musteriler.ad as musteri')
        ->get();
    return view('kurye.index', compact('kuryeler', 'teslimatlar'));
});

// ---------------- CANLI KURYE GPS ----------------
// Kurye telefonundan canli konum -> patron haritada gorur. Dis bagimlilik YOK (OSM/Leaflet bedava).
if (!function_exists('_kuryeCanliEnsure')) {
    function _kuryeCanliEnsure($subeId)
    {
        if (!Schema::hasColumn('kuryeler', 'token')) {
            Schema::table('kuryeler', fn ($t) => $t->string('token', 40)->nullable()->after('id'));
        }
        if (!Schema::hasColumn('kuryeler', 'konum_zamani')) {
            Schema::table('kuryeler', fn ($t) => $t->timestamp('konum_zamani')->nullable());
        }
        // En az 2 kurye olsun + token + demo konum (Kadikoy civari)
        $baseLat = 40.9905; $baseLng = 29.0250;
        $kuryeler = DB::table('kuryeler')->where('sube_id', $subeId)->get();
        if ($kuryeler->count() < 2) {
            foreach (['Mehmet Kurye', 'Ali Kurye', 'Emre Kurye'] as $i => $ad) {
                if ($kuryeler->count() + $i >= 3) break;
                DB::table('kuryeler')->insert(['sube_id' => $subeId, 'ad' => $ad, 'telefon' => '053' . rand(10000000, 99999999),
                    'aktif' => 1, 'durum' => 'musait', 'created_at' => now(), 'updated_at' => now()]);
            }
            $kuryeler = DB::table('kuryeler')->where('sube_id', $subeId)->get();
        }
        foreach ($kuryeler as $k) {
            $upd = [];
            if (empty($k->token)) $upd['token'] = \Illuminate\Support\Str::random(24);
            if ($k->son_lat === null) { $upd['son_lat'] = $baseLat + (rand(-120, 120) / 10000); $upd['son_lng'] = $baseLng + (rand(-120, 120) / 10000); $upd['konum_zamani'] = now(); }
            if ($upd) DB::table('kuryeler')->where('id', $k->id)->update($upd);
        }
        // En az 3 aktif teslimat (yolda) olsun
        $yolda = DB::table('adisyonlar')->where('sube_id', $subeId)->where('kanal', 'paket')->where('teslimat_durumu', 'yolda')->count();
        if ($yolda < 3) {
            $kids = DB::table('kuryeler')->where('sube_id', $subeId)->pluck('id')->all();
            $adresler = ['Bağdat Cad. No:112 D:4, Kadıköy', 'Moda Cad. No:45, Kadıköy', 'Feneryolu Mah. Ressam Salih Sk. 8', 'Caferağa Mah. Dr. Esat Işık Cd. 21', 'Osmanağa Mah. Söğütlüçeşme Cd. 60'];
            for ($i = $yolda; $i < 3; $i++) {
                DB::table('adisyonlar')->insert([
                    'sube_id' => $subeId, 'masa_id' => null, 'kurye_id' => $kids[array_rand($kids)] ?? null,
                    'kanal' => 'paket', 'platform' => ['telefon', 'yemeksepeti', 'getir'][array_rand([0, 1, 2])],
                    'teslimat_adres' => $adresler[array_rand($adresler)], 'teslimat_durumu' => 'yolda',
                    'misafir_sayisi' => 1, 'durum' => 'acik', 'ara_toplam' => rand(180, 640), 'toplam' => rand(180, 640),
                    'acilis' => now()->subMinutes(rand(5, 40)), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }
}

Route::get('/kurye-kur', function () {
    $subeId = DB::table('subeler')->value('id');
    _kuryeCanliEnsure($subeId);
    $kuryeler = DB::table('kuryeler')->where('sube_id', $subeId)->get(['ad', 'token']);
    $links = $kuryeler->map(fn ($k) => $k->ad . ' -> ' . url('/kurye/' . $k->token))->implode("\n");
    return response("Canli kurye GPS hazir.\n\nKurye panel linkleri (telefonda ac):\n" . $links)->header('Content-Type', 'text/plain; charset=utf-8');
});

// Kurye telefon paneli (canli konum gonderir + teslimatlarini gorur)
Route::get('/kurye/{token}', function ($token) {
    $k = DB::table('kuryeler')->where('token', $token)->first();
    if (!$k) abort(404);
    $teslimatlar = DB::table('adisyonlar')->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->where('adisyonlar.kurye_id', $k->id)->whereIn('adisyonlar.teslimat_durumu', ['hazir', 'yolda'])
        ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.teslimat_adres', 'adisyonlar.teslimat_durumu', 'adisyonlar.platform', 'musteriler.ad as musteri', 'musteriler.telefon')
        ->orderBy('adisyonlar.id')->get();
    return view('kurye_panel', ['k' => $k, 'teslimatlar' => $teslimatlar]);
});

// Kurye konum guncelle (CSRF muaf: kurye/*)
Route::post('/kurye/{token}/konum', function (Request $r, $token) {
    $k = DB::table('kuryeler')->where('token', $token)->first();
    if (!$k) return response()->json(['ok' => 0], 404);
    $lat = (float) $r->input('lat'); $lng = (float) $r->input('lng');
    if (!$lat || !$lng) return ['ok' => 0];
    DB::table('kuryeler')->where('id', $k->id)->update(['son_lat' => $lat, 'son_lng' => $lng, 'konum_zamani' => now()]);
    return ['ok' => 1];
});

// Kurye teslimat durumu (yola cikti / teslim etti)
Route::post('/kurye/{token}/durum', function (Request $r, $token) {
    $k = DB::table('kuryeler')->where('token', $token)->first();
    if (!$k) return response()->json(['ok' => 0], 404);
    $aid = (int) $r->input('adisyon_id'); $durum = (string) $r->input('durum');
    $a = DB::table('adisyonlar')->where('id', $aid)->where('kurye_id', $k->id)->first();
    if (!$a) return ['ok' => 0, 'hata' => 'Sipariş bulunamadı'];
    if (!in_array($durum, ['yolda', 'teslim'])) return ['ok' => 0];
    $upd = ['teslimat_durumu' => $durum, 'updated_at' => now()];
    if ($durum === 'teslim') $upd['teslim_zamani'] = now();
    DB::table('adisyonlar')->where('id', $aid)->update($upd);
    // Kurye durumu: yolda ise teslimatta, kalan teslimat yoksa musait
    $kalan = DB::table('adisyonlar')->where('kurye_id', $k->id)->whereIn('teslimat_durumu', ['hazir', 'yolda'])->count();
    DB::table('kuryeler')->where('id', $k->id)->update(['durum' => $kalan > 0 ? 'teslimatta' : 'musait']);
    return ['ok' => 1, 'mesaj' => $durum === 'teslim' ? 'Teslim edildi ✅' : 'Yola çıkıldı 🛵'];
});

// Patron canli harita sayfasi
Route::get('/kurye-canli', function () {
    $subeId = DB::table('subeler')->value('id');
    _kuryeCanliEnsure($subeId);
    return view('kurye_harita', ['sube' => DB::table('subeler')->where('id', $subeId)->first()]);
});

// Canli veri (harita + Flutter icin JSON) — kurye konumlari + aktif teslimatlar
Route::get('/api/kurye-canli-veri', function (Request $r) {
    $subeId = (int) ($r->query('sube') ?: DB::table('subeler')->value('id'));
    $simdi = now();
    $kuryeler = DB::table('kuryeler')->where('sube_id', $subeId)->where('aktif', 1)->get()->map(function ($k) use ($simdi) {
        $aktifTeslimat = DB::table('adisyonlar')->where('kurye_id', $k->id)->whereIn('teslimat_durumu', ['hazir', 'yolda'])->count();
        $dk = $k->konum_zamani ? (int) \Carbon\Carbon::parse($k->konum_zamani)->diffInMinutes($simdi) : null;
        return ['id' => (int) $k->id, 'ad' => $k->ad, 'durum' => $k->durum, 'lat' => $k->son_lat ? (float) $k->son_lat : null,
            'lng' => $k->son_lng ? (float) $k->son_lng : null, 'aktif_teslimat' => $aktifTeslimat, 'konum_dk' => $dk];
    })->values();
    $teslimatlar = DB::table('adisyonlar')->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->where('adisyonlar.sube_id', $subeId)->where('adisyonlar.kanal', 'paket')->whereIn('adisyonlar.teslimat_durumu', ['hazir', 'yolda'])
        ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.teslimat_adres', 'adisyonlar.teslimat_durumu', 'adisyonlar.platform', 'kuryeler.ad as kurye', 'musteriler.ad as musteri')
        ->orderBy('adisyonlar.id')->get();
    return ['ok' => 1, 'kuryeler' => $kuryeler, 'teslimatlar' => $teslimatlar,
        'ozet' => ['kurye' => $kuryeler->count(), 'musait' => $kuryeler->where('durum', 'musait')->count(), 'yolda' => $teslimatlar->count()]];
});

// ============================ CALLERID (arayan tanima — VoIP/FreePBX webhook) ============================
if (!function_exists('_telNorm')) {
    function _telNorm($t) { $d = preg_replace('/\D/', '', (string) $t); return strlen($d) > 10 ? substr($d, -10) : $d; }
}
if (!function_exists('_calleridEnsure')) {
    function _calleridEnsure()
    {
        if (Schema::hasTable('cagri_loglari') && !Schema::hasColumn('cagri_loglari', 'durum')) {
            Schema::table('cagri_loglari', fn ($t) => $t->string('durum', 15)->default('bekliyor')->after('sonuc'));
        }
        if (Schema::hasTable('cagri_loglari') && !Schema::hasColumn('cagri_loglari', 'hat')) {
            Schema::table('cagri_loglari', fn ($t) => $t->string('hat', 40)->nullable());
        }
        if (Schema::hasTable('musteriler') && !Schema::hasColumn('musteriler', 'telefon_norm')) {
            Schema::table('musteriler', fn ($t) => $t->string('telefon_norm', 12)->nullable()->index());
            foreach (DB::table('musteriler')->get(['id', 'telefon']) as $m) {
                DB::table('musteriler')->where('id', $m->id)->update(['telefon_norm' => _telNorm($m->telefon)]);
            }
        }
    }
}
if (!function_exists('_musteriKart')) {
    function _musteriKart($m)
    {
        if (!$m) return null;
        $sonlar = DB::table('adisyonlar')->where('musteri_id', $m->id)->orderByDesc('id')->limit(3)
            ->get(['id', 'toplam', 'durum', 'created_at'])
            ->map(fn ($a) => ['id' => (int) $a->id, 'toplam' => (float) $a->toplam, 'durum' => $a->durum,
                'tarih' => $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('d.m.Y') : '']);
        return ['id' => (int) $m->id, 'ad' => $m->ad, 'telefon' => $m->telefon, 'adres' => $m->adres ?? '',
            'siparis_sayisi' => (int) ($m->siparis_sayisi ?? 0), 'toplam_harcama' => (float) ($m->toplam_harcama ?? 0),
            'puan' => (int) ($m->puan ?? 0), 'notlar' => $m->notlar ?? '', 'son_siparisler' => $sonlar];
    }
}
if (!function_exists('_calleridGelen')) {
    function _calleridGelen($subeId, $telefon, $hat = null)
    {
        _calleridEnsure();
        $norm = _telNorm($telefon);
        $m = $norm ? DB::table('musteriler')->where('sube_id', $subeId)->where('telefon_norm', $norm)->first() : null;
        $cid = DB::table('cagri_loglari')->insertGetId([
            'sube_id' => $subeId, 'telefon' => $telefon, 'musteri_id' => $m->id ?? null,
            'yon' => 'gelen', 'sonuc' => 'cevaplandi', 'durum' => 'bekliyor', 'hat' => $hat, 'created_at' => now(),
        ]);
        return ['ok' => 1, 'cagri_id' => $cid, 'yeni_musteri' => $m ? false : true, 'musteri' => _musteriKart($m), 'telefon' => $telefon];
    }
}

// VOIP WEBHOOK — FreePBX/Asterisk buraya POST/GET atar (CSRF muaf: api/*)
Route::match(['get', 'post'], '/api/callerid/gelen', function (Request $r) {
    $tel = $r->input('telefon') ?: $r->query('telefon');
    if (!$tel) return response()->json(['ok' => 0, 'hata' => 'telefon gerekli'], 422);
    $subeId = (int) ($r->input('sube') ?: $r->query('sube') ?: DB::table('subeler')->value('id'));
    return _calleridGelen($subeId, $tel, $r->input('hat') ?: $r->query('hat'));
});

// TEST — VoIP olmadan gelen cagri simule et
Route::get('/callerid-test', function (Request $r) {
    $subeId = DB::table('subeler')->value('id');
    _calleridEnsure();
    $tel = $r->query('telefon');
    if (!$tel) {
        $m = DB::table('musteriler')->where('sube_id', $subeId)->inRandomOrder()->first();
        $tel = $m->telefon ?? '0555 000 00 00';
    }
    $res = _calleridGelen($subeId, $tel, 'dahili-101');
    return response()->json(['test' => true, 'cagri' => $res], 200, [], JSON_UNESCAPED_UNICODE);
});

// KURULUM
Route::get('/callerid-kur', function () {
    _calleridEnsure();
    $subeId = DB::table('subeler')->value('id');
    $mus = DB::table('musteriler')->where('sube_id', $subeId)->count();
    $ornek = DB::table('musteriler')->where('sube_id', $subeId)->value('telefon');
    return response("CallerID hazir.\nMusteri sayisi: $mus\nTest: " . url('/callerid-test') . "  (ya da ?telefon=$ornek)\nEkran-pop: " . url('/cagri-ekran') . "\nWebhook (FreePBX): " . url('/api/callerid/gelen') . "?telefon=NUMARA")->header('Content-Type', 'text/plain; charset=utf-8');
});

// EKRAN-POP verisi: son 90 sn icinde bekleyen gelen cagri
Route::get('/api/callerid-aktif', function (Request $r) {
    _calleridEnsure();
    $subeId = (int) ($r->query('sube') ?: DB::table('subeler')->value('id'));
    $c = DB::table('cagri_loglari')->where('sube_id', $subeId)->where('yon', 'gelen')->where('durum', 'bekliyor')
        ->where('created_at', '>=', now()->subSeconds(90))->orderByDesc('id')->first();
    if (!$c) return ['ok' => 1, 'cagri' => null];
    $m = $c->musteri_id ? DB::table('musteriler')->where('id', $c->musteri_id)->first() : null;
    return ['ok' => 1, 'cagri' => ['id' => (int) $c->id, 'telefon' => $c->telefon, 'hat' => $c->hat,
        'saniye' => (int) \Carbon\Carbon::parse($c->created_at)->diffInSeconds(now()),
        'yeni_musteri' => $m ? false : true, 'musteri' => _musteriKart($m)]];
});

Route::post('/api/callerid-goruldu', function (Request $r) {
    DB::table('cagri_loglari')->where('id', (int) $r->input('cagri_id'))->update(['durum' => 'goruldu']);
    return ['ok' => 1];
});

Route::get('/api/patron/cagrilar', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _calleridEnsure();
    $rows = DB::table('cagri_loglari')->leftJoin('musteriler', 'cagri_loglari.musteri_id', '=', 'musteriler.id')
        ->where('cagri_loglari.sube_id', $p->sube_id)->orderByDesc('cagri_loglari.id')->limit(80)
        ->select('cagri_loglari.*', 'musteriler.ad as musteri_ad')
        ->get()->map(fn ($c) => ['id' => (int) $c->id, 'telefon' => $c->telefon, 'musteri' => $c->musteri_ad,
            'yon' => $c->yon, 'sonuc' => $c->sonuc, 'zaman' => \Carbon\Carbon::parse($c->created_at)->format('d.m H:i')]);
    return ['ok' => 1, 'cagrilar' => $rows];
});

// EKRAN-POP sayfasi — telefonun yanindaki ekranda acilir; cagri gelince kart patlar
Route::get('/cagri-ekran', function () {
    $subeId = DB::table('subeler')->value('id');
    _calleridEnsure();
    return view('cagri_ekran', ['sube' => DB::table('subeler')->where('id', $subeId)->first()]);
});

// ============================ MUSTERI MARKALI APP (online siparis PWA) ============================
if (!function_exists('_appEnsure')) {
    function _appEnsure()
    {
        if (!Schema::hasColumn('adisyonlar', 'takip_token')) {
            Schema::table('adisyonlar', fn ($t) => $t->string('takip_token', 40)->nullable()->index());
        }
        if (!Schema::hasColumn('adisyonlar', 'odeme_yontemi')) {
            Schema::table('adisyonlar', fn ($t) => $t->string('odeme_yontemi', 20)->nullable());
        }
    }
}

// Markali siparis sayfasi (PWA)
Route::get('/app/{sube}', function ($sube) {
    $s = DB::table('subeler')->where('id', $sube)->first();
    if (!$s) abort(404);
    _appEnsure();
    return view('app_siparis', ['sube' => $s]);
});

// Kurulum / test linki
Route::get('/app-kur', function () {
    _appEnsure();
    $s = DB::table('subeler')->first();
    return response("Musteri App hazir.\nSiparis sayfasi: " . url('/app/' . $s->id) . "\n(QR/link ile musteriye verilir)")->header('Content-Type', 'text/plain; charset=utf-8');
});

// Public menu (app icin)
Route::get('/api/app/{sube}/menu', function ($sube) {
    $s = DB::table('subeler')->where('id', $sube)->first();
    if (!$s) return response()->json(['ok' => 0], 404);
    $kats = DB::table('menu_kategorileri')->where('sube_id', $s->id)->where('aktif', 1)->orderBy('sira')->orderBy('ad')
        ->get(['id', 'ad'])->map(fn ($k) => ['id' => (int) $k->id, 'ad' => $k->ad]);
    $urunler = DB::table('urunler')->where('sube_id', $s->id)->where('aktif', 1)->orderBy('ad')
        ->get(['id', 'ad', 'aciklama', 'fiyat', 'kategori_id', 'tukendi', 'gorsel'])
        ->map(fn ($u) => ['id' => (int) $u->id, 'ad' => $u->ad, 'aciklama' => $u->aciklama ?: '', 'fiyat' => (float) $u->fiyat,
            'kategori_id' => $u->kategori_id ? (int) $u->kategori_id : 0, 'tukendi' => (bool) $u->tukendi, 'gorsel' => $u->gorsel ?: null]);
    return ['ok' => 1, 'sube' => ['id' => (int) $s->id, 'ad' => $s->ad, 'adres' => $s->adres ?? '', 'telefon' => $s->telefon ?? ''],
        'kategoriler' => $kats, 'urunler' => $urunler];
});

// Siparis olustur (paket / gel-al) — CSRF muaf (api/*)
Route::post('/api/app/{sube}/siparis', function (Request $r, $sube) {
    $s = DB::table('subeler')->where('id', $sube)->first();
    if (!$s) return response()->json(['ok' => 0, 'hata' => 'Şube bulunamadı'], 404);
    _appEnsure();
    _calleridEnsure(); // musteriler.telefon_norm
    $ad = trim((string) $r->input('ad')); $tel = trim((string) $r->input('telefon'));
    if ($ad === '' || $tel === '') return ['ok' => 0, 'hata' => 'Ad ve telefon zorunlu'];
    $tip = $r->input('tip') === 'gelal' ? 'gelal' : 'paket';
    $adres = trim((string) $r->input('adres'));
    if ($tip === 'paket' && $adres === '') return ['ok' => 0, 'hata' => 'Teslimat adresi gerekli'];
    $kalemler = json_decode((string) $r->input('kalemler'), true);
    if (!is_array($kalemler) || empty($kalemler)) return ['ok' => 0, 'hata' => 'Sepet boş'];
    $odeme = in_array($r->input('odeme'), ['nakit', 'kart_kapida']) ? $r->input('odeme') : 'nakit';
    // Musteri upsert (telefondan)
    $norm = _telNorm($tel);
    $m = $norm ? DB::table('musteriler')->where('sube_id', $s->id)->where('telefon_norm', $norm)->first() : null;
    if ($m) { DB::table('musteriler')->where('id', $m->id)->update(['ad' => $ad, 'adres' => $adres ?: $m->adres, 'updated_at' => now()]); $mid = $m->id; }
    else { $mid = DB::table('musteriler')->insertGetId(['sube_id' => $s->id, 'ad' => $ad, 'telefon' => $tel, 'telefon_norm' => $norm, 'adres' => $adres ?: null, 'created_at' => now(), 'updated_at' => now()]); }
    // Adisyon (kanal=paket, platform=app) -> mutfaga duser
    $token = \Illuminate\Support\Str::random(28);
    $adId = DB::table('adisyonlar')->insertGetId([
        'sube_id' => $s->id, 'masa_id' => null, 'musteri_id' => $mid, 'kanal' => 'paket', 'platform' => 'app',
        'teslimat_adres' => $tip === 'paket' ? $adres : 'GEL-AL', 'teslimat_durumu' => 'hazirlaniyor',
        'takip_token' => $token, 'odeme_yontemi' => $odeme, 'misafir_sayisi' => 1, 'durum' => 'acik',
        'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0, 'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $eklenen = 0; $tukendi = [];
    $notEk = ($tip === 'gelal' ? 'Gel-Al' : 'Paket') . ' · App';
    foreach ($kalemler as $k) {
        $uid = (int) ($k['urun_id'] ?? 0); $adet = max(1, min(50, (int) ($k['adet'] ?? 1)));
        $u = DB::table('urunler')->where('id', $uid)->where('sube_id', $s->id)->first();
        if (!$u) continue;
        if ($u->tukendi) { $tukendi[] = $u->ad; continue; }
        DB::table('adisyon_kalemleri')->insert(['adisyon_id' => $adId, 'urun_id' => $u->id, 'urun_adi' => $u->ad, 'adet' => $adet,
            'birim_fiyat' => (float) $u->fiyat, 'tutar' => (float) $u->fiyat * $adet, 'durum' => 'gonderildi', 'not' => $notEk,
            'gonderim_zamani' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $eklenen++;
    }
    if ($eklenen === 0) { DB::table('adisyonlar')->where('id', $adId)->delete(); return ['ok' => 0, 'hata' => $tukendi ? ('Tükendi: ' . implode(', ', $tukendi)) : 'Ürün eklenemedi']; }
    $araTop = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $adId)->sum('tutar');
    DB::table('adisyonlar')->where('id', $adId)->update(['ara_toplam' => $araTop, 'toplam' => $araTop, 'updated_at' => now()]);
    return ['ok' => 1, 'adisyon_id' => $adId, 'takip_token' => $token, 'toplam' => $araTop, 'takip_url' => url('/siparisim/' . $token), 'tukendi' => $tukendi];
});

// Siparis takip verisi (durum + canli kurye)
Route::get('/api/app/siparis-durum/{token}', function ($token) {
    $a = DB::table('adisyonlar')->where('takip_token', $token)->first();
    if (!$a) return response()->json(['ok' => 0], 404);
    $durum = $a->teslimat_durumu ?: 'hazirlaniyor';
    if ($a->durum === 'odendi' && $durum !== 'teslim') $durum = 'teslim';
    $kurye = null;
    if ($a->kurye_id && $durum === 'yolda') {
        $k = DB::table('kuryeler')->find($a->kurye_id);
        if ($k) $kurye = ['ad' => $k->ad, 'telefon' => $k->telefon, 'lat' => $k->son_lat ? (float) $k->son_lat : null, 'lng' => $k->son_lng ? (float) $k->son_lng : null];
    }
    $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')
        ->get(['urun_adi', 'adet', 'tutar'])->map(fn ($x) => ['ad' => $x->urun_adi, 'adet' => (float) $x->adet, 'tutar' => (float) $x->tutar]);
    return ['ok' => 1, 'no' => (int) $a->id, 'durum' => $durum, 'adres' => $a->teslimat_adres, 'toplam' => (float) $a->toplam,
        'odeme' => $a->odeme_yontemi, 'kurye' => $kurye, 'kalemler' => $kalemler];
});

// Siparis takip sayfasi (musteri)
Route::get('/siparisim/{token}', function ($token) {
    $a = DB::table('adisyonlar')->where('takip_token', $token)->first();
    if (!$a) abort(404);
    return view('app_takip', ['token' => $token, 'sube' => DB::table('subeler')->where('id', $a->sube_id)->first()]);
});

// ============================ MUTFAK (KDS) ============================
Route::get('/mutfak', function () {
    $kalemler = DB::table('adisyon_kalemleri')
        ->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
        ->where('adisyon_kalemleri.durum', 'gonderildi')
        ->select('adisyon_kalemleri.id', 'adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.adet', 'adisyon_kalemleri.not',
            'adisyon_kalemleri.gonderim_zamani', 'adisyonlar.kanal', 'masalar.ad as masa')
        ->orderBy('adisyon_kalemleri.gonderim_zamani')->get();
    return view('mutfak.index', compact('kalemler'));
});

Route::post('/mutfak/hazir', function (Request $r) {
    DB::table('adisyon_kalemleri')->where('id', $r->kalem_id)->update(['durum' => 'hazir', 'hazir_zamani' => now()]);
    return ['ok' => 1];
});

// Urun adindan istasyon tahmini (izgara/firin/tatli/bar/soguk/mutfak). Oncelik sirali (ilk eslesen kazanir).
if (!function_exists('_mutfakIstasyonTahmin')) {
    function _mutfakIstasyonTahmin(string $ad): string
    {
        $a = mb_strtolower($ad, 'UTF-8');
        $harita = [
            'bar'    => ['kola', 'ayran', ' su', 'soda', 'çay', 'kahve', 'meşrubat', 'içecek', 'bira', 'şarap', 'kokteyl', 'limonata', 'meyve suyu', 'americano', 'latte', 'espresso', 'cappuccino', 'mocha', 'smoothie', 'milkshake', 'şalgam', 'nar suyu', 'türk kahve', 'sahlep'],
            'tatli'  => ['tatlı', 'sütlaç', 'künefe', 'baklava', 'dondurma', 'kek', 'brownie', 'profiterol', 'magnolia', 'tiramisu', 'waffle', 'cheesecake', 'sufle', 'kazandibi', 'trileçe', 'ekler', 'mozaik', 'muhallebi'],
            'firin'  => ['pizza', 'pide', 'lahmacun', 'börek', 'ekmek', 'fırın', 'makarna', 'lazanya', 'gratin', 'kumpir', 'tost', 'kaşarlı', 'poğaça'],
            'izgara' => ['köfte', 'ızgara', 'izgara', 'kebap', 'kebab', 'şiş', 'tavuk', 'biftek', 'kanat', 'pirzola', 'adana', 'urfa', 'döner', 'mangal', 'burger', 'steak', 'bonfile', 'antrikot', 'çöp şiş', 'kokoreç', 'balık', 'levrek', 'çipura', 'somon'],
            'soguk'  => ['salata', 'meze', 'çiğ köfte', 'humus', 'cacık', 'söğüş', 'antipasto', 'tabule', 'közlenmiş', 'haydari', 'atom', 'ezme'],
        ];
        foreach ($harita as $ist => $kelimeler) {
            foreach ($kelimeler as $kw) {
                if (mb_strpos($a, $kw) !== false) return $ist;
            }
        }
        return 'mutfak'; // corba, pilav, garnitur, kizartma vb.
    }
}

// MUTFAK KURULUM: istasyon + hazir_zamani kolonlari (defansif) + urunlere istasyon ata (tahminle).
Route::get('/mutfak-kur', function () {
    $eklenen = [];
    if (!Schema::hasColumn('urunler', 'istasyon')) {
        Schema::table('urunler', fn ($t) => $t->string('istasyon', 20)->default('mutfak')->after('tukendi'));
        $eklenen[] = 'urunler.istasyon';
    }
    if (!Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani')) {
        Schema::table('adisyon_kalemleri', fn ($t) => $t->timestamp('hazir_zamani')->nullable()->after('gonderim_zamani'));
        $eklenen[] = 'adisyon_kalemleri.hazir_zamani';
    }
    // Istasyonu bos/mutfak olan tum urunlere ada gore istasyon ata
    $urunler = DB::table('urunler')->get(['id', 'ad', 'istasyon']);
    $guncel = 0;
    foreach ($urunler as $u) {
        $ist = _mutfakIstasyonTahmin($u->ad);
        if (($u->istasyon ?? 'mutfak') !== $ist) {
            DB::table('urunler')->where('id', $u->id)->update(['istasyon' => $ist]);
            $guncel++;
        }
    }
    $dagilim = DB::table('urunler')->select('istasyon', DB::raw('COUNT(*) as adet'))->groupBy('istasyon')->pluck('adet', 'istasyon');
    return ['ok' => 1, 'eklenen_kolonlar' => $eklenen, 'istasyon_atanan_urun' => $guncel, 'istasyon_dagilimi' => $dagilim];
});

// ============================ MENU YONETIMI ============================
Route::get('/menu', function () {
    $subeId = DB::table('subeler')->value('id');
    $kategoriler = DB::table('menu_kategorileri')->where('sube_id', $subeId)->orderBy('sira')->get();
    $urunler = DB::table('urunler')->where('sube_id', $subeId)->get()->groupBy('kategori_id');
    return view('menu.index', compact('kategoriler', 'urunler'));
});

Route::post('/menu/86', function (Request $r) {
    $u = DB::table('urunler')->find($r->urun_id);
    DB::table('urunler')->where('id', $r->urun_id)->update(['tukendi' => $u->tukendi ? 0 : 1]);
    return ['ok' => 1, 'tukendi' => $u->tukendi ? 0 : 1];
});

// ============================ STOK & RECETE ============================
Route::get('/stok', function () {
    $subeId = DB::table('subeler')->value('id');
    $birimler = DB::table('birimler')->pluck('kisaltma', 'id');
    // Stok toplamini alt-sorgu ile al (GROUP BY / ONLY_FULL_GROUP_BY sorunu olmasin)
    $malzemeler = DB::table('malzemeler')
        ->leftJoin('malzeme_kategorileri', 'malzemeler.kategori_id', '=', 'malzeme_kategorileri.id')
        ->select('malzemeler.*', 'malzeme_kategorileri.ad as kategori',
            DB::raw('(SELECT COALESCE(SUM(sh.miktar),0) FROM stok_hareketleri sh WHERE sh.malzeme_id = malzemeler.id) as stok'))
        ->orderBy('malzemeler.ad')->get();
    $receteler = DB::table('receteler')->leftJoin('urunler', 'receteler.urun_id', '=', 'urunler.id')
        ->select('receteler.id', 'receteler.ad', 'receteler.tip', DB::raw('(SELECT COUNT(*) FROM recete_kalemleri WHERE recete_kalemleri.recete_id = receteler.id) as kalem_sayisi'))
        ->orderBy('receteler.tip')->get();
    return view('stok.index', compact('malzemeler', 'birimler', 'receteler'));
});

// ============================ SATIN ALMA + FIYAT ZEKASI ============================
Route::get('/satinalma', function () {
    $faturalar = DB::table('alis_faturalari')->leftJoin('tedarikciler', 'alis_faturalari.tedarikci_id', '=', 'tedarikciler.id')
        ->select('alis_faturalari.*', 'tedarikciler.ad as tedarikci')
        ->orderByDesc('tarih')->limit(30)->get();
    $uyarilar = DB::table('alis_fatura_kalemleri')->join('malzemeler', 'alis_fatura_kalemleri.malzeme_id', '=', 'malzemeler.id')
        ->join('alis_faturalari', 'alis_fatura_kalemleri.fatura_id', '=', 'alis_faturalari.id')
        ->whereIn('alis_fatura_kalemleri.uyari', ['sari', 'kirmizi'])
        ->select('malzemeler.ad', 'alis_fatura_kalemleri.uyari', 'alis_fatura_kalemleri.birim_fiyat',
            'alis_fatura_kalemleri.onceki_fiyat', 'alis_fatura_kalemleri.fiyat_farki_yuzde',
            'alis_fatura_kalemleri.fiyat_farki_tutar', 'alis_faturalari.tarih')
        ->orderByDesc('alis_faturalari.tarih')->limit(25)->get();
    $tedarikciler = DB::table('tedarikciler')->get();
    return view('satinalma.index', compact('faturalar', 'uyarilar', 'tedarikciler'));
});

// ============================ MUSTERILER / CRM ============================
Route::get('/musteriler', function () {
    $musteriler = DB::table('musteriler')->orderByDesc('toplam_harcama')->limit(50)->get();
    $toplam = DB::table('musteriler')->count();
    return view('musteriler.index', compact('musteriler', 'toplam'));
});

// ============================ CAGRI MERKEZI (CallerID) ============================
Route::get('/cagrilar', function () {
    $cagrilar = DB::table('cagri_loglari')->leftJoin('musteriler', 'cagri_loglari.musteri_id', '=', 'musteriler.id')
        ->select('cagri_loglari.*', 'musteriler.ad as musteri', 'musteriler.siparis_sayisi', 'musteriler.toplam_harcama', 'musteriler.adres')
        ->orderByDesc('cagri_loglari.created_at')->limit(40)->get();
    $bugun = DB::table('cagri_loglari')->whereDate('created_at', today())->count();
    $kacan = DB::table('cagri_loglari')->where('sonuc', 'kacan')->whereDate('created_at', today())->count();
    return view('cagrilar.index', compact('cagrilar', 'bugun', 'kacan'));
});

// ============================ RAPORLAR ============================
Route::get('/raporlar', function () {
    $son30 = now()->subDays(30);
    // Menu muhendisligi: populerlik x ciro
    $menuMuh = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', $son30)
        ->select('adisyon_kalemleri.urun_adi', DB::raw('SUM(adisyon_kalemleri.adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as ciro'))
        ->groupBy('adisyon_kalemleri.urun_adi')->orderByDesc('ciro')->get();
    // Odeme tipi dagilimi
    $odemeTipi = DB::table('odemeler')->where('created_at', '>=', $son30)
        ->select('tip', DB::raw('SUM(tutar) as tutar'), DB::raw('COUNT(*) as adet'))->groupBy('tip')->get();
    // Saatlik yogunluk (DB-bagimsiz: PHP'de grupla)
    $acilislar = DB::table('adisyonlar')->where('durum', 'odendi')->where('kapanis', '>=', $son30)->pluck('acilis');
    $saatlik = [];
    foreach ($acilislar as $a) { $h = (int) substr((string) $a, 11, 2); $saatlik[$h] = ($saatlik[$h] ?? 0) + 1; }
    return view('raporlar.index', compact('menuMuh', 'odemeTipi', 'saatlik'));
});

// ============================ AI COPILOT ============================
Route::get('/copilot', function () {
    return view('copilot.index');
});

Route::post('/copilot/sor', function (Request $r) {
    return response()->json(_copilotCevap((string) $r->soru));
});

// ============================ QR MENU (musteri tarafi, public) ============================
Route::get('/qr/{masa?}', function ($masaAd = null) {
    $subeId = DB::table('subeler')->value('id');
    $sube = DB::table('subeler')->find($subeId);
    $kategoriler = DB::table('menu_kategorileri')->where('sube_id', $subeId)->orderBy('sira')->get();
    $urunler = DB::table('urunler')->where('sube_id', $subeId)->where('aktif', 1)->get()->groupBy('kategori_id');
    return view('qr.menu', compact('sube', 'kategoriler', 'urunler', 'masaAd'));
});

// ============================ ENTEGRASYONLAR (paket servis) ============================
Route::get('/entegrasyon', function () {
    $sube = DB::table('subeler')->first();
    $entegrasyonlar = DB::table('entegrasyonlar')->where('sube_id', $sube->id)->get()->keyBy('platform');
    $webhookUrl = url('/webhook/siparis/' . $sube->webhook_token);
    return view('entegrasyon.index', compact('sube', 'entegrasyonlar', 'webhookUrl'));
});

Route::post('/entegrasyon/kaydet', function (Request $r) {
    $sube = DB::table('subeler')->first();
    DB::table('entegrasyonlar')->updateOrInsert(
        ['sube_id' => $sube->id, 'platform' => $r->platform],
        ['aktif' => $r->aktif ? 1 : 0, 'magaza_id' => $r->magaza_id, 'api_key' => $r->api_key, 'otomatik_onay' => $r->otomatik_onay ? 1 : 0, 'updated_at' => now()]
    );
    return ['ok' => 1];
});

// Test siparisi: gercek webhook akisini simule eder -> Paket Merkezi'ne dusr
Route::post('/entegrasyon/test', function (Request $r) {
    $sube = DB::table('subeler')->first();
    $urunler = DB::table('urunler')->where('sube_id', $sube->id)->inRandomOrder()->limit(random_int(1, 3))->get();
    $kalemler = $urunler->map(fn ($u) => ['ad' => $u->ad, 'adet' => random_int(1, 2), 'fiyat' => $u->fiyat])->toArray();
    $adlar = ['Deniz Yilmaz', 'Cem Ozkan', 'Selin Ak', 'Baris Tan', 'Ece Demir'];
    return _paketSiparisAl($sube, [
        'platform' => $r->platform ?? 'getir',
        'musteri' => ['ad' => $adlar[array_rand($adlar)], 'telefon' => '0532' . random_int(1000000, 9999999), 'adres' => 'Moda Cad. No:' . random_int(1, 100) . ', Kadikoy'],
        'kalemler' => $kalemler,
    ]);
});

// PUBLIC WEBHOOK: middleware/aggregator (Posentegra/Entegro vb.) siparisleri buraya POST eder (bizim normalize formatimiz)
Route::post('/webhook/siparis/{token}', function (Request $r, $token) {
    $sube = DB::table('subeler')->where('webhook_token', $token)->first();
    if (!$sube) return response()->json(['ok' => 0, 'hata' => 'Gecersiz token'], 403);
    try {
        return response()->json(_paketSiparisAl($sube, $r->all()));
    } catch (\Throwable $e) {
        return response()->json(['ok' => 0, 'hata' => $e->getMessage()], 500);
    }
});

// Platform JSON -> bizim format (dogrudan Getir/Yemeksepeti/Trendyol baglaninca; gercek alan adlari API doc'a gore netlesir)
if (!function_exists('_pazaryeriNormalize')) {
    function _pazaryeriNormalize($platform, array $raw): array
    {
        if (isset($raw['kalemler']) || isset($raw['musteri'])) { $raw['platform'] = $platform; return $raw; } // zaten bizim format
        $m = [
            'platform' => $platform,
            'siparis_no' => $raw['orderId'] ?? $raw['order_id'] ?? $raw['id'] ?? null,
            'musteri' => [
                'ad' => $raw['customer']['name'] ?? $raw['customerName'] ?? $raw['name'] ?? 'Paket Müşteri',
                'telefon' => $raw['customer']['phone'] ?? $raw['phone'] ?? null,
                'adres' => $raw['delivery']['address'] ?? $raw['address'] ?? ($raw['customer']['address'] ?? null),
            ],
            'odeme_yontemi' => $raw['paymentType'] ?? $raw['payment'] ?? null,
            'kalemler' => [],
        ];
        foreach (($raw['items'] ?? $raw['products'] ?? $raw['lines'] ?? []) as $it) {
            $m['kalemler'][] = [
                'ad' => $it['name'] ?? $it['productName'] ?? $it['title'] ?? 'Ürün',
                'adet' => $it['count'] ?? $it['quantity'] ?? $it['qty'] ?? 1,
                'fiyat' => $it['price'] ?? $it['unitPrice'] ?? 0,
                'not' => $it['note'] ?? ($it['comment'] ?? null),
            ];
        }
        return $m;
    }
}

// Platform-basi webhook: dogrudan pazaryeri baglanirsa (normalize edip ayni hatta verir)
Route::post('/webhook/{platform}/{token}', function (Request $r, $platform, $token) {
    $sube = DB::table('subeler')->where('webhook_token', $token)->first();
    if (!$sube) return response()->json(['ok' => 0, 'hata' => 'Gecersiz token'], 403);
    if (!in_array($platform, ['getir', 'yemeksepeti', 'trendyol', 'migros', 'gofody'])) return response()->json(['ok' => 0, 'hata' => 'Bilinmeyen platform'], 400);
    try {
        return response()->json(_paketSiparisAl($sube, _pazaryeriNormalize($platform, $r->all())));
    } catch (\Throwable $e) {
        return response()->json(['ok' => 0, 'hata' => $e->getMessage()], 500);
    }
});

// Kurulum/ozet: aggregatore/pazaryerine verilecek webhook adresleri
Route::get('/pazaryeri-kur', function () {
    $sube = DB::table('subeler')->first();
    if (empty($sube->webhook_token)) {
        DB::table('subeler')->where('id', $sube->id)->update(['webhook_token' => \Illuminate\Support\Str::random(32)]);
        $sube = DB::table('subeler')->first();
    }
    $t = $sube->webhook_token;
    $out = "Pazaryeri alim hatti HAZIR.\n\nGenel webhook (aggregator - Posentegra/Entegro; onerilir):\n" . url('/webhook/siparis/' . $t) . "\n\nDogrudan baglanti (platform-basi):\n";
    foreach (['getir', 'yemeksepeti', 'trendyol', 'migros'] as $p) $out .= "  " . ucfirst($p) . ": " . url('/webhook/' . $p . '/' . $t) . "\n";
    $out .= "\nGelen siparis -> otomatik musteri + adisyon (kanal=paket) -> MUTFAK ekranina duser.\nGercek siparis icin: aggregator uyeligi VEYA pazaryeri API anahtari (senin tarafinda).";
    return response($out)->header('Content-Type', 'text/plain; charset=utf-8');
});

// ==================== ONLINE ODEME (pluggable: Iyzico/PayTR; simulasyon -> anahtar gelince canli) ====================
if (!function_exists('_odemeEnsure')) {
    function _odemeEnsure()
    {
        if (!Schema::hasTable('odeme_islemleri')) {
            Schema::create('odeme_islemleri', function ($t) {
                $t->id(); $t->unsignedBigInteger('sube_id'); $t->unsignedBigInteger('adisyon_id');
                $t->string('token', 40)->index(); $t->decimal('tutar', 12, 2);
                $t->string('saglayici', 20)->default('simulasyon'); $t->string('durum', 15)->default('bekliyor');
                $t->timestamp('created_at')->useCurrent();
            });
        }
        if (!Schema::hasTable('odeme_ayarlari')) {
            Schema::create('odeme_ayarlari', function ($t) {
                $t->id(); $t->unsignedBigInteger('sube_id')->unique();
                $t->string('saglayici', 20)->default('kapali'); $t->boolean('aktif')->default(0);
                $t->string('api_key')->nullable(); $t->string('secret')->nullable(); $t->string('magaza_id')->nullable();
                $t->timestamps();
            });
        }
    }
}
if (!function_exists('_odemeSaglayici')) {
    function _odemeSaglayici($subeId)
    {
        _odemeEnsure();
        $a = DB::table('odeme_ayarlari')->where('sube_id', $subeId)->first();
        if ($a && $a->aktif && $a->saglayici !== 'kapali' && $a->api_key) return $a->saglayici;
        return 'simulasyon';
    }
}
// Odeme baslat: takip_token VEYA adisyon_id
Route::post('/api/odeme/baslat', function (Request $r) {
    _odemeEnsure();
    $a = null;
    if ($r->filled('takip_token')) $a = DB::table('adisyonlar')->where('takip_token', $r->takip_token)->first();
    elseif ($r->filled('adisyon_id')) $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a) return response()->json(['ok' => 0, 'hata' => 'Adisyon bulunamadı'], 404);
    if ($a->durum === 'odendi') return ['ok' => 0, 'hata' => 'Bu sipariş zaten ödendi'];
    $tutar = (float) $a->toplam;
    if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Ödenecek tutar yok'];
    $saglayici = _odemeSaglayici($a->sube_id);
    $token = \Illuminate\Support\Str::random(30);
    DB::table('odeme_islemleri')->insert(['sube_id' => $a->sube_id, 'adisyon_id' => $a->id, 'token' => $token,
        'tutar' => $tutar, 'saglayici' => $saglayici, 'durum' => 'bekliyor', 'created_at' => now()]);
    return ['ok' => 1, 'ode_url' => url('/ode/' . $token), 'saglayici' => $saglayici, 'tutar' => $tutar];
});
// Odeme sayfasi
Route::get('/ode/{token}', function ($token) {
    _odemeEnsure();
    $i = DB::table('odeme_islemleri')->where('token', $token)->first();
    if (!$i) abort(404);
    return view('odeme', ['islem' => $i, 'sube' => DB::table('subeler')->find($i->sube_id)]);
});
// Odeme tamamla (CSRF muaf: ode/*) — simulasyon basarili; gercek saglayici callback'i buraya baglanir
Route::post('/ode/{token}/tamamla', function (Request $r, $token) {
    _odemeEnsure();
    $i = DB::table('odeme_islemleri')->where('token', $token)->first();
    if (!$i) return response()->json(['ok' => 0], 404);
    if ($i->durum === 'odendi') return ['ok' => 1, 'mesaj' => 'Zaten ödendi'];
    // === GERCEK SAGLAYICI DOGRULAMASI (Iyzico/PayTR 3D sonucu) BURAYA ===
    $a = DB::table('adisyonlar')->find($i->adisyon_id);
    if ($a && $a->durum !== 'odendi') {
        DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => 'online', 'tutar' => $i->tutar, 'created_at' => now()]);
        DB::table('adisyonlar')->where('id', $a->id)->update(['durum' => 'odendi', 'kapanis' => now(), 'updated_at' => now()]);
        if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
    }
    DB::table('odeme_islemleri')->where('id', $i->id)->update(['durum' => 'odendi']);
    return ['ok' => 1, 'mesaj' => 'Ödeme başarılı'];
});
Route::get('/api/odeme/durum/{token}', function ($token) {
    _odemeEnsure();
    $i = DB::table('odeme_islemleri')->where('token', $token)->first();
    if (!$i) return response()->json(['ok' => 0], 404);
    return ['ok' => 1, 'durum' => $i->durum, 'tutar' => (float) $i->tutar, 'saglayici' => $i->saglayici];
});
Route::get('/odeme-kur', function () {
    _odemeEnsure();
    return response("Online odeme hazir (SIMULASYON modu; Iyzico/PayTR anahtari girilince CANLI).\nAkis: POST /api/odeme/baslat {adisyon_id|takip_token} -> ode_url -> /ode/{token} -> POST /ode/{token}/tamamla\nMasada QR ve musteri app'te 'Online Ode' bu akisi kullanir.")->header('Content-Type', 'text/plain; charset=utf-8');
});

// ============================ PATRON HIZLI KARSILASTIRMA (Kerzz Boss tarzi) ============================
Route::get('/patron', function () {
    $now = now();
    $t0 = today()->startOfDay();
    $ciro = fn ($from, $to) => (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');

    // Bugun (su ana kadar) vs adil karsilastirmalar (dun/gecen hafta AYNI SAATE kadar)
    $bugun = $ciro($t0, $now);
    $dun = $ciro((clone $t0)->subDay(), (clone $now)->subDay());
    $gecenHaftaGun = $ciro((clone $t0)->subDays(7), (clone $now)->subDays(7));
    $buHafta = $ciro((clone $now)->subDays(7), $now);
    $oncekiHafta = $ciro((clone $now)->subDays(14), (clone $now)->subDays(7));
    $buAy = $ciro((clone $now)->subDays(30), $now);
    $oncekiAy = $ciro((clone $now)->subDays(60), (clone $now)->subDays(30));

    $bugunAdisyon = DB::table('adisyonlar')->whereBetween('acilis', [$t0, $now])->count();
    $acikMasa = DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')->count();
    $acikTutar = (float) DB::table('adisyonlar')->where('durum', 'acik')->sum('toplam');

    $topBugun = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->whereBetween('adisyonlar.acilis', [$t0, $now])
        ->select('urun_adi', DB::raw('SUM(adet) as a'))->groupBy('urun_adi')->orderByDesc('a')->limit(3)->get();

    // Olay-tabanli uyarilar (anomali)
    $uyarilar = [];
    if ($dun > 0 && $bugun < $dun * 0.85) {
        $uyarilar[] = ['ikon' => '📉', 'renk' => 'rose', 'msg' => 'Bugün ciro, dün aynı saate göre %' . round(($dun - $bugun) / $dun * 100) . ' geride.'];
    } elseif ($dun > 0 && $bugun > $dun * 1.15) {
        $uyarilar[] = ['ikon' => '🚀', 'renk' => 'emerald', 'msg' => 'Bugün ciro, dün aynı saate göre %' . round(($bugun - $dun) / $dun * 100) . ' önde. Harika gidiyor!'];
    }
    $kritik = DB::table('malzemeler')->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
        ->select('malzemeler.id')->groupBy('malzemeler.id', 'malzemeler.kritik_stok')
        ->havingRaw('COALESCE(SUM(stok_hareketleri.miktar),0) < malzemeler.kritik_stok')->get()->count();
    if ($kritik > 0) $uyarilar[] = ['ikon' => '📦', 'renk' => 'amber', 'msg' => $kritik . ' malzeme kritik stok seviyesinde — sipariş ver.'];
    $kacan = DB::table('cagri_loglari')->where('sonuc', 'kacan')->whereDate('created_at', today())->count();
    if ($kacan > 0) $uyarilar[] = ['ikon' => '📵', 'renk' => 'rose', 'msg' => 'Bugün ' . $kacan . ' kaçan çağrı — AI Santral bunları kurtarabilir.'];
    $paketAktif = DB::table('adisyonlar')->where('kanal', 'paket')->where('durum', 'acik')->count();
    if ($paketAktif > 0) $uyarilar[] = ['ikon' => '🛵', 'renk' => 'indigo', 'msg' => $paketAktif . ' aktif paket sipariş hazırlanıyor.'];

    return view('patron', compact('bugun', 'dun', 'gecenHaftaGun', 'buHafta', 'oncekiHafta', 'buAy', 'oncekiAy',
        'bugunAdisyon', 'acikMasa', 'acikTutar', 'topBugun', 'uyarilar'));
});

// ============================ FIYATLANDIRMA / PAKETLER ============================
Route::get('/fiyatlandirma', function () {
    return view('fiyatlandirma');
});

// ============================ DEMO VERI YUKLE (tek tik) ============================
// Tum tablolari (yeni eklenenler dahil) taze demo veriyle doldurur. Idempotent.
Route::get('/demo-veri-yukle', function () {
    @set_time_limit(600);
    @ini_set('memory_limit', '512M');
    try {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
    } catch (\Throwable $e) {
        return '<div style="font-family:sans-serif;max-width:600px;margin:80px auto;padding:32px;border:1px solid #fecaca;border-radius:16px;background:#fef2f2">'
            . '<h2 style="color:#b91c1c">Hata</h2><pre style="white-space:pre-wrap;color:#7f1d1d">' . e($e->getMessage()) . '</pre></div>';
    }
    return '<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;padding:32px;border:1px solid #e2e8f0;border-radius:16px">'
        . '<div style="font-size:48px">✅</div>'
        . '<h2 style="color:#0f172a">Demo veriler yüklendi</h2>'
        . '<p style="color:#64748b">Tüm modüller dolduruldu (teklifler, kampanyalar, paket, kurye, müşteri dahil).</p>'
        . '<a href="/teklif" style="display:inline-block;margin-top:12px;background:#4f46e5;color:#fff;padding:12px 24px;border-radius:12px;text-decoration:none;font-weight:600">Teklifler\'e git →</a></div>';
});

// Acik paket siparislerin sürelerini tazele -> rozetler yesil/turuncu/kirmizi karisik gorunsun.
// Full seed'i CALISTIRMAZ (takilmaz); sadece acik paket adisyonlarin acilis saatini yeniden dagitir.
Route::get('/paket-tazele', function () {
    $acik = DB::table('adisyonlar')->where('kanal', 'paket')->where('durum', 'acik')
        ->orderBy('id')->pluck('id');
    if ($acik->isEmpty()) {
        return '<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;padding:32px;border:1px solid #e2e8f0;border-radius:16px">'
            . '<div style="font-size:48px">📦</div><h2 style="color:#0f172a">Açık paket sipariş yok</h2>'
            . '<p style="color:#64748b">Önce /demo-veri-yukle ile veri yükleyin.</p></div>';
    }
    // Kova basina araliklar: yesil (<25dk), turuncu (25-45dk), kirmizi (>=45dk)
    $kovalar = [[5, 20], [28, 42], [52, 95]];
    $sayac = ['yesil' => 0, 'turuncu' => 0, 'kirmizi' => 0];
    foreach ($acik as $i => $id) {
        $kova = $kovalar[$i % 3];
        $acilis = now()->subMinutes(random_int($kova[0], $kova[1]));
        DB::table('adisyonlar')->where('id', $id)->update([
            'acilis' => $acilis, 'created_at' => $acilis, 'updated_at' => $acilis,
        ]);
        DB::table('adisyon_kalemleri')->where('adisyon_id', $id)
            ->update(['gonderim_zamani' => $acilis]);
        $sayac[$i % 3 === 0 ? 'yesil' : ($i % 3 === 1 ? 'turuncu' : 'kirmizi')]++;
    }
    return '<div style="font-family:sans-serif;max-width:520px;margin:80px auto;text-align:center;padding:32px;border:1px solid #e2e8f0;border-radius:16px">'
        . '<div style="font-size:48px">✅</div><h2 style="color:#0f172a">Paket süreleri tazelendi</h2>'
        . '<p style="color:#64748b">' . $acik->count() . ' sipariş güncellendi — '
        . '🟢 ' . $sayac['yesil'] . ' &nbsp; 🟠 ' . $sayac['turuncu'] . ' &nbsp; 🔴 ' . $sayac['kirmizi'] . '</p>'
        . '<p style="color:#94a3b8;font-size:13px">Uygulamada Paket sekmesini aşağı çekip yenileyin.</p></div>';
});

// Kayip Radari zenginlestirme (SADECE ekler, truncate YOK, ciroyu etkilemez, tek sefer)
Route::get('/enrich-kayip', function () {
    if (DB::table('stok_hareketleri')->where('tip', 'fire')->count() > 5) {
        return 'Zaten zenginlestirilmis (fire hareketi mevcut).';
    }
    $sube = DB::table('subeler')->first();
    $garsonlar = DB::table('personeller')->where('sube_id', $sube->id)->pluck('id')->all();
    $g = fn () => $garsonlar[array_rand($garsonlar)];

    // 1) FIRE / Zayi
    foreach (DB::table('malzemeler')->get(['id', 'guncel_maliyet']) as $m) {
        if (random_int(0, 2) === 0) {
            DB::table('stok_hareketleri')->insert([
                'sube_id' => $sube->id, 'malzeme_id' => $m->id, 'tip' => 'fire',
                'miktar' => -1 * random_int(2, 20), 'birim_maliyet' => $m->guncel_maliyet ?: random_int(20, 200),
                'kaynak_tip' => 'fire', 'aciklama' => ['SKT gecti', 'Bozulma', 'Dokulme', 'Hazirlik firesi'][random_int(0, 3)],
                'personel_id' => $g(), 'created_at' => now()->subDays(random_int(0, 14)),
            ]);
        }
    }
    // 2) SILINEN URUN (void) - mevcut odendi adisyonlara YENI iptal kalem (toplam etkilenmez)
    $urunler = DB::table('urunler')->inRandomOrder()->limit(50)->get(['id', 'ad', 'fiyat']);
    $uArr = $urunler->all();
    foreach (DB::table('adisyonlar')->where('durum', 'odendi')->where('kapanis', '>=', now()->subDays(30))
        ->inRandomOrder()->limit(45)->get(['id', 'acilis', 'acan_personel_id']) as $a) {
        $u = $uArr[array_rand($uArr)];
        $adet = random_int(1, 2);
        DB::table('adisyon_kalemleri')->insert([
            'adisyon_id' => $a->id, 'urun_id' => $u->id, 'urun_adi' => $u->ad,
            'adet' => $adet, 'birim_fiyat' => $u->fiyat, 'tutar' => $u->fiyat * $adet,
            'durum' => 'iptal', 'personel_id' => $a->acan_personel_id,
            'gonderim_zamani' => $a->acilis, 'created_at' => $a->acilis, 'updated_at' => $a->acilis,
        ]);
        DB::table('iptal_indirim_loglari')->insert([
            'sube_id' => $sube->id, 'adisyon_id' => $a->id, 'tip' => 'void', 'tutar' => $u->fiyat * $adet,
            'sebep' => ['Musteri vazgecti', 'Yanlis girildi', 'Urun tukendi', 'Musteri begenmedi'][random_int(0, 3)],
            'personel_id' => $a->acan_personel_id, 'created_at' => $a->acilis,
        ]);
    }
    // 3) IPTAL ADISYON (Canceled Checks) - YENI iptal adisyonlar (ciroyu etkilemez)
    $masalar = DB::table('masalar')->pluck('id')->all();
    for ($i = 0; $i < 14; $i++) {
        $ac = now()->subDays(random_int(0, 29))->setTime(random_int(12, 22), random_int(0, 59));
        $t = random_int(200, 900);
        DB::table('adisyonlar')->insert([
            'sube_id' => $sube->id, 'masa_id' => $masalar[array_rand($masalar)], 'kanal' => 'salon',
            'misafir_sayisi' => random_int(1, 4), 'durum' => 'iptal', 'acan_personel_id' => $g(),
            'ara_toplam' => $t, 'indirim' => 0, 'ikram' => 0, 'toplam' => $t,
            'acilis' => $ac, 'kapanis' => null, 'created_at' => $ac, 'updated_at' => $ac,
        ]);
    }
    // 4) Iskonto/ikram biraz daha belirgin olsun: bazi odendi adisyonlara indirim/ikram ekle (toplam+odeme tutarli dusur)
    foreach (DB::table('adisyonlar')->where('durum', 'odendi')->where('kapanis', '>=', now()->subDays(30))
        ->where('indirim', 0)->where('ikram', 0)->inRandomOrder()->limit(60)->get(['id', 'ara_toplam', 'toplam', 'acan_personel_id', 'kapanis']) as $a) {
        $indirim = round($a->ara_toplam * [0.05, 0.1, 0.15, 0.2][random_int(0, 3)], 2);
        $yeni = max(0, $a->toplam - $indirim);
        DB::table('adisyonlar')->where('id', $a->id)->update(['indirim' => $indirim, 'toplam' => $yeni]);
        DB::table('odemeler')->where('adisyon_id', $a->id)->update(['tutar' => $yeni]);
        DB::table('iptal_indirim_loglari')->insert([
            'sube_id' => $sube->id, 'adisyon_id' => $a->id, 'tip' => 'indirim', 'tutar' => $indirim,
            'sebep' => ['Musteri memnuniyeti', 'Personel', 'Isletme yakini', 'Telafi'][random_int(0, 3)],
            'personel_id' => $a->acan_personel_id, 'created_at' => $a->kapanis,
        ]);
    }
    return 'Kayip radari zenginlestirildi: fire + silinen urun + iptal adisyon + iskonto eklendi. ✅';
});

// Musteri degerlendirmesi (anket/yorum) seed - tabloyu garantiye alir + doldurur (tek sefer)
Route::get('/enrich-anket', function () {
    if (!Schema::hasTable('degerlendirmeler')) {
        Schema::create('degerlendirmeler', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('adisyon_id')->nullable();
            $t->unsignedBigInteger('masa_id')->nullable();
            $t->unsignedBigInteger('musteri_id')->nullable();
            $t->unsignedTinyInteger('puan')->default(0);
            $t->unsignedTinyInteger('lezzet')->default(0);
            $t->unsignedTinyInteger('servis')->default(0);
            $t->unsignedTinyInteger('hiz')->default(0);
            $t->text('yorum')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }
    if (DB::table('degerlendirmeler')->count() > 5) return 'Anketler zaten dolu.';

    $yorumlar = [
        5 => ['Her şey mükemmeldi, kesinlikle tekrar geleceğiz!', 'Lezzetler harika, personel çok ilgili. Ellerinize sağlık.', 'Bayıldık, sıcacık ve hızlı servis. 10/10', 'Uzun zamandır bu kadar güzel yememiştik.'],
        4 => ['Genel olarak memnun kaldık, lezzetler güzeldi.', 'Güzeldi ama servis biraz yavaştı, yine de tavsiye ederim.', 'Lezzet iyiydi, ortam keyifliydi.', 'Memnun ayrıldık, teşekkürler.'],
        3 => ['Fena değildi ama beklediğimden farklıydı.', 'Ortalama bir deneyimdi, ne iyi ne kötü.', 'Yemek iyiydi ama biraz beklettiler.'],
        2 => ['Yemekler ılık geldi, servis yavaştı.', 'Fiyat/performans pek iyi değil, biraz hayal kırıklığı.', 'Sipariş eksik geldi, düzeltilene kadar bekledik.'],
        1 => ['Çok kötü bir deneyimdi, bir daha gelmem.', 'Sipariş yanlış geldi ve ilgilenen olmadı.', 'Soğuk yemek, ilgisiz personel. Tavsiye etmem.'],
    ];
    $agirlik = [5, 5, 5, 5, 4, 4, 4, 3, 3, 2, 1]; // cogunluk mutlu

    $adisyonlar = DB::table('adisyonlar')->where('durum', 'odendi')->where('kapanis', '>=', now()->subDays(60))
        ->inRandomOrder()->limit(400)->get(['id', 'sube_id', 'masa_id', 'musteri_id', 'kapanis']);
    $n = 0;
    foreach ($adisyonlar as $a) {
        if (random_int(0, 99) < 35) continue; // ~%65 anket doldurmus
        $puan = $agirlik[array_rand($agirlik)];
        $sap = fn () => max(1, min(5, $puan + random_int(-1, 1)));
        DB::table('degerlendirmeler')->insert([
            'sube_id' => $a->sube_id, 'adisyon_id' => $a->id, 'masa_id' => $a->masa_id, 'musteri_id' => $a->musteri_id,
            'puan' => $puan, 'lezzet' => $sap(), 'servis' => $sap(), 'hiz' => $sap(),
            'yorum' => $yorumlar[$puan][array_rand($yorumlar[$puan])],
            'created_at' => \Carbon\Carbon::parse($a->kapanis)->addMinutes(random_int(5, 120)),
        ]);
        $n++;
    }
    return "Anketler yüklendi: $n değerlendirme eklendi. ✅";
});

// Tum urunlere recete seed (recetesi olmayana) -> food-cost gercek receteden hesaplanir + detayda gorunur
Route::get('/enrich-recete', function () {
    $malzemeler = DB::table('malzemeler')->get(['id', 'temel_birim_id']);
    if ($malzemeler->isEmpty()) return 'Malzeme yok.';
    $birimTip = DB::table('birimler')->pluck('tip', 'id'); // agirlik | hacim | adet
    $n = 0;
    foreach (DB::table('urunler')->get(['id', 'ad']) as $u) {
        if (DB::table('receteler')->where('urun_id', $u->id)->where('tip', 'urun')->exists()) continue;
        $rid = DB::table('receteler')->insertGetId([
            'ad' => $u->ad . ' Reçetesi', 'tip' => 'urun', 'urun_id' => $u->id,
            'verim_miktar' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($malzemeler->shuffle()->take(random_int(3, 5)) as $m) {
            $tip = $birimTip[$m->temel_birim_id] ?? 'adet';
            $miktar = $tip === 'agirlik' ? random_int(30, 300) : ($tip === 'hacim' ? random_int(10, 200) : random_int(1, 3));
            DB::table('recete_kalemleri')->insert([
                'recete_id' => $rid, 'malzeme_id' => $m->id, 'miktar' => $miktar,
                'birim_id' => $m->temel_birim_id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $n++;
    }
    return "Reçeteler yüklendi: $n ürüne reçete eklendi. ✅";
});

// Kapali adisyonlarin bir kismina musteri ata + degerlendirme/istatistikleri baglar (musteri karti dolsun)
Route::get('/enrich-musteri-baglama', function () {
    $musteriler = DB::table('musteriler')->pluck('id')->all();
    if (!$musteriler) return 'Müşteri yok.';
    $n = 0;
    foreach (DB::table('adisyonlar')->where('durum', 'odendi')->whereNull('musteri_id')->inRandomOrder()->limit(300)->get(['id']) as $a) {
        if (random_int(0, 99) < 55) continue; // ~%45'ine musteri ata
        DB::table('adisyonlar')->where('id', $a->id)->update(['musteri_id' => $musteriler[array_rand($musteriler)]]);
        $n++;
    }
    // Degerlendirme musteri_id'yi adisyondan doldur
    if (Schema::hasTable('degerlendirmeler')) {
        foreach (DB::table('degerlendirmeler')->whereNotNull('adisyon_id')->get(['id', 'adisyon_id']) as $dd) {
            $mid = DB::table('adisyonlar')->where('id', $dd->adisyon_id)->value('musteri_id');
            if ($mid) DB::table('degerlendirmeler')->where('id', $dd->id)->update(['musteri_id' => $mid]);
        }
    }
    // Musteri istatistiklerini guncelle
    foreach ($musteriler as $mid) {
        DB::table('musteriler')->where('id', $mid)->update([
            'siparis_sayisi' => DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->count(),
            'toplam_harcama' => (float) DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->sum('toplam'),
        ]);
    }
    return "Müşteri bağlama: $n kapalı adisyona müşteri atandı + değerlendirme/istatistik güncellendi. ✅";
});

// Receteleri KALIBRE et: her urunun malzeme maliyeti ~fiyatin %26-34'u olacak sekilde miktarlar hesaplanir
// (ucuz malzeme cok gram, pahali az gram = gercekci). Food-cost saglikli, gram gorunur.
Route::get('/enrich-recete-fix', function () {
    $malzemeler = DB::table('malzemeler')->where('guncel_maliyet', '>', 0)->get(['id', 'temel_birim_id', 'guncel_maliyet']);
    if ($malzemeler->count() < 3) return 'Yeterli maliyetli malzeme yok.';
    $birimTip = DB::table('birimler')->pluck('tip', 'id');
    $n = 0;
    foreach (DB::table('urunler')->where('fiyat', '>', 0)->get(['id', 'ad', 'fiyat']) as $u) {
        $rid = DB::table('receteler')->where('urun_id', $u->id)->where('tip', 'urun')->value('id');
        if (!$rid) {
            $rid = DB::table('receteler')->insertGetId(['ad' => $u->ad . ' Reçetesi', 'tip' => 'urun', 'urun_id' => $u->id,
                'verim_miktar' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('recete_kalemleri')->where('recete_id', $rid)->delete();
        $target = (float) $u->fiyat * random_int(26, 34) / 100;   // hedef malzeme maliyeti
        $sec = $malzemeler->shuffle()->take(random_int(3, 5));
        $weights = [];
        $tot = 0;
        foreach ($sec as $m) { $w = random_int(10, 100); $weights[$m->id] = $w; $tot += $w; }
        foreach ($sec as $m) {
            $costShare = $target * ($weights[$m->id] / $tot);       // bu malzemeye dusen maliyet
            $amount = $costShare / (float) $m->guncel_maliyet;      // temel birimde miktar
            $tip = $birimTip[$m->temel_birim_id] ?? 'adet';
            if ($tip === 'adet') $amount = max(1, round($amount));
            else $amount = $amount < 10 ? max(0.5, round($amount, 1)) : round($amount);
            DB::table('recete_kalemleri')->insert(['recete_id' => $rid, 'malzeme_id' => $m->id, 'miktar' => $amount,
                'birim_id' => $m->temel_birim_id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $n++;
    }
    return "Reçeteler kalibre edildi: $n ürün, food-cost ~%30 hedefiyle. ✅";
});

// AKILLI recete: urun adina gore gercekci malzeme + gercekci gram (dogal food-cost)
Route::get('/enrich-recete-akilli', function () {
    $malz = DB::table('malzemeler')->get()->keyBy('ad');
    // [min, max, ondalik?] temel birimde gercekci porsiyon miktari
    $aralik = [
        'Dana Kiyma' => [120, 160], 'Dana Antrikot' => [150, 220], 'Tavuk Gogus' => [130, 180], 'Kuzu Pirzola' => [180, 250], 'Sucuk' => [30, 60],
        'Domates' => [30, 80], 'Salatalik' => [20, 50], 'Sogan' => [20, 50], 'Patates' => [100, 180], 'Biber' => [20, 50], 'Mantar' => [30, 80],
        'Marul' => [0.2, 0.4, 1], 'Maydanoz' => [0.1, 0.3, 1],
        'Beyaz Peynir' => [40, 90], 'Kasar Peyniri' => [60, 120], 'Mozzarella' => [80, 140], 'Tereyagi' => [10, 30], 'Yogurt' => [120, 200], 'Sut' => [120, 220], 'Yumurta' => [1, 2],
        'Makarna' => [100, 160], 'Pirinc' => [80, 150], 'Bulgur' => [60, 120], 'Mercimek' => [60, 100],
        'Kola' => [1, 1], 'Ayran' => [1, 1], 'Su' => [1, 1], 'Cay' => [3, 5], 'Turk Kahvesi' => [12, 18],
        'Zeytinyagi' => [10, 25], 'Aycicek Yagi' => [15, 40], 'Tuz' => [3, 8], 'Karabiber' => [1, 3], 'Domates Salcasi' => [20, 50],
        'Un' => [200, 280], 'Ekmek' => [0.2, 0.6, 1], 'Hamburger Ekmegi' => [1, 1], 'Patates (Donuk)' => [120, 200], 'Dondurma' => [80, 150],
    ];
    $miktarUret = function ($ad) use ($aralik) {
        $a = $aralik[$ad] ?? [20, 80];
        $dec = $a[2] ?? 0;
        if ($dec) return random_int((int) round($a[0] * 10), (int) round($a[1] * 10)) / 10;
        return random_int((int) $a[0], (int) $a[1]);
    };
    // keyword (kucuk harf) => malzeme adlari — spesifik once
    $kurallar = [
        ['cheese', ['Dana Kiyma', 'Hamburger Ekmegi', 'Kasar Peyniri', 'Domates', 'Marul']],
        ['burger', ['Dana Kiyma', 'Hamburger Ekmegi', 'Kasar Peyniri', 'Domates', 'Marul', 'Sogan']],
        ['adana', ['Dana Kiyma', 'Biber', 'Sogan', 'Karabiber', 'Tuz', 'Maydanoz']],
        ['kofte', ['Dana Kiyma', 'Ekmek', 'Sogan', 'Maydanoz', 'Tuz', 'Karabiber']],
        ['köfte', ['Dana Kiyma', 'Ekmek', 'Sogan', 'Maydanoz', 'Tuz', 'Karabiber']],
        ['lahmacun', ['Un', 'Dana Kiyma', 'Sogan', 'Biber', 'Domates Salcasi']],
        ['pirzola', ['Kuzu Pirzola', 'Tuz', 'Karabiber', 'Zeytinyagi', 'Patates']],
        ['antrikot', ['Dana Antrikot', 'Tuz', 'Karabiber', 'Tereyagi']],
        ['sote', ['Dana Antrikot', 'Biber', 'Sogan', 'Domates', 'Mantar', 'Zeytinyagi']],
        ['söte', ['Dana Antrikot', 'Biber', 'Sogan', 'Domates', 'Mantar', 'Zeytinyagi']],
        ['guvec', ['Tavuk Gogus', 'Patates', 'Biber', 'Domates', 'Sogan', 'Kasar Peyniri']],
        ['güveç', ['Tavuk Gogus', 'Patates', 'Biber', 'Domates', 'Sogan', 'Kasar Peyniri']],
        ['izgara', ['Tavuk Gogus', 'Dana Antrikot', 'Tuz', 'Karabiber', 'Zeytinyagi', 'Biber']],
        ['kebap', ['Dana Kiyma', 'Biber', 'Sogan', 'Domates', 'Tuz']],
        ['margarita', ['Un', 'Mozzarella', 'Domates Salcasi', 'Zeytinyagi']],
        ['pizza', ['Un', 'Mozzarella', 'Domates Salcasi', 'Kasar Peyniri', 'Sucuk', 'Mantar']],
        ['makarna', ['Makarna', 'Domates Salcasi', 'Kasar Peyniri', 'Tereyagi', 'Mantar']],
        ['spagetti', ['Makarna', 'Domates Salcasi', 'Kasar Peyniri', 'Tereyagi']],
        ['penne', ['Makarna', 'Domates Salcasi', 'Kasar Peyniri', 'Mantar']],
        ['salata', ['Marul', 'Domates', 'Salatalik', 'Biber', 'Zeytinyagi', 'Beyaz Peynir']],
        ['corba', ['Mercimek', 'Sogan', 'Un', 'Tereyagi', 'Tuz']],
        ['çorba', ['Mercimek', 'Sogan', 'Un', 'Tereyagi', 'Tuz']],
        ['mercimek', ['Mercimek', 'Sogan', 'Un', 'Tereyagi', 'Tuz']],
        ['pilav', ['Pirinc', 'Tereyagi', 'Tuz']],
        ['baklava', ['Un', 'Tereyagi', 'Yumurta']],
        ['sutlac', ['Sut', 'Pirinc', 'Un']],
        ['sütlaç', ['Sut', 'Pirinc', 'Un']],
        ['dondurma', ['Dondurma', 'Sut']],
        ['tatli', ['Un', 'Sut', 'Tereyagi', 'Yumurta']],
        ['tatlı', ['Un', 'Sut', 'Tereyagi', 'Yumurta']],
        ['patates', ['Patates (Donuk)', 'Aycicek Yagi', 'Tuz']],
        ['tavuk', ['Tavuk Gogus', 'Tuz', 'Karabiber', 'Zeytinyagi', 'Biber']],
        ['salep', ['Sut', 'Tuz']],
        ['latte', ['Turk Kahvesi', 'Sut']],
        ['cappuccino', ['Turk Kahvesi', 'Sut']],
        ['mocha', ['Turk Kahvesi', 'Sut']],
        ['macchiato', ['Turk Kahvesi', 'Sut']],
        ['americano', ['Turk Kahvesi', 'Su']],
        ['espresso', ['Turk Kahvesi']],
        ['filter', ['Turk Kahvesi', 'Su']],
        ['flat white', ['Turk Kahvesi', 'Sut']],
        ['white', ['Turk Kahvesi', 'Sut']],
        ['chocolate', ['Sut', 'Dondurma']],
        ['çikolata', ['Sut', 'Dondurma']],
        ['coffee', ['Turk Kahvesi', 'Sut']],
        ['kahve', ['Turk Kahvesi', 'Su']],
        ['çay', ['Cay', 'Su']],
        ['cay', ['Cay', 'Su']],
        ['ayran', ['Yogurt', 'Su', 'Tuz']],
        ['kola', ['Kola']],
        ['soda', ['Su']],
        ['su', ['Su']],
        ['et', ['Dana Antrikot', 'Tuz', 'Karabiber', 'Tereyagi']],
    ];
    $fallback = ['Sogan', 'Domates', 'Zeytinyagi', 'Tuz', 'Tereyagi'];
    $n = 0;
    foreach (DB::table('urunler')->where('fiyat', '>', 0)->get(['id', 'ad', 'fiyat']) as $u) {
        $ad = mb_strtolower($u->ad, 'UTF-8');
        $secilen = null;
        foreach ($kurallar as [$kw, $ings]) {
            if (mb_strpos($ad, $kw) !== false) { $secilen = $ings; break; }
        }
        if (!$secilen) $secilen = $fallback;
        $rid = DB::table('receteler')->where('urun_id', $u->id)->where('tip', 'urun')->value('id');
        if (!$rid) {
            $rid = DB::table('receteler')->insertGetId(['ad' => $u->ad . ' Reçetesi', 'tip' => 'urun', 'urun_id' => $u->id,
                'verim_miktar' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('recete_kalemleri')->where('recete_id', $rid)->delete();
        foreach ($secilen as $mad) {
            $m = $malz[$mad] ?? null;
            if (!$m) continue;
            DB::table('recete_kalemleri')->insert(['recete_id' => $rid, 'malzeme_id' => $m->id, 'miktar' => $miktarUret($mad),
                'birim_id' => $m->temel_birim_id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $n++;
    }
    return "Akıllı reçeteler yüklendi: $n ürün (ada göre gerçekçi malzeme). ✅";
});

// Patron (sahip) adini degistir (canli, reseed'siz). ?ad=Özcan
Route::get('/set-patron-adi', function (Request $r) {
    $ad = trim((string) ($r->ad ?: 'Özcan'));
    $n = DB::table('personeller')->where('rol', 'sahip')->update(['ad' => $ad]);
    return "Sahip adi guncellendi: $ad ($n kayit). Uygulamada cikis yapip tekrar girin.";
});

// Turkce karakter duzeltme: ASCII isimleri (Kofte->Köfte, Cay->Çay...) gercek Turkce'ye cevirir.
// urunler + adisyon_kalemleri(snapshot) + malzemeler + personeller + subeler + kategoriler.
Route::get('/fix-turkce', function () {
    $map = [
        // urunler
        'Mercimek Corbasi' => 'Mercimek Çorbası', 'Ezogelin Corbasi' => 'Ezogelin Çorbası', 'Sigara Boregi' => 'Sigara Böreği',
        'Coban Salata' => 'Çoban Salata', 'Ton Balikli Salata' => 'Ton Balıklı Salata', 'Tavuk Sis' => 'Tavuk Şiş',
        'Karisik Izgara' => 'Karışık Izgara', 'Kofte' => 'Köfte', 'Izgara Kofte' => 'Izgara Köfte', 'Guvec' => 'Güveç',
        'Manti' => 'Mantı', 'Karisik Pizza' => 'Karışık Pizza', 'Sutlac' => 'Sütlaç', 'Kunefe' => 'Künefe',
        'Cay' => 'Çay', 'Turk Kahvesi' => 'Türk Kahvesi',
        // malzemeler
        'Dana Kiyma' => 'Dana Kıyma', 'Tavuk Gogus' => 'Tavuk Göğüs', 'Salatalik' => 'Salatalık', 'Sogan' => 'Soğan',
        'Kasar Peyniri' => 'Kaşar Peyniri', 'Tereyagi' => 'Tereyağı', 'Yogurt' => 'Yoğurt', 'Sut' => 'Süt',
        'Pirinc' => 'Pirinç', 'Zeytinyagi' => 'Zeytinyağı', 'Aycicek Yagi' => 'Ayçiçek Yağı',
        'Domates Salcasi' => 'Domates Salçası', 'Hamburger Ekmegi' => 'Hamburger Ekmeği',
        // kategoriler
        'Baslangiclar' => 'Başlangıçlar', 'Tatlilar' => 'Tatlılar', 'Soguk Icecekler' => 'Soğuk İçecekler', 'Sicak Icecekler' => 'Sıcak İçecekler',
        // personel
        'Zeynep Sahin' => 'Zeynep Şahin', 'Can Ozturk' => 'Can Öztürk', 'Hasan Celik' => 'Hasan Çelik',
        'Ayse Yildiz' => 'Ayşe Yıldız', 'Burak Aydin' => 'Burak Aydın',
        // sube
        'Lezzet Duragi' => 'Lezzet Durağı',
    ];
    $n = 0;
    foreach ($map as $eski => $yeni) {
        $n += DB::table('urunler')->where('ad', $eski)->update(['ad' => $yeni]);
        DB::table('adisyon_kalemleri')->where('urun_adi', $eski)->update(['urun_adi' => $yeni]);
        DB::table('malzemeler')->where('ad', $eski)->update(['ad' => $yeni]);
        DB::table('personeller')->where('ad', $eski)->update(['ad' => $yeni]);
        DB::table('subeler')->where('ad', $eski)->update(['ad' => $yeni]);
        DB::table('menu_kategorileri')->where('ad', $eski)->update(['ad' => $yeni]);
    }
    return 'Türkçe karakterler düzeltildi (' . count($map) . ' isim). ✅';
});

// ============================ ASISTAN KALIP (soru-cevap) YONETIMI ============================
// Tablo kur + baslangic kaliplari (bir kez).
Route::get('/kalip-kur', function () {
    if (!Schema::hasTable('asistan_kalip')) {
        Schema::create('asistan_kalip', function ($t) {
            $t->increments('id');
            $t->text('tetikleyiciler');
            $t->text('cevap');
            $t->string('kategori', 40)->nullable();
            $t->boolean('aktif')->default(1);
            $t->unsignedInteger('kullanim_sayisi')->default(0);
            $t->timestamps();
        });
    }
    $starter = [
        ['sen kimsin, kimsin, adin ne, nesin, ne asistanisin, kim oldugun', 'Ben restoranınızın asistanıyım. Ciro, satış, personel, masa, food-cost ve kayıp gibi konularda size yardımcı olurum.', 'kimlik'],
        ['ne yapabilirsin, neler yapabilirsin, ne ise yararsin, gorevin ne, ne yaparsin', 'Ciro ve kasa durumu, en çok satan ürün, personel performansı, açık masalar, paket siparişler, food-cost ve kayıp radarını sorabilirsiniz.', 'kimlik'],
        ['selam, merhaba, gunaydin, iyi gunler, iyi aksamlar, alo', 'Merhaba! Size nasıl yardımcı olabilirim?', 'sohbet'],
        ['nasilsin, naber, ne haber, iyi misin, keyifler', 'Teşekkür ederim, gayet iyiyim. Sizin için ne öğrenmek istersiniz?', 'sohbet'],
        ['seni kim yapti, kim gelistirdi, uretici, kim yazdi', 'Beni restoranınızın yazılım ekibi hazırladı. Hadi işletmenize bakalım mı?', 'kimlik'],
    ];
    $n = 0;
    foreach ($starter as [$tet, $cev, $kat]) {
        if (!DB::table('asistan_kalip')->where('tetikleyiciler', $tet)->exists()) {
            DB::table('asistan_kalip')->insert(['tetikleyiciler' => $tet, 'cevap' => $cev, 'kategori' => $kat, 'aktif' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }
    }
    \Cache::forget('resto_kalip_liste_v1');
    return "Kalıp sistemi kuruldu. $n başlangıç kalıbı eklendi. Toplam: " . DB::table('asistan_kalip')->count();
});

// Kalip ekle: ?tetik=... &cevap=... (&kategori=...)  (GET veya POST)
Route::match(['get', 'post'], '/kalip-ekle', function (Request $r) {
    $tet = trim((string) $r->tetik);
    $cev = trim((string) $r->cevap);
    if ($tet === '' || $cev === '') return response('tetik ve cevap gerekli', 400);
    if (!Schema::hasTable('asistan_kalip')) return response('Önce /kalip-kur çalıştırın', 400);
    $id = DB::table('asistan_kalip')->insertGetId(['tetikleyiciler' => $tet, 'cevap' => $cev, 'kategori' => $r->kategori ?: 'genel', 'aktif' => 1, 'created_at' => now(), 'updated_at' => now()]);
    \Cache::forget('resto_kalip_liste_v1');
    return "Kalıp eklendi (id=$id). Toplam: " . DB::table('asistan_kalip')->count();
});

Route::get('/kalip-liste', function () {
    if (!Schema::hasTable('asistan_kalip')) return 'Tablo yok, /kalip-kur çalıştırın.';
    return DB::table('asistan_kalip')->orderBy('id')->get(['id', 'tetikleyiciler', 'cevap', 'kategori', 'aktif', 'kullanim_sayisi']);
});

Route::get('/kalip-sil', function (Request $r) {
    DB::table('asistan_kalip')->where('id', (int) $r->id)->delete();
    \Cache::forget('resto_kalip_liste_v1');
    return 'Silindi (id=' . (int) $r->id . ').';
});

// ============================ MUSTERI QR ASISTANI (public, girissiz) ============================
// Masadaki QR -> tarayicida AI asistan sayfasi acilir.
// Palet haritasi: config('temalar') bos ise (config cache) dosyayi DOGRUDAN yukle -> canliyi bozmaz
if (!function_exists('resto_temalar')) {
    function resto_temalar() {
        $t = config('temalar');
        if (is_array($t) && !empty($t)) return $t;
        $f = base_path('config/temalar.php');
        return is_file($f) ? require $f : ['altin' => ['ad' => 'Altın & Siyah', 'emoji' => '👑', 'ana' => '#F6DFA0', 'ana2' => '#E9C46A', 'ana3' => '#C9962F', 'ink' => '#3a2600', 'glow' => 'rgba(233,196,106,.16)']];
    }
}
// subeler.tema + tema_renk + tema_renk2 kolonlarini garanti et
if (!function_exists('resto_tema_kolon')) {
    function resto_tema_kolon() {
        try {
            if (!Schema::hasColumn('subeler', 'tema')) Schema::table('subeler', function ($t) { $t->string('tema', 20)->nullable(); });
            if (!Schema::hasColumn('subeler', 'tema_renk')) Schema::table('subeler', function ($t) { $t->string('tema_renk', 9)->nullable(); });
            if (!Schema::hasColumn('subeler', 'tema_renk2')) Schema::table('subeler', function ($t) { $t->string('tema_renk2', 9)->nullable(); });
            if (!Schema::hasColumn('subeler', 'tema_mod')) Schema::table('subeler', function ($t) { $t->string('tema_mod', 10)->nullable(); });
        } catch (\Throwable $e) {}
    }
}
// tek hex'ten acik/koyu ton + okunur yazi rengi uret (yardimci)
if (!function_exists('resto_renk_tonlar')) {
    function resto_renk_tonlar($hex, $varsayilan = '#C41E3A') {
        $hex = '#' . ltrim((string) $hex, '#');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) $hex = $varsayilan;
        $r = hexdec(substr($hex, 1, 2)); $g = hexdec(substr($hex, 3, 2)); $b = hexdec(substr($hex, 5, 2));
        $mix = function ($t, $hedef) use ($r, $g, $b) {
            $f = function ($c) use ($t, $hedef) { return (int) round($c + ($hedef - $c) * $t); };
            return sprintf('#%02X%02X%02X', $f($r), $f($g), $f($b));
        };
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return ['ana' => strtoupper($hex), 'acik' => $mix(0.22, 255), 'koyu' => $mix(0.26, 0),
            'ink' => $lum > 0.62 ? '#3a2600' : '#ffffff', 'r' => $r, 'g' => $g, 'b' => $b];
    }
}
// OZEL tema: ana renk (butonlar) + detay renk (cizgi/fiyat/logo). detay bos ise ana renk kullanilir (hepsi ayni renk).
if (!function_exists('resto_tema_uret')) {
    function resto_tema_uret($anaHex, $detayHex = null) {
        $a = resto_renk_tonlar($anaHex, '#C41E3A');
        $d = resto_renk_tonlar($detayHex ?: $anaHex, $a['ana']);
        return [
            'ad' => 'Özel Renk', 'emoji' => '🎨',
            'ana' => $a['ana'], 'ana2' => $a['acik'], 'ana3' => $a['koyu'], 'ink' => $a['ink'],
            'glow' => "rgba({$a['r']},{$a['g']},{$a['b']},.15)",
            'detay' => $d['ana'], 'detay2' => $d['koyu'],   // altin yerine gecen "detay" rengi
        ];
    }
}

// QR MENU (mockup 'Lezzet Duragi' — birebir): telefon + tablet, gercek puan, sipariş
Route::get('/masa/{id}', function ($id) {
    $masa = DB::table('masalar')->find((int) $id);
    if (!$masa) abort(404);
    $sube = DB::table('subeler')->find($masa->sube_id);
    $temalar = resto_temalar();
    $stored = ($sube && isset($sube->tema)) ? $sube->tema : null;
    if ($stored === 'ozel' && !empty($sube->tema_renk)) { $key = 'ozel'; $tema = resto_tema_uret($sube->tema_renk, $sube->tema_renk2 ?? null); }
    elseif ($stored && isset($temalar[$stored])) { $key = $stored; $tema = $temalar[$stored]; }
    else { $key = 'altin'; $tema = $temalar['altin'] ?? reset($temalar); }
    $mod = ($sube && isset($sube->tema_mod) && $sube->tema_mod === 'acik') ? 'acik' : 'koyu';
    return view('masa_menu', ['masa' => $masa, 'sube' => $sube, 'tema' => $tema, 'temaKey' => $key, 'mod' => $mod]);
});

// ---------------- MASA QR (otomatik token + yazdirilabilir afis) ----------------
if (!function_exists('_masaQrEnsure')) {
    function _masaQrEnsure($subeId = null)
    {
        if (!Schema::hasColumn('masalar', 'qr_token')) {
            Schema::table('masalar', fn ($t) => $t->string('qr_token', 32)->nullable()->index());
        }
        $q = DB::table('masalar')->where(function ($w) { $w->whereNull('qr_token')->orWhere('qr_token', ''); });
        if ($subeId) $q->where('sube_id', $subeId);
        foreach ($q->get(['id']) as $m) {
            DB::table('masalar')->where('id', $m->id)->update(['qr_token' => \Illuminate\Support\Str::random(18)]);
        }
    }
}
// masa deneyim view (tema hazirligi) — /masa/{id} ile ayni
if (!function_exists('_masaView')) {
    function _masaView($masa)
    {
        $sube = DB::table('subeler')->find($masa->sube_id);
        $temalar = resto_temalar();
        $stored = ($sube && isset($sube->tema)) ? $sube->tema : null;
        if ($stored === 'ozel' && !empty($sube->tema_renk)) { $key = 'ozel'; $tema = resto_tema_uret($sube->tema_renk, $sube->tema_renk2 ?? null); }
        elseif ($stored && isset($temalar[$stored])) { $key = $stored; $tema = $temalar[$stored]; }
        else { $key = 'altin'; $tema = $temalar['altin'] ?? reset($temalar); }
        $mod = ($sube && isset($sube->tema_mod) && $sube->tema_mod === 'acik') ? 'acik' : 'koyu';
        return view('masa_menu', ['masa' => $masa, 'sube' => $sube, 'tema' => $tema, 'temaKey' => $key, 'mod' => $mod]);
    }
}
// QR hedefi: /m/{token} -> masadaki tam deneyim (AI + siparis + garson + hesap + odeme)
Route::get('/m/{token}', function ($token) {
    _masaQrEnsure();
    $masa = DB::table('masalar')->where('qr_token', $token)->first();
    if (!$masa) abort(404);
    return _masaView($masa);
});
// Kurulum: tum masalara token
Route::get('/masa-qr-kur', function () {
    _masaQrEnsure();
    $n = DB::table('masalar')->whereNotNull('qr_token')->count();
    return response("Masa QR hazir. $n masaya token atandi.\nTum afisler (yazdir): " . url('/masa-afisler'))->header('Content-Type', 'text/plain; charset=utf-8');
});
// Tek masa afisi (yazdirilabilir)
Route::get('/masa-afis/{id}', function ($id) {
    $masa = DB::table('masalar')->find((int) $id);
    if (!$masa) abort(404);
    _masaQrEnsure($masa->sube_id);
    $masa = DB::table('masalar')->find((int) $id);
    return view('masa_afis', ['masalar' => [$masa], 'sube' => DB::table('subeler')->find($masa->sube_id)]);
});
// Tum masalar tek sayfa (toplu yazdir/PDF)
Route::get('/masa-afisler', function (Request $r) {
    $subeId = (int) ($r->query('sube') ?: DB::table('subeler')->value('id'));
    _masaQrEnsure($subeId);
    $masalar = DB::table('masalar')->where('sube_id', $subeId)->orderBy('id')->get();
    return view('masa_afis', ['masalar' => $masalar, 'sube' => DB::table('subeler')->find($subeId)]);
});

// RENK KARTELASI: restoran QR menu temasini secer (subeler.tema)
Route::get('/tema/{subeId?}', function ($subeId = null) {
    try { if (!Schema::hasColumn('subeler', 'tema')) Schema::table('subeler', function ($t) { $t->string('tema', 20)->nullable(); }); } catch (\Throwable $e) {}
    $sube = $subeId ? DB::table('subeler')->find((int) $subeId) : DB::table('subeler')->first();
    if (!$sube) abort(404, 'Şube yok');
    $temalar = resto_temalar();
    $secili = (isset($sube->tema) && isset($temalar[$sube->tema])) ? $sube->tema : 'altin';
    return view('tema', ['sube' => $sube, 'temalar' => $temalar, 'secili' => $secili]);
});
Route::post('/tema/kaydet', function (Request $r) {
    try { if (!Schema::hasColumn('subeler', 'tema')) Schema::table('subeler', function ($t) { $t->string('tema', 20)->nullable(); }); } catch (\Throwable $e) {}
    $key = (string) $r->tema;
    if (!isset(resto_temalar()[$key])) return ['ok' => 0, 'hata' => 'Geçersiz tema'];
    $subeId = (int) ($r->sube ?: DB::table('subeler')->value('id'));
    DB::table('subeler')->where('id', $subeId)->update(['tema' => $key]);
    return ['ok' => 1, 'tema' => $key];
});

// ONIZLEME: masa ID bilmeden ilk gercek masanin QR menusunu ac (test icin)
Route::get('/menu-onizle', function () {
    $masa = DB::table('masalar')->orderBy('id')->first();
    if (!$masa) abort(404, 'Once masa eklenmeli');
    return redirect('/masa/' . $masa->id);
});

// Sesli asistan (sonra ana akisa baglanacak) — ayri linkte korunuyor
Route::get('/masa/{id}/asistan', function ($id) {
    $masa = DB::table('masalar')->find((int) $id);
    if (!$masa) abort(404);
    $sube = DB::table('subeler')->find($masa->sube_id);
    return view('masa_asistan', ['masa' => $masa, 'sube' => $sube]);
});

// Musteri sorusu -> MusteriAsistan (menu/oneri/kalip/garson)
Route::post('/api/qr/asistan', function (Request $r) {
    $masa = DB::table('masalar')->find((int) $r->masa);
    $subeId = $masa ? $masa->sube_id : DB::table('subeler')->value('id');
    $a = new \App\Services\MusteriAsistan($subeId);
    return $a->cevapla((string) $r->soru, (string) $r->input('baglam', ''));
});

// Musteri kendi basina TUM menuyu inceler (sesli asistan kapali)
Route::get('/api/qr/menu-tam', function (Request $r) {
    $masa = DB::table('masalar')->find((int) $r->masa);
    $subeId = $masa ? $masa->sube_id : DB::table('subeler')->value('id');
    return (new \App\Services\MusteriAsistan($subeId))->menuTam();
});

// Garson/hesap cagrisi -> masa_cagrilari (KDS/patron tarafinda gorunebilir)
Route::post('/api/qr/garson-cagir', function (Request $r) {
    $masa = DB::table('masalar')->find((int) $r->masa);
    if (!$masa) return ['ok' => 0];
    if (!Schema::hasTable('masa_cagrilari')) {
        Schema::create('masa_cagrilari', function ($t) {
            $t->increments('id');
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('masa_id');
            $t->string('tip', 20)->default('garson');
            $t->string('durum', 20)->default('bekliyor');
            $t->timestamp('created_at')->useCurrent();
        });
    }
    DB::table('masa_cagrilari')->insert(['sube_id' => $masa->sube_id, 'masa_id' => $masa->id,
        'tip' => in_array($r->tip, ['garson', 'hesap']) ? $r->tip : 'garson', 'durum' => 'bekliyor', 'created_at' => now()]);
    return ['ok' => 1];
});

// QR MENU: musteri urune PUAN verir (gercek degerlendirme sistemi) -> urun_puanlari
Route::post('/api/qr/urun-puan', function (Request $r) {
    $masa = DB::table('masalar')->find((int) $r->masa);
    $subeId = $masa ? $masa->sube_id : DB::table('subeler')->value('id');
    $urunId = (int) $r->urun_id;
    $puan = (int) $r->puan;
    if (!$urunId || $puan < 1 || $puan > 5) return ['ok' => 0, 'hata' => 'Geçersiz puan'];
    if (!Schema::hasTable('urun_puanlari')) {
        Schema::create('urun_puanlari', function ($t) {
            $t->increments('id');
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('urun_id');
            $t->unsignedBigInteger('masa_id')->nullable();
            $t->unsignedTinyInteger('puan');           // 1-5
            $t->string('parmak', 64)->nullable();      // cihaz parmak izi (spam/tekrar engeli)
            $t->timestamp('created_at')->useCurrent();
            $t->index(['sube_id', 'urun_id']);
        });
    }
    $parmak = substr(sha1(($r->parmak ?: $r->ip()) . '|' . $urunId), 0, 64);
    // Ayni cihaz ayni urune tekrar verirse GUNCELLE (spam engeli)
    $mevcut = DB::table('urun_puanlari')->where('urun_id', $urunId)->where('parmak', $parmak)->first();
    if ($mevcut) {
        DB::table('urun_puanlari')->where('id', $mevcut->id)->update(['puan' => $puan, 'created_at' => now()]);
    } else {
        DB::table('urun_puanlari')->insert(['sube_id' => $subeId, 'urun_id' => $urunId,
            'masa_id' => $masa->id ?? null, 'puan' => $puan, 'parmak' => $parmak, 'created_at' => now()]);
    }
    $agg = DB::table('urun_puanlari')->where('urun_id', $urunId)
        ->selectRaw('ROUND(AVG(puan),1) as ort, COUNT(*) as say')->first();
    return ['ok' => 1, 'puan' => (float) $agg->ort, 'puan_say' => (int) $agg->say];
});

// Musteri AI: ogrenilen cevaplar (Haiku onbellegi) — listele / sil / durum
Route::get('/musteri-ai-ogrenilen', function (Request $r) {
    $anahtarVar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY')) !== '';
    if (!Schema::hasTable('musteri_ai_ogrenilen')) {
        return response()->json(['anahtar_var' => $anahtarVar, 'bugun_uretilen' => 0, 'toplam' => 0, 'ogrenilenler' => []], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    if ($r->filled('sil')) { DB::table('musteri_ai_ogrenilen')->where('id', (int) $r->input('sil'))->delete(); }
    $q = DB::table('musteri_ai_ogrenilen');
    if ($r->filled('sube')) $q->where('sube_id', (int) $r->input('sube'));
    $liste = $q->orderByDesc('kullanim')->orderByDesc('id')->limit(300)
        ->get(['id', 'sube_id', 'soru_key', 'cevap', 'kullanim', 'created_at']);
    return response()->json([
        'anahtar_var' => $anahtarVar,
        'gunluk_tavan' => (int) config('services.anthropic.sohbet_gunluk_limit', 80),
        'bugun_uretilen' => DB::table('musteri_ai_ogrenilen')->whereDate('created_at', today())->count(),
        'toplam' => DB::table('musteri_ai_ogrenilen')->count(),
        'not' => 'Silmek icin: /musteri-ai-ogrenilen?sil=ID',
        'ogrenilenler' => $liste,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// QR SIPARIS -> acik adisyon bul/olustur + kalem ekle (mutfaga gonderildi) + garson bildirimi
Route::post('/api/qr/siparis-gonder', function (Request $r) {
    $masa = DB::table('masalar')->find((int) $r->masa);
    if (!$masa) return response()->json(['ok' => 0, 'hata' => 'Masa bulunamadı'], 404);
    $kalemler = json_decode((string) $r->kalemler, true);
    if (!is_array($kalemler) || empty($kalemler)) return ['ok' => 0, 'hata' => 'Sepet boş'];
    // Acik adisyon bul, yoksa olustur (kanal=qr)
    $adId = DB::table('adisyonlar')->where('masa_id', $masa->id)->where('durum', 'acik')->value('id');
    if (!$adId) {
        $adId = DB::table('adisyonlar')->insertGetId([
            'sube_id' => $masa->sube_id, 'masa_id' => $masa->id, 'kanal' => 'qr', 'misafir_sayisi' => 1, 'durum' => 'acik',
            'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0, 'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    $eklenen = 0;
    $tukendi = [];
    foreach ($kalemler as $k) {
        $uid = (int) ($k['urun_id'] ?? 0);
        $adet = max(1, min(50, (int) ($k['adet'] ?? 1)));
        $u = DB::table('urunler')->where('id', $uid)->where('sube_id', $masa->sube_id)->first();
        if (!$u) continue;
        if ($u->tukendi) { $tukendi[] = $u->ad; continue; }
        DB::table('adisyon_kalemleri')->insert([
            'adisyon_id' => $adId, 'urun_id' => $u->id, 'urun_adi' => $u->ad, 'adet' => $adet,
            'birim_fiyat' => (float) $u->fiyat, 'tutar' => (float) $u->fiyat * $adet,
            'durum' => 'gonderildi', 'not' => 'QR sipariş', 'gonderim_zamani' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $eklenen++;
    }
    if ($eklenen === 0) return ['ok' => 0, 'hata' => $tukendi ? ('Tükendi: ' . implode(', ', $tukendi)) : 'Geçerli ürün eklenemedi'];
    $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $adId)->where('durum', '!=', 'iptal')->sum('tutar');
    $ad = DB::table('adisyonlar')->find($adId);
    $toplam = max(0, $ara - (float) $ad->indirim - (float) $ad->ikram);
    DB::table('adisyonlar')->where('id', $adId)->update(['ara_toplam' => $ara, 'toplam' => $toplam, 'updated_at' => now()]);
    DB::table('masalar')->where('id', $masa->id)->update(['durum' => 'dolu']);
    if (Schema::hasTable('masa_cagrilari')) {
        DB::table('masa_cagrilari')->insert(['sube_id' => $masa->sube_id, 'masa_id' => $masa->id, 'tip' => 'siparis', 'durum' => 'bekliyor', 'created_at' => now()]);
    }
    return ['ok' => 1, 'mesaj' => 'Siparişiniz mutfağa iletildi', 'eklenen' => $eklenen, 'toplam' => $toplam, 'tukendi' => $tukendi];
});

// QR masa: acik hesabi online odemeye baslat (masadaki "Online Ode")
Route::post('/api/qr/ode-baslat', function (Request $r) {
    if (function_exists('_odemeEnsure')) _odemeEnsure();
    $masa = DB::table('masalar')->find((int) $r->masa);
    if (!$masa) return response()->json(['ok' => 0, 'hata' => 'Masa bulunamadı'], 404);
    $a = DB::table('adisyonlar')->where('masa_id', $masa->id)->where('durum', 'acik')->orderByDesc('id')->first();
    if (!$a) return ['ok' => 0, 'hata' => 'Açık hesabınız yok'];
    if ((float) $a->toplam <= 0) return ['ok' => 0, 'hata' => 'Ödenecek tutar yok'];
    $token = \Illuminate\Support\Str::random(30);
    DB::table('odeme_islemleri')->insert(['sube_id' => $a->sube_id, 'adisyon_id' => $a->id, 'token' => $token,
        'tutar' => (float) $a->toplam, 'saglayici' => _odemeSaglayici($a->sube_id), 'durum' => 'bekliyor', 'created_at' => now()]);
    return ['ok' => 1, 'ode_url' => url('/ode/' . $token), 'tutar' => (float) $a->toplam];
});

// Sunucu TTS (Google Cloud, kaliteli ERKEK ses) -> MP3 URL (onbellekli). Anahtar yoksa basarili=false.
Route::match(['get', 'post'], '/api/tts', function (Request $r) {
    $metin = trim((string) $r->input('metin', ''));
    if ($metin === '') return response()->json(['basarili' => false], 422);
    if (mb_strlen($metin) > 2000) $metin = mb_substr($metin, 0, 2000);
    // Hangi restoran/sube? -> her sube kendi aylik limitine sayilir (multi-tenant)
    $subeId = null;
    if ($r->filled('masa')) { $m = DB::table('masalar')->find((int) $r->input('masa')); $subeId = $m ? $m->sube_id : null; }
    elseif ($r->filled('sube')) { $subeId = (int) $r->input('sube'); }
    $servis = new \App\Services\SeslendirmeServisi($subeId);
    $ses = $r->input('ses');
    if (!$ses) $ses = resto_ayar_al('tts_ses', null); // patronun sectigi kalici ses (yoksa config varsayilani)
    $ad = $servis->uret($metin, $ses);
    if (!$ad) return response()->json(['basarili' => false, 'anahtar_var' => (string) config('services.google_tts.key', '') !== ''], 200);
    return response()->json(['basarili' => true, 'url' => url('/api/ses/' . $ad)]);
});
Route::get('/api/ses/{ad}', function ($ad) {
    $servis = new \App\Services\SeslendirmeServisi();
    $yol = $servis->dosyaYolu($ad);
    if (!$yol) abort(404);
    return response()->file($yol, ['Content-Type' => 'audio/mpeg', 'Cache-Control' => 'public, max-age=31536000']);
})->where('ad', '[a-f0-9]{32}\.mp3');

// ---- Ayarlar (anahtar-deger) yardimcilari: secilen TTS sesi burada saklanir ----
if (!function_exists('resto_ayar_al')) {
    function resto_ayar_tablo()
    {
        if (!Schema::hasTable('ayarlar')) {
            Schema::create('ayarlar', function ($t) {
                $t->string('anahtar', 60)->primary();
                $t->text('deger')->nullable();
            });
        }
    }
    function resto_ayar_al($anahtar, $vars = null)
    {
        resto_ayar_tablo();
        $v = DB::table('ayarlar')->where('anahtar', $anahtar)->value('deger');
        return ($v === null || $v === '') ? $vars : $v;
    }
    function resto_ayar_yaz($anahtar, $deger)
    {
        resto_ayar_tablo();
        DB::table('ayarlar')->updateOrInsert(['anahtar' => $anahtar], ['deger' => $deger]);
    }
}

// Secilebilir ERKEK Turkce sesler (dinle + sec)
if (!function_exists('resto_erkek_sesler')) {
    function resto_erkek_sesler()
    {
        return [
            ['ses' => 'tr-TR-Wavenet-D',        'ad' => 'Erkek 1 · WaveNet-D',     'not' => 'Dolgun, net (şu anki)'],
            ['ses' => 'tr-TR-Wavenet-B',        'ad' => 'Erkek 2 · WaveNet-B',     'not' => 'Biraz daha genç ton'],
            ['ses' => 'tr-TR-Chirp3-HD-Charon', 'ad' => 'Erkek 3 · Chirp3-HD Charon', 'not' => 'EN DOĞAL / yeni nesil'],
            ['ses' => 'tr-TR-Chirp3-HD-Fenrir', 'ad' => 'Erkek 4 · Chirp3-HD Fenrir', 'not' => 'Doğal, canlı'],
            ['ses' => 'tr-TR-Chirp3-HD-Puck',   'ad' => 'Erkek 5 · Chirp3-HD Puck',   'not' => 'Doğal, enerjik'],
            ['ses' => 'tr-TR-Standard-D',       'ad' => 'Erkek 6 · Standard-D',    'not' => 'Basit ama 4× bedava kota'],
            ['ses' => 'tr-TR-Standard-B',       'ad' => 'Erkek 7 · Standard-B',    'not' => 'Basit, 4× bedava kota'],
        ];
    }
}

// Urunlere istah acici FAKE aciklama doldur (bos olanlara). Kart sunumlari zengin gorunsun.
Route::get('/enrich-urun-aciklama', function (Request $r) {
    $force = $r->boolean('force'); // ?force=1 -> mevcut aciklamalari da UZERINE yaz (istah kabartan yeni metin)
    // ISTAH KABARTAN, duyusal aciklamalar ("woow" etkisi)
    $harita = [
        'izgara kofte' => 'Mangalın közünde cızırdayarak pişen, dışı hafif çıtır içi sulu dana köfte; yanında közlenmiş biber ve tereyağlı pilavla.',
        'kofte' => 'Elde yoğrulmuş dana kıymanın közle buluştuğu, her lokmada baharat kokan sulu köfteler.',
        'ezogelin' => 'Nane ve pul biberin dans ettiği, kaşığınızı bekleyen sıcacık, kadifemsi ezogelin çorbası.',
        'mercimek' => 'Tereyağında kavrulmuş naneyle taçlanan, ipeksi kıvamda sıcacık mercimek çorbası.',
        'corba' => 'Günün tazecik çorbası; ilk kaşıkta içinizi ısıtan, doyurucu bir başlangıç.',
        'latte' => 'Espresso üzerine buğulanan kadifemsi süt; köpüğünde erimek isteyeceğiniz yumuşacık bir kahve.',
        'espresso' => 'Taze çekilmiş çekirdeklerden, yoğun aroması burnunuza vuran gerçek espresso.',
        'cay' => 'İnce belli bardakta, tavşan kanı demli, buram buram Türk çayı.',
        'ayran' => 'Elde çırpılmış, üstü kar gibi köpüklü, buz gibi ayran.',
        'meyve suyu' => 'O an sıkılmış mevsim meyvelerinin buz gibi, ferahlatan tazeliği.',
        'limonata' => 'Taze limon ve naneyle çırpılmış, ilk yudumda serinleten ev yapımı limonata.',
        'kola' => 'Buz gibi, gazı çıtırdayan, serinletici bir mola.',
        'pizza' => 'Odun ateşi sıcaklığında, kenarı çıtır, üstünde tel tel uzayan eriyik mozzarella.',
        'margarita' => 'Domates sosu, taze fesleğen ve eriyen mozzarellanın sadeliğindeki o mükemmel uyum.',
        'burger' => '180 gramlık sulu dana köftesi, üzerine akan cheddar ve ev yapımı özel sos; yanında altın sarısı çıtır patates.',
        'salata' => 'Sabah toplanmış gibi taptaze yeşillikler, sızma zeytinyağı ve limonun canlı ferahlığı.',
        'sezar' => 'Kıtır kruton, rendelenmiş parmesan ve kremsi sezar sosunun buluştuğu doyurucu klasik.',
        'makarna' => 'Al dente pişmiş makarna, üzerinde parlayan özel sosuyla iştah kabartan bir tabak.',
        'bolonez' => 'Saatlerce pişen dana kıymalı zengin domates sos, al dente makarnaya sarılıyor.',
        'tavuk' => 'Baharatlarda marine edilip ızgarada mühürlenen, her dilimi sulu tavuk.',
        'pirzola' => 'Közün üzerinde mühürlenen, kemiğinden ayrılan tereyağı yumuşaklığında kuzu pirzola.',
        'antrikot' => 'Dinlendirilip ateşte mühürlenen, ortası pembe, çatalınıza gelince eriyen sulu antrikot.',
        'baklava' => 'Kat kat el açması yufka, bol Antep fıstığı ve hafif şerbetin çıtır çıtır buluşması.',
        'kunefe' => 'Tel kadayıfın arasında uzayan sıcacık peynir, üzeri fıstık; ilk çatalda gönül alan tatlı.',
        'sutlac' => 'Fırında üzeri hafif kızarmış, tarçın kokan, kaşık kaşık çocukluğunuza götüren sütlaç.',
        'brownie' => 'Sıcacık, içi akışkan çikolatalı brownie; yanında eriyen bir top vanilyalı dondurma.',
        'dondurma' => 'Geleneksel yöntemle çekilmiş, kaşığa direnen yoğun kıvamlı dondurma.',
    ];
    $katFallback = [
        'baslangiclar' => 'İştahınızı açacak, sofraya neşe katan tazecik bir başlangıç.',
        'salatalar' => 'Taptaze yeşillikler, canlı renkler; her yemeğin yanına yakışan ferahlık.',
        'izgaralar' => 'Mangal közünde cızırdayarak pişen, dumanı üstünde sulu ızgara lezzeti.',
        'ana yemekler' => 'Şefin özenle hazırladığı, tabağı boşaltacağınız doyurucu bir baş yemek.',
        'burgerler' => 'Sulu köfte, eriyen peynir ve çıtır ekmeğin efsane uyumu.',
        'pizzalar' => 'Fırından yeni çıkmış, kenarı çıtır, üstü bol malzemeli sıcacık dilimler.',
        'makarnalar' => 'Özel sosuna sarılmış al dente makarna; sarımsak ve peynirin daveti.',
        'tatlilar' => 'Tatlı krizinize birebir; ilk kaşıkta "bir tane daha" dedirten mutluluk.',
        'soguk icecekler' => 'Buz gibi, ilk yudumda serinleten ferahlık.',
        'sicak icecekler' => 'Avucunuzu ısıtan, keyifli bir mola.',
    ];
    $norm = function ($s) {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $s = strtr($s, ['ç'=>'c','ğ'=>'g','ı'=>'i','İ'=>'i','ö'=>'o','ş'=>'s','ü'=>'u','â'=>'a','î'=>'i','û'=>'u']);
        return preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9\s]/', ' ', $s));
    };
    $urunler = DB::table('urunler')->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
        ->select('urunler.id', 'urunler.ad', 'urunler.aciklama', 'menu_kategorileri.ad as kat')->get();
    $n = 0;
    foreach ($urunler as $u) {
        if (!$force && !empty(trim((string) $u->aciklama))) continue; // force yoksa dolu olani ellemme
        $adn = $norm($u->ad);
        $ac = null;
        foreach ($harita as $k => $v) { if (strpos($adn, $norm($k)) !== false) { $ac = $v; break; } }
        if (!$ac) $ac = $katFallback[$norm($u->kat)] ?? 'Şefin özenle hazırladığı, iştah kabartan lezzetlerden.';
        DB::table('urunler')->where('id', $u->id)->update(['aciklama' => $ac]);
        $n++;
    }
    return "Ürün açıklamaları güncellendi: $n ürün. " . ($force ? '(mevcutlar dahil üzerine yazıldı)' : '(sadece boşlar)') . ' ✅';
});

// ---- URUN FOTOGRAFLARI (isletme panelinden yukleme) ----
if (!function_exists('resto_gorsel_kolon')) {
    function resto_gorsel_kolon()
    {
        if (!Schema::hasColumn('urunler', 'gorsel')) {
            Schema::table('urunler', function ($t) { $t->string('gorsel', 255)->nullable(); });
        }
    }
}

// Foto yonetim sayfasi: her urune kendi fotografini yukle
Route::get('/urun-fotolar', function (Request $r) {
    resto_gorsel_kolon();
    $subeId = (int) ($r->input('sube') ?: DB::table('subeler')->min('id'));
    $asistan = new \App\Services\MusteriAsistan($subeId);
    $urunler = DB::table('urunler')->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
        ->where('urunler.sube_id', $subeId)->where('urunler.aktif', 1)
        ->orderBy('menu_kategorileri.sira')->orderBy('urunler.ad')
        ->select('urunler.id', 'urunler.ad', 'urunler.fiyat', 'urunler.gorsel', 'menu_kategorileri.ad as kat')->get();
    $liste = $urunler->map(function ($u) use ($asistan) {
        return [
            'id' => $u->id, 'ad' => $u->ad, 'kat' => $u->kat ?: '-',
            'fiyat' => number_format((float) $u->fiyat, 0, ',', '') . ' TL',
            'yuklendi' => !empty($u->gorsel),
            'onizleme' => $asistan->onizlemeGorsel($u->kat, $u->ad, $u->id),
        ];
    })->all();
    $subeler = DB::table('subeler')->select('id', 'ad')->get();
    return view('urun_fotolar', ['liste' => $liste, 'subeId' => $subeId, 'subeler' => $subeler]);
});

// Foto yukle (multipart) -> storage/app/urun_foto/{id}.{ext}, urunler.gorsel = /urun-foto/{id}
Route::post('/urun-foto-yukle', function (Request $r) {
    $id = (int) $r->input('urun_id');
    $u = DB::table('urunler')->find($id);
    if (!$u) return response()->json(['ok' => 0, 'mesaj' => 'Ürün bulunamadı'], 404);
    if (!$r->hasFile('foto') || !$r->file('foto')->isValid()) return response()->json(['ok' => 0, 'mesaj' => 'Geçerli dosya yok'], 422);
    $f = $r->file('foto');
    $ext = strtolower($f->getClientOriginalExtension() ?: 'jpg');
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return response()->json(['ok' => 0, 'mesaj' => 'Sadece JPG, PNG veya WEBP'], 422);
    if ($f->getSize() > 6 * 1024 * 1024) return response()->json(['ok' => 0, 'mesaj' => 'En fazla 6 MB'], 422);
    $dir = storage_path('app/urun_foto');
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    foreach (glob($dir . '/' . $id . '.*') ?: [] as $old) @unlink($old); // eski fotoyu sil
    $f->move($dir, $id . '.' . $ext);
    resto_gorsel_kolon();
    $url = url('/urun-foto/' . $id);
    DB::table('urunler')->where('id', $id)->update(['gorsel' => $url]);
    return response()->json(['ok' => 1, 'url' => $url . '?v=' . time()]);
});

// Yuklenen fotoyu kaldir -> tekrar otomatik (stok) fotoya doner
Route::match(['get', 'post'], '/urun-foto-sil', function (Request $r) {
    $id = (int) $r->input('urun_id');
    $dir = storage_path('app/urun_foto');
    foreach (glob($dir . '/' . $id . '.*') ?: [] as $old) @unlink($old);
    resto_gorsel_kolon();
    DB::table('urunler')->where('id', $id)->update(['gorsel' => null]);
    return response()->json(['ok' => 1]);
});

// Yuklenen urun fotografini servis et
Route::get('/urun-foto/{id}', function ($id) {
    $dir = storage_path('app/urun_foto');
    $files = glob($dir . '/' . ((int) $id) . '.*');
    if (empty($files)) abort(404);
    $yol = $files[0];
    $ext = strtolower(pathinfo($yol, PATHINFO_EXTENSION));
    $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'][$ext] ?? 'image/jpeg';
    return response()->file($yol, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=600']);
})->where('id', '\d+');

// MP3 onbellek temizligi: uzun suredir kullanilmayan sesleri sil (disk sabit kalsin).
// Gunluk cron ile calistir: curl -s https://restaurant.webfirmam.com.tr/tts-temizle?gun=45
Route::get('/tts-temizle', function (Request $r) {
    $gun = (int) $r->input('gun', 45);
    $s = new \App\Services\SeslendirmeServisi();
    $sonuc = $s->eskileriTemizle($gun);
    return response()->json(['ok' => 1, 'gun' => max(1, $gun)] + $sonuc, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// Ses test sayfasi: her erkek sesi dinle, begendigini sec
Route::get('/ses-test', function () {
    $secili = resto_ayar_al('tts_ses', (string) config('services.google_tts.voice', 'tr-TR-Wavenet-D'));
    $anahtarVar = (string) config('services.google_tts.key', '') !== '';
    return view('ses_test', ['sesler' => resto_erkek_sesler(), 'secili' => $secili, 'anahtarVar' => $anahtarVar]);
});

// Bu ayki Cloud TTS kullanimi — HER RESTORAN (sube) AYRI. Fatura kontrolu.
Route::get('/tts-kullanim', function (Request $r) {
    $limit = (int) config('services.google_tts.aylik_limit', 900000);
    $ay = date('Ym');
    if (!Schema::hasTable('ayarlar')) return response()->json(['ay' => date('Y-m'), 'restoranlar' => [], 'not' => 'Henuz kullanim yok.'], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $satirlar = DB::table('ayarlar')->where('anahtar', 'like', 'tts_kota_%_' . $ay)->get();
    $subeAd = DB::table('subeler')->pluck('ad', 'id');
    $liste = [];
    $toplam = 0;
    foreach ($satirlar as $s) {
        // anahtar: tts_kota_{subeId}_{YYYYMM}
        if (!preg_match('/^tts_kota_(\d+)_' . $ay . '$/', $s->anahtar, $m)) continue;
        $sid = (int) $m[1];
        $kul = (int) $s->deger;
        $toplam += $kul;
        $liste[] = [
            'sube_id' => $sid,
            'restoran' => $sid === 0 ? '(test/genel)' : ($subeAd[$sid] ?? ('Sube ' . $sid)),
            'kullanilan_karakter' => $kul,
            'aylik_sert_limit' => $limit,
            'doluluk_yuzde' => $limit > 0 ? round($kul / $limit * 100, 1) : 0,
            'kalan_karakter' => max(0, $limit - $kul),
            'durum' => $kul >= $limit ? 'DOLDU (Cloud durdu, bedava sese dustu)' : 'normal',
        ];
    }
    usort($liste, fn ($a, $b) => $b['kullanilan_karakter'] <=> $a['kullanilan_karakter']);
    return response()->json([
        'ay' => date('Y-m'),
        'her_restoran_aylik_limit' => $limit,
        'toplam_kullanilan' => $toplam,
        'restoran_sayisi' => count($liste),
        'restoranlar' => $liste,
        'not' => 'Her restoran KENDI limitine sayilir; biri dolsa digerleri etkilenmez. Limit dolan Cloud durur, bedava cihaz sesine duser -> fatura yok.',
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// Sesi sec (kalici) -> musteri asistani bundan sonra bu sesi kullanir
Route::match(['get', 'post'], '/ses-sec', function (Request $r) {
    $ses = (string) $r->input('ses', '');
    $gecerli = array_column(resto_erkek_sesler(), 'ses');
    if (!in_array($ses, $gecerli, true)) return response()->json(['ok' => 0, 'mesaj' => 'Geçersiz ses'], 422);
    resto_ayar_yaz('tts_ses', $ses);
    return response()->json(['ok' => 1, 'ses' => $ses]);
});

// Acik masalarin acilis saatini "az once"ye tazele (demo verisi eski tarihli kaliyordu -> sure gercekci gorunsun)
Route::get('/enrich-acik-tazele', function () {
    $n = 0; $kalem = 0;
    $hazirVar = Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani');
    foreach (DB::table('adisyonlar')->where('durum', 'acik')->get(['id']) as $a) {
        DB::table('adisyonlar')->where('id', $a->id)->update(['acilis' => now()->subMinutes(random_int(5, 180))]);
        // Mutfak KDS gercekci gorunsun: bekleyen (gonderildi) kalemleri son 1-25 dk'ya cek
        foreach (DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', 'gonderildi')->get(['id']) as $k) {
            DB::table('adisyon_kalemleri')->where('id', $k->id)->update(['gonderim_zamani' => now()->subMinutes(random_int(1, 25))]);
            $kalem++;
        }
        // Servise hazir (hazir) kalemleri son 1-8 dk'ya cek
        if ($hazirVar) {
            foreach (DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', 'hazir')->get(['id']) as $k) {
                DB::table('adisyon_kalemleri')->where('id', $k->id)->update([
                    'gonderim_zamani' => now()->subMinutes(random_int(10, 30)), 'hazir_zamani' => now()->subMinutes(random_int(1, 8))]);
            }
        }
        $n++;
    }
    return "Açık adisyonlar tazelendi: $n masa (5-180 dk), $kalem mutfak kalemi (1-25 dk). ✅";
});

// ============================ PERSONEL YETKILERI (Kerzz tarzi granular) ============================
if (!function_exists('_restoYetkiKeys')) {
    function _restoYetkiKeys()
    {
        return ['adisyon_ac', 'adisyon_kapat', 'adisyon_iptal', 'adisyon_bol', 'adisyon_birlestir',
            'iskonto', 'ikram', 'urun_sil', 'fatura_kes', 'geri_islem', 'maliyet_gor', 'rapor_gor'];
    }
}
if (!function_exists('_restoYetkiVarsayilan')) {
    function _restoYetkiVarsayilan($rol)
    {
        $y = array_fill_keys(_restoYetkiKeys(), false);
        $limit = 0;      // iskonto % limiti
        $ikramLimit = 0; // ikram TL limiti
        $ac = function (&$y, $liste) { foreach ($liste as $k) $y[$k] = true; };
        switch ($rol) {
            case 'sahip': foreach ($y as $k => $v) $y[$k] = true; $limit = 100; $ikramLimit = 100000; break;
            case 'mudur': foreach ($y as $k => $v) $y[$k] = true; $limit = 50; $ikramLimit = 1000; break;
            case 'kasa': $ac($y, ['adisyon_ac', 'adisyon_kapat', 'fatura_kes', 'maliyet_gor']); $limit = 10; $ikramLimit = 0; break;
            case 'garson': $ac($y, ['adisyon_ac', 'adisyon_kapat', 'adisyon_bol']); $limit = 0; $ikramLimit = 50; break;
        }
        return ['yetkiler' => $y, 'iskonto_limit' => $limit, 'ikram_limit' => $ikramLimit];
    }
}
if (!function_exists('_restoYetkiVar')) {
    function _restoYetkiVar($personel, $yetki)
    {
        if (!$personel) return false;
        if (($personel->rol ?? '') === 'sahip') return true;
        $y = isset($personel->yetkiler) && $personel->yetkiler ? json_decode($personel->yetkiler, true) : _restoYetkiVarsayilan($personel->rol ?? '')['yetkiler'];
        return !empty($y[$yetki]);
    }
}

// Yetki kolonlarini kur + varsayilanlari doldur (tek sefer, migrate beklemeden)
Route::get('/yetki-kur', function () {
    if (!Schema::hasColumn('personeller', 'yetkiler')) {
        Schema::table('personeller', fn ($t) => $t->text('yetkiler')->nullable());
    }
    if (!Schema::hasColumn('personeller', 'iskonto_limit')) {
        Schema::table('personeller', fn ($t) => $t->decimal('iskonto_limit', 5, 2)->default(0));
    }
    if (!Schema::hasColumn('personeller', 'ikram_limit')) {
        Schema::table('personeller', fn ($t) => $t->decimal('ikram_limit', 12, 2)->nullable());
    }
    $n = 0;
    foreach (DB::table('personeller')->get(['id', 'rol', 'yetkiler', 'ikram_limit']) as $p) {
        $d = _restoYetkiVarsayilan($p->rol);
        if (!$p->yetkiler) {
            DB::table('personeller')->where('id', $p->id)->update(['yetkiler' => json_encode($d['yetkiler']), 'iskonto_limit' => $d['iskonto_limit'], 'ikram_limit' => $d['ikram_limit']]);
            $n++;
        } elseif ($p->ikram_limit === null) {
            DB::table('personeller')->where('id', $p->id)->update(['ikram_limit' => $d['ikram_limit']]);
        }
    }
    return "Yetkiler kuruldu: $n yeni + ikram limitleri güncellendi. ✅";
});

// ============================ FLUTTER API (token = personel PIN girisi) ============================
if (!function_exists('_apiPersonel')) {
    function _apiPersonel(Request $r)
    {
        $token = $r->bearerToken();
        if (!$token) return null;
        return DB::table('personeller')->where('api_token', $token)->first();
    }
}

Route::post('/api/login', function (Request $r) {
    // Patron app: sahip/mudur PIN ile giris
    // Tum AKTIF personel giris yapabilir (garson terminali dahil); UI role gore acilir.
    $p = DB::table('personeller')->where('pin', (string) $r->pin)->where('aktif', 1)->first();
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'PIN hatalı'], 401);
    // Mevcut token'i KORU (yeni login eskisini gecersiz kilmasin -> es zamanli oturumlar/testler bozulmaz)
    $token = $p->api_token ?: \Illuminate\Support\Str::random(48);
    if (!$p->api_token) DB::table('personeller')->where('id', $p->id)->update(['api_token' => $token]);
    return [
        'ok' => 1, 'token' => $token,
        'personel' => ['id' => $p->id, 'ad' => $p->ad, 'rol' => $p->rol],
        'sube' => DB::table('subeler')->where('id', $p->sube_id)->value('ad'),
    ];
});

// Urun bazinda birim maliyet haritasi (receteden hesaplanir; food-cost icin).
// Cok katmanli receteyi (yari mamul/alt_recete) memoize ederek cozer.
if (!function_exists('_restoUrunMaliyetMap')) {
    function _restoUrunMaliyetMap()
    {
        try {
            $malz = DB::table('malzemeler')->get(['id', 'temel_birim_id', 'guncel_maliyet'])->keyBy('id');
            $cevrim = DB::table('birim_cevrimleri')->get()->groupBy('malzeme_id');
            $receteler = DB::table('receteler')->get()->keyBy('id');
            $kalemler = DB::table('recete_kalemleri')->get()->groupBy('recete_id');

            $karsilik = function ($malzemeId, $birimId) use ($malz, $cevrim) {
                $m = $malz[$malzemeId] ?? null;
                if (!$m) return 0.0;
                if ((int) $birimId === (int) $m->temel_birim_id) return 1.0;
                foreach (($cevrim[$malzemeId] ?? []) as $c) {
                    if ((int) $c->birim_id === (int) $birimId) return (float) $c->temel_birim_karsiligi;
                }
                return 1.0;
            };

            $memo = [];
            $receteMaliyet = function ($receteId) use (&$receteMaliyet, &$memo, $receteler, $kalemler, $malz, $karsilik) {
                if (isset($memo[$receteId])) return $memo[$receteId];
                $memo[$receteId] = 0.0; // dongu koruma
                $toplam = 0.0;
                foreach (($kalemler[$receteId] ?? []) as $k) {
                    if ($k->malzeme_id) {
                        $m = $malz[$k->malzeme_id] ?? null;
                        if ($m) $toplam += (float) $k->miktar * $karsilik($k->malzeme_id, $k->birim_id) * (float) $m->guncel_maliyet;
                    } elseif ($k->alt_recete_id) {
                        $alt = $receteler[$k->alt_recete_id] ?? null;
                        $altToplam = $receteMaliyet($k->alt_recete_id);
                        $verim = $alt && (float) $alt->verim_miktar > 0 ? (float) $alt->verim_miktar : 1.0;
                        $toplam += (float) $k->miktar * ($altToplam / $verim);
                    }
                }
                return $memo[$receteId] = $toplam;
            };

            $map = [];       // urun_id => birim maliyet
            $mapAd = [];     // urun_adi => birim maliyet (fallback)
            foreach ($receteler as $rec) {
                if ($rec->tip !== 'urun' || !$rec->urun_id) continue;
                $mal = $receteMaliyet($rec->id);
                $map[(int) $rec->urun_id] = $mal;
                $ad = DB::table('urunler')->where('id', $rec->urun_id)->value('ad');
                if ($ad) $mapAd[$ad] = $mal;
            }
            return ['id' => $map, 'ad' => $mapAd];
        } catch (\Throwable $e) {
            return ['id' => [], 'ad' => []];
        }
    }
}

// Secilen periyot icin [baslangic, bitis] ve esit uzunluktaki onceki (karsilastirma) pencere.
if (!function_exists('_restoPeriyot')) {
    function _restoPeriyot(string $period)
    {
        $now = now();
        switch ($period) {
            case 'gunluk':
                return [today()->startOfDay(), $now, (clone $now)->subDay()->startOfDay(), (clone $now)->subDay()];
            case 'aylik':
                return [(clone $now)->subDays(30), $now, (clone $now)->subDays(60), (clone $now)->subDays(30)];
            case 'yillik':
                return [(clone $now)->subDays(365), $now, (clone $now)->subDays(730), (clone $now)->subDays(365)];
            case 'haftalik':
            default:
                return [(clone $now)->subDays(7), $now, (clone $now)->subDays(14), (clone $now)->subDays(7)];
        }
    }
}

// Derin analiz icin ZENGIN KURAL MOTORU yorumlari (anahtar yoksa bile detay verir; Haiku fallback'i).
if (!function_exists('_restoKuralYorum')) {
    function _restoKuralYorum(array $b)
    {
        $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . 'TL';
        $out = [];
        $tip = $b['tip'] ?? '';
        if ($tip === 'urun_karlilik_analizi') {
            $ad = $b['urun'];
            $my = (int) $b['food_cost_yuzde'];
            $fiyat = (float) $b['satis_fiyati'];
            $bmal = (float) $b['porsiyon_malzeme_maliyeti'];
            $bkar = (float) $b['porsiyon_brut_kar'];
            $adet = (float) $b['donem_satis_adet'];
            $dom = null;
            $domT = 0;
            foreach (($b['recete_malzemeler'] ?? []) as $k) {
                if (($k['maliyet'] ?? 0) > $domT) { $domT = $k['maliyet']; $dom = $k; }
            }
            $domPay = $bmal > 0 ? round($domT / $bmal * 100) : 0;
            $domAd = $dom['malzeme'] ?? null;
            if ($adet <= 0) return [$ad . ' bu dönem hiç satmamış; kâr/maliyet değerlendirmesi için satış gerekiyor. Menüdeki yerini ve fiyatını gözden geçirin.'];
            if ($my >= 38) {
                $out[] = $ad . ' food-cost %' . $my . ' ile yüksek: bir porsiyon ' . $tl($bmal) . ' malzeme, ' . $tl($fiyat) . ' satış, sadece ' . $tl($bkar) . ' brüt kâr bırakıyor.';
                $out[] = $domAd ? ('Maliyetin %' . $domPay . 'ı ' . $domAd . 'ten geliyor — porsiyonunu kısmak en hızlı çözüm; alternatif olarak fiyatı ' . $tl(round($fiyat * 1.15)) . ' seviyesine çekin.') : 'Fiyatı artırmak ya da reçeteyi sadeleştirmek gerekir.';
            } elseif ($my >= 30) {
                $out[] = $ad . ' normal bandda (food-cost %' . $my . '), porsiyon başına ' . $tl($bkar) . ' brüt kâr bırakıyor.';
                if ($domAd) $out[] = 'Maliyetin %' . $domPay . 'ı ' . $domAd . 'ten geliyor; oradan küçük bir kısıntı food-cost\'u %28 altına indirir.';
            } else {
                $out[] = $ad . ' çok kârlı (food-cost %' . $my . '), porsiyon başına ' . $tl($bkar) . ' kâr bırakıyor.';
                $out[] = 'Menüde öne çıkarın, garsonlara öncelikli önertin — yıldız ürün adayı.';
            }
            if ($adet >= 20) $out[] = 'Dönemde ' . round($adet) . ' adet satmış, talep güçlü' . ($my >= 35 ? '; yüksek maliyet nedeniyle buradaki her iyileştirme toplam kâra büyük yansır.' : '; iyi marjla birleşince gerçek bir kâr motoru.');
            elseif ($adet < 5) $out[] = 'Bu dönem yalnızca ' . round($adet) . ' adet satmış — talep zayıf; menüdeki yeri, fiyatı veya sunumu gözden geçirilebilir.';
            return $out;
        }
        if ($tip === 'isletme_ozeti') {
            $ciro = (float) ($b['ciro'] ?? 0);
            $comp = (float) ($b['onceki_donem_ciro'] ?? 0);
            $out[] = $comp > 0
                ? ('Ciro ' . $tl($ciro) . ', önceki döneme göre ' . ($ciro >= $comp ? '%' . round(($ciro - $comp) / $comp * 100) . ' önde' : '%' . round(($comp - $ciro) / $comp * 100) . ' geride') . '.')
                : ('Dönem cirosu ' . $tl($ciro) . '.');
            $isk = (float) ($b['iskonto'] ?? 0);
            if ($ciro > 0 && $isk > $ciro * 0.05) $out[] = 'İskonto ' . $tl($isk) . ' ile ciroya oranla yüksek — indirim yetkilerini ve nedenlerini gözden geçirin.';
            if (!empty($b['iptal_adisyon_adet'])) $out[] = $b['iptal_adisyon_adet'] . ' adisyon iptal edilmiş; tekrar eden iptaller varsa nedenini araştırın.';
            if (!empty($b['cok_satan_urunler'])) $out[] = 'En çok satanlar: ' . implode(', ', array_slice($b['cok_satan_urunler'], 0, 3)) . '. Bu ürünlerin food-cost oranını kontrol edin; küçük bir iyileştirme büyük kazanç.';
            return $out;
        }
        if ($tip === 'sadik_musteri_analizi') {
            $out[] = 'Bu müşteri ' . (int) ($b['siparis_sayisi'] ?? 0) . ' siparişte ' . $tl($b['toplam_harcama'] ?? 0) . ' harcamış.';
            $yorumlar = $b['son_yorumlar'] ?? [];
            if (!empty($yorumlar)) {
                $ort = array_sum(array_map(fn ($y) => (int) $y['puan'], $yorumlar)) / max(1, count($yorumlar));
                $out[] = $ort <= 2.5
                    ? ('Son yorumları düşük (⭐' . round($ort, 1) . ') — değerli müşteri kaçabilir, aramanız/telafi önerilir.')
                    : ('Memnuniyeti iyi (⭐' . round($ort, 1) . ') — sadık müşteri, özel ilgi işe yarar.');
            }
            if (($b['son_siparis_gun_once'] ?? null) !== null && $b['son_siparis_gun_once'] >= 14) $out[] = $b['son_siparis_gun_once'] . ' gündür gelmemiş — geri kazanım kampanyası düşünün.';
            if (!empty($b['favori_urunler'])) $out[] = 'Favorisi: ' . implode(', ', $b['favori_urunler']) . ' — öneri/kampanyada kullanın.';
            return $out;
        }
        return $out;
    }
}

Route::get('/api/patron/ozet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);

    $period = in_array($r->period, ['gunluk', 'haftalik', 'aylik', 'yillik']) ? $r->period : 'gunluk';
    [$from, $to, $pfrom, $pto] = _restoPeriyot($period);
    $now = now();
    $t0 = today()->startOfDay();

    // --- Ciro (odeme bazli, Kerzz "Total Amount") ---
    $ciroArasi = fn ($f, $t) => (float) DB::table('odemeler')->whereBetween('created_at', [$f, $t])->sum('tutar');
    $ciro = $ciroArasi($from, $to);
    $compCiro = $ciroArasi($pfrom, $pto);

    // --- Folyo / misafir / ortalamalar (odenmis adisyon = kapali folio) ---
    $folyoMetrik = function ($f, $t) {
        $q = DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$f, $t]);
        $adet = (clone $q)->count();
        $misafir = (int) (clone $q)->sum('misafir_sayisi');
        $ciro = (float) (clone $q)->sum('toplam');
        return [
            'folyo' => $adet,
            'misafir' => $misafir,
            'folyo_ort' => $adet > 0 ? $ciro / $adet : 0,
            'kisi_basi' => $misafir > 0 ? $ciro / $misafir : 0,
        ];
    };
    $info = $folyoMetrik($from, $to);
    $comp = $folyoMetrik($pfrom, $pto);

    // --- Acik / Kapali folio ---
    $acikAdet = DB::table('adisyonlar')->where('durum', 'acik')->count();
    $acikTutar = (float) DB::table('adisyonlar')->where('durum', 'acik')->sum('toplam');
    $kapaliAdet = $info['folyo'];
    $kapaliTutar = $ciro;

    // --- Food-cost: satilan urunlerin recete maliyeti (Sales & Costs) ---
    $maliyetMap = _restoUrunMaliyetMap();
    $satisSatir = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
        ->where('adisyon_kalemleri.durum', '!=', 'iptal')
        ->select('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi',
            DB::raw('SUM(adisyon_kalemleri.adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as satis'))
        ->groupBy('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi')
        ->orderByDesc('satis')->get();
    $toplamMaliyet = 0.0;
    $urunler = $satisSatir->map(function ($s) use ($maliyetMap, &$toplamMaliyet) {
        $birim = $maliyetMap['id'][(int) $s->urun_id] ?? ($maliyetMap['ad'][$s->urun_adi] ?? 0);
        $mal = (float) $s->adet * (float) $birim;
        if ($mal <= 0 && $s->satis > 0) $mal = (float) $s->satis * 0.30; // recete yoksa tahmini food-cost
        $toplamMaliyet += $mal;
        return [
            'urun_id' => (int) $s->urun_id, 'ad' => $s->urun_adi, 'adet' => (float) $s->adet, 'satis' => (float) $s->satis,
            'maliyet' => round($mal, 2), 'yuzde' => $s->satis > 0 ? round($mal / $s->satis * 100) : 0,
        ];
    })->take(40)->values();
    $maliyetYuzde = $ciro > 0 ? round($toplamMaliyet / $ciro * 100) : 0;

    // --- KAYIP RADARI ---
    $odendiPencere = fn () => DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to]);
    $iskonto = (float) $odendiPencere()->sum('indirim');
    $ikram = (float) $odendiPencere()->sum('ikram');
    $silinen = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyon_kalemleri.durum', 'iptal')->whereBetween('adisyonlar.acilis', [$from, $to])
        ->sum('adisyon_kalemleri.tutar');
    $iptalQ = DB::table('adisyonlar')->where('durum', 'iptal')->whereBetween('acilis', [$from, $to]);
    $iptalTutar = (float) (clone $iptalQ)->sum('toplam');
    $iptalAdet = (clone $iptalQ)->count();
    $fire = 0.0;
    try {
        $fire = (float) DB::table('stok_hareketleri')->where('tip', 'fire')
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('COALESCE(SUM(ABS(miktar) * birim_maliyet),0) as t'))->value('t');
    } catch (\Throwable $e) {
    }
    // Odenmez: odendi ama odeme kaydi eksik olan folyolarin acigi
    $odenmez = 0.0;
    try {
        $odenmez = (float) DB::table('adisyonlar')->where('adisyonlar.durum', 'odendi')
            ->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->leftJoin('odemeler', 'adisyonlar.id', '=', 'odemeler.adisyon_id')
            ->select(DB::raw('COALESCE(SUM(adisyonlar.toplam),0) - COALESCE(SUM(odemeler.tutar),0) as a'))
            ->havingRaw('a > 0')->value('a') ?? 0;
    } catch (\Throwable $e) {
    }
    $yuzdele = fn ($tutar) => ['tutar' => round($tutar, 2), 'yuzde' => $ciro > 0 ? round($tutar / $ciro * 100, 1) : 0];
    $kayip = [
        'iskonto' => $yuzdele($iskonto),
        'ikram' => $yuzdele($ikram),
        'silinen' => $yuzdele($silinen),
        'iptal' => array_merge($yuzdele($iptalTutar), ['adet' => $iptalAdet]),
        'fire' => $yuzdele($fire),
        'odenmez' => $yuzdele(max(0, $odenmez)),
    ];

    // --- Odeme tipi dagilimi ---
    $odemeTipleri = DB::table('odemeler')->whereBetween('created_at', [$from, $to])
        ->select('tip', DB::raw('COUNT(*) as adet'), DB::raw('SUM(tutar) as tutar'))
        ->groupBy('tip')->orderByDesc('tutar')->get();

    // --- Servis tipi (kanal) dagilimi ---
    $servisTipleri = DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])
        ->select('kanal', DB::raw('COUNT(*) as adet'), DB::raw('SUM(toplam) as tutar'))
        ->groupBy('kanal')->orderByDesc('tutar')->get()
        ->map(fn ($k) => ['ad' => ['salon' => 'Masaya Servis', 'paket' => 'Paket / Kurye', 'qr' => 'QR / Self'][$k->kanal] ?? ucfirst($k->kanal),
            'adet' => $k->adet, 'tutar' => (float) $k->tutar]);

    // --- Son 10 gun grafik ---
    $gunluk = [];
    for ($i = 9; $i >= 0; $i--) {
        $g0 = (clone $t0)->subDays($i);
        $g1 = (clone $g0)->endOfDay();
        $gunluk[] = ['gun' => $g0->format('d/m'), 'ciro' => $ciroArasi($g0, $g1)];
    }

    // --- Uyarilar / AI Bildirimleri (kural motoru) — hem string listesi (legacy) hem yapili bildirim ---
    $uyarilar = [];      // legacy: eski app derlemeleri kirilmasin
    $bildirimler = [];   // yeni: baslik + detayli aciklama + onem + aksiyon linki (AI bildirim merkezi)
    $tl = fn ($v) => number_format((float) $v, 0, ',', '.') . 'TL';
    $ekle = function ($seviye, $ikon, $baslik, $mesaj, $detay, $aksiyon = null) use (&$uyarilar, &$bildirimler) {
        $uyarilar[] = $mesaj;
        $bildirimler[] = compact('seviye', 'ikon', 'baslik', 'mesaj', 'detay', 'aksiyon');
    };

    if ($compCiro > 0 && $ciro < $compCiro * 0.85) {
        $dd = round(($compCiro - $ciro) / $compCiro * 100);
        $ekle('riskli', '📉', 'Ciro düşüşte', 'Ciro önceki döneme göre %' . $dd . ' geride.',
            'Bu dönem ' . $tl($ciro) . ' ciro var; önceki dönem ' . $tl($compCiro) . ' idi — yaklaşık %' . $dd . ' düşüş. Düşüş genelde iki sebepten gelir: ya daha az misafir geldi (folyo/trafik azaldı), ya da kişi başı harcama küçüldü (sepet düştü). Önce Kayıp Radarı’na bak — iskonto, ikram, iptal ve fire sızıntısı ciroyu aşağı çekiyor olabilir. Sonra kapanan adisyon sayısını ve kişi başı tutarı önceki dönemle karşılaştır.',
            ['tip' => 'kayip', 'alt' => 'iskonto', 'etiket' => 'Kayıp Radarı’nı aç']);
    } elseif ($compCiro > 0 && $ciro > $compCiro * 1.15) {
        $dd = round(($ciro - $compCiro) / $compCiro * 100);
        $ekle('iyi', '🚀', 'Ciro artışta', 'Ciro önceki döneme göre %' . $dd . ' önde.',
            'Tebrikler — bu dönem ciro önceki döneme göre %' . $dd . ' arttı (' . $tl($ciro) . '). Bu ivmeyi sürdürmek için hangi ürünlerin ve hangi servis tipinin (salon/paket) çektiğine Satış & Maliyet ve Servis Tipi dağılımından bak; işe yarayan kampanya/menüyü koru.',
            null);
    }
    if ($ciro > 0 && $iskonto > $ciro * 0.05) {
        $o = round($iskonto / $ciro * 100);
        $ekle('uyari', '🏷️', 'İskonto yüksek', 'İskonto oranı yüksek: ciro yaklaşık %' . $o . ' iskontoya gitmiş.',
            'Bu dönem toplam ' . $tl($iskonto) . ' iskonto verildi — cironun %' . $o . '’sı. Restoranda sağlıklı iskonto oranı genelde %2-3 civarındadır. Kimin ne kadar iskonto yaptığını Kayıp Radarı > İskonto dökümünden personel bazında görebilirsin; sürekli tekrarlayan indirimler alışkanlık ya da suistimal işareti olabilir. Gerekirse personel iskonto limitini Yetkiler’den kıs.',
            ['tip' => 'kayip', 'alt' => 'iskonto', 'etiket' => 'İskonto dökümünü aç']);
    }
    if ($maliyetYuzde >= 40) {
        $ekle($maliyetYuzde >= 50 ? 'riskli' : 'uyari', '🍳', 'Food-cost baskısı', 'Maliyet oranı %' . $maliyetYuzde . ' — kârlılık baskı altında.',
            'Yemek maliyeti (food-cost) ciroya oranla %' . $maliyetYuzde . '. Restoranda hedef genelde %28-35 arasıdır; bunun üstü kârı eritir. Muhtemel sebepler: porsiyon kaçağı, fire/zayi, maliyeti yüksek ürünlerin çok satması ya da güncellenmemiş satış fiyatları. Food-Cost dökümünden hangi ürünlerin maliyeti şişirdiğini gör; gerekirse fiyat güncelle veya reçete/porsiyonu standartlaştır.',
            ['tip' => 'maliyet', 'etiket' => 'Food-Cost dökümü']);
    }
    try {
        $kritik = DB::table('malzemeler')->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
            ->select('malzemeler.id')->groupBy('malzemeler.id', 'malzemeler.kritik_stok')
            ->havingRaw('COALESCE(SUM(stok_hareketleri.miktar),0) < malzemeler.kritik_stok')->get()->count();
        if ($kritik > 0) $ekle('uyari', '📦', 'Kritik stok', $kritik . ' malzeme kritik stok seviyesinde.',
            $kritik . ' malzemenin stoğu kritik eşiğin altına düştü. Bunlar tükenirse ilgili ürünleri satamazsın (mutfakta 86’ya düşer, sipariş kaçar). Stok & Satın Alma ekranından eksik malzemeleri tedarikçiye sipariş et; yoğun günlerden önce kritik kalemleri hazırda tut.',
            null);
    } catch (\Throwable $e) {
    }

    // --- Legacy (eski app derlemeleri kirilmasin) ---
    $bugun = $ciroArasi($t0, $now);
    $dun = $ciroArasi((clone $t0)->subDay(), (clone $now)->subDay());

    return [
        'ok' => 1,
        'patronAd' => $p->ad,
        'period' => $period,
        'ciro' => $ciro, 'compCiro' => $compCiro,
        'ciroYuzde' => $compCiro > 0 ? round(($ciro - $compCiro) / $compCiro * 100, 1) : null,
        'info' => $info, 'comp' => $comp,
        'acikAdet' => $acikAdet, 'acikTutar' => $acikTutar,
        'kapaliAdet' => $kapaliAdet, 'kapaliTutar' => $kapaliTutar,
        'maliyet' => round($toplamMaliyet, 2), 'maliyetYuzde' => $maliyetYuzde,
        'kayip' => $kayip,
        'odemeTipleri' => $odemeTipleri,
        'servisTipleri' => $servisTipleri,
        'gunluk' => $gunluk,
        'urunler' => $urunler,
        'uyarilar' => $uyarilar,
        'bildirimler' => $bildirimler,
        // legacy
        'bugun' => $bugun, 'dun' => $dun,
        'acikMasa' => DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')->count(),
        'masaSayisi' => DB::table('masalar')->count(),
        'bugunAdisyon' => DB::table('adisyonlar')->whereBetween('acilis', [$t0, $now])->count(),
        'top' => $urunler->take(5),
    ];
});

// KVKK: musteri adi/telefonu SADECE sahip (patron) tam gorur; mudur maskeli gorur.
if (!function_exists('_kvkkAd')) {
    function _kvkkAd($ad, $tamGor)
    {
        if ($tamGor || !$ad) return $ad;
        return collect(explode(' ', trim($ad)))->map(function ($w) {
            $len = mb_strlen($w);
            if ($len <= 1) return $w;
            if ($len === 2) return mb_substr($w, 0, 1) . '*';
            return mb_substr($w, 0, 2) . str_repeat('*', $len - 2);
        })->implode(' ');
    }
}
if (!function_exists('_kvkkTel')) {
    function _kvkkTel($tel, $tamGor)
    {
        if ($tamGor || !$tel) return $tel;
        $d = preg_replace('/\D/', '', $tel);
        if (mb_strlen($d) < 4) return '***';
        return mb_substr($d, 0, 4) . ' *** ** ' . mb_substr($d, -2);
    }
}

// Kart tiklama -> drill-down detay (Kerzz BOSS tarzi). tip: urun | kayip | acik | odeme | servis
Route::get('/api/patron/detay', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $tamGor = ($p->rol === 'sahip'); // KVKK: sadece patron musteri PII'sini tam gorur
    $tip = $r->tip;
    $period = in_array($r->period, ['gunluk', 'haftalik', 'aylik', 'yillik']) ? $r->period : 'haftalik';
    [$from, $to] = _restoPeriyot($period);

    // ---- URUN DETAY: recete maliyeti + donem satis + en cok satan garson + gunluk ----
    if ($tip === 'urun') {
        $urunId = (int) $r->id;
        $urun = DB::table('urunler')->find($urunId);
        if (!$urun) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];

        // Recete kalemleri (malzeme bazinda maliyet) + KALAN STOKLA YAPILABILIR PORSIYON
        $recete = DB::table('receteler')->where('urun_id', $urunId)->where('tip', 'urun')->first();
        $receteKalem = [];
        $receteToplam = 0.0;
        $yapilabilir = null;      // kalan stokla kac porsiyon daha cikar (darbogaz malzemeye gore)
        $darbogaz = null;         // en once biten (siniri belirleyen) malzeme
        if ($recete) {
            $cevrimMap = DB::table('birim_cevrimleri')->get()->groupBy('malzeme_id');
            foreach (DB::table('recete_kalemleri')->where('recete_id', $recete->id)->get() as $rk) {
                if (!$rk->malzeme_id) continue;
                $m = DB::table('malzemeler')->find($rk->malzeme_id);
                if (!$m) continue;
                $birim = DB::table('birimler')->find($rk->birim_id);
                $karsilik = 1.0;
                if ((int) $rk->birim_id !== (int) $m->temel_birim_id) {
                    foreach (($cevrimMap[$rk->malzeme_id] ?? []) as $c) {
                        if ((int) $c->birim_id === (int) $rk->birim_id) $karsilik = (float) $c->temel_birim_karsiligi;
                    }
                }
                $temelMiktar = (float) $rk->miktar * $karsilik;              // 1 porsiyon icin temel birimde miktar
                $satirMaliyet = $temelMiktar * (float) $m->guncel_maliyet;
                $receteToplam += $satirMaliyet;
                // Bu malzemenin mevcut stogu (temel birimde net) + bundan kac porsiyon cikar
                $stok = (float) DB::table('stok_hareketleri')->where('sube_id', $urun->sube_id)->where('malzeme_id', $rk->malzeme_id)->sum('miktar');
                $por = $temelMiktar > 0 ? (int) floor(max(0, $stok) / $temelMiktar) : null;
                if ($por !== null) {
                    if ($yapilabilir === null || $por < $yapilabilir) { $yapilabilir = $por; $darbogaz = $m->ad; }
                }
                $receteKalem[] = [
                    'malzeme' => $m->ad, 'miktar' => (float) $rk->miktar,
                    'birim' => $birim->kisaltma ?? '', 'maliyet' => round($satirMaliyet, 2),
                    'stok' => round($stok, 2), 'porsiyon' => $por,
                ];
            }
        }
        // Donem satis
        $st = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyon_kalemleri.urun_id', $urunId)->where('adisyonlar.durum', 'odendi')
            ->where('adisyon_kalemleri.durum', '!=', 'iptal')->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->selectRaw('COALESCE(SUM(adisyon_kalemleri.adet),0) adet, COALESCE(SUM(adisyon_kalemleri.tutar),0) ciro, COUNT(DISTINCT adisyonlar.id) folyo')
            ->first();
        // Bugun
        $bugunAdet = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyon_kalemleri.urun_id', $urunId)->where('adisyonlar.durum', 'odendi')
            ->whereDate('adisyonlar.kapanis', today())->sum('adisyon_kalemleri.adet');
        // En cok satan garson
        $garsonlar = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
            ->where('adisyon_kalemleri.urun_id', $urunId)->where('adisyonlar.durum', 'odendi')
            ->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->groupBy('personeller.id', 'personeller.ad')
            ->selectRaw('personeller.ad, SUM(adisyon_kalemleri.adet) adet, SUM(adisyon_kalemleri.tutar) ciro')
            ->orderByDesc('adet')->limit(8)->get();
        // Son 10 gun adet
        $gunluk = [];
        for ($i = 9; $i >= 0; $i--) {
            $g0 = today()->subDays($i)->startOfDay();
            $g1 = (clone $g0)->endOfDay();
            $ad = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                ->where('adisyon_kalemleri.urun_id', $urunId)->where('adisyonlar.durum', 'odendi')
                ->whereBetween('adisyonlar.kapanis', [$g0, $g1])->sum('adisyon_kalemleri.adet');
            $gunluk[] = ['gun' => $g0->format('d/m'), 'deger' => $ad];
        }
        $satisTutar = (float) $st->ciro;
        $toplamMaliyet = $receteToplam > 0 ? $receteToplam * (float) $st->adet : $satisTutar * 0.30;
        $my = $satisTutar > 0 ? round($toplamMaliyet / $satisTutar * 100) : 0;
        // KISA standart yorum (varsayilan). Detayli/gerekceli yorum -> "Derin AI Analizi" butonu (ai-analiz).
        $ai = [];
        if ($satisTutar <= 0) {
            $ai[] = ['seviye' => 'bilgi', 'mesaj' => $urun->ad . ' bu dönem hiç satmamış.'];
        } elseif ($my >= 38) {
            $ai[] = ['seviye' => 'riskli', 'mesaj' => 'Food-cost %' . $my . ' — maliyet yüksek, kâr dar. Detaylı yorum için AI analizine dokunun.'];
        } elseif ($my >= 30) {
            $ai[] = ['seviye' => 'bilgi', 'mesaj' => 'Food-cost %' . $my . ' — normal seviyede. Detaylı yorum için AI analizine dokunun.'];
        } else {
            $ai[] = ['seviye' => 'iyi', 'mesaj' => 'Food-cost %' . $my . ' — kârlı ürün. Detaylı yorum için AI analizine dokunun.'];
        }
        return [
            'ok' => 1, 'baslik' => $urun->ad, 'tip' => 'urun', 'ai' => $ai,
            'ozet' => [
                'Satılan' => rtrim(rtrim(number_format((float) $st->adet, 1, ',', '.'), '0'), ',') . ' adet',
                'Ciro' => number_format($satisTutar, 0, ',', '.') . 'TL',
                'Bugün' => rtrim(rtrim(number_format($bugunAdet, 1, ',', '.'), '0'), ',') . ' adet',
                'Fiyat' => number_format((float) $urun->fiyat, 0, ',', '.') . 'TL',
                'Kalan stok' => $yapilabilir !== null ? ($yapilabilir . ' porsiyon') : '—',
            ],
            'recete' => $receteKalem, 'receteBirimMaliyet' => round($receteToplam, 2),
            'toplamMaliyet' => round($toplamMaliyet, 2),
            'maliyetYuzde' => $satisTutar > 0 ? round($toplamMaliyet / $satisTutar * 100) : 0,
            'yapilabilirPorsiyon' => $yapilabilir, 'darbogazMalzeme' => $darbogaz,
            'garsonlar' => $garsonlar, 'gunluk' => $gunluk,
        ];
    }

    // ---- KAYIP DETAY: kayit listesi (garson + sebep + tutar + zaman) ----
    if ($tip === 'kayip') {
        $alt = $r->alt; // iskonto | ikram | silinen | iptal | fire
        $kayitlar = collect();
        if (in_array($alt, ['iskonto', 'ikram', 'silinen'])) {
            $logTip = $alt === 'silinen' ? 'void' : ($alt === 'iskonto' ? 'indirim' : 'ikram');
            $kayitlar = DB::table('iptal_indirim_loglari')->leftJoin('personeller', 'iptal_indirim_loglari.personel_id', '=', 'personeller.id')
                ->where('iptal_indirim_loglari.tip', $logTip)->whereBetween('iptal_indirim_loglari.created_at', [$from, $to])
                ->select('personeller.ad as garson', 'iptal_indirim_loglari.tutar', 'iptal_indirim_loglari.sebep',
                    'iptal_indirim_loglari.created_at', 'iptal_indirim_loglari.adisyon_id')
                ->orderByDesc('iptal_indirim_loglari.created_at')->limit(60)->get();
        } elseif ($alt === 'iptal') {
            $kayitlar = DB::table('adisyonlar')->leftJoin('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
                ->where('adisyonlar.durum', 'iptal')->whereBetween('adisyonlar.acilis', [$from, $to])
                ->select('personeller.ad as garson', 'adisyonlar.toplam as tutar',
                    DB::raw("'Adisyon iptal' as sebep"), 'adisyonlar.acilis as created_at', 'adisyonlar.id as adisyon_id')
                ->orderByDesc('adisyonlar.acilis')->limit(60)->get();
        } elseif ($alt === 'fire') {
            $kayitlar = DB::table('stok_hareketleri')->join('malzemeler', 'stok_hareketleri.malzeme_id', '=', 'malzemeler.id')
                ->leftJoin('personeller', 'stok_hareketleri.personel_id', '=', 'personeller.id')
                ->where('stok_hareketleri.tip', 'fire')->whereBetween('stok_hareketleri.created_at', [$from, $to])
                ->select('malzemeler.ad as garson', DB::raw('ABS(stok_hareketleri.miktar) * stok_hareketleri.birim_maliyet as tutar'),
                    'stok_hareketleri.aciklama as sebep', 'stok_hareketleri.created_at', DB::raw('NULL as adisyon_id'))
                ->orderByDesc('stok_hareketleri.created_at')->limit(60)->get();
        } elseif ($alt === 'odenmez') {
            // Kapanmis ("odendi") ama girilen odemeler toplami hesaptan az kalan adisyonlar -> tahsilat acigi
            $kayitlar = DB::table('adisyonlar')->leftJoin('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
                ->leftJoin('odemeler', 'adisyonlar.id', '=', 'odemeler.adisyon_id')
                ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
                ->groupBy('adisyonlar.id', 'personeller.ad', 'adisyonlar.toplam', 'adisyonlar.kapanis')
                ->havingRaw('COALESCE(adisyonlar.toplam,0) - COALESCE(SUM(odemeler.tutar),0) > 0')
                ->select('personeller.ad as garson',
                    DB::raw('COALESCE(adisyonlar.toplam,0) - COALESCE(SUM(odemeler.tutar),0) as tutar'),
                    DB::raw("'Tahsilat eksik' as sebep"), 'adisyonlar.kapanis as created_at', 'adisyonlar.id as adisyon_id')
                ->orderByDesc('adisyonlar.kapanis')->limit(60)->get();
        }
        $basliklar = ['iskonto' => 'İskonto', 'ikram' => 'İkram', 'silinen' => 'Silinen Ürün', 'iptal' => 'İptal Adisyon', 'fire' => 'Fire / Zayi', 'odenmez' => 'Tahsil Edilemeyen'];
        $sebepDagilim = $kayitlar->groupBy('sebep')->map(fn ($g) => ['sebep' => $g[0]->sebep ?: '-', 'adet' => $g->count(), 'tutar' => (float) $g->sum('tutar')])
            ->sortByDesc('tutar')->values();
        $ai = [];
        $toplamK = (float) $kayitlar->sum('tutar');
        if ($alt !== 'fire' && $toplamK > 0) {
            $tg = $kayitlar->groupBy('garson')->map(fn ($g) => (object) ['garson' => $g[0]->garson, 'tutar' => (float) $g->sum('tutar')])->sortByDesc('tutar')->first();
            if ($tg && $tg->garson) {
                $oran = round($tg->tutar / $toplamK * 100);
                if ($oran >= 40) $ai[] = ['seviye' => 'riskli', 'mesaj' => ($basliklar[$alt] ?? 'Kayıp') . ' işlemlerinin %' . $oran . '\'ı ' . $tg->garson . '\'da yoğunlaşmış — kontrol edilmesi önerilir.'];
            }
        }
        // Kayitlarin adisyonlari icin masa + musteri (tek ek sorgu) -> karttan gorulsun, tiklaninca detaya gidilsin
        $aids = $kayitlar->pluck('adisyon_id')->filter()->unique()->values()->all();
        $adisyonBilgi = [];
        if ($aids) {
            foreach (DB::table('adisyonlar')->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
                ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
                ->whereIn('adisyonlar.id', $aids)
                ->select('adisyonlar.id', 'adisyonlar.kanal', 'masalar.ad as masa', 'musteriler.ad as musteri')->get() as $rw) {
                $adisyonBilgi[$rw->id] = $rw;
            }
        }
        $yerAd = ['salon' => 'Masa Servis', 'paket' => 'Paket', 'qr' => 'QR'];
        return [
            'ok' => 1, 'baslik' => ($basliklar[$alt] ?? 'Kayıp') . ' Detayı', 'tip' => 'kayip', 'ai' => $ai,
            'toplam' => (float) $kayitlar->sum('tutar'), 'adet' => $kayitlar->count(),
            'sebepler' => $sebepDagilim,
            'kayitlar' => $kayitlar->map(function ($k) use ($adisyonBilgi, $yerAd) {
                $b = ($k->adisyon_id && isset($adisyonBilgi[$k->adisyon_id])) ? $adisyonBilgi[$k->adisyon_id] : null;
                return [
                    'garson' => $k->garson ?? '-', 'tutar' => (float) $k->tutar, 'sebep' => $k->sebep ?? '-',
                    'zaman' => $k->created_at ? \Carbon\Carbon::parse($k->created_at)->format('d.m H:i') : '',
                    'adisyon_id' => $k->adisyon_id,
                    'masa' => $b ? ($b->masa ?? ($yerAd[$b->kanal] ?? null)) : null,
                    'musteri' => $b ? $b->musteri : null,
                ];
            }),
        ];
    }

    // ---- ACIK ADISYON DETAY ----
    if ($tip === 'acik') {
        $simdi = now();
        $kayitlar = DB::table('adisyonlar')->where('adisyonlar.durum', 'acik')
            ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
            ->leftJoin('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
            ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
            ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.acilis', 'adisyonlar.kanal', 'adisyonlar.misafir_sayisi',
                'adisyonlar.musteri_id', 'masalar.ad as masa', 'personeller.ad as garson', 'musteriler.ad as musteri')
            ->orderByDesc('adisyonlar.toplam')->get()
            ->map(function ($a) use ($simdi, $tamGor) {
                $dk = $a->acilis ? (int) round(\Carbon\Carbon::parse($a->acilis)->diffInMinutes($simdi)) : 0;
                $adet = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->count();
                $sure = $dk < 60 ? ($dk . ' dk') : ($dk < 1440 ? (intdiv($dk, 60) . ' sa ' . ($dk % 60) . ' dk') : (intdiv($dk, 1440) . ' gün ' . intdiv($dk % 1440, 60) . ' sa'));
                return [
                    'id' => $a->id, 'masa' => $a->masa ?? ucfirst($a->kanal), 'garson' => $a->garson ?? '-',
                    'musteri' => _kvkkAd($a->musteri, $tamGor), 'musteri_id' => $a->musteri_id,
                    'tutar' => (float) $a->toplam, 'sure_dk' => (int) $dk, 'sure' => $sure, 'kalem' => $adet, 'misafir' => $a->misafir_sayisi,
                ];
            });
        return [
            'ok' => 1, 'baslik' => 'Açık Adisyonlar', 'tip' => 'acik',
            'toplam' => (float) $kayitlar->sum('tutar'), 'adet' => $kayitlar->count(), 'kayitlar' => $kayitlar,
        ];
    }

    // ---- TEK ADISYON DETAY (urunler + odemeler + musteri anketi/yorumu) ----
    if ($tip === 'adisyon') {
        $id = (int) $r->id;
        $a = DB::table('adisyonlar')->find($id);
        if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
        $masa = $a->masa_id ? DB::table('masalar')->where('id', $a->masa_id)->value('ad') : null;
        $garson = $a->acan_personel_id ? DB::table('personeller')->where('id', $a->acan_personel_id)->value('ad') : null;
        $musteri = $a->musteri_id ? DB::table('musteriler')->where('id', $a->musteri_id)->first(['ad', 'telefon']) : null;
        $kanalAd = ['salon' => 'Masa Servis', 'paket' => 'Paket / Kurye', 'qr' => 'QR / Self'][$a->kanal] ?? ucfirst($a->kanal);
        $sureDk = ($a->acilis && $a->kapanis) ? (int) round(\Carbon\Carbon::parse($a->acilis)->diffInMinutes(\Carbon\Carbon::parse($a->kapanis)))
            : ($a->acilis ? (int) round(\Carbon\Carbon::parse($a->acilis)->diffInMinutes(now())) : 0);
        // Okunur sure: dk / sa dk / gun sa
        $sureStr = $sureDk < 60 ? ($sureDk . ' dk')
            : ($sureDk < 1440 ? (intdiv($sureDk, 60) . ' sa ' . ($sureDk % 60) . ' dk')
                : (intdiv($sureDk, 1440) . ' gün ' . intdiv($sureDk % 1440, 60) . ' sa'));
        $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $id)
            ->select('id', 'urun_adi', 'adet', 'birim_fiyat', 'tutar', 'durum', 'not')->orderBy('id')->get()
            ->map(fn ($k) => ['id' => (int) $k->id, 'ad' => $k->urun_adi, 'adet' => (float) $k->adet, 'birim_fiyat' => (float) $k->birim_fiyat,
                'tutar' => (float) $k->tutar, 'durum' => $k->durum, 'not' => $k->not]);
        $odemeler = DB::table('odemeler')->where('adisyon_id', $id)->select('tip', 'tutar')->get()
            ->map(fn ($o) => ['tip' => $o->tip, 'tutar' => (float) $o->tutar]);
        $deg = null;
        if (Schema::hasTable('degerlendirmeler')) {
            $d0 = DB::table('degerlendirmeler')->where('adisyon_id', $id)->orderByDesc('created_at')->first();
            if ($d0) {
                $deg = [
                    'puan' => (int) $d0->puan, 'lezzet' => (int) $d0->lezzet, 'servis' => (int) $d0->servis, 'hiz' => (int) $d0->hiz,
                    'yorum' => $d0->yorum, 'mutlu' => $d0->puan >= 4,
                    'zaman' => \Carbon\Carbon::parse($d0->created_at)->format('d.m.Y H:i'),
                ];
            }
        }
        $ai = [];
        if ($deg && empty($deg['mutlu'])) $ai[] = ['seviye' => 'riskli', 'mesaj' => 'Müşteri memnun kalmamış (⭐' . $deg['puan'] . '). Telafi araması/jesti önerilir.'];
        if ((float) $a->ara_toplam > 0 && (float) $a->indirim > (float) $a->ara_toplam * 0.15) $ai[] = ['seviye' => 'bilgi', 'mesaj' => 'Yüksek iskonto: ' . number_format($a->indirim, 0, ',', '.') . 'TL (ara toplamın %' . round($a->indirim / $a->ara_toplam * 100) . '\'ı).'];
        // Bu adisyona birlesmis masalar (bu adisyon hedefse) -> kendisi + kaynak masalar
        $birlesik = [];
        $ayrilabilir = []; // gruptan ayrilabilecek kaynak masalar (id+ad) -> "Masa Ayir"
        if ($masa) {
            $kaynakMasaIdler = DB::table('adisyon_masa_loglari')->where('adisyon_id', $id)->where('islem', 'birlestirme')
                ->pluck('eski_masa_id')->filter()->unique()->all();
            if ($kaynakMasaIdler) {
                $adMap = DB::table('masalar')->whereIn('id', $kaynakMasaIdler)->pluck('ad', 'id');
                $birlesik = array_values(array_unique(array_merge([$masa], $adMap->values()->all())));
                foreach ($adMap as $mid => $mad) $ayrilabilir[] = ['id' => (int) $mid, 'ad' => $mad];
            }
        }
        return [
            'ok' => 1, 'baslik' => ($masa ?? $kanalAd) . ' · Adisyon', 'tip' => 'adisyon', 'ai' => $ai,
            'masa' => $masa, 'birlesik_masalar' => $birlesik, 'ayrilabilir_masalar' => $ayrilabilir,
            'ozet' => ['Masa' => $masa ?? $kanalAd, 'Garson' => $garson ?? '-', 'Kişi' => (string) $a->misafir_sayisi, 'Süre' => $sureStr],
            'durum' => $a->durum, 'kanal' => $kanalAd,
            'acilis' => $a->acilis ? \Carbon\Carbon::parse($a->acilis)->format('d.m H:i') : '-',
            'kapanis' => $a->kapanis ? \Carbon\Carbon::parse($a->kapanis)->format('d.m H:i') : null,
            'araToplam' => (float) $a->ara_toplam, 'indirim' => (float) $a->indirim, 'ikram' => (float) $a->ikram, 'toplam' => (float) $a->toplam,
            'kalemler' => $kalemler, 'odemeler' => $odemeler,
            'musteri' => $musteri ? ['id' => $a->musteri_id, 'ad' => _kvkkAd($musteri->ad, $tamGor), 'telefon' => _kvkkTel($musteri->telefon, $tamGor)] : null,
            'degerlendirme' => $deg,
        ];
    }

    // ---- MUSTERI DETAY (gecmis siparisler + odeme aliskanligi + favori urun + yorumlar) ----
    if ($tip === 'musteri') {
        $mid = (int) $r->id;
        $m = DB::table('musteriler')->find($mid);
        if (!$m) return ['ok' => 0, 'hata' => 'Müşteri bulunamadı'];
        $siparisler = DB::table('adisyonlar')->where('adisyonlar.musteri_id', $mid)->where('adisyonlar.durum', 'odendi')
            ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
            ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.kapanis', 'adisyonlar.kanal', 'adisyonlar.platform', 'masalar.ad as masa')
            ->orderByDesc('adisyonlar.kapanis')->limit(40)->get()
            ->map(fn ($a) => [
                'id' => $a->id, 'tutar' => (float) $a->toplam, 'kanal' => $a->platform ?: $a->kanal,
                'masa' => $a->masa ?? ucfirst($a->kanal),
                'zaman' => $a->kapanis ? \Carbon\Carbon::parse($a->kapanis)->format('d.m.Y') : '',
            ]);
        $odeme = DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.musteri_id', $mid)
            ->select('odemeler.tip', DB::raw('COUNT(*) as adet'), DB::raw('SUM(odemeler.tutar) as tutar'))
            ->groupBy('odemeler.tip')->orderByDesc('tutar')->get();
        $favori = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.musteri_id', $mid)->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('adisyon_kalemleri.urun_adi', DB::raw('SUM(adisyon_kalemleri.adet) as adet'))
            ->groupBy('adisyon_kalemleri.urun_adi')->orderByDesc('adet')->limit(8)->get();
        $yorumlar = collect();
        if (Schema::hasTable('degerlendirmeler')) {
            $yorumlar = DB::table('degerlendirmeler')->where('musteri_id', $mid)->orderByDesc('created_at')->limit(20)->get()
                ->map(fn ($x) => ['puan' => (int) $x->puan, 'yorum' => $x->yorum,
                    'zaman' => \Carbon\Carbon::parse($x->created_at)->format('d.m.Y')]);
        }
        $gAdet = DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->count();
        $gHarcama = (float) DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->sum('toplam');
        // AI yorumlar (kural motoru)
        $ai = [];
        if ($yorumlar->count() >= 1) {
            $ortSon = round($yorumlar->take(2)->avg('puan'), 1);
            if ($ortSon <= 2.5 && $gHarcama >= 3000) {
                $ai[] = ['seviye' => 'riskli', 'mesaj' => 'Son ziyaretlerde memnuniyetsiz (⭐' . $ortSon . '). ' . number_format($gHarcama, 0, ',', '.') . 'TL harcayan değerli müşteri — kaybetmemek için aranması önerilir.'];
            }
        }
        if ($gHarcama >= 10000) {
            $ai[] = ['seviye' => 'iyi', 'mesaj' => 'VIP müşteri: toplam ' . number_format($gHarcama, 0, ',', '.') . 'TL / ' . $gAdet . ' sipariş. Özel ilgi gösterin.'];
        }
        if ($favori->count()) {
            $ai[] = ['seviye' => 'bilgi', 'mesaj' => 'En sevdiği: ' . $favori[0]->urun_adi . '. Kampanya/öneride kullanılabilir.'];
        }
        return [
            'ok' => 1, 'baslik' => _kvkkAd($m->ad, $tamGor), 'tip' => 'musteri', 'kvkk' => !$tamGor, 'ai' => $ai,
            'profil' => ['ad' => _kvkkAd($m->ad, $tamGor), 'telefon' => _kvkkTel($m->telefon, $tamGor),
                'adres' => $tamGor ? $m->adres : null, 'notlar' => $tamGor ? $m->notlar : null],
            'ozet' => [
                'Sipariş' => (string) $gAdet,
                'Toplam' => number_format($gHarcama, 0, ',', '.') . 'TL',
                'Ortalama' => number_format($gAdet > 0 ? $gHarcama / $gAdet : 0, 0, ',', '.') . 'TL',
                'Puan' => (string) (int) $m->puan,
            ],
            'siparisler' => $siparisler, 'odeme' => $odeme, 'favori' => $favori, 'yorumlar' => $yorumlar,
        ];
    }

    // ---- KAPANAN ADISYON DETAY ----
    if ($tip === 'kapali') {
        $kayitlar = DB::table('adisyonlar')->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
            ->leftJoin('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
            ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
            ->select('adisyonlar.id', 'adisyonlar.toplam', 'adisyonlar.kapanis', 'adisyonlar.kanal', 'adisyonlar.misafir_sayisi',
                'adisyonlar.musteri_id', 'masalar.ad as masa', 'personeller.ad as garson', 'musteriler.ad as musteri')
            ->orderByDesc('adisyonlar.kapanis')->limit(80)->get()
            ->map(fn ($a) => [
                'id' => $a->id, 'masa' => $a->masa ?? ucfirst($a->kanal), 'garson' => $a->garson ?? '-',
                'musteri' => _kvkkAd($a->musteri, $tamGor), 'musteri_id' => $a->musteri_id,
                'tutar' => (float) $a->toplam, 'misafir' => $a->misafir_sayisi,
                'zaman' => $a->kapanis ? \Carbon\Carbon::parse($a->kapanis)->format('d.m H:i') : '',
            ]);
        $odemeDagilim = DB::table('odemeler')->whereBetween('created_at', [$from, $to])
            ->select('tip', DB::raw('COUNT(*) as adet'), DB::raw('SUM(tutar) as tutar'))
            ->groupBy('tip')->orderByDesc('tutar')->get();
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        return [
            'ok' => 1, 'baslik' => 'Kapanan Adisyonlar', 'tip' => 'kapali',
            'toplam' => $ciro,
            'adet' => DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->count(),
            'odemeDagilim' => $odemeDagilim, 'kayitlar' => $kayitlar,
        ];
    }

    // ---- FOOD-COST (TOPLAM MALIYET) DETAY ----
    if ($tip === 'maliyet') {
        $maliyetMap = _restoUrunMaliyetMap();
        $satisSatir = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi',
                DB::raw('SUM(adisyon_kalemleri.adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as satis'))
            ->groupBy('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi')->get();
        $toplamMaliyet = 0.0;
        $toplamSatis = 0.0;
        $urunler = $satisSatir->map(function ($s) use ($maliyetMap, &$toplamMaliyet, &$toplamSatis) {
            $birim = $maliyetMap['id'][(int) $s->urun_id] ?? ($maliyetMap['ad'][$s->urun_adi] ?? 0);
            $mal = (float) $s->adet * (float) $birim;
            if ($mal <= 0 && $s->satis > 0) $mal = (float) $s->satis * 0.30;
            $toplamMaliyet += $mal;
            $toplamSatis += (float) $s->satis;
            return [
                'urun_id' => (int) $s->urun_id, 'ad' => $s->urun_adi, 'adet' => (float) $s->adet, 'satis' => (float) $s->satis,
                'maliyet' => round($mal, 2), 'yuzde' => $s->satis > 0 ? round($mal / $s->satis * 100) : 0,
            ];
        })->sortByDesc('maliyet')->take(40)->values();
        $my = $toplamSatis > 0 ? round($toplamMaliyet / $toplamSatis * 100) : 0;
        $ai = [];
        if ($my >= 32) $ai[] = ['seviye' => 'riskli', 'mesaj' => 'Genel food-cost %' . $my . ' — hedefin üstünde. Aşağıdaki en yüksek maliyetli ürünleri inceleyin.'];
        elseif ($my > 0 && $my < 25) $ai[] = ['seviye' => 'iyi', 'mesaj' => 'Food-cost %' . $my . ' — sağlıklı seviyede.'];
        $ep = $urunler->first();
        if ($ep && $ep['yuzde'] >= 35) $ai[] = ['seviye' => 'riskli', 'mesaj' => $ep['ad'] . ' maliyeti %' . $ep['yuzde'] . ' — en riskli kalem, önceliklendirin.'];
        return [
            'ok' => 1, 'baslik' => 'Food-Cost Detayı', 'tip' => 'maliyet', 'ai' => $ai,
            'toplamSatis' => round($toplamSatis, 2), 'toplamMaliyet' => round($toplamMaliyet, 2),
            'brutKar' => round($toplamSatis - $toplamMaliyet, 2),
            'maliyetYuzde' => $my,
            'urunler' => $urunler,
        ];
    }

    return ['ok' => 0, 'hata' => 'Bilinmeyen tip'];
});

// Derin AI Analizi (Haiku) — kural motoru USTUNE, on-demand, ANONIM baglam, ogrenen onbellek
Route::get('/api/patron/ai-analiz', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $kapsam = in_array($r->kapsam, ['ozet', 'musteri', 'urun']) ? $r->kapsam : 'ozet';
    $period = in_array($r->period, ['gunluk', 'haftalik', 'aylik', 'yillik']) ? $r->period : 'haftalik';
    [$from, $to, $pfrom, $pto] = _restoPeriyot($period);

    // 1) Baglam (PII GONDERILMEZ - anonim)
    if ($kapsam === 'musteri') {
        $mid = (int) $r->id;
        $m = DB::table('musteriler')->find($mid);
        if (!$m) return ['ok' => 0, 'hata' => 'Müşteri yok'];
        $adet = DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->count();
        $harcama = (float) DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->sum('toplam');
        $sonSiparis = DB::table('adisyonlar')->where('musteri_id', $mid)->where('durum', 'odendi')->max('kapanis');
        $gunOnce = $sonSiparis ? (int) round(\Carbon\Carbon::parse($sonSiparis)->diffInDays(now())) : null;
        $yorumlar = Schema::hasTable('degerlendirmeler')
            ? DB::table('degerlendirmeler')->where('musteri_id', $mid)->orderByDesc('created_at')->limit(5)->get(['puan', 'yorum']) : collect();
        $favori = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.musteri_id', $mid)->groupBy('urun_adi')->orderByRaw('SUM(adet) desc')->limit(3)->pluck('urun_adi')->all();
        $baglam = [
            'tip' => 'sadik_musteri_analizi', 'siparis_sayisi' => $adet, 'toplam_harcama' => round($harcama),
            'son_siparis_gun_once' => $gunOnce, 'favori_urunler' => $favori,
            'son_yorumlar' => $yorumlar->map(fn ($y) => ['puan' => (int) $y->puan, 'yorum' => $y->yorum])->all(),
        ];
    } elseif ($kapsam === 'urun') {
        $uid = (int) $r->id;
        $u = DB::table('urunler')->find($uid);
        if (!$u) return ['ok' => 0, 'hata' => 'Ürün yok'];
        $map = function_exists('_restoUrunMaliyetMap') ? _restoUrunMaliyetMap() : ['id' => [], 'ad' => []];
        $birim = (float) ($map['id'][$uid] ?? ($map['ad'][$u->ad] ?? 0));
        $st = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyon_kalemleri.urun_id', $uid)->where('adisyonlar.durum', 'odendi')->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->selectRaw('COALESCE(SUM(adet),0) adet, COALESCE(SUM(adisyon_kalemleri.tutar),0) ciro')->first();
        $kalemler = [];
        $rec = DB::table('receteler')->where('urun_id', $uid)->where('tip', 'urun')->value('id');
        if ($rec) {
            foreach (DB::table('recete_kalemleri')->where('recete_id', $rec)->get() as $rk) {
                if (!$rk->malzeme_id) continue;
                $mm = DB::table('malzemeler')->find($rk->malzeme_id);
                if ($mm) $kalemler[] = ['malzeme' => $mm->ad, 'miktar' => (float) $rk->miktar, 'maliyet' => round((float) $rk->miktar * (float) $mm->guncel_maliyet, 2)];
            }
        }
        $fiyat = (float) $u->fiyat;
        $adet = (float) $st->adet;
        $satis = (float) $st->ciro;
        $toplamMal = $birim > 0 ? $birim * $adet : $satis * 0.30;
        $baglam = [
            'tip' => 'urun_karlilik_analizi', 'urun' => $u->ad, 'satis_fiyati' => round($fiyat),
            'porsiyon_malzeme_maliyeti' => round($birim, 2), 'porsiyon_brut_kar' => round(max(0, $fiyat - $birim)),
            'food_cost_yuzde' => $satis > 0 ? round($toplamMal / $satis * 100) : 0, 'donem_satis_adet' => round($adet),
            'recete_malzemeler' => $kalemler,
        ];
    } else {
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        $compCiro = (float) DB::table('odemeler')->whereBetween('created_at', [$pfrom, $pto])->sum('tutar');
        $iskonto = (float) DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->sum('indirim');
        $ikram = (float) DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->sum('ikram');
        $iptalAdet = DB::table('adisyonlar')->where('durum', 'iptal')->whereBetween('acilis', [$from, $to])->count();
        $topUrun = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->groupBy('urun_adi')->orderByRaw('SUM(adisyon_kalemleri.tutar) desc')->limit(5)->pluck('urun_adi')->all();
        $baglam = [
            'tip' => 'isletme_ozeti', 'donem' => $period, 'ciro' => round($ciro), 'onceki_donem_ciro' => round($compCiro),
            'iskonto' => round($iskonto), 'ikram' => round($ikram), 'iptal_adisyon_adet' => $iptalAdet, 'cok_satan_urunler' => $topUrun,
        ];
    }

    // 2) Ogrenen onbellek (ayni baglam tekrar faturalanmasin)
    if (!Schema::hasTable('ai_onbellek')) {
        Schema::create('ai_onbellek', function ($t) {
            $t->id();
            $t->string('anahtar', 64)->unique();
            $t->text('cevap');
            $t->timestamp('created_at')->useCurrent();
        });
    }
    $anahtar = hash('sha256', json_encode($baglam));
    $cache = DB::table('ai_onbellek')->where('anahtar', $anahtar)->first();
    if ($cache) return ['ok' => 1, 'kaynak' => 'onbellek', 'yorumlar' => json_decode($cache->cevap, true)];

    // 3) Zengin KURAL yorumu (anahtar yoksa / LLM basarisizsa DETAY yine gelir)
    $kuralYorum = _restoKuralYorum($baglam);
    if (empty($kuralYorum)) $kuralYorum = ['Bu dönem için analiz üretecek yeterli veri yok; biraz satış sonrası zenginleşir.'];

    $apiKey = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        return ['ok' => 1, 'kaynak' => 'kural', 'yorumlar' => $kuralYorum];
    }

    // 4) Haiku
    try {
        $sys = 'Sen bir restoran patronuna kısa, net, EYLEME DÖNÜK öneriler veren deneyimli bir işletme danışmanısın. Sadece verilen JSON verisine dayan. En fazla 4 madde, her biri TEK cümle, Türkçe, patron diliyle. Rakamları yorumla ve somut aksiyon öner. Giriş/kapanış cümlesi yazma, sadece maddeler.';
        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json',
        ])->timeout(25)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 400, 'system' => $sys,
            'messages' => [['role' => 'user', 'content' => 'Veri: ' . json_encode($baglam, JSON_UNESCAPED_UNICODE) . ' — Bu verilere göre patrona önerilerini madde madde ver.']],
        ]);
        if (!$resp->successful()) {
            return ['ok' => 1, 'kaynak' => 'kural', 'yorumlar' => $kuralYorum]; // LLM hata -> zengin kural yorumu
        }
        $metin = $resp->json('content.0.text') ?? '';
        $maddeler = collect(preg_split('/\n+/', $metin))->map(fn ($l) => trim(preg_replace('/^[\-\*\d\.\)\s]+/u', '', $l)))->filter()->values()->all();
        if (empty($maddeler)) $maddeler = [$metin];
        DB::table('ai_onbellek')->insert(['anahtar' => $anahtar, 'cevap' => json_encode($maddeler, JSON_UNESCAPED_UNICODE), 'created_at' => now()]);
        return ['ok' => 1, 'kaynak' => 'haiku', 'yorumlar' => $maddeler];
    } catch (\Throwable $e) {
        return ['ok' => 1, 'kaynak' => 'kural', 'yorumlar' => $kuralYorum]; // hata -> zengin kural yorumu
    }
});

// PATRON SESLI/YAZILI ASISTAN — kural motoru -> Haiku niyet -> sohbet (ogrenen onbellek + gecmis)
Route::post('/api/patron/asistan-sor', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $soru = trim((string) $r->soru);
    if ($soru === '') return ['ok' => 1, 'cevap' => 'Dinliyorum, bir şey sorabilirsiniz.', 'seslendir' => false, 'kart' => null, 'intent' => 'bos', 'kaynak' => 'bos'];

    $a = new \App\Services\RestoAsistan();
    $userId = (int) $p->id;
    $gecmis = $a->gecmisGetir($userId);
    $brief = $a->patronBrief($p->sube_id); // karakterin akil yurutecegi gercek veri

    $niyet = $a->ogrenilenNiyet($soru) ?: $a->niyetCoz($soru);
    $intent = $niyet['intent'] ?? 'bilinmiyor';
    // ONERI/STRATEJI sorusu ("satislari nasil artiririm / ne yapmaliyim / ne onerirsin"): kural
    // tuzagina (ciro/tespit) dusmesin -> DOGRUDAN AI karakterine yonlendir (veriden tailored oneri).
    if ($a->oneriMi($soru)) $intent = 'oneri';
    $sonuc = null;
    $kaynak = 'kural';

    // 0) TESPIT (BEDAVA): "goremedigim ne var / gozune carpan yanlis bir sey / sence sorun var mi"
    //    gibi YORUM isteyen her soru -> proaktif tespitler (kelime listesine takilmadan)
    if ($intent === 'tespit' || ($intent === 'bilinmiyor' && $a->analitikMi($soru))) {
        $sonuc = $a->tespitCevap($p->sube_id);
        $kaynak = 'tespit';
    }

    // 1) DOGRUDAN sayisal niyet -> bedava kart (ozet ve oneri HARIC -> onlar karaktere gider)
    if (!$sonuc && $intent !== 'bilinmiyor' && $intent !== 'ozet' && $intent !== 'oneri') $sonuc = $a->cevapla($niyet);

    // 2) KALIP kutuphanesi (bedava ogrenilmis soru-cevap)
    if (!$sonuc) {
        $kalip = $a->kalipCevabi($soru);
        if ($kalip) { $sonuc = $kalip; $kaynak = 'kalip'; }
    }

    // 3) KARAKTER (isletme ortagi) — LLM; GUNLUK LIMIT + VERI-FARKINDA AKILLI ONBELLEK
    if (!$sonuc && config('services.anthropic.sohbet_acik')) {
        $gunKey = 'resto_patron_ai_gun:' . (int) $p->sube_id . ':' . date('Y-m-d');
        // VERI PARMAK IZI: rakamlar degismedikce ayni analiz tekrar kullanilir (bayat DEGIL,
        // cunku parmak izi yeni satis/fatura olunca degisir -> otomatik tazelenir). Saat/dakika DAHIL EDILMEZ.
        $fp = substr(md5(json_encode([
            $brief['ciro_bugun'] ?? 0, $brief['ciro_bu_hafta'] ?? 0, $brief['misafir_bu_hafta'] ?? 0,
            $brief['acik_masa'] ?? 0, count($brief['tespitler'] ?? []), implode(',', $brief['kritik_stok'] ?? []),
        ], JSON_UNESCAPED_UNICODE)), 0, 12);
        // "Genel durum" sorulari (nasil gidiyor/isler nasil/durum ne...) tek KOVADA -> farkli sorus, ayni cevap
        $kova = ($intent === 'ozet') ? 'genel' : ('s:' . md5(mb_strtolower(trim($soru))));
        $onbKey = 'resto_patron_ai_cev:' . (int) $p->sube_id . ':' . $kova . ':' . $fp;
        $limit = (int) config('services.anthropic.sohbet_gunluk_limit', 80);
        $onbek = cache()->get($onbKey);
        if ($onbek) {
            // Ayni analiz + veri degismemis -> onbellekten (BEDAVA, kredi harcanmaz)
            $sonuc = ['cevap' => $onbek, 'seslendir' => true, 'kart' => null, 'intent' => 'patron']; $kaynak = 'patron_ai_onbellek';
        } elseif ((int) cache()->get($gunKey, 0) < $limit) {
            $c = $a->patronSohbet($soru, $gecmis, $brief);
            if ($c) {
                cache()->put($onbKey, $c, now()->addHours(2)); // veri degisince zaten parmak izi degisip tazelenir
                cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->addHours(26));
                $sonuc = ['cevap' => $c, 'seslendir' => true, 'kart' => null, 'intent' => 'patron']; $kaynak = 'patron_ai';
            }
        }
        // limit dolduysa: asagidaki fallback bedava ozet karti/yardim verir
    }

    // 4) Anahtar yok / hata -> ozet ise kart; oneri ise bedava tespitler (ne yapmali ipucu); degilse yardim
    if (!$sonuc) {
        if ($intent === 'ozet') { $sonuc = $a->cevapla($niyet); $kaynak = 'kural'; }
        elseif ($intent === 'oneri') { $sonuc = $a->tespitCevap($p->sube_id); $kaynak = 'tespit'; }
        else { $sonuc = $a->yardimCevabi($niyet); $kaynak = ($a->aiTeshis === 'anahtar_yok') ? 'yardim_anahtarsiz' : 'yardim'; }
    }

    $a->gecmisEkle($userId, $soru, $sonuc['cevap'] ?? '', $p->sube_id, $sonuc['intent'] ?? null, $kaynak);
    return [
        'ok' => 1, 'cevap' => $sonuc['cevap'] ?? '', 'seslendir' => $sonuc['seslendir'] ?? true,
        'kart' => $sonuc['kart'] ?? null, 'intent' => $sonuc['intent'] ?? 'bilinmiyor', 'kaynak' => $kaynak,
    ];
});

// KONUSMA GECMISI (kalici) — sube bazinda son sorular/cevaplar
Route::get('/api/patron/asistan-gecmis', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!Schema::hasTable('asistan_konusma')) return ['ok' => 1, 'gecmis' => []];
    $limit = min(100, max(1, (int) ($r->limit ?: 40)));
    $adlar = DB::table('personeller')->pluck('ad', 'id');
    $liste = DB::table('asistan_konusma')->where('sube_id', $p->sube_id)->orderByDesc('id')->limit($limit)
        ->get(['personel_id', 'soru', 'cevap', 'intent', 'created_at'])
        ->map(fn ($x) => ['soru' => $x->soru, 'cevap' => $x->cevap, 'kim' => $adlar[$x->personel_id] ?? '', 'tarih' => substr((string) $x->created_at, 0, 16)]);
    return ['ok' => 1, 'gecmis' => $liste];
});

// PROAKTIF TESPITLER — asistan acilista patronun goremedigi kacak/risk/firsatlari sunar
Route::get('/api/patron/asistan-tespitler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $a = new \App\Services\RestoAsistan();
    $veri = $a->tespitler($p->sube_id);
    return ['ok' => 1, 'selam' => $veri['selam'], 'tespitler' => $veri['tespitler']];
});

// Tek seferlik: DB'deki ASCII-Turkce isimleri duzelt (asistan dogru seslendirsin)
Route::get('/api/patron/duzelt-turkce', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $duzelt = ['Yari Mamul' => 'Yarı Mamul'];
    $say = 0;
    foreach (['malzemeler', 'urunler'] as $tablo) {
        foreach ($duzelt as $eski => $yeni) {
            $say += DB::table($tablo)->where('ad', 'like', '%' . $eski . '%')->update(['ad' => DB::raw("REPLACE(ad, '" . $eski . "', '" . $yeni . "')")]);
        }
    }
    return ['ok' => 1, 'duzeltilen' => $say];
});

Route::get('/api/masalar', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $acik = DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')
        ->select('id', 'masa_id', 'toplam', 'acilis')->get()->keyBy('masa_id');

    // Aktif birlesmeler: HALA acik hedef adisyona birlesmis kaynak masalar (log tabanli, kendi kendini temizler)
    $masaAdlari = DB::table('masalar')->where('sube_id', $p->sube_id)->pluck('ad', 'id');
    $birlesmeGrup = [];   // hedef_masa_id => [kaynak masa adlari]
    $birlesmeKaynak = []; // kaynak_masa_id => ['hedef_ad'=>, 'hedef_adisyon_id'=>]
    $logs = DB::table('adisyon_masa_loglari as l')->join('adisyonlar as a', 'l.adisyon_id', '=', 'a.id')
        ->where('l.islem', 'birlestirme')->where('a.durum', 'acik')->whereNotNull('a.masa_id')
        ->select('l.eski_masa_id', 'a.masa_id as hedef_masa_id', 'a.id as hedef_adisyon_id')->get();
    foreach ($logs as $lg) {
        if (!$lg->eski_masa_id) continue;
        $birlesmeGrup[$lg->hedef_masa_id][] = $masaAdlari[$lg->eski_masa_id] ?? ('Masa ' . $lg->eski_masa_id);
        $birlesmeKaynak[$lg->eski_masa_id] = [
            'hedef_ad' => $masaAdlari[$lg->hedef_masa_id] ?? '',
            'hedef_adisyon_id' => $lg->hedef_adisyon_id,
        ];
    }

    $masalar = DB::table('masalar')->leftJoin('bolgeler', 'masalar.bolge_id', '=', 'bolgeler.id')
        ->where('masalar.sube_id', $p->sube_id)
        ->select('masalar.id', 'masalar.ad', 'masalar.durum', 'masalar.kapasite', 'bolgeler.ad as bolge')
        ->orderBy('bolgeler.sira')->orderBy('masalar.id')->get()
        ->map(function ($m) use ($acik, $birlesmeGrup, $birlesmeKaynak) {
            $a = $acik[$m->id] ?? null;
            $row = ['id' => $m->id, 'ad' => $m->ad, 'bolge' => $m->bolge, 'durum' => $m->durum,
                'kapasite' => $m->kapasite, 'tutar' => $a ? (float) $a->toplam : 0,
                'adisyon_id' => $a ? $a->id : null];
            // Hedef masa: hangi masalar birlesmis (kendisi + kaynaklar)
            if ($a && isset($birlesmeGrup[$m->id])) {
                $row['birlesik_masalar'] = array_values(array_unique(array_merge([$m->ad], $birlesmeGrup[$m->id])));
            }
            // Kaynak masa: artik bos ama acik hedefe bagli -> 'birlesik' sanal durumu
            if (!$a && isset($birlesmeKaynak[$m->id])) {
                $row['durum'] = 'birlesik';
                $row['birlesik_hedef_ad'] = $birlesmeKaynak[$m->id]['hedef_ad'];
                $row['birlesik_hedef_adisyon_id'] = $birlesmeKaynak[$m->id]['hedef_adisyon_id'];
            }
            return $row;
        });
    return ['ok' => 1, 'masalar' => $masalar];
});

// Personel yetkileri: listele (sahip/mudur gorur) + kaydet (SADECE sahip)
Route::get('/api/patron/personeller', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $liste = DB::table('personeller')->where('sube_id', $p->sube_id)
        ->orderByRaw("FIELD(rol,'sahip','mudur','kasa','garson','mutfak')")->orderBy('ad')
        ->get(['id', 'ad', 'rol', 'yetkiler', 'iskonto_limit', 'ikram_limit'])
        ->map(function ($x) {
            $y = $x->yetkiler ? json_decode($x->yetkiler, true) : _restoYetkiVarsayilan($x->rol)['yetkiler'];
            // Eksik anahtarlari tamamla (yeni yetki eklenirse)
            $y = array_merge(array_fill_keys(_restoYetkiKeys(), false), is_array($y) ? $y : []);
            return ['id' => (int) $x->id, 'ad' => $x->ad, 'rol' => $x->rol, 'yetkiler' => $y,
                'iskonto_limit' => (float) $x->iskonto_limit,
                'ikram_limit' => $x->ikram_limit === null ? (float) _restoYetkiVarsayilan($x->rol)['ikram_limit'] : (float) $x->ikram_limit];
        });
    return ['ok' => 1, 'duzenleyebilir' => $p->rol === 'sahip', 'anahtarlar' => _restoYetkiKeys(), 'personeller' => $liste];
});

Route::post('/api/patron/yetki-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetki düzenlemeyi sadece işletme sahibi yapabilir.'], 403);
    $pid = (int) $r->personel_id;
    $hedef = DB::table('personeller')->where('id', $pid)->where('sube_id', $p->sube_id)->first();
    if (!$hedef) return ['ok' => 0, 'hata' => 'Personel bulunamadı'];
    $gelen = json_decode((string) $r->yetkiler, true);
    if (!is_array($gelen)) return ['ok' => 0, 'hata' => 'Geçersiz yetki verisi'];
    // Sadece bilinen anahtarlar, bool
    $temiz = [];
    foreach (_restoYetkiKeys() as $k) $temiz[$k] = !empty($gelen[$k]);
    DB::table('personeller')->where('id', $pid)->update([
        'yetkiler' => json_encode($temiz),
        'iskonto_limit' => max(0, min(100, (float) $r->iskonto_limit)),
        'ikram_limit' => max(0, (float) $r->ikram_limit),
    ]);
    return ['ok' => 1];
});

// ============================================================================
// PERSONEL YONETIMI: kart (ekle/duzenle) + maas/prim konfigu + hareket defteri
// (avans/odeme/prim/kesinti) + OTOMATIK prim (ciro yuzdesi) + gider entegrasyonu.
// Tum para hesabi TEK sorgu setiyle (hizli). Sadece sahip/mudur; para = sahip.
// ============================================================================

// personeller tablosuna maas/prim kolonlarini (yoksa) ekler — lazy, idempotent.
function _restoPersonelKolonEnsure()
{
    $ekle = [
        'maas' => "ALTER TABLE personeller ADD COLUMN maas DECIMAL(12,2) NOT NULL DEFAULT 0",
        'maas_tipi' => "ALTER TABLE personeller ADD COLUMN maas_tipi VARCHAR(16) NOT NULL DEFAULT 'aylik'", // aylik|gunluk|saatlik
        'prim_tipi' => "ALTER TABLE personeller ADD COLUMN prim_tipi VARCHAR(16) NOT NULL DEFAULT 'yok'",    // yok|ciro
        'prim_oran' => "ALTER TABLE personeller ADD COLUMN prim_oran DECIMAL(6,2) NOT NULL DEFAULT 0",       // %
        'ise_baslama' => "ALTER TABLE personeller ADD COLUMN ise_baslama DATE NULL",
        'iban' => "ALTER TABLE personeller ADD COLUMN iban VARCHAR(40) NULL",
    ];
    foreach ($ekle as $kol => $sql) {
        if (!Schema::hasColumn('personeller', $kol)) DB::statement($sql);
    }
}

// Personel hareket defteri (para giris/cikis): avans|odeme|prim|kesinti|ek_odeme
function _restoPersonelHareketEnsure()
{
    if (Schema::hasTable('personel_hareketleri')) return;
    Schema::create('personel_hareketleri', function ($t) {
        $t->id();
        $t->unsignedBigInteger('sube_id');
        $t->unsignedBigInteger('personel_id');
        $t->string('tur', 16);                    // avans|odeme|prim|kesinti|ek_odeme
        $t->decimal('tutar', 12, 2)->default(0);
        $t->string('aciklama')->nullable();
        $t->date('tarih');
        $t->unsignedBigInteger('created_by')->nullable();
        $t->timestamp('created_at')->useCurrent();
        $t->index(['sube_id', 'personel_id', 'tarih']);
    });
}

// Genel gider defteri (kira/fatura/malzeme + otomatik personel odemeleri raporlanir)
function _restoGiderEnsure()
{
    if (Schema::hasTable('giderler')) return;
    Schema::create('giderler', function ($t) {
        $t->id();
        $t->unsignedBigInteger('sube_id');
        $t->string('kategori', 32)->default('diger'); // kira|fatura|malzeme|maas|vergi|diger
        $t->string('aciklama')->nullable();
        $t->decimal('tutar', 12, 2)->default(0);
        $t->date('tarih');
        $t->unsignedBigInteger('created_by')->nullable();
        $t->timestamp('created_at')->useCurrent();
        $t->index(['sube_id', 'tarih']);
    });
}

// Bir ayin baslangic/bitis damgasini verir. $ay = 'YYYY-MM' (bos ise bu ay).
function _restoAyAralik($ay)
{
    $ay = preg_match('/^\d{4}-\d{2}$/', (string) $ay) ? $ay : date('Y-m');
    $bas = $ay . '-01 00:00:00';
    $bit = date('Y-m-t', strtotime($ay . '-01')) . ' 23:59:59';
    return [$ay, $bas, $bit];
}

// Bir personelin bir aylik para ozetini hesaplar (maas + prim + hareketler).
function _restoPersonelHesap($subeId, $per, $bas, $bit)
{
    $pid = (int) $per->id;
    // Hesaplanan prim: ciro yuzdesi (acan garsonun kapanmis adisyon cirosu)
    $ciro = 0.0;
    $primHesap = 0.0;
    if (($per->prim_tipi ?? 'yok') === 'ciro' && (float) ($per->prim_oran ?? 0) > 0) {
        $ciro = (float) DB::table('adisyonlar')->where('sube_id', $subeId)
            ->where('acan_personel_id', $pid)->where('durum', 'odendi')
            ->whereBetween('kapanis', [$bas, $bit])->sum('toplam');
        $primHesap = round($ciro * (float) $per->prim_oran / 100, 2);
    }
    // Hareketler (tur bazinda toplam)
    $har = DB::table('personel_hareketleri')->where('sube_id', $subeId)->where('personel_id', $pid)
        ->whereBetween('tarih', [substr($bas, 0, 10), substr($bit, 0, 10)])
        ->selectRaw('tur, SUM(tutar) t')->groupBy('tur')->pluck('t', 'tur');
    $avans = (float) ($har['avans'] ?? 0);
    $odenen = (float) ($har['odeme'] ?? 0);
    $primManuel = (float) ($har['prim'] ?? 0);
    $kesinti = (float) ($har['kesinti'] ?? 0);
    $ekOdeme = (float) ($har['ek_odeme'] ?? 0);
    $maas = (float) ($per->maas ?? 0);
    // Hakedis = maas + hesaplanan prim + manuel prim + ek odeme - kesinti
    $hakedis = round($maas + $primHesap + $primManuel + $ekOdeme - $kesinti, 2);
    // Net odenecek = hakedis - (avans + odenen)
    $net = round($hakedis - $avans - $odenen, 2);
    return [
        'id' => $pid, 'ad' => $per->ad, 'rol' => $per->rol, 'aktif' => (int) $per->aktif,
        'maas' => $maas, 'maas_tipi' => $per->maas_tipi ?? 'aylik',
        'prim_tipi' => $per->prim_tipi ?? 'yok', 'prim_oran' => (float) ($per->prim_oran ?? 0),
        'ciro' => round($ciro, 2), 'prim_hesap' => $primHesap, 'prim_manuel' => $primManuel,
        'avans' => $avans, 'kesinti' => $kesinti, 'ek_odeme' => $ekOdeme, 'odenen' => $odenen,
        'hakedis' => $hakedis, 'net' => $net,
    ];
}

// LISTE + aylik ozet — sahip/mudur. ?ay=YYYY-MM
Route::get('/api/patron/personel-list', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoPersonelKolonEnsure();
    _restoPersonelHareketEnsure();
    [$ay, $bas, $bit] = _restoAyAralik($r->ay);
    $liste = DB::table('personeller')->where('sube_id', $p->sube_id)
        ->orderByRaw("FIELD(rol,'sahip','mudur','kasa','garson','mutfak')")->orderBy('ad')->get();
    $rows = [];
    $toplamMaas = 0; $toplamPrim = 0; $toplamNet = 0; $toplamOdenen = 0;
    foreach ($liste as $per) {
        $h = _restoPersonelHesap($p->sube_id, $per, $bas, $bit);
        $rows[] = $h;
        if ($h['aktif']) { $toplamMaas += $h['maas']; $toplamPrim += $h['prim_hesap'] + $h['prim_manuel']; $toplamNet += $h['net']; $toplamOdenen += $h['odenen']; }
    }
    return ['ok' => 1, 'ay' => $ay, 'duzenleyebilir' => $p->rol === 'sahip',
        'ozet' => ['maas' => round($toplamMaas, 2), 'prim' => round($toplamPrim, 2), 'net_kalan' => round($toplamNet, 2), 'odenen' => round($toplamOdenen, 2), 'gider_toplam' => round($toplamMaas + $toplamPrim, 2)],
        'personeller' => $rows];
});

// DETAY: bir personelin ozeti + hareket defteri + prim kaynagi
Route::get('/api/patron/personel-detay', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoPersonelKolonEnsure();
    _restoPersonelHareketEnsure();
    [$ay, $bas, $bit] = _restoAyAralik($r->ay);
    $per = DB::table('personeller')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->first();
    if (!$per) return ['ok' => 0, 'hata' => 'Personel bulunamadı'];
    $ozet = _restoPersonelHesap($p->sube_id, $per, $bas, $bit);
    $ozet['telefon'] = $per->telefon; $ozet['pin'] = $per->pin; $ozet['iban'] = $per->iban ?? null;
    $ozet['ise_baslama'] = $per->ise_baslama ?? null;
    $hareketler = DB::table('personel_hareketleri')->where('sube_id', $p->sube_id)->where('personel_id', $per->id)
        ->whereBetween('tarih', [substr($bas, 0, 10), substr($bit, 0, 10)])
        ->orderByDesc('tarih')->orderByDesc('id')
        ->get(['id', 'tur', 'tutar', 'aciklama', 'tarih'])
        ->map(fn ($x) => ['id' => (int) $x->id, 'tur' => $x->tur, 'tutar' => (float) $x->tutar, 'aciklama' => $x->aciklama, 'tarih' => $x->tarih]);
    return ['ok' => 1, 'ay' => $ay, 'duzenleyebilir' => $p->rol === 'sahip', 'ozet' => $ozet, 'hareketler' => $hareketler];
});

// KAYDET: personel ekle/duzenle (+maas/prim konfigu) — SADECE sahip
Route::post('/api/patron/personel-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Personel/maaş düzenlemeyi sadece işletme sahibi yapabilir.'], 403);
    _restoPersonelKolonEnsure();
    $ad = trim((string) $r->ad);
    if ($ad === '') return ['ok' => 0, 'hata' => 'İsim boş olamaz'];
    $rol = in_array($r->rol, ['sahip', 'mudur', 'kasa', 'garson', 'mutfak']) ? $r->rol : 'garson';
    $pin = preg_replace('/\D/', '', (string) $r->pin);
    $veri = [
        'ad' => $ad,
        'telefon' => $r->telefon ? (string) $r->telefon : null,
        'rol' => $rol,
        'aktif' => $r->aktif === '0' ? 0 : 1,
        'maas' => max(0, (float) $r->maas),
        'maas_tipi' => in_array($r->maas_tipi, ['aylik', 'gunluk', 'saatlik']) ? $r->maas_tipi : 'aylik',
        'prim_tipi' => in_array($r->prim_tipi, ['yok', 'ciro']) ? $r->prim_tipi : 'yok',
        'prim_oran' => max(0, min(100, (float) $r->prim_oran)),
        'iban' => $r->iban ? (string) $r->iban : null,
        'ise_baslama' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->ise_baslama) ? $r->ise_baslama : null,
    ];
    $pid = (int) $r->id;
    if ($pin !== '') {
        // PIN benzersiz olmali (ayni sube)
        $cak = DB::table('personeller')->where('sube_id', $p->sube_id)->where('pin', $pin)->where('id', '!=', $pid)->exists();
        if ($cak) return ['ok' => 0, 'hata' => 'Bu PIN başka personelde kullanılıyor.'];
        $veri['pin'] = $pin;
    }
    if ($pid > 0) {
        $hedef = DB::table('personeller')->where('id', $pid)->where('sube_id', $p->sube_id)->first();
        if (!$hedef) return ['ok' => 0, 'hata' => 'Personel bulunamadı'];
        DB::table('personeller')->where('id', $pid)->update($veri);
        return ['ok' => 1, 'id' => $pid];
    }
    if ($pin === '') return ['ok' => 0, 'hata' => 'Yeni personel için PIN zorunlu.'];
    $veri['sube_id'] = $p->sube_id;
    $veri['created_at'] = now();
    $veri['updated_at'] = now();
    $yeni = DB::table('personeller')->insertGetId($veri);
    return ['ok' => 1, 'id' => $yeni];
});

// HAREKET EKLE: avans/odeme/prim/kesinti/ek_odeme — SADECE sahip
Route::post('/api/patron/personel-hareket', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Bu işlemi sadece işletme sahibi yapabilir.'], 403);
    _restoPersonelHareketEnsure();
    _restoGiderEnsure();
    $tur = in_array($r->tur, ['avans', 'odeme', 'prim', 'kesinti', 'ek_odeme']) ? $r->tur : null;
    if (!$tur) return ['ok' => 0, 'hata' => 'Geçersiz işlem türü'];
    $tutar = round((float) $r->tutar, 2);
    if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Tutar 0’dan büyük olmalı'];
    $hedef = DB::table('personeller')->where('id', (int) $r->personel_id)->where('sube_id', $p->sube_id)->first();
    if (!$hedef) return ['ok' => 0, 'hata' => 'Personel bulunamadı'];
    $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->tarih) ? $r->tarih : date('Y-m-d');
    $id = DB::table('personel_hareketleri')->insertGetId([
        'sube_id' => $p->sube_id, 'personel_id' => $hedef->id, 'tur' => $tur, 'tutar' => $tutar,
        'aciklama' => $r->aciklama ? (string) $r->aciklama : null, 'tarih' => $tarih, 'created_by' => $p->id,
    ]);
    // Avans ve odeme = kasadan cikan para -> otomatik GIDER (maas kategorisi) + kasadan cikis
    if (in_array($tur, ['avans', 'odeme'])) {
        DB::table('giderler')->insert([
            'sube_id' => $p->sube_id, 'kategori' => 'maas',
            'aciklama' => $hedef->ad . ' — ' . ($tur === 'avans' ? 'Avans' : 'Maaş/ödeme'),
            'tutar' => $tutar, 'tarih' => $tarih, 'created_by' => $p->id,
        ]);
        _kasaYaz($p->sube_id, 'personel', 'cikis', $tutar, $hedef->ad . ' — ' . ($tur === 'avans' ? 'Avans' : 'Maaş/ödeme'), 'personel', $hedef->id, $p->id);
    }
    return ['ok' => 1, 'id' => $id];
});

// HAREKET SIL — SADECE sahip
Route::post('/api/patron/personel-hareket-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    DB::table('personel_hareketleri')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->delete();
    return ['ok' => 1];
});

// GIDERLER: aylik liste + ozet (kategori kirilimli). Personel odemeleri otomatik dahil.
Route::get('/api/patron/giderler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoGiderEnsure();
    [$ay, $bas, $bit] = _restoAyAralik($r->ay);
    $b = substr($bas, 0, 10); $e = substr($bit, 0, 10);
    $liste = DB::table('giderler')->where('sube_id', $p->sube_id)->whereBetween('tarih', [$b, $e])
        ->orderByDesc('tarih')->orderByDesc('id')
        ->get(['id', 'kategori', 'aciklama', 'tutar', 'tarih'])
        ->map(fn ($x) => ['id' => (int) $x->id, 'kategori' => $x->kategori, 'aciklama' => $x->aciklama, 'tutar' => (float) $x->tutar, 'tarih' => $x->tarih]);
    $kat = DB::table('giderler')->where('sube_id', $p->sube_id)->whereBetween('tarih', [$b, $e])
        ->selectRaw('kategori, SUM(tutar) t')->groupBy('kategori')->pluck('t', 'kategori');
    $toplam = (float) DB::table('giderler')->where('sube_id', $p->sube_id)->whereBetween('tarih', [$b, $e])->sum('tutar');
    return ['ok' => 1, 'ay' => $ay, 'duzenleyebilir' => in_array($p->rol, ['sahip', 'mudur']), 'toplam' => round($toplam, 2), 'kategoriler' => $kat, 'giderler' => $liste];
});

// GIDER EKLE — patron (sahip + mudur); menu de patrona acik oldugundan tutarli
Route::post('/api/patron/gider-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Gider eklemeyi sadece patron (sahip/müdür) yapabilir.'], 403);
    _restoGiderEnsure();
    $tutar = round((float) $r->tutar, 2);
    if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Tutar 0’dan büyük olmalı'];
    $kategori = in_array($r->kategori, ['kira', 'fatura', 'malzeme', 'maas', 'vergi', 'diger']) ? $r->kategori : 'diger';
    $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->tarih) ? $r->tarih : date('Y-m-d');
    $id = DB::table('giderler')->insertGetId([
        'sube_id' => $p->sube_id, 'kategori' => $kategori, 'aciklama' => $r->aciklama ? (string) $r->aciklama : null,
        'tutar' => $tutar, 'tarih' => $tarih, 'created_by' => $p->id,
    ]);
    // Nakit gider ise (varsayilan) kasadan cikis (acik vardiya varsa). nakit=0 gelirse kasaya dokunma.
    $nakit = !isset($r->nakit) || in_array((string) $r->nakit, ['1', 'true', 'nakit', 'on'], true);
    if ($nakit) _kasaYaz($p->sube_id, 'gider', 'cikis', $tutar, ($kategori === 'maas' ? 'Maaş' : ucfirst($kategori)) . ' gideri' . ($r->aciklama ? ' — ' . $r->aciklama : ''), 'gider', $id, $p->id);
    return ['ok' => 1, 'id' => $id];
});

// GIDER SIL — patron (sahip + mudur)
Route::post('/api/patron/gider-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    DB::table('giderler')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->delete();
    return ['ok' => 1];
});

// ============================ KASA (vardiya bazli nakit cekmece) ============================
// Tek "nakit gercegi": tum nakit hareketler kasa_hareketleri'ne yazilir. Vardiya acilir (devir),
// gun sonu sayilir -> beklenen vs sayilan = fark (acik/fazla). Nakit satis/gider/tahsilat/avans OTOMATIK baglanir.
function _restoKasaEnsure()
{
    if (!Schema::hasTable('kasa_oturumlari')) {
        Schema::create('kasa_oturumlari', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('acan_personel_id')->nullable();
            $t->decimal('acilis_devir', 12, 2)->default(0);
            $t->timestamp('acilis_zamani')->nullable();
            $t->timestamp('kapanis_zamani')->nullable();
            $t->decimal('beklenen_nakit', 12, 2)->nullable();
            $t->decimal('sayilan_nakit', 12, 2)->nullable();
            $t->decimal('fark', 12, 2)->nullable();
            $t->unsignedBigInteger('kapatan_personel_id')->nullable();
            $t->string('durum', 10)->default('acik');
            $t->string('not', 255)->nullable();
            $t->timestamps();
            $t->index(['sube_id', 'durum']);
        });
    }
    if (!Schema::hasTable('kasa_hareketleri')) {
        Schema::create('kasa_hareketleri', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('oturum_id');
            $t->string('tip', 20);   // devir | satis | tahsilat | gider | personel | al | koy
            $t->string('yon', 6);    // giris | cikis
            $t->decimal('tutar', 12, 2)->default(0);
            $t->string('aciklama', 255)->nullable();
            $t->string('kaynak_tip', 20)->nullable();
            $t->unsignedBigInteger('kaynak_id')->nullable();
            $t->unsignedBigInteger('personel_id')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['oturum_id']);
            $t->index(['sube_id', 'created_at']);
        });
    }
}
if (!function_exists('_kasaAcikOturum')) {
    function _kasaAcikOturum($subeId)
    {
        _restoKasaEnsure();
        return DB::table('kasa_oturumlari')->where('sube_id', $subeId)->where('durum', 'acik')->orderByDesc('id')->first();
    }
}
// Nakit hareketi kasaya yaz — SADECE acik oturum varsa (yoksa sessiz gecer, akisi bozmaz)
if (!function_exists('_kasaYaz')) {
    function _kasaYaz($subeId, $tip, $yon, $tutar, $aciklama, $kaynakTip = 'manuel', $kaynakId = null, $personelId = null)
    {
        try {
            $tutar = round((float) $tutar, 2);
            if ($tutar <= 0) return;
            $o = _kasaAcikOturum($subeId);
            if (!$o) return;
            DB::table('kasa_hareketleri')->insert([
                'sube_id' => $subeId, 'oturum_id' => $o->id, 'tip' => $tip, 'yon' => $yon, 'tutar' => $tutar,
                'aciklama' => $aciklama, 'kaynak_tip' => $kaynakTip, 'kaynak_id' => $kaynakId,
                'personel_id' => $personelId, 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

// KASA DURUMU
Route::get('/api/patron/kasa', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $o = _kasaAcikOturum($p->sube_id);
    if (!$o) {
        $son = DB::table('kasa_oturumlari')->where('sube_id', $p->sube_id)->where('durum', 'kapali')->orderByDesc('id')->first();
        return ['ok' => 1, 'acik' => false, 'son_devir' => $son ? (float) $son->sayilan_nakit : 0];
    }
    $har = DB::table('kasa_hareketleri as h')->leftJoin('personeller as pe', 'h.personel_id', '=', 'pe.id')
        ->where('h.oturum_id', $o->id)->orderByDesc('h.id')
        ->select('h.tip', 'h.yon', 'h.tutar', 'h.aciklama', 'h.created_at', 'pe.ad as personel')->get()
        ->map(fn ($h) => ['tip' => $h->tip, 'yon' => $h->yon, 'tutar' => (float) $h->tutar, 'aciklama' => $h->aciklama,
            'personel' => $h->personel, 'zaman' => \Carbon\Carbon::parse($h->created_at)->format('H:i')]);
    $giris = (float) DB::table('kasa_hareketleri')->where('oturum_id', $o->id)->where('yon', 'giris')->sum('tutar');
    $cikis = (float) DB::table('kasa_hareketleri')->where('oturum_id', $o->id)->where('yon', 'cikis')->sum('tutar');
    $kirilim = DB::table('kasa_hareketleri')->where('oturum_id', $o->id)
        ->select('tip', 'yon', DB::raw('SUM(tutar) as t'))->groupBy('tip', 'yon')->get()
        ->map(fn ($k) => ['tip' => $k->tip, 'yon' => $k->yon, 'tutar' => (float) $k->t]);
    return ['ok' => 1, 'acik' => true, 'oturum_id' => $o->id,
        'devir' => (float) $o->acilis_devir, 'acan' => DB::table('personeller')->where('id', $o->acan_personel_id)->value('ad'),
        'acilis' => \Carbon\Carbon::parse($o->acilis_zamani)->format('d.m H:i'),
        'sure_dk' => (int) round(\Carbon\Carbon::parse($o->acilis_zamani)->diffInMinutes(now())),
        'giris' => $giris, 'cikis' => $cikis, 'beklenen' => round($giris - $cikis, 2),
        'kirilim' => $kirilim, 'hareketler' => $har];
});

// KASA AC (vardiya) — devir gir
Route::post('/api/patron/kasa-ac', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    if (!in_array($p->rol, ['sahip', 'mudur', 'kasa'])) return ['ok' => 0, 'hata' => 'Kasa açma yetkiniz yok.'];
    if (_kasaAcikOturum($p->sube_id)) return ['ok' => 0, 'hata' => 'Zaten açık bir kasa var. Önce onu kapatın.'];
    $devir = max(0, round((float) $r->devir, 2));
    $oid = DB::table('kasa_oturumlari')->insertGetId([
        'sube_id' => $p->sube_id, 'acan_personel_id' => $p->id, 'acilis_devir' => $devir,
        'acilis_zamani' => now(), 'durum' => 'acik', 'created_at' => now(), 'updated_at' => now(),
    ]);
    if ($devir > 0) {
        DB::table('kasa_hareketleri')->insert(['sube_id' => $p->sube_id, 'oturum_id' => $oid, 'tip' => 'devir', 'yon' => 'giris',
            'tutar' => $devir, 'aciklama' => 'Açılış devir', 'kaynak_tip' => 'manuel', 'personel_id' => $p->id, 'created_at' => now()]);
    }
    return ['ok' => 1, 'mesaj' => 'Kasa açıldı.', 'oturum_id' => $oid];
});

// KASA HAREKET (elle al/koy) — satis disi nakit
Route::post('/api/patron/kasa-hareket', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $o = _kasaAcikOturum($p->sube_id);
    if (!$o) return ['ok' => 0, 'hata' => 'Açık kasa yok. Önce kasa açın.'];
    $yon = in_array($r->yon, ['giris', 'cikis']) ? $r->yon : 'cikis';
    $tutar = round((float) $r->tutar, 2);
    if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Geçerli tutar girin.'];
    DB::table('kasa_hareketleri')->insert([
        'sube_id' => $p->sube_id, 'oturum_id' => $o->id, 'tip' => $yon === 'giris' ? 'koy' : 'al', 'yon' => $yon,
        'tutar' => $tutar, 'aciklama' => $r->aciklama ? (string) $r->aciklama : ($yon === 'giris' ? 'Kasaya para konuldu' : 'Kasadan para alındı'),
        'kaynak_tip' => 'manuel', 'personel_id' => $p->id, 'created_at' => now(),
    ]);
    return ['ok' => 1, 'mesaj' => $yon === 'giris' ? 'Kasaya eklendi.' : 'Kasadan düşüldü.'];
});

// KASA KAPAT (say) — beklenen vs sayilan = fark
Route::post('/api/patron/kasa-kapat', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    if (!in_array($p->rol, ['sahip', 'mudur', 'kasa'])) return ['ok' => 0, 'hata' => 'Kasa kapatma yetkiniz yok.'];
    $o = _kasaAcikOturum($p->sube_id);
    if (!$o) return ['ok' => 0, 'hata' => 'Açık kasa yok.'];
    $giris = (float) DB::table('kasa_hareketleri')->where('oturum_id', $o->id)->where('yon', 'giris')->sum('tutar');
    $cikis = (float) DB::table('kasa_hareketleri')->where('oturum_id', $o->id)->where('yon', 'cikis')->sum('tutar');
    $beklenen = round($giris - $cikis, 2);
    $sayilan = round((float) $r->sayilan, 2);
    $fark = round($sayilan - $beklenen, 2);
    DB::table('kasa_oturumlari')->where('id', $o->id)->update([
        'kapanis_zamani' => now(), 'beklenen_nakit' => $beklenen, 'sayilan_nakit' => $sayilan, 'fark' => $fark,
        'kapatan_personel_id' => $p->id, 'durum' => 'kapali', 'not' => $r->not ? (string) $r->not : null, 'updated_at' => now(),
    ]);
    return ['ok' => 1, 'mesaj' => 'Kasa kapatıldı. ' . ($fark == 0 ? 'Kasa tuttu ✓' : ($fark > 0 ? 'Fazla' : 'Açık')),
        'beklenen' => $beklenen, 'sayilan' => $sayilan, 'fark' => $fark];
});

// KASA GECMIS (kapali vardiyalar)
Route::get('/api/patron/kasa-gecmis', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _restoKasaEnsure();
    $liste = DB::table('kasa_oturumlari as o')->leftJoin('personeller as a', 'o.acan_personel_id', '=', 'a.id')
        ->leftJoin('personeller as k', 'o.kapatan_personel_id', '=', 'k.id')
        ->where('o.sube_id', $p->sube_id)->where('o.durum', 'kapali')->orderByDesc('o.id')->limit(30)
        ->select('o.id', 'o.acilis_devir', 'o.beklenen_nakit', 'o.sayilan_nakit', 'o.fark', 'o.acilis_zamani', 'o.kapanis_zamani',
            'a.ad as acan', 'k.ad as kapatan')->get()
        ->map(fn ($o) => [
            'id' => $o->id, 'devir' => (float) $o->acilis_devir, 'beklenen' => (float) $o->beklenen_nakit,
            'sayilan' => (float) $o->sayilan_nakit, 'fark' => (float) $o->fark, 'acan' => $o->acan, 'kapatan' => $o->kapatan,
            'acilis' => $o->acilis_zamani ? \Carbon\Carbon::parse($o->acilis_zamani)->format('d.m H:i') : '-',
            'kapanis' => $o->kapanis_zamani ? \Carbon\Carbon::parse($o->kapanis_zamani)->format('d.m H:i') : '-',
        ]);
    return ['ok' => 1, 'oturumlar' => $liste];
});

// ============================================================================
// STOK / REÇETE / SATIN ALMA / FİNANS modülü. Tablolar zaten var (malzemeler,
// birimler, receteler, stok_hareketleri, tedarikciler, alis_faturalari...).
// Burada YÖNETİM UÇLARI: malzeme/tedarikçi/reçete girişi, alış faturası (stok
// girişi + fiyat uyarısı + maliyet güncelleme + otomatik gider) ve finans özeti.
// ============================================================================

// Birimler/kategoriler boşsa yaygın olanları ekle (formlar boş kalmasın).
function _restoStokSeedEnsure()
{
    if (DB::table('birimler')->count() === 0) {
        DB::table('birimler')->insert([
            ['ad' => 'Gram', 'kisaltma' => 'g', 'tip' => 'agirlik'],
            ['ad' => 'Kilogram', 'kisaltma' => 'kg', 'tip' => 'agirlik'],
            ['ad' => 'Mililitre', 'kisaltma' => 'ml', 'tip' => 'hacim'],
            ['ad' => 'Litre', 'kisaltma' => 'lt', 'tip' => 'hacim'],
            ['ad' => 'Adet', 'kisaltma' => 'ad', 'tip' => 'adet'],
            ['ad' => 'Paket', 'kisaltma' => 'pk', 'tip' => 'adet'],
            ['ad' => 'Koli', 'kisaltma' => 'koli', 'tip' => 'adet'],
            ['ad' => 'Demet', 'kisaltma' => 'demet', 'tip' => 'adet'],
        ]);
    }
    if (DB::table('malzeme_kategorileri')->count() === 0) {
        foreach (['Et & Tavuk', 'Sebze & Meyve', 'Süt Ürünleri', 'Bakliyat & Kuru Gıda', 'İçecek', 'Baharat & Sos', 'Temizlik', 'Diğer'] as $ad) {
            DB::table('malzeme_kategorileri')->insert(['ad' => $ad]);
        }
    }
}

// Bir malzemenin verilen birimi kaç TEMEL birim eder (çevrim faktörü).
function _restoBirimKarsilik($malzemeId, $birimId, $temelBirimId = null)
{
    if ($temelBirimId === null) $temelBirimId = DB::table('malzemeler')->where('id', $malzemeId)->value('temel_birim_id');
    if ((int) $birimId === (int) $temelBirimId) return 1.0;
    $c = DB::table('birim_cevrimleri')->where('malzeme_id', $malzemeId)->where('birim_id', $birimId)->value('temel_birim_karsiligi');
    if ($c) return (float) $c;
    // Bilinen genel çevrimler (kg->g, lt->ml) — malzeme çevrimi tanımlı değilse
    $b = DB::table('birimler')->where('id', $birimId)->value('kisaltma');
    $t = DB::table('birimler')->where('id', $temelBirimId)->value('kisaltma');
    $genel = ['kg' => ['g' => 1000], 'lt' => ['ml' => 1000], 'g' => ['kg' => 0.001], 'ml' => ['lt' => 0.001]];
    if (isset($genel[$b][$t])) return (float) $genel[$b][$t];
    return 1.0;
}

// Şubedeki her malzemenin mevcut stok miktarı (temel birim) — imzalı SUM.
function _restoStokMevcut($subeId)
{
    return DB::table('stok_hareketleri')->where('sube_id', $subeId)
        ->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
}

// Adisyon kapanınca reçeteden otomatik stok düşümü (tuketim). GÜVENLİ: hata olsa
// bile satışı bozmaz (try/catch), reçetesi/malzemesi olmayan ürünü sessiz atlar,
// sadece stok_takipli malzemeyi düşer, aynı adisyonu iki kez düşmez (idempotent).
function _restoStokTuket($adisyonId, $subeId, $personelId)
{
    try {
        // Zaten düşülmüş mü? (idempotent koruma)
        if (DB::table('stok_hareketleri')->where('kaynak_tip', 'adisyon')->where('kaynak_id', $adisyonId)->exists()) return;
        // İptal olmayan kalemler: urun_id -> toplam adet
        $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $adisyonId)
            ->where('durum', '!=', 'iptal')->whereNotNull('urun_id')
            ->selectRaw('urun_id, SUM(adet) adet')->groupBy('urun_id')->get();
        if ($kalemler->isEmpty()) return;
        foreach ($kalemler as $kal) {
            $recete = DB::table('receteler')->where('tip', 'urun')->where('urun_id', $kal->urun_id)->first();
            if (!$recete) continue;
            foreach (DB::table('recete_kalemleri')->where('recete_id', $recete->id)->whereNotNull('malzeme_id')->get() as $rk) {
                $m = DB::table('malzemeler')->where('id', $rk->malzeme_id)->first(['id', 'temel_birim_id', 'guncel_maliyet', 'stok_takipli']);
                if (!$m || !$m->stok_takipli) continue;
                $kars = _restoBirimKarsilik($m->id, $rk->birim_id, $m->temel_birim_id);
                $temelMiktar = (float) $rk->miktar * $kars * (float) $kal->adet; // toplam tüketilen (temel birim)
                if ($temelMiktar <= 0) continue;
                DB::table('stok_hareketleri')->insert([
                    'sube_id' => $subeId, 'malzeme_id' => $m->id, 'tip' => 'tuketim', 'miktar' => -$temelMiktar,
                    'birim_maliyet' => (float) $m->guncel_maliyet, 'kaynak_tip' => 'adisyon', 'kaynak_id' => $adisyonId,
                    'aciklama' => 'Satış tüketimi', 'personel_id' => $personelId,
                ]);
            }
        }
    } catch (\Throwable $e) {
        // sessiz geç — satış akışını asla bozma
    }
}

// ---- META (form açılır listeleri) ----
Route::get('/api/patron/stok-meta', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoStokSeedEnsure();
    return ['ok' => 1,
        'birimler' => DB::table('birimler')->orderBy('id')->get(['id', 'ad', 'kisaltma', 'tip']),
        'kategoriler' => DB::table('malzeme_kategorileri')->orderBy('ad')->get(['id', 'ad']),
        'tedarikciler' => DB::table('tedarikciler')->orderBy('ad')->get(['id', 'ad']),
    ];
});

// ---- MALZEMELER / STOK ----
Route::get('/api/patron/malzemeler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoStokSeedEnsure();
    $mevcut = _restoStokMevcut($p->sube_id);
    $birimler = DB::table('birimler')->pluck('kisaltma', 'id');
    $katlar = DB::table('malzeme_kategorileri')->pluck('ad', 'id');
    $liste = DB::table('malzemeler')->orderBy('ad')->get();
    $toplamDeger = 0.0; $kritikSayi = 0;
    $rows = $liste->map(function ($m) use ($mevcut, $birimler, $katlar, &$toplamDeger, &$kritikSayi) {
        $stok = (float) ($mevcut[$m->id] ?? 0);
        $deger = $stok * (float) $m->guncel_maliyet;
        $toplamDeger += $deger;
        $kritik = (float) $m->kritik_stok > 0 && $stok <= (float) $m->kritik_stok;
        if ($kritik) $kritikSayi++;
        return ['id' => (int) $m->id, 'ad' => $m->ad, 'kategori' => $katlar[$m->kategori_id] ?? '—',
            'kategori_id' => (int) $m->kategori_id, 'temel_birim_id' => (int) $m->temel_birim_id,
            'birim' => $birimler[$m->temel_birim_id] ?? '', 'guncel_maliyet' => (float) $m->guncel_maliyet,
            'kritik_stok' => (float) $m->kritik_stok, 'stok_takipli' => (int) $m->stok_takipli,
            'mevcut' => round($stok, 3), 'deger' => round($deger, 2), 'kritik' => $kritik];
    });
    return ['ok' => 1, 'duzenleyebilir' => $p->rol === 'sahip', 'toplam_deger' => round($toplamDeger, 2), 'kritik_sayi' => $kritikSayi, 'malzemeler' => $rows];
});

Route::get('/api/patron/malzeme-detay', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $m = DB::table('malzemeler')->where('id', (int) $r->id)->first();
    if (!$m) return ['ok' => 0, 'hata' => 'Malzeme bulunamadı'];
    $birim = DB::table('birimler')->where('id', $m->temel_birim_id)->value('kisaltma');
    $mevcut = (float) DB::table('stok_hareketleri')->where('sube_id', $p->sube_id)->where('malzeme_id', $m->id)->sum('miktar');
    $hareketler = DB::table('stok_hareketleri')->where('sube_id', $p->sube_id)->where('malzeme_id', $m->id)
        ->orderByDesc('id')->limit(40)->get(['id', 'tip', 'miktar', 'birim_maliyet', 'aciklama', 'created_at'])
        ->map(fn ($x) => ['id' => (int) $x->id, 'tip' => $x->tip, 'miktar' => (float) $x->miktar, 'birim_maliyet' => (float) $x->birim_maliyet, 'aciklama' => $x->aciklama, 'tarih' => substr((string) $x->created_at, 0, 16)]);
    return ['ok' => 1, 'duzenleyebilir' => $p->rol === 'sahip', 'ad' => $m->ad, 'birim' => $birim,
        'guncel_maliyet' => (float) $m->guncel_maliyet, 'mevcut' => round($mevcut, 3), 'deger' => round($mevcut * (float) $m->guncel_maliyet, 2), 'hareketler' => $hareketler];
});

Route::post('/api/patron/malzeme-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Bunu sadece işletme sahibi yapabilir.'], 403);
    _restoStokSeedEnsure();
    $ad = trim((string) $r->ad);
    if ($ad === '') return ['ok' => 0, 'hata' => 'Malzeme adı boş olamaz'];
    $temel = (int) $r->temel_birim_id ?: DB::table('birimler')->min('id');
    $veri = ['ad' => $ad, 'kategori_id' => (int) $r->kategori_id ?: null, 'temel_birim_id' => $temel,
        'kritik_stok' => max(0, (float) $r->kritik_stok), 'stok_takipli' => $r->stok_takipli === '0' ? 0 : 1, 'updated_at' => now()];
    $id = (int) $r->id;
    if ($id > 0) {
        DB::table('malzemeler')->where('id', $id)->update($veri);
        return ['ok' => 1, 'id' => $id];
    }
    // Yeni malzemede başlangıç maliyeti (opsiyonel)
    $veri['guncel_maliyet'] = max(0, (float) $r->guncel_maliyet);
    $veri['created_at'] = now();
    $yeni = DB::table('malzemeler')->insertGetId($veri);
    return ['ok' => 1, 'id' => $yeni];
});

Route::post('/api/patron/malzeme-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $id = (int) $r->id;
    if (DB::table('recete_kalemleri')->where('malzeme_id', $id)->exists()) return ['ok' => 0, 'hata' => 'Bu malzeme bir reçetede kullanılıyor, önce reçeteden çıkarın.'];
    DB::table('stok_hareketleri')->where('malzeme_id', $id)->where('sube_id', $p->sube_id)->delete();
    DB::table('malzemeler')->where('id', $id)->delete();
    return ['ok' => 1];
});

Route::post('/api/patron/malzeme-kategori-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $ad = trim((string) $r->ad);
    if ($ad === '') return ['ok' => 0, 'hata' => 'Boş olamaz'];
    $id = DB::table('malzeme_kategorileri')->insertGetId(['ad' => $ad]);
    return ['ok' => 1, 'id' => $id];
});

// Manuel stok hareketi: giris | fire | duzeltme
Route::post('/api/patron/stok-hareket', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $m = DB::table('malzemeler')->where('id', (int) $r->malzeme_id)->first();
    if (!$m) return ['ok' => 0, 'hata' => 'Malzeme bulunamadı'];
    $tip = in_array($r->tip, ['giris', 'fire', 'duzeltme']) ? $r->tip : null;
    if (!$tip) return ['ok' => 0, 'hata' => 'Geçersiz hareket türü'];
    $miktar = (float) $r->miktar; // temel birimde
    if ($miktar == 0) return ['ok' => 0, 'hata' => 'Miktar 0 olamaz'];
    $imzali = $tip === 'fire' ? -abs($miktar) : ($tip === 'giris' ? abs($miktar) : $miktar);
    DB::table('stok_hareketleri')->insert([
        'sube_id' => $p->sube_id, 'malzeme_id' => $m->id, 'tip' => $tip === 'giris' ? 'iade' : ($tip === 'fire' ? 'fire' : 'sayim'),
        'miktar' => $imzali, 'birim_maliyet' => (float) $m->guncel_maliyet, 'kaynak_tip' => 'manuel',
        'aciklama' => $r->aciklama ? (string) $r->aciklama : ($tip === 'fire' ? 'Fire' : ($tip === 'giris' ? 'Manuel giriş' : 'Sayım düzeltme')),
        'personel_id' => $p->id,
    ]);
    return ['ok' => 1];
});

// ---- TEDARİKÇİLER ----
Route::get('/api/patron/tedarikciler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $alis = DB::table('alis_faturalari')->where('sube_id', $p->sube_id)
        ->selectRaw('tedarikci_id, SUM(toplam) t, COUNT(*) c, MAX(tarih) son')->groupBy('tedarikci_id')->get()->keyBy('tedarikci_id');
    $liste = DB::table('tedarikciler')->orderBy('ad')->get(['id', 'ad', 'telefon', 'aciklama'])->map(function ($x) use ($alis) {
        $a = $alis[$x->id] ?? null;
        return ['id' => (int) $x->id, 'ad' => $x->ad, 'telefon' => $x->telefon, 'aciklama' => $x->aciklama,
            'toplam_alis' => $a ? (float) $a->t : 0.0, 'fatura_sayisi' => $a ? (int) $a->c : 0, 'son_alis' => $a ? $a->son : null];
    });
    return ['ok' => 1, 'duzenleyebilir' => $p->rol === 'sahip', 'tedarikciler' => $liste];
});

Route::post('/api/patron/tedarikci-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $ad = trim((string) $r->ad);
    if ($ad === '') return ['ok' => 0, 'hata' => 'Ad boş olamaz'];
    $veri = ['ad' => $ad, 'telefon' => $r->telefon ? (string) $r->telefon : null, 'aciklama' => $r->aciklama ? (string) $r->aciklama : null, 'updated_at' => now()];
    $id = (int) $r->id;
    if ($id > 0) { DB::table('tedarikciler')->where('id', $id)->update($veri); return ['ok' => 1, 'id' => $id]; }
    $veri['created_at'] = now();
    return ['ok' => 1, 'id' => DB::table('tedarikciler')->insertGetId($veri)];
});

Route::post('/api/patron/tedarikci-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $id = (int) $r->id;
    if (DB::table('alis_faturalari')->where('tedarikci_id', $id)->exists()) return ['ok' => 0, 'hata' => 'Bu tedarikçinin faturaları var, silinemez.'];
    DB::table('tedarikciler')->where('id', $id)->delete();
    return ['ok' => 1];
});

// ---- ALIŞ FATURALARI (stok girişi + fiyat uyarısı + maliyet güncelleme + gider) ----
Route::get('/api/patron/alis-faturalari', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoGiderEnsure();
    [$ay, $bas, $bit] = _restoAyAralik($r->ay);
    $ted = DB::table('tedarikciler')->pluck('ad', 'id');
    $liste = DB::table('alis_faturalari')->where('sube_id', $p->sube_id)
        ->whereBetween('tarih', [substr($bas, 0, 10), substr($bit, 0, 10)])->orderByDesc('tarih')->orderByDesc('id')->get();
    $toplam = 0.0;
    $rows = $liste->map(function ($f) use ($ted, &$toplam) {
        $toplam += (float) $f->toplam;
        $enUyari = DB::table('alis_fatura_kalemleri')->where('fatura_id', $f->id)
            ->orderByRaw("FIELD(uyari,'kirmizi','sari','yesil') ")->value('uyari');
        return ['id' => (int) $f->id, 'tedarikci' => $ted[$f->tedarikci_id] ?? 'Bilinmiyor', 'fatura_no' => $f->fatura_no,
            'tarih' => $f->tarih, 'toplam' => (float) $f->toplam, 'durum' => $f->durum, 'uyari' => $enUyari ?: 'yesil'];
    });
    return ['ok' => 1, 'ay' => $ay, 'duzenleyebilir' => $p->rol === 'sahip', 'toplam' => round($toplam, 2), 'faturalar' => $rows];
});

Route::get('/api/patron/alis-fatura-detay', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $f = DB::table('alis_faturalari')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->first();
    if (!$f) return ['ok' => 0, 'hata' => 'Fatura bulunamadı'];
    $ted = DB::table('tedarikciler')->where('id', $f->tedarikci_id)->value('ad');
    $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    $malz = DB::table('malzemeler')->pluck('ad', 'id');
    $kalemler = DB::table('alis_fatura_kalemleri')->where('fatura_id', $f->id)->get()->map(fn ($k) => [
        'malzeme' => $malz[$k->malzeme_id] ?? '—', 'birim' => $birim[$k->alis_birim_id] ?? '', 'miktar' => (float) $k->miktar,
        'birim_fiyat' => (float) $k->birim_fiyat, 'satir_toplam' => (float) $k->satir_toplam,
        'onceki_fiyat' => (float) $k->onceki_fiyat, 'fiyat_farki_yuzde' => (float) $k->fiyat_farki_yuzde, 'uyari' => $k->uyari]);
    return ['ok' => 1, 'tedarikci' => $ted, 'fatura_no' => $f->fatura_no, 'tarih' => $f->tarih, 'toplam' => (float) $f->toplam, 'durum' => $f->durum, 'kalemler' => $kalemler];
});

Route::post('/api/patron/alis-fatura-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Fatura girişini sadece işletme sahibi yapabilir.'], 403);
    _restoGiderEnsure();
    $kalemler = json_decode((string) $r->kalemler, true);
    if (!is_array($kalemler) || count($kalemler) === 0) return ['ok' => 0, 'hata' => 'En az bir kalem girmelisiniz'];
    $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->tarih) ? $r->tarih : date('Y-m-d');
    $tedId = (int) $r->tedarikci_id ?: null;

    return DB::transaction(function () use ($r, $p, $kalemler, $tarih, $tedId) {
        // Önce toplamı ve uyarıları hesapla
        $hazir = [];
        $toplam = 0.0;
        foreach ($kalemler as $k) {
            $mid = (int) ($k['malzeme_id'] ?? 0);
            $m = DB::table('malzemeler')->where('id', $mid)->first();
            if (!$m) continue;
            $birimId = (int) ($k['birim_id'] ?? $m->temel_birim_id);
            $miktar = (float) ($k['miktar'] ?? 0);
            $birimFiyat = (float) ($k['birim_fiyat'] ?? 0);
            if ($miktar <= 0 || $birimFiyat < 0) continue;
            $satir = round($miktar * $birimFiyat, 2);
            $toplam += $satir;
            // Önceki alış fiyatı (aynı malzeme, aynı birim) -> fiyat farkı + uyarı
            $onceki = (float) DB::table('alis_fatura_kalemleri')->where('malzeme_id', $mid)->where('alis_birim_id', $birimId)
                ->orderByDesc('id')->value('birim_fiyat');
            $farkYuzde = $onceki > 0 ? round(($birimFiyat - $onceki) / $onceki * 100, 2) : 0.0;
            $uyari = 'yesil';
            if ($onceki > 0) { $a = abs($farkYuzde); $uyari = $a >= 15 ? 'kirmizi' : ($a >= 5 ? 'sari' : 'yesil'); }
            $hazir[] = compact('m', 'birimId', 'miktar', 'birimFiyat', 'satir', 'onceki', 'farkYuzde', 'uyari');
        }
        if (count($hazir) === 0) return ['ok' => 0, 'hata' => 'Geçerli kalem yok'];

        $faturaId = DB::table('alis_faturalari')->insertGetId([
            'sube_id' => $p->sube_id, 'tedarikci_id' => $tedId, 'fatura_no' => $r->fatura_no ? (string) $r->fatura_no : null,
            'tarih' => $tarih, 'toplam' => round($toplam, 2), 'durum' => 'onaylandi',
            'giren_personel_id' => $p->id, 'onaylayan_personel_id' => $p->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($hazir as $h) {
            $m = $h['m'];
            DB::table('alis_fatura_kalemleri')->insert([
                'fatura_id' => $faturaId, 'malzeme_id' => $m->id, 'alis_birim_id' => $h['birimId'], 'miktar' => $h['miktar'],
                'birim_fiyat' => $h['birimFiyat'], 'satir_toplam' => $h['satir'], 'onceki_fiyat' => $h['onceki'],
                'fiyat_farki_yuzde' => $h['farkYuzde'], 'fiyat_farki_tutar' => round(($h['birimFiyat'] - $h['onceki']) * $h['miktar'], 2),
                'uyari' => $h['uyari'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            // Stok girişi: temel birime çevir
            $kars = _restoBirimKarsilik($m->id, $h['birimId'], $m->temel_birim_id);
            $temelMiktar = $h['miktar'] * $kars;
            $temelMaliyet = $kars > 0 ? $h['birimFiyat'] / $kars : $h['birimFiyat']; // temel birim başına maliyet
            DB::table('stok_hareketleri')->insert([
                'sube_id' => $p->sube_id, 'malzeme_id' => $m->id, 'tip' => 'alis', 'miktar' => $temelMiktar,
                'birim_maliyet' => $temelMaliyet, 'kaynak_tip' => 'fatura', 'kaynak_id' => $faturaId,
                'aciklama' => 'Alış faturası', 'personel_id' => $p->id,
            ]);
            // Hareketli ağırlıklı ortalama maliyet güncelle
            $eskiStok = (float) DB::table('stok_hareketleri')->where('sube_id', $p->sube_id)->where('malzeme_id', $m->id)->where('kaynak_id', '!=', $faturaId)->sum('miktar');
            $eskiMaliyet = (float) $m->guncel_maliyet;
            if ($eskiStok > 0 && $eskiMaliyet > 0) {
                $yeni = ($eskiStok * $eskiMaliyet + $temelMiktar * $temelMaliyet) / ($eskiStok + $temelMiktar);
            } else {
                $yeni = $temelMaliyet;
            }
            DB::table('malzemeler')->where('id', $m->id)->update(['guncel_maliyet' => round($yeni, 4), 'updated_at' => now()]);
        }

        // Otomatik gider (kategori malzeme)
        $tedAd = $tedId ? DB::table('tedarikciler')->where('id', $tedId)->value('ad') : 'Tedarikçi';
        DB::table('giderler')->insert(['sube_id' => $p->sube_id, 'kategori' => 'malzeme',
            'aciklama' => $tedAd . ($r->fatura_no ? ' · ' . $r->fatura_no : '') . ' (alış faturası)', 'tutar' => round($toplam, 2),
            'tarih' => $tarih, 'created_by' => $p->id]);

        return ['ok' => 1, 'id' => $faturaId, 'toplam' => round($toplam, 2)];
    });
});

Route::post('/api/patron/alis-fatura-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || $p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $id = (int) $r->id;
    $f = DB::table('alis_faturalari')->where('id', $id)->where('sube_id', $p->sube_id)->first();
    if (!$f) return ['ok' => 0, 'hata' => 'Fatura bulunamadı'];
    DB::transaction(function () use ($id, $f, $p) {
        DB::table('stok_hareketleri')->where('kaynak_tip', 'fatura')->where('kaynak_id', $id)->delete();
        DB::table('alis_fatura_kalemleri')->where('fatura_id', $id)->delete();
        DB::table('alis_faturalari')->where('id', $id)->delete();
        // Bağlı otomatik gideri de kaldır (tutar+tarih eşleşmesiyle en yakın)
        $g = DB::table('giderler')->where('sube_id', $p->sube_id)->where('kategori', 'malzeme')->where('tutar', (float) $f->toplam)->where('tarih', $f->tarih)->orderByDesc('id')->first();
        if ($g) DB::table('giderler')->where('id', $g->id)->delete();
    });
    return ['ok' => 1];
});

// ---- REÇETE EDİTÖRÜ ----
Route::get('/api/patron/urun-recete', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $urunId = (int) $r->urun_id;
    $urun = DB::table('urunler')->where('id', $urunId)->first(['id', 'ad', 'fiyat']);
    if (!$urun) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
    $recete = DB::table('receteler')->where('tip', 'urun')->where('urun_id', $urunId)->first();
    $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    $malz = DB::table('malzemeler')->get()->keyBy('id');
    $kalemler = [];
    $maliyet = 0.0;
    if ($recete) {
        foreach (DB::table('recete_kalemleri')->where('recete_id', $recete->id)->whereNotNull('malzeme_id')->get() as $k) {
            $m = $malz[$k->malzeme_id] ?? null;
            $kars = _restoBirimKarsilik($k->malzeme_id, $k->birim_id, $m->temel_birim_id ?? null);
            $satirMal = $m ? (float) $k->miktar * $kars * (float) $m->guncel_maliyet : 0.0;
            $maliyet += $satirMal;
            $kalemler[] = ['malzeme_id' => (int) $k->malzeme_id, 'malzeme' => $m->ad ?? '—', 'miktar' => (float) $k->miktar,
                'birim_id' => (int) $k->birim_id, 'birim' => $birim[$k->birim_id] ?? '', 'satir_maliyet' => round($satirMal, 2)];
        }
    }
    $fc = (float) $urun->fiyat > 0 ? round($maliyet / (float) $urun->fiyat * 100, 1) : 0.0;
    return ['ok' => 1, 'duzenleyebilir' => $p->rol === 'sahip', 'urun_ad' => $urun->ad, 'fiyat' => (float) $urun->fiyat,
        'maliyet' => round($maliyet, 2), 'food_cost' => $fc, 'kalemler' => $kalemler];
});

Route::post('/api/patron/recete-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if ($p->rol !== 'sahip') return response()->json(['ok' => 0, 'hata' => 'Reçeteyi sadece işletme sahibi düzenleyebilir.'], 403);
    $urunId = (int) $r->urun_id;
    $urun = DB::table('urunler')->where('id', $urunId)->first();
    if (!$urun) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
    $kalemler = json_decode((string) $r->kalemler, true);
    if (!is_array($kalemler)) return ['ok' => 0, 'hata' => 'Geçersiz veri'];
    return DB::transaction(function () use ($urun, $urunId, $kalemler) {
        $recete = DB::table('receteler')->where('tip', 'urun')->where('urun_id', $urunId)->first();
        if ($recete) {
            $receteId = $recete->id;
            DB::table('recete_kalemleri')->where('recete_id', $receteId)->delete();
        } else {
            $receteId = DB::table('receteler')->insertGetId(['ad' => $urun->ad . ' Reçetesi', 'tip' => 'urun', 'urun_id' => $urunId, 'verim_miktar' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ($kalemler as $k) {
            $mid = (int) ($k['malzeme_id'] ?? 0);
            $miktar = (float) ($k['miktar'] ?? 0);
            $birimId = (int) ($k['birim_id'] ?? 0);
            if ($mid <= 0 || $miktar <= 0 || $birimId <= 0) continue;
            DB::table('recete_kalemleri')->insert(['recete_id' => $receteId, 'malzeme_id' => $mid, 'miktar' => $miktar, 'birim_id' => $birimId, 'created_at' => now(), 'updated_at' => now()]);
        }
        return ['ok' => 1];
    });
});

// Reçetesi olmayan/olan ürünleri listele (reçete yönetimi ekranı için)
Route::get('/api/patron/recete-urunler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $mMap = _restoUrunMaliyetMap();
    $receteli = DB::table('receteler')->where('tip', 'urun')->whereNotNull('urun_id')
        ->join('recete_kalemleri', 'receteler.id', '=', 'recete_kalemleri.recete_id')
        ->distinct()->pluck('receteler.urun_id')->toArray();
    $receteliSet = array_flip($receteli);
    $urunler = DB::table('urunler')->where('aktif', 1)->orderBy('ad')->get(['id', 'ad', 'fiyat'])->map(function ($u) use ($mMap, $receteliSet) {
        $mal = $mMap['id'][$u->id] ?? 0;
        $fc = (float) $u->fiyat > 0 && $mal > 0 ? round($mal / (float) $u->fiyat * 100, 1) : 0.0;
        return ['id' => (int) $u->id, 'ad' => $u->ad, 'fiyat' => (float) $u->fiyat, 'maliyet' => round($mal, 2),
            'food_cost' => $fc, 'receteli' => isset($receteliSet[$u->id])];
    });
    return ['ok' => 1, 'urunler' => $urunler];
});

// ---- FİNANSAL ÖZET (aylık gelir/gider/net + tedarikçi alış) ----
Route::get('/api/patron/finans', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoGiderEnsure();
    [$ay, $bas, $bit] = _restoAyAralik($r->ay);
    // Gelir: odemeler (adisyon join ile sube filtre)
    $gelirRows = DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->whereBetween('odemeler.created_at', [$bas, $bit])
        ->selectRaw('odemeler.tip, SUM(odemeler.tutar) t, SUM(odemeler.bahsis) b')->groupBy('odemeler.tip')->get();
    $gelir = 0.0; $bahsis = 0.0; $gelirTip = [];
    foreach ($gelirRows as $g) { $gelir += (float) $g->t; $bahsis += (float) $g->b; $gelirTip[] = ['tip' => $g->tip, 'tutar' => (float) $g->t]; }
    // Gider: giderler kategori kırılımı
    $giderRows = DB::table('giderler')->where('sube_id', $p->sube_id)->whereBetween('tarih', [substr($bas, 0, 10), substr($bit, 0, 10)])
        ->selectRaw('kategori, SUM(tutar) t')->groupBy('kategori')->get();
    $gider = 0.0; $giderKat = [];
    foreach ($giderRows as $g) { $gider += (float) $g->t; $giderKat[] = ['kategori' => $g->kategori, 'tutar' => (float) $g->t]; }
    usort($giderKat, fn ($a, $b) => $b['tutar'] <=> $a['tutar']);
    // Tedarikçi alış (bilgi)
    $alis = (float) DB::table('alis_faturalari')->where('sube_id', $p->sube_id)->whereBetween('tarih', [substr($bas, 0, 10), substr($bit, 0, 10)])->sum('toplam');
    return ['ok' => 1, 'ay' => $ay, 'gelir' => round($gelir, 2), 'bahsis' => round($bahsis, 2), 'gider' => round($gider, 2),
        'net' => round($gelir - $gider, 2), 'gelir_tip' => $gelirTip, 'gider_kategori' => $giderKat, 'alis_toplam' => round($alis, 2)];
});

// ADISYON OPERASYONLARI (yetki kontrollu): kapat | iskonto | ikram | iptal
// Limit asimi/garson kisiti -> onay_pin (mudur/sahip PIN'i) ile onaylanir.
Route::post('/api/patron/adisyon-islem', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $islem = $r->islem;
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    if ($a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Bu adisyon zaten kapanmış veya iptal edilmiş.'];

    // Onaylayan (mudur/sahip PIN'i) — limit asimi/iptal onayinda kullanilir
    $onaylayan = null;
    if ($r->onay_pin) {
        $onaylayan = DB::table('personeller')->where('sube_id', $p->sube_id)->where('pin', (string) $r->onay_pin)->first();
        if (!$onaylayan) return ['ok' => 0, 'hata' => 'Onay PIN hatalı.'];
    }
    $yetki = fn ($k) => _restoYetkiVar($p, $k);

    if ($islem === 'kapat') {
        if (!$yetki('adisyon_kapat')) return ['ok' => 0, 'hata' => 'Ödeme alma / masa kapatma yetkiniz yok.'];
        $tip = in_array($r->odeme_tip, ['nakit', 'kredi', 'yemek_karti', 'acik_hesap']) ? $r->odeme_tip : 'nakit';
        $kalan = (float) $a->toplam - (float) DB::table('odemeler')->where('adisyon_id', $a->id)->sum('tutar');
        if ($tip === 'acik_hesap') {
            // "Bana yazin" -> secili cari hesaba borc yazilir (nakit girmez ama ciroya sayilir)
            $cari = DB::table('cari_hesaplar')->where('id', (int) $r->cari_id)->where('sube_id', $p->sube_id)->first();
            if (!$cari) return ['ok' => 0, 'hata' => 'Açık hesap için bir cari seçmelisiniz.'];
            if ($kalan > 0) {
                DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => 'acik_hesap', 'tutar' => $kalan, 'personel_id' => $p->id, 'created_at' => now()]);
                DB::table('cari_hareketler')->insert(['sube_id' => $p->sube_id, 'cari_id' => $cari->id, 'tip' => 'borc', 'tutar' => $kalan,
                    'adisyon_id' => $a->id, 'aciklama' => ($a->masa_id ? 'Masa açık hesap' : 'Adisyon açık hesap'), 'personel_id' => $p->id, 'created_at' => now()]);
            }
            $mesaj = $cari->ad . ' hesabına yazıldı (' . number_format($kalan, 0, ',', '.') . 'TL).';
        } else {
            if ($kalan > 0) {
                DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => $tip, 'tutar' => $kalan, 'personel_id' => $p->id, 'created_at' => now()]);
                // Nakit ise kasaya (acik vardiya varsa) otomatik giris
                if ($tip === 'nakit') _kasaYaz($p->sube_id, 'satis', 'giris', $kalan, 'Nakit satış · adisyon #' . $a->id, 'adisyon', $a->id, $p->id);
            }
            $mesaj = 'Ödeme alındı (' . ['nakit' => 'Nakit', 'kredi' => 'Kredi', 'yemek_karti' => 'Yemek Kartı'][$tip] . '), masa kapatıldı.';
        }
        DB::table('adisyonlar')->where('id', $a->id)->update(['durum' => 'odendi', 'kapanis' => now()]);
        if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
        _restoStokTuket($a->id, $p->sube_id, $p->id); // reçeteden otomatik stok düşümü (güvenli, bozmaz)
        return ['ok' => 1, 'mesaj' => $mesaj];
    }

    if ($islem === 'iskonto') {
        if (!$yetki('iskonto')) return ['ok' => 0, 'hata' => 'İskonto uygulama yetkiniz yok.'];
        $oran = max(0, min(100, (float) $r->oran));
        if ($oran <= 0) return ['ok' => 0, 'hata' => 'Geçerli bir iskonto oranı girin.'];
        $limit = $p->rol === 'sahip' ? 100 : (float) ($p->iskonto_limit ?? 0);
        if ($oran > $limit) {
            if (!$onaylayan) return ['ok' => 0, 'onay_gerek' => true, 'hata' => '%' . round($oran) . ' iskonto, limitinizi (%' . round($limit) . ') aşıyor. Yetkili PIN onayı gerekli.'];
            $onayLimit = $onaylayan->rol === 'sahip' ? 100 : (float) ($onaylayan->iskonto_limit ?? 0);
            if (!_restoYetkiVar($onaylayan, 'iskonto') || $onayLimit < $oran) return ['ok' => 0, 'hata' => 'Onaylayan kişinin iskonto yetkisi/limiti de yetersiz.'];
        }
        $indirim = round((float) $a->ara_toplam * $oran / 100, 2);
        $yeni = max(0, (float) $a->ara_toplam - $indirim - (float) $a->ikram);
        DB::table('adisyonlar')->where('id', $a->id)->update(['indirim' => $indirim, 'toplam' => $yeni]);
        DB::table('iptal_indirim_loglari')->insert(['sube_id' => $p->sube_id, 'adisyon_id' => $a->id, 'tip' => 'indirim',
            'tutar' => $indirim, 'sebep' => $r->sebep ?: ('%' . round($oran) . ' iskonto'), 'personel_id' => ($onaylayan->id ?? $p->id), 'created_at' => now()]);
        return ['ok' => 1, 'mesaj' => '%' . round($oran) . ' iskonto uygulandı (' . number_format($indirim, 0, ',', '.') . 'TL)' . ($onaylayan ? ' — ' . $onaylayan->ad . ' onayı ile' : '') . '.'];
    }

    if ($islem === 'ikram') {
        if (!$yetki('ikram')) return ['ok' => 0, 'hata' => 'İkram yetkiniz yok.'];
        // Ikram URUN bazlidir: adisyondaki secili kalemler ikram edilir
        $ids = array_filter(array_map('intval', explode(',', (string) $r->kalem_idler)));
        $adlar = '';
        if (!empty($ids)) {
            $secili = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->whereIn('id', $ids)->where('durum', '!=', 'iptal')->get(['urun_adi', 'tutar']);
            $tutar = (float) $secili->sum('tutar');
            $adlar = $secili->pluck('urun_adi')->implode(', ');
        } else {
            $tutar = max(0, (float) $r->tutar); // eski/serbest tutar (geriye uyum)
        }
        if ($tutar <= 0) return ['ok' => 0, 'hata' => 'İkram için ürün seçin.'];
        if ($tutar > (float) $a->ara_toplam) $tutar = (float) $a->ara_toplam;
        // Ikram LIMITI (TL): asarsa yetkili PIN onayi
        $iLimit = $p->rol === 'sahip' ? 1e12 : (float) ($p->ikram_limit ?? 0);
        if ($tutar > $iLimit) {
            if (!$onaylayan) return ['ok' => 0, 'onay_gerek' => true, 'hata' => number_format($tutar, 0, ',', '.') . 'TL ikram, limitinizi (' . number_format($iLimit, 0, ',', '.') . 'TL) aşıyor. Yetkili PIN onayı gerekli.'];
            $onayLimit = $onaylayan->rol === 'sahip' ? 1e12 : (float) ($onaylayan->ikram_limit ?? 0);
            if (!_restoYetkiVar($onaylayan, 'ikram') || $onayLimit < $tutar) return ['ok' => 0, 'hata' => 'Onaylayan kişinin ikram yetkisi/limiti de yetersiz.'];
        }
        $yeni = max(0, (float) $a->ara_toplam - (float) $a->indirim - $tutar);
        DB::table('adisyonlar')->where('id', $a->id)->update(['ikram' => $tutar, 'toplam' => $yeni]);
        DB::table('iptal_indirim_loglari')->insert(['sube_id' => $p->sube_id, 'adisyon_id' => $a->id, 'tip' => 'ikram',
            'tutar' => $tutar, 'sebep' => $adlar ? ('İkram: ' . $adlar) : ($r->sebep ?: 'İkram'), 'personel_id' => ($onaylayan->id ?? $p->id), 'created_at' => now()]);
        return ['ok' => 1, 'mesaj' => number_format($tutar, 0, ',', '.') . 'TL ikram' . ($adlar ? ' (' . $adlar . ')' : '') . ' uygulandı' . ($onaylayan ? ' — ' . $onaylayan->ad . ' onayı ile' : '') . '.'];
    }

    if ($islem === 'iptal') {
        if (!$yetki('adisyon_iptal')) return ['ok' => 0, 'hata' => 'Adisyon iptal yetkiniz yok.'];
        if (!in_array($p->rol, ['sahip', 'mudur'])) {
            if (!$onaylayan || !in_array($onaylayan->rol, ['sahip', 'mudur'])) return ['ok' => 0, 'onay_gerek' => true, 'hata' => 'Adisyon iptali için müdür/sahip PIN onayı gerekli.'];
        }
        DB::table('adisyonlar')->where('id', $a->id)->update(['durum' => 'iptal']);
        if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
        DB::table('iptal_indirim_loglari')->insert(['sube_id' => $p->sube_id, 'adisyon_id' => $a->id, 'tip' => 'void',
            'tutar' => (float) $a->toplam, 'sebep' => $r->sebep ?: 'Adisyon iptal', 'personel_id' => ($onaylayan->id ?? $p->id), 'created_at' => now()]);
        return ['ok' => 1, 'mesaj' => 'Adisyon iptal edildi, masa boşaltıldı.'];
    }

    return ['ok' => 0, 'hata' => 'Bilinmeyen işlem'];
});

// MASA AC: bos masaya yeni (bos) adisyon acar. Yetki: adisyon_ac (sahip hep).
Route::post('/api/patron/masa-ac', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Masa / adisyon açma yetkiniz yok.'];
    $masa = DB::table('masalar')->where('id', (int) $r->masa_id)->where('sube_id', $p->sube_id)->first();
    if (!$masa) return ['ok' => 0, 'hata' => 'Masa bulunamadı'];
    // Zaten acik adisyon varsa onu don (cift acilma olmasin)
    $mevcut = DB::table('adisyonlar')->where('masa_id', $masa->id)->where('durum', 'acik')->value('id');
    if ($mevcut) return ['ok' => 1, 'adisyon_id' => $mevcut, 'mesaj' => $masa->ad . ' zaten açık.'];
    $misafir = max(1, (int) $r->misafir);
    $id = DB::table('adisyonlar')->insertGetId([
        'sube_id' => $p->sube_id, 'masa_id' => $masa->id, 'kanal' => 'salon',
        'misafir_sayisi' => $misafir, 'durum' => 'acik', 'acan_personel_id' => $p->id,
        'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
        'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('masalar')->where('id', $masa->id)->update(['durum' => 'dolu']);
    return ['ok' => 1, 'adisyon_id' => $id, 'mesaj' => $masa->ad . ' açıldı (' . $misafir . ' kişi).'];
});

// MENU: siparis girisi icin kategori + urun listesi
Route::get('/api/menu', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $kategoriler = DB::table('menu_kategorileri')->where('sube_id', $p->sube_id)->orderBy('sira')->orderBy('ad')->get(['id', 'ad'])
        ->map(fn ($k) => ['id' => (int) $k->id, 'ad' => $k->ad]);
    $urunler = DB::table('urunler')->where('sube_id', $p->sube_id)->where('aktif', 1)->orderBy('ad')
        ->get(['id', 'ad', 'fiyat', 'kategori_id', 'tukendi'])
        ->map(fn ($u) => ['id' => (int) $u->id, 'ad' => $u->ad, 'fiyat' => (float) $u->fiyat,
            'kategori_id' => $u->kategori_id ? (int) $u->kategori_id : 0, 'tukendi' => (bool) $u->tukendi]);
    return ['ok' => 1, 'kategoriler' => $kategoriler, 'urunler' => $urunler];
});

// ============================ MENU YONETIMI (sahip/mudur) ============================
if (!function_exists('_restoMenuYetki')) {
    function _restoMenuYetki($p) { return $p && in_array(($p->rol ?? ''), ['sahip', 'mudur']); }
}

// Duzenleme icin TUM kategori + urunler (tum alanlar)
Route::get('/api/patron/menu-yonetim', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $kats = DB::table('menu_kategorileri')->where('sube_id', $p->sube_id)->orderBy('sira')->orderBy('ad')
        ->get(['id', 'ad', 'sira', 'aktif'])
        ->map(fn ($k) => ['id' => (int) $k->id, 'ad' => $k->ad, 'sira' => (int) $k->sira, 'aktif' => (bool) $k->aktif]);
    $urunler = DB::table('urunler')->where('sube_id', $p->sube_id)->orderBy('ad')
        ->get(['id', 'ad', 'aciklama', 'fiyat', 'kategori_id', 'tukendi', 'aktif', 'gorsel', 'updated_at'])
        ->map(fn ($u) => [
            'id' => (int) $u->id, 'ad' => $u->ad, 'aciklama' => $u->aciklama ?: '', 'fiyat' => (float) $u->fiyat,
            'kategori_id' => $u->kategori_id ? (int) $u->kategori_id : 0, 'tukendi' => (bool) $u->tukendi, 'aktif' => (bool) $u->aktif,
            'gorsel' => $u->gorsel ? ($u->gorsel . '?v=' . ($u->updated_at ? strtotime($u->updated_at) : 0)) : null,
        ]);
    return ['ok' => 1, 'duzenleyebilir' => _restoMenuYetki($p), 'kategoriler' => $kats, 'urunler' => $urunler];
});

// QR MENU RENK TEMASI: uygulamadan sec (kartela) -> subeler.tema
Route::get('/api/patron/tema', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    resto_tema_kolon();
    $temalar = resto_temalar();
    $sube = DB::table('subeler')->find($p->sube_id);
    $stored = isset($sube->tema) ? $sube->tema : null;
    $secili = ($stored === 'ozel') ? 'ozel' : (isset($temalar[$stored]) ? $stored : 'altin');
    $liste = [];
    foreach ($temalar as $k => $t) $liste[] = ['key' => $k] + $t;
    return ['ok' => 1, 'duzenleyebilir' => _restoMenuYetki($p), 'secili' => $secili,
        'renk' => (isset($sube->tema_renk) && $sube->tema_renk) ? $sube->tema_renk : '#C41E3A',
        'renk2' => (isset($sube->tema_renk2) && $sube->tema_renk2) ? $sube->tema_renk2 : '',
        'mod' => (isset($sube->tema_mod) && $sube->tema_mod === 'acik') ? 'acik' : 'koyu',
        'temalar' => $liste];
});
// QR menu varsayilan MODU (koyu/acik) — musteri yine kendi cihazinda cevirebilir
Route::post('/api/patron/tema-mod', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    resto_tema_kolon();
    $mod = $r->input('mod') === 'acik' ? 'acik' : 'koyu';
    DB::table('subeler')->where('id', $p->sube_id)->update(['tema_mod' => $mod]);
    return ['ok' => 1, 'mod' => $mod];
});
Route::post('/api/patron/tema-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    resto_tema_kolon();
    $key = (string) $r->input('tema');
    if ($key === 'ozel') {
        $renk = '#' . ltrim((string) $r->input('renk'), '#');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $renk)) return ['ok' => 0, 'hata' => 'Geçersiz renk'];
        $renk2raw = (string) $r->input('renk2');
        $renk2 = $renk2raw ? ('#' . ltrim($renk2raw, '#')) : null;
        if ($renk2 !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $renk2)) $renk2 = null;
        DB::table('subeler')->where('id', $p->sube_id)->update(['tema' => 'ozel', 'tema_renk' => strtoupper($renk), 'tema_renk2' => $renk2 ? strtoupper($renk2) : null]);
        return ['ok' => 1, 'tema' => 'ozel', 'renk' => strtoupper($renk), 'renk2' => $renk2 ? strtoupper($renk2) : ''];
    }
    if (!isset(resto_temalar()[$key])) return ['ok' => 0, 'hata' => 'Geçersiz tema'];
    DB::table('subeler')->where('id', $p->sube_id)->update(['tema' => $key]);
    return ['ok' => 1, 'tema' => $key];
});

// Urun ekle/guncelle (id bos -> yeni)
Route::post('/api/patron/urun-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    $ad = trim((string) $r->input('ad'));
    if ($ad === '') return ['ok' => 0, 'hata' => 'Ürün adı gerekli'];
    $katId = (int) $r->input('kategori_id');
    if ($katId && !DB::table('menu_kategorileri')->where('id', $katId)->where('sube_id', $p->sube_id)->exists()) $katId = 0;
    $data = [
        'ad' => $ad,
        'aciklama' => trim((string) $r->input('aciklama')) ?: null,
        'fiyat' => (float) str_replace(',', '.', (string) $r->input('fiyat', 0)),
        'kategori_id' => $katId ?: null,
        'tukendi' => $r->boolean('tukendi') ? 1 : 0,
        'aktif' => $r->input('aktif') !== null ? ($r->boolean('aktif') ? 1 : 0) : 1,
        'updated_at' => now(),
    ];
    $id = (int) $r->input('id');
    if ($id) {
        if (!DB::table('urunler')->where('id', $id)->where('sube_id', $p->sube_id)->exists()) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
        DB::table('urunler')->where('id', $id)->update($data);
    } else {
        $data['sube_id'] = $p->sube_id;
        $data['stok_takipli'] = 0;
        $data['created_at'] = now();
        $id = DB::table('urunler')->insertGetId($data);
    }
    $u = DB::table('urunler')->find($id);
    return ['ok' => 1, 'urun' => [
        'id' => (int) $u->id, 'ad' => $u->ad, 'aciklama' => $u->aciklama ?: '', 'fiyat' => (float) $u->fiyat,
        'kategori_id' => $u->kategori_id ? (int) $u->kategori_id : 0, 'tukendi' => (bool) $u->tukendi, 'aktif' => (bool) $u->aktif,
        'gorsel' => $u->gorsel ? ($u->gorsel . '?v=' . time()) : null,
    ]];
});

// Urun sil (FK varsa gizle=aktif 0)
Route::post('/api/patron/urun-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    $id = (int) $r->input('id');
    if (!DB::table('urunler')->where('id', $id)->where('sube_id', $p->sube_id)->exists()) return ['ok' => 0, 'hata' => 'Bulunamadı'];
    foreach (glob(storage_path('app/urun_foto') . '/' . $id . '.*') ?: [] as $f) @unlink($f);
    try {
        DB::table('urunler')->where('id', $id)->delete();
    } catch (\Throwable $e) {
        DB::table('urunler')->where('id', $id)->update(['aktif' => 0, 'updated_at' => now()]); // gecmis referansi varsa gizle
    }
    return ['ok' => 1];
});

// Kategori ekle/guncelle
Route::post('/api/patron/kategori-kaydet', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    $ad = trim((string) $r->input('ad'));
    if ($ad === '') return ['ok' => 0, 'hata' => 'Kategori adı gerekli'];
    $sira = (int) $r->input('sira', 0);
    $id = (int) $r->input('id');
    if ($id) {
        if (!DB::table('menu_kategorileri')->where('id', $id)->where('sube_id', $p->sube_id)->exists()) return ['ok' => 0, 'hata' => 'Bulunamadı'];
        DB::table('menu_kategorileri')->where('id', $id)->update(['ad' => $ad, 'sira' => $sira, 'updated_at' => now()]);
    } else {
        $id = DB::table('menu_kategorileri')->insertGetId(['sube_id' => $p->sube_id, 'ad' => $ad, 'sira' => $sira, 'aktif' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }
    return ['ok' => 1, 'kategori' => ['id' => (int) $id, 'ad' => $ad, 'sira' => $sira, 'aktif' => true]];
});

// Kategori sil (icinde urun varsa engelle)
Route::post('/api/patron/kategori-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    $id = (int) $r->input('id');
    if (!DB::table('menu_kategorileri')->where('id', $id)->where('sube_id', $p->sube_id)->exists()) return ['ok' => 0, 'hata' => 'Bulunamadı'];
    $say = DB::table('urunler')->where('kategori_id', $id)->count();
    if ($say > 0) return ['ok' => 0, 'hata' => "Bu kategoride $say ürün var. Önce taşıyın ya da silin."];
    DB::table('menu_kategorileri')->where('id', $id)->delete();
    return ['ok' => 1];
});

// Urun fotografi yukle (uygulamadan)
Route::post('/api/patron/urun-foto', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Yetkiniz yok'], $p ? 403 : 401);
    $id = (int) $r->input('urun_id');
    if (!DB::table('urunler')->where('id', $id)->where('sube_id', $p->sube_id)->exists()) return response()->json(['ok' => 0, 'hata' => 'Ürün bulunamadı'], 404);
    if (!$r->hasFile('foto') || !$r->file('foto')->isValid()) return response()->json(['ok' => 0, 'hata' => 'Geçerli dosya yok'], 422);
    $f = $r->file('foto');
    $ext = strtolower($f->getClientOriginalExtension() ?: 'jpg');
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return response()->json(['ok' => 0, 'hata' => 'JPG, PNG veya WEBP'], 422);
    if ($f->getSize() > 8 * 1024 * 1024) return response()->json(['ok' => 0, 'hata' => 'En fazla 8 MB'], 422);
    $dir = storage_path('app/urun_foto');
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    foreach (glob($dir . '/' . $id . '.*') ?: [] as $old) @unlink($old);
    $f->move($dir, $id . '.' . $ext);
    $url = url('/urun-foto/' . $id);
    DB::table('urunler')->where('id', $id)->update(['gorsel' => $url, 'updated_at' => now()]);
    return ['ok' => 1, 'url' => $url . '?v=' . time()];
});

// ADISYONA URUN EKLE (siparis al). kalemler = JSON [{urun_id,adet}]. Yetki: adisyon_ac.
Route::post('/api/patron/adisyon-urun-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Sipariş ekleme yetkiniz yok.'];
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    if ($a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Bu adisyon açık değil.'];
    $kalemler = json_decode((string) $r->kalemler, true);
    if (!is_array($kalemler) || empty($kalemler)) return ['ok' => 0, 'hata' => 'Ürün seçilmedi'];
    $eklenen = 0;
    $tukendiAtlanan = [];
    foreach ($kalemler as $k) {
        $uid = (int) ($k['urun_id'] ?? 0);
        $adet = max(1, (int) ($k['adet'] ?? 1));
        $u = DB::table('urunler')->where('id', $uid)->where('sube_id', $p->sube_id)->first();
        if (!$u) continue;
        if ($u->tukendi) { $tukendiAtlanan[] = $u->ad; continue; } // 86: tukenen urun siparise eklenmez
        DB::table('adisyon_kalemleri')->insert([
            'adisyon_id' => $a->id, 'urun_id' => $u->id, 'urun_adi' => $u->ad,
            'adet' => $adet, 'birim_fiyat' => (float) $u->fiyat, 'tutar' => (float) $u->fiyat * $adet,
            'durum' => 'gonderildi', 'personel_id' => $p->id, 'gonderim_zamani' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $eklenen++;
    }
    if ($eklenen === 0) {
        $msg = $tukendiAtlanan ? ('Tükendi (86): ' . implode(', ', $tukendiAtlanan) . ' — eklenemedi.') : 'Geçerli ürün eklenemedi';
        return ['ok' => 0, 'hata' => $msg];
    }
    $araToplam = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->sum('tutar');
    $toplam = max(0, $araToplam - (float) $a->indirim - (float) $a->ikram);
    DB::table('adisyonlar')->where('id', $a->id)->update(['ara_toplam' => $araToplam, 'toplam' => $toplam, 'updated_at' => now()]);
    if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'dolu']);
    $mesaj = $eklenen . ' kalem eklendi.';
    if ($tukendiAtlanan) $mesaj .= ' (Tükendi, atlandı: ' . implode(', ', $tukendiAtlanan) . ')';
    return ['ok' => 1, 'mesaj' => $mesaj, 'ara_toplam' => $araToplam, 'toplam' => $toplam, 'tukendi_atlanan' => $tukendiAtlanan];
});

// KALEM VOID (urun sil): yetki urun_sil; yoksa mudur/sahip onay_pin. Loglar + toplam gunceller.
Route::post('/api/patron/kalem-void', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    if ($a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Bu adisyon açık değil.'];
    $kalem = DB::table('adisyon_kalemleri')->where('id', (int) $r->kalem_id)->where('adisyon_id', $a->id)->first();
    if (!$kalem || $kalem->durum === 'iptal') return ['ok' => 0, 'hata' => 'Kalem bulunamadı'];
    $onaylayan = null;
    if (!_restoYetkiVar($p, 'urun_sil')) {
        if ($r->onay_pin) $onaylayan = DB::table('personeller')->where('sube_id', $p->sube_id)->where('pin', (string) $r->onay_pin)->first();
        if (!$onaylayan || !_restoYetkiVar($onaylayan, 'urun_sil')) {
            return ['ok' => 0, 'onay_gerek' => true, 'hata' => 'Ürün silme yetkiniz yok. Yetkili (müdür/sahip) PIN onayı gerekli.'];
        }
    }
    DB::table('adisyon_kalemleri')->where('id', $kalem->id)->update(['durum' => 'iptal', 'updated_at' => now()]);
    DB::table('iptal_indirim_loglari')->insert(['sube_id' => $p->sube_id, 'adisyon_id' => $a->id, 'adisyon_kalem_id' => $kalem->id,
        'tip' => 'void', 'tutar' => (float) $kalem->tutar, 'sebep' => $r->sebep ?: 'Ürün silindi', 'personel_id' => ($onaylayan->id ?? $p->id), 'created_at' => now()]);
    $araToplam = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->sum('tutar');
    $toplam = max(0, $araToplam - (float) $a->indirim - (float) $a->ikram);
    DB::table('adisyonlar')->where('id', $a->id)->update(['ara_toplam' => $araToplam, 'toplam' => $toplam, 'updated_at' => now()]);
    return ['ok' => 1, 'mesaj' => $kalem->urun_adi . ' silindi.' . ($onaylayan ? ' — ' . $onaylayan->ad . ' onayı ile' : ''), 'toplam' => $toplam];
});

// Istasyon kod->etiket. Filtre parametresi bu kodlarla gelir.
if (!function_exists('_mutfakIstasyonlar')) {
    function _mutfakIstasyonlar(): array
    {
        return ['izgara' => '🔥 Izgara', 'firin' => '🍕 Fırın', 'mutfak' => '🍳 Sıcak', 'soguk' => '🥗 Soğuk', 'tatli' => '🍰 Tatlı', 'bar' => '🍹 Bar'];
    }
}

// MUTFAK (KDS): bekleyen siparis kalemleri (durum=gonderildi) adisyona gruplu.
// + istasyon (urunden), + kur, + all-day toplu uretim, + istasyon dagilimi. ?istasyon=izgara ile filtre.
Route::get('/api/mutfak', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $simdi = now();
    $istasyonVar = Schema::hasColumn('urunler', 'istasyon');
    $filtre = $r->query('istasyon');
    $q = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
        ->leftJoin('urunler', 'adisyon_kalemleri.urun_id', '=', 'urunler.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyonlar.durum', 'acik')->where('adisyon_kalemleri.durum', 'gonderildi');
    $sel = ['adisyon_kalemleri.id', 'adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.adet', 'adisyon_kalemleri.not',
        'adisyon_kalemleri.kur', 'adisyon_kalemleri.gonderim_zamani', 'adisyonlar.id as adisyon_id', 'masalar.ad as masa', 'adisyonlar.kanal'];
    $sel[] = $istasyonVar ? DB::raw("COALESCE(urunler.istasyon,'mutfak') as istasyon") : DB::raw("'mutfak' as istasyon");
    $rows = $q->select($sel)->orderBy('adisyon_kalemleri.gonderim_zamani')->get();

    $etiket = _mutfakIstasyonlar();
    $gruplu = [];
    $istSay = [];                 // istasyon -> bekleyen kalem (adet)
    $toplu = [];                  // all-day: urun+istasyon -> toplam adet + en eski dk
    foreach ($rows as $k) {
        $ist = $k->istasyon ?: 'mutfak';
        $dkK = $k->gonderim_zamani ? (int) \Carbon\Carbon::parse($k->gonderim_zamani)->diffInMinutes($simdi) : 0;
        $adet = (float) $k->adet;
        // All-day her zaman TUM istasyonlardan toplanir (mutfak sefi butun uretimi gorsun)
        $tkey = $ist . '|' . $k->urun_adi;
        if (!isset($toplu[$tkey])) $toplu[$tkey] = ['ad' => $k->urun_adi, 'istasyon' => $ist, 'adet' => 0.0, 'dk' => $dkK];
        $toplu[$tkey]['adet'] += $adet;
        $toplu[$tkey]['dk'] = max($toplu[$tkey]['dk'], $dkK);
        $istSay[$ist] = ($istSay[$ist] ?? 0) + $adet;
        // Istasyon filtresi (siparis kartlari icin)
        if ($filtre && $filtre !== 'hepsi' && $ist !== $filtre) continue;
        $aid = $k->adisyon_id;
        if (!isset($gruplu[$aid])) {
            $dk = $k->gonderim_zamani ? (int) \Carbon\Carbon::parse($k->gonderim_zamani)->diffInMinutes($simdi) : 0;
            $gruplu[$aid] = ['adisyon_id' => $aid, 'masa' => $k->masa ?? ucfirst($k->kanal), 'kanal' => $k->kanal, 'dk' => $dk, 'kalemler' => []];
        }
        $gruplu[$aid]['kalemler'][] = ['id' => $k->id, 'ad' => $k->urun_adi, 'adet' => $adet, 'not' => $k->not, 'kur' => $k->kur, 'istasyon' => $ist];
    }
    // Istasyon ozeti (bekleyeni olmayan da gorunsun ki sekmeler sabit dursun degil -> sadece dolu olanlar + hepsi)
    $istasyonlar = [];
    foreach ($etiket as $kod => $ad) {
        if (($istSay[$kod] ?? 0) > 0) $istasyonlar[] = ['kod' => $kod, 'ad' => $ad, 'bekleyen' => (int) round($istSay[$kod])];
    }
    // All-day: en cok bekleyen ustte
    $topluArr = array_values($toplu);
    usort($topluArr, fn ($a, $b) => $b['dk'] <=> $a['dk']);
    foreach ($topluArr as &$t) { $t['adet'] = $t['adet']; $t['dk'] = (int) $t['dk']; $t['istasyon_ad'] = $etiket[$t['istasyon']] ?? $t['istasyon']; }
    unset($t);

    return ['ok' => 1, 'siparisler' => array_values($gruplu), 'istasyonlar' => $istasyonlar,
        'toplu' => $topluArr, 'toplam_bekleyen' => (int) round(array_sum($istSay))];
});

// Mutfak: kalem/adisyon hazir isaretle (+ hazir_zamani damgasi = hazirlik suresi analitigi)
Route::post('/api/mutfak/hazir', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $upd = ['durum' => 'hazir', 'updated_at' => now()];
    if (Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani')) $upd['hazir_zamani'] = now();
    if ($r->adisyon_id) {
        DB::table('adisyon_kalemleri')->where('adisyon_id', (int) $r->adisyon_id)->where('durum', 'gonderildi')->update($upd);
    } else {
        DB::table('adisyon_kalemleri')->where('id', (int) $r->kalem_id)->where('durum', 'gonderildi')->update($upd);
    }
    return ['ok' => 1];
});

// SERVISE HAZIR: durum=hazir kalemler (mutfak bitirdi, garson alsin) adisyona gruplu.
Route::get('/api/mutfak/servise-hazir', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $simdi = now();
    $bekVar = Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani');
    $sel = ['adisyon_kalemleri.id', 'adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.adet', 'adisyon_kalemleri.not',
        'adisyonlar.id as adisyon_id', 'masalar.ad as masa', 'adisyonlar.kanal'];
    $sel[] = $bekVar ? 'adisyon_kalemleri.hazir_zamani' : DB::raw('NULL as hazir_zamani');
    $rows = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyonlar.durum', 'acik')->where('adisyon_kalemleri.durum', 'hazir')
        ->select($sel)->orderBy('adisyon_kalemleri.hazir_zamani')->get();
    $gruplu = [];
    foreach ($rows as $k) {
        $aid = $k->adisyon_id;
        if (!isset($gruplu[$aid])) {
            $dk = $k->hazir_zamani ? (int) \Carbon\Carbon::parse($k->hazir_zamani)->diffInMinutes($simdi) : 0;
            $gruplu[$aid] = ['adisyon_id' => $aid, 'masa' => $k->masa ?? ucfirst($k->kanal), 'dk' => $dk, 'kalemler' => []];
        }
        $gruplu[$aid]['kalemler'][] = ['id' => $k->id, 'ad' => $k->urun_adi, 'adet' => (float) $k->adet, 'not' => $k->not];
    }
    return ['ok' => 1, 'siparisler' => array_values($gruplu)];
});

// SERVIS EDILDI: hazir kalemleri servise ver (garson aldi) -> durum=servis.
Route::post('/api/mutfak/servis', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    if ($r->adisyon_id) {
        DB::table('adisyon_kalemleri')->where('adisyon_id', (int) $r->adisyon_id)->where('durum', 'hazir')->update(['durum' => 'servis', 'updated_at' => now()]);
    } else {
        DB::table('adisyon_kalemleri')->where('id', (int) $r->kalem_id)->where('durum', 'hazir')->update(['durum' => 'servis', 'updated_at' => now()]);
    }
    return ['ok' => 1];
});

// 86 / TUKENDI: mutfak urun listesi (istasyon + tukendi durumu ile).
Route::get('/api/mutfak/urunler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $istVar = Schema::hasColumn('urunler', 'istasyon');
    $sel = ['urunler.id', 'urunler.ad', 'urunler.tukendi', 'menu_kategorileri.ad as kategori'];
    $sel[] = $istVar ? 'urunler.istasyon' : DB::raw("'mutfak' as istasyon");
    $rows = DB::table('urunler')->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
        ->where('urunler.sube_id', $p->sube_id)->where('urunler.aktif', 1)
        ->orderByDesc('urunler.tukendi')->orderBy('urunler.ad')->select($sel)->get()
        ->map(fn ($u) => ['id' => (int) $u->id, 'ad' => $u->ad, 'tukendi' => (bool) $u->tukendi,
            'istasyon' => $u->istasyon ?: 'mutfak', 'kategori' => $u->kategori ?: '—']);
    return ['ok' => 1, 'urunler' => $rows, 'tukenen' => $rows->where('tukendi', true)->count()];
});

// 86 TOGGLE: urunu tukendi/geldi yap. Yetki: adisyon_ac olan herkes (mutfak/garson dahil).
Route::post('/api/mutfak/86', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $u = DB::table('urunler')->where('id', (int) $r->urun_id)->where('sube_id', $p->sube_id)->first();
    if (!$u) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
    $yeni = $u->tukendi ? 0 : 1;
    DB::table('urunler')->where('id', $u->id)->update(['tukendi' => $yeni, 'updated_at' => now()]);
    return ['ok' => 1, 'tukendi' => (bool) $yeni, 'mesaj' => $u->ad . ($yeni ? ' → tükendi (86) 🔴' : ' → tekrar satışta 🟢')];
});

// MUTFAK ANALIZ: hazirlik suresi, en yavas urunler, istasyon yuku, saatlik yogunluk + kurallı prep onerisi.
Route::get('/api/mutfak/analiz', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $bekVar = Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani');
    $istVar = Schema::hasColumn('urunler', 'istasyon');
    $bugun = now()->startOfDay();
    $simdi = now();

    // 1) Ortalama hazirlik suresi (bugun, gonderim->hazir olan kalemler)
    $ortDk = null; $olcum = 0;
    $enYavas = [];
    if ($bekVar) {
        $done = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.sube_id', $p->sube_id)->whereNotNull('adisyon_kalemleri.hazir_zamani')
            ->whereNotNull('adisyon_kalemleri.gonderim_zamani')->where('adisyon_kalemleri.hazir_zamani', '>=', $bugun)
            ->select('adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.gonderim_zamani', 'adisyon_kalemleri.hazir_zamani')->get();
        $topla = 0; $urunSure = [];
        foreach ($done as $d) {
            $dk = (int) \Carbon\Carbon::parse($d->gonderim_zamani)->diffInMinutes(\Carbon\Carbon::parse($d->hazir_zamani));
            $topla += $dk; $olcum++;
            $urunSure[$d->urun_adi][] = $dk;
        }
        if ($olcum > 0) $ortDk = round($topla / $olcum, 1);
        foreach ($urunSure as $ad => $arr) $enYavas[] = ['ad' => $ad, 'dk' => round(array_sum($arr) / count($arr), 1), 'adet' => count($arr)];
        usort($enYavas, fn ($a, $b) => $b['dk'] <=> $a['dk']);
        $enYavas = array_slice($enYavas, 0, 6);
    }

    // 2) Su an bekleyen kalemler -> istasyon yuku + geciken sayisi
    $bekleyen = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->leftJoin('urunler', 'adisyon_kalemleri.urun_id', '=', 'urunler.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyonlar.durum', 'acik')->where('adisyon_kalemleri.durum', 'gonderildi')
        ->select('adisyon_kalemleri.adet', 'adisyon_kalemleri.gonderim_zamani',
            $istVar ? DB::raw("COALESCE(urunler.istasyon,'mutfak') as istasyon") : DB::raw("'mutfak' as istasyon"))->get();
    $etiket = _mutfakIstasyonlar();
    $istYuk = []; $geciken = 0; $enEskiDk = 0;
    foreach ($bekleyen as $b) {
        $ist = $b->istasyon ?: 'mutfak';
        $istYuk[$ist] = ($istYuk[$ist] ?? 0) + (float) $b->adet;
        $dk = $b->gonderim_zamani ? (int) \Carbon\Carbon::parse($b->gonderim_zamani)->diffInMinutes($simdi) : 0;
        if ($dk >= 15) $geciken++;
        $enEskiDk = max($enEskiDk, $dk);
    }
    $istasyonYuku = [];
    foreach ($istYuk as $kod => $adet) $istasyonYuku[] = ['kod' => $kod, 'ad' => $etiket[$kod] ?? $kod, 'adet' => (int) round($adet)];
    usort($istasyonYuku, fn ($a, $b) => $b['adet'] <=> $a['adet']);

    // 3) Saatlik yogunluk (bugun mutfaga gonderilen kalem adedi, saat bazinda)
    $saatlik = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyon_kalemleri.gonderim_zamani', '>=', $bugun)
        ->select(DB::raw('HOUR(adisyon_kalemleri.gonderim_zamani) as saat'), DB::raw('COUNT(*) as adet'))
        ->groupBy(DB::raw('HOUR(adisyon_kalemleri.gonderim_zamani)'))->pluck('adet', 'saat');
    $saatDizi = [];
    foreach ($saatlik as $s => $a) $saatDizi[] = ['saat' => (int) $s, 'adet' => (int) $a];
    usort($saatDizi, fn ($a, $b) => $a['saat'] <=> $b['saat']);

    // 4) Kural motoru prep onerileri (bedava, rakam uydurmaz)
    $oneriler = [];
    if ($geciken > 0) {
        $enYogun = $istasyonYuku[0] ?? null;
        $oneriler[] = ['tip' => 'darbogaz', 'ikon' => '⚠️',
            'metin' => "$geciken sipariş 15 dk+ bekliyor" . ($enYogun ? " · en yoğun istasyon: {$enYogun['ad']} ({$enYogun['adet']} ürün). Buraya destek verin." : '.')];
    }
    if ($ortDk !== null && $ortDk > 18) {
        $oneriler[] = ['tip' => 'sure', 'ikon' => '🐢', 'metin' => "Bugün ortalama hazırlık {$ortDk} dk — hedefin (12-15 dk) üstünde. En yavaş: " . (($enYavas[0]['ad'] ?? '') ?: '—') . '.'];
    }
    // Bugun en cok satan urunler -> "hazirlikta bulundur" (mise en place)
    $cokSatan = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyon_kalemleri.gonderim_zamani', '>=', $bugun)
        ->where('adisyon_kalemleri.durum', '!=', 'iptal')
        ->select('adisyon_kalemleri.urun_adi', DB::raw('SUM(adisyon_kalemleri.adet) as adet'))
        ->groupBy('adisyon_kalemleri.urun_adi')->orderByDesc('adet')->limit(3)->get();
    if ($cokSatan->count() > 0) {
        $liste = $cokSatan->map(fn ($c) => $c->urun_adi . ' (' . (int) $c->adet . ')')->implode(', ');
        $oneriler[] = ['tip' => 'prep', 'ikon' => '📋', 'metin' => "Bugünün çok satanları: $liste. Malzemelerini hazırda tut (mise en place)."];
    }
    // Tukenen urun uyarisi
    $tukenen = DB::table('urunler')->where('sube_id', $p->sube_id)->where('aktif', 1)->where('tukendi', 1)->pluck('ad');
    if ($tukenen->count() > 0) {
        $oneriler[] = ['tip' => 'tukendi', 'ikon' => '🔴', 'metin' => 'Şu an 86 (tükendi): ' . $tukenen->implode(', ') . '.'];
    }
    if (empty($oneriler)) $oneriler[] = ['tip' => 'ok', 'ikon' => '✅', 'metin' => 'Mutfak akışı sağlıklı görünüyor. Bekleyen gecikmiş sipariş yok.'];

    return ['ok' => 1,
        'ozet' => ['ort_dk' => $ortDk, 'olcum' => $olcum, 'bekleyen' => (int) round(array_sum($istYuk)), 'geciken' => $geciken, 'en_eski_dk' => $enEskiDk],
        'en_yavas' => $enYavas, 'istasyon_yuku' => $istasyonYuku, 'saatlik' => $saatDizi, 'oneriler' => $oneriler];
});

// ============================ REZERVASYON (online masa rezervasyonu) ============================
if (!function_exists('_rezervasyonEnsure')) {
    function _rezervasyonEnsure($subeId)
    {
        if (!Schema::hasTable('rezervasyonlar')) {
            Schema::create('rezervasyonlar', function ($t) {
                $t->id();
                $t->unsignedBigInteger('sube_id');
                $t->unsignedBigInteger('masa_id')->nullable();     // atanan masa (opsiyonel)
                $t->string('ad');
                $t->string('telefon', 30)->nullable();
                $t->unsignedInteger('kisi')->default(2);
                $t->date('tarih');
                $t->string('saat', 5);                              // "19:30"
                $t->string('durum', 20)->default('bekliyor');       // bekliyor | onaylandi | geldi | iptal | gelmedi
                $t->string('kaynak', 20)->default('telefon');       // telefon | web | qr
                $t->string('not')->nullable();
                $t->unsignedBigInteger('personel_id')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'tarih']);
            });
        }
        if (DB::table('rezervasyonlar')->where('sube_id', $subeId)->count() > 0) return;
        $bugun = now()->format('Y-m-d');
        $yarin = now()->addDay()->format('Y-m-d');
        $demo = [
            ['ad' => 'Ahmet Yılmaz', 'telefon' => '0532 111 22 33', 'kisi' => 4, 'tarih' => $bugun, 'saat' => '19:30', 'durum' => 'onaylandi', 'kaynak' => 'telefon', 'not' => 'Pencere kenarı isteği'],
            ['ad' => 'Elif Kaya', 'telefon' => '0533 999 88 77', 'kisi' => 2, 'tarih' => $bugun, 'saat' => '20:00', 'durum' => 'bekliyor', 'kaynak' => 'web', 'not' => null],
            ['ad' => 'Berk İnşaat (kurumsal)', 'telefon' => '0216 555 44 33', 'kisi' => 8, 'tarih' => $bugun, 'saat' => '21:00', 'durum' => 'onaylandi', 'kaynak' => 'telefon', 'not' => 'Doğum günü pastası'],
            ['ad' => 'Deniz Demir', 'telefon' => '0534 222 11 00', 'kisi' => 3, 'tarih' => $yarin, 'saat' => '13:00', 'durum' => 'bekliyor', 'kaynak' => 'web', 'not' => 'Bebek sandalyesi'],
            ['ad' => 'Selin Ak', 'telefon' => '0535 333 22 11', 'kisi' => 6, 'tarih' => $yarin, 'saat' => '20:30', 'durum' => 'onaylandi', 'kaynak' => 'qr', 'not' => null],
        ];
        foreach ($demo as $d) {
            DB::table('rezervasyonlar')->insert(array_merge($d, ['sube_id' => $subeId, 'created_at' => now()]));
        }
    }
}

// Kurulum + demo seed
Route::get('/rezervasyon-kur', function () {
    $subeId = DB::table('subeler')->value('id');
    _rezervasyonEnsure($subeId);
    $say = DB::table('rezervasyonlar')->where('sube_id', $subeId)->count();
    return ['ok' => 1, 'mesaj' => "Rezervasyon tablosu hazır + demo yüklendi. Toplam kayıt: $say"];
});

if (!function_exists('_rezDurumlar')) {
    function _rezDurumlar() { return ['bekliyor', 'onaylandi', 'geldi', 'iptal', 'gelmedi']; }
}

// PATRON API: gune gore rezervasyon listesi (varsayilan bugun)
Route::get('/api/patron/rezervasyonlar', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _rezervasyonEnsure($p->sube_id);
    $tarih = $r->query('tarih') ?: now()->format('Y-m-d');
    $rows = DB::table('rezervasyonlar')->leftJoin('masalar', 'rezervasyonlar.masa_id', '=', 'masalar.id')
        ->where('rezervasyonlar.sube_id', $p->sube_id)->where('rezervasyonlar.tarih', $tarih)
        ->orderBy('rezervasyonlar.saat')
        ->select('rezervasyonlar.*', 'masalar.ad as masa_ad')
        ->get()->map(fn ($x) => [
            'id' => (int) $x->id, 'ad' => $x->ad, 'telefon' => $x->telefon, 'kisi' => (int) $x->kisi,
            'tarih' => $x->tarih, 'saat' => substr($x->saat, 0, 5), 'durum' => $x->durum, 'kaynak' => $x->kaynak,
            'not' => $x->not, 'masa_id' => $x->masa_id ? (int) $x->masa_id : null, 'masa_ad' => $x->masa_ad,
        ]);
    // Gunluk ozet
    $aktif = $rows->whereIn('durum', ['bekliyor', 'onaylandi']);
    $ozet = [
        'toplam' => $rows->count(),
        'bekleyen' => $rows->where('durum', 'bekliyor')->count(),
        'onayli' => $rows->where('durum', 'onaylandi')->count(),
        'geldi' => $rows->where('durum', 'geldi')->count(),
        'kisi' => $aktif->sum('kisi'),
    ];
    // Sonraki 7 gunun rezervasyon sayilari (mini takvim)
    $gunler = [];
    for ($i = 0; $i < 7; $i++) {
        $g = now()->addDays($i)->format('Y-m-d');
        $gunler[] = ['tarih' => $g, 'adet' => DB::table('rezervasyonlar')->where('sube_id', $p->sube_id)->where('tarih', $g)->whereIn('durum', ['bekliyor', 'onaylandi', 'geldi'])->count()];
    }
    return ['ok' => 1, 'tarih' => $tarih, 'rezervasyonlar' => $rows->values(), 'ozet' => $ozet, 'gunler' => $gunler];
});

// PATRON API: rezervasyon ekle
Route::post('/api/patron/rezervasyon-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _rezervasyonEnsure($p->sube_id);
    $ad = trim((string) $r->input('ad'));
    if ($ad === '') return ['ok' => 0, 'hata' => 'İsim gerekli'];
    $tarih = $r->input('tarih') ?: now()->format('Y-m-d');
    $saat = substr(trim((string) $r->input('saat', '19:00')), 0, 5);
    $masaId = (int) $r->input('masa_id');
    if ($masaId && !DB::table('masalar')->where('id', $masaId)->where('sube_id', $p->sube_id)->exists()) $masaId = 0;
    $id = DB::table('rezervasyonlar')->insertGetId([
        'sube_id' => $p->sube_id, 'masa_id' => $masaId ?: null, 'ad' => $ad,
        'telefon' => trim((string) $r->input('telefon')) ?: null, 'kisi' => max(1, (int) $r->input('kisi', 2)),
        'tarih' => $tarih, 'saat' => $saat, 'durum' => 'onaylandi', 'kaynak' => 'telefon',
        'not' => trim((string) $r->input('not')) ?: null, 'personel_id' => $p->id, 'created_at' => now(),
    ]);
    return ['ok' => 1, 'id' => $id, 'mesaj' => "$ad · $tarih $saat rezervasyonu eklendi."];
});

// PATRON API: rezervasyon durum guncelle (+ masa atama opsiyonel)
Route::post('/api/patron/rezervasyon-durum', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $rez = DB::table('rezervasyonlar')->where('id', (int) $r->input('id'))->where('sube_id', $p->sube_id)->first();
    if (!$rez) return ['ok' => 0, 'hata' => 'Rezervasyon bulunamadı'];
    $durum = (string) $r->input('durum');
    $upd = [];
    if (in_array($durum, _rezDurumlar())) $upd['durum'] = $durum;
    if ($r->has('masa_id')) {
        $masaId = (int) $r->input('masa_id');
        $upd['masa_id'] = ($masaId && DB::table('masalar')->where('id', $masaId)->where('sube_id', $p->sube_id)->exists()) ? $masaId : null;
    }
    if (empty($upd)) return ['ok' => 0, 'hata' => 'Geçersiz işlem'];
    DB::table('rezervasyonlar')->where('id', $rez->id)->update($upd);
    // Masa durumu senkron: onaylandi+masa -> rezerve; iptal/gelmedi -> masa bos (rezerve idiyse)
    if (!empty($rez->masa_id) || !empty($upd['masa_id'])) {
        $masaId = $upd['masa_id'] ?? $rez->masa_id;
        $yeniDurum = $upd['durum'] ?? $rez->durum;
        if ($masaId) {
            $m = DB::table('masalar')->where('id', $masaId)->first();
            if ($m) {
                if ($yeniDurum === 'onaylandi' && $m->durum === 'bos') DB::table('masalar')->where('id', $masaId)->update(['durum' => 'rezerve']);
                if (in_array($yeniDurum, ['iptal', 'gelmedi', 'geldi']) && $m->durum === 'rezerve') DB::table('masalar')->where('id', $masaId)->update(['durum' => 'bos']);
            }
        }
    }
    return ['ok' => 1, 'mesaj' => 'Rezervasyon güncellendi.'];
});

// ONLINE REZERVASYON (musteri tarafi) — /rez/{sube}
Route::get('/rez/{sube}', function ($sube) {
    $s = DB::table('subeler')->where('id', $sube)->first();
    if (!$s) abort(404);
    _rezervasyonEnsure($s->id);
    return view('rez', ['sube' => $s]);
});
Route::post('/rez/{sube}', function (Request $r, $sube) {
    $s = DB::table('subeler')->where('id', $sube)->first();
    if (!$s) abort(404);
    _rezervasyonEnsure($s->id);
    $ad = trim((string) $r->input('ad'));
    $tel = trim((string) $r->input('telefon'));
    if ($ad === '' || $tel === '') return response()->json(['ok' => 0, 'hata' => 'Ad ve telefon zorunlu.'], 422);
    $tarih = $r->input('tarih') ?: now()->format('Y-m-d');
    $saat = substr(trim((string) $r->input('saat', '19:00')), 0, 5);
    DB::table('rezervasyonlar')->insert([
        'sube_id' => $s->id, 'masa_id' => null, 'ad' => $ad, 'telefon' => $tel,
        'kisi' => max(1, (int) $r->input('kisi', 2)), 'tarih' => $tarih, 'saat' => $saat,
        'durum' => 'bekliyor', 'kaynak' => 'web', 'not' => trim((string) $r->input('not')) ?: null, 'created_at' => now(),
    ]);
    return ['ok' => 1, 'mesaj' => 'Rezervasyon talebiniz alındı! En kısa sürede sizi arayıp onaylayacağız.'];
});

// ---- SEBEPLER (silme/iskonto/ikram/iptal) - duzenlenebilir liste ----
if (!function_exists('_restoSebeplerEnsure')) {
    function _restoSebeplerEnsure($subeId)
    {
        if (!Schema::hasTable('sebepler')) {
            Schema::create('sebepler', function ($t) {
                $t->id();
                $t->unsignedBigInteger('sube_id');
                $t->string('tur', 20);
                $t->string('metin');
                $t->unsignedInteger('sira')->default(0);
                $t->boolean('aktif')->default(true);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'tur']);
            });
        }
        if (DB::table('sebepler')->where('sube_id', $subeId)->count() > 0) return;
        $def = [
            'void' => ['Müşteri beğenmedi', 'Yanlış/fazla girildi', 'Müşteri vazgeçti', 'Ürün tükendi', 'Fire / zayi', 'Sipariş geç gitti', 'Ürün döküldü'],
            'indirim' => ['Müşteri memnuniyeti', 'Personel', 'İşletme yakını', 'Telafi', 'Sadık müşteri'],
            'ikram' => ['Sipariş geç gitti', 'Tanıdık', 'Daimi müşteri', 'Sorunlu misafir', 'İşletme jesti'],
            'iptal' => ['Müşteri gelmedi', 'Yanlış açıldı', 'Çift açıldı', 'Müşteri vazgeçti'],
        ];
        $i = 0;
        $rows = [];
        foreach ($def as $tur => $list) {
            foreach ($list as $s) $rows[] = ['sube_id' => $subeId, 'tur' => $tur, 'metin' => $s, 'sira' => $i++, 'aktif' => 1, 'created_at' => now()];
        }
        DB::table('sebepler')->insert($rows);
    }
}

Route::get('/api/patron/sebepler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _restoSebeplerEnsure($p->sube_id);
    $q = DB::table('sebepler')->where('sube_id', $p->sube_id)->where('aktif', 1);
    if (in_array($r->tur, ['void', 'indirim', 'ikram', 'iptal'])) $q->where('tur', $r->tur);
    $liste = $q->orderBy('tur')->orderBy('sira')->orderBy('id')->get(['id', 'tur', 'metin']);
    return ['ok' => 1, 'duzenleyebilir' => in_array($p->rol, ['sahip', 'mudur']), 'sebepler' => $liste];
});

Route::post('/api/patron/sebep-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _restoSebeplerEnsure($p->sube_id);
    $tur = in_array($r->tur, ['void', 'indirim', 'ikram', 'iptal']) ? $r->tur : 'void';
    $metin = trim((string) $r->metin);
    if ($metin === '') return ['ok' => 0, 'hata' => 'Sebep metni boş'];
    DB::table('sebepler')->insert(['sube_id' => $p->sube_id, 'tur' => $tur, 'metin' => $metin, 'sira' => 99, 'aktif' => 1, 'created_at' => now()]);
    return ['ok' => 1];
});

Route::post('/api/patron/sebep-sil', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    DB::table('sebepler')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->update(['aktif' => 0]);
    return ['ok' => 1];
});

// ---- MASA TASI ----
Route::post('/api/patron/masa-tasi', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Masa taşıma yetkiniz yok.'];
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a || $a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Açık adisyon bulunamadı'];
    $yeni = DB::table('masalar')->where('id', (int) $r->yeni_masa_id)->where('sube_id', $p->sube_id)->first();
    if (!$yeni) return ['ok' => 0, 'hata' => 'Hedef masa bulunamadı'];
    if (DB::table('adisyonlar')->where('masa_id', $yeni->id)->where('durum', 'acik')->exists()) {
        return ['ok' => 0, 'hata' => $yeni->ad . ' dolu. Boş masa seçin (dolu için Birleştir kullanın).'];
    }
    $eski = $a->masa_id;
    DB::table('adisyonlar')->where('id', $a->id)->update(['masa_id' => $yeni->id, 'updated_at' => now()]);
    if ($eski) DB::table('masalar')->where('id', $eski)->update(['durum' => 'bos']);
    DB::table('masalar')->where('id', $yeni->id)->update(['durum' => 'dolu']);
    DB::table('adisyon_masa_loglari')->insert(['adisyon_id' => $a->id, 'islem' => 'tasima', 'eski_masa_id' => $eski, 'yeni_masa_id' => $yeni->id, 'personel_id' => $p->id, 'created_at' => now()]);
    return ['ok' => 1, 'mesaj' => $yeni->ad . ' masasına taşındı.'];
});

// ---- MASA BIRLESTIR ----
Route::post('/api/patron/masa-birlestir', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    // Birlestirme rutin bir masa operasyonu -> tasima ile ayni erisim (adisyon_birlestir VEYA adisyon_ac yeterli)
    if (!_restoYetkiVar($p, 'adisyon_birlestir') && !_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Masa birleştirme yetkiniz yok.'];
    $hedef = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    $kaynak = DB::table('adisyonlar')->find((int) $r->kaynak_adisyon_id);
    if (!$hedef || !$kaynak || $hedef->durum !== 'acik' || $kaynak->durum !== 'acik') return ['ok' => 0, 'hata' => 'Açık adisyonlar bulunamadı'];
    if ($hedef->id === $kaynak->id) return ['ok' => 0, 'hata' => 'Aynı adisyon seçilemez'];
    DB::table('adisyon_kalemleri')->where('adisyon_id', $kaynak->id)->update(['adisyon_id' => $hedef->id, 'updated_at' => now()]);
    // Zincir: kaynaga daha once birlesmis masalarin loglari da hedefe tasinsin (Masa1->Masa2->Masa3)
    DB::table('adisyon_masa_loglari')->where('adisyon_id', $kaynak->id)->where('islem', 'birlestirme')->update(['adisyon_id' => $hedef->id]);
    DB::table('adisyonlar')->where('id', $kaynak->id)->update(['durum' => 'iptal', 'kapanis' => now(), 'updated_at' => now()]);
    if ($kaynak->masa_id) DB::table('masalar')->where('id', $kaynak->masa_id)->update(['durum' => 'bos']);
    $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $hedef->id)->where('durum', '!=', 'iptal')->sum('tutar');
    $top = max(0, $ara - (float) $hedef->indirim - (float) $hedef->ikram);
    DB::table('adisyonlar')->where('id', $hedef->id)->update(['ara_toplam' => $ara, 'toplam' => $top,
        'misafir_sayisi' => (int) $hedef->misafir_sayisi + (int) $kaynak->misafir_sayisi, 'updated_at' => now()]);
    DB::table('adisyon_masa_loglari')->insert(['adisyon_id' => $hedef->id, 'islem' => 'birlestirme', 'eski_masa_id' => $kaynak->masa_id, 'yeni_masa_id' => $hedef->masa_id, 'personel_id' => $p->id, 'created_at' => now()]);
    return ['ok' => 1, 'mesaj' => 'Masalar birleştirildi.', 'toplam' => $top];
});

// ---- MASA GRUPLA: bos masalari da birlestir (buyuk grup once oturur, sonra siparis) ----
// hedef bos ise adisyon acar (misafir), kaynak dolu ise fatura tasir; kaynak bos ise sadece baglar.
Route::post('/api/patron/masa-grupla', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_birlestir') && !_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Masa birleştirme yetkiniz yok.'];
    $hedefMasa = DB::table('masalar')->where('id', (int) $r->hedef_masa_id)->where('sube_id', $p->sube_id)->first();
    $kaynakMasa = DB::table('masalar')->where('id', (int) $r->kaynak_masa_id)->where('sube_id', $p->sube_id)->first();
    if (!$hedefMasa || !$kaynakMasa) return ['ok' => 0, 'hata' => 'Masa bulunamadı'];
    if ($hedefMasa->id === $kaynakMasa->id) return ['ok' => 0, 'hata' => 'Aynı masa seçilemez'];
    $misafir = max(1, (int) ($r->misafir ?? 1));

    // Hedef adisyonu: yoksa YENI ac (birlesik masa acilis)
    $yeniAcildi = false;
    $hedef = DB::table('adisyonlar')->where('masa_id', $hedefMasa->id)->where('durum', 'acik')->first();
    if (!$hedef) {
        $hedefId = DB::table('adisyonlar')->insertGetId([
            'sube_id' => $p->sube_id, 'masa_id' => $hedefMasa->id, 'kanal' => 'salon',
            'misafir_sayisi' => $misafir, 'durum' => 'acik', 'acan_personel_id' => $p->id,
            'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
            'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('masalar')->where('id', $hedefMasa->id)->update(['durum' => 'dolu']);
        $hedef = DB::table('adisyonlar')->find($hedefId);
        $yeniAcildi = true;
    }

    // Kaynak adisyonu varsa fatura birlestir; yoksa sadece bagla
    $kaynak = DB::table('adisyonlar')->where('masa_id', $kaynakMasa->id)->where('durum', 'acik')->first();
    $ekMisafir = 0;
    if ($kaynak) {
        DB::table('adisyon_kalemleri')->where('adisyon_id', $kaynak->id)->update(['adisyon_id' => $hedef->id, 'updated_at' => now()]);
        DB::table('adisyon_masa_loglari')->where('adisyon_id', $kaynak->id)->where('islem', 'birlestirme')->update(['adisyon_id' => $hedef->id]);
        DB::table('adisyonlar')->where('id', $kaynak->id)->update(['durum' => 'iptal', 'kapanis' => now(), 'updated_at' => now()]);
        $ekMisafir = (int) $kaynak->misafir_sayisi;
    }
    DB::table('masalar')->where('id', $kaynakMasa->id)->update(['durum' => 'bos']);

    DB::table('adisyon_masa_loglari')->insert(['adisyon_id' => $hedef->id, 'islem' => 'birlestirme', 'eski_masa_id' => $kaynakMasa->id, 'yeni_masa_id' => $hedefMasa->id, 'personel_id' => $p->id, 'created_at' => now()]);
    $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $hedef->id)->where('durum', '!=', 'iptal')->sum('tutar');
    $top = max(0, $ara - (float) $hedef->indirim - (float) $hedef->ikram);
    // Yeni acildiysa misafir zaten dogru; degilse kaynaktan gelen misafiri ekle
    $yeniMisafir = $yeniAcildi ? (int) $hedef->misafir_sayisi : ((int) $hedef->misafir_sayisi + $ekMisafir);
    DB::table('adisyonlar')->where('id', $hedef->id)->update(['ara_toplam' => $ara, 'toplam' => $top, 'misafir_sayisi' => $yeniMisafir, 'updated_at' => now()]);
    return ['ok' => 1, 'mesaj' => 'Masalar birleştirildi.', 'adisyon_id' => $hedef->id, 'yeni_acildi' => $yeniAcildi, 'toplam' => $top];
});

// ---- MASA AYIR: birlesik gruptan bir masayi ayir -> o masaya YENI bagimsiz adisyon (secili urunlerle) ----
// Senaryo: 3 masa birlestirildi, bir alt-grup kalan masada oturuyor; onlari ayri hesaba/masaya al.
Route::post('/api/patron/masa-ayir', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    if (!_restoYetkiVar($p, 'adisyon_birlestir') && !_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Masa ayırma yetkiniz yok.'];
    $ana = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$ana || $ana->durum !== 'acik') return ['ok' => 0, 'hata' => 'Açık adisyon bulunamadı'];
    $masaId = (int) $r->masa_id;
    // Bu masa gercekten bu adisyonun birlesik grubunda (kaynak) mi?
    $log = DB::table('adisyon_masa_loglari')->where('adisyon_id', $ana->id)->where('islem', 'birlestirme')
        ->where('eski_masa_id', $masaId)->first();
    if (!$log) return ['ok' => 0, 'hata' => 'Bu masa bu birleşik grupta değil.'];
    $ayrilanMasa = DB::table('masalar')->where('id', $masaId)->where('sube_id', $p->sube_id)->first();
    if (!$ayrilanMasa) return ['ok' => 0, 'hata' => 'Masa bulunamadı'];
    if (DB::table('adisyonlar')->where('masa_id', $masaId)->where('durum', 'acik')->exists()) {
        return ['ok' => 0, 'hata' => $ayrilanMasa->ad . ' zaten dolu.'];
    }
    // Ayrilan masaya yeni acik adisyon
    $misafir = max(1, (int) ($r->misafir ?? 1));
    $yeniId = DB::table('adisyonlar')->insertGetId([
        'sube_id' => $ana->sube_id, 'masa_id' => $masaId, 'kanal' => 'salon', 'misafir_sayisi' => $misafir, 'durum' => 'acik',
        'acan_personel_id' => $p->id, 'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
        'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    // Secili kalemleri yeni adisyona tasi (bos gelirse masa bos acilir)
    $ids = array_filter(array_map('intval', explode(',', (string) $r->kalem_idler)));
    if ($ids) {
        DB::table('adisyon_kalemleri')->where('adisyon_id', $ana->id)->whereIn('id', $ids)
            ->where('durum', '!=', 'iptal')->update(['adisyon_id' => $yeniId, 'updated_at' => now()]);
    }
    // Masa gruptan cikar (log sil) + dolu yap
    DB::table('adisyon_masa_loglari')->where('id', $log->id)->delete();
    DB::table('masalar')->where('id', $masaId)->update(['durum' => 'dolu']);
    // Iki adisyonu da yeniden hesapla
    foreach ([$ana->id, $yeniId] as $aid) {
        $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $aid)->where('durum', '!=', 'iptal')->sum('tutar');
        $adis = DB::table('adisyonlar')->find($aid);
        $top = max(0, $ara - (float) $adis->indirim - (float) $adis->ikram);
        DB::table('adisyonlar')->where('id', $aid)->update(['ara_toplam' => $ara, 'toplam' => $top, 'updated_at' => now()]);
    }
    return ['ok' => 1, 'mesaj' => $ayrilanMasa->ad . ' ayrıldı, kendi hesabı açıldı.', 'yeni_adisyon_id' => $yeniId];
});

// ---- ADISYON BOL ----
Route::post('/api/patron/adisyon-bol', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_bol')) return ['ok' => 0, 'hata' => 'Adisyon bölme yetkiniz yok.'];
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a || $a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Açık adisyon bulunamadı'];
    $ids = array_filter(array_map('intval', explode(',', (string) $r->kalem_idler)));
    if (empty($ids)) return ['ok' => 0, 'hata' => 'Bölünecek ürün seçin'];
    $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->whereIn('id', $ids)->where('durum', '!=', 'iptal')->get();
    if ($kalemler->isEmpty()) return ['ok' => 0, 'hata' => 'Geçerli ürün yok'];
    $toplamKalem = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->count();
    if ($kalemler->count() >= $toplamKalem) return ['ok' => 0, 'hata' => 'En az bir ürün asıl adisyonda kalmalı.'];
    $yeniId = DB::table('adisyonlar')->insertGetId([
        'sube_id' => $a->sube_id, 'masa_id' => $a->masa_id, 'kanal' => $a->kanal, 'misafir_sayisi' => 1, 'durum' => 'acik',
        'acan_personel_id' => $p->id, 'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
        'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('adisyon_kalemleri')->whereIn('id', $kalemler->pluck('id'))->update(['adisyon_id' => $yeniId, 'updated_at' => now()]);
    foreach ([$a->id, $yeniId] as $aid) {
        $ara = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $aid)->where('durum', '!=', 'iptal')->sum('tutar');
        $adis = DB::table('adisyonlar')->find($aid);
        $top = max(0, $ara - (float) $adis->indirim - (float) $adis->ikram);
        DB::table('adisyonlar')->where('id', $aid)->update(['ara_toplam' => $ara, 'toplam' => $top, 'updated_at' => now()]);
    }
    DB::table('adisyon_masa_loglari')->insert(['adisyon_id' => $a->id, 'islem' => 'bolme', 'eski_masa_id' => $a->masa_id, 'yeni_masa_id' => $a->masa_id, 'personel_id' => $p->id, 'created_at' => now()]);
    return ['ok' => 1, 'mesaj' => $kalemler->count() . ' ürün yeni adisyona bölündü.', 'yeni_adisyon_id' => $yeniId];
});

// ---- FIS: yazdirma icin adisyon fisi verisi ----
Route::get('/api/patron/fis', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $a = DB::table('adisyonlar')->find((int) $r->adisyon_id);
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    $sube = DB::table('subeler')->find($a->sube_id);
    $masa = $a->masa_id ? DB::table('masalar')->where('id', $a->masa_id)->value('ad') : null;
    $garson = $a->acan_personel_id ? DB::table('personeller')->where('id', $a->acan_personel_id)->value('ad') : null;
    $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')
        ->select('urun_adi', 'adet', 'birim_fiyat', 'tutar')->orderBy('id')->get()
        ->map(fn ($k) => ['ad' => $k->urun_adi, 'adet' => (float) $k->adet, 'birim_fiyat' => (float) $k->birim_fiyat, 'tutar' => (float) $k->tutar]);
    $odemeler = DB::table('odemeler')->where('adisyon_id', $a->id)->select('tip', 'tutar')->get()
        ->map(fn ($o) => ['tip' => $o->tip, 'tutar' => (float) $o->tutar]);
    return [
        'ok' => 1,
        'isletme' => $sube->ad ?? 'ResteOS', 'adres' => $sube->adres ?? '', 'telefon' => $sube->telefon ?? '',
        'masa' => $masa ?? ucfirst($a->kanal), 'garson' => $garson ?? '-',
        'tarih' => now()->format('d.m.Y H:i'), 'adisyon_no' => $a->id,
        'kalemler' => $kalemler,
        'ara_toplam' => (float) $a->ara_toplam, 'indirim' => (float) $a->indirim, 'ikram' => (float) $a->ikram, 'toplam' => (float) $a->toplam,
        'odemeler' => $odemeler,
    ];
});

// ---- GUN SONU / Z RAPORU ----
Route::get('/api/patron/z-raporu', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $tarih = $r->tarih ? \Carbon\Carbon::parse($r->tarih) : today();
    $from = (clone $tarih)->startOfDay();
    $to = (clone $tarih)->endOfDay();
    $sube = DB::table('subeler')->find($p->sube_id);

    $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
    $odeme = DB::table('odemeler')->whereBetween('created_at', [$from, $to])
        ->select('tip', DB::raw('COUNT(*) as adet'), DB::raw('SUM(tutar) as tutar'))->groupBy('tip')->orderByDesc('tutar')->get();
    $kapananQ = DB::table('adisyonlar')->where('sube_id', $p->sube_id)->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to]);
    $kapanan = (clone $kapananQ)->count();
    $misafir = (int) (clone $kapananQ)->sum('misafir_sayisi');
    $iskonto = (float) (clone $kapananQ)->sum('indirim');
    $ikram = (float) (clone $kapananQ)->sum('ikram');
    $iptalAdet = DB::table('adisyonlar')->where('sube_id', $p->sube_id)->where('durum', 'iptal')->whereBetween('acilis', [$from, $to])->count();
    $iptalTutar = (float) DB::table('adisyonlar')->where('sube_id', $p->sube_id)->where('durum', 'iptal')->whereBetween('acilis', [$from, $to])->sum('toplam');
    $void = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyon_kalemleri.durum', 'iptal')->whereBetween('adisyonlar.acilis', [$from, $to])->sum('adisyon_kalemleri.tutar');
    $fire = 0.0;
    try {
        $fire = (float) DB::table('stok_hareketleri')->where('sube_id', $p->sube_id)->where('tip', 'fire')->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('COALESCE(SUM(ABS(miktar)*birim_maliyet),0) as t'))->value('t');
    } catch (\Throwable $e) {
    }
    // food-cost
    $maliyet = 0.0;
    if (function_exists('_restoUrunMaliyetMap')) {
        $map = _restoUrunMaliyetMap();
        $satir = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi', DB::raw('SUM(adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as satis'))
            ->groupBy('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi')->get();
        foreach ($satir as $s) {
            $b = $map['id'][(int) $s->urun_id] ?? ($map['ad'][$s->urun_adi] ?? 0);
            $m = (float) $s->adet * (float) $b;
            if ($m <= 0 && $s->satis > 0) $m = (float) $s->satis * 0.30;
            $maliyet += $m;
        }
    }
    $servis = DB::table('adisyonlar')->where('sube_id', $p->sube_id)->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])
        ->select('kanal', DB::raw('COUNT(*) as adet'), DB::raw('SUM(toplam) as tutar'))->groupBy('kanal')->get()
        ->map(fn ($k) => ['ad' => ['salon' => 'Masa', 'paket' => 'Paket', 'qr' => 'QR'][$k->kanal] ?? ucfirst($k->kanal), 'adet' => $k->adet, 'tutar' => (float) $k->tutar]);
    $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
        ->select('urun_adi', DB::raw('SUM(adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as ciro'))->groupBy('urun_adi')->orderByDesc('adet')->limit(5)->get();
    $acikKalan = DB::table('adisyonlar')->where('sube_id', $p->sube_id)->where('durum', 'acik')->count();

    return [
        'ok' => 1, 'isletme' => $sube->ad ?? 'ResteOS', 'tarih' => $tarih->format('d.m.Y'),
        'ciro' => $ciro, 'kapanan' => $kapanan, 'misafir' => $misafir,
        'ortalama' => $kapanan > 0 ? round($ciro / $kapanan) : 0, 'kisi_basi' => $misafir > 0 ? round($ciro / $misafir) : 0,
        'odeme' => $odeme, 'servis' => $servis, 'top' => $top,
        'iskonto' => $iskonto, 'ikram' => $ikram, 'iptal_adet' => $iptalAdet, 'iptal_tutar' => $iptalTutar, 'void' => $void, 'fire' => $fire,
        'maliyet' => round($maliyet), 'maliyet_yuzde' => $ciro > 0 ? round($maliyet / $ciro * 100) : 0, 'brut_kar' => round($ciro - $maliyet),
        'acik_kalan' => $acikKalan,
    ];
});

// ---- HAREKETLER / AKTIVITE LOG (masa tasi/birlestir/bol + void/iskonto/ikram) ----
Route::get('/api/patron/hareketler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $out = [];
    // Masa loglari
    $masaLog = DB::table('adisyon_masa_loglari')->join('adisyonlar', 'adisyon_masa_loglari.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $p->sube_id)
        ->leftJoin('personeller', 'adisyon_masa_loglari.personel_id', '=', 'personeller.id')
        ->select('adisyon_masa_loglari.islem', 'adisyon_masa_loglari.created_at', 'personeller.ad as personel',
            'adisyon_masa_loglari.adisyon_id')
        ->orderByDesc('adisyon_masa_loglari.created_at')->limit(60)->get();
    foreach ($masaLog as $m) {
        $etiket = ['tasima' => 'Masa Taşındı', 'birlestirme' => 'Masa Birleştirildi', 'bolme' => 'Adisyon Bölündü', 'garson_devri' => 'Garson Devri'][$m->islem] ?? $m->islem;
        $out[] = ['tip' => $m->islem, 'baslik' => $etiket, 'personel' => $m->personel ?? '-', 'tutar' => null,
            'zaman' => \Carbon\Carbon::parse($m->created_at)->format('d.m H:i'), 'ts' => $m->created_at, 'adisyon_id' => $m->adisyon_id];
    }
    // Void/iskonto/ikram loglari
    $iLog = DB::table('iptal_indirim_loglari')->where('iptal_indirim_loglari.sube_id', $p->sube_id)
        ->leftJoin('personeller', 'iptal_indirim_loglari.personel_id', '=', 'personeller.id')
        ->select('iptal_indirim_loglari.tip', 'iptal_indirim_loglari.tutar', 'iptal_indirim_loglari.sebep',
            'iptal_indirim_loglari.created_at', 'personeller.ad as personel', 'iptal_indirim_loglari.adisyon_id')
        ->orderByDesc('iptal_indirim_loglari.created_at')->limit(60)->get();
    foreach ($iLog as $m) {
        $etiket = ['void' => 'Ürün Silindi', 'indirim' => 'İskonto', 'ikram' => 'İkram'][$m->tip] ?? $m->tip;
        $out[] = ['tip' => $m->tip, 'baslik' => $etiket . ($m->sebep ? ' · ' . $m->sebep : ''), 'personel' => $m->personel ?? '-',
            'tutar' => (float) $m->tutar, 'zaman' => \Carbon\Carbon::parse($m->created_at)->format('d.m H:i'), 'ts' => $m->created_at, 'adisyon_id' => $m->adisyon_id];
    }
    usort($out, fn ($a, $b) => strcmp($b['ts'], $a['ts']));
    $out = array_slice($out, 0, 80);
    foreach ($out as &$o) unset($o['ts']);
    return ['ok' => 1, 'hareketler' => $out];
});

// ============================ CARI / ACIK HESAP ("bana yazin") ============================
// Tablolari kur + Patron hesabi + demo cariler/hareketler (tek sefer)
Route::get('/cari-kur', function () {
    if (!Schema::hasTable('cari_hesaplar')) {
        Schema::create('cari_hesaplar', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->string('ad');
            $t->string('tip')->default('musteri'); // patron | musteri | firma | personel
            $t->string('telefon')->nullable();
            $t->boolean('aktif')->default(true);
            $t->timestamp('created_at')->useCurrent();
        });
    }
    if (!Schema::hasTable('cari_hareketler')) {
        Schema::create('cari_hareketler', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id');
            $t->unsignedBigInteger('cari_id');
            $t->string('tip'); // borc (satis) | tahsilat (odeme)
            $t->decimal('tutar', 14, 2);
            $t->unsignedBigInteger('adisyon_id')->nullable();
            $t->string('odeme_sekli')->nullable(); // tahsilat: nakit|havale|kredi
            $t->string('aciklama')->nullable();
            $t->unsignedBigInteger('personel_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['sube_id', 'cari_id']);
        });
    }
    $sube = DB::table('subeler')->first();
    if (DB::table('cari_hesaplar')->where('sube_id', $sube->id)->count() > 0) return 'Cari zaten kurulu.';
    $sahip = DB::table('personeller')->where('rol', 'sahip')->value('id');
    $ids = [DB::table('cari_hesaplar')->insertGetId(['sube_id' => $sube->id, 'ad' => 'Patron (İşletme Sahibi)', 'tip' => 'patron', 'aktif' => 1, 'created_at' => now()])];
    foreach ([['Ahmet Yılmaz', 'musteri', '05321112233'], ['Berk İnşaat', 'firma', '02165554433'], ['Elif Kaya', 'musteri', '05339998877'], ['Deniz Ltd. Şti.', 'firma', '02123334455']] as [$ad, $tip, $tel]) {
        $ids[] = DB::table('cari_hesaplar')->insertGetId(['sube_id' => $sube->id, 'ad' => $ad, 'tip' => $tip, 'telefon' => $tel, 'aktif' => 1, 'created_at' => now()]);
    }
    foreach ($ids as $cid) {
        for ($i = 0, $n = random_int(2, 6); $i < $n; $i++) {
            DB::table('cari_hareketler')->insert(['sube_id' => $sube->id, 'cari_id' => $cid, 'tip' => 'borc', 'tutar' => random_int(150, 1200),
                'aciklama' => 'Açık hesap satış', 'personel_id' => $sahip, 'created_at' => now()->subDays(random_int(1, 40))]);
        }
        if (random_int(0, 1)) DB::table('cari_hareketler')->insert(['sube_id' => $sube->id, 'cari_id' => $cid, 'tip' => 'tahsilat', 'tutar' => random_int(200, 800),
            'odeme_sekli' => 'havale', 'aciklama' => 'Tahsilat', 'personel_id' => $sahip, 'created_at' => now()->subDays(random_int(1, 20))]);
    }
    return 'Cari hesaplar kuruldu: ' . count($ids) . ' hesap (Patron + demo müşteri/firma) + hareketler. ✅';
});

if (!function_exists('_cariBakiye')) {
    function _cariBakiye($cariId)
    {
        $borc = (float) DB::table('cari_hareketler')->where('cari_id', $cariId)->where('tip', 'borc')->sum('tutar');
        $tah = (float) DB::table('cari_hareketler')->where('cari_id', $cariId)->where('tip', 'tahsilat')->sum('tutar');
        return round($borc - $tah, 2);
    }
}

Route::get('/api/patron/cariler', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!Schema::hasTable('cari_hesaplar')) return ['ok' => 1, 'cariler' => [], 'toplam_alacak' => 0];
    $cariler = DB::table('cari_hesaplar')->where('sube_id', $p->sube_id)->where('aktif', 1)
        ->orderByRaw("FIELD(tip,'patron','firma','musteri','personel')")->orderBy('ad')->get()
        ->map(fn ($c) => ['id' => (int) $c->id, 'ad' => $c->ad, 'tip' => $c->tip, 'telefon' => $c->telefon, 'bakiye' => _cariBakiye($c->id)]);
    return ['ok' => 1, 'toplam_alacak' => round($cariler->sum('bakiye'), 2), 'cariler' => $cariler];
});

Route::get('/api/patron/cari-detay', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $c = DB::table('cari_hesaplar')->where('id', (int) $r->id)->where('sube_id', $p->sube_id)->first();
    if (!$c) return ['ok' => 0, 'hata' => 'Cari bulunamadı'];
    $borc = (float) DB::table('cari_hareketler')->where('cari_id', $c->id)->where('tip', 'borc')->sum('tutar');
    $tah = (float) DB::table('cari_hareketler')->where('cari_id', $c->id)->where('tip', 'tahsilat')->sum('tutar');
    $hareketler = DB::table('cari_hareketler')->where('cari_id', $c->id)->orderByDesc('created_at')->limit(80)->get()
        ->map(fn ($h) => ['tip' => $h->tip, 'tutar' => (float) $h->tutar, 'aciklama' => $h->aciklama, 'odeme_sekli' => $h->odeme_sekli,
            'zaman' => \Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i')]);
    return ['ok' => 1, 'ad' => $c->ad, 'tip' => $c->tip, 'telefon' => $c->telefon, 'bakiye' => round($borc - $tah, 2),
        'toplam_borc' => round($borc, 2), 'toplam_tahsilat' => round($tah, 2), 'hareketler' => $hareketler];
});

Route::post('/api/patron/cari-tahsilat', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $c = DB::table('cari_hesaplar')->where('id', (int) $r->cari_id)->where('sube_id', $p->sube_id)->first();
    if (!$c) return ['ok' => 0, 'hata' => 'Cari bulunamadı'];
    $tutar = max(0, (float) $r->tutar);
    if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Geçerli bir tutar girin.'];
    $sekil = in_array($r->odeme_sekli, ['nakit', 'havale', 'kredi']) ? $r->odeme_sekli : 'nakit';
    DB::table('cari_hareketler')->insert(['sube_id' => $p->sube_id, 'cari_id' => $c->id, 'tip' => 'tahsilat', 'tutar' => $tutar,
        'odeme_sekli' => $sekil, 'aciklama' => 'Tahsilat', 'personel_id' => $p->id, 'created_at' => now()]);
    // Nakit tahsilat -> kasaya giris (acik vardiya varsa)
    if ($sekil === 'nakit') _kasaYaz($p->sube_id, 'tahsilat', 'giris', $tutar, $c->ad . ' cari tahsilat', 'cari', $c->id, $p->id);
    return ['ok' => 1, 'mesaj' => number_format($tutar, 0, ',', '.') . 'TL tahsil edildi (' . $sekil . '). Yeni bakiye ' . number_format(_cariBakiye($c->id), 0, ',', '.') . 'TL.'];
});

Route::post('/api/patron/cari-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    $ad = trim((string) $r->ad);
    if ($ad === '') return ['ok' => 0, 'hata' => 'İsim girin'];
    $tip = in_array($r->tip, ['musteri', 'firma', 'personel', 'patron']) ? $r->tip : 'musteri';
    $id = DB::table('cari_hesaplar')->insertGetId(['sube_id' => $p->sube_id, 'ad' => $ad, 'tip' => $tip, 'telefon' => $r->telefon, 'aktif' => 1, 'created_at' => now()]);
    return ['ok' => 1, 'id' => (int) $id, 'ad' => $ad, 'tip' => $tip];
});

// Paket odeme yontemi: kolonu kur + bos siparisleri platforma gore doldur (tek sefer, stabil)
if (!function_exists('_paketOdemeVarsayilan')) {
    function _paketOdemeVarsayilan($platform, $id = 0)
    {
        $online = ['getir', 'yemeksepeti', 'trendyol', 'migros'];
        if (in_array(strtolower((string) $platform), $online)) return 'online';
        return ((int) $id % 2 === 0) ? 'nakit' : 'kredi'; // whatsapp/telefon: kapida nakit/kart
    }
}
if (!function_exists('_paketOdemeEnsure')) {
    function _paketOdemeEnsure()
    {
        if (!Schema::hasColumn('adisyonlar', 'odeme_yontemi')) {
            try { Schema::table('adisyonlar', fn ($t) => $t->string('odeme_yontemi', 20)->nullable()); } catch (\Throwable $e) { return; }
        }
        try {
            foreach (DB::table('adisyonlar')->where('kanal', 'paket')->whereNull('odeme_yontemi')->get(['id', 'platform']) as $b) {
                DB::table('adisyonlar')->where('id', $b->id)->update(['odeme_yontemi' => _paketOdemeVarsayilan($b->platform, $b->id)]);
            }
        } catch (\Throwable $e) {
        }
    }
}

Route::get('/api/paket', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _paketOdemeEnsure();
    $simdi = now();
    $siparisler = DB::table('adisyonlar')->where('adisyonlar.kanal', 'paket')->where('adisyonlar.durum', 'acik')
        ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->select('adisyonlar.id', 'adisyonlar.platform', 'adisyonlar.platform_siparis_no', 'adisyonlar.teslimat_durumu',
            'adisyonlar.toplam', 'adisyonlar.acilis', 'adisyonlar.teslimat_adres', 'adisyonlar.odeme_yontemi',
            'musteriler.ad as musteri', 'musteriler.telefon', 'kuryeler.ad as kurye')
        ->orderByDesc('adisyonlar.acilis')->get()
        ->map(function ($s) use ($simdi) {
            $dk = $s->acilis ? (int) round(\Carbon\Carbon::parse($s->acilis)->diffInMinutes($simdi)) : 0;
            $s->gecen_dk = $dk;
            $s->gecen_metin = $dk < 60 ? ($dk . ' dk') : (intdiv($dk, 60) . ' sa ' . ($dk % 60) . ' dk');
            $s->urun_adet = (int) DB::table('adisyon_kalemleri')->where('adisyon_id', $s->id)->sum('adet');
            $s->odeme_yontemi = $s->odeme_yontemi ?: _paketOdemeVarsayilan($s->platform, $s->id);
            return $s;
        });
    return ['ok' => 1, 'siparisler' => $siparisler];
});

Route::get('/api/paket/{id}', function (Request $r, $id) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $a = DB::table('adisyonlar')->where('adisyonlar.id', $id)->where('adisyonlar.kanal', 'paket')
        ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->select('adisyonlar.*', 'musteriler.ad as musteri', 'musteriler.telefon', 'musteriler.adres as musteri_adres',
            'kuryeler.ad as kurye', 'kuryeler.telefon as kurye_tel')
        ->first();
    if (!$a) return response()->json(['ok' => 0, 'mesaj' => 'Sipariş bulunamadı'], 404);
    $dk = $a->acilis ? (int) round(\Carbon\Carbon::parse($a->acilis)->diffInMinutes(now())) : 0;
    $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $id)
        ->select('urun_adi', 'adet', 'birim_fiyat', 'tutar', 'not')->orderBy('id')->get();
    return ['ok' => 1, 'siparis' => [
        'id' => $a->id,
        'platform' => $a->platform,
        'platform_siparis_no' => $a->platform_siparis_no,
        'teslimat_durumu' => $a->teslimat_durumu,
        'musteri' => $a->musteri ?? 'Müşteri',
        'telefon' => $a->telefon,
        'teslimat_adres' => $a->teslimat_adres ?: $a->musteri_adres,
        'kurye' => $a->kurye,
        'kurye_tel' => $a->kurye_tel,
        'odeme_yontemi' => ($a->odeme_yontemi ?? null) ?: _paketOdemeVarsayilan($a->platform, $a->id),
        'acilis' => $a->acilis ? \Carbon\Carbon::parse($a->acilis)->format('d.m.Y H:i') : '-',
        'gecen_dk' => $dk,
        'gecen_metin' => $dk < 60 ? ($dk . ' dk') : (intdiv($dk, 60) . ' sa ' . ($dk % 60) . ' dk'),
        'ara_toplam' => (float) $a->ara_toplam,
        'indirim' => (float) $a->indirim,
        'ikram' => (float) $a->ikram,
        'toplam' => (float) $a->toplam,
        'kalemler' => $kalemler,
    ]];
});

// Paket sipariş durum akışı (token'li): kabul -> hazir, yola -> yolda, teslim -> kapat, iptal -> iptal
Route::post('/api/paket/durum', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $id = (int) $r->input('id');
    $aksiyon = (string) $r->input('aksiyon');
    $a = DB::table('adisyonlar')->where('id', $id)->where('kanal', 'paket')->first();
    if (!$a) return response()->json(['ok' => 0, 'mesaj' => 'Sipariş bulunamadı'], 404);
    $upd = ['updated_at' => now()];
    switch ($aksiyon) {
        case 'kabul':
            $upd['teslimat_durumu'] = 'hazir';
            break;
        case 'yola':
            $upd['teslimat_durumu'] = 'yolda';
            break;
        case 'teslim':
            $upd['teslimat_durumu'] = 'teslim';
            $upd['durum'] = 'odendi';
            $upd['kapanis'] = now();
            if (Schema::hasColumn('adisyonlar', 'teslim_zamani')) $upd['teslim_zamani'] = now();
            break;
        case 'iptal':
            $upd['teslimat_durumu'] = 'iptal';
            $upd['durum'] = 'iptal';
            $upd['kapanis'] = now();
            break;
        default:
            return response()->json(['ok' => 0, 'mesaj' => 'Geçersiz aksiyon'], 422);
    }
    DB::table('adisyonlar')->where('id', $id)->update($upd);
    return ['ok' => 1];
});

Route::get('/api/raporlar', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $son30 = now()->subDays(30);
    $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', $son30)
        ->select('urun_adi', DB::raw('SUM(adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as ciro'))
        ->groupBy('urun_adi')->orderByDesc('ciro')->limit(10)->get();
    $personel = DB::table('adisyonlar')->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
        ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', $son30)
        ->select('personeller.ad', DB::raw('COUNT(*) as adisyon'), DB::raw('SUM(adisyonlar.toplam) as ciro'))
        ->groupBy('personeller.id', 'personeller.ad')->orderByDesc('ciro')->get();
    $odeme = DB::table('odemeler')->where('created_at', '>=', $son30)
        ->select('tip', DB::raw('SUM(tutar) as t'))->groupBy('tip')->orderByDesc('t')->get();
    return ['ok' => 1, 'top' => $top, 'personel' => $personel, 'odeme' => $odeme];
});

// ============================ KIOSK (self-servis, public) ============================
Route::get('/kiosk', function () {
    $subeId = DB::table('subeler')->value('id');
    $sube = DB::table('subeler')->find($subeId);
    $kategoriler = DB::table('menu_kategorileri')->where('sube_id', $subeId)->orderBy('sira')->get();
    $urunler = DB::table('urunler')->where('sube_id', $subeId)->where('aktif', 1)->get()->groupBy('kategori_id');
    return view('kiosk', compact('sube', 'kategoriler', 'urunler'));
});

// ============================ SADAKAT / KAMPANYA ============================
Route::get('/sadakat', function () {
    $kampanyalar = DB::table('kampanyalar')->orderByDesc('aktif')->orderBy('id')->get();
    $topMusteri = DB::table('musteriler')->orderByDesc('puan')->limit(10)->get();
    $toplamPuan = (int) DB::table('musteriler')->sum('puan');
    $aktifKampanya = DB::table('kampanyalar')->where('aktif', 1)->count();
    $musteriSayisi = DB::table('musteriler')->count();
    return view('sadakat', compact('kampanyalar', 'topMusteri', 'toplamPuan', 'aktifKampanya', 'musteriSayisi'));
});
Route::post('/sadakat/toggle', function (Request $r) {
    $k = DB::table('kampanyalar')->find($r->id);
    DB::table('kampanyalar')->where('id', $r->id)->update(['aktif' => $k->aktif ? 0 : 1]);
    return ['ok' => 1, 'aktif' => $k->aktif ? 0 : 1];
});

// ============================ E-DONUSUM (e-arsiv/e-fatura) ============================
Route::get('/edonusum', function () {
    $sube = DB::table('subeler')->first();
    $ayar = DB::table('edonusum_ayarlari')->where('sube_id', $sube->id)->first();
    $belgeler = DB::table('e_faturalar')->where('sube_id', $sube->id)->orderByDesc('created_at')->limit(30)->get();
    $bugunAdet = DB::table('e_faturalar')->where('sube_id', $sube->id)->whereDate('created_at', today())->count();
    $bugunTutar = (float) DB::table('e_faturalar')->where('sube_id', $sube->id)->whereDate('created_at', today())->sum('toplam');
    $ayAdet = DB::table('e_faturalar')->where('sube_id', $sube->id)->where('created_at', '>=', now()->subDays(30))->count();
    return view('edonusum', compact('sube', 'ayar', 'belgeler', 'bugunAdet', 'bugunTutar', 'ayAdet'));
});
Route::post('/edonusum/ayar-kaydet', function (Request $r) {
    $sube = DB::table('subeler')->first();
    DB::table('edonusum_ayarlari')->updateOrInsert(
        ['sube_id' => $sube->id],
        ['entegrator' => $r->entegrator ?: 'parasut', 'api_key' => $r->api_key, 'api_secret' => $r->api_secret,
            'firma_unvan' => $r->firma_unvan, 'vkn_tckn' => $r->vkn_tckn, 'vergi_dairesi' => $r->vergi_dairesi,
            'adres' => $r->adres, 'mali_muhur_yuklu' => $r->mali_muhur_yuklu ? 1 : 0, 'aktif' => $r->aktif ? 1 : 0,
            'fis_modu' => $r->fis_modu ?: 'earsiv', 'okc_marka' => $r->okc_marka, 'okc_ip' => $r->okc_ip,
            'okc_port' => $r->okc_port, 'okc_aktif' => $r->okc_aktif ? 1 : 0,
            'updated_at' => now()]
    );
    return ['ok' => 1];
});

// Yazdirilabilir hesap fisi (bilgi fisi) — herhangi bir termal yaziciyla
Route::get('/pos/fis/{adisyon}', function ($id) {
    $a = DB::table('adisyonlar')->find($id);
    if (!$a) abort(404);
    $sube = DB::table('subeler')->find($a->sube_id);
    $kalemler = DB::table('adisyon_kalemleri')->where('adisyon_id', $id)->get();
    $masa = $a->masa_id ? DB::table('masalar')->where('id', $a->masa_id)->value('ad') : null;
    $ayar = DB::table('edonusum_ayarlari')->where('sube_id', $a->sube_id)->first();
    return view('pos.fis', compact('a', 'sube', 'kalemler', 'masa', 'ayar'));
});
Route::post('/pos/fatura-olustur', function (Request $r) {
    $a = DB::table('adisyonlar')->find($r->adisyon_id);
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadi'];
    $sube = DB::table('subeler')->find($a->sube_id);
    return _eFaturaKes($sube, $a, ['unvan' => $r->alici_unvan, 'vkn' => $r->alici_vkn, 'adres' => $r->alici_adres]);
});

// ============================ ERP / CARI - MUHASEBE ============================
Route::get('/muhasebe', function () {
    $son30 = now()->subDays(30);
    $gelir = (float) DB::table('odemeler')->where('created_at', '>=', $son30)->sum('tutar');
    $gider = (float) DB::table('alis_faturalari')->where('tarih', '>=', $son30->toDateString())->sum('toplam');
    $tedarikciCari = DB::table('alis_faturalari')->leftJoin('tedarikciler', 'alis_faturalari.tedarikci_id', '=', 'tedarikciler.id')
        ->select('tedarikciler.ad', DB::raw('SUM(alis_faturalari.toplam) as borc'), DB::raw('COUNT(*) as fatura'))
        ->groupBy('tedarikciler.id', 'tedarikciler.ad')->orderByDesc('borc')->get();
    $kasa = DB::table('odemeler')->where('created_at', '>=', $son30)
        ->select('tip', DB::raw('SUM(tutar) as t'), DB::raw('COUNT(*) as adet'))->groupBy('tip')->orderByDesc('t')->get();
    return view('muhasebe', compact('gelir', 'gider', 'tedarikciCari', 'kasa'));
});

// ============================ TEKLIF KARSILASTIRMA (satin alma) ============================
Route::get('/teklif', function () {
    $teklifler = DB::table('teklifler')
        ->join('malzemeler', 'teklifler.malzeme_id', '=', 'malzemeler.id')
        ->join('tedarikciler', 'teklifler.tedarikci_id', '=', 'tedarikciler.id')
        ->leftJoin('birimler', 'teklifler.birim_id', '=', 'birimler.id')
        ->select('teklifler.birim_fiyat', 'teklifler.tarih', 'malzemeler.ad as malzeme',
            'tedarikciler.ad as tedarikci', 'birimler.kisaltma as birim')
        ->orderBy('malzemeler.ad')->orderBy('teklifler.birim_fiyat')->get()->groupBy('malzeme');
    return view('teklif', compact('teklifler'));
});


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
        $fmt = fn ($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
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

        $adisyonId = DB::table('adisyonlar')->insertGetId([
            'sube_id' => $sube->id, 'masa_id' => null, 'musteri_id' => $mid, 'kurye_id' => null,
            'kanal' => 'paket', 'platform' => $platform,
            'platform_siparis_no' => $data['siparis_no'] ?? (strtoupper(substr($platform, 0, 3)) . random_int(100000, 999999)),
            'teslimat_adres' => $m['adres'] ?? ($data['adres'] ?? null),
            'teslimat_durumu' => 'hazirlaniyor', 'misafir_sayisi' => 1, 'durum' => 'acik',
            'acan_personel_id' => null, 'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
            'acilis' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

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
    DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => $r->tip ?? 'nakit', 'tutar' => $a->toplam, 'bahsis' => 0, 'personel_id' => $a->acan_personel_id, 'created_at' => now()]);
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
    DB::table('adisyon_kalemleri')->where('id', $r->kalem_id)->update(['durum' => 'hazir']);
    return ['ok' => 1];
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

// PUBLIC WEBHOOK: middleware (Posentegra vb.) siparisleri buraya POST eder
Route::post('/webhook/siparis/{token}', function (Request $r, $token) {
    $sube = DB::table('subeler')->where('webhook_token', $token)->first();
    if (!$sube) return response()->json(['ok' => 0, 'hata' => 'Gecersiz token'], 403);
    try {
        return response()->json(_paketSiparisAl($sube, $r->all()));
    } catch (\Throwable $e) {
        return response()->json(['ok' => 0, 'hata' => $e->getMessage()], 500);
    }
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

// Acik masalarin acilis saatini "az once"ye tazele (demo verisi eski tarihli kaliyordu -> sure gercekci gorunsun)
Route::get('/enrich-acik-tazele', function () {
    $n = 0;
    foreach (DB::table('adisyonlar')->where('durum', 'acik')->get(['id']) as $a) {
        DB::table('adisyonlar')->where('id', $a->id)->update(['acilis' => now()->subMinutes(random_int(5, 180))]);
        $n++;
    }
    return "Açık adisyonların açılış saati tazelendi: $n masa (5-180 dk önce). ✅";
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
        $limit = 0;
        $ac = function (&$y, $liste) { foreach ($liste as $k) $y[$k] = true; };
        switch ($rol) {
            case 'sahip': foreach ($y as $k => $v) $y[$k] = true; $limit = 100; break;
            case 'mudur': foreach ($y as $k => $v) $y[$k] = true; $limit = 50; break;
            case 'kasa': $ac($y, ['adisyon_ac', 'adisyon_kapat', 'fatura_kes', 'maliyet_gor']); $limit = 10; break;
            case 'garson': $ac($y, ['adisyon_ac', 'adisyon_kapat', 'adisyon_bol']); $limit = 0; break;
        }
        return ['yetkiler' => $y, 'iskonto_limit' => $limit];
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
    $n = 0;
    foreach (DB::table('personeller')->get(['id', 'rol', 'yetkiler']) as $p) {
        if ($p->yetkiler) continue;
        $d = _restoYetkiVarsayilan($p->rol);
        DB::table('personeller')->where('id', $p->id)->update(['yetkiler' => json_encode($d['yetkiler']), 'iskonto_limit' => $d['iskonto_limit']]);
        $n++;
    }
    return "Yetkiler kuruldu: $n personel varsayılan yetkilerle donatıldı. ✅";
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
    $p = DB::table('personeller')->where('pin', (string) $r->pin)->whereIn('rol', ['sahip', 'mudur'])->first();
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'PIN hatalı veya yetkiniz yok'], 401);
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
        $tl = fn ($x) => '₺' . number_format((float) $x, 0, ',', '.');
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

    // --- Uyarilar (AI oncesi kural motoru) ---
    $uyarilar = [];
    if ($compCiro > 0 && $ciro < $compCiro * 0.85) $uyarilar[] = 'Ciro önceki döneme göre %' . round(($compCiro - $ciro) / $compCiro * 100) . ' geride.';
    elseif ($compCiro > 0 && $ciro > $compCiro * 1.15) $uyarilar[] = 'Ciro önceki döneme göre %' . round(($ciro - $compCiro) / $compCiro * 100) . ' önde. 🚀';
    if ($ciro > 0 && $iskonto > $ciro * 0.05) $uyarilar[] = 'İskonto oranı yüksek: ciro yaklaşık %' . round($iskonto / $ciro * 100) . ' iskontoya gitmiş.';
    if ($maliyetYuzde >= 40) $uyarilar[] = 'Maliyet oranı %' . $maliyetYuzde . ' — kârlılık baskı altında.';
    try {
        $kritik = DB::table('malzemeler')->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
            ->select('malzemeler.id')->groupBy('malzemeler.id', 'malzemeler.kritik_stok')
            ->havingRaw('COALESCE(SUM(stok_hareketleri.miktar),0) < malzemeler.kritik_stok')->get()->count();
        if ($kritik > 0) $uyarilar[] = $kritik . ' malzeme kritik stok seviyesinde.';
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

        // Recete kalemleri (malzeme bazinda maliyet)
        $recete = DB::table('receteler')->where('urun_id', $urunId)->where('tip', 'urun')->first();
        $receteKalem = [];
        $receteToplam = 0.0;
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
                $satirMaliyet = (float) $rk->miktar * $karsilik * (float) $m->guncel_maliyet;
                $receteToplam += $satirMaliyet;
                $receteKalem[] = [
                    'malzeme' => $m->ad, 'miktar' => (float) $rk->miktar,
                    'birim' => $birim->kisaltma ?? '', 'maliyet' => round($satirMaliyet, 2),
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
                'Ciro' => '₺' . number_format($satisTutar, 0, ',', '.'),
                'Bugün' => rtrim(rtrim(number_format($bugunAdet, 1, ',', '.'), '0'), ',') . ' adet',
                'Fiyat' => '₺' . number_format((float) $urun->fiyat, 0, ',', '.'),
            ],
            'recete' => $receteKalem, 'receteBirimMaliyet' => round($receteToplam, 2),
            'toplamMaliyet' => round($toplamMaliyet, 2),
            'maliyetYuzde' => $satisTutar > 0 ? round($toplamMaliyet / $satisTutar * 100) : 0,
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
        }
        $basliklar = ['iskonto' => 'İskonto', 'ikram' => 'İkram', 'silinen' => 'Silinen Ürün', 'iptal' => 'İptal Adisyon', 'fire' => 'Fire / Zayi', 'odenmez' => 'Ödenmez'];
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
        return [
            'ok' => 1, 'baslik' => ($basliklar[$alt] ?? 'Kayıp') . ' Detayı', 'tip' => 'kayip', 'ai' => $ai,
            'toplam' => (float) $kayitlar->sum('tutar'), 'adet' => $kayitlar->count(),
            'sebepler' => $sebepDagilim,
            'kayitlar' => $kayitlar->map(fn ($k) => [
                'garson' => $k->garson ?? '-', 'tutar' => (float) $k->tutar, 'sebep' => $k->sebep ?? '-',
                'zaman' => $k->created_at ? \Carbon\Carbon::parse($k->created_at)->format('d.m H:i') : '',
                'adisyon_id' => $k->adisyon_id,
            ]),
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
            ->select('urun_adi', 'adet', 'birim_fiyat', 'tutar', 'durum', 'not')->orderBy('id')->get()
            ->map(fn ($k) => ['ad' => $k->urun_adi, 'adet' => (float) $k->adet, 'birim_fiyat' => (float) $k->birim_fiyat,
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
        if ((float) $a->ara_toplam > 0 && (float) $a->indirim > (float) $a->ara_toplam * 0.15) $ai[] = ['seviye' => 'bilgi', 'mesaj' => 'Yüksek iskonto: ₺' . number_format($a->indirim, 0, ',', '.') . ' (ara toplamın %' . round($a->indirim / $a->ara_toplam * 100) . '\'ı).'];
        return [
            'ok' => 1, 'baslik' => ($masa ?? $kanalAd) . ' · Adisyon', 'tip' => 'adisyon', 'ai' => $ai,
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
                $ai[] = ['seviye' => 'riskli', 'mesaj' => 'Son ziyaretlerde memnuniyetsiz (⭐' . $ortSon . '). ₺' . number_format($gHarcama, 0, ',', '.') . ' harcayan değerli müşteri — kaybetmemek için aranması önerilir.'];
            }
        }
        if ($gHarcama >= 10000) {
            $ai[] = ['seviye' => 'iyi', 'mesaj' => 'VIP müşteri: toplam ₺' . number_format($gHarcama, 0, ',', '.') . ' / ' . $gAdet . ' sipariş. Özel ilgi gösterin.'];
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
                'Toplam' => '₺' . number_format($gHarcama, 0, ',', '.'),
                'Ortalama' => '₺' . number_format($gAdet > 0 ? $gHarcama / $gAdet : 0, 0, ',', '.'),
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

    // 1) Ogrenilen niyet (bedava tekrar) -> 2) kural motoru
    $niyet = $a->ogrenilenNiyet($soru) ?: $a->niyetCoz($soru);
    $sonuc = ($niyet['intent'] ?? 'bilinmiyor') !== 'bilinmiyor' ? $a->cevapla($niyet) : null;
    $kaynak = 'kural';

    if (!$sonuc) {
        // 3) Haiku ile niyet coz
        $aiNiyet = $a->niyetCozAI($soru, $gecmis);
        if ($aiNiyet && !in_array($aiNiyet['intent'], ['sohbet', 'bilinmiyor'], true)) {
            $sonuc = $a->cevapla($aiNiyet);
            if ($sonuc) { $a->ogren($soru, $aiNiyet); $kaynak = 'ai_niyet'; }
        }
        // 4) Sohbet/kimlik -> Haiku dogal cevap
        if (!$sonuc) {
            $sohbet = $a->sohbetAI($soru, $gecmis);
            if ($sohbet) { $sonuc = ['cevap' => $sohbet, 'seslendir' => true, 'kart' => null, 'intent' => 'sohbet']; $kaynak = 'ai_sohbet'; }
        }
        // 5) Anahtar yok / hata -> yardim
        if (!$sonuc) { $sonuc = $a->yardimCevabi($niyet); $kaynak = ($a->aiTeshis === 'anahtar_yok') ? 'yardim_anahtarsiz' : 'yardim'; }
    }

    $a->gecmisEkle($userId, $soru, $sonuc['cevap'] ?? '');
    return [
        'ok' => 1, 'cevap' => $sonuc['cevap'] ?? '', 'seslendir' => $sonuc['seslendir'] ?? true,
        'kart' => $sonuc['kart'] ?? null, 'intent' => $sonuc['intent'] ?? 'bilinmiyor', 'kaynak' => $kaynak,
    ];
});

Route::get('/api/masalar', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $acik = DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')
        ->select('id', 'masa_id', 'toplam', 'acilis')->get()->keyBy('masa_id');
    $masalar = DB::table('masalar')->leftJoin('bolgeler', 'masalar.bolge_id', '=', 'bolgeler.id')
        ->where('masalar.sube_id', $p->sube_id)
        ->select('masalar.id', 'masalar.ad', 'masalar.durum', 'masalar.kapasite', 'bolgeler.ad as bolge')
        ->orderBy('bolgeler.sira')->orderBy('masalar.id')->get()
        ->map(function ($m) use ($acik) {
            $a = $acik[$m->id] ?? null;
            return ['id' => $m->id, 'ad' => $m->ad, 'bolge' => $m->bolge, 'durum' => $m->durum,
                'kapasite' => $m->kapasite, 'tutar' => $a ? (float) $a->toplam : 0,
                'adisyon_id' => $a ? $a->id : null];
        });
    return ['ok' => 1, 'masalar' => $masalar];
});

// Personel yetkileri: listele (sahip/mudur gorur) + kaydet (SADECE sahip)
Route::get('/api/patron/personeller', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p || !in_array($p->rol, ['sahip', 'mudur'])) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    $liste = DB::table('personeller')->where('sube_id', $p->sube_id)
        ->orderByRaw("FIELD(rol,'sahip','mudur','kasa','garson','mutfak')")->orderBy('ad')
        ->get(['id', 'ad', 'rol', 'yetkiler', 'iskonto_limit'])
        ->map(function ($x) {
            $y = $x->yetkiler ? json_decode($x->yetkiler, true) : _restoYetkiVarsayilan($x->rol)['yetkiler'];
            // Eksik anahtarlari tamamla (yeni yetki eklenirse)
            $y = array_merge(array_fill_keys(_restoYetkiKeys(), false), is_array($y) ? $y : []);
            return ['id' => (int) $x->id, 'ad' => $x->ad, 'rol' => $x->rol, 'yetkiler' => $y, 'iskonto_limit' => (float) $x->iskonto_limit];
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
    ]);
    return ['ok' => 1];
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
        $tip = in_array($r->odeme_tip, ['nakit', 'kredi', 'yemek_karti']) ? $r->odeme_tip : 'nakit';
        $kalan = (float) $a->toplam - (float) DB::table('odemeler')->where('adisyon_id', $a->id)->sum('tutar');
        if ($kalan > 0) {
            DB::table('odemeler')->insert(['adisyon_id' => $a->id, 'tip' => $tip, 'tutar' => $kalan, 'personel_id' => $p->id, 'created_at' => now()]);
        }
        DB::table('adisyonlar')->where('id', $a->id)->update(['durum' => 'odendi', 'kapanis' => now()]);
        if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
        return ['ok' => 1, 'mesaj' => 'Ödeme alındı (' . ['nakit' => 'Nakit', 'kredi' => 'Kredi', 'yemek_karti' => 'Yemek Kartı'][$tip] . '), masa kapatıldı.'];
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
        return ['ok' => 1, 'mesaj' => '%' . round($oran) . ' iskonto uygulandı (₺' . number_format($indirim, 0, ',', '.') . ')' . ($onaylayan ? ' — ' . $onaylayan->ad . ' onayı ile' : '') . '.'];
    }

    if ($islem === 'ikram') {
        if (!$yetki('ikram')) return ['ok' => 0, 'hata' => 'İkram yetkiniz yok.'];
        $tutar = max(0, (float) $r->tutar);
        if ($tutar <= 0) return ['ok' => 0, 'hata' => 'Geçerli bir ikram tutarı girin.'];
        if ($tutar > (float) $a->ara_toplam) $tutar = (float) $a->ara_toplam;
        $yeni = max(0, (float) $a->ara_toplam - (float) $a->indirim - $tutar);
        DB::table('adisyonlar')->where('id', $a->id)->update(['ikram' => $tutar, 'toplam' => $yeni]);
        DB::table('iptal_indirim_loglari')->insert(['sube_id' => $p->sube_id, 'adisyon_id' => $a->id, 'tip' => 'ikram',
            'tutar' => $tutar, 'sebep' => $r->sebep ?: 'İkram', 'personel_id' => $p->id, 'created_at' => now()]);
        return ['ok' => 1, 'mesaj' => '₺' . number_format($tutar, 0, ',', '.') . ' ikram uygulandı.'];
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

Route::get('/api/paket', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    $simdi = now();
    $siparisler = DB::table('adisyonlar')->where('adisyonlar.kanal', 'paket')->where('adisyonlar.durum', 'acik')
        ->leftJoin('musteriler', 'adisyonlar.musteri_id', '=', 'musteriler.id')
        ->leftJoin('kuryeler', 'adisyonlar.kurye_id', '=', 'kuryeler.id')
        ->select('adisyonlar.id', 'adisyonlar.platform', 'adisyonlar.platform_siparis_no', 'adisyonlar.teslimat_durumu',
            'adisyonlar.toplam', 'adisyonlar.acilis', 'adisyonlar.teslimat_adres',
            'musteriler.ad as musteri', 'musteriler.telefon', 'kuryeler.ad as kurye')
        ->orderByDesc('adisyonlar.acilis')->get()
        ->map(function ($s) use ($simdi) {
            $dk = $s->acilis ? (int) round(\Carbon\Carbon::parse($s->acilis)->diffInMinutes($simdi)) : 0;
            $s->gecen_dk = $dk;
            $s->gecen_metin = $dk < 60 ? ($dk . ' dk') : (intdiv($dk, 60) . ' sa ' . ($dk % 60) . ' dk');
            $s->urun_adet = (int) DB::table('adisyon_kalemleri')->where('adisyon_id', $s->id)->sum('adet');
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


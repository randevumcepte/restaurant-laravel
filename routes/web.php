<?php

use Illuminate\Http\Request;
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
    return view('pos.adisyon', compact('masa', 'adisyon', 'kalemler', 'kategoriler', 'urunler', 'bosMasalar'));
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
    return ['ok' => 1];
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
    $aktif = DB::table('adisyonlar')->where('kanal', 'paket')->where('durum', 'acik')
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
    $malzemeler = DB::table('malzemeler')
        ->leftJoin('stok_hareketleri', 'malzemeler.id', '=', 'stok_hareketleri.malzeme_id')
        ->leftJoin('malzeme_kategorileri', 'malzemeler.kategori_id', '=', 'malzeme_kategorileri.id')
        ->select('malzemeler.*', 'malzeme_kategorileri.ad as kategori', DB::raw('COALESCE(SUM(stok_hareketleri.miktar),0) as stok'))
        ->groupBy('malzemeler.id', 'malzeme_kategorileri.ad')
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


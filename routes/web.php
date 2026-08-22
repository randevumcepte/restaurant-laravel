<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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

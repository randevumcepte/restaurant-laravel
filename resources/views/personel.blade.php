@extends('layout.app')
@section('rk_ikon', 'badge')
@section('baslik', 'Personel & Maaş')
@section('title', 'Personel & Maaş')

@php
// ------------------------------------------------------------------
// Flutter personel_screen.dart BİREBİR.
// Rol renk / etiket (Flutter _rolAd / _rolRenk ile aynı).
// ------------------------------------------------------------------
$rolAd = ['sahip' => 'Sahip', 'mudur' => 'Müdür', 'satinalmaci' => 'Satın Almacı', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak'];
$rolRenk = [
    'sahip'  => 'var(--mor1)',
    'mudur'  => 'var(--mavi)',
    'satinalmaci' => '#0D9488',
    'kasa'   => 'var(--amber)',
    'garson' => 'var(--yesil)',
    'mutfak' => '#D97706',
];
$renkAl = fn ($rol) => $rolRenk[$rol] ?? '#64748B';

// Yetki etiketleri — Flutter _etiketler ile birebir.
$etiketler = [
    'adisyon_ac'       => 'Adisyon Aç',
    'adisyon_kapat'    => 'Ödeme Al / Masa Kapat',
    'adisyon_iptal'    => 'Adisyon İptal',
    'adisyon_bol'      => 'Adisyon Böl',
    'adisyon_birlestir'=> 'Masa Birleştir',
    'masa_tasima'      => 'Masa Taşıma',
    'iskonto'          => 'İskonto Uygula',
    'ikram'            => 'İkram',
    'urun_sil'         => 'Ürün Sil (Void)',
    'fatura_kes'       => 'Fatura Kes',
    'geri_islem'       => 'Geriye Dönük İşlem',
    'maliyet_gor'      => 'Maliyet Görebilir',
    'rapor_gor'        => 'Rapor Görebilir',
];
$gruplar = [
    'Adisyon'      => ['ikon' => 'receipt_long', 'keys' => ['adisyon_ac', 'adisyon_kapat', 'adisyon_iptal', 'adisyon_bol', 'adisyon_birlestir', 'masa_tasima']],
    'Para & Kayıp' => ['ikon' => 'payments',     'keys' => ['iskonto', 'ikram', 'urun_sil', 'fatura_kes']],
    'Yönetim'      => ['ikon' => 'insights',      'keys' => ['geri_islem', 'maliyet_gor', 'rapor_gor']],
];

$personeller    = $personeller ?? [];
$duzenleyebilir = $duzenleyebilir ?? false;

// Flutter masaüstü: role göre grupla, sabit sırayla göster.
$rolGrup = [
    'sahip' => 'Yöneticiler', 'mudur' => 'Yöneticiler', 'satinalmaci' => 'Yöneticiler',
    'garson' => 'Garsonlar',
    'mutfak' => 'Mutfak / Ustalar', 'usta' => 'Mutfak / Ustalar',
    'kasa' => 'Kasiyerler',
];
$grupSira = ['Yöneticiler', 'Garsonlar', 'Mutfak / Ustalar', 'Kasiyerler', 'Diğer'];
$grupRenk = [
    'Yöneticiler' => 'var(--mor1)', 'Garsonlar' => 'var(--yesil)',
    'Mutfak / Ustalar' => '#D97706', 'Kasiyerler' => 'var(--mavi)', 'Diğer' => '#64748B',
];

$siraliGruplar = [];
foreach ($personeller as $p) {
    $g = $rolGrup[$p['rol'] ?? 'garson'] ?? 'Diğer';
    $siraliGruplar[$g][] = $p;
}
foreach ($siraliGruplar as &$liste) {
    usort($liste, fn ($a, $b) => (int)($b['aktif'] ?? 0) <=> (int)($a['aktif'] ?? 0));
}
unset($liste);

// =====================================================================
// MAAŞ / PRİM / HAREKET verisini blade PHP ile doğrudan hesapla
// (Flutter _restoPersonelHesap + _restoAyAralik karşılığı)
// =====================================================================
$ayParam  = request('ay');   // YYYY-MM veya boş
$aylar    = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$simdi    = now();
$ayYil    = $simdi->year;
$ayAy     = $simdi->month;
if ($ayParam && preg_match('/^(\d{4})-(\d{2})$/', $ayParam, $m)) {
    $ayYil = (int) $m[1];
    $ayAy  = (int) $m[2];
    // Gelecek aya geçiş engeli
    if (mktime(0,0,0,$ayAy,1,$ayYil) > mktime(0,0,0,$simdi->month,1,$simdi->year)) {
        $ayYil = $simdi->year; $ayAy = $simdi->month;
    }
}
$ayMetin = ($aylar[$ayAy] ?? $ayAy) . ' ' . $ayYil;
$ayKey   = $ayYil . '-' . str_pad($ayAy, 2, '0', STR_PAD_LEFT);

$oncekiAy  = \Carbon\Carbon::create($ayYil, $ayAy, 1)->subMonth();
$sonrakiAy = \Carbon\Carbon::create($ayYil, $ayAy, 1)->addMonth();
$gelecekMi = $sonrakiAy->greaterThan(\Carbon\Carbon::now()->startOfMonth());
$oncekiKey = $oncekiAy->format('Y-m');
$sonrakiKey = $sonrakiAy->format('Y-m');

$sid = _webSubeId();
$bas = "$ayKey-01 00:00:00";
$bit = \Carbon\Carbon::create($ayYil, $ayAy, 1)->endOfMonth()->format('Y-m-d 23:59:59');

// Maaş hesabı yapmak için `maas`, `maas_tipi`, `prim_tipi`, `prim_oran` kolonlarını getir.
// _restoPersonelKolonEnsure() çağrılmış (route bunu yapıyor), güvenle sorgulayabiliriz.
$maasMap = [];
try {
    $maasListe = \Illuminate\Support\Facades\DB::table('personeller')
        ->where('sube_id', $sid)
        ->get(['id','ad','rol','aktif','maas','maas_tipi','prim_tipi','prim_oran','iban','pin','telefon']);
    foreach ($maasListe as $per) {
        $maasMap[(int)$per->id] = $per;
    }
} catch (\Throwable $e) {}

// Hareket tablosu varsa aylık toplamları çek.
$hareketToplamMap = [];
try {
    $harRows = \Illuminate\Support\Facades\DB::table('personel_hareketleri')
        ->where('sube_id', $sid)
        ->whereBetween('tarih', [substr($bas,0,10), substr($bit,0,10)])
        ->selectRaw('personel_id, tur, SUM(tutar) t')
        ->groupBy('personel_id', 'tur')
        ->get();
    foreach ($harRows as $hr) {
        $hareketToplamMap[(int)$hr->personel_id][$hr->tur] = (float)$hr->t;
    }
} catch (\Throwable $e) {}

// Ciro prim hesabı
$ciroMap = [];
try {
    $ciroRows = \Illuminate\Support\Facades\DB::table('adisyonlar')
        ->where('sube_id', $sid)
        ->where('durum', 'odendi')
        ->whereBetween('kapanis', [$bas, $bit])
        ->whereNotNull('acan_personel_id')
        ->selectRaw('acan_personel_id, SUM(toplam) c')
        ->groupBy('acan_personel_id')
        ->pluck('c', 'acan_personel_id');
    foreach ($ciroRows as $pid => $c) $ciroMap[(int)$pid] = (float)$c;
} catch (\Throwable $e) {}

// Her personel için hesap objesi oluştur.
$hesapMap = [];
$toplamMaas = 0; $toplamPrim = 0; $toplamOdenen = 0; $toplamNet = 0;
foreach ($maasMap as $pid => $per) {
    $maas = (float)($per->maas ?? 0);
    $ciro = 0.0; $primHesap = 0.0;
    if (($per->prim_tipi ?? 'yok') === 'ciro' && (float)($per->prim_oran ?? 0) > 0) {
        $ciro = $ciroMap[$pid] ?? 0.0;
        $primHesap = round($ciro * (float)$per->prim_oran / 100, 2);
    }
    $har = $hareketToplamMap[$pid] ?? [];
    $avans     = (float)($har['avans'] ?? 0);
    $odenen    = (float)($har['odeme'] ?? 0);
    $primManuel= (float)($har['prim'] ?? 0);
    $kesinti   = (float)($har['kesinti'] ?? 0);
    $ekOdeme   = (float)($har['ek_odeme'] ?? 0);
    $hakedis   = round($maas + $primHesap + $primManuel + $ekOdeme - $kesinti, 2);
    $net       = round($hakedis - $avans - $odenen, 2);
    $hesapMap[$pid] = [
        'maas' => $maas, 'maas_tipi' => $per->maas_tipi ?? 'aylik',
        'prim_tipi' => $per->prim_tipi ?? 'yok', 'prim_oran' => (float)($per->prim_oran ?? 0),
        'ciro' => round($ciro,2), 'prim_hesap' => $primHesap, 'prim_manuel' => $primManuel,
        'avans' => $avans, 'kesinti' => $kesinti, 'ek_odeme' => $ekOdeme,
        'odenen' => $odenen, 'hakedis' => $hakedis, 'net' => $net,
        'iban' => $per->iban ?? null, 'telefon' => $per->telefon ?? null,
    ];
    if ((int)($per->aktif ?? 0) === 1) {
        $toplamMaas   += $maas;
        $toplamPrim   += $primHesap + $primManuel;
        $toplamOdenen += $odenen;
        $toplamNet    += $net;
    }
}
$ozet = ['maas' => round($toplamMaas,2), 'prim' => round($toplamPrim,2), 'odenen' => round($toplamOdenen,2), 'net_kalan' => round($toplamNet,2)];

// Hareket defteri (detay paneli için — JSON'a aktarılacak)
$hareketDetayMap = [];
try {
    $harDetayRows = \Illuminate\Support\Facades\DB::table('personel_hareketleri')
        ->where('sube_id', $sid)
        ->whereBetween('tarih', [substr($bas,0,10), substr($bit,0,10)])
        ->orderByDesc('tarih')->orderByDesc('id')
        ->get(['id','personel_id','tur','tutar','aciklama','tarih']);
    foreach ($harDetayRows as $hr) {
        $hareketDetayMap[(int)$hr->personel_id][] = [
            'id' => (int)$hr->id, 'tur' => $hr->tur,
            'tutar' => (float)$hr->tutar, 'aciklama' => $hr->aciklama, 'tarih' => $hr->tarih,
        ];
    }
} catch (\Throwable $e) {}

// Maaş tipi etiketi (Flutter _maasTipiAd)
$maasTipiAd = ['aylik' => 'ay', 'gunluk' => 'gün', 'saatlik' => 'saat'];
$tl = fn ($v) => number_format((float)$v, 0, ',', '.') . ' TL';

$aktifSayi = count(array_filter($personeller, fn ($p) => (int)($p['aktif'] ?? 0) === 1));

// Hareket API çağrısı için POS session'dan api_token'ı JS'e aktar (varsa).
// Bu sayede hareket kaydet/sil butonları web panelinden de çalışır.
$webApiToken = null;
try {
    $posPer = _posPersonel();
    if ($posPer && ($posPer->rol ?? '') === 'sahip') {
        $webApiToken = $posPer->api_token ?? null;
    }
} catch (\Throwable $e) {}
@endphp

@push('head')
<style>
/* ===== PERSONEL SAYFASI — Flutter personel_screen.dart BİREBİR ===== */
#pnl{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--card:#fff;--card2:#f1f5f9;--ink:#1e293b}
body.koyu #pnl{--line:#2c3650;--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b}

/* ── Ay Seçici ── */
#pnl .ay-bar{display:flex;align-items:center;justify-content:center;gap:4px;padding:6px 0 10px}
#pnl .ay-btn{width:34px;height:34px;border:none;background:none;cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--mor1);transition:background .15s}
#pnl .ay-btn:hover{background:color-mix(in srgb,var(--mor1) 12%,transparent)}
#pnl .ay-btn.dis{opacity:.3;pointer-events:none}
#pnl .ay-metin{font-weight:700;font-size:15px;color:var(--ink);min-width:120px;text-align:center}

/* ── Özet kart (Flutter _ozetKart gradient) ── */
#pnl .ozet-kart{background:linear-gradient(135deg,var(--mor1),var(--mavi));border-radius:18px;padding:18px 20px;margin-bottom:14px;color:#fff}
#pnl .ozet-kart .bas{font-size:12px;color:rgba(255,255,255,.7);margin-bottom:2px}
#pnl .ozet-kart .toplam{font-size:28px;font-weight:800;margin-bottom:12px}
#pnl .ozet-kart .mini-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
#pnl .ozet-kart .mini{display:flex;flex-direction:column;gap:2px}
#pnl .ozet-kart .mini .v{font-size:14px;font-weight:700;word-break:break-all}
#pnl .ozet-kart .mini .l{font-size:10px;color:rgba(255,255,255,.6)}

/* ── Grup başlık (Flutter MBolumBaslik) ── */
#pnl .grp-bas{display:flex;align-items:center;gap:8px;margin:14px 0 6px}
#pnl .grp-bas .cubuk{width:4px;height:18px;border-radius:2px;flex:none}
#pnl .grp-bas .ad{font-weight:800;font-size:14px;color:var(--ink)}
#pnl .grp-bas .sayi{font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px}

/* ── Personel kart (Flutter _masaustuKart) ── */
#pnl .p-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;cursor:pointer;transition:box-shadow .15s,border-color .15s}
#pnl .p-kart:hover{box-shadow:0 4px 18px rgba(0,0,0,.08);border-color:color-mix(in srgb,var(--mor1) 35%,transparent)}
#pnl .p-kart.pasif{opacity:.55}
#pnl .avatar{width:44px;height:44px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:19px}
#pnl .p-kart .p-meta{min-width:0;flex:1}
#pnl .p-kart .p-ad{font-weight:800;font-size:15px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#pnl .p-kart .p-alt{display:flex;align-items:center;gap:7px;margin-top:4px;flex-wrap:wrap}
#pnl .p-kart .p-maas{font-size:12px;color:var(--sub)}
#pnl .p-kart .p-prim{font-size:12px;color:var(--yesil)}
#pnl .p-kart .p-net{text-align:right;flex:none}
#pnl .p-kart .p-net .v{font-weight:800;font-size:16px}
#pnl .p-kart .p-net .l{font-size:10px;color:var(--sub);text-align:right}

/* ── Rozet ── */
#pnl .rozet{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700;border:1px solid;white-space:nowrap}

/* ── Grid ── */
#pnl .p-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:12px;margin-bottom:6px}

/* =====================================================================
   DETAY PANEL (Flutter PersonelDetayScreen — sağdan kayar)
   ===================================================================== */
#detayOverlay{position:fixed;inset:0;z-index:40;background:rgba(15,23,42,.45);display:none;transition:opacity .22s}
#detayOverlay.acik{display:flex;justify-content:flex-end}
#detayPanel{position:relative;width:100%;max-width:500px;height:100%;background:var(--card);overflow-y:auto;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .22s cubic-bezier(.4,0,.2,1)}
#detayOverlay.acik #detayPanel{transform:translateX(0)}
#detayPanel .det-head{display:flex;align-items:center;gap:10px;padding:16px 18px;border-bottom:1px solid var(--line);position:sticky;top:0;background:var(--card);z-index:2}
#detayPanel .det-head .det-ava{width:40px;height:40px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:17px}
#detayPanel .det-head .det-ad{flex:1;min-width:0}
#detayPanel .det-head .det-ad .ad{font-weight:800;font-size:16px;color:var(--ink)}
#detayPanel .det-head .det-ad .alt{margin-top:2px;display:flex;gap:6px;flex-wrap:wrap}
#detayPanel .det-kapa{width:34px;height:34px;border:none;background:none;cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--sub);font-size:18px;transition:background .15s}
#detayPanel .det-kapa:hover{background:var(--card2)}
#detayPanel .det-govde{padding:16px 18px;flex:1}

/* Hesap kart (Flutter _hesapKart) */
#detayPanel .det-kart{background:var(--card2);border-radius:16px;padding:16px;margin-bottom:14px;border:1px solid var(--line)}
body.koyu #detayPanel .det-kart{background:#1b2235}
#detayPanel .det-kart .kart-bas{font-weight:700;font-size:14px;color:var(--ink);margin-bottom:10px}
#detayPanel .satir{display:flex;justify-content:space-between;align-items:center;padding:4px 0}
#detayPanel .satir .s{font-size:13px;color:var(--sub)}
#detayPanel .satir .v{font-size:13px;font-weight:600;color:var(--ink)}
#detayPanel .satir .v.yesil{color:var(--yesil)}
#detayPanel .satir .v.kirmizi{color:var(--kirmizi)}
#detayPanel .satir .v.turuncu{color:#D97706}
#detayPanel .satir .v.kalin{font-weight:800}
#detayPanel .hr{border:none;border-top:1px solid var(--line);margin:8px 0}
#detayPanel .net-satir{background:color-mix(in srgb,var(--yesil) 12%,transparent);border-radius:12px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;margin-top:8px}
#detayPanel .net-satir.sifir{background:color-mix(in srgb,var(--sub) 10%,transparent)}
#detayPanel .net-satir .ns-etiket{font-weight:700;font-size:15px;color:var(--ink)}
#detayPanel .net-satir .ns-v{font-weight:800;font-size:18px;color:var(--yesil)}
#detayPanel .net-satir.sifir .ns-v{color:var(--sub)}

/* İşlem butonları (Flutter _islemler) */
#detayPanel .islem-wrap{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
#detayPanel .islem-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:12px;border:1.5px solid;background:none;cursor:pointer;font-weight:700;font-size:13px;transition:background .15s}
#detayPanel .islem-btn:hover{filter:brightness(1.08)}

/* Hareket defteri (Flutter _hareketDefteri) */
#detayPanel .har-satir{display:flex;align-items:flex-start;gap:10px;padding:7px 0;border-bottom:1px solid var(--line)}
#detayPanel .har-satir:last-child{border-bottom:none}
#detayPanel .har-satir .har-ic{width:18px;flex:none;margin-top:1px}
#detayPanel .har-satir .har-meta{flex:1;min-width:0}
#detayPanel .har-satir .har-meta .tur{font-size:13px;font-weight:600;color:var(--ink)}
#detayPanel .har-satir .har-meta .tar{font-size:11px;color:var(--sub);margin-top:1px}
#detayPanel .har-satir .har-tutar{font-weight:700;font-size:14px;flex:none}
#detayPanel .har-satir .har-sil{width:26px;height:26px;border:none;background:none;cursor:pointer;color:var(--sub);border-radius:6px;display:flex;align-items:center;justify-content:center;transition:background .12s}
#detayPanel .har-satir .har-sil:hover{background:var(--card2)}

/* Personel düzenle & yetki modal */
.rk-modal-arka{position:fixed;inset:0;z-index:50;background:rgba(15,23,42,.55);display:none;align-items:flex-start;justify-content:center;padding:28px 16px;overflow-y:auto}
.rk-modal-arka.acik{display:flex}
.rk-modal{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 20px 60px rgba(15,23,42,.35);width:100%;max-width:520px;margin:auto 0}
.rk-modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line)}
.rk-modal-head .b{font-size:16px;font-weight:800;color:var(--ink)}
.rk-modal-kapa{background:none;border:none;font-size:26px;line-height:1;color:var(--sub);cursor:pointer}
.rk-modal-kapa:hover{color:var(--ink)}
.rk-modal-govde{padding:16px 20px}
.rk-modal-foot{padding:14px 20px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:10px}
.rk-yetki-satir{display:flex;align-items:center;gap:10px;padding:9px 2px;cursor:pointer;color:var(--ink);font-size:14px}
.rk-chk{width:18px;height:18px;accent-color:var(--mor1);cursor:pointer}
.rk-yetki-limit{display:flex;align-items:center;gap:8px;padding:0 0 10px 28px;flex-wrap:wrap}

/* Hareket dialog */
#hareketModal .rk-modal-govde label{display:block;font-size:12.5px;font-weight:600;color:var(--sub);margin-bottom:4px;margin-top:10px}
#hareketModal .rk-modal-govde label:first-child{margin-top:0}
</style>
@endpush

@section('content')
<div id="pnl">

{{-- Flash --}}
@if (session('ok'))
  <div class="rk-flash ok">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="rk-flash err">{{ session('hata') }}</div>
@endif

{{-- ── Ay Seçici (Flutter _aySecici) ── --}}
<div class="ay-bar">
  <a href="/personel?ay={{ $oncekiKey }}" class="ay-btn" title="Önceki ay">
    <span class="material-icons-outlined" style="font-size:22px">chevron_left</span>
  </a>
  <span class="ay-metin">{{ $ayMetin }}</span>
  <a href="{{ $gelecekMi ? '#' : '/personel?ay='.$sonrakiKey }}" class="ay-btn{{ $gelecekMi ? ' dis' : '' }}" title="Sonraki ay">
    <span class="material-icons-outlined" style="font-size:22px">chevron_right</span>
  </a>
</div>

{{-- ── Özet Kart (Flutter _ozetKart) ── --}}
<div class="ozet-kart">
  <div class="bas">Bu Ay Personel Gideri</div>
  <div class="toplam">{{ $tl($ozet['maas'] + $ozet['prim']) }}</div>
  <div class="mini-grid">
    <div class="mini"><span class="v">{{ $tl($ozet['maas']) }}</span><span class="l">Maaş</span></div>
    <div class="mini"><span class="v">{{ $tl($ozet['prim']) }}</span><span class="l">Prim</span></div>
    <div class="mini"><span class="v">{{ $tl($ozet['odenen']) }}</span><span class="l">Ödenen</span></div>
    <div class="mini"><span class="v">{{ $tl($ozet['net_kalan']) }}</span><span class="l">Kalan</span></div>
  </div>
</div>

{{-- Üst şerit: personel sayısı + Personel Ekle butonu --}}
<div class="rk-bolum" style="justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px">
  <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
    <span class="rk-rozet" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 45%,transparent);background:color-mix(in srgb,var(--mor1) 12%,transparent)">
      <span class="material-icons-outlined" style="font-size:16px">groups</span>{{ count($personeller) }} personel
    </span>
    <span class="rk-rozet" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 45%,transparent);background:color-mix(in srgb,var(--yesil) 12%,transparent)">
      <span class="material-icons-outlined" style="font-size:16px">check_circle</span>{{ $aktifSayi }} aktif
    </span>
  </div>
  @if ($duzenleyebilir)
    <button type="button" class="rk-btn" style="background:var(--mor1)" onclick="personelAc(null)">
      <span class="material-icons-outlined" style="font-size:18px">person_add_alt</span> Personel Ekle
    </button>
  @endif
</div>

@if (!$duzenleyebilir)
  <div class="rk-flash err" style="font-weight:600">
    Personel ve yetkileri yalnızca işletme sahibi düzenleyebilir. Görüntülüyorsunuz.
  </div>
@endif

@if (empty($personeller))
  <div class="rk-card" style="text-align:center;padding:38px">
    <span class="rk-sub">Henüz personel yok.</span>
  </div>
@else
  {{-- Role göre gruplu kart ızgarası (Flutter _masaustuGovde / _grupSira) --}}
  @foreach ($grupSira as $grup)
    @continue (empty($siraliGruplar[$grup]))
    @php $gRenk = $grupRenk[$grup] ?? '#64748B'; @endphp
    <div class="grp-bas">
      <span class="cubuk" style="background:{{ $gRenk }}"></span>
      <span class="ad">{{ $grup }}</span>
      <span class="sayi" style="color:{{ $gRenk }};background:color-mix(in srgb,{{ $gRenk }} 13%,transparent)">{{ count($siraliGruplar[$grup]) }}</span>
    </div>

    <div class="p-grid">
      @foreach ($siraliGruplar[$grup] as $p)
        @php
          $pid    = (int)($p['id'] ?? 0);
          $rol    = $p['rol'] ?? 'garson';
          $renk   = $renkAl($rol);
          $aktif  = (int)($p['aktif'] ?? 0) === 1;
          $harf   = mb_strtoupper(mb_substr($p['ad'] ?? '?', 0, 1));
          $h      = $hesapMap[$pid] ?? ['maas'=>0,'prim_hesap'=>0,'net'=>0];
          $net    = (float)($h['net'] ?? 0);
          $netRenk = $net > 0 ? 'var(--ink)' : 'var(--sub)';
          // Detay panel için JSON (personel bilgisi + hesap + hareketler)
          $detayData = array_merge($p, $h, [
              'hareketler' => $hareketDetayMap[$pid] ?? [],
          ]);
        @endphp
        {{-- Flutter _masaustuKart / _personelKart --}}
        <div class="p-kart{{ $aktif ? '' : ' pasif' }}"
             onclick='detayAc(@json($detayData))'
             data-pid="{{ $pid }}">
          <div class="avatar" style="background:color-mix(in srgb,{{ $renk }} 18%,transparent);color:{{ $renk }}">{{ $harf }}</div>
          <div class="p-meta">
            <div class="p-ad">{{ $p['ad'] ?? '—' }}</div>
            <div class="p-alt">
              <span class="rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 45%,transparent);background:color-mix(in srgb,{{ $renk }} 16%,transparent)">{{ $rolAd[$rol] ?? $rol }}</span>
              @if (!$aktif)
                <span class="rozet" style="color:var(--sub);border-color:var(--line);background:var(--card2)">Pasif</span>
              @endif
              <span class="p-maas">Maaş {{ $tl($h['maas'] ?? 0) }}</span>
              @if (($h['prim_hesap'] ?? 0) > 0)
                <span class="p-prim">+prim {{ $tl($h['prim_hesap']) }}</span>
              @endif
            </div>
          </div>
          <div class="p-net">
            <div class="v" style="color:{{ $netRenk }}">{{ $tl($net) }}</div>
            <div class="l">kalan</div>
          </div>
          <span class="material-icons-outlined" style="font-size:20px;color:var(--sub);flex:none">chevron_right</span>
        </div>
      @endforeach
    </div>
  @endforeach
@endif

{{-- ===================================================================
     DETAY PANEL (Flutter PersonelDetayScreen)
     Sağdan kayar, overlay tıklamasıyla kapanır.
     =================================================================== --}}
<div id="detayOverlay" onclick="if(event.target===this)detayKapat()">
  <div id="detayPanel">
    {{-- Başlık --}}
    <div class="det-head">
      <div class="det-ava" id="detAva"></div>
      <div class="det-ad">
        <div class="ad" id="detAd"></div>
        <div class="alt" id="detAlt"></div>
      </div>
      @if ($duzenleyebilir)
      <button class="det-kapa" onclick="detDuzenle()" title="Düzenle" style="color:var(--mavi)">
        <span class="material-icons-outlined" style="font-size:20px">edit</span>
      </button>
      @endif
      <button class="det-kapa" onclick="detayKapat()">
        <span class="material-icons-outlined">close</span>
      </button>
    </div>
    {{-- Gövde --}}
    <div class="det-govde">

      {{-- Bilgi kart (Flutter _bilgiKart) --}}
      <div class="det-kart" id="detBilgiKart">
        <div id="detBilgiIcerik"></div>
      </div>

      {{-- Yetki kart (Flutter _yetkiKart) — tıklanınca yetkiAc() --}}
      <div class="det-kart" style="cursor:pointer;display:flex;align-items:center;gap:12px;padding:14px 16px" onclick="detYetkiAc()" id="detYetkiRow">
        <div style="padding:9px;border-radius:12px;background:color-mix(in srgb,var(--mavi) 16%,transparent);flex:none">
          <span class="material-icons-outlined" style="font-size:22px;color:var(--mavi)">shield</span>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:15px;color:var(--ink)">Yetkiler &amp; Limitler</div>
          <div style="font-size:12px;color:var(--sub);margin-top:2px">Adisyon, para, yönetim yetkileri + iskonto/ikram limiti</div>
        </div>
        <span class="material-icons-outlined" style="font-size:20px;color:var(--sub)" id="detYetkiIkon">chevron_right</span>
      </div>

      {{-- Hesap kart (Flutter _hesapKart) --}}
      <div class="det-kart" id="detHesapKart">
        <div class="kart-bas">Bu Ay Hesap</div>
        <div id="detHesapIcerik"></div>
      </div>

      {{-- İşlem butonları (Flutter _islemler) — sadece sahip --}}
      <div class="islem-wrap" id="detIslemler" style="display:none">
        <button class="islem-btn" style="color:#D97706;border-color:color-mix(in srgb,#D97706 40%,transparent);background:color-mix(in srgb,#D97706 12%,transparent)"
                onclick="hareketDialog('avans','Avans Ver')">
          <span class="material-icons-outlined" style="font-size:18px">south_west</span> Avans Ver
        </button>
        <button class="islem-btn" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 40%,transparent);background:color-mix(in srgb,var(--yesil) 12%,transparent)"
                onclick="hareketDialog('odeme','Maaş/Ödeme Yap')">
          <span class="material-icons-outlined" style="font-size:18px">check_circle_outline</span> Ödeme Yap
        </button>
        <button class="islem-btn" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 40%,transparent);background:color-mix(in srgb,var(--mor1) 12%,transparent)"
                onclick="hareketDialog('prim','Manuel Prim')">
          <span class="material-icons-outlined" style="font-size:18px">star_outline</span> Prim Ekle
        </button>
        <button class="islem-btn" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent);background:color-mix(in srgb,var(--kirmizi) 12%,transparent)"
                onclick="hareketDialog('kesinti','Kesinti')">
          <span class="material-icons-outlined" style="font-size:18px">remove_circle_outline</span> Kesinti
        </button>
        <button class="islem-btn" style="color:var(--mavi);border-color:color-mix(in srgb,var(--mavi) 40%,transparent);background:color-mix(in srgb,var(--mavi) 12%,transparent)"
                onclick="hareketDialog('ek_odeme','Ek Ödeme')">
          <span class="material-icons-outlined" style="font-size:18px">add</span> Ek Ödeme
        </button>
      </div>

      {{-- Hareket defteri (Flutter _hareketDefteri) --}}
      <div class="det-kart" id="detHareketKart">
        <div class="kart-bas">Hareket Defteri</div>
        <div id="detHareketIcerik"></div>
      </div>

    </div>{{-- /det-govde --}}
  </div>{{-- /detayPanel --}}
</div>{{-- /detayOverlay --}}

@if ($duzenleyebilir)

{{-- ==================== PERSONEL EKLE / DÜZENLE MODAL ==================== --}}
<div id="personelModal" class="rk-modal-arka" onclick="if(event.target===this)personelKapat()">
  <div class="rk-modal">
    <form method="POST" action="/personel/kaydet">
      @csrf
      <input type="hidden" name="id" id="f_id" value="">
      <div class="rk-modal-head">
        <span class="b" id="personelModalBaslik">Yeni Personel</span>
        <button type="button" class="rk-modal-kapa" onclick="personelKapat()">&times;</button>
      </div>
      <div class="rk-modal-govde">
        {{-- Bilgi notu --}}
        <div style="background:color-mix(in srgb,var(--mavi) 10%,transparent);border:1px solid color-mix(in srgb,var(--mavi) 30%,transparent);border-radius:12px;padding:10px 12px;margin-bottom:14px;display:flex;gap:9px;align-items:flex-start">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mavi)">info</span>
          <span class="rk-sub2" style="font-size:12px;line-height:1.4"><b>Kasa / istasyon oluşturmak için:</b> bir ad yaz (ör. "Kasa 1"), <b>Rol = Kasa</b> seç ve bir <b>PIN</b> ver. Telefon gerekmez. O PIN'le kasada giriş yapılır; kaydettikten sonra kartına tıkla → <b>Yetkiler</b>'den ince ayar yaparsın.</span>
        </div>

        <label class="rk-lbl">Ad Soyad</label>
        <input type="text" name="ad" id="f_ad" required class="rk-inp" placeholder='Ad Soyad veya "Kasa 1"'>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="rk-lbl">Telefon <span class="rk-sub" style="font-weight:400">(opsiyonel)</span></label>
            <input type="text" name="telefon" id="f_telefon" class="rk-inp" inputmode="tel" placeholder="Kasa için boş bırak">
          </div>
          <div>
            <label class="rk-lbl">PIN (en az 4 hane)</label>
            <input type="text" name="pin" id="f_pin" inputmode="numeric" maxlength="8" class="rk-inp" placeholder="Boş = değişmez">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="rk-lbl">Rol</label>
            <select name="rol" id="f_rol" class="rk-inp">
              @foreach ($rolAd as $k => $ad)
                <option value="{{ $k }}">{{ $ad }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="rk-lbl">Durum</label>
            <select name="aktif" id="f_aktif" class="rk-inp">
              <option value="1">Aktif</option>
              <option value="0">Pasif</option>
            </select>
          </div>
        </div>

        {{-- Maaş / Prim (Flutter _formAc bölümü) --}}
        <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-top:4px">
          <div>
            <label class="rk-lbl">Maaş (TL)</label>
            <input type="number" name="maas" id="f_maas" min="0" step="1" class="rk-inp" placeholder="0">
          </div>
          <div>
            <label class="rk-lbl">Tip</label>
            <select name="maas_tipi" id="f_maas_tipi" class="rk-inp">
              <option value="aylik">Aylık</option>
              <option value="gunluk">Günlük</option>
              <option value="saatlik">Saatlik</option>
            </select>
          </div>
        </div>

        <div style="margin-top:4px">
          <label class="rk-lbl">Prim</label>
          <select name="prim_tipi" id="f_prim_tipi" class="rk-inp" onchange="primTipiDegis()">
            <option value="yok">Prim Yok</option>
            <option value="ciro">Ciro Yüzdesi</option>
          </select>
        </div>
        <div id="f_prim_oran_satir" style="display:none;margin-top:4px">
          <label class="rk-lbl">Prim Oranı (% ciro)</label>
          <input type="number" name="prim_oran" id="f_prim_oran" min="0" max="100" step="0.1" class="rk-inp" placeholder="0">
        </div>

        <div style="margin-top:4px">
          <label class="rk-lbl">IBAN (opsiyonel)</label>
          <input type="text" name="iban" id="f_iban" class="rk-inp" placeholder="TR...">
        </div>

      </div>
      <div class="rk-modal-foot">
        <button type="button" class="rk-btn bos" onclick="personelKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Kaydet</button>
      </div>
    </form>
  </div>
</div>

{{-- ==================== YETKİ DÜZENLE MODAL ==================== --}}
<div id="yetkiModal" class="rk-modal-arka" onclick="if(event.target===this)yetkiKapat()">
  <div class="rk-modal">
    <form method="POST" action="/personel/yetki">
      @csrf
      <input type="hidden" name="id" id="y_id" value="">
      <div class="rk-modal-head">
        <span class="b"><span id="yetkiModalAd">Personel</span> — Yetkiler</span>
        <button type="button" class="rk-modal-kapa" onclick="yetkiKapat()">&times;</button>
      </div>
      <div class="rk-modal-govde">
        {{-- Yetki Seviyesi (kademe) --}}
        <div class="rk-bolum" style="margin:2px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">workspace_premium</span>
          <span class="b" style="font-size:13.5px;color:var(--mor1)">Yetki Seviyesi</span>
        </div>
        <select name="kademe" id="y_kademe" class="rk-inp" onchange="kademeUygula()">
          @foreach ($kademeler as $kd => $info)
            <option value="{{ $kd }}">{{ $info['ad'] }} — {{ $info['aciklama'] }}</option>
          @endforeach
        </select>
        <div class="rk-sub" style="font-size:11.5px;margin:6px 0 16px">Seviye seçince aşağıdaki sayfalar otomatik işaretlenir; istediğini tek tek açıp kapatabilirsin.</div>

        {{-- Departman (sayfa yetki şablonu) --}}
        @if (!empty($departmanlar) && count($departmanlar))
        <div class="rk-bolum" style="margin:2px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mavi)">groups</span>
          <span class="b" style="font-size:13.5px;color:var(--mavi)">Departman</span>
        </div>
        <select name="departman_id" id="y_departman" class="rk-inp" onchange="departmanDegisti()">
          <option value="0">— Departman yok —</option>
          @foreach ($departmanlar as $d)
            <option value="{{ $d->id }}">{{ $d->grup ? $d->grup.' › ' : '' }}{{ $d->ad }}</option>
          @endforeach
        </select>
        <div style="display:flex;gap:10px;margin-top:8px">
          <div style="flex:1">
            <label class="rk-lbl" style="font-size:12px">Ünvan / Kademe</label>
            <select name="departman_unvan" id="y_unvan" class="rk-inp"><option value="">—</option></select>
          </div>
          <label class="rk-yetki-satir" style="align-self:flex-end;padding-bottom:10px">
            <input type="checkbox" name="departman_sorumlu" id="y_sorumlu" class="rk-chk" onchange="departmanUygula()">
            <span>Departman Sorumlusu</span>
          </label>
        </div>
        <div class="rk-sub" style="font-size:11.5px;margin:6px 0 16px">Departman seçince o departmanın sayfaları otomatik işaretlenir; <b>Sorumlu</b> işaretlenirse yönetim sayfaları da eklenir (en üst kademe). İki departman aynı sayfayı paylaşabilir; sonra elle ince ayar yapabilirsin. <a href="/departman-yonetim" style="color:var(--mavi)">Departmanları düzenle</a></div>
        @endif

        {{-- Sayfa (menü) yetkileri --}}
        <div class="rk-bolum" style="margin:2px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">visibility</span>
          <span class="b" style="font-size:13.5px;color:var(--mor1)">Görebileceği Sayfalar (Menü)</span>
        </div>
        @foreach ($sayfaKategori as $kat => $sayfalar)
          <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:8px 2px 4px">{{ $kat }}</div>
          <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:10px">
            @foreach ($sayfalar as $sk => $et)
              <label class="rk-yetki-satir">
                <input type="checkbox" name="sayfa_{{ $sk }}" data-sayfa="{{ $sk }}" class="rk-chk">
                <span>{{ $et }}</span>
              </label>
            @endforeach
          </div>
        @endforeach

        {{-- İşlem yetkileri --}}
        <div class="rk-bolum" style="margin:16px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">tune</span>
          <span class="b" style="font-size:13.5px;color:var(--mor1)">İşlem Yetkileri (POS)</span>
        </div>
        @foreach ($gruplar as $baslik => $g)
          <div class="rk-bolum" style="margin:2px 0 8px">
            <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">{{ $g['ikon'] }}</span>
            <span class="b" style="font-size:13.5px;color:var(--mor1)">{{ $baslik }}</span>
          </div>
          <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:14px">
            @foreach ($g['keys'] as $k)
              @if (isset($etiketler[$k]))
                <label class="rk-yetki-satir">
                  <input type="checkbox" name="yetki_{{ $k }}" data-yetki="{{ $k }}" class="rk-chk"
                         @if ($k === 'iskonto' || $k === 'ikram') onchange="yetkiLimitGoster()" @endif>
                  <span>{{ $etiketler[$k] }}</span>
                </label>
                @if ($k === 'iskonto')
                  <div id="y_iskonto_satir" class="rk-yetki-limit" style="display:none">
                    <span class="rk-sub" style="font-size:12px">İskonto limiti</span>
                    <input type="number" name="iskonto_limit" id="y_iskonto_limit" min="0" max="100" step="1" class="rk-inp" style="width:80px;padding:6px 10px">
                    <span class="rk-sub" style="font-size:12px">% (üstü yönetici onayı)</span>
                  </div>
                @endif
                @if ($k === 'ikram')
                  <div id="y_ikram_satir" class="rk-yetki-limit" style="display:none">
                    <span class="rk-sub" style="font-size:12px">İkram limiti</span>
                    <input type="number" name="ikram_limit" id="y_ikram_limit" min="0" step="1" class="rk-inp" style="width:100px;padding:6px 10px">
                    <span class="rk-sub" style="font-size:12px">₺ (üstü yönetici onayı)</span>
                  </div>
                @endif
              @endif
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="rk-modal-foot">
        <button type="button" class="rk-btn bos" onclick="yetkiKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Yetkileri Kaydet</button>
      </div>
    </form>
  </div>
</div>

{{-- ==================== HAREKET DİYALOG (Flutter _hareketDialog) ==================== --}}
<div id="hareketModal" class="rk-modal-arka" onclick="if(event.target===this)hareketKapat()">
  <div class="rk-modal" style="max-width:380px">
    <div class="rk-modal-head">
      <span class="b" id="hareketBaslik">İşlem</span>
      <button type="button" class="rk-modal-kapa" onclick="hareketKapat()">&times;</button>
    </div>
    <div class="rk-modal-govde">
      <label>Tutar (TL)</label>
      <input type="number" id="h_tutar" min="0.01" step="0.01" class="rk-inp" placeholder="0" autofocus>
      <label>Açıklama (opsiyonel)</label>
      <input type="text" id="h_aciklama" class="rk-inp" placeholder="">
    </div>
    <div class="rk-modal-foot">
      <button type="button" class="rk-btn bos" onclick="hareketKapat()">Vazgeç</button>
      <button type="button" class="rk-btn" style="background:var(--mor1)" onclick="hareketKaydet()">Kaydet</button>
    </div>
  </div>
</div>

@endif {{-- /duzenleyebilir --}}

</div>{{-- /pnl --}}

@push('scripts')
<script>
// =============================================================
// VERİ: PHP'den gelen personel + hesap + hareket verileri
// =============================================================
var DUZENLEYEBILIR = {{ $duzenleyebilir ? 'true' : 'false' }};
var AY_KEY         = '{{ $ayKey }}';
var KADEME_SAYFA   = @json($kademeSayfa ?? []);
var DEPARTMAN_SAYFA= @json($departmanSayfa ?? []);
var DEPARTMAN_EK   = @json($departmanEk ?? []);
var DEPARTMAN_UNVAN= @json($departmanUnvan ?? []);
var ROL_RENK       = @json($rolRenk);
var ROL_AD         = @json($rolAd);
var WEB_API_TOKEN  = @json($webApiToken); // POS session'dan (sahip) — hareket AJAX için

// =============================================================
// YARDIMCI FONKSİYONLAR
// =============================================================
function pTL(v) {
    v = parseFloat(v) || 0;
    return new Intl.NumberFormat('tr-TR').format(Math.round(v)) + ' TL';
}
function pRozet(etiket, renk, ekStyle) {
    ekStyle = ekStyle || '';
    return '<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700;border:1px solid;color:'+renk+';border-color:color-mix(in srgb,'+renk+' 45%,transparent);background:color-mix(in srgb,'+renk+' 16%,transparent);'+ekStyle+'">'+esc(etiket)+'</span>';
}
function esc(s) {
    s = String(s||'');
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function maasTipiAd(t) { return {aylik:'ay',gunluk:'gün',saatlik:'saat'}[t]||'ay'; }
function turMeta(tur) {
    // [etiket, ikon, renk]
    return {
        avans:    ['Avans',        'south_west',          '#D97706'],
        odeme:    ['Maaş/Ödeme',   'check_circle',        'var(--yesil)'],
        prim:     ['Manuel Prim',  'star',                'var(--mor1)'],
        kesinti:  ['Kesinti',      'remove_circle',       'var(--kirmizi)'],
        ek_odeme: ['Ek Ödeme',     'add_circle',          'var(--mavi)'],
    }[tur] || [tur, 'circle', 'var(--sub)'];
}

// =============================================================
// DETAY PANELİ
// =============================================================
var _aktifPersonel = null;

function detayAc(p) {
    _aktifPersonel = p;
    var rol  = p.rol || 'garson';
    var renk = ROL_RENK[rol] || '#64748B';
    var harf = (p.ad||'?').charAt(0).toUpperCase();
    var aktif = parseInt(p.aktif) === 1;

    // Başlık
    var ava = document.getElementById('detAva');
    ava.textContent = harf;
    ava.style.cssText = 'background:color-mix(in srgb,'+renk+' 18%,transparent);color:'+renk;
    document.getElementById('detAd').textContent = p.ad || '—';

    var altEl = document.getElementById('detAlt');
    altEl.innerHTML = pRozet(ROL_AD[rol]||rol, renk);
    if (!aktif) altEl.innerHTML += ' ' + pRozet('Pasif', 'var(--sub)');
    if (p.telefon) altEl.innerHTML += ' <span style="font-size:12px;color:var(--sub)">'+esc(p.telefon)+'</span>';

    // Bilgi kart içeriği (Flutter _bilgiKart)
    var primTipi = p.prim_tipi || 'yok';
    var bi = '';
    bi += satirHtml('Maaş', pTL(p.maas||0)+' / '+maasTipiAd(p.maas_tipi));
    bi += satirHtml('Prim', primTipi==='ciro' ? 'Ciro %'+(parseFloat(p.prim_oran)||0) : 'Yok', primTipi==='ciro'?'var(--yesil)':'var(--sub)');
    if (p.pin)  bi += satirHtml('PIN', p.pin);
    if (p.iban) bi += satirHtml('IBAN', p.iban);
    document.getElementById('detBilgiIcerik').innerHTML = bi;

    // Yetki satırı ikon
    document.getElementById('detYetkiIkon').textContent = DUZENLEYEBILIR ? 'chevron_right' : 'visibility';

    // Hesap kart (Flutter _hesapKart)
    var net  = parseFloat(p.net || 0);
    var hi   = '';
    if (parseFloat(p.ciro||0) > 0) hi += satirHtml('Ciro (prim tabanı)', pTL(p.ciro));
    hi += satirHtml('Maaş tahakkuk', pTL(p.maas||0));
    if (parseFloat(p.prim_hesap||0) > 0) hi += satirHtml('Hesaplanan prim', '+'+pTL(p.prim_hesap), 'var(--yesil)');
    if (parseFloat(p.prim_manuel||0) > 0) hi += satirHtml('Manuel prim', '+'+pTL(p.prim_manuel), 'var(--yesil)');
    if (parseFloat(p.ek_odeme||0) > 0)   hi += satirHtml('Ek ödeme', '+'+pTL(p.ek_odeme), 'var(--yesil)');
    if (parseFloat(p.kesinti||0) > 0)     hi += satirHtml('Kesinti', '-'+pTL(p.kesinti), 'var(--kirmizi)');
    hi += '<hr style="border:none;border-top:1px solid var(--line);margin:8px 0">';
    hi += satirHtml('Hakediş', pTL(p.hakedis||0), null, true);
    if (parseFloat(p.avans||0) > 0)  hi += satirHtml('Avans',  '-'+pTL(p.avans),  '#D97706');
    if (parseFloat(p.odenen||0) > 0) hi += satirHtml('Ödenen', '-'+pTL(p.odenen), '#D97706');
    hi += '<div class="net-satir'+(net>0?'':' sifir')+'">'
        + '<span class="ns-etiket">Net Ödenecek</span>'
        + '<span class="ns-v">'+pTL(net)+'</span></div>';
    document.getElementById('detHesapIcerik').innerHTML = hi;

    // İşlem butonları
    document.getElementById('detIslemler').style.display = DUZENLEYEBILIR ? 'flex' : 'none';

    // Hareket defteri
    var hareketler = p.hareketler || [];
    var harEl = document.getElementById('detHareketIcerik');
    if (!hareketler.length) {
        harEl.innerHTML = '<p style="color:var(--sub);font-size:13px;padding:6px 0">Bu ay hareket yok.</p>';
    } else {
        harEl.innerHTML = hareketler.map(function(h) { return hareketSatirHtml(h); }).join('');
    }

    // Panel aç
    var ov = document.getElementById('detayOverlay');
    ov.classList.add('acik');
    setTimeout(function(){ document.getElementById('detayPanel').style.transform='translateX(0)'; }, 10);
}

function detayKapat() {
    var ov = document.getElementById('detayOverlay');
    ov.classList.remove('acik');
}

function satirHtml(s, v, renk, kalin) {
    var vStyle = 'color:'+(renk||'var(--ink)')+';'+(kalin?'font-weight:800':'font-weight:600');
    return '<div class="satir"><span class="s">'+esc(s)+'</span><span class="v" style="'+vStyle+'">'+esc(v)+'</span></div>';
}

function hareketSatirHtml(h) {
    var meta  = turMeta(h.tur);
    var silBtn = DUZENLEYEBILIR
        ? '<button class="har-sil" onclick="hareketSil('+h.id+',event)" title="Sil"><span class="material-icons-outlined" style="font-size:16px">close</span></button>'
        : '';
    var tarAcik = h.tarih + (h.aciklama ? ' · '+esc(h.aciklama) : '');
    return '<div class="har-satir">'
        + '<span class="material-icons-outlined har-ic" style="color:'+meta[2]+';font-size:18px">'+meta[1]+'</span>'
        + '<div class="har-meta"><div class="tur">'+esc(meta[0])+'</div><div class="tar">'+esc(tarAcik)+'</div></div>'
        + '<span class="har-tutar" style="color:'+meta[2]+'">'+pTL(h.tutar)+'</span>'
        + silBtn
        + '</div>';
}

// Detay panelinden yetki modalını aç
function detYetkiAc() {
    if (!_aktifPersonel) return;
    yetkiAc(_aktifPersonel);
}
// Detay panelinden personel düzenle (Flutter AppBar edit butonu)
function detDuzenle() {
    if (!_aktifPersonel) return;
    personelAc(_aktifPersonel);
}

// =============================================================
// PERSONEL EKLE / DÜZENLE MODAL
// =============================================================
function personelAc(p) {
    document.getElementById('personelModalBaslik').textContent = p ? 'Personeli Düzenle' : 'Yeni Personel';
    document.getElementById('f_id').value       = p ? p.id : '';
    document.getElementById('f_ad').value       = p ? (p.ad || '') : '';
    document.getElementById('f_telefon').value  = p ? (p.telefon || '') : '';
    document.getElementById('f_pin').value      = p ? (p.pin || '') : '';
    document.getElementById('f_rol').value      = p ? (p.rol || 'garson') : 'garson';
    document.getElementById('f_aktif').value    = p ? String(p.aktif) : '1';
    document.getElementById('f_maas').value     = p ? (parseFloat(p.maas)||0) : '';
    document.getElementById('f_maas_tipi').value= p ? (p.maas_tipi||'aylik') : 'aylik';
    document.getElementById('f_prim_tipi').value= p ? (p.prim_tipi||'yok') : 'yok';
    document.getElementById('f_prim_oran').value= p ? (parseFloat(p.prim_oran)||0) : '';
    document.getElementById('f_iban').value     = p ? (p.iban || '') : '';
    primTipiDegis();
    document.getElementById('personelModal').classList.add('acik');
}
function personelKapat() { document.getElementById('personelModal').classList.remove('acik'); }

function primTipiDegis() {
    var ciro = document.getElementById('f_prim_tipi').value === 'ciro';
    document.getElementById('f_prim_oran_satir').style.display = ciro ? 'block' : 'none';
}

// =============================================================
// YETKİ MODAL
// =============================================================
function kademeUygula() {
    var kd = document.getElementById('y_kademe').value;
    var liste = KADEME_SAYFA[kd] || [];
    var hepsi = (kd === 'sahip');
    document.querySelectorAll('#yetkiModal input[data-sayfa]').forEach(function (el) {
        el.checked = hepsi || liste.indexOf(el.dataset.sayfa) >= 0;
    });
}
// Departman seçimi değişince: ünvan listesini doldur + sayfaları uygula.
function departmanDegisti() {
    var el = document.getElementById('y_departman');
    var uv = document.getElementById('y_unvan');
    if (uv) {
        var did0 = el ? el.value : '0';
        var liste = DEPARTMAN_UNVAN[did0] || [];
        uv.innerHTML = '<option value="">—</option>';
        liste.forEach(function (u, i) {
            var o = document.createElement('option'); o.value = u; o.textContent = (i === 0 ? '★ ' : '') + u; uv.appendChild(o);
        });
    }
    departmanUygula();
}
// Departmanın ÜYE sayfaları + (Sorumlu işaretliyse) yönetim ek sayfalarını işaretle.
function departmanUygula() {
    var el = document.getElementById('y_departman');
    if (!el) return;
    var did = el.value;
    if (!did || did === '0') return; // "yok" seçilince mevcut işaretler korunur
    var liste = (DEPARTMAN_SAYFA[did] || []).slice();
    var sorumlu = document.getElementById('y_sorumlu');
    if (sorumlu && sorumlu.checked) (DEPARTMAN_EK[did] || []).forEach(function (k) { if (liste.indexOf(k) < 0) liste.push(k); });
    document.querySelectorAll('#yetkiModal input[data-sayfa]').forEach(function (c) {
        c.checked = liste.indexOf(c.dataset.sayfa) >= 0;
    });
}
function yetkiAc(p) {
    document.getElementById('y_id').value = p.id;
    document.getElementById('yetkiModalAd').textContent = p.ad || 'Personel';
    var y = p.yetkiler || {};
    document.querySelectorAll('#yetkiModal input[data-yetki]').forEach(function (el) {
        el.checked = y[el.dataset.yetki] === true;
    });
    document.getElementById('y_kademe').value = p.kademe || 'personel';
    var dep = document.getElementById('y_departman');
    if (dep) {
        dep.value = (p.departman_id && p.departman_id > 0) ? String(p.departman_id) : '0';
        // Ünvan listesini bu departmana göre doldur (uygulamadan; sayfa işaretlerini DEĞİŞTİRME).
        var uv = document.getElementById('y_unvan');
        if (uv) {
            var liste = DEPARTMAN_UNVAN[dep.value] || [];
            uv.innerHTML = '<option value="">—</option>';
            liste.forEach(function (u, i) {
                var o = document.createElement('option'); o.value = u; o.textContent = (i === 0 ? '★ ' : '') + u; uv.appendChild(o);
            });
            uv.value = p.departman_unvan || '';
        }
        var sor = document.getElementById('y_sorumlu');
        if (sor) sor.checked = p.departman_sorumlu === 1;
    }
    var sf = p.sayfa || {};
    document.querySelectorAll('#yetkiModal input[data-sayfa]').forEach(function (el) {
        el.checked = sf[el.dataset.sayfa] === true;
    });
    document.getElementById('y_iskonto_limit').value = Math.round(p.iskonto_limit || 0);
    document.getElementById('y_ikram_limit').value   = Math.round(p.ikram_limit || 0);
    yetkiLimitGoster();
    document.getElementById('yetkiModal').classList.add('acik');
}
function yetkiKapat() { document.getElementById('yetkiModal').classList.remove('acik'); }
function yetkiLimitGoster() {
    var isk = document.querySelector('#yetkiModal input[data-yetki="iskonto"]');
    var ikr = document.querySelector('#yetkiModal input[data-yetki="ikram"]');
    document.getElementById('y_iskonto_satir').style.display = (isk && isk.checked) ? 'flex' : 'none';
    document.getElementById('y_ikram_satir').style.display   = (ikr && ikr.checked) ? 'flex' : 'none';
}

// =============================================================
// HAREKET DİYALOG (Flutter _hareketDialog)
// =============================================================
var _hareketTur = null;

function hareketDialog(tur, baslik) {
    if (!_aktifPersonel) return;
    _hareketTur = tur;
    document.getElementById('hareketBaslik').textContent = baslik;
    document.getElementById('h_tutar').value     = '';
    document.getElementById('h_aciklama').value  = '';
    document.getElementById('hareketModal').classList.add('acik');
    setTimeout(function(){ document.getElementById('h_tutar').focus(); }, 80);
}
function hareketKapat() { document.getElementById('hareketModal').classList.remove('acik'); }

// Hareket API çağrısı: Bearer token (POS sahip session'dan) + CSRF fallback
async function apiHareket(url, data) {
    var headers = { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf };
    if (WEB_API_TOKEN) headers['Authorization'] = 'Bearer ' + WEB_API_TOKEN;
    var r = await fetch(url, { method: 'POST', headers: headers, body: JSON.stringify(data) });
    return r.json();
}

async function hareketKaydet() {
    var tutar = parseFloat(document.getElementById('h_tutar').value);
    if (!tutar || tutar <= 0) { alert('Geçerli bir tutar girin'); return; }
    var aciklama = document.getElementById('h_aciklama').value.trim();
    hareketKapat();
    try {
        var res = await apiHareket('/api/patron/personel-hareket', {
            personel_id: _aktifPersonel.id,
            tur: _hareketTur,
            tutar: tutar,
            aciklama: aciklama
        });
        if (res.ok === 1) {
            window.location.href = '/personel?ay=' + AY_KEY;
        } else {
            alert(res.hata || 'Kaydedilemedi');
        }
    } catch(e) { alert('Bağlantı hatası'); }
}

async function hareketSil(id, e) {
    e.stopPropagation();
    if (!confirm('Bu hareketi sil?')) return;
    try {
        var res = await apiHareket('/api/patron/personel-hareket-sil', { id: id });
        if (res.ok === 1) {
            window.location.href = '/personel?ay=' + AY_KEY;
        } else {
            alert(res.hata || 'Silinemedi');
        }
    } catch(e) { alert('Bağlantı hatası'); }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { detayKapat(); personelKapat(); yetkiKapat(); hareketKapat(); }
});
</script>
@endpush
@endsection

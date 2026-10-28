@extends('layout.app')
@section('title', 'Satınalma Siparişi')
@section('rk_ikon', 'shopping_cart_checkout')
@section('baslik', 'Satınalma Siparişi')

@push('head')
<style>
#sas{
  --mor1:#7C3AED;--mor2:#A855F7;--mavi:#4F46E5;--mavi2:#6366F1;
  --yesil:#10B981;--yesil2:#34D399;--kirmizi:#F43F5E;--kirmizi2:#FB7185;
  --amber:#F59E0B;--amber2:#FBBF24;--cyan:#06B6D4;--pembe:#EC4899;
  --card:#fff;--card2:#f8fafc;--card3:#f1f5f9;--bg:#f6f7fb;
  --ink:#0f172a;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
  --sh1:0 2px 8px rgba(15,23,42,.06);--sh2:0 8px 24px rgba(15,23,42,.08);
}
body.koyu #sas{--card:#161d2e;--card2:#1b2235;--card3:#222a40;--bg:#0f1623;
  --ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
  --sh1:0 2px 8px rgba(0,0,0,.3);--sh2:0 8px 24px rgba(0,0,0,.35)}
#sas{background:var(--bg);min-height:calc(100vh - 60px)}#sas *{box-sizing:border-box}
#sas .pano{padding:0 0 32px}

/* Flash */
#sas .flash{border-radius:14px;padding:12px 16px;margin-bottom:14px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:9px}
#sas .flash.ok{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(52,211,153,.08));color:#047857;border:1px solid rgba(16,185,129,.3)}
#sas .flash.err{background:linear-gradient(135deg,rgba(244,63,94,.14),rgba(251,113,133,.08));color:#be123c;border:1px solid rgba(244,63,94,.3)}

/* HERO */
#sas .hero{background:linear-gradient(135deg,var(--mor1) 0%,var(--mavi) 100%);border-radius:22px;padding:22px 24px;margin-bottom:16px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 32px rgba(124,58,237,.25)}
#sas .hero::before{content:'';position:absolute;top:-100px;right:-80px;width:280px;height:280px;background:radial-gradient(circle,rgba(255,255,255,.15),transparent 70%);border-radius:50%;pointer-events:none}
#sas .hero::after{content:'';position:absolute;bottom:-60px;left:30%;width:220px;height:220px;background:radial-gradient(circle,rgba(255,255,255,.1),transparent 70%);border-radius:50%;pointer-events:none}
#sas .hero-ust{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:14px;position:relative}
#sas .hero .lbl{font-size:11.5px;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.7px;display:flex;align-items:center;gap:7px}
#sas .hero .lbl .material-icons-outlined{font-size:16px}
#sas .hero .tutar{font-size:38px;font-weight:900;color:#fff;line-height:1;letter-spacing:-1px;margin-top:7px}
#sas .hero .info{font-size:12.5px;color:rgba(255,255,255,.78);margin-top:7px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
#sas .hero .mini-dot{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);padding:3px 9px;border-radius:999px;font-size:11.5px;font-weight:700}
#sas .hero-sag{display:flex;gap:10px;align-items:center;flex-wrap:wrap;position:relative;z-index:1}
#sas .ekle-btn{display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--mor1);border:0;border-radius:12px;padding:11px 20px;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);transition:transform .14s,box-shadow .14s}
#sas .ekle-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.2)}
#sas .ekle-btn .material-icons-outlined{font-size:19px}

#sas .hero-kpi{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;position:relative;z-index:1}
#sas .kpi-chip{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:13px;padding:11px 13px;backdrop-filter:blur(10px);cursor:pointer;transition:background .14s,transform .14s}
#sas .kpi-chip:hover{background:rgba(255,255,255,.22);transform:translateY(-1px)}
#sas .kpi-chip .l{font-size:10.5px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;gap:5px}
#sas .kpi-chip .l .material-icons-outlined{font-size:13px}
#sas .kpi-chip .v{font-size:22px;font-weight:900;color:#fff;line-height:1;margin-top:4px;letter-spacing:-.3px}

/* Durum tab */
#sas .durum-bar{display:flex;align-items:center;gap:7px;overflow-x:auto;padding-bottom:4px;margin-bottom:12px;scrollbar-width:thin}
#sas .durum-bar::-webkit-scrollbar{height:3px}
#sas .durum-bar::-webkit-scrollbar-thumb{background:var(--line);border-radius:3px}
#sas .d-tab{display:inline-flex;align-items:center;gap:7px;padding:9px 15px;border-radius:999px;background:var(--card);border:1.5px solid var(--line);font-size:13px;font-weight:800;color:var(--sub);cursor:pointer;transition:all .14s;user-select:none;white-space:nowrap}
#sas .d-tab:hover{color:var(--ink);border-color:var(--sub2)}
#sas .d-tab.aktif{border-color:transparent;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.1)}
#sas .d-tab .say{font-size:11px;font-weight:900;background:rgba(0,0,0,.08);padding:1px 8px;border-radius:999px;min-width:20px;text-align:center}
#sas .d-tab.aktif .say{background:rgba(255,255,255,.26)}
#sas .d-tab .material-icons-outlined{font-size:15px}

/* Arama + Hızlı filtre */
#sas .alt-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
#sas .arama{flex:1;min-width:240px;position:relative}
#sas .arama .material-icons-outlined{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub2);font-size:20px}
#sas .arama input{width:100%;height:42px;padding:0 14px 0 40px;border:1.5px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;transition:border-color .15s,box-shadow .15s}
#sas .arama input:focus{outline:none;border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.14)}
#sas .hizli{display:flex;gap:6px;flex-wrap:wrap}
#sas .h-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:999px;background:var(--card);border:1.5px solid var(--line);font-size:12.5px;font-weight:700;color:var(--sub);cursor:pointer;transition:all .14s;user-select:none;white-space:nowrap}
#sas .h-chip:hover{color:var(--ink);border-color:var(--sub2)}
#sas .h-chip.aktif{border-color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 10%,transparent);color:var(--kirmizi)}
#sas .h-chip.aktif.amb{border-color:var(--amber);background:color-mix(in srgb,var(--amber) 10%,transparent);color:var(--amber)}
#sas .h-chip .material-icons-outlined{font-size:15px}

/* Secili toplam ozet */
#sas .ozet-bar{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 14px;margin-bottom:12px;display:flex;align-items:center;gap:16px;font-size:12.5px;color:var(--sub);flex-wrap:wrap}
#sas .ozet-bar b{color:var(--ink);font-weight:800}

/* Sipariş kartları grid */
#sas .kart-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:12px}
#sas .s-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;cursor:pointer;transition:transform .14s,box-shadow .14s,border-color .14s;position:relative;overflow:hidden;text-decoration:none;color:inherit;display:block}
#sas .s-kart:hover{transform:translateY(-2px);box-shadow:var(--sh2);border-color:color-mix(in srgb,var(--mor1) 25%,var(--line))}
#sas .s-kart .strip{position:absolute;left:0;top:0;bottom:0;width:4px}
#sas .s-kart .ust{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
#sas .s-kart .sn{font-size:13px;font-weight:800;color:var(--ink);letter-spacing:-.2px;line-height:1.2}
#sas .s-kart .sn-sub{font-size:11.5px;color:var(--sub);margin-top:3px;display:inline-flex;align-items:center;gap:4px}
#sas .s-kart .sn-sub .material-icons-outlined{font-size:13px}
#sas .d-pill{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}
#sas .d-pill .material-icons-outlined{font-size:12px}

#sas .s-kart .orta{display:flex;align-items:center;gap:11px;margin-bottom:12px;padding:11px 12px;background:var(--card2);border-radius:12px}
#sas .s-kart .av{width:36px;height:36px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;flex-shrink:0}
#sas .s-kart .ted-ad{font-size:13px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;min-width:0}
#sas .s-kart .ted-meta{font-size:11px;color:var(--sub);margin-top:2px}

#sas .s-kart .alt-grid{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end}
#sas .s-kart .tutar{font-size:20px;font-weight:900;color:var(--ink);letter-spacing:-.3px;line-height:1}
#sas .s-kart .tutar .cur{font-size:14px;color:var(--sub);font-weight:700;margin-left:2px}
#sas .s-kart .tutar-l{font-size:10.5px;color:var(--sub2);font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-top:3px}
#sas .s-kart .detay-btn{display:inline-flex;align-items:center;gap:4px;padding:7px 12px;border-radius:10px;background:color-mix(in srgb,var(--mor1) 10%,transparent);color:var(--mor1);font-size:12px;font-weight:800;transition:background .14s}
#sas .s-kart:hover .detay-btn{background:var(--mor1);color:#fff}
#sas .s-kart .detay-btn .material-icons-outlined{font-size:16px}

#sas .s-kart .teslim{margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--sub)}
#sas .s-kart .teslim .material-icons-outlined{font-size:14px}
#sas .s-kart .teslim.geciken{color:var(--kirmizi);font-weight:700}
#sas .s-kart .teslim.yakin{color:var(--amber);font-weight:700}
#sas .s-kart .onay-badge{position:absolute;top:12px;right:12px;background:rgba(16,185,129,.14);color:var(--yesil);padding:3px 9px;border-radius:999px;font-size:10px;font-weight:800;display:flex;align-items:center;gap:3px}
#sas .s-kart .onay-badge .material-icons-outlined{font-size:12px}
#sas .s-kart .onay-badge.bek{background:rgba(245,158,11,.14);color:var(--amber)}
#sas .s-kart .onay-badge.dg{background:rgba(244,63,94,.14);color:var(--kirmizi)}

/* Boş durum */
#sas .bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:60px 20px;text-align:center;color:var(--sub)}
#sas .bos .ic{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 15%,var(--card3)),color-mix(in srgb,var(--mavi) 10%,var(--card3)));margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
#sas .bos .ic .material-icons-outlined{font-size:34px;color:var(--mor1)}
#sas .bos .b{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:5px}

/* Legend */
#sas .legend{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 14px;margin-top:12px;display:flex;flex-wrap:wrap;gap:12px;font-size:11.5px;color:var(--sub);align-items:center}
#sas .legend .lg{display:inline-flex;align-items:center;gap:5px}
#sas .legend .lg .d{width:9px;height:9px;border-radius:3px}
#sas .legend .lg b{color:var(--ink);font-weight:800}

@media(max-width:900px){
  #sas .hero-ust{flex-direction:column;align-items:stretch}
  #sas .hero .tutar{font-size:30px}
  #sas .hero-kpi{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:640px){
  #sas .pano{padding:0 0 24px}
  #sas .hero{padding:18px}
  #sas .hero .tutar{font-size:26px}
  #sas .hero-kpi{grid-template-columns:repeat(2,1fr)}
  #sas .alt-bar{flex-direction:column;align-items:stretch}
  #sas .kart-grid{grid-template-columns:1fr}
}

/* MODAL (yeni sipariş) — mevcut tasarım korundu, hafif modernize */
#saModal{display:none;position:fixed;inset:0;z-index:60;background:rgba(10,15,30,.55);backdrop-filter:blur(2px);align-items:flex-start;justify-content:center;padding:24px;overflow:auto}
#saModal.ac{display:flex}
#saModal .kutu{width:100%;max-width:820px;margin:auto;background:var(--card);border-radius:20px;padding:22px;box-shadow:0 24px 60px rgba(0,0,0,.35)}
#saModal .mu{display:flex;align-items:center;gap:12px;margin-bottom:16px}
#saModal .mi{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;display:flex;align-items:center;justify-content:center}
#saModal .mi .material-icons-outlined{font-size:22px}
#saModal .mt{font-size:17px;font-weight:800;color:var(--ink)}
#saModal .kapa{margin-left:auto;background:none;border:0;font-size:26px;color:var(--sub);cursor:pointer}
#sas .modal-row{display:grid;grid-template-columns:2fr 1fr 1fr;gap:10px}
#sas .modal-lbl{display:block;font-size:11.5px;font-weight:700;color:var(--sub);margin:0 0 5px;text-transform:uppercase;letter-spacing:.4px}
#sas .modal-inp{width:100%;height:42px;padding:0 13px;border:1.5px solid var(--line);border-radius:11px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box}
#sas .modal-inp:focus{border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.14)}
#sas .modal-sec-bas{display:flex;align-items:center;gap:9px;margin:16px 0 10px}
#sas .modal-sec-bas .cu{width:4px;height:16px;background:var(--mor1);border-radius:2px}
#sas .modal-sec-bas .b{font-size:14px;font-weight:800;color:var(--ink)}
#sas .modal-sec-bas .sp{flex:1}
#sas .modal-sec-bas .kalem-ekle{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border-radius:9px;background:color-mix(in srgb,var(--mor1) 10%,transparent);color:var(--mor1);border:0;font-size:12.5px;font-weight:800;cursor:pointer}
#sas .modal-sec-bas .kalem-ekle:hover{background:var(--mor1);color:#fff}
#sas .modal-sec-bas .kalem-ekle .material-icons-outlined{font-size:16px}
#sas .modal-toplam{display:flex;justify-content:flex-end;gap:22px;margin-top:12px;padding-top:12px;border-top:1px solid var(--line);flex-wrap:wrap;font-size:13px}
#sas .modal-toplam .ikinci{color:var(--sub)}
#sas .modal-toplam .genel{font-size:16px;font-weight:900;color:var(--mor1)}
#sas .modal-foot{display:flex;gap:10px;margin-top:18px}
#sas .modal-foot button{flex:1;height:46px;border:0;border-radius:12px;font-size:14px;font-weight:800;cursor:pointer;transition:transform .14s,box-shadow .14s;display:inline-flex;align-items:center;justify-content:center;gap:7px}
#sas .modal-foot .taslak{background:var(--card3);color:var(--ink)}
#sas .modal-foot .taslak:hover{background:var(--line)}
#sas .modal-foot .gonder{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.3)}
#sas .modal-foot .gonder:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(124,58,237,.4)}
#sas .modal-foot .material-icons-outlined{font-size:18px}
.sa-satir{display:grid;grid-template-columns:3fr 1.2fr 1.3fr 1.3fr 1fr 1fr auto;gap:6px;align-items:center;margin-bottom:6px}
.sa-satir select,.sa-satir input{padding:9px 10px !important;border:1.5px solid var(--line) !important;border-radius:9px !important;font-size:13px}
.sa-satir .sa-sil{padding:9px 10px;border:0;background:color-mix(in srgb,#F43F5E 10%,transparent);color:#F43F5E;border-radius:9px;cursor:pointer}
.sa-satir .sa-sil:hover{background:#F43F5E;color:#fff}
.sa-satir .sa-sil .material-icons-outlined{font-size:15px}
</style>
@endpush

@section('content')
@php
  $siparisler = $siparisler ?? collect();
  $tedarikciler = $tedarikciler ?? collect();
  $malzemeler = $malzemeler ?? collect();
  $birimler = $birimler ?? collect();
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
  $tl2 = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';

  $durumRenk = [
    'taslak'=>'#64748B','gonderildi'=>'#4F46E5','onaylandi'=>'#06B6D4',
    'kismi'=>'#F59E0B','tamamlandi'=>'#10B981','iptal'=>'#F43F5E'
  ];
  $durumAd = [
    'taslak'=>'Taslak','gonderildi'=>'Gönderildi','onaylandi'=>'Onaylandı',
    'kismi'=>'Kısmi Teslim','tamamlandi'=>'Tamamlandı','iptal'=>'İptal'
  ];
  $durumIk = [
    'taslak'=>'edit_note','gonderildi'=>'send','onaylandi'=>'verified',
    'kismi'=>'inventory','tamamlandi'=>'check_circle','iptal'=>'cancel'
  ];

  // Hesaplar
  $bugun = \Carbon\Carbon::today();
  $buAyBas = \Carbon\Carbon::now()->startOfMonth();
  $durumSay = ['taslak'=>0,'gonderildi'=>0,'onaylandi'=>0,'kismi'=>0,'tamamlandi'=>0,'iptal'=>0];
  $buAyToplam = 0; $buAyTamamlanan = 0;
  $gecikenSay = 0; $yakinSay = 0; $bekleyenOnay = 0;
  $toplamTedarikci = []; $toplamTutar = 0;

  foreach ($siparisler as $s) {
    $durumSay[$s->durum] = ($durumSay[$s->durum] ?? 0) + 1;
    $toplamTutar += (float) $s->genel_toplam;
    if (!empty($s->tedarikci_id)) $toplamTedarikci[(int)$s->tedarikci_id] = true;
    if ($s->tarih && \Carbon\Carbon::parse($s->tarih)->greaterThanOrEqualTo($buAyBas)) {
      if (!in_array($s->durum, ['iptal'])) $buAyToplam += (float) $s->genel_toplam;
      if ($s->durum === 'tamamlandi') $buAyTamamlanan++;
    }
    // Geciken: beklenen_teslim geçmiş + tamamlanmamış/iptal değil
    if (!empty($s->beklenen_teslim) && !in_array($s->durum, ['tamamlandi','iptal'])) {
      $bt = \Carbon\Carbon::parse($s->beklenen_teslim);
      $diff = $bugun->diffInDays($bt, false);
      if ($diff < 0) $gecikenSay++;
      elseif ($diff <= 2) $yakinSay++;
    }
    if (($s->onay_durum ?? '') === 'bekliyor') $bekleyenOnay++;
  }
@endphp

<div id="sas">
<div class="pano">

@if (session('sa_ok'))
  <div class="flash ok"><span class="material-icons-outlined" style="font-size:20px">check_circle</span>{{ session('sa_ok') }}</div>
@endif
@if (session('sa_hata'))
  <div class="flash err"><span class="material-icons-outlined" style="font-size:20px">error</span>{{ session('sa_hata') }}</div>
@endif

{{-- HERO ÖZET --}}
<div class="hero">
  <div class="hero-ust">
    <div>
      <div class="lbl"><span class="material-icons-outlined">shopping_cart_checkout</span>Bu Ay Satınalma</div>
      <div class="tutar">{{ $tl($buAyToplam) }}</div>
      <div class="info">
        <span class="mini-dot"><span class="material-icons-outlined" style="font-size:14px">receipt_long</span>{{ $siparisler->count() }} sipariş</span>
        <span class="mini-dot"><span class="material-icons-outlined" style="font-size:14px">groups</span>{{ count($toplamTedarikci) }} tedarikçi</span>
        @if($gecikenSay>0)<span class="mini-dot" style="background:rgba(244,63,94,.3)"><span class="material-icons-outlined" style="font-size:14px">running_with_errors</span>{{ $gecikenSay }} geciken</span>@endif
        @if($bekleyenOnay>0)<span class="mini-dot" style="background:rgba(245,158,11,.3)"><span class="material-icons-outlined" style="font-size:14px">hourglass_bottom</span>{{ $bekleyenOnay }} onay bekliyor</span>@endif
      </div>
    </div>
    <div class="hero-sag">
      <button type="button" class="ekle-btn" onclick="saAc()">
        <span class="material-icons-outlined">add_shopping_cart</span>Yeni Sipariş
      </button>
    </div>
  </div>
  <div class="hero-kpi">
    <div class="kpi-chip" onclick="sasFiltre('gonderildi')">
      <div class="l"><span class="material-icons-outlined">send</span>Gönderildi</div>
      <div class="v">{{ $durumSay['gonderildi'] }}</div>
    </div>
    <div class="kpi-chip" onclick="sasFiltre('onaylandi')">
      <div class="l"><span class="material-icons-outlined">verified</span>Onaylandı</div>
      <div class="v">{{ $durumSay['onaylandi'] }}</div>
    </div>
    <div class="kpi-chip" onclick="sasFiltre('kismi')">
      <div class="l"><span class="material-icons-outlined">inventory</span>Kısmi</div>
      <div class="v">{{ $durumSay['kismi'] }}</div>
    </div>
    <div class="kpi-chip" onclick="sasFiltre('tamamlandi')">
      <div class="l"><span class="material-icons-outlined">check_circle</span>Tamamlanan</div>
      <div class="v">{{ $durumSay['tamamlandi'] }}</div>
    </div>
    <div class="kpi-chip" onclick="sasFiltre('iptal')">
      <div class="l"><span class="material-icons-outlined">cancel</span>İptal</div>
      <div class="v">{{ $durumSay['iptal'] }}</div>
    </div>
  </div>
</div>

{{-- DURUM TAB BARI --}}
<div class="durum-bar" id="sasDurumBar">
  <div class="d-tab aktif" data-durum="hepsi" style="background:var(--ink);color:#fff;border-color:var(--ink)" onclick="sasDurumTik(this)">
    <span class="material-icons-outlined">apps</span>Tümü <span class="say">{{ $siparisler->count() }}</span>
  </div>
  @foreach(['taslak','gonderildi','onaylandi','kismi','tamamlandi','iptal'] as $dk)
    @php $dc = $durumRenk[$dk]; $di = $durumIk[$dk]; @endphp
    <div class="d-tab" data-durum="{{ $dk }}" style="--dc:{{ $dc }}" onclick="sasDurumTik(this)">
      <span class="material-icons-outlined" style="color:{{ $dc }}">{{ $di }}</span>
      {{ $durumAd[$dk] }} <span class="say">{{ $durumSay[$dk] }}</span>
    </div>
  @endforeach
</div>

{{-- ARAMA + HIZLI FİLTRE --}}
<div class="alt-bar">
  <div class="arama">
    <span class="material-icons-outlined">search</span>
    <input type="text" id="sasAra" placeholder="Sipariş no veya tedarikçi ara…" oninput="sasFiltreYap()">
  </div>
  <div class="hizli">
    @if($gecikenSay>0)
      <div class="h-chip" data-hizli="geciken" onclick="sasHizli(this)">
        <span class="material-icons-outlined">running_with_errors</span>Geciken ({{ $gecikenSay }})
      </div>
    @endif
    @if($yakinSay>0)
      <div class="h-chip" data-hizli="yakin" onclick="sasHizli(this)">
        <span class="material-icons-outlined">schedule</span>2 gün içinde ({{ $yakinSay }})
      </div>
    @endif
    @if($bekleyenOnay>0)
      <div class="h-chip" data-hizli="onay" onclick="sasHizli(this)">
        <span class="material-icons-outlined">hourglass_bottom</span>Onay bekliyor ({{ $bekleyenOnay }})
      </div>
    @endif
  </div>
</div>

{{-- SEÇİLİ FİLTRE ÖZETİ --}}
<div class="ozet-bar" id="sasOzet" style="display:none">
  <span><span class="material-icons-outlined" style="font-size:16px;vertical-align:-3px;color:var(--sub2)">filter_list</span> <b id="sasOzetSay">0</b> sipariş görünüyor</span>
  <span>Toplam: <b id="sasOzetTutar">0 ₺</b></span>
</div>

{{-- SİPARİŞ KARTLARI --}}
@if ($siparisler->isEmpty())
  <div class="bos">
    <div class="ic"><span class="material-icons-outlined">shopping_cart</span></div>
    <div class="b">Henüz satınalma siparişi yok</div>
    <div>Yukarıdaki "Yeni Sipariş" ile başla.</div>
  </div>
@else
  <div class="kart-grid" id="sasListe">
    @foreach ($siparisler as $s)
      @php
        $dr = $durumRenk[$s->durum] ?? '#64748B';
        $da = $durumAd[$s->durum] ?? $s->durum;
        $di = $durumIk[$s->durum] ?? 'help';
        $tedHarf = mb_strtoupper(mb_substr($s->tedarikci ?? '?', 0, 1));
        $tarihFmt = $s->tarih ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : '—';
        // Beklenen teslim durumu
        $teslimCls = ''; $teslimTxt = ''; $teslimIk = 'event';
        if (!empty($s->beklenen_teslim) && !in_array($s->durum, ['tamamlandi','iptal'])) {
          $bt = \Carbon\Carbon::parse($s->beklenen_teslim);
          $diff = $bugun->diffInDays($bt, false);
          if ($diff < 0)      { $teslimCls = 'geciken'; $teslimTxt = abs($diff).' gün geciken'; $teslimIk = 'running_with_errors'; }
          elseif ($diff == 0) { $teslimCls = 'yakin';   $teslimTxt = 'Bugün teslim'; $teslimIk = 'today'; }
          elseif ($diff == 1) { $teslimCls = 'yakin';   $teslimTxt = 'Yarın teslim'; $teslimIk = 'event_available'; }
          elseif ($diff <= 7) { $teslimTxt = $diff.' gün sonra teslim'; $teslimIk = 'event_available'; }
          else                { $teslimTxt = $bt->format('d.m.Y').' teslim'; }
        } elseif (!empty($s->beklenen_teslim)) {
          $teslimTxt = \Carbon\Carbon::parse($s->beklenen_teslim)->format('d.m.Y').' teslim';
        }
        $onayDurum = $s->onay_durum ?? null;
        // Hizlı filtre data
        $hizliTag = '';
        if (!empty($s->beklenen_teslim) && !in_array($s->durum, ['tamamlandi','iptal'])) {
          $diff = $bugun->diffInDays(\Carbon\Carbon::parse($s->beklenen_teslim), false);
          if ($diff < 0) $hizliTag = 'geciken';
          elseif ($diff <= 2) $hizliTag = 'yakin';
        }
        if ($onayDurum === 'bekliyor') $hizliTag .= ' onay';
      @endphp
      <a href="/satinalma-siparis-detay?id={{ $s->id }}" class="s-kart"
         data-durum="{{ $s->durum }}"
         data-ara="{{ mb_strtolower($s->siparis_no . ' ' . ($s->tedarikci ?? '')) }}"
         data-hizli="{{ $hizliTag }}"
         data-tutar="{{ (float) $s->genel_toplam }}">
        <div class="strip" style="background:{{ $dr }}"></div>
        @if($onayDurum === 'onaylandi')
          <div class="onay-badge"><span class="material-icons-outlined">verified</span>Tedarikçi onayladı</div>
        @elseif($onayDurum === 'bekliyor')
          <div class="onay-badge bek"><span class="material-icons-outlined">hourglass_bottom</span>Onay bekliyor</div>
        @elseif($onayDurum === 'degisiklik')
          <div class="onay-badge dg"><span class="material-icons-outlined">edit</span>Değişiklik istendi</div>
        @endif

        <div class="ust">
          <div style="min-width:0">
            <div class="sn">{{ $s->siparis_no }}</div>
            <div class="sn-sub"><span class="material-icons-outlined">event</span>{{ $tarihFmt }}</div>
          </div>
          <span class="d-pill" style="background:color-mix(in srgb,{{ $dr }} 14%,transparent);color:{{ $dr }}">
            <span class="material-icons-outlined">{{ $di }}</span>{{ $da }}
          </span>
        </div>

        <div class="orta">
          <div class="av" style="background:linear-gradient(135deg,{{ $dr }},color-mix(in srgb,{{ $dr }} 70%,#000))">{{ $tedHarf }}</div>
          <div style="flex:1;min-width:0">
            <div class="ted-ad">{{ $s->tedarikci ?: 'Tedarikçi — belirsiz' }}</div>
            @if(!empty($s->siparis_no))<div class="ted-meta">#{{ $s->siparis_no }}</div>@endif
          </div>
        </div>

        <div class="alt-grid">
          <div>
            <div class="tutar">{{ $tl($s->genel_toplam) }}</div>
            <div class="tutar-l">genel toplam</div>
          </div>
          <div class="detay-btn">Detay<span class="material-icons-outlined">arrow_forward</span></div>
        </div>

        @if($teslimTxt)
          <div class="teslim {{ $teslimCls }}">
            <span class="material-icons-outlined">{{ $teslimIk }}</span>{{ $teslimTxt }}
          </div>
        @endif
      </a>
    @endforeach
  </div>

  <div class="bos" id="sasBos" style="display:none;margin-top:12px">
    <div class="ic"><span class="material-icons-outlined">search_off</span></div>
    <div class="b">Eşleşen sipariş yok</div>
    <div>Arama veya filtreyi temizle.</div>
  </div>

  <div class="legend">
    <span>Durum renkleri:</span>
    @foreach($durumRenk as $k=>$c)
      <span class="lg"><span class="d" style="background:{{ $c }}"></span><b>{{ $durumAd[$k] }}</b></span>
    @endforeach
  </div>
@endif

{{-- ===== YENİ SİPARİŞ MODAL ===== --}}
<div id="saModal">
  <div class="kutu">
    <div class="mu">
      <div class="mi"><span class="material-icons-outlined">add_shopping_cart</span></div>
      <div class="mt">Yeni Satınalma Siparişi</div>
      <button type="button" class="kapa" onclick="saKapat()">&times;</button>
    </div>
    <form method="POST" action="/satinalma-siparis/ekle" id="saForm">
      @csrf
      <input type="hidden" name="kalemler" id="saKalemler">
      <input type="hidden" name="gonder" id="saGonder" value="">
      <div class="modal-row">
        <div>
          <label class="modal-lbl">Tedarikçi</label>
          <select name="tedarikci_id" class="modal-inp"><option value="">— Seç —</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select>
        </div>
        <div><label class="modal-lbl">Sipariş Tarihi</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="modal-inp"></div>
        <div><label class="modal-lbl">Beklenen Teslim</label><input type="date" name="beklenen_teslim" class="modal-inp"></div>
      </div>

      <div class="modal-sec-bas">
        <span class="cu"></span><span class="b">Kalemler</span><span class="sp"></span>
        <button type="button" class="kalem-ekle" onclick="saSatirEkle()"><span class="material-icons-outlined">add</span>Kalem Ekle</button>
      </div>
      <div id="saSatirlar"></div>

      <div class="modal-toplam">
        <span class="ikinci">Ara Toplam: <b id="saAra">0,00 ₺</b></span>
        <span class="ikinci">KDV: <b id="saKdv">0,00 ₺</b></span>
        <span class="genel">Genel: <span id="saGenel">0,00 ₺</span></span>
      </div>

      <label class="modal-lbl" style="margin-top:14px">Not (opsiyonel)</label>
      <input type="text" name="notlar" placeholder="Sipariş notu…" class="modal-inp">

      <div class="modal-foot">
        <button type="submit" class="taslak" onclick="document.getElementById('saGonder').value=''">
          <span class="material-icons-outlined">save</span>Taslak Kaydet
        </button>
        <button type="submit" class="gonder" onclick="document.getElementById('saGonder').value='1'">
          <span class="material-icons-outlined">send</span>Oluştur & Gönder
        </button>
      </div>
    </form>
  </div>
</div>

</div>
</div>

<script>
const SA_MALZ = @json($malzemeler->map(fn($m)=>['id'=>(int)$m->id,'ad'=>$m->ad,'birim'=>(int)($m->temel_birim_id ?? 0)])->values());
const SA_BIRIM = @json($birimler->map(fn($b)=>['id'=>(int)$b->id,'ad'=>$b->ad,'ks'=>$b->kisaltma])->values());

function saAc(){ document.getElementById('saModal').classList.add('ac'); if(!document.querySelector('.sa-satir')) saSatirEkle(); }
function saKapat(){ document.getElementById('saModal').classList.remove('ac'); }
function saMalzOpt(){ return '<option value="">Malzeme…</option>'+SA_MALZ.map(m=>`<option value="${m.id}" data-birim="${m.birim}">${m.ad}</option>`).join(''); }
function saBirimOpt(){ return SA_BIRIM.map(b=>`<option value="${b.id}">${b.ks||b.ad}</option>`).join(''); }
function saSatirEkle(){
  const d=document.createElement('div'); d.className='sa-satir';
  d.innerHTML=`<select class="sa-m" onchange="saMalzChg(this)">${saMalzOpt()}</select>
    <input type="number" class="sa-mik" placeholder="Miktar" step="0.001" min="0" oninput="saHesap()">
    <select class="sa-b">${saBirimOpt()}</select>
    <input type="number" class="sa-f" placeholder="Birim fiyat" step="0.0001" min="0" oninput="saHesap()">
    <input type="number" class="sa-i" placeholder="İsk%" step="0.01" min="0" oninput="saHesap()">
    <input type="number" class="sa-k" placeholder="KDV%" step="0.01" min="0" value="10" oninput="saHesap()">
    <button type="button" class="sa-sil" onclick="this.parentElement.remove();saHesap()"><span class="material-icons-outlined">close</span></button>`;
  document.getElementById('saSatirlar').appendChild(d);
}
function saMalzChg(sel){ const b=sel.selectedOptions[0]?.dataset.birim; if(b){ const row=sel.closest('.sa-satir'); const bs=row.querySelector('.sa-b'); if(bs) bs.value=b; } }
function saHesap(){
  let ara=0,kdv=0;
  document.querySelectorAll('.sa-satir').forEach(r=>{
    const mik=parseFloat(r.querySelector('.sa-mik').value)||0, f=parseFloat(r.querySelector('.sa-f').value)||0;
    const iy=parseFloat(r.querySelector('.sa-i').value)||0, ky=parseFloat(r.querySelector('.sa-k').value)||0;
    const brut=mik*f, isk=brut*iy/100, net=brut-isk; ara+=net; kdv+=net*ky/100;
  });
  const f=v=>v.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺';
  document.getElementById('saAra').textContent=f(ara);
  document.getElementById('saKdv').textContent=f(kdv);
  document.getElementById('saGenel').textContent=f(ara+kdv);
}
document.getElementById('saForm')?.addEventListener('submit',function(e){
  const kal=[];
  document.querySelectorAll('.sa-satir').forEach(r=>{
    const mid=parseInt(r.querySelector('.sa-m').value)||0, mik=parseFloat(r.querySelector('.sa-mik').value)||0;
    if(!mid||mik<=0) return;
    kal.push({malzeme_id:mid,miktar:mik,alis_birim_id:parseInt(r.querySelector('.sa-b').value)||0,birim_fiyat:parseFloat(r.querySelector('.sa-f').value)||0,iskonto_yuzde:parseFloat(r.querySelector('.sa-i').value)||0,kdv_yuzde:parseFloat(r.querySelector('.sa-k').value)||0});
  });
  if(!kal.length){ e.preventDefault(); rkBilgi('En az bir geçerli kalem ekleyin.','wn'); return; }
  document.getElementById('saKalemler').value=JSON.stringify(kal);
});
document.getElementById('saModal').addEventListener('click',function(e){ if(e.target===this) saKapat(); });

// ===== LİSTE FİLTRELEME =====
function sasFiltre(durum){
  var tabs = document.querySelectorAll('#sasDurumBar .d-tab');
  tabs.forEach(t=>t.classList.remove('aktif'));
  tabs.forEach(t=>{
    t.style.background=''; t.style.color=''; t.style.borderColor='';
  });
  var sec = document.querySelector('#sasDurumBar .d-tab[data-durum="'+durum+'"]');
  if (sec) {
    sec.classList.add('aktif');
    var dc = sec.style.getPropertyValue('--dc') || 'var(--ink)';
    sec.style.background = dc; sec.style.color = '#fff'; sec.style.borderColor = dc;
  }
  // Hızlı filtreleri temizle
  document.querySelectorAll('.h-chip').forEach(c=>c.classList.remove('aktif'));
  sasFiltreYap();
}
function sasDurumTik(el){
  var d = el.dataset.durum;
  if (d === 'hepsi') {
    document.querySelectorAll('#sasDurumBar .d-tab').forEach(t=>{
      t.classList.remove('aktif');
      t.style.background=''; t.style.color=''; t.style.borderColor='';
    });
    el.classList.add('aktif');
    el.style.background='var(--ink)'; el.style.color='#fff'; el.style.borderColor='var(--ink)';
    document.querySelectorAll('.h-chip').forEach(c=>c.classList.remove('aktif'));
    sasFiltreYap();
  } else {
    sasFiltre(d);
  }
}
function sasHizli(el){
  // Toggle
  el.classList.toggle('aktif');
  // Hızlı filtre açıkken diğer durum filtresi tümü olsun
  if (el.classList.contains('aktif')) {
    sasFiltre('hepsi');
    el.classList.add('aktif'); // sasFiltre temizler, geri aç
    if (el.dataset.hizli === 'yakin') el.classList.add('amb');
  }
  sasFiltreYap();
}
function sasFiltreYap(){
  var q = (document.getElementById('sasAra')?.value || '').toLowerCase().trim();
  var durum = document.querySelector('#sasDurumBar .d-tab.aktif')?.dataset.durum || 'hepsi';
  var hizliler = Array.from(document.querySelectorAll('.h-chip.aktif')).map(c=>c.dataset.hizli);
  var gorunen = 0, toplam = 0;
  document.querySelectorAll('#sasListe .s-kart').forEach(k=>{
    var ok = true;
    if (q && !(k.dataset.ara||'').includes(q)) ok = false;
    if (durum !== 'hepsi' && k.dataset.durum !== durum) ok = false;
    if (hizliler.length && !hizliler.some(h => (k.dataset.hizli||'').includes(h))) ok = false;
    k.style.display = ok ? '' : 'none';
    if (ok) { gorunen++; toplam += parseFloat(k.dataset.tutar||0); }
  });
  var bos = document.getElementById('sasBos');
  if (bos) bos.style.display = (gorunen===0 && document.querySelectorAll('#sasListe .s-kart').length>0) ? 'block' : 'none';
  var ozet = document.getElementById('sasOzet');
  var isFiltered = (q || durum !== 'hepsi' || hizliler.length);
  if (ozet) {
    ozet.style.display = isFiltered ? 'flex' : 'none';
    document.getElementById('sasOzetSay').textContent = gorunen;
    document.getElementById('sasOzetTutar').textContent = toplam.toLocaleString('tr-TR',{maximumFractionDigits:0}) + ' ₺';
  }
}
</script>
@endsection

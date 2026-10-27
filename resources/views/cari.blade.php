@extends('layout.app')
@section('title', 'Cari / Açık Hesaplar')
@section('rk_ikon', 'account_balance_wallet')
@section('baslik', 'Cari / Açık Hesaplar')

@php
  $tipAd  = ['patron' => 'Patron', 'firma' => 'Firma', 'musteri' => 'Müşteri', 'personel' => 'Personel'];
  $tipRnk = ['patron' => '#7C3AED', 'firma' => '#4F46E5', 'musteri' => '#10B981', 'personel' => '#D97706'];
  $tipIkon= ['patron' => 'star', 'firma' => 'business', 'musteri' => 'person', 'personel' => 'badge'];

  $cariler    = $cariler    ?? [];
  $ozet       = $ozet       ?? ['toplam_alacak' => 0, 'adet' => 0];
  $detay      = $detay      ?? null;
  $hareketler = $hareketler ?? [];
  $aktifId    = $aktifId    ?? 0;

  $adet   = $ozet['adet'] ?? count($cariler);
  $tl     = fn($v) => number_format(round((float) $v), 0, ',', '.') . ' ₺';
  $rnk    = fn($t) => $tipRnk[$t] ?? '#64748B';

  $borcluSayi = collect($cariler)->filter(fn($c) => (float)($c['bakiye'] ?? 0) > 0)->count();
  $temizSayi  = max(0, $adet - $borcluSayi);
  $tipDag = [];
  foreach ($cariler as $c) { $t = $c['tip'] ?? 'diger'; $tipDag[$t] = ($tipDag[$t] ?? 0) + 1; }
@endphp

@section('rk_alt', $adet . ' cari hesap')

@push('head')
<style>
#cr{
  --mor1:#7C3AED;--mor2:#A855F7;--mavi:#4F46E5;--mavi2:#6366F1;
  --yesil:#10B981;--yesil2:#34D399;--kirmizi:#F43F5E;--kirmizi2:#FB7185;
  --amber:#F59E0B;--amber2:#FBBF24;--cyan:#06B6D4;
  --card:#fff;--card2:#f8fafc;--card3:#f1f5f9;--bg:#f6f7fb;
  --ink:#0f172a;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
  --sh1:0 2px 8px rgba(15,23,42,.06);--sh2:0 8px 24px rgba(15,23,42,.08);
}
body.koyu #cr{
  --card:#161d2e;--card2:#1b2235;--card3:#222a40;--bg:#0f1623;
  --ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
  --sh1:0 2px 8px rgba(0,0,0,.3);--sh2:0 8px 24px rgba(0,0,0,.35);
}
#cr{background:var(--bg);min-height:calc(100vh - 60px)}
#cr *{box-sizing:border-box}
#cr .pano{width:100%;padding:20px 22px 36px}

/* Flash */
#cr .flash{border-radius:14px;padding:12px 16px;margin-bottom:14px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:9px}
#cr .flash.ok{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(52,211,153,.08));color:#047857;border:1px solid rgba(16,185,129,.3)}
#cr .flash.err{background:linear-gradient(135deg,rgba(244,63,94,.14),rgba(251,113,133,.08));color:#be123c;border:1px solid rgba(244,63,94,.3)}

/* HERO ÖZET */
#cr .hero{background:linear-gradient(135deg,var(--mor1) 0%,var(--mavi) 100%);border-radius:22px;padding:24px 26px;margin-bottom:18px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 32px rgba(124,58,237,.25)}
#cr .hero::before{content:'';position:absolute;top:-100px;right:-80px;width:280px;height:280px;background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%);border-radius:50%;pointer-events:none}
#cr .hero::after{content:'';position:absolute;bottom:-60px;left:30%;width:200px;height:200px;background:radial-gradient(circle,rgba(255,255,255,.1),transparent 70%);border-radius:50%;pointer-events:none}
#cr .hero-grid{display:grid;grid-template-columns:1fr auto;gap:22px;align-items:center;position:relative}
#cr .hero-ana .lbl{font-size:12.5px;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px}
#cr .hero-ana .tutar{font-size:42px;font-weight:900;letter-spacing:-1px;line-height:1}
#cr .hero-ana .alt{font-size:13px;color:rgba(255,255,255,.75);margin-top:7px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
#cr .hero-ana .alt .mini-dot{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700}
#cr .hero-chips{display:flex;gap:10px;flex-wrap:wrap;position:relative;z-index:1}
#cr .hero-chip{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.25);border-radius:14px;padding:12px 16px;backdrop-filter:blur(10px);min-width:110px}
#cr .hero-chip .l{font-size:11px;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.6px;display:flex;align-items:center;gap:5px}
#cr .hero-chip .v{font-size:22px;font-weight:900;color:#fff;line-height:1;margin-top:4px}

/* GRID: list | detay */
#cr .cr-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:18px;align-items:start}

/* ARA ÇUBUK (arama + yeni) */
#cr .ustbar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
#cr .arama{flex:1;min-width:200px;position:relative}
#cr .arama .material-icons-outlined{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub2);font-size:20px}
#cr .arama input{width:100%;height:42px;padding:0 14px 0 40px;border:1.5px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;transition:border-color .15s,box-shadow .15s}
#cr .arama input:focus{outline:none;border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.14)}
#cr .filtrele{display:flex;gap:6px;flex-wrap:wrap}
#cr .filtre{padding:8px 13px;border-radius:10px;background:var(--card);border:1.5px solid var(--line);font-size:12.5px;font-weight:700;color:var(--sub);cursor:pointer;transition:all .14s;user-select:none}
#cr .filtre:hover{color:var(--ink);border-color:var(--sub2)}
#cr .filtre.aktif{background:var(--mor1);border-color:var(--mor1);color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.3)}
#cr .btn-ekle{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:12px;padding:11px 20px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(124,58,237,.3);transition:transform .14s,box-shadow .14s}
#cr .btn-ekle:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(124,58,237,.4)}
#cr .btn-ekle .material-icons-outlined{font-size:19px}

/* CARİ KART (modern liste öğesi) */
#cr .cari-list{display:flex;flex-direction:column;gap:10px}
#cr .cari-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;transition:transform .14s,box-shadow .14s,border-color .14s;cursor:pointer;text-decoration:none;color:inherit;position:relative;overflow:hidden}
#cr .cari-kart:hover{transform:translateY(-2px);box-shadow:var(--sh2);border-color:color-mix(in srgb,var(--mor1) 30%,var(--line))}
#cr .cari-kart.aktif{border-color:var(--mor1);box-shadow:0 0 0 2px rgba(124,58,237,.14),var(--sh2);background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 4%,var(--card)),var(--card))}
#cr .cari-kart .strip{position:absolute;left:0;top:0;bottom:0;width:4px}
#cr .av{width:46px;height:46px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:17px;font-weight:900;flex-shrink:0;position:relative}
#cr .av .material-icons-outlined{font-size:17px;position:absolute;bottom:-4px;right:-4px;width:19px;height:19px;background:var(--card);border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid var(--card)}
#cr .orta{min-width:0}
#cr .orta .ad{font-size:14.5px;font-weight:800;color:var(--ink);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#cr .orta .meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:5px}
#cr .roz{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:999px;font-size:11px;font-weight:700;line-height:1.2}
#cr .roz .material-icons-outlined{font-size:12px}
#cr .tel{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;color:var(--sub);font-weight:600}
#cr .tel .material-icons-outlined{font-size:13px;color:var(--sub2)}
#cr .sag{display:flex;flex-direction:column;align-items:flex-end;gap:3px;flex-shrink:0}
#cr .bakiye{font-size:16px;font-weight:900;line-height:1;letter-spacing:-.3px}
#cr .durum-rz{font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px}
#cr .durum-rz.borc{background:rgba(244,63,94,.14);color:var(--kirmizi)}
#cr .durum-rz.temiz{background:rgba(16,185,129,.14);color:var(--yesil)}

/* Boş durum */
#cr .bos{background:var(--card);border:1px dashed var(--line);border-radius:16px;padding:48px 20px;text-align:center;color:var(--sub)}
#cr .bos .material-icons-outlined{font-size:48px;color:var(--sub2);margin-bottom:10px;opacity:.5}
#cr .bos .b{font-size:14.5px;font-weight:700;color:var(--ink);margin-bottom:5px}
#cr .bos .a{font-size:12.5px;color:var(--sub)}

/* ===== DETAY PANELİ ===== */
#cr .det-sticky{position:sticky;top:12px}
#cr .det-hero{border-radius:18px;overflow:hidden;margin-bottom:14px;color:#fff;position:relative;padding:22px 22px 20px;box-shadow:var(--sh2)}
#cr .det-hero::before{content:'';position:absolute;top:-80px;right:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%);border-radius:50%}
#cr .det-hero-ust{display:flex;align-items:center;gap:12px;margin-bottom:14px;position:relative}
#cr .det-hero-ust .av2{width:52px;height:52px;border-radius:16px;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#fff;backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.3)}
#cr .det-hero-ust .ad{font-size:18px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:4px}
#cr .det-hero-ust .roz2{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;color:#fff}
#cr .det-hero-ust .roz2 .material-icons-outlined{font-size:13px}
#cr .det-hero .buyuk-tutar{font-size:36px;font-weight:900;color:#fff;letter-spacing:-1px;line-height:1;position:relative}
#cr .det-hero .lbl2{font-size:11.5px;color:rgba(255,255,255,.8);font-weight:700;text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px;position:relative}
#cr .det-hero .stat2{display:flex;gap:14px;margin-top:14px;flex-wrap:wrap;position:relative}
#cr .det-hero .stat2 .st{flex:1;min-width:100px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:11px;padding:9px 12px;backdrop-filter:blur(6px)}
#cr .det-hero .stat2 .st .sl{font-size:10.5px;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.5px}
#cr .det-hero .stat2 .st .sv{font-size:14.5px;font-weight:800;color:#fff;margin-top:3px;line-height:1.1}

#cr .det-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-bottom:12px;box-shadow:var(--sh1)}
#cr .baslik2{display:flex;align-items:center;gap:9px;margin-bottom:12px}
#cr .baslik2 .ik{width:28px;height:28px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
#cr .baslik2 .ik .material-icons-outlined{font-size:17px}
#cr .baslik2 .b{font-size:14px;font-weight:800;color:var(--ink)}
#cr .lbl{display:block;font-size:11.5px;font-weight:700;color:var(--sub);margin:0 0 5px;text-transform:uppercase;letter-spacing:.4px}
#cr .lbl.t2{margin-top:12px}
#cr .inp{width:100%;height:44px;border:1.5px solid var(--line);border-radius:11px;background:var(--card);color:var(--ink);font-size:14.5px;font-weight:700;padding:0 13px;outline:none;transition:border-color .15s,box-shadow .15s}
#cr .inp:focus{border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.14)}
#cr .inp::placeholder{color:var(--sub2);font-weight:600}

#cr .tipler{display:grid;grid-template-columns:repeat(3,1fr);gap:7px}
#cr .tip{padding:9px 4px;border-radius:10px;border:1.5px solid var(--line);background:var(--card);cursor:pointer;text-align:center;font-size:12.5px;font-weight:700;color:var(--sub);transition:all .14s;user-select:none}
#cr .tip:hover{color:var(--ink);border-color:var(--sub2)}
#cr .tip.sec{background:color-mix(in srgb,var(--mor1) 10%,var(--card));border-color:var(--mor1);color:var(--mor1)}

#cr .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;width:100%;height:46px;border:0;border-radius:12px;font-size:14px;font-weight:800;color:#fff;cursor:pointer;transition:transform .14s,box-shadow .14s;margin-top:14px}
#cr .btn:hover{transform:translateY(-1px)}
#cr .btn.yesil{background:linear-gradient(135deg,var(--yesil),#059669);box-shadow:0 4px 12px rgba(16,185,129,.3)}
#cr .btn.yesil:hover{box-shadow:0 6px 16px rgba(16,185,129,.4)}
#cr .btn.mor{background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 4px 12px rgba(124,58,237,.3)}
#cr .btn.mor:hover{box-shadow:0 6px 16px rgba(124,58,237,.4)}
#cr .btn .material-icons-outlined{font-size:19px}

/* Hareket satırı */
#cr .har{display:flex;align-items:center;gap:11px;padding:11px 13px;border-radius:12px;background:var(--card2);margin-bottom:7px;transition:background .12s}
#cr .har:hover{background:var(--card3)}
#cr .har:last-child{margin-bottom:0}
#cr .har .hi{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#cr .har .hi .material-icons-outlined{font-size:17px;color:#fff}
#cr .har.borc .hi{background:linear-gradient(135deg,var(--kirmizi),#E11D48)}
#cr .har.tah .hi{background:linear-gradient(135deg,var(--yesil),#059669)}
#cr .har .hb{flex:1;min-width:0}
#cr .har .hb .ad{font-size:13px;font-weight:700;color:var(--ink);line-height:1.2}
#cr .har .hb .z{font-size:11px;color:var(--sub);margin-top:2px}
#cr .har .ht{font-size:14px;font-weight:900;white-space:nowrap;letter-spacing:-.2px}

/* Boş detay */
#cr .det-bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:60px 24px;text-align:center;color:var(--sub)}
#cr .det-bos .ic{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 15%,var(--card3)),color-mix(in srgb,var(--mavi) 10%,var(--card3)));margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
#cr .det-bos .ic .material-icons-outlined{font-size:34px;color:var(--mor1)}
#cr .det-bos .b{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:5px}
#cr .det-bos .a{font-size:13px;color:var(--sub);max-width:280px;margin:0 auto;line-height:1.4}

/* MODAL (yeni cari) */
#crModal{display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(10,15,30,.55);backdrop-filter:blur(2px)}
#crModal.ac{display:flex;animation:crFi .2s ease}
@keyframes crFi{from{opacity:0}to{opacity:1}}
#crModal .kutu{width:100%;max-width:440px;background:var(--card);border-radius:20px;padding:0;box-shadow:0 24px 60px rgba(0,0,0,.35);overflow:hidden;transform:scale(.94);animation:crPp .25s cubic-bezier(.34,1.56,.64,1) forwards}
@keyframes crPp{to{transform:scale(1)}}
#crModal .mu{display:flex;align-items:center;gap:12px;padding:20px 22px 10px}
#crModal .mu .mi{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;color:#fff}
#crModal .mu .mi .material-icons-outlined{font-size:22px}
#crModal .mu .mb{font-size:16px;font-weight:800;color:var(--ink)}
#crModal .mb-form{padding:0 22px 20px}
#crModal .ma{display:flex;gap:10px;justify-content:flex-end;padding:14px 22px 20px;background:var(--card2)}
#crModal .vaz{height:42px;padding:0 18px;border:1.5px solid var(--line);background:var(--card);border-radius:11px;font-size:13.5px;font-weight:700;color:var(--ink);cursor:pointer}
#crModal .vaz:hover{background:var(--card3)}
#crModal .onay{height:42px;padding:0 20px;background:linear-gradient(135deg,var(--mor1),var(--mavi));border:0;border-radius:11px;color:#fff;font-size:13.5px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(124,58,237,.3)}

/* Responsive */
@media(max-width:1100px){#cr .cr-grid{grid-template-columns:1fr}#cr .det-sticky{position:static}}
@media(max-width:900px){
  #cr .pano{padding:16px 14px 30px}
  #cr .hero{padding:20px}
  #cr .hero-grid{grid-template-columns:1fr}
  #cr .hero-ana .tutar{font-size:34px}
  #cr .hero-chips{width:100%}
  #cr .hero-chip{flex:1;min-width:0}
}
@media(max-width:640px){
  #cr .hero-ana .tutar{font-size:28px}
  #cr .hero-chips{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;width:100%}
  #cr .hero-chip{min-width:0;padding:10px 12px}
  #cr .hero-chip .v{font-size:18px}
  #cr .cari-kart{grid-template-columns:auto 1fr;row-gap:10px}
  #cr .cari-kart .sag{grid-column:1 / -1;flex-direction:row;align-items:center;justify-content:space-between;padding-top:8px;border-top:1px dashed var(--line);width:100%}
  #cr .ustbar{flex-direction:column;align-items:stretch}
  #cr .btn-ekle{width:100%;justify-content:center}
  #cr .det-hero .buyuk-tutar{font-size:28px}
  #cr .tipler{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:420px){
  #cr .filtrele{overflow-x:auto;flex-wrap:nowrap;scrollbar-width:none}
  #cr .filtrele::-webkit-scrollbar{display:none}
  #cr .filtre{white-space:nowrap}
}
</style>
@endpush

@section('content')
<div id="cr">
<div class="pano">

@if (session('cari_ok'))
  <div class="flash ok"><span class="material-icons-outlined" style="font-size:20px">check_circle</span>{{ session('cari_ok') }}</div>
@endif
@if (session('cari_hata'))
  <div class="flash err"><span class="material-icons-outlined" style="font-size:20px">error</span>{{ session('cari_hata') }}</div>
@endif

{{-- HERO ÖZET --}}
<div class="hero">
  <div class="hero-grid">
    <div class="hero-ana">
      <div class="lbl">Toplam Açık Alacak</div>
      <div class="tutar">{{ $tl($ozet['toplam_alacak'] ?? 0) }}</div>
      <div class="alt">
        <span class="mini-dot"><span class="material-icons-outlined" style="font-size:14px">groups</span>{{ $adet }} hesap</span>
        @if($borcluSayi>0)<span class="mini-dot"><span class="material-icons-outlined" style="font-size:14px">warning</span>{{ $borcluSayi }} borçlu</span>@endif
        @if($temizSayi>0)<span class="mini-dot"><span class="material-icons-outlined" style="font-size:14px">verified</span>{{ $temizSayi }} temiz</span>@endif
      </div>
    </div>
    <div class="hero-chips">
      @foreach($tipDag as $t => $n)
        @php $r = $rnk($t); @endphp
        <div class="hero-chip">
          <div class="l"><span class="material-icons-outlined" style="font-size:13px">{{ $tipIkon[$t] ?? 'group' }}</span>{{ $tipAd[$t] ?? ucfirst($t) }}</div>
          <div class="v">{{ $n }}</div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<div class="cr-grid">

  {{-- SOL: ARAMA + LİSTE --}}
  <div>
    <div class="ustbar">
      <div class="arama">
        <span class="material-icons-outlined">search</span>
        <input type="text" id="crAra" placeholder="Cari ara…" oninput="crFiltre()">
      </div>
      <div class="filtrele">
        <div class="filtre aktif" data-f="hepsi" onclick="crTipFiltre(this)">Tümü</div>
        <div class="filtre" data-f="borc" onclick="crTipFiltre(this)">Borçlu</div>
        <div class="filtre" data-f="temiz" onclick="crTipFiltre(this)">Temiz</div>
      </div>
      <button class="btn-ekle" onclick="crYeniAc()"><span class="material-icons-outlined">person_add_alt</span>Yeni Cari</button>
    </div>

    @if (!count($cariler))
      <div class="bos">
        <span class="material-icons-outlined">account_balance_wallet</span>
        <div class="b">Henüz cari hesap yok</div>
        <div class="a">Yeni Cari butonuna basarak ilk hesabı oluştur.</div>
      </div>
    @else
      <div class="cari-list" id="crListe">
        @foreach ($cariler as $c)
          @php
            $bk = (float) ($c['bakiye'] ?? 0);
            $tip = $c['tip'] ?? '';
            $renk = $rnk($tip);
            $aktif = ($aktifId == ($c['id'] ?? 0));
            $borc = $bk > 0;
            $ilk = mb_strtoupper(mb_substr($c['ad'] ?? '?', 0, 1));
            $ikon = $tipIkon[$tip] ?? 'person';
          @endphp
          <a href="/cari?id={{ $c['id'] }}" class="cari-kart {{ $aktif ? 'aktif' : '' }}"
             data-ad="{{ mb_strtolower($c['ad'] ?? '') }}"
             data-tel="{{ $c['telefon'] ?? '' }}"
             data-durum="{{ $borc ? 'borc' : 'temiz' }}">
            <div class="strip" style="background:{{ $renk }}"></div>
            <div class="av" style="background:color-mix(in srgb,{{ $renk }} 18%,var(--card3));color:{{ $renk }}">
              {{ $ilk }}
              <span class="material-icons-outlined" style="color:{{ $renk }}">{{ $ikon }}</span>
            </div>
            <div class="orta">
              <div class="ad">{{ $c['ad'] ?? '' }}</div>
              <div class="meta">
                <span class="roz" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 12%,transparent)">
                  <span class="material-icons-outlined">{{ $ikon }}</span>{{ $tipAd[$tip] ?? $tip }}
                </span>
                @if(!empty($c['telefon']))
                  <span class="tel"><span class="material-icons-outlined">phone</span>{{ $c['telefon'] }}</span>
                @endif
              </div>
            </div>
            <div class="sag">
              <div class="bakiye" style="color:{{ $borc ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $tl($bk) }}</div>
              <div class="durum-rz {{ $borc ? 'borc' : 'temiz' }}">{{ $borc ? 'borç' : 'temiz' }}</div>
            </div>
          </a>
        @endforeach
      </div>
      <div class="bos" id="crBosFiltre" style="display:none;margin-top:10px">
        <span class="material-icons-outlined">search_off</span>
        <div class="b">Eşleşen cari yok</div>
        <div class="a">Arama ya da filtreyi temizleyin.</div>
      </div>
    @endif
  </div>

  {{-- SAĞ: DETAY --}}
  <div>
    <div class="det-sticky">
    @if ($detay)
      @php
        $bk = (float) ($detay['bakiye'] ?? 0);
        $borc = $bk > 0;
        $tip = $detay['tip'] ?? '';
        $renk = $rnk($tip);
        $ikon = $tipIkon[$tip] ?? 'person';
        $gbg = $borc
          ? 'linear-gradient(135deg,#DC2626 0%,#9F1239 100%)'
          : 'linear-gradient(135deg,'.$renk.' 0%,'.$rnk($tip).' 100%)';
        if (!$borc) $gbg = 'linear-gradient(135deg,#10B981 0%,#059669 100%)';
        $ilkD = mb_strtoupper(mb_substr($detay['ad'] ?? '?', 0, 1));
      @endphp

      <div class="det-hero" style="background:{{ $gbg }}">
        <div class="det-hero-ust">
          <div class="av2">{{ $ilkD }}</div>
          <div style="flex:1;min-width:0">
            <div class="ad">{{ $detay['ad'] ?? '' }}</div>
            <span class="roz2"><span class="material-icons-outlined">{{ $ikon }}</span>{{ $tipAd[$tip] ?? $tip }}</span>
          </div>
        </div>
        <div class="lbl2">{{ $borc ? 'Güncel Borç' : 'Hesap Temiz' }}</div>
        <div class="buyuk-tutar">{{ $tl($bk) }}</div>
        <div class="stat2">
          <div class="st"><div class="sl">Toplam Satış</div><div class="sv">{{ $tl($detay['toplam_borc'] ?? 0) }}</div></div>
          @if (isset($detay['toplam_tahsilat']))
            <div class="st"><div class="sl">Tahsilat</div><div class="sv">{{ $tl($detay['toplam_tahsilat']) }}</div></div>
          @endif
          <div class="st"><div class="sl">Hareket</div><div class="sv">{{ count($hareketler) }}</div></div>
        </div>
      </div>

      {{-- Tahsilat formu --}}
      <div class="det-kart">
        <div class="baslik2">
          <div class="ik" style="background:linear-gradient(135deg,var(--yesil),#059669)"><span class="material-icons-outlined">payments</span></div>
          <div class="b">Tahsilat · Borç Kapat</div>
        </div>
        <form method="POST" action="/cari/tahsilat">
          @csrf
          <input type="hidden" name="cari_id" value="{{ $aktifId }}">
          <label class="lbl">Tutar (₺)</label>
          <input type="number" name="tutar" step="0.01" min="0.01" required class="inp" value="{{ $borc ? round($bk) : '' }}" placeholder="örn. 500" inputmode="decimal">
          <label class="lbl t2">Ödeme Şekli</label>
          <div class="tipler" id="tahSekilKutu">
            @foreach (['nakit' => ['Nakit','payments'], 'havale' => ['Havale','account_balance'], 'kredi' => ['Kart','credit_card']] as $sk => [$sad,$sik])
              <label class="tip" id="tahTip_{{ $sk }}" onclick="crTahSekil('{{ $sk }}')">
                <input type="radio" name="odeme_sekli" value="{{ $sk }}" {{ $sk === 'nakit' ? 'checked' : '' }} style="display:none">
                {{ $sad }}
              </label>
            @endforeach
          </div>
          <button type="submit" class="btn yesil"><span class="material-icons-outlined">check_circle</span>Tahsil Et</button>
        </form>
      </div>

      {{-- Hareketler --}}
      <div class="det-kart">
        <div class="baslik2">
          <div class="ik" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"><span class="material-icons-outlined">history</span></div>
          <div class="b">Hesap Hareketleri</div>
        </div>
        @if (!count($hareketler))
          <div style="text-align:center;color:var(--sub);padding:18px 0;font-size:13px">Hareket yok.</div>
        @else
          @foreach ($hareketler as $h)
            @php
              $hBorc = (($h['tip'] ?? '') === 'borc');
              $hAd   = $hBorc ? 'Satış · Borç' : 'Tahsilat' . (!empty($h['odeme_sekli']) ? ' · ' . ucfirst($h['odeme_sekli']) : '');
            @endphp
            <div class="har {{ $hBorc ? 'borc' : 'tah' }}">
              <div class="hi"><span class="material-icons-outlined">{{ $hBorc ? 'arrow_upward' : 'arrow_downward' }}</span></div>
              <div class="hb">
                <div class="ad">{{ $hAd }}</div>
                <div class="z">{{ $h['zaman'] ?? '' }}</div>
              </div>
              <div class="ht" style="color:{{ $hBorc ? 'var(--kirmizi)' : 'var(--yesil)' }}">
                {{ $hBorc ? '+' : '−' }}{{ $tl($h['tutar'] ?? 0) }}
              </div>
            </div>
          @endforeach
        @endif
      </div>
    @else
      <div class="det-bos">
        <div class="ic"><span class="material-icons-outlined">account_balance_wallet</span></div>
        <div class="b">Cari seçili değil</div>
        <div class="a">Listeden bir hesap seç — detay, tahsilat ve geçmiş burada açılır.</div>
      </div>
    @endif
    </div>
  </div>

</div>

{{-- YENİ CARİ MODAL --}}
<div id="crModal" role="dialog" aria-modal="true" onclick="if(event.target===this)crYeniKapat()">
  <div class="kutu">
    <div class="mu">
      <div class="mi"><span class="material-icons-outlined">person_add_alt</span></div>
      <div class="mb">Yeni Cari Hesap</div>
    </div>
    <form method="POST" action="/cari/ekle" id="crYeniForm" class="mb-form">
      @csrf
      <label class="lbl">İsim / Ünvan</label>
      <input name="ad" required autofocus placeholder="örn. Et Dünyası" class="inp" id="crAdInp">
      <label class="lbl t2">Telefon (opsiyonel)</label>
      <input name="telefon" placeholder="05xx xxx xx xx" class="inp" inputmode="tel">
      <label class="lbl t2">Tip</label>
      <div class="tipler" id="crTipKutu">
        @foreach (['musteri' => 'Müşteri', 'firma' => 'Firma', 'personel' => 'Personel'] as $tk => $tad)
          <label class="tip" id="crTip_{{ $tk }}" onclick="crTip('{{ $tk }}')">
            <input type="radio" name="tip" value="{{ $tk }}" {{ $tk === 'musteri' ? 'checked' : '' }} style="display:none">
            {{ $tad }}
          </label>
        @endforeach
      </div>
      <div class="ma" style="margin:18px -22px -20px">
        <button type="button" class="vaz" onclick="crYeniKapat()">Vazgeç</button>
        <button type="submit" class="onay">Ekle</button>
      </div>
    </form>
  </div>
</div>

</div>
</div>

<script>
function crYeniAc(){ document.getElementById('crModal').classList.add('ac'); setTimeout(()=>document.getElementById('crAdInp')?.focus(),80); }
function crYeniKapat(){ document.getElementById('crModal').classList.remove('ac'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape') crYeniKapat(); });

function crTip(sec){
  ['musteri','firma','personel'].forEach(k=>{
    const el=document.getElementById('crTip_'+k); if(!el) return;
    const inp=el.querySelector('input');
    inp.checked = (k===sec);
    el.classList.toggle('sec', k===sec);
  });
}
function crTahSekil(sec){
  ['nakit','havale','kredi'].forEach(k=>{
    const el=document.getElementById('tahTip_'+k); if(!el) return;
    const inp=el.querySelector('input');
    inp.checked = (k===sec);
    el.classList.toggle('sec', k===sec);
  });
}
function crFiltre(){
  var q = (document.getElementById('crAra').value||'').toLowerCase().trim();
  var durumF = document.querySelector('.filtre.aktif')?.dataset.f || 'hepsi';
  var kartlar = document.querySelectorAll('#crListe .cari-kart');
  var gorunen = 0;
  kartlar.forEach(k=>{
    var ad = k.dataset.ad || '';
    var tel = (k.dataset.tel||'').toLowerCase();
    var durum = k.dataset.durum;
    var qOk = !q || ad.includes(q) || tel.includes(q);
    var dOk = durumF==='hepsi' || durumF===durum;
    var vis = qOk && dOk;
    k.style.display = vis ? '' : 'none';
    if (vis) gorunen++;
  });
  var bos = document.getElementById('crBosFiltre');
  if (bos) bos.style.display = (gorunen===0 && kartlar.length>0) ? 'block' : 'none';
}
function crTipFiltre(el){
  document.querySelectorAll('.filtre').forEach(f=>f.classList.remove('aktif'));
  el.classList.add('aktif');
  crFiltre();
}

(function(){
  crTip('musteri');
  crTahSekil('nakit');
})();
</script>
@endsection

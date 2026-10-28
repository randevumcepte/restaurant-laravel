@extends('layout.app')
@section('rk_ikon', 'groups')
@section('baslik', 'İnsan Kaynakları — Özet')
@section('title', 'İK Özet')

@push('head')
<style>
#iko{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--pembe:#EC4899;--turuncu:#F97316;--cyan:#06B6D4;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #iko{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#iko{background:var(--bg);min-height:calc(100vh - 60px)}#iko *{box-sizing:border-box}
#iko .pano{padding:0 0 24px}
#iko a{text-decoration:none;color:inherit}

#iko .selam-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
#iko .selam-ikon{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .selam-ikon .material-icons-outlined{color:#fff;font-size:24px}
#iko .selam-kart .ad{font-size:17px;font-weight:700;color:var(--ink);line-height:1.1}
#iko .selam-kart .sube{font-size:12.5px;color:var(--sub);margin-top:2px}

#iko .ai-panel{flex:1;min-width:240px;margin:0 10px;padding:2px 0 2px 14px;border-left:2px solid color-mix(in srgb,var(--mor1) 28%,transparent);display:flex;flex-direction:column;gap:4px}
#iko .ai-panel .aip-head{display:flex;align-items:center;gap:9px;margin-bottom:3px}
#iko .ai-panel .aip-orb{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 0 color-mix(in srgb,var(--mor1) 55%,transparent);animation:ikoAiOrb 2.2s ease-out infinite}
#iko .ai-panel .aip-orb .material-icons-outlined{font-size:18px;color:#fff}
@keyframes ikoAiOrb{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--mor1) 45%,transparent)}70%{box-shadow:0 0 0 9px transparent}100%{box-shadow:0 0 0 0 transparent}}
#iko .ai-panel .aip-baslik{font-size:13.5px;font-weight:800;color:var(--ink);letter-spacing:-.1px}
#iko .ai-panel .aip-oneri{display:flex;align-items:center;gap:9px;padding:5px 2px;transition:transform .14s ease,color .14s ease;cursor:pointer}
#iko .ai-panel .aip-oneri:hover{transform:translateX(3px)}
#iko .ai-panel .aip-oneri:hover .aip-mtn{color:var(--mor1)}
#iko .ai-panel .aip-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
#iko .ai-panel .aip-mtn{flex:1;font-size:12.5px;font-weight:600;color:var(--ink);line-height:1.35;transition:color .14s ease}
#iko .ai-panel .aip-bos{font-size:12.5px;font-weight:600;color:var(--sub);padding:2px 0}
#iko .ai-panel .aip-tumu{font-size:11.5px;font-weight:700;color:var(--mor1);align-self:flex-start;margin-top:2px;opacity:.9;transition:opacity .14s}
#iko .ai-panel .aip-tumu:hover{opacity:1}

#iko .selam-bordro{text-align:right;flex-shrink:0;background:linear-gradient(135deg,var(--mor1),var(--mavi));border-radius:16px;padding:12px 20px;color:#fff;box-shadow:0 6px 18px color-mix(in srgb,var(--mor1) 38%,transparent)}
#iko .selam-bordro.risk{background:linear-gradient(135deg,var(--kirmizi),#E11D48);box-shadow:0 6px 18px rgba(244,63,94,.4)}
#iko .selam-bordro.dikkat{background:linear-gradient(135deg,var(--amber),#D97706);box-shadow:0 6px 18px rgba(245,158,11,.4)}
#iko .selam-bordro .sh-lbl{font-size:12px;font-weight:700;color:rgba(255,255,255,.85);margin-bottom:4px;display:flex;align-items:center;gap:5px;justify-content:flex-end}
#iko .selam-bordro .sh-deger{font-size:30px;font-weight:900;color:#fff;line-height:1;letter-spacing:-.5px}
#iko .selam-bordro .sh-alt{font-size:12px;font-weight:800;margin-top:6px;color:#fff !important;background:rgba(255,255,255,.2);border-radius:20px;padding:2px 10px;display:inline-block}

#iko .uclu{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
#iko .k-mini{background:var(--card);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;display:flex;flex-direction:row;transition:transform .12s,box-shadow .12s}
#iko .k-mini:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.09)}
#iko .k-mini .cubuk{width:5px;flex-shrink:0}
#iko .k-mini .ic{padding:14px;flex:1;min-width:0}
#iko .kb-row{display:flex;align-items:center;gap:8px;margin-bottom:10px}
#iko .k-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .k-ikon .material-icons-outlined{font-size:17px}
#iko .k-label{font-size:12.5px;font-weight:600;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#iko .k-deger{font-size:27px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:8px}
#iko .k-alt{display:flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:var(--sub)}

#iko .hizli{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
#iko .bilgi-kart{background:var(--card);border-radius:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:13px;transition:transform .12s,box-shadow .12s}
#iko .bilgi-kart:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.08)}
#iko .bk-row{display:flex;align-items:center;gap:7px;margin-bottom:9px}
#iko .bk-ikon{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .bk-ikon .material-icons-outlined{font-size:15px}
#iko .bk-label{font-size:11.5px;font-weight:600;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#iko .bk-deger{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:3px}
#iko .bk-alt{font-size:11px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

#iko .kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px;margin-bottom:16px}
#iko .ucgen-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
#iko .ucgen-grid > .kart{margin-bottom:0}
#iko .kart-baslik{display:flex;align-items:center;gap:8px;margin-bottom:14px}
#iko .kb-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .kb-ikon .material-icons-outlined{font-size:17px}
#iko .kb-ad{font-size:15px;font-weight:700;color:var(--ink)}

#iko .bar{height:10px;border-radius:6px;background:var(--card2);overflow:hidden;margin-top:8px}
#iko .bar div{height:100%;border-radius:6px;transition:width .7s cubic-bezier(.22,.68,0,1.2)}

#iko .bek-satir{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line);transition:background .12s}
#iko .bek-satir:first-of-type{border-top:none}
#iko .roz-pastel{font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:999px;flex-shrink:0}

#iko .arac-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(116px,1fr));gap:10px;margin-bottom:16px}
#iko .arac{--c:#7C3AED;background:color-mix(in srgb,var(--c) 7%,var(--card));border:1px solid color-mix(in srgb,var(--c) 16%,transparent);border-radius:14px;box-shadow:0 2px 6px rgba(0,0,0,.04);padding:14px 8px;text-align:center;transition:transform .14s,box-shadow .14s,background .14s,border-color .14s;display:block}
#iko .arac:hover{transform:translateY(-2px);background:color-mix(in srgb,var(--c) 13%,var(--card));border-color:color-mix(in srgb,var(--c) 32%,transparent);box-shadow:0 6px 18px color-mix(in srgb,var(--c) 22%,transparent)}
#iko .arac .ai{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 7px;background:color-mix(in srgb,var(--c) 20%,var(--card));color:var(--c)}
#iko .arac .ai .material-icons-outlined{font-size:22px}
#iko .arac .al{font-size:11.5px;font-weight:600;color:var(--ink);line-height:1.25}

#iko .gundem{background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 8%,var(--card)),var(--card));border:1px solid color-mix(in srgb,var(--mor1) 22%,var(--line));border-radius:18px;padding:14px 16px;margin-bottom:16px}
#iko .gundem .gd-head{display:flex;align-items:center;gap:9px;margin-bottom:10px}
#iko .gundem .gd-head .gi{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;color:#fff}
#iko .gundem .gd-head .gi .material-icons-outlined{font-size:17px}
#iko .gundem .gd-head b{font-size:14.5px;color:var(--ink);font-weight:800}
#iko .gundem .gd-head small{color:var(--sub);font-size:12px}
#iko .gundem .gd-liste{display:flex;flex-direction:column;gap:7px}
#iko .gd-item{display:flex;align-items:center;gap:9px;padding:9px 12px;background:var(--card);border-radius:11px;border:1px solid var(--line);font-size:13px;color:var(--ink);line-height:1.3}
#iko .gd-item .gm{width:28px;height:28px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .gd-item .gm .material-icons-outlined{font-size:16px}
#iko .gd-item .gt{flex:1;min-width:0}
#iko .gd-item .ga{font-size:11.5px;font-weight:800;padding:4px 10px;border-radius:999px;flex-shrink:0;white-space:nowrap}

@media(max-width:1300px){#iko .ucgen-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){
  #iko .selam-kart{flex-wrap:wrap}
  #iko .ai-panel{order:3;flex-basis:100%;margin:8px 0 0;padding-left:0;border-left:none;border-top:2px solid color-mix(in srgb,var(--mor1) 28%,transparent);padding-top:10px}
  #iko .uclu{grid-template-columns:1fr}
  #iko .hizli{grid-template-columns:repeat(2,1fr)}
  #iko .ucgen-grid{grid-template-columns:1fr}
  #iko .k-deger{font-size:24px}
}
@media(max-width:620px){
  #iko .pano{padding:14px 14px 24px}
  #iko .selam-kart{padding:12px 14px}
  #iko .selam-bordro{text-align:left;padding:10px 14px;margin-top:8px;flex-basis:100%;display:flex;align-items:center;gap:12px}
  #iko .selam-bordro .sh-lbl{margin-bottom:0;justify-content:flex-start;font-size:11.5px}
  #iko .selam-bordro .sh-deger{font-size:22px;flex:1;text-align:right}
  #iko .selam-bordro .sh-alt{margin-top:0;font-size:11px}
  #iko .selam-kart .ad{font-size:15px}
  #iko .k-deger{font-size:22px}
  #iko .bk-deger{font-size:19px}
  #iko .arac-grid{grid-template-columns:repeat(auto-fit,minmax(96px,1fr));gap:8px}
  #iko .arac{padding:12px 6px}
  #iko .arac .ai{width:36px;height:36px}
  #iko .arac .ai .material-icons-outlined{font-size:19px}
  #iko .arac .al{font-size:11px}
}
@media(max-width:420px){#iko .hizli{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php
  $saat = (int) date('H');
  $selam = $saat < 12 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $selamIk = $saat < 18 ? 'wb_sunny' : 'nightlight';
  $tl0 = fn($v) => number_format((float)($v ?? 0), 0, ',', '.') . ' ₺';

  $kadro  = $veri['kadro']  ?? ['aktif'=>0,'yeni_giren'=>0,'ayrilan'=>0];
  $devam  = $veri['devam']  ?? ['beklenen'=>0,'gelen'=>null,'mesaide'=>null];
  $bordro = $veri['bordro'] ?? ['hakedis'=>0,'odenen'=>0,'net'=>0];
  $izin   = $veri['izin']   ?? ['bekleyen'=>0,'bugun_izinli'=>[]];
  $iscilikY = $veri['iscilik_yuzde'] ?? null;
  $iscilikS = $veri['iscilik_saglik'] ?? 'iyi';
  $dogum = $veri['dogum_gunleri'] ?? [];
  $yildnm = $veri['yildonumleri'] ?? [];
  $eksik = $veri['eksik_belge'] ?? [];
  $aiAks = $veri['ai_aksiyonlar'] ?? [];

  $bordroCls = $iscilikS === 'risk' ? 'risk' : ($iscilikS === 'dikkat' ? 'dikkat' : '');
  $iscilikC = $iscilikY === null ? 'var(--sub)' : ($iscilikY > 40 ? 'var(--kirmizi)' : ($iscilikY > 35 ? 'var(--amber)' : 'var(--yesil)'));

  $devamYuzde = ($devam['beklenen'] > 0 && $devam['gelen'] !== null) ? round($devam['gelen'] / $devam['beklenen'] * 100) : null;
  $devamC = $devamYuzde === null ? 'var(--sub)' : ($devamYuzde >= 90 ? 'var(--yesil)' : ($devamYuzde >= 70 ? 'var(--amber)' : 'var(--kirmizi)'));

  $araclar = [
    ['/personel','badge','Personel','#4F46E5'],
    ['/puantaj','schedule','Puantaj','#0EA5E9'],
    ['/ik-vardiya','calendar_month','Vardiya','#10B981'],
    ['/ik-izin','event_available','İzin','#F59E0B'],
    ['/ik-aday','how_to_reg','İşe Alım','#10B981'],
    ['/ik-risk','crisis_alert','Risk Radarı','#F97316'],
    ['/garson-performans','emoji_events','Performans','#F6CE63'],
    ['/ik-asistan','smart_toy','İK AI','#7C3AED'],
  ];

  $topOneriler = array_slice($aiAks, 0, 3);
  $rnkMap = ['mor'=>'var(--mor1)','mavi'=>'var(--mavi)','yesil'=>'var(--yesil)','kirmizi'=>'var(--kirmizi)','amber'=>'var(--amber)','turuncu'=>'var(--turuncu)','pembe'=>'var(--pembe)'];
  $rnk = fn($k) => $rnkMap[$k] ?? 'var(--mor1)';
  $gRnkMap = ['pembe'=>['#EC4899','rgba(236,72,153,.14)'], 'mor'=>['#7C3AED','rgba(124,58,237,.14)'], 'amber'=>['#F59E0B','rgba(245,158,11,.14)'], 'kirmizi'=>['#F43F5E','rgba(244,63,94,.14)'], 'turuncu'=>['#F97316','rgba(249,115,22,.14)'], 'yesil'=>['#10B981','rgba(16,185,129,.14)'], 'mavi'=>['#4F46E5','rgba(79,70,229,.14)']];
@endphp

<div id="iko">
<div class="pano">

  {{-- SELAM KART (hero) --}}
  <div class="selam-kart">
    <div class="selam-ikon"><span class="material-icons-outlined">{{ $selamIk }}</span></div>
    <div style="flex-shrink:0">
      <div class="ad">{{ $selam }}, İnsan Kaynakları 👥</div>
      <div class="sube">{{ $kadro['aktif'] }} aktif personel · bu ayın bordro özeti</div>
    </div>

    <div class="ai-panel">
      <div class="aip-head"><span class="aip-orb"><span class="material-icons-outlined">auto_awesome</span></span> <span class="aip-baslik">AI İK Asistanı</span></div>
      <div class="aip-body">
        @forelse ($topOneriler as $a)
          @php $url = $a['url'] ?? '/ik-asistan'; @endphp
          <a href="{{ $url }}" class="aip-oneri">
            <span class="aip-dot" style="background:{{ $rnk($a['renk'] ?? 'mor') }}"></span>
            <span class="aip-mtn">{{ $a['metin'] ?? '' }}</span>
          </a>
        @empty
          <div class="aip-bos"><span class="material-icons-outlined" style="color:var(--yesil);font-size:18px;vertical-align:-4px">check_circle</span> İK verileri sakin — acil aksiyon yok.</div>
        @endforelse
      </div>
      <a href="/ik-asistan" class="aip-tumu">AI'ya soru sor · tüm öneriler <span class="material-icons-outlined" style="font-size:15px;vertical-align:-3px">arrow_forward</span></a>
    </div>

    <a href="/personel" class="selam-bordro {{ $bordroCls }}">
      <div class="sh-lbl"><span class="material-icons-outlined" style="font-size:14px;vertical-align:-2px">payments</span> Bu Ay Bordro</div>
      <div class="sh-deger">{{ $tl0($bordro['hakedis']) }}</div>
      <div class="sh-alt">@if($iscilikY!==null) işçilik %{{ $iscilikY }} @if($iscilikS==='risk')· risk@elseif($iscilikS==='dikkat')· dikkat@endif @else işçilik hesaplanamadı @endif</div>
    </a>
  </div>

  {{-- GÜNÜN GÜNDEMİ --}}
  @if (!empty($gundem['maddeler']))
    <div class="gundem">
      <div class="gd-head">
        <div class="gi"><span class="material-icons-outlined">today</span></div>
        <div><b>Günün Gündemi</b> <small>· {{ $gundem['sayi'] }} madde bugün dikkat gerektiriyor</small></div>
      </div>
      <div class="gd-liste">
        @foreach ($gundem['maddeler'] as $m)
          @php [$gColor,$gBg] = $gRnkMap[$m['renk'] ?? 'mor'] ?? ['#7C3AED','rgba(124,58,237,.14)']; @endphp
          <div class="gd-item">
            <div class="gm" style="background:{{ $gBg }}"><span class="material-icons-outlined" style="color:{{ $gColor }}">{{ $m['ikon'] ?? 'info' }}</span></div>
            <div class="gt">{{ $m['metin'] ?? '' }}</div>
            @if (!empty($m['link']))
              <a href="{{ $m['link']['url'] }}" class="ga" style="color:{{ $gColor }};background:{{ $gBg }}">{{ $m['link']['lbl'] ?? 'Git' }} →</a>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  @endif

  {{-- 3lü büyük kart --}}
  <div class="uclu">
    <a href="/personel" class="k-mini">
      <div class="cubuk" style="background:var(--yesil)"></div>
      <div class="ic">
        <div class="kb-row"><div class="k-ikon" style="background:color-mix(in srgb,var(--yesil) 15%,transparent);color:var(--yesil)"><span class="material-icons-outlined">groups</span></div><span class="k-label">Aktif Personel</span></div>
        <div class="k-deger">{{ $kadro['aktif'] }}</div>
        <div class="k-alt" style="color:var(--sub)">
          @if($kadro['yeni_giren']>0)<span style="color:var(--yesil)">+{{ $kadro['yeni_giren'] }} yeni</span>@endif
          @if($kadro['ayrilan']>0) · <span style="color:var(--kirmizi)">-{{ $kadro['ayrilan'] }} ayrıldı</span>@endif
          @if(!$kadro['yeni_giren']&&!$kadro['ayrilan'])bu ay değişim yok@endif
        </div>
      </div>
    </a>
    <a href="/puantaj" class="k-mini">
      <div class="cubuk" style="background:{{ $devamC }}"></div>
      <div class="ic">
        <div class="kb-row"><div class="k-ikon" style="background:color-mix(in srgb,{{ $devamC }} 15%,transparent);color:{{ $devamC }}"><span class="material-icons-outlined">how_to_reg</span></div><span class="k-label">Bugün Devam</span></div>
        <div class="k-deger" style="color:{{ $devamC }}">{{ $devam['gelen'] ?? '—' }}@if($devam['gelen']!==null)<span style="font-size:16px;color:var(--sub);font-weight:600">/{{ $devam['beklenen'] }}</span>@endif</div>
        <div class="k-alt">@if($devamYuzde!==null)%{{ $devamYuzde }} geldi@else puantaj verisi yok@endif · <b style="color:var(--mavi)">{{ $devam['mesaide'] ?? '—' }}</b> mesaide</div>
      </div>
    </a>
    <a href="/ik-izin" class="k-mini">
      <div class="cubuk" style="background:{{ $izin['bekleyen']>0?'var(--amber)':'var(--sub2)' }}"></div>
      <div class="ic">
        <div class="kb-row"><div class="k-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">event_available</span></div><span class="k-label">Bekleyen İzin</span></div>
        <div class="k-deger" style="color:{{ $izin['bekleyen']>0?'var(--amber)':'var(--ink)' }}">{{ $izin['bekleyen'] }}</div>
        <div class="k-alt">@if($izin['bekleyen']>0)onay bekliyor@else talep yok@endif · <b>{{ count($izin['bugun_izinli']) }}</b> kişi bugün izinli</div>
      </div>
    </a>
  </div>

  {{-- 4lü küçük kart --}}
  <div class="hizli">
    <a href="/personel" class="bilgi-kart">
      <div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--mavi) 15%,transparent);color:var(--mavi)"><span class="material-icons-outlined">payments</span></div><span class="bk-label">Ödenen</span></div>
      <div class="bk-deger">{{ $tl0($bordro['odenen']) }}</div>
      <div class="bk-alt">bu ay</div>
    </a>
    <a href="/personel" class="bilgi-kart">
      <div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--kirmizi) 15%,transparent);color:var(--kirmizi)"><span class="material-icons-outlined">account_balance_wallet</span></div><span class="bk-label">Kalan Ödeme</span></div>
      <div class="bk-deger" style="color:{{ $bordro['net']>0?'var(--kirmizi)':'var(--ink)' }}">{{ $tl0($bordro['net']) }}</div>
      <div class="bk-alt">net</div>
    </a>
    <a href="/personel" class="bilgi-kart">
      <div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">badge</span></div><span class="bk-label">Eksik Özlük</span></div>
      <div class="bk-deger" style="color:{{ count($eksik)>0?'var(--amber)':'var(--ink)' }}">{{ count($eksik) }}</div>
      <div class="bk-alt">personel</div>
    </a>
    <a href="/ik-aday" class="bilgi-kart">
      <div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--yesil) 15%,transparent);color:var(--yesil)"><span class="material-icons-outlined">how_to_reg</span></div><span class="bk-label">Yeni Başvuru</span></div>
      @php $yb = collect($aiAks)->firstWhere('key','basvuru'); @endphp
      <div class="bk-deger">{{ $yb['adet'] ?? 0 }}</div>
      <div class="bk-alt">aday</div>
    </a>
  </div>

  {{-- İK Araçları --}}
  <div class="kart-baslik" style="margin:2px 2px 10px">
    <div class="kb-ikon" style="background:color-mix(in srgb,var(--mavi) 15%,transparent);color:var(--mavi)"><span class="material-icons-outlined">apps</span></div>
    <span class="kb-ad">İK Araçları</span>
  </div>
  <div class="arac-grid">
    @foreach ($araclar as [$u,$ik,$ad,$rk])
      <a href="{{ $u }}" class="arac" style="--c:{{ $rk }}">
        <div class="ai"><span class="material-icons-outlined">{{ $ik }}</span></div>
        <div class="al">{{ $ad }}</div>
      </a>
    @endforeach
  </div>

  {{-- İşçilik maliyeti bar --}}
  @if ($iscilikY !== null)
  <div class="kart">
    <div class="kart-baslik">
      <div class="kb-ikon" style="background:color-mix(in srgb,{{ $iscilikC }} 15%,transparent);color:{{ $iscilikC }}"><span class="material-icons-outlined">bar_chart</span></div>
      <span class="kb-ad">İşçilik / Ciro Oranı</span>
      <span style="margin-left:auto;font-weight:800;color:{{ $iscilikC }}">%{{ $iscilikY }} @if($iscilikS==='risk')· risk@elseif($iscilikS==='dikkat')· dikkat@else· sağlıklı bant@endif</span>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--sub)">
      <span>Hakediş: {{ $tl0($bordro['hakedis']) }}</span>
      <span>Ciro: {{ $tl0($veri['ciro_ay'] ?? 0) }}</span>
    </div>
    <div class="bar"><div style="width:{{ min(100,$iscilikY) }}%;background:{{ $iscilikC }}"></div></div>
    <div style="font-size:11px;color:var(--sub2);margin-top:6px">Hedef bant: %25–35 · %40 üzeri risk</div>
  </div>
  @endif

  {{-- Üçgen: Bugün izinli · Doğum günleri · Yıldönümü --}}
  <div class="ucgen-grid">
    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">beach_access</span></div><span class="kb-ad">Bugün İzinli</span><span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">{{ count($izin['bugun_izinli']) }} kişi</span></div>
      @forelse ($izin['bugun_izinli'] as $ad)
        <div class="bek-satir">
          <span class="material-icons-outlined" style="color:var(--amber);font-size:19px">event_available</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ad }}</div></div>
          <span class="roz-pastel" style="background:color-mix(in srgb,var(--amber) 14%,transparent);color:var(--amber)">izinli</span>
        </div>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Bugün izinli kimse yok ✓</div>
      @endforelse
    </div>

    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,var(--pembe) 15%,transparent);color:var(--pembe)"><span class="material-icons-outlined">cake</span></div><span class="kb-ad">Yaklaşan Doğum Günleri</span><span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">7 gün</span></div>
      @forelse ($dogum as $d)
        <div class="bek-satir">
          <span class="material-icons-outlined" style="color:var(--pembe);font-size:19px">cake</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $d['ad'] }}</div><div style="font-size:11.5px;color:var(--sub)">{{ $d['tarih'] }}</div></div>
          <span class="roz-pastel" style="background:color-mix(in srgb,var(--pembe) 14%,transparent);color:var(--pembe)">{{ $d['gun'] }}</span>
        </div>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Önümüzdeki haftada doğum günü yok.</div>
      @endforelse
    </div>

    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,var(--mor1) 15%,transparent);color:var(--mor1)"><span class="material-icons-outlined">celebration</span></div><span class="kb-ad">İşe Giriş Yıldönümü</span><span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">bu ay</span></div>
      @forelse ($yildnm as $y)
        <div class="bek-satir">
          <span class="material-icons-outlined" style="color:var(--mor1);font-size:19px">workspace_premium</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $y['ad'] }}</div><div style="font-size:11.5px;color:var(--sub)">{{ $y['tarih'] }}</div></div>
          <span class="roz-pastel" style="background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)">{{ $y['yil'] }}. yıl</span>
        </div>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Bu ay yıldönümü yok.</div>
      @endforelse
    </div>
  </div>

  {{-- Eksik özlük bilgisi --}}
  @if (count($eksik) > 0)
  <div class="kart">
    <div class="kart-baslik">
      <div class="kb-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">warning_amber</span></div>
      <span class="kb-ad">Özlük Bilgisi Eksik</span>
      <span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">{{ count($eksik) }} personel</span>
    </div>
    @foreach (array_slice($eksik, 0, 6) as $e)
      <a href="/personel" class="bek-satir">
        <span class="material-icons-outlined" style="color:var(--amber);font-size:19px">badge</span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $e['ad'] }}</div>
          <div style="font-size:11.5px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Eksik: {{ implode(', ', $e['eksik']) }}</div>
        </div>
        <span class="roz-pastel" style="background:color-mix(in srgb,var(--amber) 14%,transparent);color:var(--amber)">{{ count($e['eksik']) }} alan</span>
      </a>
    @endforeach
    @if (count($eksik) > 6)
      <a href="/personel" style="display:block;text-align:center;margin-top:10px;font-size:12.5px;color:var(--mor1);font-weight:700">+{{ count($eksik)-6 }} personel daha · Hepsini göster →</a>
    @endif
  </div>
  @endif

</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', $deptAd . ' Brifingi')
@section('title', $deptAd . ' Brifingi')

@push('head')
<style>
  /* ====== PATRON AI DASHBOARD ile BİREBİR aynı tasarım (full width · kompakt) ====== */
  #pai{max-width:none;width:100%;margin:0;--rose:#F56A8B;--pgreen:#2FBE94;--pamber:#F6A623;--pblue:#5B8DEF;--pred:#F5576C}
  #pai *{box-sizing:border-box}
  #pai .t-rose{background:rgba(245,106,139,.13);color:var(--rose)} #pai .t-green{background:rgba(47,190,148,.14);color:var(--pgreen)}
  #pai .t-amber{background:rgba(246,166,35,.15);color:var(--pamber)} #pai .t-blue{background:rgba(91,141,239,.14);color:var(--pblue)}
  #pai .t-red{background:rgba(245,87,108,.13);color:var(--pred)} #pai .t-mor{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}

  .pai-hero{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:11px}
  .pai-hero-ic{width:46px;height:46px;border-radius:13px;flex:none;display:flex;align-items:center;justify-content:center;color:#fff;box-shadow:0 10px 22px rgba(124,58,237,.25)}
  .pai-hero-ic .material-icons-outlined{font-size:24px}
  .pai-hero-selam{font-size:21px;font-weight:800;color:var(--ink);line-height:1.15;letter-spacing:-.5px}
  .pai-hero-alt{font-size:12px;color:var(--sub);margin-top:3px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-weight:500}
  .pai-badge{display:inline-flex;align-items:center;gap:4px;background:color-mix(in srgb,var(--mor1) 12%,var(--card));border:1px solid color-mix(in srgb,var(--mor1) 25%,var(--line));padding:2px 9px;border-radius:20px;font-weight:700;font-size:11px;color:var(--mor1)}
  .pai-hero-sag{margin-left:auto;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
  .pai-roz{display:inline-flex;align-items:center;gap:6px;background:var(--card);border:1px solid var(--line);color:var(--sub);padding:7px 12px;border-radius:12px;font-size:12px;font-weight:700}
  .pai-roz .material-icons-outlined{font-size:15px}

  .pai-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:11px;margin-bottom:11px}
  @media(max-width:560px){ .pai-kpis{grid-template-columns:repeat(2,1fr)} }
  .pai-k{min-width:0;background:var(--card);border:1px solid var(--line);border-radius:15px;padding:10px 12px;position:relative;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  a.pai-k{text-decoration:none;color:inherit;cursor:pointer;transition:transform .14s,box-shadow .14s,border-color .14s}
  a.pai-k:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(20,20,50,.09);border-color:var(--mor1)}
  a.pai-k .kgit{position:absolute;top:9px;right:10px;font-size:15px;color:var(--sub2);opacity:.45}
  a.pai-k:hover .kgit{color:var(--mor1);opacity:1}
  .pai-k .kic{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:7px}
  .pai-k .kic .material-icons-outlined{font-size:19px}
  .pai-k .kbuyuk{font-size:19px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .pai-k .kbuyuk small{font-size:12px;font-weight:700;color:var(--sub)}
  .pai-k .ketk{font-size:11.5px;color:var(--ink);font-weight:700;margin-top:5px}
  .pai-k .kalt{font-size:10px;color:var(--sub);margin-top:2px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

  .pai-mid{display:grid;grid-template-columns:1.72fr 1fr;gap:12px;margin-bottom:12px;align-items:start}
  .pai-mid>*{min-width:0}
  .pai-card,.pai-aipanel{background:var(--card);border:1px solid var(--line);border-radius:17px;padding:14px 15px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .pai-aipanel{background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 6%,var(--card)),var(--card) 60%);border-color:color-mix(in srgb,var(--mor1) 18%,var(--line))}
  .pai-card-head{display:flex;align-items:center;gap:9px;margin-bottom:11px}
  .pai-card-head .material-icons-outlined{font-size:20px;color:var(--mor1)}
  .pai-card-head .b{font-size:15px;font-weight:800;color:var(--ink)}
  .pai-card-head .cnt{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);border-radius:20px;padding:1px 9px;font-size:12px;font-weight:800}

  .pai-task-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .pai-task{display:flex;align-items:center;gap:11px;background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:9px 11px}
  .pai-task .tic{width:35px;height:35px;border-radius:10px;flex:none;display:flex;align-items:center;justify-content:center}
  .pai-task .tic .material-icons-outlined{font-size:17px}
  .pai-task .tmid{flex:1;min-width:0}
  .pai-task .trot{display:inline-block;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 7px;border-radius:20px}
  .pai-task .tbas{font-size:12px;font-weight:700;color:var(--ink);line-height:1.25;margin-top:3px}
  .pai-task .talt{font-size:10px;color:var(--sub);margin-top:1px;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word}
  .pai-task .tbtn{flex:none;border:0;border-radius:9px;padding:6px 11px;font-size:11px;font-weight:700;cursor:pointer;background:linear-gradient(135deg,#7B5CFA,#6A49F2);color:#fff;text-decoration:none;white-space:nowrap}

  .pai-aipanel .ust{display:flex;align-items:center;gap:10px;margin-bottom:9px}
  .pai-aipanel .rob{width:36px;height:36px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 8px 18px rgba(124,58,237,.3)}
  .pai-aipanel .rob .material-icons-outlined{color:#fff;font-size:20px}
  .pai-aipanel .bas{font-size:15px;font-weight:800;color:var(--ink)}
  .pai-aipanel .verdict{font-size:11.5px;line-height:1.55;color:var(--sub);white-space:pre-line;margin-bottom:11px}
  .pai-aipanel .yapar-bas{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--ink);margin-bottom:8px}
  .pai-aipanel .yapar-bas .mini{background:var(--mor1);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:6px}
  .pai-aiact{display:grid;grid-template-columns:1fr 1fr;gap:7px}
  .pai-aiact a{display:flex;align-items:center;gap:9px;text-align:left;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:8px;font-size:10.5px;font-weight:600;color:var(--ink);text-decoration:none;transition:.15s;line-height:1.3}
  .pai-aiact a:hover{border-color:var(--mor1)}
  .pai-aiact .aic{width:29px;height:29px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .pai-aiact .aic .material-icons-outlined{font-size:16px}
  .pai-sohbet-cta{display:flex;align-items:center;gap:11px;text-decoration:none;margin:0 0 12px;padding:11px 13px;border-radius:13px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 8px 20px rgba(124,58,237,.28);transition:transform .15s,box-shadow .15s}
  .pai-sohbet-cta:hover{transform:translateY(-1px);box-shadow:0 12px 26px rgba(124,58,237,.36)}
  .pai-sohbet-cta > .material-icons-outlined{font-size:22px;flex:none}
  .pai-sohbet-cta .ok{margin-left:auto;font-size:18px;opacity:.9}
  .pai-sohbet-cta b{display:block;font-size:13px;font-weight:800;line-height:1.2}
  .pai-sohbet-cta .sub{display:block;font-size:10.5px;opacity:.92;margin-top:2px;font-weight:500}

  /* DÜN YAPILANLAR bloğu (patron analiz bloğu stili) */
  .pai-blok{background:var(--card);border:1px solid var(--line);border-radius:17px;box-shadow:0 1px 3px rgba(20,20,50,.03);padding:15px 17px;margin-bottom:12px}
  .pai-blok h4{font-size:12px;font-weight:800;color:var(--sub2);letter-spacing:.4px;margin:0 0 12px;display:flex;align-items:center;gap:8px;text-transform:uppercase}
  .pai-blok h4 .material-icons-outlined{font-size:17px;color:var(--pgreen)}
  .pai-dun-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px dashed var(--line);font-size:12.5px}
  .pai-dun-sat:first-of-type{border-top:0;padding-top:0}
  .pai-dun-sat .ck{width:22px;height:22px;border-radius:50%;background:rgba(47,190,148,.15);color:var(--pgreen);display:flex;align-items:center;justify-content:center;flex:none}
  .pai-dun-sat .ck .material-icons-outlined{font-size:14px}
  .pai-dun-sat .mtn{flex:1;min-width:0;color:var(--ink);font-weight:600}
  .pai-dun-sat a{color:var(--mor1);font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
  .pai-dun-bos{color:var(--sub);font-size:12.5px;padding:6px 0}
  .pai-task-bos{color:var(--sub);padding:18px;text-align:center;font-size:13.5px}

  .pai-task .trot{display:inline-block;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 7px;border-radius:20px}
  .pai-sec-bas{display:flex;align-items:center;gap:9px;font-size:15px;font-weight:800;color:var(--ink);margin:14px 2px 10px}
  .pai-sec-bas .material-icons-outlined{font-size:20px;color:var(--mor1)}
  .pai-analiz-grid{display:flex;gap:11px;margin-bottom:12px;overflow-x:auto;padding-bottom:3px}
  .pai-analiz-grid::-webkit-scrollbar{height:6px} .pai-analiz-grid::-webkit-scrollbar-thumb{background:var(--line);border-radius:6px}
  .pai-an{flex:1 1 0;min-width:200px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--line);border-radius:14px;padding:12px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .pai-an-bas{display:flex;align-items:center;gap:11px;margin-bottom:9px}
  .pai-an-ic{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none}
  .pai-an-ic .material-icons-outlined{font-size:21px}
  .pai-an-bas .t{font-weight:800;font-size:14.5px;color:var(--ink)}
  .pai-an-bas .d{font-size:11.5px;color:var(--sub);font-weight:600}
  .pai-vd{font-size:10px;font-weight:800;padding:4px 11px;border-radius:20px;color:#fff;letter-spacing:.5px;margin-left:auto}
  .pai-yorum{font-size:13px;line-height:1.55;color:var(--ink);margin:2px 0 0}

  @media(max-width:1200px){ .pai-analiz-grid{grid-template-columns:repeat(2,1fr)} }
  @media(max-width:620px){ .pai-analiz-grid{grid-template-columns:1fr} }
  @media(max-width:900px){ .pai-mid{grid-template-columns:1fr} .pai-task-grid{grid-template-columns:1fr} .pai-aiact{grid-template-columns:1fr} }
  @media(max-width:640px){ .pai-hero-sag{margin-left:0;width:100%} .pai-hero-selam{font-size:19px} }
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="pai">
@php
  $saat = (int) now()->format('H');
  $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $adK = $ad ? trim(explode(' ', trim($ad))[0]) : '';
  // DEPARTMAN SORUMLUSU: onayını bekleyen satın alma talepleri (varsa üstte uyarı)
  $__onayN = 0;
  try {
    $__sid2 = function_exists('_webSubeId') ? _webSubeId() : null;
    $__bp2 = function_exists('_posPersonel') ? _posPersonel() : null;
    if ($__bp2 && $__sid2 && function_exists('_sorumluDepartmanIdleri')) {
      $__sd = _sorumluDepartmanIdleri($__sid2, $__bp2->id ?? 0);
      if ($__sd) $__onayN = (int) \Illuminate\Support\Facades\DB::table('satinalma_talepleri')->where('sube_id', $__sid2)->where('durum', 'departman_onay')->whereIn('departman_id', $__sd)->count();
    }
  } catch (\Throwable $e) {}
@endphp
@if($__onayN > 0)
<a href="/talep-onaylarim" style="display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,#F59E0B,#D97706);color:#fff;text-decoration:none;border-radius:14px;padding:13px 16px;margin-bottom:12px;font-weight:700;box-shadow:0 8px 20px rgba(245,158,11,.28)">
  <span class="material-icons-outlined">fact_check</span>
  <span><b>{{ $__onayN }}</b> satın alma talebi senin onayını bekliyor</span>
  <span class="material-icons-outlined" style="margin-left:auto">chevron_right</span>
</a>
@endif

{{-- ============ HERO ============ --}}
<div class="pai-hero">
  <div class="pai-hero-ic" style="background:linear-gradient(135deg,{{ $deptRenk }},color-mix(in srgb,{{ $deptRenk }} 68%,#000))"><span class="material-icons-outlined">{{ $deptIkon }}</span></div>
  <div style="min-width:160px">
    <div class="pai-hero-selam">{{ $selam }}{{ $adK ? ' '.$adK : '' }} 👋</div>
    <div class="pai-hero-alt">
      <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}</span><span>·</span>
      <span>{{ $deptAd }} sabah brifingi</span>
      <span class="pai-badge">⚙️ departman brifingi</span>
    </div>
  </div>
  <div class="pai-hero-sag">
    @foreach ($rozetler as $rz)
      <span class="pai-roz" @if(!empty($rz['renk']))style="color:{{ $rz['renk'] }}"@endif><span class="material-icons-outlined">{{ $rz['ikon'] }}</span>{{ $rz['metin'] }}</span>
    @endforeach
  </div>
</div>

@if (!empty($demo))
<div style="display:flex;align-items:center;gap:8px;background:color-mix(in srgb,var(--amber) 12%,var(--card));border:1px solid color-mix(in srgb,var(--amber) 40%,var(--line));border-radius:14px;padding:11px 15px;margin-bottom:12px;font-size:12.5px">
  <span class="material-icons-outlined" style="font-size:18px;color:var(--amber)">theater_comedy</span>
  <span style="flex:1;color:var(--sub)"><b style="color:var(--amber)">DEMO VERİ</b> — gerçek değil; sayfanın dolu halini gösterir.</span>
  <a href="/departman-brief{{ !empty($kod) ? '?dep='.$kod : '' }}" style="color:var(--mor1);font-weight:700;text-decoration:none;white-space:nowrap">Gerçek veriye dön →</a>
</div>
@endif

{{-- ============ 5 KPI ============ --}}
@if (count($kpis))
<div class="pai-kpis">
  @foreach ($kpis as $k)
    @php
      $kl = $k['link'] ?? null;
      $tint = $k['tint'] ?? 't-mor';
      $hx = ['t-rose'=>'#F56A8B','t-green'=>'#2FBE94','t-amber'=>'#F6A623','t-blue'=>'#5B8DEF','t-red'=>'#F5576C','t-mor'=>'#7C3AED'][$tint] ?? '#7C3AED';
    @endphp
    <{{ $kl ? 'a' : 'div' }} class="pai-k" style="border-top:3px solid {{ $hx }};background:linear-gradient(170deg,color-mix(in srgb,{{ $hx }} 9%,var(--card)),var(--card) 72%)"@if($kl) href="{{ $kl }}"@endif>
      @if($kl)<span class="material-icons-outlined kgit">chevron_right</span>@endif
      <div class="kic {{ $tint }}"><span class="material-icons-outlined">{{ $k['ikon'] }}</span></div>
      <div class="kbuyuk" style="color:{{ $hx }}">{!! $k['deger'] !!}</div>
      <div class="ketk">{{ $k['label'] }}</div>
      @if (!empty($k['alt']))<div class="kalt">{{ $k['alt'] }}</div>@endif
    </{{ $kl ? 'a' : 'div' }}>
  @endforeach
</div>
@endif

{{-- ============ SANTRAL & REZERVASYON AKIŞI (sadece Rezervasyon Satış) ============ --}}
@if (!empty($crmRez))
@php $cz = $crmRez; @endphp
<style>
  .crz-sec-bas{display:flex;align-items:center;gap:9px;font-size:15px;font-weight:800;color:var(--ink);margin:14px 2px 10px}
  .crz-sec-bas .material-icons-outlined{font-size:20px;color:var(--mor1)}
  .crz-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:12px;align-items:start}
  @media(max-width:1100px){.crz-grid{grid-template-columns:1fr 1fr}}
  @media(max-width:720px){.crz-grid{grid-template-columns:1fr}}
  .crz-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 15px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .crz-ch{display:flex;align-items:center;gap:9px;margin-bottom:11px}
  .crz-ch .material-icons-outlined{font-size:19px}
  .crz-ch .b{font-size:14px;font-weight:800;color:var(--ink)}
  .crz-ch .cnt{margin-left:auto;border-radius:20px;padding:1px 9px;font-size:12px;font-weight:800}
  .crz-r{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px dashed var(--line)}
  .crz-r:first-of-type{border-top:0}
  .crz-r .ri{flex:1;min-width:0}
  .crz-r .rt{font-size:13px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .crz-r .rd{font-size:11px;color:var(--sub);margin-top:1px}
  .crz-btn{flex:none;border:0;border-radius:9px;padding:6px 11px;font-size:11px;font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
  .crz-btn .material-icons-outlined{font-size:14px}
  .crz-onay{background:linear-gradient(135deg,#2FBE94,#1FA37C);color:#fff}
  .crz-dinle{background:var(--card2);border:1px solid var(--line);color:var(--mor1)}
  .crz-ara{background:linear-gradient(135deg,#5B8DEF,#3F6FD6);color:#fff}
  .crz-badge{flex:none;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;color:#fff;text-transform:uppercase;letter-spacing:.3px}
  .crz-bos{color:var(--sub);font-size:12.5px;padding:14px 4px;text-align:center}
  .crz-full{margin-bottom:12px}
  .crz-tag{flex:none;display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;color:#fff}
</style>
@php
  $durR = ['bekliyor'=>'#F59E0B','onaylandi'=>'#2FBE94','geldi'=>'#5B8DEF','gelmedi'=>'#F43F5E','iptal'=>'#94A3B8'];
  $durA = ['bekliyor'=>'Bekliyor','onaylandi'=>'Onaylı','geldi'=>'Geldi','gelmedi'=>'Gelmedi','iptal'=>'İptal'];
  $kanR = ['Santral'=>'#7C3AED','Panel'=>'#64748B','Telefon'=>'#0EA5E9','Uygulama'=>'#10B981','WhatsApp'=>'#22C55E'];
@endphp
<div class="crz-sec-bas"><span class="material-icons-outlined">call</span> Santral & Rezervasyon Akışı</div>
<div class="crz-grid">
  {{-- Santraldan gelen (onay bekleyen) --}}
  <div class="crz-card">
    <div class="crz-ch"><span class="material-icons-outlined" style="color:#7C3AED">call</span><span class="b">Santraldan gelen</span><span class="cnt" style="background:color-mix(in srgb,#7C3AED 14%,transparent);color:#7C3AED">{{ count($cz['santral'] ?? []) }} onay bekliyor</span></div>
    @forelse (($cz['santral'] ?? []) as $s)
      <div class="crz-r">
        <span class="crz-tag" style="background:#7C3AED">📞 SANTRAL</span>
        <div class="ri"><div class="rt">{{ $s['ad'] }} · {{ $s['kisi'] }} kişi</div><div class="rd">{{ !empty($s['bugun']) ? 'Bugün' : \Carbon\Carbon::parse($s['tarih'])->format('d.m') }} · {{ $s['saat'] }}{{ !empty($s['telefon']) ? ' · '.$s['telefon'] : '' }}</div></div>
        @if (!empty($s['ses']))<a class="crz-btn crz-dinle" href="{{ $s['ses'] }}" target="_blank"><span class="material-icons-outlined">play_circle</span>Dinle</a>@endif
        @if (!empty($s['id']))
        <form method="POST" action="/rezervasyon/durum" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $s['id'] }}"><input type="hidden" name="durum" value="onaylandi"><input type="hidden" name="tarih" value="{{ $s['tarih'] }}"><button class="crz-btn crz-onay" type="submit"><span class="material-icons-outlined">check</span>Onayla</button></form>
        @else<span class="crz-btn crz-onay"><span class="material-icons-outlined">check</span>Onayla</span>@endif
      </div>
    @empty
      <div class="crz-bos">Santraldan onay bekleyen rezervasyon yok. 👍</div>
    @endforelse
  </div>
  {{-- Kaçan çağrı — geri ara --}}
  <div class="crz-card">
    <div class="crz-ch"><span class="material-icons-outlined" style="color:#F43F5E">phone_missed</span><span class="b">Kaçan çağrı — geri ara</span><span class="cnt" style="background:color-mix(in srgb,#F43F5E 13%,transparent);color:#F43F5E">{{ count($cz['kacan'] ?? []) }}</span></div>
    @forelse (($cz['kacan'] ?? []) as $k)
      <div class="crz-r">
        <span class="crz-badge" style="background:{{ ($k['sonuc'] ?? '')==='bilgi' ? '#F59E0B' : '#F43F5E' }}">{{ ($k['sonuc'] ?? '')==='bilgi' ? 'bilgi aldı' : 'kaçan' }}</span>
        <div class="ri"><div class="rt">{{ $k['telefon'] }}</div><div class="rd">{{ $k['zaman'] }} · rezervasyona dönmedi</div></div>
        <a class="crz-btn crz-ara" href="tel:{{ $k['telefon'] }}"><span class="material-icons-outlined">call</span>Ara</a>
      </div>
    @empty
      <div class="crz-bos">Son 3 günde sonuçsuz/kaçan çağrı yok. 👍</div>
    @endforelse
  </div>
  {{-- Bugünün rezervasyonları (kanal rozetli) --}}
  <div class="crz-card">
  <div class="crz-ch"><span class="material-icons-outlined" style="color:#A855F7">event_available</span><span class="b">Bugünün rezervasyonları</span><span class="cnt" style="background:color-mix(in srgb,#A855F7 13%,transparent);color:#A855F7">{{ count($cz['bugun'] ?? []) }}</span><a href="/rezervasyon" style="margin-left:8px;font-size:11px;font-weight:700;color:var(--mor1);text-decoration:none">Tümü →</a></div>
  @forelse (($cz['bugun'] ?? []) as $b)
    <div class="crz-r">
      <div class="rt" style="flex:none;width:46px;font-variant-numeric:tabular-nums">{{ $b['saat'] }}</div>
      <div class="ri"><div class="rt">{{ $b['ad'] }} · {{ $b['kisi'] }} kişi</div></div>
      <span class="crz-badge" style="background:{{ $kanR[$b['kanal']] ?? '#64748B' }}">{{ !empty($b['santral']) ? '📞 '.$b['kanal'] : $b['kanal'] }}</span>
      <span class="crz-badge" style="background:{{ $durR[$b['durum']] ?? '#64748B' }}">{{ $durA[$b['durum']] ?? $b['durum'] }}</span>
    </div>
  @empty
    <div class="crz-bos">Bugün rezervasyon yok. Boş günü doldurma kampanyası düşünebilirsin.</div>
  @endforelse
  </div>
</div>
@endif

{{-- ============ ORTA: Bugün yapılacaklar (sol) + AI öneri (sağ) ============ --}}
<div class="pai-mid">
  <div class="pai-card">
    <div class="pai-card-head"><span class="material-icons-outlined">track_changes</span><span class="b">Bugün önceliklerin</span>@if (count($isler))<span class="cnt">{{ count($isler) }}</span>@endif</div>
    @if (count($isler))
    <div class="pai-task-grid">
      @foreach ($isler as $is)
        @php $rk = $is['renk'] ?? 'var(--mor1)'; $ak = $is['aksiyon'] ?? null; @endphp
        <div class="pai-task">
          <div class="tic" style="background:color-mix(in srgb,{{ $rk }} 13%,transparent);color:{{ $rk }}"><span class="material-icons-outlined">{{ $is['ikon'] ?? 'task_alt' }}</span></div>
          <div class="tmid">
            @if (!empty($is['grup']))<span class="trot" style="background:color-mix(in srgb,{{ $rk }} 14%,transparent);color:{{ $rk }}">{{ $is['grup'] }}</span>@endif
            <div class="tbas">{{ $is['baslik'] }}</div>
            @if (!empty($is['aciklama']))<div class="talt">{{ $is['aciklama'] }}</div>@endif
          </div>
          @if ($ak && !empty($ak['url']))<a class="tbtn" href="{{ $ak['url'] }}">{{ $ak['lbl'] ?? 'Aç' }}</a>@endif
        </div>
      @endforeach
    </div>
    @else<div class="pai-task-bos">✅ Bekleyen iş yok — her şey yolunda 👍</div>@endif
  </div>

  <div class="pai-aipanel">
    <div class="ust"><div class="rob"><span class="material-icons-outlined">smart_toy</span></div><div class="bas">{{ $deptAd }} AI ne diyor?</div></div>
    <div class="verdict">{!! $aiOzet !!}</div>
    @if (!empty($aiLink))
      <a href="{{ $aiLink['url'] }}" class="pai-sohbet-cta">
        <span class="material-icons-outlined">forum</span>
        <div style="flex:1;min-width:0"><b>{{ $aiLink['lbl'] }}</b><span class="sub">Departmanınla ilgili sor — yardımcı olayım</span></div>
        <span class="material-icons-outlined ok">arrow_forward</span>
      </a>
    @endif
    @if (count($hizli))
    <div class="yapar-bas"><span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">bolt</span> Hızlı erişim</div>
    <div class="pai-aiact">
      @foreach ($hizli as $h)
        <a href="{{ $h['url'] }}"><span class="aic"><span class="material-icons-outlined">{{ $h['ikon'] }}</span></span>{{ $h['lbl'] }}</a>
      @endforeach
    </div>
    @endif
  </div>
</div>

{{-- ============ DETAYLI AI ANALİZİ ============ --}}
@if (count($analiz))
<div class="pai-sec-bas"><span class="material-icons-outlined">analytics</span> Detaylı AI analizi</div>
<div class="pai-analiz-grid">
  @foreach ($analiz as $a)
    @php
      $dur = $a['durum'] ?? 'notr';
      $durRenk = ['risk'=>'#F43F5E','dikkat'=>'#F59E0B','iyi'=>'#10B981','notr'=>'#64748B'][$dur] ?? '#64748B';
      $durAd = ['risk'=>'RİSK','dikkat'=>'DİKKAT','iyi'=>'İYİ','notr'=>'NÖTR'][$dur] ?? 'NÖTR';
      $rk = $a['renk'] ?? 'var(--mor1)';
    @endphp
    <div class="pai-an" style="border-left-color:{{ $durRenk }}">
      <div class="pai-an-bas">
        <div class="pai-an-ic" style="background:color-mix(in srgb,{{ $rk }} 13%,transparent);color:{{ $rk }}"><span class="material-icons-outlined">{{ $a['ikon'] ?? 'insights' }}</span></div>
        <div><div class="t">{{ $a['baslik'] }}</div>@if(!empty($a['alt']))<div class="d">{{ $a['alt'] }}</div>@endif</div>
        <span class="pai-vd" style="background:{{ $durRenk }}">{{ $durAd }}</span>
      </div>
      <div class="pai-yorum">{{ $a['yorum'] ?? '' }}</div>
    </div>
  @endforeach
</div>
@endif

{{-- ============ DÜN YAPILANLAR ============ --}}
<div class="pai-blok">
  <h4><span class="material-icons-outlined">done_all</span> Dün Yapılanlar</h4>
  @forelse ($dunYapilanlar as $y)
    <div class="pai-dun-sat"><div class="ck"><span class="material-icons-outlined">check</span></div><div class="mtn">{{ $y['mtn'] }}</div>@if (!empty($y['link']))<a href="{{ $y['link'] }}">Aç →</a>@endif</div>
  @empty
    <div class="pai-dun-bos">Dün bu departmanda kayıtlı hareket yok.</div>
  @endforelse
</div>

</div>
</div>
@endsection

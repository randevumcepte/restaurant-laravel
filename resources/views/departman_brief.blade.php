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

  .pai-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:11px}
  .pai-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:10px 12px;position:relative;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .pai-k .kic{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:7px}
  .pai-k .kic .material-icons-outlined{font-size:19px}
  .pai-k .kbuyuk{font-size:19px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .pai-k .kbuyuk small{font-size:12px;font-weight:700;color:var(--sub)}
  .pai-k .ketk{font-size:11.5px;color:var(--ink);font-weight:700;margin-top:5px}
  .pai-k .kalt{font-size:10px;color:var(--sub);margin-top:2px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

  .pai-mid{display:grid;grid-template-columns:1.72fr 1fr;gap:12px;margin-bottom:12px;align-items:start}
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
  .pai-task .talt{font-size:10px;color:var(--sub);margin-top:1px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
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
  .pai-analiz-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:11px;margin-bottom:12px}
  .pai-an{background:var(--card);border:1px solid var(--line);border-left:4px solid var(--line);border-radius:14px;padding:12px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .pai-an-bas{display:flex;align-items:center;gap:11px;margin-bottom:9px}
  .pai-an-ic{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none}
  .pai-an-ic .material-icons-outlined{font-size:21px}
  .pai-an-bas .t{font-weight:800;font-size:14.5px;color:var(--ink)}
  .pai-an-bas .d{font-size:11.5px;color:var(--sub);font-weight:600}
  .pai-vd{font-size:10px;font-weight:800;padding:4px 11px;border-radius:20px;color:#fff;letter-spacing:.5px;margin-left:auto}
  .pai-yorum{font-size:13px;line-height:1.55;color:var(--ink);margin:2px 0 0}

  @media(max-width:1100px){ .pai-kpis{grid-template-columns:repeat(3,1fr)} .pai-analiz-grid{grid-template-columns:1fr} }
  @media(max-width:900px){ .pai-mid{grid-template-columns:1fr} .pai-task-grid{grid-template-columns:1fr} .pai-aiact{grid-template-columns:1fr} }
  @media(max-width:640px){ .pai-kpis{grid-template-columns:1fr 1fr} .pai-hero-sag{margin-left:0;width:100%} .pai-hero-selam{font-size:19px} }
  @media(max-width:420px){ .pai-kpis{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="pai">
@php
  $saat = (int) now()->format('H');
  $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $adK = $ad ? trim(explode(' ', trim($ad))[0]) : '';
@endphp

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
    <div class="pai-k">
      <div class="kic {{ $k['tint'] ?? 't-mor' }}"><span class="material-icons-outlined">{{ $k['ikon'] }}</span></div>
      <div class="kbuyuk">{!! $k['deger'] !!}</div>
      <div class="ketk">{{ $k['label'] }}</div>
      @if (!empty($k['alt']))<div class="kalt">{{ $k['alt'] }}</div>@endif
    </div>
  @endforeach
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

@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', $deptAd . ' Brifingi')
@section('title', $deptAd . ' Brifingi')

@push('head')
<style>
  /* ====== Departman Sabah Brifingi — satinalma_brief ile AYNI tasarım (pastel · kompakt) ====== */
  #sb{max-width:1200px;margin:0 auto;--rose:#F56A8B;--pgreen:#2FBE94;--pamber:#F6A623;--pblue:#5B8DEF;--pred:#F5576C}
  #sb *{box-sizing:border-box}
  .sb-hero{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:12px}
  .sb-hero-ic{display:flex;align-items:center;gap:13px;flex:1;min-width:200px}
  .sb-orb{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 10px 22px rgba(124,58,237,.25)}
  .sb-orb .material-icons-outlined{color:#fff;font-size:24px}
  .sb-selam{font-size:21px;font-weight:800;color:var(--ink);line-height:1.1;letter-spacing:-.5px;margin-bottom:3px}
  .sb-alt{font-size:12px;color:var(--sub);font-weight:500}
  .sb-rozetler{display:flex;gap:7px;flex-wrap:wrap}
  .sb-roz{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border:1px solid var(--line);color:var(--sub);padding:7px 12px;border-radius:12px;font-size:12px;font-weight:700}
  .sb-roz .material-icons-outlined{font-size:15px}

  .sb-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:12px}
  .sb-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:11px 12px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-k .kic{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
  .sb-k .kic .material-icons-outlined{font-size:19px}
  .sb-k .kv{font-size:19px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .sb-k .kv small{font-size:12px;font-weight:700;color:var(--sub)}
  .sb-k .kl{font-size:11.5px;color:var(--ink);font-weight:700;margin-top:5px}
  .sb-k .ks{font-size:10px;color:var(--sub);margin-top:2px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  #sb .t-rose{background:rgba(245,106,139,.13);color:var(--rose)} #sb .t-green{background:rgba(47,190,148,.14);color:var(--pgreen)}
  #sb .t-amber{background:rgba(246,166,35,.15);color:var(--pamber)} #sb .t-blue{background:rgba(91,141,239,.14);color:var(--pblue)}
  #sb .t-red{background:rgba(245,87,108,.13);color:var(--pred)} #sb .t-mor{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}

  .sb-mid{display:grid;grid-template-columns:1.6fr 1fr;gap:13px;margin-bottom:13px;align-items:start}
  @media(max-width:1100px){.sb-kpis{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:900px){.sb-mid{grid-template-columns:1fr}}
  @media(max-width:560px){.sb-kpis{grid-template-columns:1fr 1fr}}

  .sb-bugun-blok,.sb-dun-blok{background:var(--card);border:1px solid var(--line);border-radius:17px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-ch{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.2px}
  .sb-ch .ikon-ct{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;flex:none}
  .sb-ch .ikon-ct .material-icons-outlined{font-size:17px}
  .sb-ch .sayi{margin-left:auto;background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);padding:1px 9px;border-radius:20px;font-size:12px;font-weight:800}

  .sb-yap-sat{position:relative;background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px;margin-bottom:8px}
  .sb-yap-sat:last-child{margin-bottom:0}
  .sb-yap-sat.oncelik-yuksek{border-left:3px solid var(--pred)}
  .sb-yap-sat.oncelik-orta{border-left:3px solid var(--pamber)}
  .sb-yap-sat.oncelik-dusuk{border-left:3px solid var(--pblue)}
  .sb-yap-head{display:flex;align-items:center;gap:11px}
  .sb-yap-ikon{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex:none;color:#fff}
  .sb-yap-ikon .material-icons-outlined{font-size:19px}
  .sb-yap-ic{flex:1;min-width:0}
  .sb-yap-bas{font-size:13px;font-weight:700;color:var(--ink);line-height:1.3}
  .sb-yap-aci{font-size:11px;color:var(--sub);line-height:1.4;margin-top:2px}
  .sb-yap-onc-roz{font-size:9px;font-weight:900;padding:2px 8px;border-radius:20px;letter-spacing:.3px;flex:none;color:#fff}
  .sb-yap-onc-roz.yuksek{background:var(--pred)}.sb-yap-onc-roz.orta{background:var(--pamber)}.sb-yap-onc-roz.dusuk{background:var(--pblue)}
  .sb-yap-btn-grup{display:flex;gap:8px;justify-content:flex-end;align-items:center;margin-top:9px}
  .sb-btn-link{background:color-mix(in srgb,var(--pblue) 14%,transparent);color:var(--pblue);border:0;padding:8px 15px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;text-decoration:none}
  .sb-btn-link:hover{background:var(--pblue);color:#fff}
  .sb-btn-link .material-icons-outlined{font-size:15px}
  .sb-bugun-bos{text-align:center;padding:26px 10px;color:var(--sub);font-size:13px}
  .sb-bugun-bos .material-icons-outlined{font-size:40px;color:var(--pgreen);display:block;margin:0 auto 7px;opacity:.7}

  .sb-dun-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px dashed var(--line);font-size:12.5px}
  .sb-dun-sat:first-of-type{border-top:0;padding-top:0}
  .sb-dun-sat .check{width:22px;height:22px;border-radius:50%;background:rgba(47,190,148,.15);color:var(--pgreen);display:flex;align-items:center;justify-content:center;flex:none}
  .sb-dun-sat .check .material-icons-outlined{font-size:14px}
  .sb-dun-sat .mtn{flex:1;min-width:0;color:var(--ink);font-weight:600}
  .sb-dun-sat a{color:var(--mor1);font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
  .sb-dun-bos{text-align:center;padding:22px 10px;color:var(--sub);font-size:12.5px}

  .sb-aipanel{background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 7%,var(--card)),var(--card) 60%);border:1px solid color-mix(in srgb,var(--mor1) 18%,var(--line));border-radius:17px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-aipanel .ust{display:flex;align-items:center;gap:10px;margin-bottom:10px}
  .sb-aipanel .rob{width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 8px 18px rgba(124,58,237,.3)}
  .sb-aipanel .rob .material-icons-outlined{color:#fff;font-size:20px}
  .sb-aipanel .bas{font-size:15px;font-weight:800;color:var(--ink)}
  .sb-aipanel .verdict{font-size:12px;line-height:1.55;color:var(--sub);margin-bottom:12px}
  .sb-aipanel .verdict .boldv{font-weight:800;color:var(--mor1)}
  .sb-aipanel .yapar{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--ink);margin-bottom:8px}
  .sb-aiact{display:flex;flex-direction:column;gap:7px}
  .sb-aiact a{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:9px 11px;font-size:11.5px;font-weight:600;color:var(--ink);text-decoration:none;transition:.15s}
  .sb-aiact a:hover{border-color:var(--mor1)}
  .sb-aiact .aic{width:29px;height:29px;border-radius:9px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);display:flex;align-items:center;justify-content:center;flex:none}
  .sb-aiact .aic .material-icons-outlined{font-size:16px}
  .sb-ai-cta{display:flex;align-items:center;gap:10px;text-decoration:none;margin-bottom:11px;padding:10px 12px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 8px 18px rgba(124,58,237,.26)}
  .sb-ai-cta b{font-size:12.5px;font-weight:800}
  .sb-ai-cta .material-icons-outlined{font-size:19px}
  .sb-ai-cta .ok{margin-left:auto}

  .sb-mini-bar{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px 13px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-mini-bar .chip{display:inline-flex;align-items:center;gap:5px;background:var(--card2);border-radius:20px;padding:5px 11px;font-size:11.5px;color:var(--sub);font-weight:600}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="sb">
@php
  $saat = (int) now()->format('H');
  $selamZ = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $adK = $ad ? trim(explode(' ', trim($ad))[0]) : 'Ekip';
@endphp

{{-- HERO --}}
<div class="sb-hero">
  <div class="sb-hero-ic">
    <div class="sb-orb" style="background:linear-gradient(135deg,{{ $deptRenk }},color-mix(in srgb,{{ $deptRenk }} 70%,#000))"><span class="material-icons-outlined">{{ $deptIkon }}</span></div>
    <div style="flex:1;min-width:0">
      <div class="sb-selam">{{ $selamZ }}, {{ $adK }}! ☕</div>
      <div class="sb-alt">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} · {{ $deptAd }} Sabah Brifingi</div>
    </div>
  </div>
  <div class="sb-rozetler">
    @foreach ($rozetler as $rz)
      <span class="sb-roz" @if(!empty($rz['renk']))style="background:color-mix(in srgb,{{ $rz['renk'] }} 13%,transparent);border-color:transparent;color:{{ $rz['renk'] }}"@endif>
        <span class="material-icons-outlined">{{ $rz['ikon'] }}</span>{{ $rz['metin'] }}
      </span>
    @endforeach
  </div>
</div>

{{-- KPI --}}
@if (count($kpis))
<div class="sb-kpis">
  @foreach ($kpis as $k)
    <div class="sb-k">
      <div class="kic {{ $k['tint'] ?? 't-mor' }}"><span class="material-icons-outlined">{{ $k['ikon'] }}</span></div>
      <div class="kv">{!! $k['deger'] !!}</div>
      <div class="kl">{{ $k['label'] }}</div>
      @if (!empty($k['alt']))<div class="ks">{{ $k['alt'] }}</div>@endif
    </div>
  @endforeach
</div>
@endif

{{-- MID: Bugün Yapılacaklar + AI panel --}}
<div class="sb-mid">
  <div class="sb-bugun-blok">
    <div class="sb-ch"><span class="ikon-ct" style="background:linear-gradient(135deg,#7B5CFA,#4F46E5)"><span class="material-icons-outlined">track_changes</span></span> Bugün Yapılacaklar <span class="sayi">{{ count($bugunYapilacak) }}</span></div>
    @forelse ($bugunYapilacak as $y)
      <div class="sb-yap-sat oncelik-{{ $y['oncelik'] ?? 'orta' }}">
        <div class="sb-yap-head">
          <div class="sb-yap-ikon" style="background:{{ $y['renk'] ?? '#7C3AED' }}"><span class="material-icons-outlined">{{ $y['ikon'] ?? 'task_alt' }}</span></div>
          <div class="sb-yap-ic">
            <div class="sb-yap-bas">{{ $y['baslik'] }}</div>
            @if (!empty($y['aciklama']))<div class="sb-yap-aci">{{ $y['aciklama'] }}</div>@endif
          </div>
          <span class="sb-yap-onc-roz {{ $y['oncelik'] ?? 'orta' }}">{{ ['yuksek' => 'ACİL', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$y['oncelik'] ?? 'orta'] }}</span>
        </div>
        @if (!empty($y['url']))
        <div class="sb-yap-btn-grup">
          <a href="{{ $y['url'] }}" class="sb-btn-link"><span class="material-icons-outlined">arrow_forward</span> {{ $y['buton'] ?? 'Aç' }}</a>
        </div>
        @endif
      </div>
    @empty
      <div class="sb-bugun-bos">
        <span class="material-icons-outlined">check_circle</span>
        <div>Her şey yolunda — bugün için acil aksiyon yok.</div>
      </div>
    @endforelse
  </div>

  <div class="sb-aipanel">
    <div class="ust"><div class="rob"><span class="material-icons-outlined">smart_toy</span></div><div class="bas">{{ $deptAd }} AI ne diyor?</div></div>
    <div class="verdict">{!! $aiOzet !!}</div>
    @if (!empty($aiLink))
      <a href="{{ $aiLink['url'] }}" class="sb-ai-cta"><span class="material-icons-outlined">forum</span><b>{{ $aiLink['lbl'] }}</b><span class="material-icons-outlined ok">arrow_forward</span></a>
    @endif
    @if (count($hizli))
    <div class="yapar"><span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">bolt</span> Hızlı erişim</div>
    <div class="sb-aiact">
      @foreach ($hizli as $h)
        <a href="{{ $h['url'] }}"><span class="aic"><span class="material-icons-outlined">{{ $h['ikon'] }}</span></span>{{ $h['lbl'] }}</a>
      @endforeach
    </div>
    @endif
  </div>
</div>

{{-- DÜN YAPILANLAR --}}
<div class="sb-dun-blok" style="margin-bottom:13px">
  <div class="sb-ch"><span class="ikon-ct" style="background:linear-gradient(135deg,#2FBE94,#059669)"><span class="material-icons-outlined">done_all</span></span> Dün Yapılanlar</div>
  @forelse ($dunYapilanlar as $y)
    <div class="sb-dun-sat"><div class="check"><span class="material-icons-outlined">check</span></div><div class="mtn">{{ $y['mtn'] }}</div>@if (!empty($y['link']))<a href="{{ $y['link'] }}">Aç →</a>@endif</div>
  @empty
    <div class="sb-dun-bos">Dün bu departmanda kayıtlı hareket yok.</div>
  @endforelse
</div>

{{-- MİNİ ŞERİT --}}
@if (count($miniBar))
<div class="sb-mini-bar">
  @foreach ($miniBar as $c)<span class="chip">{!! $c !!}</span>@endforeach
</div>
@endif

</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'apartment')
@section('baslik', 'Departman Brifingi')
@section('title', $dep->ad . ' — Brifing')

@push('head')
<style>
  #dr{max-width:1060px;margin:0 auto;--rose:#F56A8B;--pgreen:#2FBE94;--pamber:#F6A623;--pblue:#5B8DEF;--pred:#F5576C}
  #dr *{box-sizing:border-box}
  #dr .card{background:var(--card);border:1px solid var(--line);border-radius:17px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03);margin-bottom:13px}
  #dr .ch{display:flex;align-items:center;gap:9px;font-size:15px;font-weight:800;color:var(--ink);margin-bottom:12px}
  #dr .ch .material-icons-outlined{font-size:20px;color:var(--mor1)}
  #dr .ch .link{margin-left:auto;font-size:12px;font-weight:700;color:var(--mor1);text-decoration:none}

  /* HERO */
  #dr .hero{display:flex;align-items:center;gap:13px;flex-wrap:wrap;margin-bottom:13px}
  #dr .hback{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:var(--card);color:var(--sub);display:flex;align-items:center;justify-content:center;text-decoration:none;flex:none}
  #dr .hic{width:50px;height:50px;border-radius:15px;flex:none;display:flex;align-items:center;justify-content:center}
  #dr .hic .material-icons-outlined{font-size:26px}
  #dr .hname{font-size:21px;font-weight:800;color:var(--ink);letter-spacing:-.4px;line-height:1.1}
  #dr .hsub{font-size:12px;color:var(--sub);margin-top:3px;font-weight:500}
  #dr .vd{font-size:10px;font-weight:800;padding:4px 12px;border-radius:20px;color:#fff;letter-spacing:.4px}
  #dr .href{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:var(--card);color:var(--sub);display:flex;align-items:center;justify-content:center;text-decoration:none;flex:none}

  /* AI PANEL */
  #dr .ai{background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 7%,var(--card)),var(--card) 62%);border:1px solid color-mix(in srgb,var(--mor1) 18%,var(--line));border-radius:18px;padding:16px;margin-bottom:13px}
  #dr .ai .ust{display:flex;align-items:center;gap:11px;margin-bottom:11px}
  #dr .ai .rob{width:40px;height:40px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 8px 18px rgba(124,58,237,.3)}
  #dr .ai .rob .material-icons-outlined{color:#fff;font-size:21px}
  #dr .ai .bas{font-size:15px;font-weight:800;color:var(--ink)}
  #dr .ai .yorum{font-size:13.5px;line-height:1.6;color:var(--ink)}
  #dr .oz{display:flex;flex-direction:column;gap:3px;border-radius:12px;padding:11px 13px;margin-top:11px;font-size:12.5px;line-height:1.5}
  #dr .oz.red{background:rgba(245,87,108,.09)} #dr .oz.soft{background:var(--card2)} #dr .oz.amber{background:rgba(246,166,35,.1)}
  #dr .oz b{font-weight:800}

  /* KPI */
  #dr .kpis{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:11px}
  #dr .k{background:var(--card2);border:1px solid var(--line);border-radius:14px;padding:12px 13px}
  #dr .k .v{font-size:20px;font-weight:800;color:var(--ink);letter-spacing:-.4px;font-variant-numeric:tabular-nums}
  #dr .k .l{font-size:11px;color:var(--sub);margin-top:3px;font-weight:600}

  /* KRİTİK */
  #dr .kr{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)}
  #dr .kr:last-child{border-bottom:0}
  #dr .kr .ki{width:30px;height:30px;border-radius:9px;background:rgba(245,87,108,.13);color:var(--pred);display:flex;align-items:center;justify-content:center;flex:none}
  #dr .kr .ki .material-icons-outlined{font-size:16px}
  #dr .kr .ka{flex:1;font-weight:600;font-size:13px;min-width:0}
  #dr .kr .km{font-size:11.5px;color:var(--sub)}
  #dr .kr .ko{font-size:10.5px;font-weight:800;background:color-mix(in srgb,var(--pblue) 14%,transparent);color:var(--pblue);border-radius:20px;padding:3px 9px;white-space:nowrap}
  #dr .btn{border:0;border-radius:11px;padding:9px 15px;font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
  #dr .btn .material-icons-outlined{font-size:17px}
  #dr .btn.red{background:var(--pred);color:#fff} #dr .btn.bos{background:var(--card);border:1px solid var(--line);color:var(--mor1)}
  #dr .chip{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);border-radius:11px;padding:8px 13px;font-size:12px;font-weight:700;text-decoration:none}
  #dr .chip .material-icons-outlined{font-size:16px}

  /* HAFTALIK BAR */
  #dr .bar{display:flex;align-items:flex-end;gap:9px;height:120px}
  #dr .bar .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;height:100%}
  #dr .bar .b .wrap{flex:1;display:flex;align-items:flex-end;width:64%}
  #dr .bar .b .col{width:100%;background:linear-gradient(180deg,var(--mor1),var(--mavi));border-radius:7px 7px 0 0;min-height:3px}
  #dr .bar .b .g{font-size:10px;color:var(--sub);font-weight:600}

  /* PERSONEL */
  #dr .pr{display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid var(--line)}
  #dr .pr:last-child{border-bottom:0}
  #dr .pr .pa{width:34px;height:34px;border-radius:50%;background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex:none}
  #dr .pr .pn{flex:1;font-weight:700;font-size:13px}
  #dr .pr .pu{font-size:11px;color:var(--sub);font-weight:500}
  #dr .pr .pb{font-size:11px;font-weight:700;color:var(--sub);background:var(--card2);border-radius:20px;padding:3px 10px}
  @media(max-width:560px){ #dr .kpis{grid-template-columns:1fr 1fr} #dr .hname{font-size:19px} }
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="dr">
  @php
    $vd = ['iyi'=>['İYİ','var(--pgreen)'],'notr'=>['NÖTR','var(--sub)'],'dikkat'=>['DİKKAT','var(--pamber)'],'risk'=>['RİSK','var(--pred)']];
    [$det,$drk] = $vd[$veri['durum']] ?? $vd['notr'];
    $maxH = max(1, max(array_map(fn($x)=>$x['deger'], $haftalik ?: [['deger'=>1]])));
    $aiVar = ($ai['kaynak'] ?? '') === 'ai' && !empty($ai['yorum']);
    $depRenk = $dep->renk ?: 'var(--mavi)';
  @endphp

  {{-- HERO --}}
  <div class="hero">
    <a href="/patron-ai" class="hback"><span class="material-icons-outlined" style="font-size:19px">arrow_back</span></a>
    <div class="hic" style="background:color-mix(in srgb,{{ $depRenk }} 14%,transparent);color:{{ $depRenk }}"><span class="material-icons-outlined">{{ $dep->ikon ?: 'apartment' }}</span></div>
    <div style="flex:1;min-width:0">
      <div class="hname">{{ $dep->ad }}</div>
      <div class="hsub">{{ $dep->grup }} · {{ \Carbon\Carbon::parse($tarih)->translatedFormat('d F') }} (dünkü brifing)</div>
    </div>
    <span class="vd" style="background:{{ $drk }}">{{ $det }}</span>
    <a href="/departman-rapor?id={{ $dep->id }}&force=1" class="href" title="AI'yı yenile"><span class="material-icons-outlined" style="font-size:18px">refresh</span></a>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- AI ANALİZİ --}}
  <div class="ai">
    <div class="ust"><div class="rob"><span class="material-icons-outlined">{{ $aiVar ? 'smart_toy' : 'insights' }}</span></div><div class="bas">{{ $aiVar ? 'AI Departman Analizi' : 'Durum Özeti' }}</div></div>
    @if ($aiVar)
      <div class="yorum">{{ $ai['yorum'] }}</div>
      @if (!empty($ai['sorunlar']))
        <div class="oz red"><b style="color:var(--pred)">🔴 Tespit edilen sorunlar</b>@foreach ($ai['sorunlar'] as $s)<div>• {{ $s }}</div>@endforeach</div>
      @endif
      @if (!empty($ai['oneriler']))
        <div class="oz soft"><b style="color:var(--ink)">✅ Öneriler</b>@foreach ($ai['oneriler'] as $o)<div style="color:var(--sub)">• {{ $o }}</div>@endforeach</div>
      @endif
    @else
      <div class="yorum">{{ $veri['ozet'] }}</div>
      @foreach (($veri['uyari'] ?? []) as $u)<div class="oz amber"><span>⚠️ {{ $u }}</span></div>@endforeach
      @if (config('services.resteos.patron_ai_v2', false) === false)
        <div class="rk-sub" style="font-size:11px;margin-top:9px">💡 Derin AI analizi için <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
      @endif
    @endif
  </div>

  {{-- KRİTİK MALZEMELER + AKSİYONLAR --}}
  @php
    $aks = $veri['aksiyonlar'] ?? [];
    $acilVar = collect($aks)->firstWhere('tip', 'acil_siparis');
    $linkler = array_values(array_filter($aks, fn ($a) => ($a['tip'] ?? '') === 'link'));
    $renkMap = ['kirmizi'=>'var(--pred)','amber'=>'var(--pamber)','mavi'=>'var(--pblue)','yesil'=>'var(--pgreen)','mor'=>'var(--mor1)'];
  @endphp
  @if ($acilVar && !empty($veri['kritik_liste']))
  <div class="card" style="border-left:4px solid var(--pred)">
    <div class="ch" style="color:var(--pred)"><span class="material-icons-outlined" style="color:var(--pred)">warning</span> Kritik Malzemeler — Acil Sipariş ({{ count($veri['kritik_liste']) }})</div>
    <div style="margin-bottom:12px">
      @foreach ($veri['kritik_liste'] as $k)
        <div class="kr"><div class="ki"><span class="material-icons-outlined">inventory_2</span></div><div class="ka">{{ $k['ad'] }}</div><span class="km">{{ $k['mevcut'] }}/{{ $k['kritik'] }} {{ $k['birim'] }}</span><span class="ko">öneri {{ $k['oneri'] }} {{ $k['birim'] }}</span></div>
      @endforeach
    </div>
    <div style="display:flex;gap:9px;flex-wrap:wrap">
      <a href="/acil-siparis-hazirla" class="btn red"><span class="material-icons-outlined">shopping_cart_checkout</span> Hemen Sipariş Hazırla</a>
      <form method="POST" action="/fiyat-arastir" style="display:inline" onsubmit="return rkOnayForm(this, 'Bu ürünlere bağlı tedarikçilere e-posta + WhatsApp ile fiyat teklifi isteği gönderilsin mi?', 'var(--mor1)', 'Evet, fiyat iste')">
        @csrf<input type="hidden" name="geri" value="/departman-rapor?id={{ $dep->id }}"><input type="hidden" name="dep" value="{{ $dep->id }}">
        <button type="submit" class="btn bos"><span class="material-icons-outlined">request_quote</span> Fiyat Araştır</button>
      </form>
    </div>
  </div>
  @endif

  {{-- HIZLI AKSİYONLAR --}}
  @if (count($linkler))
  <div class="card">
    <div class="ch"><span class="material-icons-outlined">bolt</span> Hızlı Aksiyonlar</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach ($linkler as $a)
        <a href="{{ $a['url'] }}" class="chip" style="color:{{ $renkMap[$a['renk']??'mavi']??'var(--pblue)' }}"><span class="material-icons-outlined">{{ $a['ikon']??'arrow_forward' }}</span> {{ $a['lbl'] }} →</a>
      @endforeach
    </div>
  </div>
  @endif

  {{-- GÜNLÜK KPI --}}
  @if (!$veri['veri_yok'] && count($veri['kpi']))
  <div class="card">
    <div class="ch"><span class="material-icons-outlined">assessment</span> Dünkü Göstergeler
      @if ($veri['is_akisi'])<span class="vd" style="margin-left:4px;background:{{ $veri['is_akisi']==='yavaş'?'var(--pamber)':'var(--pgreen)' }}">İş akışı: {{ $veri['is_akisi'] }}</span>@endif
    </div>
    <div class="kpis">
      @foreach ($veri['kpi'] as $k)<div class="k"><div class="v">{{ $k[1] }}</div><div class="l">{{ $k[0] }}</div></div>@endforeach
    </div>
  </div>
  @endif

  {{-- HAFTALIK --}}
  @if (count($haftalik))
  <div class="card">
    <div class="ch"><span class="material-icons-outlined">bar_chart</span> Son 7 Gün</div>
    <div class="bar">
      @foreach ($haftalik as $h)
        <div class="b" title="{{ $h['deger'] }}"><div class="wrap"><div class="col" style="height:{{ max(3, round($h['deger']/$maxH*100)) }}%"></div></div><div class="g">{{ $h['gun'] }}</div></div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- PERSONEL --}}
  @if (count($personeller))
  <div class="card">
    <div class="ch"><span class="material-icons-outlined">groups</span> Departman Personeli <span class="cnt" style="background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);border-radius:20px;padding:1px 9px;font-size:12px;font-weight:800">{{ count($personeller) }}</span></div>
    @foreach ($personeller as $pp)
      @php $pad = trim($pp['ad']); $pini = mb_strtoupper(mb_substr($pad,0,1)); @endphp
      <div class="pr"><span class="pa">{{ $pini }}</span><div style="flex:1;min-width:0"><div class="pn">{{ $pp['ad'] }}</div><div class="pu">{{ $pp['unvan'] ?: $pp['rol'] }}</div></div>@if ($pp['adisyon'] > 0)<span class="pb">{{ $pp['adisyon'] }} adisyon</span>@endif</div>
    @endforeach
  </div>
  @else
  <div class="rk-sub" style="font-size:12px;padding:8px 2px">Bu departmana atanmış personel yok. <a href="/personel" style="color:var(--mor1)">Personel ekranından</a> departman ataması yapabilirsin.</div>
  @endif
</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'apartment')
@section('baslik', 'Departman Raporu')
@section('title', $dep->ad . ' — Rapor')

@push('head')
<style>
  #dr{max-width:900px}
  .dr-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .dr-h{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 10px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .dr-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
  .dr-kpi .k{background:var(--card2);border-radius:12px;padding:12px 14px}
  .dr-kpi .k .v{font-size:19px;font-weight:800;color:var(--ink)}
  .dr-kpi .k .l{font-size:11.5px;color:var(--sub);margin-top:2px}
  .dr-bar{display:flex;align-items:flex-end;gap:8px;height:120px}
  .dr-bar .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%}
  .dr-bar .b .col{width:100%;background:linear-gradient(180deg,var(--mor1),var(--mavi));border-radius:6px 6px 0 0;min-height:3px}
  .dr-bar .b .g{font-size:10px;color:var(--sub)}
  .dr-vd{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;letter-spacing:.4px}
  .dr-oz{display:flex;gap:8px;align-items:flex-start;font-size:13px;line-height:1.5;padding:9px 11px;border-radius:10px;margin-top:7px}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="dr">
  @php
    $vd = ['iyi'=>['İYİ','var(--yesil)'],'notr'=>['NÖTR','var(--sub)'],'dikkat'=>['DİKKAT','var(--amber)'],'risk'=>['RİSK','var(--kirmizi)']];
    [$det,$drk] = $vd[$veri['durum']] ?? $vd['notr'];
    $maxH = max(1, max(array_map(fn($x)=>$x['deger'], $haftalik ?: [['deger'=>1]])));
    $aiVar = ($ai['kaynak'] ?? '') === 'ai' && !empty($ai['yorum']);
  @endphp

  {{-- Başlık --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <a href="/patron-ai" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:30px;color:{{ $dep->renk ?: 'var(--mavi)' }}">{{ $dep->ikon ?: 'apartment' }}</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">{{ $dep->ad }}</div>
      <div class="rk-sub" style="font-size:12px">{{ $dep->grup }} · {{ $tarih }} (dünkü rapor)</div>
    </div>
    <span class="dr-vd" style="background:{{ $drk }}">{{ $det }}</span>
    <a href="/departman-rapor?id={{ $dep->id }}&force=1" class="rk-btn bos" style="padding:6px 10px" title="AI'yı yenile"><span class="material-icons-outlined" style="font-size:16px">refresh</span></a>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- AI ANALİZİ --}}
  <div class="dr-blok" style="border-left:4px solid var(--mor1);{{ $aiVar ? 'background:linear-gradient(135deg,rgba(124,58,237,.06),rgba(79,70,229,.06))' : '' }}">
    <div class="dr-h" style="color:var(--mor1)"><span class="material-icons-outlined" style="font-size:17px">{{ $aiVar ? 'auto_awesome' : 'insights' }}</span> {{ $aiVar ? 'AI Analizi' : 'Durum Özeti' }}</div>
    @if ($aiVar)
      <div style="font-size:14.5px;line-height:1.65;color:var(--ink)">{{ $ai['yorum'] }}</div>
      @if (!empty($ai['sorunlar']))
        <div class="dr-oz" style="background:rgba(244,63,94,.10);color:var(--kirmizi);flex-direction:column;align-items:stretch">
          <b>🔴 Tespit edilen sorunlar</b>
          @foreach ($ai['sorunlar'] as $s)<div>• {{ $s }}</div>@endforeach
        </div>
      @endif
      @if (!empty($ai['oneriler']))
        <div class="dr-oz" style="background:var(--card2);flex-direction:column;align-items:stretch">
          <b style="color:var(--ink)">✅ Öneriler</b>
          @foreach ($ai['oneriler'] as $o)<div style="color:var(--sub2)">• {{ $o }}</div>@endforeach
        </div>
      @endif
    @else
      <div style="font-size:14px;line-height:1.6;color:var(--ink)">{{ $veri['ozet'] }}</div>
      @foreach (($veri['uyari'] ?? []) as $u)
        <div class="dr-oz" style="background:rgba(245,158,11,.10);color:var(--amber)"><span>⚠️</span><span>{{ $u }}</span></div>
      @endforeach
      @if (config('services.resteos.patron_ai_v2', false) === false)
        <div class="rk-sub" style="font-size:11px;margin-top:8px">💡 Derin AI analizi için <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
      @endif
    @endif
  </div>

  {{-- KRİTİK MALZEMELER + AKSİYONLAR (çözüm odaklı) --}}
  @php
    $aks = $veri['aksiyonlar'] ?? [];
    $acilVar = collect($aks)->firstWhere('tip', 'acil_siparis');
    $linkler = array_values(array_filter($aks, fn ($a) => ($a['tip'] ?? '') === 'link'));
    $renkMap = ['kirmizi'=>'var(--kirmizi)','amber'=>'var(--amber)','mavi'=>'var(--mavi)','yesil'=>'var(--yesil)','mor'=>'var(--mor1)'];
  @endphp
  @if ($acilVar && !empty($veri['kritik_liste']))
  <div class="dr-blok" style="border-left:4px solid var(--kirmizi)">
    <div class="dr-h" style="color:var(--kirmizi)"><span class="material-icons-outlined" style="font-size:17px">warning</span> Kritik Malzemeler — Acil Sipariş Gerekli ({{ count($veri['kritik_liste']) }})</div>
    <div style="margin-bottom:12px">
      @foreach ($veri['kritik_liste'] as $k)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)">
          <span class="material-icons-outlined" style="color:var(--kirmizi);font-size:18px">inventory_2</span>
          <span style="flex:1;font-weight:600">{{ $k['ad'] }}</span>
          <span class="rk-sub" style="font-size:12px">mevcut {{ $k['mevcut'] }} / kritik {{ $k['kritik'] }} {{ $k['birim'] }}</span>
          <span class="rk-rozet" style="background:var(--mavi)">öneri {{ $k['oneri'] }} {{ $k['birim'] }}</span>
        </div>
      @endforeach
    </div>
    <form method="POST" action="/patron-ai/acil-siparis" onsubmit="return rkOnayForm(this, '{{ count($veri['kritik_liste']) }} kritik malzeme için ACİL satın alma talebi oluşturulsun ve Satın Alma\'ya iletilsin mi?', 'var(--kirmizi)', 'Evet, oluştur')">
      @csrf
      <input type="hidden" name="geri" value="/departman-rapor?id={{ $dep->id }}">
      <button type="submit" class="rk-btn" style="background:var(--kirmizi)"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">shopping_cart_checkout</span> Acil Sipariş Oluştur ({{ count($veri['kritik_liste']) }} kalem) → Satın Alma</button>
    </form>
  </div>
  @endif

  @if (count($linkler))
  <div class="dr-blok">
    <div class="dr-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">bolt</span> Hızlı Aksiyonlar</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach ($linkler as $a)
        <a href="{{ $a['url'] }}" class="rk-btn bos" style="padding:7px 14px;color:{{ $renkMap[$a['renk']??'mavi']??'var(--mavi)' }}"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">{{ $a['ikon']??'arrow_forward' }}</span> {{ $a['lbl'] }} →</a>
      @endforeach
    </div>
  </div>
  @endif

  {{-- GÜNLÜK KPI --}}
  @if (!$veri['veri_yok'] && count($veri['kpi']))
  <div class="dr-blok">
    <div class="dr-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">assessment</span> Dünkü Göstergeler
      @if ($veri['is_akisi'])<span class="rk-rozet" style="margin-left:6px;background:{{ $veri['is_akisi']==='yavaş'?'var(--amber)':'var(--yesil)' }}">İş akışı: {{ $veri['is_akisi'] }}</span>@endif
    </div>
    <div class="dr-kpi">
      @foreach ($veri['kpi'] as $k)
        <div class="k"><div class="v">{{ $k[1] }}</div><div class="l">{{ $k[0] }}</div></div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- HAFTALIK --}}
  @if (count($haftalik))
  <div class="dr-blok">
    <div class="dr-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">bar_chart</span> Son 7 Gün</div>
    <div class="dr-bar">
      @foreach ($haftalik as $h)
        <div class="b" title="{{ $h['deger'] }}">
          <div style="flex:1;display:flex;align-items:flex-end;width:60%"><div class="col" style="height:{{ max(3, round($h['deger']/$maxH*100)) }}%"></div></div>
          <div class="g">{{ $h['gun'] }}</div>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- PERSONEL --}}
  @if (count($personeller))
  <div class="dr-blok">
    <div class="dr-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--yesil)">groups</span> Departman Personeli</div>
    @foreach ($personeller as $pp)
      <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)">
        <span class="material-icons-outlined" style="color:var(--sub);font-size:20px">person</span>
        <div style="flex:1"><div style="font-weight:600;font-size:13.5px">{{ $pp['ad'] }}</div><div class="rk-sub" style="font-size:11.5px">{{ $pp['unvan'] ?: $pp['rol'] }}</div></div>
        @if ($pp['adisyon'] > 0)<span class="rk-rozet" style="background:var(--card2);color:var(--sub2)">{{ $pp['adisyon'] }} adisyon</span>@endif
      </div>
    @endforeach
  </div>
  @else
  <div class="rk-sub" style="font-size:12px;padding:8px 2px">Bu departmana atanmış personel yok. <a href="/personel" style="color:var(--mavi)">Personel ekranından</a> departman ataması yapabilirsin.</div>
  @endif
</div>
</div>
@endsection

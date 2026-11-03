@extends('layout.app')
@section('rk_ikon', 'event_available')
@section('baslik', 'Vergi Takvimi')
@section('title', 'Vergi Takvimi')

@section('content')
<style>
  #vt{max-width:820px;margin:0 auto}
  #vt *{box-sizing:border-box}
  .vt-bas{font-size:13px;color:var(--sub);margin:0 2px 14px;line-height:1.5}
  .vt-list{display:flex;flex-direction:column;gap:10px}
  .vt-row{display:flex;align-items:center;gap:13px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--line);border-radius:14px;padding:13px 15px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .vt-ic{width:42px;height:42px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;color:#fff}
  .vt-ic .material-icons-outlined{font-size:22px}
  .vt-mid{flex:1;min-width:0}
  .vt-ad{font-size:14.5px;font-weight:800;color:var(--ink);line-height:1.2}
  .vt-ac{font-size:11.5px;color:var(--sub);margin-top:3px;line-height:1.4}
  .vt-sag{text-align:right;flex:none}
  .vt-tar{font-size:13px;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  .vt-kal{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;margin-top:5px;display:inline-block;letter-spacing:.2px}
  .vt-not{margin-top:16px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .vt-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="vt">
  <div class="vt-bas">Yaklaşan beyanname ve ödeme son tarihleri — en yakın üstte. Renk: <b style="color:#F43F5E">kırmızı</b> ≤3 gün, <b style="color:#F59E0B">turuncu</b> ≤7 gün.</div>
  <div class="vt-list">
    @foreach ($liste as $v)
      @php
        $kalan = $v['kalan'];
        $kc = $kalan < 0 ? '#94A3B8' : ($kalan <= 3 ? '#F43F5E' : ($kalan <= 7 ? '#F59E0B' : '#10B981'));
        $kt = $kalan < 0 ? (abs($kalan).' gün geçti') : ($kalan === 0 ? 'BUGÜN' : $kalan.' gün kaldı');
      @endphp
      <div class="vt-row" style="border-left-color:{{ $kc }}">
        <div class="vt-ic" style="background:{{ $v['renk'] }}"><span class="material-icons-outlined">{{ $v['ikon'] }}</span></div>
        <div class="vt-mid">
          <div class="vt-ad">{{ $v['ad'] }}</div>
          <div class="vt-ac">{{ $v['aciklama'] }}</div>
        </div>
        <div class="vt-sag">
          <div class="vt-tar">{{ $v['tarih']->translatedFormat('d M') }}</div>
          <span class="vt-kal" style="background:{{ $kc }}">{{ $kt }}</span>
        </div>
      </div>
    @endforeach
  </div>
  <div class="vt-not"><span class="material-icons-outlined">info</span><span>Tarihler standart takvime göre yaklaşıktır; resmî tatil/uzatma olabilir. Kesin tarih ve mükellefiyetini <b>mali müşavirinle</b> teyit et. KDV beyannamesini hazırlamak için <a href="/kdv-ozet" style="color:var(--mor1);font-weight:700;text-decoration:none">KDV Özeti →</a></span></div>
</div>
</div>
@endsection

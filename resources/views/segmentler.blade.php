@extends('layout.app')
@section('rk_ikon', 'groups')
@section('baslik', 'Müşteri Segmentleri')
@section('title', 'Segmentler')

@section('content')
<style>
  #sg{max-width:920px;margin:0 auto}
  #sg *{box-sizing:border-box}
  .sg-top{background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;border-radius:16px;padding:16px 20px;margin-bottom:14px;display:flex;align-items:center;gap:14px}
  .sg-top .material-icons-outlined{font-size:30px} .sg-top .l{font-size:12.5px;font-weight:700;opacity:.9} .sg-top .b{font-size:26px;font-weight:800;letter-spacing:-.6px}
  .sg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:13px}
  .sg-c{background:var(--card);border:1px solid var(--line);border-top:4px solid var(--line);border-radius:15px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sg-hd{display:flex;align-items:center;gap:11px;margin-bottom:9px}
  .sg-ic{width:40px;height:40px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;color:#fff}
  .sg-ad{font-weight:800;color:var(--ink);font-size:14.5px} .sg-say{font-size:26px;font-weight:800;color:var(--ink);letter-spacing:-.6px;font-variant-numeric:tabular-nums}
  .sg-aci{font-size:11.5px;color:var(--sub);margin-top:2px}
  .sg-btn{display:inline-flex;align-items:center;gap:6px;margin-top:11px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);border-radius:9px;padding:7px 13px;font-size:12px;font-weight:800;text-decoration:none}
  .sg-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .sg-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="sg">
  <div class="sg-top"><span class="material-icons-outlined">notifications_active</span><div><div class="l">Uygulama/push açık müşteri (kampanya hedefi)</div><div class="b">{{ $pushliMusteri }}</div></div></div>
  <div class="sg-grid">
    @foreach($segmentler as $s)
      <div class="sg-c" style="border-top-color:{{ $s['renk'] }}">
        <div class="sg-hd"><div class="sg-ic" style="background:{{ $s['renk'] }}"><span class="material-icons-outlined">{{ $s['ikon'] }}</span></div><div><div class="sg-ad">{{ $s['ad'] }}</div></div></div>
        <div class="sg-say">{{ $s['sayi'] }}</div>
        <div class="sg-aci">{{ $s['aciklama'] }}</div>
        <a href="/kampanya?segment={{ $s['key'] }}" class="sg-btn"><span class="material-icons-outlined" style="font-size:15px">campaign</span> Kampanya gönder</a>
      </div>
    @endforeach
  </div>
  <div class="sg-not"><span class="material-icons-outlined">info</span><span>Segmentler müşteri kayıt + sipariş verisinden otomatik hesaplanır. Kampanya <b>yalnızca uygulamayı indiren (push açık)</b> müşterilere gider — toplu WhatsApp yapılmaz (ban riski).</span></div>
</div>
</div>
@endsection

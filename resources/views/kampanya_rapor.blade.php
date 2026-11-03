@extends('layout.app')
@section('rk_ikon', 'leaderboard')
@section('baslik', 'Kampanya Raporu')
@section('title', 'Kampanya Raporu')

@section('content')
<style>
  #kr{max-width:900px;margin:0 auto}
  #kr *{box-sizing:border-box}
  .kr-ozet{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:560px){.kr-ozet{grid-template-columns:1fr}}
  .kr-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .kr-k .l{font-size:12px;color:var(--sub);font-weight:700} .kr-k .b{font-size:24px;font-weight:800;color:var(--ink);margin-top:4px;letter-spacing:-.5px}
  .kr-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .kr-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .kr-row:first-child{border-top:0}
  .kr-row .ic{width:34px;height:34px;border-radius:10px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .kr-row .a{flex:1;min-width:0} .kr-row .a .t{font-weight:700;color:var(--ink)} .kr-row .a .d{font-size:11px;color:var(--sub)}
  .kr-row .seg{font-size:10px;font-weight:800;text-transform:uppercase;background:var(--card2);color:var(--sub);padding:2px 8px;border-radius:20px}
  .kr-row .g{text-align:right} .kr-row .g .v{font-weight:800;color:#2FBE94;font-variant-numeric:tabular-nums} .kr-row .g .t{font-size:10px;color:var(--sub)}
  .kr-bos{text-align:center;padding:28px;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="kr">
  <div class="kr-ozet">
    <div class="kr-k"><div class="l">Toplam kampanya</div><div class="b">{{ $topGonderim }}</div></div>
    <div class="kr-k"><div class="l">Ulaşılan müşteri (toplam)</div><div class="b" style="color:#2FBE94">{{ number_format($topKisi,0,',','.') }}</div></div>
  </div>
  <div class="kr-tab">
    @forelse($liste as $k)
      <div class="kr-row">
        <div class="ic"><span class="material-icons-outlined">campaign</span></div>
        <div class="a"><div class="t">{{ $k->baslik }}</div><div class="d">{{ \Carbon\Carbon::parse($k->created_at)->format('d.m.Y H:i') }} · {{ $k->kanal }}</div></div>
        <span class="seg">{{ $k->segment }}</span>
        <div class="g"><div class="v">{{ $k->gonderilen }}/{{ $k->hedef }}</div><div class="t">ulaşan/hedef</div></div>
      </div>
    @empty
      <div class="kr-bos">Henüz kampanya gönderilmedi. <a href="/kampanya" style="color:var(--mor1);font-weight:700;text-decoration:none">Kampanya Stüdyosu →</a></div>
    @endforelse
  </div>
</div>
</div>
@endsection

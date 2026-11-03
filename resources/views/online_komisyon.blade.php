@extends('layout.app')
@section('rk_ikon', 'percent')
@section('baslik', 'Online Kanal Komisyon')
@section('title', 'Online Komisyon')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺'; @endphp
<style>
  #ok{max-width:900px;margin:0 auto}
  #ok *{box-sizing:border-box}
  .ok-ay{font-size:15px;font-weight:800;color:var(--ink);margin:0 2px 12px}
  .ok-ozet{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:14px}
  @media(max-width:680px){.ok-ozet{grid-template-columns:1fr}}
  .ok-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .ok-k .l{font-size:12px;color:var(--sub);font-weight:700} .ok-k .b{font-size:22px;font-weight:800;color:var(--ink);margin-top:4px;letter-spacing:-.5px}
  .ok-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .ok-row{display:flex;align-items:center;gap:12px;padding:13px 16px;border-top:1px solid var(--line);font-size:13px}
  .ok-row:first-child{border-top:0}
  .ok-row .pl{flex:1;min-width:0;font-weight:800;color:var(--ink);text-transform:capitalize} .ok-row .ad{font-size:11px;color:var(--sub);font-weight:500}
  .ok-row .cell{text-align:right;min-width:92px} .ok-row .cell .v{font-weight:800;font-variant-numeric:tabular-nums} .ok-row .cell .t{font-size:10px;color:var(--sub);font-weight:600;text-transform:uppercase}
  .ok-bos{text-align:center;padding:26px;color:var(--sub);font-size:13px}
  .ok-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .ok-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="ok">
  <div class="ok-ay">{{ \Carbon\Carbon::parse($ay.'-01')->translatedFormat('F Y') }} · Online kanal komisyonu</div>
  <div class="ok-ozet">
    <div class="ok-k"><div class="l">Online ciro (brüt)</div><div class="b">{{ $tl($topCiro) }}</div></div>
    <div class="ok-k"><div class="l">Tahmini komisyon</div><div class="b" style="color:#F43F5E">− {{ $tl($topKom) }}</div></div>
    <div class="ok-k"><div class="l">Net (komisyon sonrası)</div><div class="b" style="color:#2FBE94">{{ $tl($topNet) }}</div></div>
  </div>
  <div class="ok-tab">
    @forelse($liste as $r)
      <div class="ok-row">
        <div class="pl">{{ $r->platform }}<div class="ad">{{ $r->adet }} sipariş · %{{ $r->oran }} komisyon</div></div>
        <div class="cell"><div class="v">{{ $tl($r->ciro) }}</div><div class="t">ciro</div></div>
        <div class="cell"><div class="v" style="color:#F43F5E">− {{ $tl($r->komisyon) }}</div><div class="t">komisyon</div></div>
        <div class="cell"><div class="v" style="color:#2FBE94">{{ $tl($r->net) }}</div><div class="t">net</div></div>
      </div>
    @empty
      <div class="ok-bos">Bu ay online kanal (Yemeksepeti/Getir/Trendyol…) siparişi görünmüyor.</div>
    @endforelse
  </div>
  <div class="ok-not"><span class="material-icons-outlined">info</span><span>Komisyon oranları <b>varsayılandır</b> (Yemeksepeti/Getir/Trendyol %18, diğer %15). Gerçek sözleşme oranların farklıysa söyle, kanal bazlı oranı ayarlanır hale getiririm. Kanal, adisyonun <b>platform</b> alanından okunur.</span></div>
</div>
</div>
@endsection

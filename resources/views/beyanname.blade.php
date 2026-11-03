@extends('layout.app')
@section('rk_ikon', 'description')
@section('baslik', 'Beyanname Hazırlık')
@section('title', 'Beyanname Hazırlık')

@section('content')
@php
  $tl = fn($n) => number_format((float) round((float) ($n ?? 0), 2), 2, ',', '.') . '₺';
  $kdvDur = $d['odenecekKDV'] > 0 ? 'ödenecek' : 'devreden';
@endphp
<style>
  #by{max-width:860px;margin:0 auto}
  #by *{box-sizing:border-box}
  .by-ay{font-size:15px;font-weight:800;color:var(--ink);margin:0 2px 12px}
  .by-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-bottom:13px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .by-card h4{font-size:13px;font-weight:800;color:var(--ink);margin:0 0 12px;display:flex;align-items:center;gap:9px}
  .by-card h4 .material-icons-outlined{font-size:19px;color:var(--mor1)}
  .by-card h4 .son{margin-left:auto;font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff}
  .by-tr{display:flex;justify-content:space-between;padding:9px 0;border-top:1px solid var(--line);font-size:13.5px}
  .by-tr:first-of-type{border-top:0} .by-tr .l{color:var(--sub);font-weight:600} .by-tr .r{color:var(--ink);font-weight:800;font-variant-numeric:tabular-nums}
  .by-tr.vurgu .l,.by-tr.vurgu .r{font-size:15px;color:var(--ink)}
  .by-acts{display:flex;gap:9px;margin-top:12px;flex-wrap:wrap}
  .by-acts a{font-size:12px;font-weight:700;text-decoration:none;border:1px solid var(--line);background:var(--card2);color:var(--mor1);border-radius:9px;padding:8px 14px}
  .by-not{font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .by-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="by">
  <div class="by-ay">{{ \Carbon\Carbon::parse($d['ay'].'-01')->translatedFormat('F Y') }} dönemi beyanname hazırlık özeti</div>

  <div class="by-card">
    <h4><span class="material-icons-outlined">receipt_long</span> KDV Beyannamesi (KDV1)<span class="son" style="background:{{ $d['odenecekKDV']>0?'#F43F5E':'#10B981' }}">{{ ucfirst($kdvDur) }} {{ $tl(abs($d['odenecekKDV'])) }}</span></h4>
    <div class="by-tr"><span class="l">Teslim matrahı (KDV hariç satış)</span><span class="r">{{ $tl($d['ciroNet']) }}</span></div>
    <div class="by-tr"><span class="l">Hesaplanan KDV</span><span class="r">{{ $tl($d['hesKDV']) }}</span></div>
    <div class="by-tr"><span class="l">İndirilecek KDV (alış)</span><span class="r">− {{ $tl($d['indKDV']) }}</span></div>
    <div class="by-tr vurgu"><span class="l">{{ $d['odenecekKDV']>=0?'Ödenecek KDV':'Devreden KDV' }}</span><span class="r" style="color:{{ $d['odenecekKDV']>0?'#F43F5E':'#10B981' }}">{{ $tl(abs($d['odenecekKDV'])) }}</span></div>
    <div class="by-acts"><a href="/kdv-ozet">KDV Özeti →</a><a href="/muhasebe-entegrasyon">Mahsup fişini aktar →</a></div>
  </div>

  <div class="by-card">
    <h4><span class="material-icons-outlined">groups</span> Muhtasar ve Prim Hizmet (hazırlık)</h4>
    <div class="by-tr"><span class="l">Dönem bordro hakedişi (brüt)</span><span class="r">{{ $tl($d['bordro']) }}</span></div>
    <div class="by-tr"><span class="l">Gelir vergisi stopajı + SGK</span><span class="r" style="color:var(--sub2)">bordrodan hesaplanır</span></div>
    <div class="by-acts"><a href="/ik-ozet">İK / Bordro →</a></div>
  </div>

  <div class="by-card">
    <h4><span class="material-icons-outlined">event_available</span> Yaklaşan son tarihler</h4>
    @foreach(array_slice($vt, 0, 4) as $v)
      <div class="by-tr"><span class="l">{{ $v['ad'] }}</span><span class="r" style="color:{{ $v['kalan']<=3?'#F43F5E':($v['kalan']<=7?'#F59E0B':'var(--ink)') }}">{{ $v['tarih']->translatedFormat('d M') }} · {{ max(0,$v['kalan']) }} gün</span></div>
    @endforeach
    <div class="by-acts"><a href="/vergi-takvimi">Tüm vergi takvimi →</a></div>
  </div>

  <div class="by-not"><span class="material-icons-outlined">info</span><span>Bu ekran <b>hazırlık özetidir</b>; resmî beyanname değildir. Rakamlar tahminidir (tek KDV oranı, bordrodan türetilen stopaj). Kesin beyan ve gönderim <b>mali müşavirin</b> veya Defter-Beyan/entegratör üzerinden yapılır. Mahsup fişini Logo/Netsis'e <a href="/muhasebe-entegrasyon" style="color:var(--mor1);font-weight:700;text-decoration:none">Muhasebe Entegrasyonu</a>'ndan aktarabilirsin.</span></div>
</div>
</div>
@endsection

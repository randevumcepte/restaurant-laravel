@extends('layout.app')
@section('rk_ikon', 'receipt_long')
@section('baslik', 'KDV Özeti')
@section('title', 'KDV Özeti')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0), 2), 2, ',', '.') . '₺'; @endphp
<style>
  #kv{max-width:860px;margin:0 auto}
  #kv *{box-sizing:border-box}
  .kv-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .kv-ay{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:8px 13px;font-size:13px;font-weight:700;color:var(--ink);text-decoration:none}
  .kv-sage{margin-left:auto;font-size:12px;color:var(--sub);font-weight:600}
  .kv-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:14px}
  @media(max-width:720px){.kv-grid{grid-template-columns:1fr}}
  .kv-k{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .kv-k .ic{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:11px}
  .kv-k .ic .material-icons-outlined{font-size:21px}
  .kv-k .lbl{font-size:12px;color:var(--sub);font-weight:700}
  .kv-k .big{font-size:24px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1.1;margin-top:4px;font-variant-numeric:tabular-nums}
  .kv-k .sub{font-size:11px;color:var(--sub2);margin-top:4px}
  .kv-k.sat .ic{background:rgba(47,190,148,.14);color:#2FBE94} .kv-k.al .ic{background:rgba(91,141,239,.14);color:#5B8DEF}
  .kv-k.od{border-color:color-mix(in srgb,var(--mor1) 30%,var(--line));background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 7%,var(--card)),var(--card) 60%)} .kv-k.od .ic{background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)}
  .kv-tab{background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden}
  .kv-tr{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--line);font-size:13.5px}
  .kv-tr:first-child{border-top:0} .kv-tr .l{color:var(--sub);font-weight:600} .kv-tr .r{color:var(--ink);font-weight:800;font-variant-numeric:tabular-nums}
  .kv-tr.vurgu{background:var(--card2)} .kv-tr.vurgu .l,.kv-tr.vurgu .r{font-size:15px;color:var(--ink)}
  .kv-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .kv-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="kv">
  <div class="kv-top">
    <span style="font-size:15px;font-weight:800;color:var(--ink)">{{ \Carbon\Carbon::parse($d['ay'].'-01')->translatedFormat('F Y') }} KDV Özeti</span>
    <span class="kv-sage">KDV oranı %{{ $d['oran'] }}</span>
  </div>

  <div class="kv-grid">
    <div class="kv-k sat">
      <div class="ic"><span class="material-icons-outlined">trending_up</span></div>
      <div class="lbl">Hesaplanan KDV (satış)</div>
      <div class="big">{{ $tl($d['hesKDV']) }}</div>
      <div class="sub">matrah {{ $tl($d['ciroNet']) }}</div>
    </div>
    <div class="kv-k al">
      <div class="ic"><span class="material-icons-outlined">trending_down</span></div>
      <div class="lbl">İndirilecek KDV (alış)</div>
      <div class="big">{{ $tl($d['indKDV']) }}</div>
      <div class="sub">alış {{ $tl($d['alisBrut']) }}</div>
    </div>
    <div class="kv-k od">
      <div class="ic"><span class="material-icons-outlined">account_balance</span></div>
      <div class="lbl">{{ $d['odenecekKDV'] >= 0 ? 'Ödenecek KDV' : 'Devreden KDV' }}</div>
      <div class="big" style="color:{{ $d['odenecekKDV'] > 0 ? '#F43F5E' : '#10B981' }}">{{ $tl(abs($d['odenecekKDV'])) }}</div>
      <div class="sub">hesaplanan − indirilecek</div>
    </div>
  </div>

  <div class="kv-tab">
    <div class="kv-tr"><span class="l">Brüt ciro (KDV dahil)</span><span class="r">{{ $tl($d['ciroBrut']) }}</span></div>
    <div class="kv-tr"><span class="l">Matrah (KDV hariç)</span><span class="r">{{ $tl($d['ciroNet']) }}</span></div>
    <div class="kv-tr"><span class="l">Hesaplanan KDV</span><span class="r">{{ $tl($d['hesKDV']) }}</span></div>
    <div class="kv-tr"><span class="l">İndirilecek KDV</span><span class="r">− {{ $tl($d['indKDV']) }}</span></div>
    <div class="kv-tr vurgu"><span class="l">{{ $d['odenecekKDV'] >= 0 ? 'Ödenecek KDV' : 'Sonraki döneme devreden' }}</span><span class="r" style="color:{{ $d['odenecekKDV'] > 0 ? '#F43F5E' : '#10B981' }}">{{ $tl(abs($d['odenecekKDV'])) }}</span></div>
  </div>

  <div class="kv-not"><span class="material-icons-outlined">info</span><span>Bu özet <b>tahminidir</b> (tek oran %{{ $d['oran'] }} üzerinden). Gerçek beyanname oran kırılımı (%1/%10/%20), tevkifat (KDV2) ve istisnalarla değişir — <b>mali müşavirinle</b> kesinleştir. Oranı <a href="/muhasebe-entegrasyon" style="color:var(--mor1);font-weight:700;text-decoration:none">Muhasebe Entegrasyonu</a>'ndan ayarlayabilirsin.</span></div>
</div>
</div>
@endsection

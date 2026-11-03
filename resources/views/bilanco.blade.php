@extends('layout.app')
@section('rk_ikon', 'balance')
@section('baslik', 'Bilanço (özet)')
@section('title', 'Bilanço')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺'; @endphp
<style>
  #bl{max-width:880px;margin:0 auto}
  #bl *{box-sizing:border-box}
  .bl-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:680px){.bl-grid{grid-template-columns:1fr}}
  .bl-col{background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .bl-h{padding:13px 17px;font-size:13px;font-weight:800;color:#fff;display:flex;align-items:center;gap:9px}
  .bl-h.v{background:linear-gradient(135deg,#2FBE94,#059669)} .bl-h.b{background:linear-gradient(135deg,#F5576C,#DC2626)}
  .bl-tr{display:flex;justify-content:space-between;padding:11px 17px;border-top:1px solid var(--line);font-size:13.5px}
  .bl-tr .l{color:var(--sub);font-weight:600} .bl-tr .r{color:var(--ink);font-weight:700;font-variant-numeric:tabular-nums}
  .bl-tr.top{background:var(--card2)} .bl-tr.top .l,.bl-tr.top .r{font-weight:800;font-size:14.5px;color:var(--ink)}
  .bl-oz{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border-radius:16px;padding:16px 20px;display:flex;align-items:center;gap:14px}
  .bl-oz .l{font-size:12.5px;font-weight:700;opacity:.9} .bl-oz .b{font-size:28px;font-weight:800;letter-spacing:-.6px}
  .bl-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .bl-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="bl">
  <div class="bl-grid">
    <div class="bl-col">
      <div class="bl-h v"><span class="material-icons-outlined">trending_up</span> Varlıklar (aktif)</div>
      @foreach($varliklar as $ad=>$tutar)<div class="bl-tr"><span class="l">{{ $ad }}</span><span class="r">{{ $tl($tutar) }}</span></div>@endforeach
      <div class="bl-tr top"><span class="l">Toplam varlık</span><span class="r">{{ $tl($topVar) }}</span></div>
    </div>
    <div class="bl-col">
      <div class="bl-h b"><span class="material-icons-outlined">trending_down</span> Borçlar (pasif)</div>
      @foreach($borclar as $ad=>$tutar)<div class="bl-tr"><span class="l">{{ $ad }}</span><span class="r">{{ $tl($tutar) }}</span></div>@endforeach
      <div class="bl-tr top"><span class="l">Toplam borç</span><span class="r">{{ $tl($topBorc) }}</span></div>
    </div>
  </div>
  <div class="bl-oz"><span class="material-icons-outlined" style="font-size:30px">savings</span><div><div class="l">Net özkaynak (varlık − borç)</div><div class="b">{{ $tl($ozkaynak) }}</div></div></div>
  <div class="bl-not"><span class="material-icons-outlined">info</span><span>Bu <b>basitleştirilmiş yönetim bilançosudur</b>, resmî bilanço değildir. Kasa nakdi, peşin giderler, amortisman birikmişi, SGK/vergi tahakkukları ve sermaye kalemleri tam yansımaz. Resmî bilanço için <b>mali müşavirinle</b> çalış. Banka <a href="/banka" style="color:var(--mor1);font-weight:700;text-decoration:none">Banka</a>, çek/senet <a href="/cek-senet" style="color:var(--mor1);font-weight:700;text-decoration:none">Çek-Senet</a> ekranlarından beslenir.</span></div>
</div>
</div>
@endsection

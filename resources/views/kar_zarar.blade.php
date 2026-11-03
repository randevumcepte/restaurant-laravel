@extends('layout.app')
@section('rk_ikon', 'account_balance')
@section('baslik', 'Kâr-Zarar (P&L)')
@section('title', 'Kâr-Zarar')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺'; @endphp
<style>
  #kz{max-width:720px;margin:0 auto}
  #kz *{box-sizing:border-box}
  .kz-top{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .kz-ozet{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:560px){.kz-ozet{grid-template-columns:1fr}}
  .kz-net{border-radius:18px;padding:18px 20px;color:#fff;box-shadow:0 12px 30px rgba(79,70,229,.28)}
  .kz-net.art{background:linear-gradient(135deg,#2FBE94,#059669);box-shadow:0 12px 30px rgba(16,185,129,.28)}
  .kz-net.zar{background:linear-gradient(135deg,#F5576C,#DC2626);box-shadow:0 12px 30px rgba(244,63,94,.28)}
  .kz-net .lbl{font-size:12.5px;font-weight:700;opacity:.9}
  .kz-net .big{font-size:30px;font-weight:800;letter-spacing:-.8px;line-height:1.05;margin-top:5px}
  .kz-net .sub{font-size:12px;opacity:.9;margin-top:5px}
  .kz-marj{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px 20px;display:flex;flex-direction:column;justify-content:center;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .kz-marj .lbl{font-size:12.5px;color:var(--sub);font-weight:700}
  .kz-marj .big{font-size:30px;font-weight:800;color:var(--ink);letter-spacing:-.8px;margin-top:5px}
  .kz-marj .sub{font-size:12px;color:var(--sub2);margin-top:5px}
  .kz-tab{background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden}
  .kz-tr{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--line);font-size:13.5px}
  .kz-tr:first-child{border-top:0} .kz-tr .l{color:var(--ink);font-weight:600} .kz-tr .l .material-icons-outlined{font-size:15px;vertical-align:-3px;margin-right:6px;color:var(--sub2)}
  .kz-tr .r{font-weight:800;font-variant-numeric:tabular-nums;color:var(--ink)}
  .kz-tr.alt{padding-left:34px} .kz-tr.alt .l{color:var(--sub);font-weight:500;font-size:12.5px}
  .kz-tr.ara{background:var(--card2)} .kz-tr.ara .l,.kz-tr.ara .r{font-size:14.5px;font-weight:800}
  .kz-eksi .r{color:#F43F5E}
  .kz-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .kz-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="kz">
  @php $art = $d['netKar'] >= 0; @endphp
  <div class="kz-top">
    <span style="font-size:15px;font-weight:800;color:var(--ink)">{{ \Carbon\Carbon::parse($d['ay'].'-01')->translatedFormat('F Y') }} Kâr-Zarar</span>
  </div>

  <div class="kz-ozet">
    <div class="kz-net {{ $art ? 'art' : 'zar' }}">
      <div class="lbl">{{ $art ? 'NET KÂR' : 'NET ZARAR' }}</div>
      <div class="big">{{ $tl(abs($d['netKar'])) }}</div>
      <div class="sub">net satış {{ $tl($d['ciroNet']) }} üzerinden</div>
    </div>
    <div class="kz-marj">
      <div class="lbl">Net kâr marjı</div>
      <div class="big" style="color:{{ $art ? '#2FBE94' : '#F43F5E' }}">{{ $d['marj'] === null ? '—' : '%'.$d['marj'] }}</div>
      <div class="sub">brüt kâr {{ $tl($d['brutKar']) }}</div>
    </div>
  </div>

  <div class="kz-tab">
    <div class="kz-tr"><span class="l"><span class="material-icons-outlined">payments</span>Net satış (KDV hariç)</span><span class="r">{{ $tl($d['ciroNet']) }}</span></div>
    <div class="kz-tr kz-eksi"><span class="l"><span class="material-icons-outlined">inventory_2</span>Satılan malın maliyeti (SMM)</span><span class="r">− {{ $tl($d['smm']) }}</span></div>
    <div class="kz-tr ara"><span class="l">= Brüt kâr</span><span class="r">{{ $tl($d['brutKar']) }}</span></div>
    <div class="kz-tr kz-eksi alt"><span class="l">İşletme giderleri</span><span class="r">− {{ $tl($d['gider']) }}</span></div>
    <div class="kz-tr kz-eksi alt"><span class="l">Personel (bordro hakediş)</span><span class="r">− {{ $tl($d['bordro']) }}</span></div>
    <div class="kz-tr kz-eksi"><span class="l"><span class="material-icons-outlined">request_quote</span>Toplam gider</span><span class="r">− {{ $tl($d['toplamGider']) }}</span></div>
    <div class="kz-tr ara"><span class="l">= {{ $art ? 'Net kâr' : 'Net zarar' }}</span><span class="r" style="color:{{ $art ? '#2FBE94' : '#F43F5E' }}">{{ $tl($d['netKar']) }}</span></div>
  </div>

  <div class="kz-not"><span class="material-icons-outlined">info</span><span>SMM reçete maliyetinden (maliyet girilmemiş üründe satışın %30'u varsayılır), bordro İK hakedişinden gelir. <b>Maaşları ayrıca "Giderler"e de girdiysen çift sayım olabilir</b> — kontrol et. KDV ve vergi hariç yönetim raporudur, resmi gelir tablosu değildir.</span></div>
</div>
</div>
@endsection

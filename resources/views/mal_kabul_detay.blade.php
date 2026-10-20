@extends('layout.app')
@section('title', 'Mal Kabul Detay')
@section('rk_ikon', 'inventory')
@section('baslik', 'Mal Kabul Detayı')
@section('rk_alt', ($m->irsaliye_no ?? '') ? ('İrsaliye ' . $m->irsaliye_no) : 'İrsaliye / GRN')

@section('content')
@php
  $m = $m ?? null;
  $kalemler = $kalemler ?? collect();
  $tedarikci = $tedarikci ?? null;
  $siparis = $siparis ?? null;
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $redVar = $kalemler->sum(fn($k) => (float) $k->red_miktar) > 0;
@endphp

@if (session('mk_ok'))<div class="rk-flash ok">{{ session('mk_ok') }}</div>@endif
@if (session('mk_hata'))<div class="rk-flash err">{{ session('mk_hata') }}</div>@endif

<div style="display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap">
  <a href="/mal-kabul" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Mal Kabuller</a>
  <span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">✓ Stok işlendi</span>
  @if ($siparis)<a href="/satinalma-siparis-detay?id={{ $siparis->id }}" class="rk-btn bos" style="padding:8px 13px">Sipariş: {{ $siparis->siparis_no }}</a>@endif
</div>

<div class="rk-grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">Tedarikçi</div><div class="rk-ink" style="font-weight:700;font-size:15px">{{ $tedarikci->ad ?? '—' }}</div></div>
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">İrsaliye No / Tarih</div><div class="rk-ink" style="font-weight:600">{{ $m->irsaliye_no ?: '—' }}</div><div class="rk-sub2" style="font-size:12px">{{ $m->tarih ? \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') : '—' }}</div></div>
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">Kalem</div><div class="rk-ink" style="font-weight:700;font-size:15px">{{ $kalemler->count() }}</div>@if($redVar)<div class="rk-sub2" style="font-size:12px;color:var(--kirmizi)">⚠ Red/eksik var</div>@endif</div>
</div>

<div class="rk-bolum"><span class="cubuk" style="background:var(--yesil)"></span><span class="b">Gelen Kalemler</span></div>
<div class="rk-tablo">
  <table>
    <thead><tr><th style="width:26%">Malzeme</th><th class="sag">Sipariş</th><th class="sag">Gelen</th><th class="sag">Kabul</th><th class="sag">Red</th><th class="sag">Birim Fiyat</th><th>Lot</th><th class="sag">SKT</th></tr></thead>
    <tbody>
      @foreach ($kalemler as $k)
        @php $red = (float) $k->red_miktar; @endphp
        <tr>
          <td style="font-weight:600">{{ $k->malzeme }} <span class="rk-sub2" style="font-size:11px">/ {{ $k->birim }}</span></td>
          <td class="sag rk-sub2">{{ $k->siparis_miktar !== null ? $num($k->siparis_miktar) : '—' }}</td>
          <td class="sag">{{ $num($k->gelen_miktar) }}</td>
          <td class="sag" style="font-weight:700;color:var(--yesil)">{{ $num($k->kabul_miktar) }}</td>
          <td class="sag" style="{{ $red > 0 ? 'color:var(--kirmizi);font-weight:700' : 'color:var(--sub2)' }}">{{ $red > 0 ? $num($red) : '—' }}</td>
          <td class="sag">{{ $tl($k->birim_fiyat) }}</td>
          <td class="rk-sub2" style="font-size:12px">{{ $k->lot_no ?: '—' }}</td>
          <td class="sag rk-sub2" style="font-size:12px">{{ $k->skt ? \Carbon\Carbon::parse($k->skt)->format('d.m.Y') : '—' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if (!empty($m->notlar))<div class="rk-card" style="margin-top:12px"><div class="rk-sub2" style="font-size:11px">Not</div><div class="rk-ink">{{ $m->notlar }}</div></div>@endif

<div class="rk-card" style="margin-top:16px;background:color-mix(in srgb,var(--amber) 7%,transparent);border-color:color-mix(in srgb,var(--amber) 28%,transparent)">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span class="material-icons-outlined" style="color:var(--amber);font-size:22px">receipt</span>
    <div style="flex:1;min-width:200px"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Fatura geldiğinde</div><div class="rk-sub2" style="font-size:12px"><b>3'lü eşleştir</b>: sipariş ↔ irsaliye ↔ fatura; miktar+fiyat tutuyor mu, fazla ödeme var mı.</div></div>
    <a href="/fatura-eslestir?mal_kabul_id={{ $m->id }}" class="rk-btn" style="background:var(--amber);padding:11px 16px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">fact_check</span> Fatura Eşleştir</a>
  </div>
</div>
@endsection

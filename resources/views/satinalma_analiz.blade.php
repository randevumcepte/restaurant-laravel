@extends('layout.app')
@section('title', 'Satınalma Analizi')
@section('rk_ikon', 'leaderboard')
@section('baslik', 'Satınalma Analizi')
@section('rk_alt', 'Tedarikçi karnesi + harcama trendi')

@section('content')
@php
  $karne = $karne ?? collect();
  $aylik = $aylik ?? collect();
  $toplamAlis = (float) ($toplamAlis ?? 0);
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $tl0 = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $enYuksekAy = $aylik->max('t') ?: 1;
  $yildiz = function($p){ $tam = floor($p); $yarim = ($p - $tam) >= 0.5; return str_repeat('★', (int)$tam) . ($yarim ? '½' : ''); };
@endphp

{{-- Harcama trendi --}}
<div class="rk-card" style="margin-bottom:16px">
  <div class="rk-bolum" style="margin-bottom:12px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">Harcama Trendi (son 6 ay)</span>
    <span class="rk-rozet" style="margin-left:auto;color:var(--mavi);background:color-mix(in srgb,var(--mavi) 13%,transparent);border-color:color-mix(in srgb,var(--mavi) 40%,transparent)">Toplam alış: {{ $tl0($toplamAlis) }}</span></div>
  @if ($aylik->isEmpty())
    <div class="rk-sub" style="text-align:center;padding:16px">Henüz alış faturası yok.</div>
  @else
    <div style="display:flex;align-items:flex-end;gap:12px;height:140px;padding:0 4px">
      @foreach ($aylik as $a)
        @php $h = max(6, round(($a->t / $enYuksekAy) * 120)); @endphp
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:5px">
          <div class="rk-sub2" style="font-size:10.5px;font-weight:700">{{ $tl0($a->t) }}</div>
          <div style="width:100%;max-width:54px;height:{{ $h }}px;background:linear-gradient(180deg,var(--mavi),var(--mor1));border-radius:7px 7px 0 0"></div>
          <div class="rk-sub2" style="font-size:11px">{{ \Carbon\Carbon::parse($a->ay.'-01')->translatedFormat('M') }}</div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- Tedarikçi karnesi --}}
<div class="rk-bolum"><span class="cubuk" style="background:var(--gold)"></span><span class="b">🏅 Tedarikçi Karnesi</span>
  <span class="rk-rozet" style="color:var(--goldd);background:color-mix(in srgb,var(--gold) 22%,transparent);border-color:color-mix(in srgb,var(--gold) 55%,transparent)">{{ $karne->count() }}</span></div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Zamanında teslim (PO beklenen vs mal kabul tarihi) + red/eksik oranı → yıldız puanı.</div>
@if ($karne->isEmpty())
  <div class="rk-card" style="text-align:center;padding:30px"><div class="rk-sub">Karne için alış faturası + mal kabul verisi gerekir. Satınalma yaptıkça dolar.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:26%">Tedarikçi</th><th class="sag">Toplam Alış</th><th class="sag">Fatura</th><th class="orta">Zamanında</th><th class="orta">Red/Eksik</th><th class="orta">Puan</th></tr></thead>
      <tbody>
        @foreach ($karne as $k)
          @php
            $zRenk = $k->zamaninda === null ? 'var(--sub2)' : ($k->zamaninda >= 90 ? 'var(--yesil)' : ($k->zamaninda >= 70 ? 'var(--amber)' : 'var(--kirmizi)'));
            $pRenk = $k->puan >= 4 ? 'var(--yesil)' : ($k->puan >= 3 ? 'var(--amber)' : 'var(--kirmizi)');
          @endphp
          <tr>
            <td style="font-weight:600">{{ $k->ad }}</td>
            <td class="sag" style="font-weight:700">{{ $tl0($k->toplam) }}</td>
            <td class="sag rk-sub2">{{ $k->fatura }}</td>
            <td class="orta"><span style="color:{{ $zRenk }};font-weight:700">{{ $k->zamaninda === null ? '—' : '%'.$k->zamaninda }}</span></td>
            <td class="orta"><span style="color:{{ $k->red_oran > 0 ? 'var(--kirmizi)' : 'var(--sub2)' }};font-weight:700">{{ $k->red_oran > 0 ? '%'.number_format($k->red_oran,1) : '—' }}</span></td>
            <td class="orta"><span class="rk-rozet" style="color:{{ $pRenk }};background:color-mix(in srgb,{{ $pRenk }} 13%,transparent);border-color:color-mix(in srgb,{{ $pRenk }} 40%,transparent)">{{ number_format($k->puan,1) }} {{ $yildiz($k->puan) }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Zamanında = sadece beklenen teslim tarihi girilmiş siparişlerden hesaplanır. Veri arttıkça puanlar netleşir.</div>
@endif
@endsection

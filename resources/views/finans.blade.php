@extends('layout.app')
@section('rk_ikon', 'analytics')
@section('baslik', 'Finans / Kâr-Zarar')
@section('rk_alt', $aralik_metin ?? '')

@section('content')
@php
  $periodlar = ['gunluk' => 'Bugün', 'haftalik' => 'Bu Hafta', 'aylik' => 'Bu Ay'];
  $period = $period ?? 'aylik';
  $gelir = (float) ($gelir ?? 0);
  $gider = (float) ($gider ?? 0);
  $bahsis = (float) ($bahsis ?? 0);
  $net = (float) ($net ?? ($gelir - $gider));
  $alis = (float) ($alis_toplam ?? 0);
  $gelirTip = $gelir_tip ?? [];
  $giderKat = $gider_kategori ?? [];
  $tipAd = ['nakit' => 'Nakit', 'kredi' => 'Kredi Kartı', 'yemek_karti' => 'Yemek Kartı', 'acik_hesap' => 'Açık Hesap'];
  $katAd = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme / Alış', 'maas' => 'Maaş / Personel', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $tl = fn ($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $netRenk = $net >= 0 ? 'var(--mor1)' : 'var(--kirmizi)';
@endphp

{{-- Dönem sekmeleri (Flutter: önceki/sonraki ay oku karşılığı — gün/hafta/ay) --}}
<div class="rk-bolum" style="justify-content:flex-end;margin-bottom:16px">
  <div style="display:inline-flex;gap:8px">
    @foreach ($periodlar as $k => $ad)
      <a href="/finans?period={{ $k }}"
         class="rk-btn {{ $period === $k ? '' : 'bos' }}"
         style="{{ $period === $k ? 'background:var(--mor1)' : '' }}">{{ $ad }}</a>
    @endforeach
  </div>
</div>

{{-- Üst satır: 3 büyük KPI kartı (Flutter MIstatKart birebir) --}}
<div class="rk-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px">
  {{-- Gelir --}}
  <div class="rk-stat">
    <div class="head" style="background:var(--yesil)">Gelir</div>
    <div class="body">
      <div class="buyuk">{{ $tl($gelir) }}</div>
      @if ($bahsis > 0)
        <div class="satir"><span class="e">Bahşiş</span><span class="d" style="color:var(--yesil)">{{ $tl($bahsis) }}</span></div>
      @endif
      <div class="satir"><span class="e">Ödeme tipi</span><span class="d">{{ count($gelirTip) }} kalem</span></div>
    </div>
  </div>

  {{-- Gider --}}
  <div class="rk-stat">
    <div class="head" style="background:var(--kirmizi)">Gider</div>
    <div class="body">
      <div class="buyuk">{{ $tl($gider) }}</div>
      @if ($alis > 0)
        <div class="satir"><span class="e">Tedarikçi alışı</span><span class="d" style="color:var(--kirmizi)">{{ $tl($alis) }}</span></div>
      @endif
      <div class="satir"><span class="e">Kategori</span><span class="d">{{ count($giderKat) }} kalem</span></div>
    </div>
  </div>

  {{-- Net Kâr / Zarar --}}
  <div class="rk-stat">
    <div class="head" style="background:{{ $netRenk }}">{{ $net >= 0 ? 'Net Kâr' : 'Net Zarar' }}</div>
    <div class="body">
      <div class="buyuk">{{ $tl($net) }}</div>
      <div class="satir"><span class="e">Gelir</span><span class="d" style="color:var(--yesil)">{{ $tl($gelir) }}</span></div>
      <div class="satir"><span class="e">Gider</span><span class="d" style="color:var(--kirmizi)">{{ $tl($gider) }}</span></div>
    </div>
  </div>
</div>

{{-- Alt satır: gelir/gider döküm tabloları yan yana (Flutter MBolumBaslik + MTablo) --}}
<div class="rk-grid" style="grid-template-columns:repeat(2,1fr);align-items:start">
  {{-- Gelir — Ödeme Tipi --}}
  <div>
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--yesil)"></span>
      <span class="b">Gelir — Ödeme Tipi</span>
      <span class="sayi" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent)">{{ count($gelirTip) }}</span>
    </div>
    @if (empty($gelirTip))
      <div class="rk-card"><span class="rk-sub">Bu ay tahsilat yok.</span></div>
    @else
      <div class="rk-tablo">
        <table>
          <thead><tr><th>Ödeme Tipi</th><th class="sag">Tutar</th></tr></thead>
          <tbody>
            @foreach ($gelirTip as $g)
              <tr>
                <td>{{ $tipAd[$g['tip']] ?? $g['tip'] }}</td>
                <td class="sag" style="color:var(--yesil);font-weight:700">{{ $tl($g['tutar']) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Gider — Kategori (+ Tedarikçi Alışı) --}}
  <div>
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--kirmizi)"></span>
      <span class="b">Gider — Kategori</span>
      <span class="sayi" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent)">{{ count($giderKat) }}</span>
    </div>
    @if (empty($giderKat))
      <div class="rk-card"><span class="rk-sub">Bu ay gider yok.</span></div>
    @else
      <div class="rk-tablo">
        <table>
          <thead><tr><th>Kategori</th><th class="sag">Tutar</th></tr></thead>
          <tbody>
            @foreach ($giderKat as $g)
              <tr>
                <td>{{ $katAd[$g['kategori']] ?? $g['kategori'] }}</td>
                <td class="sag" style="color:var(--kirmizi);font-weight:700">{{ $tl($g['tutar']) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    @if ($alis > 0)
      <div class="rk-bolum" style="margin-top:14px">
        <span class="cubuk" style="background:var(--amber)"></span>
        <span class="b">Tedarikçi Alışı</span>
      </div>
      <div class="rk-tablo">
        <table>
          <thead><tr><th>Kalem</th><th class="sag">Tutar</th></tr></thead>
          <tbody>
            <tr>
              <td>Toplam alış faturası</td>
              <td class="sag" style="color:var(--amber);font-weight:700">{{ $tl($alis) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection

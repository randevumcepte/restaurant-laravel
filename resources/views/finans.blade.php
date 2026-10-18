@extends('layout.app')
@section('title', 'Finans')
@section('baslik', '💵 Finans / Kâr-Zarar')

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
  $maxGelir = $gelirTip ? max(array_merge([1], array_map(fn ($g) => (float) $g['tutar'], $gelirTip))) : 1;
  $maxGider = $giderKat ? max(array_merge([1], array_map(fn ($g) => (float) $g['tutar'], $giderKat))) : 1;
@endphp

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="inline-flex bg-white rounded-xl p-1 shadow-sm">
    @foreach ($periodlar as $k => $ad)
      <a href="/finans?period={{ $k }}"
         class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $period === $k ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">{{ $ad }}</a>
    @endforeach
  </div>
  <div class="text-xs text-slate-400">{{ $aralik_metin ?? '' }}</div>
</div>

{{-- Özet kartları: Gelir / Gider / Net --}}
<div class="grid gap-4 md:grid-cols-3 mb-6">
  <div class="bg-white rounded-2xl p-5 shadow-sm border-t-4 border-emerald-400">
    <div class="flex items-center gap-2 text-emerald-500 text-sm font-semibold mb-1">
      <span class="text-lg">↓</span> Gelir
    </div>
    <div class="text-3xl font-extrabold text-slate-800">{{ $tl($gelir) }}</div>
    <div class="text-xs text-slate-400 mt-2">
      @if ($bahsis > 0) Bahşiş: <b class="text-emerald-600">{{ $tl($bahsis) }}</b> · @endif
      {{ count($gelirTip) }} ödeme tipi
    </div>
  </div>

  <div class="bg-white rounded-2xl p-5 shadow-sm border-t-4 border-rose-400">
    <div class="flex items-center gap-2 text-rose-500 text-sm font-semibold mb-1">
      <span class="text-lg">↑</span> Gider
    </div>
    <div class="text-3xl font-extrabold text-slate-800">{{ $tl($gider) }}</div>
    <div class="text-xs text-slate-400 mt-2">
      @if ($alis > 0) Tedarikçi alışı: <b class="text-rose-600">{{ $tl($alis) }}</b> · @endif
      {{ count($giderKat) }} kategori
    </div>
  </div>

  <div class="rounded-2xl p-5 shadow-sm text-white bg-gradient-to-br {{ $net >= 0 ? 'from-violet-600 to-indigo-600' : 'from-rose-700 to-rose-500' }}">
    <div class="text-sm font-semibold mb-1 opacity-90">{{ $net >= 0 ? 'Net Kâr' : 'Net Zarar' }}</div>
    <div class="text-3xl font-extrabold">{{ $tl($net) }}</div>
    <div class="flex gap-4 text-xs mt-3 opacity-90">
      <span>Gelir <b>{{ $tl($gelir) }}</b></span>
      <span>Gider <b>{{ $tl($gider) }}</b></span>
    </div>
  </div>
</div>

{{-- Dağılım tabloları: Gelir ödeme tipi + Gider kategori --}}
<div class="grid gap-4 lg:grid-cols-2">
  {{-- Gelir — ödeme tipi --}}
  <div class="bg-white rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800">💵 Gelir — Ödeme Tipi</h3>
      <span class="text-xs text-slate-400">{{ count($gelirTip) }} kalem</span>
    </div>
    @if (empty($gelirTip))
      <div class="text-center text-slate-400 py-8 text-sm">Bu dönemde tahsilat yok.</div>
    @else
      <div class="space-y-3">
        @foreach ($gelirTip as $g)
          @php $oran = round((float) $g['tutar'] / $maxGelir * 100); @endphp
          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-slate-600">{{ $tipAd[$g['tip']] ?? $g['tip'] }}</span>
              <b class="text-emerald-600">{{ $tl($g['tutar']) }}</b>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-emerald-400 rounded-full" style="width: {{ max(3, $oran) }}%"></div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Gider — kategori --}}
  <div class="bg-white rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800">🧾 Gider — Kategori</h3>
      <span class="text-xs text-slate-400">{{ count($giderKat) }} kalem</span>
    </div>
    @if (empty($giderKat))
      <div class="text-center text-slate-400 py-8 text-sm">Bu dönemde gider yok.</div>
    @else
      <div class="space-y-3">
        @foreach ($giderKat as $g)
          @php $oran = round((float) $g['tutar'] / $maxGider * 100); @endphp
          <div>
            <div class="flex items-center justify-between text-sm mb-1">
              <span class="text-slate-600">{{ $katAd[$g['kategori']] ?? $g['kategori'] }}</span>
              <b class="text-rose-600">{{ $tl($g['tutar']) }}</b>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-rose-400 rounded-full" style="width: {{ max(3, $oran) }}%"></div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
    @if ($alis > 0)
      <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-sm">
        <span class="text-slate-500">📦 Toplam alış faturası</span>
        <b class="text-amber-600">{{ $tl($alis) }}</b>
      </div>
    @endif
  </div>
</div>
@endsection

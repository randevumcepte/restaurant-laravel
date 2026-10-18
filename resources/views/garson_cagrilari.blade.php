@extends('layout.app')
@section('title', 'Garson Çağrıları')
@section('baslik', '🔔 Garson Çağrıları')

@section('content')
@php
  // Tip -> etiket + renk + ikon (Flutter garson_cagrilari_screen ile ayni semantik)
  $tipAdi = function ($t) {
    return $t === 'servise_hazir' ? 'Servise hazır (mutfak)'
      : ($t === 'tasima' ? 'Taşıma talebi'
      : ($t === 'odeme' ? 'Ödeme alınacak'
      : ($t === 'hesap' ? 'Hesap istiyor'
      : ($t === 'acil' ? 'Acil çağrı'
      : ($t === 'siparis' ? 'Sipariş verdi' : 'Garson çağırıyor')))));
  };
  $tipIkon = function ($t) {
    return $t === 'servise_hazir' ? '🍽️'
      : ($t === 'tasima' ? '↔️'
      : ($t === 'odeme' ? '💵'
      : ($t === 'hesap' ? '💳'
      : ($t === 'acil' ? '🔴' : '🙋'))));
  };
  $sureYazi = function ($sn) {
    $sn = $sn < 0 ? 0 : $sn;
    return $sn < 60 ? ($sn . ' sn') : (floor($sn / 60) . ' dk');
  };
@endphp

{{-- 10 sn'de bir sayfa kendini yeniler --}}
<meta http-equiv="refresh" content="10">

@if (session('ok'))
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-2.5 mb-4 text-sm font-semibold">{{ session('ok') }}</div>
@endif
@if (session('hata') || !empty($hata))
  <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-2.5 mb-4 text-sm font-semibold">{{ session('hata') ?: $hata }}</div>
@endif

<div class="flex items-center gap-3 mb-5 flex-wrap">
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm text-sm">
    <span class="text-slate-400">Bekleyen</span>
    <b class="ml-1 {{ count($bekleyen) ? 'text-rose-600' : 'text-slate-800' }}">{{ count($bekleyen) }}</b>
  </div>
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm text-sm">
    <span class="text-slate-400">Son karşılanan</span>
    <b class="text-slate-800 ml-1">{{ count($karsilanan) }}</b>
  </div>
  @if (count($bekleyen))
    <form method="POST" action="/garson-cagrilari/karsilandi" class="ml-auto">
      @csrf
      <input type="hidden" name="hepsi" value="1">
      <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-xl px-4 py-2 shadow-sm">✓ Tümünü karşıla</button>
    </form>
  @endif
  <span class="text-[11px] text-slate-400 {{ count($bekleyen) ? '' : 'ml-auto' }}">10 sn'de bir otomatik yenilenir</span>
</div>

{{-- BEKLEYEN ÇAĞRILAR --}}
@if (!count($bekleyen))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm mb-6">
    <div class="text-4xl mb-2">🔔</div>
    <div class="font-semibold text-slate-500">Bekleyen çağrı yok</div>
    <div class="text-xs mt-1">QR menüden gelen çağrılar buraya anında düşer.</div>
  </div>
@else
  <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 mb-6">
    @foreach ($bekleyen as $c)
      @php
        $geciken = $c['saniye'] >= 60;
        $hesapMi = $c['tip'] === 'hesap';
        $vurgu = $hesapMi ? 'amber' : ($c['tip'] === 'tasima' ? 'violet' : 'rose');
      @endphp
      <div class="bg-white rounded-2xl p-4 shadow-sm flex items-center gap-3 {{ $geciken ? 'ring-2 ring-rose-300' : '' }}">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-2xl bg-{{ $vurgu }}-50">{{ $tipIkon($c['tip']) }}</div>
        <div class="min-w-0 flex-1">
          <div class="font-extrabold text-slate-800 text-lg truncate">
            {{ $c['tip'] === 'tasima' && $c['hedef'] ? ($c['masa'] . ' → ' . $c['hedef']) : $c['masa'] }}
          </div>
          <div class="text-xs font-bold text-{{ $vurgu }}-600">{{ $tipAdi($c['tip']) }}</div>
        </div>
        <div class="text-right">
          <div class="font-extrabold {{ $geciken ? 'text-rose-600' : 'text-indigo-600' }}">{{ $sureYazi($c['saniye']) }}</div>
          <div class="text-[11px] text-slate-400">{{ $c['saat'] }}</div>
        </div>
        <form method="POST" action="/garson-cagrilari/karsilandi">
          @csrf
          <input type="hidden" name="id" value="{{ $c['id'] }}">
          <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl px-4 py-3 shadow-sm" title="Karşılandı">✓</button>
        </form>
      </div>
    @endforeach
  </div>
@endif

{{-- SON KARŞILANAN ÇAĞRILAR --}}
@if (count($karsilanan))
  <div class="text-sm font-bold text-slate-500 mb-2">Son karşılananlar</div>
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100">
          <th class="px-4 py-2.5 font-semibold">Masa</th>
          <th class="px-4 py-2.5 font-semibold">Tip</th>
          <th class="px-4 py-2.5 font-semibold">Saat</th>
          <th class="px-4 py-2.5 font-semibold">Durum</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($karsilanan as $c)
          <tr class="border-b border-slate-50 last:border-0">
            <td class="px-4 py-2.5 font-semibold text-slate-700">{{ $c['masa'] }}</td>
            <td class="px-4 py-2.5 text-slate-500">{{ $tipIkon($c['tip']) }} {{ $tipAdi($c['tip']) }}</td>
            <td class="px-4 py-2.5 text-slate-400">{{ $c['saat'] }}</td>
            <td class="px-4 py-2.5"><span class="inline-block bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-full px-2.5 py-0.5">✓ Karşılandı</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

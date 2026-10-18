@extends('layout.app')
@section('title', 'Üretim Riski')
@section('baslik', '⚠️ Üretim Riski')

@section('content')
@php
  // Flutter uretim_riski_screen karsiligi (salt-okunur).
  // kritik: ['id','ad','mevcut','kritik','birim']  |  riskli: ['urun_id','urun','yapilabilir','darbogaz']
  $kritik = $kritik ?? [];
  $riskli = $riskli ?? [];
  $esik   = $esik ?? 15;
  $huzur  = count($kritik) === 0 && count($riskli) === 0;
  $mik = function ($v) {
    $v = (float) $v;
    return $v == floor($v) ? number_format($v, 0, ',', '.') : number_format($v, 2, ',', '.');
  };
@endphp

{{-- Özet bant --}}
<div class="rounded-2xl p-5 mb-6 flex items-center gap-4 text-white shadow-sm
            {{ $huzur ? 'bg-gradient-to-r from-emerald-500 to-sky-500' : 'bg-gradient-to-r from-rose-500 to-amber-500' }}">
  <div class="text-3xl">{{ $huzur ? '✅' : '⚠️' }}</div>
  <div class="font-semibold leading-snug">
    @if ($huzur)
      Her şey yolunda — kritik malzeme yok, tüm yemekler rahat çıkıyor.
    @else
      {{ count($kritik) }} kritik malzeme · {{ count($riskli) }} yemek riskte. Hemen stok girin.
    @endif
  </div>
  <div class="ml-auto hidden sm:block text-right text-xs opacity-90">
    <div>Eşik</div>
    <div class="text-lg font-extrabold">≤ {{ $esik }} porsiyon</div>
  </div>
</div>

@if ($huzur)
  <div class="bg-white rounded-2xl p-12 text-center text-slate-400 shadow-sm">Risk yok 🎉</div>
@else

  {{-- Kritik malzemeler --}}
  @if (count($kritik))
    <div class="flex items-center gap-2 mb-3">
      <h2 class="text-base font-bold text-slate-800">📦 Kritik Malzemeler</h2>
      <span class="bg-slate-200 text-slate-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($kritik) }}</span>
    </div>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 mb-8">
      @foreach ($kritik as $k)
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-rose-200 flex items-center gap-3">
          <div class="text-rose-500 text-xl">📥</div>
          <div class="font-semibold text-slate-800 truncate">{{ $k['ad'] }}</div>
          <div class="ml-auto text-right">
            <div class="text-rose-600 font-bold">{{ $mik($k['mevcut']) }} {{ $k['birim'] }}</div>
            <div class="text-[11px] text-slate-400">kritik: {{ $mik($k['kritik']) }}</div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- Riskli yemekler --}}
  @if (count($riskli))
    <div class="flex items-center gap-2 mb-1">
      <h2 class="text-base font-bold text-slate-800">🍽️ Riskli Yemekler</h2>
      <span class="bg-slate-200 text-slate-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ count($riskli) }}</span>
    </div>
    <p class="text-xs text-slate-400 mb-3">Kalan stokla en fazla kaç porsiyon çıkar (yarı mamül içindeki hammadde dahil).</p>
    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
      @foreach ($riskli as $u)
        @php
          $yap = (int) $u['yapilabilir'];
          $cikmaz = $yap <= 0;
          if ($cikmaz) { $renk = 'rose'; }
          elseif ($yap <= 5) { $renk = 'amber'; }
          else { $renk = 'emerald'; }
        @endphp
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 flex items-center gap-3">
          <div class="min-w-0">
            <div class="font-semibold text-slate-800 truncate">{{ $u['urun'] }}</div>
            <div class="text-xs text-slate-400 truncate">darboğaz: {{ $u['darbogaz'] }}</div>
          </div>
          <div class="ml-auto shrink-0 px-3 py-2 rounded-xl text-sm font-bold
                      bg-{{ $renk }}-50 text-{{ $renk }}-600">
            {{ $cikmaz ? 'ÇIKMIYOR' : $yap . ' porsiyon' }}
          </div>
        </div>
      @endforeach
    </div>
  @endif

@endif
@endsection

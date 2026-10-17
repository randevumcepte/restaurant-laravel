@extends('layout.app')
@section('title', 'Garson Performans')
@section('baslik', '🏅 Garson Performans')

@section('content')
@php
  $periodlar = ['gunluk' => 'Bugün', 'haftalik' => 'Hafta', 'aylik' => 'Ay', 'yillik' => 'Yıl'];
  $toplamCiro = array_sum(array_column($garsonlar, 'ciro'));
  $toplamAdisyon = array_sum(array_column($garsonlar, 'adisyon'));
  $maxCiro = $garsonlar ? max(array_column($garsonlar, 'ciro')) : 0;
@endphp

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="inline-flex bg-white rounded-xl p-1 shadow-sm">
    @foreach ($periodlar as $k => $ad)
      <a href="/garson-performans?period={{ $k }}"
         class="px-4 py-2 rounded-lg text-sm font-semibold {{ $period === $k ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">{{ $ad }}</a>
    @endforeach
  </div>
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Toplam ciro</span> <b class="text-slate-800 ml-1">{{ number_format($toplamCiro, 0, ',', '.') }}₺</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Adisyon</span> <b class="text-slate-800 ml-1">{{ $toplamAdisyon }}</b></div>
  </div>
</div>

@if (!$garsonlar)
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Bu dönemde garson hareketi yok.</div>
@else
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($garsonlar as $i => $g)
      @php $oran = $maxCiro > 0 ? round($g['ciro'] / $maxCiro * 100) : 0; @endphp
      <div class="bg-white rounded-2xl p-5 shadow-sm {{ $i === 0 && $g['ciro'] > 0 ? 'ring-2 ring-amber-300' : '' }}">
        <div class="flex items-center gap-3 mb-4">
          <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-white {{ $i === 0 ? 'bg-amber-400' : ($i === 1 ? 'bg-slate-400' : ($i === 2 ? 'bg-orange-400' : 'bg-indigo-400')) }}">
            {{ mb_substr($g['ad'], 0, 1) }}
          </div>
          <div class="min-w-0">
            <div class="font-bold text-slate-800 truncate">{{ $g['ad'] }}</div>
            <div class="text-xs text-slate-400">{{ $i === 0 && $g['ciro'] > 0 ? '🥇 En iyi' : '#' . ($i + 1) }}</div>
          </div>
          <div class="ml-auto text-right">
            <div class="text-xl font-extrabold text-indigo-700">{{ number_format($g['ciro'], 0, ',', '.') }}₺</div>
            <div class="text-[11px] text-slate-400">ciro</div>
          </div>
        </div>
        <div class="w-full h-1.5 bg-slate-100 rounded-full mb-4 overflow-hidden">
          <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full" style="width: {{ $oran }}%"></div>
        </div>
        <div class="grid grid-cols-4 gap-2 text-center">
          @php
            $mini = [
              ['Adisyon', $g['adisyon']],
              ['Kalem', $g['kalem']],
              ['Ort. servis', $g['ort_servis_dk'] . ' dk'],
              ['Adım', number_format($g['adim'], 0, ',', '.')],
            ];
          @endphp
          @foreach ($mini as [$ad, $v])
            <div class="bg-slate-50 rounded-lg py-2">
              <div class="text-sm font-bold text-slate-700">{{ $v }}</div>
              <div class="text-[10px] text-slate-400 mt-0.5">{{ $ad }}</div>
            </div>
          @endforeach
        </div>
        @if (!empty($g['saat_dagilim']))
          @php $maxSaat = max(array_map(fn($s) => $s['adisyon'], $g['saat_dagilim'])) ?: 1; @endphp
          <div class="mt-4">
            <div class="text-[11px] text-slate-400 mb-1.5">Saat dağılımı (adisyon)</div>
            <div class="flex items-end gap-0.5 h-10">
              @foreach ($g['saat_dagilim'] as $s)
                <div class="flex-1 bg-indigo-200 rounded-t hover:bg-indigo-400 transition" style="height: {{ max(4, round($s['adisyon'] / $maxSaat * 100)) }}%" title="{{ $s['saat'] }}:00 · {{ $s['adisyon'] }}"></div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    @endforeach
  </div>
@endif
@endsection

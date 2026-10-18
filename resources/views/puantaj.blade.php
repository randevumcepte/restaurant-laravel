@extends('layout.app')
@section('title', 'Puantaj')
@section('baslik', '⏱️ Puantaj (Mesai)')

@section('content')
@php
  $periodlar = ['gun' => 'Bugün', 'hafta' => 'Bu Hafta', 'ay' => 'Bu Ay'];
  $period = in_array($period ?? 'gun', ['gun', 'hafta', 'ay']) ? ($period ?? 'gun') : 'gun';

  $toplamDk = 0;
  foreach (($personeller ?? []) as $pp) { $toplamDk += (int) ($pp['toplam_dk'] ?? 0); }
  $maxDk = 0;
  foreach (($personeller ?? []) as $pp) { $maxDk = max($maxDk, (int) ($pp['toplam_dk'] ?? 0)); }

  $sureStr = function ($dk) {
    $dk = (int) $dk;
    $s = intdiv($dk, 60); $d = $dk % 60;
    return $s > 0 ? ($s . 's ' . $d . 'dk') : ($d . 'dk');
  };
@endphp

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="inline-flex bg-white rounded-xl p-1 shadow-sm">
    @foreach ($periodlar as $k => $ad)
      <a href="/puantaj?period={{ $k }}"
         class="px-4 py-2 rounded-lg text-sm font-semibold {{ $period === $k ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">{{ $ad }}</a>
    @endforeach
  </div>
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Personel</span> <b class="text-slate-800 ml-1">{{ count($personeller ?? []) }}</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Toplam süre</span> <b class="text-slate-800 ml-1">{{ $sureStr($toplamDk) }}</b></div>
  </div>
</div>

@if (empty($personeller))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Bu dönemde mesai kaydı yok.</div>
@else
  <div class="grid gap-4 lg:grid-cols-2">
    @foreach ($personeller as $i => $p)
      @php
        $oran = $maxDk > 0 ? round(($p['toplam_dk'] ?? 0) / $maxDk * 100) : 0;
        $kayitlar = $p['kayitlar'] ?? [];
      @endphp
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-white {{ $i === 0 ? 'bg-amber-400' : ($i === 1 ? 'bg-slate-400' : ($i === 2 ? 'bg-orange-400' : 'bg-indigo-400')) }}">
            {{ mb_substr($p['ad'] ?: '?', 0, 1) }}
          </div>
          <div class="min-w-0">
            <div class="font-bold text-slate-800 truncate">{{ $p['ad'] ?: '-' }}</div>
            <div class="text-xs text-slate-400">{{ count($kayitlar) }} kayıt @if (!empty($p['acik']))· <span class="text-emerald-600 font-semibold">mesaide</span>@endif</div>
          </div>
          <div class="ml-auto text-right">
            <div class="text-xl font-extrabold text-indigo-700">{{ $sureStr($p['toplam_dk'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-400">toplam</div>
          </div>
        </div>
        <div class="w-full h-1.5 bg-slate-100 rounded-full mb-4 overflow-hidden">
          <div class="h-full bg-gradient-to-r from-indigo-500 to-violet-500 rounded-full" style="width: {{ $oran }}%"></div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-[11px] text-slate-400 uppercase tracking-wide">
                <th class="py-1 pr-2 font-semibold">Tarih</th>
                <th class="py-1 px-2 font-semibold">Giriş</th>
                <th class="py-1 px-2 font-semibold">Çıkış</th>
                <th class="py-1 px-2 font-semibold text-right">Süre</th>
                <th class="py-1 pl-2 font-semibold text-right">Adım</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($kayitlar as $k)
                @php
                  $acik = ($k['durum'] ?? '') === 'acik';
                  $eksik = !$acik && in_array($k['cikis_tip'] ?? '', ['geofence', 'kapanis']);
                @endphp
                <tr class="border-t border-slate-100">
                  <td class="py-2 pr-2 text-slate-500 whitespace-nowrap">{{ $k['tarih'] }}</td>
                  <td class="py-2 px-2 text-slate-700 whitespace-nowrap">{{ $k['giris'] }}</td>
                  <td class="py-2 px-2 whitespace-nowrap">
                    @if ($acik)
                      <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>devam</span>
                    @elseif ($eksik)
                      <span class="text-amber-600" title="Eksik çıkış ({{ $k['cikis_tip'] }})">{{ $k['cikis'] ?: '?' }} ⚠</span>
                    @else
                      <span class="text-slate-700">{{ $k['cikis'] ?: '?' }}</span>
                    @endif
                  </td>
                  <td class="py-2 px-2 text-right font-semibold text-slate-800 whitespace-nowrap">{{ $sureStr($k['dakika'] ?? 0) }}</td>
                  <td class="py-2 pl-2 text-right text-slate-500 whitespace-nowrap">{{ number_format((int) ($k['adim'] ?? 0), 0, ',', '.') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  </div>
@endif
@endsection

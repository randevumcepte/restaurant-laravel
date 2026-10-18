@extends('layout.app')
@section('title', 'Kasa')
@section('baslik', '💰 Kasa')

@section('content')
@php
  $periodlar = ['gun' => 'Bugün', 'hafta' => 'Hafta', 'ay' => 'Ay'];
  $period = $period ?? 'gun';
  $acik = $acik ?? false;
  $giris = (float) ($giris ?? 0);
  $cikis = (float) ($cikis ?? 0);
  $beklenen = (float) ($beklenen ?? 0);
  $devir = (float) ($devir ?? 0);
  $hareketler = $hareketler ?? [];
  $etiket = ['devir' => 'Devir', 'satis' => 'Nakit Satış', 'tahsilat' => 'Tahsilat', 'gider' => 'Gider', 'personel' => 'Personel', 'al' => 'Kasadan Al', 'koy' => 'Kasaya Koy'];
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
@endphp

@if (session('ok'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-semibold">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm font-semibold">{{ session('hata') }}</div>
@endif

{{-- Durum özeti --}}
@if (!$acik)
  <div class="bg-white rounded-2xl p-10 text-center shadow-sm mb-5">
    <div class="text-5xl mb-3">🗄️</div>
    <div class="text-xl font-bold text-slate-800">Kasa kapalı</div>
    <p class="text-sm text-slate-400 mt-2">Bu şubede şu an açık bir kasa vardiyası yok.<br>Kasa, uygulamadan (POS) açılır. Açıldığında güncel durum burada görünür.</p>
    @if (($son_devir ?? 0) > 0)
      <div class="text-xs text-slate-400 mt-3">Son kapanış: <b class="text-slate-600">{{ $tl($son_devir) }}</b></div>
    @endif
  </div>
@else
  <div class="grid gap-4 md:grid-cols-4 mb-5">
    <div class="md:col-span-2 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-2xl p-5 shadow-sm text-white">
      <div class="flex items-center justify-between">
        <span class="text-sm text-white/80">Kasada Beklenen Nakit</span>
        <span class="text-[11px] bg-white/20 rounded-full px-2 py-0.5 font-bold">Açık · {{ $acilis ?? '' }}</span>
      </div>
      <div class="text-3xl font-extrabold mt-1">{{ $tl($beklenen) }}</div>
      <div class="text-xs text-white/80 mt-1">Açan: {{ $acan ?? '-' }} &nbsp;·&nbsp; Devir {{ $tl($devir) }}</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs text-slate-400">Giriş (+)</div>
      <div class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $tl($giris) }}</div>
      <div class="text-[11px] text-slate-400 mt-1">Devir dahil kasaya eklenen</div>
    </div>
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs text-slate-400">Çıkış (−)</div>
      <div class="text-2xl font-extrabold text-rose-500 mt-1">{{ $tl($cikis) }}</div>
      <div class="text-[11px] text-slate-400 mt-1">Kasadan alınan</div>
    </div>
  </div>

  {{-- Manuel kasa giriş/çıkış --}}
  <div class="bg-white rounded-2xl p-5 shadow-sm mb-5">
    <div class="font-bold text-slate-800 mb-3">Manuel Kasa Hareketi</div>
    <form method="POST" action="/kasa/hareket" class="flex flex-wrap items-end gap-3">
      @csrf
      <div>
        <label class="block text-[11px] text-slate-400 mb-1">Yön</label>
        <select name="yon" class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none">
          <option value="giris">Kasaya Koy (+)</option>
          <option value="cikis">Kasadan Al (−)</option>
        </select>
      </div>
      <div>
        <label class="block text-[11px] text-slate-400 mb-1">Tutar</label>
        <input type="number" name="tutar" step="0.01" min="0.01" required placeholder="0,00"
               class="w-32 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none">
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="block text-[11px] text-slate-400 mb-1">Açıklama (opsiyonel)</label>
        <input type="text" name="aciklama" maxlength="200" placeholder="Örn. bozuk para, kasa düzeltme"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-300 outline-none">
      </div>
      <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-lg px-5 py-2 transition">Kaydet</button>
    </form>
    <p class="text-[11px] text-slate-400 mt-2">Kasa açma / sayım-kapatma işlemleri uygulamadan (POS) yapılır.</p>
  </div>
@endif

{{-- Dönem filtresi + hareketler --}}
<div class="flex items-center justify-between mb-3 flex-wrap gap-3">
  <div class="font-bold text-slate-800">Kasa Hareketleri</div>
  <div class="inline-flex bg-white rounded-xl p-1 shadow-sm">
    @foreach ($periodlar as $k => $ad)
      <a href="/kasa?period={{ $k }}"
         class="px-4 py-2 rounded-lg text-sm font-semibold {{ $period === $k ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">{{ $ad }}</a>
    @endforeach
  </div>
</div>

@if (empty($hareketler))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Bu dönemde kasa hareketi yok.</div>
@else
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-[11px] uppercase tracking-wide text-slate-400 border-b border-slate-100">
          <th class="px-4 py-3">Tarih</th>
          <th class="px-4 py-3">Tip</th>
          <th class="px-4 py-3">Açıklama</th>
          <th class="px-4 py-3 text-center">Yön</th>
          <th class="px-4 py-3 text-right">Tutar</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($hareketler as $h)
          @php
            $girisMi = ($h['yon'] ?? '') === 'giris';
            $renk = $girisMi ? 'text-emerald-600' : 'text-rose-500';
          @endphp
          <tr class="border-b border-slate-50 hover:bg-slate-50/60">
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $h['zaman'] ?? '' }}</td>
            <td class="px-4 py-3 font-semibold text-slate-700">{{ $etiket[$h['tip'] ?? ''] ?? ($h['tip'] ?? '-') }}</td>
            <td class="px-4 py-3 text-slate-500">
              {{ $h['aciklama'] ?? '' }}
              @if (!empty($h['personel']))<span class="text-slate-400">· {{ $h['personel'] }}</span>@endif
            </td>
            <td class="px-4 py-3 text-center">
              <span class="inline-block text-[11px] font-bold rounded-full px-2 py-0.5 {{ $girisMi ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500' }}">
                {{ $girisMi ? 'Giriş' : 'Çıkış' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right font-bold {{ $renk }} whitespace-nowrap">{{ $girisMi ? '+' : '−' }}{{ $tl($h['tutar'] ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

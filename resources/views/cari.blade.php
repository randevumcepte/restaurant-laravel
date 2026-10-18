@extends('layout.app')
@section('title', 'Cari Hesaplar')
@section('baslik', '🧮 Cari Hesaplar')

@section('content')
@php
  $tipAd  = ['patron' => 'Patron', 'firma' => 'Firma', 'musteri' => 'Müşteri', 'personel' => 'Personel'];
  $tipRnk = ['patron' => 'bg-violet-500', 'firma' => 'bg-indigo-500', 'musteri' => 'bg-emerald-500', 'personel' => 'bg-amber-500'];
  $tipTxt = ['patron' => 'text-violet-700 bg-violet-100', 'firma' => 'text-indigo-700 bg-indigo-100', 'musteri' => 'text-emerald-700 bg-emerald-100', 'personel' => 'text-amber-700 bg-amber-100'];
  $cariler   = $cariler   ?? [];
  $ozet      = $ozet      ?? ['toplam_alacak' => 0, 'adet' => 0];
  $detay     = $detay     ?? null;   // seçili cari (GET ?id=)
  $hareketler = $hareketler ?? [];
  $aktifId   = $aktifId   ?? 0;
@endphp

@if (session('cari_ok'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">{{ session('cari_ok') }}</div>
@endif
@if (session('cari_hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-semibold">{{ session('cari_hata') }}</div>
@endif

{{-- Özet şeridi --}}
<div class="grid gap-4 sm:grid-cols-3 mb-5">
  <div class="rounded-2xl p-5 text-white shadow-sm bg-gradient-to-br from-violet-600 to-indigo-600 sm:col-span-2">
    <div class="text-sm text-white/70">Toplam Açık Alacak</div>
    <div class="text-3xl font-extrabold mt-1">{{ number_format($ozet['toplam_alacak'], 0, ',', '.') }}₺</div>
    <div class="text-xs text-white/60 mt-1">{{ $ozet['adet'] }} cari hesap</div>
  </div>
  <div class="rounded-2xl p-5 bg-white shadow-sm">
    <div class="text-sm text-slate-400">Cari Sayısı</div>
    <div class="text-3xl font-extrabold text-slate-800 mt-1">{{ $ozet['adet'] }}</div>
    <div class="text-xs text-slate-400 mt-1">müşteri / firma / personel</div>
  </div>
</div>

<div class="grid gap-5 lg:grid-cols-3">
  {{-- Sol: liste + yeni cari --}}
  <div class="lg:col-span-2 space-y-5">
    {{-- Yeni cari ekle --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="font-bold text-slate-800 mb-3">Yeni Cari Hesap</div>
      <form method="POST" action="/cari/ekle" class="grid gap-3 sm:grid-cols-12">
        @csrf
        <input name="ad" required placeholder="İsim / Ünvan"
               class="sm:col-span-5 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
        <input name="telefon" placeholder="Telefon (opsiyonel)"
               class="sm:col-span-3 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 outline-none">
        <select name="tip" class="sm:col-span-2 border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-indigo-300 outline-none">
          <option value="musteri">Müşteri</option>
          <option value="firma">Firma</option>
          <option value="personel">Personel</option>
        </select>
        <button type="submit" class="sm:col-span-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-semibold">Ekle</button>
      </form>
    </div>

    {{-- Cari listesi --}}
    @if (!count($cariler))
      <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Cari hesap yok.</div>
    @else
      <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-slate-400 border-b border-slate-100">
              <th class="px-5 py-3 font-semibold">Cari</th>
              <th class="px-3 py-3 font-semibold">Tip</th>
              <th class="px-3 py-3 font-semibold text-right">Bakiye</th>
              <th class="px-5 py-3 font-semibold text-right"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($cariler as $c)
              @php $bk = (float) $c['bakiye']; @endphp
              <tr class="border-b border-slate-50 hover:bg-slate-50 {{ $aktifId == $c['id'] ? 'bg-indigo-50/60' : '' }}">
                <td class="px-5 py-3">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm {{ $tipRnk[$c['tip']] ?? 'bg-slate-400' }}">
                      {{ mb_substr($c['ad'], 0, 1) }}
                    </div>
                    <div class="min-w-0">
                      <div class="font-semibold text-slate-800 truncate">{{ $c['ad'] }}</div>
                      @if (!empty($c['telefon']))<div class="text-xs text-slate-400">{{ $c['telefon'] }}</div>@endif
                    </div>
                  </div>
                </td>
                <td class="px-3 py-3">
                  <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $tipTxt[$c['tip']] ?? 'text-slate-600 bg-slate-100' }}">{{ $tipAd[$c['tip']] ?? $c['tip'] }}</span>
                </td>
                <td class="px-3 py-3 text-right font-bold {{ $bk > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                  {{ number_format($bk, 0, ',', '.') }}₺
                  <div class="text-[10px] font-normal text-slate-400">{{ $bk > 0 ? 'borç' : 'temiz' }}</div>
                </td>
                <td class="px-5 py-3 text-right">
                  <a href="/cari?id={{ $c['id'] }}" class="text-indigo-600 hover:text-indigo-800 text-xs font-semibold">Detay →</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Sağ: seçili cari detayı + tahsilat --}}
  <div class="space-y-5">
    @if ($detay)
      @php $bk = (float) $detay['bakiye']; @endphp
      <div class="rounded-2xl p-5 text-white shadow-sm {{ $bk > 0 ? 'bg-gradient-to-br from-rose-700 to-rose-900' : 'bg-gradient-to-br from-violet-600 to-indigo-600' }}">
        <div class="flex items-center justify-between">
          <div class="font-bold text-lg truncate">{{ $detay['ad'] }}</div>
          <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/20">{{ $tipAd[$detay['tip']] ?? $detay['tip'] }}</span>
        </div>
        <div class="text-xs text-white/70 mt-3">Güncel Bakiye (Borç)</div>
        <div class="text-3xl font-extrabold">{{ number_format($bk, 0, ',', '.') }}₺</div>
        <div class="text-xs text-white/60 mt-1">
          Toplam satış {{ number_format($detay['toplam_borc'], 0, ',', '.') }}₺ · Tahsilat {{ number_format($detay['toplam_tahsilat'], 0, ',', '.') }}₺
        </div>
      </div>

      {{-- Tahsilat formu --}}
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="font-bold text-slate-800 mb-3">Tahsilat (Borç Kapat)</div>
        <form method="POST" action="/cari/tahsilat" class="space-y-3">
          @csrf
          <input type="hidden" name="cari_id" value="{{ $aktifId }}">
          <input type="number" name="tutar" step="0.01" min="0.01" required
                 value="{{ $bk > 0 ? round($bk) : '' }}" placeholder="Tutar (₺)"
                 class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-300 outline-none">
          <select name="odeme_sekli" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-emerald-300 outline-none">
            <option value="nakit">Nakit</option>
            <option value="havale">Havale</option>
            <option value="kredi">Kredi Kartı</option>
          </select>
          <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2.5 text-sm font-bold">Tahsil Et</button>
        </form>
      </div>

      {{-- Hareketler --}}
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="font-bold text-slate-800 mb-3">Hesap Hareketleri</div>
        @if (!count($hareketler))
          <div class="text-sm text-slate-400 text-center py-4">Hareket yok.</div>
        @else
          <div class="space-y-2">
            @foreach ($hareketler as $h)
              @php $borc = ($h['tip'] === 'borc'); @endphp
              <div class="flex items-center gap-3 border border-slate-100 rounded-lg px-3 py-2">
                <div class="text-lg {{ $borc ? 'text-rose-500' : 'text-emerald-500' }}">{{ $borc ? '↑' : '↓' }}</div>
                <div class="min-w-0 flex-1">
                  <div class="text-sm font-semibold text-slate-700">
                    {{ $borc ? 'Satış (Borç)' : 'Tahsilat' . (!empty($h['odeme_sekli']) ? ' · ' . $h['odeme_sekli'] : '') }}
                  </div>
                  <div class="text-[11px] text-slate-400">{{ $h['zaman'] }}</div>
                </div>
                <div class="font-bold {{ $borc ? 'text-rose-600' : 'text-emerald-600' }}">
                  {{ $borc ? '+' : '-' }}{{ number_format($h['tutar'], 0, ',', '.') }}₺
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    @else
      <div class="bg-white rounded-2xl p-8 text-center text-slate-400 shadow-sm">
        <div class="text-3xl mb-2">🧮</div>
        Detay ve tahsilat için soldaki listeden bir cari seçin.
      </div>
    @endif
  </div>
</div>
@endsection

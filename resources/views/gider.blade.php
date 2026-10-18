@extends('layout.app')
@section('title', 'Giderler')
@section('baslik', '🧾 Giderler')

@section('content')
@php
  $katAd = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme', 'maas' => 'Maaş / Personel', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $katRenk = ['kira' => 'amber', 'fatura' => 'indigo', 'malzeme' => 'emerald', 'maas' => 'violet', 'vergi' => 'rose', 'diger' => 'slate'];
  $aylar = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
  $yil = (int) substr($ay, 0, 4);
  $aySayi = (int) substr($ay, 5, 2);
  $ayMetin = ($aylar[$aySayi] ?? '') . ' ' . $yil;
  // Önceki / sonraki ay
  $oncekiTs = mktime(0, 0, 0, $aySayi - 1, 1, $yil);
  $sonrakiTs = mktime(0, 0, 0, $aySayi + 1, 1, $yil);
  $onceki = date('Y-m', $oncekiTs);
  $sonraki = date('Y-m', $sonrakiTs);
  $sonrakiVar = $sonrakiTs <= mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));
@endphp

{{-- Üst bar: ay gezgini + toplam + Gider Ekle --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="inline-flex items-center bg-white rounded-xl p-1 shadow-sm">
    <a href="/gider?ay={{ $onceki }}" class="px-3 py-2 rounded-lg text-slate-500 hover:bg-slate-100">‹</a>
    <span class="px-4 py-2 text-sm font-bold text-slate-800">{{ $ayMetin }}</span>
    @if ($sonrakiVar)
      <a href="/gider?ay={{ $sonraki }}" class="px-3 py-2 rounded-lg text-slate-500 hover:bg-slate-100">›</a>
    @else
      <span class="px-3 py-2 rounded-lg text-slate-200">›</span>
    @endif
  </div>
  <div class="flex items-center gap-3">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm text-sm"><span class="text-slate-400">Toplam</span> <b class="text-rose-600 ml-1">{{ number_format($toplam, 0, ',', '.') }}₺</b></div>
    <button type="button" onclick="document.getElementById('giderEkleModal').classList.remove('hidden')"
            class="px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold shadow-sm">+ Gider Ekle</button>
  </div>
</div>

{{-- Kategori özet kartları --}}
@if (!empty($kategoriler))
  <div class="grid gap-3 grid-cols-2 md:grid-cols-3 xl:grid-cols-6 mb-5">
    @foreach ($kategoriler as $kat => $tutarKat)
      @php $renk = $katRenk[$kat] ?? 'slate'; @endphp
      <div class="bg-white rounded-2xl p-4 shadow-sm border-l-4 border-{{ $renk }}-400">
        <div class="text-[11px] text-slate-400 mb-0.5">{{ $katAd[$kat] ?? ucfirst($kat) }}</div>
        <div class="text-lg font-extrabold text-slate-800">{{ number_format($tutarKat, 0, ',', '.') }}₺</div>
      </div>
    @endforeach
  </div>
@endif

{{-- Liste --}}
@if (empty($giderler))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Bu ay gider kaydı yok.</div>
@else
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-400 border-b border-slate-100">
          <th class="px-4 py-3 font-semibold">Kategori</th>
          <th class="px-4 py-3 font-semibold">Açıklama</th>
          <th class="px-4 py-3 font-semibold">Tarih</th>
          <th class="px-4 py-3 font-semibold text-right">Tutar</th>
          <th class="px-4 py-3 font-semibold text-right">İşlem</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($giderler as $g)
          @php $kat = $g->kategori; $renk = $katRenk[$kat] ?? 'slate'; @endphp
          <tr class="border-b border-slate-50 hover:bg-slate-50">
            <td class="px-4 py-3">
              <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-semibold bg-{{ $renk }}-50 text-{{ $renk }}-600">{{ $katAd[$kat] ?? ucfirst($kat) }}</span>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ $g->aciklama ?: '—' }}</td>
            <td class="px-4 py-3 text-slate-500">{{ $g->tarih }}</td>
            <td class="px-4 py-3 text-right font-bold text-slate-800">{{ number_format($g->tutar, 2, ',', '.') }}₺</td>
            <td class="px-4 py-3 text-right">
              @if ($kat !== 'maas')
                <form method="POST" action="/gider/sil" onsubmit="return confirm('Bu gideri silmek istediğinize emin misiniz?')" class="inline">
                  @csrf
                  <input type="hidden" name="id" value="{{ $g->id }}">
                  <input type="hidden" name="ay" value="{{ $ay }}">
                  <button type="submit" class="text-slate-400 hover:text-rose-500 font-bold px-2" title="Sil">✕</button>
                </form>
              @else
                <span class="text-slate-300 text-xs">otomatik</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- Gider Ekle Modal --}}
<div id="giderEkleModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
    <div class="flex items-center justify-between mb-5">
      <h3 class="text-lg font-bold text-slate-800">Yeni Gider</h3>
      <button type="button" onclick="document.getElementById('giderEkleModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl leading-none">✕</button>
    </div>
    <form method="POST" action="/gider/ekle" class="space-y-4">
      @csrf
      <input type="hidden" name="ay" value="{{ $ay }}">
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Kategori</label>
        <select name="kategori" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 focus:border-violet-400 outline-none">
          <option value="kira">Kira</option>
          <option value="fatura">Fatura (elektrik/su/doğalgaz)</option>
          <option value="malzeme">Malzeme / Alım</option>
          <option value="vergi">Vergi / Resmi</option>
          <option value="diger">Diğer</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Tutar (TL)</label>
        <input type="number" name="tutar" step="0.01" min="0" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 focus:border-violet-400 outline-none" placeholder="0">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Tarih</label>
        <input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 focus:border-violet-400 outline-none">
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Açıklama (opsiyonel)</label>
        <input type="text" name="aciklama" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-400 focus:border-violet-400 outline-none" placeholder="Örn. Eylül kirası">
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="nakit" value="1" checked class="rounded border-slate-300 text-violet-600 focus:ring-violet-400">
        Nakit gider (açık vardiya varsa kasadan düş)
      </label>
      <div class="flex gap-3 pt-2">
        <button type="button" onclick="document.getElementById('giderEkleModal').classList.add('hidden')" class="flex-1 rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-500 hover:bg-slate-50">Vazgeç</button>
        <button type="submit" class="flex-1 rounded-xl bg-violet-600 hover:bg-violet-700 py-2.5 text-sm font-semibold text-white">Kaydet</button>
      </div>
    </form>
  </div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Tedarikçiler')
@section('baslik', '🚚 Tedarikçiler')

@section('content')
@php
  $list = isset($tedarikciler) ? $tedarikciler : [];
  $toplamAlis = 0; $toplamFatura = 0;
  foreach ($list as $t) { $toplamAlis += (float) ($t->toplam_alis ?? 0); $toplamFatura += (int) ($t->fatura_sayisi ?? 0); }
@endphp

@if (session('ted_ok'))
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('ted_ok') }}</div>
@endif
@if (session('ted_hata'))
  <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('ted_hata') }}</div>
@endif

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Tedarikçi</span> <b class="text-slate-800 ml-1">{{ count($list) }}</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Toplam alış</span> <b class="text-indigo-700 ml-1">{{ number_format($toplamAlis, 0, ',', '.') }}₺</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Fatura</span> <b class="text-slate-800 ml-1">{{ $toplamFatura }}</b></div>
  </div>
  <button type="button" onclick="tedForm()"
          class="px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm">+ Yeni Tedarikçi</button>
</div>

@if (!count($list))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Henüz tedarikçi yok. Sağ üstten ekleyin.</div>
@else
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-semibold">Tedarikçi</th>
          <th class="px-5 py-3 font-semibold">Telefon</th>
          <th class="px-5 py-3 font-semibold">Not</th>
          <th class="px-5 py-3 font-semibold text-right">Toplam Alış</th>
          <th class="px-5 py-3 font-semibold text-right">Fatura</th>
          <th class="px-5 py-3 font-semibold text-right">İşlem</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($list as $t)
          <tr class="border-b border-slate-50 last:border-0 hover:bg-slate-50/60">
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">{{ mb_substr($t->ad, 0, 1) }}</div>
                <span class="font-semibold text-slate-800">{{ $t->ad }}</span>
              </div>
            </td>
            <td class="px-5 py-3 text-slate-600">{{ $t->telefon ?: '—' }}</td>
            <td class="px-5 py-3 text-slate-400 max-w-xs truncate">{{ $t->aciklama ?: '—' }}</td>
            <td class="px-5 py-3 text-right font-bold text-slate-800">{{ number_format((float) ($t->toplam_alis ?? 0), 0, ',', '.') }}₺</td>
            <td class="px-5 py-3 text-right text-slate-500">{{ (int) ($t->fatura_sayisi ?? 0) }}</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">
              <button type="button"
                      onclick="tedForm({{ (int) $t->id }}, {{ \Illuminate\Support\Js::from($t->ad) }}, {{ \Illuminate\Support\Js::from($t->telefon) }}, {{ \Illuminate\Support\Js::from($t->aciklama) }})"
                      class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200">Düzenle</button>
              <form action="/tedarikci/sil" method="POST" class="inline" onsubmit="return confirm('{{ $t->ad }} silinsin mi? Bu işlem geri alınamaz.')">
                @csrf
                <input type="hidden" name="id" value="{{ (int) $t->id }}">
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100">Sil</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- Ekle / Düzenle modal --}}
<div id="tedModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4">
  <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
    <div class="flex items-center justify-between mb-5">
      <h3 id="tedModalBaslik" class="text-lg font-bold text-slate-800">Yeni Tedarikçi</h3>
      <button type="button" onclick="tedKapat()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
    </div>
    <form action="/tedarikci/kaydet" method="POST" class="space-y-4">
      @csrf
      <input type="hidden" name="id" id="tedId" value="">
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Firma / Kişi adı</label>
        <input type="text" name="ad" id="tedAd" required
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Telefon</label>
        <input type="text" name="telefon" id="tedTel"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-600 mb-1">Not (opsiyonel)</label>
        <input type="text" name="aciklama" id="tedAciklama"
               class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
      </div>
      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="tedKapat()" class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-500 hover:bg-slate-100">Vazgeç</button>
        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-600 text-white hover:bg-indigo-700">Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
  function tedForm(id, ad, tel, aciklama) {
    var m = document.getElementById('tedModal');
    document.getElementById('tedModalBaslik').textContent = id ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi';
    document.getElementById('tedId').value = id || '';
    document.getElementById('tedAd').value = ad || '';
    document.getElementById('tedTel').value = tel || '';
    document.getElementById('tedAciklama').value = aciklama || '';
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.getElementById('tedAd').focus();
  }
  function tedKapat() {
    var m = document.getElementById('tedModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }
  document.getElementById('tedModal').addEventListener('click', function (e) { if (e.target === this) tedKapat(); });
</script>
@endsection

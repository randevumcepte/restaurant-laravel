@extends('layout.app')
@section('title', 'Sayım')
@section('baslik', '📋 Sayım')

@section('content')
@php
  $malzemeler = $malzemeler ?? [];
  $sonSayimlar = $sonSayimlar ?? [];
  $toplamKalem = count($malzemeler);
@endphp

@if (session('sayim_sonuc'))
  @php $s = session('sayim_sonuc'); $fm = (float) ($s['toplam_fark_maliyet'] ?? 0); @endphp
  <div class="mb-5 rounded-2xl p-5 shadow-sm {{ $fm < 0 ? 'bg-rose-50 border border-rose-200' : ($fm > 0 ? 'bg-amber-50 border border-amber-200' : 'bg-emerald-50 border border-emerald-200') }}">
    <div class="font-bold text-slate-800 mb-1">Sayım kaydedildi</div>
    <div class="text-sm text-slate-500">{{ $s['sayilan_kalem'] ?? 0 }} kalem sayıldı · {{ $s['eksik'] ?? 0 }} eksik · {{ $s['fazla'] ?? 0 }} fazla</div>
    <div class="mt-1 font-bold {{ $fm < 0 ? 'text-rose-600' : ($fm > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
      {{ $fm < 0 ? 'Toplam açık: ' . number_format(abs($fm), 0, ',', '.') . '₺ (kayıp)' : ($fm > 0 ? 'Toplam fazla: ' . number_format($fm, 0, ',', '.') . '₺' : 'Fark yok — tertemiz.') }}
    </div>
  </div>
@endif

@if (session('sayim_hata'))
  <div class="mb-5 rounded-2xl p-4 bg-rose-50 border border-rose-200 text-rose-700 text-sm shadow-sm">{{ session('sayim_hata') }}</div>
@endif

<div class="mb-5 rounded-2xl p-4 bg-sky-50 border border-sky-200 text-sky-800 text-sm shadow-sm flex items-start gap-2">
  <span>ℹ️</span>
  <span>Fiili sayımı girin. Sistem "olması gereken" (teorik defter stoğu) ile karşılaştırıp farkı (kayıp/fazla) çıkarır. Sadece saydıklarınızı girin — boş bırakılan satırlar işlenmez.</span>
</div>

@if (!$malzemeler)
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Stok takipli malzeme yok.</div>
@else
<form method="POST" action="/sayim/kaydet">
  @csrf
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100 flex-wrap gap-3">
      <div>
        <input type="text" id="sayimAra" placeholder="Malzeme ara…"
               class="border border-slate-200 rounded-lg px-3 py-2 text-sm w-56 focus:outline-none focus:ring-2 focus:ring-indigo-200"
               onkeyup="sayimFiltre()">
        <span class="text-xs text-slate-400 ml-2">{{ $toplamKalem }} kalem</span>
      </div>
      <button type="submit"
              class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm shadow-sm">
        Sayımı Kaydet
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-400 text-xs uppercase">
          <tr>
            <th class="text-left font-semibold px-5 py-3">Malzeme</th>
            <th class="text-left font-semibold px-5 py-3">Kategori</th>
            <th class="text-right font-semibold px-5 py-3">Sistemde (teorik)</th>
            <th class="text-center font-semibold px-5 py-3 w-40">Sayılan (fiili)</th>
            <th class="text-right font-semibold px-5 py-3 w-28">Fark</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100" id="sayimTablo">
          @foreach ($malzemeler as $m)
            @php
              $teorik = (float) ($m['teorik'] ?? 0);
              $teorikFmt = $teorik == round($teorik) ? number_format($teorik, 0, ',', '.') : rtrim(rtrim(number_format($teorik, 3, ',', '.'), '0'), ',');
            @endphp
            <tr class="sayim-satir hover:bg-slate-50" data-ad="{{ mb_strtolower($m['ad']) }}">
              <td class="px-5 py-3 font-medium text-slate-800">{{ $m['ad'] }}</td>
              <td class="px-5 py-3 text-slate-400">{{ $m['kategori'] ?? '—' }}</td>
              <td class="px-5 py-3 text-right text-slate-500">{{ $teorikFmt }} <span class="text-xs text-slate-400">{{ $m['birim'] }}</span></td>
              <td class="px-5 py-3 text-center">
                <input type="number" step="any" min="0" name="sayilan[{{ $m['malzeme_id'] }}]"
                       class="sayim-input border border-slate-200 rounded-lg px-2 py-1.5 text-center w-28 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                       data-teorik="{{ $teorik }}" placeholder="Say" oninput="sayimFark(this)">
              </td>
              <td class="px-5 py-3 text-right font-semibold text-slate-300 sayim-fark">—</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</form>
@endif

@if ($sonSayimlar)
  <h3 class="text-sm font-bold text-slate-500 uppercase mb-3">Son Sayımlar</h3>
  <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($sonSayimlar as $s)
      @php $fm = (float) $s['fark_maliyet']; @endphp
      <div class="bg-white rounded-2xl p-4 shadow-sm flex items-center justify-between">
        <div>
          <div class="font-semibold text-slate-800">{{ $s['tarih'] }}</div>
          <div class="text-xs text-slate-400">{{ $s['kalem'] }} kalem · {{ $s['eksik'] }} eksik</div>
        </div>
        <div class="font-bold {{ $fm < 0 ? 'text-rose-600' : ($fm > 0 ? 'text-amber-600' : 'text-emerald-600') }}">
          {{ $fm < 0 ? '-' . number_format(abs($fm), 0, ',', '.') . '₺' : ($fm > 0 ? '+' . number_format($fm, 0, ',', '.') . '₺' : '0') }}
        </div>
      </div>
    @endforeach
  </div>
@endif

<script>
  function sayimFiltre() {
    var q = (document.getElementById('sayimAra').value || '').toLowerCase().trim();
    document.querySelectorAll('.sayim-satir').forEach(function (tr) {
      tr.style.display = (!q || (tr.getAttribute('data-ad') || '').indexOf(q) !== -1) ? '' : 'none';
    });
  }
  function sayimFark(inp) {
    var td = inp.closest('tr').querySelector('.sayim-fark');
    var t = parseFloat(inp.getAttribute('data-teorik')) || 0;
    var v = inp.value.trim();
    if (v === '') { td.textContent = '—'; td.className = 'px-5 py-3 text-right font-semibold text-slate-300 sayim-fark'; return; }
    var f = (parseFloat(v.replace(',', '.')) || 0) - t;
    var r = Math.round(f * 10000) / 10000;
    var txt = (r > 0 ? '+' : '') + (r === Math.round(r) ? r : r.toFixed(2));
    var cls = Math.abs(r) < 0.0001 ? 'text-emerald-600' : (r < 0 ? 'text-rose-600' : 'text-amber-600');
    td.textContent = txt;
    td.className = 'px-5 py-3 text-right font-semibold sayim-fark ' + cls;
  }
</script>
@endsection

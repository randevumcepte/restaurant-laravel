@extends('layout.app')
@section('title', 'Barkod Yönetim')
@section('baslik', '🏷️ Barkod Yönetim')

@section('content')
@php
  $urunler = $urunler ?? [];
  $malzemeler = $malzemeler ?? [];
  $barkodlar = $barkodlar ?? [];
  $urunSay = collect($barkodlar)->where('tur', 'urun')->count();
  $malSay = collect($barkodlar)->where('tur', 'malzeme')->count();
@endphp

@if (session('ok'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-semibold">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm font-semibold">{{ session('hata') }}</div>
@endif

<div class="flex gap-3 text-sm mb-5 flex-wrap">
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Toplam barkod</span> <b class="text-slate-800 ml-1">{{ count($barkodlar) }}</b></div>
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Ürün</span> <b class="text-slate-800 ml-1">{{ $urunSay }}</b></div>
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Malzeme</span> <b class="text-slate-800 ml-1">{{ $malSay }}</b></div>
</div>

<div class="grid gap-5 lg:grid-cols-3">
  {{-- Barkod ekle --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="font-bold text-slate-800 mb-4 flex items-center gap-2"><span>➕</span> Yeni Barkod</div>
      <form method="POST" action="/barkod/ekle" class="space-y-3" id="barkodForm">
        @csrf
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Barkod</label>
          <input type="text" name="barkod" required autocomplete="off"
                 placeholder="Barkod numarası"
                 class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Tür</label>
          <div class="inline-flex bg-slate-100 rounded-xl p-1 w-full">
            <label class="flex-1 text-center cursor-pointer">
              <input type="radio" name="tur" value="urun" class="peer sr-only" checked onchange="barkodTurDegis()">
              <span class="block px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-500 peer-checked:bg-indigo-600 peer-checked:text-white">Ürün</span>
            </label>
            <label class="flex-1 text-center cursor-pointer">
              <input type="radio" name="tur" value="malzeme" class="peer sr-only" onchange="barkodTurDegis()">
              <span class="block px-3 py-1.5 rounded-lg text-sm font-semibold text-slate-500 peer-checked:bg-indigo-600 peer-checked:text-white">Malzeme</span>
            </label>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Bağlanacak öğe</label>
          <select name="hedef_id_urun" id="selUrun"
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <option value="">— Ürün seç —</option>
            @foreach ($urunler as $u)
              <option value="{{ $u->id }}">{{ $u->ad }}</option>
            @endforeach
          </select>
          <select name="hedef_id_malzeme" id="selMalzeme" disabled
                  class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300 hidden">
            <option value="">— Malzeme seç —</option>
            @foreach ($malzemeler as $m)
              <option value="{{ $m->id }}">{{ $m->ad }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl px-4 py-2.5 text-sm transition">Barkod Ekle</button>
        <p class="text-[11px] text-slate-400 leading-relaxed">Aynı barkod başka bir öğede tanımlıysa eklenmez. Bir öğenin birden çok barkodu olabilir.</p>
      </form>
    </div>
  </div>

  {{-- Tanımlı barkodlar --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 font-bold text-slate-800 flex items-center gap-2"><span>📋</span> Tanımlı Barkodlar</div>
      @if (!count($barkodlar))
        <div class="p-10 text-center text-slate-400 text-sm">Henüz barkod tanımlı değil.</div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-slate-400 text-xs border-b border-slate-100">
                <th class="px-5 py-3 font-semibold">Barkod</th>
                <th class="px-5 py-3 font-semibold">Tür</th>
                <th class="px-5 py-3 font-semibold">Bağlı öğe</th>
                <th class="px-5 py-3 font-semibold text-right">İşlem</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($barkodlar as $b)
                <tr class="border-b border-slate-50 hover:bg-slate-50/60">
                  <td class="px-5 py-3 font-mono font-bold text-slate-700 tracking-wide">{{ $b->barkod }}</td>
                  <td class="px-5 py-3">
                    @if ($b->tur === 'urun')
                      <span class="inline-block px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-600 text-xs font-semibold">Ürün</span>
                    @else
                      <span class="inline-block px-2.5 py-1 rounded-full bg-amber-50 text-amber-600 text-xs font-semibold">Malzeme</span>
                    @endif
                  </td>
                  <td class="px-5 py-3 text-slate-700">
                    {{ $b->hedef_ad ?? ('#' . $b->hedef_id . ' (silinmiş)') }}
                  </td>
                  <td class="px-5 py-3 text-right">
                    <form method="POST" action="/barkod/sil" onsubmit="return confirm('Bu barkodu sil?')" class="inline">
                      @csrf
                      <input type="hidden" name="id" value="{{ $b->id }}">
                      <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Sil</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>

<script>
  function barkodTurDegis() {
    var tur = document.querySelector('input[name="tur"]:checked').value;
    var su = document.getElementById('selUrun');
    var sm = document.getElementById('selMalzeme');
    if (tur === 'urun') {
      su.classList.remove('hidden'); su.disabled = false;
      sm.classList.add('hidden'); sm.disabled = true;
    } else {
      sm.classList.remove('hidden'); sm.disabled = false;
      su.classList.add('hidden'); su.disabled = true;
    }
  }
</script>
@endsection

@extends('layout.app')
@section('title', 'Sebep Yönetimi')
@section('baslik', '🏷️ Sebep Yönetimi')

@section('content')
@php
  $turler = ['void' => 'Ürün Silme', 'indirim' => 'İskonto', 'ikram' => 'İkram', 'iptal' => 'İptal'];
  $renk = ['void' => 'rose', 'indirim' => 'emerald', 'ikram' => 'amber', 'iptal' => 'slate'];
  $gruplar = $gruplar ?? [];
@endphp

<p class="text-sm text-slate-500 mb-5">İptal, ürün silme (void), ikram ve iskonto işlemlerinde personele gösterilecek hazır sebepleri yönetin. Bu sebepler hem POS'ta hem mobil uygulamada kullanılır.</p>

@if (session('sebep_ok'))
  <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">{{ session('sebep_ok') }}</div>
@endif
@if (session('sebep_hata'))
  <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-3 text-sm">{{ session('sebep_hata') }}</div>
@endif

<div class="grid gap-5 md:grid-cols-2">
  @foreach ($turler as $tur => $ad)
    @php
      $c = $renk[$tur];
      $liste = $gruplar[$tur] ?? [];
    @endphp
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col">
      <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="inline-block w-2.5 h-2.5 rounded-full bg-{{ $c }}-500"></span>
          <h2 class="font-bold text-slate-800">{{ $ad }}</h2>
        </div>
        <span class="text-xs text-slate-400 bg-slate-50 rounded-full px-2.5 py-1">{{ count($liste) }} sebep</span>
      </div>

      <div class="p-4 flex-1">
        @if (empty($liste))
          <div class="text-center text-slate-400 text-sm py-6">Bu türde henüz sebep yok.</div>
        @else
          <div class="space-y-2">
            @foreach ($liste as $s)
              <div class="flex items-center gap-2 bg-slate-50 rounded-lg px-3 py-2">
                <span class="flex-1 text-sm text-slate-700">{{ $s->metin }}</span>
                <form method="POST" action="/sebep-yonetimi/sil" onsubmit="return confirm('Bu sebep silinsin mi?');">
                  @csrf
                  <input type="hidden" name="id" value="{{ $s->id }}">
                  <button type="submit" class="text-rose-400 hover:text-rose-600 transition" title="Sil">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </form>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <div class="px-4 py-3 border-t border-slate-100 bg-slate-50/50">
        <form method="POST" action="/sebep-yonetimi/ekle" class="flex gap-2">
          @csrf
          <input type="hidden" name="tur" value="{{ $tur }}">
          <input type="text" name="metin" required maxlength="120" placeholder="Yeni sebep ekle..."
                 class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
          <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm font-semibold transition">Ekle</button>
        </form>
      </div>
    </div>
  @endforeach
</div>
@endsection

@extends('layout.app')
@section('title', 'POS')
@section('baslik', 'POS / Masa Haritası')

@section('content')
@php
    $renk = [
        'bos' => 'bg-white border-slate-200 hover:border-indigo-400',
        'dolu' => 'bg-indigo-50 border-indigo-300',
        'rezerve' => 'bg-amber-50 border-amber-300',
        'kirli' => 'bg-orange-50 border-orange-300',
        'birlesik' => 'bg-violet-50 border-violet-300',
    ];
@endphp

<div class="flex items-center justify-between gap-3 mb-4 bg-white border border-slate-200 rounded-xl px-4 py-2.5">
    <div class="text-sm text-slate-600">👤 Garson: <b id="posGarsonAd" class="text-indigo-600">—</b></div>
    <button onclick="posGarsonSec()" class="text-xs font-semibold text-indigo-600 border border-indigo-200 rounded-lg px-3 py-1.5 hover:bg-indigo-50">Değiştir</button>
</div>

<div class="flex items-center gap-4 mb-5 text-xs text-slate-500">
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-white border border-slate-300"></span> Boş</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-indigo-100 border border-indigo-300"></span> Dolu</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Rezerve</span>
    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-orange-100 border border-orange-300"></span> Temizlenecek</span>
</div>

@foreach ($bolgeler as $bolge)
    <div class="mb-6">
        <h2 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">📍 {{ $bolge->ad }}
            <span class="text-xs font-normal text-slate-400">({{ count($masalar[$bolge->id] ?? []) }} masa)</span></h2>
        <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-3">
            @foreach ($masalar[$bolge->id] ?? [] as $masa)
                @php $ad = $acik[$masa->id] ?? null; @endphp
                <a href="/pos/masa/{{ $masa->id }}"
                   class="aspect-square rounded-2xl border-2 p-2 flex flex-col items-center justify-center text-center transition {{ $renk[$masa->durum] ?? $renk['bos'] }}">
                    <div class="font-bold text-slate-800 text-sm leading-tight">{{ $masa->ad }}</div>
                    <div class="text-[10px] text-slate-400">{{ $masa->kapasite }} kişi</div>
                    @if ($ad)
                        <div class="mt-1 text-xs font-bold text-indigo-600">{{ number_format((float) $ad->toplam, 0, ',', '.') }} ₺</div>
                        <div class="text-[9px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($ad->acilis)->diffForHumans(null, true) }}</div>
                    @else
                        <div class="mt-1 text-[10px] text-slate-300">boş</div>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
@endforeach

{{-- Garson seçim modalı --}}
<div id="posGarsonModal" style="display:none" class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative bg-white rounded-2xl p-5 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-slate-800 mb-1">Garson Seç</h3>
        <p class="text-xs text-slate-500 mb-4">Aldığın siparişler sana yazılır. Bu cihaz seçimi hatırlar.</p>
        <div class="grid grid-cols-2 gap-2 max-h-80 overflow-y-auto">
            @foreach ($garsonlar as $g)
                <button class="pg-btn border border-slate-200 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 hover:border-indigo-400 hover:bg-indigo-50 text-left"
                        data-id="{{ $g->id }}" data-ad="{{ $g->ad }}">
                    {{ $g->ad }}<span class="block text-[10px] text-slate-400">{{ $g->rol }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function posGarsonGoster() { document.getElementById('posGarsonAd').textContent = localStorage.getItem('pos_garson_ad') || '—'; }
    function posGarsonSec() { document.getElementById('posGarsonModal').style.display = 'flex'; }
    function posGarsonAta(id, ad) { localStorage.setItem('pos_garson_id', id); localStorage.setItem('pos_garson_ad', ad); document.getElementById('posGarsonModal').style.display = 'none'; posGarsonGoster(); }
    document.addEventListener('DOMContentLoaded', function () {
        posGarsonGoster();
        document.querySelectorAll('.pg-btn').forEach(function (b) { b.onclick = function () { posGarsonAta(b.dataset.id, b.dataset.ad); }; });
        if (!localStorage.getItem('pos_garson_id')) posGarsonSec();
    });
</script>
@endpush

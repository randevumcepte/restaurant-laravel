@extends('layout.app')
@section('title', 'Patron Özet')
@section('baslik', '👑 Patron Özet — Hızlı Karşılaştırma')

@section('content')
@php
    $para = fn ($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
    $yuzde = function ($simdi, $onceki) {
        if ($onceki <= 0) return null;
        return round(($simdi - $onceki) / $onceki * 100, 1);
    };
@endphp

@php
    $rozet = function ($y) {
        if ($y === null) return '<span class="text-xs text-slate-400">—</span>';
        $up = $y >= 0;
        $renk = $up ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
        $ok = $up ? '▲' : '▼';
        return '<span class="text-xs font-bold px-2 py-0.5 rounded-full ' . $renk . '">' . $ok . ' %' . number_format(abs($y), 1, ',', '.') . '</span>';
    };
@endphp

<div class="max-w-2xl mx-auto">

    {{-- Olay / anomali seridi --}}
    @if (count($uyarilar))
        <div class="space-y-2 mb-5">
            @foreach ($uyarilar as $u)
                <div class="flex items-center gap-3 px-4 py-2.5 rounded-xl bg-{{ $u['renk'] }}-50 border border-{{ $u['renk'] }}-100">
                    <span class="text-lg">{{ $u['ikon'] }}</span>
                    <span class="text-sm text-{{ $u['renk'] }}-700 font-medium">{{ $u['msg'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- BUGUN hero --}}
    <div class="bg-gradient-to-br from-indigo-600 to-violet-600 text-white rounded-3xl p-6 mb-4 shadow-lg">
        <div class="text-indigo-100 text-sm font-medium">Bugünkü Ciro</div>
        <div class="text-4xl font-bold mt-1">{{ $para($bugun) }}</div>
        <div class="flex flex-wrap gap-2 mt-4">
            <div class="bg-white/15 rounded-xl px-3 py-2">
                <div class="text-indigo-100 text-[11px]">Düne göre (aynı saat)</div>
                <div class="font-semibold text-sm">{!! $rozet($yuzde($bugun, $dun)) !!} <span class="text-indigo-200 text-xs ml-1">dün {{ $para($dun) }}</span></div>
            </div>
            <div class="bg-white/15 rounded-xl px-3 py-2">
                <div class="text-indigo-100 text-[11px]">Geçen hafta bugün</div>
                <div class="font-semibold text-sm">{!! $rozet($yuzde($bugun, $gecenHaftaGun)) !!} <span class="text-indigo-200 text-xs ml-1">{{ $para($gecenHaftaGun) }}</span></div>
            </div>
        </div>
    </div>

    {{-- Hafta / Ay --}}
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Bu Hafta</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $para($buHafta) }}</div>
            <div class="mt-2">{!! $rozet($yuzde($buHafta, $oncekiHafta)) !!} <span class="text-xs text-slate-400 ml-1">geçen hafta {{ $para($oncekiHafta) }}</span></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="text-xs uppercase tracking-wide text-slate-400 font-semibold">Bu Ay</div>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $para($buAy) }}</div>
            <div class="mt-2">{!! $rozet($yuzde($buAy, $oncekiAy)) !!} <span class="text-xs text-slate-400 ml-1">geçen ay {{ $para($oncekiAy) }}</span></div>
        </div>
    </div>

    {{-- Canli mini --}}
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-amber-600">{{ $acikMasa }}</div>
            <div class="text-xs text-slate-400 mt-1">Açık Masa</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center">
            <div class="text-lg font-bold text-indigo-600">{{ $para($acikTutar) }}</div>
            <div class="text-xs text-slate-400 mt-1">Masada Bekleyen</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center">
            <div class="text-2xl font-bold text-slate-700">{{ $bugunAdisyon }}</div>
            <div class="text-xs text-slate-400 mt-1">Bugün Adisyon</div>
        </div>
    </div>

    {{-- Bugun en cok satan --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-3">🔥 Bugün En Çok Satan</h2>
        <div class="space-y-2">
            @forelse ($topBugun as $i => $t)
                <div class="flex items-center justify-between py-1.5">
                    <span class="text-sm text-slate-700"><span class="text-slate-400 mr-2">{{ $i + 1 }}.</span>{{ $t->urun_adi }}</span>
                    <span class="text-sm font-semibold text-slate-600">{{ (int) $t->a }} adet</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">Bugün henüz satış yok.</p>
            @endforelse
        </div>
    </div>

    <p class="text-center text-xs text-slate-400 mt-5">Tek bakışta, sıfır tık — anlık ve doğru. (Kaynak: immutable ödeme defteri)</p>
</div>
@endsection

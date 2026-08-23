@extends('layout.app')
@section('title', 'Sadakat')
@section('baslik', '🎁 Sadakat & Kampanyalar')

@section('content')
@php
    $tipAd = [
        'yuzde' => 'Yüzde indirim', 'tutar' => 'Tutar indirim', 'ikinci_bedava' => '2. ürün bedava',
        'urun_hediye' => 'Ürün hediye', 'puan_carpani' => 'Puan çarpanı',
    ];
    $tipDeger = function ($k) {
        if ($k->tip === 'yuzde') return '%' . rtrim(rtrim(number_format($k->deger, 2, ',', '.'), '0'), ',');
        if ($k->tip === 'tutar') return number_format($k->deger, 0, ',', '.') . ' ₺';
        if ($k->tip === 'puan_carpani') return $k->deger . 'x puan';
        return '—';
    };
@endphp

<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Aktif Kampanya</div><div class="text-2xl font-bold text-indigo-600 mt-1">{{ $aktifKampanya }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Toplam Sadakat Puanı</div><div class="text-2xl font-bold text-amber-600 mt-1">{{ number_format($toplamPuan, 0, ',', '.') }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Kayıtlı Müşteri</div><div class="text-2xl font-bold text-slate-700 mt-1">{{ number_format($musteriSayisi, 0, ',', '.') }}</div></div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Kampanyalar</h2>
        <div class="space-y-3">
            @foreach ($kampanyalar as $k)
                <div x-data="{ aktif: {{ $k->aktif ? 'true' : 'false' }}, toggle() { api('/sadakat/toggle', { id: {{ $k->id }} }).then(r => this.aktif = !!r.aktif); } }"
                     class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center justify-between">
                    <div>
                        <div class="font-semibold text-slate-800">{{ $k->ad }}</div>
                        <div class="text-xs text-slate-400">{{ $tipAd[$k->tip] ?? $k->tip }} · {{ $tipDeger($k) }}
                            @if ($k->min_tutar > 0) · min {{ number_format($k->min_tutar, 0, ',', '.') }} ₺ @endif
                            · {{ $k->kullanim }} kez kullanıldı</div>
                    </div>
                    <button @click="toggle()" :class="aktif ? 'bg-emerald-500' : 'bg-slate-300'" class="w-12 h-6 rounded-full relative transition shrink-0">
                        <span :class="aktif ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition"></span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">🏆 En Sadık Müşteriler</h2>
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
            @foreach ($topMusteri as $i => $m)
                <div class="flex items-center justify-between px-4 py-3">
                    <div><span class="text-slate-400 mr-2">{{ $i + 1 }}.</span><span class="font-medium text-slate-800">{{ $m->ad }}</span>
                        <div class="text-xs text-slate-400 ml-5">{{ $m->siparis_sayisi }} sipariş · {{ number_format((float) $m->toplam_harcama, 0, ',', '.') }} ₺</div></div>
                    <span class="bg-amber-100 text-amber-700 text-sm font-bold px-3 py-1 rounded-full">{{ number_format($m->puan, 0, ',', '.') }} puan</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

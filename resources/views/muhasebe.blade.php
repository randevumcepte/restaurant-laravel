@extends('layout.app')
@section('title', 'Muhasebe')
@section('baslik', '💰 ERP / Cari & Muhasebe')

@section('content')
@php $net = $gelir - $gider; @endphp
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-5"><div class="text-xs text-slate-400 uppercase font-semibold">Gelir (30 gün)</div><div class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($gelir, 0, ',', '.') }} ₺</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5"><div class="text-xs text-slate-400 uppercase font-semibold">Gider / Alış (30 gün)</div><div class="text-2xl font-bold text-rose-600 mt-1">{{ number_format($gider, 0, ',', '.') }} ₺</div></div>
    <div class="bg-white rounded-2xl border-2 {{ $net >= 0 ? 'border-emerald-300' : 'border-rose-300' }} p-5"><div class="text-xs text-slate-400 uppercase font-semibold">Brüt (Gelir − Alış)</div><div class="text-2xl font-bold {{ $net >= 0 ? 'text-emerald-600' : 'text-rose-600' }} mt-1">{{ number_format($net, 0, ',', '.') }} ₺</div></div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Kasa / Ödeme Tipi Dağılımı <span class="text-xs font-normal text-slate-400">(30 gün)</span></h2>
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
            @foreach ($kasa as $k)
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-slate-700 capitalize">{{ str_replace('_', ' ', $k->tip) }} <span class="text-xs text-slate-400">({{ $k->adet }} işlem)</span></span>
                    <span class="font-semibold text-slate-800">{{ number_format((float) $k->t, 0, ',', '.') }} ₺</span>
                </div>
            @endforeach
        </div>
    </div>
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Tedarikçi Cari (borç)</h2>
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
            @forelse ($tedarikciCari as $t)
                <div class="flex items-center justify-between px-4 py-3">
                    <div><span class="font-medium text-slate-800">{{ $t->ad ?? 'Tedarikçi' }}</span>
                        <span class="text-xs text-slate-400 ml-2">{{ $t->fatura }} fatura</span></div>
                    <span class="font-semibold text-rose-600">{{ number_format((float) $t->borc, 0, ',', '.') }} ₺</span>
                </div>
            @empty <p class="text-sm text-slate-400 p-6 text-center">Cari kayıt yok.</p> @endforelse
        </div>
    </div>
</div>
<p class="text-xs text-slate-400 mt-4">ℹ️ Tam muhasebe (cari mutabakat, mizan, e-defter) için Paraşüt/Logo/Mikro entegrasyonu eklenir. Buradaki rakamlar gerçek satış+alış defterinden anlık hesaplanır (tutarlı).</p>
@endsection

@extends('layout.app')
@section('title', (($ekran ?? 'mutfak') === 'bar') ? 'Bar Ekranı' : 'Mutfak Ekranı')
@section('baslik', (($ekran ?? 'mutfak') === 'bar') ? '🍹 Bar Ekranı (KDS)' : '👨‍🍳 Mutfak Ekranı (KDS)')

@push('head')<meta http-equiv="refresh" content="20">@endpush

@section('content')
@php $bar = ($ekran ?? 'mutfak') === 'bar'; @endphp
<p class="text-sm text-slate-500 mb-4">{{ $bar ? 'Bara gönderilen içecekler' : 'Mutfağa gönderilen yemekler' }} · <span class="text-slate-400">ekran 20 sn'de bir otomatik yenilenir</span></p>

@php
    $hazirlanan = $kalemler->whereIn('durum', ['gonderildi', 'hazirlaniyor'])->values();
    $bekleyen = $kalemler->where('durum', 'hazir')->values();
@endphp
@if ($hazirlanan->isEmpty() && $bekleyen->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="text-5xl mb-3">✅</div>
        <p class="text-slate-500">{{ $bar ? 'Bekleyen içecek yok. Bar temiz!' : 'Bekleyen sipariş yok. Mutfak temiz!' }}</p>
    </div>
@else
    @if ($hazirlanan->isNotEmpty())
        <h2 class="text-base font-bold text-slate-700 mb-3 flex items-center gap-2">🔥 Hazırlanıyor
            <span class="text-xs font-semibold bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">{{ $hazirlanan->count() }}</span></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-7">
            @foreach ($hazirlanan as $k)
                @php $gz = $k->gonderim_zamani ? \Illuminate\Support\Carbon::parse($k->gonderim_zamani) : now(); $dk = (int) round($gz->diffInMinutes(now())); @endphp
                <div x-data="{ hazir() { api('/mutfak/hazir', { kalem_id: {{ $k->id }} }).then(() => location.reload()); } }"
                     class="bg-white rounded-2xl border-2 {{ $dk >= 15 ? 'border-rose-300' : ($dk >= 8 ? 'border-amber-300' : 'border-slate-200') }} p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $k->kanal === 'paket' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }}">
                            {{ $k->kanal === 'paket' ? '🛵 PAKET' : ('🍽️ Masa ' . ($k->masa ?? '-')) }}</span>
                        <span class="text-xs font-semibold {{ $dk >= 15 ? 'text-rose-600' : ($dk >= 8 ? 'text-amber-600' : 'text-slate-400') }}">{{ $dk }} dk</span>
                    </div>
                    <div class="text-lg font-bold text-slate-800">{{ $k->adet }}× {{ $k->urun_adi }}</div>
                    @if ($k->not)<div class="text-sm text-rose-500 mt-1">📝 {{ $k->not }}</div>@endif
                    <button @click="hazir()" class="mt-3 w-full bg-emerald-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-emerald-700">✓ Hazır</button>
                </div>
            @endforeach
        </div>
    @endif

    @if ($bekleyen->isNotEmpty())
        <h2 class="text-base font-bold text-slate-700 mb-3 flex items-center gap-2">🔔 Hazır · Servis Bekliyor
            <span class="text-xs font-semibold bg-teal-100 text-teal-700 px-2 py-0.5 rounded-full">{{ $bekleyen->count() }}</span></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach ($bekleyen as $k)
                @php $hz = $k->hazir_zamani ? \Illuminate\Support\Carbon::parse($k->hazir_zamani) : now(); $bdk = (int) round($hz->diffInMinutes(now())); @endphp
                <div x-data="{ servis() { api('/mutfak/servis', { kalem_id: {{ $k->id }} }).then(() => location.reload()); } }"
                     class="bg-teal-50 rounded-2xl border-2 {{ $bdk >= 5 ? 'border-rose-300' : 'border-teal-300' }} p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold px-2 py-0.5 rounded {{ $k->kanal === 'paket' ? 'bg-purple-100 text-purple-700' : 'bg-indigo-100 text-indigo-700' }}">
                            {{ $k->kanal === 'paket' ? '🛵 PAKET' : ('🍽️ Masa ' . ($k->masa ?? '-')) }}</span>
                        <span class="text-xs font-semibold {{ $bdk >= 5 ? 'text-rose-600' : 'text-teal-600' }}">{{ $bdk }} dk bekliyor</span>
                    </div>
                    <div class="text-lg font-bold text-slate-800">{{ $k->adet }}× {{ $k->urun_adi }}</div>
                    <button @click="servis()" class="mt-3 w-full bg-teal-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-teal-700">✓ Servis Edildi</button>
                </div>
            @endforeach
        </div>
    @endif
@endif
@endsection

@extends('layout.app')
@section('title', 'Mutfak Ekranı')
@section('baslik', '👨‍🍳 Mutfak Ekranı (KDS)')

@push('head')<meta http-equiv="refresh" content="20">@endpush

@section('content')
<p class="text-sm text-slate-500 mb-4">Mutfağa gönderilen siparişler · <span class="text-slate-400">ekran 20 sn'de bir otomatik yenilenir</span></p>

@if ($kalemler->isEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="text-5xl mb-3">✅</div>
        <p class="text-slate-500">Bekleyen sipariş yok. Mutfak temiz!</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach ($kalemler as $k)
            @php $gecen = \Illuminate\Support\Carbon::parse($k->gonderim_zamani); $dk = (int) round($gecen->diffInMinutes(now())); @endphp
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
@endsection

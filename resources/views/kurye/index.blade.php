@extends('layout.app')
@section('title', 'Kurye Takip')
@section('baslik', '🗺️ Kurye Takip')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div id="harita" style="height: 520px; width: 100%;"></div>
    </div>
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Kuryeler ({{ $kuryeler->count() }})</h2>
        <div class="space-y-2 mb-6">
            @foreach ($kuryeler as $k)
                <div class="bg-white rounded-xl border border-slate-200 p-3 flex items-center justify-between">
                    <div><div class="font-semibold text-slate-800 text-sm">🛵 {{ $k->ad }}</div>
                        <div class="text-xs text-slate-400">{{ $k->telefon }}</div></div>
                    <span class="text-xs font-semibold px-2 py-1 rounded {{ $k->durum === 'teslimatta' ? 'bg-indigo-100 text-indigo-700' : ($k->durum === 'mola' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $k->durum }}</span>
                </div>
            @endforeach
        </div>
        <h2 class="font-semibold text-slate-700 mb-3">Yoldaki Teslimatlar ({{ $teslimatlar->count() }})</h2>
        <div class="space-y-2">
            @forelse ($teslimatlar as $t)
                <div class="bg-white rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between"><span class="font-semibold text-slate-800 text-sm">{{ $t->musteri ?? 'Müşteri' }}</span>
                        <span class="font-bold text-indigo-600 text-sm">{{ number_format((float) $t->toplam, 0, ',', '.') }} ₺</span></div>
                    <div class="text-xs text-slate-400 truncate">{{ $t->teslimat_adres }}</div>
                    <div class="text-xs text-indigo-500 mt-0.5">🛵 {{ $t->kurye ?? '-' }}</div>
                </div>
            @empty <p class="text-sm text-slate-400">Yolda teslimat yok.</p> @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    const kuryeler = @json($kuryeler);
    const map = L.map('harita').setView([40.988, 29.03], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
    // Restoran
    L.marker([40.988, 29.03]).addTo(map).bindPopup('🍔 Lezzet Duragi (Restoran)');
    // Kuryeler
    kuryeler.forEach(k => {
        if (k.son_lat && k.son_lng) {
            const icon = L.divIcon({ html: '<div style="background:#4f46e5;color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 6px rgba(0,0,0,.3)">🛵</div>', className: '', iconSize: [32, 32] });
            L.marker([parseFloat(k.son_lat), parseFloat(k.son_lng)], { icon }).addTo(map).bindPopup('<b>' + k.ad + '</b><br>' + k.durum);
        }
    });
</script>
@endpush
@endsection

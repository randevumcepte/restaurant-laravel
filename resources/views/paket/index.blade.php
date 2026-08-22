@extends('layout.app')
@section('title', 'Paket Siparişler')
@section('baslik', '🛵 Paket Sipariş Merkezi')

@section('content')
@php
    $pRenk = [
        'getir' => 'bg-purple-100 text-purple-700', 'yemeksepeti' => 'bg-rose-100 text-rose-700',
        'trendyol' => 'bg-orange-100 text-orange-700', 'migros' => 'bg-green-100 text-green-700',
        'gofody' => 'bg-cyan-100 text-cyan-700', 'telefon' => 'bg-slate-200 text-slate-700',
        'whatsapp' => 'bg-emerald-100 text-emerald-700',
    ];
    $dRenk = ['hazirlaniyor' => 'text-amber-600', 'hazir' => 'text-sky-600', 'yolda' => 'text-indigo-600', 'teslim' => 'text-emerald-600'];
    $sonraki = ['hazirlaniyor' => 'hazir', 'hazir' => 'yolda', 'yolda' => 'teslim'];
    $sonrakiEtiket = ['hazirlaniyor' => 'Hazır', 'hazir' => 'Kuryeye Ver', 'yolda' => 'Teslim Edildi'];
@endphp

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Aktif Sipariş</div><div class="text-2xl font-bold text-indigo-600 mt-1">{{ $aktif->count() }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Paket</div><div class="text-2xl font-bold text-slate-700 mt-1">{{ $bugunPaket }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Yolda</div><div class="text-2xl font-bold text-amber-600 mt-1">{{ $aktif->where('teslimat_durumu', 'yolda')->count() }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Platform</div><div class="text-2xl font-bold text-emerald-600 mt-1">{{ $platformDagilim->count() }}</div></div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <h2 class="font-semibold text-slate-700 mb-3">Aktif Siparişler (tüm platformlar tek ekranda)</h2>
        <div class="space-y-3">
            @forelse ($aktif as $s)
                <div x-data="{ ilerle() { api('/paket/durum', { adisyon_id: {{ $s->id }}, durum: '{{ $sonraki[$s->teslimat_durumu] ?? 'teslim' }}' }).then(() => location.reload()); } }"
                     class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold px-2 py-0.5 rounded {{ $pRenk[$s->platform] ?? 'bg-slate-100 text-slate-600' }}">{{ strtoupper($s->platform ?? 'SALON') }}</span>
                            <span class="text-xs text-slate-400">#{{ $s->platform_siparis_no }}</span>
                            <span class="text-xs font-semibold {{ $dRenk[$s->teslimat_durumu] ?? '' }}">● {{ $s->teslimat_durumu }}</span>
                        </div>
                        <div class="font-semibold text-slate-800">{{ $s->musteri_ad ?? 'Müşteri' }} · <span class="text-slate-500 text-sm">{{ $s->telefon }}</span></div>
                        <div class="text-xs text-slate-400 truncate">{{ $s->teslimat_adres }}</div>
                        @if ($s->kurye_ad)<div class="text-xs text-indigo-500 mt-0.5">🛵 {{ $s->kurye_ad }}</div>@endif
                    </div>
                    <div class="text-right shrink-0 ml-3">
                        <div class="font-bold text-slate-900">{{ number_format((float) $s->toplam, 0, ',', '.') }} ₺</div>
                        @if (isset($sonraki[$s->teslimat_durumu]))
                            <button @click="ilerle()" class="mt-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg px-3 py-1.5 hover:bg-indigo-700">{{ $sonrakiEtiket[$s->teslimat_durumu] }} →</button>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-400 bg-white rounded-2xl border border-slate-200 p-6 text-center">Şu an aktif paket sipariş yok.</p>
            @endforelse
        </div>
    </div>

    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Platform Dağılımı <span class="text-xs font-normal text-slate-400">(30 gün)</span></h2>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3">
            @foreach ($platformDagilim as $p)
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold px-2 py-0.5 rounded {{ $pRenk[$p->platform] ?? 'bg-slate-100 text-slate-600' }}">{{ strtoupper($p->platform ?? '-') }}</span>
                    <span class="text-sm text-slate-600">{{ $p->adet }} sipariş · <b class="text-emerald-600">{{ number_format((float) $p->ciro, 0, ',', '.') }} ₺</b></span>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 mt-3 px-1">ℹ️ Gerçek Getir/Yemeksepeti/Trendyol bağlantısı için resmî API anahtarları gerekir. Mimari hazır; anahtar gelince otomatik akış aktif olur.</p>
    </div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Entegrasyonlar')
@section('baslik', '🔌 Paket Servis Entegrasyonları')

@section('content')
@php
    $platformlar = [
        'yemeksepeti' => ['Yemeksepeti', 'bg-rose-500', 'Delivery Hero altyapısı'],
        'trendyol' => ['Trendyol GO', 'bg-orange-500', 'Uber Eats TR ile birlikte'],
        'getir' => ['Getir Yemek', 'bg-purple-500', '⚠️ Uber Eats\'e taşındı'],
        'migros' => ['Migros Yemek', 'bg-green-600', ''],
        'ubereats' => ['Uber Eats', 'bg-slate-800', 'Getir + Trendyol GO'],
    ];
@endphp

{{-- Webhook URL --}}
<div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 mb-6" x-data="{ kopyala() { navigator.clipboard.writeText('{{ $webhookUrl }}'); this.k = true; setTimeout(() => this.k = false, 1500); }, k: false }">
    <div class="flex items-center justify-between gap-4">
        <div class="min-w-0">
            <h2 class="font-semibold text-slate-800 mb-1">📨 Webhook Adresin</h2>
            <p class="text-sm text-slate-500 mb-2">Bu adresi middleware'ine (Posentegra vb.) ver — gelen siparişler <b>anında Paket Merkezi'ne</b> düşer.</p>
            <code class="text-xs bg-white border border-slate-200 rounded-lg px-3 py-1.5 block truncate text-indigo-700">{{ $webhookUrl }}</code>
        </div>
        <button @click="kopyala()" class="shrink-0 bg-indigo-600 text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-indigo-700" x-text="k ? '✓ Kopyalandı' : 'Kopyala'"></button>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-4">
    @foreach ($platformlar as $key => [$ad, $renk, $not])
        @php $e = $entegrasyonlar[$key] ?? null; @endphp
        <div x-data="{
                aktif: {{ $e && $e->aktif ? 'true' : 'false' }}, oto: {{ $e && $e->otomatik_onay ? 'true' : 'false' }},
                magaza: '{{ $e->magaza_id ?? '' }}', apikey: '{{ $e->api_key ?? '' }}', test: '',
                kaydet() { api('/entegrasyon/kaydet', { platform: '{{ $key }}', aktif: this.aktif ? 1 : 0, otomatik_onay: this.oto ? 1 : 0, magaza_id: this.magaza, api_key: this.apikey }).then(() => { this.msg = 'Kaydedildi ✓'; setTimeout(() => this.msg = '', 1500); }); },
                testSiparis() { this.test = '...'; api('/entegrasyon/test', { platform: '{{ $key }}' }).then(r => { this.test = 'Sipariş #' + r.adisyon_id + ' oluşturuldu → Paket Merkezi'; }); },
                msg: '' }"
             class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ $renk }} text-white flex items-center justify-center font-bold">{{ mb_substr($ad, 0, 1) }}</div>
                    <div><div class="font-bold text-slate-800">{{ $ad }}</div>
                        @if ($not)<div class="text-[11px] text-slate-400">{{ $not }}</div>@endif</div>
                </div>
                <button @click="aktif = !aktif; kaydet()" :class="aktif ? 'bg-emerald-500' : 'bg-slate-300'" class="w-12 h-6 rounded-full relative transition">
                    <span :class="aktif ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition"></span>
                </button>
            </div>
            <div x-show="aktif" class="space-y-2">
                <input x-model="magaza" @change="kaydet()" placeholder="Mağaza ID" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <input x-model="apikey" @change="kaydet()" placeholder="API Anahtarı (opsiyonel)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" x-model="oto" @change="kaydet()"> Otomatik onay</label>
                <div class="flex items-center gap-2 pt-1">
                    <button @click="testSiparis()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg px-3 py-2">🧪 Test Siparişi Gönder</button>
                    <span class="text-xs text-emerald-600" x-text="test"></span>
                </div>
            </div>
            <div class="text-xs text-emerald-600 mt-2" x-text="msg"></div>
        </div>
    @endforeach
</div>

<p class="text-xs text-slate-400 mt-5">ℹ️ Gerçek bağlantı için: ya her platformun partner programına başvur (Yemeksepeti Delivery Hero / Trendyol GO), ya da tek noktadan <b>Posentegra</b> gibi bir middleware'e abone ol ve yukarıdaki webhook adresini ver. "Test Siparişi" gerçek webhook akışının aynısını çalıştırır.</p>
@endsection

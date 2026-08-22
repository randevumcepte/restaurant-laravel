<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sube->ad }} — Menü</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50" x-data="{ sepet: [], acik: false,
    ekle(ad, fiyat) { const v = this.sepet.find(s => s.ad === ad); if (v) v.adet++; else this.sepet.push({ ad, fiyat, adet: 1 }); },
    get toplam() { return this.sepet.reduce((t, s) => t + s.fiyat * s.adet, 0); },
    get adet() { return this.sepet.reduce((t, s) => t + s.adet, 0); } }">

<div class="max-w-md mx-auto pb-28">
    <div class="bg-gradient-to-br from-indigo-600 to-violet-600 text-white px-5 py-6 rounded-b-3xl">
        <div class="text-2xl font-bold">{{ $sube->ad }}</div>
        <div class="text-indigo-100 text-sm">{{ $masaAd ? 'Masa ' . $masaAd : 'Dijital Menü' }} · QR Sipariş</div>
    </div>

    @foreach ($kategoriler as $k)
        <div class="px-4 mt-5">
            <h2 class="font-bold text-slate-800 mb-2">{{ $k->ad }}</h2>
            <div class="space-y-2">
                @foreach ($urunler[$k->id] ?? [] as $u)
                    <div class="bg-white rounded-2xl border border-slate-200 p-3 flex items-center justify-between {{ $u->tukendi ? 'opacity-50' : '' }}">
                        <div><div class="font-semibold text-slate-800 text-sm">{{ $u->ad }}</div>
                            <div class="text-indigo-600 font-bold text-sm">{{ number_format((float) $u->fiyat, 0, ',', '.') }} ₺</div>
                            @if ($u->tukendi)<div class="text-[10px] text-rose-500 font-semibold">TÜKENDİ</div>@endif</div>
                        @if (!$u->tukendi)
                            <button @click="ekle('{{ addslashes($u->ad) }}', {{ (float) $u->fiyat }})"
                                class="bg-indigo-600 text-white w-9 h-9 rounded-full text-xl font-bold leading-none hover:bg-indigo-700">+</button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

{{-- Sepet bar --}}
<div x-show="adet > 0" x-cloak class="fixed bottom-0 left-0 right-0 max-w-md mx-auto p-3">
    <button @click="acik = true" class="w-full bg-indigo-600 text-white rounded-2xl py-4 px-5 flex items-center justify-between shadow-lg">
        <span class="font-semibold" x-text="adet + ' ürün'"></span>
        <span class="font-bold">Sepeti Gör · <span x-text="new Intl.NumberFormat('tr-TR').format(toplam) + ' ₺'"></span></span>
    </button>
</div>

{{-- Sepet modal --}}
<div x-show="acik" x-cloak class="fixed inset-0 bg-black/40 flex items-end z-50" @click.self="acik = false">
    <div class="bg-white rounded-t-3xl w-full max-w-md mx-auto p-5 max-h-[80vh] overflow-y-auto">
        <div class="w-10 h-1 bg-slate-200 rounded-full mx-auto mb-4"></div>
        <h3 class="font-bold text-slate-800 mb-3">Sepetim</h3>
        <template x-for="s in sepet" :key="s.ad">
            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                <span class="text-sm text-slate-700" x-text="s.adet + '× ' + s.ad"></span>
                <span class="font-semibold text-sm" x-text="new Intl.NumberFormat('tr-TR').format(s.fiyat * s.adet) + ' ₺'"></span>
            </div>
        </template>
        <div class="flex justify-between font-bold text-lg mt-4"><span>Toplam</span><span x-text="new Intl.NumberFormat('tr-TR').format(toplam) + ' ₺'"></span></div>
        <button @click="alert('Sipariş garsonunuza iletildi! (demo)'); acik=false; sepet=[]"
            class="w-full bg-emerald-600 text-white font-semibold rounded-2xl py-3.5 mt-4 hover:bg-emerald-700">Siparişi Onayla</button>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
</body>
</html>

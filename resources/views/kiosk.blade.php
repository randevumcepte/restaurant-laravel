<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sube->ad }} — Self Servis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-slate-100 h-screen overflow-hidden" x-data="{
    kategori: {{ $kategoriler->first()->id ?? 0 }}, sepet: [],
    ekle(ad, fiyat) { const v = this.sepet.find(s => s.ad === ad); if (v) v.adet++; else this.sepet.push({ ad, fiyat, adet: 1 }); },
    azalt(ad) { const v = this.sepet.find(s => s.ad === ad); if (v) { v.adet--; if (v.adet <= 0) this.sepet = this.sepet.filter(s => s.ad !== ad); } },
    get toplam() { return this.sepet.reduce((t, s) => t + s.fiyat * s.adet, 0); },
    get adet() { return this.sepet.reduce((t, s) => t + s.adet, 0); },
    tamamla() { if (!this.adet) return; alert('Siparişiniz alındı! Numaranız: ' + Math.floor(Math.random()*900+100) + '\nMutfağa iletildi. Afiyet olsun! 🍽️'); this.sepet = []; } }">

<div class="flex h-screen">
    {{-- Kategori kolonu --}}
    <div class="w-52 bg-slate-900 text-white overflow-y-auto shrink-0">
        <div class="p-5 border-b border-slate-700">
            <div class="text-xl font-bold">{{ $sube->ad }}</div>
            <div class="text-xs text-slate-400">Self Servis Kiosk</div>
        </div>
        @foreach ($kategoriler as $k)
            <button @click="kategori = {{ $k->id }}" :class="kategori === {{ $k->id }} ? 'bg-indigo-600' : ''"
                class="w-full text-left px-5 py-4 text-lg font-medium hover:bg-slate-800 transition">{{ $k->ad }}</button>
        @endforeach
    </div>

    {{-- Urunler --}}
    <div class="flex-1 overflow-y-auto p-6">
        @foreach ($kategoriler as $k)
            <div x-show="kategori === {{ $k->id }}" class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($urunler[$k->id] ?? [] as $u)
                    <button @click="ekle('{{ addslashes($u->ad) }}', {{ (float) $u->fiyat }})" {{ $u->tukendi ? 'disabled' : '' }}
                        class="bg-white rounded-3xl p-5 text-left shadow-sm hover:shadow-lg hover:ring-2 hover:ring-indigo-400 transition {{ $u->tukendi ? 'opacity-40' : '' }}">
                        <div class="text-lg font-bold text-slate-800">{{ $u->ad }}</div>
                        <div class="text-2xl font-bold text-indigo-600 mt-2">{{ number_format((float) $u->fiyat, 0, ',', '.') }} ₺</div>
                        @if ($u->tukendi)<div class="text-sm text-rose-500 font-semibold mt-1">Tükendi</div>@endif
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Sepet --}}
    <div class="w-96 bg-white border-l border-slate-200 flex flex-col shrink-0">
        <div class="p-5 border-b border-slate-100"><h2 class="text-xl font-bold text-slate-800">🛒 Sepetim</h2></div>
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <template x-for="s in sepet" :key="s.ad">
                <div class="flex items-center justify-between bg-slate-50 rounded-2xl p-3">
                    <div class="min-w-0"><div class="font-semibold text-slate-800 text-sm truncate" x-text="s.ad"></div>
                        <div class="text-indigo-600 font-bold text-sm" x-text="new Intl.NumberFormat('tr-TR').format(s.fiyat * s.adet) + ' ₺'"></div></div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="azalt(s.ad)" class="w-8 h-8 rounded-full bg-slate-200 text-lg font-bold">−</button>
                        <span class="w-6 text-center font-bold" x-text="s.adet"></span>
                        <button @click="ekle(s.ad, s.fiyat)" class="w-8 h-8 rounded-full bg-indigo-600 text-white text-lg font-bold">+</button>
                    </div>
                </div>
            </template>
            <p x-show="!adet" class="text-center text-slate-400 py-16">Ürün seçmek için soldan dokunun</p>
        </div>
        <div class="p-5 border-t border-slate-100">
            <div class="flex justify-between text-2xl font-bold text-slate-900 mb-4"><span>Toplam</span><span x-text="new Intl.NumberFormat('tr-TR').format(toplam) + ' ₺'"></span></div>
            <button @click="tamamla()" :disabled="!adet" :class="adet ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-300'"
                class="w-full text-white text-xl font-bold rounded-2xl py-5 transition">Siparişi Tamamla</button>
        </div>
    </div>
</div>
</body>
</html>

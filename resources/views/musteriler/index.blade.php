@extends('layout.app')
@section('title', 'Müşteriler')
@section('baslik', '👥 Müşteriler / Sadakat')

@section('content')
<div x-data="{ ekle: false, duzenle: false, f: { id: null, ad: '', telefon: '', adres: '' },
        kaydetYeni() { api('/musteriler/ekle', this.f).then(() => location.reload()); },
        kaydetDuzenle() { api('/musteriler/guncelle', this.f).then(() => location.reload()); },
        acDuzenle(m) { this.f = { id: m.id, ad: m.ad, telefon: m.telefon, adres: m.adres }; this.duzenle = true; } }">

    <div class="flex items-center justify-between mb-4">
        <p class="text-sm text-slate-500">Toplam {{ $toplam }} müşteri · en çok harcayanlar</p>
        <button @click="f = { id: null, ad: '', telefon: '', adres: '' }; ekle = true" class="bg-indigo-600 text-white text-sm font-semibold rounded-xl px-4 py-2 hover:bg-indigo-700">+ Müşteri Ekle</button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr><th class="text-left px-4 py-2">Müşteri</th><th class="text-left px-4 py-2">Telefon</th>
                    <th class="text-left px-4 py-2">Adres</th><th class="text-right px-4 py-2">Sipariş</th>
                    <th class="text-right px-4 py-2">Harcama</th><th class="text-right px-4 py-2">Puan</th><th class="px-4 py-2"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($musteriler as $m)
                    <tr>
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $m->ad }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $m->telefon }}</td>
                        <td class="px-4 py-2 text-slate-400 text-xs truncate max-w-[200px]">{{ $m->adres }}</td>
                        <td class="px-4 py-2 text-right text-slate-600">{{ $m->siparis_sayisi }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-emerald-600">{{ number_format((float) $m->toplam_harcama, 0, ',', '.') }} ₺</td>
                        <td class="px-4 py-2 text-right"><span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded">{{ $m->puan }}</span></td>
                        <td class="px-4 py-2 text-right">
                            <button @click='acDuzenle(@json($m))' class="text-xs text-indigo-600 hover:underline">Düzenle</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Ekle modal --}}
    <div x-show="ekle" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="ekle = false">
        <div class="bg-white rounded-2xl p-6 w-96">
            <h3 class="font-bold text-slate-800 mb-4">Yeni Müşteri</h3>
            <input x-model="f.ad" placeholder="Ad Soyad" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
            <input x-model="f.telefon" placeholder="Telefon" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
            <input x-model="f.adres" placeholder="Adres" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-4 text-sm">
            <div class="flex gap-2">
                <button @click="ekle = false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">İptal</button>
                <button @click="kaydetYeni()" class="flex-1 bg-indigo-600 text-white rounded-xl py-2.5 text-sm font-semibold">Kaydet</button>
            </div>
        </div>
    </div>

    {{-- Duzenle modal --}}
    <div x-show="duzenle" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="duzenle = false">
        <div class="bg-white rounded-2xl p-6 w-96">
            <h3 class="font-bold text-slate-800 mb-4">Müşteri Düzenle</h3>
            <input x-model="f.ad" placeholder="Ad Soyad" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
            <input x-model="f.telefon" placeholder="Telefon" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
            <input x-model="f.adres" placeholder="Adres" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-4 text-sm">
            <div class="flex gap-2">
                <button @click="duzenle = false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">İptal</button>
                <button @click="kaydetDuzenle()" class="flex-1 bg-indigo-600 text-white rounded-xl py-2.5 text-sm font-semibold">Güncelle</button>
            </div>
        </div>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection

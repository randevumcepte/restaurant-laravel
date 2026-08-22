@extends('layout.app')
@section('title', 'Menü')
@section('baslik', '📋 Menü Yönetimi')

@section('content')
<p class="text-sm text-slate-500 mb-5">{{ $kategoriler->count() }} kategori · {{ $urunler->flatten()->count() }} ürün · "86" ile ürünü anlık tükendi işaretleyin</p>

<div class="space-y-6">
    @foreach ($kategoriler as $k)
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h2 class="font-semibold text-slate-800 mb-3">{{ $k->ad }} <span class="text-xs font-normal text-slate-400">({{ count($urunler[$k->id] ?? []) }})</span></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach ($urunler[$k->id] ?? [] as $u)
                    <div x-data="{ tukendi: {{ $u->tukendi ? 'true' : 'false' }},
                        toggle() { api('/menu/86', { urun_id: {{ $u->id }} }).then(r => this.tukendi = !!r.tukendi); } }"
                         class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                        <div :class="tukendi ? 'opacity-40 line-through' : ''">
                            <span class="font-medium text-slate-800 text-sm">{{ $u->ad }}</span>
                            <span class="text-indigo-600 font-bold text-sm ml-2">{{ number_format((float) $u->fiyat, 0, ',', '.') }} ₺</span>
                        </div>
                        <button @click="toggle()" :class="tukendi ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'"
                            class="text-xs font-semibold px-2.5 py-1 rounded-lg" x-text="tukendi ? 'Tükendi' : 'Mevcut'"></button>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection

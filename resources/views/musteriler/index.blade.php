@extends('layout.app')
@section('title', 'Müşteriler')
@section('baslik', '👥 Müşteriler / Sadakat')

@section('content')
<p class="text-sm text-slate-500 mb-4">Toplam {{ $toplam }} müşteri · en çok harcayanlar</p>
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr><th class="text-left px-4 py-2">Müşteri</th><th class="text-left px-4 py-2">Telefon</th>
                <th class="text-left px-4 py-2">Adres</th><th class="text-right px-4 py-2">Sipariş</th>
                <th class="text-right px-4 py-2">Harcama</th><th class="text-right px-4 py-2">Puan</th></tr>
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
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

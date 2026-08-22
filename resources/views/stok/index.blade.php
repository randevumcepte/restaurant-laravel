@extends('layout.app')
@section('title', 'Stok & Reçete')
@section('baslik', '📦 Stok & Reçete')

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <h2 class="font-semibold text-slate-700 mb-3">Malzeme Stokları <span class="text-xs font-normal text-slate-400">(defterden türetilen anlık stok)</span></h2>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr><th class="text-left px-4 py-2">Malzeme</th><th class="text-left px-4 py-2">Kategori</th>
                        <th class="text-right px-4 py-2">Stok</th><th class="text-right px-4 py-2">Kritik</th><th class="text-right px-4 py-2">Maliyet</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($malzemeler as $m)
                        @php $dusuk = $m->stok < $m->kritik_stok; $b = $birimler[$m->temel_birim_id] ?? ''; @endphp
                        <tr class="{{ $dusuk ? 'bg-rose-50' : '' }}">
                            <td class="px-4 py-2 font-medium text-slate-800">{{ $m->ad }}</td>
                            <td class="px-4 py-2 text-slate-400 text-xs">{{ $m->kategori }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $dusuk ? 'text-rose-600' : 'text-slate-700' }}">{{ number_format((float) $m->stok, 0, ',', '.') }} {{ $b }}</td>
                            <td class="px-4 py-2 text-right text-slate-400">{{ number_format((float) $m->kritik_stok, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right text-slate-500">{{ number_format((float) $m->guncel_maliyet, 2, ',', '.') }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Reçeteler ({{ $receteler->count() }})</h2>
        <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2">
            @foreach ($receteler as $r)
                <div class="flex items-center justify-between py-1.5">
                    <div><span class="font-medium text-slate-800 text-sm">{{ $r->ad }}</span>
                        @if ($r->tip === 'yari_mamul')<span class="text-[10px] bg-violet-100 text-violet-700 px-1.5 py-0.5 rounded ml-1">yarı mamul</span>@endif</div>
                    <span class="text-xs text-slate-400">{{ $r->kalem_sayisi }} malzeme</span>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-slate-400 mt-3 px-1">ℹ️ Satış yapıldıkça reçeteden otomatik stok düşümü (M3'te tam devrede). Yarı mamul (Domates Sos) üretilip stoğa girer, sonra pizza/makarnada kullanılır.</p>
    </div>
</div>
@endsection

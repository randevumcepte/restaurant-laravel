@extends('layout.app')
@section('title', 'Raporlar')
@section('baslik', '📈 Raporlar & Menü Mühendisliği')

@section('content')
@php
    $ortAdet = $menuMuh->avg('adet') ?: 1;
    $ortCiro = $menuMuh->avg('ciro') ?: 1;
    $sinif = function ($x) use ($ortAdet, $ortCiro) {
        $pop = $x->adet >= $ortAdet; $kar = $x->ciro >= $ortCiro;
        if ($pop && $kar) return ['Yıldız', 'bg-emerald-100 text-emerald-700'];
        if ($pop && !$kar) return ['İş Atı', 'bg-sky-100 text-sky-700'];
        if (!$pop && $kar) return ['Bilmece', 'bg-amber-100 text-amber-700'];
        return ['Köpek', 'bg-rose-100 text-rose-700'];
    };
    $maxSaat = max(1, count($saatlik) ? max($saatlik) : 1);
@endphp

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <h2 class="font-semibold text-slate-700 mb-3">Menü Mühendisliği <span class="text-xs font-normal text-slate-400">(popülerlik × ciro, 30 gün)</span></h2>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase"><tr>
                    <th class="text-left px-4 py-2">Ürün</th><th class="text-right px-4 py-2">Adet</th>
                    <th class="text-right px-4 py-2">Ciro</th><th class="text-center px-4 py-2">Sınıf</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($menuMuh as $m)
                        @php [$et, $renk] = $sinif($m); @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium text-slate-800">{{ $m->urun_adi }}</td>
                            <td class="px-4 py-2 text-right text-slate-600">{{ (int) $m->adet }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-slate-700">{{ number_format((float) $m->ciro, 0, ',', '.') }} ₺</td>
                            <td class="px-4 py-2 text-center"><span class="text-xs font-semibold px-2 py-0.5 rounded {{ $renk }}">{{ $et }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-slate-400 mt-2">⭐ Yıldız: çok satan+kârlı · 🐎 İş Atı: çok satan az kârlı · ❓ Bilmece: az satan kârlı · 🐕 Köpek: ikisi de düşük (menüden çıkar)</p>
    </div>

    <div class="space-y-6">
        <div>
            <h2 class="font-semibold text-slate-700 mb-3">Ödeme Tipleri <span class="text-xs font-normal text-slate-400">(30 gün)</span></h2>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 space-y-2">
                @foreach ($odemeTipi as $o)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600 capitalize">{{ str_replace('_', ' ', $o->tip) }}</span>
                        <span class="text-sm font-semibold text-slate-800">{{ number_format((float) $o->tutar, 0, ',', '.') }} ₺ <span class="text-slate-400 text-xs">({{ $o->adet }})</span></span>
                    </div>
                @endforeach
            </div>
        </div>
        <div>
            <h2 class="font-semibold text-slate-700 mb-3">Saatlik Yoğunluk</h2>
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                <div class="flex items-end gap-1 h-32">
                    @for ($h = 9; $h <= 23; $h++)
                        @php $v = $saatlik[$h] ?? 0; @endphp
                        <div class="flex-1 flex flex-col items-center justify-end h-full">
                            <div class="w-full rounded-t bg-indigo-400" style="height: {{ max(2, (int) ($v / $maxSaat * 100)) }}%" title="{{ $h }}:00 → {{ $v }}"></div>
                            <div class="text-[9px] text-slate-400 mt-0.5">{{ $h }}</div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

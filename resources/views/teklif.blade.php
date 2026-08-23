@extends('layout.app')
@section('title', 'Teklifler')
@section('baslik', '📋 Tedarikçi Teklif Karşılaştırma')

@section('content')
<p class="text-sm text-slate-500 mb-5">Her malzeme için tedarikçi tekliflerini karşılaştır — <b class="text-emerald-600">en ucuz</b> otomatik işaretlenir.</p>

<div class="grid md:grid-cols-2 gap-4">
    @foreach ($teklifler as $malzeme => $liste)
        @php $enUcuz = $liste->first(); @endphp
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <h2 class="font-semibold text-slate-800 mb-3">{{ $malzeme }}</h2>
            <div class="space-y-2">
                @foreach ($liste as $t)
                    @php $ucuz = $t->birim_fiyat == $enUcuz->birim_fiyat; @endphp
                    <div class="flex items-center justify-between py-2 px-3 rounded-xl {{ $ucuz ? 'bg-emerald-50 border border-emerald-200' : 'bg-slate-50' }}">
                        <div>
                            <span class="font-medium text-slate-800 text-sm">{{ $t->tedarikci }}</span>
                            @if ($ucuz)<span class="text-[10px] bg-emerald-500 text-white font-bold px-1.5 py-0.5 rounded ml-1">EN UCUZ</span>@endif
                            <div class="text-[11px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($t->tarih)->format('d.m.Y') }}</div>
                        </div>
                        <span class="font-bold text-sm {{ $ucuz ? 'text-emerald-600' : 'text-slate-600' }}">{{ number_format((float) $t->birim_fiyat, 2, ',', '.') }} ₺<span class="text-xs text-slate-400 font-normal">/{{ $t->birim }}</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
@endsection

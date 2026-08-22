@extends('layout.app')
@section('title', 'Satın Alma')
@section('baslik', '🧾 Satın Alma & Fiyat Zekâsı')

@section('content')
<div class="grid lg:grid-cols-2 gap-6">
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">📈 Fiyat Uyarıları <span class="text-xs font-normal text-slate-400">(son alışa göre artış)</span></h2>
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
            @forelse ($uyarilar as $u)
                <div class="flex items-center justify-between px-4 py-3 {{ $u->uyari === 'kirmizi' ? 'bg-rose-50' : 'bg-amber-50/50' }}">
                    <div>
                        <span class="font-medium text-slate-800">{{ $u->ad }}</span>
                        <div class="text-xs text-slate-400">{{ number_format((float) $u->onceki_fiyat, 2, ',', '.') }} → {{ number_format((float) $u->birim_fiyat, 2, ',', '.') }} ₺ · {{ \Illuminate\Support\Carbon::parse($u->tarih)->format('d.m.Y') }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-sm font-bold {{ $u->uyari === 'kirmizi' ? 'text-rose-600' : 'text-amber-600' }}">%{{ number_format((float) $u->fiyat_farki_yuzde, 1, ',', '.') }}</span>
                        <div class="text-xs text-slate-400">+{{ number_format((float) $u->fiyat_farki_tutar, 0, ',', '.') }} ₺</div>
                    </div>
                </div>
            @empty <p class="text-sm text-slate-400 p-6 text-center">Fiyat uyarısı yok.</p> @endforelse
        </div>
        <p class="text-xs text-slate-400 mt-3 px-1">🟢 ±%5 · 🟡 %5-15 · 🔴 %15+ (patron onayı gerektirir)</p>
    </div>
    <div>
        <h2 class="font-semibold text-slate-700 mb-3">Son Alış Faturaları</h2>
        <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
            @foreach ($faturalar as $f)
                <div class="flex items-center justify-between px-4 py-3">
                    <div><span class="font-medium text-slate-800 text-sm">{{ $f->tedarikci ?? 'Tedarikçi' }}</span>
                        <div class="text-xs text-slate-400">{{ $f->fatura_no }} · {{ \Illuminate\Support\Carbon::parse($f->tarih)->format('d.m.Y') }}</div></div>
                    <span class="font-bold text-slate-700 text-sm">{{ number_format((float) $f->toplam, 0, ',', '.') }} ₺</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

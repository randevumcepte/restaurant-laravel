@extends('layout.app')
@section('title', 'Çağrı Merkezi')
@section('baslik', '📞 Çağrı Merkezi (CallerID)')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Çağrı</div><div class="text-2xl font-bold text-indigo-600 mt-1">{{ $bugun }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Kaçan Çağrı</div><div class="text-2xl font-bold text-rose-600 mt-1">{{ $kacan }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">AI Santral</div><div class="text-sm font-bold text-emerald-600 mt-2">Hazır (anahtar bekliyor)</div></div>
</div>

<p class="text-sm text-slate-500 mb-3">Telefon çaldığında müşteri geçmişi otomatik ekrana gelir (CallerID). Gelen çağrılar:</p>
<div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-100">
    @foreach ($cagrilar as $c)
        <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <span class="text-lg">{{ $c->sonuc === 'kacan' ? '📵' : ($c->sonuc === 'siparis' ? '🛒' : '📞') }}</span>
                <div>
                    <div class="font-medium text-slate-800">{{ $c->musteri ?? 'Bilinmeyen numara' }} <span class="text-slate-400 text-sm font-normal">{{ $c->telefon }}</span></div>
                    @if ($c->musteri)
                        <div class="text-xs text-slate-400">{{ $c->siparis_sayisi }} sipariş · {{ number_format((float) $c->toplam_harcama, 0, ',', '.') }} ₺ · {{ Str::limit($c->adres, 40) }}</div>
                    @else
                        <div class="text-xs text-slate-400">Yeni / kayıtsız müşteri</div>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold px-2 py-1 rounded {{ $c->sonuc === 'kacan' ? 'bg-rose-100 text-rose-700' : ($c->sonuc === 'siparis' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600') }}">{{ $c->sonuc }}</span>
                <div class="text-[11px] text-slate-400 mt-1">{{ \Illuminate\Support\Carbon::parse($c->created_at)->diffForHumans() }}</div>
            </div>
        </div>
    @endforeach
</div>
@endsection

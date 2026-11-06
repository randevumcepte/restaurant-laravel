@extends('layout.app')
@section('title', 'Çağrı Merkezi')
@section('baslik', 'Çağrı Merkezi (Arayan Tanıma)')
@section('rk_ikon', 'contact_phone')
@section('rk_alt', 'Telefon çaldığında müşteri geçmişi otomatik ekrana gelir')

@section('content')
@php
    $_sonuclar = ['kacan' => ['Kaçan', '#E11D48'], 'siparis' => ['Sipariş', '#059669'], 'cevaplandi' => ['Cevaplandı', '#64748B']];
    $_kart = 'background:var(--card);border:1px solid var(--line);border-radius:16px';
@endphp
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="p-4" style="{{ $_kart }}"><div class="text-xs uppercase font-semibold" style="color:var(--sub)">Bugün Çağrı</div><div class="text-2xl font-bold text-indigo-500 mt-1">{{ $bugun }}</div></div>
    <div class="p-4" style="{{ $_kart }}"><div class="text-xs uppercase font-semibold" style="color:var(--sub)">Kaçan Çağrı</div><div class="text-2xl font-bold text-rose-500 mt-1">{{ $kacan }}</div></div>
</div>

<p class="text-sm mb-3" style="color:var(--sub)">Gelen çağrılar:</p>
<div style="{{ $_kart }};overflow:hidden">
    @forelse ($cagrilar as $c)
        @php [$_etiket, $_renk] = $_sonuclar[$c->sonuc] ?? [$c->sonuc, '#64748B']; @endphp
        <div class="flex items-center justify-between px-4 py-3" style="border-bottom:1px solid var(--line)">
            <div class="flex items-center gap-3">
                <span class="text-lg">{{ $c->sonuc === 'kacan' ? '📵' : ($c->sonuc === 'siparis' ? '🛒' : '📞') }}</span>
                <div>
                    <div class="font-medium" style="color:var(--ink)">{{ $c->musteri ?? 'Bilinmeyen numara' }} <span class="text-sm font-normal" style="color:var(--sub)">{{ $c->telefon }}</span></div>
                    @if ($c->musteri)
                        <div class="text-xs" style="color:var(--sub)">{{ $c->siparis_sayisi }} sipariş · {{ number_format((float) $c->toplam_harcama, 0, ',', '.') }} ₺ · {{ Str::limit($c->adres, 40) }}</div>
                    @else
                        <div class="text-xs" style="color:var(--sub)">Yeni / kayıtsız müşteri</div>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs font-semibold px-2 py-1 rounded" style="color:{{ $_renk }};background:color-mix(in srgb,{{ $_renk }} 14%,transparent)">{{ $_etiket }}</span>
                <div class="text-[11px] mt-1" style="color:var(--sub)">{{ \Illuminate\Support\Carbon::parse($c->created_at)->locale('tr')->diffForHumans() }}</div>
            </div>
        </div>
    @empty
        <div class="px-4 py-8 text-center text-sm" style="color:var(--sub)">Henüz çağrı kaydı yok</div>
    @endforelse
</div>
@endsection

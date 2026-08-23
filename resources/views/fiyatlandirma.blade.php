@extends('layout.app')
@section('title', 'Fiyatlandırma')
@section('baslik', '🏷️ Fiyatlandırma & Paketler')

@section('content')
@php
    $paketler = [
        ['Başlangıç', 899, 'Küçük cafe & büfe', 'slate', false],
        ['Pro', 1799, 'Tam restoran', 'indigo', true],
        ['Zincir', 3499, 'Çoklu şube & franchise', 'violet', false],
    ];
    // ozellik => [baslangic, pro, zincir]
    $matris = [
        'POS / Adisyon / Masa yönetimi' => [1, 1, 1],
        'QR Menü (müşteri sipariş)' => [1, 1, 1],
        'Menü yönetimi + 86 (tükendi)' => [1, 1, 1],
        'Temel ciro & satış raporları' => [1, 1, 1],
        'Mutfak Ekranı (KDS)' => [0, 1, 1],
        'Stok & Reçete + maliyet' => [0, 1, 1],
        'Satın Alma + Fiyat Zekâsı' => [0, 1, 1],
        'Paket Sipariş Merkezi (tüm platformlar)' => [0, 1, 1],
        'Kurye Takip (harita)' => [0, 1, 1],
        'Müşteri / CRM + Sadakat' => [0, 1, 1],
        'Çağrı Merkezi (CallerID)' => [0, 1, 1],
        'Menü Mühendisliği raporu' => [0, 1, 1],
        'Çoklu şube + Şube karşılaştırma' => [0, 0, 1],
        'AI Copilot (restoranınla konuş)' => [0, 0, 1],
        'Gelişmiş yetki / rol yönetimi' => [0, 0, 1],
        'Öncelikli 7/24 destek' => [0, 0, 1],
        'Şube sayısı' => ['1', '1', 'Sınırsız'],
    ];
@endphp

<p class="text-slate-500 mb-6">Şeffaf fiyat, gizli maliyet yok. 14 gün ücretsiz dene, kart gerekmez. İstediğin zaman iptal et.</p>

{{-- Paket kartlari --}}
<div class="grid md:grid-cols-3 gap-5 mb-10">
    @foreach ($paketler as [$ad, $fiyat, $hedef, $renk, $populer])
        <div class="relative bg-white rounded-3xl border-2 {{ $populer ? 'border-indigo-500 shadow-lg' : 'border-slate-200' }} p-6">
            @if ($populer)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-bold px-3 py-1 rounded-full">EN POPÜLER</div>
            @endif
            <div class="text-lg font-bold text-slate-900">{{ $ad }}</div>
            <div class="text-xs text-slate-400 mb-3">{{ $hedef }}</div>
            <div class="flex items-end gap-1 mb-4">
                <span class="text-3xl font-bold text-slate-900">{{ number_format($fiyat, 0, ',', '.') }} ₺</span>
                <span class="text-slate-400 text-sm mb-1">/ay + KDV</span>
            </div>
            <button class="w-full {{ $populer ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }} font-semibold rounded-xl py-3 transition">14 Gün Ücretsiz Dene</button>
        </div>
    @endforeach
</div>

{{-- Ozellik matrisi --}}
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr>
                <th class="text-left px-4 py-3">Özellik</th>
                <th class="text-center px-4 py-3">Başlangıç</th>
                <th class="text-center px-4 py-3 bg-indigo-50 text-indigo-600">Pro</th>
                <th class="text-center px-4 py-3">Zincir</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($matris as $ozellik => $degerler)
                <tr>
                    <td class="px-4 py-2.5 text-slate-700">{{ $ozellik }}</td>
                    @foreach ($degerler as $i => $d)
                        <td class="px-4 py-2.5 text-center {{ $i === 1 ? 'bg-indigo-50/40' : '' }}">
                            @if ($d === 1)
                                <span class="text-emerald-500 font-bold">✓</span>
                            @elseif ($d === 0)
                                <span class="text-slate-300">—</span>
                            @else
                                <span class="text-slate-700 font-semibold text-xs">{{ $d }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Ek moduller / kullandikca ode --}}
<h2 class="font-bold text-slate-800 mb-3">Ek Modüller & Kullandıkça Öde</h2>
<div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="text-2xl mb-1">📞</div>
        <div class="font-semibold text-slate-800">AI Santral</div>
        <div class="text-xs text-slate-400 mb-2">7/24 telefonu AI açar, kaçan çağrıyı kurtarır</div>
        <div class="text-indigo-600 font-bold text-sm">Kullandıkça öde · ~2 ₺/çağrı</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="text-2xl mb-1">🏬</div>
        <div class="font-semibold text-slate-800">Ek Şube</div>
        <div class="text-xs text-slate-400 mb-2">Zincir paketinde ek her şube</div>
        <div class="text-indigo-600 font-bold text-sm">599 ₺/ay/şube</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="text-2xl mb-1">💳</div>
        <div class="font-semibold text-slate-800">Entegre Ödeme</div>
        <div class="text-xs text-slate-400 mb-2">QR/online ödeme, tek tıkla</div>
        <div class="text-indigo-600 font-bold text-sm">İşlem başına düşük komisyon</div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <div class="text-2xl mb-1">🎓</div>
        <div class="font-semibold text-slate-800">Kurulum & Eğitim</div>
        <div class="text-xs text-slate-400 mb-2">Menü kurulumu + 10 dk personel eğitimi</div>
        <div class="text-indigo-600 font-bold text-sm">Tek seferlik (opsiyonel)</div>
    </div>
</div>

<p class="text-xs text-slate-400 mt-6">💡 Kerzz/Simpra fiyatı gizler (demo→teklif). Biz şeffaf gösteriyoruz — küçük işletme hemen başlar. AI Santral & ödeme komisyonu ana gelir kolları (Toast modeli).</p>
@endsection

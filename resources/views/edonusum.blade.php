@extends('layout.app')
@section('title', 'E-Dönüşüm')
@section('baslik', '🧾 E-Dönüşüm (E-Arşiv / E-Fatura)')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Belge</div><div class="text-2xl font-bold text-indigo-600 mt-1">{{ $bugunAdet }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Tutar</div><div class="text-xl font-bold text-emerald-600 mt-1">{{ number_format($bugunTutar, 0, ',', '.') }} ₺</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">30 Gün Belge</div><div class="text-2xl font-bold text-slate-700 mt-1">{{ $ayAdet }}</div></div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Yazarkasa (ÖKC)</div><div class="text-sm font-bold text-emerald-600 mt-2">● Bağlı (demo)</div></div>
</div>

<p class="text-sm text-slate-500 mb-3">Ödeme alındığında <b>e-Arşiv fişi otomatik kesilir</b>. Son belgeler:</p>
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr><th class="text-left px-4 py-2">Belge No</th><th class="text-left px-4 py-2">Kaynak</th>
                <th class="text-left px-4 py-2">Tarih</th><th class="text-right px-4 py-2">Tutar</th><th class="text-center px-4 py-2">Durum</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($belgeler as $b)
                <tr>
                    <td class="px-4 py-2 font-mono text-xs text-indigo-700">EAR{{ now()->year }}{{ str_pad($b->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-4 py-2 text-slate-600">{{ $b->kanal === 'paket' ? '🛵 Paket' : ('🍽️ Masa ' . ($b->masa ?? '-')) }}</td>
                    <td class="px-4 py-2 text-slate-400 text-xs">{{ \Illuminate\Support\Carbon::parse($b->kapanis)->format('d.m.Y H:i') }}</td>
                    <td class="px-4 py-2 text-right font-semibold text-slate-700">{{ number_format((float) $b->toplam, 0, ',', '.') }} ₺</td>
                    <td class="px-4 py-2 text-center"><span class="text-xs bg-emerald-100 text-emerald-700 font-semibold px-2 py-0.5 rounded">✓ Kesildi</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="text-xs text-slate-400 mt-4">ℹ️ Gerçek e-Arşiv/e-Fatura için GİB onaylı entegratör (ör. Cloud Labs e-Dönüşüm) bağlanır; yazarkasa (ÖKC) ile mali fiş otomatik kesilir. Mimari hazır, entegratör anahtarı gelince canlıya alınır.</p>
@endsection

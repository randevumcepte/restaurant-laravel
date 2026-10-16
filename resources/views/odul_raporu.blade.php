@extends('layout.app')
@section('title', 'Ödül Raporu')
@section('baslik', '🏆 Kullanılan Ödüller')

@section('content')
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  @php
    $kutu = [
      ['Kullanılan ödül', $ozet['adet'], '🎁', 'indigo'],
      ['Harcanan puan', number_format($ozet['puan'], 0, ',', '.'), '⭐', 'amber'],
      ['Bugün', $ozet['bugun'], '📅', 'emerald'],
      ['Bekleyen kod', $ozet['bekleyen'], '⏳', 'slate'],
    ];
  @endphp
  @foreach ($kutu as [$ad, $deger, $ikon, $renk])
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-2xl mb-1">{{ $ikon }}</div>
      <div class="text-2xl font-extrabold text-slate-800">{{ $deger }}</div>
      <div class="text-xs text-slate-500 mt-0.5">{{ $ad }}</div>
    </div>
  @endforeach
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
    <h2 class="font-bold text-slate-800">Son Ödül Kullanımları</h2>
    <a href="/odul-dogrula" class="text-sm font-semibold text-indigo-600 hover:underline">✅ Ödül Doğrula →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="text-left text-slate-400 border-b border-slate-100">
          <th class="px-5 py-3 font-medium">Tarih</th>
          <th class="px-5 py-3 font-medium">Müşteri</th>
          <th class="px-5 py-3 font-medium">Ödül</th>
          <th class="px-5 py-3 font-medium text-right">Puan</th>
          <th class="px-5 py-3 font-medium">Kasiyer</th>
          <th class="px-5 py-3 font-medium">Durum</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $x)
          @php
            $t = $x->kullanildi_at ?? $x->created_at;
            $ad = trim(($x->m_ad ?? '') . ' ' . ($x->m_soyad ?? '')) ?: ($x->m_tel ?? 'Müşteri');
            $durumRenk = $x->durum === 'kullanildi' ? 'bg-emerald-50 text-emerald-600' : ($x->durum === 'bekliyor' ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500');
            $durumAd = $x->durum === 'kullanildi' ? 'Kullanıldı' : ($x->durum === 'bekliyor' ? 'Bekliyor' : 'İptal');
          @endphp
          <tr class="border-b border-slate-50 hover:bg-slate-50/60">
            <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($t)->format('d.m.Y H:i') }}</td>
            <td class="px-5 py-3 text-slate-800 font-medium">{{ $ad }}</td>
            <td class="px-5 py-3 text-slate-700">🎁 {{ $x->odul_ad }} <span class="text-slate-300 text-xs ml-1">{{ $x->kod }}</span></td>
            <td class="px-5 py-3 text-right font-bold text-indigo-700">{{ $x->puan }}</td>
            <td class="px-5 py-3 text-slate-500">{{ $x->kasiyer_ad ?? '—' }}</td>
            <td class="px-5 py-3"><span class="text-xs font-semibold px-2 py-1 rounded-full {{ $durumRenk }}">{{ $durumAd }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6" class="px-5 py-10 text-center text-slate-400">Henüz ödül kullanımı yok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

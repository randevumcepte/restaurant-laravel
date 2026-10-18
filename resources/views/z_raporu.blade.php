@extends('layout.app')
@section('title', 'Z Raporu')
@section('baslik', '🧾 Z Raporu (Gün Sonu)')

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
  $my = (int) ($z['maliyet_yuzde'] ?? 0);
  $myRenk = $my >= 38 ? 'text-rose-600' : ($my >= 30 ? 'text-amber-500' : 'text-emerald-600');
@endphp

<style>
  @media print {
    body { background: #fff !important; }
    .no-print { display: none !important; }
    .z-sheet { box-shadow: none !important; border: 0 !important; }
    aside, nav, header, .sidebar { display: none !important; }
    main { margin: 0 !important; padding: 0 !important; }
  }
</style>

{{-- Araç çubuğu: tarih seçici + yazdır (yazdırmada gizli) --}}
<div class="no-print flex items-center justify-between mb-5 flex-wrap gap-3">
  <form method="GET" action="/z-raporu" class="inline-flex items-center gap-2 bg-white rounded-xl p-2 shadow-sm">
    <a href="/z-raporu?tarih={{ $onceki }}" class="w-9 h-9 flex items-center justify-center rounded-lg text-indigo-600 hover:bg-slate-100 text-lg">‹</a>
    <input type="date" name="tarih" value="{{ $ymd }}" max="{{ $bugun }}" onchange="this.form.submit()"
           class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
    @if ($sonraki)
      <a href="/z-raporu?tarih={{ $sonraki }}" class="w-9 h-9 flex items-center justify-center rounded-lg text-indigo-600 hover:bg-slate-100 text-lg">›</a>
    @else
      <span class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-300 text-lg">›</span>
    @endif
  </form>
  <button onclick="window.print()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm px-4 py-2.5 rounded-xl shadow-sm">
    🖨️ Z Raporunu Yazdır
  </button>
</div>

@if (!empty($z['hata']))
  <div class="bg-white rounded-2xl p-10 text-center text-rose-500 shadow-sm">Rapor hesaplanamadı: {{ $z['hata'] }}</div>
@else
<div class="z-sheet max-w-4xl mx-auto bg-white rounded-2xl shadow-sm p-6 md:p-8">

  {{-- Başlık --}}
  <div class="text-center border-b border-slate-100 pb-5 mb-6">
    <div class="text-lg font-extrabold text-slate-800">{{ $z['isletme'] }}</div>
    <div class="text-sm text-slate-400 mt-0.5">Gün Sonu (Z) Raporu · {{ $z['tarih'] }}</div>
  </div>

  {{-- Ciro hero --}}
  <div class="rounded-2xl p-6 text-white mb-6" style="background: linear-gradient(135deg, #4F46E5, #7C3AED);">
    <div class="text-sm text-white/70">Toplam Ciro</div>
    <div class="text-4xl font-extrabold mt-1">{{ $tl($z['ciro']) }}</div>
    <div class="grid grid-cols-4 gap-3 mt-5">
      @php
        $hero = [
          [$z['kapanan'], 'Adisyon'],
          [$z['misafir'], 'Misafir'],
          [$tl($z['ortalama']), 'Ort. Adisyon'],
          [$tl($z['kisi_basi']), 'Kişi Başı'],
        ];
      @endphp
      @foreach ($hero as [$v, $e])
        <div>
          <div class="text-base font-bold truncate">{{ $v }}</div>
          <div class="text-[11px] text-white/60">{{ $e }}</div>
        </div>
      @endforeach
    </div>
  </div>

  @if (($z['acik_kalan'] ?? 0) > 0)
    <div class="flex items-start gap-2 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 mb-6 text-sm text-orange-800">
      <span>⚠️</span>
      <span>{{ $z['acik_kalan'] }} adisyon hâlâ açık — gün kapanmadan kapatılmalı.</span>
    </div>
  @endif

  <div class="grid gap-6 md:grid-cols-2">

    {{-- Ödeme dağılımı --}}
    <div class="border border-slate-100 rounded-2xl p-5">
      <div class="font-bold text-slate-800 mb-3">💳 Ödeme Dağılımı</div>
      @forelse ($z['odeme'] as $o)
        <div class="flex items-center justify-between py-1.5 text-sm">
          <span class="text-slate-500">{{ mb_strtoupper($o->tip ?? '-') }} <span class="text-slate-300">({{ (int) $o->adet }})</span></span>
          <span class="font-semibold text-slate-700">{{ $tl($o->tutar) }}</span>
        </div>
      @empty
        <div class="text-sm text-slate-400 py-1.5">Ödeme yok.</div>
      @endforelse
    </div>

    {{-- Servis tipi --}}
    <div class="border border-slate-100 rounded-2xl p-5">
      <div class="font-bold text-slate-800 mb-3">🍽️ Servis Tipi</div>
      @forelse ($z['servis'] as $s)
        <div class="flex items-center justify-between py-1.5 text-sm">
          <span class="text-slate-500">{{ $s['ad'] }} <span class="text-slate-300">({{ (int) $s['adet'] }})</span></span>
          <span class="font-semibold text-slate-700">{{ $tl($s['tutar']) }}</span>
        </div>
      @empty
        <div class="text-sm text-slate-400 py-1.5">Kayıt yok.</div>
      @endforelse
    </div>

    {{-- Maliyet / Kâr --}}
    <div class="border border-slate-100 rounded-2xl p-5">
      <div class="font-bold text-slate-800 mb-3">💰 Maliyet / Kâr</div>
      <div class="flex items-center justify-between py-1.5 text-sm">
        <span class="text-slate-500">Food-Cost</span>
        <span class="font-semibold {{ $myRenk }}">{{ $tl($z['maliyet']) }} <span class="text-slate-400">(%{{ $my }})</span></span>
      </div>
      <div class="flex items-center justify-between py-1.5 text-sm">
        <span class="text-slate-500">Brüt Kâr</span>
        <span class="font-bold text-emerald-600">{{ $tl($z['brut_kar']) }}</span>
      </div>
    </div>

    {{-- Kayıp / Sızıntı --}}
    <div class="border border-slate-100 rounded-2xl p-5">
      <div class="font-bold text-slate-800 mb-3">🎯 Kayıp / Sızıntı</div>
      @php
        $sizinti = [
          ['İskonto', $z['iskonto']],
          ['İkram', $z['ikram']],
          ['Silinen Ürün', $z['void']],
          ['İptal Adisyon (' . (int) $z['iptal_adet'] . ')', $z['iptal_tutar']],
          ['Fire', $z['fire']],
        ];
      @endphp
      @foreach ($sizinti as [$ad, $v])
        <div class="flex items-center justify-between py-1.5 text-sm">
          <span class="text-slate-500">{{ $ad }}</span>
          <span class="font-semibold text-rose-600">{{ $tl($v) }}</span>
        </div>
      @endforeach
    </div>

    {{-- En çok satan --}}
    <div class="border border-slate-100 rounded-2xl p-5 md:col-span-2">
      <div class="font-bold text-slate-800 mb-3">🔥 En Çok Satan</div>
      @forelse ($z['top'] as $i => $t)
        <div class="flex items-center justify-between py-1.5 text-sm">
          <span class="text-slate-500">{{ $i + 1 }}. {{ $t->urun_adi }}</span>
          <span class="font-semibold text-slate-700">{{ (int) $t->adet }} adet</span>
        </div>
      @empty
        <div class="text-sm text-slate-400 py-1.5">Satış yok.</div>
      @endforelse
    </div>

  </div>

  <div class="text-center text-[11px] text-slate-300 mt-8 pt-5 border-t border-slate-100">
    {{ $z['isletme'] }} · {{ $z['tarih'] }} · ResteOS Z Raporu · {{ now()->format('d.m.Y H:i') }}
  </div>

</div>
@endif
@endsection

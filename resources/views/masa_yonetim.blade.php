@extends('layout.app')
@section('title', 'Masa Yönetim')
@section('baslik', '🪑 Masa Yönetim')

@section('content')
@php
  $aktif    = $aktif    ?? [];
  $arsiv    = $arsiv    ?? [];
  $bolgeler = $bolgeler ?? [];

  // Aktif masalari bolgeye gore grupla
  $gruplar = [];
  foreach ($aktif as $m) {
      $bk = $m['bolge'] ?: 'Bölgesiz';
      $gruplar[$bk][] = $m;
  }
  ksort($gruplar);
@endphp

@if (session('ok'))
  <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-3 text-sm">{{ session('hata') }}</div>
@endif

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Aktif masa</span> <b class="text-slate-800 ml-1">{{ count($aktif) }}</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Arşiv</span> <b class="text-slate-800 ml-1">{{ count($arsiv) }}</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Bölge</span> <b class="text-slate-800 ml-1">{{ count($bolgeler) }}</b></div>
  </div>
  <a href="/masa-afisler" target="_blank"
     class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl px-4 py-2 shadow-sm">
    🖨️ Tüm QR afişlerini yazdır
  </a>
</div>

{{-- ===== MASA EKLE ===== --}}
<div class="bg-white rounded-2xl p-5 shadow-sm mb-6">
  <div class="font-bold text-slate-800 mb-3">➕ Masa Ekle</div>
  <form method="POST" action="/masa-yonetim/ekle" class="grid gap-3 md:grid-cols-4 items-end">
    @csrf
    <div>
      <label class="block text-xs text-slate-400 mb-1">Masa adı <span class="text-slate-300">(boş = otomatik)</span></label>
      <input type="text" name="ad" placeholder="Örn. Masa 5 / Teras 2"
             class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
    </div>
    <div>
      <label class="block text-xs text-slate-400 mb-1">Kapasite (kişi)</label>
      <input type="number" name="kapasite" value="4" min="1" max="50"
             class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
    </div>
    <div>
      <label class="block text-xs text-slate-400 mb-1">Bölge</label>
      <select name="bolge_id"
              class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-300">
        <option value="">Varsayılan (Salon)</option>
        @foreach ($bolgeler as $b)
          <option value="{{ $b->id }}">{{ $b->ad }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <button type="submit"
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg px-4 py-2 shadow-sm">
        Ekle (QR otomatik)
      </button>
    </div>
  </form>
  <div class="text-[11px] text-slate-400 mt-2">Her masa için kalıcı QR kodu otomatik oluşturulur ve asla değişmez.</div>
</div>

{{-- ===== AKTIF MASALAR (bolgeye gore) ===== --}}
@if (empty($aktif))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm mb-6">
    Henüz masa yok. Yukarıdan "Masa Ekle" ile başlayın.
  </div>
@else
  @foreach ($gruplar as $bolge => $masalar)
    <div class="mb-6">
      <div class="flex items-center gap-2 mb-3">
        <span class="text-sm font-bold text-slate-700">📍 {{ $bolge }}</span>
        <span class="text-xs text-slate-400">({{ count($masalar) }} masa)</span>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @foreach ($masalar as $m)
          <div class="bg-white rounded-2xl p-4 shadow-sm flex flex-col">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="font-bold text-slate-800 truncate">{{ $m['ad'] }}</div>
                <div class="text-xs text-slate-400">{{ $m['kapasite'] }} kişi</div>
              </div>
              @if (!empty($m['qr_token']))
                <span class="shrink-0 inline-flex items-center gap-1 bg-emerald-50 text-emerald-600 text-[10px] font-semibold rounded-full px-2 py-1">● QR hazır</span>
              @else
                <span class="shrink-0 inline-flex items-center gap-1 bg-amber-50 text-amber-600 text-[10px] font-semibold rounded-full px-2 py-1">QR yok</span>
              @endif
            </div>

            <div class="mt-3 bg-slate-50 rounded-xl p-3 flex items-center justify-center">
              @if (!empty($m['qr_url']))
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($m['qr_url']) }}"
                     alt="QR" class="w-32 h-32 bg-white rounded-lg" loading="lazy">
              @else
                <div class="w-32 h-32 flex items-center justify-center text-slate-300 text-4xl">⬚</div>
              @endif
            </div>

            <div class="mt-3 flex gap-2">
              @if (!empty($m['qr_url']))
                <a href="/masa-afis/{{ $m['id'] }}" target="_blank"
                   class="flex-1 text-center bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-semibold rounded-lg px-3 py-2">QR Afiş</a>
              @endif
              <form method="POST" action="/masa-yonetim/sil" class="flex-1"
                    onsubmit="return confirm('{{ $m['ad'] }} arşive taşınsın mı? Masa SİLİNMEZ, QR kodu KORUNUR — istediğin an aynı kodla geri getirebilirsin.');">
                @csrf
                <input type="hidden" name="id" value="{{ $m['id'] }}">
                <button type="submit"
                        class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold rounded-lg px-3 py-2">Arşivle</button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endforeach
@endif

{{-- ===== ARSIV ===== --}}
<div class="bg-white rounded-2xl p-5 shadow-sm">
  <div class="font-bold text-slate-800 mb-1">🗄️ Arşiv</div>
  <div class="text-[11px] text-slate-400 mb-4">Silinen masalar buraya düşer. QR kodları korunur; tek tıkla aynı kodla geri gelir.</div>
  @if (empty($arsiv))
    <div class="text-sm text-slate-400 text-center py-6">Arşiv boş.</div>
  @else
    <div class="divide-y divide-slate-100">
      @foreach ($arsiv as $m)
        <div class="flex items-center gap-3 py-3">
          <div class="text-slate-300 text-2xl">▦</div>
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-slate-700 truncate">{{ $m['ad'] }}</div>
            <div class="text-xs text-slate-400">{{ $m['kapasite'] }} kişi · QR korunuyor (kalıcı kod)</div>
          </div>
          <form method="POST" action="/masa-yonetim/geri">
            @csrf
            <input type="hidden" name="id" value="{{ $m['id'] }}">
            <button type="submit"
                    class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg px-3 py-2">↩ Geri Getir</button>
          </form>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

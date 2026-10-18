@extends('layout.app')
@section('title', 'Bağlı Cihazlar')
@section('baslik', '🖥️ Bağlı Cihazlar')

@section('content')
@php
  // tip -> etiket + ikon (Flutter bagli_cihazlar_screen ile birebir)
  $tipler = [
    'yazici'       => ['ad' => 'Yazıcı (fiş/mutfak)', 'ikon' => '🖨️'],
    'yazarkasa'    => ['ad' => 'Yazarkasa (ÖKC)',     'ikon' => '🧾'],
    'terminal'     => ['ad' => 'Ödeme Terminali (POS)','ikon' => '💳'],
    'cekmece'      => ['ad' => 'Kasa Çekmecesi',       'ikon' => '🗄️'],
    'bilgisayar'   => ['ad' => 'Bilgisayar / Kasa',    'ikon' => '💻'],
    'kds'          => ['ad' => 'Mutfak Ekranı (KDS)',  'ikon' => '📺'],
    'barkod'       => ['ad' => 'Barkod Okuyucu',       'ikon' => '🔳'],
    'el_terminali' => ['ad' => 'El Terminali',         'ikon' => '📟'],
    'tablet'       => ['ad' => 'Tablet',               'ikon' => '📱'],
    'telefon'      => ['ad' => 'Telefon',              'ikon' => '📱'],
    'diger'        => ['ad' => 'Diğer',                'ikon' => '🔌'],
  ];
  $tipAd   = fn($k) => $tipler[$k]['ad']   ?? ($tipler['diger']['ad']);
  $tipIkon = fn($k) => $tipler[$k]['ikon'] ?? ($tipler['diger']['ikon']);

  $manuel = array_values(array_filter($cihazlar, fn($c) => !empty($c['manuel'])));
  $canli  = array_values(array_filter($cihazlar, fn($c) => empty($c['manuel'])));
  $onlineSayi = count(array_filter($canli, fn($c) => !empty($c['online'])));

  // Yazarkasa durum renkleri (Flutter _ykRenk eşlemesi)
  $ykRenkler = [
    'online'   => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500', 'et' => 'Bağlı'],
    'hata'     => ['bg' => 'bg-rose-100',    'text' => 'text-rose-700',    'dot' => 'bg-rose-500',    'et' => 'HATA'],
    'test'     => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500',   'et' => 'Test/Simülasyon'],
    'bekliyor' => ['bg' => 'bg-amber-100',   'text' => 'text-amber-700',   'dot' => 'bg-amber-500',   'et' => 'Aktif · fiş yok'],
    'kapali'   => ['bg' => 'bg-slate-100',   'text' => 'text-slate-500',   'dot' => 'bg-slate-400',   'et' => 'Kapalı'],
  ];
@endphp

@if (session('bc_mesaj'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm font-medium">{{ session('bc_mesaj') }}</div>
@endif
@if (session('bc_hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm font-medium">{{ session('bc_hata') }}</div>
@endif

@if ($hata)
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">Cihaz bilgileri yüklenemedi. (Tablo henüz oluşmamış olabilir.)</div>
@endif

{{-- Özet şeridi --}}
<div class="flex items-center gap-3 mb-5 flex-wrap text-sm">
  <div class="inline-flex items-center gap-2 bg-white rounded-xl px-4 py-2 shadow-sm">
    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
    <b class="text-slate-800">{{ $onlineSayi }}</b> <span class="text-slate-400">cihaz çevrimiçi</span>
  </div>
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Tanımlı</span> <b class="text-slate-800 ml-1">{{ count($manuel) }}</b></div>
  <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Toplam</span> <b class="text-slate-800 ml-1">{{ count($cihazlar) }}</b></div>
  <div class="ml-auto text-xs text-slate-400">Çevrimiçi eşiği: son {{ (int) $esikSn }} sn</div>
</div>

{{-- TANIMLI CIHAZLAR (manuel) --}}
<div class="flex items-center gap-2 mb-3">
  <h2 class="text-base font-bold text-slate-800">🔌 Tanımlı Cihazlar</h2>
  <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">{{ count($manuel) }}</span>
</div>
@if (!$manuel)
  <div class="bg-white rounded-2xl p-8 text-center text-slate-400 shadow-sm mb-8 text-sm">
    Henüz cihaz tanımlanmadı. Yazıcı, yazarkasa, terminal vb. cihazlar uygulamadan eklenip IP/port girilebilir.
  </div>
@else
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 mb-8">
    @foreach ($manuel as $c)
      @php $aktif = !empty($c['aktif']); @endphp
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="flex items-start gap-3">
          <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl {{ $aktif ? 'bg-emerald-50' : 'bg-slate-100' }}">{{ $tipIkon($c['tip']) }}</div>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-slate-800 truncate">{{ $c['ad'] }}</div>
            <div class="text-xs text-slate-400 truncate">{{ $tipAd($c['tip']) }}{{ !empty($c['marka']) ? ' · ' . $c['marka'] : '' }}</div>
            @if (!empty($c['ip']))
              <div class="text-xs font-semibold text-slate-500 mt-0.5">IP: {{ $c['ip'] }}{{ !empty($c['port']) ? ':' . $c['port'] : '' }}</div>
            @endif
          </div>
          <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $aktif ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $aktif ? 'Aktif' : 'Pasif' }}</span>
        </div>
        @if (!empty($c['seri_no']))
          <div class="text-[11px] text-slate-400 mt-2">Seri No: {{ $c['seri_no'] }}</div>
        @endif
        @if (!empty($c['notlar']))
          <div class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $c['notlar'] }}</div>
        @endif
        <div class="flex items-center gap-2 mt-4 pt-3 border-t border-slate-100">
          <form method="POST" action="/bagli-cihazlar/ad" class="flex items-center gap-2 flex-1"
                onsubmit="var n=this.querySelector('[name=ad]'); if(!n.value.trim()){n.focus();return false;} return true;">
            @csrf
            <input type="hidden" name="id" value="{{ $c['id'] }}">
            <input name="ad" value="{{ $c['ad'] }}" maxlength="80"
                   class="flex-1 min-w-0 px-2.5 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 focus:outline-none focus:border-indigo-400">
            <button class="shrink-0 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Kaydet</button>
          </form>
          <form method="POST" action="/bagli-cihazlar/sil" onsubmit="return confirm('“{{ $c['ad'] }}” silinsin mi?');">
            @csrf
            <input type="hidden" name="id" value="{{ $c['id'] }}">
            <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 text-xs font-semibold hover:bg-rose-100 border border-rose-200">Sil</button>
          </form>
        </div>
      </div>
    @endforeach
  </div>
@endif

{{-- YAZARKASA (OKC durumu) --}}
@if ($yazarkasa)
  @php $yk = $ykRenkler[$yazarkasa['durum']] ?? $ykRenkler['kapali']; @endphp
  <div class="flex items-center gap-2 mb-3">
    <h2 class="text-base font-bold text-slate-800">🧾 Yazarkasa (ÖKC)</h2>
  </div>
  <div class="bg-white rounded-2xl p-5 shadow-sm mb-8">
    <div class="flex items-start gap-3">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-xl {{ $yk['bg'] }}">🧾</div>
      <div class="min-w-0 flex-1">
        <div class="font-bold text-slate-800">Yazarkasa{{ !empty($yazarkasa['marka']) ? ' · ' . $yazarkasa['marka'] : '' }}</div>
        @if (!empty($yazarkasa['aktif']) && !empty($yazarkasa['ip']))
          <div class="text-xs text-slate-400 mt-0.5">IP: {{ $yazarkasa['ip'] }}{{ !empty($yazarkasa['port']) ? ':' . $yazarkasa['port'] : '' }}</div>
        @endif
      </div>
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold {{ $yk['bg'] }} {{ $yk['text'] }}">
        <span class="w-2 h-2 rounded-full {{ $yk['dot'] }}"></span> {{ $yk['et'] }}
      </span>
    </div>
    <div class="text-sm text-slate-600 mt-3 leading-relaxed">{{ $yazarkasa['mesaj'] }}</div>
    @if (!empty($yazarkasa['son_fis']))
      <div class="text-xs text-slate-400 mt-1">Son mali fiş: {{ $yazarkasa['son_fis'] }}</div>
    @endif
  </div>
@endif

{{-- CANLI (heartbeat) CIHAZLAR --}}
<div class="flex items-center gap-2 mb-3">
  <h2 class="text-base font-bold text-slate-800">📱 Çevrimiçi Cihazlar (ResteOS)</h2>
  <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">{{ count($canli) }}</span>
</div>
@if (!$canli)
  <div class="bg-white rounded-2xl p-8 text-center text-slate-400 shadow-sm text-sm">
    Sinyal gönderen cihaz yok. Bir cihazda ResteOS açık olduğunda burada görünür.
  </div>
@else
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($canli as $c)
      @php $online = !empty($c['online']); @endphp
      <div class="bg-white rounded-2xl p-5 shadow-sm {{ $online ? 'ring-1 ring-emerald-200' : 'ring-1 ring-rose-100' }}">
        <div class="flex items-start gap-3">
          <div class="relative w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-xl">
            {{ $tipIkon($c['tip']) }}
            <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white {{ $online ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-slate-800 truncate">{{ $c['ad'] }}</div>
            <div class="text-xs text-slate-400 truncate">
              {{ $c['platform'] ?: $tipAd($c['tip']) }}{{ !empty($c['ip']) ? ' · ' . $c['ip'] : '' }}
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">
              {{ !empty($c['rol']) ? ucfirst($c['rol']) : '' }}{{ !empty($c['surum']) ? ' · v' . $c['surum'] : '' }}
            </div>
          </div>
          <div class="text-right">
            <span class="px-2 py-0.5 rounded-lg text-xs font-bold {{ $online ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $online ? 'Bağlı' : 'Kopuk' }}</span>
            <div class="text-[11px] text-slate-400 mt-1">{{ $c['son_gorulme'] }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif
@endsection

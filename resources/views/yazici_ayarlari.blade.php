@extends('layout.app')
@section('title', 'Yazıcı Ayarları')
@section('baslik', '🖨️ Yazıcı Ayarları')

@section('content')
@php
  $ip        = $ayar['ip'] ?? '';
  $port      = $ayar['port'] ?? '9100';
  $dar       = (int) ($ayar['dar'] ?? 0) === 1;      // 1 = 58mm dar · 0 = 80mm geniş
  $turkce    = (int) ($ayar['turkce'] ?? 1) === 1;    // 1 = CP857 Türkçe · 0 = ASCII
  $kod       = $ayar['kod'] ?? '13';
  $cekmece   = (int) ($ayar['cekmece'] ?? 1) === 1;
  $mutfakMod = in_array(($ayar['mutfak_mod'] ?? 'ekran'), ['ekran', 'yazici', 'ikisi']) ? ($ayar['mutfak_mod'] ?? 'ekran') : 'ekran';
  $mutfakIp  = $ayar['mutfak_ip'] ?? '';
  $barIp     = $ayar['bar_ip'] ?? '';
  $izgaraIp  = $ayar['izgara_ip'] ?? '';
@endphp

<div class="max-w-3xl mx-auto">

  @if (session('yazici_ok'))
    <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-semibold">
      ✓ Ayarlar kaydedildi.
    </div>
  @endif
  @if (session('yazici_hata'))
    <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-3 text-sm font-semibold">
      ⚠ {{ session('yazici_hata') }}
    </div>
  @endif

  <div class="mb-5 bg-indigo-50 border border-indigo-200 rounded-2xl p-4 flex gap-3">
    <div class="text-indigo-500 text-lg leading-none mt-0.5">ℹ️</div>
    <div class="text-sm text-slate-600 leading-relaxed">
      Ağ (LAN) yazıcısına doğrudan basar; mevcut POS/ÖKC sistemine dokunmaz. Yazıcının IP adresini girin.
      Bu ayarlar <b>uygulamadaki Yazıcı Ayarları</b> ile aynı kaynağa yazılır. Mali fiş <b>değildir</b> — yasal fiş ÖKC cihazından kesilmeye devam eder.
    </div>
  </div>

  <form method="POST" action="/yazici-ayarlari/kaydet" class="space-y-5">
    @csrf

    {{-- Bağlantı --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Bağlantı</div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Yazıcı IP adresi</label>
          <input type="text" name="ip" value="{{ $ip }}" placeholder="örn. 192.168.1.50"
                 class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Port</label>
          <input type="text" name="port" value="{{ $port }}" placeholder="9100"
                 class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none">
        </div>
      </div>
    </div>

    {{-- Kağıt Genişliği --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Kağıt Genişliği</div>
      <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer">
          <input type="radio" name="dar" value="0" class="peer sr-only" {{ !$dar ? 'checked' : '' }}>
          <div class="text-center py-3 rounded-lg border text-sm font-semibold text-slate-500 border-slate-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">80 mm (geniş)</div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="dar" value="1" class="peer sr-only" {{ $dar ? 'checked' : '' }}>
          <div class="text-center py-3 rounded-lg border text-sm font-semibold text-slate-500 border-slate-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">58 mm (dar)</div>
        </label>
      </div>
    </div>

    {{-- Karakter --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Karakter</div>
      <div class="grid grid-cols-2 gap-3">
        <label class="cursor-pointer">
          <input type="radio" name="turkce" value="1" class="peer sr-only" {{ $turkce ? 'checked' : '' }}>
          <div class="text-center py-3 rounded-lg border text-sm font-semibold text-slate-500 border-slate-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">Türkçe (CP857)</div>
        </label>
        <label class="cursor-pointer">
          <input type="radio" name="turkce" value="0" class="peer sr-only" {{ !$turkce ? 'checked' : '' }}>
          <div class="text-center py-3 rounded-lg border text-sm font-semibold text-slate-500 border-slate-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">ASCII (sade)</div>
        </label>
      </div>
      <div class="mt-4 flex items-center gap-3">
        <div class="text-xs text-slate-500 flex-1">Kod sayfası no (Türkçe bozuk çıkarsa değiştir: 13 · 18 · 34…)</div>
        <input type="text" name="kod" value="{{ $kod }}" placeholder="13"
               class="w-16 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 text-center focus:border-indigo-500 focus:ring-0 outline-none">
      </div>
    </div>

    {{-- Çekmece --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Çekmece</div>
      <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="cekmece" value="1" {{ $cekmece ? 'checked' : '' }}
               class="mt-0.5 w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-0">
        <div>
          <div class="text-sm font-semibold text-slate-700">Hesap fişi basınca çekmeceyi aç</div>
          <div class="text-xs text-slate-400 mt-0.5">Yazıcıya bağlı kasa çekmecesi (RJ11) tetiklenir</div>
        </div>
      </label>
    </div>

    {{-- Mutfak Çıktısı --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-3">Mutfak Çıktısı (sipariş gönderilince)</div>
      <div class="grid grid-cols-3 gap-3 mb-3">
        @php $modlar = ['ekran' => '🖥️ Ekran', 'yazici' => '🧾 Yazıcı', 'ikisi' => '🖥️+🧾 İkisi']; @endphp
        @foreach ($modlar as $mk => $mad)
          <label class="cursor-pointer">
            <input type="radio" name="mutfak_mod" value="{{ $mk }}" class="peer sr-only" {{ $mutfakMod === $mk ? 'checked' : '' }}>
            <div class="text-center py-3 rounded-lg border text-sm font-semibold text-slate-500 border-slate-200 peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 transition">{{ $mad }}</div>
          </label>
        @endforeach
      </div>
      <div class="text-xs text-slate-400 leading-relaxed mb-4">
        <b>Ekran:</b> sipariş sadece Mutfak (KDS) ekranına düşer, fiş basılmaz.
        <b>Yazıcı:</b> mutfağa otomatik fiş basılır (KDS'de de görünür).
        <b>İkisi:</b> hem KDS ekranı hem otomatik fiş.
      </div>
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1.5">Mutfak yazıcısı IP (boş = ana yazıcı)</label>
          <input type="text" name="mutfak_ip" value="{{ $mutfakIp }}" placeholder="örn. 192.168.1.51"
                 class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none">
        </div>
        <div class="text-xs text-slate-400 leading-relaxed">
          İstasyon yazıcıları (boş = mutfak yazıcısı). Bar/ızgara siparişi KENDİ fişine + KENDİ yazıcısına gider.
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">🍹 Bar yazıcısı IP</label>
            <input type="text" name="bar_ip" value="{{ $barIp }}" placeholder="örn. 192.168.1.52"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1.5">🔥 Izgara yazıcısı IP</label>
            <input type="text" name="izgara_ip" value="{{ $izgaraIp }}" placeholder="örn. 192.168.1.53"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-500 focus:ring-0 outline-none">
          </div>
        </div>
      </div>
    </div>

    <div class="flex justify-end">
      <button type="submit"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm rounded-xl px-6 py-3 shadow-sm transition">
        💾 Kaydet
      </button>
    </div>
  </form>

</div>
@endsection

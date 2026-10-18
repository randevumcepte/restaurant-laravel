@extends('layout.app')
@section('title', 'Yazıcı Ayarları')
@section('rk_ikon', 'print')
@section('baslik', 'Yazıcı Ayarları')

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

<style>
  [x-cloak]{display:none!important}
  .ya-wrap{max-width:720px}
  .ya-bilgi{display:flex;gap:11px;align-items:flex-start;background:color-mix(in srgb,var(--mor1) 10%,transparent);border:1px solid color-mix(in srgb,var(--mor1) 30%,transparent);border-radius:12px;padding:13px;margin-bottom:16px}
  .ya-bilgi .material-icons-outlined{color:var(--mor1);font-size:18px;margin-top:1px}
  .ya-bilgi .m{color:var(--sub2);font-size:12px;line-height:1.35}
  .ya-blok{margin-bottom:16px}
  .ya-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .ya-basl{font-size:12.5px;font-weight:800;color:var(--sub);letter-spacing:.3px;margin:0 0 8px 4px}
  .ya-ipuc{color:var(--sub);font-size:11.5px;line-height:1.35}
  /* segmented seçim (Flutter _secim) */
  .ya-seg{display:flex;gap:10px}
  .ya-seg.uc>label{flex:1}
  .ya-seg>label{flex:1;display:block;cursor:pointer}
  .ya-seg input{position:absolute;opacity:0;width:0;height:0}
  .ya-seg .kutu{text-align:center;padding:12px;border-radius:10px;border:1px solid var(--line);background:var(--bg);color:var(--sub2);font-size:13px;font-weight:600;transition:.12s}
  .ya-seg input:checked + .kutu{background:var(--mor1);border-color:var(--mor1);color:#fff}
  /* çekmece anahtarı (SwitchListTile) */
  .ya-sw{display:flex;align-items:center;justify-content:space-between;gap:14px}
  .ya-sw .t{font-size:14px;color:var(--ink)}
  .ya-sw .a{font-size:11.5px;color:var(--sub);margin-top:2px}
  .ya-switch{position:relative;display:inline-block;width:46px;height:26px;flex:0 0 auto}
  .ya-switch input{opacity:0;width:0;height:0}
  .ya-switch .sl{position:absolute;inset:0;background:var(--line);border-radius:26px;transition:.15s;cursor:pointer}
  .ya-switch .sl:before{content:"";position:absolute;height:20px;width:20px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s;box-shadow:0 1px 3px rgba(0,0,0,.25)}
  .ya-switch input:checked + .sl{background:var(--mor1)}
  .ya-switch input:checked + .sl:before{transform:translateX(20px)}
  .ya-satir{display:flex;align-items:center;gap:10px;margin-top:4px}
  .ya-satir .ipuc{flex:1}
  .ya-kod{width:64px;flex:0 0 auto}
  .ya-kod .rk-inp{text-align:center}
</style>

<div class="ya-wrap" x-data="{ mutfakMod: '{{ $mutfakMod }}', turkce: {{ $turkce ? 'true' : 'false' }} }">

  @if (session('yazici_ok'))
    <div class="rk-flash ok">✓ Ayarlar kaydedildi.</div>
  @endif
  @if (session('yazici_hata'))
    <div class="rk-flash err">⚠ {{ session('yazici_hata') }}</div>
  @endif

  {{-- Bilgi kartı (Flutter _bilgiKart) --}}
  <div class="ya-bilgi">
    <span class="material-icons-outlined">info</span>
    <div class="m">
      Ağ (LAN) yazıcısına doğrudan basar; mevcut Kerzz/POS sistemine dokunmaz. Yazıcının IP adresini gir.
      Bu ayarlar <b>uygulamadaki Yazıcı Ayarları</b> ile aynı kaynağa yazılır. Mali fiş <b>DEĞİLDİR</b> — yasal fiş POS cihazından kesilmeye devam eder.
    </div>
  </div>

  <form method="POST" action="/yazici-ayarlari/kaydet">
    @csrf

    {{-- Bağlantı --}}
    <div class="ya-blok">
      <div class="ya-basl">BAĞLANTI</div>
      <div class="ya-kart">
        <label class="rk-lbl" style="margin-top:0">Yazıcı IP adresi</label>
        <input type="text" name="ip" value="{{ $ip }}" placeholder="örn. 192.168.1.50" class="rk-inp" inputmode="numeric">
        <label class="rk-lbl">Port</label>
        <input type="text" name="port" value="{{ $port }}" placeholder="9100" class="rk-inp" inputmode="numeric" style="max-width:120px">
      </div>
    </div>

    {{-- Kağıt Genişliği --}}
    <div class="ya-blok">
      <div class="ya-basl">KAĞIT GENİŞLİĞİ</div>
      <div class="ya-kart">
        <div class="ya-seg">
          <label>
            <input type="radio" name="dar" value="0" {{ !$dar ? 'checked' : '' }}>
            <div class="kutu">80 mm (geniş)</div>
          </label>
          <label>
            <input type="radio" name="dar" value="1" {{ $dar ? 'checked' : '' }}>
            <div class="kutu">58 mm (dar)</div>
          </label>
        </div>
      </div>
    </div>

    {{-- Karakter --}}
    <div class="ya-blok">
      <div class="ya-basl">KARAKTER</div>
      <div class="ya-kart">
        <div class="ya-seg">
          <label>
            <input type="radio" name="turkce" value="1" {{ $turkce ? 'checked' : '' }} @change="turkce = true">
            <div class="kutu">Türkçe (CP857)</div>
          </label>
          <label>
            <input type="radio" name="turkce" value="0" {{ !$turkce ? 'checked' : '' }} @change="turkce = false">
            <div class="kutu">ASCII (sade)</div>
          </label>
        </div>
        <div class="ya-satir" x-show="turkce" x-cloak>
          <div class="ipuc ya-ipuc">Kod sayfası no (Türkçe bozuk çıkarsa değiştir: 13 · 18 · 34…)</div>
          <div class="ya-kod">
            <input type="text" name="kod" value="{{ $kod }}" placeholder="13" class="rk-inp" inputmode="numeric">
          </div>
        </div>
      </div>
    </div>

    {{-- Çekmece --}}
    <div class="ya-blok">
      <div class="ya-basl">ÇEKMECE</div>
      <div class="ya-kart">
        <label class="ya-sw">
          <div>
            <div class="t">Hesap fişi basınca çekmeceyi aç</div>
            <div class="a">Yazıcıya bağlı kasa çekmecesi (RJ11) tetiklenir</div>
          </div>
          <span class="ya-switch">
            <input type="checkbox" name="cekmece" value="1" {{ $cekmece ? 'checked' : '' }}>
            <span class="sl"></span>
          </span>
        </label>
      </div>
    </div>

    {{-- Mutfak Çıktısı --}}
    <div class="ya-blok">
      <div class="ya-basl">MUTFAK ÇIKTISI (SİPARİŞ GÖNDERİLİNCE)</div>
      <div class="ya-kart">
        @php $modlar = ['ekran' => '🖥️ Ekran', 'yazici' => '🧾 Yazıcı', 'ikisi' => '🖥️+🧾 İkisi']; @endphp
        <div class="ya-seg uc">
          @foreach ($modlar as $mk => $mad)
            <label>
              <input type="radio" name="mutfak_mod" value="{{ $mk }}" {{ $mutfakMod === $mk ? 'checked' : '' }} @change="mutfakMod = '{{ $mk }}'">
              <div class="kutu">{{ $mad }}</div>
            </label>
          @endforeach
        </div>
        <div class="ya-ipuc" style="margin-top:8px">
          <span x-show="mutfakMod === 'ekran'">Sipariş sadece Mutfak (KDS) ekranına düşer, fiş basılmaz.</span>
          <span x-show="mutfakMod === 'yazici'" x-cloak>Sipariş gönderilince mutfağa otomatik fiş basılır (KDS ekranında da görünür).</span>
          <span x-show="mutfakMod === 'ikisi'" x-cloak>Hem KDS ekranına düşer hem mutfağa otomatik fiş basılır.</span>
        </div>

        <div x-show="mutfakMod !== 'ekran'" x-cloak>
          <label class="rk-lbl">Mutfak yazıcısı IP (boş = ana yazıcı)</label>
          <input type="text" name="mutfak_ip" value="{{ $mutfakIp }}" placeholder="örn. 192.168.1.51" class="rk-inp" inputmode="numeric">
          <div class="ya-ipuc" style="margin-top:10px">İstasyon yazıcıları (boş = mutfak yazıcısı). Bar/ızgara siparişi KENDİ fişine + KENDİ yazıcısına gider.</div>
          <label class="rk-lbl">🍹 Bar yazıcısı IP</label>
          <input type="text" name="bar_ip" value="{{ $barIp }}" placeholder="örn. 192.168.1.52" class="rk-inp" inputmode="numeric">
          <label class="rk-lbl">🔥 Izgara yazıcısı IP</label>
          <input type="text" name="izgara_ip" value="{{ $izgaraIp }}" placeholder="örn. 192.168.1.53" class="rk-inp" inputmode="numeric">
        </div>
      </div>
    </div>

    <button type="submit" class="rk-btn lg" style="background:var(--mor1)">
      <span class="material-icons-outlined" style="color:#fff">save</span> Kaydet
    </button>
  </form>

</div>
@endsection

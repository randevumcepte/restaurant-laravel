@extends('layout.app')
@section('title', 'Yazıcı Ayarları')
@section('rk_ikon', 'print')
@section('baslik', 'Yazıcı Ayarları')

@push('head')
<style>
/* ===== YAZICI AYARLARI — Flutter yazici_ayarlari_screen birebir ===== */
#ya{max-width:720px}

/* Bilgi kartı (_bilgiKart) */
#ya .ya-bilgi{display:flex;gap:11px;align-items:flex-start;
  background:color-mix(in srgb,var(--mor1) 10%,transparent);
  border:1px solid color-mix(in srgb,var(--mor1) 30%,transparent);
  border-radius:12px;padding:13px;margin-bottom:14px}
#ya .ya-bilgi .material-icons-outlined{color:var(--mor1);font-size:18px;margin-top:1px;flex:none}
#ya .ya-bilgi .m{color:var(--sub2);font-size:12px;line-height:1.35}

/* Flash mesajı */
#ya .ya-flash{display:flex;align-items:center;gap:8px;border-radius:10px;padding:11px 14px;
  font-size:13.5px;font-weight:600;margin-bottom:14px}
#ya .ya-flash.ok{background:color-mix(in srgb,#10B981 12%,transparent);
  border:1px solid color-mix(in srgb,#10B981 35%,transparent);color:#10B981}
#ya .ya-flash.err{background:color-mix(in srgb,#F43F5E 12%,transparent);
  border:1px solid color-mix(in srgb,#F43F5E 35%,transparent);color:#F43F5E}

/* Blok: başlık + kart */
#ya .ya-blok{margin-bottom:14px}
#ya .ya-basl{font-size:12.5px;font-weight:800;color:var(--sub);letter-spacing:.3px;
  margin:0 0 8px 4px}

/* Kart (_kutu) */
#ya .ya-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}

/* Alan (_alan) */
#ya .ya-lbl{font-size:12.5px;font-weight:600;color:var(--sub2);margin-bottom:6px;display:block}
#ya .ya-inp{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px 12px;
  font-size:15px;background:var(--bg);color:var(--ink);outline:none;box-sizing:border-box;
  transition:border-color .12s}
#ya .ya-inp:focus{border-color:var(--mor1)}
#ya .ya-inp::placeholder{color:var(--sub)}
#ya .ya-inp.kisa{max-width:120px}
#ya .ya-inp.kucuk{max-width:64px;text-align:center}
#ya .ya-mt{margin-top:12px}

/* Segmented seçim (_secim) */
#ya .ya-seg{display:flex;gap:10px}
#ya .ya-seg label{flex:1;display:block;cursor:pointer;position:relative}
#ya .ya-seg input[type=radio]{position:absolute;opacity:0;width:0;height:0}
#ya .ya-seg .kutu{text-align:center;padding:12px;border-radius:10px;
  border:1px solid var(--line);background:var(--bg);color:var(--sub2);
  font-size:13px;font-weight:600;transition:.12s;line-height:1.3;
  -webkit-user-select:none;user-select:none}
#ya .ya-seg input[type=radio]:checked + .kutu{background:var(--mor1);border-color:var(--mor1);color:#fff}

/* Switch (SwitchListTile) */
#ya .ya-sw{display:flex;align-items:center;justify-content:space-between;gap:14px}
#ya .ya-sw .ya-sw-text .t{font-size:14px;color:var(--ink)}
#ya .ya-sw .ya-sw-text .a{font-size:11.5px;color:var(--sub);margin-top:3px}
#ya .ya-switch{position:relative;display:inline-block;width:46px;height:26px;flex:none}
#ya .ya-switch input{opacity:0;width:0;height:0;position:absolute}
#ya .ya-switch .sl{position:absolute;inset:0;background:var(--line);border-radius:26px;
  cursor:pointer;transition:.15s}
#ya .ya-switch .sl:before{content:"";position:absolute;height:20px;width:20px;
  left:3px;top:3px;background:#fff;border-radius:50%;transition:.15s;
  box-shadow:0 1px 3px rgba(0,0,0,.25)}
#ya .ya-switch input:checked + .sl{background:var(--mor1)}
#ya .ya-switch input:checked + .sl:before{transform:translateX(20px)}

/* Kod sayfası satırı */
#ya .ya-satir{display:flex;align-items:flex-start;gap:10px;margin-top:12px}
#ya .ya-satir .ipuc{flex:1;font-size:11.5px;color:var(--sub);line-height:1.35}

/* Mutfak açıklama */
#ya .ya-ipuc{font-size:11.5px;color:var(--sub);line-height:1.35;margin-top:8px}

/* Ana kaydet butonu (_anaButon) */
#ya .ya-btn-ana{display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:15px;border-radius:13px;background:var(--mor1);color:#fff;
  font-size:15px;font-weight:700;border:none;cursor:pointer;
  text-decoration:none;box-sizing:border-box;transition:opacity .12s}
#ya .ya-btn-ana:hover{opacity:.88}
#ya .ya-btn-ana .material-icons-outlined{font-size:19px;color:#fff}

/* Test butonları (_testButon) */
#ya .ya-btn-test{display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;padding:14px;border-radius:13px;background:var(--card);
  font-size:14px;font-weight:700;border:1px solid;cursor:default;
  box-sizing:border-box;opacity:.45;margin-bottom:10px;
  -webkit-user-select:none;user-select:none}
#ya .ya-btn-test .material-icons-outlined{font-size:19px}
#ya .ya-test-not{background:color-mix(in srgb,#F59E0B 10%,transparent);
  border:1px solid color-mix(in srgb,#F59E0B 30%,transparent);
  border-radius:10px;padding:10px 12px;margin-bottom:10px;
  font-size:12px;color:var(--sub);line-height:1.4}
#ya .ya-test-not .material-icons-outlined{font-size:16px;vertical-align:-3px;margin-right:4px;color:#F59E0B}
</style>
@endpush

@php
  $ip        = $ayar['ip'] ?? '';
  $port      = $ayar['port'] ?? '9100';
  $dar       = (int) ($ayar['dar'] ?? 0) === 1;
  $turkce    = (int) ($ayar['turkce'] ?? 1) === 1;
  $kod       = $ayar['kod'] ?? '13';
  $cekmece   = (int) ($ayar['cekmece'] ?? 1) === 1;
  $mutfakMod = in_array(($ayar['mutfak_mod'] ?? 'ekran'), ['ekran', 'yazici', 'ikisi'])
                 ? ($ayar['mutfak_mod'] ?? 'ekran') : 'ekran';
  $mutfakIp  = $ayar['mutfak_ip'] ?? '';
  $barIp     = $ayar['bar_ip'] ?? '';
  $izgaraIp  = $ayar['izgara_ip'] ?? '';
@endphp

@section('content')
<div id="ya" x-data="{
  mutfakMod: '{{ $mutfakMod }}',
  turkce: {{ $turkce ? 'true' : 'false' }}
}">

  {{-- Flash --}}
  @if (session('yazici_ok'))
    <div class="ya-flash ok">
      <span class="material-icons-outlined" style="font-size:17px">check_circle</span>
      Ayarlar kaydedildi.
    </div>
  @endif
  @if (session('yazici_hata'))
    <div class="ya-flash err">
      <span class="material-icons-outlined" style="font-size:17px">error_outline</span>
      {{ session('yazici_hata') }}
    </div>
  @endif

  {{-- Bilgi kartı (_bilgiKart) --}}
  <div class="ya-bilgi">
    <span class="material-icons-outlined">info</span>
    <div class="m">
      Ağ (LAN) yazıcısına doğrudan basar; mevcut Kerzz/POS sistemine dokunmaz.
      Yazıcının IP adresini gir, uygulamadaki <strong>Hesap Fişi Testi</strong> ile dene.
      Mali fiş <strong>DEĞİLDİR</strong> — yasal fiş POS cihazından kesilmeye devam eder.
    </div>
  </div>

  <form method="POST" action="/yazici-ayarlari/kaydet">
    @csrf

    {{-- BAĞLANTI --}}
    <div class="ya-blok">
      <div class="ya-basl">BAĞLANTI</div>
      <div class="ya-kart">
        <label class="ya-lbl">Yazıcı IP adresi</label>
        <input type="text" name="ip" value="{{ $ip }}"
          placeholder="örn. 192.168.1.50"
          class="ya-inp" inputmode="numeric">
        <div class="ya-mt">
          <label class="ya-lbl">Port</label>
          <input type="text" name="port" value="{{ $port }}"
            placeholder="9100" class="ya-inp kisa" inputmode="numeric">
        </div>
      </div>
    </div>

    {{-- KAĞIT GENİŞLİĞİ --}}
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

    {{-- KARAKTER --}}
    <div class="ya-blok">
      <div class="ya-basl">KARAKTER</div>
      <div class="ya-kart">
        <div class="ya-seg">
          <label>
            <input type="radio" name="turkce" value="1"
              {{ $turkce ? 'checked' : '' }}
              @change="turkce = true">
            <div class="kutu">Türkçe (CP857)</div>
          </label>
          <label>
            <input type="radio" name="turkce" value="0"
              {{ !$turkce ? 'checked' : '' }}
              @change="turkce = false">
            <div class="kutu">ASCII (sade)</div>
          </label>
        </div>
        {{-- Kod sayfası (turkce seçiliyken görünür) --}}
        <div class="ya-satir" x-show="turkce" x-cloak>
          <div class="ipuc">
            Kod sayfası no (Türkçe bozuk çıkarsa değiştir: 13 · 18 · 34…)
          </div>
          <input type="text" name="kod" value="{{ $kod }}"
            placeholder="13" class="ya-inp kucuk" inputmode="numeric">
        </div>
      </div>
    </div>

    {{-- ÇEKMECE --}}
    <div class="ya-blok">
      <div class="ya-basl">ÇEKMECE</div>
      <div class="ya-kart">
        <label class="ya-sw" style="cursor:pointer">
          <div class="ya-sw-text">
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

    {{-- MUTFAK ÇIKTISI --}}
    <div class="ya-blok">
      <div class="ya-basl">MUTFAK ÇIKTISI (SİPARİŞ GÖNDERİLİNCE)</div>
      <div class="ya-kart">
        <div class="ya-seg">
          <label>
            <input type="radio" name="mutfak_mod" value="ekran"
              {{ $mutfakMod === 'ekran' ? 'checked' : '' }}
              @change="mutfakMod = 'ekran'">
            <div class="kutu">🖥️ Ekran</div>
          </label>
          <label>
            <input type="radio" name="mutfak_mod" value="yazici"
              {{ $mutfakMod === 'yazici' ? 'checked' : '' }}
              @change="mutfakMod = 'yazici'">
            <div class="kutu">🧾 Yazıcı</div>
          </label>
          <label>
            <input type="radio" name="mutfak_mod" value="ikisi"
              {{ $mutfakMod === 'ikisi' ? 'checked' : '' }}
              @change="mutfakMod = 'ikisi'">
            <div class="kutu">🖥️+🧾 İkisi</div>
          </label>
        </div>

        {{-- Açıklama metni --}}
        <div class="ya-ipuc">
          <span x-show="mutfakMod === 'ekran'">Sipariş sadece Mutfak (KDS) ekranına düşer, fiş basılmaz.</span>
          <span x-show="mutfakMod === 'yazici'" x-cloak>Sipariş gönderilince mutfağa otomatik fiş basılır (KDS ekranında da görünür).</span>
          <span x-show="mutfakMod === 'ikisi'" x-cloak>Hem KDS ekranına düşer hem mutfağa otomatik fiş basılır.</span>
        </div>

        {{-- Mutfak + istasyon IP'leri --}}
        <div x-show="mutfakMod !== 'ekran'" x-cloak>
          <div class="ya-mt">
            <label class="ya-lbl">Mutfak yazıcısı IP (boş = ana yazıcı)</label>
            <input type="text" name="mutfak_ip" value="{{ $mutfakIp }}"
              placeholder="örn. 192.168.1.51"
              class="ya-inp" inputmode="numeric">
          </div>
          <div class="ya-ipuc" style="margin-top:10px">
            İstasyon yazıcıları (boş = mutfak yazıcısı). Bar/ızgara siparişi KENDİ fişine + KENDİ yazıcısına gider.
          </div>
          <div class="ya-mt">
            <label class="ya-lbl">🍹 Bar yazıcısı IP</label>
            <input type="text" name="bar_ip" value="{{ $barIp }}"
              placeholder="örn. 192.168.1.52"
              class="ya-inp" inputmode="numeric">
          </div>
          <div class="ya-mt">
            <label class="ya-lbl">🔥 Izgara yazıcısı IP</label>
            <input type="text" name="izgara_ip" value="{{ $izgaraIp }}"
              placeholder="örn. 192.168.1.53"
              class="ya-inp" inputmode="numeric">
          </div>
        </div>
      </div>
    </div>

    {{-- KAYDET butonu (_anaButon) --}}
    <button type="submit" class="ya-btn-ana" style="margin-bottom:22px">
      <span class="material-icons-outlined">save</span>
      Kaydet
    </button>

  </form>

  {{-- TEST bölümü (_kartBaslik + _testButon'lar) --}}
  <div class="ya-blok">
    <div class="ya-basl">TEST</div>

    <div class="ya-test-not">
      <span class="material-icons-outlined">info</span>
      Test baskıları TCP/IP soketi üzerinden direkt yazıcıya gider.
      Web panelinden tetiklenemez — test için <strong>ResteOS uygulamasını</strong> kullanın
      (Yazıcı Ayarları → Hesap Fişi Testi).
    </div>

    {{-- Hesap Fişi Testi --}}
    <div class="ya-btn-test" style="border-color:rgba(16,185,129,.5);color:#10B981">
      <span class="material-icons-outlined" style="color:#10B981">receipt_long</span>
      Hesap Fişi Testi
    </div>

    {{-- Mutfak Fişi Testi --}}
    <div class="ya-btn-test" style="border-color:rgba(124,58,237,.5);color:#7C3AED">
      <span class="material-icons-outlined" style="color:#7C3AED">soup_kitchen</span>
      Mutfak Fişi Testi
    </div>

    {{-- Çekmeceyi Aç --}}
    <div class="ya-btn-test" style="border-color:rgba(245,158,11,.5);color:#F59E0B">
      <span class="material-icons-outlined" style="color:#F59E0B">point_of_sale</span>
      Çekmeceyi Aç
    </div>
  </div>

</div>
@endsection

@extends('layout.app')
@section('title', 'Ödeme Ayarları')
@section('baslik', '💳 Ödeme Ayarları (PayTR / iyzico)')

@push('head')
<style>
#odroot{--line:#e4e4ee;max-width:700px}
#odroot .card{background:#fff;border-radius:16px;padding:22px;box-shadow:0 6px 20px rgba(40,30,80,.06);margin-bottom:18px}
#odroot h2{font-size:15px;margin:0 0 4px;color:#39304f;font-weight:800}
#odroot .alt{color:#8a8a9a;font-size:13px;margin:0 0 16px}
#odroot .flash{background:#e7f8ef;color:#137a4a;padding:10px 14px;border-radius:10px;margin:0 0 16px;font-size:14px;font-weight:600}
#odroot label{display:block;font-size:12.5px;color:#6a6a80;font-weight:700;margin:14px 0 6px}
#odroot input,#odroot select{width:100%;padding:11px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:14px;box-sizing:border-box}
#odroot .kayitli{font-size:11.5px;color:#137a4a;font-weight:700;margin-top:4px}
#odroot .cb{display:flex;gap:8px;align-items:center;background:#f6f7fb;border:1px dashed #c9cce0;border-radius:10px;padding:10px 12px}
#odroot .cb code{font-size:12.5px;color:#39304f;word-break:break-all;flex:1}
#odroot .cpy{background:#eef;color:#4338ca;border:none;border-radius:8px;padding:7px 11px;font-weight:700;cursor:pointer;font-size:12.5px}
#odroot .save{margin-top:18px;padding:13px 24px;background:linear-gradient(135deg,#17171c,#2b2b33);color:#fff;border:none;border-radius:11px;font-size:15px;font-weight:800;cursor:pointer}
#odroot .note{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;color:#9a3412;font-size:12.8px;line-height:1.55}
#odroot .sw{display:flex;align-items:center;gap:10px;margin-top:16px}
#odroot ol{margin:6px 0 0;padding-left:18px}#odroot ol li{margin-bottom:3px}
</style>
@endpush

@section('content')
@php
  $sag = $a->saglayici ?? 'kapali';
  $apiVar = !empty($a->api_key);
  $secVar = !empty($a->secret);
@endphp
<div id="odroot">
  @if (session('ay_ok'))<div class="flash">✓ {{ session('ay_ok') }}</div>@endif

  <div class="note">
    🔐 <b>Her restoran kendi PayTR hesabını kullanır</b> — para doğrudan <b>sizin</b> banka hesabınıza geçer, biz paraya dokunmayız.<br>
    PayTR hesabı yoksa: <b>paytr.com → Üye İşyeri Başvuru</b>. Onay sonrası PayTR panelinden şu 3 bilgiyi alırsınız:
    <ol>
      <li><b>Mağaza No</b> (merchant_id)</li>
      <li><b>Mağaza Parolası</b> (merchant_key)</li>
      <li><b>Mağaza Gizli Anahtarı</b> (merchant_salt)</li>
    </ol>
  </div>

  <form method="post" action="/odeme-ayar/kaydet">
    @csrf
    <div class="card">
      <h2>Sağlayıcı Bağlantısı</h2>
      <p class="alt">Bilgileri girip "Aktif" işaretleyince cüzdan yüklemeleri gerçek karttan çekilir.</p>

      <label>Ödeme Sağlayıcı</label>
      <select name="saglayici">
        <option value="kapali" @selected($sag==='kapali')>Kapalı (test / simülasyon)</option>
        <option value="paytr" @selected($sag==='paytr')>PayTR</option>
        <option value="iyzico" @selected($sag==='iyzico')>iyzico</option>
      </select>

      <label>Mağaza No (merchant_id)</label>
      <input type="text" name="magaza_id" value="{{ $a->magaza_id ?? '' }}" placeholder="Örn: 123456" autocomplete="off">

      <label>Mağaza Parolası (merchant_key)</label>
      <input type="password" name="api_key" placeholder="{{ $apiVar ? '•••••••• (kayıtlı — değiştirmek için yaz)' : 'PayTR mağaza parolası' }}" autocomplete="off">
      @if($apiVar)<div class="kayitli">✓ Kayıtlı</div>@endif

      <label>Mağaza Gizli Anahtarı (merchant_salt)</label>
      <input type="password" name="secret" placeholder="{{ $secVar ? '•••••••• (kayıtlı — değiştirmek için yaz)' : 'PayTR gizli anahtarı' }}" autocomplete="off">
      @if($secVar)<div class="kayitli">✓ Kayıtlı</div>@endif

      <div class="sw">
        <input type="checkbox" name="aktif" value="1" @checked(!empty($a->aktif)) style="width:auto">
        <span style="font-size:13.5px;color:#39304f;font-weight:700">Aktif — gerçek ödeme açık</span>
      </div>
    </div>

    <div class="card">
      <h2>Bildirim (Callback) URL'i</h2>
      <p class="alt">Bu adresi PayTR panelinizde <b>"Bildirim URL"</b> alanına yapıştırın. Ödeme sonucu buraya gelir.</p>
      <div class="cb"><code id="cbu">{{ $callback }}</code><button type="button" class="cpy" onclick="navigator.clipboard.writeText(document.getElementById('cbu').innerText)">Kopyala</button></div>
    </div>

    <button class="save">Kaydet</button>
  </form>

  <div class="note" style="margin-top:18px">
    ℹ️ Not: Anahtarlar kaydedildikten sonra gerçek kart çekimi için <b>PayTR adaptörü</b> (get-token + imzalı callback) de aktif olmalı. Test mağaza bilgisiyle birlikte devreye alınır.
  </div>
</div>
@endsection

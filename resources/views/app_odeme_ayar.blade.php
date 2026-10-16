@extends('layout.app')
@section('title', 'Ödeme Ayarları')
@section('baslik', '💳 Ödeme Ayarları (PayTR / iyzico)')

@push('head')
<style>
#odroot{--line:#e4e4ee;max-width:1060px}
#odroot .wrap{display:grid;grid-template-columns:1fr 350px;gap:20px;align-items:start}
@media(max-width:900px){#odroot .wrap{grid-template-columns:1fr}}
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
#odroot .save{margin-top:6px;padding:13px 26px;background:linear-gradient(135deg,#17171c,#2b2b33);color:#fff;border:none;border-radius:11px;font-size:15px;font-weight:800;cursor:pointer}
#odroot .note{background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:16px;color:#9a3412;font-size:12.8px;line-height:1.55;margin-bottom:18px}
#odroot .sw{display:flex;align-items:center;gap:10px;margin-top:16px}
#odroot ol{margin:6px 0 0;padding-left:18px}#odroot ol li{margin-bottom:3px}
#odroot .adim{display:flex;gap:11px;margin-bottom:12px}
#odroot .adim .n{flex:none;width:24px;height:24px;border-radius:50%;background:#eef;color:#4338ca;font-weight:800;font-size:12.5px;display:flex;align-items:center;justify-content:center}
#odroot .adim .t{font-size:12.8px;color:#51526a;line-height:1.45}
#odroot .sup{background:linear-gradient(135deg,#17171c,#3a2b55);border-radius:16px;padding:22px;color:#fff;box-shadow:0 10px 26px rgba(40,30,80,.18)}
#odroot .sup h3{margin:0 0 6px;font-size:16px;font-weight:800}
#odroot .sup p{margin:0 0 16px;font-size:13px;line-height:1.55;color:#d6d3e6}
#odroot .sup .tel{display:block;font-size:22px;font-weight:900;letter-spacing:.5px;color:#fff;text-decoration:none;margin-bottom:14px}
#odroot .sup .btns{display:flex;gap:10px}
#odroot .sup a.b{flex:1;text-align:center;text-decoration:none;font-weight:800;font-size:13.5px;padding:11px 0;border-radius:11px}
#odroot .sup .ara{background:#fff;color:#1f1733}
#odroot .sup .wa{background:#25D366;color:#063}
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

  <div class="wrap">
    {{-- SOL: Form --}}
    <div>
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
    </div>

    {{-- SAĞ: Bilgi + Destek --}}
    <div>
      <div class="note">
        🔐 <b>Her restoran kendi PayTR hesabını kullanır</b> — para doğrudan <b>sizin</b> banka hesabınıza geçer, biz paraya dokunmayız.<br><br>
        PayTR hesabınız yoksa: <b>paytr.com → Üye İşyeri Başvuru</b>. Onay sonrası PayTR panelinden 3 bilgiyi alırsınız:
        <ol>
          <li><b>Mağaza No</b> (merchant_id)</li>
          <li><b>Mağaza Parolası</b> (merchant_key)</li>
          <li><b>Mağaza Gizli Anahtarı</b> (merchant_salt)</li>
        </ol>
      </div>

      <div class="card">
        <h2 style="margin-bottom:14px">Nasıl çalışır?</h2>
        <div class="adim"><div class="n">1</div><div class="t">PayTR'den aldığınız 3 bilgiyi buraya girer, "Aktif" edersiniz.</div></div>
        <div class="adim"><div class="n">2</div><div class="t">Müşteri cüzdana yükleme yapınca PayTR'nin güvenli kart ekranı açılır.</div></div>
        <div class="adim"><div class="n">3</div><div class="t">Ödeme onaylanınca para hesabınıza geçer, müşterinin cüzdanı anında yüklenir.</div></div>
        <div class="adim"><div class="n">4</div><div class="t">Kasada cüzdanla ödemede ek komisyon yok — kart komisyonunu tek seferde ödersiniz.</div></div>
      </div>

      <div class="sup">
        <h3>Yardıma mı ihtiyacınız var? 💜</h3>
        <p>PayTR bağlantısı, kurulum veya herhangi bir konuda takılırsanız yanınızdayız. Kurulumu birlikte dakikalar içinde tamamlayalım.</p>
        <a class="tel" href="tel:+905412948144">0541 294 81 44</a>
        <div class="btns">
          <a class="b ara" href="tel:+905412948144">📞 Ara</a>
          <a class="b wa" href="https://wa.me/905412948144?text=Merhaba, ResteOS ödeme (PayTR) kurulumu için yardım istiyorum." target="_blank" rel="noopener">WhatsApp</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

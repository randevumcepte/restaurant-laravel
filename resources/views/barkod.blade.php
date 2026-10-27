@extends('layout.app')
@section('rk_ikon', 'qr_code')
@section('baslik', 'Barkod Yönetim')
@section('rk_alt', 'Bir ürüne ya da malzemeye barkod ekle/sil — çoklu barkod destekli (aynı öğenin birden çok barkodu olabilir)')

@push('head')
<style>
/* Flutter barkod_yonetim_screen birebir — tema değişkenleri layout.app'ten gelir */
#brkroot{max-width:900px}
/* CSS değişkenler: light/dark */
#brkroot{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
}
body.koyu #brkroot{--card:#1b2235;--card2:#161d2e;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}

/* ---- üst bölüm: giriş satırı (Flutter Row: TextField + IconButton.filled) ---- */
#brkroot .brk-giri{display:flex;gap:8px;align-items:center;padding:12px 0}
#brkroot .brk-giri input[type=text]{
  flex:1;height:48px;border:none;border-radius:12px;
  background:var(--card2);color:var(--ink);font-size:15px;padding:0 14px;outline:none;
}
#brkroot .brk-giri input[type=text]::placeholder{color:var(--sub)}
#brkroot .brk-ekle-btn{
  width:44px;height:44px;border:none;border-radius:12px;background:var(--mor1);cursor:pointer;
  display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;
}
#brkroot .brk-ekle-btn:disabled{opacity:.5;cursor:default}

/* ---- Flutter ElevatedButton.icon — Kamerayla Barkod Okut ---- */
#brkroot .brk-kamera-btn{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;height:50px;border:none;border-radius:12px;
  background:var(--mor1);color:#fff;font-size:15px;font-weight:700;cursor:pointer;
}
#brkroot .brk-kamera-btn .material-icons-outlined{font-size:20px}

/* ---- Flutter OutlinedButton.icon — İç Barkod Üret ---- */
#brkroot .brk-ic-btn{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;height:46px;border:1.5px solid var(--mor1);border-radius:12px;
  background:transparent;color:var(--mor1);font-size:14px;font-weight:700;cursor:pointer;margin-top:8px;
}
#brkroot .brk-ic-btn:disabled{opacity:.5;cursor:default}
#brkroot .brk-ic-btn .material-icons-outlined{font-size:18px}

/* ---- öğe seçici (tür segmenti + dropdown) ---- */
#brkroot .brk-tur{display:flex;gap:8px;margin:10px 0 6px}
#brkroot .brk-seg{
  flex:1;display:block;text-align:center;padding:10px 8px;
  border:1.5px solid var(--line);border-radius:10px;
  font-size:14px;font-weight:700;color:var(--sub2);background:var(--card);
  cursor:pointer;transition:.15s;
}
#brkroot .brk-seg.akt{background:var(--mor1);border-color:var(--mor1);color:#fff}
#brkroot .brk-sel{
  width:100%;height:44px;border:1.5px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;padding:0 10px;outline:none;
}

/* ---- boş durum (Flutter Center + Text) ---- */
#brkroot .brk-bos{
  text-align:center;color:var(--sub);font-size:14px;
  padding:48px 16px;background:var(--card);border-radius:14px;
  border:1px solid var(--line);line-height:1.8;
}

/* ---- Flutter Container (barkod kartı) ---- */
#brkroot .brk-kart{
  display:flex;align-items:center;gap:12px;
  background:var(--card);border-radius:12px;padding:12px 14px;
  box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:8px;
}
body.koyu #brkroot .brk-kart{box-shadow:0 2px 8px rgba(0,0,0,.25)}
#brkroot .brk-kart .material-icons-outlined.ikon{color:var(--mor1);font-size:26px;flex-shrink:0}
#brkroot .brk-kart .barkod-text{
  flex:1;font-size:15px;font-weight:700;letter-spacing:.5px;
  font-variant-numeric:tabular-nums;color:var(--ink);word-break:break-all;
}
#brkroot .brk-kart .hedef-text{font-size:12px;color:var(--sub);margin-top:2px}
#brkroot .brk-tur-rozet{
  display:inline-flex;align-items:center;padding:2px 10px;border-radius:20px;
  font-size:11.5px;font-weight:700;flex-shrink:0;
}
#brkroot .brk-tur-rozet.urun{color:#2563EB;border:1px solid rgba(37,99,235,.3);background:rgba(37,99,235,.1)}
#brkroot .brk-tur-rozet.malzeme{color:var(--amber);border:1px solid rgba(245,158,11,.3);background:rgba(245,158,11,.1)}

/* ---- Flutter IconButton sil (kirmizi) ---- */
#brkroot .brk-sil-btn{
  background:none;border:none;cursor:pointer;padding:4px;
  color:var(--kirmizi);display:inline-flex;border-radius:8px;
  transition:background .12s;
}
#brkroot .brk-sil-btn:hover{background:rgba(244,63,94,.1)}
#brkroot .brk-sil-btn .material-icons-outlined{font-size:20px}

/* ---- flash mesajları ---- */
#brkroot .brk-flash{
  padding:12px 16px;border-radius:12px;font-size:14px;font-weight:600;margin-bottom:10px;
}
#brkroot .brk-flash.ok{background:rgba(16,185,129,.12);color:#065f46;border:1px solid rgba(16,185,129,.3)}
body.koyu #brkroot .brk-flash.ok{color:#6ee7b7}
#brkroot .brk-flash.err{background:rgba(244,63,94,.1);color:#9f1239;border:1px solid rgba(244,63,94,.25)}
body.koyu #brkroot .brk-flash.err{color:#fda4af}

/* ---- bölüm başlıkları ---- */
#brkroot .brk-bolum{display:flex;align-items:center;gap:8px;margin-bottom:8px}
#brkroot .brk-bolum .cubuk{width:4px;height:20px;border-radius:2px;flex-shrink:0}
#brkroot .brk-bolum .b{font-size:15px;font-weight:700;color:var(--ink)}
#brkroot .brk-bolum .sayi{
  margin-left:auto;font-size:12px;font-weight:700;
  color:var(--mor1);background:rgba(124,58,237,.12);
  border-radius:20px;padding:2px 10px;
}

/* ---- iki sütunlu düzen ---- */
#brkroot .brk-grid{display:grid;grid-template-columns:minmax(300px,360px) 1fr;gap:16px;align-items:start}
@media(max-width:800px){#brkroot .brk-grid{grid-template-columns:1fr}}

/* ---- ekle formu kartı ---- */
#brkroot .brk-form-kart{
  background:var(--card);border-radius:14px;padding:16px;
  border:1px solid var(--line);box-shadow:0 2px 8px rgba(0,0,0,.05);
}
body.koyu #brkroot .brk-form-kart{box-shadow:0 2px 8px rgba(0,0,0,.2)}

/* ---- bilgi metni ---- */
#brkroot .brk-info{font-size:11.5px;color:var(--sub);line-height:1.6;margin-top:10px}

/* ---- ic-barkod mesaj ---- */
#brkroot #brkIcMsg{display:none;margin-top:8px}
</style>
@endpush

@section('content')
@php
  $urunler    = $urunler    ?? [];
  $malzemeler = $malzemeler ?? [];
  $barkodlar  = $barkodlar  ?? [];
@endphp

<div id="brkroot">

  {{-- Flash bildirimler (Flutter SnackBar karşılığı) --}}
  @if (session('ok'))
    <div class="brk-flash ok">
      <span class="material-icons-outlined" style="font-size:16px;vertical-align:-3px;margin-right:6px">check_circle</span>{{ session('ok') }}
    </div>
  @endif
  @if (session('hata'))
    <div class="brk-flash err">
      <span class="material-icons-outlined" style="font-size:16px;vertical-align:-3px;margin-right:6px">error_outline</span>{{ session('hata') }}
    </div>
  @endif

  <div class="brk-grid">

    {{-- ── Sol: Yeni Barkod Ekle (Flutter Column üstü: TextField + kamera btn + ic-uret btn) ── --}}
    <div class="brk-form-kart">
      <div class="brk-bolum">
        <span class="cubuk" style="background:var(--mor1)"></span>
        <span class="b">Yeni Barkod</span>
      </div>

      <form method="POST" action="/barkod/ekle" id="brkForm" style="margin:0" autocomplete="off">
        @csrf

        {{-- Flutter TextField: barkod numarası elle yazma + IconButton.filled ekle --}}
        <div class="brk-giri">
          <input type="text" name="barkod" id="brkKod" placeholder="Barkod (elle yaz)" class="brk-giri" autocomplete="off"
                 style="flex:1;height:48px;border:none;border-radius:12px;background:var(--card2);color:var(--ink);font-size:15px;padding:0 14px;outline:none">
          <button type="submit" class="brk-ekle-btn" title="Ekle" id="brkEkleBtn">
            <span class="material-icons-outlined" style="font-size:20px">add</span>
          </button>
        </div>

        {{-- Flutter Tür segmenti (ElevatedButton tarzı segment seçici) --}}
        <div class="brk-tur" id="brkTurSeg">
          <label style="flex:1;cursor:pointer;margin:0">
            <input type="radio" name="tur" value="urun" style="position:absolute;opacity:0;pointer-events:none" checked onchange="brkTurDegis()">
            <span class="brk-seg akt" data-tur="urun">Ürün</span>
          </label>
          <label style="flex:1;cursor:pointer;margin:0">
            <input type="radio" name="tur" value="malzeme" style="position:absolute;opacity:0;pointer-events:none" onchange="brkTurDegis()">
            <span class="brk-seg" data-tur="malzeme">Malzeme</span>
          </label>
        </div>

        {{-- Bağlanacak öğe dropdown --}}
        <select name="hedef_id_urun" id="brkSelUrun" class="brk-sel" style="margin-bottom:6px">
          <option value="">— Ürün seç —</option>
          @foreach ($urunler as $u)
            <option value="{{ $u->id }}">{{ $u->ad }}</option>
          @endforeach
        </select>
        <select name="hedef_id_malzeme" id="brkSelMal" class="brk-sel" disabled style="display:none;margin-bottom:6px">
          <option value="">— Malzeme seç —</option>
          @foreach ($malzemeler as $m)
            <option value="{{ $m->id }}">{{ $m->ad }}</option>
          @endforeach
        </select>

        {{-- Flutter ElevatedButton.icon — "Kamerayla Barkod Okut"
             Web'de kamera API yok; JS ile BarcodeDetector veya input capture açar --}}
        <button type="button" class="brk-kamera-btn" style="margin-top:10px" onclick="brkKameraOkut()">
          <span class="material-icons-outlined">qr_code_scanner</span>
          <span>Kamerayla Barkod Okut</span>
        </button>

        <p class="brk-info">
          Aynı barkod başka bir öğede tanımlıysa eklenmez.
          Bir öğenin birden çok barkodu olabilir.
        </p>
      </form>

      {{-- Flutter OutlinedButton.icon — "İç Barkod Üret" (barkodsuz ürün için dahili numara)
           Web'de seçilen tur+hedef_id ile /api/barkod/ic-uret POST yapılır, sonra sayfa yenilenir --}}
      <button type="button" class="brk-ic-btn" id="brkIcBtn" onclick="brkIcUret()">
        <span class="material-icons-outlined">tag</span>
        <span>İç Barkod Üret (barkodsuz ürün)</span>
      </button>
      <div id="brkIcMsg" class="brk-flash ok" style="display:none"></div>
    </div>

    {{-- ── Sağ: Tanımlı Barkodlar listesi (Flutter ListView.separated + Container kartlar) ── --}}
    <div>
      <div class="brk-bolum">
        <span class="cubuk" style="background:var(--mor1)"></span>
        <span class="b">Tanımlı Barkodlar</span>
        <span class="sayi">{{ count($barkodlar) }} barkod</span>
      </div>

      @if (!count($barkodlar))
        {{-- Flutter Center: "Henüz barkod yok.\nOkut ya da elle ekle." --}}
        <div class="brk-bos">
          Henüz barkod yok.<br>Okut ya da soldaki formdan ekle.
        </div>
      @else
        {{-- Flutter ListView.separated: her eleman bir Container (brk-kart) --}}
        @foreach ($barkodlar as $b)
          <div class="brk-kart">
            {{-- Flutter Icon(Icons.qr_code_2, color: mor1) --}}
            <span class="material-icons-outlined ikon">qr_code_2</span>

            <div style="flex:1;min-width:0">
              {{-- Flutter Text: barkod kodu, fontSize:15, fontWeight:700, letterSpacing:.5 --}}
              <div class="barkod-text">{{ $b->barkod }}</div>
              {{-- Bağlı öğe adı (web'e özgü ek bilgi, Flutter'da başlık widget.ad) --}}
              @if ($b->hedef_ad ?? null)
                <div class="hedef-text">{{ $b->hedef_ad }}</div>
              @else
                <div class="hedef-text" style="color:var(--kirmizi)">#{{ $b->hedef_id }} (silinmiş)</div>
              @endif
            </div>

            {{-- Tür rozeti --}}
            <span class="brk-tur-rozet {{ $b->tur === 'urun' ? 'urun' : 'malzeme' }}">
              {{ $b->tur === 'urun' ? 'Ürün' : 'Malzeme' }}
            </span>

            {{-- Flutter IconButton(icon: Icons.delete_outline, color: kirmizi) — sil --}}
            <form method="POST" action="/barkod/sil" onsubmit="return rkOnayForm(this, 'Bu barkodu sil?', 'var(--kirmizi)', 'Sil');" style="margin:0;line-height:0;display:inline">
              @csrf
              <input type="hidden" name="id" value="{{ $b->id }}">
              <button type="submit" class="brk-sil-btn" title="Sil">
                <span class="material-icons-outlined">delete_outline</span>
              </button>
            </form>
          </div>
        @endforeach
      @endif
    </div>

  </div>{{-- .brk-grid --}}

  {{-- Kamera modal (BarcodeDetector API — sadece Chrome/Android destekler) --}}
  <div id="brkCamModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.72);display:none;align-items:center;justify-content:center">
    <div style="background:var(--card);border-radius:18px;padding:20px;width:min(96vw,460px);position:relative">
      <div style="font-weight:700;font-size:16px;color:var(--ink);margin-bottom:12px">
        <span class="material-icons-outlined" style="vertical-align:-4px;color:var(--mor1)">qr_code_scanner</span>
        Barkod Okut
      </div>
      <video id="brkCamVid" autoplay playsinline style="width:100%;border-radius:12px;background:#000;aspect-ratio:4/3;object-fit:cover"></video>
      <canvas id="brkCamCanvas" style="display:none"></canvas>
      <p id="brkCamMsg" style="font-size:13px;color:var(--sub);margin-top:10px;min-height:20px"></p>
      <div style="display:flex;gap:10px;margin-top:12px">
        <button onclick="brkCamKapat()" style="flex:1;height:44px;border:1.5px solid var(--line);border-radius:10px;background:transparent;color:var(--sub);font-weight:700;cursor:pointer">Vazgeç</button>
      </div>
    </div>
  </div>

</div>{{-- #brkroot --}}

<script>
/* ── Tür segmenti toggle (Flutter radio onchange) ── */
function brkTurDegis() {
  var tur = document.querySelector('input[name="tur"]:checked').value;
  var su  = document.getElementById('brkSelUrun');
  var sm  = document.getElementById('brkSelMal');
  document.querySelectorAll('#brkTurSeg .brk-seg').forEach(function(s){ s.classList.toggle('akt', s.dataset.tur === tur); });
  if (tur === 'urun') {
    su.style.display=''; su.disabled=false;
    sm.style.display='none'; sm.disabled=true;
  } else {
    sm.style.display=''; sm.disabled=false;
    su.style.display='none'; su.disabled=true;
  }
}

/* ── İç Barkod Üret — /api/barkod/ic-uret (Flutter _icUret) ── */
async function brkIcUret() {
  var btn = document.getElementById('brkIcBtn');
  var msg = document.getElementById('brkIcMsg');
  var tur = document.querySelector('input[name="tur"]:checked').value;
  var hid = tur === 'urun'
    ? parseInt(document.getElementById('brkSelUrun').value  || '0', 10)
    : parseInt(document.getElementById('brkSelMal').value   || '0', 10);
  if (!hid) { brkSnack('Önce bağlanacak öğeyi seç.', true); return; }
  btn.disabled = true;
  try {
    var r = await api('/api/barkod/ic-uret', { tur: tur, hedef_id: hid });
    if (r.ok === 1) {
      msg.style.display = 'block';
      msg.textContent = 'İç barkod üretildi: ' + r.barkod;
      setTimeout(function(){ location.reload(); }, 1200);
    } else {
      brkSnack(r.hata || 'Üretilemedi.', true);
    }
  } catch(e) { brkSnack('Bağlantı hatası', true); }
  finally { btn.disabled = false; }
}

/* ── Kamera Okut (Flutter BarcodeScanner / BarcodeDetector API) ── */
var _brkStream = null;
var _brkDetector = null;
var _brkRaf = null;

async function brkKameraOkut() {
  if (!('BarcodeDetector' in window)) {
    /* BarcodeDetector yok — input[capture] ile native kamera aç */
    var inp = document.createElement('input');
    inp.type='file'; inp.accept='image/*'; inp.capture='environment';
    inp.style.display='none';
    inp.onchange = async function() {
      if (!inp.files || !inp.files[0]) return;
      try {
        var det = new BarcodeDetector({ formats: ['code_128','ean_13','ean_8','code_39','upc_a','upc_e','qr_code','data_matrix','aztec','pdf417','itf','codabar'] });
        var bmp = await createImageBitmap(inp.files[0]);
        var codes = await det.detect(bmp);
        if (codes.length) { brkKodAlindi(codes[0].rawValue); }
        else { brkSnack('Barkod okunamadı. Elle gir.', true); }
      } catch(e) { brkSnack('Bu tarayıcı barkod okumayı desteklemiyor. Elle gir.', true); }
    };
    document.body.appendChild(inp); inp.click(); document.body.removeChild(inp);
    return;
  }
  /* BarcodeDetector var — canlı kamera modalı */
  var modal = document.getElementById('brkCamModal');
  var vid   = document.getElementById('brkCamVid');
  var cv    = document.getElementById('brkCamCanvas');
  var msgEl = document.getElementById('brkCamMsg');
  try {
    _brkStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
    vid.srcObject = _brkStream;
    modal.style.display = 'flex';
    msgEl.textContent = 'Barkodu kameraya gösterin…';
    _brkDetector = new BarcodeDetector({ formats: ['code_128','ean_13','ean_8','code_39','upc_a','upc_e','qr_code','data_matrix','aztec','pdf417','itf','codabar'] });
    function tarama() {
      if (!_brkStream) return;
      _brkRaf = requestAnimationFrame(async function() {
        if (vid.readyState >= 2) {
          try {
            var codes = await _brkDetector.detect(vid);
            if (codes.length) { brkCamKapat(); brkKodAlindi(codes[0].rawValue); return; }
          } catch(e) {}
        }
        tarama();
      });
    }
    tarama();
  } catch(e) {
    brkSnack('Kamera erişimi reddedildi. Elle gir.', true);
    brkCamKapat();
  }
}

function brkCamKapat() {
  if (_brkRaf) { cancelAnimationFrame(_brkRaf); _brkRaf = null; }
  if (_brkStream) { _brkStream.getTracks().forEach(function(t){ t.stop(); }); _brkStream = null; }
  var vid = document.getElementById('brkCamVid');
  if (vid) vid.srcObject = null;
  var modal = document.getElementById('brkCamModal');
  if (modal) modal.style.display = 'none';
}

function brkKodAlindi(kod) {
  var inp = document.getElementById('brkKod');
  if (inp) inp.value = kod;
  brkSnack('Barkod okundu: ' + kod, false);
}

/* ── Mini snackbar (Flutter SnackBar) ── */
var _brkSnackT = null;
function brkSnack(m, hata) {
  var el = document.getElementById('brkSnackbar');
  if (!el) {
    el = document.createElement('div');
    el.id = 'brkSnackbar';
    el.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:9999;'
      + 'padding:12px 20px;border-radius:12px;font-size:14px;font-weight:600;pointer-events:none;'
      + 'transition:opacity .25s;max-width:90vw;text-align:center';
    document.body.appendChild(el);
  }
  el.textContent = m;
  el.style.background = hata ? '#DC2626' : '#16A34A';
  el.style.color = '#fff';
  el.style.opacity = '1';
  if (_brkSnackT) clearTimeout(_brkSnackT);
  _brkSnackT = setTimeout(function(){ el.style.opacity='0'; }, 3000);
}
</script>
@endsection

@extends('layout.app')
@section('title', 'Kaçak Önleme / Ödeme')
@section('rk_ikon', 'security')
@section('baslik', 'Kaçak Önleme / Ödeme')

@push('head')
<style>
/* ── Kaçak Önleme & Ödeme Modu — Flutter OdemeModuScreen web eşdeğeri ─── */
#odm{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;max-width:680px}
body.koyu #odm{--card:#1b2235;--ink:#e2e8f0;--sub:#64748b;--sub2:#94a3b8;--line:#2c3650}

/* liste kabı */
#odm .odm-list{display:flex;flex-direction:column;gap:0;padding:16px 0 40px}

/* bölüm başlığı */
#odm .odm-baslik{font-size:12.5px;font-weight:700;color:var(--sub);padding:18px 2px 8px;text-transform:uppercase;letter-spacing:.4px}

/* mod kartı */
#odm .mod-kart{background:var(--card);border-radius:14px;border:1.5px solid var(--line);padding:13px;margin-bottom:8px;
  display:flex;align-items:center;gap:12px;cursor:pointer;transition:border-color .15s,box-shadow .15s;user-select:none}
#odm .mod-kart.secili{border-color:var(--mavi);border-width:2px;box-shadow:0 0 0 2px rgba(79,70,229,.08)}
#odm .mod-kart .ik{font-size:24px;flex:none}
#odm .mod-kart .icerik{flex:1;min-width:0}
#odm .mod-kart .mod-ad{font-size:15px;font-weight:700;color:var(--ink);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
#odm .mod-kart .mod-ad .kilit{font-size:13px;color:var(--sub2)}
#odm .mod-kart .mod-aciklama{font-size:12px;color:var(--sub2);margin-top:3px;line-height:1.3}
#odm .mod-kart .radyo{color:var(--sub2);font-size:22px;flex:none;transition:color .15s}
#odm .mod-kart.secili .radyo{color:var(--mavi)}

/* uyarı bandı (amber) */
#odm .uyari-bant{background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);border-radius:12px;
  padding:12px;font-size:12.5px;color:var(--ink);line-height:1.35;margin-bottom:8px}

/* radar kartı */
#odm .radar-kart{background:var(--card);border-radius:12px;border:1px solid var(--line);
  padding:0 14px}
#odm .radar-satir{display:flex;align-items:center;gap:10px;padding:14px 0;cursor:pointer;user-select:none}
#odm .radar-satir .radar-yazi{flex:1;min-width:0}
#odm .radar-satir .radar-baslik{font-size:14.5px;color:var(--ink)}
#odm .radar-satir .radar-alt{font-size:12px;color:var(--sub2);margin-top:2px;line-height:1.3}

/* toggle switch */
#odm .tog{position:relative;display:inline-flex;align-items:center;width:42px;height:24px;flex:none}
#odm .tog input{opacity:0;width:0;height:0;position:absolute}
#odm .tog-slider{position:absolute;inset:0;border-radius:34px;background:#cbd5e1;transition:background .2s;cursor:pointer}
#odm .tog-slider:before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;background:#fff;
  top:3px;left:3px;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
#odm .tog input:checked + .tog-slider{background:#22C55E}
#odm .tog input:checked + .tog-slider:before{transform:translateX(18px)}

/* slider satırı */
#odm .slider-satir{display:flex;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--line)}
#odm .slider-satir .sl-label{font-size:13.5px;color:var(--sub);white-space:nowrap}
#odm .slider-satir .sl-deger{font-size:14px;font-weight:700;color:var(--ink);white-space:nowrap;min-width:48px;text-align:right}
#odm .slider-satir input[type=range]{flex:1;accent-color:var(--mavi);cursor:pointer}

/* hint metni */
#odm .hint{font-size:12px;color:var(--sub2);padding:6px 2px 0;line-height:1.35}

/* bölge satırı */
#odm .bolge-kart{background:var(--card);border-radius:12px;border:1px solid var(--line);padding:4px 14px;
  display:flex;align-items:center;gap:10px;margin-top:8px}
#odm .bolge-kart .bolge-ad{flex:1;font-size:14.5px;font-weight:600;color:var(--ink)}
#odm .bolge-kart select{border:none;background:transparent;font-size:13.5px;color:var(--ink);cursor:pointer;
  padding:10px 4px;outline:none;appearance:auto}
body.koyu #odm .bolge-kart select{color:var(--ink);background:var(--card)}
#odm .bolge-kart select option{background:var(--card);color:var(--ink)}

/* kaydet butonu */
#odm .kaydet-btn{width:100%;height:52px;background:var(--mavi);color:#fff;border:none;border-radius:14px;
  font-size:16px;font-weight:700;cursor:pointer;margin-top:18px;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s}
#odm .kaydet-btn:disabled{opacity:.55;cursor:not-allowed}
#odm .kaydet-btn .spin{width:20px;height:20px;border:2.5px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:odmSpin .7s linear infinite}
@keyframes odmSpin{to{transform:rotate(360deg)}}
#odm .yetki-yok{font-size:13.5px;color:var(--sub);padding-top:4px}

/* toast */
#odm-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);
  background:#1e293b;color:#fff;padding:11px 22px;border-radius:12px;font-size:14px;font-weight:600;
  z-index:9999;opacity:0;transition:opacity .25s,transform .25s;pointer-events:none}
#odm-toast.goster{opacity:1;transform:translateX(-50%) translateY(0)}

/* spinner overlay (yükleniyor) */
#odm-yukleniyor{display:flex;align-items:center;justify-content:center;padding:60px 0}
#odm-yukleniyor .spin2{width:34px;height:34px;border:3px solid var(--line);border-top-color:var(--mavi);
  border-radius:50%;animation:odmSpin .7s linear infinite}
</style>
@endpush

@section('content')
@php
  /* Personel token — Bearer gerektiren /api/patron/odeme-modu uçlarına JS'den çağrı için */
  $_odm_p = null;
  try { if (function_exists('_posPersonel')) $_odm_p = _posPersonel(); } catch (\Throwable $e) {}
  $odmToken = $_odm_p ? ($_odm_p->api_token ?? '') : '';
  $odmDuz   = $_odm_p && in_array($_odm_p->rol ?? '', ['sahip', 'mudur']);
@endphp

<div id="odm">
  <div id="odm-yukleniyor"><div class="spin2"></div></div>
  <div id="odm-icerik" style="display:none" class="odm-list">

    <!-- ── Ödeme Modu ── -->
    <div class="odm-baslik">Ödeme Modu</div>

    <!-- post_pay -->
    <div class="mod-kart" id="mk-post_pay" onclick="odmModSec('post_pay')">
      <span class="ik">🍽️</span>
      <div class="icerik">
        <div class="mod-ad">Klasik (servis sonrası öde)</div>
        <div class="mod-aciklama">Müşteri yer, sonra öder. Aktif koruma: aşağıdaki Kaçak Radarı.</div>
      </div>
      <span class="material-icons-outlined radyo" id="radyo-post_pay">radio_button_off</span>
    </div>

    <!-- on_odeme -->
    <div class="mod-kart" id="mk-on_odeme" onclick="odmModSec('on_odeme')">
      <span class="ik">💳</span>
      <div class="icerik">
        <div class="mod-ad">Ön Ödemeli <span class="kilit">🔒</span></div>
        <div class="mod-aciklama">Sipariş önce ödenir, sonra mutfağa düşer. Kaçak = sıfır.</div>
      </div>
      <span class="material-icons-outlined radyo" id="radyo-on_odeme">radio_button_off</span>
    </div>

    <!-- acik_kart -->
    <div class="mod-kart" id="mk-acik_kart" onclick="odmModSec('acik_kart')">
      <span class="ik">🔐</span>
      <div class="icerik">
        <div class="mod-ad">Açık Hesap + Kart <span class="kilit">🔒</span></div>
        <div class="mod-aciklama">QR'da kart bağlanır, çıkışta otomatik çekilir.</div>
      </div>
      <span class="material-icons-outlined radyo" id="radyo-acik_kart">radio_button_off</span>
    </div>

    <!-- uyarı bandı -->
    <div class="uyari-bant">
      🔒 "Ön Ödemeli" ve "Açık Hesap + Kart", canlı ödeme (Iyzico/PayTR) devreye girince tam aktifleşir. Şu an her modda aktif koruma: Kaçak Radarı.
    </div>

    <!-- ── Kaçak Radarı ── -->
    <div class="odm-baslik">Kaçak Radarı</div>

    <div class="radar-kart">
      <div class="radar-satir" onclick="odmToggle()">
        <div class="radar-yazi">
          <div class="radar-baslik">Uzun süredir açık + ödenmemiş masaları uyar</div>
          <div class="radar-alt">Garson ekranında kırmızı "ödemeden ayrılma riski" kartı gösterilir.</div>
        </div>
        <label class="tog" onclick="event.stopPropagation()">
          <input type="checkbox" id="kacak-toggl" onchange="odmToggleChange()">
          <span class="tog-slider"></span>
        </label>
      </div>

      <div id="slider-wrap" style="display:none">
        <div class="slider-satir">
          <span class="sl-label">Uyarı süresi</span>
          <input type="range" id="kacak-slider" min="15" max="240" step="15" value="90" oninput="odmSlider(this.value)">
          <span class="sl-deger" id="kacak-dk-label">90 dk</span>
        </div>
      </div>
    </div>

    <div class="hint">
      Örn: 90 dk'dan uzun süredir açık ve ödenmemiş bir masa varsa garson uyarılır. Süreyi restoranın ortalama oturma süresine göre ayarla.
    </div>

    <!-- ── Bölge Bazlı ── -->
    <div id="bolge-wrap" style="display:none">
      <div class="odm-baslik">Bölge Bazlı (opsiyonel)</div>
      <div class="hint" style="padding-bottom:4px">
        Her bölge için ayrı mod seçebilirsin (ör. Bahçe = Ön Ödemeli, Salon = Klasik). "Varsayılan" = yukarıdaki restoran modu.
      </div>
      <div id="bolge-listesi"></div>
    </div>

    <!-- ── Kaydet / Yetki ── -->
    @if($odmDuz)
      <button class="kaydet-btn" id="kaydet-btn" onclick="odmKaydet()">
        <span id="kaydet-label">Kaydet</span>
      </button>
    @else
      <div class="yetki-yok">Bu ayarları yalnızca işletme sahibi/müdürü değiştirebilir.</div>
    @endif

  </div><!-- /odm-icerik -->
</div><!-- /odm -->

<div id="odm-toast"></div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// Kaçak Önleme / Ödeme Modu — Flutter OdemeModuScreen web eşdeğeri
// ─────────────────────────────────────────────────────────────────────────────
const ODM_TOKEN    = '{{ $odmToken }}';
const ODM_DUZ      = {{ $odmDuz ? 'true' : 'false' }};
const ODM_MOD_AD   = { '': 'Varsayılan', post_pay: 'Klasik', on_odeme: 'Ön Ödemeli', acik_kart: 'Açık Hesap+Kart' };

let odmDurum = { mod: 'post_pay', kacak_aktif: true, kacak_dk: 90, bolgeler: [] };

/* ── api çağrısı (Bearer) ── */
async function odmApi(url, data) {
    const opts = {
        method: data ? 'POST' : 'GET',
        headers: {
            'Authorization': 'Bearer ' + ODM_TOKEN,
            'Accept': 'application/json',
            'X-CSRF-TOKEN': window.csrf,
        }
    };
    if (data) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    }
    const r = await fetch(url, opts);
    return r.json();
}

/* ── yükle ── */
async function odmYukle() {
    try {
        const res = await odmApi('/api/patron/odeme-modu');
        if (res && res.ok) {
            odmDurum.mod         = res.mod        || 'post_pay';
            odmDurum.kacak_aktif = res.kacak_aktif !== false;
            odmDurum.kacak_dk    = parseInt(res.kacak_dk) || 90;
            odmDurum.bolgeler    = Array.isArray(res.bolgeler) ? res.bolgeler : [];
        }
    } catch (e) { /* sessiz */ }
    odmRender();
    document.getElementById('odm-yukleniyor').style.display = 'none';
    document.getElementById('odm-icerik').style.display = 'flex';
    document.getElementById('odm-icerik').style.flexDirection = 'column';
}

/* ── render (state → DOM) ── */
function odmRender() {
    // mod kartları
    ['post_pay', 'on_odeme', 'acik_kart'].forEach(function(m) {
        const kart  = document.getElementById('mk-' + m);
        const radyo = document.getElementById('radyo-' + m);
        if (odmDurum.mod === m) {
            kart.classList.add('secili');
            radyo.textContent = 'radio_button_checked';
        } else {
            kart.classList.remove('secili');
            radyo.textContent = 'radio_button_off';
        }
        // devre dışı görünüm (yetki yoksa pointer yok)
        kart.style.cursor = ODM_DUZ ? 'pointer' : 'default';
    });

    // toggle
    const togEl = document.getElementById('kacak-toggl');
    togEl.checked = odmDurum.kacak_aktif;
    if (!ODM_DUZ) togEl.disabled = true;

    // slider
    const sliderWrap = document.getElementById('slider-wrap');
    sliderWrap.style.display = odmDurum.kacak_aktif ? 'block' : 'none';
    const sliderEl = document.getElementById('kacak-slider');
    sliderEl.value = Math.min(240, Math.max(15, odmDurum.kacak_dk));
    document.getElementById('kacak-dk-label').textContent = odmDurum.kacak_dk + ' dk';
    if (!ODM_DUZ) sliderEl.disabled = true;

    // bölgeler
    const bWrap = document.getElementById('bolge-wrap');
    if (odmDurum.bolgeler.length > 0) {
        bWrap.style.display = 'block';
        odmBolgeleriRender();
    } else {
        bWrap.style.display = 'none';
    }
}

/* ── bölge listesini çiz ── */
function odmBolgeleriRender() {
    const list = document.getElementById('bolge-listesi');
    list.innerHTML = '';
    odmDurum.bolgeler.forEach(function(b, idx) {
        const div = document.createElement('div');
        div.className = 'bolge-kart';

        const adEl = document.createElement('span');
        adEl.className = 'bolge-ad';
        adEl.textContent = b.ad || '';

        const sel = document.createElement('select');
        sel.disabled = !ODM_DUZ;
        Object.keys(ODM_MOD_AD).forEach(function(k) {
            const opt = document.createElement('option');
            opt.value = k;
            opt.textContent = ODM_MOD_AD[k];
            if ((b.mod || '') === k) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.addEventListener('change', function() {
            odmDurum.bolgeler[idx].mod = sel.value;
        });

        div.appendChild(adEl);
        div.appendChild(sel);
        list.appendChild(div);
    });
}

/* ── mod seç ── */
function odmModSec(m) {
    if (!ODM_DUZ) return;
    odmDurum.mod = m;
    odmRender();
}

/* ── toggle (tıklama ile) ── */
function odmToggle() {
    if (!ODM_DUZ) return;
    const togEl = document.getElementById('kacak-toggl');
    togEl.checked = !togEl.checked;
    odmDurum.kacak_aktif = togEl.checked;
    odmRender();
}

/* ── toggle (checkbox onchange) ── */
function odmToggleChange() {
    odmDurum.kacak_aktif = document.getElementById('kacak-toggl').checked;
    odmRender();
}

/* ── slider ── */
function odmSlider(val) {
    odmDurum.kacak_dk = parseInt(val);
    document.getElementById('kacak-dk-label').textContent = odmDurum.kacak_dk + ' dk';
}

/* ── kaydet ── */
async function odmKaydet() {
    if (!ODM_DUZ) return;
    const btn = document.getElementById('kaydet-btn');
    const lbl = document.getElementById('kaydet-label');
    btn.disabled = true;
    lbl.innerHTML = '<span class="spin"></span>';
    try {
        const payload = {
            mod: odmDurum.mod,
            kacak_aktif: odmDurum.kacak_aktif ? '1' : '0',
            kacak_dk: odmDurum.kacak_dk,
            bolgeler: JSON.stringify(odmDurum.bolgeler.map(function(b) {
                return { id: b.id, mod: b.mod || '' };
            })),
        };
        const res = await odmApi('/api/patron/odeme-modu-kaydet', payload);
        if (res && res.ok) {
            odmToast('Kaydedildi ✓');
        } else {
            odmToast('Kaydedilemedi');
        }
    } catch (e) {
        odmToast('Kaydedilemedi');
    } finally {
        btn.disabled = false;
        lbl.textContent = 'Kaydet';
    }
}

/* ── toast ── */
let _odmToastTimer = null;
function odmToast(msg) {
    const el = document.getElementById('odm-toast');
    el.textContent = msg;
    el.classList.add('goster');
    if (_odmToastTimer) clearTimeout(_odmToastTimer);
    _odmToastTimer = setTimeout(function() { el.classList.remove('goster'); }, 2500);
}

/* ── başlat ── */
odmYukle();
</script>
@endsection

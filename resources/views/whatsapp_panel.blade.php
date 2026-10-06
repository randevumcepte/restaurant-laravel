@extends('layout.app')
@section('title', 'WhatsApp Yönetimi')
@section('baslik', '🟢 WhatsApp Yönetimi')

@section('content')
<style>
/* ===== Bağlantı ===== */
.wa-card { background:#fff; border-radius:14px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,.06); border:1px solid #eef1f6; }
.wa-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
@media (max-width:900px){ .wa-grid{ grid-template-columns:1fr; } }
.wa-qr { text-align:center; }
.wa-qr img { width:260px; height:260px; max-width:100%; border:1px solid #eee; padding:8px; background:#fff; border-radius:10px; }
.wa-status { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:999px; font-weight:600; font-size:14px; }
.wa-status .dot { width:10px; height:10px; border-radius:50%; background:#aaa; }
.wa-status.connected{ background:#e8f7ee; color:#1a7f3e; } .wa-status.connected .dot{ background:#1a7f3e; }
.wa-status.qr-pending{ background:#fff6e5; color:#b67a00; } .wa-status.qr-pending .dot{ background:#b67a00; }
.wa-status.disconnected,.wa-status.connecting{ background:#eef1f6; color:#444; } .wa-status.connecting .dot{ background:#666; }
.wa-info { color:#555; line-height:1.6; font-size:14px; } .wa-info ul { margin:8px 0; padding-left:18px; }
.btn-wa { background:#25D366; color:#fff; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; }
.btn-wa:hover{ background:#1ebe57; } .btn-wa-danger{ background:#dc3545; } .btn-wa-danger:hover{ background:#c82333; }
.wa-meta { color:#666; font-size:13px; margin-top:10px; } .wa-meta b { color:#222; }
.wa-link-label { display:block; font-size:12.5px; font-weight:600; color:#333; margin-bottom:4px; }
.wa-link-input { width:100%; padding:9px 11px; border:1px solid #ced4da; border-radius:6px; font-size:13px; box-sizing:border-box; }
.wa-link-input:focus { border-color:#25D366; outline:none; box-shadow:0 0 0 3px rgba(37,211,102,.12); }
.wa-link-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
/* ===== Kontör / Paketler ===== */
.wkp-free-banner { display:flex; align-items:center; gap:16px; background:linear-gradient(135deg,#e7f8ef 0%,#d6f3e3 100%); border:1.5px solid #b8ebcf; border-radius:18px; padding:16px 20px; margin:26px 0; box-shadow:0 6px 20px rgba(37,211,102,.10); }
.wkp-free-ic { font-size:30px; line-height:1; } .wkp-free-t1 { font-size:16px; font-weight:800; color:#12805a; } .wkp-free-t2 { font-size:13px; color:#3f6b57; margin-top:3px; }
.wkp-free-pill { margin-left:auto; background:#12805a; color:#fff; font-weight:700; font-size:12px; padding:7px 14px; border-radius:20px; white-space:nowrap; }
.wkp-header { text-align:center; margin:8px 0 18px; } .wkp-header h2 { font-size:26px; font-weight:800; color:#111827; margin:0 0 6px; } .wkp-header p { color:#6c757d; font-size:14px; max-width:620px; margin:0 auto; }
.wkp-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; } @media (max-width:980px){ .wkp-grid{ grid-template-columns:1fr; } }
.wkp-card { position:relative; background:#fff; border:1.5px solid #eceff3; border-radius:18px; padding:24px 20px 20px; text-align:center; transition:all .18s; display:flex; flex-direction:column; }
.wkp-card:hover { transform:translateY(-4px); box-shadow:0 16px 34px rgba(17,24,39,.10); border-color:#c9efd9; }
.wkp-card.best { border-color:#25D366; box-shadow:0 10px 30px rgba(37,211,102,.18); }
.wkp-tag { position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:linear-gradient(135deg,#25D366,#12b455); color:#fff; font-size:11px; font-weight:800; letter-spacing:.4px; padding:5px 14px; border-radius:20px; white-space:nowrap; box-shadow:0 4px 12px rgba(37,211,102,.4); }
.wkp-adet { font-size:26px; font-weight:800; color:#111827; } .wkp-adet span { font-size:14px; font-weight:600; color:#8a94a6; }
.wkp-birim { font-size:12.5px; color:#8a94a6; margin-top:2px; }
.wkp-fiyat { font-size:34px; font-weight:800; color:#12805a; margin:16px 0 2px; line-height:1; } .wkp-fiyat small { font-size:16px; font-weight:600; color:#8a94a6; }
.wkp-tasarruf { display:inline-block; margin:8px auto 0; min-height:24px; font-size:12.5px; font-weight:700; color:#12805a; background:#e7f8ef; border-radius:20px; padding:4px 12px; } .wkp-tasarruf.bos { background:transparent; color:transparent; }
.wkp-btn2 { margin-top:18px; width:100%; padding:12px; border:0; border-radius:12px; font-weight:700; font-size:14px; cursor:pointer; background:#f3f4f6; color:#374151; }
.wkp-btn2:hover { background:#e5e7eb; } .wkp-card.best .wkp-btn2 { background:linear-gradient(135deg,#25D366,#12b455); color:#fff; box-shadow:0 8px 18px rgba(37,211,102,.32); }
/* ===== İstatistik ===== */
.wsi-tabs { display:flex; gap:6px; margin:28px 0 16px; border-bottom:2px solid #e3e8f0; flex-wrap:wrap; }
.wsi-tab { padding:10px 18px; cursor:pointer; border-radius:6px 6px 0 0; font-weight:600; color:#666; background:#f7f9fc; border:1px solid #e3e8f0; border-bottom:none; }
.wsi-tab.active { background:#25D366; color:#fff; border-color:#25D366; }
.wsi-section { display:none; } .wsi-section.active { display:block; }
.wsi-stat-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:18px; }
.wsi-stat-card { background:#fff; border-radius:10px; padding:14px; box-shadow:0 1px 4px rgba(0,0,0,.05); border-left:4px solid #25D366; }
.wsi-stat-card.info{ border-left-color:#0099ff; } .wsi-stat-card.warn{ border-left-color:#f0ad4e; }
.wsi-stat-label { color:#777; font-size:12px; margin-bottom:4px; } .wsi-stat-value { font-size:24px; font-weight:800; color:#222; } .wsi-stat-sub { color:#999; font-size:11px; margin-top:3px; }
.wsi-table { width:100%; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.05); border-collapse:collapse; font-size:13px; }
.wsi-table th,.wsi-table td { padding:9px 11px; text-align:left; border-bottom:1px solid #f0f3f7; } .wsi-table th { background:#f7f9fc; font-weight:600; color:#333; font-size:11px; text-transform:uppercase; }
.wsi-badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; }
.wsi-badge.success{ background:#d4edda; color:#155724; } .wsi-badge.fail{ background:#f8d7da; color:#721c24; } .wsi-badge.fallback{ background:#fff3cd; color:#856404; } .wsi-badge.queued{ background:#cce5ff; color:#004085; }
.wsi-filter { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; align-items:flex-end; } .wsi-filter input,.wsi-filter select{ padding:7px 10px; border:1px solid #ced4da; border-radius:5px; font-size:13px; } .wsi-filter label{ display:block; font-size:11px; color:#666; margin-bottom:3px; font-weight:600; }
.wsi-btn { padding:7px 14px; background:#25D366; color:#fff; border:none; border-radius:5px; cursor:pointer; font-size:13px; font-weight:600; } .wsi-btn.secondary{ background:#6c757d; }
.wsi-pag { display:flex; gap:6px; justify-content:center; margin-top:14px; align-items:center; } .wsi-pag button{ padding:6px 12px; border:1px solid #dee2e6; background:#fff; border-radius:4px; cursor:pointer; } .wsi-pag button:disabled{ opacity:.4; cursor:not-allowed; }
.wsi-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; align-items:center; justify-content:center; } .wsi-modal.show{ display:flex; }
.wsi-modal-content { background:#fff; border-radius:12px; padding:22px; max-width:520px; width:92%; max-height:90vh; overflow:auto; }
.wsi-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; } .wsi-modal-close{ cursor:pointer; font-size:22px; color:#999; }
</style>

<input type="hidden" id="sube" value="{{ $subeId }}">

<!-- Bağlantı -->
<div class="wa-card">
  <div class="wa-grid">
    <div class="wa-qr">
      <h3 style="margin-bottom:14px">Bağlantı Durumu
        <span id="durumBadge" class="wa-status connecting"><span class="dot"></span><span id="durumYazi">Yükleniyor…</span></span>
      </h3>
      <div id="qrWrap" style="display:none">
        <p style="color:#555;margin-bottom:10px">Telefonunuzdan <b>WhatsApp &gt; Ayarlar &gt; Bağlı Cihazlar &gt; Cihaz Bağla</b> ile QR'ı okutun.</p>
        <div id="qrImgBox"></div>
        <p class="wa-meta">QR 30-60 sn geçerlidir, otomatik yenilenir.</p>
      </div>
      <div id="connWrap" style="display:none">
        <div style="font-size:48px;margin:18px 0">✅</div>
        <p>WhatsApp bağlı numara: <b id="baglıNo">-</b></p>
      </div>
      <div id="offWrap" style="display:none">
        <div style="font-size:48px;margin:18px 0">📴</div>
        <p>WhatsApp oturumu kapalı. Başlatmak için butona tıklayın.</p>
      </div>
      <div style="margin-top:16px; display:flex; gap:12px; justify-content:center; flex-wrap:wrap">
        <button class="btn-wa" id="baglanBtn" onclick="baglan()">WhatsApp'ı Bağla</button>
        <button class="btn-wa btn-wa-danger" id="cikisBtn" style="display:none" onclick="cikis()">Oturumu Kapat</button>
      </div>
    </div>
    <div class="wa-info">
      <h3>Nasıl Çalışır?</h3>
      <ul>
        <li>WhatsApp bağlandıktan sonra sipariş/rezervasyon bildirimleri <b>kendi WhatsApp numaranız</b> üzerinden iletilir.</li>
        <li>Müşterinin WhatsApp'ı yoksa mesaj <b>otomatik SMS</b>'e düşebilir — bildirim kaybolmaz.</li>
        <li>Mesajlar müşterilere <b>doğal aralıklarla, kişiselleştirilmiş</b> gider.</li>
        <li><b>İlk hafta hazırlık:</b> numaranız stabil çalışsın diye ilk günler günlük gönderim kademeli artar.</li>
        <li>Bağlantı koparsa <b>panel size haber verir</b>, bildirimler SMS'e geçebilir.</li>
        <li><b>İpucu:</b> en az 2 haftadır kullanılan, WhatsApp Business yüklü bir numara en sağlıklı sonucu verir.</li>
      </ul>
      <div class="wa-meta"><div>Günlük limit: <b>200</b></div></div>
    </div>
  </div>
</div>

<!-- İşletme Bağlantıları -->
<div class="wa-card" style="margin-top:18px">
  <h3 style="margin-bottom:8px">🔗 İşletme Bağlantıları</h3>
  <p style="color:#555;margin-bottom:14px;font-size:13.5px;line-height:1.5">Buraya girdiğiniz bağlantıları, müşteriye WhatsApp mesajı yazarken <b>tek tıkla</b> ekleyebilirsiniz. <b>Buton başlığını siz belirlersiniz.</b> Boş bıraktıklarınız görünmez.</p>
  <div style="display:grid; gap:16px; max-width:720px">
    <div>
      <label class="wa-link-label">📍 Konum (Google Maps → Paylaş → Bağlantıyı kopyala)</label>
      <input type="url" id="bgKonum" class="wa-link-input" value="{{ $bg['konum'] }}" placeholder="https://maps.app.goo.gl/...">
    </div>
    <div class="wa-link-row">
      <div style="flex:0 0 190px; min-width:150px"><label class="wa-link-label">Buton Başlığı</label><input id="bgIgBaslik" class="wa-link-input" maxlength="60" value="{{ $bg['igBaslik'] }}"></div>
      <div style="flex:1; min-width:230px"><label class="wa-link-label">Bağlantı</label><input type="url" id="bgIgLink" class="wa-link-input" value="{{ $bg['igLink'] }}" placeholder="https://instagram.com/kullaniciadi"></div>
    </div>
    <div class="wa-link-row">
      <div style="flex:0 0 190px; min-width:150px"><label class="wa-link-label">Buton Başlığı</label><input id="bgWebBaslik" class="wa-link-input" maxlength="60" value="{{ $bg['webBaslik'] }}"></div>
      <div style="flex:1; min-width:230px"><label class="wa-link-label">Bağlantı</label><input type="url" id="bgWebLink" class="wa-link-input" value="{{ $bg['webLink'] }}" placeholder="https://..."></div>
    </div>
  </div>
  <div style="margin-top:14px; display:flex; gap:12px; align-items:center; flex-wrap:wrap">
    <button class="btn-wa" onclick="baglantiKaydet()">Bağlantıları Kaydet</button>
    <span id="bgStatus" style="font-size:13px; display:none"></span>
  </div>
</div>

<!-- Kontör / Paketler -->
<div class="wa-card" style="margin-top:18px">
  <div class="wkp-free-banner">
    <div class="wkp-free-ic">💬</div>
    <div><div class="wkp-free-t1" id="kontorT1">WhatsApp Kontör</div><div class="wkp-free-t2" id="kontorT2">yükleniyor…</div></div>
    <div class="wkp-free-pill" id="kontorPill">— KONTÖR</div>
  </div>
  <div class="wkp-header">
    <h2>WhatsApp Kontör Paketleri</h2>
    <p>1 mesaj = 1 kontör. Sipariş/rezervasyon bildirimi ve manuel mesajlar kontörden düşer. Çok alırsanız tanesi daha ucuza gelir.</p>
  </div>
  <div class="wkp-grid">
    <div class="wkp-card"><div class="wkp-adet">10.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,285 TL</div><div class="wkp-fiyat">2.850 <small>TL</small></div><div class="wkp-tasarruf bos">—</div><button class="wkp-btn2" onclick="talepAc('kontor_10000')">Kontör Al</button></div>
    <div class="wkp-card"><div class="wkp-adet">20.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,265 TL</div><div class="wkp-fiyat">5.300 <small>TL</small></div><div class="wkp-tasarruf">400 TL avantaj</div><button class="wkp-btn2" onclick="talepAc('kontor_20000')">Kontör Al</button></div>
    <div class="wkp-card"><div class="wkp-adet">40.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,245 TL</div><div class="wkp-fiyat">9.800 <small>TL</small></div><div class="wkp-tasarruf">1.600 TL avantaj</div><button class="wkp-btn2" onclick="talepAc('kontor_40000')">Kontör Al</button></div>
    <div class="wkp-card"><div class="wkp-adet">60.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,230 TL</div><div class="wkp-fiyat">13.800 <small>TL</small></div><div class="wkp-tasarruf">3.300 TL avantaj</div><button class="wkp-btn2" onclick="talepAc('kontor_60000')">Kontör Al</button></div>
    <div class="wkp-card"><div class="wkp-adet">80.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,213 TL</div><div class="wkp-fiyat">17.000 <small>TL</small></div><div class="wkp-tasarruf">5.800 TL avantaj</div><button class="wkp-btn2" onclick="talepAc('kontor_80000')">Kontör Al</button></div>
    <div class="wkp-card best"><div class="wkp-tag">⭐ EN AVANTAJLI · %30</div><div class="wkp-adet">100.000 <span>kontör</span></div><div class="wkp-birim">tanesi 0,200 TL</div><div class="wkp-fiyat">20.000 <small>TL</small></div><div class="wkp-tasarruf">8.500 TL avantaj</div><button class="wkp-btn2" onclick="talepAc('kontor_100000')">Kontör Al</button></div>
  </div>
  <p style="text-align:center;color:#9ca3af;font-size:12.5px;margin-top:18px">Ödeme sonrası kontörünüz manuel olarak yüklenir. "Kontör Al" ile talebinizi bırakın, en kısa sürede sizinle iletişime geçelim.</p>
</div>

<!-- Talep modal -->
<div class="wsi-modal" id="talepModal">
  <div class="wsi-modal-content" style="max-width:460px">
    <div class="wsi-modal-head"><h4 style="margin:0" id="talepBaslik">Kontör Talebi</h4><span class="wsi-modal-close" onclick="document.getElementById('talepModal').classList.remove('show')">×</span></div>
    <p style="color:#6c757d;font-size:14px;line-height:1.5">Bu paket için talebinizi bırakıyorsunuz. Yetkilimize bildirim gider ve ödeme + kontör yükleme için sizinle iletişime geçilir.</p>
    <label style="font-size:13px;color:#444;font-weight:600;margin-top:10px;display:block">İletişim / Not (opsiyonel)</label>
    <input id="talepIletisim" style="width:100%;padding:10px;border:1px solid #ced4da;border-radius:6px;margin-top:6px;font-size:14px" placeholder="örn. bugün ödemek istiyorum / 0555 123 45 67">
    <button class="btn-wa" style="margin-top:16px" onclick="talepGonder()">Talep Gönder</button>
    <div id="talepSonuc" style="margin-top:10px;font-size:13px"></div>
  </div>
</div>

<!-- İstatistik sekmeleri -->
<div class="wsi-tabs">
  <div class="wsi-tab active" data-wsi="ozet">📊 İstatistik</div>
  <div class="wsi-tab" data-wsi="loglar">📨 Mesajlarım</div>
  <div class="wsi-tab" data-wsi="aliciler">👥 Alıcılarım</div>
</div>

<div class="wsi-section active" id="sec-ozet">
  <div class="wsi-stat-grid" id="ozetGrid"><div class="wsi-stat-card"><div class="wsi-stat-label">Yükleniyor…</div><div class="wsi-stat-value">—</div></div></div>
  <div style="background:#fff;border-radius:10px;padding:18px;box-shadow:0 1px 4px rgba(0,0,0,.05)"><h4 style="margin-top:0">Son 30 Gün — Günlük Mesaj Hacmi</h4><canvas id="wsiChart" style="max-height:280px"></canvas></div>
</div>

<div class="wsi-section" id="sec-loglar">
  <div class="wsi-filter">
    <div><label>Durum</label><select id="fDurum"><option value="">Tümü</option><option value="1">Gönderildi</option><option value="2">Başarısız</option><option value="3">SMS'e Düştü</option></select></div>
    <div><label>Telefon</label><input id="fTel" placeholder="905..."></div>
    <div><label>Başlangıç</label><input type="date" id="fBas"></div>
    <div><label>Bitiş</label><input type="date" id="fBit"></div>
    <div><label>Mesajda Ara</label><input id="fArama" placeholder="..."></div>
    <div><button class="wsi-btn" onclick="logYukle(1)">Filtrele</button></div>
    <div><button class="wsi-btn secondary" onclick="logSifirla()">Sıfırla</button></div>
  </div>
  <div style="overflow-x:auto"><table class="wsi-table" id="logTbl"><thead><tr><th>Tarih</th><th>Telefon</th><th>Durum</th><th>Mesaj</th><th>Hata</th></tr></thead><tbody><tr><td colspan="5">Yükleniyor…</td></tr></tbody></table></div>
  <div class="wsi-pag" id="logPag"></div>
</div>

<div class="wsi-section" id="sec-aliciler">
  <div style="overflow-x:auto"><table class="wsi-table" id="aliciTbl"><thead><tr><th>Telefon</th><th>Toplam</th><th>Başarılı</th><th>İlk</th><th>Son</th></tr></thead><tbody><tr><td colspan="5">Yükleniyor…</td></tr></tbody></table></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const $ = s => document.querySelector(s);
const sube = () => $('#sube').value || 1;
let qrTimer=null, durumTimer=null, logSayfa=1;
function toast(m){ const el=document.createElement('div'); el.textContent=m; el.style.cssText='position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#111827;color:#fff;padding:10px 18px;border-radius:10px;z-index:99999;font-size:13px;box-shadow:0 6px 20px rgba(0,0,0,.25)'; document.body.appendChild(el); setTimeout(()=>el.remove(),2400); }
function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function fmt(s){ if(!s) return '—'; try{ return new Date(s.replace(' ','T')).toLocaleString('tr-TR'); }catch(e){ return s; } }

/* ---- Bağlantı ---- */
async function durumYenile(){
  try{
    const r = await fetch('/api/wa/durum?sube='+sube()+'&_='+Date.now(),{cache:'no-store'}); const j = await r.json();
    const b = j.body||j||{}; const phone = b.phone||b.number||b.jid||''; const bagli = b.connected===true && !!phone;
    const badge=$('#durumBadge'), yazi=$('#durumYazi');
    badge.className='wa-status '+(bagli?'connected':(b.qr||b.state==='qr'?'qr-pending':'disconnected'));
    yazi.textContent = bagli?'Bağlı':(b.qr||b.state==='qr'?'QR Bekleniyor':'Bağlı Değil');
    $('#connWrap').style.display = bagli?'block':'none';
    $('#baglıNo').textContent = phone||'-';
    $('#qrWrap').style.display = (!bagli && (b.qr||b.state==='qr'))?'block':'none';
    $('#offWrap').style.display = (!bagli && !(b.qr||b.state==='qr'))?'block':'none';
    $('#cikisBtn').style.display = bagli?'inline-block':'none';
    $('#baglanBtn').style.display = bagli?'none':'inline-block';
    if(!bagli && (b.qr||b.state==='qr')) loadQr();
  }catch(e){}
}
async function loadQr(){
  try{
    const r = await fetch('/api/wa/qr?sube='+sube()+'&_='+Date.now(),{cache:'no-store'}); const j = await r.json();
    const q = (j.body&&j.body.qr)||j.qr||'';
    if(q){ const src = q.startsWith('data:')?q:('https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=1&data='+encodeURIComponent(q)); $('#qrImgBox').innerHTML='<img class="" src="'+src+'" alt="QR">'; }
  }catch(e){}
}
async function baglan(){ await fetch('/api/wa/baglan',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sube:sube()})}); toast('Başlatılıyor, QR üretiliyor…'); setTimeout(durumYenile,1500); }
async function cikis(){ if(!confirm('Oturumu kapat?'))return; await fetch('/api/wa/cikis',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sube:sube()})}); toast('Oturum kapatıldı'); setTimeout(durumYenile,1000); }

/* ---- İşletme Bağlantıları ---- */
async function baglantiKaydet(){
  const body = new URLSearchParams({ konum_linki:$('#bgKonum').value.trim(), instagram_baslik:$('#bgIgBaslik').value.trim(), instagram_linki:$('#bgIgLink').value.trim(), web_baslik:$('#bgWebBaslik').value.trim(), web_linki:$('#bgWebLink').value.trim() });
  const st=$('#bgStatus'); st.style.display='inline';
  try{ const r=await fetch('/api/wa/baglantilar-kaydet',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body}); const j=await r.json(); st.style.color='#1a7f3e'; st.textContent = j.ok?'✓ Kaydedildi':'Hata'; setTimeout(()=>st.style.display='none',3500); }
  catch(e){ st.style.color='#dc3545'; st.textContent='Bağlantı hatası'; }
}

/* ---- Kontör ---- */
let talepKey='';
async function kontorYukle(){
  try{
    const r = await fetch('/api/wa/paket-durum?sube='+sube()+'&_='+Date.now(),{cache:'no-store'}); const j = await r.json();
    const bak = Number(j.bakiye)||0;
    $('#kontorPill').textContent = bak.toLocaleString('tr-TR')+' KONTÖR';
    $('#kontorPill').style.background = bak<=500?'#b91c1c':(bak<=1000?'#9a6a00':'#12805a');
    $('#kontorT1').textContent = j.kontorlu ? 'Kontörlü dönem' : 'Ücretsiz / deneme dönemi';
    $('#kontorT2').textContent = j.kontorlu ? 'Her mesaj 1 kontör düşer. Bakiyeniz bitmeden paket alın.' : ('Şu an mesaj başına kontör düşmez'+(j.deneme_bitis?(' (bitiş: '+j.deneme_bitis+')'):'')+'.');
  }catch(e){}
}
function talepAc(key){ talepKey=key; $('#talepIletisim').value=''; $('#talepSonuc').textContent=''; $('#talepModal').classList.add('show'); }
async function talepGonder(){
  try{ const r=await fetch('/api/wa/kontor-talep',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sube:sube(),paket:talepKey,iletisim:$('#talepIletisim').value.trim()})}); const j=await r.json();
    $('#talepSonuc').style.color = j.ok?'#1a7f3e':'#dc3545'; $('#talepSonuc').textContent = j.mesaj||(j.ok?'Talep alındı':'Hata'); if(j.ok) setTimeout(()=>$('#talepModal').classList.remove('show'),1800);
  }catch(e){ $('#talepSonuc').textContent='Gönderilemedi'; }
}

/* ---- İstatistik ---- */
document.querySelectorAll('.wsi-tab').forEach(t=>t.addEventListener('click',()=>{
  document.querySelectorAll('.wsi-tab').forEach(x=>x.classList.remove('active'));
  document.querySelectorAll('.wsi-section').forEach(x=>x.classList.remove('active'));
  t.classList.add('active'); $('#sec-'+t.dataset.wsi).classList.add('active');
  if(t.dataset.wsi==='loglar') logYukle(1); if(t.dataset.wsi==='aliciler') aliciYukle();
}));
let chart=null;
async function ozetYukle(){
  try{
    const r=await fetch('/api/wa/ozet-data?sube='+sube()+'&_='+Date.now(),{cache:'no-store'}); const j=await r.json();
    $('#ozetGrid').innerHTML =
      card('info','Bağlı Numara', j.numara||'—','')
      +card('','Bugün Toplam', j.bugun||0, '✓'+(j.bugun_ok||0)+' ✕'+(j.bugun_fail||0)+' ↷'+(j.bugun_sms||0))
      +card('','7 Gün', j.son7||0, (j.son7_ok||0)+' başarılı')
      +card('','30 Gün', j.son30||0, (j.son30_ok||0)+' başarılı')
      +card('warn','Haftalık Başarı', (j.basari||0)+'%','')
      +card('info','Günlük Limit', j.gunluk_limit||200,'');
    if(typeof Chart!=='undefined' && j.gunler){
      const ctx=$('#wsiChart').getContext('2d'); if(chart) chart.destroy();
      chart=new Chart(ctx,{type:'bar',data:{labels:j.gunler.map(g=>g.gun),datasets:[
        {label:'Başarılı',data:j.gunler.map(g=>g.basarili),backgroundColor:'#25D366'},
        {label:'Başarısız',data:j.gunler.map(g=>g.basarisiz),backgroundColor:'#dc3545'},
        {label:"SMS'e Düştü",data:j.gunler.map(g=>g.sms),backgroundColor:'#f0ad4e'}]},
        options:{responsive:true,scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}},plugins:{legend:{position:'top'}}}});
    }
  }catch(e){}
}
function card(cls,label,val,sub){ return '<div class="wsi-stat-card '+cls+'"><div class="wsi-stat-label">'+esc(label)+'</div><div class="wsi-stat-value">'+esc(val)+'</div>'+(sub?'<div class="wsi-stat-sub">'+esc(sub)+'</div>':'')+'</div>'; }
const DBADGE={1:'success',2:'fail',3:'fallback',0:'queued'}, DLBL={1:'Gönderildi',2:'Başarısız',3:"SMS'e Düştü",0:'Kuyrukta'};
async function logYukle(sayfa){
  logSayfa=sayfa||1;
  const p=new URLSearchParams({sube:sube(),sayfa:logSayfa,durum:$('#fDurum').value,telefon:$('#fTel').value.trim(),baslangic:$('#fBas').value,bitis:$('#fBit').value,arama:$('#fArama').value.trim()});
  const r=await fetch('/api/wa/loglar-data?'+p); const j=await r.json();
  const tb=$('#logTbl').querySelector('tbody');
  if(!j.kayitlar||!j.kayitlar.length){ tb.innerHTML='<tr><td colspan="5">Kayıt yok.</td></tr>'; $('#logPag').innerHTML=''; return; }
  tb.innerHTML=j.kayitlar.map(k=>'<tr><td>'+fmt(k.created_at)+'</td><td>'+esc(k.telefon||'-')+'</td><td><span class="wsi-badge '+(DBADGE[k.durum]||'')+'">'+(DLBL[k.durum]||k.durum)+'</span></td><td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(k.mesaj||'')+'</td><td>'+esc(k.hata||'')+'</td></tr>').join('');
  const toplamSayfa=Math.ceil(j.toplam/j.boyut)||1;
  $('#logPag').innerHTML='<button '+(logSayfa<=1?'disabled':'')+' onclick="logYukle('+(logSayfa-1)+')">‹</button><span style="font-size:13px">'+logSayfa+' / '+toplamSayfa+' ('+j.toplam+')</span><button '+(logSayfa>=toplamSayfa?'disabled':'')+' onclick="logYukle('+(logSayfa+1)+')">›</button>';
}
function logSifirla(){ $('#fDurum').value='';$('#fTel').value='';$('#fBas').value='';$('#fBit').value='';$('#fArama').value=''; logYukle(1); }
async function aliciYukle(){
  const r=await fetch('/api/wa/aliciler-data?sube='+sube()); const j=await r.json();
  const tb=$('#aliciTbl').querySelector('tbody');
  if(!j.aliciler||!j.aliciler.length){ tb.innerHTML='<tr><td colspan="5">Kayıt yok.</td></tr>'; return; }
  tb.innerHTML=j.aliciler.map(a=>'<tr><td>'+esc(a.telefon||'-')+'</td><td>'+a.toplam+'</td><td>'+a.basarili+'</td><td>'+fmt(a.ilk)+'</td><td>'+fmt(a.son)+'</td></tr>').join('');
}

/* init */
durumYenile(); kontorYukle(); ozetYukle();
durumTimer=setInterval(durumYenile,8000);
</script>
@endsection

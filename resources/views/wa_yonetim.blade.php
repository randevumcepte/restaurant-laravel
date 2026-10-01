<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WhatsApp Yönetimi · ResteOS</title>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --line:#2D3752; --ink:#F1F5F9; --sub:#94A3B8; --yesil:#10B981; --kirmizi:#F43F5E; }
  *{ box-sizing:border-box; } body{ margin:0; background:var(--bg); color:var(--ink); font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; }
  .wrap{ max-width:900px; margin:0 auto; padding:20px 16px 60px; }
  h1{ font-size:22px; margin:6px 0 2px; } .mut{ color:var(--sub); font-size:13px; }
  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:18px; }
  @media(max-width:720px){ .grid{ grid-template-columns:1fr; } }
  .card{ background:var(--card); border:1px solid var(--line); border-radius:16px; padding:18px; }
  .card h2{ font-size:15px; margin:0 0 12px; display:flex; align-items:center; gap:8px; }
  label{ display:block; font-size:12px; color:var(--sub); margin:10px 0 4px; }
  input,textarea,select{ width:100%; background:#0F1626; border:1px solid var(--line); color:var(--ink); border-radius:10px; padding:10px 12px; font-size:14px; }
  textarea{ min-height:70px; resize:vertical; }
  .btn{ border:none; border-radius:11px; padding:11px 16px; font-size:14px; font-weight:700; color:#fff; cursor:pointer; background:linear-gradient(135deg,var(--mor),var(--mavi)); }
  .btn.sec{ background:#222a40; } .btn.red{ background:var(--kirmizi); } .btn.sm{ padding:8px 12px; font-size:13px; }
  .row{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
  .pill{ display:inline-flex; align-items:center; gap:7px; padding:6px 12px; border-radius:999px; font-size:13px; font-weight:700; }
  .dot{ width:9px; height:9px; border-radius:50%; background:var(--sub); }
  .ok{ background:rgba(16,185,129,.15); color:#6EE7B7; } .ok .dot{ background:var(--yesil); }
  .no{ background:rgba(244,63,94,.14); color:#FDA4AF; } .no .dot{ background:var(--kirmizi); }
  #qrbox{ background:#fff; border-radius:14px; padding:14px; width:260px; max-width:100%; margin:12px auto 0; display:none; text-align:center; }
  #qrbox canvas,#qrbox img{ width:100%; height:auto; } #qrbox .t{ color:#111; font-size:12px; margin-top:8px; }
  .note{ font-size:12px; color:var(--sub); margin-top:8px; line-height:1.5; }
  code{ background:#0F1626; padding:2px 6px; border-radius:6px; font-size:12px; color:#C7D2FE; word-break:break-all; }
  .toast{ position:fixed; left:50%; bottom:22px; transform:translateX(-50%); background:#1b2540; border:1px solid var(--line); color:var(--ink); padding:10px 16px; border-radius:10px; font-size:13px; opacity:0; transition:.2s; }
  .toast.show{ opacity:1; }
</style>
</head>
<body>
<div class="wrap">
  <h1>📲 WhatsApp Sipariş Yönetimi</h1>
  <div class="mut">Müşteriler WhatsApp'tan yazıp sipariş verir; durum değişince bildirim gider. (whatsmeow)</div>

  @if(count($subeler) > 1)
  <div style="margin-top:14px">
    <label>Şube</label>
    <select id="sube">
      @foreach($subeler as $s)<option value="{{ $s->id }}">{{ $s->ad }}</option>@endforeach
    </select>
  </div>
  @else
  <input type="hidden" id="sube" value="{{ $subeler[0]->id ?? 1 }}">
  @endif

  <div class="grid">
    <!-- Bağlantı -->
    <div class="card">
      <h2>🔌 Bağlantı</h2>
      <div class="row">
        <span id="durumPill" class="pill no"><span class="dot"></span><span id="durumYazi">kontrol ediliyor…</span></span>
        <button class="btn sm sec" onclick="durumYenile()">Yenile</button>
      </div>
      <div style="margin-top:14px" class="row">
        <button class="btn" onclick="baglan()">📷 Bağlan / QR Üret</button>
        <button class="btn sm red" onclick="cikis()">Çıkış</button>
      </div>
      <div id="qrbox"><div id="qr"></div><div class="t">WhatsApp → Ayarlar → Bağlı Cihazlar → Cihaz Bağla ile tara</div></div>
      <div class="note">Sidecar ayarlı değilse önce sağdaki ayarları kaydet. Numara bağlandıktan sonra QR gerekmez.</div>
    </div>

    <!-- Ayarlar -->
    <div class="card">
      <h2>⚙️ Ayarlar</h2>
      <label>Sidecar URL (whatsmeow bridge)</label>
      <input id="sidecar" value="{{ $sidecar }}" placeholder="http://127.0.0.1:3002">
      <label>Servis Token {!! $tokenVar ? '<span style=\'color:#6EE7B7\'>(kayıtlı)</span>' : '' !!}</label>
      <input id="token" type="password" placeholder="{{ $tokenVar ? '•••••• (değiştirmek için yaz)' : 'bridge X-Service-Token' }}">
      <label>Karşılama mesajı (opsiyonel)</label>
      <textarea id="karsilama" placeholder="Boşsa varsayılan karşılama kullanılır">{{ $karsilama }}</textarea>
      <div style="margin-top:12px"><button class="btn" onclick="ayarKaydet()">Kaydet</button></div>
    </div>

    <!-- Test -->
    <div class="card">
      <h2>🧪 Test Gönder</h2>
      <label>Telefon (5xxxxxxxxx)</label>
      <div class="row">
        <input id="testTel" style="flex:1; min-width:160px" placeholder="5xxxxxxxxx">
        <button class="btn" onclick="testGonder()">Gönder</button>
      </div>
      <div class="note">Bağlı numaradan bu numaraya test mesajı atar.</div>
    </div>

    <!-- Webhook -->
    <div class="card">
      <h2>🔗 Gelen Mesaj Webhook'u</h2>
      <div class="note">Bridge'in gelen mesaj webhook'unu buraya ayarla:</div>
      <div style="margin-top:8px"><code id="wh">{{ $webhook }}</code></div>
      <div style="margin-top:10px"><button class="btn sm sec" onclick="kopyala()">Kopyala</button></div>
      <div class="note">Gövde alanları: <code>sube_id, from, text</code> (+ konum için <code>lat, lng, type=location</code>).</div>
    </div>
  </div>
</div>
<div class="toast" id="toast"></div>

<script>
const $ = s => document.querySelector(s);
const sube = () => $('#sube').value || 1;
let qrTimer = null, durumTimer = null;

function toast(m){ const t=$('#toast'); t.textContent=m; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),2200); }

async function durumYenile(){
  try{
    const r = await fetch('/api/wa/durum?sube='+sube()); const j = await r.json();
    const b = j.body || j || {};
    const bagli = b.connected === true;
    const pill = $('#durumPill'), yazi = $('#durumYazi');
    if(bagli){ pill.className='pill ok'; yazi.textContent='Bağlı'+(b.phone?(' · '+b.phone):''); $('#qrbox').style.display='none'; if(qrTimer){clearInterval(qrTimer); qrTimer=null;} }
    else { pill.className='pill no'; yazi.textContent = (b.status||'bağlı değil'); }
  }catch(e){ $('#durumYazi').textContent='sidecar erişilemedi'; }
}

async function baglan(){
  toast('QR üretiliyor…');
  try{ await fetch('/api/wa/baglan',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sube:sube()})}); }catch(e){}
  $('#qrbox').style.display='block';
  if(qrTimer) clearInterval(qrTimer);
  qrGetir();
  qrTimer = setInterval(qrGetir, 2500);
}
async function qrGetir(){
  try{
    const r = await fetch('/api/wa/qr?sube='+sube()); const j = await r.json();
    const b = j.body || j || {};
    const val = b.qr || b.qrcode || b.code || b.image || '';
    const box = $('#qr');
    if(!val){ box.innerHTML='<div style="color:#111;font-size:13px">QR hazırlanıyor…</div>'; return; }
    if(String(val).startsWith('data:')){ box.innerHTML = '<img src="'+val+'">'; }
    else { box.innerHTML=''; QRCode.toCanvas(val, {width:220, margin:1}, (err,cv)=>{ if(!err) box.appendChild(cv); }); }
    durumYenile();
  }catch(e){}
}
async function cikis(){
  if(!confirm('WhatsApp oturumu kapatılsın mı?')) return;
  try{ await fetch('/api/wa/cikis',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({sube:sube()})}); toast('Çıkış yapıldı'); }catch(e){}
  if(qrTimer){clearInterval(qrTimer); qrTimer=null;} $('#qrbox').style.display='none';
  durumYenile();
}
async function ayarKaydet(){
  const sc = $('#sidecar').value.trim();
  const tk = $('#token').value.trim();
  const ka = $('#karsilama').value;
  await fetch('/wa-ayar?anahtar=wa_sidecar_url&deger='+encodeURIComponent(sc));
  if(tk) await fetch('/wa-ayar?anahtar=wa_servis_token&deger='+encodeURIComponent(tk));
  await fetch('/wa-ayar?anahtar=wa_karsilama&deger='+encodeURIComponent(ka));
  toast('Ayarlar kaydedildi'); $('#token').value=''; durumYenile();
}
async function testGonder(){
  const tel = $('#testTel').value.trim();
  if(!tel){ toast('Telefon gir'); return; }
  const r = await fetch('/wa-test?sube='+sube()+'&tel='+encodeURIComponent(tel)); const t = await r.text();
  toast(t);
}
function kopyala(){ navigator.clipboard.writeText($('#wh').textContent).then(()=>toast('Kopyalandı')); }

$('#sube') && $('#sube').addEventListener && $('#sube').addEventListener('change', durumYenile);
durumYenile();
durumTimer = setInterval(durumYenile, 8000);
</script>
</body>
</html>

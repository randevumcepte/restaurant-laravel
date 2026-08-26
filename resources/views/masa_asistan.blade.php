<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>{{ $sube->ad ?? 'Restoran' }} · {{ $masa->ad ?? '' }}</title>
<style>
  :root{ --mor:#7C3AED; --mor2:#9D5DC8; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; }
  *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  html,body{ margin:0; height:100%; background:var(--bg); color:#fff; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  #app{ display:flex; flex-direction:column; height:100dvh; }
  header{ padding:14px 16px; display:flex; align-items:center; gap:10px; }
  header .logo{ background:linear-gradient(135deg,var(--mor),var(--mavi)); padding:5px 9px; border-radius:9px; font-weight:800; font-size:13px; }
  header .ad{ font-weight:800; font-size:16px; }
  header .masa{ margin-left:auto; background:rgba(124,58,237,.25); color:#C4B5FD; font-size:12px; font-weight:700; padding:4px 10px; border-radius:20px; }
  #orb-wrap{ display:flex; flex-direction:column; align-items:center; padding:8px 0 2px; }
  #orb{ width:120px; height:120px; border-radius:50%; position:relative;
    background:conic-gradient(from 0deg,#22D3EE,#3B82F6,#8B5CF6,#EC4899,#22D3EE);
    box-shadow:0 0 40px rgba(139,92,246,.45),0 0 22px rgba(34,211,238,.3); animation:spin 6s linear infinite; }
  #orb::after{ content:''; position:absolute; inset:26px; border-radius:50%;
    background:radial-gradient(circle,#fff 0%,rgba(255,255,255,.85) 45%,rgba(255,255,255,0) 72%); }
  #orb.dinliyor{ animation:spin 3s linear infinite, pulse 1s ease-in-out infinite; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  @keyframes pulse{ 0%,100%{ transform:scale(1);} 50%{ transform:scale(1.06);} }
  #durum{ text-align:center; color:#94A3B8; font-size:13px; margin:8px 20px 0; min-height:18px; }
  #sohbet{ flex:1; overflow-y:auto; padding:12px 14px 4px; -webkit-overflow-scrolling:touch; }
  .balon{ max-width:86%; padding:11px 15px; border-radius:16px; margin-bottom:10px; font-size:14.5px; line-height:1.4; }
  .ben{ margin-left:auto; background:linear-gradient(135deg,var(--mor),var(--mavi)); border-bottom-right-radius:4px; }
  .ai{ background:var(--card); border-bottom-left-radius:4px; color:#E2E8F0; }
  .cips{ display:flex; gap:8px; overflow-x:auto; padding:6px 14px; }
  .cip{ flex:0 0 auto; background:var(--card); border:1px solid #2D3752; color:#C4B5FD; font-size:12.5px; font-weight:600;
    padding:9px 13px; border-radius:20px; white-space:nowrap; }
  footer{ padding:10px 12px calc(10px + env(safe-area-inset-bottom)); display:flex; gap:8px; align-items:center; }
  #metin{ flex:1; background:var(--card); border:none; color:#fff; font-size:14.5px; padding:13px 16px; border-radius:24px; outline:none; }
  .yuvarlak{ width:48px; height:48px; border-radius:50%; border:none; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
  #mic{ background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 4px 14px rgba(124,58,237,.4); }
  #mic.dinliyor{ background:linear-gradient(135deg,#F43F5E,#EF4444); }
  #gonder{ background:var(--card); color:#C4B5FD; }
  /* ---- Gorsel kartlar (PREMIUM) ---- */
  .kartsira{ display:flex; gap:14px; overflow-x:auto; padding:6px 2px 18px; margin-bottom:4px;
    -webkit-overflow-scrolling:touch; scroll-snap-type:x mandatory; }
  .kartsira::-webkit-scrollbar{ height:0; }
  .mkart{ flex:0 0 236px; scroll-snap-align:start; position:relative; height:312px; border-radius:24px; overflow:hidden;
    background:#0e1428; border:1px solid rgba(255,255,255,.07); box-shadow:0 14px 34px rgba(0,0,0,.5);
    opacity:0; transform:translateY(18px) scale(.97); animation:kartGir .55s cubic-bezier(.2,.75,.2,1) forwards; }
  @keyframes kartGir{ to{ opacity:1; transform:none; } }
  .mkart .gor{ position:absolute; inset:0; background:#0e1428; }
  .mkart .gor img{ width:100%; height:100%; object-fit:cover; transform:scale(1.03); transition:transform .7s ease; }
  .mkart:active .gor img{ transform:scale(1.1); }
  .mkart .tile{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; }
  .mkart .tile span{ font-size:86px; filter:drop-shadow(0 6px 14px rgba(0,0,0,.45)); }
  .mkart .shade{ position:absolute; inset:0; background:linear-gradient(180deg,rgba(6,10,20,.05) 28%,rgba(6,10,20,.5) 56%,rgba(6,10,20,.94) 100%); }
  .mkart .et{ position:absolute; top:13px; left:13px; z-index:2; background:linear-gradient(135deg,#F6CE63,#E0A431);
    color:#3a2600; font-size:11px; font-weight:800; letter-spacing:.3px; padding:5px 12px; border-radius:30px;
    box-shadow:0 5px 14px rgba(224,164,49,.5); }
  .mkart .body{ position:absolute; left:0; right:0; bottom:0; z-index:2; padding:15px 16px 16px; }
  .mkart .mad{ font-weight:800; font-size:19px; line-height:1.15; letter-spacing:.2px; text-shadow:0 2px 10px rgba(0,0,0,.7); }
  .mkart .mfi{ display:inline-block; margin-top:7px; background:rgba(255,255,255,.13); backdrop-filter:blur(8px);
    color:#FDE9B5; font-weight:800; font-size:14px; padding:3px 12px; border-radius:22px; border:1px solid rgba(246,206,99,.45); }
  .mkart .ac{ color:#D7DEEA; font-size:11.8px; line-height:1.38; margin-top:9px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  .mkart .mbtn{ margin-top:12px; width:100%; border:none; border-radius:14px; padding:12px; font-weight:800; font-size:13.5px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 7px 18px rgba(124,58,237,.5); }
  .mkart .mbtn:active{ transform:translateY(1px); }
  /* Kategori kartlari */
  .katsira{ display:flex; gap:11px; overflow-x:auto; padding:6px 2px 16px; margin-bottom:4px; -webkit-overflow-scrolling:touch; }
  .katsira::-webkit-scrollbar{ height:0; }
  .kkart{ flex:0 0 auto; min-width:100px; display:flex; flex-direction:column; align-items:center; gap:7px;
    padding:17px 15px; border-radius:20px; border:1px solid rgba(255,255,255,.08);
    background:linear-gradient(160deg,#1c2542,#141a30); box-shadow:0 8px 20px rgba(0,0,0,.4);
    opacity:0; transform:translateY(14px); animation:kartGir .5s cubic-bezier(.2,.75,.2,1) forwards; }
  .kkart:active{ border-color:rgba(124,58,237,.6); }
  .kkart .ke{ font-size:32px; filter:drop-shadow(0 3px 8px rgba(0,0,0,.4)); }
  .kkart .kad{ font-weight:700; font-size:12.5px; color:#E7ECF5; text-align:center; }
  .mkart .gor{ cursor:pointer; }
  /* ---- Foto galeri (lightbox) ---- */
  #lightbox{ position:fixed; inset:0; z-index:60; background:rgba(4,7,15,.95); backdrop-filter:blur(8px);
    display:none; flex-direction:column; animation:lbGir .25s ease; }
  @keyframes lbGir{ from{ opacity:0; } to{ opacity:1; } }
  .lb-bar{ display:flex; align-items:center; gap:10px; padding:16px 18px; }
  .lb-bar #lb-ad{ font-weight:800; font-size:19px; }
  .lb-bar #lb-kapat{ margin-left:auto; width:40px; height:40px; border-radius:50%; border:none;
    background:rgba(255,255,255,.13); color:#fff; font-size:18px; }
  .lb-imgs{ flex:1; display:flex; overflow-x:auto; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; }
  .lb-imgs::-webkit-scrollbar{ height:0; }
  .lb-imgs img{ flex:0 0 100%; width:100%; height:100%; object-fit:cover; scroll-snap-align:center; }
  .lb-alt{ padding:15px 18px calc(18px + env(safe-area-inset-bottom)); background:linear-gradient(0deg,rgba(6,10,20,.96),rgba(6,10,20,0)); }
  .lb-alt #lb-fi{ display:inline-block; background:linear-gradient(135deg,#F6CE63,#E0A431); color:#3a2600;
    font-weight:800; font-size:15px; padding:4px 14px; border-radius:22px; }
  .lb-alt #lb-ac{ display:block; color:#D7DEEA; font-size:13.5px; line-height:1.45; margin:11px 0 14px; }
  .lb-alt #lb-iste{ width:100%; border:none; border-radius:14px; padding:14px; font-weight:800; font-size:14.5px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 20px rgba(124,58,237,.5); }
  .lb-ipucu{ text-align:center; color:#64748B; font-size:11.5px; padding:6px 0 2px; }
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="logo">RestoOS</span>
    <span class="ad">{{ $sube->ad ?? 'Restoran' }}</span>
    <span class="masa">🍽️ {{ $masa->ad ?? '' }}</span>
  </header>
  <div id="orb-wrap">
    <div id="orb" onclick="dinle()"></div>
    <div id="durum">Dokunup konuşun ya da yazın</div>
  </div>
  <div id="sohbet"></div>
  <div class="cips">
    <div class="cip" onclick="sor('Menüde neler var?')">📋 Menü</div>
    <div class="cip" onclick="sor('Günün yemeği ne?')">⭐ Günün yemeği</div>
    <div class="cip" onclick="sor('Ne önerirsin?')">👍 Öneri</div>
    <div class="cip" onclick="sor('Tatlılar neler?')">🍰 Tatlılar</div>
    <div class="cip" onclick="sor('WiFi şifresi ne?')">📶 WiFi</div>
    <div class="cip" onclick="sor('Garsonu çağır')">🙋 Garson</div>
  </div>
  <footer>
    <button class="yuvarlak" id="mic" onclick="dinle()">🎤</button>
    <input id="metin" placeholder="Sorunuzu yazın…" onkeydown="if(event.key==='Enter')gonderMetin()">
    <button class="yuvarlak" id="gonder" onclick="gonderMetin()">➤</button>
  </footer>
</div>

<div id="lightbox">
  <div class="lb-bar"><span id="lb-ad"></span><button id="lb-kapat">✕</button></div>
  <div class="lb-imgs" id="lb-imgs"></div>
  <div class="lb-ipucu">← kaydırarak diğer fotoğraflara bakın →</div>
  <div class="lb-alt"><span id="lb-fi"></span><span id="lb-ac"></span><button id="lb-iste">🙋 İstiyorum</button></div>
</div>

<script>
const MASA = @json($masa->id);
const sohbet = document.getElementById('sohbet');
const orb = document.getElementById('orb');
const micBtn = document.getElementById('mic');
const durumEl = document.getElementById('durum');
let bekliyor = false;

function ekle(kim, metin){
  const d = document.createElement('div');
  d.className = 'balon ' + (kim==='ben'?'ben':'ai');
  d.textContent = metin;
  sohbet.appendChild(d);
  sohbet.scrollTop = sohbet.scrollHeight;
}

function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
// Ada gore sabit renkli gradient (foto yoksa emoji tile arka plani)
function gradientFor(str){ let h=0; for(let i=0;i<String(str).length;i++) h=(h*31+str.charCodeAt(i))%360; return `linear-gradient(135deg,hsl(${h},62%,52%),hsl(${(h+42)%360},68%,42%))`; }

// Urun kartlari (tam boy foto hero + altin etiket + cam fiyat rozeti + "Istiyorum")
function kartlariEkle(kartlar){
  const sar = document.createElement('div'); sar.className='kartsira';
  kartlar.forEach((k, idx)=>{
    const c = document.createElement('div'); c.className='mkart';
    c.style.animationDelay = (idx*75)+'ms';
    const tile = `<div class="tile" style="background:${gradientFor(k.ad)}"><span>${k.emoji||'🍽️'}</span></div>`;
    const gorsel = k.gorsel
      ? `<img src="${esc(k.gorsel)}" alt="${esc(k.ad)}" loading="lazy" onerror="this.onerror=null;this.parentNode.innerHTML=${JSON.stringify(tile)}">`
      : tile;
    const et = k.etiket ? `<div class="et">★ ${esc(k.etiket)}</div>` : '';
    const ac = k.aciklama ? `<div class="ac">${esc(k.aciklama)}</div>` : '';
    c.innerHTML = `<div class="gor">${gorsel}</div><div class="shade"></div>${et}`
      + `<div class="body"><div class="mad">${esc(k.ad)}</div>`
      + `<div class="mfi">${esc(k.fiyat_yazi||'')}</div>${ac}`
      + `<button class="mbtn">🙋 İstiyorum</button></div>`;
    c.querySelector('.mbtn').addEventListener('click', e=>{ e.stopPropagation(); istiyorum(k.ad); });
    c.querySelector('.gor').addEventListener('click', e=>{ e.stopPropagation(); acGaleri(k); });
    c.addEventListener('click', ()=> sor(k.ad));
    sar.appendChild(c);
  });
  sohbet.appendChild(sar); sohbet.scrollTop = sohbet.scrollHeight;
}

/* ---- Foto galeri: karttaki resme dokununca ayni yemegin birkac fotografi ---- */
const lb = document.getElementById('lightbox');
function acGaleri(k){
  document.getElementById('lb-ad').textContent = k.ad || '';
  document.getElementById('lb-fi').textContent = k.fiyat_yazi || '';
  document.getElementById('lb-ac').textContent = k.aciklama || '';
  const wrap = document.getElementById('lb-imgs'); wrap.innerHTML = '';
  const liste = (k.gorseller && k.gorseller.length ? k.gorseller : [k.gorsel]).filter(Boolean);
  if(!liste.length){ const d=document.createElement('div'); d.className='tile'; d.style.cssText='flex:0 0 100%;background:'+gradientFor(k.ad); d.innerHTML='<span style="font-size:120px">'+(k.emoji||'🍽️')+'</span>'; wrap.appendChild(d); }
  liste.forEach(src=>{
    const im = document.createElement('img'); im.src = src; im.alt = k.ad; im.loading='lazy';
    im.addEventListener('error', function(){ this.style.background = gradientFor(k.ad); this.removeAttribute('src'); });
    wrap.appendChild(im);
  });
  document.getElementById('lb-iste').onclick = ()=>{ kapatGaleri(); istiyorum(k.ad); };
  lb.querySelector('.lb-ipucu').style.display = liste.length > 1 ? 'block' : 'none';
  lb.style.display = 'flex';
}
function kapatGaleri(){ lb.style.display = 'none'; }
document.getElementById('lb-kapat').addEventListener('click', kapatGaleri);
lb.addEventListener('click', e=>{ if(e.target === lb) kapatGaleri(); });

// Kategori kartlari (dokun -> o kategoriyi ac)
function kategorilerEkle(kats){
  const sar = document.createElement('div'); sar.className='katsira';
  kats.forEach((k, idx)=>{
    const c = document.createElement('div'); c.className='kkart';
    c.style.animationDelay = (idx*55)+'ms';
    c.innerHTML = `<span class="ke">${k.emoji||'🍽️'}</span><span class="kad">${esc(k.ad)}</span>`;
    c.addEventListener('click', ()=> sor(k.ad + ' neler var'));
    sar.appendChild(c);
  });
  sohbet.appendChild(sar); sohbet.scrollTop = sohbet.scrollHeight;
}

// "Istiyorum" -> garson cagir (Faz 1: siparisi garson alir)
function istiyorum(ad){
  ekle('ben', ad + ' istiyorum');
  garsonCagir('garson');
  const m = 'Harika seçim! ' + ad + ' için garsonumuzu çağırdım, birazdan siparişinizi alacak. 😊';
  ekle('ai', m); konus(m);
}

/* ---- Seslendirme: ONCE bedava cihaz (tarayici) sesi; yoksa sunucu Google TTS ---- */
const synth = window.speechSynthesis;
let trVoice = null;
function seciSes(){
  try{
    const vs = synth.getVoices() || [];
    const tr = vs.filter(v=>/^tr/i.test(v.lang));
    // Once ERKEK adayi (varsa), sonra cihaz-yerel (localService=bedava/kaliteli), sonra herhangi tr
    trVoice = tr.find(v=>/(erkek|male|-tmc|-tmb|#male)/i.test(v.name))
           || tr.find(v=>v.localService && /google/i.test(v.name))
           || tr.find(v=>v.localService)
           || tr.find(v=>/google/i.test(v.name))
           || tr[0] || null;
  }catch(e){}
}
if(synth){ synth.onvoiceschanged = seciSes; seciSes(); }
let sesCalar = new Audio();
function sesDurdur(){ try{ synth && synth.cancel(); }catch(_){}; try{ sesCalar.pause(); }catch(_){} }
function temizle(t){ return (t||'').replace(/[^\p{L}\p{N}\s.,!?%:₺'"()-]/gu,'').trim(); }
// SADECE Android bedava cihaz sesi kullanir; diger herkes (iPhone/masaustu) Cloud (Puck).
const isAndroid = /android/i.test(navigator.userAgent);
// Bedava cihaz (tarayici) sesi ile konus. Basarili ise true.
function cihazKonus(temiz){
  seciSes();
  if(synth && trVoice){
    try{ synth.cancel(); const u=new SpeechSynthesisUtterance(temiz); u.lang='tr-TR'; u.voice=trVoice; u.rate=1.0; u.pitch=1.0; synth.speak(u); return true; }catch(e){}
  }
  return false;
}
// Sunucu Google TTS ile konus. Basarili ise true.
async function cloudKonus(temiz){
  try{
    const r = await fetch('/api/tts', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({metin:temiz, masa:MASA})});
    const j = await r.json();
    if(j.basarili && j.url){ sesDurdur(); sesCalar = new Audio(j.url); sesCalar.play().catch(()=>{}); return true; }
  }catch(e){}
  return false;
}
function seseHazirla(t){
  // Binlik noktayi kaldir (1.250->1250), "₺75"/"75 TL" -> "75 lira" (hem cihaz hem cloud akici okusun)
  return temizle(t)
    .replace(/(\d)\.(\d{3})(?=\D|$)/g,'$1$2')
    .replace(/(\d)\.(\d{3})(?=\D|$)/g,'$1$2')
    .replace(/₺\s*(\d+)/g,'$1 lira')
    .replace(/(\d+)\s*(?:₺|tl)\b/gi,'$1 lira')
    .replace(/₺/g,' lira');
}
async function konus(t){
  const temiz = seseHazirla(t);
  if(!temiz) return;
  if(isAndroid){
    // Android: ONCE bedava cihaz sesi (para gitmez); TR ses yoksa Cloud'a dus
    if(cihazKonus(temiz)) return;
    await cloudKonus(temiz); return;
  }
  // Diger herkes (iPhone/masaustu): Puck (Cloud); olmazsa cihaz sesi
  if(await cloudKonus(temiz)) return;
  cihazKonus(temiz);
}

/* ---- Konusma tanima (tarayici STT) ---- */
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
let rec = null, dinliyorMu = false;
if(SR){
  rec = new SR(); rec.lang='tr-TR'; rec.interimResults=true; rec.continuous=false; rec.maxAlternatives=1;
  rec.onstart = ()=>{ dinliyorMu=true; orb.classList.add('dinliyor'); micBtn.classList.add('dinliyor'); durumEl.textContent='Dinliyorum…'; };
  rec.onerror = ()=>{ bitir(); };
  rec.onend = ()=>{ bitir(); };
  rec.onresult = (e)=>{
    let t=''; for(let i=0;i<e.results.length;i++) t += e.results[i][0].transcript;
    durumEl.textContent = t || 'Dinliyorum…';
    if(e.results[e.results.length-1].isFinal && t.trim()){ dinliyorMu=false; try{rec.stop()}catch(_){}
      sor(t.trim()); }
  };
}
function bitir(){ dinliyorMu=false; orb.classList.remove('dinliyor'); micBtn.classList.remove('dinliyor'); if(!bekliyor) durumEl.textContent='Dokunup konuşun ya da yazın'; }
function dinle(){
  if(!rec){ durumEl.textContent='Bu tarayıcı sesi desteklemiyor, yazabilirsiniz.'; return; }
  if(dinliyorMu){ try{rec.stop()}catch(_){}; return; }
  try{ sesDurdur(); rec.start(); }catch(e){}
}

/* ---- Gonderim ---- */
function gonderMetin(){ const el=document.getElementById('metin'); const t=el.value.trim(); if(!t)return; el.value=''; sor(t); }
async function sor(soru){
  if(bekliyor) return;
  ekle('ben', soru); bekliyor=true; durumEl.textContent='Düşünüyorum…';
  try{
    const r = await fetch('/api/qr/asistan', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
      body:new URLSearchParams({masa:MASA, soru})});
    const j = await r.json();
    const cevap = j.cevap || 'Bir sorun oldu, tekrar dener misiniz?';
    ekle('ai', cevap);
    if(Array.isArray(j.kategoriler) && j.kategoriler.length) kategorilerEkle(j.kategoriler);
    if(Array.isArray(j.kartlar) && j.kartlar.length) kartlariEkle(j.kartlar);
    if(j.seslendir!==false) konus(cevap);
    if(j.aksiyon==='garson_cagir') garsonCagir(j.tip || 'garson');
  }catch(e){ ekle('ai','Bağlantı hatası, tekrar dener misiniz?'); }
  bekliyor=false; durumEl.textContent='Dokunup konuşun ya da yazın';
}
async function garsonCagir(tip){
  try{ await fetch('/api/qr/garson-cagir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA,tip})}); }catch(e){}
}

/* ---- Acilis karsilama ---- */
window.addEventListener('load', ()=>{
  const selam = 'Hoş geldiniz! Ben {{ $sube->ad ?? "restoranınızın" }} masanızın asistanıyım. Menüyü tanıtabilir, öneride bulunabilir ya da garson çağırabilirim. Ne yapmak istersiniz?';
  ekle('ai', selam);
  // Tarayicilar otomatik sesi kullanici etkilesimi olmadan engelleyebilir; deneriz.
  setTimeout(()=>konus(selam), 400);
});
</script>
</body>
</html>

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
  #mic.acik{ background:linear-gradient(135deg,#16A34A,#22C55E); box-shadow:0 0 0 4px rgba(34,197,94,.25),0 4px 14px rgba(34,197,94,.5); }
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
  /* ---- Sepet (sipariş onayı) ---- */
  .sepet{ background:linear-gradient(160deg,#1b2340,#141a2e); border:1px solid #2D3752; border-radius:18px; padding:14px 15px; margin-bottom:10px;
    box-shadow:0 10px 26px rgba(0,0,0,.4); }
  .sepet h4{ margin:0 0 10px; font-size:15px; color:#fff; }
  .sepet .satir{ display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #232B42; }
  .sepet .satir:last-of-type{ border-bottom:none; }
  .sepet .sad{ flex:1; font-size:13.5px; color:#E2E8F0; font-weight:600; }
  .sepet .sfi{ color:#FDE9B5; font-weight:800; font-size:13px; min-width:78px; text-align:right; }
  .sepet .adet{ display:flex; align-items:center; gap:9px; }
  .sepet .adet button{ width:28px; height:28px; border-radius:9px; border:none; background:#25304A; color:#fff; font-size:17px; font-weight:800; line-height:1; }
  .sepet .adet span{ min-width:18px; text-align:center; color:#fff; font-weight:800; }
  .sepet .top{ display:flex; justify-content:space-between; margin-top:11px; padding-top:10px; border-top:1px solid #2D3752; font-weight:800; color:#fff; font-size:15px; }
  .sepet .btns{ display:flex; gap:9px; margin-top:13px; }
  .sepet .onayla{ flex:1; background:linear-gradient(135deg,#16A34A,#22C55E); color:#fff; border:none; border-radius:13px; padding:13px; font-weight:800; font-size:14.5px;
    box-shadow:0 7px 18px rgba(34,197,94,.4); }
  .sepet .onayla:disabled{ opacity:.6; }
  .sepet .vazgec{ background:#2a2030; color:#FCA5A5; border:none; border-radius:13px; padding:13px 16px; font-weight:700; }
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
    <div id="orb" onclick="basla()"></div>
    <div id="durum">Konuşmak için mikrofona ya da daireye dokunun 🎤</div>
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
    <button class="yuvarlak" id="mic" onclick="basla()">🎤</button>
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
const SELAM = 'Hoş geldiniz! Ben {{ $sube->ad ?? "restoranınızın" }} masa asistanınızım. Size nasıl yardımcı olabilirim? Benimle konuşmak için aşağıdaki mikrofon işaretine bir kez dokunmanız yeterli, gerisini bana bırakın.';
let ilkSelamVerildi = false;
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

// "Istiyorum" -> urunu sepete ekle (birikimli akis)
function istiyorum(ad){ sor(ad + ' istiyorum'); }

/* ---- Sepet (Faz 2: konusarak biriken siparis) ---- */
let sepet = [];            // birikimli sepet (mesajlar arasi korunur)
let siparisModu = false;   // siparis akisinda miyiz
let sepetEl = null;
function sepetMerge(items){
  (items||[]).forEach(it=>{
    const ex = sepet.find(x=>x.urun_id===it.urun_id);
    if(ex) ex.adet += it.adet; else sepet.push({urun_id:it.urun_id, ad:it.ad, adet:it.adet, fiyat:it.fiyat});
  });
}
function sepetSet(it){ // adedi AYARLA (topla degil)
  const ex = sepet.find(x=>x.urun_id===it.urun_id);
  if(ex) ex.adet = Math.max(1, it.adet); else sepet.push({urun_id:it.urun_id, ad:it.ad, adet:Math.max(1,it.adet), fiyat:it.fiyat});
}
function sepetCikar(id){ sepet = sepet.filter(x=>x.urun_id!==id); }
function sepetGuncelle(){
  if(sepetEl){ sepetEl.remove(); sepetEl=null; }
  if(!sepet.length) return;
  const tl = v => v.toLocaleString('tr') + ' TL';
  const wrap = document.createElement('div'); wrap.className='sepet';
  const toplam = sepet.reduce((s,k)=>s + k.fiyat*k.adet, 0);
  wrap.innerHTML = '<h4>🧾 Siparişiniz</h4>' +
    sepet.map((k,idx)=>`<div class="satir"><span class="sad">${esc(k.ad)}</span>`
      + `<span class="adet"><button data-i="${idx}" data-d="-1">−</button><span>${k.adet}</span><button data-i="${idx}" data-d="1">+</button></span>`
      + `<span class="sfi">${tl(k.fiyat*k.adet)}</span></div>`).join('')
    + `<div class="top"><span>Toplam</span><span>${tl(toplam)}</span></div>`
    + `<div class="btns"><button class="onayla">✅ Onayla ve Gönder</button><button class="vazgec">Vazgeç</button></div>`;
  wrap.querySelectorAll('.adet button').forEach(b=> b.addEventListener('click', ()=>{
    const i=+b.dataset.i, d=+b.dataset.d;
    sepet[i].adet = Math.max(1, sepet[i].adet + d);
    if(sepet[i].adet===0) sepet.splice(i,1);
    sepetGuncelle();
  }));
  wrap.querySelector('.vazgec').addEventListener('click', ()=>{ sepet=[]; siparisModu=false; sepetGuncelle(); const m='Tamam, siparişinizi iptal ettim. 😊'; ekle('ai', m); konus(m); });
  wrap.querySelector('.onayla').addEventListener('click', finalizeSiparis);
  sohbet.appendChild(wrap); sepetEl = wrap; sohbet.scrollTop = sohbet.scrollHeight;
}
// "hayir / bu kadar / yeterli" -> siparisi bitir
function bitirMi(t){
  const n = (t||'').toLocaleLowerCase('tr').replace(/[^a-zçğıöşü ]/g,' ');
  return /(^| )(hayir|hayır|yok|bu kadar|bukadar|yeterli|tamamdir|tamamdır|bitir|baska yok|başka yok|olmaz|bitti|tamam)( |$)/.test(' '+n+' ');
}
async function finalizeSiparis(){
  if(!sepet.length) return;
  if(sepetEl){ const b=sepetEl.querySelector('.onayla'); if(b){ b.disabled=true; b.textContent='Gönderiliyor…'; } }
  const adet = sepet.reduce((s,k)=>s+k.adet,0);
  let lo = Math.max(15, Math.min(35, Math.round((14 + adet*2)/5)*5)); const hi = lo + 10;
  try{
    const r = await fetch('/api/qr/siparis-gonder', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({masa:MASA, kalemler: JSON.stringify(sepet.map(k=>({urun_id:k.urun_id, adet:k.adet})))})});
    const j = await r.json();
    if(j.ok){
      sepet=[]; siparisModu=false; sepetGuncelle();
      const m = 'Harika, teşekkür ederim! 🎉 Siparişinizi mutfağımıza ilettim; yaklaşık '+lo+'-'+hi+' dakika içinde özenle hazırlanıp masanıza gelecek. Afiyet olsun!';
      ekle('ai', m); await konus(m);
    } else { ekle('ai', j.hata || 'Siparişi gönderemedim, tekrar dener misiniz?'); sepetGuncelle(); }
  }catch(e){ ekle('ai','Bağlantı hatası, tekrar dener misiniz?'); sepetGuncelle(); }
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
let konusuyor = false;
function sesDurdur(){ try{ synth && synth.cancel(); }catch(_){}; try{ sesCalar.pause(); }catch(_){} }
function temizle(t){ return (t||'').replace(/[^\p{L}\p{N}\s.,!?%:₺'"()-]/gu,'').trim(); }
// SADECE Android bedava cihaz sesi kullanir; diger herkes (iPhone/masaustu) Cloud (Puck).
const isAndroid = /android/i.test(navigator.userAgent);
// Cihaz (tarayici) sesi; bitince done() cagirir. Basarili ise true.
function cihazKonus(temiz, done){
  seciSes();
  if(synth && trVoice){
    try{
      synth.cancel();
      const u=new SpeechSynthesisUtterance(temiz); u.lang='tr-TR'; u.voice=trVoice; u.rate=1.0; u.pitch=1.0;
      u.onend = done; u.onerror = done;
      synth.speak(u); return true;
    }catch(e){}
  }
  return false;
}
// Sunucu Google TTS; bitince done() cagirir. Basarili ise true.
async function cloudKonus(temiz, done){
  try{
    const r = await fetch('/api/tts', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({metin:temiz, masa:MASA})});
    const j = await r.json();
    if(j.basarili && j.url){ sesDurdur(); sesCalar = new Audio(j.url); sesCalar.onended = done; sesCalar.onerror = done; sesCalar.play().catch(()=>{}); return true; }
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
let _konusBit = null;                 // aktif konusmayi disaridan kesmek icin
function konusKes(){ if(_konusBit){ const b=_konusBit; _konusBit=null; b(); } }
// KONUS: konusur. bargeIn=true ise VAD ile araya girme: musteri konusmaya baslayinca sesi KESER (dongu dinlemeye gecer). KARARLI.
function konus(t, bargeIn){
  return new Promise(async (resolve)=>{
    const temiz = seseHazirla(t);
    if(!temiz){ resolve(); return; }
    try{ rec && rec.stop(); }catch(_){}
    konusuyor = true;
    let bitti=false, vad=null;
    const emniyet = setTimeout(()=>bit(), Math.min(20000, 2600 + temiz.length*95));
    function bit(){ if(bitti) return; bitti=true; clearTimeout(emniyet); if(vad) clearInterval(vad); _konusBit=null; konusuyor=false; sesDurdur(); resolve(); }
    _konusBit = bit;
    // ARAYA GIRME (VAD): echo-cancelled mikrofonda enerji yukselirse (musteri konusuyor) -> sesi kes; dongu dinlemeye gecer.
    if(bargeIn && _analiz){
      let yuksek=0, taban=0;
      const kalib = setInterval(()=>{ if(bitti){ clearInterval(kalib); return; } taban = Math.max(taban, sesSeviyesi()); }, 50);
      setTimeout(()=>{
        clearInterval(kalib);
        if(bitti) return;
        const esik = Math.max(_vadEsik, taban * 1.5 + 0.02);   // musteri sesi Puck yankisinin UZERINE cikmali
        vad = setInterval(()=>{
          if(bitti) return;
          if(sesSeviyesi() > esik){ if(++yuksek >= 2){ bit(); } }
          else if(yuksek > 0) yuksek--;
        }, 40);
      }, 450);
    }
    let ok = await cloudKonus(temiz, bit);
    if(!ok) ok = cihazKonus(temiz, bit);
    if(!ok) bit();
  });
}

/* ---- Konusma tanima (tarayici STT) — SIRALI DONGU (patron AI akisi) ---- */
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
let rec = null, dinliyorMu = false, sohbetAktif = false;
if(SR){ rec = new SR(); rec.lang='tr-TR'; rec.interimResults=true; rec.continuous=false; rec.maxAlternatives=1; }
// TEK cumle dinle; sessizlik/bitis olunca duyulan metni doner (mic sonra KAPALI).
function dinle(){
  return new Promise((resolve)=>{
    if(!rec){ resolve(''); return; }
    let son=''; let bitti=false; let sess=null; let watch=null;
    function bit(){
      if(bitti) return; bitti=true; clearTimeout(sess); clearTimeout(watch);
      rec.onresult=null; rec.onend=null; rec.onerror=null; rec.onstart=null;
      dinliyorMu=false; orb.classList.remove('dinliyor'); micBtn.classList.remove('dinliyor');
      resolve(son.trim());
    }
    rec.onstart=()=>{ dinliyorMu=true; orb.classList.add('dinliyor'); micBtn.classList.add('dinliyor'); durumEl.textContent='Sizi dinliyorum, buyurun…'; };
    rec.onerror=()=> bit();
    rec.onend=()=> bit();
    rec.onresult=(e)=>{
      let t=''; for(let i=0;i<e.results.length;i++) t += e.results[i][0].transcript;
      son=t; durumEl.textContent = t || '…';
      clearTimeout(sess); sess=setTimeout(()=>{ try{rec.stop()}catch(_){} }, 1600); // konustuktan sonra 1.6sn sessizlik -> bitir
      if(e.results[e.results.length-1].isFinal){ try{rec.stop()}catch(_){} }
    };
    watch = setTimeout(()=>{ try{rec.stop()}catch(_){} }, 12000); // en fazla ~12sn
    // Araya girmede ilk kelime kacmasin -> tanimayi cok hizli baslat
    setTimeout(()=>{ if(bitti) return; try{ rec.start(); }catch(e){ setTimeout(()=>{ try{rec.start()}catch(_){ bit(); } }, 200); } }, 30);
  });
}
function iptalMi(t){ const n=' '+(t||'').toLocaleLowerCase('tr')+' '; return /( )(kapat|kapatabilir|görüşürüz|gorusuruz|hoşça kal|hosca kal|sohbeti kapat)( )/.test(n); }
// Mikrofon izni + VAD (araya girme icin ses enerjisi olcer). Echo-cancelled akis -> asistanin kendi sesi elenir.
let _izinVerildi = false, _audioCtx = null, _analiz = null, _vadBuf = null;
const _vadEsik = 0.055;   // konusma esigi (RMS). Yuksek=daha zor keser, dusuk=daha kolay.
async function micIzniIste(){
  if(_analiz){ try{ _audioCtx && _audioCtx.state === 'suspended' && _audioCtx.resume(); }catch(_){} return; }
  try{
    if(!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) return;
    const stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation:true, noiseSuppression:true, autoGainControl:true } });
    _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    try{ await _audioCtx.resume(); }catch(_){}
    const src = _audioCtx.createMediaStreamSource(stream);
    _analiz = _audioCtx.createAnalyser(); _analiz.fftSize = 512;
    src.connect(_analiz);
    _vadBuf = new Uint8Array(_analiz.fftSize);
    _izinVerildi = true;
  }catch(e){}
}
function sesSeviyesi(){
  if(!_analiz) return 0;
  _analiz.getByteTimeDomainData(_vadBuf);
  let s=0; for(let i=0;i<_vadBuf.length;i++){ const v=(_vadBuf[i]-128)/128; s+=v*v; }
  return Math.sqrt(s/_vadBuf.length);
}
// SOHBET DONGUSU: karsila -> [dinle -> isle -> konus] tekrar (mic konusurken KAPALI)
let _sonBaslaAn = 0;
async function basla(selamla=true){
  const simdi = (window.performance && performance.now) ? performance.now() : (+new Date());
  if(simdi - _sonBaslaAn < 700) return; // ekran+buton ayni anda -> cift tetiklemeyi yut
  _sonBaslaAn = simdi;
  if(!rec){ durumEl.textContent='Bu tarayıcı sesi desteklemiyor, aşağıdan yazabilirsiniz.'; return; }
  if(sohbetAktif){
    if(konusuyor){ konusKes(); return; } // konusurken dokunmak = KES ve dinle (kapatma degil)
    sohbetAktif=false; try{rec.stop()}catch(_){}; sesDurdur(); konusuyor=false; micBtn.classList.remove('acik'); durumEl.textContent='Ekrana dokunup tekrar konuşabilirsiniz'; return;
  }
  sohbetAktif=true; micBtn.classList.add('acik');
  await micIzniIste();   // izin + VAD kurulumu (ilk sefer dialog cikar; izin gelince devam)
  if(selamla){ await konus(ilkSelamVerildi ? 'Buyurun, sizi dinliyorum.' : SELAM, true); ilkSelamVerildi = true; }
  let bos=0;
  while(sohbetAktif){
    const c = await dinle();   // konus VAD ile kesildiyse burada dinler
    if(!sohbetAktif) break;
    if(!c){ if(++bos>=2){ await konus('Başka bir arzunuz yoksa dinlemeyi kapatıyorum. İstediğinizde mikrofona tekrar dokunun.'); break; } await konus('Sizi tam anlayamadım, tekrar eder misiniz?'); continue; }
    bos=0;
    ekle('ben', c);
    if(siparisModu && sepet.length && bitirMi(c)){ await finalizeSiparis(); continue; }
    if(iptalMi(c)){ await konus('Tabii, kapatıyorum. Afiyet olsun!'); break; }
    const cevap = await sunucudanCevap(c);
    if(cevap) await konus(cevap, true);   // konusurken musteri konusursa VAD keser -> dongu tekrar dinler
  }
  sohbetAktif=false; micBtn.classList.remove('acik');
  if(!konusuyor) durumEl.textContent='Dokunup konuşun ya da yazın';
}

/* ---- Sunucuya sor + ekrani guncelle; seslendirilecek metni doner ('' = sessiz) ---- */
async function sunucudanCevap(soru){
  durumEl.textContent='Düşünüyorum…';
  try{
    const r = await fetch('/api/qr/asistan', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
      body:new URLSearchParams({masa:MASA, soru, baglam: window.sonUrun || ''})});
    const j = await r.json();
    if(j.urun_baglam) window.sonUrun = j.urun_baglam;  // son konusulan tekil urunu hatirla
    else if(Array.isArray(j.kategoriler) || (Array.isArray(j.kartlar) && j.kartlar.length>1)) window.sonUrun=''; // kategori/coklu liste -> baglam belirsiz, temizle
    const cevap = j.cevap || 'Bir sorun oldu, tekrar dener misiniz?';
    ekle('ai', cevap);
    if(Array.isArray(j.kategoriler) && j.kategoriler.length) kategorilerEkle(j.kategoriler);
    if(Array.isArray(j.kartlar) && j.kartlar.length) kartlariEkle(j.kartlar);
    if(j.aksiyon==='siparis_basla') siparisModu=true;
    if(j.aksiyon==='sepet_ekle' && Array.isArray(j.eklenen)){ siparisModu=true; sepetMerge(j.eklenen); sepetGuncelle(); }
    if(j.aksiyon==='sepet_ayarla' && Array.isArray(j.eklenen)){ siparisModu=true; j.eklenen.forEach(sepetSet); sepetGuncelle(); }
    if(j.aksiyon==='sepet_cikar' && j.cikar){ sepetCikar(j.cikar.urun_id); sepetGuncelle(); }
    if(j.aksiyon==='siparis_bitir'){ if(siparisModu && sepet.length){ finalizeSiparis(); return ''; } }
    if(j.aksiyon==='garson_cagir') garsonCagir(j.tip || 'garson');
    return (j.seslendir===false) ? '' : cevap;
  }catch(e){ ekle('ai','Bağlantı hatası, tekrar dener misiniz?'); return ''; }
}

/* ---- Yazili / cip gonderimi (mikrofonsuz tek seferlik) ---- */
function gonderMetin(){ const el=document.getElementById('metin'); const t=el.value.trim(); if(!t)return; el.value=''; sor(t); }
async function sor(soru){
  ekle('ben', soru);
  if(siparisModu && sepet.length && bitirMi(soru)){ await finalizeSiparis(); return; }
  const cevap = await sunucudanCevap(soru);
  if(cevap) konus(cevap);
  if(!sohbetAktif && !konusuyor) durumEl.textContent='Dokunup konuşun ya da yazın';
}
async function garsonCagir(tip){
  try{ await fetch('/api/qr/garson-cagir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA,tip})}); }catch(e){}
}

/* ---- Acilis: karsilamayi OTOMATIK seslendir; ilk dokunusta HEMEN dinle (tek dokunus), izin varsa otomatik ---- */
window.addEventListener('load', async ()=>{
  ekle('ai', SELAM);
  ilkSelamVerildi = true;
  const karsilama = konus(SELAM);   // OTOMATIK seslendir
  durumEl.textContent = 'Konuşmak için ekrana dokunun 👆';

  // Mikrofon izni verildiyse: karsilama bitince KENDILIGINDEN dinlemeye gec (dokunmaya gerek yok)
  try{
    if(navigator.permissions && navigator.permissions.query){
      const st = await navigator.permissions.query({name:'microphone'});
      if(st.state === 'granted') karsilama.then(()=>{ if(!sohbetAktif) basla(false); });
    }
  }catch(e){}

  // Ilk dokunus (ekranin herhangi yeri): karsilamayi KES ve HEMEN dinlemeye basla -> TEK dokunus yeterli
  const ilkDokun = ()=>{
    document.removeEventListener('pointerdown', ilkDokun);
    konusKes();                                  // uzun karsilamayi HEMEN kes
    if(!sohbetAktif) basla(false);
  };
  document.addEventListener('pointerdown', ilkDokun, { once:true });
});
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<title>Garson Çağrıları · {{ $sube->ad ?? '' }}</title>
<style>
  *{ box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  body{ min-height:100dvh; background:#0B1020; color:#F1F5F9; }
  header{ display:flex; align-items:center; gap:12px; padding:16px 20px; background:#11172a; border-bottom:1px solid #223; position:sticky; top:0; z-index:2; }
  header .zil{ font-size:26px; }
  header h1{ font-size:18px; font-weight:800; }
  header .sube{ color:#94A3B8; font-size:13px; }
  header .durum{ margin-left:auto; display:flex; align-items:center; gap:8px; color:#94A3B8; font-size:12.5px; }
  header .nokta{ width:9px; height:9px; border-radius:50%; background:#22C55E; box-shadow:0 0 0 0 rgba(34,197,94,.6); animation:cip 1.6s infinite; }
  @keyframes cip{ 0%{ box-shadow:0 0 0 0 rgba(34,197,94,.6);} 70%{ box-shadow:0 0 0 8px rgba(34,197,94,0);} 100%{ box-shadow:0 0 0 0 rgba(34,197,94,0);} }
  header .ses{ background:#1e2740; border:1px solid #2d3752; color:#CBD5E1; font-size:12.5px; font-weight:700; padding:8px 12px; border-radius:20px; cursor:pointer; }

  #liste{ padding:18px; display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }
  .bos{ grid-column:1/-1; text-align:center; color:#64748B; padding:80px 20px; font-size:16px; }
  .cag{ border-radius:20px; padding:18px 18px 16px; border:1px solid #2d3752; background:linear-gradient(160deg,#1a2138,#141a2e);
    box-shadow:0 14px 30px -14px rgba(0,0,0,.7); animation:gir .35s cubic-bezier(.2,.8,.2,1); position:relative; overflow:hidden; }
  .cag.hesap{ background:linear-gradient(160deg,#2a1e10,#1c1408); border-color:#7a5a1e; }
  @keyframes gir{ from{ opacity:0; transform:translateY(12px) scale(.97);} to{ opacity:1; transform:none; } }
  .cag .ust{ display:flex; align-items:center; gap:10px; }
  .cag .ik{ width:46px; height:46px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px;
    background:rgba(244,63,94,.18); flex:0 0 auto; }
  .cag.hesap .ik{ background:rgba(245,158,11,.2); }
  .cag.risk{ background:linear-gradient(160deg,#2a1010,#1c0a0a); border-color:#b91c1c; }
  .cag.risk .ik{ background:rgba(239,68,68,.2); }
  .cag.risk .sure b{ color:#f87171; }
  .cag.acilkart{ background:linear-gradient(160deg,#3a0a0a,#240404); border:2px solid #ef4444; animation:acilyan .8s infinite; }
  .cag.acilkart .ik{ background:rgba(239,68,68,.28); }
  .cag.acilkart .masa,.cag.acilkart .tip{ color:#fecaca; }
  @keyframes acilyan{ 0%,100%{ border-color:#ef4444; box-shadow:0 0 0 0 rgba(239,68,68,.5);} 50%{ border-color:#7f1d1d; box-shadow:0 0 22px 2px rgba(239,68,68,.55);} }
  #acilPop{ position:fixed; inset:0; z-index:30; display:none; align-items:center; justify-content:center; padding:24px; background:rgba(40,4,4,.82); }
  #acilPop.acik{ display:flex; animation:opin .2s ease; }
  #acilPop .kutu{ width:100%; max-width:440px; border-radius:24px; padding:32px 26px; text-align:center; background:linear-gradient(160deg,#3a0a0a,#1a0202); border:3px solid #ef4444; box-shadow:0 24px 70px -18px rgba(239,68,68,.7); animation:acilyan .7s infinite; }
  #acilPop .em{ font-size:60px }
  #acilPop h2{ font-size:26px; font-weight:900; margin-top:10px; color:#fff }
  #acilPop .m{ font-size:16px; color:#fecaca; margin-top:8px; line-height:1.35 }
  #acilPop button{ margin-top:22px; width:100%; border:none; border-radius:16px; padding:16px; font-size:17px; font-weight:900; color:#fff; background:linear-gradient(135deg,#dc2626,#ef4444); cursor:pointer; }
  .cag.odeme{ background:linear-gradient(160deg,#0f2417,#0a1a10); border-color:#1e7a4a; }
  .cag.odeme .ik{ background:rgba(34,197,94,.2); }
  .cag .odetut{ margin-top:12px; font-size:26px; font-weight:900; color:#4ade80; font-variant-numeric:tabular-nums; }
  .cag .odesor{ margin-top:4px; font-size:12px; color:#94A3B8; }
  .cag .odebtn{ display:flex; gap:8px; margin-top:12px; }
  .cag .odebtn button{ flex:1; margin-top:0; padding:13px 4px; font-size:13.5px; }
  #odemePop{ position:fixed; inset:0; z-index:20; display:none; align-items:center; justify-content:center; padding:24px; background:rgba(3,7,18,.72); }
  #odemePop.acik{ display:flex; animation:opin .25s ease; }
  @keyframes opin{ from{ opacity:0 } to{ opacity:1 } }
  #odemePop .kutu{ width:100%; max-width:420px; border-radius:24px; padding:30px 26px; text-align:center; background:linear-gradient(160deg,#0f2417,#0a1a10); border:2px solid #22C55E; box-shadow:0 24px 60px -20px rgba(34,197,94,.6); animation:oppulse 1.2s infinite; }
  @keyframes oppulse{ 0%,100%{ border-color:#22C55E } 50%{ border-color:#14532d } }
  #odemePop .em{ font-size:52px }
  #odemePop h2{ font-size:22px; font-weight:900; margin-top:10px; line-height:1.25 }
  #odemePop .m{ font-size:15px; color:#CBD5E1; margin-top:8px; line-height:1.35 }
  #odemePop .t{ font-size:34px; font-weight:900; color:#4ade80; margin-top:14px; font-variant-numeric:tabular-nums }
  #odemePop button{ margin-top:22px; width:100%; border:none; border-radius:16px; padding:15px; font-size:16px; font-weight:800; color:#fff; background:linear-gradient(135deg,#16A34A,#22C55E); cursor:pointer; }
  .cag .masa{ font-size:22px; font-weight:900; }
  .cag .tip{ font-size:13.5px; color:#CBD5E1; font-weight:700; margin-top:1px; }
  .cag .sure{ margin-left:auto; text-align:right; }
  .cag .sure b{ display:block; font-size:20px; font-weight:800; color:#F6CE63; font-variant-numeric:tabular-nums; }
  .cag .sure i{ font-style:normal; font-size:10.5px; color:#64748B; }
  .cag button{ width:100%; margin-top:15px; border:none; border-radius:14px; padding:14px; font-size:15px; font-weight:800; color:#fff;
    background:linear-gradient(135deg,#16A34A,#22C55E); box-shadow:0 8px 20px rgba(34,197,94,.4); cursor:pointer; }
  .cag button:active{ transform:translateY(1px); }
  .cag.geciken{ animation:yanip 1.1s infinite; } /* 60sn+ bekleyen dikkat ceker */
  @keyframes yanip{ 0%,100%{ border-color:#f43f5e; } 50%{ border-color:#2d3752; } }
</style>
</head>
<body>
<header>
  <span class="zil">🔔</span>
  <div><h1>Garson Çağrıları</h1><div class="sube">{{ $sube->ad ?? '' }}</div></div>
  <div class="durum"><span class="nokta"></span><span id="dstr">Canlı dinleniyor…</span>
    <button class="ses" id="sesBtn" onclick="sesAc()">🔇 Sesi Aç</button></div>
</header>
<div id="liste"><div class="bos">Bekleyen çağrı yok. Yeni çağrılar buraya anında düşer. 🔔</div></div>

<!-- Kasada odeme tercihi bildirimi (titresimli, dikkat cekici) -->
<div id="odemePop">
  <div class="kutu">
    <div class="em">💰</div>
    <h2><span id="op-masa">Masa</span> kasada ödemeyi tercih etti</h2>
    <div class="m">İşletme kuralınıza göre: masaya gidip ödemeyi alın ya da müşteriyi kasaya yönlendirin.</div>
    <div class="t" id="op-tutar">0 TL</div>
    <button onclick="odemePopupKapat()">Tamam, ilgileniyorum</button>
  </div>
</div>

<!-- ACIL DURUM alarmi (en guclu: kirmizi + surekli titresim + zil) -->
<div id="acilPop">
  <div class="kutu">
    <div class="em">🚨</div>
    <h2>ACİL DURUM — <span id="ap-masa">Masa</span></h2>
    <div class="m">Müşteri acil yardım istedi. LÜTFEN HEMEN masaya gidin; gerekiyorsa 112’yi arayın.</div>
    <button onclick="acilPopupKapat()">Gördüm, gidiyorum</button>
  </div>
</div>

<script>
const SUBE = @json($sube->id ?? 0);
let _sesAcik = false, _biliniyor = new Set(), _ilk = true, _sonZil = 0;
let _actx = null;

function sesAc(){
  _sesAcik = !_sesAcik;
  document.getElementById('sesBtn').textContent = _sesAcik ? '🔔 Ses Açık' : '🔇 Sesi Aç';
  if(_sesAcik){ try{ _actx = _actx || new (window.AudioContext||window.webkitAudioContext)(); _actx.resume(); }catch(e){} bipCal(); }
}
// Tek zil salvosu: YUKSEK sesli, delici (kare dalga) telefon-zili gibi 4 vurus
function zilSalvo(gecikme){
  if(!_sesAcik || !_actx) return;
  try{
    const master=_actx.createGain(); master.gain.value=0.85; master.connect(_actx.destination);
    [[988,0],[784,0.17],[988,0.34],[784,0.51]].forEach(([f,dt])=>{
      const o=_actx.createOscillator(), g=_actx.createGain();
      o.type='square'; o.frequency.value=f;
      o.connect(g); g.connect(master);
      const t=_actx.currentTime + (gecikme||0) + dt;
      g.gain.setValueAtTime(0.0001,t); g.gain.exponentialRampToValueAtTime(0.9,t+0.02); g.gain.exponentialRampToValueAtTime(0.0001,t+0.15);
      o.start(t); o.stop(t+0.17);
    });
  }catch(e){}
}
// Yuksek sesli, TEKRARLAYAN zil (yogunlukta iskalanmasin) — kez kadar salvo
function zilCal(kez){ kez=kez||3; for(let i=0;i<kez;i++) zilSalvo(i*0.8); }
function bipCal(){ zilSalvo(0); }  // geriye donuk uyum
function titret(p){ try{ if(navigator.vibrate) navigator.vibrate(p); }catch(e){} }
// Kasada odeme tercihi: tam ekran dikkat popup + GUCLU titresim + yuksek zil
function odemePopup(c){
  zilCal(4); titret([600,200,600,200,600,200,900]);
  const p=document.getElementById('odemePop'); if(!p) return;
  document.getElementById('op-masa').textContent=c.masa||'Masa';
  document.getElementById('op-tutar').textContent=(c.tutar||0).toLocaleString('tr')+' TL';
  p.classList.add('acik');
}
function odemePopupKapat(){ const p=document.getElementById('odemePop'); if(p) p.classList.remove('acik'); }
// ACIL DURUM: en guclu alarm — kirmizi popup + uzun surekli titresim + coklu zil
function acilPopup(c){
  try{ zilCal(6); }catch(_){}
  titret([800,200,800,200,800,200,1000,200,1000]);
  const p=document.getElementById('acilPop'); if(!p) return;
  const m=document.getElementById('ap-masa'); if(m) m.textContent=c.masa||'Masa';
  p.classList.add('acik');
}
function acilPopupKapat(){ const p=document.getElementById('acilPop'); if(p) p.classList.remove('acik'); }
function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
function tipYazi(t){ return t==='acil' ? 'ACİL DURUM' : (t==='tasima' ? 'Taşıma talebi' : (t==='odeme' ? 'Ödeme alınacak' : (t==='hesap' ? 'Hesap istiyor' : (t==='siparis' ? 'Sipariş verdi' : 'Garson çağırıyor')))); }
function tipIkon(t){ return t==='acil' ? '🚨' : (t==='tasima' ? '🔀' : (t==='odeme' ? '💰' : (t==='hesap' ? '💳' : (t==='siparis' ? '🧾' : '🔔')))); }
function sureYazi(sn){ sn=Math.max(0,Math.round(sn)); if(sn<60) return sn+' sn'; const d=Math.floor(sn/60); return d+' dk'; }

async function cek(){
  try{
    const r = await fetch('/api/garson-cagrilari?sube='+SUBE, {cache:'no-store'});
    const j = await r.json();
    const liste = (j.ok && Array.isArray(j.cagrilar)) ? j.cagrilar : [];
    document.getElementById('dstr').textContent = 'Canlı · '+ (j.sunucu_saat||'');
    const yeniler = liste.filter(c=> !_biliniyor.has(c.id));
    const _now = Date.now();
    if(yeniler.length && !_ilk){ zilCal(3); titret([500,180,500,180,700]); _sonZil=_now; }
    else if(liste.length && !_ilk && (_now-_sonZil>18000)){ zilSalvo(0); titret([300,150,300]); _sonZil=_now; } // bekleyen cagri varken hatirlatma
    const yeniAcil = yeniler.find(c=> c.tip==='acil');
    if(yeniAcil && !_ilk) acilPopup(yeniAcil);       // ACIL DURUM -> en guclu alarm (kirmizi popup + surekli titresim + zil)
    const yeniOdeme = yeniler.find(c=> c.tip==='odeme');
    if(yeniOdeme && !_ilk && !yeniAcil) odemePopup(yeniOdeme);   // kasada odeme tercihi -> dikkat cekici popup + guclu titresim
    _biliniyor = new Set(liste.map(c=>c.id));
    _ilk = false;
    ciz(liste, (j.ok && Array.isArray(j.riskli)) ? j.riskli : []);
  }catch(e){ document.getElementById('dstr').textContent = 'Bağlantı bekleniyor…'; }
}
function ciz(liste, riskli){
  riskli = riskli || [];
  const w = document.getElementById('liste');
  if(!liste.length && !riskli.length){ w.innerHTML='<div class="bos">Bekleyen çağrı yok. Yeni çağrılar buraya anında düşer. 🔔</div>'; return; }
  w.innerHTML='';
  // KACAK RADARI: uzun suredir acik + odenmemis masalar (odemeden ayrilma riski)
  riskli.forEach(rk=>{
    const el=document.createElement('div');
    el.className='cag risk geciken';
    el.innerHTML = `<div class="ust"><div class="ik">⚠️</div>`
      + `<div><div class="masa">${esc(rk.masa)}</div><div class="tip">Ödemeden ayrılma riski · ${rk.dakika} dk açık</div></div>`
      + `<div class="sure"><b>${(rk.kalan||0).toLocaleString('tr')} ₺</b><i>ödenmemiş</i></div></div>`;
    w.appendChild(el);
  });
  liste.forEach(c=>{
    const el=document.createElement('div');
    const odeme = c.tip==='odeme';
    el.className='cag '+(c.tip==='acil'?'acilkart geciken':(odeme?'odeme':(c.tip==='hesap'?'hesap':'')))+(c.saniye>=60?' geciken':'');
    let alt;
    if(odeme){
      alt = `<div class="odetut">${(c.tutar||0).toLocaleString('tr')} TL</div>`
        + `<div class="odesor">Nasıl tahsil edildi?</div>`
        + `<div class="odebtn"><button data-t="nakit">💵 Nakit</button><button data-t="kredi">💳 Kredi</button><button data-t="yemek_karti">🍽️ Yemek K.</button></div>`;
    } else {
      alt = `<button>✓ Karşılandı</button>`;
    }
    el.innerHTML = `<div class="ust"><div class="ik">${tipIkon(c.tip)}</div>`
      + `<div><div class="masa">${esc(c.masa)}${c.tip==='tasima'&&c.hedef?(' → '+esc(c.hedef)):''}</div><div class="tip">${tipYazi(c.tip)}${c.tip==='tasima'?' · uygulamadan onaylayın':''}</div></div>`
      + `<div class="sure"><b>${sureYazi(c.saniye)}</b><i>${esc(c.saat)}</i></div></div>`
      + alt;
    if(odeme){
      el.querySelectorAll('.odebtn button').forEach(b=> b.addEventListener('click', ()=> tahsil(c, b.dataset.t, el)));
    } else {
      el.querySelector('button').addEventListener('click', ()=> kapat(c.id, el));
    }
    w.appendChild(el);
  });
}
// Kasada/garsonda odemeyi secilen yontemle tahsil et (nakit/kredi/yemek karti)
async function tahsil(c, tip, el){
  el.style.opacity='.5'; el.querySelectorAll('button').forEach(b=> b.disabled=true);
  try{
    const r = await fetch('/api/qr/kasa-tahsil',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({odeme_token:c.odeme_token||'', cagri_id:c.id, odeme_tip:tip})});
    const j = await r.json();
    if(!j.ok){ el.style.opacity='1'; el.querySelectorAll('button').forEach(b=> b.disabled=false); document.getElementById('dstr').textContent = j.hata || 'İşlem yapılamadı'; return; }
  }catch(e){ el.style.opacity='1'; el.querySelectorAll('button').forEach(b=> b.disabled=false); return; }
  _biliniyor.delete(c.id);
  cek();
}
async function kapat(id, el){
  el.style.opacity='.4';
  try{ await fetch('/api/garson-cagri-kapat',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({id})}); }catch(e){}
  _biliniyor.delete(id);
  cek();
}
cek(); setInterval(cek, 4000);
</script>
</body>
</html>

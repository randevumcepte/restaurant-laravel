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

<script>
const SUBE = @json($sube->id ?? 0);
let _sesAcik = false, _biliniyor = new Set(), _ilk = true;
let _actx = null;

function sesAc(){
  _sesAcik = !_sesAcik;
  document.getElementById('sesBtn').textContent = _sesAcik ? '🔔 Ses Açık' : '🔇 Sesi Aç';
  if(_sesAcik){ try{ _actx = _actx || new (window.AudioContext||window.webkitAudioContext)(); _actx.resume(); }catch(e){} bipCal(); }
}
function bipCal(){
  if(!_sesAcik || !_actx) return;
  try{
    for(let i=0;i<2;i++){
      const o=_actx.createOscillator(), g=_actx.createGain();
      o.type='sine'; o.frequency.value = i? 660:880;
      o.connect(g); g.connect(_actx.destination);
      const t=_actx.currentTime + i*0.22;
      g.gain.setValueAtTime(0.001,t); g.gain.exponentialRampToValueAtTime(0.35,t+0.02); g.gain.exponentialRampToValueAtTime(0.001,t+0.2);
      o.start(t); o.stop(t+0.22);
    }
  }catch(e){}
}
function esc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
function tipYazi(t){ return t==='hesap' ? 'Hesap istiyor' : (t==='siparis' ? 'Sipariş verdi' : 'Garson çağırıyor'); }
function tipIkon(t){ return t==='hesap' ? '💳' : (t==='siparis' ? '🧾' : '🔔'); }
function sureYazi(sn){ sn=Math.max(0,Math.round(sn)); if(sn<60) return sn+' sn'; const d=Math.floor(sn/60); return d+' dk'; }

async function cek(){
  try{
    const r = await fetch('/api/garson-cagrilari?sube='+SUBE, {cache:'no-store'});
    const j = await r.json();
    const liste = (j.ok && Array.isArray(j.cagrilar)) ? j.cagrilar : [];
    document.getElementById('dstr').textContent = 'Canlı · '+ (j.sunucu_saat||'');
    const yeniVar = liste.some(c=> !_biliniyor.has(c.id));
    if(yeniVar && !_ilk) bipCal();
    _biliniyor = new Set(liste.map(c=>c.id));
    _ilk = false;
    ciz(liste);
  }catch(e){ document.getElementById('dstr').textContent = 'Bağlantı bekleniyor…'; }
}
function ciz(liste){
  const w = document.getElementById('liste');
  if(!liste.length){ w.innerHTML='<div class="bos">Bekleyen çağrı yok. Yeni çağrılar buraya anında düşer. 🔔</div>'; return; }
  w.innerHTML='';
  liste.forEach(c=>{
    const el=document.createElement('div');
    el.className='cag '+(c.tip==='hesap'?'hesap':'')+(c.saniye>=60?' geciken':'');
    el.innerHTML = `<div class="ust"><div class="ik">${tipIkon(c.tip)}</div>`
      + `<div><div class="masa">${esc(c.masa)}</div><div class="tip">${tipYazi(c.tip)}</div></div>`
      + `<div class="sure"><b>${sureYazi(c.saniye)}</b><i>${esc(c.saat)}</i></div></div>`
      + `<button>✓ Karşılandı</button>`;
    el.querySelector('button').addEventListener('click', ()=> kapat(c.id, el));
    w.appendChild(el);
  });
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

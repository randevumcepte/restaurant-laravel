<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<title>QR Menü Rengi · {{ $sube->ad ?? 'Restoran' }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800;900&display=swap');
  :root{ --ana:#F6DFA0; --ana2:#E9C46A; --ana3:#C9962F; --ink:#3a2600; --glow:rgba(233,196,106,.16);
    --gold:#E9C46A; --gold2:#C9962F; --cizgi:rgba(233,196,106,.20); --serif:'Playfair Display',Georgia,serif; }
  *{ box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; }
  body{ min-height:100dvh; color:#F3E9EE; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:#0b090c;
    background-image:
      radial-gradient(900px 620px at 88% -8%, var(--glow), transparent 58%),
      radial-gradient(1200px 900px at 60% 0%, #1b1620 0%, #130f15 46%, #0b090c 100%);
    padding:22px 16px 40px; }
  .wrap{ max-width:900px; margin:0 auto; }
  .bas{ text-align:center; margin-bottom:6px; }
  .bas .toque{ font-size:30px; }
  .bas h1{ font-family:var(--serif); font-size:26px; font-weight:800; margin-top:4px;
    background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
  .bas p{ color:#B49CB6; font-size:13.5px; margin-top:6px; }
  .bas .sub{ color:var(--gold2); font-size:12px; font-weight:700; margin-top:3px; }

  .grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:22px; }
  @media(min-width:680px){ .grid{ grid-template-columns:1fr 1fr 1fr; } }
  .kart{ position:relative; border-radius:20px; overflow:hidden; cursor:pointer; border:1px solid var(--cizgi);
    background:#171319; box-shadow:0 14px 30px -18px rgba(0,0,0,.8); transition:transform .15s, border-color .15s; }
  .kart:active{ transform:scale(.98); }
  .kart.sec{ border-color:var(--gold); box-shadow:0 0 0 2px var(--gold), 0 16px 34px -16px rgba(0,0,0,.85); }
  .kart .sw{ height:96px; position:relative; }
  .kart .sw .g{ position:absolute; left:14px; bottom:12px; width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F); box-shadow:0 4px 12px rgba(0,0,0,.4); }
  .kart .sw .em{ position:absolute; right:12px; top:10px; font-size:24px; filter:drop-shadow(0 3px 6px rgba(0,0,0,.5)); }
  .kart .in{ padding:12px 14px 14px; }
  .kart .in b{ font-size:14.5px; font-weight:800; }
  .kart .in .btn{ display:inline-block; margin-top:9px; font-size:11.5px; font-weight:800; padding:6px 12px; border-radius:20px; }
  .kart .tik{ position:absolute; top:10px; left:10px; width:26px; height:26px; border-radius:50%; display:none;
    align-items:center; justify-content:center; background:var(--gold); color:#3a2600; font-size:15px; font-weight:900; }
  .kart.sec .tik{ display:flex; }

  /* CANLI ONIZLEME */
  .onizlik{ margin-top:30px; }
  .onizlik h2{ font-family:var(--serif); font-size:18px; color:var(--gold); margin-bottom:12px; text-align:center; }
  .prev{ max-width:340px; margin:0 auto; border-radius:22px; overflow:hidden; border:1px solid var(--cizgi);
    background:linear-gradient(160deg, rgba(233,196,106,.07), rgba(18,13,18,.8)); padding:16px; }
  .prev .pcard{ position:relative; height:150px; border-radius:16px; overflow:hidden; }
  .prev .pcard img{ width:100%; height:100%; object-fit:cover; }
  .prev .pcard .sh{ position:absolute; inset:0; background:linear-gradient(180deg,transparent 40%,rgba(0,0,0,.85)); }
  .prev .pcard .t{ position:absolute; left:12px; right:12px; bottom:10px; display:flex; align-items:flex-end; justify-content:space-between; }
  .prev .pcard .t .ad{ font-weight:800; font-size:15px; text-shadow:0 2px 8px rgba(0,0,0,.7); }
  .prev .pcard .t .ad small{ display:block; color:var(--gold); font-weight:800; }
  .prev .pcard .t .art{ width:34px; height:34px; border-radius:11px; border:none; font-size:20px; color:var(--ink);
    background:linear-gradient(135deg,var(--ana),var(--ana2),var(--ana3)); box-shadow:0 6px 16px rgba(0,0,0,.5); }
  .prev .satir{ display:flex; gap:9px; margin-top:14px; }
  .prev .satir .ara{ flex:1; background:rgba(0,0,0,.3); border:1px solid var(--cizgi); border-radius:13px; padding:11px 13px; color:#9a8a9c; font-size:13px; }
  .prev .satir .go{ width:46px; border:none; border-radius:13px; font-size:17px; color:var(--ink);
    background:linear-gradient(135deg,var(--ana),var(--ana2),var(--ana3)); box-shadow:0 6px 14px rgba(0,0,0,.4); }
  .prev .cip{ display:inline-flex; align-items:center; gap:6px; margin-top:14px; padding:9px 14px; border-radius:14px; font-size:12.5px; font-weight:800;
    color:var(--ink); background:linear-gradient(135deg,var(--ana),var(--ana2),var(--ana3)); }

  .ac{ display:block; max-width:340px; margin:22px auto 0; text-align:center; text-decoration:none; font-weight:800; font-size:14.5px;
    padding:15px; border-radius:16px; color:#3a2600; background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F); box-shadow:0 12px 26px rgba(201,150,47,.4); }
  #kayit{ position:fixed; left:50%; transform:translateX(-50%); bottom:22px; background:#16351f; color:#c9f5d6; border:1px solid #2e7d4d;
    padding:12px 18px; border-radius:14px; font-size:13.5px; font-weight:700; opacity:0; transition:.25s; pointer-events:none; }

  /* OZEL RENK + LOGO */
  .ozel{ margin-top:16px; border:1px solid var(--cizgi); border-radius:20px; background:#171319; padding:16px 16px 18px; transition:border-color .15s, box-shadow .15s; }
  .ozel.sec{ border-color:var(--gold); box-shadow:0 0 0 2px var(--gold); }
  .ozel h3{ font-family:var(--serif); font-size:17px; color:var(--gold); display:flex; align-items:center; gap:8px; }
  .ozel p{ color:#B49CB6; font-size:12.5px; margin:5px 0 14px; line-height:1.4; }
  .ozel-row{ display:flex; gap:12px; flex-wrap:wrap; }
  .ozel-renk{ flex:1; min-width:150px; display:flex; align-items:center; gap:10px; background:rgba(0,0,0,.28); border:1px solid var(--cizgi); border-radius:14px; padding:10px 12px; cursor:pointer; }
  .ozel-renk span{ font-size:12px; color:#9a8a9c; flex:1; }
  .ozel-renk input[type=color]{ width:36px; height:36px; border:none; background:none; border-radius:9px; cursor:pointer; padding:0; }
  .ozel-renk input[type=color]::-webkit-color-swatch-wrapper{ padding:0; }
  .ozel-renk input[type=color]::-webkit-color-swatch{ border:1px solid rgba(255,255,255,.2); border-radius:9px; }
  .ozel-renk b{ font-size:12px; color:#F3E9EE; min-width:66px; letter-spacing:.5px; }
  .ozel-logo{ margin-top:12px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
  .ozel-logo button{ background:rgba(233,196,106,.12); border:1px dashed var(--gold2); color:var(--gold); border-radius:13px; padding:11px 16px; font-size:13px; font-weight:800; cursor:pointer; }
  .ozel-logo button:active{ transform:scale(.98); }
  .ozel-logo img{ width:46px; height:46px; object-fit:contain; border-radius:11px; background:#fff; padding:4px; }
  .ozel-uygula{ margin-top:14px; width:100%; border:none; border-radius:14px; padding:14px; font-size:14.5px; font-weight:800; color:var(--ink); cursor:pointer;
    background:linear-gradient(135deg,var(--ana),var(--ana2),var(--ana3)); box-shadow:0 10px 24px rgba(0,0,0,.45); }
  .ozel-uygula:active{ transform:scale(.99); }
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="toque">👨‍🍳</div>
    <h1>{{ $sube->ad ?? 'Restoran' }}</h1>
    <p>QR menünüzün rengini seçin — anında kaydedilir</p>
    <div class="sub">RENK KARTELASI</div>
  </div>

  <div class="grid" id="grid">
    @foreach($temalar as $key => $t)
    <div class="kart {{ $key === $secili ? 'sec' : '' }}" data-key="{{ $key }}"
         data-ana="{{ $t['ana'] }}" data-ana2="{{ $t['ana2'] }}" data-ana3="{{ $t['ana3'] }}" data-ink="{{ $t['ink'] }}" data-glow="{{ $t['glow'] }}"
         onclick="sec(this)">
      <div class="tik">✓</div>
      <div class="sw" style="background:linear-gradient(135deg,{{ $t['ana'] }},{{ $t['ana3'] }})">
        <div class="g"></div><div class="em">{{ $t['emoji'] }}</div>
      </div>
      <div class="in">
        <b>{{ $t['ad'] }}</b>
        <div class="btn" style="background:linear-gradient(135deg,{{ $t['ana'] }},{{ $t['ana3'] }});color:{{ $t['ink'] }}">Örnek Buton</div>
      </div>
    </div>
    @endforeach
  </div>

  {{-- ÖZEL RENK + LOGO --}}
  <div class="ozel {{ $ozel ?? false ? 'sec' : '' }}" id="ozelPanel">
    <h3>🎨 Kendi Renginiz</h3>
    <p>Markanın rengini seç, ya da <b style="color:var(--gold)">logonu yükle</b> — sistem logondan baskın rengi bulup otomatik uygular.</p>
    <div class="ozel-row">
      <label class="ozel-renk"><span>Ana Renk (butonlar)</span>
        <input type="color" id="ozelAna" value="{{ ($ozel ?? false) ? ($sube->tema_renk ?? '#C41E3A') : '#C41E3A' }}" oninput="ozelDegis()">
        <b id="ozelAnaHex">{{ ($ozel ?? false) ? ($sube->tema_renk ?? '#C41E3A') : '#C41E3A' }}</b>
      </label>
      <label class="ozel-renk"><span>Detay (altın yerine)</span>
        <input type="color" id="ozelDetay" value="{{ ($ozel ?? false) ? ($sube->tema_renk2 ?? ($sube->tema_renk ?? '#E9C46A')) : '#E9C46A' }}" oninput="ozelDegis()">
        <b id="ozelDetayHex">{{ ($ozel ?? false) ? ($sube->tema_renk2 ?? '#E9C46A') : '#E9C46A' }}</b>
      </label>
    </div>
    <div class="ozel-logo">
      <input type="file" id="ozelLogo" accept="image/*" style="display:none" onchange="ozelLogoYukle(event)">
      <button type="button" onclick="document.getElementById('ozelLogo').click()">📤 Logo Yükle → Rengi Otomatik Al</button>
      <img id="ozelLogoImg" alt="logo" style="display:none">
    </div>
    <button type="button" class="ozel-uygula" onclick="ozelKaydet()">Bu Rengi Uygula &amp; Kaydet</button>
  </div>

  <div class="onizlik">
    <h2>Canlı Önizleme</h2>
    <div class="prev">
      <div class="pcard">
        <img src="https://images.unsplash.com/photo-1600891964092-4316c288032e?auto=format&fit=crop&w=600&q=72" alt="" onerror="this.style.display='none'">
        <div class="sh"></div>
        <div class="t"><div class="ad">Izgara Antrikot<small>650 TL</small></div><button class="art">+</button></div>
      </div>
      <div class="satir"><div class="ara">Ne yemek istersiniz?</div><button class="go">🔍</button></div>
      <div class="cip">🍽️ Tüm Menüler</div>
    </div>
  </div>

  <a class="ac" href="/menu-onizle" target="_blank">Menüyü Aç / Önizle →</a>
</div>

<div id="kayit">✓ Kaydedildi</div>

<script>
const SUBE = @json($sube->id ?? 0);
function uygula(el){
  const r=document.documentElement.style;
  r.setProperty('--ana', el.dataset.ana); r.setProperty('--ana2', el.dataset.ana2);
  r.setProperty('--ana3', el.dataset.ana3); r.setProperty('--ink', el.dataset.ink); r.setProperty('--glow', el.dataset.glow);
  r.setProperty('--gold', '#E9C46A'); r.setProperty('--gold2', '#C9962F'); // preset'te altın detay sabit
}
function sec(el){
  document.querySelectorAll('.kart').forEach(k=>k.classList.remove('sec'));
  var op=document.getElementById('ozelPanel'); if(op) op.classList.remove('sec');
  el.classList.add('sec');
  uygula(el);
  fetch('/tema/kaydet',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({tema:el.dataset.key, sube:SUBE})})
    .then(x=>x.json()).then(j=>{ if(j.ok) mesaj(); });
}
function mesaj(t){ const k=document.getElementById('kayit'); k.textContent=t||'✓ Kaydedildi'; k.style.opacity='1'; clearTimeout(k._z); k._z=setTimeout(()=>k.style.opacity='0',2400); }

// ── ÖZEL RENK + LOGO ──
function _hx(v){ return ('0'+Math.max(0,Math.min(255,Math.round(v))).toString(16)).slice(-2); }
// tek hex'ten palet (resto_renk_tonlar ile birebir mantık)
function _tonlar(hex){
  hex=hex.replace('#','');
  const r=parseInt(hex.slice(0,2),16),g=parseInt(hex.slice(2,4),16),b=parseInt(hex.slice(4,6),16);
  const mix=(t,h)=>'#'+[r,g,b].map(c=>_hx(c+(h-c)*t)).join('').toUpperCase();
  const lum=(0.299*r+0.587*g+0.114*b)/255;
  return { ana:'#'+hex.toUpperCase(), ana2:mix(0.22,255), ana3:mix(0.26,0), ink: lum>0.62?'#3a2600':'#ffffff', glow:'rgba('+r+','+g+','+b+',.15)' };
}
function ozelUygulaPrev(){
  const ana=document.getElementById('ozelAna').value, detay=document.getElementById('ozelDetay').value;
  const t=_tonlar(ana), r=document.documentElement.style;
  r.setProperty('--ana',t.ana); r.setProperty('--ana2',t.ana2); r.setProperty('--ana3',t.ana3);
  r.setProperty('--ink',t.ink); r.setProperty('--glow',t.glow);
  r.setProperty('--gold', detay); r.setProperty('--gold2', detay);
}
function ozelDegis(){
  document.getElementById('ozelAnaHex').textContent=document.getElementById('ozelAna').value.toUpperCase();
  document.getElementById('ozelDetayHex').textContent=document.getElementById('ozelDetay').value.toUpperCase();
  document.querySelectorAll('.kart').forEach(k=>k.classList.remove('sec'));
  document.getElementById('ozelPanel').classList.add('sec');
  ozelUygulaPrev();
}
function ozelKaydet(){
  const ana=document.getElementById('ozelAna').value, detay=document.getElementById('ozelDetay').value;
  document.querySelectorAll('.kart').forEach(k=>k.classList.remove('sec'));
  document.getElementById('ozelPanel').classList.add('sec');
  ozelUygulaPrev();
  fetch('/tema/kaydet',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({tema:'ozel', renk:ana, renk2:detay, sube:SUBE})})
    .then(x=>x.json()).then(j=>{ if(j.ok) mesaj('✓ Özel renk kaydedildi'); else alert(j.hata||'Kaydedilemedi'); });
}
function ozelLogoYukle(e){
  const f=e.target.files && e.target.files[0]; if(!f) return;
  const rd=new FileReader();
  rd.onload=function(ev){
    const im=new Image();
    im.onload=function(){
      const img=document.getElementById('ozelLogoImg'); img.src=im.src; img.style.display='';
      const renk=_logoRenk(im);
      if(renk){ document.getElementById('ozelAna').value=renk; ozelDegis(); mesaj('Logodan renk alındı: '+renk+' — kaydetmek için Uygula'); }
      else { alert('Logodan belirgin bir renk bulunamadı. Rengi elle seçebilirsin.'); }
    };
    im.src=ev.target.result;
  };
  rd.readAsDataURL(f);
}
// logodan baskın (doygun) rengi bul — canvas, AI yok
function _logoRenk(im){
  try{
    const c=document.createElement('canvas'), s=48; c.width=s; c.height=s;
    const x=c.getContext('2d'); x.drawImage(im,0,0,s,s);
    const d=x.getImageData(0,0,s,s).data, bk={};
    for(let i=0;i<d.length;i+=4){
      const r=d[i],g=d[i+1],b=d[i+2],a=d[i+3]; if(a<128) continue;
      const mx=Math.max(r,g,b),mn=Math.min(r,g,b), sat=mx===0?0:(mx-mn)/mx, lum=(0.299*r+0.587*g+0.114*b)/255;
      if(lum>0.92||lum<0.08||sat<0.18) continue; // beyaz/siyah/gri ele
      const key=(r>>4)+'-'+(g>>4)+'-'+(b>>4);
      if(!bk[key]) bk[key]={n:0,r:0,g:0,b:0,sc:0};
      const o=bk[key]; o.n++; o.r+=r; o.g+=g; o.b+=b; o.sc+=sat;
    }
    let best=null;
    for(const k in bk){ const o=bk[k]; if(!best||o.sc>best.sc) best={sc:o.sc,r:o.r/o.n,g:o.g/o.n,b:o.b/o.n}; }
    if(!best) return null;
    return '#'+_hx(best.r)+_hx(best.g)+_hx(best.b);
  }catch(e){ return null; }
}

// açılışta: özel seçiliyse özel paleti, değilse preset paleti uygula (KAYIT YOK)
(function(){
  const s=document.querySelector('.kart.sec');
  if(s){ uygula(s); }
  else if(document.getElementById('ozelPanel') && document.getElementById('ozelPanel').classList.contains('sec')){ ozelUygulaPrev(); }
})();
</script>
</body>
</html>

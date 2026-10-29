<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Deneyiminiz nasıldı? · {{ $sube->ad ?? 'Restoran' }}</title>
<style>
  :root{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--amber:#F59E0B;--kirmizi:#F43F5E;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e8ecf3}
  *{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;-webkit-tap-highlight-color:transparent}
  body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(170deg,#ffffff,#f3f0fb);padding:18px}
  .kart{width:100%;max-width:400px;display:flex;flex-direction:column;align-items:center;text-align:center}
  .sayfa{width:100%;display:none;flex-direction:column;align-items:center;animation:in .4s cubic-bezier(.22,.68,0,1.2)}
  .sayfa.akt{display:flex}
  @keyframes in{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  .logo{width:64px;height:64px;border-radius:19px;background:linear-gradient(135deg,#FB923C,#F43F5E);display:flex;align-items:center;justify-content:center;font-size:30px;box-shadow:0 10px 24px -6px rgba(244,63,94,.5);margin-bottom:10px}
  .ad{font-size:19px;font-weight:800;color:var(--ink)}
  .alt{font-size:12.5px;color:var(--sub);margin-bottom:4px}
  .soru{font-size:23px;font-weight:800;color:var(--ink);margin:30px 0 4px;line-height:1.25}
  .soru-alt{font-size:13px;color:var(--sub);margin-bottom:26px}
  .yuzler{display:flex;gap:6px;justify-content:center;width:100%}
  .yuz{flex:1;background:#fff;border:2px solid var(--line);border-radius:18px;padding:13px 2px 9px;cursor:pointer;transition:.16s;display:flex;flex-direction:column;align-items:center;gap:5px}
  .yuz:active{transform:scale(.92)}
  .yuz .e{font-size:30px;line-height:1}
  .yuz .l{font-size:9.5px;color:var(--sub);font-weight:600}
  .ipucu{margin-top:26px;font-size:11.5px;color:var(--sub2)}
  .buyuk{font-size:66px;margin:18px 0 2px;animation:pop .5s cubic-bezier(.34,1.56,.64,1) both}
  @keyframes pop{from{transform:scale(0)}to{transform:scale(1)}}
  .baslik{font-size:24px;font-weight:900;color:var(--ink);margin-bottom:8px}
  .aciklama{font-size:16px;font-weight:600;color:var(--sub);line-height:1.4;max-width:230px;margin-bottom:28px}
  .gwrap{position:relative;width:100%;display:flex;justify-content:center}
  .gwrap::before{content:'';position:absolute;inset:-7px;border-radius:24px;z-index:0;filter:blur(11px);background:linear-gradient(90deg,#4285F4,#EA4335,#FBBC05,#34A853,#4285F4);background-size:300% 100%;animation:gglow 3s linear infinite,gpulse 1.7s ease-in-out infinite}
  @keyframes gglow{to{background-position:300% 0}}@keyframes gpulse{0%,100%{opacity:.4;transform:scale(1)}50%{opacity:.8;transform:scale(1.04)}}
  .gbtn{position:relative;z-index:1;width:100%;background:#fff;border:2px solid #fff;border-radius:18px;padding:19px 16px;display:flex;align-items:center;justify-content:center;gap:13px;cursor:pointer;text-decoration:none;overflow:hidden;box-shadow:0 14px 32px -8px rgba(66,133,244,.5);animation:gbounce 1.8s ease-in-out infinite}
  @keyframes gbounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
  .gbtn::after{content:'';position:absolute;top:0;left:-60%;width:45%;height:100%;transform:skewX(-18deg);background:linear-gradient(100deg,transparent,rgba(66,133,244,.22),transparent);animation:gshine 2.6s ease-in-out infinite}
  @keyframes gshine{0%{left:-60%}55%,100%{left:135%}}
  .gbtn .g{font-size:30px;font-weight:800;color:#4285F4}
  .gbtn b{font-size:18px;color:#1f2937}.gbtn span{font-size:12px;color:#5f6368;display:block}
  .hint{font-size:12.5px;color:var(--mor1);font-weight:800;margin-top:15px;animation:bob 1.1s ease-in-out infinite}
  @keyframes bob{0%,100%{transform:translateY(0)}50%{transform:translateY(5px)}}
  .yildiz{font-size:25px;letter-spacing:3px;margin-top:16px;display:flex;gap:4px}
  .yildiz i{color:#e2e8f0;font-style:normal;animation:sf 2.6s ease-in-out infinite}
  .yildiz i:nth-child(2){animation-delay:.22s}.yildiz i:nth-child(3){animation-delay:.44s}.yildiz i:nth-child(4){animation-delay:.66s}.yildiz i:nth-child(5){animation-delay:.88s}
  @keyframes sf{0%,38%{color:#e2e8f0}18%{color:#FBBC05;transform:scale(1.3)}50%,100%{color:#FBBC05}}
  .tesekkur{margin-top:16px;font-size:12px;color:var(--sub2)}
  .cipler{display:flex;flex-wrap:wrap;gap:9px;justify-content:center;margin:4px 0 16px}
  .cip{background:#fff;border:1.5px solid var(--line);border-radius:30px;padding:10px 16px;font-size:13.5px;font-weight:600;color:var(--ink);cursor:pointer;transition:.15s}
  .cip.sec{background:color-mix(in srgb,var(--mor1) 12%,#fff);border-color:var(--mor1);color:var(--mor1)}
  textarea{width:100%;border:1.5px solid var(--line);border-radius:14px;padding:12px;font-size:14px;resize:none;height:74px;color:var(--ink);font-family:inherit}
  textarea:focus{outline:none;border-color:var(--mor1)}
  .gonder{width:100%;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:16px;padding:16px;font-size:15px;font-weight:700;cursor:pointer;margin-top:16px;box-shadow:0 10px 24px -8px rgba(124,58,237,.5)}
  .gonder:active{transform:scale(.97)}
  .uyari{background:color-mix(in srgb,var(--amber) 12%,#fff);border:1px solid color-mix(in srgb,var(--amber) 35%,transparent);border-radius:14px;padding:13px 15px;font-size:12.5px;color:#92600a;margin-top:18px;line-height:1.5}
</style>
</head>
<body>
<div class="kart">
  <input type="hidden" id="token" value="{{ $token }}">
  <input type="hidden" id="googleLink" value="{{ $googleLink }}">

  {{-- EKRAN 1 --}}
  <div class="sayfa akt" id="s1">
    <div class="logo">🍽️</div>
    <div class="ad">{{ $sube->ad ?? 'Restoran' }}</div>
    <div class="alt">{{ $masaAd }} · Teşekkürler 💛</div>
    <div class="soru">Deneyiminiz<br>nasıldı?</div>
    <div class="soru-alt">Tek dokunuş, 2 saniye</div>
    <div class="yuzler">
      <div class="yuz" onclick="sec(1)"><span class="e">😣</span><span class="l">Çok Kötü</span></div>
      <div class="yuz" onclick="sec(2)"><span class="e">😕</span><span class="l">Kötü</span></div>
      <div class="yuz" onclick="sec(3)"><span class="e">😐</span><span class="l">İdare</span></div>
      <div class="yuz" onclick="sec(4)"><span class="e">🙂</span><span class="l">İyi</span></div>
      <div class="yuz" onclick="sec(5)"><span class="e">😍</span><span class="l">Harika</span></div>
    </div>
    <div class="ipucu">Yanıtınız bize çok değerli ✨</div>
  </div>

  {{-- EKRAN 2: MUTLU --}}
  <div class="sayfa" id="s2">
    <div class="buyuk">🎉</div>
    <div class="baslik">Harika!</div>
    <div class="aciklama" id="mutluMetin">Google'da paylaşır mısın? 💜</div>
    <div id="googleAlan"></div>
    <div class="tesekkur">Tekrar görüşmek dileğiyle 🙏</div>
  </div>

  {{-- EKRAN 3: MUTSUZ --}}
  <div class="sayfa" id="s3">
    <div class="soru" style="margin-top:14px;font-size:22px">Çok üzgünüz 🙏</div>
    <div class="soru-alt">Hemen düzeltmek isteriz — ne oldu?</div>
    <div class="cipler" id="cipler">
      <div class="cip" data-s="Yemek" onclick="cipSec(this)">🍽️ Yemek</div>
      <div class="cip" data-s="Servis" onclick="cipSec(this)">⏱️ Servis</div>
      <div class="cip" data-s="Temizlik" onclick="cipSec(this)">🧹 Temizlik</div>
      <div class="cip" data-s="Fiyat" onclick="cipSec(this)">💳 Fiyat</div>
      <div class="cip" data-s="Ortam" onclick="cipSec(this)">🔊 Ortam</div>
    </div>
    <textarea id="not" placeholder="İsterseniz birkaç kelime yazın (opsiyonel)..."></textarea>
    <button class="gonder" onclick="mutsuzGonder()">Yöneticiye İlet →</button>
  </div>

  {{-- EKRAN 4: MUTSUZ ONAY --}}
  <div class="sayfa" id="s4">
    <div class="buyuk">🤝</div>
    <div class="baslik">Teşekkürler!</div>
    <div class="aciklama">Geri bildiriminiz <b>anında yöneticimize</b> iletildi. Birazdan masanıza gelip ilgilenecek.</div>
    <div class="uyari">💡 Sorununuzu <b>siz buradayken</b> çözmek bizim için önemli.</div>
  </div>
</div>

<script>
const CSRF=document.querySelector('meta[name=csrf-token]').content;
const TOKEN=document.getElementById('token').value;
const GLINK=document.getElementById('googleLink').value;
let puan=0;
function goster(id){document.querySelectorAll('.sayfa').forEach(s=>s.classList.remove('akt'));document.getElementById(id).classList.add('akt');}
async function kaydet(data){
  try{await fetch('/memnuniyet/kaydet',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(Object.assign({token:TOKEN},data))});}catch(e){}
}
function sec(n){
  puan=n;
  if(n>=4){ kaydet({puan:n}); mutluEkran(); goster('s2'); }
  else { goster('s3'); }
}
function mutluEkran(){
  const alan=document.getElementById('googleAlan');
  if(GLINK){
    alan.innerHTML='<div class="gwrap"><a class="gbtn" href="'+GLINK+'" target="_blank" rel="noopener"><span class="g">G</span><span><b>Google\'da Değerlendir</b><span>⭐ 30 saniye · tek tık</span></span></a></div>'+
      '<div class="hint">👆 buraya dokun</div>'+
      '<div class="yildiz"><i>★</i><i>★</i><i>★</i><i>★</i><i>★</i></div>';
  } else {
    document.getElementById('mutluMetin').textContent='Bizi tercih ettiğiniz için teşekkürler 💜';
    alan.innerHTML='<div class="yildiz"><i>★</i><i>★</i><i>★</i><i>★</i><i>★</i></div>';
  }
}
function cipSec(el){el.classList.toggle('sec');}
function mutsuzGonder(){
  const sebepler=[...document.querySelectorAll('#cipler .cip.sec')].map(c=>c.dataset.s).join(',');
  const not=document.getElementById('not').value.trim();
  kaydet({puan:puan||2,sebepler:sebepler,not:not});
  goster('s4');
}
</script>
</body>
</html>

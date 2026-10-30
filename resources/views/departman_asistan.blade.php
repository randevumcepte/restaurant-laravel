@extends('layout.app')
@section('title', $baslik)
@section('rk_ikon', 'smart_toy')
@section('baslik', $baslik)
@section('rk_alt', $selam)

@section('content')
<style>
  /* ===== ORTAK AI ASİSTAN — premium tasarım (satınalma asistanı referans, tüm departmanlar varsayılan) ===== */
  #asW{--mor1:#7C3AED;--mavi:#4F46E5;--pembe:#EC4899;--yesil:#10B981;--amber:#F59E0B;
    --card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
    max-width:860px;margin:0 auto;padding-bottom:120px}
  body.koyu #asW{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650;--bg:#0B1020}
  .rk-main{background:var(--bg)}
  #asHero{text-align:center;padding:40px 20px 18px;position:relative}
  #asHero .sa-orb-stage{position:relative;width:140px;height:140px;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;animation:saOrbIn .9s cubic-bezier(.34,1.56,.64,1) both}
  @keyframes saOrbIn{from{opacity:0;transform:scale(.4)}to{opacity:1;transform:scale(1)}}
  #asHero .sa-orb-stage::before{content:'';position:absolute;inset:-25px;border-radius:50%;background:radial-gradient(circle at 35% 40%, rgba(168,85,247,.45) 0%, transparent 55%),radial-gradient(circle at 65% 60%, rgba(236,72,153,.35) 0%, transparent 55%),radial-gradient(circle at 50% 50%, rgba(59,130,246,.3) 0%, transparent 60%);filter:blur(16px);animation:saAura 6s ease-in-out infinite;z-index:0}
  @keyframes saAura{0%,100%{opacity:.65;transform:scale(1)}50%{opacity:.95;transform:scale(1.08)}}
  #asHero .sa-orb{width:120px;height:120px;border-radius:50%;position:relative;z-index:1;background:radial-gradient(circle at 30% 25%, #1e1b4b 0%, #0a0520 60%, #000 100%);overflow:hidden;box-shadow:0 20px 50px -12px rgba(124,58,237,.4),0 0 50px -10px rgba(236,72,153,.3),inset 0 2px 6px rgba(255,255,255,.15),inset 0 -10px 25px rgba(0,0,0,.5),inset 0 0 40px rgba(99,102,241,.2);animation:saBreath 5s ease-in-out infinite}
  @keyframes saBreath{0%,100%{transform:scale(1)}50%{transform:scale(1.035)}}
  #asHero .sa-orb .layer{position:absolute;width:170%;height:170%;top:-35%;left:-35%;mix-blend-mode:screen;filter:blur(14px);pointer-events:none}
  #asHero .sa-orb .l1{background:radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 32%);animation:saFloat1 7s ease-in-out infinite}
  #asHero .sa-orb .l2{background:radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 32%);animation:saFloat2 9s ease-in-out infinite}
  #asHero .sa-orb .l3{background:radial-gradient(circle at 60% 30%, #3b82f6 0%, transparent 28%),radial-gradient(circle at 40% 75%, #f59e0b 0%, transparent 25%);animation:saFloat3 11s ease-in-out infinite}
  @keyframes saFloat1{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(22%,-18%) scale(1.1)}66%{transform:translate(-18%,22%) scale(.95)}}
  @keyframes saFloat2{0%,100%{transform:translate(0,0) scale(1)}33%{transform:translate(-22%,20%) scale(.9)}66%{transform:translate(18%,-22%) scale(1.1)}}
  @keyframes saFloat3{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(12%,15%) scale(1.05)}}
  #asHero .sa-orb .shine{position:absolute;top:10%;left:18%;width:48%;height:36%;background:radial-gradient(ellipse at center, rgba(255,255,255,.55) 0%, transparent 65%);filter:blur(5px);z-index:2;pointer-events:none;animation:saShinePath 6s ease-in-out infinite}
  @keyframes saShinePath{0%,100%{transform:translate(0,0) scale(1);opacity:.6}50%{transform:translate(14%,8%) scale(1.1);opacity:.85}}
  #asHero .sa-orb .eyes{position:absolute;top:48%;left:50%;transform:translate(-50%,-50%);display:flex;gap:14px;z-index:4;pointer-events:none;filter:drop-shadow(0 2px 6px rgba(0,0,0,.4)) drop-shadow(0 0 10px rgba(168,85,247,.5))}
  #asHero .sa-orb .eye{width:16px;height:20px;border-radius:50%;background:#fff;position:relative;overflow:hidden;animation:saBlink 5.5s infinite}
  #asHero .sa-orb .pupil{width:9px;height:9px;border-radius:50%;background:#0a0520;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);animation:saLook 7s ease-in-out infinite;box-shadow:inset 0 1px 2px rgba(99,102,241,.5)}
  #asHero .sa-orb .pupil::after{content:'';position:absolute;top:1.5px;left:1.5px;width:3px;height:3px;border-radius:50%;background:#fff;opacity:.9}
  @keyframes saLook{0%,100%{transform:translate(-50%,-50%)}15%{transform:translate(-130%,-50%)}30%{transform:translate(-130%,-30%)}50%{transform:translate(30%,-50%)}65%{transform:translate(30%,-70%)}80%{transform:translate(-50%,-30%)}}
  @keyframes saBlink{0%,42%,48%,92%,96%,100%{transform:scaleY(1)}45%,94%{transform:scaleY(.1)}}
  #asHero .sa-orb-stage .orbit{position:absolute;inset:0;z-index:3;pointer-events:none;animation:saOrbit 14s linear infinite}
  #asHero .sa-orb-stage .orbit::before{content:'';position:absolute;top:-4px;left:50%;width:8px;height:8px;border-radius:50%;transform:translateX(-50%);background:radial-gradient(circle, #fff 0%, #c4b5fd 40%, transparent 70%);box-shadow:0 0 12px rgba(255,255,255,.9), 0 0 24px rgba(168,85,247,.6)}
  @keyframes saOrbit{to{transform:rotate(360deg)}}
  #asHero h1{font-size:26px;font-weight:800;color:var(--ink);margin:0 0 8px;letter-spacing:-.6px;animation:saInUp .5s ease-out .15s both}
  #asHero p{font-size:14px;color:var(--sub);margin:0 auto;max-width:460px;line-height:1.55;animation:saInUp .5s ease-out .25s both}
  @keyframes saInUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  #asOneriler{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin:22px 0 14px;padding:0 2px}
  .sa-oneri{display:flex;align-items:flex-start;gap:10px;padding:14px 14px 14px 12px;border-radius:14px;background:var(--card);border:1px solid var(--line);cursor:pointer;text-align:left;transition:all .18s;font-family:inherit;color:var(--ink)}
  .sa-oneri:hover{border-color:color-mix(in srgb,var(--mor1) 35%,var(--line));transform:translateY(-2px);box-shadow:0 8px 24px -6px rgba(124,58,237,.15)}
  .sa-oneri-ikon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff}
  .sa-oneri-ikon .material-icons-outlined{font-size:19px}
  .sa-oneri-bas{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.3}
  .sa-oneri-alt{font-size:11.5px;color:var(--sub);margin-top:3px;line-height:1.4}
  #asChat{display:flex;flex-direction:column;gap:12px;min-height:60px;padding:4px 2px 180px}
  #asChat > *{scroll-margin-bottom:200px}
  .sa-msg{max-width:88%;padding:12px 15px;border-radius:16px;font-size:13.5px;line-height:1.6;white-space:pre-wrap;word-wrap:break-word;animation:saIn .25s ease-out}
  @keyframes saIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
  .sa-msg.ben{align-self:flex-end;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-bottom-right-radius:6px;box-shadow:0 4px 14px -4px rgba(124,58,237,.4)}
  .sa-msg.ai{align-self:flex-start;background:var(--card);color:var(--ink);border:1px solid var(--line);border-bottom-left-radius:6px;box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-msg-wrap{display:flex;gap:9px;align-items:flex-start;max-width:92%}
  .sa-msg-wrap.ben{align-self:flex-end;flex-direction:row-reverse}
  .sa-msg-wrap.ai{align-self:flex-start}
  .sa-msg-wrap .sa-avatar{width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;color:#fff;margin-top:4px;position:relative;overflow:hidden}
  .sa-msg-wrap.ai .sa-avatar{background:radial-gradient(circle at 30% 30%, #1e1b4b, #000);box-shadow:0 2px 8px rgba(124,58,237,.4), inset 0 0 10px rgba(168,85,247,.3)}
  .sa-msg-wrap.ai .sa-avatar::after{content:'';position:absolute;inset:0;border-radius:50%;background:radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 40%),radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 40%);mix-blend-mode:screen;filter:blur(4px)}
  .sa-msg-wrap.ben .sa-avatar{background:linear-gradient(135deg,var(--sub),var(--sub2));color:#fff}
  .sa-msg-wrap.ben .sa-avatar .material-icons-outlined{font-size:16px;position:relative;z-index:1}
  .sa-msg.ai .sa-h{display:block;font-weight:700;font-size:14px;color:var(--mor1);margin:8px 0 4px}
  .sa-msg.ai .sa-h:first-child{margin-top:0}
  .sa-msg.ai b{font-weight:700;color:var(--ink)}
  .sa-msg.ai .sa-mad{color:var(--mor1);font-weight:700;margin-right:4px}
  .sa-msg.ai a{color:var(--mavi);font-weight:700;text-decoration:none}
  .sa-yaziyor{align-self:flex-start;display:inline-flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:16px;border-bottom-left-radius:6px;padding:10px 14px;box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-yaziyor .sa-mini-orb{width:26px;height:26px;border-radius:50%;flex-shrink:0;position:relative;overflow:hidden;background:radial-gradient(circle at 30% 30%, #1e1b4b, #000);animation:saPulse 1.5s ease-in-out infinite;box-shadow:0 0 10px rgba(168,85,247,.5)}
  .sa-yaziyor .sa-mini-orb::after{content:'';position:absolute;inset:0;border-radius:50%;background:radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 45%),radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 45%);mix-blend-mode:screen;filter:blur(3px)}
  @keyframes saPulse{0%,100%{transform:scale(1);box-shadow:0 0 10px rgba(168,85,247,.5)}50%{transform:scale(1.1);box-shadow:0 0 20px rgba(168,85,247,.8)}}
  .sa-yaziyor .sa-yz-txt{font-size:12.5px;font-weight:700;background:linear-gradient(90deg,#7C3AED,#4F46E5,#EC4899,#7C3AED);background-size:200% auto;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;animation:saShine 2.2s linear infinite}
  .sa-yaziyor .sa-dots{display:inline-flex;gap:3px}
  .sa-yaziyor .sa-dots i{width:5px;height:5px;border-radius:50%;display:inline-block;animation:saBounce 1.2s infinite}
  .sa-yaziyor .sa-dots i:nth-child(1){background:#7C3AED}
  .sa-yaziyor .sa-dots i:nth-child(2){background:#4F46E5;animation-delay:.18s}
  .sa-yaziyor .sa-dots i:nth-child(3){background:#EC4899;animation-delay:.36s}
  @keyframes saBounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
  @keyframes saShine{to{background-position:200% center}}
  .sa-eylem-balon{background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(79,70,229,.04));border:1.5px solid rgba(124,58,237,.3);border-radius:16px;padding:14px 16px;margin:4px 0;max-width:92%;align-self:flex-start}
  .sa-eylem-balon .se-h{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--mor1);text-transform:uppercase;letter-spacing:.3px;margin-bottom:8px}
  .sa-eylem-balon .se-ozet{font-size:13.5px;color:var(--ink);line-height:1.5;margin-bottom:10px}
  .sa-eylem-balon .se-kilit{font-size:11.5px;color:var(--sub);margin-bottom:12px}
  .sa-eylem-balon .se-btn-grup{display:flex;gap:9px;justify-content:flex-end}
  .sa-eylem-balon .se-yap{background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:0;border-radius:10px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;gap:5px;box-shadow:0 3px 10px rgba(16,185,129,.3)}
  .sa-eylem-balon .se-yap:hover{opacity:.9}
  .sa-eylem-balon .se-vaz{background:var(--card2);color:var(--sub2);border:1px solid var(--line);border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;font-size:13px}
  .sa-eylem-balon .se-sonuc{font-size:13px;line-height:1.5;padding:11px 13px;border-radius:9px;background:var(--card2);margin-top:12px}
  .sa-eylem-balon .se-sonuc.ok{border-left:3px solid #10B981;color:#047857}
  .sa-eylem-balon .se-sonuc.hata{border-left:3px solid #F43F5E;color:#9f1239}
  .sa-eylem-balon .se-sonuc.vz{border-left:3px solid var(--sub);color:var(--sub2)}
  #asInputBar{position:sticky;bottom:14px;z-index:30;margin-top:16px;padding:4px}
  #asInputBar .wrap{display:flex;gap:8px;align-items:center;padding:8px 8px 8px 16px;border-radius:999px;background:var(--card);border:1px solid var(--line);box-shadow:0 10px 30px -8px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);transition:border-color .15s, box-shadow .15s}
  #asInputBar .wrap:focus-within{border-color:var(--mor1);box-shadow:0 10px 30px -8px color-mix(in srgb,var(--mor1) 30%,transparent),0 0 0 3px color-mix(in srgb,var(--mor1) 12%,transparent)}
  #asInputBar input{flex:1;border:none;outline:none;background:transparent;color:var(--ink);font-size:14px;padding:9px 0;font-family:inherit}
  #asInputBar input::placeholder{color:var(--sub2)}
  #asInputBar .gnd{width:40px;height:40px;border-radius:50%;flex-shrink:0;border:none;cursor:pointer;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px -2px color-mix(in srgb,var(--mor1) 50%,transparent);transition:transform .15s, box-shadow .15s}
  #asInputBar .gnd:hover{transform:scale(1.06)}
  #asInputBar .gnd:active{transform:scale(.95)}
  #asInputBar .gnd:disabled{opacity:.5;cursor:not-allowed;transform:none}
  #asInputBar .gnd .material-icons-outlined{font-size:19px}
  #asInputBar .ipucu{text-align:center;font-size:10.5px;color:var(--sub2);margin-top:6px;font-weight:500}
  @media (max-width:640px){#asHero{padding:30px 14px 14px}#asHero h1{font-size:22px}#asHero .sa-orb-stage{width:120px;height:120px}#asHero .sa-orb{width:100px;height:100px}#asOneriler{grid-template-columns:1fr}.sa-msg{max-width:92%;font-size:13px}#asInputBar{bottom:10px}}
</style>

<div id="asW">
  <div id="asHero">
    <div class="sa-orb-stage">
      <div class="sa-orb">
        <div class="layer l1"></div><div class="layer l2"></div><div class="layer l3"></div>
        <div class="shine"></div>
        <div class="eyes"><div class="eye"><div class="pupil"></div></div><div class="eye"><div class="pupil"></div></div></div>
      </div>
      <div class="orbit"></div>
    </div>
    <h1>{{ $baslik }}</h1>
    <p>{{ $selam }}</p>
  </div>

  <div id="asOneriler">
    @foreach ($hizli as $h)
      <button type="button" class="sa-oneri" onclick="asSor(@js($h[1]))">
        <div class="sa-oneri-ikon" style="background:{{ $h[3] ?? 'linear-gradient(135deg,#7C3AED,#4F46E5)' }}"><span class="material-icons-outlined">{{ $h[2] }}</span></div>
        <div><div class="sa-oneri-bas">{{ $h[0] }}</div>@if(!empty($h[4]))<div class="sa-oneri-alt">{{ $h[4] }}</div>@endif</div>
      </button>
    @endforeach
  </div>

  <div id="asChat"></div>

  <div id="asInputBar">
    <div class="wrap">
      <input type="text" id="asInput" placeholder="{{ $placeholder ?? 'Sorunu yaz…' }}" onkeydown="if(event.key==='Enter')asSor()" autocomplete="off">
      <button type="button" class="gnd" id="asGnd" onclick="asSor()" title="Gönder"><span class="material-icons-outlined">arrow_upward</span></button>
    </div>
    <div class="ipucu"><b>Enter</b> ile gönder · Değiştiren işlemler önce onayına sunulur</div>
  </div>
</div>

<script>
const AS_CSRF = document.querySelector('meta[name=csrf-token]').content;
const AS_SOR = @json($sorUrl);
const AS_UYG = @json($uygulaUrl);
const AS_EK  = @json($ekParam ?? (object)[]);

function asEsc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function asFormat(t){
  let h=asEsc(t);
  h=h.replace(/\*\*([^*]+?)\*\*/g,'<b>$1</b>');
  h=h.replace(/^[ \t]*[-*•]\s+/gm,'<span class="sa-mad">•</span> ');
  h=h.replace(/\n/g,'<br>');
  return h;
}
function asHeroGizle(){ const h=document.getElementById('asHero'); if(h)h.style.display='none'; const o=document.getElementById('asOneriler'); if(o)o.style.display='none'; }
function asYaziyor(){ const d=document.createElement('div'); d.className='sa-yaziyor'; d.innerHTML='<span class="sa-mini-orb"></span><span class="sa-yz-txt">AI düşünüyor</span><span class="sa-dots"><i></i><i></i><i></i></span>'; document.getElementById('asChat').appendChild(d); d.scrollIntoView({behavior:'smooth',block:'end'}); return d; }
function asBubble(metin, ben){
  const w=document.createElement('div'); w.className='sa-msg-wrap '+(ben?'ben':'ai');
  const av=document.createElement('div'); av.className='sa-avatar'; if(ben) av.innerHTML='<span class="material-icons-outlined">person</span>';
  const m=document.createElement('div'); m.className='sa-msg '+(ben?'ben':'ai');
  if(ben) m.textContent=metin; else m.innerHTML=asFormat(metin);
  w.appendChild(av); w.appendChild(m);
  document.getElementById('asChat').appendChild(w); w.scrollIntoView({behavior:'smooth',block:'end'});
  return {wrap:w, msg:m};
}

let asBusy=false;
async function asSor(metin){
  if(asBusy) return;
  const inp=document.getElementById('asInput');
  const soru=(metin||inp.value||'').trim();
  if(!soru) return;
  inp.value=''; asHeroGizle();
  // KONUŞMA GEÇMİŞİ: yeni mesajdan ÖNCEki balonları topla (son 8) -> AI bağlamı hatırlasın
  const gecmis=[];
  document.querySelectorAll('#asChat .sa-msg').forEach(el=>{ const t=el.textContent.trim(); if(t) gecmis.push({rol: el.classList.contains('ben')?'user':'ai', metin:t}); });
  asBubble(soru,true);
  asBusy=true; document.getElementById('asGnd').disabled=true;
  const bekle=asYaziyor();
  try{
    const body=Object.assign({}, AS_EK, {metin:soru, gecmis:gecmis.slice(-8)});
    const r=await fetch(AS_SOR,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':AS_CSRF,'Accept':'application/json'},body:JSON.stringify(body)});
    const j=await r.json();
    bekle.remove();
    if(j.ok!==1){ asBubble(j.hata||'Bir sorun oldu, tekrar dener misin?',false); }
    else if(j.tip==='eylem'){ asEylemBalon(j.ozet, j.eylem, j.params||{}, j.log_id||0); }
    else {
      const b=asBubble(j.cevap||'…',false);
      if(j.link){ const a=document.createElement('a'); a.href=j.link.url; a.innerHTML='<br>🔗 '+asEsc(j.link.lbl); b.msg.appendChild(a); }
      if(j.aksiyon){ asAksiyonBtn(b.msg, j.aksiyon); }
    }
  }catch(e){ bekle.remove(); asBubble('Bağlantı hatası, tekrar dene.',false); }
  asBusy=false; document.getElementById('asGnd').disabled=false; document.getElementById('asInput').focus();
}

// okuma cevabı altındaki "şunu yapabilirim" aksiyonu -> aynı niyeti eyleme çevir
function asAksiyonBtn(msgEl, aks){
  const w=document.createElement('div'); w.style.marginTop='10px';
  const b=document.createElement('button'); b.className='se-yap'; b.style.background='linear-gradient(135deg,#F59E0B,#F97316)';
  b.innerHTML='<span class="material-icons-outlined" style="font-size:15px">bolt</span> '+asEsc(aks.lbl);
  b.onclick=()=>{ w.remove(); asSor(aks.soru || aks.intent); };
  w.appendChild(b); msgEl.appendChild(w);
}

// AI eylem önerisi balonu: ÖZET + Onayla/Vazgeç (değiştiren işlemler)
function asEylemBalon(ozet, eylem, params, log_id){
  const akis=document.getElementById('asChat');
  const d=document.createElement('div'); d.className='sa-eylem-balon';
  d.innerHTML=
    '<div class="se-h"><span class="material-icons-outlined" style="font-size:16px">auto_awesome</span> Onayına sunuyorum</div>'+
    '<div class="se-ozet">'+asEsc(ozet)+'</div>'+
    '<div class="se-kilit"><span class="material-icons-outlined" style="font-size:13px;color:#10B981;vertical-align:-2px">lock</span> Onaylamadan hiçbir şey değişmez. Onay kaydedilir.</div>'+
    '<div class="se-btn-grup"><button class="se-vaz">Vazgeç</button><button class="se-yap"><span class="material-icons-outlined" style="font-size:15px">check</span> Onayla, yap</button></div>'+
    '<div class="se-sonuc" style="display:none"></div>';
  const yap=d.querySelector('.se-yap'), vaz=d.querySelector('.se-vaz'), son=d.querySelector('.se-sonuc');
  yap.onclick=async()=>{
    yap.disabled=true; vaz.disabled=true; yap.innerHTML='<span class="material-icons-outlined" style="font-size:15px">hourglass_bottom</span> Yürütülüyor';
    try{
      const body=Object.assign({}, AS_EK, {eylem, params, log_id});
      const r=await fetch(AS_UYG,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':AS_CSRF,'Accept':'application/json'},body:JSON.stringify(body)});
      const j=await r.json(); const s=(j&&j.sonuc)||{};
      son.style.display='block'; son.className='se-sonuc '+(s.ok?'ok':'hata'); son.textContent=s.mesaj||'Sonuç alınamadı.';
      yap.style.display='none'; vaz.style.display='none';
    }catch(e){ son.style.display='block'; son.className='se-sonuc hata'; son.textContent='Bağlantı hatası'; yap.disabled=false; vaz.disabled=false; yap.innerHTML='<span class="material-icons-outlined" style="font-size:15px">check</span> Onayla, yap'; }
  };
  vaz.onclick=()=>{ son.style.display='block'; son.className='se-sonuc vz'; son.textContent='Tamam, vazgeçtim.'; yap.style.display='none'; vaz.style.display='none'; };
  akis.appendChild(d); d.scrollIntoView({behavior:'smooth',block:'end'});
}
</script>
@endsection

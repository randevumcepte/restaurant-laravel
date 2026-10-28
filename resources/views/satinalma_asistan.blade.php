@extends('layout.app')
@section('title', 'Satınalma AI Asistanı')
@section('rk_ikon', 'smart_toy')
@section('baslik', 'Satınalma AI Asistanı')
@section('rk_alt', 'Stok + fiyat + tedarikçi verinden anında cevap')

@section('content')
<style>
  /* ===== Satınalma AI — premium tasarım ===== */
  #saWrap{--mor1:#7C3AED;--mavi:#4F46E5;--pembe:#EC4899;--yesil:#10B981;--amber:#F59E0B;
    --card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
    max-width:860px;margin:0 auto;padding-bottom:120px}
  body.koyu #saWrap{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650;--bg:#0B1020}
  .rk-main{background:var(--bg)}

  /* --- Hero: premium AI nebula orb --- */
  #saHero{text-align:center;padding:40px 20px 18px;position:relative}

  #saHero .sa-orb-stage{position:relative;width:140px;height:140px;margin:0 auto 20px;
    display:flex;align-items:center;justify-content:center;
    animation:saOrbIn .9s cubic-bezier(.34,1.56,.64,1) both}
  @keyframes saOrbIn{from{opacity:0;transform:scale(.4)}to{opacity:1;transform:scale(1)}}

  /* Dış atmosfer glow */
  #saHero .sa-orb-stage::before{content:'';position:absolute;inset:-25px;border-radius:50%;
    background:
      radial-gradient(circle at 35% 40%, rgba(168,85,247,.45) 0%, transparent 55%),
      radial-gradient(circle at 65% 60%, rgba(236,72,153,.35) 0%, transparent 55%),
      radial-gradient(circle at 50% 50%, rgba(59,130,246,.3) 0%, transparent 60%);
    filter:blur(16px);animation:saAura 6s ease-in-out infinite;z-index:0}
  @keyframes saAura{0%,100%{opacity:.65;transform:scale(1)}50%{opacity:.95;transform:scale(1.08)}}

  /* Ana küre: dark holographic base */
  #saHero .sa-orb{width:120px;height:120px;border-radius:50%;position:relative;z-index:1;
    background:radial-gradient(circle at 30% 25%, #1e1b4b 0%, #0a0520 60%, #000 100%);
    overflow:hidden;
    box-shadow:
      0 20px 50px -12px rgba(124,58,237,.4),
      0 0 50px -10px rgba(236,72,153,.3),
      inset 0 2px 6px rgba(255,255,255,.15),
      inset 0 -10px 25px rgba(0,0,0,.5),
      inset 0 0 40px rgba(99,102,241,.2);
    animation:saBreath 5s ease-in-out infinite}
  @keyframes saBreath{0%,100%{transform:scale(1)}50%{transform:scale(1.035)}}

  /* Nebula katmanları — her biri bağımsız dolaşır, mix-blend:screen ile karışır */
  #saHero .sa-orb .layer{position:absolute;width:170%;height:170%;top:-35%;left:-35%;
    mix-blend-mode:screen;filter:blur(14px);pointer-events:none}
  #saHero .sa-orb .l1{background:radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 32%);
    animation:saFloat1 7s ease-in-out infinite}
  #saHero .sa-orb .l2{background:radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 32%);
    animation:saFloat2 9s ease-in-out infinite}
  #saHero .sa-orb .l3{background:
    radial-gradient(circle at 60% 30%, #3b82f6 0%, transparent 28%),
    radial-gradient(circle at 40% 75%, #f59e0b 0%, transparent 25%);
    animation:saFloat3 11s ease-in-out infinite}
  @keyframes saFloat1{0%,100%{transform:translate(0,0) scale(1)}
    33%{transform:translate(22%,-18%) scale(1.1)}
    66%{transform:translate(-18%,22%) scale(.95)}}
  @keyframes saFloat2{0%,100%{transform:translate(0,0) scale(1)}
    33%{transform:translate(-22%,20%) scale(.9)}
    66%{transform:translate(18%,-22%) scale(1.1)}}
  @keyframes saFloat3{0%,100%{transform:translate(0,0) scale(1)}
    50%{transform:translate(12%,15%) scale(1.05)}}

  /* Üstteki parlak highlight (cam yüzeyi) */
  #saHero .sa-orb .shine{position:absolute;top:10%;left:18%;width:48%;height:36%;
    background:radial-gradient(ellipse at center, rgba(255,255,255,.55) 0%, transparent 65%);
    filter:blur(5px);z-index:2;pointer-events:none;animation:saShinePath 6s ease-in-out infinite}
  @keyframes saShinePath{0%,100%{transform:translate(0,0) scale(1);opacity:.6}
    50%{transform:translate(14%,8%) scale(1.1);opacity:.85}}

  /* Orb etrafında dönen küçük parlak partikül */
  #saHero .sa-orb-stage .orbit{position:absolute;inset:0;z-index:3;pointer-events:none;
    animation:saOrbit 14s linear infinite}
  #saHero .sa-orb-stage .orbit::before{content:'';position:absolute;top:-4px;left:50%;
    width:8px;height:8px;border-radius:50%;transform:translateX(-50%);
    background:radial-gradient(circle, #fff 0%, #c4b5fd 40%, transparent 70%);
    box-shadow:0 0 12px rgba(255,255,255,.9), 0 0 24px rgba(168,85,247,.6)}
  @keyframes saOrbit{to{transform:rotate(360deg)}}

  #saHero h1{font-size:26px;font-weight:800;color:var(--ink);margin:0 0 8px;letter-spacing:-.6px;
    animation:saInUp .5s ease-out .15s both}
  #saHero p{font-size:14px;color:var(--sub);margin:0 auto;max-width:460px;line-height:1.55;
    animation:saInUp .5s ease-out .25s both}
  @keyframes saInUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

  /* --- Hızlı öneri kartları (grid) --- */
  #saOneriler{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin:22px 0 14px;padding:0 2px}
  .sa-oneri{display:flex;align-items:flex-start;gap:10px;padding:14px 14px 14px 12px;border-radius:14px;
    background:var(--card);border:1px solid var(--line);cursor:pointer;text-align:left;
    transition:all .18s;font-family:inherit;color:var(--ink)}
  .sa-oneri:hover{border-color:color-mix(in srgb,var(--mor1) 35%,var(--line));
    transform:translateY(-2px);box-shadow:0 8px 24px -6px rgba(124,58,237,.15)}
  .sa-oneri-ikon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;
    flex-shrink:0;color:#fff}
  .sa-oneri-ikon .material-icons-outlined{font-size:19px}
  .sa-oneri-bas{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.3}
  .sa-oneri-alt{font-size:11.5px;color:var(--sub);margin-top:3px;line-height:1.4}

  /* --- Araç çubuğu --- */
  #saArac{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;padding:0 2px}
  .sa-arac-btn{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border-radius:10px;
    background:color-mix(in srgb,var(--yesil) 10%,var(--card));
    color:var(--yesil);border:1px solid color-mix(in srgb,var(--yesil) 30%,var(--line));
    font-size:12.5px;font-weight:700;cursor:pointer;transition:all .15s;font-family:inherit}
  .sa-arac-btn:hover{background:var(--yesil);color:#fff;border-color:var(--yesil);
    box-shadow:0 4px 12px color-mix(in srgb,var(--yesil) 30%,transparent)}
  .sa-arac-btn .material-icons-outlined{font-size:16px}
  #saArac .sa-sayac{margin-left:auto;font-size:11.5px;color:var(--sub2);font-weight:600;display:inline-flex;align-items:center;gap:5px}
  #saArac .sa-sayac .dot{width:6px;height:6px;border-radius:50%;background:var(--yesil);box-shadow:0 0 8px var(--yesil)}

  /* --- Chat --- */
  #saChat{display:flex;flex-direction:column;gap:12px;min-height:60px;padding:4px 2px 200px}
  #saChat > *{scroll-margin-bottom:180px}
  .sa-msg{max-width:88%;padding:12px 15px;border-radius:16px;font-size:13.5px;line-height:1.6;
    white-space:pre-wrap;word-wrap:break-word;animation:saIn .25s ease-out}
  @keyframes saIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
  .sa-msg.ben{align-self:flex-end;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;
    border-bottom-right-radius:6px;box-shadow:0 4px 14px -4px rgba(124,58,237,.4)}
  .sa-msg.ai{align-self:flex-start;background:var(--card);color:var(--ink);
    border:1px solid var(--line);border-bottom-left-radius:6px;
    box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-msg-wrap{display:flex;gap:9px;align-items:flex-start;max-width:92%}
  .sa-msg-wrap.ben{align-self:flex-end;flex-direction:row-reverse}
  .sa-msg-wrap.ai{align-self:flex-start}
  .sa-msg-wrap .sa-avatar{width:30px;height:30px;border-radius:50%;flex-shrink:0;display:flex;
    align-items:center;justify-content:center;color:#fff;margin-top:4px;position:relative;overflow:hidden}
  .sa-msg-wrap.ai .sa-avatar{background:radial-gradient(circle at 30% 30%, #1e1b4b, #000);
    box-shadow:0 2px 8px rgba(124,58,237,.4), inset 0 0 10px rgba(168,85,247,.3)}
  .sa-msg-wrap.ai .sa-avatar::after{content:'';position:absolute;inset:0;border-radius:50%;
    background:
      radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 40%),
      radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 40%);
    mix-blend-mode:screen;filter:blur(4px)}
  .sa-msg-wrap.ben .sa-avatar{background:linear-gradient(135deg,var(--sub),var(--sub2));color:#fff}
  .sa-msg-wrap.ben .sa-avatar .material-icons-outlined{font-size:16px;position:relative;z-index:1}

  /* AI mesaj içi formatlar */
  .sa-msg.ai .sa-h{display:block;font-weight:700;font-size:14px;color:var(--mor1);margin:8px 0 4px;letter-spacing:.1px}
  .sa-msg.ai .sa-h:first-child{margin-top:0}
  .sa-msg.ai b{font-weight:700;color:var(--ink)}
  .sa-msg.ai .sa-mad{color:var(--mor1);font-weight:700;margin-right:4px}

  /* AI düşünüyor */
  .sa-yaziyor{align-self:flex-start;display:inline-flex;align-items:center;gap:10px;
    background:var(--card);border:1px solid var(--line);border-radius:16px;border-bottom-left-radius:6px;
    padding:10px 14px;box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-yaziyor .sa-mini-orb{width:26px;height:26px;border-radius:50%;flex-shrink:0;position:relative;overflow:hidden;
    background:radial-gradient(circle at 30% 30%, #1e1b4b, #000);animation:saPulse 1.5s ease-in-out infinite;
    box-shadow:0 0 10px rgba(168,85,247,.5)}
  .sa-yaziyor .sa-mini-orb::after{content:'';position:absolute;inset:0;border-radius:50%;
    background:
      radial-gradient(circle at 30% 40%, #a855f7 0%, transparent 45%),
      radial-gradient(circle at 70% 60%, #ec4899 0%, transparent 45%);
    mix-blend-mode:screen;filter:blur(3px)}
  @keyframes saPulse{0%,100%{transform:scale(1);box-shadow:0 0 10px rgba(168,85,247,.5)}
    50%{transform:scale(1.1);box-shadow:0 0 20px rgba(168,85,247,.8)}}
  .sa-yaziyor .sa-yz-txt{font-size:12.5px;font-weight:700;
    background:linear-gradient(90deg,#7C3AED,#4F46E5,#EC4899,#7C3AED);background-size:200% auto;
    -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;
    animation:saShine 2.2s linear infinite}
  .sa-yaziyor .sa-dots{display:inline-flex;gap:3px}
  .sa-yaziyor .sa-dots i{width:5px;height:5px;border-radius:50%;display:inline-block;animation:saBounce 1.2s infinite}
  .sa-yaziyor .sa-dots i:nth-child(1){background:#7C3AED}
  .sa-yaziyor .sa-dots i:nth-child(2){background:#4F46E5;animation-delay:.18s}
  .sa-yaziyor .sa-dots i:nth-child(3){background:#EC4899;animation-delay:.36s}
  @keyframes saBounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
  @keyframes saShine{to{background-position:200% center}}

  /* Export + teklif balonları */
  .sa-export{align-self:flex-start;max-width:92%;background:var(--card);border:1px solid var(--line);
    border-radius:16px;border-bottom-left-radius:6px;padding:12px 14px;
    display:flex;flex-wrap:wrap;gap:7px;animation:saIn .25s ease-out;box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-export .sa-exp-btn{display:inline-flex;align-items:center;gap:5px;padding:8px 13px;
    border-radius:10px;background:color-mix(in srgb,var(--yesil) 12%,var(--card));
    color:var(--yesil);border:1px solid color-mix(in srgb,var(--yesil) 35%,var(--line));
    font-size:12.5px;font-weight:700;cursor:pointer;transition:all .15s;font-family:inherit}
  .sa-export .sa-exp-btn:hover{background:var(--yesil);color:#fff;border-color:var(--yesil)}
  .sa-export .sa-exp-btn .material-icons-outlined{font-size:15px}

  .sa-teklif{align-self:flex-start;max-width:92%;background:color-mix(in srgb,var(--mor1) 8%,var(--card));
    border:1px solid color-mix(in srgb,var(--mor1) 30%,var(--line));
    border-radius:16px;border-bottom-left-radius:6px;padding:12px 14px;
    font-size:13px;color:var(--ink);animation:saIn .25s ease-out}
  .sa-teklif .evet{margin-top:9px;background:var(--mor1);color:#fff;padding:8px 15px;font-size:12.5px;
    border:0;border-radius:10px;cursor:pointer;font-weight:700;transition:transform .12s}
  .sa-teklif .evet:hover{transform:translateY(-1px)}

  /* Floating input bar */
  #saInputBar{position:sticky;bottom:14px;z-index:30;margin-top:16px;padding:4px}
  #saInputBar .wrap{display:flex;gap:8px;align-items:center;padding:8px 8px 8px 16px;
    border-radius:999px;background:var(--card);border:1px solid var(--line);
    box-shadow:0 10px 30px -8px rgba(0,0,0,.12), 0 4px 12px rgba(0,0,0,.06);
    transition:border-color .15s, box-shadow .15s}
  #saInputBar .wrap:focus-within{border-color:var(--mor1);
    box-shadow:0 10px 30px -8px color-mix(in srgb,var(--mor1) 30%,transparent),
               0 0 0 3px color-mix(in srgb,var(--mor1) 12%,transparent)}
  #saInputBar input{flex:1;border:none;outline:none;background:transparent;color:var(--ink);
    font-size:14px;padding:9px 0;font-family:inherit}
  #saInputBar input::placeholder{color:var(--sub2)}
  #saInputBar .gnd{width:40px;height:40px;border-radius:50%;flex-shrink:0;border:none;cursor:pointer;
    background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 4px 14px -2px color-mix(in srgb,var(--mor1) 50%,transparent);
    transition:transform .15s, box-shadow .15s}
  #saInputBar .gnd:hover{transform:scale(1.06);
    box-shadow:0 6px 18px -2px color-mix(in srgb,var(--mor1) 60%,transparent)}
  #saInputBar .gnd:active{transform:scale(.95)}
  #saInputBar .gnd:disabled{opacity:.5;cursor:not-allowed;transform:none}
  #saInputBar .gnd .material-icons-outlined{font-size:19px}
  #saInputBar .ipucu{text-align:center;font-size:10.5px;color:var(--sub2);margin-top:6px;font-weight:500}

  @media (max-width:640px){
    #saHero{padding:30px 14px 14px}
    #saHero h1{font-size:22px}
    #saHero .sa-orb-stage{width:120px;height:120px}
    #saHero .sa-orb{width:100px;height:100px}
    #saOneriler{grid-template-columns:1fr}
    .sa-msg{max-width:92%;font-size:13px}
    #saInputBar{bottom:10px}
  }
</style>

<div id="saWrap">

  {{-- Hero (sohbet başlamadan önce) --}}
  <div id="saHero">
    <div class="sa-orb-stage">
      <div class="sa-orb">
        <div class="layer l1"></div>
        <div class="layer l2"></div>
        <div class="layer l3"></div>
        <div class="shine"></div>
      </div>
      <div class="orbit"></div>
    </div>
    <h1>Satınalma AI Asistanı</h1>
    <p>Stok, fiyat ve tedarikçi verini anında yorumlar. Soru sor, somut aksiyon al.</p>
  </div>

  {{-- Hızlı öneri kartları --}}
  <div id="saOneriler">
    <button type="button" class="sa-oneri" onclick="saSor('Bu hafta ne almalıyım?')">
      <div class="sa-oneri-ikon" style="background:linear-gradient(135deg,#7C3AED,#4F46E5)"><span class="material-icons-outlined">shopping_cart</span></div>
      <div>
        <div class="sa-oneri-bas">Bu hafta ne almalıyım?</div>
        <div class="sa-oneri-alt">Kritik stok + sipariş önerisi</div>
      </div>
    </button>
    <button type="button" class="sa-oneri" onclick="saSor('Gizli zam var mı?')">
      <div class="sa-oneri-ikon" style="background:linear-gradient(135deg,#F43F5E,#F59E0B)"><span class="material-icons-outlined">trending_up</span></div>
      <div>
        <div class="sa-oneri-bas">Gizli zam var mı?</div>
        <div class="sa-oneri-alt">Sessiz fiyat artışlarını yakala</div>
      </div>
    </button>
    <button type="button" class="sa-oneri" onclick="saSor('En çok nereye harcıyorum?')">
      <div class="sa-oneri-ikon" style="background:linear-gradient(135deg,#10B981,#06B6D4)"><span class="material-icons-outlined">payments</span></div>
      <div>
        <div class="sa-oneri-bas">En çok nereye harcıyorum?</div>
        <div class="sa-oneri-alt">Kategori / malzeme dağılımı</div>
      </div>
    </button>
    <button type="button" class="sa-oneri" onclick="saSor('Hangi tedarikçiyle pazarlık etmeliyim?')">
      <div class="sa-oneri-ikon" style="background:linear-gradient(135deg,#EC4899,#8B5CF6)"><span class="material-icons-outlined">handshake</span></div>
      <div>
        <div class="sa-oneri-bas">Pazarlık önerisi</div>
        <div class="sa-oneri-alt">Hangi tedarikçi — hangi kalem</div>
      </div>
    </button>
  </div>

  {{-- Araç çubuğu --}}
  <div id="saArac">
    <button type="button" class="sa-arac-btn" onclick="saRaporMenu()">
      <span class="material-icons-outlined">file_download</span> Rapor İndir (Excel)
    </button>
    <div class="sa-sayac" id="saSayac" style="display:none"><span class="dot"></span><span id="saSayacTxt">0 mesaj</span></div>
  </div>

  {{-- Sohbet --}}
  <div id="saChat"></div>

  {{-- Floating input --}}
  <div id="saInputBar">
    <div class="wrap">
      <input type="text" id="saInput" placeholder="Satınalma sorunu yaz…" onkeydown="if(event.key==='Enter')saSor()" autocomplete="off">
      <button type="button" class="gnd" id="saGnd" onclick="saSor()" title="Gönder">
        <span class="material-icons-outlined">arrow_upward</span>
      </button>
    </div>
    <div class="ipucu"><b>Enter</b> ile gönder · AI yanıtları veriye dayalıdır</div>
  </div>

</div>

<script>
const SA_CSRF = document.querySelector('meta[name=csrf-token]').content;

function saEsc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function saFormat(t){
  let h=saEsc(t);
  h=h.replace(/^\s*#{1,6}\s*(.+?)\s*$/gm,'<span class="sa-h">$1</span>');
  h=h.replace(/\*\*([^*]+?)\*\*/g,'<b>$1</b>');
  h=h.replace(/__([^_]+?)__/g,'<b>$1</b>');
  h=h.replace(/^[ \t]*[-*•]\s+/gm,'<span class="sa-mad">•</span> ');
  return h;
}

function saHeroGizle(){
  const h=document.getElementById('saHero'); if(h) h.style.display='none';
  const o=document.getElementById('saOneriler'); if(o) o.style.display='none';
}

function saSayacGuncelle(){
  const n=document.querySelectorAll('#saChat .sa-msg-wrap, #saChat .sa-yaziyor, #saChat .sa-export, #saChat .sa-teklif').length;
  if(!n) return;
  const el=document.getElementById('saSayac');
  const tx=document.getElementById('saSayacTxt');
  if(el){el.style.display=''; tx.textContent=n+' mesaj';}
}

function saYaziyor(){
  const d=document.createElement('div');
  d.className='sa-yaziyor';
  d.innerHTML='<span class="sa-mini-orb"></span>'+
    '<span class="sa-yz-txt">AI düşünüyor</span>'+
    '<span class="sa-dots"><i></i><i></i><i></i></span>';
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  return d;
}

function saBubble(metin, ben){
  const w=document.createElement('div');
  w.className='sa-msg-wrap '+(ben?'ben':'ai');
  const av=document.createElement('div');
  av.className='sa-avatar';
  if(ben) av.innerHTML='<span class="material-icons-outlined">person</span>';
  const m=document.createElement('div');
  m.className='sa-msg '+(ben?'ben':'ai');
  if(ben) m.textContent=metin; else m.innerHTML=saFormat(metin);
  w.appendChild(av); w.appendChild(m);
  document.getElementById('saChat').appendChild(w);
  w.scrollIntoView({behavior:'smooth',block:'end'});
  saSayacGuncelle();
  return w;
}

const SA_RAPORLAR=[
  {tip:'kritik',ad:'Kritik Stok'},{tip:'zam',ad:'Gizli Zamlar'},{tip:'tedarikci',ad:'Tedarikçi Karnesi'},
  {tip:'harcama',ad:'Aylık Harcama'},{tip:'siparis',ad:'Açık Siparişler'},{tip:'talep',ad:'Talepler'}
];
const SA_AD={kritik:'Kritik Stok',zam:'Gizli Zamlar',tedarikci:'Tedarikçi Karnesi',harcama:'Aylık Harcama',siparis:'Açık Siparişler',talep:'Talepler'};
let saTeklifAcik=false; let saTeklifTip='kritik';

function saIndir(tip){
  const f=document.createElement('iframe');
  f.style.display='none'; f.src='/satinalma-asistan/excel?tip='+tip;
  document.body.appendChild(f);
  setTimeout(()=>f.remove(),15000);
}

function saExportButonlar(list){
  saTeklifAcik=false;
  const d=document.createElement('div');
  d.className='sa-export';
  (list||SA_RAPORLAR).forEach(o=>{
    const b=document.createElement('button');
    b.type='button'; b.className='sa-exp-btn';
    b.innerHTML='<span class="material-icons-outlined">table_view</span> '+saEsc(o.ad);
    b.onclick=()=>saIndir(o.tip);
    d.appendChild(b);
  });
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  saSayacGuncelle();
}

function saTeklifBalonu(tip){
  saTeklifAcik=true; saTeklifTip=tip||'kritik';
  const d=document.createElement('div');
  d.className='sa-teklif';
  d.innerHTML='📊 İstersen bu verileri (<b>'+(SA_AD[saTeklifTip]||'rapor')+'</b>) <b>Excel\'e aktarayım</b> — aktarayım mı?';
  const b=document.createElement('button');
  b.type='button'; b.className='evet';
  b.textContent='Evet, Excel\'e aktar';
  b.onclick=()=>{ b.disabled=true; b.textContent='İndiriliyor…'; saIndir(saTeklifTip); saTeklifAcik=false; };
  d.appendChild(b);
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  saSayacGuncelle();
}

function saRaporMenu(){
  saHeroGizle();
  saBubble('Hangi raporu indirmek istersin?',false);
  saExportButonlar();
}

let saBusy=false;
async function saSor(metin){
  if(saBusy) return;
  const inp=document.getElementById('saInput');
  const soru=(metin||inp.value||'').trim();
  if(!soru) return;
  inp.value='';
  saHeroGizle();
  if(saTeklifAcik && /^(evet|evt|olur|aktar|tabii|tabi|tamam|he|hı|hi|ok|okey|peki|yes)\b/i.test(soru)){
    saBubble(soru,true); saBubble('Tamam, '+(SA_AD[saTeklifTip]||'rapor')+' indiriliyor… 📥',false); saIndir(saTeklifTip); saTeklifAcik=false; return;
  }
  saBubble(soru,true);
  saBusy=true;
  document.getElementById('saGnd').disabled=true;
  const bekle=saYaziyor();
  try{
    const r=await fetch('/satinalma-asistan/sor',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':SA_CSRF,'Accept':'application/json'},body:JSON.stringify({soru})});
    const j=await r.json();
    bekle.remove();
    saBubble(j.cevap||'Cevap alınamadı.',false);
    if(j.exportlar && j.exportlar.length){ saExportButonlar(j.exportlar); }
  }catch(e){ bekle.remove(); saBubble('Bağlantı hatası, tekrar dene.',false); }
  saBusy=false;
  document.getElementById('saGnd').disabled=false;
  document.getElementById('saInput').focus();
}
</script>
@endsection

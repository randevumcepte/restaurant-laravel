@extends('layout.app')
@section('title', 'Satınalma AI Asistanı')
@section('rk_ikon', 'smart_toy')
@section('baslik', 'Satınalma AI Asistanı')
@section('rk_alt', 'Stok + fiyat + tedarikçi verinden anında cevap')

@section('content')
<style>
  /* ===== Satınalma AI — modern tasarım ===== */
  #saWrap{--mor1:#7C3AED;--mavi:#4F46E5;--pembe:#EC4899;--yesil:#10B981;--amber:#F59E0B;
    --card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
    max-width:860px;margin:0 auto;padding-bottom:120px}
  body.koyu #saWrap{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650;--bg:#0B1020}
  .rk-main{background:var(--bg)}

  /* --- Hero (sohbet başlangıcında) WOW tasarım --- */
  #saHero{position:relative;text-align:center;padding:40px 20px 10px;overflow:hidden;isolation:isolate}

  /* Mesh gradient blob'lar — arka plan */
  #saHero .sa-bg-mesh{position:absolute;inset:-40px;z-index:-1;pointer-events:none;overflow:hidden;filter:blur(60px);opacity:.55}
  body.koyu #saHero .sa-bg-mesh{opacity:.4}
  #saHero .sa-bg-mesh .blob{position:absolute;border-radius:50%;will-change:transform}
  #saHero .sa-bg-mesh .b1{width:280px;height:280px;background:#7C3AED;top:-60px;left:10%;animation:saBlob1 14s ease-in-out infinite}
  #saHero .sa-bg-mesh .b2{width:240px;height:240px;background:#EC4899;top:30px;right:5%;animation:saBlob2 16s ease-in-out infinite}
  #saHero .sa-bg-mesh .b3{width:220px;height:220px;background:#4F46E5;bottom:-40px;left:40%;animation:saBlob3 18s ease-in-out infinite}
  #saHero .sa-bg-mesh .b4{width:180px;height:180px;background:#F59E0B;top:40%;right:35%;animation:saBlob1 20s ease-in-out infinite reverse}
  @keyframes saBlob1{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(40px,-30px) scale(1.15)}}
  @keyframes saBlob2{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(-50px,20px) scale(.9)}}
  @keyframes saBlob3{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(30px,-40px) scale(1.1)}}

  /* Hero içerik */
  #saHero .sa-hero-inner{position:relative;z-index:1}

  /* Orb + ring */
  #saHero .sa-orb-wrap{position:relative;width:100px;height:100px;margin:0 auto 20px;animation:saIn .6s cubic-bezier(.34,1.56,.64,1) both}
  #saHero .sa-ring{position:absolute;inset:-6px;border-radius:50%;
    background:conic-gradient(from 0deg,#7C3AED,#4F46E5,#EC4899,#F59E0B,#10B981,#7C3AED);
    animation:saSpin 6s linear infinite;mask:radial-gradient(farthest-side,transparent calc(100% - 3px),#000 calc(100% - 2px));
    -webkit-mask:radial-gradient(farthest-side,transparent calc(100% - 3px),#000 calc(100% - 2px))}
  #saHero .sa-ring-outer{position:absolute;inset:-20px;border-radius:50%;border:1px dashed color-mix(in srgb,var(--mor1) 25%,transparent);
    animation:saSpin 30s linear infinite reverse}
  #saHero .sa-orb-xl{position:absolute;inset:0;border-radius:50%;
    background:conic-gradient(from 180deg,#7C3AED,#4F46E5,#EC4899,#F59E0B,#7C3AED);
    display:flex;align-items:center;justify-content:center;
    box-shadow:0 20px 50px -10px rgba(124,58,237,.5), 0 0 60px -10px rgba(236,72,153,.3), inset 0 0 30px rgba(255,255,255,.15);
    animation:saPulseBig 3s ease-in-out infinite}
  #saHero .sa-orb-xl::before{content:'';position:absolute;inset:5px;border-radius:50%;
    background:var(--card);box-shadow:inset 0 2px 10px color-mix(in srgb,var(--mor1) 15%,transparent)}
  #saHero .sa-orb-xl .material-icons-outlined{position:relative;z-index:1;font-size:44px;
    background:linear-gradient(135deg,#7C3AED,#4F46E5,#EC4899);
    -webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;
    animation:saSparkle 2.5s ease-in-out infinite}
  @keyframes saSpin{to{transform:rotate(360deg)}}
  @keyframes saPulseBig{0%,100%{box-shadow:0 20px 50px -10px rgba(124,58,237,.5), 0 0 60px -10px rgba(236,72,153,.3), inset 0 0 30px rgba(255,255,255,.15)}
    50%{box-shadow:0 24px 60px -8px rgba(124,58,237,.65), 0 0 80px -5px rgba(236,72,153,.45), inset 0 0 30px rgba(255,255,255,.2)}}
  @keyframes saSparkle{0%,100%{transform:scale(1) rotate(0deg)}50%{transform:scale(1.1) rotate(5deg)}}

  /* Online rozet */
  #saHero .sa-online{position:absolute;top:-6px;right:-10px;display:inline-flex;align-items:center;gap:5px;
    padding:4px 9px;border-radius:999px;background:var(--card);border:1px solid color-mix(in srgb,var(--yesil) 35%,var(--line));
    color:var(--yesil);font-size:10.5px;font-weight:800;letter-spacing:.3px;text-transform:uppercase;
    box-shadow:0 4px 12px rgba(0,0,0,.08)}
  #saHero .sa-online .dot{width:7px;height:7px;border-radius:50%;background:var(--yesil);
    box-shadow:0 0 0 0 var(--yesil);animation:saOnlinePulse 1.6s ease-out infinite}
  @keyframes saOnlinePulse{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--yesil) 70%,transparent)}100%{box-shadow:0 0 0 8px transparent}}

  /* Shimmer başlık */
  #saHero h1{font-size:30px;font-weight:900;margin:0 0 8px;letter-spacing:-.8px;
    background:linear-gradient(90deg,var(--ink) 0%,var(--mor1) 25%,var(--mavi) 50%,var(--pembe) 75%,var(--ink) 100%);
    background-size:200% auto;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;
    animation:saShimmer 4s linear infinite, saInUp .6s ease-out .1s both}
  @keyframes saShimmer{to{background-position:200% center}}

  #saHero p{font-size:14.5px;color:var(--sub);margin:0 auto;max-width:480px;line-height:1.55;
    animation:saInUp .6s ease-out .2s both}

  /* Rotating örnek soru */
  #saHero .sa-rotator{margin-top:14px;display:inline-flex;align-items:center;gap:8px;padding:7px 14px;
    border-radius:999px;background:color-mix(in srgb,var(--mor1) 7%,var(--card));
    border:1px solid color-mix(in srgb,var(--mor1) 25%,var(--line));
    font-size:12.5px;animation:saInUp .6s ease-out .3s both}
  #saHero .sa-rotator .et{color:var(--sub2);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.4px}
  #saHero .sa-rotator .rot{color:var(--mor1);font-weight:700;transition:opacity .3s ease}

  @keyframes saIn{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)}}
  @keyframes saInUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}

  /* Sparkle partiküller — hero'nun etrafında */
  #saHero .sa-spark{position:absolute;pointer-events:none;color:var(--mor1);opacity:0;
    animation:saSparkFloat 4s ease-in-out infinite}
  #saHero .sa-spark:nth-child(1){top:20%;left:15%;animation-delay:0s;color:#7C3AED}
  #saHero .sa-spark:nth-child(2){top:60%;left:8%;animation-delay:1s;color:#EC4899}
  #saHero .sa-spark:nth-child(3){top:30%;right:12%;animation-delay:2s;color:#F59E0B}
  #saHero .sa-spark:nth-child(4){top:70%;right:20%;animation-delay:.5s;color:#4F46E5}
  #saHero .sa-spark:nth-child(5){top:10%;left:50%;animation-delay:1.5s;color:#10B981}
  #saHero .sa-spark .material-icons-outlined{font-size:14px}
  @keyframes saSparkFloat{0%,100%{opacity:0;transform:translateY(0) scale(.5)}
    40%,60%{opacity:.7;transform:translateY(-12px) scale(1)}}

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

  /* --- Araç çubuğu (Rapor indir) --- */
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

  /* --- Chat alanı --- */
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
    align-items:center;justify-content:center;color:#fff;margin-top:4px}
  .sa-msg-wrap.ai .sa-avatar{background:conic-gradient(from 180deg,#7C3AED,#4F46E5,#EC4899,#F59E0B,#7C3AED);
    position:relative}
  .sa-msg-wrap.ai .sa-avatar::before{content:'';position:absolute;inset:2px;border-radius:50%;background:var(--card)}
  .sa-msg-wrap.ai .sa-avatar .material-icons-outlined{position:relative;z-index:1;font-size:15px;
    background:linear-gradient(135deg,#7C3AED,#EC4899);-webkit-background-clip:text;background-clip:text;color:transparent}
  .sa-msg-wrap.ben .sa-avatar{background:linear-gradient(135deg,var(--sub),var(--sub2));color:#fff}
  .sa-msg-wrap.ben .sa-avatar .material-icons-outlined{font-size:16px}

  /* AI mesaj içi formatlar */
  .sa-msg.ai .sa-h{display:block;font-weight:700;font-size:14px;color:var(--mor1);margin:8px 0 4px;letter-spacing:.1px}
  .sa-msg.ai .sa-h:first-child{margin-top:0}
  .sa-msg.ai b{font-weight:700;color:var(--ink)}
  .sa-msg.ai .sa-mad{color:var(--mor1);font-weight:700;margin-right:4px}

  /* --- AI düşünüyor --- */
  .sa-yaziyor{align-self:flex-start;display:inline-flex;align-items:center;gap:10px;
    background:var(--card);border:1px solid var(--line);border-radius:16px;border-bottom-left-radius:6px;
    padding:10px 14px;box-shadow:0 2px 10px -2px rgba(0,0,0,.05)}
  .sa-yaziyor .sa-orb{width:26px;height:26px;border-radius:50%;flex-shrink:0;
    background:conic-gradient(from 180deg,#7C3AED,#4F46E5,#EC4899,#7C3AED);
    display:flex;align-items:center;justify-content:center;position:relative;
    animation:saPulse 1.4s ease-in-out infinite}
  .sa-yaziyor .sa-orb::before{content:'';position:absolute;inset:3px;border-radius:50%;background:var(--card)}
  .sa-yaziyor .sa-orb .material-icons-outlined{position:relative;z-index:1;font-size:14px;
    background:linear-gradient(135deg,#7C3AED,#EC4899);-webkit-background-clip:text;background-clip:text;color:transparent}
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
  @keyframes saPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(124,58,237,.4)}50%{transform:scale(1.08);box-shadow:0 0 0 8px rgba(124,58,237,0)}}
  @keyframes saShine{to{background-position:200% center}}

  /* --- Export butonları balonu --- */
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

  /* --- Floating input bar --- */
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
    #saHero{padding:20px 14px 14px}
    #saHero h1{font-size:20px}
    #saOneriler{grid-template-columns:1fr}
    .sa-msg{max-width:92%;font-size:13px}
    #saInputBar{bottom:10px}
  }
</style>

<div id="saWrap">

  {{-- Hero (sohbet başlamadan önce) --}}
  <div id="saHero">
    <div class="sa-bg-mesh">
      <div class="blob b1"></div><div class="blob b2"></div>
      <div class="blob b3"></div><div class="blob b4"></div>
    </div>

    <span class="sa-spark"><span class="material-icons-outlined">auto_awesome</span></span>
    <span class="sa-spark"><span class="material-icons-outlined">star</span></span>
    <span class="sa-spark"><span class="material-icons-outlined">auto_awesome</span></span>
    <span class="sa-spark"><span class="material-icons-outlined">star</span></span>
    <span class="sa-spark"><span class="material-icons-outlined">auto_awesome</span></span>

    <div class="sa-hero-inner">
      <div class="sa-orb-wrap">
        <div class="sa-ring-outer"></div>
        <div class="sa-ring"></div>
        <div class="sa-orb-xl"><span class="material-icons-outlined">auto_awesome</span></div>
        <div class="sa-online"><span class="dot"></span>Çevrimiçi</div>
      </div>
      <h1>Satınalma AI Asistanı</h1>
      <p>Stok, fiyat ve tedarikçi verini anında yorumlar. Soru sor, somut aksiyon al.</p>
      <div class="sa-rotator">
        <span class="et">Örnek</span>
        <span class="rot" id="saRot">"Bu hafta ne almalıyım?"</span>
      </div>
    </div>
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

// Hero ve öneri kartlarını gizle (ilk mesajdan sonra)
function saHeroGizle(){
  const h=document.getElementById('saHero'); if(h) h.style.display='none';
  const o=document.getElementById('saOneriler'); if(o) o.style.display='none';
  if (saRotTimer) { clearInterval(saRotTimer); saRotTimer=null; }
}

// Hero'da örnek soru metnini döngüyle değiştir
const SA_ORNEKLER=[
  '"Bu hafta ne almalıyım?"',
  '"Soğana gizli zam var mı?"',
  '"Et Dünyası ile pazarlık payım ne?"',
  '"Bu ay en çok nereye harcadım?"',
  '"Hangi tedarikçi daha ucuz tavuk veriyor?"',
  '"Kritik stokta kaç kalem var?"',
  '"Son 30 günde en çok artan fiyat hangisi?"'
];
let saRotIdx=0, saRotTimer=null;
function saRotStart(){
  const el=document.getElementById('saRot'); if(!el) return;
  saRotTimer=setInterval(()=>{
    saRotIdx=(saRotIdx+1)%SA_ORNEKLER.length;
    el.style.opacity='0';
    setTimeout(()=>{ el.textContent=SA_ORNEKLER[saRotIdx]; el.style.opacity='1'; }, 300);
  }, 3200);
}
document.addEventListener('DOMContentLoaded', saRotStart);

function saSayacGuncelle(){
  const n=document.querySelectorAll('#saChat .sa-msg-wrap, #saChat .sa-yaziyor, #saChat .sa-export, #saChat .sa-teklif').length;
  if(!n) return;
  const el=document.getElementById('saSayac');
  const tx=document.getElementById('saSayacTxt');
  if(el){el.style.display=''; tx.textContent=n+' mesaj';}
}

// AI "düşünüyor"
function saYaziyor(){
  const d=document.createElement('div');
  d.className='sa-yaziyor';
  d.innerHTML='<span class="sa-orb"><span class="material-icons-outlined">auto_awesome</span></span>'+
    '<span class="sa-yz-txt">AI düşünüyor</span>'+
    '<span class="sa-dots"><i></i><i></i><i></i></span>';
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  return d;
}

// Mesaj balonu (avatar + içerik)
function saBubble(metin, ben){
  const w=document.createElement('div');
  w.className='sa-msg-wrap '+(ben?'ben':'ai');
  const av=document.createElement('div');
  av.className='sa-avatar';
  av.innerHTML='<span class="material-icons-outlined">'+(ben?'person':'auto_awesome')+'</span>';
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

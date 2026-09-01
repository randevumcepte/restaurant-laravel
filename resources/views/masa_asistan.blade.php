<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>{{ $sube->ad ?? 'Restoran' }} · {{ $masa->ad ?? '' }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Dancing+Script:wght@600;700&display=swap');
  :root{ --mor:#8B3BEA; --mor2:#A855F7; --mavi:#6D28D9; --bg:#150a1a; --card:#241329;
    --gold:#E9C46A; --gold2:#C9962F; --ink:#F3E9EE; --sessiz:#B49CB6; --cizgi:rgba(233,196,106,.18);
    --serif:'Playfair Display',Georgia,'Times New Roman',serif; --script:'Dancing Script',cursive; }
  *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  html,body{ margin:0; height:100%; color:var(--ink); font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:#120912;
    background-image:
      radial-gradient(900px 620px at 88% -8%, rgba(233,150,60,.20), transparent 60%),
      radial-gradient(760px 560px at 6% 108%, rgba(139,59,234,.20), transparent 58%),
      radial-gradient(1200px 900px at 60% 0%, #331436 0%, #1c0d22 44%, #120912 100%); }
  #app{ display:flex; flex-direction:column; height:100dvh; }
  header{ padding:15px 16px 12px; display:flex; align-items:center; gap:11px; }
  header .logo{ display:inline-flex; align-items:center; gap:7px; }
  header .logo .toque{ font-size:22px; filter:drop-shadow(0 2px 6px rgba(233,196,106,.5)); }
  header .logo .lz{ display:flex; flex-direction:column; line-height:1; }
  header .logo .lz b{ font-family:var(--serif); font-weight:800; font-size:20px; letter-spacing:1px;
    background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
  header .logo .lz i{ font-style:normal; font-size:8px; font-weight:700; letter-spacing:4px; color:var(--gold2); margin-top:2px; }
  header .ad{ font-family:var(--serif); font-weight:700; font-size:15px; color:var(--ink); opacity:.9; }
  header .menuBtn{ margin-left:auto; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; border:none; font-size:12.5px; font-weight:800; padding:8px 14px; border-radius:22px; box-shadow:0 5px 14px rgba(201,150,47,.4); }
  header .masa{ margin-left:8px; background:rgba(139,59,234,.22); color:#D6BBF3; font-size:12px; font-weight:700; padding:5px 11px; border-radius:20px; border:1px solid rgba(139,59,234,.3); }
  .menuBaslik{ margin:16px 4px 8px; font-family:var(--serif); font-size:17px; font-weight:800; color:var(--gold); display:flex; align-items:center; gap:8px; }
  .menuBaslik span{ font-size:22px; }
  #orb-wrap{ display:flex; flex-direction:column; align-items:center; padding:8px 0 2px; }
  #orb{ width:120px; height:120px; border-radius:50%; position:relative;
    background:conic-gradient(from 0deg,#E9C46A,#A855F7,#8B3BEA,#F6DFA0,#E9C46A);
    box-shadow:0 0 42px rgba(168,85,247,.45),0 0 24px rgba(233,196,106,.4); animation:spin 6s linear infinite; }
  #orb::after{ content:''; position:absolute; inset:26px; border-radius:50%;
    background:radial-gradient(circle,#fff 0%,rgba(255,255,255,.85) 45%,rgba(255,255,255,0) 72%); }
  #orb.dinliyor{ animation:spin 3s linear infinite, pulse 1s ease-in-out infinite; }
  @keyframes spin{ to{ transform:rotate(360deg); } }
  @keyframes pulse{ 0%,100%{ transform:scale(1);} 50%{ transform:scale(1.06);} }
  #durum{ text-align:center; color:var(--sessiz); font-size:13px; margin:8px 20px 0; min-height:18px; }
  #sohbet{ flex:1; overflow-y:auto; padding:12px 14px 4px; -webkit-overflow-scrolling:touch; }
  .balon{ max-width:86%; padding:11px 15px; border-radius:16px; margin-bottom:10px; font-size:14.5px; line-height:1.4; }
  .ben{ margin-left:auto; background:linear-gradient(135deg,var(--mor),var(--mavi)); border-bottom-right-radius:4px; }
  .ai{ background:var(--card); border:1px solid var(--cizgi); border-bottom-left-radius:4px; color:#EBDDE9; }
  .cips{ display:flex; gap:8px; overflow-x:auto; padding:6px 14px; }
  .cip{ flex:0 0 auto; background:var(--card); border:1px solid var(--cizgi); color:#E7D3B4; font-size:12.5px; font-weight:600;
    padding:9px 13px; border-radius:20px; white-space:nowrap; }
  footer{ padding:10px 12px calc(10px + env(safe-area-inset-bottom)); display:flex; gap:8px; align-items:center; }
  #metin{ flex:1; background:var(--card); border:none; color:#fff; font-size:14.5px; padding:13px 16px; border-radius:24px; outline:none; }
  .yuvarlak{ width:48px; height:48px; border-radius:50%; border:none; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
  #mic{ background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 4px 14px rgba(124,58,237,.4); }
  #mic.acik{ background:linear-gradient(135deg,#16A34A,#22C55E); box-shadow:0 0 0 4px rgba(34,197,94,.25),0 4px 14px rgba(34,197,94,.5); }
  #mic.dinliyor{ background:linear-gradient(135deg,#F43F5E,#EF4444); }
  #gonder{ background:var(--card); color:var(--gold); }
  /* ---- Gorsel kartlar (PREMIUM) ---- */
  .kartsira{ display:flex; gap:14px; overflow-x:auto; padding:6px 2px 18px; margin-bottom:4px;
    -webkit-overflow-scrolling:touch; scroll-snap-type:x mandatory; }
  .kartsira::-webkit-scrollbar{ height:0; }
  .mkart{ flex:0 0 236px; scroll-snap-align:start; position:relative; height:312px; border-radius:24px; overflow:hidden;
    background:#1e1024; border:1px solid rgba(255,255,255,.07); box-shadow:0 14px 34px rgba(0,0,0,.5);
    opacity:0; transform:translateY(18px) scale(.97); animation:kartGir .55s cubic-bezier(.2,.75,.2,1) forwards; }
  @keyframes kartGir{ to{ opacity:1; transform:none; } }
  .mkart .gor{ position:absolute; inset:0; background:#1e1024; }
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
    background:linear-gradient(160deg,#2a1731,#1c0f22); box-shadow:0 8px 20px rgba(0,0,0,.4);
    opacity:0; transform:translateY(14px); animation:kartGir .5s cubic-bezier(.2,.75,.2,1) forwards; }
  .kkart:active{ border-color:rgba(233,196,106,.6); }
  .kkart .ke{ font-size:32px; filter:drop-shadow(0 3px 8px rgba(0,0,0,.4)); }
  .kkart .kad{ font-weight:700; font-size:12.5px; color:#E7ECF5; text-align:center; }
  /* ---- Sepet (sipariş onayı) ---- */
  .sepet{ background:linear-gradient(160deg,#2a1731,#1c0f22); border:1px solid #2D3752; border-radius:18px; padding:14px 15px; margin-bottom:10px;
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
  #lightbox{ position:fixed; inset:0; z-index:80; background:rgba(4,7,15,.95); backdrop-filter:blur(8px);
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
  /* ---- SALT OKUNUR PREMIUM tam ekran menu ---- */
  #menuTam{ position:fixed; inset:0; z-index:70; background:radial-gradient(1000px 700px at 70% -8%, #331436 0%, #1c0d22 46%, #120912 100%); display:none; flex-direction:column; }
  #menuTam .mt-bar{ display:flex; align-items:center; gap:10px; padding:15px 18px; border-bottom:1px solid rgba(255,255,255,.06);
    background:linear-gradient(135deg,rgba(139,59,234,.30),rgba(51,20,54,.55)); border-bottom:1px solid var(--cizgi); position:sticky; top:0; z-index:2; backdrop-filter:blur(8px); }
  #menuTam .mt-title{ font-family:var(--serif); font-weight:800; font-size:19px; color:var(--gold); letter-spacing:.3px; }
  #menuTam .mt-kapat{ margin-left:auto; background:rgba(255,255,255,.16); color:#fff; border:none; font-size:13.5px; font-weight:800; padding:9px 15px; border-radius:22px; }
  #menuTam .mt-body{ flex:1; overflow-y:auto; padding:4px 14px 48px; -webkit-overflow-scrolling:touch; }
  #menuTam .mt-yukle{ text-align:center; color:#94A3B8; padding:48px 0; }
  /* icerigi masaustunde ortala + genisligi sinirla (full-bleed cirkinligi biter) */
  #menuTam .mt-col{ width:100%; max-width:1000px; margin:0 auto; }
  /* SEVIYE 1: sik gradient hero (isletme foto yuklerse ileride arka plana konur) */
  #menuTam .mt-hero{ position:relative; margin-top:6px; margin-bottom:22px; padding:40px 20px 34px; overflow:hidden;
    border-radius:24px; text-align:center; box-shadow:0 20px 44px -22px rgba(0,0,0,.85);
    background:radial-gradient(120% 140% at 50% 0%, #3a1d63 0%, #241247 45%, #12102b 100%);
    border:1px solid rgba(246,206,99,.22); }
  #menuTam .mt-hero::before{ content:''; position:absolute; inset:0;
    background:radial-gradient(60% 80% at 50% -10%, rgba(246,206,99,.16), transparent 70%); }
  #menuTam .mt-hero-ic{ position:relative; }
  #menuTam .mt-hero-scr{ font-family:var(--script); font-size:26px; font-weight:700; color:var(--gold); line-height:1; margin-bottom:2px;
    text-shadow:0 3px 14px rgba(233,196,106,.35); }
  #menuTam .mt-hero-ad{ font-family:var(--serif); font-size:36px; font-weight:900; color:#fff;
    letter-spacing:.5px; text-shadow:0 4px 18px rgba(0,0,0,.55); line-height:1.05; }
  #menuTam .mt-hero-alt{ display:inline-block; margin-top:14px; padding-top:12px; font-size:11px; font-weight:800; letter-spacing:4px; color:var(--gold);
    border-top:1px solid rgba(233,196,106,.45); }
  /* SEVIYE 1: kategori kutulari - sabit guzel oran (16:10), responsive auto-fill */
  #menuTam .mt-katgrid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:16px; padding:2px 0; }
  #menuTam .mt-kattile{ position:relative; border-radius:20px; overflow:hidden; aspect-ratio:16/10; cursor:pointer;
    border:1px solid rgba(255,255,255,.09); box-shadow:0 14px 30px -16px rgba(0,0,0,.8);
    opacity:0; transform:translateY(16px); animation:mtUp .5s cubic-bezier(.2,.7,.2,1) forwards; transition:transform .18s; }
  @supports not (aspect-ratio:1/1){ #menuTam .mt-kattile{ height:170px; } }
  #menuTam .mt-kattile:hover{ transform:translateY(-3px); }
  #menuTam .mt-kattile:active{ transform:scale(.97); }
  #menuTam .mt-kt-gor{ position:absolute; inset:0; }
  #menuTam .mt-kt-gor img{ width:100%; height:100%; object-fit:cover; transition:transform .5s; }
  #menuTam .mt-kattile:hover .mt-kt-gor img{ transform:scale(1.07); }
  #menuTam .mt-kt-em{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
    font-size:64px; filter:drop-shadow(0 6px 14px rgba(0,0,0,.45)); opacity:.96; }
  #menuTam .mt-kt-grad::after{ content:''; position:absolute; inset:0;
    background:radial-gradient(80% 60% at 50% 30%, rgba(255,255,255,.14), transparent 70%); pointer-events:none; }
  #menuTam .mt-kt-shade{ position:absolute; inset:0; background:linear-gradient(180deg,rgba(0,0,0,0) 40%,rgba(0,0,0,.72) 100%); }
  #menuTam .mt-kt-ad{ position:absolute; left:14px; right:14px; bottom:12px; display:flex; align-items:center; gap:8px;
    font-size:16.5px; font-weight:800; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,.65); }
  #menuTam .mt-kt-ad span{ font-size:20px; }
  #menuTam .mt-kt-ad em{ margin-left:auto; font-style:normal; font-size:10.5px; font-weight:700; color:#F6CE63; opacity:.95;
    background:rgba(0,0,0,.35); padding:3px 9px; border-radius:20px; white-space:nowrap; }
  /* SEVIYE 2: geri butonu */
  #menuTam .mt-geri{ display:inline-flex; align-items:center; gap:6px; margin:6px 0 2px; background:rgba(255,255,255,.1);
    color:#E8ECF7; border:1px solid rgba(255,255,255,.12); font-size:13.5px; font-weight:800; padding:9px 16px; border-radius:22px; cursor:pointer; }
  #menuTam .mt-geri:active{ transform:scale(.96); }
  @keyframes mtUp{ to{ opacity:1; transform:translateY(0); } }
  /* zarif kategori basligi + altin cizgi */
  #menuTam .mt-kat{ display:flex; align-items:center; gap:10px; font-size:20px; font-weight:800; color:#fff; margin:26px 2px 14px; letter-spacing:.3px; }
  #menuTam .mt-kat span{ font-size:26px; filter:drop-shadow(0 3px 8px rgba(0,0,0,.4)); }
  #menuTam .mt-kat::after{ content:''; flex:1; height:2px; margin-left:6px; border-radius:2px;
    background:linear-gradient(90deg,rgba(246,206,99,.7),rgba(246,206,99,0)); }
  #menuTam .mt-grid{ display:grid; grid-template-columns:1fr; gap:16px; }
  @media(min-width:640px){ #menuTam .mt-grid{ grid-template-columns:1fr 1fr; } }
  @media(min-width:1000px){ #menuTam .mt-grid{ grid-template-columns:1fr 1fr 1fr; } }
  /* immersive foto hero kart */
  #menuTam .mt-urun{ border-radius:22px; overflow:hidden; background:#1e1024; border:1px solid rgba(255,255,255,.07);
    box-shadow:0 16px 38px rgba(0,0,0,.5); opacity:0; transform:translateY(16px) scale(.98);
    animation:kartGir .55s cubic-bezier(.2,.75,.2,1) forwards; }
  #menuTam .mt-gor{ position:relative; height:210px; background:#1e1024; }
  #menuTam .mt-gor img{ width:100%; height:100%; object-fit:cover; transform:scale(1.03); transition:transform .7s ease; }
  #menuTam .mt-urun:active .mt-gor img{ transform:scale(1.09); }
  #menuTam .mt-tile{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
  #menuTam .mt-tile span{ font-size:86px; filter:drop-shadow(0 6px 14px rgba(0,0,0,.45)); }
  #menuTam .mt-shade{ position:absolute; inset:0; background:linear-gradient(180deg,rgba(6,10,20,.05) 30%,rgba(6,10,20,.55) 62%,rgba(6,10,20,.96) 100%); }
  #menuTam .mt-rz{ position:absolute; top:12px; left:12px; z-index:2; background:linear-gradient(135deg,#F6CE63,#E0A431); color:#3a2600;
    font-size:11px; font-weight:800; letter-spacing:.3px; padding:5px 12px; border-radius:30px; box-shadow:0 5px 14px rgba(224,164,49,.5); }
  #menuTam .mt-ov{ position:absolute; left:0; right:0; bottom:0; z-index:2; padding:15px 16px; display:flex; align-items:flex-end; justify-content:space-between; gap:10px; }
  #menuTam .mt-ad{ font-weight:800; font-size:20px; color:#fff; line-height:1.15; letter-spacing:.2px; text-shadow:0 2px 10px rgba(0,0,0,.7); flex:1; }
  #menuTam .mt-fi{ background:rgba(255,255,255,.14); backdrop-filter:blur(8px); color:#FDE9B5; font-weight:800; font-size:15px;
    padding:5px 13px; border-radius:22px; border:1px solid rgba(246,206,99,.45); white-space:nowrap; }
  #menuTam .mt-ac{ color:#D8C8D6; font-size:12.8px; line-height:1.45; padding:12px 16px 4px;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  #menuTam .mt-bak{ color:var(--gold2); font-size:11.5px; font-weight:700; padding:6px 16px 14px; }

  /* =================== MOCKUP HOME (Lezzet Duragi) =================== */
  header .tr{ background:rgba(255,255,255,.06); border:1px solid var(--cizgi); color:#E7D3B4; font-size:12px; font-weight:700; padding:5px 11px; border-radius:12px; }
  #home{ flex:1; overflow-y:auto; -webkit-overflow-scrolling:touch; padding:4px 16px calc(96px + env(safe-area-inset-bottom)); }
  #home::-webkit-scrollbar{ width:0; }
  /* karsilama + arama */
  .hg{ background:linear-gradient(150deg,rgba(139,59,234,.18),rgba(36,19,41,.6)); border:1px solid var(--cizgi);
    border-radius:22px; padding:20px 18px; box-shadow:0 16px 36px -20px rgba(0,0,0,.8); }
  .hg-h{ font-family:var(--serif); font-weight:800; font-size:22px; color:#fff; }
  .hg-s{ color:var(--sessiz); font-size:13.5px; margin-top:5px; }
  .ara{ display:flex; gap:9px; margin-top:15px; }
  .ara input{ flex:1; background:rgba(0,0,0,.28); border:1px solid var(--cizgi); color:#fff; font-size:14.5px;
    padding:14px 16px; border-radius:16px; outline:none; }
  .ara input::placeholder{ color:#9a8a9c; }
  .ara button{ width:52px; border:none; border-radius:16px; font-size:19px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 18px rgba(139,59,234,.5); }
  /* kategori cipleri (ikonlu) */
  .katcips{ display:flex; gap:10px; overflow-x:auto; padding:18px 2px 6px; -webkit-overflow-scrolling:touch; }
  .katcips::-webkit-scrollbar{ height:0; }
  .kc{ flex:0 0 auto; display:flex; flex-direction:column; align-items:center; gap:6px; min-width:74px; padding:12px 12px 10px;
    border-radius:18px; background:var(--card); border:1px solid var(--cizgi); cursor:pointer; transition:.18s; }
  .kc .kci{ width:38px; height:38px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px;
    background:rgba(139,59,234,.16); }
  .kc .kcn{ font-size:11.5px; font-weight:700; color:#E7D3B4; white-space:nowrap; }
  .kc.act{ background:linear-gradient(135deg,var(--mor),var(--mavi)); border-color:transparent; box-shadow:0 8px 20px rgba(139,59,234,.5); }
  .kc.act .kci{ background:rgba(255,255,255,.18); }
  .kc.act .kcn{ color:#fff; }
  /* bolum basligi */
  .bolum-bas{ display:flex; align-items:baseline; justify-content:space-between; margin:20px 2px 4px; }
  .bolum-bas b{ font-family:var(--serif); font-size:19px; color:#fff; }
  .bolum-bas a{ color:var(--gold); font-size:12.5px; font-weight:700; cursor:pointer; }
  /* populer yatay kartlar */
  .popsira{ display:flex; gap:14px; overflow-x:auto; padding:10px 2px 6px; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch; }
  .popsira::-webkit-scrollbar{ height:0; }
  .pop-yuk{ color:var(--sessiz); font-size:13px; padding:20px 4px; }
  .pk{ flex:0 0 168px; scroll-snap-align:start; background:var(--card); border:1px solid var(--cizgi); border-radius:20px;
    overflow:hidden; box-shadow:0 14px 30px -16px rgba(0,0,0,.75); cursor:pointer;
    opacity:0; transform:translateY(14px); animation:kartGir .5s cubic-bezier(.2,.75,.2,1) forwards; }
  .pk .pk-g{ position:relative; height:118px; background:#1e1024; }
  .pk .pk-g img{ width:100%; height:100%; object-fit:cover; }
  .pk .pk-em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:46px; }
  .pk .pk-tag{ position:absolute; top:9px; left:9px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600;
    font-size:10px; font-weight:800; padding:3px 9px; border-radius:20px; }
  .pk .pk-b{ padding:11px 12px 13px; }
  .pk .pk-ad{ font-weight:800; font-size:14px; color:#fff; line-height:1.2; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .pk .pk-alt{ display:flex; align-items:center; justify-content:space-between; margin-top:9px; }
  .pk .pk-fi{ color:var(--gold); font-weight:800; font-size:14px; }
  .pk .pk-art{ width:30px; height:30px; border-radius:10px; border:none; color:#fff; font-size:17px; line-height:1;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 6px 14px rgba(139,59,234,.5); }
  /* bugune ozel geri sayim */
  .ozel{ position:relative; margin-top:22px; border-radius:22px; overflow:hidden; padding:20px 18px;
    background:linear-gradient(135deg,#4a1d6b,#2a1140); border:1px solid rgba(233,196,106,.28);
    box-shadow:0 18px 40px -20px rgba(0,0,0,.85); display:flex; align-items:center; }
  .ozel::before{ content:''; position:absolute; inset:0; background:radial-gradient(60% 90% at 90% 10%, rgba(233,196,106,.18), transparent 60%); }
  .ozel-ic{ position:relative; flex:1; }
  .ozel-b{ font-family:var(--serif); font-size:18px; font-weight:800; color:#fff; }
  .ozel-s{ color:#E9D3EE; font-size:12.5px; margin-top:3px; }
  .sayac{ display:flex; gap:8px; margin-top:13px; }
  .sayac > div{ background:rgba(0,0,0,.32); border:1px solid var(--cizgi); border-radius:12px; padding:7px 0; width:52px; text-align:center; }
  .sayac b{ display:block; font-size:19px; font-weight:800; color:var(--gold); font-variant-numeric:tabular-nums; }
  .sayac i{ font-style:normal; font-size:8.5px; font-weight:700; letter-spacing:1px; color:var(--sessiz); }
  .ozel-rozet{ position:relative; width:58px; height:58px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; font-weight:800; font-size:13px; flex:0 0 auto; margin-left:12px;
    box-shadow:0 8px 20px rgba(201,150,47,.5); }
  .ozel-rozet span{ font-size:19px; }
  /* alt navigasyon bar */
  #altbar{ position:fixed; left:0; right:0; bottom:0; z-index:60; display:flex; align-items:flex-end; justify-content:space-around;
    padding:8px 8px calc(8px + env(safe-area-inset-bottom)); background:rgba(20,10,22,.92); backdrop-filter:blur(14px);
    border-top:1px solid var(--cizgi); }
  #altbar button{ flex:1; background:none; border:none; color:var(--sessiz); font-size:10.5px; font-weight:700;
    display:flex; flex-direction:column; align-items:center; gap:3px; padding:5px 0; }
  #altbar button span{ font-size:19px; }
  #altbar button.act{ color:var(--gold); }
  #altbar .qr{ flex:0 0 auto; }
  #altbar .qr .qri{ width:58px; height:58px; margin-top:-24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 22px rgba(139,59,234,.6), 0 0 0 5px rgba(20,10,22,.92); }
  /* asistan kayan panel */
  #asheet{ position:fixed; left:0; right:0; bottom:0; z-index:75; height:82dvh; display:flex; flex-direction:column;
    background:linear-gradient(180deg,#241329,#160a1a); border-top-left-radius:26px; border-top-right-radius:26px;
    border:1px solid var(--cizgi); box-shadow:0 -20px 50px rgba(0,0,0,.6); transform:translateY(102%); transition:transform .34s cubic-bezier(.2,.8,.2,1); }
  #asheet.acik{ transform:translateY(0); }
  #asheet .as-tut{ width:44px; height:5px; border-radius:3px; background:rgba(255,255,255,.22); margin:9px auto 2px; }
  #asheet .as-bar{ display:flex; align-items:center; gap:12px; padding:6px 16px 8px; }
  #asheet #orb{ width:46px; height:46px; flex:0 0 auto; }
  #asheet #orb::after{ inset:10px; }
  #asheet .as-t{ display:flex; flex-direction:column; }
  #asheet .as-t b{ font-family:var(--serif); font-size:16px; color:var(--gold); }
  #asheet #durum{ text-align:left; margin:0; font-size:12px; }
  #asheet .as-x{ margin-left:auto; width:38px; height:38px; border-radius:50%; border:none; background:rgba(255,255,255,.1); color:#fff; font-size:16px; }
</style>
</head>
<body>
<div id="app">
  <header>
    <span class="logo"><span class="toque">👨‍🍳</span><span class="lz"><b>{{ $sube->ad ?? 'ResteOS' }}</b><i>RESTORAN</i></span></span>
    <span class="masa" style="margin-left:auto">🍽️ {{ $masa->ad ?? '' }}</span>
  </header>

  <!-- ===== MOCKUP HOME ===== -->
  <main id="home">
    <div class="hg">
      <div class="hg-h">Hoş geldiniz! 👋</div>
      <div class="hg-s">Lezzet dolu bir deneyime hazır mısınız?</div>
      <form class="ara" onsubmit="aramaGonder(event)">
        <input id="ara" placeholder="Ne yemek istersiniz?" autocomplete="off">
        <button type="submit" aria-label="Ara">🔍</button>
      </form>
    </div>

    <div class="katcips" id="katcips"></div>

    <div class="bolum-bas"><b>Popüler Lezzetler</b><a onclick="menuyuIncele()">Tümünü Gör →</a></div>
    <div class="popsira" id="popsira"><div class="pop-yuk">Lezzetler yükleniyor…</div></div>

    <div class="ozel">
      <div class="ozel-ic">
        <div class="ozel-b">Bugüne Özel</div>
        <div class="ozel-s">Seçili menülerde %20 indirim!</div>
        <div class="sayac" id="sayac">
          <div><b id="s-sa">00</b><i>SAAT</i></div>
          <div><b id="s-dk">00</b><i>DAKİKA</i></div>
          <div><b id="s-sn">00</b><i>SANİYE</i></div>
        </div>
      </div>
      <div class="ozel-rozet">%<span>20</span></div>
    </div>
  </main>

  <!-- ===== ASISTAN KAYAN PANEL ===== -->
  <div id="asheet">
    <div class="as-tut"></div>
    <div class="as-bar">
      <div id="orb" onclick="basla()"></div>
      <div class="as-t"><b>Sesli Asistan</b><span id="durum">Dokunup konuşun ya da yazın</span></div>
      <button class="as-x" onclick="sheetKapat()" aria-label="Kapat">✕</button>
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

  <!-- ===== ALT NAVIGASYON ===== -->
  <nav id="altbar">
    <button class="act" onclick="menuyuIncele()"><span>📋</span>Menü</button>
    <button onclick="siparislerimAc()"><span>🧾</span>Siparişlerim</button>
    <button class="qr" onclick="asistanAc()"><div class="qri">🎤</div></button>
    <button onclick="cagir('garson')"><span>🔔</span>Çağır</button>
    <button onclick="cagir('hesap')"><span>💳</span>Hesabım</button>
  </nav>
</div>

<div id="lightbox">
  <div class="lb-bar"><span id="lb-ad"></span><button id="lb-kapat">✕</button></div>
  <div class="lb-imgs" id="lb-imgs"></div>
  <div class="lb-ipucu">← kaydırarak diğer fotoğraflara bakın →</div>
  <div class="lb-alt"><span id="lb-fi"></span><span id="lb-ac"></span><button id="lb-iste">🙋 İstiyorum</button></div>
</div>

<!-- SALT OKUNUR tam ekran menu (AI kapali) -->
<div id="menuTam">
  <div class="mt-bar">
    <span class="mt-title">📋 {{ $sube->ad ?? 'Menü' }}</span>
    <button class="mt-kapat" onclick="kapatMenu()">✕ Kapat</button>
  </div>
  <div class="mt-body"></div>
</div>

<script>
const MASA = @json($masa->id);
const SUBE_AD = @json($sube->ad ?? 'Menü');
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
    c.querySelector('.mbtn').addEventListener('click', e=>{ e.stopPropagation(); istiyorum(k); });
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
  // Sadece isletmenin YUKLEDIGI gercek fotograf; yoksa sik gradient+emoji (loremflickr saçma foto YOK)
  const liste = k.gercek_foto ? (k.gorseller && k.gorseller.length ? k.gorseller : [k.gorsel]).filter(Boolean) : [];
  if(!liste.length){ const d=document.createElement('div'); d.className='tile'; d.style.cssText='flex:0 0 100%;background:'+gradientFor(k.ad); d.innerHTML='<span style="font-size:120px">'+(k.emoji||'🍽️')+'</span>'; wrap.appendChild(d); }
  liste.forEach(src=>{
    const im = document.createElement('img'); im.src = src; im.alt = k.ad; im.loading='lazy';
    im.addEventListener('error', function(){ this.style.background = gradientFor(k.ad); this.removeAttribute('src'); });
    wrap.appendChild(im);
  });
  const menuAcik = document.getElementById('menuTam').style.display === 'flex';   // salt okunur menu -> siparis butonu yok
  const iste = document.getElementById('lb-iste');
  iste.style.display = menuAcik ? 'none' : '';
  iste.onclick = ()=>{ kapatGaleri(); istiyorum(k); };
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

// "Istiyorum" -> urunu sepete ekle. Sessiz (inceleme) modda konusmadan direkt sepete.
function istiyorum(k){
  const ad = (k && typeof k==='object') ? k.ad : k;
  if(sessizMod && k && typeof k==='object' && k.urun_id){
    sepetMerge([{urun_id:k.urun_id, ad:k.ad, adet:1, fiyat:k.fiyat}]); siparisModu=true; sepetGuncelle();
    ekle('ai', k.ad+' sepetinize eklendi. 😊 Eklemeye devam edebilir ya da sepetten "Onayla ve Gönder"e dokunabilirsiniz.');
    return;
  }
  sor(ad + ' istiyorum');
}

/* ---- MENUYU İNCELE: SALT OKUNUR premium QR menu (2 seviyeli: hero + kategori tile -> urunler) ---- */
let _menuData = [];
async function menuyuIncele(){
  sessizMod = true; sohbetAktif = false;
  konusKes(); sesDurdur(); konusuyor = false; try{ rec && rec.stop(); }catch(_){}
  micBtn.classList.remove('acik');
  const ov = document.getElementById('menuTam');
  const body = ov.querySelector('.mt-body');
  body.innerHTML = '<div class="mt-yukle">Menü yükleniyor…</div>';
  ov.style.display = 'flex';
  try{
    const r = await fetch('/api/qr/menu-tam?masa='+MASA);
    const j = await r.json();
    _menuData = (j.ok && Array.isArray(j.kategoriler)) ? j.kategoriler : [];
    if(_menuData.length) menuIzgaraGoster();
    else body.innerHTML = '<div class="mt-yukle">Menü şu an görüntülenemiyor.</div>';
  }catch(e){ body.innerHTML = '<div class="mt-yukle">Menü yüklenemedi.</div>'; }
}
// SEVIYE 1: hero + kategori kutulari (gercek foto varsa foto, yoksa sik gradient+emoji)
function menuIzgaraGoster(){
  const body = document.getElementById('menuTam').querySelector('.mt-body'); body.innerHTML='';
  const col = document.createElement('div'); col.className='mt-col'; body.appendChild(col);
  const hero = document.createElement('div'); hero.className='mt-hero';
  hero.innerHTML = `<div class="mt-hero-ic"><div class="mt-hero-scr">Afiyet olsun</div><div class="mt-hero-ad">${esc(SUBE_AD)}</div><div class="mt-hero-alt">— DİJİTAL MENÜ —</div></div>`;
  col.appendChild(hero);
  const grid = document.createElement('div'); grid.className='mt-katgrid';
  _menuData.forEach((k,i)=>{
    const t = document.createElement('div'); t.className='mt-kattile'; t.style.animationDelay=(i*45)+'ms';
    const ilk = (k.kartlar && k.kartlar[0]) ? k.kartlar[0] : null;
    let gorsel;
    if(ilk && ilk.gercek_foto && ilk.gorsel){                    // sadece isletmenin YUKLEDIGI gercek foto
      gorsel = `<img src="${esc(ilk.gorsel)}" loading="lazy" onerror="this.parentNode.innerHTML='<div class=\\'mt-kt-em\\'>${k.emoji||'🍽️'}</div>'">`;
    } else {                                                     // stok/loremflickr YOK -> sik gradient + buyuk emoji
      t.classList.add('mt-kt-grad'); t.style.background = gradientFor(k.ad);
      gorsel = `<div class="mt-kt-em">${k.emoji||'🍽️'}</div>`;
    }
    t.innerHTML = `<div class="mt-kt-gor">${gorsel}<div class="mt-kt-shade"></div></div>`
      + `<div class="mt-kt-ad"><span>${k.emoji||'🍽️'}</span>${esc(k.ad)}<em>${(k.kartlar||[]).length} çeşit</em></div>`;
    t.addEventListener('click', ()=> kategoriAc(i));
    grid.appendChild(t);
  });
  col.appendChild(grid); body.scrollTop=0;
}
// SEVIYE 2: bir kategorinin urunleri (immersive kartlar)
function kategoriAc(i){
  const k = _menuData[i]; if(!k) return;
  const body = document.getElementById('menuTam').querySelector('.mt-body'); body.innerHTML='';
  const col = document.createElement('div'); col.className='mt-col'; body.appendChild(col);
  const geri = document.createElement('button'); geri.className='mt-geri'; geri.innerHTML='← Kategoriler';
  geri.addEventListener('click', menuIzgaraGoster); col.appendChild(geri);
  const kat = document.createElement('div'); kat.className='mt-kat'; kat.innerHTML=`<span>${k.emoji||'🍽️'}</span>${esc(k.ad)}`; col.appendChild(kat);
  const grid = document.createElement('div'); grid.className='mt-grid';
  (k.kartlar||[]).forEach((u, idx)=>{
    const c = document.createElement('div'); c.className='mt-urun'; c.style.animationDelay=(idx*55)+'ms';
    const tile = `<div class="mt-tile" style="background:${gradientFor(u.ad)}"><span>${u.emoji||'🍽️'}</span></div>`;
    const gorsel = (u.gercek_foto && u.gorsel)   // sadece gercek yuklenen foto; degilse sik gradient tile (loremflickr YOK)
      ? `<img src="${esc(u.gorsel)}" alt="${esc(u.ad)}" loading="lazy" onerror="this.onerror=null;this.parentNode.innerHTML=${JSON.stringify(tile)}">`
      : tile;
    const et = u.etiket ? `<span class="mt-rz">★ ${esc(u.etiket)}</span>` : '';
    const ac = u.aciklama ? `<div class="mt-ac">${esc(u.aciklama)}</div>` : '';
    const bak = (u.gercek_foto && u.gorsel) ? '📷 Fotoğraflara bak' : 'ℹ️ Detaya bak';
    c.innerHTML = `<div class="mt-gor">${gorsel}<div class="mt-shade"></div>${et}`
      + `<div class="mt-ov"><div class="mt-ad">${esc(u.ad)}</div><div class="mt-fi">${esc(u.fiyat_yazi||'')}</div></div></div>`
      + ac + `<div class="mt-bak">${bak}</div>`;
    c.addEventListener('click', ()=> acGaleri(u));
    grid.appendChild(c);
  });
  col.appendChild(grid); body.scrollTop=0;
}
function kapatMenu(){ document.getElementById('menuTam').style.display = 'none'; }

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
// "hayir / bu kadar / yeterli" -> siparisi bitir (SADECE net "bitirme" ifadesi; "hayir yanlis anladim" gibi DUZELTME -> bitirmez)
function bitirMi(t){
  const n = (t||'').toLocaleLowerCase('tr').replace(/[^a-zçğıöşü ]/g,' ').replace(/\s+/g,' ').trim();
  if(/(yanlis|yanlış|anlamad|değil|degil|öyle değil|yanılıyor|yaniliyor|demek istemedim|kastetmedim)/.test(' '+n+' ')) return false; // duzeltme
  return /^(hayir|hayır|yok|bu kadar|bukadar|yeterli|tamamdir|tamamdır|bitir|bitti|bu kadar yeter|baska yok|başka yok|olmaz|tamam|yok tesekkur|yok teşekkür|hayir tesekkur|hayır teşekkür)$/.test(n);
}
async function finalizeSiparis(){
  if(!sepet.length) return;
  if(sepetEl){ const b=sepetEl.querySelector('.onayla'); if(b){ b.disabled=true; b.textContent='Gönderiliyor…'; } }
  try{
    const r = await fetch('/api/qr/siparis-gonder', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({masa:MASA, kalemler: JSON.stringify(sepet.map(k=>({urun_id:k.urun_id, adet:k.adet})))})});
    const j = await r.json();
    if(j.ok){
      sepet=[]; siparisModu=false; sepetGuncelle();
      const kapanislar = [
        'Harika, teşekkür ederim! 🎉 Siparişinizi mutfağımıza ilettim, hemen hazırlanmaya başlıyor. Sıcacık masanıza gelecek, afiyet olsun!',
        'Süper, aldım! 🎉 Mutfağımız hemen işe koyuldu; birazdan tazecik önünüzde olur. Afiyet olsun!',
        'Teşekkür ederim! 🎉 Siparişiniz mutfağa geçti, taptaze hazırlanıp masanıza gelecek. Afiyet olsun!',
        'Harika seçim! 🎉 Mutfağımıza ilettim, özenle hazırlanıyor; az sonra sofranızda. Afiyet olsun!',
      ];
      const m = kapanislar[Math.floor((window.performance?performance.now():Date.now())) % kapanislar.length];
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
let bargeAktif = false;      // asistan konusurken musteri konusmaya basladi mi (VAD)
let _bekleyenCoz = null;     // siradaki kullanici sozunu bekleyen cozucu (dinle ya da barge)
let _islenmis = 0;          // continuous tanimada islenen final sonuc sayisi
let kufurSay = 0, kapatIstegi = false;   // kufur sayaci + 2. kufurde kapat
let sessizMod = false;                   // musteri kendi basina menuyu inceliyor (asistan susar)
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
let _konusBit = null;                 // aktif konusmayi disaridan kesmek icin (dokunmayla)
function konusKes(){ if(_konusBit){ const b=_konusBit; _konusBit=null; b(null); } }
// KONUS: konusur. bargeIn=true -> VAD ile musteri konusunca SUSAR; tanima SICAK/continuous oldugu icin ILK kelime dahil yakalanir.
// Araya girilen metni doner (yoksa null).
function konus(t, bargeIn){
  return new Promise((resolve)=>{
    if(sessizMod){ resolve(null); return; }   // menuyu inceleme modunda asistan susar
    const temiz = seseHazirla(t);
    if(!temiz){ resolve(null); return; }
    konusuyor = true; bargeAktif = false;
    let bitti=false, vad=null, bargeWatch=null;
    const emniyet = setTimeout(()=>bit(null), Math.min(22000, 3000 + temiz.length*95));
    function bit(userText){
      if(bitti) return; bitti=true;
      clearTimeout(emniyet); clearTimeout(bargeWatch); if(vad) clearInterval(vad);
      _konusBit=null; _bekleyenCoz=null; konusuyor=false; bargeAktif=false; sesDurdur();
      resolve(userText || null);
    }
    _konusBit = ()=>bit(null);
    // Araya girildiyse (bargeAktif) continuous tanimadan gelen ILK kullanici final'i -> onu don
    _bekleyenCoz = (m)=>{ if(bargeAktif) bit(m); };
    // VAD: musteri konusmaya baslayinca TTS'i KES + capture'a gec (tanima zaten sicak)
    if(bargeIn && _analiz){
      let yuksek=0, taban=0;
      const kalib = setInterval(()=>{ if(bitti){ clearInterval(kalib); return; } taban = Math.max(taban, sesSeviyesi()); }, 50);
      setTimeout(()=>{
        clearInterval(kalib);
        if(bitti) return;
        const esik = Math.max(_vadEsik, taban * 1.5 + 0.02);
        vad = setInterval(()=>{
          if(bitti || bargeAktif) return;
          if(sesSeviyesi() > esik){ if(++yuksek >= 2){ sesDurdur(); bargeAktif=true; durumEl.textContent='Sizi dinliyorum…'; bargeWatch=setTimeout(()=>bit(null), 6000); } }
          else if(yuksek > 0) yuksek--;
        }, 40);
      }, 450);
    }
    (async ()=>{ let ok = await cloudKonus(temiz, ()=>bit(null)); if(!ok) ok = cihazKonus(temiz, ()=>bit(null)); if(!ok) bit(null); })();
  });
}

/* ---- Konusma tanima (tarayici STT) — SUREKLI/CONTINUOUS (ilk kelime kacmasin) ---- */
const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
let rec = null, sohbetAktif = false;
if(SR){
  rec = new SR(); rec.lang='tr-TR'; rec.interimResults=true; rec.continuous=true; rec.maxAlternatives=1;
  rec.onresult=(e)=>{
    let interim='';
    for(let i=_islenmis; i<e.results.length; i++){
      const res=e.results[i];
      if(res.isFinal){ _islenmis = i+1; kullaniciFinal((res[0].transcript||'').trim()); }
      else interim += res[0].transcript;
    }
    if(interim && _bekleyenCoz && (!konusuyor || bargeAktif)) durumEl.textContent = interim;
  };
  rec.onerror=()=>{};
  rec.onend=()=>{ if(sohbetAktif){ _islenmis=0; setTimeout(()=>{ try{ rec.start(); }catch(_){} }, 120); } };
}
// Continuous akistan gelen FINAL kullanici sozu: echo (asistan konusurken, VAD yok) yok sayilir; degilse bekleyene teslim.
function kullaniciFinal(m){
  if(!m) return;
  if(konusuyor && !bargeAktif) return;      // ECHO (asistan konusuyor, musteri henuz konusmadi) -> yoksay
  if(_bekleyenCoz){ const r=_bekleyenCoz; _bekleyenCoz=null; r(m); }
}
function recBaslat(){ if(!rec) return; try{ _islenmis=0; rec.start(); }catch(e){} }
// TEK cumle dinle: continuous akistan siradaki kullanici final'ini bekler.
function dinle(){
  return new Promise((resolve)=>{
    if(!rec){ resolve(''); return; }
    let done=false;
    orb.classList.add('dinliyor'); micBtn.classList.add('dinliyor'); durumEl.textContent='Sizi dinliyorum, buyurun…';
    const watch = setTimeout(()=>fin(''), 15000);
    function fin(m){ if(done) return; done=true; clearTimeout(watch); _bekleyenCoz=null; orb.classList.remove('dinliyor'); micBtn.classList.remove('dinliyor'); resolve((m||'').trim()); }
    _bekleyenCoz = (m)=>fin(m);
  });
}
function iptalMi(t){ const n=' '+(t||'').toLocaleLowerCase('tr')+' '; return /( )(kapat|kapatabilir|görüşürüz|gorusuruz|hoşça kal|hosca kal|sohbeti kapat)( )/.test(n); }
// Mikrofon izni + VAD (araya girme icin ses enerjisi olcer). Echo-cancelled -> asistanin kendi sesi elenir.
let _izinVerildi = false, _audioCtx = null, _analiz = null, _vadBuf = null;
const _vadEsik = 0.055;
async function micIzniIste(){
  if(_analiz){ try{ _audioCtx && _audioCtx.state==='suspended' && _audioCtx.resume(); }catch(_){} return; }
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
  sessizMod=false;       // asistan geri geldi (menu inceleme modundan cik)
  sohbetAktif=true; micBtn.classList.add('acik');
  await micIzniIste();   // izin + VAD kurulumu (ilk sefer dialog cikar; izin gelince devam)
  recBaslat();           // SUREKLI tanima BASLASIN (hep acik -> araya girince ilk kelime yakalanir)
  let girdi = null;      // araya girmede continuous akistan yakalanan metin -> dinle atlanir
  if(selamla){ const bi = await konus(ilkSelamVerildi ? 'Buyurun, sizi dinliyorum.' : SELAM, true); ilkSelamVerildi = true; if(bi) girdi = bi; }
  let bos=0;
  while(sohbetAktif){
    const c = girdi || await dinle(); girdi = null;
    if(!sohbetAktif) break;
    if(!c){ if(++bos>=2){ await konus('Başka bir arzunuz yoksa dinlemeyi kapatıyorum. İstediğinizde mikrofona tekrar dokunun.'); break; } await konus('Sizi tam anlayamadım, tekrar eder misiniz?'); continue; }
    bos=0;
    ekle('ben', c);
    if(siparisModu && sepet.length && bitirMi(c)){ await finalizeSiparis(); continue; }
    if(iptalMi(c)){ await konus('Tabii, kapatıyorum. Afiyet olsun!'); break; }
    const cevap = await sunucudanCevap(c);
    if(cevap){ const bi = await konus(cevap, true); if(bi){ girdi = bi; } }   // araya girilirse yakalanan metni isle
    if(kapatIstegi){ kapatIstegi=false; break; }   // 2. kufur -> gorusmeyi kapat
  }
  sohbetAktif=false; micBtn.classList.remove('acik');
  try{ rec.stop(); }catch(_){}   // sohbet bitti -> continuous tanimayi durdur
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
    // KUFUR: 1. kez uyar; 2. kez kapat
    if(j.aksiyon==='kufur'){
      kufurSay++;
      if(kufurSay>=2){ kapatIstegi=true; const m='Maalesef bu şekilde devam edemeyeceğim, görüşmeyi kapatıyorum. İyi günler dilerim.'; ekle('ai', m); return m; }
      const uyari = j.cevap || 'Efendim, sizi saygıya davet ediyorum.'; ekle('ai', uyari); return uyari;
    }
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
  sheetAc();
  ekle('ben', soru);
  if(siparisModu && sepet.length && bitirMi(soru)){ await finalizeSiparis(); return; }
  const cevap = await sunucudanCevap(soru);
  if(cevap) konus(cevap);
  if(!sohbetAktif && !konusuyor) durumEl.textContent='Dokunup konuşun ya da yazın';
}
async function garsonCagir(tip){
  try{ await fetch('/api/qr/garson-cagir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA,tip})}); }catch(e){}
}

/* ==================== MOCKUP HOME + ASISTAN PANEL ==================== */
const asheetEl = document.getElementById('asheet');
let _homeData = [], _popList = [];
function sheetAc(){ asheetEl.classList.add('acik'); }
function sheetKapat(){
  asheetEl.classList.remove('acik');
  sohbetAktif=false; try{ rec && rec.stop(); }catch(_){} sesDurdur(); konusuyor=false;
  micBtn.classList.remove('acik','dinliyor'); orb.classList.remove('dinliyor');
}
// Alt bar QR/mic -> asistani ac + sesli baslat
function asistanAc(){ sheetAc(); if(!sohbetAktif) basla(true); }
// Arama cubugu -> asistana yaz
function aramaGonder(e){ e.preventDefault(); const el=document.getElementById('ara'); const t=(el.value||'').trim(); if(!t) return; el.value=''; sheetAc(); sor(t); }
// Siparislerim -> paneli ac (sepet zaten sohbette gorunur)
function siparislerimAc(){ sheetAc(); if(!sepet.length){ const m='Henüz sepetinizde bir şey yok. 🙂 Ne istersiniz? Bana söylemeniz yeterli.'; ekle('ai', m); } sohbet.scrollTop=sohbet.scrollHeight; }
// Garson / hesap cagir + geri bildirim
async function cagir(tip){ await garsonCagir(tip); toast(tip==='hesap' ? '💳 Hesap isteğiniz iletildi, birazdan geliyoruz.' : '🔔 Garson çağrıldı, birazdan yanınızdayız.'); }
function toast(msg){
  let t=document.getElementById('_toast');
  if(!t){ t=document.createElement('div'); t.id='_toast'; t.style.cssText='position:fixed;left:50%;transform:translateX(-50%);bottom:96px;z-index:90;background:linear-gradient(135deg,#2a1731,#160a1a);color:#fff;border:1px solid var(--cizgi);padding:13px 18px;border-radius:16px;font-size:13.5px;font-weight:600;box-shadow:0 14px 34px rgba(0,0,0,.6);max-width:88%;text-align:center;opacity:0;transition:.25s;'; document.body.appendChild(t); }
  t.textContent=msg; requestAnimationFrame(()=>{ t.style.opacity='1'; }); clearTimeout(t._z); t._z=setTimeout(()=>{ t.style.opacity='0'; }, 3200);
}

// HOME verisini yukle: kategori cipleri + populer kartlar
async function homeYukle(){
  try{
    const r = await fetch('/api/qr/menu-tam?masa='+MASA); const j = await r.json();
    _homeData = (j.ok && Array.isArray(j.kategoriler)) ? j.kategoriler : [];
  }catch(e){ _homeData = []; }
  cipleriKur(); populerKur('*');
}
function cipleriKur(){
  const wrap=document.getElementById('katcips'); if(!wrap) return;
  let h = `<div class="kc act" data-k="*" onclick="cipSec(this,'*')"><div class="kci">🍽️</div><div class="kcn">Tüm Menüler</div></div>`;
  _homeData.forEach((k,i)=>{ h += `<div class="kc" data-k="${i}" onclick="cipSec(this,'${i}')"><div class="kci">${k.emoji||'🍽️'}</div><div class="kcn">${esc(k.ad)}</div></div>`; });
  wrap.innerHTML = h;
}
function cipSec(el, key){ document.querySelectorAll('#katcips .kc').forEach(c=>c.classList.remove('act')); el.classList.add('act'); populerKur(key); }
function populerKur(key){
  const wrap=document.getElementById('popsira'); if(!wrap) return;
  let urunler=[];
  if(key==='*'){ _homeData.forEach(k=> (k.kartlar||[]).slice(0,2).forEach(u=> urunler.push(u))); urunler=urunler.slice(0,10); }
  else { const k=_homeData[+key]; urunler=(k&&k.kartlar)?k.kartlar.slice(0,12):[]; }
  if(!urunler.length){ wrap.innerHTML='<div class="pop-yuk">Bu kategoride ürün yok.</div>'; return; }
  _popList = urunler; wrap.innerHTML='';
  urunler.forEach((u,i)=>{
    const c=document.createElement('div'); c.className='pk'; c.style.animationDelay=(i*45)+'ms';
    const gor = (u.gercek_foto && u.gorsel)
      ? `<img src="${esc(u.gorsel)}" loading="lazy" onerror="this.onerror=null;this.parentNode.innerHTML='<div class=\\'pk-em\\'>${u.emoji||'🍽️'}</div>'">`
      : `<div class="pk-em" style="background:${gradientFor(u.ad)}">${u.emoji||'🍽️'}</div>`;
    const tag = u.etiket ? `<span class="pk-tag">${esc(u.etiket)}</span>` : `<span class="pk-tag">Popüler</span>`;
    const artBtn = document.createElement('button'); artBtn.className='pk-art'; artBtn.textContent='+';
    artBtn.addEventListener('click', ev=>{ ev.stopPropagation(); hizliEkle(i); });
    c.innerHTML = `<div class="pk-g">${gor}${tag}</div>`
      + `<div class="pk-b"><div class="pk-ad">${esc(u.ad)}</div>`
      + `<div class="pk-alt"><span class="pk-fi">${esc(u.fiyat_yazi||'')}</span></div></div>`;
    c.querySelector('.pk-alt').appendChild(artBtn);
    c.addEventListener('click', ()=> acGaleri(u));
    wrap.appendChild(c);
  });
}
// Populer karttan '+' ile hizli sepete ekle (panel acilir)
function hizliEkle(i){ const u=_popList[i]; if(!u) return; sheetAc(); sessizMod=false; istiyorum(u); toast('🛒 '+u.ad+' sepete eklendi'); }

// Geri sayim (bugun sonuna kadar) — sadece gorsel
function sayacBasla(){
  const sa=document.getElementById('s-sa'), dk=document.getElementById('s-dk'), sn=document.getElementById('s-sn');
  if(!sa) return;
  function tik(){
    const now=new Date(); const bit=new Date(now.getFullYear(),now.getMonth(),now.getDate(),23,59,59);
    let f=Math.max(0,Math.floor((bit-now)/1000));
    const h=String(Math.floor(f/3600)).padStart(2,'0'); f%=3600;
    const m=String(Math.floor(f/60)).padStart(2,'0'); const s=String(f%60).padStart(2,'0');
    sa.textContent=h; dk.textContent=m; sn.textContent=s;
  }
  tik(); setInterval(tik,1000);
}

/* ---- Acilis: home hazirla; asistan panelde, sohbete karsilama koyulur (otomatik konusma YOK) ---- */
window.addEventListener('load', ()=>{
  ekle('ai', SELAM);          // panel acilinca gorunur
  homeYukle();
  sayacBasla();
});
</script>
</body>
</html>

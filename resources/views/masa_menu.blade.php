<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<title>{{ $sube->ad ?? 'Restoran' }} · {{ $masa->ad ?? '' }}</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Dancing+Script:wght@600;700&display=swap');
  :root{ --mor:#8B3BEA; --mor2:#A855F7; --mavi:#6D28D9; --card:#241329; --card2:#2c1734;
    --gold:#E9C46A; --gold2:#C9962F; --ink:#F3E9EE; --sessiz:#B49CB6; --cizgi:rgba(233,196,106,.18);
    --serif:'Playfair Display',Georgia,serif; --script:'Dancing Script',cursive; }
  *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent; margin:0; padding:0; }
  html,body{ height:100%; color:var(--ink); font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    background:#120912;
    background-image:
      radial-gradient(900px 620px at 90% -6%, rgba(233,150,60,.18), transparent 60%),
      radial-gradient(760px 560px at 4% 106%, rgba(139,59,234,.20), transparent 58%),
      radial-gradient(1200px 900px at 60% 0%, #331436 0%, #1c0d22 44%, #120912 100%); }
  img{ display:block; }
  ::-webkit-scrollbar{ width:0; height:0; }

  /* marka logo */
  .brand{ display:inline-flex; align-items:center; gap:9px; }
  .brand .toque{ font-size:24px; filter:drop-shadow(0 2px 6px rgba(233,196,106,.5)); }
  .brand .bt{ display:flex; flex-direction:column; line-height:1; }
  .brand .bt b{ font-family:var(--serif); font-weight:800; font-size:20px; letter-spacing:1px;
    background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
  .brand .bt i{ font-style:normal; font-size:8px; font-weight:700; letter-spacing:4px; color:var(--gold2); margin-top:3px; }
  .trbtn{ background:rgba(255,255,255,.06); border:1px solid var(--cizgi); color:#E7D3B4; font-size:12px; font-weight:700; padding:6px 11px; border-radius:12px; }

  /* yildiz */
  .yildiz{ display:inline-flex; align-items:center; gap:3px; font-size:12.5px; font-weight:800; color:var(--gold); }
  .yildiz.yeni{ color:#C9B7CB; font-weight:700; font-size:11px; background:rgba(255,255,255,.07); padding:2px 8px; border-radius:12px; }

  /* ==================== TELEFON (tek ekrana sigar, kaydirma yok) ==================== */
  #wrap{ height:100dvh; overflow:hidden; display:flex; flex-direction:column; padding:0 14px calc(80px + env(safe-area-inset-bottom)); }
  #wrap > *{ flex-shrink:0; }
  #wrap > header{ position:relative; display:flex; align-items:center; justify-content:center; padding:8px 0 8px; }
  #wrap > header .trbtn{ position:absolute; right:0; top:50%; transform:translateY(-50%); }
  #wrap > header .brand{ flex-direction:column; gap:2px; }
  #wrap > header .brand .bt{ align-items:center; } #wrap > header .brand .toque{ font-size:22px; }
  #wrap > header .brand .bt b{ font-size:18px; }
  .hg{ background:linear-gradient(150deg,rgba(139,59,234,.20),rgba(36,19,41,.65)); border:1px solid var(--cizgi);
    border-radius:18px; padding:13px 15px; box-shadow:0 12px 28px -18px rgba(0,0,0,.8); }
  .hg h1{ font-family:var(--serif); font-weight:800; font-size:18px; }
  .hg p{ color:var(--sessiz); font-size:12px; margin-top:3px; }
  .ara{ display:flex; gap:8px; margin-top:10px; }
  .ara input{ flex:1; background:rgba(0,0,0,.28); border:1px solid var(--cizgi); color:#fff; font-size:13.5px; padding:11px 14px; border-radius:13px; outline:none; }
  .ara input::placeholder{ color:#9a8a9c; }
  .ara button{ width:46px; border:none; border-radius:13px; font-size:17px; color:#fff; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 6px 14px rgba(139,59,234,.5); }

  .chips{ display:flex; gap:9px; overflow-x:auto; padding:11px 0 3px; }
  .chip{ flex:0 0 auto; display:flex; flex-direction:column; align-items:center; gap:5px; min-width:66px; padding:9px 9px 8px;
    border-radius:15px; background:var(--card); border:1px solid var(--cizgi); cursor:pointer; }
  .chip .ci{ width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; background:rgba(139,59,234,.16); }
  .chip .cn{ font-size:11px; font-weight:700; color:#E7D3B4; white-space:nowrap; }
  .chip.act{ background:linear-gradient(135deg,var(--mor),var(--mavi)); border-color:transparent; box-shadow:0 8px 20px rgba(139,59,234,.5); }
  .chip.act .ci{ background:rgba(255,255,255,.18); } .chip.act .cn{ color:#fff; }

  .bbas{ display:flex; align-items:baseline; justify-content:space-between; margin:12px 2px 2px; }
  .bbas b{ font-family:var(--serif); font-size:17px; }
  .bbas a{ color:var(--gold); font-size:12px; font-weight:700; cursor:pointer; }

  /* Populer kartlar KALAN dikey boslugu esnek doldurur -> her sey tek ekrana sigar */
  .pop{ flex:1 1 0; min-height:0; display:flex; gap:12px; overflow-x:auto; align-items:stretch; padding:8px 2px 4px; }
  /* Kart bastan sona FOTOGRAF; yazilar en altta; foto->yazi gecisi YUMUSAK + FLU (buzlu cam maske) */
  .pk{ position:relative; flex:0 0 158px; background:#1e1024; border:1px solid var(--cizgi); border-radius:18px; overflow:hidden;
    box-shadow:0 12px 26px -16px rgba(0,0,0,.75); cursor:pointer;
    opacity:0; transform:translateY(12px); animation:up .5s cubic-bezier(.2,.75,.2,1) forwards; }
  .pk .g{ position:absolute; inset:0; }
  .pk .g img{ width:100%; height:100%; object-fit:cover; }
  .pk .em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:54px; }
  .pk .tag{ position:absolute; z-index:3; top:9px; left:9px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; font-size:9.5px; font-weight:800; padding:3px 8px; border-radius:20px; }
  .pk .tuk{ position:absolute; z-index:3; inset:0; background:rgba(10,6,12,.55); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#fff; }
  .pk .glass{ position:absolute; z-index:1; inset:0;
    background:linear-gradient(180deg, rgba(88,42,140,0) 0%, rgba(80,38,128,.28) 48%, rgba(64,30,104,.72) 80%, rgba(52,24,86,.92) 100%); }
  .pk .b{ position:absolute; z-index:2; left:0; right:0; bottom:0; padding:10px 12px 12px; }
  .pk .ad{ font-weight:800; font-size:13.5px; line-height:1.15; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,.75); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .pk .yildiz{ margin-top:5px; font-size:11.5px; text-shadow:0 1px 6px rgba(0,0,0,.6); }
  .pk .alt{ display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
  .pk .fi{ color:var(--gold); font-weight:800; font-size:15px; text-shadow:0 2px 8px rgba(0,0,0,.75); }
  .pk .art{ width:30px; height:30px; border-radius:10px; border:none; color:#fff; font-size:18px; line-height:1; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 6px 14px rgba(139,59,234,.6); }

  .ozel{ position:relative; margin-top:12px; border-radius:18px; overflow:hidden; padding:13px 15px;
    background:linear-gradient(135deg,#4a1d6b,#2a1140); border:1px solid rgba(233,196,106,.28); box-shadow:0 14px 30px -18px rgba(0,0,0,.85); display:flex; align-items:center; }
  .ozel::before{ content:''; position:absolute; inset:0; background:radial-gradient(60% 90% at 90% 10%, rgba(233,196,106,.18), transparent 60%); }
  .ozel .ic{ position:relative; flex:1; }
  .ozel .ic b{ font-family:var(--serif); font-size:16px; }
  .ozel .ic p{ color:#E9D3EE; font-size:11.5px; margin-top:2px; }
  .sayac{ display:flex; gap:7px; margin-top:9px; }
  .sayac > div{ background:rgba(0,0,0,.32); border:1px solid var(--cizgi); border-radius:11px; padding:5px 0; width:48px; text-align:center; }
  .sayac b{ display:block; font-size:17px; font-weight:800; color:var(--gold); font-variant-numeric:tabular-nums; }
  .sayac i{ font-style:normal; font-size:8px; font-weight:700; letter-spacing:.5px; color:var(--sessiz); }
  .rozet{ position:relative; width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-left:10px; flex:0 0 auto;
    background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; font-weight:800; font-size:12px; box-shadow:0 8px 20px rgba(201,150,47,.5); }
  .rozet span{ font-size:18px; }

  /* alt nav */
  #altbar{ position:fixed; left:0; right:0; bottom:0; z-index:60; display:flex; align-items:flex-end; justify-content:space-around;
    padding:8px 8px calc(8px + env(safe-area-inset-bottom)); background:rgba(20,10,22,.94); backdrop-filter:blur(14px); border-top:1px solid var(--cizgi); }
  #altbar button{ flex:1; background:none; border:none; color:var(--sessiz); font-size:10.5px; font-weight:700; display:flex; flex-direction:column; align-items:center; gap:3px; padding:5px 0; }
  #altbar button span{ font-size:19px; } #altbar button.act{ color:var(--gold); }
  #altbar .qr{ flex:0 0 auto; }
  #altbar .qr .qi{ width:58px; height:58px; margin-top:-24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:23px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 22px rgba(139,59,234,.6), 0 0 0 5px rgba(20,10,22,.94); }
  .nrozet{ position:absolute; top:-3px; right:calc(50% - 22px); background:#F43F5E; color:#fff; font-size:10px; font-weight:800; min-width:17px; height:17px; border-radius:9px; display:flex; align-items:center; justify-content:center; padding:0 4px; }

  /* ==================== TABLET / GENIS EKRAN ==================== */
  #desk{ display:none; }
  @media(min-width:920px){
    #wrap{ display:none; }
    #desk{ display:flex; min-height:100dvh; }
    #side{ width:270px; flex:0 0 270px; position:sticky; top:0; align-self:flex-start; height:100dvh; padding:26px 20px;
      display:flex; flex-direction:column; gap:6px; border-right:1px solid var(--cizgi); background:rgba(20,10,22,.4); }
    #side .brand{ margin:4px 4px 22px; }
    #side .nav{ display:flex; flex-direction:column; gap:4px; }
    #side .nav a{ display:flex; align-items:center; gap:13px; padding:13px 15px; border-radius:14px; color:#D8C6D8; font-size:14.5px; font-weight:600; cursor:pointer; }
    #side .nav a span{ font-size:18px; width:22px; text-align:center; }
    #side .nav a:hover{ background:rgba(255,255,255,.05); }
    #side .nav a.act{ background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; box-shadow:0 8px 20px rgba(139,59,234,.45); }
    #side .sp{ flex:1; }
    #side .sbtn{ display:flex; align-items:center; gap:11px; padding:14px 16px; border-radius:16px; border:none; cursor:pointer; text-align:left; margin-top:10px; }
    #side .sbtn span{ font-size:20px; }
    #side .sbtn b{ display:block; font-size:14px; font-weight:800; } #side .sbtn i{ font-style:normal; font-size:11px; opacity:.85; }
    #side .cagir{ background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; }
    #side .hesap{ background:var(--card2); color:var(--ink); border:1px solid var(--cizgi); }
    #side .dil{ margin-top:14px; background:rgba(0,0,0,.25); border:1px solid var(--cizgi); color:#D8C6D8; padding:12px 15px; border-radius:14px; font-size:13.5px; display:flex; align-items:center; gap:9px; }

    #deskmain{ flex:1; min-width:0; padding:26px 30px 60px; overflow-y:auto; height:100dvh; }
    .hero{ position:relative; border-radius:26px; overflow:hidden; min-height:320px; display:flex; align-items:center; padding:44px 48px;
      background:#241233; border:1px solid var(--cizgi); box-shadow:0 26px 60px -30px rgba(0,0,0,.9); }
    .hero .hbg{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; }
    .hero .hsh{ position:absolute; inset:0; z-index:1;
      background:linear-gradient(100deg, rgba(18,9,18,.96) 0%, rgba(28,13,34,.85) 38%, rgba(28,13,34,.25) 70%, rgba(28,13,34,.05) 100%); }
    .hero .ht{ position:relative; z-index:2; max-width:56%; }
    .hero .scr{ font-family:var(--script); font-size:40px; font-weight:700; color:var(--gold); line-height:.9; text-shadow:0 4px 18px rgba(233,196,106,.35); }
    .hero .big{ font-family:var(--serif); font-weight:900; font-size:46px; line-height:1.02; margin-top:4px; letter-spacing:.5px; }
    .hero .sub{ color:#E9D3EE; font-size:14.5px; margin-top:14px; max-width:400px; line-height:1.5; }
    .hero .kesfet{ margin-top:22px; display:inline-flex; align-items:center; gap:10px; background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; border:none;
      font-size:14.5px; font-weight:800; padding:14px 26px; border-radius:16px; cursor:pointer; box-shadow:0 12px 26px rgba(139,59,234,.5); }
    .hero .plate{ position:absolute; right:6%; top:50%; transform:translateY(-50%); font-size:150px; filter:drop-shadow(0 20px 40px rgba(0,0,0,.6)); opacity:.92; }
    .hero .dots{ position:absolute; z-index:2; left:48px; bottom:22px; display:flex; gap:7px; }
    .hero .dots i{ width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,.3); }
    .hero .dots i.on{ width:22px; border-radius:5px; background:var(--gold); }

    .dgrid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; margin-top:16px; }
    .dk{ background:var(--card); border:1px solid var(--cizgi); border-radius:22px; overflow:hidden; cursor:pointer; box-shadow:0 16px 34px -18px rgba(0,0,0,.8); transition:transform .18s; }
    .dk:hover{ transform:translateY(-4px); }
    .dk .g{ position:relative; height:150px; background:#1e1024; }
    .dk .g img{ width:100%; height:100%; object-fit:cover; }
    .dk .em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:58px; }
    .dk .tag{ position:absolute; top:11px; left:11px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; font-size:10.5px; font-weight:800; padding:4px 10px; border-radius:20px; }
    .dk .b{ padding:14px 15px 16px; }
    .dk .ad{ font-weight:800; font-size:16px; }
    .dk .alt{ display:flex; align-items:center; justify-content:space-between; margin-top:11px; }
    .dk .fi{ color:var(--gold); font-weight:800; font-size:17px; }
    .dk .art{ width:38px; height:38px; border-radius:12px; border:none; color:#fff; font-size:22px; line-height:1; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 18px rgba(139,59,234,.5); }

    .qrban{ margin-top:30px; border-radius:24px; padding:32px 36px; display:flex; align-items:center; gap:20px;
      background:linear-gradient(120deg,#241329,#1a0d20); border:1px solid var(--cizgi); box-shadow:0 20px 44px -24px rgba(0,0,0,.85); }
    .qrban .qt{ flex:1; }
    .qrban .qt b{ font-family:var(--serif); font-size:24px; }
    .qrban .qt p{ color:var(--sessiz); font-size:14px; margin-top:8px; max-width:460px; line-height:1.5; }
    .qrban .qt .okut{ margin-top:18px; background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; border:none; font-size:14px; font-weight:800; padding:13px 24px; border-radius:14px; cursor:pointer; }
    .qrban .qg{ font-size:96px; filter:drop-shadow(0 12px 26px rgba(139,59,234,.5)); }
    #deskcart{ display:flex; }
  }
  /* masaustu yuzen sepet butonu */
  #deskcart{ display:none; position:fixed; right:26px; bottom:26px; z-index:70; align-items:center; gap:10px; cursor:pointer;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; border:none; padding:15px 22px; border-radius:20px; font-size:14.5px; font-weight:800;
    box-shadow:0 14px 34px rgba(139,59,234,.6); }
  #deskcart .dc-n{ background:#fff; color:var(--mor); min-width:22px; height:22px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:12.5px; }
  #deskcart.bos{ display:none !important; }

  /* ==================== ORTAK OVERLAY'LER ==================== */
  .ov{ position:fixed; inset:0; z-index:80; display:none; }
  .ov.acik{ display:flex; }
  .ov-bg{ position:absolute; inset:0; background:rgba(6,4,10,.7); backdrop-filter:blur(4px); }

  /* urun detay */
  #detay{ align-items:flex-end; justify-content:center; }
  @media(min-width:920px){ #detay{ align-items:center; } }
  #detay .kutu{ position:relative; z-index:2; width:100%; max-width:480px; background:linear-gradient(180deg,#2a1731,#160a1a); border:1px solid var(--cizgi);
    border-radius:26px 26px 0 0; overflow:hidden; max-height:92dvh; overflow-y:auto; animation:slideUp .3s cubic-bezier(.2,.8,.2,1); }
  @media(min-width:920px){ #detay .kutu{ border-radius:26px; } }
  #detay .foto{ position:relative; height:240px; background:#1e1024; }
  #detay .foto img{ width:100%; height:100%; object-fit:cover; }
  #detay .foto .em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:96px; }
  #detay .foto .x{ position:absolute; top:14px; right:14px; width:40px; height:40px; border-radius:50%; border:none; background:rgba(0,0,0,.5); color:#fff; font-size:18px; }
  #detay .in{ padding:20px 20px 26px; }
  #detay .in h2{ font-family:var(--serif); font-size:24px; }
  #detay .in .fi{ display:inline-block; margin-top:10px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:#3a2600; font-weight:800; font-size:17px; padding:5px 15px; border-radius:22px; }
  #detay .in .ac{ color:#D8C8D6; font-size:14px; line-height:1.55; margin-top:14px; }
  #detay .puanbox{ margin-top:18px; padding:15px; border-radius:16px; background:rgba(0,0,0,.22); border:1px solid var(--cizgi); }
  #detay .puanbox .u{ font-size:13px; color:var(--sessiz); }
  #detay .puanbox .u b{ color:var(--gold); }
  #detay .puanver{ display:flex; gap:8px; margin-top:11px; }
  #detay .puanver .s{ font-size:30px; cursor:pointer; filter:grayscale(1) opacity(.5); transition:.12s; }
  #detay .puanver .s.on{ filter:none; transform:scale(1.08); }
  #detay .puanver .s:hover{ transform:scale(1.12); }
  #detay .miktar{ display:flex; align-items:center; gap:16px; margin-top:20px; }
  #detay .miktar button{ width:44px; height:44px; border-radius:13px; border:none; background:var(--card2); color:#fff; font-size:22px; }
  #detay .miktar span{ font-size:20px; font-weight:800; min-width:26px; text-align:center; }
  #detay .ekle{ width:100%; margin-top:20px; border:none; border-radius:16px; padding:16px; font-weight:800; font-size:15.5px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 10px 24px rgba(139,59,234,.5); }
  #detay .ekle:disabled{ opacity:.5; }

  /* sepet sheet */
  #sepet{ align-items:flex-end; justify-content:center; }
  @media(min-width:920px){ #sepet{ align-items:center; } }
  #sepet .kutu{ position:relative; z-index:2; width:100%; max-width:480px; background:linear-gradient(180deg,#241329,#160a1a); border:1px solid var(--cizgi);
    border-radius:26px 26px 0 0; max-height:88dvh; display:flex; flex-direction:column; animation:slideUp .3s cubic-bezier(.2,.8,.2,1); }
  @media(min-width:920px){ #sepet .kutu{ border-radius:24px; } }
  #sepet .bar{ display:flex; align-items:center; padding:18px 20px 12px; }
  #sepet .bar b{ font-family:var(--serif); font-size:20px; color:var(--gold); }
  #sepet .bar .x{ margin-left:auto; width:36px; height:36px; border-radius:50%; border:none; background:rgba(255,255,255,.1); color:#fff; font-size:16px; }
  #sepet .liste{ flex:1; overflow-y:auto; padding:0 20px; }
  #sepet .sat{ display:flex; align-items:center; gap:12px; padding:13px 0; border-bottom:1px solid rgba(255,255,255,.06); }
  #sepet .sat .sad{ flex:1; font-size:14.5px; font-weight:600; }
  #sepet .sat .sf{ color:var(--gold); font-weight:800; font-size:14px; min-width:78px; text-align:right; }
  #sepet .adet{ display:flex; align-items:center; gap:10px; }
  #sepet .adet button{ width:30px; height:30px; border-radius:9px; border:none; background:var(--card2); color:#fff; font-size:18px; }
  #sepet .adet span{ min-width:18px; text-align:center; font-weight:800; }
  #sepet .bos{ text-align:center; color:var(--sessiz); padding:40px 0; }
  #sepet .foot{ padding:14px 20px calc(18px + env(safe-area-inset-bottom)); border-top:1px solid var(--cizgi); }
  #sepet .top{ display:flex; justify-content:space-between; font-weight:800; font-size:17px; margin-bottom:12px; }
  #sepet .top b{ color:var(--gold); }
  #sepet .gonder{ width:100%; border:none; border-radius:16px; padding:16px; font-weight:800; font-size:15.5px; color:#fff;
    background:linear-gradient(135deg,#16A34A,#22C55E); box-shadow:0 10px 24px rgba(34,197,94,.4); }
  #sepet .gonder:disabled{ opacity:.5; }

  /* tam menu overlay */
  #menu{ }
  #menu .kutu{ position:relative; z-index:2; width:100%; height:100dvh; overflow-y:auto; display:flex; flex-direction:column; }
  #menu .mbar{ position:sticky; top:0; z-index:3; display:flex; align-items:center; gap:12px; padding:16px 18px;
    background:linear-gradient(135deg,rgba(139,59,234,.3),rgba(51,20,54,.65)); border-bottom:1px solid var(--cizgi); backdrop-filter:blur(10px); }
  #menu .mbar b{ font-family:var(--serif); font-size:19px; color:var(--gold); }
  #menu .mbar .x{ margin-left:auto; background:rgba(255,255,255,.16); color:#fff; border:none; font-size:13.5px; font-weight:800; padding:9px 15px; border-radius:22px; }
  #menu .mbody{ padding:6px 16px 40px; max-width:1000px; margin:0 auto; width:100%; }
  #menu .kat{ display:flex; align-items:center; gap:10px; font-family:var(--serif); font-size:21px; font-weight:800; margin:24px 2px 12px; }
  #menu .kat span{ font-size:24px; } #menu .kat::after{ content:''; flex:1; height:2px; margin-left:6px; border-radius:2px; background:linear-gradient(90deg,rgba(233,196,106,.6),transparent); }
  #menu .mgrid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:16px; }
  #menu .mk{ display:flex; gap:12px; background:var(--card); border:1px solid var(--cizgi); border-radius:18px; padding:11px; cursor:pointer; }
  #menu .mk .g{ width:82px; height:82px; flex:0 0 82px; border-radius:13px; overflow:hidden; background:#1e1024; }
  #menu .mk .g img{ width:100%; height:100%; object-fit:cover; }
  #menu .mk .em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:34px; }
  #menu .mk .b{ flex:1; min-width:0; display:flex; flex-direction:column; }
  #menu .mk .ad{ font-weight:800; font-size:14.5px; }
  #menu .mk .ac{ color:var(--sessiz); font-size:11.5px; margin-top:3px; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
  #menu .mk .alt{ display:flex; align-items:center; justify-content:space-between; margin-top:auto; padding-top:8px; }
  #menu .mk .fi{ color:var(--gold); font-weight:800; font-size:14.5px; }
  #menu .mk .art{ width:30px; height:30px; border-radius:10px; border:none; color:#fff; font-size:18px; background:linear-gradient(135deg,var(--mor),var(--mavi)); }

  #toast{ position:fixed; left:50%; transform:translateX(-50%); bottom:100px; z-index:95; background:linear-gradient(135deg,#2a1731,#160a1a); color:#fff;
    border:1px solid var(--cizgi); padding:13px 18px; border-radius:16px; font-size:13.5px; font-weight:600; box-shadow:0 14px 34px rgba(0,0,0,.6); max-width:88%; text-align:center; opacity:0; transition:.25s; pointer-events:none; }

  @keyframes up{ to{ opacity:1; transform:none; } }
  @keyframes slideUp{ from{ transform:translateY(40px); opacity:.4; } to{ transform:none; opacity:1; } }
</style>
</head>
<body>

<!-- ==================== TELEFON ==================== -->
<div id="wrap">
  <header>
    <span class="brand"><span class="toque">👨‍🍳</span><span class="bt"><b>{{ $sube->ad ?? 'ResteOS' }}</b><i>RESTORAN</i></span></span>
    <button class="trbtn">TR ▾</button>
  </header>

  <div class="hg">
    <h1>Hoş geldiniz! 👋</h1>
    <p>Lezzet dolu bir deneyime hazır mısınız?</p>
    <form class="ara" onsubmit="araGonder(event)">
      <input id="ara" placeholder="Ne yemek istersiniz?" autocomplete="off">
      <button type="submit" aria-label="Ara">🔍</button>
    </form>
  </div>

  <div class="chips" id="chips"></div>

  <div class="bbas"><b>Popüler Lezzetler</b><a onclick="menuAc()">Tümünü Gör →</a></div>
  <div class="pop" id="pop"><div style="color:var(--sessiz);font-size:13px;padding:16px 2px">Lezzetler yükleniyor…</div></div>

  <div class="ozel">
    <div class="ic">
      <b>Bugüne Özel</b>
      <p>Seçili menülerde %20 indirim!</p>
      <div class="sayac">
        <div><b id="s-sa">00</b><i>SAAT</i></div>
        <div><b id="s-dk">00</b><i>DAKİKA</i></div>
        <div><b id="s-sn">00</b><i>SANİYE</i></div>
      </div>
    </div>
    <div class="rozet">%<span>20</span></div>
  </div>

  <nav id="altbar">
    <button class="act" onclick="menuAc()"><span>📋</span>Menü</button>
    <button onclick="sepetAc()"><span>🧾</span>Siparişlerim</button>
    <button class="qr" onclick="menuAc()"><div class="qi">🍽️</div></button>
    <button onclick="cagir('garson')"><span>🔔</span>Çağır</button>
    <button onclick="cagir('hesap')"><span>💳</span>Hesabım</button>
  </nav>
</div>

<!-- ==================== TABLET / GENIS EKRAN ==================== -->
<div id="desk">
  <aside id="side">
    <span class="brand"><span class="toque">👨‍🍳</span><span class="bt"><b>{{ $sube->ad ?? 'ResteOS' }}</b><i>RESTORAN</i></span></span>
    <div class="nav">
      <a class="act" onclick="anaSayfa(this)"><span>🏠</span>Ana Sayfa</a>
      <a onclick="menuAc()"><span>📖</span>Menüler</a>
      <a onclick="menuAc('içecek')"><span>🥤</span>İçecekler</a>
      <a onclick="menuAc('tatlı')"><span>🍰</span>Tatlılar</a>
      <a onclick="popScroll(this)"><span>⭐</span>Popüler</a>
      <a onclick="kampanya(this)"><span>🎁</span>Kampanyalar</a>
      <a onclick="hakkimizda()"><span>ℹ️</span>Hakkımızda</a>
    </div>
    <div class="sp"></div>
    <button class="sbtn cagir" onclick="cagir('garson')"><span>🔔</span><span><b>Garson Çağır</b><i>Size hemen yardımcı olalım</i></span></button>
    <button class="sbtn hesap" onclick="cagir('hesap')"><span>💳</span><span><b>Hesabımı İste</b><i>Hesabınızı kolayca alın</i></span></button>
    <div class="dil">🌐 Türkçe ▾</div>
  </aside>

  <main id="deskmain">
    <div class="hero">
      <img class="hbg" src="https://images.unsplash.com/photo-1600891964092-4316c288032e?auto=format&fit=crop&w=1200&q=75" alt="" onerror="this.style.display='none'">
      <div class="hsh"></div>
      <div class="ht">
        <div class="scr">Lezzetin</div>
        <div class="big">EN KEYİFLİSİ</div>
        <div class="sub">En özel tarifler, taptaze malzemelerle sizler için hazırlandı.</div>
        <button class="kesfet" onclick="menuAc()">MENÜYÜ KEŞFET →</button>
      </div>
      <div class="dots"><i class="on"></i><i></i><i></i></div>
    </div>

    <div class="bbas" style="margin-top:30px"><b style="font-size:24px">Öne Çıkanlar</b><a onclick="menuAc()">Tümünü Gör →</a></div>
    <div class="dgrid" id="dgrid"></div>

    <div class="qrban">
      <div class="qt">
        <b>QR ile hızlı sipariş</b>
        <p>Masanızdaki menüye hızlıca ulaşın, siparişinizi verin; garson beklemeden keyfinize bakın.</p>
        <button class="okut" onclick="menuAc()">MENÜYÜ AÇ</button>
      </div>
      <div class="qg">🍽️</div>
    </div>
  </main>
</div>

<!-- ==================== URUN DETAY ==================== -->
<div class="ov" id="detay">
  <div class="ov-bg" onclick="detayKapat()"></div>
  <div class="kutu">
    <div class="foto" id="d-foto"></div>
    <div class="in">
      <h2 id="d-ad"></h2>
      <span class="fi" id="d-fi"></span>
      <div class="ac" id="d-ac"></div>
      <div class="puanbox">
        <div class="u" id="d-puan-ust">Bu ürünü ilk siz değerlendirin</div>
        <div class="puanver" id="d-puanver">
          <span class="s" data-p="1">★</span><span class="s" data-p="2">★</span><span class="s" data-p="3">★</span><span class="s" data-p="4">★</span><span class="s" data-p="5">★</span>
        </div>
      </div>
      <div class="miktar">
        <button onclick="dMiktar(-1)">−</button><span id="d-mik">1</span><button onclick="dMiktar(1)">+</button>
        <button class="ekle" style="flex:1;margin-top:0;width:auto" onclick="detaydanEkle()" id="d-ekle">Sepete Ekle</button>
      </div>
    </div>
  </div>
</div>

<!-- ==================== SEPET ==================== -->
<div class="ov" id="sepet">
  <div class="ov-bg" onclick="sepetKapat()"></div>
  <div class="kutu">
    <div class="bar"><b>🧾 Siparişiniz</b><button class="x" onclick="sepetKapat()">✕</button></div>
    <div class="liste" id="sepet-liste"></div>
    <div class="foot">
      <div class="top"><span>Toplam</span><b id="sepet-toplam">0 TL</b></div>
      <button class="gonder" id="sepet-gonder" onclick="siparisGonder()">✅ Siparişi Gönder</button>
    </div>
  </div>
</div>

<!-- ==================== TAM MENU ==================== -->
<div class="ov" id="menu">
  <div class="kutu">
    <div class="mbar"><span class="brand"><span class="toque">👨‍🍳</span></span><b id="menu-bas">Menü</b><button class="x" onclick="menuKapat()">Kapat ✕</button></div>
    <div class="mbody" id="menu-body"></div>
  </div>
</div>

<button id="deskcart" class="bos" onclick="sepetAc()">🧾 Sepetim <span class="dc-n" id="dc-n">0</span></button>

<div id="toast"></div>

<script>
const MASA = @json($masa->id ?? 0);
const SUBE_AD = @json($sube->ad ?? 'Restoran');
let _data = [];            // kategoriler
let _urun = {};            // urun_id -> urun (detay/sepet icin)
let _sepet = [];           // {urun_id, ad, fiyat, adet}
let _detayUrun = null, _detayMik = 1, _aktifKey = '*';

function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function gradientFor(str){ let h=0; str=String(str||''); for(let i=0;i<str.length;i++) h=(h*31+str.charCodeAt(i))%360; return `linear-gradient(135deg,hsl(${h},60%,42%),hsl(${(h+40)%360},64%,30%))`; }
function toast(msg){ const t=document.getElementById('toast'); t.textContent=msg; t.style.opacity='1'; clearTimeout(t._z); t._z=setTimeout(()=>t.style.opacity='0',3000); }

/* ---- veri ---- */
async function yukle(){
  try{ const r=await fetch('/api/qr/menu-tam?masa='+MASA); const j=await r.json(); _data=(j.ok&&Array.isArray(j.kategoriler))?j.kategoriler:[]; }
  catch(e){ _data=[]; }
  _urun={}; _data.forEach(k=>(k.kartlar||[]).forEach(u=>{ u._kat=k.ad; if(u.urun_id) _urun[u.urun_id]=u; }));
  chipleriCiz(); populerCiz('*'); dgridCiz();
}

/* ---- yildiz gosterim ---- */
function yildizHtml(u){
  if(u.puan && u.puan_say>0) return `<span class="yildiz">★ ${u.puan.toFixed(1)} <span style="color:#d8c8d6;font-weight:600">(${u.puan_say})</span></span>`;
  return '';   // puan yoksa hicbir sey gosterme ("Yeni" kaldirildi)
}
/* ---- yemek turune gore GERCEK stok fotograf (URL'ler curl ile test edildi=200) ---- */
const IMG='https://images.unsplash.com/photo-', Q='?auto=format&fit=crop&w=600&q=72';
const FOTO_HARITA=[
  ['izgara kofte','1529042410759-befb1204b468'],['kofte','1529042410759-befb1204b468'],
  ['adana','1601050690597-df0568f70950'],['urfa','1601050690597-df0568f70950'],['iskender','1601050690597-df0568f70950'],
  ['beyti','1601050690597-df0568f70950'],['sis','1601050690597-df0568f70950'],['kebap','1601050690597-df0568f70950'],
  ['pirzola','1601050690597-df0568f70950'],['kuzu','1601050690597-df0568f70950'],
  ['antrikot','1600891964092-4316c288032e'],['biftek','1600891964092-4316c288032e'],['bonfile','1600891964092-4316c288032e'],['steak','1600891964092-4316c288032e'],
  ['tavuk','1598103442097-8b74394b95c6'],['pilic','1598103442097-8b74394b95c6'],['kanat','1598103442097-8b74394b95c6'],
  ['somon','1519708227418-c8fd9a32b7a2'],['levrek','1519708227418-c8fd9a32b7a2'],['cipura','1519708227418-c8fd9a32b7a2'],['karides','1519708227418-c8fd9a32b7a2'],['balik','1519708227418-c8fd9a32b7a2'],['deniz','1519708227418-c8fd9a32b7a2'],
  ['sote','1541014741259-de529411b96a'],['guvec','1541014741259-de529411b96a'],['kavurma','1541014741259-de529411b96a'],['tas kebap','1541014741259-de529411b96a'],
  ['lahmacun','1513104890138-7c749659a591'],['pide','1513104890138-7c749659a591'],['pizza','1513104890138-7c749659a591'],
  ['hamburger','1568901346375-23c9450c58cd'],['cheeseburger','1568901346375-23c9450c58cd'],['burger','1568901346375-23c9450c58cd'],
  ['spagetti','1551183053-bf91a1d81141'],['bolonez','1551183053-bf91a1d81141'],['penne','1551183053-bf91a1d81141'],['makarna','1551183053-bf91a1d81141'],
  ['sezar','1512621776951-a57141f2eefd'],['coban','1546069901-ba9599a7e63c'],['salata','1512621776951-a57141f2eefd'],
  ['mercimek','1547592166-23ac45744acd'],['ezogelin','1547592166-23ac45744acd'],['yayla','1547592166-23ac45744acd'],['corba','1547592166-23ac45744acd'],
  ['kunefe','1631452180519-c014fe946bc7'],['baklava','1551024601-bec78aea704b'],
  ['sutlac','1551024601-bec78aea704b'],['kazandibi','1551024601-bec78aea704b'],['profiterol','1578985545062-69928b1d9587'],['browni','1578985545062-69928b1d9587'],['brownie','1578985545062-69928b1d9587'],['cheesecake','1578985545062-69928b1d9587'],['tiramisu','1578985545062-69928b1d9587'],['magnolya','1578985545062-69928b1d9587'],['kek','1578985545062-69928b1d9587'],['tatli','1578985545062-69928b1d9587'],
  ['dondurma','1497034825429-c343d7c6a68f'],
  ['latte','1509042239860-f550ce710b93'],['espresso','1509042239860-f550ce710b93'],['cappuccino','1509042239860-f550ce710b93'],['americano','1509042239860-f550ce710b93'],['filtre','1509042239860-f550ce710b93'],['kahve','1509042239860-f550ce710b93'],
  ['bitki cay','1544787219-7f47ccb76574'],['cay','1544787219-7f47ccb76574'],
  ['milkshake','1558961363-fa8fdf82db35'],['smoothie','1558961363-fa8fdf82db35'],['shake','1558961363-fa8fdf82db35'],['ayran','1558961363-fa8fdf82db35'],
  ['portakal suyu','1621263764928-df1444c5e859'],['meyve suyu','1621263764928-df1444c5e859'],['limonata','1621263764928-df1444c5e859'],
  ['kola','1600335895229-6e75511892c8'],['gazoz','1600335895229-6e75511892c8'],['soda','1600335895229-6e75511892c8'],['sprite','1600335895229-6e75511892c8'],['fanta','1600335895229-6e75511892c8'],
  ['pilav','1476718406336-bb5a9690ee2a'],['pirinc','1476718406336-bb5a9690ee2a'],['bulgur','1476718406336-bb5a9690ee2a'],['risotto','1476718406336-bb5a9690ee2a'],
  ['humus','1587314168485-3236d6710814'],['haydari','1587314168485-3236d6710814'],['sigara boregi','1587314168485-3236d6710814'],['meze','1587314168485-3236d6710814'],['baslangic','1587314168485-3236d6710814'],['antre','1587314168485-3236d6710814'],
  ['menemen','1533089860892-a7c6f0a88666'],['omlet','1533089860892-a7c6f0a88666'],['yumurta','1533089860892-a7c6f0a88666'],['serpme','1533089860892-a7c6f0a88666'],['kahvalti','1533089860892-a7c6f0a88666'],
  ['nugget','1573080496219-bb080dd4f877'],['sogan halka','1573080496219-bb080dd4f877'],['cips','1573080496219-bb080dd4f877'],['patates','1573080496219-bb080dd4f877'],
];
function norm(s){ return (s||'').toLocaleLowerCase('tr').replace(/ı/g,'i').replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ö/g,'o').replace(/ç/g,'c'); }
function stokFoto(ad, kat){
  const t=norm(ad+' '+(kat||''));
  for(const [k,id] of FOTO_HARITA){ if(t.indexOf(norm(k))!==-1) return IMG+id+Q; }
  return IMG+'1541014741259-de529411b96a'+Q;   // genel: sik tabak
}
function gorselHtml(u, cls){
  const src = (u.gercek_foto && u.gorsel) ? u.gorsel : stokFoto(u.ad, u._kat);
  return `<img src="${esc(src)}" loading="lazy" onerror="this.onerror=null;this.parentNode.innerHTML='<div class=&quot;${cls}&quot; style=&quot;background:${gradientFor(u.ad)}&quot;>${u.emoji||'🍽️'}</div>'">`;
}

/* ---- telefon: cipler ---- */
function chipleriCiz(){
  const w=document.getElementById('chips'); if(!w) return;
  let h=`<div class="chip act" onclick="chipSec(this,'*')"><div class="ci">🍽️</div><div class="cn">Tüm Menüler</div></div>`;
  _data.forEach((k,i)=>{ h+=`<div class="chip" onclick="chipSec(this,${i})"><div class="ci">${k.emoji||'🍽️'}</div><div class="cn">${esc(k.ad)}</div></div>`; });
  w.innerHTML=h;
}
function chipSec(el,key){ document.querySelectorAll('#chips .chip').forEach(c=>c.classList.remove('act')); el.classList.add('act'); populerCiz(key); }

/* ---- telefon: populer ---- */
function urunlerFor(key){
  if(key==='*'){ let a=[]; _data.forEach(k=>(k.kartlar||[]).slice(0,2).forEach(u=>a.push(u))); return a.slice(0,12); }
  const k=_data[+key]; return (k&&k.kartlar)?k.kartlar:[];
}
function populerCiz(key){
  _aktifKey=key;
  const w=document.getElementById('pop'); if(!w) return;
  const list=urunlerFor(key);
  if(!list.length){ w.innerHTML='<div style="color:var(--sessiz);font-size:13px;padding:16px 2px">Ürün yok.</div>'; return; }
  w.innerHTML='';
  list.forEach((u,i)=>{
    const c=document.createElement('div'); c.className='pk'; c.style.animationDelay=(i*40)+'ms';
    const tuk=u.etiket==='Tükendi';
    c.innerHTML=`<div class="g">${gorselHtml(u,'em')}</div>${u.etiket&&!tuk?`<span class="tag">${esc(u.etiket)}</span>`:''}${tuk?'<div class="tuk">Tükendi</div>':''}<div class="glass"></div>`
      +`<div class="b"><div class="ad">${esc(u.ad)}</div>${yildizHtml(u)}`
      +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div>`;
    c.querySelector('.art').addEventListener('click',ev=>{ ev.stopPropagation(); if(!tuk) sepeteEkle(u,1,true); });
    c.addEventListener('click',()=>detayAc(u));
    w.appendChild(c);
  });
}

/* ---- tablet: one cikanlar grid ---- */
function dgridCiz(){
  const w=document.getElementById('dgrid'); if(!w) return;
  const list=urunlerFor('*');
  w.innerHTML='';
  list.forEach(u=>{
    const tuk=u.etiket==='Tükendi';
    const c=document.createElement('div'); c.className='dk';
    c.innerHTML=`<div class="g">${gorselHtml(u,'em')}${u.etiket&&!tuk?`<span class="tag">${esc(u.etiket)}</span>`:''}${tuk?'<div class="tuk">Tükendi</div>':''}</div>`
      +`<div class="b"><div class="ad">${esc(u.ad)}</div><div style="margin-top:7px">${yildizHtml(u)}</div>`
      +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div>`;
    c.querySelector('.art').addEventListener('click',ev=>{ ev.stopPropagation(); if(!tuk) sepeteEkle(u,1,true); });
    c.addEventListener('click',()=>detayAc(u));
    w.appendChild(c);
  });
}

/* ---- URUN DETAY + PUAN VER ---- */
function detayAc(u){
  _detayUrun=u; _detayMik=1;
  document.getElementById('d-foto').innerHTML=gorselHtml(u,'em')+'<button class="x" onclick="detayKapat()">✕</button>';
  document.getElementById('d-ad').textContent=u.ad||'';
  document.getElementById('d-fi').textContent=u.fiyat_yazi||'';
  document.getElementById('d-ac').textContent=u.aciklama||'';
  document.getElementById('d-mik').textContent='1';
  const tuk=u.etiket==='Tükendi';
  const eb=document.getElementById('d-ekle'); eb.disabled=tuk; eb.textContent=tuk?'Tükendi':'Sepete Ekle';
  // puan ust bilgi
  const ust=document.getElementById('d-puan-ust');
  ust.innerHTML = (u.puan&&u.puan_say>0) ? `Ortalama puan: <b>★ ${u.puan.toFixed(1)}</b> (${u.puan_say} değerlendirme) · Siz de puan verin:` : 'Bu ürünü ilk siz değerlendirin:';
  puanIsaretle(0);
  document.getElementById('detay').classList.add('acik');
}
function detayKapat(){ document.getElementById('detay').classList.remove('acik'); }
function dMiktar(d){ _detayMik=Math.max(1,_detayMik+d); document.getElementById('d-mik').textContent=_detayMik; }
function detaydanEkle(){ if(_detayUrun){ sepeteEkle(_detayUrun,_detayMik,false); detayKapat(); toast('🛒 '+_detayUrun.ad+' sepete eklendi'); } }
function puanIsaretle(n){ document.querySelectorAll('#d-puanver .s').forEach(s=>s.classList.toggle('on',+s.dataset.p<=n)); }
document.querySelectorAll('#d-puanver .s').forEach(s=>{
  s.addEventListener('mouseenter',()=>puanIsaretle(+s.dataset.p));
  s.addEventListener('click',()=>puanVer(+s.dataset.p));
});
document.getElementById('d-puanver').addEventListener('mouseleave',()=>puanIsaretle(0));
async function puanVer(p){
  if(!_detayUrun) return;
  puanIsaretle(p);
  try{
    const r=await fetch('/api/qr/urun-puan',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({masa:MASA,urun_id:_detayUrun.urun_id,puan:p,parmak:parmakIzi()})});
    const j=await r.json();
    if(j.ok){
      _detayUrun.puan=j.puan; _detayUrun.puan_say=j.puan_say;
      if(_urun[_detayUrun.urun_id]){ _urun[_detayUrun.urun_id].puan=j.puan; _urun[_detayUrun.urun_id].puan_say=j.puan_say; }
      document.getElementById('d-puan-ust').innerHTML=`Puanınız için teşekkürler! 🌟 Ortalama: <b>★ ${j.puan.toFixed(1)}</b> (${j.puan_say})`;
      populerCiz(_aktifKey); dgridCiz();
      toast('🌟 Değerlendirmeniz kaydedildi');
    }
  }catch(e){ toast('Puan gönderilemedi, tekrar deneyin'); }
}
function parmakIzi(){ let x=localStorage.getItem('_pf'); if(!x){ x=Math.random().toString(36).slice(2)+Date.now().toString(36); localStorage.setItem('_pf',x); } return x; }

/* ---- SEPET ---- */
function sepeteEkle(u,adet,mesaj){
  const v=_sepet.find(s=>s.urun_id===u.urun_id);
  if(v) v.adet+=adet; else _sepet.push({urun_id:u.urun_id,ad:u.ad,fiyat:u.fiyat,adet:adet});
  sepetRozet(); if(mesaj) toast('🛒 '+u.ad+' sepete eklendi');
}
function sepetRozet(){
  const n=_sepet.reduce((s,k)=>s+k.adet,0);
  let r=document.querySelector('#altbar .nrozet');
  if(n>0){ if(!r){ r=document.createElement('div'); r.className='nrozet'; document.getElementById('altbar').appendChild(r); } r.textContent=n; }
  else if(r) r.remove();
  const dc=document.getElementById('deskcart'); document.getElementById('dc-n').textContent=n; dc.classList.toggle('bos',n===0);
}
function sepetAc(){
  const l=document.getElementById('sepet-liste');
  if(!_sepet.length){ l.innerHTML='<div class="bos">Sepetiniz boş. 🙂<br>Menüden lezzet seçebilirsiniz.</div>'; }
  else{
    l.innerHTML=_sepet.map((k,i)=>`<div class="sat"><span class="sad">${esc(k.ad)}</span>`
      +`<span class="adet"><button onclick="sepetAdet(${i},-1)">−</button><span>${k.adet}</span><button onclick="sepetAdet(${i},1)">+</button></span>`
      +`<span class="sf">${(k.fiyat*k.adet).toLocaleString('tr')} TL</span></div>`).join('');
  }
  const top=_sepet.reduce((s,k)=>s+k.fiyat*k.adet,0);
  document.getElementById('sepet-toplam').textContent=top.toLocaleString('tr')+' TL';
  document.getElementById('sepet-gonder').disabled=!_sepet.length;
  document.getElementById('sepet').classList.add('acik');
}
function sepetKapat(){ document.getElementById('sepet').classList.remove('acik'); }
function sepetAdet(i,d){ _sepet[i].adet+=d; if(_sepet[i].adet<=0) _sepet.splice(i,1); sepetRozet(); sepetAc(); }
async function siparisGonder(){
  if(!_sepet.length) return;
  const btn=document.getElementById('sepet-gonder'); btn.disabled=true; btn.textContent='Gönderiliyor…';
  try{
    const r=await fetch('/api/qr/siparis-gonder',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({masa:MASA,kalemler:JSON.stringify(_sepet.map(k=>({urun_id:k.urun_id,adet:k.adet})))})});
    const j=await r.json();
    if(j.ok){ _sepet=[]; sepetRozet(); sepetKapat(); toast('✅ Siparişiniz mutfağa iletildi, afiyet olsun!'); }
    else{ toast(j.hata||'Sipariş gönderilemedi'); btn.disabled=false; btn.textContent='✅ Siparişi Gönder'; }
  }catch(e){ toast('Sipariş gönderilemedi, tekrar deneyin'); btn.disabled=false; btn.textContent='✅ Siparişi Gönder'; }
}

/* ---- TAM MENU ---- */
function menuAc(filtre){
  const body=document.getElementById('menu-body'); body.innerHTML='';
  document.getElementById('menu-bas').textContent = filtre ? filtre.charAt(0).toLocaleUpperCase('tr')+filtre.slice(1) : 'Menü';
  let kats=_data;
  if(filtre){ const f=filtre.toLocaleLowerCase('tr'); kats=_data.filter(k=>k.ad.toLocaleLowerCase('tr').includes(f)); if(!kats.length) kats=_data; }
  kats.forEach(k=>{
    const sec=document.createElement('div');
    let h=`<div class="kat"><span>${k.emoji||'🍽️'}</span>${esc(k.ad)}</div><div class="mgrid">`;
    (k.kartlar||[]).forEach(u=>{
      const tuk=u.etiket==='Tükendi';
      h+=`<div class="mk" data-uid="${u.urun_id}"><div class="g">${gorselHtml(u,'em')}</div>`
        +`<div class="b"><div class="ad">${esc(u.ad)}</div><div class="ac">${esc(u.aciklama||'')}</div>`
        +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" data-uid="${u.urun_id}" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div></div>`;
    });
    h+='</div>'; sec.innerHTML=h; body.appendChild(sec);
  });
  body.querySelectorAll('.mk').forEach(el=>{
    el.addEventListener('click',()=>{ const u=_urun[el.dataset.uid]; if(u) detayAc(u); });
  });
  body.querySelectorAll('.mk .art').forEach(b=>{
    b.addEventListener('click',ev=>{ ev.stopPropagation(); const u=_urun[b.dataset.uid]; if(u) sepeteEkle(u,1,true); });
  });
  document.getElementById('menu').classList.add('acik');
}
function menuKapat(){ document.getElementById('menu').classList.remove('acik'); }
function araGonder(e){ e.preventDefault(); const t=(document.getElementById('ara').value||'').trim(); if(!t) return; menuAra(t); }
function menuAra(q){
  const body=document.getElementById('menu-body'); body.innerHTML=''; document.getElementById('menu-bas').textContent='"'+q+'" için sonuçlar';
  const nq=q.toLocaleLowerCase('tr'); let bulunan=[];
  _data.forEach(k=>(k.kartlar||[]).forEach(u=>{ if((u.ad||'').toLocaleLowerCase('tr').includes(nq)||(u.aciklama||'').toLocaleLowerCase('tr').includes(nq)) bulunan.push(u); }));
  if(!bulunan.length){ body.innerHTML='<div style="text-align:center;color:var(--sessiz);padding:50px 0">Sonuç bulunamadı. 🙁</div>'; }
  else{
    let h='<div class="mgrid" style="margin-top:16px">';
    bulunan.forEach(u=>{ h+=`<div class="mk" data-uid="${u.urun_id}"><div class="g">${gorselHtml(u,'em')}</div><div class="b"><div class="ad">${esc(u.ad)}</div><div class="ac">${esc(u.aciklama||'')}</div><div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" data-uid="${u.urun_id}">+</button></div></div></div>`; });
    h+='</div>'; body.innerHTML=h;
    body.querySelectorAll('.mk').forEach(el=>el.addEventListener('click',()=>{ const u=_urun[el.dataset.uid]; if(u) detayAc(u); }));
    body.querySelectorAll('.mk .art').forEach(b=>b.addEventListener('click',ev=>{ ev.stopPropagation(); const u=_urun[b.dataset.uid]; if(u) sepeteEkle(u,1,true); }));
  }
  document.getElementById('menu').classList.add('acik');
}

/* ---- garson/hesap ---- */
async function cagir(tip){
  try{ await fetch('/api/qr/garson-cagir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA,tip})}); }catch(e){}
  toast(tip==='hesap'?'💳 Hesap isteğiniz iletildi, birazdan geliyoruz.':'🔔 Garson çağrıldı, birazdan yanınızdayız.');
}

/* ---- tablet sidebar ---- */
function anaSayfa(el){ document.querySelectorAll('#side .nav a').forEach(a=>a.classList.remove('act')); el.classList.add('act'); document.getElementById('deskmain').scrollTo({top:0,behavior:'smooth'}); }
function popScroll(el){ document.querySelectorAll('#side .nav a').forEach(a=>a.classList.remove('act')); el.classList.add('act'); document.getElementById('dgrid').scrollIntoView({behavior:'smooth',block:'start'}); }
function kampanya(el){ document.querySelectorAll('#side .nav a').forEach(a=>a.classList.remove('act')); el.classList.add('act'); toast('🎁 Bugüne özel: Seçili menülerde %20 indirim!'); }
function hakkimizda(){ toast('👨‍🍳 '+SUBE_AD+' — afiyetle hazırlanan lezzetler.'); }

/* ---- geri sayim ---- */
function sayac(){
  const sa=document.getElementById('s-sa'),dk=document.getElementById('s-dk'),sn=document.getElementById('s-sn'); if(!sa) return;
  function tik(){ const now=new Date(); const bit=new Date(now.getFullYear(),now.getMonth(),now.getDate(),23,59,59); let f=Math.max(0,Math.floor((bit-now)/1000));
    sa.textContent=String(Math.floor(f/3600)).padStart(2,'0'); f%=3600; dk.textContent=String(Math.floor(f/60)).padStart(2,'0'); sn.textContent=String(f%60).padStart(2,'0'); }
  tik(); setInterval(tik,1000);
}

window.addEventListener('load',()=>{ yukle(); sayac(); });
</script>
</body>
</html>

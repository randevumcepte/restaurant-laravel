@php
  $detayHex = $tema['detay'] ?? '#E9C46A';
  $detay2Hex = $tema['detay2'] ?? '#C9962F';
  $dh = ltrim($detayHex, '#'); if (strlen($dh) < 6) $dh = 'E9C46A';
  $dr = hexdec(substr($dh, 0, 2)); $dg = hexdec(substr($dh, 2, 2)); $db = hexdec(substr($dh, 4, 2));
  $detayLum = (0.299 * $dr + 0.587 * $dg + 0.114 * $db) / 255;
  $goldInk = $detayLum > 0.62 ? '#3a2600' : '#ffffff';
@endphp
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
  :root{ --mor:{{ $tema['ana'] ?? '#F6DFA0' }}; --mor2:{{ $tema['ana2'] ?? '#E9C46A' }}; --mavi:{{ $tema['ana3'] ?? '#C9962F' }};
    --ana-ink:{{ $tema['ink'] ?? '#3a2600' }}; --glow:{{ $tema['glow'] ?? 'rgba(233,196,106,.16)' }};
    --card:#201a24; --card2:#2a2130;
    --neutral:rgba(255,255,255,.08); --navbg:rgba(20,10,22,.94); --input:rgba(0,0,0,.28);
    --gold:{{ $detayHex }}; --gold2:{{ $detay2Hex }}; --gold-ink:{{ $goldInk }};
    --cizgi:rgba({{ $dr }},{{ $dg }},{{ $db }},.22); --gold-bg:rgba({{ $dr }},{{ $dg }},{{ $db }},.08); --gold-bd:rgba({{ $dr }},{{ $dg }},{{ $db }},.40);
    --ink:#F3E9EE; --sessiz:#B49CB6;
    --serif:'Playfair Display',Georgia,serif; --script:'Dancing Script',cursive; }
  /* ===== ACIK MOD (gunduz): html.acik -> yuzey/yazi degiskenleri ters cevrilir ===== */
  html.acik{ --card:#FFFFFF; --card2:#F4EDF2; --ink:#241826; --sessiz:#877884;
    --neutral:rgba(0,0,0,.09); --navbg:rgba(255,255,255,.96); --input:rgba(0,0,0,.045); }
  html.acik body{ background:#F5EFF3;
    background-image:
      radial-gradient(900px 620px at 90% -8%, var(--glow), transparent 62%),
      radial-gradient(760px 560px at 4% 106%, var(--glow), transparent 62%),
      radial-gradient(1200px 900px at 60% 0%, #FCF8FB 0%, #F2E9F0 58%, #EDE3EB 100%); }
  html.acik .pk .ad, html.acik .pk .fi{ text-shadow:0 2px 8px rgba(0,0,0,.85); }  /* foto uzeri yazi hep beyaz kalir */
  /* gece/gunduz butonu (menu sol ust) */
  .modbtn{ width:38px; height:38px; border-radius:50%; border:1px solid var(--cizgi); background:var(--card2); color:var(--gold);
    font-size:17px; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,.25); }
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
  .brand .toque{ font-size:24px; filter:drop-shadow(0 2px 6px var(--cizgi)); }
  .brand .bt{ display:flex; flex-direction:column; line-height:1; }
  .brand .bt b{ font-family:var(--serif); font-weight:800; font-size:20px; letter-spacing:1px;
    background:linear-gradient(135deg,var(--gold),var(--gold2)); -webkit-background-clip:text; background-clip:text; -webkit-text-fill-color:transparent; }
  .brand .bt i{ font-style:normal; font-size:8px; font-weight:700; letter-spacing:4px; color:var(--gold2); margin-top:3px; }
  .trbtn{ background:var(--neutral); border:1px solid var(--cizgi); color:var(--ink); font-size:12px; font-weight:700; padding:6px 11px; border-radius:12px; }

  /* yildiz */
  .yildiz{ display:inline-flex; align-items:center; gap:3px; font-size:12.5px; font-weight:800; color:var(--gold); }
  .yildiz.yeni{ color:#C9B7CB; font-weight:700; font-size:11px; background:var(--neutral); padding:2px 8px; border-radius:12px; }

  /* ==================== TELEFON (tek ekrana sigar, kaydirma yok) ==================== */
  #wrap{ height:100dvh; overflow:hidden; display:flex; flex-direction:column; padding:0 14px calc(80px + env(safe-area-inset-bottom)); }
  #wrap > *{ flex-shrink:0; }
  #wrap > header{ position:relative; display:flex; align-items:center; justify-content:center; padding:8px 0 8px; }
  #wrap > header .trbtn{ position:absolute; right:0; top:50%; transform:translateY(-50%); }
  #wrap > header .modbtn{ position:absolute; left:0; top:50%; transform:translateY(-50%); }
  #wrap > header .brand{ flex-direction:column; gap:2px; }
  #wrap > header .brand .bt{ align-items:center; } #wrap > header .brand .toque{ font-size:22px; }
  #wrap > header .brand .bt b{ font-size:18px; }
  .hg{ background:linear-gradient(150deg,rgba(139,59,234,.20),rgba(36,19,41,.65)); border:1px solid var(--cizgi);
    border-radius:18px; padding:13px 15px; box-shadow:0 12px 28px -18px rgba(0,0,0,.8); }
  .hg h1{ font-family:var(--serif); font-weight:800; font-size:18px; }
  .hg p{ color:var(--sessiz); font-size:12px; margin-top:3px; }
  .ara{ display:flex; gap:8px; margin-top:10px; }
  .ara input{ flex:1; background:var(--input); border:1px solid var(--cizgi); color:var(--ink); font-size:13.5px; padding:11px 14px; border-radius:13px; outline:none; }
  .ara input::placeholder{ color:#9a8a9c; }
  .ara button{ width:46px; border:none; border-radius:13px; font-size:17px; color:#fff; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 6px 14px rgba(139,59,234,.5); }

  .chips{ display:flex; gap:9px; overflow-x:auto; padding:11px 0 3px; }
  .chip{ flex:0 0 auto; display:flex; flex-direction:column; align-items:center; gap:5px; min-width:66px; padding:9px 9px 8px;
    border-radius:15px; background:var(--card); border:1px solid var(--cizgi); cursor:pointer; }
  .chip .ci{ width:32px; height:32px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; background:rgba(139,59,234,.16); }
  .chip .cn{ font-size:11px; font-weight:700; color:var(--ink); white-space:nowrap; }
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
  .pk .tag{ position:absolute; z-index:3; top:9px; left:9px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:var(--gold-ink); font-size:9.5px; font-weight:800; padding:3px 8px; border-radius:20px; }
  .pk .tuk{ position:absolute; z-index:3; inset:0; background:rgba(10,6,12,.55); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#fff; }
  .pk .glass{ position:absolute; z-index:1; inset:0;
    background:linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,.30) 48%, rgba(0,0,0,.74) 80%, rgba(0,0,0,.94) 100%); }
  .pk .b{ position:absolute; z-index:2; left:0; right:0; bottom:0; padding:10px 12px 12px; }
  .pk .ad{ font-weight:800; font-size:13.5px; line-height:1.15; color:#fff; text-shadow:0 2px 8px rgba(0,0,0,.75); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .pk .yildiz{ margin-top:5px; font-size:11.5px; text-shadow:0 1px 6px rgba(0,0,0,.6); }
  .pk .alt{ display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
  .pk .fi{ color:var(--gold); font-weight:800; font-size:15px; text-shadow:0 2px 8px rgba(0,0,0,.75); }
  .pk .art{ width:30px; height:30px; border-radius:10px; border:none; color:#fff; font-size:18px; line-height:1; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 6px 14px rgba(139,59,234,.6); }

  .ozel{ position:relative; margin-top:12px; border-radius:18px; overflow:hidden; padding:13px 15px;
    background:linear-gradient(135deg,#4a1d6b,#2a1140); border:1px solid rgba(233,196,106,.28); box-shadow:0 14px 30px -18px rgba(0,0,0,.85); display:flex; align-items:center; }
  .ozel::before{ content:''; position:absolute; inset:0; background:radial-gradient(60% 90% at 90% 10%, var(--cizgi), transparent 60%); }
  .ozel .ic{ position:relative; flex:1; }
  .ozel .ic b{ font-family:var(--serif); font-size:16px; }
  .ozel .ic p{ color:#E9D3EE; font-size:11.5px; margin-top:2px; }
  .sayac{ display:flex; gap:7px; margin-top:9px; }
  .sayac > div{ background:rgba(0,0,0,.32); border:1px solid var(--cizgi); border-radius:11px; padding:5px 0; width:48px; text-align:center; }
  .sayac b{ display:block; font-size:17px; font-weight:800; color:var(--gold); font-variant-numeric:tabular-nums; }
  .sayac i{ font-style:normal; font-size:8px; font-weight:700; letter-spacing:.5px; color:var(--sessiz); }
  .rozet{ position:relative; width:52px; height:52px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin-left:10px; flex:0 0 auto;
    background:linear-gradient(135deg,var(--gold),var(--gold2)); color:var(--gold-ink); font-weight:800; font-size:12px; box-shadow:0 8px 20px rgba(201,150,47,.5); }
  .rozet span{ font-size:18px; }

  /* alt nav */
  #altbar{ position:fixed; left:0; right:0; bottom:0; z-index:94; display:flex; align-items:flex-end; justify-content:space-around;
    padding:8px 8px calc(8px + env(safe-area-inset-bottom)); background:var(--navbg); backdrop-filter:blur(14px); border-top:1px solid var(--cizgi); }
  #altbar button{ flex:1; background:none; border:none; color:var(--sessiz); font-size:10.5px; font-weight:700; display:flex; flex-direction:column; align-items:center; gap:3px; padding:5px 0; }
  #altbar button span{ font-size:19px; } #altbar button.act{ color:var(--gold); }
  #altbar .qr{ flex:0 0 auto; }
  #altbar .qr .qi{ position:relative; width:64px; height:64px; margin-top:-28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:26px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 10px 24px rgba(139,59,234,.6), 0 0 0 5px var(--navbg); }
  /* PATLAYAN DALGA: 'dokun bana' hissi (iki halka, kaymalı) */
  #altbar .qr .qi::before, #altbar .qr .qi::after{ content:''; position:absolute; inset:0; border-radius:50%; border:2.5px solid rgba(255,255,255,.9); pointer-events:none; animation:qrDalga 2.2s ease-out infinite; }
  #altbar .qr .qi::after{ animation-delay:1.1s; }
  @keyframes qrDalga{ 0%{ transform:scale(1); opacity:.75; } 100%{ transform:scale(2); opacity:0; } }
  .nrozet{ position:absolute; top:-3px; right:calc(50% - 22px); background:#F43F5E; color:#fff; font-size:10px; font-weight:800; min-width:17px; height:17px; border-radius:9px; display:flex; align-items:center; justify-content:center; padding:0 4px; }

  /* ==================== TABLET / GENIS EKRAN ==================== */
  #desk{ display:none; }
  @media(min-width:920px){
    #wrap{ display:none; }
    #desk{ display:flex; min-height:100dvh; }
    #side{ width:270px; flex:0 0 270px; position:sticky; top:0; align-self:flex-start; height:100dvh; padding:26px 20px;
      display:flex; flex-direction:column; gap:6px; border-right:1px solid var(--cizgi); background:var(--card); }
    #side .brand{ margin:4px 4px 22px; }
    #side .nav{ display:flex; flex-direction:column; gap:4px; }
    #side .nav a{ display:flex; align-items:center; gap:13px; padding:13px 15px; border-radius:14px; color:var(--ink); font-size:14.5px; font-weight:600; cursor:pointer; }
    #side .nav a span{ font-size:18px; width:22px; text-align:center; }
    #side .nav a:hover{ background:var(--neutral); }
    #side .nav a.act{ background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; box-shadow:0 8px 20px rgba(139,59,234,.45); }
    #side .sp{ flex:1; }
    #side .sbtn{ display:flex; align-items:center; gap:11px; padding:14px 16px; border-radius:16px; border:none; cursor:pointer; text-align:left; margin-top:10px; }
    #side .sbtn span{ font-size:20px; }
    #side .sbtn b{ display:block; font-size:14px; font-weight:800; } #side .sbtn i{ font-style:normal; font-size:11px; opacity:.85; }
    #side .cagir{ background:linear-gradient(135deg,var(--mor),var(--mavi)); color:#fff; }
    #side .hesap{ background:var(--card2); color:var(--ink); border:1px solid var(--cizgi); }
    #side .dil{ margin-top:14px; background:var(--card2); border:1px solid var(--cizgi); color:var(--ink); padding:12px 15px; border-radius:14px; font-size:13.5px; display:flex; align-items:center; gap:9px; }

    #deskmain{ flex:1; min-width:0; padding:26px 30px 60px; overflow-y:auto; height:100dvh; }
    .hero{ position:relative; border-radius:26px; overflow:hidden; min-height:320px; display:flex; align-items:center; padding:44px 48px;
      background:#241233; border:1px solid var(--cizgi); box-shadow:0 26px 60px -30px rgba(0,0,0,.9); }
    .hero .hbg{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; z-index:0; }
    .hero .hsh{ position:absolute; inset:0; z-index:1;
      background:linear-gradient(100deg, rgba(18,9,18,.96) 0%, rgba(28,13,34,.85) 38%, rgba(28,13,34,.25) 70%, rgba(28,13,34,.05) 100%); }
    .hero .ht{ position:relative; z-index:2; max-width:56%; }
    .hero .scr{ font-family:var(--script); font-size:40px; font-weight:700; color:var(--gold); line-height:.9; text-shadow:0 4px 18px var(--cizgi); }
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
    .dk .tag{ position:absolute; top:11px; left:11px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:var(--gold-ink); font-size:10.5px; font-weight:800; padding:4px 10px; border-radius:20px; }
    .dk .b{ padding:14px 15px 16px; }
    .dk .ad{ font-weight:800; font-size:16px; }
    .dk .alt{ display:flex; align-items:center; justify-content:space-between; margin-top:11px; }
    .dk .fi{ color:var(--gold); font-weight:800; font-size:17px; }
    .dk .art{ width:38px; height:38px; border-radius:12px; border:none; color:#fff; font-size:22px; line-height:1; background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 8px 18px rgba(139,59,234,.5); }

    .qrban{ margin-top:30px; border-radius:24px; padding:32px 36px; display:flex; align-items:center; gap:20px;
      background:linear-gradient(120deg,var(--card),var(--card2)); border:1px solid var(--cizgi); box-shadow:0 20px 44px -24px rgba(0,0,0,.85); }
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
  #sepet, #detay{ z-index:92; }   /* sepet/detay katmanı; alt menü (94) HER ZAMAN üstte kalır */
  /* Alt menü her sayfada SABİT görünsün: sheet alta yaslı kalır ama içerik (butonlar) alt menünün ÜSTÜNde biter (mobil) */
  @media(max-width:919px){
    /* 112px: alt menü barı (~62px) + ortada 28px yukarı taşan robot dahil temiz boşluk.
       !important: taban '#detay .in' / '#sepet .foot' padding kurallari CSS'te DAHA SONRA gelip bunu eziyordu. */
    #detay .in{ padding-bottom:calc(112px + env(safe-area-inset-bottom)) !important; }
    #sepet .foot{ padding-bottom:calc(112px + env(safe-area-inset-bottom)) !important; }
  }
  .ov.acik{ display:flex; }
  .ov-bg{ position:absolute; inset:0; background:rgba(6,4,10,.7); backdrop-filter:blur(4px); }

  /* urun detay */
  #detay{ align-items:flex-end; justify-content:center; }
  @media(min-width:920px){ #detay{ align-items:center; } }
  #detay .kutu{ position:relative; z-index:2; width:100%; max-width:480px; background:linear-gradient(180deg,var(--card),var(--card2)); border:1px solid var(--cizgi);
    border-radius:26px 26px 0 0; overflow:hidden; max-height:92dvh; overflow-y:auto; animation:slideUp .3s cubic-bezier(.2,.8,.2,1); }
  @media(min-width:920px){ #detay .kutu{ border-radius:26px; } }
  #detay .foto{ position:relative; height:240px; background:#1e1024; }
  #detay .foto img{ width:100%; height:100%; object-fit:cover; }
  #detay .foto .em{ width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:96px; }
  #detay .foto .x{ position:absolute; top:14px; right:14px; width:40px; height:40px; border-radius:50%; border:none; background:rgba(0,0,0,.5); color:#fff; font-size:18px; }
  #detay .in{ padding:20px 20px 26px; }
  #detay .in h2{ font-family:var(--serif); font-size:24px; }
  #detay .in .fi{ display:inline-block; margin-top:10px; background:linear-gradient(135deg,var(--gold),var(--gold2)); color:var(--gold-ink); font-weight:800; font-size:17px; padding:5px 15px; border-radius:22px; }
  #detay .in .ac{ color:var(--sessiz); font-size:14px; line-height:1.55; margin-top:14px; }
  #detay .puanbox{ margin-top:18px; padding:15px; border-radius:16px; background:rgba(0,0,0,.22); border:1px solid var(--cizgi); }
  #detay .puanbox .u{ font-size:13px; color:var(--sessiz); }
  #detay .puanbox .u b{ color:var(--gold); }
  #detay .puanver{ display:flex; gap:8px; margin-top:11px; }
  #detay .puanver .s{ font-size:30px; cursor:pointer; filter:grayscale(1) opacity(.5); transition:.12s; }
  #detay .puanver .s.on{ filter:none; transform:scale(1.08); }
  #detay .puanver .s:hover{ transform:scale(1.12); }
  #detay .miktar{ display:flex; align-items:center; gap:12px; margin-top:20px; }
  #detay .miktar > button:not(.ekle){ flex:0 0 auto; width:48px; height:52px; border-radius:14px; border:none; background:var(--card2); color:var(--ink); font-size:22px; line-height:1; display:flex; align-items:center; justify-content:center; }
  #detay .miktar span{ font-size:20px; font-weight:800; min-width:26px; text-align:center; color:var(--ink); }
  #detay .ekle{ width:100%; margin-top:20px; border:none; border-radius:16px; padding:16px; font-weight:800; font-size:15.5px; color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi)); box-shadow:0 10px 24px rgba(139,59,234,.5); }
  /* alt satirdaki Sepete Ekle: qty butonlariyla AYNI yukseklik, metin ortali, tasma yok */
  #detay .miktar .ekle{ flex:1 1 auto; width:auto; height:52px; margin-top:0; padding:0 16px; display:flex; align-items:center; justify-content:center; white-space:nowrap; }
  #detay .ekle:disabled{ opacity:.5; }

  /* sepet sheet */
  #sepet{ align-items:flex-end; justify-content:center; }
  @media(min-width:920px){ #sepet{ align-items:center; } }
  #sepet .kutu{ position:relative; z-index:2; width:100%; max-width:480px; background:linear-gradient(180deg,var(--card),var(--card2)); border:1px solid var(--cizgi);
    border-radius:26px 26px 0 0; max-height:88dvh; display:flex; flex-direction:column; animation:slideUp .3s cubic-bezier(.2,.8,.2,1); }
  @media(min-width:920px){ #sepet .kutu{ border-radius:24px; } }
  #sepet .bar{ display:flex; align-items:center; padding:18px 20px 12px; }
  #sepet .bar b{ font-family:var(--serif); font-size:20px; color:var(--gold); }
  #sepet .bar .x{ margin-left:auto; width:36px; height:36px; border-radius:50%; border:none; background:var(--neutral); color:#fff; font-size:16px; }
  #sepet .liste{ flex:1; overflow-y:auto; padding:0 20px; }
  #sepet .sat{ display:flex; align-items:center; gap:12px; padding:13px 0; border-bottom:1px solid var(--neutral); }
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
  #menu{ background:#120912; }
  html.acik #menu{ background:#F5EFF3; }
  #menu .kutu{ position:relative; z-index:2; width:100%; height:100dvh; overflow-y:auto; display:flex; flex-direction:column; }
  #menu .mbar{ position:sticky; top:0; z-index:3; display:flex; align-items:center; gap:12px; padding:16px 18px;
    background:linear-gradient(135deg,rgba(139,59,234,.3),rgba(51,20,54,.65)); border-bottom:1px solid var(--cizgi); backdrop-filter:blur(10px); }
  #menu .mbar b{ font-family:var(--serif); font-size:19px; color:var(--gold); }
  #menu .mbar .x{ margin-left:auto; background:rgba(255,255,255,.16); color:#fff; border:none; font-size:13.5px; font-weight:800; padding:9px 15px; border-radius:22px; }
  #menu .mbody{ padding:6px 16px 100px; max-width:1000px; margin:0 auto; width:100%; }   /* alt menü için altta boşluk */
  #menu .kat{ display:flex; align-items:center; gap:10px; font-family:var(--serif); font-size:21px; font-weight:800; margin:24px 2px 12px; }
  #menu .kat span{ font-size:24px; } #menu .kat::after{ content:''; flex:1; height:2px; margin-left:6px; border-radius:2px; background:linear-gradient(90deg,var(--gold),transparent); }
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

  #toast{ position:fixed; left:50%; transform:translateX(-50%); bottom:100px; z-index:95; background:linear-gradient(135deg,var(--card),var(--card2)); color:var(--ink);
    border:1px solid var(--cizgi); padding:13px 18px; border-radius:16px; font-size:13.5px; font-weight:700; box-shadow:0 14px 34px rgba(0,0,0,.35); max-width:88%; text-align:center; opacity:0; transition:.25s; pointer-events:none; }

  @keyframes up{ to{ opacity:1; transform:none; } }
  @keyframes slideUp{ from{ transform:translateY(40px); opacity:.4; } to{ transform:none; opacity:1; } }

  /* ============ LUKS TEMA (renk kartelasindan gelir) : siyah zemin + palet aksani + altin detay ============ */
  html,body{ background:#0b090c;
    background-image:
      radial-gradient(920px 640px at 90% -8%, var(--glow), transparent 58%),
      radial-gradient(680px 520px at 4% 106%, var(--glow), transparent 60%),
      radial-gradient(1200px 900px at 60% 0%, #1b1620 0%, #130f15 46%, #0b090c 100%); }
  /* karsilama + bugune-ozel: koyu + altin ince kenar (tum paletlerde sabit luks) */
  .hg{ background:linear-gradient(160deg, var(--gold-bg), var(--card)); border-color:var(--gold-bd); }
  .ozel{ background:linear-gradient(135deg,var(--mor),var(--mavi)); border-color:var(--gold-bd); }
  .ozel .ic b{ color:#fff; } .ozel .ic p{ color:rgba(255,255,255,.9); }
  #menu .mbar{ background:linear-gradient(135deg, rgba(0,0,0,.42), var(--card2)); }
  .chip .ci{ background:var(--neutral); }
  /* aksan butonlarinin YAZI rengi palete gore (altin->koyu, koyu renkler->beyaz) */
  .ara button, .hero .kesfet, #detay .ekle, .pk .art, .dk .art, #menu .mk .art, .qrban .okut,
  #altbar .qr .qi, #side .cagir, #deskcart, .chip.act .cn, #side .nav a.act{ color:var(--ana-ink) !important; }
  .chip.act{ color:var(--ana-ink); } .chip.act .ci{ background:rgba(255,255,255,.20); }

  /* ==================== YÜZEN AI ASISTAN BALONCUĞU + PANEL ==================== */
  #aiFab{ position:fixed; z-index:85; right:16px; bottom:calc(88px + env(safe-area-inset-bottom));
    width:60px; height:60px; border-radius:50%; border:none; cursor:pointer; color:#fff; font-size:27px;
    background:linear-gradient(135deg,#8B3BEA,#6D28D9); display:flex; align-items:center; justify-content:center;
    box-shadow:0 12px 28px rgba(124,58,237,.6); }
  #aiFab::before{ content:''; position:absolute; inset:-5px; border-radius:50%; border:2px solid #A855F7;
    opacity:.55; animation:aiPulse 2s ease-out infinite; pointer-events:none; }
  @keyframes aiPulse{ 0%{ transform:scale(.92); opacity:.55; } 100%{ transform:scale(1.55); opacity:0; } }
  @media(min-width:920px){ #aiFab{ right:26px; bottom:104px; width:66px; height:66px; font-size:30px; } }

  /* Ortada YÜZEN robot paneli (kutu yok, şeffaf) — menü arkada görünür; içinde robot ikonu + yazı */
  #aiPanel{ position:fixed; z-index:96; display:none; flex-direction:column; overflow:visible;
    left:50%; top:50%; transform:translate(-50%,-50%); width:330px; max-width:92vw; height:470px; max-height:86dvh;
    background:transparent; border:none; box-shadow:none; }
  #aiPanel.acik{ display:flex; }
  @media(min-width:920px){ #aiPanel{ width:350px; height:490px; } }
  #aiPanel .ai-x{ position:absolute; top:-2px; right:-2px; z-index:3; width:34px; height:34px; border-radius:50%; border:none; background:rgba(20,10,22,.72); color:#fff; font-size:16px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,.4); }
  #aiFrameWrap{ flex:1; min-height:0; display:flex; }
  @media(max-width:919px){ #aiFab{ display:none !important; } }   /* telefonda giriş = alt menü robotu */
  /* Alt menü + masaüstü yüzen robot renk yankısı: MOR = AI konuşuyor, YEŞİL = sıra sende */
  #altbar .qr .qi.ai, #aiFab.ai{ background:linear-gradient(135deg,#8B3BEA,#6D28D9) !important; }
  #altbar .qr .qi.dinle, #aiFab.dinle{ background:linear-gradient(135deg,#16A34A,#22C55E) !important; }
  /* Asistan durum hapı: alt barın hemen üstünde, ORTA DEĞİL alt-hizalı, sadece asistan açıkken */
  #asbar{ position:fixed; left:50%; transform:translateX(-50%) translateY(10px); bottom:calc(102px + env(safe-area-inset-bottom));
    z-index:96; max-width:86%; padding:9px 16px; border-radius:20px; font-size:13px; font-weight:700; text-align:center;
    background:linear-gradient(135deg,#2a1731,#160a1a); color:#fff; border:1px solid rgba(255,255,255,.14); box-shadow:0 12px 30px rgba(0,0,0,.5);
    opacity:0; pointer-events:none; transition:opacity .2s, transform .2s; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  #asbar.acik{ opacity:1; transform:translateX(-50%) translateY(0); }
  @media(min-width:920px){ #asbar{ bottom:26px; } }
  /* Global dil seçici dropdown */
  #dilMenu{ position:fixed; top:54px; right:12px; z-index:100; display:none; flex-direction:column; gap:2px; padding:6px;
    background:var(--card); border:1px solid var(--cizgi); border-radius:14px; box-shadow:0 16px 40px rgba(0,0,0,.45); max-height:72vh; overflow-y:auto; }
  #dilMenu.acik{ display:flex; }
  #dilMenu button{ display:flex; align-items:center; gap:10px; background:none; border:none; color:var(--ink); font-size:14px; font-weight:600; padding:9px 14px; border-radius:10px; cursor:pointer; white-space:nowrap; text-align:left; }
  #dilMenu button.act{ background:var(--gold-bg); color:var(--gold); }
  @media(min-width:920px){ #dilMenu{ top:auto; bottom:120px; left:20px; right:auto; } }
</style>
</head>
<body>

<!-- ==================== TELEFON ==================== -->
<div id="wrap">
  <header>
    <button class="modbtn" id="modBtn" onclick="modDegistir()" aria-label="Koyu/Açık">🌙</button>
    <span class="brand"><span class="toque">👨‍🍳</span><span class="bt"><b>{{ $sube->ad ?? 'ResteOS' }}</b><i>RESTORAN</i></span></span>
    <button class="trbtn" id="trbtn" onclick="dilMenuAc(event)">TR ▾</button>
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

  <div class="bbas"><b id="popBas">Popüler Lezzetler</b><a onclick="menuAc()">Tümünü Gör →</a></div>
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
    <button class="qr" onclick="asistanAc()"><div class="qi">🤖</div></button>
    <button onclick="cagir('garson')"><span>🔔</span>Çağır</button>
    <button onclick="hesapOde()"><span>💳</span>Öde</button>
  </nav>
</div>

<!-- ==================== TABLET / GENIS EKRAN ==================== -->
<div id="desk">
  <aside id="side">
    <div style="display:flex;align-items:center;gap:10px;margin:4px 4px 22px">
      <span class="brand" style="margin:0"><span class="toque">👨‍🍳</span><span class="bt"><b>{{ $sube->ad ?? 'ResteOS' }}</b><i>RESTORAN</i></span></span>
      <button class="modbtn" id="modBtn2" onclick="modDegistir()" style="margin-left:auto" aria-label="Koyu/Açık">🌙</button>
    </div>
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
    <button class="sbtn hesap" onclick="hesapOde()"><span>💳</span><span><b>Hesabı Öde</b><i>Online öde ya da garsondan iste</i></span></button>
    <button class="sbtn cagir" onclick="asistanAc()"><span>🤖</span><span><b>Yapay Zekâ Asistan</b><i>Ürün öner, soru sor, yardım al</i></span></button>
    <div class="dil" id="dilDesk" onclick="dilMenuAc(event)">🌐 Türkçe ▾</div>
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

    <div class="bbas" style="margin-top:30px"><b id="dgridBas" style="font-size:24px">Öne Çıkanlar</b><a onclick="menuAc()">Tümünü Gör →</a></div>
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

<!-- ==================== AI ASISTAN: SADECE alt robot ikonu (iframe/panel YOK, orta bos) ==================== -->
<button id="aiFab" onclick="asistanAc()" aria-label="Yapay Zekâ Asistan">🤖</button>
<div id="asbar"><span id="asbar-t">Dokunun, konuşun</span></div>

<div id="dilMenu"></div>
<div id="toast"></div>

<script>
const MASA = @json($masa->id ?? 0);
const SUBE_AD = @json($sube->ad ?? 'Restoran');
let _data = [];            // kategoriler (Türkçe)
let _urun = {};            // urun_id -> urun (detay/sepet icin)
let _menuGecici=null, _dataDil={}, _urunDil={};   // çok dilli: çevrilmiş menü önbelleği (dil -> kategoriler)
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
  document.getElementById('menu').classList.remove('acik'); document.getElementById('detay').classList.remove('acik');  // acik diger katmanlari kapat
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
  document.getElementById('sepet').classList.remove('acik'); document.getElementById('detay').classList.remove('acik');  // acik diger katmanlari kapat
  const body=document.getElementById('menu-body'); body.innerHTML='';
  document.getElementById('menu-bas').textContent = filtre ? filtre.charAt(0).toLocaleUpperCase('tr')+filtre.slice(1) : 'Menü';
  let kats=_menuGecici||_data;   // çok dilli: asistan çevrilmiş menü verdiyse onu göster
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
  document.getElementById('menu').classList.add('acik'); menuGecmisEkle();
}
function menuKapat(){ document.getElementById('menu').classList.remove('acik'); if(history.state&&history.state.ov==='menu'){ history.back(); } }
function menuGecmisEkle(){ if(!(history.state&&history.state.ov==='menu')){ try{ history.pushState({ov:'menu'},''); }catch(e){} } }
// Tarayici GERI tusu: acik menu katmanini kapat (siteden dusme)
window.addEventListener('popstate', function(){ var m=document.getElementById('menu'); if(m&&m.classList.contains('acik')) m.classList.remove('acik'); });
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
  document.getElementById('menu').classList.add('acik'); menuGecmisEkle();
}

/* ---- garson/hesap ---- */
async function cagir(tip){
  try{ await fetch('/api/qr/garson-cagir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA,tip})}); }catch(e){}
  toast(tip==='hesap'?'💳 Hesap isteğiniz iletildi, birazdan geliyoruz.':'🔔 Garson çağrıldı, birazdan yanınızdayız.');
}

/* ---- AI asistan: AYNI sayfada YÜZEN panel (iframe embed) — ayrı sayfaya gitmez ---- */
let _asAktif=false;
/* ===== AI ASISTAN — SAYFA İÇİ ses motoru (iframe YOK: iOS mikrofonu AYNI sayfada dokunmayı ister).
   Orta panel/orb yok; SADECE alt robot ikonu + küçük durum hapı. ===== */
const _asbar=document.getElementById('asbar');
function asDurum(t, goster){ if(!_asbar) return; if(t!=null){ const el=document.getElementById('asbar-t'); if(el) el.textContent=t; } if(goster!==false) _asbar.classList.add('acik'); }
function asGizle(){ if(_asbar) _asbar.classList.remove('acik'); }

// iOS ses kilidi: ilk dokunuşta sessiz ses çal → sonraki async TTS'ler çalışır
let _sesCalar=new Audio(), _sesAcildi=false;
function sesUnlock(){ if(_sesAcildi) return; try{ _sesCalar.src='data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQAAAAA='; const p=_sesCalar.play(); if(p&&p.then) p.then(()=>{_sesAcildi=true;}).catch(()=>{}); }catch(_){} }
function sesDurdur(){ try{ _sesCalar.pause(); }catch(_){} }
const _isAndroid=/android/i.test(navigator.userAgent);
function seseHazirla(t){ return (t||'').replace(/[^\p{L}\p{N}\s.,!?%:₺'"()-]/gu,'').trim().replace(/(\d)\.(\d{3})(?=\D|$)/g,'$1$2').replace(/₺\s*(\d+)/g,'$1 lira').replace(/(\d+)\s*(?:₺|tl)\b/gi,'$1 lira').replace(/₺/g,' lira'); }

// Mikrofon: TEK AudioContext sürekli açık; her tur kayıt bayrağı (iOS şartı). Ham PCM (LINEAR16 16k) gönderir.
let _stream=null,_actx=null,_proc=null,_srSample=48000;
let _rec={active:false,chunks:[],started:false,silence:0,elapsed:0,resolve:null,pre:null};
async function micHazir(){
  if(_actx && _stream && _stream.active){ try{ await _actx.resume(); }catch(_){} return true; }
  try{
    if(!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) return false;
    _stream=await navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,autoGainControl:true}});
    _actx=new (window.AudioContext||window.webkitAudioContext)(); await _actx.resume();
    _srSample=_actx.sampleRate||48000;
    const src=_actx.createMediaStreamSource(_stream); _proc=_actx.createScriptProcessor(4096,1,1);
    _proc.onaudioprocess=(e)=>{
      if(!_rec.active) return;
      const d=e.inputBuffer.getChannelData(0); let s=0; for(let i=0;i<d.length;i++) s+=d[i]*d[i];
      const rms=Math.sqrt(s/d.length); _rec.elapsed+=d.length/_srSample;
      if(rms>0.010){ if(!_rec.started){ _rec.started=true; if(_rec.pre){ _rec.pre.forEach(p=>_rec.chunks.push(p)); _rec.pre=null; } } _rec.silence=0; _rec.chunks.push(new Float32Array(d)); }
      else if(_rec.started){ _rec.silence+=d.length/_srSample; _rec.chunks.push(new Float32Array(d)); }
      else { (_rec.pre=_rec.pre||[]).push(new Float32Array(d)); if(_rec.pre.length>3) _rec.pre.shift(); }
      if(_rec.started && _rec.silence>1.2) _recBit(true);
      else if(!_rec.started && _rec.elapsed>7) _recBit(false);
      else if(_rec.elapsed>14) _recBit(_rec.started);
    };
    src.connect(_proc); _proc.connect(_actx.destination); return true;
  }catch(e){ return false; }
}
function _recBit(gonder){ if(!_rec.active) return; _rec.active=false; const chunks=_rec.chunks,r=_rec.resolve; _rec.resolve=null; if(r) r((gonder&&chunks.length)?pcmRaw(chunks,_srSample):null); }
function turKaydet(){ return new Promise(async(resolve)=>{ if(!await micHazir()){ resolve(null); return; } try{ await _actx.resume(); }catch(_){} _rec={active:true,chunks:[],started:false,silence:0,elapsed:0,resolve:resolve,pre:null}; setTimeout(()=>{ if(_rec.active) _recBit(_rec.started); },16000); }); }
function pcmRaw(chunks,sr){ let len=0; for(const c of chunks) len+=c.length; const all=new Float32Array(len); let o=0; for(const c of chunks){ all.set(c,o); o+=c.length; } const oran=Math.max(1,sr/16000); const yeniLen=Math.floor(all.length/oran); const pcm=new Int16Array(yeniLen); for(let i=0;i<yeniLen;i++){ let v=all[Math.round(i*oran)]||0; v=Math.max(-1,Math.min(1,v)); pcm[i]=v<0?v*0x8000:v*0x7FFF; } return new Blob([pcm.buffer],{type:'application/octet-stream'}); }

// Konuş (Google TTS /api/tts). Sıra tabanlı: tam konuşur, sonra dinler.
let konusuyor=false,_konusBit=null,aktifAsDil='tr';
function konusKes(){ sesDurdur(); if(_konusBit){ const b=_konusBit; _konusBit=null; b(); } }
function konus(t){ return new Promise((resolve)=>{
  const temiz=seseHazirla(t); if(!temiz){ resolve(); return; }
  konusuyor=true; robotHal('ai'); asDurum(t);
  let bitti=false; const bit=()=>{ if(bitti) return; bitti=true; _konusBit=null; konusuyor=false; sesDurdur(); robotHal(sohbetAktif?'dinle':'bekle'); resolve(); };
  _konusBit=bit; const emniyet=setTimeout(bit, Math.min(22000,3000+temiz.length*95));
  fetch('/api/tts',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({metin:temiz,masa:MASA,dil:aktifAsDil})})
    .then(r=>r.json()).then(j=>{ if(bitti) return;
      if(j.basarili&&j.url){ sesDurdur(); _sesCalar.src=j.url; _sesCalar.onended=()=>{clearTimeout(emniyet);bit();}; _sesCalar.onerror=()=>{clearTimeout(emniyet);bit();}; const p=_sesCalar.play(); if(p&&p.catch) p.catch(()=>{clearTimeout(emniyet);bit();}); }
      else if(_isAndroid && window.speechSynthesis){ clearTimeout(emniyet); try{ const u=new SpeechSynthesisUtterance(temiz); u.lang=(aktifAsDil==='tr')?'tr-TR':aktifAsDil; u.onend=bit; u.onerror=bit; speechSynthesis.speak(u); }catch(_){ bit(); } }
      else { clearTimeout(emniyet); bit(); } }).catch(()=>{ clearTimeout(emniyet); bit(); });
}); }
async function asCevir(tr){ if(aktifAsDil==='tr'||!tr) return tr; try{ const r=await fetch('/api/qr/cevir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({metin:tr,hedef:aktifAsDil})}); const j=await r.json(); return j.metin||tr; }catch(_){ return tr; } }
async function sistemKonus(tr){ await konus(await asCevir(tr)); }

// STT: bir tur dinle -> metin
let _sttLimit=false;
async function dinleSunucu(){
  robotHal('dinle'); asDurum('🎧 Sizi dinliyorum, buyurun…');
  const wav=await turKaydet();
  if(!wav){ asDurum('Sizi duyamadım. Biraz yüksek/net konuşun.'); return ''; }
  asDurum('… anlıyorum');
  try{
    const fd=new FormData(); fd.append('ses',wav,'ses.pcm'); fd.append('masa',MASA); fd.append('dil',aktifAsDil);
    const r=await fetch('/api/qr/stt',{method:'POST',body:fd}); const j=await r.json();
    if(j.limit){ _sttLimit=true; return ''; }
    if(j.kod && j.kod!==200){ asDurum('Ses tanıma hatası (HTTP '+j.kod+')'); return ''; }
    const m=(j.metin||'').trim();
    if(!m){ asDurum('Sizi net duyamadım, tekrar eder misiniz?'); } else { asDurum('“'+m+'”'); }
    return m;
  }catch(e){ asDurum('Bağlantı hatası, tekrar deneyin.'); return ''; }
}

// Sohbet döngüsü (sıra tabanlı) — alt robota dokun: başlat / AI konuşurken kes / dinlerken kapat
let sohbetAktif=false, _sonTik=0, _ilkSelam=false;
function asistanAc(){
  sesUnlock();
  const simdi=(window.performance&&performance.now)?performance.now():(+new Date()); if(simdi-_sonTik<600) return; _sonTik=simdi;
  if(!sohbetAktif){ basla(); return; }
  if(konusuyor){ konusKes(); return; }
  sohbetKapat();
}
async function basla(){
  aktifAsDil=(window.sayfaDil||'tr');
  sohbetAktif=true; robotHal('dinle'); asDurum('Bağlanıyor…');
  const izin=await micHazir();
  if(!izin){ asDurum('Mikrofon izni gerekli. Menüden yazarak da sorabilirsiniz.'); sohbetAktif=false; robotHal('bekle'); setTimeout(asGizle,3500); return; }
  await sistemKonus(_ilkSelam?'Buyurun, sizi dinliyorum.':('Hoş geldiniz! Ben '+SUBE_AD+' masa asistanınızım. Size nasıl yardımcı olabilirim?')); _ilkSelam=true;
  let bos=0;
  while(sohbetAktif){
    const c=await dinleSunucu();
    if(!sohbetAktif) break;
    if(_sttLimit){ _sttLimit=false; await sistemKonus('Şu an sesli asistan çok yoğun. Menüden yazarak devam edebilirsiniz.'); break; }
    if(!c){ bos++; if(bos>=3){ await sistemKonus('İstediğinizde robota tekrar dokunun, buradayım.'); break; } continue; }
    bos=0;
    if(/^(kapat|kapan|görüşürüz|hoşça kal)\b/i.test(c)){ await sistemKonus('Tabii, kapatıyorum. Afiyet olsun!'); break; }
    const cevap=await sunucudanCevap(c);
    if(cevap) await konus(cevap);
  }
  sohbetAktif=false; robotHal('bekle'); setTimeout(()=>{ if(!sohbetAktif&&!konusuyor) asGizle(); },2500);
}
function sohbetKapat(){ sohbetAktif=false; konusKes(); try{ _rec.active=false; }catch(_){} robotHal('bekle'); asDurum('Görüşmek üzere 👋'); setTimeout(asGizle,1500); }

// Sunucuya sor + ANA ekranı güncelle; seslendirilecek metni döner
async function sunucudanCevap(soru){
  asDurum('🤖 Düşünüyorum…');
  try{
    const r=await fetch('/api/qr/asistan',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:new URLSearchParams({masa:MASA,soru,baglam:window.sonUrun||'',dil:aktifAsDil})});
    const j=await r.json();
    if(j.urun_baglam) window.sonUrun=j.urun_baglam; else if(Array.isArray(j.kategoriler)||(Array.isArray(j.kartlar)&&j.kartlar.length>1)) window.sonUrun='';
    if(Array.isArray(j.kartlar) && j.kartlar.length){ await asKartGoster(j.kartlar, j.baslik); }
    else if(Array.isArray(j.kategoriler) && j.kategoriler.length){ asistanMenuGoster(aktifAsDil!=='tr'?aktifAsDil:null); }
    if((j.aksiyon==='sepet_ekle'||j.aksiyon==='sepet_ayarla') && Array.isArray(j.eklenen)) asSepetEkle(j.eklenen);
    if(j.aksiyon==='garson_cagir') cagir(j.tip||'garson');
    return (j.seslendir===false)?'':(j.cevap||'Bir sorun oldu, tekrar dener misiniz?');
  }catch(e){ return 'Bağlantı hatası, tekrar dener misiniz?'; }
}
async function asKartGoster(kartlar, baslik){
  let bak=_urun;
  if(aktifAsDil && aktifAsDil!=='tr'){ await menuVeriDil(aktifAsDil); if(_urunDil[aktifAsDil]) bak=_urunDil[aktifAsDil]; }
  const urunler=kartlar.map(k=>(k&&k.urun_id&&bak[k.urun_id])?bak[k.urun_id]:k).filter(Boolean);
  if(urunler.length===1){ detayAc(urunler[0]); return; }
  if(urunler.length) asistanKatmanGoster(urunler, baslik);
}
/* Asistanın getirdiği ürünleri ÖNDEKİ menü katmanında göster (ekran net değişsin) */
function asistanKatmanGoster(urunler, baslik){
  const body=document.getElementById('menu-body'); if(!body) return;
  document.getElementById('menu-bas').textContent = baslik || '🤖 Önerilenler';
  const bul=uid=>urunler.find(x=>String(x.urun_id)===String(uid)) || _urun[uid];
  let h='<div class="mgrid" style="margin-top:16px">';
  urunler.forEach(u=>{ const tuk=u.etiket==='Tükendi';
    h+=`<div class="mk" data-uid="${u.urun_id}"><div class="g">${gorselHtml(u,'em')}${u.etiket&&!tuk?`<span class="tag">${esc(u.etiket)}</span>`:''}</div>`
      +`<div class="b"><div class="ad">${esc(u.ad)}</div><div class="ac">${esc(u.aciklama||'')}</div>`
      +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" data-uid="${u.urun_id}" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div></div>`; });
  h+='</div>'; body.innerHTML=h;
  body.querySelectorAll('.mk').forEach(el=>el.addEventListener('click',()=>{ const u=bul(el.dataset.uid); if(u) detayAc(u); }));
  body.querySelectorAll('.mk .art').forEach(b=>b.addEventListener('click',ev=>{ ev.stopPropagation(); const u=bul(b.dataset.uid); if(u) sepeteEkle(u,1,true); }));
  document.getElementById('sepet').classList.remove('acik'); document.getElementById('detay').classList.remove('acik');  // acik katmanlari kapat
  document.getElementById('menu').classList.add('acik'); menuGecmisEkle();
}
function asSepetEkle(eklenen){ let n=0; eklenen.forEach(e=>{ const u=_urun[e.urun_id]; if(u){ sepeteEkle(u, e.adet||1, false); n++; } }); if(n) toast('🛒 Siparişiniz sepete eklendi'); }
function robotHal(hal){
  [document.querySelector('#altbar .qr .qi'), document.getElementById('aiFab')].forEach(q=>{ if(q){ q.classList.remove('ai','dinle'); if(hal==='ai') q.classList.add('ai'); else if(hal==='dinle') q.classList.add('dinle'); } });
}

/* ============ GLOBAL DİL (üst seçici): menü + arayüz + AI hepsi seçilen dile ============ */
const DILAD={tr:'🇹🇷 Türkçe',en:'🇬🇧 English',ar:'🇸🇦 العربية',de:'🇩🇪 Deutsch',ru:'🇷🇺 Русский',es:'🇪🇸 Español',fr:'🇫🇷 Français',nl:'🇳🇱 Nederlands',it:'🇮🇹 Italiano',uk:'🇺🇦 Українська'};
window.sayfaDil=(function(){ try{ return localStorage.getItem('qr_dil')||'tr'; }catch(e){ return 'tr'; } })();
let _cvOrijinal=null;
function dilMenuAc(ev){ if(ev) ev.stopPropagation(); const m=document.getElementById('dilMenu'); m.innerHTML=Object.keys(DILAD).map(k=>`<button class="${k===window.sayfaDil?'act':''}" onclick="dilSecGlobal('${k}')">${DILAD[k]}</button>`).join(''); m.classList.toggle('acik'); }
document.addEventListener('click', ()=>{ const m=document.getElementById('dilMenu'); if(m) m.classList.remove('acik'); });
function dilEtiketGuncelle(dil){ const tb=document.getElementById('trbtn'); if(tb) tb.textContent=dil.toUpperCase()+' ▾'; const dd=document.getElementById('dilDesk'); if(dd) dd.textContent='🌐 '+(DILAD[dil]||'🇹🇷 Türkçe').replace(/^\S+\s/,'')+' ▾'; }
async function dilSecGlobal(dil){
  const m=document.getElementById('dilMenu'); if(m) m.classList.remove('acik');
  if(!DILAD[dil]) return;
  window.sayfaDil=dil; try{ localStorage.setItem('qr_dil',dil); }catch(e){}
  dilEtiketGuncelle(dil);
  aktifAsDil=dil;   // asistan da bu dilde dinlesin/konuşsun
  await sayfaCevir(dil);
}
function _cvEl(el){ const tn=[...el.childNodes].filter(n=>n.nodeType===3 && n.textContent.trim()); return tn.length ? tn.map(n=>n.textContent).join(' ').trim() : el.textContent.trim(); }
function _cvSet(el,metin){ const tn=[...el.childNodes].filter(n=>n.nodeType===3 && n.textContent.trim()); if(tn.length){ tn[0].textContent=' '+metin+' '; for(let i=1;i<tn.length;i++) tn[i].textContent=''; } else el.textContent=metin; }
async function cevirCoklu(metinler,dil){
  if(dil==='tr') return metinler;
  try{ const r=await fetch('/api/qr/cevir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({metinler:JSON.stringify(metinler),hedef:dil})}); const j=await r.json(); return (j.metinler&&j.metinler.length===metinler.length)?j.metinler:metinler; }catch(_){ return metinler; }
}
async function sayfaCevir(dil){
  const seller=['.hg h1','.hg p','.bbas b','.bbas a','.ozel .ic b','.ozel .ic p','.sayac i','#altbar button','#menu .mbar b',
    '#side .nav a','#side .sbtn b','#side .sbtn i','.hero .scr','.hero .big','.hero .sub','.hero .kesfet','.qrban .qt b','.qrban .qt p','.qrban .okut'];
  if(!_cvOrijinal){ _cvOrijinal=[]; seller.forEach(s=>document.querySelectorAll(s).forEach(el=>_cvOrijinal.push({el,m:_cvEl(el)}))); const ara=document.getElementById('ara'); if(ara) _cvOrijinal.push({el:ara,ph:ara.getAttribute('placeholder')||''}); }
  if(dil==='tr'){ _cvOrijinal.forEach(o=>{ if(o.ph!==undefined) o.el.setAttribute('placeholder',o.ph); else _cvSet(o.el,o.m); }); }
  else{
    const metinler=_cvOrijinal.map(o=> o.ph!==undefined ? o.ph : o.m);
    const cev=await cevirCoklu(metinler,dil);
    _cvOrijinal.forEach((o,i)=>{ const t=cev[i]||metinler[i]; if(o.ph!==undefined) o.el.setAttribute('placeholder',t); else _cvSet(o.el,t); });
  }
  const kats=await menuVeriDil(dil);
  _data=kats; _urun={}; _data.forEach(k=>(k.kartlar||[]).forEach(u=>{ u._kat=k.ad; if(u.urun_id) _urun[u.urun_id]=u; }));
  try{ chipleriCiz(); }catch(_){} try{ populerCiz('*'); }catch(_){} try{ dgridCiz(); }catch(_){}
}
/* ---- Çok dilli menü: seçili dilde menüyü sunucudan çek (ONBELLEKLI, bir kez) ---- */
async function menuVeriDil(dil){
  if(!dil || dil==='tr') return _data;
  if(_dataDil[dil]) return _dataDil[dil];
  try{
    const r=await fetch('/api/qr/menu-tam?masa='+MASA+'&dil='+dil); const j=await r.json();
    if(j.ok && Array.isArray(j.kategoriler)){
      _dataDil[dil]=j.kategoriler; const m={}; j.kategoriler.forEach(k=>(k.kartlar||[]).forEach(u=>{ u._kat=k.ad; if(u.urun_id) m[u.urun_id]=u; })); _urunDil[dil]=m;
      return j.kategoriler;
    }
  }catch(_){}
  return _data;
}
async function asistanMenuGoster(dil){
  const kats = await menuVeriDil(dil);
  _menuGecici = kats; menuAc(); _menuGecici = null;
}
/* ---- Asistan panelinden gelen menü -> ANA ekranda (seçili dilde) göster ---- */
window.addEventListener('message', async (e)=>{
  const d=e.data||{};
  if(d.resto==='durum'){ robotHal(d.hal); return; }                                  // alt menü robot rengi yankısı
  if(d.resto==='kartlar' && Array.isArray(d.kartlar) && d.kartlar.length){
    let bak=_urun;
    if(d.dil && d.dil!=='tr'){ await menuVeriDil(d.dil); if(_urunDil[d.dil]) bak=_urunDil[d.dil]; }
    const urunler=d.kartlar.map(k=>(k && k.urun_id && bak[k.urun_id]) ? bak[k.urun_id] : k).filter(Boolean);
    if(urunler.length===1) detayAc(urunler[0]);
    else if(urunler.length) asistanUrunGoster(urunler);
  } else if(d.resto==='kategoriler' && Array.isArray(d.kategoriler) && d.kategoriler.length){
    asistanMenuGoster(d.dil);
  }
});
function asistanUrunGoster(list){
  const pb=document.getElementById('popBas'); if(pb) pb.textContent='🤖 Asistanın Önerileri';
  const db=document.getElementById('dgridBas'); if(db) db.textContent='🤖 Asistanın Önerileri';
  const pop=document.getElementById('pop');
  if(pop){ pop.innerHTML=''; list.forEach((u,i)=>{ const tuk=u.etiket==='Tükendi'; const c=document.createElement('div'); c.className='pk'; c.style.animationDelay=(i*40)+'ms';
    c.innerHTML=`<div class="g">${gorselHtml(u,'em')}</div>${u.etiket&&!tuk?`<span class="tag">${esc(u.etiket)}</span>`:''}${tuk?'<div class="tuk">Tükendi</div>':''}<div class="glass"></div>`
      +`<div class="b"><div class="ad">${esc(u.ad)}</div>${yildizHtml(u)}`
      +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div>`;
    c.querySelector('.art').addEventListener('click',ev=>{ ev.stopPropagation(); if(!tuk) sepeteEkle(u,1,true); });
    c.addEventListener('click',()=>detayAc(u)); pop.appendChild(c); }); }
  const dg=document.getElementById('dgrid');
  if(dg){ dg.innerHTML=''; list.forEach(u=>{ const tuk=u.etiket==='Tükendi'; const c=document.createElement('div'); c.className='dk';
    c.innerHTML=`<div class="g">${gorselHtml(u,'em')}${u.etiket&&!tuk?`<span class="tag">${esc(u.etiket)}</span>`:''}${tuk?'<div class="tuk">Tükendi</div>':''}</div>`
      +`<div class="b"><div class="ad">${esc(u.ad)}</div><div style="margin-top:7px">${yildizHtml(u)}</div>`
      +`<div class="alt"><span class="fi">${esc(u.fiyat_yazi||'')}</span><button class="art" ${tuk?'disabled style=opacity:.4':''}>+</button></div></div>`;
    c.querySelector('.art').addEventListener('click',ev=>{ ev.stopPropagation(); if(!tuk) sepeteEkle(u,1,true); });
    c.addEventListener('click',()=>detayAc(u)); dg.appendChild(c); }); }
  try{ const dm=document.getElementById('deskmain'); if(dm) dm.scrollTo({top:0,behavior:'smooth'}); }catch(_){}
  try{ toast('🤖 Önerilenleri menüde gösterdim'); }catch(_){}
}
function asistanKapat(){ sohbetKapat(); }   // geriye dönük uyum
async function hesapOde(){
  try{
    const r = await fetch('/api/qr/ode-baslat',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({masa:MASA})});
    const j = await r.json();
    if(j.ok && j.ode_url){ location.href = j.ode_url; return; }
    await cagir('hesap'); toast(j.hata ? ('ℹ️ '+j.hata+' — garson çağrıldı.') : '💳 Hesap isteğiniz iletildi.');
  }catch(e){ cagir('hesap'); }
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

// KOYU/ACIK MOD — musteri kendi cihazinda secer, tercihi hatirlanir (localStorage)
function modUygula(mod){
  const acik = mod==='acik';
  document.documentElement.classList.toggle('acik', acik);
  document.querySelectorAll('#modBtn,#modBtn2').forEach(b=> b.textContent = acik ? '☀️' : '🌙');
}
function modDegistir(){
  const yeni = document.documentElement.classList.contains('acik') ? 'koyu' : 'acik';
  try{ localStorage.setItem('qr_mod', yeni); }catch(e){}
  modUygula(yeni);
}
(function(){ let m='{{ $mod ?? "koyu" }}'; try{ m=localStorage.getItem('qr_mod')||m; }catch(e){} modUygula(m); })();

// Sayfa acilinca AI asistan kutusu OTOMATIK acilir ve (autostart ile) konusmaya baslar.
window.addEventListener('load', async ()=>{ await yukle(); sayac(); if(window.sayfaDil && window.sayfaDil!=='tr'){ dilEtiketGuncelle(window.sayfaDil); sayfaCevir(window.sayfaDil); } });
</script>
</body>
</html>

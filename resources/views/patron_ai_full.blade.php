<!doctype html>
<html lang="tr" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Patron AI — {{ $subeAd ?? 'ResteOS' }}</title>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
@php
  $adK = $ad ? trim(explode(' ', trim($ad))[0]) : null;
  $saat = (int) now()->format('H');
  $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $tip = $tip ?? 'sabah';
  $v = $brief['veri'] ?? [];
  $tl = fn ($n) => number_format((float) ($n ?? 0), 0, ',', '.') . '₺';
  $bold = fn ($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
  $analizL = $analiz ?? [];
  $denetimL = $denetim ?? [];
  $aiVar = $aiVar ?? false; $aiSoguk = $aiSoguk ?? false;
  $kayipTop = array_sum($v['kayip'] ?? []);
  $ciroY = (float) ($v['ciro_yuzde'] ?? 0);
  $krit = (int) ($v['kritik_sayi'] ?? 0);
  $ciroR = !empty($v['kiyas_etiket']) ? ($ciroY >= 5 ? '#2FBE94' : ($ciroY <= -10 ? '#F5576C' : ($ciroY < 0 ? '#F6A623' : '#5B8DEF'))) : '#5B8DEF';
  $verdict = ($aiOzet ?? null) ?: ($brief['metin'] ?? ($aiSoguk ? 'AI değerlendirmen hazırlanıyor…' : 'Bugün için özet bulunmuyor.'));
  $islerL = $isler['isler'] ?? [];
  $onayTop = (int) ($onayOp ?? 0) + (int) ($onaySa ?? 0);
  $depVd = ['iyi'=>['İYİ','#2FBE94',90],'notr'=>['NÖTR','#8A8CA8',60],'dikkat'=>['DİKKAT','#F6A623',42],'risk'=>['RİSK','#F5576C',24]];
  $anVd = ['iyi'=>['İYİ','#2FBE94'],'notr'=>['NÖTR','#8A8CA8'],'dikkat'=>['DİKKAT','#F6A623'],'risk'=>['RİSK','#F5576C']];
  $sidebar = [
    ['home','Ana Sayfa','/dashboard',false],
    ['auto_awesome','Patron AI','/patron-ai',true],
    ['point_of_sale','Satış & Pos','/pos',false],
    ['soup_kitchen','Mutfak','/mutfak',false],
    ['inventory_2','Stok / Depo','/stok',false],
    ['local_shipping','Satın Alma','/satinalma-talep',false],
    ['account_balance_wallet','Kasa / Finans','/finans',false],
    ['bar_chart','Raporlar','/raporlar',false],
    ['badge','Personel','/personel',false],
    ['groups','Müşteriler','/musteriler',false],
    ['settings','Ayarlar','/bagli-cihazlar',false],
  ];
@endphp
<style>
:root{--bg:#F3F2FB;--panel:#FFFFFF;--card2:#F7F7FC;--ink:#2A2A42;--sub:#8A8CA8;--sub2:#AEB0C6;--line:#ECEBF5;
  --mor:#7B5CFA;--mor-soft:#EEEAFE;--rose:#F56A8B;--green:#2FBE94;--amber:#F6A623;--blue:#5B8DEF;--red:#F5576C;color-scheme:light}
html[data-theme="dark"]{--bg:#12121C;--panel:#1B1B29;--card2:#232334;--ink:#ECECF6;--sub:#9A9CB8;--sub2:#71738E;--line:#2A2A3C;--mor-soft:#241F40;color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:'Plus Jakarta Sans',system-ui,sans-serif;font-size:13px;line-height:1.5}
.material-icons-outlined{font-family:'Material Icons Outlined';font-weight:normal;font-style:normal;line-height:1;letter-spacing:normal;text-transform:none;display:inline-block;white-space:nowrap;direction:ltr}
.app{display:grid;grid-template-columns:238px 1fr;min-height:100vh}
a{text-decoration:none}

/* SIDEBAR */
.side{background:var(--panel);border-right:1px solid var(--line);display:flex;flex-direction:column;padding:15px 12px;position:sticky;top:0;height:100vh}
.brand{display:flex;align-items:center;gap:11px;padding:2px 8px 14px}
.brand .logo{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#7B5CFA,#6A49F2);display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 8px 18px rgba(123,92,250,.35)}
.brand .logo .material-icons-outlined{color:#fff;font-size:21px}
.brand .bt{font-size:18px;font-weight:800;letter-spacing:-.3px;line-height:1}
.brand .bs{font-size:10px;color:var(--sub);font-weight:500;margin-top:3px}
.nav{display:flex;flex-direction:column;gap:2px;flex:1;overflow-y:auto}
.nav a{display:flex;align-items:center;gap:12px;padding:9px 13px;border-radius:11px;color:var(--sub);font-weight:600;font-size:13.5px;transition:.15s}
.nav a .material-icons-outlined{font-size:20px}
.nav a:hover{background:var(--card2);color:var(--ink)}
.nav a.on{background:linear-gradient(135deg,#7B5CFA,#6A49F2);color:#fff;box-shadow:0 8px 18px rgba(123,92,250,.3)}
.side-foot{border-top:1px solid var(--line);margin-top:8px;padding:13px 8px 2px}
.side-foot .sf1{display:flex;align-items:center;gap:7px;font-weight:800;color:var(--mor);font-size:15px}
.side-foot .sf1 .material-icons-outlined{font-size:17px}
.side-foot .sf2{font-size:11px;color:var(--sub);line-height:1.5;margin-top:6px;font-style:italic}

/* TOPBAR */
.main{min-width:0;display:flex;flex-direction:column}
.top{display:flex;align-items:center;gap:11px;padding:11px 22px;flex-wrap:wrap}
.pill{display:inline-flex;align-items:center;gap:8px;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:8px 13px;font-size:12.5px;font-weight:600;color:var(--ink)}
.pill .material-icons-outlined{font-size:16px;color:var(--sub)}
.pill.sun .material-icons-outlined{color:var(--amber)}
.top .sp{flex:1}
.ic-btn{position:relative;width:38px;height:38px;border-radius:11px;background:var(--panel);border:1px solid var(--line);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--sub)}
.ic-btn .material-icons-outlined{font-size:18px}
.ic-btn .dot{position:absolute;top:-5px;right:-5px;background:var(--red);color:#fff;font-size:9px;font-weight:800;min-width:16px;height:16px;border-radius:9px;display:flex;align-items:center;justify-content:center;border:2px solid var(--panel)}
.user{display:flex;align-items:center;gap:9px;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:5px 12px 5px 6px}
.user .ava{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#7B5CFA,#5B8DEF);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex:none}
.user .un{font-size:12.5px;font-weight:700;line-height:1.1}.user .ur{font-size:10.5px;color:var(--sub)}

.content{padding:0 22px 20px;min-width:0}
/* HERO */
.hero{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:11px}
.hero .hic{width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#FDB44B,#F6A623);display:flex;align-items:center;justify-content:center;font-size:23px;flex:none;box-shadow:0 10px 22px rgba(246,166,35,.3)}
.hero h1{margin:0;font-size:22px;font-weight:800;letter-spacing:-.5px}
.hero .hsub{display:flex;align-items:center;gap:9px;flex-wrap:wrap;font-size:12px;color:var(--sub);margin-top:4px;font-weight:500}
.hero .hdiv{width:1px;height:12px;background:var(--line)}
.badge-ai{display:inline-flex;align-items:center;gap:6px;background:var(--mor-soft);color:var(--mor);border-radius:20px;padding:3px 11px;font-size:11.5px;font-weight:700}
.badge-ai .material-icons-outlined{font-size:13px}
.hero .hright{margin-left:auto;display:flex;align-items:center;gap:8px}
.toggle{display:inline-flex;background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:3px}
.toggle a{display:flex;align-items:center;gap:6px;padding:8px 15px;border-radius:9px;font-size:13px;font-weight:700;color:var(--sub)}
.toggle a .material-icons-outlined{font-size:16px}
.toggle a.on{background:linear-gradient(135deg,#7B5CFA,#6A49F2);color:#fff;box-shadow:0 6px 14px rgba(123,92,250,.3)}

/* KPI */
.kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:11px}
.kpi{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:12px 13px;position:relative;box-shadow:0 1px 3px rgba(20,20,50,.03)}
.kpi .kinfo{position:absolute;top:12px;right:12px;color:var(--sub2);font-size:15px}
.kpi .kic{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
.kpi .kic .material-icons-outlined{font-size:20px}
.kpi .kv{font-size:20px;font-weight:800;letter-spacing:-.5px;line-height:1;font-variant-numeric:tabular-nums}
.kpi .kv small{font-size:12px;font-weight:700;color:var(--sub)}
.kpi .kl{font-size:12px;font-weight:700;margin-top:6px}
.kpi .ks{font-size:10px;color:var(--sub);margin-top:3px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.t-rose{background:rgba(245,106,139,.13);color:var(--rose)}.t-green{background:rgba(47,190,148,.14);color:var(--green)}
.t-amber{background:rgba(246,166,35,.15);color:var(--amber)}.t-blue{background:rgba(91,141,239,.14);color:var(--blue)}
.t-mor{background:var(--mor-soft);color:var(--mor)}.t-red{background:rgba(245,87,108,.13);color:var(--red)}

/* MID */
.mid{display:grid;grid-template-columns:1.72fr 1fr;gap:12px;margin-bottom:4px;align-items:start}
.panel-c{background:var(--panel);border:1px solid var(--line);border-radius:17px;padding:14px 15px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
.phead{display:flex;align-items:center;gap:9px;margin-bottom:11px}
.phead .material-icons-outlined{font-size:20px;color:var(--mor)}
.phead h2{margin:0;font-size:15px;font-weight:800;letter-spacing:-.2px}
.phead .cnt{background:var(--mor-soft);color:var(--mor);border-radius:20px;padding:1px 9px;font-size:12px;font-weight:800}
.phead .more{margin-left:auto;font-size:12px;font-weight:700;color:var(--mor)}
.tasks{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.task{display:flex;align-items:center;gap:11px;background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:9px 11px}
.task .tic{width:35px;height:35px;border-radius:10px;flex:none;display:flex;align-items:center;justify-content:center}
.task .tic .material-icons-outlined{font-size:17px}
.task .tmid{flex:1;min-width:0}
.task .trot{display:inline-block;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 7px;border-radius:20px}
.task .tb{font-size:12px;font-weight:700;margin-top:3px;line-height:1.25}
.task .ta{font-size:10px;color:var(--sub);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.task .tbtn{flex:none;border:0;border-radius:9px;padding:6px 11px;font-size:11px;font-weight:700;cursor:pointer;background:linear-gradient(135deg,#7B5CFA,#6A49F2);color:#fff;white-space:nowrap}
.task .tbtn.sade{background:var(--panel);color:var(--sub);border:1px solid var(--line)}
.ai-panel{background:linear-gradient(170deg,color-mix(in srgb,var(--mor) 8%,var(--panel)),var(--panel) 60%);border:1px solid color-mix(in srgb,var(--mor) 20%,var(--line));border-radius:17px;padding:14px 15px}
.ai-panel .aih{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.ai-panel .arob{width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#7B5CFA,#5B8DEF);display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 8px 18px rgba(123,92,250,.3)}
.ai-panel .arob .material-icons-outlined{color:#fff;font-size:20px}
.ai-panel .aih h2{margin:0;font-size:15px;font-weight:800}
.ai-panel .atext{font-size:11.5px;line-height:1.55;color:var(--sub);margin-bottom:11px}
.ai-panel .atext b{color:var(--mor)}
.ai-yapar{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;margin-bottom:8px}
.ai-yapar .material-icons-outlined{font-size:15px;color:var(--mor)}
.ai-yapar .mini{background:var(--mor);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:6px}
.ai-acts{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.ai-act{display:flex;align-items:center;gap:9px;background:var(--panel);border:1px solid var(--line);border-radius:11px;padding:8px;cursor:pointer;text-align:left;font-family:inherit;font-size:10.5px;font-weight:600;color:var(--ink);line-height:1.3}
.ai-act:hover{border-color:var(--mor)}
.ai-act .aaic{width:29px;height:29px;border-radius:9px;background:var(--mor-soft);color:var(--mor);display:flex;align-items:center;justify-content:center;flex:none}
.ai-act .aaic .material-icons-outlined{font-size:16px}

/* SECTION */
.sec{display:flex;align-items:center;gap:9px;margin:12px 2px 9px}
.sec .material-icons-outlined{font-size:20px;color:var(--mor)}
.sec h2{margin:0;font-size:15px;font-weight:800;letter-spacing:-.2px}
.sec .more{margin-left:auto;font-size:12px;font-weight:700;color:var(--mor)}
.deps{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.dep{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:11px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
.dep .dh{display:flex;align-items:center;gap:9px;margin-bottom:8px}
.dep .dic{width:33px;height:33px;border-radius:10px;flex:none;display:flex;align-items:center;justify-content:center}
.dep .dic .material-icons-outlined{font-size:17px}
.dep .dn{font-size:12.5px;font-weight:800}
.dep .drot{font-size:8.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 7px;border-radius:20px;display:inline-block;margin-top:2px}
.dep .doz{font-size:10px;color:var(--sub);line-height:1.4;min-height:26px;margin-bottom:8px}
.dep .dbar{height:5px;border-radius:5px;background:var(--card2);overflow:hidden;margin-bottom:7px}
.dep .dbar i{display:block;height:100%;border-radius:5px}
.dep .dlink{font-size:10.5px;font-weight:700;color:var(--mor)}
.anz{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}
.an{background:var(--panel);border:1px solid var(--line);border-left-width:4px;border-radius:14px;padding:12px}
.an .ah{display:flex;align-items:center;gap:11px;margin-bottom:9px}
.an .aic{width:35px;height:35px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center}
.an .aic .material-icons-outlined{font-size:18px}
.an .at{font-size:13px;font-weight:800;letter-spacing:-.2px}
.an .arot{margin-left:auto;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 9px;border-radius:20px}
.an .av{font-size:19px;font-weight:800;margin:1px 0 5px;letter-spacing:-.4px}
.an .ad{font-size:11px;color:var(--sub);line-height:1.5}
.an .aoneri{display:flex;align-items:flex-start;gap:8px;background:var(--card2);border-radius:11px;padding:9px 10px;margin-top:9px;font-size:10.5px;line-height:1.45}
.an .aoneri.risk{background:rgba(245,87,108,.09)}
.an .aoneri .arrow{color:var(--rose);font-weight:800;flex:none}

/* EYLEM MODAL */
.emod{position:fixed;inset:0;background:rgba(20,20,45,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:18px}
.emod.acik{display:flex}
.emod .ic{background:var(--panel);border-radius:18px;padding:20px;width:440px;max-width:100%}
.emod h3{margin:0 0 12px;font-size:16px;display:flex;align-items:center;gap:8px}
.emod h3 .material-icons-outlined{color:var(--mor)}
.emod .ozet{font-size:13px;line-height:1.55;color:var(--ink);background:var(--card2);border-radius:12px;padding:12px;margin-bottom:11px}
.emod .uyari{font-size:11px;color:var(--sub);display:flex;align-items:flex-start;gap:6px;margin-bottom:14px}
.emod .uyari .material-icons-outlined{font-size:14px}
.emod .btnler{display:flex;gap:9px;justify-content:flex-end}
.emod .btn-vaz{background:var(--card2);border:0;color:var(--sub);border-radius:11px;padding:10px 16px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
.emod .btn-yap{background:linear-gradient(135deg,#7B5CFA,#6A49F2);border:0;color:#fff;border-radius:11px;padding:10px 18px;font-weight:800;font-size:13px;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px}
.emod .btn-yap .material-icons-outlined{font-size:16px}
.emod .sonuc{display:none;margin-top:12px;font-size:12.5px;padding:11px;border-radius:11px;font-weight:600}
.emod .sonuc.ok{background:rgba(47,190,148,.12);color:#047857;border-left:3px solid var(--green)}
.emod .sonuc.hata{background:rgba(245,87,108,.1);color:var(--red);border-left:3px solid var(--red)}

@media(max-width:1180px){.kpis{grid-template-columns:repeat(3,1fr)}.deps{grid-template-columns:repeat(3,1fr)}.anz{grid-template-columns:1fr}}
@media(max-width:980px){.app{grid-template-columns:1fr}.side{display:none}.mid{grid-template-columns:1fr}}
@media(max-width:720px){.content{padding:0 16px 20px}.top{padding:10px 16px}.tasks{grid-template-columns:1fr}.ai-acts{grid-template-columns:1fr}.kpis{grid-template-columns:1fr 1fr}.deps{grid-template-columns:1fr 1fr}.hero h1{font-size:19px}.hero .hright{margin-left:0;width:100%}}
@media(max-width:440px){.kpis{grid-template-columns:1fr}.deps{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="app">
  {{-- SIDEBAR --}}
  <aside class="side">
    <div class="brand"><div class="logo"><span class="material-icons-outlined">restaurant</span></div><div><div class="bt">ResteOS</div><div class="bs">Restoran İşletme Sistemi</div></div></div>
    <nav class="nav">
      @foreach($sidebar as [$ik,$lbl,$href,$on])
        <a href="{{ $href }}" class="{{ $on?'on':'' }}"><span class="material-icons-outlined">{{ $ik }}</span> {{ $lbl }}</a>
      @endforeach
    </nav>
    <div class="side-foot"><div class="sf1"><span class="material-icons-outlined">auto_awesome</span> ResteOS</div><div class="sf2">Daha iyi bir restoran için, yapay zeka gücü.</div></div>
  </aside>

  {{-- MAIN --}}
  <div class="main">
    <div class="top">
      <span class="pill"><span class="material-icons-outlined">storefront</span> {{ $subeAd ?? 'İşletme' }}</span>
      <span class="pill"><span class="material-icons-outlined">calendar_today</span> {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
      <span class="pill sun"><span class="material-icons-outlined">{{ $tip==='aksam'?'nightlight':'wb_sunny' }}</span> {{ $tip==='aksam'?'Akşam Raporu':'Sabah Briefingi' }}</span>
      <span class="sp"></span>
      <a href="/bildirimler" class="ic-btn"><span class="material-icons-outlined">notifications</span>@if($onayTop>0)<span class="dot">{{ $onayTop }}</span>@endif</a>
      <div class="ic-btn" onclick="paiTema()" title="Gece / Gündüz"><span class="material-icons-outlined" id="temaIk">dark_mode</span></div>
      <a href="/profilim" class="user"><span class="ava">{{ $adK ? mb_strtoupper(mb_substr($adK,0,1)) : '?' }}</span><div><div class="un">{{ $adK ?? 'Patron' }}</div><div class="ur">İşletme Sahibi</div></div></a>
    </div>

    <div class="content">
      {{-- HERO --}}
      <div class="hero">
        <div class="hic">{{ $tip==='aksam'?'🌙':'☀️' }}</div>
        <div>
          <h1>{{ $selam }}{{ $adK ? ' '.$adK : '' }} 👋</h1>
          <div class="hsub"><span>{{ \Carbon\Carbon::now()->translatedFormat('d F') }}</span><span class="hdiv"></span><span>{{ $tip==='aksam'?'akşam raporu':'sabah briefingi' }}</span><span class="hdiv"></span><span class="badge-ai"><span class="material-icons-outlined">auto_awesome</span> {{ $aiVar?'AI yazdı':($aiSoguk?'AI hazırlanıyor':'kural motoru') }}</span></div>
        </div>
        <div class="hright">
          <div class="toggle">
            <a href="/patron-ai?tip=sabah" class="{{ $tip==='sabah'?'on':'' }}"><span class="material-icons-outlined">wb_sunny</span> Sabah</a>
            <a href="/patron-ai?tip=aksam" class="{{ $tip==='aksam'?'on':'' }}"><span class="material-icons-outlined">nightlight</span> Akşam</a>
          </div>
        </div>
      </div>

      @if(!$brief)
        <div class="panel-c" style="text-align:center;color:var(--sub);padding:34px">Bugün için henüz brifing üretilmedi. <a href="/patron-ai?force=1" style="color:var(--mor);font-weight:700">Şimdi üret →</a></div>
      @else
      {{-- 5 KPI --}}
      <div class="kpis">
        <div class="kpi"><span class="material-icons-outlined kinfo">info</span><div class="kic t-rose"><span class="material-icons-outlined">payments</span></div>
          <div class="kv">{{ $tl($v['dun_ciro'] ?? 0) }}</div><div class="kl">{{ $tip==='aksam'?'Bugünkü ciro':'Dünkü ciro' }}</div>
          @if(($v['dun_ciro']??0)==0)<div class="ks" style="color:var(--red)">veri kontrolü gerekli</div>@elseif(!empty($v['kisi_basi']))<div class="ks">kişi başı {{ $tl($v['kisi_basi']) }}</div>@endif</div>
        <div class="kpi"><span class="material-icons-outlined kinfo">info</span><div class="kic t-green"><span class="material-icons-outlined">delete_outline</span></div>
          <div class="kv">{{ $tl($kayipTop) }}</div><div class="kl">Kayıp / Zayiat</div><div class="ks">({{ $tip==='aksam'?'bugün':'dün' }})</div></div>
        <div class="kpi"><span class="material-icons-outlined kinfo">info</span><div class="kic t-amber"><span class="material-icons-outlined">inventory_2</span></div>
          <div class="kv" style="color:{{ $krit>0?'var(--red)':'var(--ink)' }}">{{ $krit }} <small>ürün</small></div><div class="kl">Kritik stok</div><div class="ks">{{ $krit>0?'sipariş gerekli':'sorun yok' }}</div></div>
        <div class="kpi"><span class="material-icons-outlined kinfo">info</span><div class="kic t-blue"><span class="material-icons-outlined">receipt_long</span></div>
          <div class="kv">{{ (int)($acikAdisyon ?? 0) }}</div><div class="kl">Açık adisyon</div><div class="ks">{{ ($acikAdisyon??0)>0?'incelenmesi gerekiyor':'hepsi kapalı' }}</div></div>
        <div class="kpi"><span class="material-icons-outlined kinfo">info</span><div class="kic t-blue"><span class="material-icons-outlined">fact_check</span></div>
          <div class="kv">{{ $onayTop }} <small>işlem</small></div><div class="kl">Onay bekleyen</div><div class="ks">{{ (int)($onayOp??0) }} operasyon + {{ (int)($onaySa??0) }} satın alma</div></div>
      </div>

      {{-- MID --}}
      <div class="mid">
        <div class="panel-c">
          <div class="phead"><span class="material-icons-outlined">track_changes</span><h2>Bugün önceliklerin</h2>@if(count($islerL))<span class="cnt">{{ count($islerL) }}</span>@endif<a href="/dashboard" class="more">Tümünü gör →</a></div>
          @if(count($islerL))
          <div class="tasks">
            @foreach($islerL as $is)
              @php $ak=$is['aksiyon']??['tip'=>'info']; $rk=$is['renk']??'var(--mor)';
                $rkHex=['var(--kirmizi)'=>'#F5576C','var(--amber)'=>'#F6A623','var(--mavi)'=>'#5B8DEF','var(--mor1)'=>'#7B5CFA','var(--yesil)'=>'#2FBE94'][$rk]??'#7B5CFA'; @endphp
              <div class="task">
                <div class="tic" style="background:color-mix(in srgb,{{ $rkHex }} 13%,transparent);color:{{ $rkHex }}"><span class="material-icons-outlined">{{ $is['ikon']??'task_alt' }}</span></div>
                <div class="tmid">
                  @if(!empty($is['grup']))<span class="trot" style="background:color-mix(in srgb,{{ $rkHex }} 14%,transparent);color:{{ $rkHex }}">{{ $is['grup'] }}</span>@endif
                  <div class="tb">{{ $is['baslik']??'' }}</div>
                  @if(!empty($is['aciklama']))<div class="ta">{{ $is['aciklama'] }}</div>@endif
                </div>
                @if(($ak['tip']??'')==='eylem')<button class="tbtn" onclick="paiEylemOner('{{ $ak['intent'] }}')">{{ $ak['lbl']??'Yap' }}</button>
                @elseif(($ak['tip']??'')==='link')<a class="tbtn" href="{{ $ak['url'] }}">{{ $ak['lbl']??'Aç' }} →</a>
                @elseif(($ak['tip']??'')==='ajanda')<button class="tbtn sade" onclick="paiAjandaBitti({{ $ak['id'] }},this)">{{ $ak['lbl']??'Bitti' }}</button>@endif
              </div>
            @endforeach
          </div>
          @else<div style="color:var(--sub);padding:16px;text-align:center">✅ Bekleyen iş yok — her şey yolunda 👍</div>@endif
        </div>

        <div class="ai-panel">
          <div class="aih"><div class="arob"><span class="material-icons-outlined">smart_toy</span></div><h2>Patron AI ne öneriyor?</h2></div>
          <div class="atext" id="pai-verdict">{!! $bold($verdict) !!}</div>
          <div class="ai-yapar"><span class="material-icons-outlined">auto_awesome</span> Senin için şunları yapabilirim <span class="mini">AI</span></div>
          <div class="ai-acts">
            <button class="ai-act" onclick="paiEylemOner('kritik_siparis_olustur')"><span class="aaic"><span class="material-icons-outlined">inventory_2</span></span>Kritik stok için sipariş taslağı hazırla</button>
            <button class="ai-act" onclick="paiEylemOner('tedarikci_hatirlat')"><span class="aaic"><span class="material-icons-outlined">local_shipping</span></span>Tedarikçiye hatırlatma gönder</button>
            <a class="ai-act" href="/dashboard"><span class="aaic"><span class="material-icons-outlined">receipt_long</span></span>Açık adisyonları listele</a>
            <a class="ai-act" href="/satinalma-talep"><span class="aaic"><span class="material-icons-outlined">fact_check</span></span>Onay bekleyen işlemleri aç</a>
          </div>
        </div>
      </div>

      {{-- DEPARTMANLAR --}}
      @if(count($denetimL))
      <div class="sec"><span class="material-icons-outlined">apartment</span><h2>Departmanların durumu</h2><a href="/departman-rapor" class="more">Tüm departmanlar →</a></div>
      <div class="deps">
        @foreach($denetimL as $dn)
          @php [$det,$drk,$dpr]=$depVd[$dn['durum']]??$depVd['notr']; $dicHex=$dn['renk']?:'#5B8DEF'; @endphp
          <div class="dep">
            <div class="dh"><div class="dic" style="background:color-mix(in srgb,{{ $dicHex }} 14%,transparent);color:{{ $dicHex }}"><span class="material-icons-outlined">{{ $dn['ikon']?:'badge' }}</span></div>
              <div style="min-width:0;flex:1"><div class="dn">{{ $dn['ad'] }}</div><span class="drot" style="background:color-mix(in srgb,{{ $drk }} 14%,transparent);color:{{ $drk }}">{{ $det }}</span>@if(!empty($dn['uyari_say']))<span class="drot" style="background:rgba(246,166,35,.15);color:var(--amber)">{{ $dn['uyari_say'] }} ⚠</span>@endif</div></div>
            <div class="doz">{{ \Illuminate\Support\Str::limit($dn['ozet'] ?? '', 70) }}</div>
            <div class="dbar"><i style="width:{{ $dpr }}%;background:{{ $drk }}"></i></div>
            <a href="/departman-rapor?id={{ $dn['id'] }}" class="dlink">Detayı gör →</a>
          </div>
        @endforeach
      </div>
      @endif

      {{-- DETAYLI AI ANALİZİ --}}
      @if(count($analizL))
      <div class="sec"><span class="material-icons-outlined">insights</span><h2>Detaylı AI analizi</h2></div>
      <div class="anz">
        @foreach($analizL as $a)
          @php [$et,$rk2]=$anVd[$a['durum']]??$anVd['notr']; @endphp
          <div class="an" style="border-left-color:{{ $rk2 }}">
            <div class="ah"><div class="aic" style="background:color-mix(in srgb,{{ $rk2 }} 14%,transparent);color:{{ $rk2 }}"><span class="material-icons-outlined">{{ $a['ikon']??'insights' }}</span></div><div class="at">{{ $a['baslik'] }}</div><span class="arot" style="background:color-mix(in srgb,{{ $rk2 }} 14%,transparent);color:{{ $rk2 }}">{{ $et }}</span></div>
            @if(!empty($a['deger']))<div class="av">{{ $a['deger'] }}</div>@endif
            <div class="ad">{!! $bold($a['yorum'] ?? '') !!}</div>
            @if(!empty($a['oneri']))<div class="aoneri {{ ($a['durum']??'')==='risk'?'risk':'' }}"><span class="arrow">→</span><span><b>Öneri:</b> {!! $bold($a['oneri']) !!}@if(!empty($a['link'])) <a href="{{ $a['link'] }}" style="color:var(--mor);font-weight:700">{{ $a['lbl']??'Aç' }} →</a>@endif</span></div>@endif
          </div>
        @endforeach
      </div>
      @endif
      @endif
    </div>
  </div>
</div>

{{-- EYLEM ONAY MODALI --}}
<div class="emod" id="emod" onclick="if(event.target===this)paiEylemKapat()">
  <div class="ic">
    <h3><span class="material-icons-outlined">auto_awesome</span> AI eylem onayı</h3>
    <div class="ozet" id="emodOzet"></div>
    <div class="uyari"><span class="material-icons-outlined">lock</span> Onayın IP'n (<b id="emodIp">—</b>) ile imzalı log'a yazılacak. Geri alınamaz.</div>
    <div class="btnler"><button class="btn-vaz" onclick="paiEylemKapat()">Kapat</button><button class="btn-yap" id="emodYap" onclick="paiEylemYap()"><span class="material-icons-outlined">check</span> İşlemi Yap</button></div>
    <div class="sonuc" id="emodSonuc"></div>
  </div>
</div>

<script>
const PAI_CSRF=document.querySelector('meta[name=csrf-token]').content;
// tema
try{ if(localStorage.getItem('resteos_tema')==='dark'){document.documentElement.setAttribute('data-theme','dark');document.getElementById('temaIk').textContent='light_mode';} }catch(e){}
function paiTema(){ const d=document.documentElement.getAttribute('data-theme')==='dark'; document.documentElement.setAttribute('data-theme',d?'light':'dark'); document.getElementById('temaIk').textContent=d?'dark_mode':'light_mode'; try{localStorage.setItem('resteos_tema',d?'light':'dark');}catch(e){} }
// ajanda bitti
function paiAjandaBitti(id,btn){ btn.disabled=true; btn.textContent='...'; fetch('/ajanda/durum',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PAI_CSRF,'Accept':'application/json'},body:JSON.stringify({id:id,durum:'tamam'})}).then(r=>r.json()).then(j=>{const t=btn.closest('.task');if(t){t.style.transition='opacity .2s';t.style.opacity='0';setTimeout(()=>t.remove(),200);}}).catch(()=>{btn.disabled=false;btn.textContent='Bitti';}); }
// eylem onay akışı
let _eLog=null;
async function paiEylemOner(intent){
  try{
    const r=await fetch('/api/patron-ai/eylem-oner',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PAI_CSRF,'Accept':'application/json'},body:JSON.stringify({intent})});
    const j=await r.json();
    if(!j.ok||!j.log_id){alert(j.hata||'Önerilemedi.');return;}
    _eLog=j.log_id;
    document.getElementById('emodOzet').textContent=j.ozet;
    document.getElementById('emodSonuc').style.display='none';
    const b=document.getElementById('emodYap');b.disabled=false;b.style.opacity='1';
    try{document.getElementById('emodIp').textContent=(await(await fetch('https://api.ipify.org?format=json')).json()).ip||'—';}catch(e){document.getElementById('emodIp').textContent='—';}
    document.getElementById('emod').classList.add('acik');
  }catch(e){alert('Bağlantı hatası');}
}
async function paiEylemYap(){
  if(!_eLog)return;
  const b=document.getElementById('emodYap');b.disabled=true;b.style.opacity='.6';b.innerHTML='<span class="material-icons-outlined">hourglass_bottom</span> Yürütülüyor...';
  try{
    const r=await fetch('/api/ai-eylem/onayla',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PAI_CSRF,'Accept':'application/json'},body:JSON.stringify({log_id:_eLog})});
    const j=await r.json();const s=j.sonuc||{};
    const el=document.getElementById('emodSonuc');el.style.display='block';el.className='sonuc '+(s.ok?'ok':'hata');el.textContent=s.mesaj||'Sonuç alınamadı.';
    b.innerHTML='<span class="material-icons-outlined">check</span> İşlemi Yap';
  }catch(e){const el=document.getElementById('emodSonuc');el.style.display='block';el.className='sonuc hata';el.textContent='Bağlantı hatası';b.disabled=false;b.style.opacity='1';}
}
function paiEylemKapat(){document.getElementById('emod').classList.remove('acik');_eLog=null;}
</script>
</body>
</html>

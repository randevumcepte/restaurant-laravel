<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#7C3AED">
<title>Sipariş Takibi · {{ $sube->ad ?? 'ResteOS' }}</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --ink:#14121A; --gri:#6B7280; --line:#EEE9F5; --bg:#FBFAFF; --yesil:#10B981; --gold:#E9A23B; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);padding-bottom:30px}
  .top{background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;padding:22px 18px 18px;border-radius:0 0 26px 26px}
  .top .brand{display:flex;align-items:center;gap:10px;margin-bottom:6px}
  .top .toque{font-size:24px}.top b{font-size:17px;font-weight:800}
  .top .no{font-size:13px;color:#E9D5FF}
  .durum-buyuk{font-size:23px;font-weight:800;margin-top:14px}
  .durum-alt{font-size:13.5px;color:#E9D5FF;margin-top:3px}
  .wrap{max-width:520px;margin:0 auto;padding:16px}
  /* Stepper */
  .step{display:flex;align-items:flex-start;gap:14px;position:relative;padding-bottom:22px}
  .step:last-child{padding-bottom:0}
  .step .cizgi{position:absolute;left:15px;top:32px;bottom:0;width:2px;background:var(--line)}
  .step.done .cizgi{background:var(--yesil)}
  .step .yuv{width:32px;height:32px;border-radius:50%;background:#fff;border:2px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;z-index:1}
  .step.done .yuv{background:var(--yesil);border-color:var(--yesil);color:#fff}
  .step.aktif .yuv{border-color:var(--mor);color:var(--mor);animation:pp 1.4s infinite}
  @keyframes pp{0%,100%{box-shadow:0 0 0 0 rgba(124,58,237,.4)}50%{box-shadow:0 0 0 8px rgba(124,58,237,0)}}
  .step .m .b{font-size:15px;font-weight:700}
  .step.pas .m .b{color:#B4BAC6}
  .step .m .a{font-size:12.5px;color:var(--gri)}
  .kart{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px;margin-top:16px}
  .kart h3{font-size:14px;color:var(--gri);margin-bottom:10px;font-weight:700}
  .si{display:flex;justify-content:space-between;font-size:14px;padding:6px 0}
  .si.top-toplam{border-top:1px solid var(--line);margin-top:6px;padding-top:10px;font-weight:800;font-size:15px}
  #map{height:230px;border-radius:16px;margin-top:16px;border:1px solid var(--line);display:none}
  .kurye-kart{display:none;align-items:center;gap:12px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px;margin-top:12px}
  .kurye-kart .av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--mor),var(--mavi));display:flex;align-items:center;justify-content:center;font-size:22px}
  .kurye-kart .ad{font-weight:700} .kurye-kart .du{font-size:12.5px;color:var(--gri)}
  .kurye-kart a{margin-left:auto;background:var(--yesil);color:#fff;text-decoration:none;padding:10px 16px;border-radius:12px;font-weight:700;font-size:13.5px}
  .kmark{background:linear-gradient(135deg,var(--mor),var(--mavi));width:34px;height:34px;border-radius:50% 50% 50% 4px;display:flex;align-items:center;justify-content:center;font-size:17px;border:2px solid #fff;transform:rotate(-45deg)}
  .kmark span{transform:rotate(45deg)}
</style>
</head>
<body>
  <div class="top">
    <div class="brand"><span class="toque">👨‍🍳</span><b>{{ $sube->ad ?? 'Restoran' }}</b></div>
    <div class="no" id="t-no">Sipariş #—</div>
    <div class="durum-buyuk" id="t-durum">Yükleniyor…</div>
    <div class="durum-alt" id="t-alt"></div>
  </div>

  <div class="wrap">
    <div id="stepper"></div>

    <div class="kurye-kart" id="kuryeKart">
      <div class="av">🛵</div>
      <div><div class="ad" id="k-ad">Kurye</div><div class="du">Siparişin yolda</div></div>
      <a id="k-ara" href="#">📞 Ara</a>
    </div>
    <div id="map"></div>

    <div class="kart">
      <h3>SİPARİŞ ÖZETİ</h3>
      <div id="kalemler"></div>
      <div class="si top-toplam"><span>Toplam</span><span id="t-toplam">—</span></div>
      <div class="si" style="color:var(--gri);font-size:13px"><span id="t-odeme"></span><span id="t-adres"></span></div>
    </div>
  </div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var TOKEN = @json($token);
  var map=null, kmarker=null;
  function TL(v){ return new Intl.NumberFormat('tr').format(Math.round(v))+'TL'; }
  var ADIMLAR = [
    {k:'alindi', b:'Siparişin alındı', a:'Restorana ulaştı'},
    {k:'hazirlaniyor', b:'Hazırlanıyor', a:'Mutfakta hazırlanıyor'},
    {k:'yolda', b:'Yola çıktı', a:'Kurye sana geliyor'},
    {k:'teslim', b:'Teslim edildi', a:'Afiyet olsun! 🎉'}
  ];
  var SIRA = {'alindi':0,'hazirlaniyor':1,'hazir':1,'yolda':2,'teslim':3};

  function ciz(d){
    var gelal = (d.adres==='GEL-AL');
    if(gelal){ ADIMLAR[2]={k:'yolda',b:'Hazır — gelebilirsin',a:'Siparişin hazır, teslim al'}; }
    document.getElementById('t-no').textContent='Sipariş #'+d.no;
    var idx = SIRA[d.durum]!=null?SIRA[d.durum]:1;
    document.getElementById('t-durum').textContent = ADIMLAR[idx].b;
    document.getElementById('t-alt').textContent = ADIMLAR[idx].a;
    var h='';
    ADIMLAR.forEach(function(s,i){
      var cls = i<idx?'done':(i===idx?'aktif':'pas');
      var ik = i<idx?'✓':(i===idx?'●':'');
      h+='<div class="step '+cls+'">'+(i<ADIMLAR.length-1?'<div class="cizgi"></div>':'')+'<div class="yuv">'+ik+'</div><div class="m"><div class="b">'+s.b+'</div><div class="a">'+(i<=idx?s.a:'')+'</div></div></div>';
    });
    document.getElementById('stepper').innerHTML=h;

    var kh=''; (d.kalemler||[]).forEach(function(k){ kh+='<div class="si"><span>'+(k.adet%1===0?k.adet:k.adet)+' × '+k.ad+'</span><span>'+TL(k.tutar)+'</span></div>'; });
    document.getElementById('kalemler').innerHTML=kh;
    document.getElementById('t-toplam').textContent=TL(d.toplam);
    document.getElementById('t-odeme').textContent = d.odeme==='kart_kapida'?'💳 Kapıda Kart':(d.odeme==='nakit'?'💵 Kapıda Nakit':'');
    document.getElementById('t-adres').textContent = gelal?'🏃 Gel-Al':'📍 '+(d.adres||'');

    // Kurye + harita (yolda ise)
    if(d.durum==='yolda' && d.kurye && d.kurye.lat){
      document.getElementById('kuryeKart').style.display='flex';
      document.getElementById('k-ad').textContent=d.kurye.ad;
      if(d.kurye.telefon) document.getElementById('k-ara').href='tel:'+d.kurye.telefon; else document.getElementById('k-ara').style.display='none';
      var mp=document.getElementById('map'); mp.style.display='block';
      if(!map){ map=L.map('map',{zoomControl:false}).setView([d.kurye.lat,d.kurye.lng],15); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19}).addTo(map); }
      var ico=L.divIcon({className:'',html:'<div class="kmark"><span>🛵</span></div>',iconSize:[34,34],iconAnchor:[17,34]});
      if(kmarker){ kmarker.setLatLng([d.kurye.lat,d.kurye.lng]); } else { kmarker=L.marker([d.kurye.lat,d.kurye.lng],{icon:ico}).addTo(map); }
      map.setView([d.kurye.lat,d.kurye.lng]);
    } else {
      document.getElementById('kuryeKart').style.display='none';
      document.getElementById('map').style.display='none';
    }
  }
  function yukle(){ fetch('/api/app/siparis-durum/'+TOKEN).then(function(r){return r.json();}).then(function(d){ if(d.ok)ciz(d); }).catch(function(){}); }
  yukle(); setInterval(yukle, 8000);
</script>
</body>
</html>

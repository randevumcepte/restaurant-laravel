<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Canlı Kurye Haritası · {{ $sube->ad ?? 'ResteOS' }}</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --line:#232B42; --sessiz:#94A3B8; --yesil:#10B981; --amber:#F59E0B; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#E8EDF6;height:100dvh;display:flex;flex-direction:column;overflow:hidden}
  header{display:flex;align-items:center;gap:14px;padding:12px 16px;border-bottom:1px solid var(--line);flex-shrink:0}
  header .t{font-size:17px;font-weight:800}
  header .ozet{margin-left:auto;display:flex;gap:16px;font-size:13px;color:var(--sessiz)}
  header .ozet b{color:#fff;font-size:15px}
  .body{flex:1;display:flex;min-height:0}
  #map{flex:1;background:#0f1424}
  .yan{width:320px;flex-shrink:0;border-left:1px solid var(--line);overflow-y:auto;padding:14px}
  @media(max-width:760px){.yan{display:none}}
  .grup{font-size:12px;font-weight:800;color:var(--sessiz);letter-spacing:.5px;margin:6px 2px 10px;text-transform:uppercase}
  .kk{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:8px;cursor:pointer}
  .kk .av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--mor),var(--mavi));display:flex;align-items:center;justify-content:center;font-size:17px}
  .kk .ad{font-size:14px;font-weight:700}
  .kk .du{font-size:11.5px;color:var(--sessiz)}
  .kk .rz{margin-left:auto;font-size:11px;font-weight:800;padding:3px 8px;border-radius:20px}
  .rz.musait{background:rgba(16,185,129,.16);color:var(--yesil)} .rz.teslimatta{background:rgba(245,158,11,.16);color:var(--amber)} .rz.mola{background:rgba(148,163,184,.16);color:var(--sessiz)}
  .tk{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:8px}
  .tk .adr{font-size:13.5px;font-weight:600} .tk .mt{font-size:11.5px;color:var(--sessiz);margin-top:3px}
  .leaflet-popup-content{font-family:inherit}
  .kmark{background:linear-gradient(135deg,var(--mor),var(--mavi));width:36px;height:36px;border-radius:50% 50% 50% 4px;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 4px 10px rgba(0,0,0,.4);border:2px solid #fff;transform:rotate(-45deg)}
  .kmark span{transform:rotate(45deg)}
</style>
</head>
<body>
<header>
  <div class="t">🛵 Canlı Kurye Haritası</div>
  <div class="ozet">
    <span><b id="o-kurye">–</b> kurye</span>
    <span><b id="o-musait" style="color:#10B981">–</b> müsait</span>
    <span><b id="o-yolda" style="color:#F59E0B">–</b> yolda</span>
    <span style="color:#475569" id="o-son"></span>
  </div>
</header>
<div class="body">
  <div id="map"></div>
  <div class="yan">
    <div class="grup">Kuryeler</div>
    <div id="kuryeler"></div>
    <div class="grup" style="margin-top:16px">Aktif Teslimatlar</div>
    <div id="teslimatlar"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var map = L.map('map', {zoomControl:true}).setView([40.9905, 29.0250], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'© OpenStreetMap'}).addTo(map);
  var markers = {}; var ilk = true;

  function ikon(){ return L.divIcon({className:'', html:'<div class="kmark"><span>🛵</span></div>', iconSize:[36,36], iconAnchor:[18,36], popupAnchor:[0,-34]}); }

  function yukle(){
    fetch('/api/kurye-canli-veri').then(function(r){return r.json();}).then(function(d){
      if(!d.ok) return;
      document.getElementById('o-kurye').textContent = d.ozet.kurye;
      document.getElementById('o-musait').textContent = d.ozet.musait;
      document.getElementById('o-yolda').textContent = d.ozet.yolda;
      var t = new Date(); document.getElementById('o-son').textContent = 'son güncelleme ' + ('0'+t.getHours()).slice(-2)+':'+('0'+t.getMinutes()).slice(-2)+':'+('0'+t.getSeconds()).slice(-2);

      var bounds = [];
      d.kuryeler.forEach(function(k){
        if(k.lat && k.lng){
          bounds.push([k.lat,k.lng]);
          var pop = '<b>'+k.ad+'</b><br>'+k.durum+' · '+k.aktif_teslimat+' teslimat'+(k.konum_dk!=null?'<br><small>'+k.konum_dk+' dk önce</small>':'');
          if(markers[k.id]){ markers[k.id].setLatLng([k.lat,k.lng]).setPopupContent(pop); }
          else { markers[k.id] = L.marker([k.lat,k.lng],{icon:ikon()}).addTo(map).bindPopup(pop); }
        }
      });
      if(ilk && bounds.length){ map.fitBounds(bounds,{padding:[60,60],maxZoom:15}); ilk=false; }

      // Yan liste
      var kh=''; d.kuryeler.forEach(function(k){
        kh += '<div class="kk" onclick="odak('+k.id+')"><div class="av">🛵</div><div><div class="ad">'+k.ad+'</div><div class="du">'+k.aktif_teslimat+' aktif teslimat'+(k.konum_dk!=null?' · '+k.konum_dk+' dk':' · konum yok')+'</div></div><div class="rz '+k.durum+'">'+k.durum+'</div></div>';
      });
      document.getElementById('kuryeler').innerHTML = kh || '<div style="color:#64748b;font-size:13px">Kurye yok</div>';

      var th=''; d.teslimatlar.forEach(function(t){
        th += '<div class="tk"><div class="adr">📍 '+(t.teslimat_adres||'Adres yok')+'</div><div class="mt">#'+t.id+' · '+(t.kurye||'atanmadı')+' · '+(t.platform||'paket')+' · '+Math.round(t.toplam)+'TL</div></div>';
      });
      document.getElementById('teslimatlar').innerHTML = th || '<div style="color:#64748b;font-size:13px">Aktif teslimat yok</div>';
    }).catch(function(){});
  }
  function odak(id){ if(markers[id]){ map.setView(markers[id].getLatLng(),16); markers[id].openPopup(); } }
  yukle(); setInterval(yukle, 8000);
</script>
</body>
</html>

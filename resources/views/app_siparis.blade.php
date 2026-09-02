<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#7C3AED">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $sube->ad ?? 'Sipariş' }}">
<title>{{ $sube->ad ?? 'Restoran' }} · Online Sipariş</title>
<style>
  :root{ --mor:#7C3AED; --mor2:#9D5DC8; --mavi:#4F46E5; --ink:#14121A; --gri:#6B7280; --line:#EEE9F5; --bg:#FBFAFF; --gold:#E9A23B; --yesil:#10B981; }
  *{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);padding-bottom:96px}
  .top{background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;padding:20px 18px 16px;border-radius:0 0 26px 26px}
  .top .brand{display:flex;align-items:center;gap:10px}
  .top .toque{font-size:26px}
  .top h1{font-size:20px;font-weight:800}
  .top .adr{font-size:12.5px;color:#E9D5FF;margin-top:2px}
  .top .tag{margin-top:12px;display:inline-flex;gap:6px;align-items:center;background:rgba(255,255,255,.16);padding:6px 12px;border-radius:20px;font-size:12.5px;font-weight:600}
  .cips{display:flex;gap:8px;overflow-x:auto;padding:14px 16px 6px;position:sticky;top:0;background:var(--bg);z-index:10}
  .cips::-webkit-scrollbar{display:none}
  .cip{flex:0 0 auto;padding:9px 16px;border-radius:20px;background:#fff;border:1px solid var(--line);font-size:13.5px;font-weight:700;color:var(--gri);cursor:pointer}
  .cip.act{background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;border-color:transparent}
  .liste{padding:6px 16px}
  .kat-b{font-size:16px;font-weight:800;margin:16px 2px 8px}
  .urun{display:flex;gap:12px;background:#fff;border:1px solid var(--line);border-radius:18px;padding:12px;margin-bottom:10px;align-items:center}
  .urun .g{width:64px;height:64px;border-radius:14px;background:#F3F0FF;display:flex;align-items:center;justify-content:center;font-size:30px;flex-shrink:0;overflow:hidden}
  .urun .g img{width:100%;height:100%;object-fit:cover}
  .urun .o{flex:1;min-width:0}
  .urun .ad{font-size:15px;font-weight:700}
  .urun .ac{font-size:12.5px;color:var(--gri);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .urun .fi{font-size:15px;font-weight:800;color:var(--mor);margin-top:5px}
  .urun .ekle{width:38px;height:38px;border-radius:12px;border:none;background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;font-size:22px;line-height:1;cursor:pointer;flex-shrink:0}
  .urun.tuk{opacity:.5} .urun.tuk .ekle{background:#CBD5E1}
  .adet{display:flex;align-items:center;gap:10px;flex-shrink:0}
  .adet button{width:32px;height:32px;border-radius:9px;border:1px solid var(--line);background:#fff;font-size:18px;color:var(--mor);cursor:pointer}
  .adet b{font-size:15px;min-width:18px;text-align:center}
  /* Sepet bar */
  .sepbar{position:fixed;left:0;right:0;bottom:0;padding:12px 16px calc(12px + env(safe-area-inset-bottom));background:var(--bg);border-top:1px solid var(--line);display:none}
  .sepbar.show{display:block}
  .sepbtn{width:100%;border:none;border-radius:16px;padding:15px 18px;background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;font-size:16px;font-weight:800;display:flex;justify-content:space-between;align-items:center;cursor:pointer}
  .sepbtn .n{background:rgba(255,255,255,.2);padding:3px 10px;border-radius:20px;font-size:13px}
  /* Alt sheet */
  .kapak{position:fixed;inset:0;background:rgba(10,6,20,.5);display:none;z-index:40}
  .kapak.show{display:block}
  .sheet{position:fixed;left:0;right:0;bottom:0;background:#fff;border-radius:24px 24px 0 0;z-index:41;transform:translateY(100%);transition:transform .25s;max-height:90dvh;overflow-y:auto;padding:18px 18px calc(20px + env(safe-area-inset-bottom))}
  .sheet.show{transform:none}
  .sheet h2{font-size:19px;font-weight:800;margin-bottom:14px}
  .si{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)}
  .si .sad{font-size:14.5px;font-weight:600}
  .si .sfi{font-size:13px;color:var(--gri)}
  label{display:block;font-size:12.5px;font-weight:700;color:var(--gri);margin:14px 2px 7px}
  input,textarea{width:100%;border:1px solid var(--line);border-radius:13px;padding:13px 14px;font-size:15px;font-family:inherit;outline:none;background:#FBFAFF}
  input:focus,textarea:focus{border-color:var(--mor)}
  textarea{height:64px;resize:none}
  .seg{display:flex;gap:8px}
  .seg .s{flex:1;text-align:center;padding:12px;border-radius:13px;border:1px solid var(--line);font-weight:700;font-size:14px;cursor:pointer;background:#fff;color:var(--gri)}
  .seg .s.act{background:var(--mor);color:#fff;border-color:transparent}
  .onay{width:100%;border:none;border-radius:16px;padding:16px;margin-top:18px;background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;font-size:16px;font-weight:800;cursor:pointer}
  .onay:disabled{opacity:.6}
  .hata{display:none;background:#FEECEC;color:#DC2626;padding:10px 14px;border-radius:12px;font-size:13px;margin-top:12px}
  .yuk{text-align:center;color:var(--gri);padding:60px 20px}
</style>
</head>
<body>
  <div class="top">
    <div class="brand"><span class="toque">👨‍🍳</span><div><h1>{{ $sube->ad ?? 'Restoran' }}</h1><div class="adr">{{ $sube->adres ?? 'Online Sipariş' }}</div></div></div>
    <div class="tag">🛵 Paket & Gel-Al · ⚡ Hızlı hazırlık</div>
  </div>

  <div class="cips" id="cips"></div>
  <div class="liste" id="liste"><div class="yuk">Menü yükleniyor…</div></div>

  <div class="sepbar" id="sepbar">
    <button class="sepbtn" onclick="sepetAc()"><span>Sepeti Gör</span><span class="n" id="sep-n">0</span><span id="sep-t">0TL</span></button>
  </div>

  <div class="kapak" id="kapak" onclick="kapat()"></div>

  <!-- Sepet sheet -->
  <div class="sheet" id="sepetSheet">
    <h2>🛒 Sepetim</h2>
    <div id="sepetIcerik"></div>
    <div class="si" style="border:none;font-weight:800;font-size:16px"><span>Toplam</span><span id="sepetToplam">0TL</span></div>
    <button class="onay" onclick="odemeAc()">Devam Et →</button>
  </div>

  <!-- Odeme sheet -->
  <div class="sheet" id="odemeSheet">
    <h2>📦 Teslimat & Ödeme</h2>
    <label>Teslimat Şekli</label>
    <div class="seg" id="tipSeg"><div class="s act" data-t="paket" onclick="tipSec('paket',this)">🛵 Adrese Paket</div><div class="s" data-t="gelal" onclick="tipSec('gelal',this)">🏃 Gel-Al</div></div>
    <label>Ad Soyad</label><input id="i-ad" placeholder="Adınız">
    <label>Telefon</label><input id="i-tel" type="tel" inputmode="tel" placeholder="05__ ___ __ __">
    <div id="adresWrap"><label>Teslimat Adresi</label><textarea id="i-adres" placeholder="Mahalle, cadde, no, daire, tarif…"></textarea></div>
    <label>Ödeme</label>
    <div class="seg" id="odemeSeg"><div class="s act" data-o="nakit" onclick="odemeSec('nakit',this)">💵 Kapıda Nakit</div><div class="s" data-o="kart_kapida" onclick="odemeSec('kart_kapida',this)">💳 Kapıda Kart</div></div>
    <label>Sipariş Notu (opsiyonel)</label><textarea id="i-not" placeholder="Zili çalmayın, kapıda arayın…"></textarea>
    <div class="hata" id="hata"></div>
    <button class="onay" id="onayBtn" onclick="gonder()">Siparişi Onayla</button>
  </div>

<script>
  var SUBE = {{ $sube->id }};
  var menu = {kategoriler:[], urunler:[]}, sepet = {}, aktifKat = 0, tip='paket', odeme='nakit';
  function TL(v){ return new Intl.NumberFormat('tr').format(Math.round(v))+'TL'; }

  fetch('/api/app/'+SUBE+'/menu').then(function(r){return r.json();}).then(function(d){
    if(!d.ok){ document.getElementById('liste').innerHTML='<div class="yuk">Menü alınamadı</div>'; return; }
    menu = d;
    var ch='<div class="cip act" onclick="katSec(0,this)">Tümü</div>';
    d.kategoriler.forEach(function(k){ ch+='<div class="cip" onclick="katSec('+k.id+',this)">'+k.ad+'</div>'; });
    document.getElementById('cips').innerHTML = ch;
    ciz();
  });

  function katSec(id, el){ aktifKat=id; document.querySelectorAll('.cip').forEach(function(c){c.classList.remove('act')}); el.classList.add('act'); ciz(); }
  function ciz(){
    var us = menu.urunler.filter(function(u){ return aktifKat===0 || u.kategori_id===aktifKat; });
    var html='';
    // Kategoriye gore grupla (Tümü'nde)
    if(aktifKat===0){
      menu.kategoriler.forEach(function(k){
        var ku = menu.urunler.filter(function(u){return u.kategori_id===k.id;});
        if(!ku.length) return;
        html += '<div class="kat-b">'+k.ad+'</div>';
        ku.forEach(function(u){ html+=urunHtml(u); });
      });
      var yok = menu.urunler.filter(function(u){return !u.kategori_id;});
      if(yok.length){ html+='<div class="kat-b">Diğer</div>'; yok.forEach(function(u){html+=urunHtml(u);}); }
    } else {
      us.forEach(function(u){ html+=urunHtml(u); });
    }
    document.getElementById('liste').innerHTML = html || '<div class="yuk">Ürün yok</div>';
  }
  function urunHtml(u){
    var g = u.gorsel ? '<img src="'+u.gorsel+'">' : '🍽️';
    var sag;
    if(u.tukendi){ sag='<button class="ekle" disabled>×</button>'; }
    else if(sepet[u.id]){ sag='<div class="adet"><button onclick="azalt('+u.id+')">−</button><b>'+sepet[u.id].adet+'</b><button onclick="arttir('+u.id+')">+</button></div>'; }
    else { sag='<button class="ekle" onclick="arttir('+u.id+')">+</button>'; }
    return '<div class="urun'+(u.tukendi?' tuk':'')+'"><div class="g">'+g+'</div><div class="o"><div class="ad">'+u.ad+(u.tukendi?' (tükendi)':'')+'</div>'+(u.aciklama?'<div class="ac">'+u.aciklama+'</div>':'')+'<div class="fi">'+TL(u.fiyat)+'</div></div>'+sag+'</div>';
  }
  function arttir(id){ var u=menu.urunler.find(function(x){return x.id===id;}); if(!u||u.tukendi)return; if(!sepet[id])sepet[id]={ad:u.ad,fiyat:u.fiyat,adet:0}; sepet[id].adet++; ciz(); sepGuncel(); }
  function azalt(id){ if(!sepet[id])return; sepet[id].adet--; if(sepet[id].adet<=0)delete sepet[id]; ciz(); sepGuncel(); }
  function sepAdet(){ var n=0; for(var k in sepet)n+=sepet[k].adet; return n; }
  function sepTop(){ var t=0; for(var k in sepet)t+=sepet[k].fiyat*sepet[k].adet; return t; }
  function sepGuncel(){
    var n=sepAdet();
    document.getElementById('sepbar').classList.toggle('show', n>0);
    document.getElementById('sep-n').textContent=n; document.getElementById('sep-t').textContent=TL(sepTop());
  }
  function sepetAc(){
    var h=''; for(var k in sepet){ var s=sepet[k]; h+='<div class="si"><div><div class="sad">'+s.ad+'</div><div class="sfi">'+s.adet+' × '+TL(s.fiyat)+'</div></div><div class="adet"><button onclick="azalt('+k+');sepetAc()">−</button><b>'+s.adet+'</b><button onclick="arttir('+k+');sepetAc()">+</button></div></div>'; }
    document.getElementById('sepetIcerik').innerHTML = h || '<div class="yuk">Sepet boş</div>';
    document.getElementById('sepetToplam').textContent = TL(sepTop());
    ac('sepetSheet');
  }
  function odemeAc(){ if(sepAdet()===0)return; kapatSheets(); ac('odemeSheet'); }
  function ac(id){ document.getElementById('kapak').classList.add('show'); document.getElementById(id).classList.add('show'); }
  function kapatSheets(){ document.getElementById('sepetSheet').classList.remove('show'); document.getElementById('odemeSheet').classList.remove('show'); }
  function kapat(){ kapatSheets(); document.getElementById('kapak').classList.remove('show'); }
  function tipSec(t,el){ tip=t; document.querySelectorAll('#tipSeg .s').forEach(function(x){x.classList.remove('act')}); el.classList.add('act'); document.getElementById('adresWrap').style.display = t==='paket'?'block':'none'; }
  function odemeSec(o,el){ odeme=o; document.querySelectorAll('#odemeSeg .s').forEach(function(x){x.classList.remove('act')}); el.classList.add('act'); }
  function gonder(){
    var hata=document.getElementById('hata'), btn=document.getElementById('onayBtn');
    var ad=document.getElementById('i-ad').value.trim(), tel=document.getElementById('i-tel').value.trim(), adres=document.getElementById('i-adres').value.trim();
    if(!ad||!tel){ hata.textContent='Ad ve telefon zorunlu.'; hata.style.display='block'; return; }
    if(tip==='paket'&&!adres){ hata.textContent='Teslimat adresi gerekli.'; hata.style.display='block'; return; }
    hata.style.display='none'; btn.disabled=true; btn.textContent='Gönderiliyor…';
    var kalemler=[]; for(var k in sepet)kalemler.push({urun_id:parseInt(k),adet:sepet[k].adet});
    var body=new URLSearchParams({ad:ad,telefon:tel,tip:tip,adres:adres,odeme:odeme,not:document.getElementById('i-not').value.trim(),kalemler:JSON.stringify(kalemler)});
    fetch('/api/app/'+SUBE+'/siparis',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},body:body})
      .then(function(r){return r.json();})
      .then(function(j){ if(j.ok){ location.href=j.takip_url; } else { hata.textContent=j.hata||'Gönderilemedi'; hata.style.display='block'; btn.disabled=false; btn.textContent='Siparişi Onayla'; } })
      .catch(function(){ hata.textContent='Bağlantı hatası'; hata.style.display='block'; btn.disabled=false; btn.textContent='Siparişi Onayla'; });
  }
</script>
</body>
</html>

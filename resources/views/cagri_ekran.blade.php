<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gelen Çağrı Ekranı · {{ $sube->ad ?? 'ResteOS' }}</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --line:#232B42; --sessiz:#94A3B8; --yesil:#10B981; --amber:#F59E0B; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#E8EDF6;min-height:100dvh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px}
  .idle{text-align:center;color:var(--sessiz)}
  .idle .ring{font-size:64px;opacity:.5;animation:sway 2.4s ease-in-out infinite}
  @keyframes sway{0%,100%{transform:rotate(-8deg)}50%{transform:rotate(8deg)}}
  .idle p{margin-top:14px;font-size:16px}
  .idle small{color:#475569}
  .pop{display:none;width:100%;max-width:480px;background:var(--card);border:1px solid var(--line);border-radius:24px;overflow:hidden;box-shadow:0 40px 90px rgba(0,0,0,.5);animation:in .3s cubic-bezier(.2,.8,.2,1)}
  @keyframes in{from{opacity:0;transform:scale(.94) translateY(10px)}to{opacity:1;transform:none}}
  .pop.show{display:block}
  .bas{background:linear-gradient(135deg,var(--mor),var(--mavi));padding:22px 24px;display:flex;align-items:center;gap:16px}
  .bas .ic{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:28px;animation:sway 1s ease-in-out infinite}
  .bas .no{font-size:13px;color:#E9D5FF}
  .bas .tel{font-size:24px;font-weight:800;letter-spacing:.5px}
  .bas .sn{margin-left:auto;font-size:12px;color:#E9D5FF;background:rgba(0,0,0,.2);padding:4px 10px;border-radius:20px}
  .icerik{padding:22px 24px}
  .ad{font-size:26px;font-weight:800;margin-bottom:4px}
  .yeni{display:inline-block;background:rgba(245,158,11,.18);color:var(--amber);font-size:12px;font-weight:800;padding:4px 12px;border-radius:20px}
  .rozetler{display:flex;gap:10px;margin:16px 0}
  .rz{flex:1;background:#0F1424;border:1px solid var(--line);border-radius:14px;padding:12px;text-align:center}
  .rz b{display:block;font-size:20px;font-weight:800;color:#fff}
  .rz i{font-style:normal;font-size:11px;color:var(--sessiz)}
  .adr{background:#0F1424;border:1px solid var(--line);border-radius:12px;padding:12px 14px;font-size:14px;margin-bottom:14px}
  .grp{font-size:12px;color:var(--sessiz);font-weight:700;margin:4px 2px 8px;letter-spacing:.4px}
  .sip{display:flex;justify-content:space-between;font-size:13.5px;padding:8px 0;border-bottom:1px solid #1c2338}
  .sip:last-child{border:none}
  .btns{display:flex;gap:10px;margin-top:18px}
  .btns button{flex:1;border:none;border-radius:14px;padding:15px;font-size:15px;font-weight:800;cursor:pointer}
  .kapat{background:rgba(255,255,255,.08);color:#fff;border:1px solid var(--line)}
  .siparis{background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff}
  .test{position:fixed;bottom:14px;right:14px;font-size:12px;color:#475569;text-decoration:none;border:1px solid var(--line);padding:8px 12px;border-radius:20px}
</style>
</head>
<body>
  <div class="idle" id="idle">
    <div class="ring">📞</div>
    <p>Çağrı bekleniyor…</p>
    <small>Bir müşteri aradığında kartı burada açılır</small>
  </div>

  <div class="pop" id="pop">
    <div class="bas">
      <div class="ic">📞</div>
      <div><div class="no" id="p-no">GELEN ÇAĞRI</div><div class="tel" id="p-tel">—</div></div>
      <div class="sn" id="p-sn"></div>
    </div>
    <div class="icerik">
      <div id="p-adwrap"><div class="ad" id="p-ad">—</div></div>
      <div class="rozetler" id="p-rozet"></div>
      <div class="adr" id="p-adres" style="display:none"></div>
      <div id="p-sipwrap" style="display:none">
        <div class="grp">SON SİPARİŞLER</div>
        <div id="p-sip"></div>
      </div>
      <div class="btns">
        <button class="kapat" onclick="kapat()">Kapat</button>
        <button class="siparis" onclick="kapat()">🧾 Sipariş Oluştur</button>
      </div>
    </div>
  </div>

  <a class="test" href="/callerid-test" target="_blank">🧪 Test çağrısı üret</a>

<script>
  var aktifId = null;
  function para(v){ return new Intl.NumberFormat('tr').format(Math.round(v)) + 'TL'; }
  function goster(c){
    aktifId = c.id;
    document.getElementById('idle').style.display='none';
    var pop = document.getElementById('pop'); pop.classList.add('show');
    document.getElementById('p-tel').textContent = c.telefon;
    document.getElementById('p-sn').textContent = c.saniye + ' sn önce';
    var m = c.musteri;
    if (c.yeni_musteri || !m){
      document.getElementById('p-ad').innerHTML = 'Bilinmeyen numara <span class="yeni">YENİ MÜŞTERİ</span>';
      document.getElementById('p-rozet').style.display='none';
      document.getElementById('p-adres').style.display='none';
      document.getElementById('p-sipwrap').style.display='none';
    } else {
      document.getElementById('p-ad').textContent = m.ad;
      document.getElementById('p-rozet').style.display='flex';
      document.getElementById('p-rozet').innerHTML =
        '<div class="rz"><b>'+m.siparis_sayisi+'</b><i>sipariş</i></div>'+
        '<div class="rz"><b>'+para(m.toplam_harcama)+'</b><i>toplam</i></div>'+
        '<div class="rz"><b>'+m.puan+'</b><i>puan</i></div>';
      if (m.adres){ var a=document.getElementById('p-adres'); a.style.display='block'; a.innerHTML='📍 '+m.adres; }
      else document.getElementById('p-adres').style.display='none';
      if (m.son_siparisler && m.son_siparisler.length){
        var h=''; m.son_siparisler.forEach(function(s){ h+='<div class="sip"><span>#'+s.id+' · '+s.tarih+'</span><span>'+para(s.toplam)+'</span></div>'; });
        document.getElementById('p-sip').innerHTML=h; document.getElementById('p-sipwrap').style.display='block';
      } else document.getElementById('p-sipwrap').style.display='none';
    }
  }
  function kapat(){
    if (aktifId){ fetch('/api/callerid-goruldu',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'cagri_id='+aktifId}).catch(function(){}); }
    aktifId=null;
    document.getElementById('pop').classList.remove('show');
    document.getElementById('idle').style.display='block';
  }
  function yokla(){
    fetch('/api/callerid-aktif').then(function(r){return r.json();}).then(function(d){
      if(d.ok && d.cagri){ if(d.cagri.id !== aktifId) goster(d.cagri); else document.getElementById('p-sn').textContent = d.cagri.saniye+' sn önce'; }
      else if(!d.cagri && aktifId){ /* suresi doldu */ aktifId=null; document.getElementById('pop').classList.remove('show'); document.getElementById('idle').style.display='block'; }
    }).catch(function(){});
  }
  yokla(); setInterval(yokla, 3000);
</script>
</body>
</html>

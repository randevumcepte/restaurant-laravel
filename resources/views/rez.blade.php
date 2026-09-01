<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $sube->ad ?? 'Restoran' }} · Online Rezervasyon</title>
<style>
  :root{ --mor:#7C3AED; --mor2:#9D5DC8; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --line:#232B42; --ink:#F3E9EE; --sessiz:#94A3B8; --gold:#E9C46A; --yesil:#10B981; }
  *{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);min-height:100dvh;
    background:
      radial-gradient(900px 500px at 90% -8%, rgba(124,58,237,.28), transparent 60%),
      radial-gradient(700px 500px at 0% 108%, rgba(79,70,229,.22), transparent 58%), var(--bg);
    padding:22px 16px 40px}
  .wrap{max-width:460px;margin:0 auto}
  .brand{display:flex;align-items:center;gap:10px;justify-content:center;margin:6px 0 22px}
  .brand .toque{font-size:26px}
  .brand b{font-family:Georgia,serif;font-size:22px;font-weight:800;letter-spacing:.5px;
    background:linear-gradient(135deg,#F6DFA0,#E9C46A,#C9962F);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
  .hero{background:linear-gradient(150deg,rgba(124,58,237,.22),rgba(22,28,46,.7));border:1px solid var(--line);border-radius:22px;padding:22px 20px;margin-bottom:16px;text-align:center}
  .hero h1{font-size:22px;font-weight:800;margin-bottom:6px}
  .hero p{color:var(--sessiz);font-size:13.5px}
  form{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:20px}
  label{display:block;font-size:12.5px;font-weight:700;color:var(--sessiz);margin:14px 2px 7px;letter-spacing:.3px}
  label:first-child{margin-top:0}
  input,select,textarea{width:100%;background:rgba(0,0,0,.28);border:1px solid var(--line);color:#fff;font-size:15px;
    padding:14px 15px;border-radius:14px;outline:none;font-family:inherit}
  input:focus,select:focus,textarea:focus{border-color:var(--mor2)}
  textarea{resize:none;height:70px}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .kisiler{display:flex;gap:8px;flex-wrap:wrap;margin-top:2px}
  .kisi{flex:0 0 auto;min-width:46px;text-align:center;padding:11px 0;border-radius:12px;background:rgba(0,0,0,.28);
    border:1px solid var(--line);font-weight:800;font-size:15px;cursor:pointer;user-select:none}
  .kisi.act{background:linear-gradient(135deg,var(--mor),var(--mavi));border-color:transparent;box-shadow:0 6px 16px rgba(124,58,237,.5)}
  .gonder{width:100%;margin-top:20px;padding:16px;border:none;border-radius:16px;font-size:16px;font-weight:800;color:#fff;
    background:linear-gradient(135deg,var(--mor),var(--mavi));box-shadow:0 12px 26px rgba(124,58,237,.45);cursor:pointer}
  .gonder:disabled{opacity:.6}
  .ok{display:none;text-align:center;padding:34px 22px;background:var(--card);border:1px solid var(--line);border-radius:22px}
  .ok .ic{width:76px;height:76px;border-radius:50%;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;font-size:38px;margin:0 auto 16px}
  .ok h2{font-size:21px;margin-bottom:8px}
  .ok p{color:var(--sessiz);font-size:14.5px;line-height:1.5}
  .hata{display:none;background:rgba(244,63,94,.14);border:1px solid rgba(244,63,94,.4);color:#FCA5A5;padding:11px 14px;border-radius:12px;font-size:13.5px;margin-top:14px}
  .dip{text-align:center;color:#475569;font-size:11.5px;margin-top:20px}
</style>
</head>
<body>
<div class="wrap">
  <div class="brand"><span class="toque">👨‍🍳</span><b>{{ $sube->ad ?? 'ResteOS' }}</b></div>

  <div id="form-alan">
    <div class="hero">
      <h1>Masanızı ayırtın 🍽️</h1>
      <p>Birkaç saniyede online rezervasyon. Onay için sizi arayacağız.</p>
    </div>
    <form id="rez" onsubmit="gonder(event)">
      <label>Ad Soyad</label>
      <input id="ad" placeholder="Adınız" required>
      <label>Telefon</label>
      <input id="telefon" type="tel" inputmode="tel" placeholder="05__ ___ __ __" required>
      <label>Kişi Sayısı</label>
      <div class="kisiler" id="kisiler"></div>
      <div class="grid2">
        <div><label>Tarih</label><input id="tarih" type="date" required></div>
        <div><label>Saat</label><select id="saat" required></select></div>
      </div>
      <label>Not (opsiyonel)</label>
      <textarea id="not" placeholder="Doğum günü, pencere kenarı, bebek sandalyesi…"></textarea>
      <div class="hata" id="hata"></div>
      <button class="gonder" id="btn" type="submit">Rezervasyon Talebi Gönder</button>
    </form>
    <div class="dip">🔒 Bilgileriniz yalnızca rezervasyon için kullanılır.</div>
  </div>

  <div class="ok" id="ok">
    <div class="ic">✅</div>
    <h2>Talebiniz alındı!</h2>
    <p id="ok-mesaj"></p>
  </div>
</div>

<script>
  var kisi = 2;
  // Kişi butonları 1..10 + 10+
  (function(){
    var c = document.getElementById('kisiler'); var html='';
    for (var i=1;i<=10;i++) html += '<div class="kisi'+(i===2?' act':'')+'" data-k="'+i+'">'+i+'</div>';
    html += '<div class="kisi" data-k="12">10+</div>';
    c.innerHTML = html;
    c.querySelectorAll('.kisi').forEach(function(el){
      el.onclick=function(){ c.querySelectorAll('.kisi').forEach(function(x){x.classList.remove('act')}); el.classList.add('act'); kisi=parseInt(el.dataset.k); };
    });
  })();
  // Tarih: bugünden itibaren
  (function(){
    var t = document.getElementById('tarih');
    var d = new Date(); var iso = d.toISOString().slice(0,10);
    t.value = iso; t.min = iso;
  })();
  // Saat: 12:00 - 23:30 yarım saat
  (function(){
    var s = document.getElementById('saat'); var html='';
    for (var h=12;h<=23;h++){ for (var m=0;m<60;m+=30){ var v=(h<10?'0':'')+h+':'+(m===0?'00':'30'); html+='<option'+(v==='19:30'?' selected':'')+'>'+v+'</option>'; } }
    s.innerHTML = html;
  })();
  function gonder(e){
    e.preventDefault();
    var btn=document.getElementById('btn'), hata=document.getElementById('hata');
    hata.style.display='none'; btn.disabled=true; btn.textContent='Gönderiliyor…';
    var body = new URLSearchParams({
      ad: document.getElementById('ad').value.trim(),
      telefon: document.getElementById('telefon').value.trim(),
      kisi: kisi, tarih: document.getElementById('tarih').value,
      saat: document.getElementById('saat').value, not: document.getElementById('not').value.trim()
    });
    fetch(location.pathname, { method:'POST', headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}, body: body })
      .then(function(r){ return r.json().then(function(j){ return {ok:r.ok, j:j}; }); })
      .then(function(res){
        if (res.ok && res.j.ok===1){
          document.getElementById('form-alan').style.display='none';
          document.getElementById('ok-mesaj').textContent = res.j.mesaj || 'Rezervasyon talebiniz alındı.';
          document.getElementById('ok').style.display='block';
          window.scrollTo(0,0);
        } else {
          hata.textContent = (res.j && res.j.hata) ? res.j.hata : 'Gönderilemedi, tekrar deneyin.';
          hata.style.display='block'; btn.disabled=false; btn.textContent='Rezervasyon Talebi Gönder';
        }
      })
      .catch(function(){ hata.textContent='Bağlantı hatası, tekrar deneyin.'; hata.style.display='block'; btn.disabled=false; btn.textContent='Rezervasyon Talebi Gönder'; });
  }
</script>
</body>
</html>

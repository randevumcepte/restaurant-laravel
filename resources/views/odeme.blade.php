<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#7C3AED">
<title>Ödeme · {{ $sube->ad ?? 'ResteOS' }}</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --ink:#14121A; --gri:#6B7280; --line:#EEE9F5; --bg:#F5F4FB; --yesil:#10B981; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:18px}
  .card{width:100%;max-width:420px;background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 30px 70px rgba(80,50,160,.18)}
  .bas{background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;padding:24px;text-align:center}
  .bas .l{font-size:13px;color:#E9D5FF}
  .bas .tut{font-size:38px;font-weight:800;margin-top:2px}
  .bas .no{font-size:12px;color:#E9D5FF;margin-top:4px}
  .govde{padding:22px}
  label{display:block;font-size:12px;font-weight:700;color:var(--gri);margin:12px 2px 6px}
  input{width:100%;border:1px solid var(--line);border-radius:12px;padding:14px;font-size:16px;outline:none;font-family:inherit;letter-spacing:.5px}
  input:focus{border-color:var(--mor)}
  .ikili{display:flex;gap:10px}
  .ode{width:100%;margin-top:20px;border:none;border-radius:14px;padding:16px;background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;font-size:17px;font-weight:800;cursor:pointer}
  .ode:disabled{opacity:.6}
  .guv{text-align:center;color:var(--gri);font-size:11.5px;margin-top:14px}
  .test{background:#FFF7E6;color:#B45309;font-size:11.5px;text-align:center;padding:8px;border-radius:10px;margin-top:12px}
  .ok{display:none;text-align:center;padding:40px 24px}
  .ok .ic{width:84px;height:84px;border-radius:50%;background:rgba(16,185,129,.15);display:flex;align-items:center;justify-content:center;font-size:42px;margin:0 auto 16px}
  .ok h2{font-size:22px;margin-bottom:6px}.ok p{color:var(--gri)}
</style>
</head>
<body>
  <div class="card">
    <div class="bas">
      <div class="l">{{ $sube->ad ?? 'Restoran' }} · Ödeme</div>
      <div class="tut">{{ number_format($islem->tutar, 0, ',', '.') }}TL</div>
      <div class="no">🔒 Güvenli ödeme</div>
    </div>
    <div class="govde" id="form">
      <label>Kart Numarası</label>
      <input id="kno" inputmode="numeric" maxlength="19" placeholder="0000 0000 0000 0000" oninput="fmt(this)">
      <label>Kart Üzerindeki İsim</label>
      <input id="kad" placeholder="Ad Soyad">
      <div class="ikili">
        <div style="flex:1"><label>Son Kullanma</label><input id="skt" maxlength="5" placeholder="AA/YY" oninput="fmtSkt(this)"></div>
        <div style="width:110px"><label>CVV</label><input id="cvv" inputmode="numeric" maxlength="4" placeholder="123"></div>
      </div>
      <button class="ode" id="odeBtn" onclick="ode()">{{ number_format($islem->tutar, 0, ',', '.') }}TL Öde</button>
      @if($islem->saglayici === 'simulasyon')
        <div class="test">🧪 Test modu — gerçek kart çekimi yapılmaz. Canlı ödeme için İyzico/PayTR anahtarı girilince aktif olur.</div>
      @endif
      <div class="guv">256-bit SSL · Kart bilgileriniz saklanmaz</div>
    </div>
    <div class="ok" id="ok">
      <div class="ic">✅</div>
      <h2>Ödeme başarılı!</h2>
      <p>Teşekkürler, siparişiniz onaylandı.</p>
    </div>
  </div>
<script>
  var TOKEN=@json($islem->token);
  function fmt(el){ el.value=el.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim(); }
  function fmtSkt(el){ var v=el.value.replace(/\D/g,''); if(v.length>=3)v=v.slice(0,2)+'/'+v.slice(2,4); el.value=v; }
  function ode(){
    var b=document.getElementById('odeBtn');
    if(document.getElementById('kno').value.replace(/\s/g,'').length<12){ alert('Kart numarasını girin'); return; }
    b.disabled=true; b.textContent='İşleniyor…';
    fetch('/ode/'+TOKEN+'/tamamla',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:''})
      .then(function(r){return r.json();})
      .then(function(j){ if(j.ok){ document.getElementById('form').style.display='none'; document.getElementById('ok').style.display='block'; } else { b.disabled=false; b.textContent='Öde'; alert(j.hata||'Ödeme başarısız'); } })
      .catch(function(){ b.disabled=false; b.textContent='Öde'; alert('Bağlantı hatası'); });
  }
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<meta name="theme-color" content="#7C3AED">
<title>Ödeme · {{ $sube->ad ?? 'ResteOS' }}</title>
<script>
  // Odeme sayfasi YENILENIRSE (nerede olursa olsun) -> menuye (dashboard) don. Ilk aciliste kalir.
  (function(){
    try{
      var e = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]);
      var yenilendi = e ? (e.type === 'reload') : (performance.navigation && performance.navigation.type === 1);
      if(yenilendi){ location.replace(@json(!empty($masaId) ? url('/masa/'.$masaId) : url('/'))); }
    }catch(_){}
  })();
</script>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --ink:#14121A; --gri:#6B7280; --line:#EEE9F5; --bg:#F5F4FB; --yesil:#10B981; --aksan:{{ $aksan ?? '#C41E3A' }}; --aksan3:{{ $aksan3 ?? '#C41E3A' }}; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100dvh;display:flex;align-items:center;justify-content:center;padding:18px;padding-bottom:calc(104px + env(safe-area-inset-bottom))}
  /* Alt menu — her sayfada sabit (menuyle ayni gorunum) */
  .altnav{position:fixed;left:0;right:0;bottom:0;z-index:60;display:flex;align-items:flex-end;justify-content:space-around;padding:8px 8px calc(8px + env(safe-area-inset-bottom));background:rgba(255,255,255,.96);backdrop-filter:blur(14px);border-top:1px solid var(--line)}
  .altnav a{flex:1;text-decoration:none;color:var(--gri);font-size:10.5px;font-weight:700;display:flex;flex-direction:column;align-items:center;gap:3px;padding:5px 0}
  .altnav a span{font-size:19px}.altnav a.act{color:var(--mor)}
  .altnav .qr{flex:0 0 auto}
  .altnav .qr .qi{position:relative;width:64px;height:64px;margin-top:-28px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:linear-gradient(135deg,var(--aksan),var(--aksan3));box-shadow:0 10px 24px rgba(139,59,234,.6),0 0 0 5px rgba(255,255,255,.96)}
  .altnav .qr .qi svg{width:26px;height:26px;fill:#fff;position:relative;z-index:1}
  /* PATLAYAN DALGA — menüdeki ile birebir (beyaz halka) */
  .altnav .qr .qi::before,.altnav .qr .qi::after{content:'';position:absolute;inset:0;border-radius:50%;border:2.5px solid rgba(255,255,255,.9);pointer-events:none;animation:qrDalga 2.2s ease-out infinite}
  .altnav .qr .qi::after{animation-delay:1.1s}
  @keyframes qrDalga{0%{transform:scale(1);opacity:.75}100%{transform:scale(2);opacity:0}}
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

  @php $menuUrl = !empty($masaId) ? url('/masa/'.$masaId) : url('/'); @endphp
  <nav class="altnav">
    <a href="{{ $menuUrl }}"><span>📋</span>Menü</a>
    <a href="{{ $menuUrl }}?sepet=1"><span>🧾</span>Siparişlerim</a>
    <a class="qr" href="{{ $menuUrl }}?ai=1" aria-label="Asistan"><span class="qi"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15a3 3 0 0 0 3-3V6a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V22h2v-3.08A7 7 0 0 0 19 12h-2z"/></svg></span></a>
    <a href="{{ $menuUrl }}?cagir=1"><span>🔔</span>Çağır</a>
    <a class="act" href="javascript:void(0)"><span>💳</span>Öde</a>
  </nav>
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

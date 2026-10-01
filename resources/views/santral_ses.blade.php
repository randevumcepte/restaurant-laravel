<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Santral — Ses Seçimi</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:640px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:16px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px;margin-bottom:16px}
  label{display:block;font-size:13px;font-weight:600;margin:12px 0 6px}
  input[type=text],input[type=password],textarea,select{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;font-size:14px;outline:none;background:#f8fafc;font-family:inherit}
  textarea{min-height:56px;resize:vertical}
  input:focus,textarea:focus,select:focus{border-color:var(--indigo);background:#fff}
  .hint{font-size:12px;color:var(--sub);margin-top:5px;line-height:1.5}
  .satir{display:flex;gap:10px;align-items:center;padding:10px;border:1px solid var(--line);border-radius:10px;margin-bottom:8px;background:#f8fafc}
  .satir .ad{flex:1;font-weight:700;font-size:14px}
  .satir .cins{font-size:11px;color:var(--sub)}
  .dinle{border:none;border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2))}
  .dinle:disabled{opacity:.5}
  .sec{border:1px solid var(--ok);color:var(--ok);background:#f0fdf4;border-radius:10px;padding:9px 12px;font-weight:700;cursor:pointer;font-size:13px}
  #durum{font-size:13px;color:var(--sub);margin-top:6px;min-height:18px}
  .rate{display:flex;align-items:center;gap:8px}
  .sectilen{background:#ecfdf5;border:1px solid #86efac;border-radius:10px;padding:12px;margin-top:10px;font-weight:700;color:#166534;display:none}
  code{background:#f1f5f9;border:1px solid var(--line);border-radius:5px;padding:1px 6px;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">🔊</div>
    <div><h1>AI Santral — Ses Seçimi</h1><p>Sesleri dinleyip en doğal olanı seçin. Seçtiğiniz ses adını köprü .env'inde <code>TTS_VOICE</code>'a yazın.</p></div>
  </div>

  <div class="card">
    @if(!$envVar)
    <label>Google TTS API anahtarı</label>
    <input type="password" id="key" placeholder="Köprü .env'indeki GOOGLE_TTS_API_KEY">
    <div class="hint">Sunucu .env'inde anahtar yok; buraya yapıştırın (sadece dinleme için kullanılır).</div>
    @else
    <input type="hidden" id="key" value="">
    <div class="hint">✅ Sunucudaki anahtar kullanılacak.</div>
    @endif

    <label>Denenecek cümle</label>
    <textarea id="metin">Siparişinizi ilettim, en kısa sürede hazırlayıp göndereceğiz. Afiyet olsun, iyi günler.</textarea>

    <label>Konuşma hızı: <span id="rateV">1.0</span></label>
    <div class="rate"><input type="range" id="rate" min="0.8" max="1.3" step="0.05" value="1.0" oninput="rateV.textContent=this.value" style="width:100%"></div>

    <div id="durum"></div>
    <div class="sectilen" id="sectilen"></div>
  </div>

  <div class="card">
    <h3 style="font-size:14px;color:var(--sub);margin-bottom:10px;text-transform:uppercase">Sesler — dinle, beğendiğini seç</h3>
    <div id="sesler"></div>

    <label>Listede yok mu? Ses adı yazıp dene (ör. Chirp3-HD)</label>
    <div style="display:flex;gap:8px"><input type="text" id="ozelAd" placeholder="tr-TR-Chirp3-HD-Charon"><button class="dinle" onclick="dinle(ozelAd.value.trim())">Dinle</button></div>
  </div>
</div>

<audio id="player"></audio>
<script>
  const SESLER=[
    {ad:'tr-TR-Wavenet-B',cins:'Erkek · WaveNet'},
    {ad:'tr-TR-Wavenet-E',cins:'Erkek · WaveNet (mevcut)'},
    {ad:'tr-TR-Standard-E',cins:'Erkek · Standard'},
    {ad:'tr-TR-Standard-B',cins:'Erkek · Standard'},
    {ad:'tr-TR-Wavenet-D',cins:'Kadın · WaveNet'},
    {ad:'tr-TR-Wavenet-C',cins:'Kadın · WaveNet'},
    {ad:'tr-TR-Wavenet-A',cins:'Kadın · WaveNet'},
    {ad:'tr-TR-Chirp3-HD-Charon',cins:'Erkek · Chirp3-HD (çok doğal)'},
    {ad:'tr-TR-Chirp3-HD-Aoede',cins:'Kadın · Chirp3-HD (çok doğal)'},
  ];
  const durum=document.getElementById('durum'), player=document.getElementById('player');
  function form(o){return Object.entries(o).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&');}

  const kap=document.getElementById('sesler');
  SESLER.forEach(s=>{
    const d=document.createElement('div'); d.className='satir';
    d.innerHTML=`<div><div class="ad">${s.ad}</div><div class="cins">${s.cins}</div></div>
      <button class="dinle" onclick="dinle('${s.ad}')">▶ Dinle</button>
      <button class="sec" onclick="sec('${s.ad}')">Bunu seç</button>`;
    kap.appendChild(d);
  });

  async function dinle(voice){
    if(!voice){durum.textContent='Ses adı boş';return;}
    durum.textContent='⏳ '+voice+' hazırlanıyor…'; durum.style.color='#64748b';
    try{
      const body=form({key:document.getElementById('key').value, voice, metin:document.getElementById('metin').value, rate:document.getElementById('rate').value});
      const r=await fetch('/api/santral-ses/dene',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body});
      const d=await r.json();
      if(!d.ok){durum.textContent='❌ '+d.hata; durum.style.color='#ef4444'; return;}
      player.src='data:audio/mp3;base64,'+d.audio; player.play();
      durum.textContent='▶ Çalıyor: '+voice; durum.style.color='#16a34a';
    }catch(e){durum.textContent='❌ '+e.message; durum.style.color='#ef4444';}
  }
  function sec(voice){
    const el=document.getElementById('sectilen');
    el.style.display='block';
    el.innerHTML='Seçtin: <code>'+voice+'</code> — köprü .env dosyasına yaz: <code>TTS_VOICE='+voice+'</code> sonra köprüyü yeniden başlat.';
  }
</script>
</body>
</html>

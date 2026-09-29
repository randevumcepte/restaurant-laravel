<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Santral — Test (yazışarak)</title>
<style>
  :root{--mor:#7C3AED;--mor2:#9D5DC8;--bg:#0B1020;--card:#161C2E;--line:#232B42;--us:#243056;--ai:#1c2740}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:#E8EDF6;min-height:100dvh;display:flex;flex-direction:column;align-items:center;padding:16px}
  .wrap{width:100%;max-width:560px;display:flex;flex-direction:column;height:calc(100dvh - 32px)}
  .bas{display:flex;align-items:center;gap:12px;padding:6px 4px 14px}
  .bas .ic{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--mor),var(--mor2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:17px}.bas small{color:#8b93a7;font-size:12px}
  .durum{margin-left:auto;font-size:11px;color:#8b93a7;text-align:right}
  #log{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:10px;padding:8px 2px}
  .msg{max-width:82%;padding:11px 14px;border-radius:16px;font-size:14.5px;line-height:1.5;white-space:pre-wrap}
  .ai{background:var(--ai);border:1px solid var(--line);border-top-left-radius:5px;align-self:flex-start}
  .us{background:linear-gradient(135deg,var(--mor),var(--mor2));border-top-right-radius:5px;align-self:flex-end}
  .aks{align-self:center;font-size:11px;font-weight:700;color:#c9b6f2;background:rgba(124,58,237,.16);border:1px solid #3a2a63;padding:4px 12px;border-radius:20px}
  .gir{display:flex;gap:8px;padding-top:10px}
  .gir input{flex:1;background:var(--card);border:1px solid var(--line);color:#fff;border-radius:14px;padding:14px;font-size:15px;outline:none}
  .gir button{border:none;border-radius:14px;padding:0 20px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--mor),var(--mor2));cursor:pointer}
  .gir button:disabled{opacity:.5}
  .oneri{display:flex;gap:6px;flex-wrap:wrap;padding:6px 2px 0}
  .oneri span{font-size:12px;color:#c9b6f2;border:1px solid #3a2a63;border-radius:20px;padding:5px 10px;cursor:pointer}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">📞</div>
    <div><h1>AI Santral — Test</h1><small>Asterisk olmadan yazışarak dene (Şube #{{ $subeId }})</small></div>
    <div class="durum" id="durum">bağlanıyor…</div>
  </div>
  <div id="log"></div>
  <div class="oneri">
    <span onclick="hizli(this)">Bu akşam 4 kişilik yer var mı?</span>
    <span onclick="hizli(this)">Kaçta açıksınız?</span>
    <span onclick="hizli(this)">Paket sipariş vermek istiyorum</span>
  </div>
  <div class="gir">
    <input id="giris" placeholder="Mesaj yazın…" autocomplete="off" onkeydown="if(event.key==='Enter')gonder()">
    <button id="btn" onclick="gonder()">Gönder</button>
  </div>
</div>
<script>
  const SUBE = {{ $subeId }};
  let oturum = null, mesgul = false;
  const log = document.getElementById('log'), durum = document.getElementById('durum'), giris = document.getElementById('giris'), btn = document.getElementById('btn');

  function ekle(metin, kim){ const d=document.createElement('div'); d.className='msg '+kim; d.textContent=metin; log.appendChild(d); log.scrollTop=log.scrollHeight; }
  function aksiyon(a){ if(!a)return; const map={rezervasyon:'✅ Rezervasyon oluşturuldu',siparis:'🛒 Sipariş alındı (Faz 4: adisyona düşecek)',aktar:'➡️ Yetkiliye aktarılıyor',veda:'👋 Görüşme bitti'}; const d=document.createElement('div'); d.className='aks'; d.textContent=map[a]||a; log.appendChild(d); log.scrollTop=log.scrollHeight; }
  function hizli(el){ giris.value=el.textContent; gonder(); }

  async function baslat(){
    try{
      const r = await fetch('/api/santral/baslat',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'sube_id='+SUBE});
      const d = await r.json();
      oturum = d.oturum_id; durum.textContent='hat açık · oturum #'+oturum;
      ekle(d.karsilama,'ai');
    }catch(e){ durum.textContent='başlatılamadı'; }
  }
  async function gonder(){
    const t = giris.value.trim(); if(!t||mesgul||!oturum) return;
    mesgul=true; btn.disabled=true; giris.value='';
    ekle(t,'us');
    try{
      const r = await fetch('/api/santral/konus',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'oturum_id='+oturum+'&metin='+encodeURIComponent(t)});
      const d = await r.json();
      if(d.ok){ ekle(d.cevap,'ai'); aksiyon(d.aksiyon); if(d.bitir) durum.textContent='görüşme kapandı'; }
      else ekle('Hata: '+(d.hata||'?'),'ai');
    }catch(e){ ekle('Bağlantı hatası','ai'); }
    mesgul=false; btn.disabled=false; giris.focus();
  }
  baslat();
</script>
</body>
</html>

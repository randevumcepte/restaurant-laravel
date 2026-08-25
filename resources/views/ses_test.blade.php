<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Erkek Ses Seçimi · RestoOS</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --yesil:#22C55E; }
  *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  html,body{ margin:0; background:var(--bg); color:#fff; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  .wrap{ max-width:640px; margin:0 auto; padding:18px 14px 40px; }
  h1{ font-size:19px; margin:6px 0 2px; }
  .alt{ color:#94A3B8; font-size:13px; margin:0 0 16px; }
  .uyari{ background:#3F1D1D; border:1px solid #7F1D1D; color:#FCA5A5; font-size:13px; padding:10px 12px; border-radius:10px; margin-bottom:14px; }
  .ornek{ background:var(--card); border:1px solid #2D3752; border-radius:12px; padding:11px 13px; font-size:13.5px; color:#CBD5E1; margin-bottom:16px; }
  .ornek b{ color:#fff; }
  .kart{ background:var(--card); border:1px solid #2D3752; border-radius:14px; padding:13px 14px; margin-bottom:11px; display:flex; align-items:center; gap:12px; transition:border-color .2s; }
  .kart.secili{ border-color:var(--yesil); box-shadow:0 0 0 1px var(--yesil) inset; }
  .kart .info{ flex:1; min-width:0; }
  .kart .isim{ font-weight:800; font-size:15px; }
  .kart .aciklama{ color:#94A3B8; font-size:12.5px; margin-top:2px; }
  .kart .rozet{ display:inline-block; background:rgba(34,197,94,.18); color:#4ADE80; font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px; margin-top:5px; }
  button{ border:none; border-radius:10px; font-weight:700; font-size:13.5px; padding:10px 14px; color:#fff; }
  .dinle{ background:linear-gradient(135deg,var(--mor),var(--mavi)); min-width:78px; }
  .dinle.calior{ background:#F43F5E; }
  .sec{ background:#25304A; color:#C4B5FD; }
  .sec.aktif{ background:var(--yesil); color:#062012; }
  .durum{ text-align:center; color:#64748B; font-size:12px; margin-top:18px; min-height:16px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>🎙️ Erkek Ses Seçimi</h1>
  <p class="alt">Müşteri masa asistanının kullanacağı sesi seç. Her sesi <b>Dinle</b> ile dene, beğendiğine <b>Seç</b> de.</p>

  @if(!$anahtarVar)
    <div class="uyari">⚠️ Sunucuda <b>GOOGLE_TTS_API_KEY</b> tanımlı değil — sesler çalmaz. Anahtarı .env'e ekleyip <code>php artisan config:clear</code> çalıştır.</div>
  @endif

  <div class="ornek">Örnek cümle: <b>“Hoş geldiniz! Bugünün önerisi Izgara Köfte, iki yüz kırk beş lira. Afiyet olsun.”</b></div>

  @foreach($sesler as $s)
    <div class="kart" data-ses="{{ $s['ses'] }}" id="kart-{{ $loop->index }}">
      <div class="info">
        <div class="isim">{{ $s['ad'] }}</div>
        <div class="aciklama">{{ $s['not'] }}</div>
        <span class="rozet" style="display:none">✓ Seçili</span>
      </div>
      <button class="dinle" onclick="dinle('{{ $s['ses'] }}', this)">▶ Dinle</button>
      <button class="sec" onclick="sec('{{ $s['ses'] }}', this)">Seç</button>
    </div>
  @endforeach

  <div class="durum" id="durum"></div>
</div>

<script>
const ORNEK = 'Hoş geldiniz! Bugünün önerisi Izgara Köfte, 245 lira. Afiyet olsun.';
const SECILI = @json($secili);
let calan = new Audio();
let aktifBtn = null;
const durum = document.getElementById('durum');

function kartlariGuncelle(){
  document.querySelectorAll('.kart').forEach(k=>{
    const secim = k.getAttribute('data-ses')===SECILI;
    k.classList.toggle('secili', secim);
    k.querySelector('.rozet').style.display = secim ? 'inline-block' : 'none';
    const sb = k.querySelector('.sec');
    sb.classList.toggle('aktif', secim);
    sb.textContent = secim ? '✓ Seçili' : 'Seç';
  });
}

async function dinle(ses, btn){
  try{ calan.pause(); }catch(_){}
  if(aktifBtn){ aktifBtn.classList.remove('calior'); aktifBtn.textContent='▶ Dinle'; }
  btn.classList.add('calior'); btn.textContent='● Yükleniyor'; aktifBtn=btn; durum.textContent='';
  try{
    const r = await fetch('/api/tts', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({metin:ORNEK, ses})});
    const j = await r.json();
    if(j.basarili && j.url){
      calan = new Audio(j.url);
      calan.onended = ()=>{ btn.classList.remove('calior'); btn.textContent='▶ Dinle'; };
      calan.play().catch(()=>{ btn.classList.remove('calior'); btn.textContent='▶ Dinle'; });
      btn.textContent='● Çalıyor';
    } else {
      btn.classList.remove('calior'); btn.textContent='▶ Dinle';
      durum.textContent = j.anahtar_var===false ? 'Sunucuda ses anahtarı yok.' : 'Bu ses üretilemedi (Google\'da bu isim olmayabilir).';
    }
  }catch(e){ btn.classList.remove('calior'); btn.textContent='▶ Dinle'; durum.textContent='Bağlantı hatası.'; }
}

async function sec(ses, btn){
  durum.textContent='Kaydediliyor…';
  try{
    const r = await fetch('/ses-sec?ses='+encodeURIComponent(ses), {method:'GET'});
    const j = await r.json();
    if(j.ok){ window.SECILI_YENI = ses; document.querySelectorAll('.kart').forEach(k=>{
        const s = k.getAttribute('data-ses')===ses;
        k.classList.toggle('secili', s);
        k.querySelector('.rozet').style.display = s?'inline-block':'none';
        const sb=k.querySelector('.sec'); sb.classList.toggle('aktif', s); sb.textContent = s?'✓ Seçili':'Seç';
      });
      durum.textContent='✓ Kaydedildi. Müşteri asistanı artık bu sesi kullanacak.';
    } else durum.textContent = j.mesaj || 'Kaydedilemedi.';
  }catch(e){ durum.textContent='Bağlantı hatası.'; }
}

kartlariGuncelle();
</script>
</body>
</html>

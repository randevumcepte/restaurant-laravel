<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>WhatsApp Kontör — Admin</title>
<style>
  :root{ --bg:#0B1020; --card:#161C2E; --line:#2D3752; --ink:#F1F5F9; --sub:#94A3B8; --yesil:#10B981; --mor:#7C3AED; }
  *{ box-sizing:border-box; } body{ margin:0; background:var(--bg); color:var(--ink); font-family:system-ui,Segoe UI,Roboto,sans-serif; }
  .wrap{ max-width:1000px; margin:0 auto; padding:20px 16px 60px; }
  h1{ font-size:22px; margin:6px 0 2px; } .mut{ color:var(--sub); font-size:13px; }
  table{ width:100%; border-collapse:collapse; margin-top:16px; background:var(--card); border-radius:12px; overflow:hidden; font-size:13px; }
  th,td{ padding:10px 12px; text-align:left; border-bottom:1px solid #1f2740; }
  th{ background:#0F1424; color:var(--sub); font-size:11px; text-transform:uppercase; }
  .btn{ background:var(--mor); color:#fff; border:none; border-radius:8px; padding:7px 12px; cursor:pointer; font-weight:600; font-size:12px; }
  .pill{ padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
  .b-bekliyor{ background:#3b2f12; color:#f6c04a; } .b-yuklendi{ background:#123a2a; color:#6ee7b7; }
  input{ background:#0F1424; border:1px solid var(--line); color:var(--ink); border-radius:8px; padding:7px 10px; }
  .key{ margin-top:10px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>💰 WhatsApp Kontör — Admin</h1>
  <div class="mut">Kontör talepleri. "Yükle" → şubenin bakiyesine ekler + talebi "yüklendi" yapar.</div>
  <div class="key">
    <label class="mut">Admin anahtarı:</label>
    <input id="key" value="{{ $key }}" placeholder="RESTEOS_ADMIN_KEY" style="width:220px">
    <button class="btn" onclick="yukle()">Talepleri Getir</button>
  </div>
  <table id="tbl"><thead><tr><th>Tarih</th><th>Şube</th><th>Paket</th><th>Fiyat</th><th>Adet</th><th>Not</th><th>Durum</th><th>İşlem</th></tr></thead><tbody id="tb"></tbody></table>
</div>
<script>
const $=s=>document.querySelector(s);
function k(){ return $('#key').value.trim(); }
async function yukle(){
  const r = await fetch('/api/wa/kontor-talepler?admin_key='+encodeURIComponent(k())+'&_='+Date.now(), {cache:'no-store'});
  const j = await r.json();
  const tb = $('#tb'); tb.innerHTML='';
  if(!j.ok){ tb.innerHTML='<tr><td colspan="8">Yetkisiz veya hata.</td></tr>'; return; }
  (j.talepler||[]).forEach(t=>{
    const tr = document.createElement('tr');
    const d = (t.created_at||'').replace('T',' ').substring(0,16);
    tr.innerHTML = '<td>'+d+'</td><td>'+(t.sube_ad||t.sube_id)+'</td><td>'+t.paket_ad+'</td><td>'+t.fiyat+'</td><td>'+(Number(t.adet)||0).toLocaleString('tr-TR')+'</td><td>'+(t.iletisim||'-')+'</td>'
      + '<td><span class="pill b-'+t.durum+'">'+t.durum+'</span></td>'
      + '<td>'+(t.durum==='bekliyor' ? '<button class="btn" data-id="'+t.id+'" data-sube="'+t.sube_id+'" data-adet="'+t.adet+'">Yükle</button>' : '✓')+'</td>';
    tb.appendChild(tr);
  });
  tb.querySelectorAll('button').forEach(b=>b.onclick=()=>kontorYukle(b.dataset.id,b.dataset.sube,b.dataset.adet));
}
async function kontorYukle(talepId,sube,adet){
  if(!confirm(adet+' kontör yüklensin mi?')) return;
  const r = await fetch('/api/wa/kontor-yukle', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({admin_key:k(),sube,adet,talep_id:talepId})});
  const j = await r.json();
  alert(j.ok ? ('Yüklendi. Yeni bakiye: '+j.bakiye) : ('Hata: '+(j.mesaj||'')));
  yukle();
}
if(k()) yukle();
</script>
</body>
</html>

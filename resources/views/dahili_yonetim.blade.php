<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dahili Yönetimi</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:760px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:18px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px;margin-bottom:16px}
  .card h2{font-size:14px;color:var(--sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px}
  input[type=text],input[type=password]{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;font-size:15px;outline:none;background:#f8fafc}
  input:focus{border-color:var(--indigo);background:#fff}
  .ekleRow{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap}
  .ekleRow .f{flex:1;min-width:120px}.ekleRow label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--sub)}
  .btn{border:none;border-radius:10px;padding:11px 16px;font-weight:700;cursor:pointer;font-size:14px}
  .btn.pri{color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2))}
  .btn.sec{background:#eef2ff;color:var(--indigo);border:1px solid #c7d2fe}
  .btn.dgr{background:#fee2e2;color:var(--red)}
  table{width:100%;border-collapse:collapse}
  th,td{text-align:left;padding:11px 8px;border-bottom:1px solid var(--line);font-size:14px}
  th{color:var(--sub);font-size:12px;text-transform:uppercase}
  td .aksi{display:flex;gap:6px;justify-content:flex-end}
  .uyari{background:#fef9c3;border:1px solid #fde047;color:#854d0e;padding:14px 16px;border-radius:12px;margin-bottom:16px}
  .hata{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:10px;margin-top:10px;font-size:13px;white-space:pre-wrap;word-break:break-all;font-family:monospace;display:none}
  .link{color:var(--indigo);font-weight:700;text-decoration:none}
  .bos{color:var(--sub);text-align:center;padding:20px}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">☎️</div>
    <div><h1>Dahili Yönetimi</h1><p>SIP dahililerini panelden oluşturun/silin. FreePBX'e yazılır, telefonlar buraya kayıt olur.</p></div>
  </div>

  @if(!$ayarli)
    <div class="uyari">⚠️ Önce FreePBX API bağlantısını ayarlayın. <a class="link" href="/freepbx-ayar">→ FreePBX API Ayarı</a></div>
  @else
  <div class="card">
    <h2>Yeni dahili ekle</h2>
    <div class="ekleRow">
      <div class="f" style="max-width:130px"><label>Numara</label><input type="text" id="e_num" placeholder="101"></div>
      <div class="f"><label>Ad</label><input type="text" id="e_ad" placeholder="Kasa"></div>
      <div class="f"><label>SIP şifresi</label><input type="text" id="e_sif" placeholder="güçlü bir şifre"></div>
      <button class="btn pri" onclick="ekle()">+ Ekle</button>
    </div>
    <div class="hata" id="e_hata"></div>
  </div>

  <div class="card">
    <h2>Dahililer</h2>
    <table>
      <thead><tr><th>Numara</th><th>Ad</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="liste"><tr><td colspan="3" class="bos">Yükleniyor…</td></tr></tbody>
    </table>
    <div class="hata" id="l_hata"></div>
  </div>
  @endif

  <a class="link" href="/freepbx-ayar">← FreePBX API Ayarı</a>
</div>

@if($ayarli)
<script>
  const listeEl=document.getElementById('liste');
  function form(obj){ return Object.entries(obj).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&'); }
  function gosterHata(id,msg){ const el=document.getElementById(id); el.textContent=msg; el.style.display=msg?'block':'none'; }

  async function yukle(){
    listeEl.innerHTML='<tr><td colspan=3 class="bos">Yükleniyor…</td></tr>';
    try{
      const r=await fetch('/api/dahili/liste',{method:'POST'}); const d=await r.json();
      if(!d.ok){ listeEl.innerHTML='<tr><td colspan=3 class="bos">Liste alınamadı</td></tr>'; gosterHata('l_hata', d.hata||''); return; }
      gosterHata('l_hata', d.hata||''); // sema uyusmazsa hatayi yine goster (liste bos olabilir)
      if(!d.liste.length){ listeEl.innerHTML='<tr><td colspan=3 class="bos">Kayıtlı dahili yok</td></tr>'; return; }
      listeEl.innerHTML='';
      d.liste.forEach(x=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td><b>${x.numara}</b></td><td>${x.ad||''}</td>
          <td><div class="aksi">
            <button class="btn sec" onclick="sifre('${x.numara}')">Şifre</button>
            <button class="btn dgr" onclick="sil('${x.numara}')">Sil</button>
          </div></td>`;
        listeEl.appendChild(tr);
      });
    }catch(e){ listeEl.innerHTML='<tr><td colspan=3 class="bos">Hata</td></tr>'; gosterHata('l_hata', e.message); }
  }

  async function ekle(){
    const numara=document.getElementById('e_num').value.trim();
    const ad=document.getElementById('e_ad').value.trim();
    const sifre=document.getElementById('e_sif').value.trim();
    gosterHata('e_hata','');
    if(!numara||!sifre){ gosterHata('e_hata','Numara ve şifre zorunlu.'); return; }
    try{
      const r=await fetch('/api/dahili/ekle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara,ad,sifre})});
      const d=await r.json();
      if(d.ok){ document.getElementById('e_num').value='';document.getElementById('e_ad').value='';document.getElementById('e_sif').value=''; yukle(); }
      else gosterHata('e_hata', (d.hata||'Eklenemedi')+(d.ham?('\n\n'+d.ham):''));
    }catch(e){ gosterHata('e_hata', e.message); }
  }

  async function sil(numara){
    if(!confirm(numara+' numaralı dahili silinsin mi?')) return;
    const r=await fetch('/api/dahili/sil',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara})});
    const d=await r.json();
    if(d.ok) yukle(); else alert('Silinemedi: '+(d.hata||''));
  }

  async function sifre(numara){
    const s=prompt(numara+' için yeni SIP şifresi:');
    if(!s) return;
    const r=await fetch('/api/dahili/sifre',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara,sifre:s})});
    const d=await r.json();
    if(d.ok) alert('Şifre güncellendi.'); else alert('Güncellenemedi: '+(d.hata||''));
  }

  yukle();
</script>
@endif
</body>
</html>

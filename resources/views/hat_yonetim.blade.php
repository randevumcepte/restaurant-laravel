<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hat Yönetimi — Trunk + DID</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:820px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:18px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px;margin-bottom:16px}
  .card h2{font-size:14px;color:var(--sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px}
  label{display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:var(--sub)}
  input[type=text],input[type=password]{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;font-size:15px;outline:none;background:#f8fafc}
  input:focus{border-color:var(--indigo);background:#fff}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .f{margin-bottom:12px}
  .btn{border:none;border-radius:10px;padding:12px 16px;font-weight:700;cursor:pointer;font-size:14px}
  .btn.pri{color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2));width:100%;font-size:16px;padding:14px}
  .btn.dgr{background:#fee2e2;color:var(--red)}
  table{width:100%;border-collapse:collapse}
  th,td{text-align:left;padding:11px 8px;border-bottom:1px solid var(--line);font-size:14px}
  th{color:var(--sub);font-size:12px;text-transform:uppercase}
  td .aksi{display:flex;gap:6px;justify-content:flex-end}
  .uyari{background:#fef9c3;border:1px solid #fde047;color:#854d0e;padding:14px 16px;border-radius:12px;margin-bottom:16px}
  .mesaj{padding:12px 14px;border-radius:10px;margin-top:12px;font-size:13px;white-space:pre-wrap;word-break:break-word;display:none}
  .mesaj.ok{background:#dcfce7;border:1px solid #86efac;color:#166534}
  .mesaj.err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;font-family:monospace}
  .link{color:var(--indigo);font-weight:700;text-decoration:none}
  .bos{color:var(--sub);text-align:center;padding:20px}
  .hint{font-size:12px;color:var(--sub);margin-top:5px;line-height:1.5}
  .rozet{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:#eef2ff;color:var(--indigo)}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">📞</div>
    <div><h1>Hat Yönetimi</h1><p>chan_sip trunk oluştur + gelen numarayı (DID) AI Santral'e (gelen-restoran) bağla. Sadece bu işletmeye ait hatlar.</p></div>
  </div>

  @if(!$ayarli)
    <div class="uyari">⚠️ Önce Trunk API bağlantısını ayarlayın (URL + gizli anahtar). <a class="link" href="/freepbx-ayar">→ FreePBX API Ayarı</a></div>
  @else
  <div class="card">
    <h2>Yeni hat kur · chan_sip trunk + DID bağlama</h2>
    <div class="grid">
      <div class="f"><label>Gelen numara (DID)</label><input type="text" id="h_did" placeholder="902322404046"></div>
      <div class="f"><label>Trunk adı (opsiyonel)</label><input type="text" id="h_ad" placeholder="DRMET-Voicetelekom"></div>
    </div>
    <div class="grid">
      <div class="f"><label>SIP sunucu (host / İP)</label><input type="text" id="h_host" placeholder="sip1.voicetelekom.net"></div>
      <div class="f"><label>Hesap ismi (username)</label><input type="text" id="h_user" placeholder="902322404046"></div>
    </div>
    <div class="grid">
      <div class="f"><label>SIP şifresi</label><input type="password" id="h_sif" placeholder="••••••••"></div>
      <div class="f"><label>Hedef context</label><input type="text" id="h_ctx" value="gelen-restoran"></div>
    </div>
    <button class="btn pri" onclick="kur()">⚙️ Trunk oluştur + DID'i bağla</button>
    <div class="hint">Sıra: (1) chan_sip trunk FreePBX'e eklenir → (2) DID <code>did_contexts</code>'e bağlanır → (3) fwconsole reload.</div>
    <div class="mesaj" id="h_mesaj"></div>
  </div>

  <div class="card">
    <h2>Bu işletmenin hatları</h2>
    <table>
      <thead><tr><th>DID</th><th>Trunk</th><th>Sunucu</th><th>Context</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="liste"><tr><td colspan="5" class="bos">Yükleniyor…</td></tr></tbody>
    </table>
  </div>
  @endif

  <a class="link" href="/freepbx-ayar">← FreePBX API Ayarı</a>
</div>

@if($ayarli)
<script>
  const SUBE = {{ (int) $subeId }};
  const listeEl=document.getElementById('liste');
  function form(obj){ return Object.entries(obj).map(([k,v])=>k+'='+encodeURIComponent(v==null?'':v)).join('&'); }
  function mesaj(txt, ok){ const el=document.getElementById('h_mesaj'); el.textContent=txt; el.className='mesaj '+(ok?'ok':'err'); el.style.display=txt?'block':'none'; }

  async function yukle(){
    listeEl.innerHTML='<tr><td colspan=5 class="bos">Yükleniyor…</td></tr>';
    try{
      const r=await fetch('/api/hat/liste',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({sube_id:SUBE})});
      const d=await r.json();
      if(!d.ok||!d.liste.length){ listeEl.innerHTML='<tr><td colspan=5 class="bos">Bu işletmeye bağlı hat yok</td></tr>'; return; }
      listeEl.innerHTML='';
      d.liste.forEach(x=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td><b>${x.did}</b></td>
          <td>${x.trunk_adi||''} <span class="rozet">${x.tech||'sip'}</span></td>
          <td>${x.host||''}</td>
          <td>${x.context_name||''}</td>
          <td><div class="aksi"><button class="btn dgr" onclick="sil('${x.did}')">Kaldır</button></div></td>`;
        listeEl.appendChild(tr);
      });
    }catch(e){ listeEl.innerHTML='<tr><td colspan=5 class="bos">Hata: '+e.message+'</td></tr>'; }
  }

  async function kur(){
    const did=document.getElementById('h_did').value.trim();
    const ad=document.getElementById('h_ad').value.trim();
    const host=document.getElementById('h_host').value.trim();
    const username=document.getElementById('h_user').value.trim();
    const sip_secret=document.getElementById('h_sif').value;
    const context=document.getElementById('h_ctx').value.trim()||'gelen-restoran';
    mesaj('',true);
    if(!did||!host||!username||!sip_secret){ mesaj('DID, host, username ve SIP şifresi zorunlu.',false); return; }
    mesaj('⏳ Trunk oluşturuluyor ve DID bağlanıyor… (reload birkaç saniye sürebilir)',true);
    try{
      const r=await fetch('/api/hat/kur',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:form({sube_id:SUBE,did,ad,host,username,sip_secret,context,tech:'sip'})});
      const d=await r.json();
      if(d.ok){
        mesaj('✅ '+(d.mesaj||'Hat kuruldu.'),true);
        document.getElementById('h_did').value='';document.getElementById('h_ad').value='';
        document.getElementById('h_host').value='';document.getElementById('h_user').value='';document.getElementById('h_sif').value='';
        yukle();
      } else {
        mesaj('❌ '+(d.hata||'Kurulamadı')+'\n\n'+JSON.stringify(d.ayrinti||{},null,2),false);
      }
    }catch(e){ mesaj('❌ İstek hatası: '+e.message,false); }
  }

  async function sil(did){
    if(!confirm(did+' numaralı hattın DID bağlaması kaldırılsın mı? (Trunk FreePBX\'te kalır)')) return;
    const r=await fetch('/api/hat/sil',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({sube_id:SUBE,did})});
    const d=await r.json();
    if(d.ok) yukle(); else alert('Kaldırılamadı: '+(d.hata||''));
  }

  yukle();
</script>
@endif
</body>
</html>

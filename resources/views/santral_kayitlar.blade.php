<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Santral — Çağrı Kayıtları</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444;--us:#4f46e5;--ai:#eef2ff}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:860px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:16px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .grid{display:grid;grid-template-columns:320px 1fr;gap:16px}
  @media(max-width:720px){.grid{grid-template-columns:1fr}}
  .card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
  .kayit{padding:10px;border:1px solid var(--line);border-radius:10px;margin-bottom:8px;cursor:pointer}
  .kayit:hover{border-color:var(--indigo);background:#f8fafc}
  .kayit.sec{border-color:var(--indigo);background:#eef2ff}
  .kayit .ust{display:flex;justify-content:space-between;font-size:12px;color:var(--sub)}
  .kayit .tel{font-weight:700;font-size:14px;color:var(--ink)}
  .rozet{display:inline-block;font-size:11px;border-radius:20px;padding:2px 8px;font-weight:700}
  .r-siparis{background:#dcfce7;color:#166534}.r-rezervasyon{background:#dbeafe;color:#1e40af}
  .r-aktar{background:#fef9c3;color:#854d0e}.r-kufur{background:#fee2e2;color:#991b1b}.r-bilgi{background:#f1f5f9;color:#475569}
  .msg{max-width:85%;padding:9px 13px;border-radius:14px;font-size:14px;line-height:1.5;margin-bottom:8px;white-space:pre-wrap}
  .us{background:var(--us);color:#fff;border-top-right-radius:4px;margin-left:auto}
  .ai{background:var(--ai);color:#1e293b;border:1px solid #c7d2fe;border-top-left-radius:4px}
  .meta{font-size:12px;color:var(--sub);margin-bottom:12px;line-height:1.7}
  .meta b{color:var(--ink)}
  .kopyala{border:1px solid var(--indigo);color:var(--indigo);background:#eef2ff;border-radius:10px;padding:8px 14px;font-weight:700;cursor:pointer;font-size:13px;margin-bottom:12px}
  .bos{color:var(--sub);text-align:center;padding:30px}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">📋</div>
    <div><h1>AI Santral — Çağrı Kayıtları</h1><p>Her telefon görüşmesinin tam dökümü. Bir çağrıya tıkla, konuşmayı oku; "Metni kopyala" ile paylaş.</p></div>
  </div>
  <div class="grid">
    <div class="card" style="max-height:80vh;overflow:auto">
      <div id="liste"><div class="bos">Yükleniyor…</div></div>
    </div>
    <div class="card" id="detay"><div class="bos">Soldan bir çağrı seç</div></div>
  </div>
</div>
<script>
  async function get(u){const r=await fetch(u);return r.json();}
  async function post(u,o){const r=await fetch(u,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:Object.entries(o).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&')});return r.json();}
  function esc(s){return (s||'').replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));}
  function rozet(s){const m={siparis:'Sipariş',rezervasyon:'Rezervasyon',aktar:'Aktarıldı',kufur:'Küfür',bilgi:'Bilgi'};return s?`<span class="rozet r-${s}">${m[s]||s}</span>`:'';}

  let sonList=[];
  async function yukle(){
    const d=await get('/api/santral-kayit/liste');
    const el=document.getElementById('liste');
    if(!d.liste||!d.liste.length){el.innerHTML='<div class="bos">Henüz çağrı yok</div>';return;}
    sonList=d.liste; el.innerHTML='';
    d.liste.forEach((x,i)=>{
      const div=document.createElement('div'); div.className='kayit'; div.id='k'+x.id;
      div.onclick=()=>detay(x.id);
      div.innerHTML=`<div class="ust"><span>#${x.id} · ${x.tur} tur</span><span>${x.created_at||''}</span></div>
        <div class="tel">${esc(x.telefon||'numara yok')}</div>
        <div style="margin-top:4px">${rozet(x.sonuc)} <span style="font-size:12px;color:#64748b">${esc(x.son_musteri||'')}</span></div>`;
      el.appendChild(div);
    });
    if(d.liste[0]) detay(d.liste[0].id); // en son cagriyi otomatik ac
  }
  async function detay(id){
    document.querySelectorAll('.kayit').forEach(k=>k.classList.remove('sec'));
    const k=document.getElementById('k'+id); if(k)k.classList.add('sec');
    const d=await post('/api/santral-kayit/detay',{id});
    const el=document.getElementById('detay');
    if(!d.ok){el.innerHTML='<div class="bos">'+(d.hata||'Hata')+'</div>';return;}
    const r=d.kayit;
    let meta=`<div class="meta"><b>Çağrı #${r.id}</b> · ${esc(r.telefon||'-')} · ${r.created_at||''}<br>
      Sonuç: ${rozet(r.sonuc)||'-'} · Durum: ${esc(r.durum||'-')}
      ${r.adisyon_id?' · Adisyon #'+r.adisyon_id:''}${r.rezervasyon_id?' · Rezervasyon #'+r.rezervasyon_id:''}</div>`;
    let chat='';
    (r.gecmis||[]).forEach(m=>{
      const kim=(m.role==='user')?'us':'ai';
      const et=(m.role==='user')?'🧑 Müşteri':'🤖 AI';
      chat+=`<div class="msg ${kim}"><div style="font-size:11px;opacity:.7;margin-bottom:2px">${et}</div>${esc(m.content)}</div>`;
    });
    window._sonDokum=metinDokum(r);
    el.innerHTML=meta+'<button class="kopyala" onclick="kopyala()">📋 Metni kopyala</button>'+(chat||'<div class="bos">Konuşma boş</div>');
  }
  function metinDokum(r){
    let t=`Cagri #${r.id} | tel:${r.telefon||'-'} | sonuc:${r.sonuc||'-'} | durum:${r.durum||'-'}`;
    if(r.adisyon_id)t+=` | adisyon:${r.adisyon_id}`; if(r.rezervasyon_id)t+=` | rezervasyon:${r.rezervasyon_id}`;
    t+='\n'; (r.gecmis||[]).forEach(m=>{t+=`\n${m.role==='user'?'MUSTERI':'AI'}: ${m.content}`;});
    if(r.siparis_veri)t+='\n\nSIPARIS_VERI: '+JSON.stringify(r.siparis_veri);
    return t;
  }
  function kopyala(){navigator.clipboard.writeText(window._sonDokum||'').then(()=>alert('Döküm kopyalandı — buraya yapıştırabilirsin.'));}
  yukle();
</script>
</body>
</html>

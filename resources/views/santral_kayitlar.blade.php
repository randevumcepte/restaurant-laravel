<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AI Santral — Çağrı Kayıtları</title>
<style>
  :root{
    --bg:#0f1117;--panel:#171a23;--panel2:#1e222e;--line:#2a2f3e;--ink:#e8ecf4;--sub:#97a0b5;
    --indigo:#6366f1;--indigo2:#8b5cf6;--ok:#22c55e;--blue:#3b82f6;--amber:#f59e0b;--red:#ef4444;--slate:#64748b;
    --us:linear-gradient(135deg,#6366f1,#8b5cf6);--ai:#232838;
  }
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh}
  .top{background:linear-gradient(120deg,#4f46e5,#7c3aed 55%,#9333ea);padding:22px 24px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .top .ic{width:50px;height:50px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:26px;backdrop-filter:blur(4px)}
  .top h1{font-size:21px;color:#fff;font-weight:800}
  .top p{color:rgba(255,255,255,.82);font-size:13px;margin-top:2px}
  .top .yenile{margin-left:auto;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:10px;padding:9px 16px;font-weight:700;cursor:pointer;font-size:13px}
  .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:18px 24px}
  @media(max-width:640px){.stats{grid-template-columns:repeat(2,1fr)}}
  .stat{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:14px 16px}
  .stat .n{font-size:24px;font-weight:800}
  .stat .l{font-size:12px;color:var(--sub);margin-top:2px;display:flex;align-items:center;gap:6px}
  .stat .dot{width:8px;height:8px;border-radius:50%}
  .grid{display:grid;grid-template-columns:340px 1fr;gap:16px;padding:0 24px 24px}
  @media(max-width:860px){.grid{grid-template-columns:1fr}}
  .panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden}
  .ara{padding:12px;border-bottom:1px solid var(--line)}
  .ara input{width:100%;background:var(--panel2);border:1px solid var(--line);color:var(--ink);border-radius:10px;padding:10px 12px;font-size:14px;outline:none}
  .liste{max-height:70vh;overflow:auto}
  .kayit{padding:12px 14px;border-bottom:1px solid var(--line);cursor:pointer;display:flex;gap:11px;align-items:flex-start;border-left:3px solid transparent;transition:.12s}
  .kayit:hover{background:var(--panel2)}
  .kayit.sec{background:var(--panel2);border-left-color:var(--indigo)}
  .kayit .av{width:38px;height:38px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}
  .kayit .ov{flex:1;min-width:0}
  .kayit .tel{font-weight:700;font-size:14px}
  .kayit .sn{font-size:12px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .kayit .alt{display:flex;align-items:center;gap:6px;margin-top:4px}
  .rozet{font-size:10.5px;border-radius:20px;padding:2px 9px;font-weight:800;letter-spacing:.02em}
  .zaman{font-size:11px;color:var(--slate);margin-left:auto;white-space:nowrap}
  /* durum renkleri */
  .c-siparis{background:rgba(34,197,94,.16);color:#4ade80}.b-siparis{background:var(--ok)}
  .c-rezervasyon{background:rgba(59,130,246,.16);color:#60a5fa}.b-rezervasyon{background:var(--blue)}
  .c-aktar{background:rgba(245,158,11,.16);color:#fbbf24}.b-aktar{background:var(--amber)}
  .c-kufur{background:rgba(239,68,68,.16);color:#f87171}.b-kufur{background:var(--red)}
  .c-bilgi,.c-none{background:rgba(100,116,139,.16);color:#94a3b8}.b-bilgi,.b-none{background:var(--slate)}
  /* detay */
  .detay{padding:18px;max-height:72vh;overflow:auto}
  .meta{background:var(--panel2);border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:14px}
  .meta .ln1{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-weight:800;font-size:16px}
  .meta .ln2{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px;font-size:12px;color:var(--sub)}
  .chip{background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:3px 9px;font-size:12px;color:var(--ink)}
  .kopyala{background:var(--us);border:none;color:#fff;border-radius:9px;padding:8px 14px;font-weight:700;cursor:pointer;font-size:12.5px;margin-bottom:14px}
  .msg{max-width:84%;padding:10px 14px;border-radius:15px;font-size:14px;line-height:1.5;margin-bottom:10px;white-space:pre-wrap;position:relative}
  .msg .kim{font-size:10.5px;opacity:.75;margin-bottom:3px;font-weight:700;letter-spacing:.02em}
  .us{background:var(--us);color:#fff;border-bottom-right-radius:5px;margin-left:auto}
  .ai{background:var(--ai);border:1px solid var(--line);border-bottom-left-radius:5px}
  .bos{color:var(--sub);text-align:center;padding:40px 20px}
</style>
</head>
<body>
  <div class="top">
    <div class="ic">📞</div>
    <div>
      <h1>AI Santral — Çağrı Kayıtları</h1>
      <p>Her telefon görüşmesinin tam dökümü, sonucu ve bağlı siparişi. Kalıcı kayıt + QA.</p>
    </div>
    <button class="yenile" onclick="yukle()">↻ Yenile</button>
  </div>

  <div class="stats" id="stats">
    <div class="stat"><div class="n" id="s_top">—</div><div class="l"><span class="dot b-none"></span>Toplam çağrı</div></div>
    <div class="stat"><div class="n" id="s_sip">—</div><div class="l"><span class="dot b-siparis"></span>Sipariş</div></div>
    <div class="stat"><div class="n" id="s_rez">—</div><div class="l"><span class="dot b-rezervasyon"></span>Rezervasyon</div></div>
    <div class="stat"><div class="n" id="s_akt">—</div><div class="l"><span class="dot b-aktar"></span>Aktarılan</div></div>
  </div>

  <div class="grid">
    <div class="panel">
      <div class="ara"><input id="q" placeholder="🔎 Telefon veya içerik ara…" oninput="filtrele()"></div>
      <div class="liste" id="liste"><div class="bos">Yükleniyor…</div></div>
    </div>
    <div class="panel"><div class="detay" id="detay"><div class="bos">Soldan bir çağrı seç</div></div></div>
  </div>

<audio id="player"></audio>
<script>
  const SMAP={siparis:'Sipariş',rezervasyon:'Rezervasyon',aktar:'Aktarıldı',kufur:'Küfür',bilgi:'Bilgi'};
  const IMAP={siparis:'🛒',rezervasyon:'📅',aktar:'↪️',kufur:'🚫',bilgi:'ℹ️'};
  let hepsi=[];

  async function get(u){const r=await fetch(u+(u.includes('?')?'&':'?')+'_t='+Date.now(),{cache:'no-store'});return r.json();}
  async function post(u,o){const r=await fetch(u,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:Object.entries(o).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&')});return r.json();}
  function esc(s){return (s||'').replace(/[&<>]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));}
  function cls(s){return s&&SMAP[s]?s:'none';}
  function rozet(s){const k=cls(s);return `<span class="rozet c-${k}">${IMAP[s]||'•'} ${SMAP[s]||'Görüşme'}</span>`;}
  function kisaZaman(t){ if(!t)return''; return String(t).replace('T',' ').slice(5,16); }

  async function yukle(){
    const el=document.getElementById('liste'); el.innerHTML='<div class="bos">Yükleniyor…</div>';
    const d=await get('/api/santral-kayit/liste');
    hepsi=d.liste||[];
    // istatistik
    document.getElementById('s_top').textContent=hepsi.length;
    document.getElementById('s_sip').textContent=hepsi.filter(x=>x.sonuc==='siparis').length;
    document.getElementById('s_rez').textContent=hepsi.filter(x=>x.sonuc==='rezervasyon').length;
    document.getElementById('s_akt').textContent=hepsi.filter(x=>x.sonuc==='aktar').length;
    ciz(hepsi);
    if(hepsi[0]) detay(hepsi[0].id);
  }
  function ciz(list){
    const el=document.getElementById('liste');
    if(!list.length){el.innerHTML='<div class="bos">Kayıt yok</div>';return;}
    el.innerHTML='';
    list.forEach(x=>{
      const k=cls(x.sonuc);
      const div=document.createElement('div'); div.className='kayit'; div.id='k'+x.id;
      div.onclick=()=>detay(x.id);
      div.innerHTML=`<div class="av b-${k}">${IMAP[x.sonuc]||'📞'}</div>
        <div class="ov">
          <div style="display:flex;align-items:center"><span class="tel">${esc(x.telefon||'numara yok')}</span><span class="zaman">${kisaZaman(x.created_at)}</span></div>
          <div class="sn">${esc(x.son_musteri||'—')}</div>
          <div class="alt">${rozet(x.sonuc)} <span style="font-size:11px;color:var(--slate)">${x.tur} tur</span></div>
        </div>`;
      el.appendChild(div);
    });
  }
  function filtrele(){
    const q=document.getElementById('q').value.toLowerCase().trim();
    if(!q){ciz(hepsi);return;}
    ciz(hepsi.filter(x=>(x.telefon||'').toLowerCase().includes(q)||(x.son_musteri||'').toLowerCase().includes(q)));
  }

  async function detay(id){
    document.querySelectorAll('.kayit').forEach(k=>k.classList.remove('sec'));
    const kk=document.getElementById('k'+id); if(kk)kk.classList.add('sec');
    const el=document.getElementById('detay'); el.innerHTML='<div class="bos">Yükleniyor…</div>';
    const d=await post('/api/santral-kayit/detay',{id});
    if(!d.ok){el.innerHTML='<div class="bos">'+(d.hata||'Hata')+'</div>';return;}
    const r=d.kayit;
    let chips='';
    if(r.adisyon_id)chips+=`<span class="chip">🛒 Adisyon #${r.adisyon_id}</span>`;
    if(r.rezervasyon_id)chips+=`<span class="chip">📅 Rezervasyon #${r.rezervasyon_id}</span>`;
    chips+=`<span class="chip">Durum: ${esc(r.durum||'-')}</span>`;
    let meta=`<div class="meta">
      <div class="ln1">${IMAP[r.sonuc]||'📞'} ${esc(r.telefon||'numara yok')} ${rozet(r.sonuc)}</div>
      <div class="ln2"><span>🕐 ${esc((r.created_at||'').replace('T',' ').slice(0,16))}</span> ${chips}</div>`;
    // SES KAYITLARI
    if(r.sesler && r.sesler.length){
      meta+='<div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">';
      r.sesler.forEach(s=>{
        const et=s.tur==='aktarma'?'↪️ Yetkiliye aktarılan görüşme':'🤖 AI görüşmesi';
        const kb=s.boyut?(' · '+Math.round(s.boyut/1024)+' KB'):'';
        meta+=`<div style="background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:8px 10px">
          <div style="font-size:12px;color:var(--sub);margin-bottom:5px;font-weight:700">${et}${kb}</div>
          <audio controls preload="none" style="width:100%;height:34px" src="${s.url}"></audio></div>`;
      });
      meta+='</div>';
    } else {
      meta+='<div style="margin-top:10px;font-size:12px;color:var(--slate)">🎙️ Ses kaydı yok</div>';
    }
    meta+='</div>';
    let chat='';
    (r.gecmis||[]).forEach(m=>{
      const kim=(m.role==='user')?'us':'ai';
      const et=(m.role==='user')?'🧑 MÜŞTERİ':'🤖 AI';
      chat+=`<div class="msg ${kim}"><div class="kim">${et}</div>${esc(m.content)}</div>`;
    });
    window._dokum=dokum(r);
    el.innerHTML=meta+'<button class="kopyala" onclick="kopyala()">📋 Dökümü kopyala</button>'+(chat||'<div class="bos">Konuşma boş</div>');
  }
  function dokum(r){
    let t=`Cagri #${r.id} | tel:${r.telefon||'-'} | sonuc:${r.sonuc||'-'} | durum:${r.durum||'-'}`;
    if(r.adisyon_id)t+=` | adisyon:${r.adisyon_id}`; if(r.rezervasyon_id)t+=` | rezervasyon:${r.rezervasyon_id}`;
    t+='\n'; (r.gecmis||[]).forEach(m=>{t+=`\n${m.role==='user'?'MUSTERI':'AI'}: ${m.content}`;});
    if(r.siparis_veri)t+='\n\nSIPARIS_VERI: '+JSON.stringify(r.siparis_veri);
    return t;
  }
  function kopyala(){navigator.clipboard.writeText(window._dokum||'').then(()=>alert('Döküm kopyalandı.'));}
  yukle();
</script>
</body>
</html>

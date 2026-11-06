@extends('layout.app')
@section('title', 'AI Santral — Çağrı Kayıtları')
@section('baslik', 'AI Santral — Çağrı Kayıtları')
@section('rk_ikon', 'call')
@section('rk_alt', 'Her telefon görüşmesinin tam dökümü, sonucu ve bağlı siparişi')
@section('rk_araclar')
  <button class="st-yenile" onclick="yukle()"><span class="material-icons-outlined">refresh</span> Yenile</button>
@endsection
@push('head')
<style>
  .st-kok{--panel:var(--card);--panel2:var(--card2);--indigo:#6366f1;--ok:#22c55e;--blue:#3b82f6;--amber:#f59e0b;--red:#ef4444;--slate:#64748b;--us:linear-gradient(135deg,#6366f1,#8b5cf6);--ai:var(--card2);color:var(--ink)}
  .st-yenile{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border:1px solid var(--line);color:var(--ink);border-radius:10px;padding:8px 14px;font-weight:700;cursor:pointer;font-size:13px}
  .st-yenile .material-icons-outlined{font-size:18px}
  .st-kok *{box-sizing:border-box}
  .st-kok .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
  @media(max-width:640px){.st-kok .stats{grid-template-columns:repeat(2,1fr)}}
  .st-kok .stat{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:14px 16px}
  .st-kok .stat .n{font-size:24px;font-weight:800}
  .st-kok .stat .l{font-size:12px;color:var(--sub);margin-top:2px;display:flex;align-items:center;gap:6px}
  .st-kok .stat .dot{width:8px;height:8px;border-radius:50%}
  .st-kok .grid{display:grid;grid-template-columns:340px minmax(0,1fr);gap:16px}
  @media(max-width:860px){.st-kok .grid{grid-template-columns:minmax(0,1fr)}}
  .st-kok .panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;overflow:hidden}
  .st-kok .ara{padding:12px;border-bottom:1px solid var(--line)}
  .st-kok .ara input{width:100%;background:var(--panel2);border:1px solid var(--line);color:var(--ink);border-radius:10px;padding:10px 12px;font-size:14px;outline:none}
  .st-kok .liste{max-height:70vh;overflow:auto}
  .st-kok .kayit{padding:12px 14px;border-bottom:1px solid var(--line);cursor:pointer;display:flex;gap:11px;align-items:flex-start;border-left:3px solid transparent;transition:.12s}
  .st-kok .kayit:hover{background:var(--panel2)}
  .st-kok .kayit.sec{background:var(--panel2);border-left-color:var(--indigo)}
  .st-kok .kayit .av{width:38px;height:38px;border-radius:50%;flex:none;display:flex;align-items:center;justify-content:center;font-size:17px;color:#fff}
  .st-kok .kayit .ov{flex:1;min-width:0}
  .st-kok .kayit .tel{font-weight:700;font-size:14px}
  .st-kok .kayit .sn{font-size:12px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .st-kok .kayit .alt{display:flex;align-items:center;gap:6px;margin-top:4px}
  .st-kok .rozet{font-size:10.5px;border-radius:20px;padding:2px 9px;font-weight:800;letter-spacing:.02em}
  .st-kok .zaman{font-size:11px;color:var(--slate);margin-left:auto;white-space:nowrap}
  /* durum renkleri */
  .st-kok .c-siparis{background:rgba(34,197,94,.16);color:#16a34a}.st-kok .b-siparis{background:var(--ok)}
  .st-kok .c-rezervasyon{background:rgba(59,130,246,.16);color:#2563eb}.st-kok .b-rezervasyon{background:var(--blue)}
  .st-kok .c-aktar{background:rgba(245,158,11,.16);color:#d97706}.st-kok .b-aktar{background:var(--amber)}
  .st-kok .c-kufur{background:rgba(239,68,68,.16);color:#dc2626}.st-kok .b-kufur{background:var(--red)}
  .st-kok .c-bilgi,.st-kok .c-none{background:rgba(100,116,139,.16);color:var(--sub)}.st-kok .b-bilgi,.st-kok .b-none{background:var(--slate)}
  /* detay */
  .st-kok .detay{padding:18px;max-height:72vh;overflow:auto}
  .st-kok .meta{background:var(--panel2);border:1px solid var(--line);border-radius:12px;padding:14px;margin-bottom:14px}
  .st-kok .meta .ln1{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-weight:800;font-size:16px}
  .st-kok .meta .ln2{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:8px;font-size:12px;color:var(--sub)}
  .st-kok .chip{background:var(--panel);border:1px solid var(--line);border-radius:8px;padding:3px 9px;font-size:12px;color:var(--ink)}
  .st-kok .kopyala{background:var(--us);border:none;color:#fff;border-radius:9px;padding:8px 14px;font-weight:700;cursor:pointer;font-size:12.5px;margin-bottom:14px}
  .st-kok .msg{max-width:84%;padding:10px 14px;border-radius:15px;font-size:14px;line-height:1.5;margin-bottom:10px;white-space:pre-wrap;position:relative}
  .st-kok .msg .kim{font-size:10.5px;opacity:.75;margin-bottom:3px;font-weight:700;letter-spacing:.02em}
  .st-kok .us{background:var(--us);color:#fff;border-bottom-right-radius:5px;margin-left:auto}
  .st-kok .ai{background:var(--ai);border:1px solid var(--line);border-bottom-left-radius:5px}
  .st-kok .bos{color:var(--sub);text-align:center;padding:40px 20px}
</style>
@endpush
@section('content')
<div class="st-kok">
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
</div>
@endsection
@push('scripts')
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
@endpush

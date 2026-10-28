@extends('layout.app')
@section('rk_ikon', 'smart_toy')
@section('baslik', 'İK AI Asistanı')
@section('title', 'İK AI Asistanı')

@push('head')
<style>
#ika{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--pembe:#EC4899;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;
}
body.koyu #ika{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;}
#ika{background:var(--bg);min-height:calc(100vh - 60px);display:flex;flex-direction:column;max-width:860px;margin:0 auto}
#ika *{box-sizing:border-box}
#ika .ust{display:flex;align-items:center;gap:13px;padding:16px 18px 10px}
#ika .ust .ik{width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(124,58,237,.35)}
#ika .ust .ik .material-icons-outlined{color:#fff;font-size:26px}
#ika .ust .ad{font-size:18px;font-weight:800;color:var(--ink)}
#ika .ust .alt{font-size:12px;color:var(--sub)}
#ika .akis{flex:1;overflow-y:auto;padding:12px 18px 8px;display:flex;flex-direction:column;gap:12px}
#ika .bal{max-width:84%;padding:12px 15px;border-radius:16px;font-size:14px;line-height:1.55;white-space:pre-wrap;word-wrap:break-word}
#ika .bal.ai{align-self:flex-start;background:var(--card);border:1px solid var(--line);color:var(--ink);box-shadow:0 2px 8px rgba(0,0,0,.05);border-bottom-left-radius:5px}
#ika .bal.ben{align-self:flex-end;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-bottom-right-radius:5px}
#ika .onizle{align-self:flex-start;max-width:90%;background:linear-gradient(135deg,rgba(245,158,11,.1),rgba(245,158,11,.04));border:1.5px solid rgba(245,158,11,.4);border-radius:16px;padding:14px 16px}
#ika .onizle .oh{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--amber);margin-bottom:8px}
#ika .onizle .ot{font-size:14px;color:var(--ink);line-height:1.55;margin-bottom:12px;white-space:pre-wrap}
#ika .onizle .ob{display:flex;gap:9px}
#ika .btn{border:none;cursor:pointer;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;color:#fff;display:inline-flex;align-items:center;gap:5px}
#ika .btn .material-icons-outlined{font-size:17px}
#ika .btn.ikincil{background:var(--card2);color:var(--sub)}
#ika .cipler{display:flex;gap:8px;flex-wrap:wrap;padding:4px 18px 8px}
#ika .cip{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:7px 13px;font-size:12.5px;font-weight:600;color:var(--sub);cursor:pointer}
#ika .cip:hover{border-color:var(--mor1);color:var(--mor1)}
#ika .giris{display:flex;gap:9px;padding:10px 18px 16px;align-items:flex-end}
#ika .giris textarea{flex:1;resize:none;border:1.5px solid var(--line);border-radius:14px;padding:12px 14px;font-size:14px;font-family:inherit;background:var(--card);color:var(--ink);max-height:120px;min-height:46px}
#ika .giris textarea:focus{outline:none;border-color:var(--mor1)}
#ika .gonder{width:46px;height:46px;border-radius:14px;border:none;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center}
#ika .gonder:disabled{opacity:.5}
#ika .link-btn{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:12.5px;font-weight:700;color:var(--mavi);text-decoration:none}
</style>
@endpush

@section('content')
<div id="ika">
  <div class="ust">
    <a href="/ik-ozet" class="rk-btn bos" style="padding:6px 9px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <div class="ik"><span class="material-icons-outlined">smart_toy</span></div>
    <div style="flex:1">
      <div class="ad">İK AI Asistanı</div>
      <div class="alt">Yaz veya sor — bilgi veririm ya da işini yaparım. Önce özetlerim, sen onaylayınca uygularım.</div>
    </div>
  </div>

  <div class="akis" id="akis">
    <div class="bal ai">Merhaba 👋 İnsan kaynakları işlerinde yardımcın olayım. Bana doğal cümleyle yazabilirsin:
• "özet ver" · "eksik belgelileri listele" · "bugün kim izinli"
• "Ahmet'e cuma-cumartesi yıllık izin ver"
• "bekleyen avans talebini onayla"
• "doğum günü olanlara kutlama gönder"

Bir şey değiştirecek veya mesaj gönderecek olursam, <b>önce ne yapacağımı gösterir, onayını beklerim.</b></div>
  </div>

  <div class="cipler">
    <div class="cip" onclick="hizli('Genel özet ver')">Özet</div>
    <div class="cip" onclick="hizli('Eksik belgelileri listele')">Eksik belgeliler</div>
    <div class="cip" onclick="hizli('Bugün kim izinli')">Bugün izinli</div>
    <div class="cip" onclick="hizli('Bekleyen izin talepleri')">Bekleyen izinler</div>
    <div class="cip" onclick="hizli('Bu ay en çok avans alan kim')">En çok avans</div>
  </div>

  <div class="giris">
    <textarea id="metin" placeholder="İK ile ilgili ne yapmak istersin?" rows="1"
      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();gonder();}" oninput="boyAyar(this)"></textarea>
    <button class="gonder" id="gonderBtn" onclick="gonder()"><span class="material-icons-outlined">send</span></button>
  </div>
</div>

<script>
const akis = document.getElementById('akis');
function boyAyar(t){ t.style.height='46px'; t.style.height=Math.min(120,t.scrollHeight)+'px'; }
function kaydir(){ akis.scrollTop = akis.scrollHeight; }
function balon(metin, kim){ const d=document.createElement('div'); d.className='bal '+kim; d.innerHTML=metin.replace(/</g,'&lt;').replace(/\n/g,'<br>'); akis.appendChild(d); kaydir(); return d; }
function hizli(t){ document.getElementById('metin').value=t; gonder(); }

async function post(url, body){
  const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body:JSON.stringify(body)});
  return r.json();
}

async function gonder(){
  const el = document.getElementById('metin');
  const metin = el.value.trim();
  if(!metin) return;
  balon(metin, 'ben');
  el.value=''; boyAyar(el);
  const btn = document.getElementById('gonderBtn'); btn.disabled=true;
  const yaz = balon('…', 'ai');
  try{
    const d = await post('/api/ik-asistan/sor', {metin});
    yaz.remove();
    if(d.ok!==1){ balon('Bir sorun oldu, tekrar dener misin?','ai'); btn.disabled=false; return; }
    if(d.tip==='eylem'){ onizleGoster(d.ozet, d.eylem, d.params, d.log_id || 0); }
    else {
      const b = balon(d.cevap||'…','ai');
      if(d.link){ const a=document.createElement('a'); a.href=d.link.url; a.className='link-btn'; a.innerHTML='<span class="material-icons-outlined" style="font-size:16px">open_in_new</span> '+d.link.lbl; b.appendChild(document.createElement('br')); b.appendChild(a); }
      if(d.aksiyon){ ekAksiyon(b, d.aksiyon); }
    }
  }catch(e){ yaz.remove(); balon('Bağlantı hatası.','ai'); }
  btn.disabled=false;
}

// okuma cevabı altında "şunu yapabilirim" aksiyonu (örn. listele -> hepsine mesaj)
function ekAksiyon(balonEl, aks){
  const w = document.createElement('div'); w.style.marginTop='10px';
  const b = document.createElement('button'); b.className='btn'; b.style.background='var(--amber)';
  b.innerHTML='<span class="material-icons-outlined">send</span> '+aks.lbl;
  b.onclick=()=>{ w.remove(); sorEylem(aks.intent); };
  w.appendChild(b); balonEl.appendChild(w);
}
// bir okuma-aksiyonu (intent) doğrudan eylem önizlemeye çevir
async function sorEylem(intent){
  const map={mesaj_eksik:'eksik bilgi iste', mesaj_dogum:'doğum günü kutla', mesaj_belge:'belge hatırlat', mesaj_gelmeyen:'gelmeyene sor'};
  hizli(map[intent]||intent);
}

function onizleGoster(ozet, eylem, params, log_id){
  const d=document.createElement('div'); d.className='onizle';
  const kilitMetin = log_id > 0 ? '<div style="font-size:11px;color:var(--sub);margin-top:4px"><span class="material-icons-outlined" style="font-size:13px;color:var(--yesil);vertical-align:-2px">lock</span> Onayın IP ile birlikte imzalı log\'a yazılacak (geri alınamaz).</div>' : '';
  d.innerHTML='<div class="oh"><span class="material-icons-outlined" style="font-size:16px">task_alt</span> Onayına sunuyorum</div><div class="ot">'+ozet.replace(/</g,'&lt;')+'</div>'+kilitMetin;
  const ob=document.createElement('div'); ob.className='ob';
  const ok=document.createElement('button'); ok.className='btn'; ok.style.background='var(--yesil)'; ok.innerHTML='<span class="material-icons-outlined">check</span> İşlemi Yap';
  ok.onclick=async()=>{
    ob.innerHTML='<span style="color:var(--sub);font-size:13px">Uygulanıyor…</span>';
    // log_id varsa ortak altyapiya git (hash chain + legal log). Yoksa legacy flow.
    const payload = log_id > 0 ? {eylem, params, log_id} : {eylem, params};
    const r=await post('/api/ik-asistan/uygula', payload);
    const s=(r&&r.sonuc)||{}; d.remove();
    balon(s.ok?(s.mesaj||'✓ Tamam'):('⚠️ '+(s.mesaj||'Yapılamadı')),'ai');
  };
  ob.appendChild(ok); d.appendChild(ob);
  akis.appendChild(d); kaydir();
}
</script>
@endsection

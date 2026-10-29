@extends('layout.app')
@section('rk_ikon', $tanim['ikon'])
@section('baslik', $tanim['ad'])
@section('title', $tanim['ad'])

@push('head')
<style>
#da{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;
  --ana:{{ $tanim['renk'] }};
}
body.koyu #da{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;}
#da{background:var(--bg);min-height:calc(100vh - 60px);display:flex;flex-direction:column;max-width:860px;margin:0 auto}
#da *{box-sizing:border-box}
#da .ust{display:flex;align-items:center;gap:13px;padding:16px 18px 10px}
#da .ust .ik{width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,var(--ana),color-mix(in srgb,var(--ana) 55%,#000));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px color-mix(in srgb,var(--ana) 40%,transparent)}
#da .ust .ik .material-icons-outlined{color:#fff;font-size:26px}
#da .ust .ad{font-size:18px;font-weight:800;color:var(--ink)}
#da .ust .alt{font-size:12px;color:var(--sub)}
#da .akis{flex:1;overflow-y:auto;padding:12px 18px 8px;display:flex;flex-direction:column;gap:12px}
#da .bal{max-width:84%;padding:12px 15px;border-radius:16px;font-size:14px;line-height:1.55;white-space:pre-wrap;word-wrap:break-word}
#da .bal.ai{align-self:flex-start;background:var(--card);border:1px solid var(--line);color:var(--ink);box-shadow:0 2px 8px rgba(0,0,0,.05);border-bottom-left-radius:5px}
#da .bal.ben{align-self:flex-end;background:linear-gradient(135deg,var(--ana),color-mix(in srgb,var(--ana) 55%,#000));color:#fff;border-bottom-right-radius:5px}
#da .onizle{align-self:flex-start;max-width:90%;background:linear-gradient(135deg,rgba(245,158,11,.1),rgba(245,158,11,.04));border:1.5px solid rgba(245,158,11,.4);border-radius:16px;padding:14px 16px}
#da .onizle .oh{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--amber);margin-bottom:8px}
#da .onizle .ot{font-size:14px;color:var(--ink);line-height:1.55;margin-bottom:12px;white-space:pre-wrap}
#da .onizle .ob{display:flex;gap:9px}
#da .btn{border:none;cursor:pointer;padding:9px 16px;border-radius:10px;font-size:13px;font-weight:700;color:#fff;display:inline-flex;align-items:center;gap:5px}
#da .btn .material-icons-outlined{font-size:17px}
#da .btn.ikincil{background:var(--card2);color:var(--sub)}
#da .oneriler{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:9px;padding:4px 18px 10px}
#da .oneri{display:flex;align-items:flex-start;gap:10px;padding:12px;border-radius:13px;background:var(--card);border:1px solid var(--line);cursor:pointer;text-align:left;transition:all .16s;font-family:inherit}
#da .oneri:hover{border-color:color-mix(in srgb,var(--ana) 35%,var(--line));transform:translateY(-2px);box-shadow:0 8px 20px -6px color-mix(in srgb,var(--ana) 20%,transparent)}
#da .oneri-ik{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff}
#da .oneri-ik .material-icons-outlined{font-size:18px}
#da .oneri-bas{font-size:13px;font-weight:700;color:var(--ink);line-height:1.3}
#da .oneri-alt{font-size:11px;color:var(--sub);margin-top:2px;line-height:1.35}
#da .giris{display:flex;gap:9px;padding:10px 18px 16px;align-items:flex-end}
#da .giris textarea{flex:1;resize:none;border:1.5px solid var(--line);border-radius:14px;padding:12px 14px;font-size:14px;font-family:inherit;background:var(--card);color:var(--ink);max-height:120px;min-height:46px}
#da .giris textarea:focus{outline:none;border-color:var(--ana)}
#da .gonder{width:46px;height:46px;border-radius:14px;border:none;background:linear-gradient(135deg,var(--ana),color-mix(in srgb,var(--ana) 55%,#000));color:#fff;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center}
#da .gonder:disabled{opacity:.5}
#da .link-btn{display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:12.5px;font-weight:700;color:var(--ana);text-decoration:none}
</style>
@endpush

@section('content')
<div id="da" data-dep="{{ $kod }}">
  <div class="ust">
    <a href="/dashboard" class="rk-btn bos" style="padding:6px 9px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <div class="ik"><span class="material-icons-outlined">{{ $tanim['ikon'] }}</span></div>
    <div style="flex:1">
      <div class="ad">{{ $tanim['ad'] }}</div>
      <div class="alt">{{ $tanim['selam'] }} Yazarak sor; bir şey değiştireceksem önce özetler, onayını beklerim.</div>
    </div>
  </div>

  <div class="akis" id="akis">
    <div class="bal ai">Merhaba 👋 {{ $tanim['selam'] }} Aşağıdaki hızlı sorulardan başlayabilir ya da doğal cümleyle yazabilirsin.</div>
  </div>

  <div class="oneriler">
    @foreach ($tanim['hizli'] as $h)
      <button type="button" class="oneri" onclick="hizli(@js($h[1]))">
        <div class="oneri-ik" style="background:{{ $h[3] }}"><span class="material-icons-outlined">{{ $h[2] }}</span></div>
        <div><div class="oneri-bas">{{ $h[0] }}</div></div>
      </button>
    @endforeach
  </div>

  <div class="giris">
    <textarea id="metin" placeholder="Ne yapmak istersin?" rows="1"
      onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();gonder();}" oninput="boyAyar(this)"></textarea>
    <button class="gonder" id="gonderBtn" onclick="gonder()"><span class="material-icons-outlined">send</span></button>
  </div>
</div>

<script>
const DEP = document.getElementById('da').getAttribute('data-dep');
const akis = document.getElementById('akis');
function boyAyar(t){ t.style.height='46px'; t.style.height=Math.min(120,t.scrollHeight)+'px'; }
function kaydir(){ akis.scrollTop = akis.scrollHeight; }
function balon(metin, kim){ const d=document.createElement('div'); d.className='bal '+kim; d.innerHTML=metin.replace(/</g,'&lt;').replace(/\n/g,'<br>'); akis.appendChild(d); kaydir(); return d; }
function hizli(t){ document.getElementById('metin').value=t; gonder(); }
async function post(url, body){ const r = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body:JSON.stringify(body)}); return r.json(); }

async function gonder(){
  const el = document.getElementById('metin'); const metin = el.value.trim();
  if(!metin) return;
  balon(metin, 'ben'); el.value=''; boyAyar(el);
  const btn = document.getElementById('gonderBtn'); btn.disabled=true;
  const yaz = balon('…', 'ai');
  try{
    const d = await post('/api/departman-asistan/sor', {dep:DEP, metin});
    yaz.remove();
    if(d.ok!==1){ balon('Bir sorun oldu, tekrar dener misin?','ai'); btn.disabled=false; return; }
    if(d.tip==='eylem'){ onizleGoster(d.ozet, d.eylem, d.params); }
    else {
      const b = balon(d.cevap||'…','ai');
      if(d.link){ const a=document.createElement('a'); a.href=d.link.url; a.className='link-btn'; a.innerHTML='<span class="material-icons-outlined" style="font-size:16px">open_in_new</span> '+d.link.lbl; b.appendChild(document.createElement('br')); b.appendChild(a); }
      if(d.aksiyon){ const w=document.createElement('div'); w.style.marginTop='10px'; const ab=document.createElement('button'); ab.className='btn'; ab.style.background='var(--amber)'; ab.innerHTML='<span class="material-icons-outlined">bolt</span> '+d.aksiyon.lbl; ab.onclick=()=>{ w.remove(); sorEylemOnizle(d.aksiyon.intent); }; w.appendChild(ab); b.appendChild(w); }
    }
  }catch(e){ yaz.remove(); balon('Bağlantı hatası.','ai'); }
  btn.disabled=false;
}
// okuma-aksiyonunu doğrudan eylem önizlemeye çevir
async function sorEylemOnizle(intent){
  const yaz = balon('…','ai');
  const d = await post('/api/departman-asistan/sor', {dep:DEP, metin: intent==='acil_stok_talep'?'kritik malzemeler için acil satın alma talebi oluştur':intent});
  yaz.remove();
  if(d.tip==='eylem') onizleGoster(d.ozet, d.eylem, d.params); else balon(d.cevap||'…','ai');
}
function onizleGoster(ozet, eylem, params){
  const d=document.createElement('div'); d.className='onizle';
  d.innerHTML='<div class="oh"><span class="material-icons-outlined" style="font-size:16px">task_alt</span> Onayına sunuyorum</div><div class="ot">'+ozet.replace(/</g,'&lt;')+'</div>';
  const ob=document.createElement('div'); ob.className='ob';
  const ok=document.createElement('button'); ok.className='btn'; ok.style.background='var(--yesil)'; ok.innerHTML='<span class="material-icons-outlined">check</span> Onayla, yap';
  const vz=document.createElement('button'); vz.className='btn ikincil'; vz.textContent='Vazgeç';
  ok.onclick=async()=>{ ob.innerHTML='<span style="color:var(--sub);font-size:13px">Uygulanıyor…</span>'; const r=await post('/api/departman-asistan/uygula',{dep:DEP,eylem,params}); const s=(r&&r.sonuc)||{}; d.remove(); balon(s.ok?(s.mesaj||'✓ Tamam'):('⚠️ '+(s.mesaj||'Yapılamadı')),'ai'); };
  vz.onclick=()=>{ d.remove(); balon('Tamam, vazgeçtim. Başka?','ai'); };
  ob.appendChild(ok); ob.appendChild(vz); d.appendChild(ob); akis.appendChild(d); kaydir();
}
</script>
@endsection

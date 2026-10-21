@extends('layout.app')
@section('title', 'Satınalma AI Asistanı')
@section('rk_ikon', 'smart_toy')
@section('baslik', 'Satınalma AI Asistanı')
@section('rk_alt', 'Stok + fiyat + tedarikçi verinden anında cevap')

@section('content')
<div style="max-width:760px;margin:0 auto">
  <div class="rk-card" style="margin-bottom:14px;display:flex;gap:12px;align-items:flex-start">
    <span class="material-icons-outlined" style="color:var(--mor1);font-size:24px">smart_toy</span>
    <div style="flex:1"><div style="font-weight:800;color:var(--ink);font-size:14px">Sor, veriden cevaplasın</div>
      <div class="rk-sub2" style="font-size:12px">"Bu hafta ne almalıyım?", "Gizli zam var mı?", "En çok nereye harcıyorum?" gibi sorular. Senin kritik stok, fiyat geçmişi ve tedarikçi verini kullanır. <b>Not:</b> AI bakiyesi yoksa veriden kural-özet döner.</div></div>
  </div>

  {{-- Hızlı sorular --}}
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
    @foreach (['Bu hafta ne almalıyım?','Gizli zam var mı?','En çok nereye harcıyorum?','Hangi tedarikçiyle pazarlık etmeliyim?'] as $q)
      <button type="button" class="rk-btn bos" style="padding:7px 12px;font-size:12.5px" onclick="saSor(this.textContent)">{{ $q }}</button>
    @endforeach
  </div>

  {{-- Sohbet --}}
  <div id="saChat" style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;min-height:60px"></div>

  <div class="rk-card" style="position:sticky;bottom:10px;display:flex;gap:8px;align-items:center;padding:10px 12px">
    <input type="text" id="saInput" placeholder="Satınalma sorunu yaz…" class="rk-inp" style="flex:1" onkeydown="if(event.key==='Enter')saSor()">
    <button type="button" class="rk-btn" style="background:var(--mor1);padding:11px 16px" onclick="saSor()"><span class="material-icons-outlined" style="color:#fff;font-size:18px">send</span></button>
  </div>
</div>

<script>
const SA_CSRF = document.querySelector('meta[name=csrf-token]').content;
function saBubble(metin, ben){
  const d=document.createElement('div');
  d.style.cssText='max-width:82%;padding:11px 14px;border-radius:14px;font-size:13.5px;white-space:pre-wrap;line-height:1.5;'+
    (ben?'align-self:flex-end;background:var(--mor1);color:#fff;border-bottom-right-radius:4px':'align-self:flex-start;background:var(--card2);color:var(--ink);border:1px solid var(--line);border-bottom-left-radius:4px');
  d.textContent=metin;
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  return d;
}
let saBusy=false;
async function saSor(metin){
  if(saBusy) return;
  const inp=document.getElementById('saInput');
  const soru=(metin||inp.value||'').trim();
  if(!soru) return;
  inp.value='';
  saBubble(soru,true);
  saBusy=true;
  const bekle=saBubble('…',false); bekle.style.opacity='.6';
  try{
    const r=await fetch('/satinalma-asistan/sor',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':SA_CSRF,'Accept':'application/json'},body:JSON.stringify({soru})});
    const j=await r.json();
    bekle.remove();
    saBubble(j.cevap||'Cevap alınamadı.',false);
  }catch(e){ bekle.remove(); saBubble('Bağlantı hatası, tekrar dene.',false); }
  saBusy=false;
}
</script>
@endsection

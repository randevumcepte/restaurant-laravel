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
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
    @foreach (['Bu hafta ne almalıyım?','Gizli zam var mı?','En çok nereye harcıyorum?','Hangi tedarikçiyle pazarlık etmeliyim?'] as $q)
      <button type="button" class="rk-btn bos" style="padding:7px 12px;font-size:12.5px" onclick="saSor(this.textContent)">{{ $q }}</button>
    @endforeach
  </div>

  {{-- Kalıcı: Rapor İndir --}}
  <div style="margin-bottom:12px">
    <button type="button" class="rk-btn bos" style="padding:7px 12px;font-size:12.5px;display:inline-flex;align-items:center;gap:6px" onclick="saRaporMenu()"><span class="material-icons-outlined" style="font-size:16px">file_download</span> Rapor İndir (Excel)</button>
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
// 6 hazır rapor (indirme)
const SA_RAPORLAR=[
  {tip:'kritik',ad:'Kritik Stok'},{tip:'zam',ad:'Gizli Zamlar'},{tip:'tedarikci',ad:'Tedarikçi Karnesi'},
  {tip:'harcama',ad:'Aylık Harcama'},{tip:'siparis',ad:'Açık Siparişler'},{tip:'talep',ad:'Talepler'}
];
let saTeklifAcik=false; // AI "aktarayım mı?" teklifi bekliyor mu

// İndirme butonlarını bir bot balonu olarak ekle
function saExportButonlar(list){
  saTeklifAcik=false;
  const d=document.createElement('div');
  d.style.cssText='align-self:flex-start;max-width:90%;background:var(--card2);border:1px solid var(--line);border-radius:14px;border-bottom-left-radius:4px;padding:10px 12px;display:flex;flex-wrap:wrap;gap:7px';
  (list||SA_RAPORLAR).forEach(o=>{
    const a=document.createElement('a');
    a.href='/satinalma-asistan/excel?tip='+o.tip; a.setAttribute('download','');
    a.className='rk-btn'; a.style.cssText='background:var(--yesil);color:#fff;padding:8px 12px;font-size:12.5px;text-decoration:none;display:inline-flex;align-items:center;gap:5px';
    a.innerHTML='<span class="material-icons-outlined" style="font-size:16px">table_view</span> '+o.ad;
    d.appendChild(a);
  });
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
}
// AI proaktif teklifi: "Excel'e aktarayım mı?" + Evet butonu
function saTeklifBalonu(){
  saTeklifAcik=true;
  const d=document.createElement('div');
  d.style.cssText='align-self:flex-start;max-width:90%;background:color-mix(in srgb,var(--mor1) 8%,var(--card));border:1px solid color-mix(in srgb,var(--mor1) 30%,transparent);border-radius:14px;border-bottom-left-radius:4px;padding:10px 13px;font-size:12.5px;color:var(--ink)';
  d.innerHTML='<div style="margin-bottom:8px">📊 İstersen bu verileri <b>Excel\'e aktarayım</b> — aktarayım mı?</div>';
  const b=document.createElement('button');
  b.type='button'; b.className='rk-btn'; b.style.cssText='background:var(--mor1);color:#fff;padding:7px 14px;font-size:12.5px';
  b.textContent='Evet, Excel\'e aktar';
  b.onclick=()=>{ b.disabled=true; saExportButonlar(); };
  d.appendChild(b);
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
}
// Kalıcı "Rapor İndir" butonu
function saRaporMenu(){
  saBubble('Hangi raporu indirmek istersin?',false);
  saExportButonlar();
}

let saBusy=false;
async function saSor(metin){
  if(saBusy) return;
  const inp=document.getElementById('saInput');
  const soru=(metin||inp.value||'').trim();
  if(!soru) return;
  inp.value='';
  // AI "aktarayım mı?" teklif ettiyse ve kullanıcı onayladıysa -> direkt indir (sunucuya gitme)
  if(saTeklifAcik && /^(evet|evt|olur|aktar|tabii|tamam|he|hı|hi|ok|okey|peki|yes)\b/i.test(soru)){
    saBubble(soru,true); saExportButonlar(); return;
  }
  saBubble(soru,true);
  saBusy=true;
  const bekle=saBubble('…',false); bekle.style.opacity='.6';
  try{
    const r=await fetch('/satinalma-asistan/sor',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':SA_CSRF,'Accept':'application/json'},body:JSON.stringify({soru})});
    const j=await r.json();
    bekle.remove();
    saBubble(j.cevap||'Cevap alınamadı.',false);
    if(j.exportlar && j.exportlar.length){ saExportButonlar(j.exportlar); }
    else if(j.teklif_export){ saTeklifBalonu(); }
  }catch(e){ bekle.remove(); saBubble('Bağlantı hatası, tekrar dene.',false); }
  saBusy=false;
}
</script>
@endsection

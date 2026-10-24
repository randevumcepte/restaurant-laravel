@extends('layout.app')
@section('title', 'Satınalma AI Asistanı')
@section('rk_ikon', 'smart_toy')
@section('baslik', 'Satınalma AI Asistanı')
@section('rk_alt', 'Stok + fiyat + tedarikçi verinden anında cevap')

@section('content')
<style>
  /* Bu sayfaya özel: içerik zemini komple beyaz (koyu modda koyu kart) — mavi/lilak kalksın */
  .rk-main{background:var(--card)}
  #saChat .sa-h{display:inline-block;font-weight:700;font-size:14px;color:var(--mor1,#7C3AED);margin:6px 0 2px;letter-spacing:.2px}
  #saChat b{font-weight:600;color:inherit}
  #saChat .sa-mad{color:var(--mor1,#7C3AED);font-weight:700}
  /* AI yazıyor göstergesi (renkli + animasyonlu) */
  #saChat .sa-yaziyor{align-self:flex-start;display:inline-flex;align-items:center;gap:9px;background:var(--card);border:1px solid var(--line);border-radius:14px;border-bottom-left-radius:4px;padding:9px 14px;box-shadow:0 1px 4px rgba(0,0,0,.05);scroll-margin-bottom:110px}
  #saChat .sa-yaziyor .sa-orb{width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#4F46E5);display:flex;align-items:center;justify-content:center;animation:saPulse 1.5s ease-in-out infinite}
  #saChat .sa-yaziyor .sa-orb .material-icons-outlined{font-size:15px;color:#fff}
  #saChat .sa-yaziyor .sa-yz-txt{font-size:12.5px;font-weight:700;background:linear-gradient(90deg,#7C3AED,#4F46E5,#EC4899,#7C3AED);background-size:200% auto;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;color:transparent;animation:saShine 2.2s linear infinite}
  #saChat .sa-yaziyor .sa-dots{display:inline-flex;gap:3px}
  #saChat .sa-yaziyor .sa-dots i{width:6px;height:6px;border-radius:50%;display:inline-block;animation:saBounce 1.2s infinite}
  #saChat .sa-yaziyor .sa-dots i:nth-child(1){background:#7C3AED}
  #saChat .sa-yaziyor .sa-dots i:nth-child(2){background:#4F46E5;animation-delay:.18s}
  #saChat .sa-yaziyor .sa-dots i:nth-child(3){background:#EC4899;animation-delay:.36s}
  @keyframes saBounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-5px);opacity:1}}
  @keyframes saPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(124,58,237,.4)}50%{transform:scale(1.1);box-shadow:0 0 0 7px rgba(124,58,237,0)}}
  @keyframes saShine{to{background-position:200% center}}
  /* Mesajlar sticky input'un altında kalmasın */
  #saChat>div{scroll-margin-bottom:110px}
  #saWrap{padding-bottom:30px}
</style>
<div id="saWrap" style="max-width:760px;margin:0 auto">
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
  <div id="saChat" style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;min-height:60px;padding-bottom:120px"></div>

  <div class="rk-card" style="position:sticky;bottom:10px;display:flex;gap:8px;align-items:center;padding:10px 12px">
    <input type="text" id="saInput" placeholder="Satınalma sorunu yaz…" class="rk-inp" style="flex:1" onkeydown="if(event.key==='Enter')saSor()">
    <button type="button" class="rk-btn" style="background:var(--mor1);padding:11px 16px" onclick="saSor()"><span class="material-icons-outlined" style="color:#fff;font-size:18px">send</span></button>
  </div>
</div>

<script>
const SA_CSRF = document.querySelector('meta[name=csrf-token]').content;
// Güvenli markdown -> HTML (sadece AI balonu için): başlık, kalın, madde
function saEsc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function saFormat(t){
  let h=saEsc(t);
  h=h.replace(/^\s*#{1,6}\s*(.+?)\s*$/gm,'<span class="sa-h">$1</span>');       // başlık
  h=h.replace(/\*\*([^*]+?)\*\*/g,'<b>$1</b>');                                 // **kalın**
  h=h.replace(/__([^_]+?)__/g,'<b>$1</b>');                                     // __kalın__
  h=h.replace(/^[ \t]*[-*•]\s+/gm,'<span class="sa-mad">•</span> ');            // madde imi
  return h;
}
// Renkli + animasyonlu "AI yazıyor" göstergesi
function saYaziyor(){
  const d=document.createElement('div');
  d.className='sa-yaziyor';
  d.innerHTML='<span class="sa-orb"><span class="material-icons-outlined">auto_awesome</span></span>'+
    '<span class="sa-yz-txt">AI düşünüyor</span>'+
    '<span class="sa-dots"><i></i><i></i><i></i></span>';
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  return d;
}
function saBubble(metin, ben){
  const d=document.createElement('div');
  d.style.cssText='max-width:86%;padding:11px 14px;border-radius:14px;font-size:13.5px;white-space:pre-wrap;line-height:1.55;'+
    (ben?'align-self:flex-end;background:var(--mor1);color:#fff;border-bottom-right-radius:4px':'align-self:flex-start;background:var(--card);color:var(--ink);border:1px solid var(--line);border-bottom-left-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.04)');
  if(ben) d.textContent=metin; else d.innerHTML=saFormat(metin);
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
  return d;
}
// 6 hazır rapor (indirme)
const SA_RAPORLAR=[
  {tip:'kritik',ad:'Kritik Stok'},{tip:'zam',ad:'Gizli Zamlar'},{tip:'tedarikci',ad:'Tedarikçi Karnesi'},
  {tip:'harcama',ad:'Aylık Harcama'},{tip:'siparis',ad:'Açık Siparişler'},{tip:'talep',ad:'Talepler'}
];
let saTeklifAcik=false;   // AI "aktarayım mı?" teklifi bekliyor mu
let saTeklifTip='kritik'; // teklif edilen rapor tipi

const SA_AD={kritik:'Kritik Stok',zam:'Gizli Zamlar',tedarikci:'Tedarikçi Karnesi',harcama:'Aylık Harcama',siparis:'Açık Siparişler',talep:'Talepler'};

// Dosyayı GERÇEKTEN indir (navigasyon yapmadan — CSV attachment döner)
function saIndir(tip){
  const f=document.createElement('iframe');
  f.style.display='none'; f.src='/satinalma-asistan/excel?tip='+tip;
  document.body.appendChild(f);
  setTimeout(()=>f.remove(),15000);
}

// İndirme butonlarını bir bot balonu olarak ekle (tıklayınca indirir)
function saExportButonlar(list){
  saTeklifAcik=false;
  const d=document.createElement('div');
  d.style.cssText='align-self:flex-start;max-width:90%;background:var(--card);border:1px solid var(--line);border-radius:14px;border-bottom-left-radius:4px;padding:10px 12px;display:flex;flex-wrap:wrap;gap:7px';
  (list||SA_RAPORLAR).forEach(o=>{
    const b=document.createElement('button');
    b.type='button'; b.className='rk-btn';
    b.style.cssText='background:#10B981;color:#fff;padding:8px 12px;font-size:12.5px;border:0;border-radius:10px;cursor:pointer;display:inline-flex;align-items:center;gap:5px';
    b.innerHTML='<span class="material-icons-outlined" style="font-size:16px">table_view</span> '+o.ad;
    b.onclick=()=>saIndir(o.tip);
    d.appendChild(b);
  });
  document.getElementById('saChat').appendChild(d);
  d.scrollIntoView({behavior:'smooth',block:'end'});
}
// AI proaktif teklifi: "Excel'e aktarayım mı?" + Evet (direkt indirir)
function saTeklifBalonu(tip){
  saTeklifAcik=true; saTeklifTip=tip||'kritik';
  const d=document.createElement('div');
  d.style.cssText='align-self:flex-start;max-width:90%;background:#f3eefe;border:1px solid #c9b6f5;border-radius:14px;border-bottom-left-radius:4px;padding:10px 13px;font-size:12.5px;color:var(--ink)';
  d.innerHTML='<div style="margin-bottom:8px">📊 İstersen bu verileri (<b>'+(SA_AD[saTeklifTip]||'rapor')+'</b>) <b>Excel\'e aktarayım</b> — aktarayım mı?</div>';
  const b=document.createElement('button');
  b.type='button'; b.style.cssText='background:#7C3AED;color:#fff;padding:7px 14px;font-size:12.5px;border:0;border-radius:10px;cursor:pointer';
  b.textContent='Evet, Excel\'e aktar';
  b.onclick=()=>{ b.disabled=true; b.textContent='İndiriliyor…'; saIndir(saTeklifTip); saTeklifAcik=false; };
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
  if(saTeklifAcik && /^(evet|evt|olur|aktar|tabii|tabi|tamam|he|hı|hi|ok|okey|peki|yes)\b/i.test(soru)){
    saBubble(soru,true); saBubble('Tamam, '+(SA_AD[saTeklifTip]||'rapor')+' indiriliyor… 📥',false); saIndir(saTeklifTip); saTeklifAcik=false; return;
  }
  saBubble(soru,true);
  saBusy=true;
  const bekle=saYaziyor();
  try{
    const r=await fetch('/satinalma-asistan/sor',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':SA_CSRF,'Accept':'application/json'},body:JSON.stringify({soru})});
    const j=await r.json();
    bekle.remove();
    saBubble(j.cevap||'Cevap alınamadı.',false);
    // Sadece kullanıcı açıkça isteyince (excel/indir/csv) indirme butonları çıkar; otomatik teklif YOK
    if(j.exportlar && j.exportlar.length){ saExportButonlar(j.exportlar); }
  }catch(e){ bekle.remove(); saBubble('Bağlantı hatası, tekrar dene.',false); }
  saBusy=false;
}
</script>
@endsection

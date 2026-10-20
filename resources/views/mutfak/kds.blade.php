@extends('layout.app')
@section('title', 'Mutfak (KDS)')
@section('rk_ikon', 'soup_kitchen')
@section('baslik', '👨‍🍳 Mutfak · Siparişler')

@push('head')
<style>
#kds{--mor:#9D5DC8;--yesil:#10B981;--amber:#F59E0B;--kirmizi:#F43F5E;--mavi:#4F46E5;--line:#e2e8f0;--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b}
body.koyu #kds{--line:#2c3650;--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8}
#kds .kds-tabs{display:flex;border-bottom:1px solid var(--line);margin:-8px -8px 12px}
#kds .kds-tab{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:10px 4px 8px;cursor:pointer;border-bottom:3px solid transparent;color:var(--sub);font-weight:700;font-size:12px}
#kds .kds-tab.akt{color:var(--ink);border-bottom-color:var(--mor)}
#kds .kds-tab .ikw{position:relative}
#kds .kds-tab .material-icons-outlined{font-size:22px}
#kds .kds-tab .rz{position:absolute;top:-6px;right:-12px;min-width:16px;height:16px;padding:0 4px;border-radius:9px;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center}
#kds .kds-ust{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px}
#kds .cip{display:inline-flex;align-items:center;gap:5px;cursor:pointer;border:1px solid var(--line);background:var(--card);border-radius:20px;padding:7px 12px;font-size:12.5px;font-weight:600;color:var(--sub)}
#kds .cip.akt{background:var(--mor);border-color:var(--mor);color:#fff}
#kds .cip .n{background:var(--card2);color:var(--mor);border-radius:20px;padding:0 6px;font-size:11px;font-weight:700}
#kds .cip.akt .n{background:rgba(255,255,255,.28);color:#fff}
#kds .kds-sag{margin-left:auto;display:flex;gap:8px;align-items:center}
#kds .boyut{display:inline-flex;align-items:center;gap:4px;color:var(--sub);font-size:12px}
#kds .boyut button{width:26px;height:26px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--ink);cursor:pointer;font-size:16px;line-height:1}
#kds .board{column-gap:10px}
#kds .fis{break-inside:avoid;margin-bottom:10px;border-radius:14px;border:2px solid var(--amber);background:var(--card);overflow:hidden;box-shadow:0 3px 8px rgba(0,0,0,.06)}
#kds .fis.yesil{border-color:var(--yesil)} #kds .fis.kirmizi{border-color:var(--kirmizi)} #kds .fis.amber{border-color:var(--amber)}
#kds .fis.blink{animation:kdsblink 1s steps(1) infinite}
@keyframes kdsblink{50%{border-color:var(--kirmizi);box-shadow:0 0 0 2px rgba(244,63,94,.35)}}
#kds .fis .bas{padding:7px 10px 6px}
#kds .fis .r1{display:flex;align-items:center;gap:6px}
#kds .rozet{padding:3px 8px;border-radius:20px;color:#fff;font-size:11px;font-weight:700}
#kds .fis .urn{color:var(--sub);font-size:11.5px;font-weight:600;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#kds .one{width:26px;height:26px;border-radius:7px;border:none;background:rgba(157,93,200,.14);color:var(--mor);cursor:pointer}
#kds .fis .r2{display:flex;align-items:center;gap:5px;margin-top:6px}
#kds .fis .masa{color:var(--ink);font-size:14.5px;font-weight:800;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#kds .fis .no{color:var(--mor);font-size:13.5px;font-weight:900}
#kds .fis .sure{display:flex;align-items:center;gap:5px;padding:6px 12px 0;font-size:12px;color:var(--sub)}
#kds .fis .bar{height:5px;border-radius:6px;margin:6px 12px 2px;overflow:hidden}
#kds .fis .bar>i{display:block;height:100%;border-radius:6px}
#kds .fis .kalemler{padding:6px 12px 4px}
#kds .fis .kal{display:flex;gap:7px;padding:2px 0;color:var(--ink);font-size:13px}
#kds .fis .kal b{color:var(--mor);min-width:26px}
#kds .fis .kal .not{color:var(--kirmizi);font-size:11px}
#kds .fis .tur{color:var(--mor);font-size:10.5px;font-weight:700;margin:4px 0 2px;border-top:1px dashed var(--line);padding-top:4px}
#kds .fis .btns{display:flex;gap:7px;padding:4px 10px 10px}
#kds .fis .btns button{flex:1;height:38px;border-radius:10px;font-weight:700;font-size:14px;cursor:pointer}
#kds .bt-basla{background:transparent;border:1.4px solid var(--amber);color:var(--amber)}
#kds .bt-hazir{background:var(--yesil);border:none;color:#fff}
#kds .bt-servis{background:var(--mavi);border:none;color:#fff}
#kds .bos{text-align:center;color:var(--sub);padding:60px 10px;font-size:15px}
#kds .u86{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:12px;background:var(--card);border:1px solid var(--line);margin-bottom:8px}
#kds .u86.tuk{background:rgba(244,63,94,.07);border-color:rgba(244,63,94,.3)}
#kds .u86 .ad{flex:1;font-weight:700;color:var(--ink)}
#kds .u86 .ad.tuk{text-decoration:line-through;color:var(--kirmizi)}
#kds .u86 button{padding:7px 14px;border-radius:9px;border:none;font-weight:700;cursor:pointer;color:#fff}
#kds .anz{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
#kds .anz .k{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px}
#kds .anz .k .v{font-size:26px;font-weight:900;color:var(--ink)} #kds .anz .k .l{font-size:12px;color:var(--sub)}
#kds .oneri{display:flex;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:8px;color:var(--ink);font-size:13.5px}
</style>
@endpush

@section('content')
<div id="kds">
    <div class="kds-tabs">
        <div class="kds-tab akt" data-t="0" onclick="kdsTab(0)"><span class="ikw"><span class="material-icons-outlined">receipt_long</span><span class="rz" data-rz="0" style="background:var(--mor);display:none"></span></span>Sipariş</div>
        <div class="kds-tab" data-t="1" onclick="kdsTab(1)"><span class="ikw"><span class="material-icons-outlined">room_service</span><span class="rz" data-rz="1" style="background:var(--mavi);display:none"></span></span>Servis</div>
        <div class="kds-tab" data-t="2" onclick="kdsTab(2)"><span class="ikw"><span class="material-icons-outlined">block</span><span class="rz" data-rz="2" style="background:var(--kirmizi);display:none"></span></span>86</div>
        <div class="kds-tab" data-t="3" onclick="kdsTab(3)"><span class="ikw"><span class="material-icons-outlined">insights</span></span>Analiz</div>
    </div>
    <div data-pane="0"></div>
    <div data-pane="1" style="display:none"></div>
    <div data-pane="2" style="display:none"></div>
    <div data-pane="3" style="display:none"></div>
</div>
<script>
const KDS = {tab:0, ist:'hepsi', toplu:false, boyut:2, veri:null, servis:null, urun:null, analiz:null, sira:[]};
const KBOY = [260,230,200,175]; // sutun genisligi (boyut 0..3)
function kesc(s){return (s==null?'':String(s)).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function ksure(dk){dk=Math.round(dk); if(dk<60)return dk+' dk'; if(dk<1440)return Math.floor(dk/60)+' sa '+(dk%60)+' dk'; return Math.floor(dk/1440)+' g '+Math.floor((dk%1440)/60)+' sa';}
function kkalan(k){return k>0?('~'+ksure(k)+' kaldı'):(k===0?'süresi doldu':ksure(-k)+' gecikme');}
function kadet(v){return v===Math.round(v)?String(Math.round(v)):String(v);}
function krenk(r){return r==='kirmizi'?'kirmizi':(r==='amber'?'amber':'yesil');}
function kkanal(s){if(s.masali)return '🪑'; const k=(s.kanal||'').toLowerCase();
  if(/paket|kurye|yemeksepeti|getir|trendyol/.test(k))return '🛵'; if(/gel|al/.test(k))return '🛍️';
  if(/online|web|app/.test(k))return '📱'; return '🧾';}

async function kget(u){const r=await fetch(u,{headers:{'Accept':'application/json'}}); return r.json();}
async function kpost(u,d){const r=await fetch(u,{method:'POST',headers:{'X-CSRF-TOKEN':window.csrf,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(d)}); return r.json();}

function kcolVar(){document.querySelector('#kds .board')?.style.setProperty('column-width', KBOY[KDS.boyut]+'px');}

// --- Sipariş kartı (Flutter _kart birebir) ---
function kfis(s){
  const basladi = s.asama==='hazirlaniyor'||s.basladi===true;
  const renk = krenk(s.renk);
  const kalan = Number(s.kalan||0), gecen=Number(s.gecen||s.dk||0), hedef=Number(s.hedef||15);
  const oran = hedef>0?Math.min(1,gecen/hedef):0;
  const hzG = Number(s.hazirlik_gecen||0), hzK = hedef-hzG;
  const prepRenk = hzK<0?'var(--kirmizi)':(hzK<=hedef/3?'var(--amber)':'var(--yesil)');
  const prepOran = hedef>0?Math.min(1,hzG/hedef):0;
  const gecikmeVar = kalan<0||s.renk==='kirmizi';
  const kritik = s.hazirlik_asti===true;
  const blink = kritik||(gecikmeVar&&!basladi);
  const minTur = Number(s.min_tur||1);
  const kalemler = s.kalemler||[];
  const toplamAdet = kalemler.reduce((t,k)=>t+Number(k.adet||0),0);
  const adId = Number(s.adisyon_id);
  let kalHtml='',onceki=null;
  for(const k of kalemler){const t=Number(k.tur||1);
    if(onceki!==null&&t!==onceki) kalHtml+='<div class="tur">'+t+'. Tur</div>'; onceki=t;
    kalHtml+='<div class="kal"><b>'+kadet(Number(k.adet))+'×</b><div>'+kesc(k.ad)+(k.not?'<div class="not">📝 '+kesc(k.not)+'</div>':'')+'</div></div>';}
  let sureHtml;
  if(!basladi){
    sureHtml='<div class="sure"><span class="material-icons-outlined" style="font-size:13px;color:var(--'+renk+')">schedule</span>'+ksure(gecen)+' geçti <b style="color:var(--'+renk+')">'+kkalan(kalan)+'</b></div>'
      +'<div class="bar" style="background:color-mix(in srgb,var(--'+renk+') 15%,transparent)"><i style="width:'+(oran*100)+'%;background:var(--'+renk+')"></i></div>';
  } else {
    sureHtml='<div class="sure"><span class="material-icons-outlined" style="font-size:14px;color:'+prepRenk+'">outdoor_grill</span>hazırlık '+ksure(hzG)+' <b style="color:'+prepRenk+'">'+kkalan(hzK)+'</b></div>'
      +'<div class="bar" style="background:color-mix(in srgb,'+prepRenk+' 15%,transparent)"><i style="width:'+(prepOran*100)+'%;background:'+prepRenk+'"></i></div>'
      +'<div style="padding:2px 12px 0;color:var(--sub);font-size:10.5px">toplam '+ksure(gecen)+' (geldiğinden beri)</div>';
  }
  const basRenk = basladi?'var(--amber)':'var(--yesil)';
  return '<div class="fis '+renk+(blink?' blink':'')+'">'
    +'<div class="bas" style="background:color-mix(in srgb,var(--'+renk+') 12%,transparent)">'
      +'<div class="r1"><span class="rozet" style="background:'+basRenk+'">'+(basladi?'👨‍🍳 Hazırlanıyor':'🆕 Yeni')+'</span>'
        +(minTur>1?'<span class="rozet" style="background:var(--mor)">'+minTur+'. Tur</span>':'')
        +'<span class="urn">· '+kadet(toplamAdet)+' ürün</span>'
        +'<button class="one" title="Öne al" onclick="kOneAl('+adId+')">⤒</button></div>'
      +'<div class="r2"><span style="font-size:17px">'+kkanal(s)+'</span>'
        +(s.masali?'<span class="masa">'+kesc(s.masa)+'</span>':'<span style="flex:1"></span>')
        +'<span style="color:var(--sub);font-size:10.5px">No </span><span class="no">#'+adId+'</span></div>'
    +'</div>'+sureHtml
    +'<div class="kalemler">'+kalHtml+'</div>'
    +'<div class="btns">'
      +(!basladi?'<button class="bt-basla" onclick="kBasla('+adId+')">▶ Başla</button>':'')
      +'<button class="bt-hazir" onclick="kHazir('+adId+')">✓ Hazır</button></div>'
  +'</div>';
}

function kRenderSip(){
  const d=KDS.veri; const pane=document.querySelector('#kds [data-pane="0"]'); if(!d){pane.innerHTML='<div class="bos">Yükleniyor…</div>';return;}
  let cip='<div class="cip'+(KDS.ist==='hepsi'?' akt':'')+'" onclick="kIst(\'hepsi\')">Tümü'+(d.toplam_bekleyen>0?'<span class="n">'+d.toplam_bekleyen+'</span>':'')+'</div>';
  for(const i of (d.istasyonlar||[])) cip+='<div class="cip'+(KDS.ist===i.kod?' akt':'')+'" onclick="kIst(\''+i.kod+'\')">'+kesc(i.ad)+(i.bekleyen>0?'<span class="n">'+i.bekleyen+'</span>':'')+'</div>';
  let ust='<div class="kds-ust">'+cip
    +'<div class="kds-sag"><div class="cip'+(KDS.toplu?' akt':'')+'" onclick="kToplu()">▦ Toplu</div>'
    +'<div class="boyut">Boyut <button onclick="kBoyut(-1)">−</button><button onclick="kBoyut(1)">+</button></div></div></div>';
  let govde;
  if(KDS.toplu){
    const t=d.toplu||[];
    govde = t.length? t.map(x=>{const rn=x.dk>=15?'kirmizi':(x.dk>=8?'amber':'yesil');
      return '<div style="display:flex;align-items:center;gap:12px;background:var(--card);border-left:4px solid var(--'+rn+');border-radius:12px;padding:12px;margin-bottom:8px">'
      +'<div style="width:46px;height:46px;display:flex;align-items:center;justify-content:center;border-radius:10px;font-size:20px;font-weight:800;color:var(--'+rn+');background:color-mix(in srgb,var(--'+rn+') 16%,transparent)">'+kadet(Number(x.adet))+'</div>'
      +'<div style="flex:1"><div style="font-weight:700;color:var(--ink)">'+kesc(x.ad)+'</div><div style="color:var(--sub);font-size:12px">'+kesc(x.istasyon_ad)+'</div></div>'
      +'<div style="color:var(--'+rn+');font-weight:700;font-size:12px">en eski '+ksure(x.dk)+'</div></div>';}).join('')
      : '<div class="bos">Üretilecek ürün yok. 👨‍🍳</div>';
    govde='<div>'+govde+'</div>';
  } else {
    const sip = kSiraUygula(d.siparisler||[]);
    govde = sip.length? '<div class="board" style="column-width:'+KBOY[KDS.boyut]+'px">'+sip.map(kfis).join('')+'</div>' : '<div class="bos">Bekleyen sipariş yok. 👨‍🍳</div>';
    document.querySelector('[data-rz="0"]').textContent=sip.length; document.querySelector('[data-rz="0"]').style.display=sip.length?'flex':'none';
  }
  pane.innerHTML=ust+govde;
  const b=document.getElementById('kdsBaslik'); if(b) b.textContent='👨‍🍳 Mutfak · Siparişler';
}
function kSiraUygula(list){ if(!KDS.sira.length) return list; const by={}; for(const e of list) by[e.adisyon_id]=e; const out=[];
  for(const id of KDS.sira){ if(by[id]){out.push(by[id]); delete by[id];} } for(const k in by) out.push(by[k]);
  KDS.sira=out.map(e=>e.adisyon_id); return out; }
function kOneAl(id){ const i=KDS.sira.indexOf(id); const cur=(KDS.veri.siparisler||[]).map(e=>e.adisyon_id); KDS.sira=[id,...cur.filter(x=>x!==id)]; kRenderSip(); }

function kRenderServis(){
  const d=KDS.servis; const pane=document.querySelector('#kds [data-pane="1"]'); if(!d){pane.innerHTML='<div class="bos">Yükleniyor…</div>';return;}
  const sip=d.siparisler||[]; document.querySelector('[data-rz="1"]').textContent=sip.length; document.querySelector('[data-rz="1"]').style.display=sip.length?'flex':'none';
  if(!sip.length){pane.innerHTML='<div class="bos">Servise hazır ürün yok. 🍽️</div>';return;}
  pane.innerHTML='<div class="board" style="column-width:230px">'+sip.map(s=>{
    const dk=Number(s.dk||0); const rn=dk>=5?'kirmizi':'mavi'; const adId=Number(s.adisyon_id);
    let kal=(s.kalemler||[]).map(k=>'<div class="kal"><b>'+kadet(Number(k.adet))+'×</b><div>'+kesc(k.ad)+'</div></div>').join('');
    return '<div class="fis '+(dk>=5?'kirmizi':'yesil')+'"><div class="bas" style="background:color-mix(in srgb,var(--mavi) 12%,transparent)">'
      +'<div class="r2"><span style="font-size:17px">'+kkanal(s)+'</span>'+(s.masali?'<span class="masa">'+kesc(s.masa)+'</span>':'<span style="flex:1"></span>')
      +'<span class="no">#'+adId+'</span></div><div style="color:var(--sub);font-size:11.5px;margin-top:4px">🔔 '+dk+' dk bekliyor</div></div>'
      +'<div class="kalemler">'+kal+'</div><div class="btns"><button class="bt-servis" onclick="kServis('+adId+')">✓ Servis Edildi</button></div></div>';
  }).join('')+'</div>';
}

function kRender86(){
  const d=KDS.urun; const pane=document.querySelector('#kds [data-pane="2"]'); if(!d){pane.innerHTML='<div class="bos">Yükleniyor…</div>';return;}
  const rz=document.querySelector('[data-rz="2"]'); rz.textContent=d.tukenen; rz.style.display=d.tukenen>0?'flex':'none';
  let h='<input id="k86ara" placeholder="Ürün ara…" oninput="k86Ara(this.value)" style="width:100%;max-width:360px;padding:10px 12px;border:1px solid var(--line);border-radius:10px;margin-bottom:12px;background:var(--card);color:var(--ink)">';
  h+='<div id="k86liste">'+(d.urunler||[]).map(u=>k86Satir(u)).join('')+'</div>';
  pane.innerHTML=h;
}
function k86Satir(u){return '<div class="u86'+(u.tukendi?' tuk':'')+'" data-ad="'+kesc((u.ad||'').toLowerCase())+'">'
  +'<div class="ad'+(u.tukendi?' tuk':'')+'">'+kesc(u.ad)+'</div><div style="color:var(--sub);font-size:11.5px">'+kesc(u.kategori)+'</div>'
  +'<button style="background:'+(u.tukendi?'var(--yesil)':'var(--kirmizi)')+'" onclick="k86Toggle('+u.id+',this)">'+(u.tukendi?'Tekrar Sat':'86 / Tükendi')+'</button></div>';}
function k86Ara(v){v=(v||'').toLowerCase(); document.querySelectorAll('#k86liste .u86').forEach(e=>e.style.display=e.dataset.ad.includes(v)?'flex':'none');}

function kRenderAnaliz(){
  const d=KDS.analiz; const pane=document.querySelector('#kds [data-pane="3"]'); if(!d){pane.innerHTML='<div class="bos">Yükleniyor…</div>';return;}
  const o=d.ozet||{};
  const kart=(v,l)=>'<div class="k"><div class="v">'+v+'</div><div class="l">'+l+'</div></div>';
  let h='<div class="anz">'+kart((o.ort_dk??'—')+(o.ort_dk!=null?' dk':''),'Ort. hazırlık')+kart((o.ort_gecikme??'—')+(o.ort_gecikme!=null?' dk':''),'Ort. gecikme')
    +kart(o.bekleyen??0,'Şu an bekleyen')+kart(o.geciken??0,'15dk+ geciken')+kart(o.en_eski_dk?ksure(o.en_eski_dk):'—','En eski')+'</div>';
  if((d.en_yavas||[]).length){h+='<h3 style="color:var(--ink);font-size:14px;margin:4px 0 8px">🐢 En yavaş ürünler</h3>';
    h+=(d.en_yavas).map(x=>'<div class="oneri"><b>'+kesc(x.ad)+'</b><span style="margin-left:auto;color:var(--sub)">ort '+x.dk+' dk · '+x.adet+'×</span></div>').join('');}
  if((d.istasyon_yuku||[]).length){h+='<h3 style="color:var(--ink);font-size:14px;margin:14px 0 8px">İstasyon yükü</h3>';
    h+=(d.istasyon_yuku).map(x=>'<div class="oneri">'+kesc(x.ad)+'<span style="margin-left:auto;font-weight:700">'+x.adet+' ürün</span></div>').join('');}
  if((d.oneriler||[]).length){h+='<h3 style="color:var(--ink);font-size:14px;margin:14px 0 8px">Öneriler</h3>';
    h+=(d.oneriler).map(x=>'<div class="oneri"><span style="font-size:18px">'+x.ikon+'</span><span>'+kesc(x.metin)+'</span></div>').join('');}
  pane.innerHTML=h;
}

// --- aksiyonlar ---
async function kBasla(id){await kpost('/mutfak/basla',{adisyon_id:id}); kYukle(0);}
async function kHazir(id){await kpost('/mutfak/adisyon-hazir',{adisyon_id:id}); kYukle(0); kYukle(1);}
async function kServis(id){await kpost('/mutfak/adisyon-servis',{adisyon_id:id}); kYukle(1);}
async function k86Toggle(id,btn){const r=await kpost('/mutfak/86-toggle',{urun_id:id}); kYukle(2);}
function kIst(k){KDS.ist=k; kYukle(0);}
function kToplu(){KDS.toplu=!KDS.toplu; kRenderSip();}
function kBoyut(d){KDS.boyut=Math.max(0,Math.min(3,KDS.boyut+(-d))); kRenderSip();}

async function kYukle(t){
  if(t===0){KDS.veri=await kget('/mutfak/veri?istasyon='+encodeURIComponent(KDS.ist)); kRenderSip();}
  if(t===1){KDS.servis=await kget('/mutfak/servise-veri'); kRenderServis();}
  if(t===2){KDS.urun=await kget('/mutfak/urunler-veri'); kRender86();}
  if(t===3){KDS.analiz=await kget('/mutfak/analiz-veri'); kRenderAnaliz();}
}
function kdsTab(t){KDS.tab=t;
  document.querySelectorAll('#kds .kds-tab').forEach((e,i)=>e.classList.toggle('akt',i===t));
  document.querySelectorAll('#kds [data-pane]').forEach((e,i)=>e.style.display=(i===t?'block':'none'));
  kYukle(t);
}
kYukle(0); kYukle(1); kYukle(2); // rozetler bastan dolsun
setInterval(()=>{ if(KDS.tab===0)kYukle(0); if(KDS.tab===1)kYukle(1); }, 15000);
</script>
@endsection

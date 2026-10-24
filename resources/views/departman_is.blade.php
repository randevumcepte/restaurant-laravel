@extends('layout.app')
@section('title', $baslik ?? 'Departman')
@section('rk_ikon', $ikon ?? 'checklist')
@section('baslik', $baslik ?? 'Departman')

@php
    $kod = $kod ?? '';
    $renk = $renk ?? '#4F46E5';
@endphp

@push('head')
<style>
#dis {
  --mor1:#7C3AED; --mavi:#4F46E5; --yesil:#10B981; --kirmizi:#F43F5E; --amber:#F59E0B; --gri:#64748b;
  --card:#fff; --card2:#f1f5f9; --ink:#1e293b; --sub:#64748b; --sub2:#94a3b8; --line:#e2e8f0; --bg:#f8fafc;
  --ana:{{ $renk }}; max-width:1100px;
}
body.koyu #dis { --card:#1b2235; --card2:#161C2E; --ink:#e2e8f0; --sub:#94a3b8; --sub2:#64748b; --line:#2c3650; --bg:#0B1020; }
#dis .dis-ozet { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
#dis .dis-rozet { display:flex; align-items:center; gap:10px; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:12px 16px; flex:1; min-width:135px; }
#dis .dis-rozet .c { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#dis .dis-rozet .c .material-icons-outlined { font-size:21px; }
#dis .dis-rozet .s { font-size:23px; font-weight:900; color:var(--ink); line-height:1; }
#dis .dis-rozet .e { font-size:11.5px; color:var(--sub); font-weight:600; margin-top:3px; }
#dis .dis-presetler { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
#dis .dis-preset { border:1px dashed var(--ana); background:transparent; color:var(--ana); border-radius:20px; padding:7px 13px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; }
#dis .dis-preset .material-icons-outlined { font-size:15px; }
#dis .dis-ekle-btn { background:var(--ana); color:#fff; border:none; border-radius:11px; padding:10px 16px; font-size:13.5px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:7px; margin-bottom:14px; }
#dis .dis-form { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:14px; display:none; }
#dis .dis-form.acik { display:block; }
#dis .dis-form .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
#dis .dis-inp, #dis .dis-sel { background:var(--card2); border:1px solid var(--line); border-radius:10px; padding:9px 12px; font-size:13.5px; color:var(--ink); flex:1; min-width:120px; }
#dis .dis-liste { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
#dis .dis-satir { display:flex; align-items:center; gap:12px; padding:12px 15px; border-top:1px solid var(--line); }
#dis .dis-satir:first-child { border-top:none; }
#dis .dis-satir .c { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#dis .dis-satir .c .material-icons-outlined { font-size:20px; }
#dis .dis-satir .o { flex:1; min-width:0; }
#dis .dis-satir .o .b1 { font-size:14.5px; font-weight:800; color:var(--ink); }
#dis .dis-satir .o .b2 { font-size:12px; color:var(--sub); margin-top:3px; display:flex; gap:7px; flex-wrap:wrap; align-items:center; }
#dis .dis-cip { font-size:10.5px; font-weight:800; padding:1px 7px; border-radius:20px; }
#dis .dis-btn { border:none; border-radius:9px; padding:8px 11px; font-size:12px; font-weight:800; cursor:pointer; color:#fff; display:inline-flex; align-items:center; gap:4px; margin-left:5px; }
#dis .dis-btn .material-icons-outlined { font-size:15px; }
#dis .dis-btn.yesil { background:var(--yesil); } #dis .dis-btn.mavi { background:var(--mavi); } #dis .dis-btn.gri { background:var(--gri); }
#dis .dis-bos { padding:22px 16px; text-align:center; color:var(--sub); font-size:13px; }
#dis .dis-sayac { font-size:12px; color:var(--sub2); margin-top:10px; }
</style>
@endpush

@section('content')
<div id="dis">
  <div class="dis-ozet">
    <div class="dis-rozet"><div class="c" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">pending_actions</span></div><div><div class="s" id="o-acik">0</div><div class="e">Açık iş</div></div></div>
    <div class="dis-rozet"><div class="c" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">task_alt</span></div><div><div class="s" id="o-tamam">0</div><div class="e">Bugün tamamlanan</div></div></div>
    <div class="dis-rozet"><div class="c" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:var(--kirmizi)">schedule</span></div><div><div class="s" id="o-gecik">0</div><div class="e">Geciken</div></div></div>
    <div class="dis-rozet"><div class="c" style="background:rgba(245,158,11,.14)"><span class="material-icons-outlined" style="color:var(--amber)">priority_high</span></div><div><div class="s" id="o-yuksek">0</div><div class="e">Yüksek öncelik</div></div></div>
  </div>

  <div class="dis-presetler" id="presetler"></div>

  <button class="dis-ekle-btn" onclick="document.getElementById('ekle-form').classList.toggle('acik')"><span class="material-icons-outlined">add</span> Yeni İş / Kayıt</button>
  <div class="dis-form" id="ekle-form">
    <div class="row">
      <select class="dis-sel" id="f-tip">
        <option value="gorev">Görev</option><option value="kontrol">Kontrol</option><option value="olay">Olay / Tutanak</option>
        <option value="ariza">Arıza</option><option value="bakim">Bakım</option><option value="etkinlik">Etkinlik</option><option value="kayit">Kayıt</option>
      </select>
      <select class="dis-sel" id="f-oncelik"><option value="orta">Orta öncelik</option><option value="yuksek">Yüksek</option><option value="dusuk">Düşük</option></select>
      <select class="dis-sel" id="f-tekrar"><option value="">Tek seferlik</option><option value="gunluk">Günlük tekrar</option><option value="haftalik">Haftalık</option><option value="aylik">Aylık</option></select>
    </div>
    <div class="row">
      <input class="dis-inp" id="f-baslik" placeholder="Başlık *" style="min-width:220px">
      <input class="dis-inp" id="f-tarih" type="date" style="max-width:170px">
    </div>
    <div class="row" style="margin-bottom:0">
      <input class="dis-inp" id="f-aciklama" placeholder="Açıklama / not (opsiyonel)">
      <button class="dis-btn yesil" style="padding:10px 16px" onclick="isEkle()"><span class="material-icons-outlined">check</span> Ekle</button>
    </div>
  </div>

  <div class="dis-liste" id="liste"><div class="dis-bos">Açık iş yok.</div></div>
  <div class="dis-sayac" id="son-g">—</div>
</div>
@endsection

@push('scripts')
<script>
const DIS_KOD = @json($kod);
const TIP = {
  gorev:['Görev','task_alt','var(--mavi)'], kontrol:['Kontrol','checklist','var(--yesil)'], olay:['Olay','report','var(--kirmizi)'],
  ariza:['Arıza','report_problem','var(--kirmizi)'], bakim:['Bakım','handyman','var(--amber)'], etkinlik:['Etkinlik','celebration','var(--mor1)'], kayit:['Kayıt','description','var(--gri)'],
};
const ONC = {yuksek:['Yüksek','var(--kirmizi)'], orta:['Orta','var(--amber)'], dusuk:['Düşük','var(--gri)']};
const TEKRAR = {gunluk:'Günlük', haftalik:'Haftalık', aylik:'Aylık'};
function disEsc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

async function disPost(url, body){
  try { const r = await fetch(url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(body)}); return await r.json(); }
  catch(e){ return {ok:0}; }
}
async function isEkle(){
  const baslik = document.getElementById('f-baslik').value.trim();
  if(!baslik){ alert('Başlık gerekli.'); return; }
  const r = await disPost('/api/departman-is/ekle', {kod:DIS_KOD, tip:document.getElementById('f-tip').value, oncelik:document.getElementById('f-oncelik').value,
    tekrar:document.getElementById('f-tekrar').value, baslik, son_tarih:document.getElementById('f-tarih').value, aciklama:document.getElementById('f-aciklama').value.trim()});
  if(r.ok===1){ ['f-baslik','f-tarih','f-aciklama'].forEach(i=>document.getElementById(i).value=''); document.getElementById('ekle-form').classList.remove('acik'); yukle(); }
  else alert(r.hata||'Eklenemedi');
}
async function presetEkle(pr){
  await disPost('/api/departman-is/ekle', {kod:DIS_KOD, tip:pr.tip, baslik:pr.baslik, oncelik:pr.oncelik||'orta', tekrar:pr.tekrar||''});
  yukle();
}
async function durumDegis(id, durum, olcumIster){
  let deger = null;
  if(durum==='tamam' && olcumIster){ deger = prompt('Ölçüm / değer girin (ör. 4 °C):', ''); if(deger===null) return; }
  await disPost('/api/departman-is/durum', {id, durum, deger: deger||''});
  yukle();
}
async function isSil(id){ if(!confirm('Bu kayıt silinsin mi?')) return; await disPost('/api/departman-is/sil', {id}); yukle(); }

async function yukle(){
  let d; try { const r = await fetch('/api/departman-is/panel?kod='+encodeURIComponent(DIS_KOD), {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); } catch(e){ return; }
  if(!d || d.ok!==1) return;
  const oz = d.ozet||{};
  document.getElementById('o-acik').textContent = oz.acik||0;
  document.getElementById('o-tamam').textContent = oz.bugun_tamam||0;
  document.getElementById('o-gecik').textContent = oz.geciken||0;
  document.getElementById('o-yuksek').textContent = oz.yuksek||0;

  // presetler
  document.getElementById('presetler').innerHTML = (d.presetler||[]).map((pr,i)=>
    `<button class="dis-preset" onclick='presetEkle(${JSON.stringify(pr).replace(/'/g,"&#39;")})'><span class="material-icons-outlined">add</span> ${disEsc(pr.baslik)}</button>`).join('');

  // liste
  const l = document.getElementById('liste');
  if(!(d.isler||[]).length){ l.innerHTML = '<div class="dis-bos">Açık iş yok. Yukarıdan ekleyebilir ya da hazır şablonlardan seçebilirsin.</div>'; }
  else l.innerHTML = d.isler.map(x=>{
    const tp = TIP[x.tip]||TIP.gorev, on = ONC[x.oncelik]||ONC.orta;
    const olcumIster = (x.tip==='kontrol');
    const yapiliyor = x.durum==='yapiliyor';
    return `<div class="dis-satir">
      <div class="c" style="background:color-mix(in srgb, ${tp[2]} 15%, transparent)"><span class="material-icons-outlined" style="color:${tp[2]}">${tp[1]}</span></div>
      <div class="o">
        <div class="b1">${disEsc(x.baslik)}${x.deger?` · <b style="color:var(--yesil)">${disEsc(x.deger)}</b>`:''}</div>
        <div class="b2">
          <span class="dis-cip" style="background:color-mix(in srgb, ${tp[2]} 14%, transparent);color:${tp[2]}">${tp[0]}</span>
          <span class="dis-cip" style="background:color-mix(in srgb, ${on[1]} 14%, transparent);color:${on[1]}">${on[0]}</span>
          ${x.tekrar?`<span style="color:var(--sub)">🔁 ${TEKRAR[x.tekrar]||x.tekrar}</span>`:''}
          ${x.son_tarih?`<span style="color:${x.gecikti?'var(--kirmizi)':'var(--sub)'}">📅 ${disEsc(x.son_tarih)}${x.gecikti?' (gecikti)':''}</span>`:''}
          ${yapiliyor?'<span style="color:var(--amber)">● yapılıyor</span>':''}
          ${x.aciklama?`<span style="color:var(--sub)">· ${disEsc(x.aciklama)}</span>`:''}
        </div>
      </div>
      ${yapiliyor?'':`<button class="dis-btn gri" onclick="durumDegis(${x.id},'yapiliyor',false)"><span class="material-icons-outlined">play_arrow</span> Başla</button>`}
      <button class="dis-btn yesil" onclick="durumDegis(${x.id},'tamam',${olcumIster})"><span class="material-icons-outlined">check</span> Tamam</button>
      <button class="dis-btn gri" onclick="isSil(${x.id})"><span class="material-icons-outlined">delete</span></button>
    </div>`;
  }).join('');

  document.getElementById('son-g').textContent = 'Son güncelleme: ' + new Date().toLocaleTimeString('tr-TR');
}
yukle();
setInterval(yukle, 8000);
</script>
@endpush

@extends('layout.app')
@section('title', 'Karşılama / Host')
@section('rk_ikon', 'co_present')
@section('baslik', 'Karşılama / Host')

@php $ad = $ad ?? ''; @endphp

@push('head')
<style>
/* ===== KARŞILAMA / HOST ===================================== */
#krs {
  --mor1:#7C3AED; --mavi:#4F46E5; --yesil:#10B981; --kirmizi:#F43F5E; --amber:#F59E0B; --mavi2:#0EA5E9;
  --card:#fff; --card2:#f1f5f9; --ink:#1e293b; --sub:#64748b; --sub2:#94a3b8; --line:#e2e8f0; --bg:#f8fafc;
  max-width: 1100px;
}
body.koyu #krs { --card:#1b2235; --card2:#161C2E; --ink:#e2e8f0; --sub:#94a3b8; --sub2:#64748b; --line:#2c3650; --bg:#0B1020; }
#krs .krs-ozet { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
#krs .krs-rozet { display:flex; align-items:center; gap:10px; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:12px 16px; flex:1; min-width:140px; }
#krs .krs-rozet .c { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#krs .krs-rozet .c .material-icons-outlined { font-size:21px; }
#krs .krs-rozet .s { font-size:23px; font-weight:900; color:var(--ink); line-height:1; }
#krs .krs-rozet .e { font-size:11.5px; color:var(--sub); font-weight:600; margin-top:3px; }
#krs .krs-blok { margin-bottom:22px; }
#krs .krs-blok-bas { display:flex; align-items:center; gap:8px; margin-bottom:9px; }
#krs .krs-blok-bas b { font-size:15.5px; color:var(--ink); font-weight:800; }
#krs .krs-blok-bas .rozet { background:var(--card2); color:var(--sub); border-radius:20px; padding:1px 9px; font-size:12px; font-weight:800; }
#krs .krs-blok-bas .ekle { margin-left:auto; }
#krs .krs-liste { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
#krs .krs-satir { display:flex; align-items:center; gap:12px; padding:12px 15px; border-top:1px solid var(--line); }
#krs .krs-satir:first-child { border-top:none; }
#krs .krs-satir .c { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#krs .krs-satir .c .material-icons-outlined { font-size:20px; }
#krs .krs-satir .o { flex:1; min-width:0; }
#krs .krs-satir .o .b1 { font-size:14.5px; font-weight:800; color:var(--ink); }
#krs .krs-satir .o .b2 { font-size:12px; color:var(--sub); margin-top:2px; }
#krs .krs-btn { border:none; border-radius:9px; padding:8px 12px; font-size:12px; font-weight:800; cursor:pointer; color:#fff; display:inline-flex; align-items:center; gap:5px; white-space:nowrap; margin-left:6px; }
#krs .krs-btn .material-icons-outlined { font-size:15px; }
#krs .krs-btn.yesil { background:var(--yesil); } #krs .krs-btn.mavi { background:var(--mavi); }
#krs .krs-btn.amber { background:var(--amber); } #krs .krs-btn.gri { background:var(--sub); }
#krs .krs-bos { padding:20px 16px; text-align:center; color:var(--sub); font-size:13px; }
#krs .krs-rozet-durum { font-size:11px; font-weight:800; padding:2px 8px; border-radius:20px; }
/* ekle formu */
#krs .krs-form { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:10px; display:none; }
#krs .krs-form.acik { display:block; }
#krs .krs-form .row { display:flex; gap:10px; flex-wrap:wrap; }
#krs .krs-inp { background:var(--card2); border:1px solid var(--line); border-radius:10px; padding:9px 12px; font-size:13.5px; color:var(--ink); flex:1; min-width:110px; }
#krs .krs-inp::placeholder { color:var(--sub2); }
/* masa durumu */
#krs .krs-bolge-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
#krs .krs-bolge { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:12px 14px; }
#krs .krs-bolge .ba { font-size:13.5px; font-weight:800; color:var(--ink); margin-bottom:8px; }
#krs .krs-bolge .d { display:flex; gap:10px; }
#krs .krs-bolge .d span { font-size:12px; font-weight:700; }
/* oturt modal */
#krs-oturt { position:fixed; inset:0; background:rgba(15,15,25,.55); display:none; align-items:center; justify-content:center; z-index:9999; padding:16px; }
#krs-oturt.acik { display:flex; }
#krs-oturt .kutu { background:var(--card); border:1px solid var(--line); border-radius:16px; width:100%; max-width:380px; padding:18px; }
#krs-oturt h4 { margin:0 0 12px; color:var(--ink); font-size:16px; }
#krs-oturt select { width:100%; background:var(--card2); border:1px solid var(--line); border-radius:10px; padding:10px 12px; font-size:14px; color:var(--ink); margin-bottom:14px; }
#krs-oturt .f { display:flex; justify-content:flex-end; gap:10px; }
</style>
@endpush

@section('content')
<div id="krs">
  <div class="krs-ozet">
    <div class="krs-rozet"><div class="c" style="background:rgba(14,165,233,.14)"><span class="material-icons-outlined" style="color:var(--mavi2)">groups</span></div><div><div class="s" id="o-bek">0</div><div class="e">Bekleme sırası (grup)</div></div></div>
    <div class="krs-rozet"><div class="c" style="background:rgba(245,158,11,.14)"><span class="material-icons-outlined" style="color:var(--amber)">event_available</span></div><div><div class="s" id="o-rez">0</div><div class="e">Bekleyen rezervasyon</div></div></div>
    <div class="krs-rozet"><div class="c" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">table_restaurant</span></div><div><div class="s" id="o-bos">0</div><div class="e">Boş masa</div></div></div>
  </div>

  {{-- BEKLEME LİSTESİ --}}
  <div class="krs-blok">
    <div class="krs-blok-bas"><b>⏳ Bekleme Listesi</b><span class="rozet" id="r-bek">0</span>
      <button class="krs-btn mavi ekle" onclick="document.getElementById('bek-form').classList.toggle('acik')"><span class="material-icons-outlined">add</span> Bekleyen Ekle</button>
    </div>
    <div class="krs-form" id="bek-form">
      <div class="row" style="margin-bottom:10px">
        <input class="krs-inp" id="f-ad" placeholder="Ad Soyad *">
        <input class="krs-inp" id="f-tel" placeholder="Telefon">
        <input class="krs-inp" id="f-kisi" type="number" min="1" value="2" placeholder="Kişi" style="max-width:90px">
        <input class="krs-inp" id="f-dk" type="number" min="0" placeholder="Tahmini dk" style="max-width:120px">
      </div>
      <div class="row">
        <input class="krs-inp" id="f-not" placeholder="Not (ör. pencere kenarı)">
        <button class="krs-btn yesil" onclick="beklemeEkle()"><span class="material-icons-outlined">check</span> Ekle</button>
      </div>
    </div>
    <div class="krs-liste" id="l-bek"><div class="krs-bos">Bekleyen yok.</div></div>
  </div>

  {{-- REZERVASYONLAR --}}
  <div class="krs-blok">
    <div class="krs-blok-bas"><b>📅 Bugünün Rezervasyonları</b><span class="rozet" id="r-rez">0</span></div>
    <div class="krs-liste" id="l-rez"><div class="krs-bos">Bugün rezervasyon yok.</div></div>
  </div>

  {{-- MASA DURUMU --}}
  <div class="krs-blok">
    <div class="krs-blok-bas"><b>🪑 Masa Durumu</b></div>
    <div class="krs-bolge-grid" id="l-masa"><div class="krs-bos">—</div></div>
  </div>
</div>

{{-- OTURT MODAL --}}
<div id="krs-oturt">
  <div class="kutu">
    <h4 id="krs-oturt-bas">Masaya Oturt</h4>
    <select id="krs-oturt-masa"></select>
    <div class="f">
      <button class="krs-btn gri" onclick="oturtKapat()"><span class="material-icons-outlined">close</span> Vazgeç</button>
      <button class="krs-btn yesil" onclick="oturtOnayla()"><span class="material-icons-outlined">check</span> Oturt</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
let KRS_BOS = [];
let KRS_PENDING = null; // {tip:'bekleme'|'rez', id}
function krsEsc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
function krsSure(sn){ sn=+sn||0; if(sn<60) return sn+' sn'; var dk=Math.floor(sn/60); if(dk<60) return dk+' dk'; return Math.floor(dk/60)+' sa '+(dk%60)+' dk'; }

async function krsPost(url, body){
  try { const r = await fetch(url, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(body)}); return await r.json(); }
  catch(e){ return {ok:0}; }
}

async function beklemeEkle(){
  const ad = document.getElementById('f-ad').value.trim();
  if(!ad){ alert('İsim gerekli.'); return; }
  const r = await krsPost('/api/karsilama/bekleme-ekle', {
    ad, telefon: document.getElementById('f-tel').value.trim(),
    kisi: document.getElementById('f-kisi').value || 2,
    tahmini_dk: document.getElementById('f-dk').value, not: document.getElementById('f-not').value.trim(),
  });
  if(r.ok===1){ ['f-ad','f-tel','f-dk','f-not'].forEach(i=>document.getElementById(i).value=''); document.getElementById('f-kisi').value='2'; document.getElementById('bek-form').classList.remove('acik'); yukle(); }
  else alert(r.hata||'Eklenemedi');
}
async function beklemeDurum(id, durum){ await krsPost('/api/karsilama/bekleme-durum', {id, durum}); yukle(); }
async function rezDurum(id, durum){ await krsPost('/api/patron/rezervasyon-durum', {id, durum}); yukle(); }

// OTURT modal
function oturtAc(tip, id, masaId){
  KRS_PENDING = {tip, id};
  const sel = document.getElementById('krs-oturt-masa');
  if(!KRS_BOS.length){ alert('Boş masa yok.'); return; }
  sel.innerHTML = KRS_BOS.map(m=>`<option value="${m.id}" ${masaId==m.id?'selected':''}>${krsEsc(m.ad)} — ${krsEsc(m.bolge)}</option>`).join('');
  document.getElementById('krs-oturt').classList.add('acik');
}
function oturtKapat(){ document.getElementById('krs-oturt').classList.remove('acik'); KRS_PENDING=null; }
async function oturtOnayla(){
  if(!KRS_PENDING) return;
  const masaId = +document.getElementById('krs-oturt-masa').value;
  const url = KRS_PENDING.tip==='bekleme' ? '/api/karsilama/bekleme-oturt' : '/api/patron/rezervasyon-oturt';
  const r = await krsPost(url, {id: KRS_PENDING.id, masa_id: masaId});
  oturtKapat();
  if(r.ok!==1) alert(r.hata||'Oturtulamadı'); else yukle();
}

const REZ_DURUM = {bekliyor:['Bekliyor','var(--amber)'], onaylandi:['Onaylı','var(--mavi)'], geldi:['Geldi','var(--yesil)']};

async function yukle(){
  let d; try { const r = await fetch('/api/karsilama/panel', {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); } catch(e){ return; }
  if(!d || d.ok!==1) return;
  KRS_BOS = d.bos_masalar || [];
  const oz = d.ozet||{};
  document.getElementById('o-bek').textContent = oz.bekleme_grup||0;
  document.getElementById('o-rez').textContent = oz.rez_bekleyen||0;
  document.getElementById('o-bos').textContent = oz.bos_masa||0;
  document.getElementById('r-bek').textContent = (d.bekleme||[]).length;
  document.getElementById('r-rez').textContent = (d.rezervasyonlar||[]).length;

  // Bekleme
  const lb = document.getElementById('l-bek');
  if(!(d.bekleme||[]).length){ lb.innerHTML = '<div class="krs-bos">Bekleyen yok.</div>'; }
  else lb.innerHTML = d.bekleme.map(b=>{
    const cagrildi = b.durum==='cagrildi';
    return `<div class="krs-satir">
      <div class="c" style="background:rgba(14,165,233,.14)"><span class="material-icons-outlined" style="color:var(--mavi2)">person</span></div>
      <div class="o"><div class="b1">${krsEsc(b.ad)} · ${b.kisi} kişi ${cagrildi?'<span class="krs-rozet-durum" style="background:rgba(16,185,129,.15);color:var(--yesil)">Çağrıldı</span>':''}</div>
        <div class="b2">${krsSure(b.saniye)} bekliyor${b.tahmini_dk?` · tahmini ${b.tahmini_dk} dk`:''}${b.telefon?` · ${krsEsc(b.telefon)}`:''}${b.not?` · ${krsEsc(b.not)}`:''}</div></div>
      ${cagrildi?'':`<button class="krs-btn amber" onclick="beklemeDurum(${b.id},'cagrildi')"><span class="material-icons-outlined">campaign</span> Çağır</button>`}
      <button class="krs-btn yesil" onclick="oturtAc('bekleme',${b.id},null)"><span class="material-icons-outlined">chair_alt</span> Oturt</button>
      <button class="krs-btn gri" onclick="beklemeDurum(${b.id},'iptal')"><span class="material-icons-outlined">close</span></button>
    </div>`;
  }).join('');

  // Rezervasyonlar
  const lr = document.getElementById('l-rez');
  if(!(d.rezervasyonlar||[]).length){ lr.innerHTML = '<div class="krs-bos">Bugün rezervasyon yok.</div>'; }
  else lr.innerHTML = d.rezervasyonlar.map(x=>{
    const dz = REZ_DURUM[x.durum]||['',''];
    const geldi = x.durum==='geldi';
    return `<div class="krs-satir">
      <div class="c" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">event</span></div>
      <div class="o"><div class="b1">${krsEsc(x.saat||'')} · ${krsEsc(x.ad)} · ${x.kisi} kişi <span class="krs-rozet-durum" style="background:${dz[1]};color:#fff">${dz[0]}</span></div>
        <div class="b2">${x.masa?('🪑 '+krsEsc(x.masa)):'masa atanmadı'}${x.etiketler?` · ${krsEsc(x.etiketler)}`:''}${x.not?` · ${krsEsc(x.not)}`:''}</div></div>
      ${geldi?'':`<button class="krs-btn amber" onclick="rezDurum(${x.id},'geldi')"><span class="material-icons-outlined">how_to_reg</span> Geldi</button>`}
      ${geldi?'':`<button class="krs-btn yesil" onclick="oturtAc('rez',${x.id},${x.masa_id||'null'})"><span class="material-icons-outlined">chair_alt</span> Oturt</button>`}
    </div>`;
  }).join('');

  // Masa durumu
  const lm = document.getElementById('l-masa');
  const bolg = (d.masa_durum&&d.masa_durum.bolgeler)||[];
  if(!bolg.length){ lm.innerHTML = '<div class="krs-bos">Masa/bölge tanımlı değil.</div>'; }
  else lm.innerHTML = bolg.map(b=>`
    <div class="krs-bolge"><div class="ba">${krsEsc(b.bolge)}</div>
      <div class="d">
        <span style="color:var(--yesil)">● ${b.bos} boş</span>
        <span style="color:var(--kirmizi)">● ${b.dolu} dolu</span>
        ${b.rezerve?`<span style="color:var(--amber)">● ${b.rezerve} rez.</span>`:''}
      </div></div>`).join('');
}

yukle();
setInterval(yukle, 7000);
</script>
@endpush

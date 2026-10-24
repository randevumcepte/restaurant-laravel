@extends('layout.app')
@section('title', 'Servis Ekranı')
@section('rk_ikon', 'room_service')
@section('baslik', 'Servis Ekranı')

@php
    $ad = $ad ?? '';
    $sorumlu = $sorumlu ?? false;
@endphp

@push('head')
<style>
/* ===== SERVİS / GARSON İŞ EKRANI ===================================== */
#srv {
  --mor1:#7C3AED; --mavi:#4F46E5; --yesil:#10B981; --kirmizi:#F43F5E; --amber:#F59E0B; --mavi2:#0EA5E9;
  --card:#fff; --card2:#f1f5f9; --ink:#1e293b; --sub:#64748b; --sub2:#94a3b8;
  --line:#e2e8f0; --bg:#f8fafc;
  max-width: 1100px;
}
body.koyu #srv {
  --card:#1b2235; --card2:#161C2E; --ink:#e2e8f0; --sub:#94a3b8; --sub2:#64748b;
  --line:#2c3650; --bg:#0B1020;
}
#srv .srv-sekme-bar { display:flex; gap:8px; margin-bottom:16px; }
#srv .srv-sekme {
  border:1px solid var(--line); background:var(--card); color:var(--sub);
  border-radius:12px; padding:9px 16px; font-size:13.5px; font-weight:800; cursor:pointer;
  display:flex; align-items:center; gap:7px;
}
#srv .srv-sekme.aktif { background:var(--mavi); border-color:var(--mavi); color:#fff; }
#srv .srv-sekme .material-icons-outlined { font-size:18px; }

/* özet rozet şeridi */
#srv .srv-ozet { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
#srv .srv-rozet {
  display:flex; align-items:center; gap:10px; background:var(--card); border:1px solid var(--line);
  border-radius:14px; padding:12px 16px; flex:1; min-width:130px;
}
#srv .srv-rozet .sr-circ { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#srv .srv-rozet .sr-circ .material-icons-outlined { font-size:21px; }
#srv .srv-rozet .sr-sayi { font-size:23px; font-weight:900; color:var(--ink); line-height:1; }
#srv .srv-rozet .sr-etiket { font-size:11.5px; color:var(--sub); font-weight:600; margin-top:3px; }

/* bölüm başlığı */
#srv .srv-blok { margin-bottom:20px; }
#srv .srv-blok-bas { display:flex; align-items:center; gap:8px; margin-bottom:9px; }
#srv .srv-blok-bas b { font-size:15px; color:var(--ink); font-weight:800; }
#srv .srv-blok-bas .rozet { background:var(--card2); color:var(--sub); border-radius:20px; padding:1px 9px; font-size:12px; font-weight:800; }

/* liste kartı */
#srv .srv-liste { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
#srv .srv-satir { display:flex; align-items:center; gap:12px; padding:13px 15px; border-top:1px solid var(--line); }
#srv .srv-satir:first-child { border-top:none; }
#srv .srv-satir .sol-circ { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#srv .srv-satir .sol-circ .material-icons-outlined { font-size:20px; }
#srv .srv-satir .orta { flex:1; min-width:0; }
#srv .srv-satir .orta .b1 { font-size:14.5px; font-weight:800; color:var(--ink); }
#srv .srv-satir .orta .b2 { font-size:12px; color:var(--sub); margin-top:2px; }
#srv .srv-btn {
  border:none; border-radius:10px; padding:9px 14px; font-size:12.5px; font-weight:800; cursor:pointer;
  color:#fff; display:flex; align-items:center; gap:6px; white-space:nowrap;
}
#srv .srv-btn .material-icons-outlined { font-size:16px; }
#srv .srv-btn.yesil { background:var(--yesil); }
#srv .srv-btn.mavi { background:var(--mavi); }
#srv .srv-btn[disabled] { opacity:.55; cursor:default; }
#srv .srv-bos { padding:22px 16px; text-align:center; color:var(--sub); font-size:13px; }

/* ekip tablosu */
#srv .srv-tablo { width:100%; border-collapse:collapse; background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
#srv .srv-tablo th, #srv .srv-tablo td { padding:11px 13px; text-align:left; font-size:13px; border-top:1px solid var(--line); }
#srv .srv-tablo th { background:var(--card2); color:var(--sub); font-weight:800; font-size:11.5px; text-transform:uppercase; letter-spacing:.3px; border-top:none; }
#srv .srv-tablo td { color:var(--ink); }
#srv .srv-tablo td.num { font-weight:800; text-align:right; }
#srv .srv-sayac { font-size:12px; color:var(--sub2); }
</style>
@endpush

@section('content')
<div id="srv">

  @if ($sorumlu)
  <div class="srv-sekme-bar">
    <div class="srv-sekme aktif" data-sek="akis" onclick="srvSekme('akis')"><span class="material-icons-outlined">checklist</span> İş Akışım</div>
    <div class="srv-sekme" data-sek="ekip" onclick="srvSekme('ekip')"><span class="material-icons-outlined">groups</span> Ekip</div>
  </div>
  @endif

  {{-- ============ İŞ AKIŞIM ============ --}}
  <div id="sek-akis">
    <div class="srv-ozet">
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:var(--kirmizi)">notifications_active</span></div><div><div class="sr-sayi" id="o-cagri">0</div><div class="sr-etiket">Bekleyen çağrı</div></div></div>
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">room_service</span></div><div><div class="sr-sayi" id="o-hazir">0</div><div class="sr-etiket">Servise hazır</div></div></div>
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">table_restaurant</span></div><div><div class="sr-sayi" id="o-acik">0</div><div class="sr-etiket">Açık masam</div></div></div>
    </div>

    <div class="srv-blok">
      <div class="srv-blok-bas"><b>🔔 Çağrılar</b><span class="rozet" id="r-cagri">0</span></div>
      <div class="srv-liste" id="l-cagri"><div class="srv-bos">Bekleyen çağrı yok.</div></div>
    </div>

    <div class="srv-blok">
      <div class="srv-blok-bas"><b>🍽️ Servise Hazır (mutfaktan çıktı)</b><span class="rozet" id="r-hazir">0</span></div>
      <div class="srv-liste" id="l-hazir"><div class="srv-bos">Servise hazır bekleyen ürün yok.</div></div>
    </div>

    <div class="srv-blok">
      <div class="srv-blok-bas"><b>🪑 Açık Masalarım</b><span class="rozet" id="r-acik">0</span></div>
      <div class="srv-liste" id="l-acik"><div class="srv-bos">Açık masan yok.</div></div>
    </div>
    <div class="srv-sayac" id="son-g">—</div>
  </div>

  {{-- ============ EKİP (sorumlu) ============ --}}
  @if ($sorumlu)
  <div id="sek-ekip" style="display:none">
    <div class="srv-ozet">
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">table_restaurant</span></div><div><div class="sr-sayi" id="e-acik">0</div><div class="sr-etiket">Açık masa</div></div></div>
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:var(--kirmizi)">notifications_active</span></div><div><div class="sr-sayi" id="e-cagri">0</div><div class="sr-etiket">Bekleyen çağrı</div></div></div>
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">room_service</span></div><div><div class="sr-sayi" id="e-hazir">0</div><div class="sr-etiket">Servise hazır bekleyen</div></div></div>
      <div class="srv-rozet"><div class="sr-circ" style="background:rgba(124,58,237,.14)"><span class="material-icons-outlined" style="color:var(--mor1)">badge</span></div><div><div class="sr-sayi" id="e-aktif">0</div><div class="sr-etiket">Aktif garson</div></div></div>
    </div>
    <div class="srv-blok">
      <div class="srv-blok-bas"><b>👥 Garson Performansı (bugün)</b></div>
      <table class="srv-tablo">
        <thead><tr><th>Garson</th><th style="text-align:right">Adisyon</th><th style="text-align:right">Ciro</th><th style="text-align:right">Kalem</th><th style="text-align:right">Ort. Servis</th></tr></thead>
        <tbody id="l-ekip"><tr><td colspan="5" class="srv-bos">Veri bekleniyor…</td></tr></tbody>
      </table>
    </div>
  </div>
  @endif

</div>
@endsection

@push('scripts')
<script>
const SRV_SORUMLU = @json($sorumlu);
function srvTl(n){ return (Math.round(+n||0)).toLocaleString('tr-TR') + '₺'; }
function srvSure(sn){ sn=+sn||0; if(sn<60) return sn+' sn'; var dk=Math.floor(sn/60); if(dk<60) return dk+' dk'; var sa=Math.floor(dk/60); return sa+' sa '+(dk%60)+' dk'; }
function srvEsc(s){ return (s==null?'':String(s)).replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

function srvSekme(s){
  document.querySelectorAll('#srv .srv-sekme').forEach(e=>e.classList.toggle('aktif', e.dataset.sek===s));
  document.getElementById('sek-akis').style.display = s==='akis' ? '' : 'none';
  var ek=document.getElementById('sek-ekip'); if(ek) ek.style.display = s==='ekip' ? '' : 'none';
  if(s==='ekip') ekipYukle();
}

async function srvPost(url, body){
  try {
    const r = await fetch(url, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify(body)});
    return await r.json();
  } catch(e){ return {ok:0}; }
}
async function cagriKapat(id){ await srvPost('/api/patron/garson-cagri-kapat', {id}); akisYukle(); }
async function teslimEt(id, btn){ if(btn) btn.disabled=true; await srvPost('/api/servis/teslim', {kalem_id:id}); akisYukle(); }

const CAGRI_IKON = {garson:'pan_tool', servise_hazir:'room_service', tasima:'swap_horiz', odeme:'payments', hesap:'receipt_long', siparis:'restaurant_menu'};

async function akisYukle(){
  let d;
  try { const r = await fetch('/api/servis/akis', {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); }
  catch(e){ return; }
  if(!d || d.ok!==1) return;
  const oz = d.ozet||{};
  document.getElementById('o-cagri').textContent = oz.cagri||0;
  document.getElementById('o-hazir').textContent = oz.hazir||0;
  document.getElementById('o-acik').textContent  = oz.acik||0;
  document.getElementById('r-cagri').textContent = (d.cagrilar||[]).length;
  document.getElementById('r-hazir').textContent = (d.hazir||[]).length;
  document.getElementById('r-acik').textContent  = (d.acik_masalar||[]).length;

  // Çağrılar
  const lc = document.getElementById('l-cagri');
  if(!(d.cagrilar||[]).length){ lc.innerHTML = '<div class="srv-bos">Bekleyen çağrı yok.</div>'; }
  else lc.innerHTML = d.cagrilar.map(c=>`
    <div class="srv-satir">
      <div class="sol-circ" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:var(--kirmizi)">${CAGRI_IKON[c.tip]||'notifications'}</span></div>
      <div class="orta"><div class="b1">${srvEsc(c.masa)}</div><div class="b2">${srvEsc(c.tip_ad)} · ${srvSure(c.saniye)} önce${c.tutar?(' · '+srvTl(c.tutar)):''}</div></div>
      <button class="srv-btn yesil" onclick="cagriKapat(${c.id})"><span class="material-icons-outlined">check</span> Karşılandı</button>
    </div>`).join('');

  // Servise hazır
  const lh = document.getElementById('l-hazir');
  if(!(d.hazir||[]).length){ lh.innerHTML = '<div class="srv-bos">Servise hazır bekleyen ürün yok.</div>'; }
  else lh.innerHTML = d.hazir.map(h=>`
    <div class="srv-satir">
      <div class="sol-circ" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">restaurant</span></div>
      <div class="orta"><div class="b1">${(+h.adet||1)}× ${srvEsc(h.urun)}</div><div class="b2">${srvEsc(h.masa)} · ${srvSure(h.saniye)} bekliyor</div></div>
      <button class="srv-btn mavi" onclick="teslimEt(${h.id}, this)"><span class="material-icons-outlined">done_all</span> Teslim Ettim</button>
    </div>`).join('');

  // Açık masalar
  const la = document.getElementById('l-acik');
  if(!(d.acik_masalar||[]).length){ la.innerHTML = '<div class="srv-bos">Açık masan yok.</div>'; }
  else la.innerHTML = d.acik_masalar.map(a=>`
    <div class="srv-satir">
      <div class="sol-circ" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">table_restaurant</span></div>
      <div class="orta"><div class="b1">${srvEsc(a.masa)}</div><div class="b2">${srvTl(a.toplam)} · ${srvSure(a.saniye)} açık</div></div>
      <a class="srv-btn mavi" href="/pos"><span class="material-icons-outlined">open_in_new</span> Masaya Git</a>
    </div>`).join('');

  const g = new Date();
  document.getElementById('son-g').textContent = 'Son güncelleme: ' + g.toLocaleTimeString('tr-TR');
}

async function ekipYukle(){
  if(!SRV_SORUMLU) return;
  let d;
  try { const r = await fetch('/api/servis/ekip', {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); }
  catch(e){ return; }
  if(!d || d.ok!==1) return;
  const oz=d.ozet||{};
  document.getElementById('e-acik').textContent  = oz.acik_masa||0;
  document.getElementById('e-cagri').textContent = oz.bekleyen_cagri||0;
  document.getElementById('e-hazir').textContent = oz.servise_hazir_bekleyen||0;
  document.getElementById('e-aktif').textContent = oz.aktif_garson||0;
  const tb=document.getElementById('l-ekip');
  const gs=d.garsonlar||[];
  if(!gs.length){ tb.innerHTML = '<tr><td colspan="5" class="srv-bos">Henüz garson verisi yok.</td></tr>'; return; }
  tb.innerHTML = gs.map(g=>`
    <tr>
      <td>${srvEsc(g.ad)}</td>
      <td class="num">${g.adisyon||0}</td>
      <td class="num">${srvTl(g.ciro)}</td>
      <td class="num">${g.kalem||0}</td>
      <td class="num">${g.ort_servis_dk?((g.ort_servis_dk)+' dk'):'—'}</td>
    </tr>`).join('');
}

akisYukle();
setInterval(()=>{ akisYukle(); const ek=document.getElementById('sek-ekip'); if(SRV_SORUMLU && ek && ek.style.display!=='none') ekipYukle(); }, 5000);
</script>
@endpush

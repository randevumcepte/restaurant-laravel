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
  --ana:{{ $renk }};
}
body.koyu #dis { --card:#1b2235; --card2:#161C2E; --ink:#e2e8f0; --sub:#a4b1c7; --sub2:#8694ad; --line:#2c3650; --bg:#0B1020; }

/* --- Üst sekmeler --- */
#dis .dis-sekmeler { display:flex; gap:6px; margin-bottom:14px; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:5px; }
#dis .dis-sek { flex:1; display:inline-flex; align-items:center; justify-content:center; gap:7px; padding:10px 14px; border-radius:10px; border:none; background:transparent; color:var(--sub); font-size:13.5px; font-weight:700; cursor:pointer; transition:all .15s; }
#dis .dis-sek:hover { background:color-mix(in srgb, var(--ana) 8%, transparent); color:var(--ana); }
#dis .dis-sek.aktif { background:var(--ana); color:#fff; box-shadow:0 4px 12px color-mix(in srgb, var(--ana) 30%, transparent); }
#dis .dis-sek .material-icons-outlined { font-size:17px; }
#dis .dis-sek .sayac { display:inline-flex; align-items:center; justify-content:center; min-width:20px; height:18px; padding:0 6px; border-radius:999px; background:color-mix(in srgb, currentColor 15%, transparent); font-size:11px; font-weight:800; }
#dis .dis-sek.aktif .sayac { background:rgba(255,255,255,.28); }

/* --- Özet rozetleri --- */
#dis .dis-ozet { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
#dis .dis-rozet { display:flex; align-items:center; gap:10px; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:12px 16px; flex:1; min-width:135px; }
#dis .dis-rozet .c { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#dis .dis-rozet .c .material-icons-outlined { font-size:21px; }
#dis .dis-rozet .s { font-size:23px; font-weight:900; color:var(--ink); line-height:1; font-variant-numeric:tabular-nums; }
#dis .dis-rozet .e { font-size:11.5px; color:var(--sub); font-weight:600; margin-top:3px; }

/* --- Preset chip + yeni iş butonu --- */
#dis .dis-presetler { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
#dis .dis-preset { border:1px dashed var(--ana); background:color-mix(in srgb, var(--ana) 5%, transparent); color:var(--ana); border-radius:20px; padding:7px 13px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:all .15s; }
#dis .dis-preset:hover { background:var(--ana); color:#fff; border-style:solid; }
#dis .dis-preset .material-icons-outlined { font-size:15px; }
#dis .dis-ekle-btn { background:var(--ana); color:#fff; border:none; border-radius:11px; padding:10px 16px; font-size:13.5px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:7px; margin-bottom:14px; transition:transform .12s, box-shadow .15s; }
#dis .dis-ekle-btn:hover { transform:translateY(-1px); box-shadow:0 6px 16px color-mix(in srgb, var(--ana) 30%, transparent); }
#dis .dis-form { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; margin-bottom:14px; display:none; }
#dis .dis-form.acik { display:block; }
#dis .dis-form .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
#dis .dis-inp, #dis .dis-sel { background:var(--card2); border:1px solid var(--line); border-radius:10px; padding:9px 12px; font-size:13.5px; color:var(--ink); flex:1; min-width:120px; }

/* --- Açık işler satırı --- */
#dis .dis-liste { background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden; }
#dis .dis-satir { display:flex; align-items:center; gap:12px; padding:12px 15px; border-top:1px solid var(--line); }
#dis .dis-satir:first-child { border-top:none; }
#dis .dis-satir .c { width:38px; height:38px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#dis .dis-satir .c .material-icons-outlined { font-size:20px; }
#dis .dis-satir .o { flex:1; min-width:0; }
#dis .dis-satir .o .b1 { font-size:14.5px; font-weight:800; color:var(--ink); }
#dis .dis-satir .o .b2 { font-size:12px; color:var(--sub); margin-top:3px; display:flex; gap:7px; flex-wrap:wrap; align-items:center; }
#dis .dis-cip { font-size:10.5px; font-weight:800; padding:2px 8px; border-radius:20px; }
#dis .dis-btn { border:none; border-radius:9px; padding:8px 11px; font-size:12px; font-weight:800; cursor:pointer; color:#fff; display:inline-flex; align-items:center; gap:4px; margin-left:5px; transition:opacity .12s; }
#dis .dis-btn:hover { opacity:.88; }
#dis .dis-btn .material-icons-outlined { font-size:15px; }
#dis .dis-btn.yesil { background:var(--yesil); } #dis .dis-btn.mavi { background:var(--mavi); } #dis .dis-btn.gri { background:var(--gri); }
#dis .dis-bos { padding:40px 16px; text-align:center; color:var(--sub); font-size:13px; }
#dis .dis-bos .material-icons-outlined { font-size:38px; color:var(--sub2); display:block; margin-bottom:8px; }
#dis .dis-sayac { font-size:12px; color:var(--sub2); margin-top:10px; }

/* --- Geçmiş sekmesi --- */
#dis .dis-filtre { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
#dis .dis-filt-chip { padding:6px 12px; border-radius:999px; border:1px solid var(--line); background:var(--card); color:var(--sub); font-size:12.5px; font-weight:700; cursor:pointer; transition:all .15s; }
#dis .dis-filt-chip:hover { border-color:var(--ana); color:var(--ana); }
#dis .dis-filt-chip.aktif { background:var(--ana); color:#fff; border-color:var(--ana); }
#dis .dis-filt-etiket { color:var(--sub); font-size:12px; font-weight:600; }

#dis .dis-g-liste { display:grid; gap:10px; }
#dis .dis-g-kart { background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px; display:grid; grid-template-columns:auto 1fr auto; gap:12px; align-items:start; transition:border-color .15s, box-shadow .15s; }
#dis .dis-g-kart:hover { border-color:color-mix(in srgb, var(--ana) 35%, var(--line)); box-shadow:0 4px 14px rgba(0,0,0,.06); }
#dis .dis-g-kart.iptal { background:color-mix(in srgb, var(--kirmizi) 3%, var(--card)); border-color:color-mix(in srgb, var(--kirmizi) 25%, var(--line)); }
#dis .dis-g-ikon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
#dis .dis-g-ikon .material-icons-outlined { font-size:22px; }
#dis .dis-g-icerik { min-width:0; }
#dis .dis-g-bas { font-size:14.5px; font-weight:800; color:var(--ink); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
#dis .dis-g-deger { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; background:color-mix(in srgb, var(--yesil) 14%, transparent); color:var(--yesil); font-size:12.5px; font-weight:800; }
#dis .dis-g-deger.iptal { background:color-mix(in srgb, var(--kirmizi) 14%, transparent); color:var(--kirmizi); }
#dis .dis-g-meta { color:var(--sub); font-size:12px; margin-top:6px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
#dis .dis-g-meta .m { display:inline-flex; align-items:center; gap:4px; }
#dis .dis-g-meta .m .material-icons-outlined { font-size:14px; color:var(--sub2); }
#dis .dis-g-zaman { display:grid; gap:4px; margin-top:8px; padding-top:8px; border-top:1px dashed var(--line); font-size:11.5px; color:var(--sub); }
#dis .dis-g-zaman .z { display:flex; align-items:center; gap:6px; }
#dis .dis-g-zaman .z .material-icons-outlined { font-size:14px; }
#dis .dis-g-zaman .z.ac .material-icons-outlined { color:var(--mavi); }
#dis .dis-g-zaman .z.ok .material-icons-outlined { color:var(--yesil); }
#dis .dis-g-zaman .z.ip .material-icons-outlined { color:var(--kirmizi); }
#dis .dis-g-zaman .z b { color:var(--ink); font-weight:700; font-variant-numeric:tabular-nums; }
#dis .dis-g-sure { text-align:right; flex-shrink:0; }
#dis .dis-g-sure .v { font-size:16px; font-weight:800; color:var(--ink); font-variant-numeric:tabular-nums; }
#dis .dis-g-sure .e { font-size:10.5px; color:var(--sub2); text-transform:uppercase; letter-spacing:.3px; margin-top:1px; }
#dis .dis-g-durum { display:inline-flex; align-items:center; gap:3px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:800; }
#dis .dis-g-durum.tamam { background:color-mix(in srgb, var(--yesil) 14%, transparent); color:var(--yesil); }
#dis .dis-g-durum.iptal { background:color-mix(in srgb, var(--kirmizi) 14%, transparent); color:var(--kirmizi); }
#dis .dis-g-durum .material-icons-outlined { font-size:13px; }

@media (max-width:640px) {
  #dis .dis-g-kart { grid-template-columns:auto 1fr; }
  #dis .dis-g-sure { grid-column:1 / -1; text-align:left; display:flex; gap:10px; align-items:center; padding-top:6px; border-top:1px dashed var(--line); margin-top:4px; }
}
</style>
@endpush

@section('content')
<div id="dis">

  {{-- Üst sekmeler --}}
  <div class="dis-sekmeler">
    <button class="dis-sek aktif" id="sek-acik" onclick="disSekSec('acik')">
      <span class="material-icons-outlined">pending_actions</span> Açık İşler
      <span class="sayac" id="sek-acik-n">0</span>
    </button>
    <button class="dis-sek" id="sek-gecmis" onclick="disSekSec('gecmis')">
      <span class="material-icons-outlined">history</span> Geçmiş
    </button>
  </div>

  {{-- SEKME: AÇIK İŞLER --}}
  <div id="panel-acik">
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

    <div class="dis-liste" id="liste"><div class="dis-bos"><span class="material-icons-outlined">inbox</span>Açık iş yok.</div></div>
    <div class="dis-sayac" id="son-g">—</div>
  </div>

  {{-- SEKME: GEÇMİŞ --}}
  <div id="panel-gecmis" style="display:none">
    <div class="dis-ozet">
      <div class="dis-rozet"><div class="c" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:var(--yesil)">check_circle</span></div><div><div class="s" id="g-tamam">0</div><div class="e">Tamamlanan</div></div></div>
      <div class="dis-rozet"><div class="c" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:var(--kirmizi)">cancel</span></div><div><div class="s" id="g-iptal">0</div><div class="e">İptal</div></div></div>
      <div class="dis-rozet"><div class="c" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:var(--mavi)">insights</span></div><div><div class="s" id="g-toplam">0</div><div class="e">Toplam kayıt</div></div></div>
    </div>

    <div class="dis-filtre">
      <span class="dis-filt-etiket">Dönem:</span>
      <button class="dis-filt-chip" data-gun="1" onclick="gFiltG(this,1)">Bugün</button>
      <button class="dis-filt-chip" data-gun="7" onclick="gFiltG(this,7)">7 gün</button>
      <button class="dis-filt-chip aktif" data-gun="30" onclick="gFiltG(this,30)">30 gün</button>
      <button class="dis-filt-chip" data-gun="90" onclick="gFiltG(this,90)">90 gün</button>
      <button class="dis-filt-chip" data-gun="365" onclick="gFiltG(this,365)">1 yıl</button>
      <button class="dis-filt-chip" data-gun="9999" onclick="gFiltG(this,9999)">Tümü</button>
      <span class="dis-filt-etiket" style="margin:0 4px 0 10px">·</span>
      <button class="dis-filt-chip aktif" data-dur="tumu" onclick="gFiltD(this,'tumu')">Hepsi</button>
      <button class="dis-filt-chip" data-dur="tamam" onclick="gFiltD(this,'tamam')">Tamam</button>
      <button class="dis-filt-chip" data-dur="iptal" onclick="gFiltD(this,'iptal')">İptal</button>
    </div>

    <div class="dis-g-liste" id="g-liste">
      <div class="dis-bos"><span class="material-icons-outlined">history</span>Henüz tamamlanmış veya iptal edilmiş kayıt yok.</div>
    </div>
  </div>

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
  const r = await disPost('/api/departman-is/ekle', {kod:DIS_KOD, tip:pr.tip, baslik:pr.baslik, oncelik:pr.oncelik||'orta', tekrar:pr.tekrar||''});
  if(r.ok!==1) alert(r.hata||'Eklenemedi');
  yukle();
}
async function durumDegis(id, durum, olcumIster){
  let deger = null;
  if(durum==='tamam' && olcumIster){ deger = prompt('Ölçüm / değer girin (ör. 4 °C):', ''); if(deger===null) return; }
  await disPost('/api/departman-is/durum', {id, durum, deger: deger||''});
  yukle();
  if (document.getElementById('panel-gecmis').style.display !== 'none') gYukle();
}
async function isSil(id){
  if(!confirm('Bu kayıt silinsin mi?')) return;
  await disPost('/api/departman-is/sil', {id}); yukle();
  if (document.getElementById('panel-gecmis').style.display !== 'none') gYukle();
}

async function yukle(){
  let d; try { const r = await fetch('/api/departman-is/panel?kod='+encodeURIComponent(DIS_KOD), {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); } catch(e){ return; }
  if(!d || d.ok!==1) return;
  const oz = d.ozet||{};
  document.getElementById('o-acik').textContent = oz.acik||0;
  document.getElementById('o-tamam').textContent = oz.bugun_tamam||0;
  document.getElementById('o-gecik').textContent = oz.geciken||0;
  document.getElementById('o-yuksek').textContent = oz.yuksek||0;
  document.getElementById('sek-acik-n').textContent = oz.acik||0;

  document.getElementById('presetler').innerHTML = (d.presetler||[]).map((pr,i)=>
    `<button class="dis-preset" onclick='presetEkle(${JSON.stringify(pr).replace(/'/g,"&#39;")})'><span class="material-icons-outlined">add</span> ${disEsc(pr.baslik)}</button>`).join('');

  const l = document.getElementById('liste');
  if(!(d.isler||[]).length){ l.innerHTML = '<div class="dis-bos"><span class="material-icons-outlined">inbox</span>Açık iş yok. Yukarıdan ekleyebilir ya da hazır şablonlardan seçebilirsin.</div>'; }
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
      <button class="dis-btn gri" onclick="durumDegis(${x.id},'iptal',false)" title="İptal et"><span class="material-icons-outlined">close</span></button>
      <button class="dis-btn gri" onclick="isSil(${x.id})" title="Sil"><span class="material-icons-outlined">delete</span></button>
    </div>`;
  }).join('');

  document.getElementById('son-g').textContent = 'Son güncelleme: ' + new Date().toLocaleTimeString('tr-TR');
}

// ─── GEÇMİŞ ──────────────────────────────────────────────────────────────────
let G_GUN = 30, G_DUR = 'tumu';
function gFiltG(btn,g){ document.querySelectorAll('.dis-filt-chip[data-gun]').forEach(b=>b.classList.remove('aktif')); btn.classList.add('aktif'); G_GUN=g; gYukle(); }
function gFiltD(btn,d){ document.querySelectorAll('.dis-filt-chip[data-dur]').forEach(b=>b.classList.remove('aktif')); btn.classList.add('aktif'); G_DUR=d; gYukle(); }

async function gYukle(){
  const l = document.getElementById('g-liste');
  l.innerHTML = '<div class="dis-bos"><span class="material-icons-outlined">hourglass_empty</span>Yükleniyor…</div>';
  let d; try { const r = await fetch('/api/departman-is/gecmis?kod='+encodeURIComponent(DIS_KOD)+'&gun='+G_GUN+'&durum='+G_DUR, {credentials:'same-origin', headers:{'Accept':'application/json'}}); d = await r.json(); } catch(e){ l.innerHTML = '<div class="dis-bos">Bağlantı hatası.</div>'; return; }
  if(!d || d.ok!==1){ l.innerHTML = '<div class="dis-bos">'+(d && d.hata ? disEsc(d.hata) : 'Veri alınamadı.')+'</div>'; return; }
  const oz = d.ozet||{};
  document.getElementById('g-tamam').textContent = oz.tamam||0;
  document.getElementById('g-iptal').textContent = oz.iptal||0;
  document.getElementById('g-toplam').textContent = oz.toplam||0;

  const k = d.kayitlar||[];
  if(!k.length){ l.innerHTML = '<div class="dis-bos"><span class="material-icons-outlined">history</span>Bu filtrelerde kayıt bulunamadı.</div>'; return; }
  l.innerHTML = k.map(x=>{
    const tp = TIP[x.tip]||TIP.gorev;
    const iptal = x.durum==='iptal';
    const on = ONC[x.oncelik]||ONC.orta;
    const degerCip = x.deger ? `<span class="dis-g-deger ${iptal?'iptal':''}">${disEsc(x.deger)}</span>` : '';
    return `<div class="dis-g-kart ${iptal?'iptal':''}">
      <div class="dis-g-ikon" style="background:color-mix(in srgb, ${tp[2]} 15%, transparent)">
        <span class="material-icons-outlined" style="color:${tp[2]}">${tp[1]}</span>
      </div>
      <div class="dis-g-icerik">
        <div class="dis-g-bas">
          ${disEsc(x.baslik)}
          ${degerCip}
          <span class="dis-g-durum ${iptal?'iptal':'tamam'}">
            <span class="material-icons-outlined">${iptal?'cancel':'check_circle'}</span>${iptal?'İptal':'Tamam'}
          </span>
        </div>
        <div class="dis-g-meta">
          <span class="m"><span class="dis-cip" style="background:color-mix(in srgb, ${tp[2]} 14%, transparent);color:${tp[2]}">${tp[0]}</span></span>
          <span class="m"><span class="dis-cip" style="background:color-mix(in srgb, ${on[1]} 14%, transparent);color:${on[1]}">${on[0]}</span></span>
          ${x.tekrar?`<span class="m"><span class="material-icons-outlined">autorenew</span>${TEKRAR[x.tekrar]||x.tekrar}</span>`:''}
          ${x.olusturan_ad?`<span class="m"><span class="material-icons-outlined">person</span>${disEsc(x.olusturan_ad)}</span>`:''}
          ${x.aciklama?`<span class="m" style="flex:1 1 100%"><span class="material-icons-outlined">sticky_note_2</span>${disEsc(x.aciklama)}</span>`:''}
        </div>
        <div class="dis-g-zaman">
          ${x.acilma?`<div class="z ac"><span class="material-icons-outlined">play_circle</span>Açılış: <b>${disEsc(x.acilma)}</b></div>`:''}
          ${x.tamam?`<div class="z ${iptal?'ip':'ok'}"><span class="material-icons-outlined">${iptal?'cancel':'check_circle'}</span>${iptal?'İptal':'Tamamlandı'}: <b>${disEsc(x.tamam)}</b></div>`:''}
        </div>
      </div>
      <div class="dis-g-sure">
        ${x.sure?`<div class="v">${disEsc(x.sure)}</div><div class="e">Süre</div>`:'<div class="v">—</div><div class="e">—</div>'}
      </div>
    </div>`;
  }).join('');
}

// ─── SEKME ───────────────────────────────────────────────────────────────────
function disSekSec(sek){
  document.getElementById('sek-acik').classList.toggle('aktif', sek==='acik');
  document.getElementById('sek-gecmis').classList.toggle('aktif', sek==='gecmis');
  document.getElementById('panel-acik').style.display   = sek==='acik'   ? '' : 'none';
  document.getElementById('panel-gecmis').style.display = sek==='gecmis' ? '' : 'none';
  if (sek==='gecmis') gYukle();
}

yukle();
setInterval(yukle, 8000);
</script>
@endpush

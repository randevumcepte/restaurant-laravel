@extends('layout.app')
@section('title', 'AI Santral — Eğitim')
@section('baslik', 'AI Santral — Eğitim')
@section('rk_ikon', 'school')
@section('rk_alt', 'Hazır cevaplar (SSS), öğrendikleri, cevaplanamayanlar ve PDF\'ten kalıp çıkarma')
@push('head')
<style>
  .st-kok{--indigo:#4f46e5;--indigo2:#6366f1;--red:#ef4444;--acik:color-mix(in srgb,#6366f1 12%,transparent);color:var(--ink);max-width:820px;margin:0 auto}
  .st-kok *{box-sizing:border-box}
  .st-kok .tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
  .st-kok .tabs button{border:1px solid var(--line);background:var(--card);border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;color:var(--sub);font-size:14px}
  .st-kok .tabs button.sec{background:var(--indigo);color:#fff;border-color:var(--indigo)}
  .st-kok .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:20px;margin-bottom:16px}
  .st-kok .card h2{font-size:14px;color:var(--sub);text-transform:uppercase;letter-spacing:.04em;margin:0 0 12px}
  .st-kok label{display:block;font-size:12px;font-weight:600;margin:10px 0 5px;color:var(--sub)}
  .st-kok input[type=text],.st-kok textarea{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;font-size:14px;outline:none;background:var(--card2);color:var(--ink);font-family:inherit}
  .st-kok textarea{min-height:60px;resize:vertical}
  .st-kok input:focus,.st-kok textarea:focus{border-color:var(--indigo);background:var(--card)}
  .st-kok .btn{border:none;border-radius:10px;padding:10px 15px;font-weight:700;cursor:pointer;font-size:14px}
  .st-kok .btn.pri{color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2))}
  .st-kok .btn.sec{background:var(--acik);color:var(--ink);border:1px solid var(--line)}
  .st-kok .btn.dgr{background:color-mix(in srgb,#ef4444 15%,transparent);color:var(--red)}
  .st-kok .btn.sm{padding:6px 10px;font-size:12px}
  .st-kok table{width:100%;border-collapse:collapse}
  .st-kok th,.st-kok td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);font-size:13px;vertical-align:top}
  .st-kok th{color:var(--sub);font-size:11px;text-transform:uppercase}
  .st-kok .aksi{display:flex;gap:6px;justify-content:flex-end}
  .st-kok .pane{display:none}.st-kok .pane.acik{display:block}
  .st-kok .bos{color:var(--sub);text-align:center;padding:18px}
  .st-kok .rozet{display:inline-block;font-size:11px;background:var(--acik);color:var(--ink);border-radius:20px;padding:2px 8px;font-weight:700}
  .st-kok .oneri{border:1px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px;background:var(--card2)}
  .st-kok .oneri b{font-size:13px}.st-kok .oneri p{font-size:13px;color:var(--sub2);margin:3px 0 0}
</style>
@endpush
@section('content')
<div class="st-kok">
  <div class="tabs">
    <button class="sec" onclick="tab('kalip',this)">SSS / Kalıplar</button>
    <button onclick="tab('ogr',this)">Öğrendikleri</button>
    <button onclick="tab('coz',this)">Cevaplanamayanlar</button>
    <button onclick="tab('pdf',this)">PDF'ten çıkar</button>
  </div>

  <!-- SSS / KALIPLAR -->
  <div class="pane acik" id="p_kalip">
    <div class="card">
      <h2>Yeni hazır cevap (SSS)</h2>
      <label>Tetikleyici kelimeler (virgülle)</label>
      <input type="text" id="k_tet" placeholder="otopark, park yeri, araç">
      <label>Cevap (telefonda okunacak)</label>
      <textarea id="k_cev" placeholder="Evet, ücretsiz otoparkımız var."></textarea>
      <label>Kategori (opsiyonel)</label>
      <input type="text" id="k_kat" placeholder="genel">
      <div style="margin-top:12px"><button class="btn pri" onclick="kalipEkle()">+ Ekle</button></div>
      <div class="hint" style="font-size:12px;color:var(--sub);margin-top:8px">Müşteri bu kelimelerden birini söyleyince AI, Haiku'ya gitmeden bu cevabı verir (bedava, anında).</div>
    </div>
    <div class="card">
      <h2>Kayıtlı kalıplar</h2>
      <table><thead><tr><th>Tetikleyiciler</th><th>Cevap</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="k_liste"><tr><td colspan=3 class="bos">Yükleniyor…</td></tr></tbody></table>
    </div>
  </div>

  <!-- OGRENILENLER -->
  <div class="pane" id="p_ogr">
    <div class="card">
      <h2>AI'nın öğrendikleri (bilgi soruları)</h2>
      <p class="hint" style="font-size:12px;color:var(--sub);margin-bottom:10px">Haiku bir kez cevapladı, sistem öğrendi; aynı soru tekrar gelince bedava veriyor. Yanlış öğrendiyse düzeltin/silin.</p>
      <table><thead><tr><th>Soru</th><th>Öğrenilen cevap</th><th>Kez</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="o_liste"><tr><td colspan=4 class="bos">Yükleniyor…</td></tr></tbody></table>
    </div>
  </div>

  <!-- COZULEMEYENLER -->
  <div class="pane" id="p_coz">
    <div class="card">
      <h2>Cevaplanamayan sorular</h2>
      <p class="hint" style="font-size:12px;color:var(--sub);margin-bottom:10px">AI'nın çözemeyip yetkiliye aktardığı sorular (en çok sorulan üstte). "Cevap ekle" ile kalıba çevir → bir daha AI cevaplasın.</p>
      <table><thead><tr><th>Soru</th><th>Kez</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="c_liste"><tr><td colspan=3 class="bos">Yükleniyor…</td></tr></tbody></table>
    </div>
  </div>

  <!-- PDF -->
  <div class="pane" id="p_pdf">
    <div class="card">
      <h2>PDF'ten kalıp çıkar</h2>
      <p class="hint" style="font-size:12px;color:var(--sub);margin-bottom:10px">Menü, SSS veya kurumsal bilgi PDF'i yükleyin; AI soru-cevap kalıpları önersin, onaylayınca eklensin.</p>
      <input type="file" id="pdf_dosya" accept="application/pdf">
      <div style="margin-top:12px"><button class="btn pri" onclick="pdfCikar()" id="pdf_btn">Kalıpları çıkar</button></div>
      <div id="pdf_sonuc" style="margin-top:14px"></div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
  const SUBE = {{ $subeId }};
  function form(o){ return Object.entries(o).map(([k,v])=>k+'='+encodeURIComponent(v)).join('&'); }
  async function post(u,o){ const r=await fetch(u,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({sube_id:SUBE,...o})}); return r.json(); }
  async function get(u){ const r=await fetch(u+'?sube_id='+SUBE+'&_t='+Date.now(),{cache:'no-store'}); return r.json(); }
  function esc(s){ return (s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

  function tab(ad,btn){
    document.querySelectorAll('.tabs button').forEach(b=>b.className='');
    btn.className='sec';
    document.querySelectorAll('.pane').forEach(p=>p.className='pane');
    document.getElementById('p_'+ad).className='pane acik';
    if(ad==='kalip') kalipYukle(); if(ad==='ogr') ogrYukle(); if(ad==='coz') cozYukle();
  }

  // --- KALIP ---
  async function kalipYukle(){
    const d=await get('/api/santral-egitim/kalip-liste'); const t=document.getElementById('k_liste');
    if(!d.liste||!d.liste.length){ t.innerHTML='<tr><td colspan=3 class="bos">Henüz kalıp yok</td></tr>'; return; }
    t.innerHTML=''; d.liste.forEach(x=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td><b>${esc(x.tetikleyiciler)}</b>${x.aktif==0?' <span class=rozet>pasif</span>':''}</td>
        <td>${esc(x.cevap)}</td>
        <td><div class="aksi">
          <button class="btn sec sm" onclick="kalipAktif(${x.id},${x.aktif==1?0:1})">${x.aktif==1?'Pasifle':'Aktifle'}</button>
          <button class="btn dgr sm" onclick="kalipSil(${x.id})">Sil</button>
        </div></td>`;
      t.appendChild(tr);
    });
  }
  async function kalipEkle(){
    const tet=k_tet.value.trim(), cev=k_cev.value.trim(), kat=k_kat.value.trim();
    if(!tet||!cev){ alert('Tetikleyici ve cevap zorunlu'); return; }
    const d=await post('/api/santral-egitim/kalip-ekle',{tetikleyiciler:tet,cevap:cev,kategori:kat});
    if(d.ok){ k_tet.value='';k_cev.value='';k_kat.value=''; kalipYukle(); } else alert(d.hata||'Hata');
  }
  async function kalipAktif(id,aktif){ await post('/api/santral-egitim/kalip-guncelle',{id,aktif}); kalipYukle(); }
  function kalipSil(id){ rkOnay('Silinsin mi?', async function(){ await post('/api/santral-egitim/kalip-sil',{id}); kalipYukle(); }, 'var(--kirmizi)', 'Sil'); }

  // --- OGRENILEN ---
  async function ogrYukle(){
    const d=await get('/api/santral-egitim/ogrenilen-liste'); const t=document.getElementById('o_liste');
    if(!d.liste||!d.liste.length){ t.innerHTML='<tr><td colspan=4 class="bos">Henüz öğrenilen yok</td></tr>'; return; }
    t.innerHTML=''; d.liste.forEach(x=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${esc(x.soru_key)}</td><td>${esc(x.cevap)}</td><td>${x.kullanim}</td>
        <td><div class="aksi">
          <button class="btn sec sm" onclick="ogrDuzelt(${x.id},this)">Düzelt</button>
          <button class="btn dgr sm" onclick="ogrSil(${x.id})">Sil</button>
        </div></td>`;
      tr.dataset.cev=x.cevap; t.appendChild(tr);
    });
  }
  function ogrDuzelt(id,btn){
    const tr=btn.closest('tr');
    rkIste('Yeni cevap:', async function(yeni){
      if(yeni===null) return;
      await post('/api/santral-egitim/ogrenilen-guncelle',{id,cevap:yeni}); ogrYukle();
    }, { baslik:'Cevabı Düzelt', varsayilan: tr.dataset.cev||'', ok:'Kaydet' });
  }
  function ogrSil(id){ rkOnay('Silinsin mi? (Bir daha sorulursa yeniden öğrenir)', async function(){ await post('/api/santral-egitim/ogrenilen-sil',{id}); ogrYukle(); }, 'var(--kirmizi)', 'Sil'); }

  // --- COZULEMEYEN ---
  async function cozYukle(){
    const d=await get('/api/santral-egitim/cozulemeyen-liste'); const t=document.getElementById('c_liste');
    if(!d.liste||!d.liste.length){ t.innerHTML='<tr><td colspan=3 class="bos">Cevaplanamayan yok 🎉</td></tr>'; return; }
    t.innerHTML=''; d.liste.forEach(x=>{
      const tr=document.createElement('tr');
      tr.innerHTML=`<td>${esc(x.ham||x.soru_norm)}</td><td>${x.adet}</td>
        <td><div class="aksi">
          <button class="btn pri sm" onclick="cozKalip(${x.id})">Cevap ekle</button>
          <button class="btn dgr sm" onclick="cozSil(${x.id})">Sil</button>
        </div></td>`;
      t.appendChild(tr);
    });
  }
  function cozKalip(id){
    rkIste('Bu soruya AI ne cevap versin?', async function(cev){
      if(!cev) return;
      const d=await post('/api/santral-egitim/cozulemeyen-kalipla',{id,cevap:cev});
      if(d.ok){ alert('Kalıba eklendi, artık AI cevaplayacak.'); cozYukle(); } else alert(d.hata||'Hata');
    }, { baslik:'AI Cevabı Ekle', placeholder:'Cevap metni', ok:'Ekle' });
  }
  function cozSil(id){ rkOnay('Silinsin mi?', async function(){ await post('/api/santral-egitim/cozulemeyen-sil',{id}); cozYukle(); }, 'var(--kirmizi)', 'Sil'); }

  // --- PDF ---
  async function pdfCikar(){
    const f=document.getElementById('pdf_dosya').files[0];
    if(!f){ alert('PDF seçin'); return; }
    const btn=document.getElementById('pdf_btn'); btn.disabled=true; btn.textContent='Çıkarılıyor…';
    const s=document.getElementById('pdf_sonuc'); s.innerHTML='<div class="bos">AI belgeyi okuyor…</div>';
    try{
      const b64=await new Promise((res,rej)=>{const r=new FileReader();r.onload=()=>res(r.result.split(',')[1]);r.onerror=rej;r.readAsDataURL(f);});
      const d=await post('/api/santral-egitim/pdf-cikar',{pdf_base64:b64});
      if(!d.ok){ s.innerHTML='<div class="hata" style="color:var(--red)">'+esc(d.hata||'Hata')+'</div>'; return; }
      window._pdfKaliplar=d.kaliplar;
      s.innerHTML='<b>'+d.kaliplar.length+' kalıp önerildi:</b>'+d.kaliplar.map(k=>`<div class="oneri"><b>${esc(k.tetikleyiciler)}</b><p>${esc(k.cevap)}</p></div>`).join('')
        +'<button class="btn pri" onclick="pdfOnayla()">Hepsini ekle</button>';
    }catch(e){ s.innerHTML='<div style="color:var(--red)">'+esc(e.message)+'</div>'; }
    finally{ btn.disabled=false; btn.textContent='Kalıpları çıkar'; }
  }
  async function pdfOnayla(){
    const d=await post('/api/santral-egitim/pdf-onayla',{kaliplar:JSON.stringify(window._pdfKaliplar||[])});
    if(d.ok){ alert(d.eklendi+' kalıp eklendi.'); document.getElementById('pdf_sonuc').innerHTML=''; }
    else alert('Hata');
  }

  kalipYukle();
</script>
@endpush

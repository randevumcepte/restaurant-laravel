@extends('resteos_yonetim.layout')
@section('baslik','Dahili Yönetimi')
@section('content')
@if(!$ayarli)
  <div class="ry-alert hata">⚠️ Önce FreePBX API bağlantısını ayarlayın. <a href="/resteos-yonetim/santral" style="font-weight:700">→ Santral Bağlantısı</a></div>
@else
<div class="ry-grid ry-g2">
  <div class="ry-card" style="align-self:start">
    <h3>➕ Yeni Dahili Ekle <span class="ry-badge mor" style="margin-left:auto">PJSIP</span></h3>
    <p class="ry-mut" style="margin-bottom:12px">SIP dahilileri panelden oluşturun/silin. FreePBX'e yazılır, telefonlar buraya kayıt olur.</p>
    <div class="ry-row">
      <div class="ry-field" style="max-width:130px"><label>Numara</label><input class="ry-input" id="e_num" placeholder="101"></div>
      <div class="ry-field"><label>Ad</label><input class="ry-input" id="e_ad" placeholder="Kasa"></div>
    </div>
    <div class="ry-field"><label>SIP şifresi</label><input class="ry-input" id="e_sif" placeholder="güçlü bir şifre"></div>
    <button class="ry-btn ry-btn-primary" type="button" onclick="ekle()">+ Ekle</button>
    <div id="e_hata" class="ry-alert hata" style="display:none;margin-top:10px;white-space:pre-wrap;word-break:break-all"></div>
  </div>

  <div class="ry-card">
    <h3>☎️ Dahililer</h3>
    <table class="ry-table">
      <thead><tr><th>Numara</th><th>Ad</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="liste"><tr><td colspan="3" class="ry-mut" style="text-align:center;padding:18px">Yükleniyor…</td></tr></tbody>
    </table>
    <div id="l_hata" class="ry-alert hata" style="display:none;margin-top:10px;white-space:pre-wrap;word-break:break-all"></div>
    <hr class="ry-hr">
    <a class="ry-btn ry-btn-soft ry-btn-sm" href="/resteos-yonetim/santral">← Santral Bağlantısı</a>
  </div>
</div>

<script>
  const listeEl=document.getElementById('liste');
  function form(obj){ return Object.entries(obj).map(([k,v])=>k+'='+encodeURIComponent(v==null?'':v)).join('&'); }
  function gosterHata(id,msg){ const el=document.getElementById(id); el.textContent=msg; el.style.display=msg?'block':'none'; }

  async function yukle(){
    listeEl.innerHTML='<tr><td colspan=3 class="ry-mut" style="text-align:center;padding:18px">Yükleniyor…</td></tr>';
    try{
      const r=await fetch('/api/dahili/liste',{method:'POST'}); const d=await r.json();
      if(!d.ok){ listeEl.innerHTML='<tr><td colspan=3 class="ry-mut" style="text-align:center;padding:18px">Liste alınamadı</td></tr>'; gosterHata('l_hata', d.hata||''); return; }
      gosterHata('l_hata', d.hata||'');
      if(!d.liste.length){ listeEl.innerHTML='<tr><td colspan=3 class="ry-mut" style="text-align:center;padding:18px">Kayıtlı dahili yok</td></tr>'; return; }
      listeEl.innerHTML='';
      d.liste.forEach(x=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td class="ry-restoran"><b>${x.numara}</b></td><td>${x.ad||''}</td>
          <td style="text-align:right"><div class="ry-flex" style="justify-content:flex-end">
            <button class="ry-btn ry-btn-soft ry-btn-sm" onclick="sifre('${x.numara}')">Şifre</button>
            <button class="ry-btn ry-btn-danger ry-btn-sm" onclick="sil('${x.numara}')">Sil</button>
          </div></td>`;
        listeEl.appendChild(tr);
      });
    }catch(e){ listeEl.innerHTML='<tr><td colspan=3 class="ry-mut" style="text-align:center;padding:18px">Hata</td></tr>'; gosterHata('l_hata', e.message); }
  }

  async function ekle(){
    const numara=document.getElementById('e_num').value.trim();
    const ad=document.getElementById('e_ad').value.trim();
    const sifre=document.getElementById('e_sif').value.trim();
    gosterHata('e_hata','');
    if(!numara||!sifre){ gosterHata('e_hata','Numara ve şifre zorunlu.'); return; }
    try{
      const r=await fetch('/api/dahili/ekle',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara,ad,sifre})});
      const d=await r.json();
      if(d.ok){ document.getElementById('e_num').value='';document.getElementById('e_ad').value='';document.getElementById('e_sif').value=''; yukle(); }
      else gosterHata('e_hata', (d.hata||'Eklenemedi')+(d.ham?('\n\n'+d.ham):''));
    }catch(e){ gosterHata('e_hata', e.message); }
  }

  async function sil(numara){
    if(!confirm(numara+' numaralı dahili silinsin mi?')) return;
    const r=await fetch('/api/dahili/sil',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara})});
    const d=await r.json();
    if(d.ok) yukle(); else alert('Silinemedi: '+(d.hata||''));
  }

  async function sifre(numara){
    const s=prompt(numara+' için yeni SIP şifresi:');
    if(!s) return;
    const r=await fetch('/api/dahili/sifre',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({numara,sifre:s})});
    const d=await r.json();
    if(d.ok) alert('Şifre güncellendi.'); else alert('Güncellenemedi: '+(d.hata||''));
  }

  yukle();
</script>
@endif
@endsection

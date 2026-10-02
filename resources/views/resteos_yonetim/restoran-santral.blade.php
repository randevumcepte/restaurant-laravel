@extends('resteos_yonetim.layout')
@section('baslik', $sube->ad.' · Santral')
@section('content')

<style>
  .st-hrow{display:flex;gap:8px;align-items:flex-end;margin-bottom:10px;padding:12px;border:1px solid var(--ry-line);border-radius:12px;background:rgba(99,102,241,.04)}
  .st-hrow .f{flex:1}.st-hrow .f.k{max-width:110px}
  .st-hrow .sil{border:none;background:#fee2e2;color:#ef4444;width:40px;height:42px;border-radius:10px;font-size:20px;cursor:pointer;flex:none}
  .st-onizle{background:rgba(99,102,241,.08);border:1px dashed rgba(99,102,241,.4);border-radius:10px;padding:12px;font-family:monospace;font-size:14px;margin-top:6px;word-break:break-all;white-space:pre-wrap}
  .st-mesaj{padding:12px 14px;border-radius:10px;margin-top:12px;font-size:13px;white-space:pre-wrap;word-break:break-word;display:none}
  .st-mesaj.ok{background:#dcfce7;border:1px solid #86efac;color:#166534}
  .st-mesaj.err{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;font-family:monospace}
  .st-gizli{display:none}
</style>

<div class="ry-between" style="margin-bottom:16px">
  <a href="/resteos-yonetim/restoran/{{ $sube->id }}" class="ry-btn ry-btn-soft ry-btn-sm">← Restoran Detayı</a>
  <a href="/resteos-yonetim/santral/dahili" class="ry-btn ry-btn-soft ry-btn-sm">☎️ Dahili Yönetimi</a>
</div>

<div class="ry-grid ry-g2">
  <!-- SOL: HAT / DID -->
  <div class="ry-card" style="align-self:start">
    <h3>📞 Hat / DID Bağlama</h3>
    <p class="ry-mut" style="margin-bottom:12px">chan_sip trunk oluştur + gelen numarayı (DID) AI Santral'e (<code>gelen-restoran</code>) bağla. Sadece bu restorana ait.</p>
    @if(!$trunkAyarli)
      <div class="ry-alert hata">⚠️ Önce Trunk API bağlantısını ayarlayın. <a href="/resteos-yonetim/santral" style="font-weight:700">→ Santral Bağlantısı</a></div>
    @else
    <div class="ry-row">
      <div class="ry-field"><label>Gelen numara (DID)</label><input class="ry-input" id="h_did" placeholder="902322404046"></div>
      <div class="ry-field"><label>Trunk adı (ops.)</label><input class="ry-input" id="h_ad" placeholder="DRMET-Voicetelekom"></div>
    </div>
    <div class="ry-row">
      <div class="ry-field"><label>SIP sunucu (host / IP)</label><input class="ry-input" id="h_host" placeholder="sip1.voicetelekom.net"></div>
      <div class="ry-field"><label>Hesap ismi (username)</label><input class="ry-input" id="h_user" placeholder="902322404046"></div>
    </div>
    <div class="ry-row">
      <div class="ry-field"><label>SIP şifresi</label><input class="ry-input" id="h_sif" type="password" placeholder="••••••••"></div>
      <div class="ry-field"><label>Hedef context</label><input class="ry-input" id="h_ctx" value="gelen-restoran"></div>
    </div>
    <button class="ry-btn ry-btn-primary" type="button" onclick="hatKur()">⚙️ Trunk oluştur + DID'i bağla</button>
    <div class="st-mesaj" id="h_mesaj"></div>

    <hr class="ry-hr">
    <h3>Bu restoranın hatları</h3>
    <table class="ry-table">
      <thead><tr><th>DID</th><th>Trunk</th><th>Sunucu</th><th style="text-align:right">İşlem</th></tr></thead>
      <tbody id="hliste"><tr><td colspan="4" class="ry-mut" style="text-align:center;padding:16px">Yükleniyor…</td></tr></tbody>
    </table>
    @endif
  </div>

  <!-- SAĞ: AI AKTARMA HEDEFLERİ -->
  <div class="ry-card" style="align-self:start">
    <h3>🤖 AI Aktarma Hedefleri</h3>
    <p class="ry-mut" style="margin-bottom:12px">AI “sizi yetkiliye bağlıyorum” dediğinde çağrı nereye/kime gitsin? Asterisk'e dokunmaya gerek yok.</p>

    <label class="ry-mut ry-flex" style="font-weight:700;gap:8px;margin-bottom:10px">
      <input type="checkbox" id="a_aktif" checked> Aktarma açık (AI gerektiğinde çağrıyı yönlendirsin)
    </label>

    <div class="ry-field"><label>Çalma stratejisi</label>
      <select class="ry-select" id="a_strateji" onchange="ciz()">
        <option value="hepsi">Hepsi aynı anda çalsın (ilk açan alır)</option>
        <option value="sirali">Sırayla çalsın (önce 1., açmazsa 2.…)</option>
      </select>
    </div>

    <label class="ry-mut" style="font-weight:700;display:block;margin:10px 0 6px">Hedefler (dahili / cep)</label>
    <div id="a_satirlar"></div>
    <button type="button" class="ry-btn ry-btn-soft ry-btn-sm ry-btn-blok" onclick="satirEkle()">+ Hedef ekle</button>

    <div class="ry-row" style="margin-top:12px">
      <div class="ry-field" style="max-width:150px"><label>Zil süresi (sn)</label><input class="ry-input" type="number" id="a_zil" value="30" min="5" max="120" oninput="ciz()"></div>
    </div>
    <div id="a_ziluyari" class="ry-mut" style="font-size:12px;margin-bottom:6px"></div>
    <label class="ry-mut" style="font-weight:700;display:block;margin-bottom:4px">Aranacak hedefler (Asterisk'e giden)</label>
    <div class="st-onizle" id="a_onizle">—</div>

    <div class="ry-field" style="margin-top:14px"><label>🛵 Teslimat bölgesi (AI bunu uygular)</label>
      <textarea id="a_teslimat" rows="3" class="ry-input" style="resize:vertical" placeholder="Örn: Sadece Bayraklı, Karşıyaka ve Çiğli'ye teslimat. Minimum sepet 150 TL."></textarea></div>

    <button class="ry-btn ry-btn-primary ry-btn-blok" type="button" onclick="aktarmaKaydet()">Aktarma Ayarını Kaydet</button>
    <div class="st-mesaj" id="a_mesaj"></div>
  </div>
</div>

<script>
  const SUBE = {{ (int) $sube->id }};
  function form(obj){ return Object.entries(obj).map(([k,v])=>k+'='+encodeURIComponent(v==null?'':v)).join('&'); }

  // ---------------- HAT / DID ----------------
  @if($trunkAyarli)
  const hlisteEl=document.getElementById('hliste');
  function hmesaj(txt, ok){ const el=document.getElementById('h_mesaj'); el.textContent=txt; el.className='st-mesaj '+(ok?'ok':'err'); el.style.display=txt?'block':'none'; }

  async function hatYukle(){
    hlisteEl.innerHTML='<tr><td colspan=4 class="ry-mut" style="text-align:center;padding:16px">Yükleniyor…</td></tr>';
    try{
      const r=await fetch('/api/hat/liste',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({sube_id:SUBE})});
      const d=await r.json();
      if(!d.ok||!d.liste.length){ hlisteEl.innerHTML='<tr><td colspan=4 class="ry-mut" style="text-align:center;padding:16px">Bu restorana bağlı hat yok</td></tr>'; return; }
      hlisteEl.innerHTML='';
      d.liste.forEach(x=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`<td class="ry-restoran"><b>${x.did}</b></td>
          <td>${x.trunk_adi||''} <span class="ry-badge mor">${x.tech||'sip'}</span></td>
          <td class="ry-mut">${x.host||''}</td>
          <td style="text-align:right"><button class="ry-btn ry-btn-danger ry-btn-sm" onclick="hatSil('${x.did}')">Kaldır</button></td>`;
        hlisteEl.appendChild(tr);
      });
    }catch(e){ hlisteEl.innerHTML='<tr><td colspan=4 class="ry-mut" style="text-align:center;padding:16px">Hata: '+e.message+'</td></tr>'; }
  }

  async function hatKur(){
    const did=document.getElementById('h_did').value.trim();
    const ad=document.getElementById('h_ad').value.trim();
    const host=document.getElementById('h_host').value.trim();
    const username=document.getElementById('h_user').value.trim();
    const sip_secret=document.getElementById('h_sif').value;
    const context=document.getElementById('h_ctx').value.trim()||'gelen-restoran';
    hmesaj('',true);
    if(!did||!host||!username||!sip_secret){ hmesaj('DID, host, username ve SIP şifresi zorunlu.',false); return; }
    hmesaj('⏳ Trunk oluşturuluyor ve DID bağlanıyor… (reload birkaç saniye sürebilir)',true);
    try{
      const r=await fetch('/api/hat/kur',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:form({sube_id:SUBE,did,ad,host,username,sip_secret,context,tech:'sip'})});
      const d=await r.json();
      if(d.ok){
        hmesaj('✅ '+(d.mesaj||'Hat kuruldu.'),true);
        ['h_did','h_ad','h_host','h_user','h_sif'].forEach(i=>document.getElementById(i).value='');
        hatYukle();
      } else hmesaj('❌ '+(d.hata||'Kurulamadı')+'\n\n'+JSON.stringify(d.ayrinti||{},null,2),false);
    }catch(e){ hmesaj('❌ İstek hatası: '+e.message,false); }
  }

  async function hatSil(did){
    if(!confirm(did+' numaralı hattın DID bağlaması kaldırılsın mı? (Trunk FreePBX\'te kalır)')) return;
    const r=await fetch('/api/hat/sil',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:form({sube_id:SUBE,did})});
    const d=await r.json();
    if(d.ok) hatYukle(); else alert('Kaldırılamadı: '+(d.hata||''));
  }
  hatYukle();
  @endif

  // ---------------- AI AKTARMA ----------------
  const satirlarEl=document.getElementById('a_satirlar');
  function amesaj(txt, ok){ const el=document.getElementById('a_mesaj'); el.textContent=txt; el.className='st-mesaj '+(ok?'ok':'err'); el.style.display=txt?'block':'none'; }

  function satirEkle(h){
    h = h || {tip:'dahili', teknoloji:'SIP', numara:'', trunk:''};
    const row=document.createElement('div'); row.className='st-hrow';
    row.innerHTML=`
      <div class="f" style="max-width:130px"><label class="ry-mut" style="font-size:12px;font-weight:700">Tip</label>
        <select class="ry-select tip" onchange="ciz()">
          <option value="dahili" ${h.tip!=='dis'?'selected':''}>Dahili</option>
          <option value="dis" ${h.tip==='dis'?'selected':''}>Cep / dış</option>
        </select></div>
      <div class="f"><label class="ry-mut numLbl" style="font-size:12px;font-weight:700">Dahili numara</label>
        <input class="ry-input numara" value="${(h.numara||'').replace(/"/g,'&quot;')}" placeholder="101" oninput="ciz()"></div>
      <div class="f k"><label class="ry-mut" style="font-size:12px;font-weight:700">Teknoloji</label>
        <select class="ry-select teknoloji" onchange="ciz()">
          <option value="SIP" ${h.teknoloji!=='PJSIP'?'selected':''}>SIP</option>
          <option value="PJSIP" ${h.teknoloji==='PJSIP'?'selected':''}>PJSIP</option>
        </select></div>
      <div class="f trunkAlan st-gizli"><label class="ry-mut" style="font-size:12px;font-weight:700">Dış hat (trunk)</label>
        <input class="ry-input trunk" value="${(h.trunk||'').replace(/"/g,'&quot;')}" placeholder="ops." oninput="ciz()"></div>
      <button type="button" class="sil" onclick="this.closest('.st-hrow').remove();ciz()">×</button>`;
    satirlarEl.appendChild(row); ciz();
  }

  function topla(){
    const out=[];
    satirlarEl.querySelectorAll('.st-hrow').forEach(r=>{
      const tip=r.querySelector('.tip').value;
      const num=r.querySelector('.numara').value.trim();
      const tek=r.querySelector('.teknoloji').value;
      const trunk=r.querySelector('.trunk').value.trim();
      r.querySelector('.trunkAlan').className='f trunkAlan'+(tip==='dis'?'':' st-gizli');
      r.querySelector('.numLbl').textContent=(tip==='dis')?'Cep / sabit numara':'Dahili numara';
      r.querySelector('.numara').placeholder=(tip==='dis')?'05xxxxxxxxx':'101';
      if(num) out.push({tip, teknoloji:tek, numara:num, trunk:trunk||null});
    });
    return out;
  }
  function dial(h){ if(!h.numara) return ''; return (h.tip==='dis'&&h.trunk)?`${h.teknoloji}/${h.trunk}/${h.numara}`:`${h.teknoloji}/${h.numara}`; }

  function ciz(){
    const hedefler=topla();
    const strat=document.getElementById('a_strateji').value||'hepsi';
    const zil=document.getElementById('a_zil').value||30;
    document.getElementById('a_ziluyari').textContent=strat==='sirali'
      ? `Sırayla: her hedef ${zil} sn çalar, açılmazsa sıradakine geçilir.`
      : `Hepsi birlikte ${zil} sn çalar; ilk açan alır.`;
    const dials=hedefler.map(dial).filter(Boolean);
    document.getElementById('a_onizle').textContent=dials.length
      ? (strat==='sirali'?dials.join('  →  '):dials.join('   +   ')) : '—';
  }

  async function aktarmaKaydet(){
    amesaj('Kaydediliyor…',true);
    try{
      const r=await fetch('/api/santral/ayar-kaydet',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:form({
          sube_id:SUBE,
          aktarma_aktif: document.getElementById('a_aktif').checked ? 1 : '',
          strateji: document.getElementById('a_strateji').value,
          hedefler: JSON.stringify(topla()),
          zil_sure: document.getElementById('a_zil').value||30,
          teslimat_bolge: document.getElementById('a_teslimat').value,
        })});
      const d=await r.json();
      amesaj(d.ok?'✅ Aktarma ayarı kaydedildi. Yeni çağrılarda geçerli.':('❌ '+(d.hata||'Kaydedilemedi')), d.ok);
    }catch(e){ amesaj('❌ İstek hatası: '+e.message,false); }
  }

  async function aktarmaYukle(){
    try{
      const r=await fetch('/api/santral/ayar?sube_id='+SUBE,{headers:{'Accept':'application/json'}});
      const d=await r.json(); const a=(d&&d.ayar)||{};
      document.getElementById('a_aktif').checked = a.aktif ? true : false;
      document.getElementById('a_strateji').value = a.strateji==='sirali' ? 'sirali' : 'hepsi';
      document.getElementById('a_zil').value = a.zil_sure || 30;
      document.getElementById('a_teslimat').value = a.teslimat_bolge || '';
      satirlarEl.innerHTML='';
      if(a.hedefler && a.hedefler.length) a.hedefler.forEach(satirEkle); else satirEkle();
      ciz();
    }catch(e){ satirEkle(); }
  }
  aktarmaYukle();
</script>
@endsection

@extends('resteos_yonetim.layout')
@section('baslik','Santral Bağlantısı')
@section('content')
<div class="ry-grid ry-g2">
  <div class="ry-card" style="align-self:start">
    <h3>🔌 FreePBX API Bağlantısı</h3>
    <p class="ry-mut" style="margin-bottom:12px">Dahilileri panelden yönetmek için FreePBX'in kendi API'sine bağlanır. FreePBX yapılandırması bozulmaz.</p>

    <label class="ry-mut ry-flex" style="font-weight:700;gap:8px;margin-bottom:10px">
      <input type="checkbox" id="f_aktif" {{ ($ay && $ay->aktif) ? 'checked' : '' }}> FreePBX API aktif
    </label>

    <div class="ry-field"><label>FreePBX admin adresi (base URL)</label>
      <input class="ry-input" id="f_base" value="{{ $ay->base_url ?? '' }}" placeholder="https://pbx.domain.com veya https://SUNUCU_IP"></div>
    <p class="ry-mut" style="margin:-6px 0 10px;font-size:12px">Sonunda / olmasın. Örn: https://192.168.1.10</p>

    <div class="ry-field"><label>Client ID</label>
      <input class="ry-input" id="f_cid" value="{{ $ay->client_id ?? '' }}" placeholder="Admin → API → Applications"></div>
    <div class="ry-field"><label>Client Secret</label>
      <input class="ry-input" id="f_csec" type="password" value="{{ $ay->client_secret ?? '' }}" placeholder="••••••••"></div>

    <div class="ry-flex" style="margin-top:6px">
      <button class="ry-btn ry-btn-primary" type="button" onclick="kaydet()">Kaydet</button>
      <button class="ry-btn ry-btn-soft" type="button" onclick="test()">Bağlantıyı test et</button>
    </div>
    <div id="f_sonuc" class="ry-mut" style="margin-top:10px"></div>
    <hr class="ry-hr">
    <a class="ry-btn ry-btn-soft ry-btn-sm" href="/resteos-yonetim/santral/dahili">→ Dahili Yönetimi</a>
  </div>

  <div>
    <div class="ry-card" style="align-self:start">
      <h3>📞 Hat (Trunk + DID) API'si</h3>
      <p class="ry-mut" style="margin-bottom:12px">GraphQL trunk açamaz. chan_sip trunk + DID bağlama, FreePBX sunucuya konan <b>santral-trunk.php</b> ucu üzerinden yapılır.</p>
      <div class="ry-field"><label>Trunk API URL</label>
        <input class="ry-input" id="t_url" value="{{ $ay->trunk_api_url ?? '' }}" placeholder="https://santral.ornek.com/monitor/api/santral-trunk.php"></div>
      <div class="ry-field"><label>Trunk API gizli anahtarı (secret)</label>
        <input class="ry-input" id="t_sec" type="password" value="{{ $ay->trunk_api_secret ?? '' }}" placeholder="••••••••"></div>
      <p class="ry-mut" style="font-size:12px">santral-trunk.php içindeki <code>SANTRAL_SECRET</code> ile birebir AYNI olmalı. Trunk/DID bağlama her restoranın kendi sayfasından yapılır.</p>
    </div>

    <div class="ry-card">
      <h3>ℹ️ Hazırlık (FreePBX'te bir kez)</h3>
      <ol class="ry-mut" style="margin:0;padding-left:18px;font-size:13px;line-height:1.7">
        <li>Admin → Module Admin → <b>API</b> modülü kurulu/etkin olsun.</li>
        <li>Admin → <b>API</b> → Applications → yeni uygulama → Client ID + Secret al.</li>
        <li>Uygulamaya dahili (Core/Extensions) okuma-yazma + GraphQL izni ver.</li>
        <li>Yukarıya gir, “Bağlantıyı test et” ile doğrula.</li>
      </ol>
    </div>
  </div>
</div>

<script>
  function form(obj){ return Object.entries(obj).map(([k,v])=>k+'='+encodeURIComponent(v==null?'':v)).join('&'); }
  function sonuc(txt,renk){ const s=document.getElementById('f_sonuc'); s.textContent=txt; s.style.color=renk||'#64748b'; }

  async function kaydet(){
    sonuc('Kaydediliyor…');
    try{
      const r=await fetch('/api/freepbx/ayar-kaydet',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:form({
          aktif: document.getElementById('f_aktif').checked ? 1 : '',
          base_url: document.getElementById('f_base').value.trim(),
          client_id: document.getElementById('f_cid').value.trim(),
          client_secret: document.getElementById('f_csec').value,
          trunk_api_url: document.getElementById('t_url').value.trim(),
          trunk_api_secret: document.getElementById('t_sec').value,
        })});
      const d=await r.json();
      sonuc(d.ok ? '✅ Kaydedildi.' : ('❌ '+(d.hata||'Kaydedilemedi')), d.ok ? '#16a34a' : '#ef4444');
    }catch(e){ sonuc('❌ İstek hatası: '+e.message,'#ef4444'); }
  }

  async function test(){
    sonuc('Test ediliyor…');
    try{
      const r=await fetch('/api/freepbx/test',{method:'POST'}); const d=await r.json();
      sonuc((d.ok?'✅ ':'❌ ')+(d.ok?(d.mesaj||'Bağlantı başarılı'):(d.hata||'Bağlantı başarısız')), d.ok?'#16a34a':'#ef4444');
    }catch(e){ sonuc('❌ İstek hatası: '+e.message,'#ef4444'); }
  }
</script>
@endsection

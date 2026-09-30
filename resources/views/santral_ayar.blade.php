<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI Santral — Aktarma Ayarları</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:640px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:18px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}
  .card h2{font-size:14px;color:var(--sub);text-transform:uppercase;letter-spacing:.04em;margin-bottom:14px}
  label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
  input[type=text],input[type=number],select{width:100%;border:1px solid var(--line);border-radius:10px;padding:12px;font-size:15px;outline:none;background:#f8fafc}
  input:focus,select:focus{border-color:var(--indigo);background:#fff}
  .satir{display:flex;gap:12px}.satir>div{flex:1}
  .hint{font-size:12px;color:var(--sub);margin-top:5px;line-height:1.5}
  .switch{display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px}
  .switch input{width:44px;height:26px;appearance:none;background:#cbd5e1;border-radius:20px;position:relative;cursor:pointer;transition:.2s}
  .switch input:checked{background:var(--ok)}
  .switch input::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.2s}
  .switch input:checked::after{left:21px}
  .onizle{background:#eef2ff;border:1px dashed #c7d2fe;border-radius:10px;padding:12px;font-family:monospace;font-size:14px;color:var(--indigo);margin-top:6px;word-break:break-all}
  .kaydet{width:100%;border:none;border-radius:12px;padding:15px;font-size:16px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2));cursor:pointer;margin-top:8px}
  .basari{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600}
  .gizli{display:none}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">📞</div>
    <div><h1>AI Santral — Aktarma Ayarları</h1><p>AI "sizi yetkiliye bağlıyorum" dediğinde çağrı nereye gitsin? Buradan yönetin; Asterisk'e dokunmanıza gerek yok.</p></div>
  </div>

  @if(request('kaydedildi'))<div class="basari">✅ Ayarlar kaydedildi. Yeni çağrılarda geçerli.</div>@endif

  <form method="post" action="/santral-ayar-kaydet">
    @csrf
    <input type="hidden" name="sube_id" value="{{ $subeId }}">

    <div class="card">
      <h2>Aktarma</h2>
      <label class="switch">
        <input type="checkbox" name="aktarma_aktif" value="1" {{ (!$ay || $ay->aktarma_aktif) ? 'checked' : '' }}>
        Aktarma açık (AI gerektiğinde çağrıyı yönlendirsin)
      </label>
      <div class="hint">Kapalıysa AI aktarmaz; kendisi çözmeye çalışır.</div>
    </div>

    <div class="card">
      <h2>Hedef</h2>

      <label>Nereye aktarılsın?</label>
      <select name="hedef_tip" id="hedef_tip" onchange="guncelle()">
        <option value="dahili" {{ (!$ay || $ay->hedef_tip=='dahili') ? 'selected' : '' }}>Santral içi dahili (ör. 101)</option>
        <option value="dis" {{ ($ay && $ay->hedef_tip=='dis') ? 'selected' : '' }}>Cep / sabit telefon (dış hat)</option>
      </select>

      <div class="satir">
        <div>
          <label id="numLabel">Dahili numara</label>
          <input type="text" name="numara" id="numara" value="{{ $ay->numara ?? '' }}" placeholder="101" oninput="guncelle()">
        </div>
        <div style="max-width:150px">
          <label>Teknoloji</label>
          <select name="teknoloji" id="teknoloji" onchange="guncelle()">
            <option value="SIP" {{ (!$ay || $ay->teknoloji=='SIP') ? 'selected' : '' }}>SIP</option>
            <option value="PJSIP" {{ ($ay && $ay->teknoloji=='PJSIP') ? 'selected' : '' }}>PJSIP</option>
          </select>
        </div>
      </div>
      <div class="hint" id="numHint">Santral içi telefonun dahili numarası. Teknolojiyi bilmiyorsanız SIP bırakın.</div>

      <div id="trunkAlan" class="gizli">
        <label>Dış hat (trunk) adı <span style="color:var(--sub);font-weight:400">— opsiyonel</span></label>
        <input type="text" name="trunk" id="trunk" value="{{ $ay->trunk ?? '' }}" placeholder="ör. santrunk (Asterisk'teki dış hat adı)" oninput="guncelle()">
        <div class="hint">Cep/sabit numaraya yönlendirme için Asterisk'teki dış hat (trunk) adı. Boş bırakırsanız numara doğrudan aranır.</div>
      </div>

      <label>Zil süresi (saniye)</label>
      <input type="number" name="zil_sure" value="{{ $ay->zil_sure ?? 30 }}" min="5" max="120" style="max-width:150px">
      <div class="hint">Bu süre içinde açılmazsa çağrı kapanır.</div>

      <label style="margin-top:16px">Oluşan arama hedefi (Asterisk'e giden)</label>
      <div class="onizle" id="onizle">{{ $dial ?: '—' }}</div>
    </div>

    <button class="kaydet" type="submit">Kaydet</button>
  </form>

  <div class="card" style="margin-top:16px">
    <h2>Asterisk kurulumu</h2>
    <div class="hint">
      ✅ Aktarma için Asterisk'e <b>hiçbir şey yazmanıza gerek yok.</b> Köprü, buraya girdiğiniz hedefi
      okuyup çağrıyı kendisi arar ve iki hattı birleştirir (ARI). Hedefi değiştirmek için sadece bu sayfayı
      kullanın; her restoranda ayrı ayar yok.<br><br>
      Tek gereken (restoran başına bir kez), gelen çağrının AI'ya ulaşması için hattın şu satırla Stasis'e
      bağlanması:<br>
      <div class="onizle" style="color:#334155">exten =&gt; s,1,Answer()<br> same =&gt; n,Stasis(restaurant-santral-ai)<br> same =&gt; n,Hangup()</div>
    </div>
  </div>
</div>

<script>
  function guncelle(){
    const tip = document.getElementById('hedef_tip').value;
    const tek = document.getElementById('teknoloji').value;
    const num = document.getElementById('numara').value.trim();
    const trunk = document.getElementById('trunk').value.trim();
    document.getElementById('trunkAlan').className = (tip==='dis') ? '' : 'gizli';
    document.getElementById('numLabel').textContent = (tip==='dis') ? 'Cep / sabit numara' : 'Dahili numara';
    document.getElementById('numara').placeholder = (tip==='dis') ? '05xxxxxxxxx' : '101';
    document.getElementById('numHint').textContent = (tip==='dis')
      ? 'Çağrının yönlendirileceği cep veya sabit telefon numarası.'
      : 'Santral içi telefonun dahili numarası. Teknolojiyi bilmiyorsanız SIP bırakın.';
    let dial = '—';
    if(num){ dial = (tip==='dis' && trunk) ? `${tek}/${trunk}/${num}` : `${tek}/${num}`; }
    document.getElementById('onizle').textContent = dial;
  }
  guncelle();
</script>
</body>
</html>

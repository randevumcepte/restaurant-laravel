<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FreePBX API — Bağlantı Ayarı</title>
<style>
  :root{--bg:#f1f5f9;--card:#fff;--line:#e2e8f0;--ink:#0f172a;--sub:#64748b;--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444}
  *{box-sizing:border-box;margin:0;padding:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif}
  body{background:var(--bg);color:var(--ink);min-height:100vh;padding:24px}
  .wrap{max-width:600px;margin:0 auto}
  .bas{display:flex;align-items:center;gap:12px;margin-bottom:18px}
  .bas .ic{width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--indigo),var(--indigo2));display:flex;align-items:center;justify-content:center;font-size:22px}
  .bas h1{font-size:20px}.bas p{color:var(--sub);font-size:13px;margin-top:2px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}
  label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
  input[type=text],input[type=password]{width:100%;border:1px solid var(--line);border-radius:10px;padding:12px;font-size:15px;outline:none;background:#f8fafc}
  input:focus{border-color:var(--indigo);background:#fff}
  .hint{font-size:12px;color:var(--sub);margin-top:5px;line-height:1.5}
  .switch{display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px}
  .switch input{width:44px;height:26px;appearance:none;background:#cbd5e1;border-radius:20px;position:relative;cursor:pointer;transition:.2s;flex:none}
  .switch input:checked{background:var(--ok)}
  .switch input::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.2s}
  .switch input:checked::after{left:21px}
  .btnrow{display:flex;gap:10px;margin-top:8px}
  .kaydet{flex:1;border:none;border-radius:12px;padding:14px;font-size:16px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2));cursor:pointer}
  .test{border:1px solid var(--indigo);color:var(--indigo);background:#eef2ff;border-radius:12px;padding:14px 18px;font-weight:700;cursor:pointer}
  .basari{background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600}
  #sonuc{margin-top:12px;font-weight:600;font-size:14px}
  .link{display:inline-block;margin-top:8px;color:var(--indigo);font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
  <div class="bas">
    <div class="ic">🔌</div>
    <div><h1>FreePBX API — Bağlantı</h1><p>Dahilileri panelden yönetmek için FreePBX'in kendi API'sine bağlanır. FreePBX bozulmaz.</p></div>
  </div>

  @if(request('kaydedildi'))<div class="basari">✅ Kaydedildi.</div>@endif

  <form method="post" action="/freepbx-ayar-kaydet">
    @csrf
    <div class="card">
      <label class="switch">
        <input type="checkbox" name="aktif" value="1" {{ ($ay && $ay->aktif) ? 'checked' : '' }}>
        FreePBX API aktif
      </label>

      <label>FreePBX admin adresi (base URL)</label>
      <input type="text" name="base_url" value="{{ $ay->base_url ?? '' }}" placeholder="https://pbx.senindomain.com veya https://SUNUCU_IP">
      <div class="hint">Sonunda / olmasın. Örn: https://192.168.1.10</div>

      <label>Client ID</label>
      <input type="text" name="client_id" value="{{ $ay->client_id ?? '' }}" placeholder="Admin → API → Applications">

      <label>Client Secret</label>
      <input type="password" name="client_secret" value="{{ $ay->client_secret ?? '' }}" placeholder="••••••••">
      <div class="hint">FreePBX Admin → API → Applications altında oluşturduğunuz uygulamanın bilgileri.</div>

      <div class="btnrow">
        <button class="kaydet" type="submit">Kaydet</button>
        <button class="test" type="button" onclick="test()">Bağlantıyı test et</button>
      </div>
      <div id="sonuc"></div>
      <a class="link" href="/dahili-yonetim">→ Dahili Yönetimi'ne git</a>
    </div>
  </form>

  <div class="card">
    <div class="hint">
      <b>Hazırlık (FreePBX'te bir kez):</b><br>
      1) Admin → Module Admin → <b>API</b> modülü kurulu/etkin olsun.<br>
      2) Admin → <b>API</b> → Applications → yeni uygulama oluştur → Client ID + Secret al.<br>
      3) Uygulamaya dahili (Core/Extensions) okuma-yazma + GraphQL izni ver.<br>
      4) Yukarıya gir, "Bağlantıyı test et" ile doğrula.
    </div>
  </div>
</div>
<script>
  async function test(){
    const s=document.getElementById('sonuc'); s.textContent='Test ediliyor…'; s.style.color='#64748b';
    try{
      const r=await fetch('/api/freepbx/test',{method:'POST'});
      const d=await r.json();
      if(d.ok){ s.textContent='✅ '+(d.mesaj||'Bağlantı başarılı'); s.style.color='#16a34a'; }
      else { s.textContent='❌ '+(d.hata||'Bağlantı başarısız'); s.style.color='#ef4444'; }
    }catch(e){ s.textContent='❌ İstek hatası: '+e.message; s.style.color='#ef4444'; }
  }
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>İş Başvurusu — {{ $sube->ad }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
<style>
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:linear-gradient(135deg,#7C3AED,#4F46E5);min-height:100vh;color:#1e2235;padding:20px}
  .wrap{max-width:560px;margin:30px auto}
  .head{text-align:center;color:#fff;margin-bottom:22px}
  .head .logo{font-size:30px;font-weight:800;margin-bottom:4px}
  .head .s{font-size:16px;font-weight:700;opacity:.95}
  .head .t{font-size:13px;opacity:.85;margin-top:6px}
  .card{background:#fff;border-radius:20px;box-shadow:0 24px 70px rgba(0,0,0,.25);padding:26px 24px}
  label{display:block;font-size:12.5px;font-weight:700;color:#5b6178;margin:14px 0 5px}
  input,textarea,select{width:100%;padding:12px 14px;border:1.5px solid #e4e6ef;border-radius:12px;font-size:14.5px;font-family:inherit;color:#1e2235;background:#fafbff}
  input:focus,textarea:focus,select:focus{outline:none;border-color:#7C3AED}
  textarea{min-height:80px;resize:vertical}
  .hp{position:absolute;left:-9999px;top:-9999px}
  .btn{width:100%;margin-top:20px;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;font-size:15.5px;font-weight:800;cursor:pointer}
  .btn:active{transform:translateY(1px)}
  .flash{border-radius:12px;padding:14px 16px;margin-bottom:16px;font-size:14px;font-weight:600}
  .flash.ok{background:#dcfce7;color:#166534}
  .flash.err{background:#fee2e2;color:#991b1b}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .foot{text-align:center;color:#fff;opacity:.75;font-size:11.5px;margin-top:18px}
  .ik{font-size:13px;color:#9096a8;margin-top:4px}
  @media(max-width:480px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <div class="head">
    <div class="logo">🍔 {{ $sube->ad }}</div>
    <div class="s">Ekibimize Katıl</div>
    <div class="t">Aşağıdaki formu doldur, en kısa sürede dönüş yapalım.</div>
  </div>
  <div class="card">
    @if (session('ok'))   <div class="flash ok">✓ {{ session('ok') }}</div> @endif
    @if (session('hata')) <div class="flash err">{{ session('hata') }}</div> @endif

    <form method="POST" action="/kariyer/{{ $sube->id }}/gonder" enctype="multipart/form-data">
      @csrf
      {{-- honeypot (bot tuzağı) --}}
      <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">

      <label>Ad Soyad *</label>
      <input type="text" name="ad" required placeholder="Adınız ve soyadınız">

      <div class="row">
        <div><label>Telefon</label><input type="tel" name="telefon" inputmode="tel" placeholder="05.."></div>
        <div><label>E-posta</label><input type="email" name="email" placeholder="ornek@mail.com"></div>
      </div>

      <label>Başvurduğun pozisyon</label>
      <input type="text" name="pozisyon" list="pozL" placeholder="ör. Garson">
      <datalist id="pozL"><option value="Garson"><option value="Komi"><option value="Mutfak / Aşçı"><option value="Kasa"><option value="Bar"><option value="Host / Karşılama"><option value="Bulaşık"><option value="Temizlik"></datalist>

      <label>Kendinden kısaca bahset / deneyim</label>
      <textarea name="notlar" placeholder="Önceki iş deneyimlerin, müsait olduğun saatler, vb."></textarea>

      <label>CV (opsiyonel — PDF/resim, en çok 8MB)</label>
      <input type="file" name="cv" accept=".pdf,.doc,.docx,image/*">

      <button type="submit" class="btn">Başvuruyu Gönder</button>
      <div class="ik" style="text-align:center;margin-top:12px">Bilgilerin yalnızca işe alım sürecinde kullanılır.</div>
    </form>
  </div>
  <div class="foot">ResteOS ile güçlendirilmiştir</div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giriş · ResteOS Sistem Yönetimi</title>
  <link rel="stylesheet" href="{{ asset('css/resteos-yonetim.css') }}?v=3">
</head>
<body>
<div class="ry-login">
  <form class="ry-login-card" method="post" action="/resteos-yonetim/giris">
    @csrf
    <div class="ry-logo-big">🍽️</div>
    <h2>ResteOS Yönetimi</h2>
    <p class="alt">Sistem yönetim paneline giriş</p>
    @if(session('hata'))<div class="ry-alert hata">⚠️ {{ session('hata') }}</div>@endif
    <div class="ry-field">
      <label>E-posta</label>
      <input class="ry-input" type="email" name="email" required autofocus placeholder="admin@resteos.com">
    </div>
    <div class="ry-field">
      <label>Şifre</label>
      <input class="ry-input" type="password" name="sifre" required placeholder="••••••••">
    </div>
    <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Giriş Yap →</button>
  </form>
</div>
</body>
</html>

@extends('resteos_yonetim.layout')
@section('baslik','Profil')
@section('content')
<div class="ry-card" style="max-width:520px">
  <h3>⚙️ Profil Bilgileri</h3>
  <form method="post" action="/resteos-yonetim/profil">@csrf
    <div class="ry-field"><label>Ad Soyad</label><input class="ry-input" name="ad" value="{{ $admin->ad }}"></div>
    <div class="ry-field"><label>E-posta</label><input class="ry-input" type="email" name="email" value="{{ $admin->email }}"></div>
    <div class="ry-field"><label>Rol</label><input class="ry-input" value="{{ $admin->rol }}" disabled></div>
    <hr class="ry-hr">
    <p class="ry-mut" style="font-weight:700">Şifre Değiştir (opsiyonel)</p>
    <div class="ry-field"><label>Mevcut Şifre</label><input class="ry-input" type="password" name="eski_sifre"></div>
    <div class="ry-field"><label>Yeni Şifre</label><input class="ry-input" type="password" name="sifre"></div>
    <button class="ry-btn ry-btn-primary" type="submit">Kaydet</button>
  </form>
</div>
@endsection

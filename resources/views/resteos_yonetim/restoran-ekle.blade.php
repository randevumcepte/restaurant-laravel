@extends('resteos_yonetim.layout')
@section('baslik','Restoran Ekle')
@section('content')
<div class="ry-card" style="max-width:640px">
  <h3>➕ Yeni Restoran (Demo)</h3>
  <form method="post" action="/resteos-yonetim/restoran-ekle">@csrf
    <div class="ry-field"><label>Restoran Adı *</label><input class="ry-input" name="ad" required></div>
    <div class="ry-row">
      <div class="ry-field"><label>Yetkili Ad</label><input class="ry-input" name="yetkili_ad"></div>
      <div class="ry-field"><label>Yetkili Tel</label><input class="ry-input" name="yetkili_tel"></div>
    </div>
    <div class="ry-row">
      <div class="ry-field"><label>Telefon</label><input class="ry-input" name="telefon"></div>
      <div class="ry-field"><label>Şehir</label><input class="ry-input" name="sehir"></div>
    </div>
    <div class="ry-field"><label>Adres</label><input class="ry-input" name="adres"></div>
    <div class="ry-field"><label>Demo Süresi (gün)</label>
      <select class="ry-select" name="demo_gun"><option value="7">7 gün</option><option value="14" selected>14 gün</option><option value="30">30 gün</option></select>
    </div>
    <button class="ry-btn ry-btn-primary" type="submit">Restoran Oluştur</button>
  </form>
</div>
@endsection

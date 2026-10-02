@extends('resteos_yonetim.layout')
@section('baslik','Sistem Sağlık')
@section('content')
<div class="ry-card" style="max-width:640px">
  <h3>💚 Sistem Durumu</h3>
  <table class="ry-table">
    <tbody>
      <tr><td class="ry-mut">Veritabanı</td><td class="ry-restoran">{{ $bilgi['db'] }}</td></tr>
      <tr><td class="ry-mut">PHP Sürümü</td><td class="ry-restoran">{{ $bilgi['php'] }}</td></tr>
      <tr><td class="ry-mut">Laravel Sürümü</td><td class="ry-restoran">{{ $bilgi['laravel'] }}</td></tr>
      <tr><td class="ry-mut">Toplam Restoran</td><td class="ry-restoran">{{ $bilgi['restoran'] }}</td></tr>
      <tr><td class="ry-mut">Toplam Adisyon</td><td class="ry-restoran">{{ number_format($bilgi['adisyon'],0,',','.') }}</td></tr>
      <tr><td class="ry-mut">Toplam Ürün</td><td class="ry-restoran">{{ number_format($bilgi['urun'],0,',','.') }}</td></tr>
      <tr><td class="ry-mut">Disk</td><td class="ry-restoran">{{ $bilgi['disk'] }}</td></tr>
      <tr><td class="ry-mut">Sunucu Saati</td><td class="ry-restoran">{{ $bilgi['zaman'] }}</td></tr>
    </tbody>
  </table>
</div>
@endsection

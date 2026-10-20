@extends('layout.app')
@section('title', 'QR Menü Rengi')
@section('rk_ikon', 'color_lens')
@section('baslik', 'QR Menü Rengi')
@section('rk_alt', 'Restoranının QR menü temasını seç — anında kaydedilir')

@section('content')
<div style="padding:0">
  <iframe src="/tema" title="QR Menü Rengi" loading="eager"
    style="width:100%;height:calc(100vh - 150px);min-height:560px;border:none;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.14);background:#0b090c;display:block"></iframe>
</div>
@endsection

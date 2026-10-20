@extends('layout.app')
@section('title', 'Yetkisiz')
@section('rk_ikon', 'lock')
@section('baslik', 'Erişim Yok')
@section('content')
<div style="max-width:460px;margin:40px auto 0;text-align:center">
  <div class="rk-card" style="padding:38px 24px">
    <span class="material-icons-outlined" style="font-size:56px;color:var(--kirmizi)">lock</span>
    <div style="font-size:19px;font-weight:800;color:var(--ink);margin-top:12px">Bu sayfaya yetkiniz yok</div>
    <div class="rk-sub" style="font-size:13.5px;margin-top:8px;line-height:1.4">Bu bölümü görüntüleme yetkiniz bulunmuyor. Gerekiyorsa işletme sahibinden yetki iste.</div>
    <a href="/pos" class="rk-btn lg" style="background:var(--mor1);max-width:220px;margin:20px auto 0">
      <span class="material-icons-outlined" style="color:#fff;font-size:18px">arrow_back</span> Ana ekrana dön
    </a>
  </div>
</div>
@endsection

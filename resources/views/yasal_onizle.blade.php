@extends('layout.app')
@section('title', $belge['baslik'])
@section('rk_ikon', 'description')
@section('baslik', $belge['baslik'])
@section('rk_alt', 'Önizleme — müşteri uygulamasında böyle görünür')

@section('content')
<div style="max-width:720px">
  <a href="/yasal-bilgiler" class="rk-btn no-print" style="background:var(--card2);color:var(--ink);margin-bottom:14px">
    <span class="material-icons-outlined">arrow_back</span><span>Yasal Bilgiler'e dön</span>
  </a>
  @if (!$belge['tam'])
    <div class="rk-flash err">Bazı zorunlu alanlar boş — metinde [köşeli parantezli] uyarılar görünüyor. Yasal Bilgiler'den tamamlayın.</div>
  @endif
  <div class="rk-card">
    <div style="font-size:11px;color:var(--sub);margin-bottom:10px">Son güncelleme: {{ $belge['guncelleme'] }}</div>
    <pre style="white-space:pre-wrap;word-wrap:break-word;font-family:inherit;font-size:13.5px;line-height:1.6;color:var(--ink);margin:0">{{ $belge['metin'] }}</pre>
  </div>
</div>
@endsection

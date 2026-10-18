@extends('layout.app')
@section('title', 'İşletme Konumu')
@section('rk_ikon', 'my_location')
@section('baslik', 'İşletme Konumu')
@section('rk_alt', 'Mesai geofence — personel bu noktaya yakınken QR okutabilir')

@section('content')
@php
  $lat = $sube->mesai_lat ?? null;
  $lng = $sube->mesai_lng ?? null;
  $yaricap = (int) ($sube->mesai_yaricap ?? 150);
  $var = $lat !== null && $lng !== null;
@endphp
<div style="max-width:560px">

  @if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

  {{-- Durum kartı --}}
  <div class="rk-card" style="display:flex;align-items:center;gap:12px">
    <span class="material-icons-outlined" style="font-size:30px;color:{{ $var ? '#16A34A' : 'var(--sub)' }}">{{ $var ? 'check_circle' : 'location_off' }}</span>
    <div>
      <div style="font-size:15px;font-weight:800;color:var(--ink)">{{ $var ? 'Konum ayarlı' : 'Konum henüz ayarlanmadı' }}</div>
      <div class="rk-sub" style="font-size:12px;margin-top:2px">{{ $var ? 'Yarıçap: '.$yaricap.' m' : 'Ayarlanmadan geofence çalışmaz (sadece 20 sn QR)' }}</div>
      @if ($var)<div class="rk-sub" style="font-size:11px">{{ number_format($lat,5) }}, {{ number_format($lng,5) }}</div>@endif
    </div>
  </div>

  <form method="post" action="/isletme-konum/kaydet" id="konumForm">
    @csrf
    <input type="hidden" name="lat" id="f_lat" value="{{ $lat }}">
    <input type="hidden" name="lng" id="f_lng" value="{{ $lng }}">

    <div style="font-size:14px;font-weight:700;color:var(--ink);margin-top:18px">Yarıçap (izin verilen mesafe)</div>
    <div class="rk-sub" style="font-size:12px">Personel bu mesafe içindeyse QR okutabilir. İç mekân GPS sapmasına karşı 150 m önerilir.</div>
    <div style="display:flex;align-items:center;gap:14px;margin-top:8px">
      <input type="range" name="yaricap" id="f_yaricap" min="100" max="300" step="25" value="{{ $yaricap }}" style="flex:1;accent-color:var(--mor1)" oninput="document.getElementById('yrLbl').textContent=this.value+' m'" onchange="document.getElementById('konumForm').submit()">
      <span id="yrLbl" style="width:56px;font-weight:700;color:var(--ink)">{{ $yaricap }} m</span>
    </div>

    <button type="button" class="rk-btn lg" style="background:var(--mor1);margin-top:14px" onclick="konumAl()" id="konumBtn">
      <span class="material-icons-outlined" style="color:#fff">my_location</span>
      <span id="konumBtnYazi">{{ $var ? 'Konumu Güncelle (buradayım)' : 'Şu Anki Konumu Kullan' }}</span>
    </button>
    <div id="konumMesaj" class="rk-sub2" style="text-align:center;font-size:13px;margin-top:12px"></div>
  </form>

  {{-- Bilgi kutusu --}}
  <div style="background:var(--card2);border-radius:12px;padding:12px;margin-top:16px;display:flex;gap:10px;align-items:flex-start">
    <span class="material-icons-outlined" style="font-size:18px;color:var(--sub)">info</span>
    <div class="rk-sub" style="font-size:12px;line-height:1.35">Bu ekranı <b>İŞLETMEDEYKEN</b> aç ve "Şu Anki Konumu Kullan"a bas. Cihazın konumu işletme noktası olarak kaydedilir; adres/harita girmene gerek yok. (Kasa Mesai QR ekranı açılınca da otomatik ayarlar.)</div>
  </div>
</div>

<script>
function konumAl(){
  var btn=document.getElementById('konumBtn'), msg=document.getElementById('konumMesaj');
  if(!navigator.geolocation){ msg.textContent='Bu tarayıcı konumu desteklemiyor.'; return; }
  btn.disabled=true; msg.textContent='Konum alınıyor…';
  navigator.geolocation.getCurrentPosition(function(pos){
    document.getElementById('f_lat').value=pos.coords.latitude;
    document.getElementById('f_lng').value=pos.coords.longitude;
    msg.textContent='Konum alındı, kaydediliyor…';
    document.getElementById('konumForm').submit();
  }, function(err){
    btn.disabled=false;
    msg.textContent = err.code===1 ? 'Konum izni verilmedi.' : 'Konum alınamadı (kapalı alanda sinyal zayıf olabilir).';
  }, {enableHighAccuracy:true, timeout:10000});
}
</script>
@endsection

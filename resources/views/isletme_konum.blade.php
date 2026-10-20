@extends('layout.app')
@section('title', 'İşletme Konumu')
@section('rk_ikon', 'my_location')
@section('baslik', 'İşletme Konumu')
@section('rk_alt', 'Mesai geofence — personel bu noktaya yakınken QR okutabilir')

@push('head')
<style>
/* ───── kök değişkenler (Flutter TemaProvider ile birebir) ───── */
#ikroot{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;
  --yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;
  --ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;
  --line:#e2e8f0;
  max-width:560px;
}
body.koyu #ikroot{
  --card:#161d2e;--card2:#1b2235;
  --ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;
  --line:#2c3650;
}

/* ── Durum kartı (Flutter Container+BoxDecoration boxShadow) ── */
#ikroot .ik-durum{
  display:flex;align-items:center;gap:12px;
  background:var(--card);border-radius:14px;padding:14px;
  box-shadow:0 4px 12px rgba(0,0,0,.08);
}
body.koyu #ikroot .ik-durum{box-shadow:0 4px 12px rgba(0,0,0,.28);}
#ikroot .ik-durum .ikon{font-size:30px;flex:none}
#ikroot .ik-durum .b{font-size:15px;font-weight:800;color:var(--ink)}
#ikroot .ik-durum .s{font-size:12px;color:var(--sub);margin-top:2px}
#ikroot .ik-durum .koord{font-size:11px;color:var(--sub);margin-top:1px}

/* ── Bölüm başlığı ── */
#ikroot .ik-baslik{font-size:14px;font-weight:700;color:var(--ink);margin-top:18px}
#ikroot .ik-acik{font-size:12px;color:var(--sub);margin-top:3px}

/* ── Yarıçap slider satırı ── */
#ikroot .ik-slider-row{display:flex;align-items:center;gap:14px;margin-top:8px}
#ikroot .ik-slider-row input[type=range]{
  flex:1;-webkit-appearance:none;appearance:none;height:5px;
  border-radius:6px;outline:none;cursor:pointer;
  background:linear-gradient(to right, var(--mor1) 0%, var(--mor1) var(--pct,33.3%), var(--line) var(--pct,33.3%), var(--line) 100%);
}
#ikroot .ik-slider-row input[type=range]::-webkit-slider-thumb{
  -webkit-appearance:none;width:22px;height:22px;border-radius:50%;
  background:var(--mor1);box-shadow:0 2px 6px rgba(124,58,237,.5);cursor:pointer;
}
#ikroot .ik-slider-row input[type=range]::-moz-range-thumb{
  width:22px;height:22px;border-radius:50%;border:none;
  background:var(--mor1);box-shadow:0 2px 6px rgba(124,58,237,.5);cursor:pointer;
}
#ikroot .ik-yr-lbl{width:56px;font-weight:700;color:var(--ink);font-size:14px;text-align:right;flex:none}

/* ── Ana buton (Flutter ElevatedButton.icon mor1) ── */
#ikroot .ik-btn{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;height:52px;border:0;border-radius:14px;
  background:var(--mor1);color:#fff;
  font-size:15px;font-weight:700;cursor:pointer;
  margin-top:14px;transition:opacity .15s;
}
#ikroot .ik-btn:disabled{opacity:.55;cursor:not-allowed}
#ikroot .ik-btn .material-icons-outlined{font-size:20px;color:#fff}

/* ── Mesaj satırı (Flutter mesaj?) ── */
#ikroot .ik-mesaj{
  text-align:center;font-size:13px;color:var(--sub2);
  margin-top:12px;min-height:18px;
}

/* ── Bilgi kutusu (Flutter card2 Container) ── */
#ikroot .ik-info{
  display:flex;align-items:flex-start;gap:10px;
  background:var(--card2);border-radius:12px;padding:12px;margin-top:16px;
}
#ikroot .ik-info .material-icons-outlined{font-size:18px;color:var(--sub);flex:none;margin-top:1px}
#ikroot .ik-info .t{font-size:12px;color:var(--sub);line-height:1.35}
#ikroot .ik-info b{color:var(--ink)}

/* ── Harita alanı (Leaflet — sadece konum ayarlıysa gösterilir) ── */
#ikroot #ik-harita-kap{
  margin-top:16px;border-radius:14px;overflow:hidden;
  border:1px solid var(--line);
  display:none; /* JS ile açılır */
}
#ikroot #ik-harita{height:240px;width:100%}

/* ── Flash bildirimleri ── */
#ikroot .rk-flash{
  padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;
}
#ikroot .rk-flash.ok{background:rgba(16,185,129,.12);color:#065f46;border:1px solid rgba(16,185,129,.3)}
#ikroot .rk-flash.err{background:rgba(244,63,94,.10);color:#9f1239;border:1px solid rgba(244,63,94,.3)}
body.koyu #ikroot .rk-flash.ok{color:#6ee7b7}
body.koyu #ikroot .rk-flash.err{color:#fda4af}
</style>
@endpush

@section('content')
@php
  $lat    = $sube->mesai_lat    ?? null;
  $lng    = $sube->mesai_lng    ?? null;
  $yaricap = (int) ($sube->mesai_yaricap ?? 150);
  $var    = $lat !== null && $lng !== null;
  /* slider yüzde: (val-min)/(max-min)*100 → (yaricap-100)/200*100 */
  $pct = round(($yaricap - 100) / 200 * 100, 1);
@endphp

<div id="ikroot">

  {{-- Flash bildirimleri --}}
  @if (session('ok'))
    <div class="rk-flash ok">{{ session('ok') }}</div>
  @endif
  @if (session('hata'))
    <div class="rk-flash err">{{ session('hata') }}</div>
  @endif

  {{-- ── Durum kartı (Flutter Container+Row) ── --}}
  <div class="ik-durum">
    <span class="material-icons-outlined ikon"
          style="color:{{ $var ? '#16A34A' : 'var(--sub)' }}">{{ $var ? 'check_circle' : 'location_off' }}</span>
    <div style="flex:1;min-width:0">
      <div class="b">{{ $var ? 'Konum ayarlı' : 'Konum henüz ayarlanmadı' }}</div>
      <div class="s">{{ $var ? 'Yarıçap: '.$yaricap.' m' : 'Ayarlanmadan geofence çalışmaz (sadece 20 sn QR)' }}</div>
      @if ($var)
        <div class="koord">{{ number_format((float)$lat, 5, '.', '') }}, {{ number_format((float)$lng, 5, '.', '') }}</div>
      @endif
    </div>
  </div>

  {{-- ── Form ── --}}
  <form method="post" action="/isletme-konum/kaydet" id="konumForm">
    @csrf
    <input type="hidden" name="lat"  id="f_lat"  value="{{ $lat }}">
    <input type="hidden" name="lng"  id="f_lng"  value="{{ $lng }}">

    {{-- Yarıçap başlığı --}}
    <div class="ik-baslik">Yarıçap (izin verilen mesafe)</div>
    <div class="ik-acik">Personel bu mesafe içindeyse QR okutabilir. İç mekân GPS sapmasına karşı 150 m önerilir.</div>

    {{-- Slider + etiket (Flutter Slider + Text) --}}
    <div class="ik-slider-row">
      <input type="range" name="yaricap" id="f_yaricap"
             min="100" max="300" step="25" value="{{ $yaricap }}"
             style="--pct:{{ $pct }}%"
             oninput="ikSlider(this)"
             onchange="konumForm.submit()">
      <span id="yrLbl" class="ik-yr-lbl">{{ $yaricap }} m</span>
    </div>

    {{-- Ana buton (Flutter ElevatedButton.icon) --}}
    <button type="button" class="ik-btn" id="konumBtn" onclick="konumAl()">
      <span class="material-icons-outlined">my_location</span>
      <span id="konumBtnYazi">{{ $var ? 'Konumu Güncelle (buradayım)' : 'Şu Anki Konumu Kullan' }}</span>
    </button>

    {{-- Mesaj (Flutter mesaj? Text) --}}
    <div class="ik-mesaj" id="konumMesaj"></div>

  </form>

  {{-- ── Harita alanı (Leaflet / OSM — web'e özgü ek, Flutter'da yok) ── --}}
  @if ($var)
  <div id="ik-harita-kap">
    <div id="ik-harita"></div>
  </div>
  @endif

  {{-- ── Bilgi kutusu (Flutter card2 Container + info_outline) ── --}}
  <div class="ik-info">
    <span class="material-icons-outlined">info_outline</span>
    <div class="t">Bu ekranı <b>İŞLETMEDEYKEN</b> aç ve "Şu Anki Konumu Kullan"a bas.
      Tarayıcının konumu işletme noktası olarak kaydedilir; adres/harita girmene gerek yok.
      (Kasa Mesai QR ekranı açılınca da otomatik ayarlar.)</div>
  </div>

</div>{{-- #ikroot --}}

{{-- ── Leaflet CDN (sadece konum varsa yükle) ── --}}
@if ($var)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV/XN/WPaI=" crossorigin=""></script>
@endif

<script>
/* ── Slider: iz rengini canlı güncelle ── */
function ikSlider(el){
  var v = +el.value, min = +el.min, max = +el.max;
  var pct = ((v - min) / (max - min) * 100).toFixed(1) + '%';
  el.style.setProperty('--pct', pct);
  document.getElementById('yrLbl').textContent = v + ' m';
}

/* ── Konum al (tarayıcı Geolocation API — Flutter Geolocator karşılığı) ── */
function konumAl(){
  var btn = document.getElementById('konumBtn');
  var msg = document.getElementById('konumMesaj');
  var yaz = document.getElementById('konumBtnYazi');
  if (!navigator.geolocation){
    msg.textContent = 'Bu tarayıcı konumu desteklemiyor.';
    return;
  }
  btn.disabled = true;
  yaz.textContent = 'Konum alınıyor…';
  msg.textContent = 'Konum alınıyor…';

  navigator.geolocation.getCurrentPosition(
    function(pos){
      document.getElementById('f_lat').value = pos.coords.latitude;
      document.getElementById('f_lng').value = pos.coords.longitude;
      msg.textContent = 'Konum alındı, kaydediliyor…';
      document.getElementById('konumForm').submit();
    },
    function(err){
      btn.disabled = false;
      yaz.textContent = '{{ $var ? "Konumu Güncelle (buradayım)" : "Şu Anki Konumu Kullan" }}';
      if (err.code === 1)
        msg.textContent = 'Konum izni verilmedi.';
      else if (err.code === 2)
        msg.textContent = 'Konum alınamadı (kapalı alanda sinyal zayıf olabilir).';
      else
        msg.textContent = 'Konum alınamadı (zaman aşımı).';
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
}

@if ($var)
/* ── Leaflet harita başlat ── */
(function(){
  var lat = {{ (float)$lat }}, lng = {{ (float)$lng }}, yr = {{ $yaricap }};
  var kap = document.getElementById('ik-harita-kap');
  if (!kap) return;
  kap.style.display = 'block';

  var map = L.map('ik-harita', { zoomControl: true, scrollWheelZoom: false }).setView([lat, lng], 16);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(map);

  /* Merkez pin */
  var pinIkon = L.divIcon({
    className: '',
    html: '<span class="material-icons-outlined" style="font-size:32px;color:#7C3AED;filter:drop-shadow(0 2px 4px rgba(0,0,0,.4))">location_on</span>',
    iconSize: [32, 32], iconAnchor: [16, 32]
  });
  L.marker([lat, lng], { icon: pinIkon }).addTo(map);

  /* Geofence dairesi */
  L.circle([lat, lng], {
    radius: yr,
    color: '#7C3AED', fillColor: '#7C3AED', fillOpacity: 0.12, weight: 2
  }).addTo(map);
})();
@endif
</script>
@endsection

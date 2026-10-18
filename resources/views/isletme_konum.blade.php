@extends('layout.app')
@section('title', 'İşletme Konum')
@section('baslik', '📍 İşletme Konum')

@section('content')
@php
  $lat = isset($sube->mesai_lat) && $sube->mesai_lat ? (float) $sube->mesai_lat : null;
  $lng = isset($sube->mesai_lng) && $sube->mesai_lng ? (float) $sube->mesai_lng : null;
  $yaricap = (int) ($sube->mesai_yaricap ?? 150);
  if ($yaricap < 100) $yaricap = 100; if ($yaricap > 300) $yaricap = 300;
  $adres = isset($sube->adres) ? (string) $sube->adres : '';
  $ayarli = $lat !== null && $lng !== null;
  // Harita başlangıç merkezi: kayıtlı konum yoksa Türkiye geneli (Ankara civarı)
  $cLat = $ayarli ? $lat : 39.925;
  $cLng = $ayarli ? $lng : 32.866;
@endphp

@if (session('ok'))
  <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm font-semibold">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl px-4 py-3 text-sm font-semibold">{{ session('hata') }}</div>
@endif

<p class="text-sm text-slate-500 mb-5 max-w-3xl">
  İşletmenin GPS noktası personel mesai QR'ında geofence merkezi olarak kullanılır. Personel bu noktaya
  seçtiğin yarıçap içindeyse QR okutabilir, uzaktan giriş engellenir. Harita üzerine tıklayarak veya
  pini sürükleyerek konumu güncelle, ardından <b>Kaydet</b>'e bas.
</p>

<form method="POST" action="/isletme-konum/kaydet">
  @csrf
  <div class="grid gap-5 lg:grid-cols-3">

    <!-- SOL: Durum + alanlar -->
    <div class="space-y-4">
      <div class="bg-white rounded-2xl p-5 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-full flex items-center justify-center text-white {{ $ayarli ? 'bg-emerald-500' : 'bg-slate-300' }}">
            <span class="text-lg">{{ $ayarli ? '✓' : '📍' }}</span>
          </div>
          <div>
            <div class="font-bold text-slate-800">{{ $ayarli ? 'Konum ayarlı' : 'Konum henüz ayarlanmadı' }}</div>
            <div class="text-xs text-slate-400">{{ $ayarli ? 'Yarıçap: ' . $yaricap . ' m' : 'Ayarlanmadan geofence çalışmaz' }}</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Adres</label>
          <textarea name="adres" rows="3"
            class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300"
            placeholder="İşletme açık adresi">{{ $adres }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Enlem (lat)</label>
            <input type="text" name="lat" id="inpLat" value="{{ $ayarli ? $lat : '' }}"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Boylam (lng)</label>
            <input type="text" name="lng" id="inpLng" value="{{ $ayarli ? $lng : '' }}"
              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <label class="text-xs font-semibold text-slate-500">Yarıçap (izin verilen mesafe)</label>
            <span id="yrLabel" class="text-xs font-bold text-indigo-700">{{ $yaricap }} m</span>
          </div>
          <input type="range" name="yaricap" id="inpYaricap" min="100" max="300" step="25" value="{{ $yaricap }}"
            class="w-full accent-indigo-600">
          <div class="text-[11px] text-slate-400 mt-1">İç mekân GPS sapmasına karşı 150 m önerilir.</div>
        </div>

        <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition">
          Kaydet
        </button>
      </div>
    </div>

    <!-- SAĞ: Harita -->
    <div class="lg:col-span-2">
      <div class="bg-white rounded-2xl p-3 shadow-sm">
        <div id="harita" style="height: 460px; width: 100%; border-radius: 14px; overflow: hidden; z-index:0;"></div>
        <div class="text-[11px] text-slate-400 mt-2 px-1">
          Haritaya tıkla veya pini sürükle → enlem/boylam otomatik güncellenir. Kesik çizgili daire geofence sınırıdır.
        </div>
      </div>
    </div>

  </div>
</form>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  var ayarli = {{ $ayarli ? 'true' : 'false' }};
  var cLat = {{ $cLat }}, cLng = {{ $cLng }};
  var yaricap = {{ $yaricap }};

  var map = L.map('harita').setView([cLat, cLng], ayarli ? 17 : 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  var marker = null, cember = null;

  function konumYaz(lat, lng) {
    document.getElementById('inpLat').value = lat.toFixed(6);
    document.getElementById('inpLng').value = lng.toFixed(6);
  }

  function ciz(lat, lng) {
    if (marker) {
      marker.setLatLng([lat, lng]);
    } else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      marker.on('dragend', function (e) {
        var p = e.target.getLatLng();
        konumYaz(p.lat, p.lng);
        cemberGuncelle(p.lat, p.lng);
      });
    }
    cemberGuncelle(lat, lng);
  }

  function cemberGuncelle(lat, lng) {
    var r = parseInt(document.getElementById('inpYaricap').value, 10) || 150;
    if (cember) {
      cember.setLatLng([lat, lng]);
      cember.setRadius(r);
    } else {
      cember = L.circle([lat, lng], { radius: r, color: '#4f46e5', weight: 2, dashArray: '6 6', fillColor: '#6366f1', fillOpacity: 0.12 }).addTo(map);
    }
  }

  if (ayarli) { ciz(cLat, cLng); }

  map.on('click', function (e) {
    konumYaz(e.latlng.lat, e.latlng.lng);
    ciz(e.latlng.lat, e.latlng.lng);
    if (map.getZoom() < 15) map.setView([e.latlng.lat, e.latlng.lng], 16);
  });

  var yr = document.getElementById('inpYaricap');
  yr.addEventListener('input', function () {
    document.getElementById('yrLabel').textContent = yr.value + ' m';
    var la = parseFloat(document.getElementById('inpLat').value);
    var ln = parseFloat(document.getElementById('inpLng').value);
    if (!isNaN(la) && !isNaN(ln)) cemberGuncelle(la, ln);
  });

  // Tailwind/flex içinde harita bazen boyut hesaplayamaz -> ilk render'da düzelt.
  setTimeout(function () { map.invalidateSize(); }, 200);
})();
</script>
@endsection

@extends('layout.app')
@section('title', 'Canlı Kurye Takibi')
@section('rk_ikon', 'two_wheeler')
@section('baslik', 'Canlı Kurye Takibi')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
/* ---- Tema değişkenleri (Flutter TemaProvider birebir) ---- */
#krt{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;
  --kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
}
body.koyu #krt{
  --card:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#111827;
}

/* Layout */
#krt{display:flex;flex-direction:column;gap:0;height:calc(100vh - 120px);min-height:420px}
#krt .ust-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 0 10px;flex-shrink:0}
#krt .ozet-pill{display:inline-flex;align-items:center;gap:6px;background:var(--card);border:1px solid var(--line);border-radius:22px;padding:7px 14px;font-size:13px;font-weight:700;color:var(--ink)}
#krt .ozet-pill .dot{width:9px;height:9px;border-radius:50%;flex-shrink:0}
#krt .son-guncelleme{margin-left:auto;font-size:11.5px;color:var(--sub2);display:flex;align-items:center;gap:5px}

/* Ana içerik: harita + yan liste */
#krt .ana{display:flex;flex:1;min-height:0;gap:12px}
#krt .harita-sarg{flex:1;min-width:0;border-radius:18px;overflow:hidden;border:1px solid var(--line);background:var(--card);position:relative;box-shadow:0 4px 12px rgba(0,0,0,.07)}
#krt #harita{width:100%;height:100%}
#krt .odak-btn{position:absolute;top:10px;right:10px;z-index:400;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:8px 12px;display:flex;align-items:center;gap:6px;font-size:13px;font-weight:700;color:var(--mor1);cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.10)}
#krt .odak-btn:hover{background:var(--line)}
#krt .atif{position:absolute;bottom:6px;left:6px;z-index:400;background:rgba(255,255,255,.82);padding:2px 7px;border-radius:6px;font-size:9.5px;color:#475569}
body.koyu #krt .atif{background:rgba(17,24,39,.72);color:#94a3b8}

/* Yan panel */
#krt .yan{width:308px;flex-shrink:0;display:flex;flex-direction:column;gap:10px;overflow-y:auto;padding-right:2px}
@media(max-width:900px){#krt .yan{width:100%;max-height:280px}}
#krt .bolum-baslik{font-size:11.5px;font-weight:800;color:var(--sub2);letter-spacing:.5px;text-transform:uppercase;margin:2px 0 6px}
#krt .kk{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:11px 12px;cursor:pointer;transition:transform .08s;margin-bottom:7px}
#krt .kk:hover{transform:translateX(2px)}
#krt .kk .av{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex-shrink:0}
#krt .kk .av .material-icons-outlined{color:#fff;font-size:22px}
#krt .kk .inf{flex:1;min-width:0}
#krt .kk .ad{font-size:14px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#krt .kk .du{font-size:11.5px;color:var(--sub);margin-top:1px}
#krt .kk .rz{flex-shrink:0;font-size:11px;font-weight:800;padding:4px 10px;border-radius:20px}
#krt .rz-musait{background:rgba(16,185,129,.14);color:#059669}
#krt .rz-teslimatta{background:rgba(245,158,11,.14);color:#d97706}
#krt .rz-mola{background:rgba(148,163,184,.14);color:#64748b}
body.koyu #krt .rz-musait{background:rgba(16,185,129,.18);color:#34d399}
body.koyu #krt .rz-teslimatta{background:rgba(245,158,11,.18);color:#fcd34d}

/* Teslimat kartı */
#krt .tk{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:11px 12px;margin-bottom:7px}
#krt .tk .tk-ust{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
#krt .tk .plat{font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:20px;background:rgba(124,58,237,.12);color:var(--mor1);text-transform:uppercase}
#krt .tk .tut{font-size:14px;font-weight:800;color:var(--yesil)}
#krt .tk .adr{font-size:13.5px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#krt .tk .mt{font-size:11.5px;color:var(--sub);margin-top:2px}
#krt .tk .kurye-cip{display:inline-flex;align-items:center;gap:4px;margin-top:5px;font-size:11.5px;color:var(--mor1);font-weight:700}

/* Canlı nokta */
#krt .canli-dot{width:8px;height:8px;border-radius:50%;background:var(--yesil);flex-shrink:0;animation:kcpulse 1.8s ease-in-out infinite}
@keyframes kcpulse{0%,100%{opacity:1}50%{opacity:.35}}
#krt .canli-dot.off{background:var(--kirmizi);animation:none}

/* Metrik kartlar */
#krt .metrik-sira{display:flex;gap:10px;flex-shrink:0}
#krt .mk{flex:1;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:11px 14px;display:flex;align-items:center;gap:10px}
#krt .mk .material-icons-outlined{font-size:22px}
#krt .mk .mv{font-size:22px;font-weight:900;color:var(--ink);line-height:1}
#krt .mk .ml{font-size:10.5px;font-weight:700;color:var(--sub);margin-top:1px}

/* Boş durum */
#krt .bos{text-align:center;padding:28px 10px;font-size:13.5px;color:var(--sub)}

/* Leaflet özel ikonlar */
.kurye-ikon-wrap{width:52px;height:52px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:50%;border:3px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,.35)}
.kurye-ikon-wrap .material-icons-outlined{color:#fff;font-size:26px}
.sube-ikon-wrap{width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:50%;border:2.5px solid #64748b;box-shadow:0 3px 7px rgba(0,0,0,.22)}
.sube-ikon-wrap .material-icons-outlined{color:#64748b;font-size:22px}
.hedef-ikon-wrap{width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#fff;border-radius:50%;border:2.5px solid #F43F5E;box-shadow:0 3px 7px rgba(0,0,0,.22)}
.hedef-ikon-wrap .material-icons-outlined{color:#F43F5E;font-size:22px}
</style>
@endpush

@section('content')
<div id="krt">

  {{-- Üst özet bar --}}
  <div class="ust-bar">
    <div class="ozet-pill"><span class="dot" style="background:var(--mor1)"></span><span id="o-kurye">0</span> kurye</div>
    <div class="ozet-pill"><span class="dot" style="background:var(--yesil)"></span><span id="o-musait">0</span> müsait</div>
    <div class="ozet-pill"><span class="dot" style="background:var(--amber)"></span><span id="o-yolda">0</span> yolda</div>
    <div class="son-guncelleme">
      <span class="material-icons-outlined" style="font-size:15px">schedule</span>
      <span id="o-son">–</span>
    </div>
  </div>

  {{-- Ana alan: harita + yan liste --}}
  <div class="ana">

    {{-- Harita --}}
    <div class="harita-sarg">
      <div id="harita"></div>
      <button class="odak-btn" onclick="tumKuryeGor()" title="Tüm kuryeye odaklan">
        <span class="material-icons-outlined" style="font-size:18px">my_location</span> Tümünü Gör
      </button>
      <div class="atif">© OpenStreetMap</div>
    </div>

    {{-- Yan liste: kuryeler + teslimatlar --}}
    <div class="yan" id="yan-panel">

      {{-- Metrik kutuları --}}
      <div class="metrik-sira">
        <div class="mk">
          <span class="material-icons-outlined" style="color:var(--mor1)">two_wheeler</span>
          <div><div class="mv" id="m-kurye">{{ $kuryeler->count() }}</div><div class="ml">Toplam Kurye</div></div>
        </div>
        <div class="mk">
          <span class="material-icons-outlined" style="color:var(--amber)">local_shipping</span>
          <div><div class="mv" id="m-yolda">{{ $teslimatlar->count() }}</div><div class="ml">Aktif Teslimat</div></div>
        </div>
      </div>

      {{-- Kuryeler --}}
      <div>
        <div class="bolum-baslik">Kuryeler</div>
        <div id="liste-kuryeler">
          @forelse ($kuryeler as $k)
            <div class="kk" onclick="odakKurye({{ $k->id }})">
              <div class="av"><span class="material-icons-outlined">two_wheeler</span></div>
              <div class="inf">
                <div class="ad">{{ $k->ad }}</div>
                <div class="du" style="display:flex;align-items:center;gap:5px">
                  <span class="canli-dot {{ ($k->son_lat ?? null) ? '' : 'off' }}"></span>
                  {{ ($k->son_lat ?? null) ? 'Konum var' : 'Konum yok' }}
                  @if($k->telefon) &nbsp;·&nbsp; {{ $k->telefon }} @endif
                </div>
              </div>
              <span class="rz rz-{{ $k->durum ?? 'musait' }}">{{ $k->durum ?? 'müsait' }}</span>
            </div>
          @empty
            <div class="bos">Aktif kurye yok</div>
          @endforelse
        </div>
      </div>

      {{-- Aktif Teslimatlar --}}
      <div>
        <div class="bolum-baslik" style="margin-top:4px">Yoldaki Teslimatlar</div>
        <div id="liste-teslimatlar">
          @forelse ($teslimatlar as $t)
            <div class="tk">
              <div class="tk-ust">
                <span class="plat">paket</span>
                <span class="tut">{{ number_format((float)$t->toplam, 0, ',', '.') }} ₺</span>
              </div>
              <div class="adr">{{ $t->teslimat_adres ?? 'Adres yok' }}</div>
              <div class="mt">#{{ $t->id }} · {{ $t->musteri ?? 'Müşteri' }}</div>
              @if($t->kurye)
                <div class="kurye-cip">
                  <span class="material-icons-outlined" style="font-size:14px">two_wheeler</span>
                  {{ $t->kurye }}
                </div>
              @endif
            </div>
          @empty
            <div class="bos">Yolda teslimat yok</div>
          @endforelse
        </div>
      </div>

    </div>{{-- /yan --}}
  </div>{{-- /ana --}}
</div>{{-- /krt --}}
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  /* ---- Harita kur ---- */
  var rLat  = {{ (isset($sube) && $sube && isset($sube->mesai_lat) && $sube->mesai_lat) ? (float)$sube->mesai_lat : 'null' }};
  var rLng  = {{ (isset($sube) && $sube && isset($sube->mesai_lng) && $sube->mesai_lng) ? (float)$sube->mesai_lng : 'null' }};
  var subAd = @json((isset($sube) && $sube) ? ($sube->ad ?? 'Restoran') : 'Restoran');
  var merkez = (rLat && rLng) ? [rLat, rLng] : [40.9905, 29.0250];

  var map = L.map('harita', { zoomControl: true, attributionControl: false })
             .setView(merkez, 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

  /* ---- İkon fabrikaları (Flutter _pin / _kuryePin birebir) ---- */
  function kuryeIcon() {
    return L.divIcon({
      className: '',
      html: '<div class="kurye-ikon-wrap"><span class="material-icons-outlined">two_wheeler</span></div>',
      iconSize: [52, 52], iconAnchor: [26, 52], popupAnchor: [0, -54]
    });
  }
  function hedefIcon() {
    return L.divIcon({
      className: '',
      html: '<div class="hedef-ikon-wrap"><span class="material-icons-outlined">location_on</span></div>',
      iconSize: [44, 44], iconAnchor: [22, 44], popupAnchor: [0, -46]
    });
  }
  function subeIcon() {
    return L.divIcon({
      className: '',
      html: '<div class="sube-ikon-wrap"><span class="material-icons-outlined">storefront</span></div>',
      iconSize: [44, 44], iconAnchor: [22, 44], popupAnchor: [0, -46]
    });
  }

  /* ---- Şube markeri (sabit) ---- */
  if (rLat && rLng) {
    L.marker([rLat, rLng], { icon: subeIcon() }).addTo(map)
      .bindPopup('<b>' + subAd + '</b><br><span style="color:#64748b;font-size:12px">Restoran</span>');
  }

  /* ---- Kurye marker deposu ---- */
  var kuryeMarkers = {};   // id -> { marker, polyline }
  var ilkFit = true;

  /* ---- Canlı veri çek (her 8 sn) ---- */
  function yukle() {
    fetch('/api/kurye-canli-veri').then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) return;

      /* Özet sayaçlar */
      document.getElementById('o-kurye').textContent  = d.ozet.kurye;
      document.getElementById('o-musait').textContent = d.ozet.musait;
      document.getElementById('o-yolda').textContent  = d.ozet.yolda;
      document.getElementById('m-kurye').textContent  = d.ozet.kurye;
      document.getElementById('m-yolda').textContent  = d.teslimatlar.length;
      var t = new Date();
      document.getElementById('o-son').textContent =
        ('0'+t.getHours()).slice(-2) + ':' + ('0'+t.getMinutes()).slice(-2) + ':' + ('0'+t.getSeconds()).slice(-2);

      var bounds = [];
      if (rLat && rLng) bounds.push([rLat, rLng]);

      /* Kurye markerları güncelle */
      d.kuryeler.forEach(function (k) {
        if (!k.lat || !k.lng) return;
        bounds.push([k.lat, k.lng]);
        var popHtml = '<b>' + k.ad + '</b><br>'
          + (k.durum === 'teslimatta' ? '🛵 Teslimatta' : (k.durum === 'mola' ? '☕ Molada' : '✅ Müsait'))
          + ' · ' + k.aktif_teslimat + ' teslimat'
          + (k.konum_dk != null ? '<br><span style="color:#64748b;font-size:11px">' + (k.konum_dk === 0 ? 'şimdi' : k.konum_dk + ' dk önce') + '</span>' : '');

        if (kuryeMarkers[k.id]) {
          kuryeMarkers[k.id].marker.setLatLng([k.lat, k.lng]).setPopupContent(popHtml);
        } else {
          kuryeMarkers[k.id] = {
            marker: L.marker([k.lat, k.lng], { icon: kuryeIcon() }).addTo(map).bindPopup(popHtml),
            polyline: null
          };
        }
      });

      /* İlk yüklemede tüm noktaları sığdır (Flutter _haritayiOturt) */
      if (ilkFit && bounds.length) {
        if (bounds.length === 1) {
          map.setView(bounds[0], 15);
        } else {
          map.fitBounds(bounds, { padding: [70, 70], maxZoom: 16 });
        }
        ilkFit = false;
      }

      /* Yan liste: kuryeler */
      var kh = '';
      d.kuryeler.forEach(function (k) {
        var rzCls = k.durum === 'teslimatta' ? 'rz-teslimatta' : (k.durum === 'mola' ? 'rz-mola' : 'rz-musait');
        var konumDurum = k.lat ? '' : ' off';
        var konumYazi  = k.lat
          ? (k.konum_dk != null ? (k.konum_dk === 0 ? 'şimdi güncellendi' : k.konum_dk + ' dk önce') : 'canlı')
          : 'Konum yok';
        kh += '<div class="kk" onclick="odakKurye(' + k.id + ')">'
            + '<div class="av"><span class="material-icons-outlined">two_wheeler</span></div>'
            + '<div class="inf">'
            + '<div class="ad">' + k.ad + '</div>'
            + '<div class="du" style="display:flex;align-items:center;gap:5px"><span class="canli-dot' + konumDurum + '"></span>' + konumYazi + '</div>'
            + '</div>'
            + '<span class="rz ' + rzCls + '">' + (k.durum || 'müsait') + '</span>'
            + '</div>';
      });
      document.getElementById('liste-kuryeler').innerHTML = kh ||
        '<div class="bos">Aktif kurye yok</div>';

      /* Yan liste: teslimatlar */
      var th = '';
      d.teslimatlar.forEach(function (t) {
        th += '<div class="tk">'
            + '<div class="tk-ust"><span class="plat">' + (t.platform || 'paket') + '</span>'
            + '<span class="tut">' + Math.round(t.toplam) + ' ₺</span></div>'
            + '<div class="adr">' + (t.teslimat_adres || 'Adres yok') + '</div>'
            + '<div class="mt">#' + t.id + ' · ' + (t.musteri || 'Müşteri') + '</div>'
            + (t.kurye ? '<div class="kurye-cip"><span class="material-icons-outlined" style="font-size:14px">two_wheeler</span>' + t.kurye + '</div>' : '')
            + '</div>';
      });
      document.getElementById('liste-teslimatlar').innerHTML = th ||
        '<div class="bos">Yolda teslimat yok</div>';

    }).catch(function () {});
  }

  /* ---- Kurye odakla (Flutter _kuryeyeOdaklan) ---- */
  window.odakKurye = function (id) {
    if (kuryeMarkers[id]) {
      map.setView(kuryeMarkers[id].marker.getLatLng(), 16);
      kuryeMarkers[id].marker.openPopup();
    }
  };

  /* ---- Tüm kuryeyi sığdır ---- */
  window.tumKuryeGor = function () {
    var noktalar = [];
    if (rLat && rLng) noktalar.push([rLat, rLng]);
    Object.keys(kuryeMarkers).forEach(function (id) {
      var ll = kuryeMarkers[id].marker.getLatLng();
      noktalar.push([ll.lat, ll.lng]);
    });
    if (!noktalar.length) return;
    if (noktalar.length === 1) { map.setView(noktalar[0], 15); return; }
    map.fitBounds(noktalar, { padding: [70, 70], maxZoom: 16 });
  };

  yukle();
  setInterval(yukle, 8000);
})();
</script>
@endpush

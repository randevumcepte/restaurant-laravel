<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>Kurye Paneli · {{ $k->ad }}</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#161C2E; --line:#232B42; --ink:#F3E9EE; --sessiz:#94A3B8; --yesil:#10B981; --amber:#F59E0B; --kirmizi:#F43F5E; }
  *{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:var(--bg);min-height:100dvh;padding:14px 14px 30px}
  .wrap{max-width:520px;margin:0 auto}
  header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
  .kd{display:flex;align-items:center;gap:10px}
  .kd .av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--mor),var(--mavi));display:flex;align-items:center;justify-content:center;font-size:22px}
  .kd b{font-size:17px}
  .kd i{font-style:normal;font-size:12px;color:var(--sessiz)}
  .gps{display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:16px;font-size:13.5px}
  .dot{width:10px;height:10px;border-radius:50%;background:var(--kirmizi);box-shadow:0 0 0 0 rgba(16,185,129,.5)}
  .dot.on{background:var(--yesil);animation:pulse 1.6s infinite}
  @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(16,185,129,.5)}70%{box-shadow:0 0 0 10px rgba(16,185,129,0)}100%{box-shadow:0 0 0 0 rgba(16,185,129,0)}}
  h2{font-size:15px;color:var(--sessiz);margin:6px 2px 10px;font-weight:700}
  .sip{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:16px;margin-bottom:12px}
  .sip .ust{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
  .sip .plat{font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;background:rgba(124,58,237,.18);color:#C4B5FD;text-transform:uppercase}
  .sip .tut{font-weight:800;color:var(--yesil);font-size:16px}
  .sip .adr{font-size:15px;font-weight:600;margin-bottom:4px}
  .sip .mus{font-size:13px;color:var(--sessiz);margin-bottom:12px}
  .sip .btns{display:flex;gap:8px}
  .sip a,.sip button{flex:1;text-align:center;border:none;border-radius:12px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none}
  .ara{background:rgba(255,255,255,.08);color:#fff;border:1px solid var(--line)}
  .yol{background:var(--amber);color:#3a2600}
  .tes{background:var(--yesil);color:#04231a}
  .harita{background:rgba(79,70,229,.15);color:#C7D2FE;border:1px solid rgba(79,70,229,.4)}
  .olmadi{background:rgba(244,63,94,.15);color:#fda4af;border:1px solid rgba(244,63,94,.4)}
  .tahsil{font-size:13.5px;padding:9px 12px;border-radius:10px;margin-bottom:12px;font-weight:600}
  .tahsil-nakit{background:rgba(16,185,129,.14);color:#6ee7b7;border:1px solid rgba(16,185,129,.35)}
  .tahsil-kart{background:rgba(245,158,11,.14);color:#fcd34d;border:1px solid rgba(245,158,11,.35)}
  .tahsil-yok{background:rgba(148,163,184,.14);color:#cbd5e1;border:1px solid rgba(148,163,184,.3)}
  .bos{text-align:center;color:var(--sessiz);padding:50px 20px}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <div class="kd">
      <div class="av">🛵</div>
      <div><b>{{ $k->ad }}</b><br><i>Kurye Paneli</i></div>
    </div>
  </header>

  <div class="gps"><span class="dot" id="dot"></span><span id="gpsyazi">Konum izni bekleniyor…</span></div>

  <h2>AKTİF TESLİMATLARIM ({{ count($teslimatlar) }})</h2>

  <div id="liste">
    @forelse ($teslimatlar as $t)
      <div class="sip" data-id="{{ $t->id }}">
        <div class="ust">
          <span class="plat">{{ $t->platform ?? 'paket' }}</span>
          <span class="tut">{{ number_format($t->toplam, 0, ',', '.') }}TL</span>
        </div>
        <div class="adr">📍 {{ $t->teslimat_adres ?? 'Adres girilmemiş' }}</div>
        <div class="mus">{{ $t->musteri ?? 'Müşteri' }} @if($t->telefon) · {{ $t->telefon }} @endif · <b>#{{ $t->id }}</b> · {{ $t->teslimat_durumu === 'yolda' ? '🛵 Yolda' : '📦 Hazır' }}</div>
        @php $oy = $t->odeme_yontemi ?? 'nakit'; $tut = number_format($t->toplam, 0, ',', '.'); @endphp
        <div class="tahsil {{ $oy==='online' ? 'tahsil-yok' : ($oy==='kart_kapida' ? 'tahsil-kart' : 'tahsil-nakit') }}">
          @if($oy==='online') 🔗 Online ödendi · <b>tahsilat YOK</b>
          @elseif($oy==='kart_kapida') 💳 Kapıda <b>KART</b> (POS götür) · <b>{{ $tut }} TL</b>
          @else 💵 Kapıda <b>NAKİT</b> al · <b>{{ $tut }} TL</b>
          @endif
        </div>
        <div class="btns">
          <a class="harita" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($t->teslimat_adres ?? '') }}" target="_blank">🗺️ Yol Tarifi</a>
          @if($t->telefon)<a class="ara" href="tel:{{ $t->telefon }}">📞 Ara</a>@endif
          @if($t->teslimat_durumu !== 'yolda')
            <button class="yol" onclick="durum({{ $t->id }},'yolda',this)">Yola Çıktım</button>
          @else
            <button class="tes" onclick="durum({{ $t->id }},'teslim',this)">Teslim Ettim ✅</button>
          @endif
        </div>
        @if($t->teslimat_durumu === 'yolda')
        <div class="btns" style="margin-top:8px">
          <button class="olmadi" onclick="teslimEdemedim({{ $t->id }},this)">Teslim Edemedim ✖</button>
        </div>
        @endif
      </div>
    @empty
      <div class="bos">🎉 Şu an aktif teslimatın yok.</div>
    @endforelse
  </div>
</div>

<script>
  var TOKEN = @json($k->token);
  var son = 0;
  // Canli konum paylasimi
  if (navigator.geolocation) {
    navigator.geolocation.watchPosition(function(pos){
      document.getElementById('dot').classList.add('on');
      document.getElementById('gpsyazi').textContent = 'Konum paylaşılıyor · canlı';
      var now = Date.now();
      if (now - son < 10000) return;   // 10 sn'de bir gonder
      son = now;
      fetch('/kurye/'+TOKEN+'/konum', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'lat='+pos.coords.latitude+'&lng='+pos.coords.longitude}).catch(function(){});
    }, function(){
      document.getElementById('gpsyazi').textContent = 'Konum izni kapalı — lütfen izin ver';
    }, {enableHighAccuracy:true, maximumAge:5000, timeout:15000});
  } else {
    document.getElementById('gpsyazi').textContent = 'Cihaz konumu desteklemiyor';
  }
  function durum(id, d, btn){
    btn.disabled = true; btn.textContent = '…';
    fetch('/kurye/'+TOKEN+'/durum', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'adisyon_id='+id+'&durum='+d})
      .then(function(r){return r.json();})
      .then(function(j){ if(j.ok){ if(j.mesaj) alert(j.mesaj); location.reload(); } else { btn.disabled=false; alert(j.hata||'Hata'); } })
      .catch(function(){ btn.disabled=false; });
  }
  function teslimEdemedim(id, btn){
    var sebep = prompt('Teslim edilemedi sebebi nedir?\n(ör. adreste bulunamadı, müşteri ulaşılamadı, müşteri vazgeçti)');
    if (sebep === null) return;
    btn.disabled = true; btn.textContent = '…';
    fetch('/kurye/'+TOKEN+'/durum', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'adisyon_id='+id+'&durum=teslim_edilemedi&sebep='+encodeURIComponent(sebep||'')})
      .then(function(r){return r.json();})
      .then(function(j){ if(j.ok){ location.reload(); } else { btn.disabled=false; alert(j.hata||'Hata'); } })
      .catch(function(){ btn.disabled=false; });
  }
</script>
</body>
</html>

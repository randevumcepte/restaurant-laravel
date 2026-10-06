<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Deneyiminizi Değerlendirin · {{ $sube->ad ?? 'Restoran' }}</title>
<style>
  :root{ --mor:#7C3AED; --mor2:#9D5DC8; --ink:#1e293b; --sub:#64748b; --line:#e2e8f0; --bg:#f1f5f9; }
  *{ box-sizing:border-box; } body{ margin:0; background:var(--bg); color:var(--ink); font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif; }
  .wrap{ max-width:480px; margin:0 auto; padding:0 16px 48px; }
  .hero{ background:linear-gradient(135deg,#5C008E,#7B2FB8 55%,#9D5DC8); color:#fff; border-radius:0 0 28px 28px; padding:34px 22px 28px; text-align:center; }
  .hero h1{ font-size:21px; margin:10px 0 4px; } .hero p{ margin:0; opacity:.9; font-size:14px; }
  .card{ background:#fff; border:1px solid var(--line); border-radius:18px; padding:22px; margin-top:-18px; box-shadow:0 10px 30px rgba(15,23,42,.08); }
  .q{ font-weight:700; margin:18px 0 8px; font-size:15px; } .q:first-child{ margin-top:4px; }
  .stars{ display:flex; gap:8px; justify-content:center; }
  .star{ font-size:40px; cursor:pointer; filter:grayscale(1) opacity(.35); transition:.12s; user-select:none; }
  .star.on{ filter:none; transform:scale(1.08); }
  .mini{ display:flex; gap:6px; } .mini .star{ font-size:26px; }
  .row{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:10px 0; border-top:1px solid var(--line); }
  .row span{ color:var(--sub); font-size:14px; font-weight:600; }
  textarea{ width:100%; border:1px solid var(--line); border-radius:12px; padding:12px; font-size:14px; font-family:inherit; resize:vertical; min-height:84px; margin-top:6px; }
  .btn{ width:100%; background:var(--mor); color:#fff; border:none; border-radius:14px; padding:15px; font-size:16px; font-weight:800; cursor:pointer; margin-top:18px; }
  .btn:disabled{ opacity:.5; }
  .ok{ text-align:center; padding:30px 10px; } .ok .big{ font-size:54px; } .ok h2{ margin:10px 0 6px; }
  .glink{ display:inline-block; margin-top:14px; background:#1a73e8; color:#fff; text-decoration:none; padding:12px 18px; border-radius:12px; font-weight:700; }
  .mut{ color:var(--sub); font-size:12.5px; text-align:center; margin-top:14px; }
</style>
</head>
<body>
<div class="hero">
  <div style="font-size:40px">🍽️</div>
  <h1>{{ $sube->ad ?? 'Restoran' }}</h1>
  <p>Deneyiminiz bizim için çok değerli</p>
</div>
<div class="wrap">
  <div class="card" id="form" @if($dolduruldu) style="display:none" @endif>
    <div class="q">Genel memnuniyetiniz?</div>
    <div class="stars" id="genel">
      @for($i=1;$i<=5;$i++)<span class="star" data-v="{{ $i }}">⭐</span>@endfor
    </div>
    <div class="row"><span>😋 Lezzet</span><div class="stars mini" id="lezzet">@for($i=1;$i<=5;$i++)<span class="star" data-v="{{ $i }}">⭐</span>@endfor</div></div>
    <div class="row"><span>🙋 Servis</span><div class="stars mini" id="servis">@for($i=1;$i<=5;$i++)<span class="star" data-v="{{ $i }}">⭐</span>@endfor</div></div>
    <div class="row"><span>⚡ Hız</span><div class="stars mini" id="hiz">@for($i=1;$i<=5;$i++)<span class="star" data-v="{{ $i }}">⭐</span>@endfor</div></div>
    <div class="q">Eklemek istediğiniz bir şey var mı?</div>
    <textarea id="yorum" placeholder="Görüşlerinizi yazabilirsiniz (opsiyonel)"></textarea>
    <button class="btn" id="gonder">Değerlendirmeyi Gönder</button>
    <div class="mut">Yanıtınız yalnızca hizmet kalitemizi geliştirmek için kullanılır.</div>
  </div>

  <div class="card ok" id="tesekkur" @if(!$dolduruldu) style="display:none" @endif>
    <div class="big">🙏</div>
    <h2>Teşekkür ederiz!</h2>
    <p class="mut" style="font-size:14px">Değerlendirmeniz bize ulaştı.</p>
    <div id="googleKutu"></div>
  </div>
</div>
<script>
  var puanlar = { genel:5, lezzet:0, servis:0, hiz:0 };
  function kur(id){
    var box = document.getElementById(id);
    box.querySelectorAll('.star').forEach(function(s){
      s.onclick = function(){
        var v = +s.dataset.v; puanlar[id] = v;
        box.querySelectorAll('.star').forEach(function(x){ x.classList.toggle('on', +x.dataset.v <= v); });
      };
    });
  }
  ['genel','lezzet','servis','hiz'].forEach(kur);
  // genel varsayilan 5 secili
  document.querySelectorAll('#genel .star').forEach(function(x){ x.classList.add('on'); });
  document.getElementById('gonder').onclick = function(){
    var b = this; b.disabled = true; b.textContent = 'Gönderiliyor...';
    fetch('{{ url('/anket/'.$token) }}', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
      body: JSON.stringify({ puan:puanlar.genel, lezzet:puanlar.lezzet||puanlar.genel, servis:puanlar.servis||puanlar.genel, hiz:puanlar.hiz||puanlar.genel, yorum:document.getElementById('yorum').value })
    }).then(function(r){ return r.json(); }).then(function(j){
      document.getElementById('form').style.display='none';
      var t = document.getElementById('tesekkur'); t.style.display='block';
      if(j && j.google){ document.getElementById('googleKutu').innerHTML =
        '<p class="mut" style="font-size:14px">Beğendiyseniz bir de Google\'da paylaşır mısınız? 🙏</p><a class="glink" href="'+j.google+'" target="_blank">⭐ Google\'da Değerlendir</a>'; }
    }).catch(function(){ b.disabled=false; b.textContent='Tekrar Dene'; });
  };
</script>
</body>
</html>

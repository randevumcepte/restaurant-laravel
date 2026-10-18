@extends('layout.app')
@section('rk_ikon', 'qr_code_scanner')
@section('baslik', 'Ödül / Kupon Doğrula')
@section('rk_alt', 'Müşterinin ödül QR kodunu okut ya da elle gir → doğrula → onayla. Onaylayınca puan otomatik düşer.')

@section('content')
<div style="max-width:560px;margin:0 auto">

  {{-- ── Giriş kartı (kod okut / elle gir) ──────────────────── --}}
  <div class="rk-card" id="odGiris">
    <div class="rk-bolum" style="margin:0 0 14px">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Ödül Kodu</span>
    </div>

    {{-- Büyük QR okut bloğu --}}
    <div id="odOkutKutu"
      style="cursor:pointer;border-radius:18px;padding:28px 20px;text-align:center;color:#fff;
             background:linear-gradient(135deg,var(--mor1),var(--mavi));
             box-shadow:0 8px 18px color-mix(in srgb,var(--mor1) 35%,transparent)">
      <span class="material-icons-outlined" style="font-size:52px">qr_code_scanner</span>
      <div style="font-size:18px;font-weight:800;margin-top:8px">Ödül Kodunu Okut</div>
      <div style="font-size:12.5px;opacity:.85;margin-top:3px">Müşterinin ekranındaki QR kodu kameraya göster</div>
      <video id="odVideo" playsinline style="display:none;width:100%;max-height:260px;border-radius:12px;margin-top:14px;background:#000"></video>
    </div>

    {{-- Ayraç --}}
    <div style="display:flex;align-items:center;gap:10px;margin:16px 0 12px">
      <span style="flex:1;height:1px;background:var(--line)"></span>
      <span style="font-size:12px;color:var(--sub)">veya kodu elle gir</span>
      <span style="flex:1;height:1px;background:var(--line)"></span>
    </div>

    {{-- Kod girişi --}}
    <input id="odKod" maxlength="8" autocomplete="off" autofocus
      class="rk-inp" placeholder="ÖRN: ZXLAGE"
      style="text-align:center;font-size:22px;font-weight:800;letter-spacing:6px;text-transform:uppercase"
      oninput="this.value=this.value.toUpperCase()">

    <button onclick="odDogrula()" id="odBtnDogrula" class="rk-btn lg" style="background:var(--mavi);margin-top:14px">
      <span id="odBtnTxt">Doğrula</span>
    </button>

    <div id="odFlash" style="margin-top:12px"></div>
  </div>

  {{-- ── Bilgi kartı (doğrula sonucu) ───────────────────────── --}}
  <div id="odBilgi" style="display:none">
    <div class="rk-card" style="text-align:center">
      <span class="material-icons-outlined" style="font-size:44px;color:var(--gold)">card_giftcard</span>
      <div id="odAd" style="font-size:20px;font-weight:800;color:var(--ink);margin-top:8px"></div>
      <div id="odPuan" style="font-size:14px;color:var(--sub);margin-top:2px"></div>
      <div id="odAciklama" style="font-size:13px;color:var(--sub2);margin-top:8px;display:none"></div>

      <div style="height:1px;background:var(--line);margin:20px 0"></div>

      <div id="odSatirlar" style="text-align:left"></div>

      <div id="odYetersizUyari" style="display:none;color:var(--kirmizi);font-size:12.5px;font-weight:700;margin-top:10px">
        Müşterinin puanı yetersiz, onaylanamaz.
      </div>
    </div>

    {{-- Bilgi şeridi (açık hesap var/yok) --}}
    <div id="odSerit" style="display:flex;gap:8px;align-items:flex-start;padding:12px;border-radius:12px;margin-top:10px">
      <span class="material-icons-outlined" id="odSeritIk" style="font-size:18px">info</span>
      <span id="odSeritTxt" style="font-size:12.5px;color:var(--sub2)"></span>
    </div>

    {{-- Aksiyonlar --}}
    <div style="display:flex;gap:12px;margin-top:16px">
      <button onclick="odVazgec()" class="rk-btn bos lg" id="odBtnVazgec" style="flex:1">Vazgeç</button>
      <button onclick="odOnayla()" class="rk-btn lg" id="odBtnOnayla" style="flex:2;background:var(--yesil)">
        <span class="material-icons-outlined" style="font-size:18px;color:#fff">check_circle</span>
        <span id="odOnaylaTxt">Onayla</span>
      </button>
    </div>
  </div>

  {{-- ── Sonuç kartı (başarı) ───────────────────────────────── --}}
  <div id="odSonuc" style="display:none">
    <div class="rk-card" style="text-align:center;padding:30px 16px;
         border-color:color-mix(in srgb,var(--yesil) 40%,transparent);
         background:color-mix(in srgb,var(--yesil) 10%,transparent)">
      <span class="material-icons-outlined" style="font-size:52px;color:var(--yesil)">check_circle</span>
      <div id="odSonucTxt" style="font-size:17px;font-weight:800;color:var(--yesil);margin-top:8px"></div>
      <button onclick="odSifirla()" class="rk-btn lg" style="background:var(--mor1);margin-top:18px;max-width:240px;margin-left:auto;margin-right:auto">
        <span class="material-icons-outlined" style="font-size:18px;color:#fff">qr_code_scanner</span>
        <span>Yeni Ödül Doğrula</span>
      </button>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(function(){
  var _kod = '', _adId = 0, _loading = false, _onaylaniyor = false;
  var $ = function(id){ return document.getElementById(id); };
  function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

  async function post(u, b){
    var r = await fetch(u, { method:'POST', headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':window.csrf, 'Accept':'application/json' }, body:JSON.stringify(b) });
    return r.json();
  }

  function temizle(raw){
    var k = (raw||'').trim();
    if (k.indexOf('/') >= 0) k = k.split('/').pop();   // URL ise son parça
    return k.trim().toUpperCase();
  }

  function flash(m, tur){
    $('odFlash').innerHTML = m ? '<div class="rk-flash '+(tur==='ok'?'ok':'err')+'">'+esc(m)+'</div>' : '';
  }

  // ── Doğrula ───────────────────────────────────────────
  window.odDogrula = async function(){
    if (_loading) return;
    var kod = temizle($('odKod').value);
    $('odKod').value = kod;
    if (!kod){ flash('Kod gir veya QR okut','err'); return; }
    _loading = true; flash(''); $('odBtnDogrula').disabled = true; $('odBtnTxt').textContent = 'Kontrol ediliyor…';
    try {
      var r = await post('/api/pos/odul-dogrula', { kod: kod });
      if (!r.ok){ flash(r.hata || 'Kod doğrulanamadı','err'); return; }
      odBilgiGoster(r);
    } catch(e){ flash('Bağlantı hatası','err'); }
    finally { _loading = false; $('odBtnDogrula').disabled = false; $('odBtnTxt').textContent = 'Doğrula'; }
  };

  function satir(ik, k, v, renk){
    return '<div style="display:flex;align-items:center;gap:10px;padding:5px 0">'
      + '<span class="material-icons-outlined" style="font-size:18px;color:var(--sub)">'+ik+'</span>'
      + '<span style="font-size:13.5px;color:var(--sub)">'+esc(k)+'</span>'
      + '<span style="margin-left:auto;font-size:14px;font-weight:700;color:'+(renk||'var(--ink)')+'">'+esc(v)+'</span>'
      + '</div>';
  }

  function odBilgiGoster(r){
    _kod = r.kod; _adId = r.adisyon ? r.adisyon.id : 0;
    var yeterli = r.yeterli === true;

    $('odAd').textContent = r.odul_ad || 'Ödül';
    $('odPuan').textContent = (r.puan != null ? r.puan : 0) + ' puan';

    var ac = $('odAciklama');
    if (r.aciklama){ ac.textContent = r.aciklama; ac.style.display = ''; } else { ac.style.display = 'none'; }

    var html = satir('person', 'Müşteri', r.musteri || '-');
    html += satir('stars', 'Mevcut Puanı', (r.musteri_puan!=null?r.musteri_puan:0) + ' puan', yeterli ? 'var(--yesil)' : 'var(--kirmizi)');
    if (r.adisyon){
      html += satir('receipt_long', 'Açık Hesap', '#'+r.adisyon.id+' · '+Math.round(r.adisyon.toplam)+'₺');
    }
    $('odSatirlar').innerHTML = html;

    $('odYetersizUyari').style.display = yeterli ? 'none' : '';

    // Bilgi şeridi (açık hesap var → yeşil, yok → amber)
    var serit = $('odSerit'), ik = $('odSeritIk'), txt = $('odSeritTxt');
    if (r.adisyon){
      serit.style.background = 'color-mix(in srgb,var(--yesil) 12%,transparent)';
      ik.style.color = 'var(--yesil)';
      txt.textContent = 'Onaylayınca ödül bu açık hesaba indirim/ikram olarak işlenir + puan düşülür.';
    } else {
      serit.style.background = 'color-mix(in srgb,var(--amber) 12%,transparent)';
      ik.style.color = 'var(--amber)';
      txt.textContent = 'Açık hesap yok. Onaylayınca sadece puan düşülür (ödülü elle verin).';
    }

    // Onayla butonu
    var btn = $('odBtnOnayla');
    btn.disabled = !yeterli;
    btn.style.background = yeterli ? 'var(--yesil)' : 'var(--sub)';
    $('odOnaylaTxt').textContent = r.adisyon ? 'Onayla & Hesaba Uygula' : 'Onayla & Puan Düş';

    odKamerayiDurdur();
    $('odGiris').style.display = 'none';
    $('odSonuc').style.display = 'none';
    $('odBilgi').style.display = '';
  }

  // ── Onayla ────────────────────────────────────────────
  window.odOnayla = async function(){
    if (_onaylaniyor || !_kod) return;
    if ($('odBtnOnayla').disabled) return;
    _onaylaniyor = true;
    $('odBtnOnayla').disabled = true; $('odBtnVazgec').disabled = true; $('odOnaylaTxt').textContent = 'Uygulanıyor…';
    try {
      var r = await post('/api/pos/odul-onayla', { kod: _kod, adisyon_id: _adId });
      if (r.ok){
        $('odSonucTxt').textContent = r.mesaj || 'Ödül uygulandı';
        $('odBilgi').style.display = 'none';
        $('odSonuc').style.display = '';
      } else {
        $('odBilgi').style.display = 'none';
        $('odGiris').style.display = '';
        flash(r.hata || 'Onaylanamadı','err');
      }
    } catch(e){
      $('odBilgi').style.display = 'none';
      $('odGiris').style.display = '';
      flash('Bağlantı hatası','err');
    } finally {
      _onaylaniyor = false;
      $('odBtnOnayla').disabled = false; $('odBtnVazgec').disabled = false; $('odOnaylaTxt').textContent = 'Onayla';
    }
  };

  window.odVazgec = function(){ odSifirla(); };

  window.odSifirla = function(){
    _kod = ''; _adId = 0;
    $('odKod').value = '';
    $('odBilgi').style.display = 'none';
    $('odSonuc').style.display = 'none';
    $('odGiris').style.display = '';
    flash('');
    setTimeout(function(){ try { $('odKod').focus(); } catch(e){} }, 150);
  };

  // ── QR okuyucu (jsQR + getUserMedia) ──────────────────
  var _stream = null, _raf = 0, _canvas = null;
  function odKamerayiDurdur(){
    if (_raf){ cancelAnimationFrame(_raf); _raf = 0; }
    if (_stream){ _stream.getTracks().forEach(function(t){ t.stop(); }); _stream = null; }
    var v = $('odVideo'); if (v){ v.pause(); v.srcObject = null; v.style.display = 'none'; }
  }
  async function odKamerayiBaslat(){
    if (_stream) return;
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof jsQR === 'undefined'){
      flash('Kamera desteklenmiyor — kodu elle girin','err'); return;
    }
    try {
      _stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
      var v = $('odVideo'); v.srcObject = _stream; v.style.display = 'block';
      await v.play();
      _canvas = _canvas || document.createElement('canvas');
      var ctx = _canvas.getContext('2d');
      var tara = function(){
        if (!_stream) return;
        if (v.readyState === v.HAVE_ENOUGH_DATA){
          _canvas.width = v.videoWidth; _canvas.height = v.videoHeight;
          ctx.drawImage(v, 0, 0, _canvas.width, _canvas.height);
          try {
            var img = ctx.getImageData(0, 0, _canvas.width, _canvas.height);
            var code = jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
            if (code && code.data){
              $('odKod').value = temizle(code.data);
              odKamerayiDurdur();
              odDogrula();
              return;
            }
          } catch(e){}
        }
        _raf = requestAnimationFrame(tara);
      };
      _raf = requestAnimationFrame(tara);
    } catch(e){ flash('Kameraya erişilemedi — kodu elle girin','err'); _stream = null; }
  }
  $('odOkutKutu').addEventListener('click', function(){
    if (_loading) return;
    if (_stream) odKamerayiDurdur(); else odKamerayiBaslat();
  });

  $('odKod').addEventListener('keydown', function(e){ if (e.key === 'Enter') odDogrula(); });
})();
</script>
@endsection

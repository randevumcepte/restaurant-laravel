{{-- İşletme Düzeni > Garson Yönetimi kodu. idzModal, idzSekme, garsonAtaAc ve masa çizimi sayfanın kendisinden gelir. --}}
@if (!empty($gyJson))
{{-- Yeni garson (panelin dışında: gizli sekme içindeki pencere açılmaz) --}}
<div class="idz-modal" id="gyYeniGarson">
  <div class="rk-card idz-modal-kutu">
    <div class="idz-modal-bas"><span class="material-icons-outlined idz-mor">person_add_alt</span><b>Yeni Garson Ekle</b><button type="button" class="mduz-ibtn" onclick="idzModalKapat(this)" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>
    <form method="POST" action="/personel/kaydet">
      @csrf
      <input type="hidden" name="rol" value="garson">
      <input type="hidden" name="geri" value="/garson-yonetimi">
      <label class="rk-lbl" style="margin-top:0">Ad Soyad</label>
      <input type="text" name="ad" maxlength="80" required class="rk-inp">
      <label class="rk-lbl">Telefon</label>
      <input type="tel" name="telefon" maxlength="20" class="rk-inp">
      <label class="rk-lbl">Giriş PIN'i (en az 4 hane)</label>
      <input type="password" name="pin" inputmode="numeric" pattern="[0-9]{4,}" required autocomplete="new-password" class="rk-inp">
      <div class="rk-sub" style="font-size:11.5px;margin-top:10px">Maaş ve özlük bilgilerini sonra <a href="/personel" style="color:var(--mor1);font-weight:700">Personel</a> ekranından ekleyebilirsiniz.</div>
      <div class="idz-modal-alt">
        <button type="button" class="idz-btn" onclick="idzModalKapat(this)">İptal</button>
        <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">add</span>Garson Ekle</button>
      </div>
    </form>
  </div>
</div>
<script>
document.getElementById('gyYeniGarson').addEventListener('click', function(e){ if(e.target === this) this.classList.remove('acik'); });
var GY = {!! $gyJson !!};
var gyD = GY.panel, gyPencere = 'saat', gyAlan = GY.alanlar.length ? GY.alanlar[0].id : 0, gyIsiGarson = 0, gyOlayGarson = 0;
var gyStat = 'gunluk', gyPerf = 'haftalik';
// Isı Haritası sekmesi: -1 = Tüm Salon
var gyIsiAlan = GY.alanlar.length > 1 ? -1 : gyAlan;
var GY_SEKMELER = ['genel', 'garsonlar', 'aktivite', 'isi', 'rapor', 'ayarlar'];
function gyE(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function gySure(sn){ if(sn == null) return '—'; var d = Math.floor(sn / 60), s = sn % 60; return d ? d + ' dk ' + s + ' sn' : s + ' sn'; }
function gyPara(v){ return Math.round(v).toLocaleString('tr-TR') + ' ₺'; }
function gyMasaAd(m){ return /^\d+$/.test(m.ad) ? 'Masa ' + m.ad : m.ad; }
// Harita etiketi: "Bahçe 3" gibi adlarda numara (bölge zaten belli, tam ad ipucunda)
function gyMasaEt(m){ var s = /^\D*\s(\d+)$/.exec(m.ad); return s ? s[1] : m.ad; }
function gyAcik(){ var p = document.querySelector('.idz-panel[data-panel="garson"]'); return !!p && p.classList.contains('aktif'); }
// Alt sekme (dıştaki idzSekme('garson') bu paneli açar)
function gySekme(ad){
  if(GY_SEKMELER.indexOf(ad) < 0) return;
  if(!gyAcik()) idzSekme('garson');
  document.querySelectorAll('.gy-sekme').forEach(function(x){ x.classList.toggle('aktif', x.getAttribute('data-gy-sekme') === ad); });
  document.querySelectorAll('.gy-panel').forEach(function(p){ p.classList.toggle('aktif', p.getAttribute('data-gy-panel') === ad); });
  try{ sessionStorage.setItem('gy_sekme', ad); }catch(e){}
  gyIsiCizHepsi();
}
document.querySelectorAll('.gy-sekme').forEach(function(b){ b.addEventListener('click', function(){ gySekme(b.getAttribute('data-gy-sekme')); }); });

function gyAvatar(g){ var f = GY.fotolar[g.id]; return f ? '<img class="idz-avatar" src="'+gyE(f)+'" alt="">' : '<span class="idz-avatar">'+gyE(String(g.ad).charAt(0).toLocaleUpperCase('tr'))+'</span>'; }
var gyDurum = {aktif:['Aktif','#10B981'], mola:['Mola','#F97316'], cevrimdisi:['Çevrimdışı','#94A3B8']};
function gyDurumEt(g){ var d = gyDurum[g.durum]; return '<span class="gy-durum" style="--r:'+d[1]+'"><i></i>'+d[0]+'</span>'; }
function gyKartCiz(){
  var k = gyD.kart;
  function ok(t, tersIyi){
    if(t == null) return '';
    var iyi = tersIyi ? t < 0 : t > 0;
    return '<span class="idz-trend '+(iyi ? '' : 'kotu')+'" title="Önceki döneme göre"><span class="material-icons-outlined">'+(t > 0 ? 'arrow_upward' : 'arrow_downward')+'</span>%'+Math.abs(t)+'</span>';
  }
  var kart = [
    ['groups', '#7C3AED', 'Aktif Garson', k.aktif + ' / ' + k.toplam, '', 'Mesaide olan ya da son 1 saatte işlem yapan garsonlar'],
    ['assignment', '#7C3AED', 'Toplam Adım', k.adim.toLocaleString('tr-TR'), '', 'Bugün garson telefonlarından gelen adım toplamı'],
    ['schedule', '#7C3AED', 'Ortalama Hizmet Süresi', gySure(k.servis_sn), ok(k.servis_trend, true), 'Mutfakta hazır olandan masaya servis edilene kadar geçen süre (bugün)'],
    ['check', '#10B981', 'Tamamlanan Sipariş', k.tamam.toLocaleString('tr-TR'), ok(k.tamam_trend), 'Bugün ödemesi alınıp kapanan salon adisyonları'],
    ['star', '#7C3AED', 'Müşteri Memnuniyeti', k.memnuniyet == null ? '—' : '%' + k.memnuniyet, ok(k.memnuniyet_trend), 'Son 7 günün memnuniyet puanı'],
  ];
  document.getElementById('gyKartlar').innerHTML = kart.map(function(c){
    return '<div class="idz-stat" title="'+gyE(c[5])+'"><span class="idz-stat-ikon" style="--r:'+c[1]+'"><span class="material-icons-outlined">'+c[0]+'</span></span><div><div class="e">'+gyE(c[2])+'</div><div class="d">'+gyE(c[3])+'</div></div>'+c[4]+'</div>';
  }).join('');
}
function gySpark(a, soluk){
  var mx = Math.max.apply(null, a.concat([1]));
  return '<span class="gy-spark'+(soluk ? ' soluk' : '')+'" aria-hidden="true">'+a.map(function(v){ return '<i style="height:'+Math.max(12, Math.round(v * 100 / mx))+'%"></i>'; }).join('')+'</span>';
}
function gyGarsonOzetCiz(){
  var l = gyD.garsonlar.slice(0, 5);
  document.getElementById('gyGarsonOzet').innerHTML = l.length ? l.map(function(g){
    var metin = g.durum === 'mola' ? 'Dinlenme süresi' : (g.son || 'Bugün işlem yok');
    var dk = g.durum === 'mola' ? (g.mola_dk != null ? g.mola_dk + ' dk' : '-') : (g.dk != null ? (g.dk < 60 ? g.dk + ' dk' : Math.floor(g.dk / 60) + ' sa') : '-');
    return '<div class="gy-gsat">'+gyAvatar(g)+'<div class="ad"><b title="'+gyE(g.ad)+'">'+gyE(g.ad)+'</b><small>Garson</small></div>'+gyDurumEt(g)+'<span class="me" title="'+gyE(metin)+'">'+gyE(metin)+'</span><span class="dk">'+dk+'</span>'+gySpark(g.spark, g.durum !== 'aktif')+'</div>';
  }).join('') : '<div class="idz-bos">Aktif garson yok. <a href="#" onclick="idzModal(\'gyYeniGarson\');return false">Garson ekleyin.</a></div>';
}
function gyHalka(deger, oran, renk, et, ipucu){
  var C = 2 * Math.PI * 36, dolu = Math.max(0, Math.min(1, oran || 0)) * C;
  return '<div class="gy-halka" title="'+gyE(ipucu)+'"><svg viewBox="0 0 88 88"><circle cx="44" cy="44" r="36" fill="none" stroke="'+renk+'" stroke-opacity=".16" stroke-width="8"/><circle cx="44" cy="44" r="36" fill="none" stroke="'+renk+'" stroke-width="8" stroke-linecap="round" stroke-dasharray="'+dolu.toFixed(1)+' '+C.toFixed(1)+'" transform="rotate(-90 44 44)"/></svg><b>'+deger+'</b><span>'+gyE(et)+'</span></div>';
}
function gyHalkaCiz(){
  var h = gyD.halka, pct = function(v){ return v == null ? '—' : '%' + v; };
  var sv = h.servis_sn == null ? '—' : (h.servis_sn / 60).toLocaleString('tr-TR', {maximumFractionDigits: 1}) + ' dk';
  document.getElementById('gyHalkalar').innerHTML =
    gyHalka(pct(h.doluluk), (h.doluluk || 0) / 100, '#7C3AED', 'Masa Doluluk Oranı', 'Şu an açık adisyonu olan masaların oranı') +
    gyHalka(sv, h.servis_sn == null ? 0 : Math.min(1, h.servis_sn / 300), '#10B981', 'Servis Süresi (Ort.)', 'Halka 5 dakikaya göre dolar, ne kadar azsa o kadar iyi') +
    gyHalka(pct(h.tamamlama), (h.tamamlama || 0) / 100, '#F59E0B', 'Sipariş Tamamlama Oranı', 'Kapanan adisyonların açılanlara oranı') +
    gyHalka(pct(h.verimlilik), (h.verimlilik || 0) / 100, '#3B82F6', 'Garson Verimliliği', 'Garsonların en iyi garsona göre ortalama skoru');
}
function gyPerfCiz(){
  var p = gyD.performans, renk = ['#F6CE63', '#C7CDF0', '#EDA67C'];
  document.getElementById('gyPerfOzet').innerHTML = p.length ? p.slice(0, 3).map(function(g, i){
    return '<div class="gy-perf"><span class="s" style="background:'+renk[i]+'">'+(i + 1)+'</span><b title="'+gyE(g.ad)+'">'+gyE(g.ad)+'</b><span class="c"><i style="width:'+g.skor+'%"></i></span><em>%'+g.skor+'</em></div>';
  }).join('') : '<div class="idz-bos">Bu dönemde garson satışı yok.</div>';
  document.getElementById('gyRapor').innerHTML = p.length ? p.map(function(g, i){
    return '<tr><td>'+(i + 1)+'</td><td><b>'+gyE(g.ad)+'</b></td><td><span class="idz-rozet">%'+g.skor+'</span></td><td>'+g.adisyon+'</td><td>'+gyPara(g.ciro)+'</td><td>'+g.kalem+'</td><td>'+gyPara(g.ort_adisyon)+'</td><td>'+(g.masa_dk > 0 ? g.masa_dk + ' dk' : '—')+'</td><td>'+g.adim.toLocaleString('tr-TR')+'</td></tr>';
  }).join('') : '<tr><td colspan="9"><div class="idz-bos">Bu dönemde garson satışı yok.</div></td></tr>';
}
var gyOlayIkon = {acilis:'table_restaurant', siparis:'receipt_long', servis:'room_service', odeme:'payments', mesai:'login', mola:'free_breakfast'};
function gyOlayCiz(){
  var o = gyD.olaylar;
  document.getElementById('gyOlayOzet').innerHTML = o.length ? o.slice(0, 4).map(function(x){
    return '<div><i></i><time>'+x.saat+'</time><span title="'+gyE(x.ad+' → '+x.m)+'">'+gyE(x.ad)+' → '+gyE(x.m)+'</span></div>';
  }).join('') : '<div class="idz-bos">Son 12 saatte hareket yok.</div>';
  var f = gyOlayGarson ? o.filter(function(x){ return x.p === gyOlayGarson; }) : o;
  document.getElementById('gyOlayTam').innerHTML = f.length ? f.map(function(x){
    return '<div class="gy-olay"><span class="material-icons-outlined">'+(gyOlayIkon[x.tip] || 'bolt')+'</span><time>'+x.saat+'</time><b>'+gyE(x.ad)+'</b><span>'+gyE(x.m)+'</span></div>';
  }).join('') : '<div class="idz-bos">Son 12 saatte hareket yok.</div>';
}
function gyAtamaOzet(id){
  var a = GY.atama[id]; if(!a) return 'Tüm masalar';
  var ad = function(bid){ var x = GY.alanlar.find(function(z){ return z.id === +bid; }); return x ? x.ad : 'Bölge'; };
  if(a.b.length) return ad(a.b[0]) + (a.b.length > 1 ? ' +' + (a.b.length - 1) + ' bölge' : '') + (a.m.length ? ' +' + a.m.length + ' masa' : '');
  if(a.m.length) return a.m.length + ' masa';
  return 'Tüm masalar';
}
function gyGarsonKartCiz(){
  var l = gyD.garsonlar;
  document.getElementById('gySay').textContent = l.length;
  document.getElementById('gyGarsonKart').innerHTML = l.length ? l.map(function(g){
    return '<div class="gy-gkart"><div class="u">'+gyAvatar(g)+'<div class="ad"><b>'+gyE(g.ad)+'</b><span class="idz-chip">'+gyE(gyAtamaOzet(g.id))+'</span></div>'+gyDurumEt(g)+'</div>'+
      '<div class="r"><span><b>'+g.adisyon+'</b>Adisyon</span><span><b>'+g.kalem+'</b>Ürün</span><span><b>'+gyPara(g.ciro)+'</b>Ciro</span><span><b>'+g.adim.toLocaleString('tr-TR')+'</b>Adım</span></div>'+
      '<div class="a"><button type="button" class="idz-btn kucuk" onclick="garsonAtaAc('+g.id+')"><span class="material-icons-outlined">tune</span>Ata / Düzenle</button><button type="button" class="idz-btn kucuk" onclick="gyMola('+g.id+',this)"><span class="material-icons-outlined">'+(g.durum === 'mola' ? 'play_arrow' : 'free_breakfast')+'</span>'+(g.durum === 'mola' ? 'Moladan Çıkar' : 'Molaya Al')+'</button></div></div>';
  }).join('') : '<div class="idz-bos">Aktif garson yok. <a href="#" onclick="idzModal(\'gyYeniGarson\');return false">Garson ekleyin.</a></div>';
}
function gyMola(id, btn){
  btn.disabled = true;
  fetch('/garson-yonetimi/mola', {method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.csrf||''}, body: JSON.stringify({personel_id:id})})
    .then(function(r){ return r.json(); }).then(function(d){
      if(d.ok != 1){ btn.disabled = false; rkBilgi(d.hata || 'İşlem yapılamadı.', 'er'); return; }
      rkBilgi(d.molada ? 'Garson molaya alındı.' : 'Garson moladan çıkarıldı.', 'ok'); gyYukle();
    }).catch(function(){ btn.disabled = false; rkBilgi('Sunucuya ulaşılamadı.', 'er'); });
}
// ---- Isı haritası ----
function gyMasaSayi(masaId, gid){ var s = gyD.isi[gyPencere][masaId]; if(!s) return 0; if(gid) return s[gid] || 0; var t = 0; for(var k in s) t += s[k]; return t; }
function gyYuzdeler(gid){
  var mx = 0, y = {};
  GY.masalar.forEach(function(m){ var n = gyMasaSayi(m.id, gid); y[m.id] = n; if(n > mx) mx = n; });
  GY.masalar.forEach(function(m){ y[m.id] = {n: y[m.id], p: mx ? Math.round(y[m.id] * 100 / mx) : 0}; });
  return y;
}
function gyIsiRenk(p){ return p >= 90 ? '#EF4444' : (p >= 70 ? '#F97316' : (p >= 40 ? '#EAB308' : '#3B82F6')); }
var GY_DIS = '#C026D3'; // alan dışı işlem: ısı renklerinde olmayan mor
var GY_PALET = null;
function gyPalet(){
  if(GY_PALET) return GY_PALET;
  var c = document.createElement('canvas'); c.width = 256; c.height = 1;
  var x = c.getContext('2d'), g = x.createLinearGradient(0, 0, 256, 0);
  [[0, '#1D4ED8'], [.3, '#06B6D4'], [.5, '#22C55E'], [.7, '#EAB308'], [.85, '#F97316'], [1, '#EF4444']].forEach(function(s){ g.addColorStop(s[0], s[1]); });
  x.fillStyle = g; x.fillRect(0, 0, 256, 1);
  return (GY_PALET = x.getImageData(0, 0, 256, 1).data);
}
// Zemin ısı katmanı: masadaki işlem yoğunluğu + servis noktasından masaya TAHMİNİ yol.
// Yalnız işlem olan masalara iz düşer; konum takibi yok.
function gyIsiKatman(W, H, noktalar, hub){
  var c = document.createElement('canvas'); c.className = 'gy-isi-katman'; c.width = Math.max(1, Math.round(W)); c.height = Math.max(1, Math.round(H));
  var x = c.getContext('2d'), mx = 0;
  noktalar.forEach(function(p){ if(p.n > mx) mx = p.n; });
  x.fillStyle = 'rgba(0,0,0,.07)'; x.fillRect(0, 0, W, H); // soğuk zemin
  function leke(px, py, r, a){
    var g = x.createRadialGradient(px, py, 0, px, py, r);
    g.addColorStop(0, 'rgba(0,0,0,' + a + ')'); g.addColorStop(1, 'rgba(0,0,0,0)');
    x.fillStyle = g; x.beginPath(); x.arc(px, py, r, 0, 7); x.fill();
  }
  var sirali = noktalar.filter(function(p){ return p.n > 0; }).sort(function(a, b){ return a.n - b.n; });
  sirali.forEach(function(p){
    var o = p.n / mx;
    if(hub){
      var dx = p.x - hub.x, dy = p.y - hub.y, k = Math.max(1, Math.ceil(Math.hypot(dx, dy) / Math.max(5, p.r * .3)));
      for(var i = 0; i <= k; i++) leke(hub.x + dx * i / k, hub.y + dy * i / k, p.r * .5, .04 + .12 * o);
    }
    leke(p.x, p.y, p.r * (1 + .7 * o), .3 + .6 * o);
  });
  var d = x.getImageData(0, 0, c.width, c.height), v = d.data, pal = gyPalet();
  for(var i = 0; i < v.length; i += 4){
    var a = v[i + 3]; if(!a) continue;
    var j = Math.min(255, a) * 4;
    v[i] = pal[j]; v[i + 1] = pal[j + 1]; v[i + 2] = pal[j + 2]; v[i + 3] = Math.min(210, 45 + a);
  }
  x.putImageData(d, 0, 0);
  // En yoğun yollara yön oku (servis noktasından masaya)
  if(hub){
    x.setLineDash([7, 6]); x.lineWidth = 2; x.strokeStyle = 'rgba(255,255,255,.9)'; x.fillStyle = 'rgba(255,255,255,.9)';
    sirali.slice(-4).forEach(function(p){
      var dx = p.x - hub.x, dy = p.y - hub.y, L = Math.hypot(dx, dy); if(L < p.r * 1.5) return;
      var ux = dx / L, uy = dy / L, sx = p.x - ux * p.r * .9, sy = p.y - uy * p.r * .9;
      x.beginPath(); x.moveTo(hub.x + ux * 10, hub.y + uy * 10); x.lineTo(sx, sy); x.stroke();
      x.beginPath(); x.moveTo(sx + ux * 8, sy + uy * 8); x.lineTo(sx - uy * 5, sy + ux * 5); x.lineTo(sx + uy * 5, sy - ux * 5); x.fill();
    });
    x.setLineDash([]);
  }
  return c;
}
// Garsonun sorumlu masaları. Atama yoksa null: tüm masalara bakar, alan dışı hesaplanmaz.
function gyKendiMasalar(gid){
  var a = GY.atama[gid]; if(!a || (!a.b.length && !a.m.length)) return null;
  var k = {};
  GY.masalar.forEach(function(m){ if(a.b.indexOf(m.b) >= 0 || a.m.indexOf(m.id) >= 0) k[m.id] = 1; });
  return k;
}
function gyAlanAd(id){ var a = GY.alanlar.find(function(x){ return x.id === id; }); return a ? a.ad : 'Bölgesiz'; }
function gyUyum(gid){
  var k = gyKendiMasalar(gid); if(!k) return null;
  var r = {ic: 0, dis: 0, top: 0, oran: null, bolge: {}, liste: []};
  GY.masalar.forEach(function(m){
    var n = gyMasaSayi(m.id, gid); if(!n) return;
    if(k[m.id]) r.ic += n;
    else { r.dis += n; r.bolge[m.b] = (r.bolge[m.b] || 0) + n; r.liste.push({m: m, n: n}); }
  });
  r.top = r.ic + r.dis;
  // Alan dışı işlem varsa %100 görünmesin
  if(r.top) r.oran = r.dis ? Math.min(99, Math.round(r.ic * 100 / r.top)) : 100;
  r.liste.sort(function(a, b){ return b.n - a.n; });
  return r;
}
function gyUyumRenk(p){ return p >= 80 ? '#10B981' : (p >= 50 ? '#F59E0B' : '#EF4444'); }
// Tüm Salon kartı: en az 720px isteyen ortak ölçü yerine kart genişliğine sığar
function gyMiniOlcu(cer, dun, yer, oran){
  var W = cer.clientWidth, H = Math.round(Math.max(W / (oran || 2.35), yer.R * 78));
  dun.style.width = W + 'px'; dun.style.height = H + 'px';
  dun.style.setProperty('--ts', Math.min(W * .12, W * .88 / yer.C * .66, H * .92 / yer.R * .9) + 'px');
  cer.style.height = H + (cer.offsetHeight - cer.clientHeight) + 'px';
}
function gyIsiCiz(cer, dun, gid, alan, mini){
  if(!cer || !cer.clientWidth) return;
  var list = GY.masalar.filter(function(m){ return m.b === alan; }), yer = idzYerlesim(list), y = gyYuzdeler(gid), kendi = gid ? gyKendiMasalar(gid) : null;
  var oran = (GY.plan._alan && GY.plan._alan[alan] || {}).oran;
  if(mini) gyMiniOlcu(cer, dun, yer, oran); else idzDunyaOlcu(cer, dun, yer, 1, oran);
  var dekor = idzDekorListe(GY.plan, alan, GY.alanlar.length ? GY.alanlar[0].id : 0), h = idzObjelerHtml(dekor, -1);
  if(!list.length) h += mini ? '<div class="gy-mini-bos">Bu alanda masa yok</div>' : '<div class="idz-plan-bos"><b>Bu alanda masa yok</b><span>Masaları Masa Planı sekmesinden ekleyebilirsiniz.</span><button type="button" class="idz-btn dolu" onclick="idzSekme(\'plan\')">Masa Planı</button></div>';
  list.forEach(function(m){
    var pl = (GY.plan[m.id] && +GY.plan[m.id].b === m.b) ? GY.plan[m.id] : null, kay = pl || yer.poz[m.id], d = y[m.id], mboy = (pl && pl.boy) || 1;
    var g = idzMasaSvg({kapasite: m.kapasite, durum: d.p >= 90 ? 'dolu' : 'bos'}, idzSekil(m, pl), (pl && pl.rot) || 0);
    var alanDisi = kendi && !kendi[m.id], isaret = alanDisi ? (d.n ? ' gy-dis' : ' gy-soluk') : '';
    h += '<button type="button" class="idz-masa gy-m'+(g.yuvarlak ? ' yuv' : '')+isaret+'" data-ad="'+gyE(gyMasaAd(m))+'" data-n="'+d.n+'" data-p="'+d.p+'" data-dis="'+(alanDisi ? 1 : 0)+'" style="left:'+(kay.x / 10)+'%;top:'+(kay.y / 10)+'%;width:calc(var(--ts) * '+(g.oran * mboy)+');--mb:'+mboy+'" title="'+gyE(gyMasaAd(m))+' · '+d.n+' işlem · %'+d.p+(alanDisi ? ' · alan dışı' : '')+'">'+g.svg+
      '<span class="et"><b>'+gyE(gyMasaEt(m))+'</b><small>%'+d.p+'</small></span>'+(alanDisi && d.n ? '<span class="gy-dis-rozet">'+d.n+'</span>' : '')+'</button>';
  });
  dun.innerHTML = h;
  // Isı katmanı masaların altında; yol başı: mutfak > bar > kasa > giriş (alanda yoksa yalnız masa lekeleri)
  var W = parseFloat(dun.style.width) || dun.clientWidth, H = parseFloat(dun.style.height) || dun.clientHeight;
  var ts = parseFloat(dun.style.getPropertyValue('--ts')) || 80, hub = null;
  ['mutfak', 'bar', 'kasa', 'giris'].some(function(t){ var o = dekor.find(function(x){ return x.t === t; }); if(o) hub = {x: o.x / 1000 * W, y: o.y / 1000 * H}; return !!o; });
  var noktalar = list.map(function(m){
    var pl = (GY.plan[m.id] && +GY.plan[m.id].b === m.b) ? GY.plan[m.id] : null, kay = pl || yer.poz[m.id];
    return {x: kay.x / 1000 * W, y: kay.y / 1000 * H, n: y[m.id].n, r: ts * .85};
  });
  dun.insertBefore(gyIsiKatman(W, H, noktalar, hub), dun.firstChild);
}
function gySalonCiz(gid){
  var kap = document.getElementById('gySalon');
  if(!kap.offsetWidth) return;
  var kendi = gid ? gyKendiMasalar(gid) : null, y = gyYuzdeler(gid);
  kap.innerHTML = GY.alanlar.map(function(a){
    var l = GY.masalar.filter(function(m){ return m.b === a.id; }), top = 0, dis = 0, ks = 0;
    l.forEach(function(m){ top += y[m.id].n; if(kendi){ if(kendi[m.id]) ks++; else dis += y[m.id].n; } });
    var rozet = '';
    if(ks) rozet += '<span class="gy-rozet kendi">'+(ks === l.length ? 'Kendi alanı' : 'Kendi alanı · ' + ks + ' masa')+'</span>';
    if(dis) rozet += '<span class="gy-rozet dis">'+dis+' alan dışı</span>';
    return '<div class="gy-bolge'+(kendi ? (ks ? ' kendi' : ' diger') : '')+'" data-alan="'+a.id+'">'+
      '<button type="button" class="gy-bolge-bas" data-alan-ac="'+a.id+'" title="'+gyE(a.ad)+' alanını büyüt"><b>'+gyE(a.ad)+'</b>'+rozet+'<em>'+top+' işlem</em><span class="material-icons-outlined">open_in_full</span></button>'+
      '<div class="idz-cerceve gy-mini"><div class="idz-dunya"></div></div></div>';
  }).join('');
  kap.querySelectorAll('.gy-bolge').forEach(function(el){
    gyIsiCiz(el.querySelector('.gy-mini'), el.querySelector('.idz-dunya'), gid, +el.getAttribute('data-alan'), true);
  });
}
function gyAlanSeritCiz(){
  var s = document.getElementById('gyAlanSerit');
  if(GY.alanlar.length < 2){ s.style.display = 'none'; return; }
  s.innerHTML = [{id: -1, ad: 'Tüm Salon'}].concat(GY.alanlar).map(function(a){
    return '<button type="button" role="tab" class="gy-alan-btn'+(a.id === gyIsiAlan ? ' aktif' : '')+'" data-alan-ac="'+a.id+'">'+(a.id === -1 ? '<span class="material-icons-outlined">dashboard</span>' : '')+gyE(a.ad)+'</button>';
  }).join('');
}
function gyUyumCiz(gid){
  var k = document.getElementById('gyUyum');
  if(!gid){ k.innerHTML = ''; return; }
  var g = gyD.garsonlar.find(function(x){ return x.id === gid; }), ad = g ? gyE(g.ad) : 'Bu garson', u = gyUyum(gid);
  if(!u){ k.innerHTML = '<div class="gy-uyum bilgi"><span class="material-icons-outlined">info</span><p><b>'+ad+' için alan atanmamış.</b> Alan atanınca kendi alanı ve alan dışı işlemleri burada görünür.</p><button type="button" class="idz-btn dolu kucuk" onclick="garsonAtaAc('+gid+')">Alan Ata</button></div>'; return; }
  if(!u.top){ k.innerHTML = '<div class="gy-uyum bilgi"><span class="material-icons-outlined">info</span><p><b>'+ad+'</b> seçilen aralıkta işlem yapmamış.</p></div>'; return; }
  var bolge = Object.keys(u.bolge).sort(function(a, b){ return u.bolge[b] - u.bolge[a]; }).map(function(b){ return gyE(gyAlanAd(+b)) + ': ' + u.bolge[b]; }).join(', ');
  k.innerHTML = '<div class="gy-uyum"><strong style="color:'+gyUyumRenk(u.oran)+'">%'+u.oran+'</strong>'+
    '<div class="m"><b>'+ad+' işlemlerinin %'+u.oran+'\'i kendi alanında</b>'+
    '<span>'+u.ic+' işlem kendi alanında, '+u.dis+' işlem alan dışında'+(bolge ? ' ('+bolge+')' : '')+'</span>'+
    '<span class="c"><i style="width:'+u.oran+'%;background:'+gyUyumRenk(u.oran)+'"></i></span></div>'+
    '<div class="ef"><span><i style="background:'+GY_DIS+'"></i>Alan dışı işlem</span><span><i class="s"></i>Alan dışı, işlem yok</span></div></div>';
}
function gyIlisteHtml(m, n, p, renk){
  return '<div class="gy-iliste"><b title="'+gyE(gyMasaAd(m))+'">'+gyE(gyMasaAd(m))+'</b><span class="c"><i style="width:'+p+'%;background:'+renk+'"></i></span><em>'+n+'</em></div>';
}
function gyYanCiz(gid){
  var h = '', bas = function(ikon, t){ return '<div class="gy-ef-bas" style="margin:0 0 8px"><span class="material-icons-outlined idz-mor">'+ikon+'</span>'+t+'</div>'; };
  h += '<div class="gy-yan">'+bas('palette', 'Isı Haritası Renkleri')+'<div class="gy-renk"><i></i><span><b>En çok işlem</b><b>Orta</b><b>En az</b></span></div>'+
    '<p class="gy-yan-not">Yollar tahminidir: servis noktasından (mutfak, bar, kasa ya da giriş) işlem yapılan masaya çizilir. Garsonun gerçek konumu ölçülmez.</p></div>';
  if(!gid){
    var sat = gyD.garsonlar.map(function(g){ var u = gyUyum(g.id); return {g: g, oran: u ? u.oran : null}; })
      .sort(function(a, b){ return (a.oran === null ? 999 : a.oran) - (b.oran === null ? 999 : b.oran); });
    h += '<div class="gy-yan">'+bas('verified', 'Alan Uyumu');
    h += sat.length ? sat.map(function(s){
      var o = s.oran, ipucu = o === null ? 'Alan atanmamış ya da bu aralıkta işlem yok' : 'İşlemlerinin %'+o+'\'i kendi alanında';
      return '<button type="button" class="gy-iliste gy-uyum-sat" data-garson="'+s.g.id+'" title="'+ipucu+'"><b>'+gyE(s.g.ad)+'</b><span class="c"><i style="width:'+(o || 0)+'%;background:'+gyUyumRenk(o || 0)+'"></i></span><em>'+(o === null ? '—' : '%' + o)+'</em></button>';
    }).join('') + '<p class="gy-yan-not">— : alan atanmamış ya da bu aralıkta işlem yok.</p>' : '<div class="idz-bos">Aktif garson yok.</div>';
    h += '</div>';
  } else {
    var u = gyUyum(gid);
    if(u && u.liste.length){
      var mx = u.liste[0].n;
      h += '<div class="gy-yan">'+bas('warning_amber', 'Alan Dışı Masalar')+u.liste.slice(0, 8).map(function(x){ return gyIlisteHtml(x.m, x.n, Math.round(x.n * 100 / mx), GY_DIS); }).join('')+'</div>';
    }
  }
  var y = gyYuzdeler(gid), l = GY.masalar.slice().sort(function(a, b){ return y[b.id].n - y[a.id].n; }).filter(function(m){ return y[m.id].n > 0; }).slice(0, 8);
  h += '<div class="gy-yan">'+bas('format_list_numbered', 'En Yoğun Masalar');
  h += l.length ? l.map(function(m){ return gyIlisteHtml(m, y[m.id].n, y[m.id].p, gyIsiRenk(y[m.id].p)); }).join('') : '<div class="idz-bos">Bu aralıkta işlem yok.</div>';
  document.getElementById('gyIsiYan').innerHTML = h + '</div>';
}
function gyIsiCizHepsi(){
  gyIsiCiz(document.getElementById('gyCerceve1'), document.getElementById('gyDunya1'), 0, gyAlan);
  var tum = gyIsiAlan === -1, cer = document.getElementById('gyCerceve2');
  document.getElementById('gySalon').style.display = tum ? '' : 'none';
  cer.style.display = tum ? 'none' : '';
  if(tum) gySalonCiz(gyIsiGarson); else gyIsiCiz(cer, document.getElementById('gyDunya2'), gyIsiGarson, gyIsiAlan);
  gyAlanSeritCiz();
  gyUyumCiz(gyIsiGarson);
  gyYanCiz(gyIsiGarson);
  gyOneriCiz();
}
function gyOneriCiz(){
  var y = gyYuzdeler(0), en = null;
  GY.masalar.forEach(function(m){ if(y[m.id].n > 0 && (!en || y[m.id].n > y[en.id].n)) en = m; });
  var kut = document.getElementById('gyOneri'), bas = '<div class="b"><span class="material-icons-outlined">lightbulb</span>Öneri</div>';
  if(!en){ kut.innerHTML = bas + '<p>Bu aralıkta yoğun masa yok, salon sakin.</p>'; return; }
  var yuk = gyD.aktif_yuk || {}, ad = {}, musait = null;
  gyD.garsonlar.forEach(function(g){ ad[g.id] = g.ad; if(g.durum === 'aktif' && (musait === null || (yuk[g.id] || 0) < (yuk[musait] || 0))) musait = g.id; });
  var kisi = musait !== null ? gyE(ad[musait]) + ' en az yoğun garson.' : 'Şu an aktif garson görünmüyor.';
  kut.innerHTML = bas + '<p><b>'+gyE(gyMasaAd(en))+'</b> bu aralıkta en yoğun masa. '+kisi+'</p><button type="button" class="idz-btn dolu kucuk" onclick="'+(musait !== null ? 'garsonAtaAc('+musait+')' : "gySekme('garsonlar')")+'">Garson Ata</button>';
}
function gySecimDoldur(){
  document.querySelectorAll('.gy-alan-sec').forEach(function(s){
    s.innerHTML = GY.alanlar.map(function(a){ return '<option value="'+a.id+'">'+gyE(a.ad)+'</option>'; }).join('');
    s.value = gyAlan;
    s.addEventListener('change', function(){ gyAlan = +s.value; document.querySelectorAll('.gy-alan-sec').forEach(function(x){ x.value = gyAlan; }); gyIsiCizHepsi(); });
  });
  if(GY.alanlar.length < 2) document.querySelectorAll('[data-alan-kutu]').forEach(function(k){ k.style.display = 'none'; });
  document.querySelectorAll('.gy-pencere-sec').forEach(function(s){ s.addEventListener('change', function(){ gyPencere = s.value; document.querySelectorAll('.gy-pencere-sec').forEach(function(x){ x.value = gyPencere; }); gyIsiCizHepsi(); }); });
  var gs = gyD.garsonlar.map(function(g){ return '<option value="'+g.id+'">'+gyE(g.ad)+'</option>'; }).join('');
  var a = document.getElementById('gyIsiGarson'), b = document.getElementById('gyOlayGarson');
  a.insertAdjacentHTML('beforeend', gs); b.insertAdjacentHTML('beforeend', gs);
  a.addEventListener('change', function(){ gyIsiGarson = +a.value; gyIsiCizHepsi(); });
  b.addEventListener('change', function(){ gyOlayGarson = +b.value; gyOlayCiz(); });
  document.getElementById('gyStatSec').addEventListener('change', function(){ gyStat = this.value; gyYukle(); });
  document.querySelectorAll('.gy-perf-sec').forEach(function(s){ s.addEventListener('change', function(){ gyPerf = s.value; document.querySelectorAll('.gy-perf-sec').forEach(function(x){ x.value = gyPerf; }); gyYukle(); }); });
}
function gyHepsiniCiz(){ gyKartCiz(); gyGarsonOzetCiz(); gyHalkaCiz(); gyPerfCiz(); gyOlayCiz(); gyGarsonKartCiz(); gyIsiCizHepsi(); }
function gyYukle(){
  fetch('/garson-yonetimi/veri?stat=' + gyStat + '&perf=' + gyPerf, {headers:{'Accept':'application/json'}, cache:'no-store'})
    .then(function(r){ return r.json(); }).then(function(d){
      if(d.ok != 1) return;
      gyD = d; gyHepsiniCiz();
      var z = document.querySelector('.idz-ust-ikon b');
      if(z) z.textContent = d.cagri, z.style.display = d.cagri > 0 ? '' : 'none';
    }).catch(function(){});
}
document.addEventListener('click', function(e){
  var m = e.target.closest('.gy-m');
  if(m){ rkBilgi(m.getAttribute('data-ad') + ': ' + m.getAttribute('data-n') + ' işlem, %' + m.getAttribute('data-p') + ' yoğunluk' + (m.getAttribute('data-dis') === '1' ? ' · alan dışı' : ''), 'info'); return; }
  var ac = e.target.closest('.gy [data-alan-ac]'), kart = e.target.closest('.gy-mini');
  if(ac || kart){ gyIsiAlan = ac ? +ac.getAttribute('data-alan-ac') : +kart.closest('.gy-bolge').getAttribute('data-alan'); gyIsiCizHepsi(); return; }
  var g = e.target.closest('.gy [data-garson]');
  if(g){ gyIsiGarson = +g.getAttribute('data-garson'); document.getElementById('gyIsiGarson').value = gyIsiGarson; gyIsiCizHepsi(); }
});
(function(){
  gySecimDoldur();
  gyHepsiniCiz();
  try{
    // Eski /garson-yonetimi#isi bağlantıları buraya ?gy=1 ile gelir; adres çubuğundaki işaret alt sekmeyi seçer
    var h = location.hash.slice(1), gy = /[?&]gy=1\b/.test(location.search);
    if(GY_SEKMELER.indexOf(h) >= 0 || h === 'garson' || gy){ idzSekme('garson'); gySekme(GY_SEKMELER.indexOf(h) >= 0 ? h : (sessionStorage.getItem('gy_sekme') || 'genel')); }
    else if(gyAcik()){ var s = sessionStorage.getItem('gy_sekme'); if(s) gySekme(s); }
    if(gy && history.replaceState) history.replaceState(null, '', location.pathname + location.hash);
  }catch(e){}
  setInterval(function(){ if(gyAcik() && !document.hidden && !document.querySelector('.idz-modal.acik') && document.getElementById('gataModal').style.display !== 'flex') gyYukle(); }, 30000);
  var zaman = null;
  if(window.ResizeObserver) new ResizeObserver(function(){ if(!gyAcik()) return; clearTimeout(zaman); zaman = setTimeout(gyIsiCizHepsi, 80); }).observe(document.querySelector('.gy'));
})();
</script>
@endif

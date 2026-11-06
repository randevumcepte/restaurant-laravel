@extends('layout.app')
@section('title', 'Garson Yönetimi')
@section('rk_ikon', 'support_agent')
@section('baslik', 'Garson Yönetimi')
@section('rk_alt', 'Garsonlarınızı yönetin, masa aktivitelerini takip edin ve performanslarını analiz edin.')

@section('rk_araclar')
  @include('layout._idz_ust_arac')
@endsection

@section('content')
@php
  $bolgeler = $bolgeler ?? collect();
  $masalar = $masalar ?? collect();
  $garsonlar = $garsonlar ?? collect();
  $masaByBolge = $masalar->groupBy(fn($m) => $m->bolge_id ?? 0);
  $alanlar = $bolgeler->map(fn($b) => ['id' => (int) $b->id, 'ad' => $b->ad])->values();
  if (isset($masaByBolge[0])) $alanlar->push(['id' => 0, 'ad' => 'Bölgesiz']);
  $gyVeri = [
    'alanlar' => $alanlar->all(),
    'masalar' => $masalar->map(fn($m) => ['id' => (int) $m->id, 'ad' => $m->ad, 'kapasite' => (int) ($m->kapasite ?? 4), 'sekil' => $m->sekil ?? 'kare', 'b' => (int) ($m->bolge_id ?? 0)])->values()->all(),
    'plan' => (object) ($plan ?? []),
    'fotolar' => (object) collect($fotolar ?? [])->filter(fn($f) => $f && str_starts_with((string) $f, 'data:image'))->all(),
    'atama' => (object) $garsonlar->mapWithKeys(fn($g) => [$g->id => ['b' => $g->bolge_idler ?? [], 'm' => $g->masa_idler ?? []]])->all(),
    'panel' => $panel ?? [],
  ];
  $gyJson = json_encode($gyVeri, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp
<div class="idz gy">
@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="gy-ust">
  <nav class="gy-yol" aria-label="Sayfa yolu">
    <a href="/isletme-duzeni"><span class="material-icons-outlined">grid_view</span>İşletme Düzeni</a>
    <span class="material-icons-outlined">chevron_right</span>
    <b>Garson Yönetimi</b>
  </nav>
  <button type="button" class="idz-btn dolu gy-yeni" onclick="idzModal('gyYeniGarson')"><span class="material-icons-outlined">add</span>Yeni Garson Ekle</button>
</div>

<div class="idz-sekmeler" role="tablist">
  <button type="button" class="idz-sekme aktif" data-sekme="genel" role="tab"><span class="material-icons-outlined">bar_chart</span>Genel Bakış</button>
  <button type="button" class="idz-sekme" data-sekme="garsonlar" role="tab"><span class="material-icons-outlined">person</span>Garsonlar</button>
  <button type="button" class="idz-sekme" data-sekme="aktivite" role="tab"><span class="material-icons-outlined">radar</span>Aktivite Takibi</button>
  <button type="button" class="idz-sekme" data-sekme="isi" role="tab"><span class="material-icons-outlined">local_fire_department</span>Isı Haritası</button>
  <button type="button" class="idz-sekme" data-sekme="rapor" role="tab"><span class="material-icons-outlined">query_stats</span>Performans Raporu</button>
  <button type="button" class="idz-sekme" data-sekme="ayarlar" role="tab"><span class="material-icons-outlined">settings</span>Ayarlar</button>
</div>

{{-- ============ GENEL BAKIŞ ============ --}}
<section class="idz-panel aktif" data-panel="genel">
  <div class="gy-kartlar" id="gyKartlar"></div>

  <div class="gy-orta">
    <div class="idz-kart gy-isi-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">blur_on</span><h3>Masa Isı Haritası</h3>
        <span class="gy-canli"><i></i>Canlı</span>
        <span class="idz-esnek"></span>
        <div class="idz-sec-kutu" data-alan-kutu><span class="material-icons-outlined">home</span><select class="gy-alan-sec" aria-label="Alan seç"></select></div>
        <div class="idz-sec-kutu"><span class="material-icons-outlined">schedule</span><select class="gy-pencere-sec" aria-label="Zaman aralığı"><option value="saat">Son 1 Saat</option><option value="bugun">Bugün</option><option value="hafta">Bu Hafta</option></select></div>
      </div>
      <div class="gy-isi-govde">
        <div class="idz-cerceve gy-cerceve" id="gyCerceve1"><div class="idz-dunya" id="gyDunya1"></div></div>
        <div class="gy-efsane">
          <div class="gy-ef-bas"><span class="material-icons-outlined idz-mor">blur_on</span>Masa Yoğunluğu</div>
          <div class="gy-ef"><i style="--r:#C026D3"></i><span>%90 – 100<small>Çok Yoğun</small></span></div>
          <div class="gy-ef"><i style="--r:#F97316"></i><span>%70 – 90<small>Yoğun</small></span></div>
          <div class="gy-ef"><i style="--r:#6C8CF5"></i><span>%40 – 70<small>Orta</small></span></div>
          <div class="gy-ef"><i style="--r:#C7D2FE"></i><span>%0 – 40<small>Sakin</small></span></div>
          <div class="gy-oneri" id="gyOneri"></div>
        </div>
      </div>
    </div>
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">groups</span><h3>Garsonlar &amp; Aktiviteleri</h3>
        <button type="button" class="idz-link" onclick="idzSekme('garsonlar')">Tümünü Gör<span class="material-icons-outlined">arrow_forward</span></button>
      </div>
      <div id="gyGarsonOzet"></div>
    </div>
  </div>

  <div class="gy-alt">
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">show_chart</span><h3>Anlık İstatistikler</h3>
        <select class="gy-mini-sec" id="gyStatSec" aria-label="İstatistik dönemi"><option value="gunluk">Bugün</option><option value="haftalik">Bu Hafta</option><option value="aylik">Bu Ay</option></select>
      </div>
      <div class="gy-halkalar" id="gyHalkalar"></div>
    </div>
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">emoji_events</span><h3>Garson Performansı</h3>
        <select class="gy-mini-sec gy-perf-sec" aria-label="Performans dönemi"><option value="gunluk">Bugün</option><option value="haftalik" selected>Bu Hafta</option><option value="aylik">Bu Ay</option></select>
      </div>
      <div id="gyPerfOzet"></div>
    </div>
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">schedule</span><h3>Son Aktiviteler</h3>
        <button type="button" class="idz-link" onclick="idzSekme('aktivite')">Tümünü Gör<span class="material-icons-outlined">arrow_forward</span></button>
      </div>
      <div class="idz-hareket" id="gyOlayOzet"></div>
    </div>
  </div>
</section>

{{-- ============ GARSONLAR ============ --}}
<section class="idz-panel" data-panel="garsonlar">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">person</span><h3>Garsonlar</h3><span class="idz-rozet" id="gySay"></span></div>
    <p class="idz-acik">Bugünkü rakamlar. Bölge seçersen o bölgenin tüm masaları dahildir; hiç seçmezsen garson tüm masalara bakar.</p>
    <div class="gy-garson-grid" id="gyGarsonKart"></div>
  </div>
</section>

{{-- ============ AKTİVİTE ============ --}}
<section class="idz-panel" data-panel="aktivite">
  <div class="idz-kart">
    <div class="idz-kart-bas">
      <span class="material-icons-outlined idz-mor">radar</span><h3>Aktivite Takibi</h3>
      <span class="gy-canli"><i></i>Canlı</span>
      <span class="idz-esnek"></span>
      <div class="idz-sec-kutu"><span class="material-icons-outlined">person</span><select id="gyOlayGarson" aria-label="Garson seç"><option value="0">Tüm personel</option></select></div>
    </div>
    <p class="idz-acik">Son 12 saatteki sipariş, servis, ödeme, mesai ve mola hareketleri.</p>
    <div class="idz-hareket gy-olay-tam" id="gyOlayTam"></div>
  </div>
</section>

{{-- ============ ISI HARİTASI ============ --}}
<section class="idz-panel" data-panel="isi">
  <div class="idz-kart">
    <div class="idz-kart-bas">
      <span class="material-icons-outlined idz-mor">local_fire_department</span><h3>Isı Haritası</h3>
      <span class="idz-esnek"></span>
      <div class="idz-sec-kutu"><span class="material-icons-outlined">schedule</span><select class="gy-pencere-sec" aria-label="Zaman aralığı"><option value="saat">Son 1 Saat</option><option value="bugun">Bugün</option><option value="hafta">Bu Hafta</option></select></div>
      <div class="idz-sec-kutu"><span class="material-icons-outlined">person</span><select id="gyIsiGarson" aria-label="Garson seç"><option value="0">Tüm garsonlar</option></select></div>
    </div>
    <p class="idz-acik">Sipariş, servis ve hesap işlemlerinden hesaplanır; garsonun yürüdüğü yeri değil, işlem yaptığı masayı gösterir. Yüzde, seçilen aralıktaki en yoğun masaya göredir (en yoğun masa %100).</p>
    <div class="gy-alan-serit" id="gyAlanSerit" role="tablist" aria-label="Alan seç"></div>
    <div id="gyUyum"></div>
    <div class="gy-isi-buyuk">
      <div>
        <div class="gy-salon" id="gySalon"></div>
        <div class="idz-cerceve gy-cerceve" id="gyCerceve2"><div class="idz-dunya" id="gyDunya2"></div></div>
      </div>
      <div id="gyIsiYan"></div>
    </div>
  </div>
</section>

{{-- ============ PERFORMANS RAPORU ============ --}}
<section class="idz-panel" data-panel="rapor">
  <div class="idz-kart">
    <div class="idz-kart-bas">
      <span class="material-icons-outlined idz-mor">query_stats</span><h3>Performans Raporu</h3>
      <select class="gy-mini-sec gy-perf-sec" aria-label="Rapor dönemi"><option value="gunluk">Bugün</option><option value="haftalik" selected>Bu Hafta</option><option value="aylik">Bu Ay</option></select>
    </div>
    <p class="idz-acik">Skor, dönemin en iyi garsonuna göre hesaplanır: ciro %50, ürün sayısı %30, adisyon sayısı %20.</p>
    <div class="gy-tablo-kap"><table class="gy-tablo"><thead><tr><th>#</th><th>Garson</th><th>Skor</th><th>Adisyon</th><th>Ciro</th><th>Ürün</th><th>Ort. Adisyon</th><th>Ort. Masa Süresi</th><th>Adım</th></tr></thead><tbody id="gyRapor"></tbody></table></div>
    <a class="idz-link" style="margin-top:12px" href="/garson-performans">Detaylı Garson Karnesi<span class="material-icons-outlined">arrow_forward</span></a>
  </div>
</section>

{{-- ============ AYARLAR ============ --}}
<section class="idz-panel" data-panel="ayarlar">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">settings</span><h3>Ayarlar</h3></div>
    <div class="idz-ayar-grid">
      @foreach ([
        ['/isletme-duzeni', 'grid_view', 'İşletme Düzeni', 'Bölge, masa ve kasa düzeni'],
        ['/personel', 'badge', 'Personel & Maaş', 'Kişi bilgileri, PIN, aktif/pasif'],
        ['/ik-vardiya', 'calendar_month', 'Vardiya Planlama', 'Haftalık garson vardiyaları'],
        ['/puantaj', 'schedule', 'Puantaj', 'Mesai giriş ve çıkışları'],
        ['/garson-cagrilari', 'notifications_active', 'Garson Çağrıları', 'Masadan gelen çağrılar'],
        ['/garson-performans', 'emoji_events', 'Garson Karnesi', 'Saatlik dağılım ve adım'],
      ] as [$u, $ik, $t, $al])
        <a class="idz-ayar" href="{{ $u }}"><span class="material-icons-outlined">{{ $ik }}</span><span><b>{{ $t }}</b><small>{{ $al }}</small></span></a>
      @endforeach
    </div>
  </div>
</section>
</div>

{{-- Yeni garson --}}
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

@include('layout._garson_ata_panelleri')
@include('layout._masa_plan_cizim')

<script>
var GY = {!! $gyJson !!};
var gyD = GY.panel, gyPencere = 'saat', gyAlan = GY.alanlar.length ? GY.alanlar[0].id : 0, gyIsiGarson = 0, gyOlayGarson = 0;
var gyStat = 'gunluk', gyPerf = 'haftalik';
// Isı Haritası sekmesi: -1 = Tüm Salon
var gyIsiAlan = GY.alanlar.length > 1 ? -1 : gyAlan;
function gyE(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function gySure(sn){ if(sn == null) return '—'; var d = Math.floor(sn / 60), s = sn % 60; return d ? d + ' dk ' + s + ' sn' : s + ' sn'; }
function gyPara(v){ return Math.round(v).toLocaleString('tr-TR') + ' ₺'; }
function gyMasaAd(m){ return /^\d+$/.test(m.ad) ? 'Masa ' + m.ad : m.ad; }
// Harita etiketi: "Bahçe 3" gibi adlarda numara (bölge zaten belli, tam ad ipucunda)
function gyMasaEt(m){ var s = /^\D*\s(\d+)$/.exec(m.ad); return s ? s[1] : m.ad; }
function idzModal(id){ var m = document.getElementById(id); if(!m) return; m.classList.add('acik'); var i = m.querySelector('input[type=text]'); if(i) setTimeout(function(){ i.focus(); }, 50); }
function idzModalKapat(el){ var m = el.closest('.idz-modal'); if(m) m.classList.remove('acik'); }
document.querySelectorAll('.idz-modal').forEach(function(m){ m.addEventListener('click', function(e){ if(e.target === m) m.classList.remove('acik'); }); });
document.addEventListener('keydown', function(e){ if(e.key === 'Escape') document.querySelectorAll('.idz-modal.acik').forEach(function(m){ m.classList.remove('acik'); }); });
function idzSekme(ad){
  var b = document.querySelector('.idz-sekme[data-sekme="'+ad+'"]'); if(!b) return;
  document.querySelectorAll('.idz-sekme').forEach(function(x){ x.classList.toggle('aktif', x === b); });
  document.querySelectorAll('.idz-panel').forEach(function(p){ p.classList.toggle('aktif', p.getAttribute('data-panel') === ad); });
  try{ sessionStorage.setItem('gy_sekme', ad); }catch(e){}
  gyIsiCizHepsi();
}
document.querySelectorAll('.idz-sekme').forEach(function(b){ b.addEventListener('click', function(){ idzSekme(b.getAttribute('data-sekme')); }); });

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
function gyIsiRenk(p){ return p >= 90 ? '#C026D3' : (p >= 70 ? '#F97316' : (p >= 40 ? '#6C8CF5' : '#C7D2FE')); }
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
  var h = idzObjelerHtml(idzDekorListe(GY.plan, alan, GY.alanlar.length ? GY.alanlar[0].id : 0), -1);
  if(!list.length) h += mini ? '<div class="gy-mini-bos">Bu alanda masa yok</div>' : '<div class="idz-plan-bos"><b>Bu alanda masa yok</b><span>Masaları İşletme Düzeni\'nden ekleyebilirsiniz.</span><a class="idz-btn dolu" href="/isletme-duzeni">İşletme Düzeni</a></div>';
  list.forEach(function(m){
    var pl = (GY.plan[m.id] && +GY.plan[m.id].b === m.b) ? GY.plan[m.id] : null, kay = pl || yer.poz[m.id], d = y[m.id], mboy = (pl && pl.boy) || 1;
    var g = idzMasaSvg({kapasite: m.kapasite, durum: d.p >= 90 ? 'dolu' : 'bos'}, idzSekil(m, pl), (pl && pl.rot) || 0);
    var alanDisi = kendi && !kendi[m.id], isaret = alanDisi ? (d.n ? ' gy-dis' : ' gy-soluk') : '';
    h += '<button type="button" class="idz-masa gy-m'+(g.yuvarlak ? ' yuv' : '')+isaret+'" data-ad="'+gyE(gyMasaAd(m))+'" data-n="'+d.n+'" data-p="'+d.p+'" data-dis="'+(alanDisi ? 1 : 0)+'" style="left:'+(kay.x / 10)+'%;top:'+(kay.y / 10)+'%;width:calc(var(--ts) * '+(g.oran * mboy)+');--mb:'+mboy+'" title="'+gyE(gyMasaAd(m))+' · '+d.n+' işlem · %'+d.p+(alanDisi ? ' · alan dışı' : '')+'">'+g.svg+
      '<i class="gy-parlama" style="--r:'+(alanDisi && d.n ? '#EF4444' : gyIsiRenk(d.p))+'"></i><span class="et"><b>'+gyE(gyMasaEt(m))+'</b><small>%'+d.p+'</small></span>'+(alanDisi && d.n ? '<span class="gy-dis-rozet">'+d.n+'</span>' : '')+'</button>';
  });
  dun.innerHTML = h;
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
    '<div class="ef"><span><i style="background:#EF4444"></i>Alan dışı işlem</span><span><i class="s"></i>Alan dışı, işlem yok</span></div></div>';
}
function gyIlisteHtml(m, n, p, renk){
  return '<div class="gy-iliste"><b title="'+gyE(gyMasaAd(m))+'">'+gyE(gyMasaAd(m))+'</b><span class="c"><i style="width:'+p+'%;background:'+renk+'"></i></span><em>'+n+'</em></div>';
}
function gyYanCiz(gid){
  var h = '', bas = function(ikon, t){ return '<div class="gy-ef-bas" style="margin:0 0 8px"><span class="material-icons-outlined idz-mor">'+ikon+'</span>'+t+'</div>'; };
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
      h += '<div class="gy-yan">'+bas('warning_amber', 'Alan Dışı Masalar')+u.liste.slice(0, 8).map(function(x){ return gyIlisteHtml(x.m, x.n, Math.round(x.n * 100 / mx), '#EF4444'); }).join('')+'</div>';
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
  kut.innerHTML = bas + '<p><b>'+gyE(gyMasaAd(en))+'</b> bu aralıkta en yoğun masa. '+kisi+'</p><button type="button" class="idz-btn dolu kucuk" onclick="'+(musait !== null ? 'garsonAtaAc('+musait+')' : "idzSekme('garsonlar')")+'">Garson Ata</button>';
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
idzAramaKur(function(esle){
  var h = [];
  GY.masalar.filter(function(m){ return esle(m.ad); }).slice(0, 6).forEach(function(m){ var al = GY.alanlar.find(function(a){ return a.id === m.b; }); h.push(['table_restaurant', m.ad, al ? al.ad : 'Bölgesiz', function(){ gyAlan = gyIsiAlan = m.b; document.querySelectorAll('.gy-alan-sec').forEach(function(x){ x.value = gyAlan; }); idzSekme('isi'); }]); });
  gyD.garsonlar.filter(function(g){ return esle(g.ad); }).slice(0, 5).forEach(function(g){ h.push(['person', g.ad, 'Garson', function(){ idzSekme('garsonlar'); }]); });
  return h;
});
document.addEventListener('click', function(e){
  var m = e.target.closest('.gy-m');
  if(m){ rkBilgi(m.getAttribute('data-ad') + ': ' + m.getAttribute('data-n') + ' işlem, %' + m.getAttribute('data-p') + ' yoğunluk' + (m.getAttribute('data-dis') === '1' ? ' · alan dışı' : ''), 'info'); return; }
  var ac = e.target.closest('[data-alan-ac]'), kart = e.target.closest('.gy-mini');
  if(ac || kart){ gyIsiAlan = ac ? +ac.getAttribute('data-alan-ac') : +kart.closest('.gy-bolge').getAttribute('data-alan'); gyIsiCizHepsi(); return; }
  var g = e.target.closest('[data-garson]');
  if(g){ gyIsiGarson = +g.getAttribute('data-garson'); document.getElementById('gyIsiGarson').value = gyIsiGarson; gyIsiCizHepsi(); }
});
(function(){
  gySecimDoldur();
  gyHepsiniCiz();
  try{
    if(location.hash.length > 1) idzSekme(location.hash.slice(1));
    else if(sessionStorage.getItem('gy_don')){ sessionStorage.removeItem('gy_don'); var s = sessionStorage.getItem('gy_sekme'); if(s) idzSekme(s); }
  }catch(e){}
  document.addEventListener('submit', function(){ try{ sessionStorage.setItem('gy_don', '1'); }catch(e){} }, true);
  setInterval(function(){ if(!document.hidden && !document.querySelector('.idz-modal.acik') && document.getElementById('gataModal').style.display !== 'flex') gyYukle(); }, 30000);
  var zaman = null;
  if(window.ResizeObserver) new ResizeObserver(function(){ clearTimeout(zaman); zaman = setTimeout(gyIsiCizHepsi, 80); }).observe(document.querySelector('.idz'));
})();
</script>
@endsection
@push('head')
@include('layout._idz_stil')
<style>
.gy-ust{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.gy-yol{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--sub2)}
.gy-yol a{display:inline-flex;align-items:center;gap:6px;color:var(--ink);font-weight:700;text-decoration:none}
.gy-yol a .material-icons-outlined{display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:9px;background:var(--mor1);color:#fff;font-size:18px}
.gy-yol>.material-icons-outlined{font-size:18px;color:var(--sub)}
.gy-yol b{font-weight:600}
.gy-yeni{min-height:42px;padding:0 22px;border-radius:999px;font-size:14px}
.idz-trend.kotu{background:color-mix(in srgb,#EF4444 13%,transparent);color:#DC2626}
.gy-kartlar{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.25fr) minmax(0,1.1fr) minmax(0,1.1fr);gap:12px;margin-bottom:12px}
.gy-kartlar .idz-stat{min-width:0}
.gy-kartlar .idz-stat>div{min-width:0}
.gy-kartlar .e{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-kartlar .d{white-space:nowrap}
.gy-canli{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;background:color-mix(in srgb,#10B981 13%,transparent);color:#059669;font-size:11.5px;font-weight:700}
.gy-canli i{width:7px;height:7px;border-radius:50%;background:#10B981;animation:gyNabiz 1.8s ease-in-out infinite}
@keyframes gyNabiz{50%{opacity:.35}}
.gy-orta{display:grid;grid-template-columns:minmax(0,1.9fr) minmax(400px,1fr);gap:12px;align-items:stretch;margin-bottom:12px}
.gy-orta>.idz-kart{margin:0}
.gy-isi-kart .idz-kart-bas,[data-panel=isi] .idz-kart-bas{flex-wrap:wrap}
.gy-isi-govde{display:grid;grid-template-columns:minmax(0,1fr) 138px;gap:10px;align-items:stretch}
.gy-cerceve{height:auto}
.gy-efsane{display:flex;flex-direction:column;gap:9px}
.gy-ef-bas{display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:800;color:var(--ink)}
.gy-ef-bas .material-icons-outlined{font-size:18px}
.gy-ef{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;color:var(--ink)}
.gy-ef i{flex:none;width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,var(--r),color-mix(in srgb,var(--r) 40%,transparent))}
.gy-ef small{display:block;font-size:11px;font-weight:500;color:var(--sub2)}
.gy-oneri{margin-top:auto;padding:10px;border-radius:13px;background:color-mix(in srgb,#F59E0B 11%,var(--card));font-size:11.5px;color:var(--ink);line-height:1.4}
.gy-oneri .b{display:flex;align-items:center;gap:4px;font-weight:800}
.gy-oneri .b .material-icons-outlined{font-size:17px;color:#F59E0B}
.gy-oneri p{margin:5px 0 8px}
.gy-oneri .idz-btn{width:100%;justify-content:center}
.gy-m{cursor:pointer;touch-action:auto}
.gy-parlama{position:absolute;left:50%;top:50%;width:72%;height:72%;transform:translate(-50%,-50%);border-radius:50%;background:radial-gradient(circle,var(--r) 0,color-mix(in srgb,var(--r) 45%,transparent) 55%,transparent 75%);opacity:.72;pointer-events:none}
.gy-m .et small{display:block;font-size:calc(var(--ts) * .115);font-weight:800;color:#1E1633;line-height:1.1}
.gy-gsat{display:grid;grid-template-columns:38px minmax(80px,1fr) 78px minmax(70px,1fr) 36px 58px;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--line)}
.gy-gsat:last-child{border-bottom:0}
.gy-gsat .idz-avatar{width:38px;height:38px;font-size:15px}
.gy-gsat .ad{min-width:0}
.gy-gsat .ad b{display:block;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gsat .ad small{font-size:11px;color:var(--sub)}
.gy-gsat .me{min-width:0;font-size:12px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gsat .dk{font-size:12px;font-weight:700;color:var(--ink);text-align:right;white-space:nowrap}
.gy-durum{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:var(--r);white-space:nowrap}
.gy-durum i{width:8px;height:8px;border-radius:50%;background:var(--r)}
.gy-spark{display:flex;align-items:flex-end;gap:2px;height:26px}
.gy-spark i{flex:1;min-width:2px;border-radius:1px;background:var(--mor1)}
.gy-spark.soluk i{opacity:.28}
.gy-alt{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr) minmax(0,1.03fr);gap:12px}
.gy-alt>.idz-kart{margin:0}
.gy-mini-sec{margin-left:auto;border:0;outline:0;padding:5px 8px;border-radius:9px;background:var(--idz-mor-a);color:var(--mor1);font:inherit;font-size:12.5px;font-weight:700;cursor:pointer}
.gy-mini-sec option{color:#1E293B}
.gy .idz-sec-kutu select{min-width:92px}
.gy-halkalar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.gy-halka{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center}
.gy-halka svg{width:min(100%,88px);height:auto}
.gy-halka b{position:absolute;top:0;left:0;right:0;height:min(100%,88px);max-height:88px;aspect-ratio:auto;display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--ink)}
.gy-halka span{margin-top:6px;font-size:11.5px;font-weight:600;color:var(--ink);line-height:1.25}
.gy-perf{display:grid;grid-template-columns:28px minmax(80px,1fr) minmax(70px,1.5fr) 38px;align-items:center;gap:10px;padding:8px 0}
.gy-perf .s{display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;color:#3B2A12;font-size:12.5px;font-weight:800}
.gy-perf b{min-width:0;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-perf .c,.gy-iliste .c{display:block;height:9px;border-radius:99px;background:var(--idz-mor-a);overflow:hidden}
.gy-perf .c i,.gy-iliste .c i{display:block;height:100%;border-radius:99px;background:var(--mor1)}
.gy-perf em,.gy-iliste em{font-style:normal;font-size:12.5px;font-weight:700;color:var(--ink);text-align:right}
.gy-garson-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px}
.gy-gkart{padding:14px;border:1px solid var(--line);border-radius:15px;background:var(--card)}
.gy-gkart .u{display:flex;align-items:center;gap:10px}
.gy-gkart .idz-avatar{width:44px;height:44px;font-size:17px}
.gy-gkart .ad{flex:1;min-width:0}
.gy-gkart .ad b{display:block;margin-bottom:3px;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gkart .ad .idz-chip{display:inline-block;max-width:100%}
.gy-gkart .r{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin:12px 0;padding:10px 4px;border-radius:12px;background:var(--card2);text-align:center;font-size:11px;color:var(--sub2)}
.gy-gkart .r b{display:block;font-size:13.5px;color:var(--ink);white-space:nowrap}
.gy-gkart .a{display:flex;gap:8px}
.gy-gkart .a .idz-btn{flex:1;justify-content:center}
.gy-olay-tam{max-height:640px;overflow:auto}
.gy-olay{display:grid!important;grid-template-columns:24px 44px minmax(90px,170px) minmax(0,1fr);align-items:center;gap:10px;padding:9px 4px!important;border-bottom:1px solid var(--line)}
.gy-olay .material-icons-outlined{font-size:20px;color:var(--mor1)}
.gy-olay b{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-isi-buyuk{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:16px;align-items:start}
.gy-iliste{display:grid;grid-template-columns:minmax(60px,90px) minmax(0,1fr) 30px;align-items:center;gap:8px;padding:7px 0}
.gy-iliste b{font-size:12.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-alan-serit{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 10px;scrollbar-width:none}
.gy-alan-serit::-webkit-scrollbar{display:none}
.gy-alan-btn{flex:none;display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 15px;border:1px solid var(--line);border-radius:999px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.gy-alan-btn .material-icons-outlined{font-size:17px;color:var(--mor1)}
.gy-alan-btn.aktif{background:color-mix(in srgb,var(--mor1) 13%,var(--card));border-color:var(--mor1);color:var(--mor1);font-weight:700}
.gy-uyum{display:flex;align-items:center;gap:14px;margin-bottom:12px;padding:12px 16px;border:1px solid var(--line);border-radius:14px;background:var(--card2)}
.gy-uyum strong{flex:none;font-size:30px;font-weight:800;line-height:1}
.gy-uyum .m{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px;font-size:12.5px;color:var(--sub)}
.gy-uyum .m b{font-size:14px;color:var(--ink)}
.gy-uyum .c{display:block;height:8px;margin-top:2px;border-radius:99px;background:var(--idz-mor-a);overflow:hidden}
.gy-uyum .c i{display:block;height:100%;border-radius:99px}
.gy-uyum .ef{flex:none;display:flex;flex-direction:column;gap:5px;font-size:11.5px;font-weight:600;color:var(--sub)}
.gy-uyum .ef span{display:flex;align-items:center;gap:6px}
.gy-uyum .ef i{width:11px;height:11px;border-radius:50%}
.gy-uyum .ef i.s{background:var(--sub2);opacity:.45}
.gy-uyum.bilgi{font-size:13px;color:var(--ink)}
.gy-uyum.bilgi>.material-icons-outlined{color:var(--mor1)}
.gy-uyum.bilgi p{flex:1;margin:0;line-height:1.4}
.gy-salon{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.gy-bolge{min-width:0;padding:8px;border:1px solid var(--line);border-radius:16px;background:var(--card);transition:opacity .15s}
.gy-bolge.kendi{border-color:var(--mor1);box-shadow:0 0 0 2px color-mix(in srgb,var(--mor1) 30%,transparent)}
.gy-bolge-bas{display:flex;flex-wrap:wrap;align-items:center;gap:6px;width:100%;min-height:30px;margin-bottom:7px;padding:0 2px;border:0;background:transparent;color:var(--ink);font:inherit;text-align:left;cursor:pointer}
.gy-bolge-bas b{font-size:13.5px;font-weight:800}
.gy-bolge-bas em{margin-left:auto;font-style:normal;font-size:12px;font-weight:600;color:var(--sub)}
.gy-bolge-bas .material-icons-outlined{font-size:16px;color:var(--mor1)}
.gy-rozet{padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.gy-rozet.kendi{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}
.gy-rozet.dis{background:color-mix(in srgb,#EF4444 13%,transparent);color:#DC2626}
.gy-mini{box-sizing:border-box;overflow:hidden;border-width:5px;border-radius:12px;cursor:zoom-in}
.gy-mini .idz-dunya{min-height:0}
.gy-mini .idz-isik{display:none}
.gy-mini .idz-masa .et{gap:0}
.gy-mini .idz-masa .et b{font-size:max(10px,calc(var(--ts) * .2 * var(--mb,1)))}
.gy-mini .gy-m .et small{font-size:max(8px,calc(var(--ts) * .115))}
.gy-mini-bos{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);padding:6px 12px;border-radius:10px;background:rgba(255,255,255,.92);color:#334155;font-size:12px;font-weight:600;white-space:nowrap}
.gy-soluk{opacity:.32}
.gy-dis svg{filter:drop-shadow(0 0 2px #EF4444) drop-shadow(0 0 8px #EF4444)}
.gy-dis-rozet{position:absolute;top:2%;right:2%;display:flex;align-items:center;justify-content:center;min-width:19px;height:19px;padding:0 4px;border-radius:999px;background:#EF4444;color:#fff;font-size:11px;font-weight:800;line-height:1;box-shadow:0 0 0 2px #fff}
.gy-yan{margin-bottom:16px}
.gy-yan:last-child{margin-bottom:0}
.gy-uyum-sat{grid-template-columns:minmax(60px,90px) minmax(0,1fr) 38px;width:100%;border:0;background:transparent;font:inherit;text-align:left;cursor:pointer;border-radius:8px}
.gy-uyum-sat:hover{background:var(--idz-mor-a)}
.gy-yan-not{margin:6px 0 0;font-size:11px;color:var(--sub)}
.gy-tablo-kap{overflow-x:auto}
.gy-tablo{width:100%;border-collapse:collapse;font-size:13px;color:var(--ink)}
.gy-tablo th{padding:9px 10px;text-align:left;font-size:12px;font-weight:700;color:var(--sub2);border-bottom:1px solid var(--line);white-space:nowrap}
.gy-tablo td{padding:10px;border-bottom:1px solid var(--line);white-space:nowrap}
@media(max-width:1500px){.gy-kartlar{grid-template-columns:repeat(3,minmax(0,1fr))}.gy-alt{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.gy-alt>.idz-kart:first-child{grid-column:1/-1}}
@media(max-width:1280px){.gy-orta{grid-template-columns:minmax(0,1fr)}.gy-gsat{grid-template-columns:38px minmax(100px,1.2fr) 80px minmax(100px,1.4fr) 40px 70px}}
@media(max-width:860px){
  .gy-kartlar{grid-template-columns:repeat(2,minmax(0,1fr))}
  .gy-alt,.gy-isi-buyuk,.gy-isi-govde,.gy-salon{grid-template-columns:minmax(0,1fr)}
  .gy-uyum{flex-wrap:wrap}
  .gy-uyum .m{flex-basis:calc(100% - 90px)}
  .gy-uyum .ef{flex-direction:row;flex-wrap:wrap}
  .gy-efsane{flex-direction:row;flex-wrap:wrap}
  .gy-ef-bas{flex-basis:100%}
  .gy-oneri{flex-basis:100%}
  .gy-gsat{grid-template-columns:38px minmax(0,1fr) auto;row-gap:2px}
  .gy-gsat .me{grid-column:2/3}
  .gy-gsat .dk{grid-column:3}
  .gy-gsat .gy-spark{display:none}
  .gy-yeni{flex:1 1 100%;justify-content:center}
}
@media(max-width:520px){.gy-halkalar{grid-template-columns:repeat(2,minmax(0,1fr))}.gy-garson-grid{grid-template-columns:minmax(0,1fr)}.gy-olay{grid-template-columns:22px 40px minmax(0,1fr)}.gy-olay>span:last-child{grid-column:3}}
</style>
@endpush

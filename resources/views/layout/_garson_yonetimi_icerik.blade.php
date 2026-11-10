{{-- İşletme Düzeni > Garson Yönetimi sekmesi. Alt sekmeler gy-sekme/gy-panel: dıştaki idz-sekme/idz-panel ile karışmasın. Kod: _garson_yonetimi_js --}}
<div class="gy">
@if (empty($gyJson))
  <div class="idz-kart"><div class="idz-bos">Garson Yönetimi şu an açılamadı. Sayfayı yenileyin.</div></div>
@else
<div class="gy-ust">
  <div class="idz-sekmeler gy-sekmeler" role="tablist">
    <button type="button" class="gy-sekme aktif" data-gy-sekme="genel" role="tab"><span class="material-icons-outlined">bar_chart</span>Genel Bakış</button>
    <button type="button" class="gy-sekme" data-gy-sekme="garsonlar" role="tab"><span class="material-icons-outlined">person</span>Garsonlar</button>
    <button type="button" class="gy-sekme" data-gy-sekme="aktivite" role="tab"><span class="material-icons-outlined">radar</span>Aktivite Takibi</button>
    <button type="button" class="gy-sekme" data-gy-sekme="isi" role="tab"><span class="material-icons-outlined">local_fire_department</span>Isı Haritası</button>
    <button type="button" class="gy-sekme" data-gy-sekme="rapor" role="tab"><span class="material-icons-outlined">query_stats</span>Performans Raporu</button>
    <button type="button" class="gy-sekme" data-gy-sekme="ayarlar" role="tab"><span class="material-icons-outlined">settings</span>Ayarlar</button>
  </div>
  <button type="button" class="idz-btn dolu gy-yeni" onclick="idzModal('gyYeniGarson')"><span class="material-icons-outlined">add</span>Yeni Garson Ekle</button>
</div>

{{-- ============ GENEL BAKIŞ ============ --}}
<section class="gy-panel aktif" data-gy-panel="genel">
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
          <div class="gy-ef"><i style="--r:#EF4444"></i><span>%90 – 100<small>Çok Yoğun</small></span></div>
          <div class="gy-ef"><i style="--r:#F97316"></i><span>%70 – 90<small>Yoğun</small></span></div>
          <div class="gy-ef"><i style="--r:#EAB308"></i><span>%40 – 70<small>Orta</small></span></div>
          <div class="gy-ef"><i style="--r:#3B82F6"></i><span>%0 – 40<small>Sakin</small></span></div>
          <div class="gy-oneri" id="gyOneri"></div>
        </div>
      </div>
    </div>
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">groups</span><h3>Garsonlar &amp; Aktiviteleri</h3>
        <button type="button" class="idz-link" onclick="gySekme('garsonlar')">Tümünü Gör<span class="material-icons-outlined">arrow_forward</span></button>
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
        <button type="button" class="idz-link" onclick="gySekme('aktivite')">Tümünü Gör<span class="material-icons-outlined">arrow_forward</span></button>
      </div>
      <div class="idz-hareket" id="gyOlayOzet"></div>
    </div>
  </div>
</section>

{{-- ============ GARSONLAR ============ --}}
<section class="gy-panel" data-gy-panel="garsonlar">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">person</span><h3>Garsonlar</h3><span class="idz-rozet" id="gySay"></span></div>
    <p class="idz-acik">Bugünkü rakamlar. Bölge seçersen o bölgenin tüm masaları dahildir; hiç seçmezsen garson tüm masalara bakar.</p>
    <div class="gy-garson-grid" id="gyGarsonKart"></div>
  </div>
</section>

{{-- ============ AKTİVİTE ============ --}}
<section class="gy-panel" data-gy-panel="aktivite">
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
<section class="gy-panel" data-gy-panel="isi">
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
<section class="gy-panel" data-gy-panel="rapor">
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
<section class="gy-panel" data-gy-panel="ayarlar">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">settings</span><h3>Ayarlar</h3></div>
    <div class="idz-ayar-grid">
      @foreach ([
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
@endif
</div>

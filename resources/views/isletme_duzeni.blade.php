@extends('layout.app')
@section('title', 'İşletme Düzeni')
@section('rk_ikon', 'grid_view')
@section('baslik', 'İşletme Düzeni')
@section('rk_alt', 'Bölge - Masa - Garson - Kasa Düzenini Kolayca Yönetin')

@section('rk_araclar')
  @include('layout._idz_ust_arac')
@endsection

@section('content')
@php
  $bolgeler = $bolgeler ?? collect();
  $masalar = $masalar ?? collect();
  $garsonlar = $garsonlar ?? collect();
  $kasalar = $kasalar ?? collect();
  $personeller = $personeller ?? collect();
  $hareketler = $hareketler ?? collect();
  $fotolar = $fotolar ?? collect();
  $plan = $plan ?? [];
  $bolgeAd = $bolgeler->pluck('ad', 'id');
  $masaByBolge = $masalar->groupBy(fn($m) => $m->bolge_id ?? 0);
  $bosSay = $masalar->where('durum', 'bos')->count();
  $garsonSay = $garsonlar->where('rol', 'garson')->count();
  $garsonOne = $garsonlar->sortBy(fn($g) => $g->rol === 'garson' ? 0 : 1)->take(4);
  $alanlar = $bolgeler->map(fn($b) => ['id' => (int) $b->id, 'ad' => $b->ad])->values();
  if (isset($masaByBolge[0])) $alanlar->push(['id' => 0, 'ad' => 'Bölgesiz']);
  $bolgeStil = function ($ad, $i) {
    $k = mb_strtolower((string) $ad);
    $ikon = str_contains($k, 'vip') ? 'workspace_premium' : ((str_contains($k, 'bah')) ? 'beach_access' : (str_contains($k, 'tera') ? 'park' : 'chair'));
    return [$ikon, ['#7C3AED', '#2FB48C', '#F59E0B', '#9B7BF0'][$i % 4]];
  };
  $garsonOzet = function ($g) use ($bolgeAd, $masalar) {
    $aB = $g->bolge_idler ?? []; $aM = $g->masa_idler ?? [];
    if ($aB) {
      $ad = $bolgeAd[$aB[0]] ?? 'Bölge';
      $ek = count($aB) - 1;
      return $ad . ($ek > 0 ? ' +' . $ek . ' bölge' : '') . ($aM ? ' +' . count($aM) . ' masa' : '');
    }
    if ($aM) {
      $ilk = $masalar->firstWhere('id', $aM[0]);
      $ad = $ilk ? ($bolgeAd[$ilk->bolge_id] ?? null) : null;
      return ($ad ? $ad . ' (' . count($aM) . ' masa)' : count($aM) . ' masa');
    }
    return 'Tüm masalar';
  };
  $rolAd = ['sahip' => 'Sahip', 'mudur' => 'Müdür', 'garson' => 'Garson', 'kasiyer' => 'Kasiyer', 'mutfak' => 'Mutfak', 'satinalmaci' => 'Satın Almacı'];
  $idzVeri = [
    'alanlar' => $alanlar->all(),
    'masalar' => $masalar->map(fn($m) => [
      'id' => (int) $m->id, 'ad' => $m->ad, 'kapasite' => (int) ($m->kapasite ?? 4), 'sekil' => $m->sekil ?? 'kare',
      'b' => (int) ($m->bolge_id ?? 0), 'durum' => $m->durum ?? 'bos', 'tutar' => (float) ($m->tutar ?? 0),
      'qr' => !empty($m->qr_token) ? url('/m/' . $m->qr_token) : null,
    ])->values()->all(),
    'garsonlar' => $garsonlar->map(fn($g) => ['id' => (int) $g->id, 'ad' => $g->ad, 'b' => array_map('intval', $g->bolge_idler ?? []), 'm' => array_map('intval', $g->masa_idler ?? [])])->values()->all(),
    'kasalar' => $kasalar->map(fn($k) => ['id' => (int) $k->id, 'ad' => $k->ad])->values()->all(),
    'plan' => (object) $plan,
    'sube' => $subeAd ?? 'Restoran',
  ];
  $idzJson = json_encode($idzVeri, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
@endphp

<div class="idz">
@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

{{-- Sekmeler --}}
<div class="idz-sekmeler" role="tablist">
  <button type="button" class="idz-sekme aktif" data-sekme="plan" role="tab"><span class="material-icons-outlined">table_restaurant</span>Masa Planı</button>
  <a class="idz-sekme" href="/garson-yonetimi"><span class="material-icons-outlined">groups</span>Garson Yönetimi</a>
  <button type="button" class="idz-sekme" data-sekme="kasa" role="tab"><span class="material-icons-outlined">point_of_sale</span>Kasa Yönetimi</button>
  <button type="button" class="idz-sekme" data-sekme="bolge" role="tab"><span class="material-icons-outlined">person_pin_circle</span>Bölge Ayarları</button>
  <button type="button" class="idz-sekme" data-sekme="yetki" role="tab"><span class="material-icons-outlined">verified_user</span>Yetki &amp; Roller</button>
  <button type="button" class="idz-sekme" data-sekme="genel" role="tab"><span class="material-icons-outlined">settings</span>Genel Ayarlar</button>
</div>

{{-- Özet kartları --}}
<div class="idz-ozet">
  <div class="idz-stat">
    <span class="idz-stat-ikon" style="--r:#7C3AED"><span class="material-icons-outlined">chair</span></span>
    <div><div class="e">Toplam Masa</div><div class="d">{{ $masalar->count() }}</div></div>
    @if (($yeniMasa ?? 0) > 0)<span class="idz-trend" title="Son 7 günde eklenen"><span class="material-icons-outlined">arrow_upward</span>{{ $yeniMasa }}</span>@endif
  </div>
  <div class="idz-stat">
    <span class="idz-stat-ikon" style="--r:#10B981"><span class="material-icons-outlined">chair</span></span>
    <div><div class="e">Boş Masa</div><div class="d">{{ $bosSay }}</div></div>
    @if ($masalar->count())<span class="idz-trend" title="Boş masa oranı">%{{ round($bosSay * 100 / $masalar->count()) }}</span>@endif
  </div>
  <div class="idz-stat">
    <span class="idz-stat-ikon" style="--r:#7C3AED"><span class="material-icons-outlined">groups</span></span>
    <div><div class="e">Aktif Garson</div><div class="d">{{ $garsonSay }}</div></div>
  </div>
  <div class="idz-stat">
    <span class="idz-stat-ikon" style="--r:#2563EB"><span class="material-icons-outlined">point_of_sale</span></span>
    <div><div class="e">Kasa Sayısı</div><div class="d">{{ $kasalar->count() }}</div></div>
  </div>
  <div class="idz-afis">
    <span class="material-icons-outlined">restaurant</span>
    <div>Restoranınızın düzeni<br>her zaman kontrol altında!</div>
  </div>
</div>

{{-- ============ MASA PLANI ============ --}}
<section class="idz-panel aktif" data-panel="plan">
  <div class="idz-plan-kart">
    <div class="idz-plan-bas">
      <span class="material-icons-outlined idz-mor" style="font-size:26px">layers</span>
      <h2>Masa Planı</h2>
      <span class="idz-esnek"></span>
      <div class="idz-sec-kutu">
        <span class="material-icons-outlined">home</span>
        <select id="idzAlanSec" aria-label="Alan seç">
          @foreach ($alanlar as $a)<option value="{{ $a['id'] }}">{{ $a['ad'] }}</option>@endforeach
        </select>
      </div>
      <div class="idz-ara idz-masa-ara">
        <span class="material-icons-outlined">search</span>
        <input type="search" id="idzMasaAra" placeholder="Masa ara..." autocomplete="off" aria-label="Masa ara">
      </div>
      <button type="button" class="idz-btn" onclick="idzModal('idzMasaEkle')"><span class="material-icons-outlined">add</span>Masa Ekle</button>
      <button type="button" class="idz-btn" onclick="idzModal('idzBolgeEkle')"><span class="material-icons-outlined">add_home_work</span>Alan Ekle</button>
      <a class="idz-btn" href="/masa-yonetim" title="Masa QR kodlarını görüntüle, indir ve yazdır"><span class="material-icons-outlined">qr_code_2</span>QR Kodlar</a>
      <button type="button" class="idz-btn dolu idz-normal" onclick="idzDuzenAc()"><span class="material-icons-outlined">edit_location_alt</span>Düzenle</button>
      <button type="button" class="idz-btn idz-duzende" onclick="idzVazgec()"><span class="material-icons-outlined">close</span>Vazgeç</button>
      <button type="button" class="idz-btn dolu idz-duzende" id="idzKaydet" onclick="idzPlanKaydet()"><span class="material-icons-outlined">save_as</span>Kaydet</button>
    </div>

    <div class="idz-duzen-bar idz-duzende">
      <span class="ipucu"><span class="material-icons-outlined">touch_app</span>Sürükleyerek taşıyın, tıklayarak şekil ve boyut seçin.</span>
      <span class="idz-esnek"></span>
      <label class="idz-sec-kutu"><span class="material-icons-outlined">auto_awesome_mosaic</span><select id="idzSablon" aria-label="Şablon uygula" onchange="idzSablonUygula(this.value)">
        <option value="">Şablon uygula</option><option value="otomatik">Otomatik dizilim</option><option value="izgara">Sıralı ızgara</option><option value="kenar">Kenarlara dizili</option><option value="ada">Kenar + orta ada</option><option value="banket">Uzun masalar (banket)</option>
      </select></label>
      <label class="idz-sec-kutu"><span class="material-icons-outlined">add_box</span><select id="idzObjeSec" aria-label="Salona ekle" onchange="idzObjeEkle(this.value)">
        <option value="">Salona ekle</option><option value="kasa">Resepsiyon / Kasa</option><option value="bar">Bar</option><option value="giris">Giriş / Kapı</option><option value="mutfak">Mutfak</option><option value="wc">WC</option><option value="depo">Depo</option><option value="otopark">Otopark</option><option value="diger">Etiket</option><option value="duvar">Duvar</option><option value="bitki">Bitki</option><option value="isik">Işık</option>
      </select></label>
      <label class="idz-sec-kutu"><span class="material-icons-outlined">aspect_ratio</span><select id="idzOran" aria-label="Salon oranı" onchange="idzOran(this.value)">
        <option value="2.35">Geniş salon</option><option value="1.8">Standart salon</option><option value="1.35">Kareye yakın</option>
      </select></label>
      <label class="idz-kutucuk"><input type="checkbox" checked onchange="idzHizala=this.checked">Hizaya otur</label>
      <button type="button" class="idz-btn ikon" id="idzGeri" onclick="idzGeriAl()" title="Geri al (Ctrl+Z)" aria-label="Geri al" disabled><span class="material-icons-outlined">undo</span></button>
    </div>

    <div class="idz-plan-govde">
      <div class="idz-alanlar" id="idzAlanlar">
        @foreach ($alanlar as $a)
          <button type="button" class="idz-alan" data-alan="{{ $a['id'] }}">
            <span class="ad"><span class="material-icons-outlined">chair</span>{{ $a['ad'] }}</span>
            <b>{{ ($masaByBolge[$a['id']] ?? collect())->count() }}</b>
          </button>
        @endforeach
        <button type="button" class="idz-alan yeni" onclick="idzModal('idzBolgeEkle')"><span class="material-icons-outlined">add</span>Yeni Alan Ekle</button>
      </div>

      <div class="idz-cerceve-kap">
      <div class="idz-ozellik" id="idzOzellik"></div>
      <div class="idz-cerceve" id="idzCerceve">
        <div class="idz-dunya" id="idzDunya"></div>
        <div class="idz-zoom">
          <button type="button" onclick="idzZoom(.2)" title="Yakınlaştır" aria-label="Yakınlaştır"><span class="material-icons-outlined">add</span></button>
          <button type="button" onclick="idzZoom(-.2)" title="Uzaklaştır" aria-label="Uzaklaştır"><span class="material-icons-outlined">remove</span></button>
          <button type="button" onclick="idzZoom(0)" title="Ortala" aria-label="Ortala"><span class="material-icons-outlined">my_location</span></button>
        </div>
      </div>
      </div>

      <div class="idz-sag">
        <div class="idz-kart">
          <div class="idz-kart-bas">
            <span class="material-icons-outlined idz-mor">groups</span><h3>Garson Atamaları</h3>
            <a class="idz-link" href="/garson-yonetimi#garsonlar">Tüm Garsonlar<span class="material-icons-outlined">arrow_forward</span></a>
          </div>
          @forelse ($garsonOne as $g)
            @php $f = $fotolar[$g->id] ?? null; @endphp
            <button type="button" class="idz-gsatir" onclick="garsonAtaAc({{ $g->id }})">
              @if ($f && str_starts_with((string) $f, 'data:image'))
                <img class="idz-avatar" src="{{ $f }}" alt="">
              @else
                <span class="idz-avatar">{{ mb_strtoupper(mb_substr($g->ad, 0, 1)) }}</span>
              @endif
              <span class="ad" title="{{ $g->ad }}">{{ $g->ad }}</span>
              <span class="idz-chip" title="{{ $garsonOzet($g) }}">{{ $garsonOzet($g) }}</span>
              <span class="idz-aktif"><i></i>Aktif</span>
            </button>
          @empty
            <div class="idz-bos">Aktif garson yok. <a href="/personel">Personel ekleyin.</a></div>
          @endforelse
        </div>
        <div class="idz-kart">
          <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">bolt</span><h3>Hızlı İşlemler</h3></div>
          <div class="idz-hizli">
            <button type="button" onclick="idzModal('idzMasaEkle')"><span class="material-icons-outlined">table_restaurant</span>Masa Ekle</button>
            <button type="button" onclick="location.href='/garson-yonetimi#garsonlar'"><span class="material-icons-outlined">groups</span>Garson Ata</button>
            <button type="button" onclick="idzSekme('kasa');idzOdak('idzKasaAd')"><span class="material-icons-outlined">point_of_sale</span>Kasa Ekle</button>
            <button type="button" onclick="idzModal('idzBolgeEkle')"><span class="material-icons-outlined">location_on</span>Bölge Oluştur</button>
            <button type="button" onclick="idzTasiAc()"><span class="material-icons-outlined">swap_horiz</span>Masa Taşı</button>
            <button type="button" onclick="idzSekme('genel')"><span class="material-icons-outlined">settings</span>Ayarlar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="idz-alt">
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">bar_chart</span><h3>Bölgeler ve Masalar</h3>
        <button type="button" class="idz-link" onclick="idzSekme('bolge')">Tüm Bölgeler<span class="material-icons-outlined">arrow_forward</span></button>
      </div>
      <div class="idz-bkartlar">
        @forelse ($bolgeler as $i => $b)
          @php
            $g = $masaByBolge[$b->id] ?? collect();
            $doluS = $g->whereIn('durum', ['dolu', 'birlesik'])->count();
            $oran = $g->count() ? round($doluS * 100 / $g->count()) : 0;
            [$bIk, $bRenk] = $bolgeStil($b->ad, $i);
          @endphp
          <button type="button" class="idz-bkart" style="--r:{{ $bRenk }}" onclick="idzAlanSec({{ (int) $b->id }},true)">
            <span class="ic"><span class="material-icons-outlined">{{ $bIk }}</span></span>
            <span class="me"><b title="{{ $b->ad }}">{{ $b->ad }}</b><span>{{ $g->count() }} Masa</span><span>%{{ $oran }} Doluluk</span></span>
            <span class="cubuk"><i style="width:{{ $oran }}%"></i></span>
          </button>
        @empty
          <div class="idz-bos">Henüz bölge yok. <a href="#" onclick="idzModal('idzBolgeEkle');return false">Bölge ekleyin.</a></div>
        @endforelse
      </div>
    </div>
    <div class="idz-kart">
      <div class="idz-kart-bas">
        <span class="material-icons-outlined idz-mor">schedule</span><h3>Son Hareketler</h3>
        <a class="idz-link" href="/hareketler">Tüm Hareketler<span class="material-icons-outlined">arrow_forward</span></a>
      </div>
      <div class="idz-hareket">
        @forelse ($hareketler as $h)
          <div><i></i><time>{{ \Carbon\Carbon::parse($h->created_at)->setTimezone('Europe/Istanbul')->format('H:i') }}</time><span title="{{ $h->aksiyon }}">{{ $h->aksiyon }}</span></div>
        @empty
          <div class="idz-bos">Henüz hareket yok. Bu sayfada yaptığınız değişiklikler burada görünür.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>

{{-- ============ KASA YÖNETİMİ ============ --}}
<section class="idz-panel" data-panel="kasa">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">point_of_sale</span><h3>Kasa Noktaları</h3><span class="idz-rozet">{{ $kasalar->count() }}</span></div>
    <p class="idz-acik">Birden fazla kasan varsa ekle; her kasaya <b>personel</b> ve/veya <b>masa</b> ata. Kasa eklemezsen tek-havuz (tek çekmece) çalışır.</p>
    <form method="POST" action="/kasalar/ekle" class="idz-form-satir">
      @csrf
      <div style="flex:1;min-width:200px">
        <label class="rk-lbl" style="margin-top:0">Yeni kasa adı</label>
        <input type="text" id="idzKasaAd" name="ad" maxlength="60" required placeholder='Örn. "Kasa 1", "Bar Kasa"' class="rk-inp">
      </div>
      <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">add</span>Kasa Ekle</button>
    </form>
  </div>
  @foreach ($kasalar as $k)
    <div class="idz-kart idz-kasa">
      <div class="idz-kasa-bas">
        <span class="material-icons-outlined idz-mor" style="font-size:24px">storefront</span>
        <form method="POST" action="/kasalar/duzenle" style="display:flex;gap:6px;align-items:center;flex:1">
          @csrf<input type="hidden" name="id" value="{{ $k->id }}">
          <input type="text" name="ad" value="{{ $k->ad }}" maxlength="60" class="rk-inp" style="max-width:240px;padding:8px 10px;font-weight:700">
          <button class="idz-btn ikon" title="Adı kaydet" aria-label="Adı kaydet"><span class="material-icons-outlined">save</span></button>
        </form>
        <form method="POST" action="/kasalar/sil" onsubmit="return rkOnayForm(this, @js($k->ad . ' kapatılsın mı?'), 'var(--kirmizi)', 'Kapat')">
          @csrf<input type="hidden" name="id" value="{{ $k->id }}">
          <button class="idz-btn ikon tehlike" title="Kasayı kapat" aria-label="Kasayı kapat"><span class="material-icons-outlined">delete_outline</span></button>
        </form>
      </div>
      @include('layout._kasa_sayfa_form', ['k' => $k])
      <div class="idz-kbaslik">PERSONEL (bu kasaya atanınca girişte otomatik burada çalışır)</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
        @foreach ($personeller as $p)
          @php $buKasa = (int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id; @endphp
          <form method="POST" action="/kasalar/atama" style="margin:0">
            @csrf<input type="hidden" name="personel_id" value="{{ $p->id }}">
            <input type="hidden" name="kasa_id" value="{{ $buKasa ? 0 : $k->id }}">
            <button class="idz-btn kucuk {{ $buKasa ? 'dolu' : '' }}">
              @if ($buKasa)<span class="material-icons-outlined">check</span>@endif{{ $p->ad }}
            </button>
          </form>
        @endforeach
      </div>
      <div class="idz-kbaslik">MASALAR (bu kasaya bağlı masalar, opsiyonel)</div>
      <form method="POST" action="/isletme-duzeni/kasa-masa">
        @csrf<input type="hidden" name="kasa_id" value="{{ $k->id }}">
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
          @forelse ($masalar as $m)
            <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px">
              <input type="checkbox" name="masa_idler[]" value="{{ $m->id }}" {{ in_array((int)$m->id, $k->masa_idler ?? []) ? 'checked' : '' }}>
              {{ $bolgeAd[$m->bolge_id] ?? '' }} · {{ $m->ad }}
            </label>
          @empty
            <span class="rk-sub2">Masa yok.</span>
          @endforelse
        </div>
        @if ($masalar->count())<button class="idz-btn dolu kucuk"><span class="material-icons-outlined">save</span>Masaları Kaydet</button>@endif
      </form>
    </div>
  @endforeach
  @if (!$kasalar->count())
    <div class="idz-kart"><div class="idz-bos">Kasa noktası yok, tek-havuz (tek çekmece) modunda çalışıyorsun. Birden fazla fiziksel kasan varsa yukarıdan ekle.</div></div>
  @endif
</section>

{{-- ============ BÖLGE AYARLARI ============ --}}
<section class="idz-panel" data-panel="bolge">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">person_pin_circle</span><h3>Bölge Ayarları</h3><span class="idz-rozet">{{ $bolgeler->count() }}</span></div>
    <p class="idz-acik">Salonun alanları: Bahçe, Teras, İç Salon, Üst Kat… Masalar bu bölgelere yerleşir.</p>
    <form method="POST" action="/isletme-duzeni/bolge-ekle" class="idz-form-satir">
      @csrf
      <div style="flex:1;min-width:220px">
        <label class="rk-lbl" style="margin-top:0">Yeni bölge adı</label>
        <input type="text" name="ad" maxlength="60" required placeholder='Örn. "Bahçe", "Teras", "İç Salon"' class="rk-inp">
      </div>
      <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">add</span>Bölge Ekle</button>
    </form>
  </div>
  <div class="idz-bolge-grid">
    @foreach ($bolgeler as $i => $b)
      @php [$bIk, $bRenk] = $bolgeStil($b->ad, $i); @endphp
      <div class="idz-kart" style="margin:0">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
          <span class="material-icons-outlined" style="color:{{ $bRenk }};font-size:22px">{{ $bIk }}</span>
          <b style="flex:1;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $b->ad }}</b>
          <span class="idz-rozet">{{ ($masaByBolge[$b->id] ?? collect())->count() }} masa</span>
        </div>
        <form method="POST" action="/isletme-duzeni/bolge-duzenle" style="display:flex;gap:6px;margin-bottom:6px">
          @csrf<input type="hidden" name="id" value="{{ $b->id }}">
          <input type="text" name="ad" value="{{ $b->ad }}" maxlength="60" class="rk-inp" style="flex:1;padding:8px 10px" aria-label="Bölge adı">
          <button class="idz-btn ikon" title="Kaydet" aria-label="Kaydet"><span class="material-icons-outlined">save</span></button>
        </form>
        <form method="POST" action="/isletme-duzeni/bolge-sil" onsubmit="return rkOnayForm(this, @js($b->ad . ' bölgesi silinsin mi?'), 'var(--kirmizi)', 'Sil')">
          @csrf<input type="hidden" name="id" value="{{ $b->id }}">
          <button class="idz-btn tehlike" style="width:100%;justify-content:center"><span class="material-icons-outlined">delete_outline</span>Sil</button>
        </form>
      </div>
    @endforeach
  </div>
  @if (!$bolgeler->count())<div class="idz-kart"><div class="idz-bos">Henüz bölge yok. Yukarıdan ekleyin.</div></div>@endif
</section>

{{-- ============ YETKİ & ROLLER ============ --}}
<section class="idz-panel" data-panel="yetki">
  <div class="idz-kart">
    <div class="idz-kart-bas">
      <span class="material-icons-outlined idz-mor">verified_user</span><h3>Yetki &amp; Roller</h3>
      <a class="idz-btn dolu kucuk" style="margin-left:auto" href="/departman-yonetim"><span class="material-icons-outlined">admin_panel_settings</span>Departman &amp; Yetki Yönetimi</a>
    </div>
    <p class="idz-acik">Aktif personelin rollere göre dağılımı. Sayfa yetkilerini Departman &amp; Yetki ekranından, kişi bilgilerini Personel ekranından düzenlersin.</p>
    <div class="idz-rol-grid">
      @foreach ($personeller->groupBy('rol') as $rol => $kisiler)
        <div class="idz-rol">
          <div class="b"><span>{{ $rolAd[$rol] ?? ucfirst($rol) }}</span><b>{{ $kisiler->count() }}</b></div>
          <div class="k">{{ $kisiler->pluck('ad')->take(6)->implode(', ') }}{{ $kisiler->count() > 6 ? ' +' . ($kisiler->count() - 6) : '' }}</div>
        </div>
      @endforeach
    </div>
    @if (!$personeller->count())<div class="idz-bos">Aktif personel yok.</div>@endif
    <a class="idz-link" style="margin-top:12px" href="/personel">Personel Yönetimine Git<span class="material-icons-outlined">arrow_forward</span></a>
  </div>
</section>

{{-- ============ GENEL AYARLAR ============ --}}
<section class="idz-panel" data-panel="genel">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">settings</span><h3>Genel Ayarlar</h3></div>
    <div class="idz-ayar-grid">
      @foreach ([
        ['/masa-yonetim', 'qr_code_2', 'QR Afişleri / Arşiv', 'QR indir ve yazdır, arşivlenen masaları geri al'],
        ['/salon-sema', 'grid_on', 'Salon Şeması', 'Kat ve parsel krokisi, Masa Planı ile eşitlenir'],
        ['/isletme-konum', 'my_location', 'İşletme Konum', 'Harita konumu ve teslimat'],
        ['/isletme-olcegi', 'tune', 'İşletme Büyüklüğü', 'Menünün sadeliğini belirler'],
        ['/yazici-ayarlari', 'print', 'Yazıcı Ayarları', 'Mutfak ve kasa yazıcıları'],
        ['/bagli-cihazlar', 'devices', 'Bağlı Cihazlar', 'Tablet ve kasa cihazları'],
      ] as [$u, $ik, $t, $al])
        <a class="idz-ayar" href="{{ $u }}"><span class="material-icons-outlined">{{ $ik }}</span><span><b>{{ $t }}</b><small>{{ $al }}</small></span></a>
      @endforeach
    </div>
  </div>
</section>
</div>

@include('layout._masa_plan_cizim')

{{-- Masa kartı (plana tıklayınca) --}}
<div class="idz-pop" id="idzPop" role="dialog" aria-label="Masa işlemleri"></div>
<form id="idzSilForm" method="POST" action="/masa-yonetim/sil" style="display:none">@csrf<input type="hidden" name="id"></form>

{{-- Masa ekle --}}
<div class="idz-modal" id="idzMasaEkle">
  <div class="rk-card idz-modal-kutu">
    <div class="idz-modal-bas"><span class="material-icons-outlined idz-mor">table_restaurant</span><b>Masa Ekle</b><button type="button" class="mduz-ibtn" onclick="idzModalKapat(this)" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>
    @if ($bolgeler->count())
    <form method="POST" action="/masa-yonetim/ekle">
      @csrf
      <label class="rk-lbl" style="margin-top:0">Masa adı</label>
      <input type="text" name="ad" maxlength="40" placeholder='Örn. "Masa 1", "B4"' class="rk-inp">
      <label class="rk-lbl">Kapasite</label>
      <input type="number" name="kapasite" min="1" max="50" value="4" class="rk-inp">
      <label class="rk-lbl">Bölge</label>
      <select name="bolge_id" id="idzYeniMasaBolge" class="rk-inp">
        @foreach ($bolgeler as $b)<option value="{{ $b->id }}">{{ $b->ad }}</option>@endforeach
      </select>
      <div class="rk-sub" style="font-size:11.5px;margin-top:10px">Her masaya <b>kalıcı QR</b> otomatik oluşur. Silinen masa arşive gider, QR korunur.</div>
      <div class="idz-modal-alt">
        <button type="button" class="idz-btn" onclick="idzModalKapat(this)">İptal</button>
        <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">add</span>Masa Ekle</button>
      </div>
    </form>
    @else
      <div class="rk-flash err" style="margin:0">Önce en az bir bölge ekleyin.</div>
    @endif
  </div>
</div>

{{-- Bölge / alan ekle --}}
<div class="idz-modal" id="idzBolgeEkle">
  <div class="rk-card idz-modal-kutu">
    <div class="idz-modal-bas"><span class="material-icons-outlined idz-mor">add_home_work</span><b>Yeni Alan Ekle</b><button type="button" class="mduz-ibtn" onclick="idzModalKapat(this)" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>
    <form method="POST" action="/isletme-duzeni/bolge-ekle">
      @csrf
      <label class="rk-lbl" style="margin-top:0">Alan adı</label>
      <input type="text" name="ad" maxlength="60" required placeholder='Örn. "Bahçe", "Teras", "İç Salon"' class="rk-inp">
      <div class="idz-modal-alt">
        <button type="button" class="idz-btn" onclick="idzModalKapat(this)">İptal</button>
        <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">add</span>Alan Ekle</button>
      </div>
    </form>
  </div>
</div>

{{-- Masa taşı (başka bölgeye) --}}
<div class="idz-modal" id="idzMasaTasi">
  <div class="rk-card idz-modal-kutu">
    <div class="idz-modal-bas"><span class="material-icons-outlined idz-mor">swap_horiz</span><b>Masa Taşı</b><button type="button" class="mduz-ibtn" onclick="idzModalKapat(this)" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>
    <form method="POST" action="/isletme-duzeni/masa-bolge">
      @csrf
      <label class="rk-lbl" style="margin-top:0">Masa</label>
      <select name="id" id="idzTasiMasa" class="rk-inp" required>
        @foreach ($masalar as $m)<option value="{{ $m->id }}">{{ $bolgeAd[$m->bolge_id] ?? 'Bölgesiz' }} · {{ $m->ad }}</option>@endforeach
      </select>
      <label class="rk-lbl">Taşınacak bölge</label>
      <select name="bolge_id" class="rk-inp" required>
        @foreach ($bolgeler as $b)<option value="{{ $b->id }}">{{ $b->ad }}</option>@endforeach
      </select>
      <div class="rk-sub" style="font-size:11.5px;margin-top:10px">Aynı bölgede konum değiştirmek için masayı planda sürükleyip <b>Kaydet</b>'e basın.</div>
      <div class="idz-modal-alt">
        <button type="button" class="idz-btn" onclick="idzModalKapat(this)">İptal</button>
        <button type="submit" class="idz-btn dolu"><span class="material-icons-outlined">swap_horiz</span>Taşı</button>
      </div>
    </form>
  </div>
</div>

@include('layout._garson_ata_panelleri')

{{-- QR afiş önizleme --}}
<div id="mduzQrModal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="mduz-qr-box">
    <div class="mduz-qr-head">
      <span class="material-icons-outlined" style="font-size:19px">qr_code_2</span>
      <span id="mduzQrAd" class="mduz-qr-masa">-</span>
    </div>
    <div class="mduz-qr-body">
      <div class="mduz-qr-sube">{{ $subeAd ?? 'Restoran' }}</div>
      <div class="mduz-qr-ipucu">Karekodu telefonunuzla okutun</div>
      <div class="mduz-qr-frame">
        <img id="mduzQrImg" src="" alt="QR" style="width:220px;height:220px;object-fit:contain;display:block">
      </div>
      <div class="mduz-qr-ozellik">
        <span><span class="material-icons-outlined">restaurant_menu</span>Menü</span>
        <span><span class="material-icons-outlined">add_shopping_cart</span>Sipariş</span>
        <span><span class="material-icons-outlined">room_service</span>Garson</span>
        <span><span class="material-icons-outlined">payments</span>Öde</span>
      </div>
      <div class="rk-sub2" id="mduzQrLink" style="font-size:10.5px;margin-top:10px;word-break:break-all;opacity:.75"></div>
    </div>
    <div class="mduz-qr-foot">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="mduzQrKapat()">Kapat</button>
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="mduzQrYazdir()">
        <span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır
      </button>
    </div>
  </div>
</div>

{{-- Masa düzenle (ad · kapasite · bölge): /api/patron/masa-guncelle --}}
<div id="mduzEditModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card" style="max-width:400px;width:100%;padding:22px">
    <div style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:16px;display:flex;align-items:center;gap:8px"><span class="material-icons-outlined idz-mor">edit</span> Masa Düzenle</div>
    <input type="hidden" id="mduzEditId">
    <label class="rk-lbl" style="margin-top:0;font-size:12px">Masa adı</label>
    <input type="text" id="mduzEditAd" class="rk-inp" maxlength="40" placeholder='Örn. "Masa 1", "B4"'>
    <label class="rk-lbl" style="font-size:12px;margin-top:12px">Kapasite (kişi)</label>
    <input type="number" id="mduzEditKap" class="rk-inp" min="1" max="50">
    <label class="rk-lbl" style="font-size:12px;margin-top:12px">Bölge</label>
    <select id="mduzEditBolge" class="rk-inp">
      @foreach ($bolgeler as $b)<option value="{{ $b->id }}">{{ $b->ad }}</option>@endforeach
    </select>
    <div class="rk-sub" style="font-size:11px;margin-top:12px">QR kodu <b>değişmez</b>, basılı QR geçerli kalır.</div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="masaDuzenleKapat()">İptal</button>
      <button type="button" class="rk-btn" id="mduzEditKaydet" style="flex:1;justify-content:center;background:var(--mor1)" onclick="masaDuzenleKaydet()">
        <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
      </button>
    </div>
  </div>
</div>

<script>
var IDZ = {!! $idzJson !!};
var MDUZ_SUBE = IDZ.sube;
var mduzAktifQr = null, mduzAktifAd = null;
function mduzQrImgUrl(url, size){ return 'https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&margin=0&data='+encodeURIComponent(url); }
function masaQr(ad, url){
  if(!url){ alert('Bu masanın QR kodu henüz oluşmamış. Sayfayı yenileyince otomatik oluşur.'); return; }
  mduzAktifQr = url; mduzAktifAd = ad || '-';
  document.getElementById('mduzQrAd').textContent = mduzAktifAd;
  document.getElementById('mduzQrLink').textContent = url;
  document.getElementById('mduzQrImg').src = mduzQrImgUrl(url, 220);
  document.getElementById('mduzQrModal').style.display = 'flex';
}
function mduzQrKapat(){ document.getElementById('mduzQrModal').style.display='none'; }
(function(){ var m=document.getElementById('mduzQrModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) mduzQrKapat(); }); })();
function mduzEsc(s){ return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function mduzQrYazdir(){
  if(!mduzAktifQr){ return; }
  var w = window.open('', '_blank');
  if(!w){ alert('Yazdırma penceresi açılamadı (açılır pencere engellenmiş olabilir).'); return; }
  w.document.write('<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>QR Afiş</title><style>'+
    '@page{size:A6;margin:10mm}body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif}'+
    '.afis{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;height:100vh}'+
    '.s{font-size:18px;font-weight:800}.h{font-size:11px;color:#555;margin-top:4px}'+
    '.afis img{width:150px;height:150px;margin:14px 0}.ad{font-size:26px;font-weight:800}.alt{font-size:10px;color:#555;margin-top:3px}'+
    '</style></head><body><div class="afis"><div class="s">'+mduzEsc(MDUZ_SUBE)+'</div>'+
    '<div class="h">Karekodu telefonunuzla okutun</div><img src="'+mduzQrImgUrl(mduzAktifQr,300)+'">'+
    '<div class="ad">'+mduzEsc(mduzAktifAd)+'</div><div class="alt">Menü · Sipariş · Garson Çağır · Öde</div></div><'+'/body></html>');
  w.document.close();
  setTimeout(function(){ try{ w.focus(); w.print(); }catch(e){} }, 600);
}
// ---- Masa düzenle (ad · kapasite · bölge) ----
function masaDuzenleAc(m){
  document.getElementById('mduzEditId').value = m.id;
  document.getElementById('mduzEditAd').value = m.ad || '';
  document.getElementById('mduzEditKap').value = m.kapasite || 4;
  document.getElementById('mduzEditBolge').value = m.b || '';
  document.getElementById('mduzEditModal').style.display = 'flex';
  setTimeout(function(){ document.getElementById('mduzEditAd').focus(); }, 50);
}
function masaDuzenleKapat(){ document.getElementById('mduzEditModal').style.display='none'; }
(function(){ var m=document.getElementById('mduzEditModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) masaDuzenleKapat(); }); })();
function masaDuzenleKaydet(){
  var id = parseInt(document.getElementById('mduzEditId').value);
  var ad = document.getElementById('mduzEditAd').value.trim();
  var kap = parseInt(document.getElementById('mduzEditKap').value) || 1;
  var bolge = document.getElementById('mduzEditBolge').value;
  if(!ad){ alert('Masa adı boş olamaz.'); return; }
  var btn = document.getElementById('mduzEditKaydet'); if(btn){ btn.disabled = true; btn.style.opacity = .6; }
  fetch('/api/patron/masa-guncelle', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':(window.csrf||(document.querySelector('meta[name=csrf-token]')||{}).content||'')},
    body: JSON.stringify({ id:id, ad:ad, kapasite:kap, bolge_id: bolge })
  }).then(function(r){ return r.json(); }).then(function(d){
    if(d.ok==1){ location.reload(); }
    else { if(btn){ btn.disabled=false; btn.style.opacity=1; } alert(d.hata || 'Güncellenemedi.'); }
  }).catch(function(){ if(btn){ btn.disabled=false; btn.style.opacity=1; } alert('Sunucuya ulaşılamadı.'); });
}
// ---- Genel modallar ----
function idzModal(id){
  var m = document.getElementById(id); if(!m) return;
  if(id === 'idzMasaEkle'){ var s = document.getElementById('idzYeniMasaBolge'); if(s && idzAktif) s.value = idzAktif; }
  m.classList.add('acik');
  var i = m.querySelector('input[type=text]'); if(i) setTimeout(function(){ i.focus(); }, 50);
}
function idzModalKapat(el){ var m = el.closest('.idz-modal'); if(m) m.classList.remove('acik'); }
document.querySelectorAll('.idz-modal').forEach(function(m){ m.addEventListener('click', function(e){ if(e.target === m) m.classList.remove('acik'); }); });
function idzTasiAc(masaId){
  if(!IDZ.masalar.length){ rkBilgi('Taşınacak masa yok.', 'wn'); return; }
  if(masaId) document.getElementById('idzTasiMasa').value = masaId;
  idzModal('idzMasaTasi');
}
function idzOdak(id){ var e = document.getElementById(id); if(e) setTimeout(function(){ e.focus(); e.scrollIntoView({block:'center'}); }, 60); }
// ---- Sekmeler: POST sonrası aynı sekmede kal ----
function idzSekme(ad){
  var b = document.querySelector('.idz-sekme[data-sekme="'+ad+'"]'); if(!b) return;
  document.querySelectorAll('.idz-sekme').forEach(function(x){ x.classList.toggle('aktif', x === b); });
  document.querySelectorAll('.idz-panel').forEach(function(p){ p.classList.toggle('aktif', p.getAttribute('data-panel') === ad); });
  try{ sessionStorage.setItem('idz_sekme', ad); }catch(e){}
  if(ad === 'plan') idzCiz();
}
document.querySelectorAll('.idz-sekme').forEach(function(b){ b.addEventListener('click', function(){ idzSekme(b.getAttribute('data-sekme')); }); });

// ===== MASA PLANI =====
var idzAktif = IDZ.alanlar.length ? IDZ.alanlar[0].id : 0;
var idzOlcek = 1, idzVurgu = null, idzDuzen = false, idzSecili = null, idzHizala = true, idzGecmis = [], idzIlk = null, idzKirli = {};
var idzDurumlar = {bos:['Boş','#10B981'], dolu:['Dolu','#7C3AED'], rezerve:['Rezerve','#F59E0B'], kirli:['Kirli','#64748B'], birlesik:['Birleşik','#7C3AED']};
function idzAlanMasalari(){ return IDZ.masalar.filter(function(m){ return m.b === idzAktif; }); }
function idzIlkAlan(){ return IDZ.alanlar.length ? IDZ.alanlar[0].id : 0; }
function idzPlanSatir(m){ var p = IDZ.plan[m.id]; return p && +p.b === m.b ? p : null; }
function idzCiz(){
  var cer = document.getElementById('idzCerceve'), dun = document.getElementById('idzDunya');
  if(!cer || !cer.clientWidth) return;
  var list = idzAlanMasalari(), yer = idzYerlesim(list), alan = (IDZ.plan._alan && IDZ.plan._alan[idzAktif]) || {};
  idzDunyaOlcu(cer, dun, yer, idzOlcek, alan.oran);
  var q = (document.getElementById('idzMasaAra').value || '').trim().toLocaleLowerCase('tr');
  var h = idzObjelerHtml(idzDekorListe(IDZ.plan, idzAktif, idzIlkAlan()), idzSecili && idzSecili.tur === 'obje' ? idzSecili.id : -1);
  if(!idzDuzen && !IDZ.alanlar.length) h += '<div class="idz-plan-bos"><b>Henüz alan yok</b><span>Başlamak için bir alan ekleyin.</span><button type="button" class="idz-btn dolu" onclick="idzModal(\'idzBolgeEkle\')">Alan Ekle</button></div>';
  else if(!idzDuzen && !list.length) h += '<div class="idz-plan-bos"><b>Bu alanda masa yok</b><span>İlk masayı ekleyerek planı oluşturun.</span><button type="button" class="idz-btn dolu" onclick="idzModal(\'idzMasaEkle\')">Masa Ekle</button></div>';
  list.forEach(function(m){
    var p = idzPlanSatir(m), kay = p || yer.poz[m.id], boy = (p && p.boy) || 1;
    var g = idzMasaSvg(m, idzSekil(m, p), (p && p.rot) || 0), du = idzDurumlar[m.durum] || idzDurumlar.bos;
    var uyar = q && m.ad.toLocaleLowerCase('tr').indexOf(q) < 0;
    var kisa = String(m.ad).replace(/^masa\s+/i, '');
    var sec = idzSecili && idzSecili.tur === 'masa' && idzSecili.id === m.id;
    h += '<button type="button" class="idz-masa'+(g.yuvarlak ? ' yuv' : '')+(uyar ? ' sonuk' : '')+(idzVurgu === m.id ? ' vurgu' : '')+(sec ? ' sec' : '')+'" data-id="'+m.id+'" style="left:'+(kay.x / 10)+'%;top:'+(kay.y / 10)+'%;width:calc(var(--ts) * '+(g.oran * boy)+');--mb:'+boy+'" title="'+mduzEsc(m.ad)+' · '+m.kapasite+' kişi">'+g.svg+
      '<span class="et"><b>'+mduzEsc(kisa)+'</b><em><i style="background:'+du[1]+'"></i>'+du[0]+'</em></span></button>';
  });
  dun.innerHTML = h;
  dun.classList.toggle('duzen', idzDuzen);
  document.querySelectorAll('#idzAlanlar .idz-alan[data-alan]').forEach(function(a){ a.classList.toggle('aktif', +a.getAttribute('data-alan') === idzAktif); });
  var sec2 = document.getElementById('idzAlanSec'); if(sec2) sec2.value = idzAktif;
  document.getElementById('idzOran').value = String(alan.oran || 2.35);
}
function idzAlanSec(id, kaydir){
  idzAktif = id; idzOlcek = 1; idzSecili = null; idzPopKapat(); idzOzellikCiz();
  try{ sessionStorage.setItem('idz_alan', id); }catch(e){}
  idzCiz();
  if(kaydir) document.getElementById('idzCerceve').scrollIntoView({behavior:'smooth', block:'center'});
}
function idzZoom(d){
  idzOlcek = d === 0 ? 1 : Math.max(1, Math.min(2.4, Math.round((idzOlcek + d) * 10) / 10));
  idzPopKapat(); idzCiz();
  if(d === 0){ var c = document.getElementById('idzCerceve'); c.scrollLeft = 0; c.scrollTop = 0; }
}
document.getElementById('idzAlanSec').addEventListener('change', function(){ idzAlanSec(+this.value); });
document.getElementById('idzMasaAra').addEventListener('input', idzCiz);
document.querySelectorAll('#idzAlanlar .idz-alan[data-alan]').forEach(function(a){ a.addEventListener('click', function(){ idzAlanSec(+a.getAttribute('data-alan')); }); });

// ---- Düzenleme modu ----
function idzYedek(){ idzGecmis.push(JSON.stringify(IDZ.plan)); if(idzGecmis.length > 60) idzGecmis.shift(); idzGeriDurum(); }
function idzGeriDurum(){ var b = document.getElementById('idzGeri'); if(b) b.disabled = !idzGecmis.length; }
function idzGeriAl(){ if(!idzGecmis.length) return; IDZ.plan = JSON.parse(idzGecmis.pop()); idzSecili = null; idzGeriDurum(); idzOzellikCiz(); idzCiz(); }
function idzDegisti(){ idzKirli[idzAktif] = 1; }
function idzDuzenAc(){
  if(!IDZ.alanlar.length){ idzModal('idzBolgeEkle'); return; }
  idzDuzen = true; idzIlk = JSON.stringify(IDZ.plan); idzGecmis = []; idzKirli = {}; idzSecili = null; idzVurgu = null;
  idzPopKapat(); idzOlcek = 1; idzGeriDurum();
  document.querySelector('.idz-plan-kart').classList.add('duzen-acik');
  idzOzellikCiz(); idzCiz();
}
function idzDuzenKapat(){
  idzDuzen = false; idzSecili = null; idzKirli = {};
  document.querySelector('.idz-plan-kart').classList.remove('duzen-acik');
  idzOzellikCiz(); idzCiz();
}
function idzVazgec(){
  function kapat(){ IDZ.plan = JSON.parse(idzIlk); idzDuzenKapat(); }
  if(Object.keys(idzKirli).length) rkOnay('Kaydedilmemiş değişiklikler silinsin mi?', kapat, 'var(--kirmizi)', 'Vazgeç'); else kapat();
}
// Varsayılan dekor düzenlenince alana yazılır, sonra alanın kendi objeleri olur
function idzObjeler(){
  IDZ.plan._alan = IDZ.plan._alan || {};
  var a = IDZ.plan._alan[idzAktif] || {oran: 2.35};
  if(!a.kayitli){ a.dekor = idzDekorListe(IDZ.plan, idzAktif, idzIlkAlan()); a.kayitli = true; IDZ.plan._alan[idzAktif] = a; }
  return a.dekor;
}
// Masanın tam satırı (otomatik konumdaysa sabitlenir)
function idzMasaSatir(m){
  var p = idzPlanSatir(m);
  if(!p){ var o = idzYerlesim(idzAlanMasalari()).poz[m.id]; p = {b: m.b, x: Math.round(o.x), y: Math.round(o.y)}; IDZ.plan[m.id] = p; }
  return p;
}
function idzObjeEkle(t){
  if(!t) return;
  idzYedek();
  var l = idzObjeler(), d = IDZ_OBJE[t];
  var yer = idzYerlesim(idzAlanMasalari()), dolu = idzAlanMasalari().map(function(m){ return idzPlanSatir(m) || yer.poz[m.id]; }).concat(l), bos = {x: 500, y: 500}, enIyi = -1;
  [[500,500],[300,500],[700,500],[150,300],[850,300],[150,700],[850,700],[500,200],[500,800],[300,200],[700,200]].forEach(function(a){
    var en = 99999; dolu.forEach(function(k){ en = Math.min(en, Math.abs(k.x - a[0]) * 2.35 + Math.abs(k.y - a[1])); });
    if(en > enIyi){ enIyi = en; bos = {x: a[0], y: a[1]}; }
  });
  l.push({t: t, ad: d[3] ? d[2] : '', x: bos.x, y: bos.y, boy: 1, rot: 0});
  idzSecili = {tur: 'obje', id: l.length - 1};
  idzDegisti(); idzOzellikCiz(); idzCiz();
  document.getElementById('idzObjeSec').value = '';
}
function idzOran(v){ idzYedek(); idzObjeler(); IDZ.plan._alan[idzAktif].oran = +v; idzDegisti(); idzCiz(); }
function idzSablonUygula(tip){
  if(!tip) return;
  var list = idzAlanMasalari();
  document.getElementById('idzSablon').value = '';
  if(!list.length){ rkBilgi('Bu alanda masa yok.', 'wn'); return; }
  idzYedek();
  var poz = idzSablon(tip, list, (IDZ.plan._alan[idzAktif] || {}).oran);
  list.forEach(function(m){
    var p = idzMasaSatir(m); p.x = Math.round(poz[m.id].x); p.y = Math.round(poz[m.id].y); p.rot = 0;
    if(tip === 'banket') p.sekil = 'uzun';
    else if(tip === 'otomatik') delete p.sekil;
  });
  idzDegisti(); idzOzellikCiz(); idzCiz();
  rkBilgi('Şablon uygulandı. Masaları elle düzeltip Kaydet\'e basın.', 'ok');
}
function idzSeciliSil(){
  if(!idzSecili || idzSecili.tur !== 'obje') return;
  idzYedek(); idzObjeler().splice(idzSecili.id, 1); idzSecili = null; idzDegisti(); idzOzellikCiz(); idzCiz();
}
function idzSeciliAyar(alan, v){
  if(!idzSecili) return;
  var hedef = idzSecili.tur === 'masa' ? idzMasaSatir(IDZ.masalar.find(function(m){ return m.id === idzSecili.id; })) : idzObjeler()[idzSecili.id];
  hedef[alan] = v; idzDegisti(); idzCiz();
}
function idzOzellikCiz(){
  var k = document.getElementById('idzOzellik');
  if(!idzDuzen || !idzSecili){ k.classList.remove('acik'); k.innerHTML = ''; return; }
  var h = '', boy = 1, rot = 0, baslik = '';
  function kaydirma(ad, et, min, max, adim, deg, birim){ return '<label>'+et+' <output>'+deg+birim+'</output><input type="range" min="'+min+'" max="'+max+'" step="'+adim+'" value="'+deg+'" onpointerdown="idzYedek()" oninput="this.previousElementSibling.textContent=this.value+\''+birim+'\';idzSeciliAyar(\''+ad+'\',+this.value)"></label>'; }
  if(idzSecili.tur === 'masa'){
    var m = IDZ.masalar.find(function(x){ return x.id === idzSecili.id; }); if(!m) return;
    var p = idzPlanSatir(m), sk = idzSekil(m, p); boy = (p && p.boy) || 1; rot = (p && p.rot) || 0;
    baslik = mduzEsc(m.ad) + ' <small>' + m.kapasite + ' kişi</small>';
    h += '<div class="sekil">' + [['kare','crop_square','Kare'],['yuvarlak','circle','Yuvarlak'],['uzun','crop_16_9','Uzun']].map(function(s){ return '<button type="button" class="'+(sk === s[0] ? 'aktif' : '')+'" onclick="idzYedek();idzSeciliAyar(\'sekil\',\''+s[0]+'\');idzOzellikCiz()"><span class="material-icons-outlined">'+s[1]+'</span>'+s[2]+'</button>'; }).join('') + '</div>';
    h += kaydirma('boy', 'Boyut', .6, 1.6, .1, boy, 'x') + kaydirma('rot', 'Döndür', 0, 345, 15, rot, '°');
    h += '<div class="alt"><button type="button" class="idz-btn kucuk" onclick="masaDuzenleAc(IDZ.masalar.find(function(x){return x.id===idzSecili.id}))"><span class="material-icons-outlined">edit</span>Ad / Kapasite</button><button type="button" class="idz-btn kucuk" onclick="idzTasiAc(idzSecili.id)"><span class="material-icons-outlined">swap_horiz</span>Alana Taşı</button></div>';
  } else {
    var o = idzObjeler()[idzSecili.id], d = IDZ_OBJE[o.t] || IDZ_OBJE.diger; if(!o) return;
    boy = o.boy || 1; rot = o.rot || 0; baslik = d[2];
    if(d[3]) h += '<label>Etiket<input type="text" class="rk-inp" maxlength="40" value="'+mduzEsc(o.ad || '')+'" onfocus="idzYedek()" oninput="idzSeciliAyar(\'ad\',this.value)"></label>';
    h += kaydirma('boy', o.t === 'duvar' ? 'Uzunluk' : 'Boyut', .4, 3, .1, boy, 'x') + kaydirma('rot', 'Döndür', 0, 345, 15, rot, '°');
    h += '<div class="alt"><button type="button" class="idz-btn kucuk tehlike" onclick="idzSeciliSil()"><span class="material-icons-outlined">delete_outline</span>Sil</button></div>';
  }
  k.innerHTML = '<div class="b"><b>'+baslik+'</b><button type="button" class="mduz-ibtn" aria-label="Kapat" onclick="idzSecili=null;idzOzellikCiz();idzCiz()"><span class="material-icons-outlined">close</span></button></div>' + h;
  k.classList.add('acik');
}
// Normal modda tıkla = masa kartı. Düzenleme modunda tıkla = seç, sürükle = taşı.
(function(){
  var dun = document.getElementById('idzDunya'), el = null, bas = null, oynadi = false, sur = null, kilavuz = [];
  function kilavuzTemizle(){ kilavuz.forEach(function(k){ k.remove(); }); kilavuz = []; }
  function kilavuzCiz(yon, deger){ var k = document.createElement('i'); k.className = 'idz-kilavuz ' + yon; k.style[yon === 'dikey' ? 'left' : 'top'] = (deger / 10) + '%'; dun.appendChild(k); kilavuz.push(k); }
  // Diğer masalarla aynı hizaya gelince oturur, yoksa ızgaraya
  function hizala(x, y, kendi){
    if(!idzHizala) return {x: x, y: y};
    var yer = idzYerlesim(idzAlanMasalari()), yx = null, yy = null, enX = 14, enY = 14;
    idzAlanMasalari().forEach(function(m){
      if(m.id === kendi) return;
      var k = idzPlanSatir(m) || yer.poz[m.id];
      if(Math.abs(k.x - x) < enX){ enX = Math.abs(k.x - x); yx = k.x; }
      if(Math.abs(k.y - y) < enY){ enY = Math.abs(k.y - y); yy = k.y; }
    });
    kilavuzTemizle();
    if(yx !== null) kilavuzCiz('dikey', yx);
    if(yy !== null) kilavuzCiz('yatay', yy);
    return {x: yx !== null ? yx : Math.round(x / 25) * 25, y: yy !== null ? yy : Math.round(y / 25) * 25};
  }
  dun.addEventListener('pointerdown', function(e){
    var t = e.target.closest('.idz-masa, .idz-obje'); if(!t) { if(idzDuzen && idzSecili){ idzSecili = null; idzOzellikCiz(); idzCiz(); } return; }
    el = t; bas = {x: e.clientX, y: e.clientY}; oynadi = false; sur = null;
    if(idzDuzen) t.setPointerCapture(e.pointerId);
  });
  dun.addEventListener('pointermove', function(e){
    if(!el || !idzDuzen) return;
    if(!oynadi && Math.abs(e.clientX - bas.x) + Math.abs(e.clientY - bas.y) < 6) return;
    var masa = el.classList.contains('idz-masa'), id = +el.getAttribute(masa ? 'data-id' : 'data-obje');
    if(!oynadi){
      oynadi = true; el.classList.add('tutuldu'); idzYedek();
      sur = masa ? idzMasaSatir(IDZ.masalar.find(function(m){ return m.id === id; })) : idzObjeler()[id];
    }
    var r = dun.getBoundingClientRect();
    var x = Math.max(15, Math.min(985, Math.round((e.clientX - r.left) / r.width * 1000)));
    var y = Math.max(15, Math.min(985, Math.round((e.clientY - r.top) / r.height * 1000)));
    var h = masa ? hizala(x, y, id) : (idzHizala ? {x: Math.round(x / 25) * 25, y: Math.round(y / 25) * 25} : {x: x, y: y});
    sur.x = h.x; sur.y = h.y;
    el.style.left = (h.x / 10) + '%'; el.style.top = (h.y / 10) + '%';
    idzDegisti();
  });
  function birak(e){
    if(!el) return;
    var t = el, masa = t.classList.contains('idz-masa'); el = null; t.classList.remove('tutuldu'); kilavuzTemizle();
    if(e.type !== 'pointerup') return;
    if(!idzDuzen){ if(masa) idzPopAc(+t.getAttribute('data-id'), t); return; }
    if(oynadi){ idzCiz(); return; }
    idzSecili = masa ? {tur: 'masa', id: +t.getAttribute('data-id')} : {tur: 'obje', id: +t.getAttribute('data-obje')};
    idzOzellikCiz(); idzCiz();
  }
  dun.addEventListener('pointerup', birak);
  dun.addEventListener('pointercancel', birak);
  dun.addEventListener('click', function(e){ if(e.target.closest('.idz-masa')) e.preventDefault(); });
  // Klavye: Enter/Boşluk aç veya seç, düzenleme modunda ok tuşları taşır
  dun.addEventListener('keydown', function(e){
    var t = e.target.closest('.idz-masa'); if(!t || !(e.key === 'Enter' || e.key === ' ')) return;
    e.preventDefault();
    if(idzDuzen){ idzSecili = {tur: 'masa', id: +t.getAttribute('data-id')}; idzOzellikCiz(); idzCiz(); }
    else idzPopAc(+t.getAttribute('data-id'), t);
  });
  document.addEventListener('keydown', function(e){
    if(!idzDuzen || /^(INPUT|SELECT|TEXTAREA)$/.test(e.target.tagName)) return;
    if((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z'){ e.preventDefault(); idzGeriAl(); return; }
    if(!idzSecili) return;
    if(e.key === 'Delete' || e.key === 'Backspace'){ if(idzSecili.tur === 'obje'){ e.preventDefault(); idzSeciliSil(); } return; }
    var d = {ArrowLeft:[-1,0], ArrowRight:[1,0], ArrowUp:[0,-1], ArrowDown:[0,1]}[e.key]; if(!d) return;
    e.preventDefault(); idzYedek();
    var s = idzSecili.tur === 'masa' ? idzMasaSatir(IDZ.masalar.find(function(m){ return m.id === idzSecili.id; })) : idzObjeler()[idzSecili.id], ad = e.shiftKey ? 25 : 5;
    s.x = Math.max(15, Math.min(985, s.x + d[0] * ad)); s.y = Math.max(15, Math.min(985, s.y + d[1] * ad));
    idzDegisti(); idzCiz();
  });
})();
function idzPlanKaydet(){
  var ids = Object.keys(idzKirli);
  if(!ids.length){ idzDuzenKapat(); rkBilgi('Değişiklik yok.', 'info'); return; }
  var alanlar = {};
  ids.forEach(function(bid){
    var b = +bid, list = IDZ.masalar.filter(function(m){ return m.b === b; }), yer = idzYerlesim(list), kon = {};
    list.forEach(function(m){ var p = idzPlanSatir(m), k = p || yer.poz[m.id]; kon[m.id] = {x: Math.round(k.x), y: Math.round(k.y)}; if(p){ if(p.sekil) kon[m.id].sekil = p.sekil; if(p.boy) kon[m.id].boy = p.boy; if(p.rot !== undefined) kon[m.id].rot = p.rot; } });
    var a = IDZ.plan._alan[b] || {};
    alanlar[b] = {oran: a.oran || 2.35, konumlar: kon, objeler: a.kayitli ? a.dekor : idzDekorListe(IDZ.plan, b, idzIlkAlan())};
  });
  var d = document.getElementById('idzKaydet'); d.disabled = true;
  fetch('/isletme-duzeni/plan-kaydet', {
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.csrf||''},
    body: JSON.stringify({alanlar: alanlar})
  }).then(function(r){ return r.json(); }).then(function(c){
    d.disabled = false;
    if(c.ok != 1){ rkBilgi(c.hata || 'Plan kaydedilemedi.', 'er'); return; }
    IDZ.masalar.forEach(function(m){ var k = alanlar[m.b] && alanlar[m.b].konumlar[m.id]; if(k) IDZ.plan[m.id] = Object.assign({b: m.b}, k); });
    idzDuzenKapat(); rkBilgi('Masa planı kaydedildi, Salon Şeması da güncellendi.', 'ok');
  }).catch(function(){ d.disabled = false; rkBilgi('Sunucuya ulaşılamadı.', 'er'); });
}
window.addEventListener('beforeunload', function(e){ if(idzDuzen && Object.keys(idzKirli).length){ e.preventDefault(); e.returnValue = ''; } });
// ---- Masa kartı ----
function idzPopKapat(){ var p = document.getElementById('idzPop'); p.classList.remove('acik'); idzVurgu = null; }
function idzPopAc(id, t){
  var m = IDZ.masalar.find(function(x){ return x.id === id; }); if(!m) return;
  var du = idzDurumlar[m.durum] || idzDurumlar.bos, alan = IDZ.alanlar.find(function(a){ return a.id === m.b; });
  var gar = IDZ.garsonlar.filter(function(g){ return g.m.indexOf(m.id) >= 0 || g.b.indexOf(m.b) >= 0; }).map(function(g){ return g.ad; });
  var p = document.getElementById('idzPop');
  p.innerHTML = '<div class="b"><b>'+mduzEsc(m.ad)+'</b><span class="d" style="--r:'+du[1]+'"><i></i>'+du[0]+'</span></div>'+
    '<div class="s">'+m.kapasite+' kişi · '+mduzEsc(alan ? alan.ad : 'Bölgesiz')+(m.tutar > 0 ? ' · '+Math.round(m.tutar).toLocaleString('tr-TR')+' ₺' : '')+'</div>'+
    '<div class="s">Garson: '+(gar.length ? mduzEsc(gar.join(', ')) : 'Atanmadı')+'</div>'+
    '<div class="a"><button type="button" data-i="qr"><span class="material-icons-outlined">qr_code_2</span>QR</button><button type="button" data-i="duz"><span class="material-icons-outlined">edit</span>Düzenle</button><button type="button" data-i="tas"><span class="material-icons-outlined">swap_horiz</span>Taşı</button><button type="button" data-i="sil" class="k"><span class="material-icons-outlined">delete_outline</span>Arşivle</button></div>';
  p.querySelector('[data-i=qr]').onclick = function(){ idzPopKapat(); masaQr(m.ad, m.qr); };
  p.querySelector('[data-i=duz]').onclick = function(){ idzPopKapat(); masaDuzenleAc(m); };
  p.querySelector('[data-i=tas]').onclick = function(){ idzPopKapat(); idzTasiAc(m.id); };
  p.querySelector('[data-i=sil]').onclick = function(){
    idzPopKapat();
    var f = document.getElementById('idzSilForm'); f.querySelector('[name=id]').value = m.id;
    rkOnayForm(f, m.ad + ' arşive taşınsın mı? (QR korunur)', 'var(--kirmizi)', 'Arşivle');
  };
  p.classList.add('acik');
  var r = t.getBoundingClientRect(), w = p.offsetWidth, h2 = p.offsetHeight;
  var x = Math.min(window.innerWidth - w - 10, Math.max(10, r.left + r.width / 2 - w / 2));
  var y = r.top - h2 - 8; if(y < 10) y = Math.min(window.innerHeight - h2 - 10, r.bottom + 8);
  p.style.left = x + 'px'; p.style.top = y + 'px';
}
document.addEventListener('pointerdown', function(e){ if(!e.target.closest('#idzPop') && !e.target.closest('.idz-masa')) idzPopKapat(); });
document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ idzPopKapat(); document.querySelectorAll('.idz-modal.acik').forEach(function(m){ m.classList.remove('acik'); }); } });
// ---- Üst arama: masa / garson / bölge / kasa ----
idzAramaKur(function(esle){
  var h = [];
  IDZ.masalar.filter(function(m){ return esle(m.ad); }).slice(0, 6).forEach(function(m){ var al = IDZ.alanlar.find(function(a){ return a.id === m.b; }); h.push(['table_restaurant', m.ad, al ? al.ad : 'Bölgesiz', function(){ idzSekme('plan'); idzAlanSec(m.b, true); idzVurgu = m.id; idzCiz(); }]); });
  IDZ.garsonlar.filter(function(g){ return esle(g.ad); }).slice(0, 4).forEach(function(g){ h.push(['groups', g.ad, 'Garson', function(){ garsonAtaAc(g.id); }]); });
  IDZ.alanlar.filter(function(a){ return esle(a.ad); }).slice(0, 4).forEach(function(a){ h.push(['person_pin_circle', a.ad, 'Bölge', function(){ idzSekme('plan'); idzAlanSec(a.id, true); }]); });
  IDZ.kasalar.filter(function(k){ return esle(k.ad); }).slice(0, 4).forEach(function(k){ h.push(['point_of_sale', k.ad, 'Kasa', function(){ idzSekme('kasa'); }]); });
  return h;
});
// ---- Saat + ilk çizim ----
(function(){
  try{
    var a = sessionStorage.getItem('idz_alan');
    if(a !== null && IDZ.alanlar.some(function(x){ return x.id === +a; })) idzAktif = +a;
    if(sessionStorage.getItem('idz_don')){ sessionStorage.removeItem('idz_don'); var s = sessionStorage.getItem('idz_sekme'); if(s) idzSekme(s); }
  }catch(e){}
  idzCiz();
  var zaman = null;
  window.addEventListener('resize', function(){ clearTimeout(zaman); zaman = setTimeout(idzCiz, 80); });
  if(window.ResizeObserver) new ResizeObserver(function(){ clearTimeout(zaman); zaman = setTimeout(idzCiz, 80); }).observe(document.querySelector('.idz'));
})();
// ---- Kaydırma konumunu koru: POST→redirect sonrası ana içerik en üste zıplamasın ----
(function(){
  var KEY = 'idz_scroll';
  function adaylar(){ return [document.querySelector('.rk-body'), document.querySelector('.rk-main'), document.scrollingElement, document.documentElement, document.body].filter(Boolean); }
  function getY(){ var y=0; adaylar().forEach(function(el){ if(el.scrollTop>y) y=el.scrollTop; }); if(window.scrollY>y) y=window.scrollY; return y; }
  function setY(y){ adaylar().forEach(function(el){ try{ el.scrollTop=y; }catch(e){} }); try{ window.scrollTo(0,y); }catch(e){} }
  try{ var s=sessionStorage.getItem(KEY); if(s!==null){ var y=parseInt(s,10)||0; sessionStorage.removeItem(KEY);
    setY(y); requestAnimationFrame(function(){ setY(y); }); setTimeout(function(){ setY(y); }, 60); setTimeout(function(){ setY(y); }, 200); } }catch(e){}
  document.addEventListener('submit', function(){ try{ sessionStorage.setItem(KEY, getY()); sessionStorage.setItem('idz_don', '1'); }catch(e){} }, true);
})();
</script>
@endsection
@push('head')
@include('layout._idz_stil')
@endpush

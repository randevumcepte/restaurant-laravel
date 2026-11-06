@extends('layout.app')
@section('title', 'İşletme Düzeni')
@section('rk_ikon', 'grid_view')
@section('baslik', 'İşletme Düzeni')
@section('rk_alt', 'Bölge - Masa - Garson - Kasa Düzenini Kolayca Yönetin')

@section('rk_araclar')
  <div class="idz-ara idz-ust-ara">
    <span class="material-icons-outlined">search</span>
    <input type="search" id="idzGenelAra" placeholder="Restoran içinde ara..." autocomplete="off" aria-label="Restoran içinde ara">
    <div class="idz-ara-sonuc" id="idzAraSonuc"></div>
  </div>
  <a href="/garson-cagrilari" class="idz-ust-ikon" title="Garson Çağrıları" aria-label="Garson Çağrıları">
    <span class="material-icons-outlined">notifications</span>
    @if (($cagriSay ?? 0) > 0)<b>{{ $cagriSay }}</b>@endif
  </a>
  <div class="idz-tarih">
    <span class="material-icons-outlined">calendar_month</span>
    <span>{{ now()->locale('tr')->translatedFormat('j F Y, l') }}</span>
    <b id="idzSaat">{{ now()->format('H:i') }}</b>
  </div>
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
  <button type="button" class="idz-sekme" data-sekme="garson" role="tab"><span class="material-icons-outlined">groups</span>Garson Yönetimi</button>
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
      <button type="button" class="idz-btn dolu" id="idzKaydet" onclick="idzPlanKaydet()"><span class="material-icons-outlined">save_as</span>Kaydet</button>
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

      <div class="idz-cerceve" id="idzCerceve">
        <div class="idz-dunya" id="idzDunya"></div>
        <div class="idz-zoom">
          <button type="button" onclick="idzZoom(.2)" title="Yakınlaştır" aria-label="Yakınlaştır"><span class="material-icons-outlined">add</span></button>
          <button type="button" onclick="idzZoom(-.2)" title="Uzaklaştır" aria-label="Uzaklaştır"><span class="material-icons-outlined">remove</span></button>
          <button type="button" onclick="idzZoom(0)" title="Ortala" aria-label="Ortala"><span class="material-icons-outlined">my_location</span></button>
        </div>
      </div>

      <div class="idz-sag">
        <div class="idz-kart">
          <div class="idz-kart-bas">
            <span class="material-icons-outlined idz-mor">groups</span><h3>Garson Atamaları</h3>
            <button type="button" class="idz-link" onclick="idzSekme('garson')">Tüm Garsonlar<span class="material-icons-outlined">arrow_forward</span></button>
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
            <button type="button" onclick="idzSekme('garson')"><span class="material-icons-outlined">groups</span>Garson Ata</button>
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
          <div><i></i><time>{{ \Carbon\Carbon::parse($h->created_at)->format('H:i') }}</time><span title="{{ $h->aksiyon }}">{{ $h->aksiyon }}</span></div>
        @empty
          <div class="idz-bos">Henüz hareket yok. Bu sayfada yaptığınız değişiklikler burada görünür.</div>
        @endforelse
      </div>
    </div>
  </div>
</section>

{{-- ============ GARSON YÖNETİMİ ============ --}}
<section class="idz-panel" data-panel="garson">
  <div class="idz-kart">
    <div class="idz-kart-bas"><span class="material-icons-outlined idz-mor">groups</span><h3>Garson Yönetimi</h3><span class="idz-rozet">{{ $garsonlar->count() }}</span></div>
    <p class="idz-acik">Her garsonun hangi <b>bölge</b> ve/veya <b>masalara</b> bakacağını seç. Bölge seçersen o bölgenin tüm masaları dahildir. Hiç seçmezsen tüm masalara bakar.</p>
    @if ($garsonlar->count() && $masalar->count())
      <div class="gata-list">
        @foreach ($garsonlar as $g)
          @php $atandi = count($g->bolge_idler ?? []) > 0 || count($g->masa_idler ?? []) > 0; @endphp
          <div class="gata-row">
            <span class="idz-avatar">{{ mb_strtoupper(mb_substr($g->ad, 0, 1)) }}</span>
            <div class="gata-bilgi">
              <div class="gata-ad">{{ $g->ad }} <span class="gata-rol">· {{ $rolAd[$g->rol] ?? ucfirst($g->rol ?? '') }}</span></div>
              <div class="gata-ozet" style="color:{{ $atandi ? 'var(--mor1)' : 'var(--sub2)' }}"><span class="material-icons-outlined" style="font-size:13px;vertical-align:-2px">{{ $atandi ? 'check_circle' : 'remove_circle_outline' }}</span> {{ $garsonOzet($g) }}</div>
            </div>
            <button type="button" class="idz-btn gata-ata-btn" onclick="garsonAtaAc({{ $g->id }})"><span class="material-icons-outlined">tune</span>Ata / Düzenle</button>
          </div>
        @endforeach
      </div>

      {{-- Gizli atama panelleri: tıklanınca modala taşınır --}}
      <div id="gata-paneller" style="display:none">
        @foreach ($garsonlar as $g)
          <div id="gata-panel-{{ $g->id }}" data-ad="{{ $g->ad }}">
            <form method="POST" action="/isletme-duzeni/garson-ata">
              @csrf<input type="hidden" name="personel_id" value="{{ $g->id }}">
              @foreach ($bolgeler as $b)
                @php $grupM = $masaByBolge[$b->id] ?? collect(); $bSecili = in_array((int)$b->id, $g->bolge_idler ?? []); @endphp
                <div style="border:1px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;color:var(--ink);font-size:13.5px">
                    <input type="checkbox" name="bolge_idler[]" value="{{ $b->id }}" {{ $bSecili ? 'checked' : '' }} onchange="rkBolgeTog(this)">
                    <span class="material-icons-outlined idz-mor" style="font-size:17px">deck</span> {{ $b->ad }} <span class="rk-sub2" style="font-weight:500;font-size:11px">(tüm bölge)</span>
                  </label>
                  @if ($grupM->count())
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;padding-left:4px" data-bolge="{{ $b->id }}">
                      @foreach ($grupM as $m)
                        <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px;{{ $bSecili ? 'opacity:.5' : '' }}">
                          <input type="checkbox" name="masa_idler[]" value="{{ $m->id }}" {{ in_array((int)$m->id, $g->masa_idler ?? []) ? 'checked' : '' }} {{ $bSecili ? 'disabled' : '' }}> {{ $m->ad }}
                        </label>
                      @endforeach
                    </div>
                  @endif
                </div>
              @endforeach
              <div style="display:flex;gap:10px;margin-top:4px">
                <button type="button" class="idz-btn" style="flex:1;justify-content:center" onclick="garsonAtaKapat()">İptal</button>
                <button type="submit" class="idz-btn dolu" style="flex:2;justify-content:center"><span class="material-icons-outlined">save</span>Kaydet</button>
              </div>
            </form>
          </div>
        @endforeach
      </div>
    @else
      <div class="idz-bos">Garson ataması için önce masa ve garson (personel) ekleyin.</div>
    @endif
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
        ['/masa-yonetim', 'qr_code_2', 'QR Afişleri / Arşiv', 'Toplu QR yazdır, arşivlenen masaları geri al'],
        ['/salon-sema', 'grid_on', 'Salon Şeması', 'Görsel kroki ve ısı haritası'],
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

{{-- SVG gradyanları (masa çizimleri ortak kullanır) --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
  <linearGradient id="idzAhsap" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E2AC73"/><stop offset="1" stop-color="#B9773F"/></linearGradient>
  <linearGradient id="idzMorG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#A06BF6"/><stop offset="1" stop-color="#6D28D9"/></linearGradient>
  <linearGradient id="idzTabla" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FCE7D6"/><stop offset="1" stop-color="#F3CFB2"/></linearGradient>
  <linearGradient id="idzYaprak" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6FA845"/><stop offset="1" stop-color="#2F6B2C"/></linearGradient>
  <linearGradient id="idzDesk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#B98A5F"/><stop offset="1" stop-color="#7C5536"/></linearGradient>
</defs></svg>

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

{{-- Garson atama modalı --}}
<div id="gataModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card gata-modal-box">
    <div class="gata-modal-head">
      <span class="material-icons-outlined idz-mor" style="font-size:20px">room_service</span>
      <span id="gataModalAd" style="font-weight:800;color:var(--ink);font-size:15px;flex:1">Garson</span>
      <button type="button" class="mduz-ibtn" style="color:var(--sub)" onclick="garsonAtaKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="rk-sub2" style="font-size:11.5px;margin:2px 0 12px">Bölge seçersen o bölgenin tüm masaları dahildir. Hiç seçmezsen bu garson tüm masalara bakar.</div>
    <div id="gataModalBody"></div>
  </div>
</div>

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
// ---- Garson atama modalı (gizli paneli modala taşı) ----
function garsonAtaAc(id){
  var panel = document.getElementById('gata-panel-'+id);
  if(!panel){ window.rkBilgi && rkBilgi('Atama için önce masa ekleyin.', 'wn'); return; }
  document.getElementById('gataModalAd').textContent = panel.getAttribute('data-ad') || 'Garson';
  document.getElementById('gataModalBody').appendChild(panel);
  panel.style.display = '';
  document.getElementById('gataModal').style.display = 'flex';
}
function garsonAtaKapat(){
  var body = document.getElementById('gataModalBody');
  var store = document.getElementById('gata-paneller');
  while(body.firstChild){ if(body.firstChild.style) body.firstChild.style.display='none'; store.appendChild(body.firstChild); }
  document.getElementById('gataModal').style.display = 'none';
}
(function(){ var m=document.getElementById('gataModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) garsonAtaKapat(); }); })();
function rkBolgeTog(cb){
  // Bölge "tümü" seçilince o bölgenin tek tek masa kutuları kapanır (zaten dahil)
  var box = cb.closest('div').querySelector('[data-bolge]');
  if(!box) return;
  box.querySelectorAll('input[type=checkbox]').forEach(function(x){
    x.disabled = cb.checked; if(cb.checked) x.checked = false;
    x.closest('label').style.opacity = cb.checked ? .5 : 1;
  });
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
var idzOlcek = 1, idzDegisen = {}, idzKonum = {}, idzVurgu = null;
var idzDurumlar = {bos:['Boş','#10B981'], dolu:['Dolu','#7C3AED'], rezerve:['Rezerve','#F59E0B'], kirli:['Kirli','#64748B'], birlesik:['Birleşik','#7C3AED']};
function idzAlanMasalari(){ return IDZ.masalar.filter(function(m){ return m.b === idzAktif; }); }
// Kayıtlı konumu olmayan masalar sıraya dizilir; alt ortadaki resepsiyon alanı boş bırakılır
function idzYerlesim(list){
  var n = list.length, C = Math.max(2, Math.ceil(Math.sqrt(n * 1.8)));
  var ayrilan = C % 2 ? 1 : 2, R = Math.max(1, Math.ceil((n + ayrilan) / C));
  var ust = C * (R - 1), kalan = Math.max(0, n - ust), xs = [];
  var solS = Math.ceil(kalan / 2), sagS = kalan - solS, i;
  for(i = 0; i < solS; i++) xs.push(60 + (i + .5) * 320 / solS);
  for(i = 0; i < sagS; i++) xs.push(620 + (i + .5) * 320 / sagS);
  var rowH = 920 / R, colW = 880 / C, out = {};
  list.forEach(function(m, k){
    if(k < ust) out[m.id] = {x: 60 + ((k % C) + .5) * colW, y: 40 + (Math.floor(k / C) + .5) * rowH};
    else out[m.id] = {x: xs[k - ust], y: 40 + (R - .5) * rowH};
  });
  return {poz: out, C: C, R: R};
}
function idzSandalye(x, y, rot, dolgu, koyu){
  return '<g transform="translate('+x+' '+y+') rotate('+rot+')">'+
    '<rect x="-17" y="-15" width="34" height="30" rx="9" fill="'+dolgu+'"/><rect x="-12" y="-10" width="24" height="13" rx="6" fill="#fff" opacity=".16"/>'+
    '<path d="M-20 -7Q-20 -24 0 -24Q20 -24 20 -7L14 -7Q14 -16 0 -16Q-14 -16 -14 -7Z" fill="'+koyu+'"/></g>';
}
function idzMasaSvg(m){
  var dolu = m.durum === 'dolu' || m.durum === 'birlesik';
  var d = dolu ? 'url(#idzMorG)' : 'url(#idzAhsap)', k = dolu ? '#5B21B6' : '#8A5429', s = '', kap = m.kapasite, i;
  var ring = dolu ? '#E6D6FB' : '#E3B98E';
  if(m.sekil === 'yuvarlak' || kap <= 2){
    var N = Math.max(2, Math.min(8, kap)), ofs = N === 2 ? 0 : 45;
    for(i = 0; i < N; i++){ var a = (ofs + i * 360 / N) * Math.PI / 180; s += idzSandalye((100 + Math.sin(a) * 74).toFixed(1), (100 - Math.cos(a) * 74).toFixed(1), (ofs + i * 360 / N).toFixed(0), d, k); }
    s += '<circle cx="100" cy="100" r="60" fill="'+ring+'"/><circle cx="100" cy="100" r="53" fill="#FFF9F0"/>';
    return {svg: '<svg viewBox="0 0 200 200">'+s+'</svg>', oran: 1, yuvarlak: true};
  }
  if(kap >= 5){
    var yan = Math.min(4, Math.max(2, Math.floor((kap - 2) / 2)));
    for(i = 0; i < yan; i++){ var x = 58 + (i + .5) * 184 / yan; s += idzSandalye(x, 30, 0, d, k) + idzSandalye(x, 170, 180, d, k); }
    s += idzSandalye(30, 100, 270, d, k) + idzSandalye(270, 100, 90, d, k);
    s += '<rect x="46" y="48" width="208" height="104" rx="9" fill="#D7A77B"/><rect x="51" y="53" width="198" height="94" rx="6" fill="url(#idzTabla)"/>';
    return {svg: '<svg viewBox="0 0 300 200">'+s+'</svg>', oran: 1.5};
  }
  s += idzSandalye(30, 100, 270, d, k) + idzSandalye(170, 100, 90, d, k);
  if(kap > 2) s += idzSandalye(100, 30, 0, d, k) + idzSandalye(100, 170, 180, d, k);
  s += '<rect x="46" y="46" width="108" height="108" rx="9" fill="#D7A77B"/><rect x="51" y="51" width="98" height="98" rx="6" fill="url(#idzTabla)"/>';
  return {svg: '<svg viewBox="0 0 200 200">'+s+'</svg>', oran: 1};
}
function idzBitki(x, y, boy, adet){
  var b = '';
  [[adet, 49, 15, 'url(#idzYaprak)', 0], [adet - 3, 37, 13, '#4F8F38', 14], [adet - 6, 25, 10, '#86BC58', 7]].forEach(function(h){
    for(var i = 0; i < h[0]; i++){
      var a = i * 360 / h[0] + h[4], l = h[1] - (i % 2 ? 5 : 0);
      b += '<path transform="rotate('+a+' 50 50)" d="M50 50Q'+(50 - h[2])+' '+(50 - l * .6)+' 50 '+(50 - l)+'Q'+(50 + h[2])+' '+(50 - l * .6)+' 50 50Z" fill="'+h[3]+'"/>';
    }
  });
  return '<svg class="idz-bitki" viewBox="0 0 100 100" style="left:'+x+'%;top:'+y+'%;width:'+boy+'%"><circle cx="50" cy="50" r="19" fill="#C9A684"/><circle cx="50" cy="50" r="14" fill="#6B4A2E"/>'+b+'</svg>';
}
var idzDekor = idzBitki(.6,1,5.4,18) + idzBitki(94,1,5.4,18) + idzBitki(.6,44,5,18) + idzBitki(94.4,44,5,18) + idzBitki(5.6,83,5.6,18) + idzBitki(93.8,84,5.8,18) + idzBitki(36,88,4,14) + idzBitki(60,88,4,14) + idzBitki(21,.5,3.4,12) + idzBitki(75.6,.5,3.4,12)
  + '<i class="idz-isik" style="left:2%;top:26%"></i><i class="idz-isik" style="left:2%;top:68%"></i><i class="idz-isik" style="right:2%;top:26%"></i><i class="idz-isik" style="right:2%;top:68%"></i><i class="idz-isik" style="left:34%;top:1.5%"></i><i class="idz-isik" style="right:34%;top:1.5%"></i><i class="idz-isik" style="left:32%;bottom:2%"></i><i class="idz-isik" style="right:32%;bottom:2%"></i>'
  + '<div class="idz-resepsiyon"><svg viewBox="0 0 220 110"><path d="M0 110Q14 66 62 66L158 66Q206 66 220 110Z" fill="#F4E7D6" opacity=".8"/><rect x="22" y="30" width="176" height="66" rx="9" fill="url(#idzDesk)"/><rect x="16" y="22" width="188" height="20" rx="7" fill="#D2A77C"/><rect x="86" y="2" width="48" height="26" rx="3" fill="#2B2B33"/><rect x="104" y="28" width="12" height="6" fill="#55555f"/></svg><span>ResteOS</span></div>';
function idzCiz(){
  var cer = document.getElementById('idzCerceve'), dun = document.getElementById('idzDunya');
  if(!cer || !cer.clientWidth) return;
  var list = idzAlanMasalari(), yer = idzYerlesim(list);
  var W = Math.max(cer.clientWidth, 720) * idzOlcek, H = Math.max(W / 2.35, yer.R * 118 * idzOlcek);
  var ts = Math.max(66 * idzOlcek, Math.min(W * .88 / yer.C * .66, H * .92 / yer.R * .9));
  cer.style.height = Math.max(Math.min(H, Math.max(cer.clientWidth / 2.35, yer.R * 118)), 360) + 'px';
  dun.style.width = W + 'px'; dun.style.height = H + 'px';
  dun.style.setProperty('--ts', ts + 'px');
  var q = (document.getElementById('idzMasaAra').value || '').trim().toLocaleLowerCase('tr');
  var h = idzDekor;
  if(!IDZ.alanlar.length) h += '<div class="idz-plan-bos"><b>Henüz alan yok</b><span>Başlamak için bir alan ekleyin.</span><button type="button" class="idz-btn dolu" onclick="idzModal(\'idzBolgeEkle\')">Alan Ekle</button></div>';
  else if(!list.length) h += '<div class="idz-plan-bos"><b>Bu alanda masa yok</b><span>İlk masayı ekleyerek planı oluşturun.</span><button type="button" class="idz-btn dolu" onclick="idzModal(\'idzMasaEkle\')">Masa Ekle</button></div>';
  list.forEach(function(m){
    var kay = idzKonum[m.id] || (IDZ.plan[m.id] && +IDZ.plan[m.id].b === m.b ? IDZ.plan[m.id] : null) || yer.poz[m.id];
    var g = idzMasaSvg(m), du = idzDurumlar[m.durum] || idzDurumlar.bos;
    var uyar = q && m.ad.toLocaleLowerCase('tr').indexOf(q) < 0;
    var kisa = String(m.ad).replace(/^masa\s+/i, '');
    h += '<button type="button" class="idz-masa'+(g.yuvarlak ? ' yuv' : '')+(uyar ? ' sonuk' : '')+(idzVurgu === m.id ? ' vurgu' : '')+'" data-id="'+m.id+'" style="left:'+(kay.x / 10)+'%;top:'+(kay.y / 10)+'%;width:calc(var(--ts) * '+g.oran+')" title="'+mduzEsc(m.ad)+' · '+m.kapasite+' kişi">'+g.svg+
      '<span class="et"><b>'+mduzEsc(kisa)+'</b><em><i style="background:'+du[1]+'"></i>'+du[0]+'</em></span></button>';
  });
  dun.innerHTML = h;
  document.querySelectorAll('#idzAlanlar .idz-alan[data-alan]').forEach(function(a){ a.classList.toggle('aktif', +a.getAttribute('data-alan') === idzAktif); });
  var sec = document.getElementById('idzAlanSec'); if(sec) sec.value = idzAktif;
  document.getElementById('idzKaydet').classList.toggle('bekliyor', Object.keys(idzDegisen).length > 0);
}
function idzAlanSec(id, kaydir){
  idzAktif = id; idzOlcek = 1; idzPopKapat();
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
// Sürükle = konum değiştir, tıkla = masa kartı
(function(){
  var dun = document.getElementById('idzDunya'), el = null, bas = null, oynadi = false;
  dun.addEventListener('pointerdown', function(e){
    var t = e.target.closest('.idz-masa'); if(!t) return;
    el = t; bas = {x: e.clientX, y: e.clientY}; oynadi = false;
    t.setPointerCapture(e.pointerId);
  });
  dun.addEventListener('pointermove', function(e){
    if(!el) return;
    if(!oynadi && Math.abs(e.clientX - bas.x) + Math.abs(e.clientY - bas.y) < 6) return;
    if(!oynadi){ oynadi = true; el.classList.add('tutuldu'); idzPopKapat(); }
    var r = dun.getBoundingClientRect();
    var x = Math.max(30, Math.min(970, Math.round((e.clientX - r.left) / r.width * 1000)));
    var y = Math.max(30, Math.min(970, Math.round((e.clientY - r.top) / r.height * 1000)));
    el.style.left = (x / 10) + '%'; el.style.top = (y / 10) + '%';
    var id = el.getAttribute('data-id');
    idzKonum[id] = {x: x, y: y}; idzDegisen[id] = {x: x, y: y};
    document.getElementById('idzKaydet').classList.add('bekliyor');
  });
  function birak(e){
    if(!el) return;
    var t = el; el.classList.remove('tutuldu'); el = null;
    if(!oynadi && e.type === 'pointerup') idzPopAc(+t.getAttribute('data-id'), t);
  }
  dun.addEventListener('pointerup', birak);
  dun.addEventListener('pointercancel', birak);
  dun.addEventListener('click', function(e){ if(e.target.closest('.idz-masa')) e.preventDefault(); });
  // Klavyeyle de açılabilsin
  dun.addEventListener('keydown', function(e){ var t = e.target.closest('.idz-masa'); if(t && (e.key === 'Enter' || e.key === ' ')){ e.preventDefault(); idzPopAc(+t.getAttribute('data-id'), t); } });
})();
function idzPlanKaydet(){
  var ids = Object.keys(idzDegisen);
  if(!ids.length){ rkBilgi('Değişiklik yok. Masayı sürükleyip konumunu değiştirebilirsiniz.', 'info'); return; }
  var b = document.getElementById('idzKaydet'); b.disabled = true;
  fetch('/isletme-duzeni/plan-kaydet', {
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.csrf||''},
    body: JSON.stringify({konumlar: idzDegisen})
  }).then(function(r){ return r.json(); }).then(function(d){
    b.disabled = false;
    if(d.ok != 1){ rkBilgi(d.hata || 'Plan kaydedilemedi.', 'er'); return; }
    IDZ.masalar.forEach(function(m){ if(idzDegisen[m.id]) IDZ.plan[m.id] = {b: m.b, x: idzDegisen[m.id].x, y: idzDegisen[m.id].y}; });
    idzDegisen = {}; idzCiz(); rkBilgi('Masa planı kaydedildi.', 'ok');
  }).catch(function(){ b.disabled = false; rkBilgi('Sunucuya ulaşılamadı.', 'er'); });
}
window.addEventListener('beforeunload', function(e){ if(Object.keys(idzDegisen).length){ e.preventDefault(); e.returnValue = ''; } });
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
(function(){
  var inp = document.getElementById('idzGenelAra'), kutu = document.getElementById('idzAraSonuc'), hedef = [];
  if(!inp) return;
  inp.addEventListener('input', function(){
    var q = inp.value.trim().toLocaleLowerCase('tr'); hedef = [];
    if(q){
      function esle(ad){ return String(ad).toLocaleLowerCase('tr').indexOf(q) >= 0; }
      IDZ.masalar.filter(function(m){ return esle(m.ad); }).slice(0, 6).forEach(function(m){ var al = IDZ.alanlar.find(function(a){ return a.id === m.b; }); hedef.push(['table_restaurant', m.ad, al ? al.ad : 'Bölgesiz', function(){ idzSekme('plan'); idzVurgu = m.id; idzAlanSec(m.b, true); idzVurgu = m.id; idzCiz(); }]); });
      IDZ.garsonlar.filter(function(g){ return esle(g.ad); }).slice(0, 4).forEach(function(g){ hedef.push(['groups', g.ad, 'Garson', function(){ idzSekme('garson'); garsonAtaAc(g.id); }]); });
      IDZ.alanlar.filter(function(a){ return esle(a.ad); }).slice(0, 4).forEach(function(a){ hedef.push(['person_pin_circle', a.ad, 'Bölge', function(){ idzSekme('plan'); idzAlanSec(a.id, true); }]); });
      IDZ.kasalar.filter(function(k){ return esle(k.ad); }).slice(0, 4).forEach(function(k){ hedef.push(['point_of_sale', k.ad, 'Kasa', function(){ idzSekme('kasa'); }]); });
    }
    kutu.innerHTML = hedef.length ? hedef.map(function(h, i){ return '<button type="button" data-i="'+i+'"><span class="material-icons-outlined">'+h[0]+'</span><b>'+mduzEsc(h[1])+'</b><small>'+mduzEsc(h[2])+'</small></button>'; }).join('') : (q ? '<div class="yok">Sonuç bulunamadı</div>' : '');
    kutu.classList.toggle('acik', !!q);
  });
  kutu.addEventListener('click', function(e){ var b = e.target.closest('button'); if(!b) return; hedef[+b.getAttribute('data-i')][3](); kutu.classList.remove('acik'); inp.value = ''; });
  document.addEventListener('pointerdown', function(e){ if(!e.target.closest('.idz-ust-ara')) kutu.classList.remove('acik'); });
})();
// ---- Saat + ilk çizim ----
setInterval(function(){ var s = document.getElementById('idzSaat'), d = new Date(); if(s) s.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0'); }, 20000);
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
@push('head')<style>
.rk-body{background:radial-gradient(1000px 420px at 88% -8%,color-mix(in srgb,var(--mor1) 13%,transparent),transparent 70%),var(--bg)}
.rk-main{min-width:0}
.idz{--idz-kart:var(--card);--idz-mor-a:color-mix(in srgb,var(--mor1) 12%,transparent);--idz-golge:0 6px 22px rgba(76,52,160,.08)}
.idz-mor{color:var(--mor1)}
.idz-esnek{flex:1}
.idz-panel{display:none}
.idz-panel.aktif{display:block}
/* Üst şerit */
.idz-ara{position:relative;display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:0 14px;height:40px}
.idz-ara>.material-icons-outlined{font-size:20px;color:var(--sub2)}
.idz-ara input{border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-size:13px;width:100%;min-width:0}
.idz-ust-ara{width:clamp(180px,24vw,420px);margin-right:10px;background:color-mix(in srgb,var(--mor1) 7%,var(--card))}
.idz-ara-sonuc{display:none;position:absolute;top:46px;left:0;right:0;z-index:60;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 16px 40px rgba(30,20,80,.2);padding:6px;max-height:340px;overflow:auto}
.idz-ara-sonuc.acik{display:block}
.idz-ara-sonuc button{display:flex;align-items:center;gap:9px;width:100%;padding:9px 10px;border:0;background:transparent;border-radius:10px;cursor:pointer;color:var(--ink);font:inherit;font-size:13px;text-align:left}
.idz-ara-sonuc button:hover{background:var(--idz-mor-a,rgba(124,58,237,.1))}
.idz-ara-sonuc button .material-icons-outlined{font-size:18px;color:var(--mor1)}
.idz-ara-sonuc small{margin-left:auto;color:var(--sub);font-size:11.5px}
.idz-ara-sonuc .yok{padding:12px;color:var(--sub);font-size:13px;text-align:center}
.idz-ust-ikon{position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:color-mix(in srgb,var(--mor1) 7%,var(--card));color:var(--ink);text-decoration:none;margin-right:8px}
.idz-ust-ikon b{position:absolute;top:-3px;right:-3px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#EF4444;color:#fff;font-size:10.5px;line-height:18px;text-align:center}
.idz-tarih{display:flex;align-items:center;gap:8px;height:40px;padding:0 14px;border-radius:999px;background:color-mix(in srgb,var(--mor1) 7%,var(--card));color:var(--ink);font-size:12.5px;font-weight:600;white-space:nowrap;margin-right:8px}
.idz-tarih .material-icons-outlined{font-size:19px;color:var(--mor1)}
.idz-tarih b{margin-left:6px}
/* Sekmeler */
.idz-sekmeler{display:flex;gap:10px;overflow-x:auto;padding:2px 2px 12px;scrollbar-width:none}
.idz-sekmeler::-webkit-scrollbar{display:none}
.idz-sekme{flex:0 0 auto;display:inline-flex;align-items:center;gap:12px;height:46px;padding:0 24px;border:1px solid var(--line);border-radius:15px;background:var(--card);color:var(--ink);font:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:transform .12s,box-shadow .15s}
.idz-sekme .material-icons-outlined{font-size:23px;color:var(--mor1)}
.idz-sekme:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(76,52,160,.12)}
.idz-sekme.aktif{background:linear-gradient(135deg,#8B5CF6,#7C3AED);border-color:transparent;color:#fff;box-shadow:0 8px 20px rgba(124,58,237,.33)}
.idz-sekme.aktif .material-icons-outlined{color:#fff}
/* Özet */
.idz-ozet{display:grid;grid-template-columns:repeat(2,minmax(190px,1fr)) minmax(190px,1fr) minmax(220px,1.2fr) minmax(320px,1.95fr);gap:12px;margin-bottom:14px}
.idz-stat{display:flex;align-items:center;gap:14px;min-height:78px;padding:12px 16px;background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--idz-golge)}
.idz-stat-ikon{flex:none;display:flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;background:color-mix(in srgb,var(--r) 13%,transparent);color:var(--r)}
.idz-stat-ikon .material-icons-outlined{font-size:30px}
.idz-stat .e{font-size:12.5px;font-weight:600;color:var(--sub2)}
.idz-stat .d{font-size:24px;line-height:1.25;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.idz-trend{margin-left:auto;align-self:flex-end;display:inline-flex;align-items:center;gap:2px;padding:4px 11px;border-radius:999px;background:color-mix(in srgb,#10B981 14%,transparent);color:#059669;font-size:12.5px;font-weight:700}
.idz-trend .material-icons-outlined{font-size:14px}
.idz-afis{position:relative;overflow:hidden;display:flex;align-items:center;gap:14px;min-height:78px;padding:12px 22px;border-radius:18px;color:#fff;font-size:14.5px;font-weight:700;line-height:1.45;background:linear-gradient(90deg,#6D28D9,#7C3AED);box-shadow:var(--idz-golge)}
.idz-afis:before{content:"";position:absolute;top:0;right:0;bottom:0;width:min(52%,340px);background:url('/img/isletme-duzeni-banner.jpg') center/cover no-repeat;-webkit-mask-image:linear-gradient(90deg,transparent,#000 62%);mask-image:linear-gradient(90deg,transparent,#000 62%)}
.idz-afis>*{position:relative}
.idz-afis>.material-icons-outlined{font-size:38px}
/* Plan kartı */
.idz-plan-kart,.idz-kart{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--idz-golge)}
.idz-plan-kart{padding:12px;margin-bottom:14px}
.idz-kart{padding:14px 16px;margin-bottom:12px}
.idz-plan-bas{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:2px 4px 12px}
.idz-plan-bas h2{margin:0 0 0 4px;font-size:17px;font-weight:800;color:var(--ink)}
.idz-sec-kutu{display:flex;align-items:center;gap:6px;height:38px;padding:0 10px 0 12px;border:1px solid var(--line);border-radius:999px;background:var(--card)}
.idz-sec-kutu .material-icons-outlined{font-size:19px;color:var(--sub2)}
.idz-sec-kutu select{border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-size:13px;font-weight:600;min-width:76px;max-width:150px}
.idz-sec-kutu select option{color:#1E293B}
.idz-masa-ara{width:clamp(170px,20vw,290px);height:38px}
.idz-btn{display:inline-flex;align-items:center;gap:7px;min-height:38px;padding:0 16px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap;transition:transform .12s,box-shadow .15s,background .12s}
.idz-btn .material-icons-outlined{font-size:18px;color:var(--mor1)}
.idz-btn:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(76,52,160,.13)}
.idz-btn.dolu{background:linear-gradient(135deg,#8B5CF6,#7C3AED);border-color:transparent;color:#fff}
.idz-btn.dolu .material-icons-outlined{color:#fff}
.idz-btn.ikon{padding:0 9px}
.idz-btn.kucuk{min-height:32px;padding:0 11px;font-size:12.5px;font-weight:600}
.idz-btn.kucuk .material-icons-outlined{font-size:15px}
.idz-btn.tehlike{color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 30%,var(--line))}
.idz-btn.tehlike .material-icons-outlined{color:var(--kirmizi)}
.idz-btn.bekliyor{box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 30%,transparent);animation:idzNabiz 1.6s ease-in-out infinite}
.idz-btn:disabled{opacity:.6;cursor:default}
@keyframes idzNabiz{50%{box-shadow:0 0 0 6px color-mix(in srgb,var(--mor1) 14%,transparent)}}
.idz-plan-govde{display:grid;grid-template-columns:142px minmax(0,1fr) 332px;gap:12px;align-items:start}
.idz-alanlar{display:flex;flex-direction:column;gap:8px;padding:12px 10px;border:1px solid var(--line);border-radius:16px;align-self:stretch}
.idz-alan{display:flex;align-items:center;justify-content:space-between;gap:6px;min-height:46px;padding:0 10px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer;text-align:left}
.idz-alan .ad{display:flex;align-items:center;gap:7px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-alan .ad .material-icons-outlined{display:none;font-size:22px;color:var(--mor1)}
.idz-alan b{flex:none;font-size:12.5px}
.idz-alan.aktif{background:color-mix(in srgb,var(--mor1) 12%,var(--card));border-color:color-mix(in srgb,var(--mor1) 28%,var(--line))}
.idz-alan.aktif .ad .material-icons-outlined{display:inline}
.idz-alan.aktif b{min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:50%;background:color-mix(in srgb,var(--mor1) 22%,transparent);color:var(--mor1)}
.idz-alan.yeni{justify-content:flex-start;gap:4px;margin-top:6px;padding:0 7px;font-size:11.5px;white-space:nowrap}
.idz-alan.yeni .material-icons-outlined{font-size:18px}
/* Kroki */
.idz-cerceve{position:relative;min-width:0;overflow:auto;border-radius:16px;border:9px solid #EADCC8;box-shadow:inset 0 0 0 2px #fff,inset 0 0 26px rgba(120,84,48,.28),0 4px 14px rgba(76,52,160,.13);background:#F3ECE2}
.idz-dunya{--ts:110px;position:relative;min-height:100%;background-color:#F6F0E7;background-image:
  linear-gradient(rgba(150,122,92,.16) 1px,transparent 1px),
  linear-gradient(90deg,rgba(150,122,92,.16) 1px,transparent 1px),
  url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='600'%3E%3Cfilter id='m' x='0' y='0' width='100%25' height='100%25'%3E%3CfeTurbulence type='turbulence' baseFrequency='.005 .011' numOctaves='5' seed='11' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .66 0 0 0 0 .58 0 0 0 0 .49 -3.4 0 0 0 .62'/%3E%3C/filter%3E%3Crect width='600' height='600' filter='url(%23m)'/%3E%3C/svg%3E");
  background-size:12.5% 25%,12.5% 25%,600px 600px}
body.koyu .idz-dunya{filter:brightness(.86)}
.idz-bitki{position:absolute;height:auto;pointer-events:none;filter:drop-shadow(0 7px 5px rgba(60,40,20,.34))}
.idz-isik{position:absolute;width:26px;height:26px;border-radius:50%;pointer-events:none;background:radial-gradient(circle,#fff 0,#FFE9B8 30%,rgba(255,226,160,0) 72%);box-shadow:0 0 40px 20px rgba(255,222,150,.62)}
.idz-resepsiyon{position:absolute;left:50%;bottom:0;width:20%;min-width:130px;transform:translateX(-50%);pointer-events:none;filter:drop-shadow(0 7px 7px rgba(60,40,20,.38))}
.idz-resepsiyon svg{display:block;width:100%}
.idz-resepsiyon span{position:absolute;left:0;right:0;top:58%;text-align:center;color:#FFF3DF;font-size:clamp(11px,1vw,15px);font-weight:800;letter-spacing:.2px}
.idz-masa{position:absolute;display:block;padding:0;border:0;background:transparent;transform:translate(-50%,-50%);cursor:grab;touch-action:none;user-select:none;color:#2B1F3F;transition:opacity .15s,filter .15s}
.idz-masa svg{display:block;width:100%;height:auto;overflow:visible;filter:drop-shadow(0 8px 7px rgba(70,44,20,.36))}
.idz-masa:hover svg{filter:drop-shadow(0 11px 10px rgba(70,44,20,.46))}
.idz-masa:focus-visible{outline:3px solid var(--mor1);outline-offset:3px;border-radius:12px}
.idz-masa.sonuk{opacity:.24}
.idz-masa.tutuldu{cursor:grabbing;z-index:4}
.idz-masa.vurgu svg{filter:drop-shadow(0 0 13px #7C3AED)}
.idz-masa .et{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:2px;pointer-events:none;max-width:calc(var(--ts) * .5)}
.idz-masa .et b{font-size:calc(var(--ts) * .2);line-height:1.05;font-weight:800;color:#1E1633;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-masa .et em{display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:999px;background:rgba(255,255,255,.92);font-style:normal;font-weight:700;font-size:calc(var(--ts) * .095);color:#2B1F3F;white-space:nowrap}
.idz-masa .et em i{width:7px;height:7px;border-radius:50%}
.idz-plan-bos{position:absolute;left:50%;top:40%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:6px;padding:20px 26px;border-radius:16px;background:rgba(255,255,255,.92);color:#334155;font-size:13px;text-align:center;box-shadow:0 8px 26px rgba(60,40,20,.2)}
.idz-plan-bos b{font-size:15px;color:#1E1633}
.idz-plan-bos .idz-btn{margin-top:6px}
.idz-zoom{position:sticky;float:left;left:10px;bottom:10px;margin:-146px 0 0 10px;z-index:5;display:flex;flex-direction:column;width:40px;background:rgba(255,255,255,.95);border-radius:12px;box-shadow:0 5px 16px rgba(60,40,20,.22);overflow:hidden}
.idz-zoom button{height:44px;border:0;background:transparent;color:#334155;cursor:pointer}
.idz-zoom button:hover{background:#F1EDFD;color:#7C3AED}
.idz-zoom .material-icons-outlined{font-size:21px;display:block;margin:auto}
/* Sağ sütun */
.idz-sag{display:flex;flex-direction:column;gap:12px;align-self:stretch}
.idz-sag .idz-kart{margin:0}
.idz-sag .idz-kart:last-child{flex:1;display:flex;flex-direction:column}
.idz-sag .idz-kart:last-child .idz-hizli{flex:1;grid-auto-rows:1fr}
.idz-kart-bas{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.idz-kart-bas h3{margin:0;font-size:15px;font-weight:800;color:var(--ink)}
.idz-kart-bas>.material-icons-outlined{font-size:22px}
.idz-link{display:inline-flex;align-items:center;gap:4px;margin-left:auto;padding:0;border:0;background:transparent;color:var(--mor1);font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap}
.idz-link .material-icons-outlined{font-size:17px}
.idz-gsatir{display:flex;align-items:center;gap:9px;width:100%;padding:7px 2px;border:0;background:transparent;color:var(--ink);font:inherit;cursor:pointer;text-align:left;border-radius:10px}
.idz-gsatir:hover{background:var(--idz-mor-a)}
.idz-gsatir .ad{flex:1;min-width:0;font-size:12.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-avatar{flex:none;display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,#A78BFA,#7C3AED);color:#fff;font-size:13px;font-weight:800}
.idz-chip{flex:none;max-width:112px;padding:4px 10px;border-radius:999px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);font-size:11.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-aktif{flex:none;display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;background:color-mix(in srgb,#10B981 13%,transparent);color:#059669;font-size:11px;font-weight:700}
.idz-aktif i{width:7px;height:7px;border-radius:50%;background:#10B981}
.idz-hizli{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}
.idz-hizli button{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:62px;padding:6px 2px;border:1px solid var(--line);border-radius:13px;background:var(--card);color:var(--ink);font:inherit;font-size:11.5px;font-weight:600;cursor:pointer;transition:transform .12s,box-shadow .15s,border-color .12s}
.idz-hizli button:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--mor1) 42%,var(--line));box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-hizli .material-icons-outlined{font-size:24px;color:var(--mor1)}
/* Alt kartlar */
.idz-alt{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(320px,1fr);gap:12px}
.idz-bkartlar{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
.idz-bkart{position:relative;display:grid;grid-template-columns:48px minmax(0,1fr);gap:4px 10px;padding:12px;border:1px solid var(--line);border-radius:14px;background:color-mix(in srgb,var(--r) 4%,var(--card));color:var(--ink);font:inherit;cursor:pointer;text-align:left;transition:transform .12s,box-shadow .15s}
.idz-bkart:hover{transform:translateY(-2px);box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-bkart .ic{display:flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:50%;background:color-mix(in srgb,var(--r) 14%,transparent);color:var(--r)}
.idz-bkart .ic .material-icons-outlined{font-size:27px}
.idz-bkart .me{display:flex;flex-direction:column;gap:3px;min-width:0;font-size:12.5px;color:var(--sub2)}
.idz-bkart .me b{font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-bkart .cubuk{grid-column:1/-1;height:9px;margin-top:8px;border-radius:99px;background:color-mix(in srgb,var(--r) 12%,transparent);overflow:hidden}
.idz-bkart .cubuk i{display:block;height:100%;border-radius:99px;background:var(--r);min-width:0}
.idz-hareket>div:not(.idz-bos){position:relative;display:flex;align-items:center;gap:14px;padding:6px 2px;font-size:12.5px;font-weight:600;color:var(--ink)}
.idz-hareket i{flex:none;width:9px;height:9px;border-radius:50%;background:var(--mor1)}
.idz-hareket time{flex:none;color:var(--sub2);font-variant-numeric:tabular-nums;font-weight:700}
.idz-hareket span{min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-bos{padding:18px 10px;color:var(--sub);font-size:13px;text-align:center}
.idz-bos a{color:var(--mor1);font-weight:700}
/* Sekme içerikleri */
.idz-acik{margin:-2px 0 12px;font-size:12.5px;color:var(--sub2);line-height:1.5}
.idz-rozet{padding:2px 10px;border-radius:999px;background:var(--idz-mor-a);color:var(--mor1);font-size:12px;font-weight:700;white-space:nowrap}
.idz-form-satir{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
.idz-bolge-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:12px}
.idz-kasa-bas{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.idz-kbaslik{margin-bottom:6px;font-size:11.5px;font-weight:700;color:var(--sub2)}
.idz-kasa form[action="/kasalar/sayfalar"] .rk-btn:not(.bos){background:var(--mor1)!important}
.idz-rol-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}
.idz-rol{padding:12px 14px;border:1px solid var(--line);border-radius:14px;background:var(--card2)}
.idz-rol .b{display:flex;justify-content:space-between;font-size:13.5px;font-weight:700;color:var(--ink)}
.idz-rol .b b{color:var(--mor1)}
.idz-rol .k{margin-top:4px;font-size:12px;color:var(--sub2);line-height:1.45}
.idz-ayar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:10px}
.idz-ayar{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:var(--card);text-decoration:none;color:var(--ink);transition:transform .12s,box-shadow .15s}
.idz-ayar:hover{transform:translateY(-2px);box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-ayar>.material-icons-outlined{font-size:26px;color:var(--mor1)}
.idz-ayar b{display:block;font-size:13.5px}
.idz-ayar small{display:block;margin-top:1px;font-size:11.5px;color:var(--sub)}
.gata-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:8px}
.gata-row{display:flex;align-items:center;gap:11px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.gata-bilgi{min-width:0;flex:1}
.gata-ad{font-weight:800;color:var(--ink);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-rol{font-weight:500;font-size:12px;color:var(--sub2)}
.gata-ozet{font-size:11.5px;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-ata-btn{flex-shrink:0;min-height:34px;padding:0 11px;font-size:12.5px}
.gata-modal-box{max-width:520px;width:100%;max-height:85vh;display:flex;flex-direction:column;padding:20px}
.gata-modal-head{display:flex;align-items:center;gap:8px}
#gataModalBody{overflow-y:auto;flex:1;margin:0 -4px;padding:0 4px}
/* Masa kartı */
.idz-pop{display:none;position:fixed;z-index:70;width:290px;padding:13px;background:var(--card);border:1px solid var(--line);border-radius:15px;box-shadow:0 16px 40px rgba(30,20,80,.27)}
.idz-pop.acik{display:block}
.idz-pop .b{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:5px}
.idz-pop .b b{font-size:15px;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-pop .d{flex:none;display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;background:color-mix(in srgb,var(--r) 13%,transparent);color:var(--r);font-size:11.5px;font-weight:700}
.idz-pop .d i{width:7px;height:7px;border-radius:50%;background:var(--r)}
.idz-pop .s{font-size:12px;color:var(--sub2);line-height:1.5}
.idz-pop .a{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:10px}
.idz-pop .a button{display:flex;flex-direction:column;align-items:center;gap:3px;padding:7px 0;border:1px solid var(--line);border-radius:10px;background:var(--card);color:var(--ink);font:inherit;font-size:11px;font-weight:600;cursor:pointer}
.idz-pop .a button:hover{background:var(--card2)}
.idz-pop .a .material-icons-outlined{font-size:19px;color:var(--mor1)}
.idz-pop .a .k,.idz-pop .a .k .material-icons-outlined{color:var(--kirmizi)}
/* Modallar */
.idz-modal{display:none;position:fixed;inset:0;z-index:110;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}
.idz-modal.acik{display:flex}
.idz-modal-kutu{width:100%;max-width:420px;padding:20px}
.idz-modal-bas{display:flex;align-items:center;gap:8px;margin-bottom:14px}
.idz-modal-bas b{flex:1;font-size:16px;color:var(--ink)}
.idz-modal-alt{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
.mduz-ibtn{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;border-radius:8px;border:none;background:transparent;color:var(--sub);cursor:pointer;transition:background .12s}
.mduz-ibtn:hover{background:color-mix(in srgb,currentColor 15%,transparent)}
.mduz-ibtn .material-icons-outlined{font-size:19px}
.mduz-qr-box{background:var(--card);border-radius:22px;max-width:360px;width:100%;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.4);animation:mduzPop .18s ease}
@keyframes mduzPop{from{transform:scale(.94);opacity:0}to{transform:scale(1);opacity:1}}
.mduz-qr-head{display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;padding:14px 18px}
.mduz-qr-masa{font-size:19px;font-weight:800;letter-spacing:.2px}
.mduz-qr-body{padding:20px 22px 4px;text-align:center}
.mduz-qr-sube{font-size:16px;font-weight:800;color:var(--ink)}
.mduz-qr-ipucu{font-size:12px;color:var(--sub);margin-top:2px}
.mduz-qr-frame{display:inline-flex;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:14px;margin:14px auto 0;box-shadow:0 6px 20px rgba(0,0,0,.1)}
.mduz-qr-ozellik{display:flex;justify-content:center;gap:14px;margin-top:16px;flex-wrap:wrap}
.mduz-qr-ozellik span{display:inline-flex;flex-direction:column;align-items:center;gap:3px;font-size:10.5px;font-weight:600;color:var(--sub)}
.mduz-qr-ozellik .material-icons-outlined{font-size:20px;color:var(--mor1)}
.mduz-qr-foot{display:flex;gap:10px;padding:16px 22px 22px}
/* Duyarlı */
@media(max-width:1500px){.idz-ust-ara{width:190px}.idz-ozet{grid-template-columns:repeat(4,minmax(0,1fr))}.idz-afis{grid-column:1/-1}}
@media(max-width:1280px){.idz-plan-govde{grid-template-columns:132px minmax(0,1fr)}.idz-sag{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:12px}.idz-sag .idz-kart{margin:0}.idz-alt{grid-template-columns:1fr}.idz-tarih span:not(.material-icons-outlined){display:none}.idz-tarih b{margin-left:0}}
@media(max-width:860px){
  .idz-ust-ara,.idz-tarih,.rk-header .alt{display:none}
  .rk-header .ikon,.idz-ust-ikon{flex:none}
  .idz-ozet{grid-template-columns:repeat(2,minmax(0,1fr))}
  .idz-plan-govde{grid-template-columns:minmax(0,1fr)}
  .idz-alanlar{flex-direction:row;overflow-x:auto;padding:8px}
  .idz-alan{flex:0 0 auto;min-height:40px}
  .idz-alan.yeni{margin-top:0}
  .idz-sag{grid-template-columns:minmax(0,1fr)}
  .idz-esnek{flex-basis:100%;height:0}
  .idz-masa-ara{flex:1 1 100%;width:auto}
  .idz-plan-bas .idz-btn{flex:1;justify-content:center}
}
@media(max-width:480px){
  .idz-stat{gap:9px;padding:10px;min-height:66px}
  .idz-stat-ikon{width:40px;height:40px}
  .idz-stat-ikon .material-icons-outlined{font-size:23px}
  .idz-stat .d{font-size:20px}
  .idz-trend{display:none}
  .idz-sekme{height:42px;padding:0 15px;font-size:13px;gap:8px}
  .idz-bkartlar,.gata-list{grid-template-columns:minmax(0,1fr)}
  .gata-row{flex-wrap:wrap}
}
@media(prefers-reduced-motion:reduce){.idz-btn.bekliyor{animation:none}.idz *{transition:none!important}}
</style>@endpush

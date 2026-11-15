@php
  // Kategori sayfası: sol menüde "Raporlar & Analizler" altındaki her kalem buraya {kat} ile gelir
  [$katAd, $katIk, $katAcik, $katRenk] = $kategoriler[$kat];
@endphp
@extends('layout.app')
@section('title', $katAd . ' · Raporlar & Analizler')
@section('rk_ikon', $katIk)
@section('baslik', $katAd)
@section('rk_alt', 'Satın alma · Raporlar & Analizler')

@section('content')
@php
  $durum = $durum ?? []; $ai = $ai ?? null;
  $modAd = ['fatura' => 'Alış faturası', 'siparis' => 'Sipariş', 'malkabul' => 'Mal kabul', 'malkabul_sip' => 'Siparişe bağlı mal kabul', 'talep' => 'Satın alma talebi',
    'rfq' => 'Teklif (RFQ)', 'recete' => 'Reçete', 'parti' => 'Lot / SKT', 'fire' => 'Fire / iade', 'tuketim' => 'Reçeteli tüketim', 'sayim' => 'Sayım', 'cari' => 'Tedarikçi cari', 'butce' => 'Bütçe', 'sube' => 'Birden çok şube'];
  // Kullanılmayan modülün nereden girileceği (gri çip tıklanınca o ekran açılır)
  $modLink = ['fatura' => ['/mal-kabul', 'Mal Kabul / İrsaliye'], 'siparis' => ['/satinalma-siparis', 'Sipariş İşlemleri'], 'malkabul' => ['/mal-kabul', 'Mal Kabul / İrsaliye'],
    'malkabul_sip' => ['/mal-kabul', 'Mal Kabul\'de "Teslim bekleyen siparişler"den seçerek'], 'talep' => ['/satinalma-talep', 'Satın Alma Talepleri'], 'rfq' => ['/satinalma-talep', 'Talepten teklif isteyerek'],
    'recete' => ['/recete', 'Reçeteler'], 'parti' => ['/mal-kabul', 'Mal Kabul\'de SKT girerek'], 'fire' => ['/stok', 'Stok > fire / iade'], 'tuketim' => ['/recete', 'Reçeteli satış'],
    'sayim' => ['/sayim', 'Sayım (Envanter)'], 'cari' => ['/tedarikci', 'Tedarikçi carisi'], 'butce' => ['/satinalma-butce', 'Satınalma Bütçe'], 'sube' => [null, 'Tek şube']];
  $donemler = ['bugun' => 'Bugün', 'dun' => 'Dün', 'bu_hafta' => 'Bu hafta', 'gecen_hafta' => 'Geçen hafta', 'bu_ay' => 'Bu ay', 'gecen_ay' => 'Geçen ay', 'son30' => 'Son 30 gün', 'son90' => 'Son 90 gün', 'bu_yil' => 'Bu yıl'];
  $donemQs = http_build_query(array_filter(['donem' => $dn['tip'], 'bas' => $dn['tip'] === 'ozel' ? $dn['bas'] : null, 'bit' => $dn['tip'] === 'ozel' ? $dn['bit'] : null]));
  $periyot = ['gunluk' => ['dun' => 'Dün', 'bugun' => 'Bugün'], 'haftalik' => ['gecen_hafta' => 'Geçen hafta', 'bu_hafta' => 'Bu hafta'], 'aylik' => ['gecen_ay' => 'Geçen ay', 'bu_ay' => 'Bu ay']];
  $radarGunler = [30, 60, 90, 180, 365];
  $radarGun = in_array((int) request('gun'), $radarGunler, true) ? (int) request('gun') : 90;
  $veriRozet = function ($gerekli) use ($durum, $modAd) {
      $eksik = array_values(array_filter($gerekli, fn($m) => empty($durum[$m])));
      if (!$eksik) return ['Veri hazır', '#059669', 'check_circle', ''];
      $ad = implode(', ', array_map(fn($m) => $modAd[$m] ?? $m, $eksik));
      if (in_array('fatura', $eksik, true) || count($eksik) === count($gerekli)) return ['Veri yok', '#DC2626', 'error', $ad];
      return ['Kısmi veri', '#D97706', 'warning', $ad];
  };
  // Bu kategorinin raporları (üst sekmeler + görüntüleyici)
  $liste = [];
  if ($kat === 'radar') {
      $hazir = !empty($durum['fatura']);
      foreach ($radar as $rk => [$ad, $acik, $ik, $renk])
          $liste[] = ['k' => $rk, 'ad' => $ad, 'acik' => $acik, 'ik' => $ik, 'renk' => $renk, 'roz' => $hazir ? ['Veri hazır', '#059669', 'check_circle', ''] : ['Veri yok', '#DC2626', 'error', 'Alış faturası'],
              'per' => null, 'url' => '/fiyat-radari/indir?rapor=' . $rk . '&gun=' . $radarGun];
      $gerekliTum = ['fatura'];
  } elseif ($kat !== 'ai') {
      $gerekliTum = [];
      foreach ($katalog as $rk => [$k, $ad, $acik, $ik, $renk, $gerekli, $vars]) {
          if ($k !== $kat) continue;
          $gerekliTum = array_merge($gerekliTum, $gerekli);
          $liste[] = ['k' => $rk, 'ad' => $ad, 'acik' => $acik, 'ik' => $ik, 'renk' => $renk, 'roz' => $veriRozet($gerekli), 'per' => $periyot[$rk] ?? null,
              'url' => '/satinalma-raporlar/indir?rapor=' . $rk];
      }
  } else $gerekliTum = ['fatura', 'siparis', 'malkabul_sip', 'talep', 'butce', 'fire', 'recete'];
  $gerekliTum = array_values(array_unique($gerekliTum));
  $sayfaDonemli = !in_array($kat, ['donemsel', 'radar'], true);
@endphp

<style>
  .sr{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;--kr:{{ $katRenk }}}
  .sr [hidden]{display:none!important}
  /* ---- rapor sekmeleri (üstte) ---- */
  .sr-sek{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
  @media(max-width:700px){.sr-sek{grid-auto-flow:column;grid-template-columns:none;grid-auto-columns:78%;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:4px}.sr-sek .sr-r{scroll-snap-align:start}}
  .sr-r{position:relative;display:flex;gap:11px;align-items:flex-start;text-align:left;width:100%;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:13px 14px;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .15s;font:inherit;color:inherit;overflow:hidden}
  .sr-r::after{content:'';position:absolute;left:0;right:0;bottom:0;height:3px;background:var(--c);transform:scaleX(0);transform-origin:left;transition:transform .2s}
  .sr-r:hover{border-color:color-mix(in srgb,var(--c) 45%,transparent);transform:translateY(-2px);box-shadow:0 12px 26px -18px var(--c)}
  .sr-r.aktif{border-color:var(--c);background:linear-gradient(160deg,color-mix(in srgb,var(--c) 9%,var(--card)),var(--card) 70%);box-shadow:0 14px 30px -18px var(--c)}
  .sr-r.aktif::after{transform:scaleX(1)}
  .sr-r .ik{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;flex:0 0 auto;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent);transition:background .15s,color .15s}
  .sr-r.aktif .ik{color:#fff;background:linear-gradient(135deg,color-mix(in srgb,var(--c) 75%,#fff),var(--c))}
  .sr-r .ik .material-icons-outlined{font-size:21px}
  .sr-r .t{font-weight:800;font-size:13.5px;color:var(--ink);line-height:1.25;display:flex;align-items:center;gap:6px}
  .sr-r .d{font-size:11.5px;color:var(--sub);line-height:1.4;margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .sr-nok{width:8px;height:8px;border-radius:50%;background:var(--n);flex:0 0 auto;box-shadow:0 0 0 3px color-mix(in srgb,var(--n) 18%,transparent)}
  /* ---- görüntüleyici ---- */
  .sr-gor{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--golge);overflow:hidden;display:flex;flex-direction:column;min-width:0}
  .sr-gor-bas{position:relative;display:flex;gap:14px;align-items:center;padding:16px 18px;flex-wrap:wrap}
  .sr-gor-bas::before{content:'';position:absolute;inset:0;background:radial-gradient(70% 160% at 0% 0%,color-mix(in srgb,var(--c,var(--kr)) 13%,transparent),transparent 65%);pointer-events:none}
  .sr-gor-bas > *{position:relative}
  .sr-gor-bas .ik{width:46px;height:46px;border-radius:13px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,color-mix(in srgb,var(--c) 72%,#fff),var(--c));box-shadow:0 10px 22px -12px var(--c);flex:0 0 auto}
  .sr-gor-bas .ik .material-icons-outlined{font-size:24px}
  .sr-gor-bas h3{margin:0;font-size:17px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .sr-gor-bas .d{font-size:12.5px;color:var(--sub2);margin-top:3px;max-width:760px;line-height:1.45}
  .sr-roz{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;color:var(--c);background:color-mix(in srgb,var(--c) 10%,transparent);border:1px solid color-mix(in srgb,var(--c) 30%,transparent);border-radius:99px;padding:2px 8px}
  .sr-roz .material-icons-outlined{font-size:13px}
  .sr-eksik{font-size:11.5px;color:#B45309;margin-top:4px}
  body.koyu .sr-eksik{color:#FBBF24}
  .sr-arac{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto}
  .sr-per{display:inline-flex;background:var(--card2);border:1px solid var(--line);border-radius:11px;padding:3px}
  .sr-per button{border:0;background:none;color:var(--sub2);border-radius:8px;padding:7px 12px;font-size:12.5px;font-weight:800;cursor:pointer}
  .sr-per button.aktif{background:var(--c);color:#fff;box-shadow:0 4px 10px -4px var(--c)}
  .sr-b{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px 14px;border-radius:11px;font-size:12.5px;font-weight:800;text-decoration:none;border:1px solid;cursor:pointer;background:none;white-space:nowrap}
  .sr-b .material-icons-outlined{font-size:17px}
  .sr-b.x{color:#fff;border-color:#16A34A;background:linear-gradient(135deg,#22C55E,#15803D)}
  .sr-b.p{color:#fff;border-color:#E11D48;background:linear-gradient(135deg,#F43F5E,#BE123C)}
  .sr-b.o{color:var(--sub2);border-color:var(--line);background:var(--card2)}
  .sr-b.x:hover,.sr-b.p:hover{filter:brightness(1.07)}
  /* ---- araç satırı: dönem + kullanılan veri ---- */
  .sr-alt{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;padding:10px 18px;border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--card2)}
  .sr-alt .grp{display:flex;flex-wrap:wrap;gap:5px;align-items:center}
  .sr-alt .lbl{font-size:10.5px;font-weight:800;color:var(--sub);letter-spacing:.4px;text-transform:uppercase;margin-right:3px}
  .sr-alt .ayr{width:1px;align-self:stretch;background:var(--line)}
  .sr-chip{display:inline-flex;align-items:center;padding:5px 10px;border-radius:99px;font-size:12px;font-weight:700;color:var(--sub2);background:var(--card);border:1px solid var(--line);text-decoration:none;white-space:nowrap}
  .sr-chip:hover{border-color:var(--kr);color:var(--kr)}
  .sr-chip.aktif{background:var(--kr);border-color:var(--kr);color:#fff}
  .sr-ozel{display:inline-flex;gap:4px;align-items:center;padding:2px 3px 2px 9px;border-radius:99px;background:var(--card);border:1px solid var(--line);font-size:12px;font-weight:700;color:var(--sub2)}
  .sr-ozel input{background:var(--card2);border:1px solid var(--line);border-radius:7px;padding:2px 4px;font-size:12px;color:var(--ink)}
  .sr-ozel button{border:0;border-radius:99px;padding:5px 10px;font-weight:800;background:var(--kr);color:#fff;cursor:pointer;font-size:12px}
  .sr-etiket{font-size:11.5px;color:var(--sub)}
  .sr-etiket b{color:var(--ink)}
  .sr-m{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;font-size:11.5px;font-weight:700;border:1px solid;text-decoration:none}
  .sr-m .material-icons-outlined{font-size:14px}
  .sr-m.var{color:#047857;border-color:color-mix(in srgb,#059669 35%,transparent);background:color-mix(in srgb,#059669 8%,transparent)}
  .sr-m.yok{color:var(--sub);border-color:var(--line);background:var(--card);border-style:dashed}
  a.sr-m.yok:hover{color:var(--kr);border-color:var(--kr)}
  body.koyu .sr-m.var{color:#34D399}
  /* ---- önizleme: raporun tam boyu, iç kaydırma yok ---- */
  .sr-cer{position:relative;min-height:520px;background:#F1F2F9}
  .sr-cer iframe{display:block;width:100%;height:900px;border:0;background:transparent}
  .sr-yuk{position:absolute;inset:0;display:grid;place-items:start center;padding-top:120px;color:var(--sub);font-size:13px;font-weight:700;pointer-events:none;transition:opacity .2s;background:color-mix(in srgb,#F1F2F9 70%,transparent)}
  .sr-yuk .material-icons-outlined{animation:srDon 1s linear infinite;font-size:30px;color:var(--kr)}
  @keyframes srDon{to{transform:rotate(360deg)}}
  /* ---- AI ---- */
  .sr-ai{border-radius:18px;padding:18px;background:linear-gradient(135deg,color-mix(in srgb,#9333EA 12%,var(--card)),color-mix(in srgb,#EC4899 8%,var(--card)));border:1px solid color-mix(in srgb,#9333EA 30%,transparent)}
  .sr-ai-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 18px;border-radius:12px;border:0;cursor:pointer;font-weight:900;font-size:13.5px;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899)}
  .sr-ai-btn[disabled]{opacity:.7;cursor:wait}
  .sr-ai-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-top:12px}
  .sr-ai-k{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px;border-top:3px solid var(--c)}
  .sr-ai-k h5{margin:0 0 8px;font-size:13px;font-weight:900;color:var(--ink)}
  .sr-ai-m{padding:8px 0;border-top:1px solid var(--line);font-size:12.5px;color:var(--sub2)}
  .sr-ai-k h5 + .sr-ai-m{border-top:0}
  .sr-ai-m b{display:block;color:var(--ink);margin-bottom:2px}
  .sr-sor{display:flex;gap:8px;margin-top:12px}
  .sr-sor input{flex:1;min-width:0;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:10px 13px;font-size:13px;color:var(--ink)}
  .sr-cevap{white-space:pre-wrap;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:13px;font-size:13px;color:var(--ink);margin-top:10px;line-height:1.55}
</style>

{{-- Dönem / süre seçimi (raporun araç satırında gösterilir) --}}
@php ob_start(); @endphp
@if ($sayfaDonemli)
  <div class="grp"><span class="lbl">Dönem</span>
    @foreach ($donemler as $k => $ad)<a class="sr-chip {{ $dn['tip'] === $k ? 'aktif' : '' }}" href="?donem={{ $k }}" onclick="this.href += location.hash">{{ $ad }}</a>@endforeach
    <form class="sr-ozel" method="GET" onsubmit="this.action = location.pathname + location.hash">
      <input type="hidden" name="donem" value="ozel"><span>Özel</span>
      <input type="date" name="bas" value="{{ $dn['bas'] }}" max="{{ date('Y-m-d') }}"><span>–</span><input type="date" name="bit" value="{{ $dn['bit'] }}" max="{{ date('Y-m-d') }}">
      <button type="submit">Uygula</button>
    </form>
    <span class="sr-etiket"><b>{{ $dn['etiket'] }}</b> · kıyas {{ \Carbon\Carbon::parse($dn['obas'])->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($dn['obit'])->format('d.m.Y') }}</span>
  </div>
@elseif ($kat === 'radar')
  <div class="grp"><span class="lbl">Süre</span>
    @foreach ($radarGunler as $g)<a class="sr-chip {{ $radarGun === $g ? 'aktif' : '' }}" href="?gun={{ $g }}" onclick="this.href += location.hash">Son {{ $g }} gün</a>@endforeach
    <a class="sr-chip" href="/fiyat-radari">Fiyat Radarı sayfası →</a>
  </div>
@endif
@php $donemHtml = ob_get_clean(); @endphp

<div class="sr">
  @if ($kat === 'ai')
    <div class="sr-gor"><div class="sr-alt" style="border:0">{!! $donemHtml !!}</div></div>
    {{-- ===== AI ===== --}}
    <div class="sr-ai">
      <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <div style="flex:1;min-width:260px">
          <div style="font-size:16px;font-weight:900;color:var(--ink)">✨ AI Yönetici Özeti — {{ $dn['ad'] }} ({{ $dn['etiket'] }})</div>
          <div style="font-size:12.5px;color:var(--sub2);margin-top:3px">Seçili dönemin satın alma verisini (harcama, fiyat değişimleri, teslimat, talepler, stok, bütçe, fire, menü etkisi) okuyup üst yönetim için <b>özet, öne çıkanlar, riskler ve öneriler</b> yazar. Bu içerik <b>yapay zekâ tarafından hazırlanır</b>; rapor dosyasında dayandığı rakamlar da yer alır.</div>
        </div>
        <button type="button" class="sr-ai-btn" id="srAiBtn" onclick="srAi(false)"><span class="material-icons-outlined">auto_awesome</span> Özet Hazırla</button>
        <button type="button" class="sr-b o" onclick="srAi(true)"><span class="material-icons-outlined">refresh</span>Yeniden</button>
      </div>
      <div class="sr-sor"><input id="srSoru" placeholder="Soru sor: ör. 'Bu ay en çok hangi tedarikçide harcama arttı?', 'Hangi siparişler gecikiyor?'" onkeydown="if(event.key==='Enter')srSor()">
        <button type="button" class="sr-ai-btn" style="padding:10px 14px" onclick="srSor()"><span class="material-icons-outlined">send</span></button></div>
      <div id="srCevap"></div>
      <div id="srAiSonuc"></div>
      <div id="srAiIndir" style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap" @if (!$ai) hidden @endif>
        <a class="sr-b x" href="/satinalma-raporlar/indir?rapor=ai&format=xlsx&{{ $donemQs }}"><span class="material-icons-outlined">table_view</span>AI özeti Excel</a>
        <a class="sr-b p" target="_blank" href="/satinalma-raporlar/indir?rapor=ai&format=pdf&{{ $donemQs }}"><span class="material-icons-outlined">picture_as_pdf</span>AI özeti PDF</a>
      </div>
    </div>
  @else
    {{-- ===== RAPOR SEKMELERİ ===== --}}
    <div class="sr-sek" id="srListe">
      @foreach ($liste as $r)
        <button type="button" class="sr-r" style="--c:{{ $r['renk'] }}" data-k="{{ $r['k'] }}" onclick="srSec('{{ $r['k'] }}')">
          <span class="ik"><span class="material-icons-outlined">{{ $r['ik'] }}</span></span>
          <span style="min-width:0;flex:1">
            <span class="t">{{ $r['ad'] }}<span class="sr-nok" style="--n:{{ $r['roz'][1] }}" title="{{ $r['roz'][0] }}"></span></span>
            <span class="d">{{ $r['acik'] }}</span>
          </span>
        </button>
      @endforeach
    </div>

    {{-- ===== GÖRÜNTÜLEYİCİ ===== --}}
    <div class="sr-gor" id="srGor">
      <div class="sr-gor-bas">
        <div class="ik" id="gIk"><span class="material-icons-outlined"></span></div>
        <div style="flex:1;min-width:240px">
          <h3><span id="gAd"></span><span class="sr-roz" id="gRoz"><span class="material-icons-outlined"></span><b></b></span></h3>
          <div class="d" id="gAcik"></div>
          <div class="sr-eksik" id="gEksik"></div>
        </div>
        <div class="sr-arac">
          <div class="sr-per" id="gPer" hidden></div>
          <a class="sr-b x" id="gX"><span class="material-icons-outlined">table_view</span>Excel</a>
          <a class="sr-b p" id="gP" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
        </div>
      </div>
      <div class="sr-alt">
        {!! $donemHtml !!}
        @if ($gerekliTum)
          @if (trim($donemHtml) !== '')<span class="ayr"></span>@endif
          <div class="grp"><span class="lbl">Kullanılan veri</span>
            @foreach ($gerekliTum as $m)
              @if (!empty($durum[$m]))
                <span class="sr-m var" title="Son 1 yılda {{ $durum[$m] }} kayıt"><span class="material-icons-outlined">check_circle</span>{{ $modAd[$m] ?? $m }}</span>
              @else
                @php [$mUrl, $mYol] = $modLink[$m] ?? [null, '']; @endphp
                @if ($mUrl)<a class="sr-m yok" href="{{ $mUrl }}" title="Son 1 yılda kayıt yok — girmek için: {{ $mYol }}"><span class="material-icons-outlined">add_circle_outline</span>{{ $modAd[$m] ?? $m }}</a>
                @else<span class="sr-m yok" title="{{ $mYol }}"><span class="material-icons-outlined">radio_button_unchecked</span>{{ $modAd[$m] ?? $m }}</span>@endif
              @endif
            @endforeach
          </div>
        @endif
      </div>
      <div class="sr-cer">
        <div class="sr-yuk" id="gYuk"><div style="text-align:center"><span class="material-icons-outlined">autorenew</span><div>Rapor hazırlanıyor…</div></div></div>
        <iframe id="gFrame" title="Rapor önizleme" scrolling="no" onload="srBoy()"></iframe>
      </div>
    </div>
  @endif
</div>

<script>
  var SR_QS = {!! json_encode($sayfaDonemli ? $donemQs : '') !!};
  var SR_AI_QS = {!! json_encode($donemQs) !!};
  var SR_LISTE = {!! json_encode((object) collect($liste)->keyBy('k')->all(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var SR_AI = {!! json_encode($ai, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var srPerSec = {}; // dönemsel raporlarda rapor başına seçili dönem
  function srEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function srUrl(r, fmt) {
    var qs = r.per ? 'donem=' + (srPerSec[r.k] || Object.keys(r.per)[0]) : SR_QS;
    return r.url + '&format=' + fmt + (qs ? '&' + qs : '');
  }
  // Önizleme raporun tam boyunda: iç kaydırma çubuğu yok, sayfa kayar
  function srBoy() {
    var f = document.getElementById('gFrame'); if (!f || !f.src || f.src === 'about:blank') return;
    try {
      var doc = f.contentDocument; if (!doc || !doc.body) return;
      f.style.height = Math.max(520, Math.ceil(doc.body.getBoundingClientRect().height)) + 'px';
      document.getElementById('gYuk').style.opacity = 0;
    } catch (e) { f.style.height = '1400px'; f.removeAttribute('scrolling'); document.getElementById('gYuk').style.opacity = 0; }
  }
  window.addEventListener('resize', function () { clearTimeout(window.srBoyT); window.srBoyT = setTimeout(srBoy, 150); });
  function srSec(k, ilk) {
    var r = SR_LISTE[k]; if (!r) return;
    document.querySelectorAll('#srListe .sr-r').forEach(function (b) { b.classList.toggle('aktif', b.dataset.k === k); });
    var g = document.getElementById('srGor'); g.style.setProperty('--c', r.renk);
    document.querySelector('#gIk span').textContent = r.ik;
    document.getElementById('gAd').textContent = r.ad;
    document.getElementById('gAcik').textContent = r.acik;
    var roz = document.getElementById('gRoz'); roz.style.setProperty('--c', r.roz[1]);
    roz.querySelector('span').textContent = r.roz[2]; roz.querySelector('b').textContent = r.roz[0];
    document.getElementById('gEksik').textContent = r.roz[3] ? 'Kullanılmayan: ' + r.roz[3] + ' — ilgili bölümler boş gelir, rapor nedenini yazar.' : '';
    var per = document.getElementById('gPer');
    if (r.per) {
      var sec = srPerSec[k] || Object.keys(r.per)[0];
      per.innerHTML = Object.keys(r.per).map(function (p) { return '<button type="button" class="' + (p === sec ? 'aktif' : '') + '" data-p="' + p + '">' + srEsc(r.per[p]) + '</button>'; }).join('');
      per.querySelectorAll('button').forEach(function (b) { b.onclick = function () { srPerSec[k] = b.dataset.p; srSec(k); }; });
      per.hidden = false;
    } else per.hidden = true;
    document.getElementById('gX').href = srUrl(r, 'xlsx');
    document.getElementById('gP').href = srUrl(r, 'pdf');
    document.getElementById('gYuk').style.opacity = 1;
    document.getElementById('gFrame').src = srUrl(r, 'html') + '&onizle=1';
    if (!ilk) { history.replaceState(null, '', '#' + k); if (window.innerWidth <= 1050) g.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  }
  (function () {
    if (!document.getElementById('srListe')) return;
    var h = location.hash.slice(1), ilk = SR_LISTE[h] ? h : Object.keys(SR_LISTE)[0];
    if (ilk) srSec(ilk, true);
  })();

  // ---- AI ----
  function srAiCiz(a) {
    if (!a || !document.getElementById('srAiSonuc')) return;
    var bol = function (anah, bas, renk) {
      var l = a[anah] || []; if (!l.length) return '';
      return '<div class="sr-ai-k" style="--c:' + renk + '"><h5>' + bas + '</h5>' + l.map(function (x) { return '<div class="sr-ai-m"><b>' + srEsc(x.baslik) + '</b>' + srEsc(x.detay) + '</div>'; }).join('') + '</div>';
    };
    document.getElementById('srAiSonuc').innerHTML =
      '<div class="sr-cevap" style="white-space:normal"><div style="font-size:10.5px;font-weight:800;color:var(--sub);letter-spacing:.4px;text-transform:uppercase">Yapay zekâ özeti · ' + srEsc(a._donem || '') + ' · ' + srEsc(a._zaman || '') + '</div><div style="margin-top:4px">' + srEsc(a.ozet) + '</div></div>' +
      '<div class="sr-ai-g">' + bol('one_cikanlar', 'Öne çıkanlar', '#4F46E5') + bol('riskler', 'Riskler', '#DC2626') + bol('oneriler', 'Öneriler', '#059669') + '</div>';
    document.getElementById('srAiIndir').hidden = false;
  }
  function srPost(g) {
    return fetch('/satinalma-raporlar/ai?' + SR_AI_QS, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(g) }).then(function (r) { return r.json(); });
  }
  function srAi(yenile) {
    var b = document.getElementById('srAiBtn'); if (b.disabled) return; var eski = b.innerHTML;
    b.disabled = true; b.innerHTML = '<span class="material-icons-outlined" style="animation:srDon 1s linear infinite">autorenew</span> Hazırlanıyor… (~20 sn)';
    srPost({ yenile: yenile ? 1 : 0 }).then(function (s) {
      b.disabled = false; b.innerHTML = eski;
      if (!s.ok) { document.getElementById('srAiSonuc').innerHTML = '<div class="sr-cevap" style="color:#B91C1C">' + srEsc(s.hata || 'Özet hazırlanamadı.') + '</div>'; return; }
      srAiCiz(s.analiz);
    }).catch(function () { b.disabled = false; b.innerHTML = eski; document.getElementById('srAiSonuc').innerHTML = '<div class="sr-cevap" style="color:#B91C1C">Bağlantı hatası.</div>'; });
  }
  function srSor() {
    var q = document.getElementById('srSoru').value.trim(); if (!q) return; var c = document.getElementById('srCevap');
    c.innerHTML = '<div class="sr-cevap">Düşünüyor…</div>';
    srPost({ soru: q }).then(function (s) { c.innerHTML = '<div class="sr-cevap"><b style="display:block;margin-bottom:6px;color:var(--sub)">❓ ' + srEsc(q) + '</b>' + srEsc(s.ok ? s.cevap : (s.hata || 'Cevap alınamadı.')) + '</div>'; })
      .catch(function () { c.innerHTML = '<div class="sr-cevap">Bağlantı hatası.</div>'; });
  }
  if (SR_AI) srAiCiz(SR_AI);
</script>
@endsection

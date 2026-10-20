@extends('layout.app')
@section('title', 'Garson Çağrıları')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Garson Çağrıları')
@section('rk_alt', 'QR menüden gelen çağrılar buraya anında düşer')

@push('head')
<style>
/* ====== garson_cagrilari_screen.dart BİREBİR ====== */
#gcroot{--poll:10}
/* AppBar başlık badge (Flutter kırmızı yuvarlak sayaç) */
#gcroot .gc-badge{display:inline-flex;align-items:center;justify-content:center;
  background:var(--kirmizi);color:#fff;border-radius:12px;padding:1px 9px;
  font-size:13px;font-weight:700;min-width:24px;line-height:22px;margin-left:8px}
/* Özet şerit kartları */
#gcroot .gc-ozet{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px}
#gcroot .gc-ozet .gc-oc{display:flex;align-items:center;gap:8px;padding:10px 16px}
/* Flutter ListView.separated padding:EdgeInsets.all(14), separatorBuilder: SizedBox(12) */
#gcroot .gc-liste{display:flex;flex-direction:column;gap:12px;margin-bottom:24px}
/* Flutter _kart Container: borderRadius:18, padding:16, boxShadow:golge */
#gcroot .gc-kart{display:flex;align-items:center;gap:13px;padding:16px;
  background:var(--card);border:1px solid var(--line);border-radius:18px;
  box-shadow:var(--golge);transition:box-shadow .1s}
#gcroot .gc-kart.geciken{border:1.6px solid var(--kirmizi)}
/* Flutter icon box: width:50,height:50, borderRadius:14, color.withValues(alpha:0.16) */
#gcroot .gc-ikon{width:50px;height:50px;flex:0 0 50px;border-radius:14px;
  display:flex;align-items:center;justify-content:center}
#gcroot .gc-ikon .material-icons-outlined{font-size:26px}
/* Flutter Expanded > Column */
#gcroot .gc-meta{min-width:0;flex:1}
/* Flutter masa ad: fontSize:19, fontWeight:w900 */
#gcroot .gc-masa{font-size:19px;font-weight:900;color:var(--ink);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* Flutter tipYazi: fontSize:13.5, fontWeight:w700 */
#gcroot .gc-tip{font-size:13.5px;font-weight:700;margin-top:2px}
/* Flutter süre kolonisi */
#gcroot .gc-sure-col{text-align:right;flex:0 0 auto}
/* Flutter süre: fontSize:16, fontWeight:w800; geciken=kirmizi, normal=gold */
#gcroot .gc-sure{font-size:16px;font-weight:800}
/* Flutter saat: fontSize:11, color:sub */
#gcroot .gc-saat{font-size:11px;color:var(--sub)}
/* Flutter FilledButton (karşıla / taşı) */
#gcroot .gc-btn{display:inline-flex;align-items:center;gap:5px;padding:12px 16px;
  border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;
  color:#fff;flex:0 0 auto;white-space:nowrap}
#gcroot .gc-btn.yesil{background:#16A34A}
#gcroot .gc-btn.mor{background:var(--mor1)}
#gcroot .gc-btn .material-icons-outlined{font-size:18px}
/* Flutter boş ekran: SizedBox(height: ~28%), Icon(60), Text(16,w600) */
#gcroot .gc-bos{text-align:center;padding:64px 20px 48px;
  background:var(--card);border:1px solid var(--line);border-radius:14px;
  box-shadow:var(--golge)}
#gcroot .gc-bos .material-icons-outlined{font-size:60px;color:var(--sub)}
#gcroot .gc-bos .b{font-size:16px;font-weight:600;color:var(--sub);margin-top:14px}
#gcroot .gc-bos .s{font-size:12.5px;color:var(--sub);margin-top:6px}
/* Tümünü karşıla butonu (Flutter gold TextButton.icon) */
#gcroot .gc-tumunu{display:inline-flex;align-items:center;gap:6px;
  background:transparent;border:none;cursor:pointer;
  color:var(--gold);font-size:14px;font-weight:700;padding:6px 8px;border-radius:8px}
#gcroot .gc-tumunu:hover{background:color-mix(in srgb,var(--gold) 12%,transparent)}
#gcroot .gc-tumunu .material-icons-outlined{font-size:19px;color:var(--gold)}
/* Son karşılananlar tablosu — web'e özgü ek bilgi (Flutter'da yoktu, web paritesi) */
#gcroot .gc-karsilanan-tablo .rk-rozet .material-icons-outlined{font-size:14px}
/* Yenile göstergesi */
#gcroot .gc-yenile{font-size:11.5px;color:var(--sub);margin-left:auto}
body.koyu #gcroot .gc-kart{border-color:var(--line)}
body.koyu #gcroot .gc-kart.geciken{border-color:var(--kirmizi)}
</style>
@endpush

@php
  /* Flutter _tipYazi ile BİREBİR */
  $tipAdi = function ($t) {
    return $t === 'servise_hazir' ? 'Servise hazır (mutfak)'
      : ($t === 'tasima'         ? 'Taşıma talebi'
      : ($t === 'odeme'          ? 'Ödeme alınacak'
      : ($t === 'hesap'          ? 'Hesap istiyor'
      : ($t === 'siparis'        ? 'Sipariş verdi'
      : 'Garson çağırıyor'))));
  };
  /* Flutter _tipIkon ile BİREBİR (material-icons-outlined karşılığı) */
  $tipIkon = function ($t) {
    return $t === 'servise_hazir' ? 'room_service'
      : ($t === 'tasima'          ? 'swap_horiz'
      : ($t === 'odeme'           ? 'payments'
      : ($t === 'hesap'           ? 'credit_card'
      : ($t === 'siparis'         ? 'receipt_long'
      : 'notifications_active'))));
  };
  /* Flutter vurgu: hesap=amber, diğer=kirmizi (taşıma mor buton, ikon rengi kirmizi) */
  $tipRenkVar = function ($t) {
    return $t === 'hesap' ? 'var(--amber)'
      : ($t === 'tasima'           ? 'var(--mor1)'
      : ($t === 'servise_hazir'    ? 'var(--yesil)'
      : 'var(--kirmizi)'));
  };
  /* Flutter _sure: sn<60 -> 'X sn', else 'X dk' */
  $sureYazi = function ($sn) {
    $sn = max(0, (int) $sn);
    return $sn < 60 ? ($sn . ' sn') : (floor($sn / 60) . ' dk');
  };
  $bekleyenSay = count($bekleyen ?? []);
  $karsilananSay = count($karsilanan ?? []);
@endphp

@section('content')
<div id="gcroot">

{{-- ===== FLASH MESAJLARI ===== --}}
@if (session('ok'))
  <div class="rk-flash ok">✅ {{ session('ok') }}</div>
@endif
@if (session('hata') || !empty($hata))
  <div class="rk-flash err">⚠️ {{ session('hata') ?: $hata }}</div>
@endif

{{-- ===== AppBar benzer: özet + tümünü karşıla + sayaç ===== --}}
{{-- Flutter AppBar title: 🔔 Garson Çağrıları + kırmızı badge + gold "Tümünü karşıla" + volume icon --}}
<div class="gc-ozet">

  {{-- Bekleyen sayaç (Flutter AppBar badge) --}}
  <div class="rk-card gc-oc">
    <span class="material-icons-outlined" style="color:var(--kirmizi)">notifications_active</span>
    <span class="rk-sub">Bekleyen</span>
    <b style="font-size:17px;color:{{ $bekleyenSay ? 'var(--kirmizi)' : 'var(--ink)' }}">{{ $bekleyenSay }}</b>
    @if ($bekleyenSay)
      <span class="gc-badge">{{ $bekleyenSay }}</span>
    @endif
  </div>

  {{-- Son karşılanan sayaç --}}
  <div class="rk-card gc-oc">
    <span class="material-icons-outlined" style="color:var(--yesil)">done_all</span>
    <span class="rk-sub">Son karşılanan</span>
    <b style="font-size:17px;color:var(--ink)">{{ $karsilananSay }}</b>
  </div>

  {{-- Flutter "Tümünü karşıla" TextButton.icon (gold, sadece bekleyen varsa) --}}
  @if ($bekleyenSay)
    <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0">
      @csrf
      <input type="hidden" name="hepsi" value="1">
      <button type="submit" class="gc-tumunu">
        <span class="material-icons-outlined">done_all</span>
        Tümünü karşıla
      </button>
    </form>
  @endif

  {{-- Flutter 10 sn auto-yenile notu --}}
  <span class="gc-yenile" id="gcSayac">10 sn'de bir yenilenir</span>
</div>

{{-- ===== BEKLEYEN ÇAĞRILAR BÖLÜMÜ ===== --}}
{{-- Flutter: ListView.separated (bekleyen liste) --}}
<div class="rk-bolum">
  <span class="cubuk" style="background:var(--kirmizi)"></span>
  <span class="b">Bekleyen Çağrılar</span>
  @if ($bekleyenSay)
    <span class="sayi" style="background:color-mix(in srgb,var(--kirmizi) 13%,transparent);color:var(--kirmizi)">{{ $bekleyenSay }}</span>
  @endif
</div>

@if (!$bekleyenSay)
  {{-- Flutter boş liste görünümü --}}
  <div class="gc-bos" style="margin-bottom:24px">
    <span class="material-icons-outlined">notifications_none</span>
    <div class="b">Bekleyen çağrı yok</div>
    <div class="s">QR menüden gelen çağrılar buraya anında düşer.</div>
  </div>
@else
  {{-- Flutter ListView.separated, padding:14, gap:12 --}}
  <div class="gc-liste">
    @foreach ($bekleyen as $c)
      @php
        $tip      = $c['tip'] ?? 'garson';
        $geciken  = ($c['saniye'] ?? 0) >= 60;   /* Flutter: sn >= 60 → kırmızı border */
        $renkVar  = $tipRenkVar($tip);
        $tasimaMi = ($tip === 'tasima');
        $masaAd   = $tasimaMi && !empty($c['hedef'])
                      ? ($c['masa'] . ' → ' . $c['hedef'])
                      : ($c['masa'] ?? '—');
      @endphp
      {{-- Flutter _kart Container --}}
      <div class="gc-kart{{ $geciken ? ' geciken' : '' }}">

        {{-- Flutter: ikon kutu (50x50, borderRadius:14, vurgu %16 arka plan) --}}
        <div class="gc-ikon"
          style="background:color-mix(in srgb,{{ $renkVar }} 16%,transparent)">
          <span class="material-icons-outlined" style="color:{{ $renkVar }}">{{ $tipIkon($tip) }}</span>
        </div>

        {{-- Flutter: Expanded Column (masa adı + tip yazısı) --}}
        <div class="gc-meta">
          <div class="gc-masa">{{ $masaAd }}</div>
          <div class="gc-tip" style="color:{{ $renkVar }}">{{ $tipAdi($tip) }}</div>
        </div>

        {{-- Flutter: sağ kolon (süre + saat) --}}
        <div class="gc-sure-col" style="margin-right:10px">
          <div class="gc-sure" style="color:{{ $geciken ? 'var(--kirmizi)' : 'var(--gold)' }}">
            {{ $sureYazi($c['saniye'] ?? 0) }}
          </div>
          <div class="gc-saat">{{ $c['saat'] ?? '' }}</div>
        </div>

        {{-- Flutter: FilledButton — taşıma=mor+swap_horiz+'Taşı', diğer=yesil+'✓' --}}
        <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0">
          @csrf
          <input type="hidden" name="id" value="{{ $c['id'] }}">
          @if ($tasimaMi)
            <button type="submit" class="gc-btn mor" title="Taşıma onayla">
              <span class="material-icons-outlined">swap_horiz</span> Taşı
            </button>
          @else
            <button type="submit" class="gc-btn yesil" title="Karşıla"
              style="font-size:17px;font-weight:700">✓</button>
          @endif
        </form>

      </div>{{-- /gc-kart --}}
    @endforeach
  </div>{{-- /gc-liste --}}
@endif

{{-- ===== SON KARŞILANANLAR (web bonus — Flutter'da list sonundaymış gibi düşün) ===== --}}
@if ($karsilananSay)
  <div class="rk-bolum" style="margin-top:8px">
    <span class="cubuk" style="background:var(--yesil)"></span>
    <span class="b">Son Karşılananlar</span>
    <span class="sayi"
      style="background:color-mix(in srgb,var(--yesil) 13%,transparent);color:var(--yesil)">{{ $karsilananSay }}</span>
  </div>
  <div class="rk-tablo gc-karsilanan-tablo">
    <table>
      <thead>
        <tr>
          <th>Masa</th>
          <th>Tip</th>
          <th>Saat</th>
          <th class="sag">Durum</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($karsilanan as $c)
          @php
            $tip     = $c['tip'] ?? 'garson';
            $masaAd  = ($tip === 'tasima' && !empty($c['hedef']))
                         ? ($c['masa'] . ' → ' . $c['hedef'])
                         : ($c['masa'] ?? '—');
          @endphp
          <tr>
            <td style="font-weight:700;color:var(--sub2)">{{ $masaAd }}</td>
            <td class="rk-sub">
              <span class="material-icons-outlined"
                style="font-size:17px;color:{{ $tipRenkVar($tip) }};vertical-align:-3px">{{ $tipIkon($tip) }}</span>
              {{ $tipAdi($tip) }}
            </td>
            <td class="rk-sub">{{ $c['saat'] ?? '' }}</td>
            <td class="sag">
              <span class="rk-rozet"
                style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 40%,transparent);background:color-mix(in srgb,var(--yesil) 13%,transparent)">
                <span class="material-icons-outlined">check</span> Karşılandı
              </span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

</div>{{-- /gcroot --}}

{{-- ===== Flutter 4 sn poll karşılığı: 10 sn'de bir sayfa yenile + geri sayım ===== --}}
<script>
(function () {
  var sn = 10, el = document.getElementById('gcSayac');
  function tick() {
    sn--;
    if (el) el.textContent = sn > 0 ? (sn + ' sn\'de bir yenilenir') : 'Yenileniyor…';
    if (sn <= 0) { window.location.reload(); return; }
    setTimeout(tick, 1000);
  }
  setTimeout(tick, 1000);
})();
</script>
@endsection

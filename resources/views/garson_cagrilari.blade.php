@extends('layout.app')
@section('title', 'Garson Çağrıları')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Garson Çağrıları')
@section('rk_alt', 'QR menüden gelen çağrılar buraya anında düşer')

@section('content')
@php
  // Flutter garson_cagrilari_screen ile BİREBİR semantik: tip -> etiket + renk + ikon.
  $tipAdi = function ($t) {
    return $t === 'servise_hazir' ? 'Servise hazır (mutfak)'
      : ($t === 'tasima' ? 'Taşıma talebi'
      : ($t === 'odeme' ? 'Ödeme alınacak'
      : ($t === 'hesap' ? 'Hesap istiyor'
      : ($t === 'acil' ? 'Acil çağrı'
      : ($t === 'siparis' ? 'Sipariş verdi' : 'Garson çağırıyor')))));
  };
  // Flutter _tipIkon karşılığı material-icons-outlined
  $tipIkon = function ($t) {
    return $t === 'servise_hazir' ? 'room_service'
      : ($t === 'tasima' ? 'swap_horiz'
      : ($t === 'odeme' ? 'payments'
      : ($t === 'hesap' ? 'credit_card'
      : ($t === 'siparis' ? 'receipt_long' : 'notifications_active'))));
  };
  // Flutter vurgu rengi: hesap=amber, taşıma=mor, ödeme/acil/diğer=kırmızı, servise_hazir=yeşil
  $tipRenk = function ($t) {
    return $t === 'hesap' ? 'var(--amber)'
      : ($t === 'tasima' ? 'var(--mor1)'
      : ($t === 'servise_hazir' ? 'var(--yesil)' : 'var(--kirmizi)'));
  };
  $sureYazi = function ($sn) {
    $sn = $sn < 0 ? 0 : $sn;
    return $sn < 60 ? ($sn . ' sn') : (floor($sn / 60) . ' dk');
  };
@endphp

{{-- 10 sn'de bir sayfa kendini yeniler (Flutter 4 sn poll karşılığı) --}}
<meta http-equiv="refresh" content="10">

@if (session('ok'))
  <div class="rk-flash ok">✅ {{ session('ok') }}</div>
@endif
@if (session('hata') || !empty($hata))
  <div class="rk-flash err">⚠️ {{ session('hata') ?: $hata }}</div>
@endif

{{-- ÖZET ŞERİDİ: Bekleyen / Son karşılanan + Tümünü karşıla --}}
<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px">
  <div class="rk-card" style="padding:10px 16px;display:flex;align-items:center;gap:8px">
    <span class="material-icons-outlined" style="color:var(--kirmizi)">notifications_active</span>
    <span class="rk-sub">Bekleyen</span>
    <b style="font-size:17px;color:{{ count($bekleyen) ? 'var(--kirmizi)' : 'var(--ink)' }}">{{ count($bekleyen) }}</b>
  </div>
  <div class="rk-card" style="padding:10px 16px;display:flex;align-items:center;gap:8px">
    <span class="material-icons-outlined" style="color:var(--yesil)">done_all</span>
    <span class="rk-sub">Son karşılanan</span>
    <b style="font-size:17px;color:var(--ink)">{{ count($karsilanan) }}</b>
  </div>
  @if (count($bekleyen))
    <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0 0 0 auto">
      @csrf
      <input type="hidden" name="hepsi" value="1">
      <button type="submit" class="rk-btn" style="background:var(--yesil)">
        <span class="material-icons-outlined" style="font-size:18px">done_all</span> Tümünü karşıla
      </button>
    </form>
  @endif
  <span class="rk-sub" style="font-size:11.5px;{{ count($bekleyen) ? '' : 'margin-left:auto' }}">10 sn'de bir otomatik yenilenir</span>
</div>

{{-- ================= BEKLEYEN ÇAĞRILAR ================= --}}
<div class="rk-bolum">
  <span class="cubuk" style="background:var(--kirmizi)"></span>
  <span class="b">Bekleyen Çağrılar</span>
  @if (count($bekleyen))
    <span class="sayi" style="background:color-mix(in srgb,var(--kirmizi) 13%,transparent);color:var(--kirmizi)">{{ count($bekleyen) }}</span>
  @endif
</div>

@if (!count($bekleyen))
  <div class="rk-card" style="text-align:center;padding:48px 20px;margin-bottom:24px">
    <span class="material-icons-outlined" style="font-size:56px;color:var(--sub)">notifications_none</span>
    <div style="font-size:16px;font-weight:700;color:var(--sub);margin-top:12px">Bekleyen çağrı yok</div>
    <div class="rk-sub" style="font-size:12.5px;margin-top:4px">QR menüden gelen çağrılar buraya anında düşer.</div>
  </div>
@else
  <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr));margin-bottom:24px">
    @foreach ($bekleyen as $c)
      @php
        $geciken = $c['saniye'] >= 60;
        $vurgu = $tipRenk($c['tip']);
        $tasimaMi = $c['tip'] === 'tasima';
      @endphp
      <div class="rk-card" style="display:flex;align-items:center;gap:13px;padding:16px;{{ $geciken ? 'border:1.6px solid var(--kirmizi)' : '' }}">
        {{-- tip ikonu (yumuşak renkli kutu) --}}
        <div style="width:50px;height:50px;flex:0 0 auto;border-radius:14px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,{{ $vurgu }} 16%,transparent)">
          <span class="material-icons-outlined" style="font-size:26px;color:{{ $vurgu }}">{{ $tipIkon($c['tip']) }}</span>
        </div>
        {{-- masa + tip --}}
        <div style="min-width:0;flex:1">
          <div style="font-size:19px;font-weight:900;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            {{ $tasimaMi && $c['hedef'] ? ($c['masa'] . ' → ' . $c['hedef']) : $c['masa'] }}
          </div>
          <div style="font-size:13.5px;font-weight:700;color:{{ $vurgu }};margin-top:2px">{{ $tipAdi($c['tip']) }}</div>
        </div>
        {{-- geçen süre + saat --}}
        <div style="text-align:right">
          <div style="font-size:16px;font-weight:800;color:{{ $geciken ? 'var(--kirmizi)' : 'var(--gold)' }}">{{ $sureYazi($c['saniye']) }}</div>
          <div class="rk-sub" style="font-size:11px">{{ $c['saat'] }}</div>
        </div>
        {{-- karşıla --}}
        <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0">
          @csrf
          <input type="hidden" name="id" value="{{ $c['id'] }}">
          <button type="submit" class="rk-btn" title="Karşıla" style="background:{{ $tasimaMi ? 'var(--mor1)' : 'var(--yesil)' }};padding:12px 16px;font-size:16px">
            @if ($tasimaMi)
              <span class="material-icons-outlined" style="font-size:18px">swap_horiz</span> Taşı
            @else
              ✓
            @endif
          </button>
        </form>
      </div>
    @endforeach
  </div>
@endif

{{-- ================= SON KARŞILANAN ÇAĞRILAR ================= --}}
@if (count($karsilanan))
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--yesil)"></span>
    <span class="b">Son Karşılananlar</span>
    <span class="sayi" style="background:color-mix(in srgb,var(--yesil) 13%,transparent);color:var(--yesil)">{{ count($karsilanan) }}</span>
  </div>
  <div class="rk-tablo">
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
          <tr>
            <td style="font-weight:700;color:var(--sub2)">
              {{ $c['tip'] === 'tasima' && $c['hedef'] ? ($c['masa'] . ' → ' . $c['hedef']) : $c['masa'] }}
            </td>
            <td class="rk-sub">
              <span class="material-icons-outlined" style="font-size:17px;color:{{ $tipRenk($c['tip']) }};vertical-align:-3px">{{ $tipIkon($c['tip']) }}</span>
              {{ $tipAdi($c['tip']) }}
            </td>
            <td class="rk-sub">{{ $c['saat'] }}</td>
            <td class="sag">
              <span class="rk-rozet" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 40%,transparent);background:color-mix(in srgb,var(--yesil) 13%,transparent)">
                <span class="material-icons-outlined" style="font-size:14px">check</span> Karşılandı
              </span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

@extends('layout.app')
@section('rk_ikon', 'schedule')
@section('baslik', 'Puantaj')
@section('title', 'Puantaj')

@section('content')
@php
  // Flutter puantaj_screen (masaüstü) BİREBİR — kim kaçta geldi/gitti, çalışma süresi, vardiya adımı.
  // Salt-okunur. Route değişkenleri: $personeller[], $period (gun/hafta/ay).
  $periodlar = ['gun' => 'Bugün', 'hafta' => 'Bu Hafta', 'ay' => 'Bu Ay'];
  $period = in_array($period ?? 'gun', array_keys($periodlar)) ? ($period ?? 'gun') : 'gun';
  $personeller = $personeller ?? [];

  $toplamDk = 0; $maxDk = 0;
  foreach ($personeller as $pp) {
    $d = (int) ($pp['toplam_dk'] ?? 0);
    $toplamDk += $d; $maxDk = max($maxDk, $d);
  }

  // Flutter _sure: dk → "Xs Yd" / "Yd"
  $sure = function ($dk) {
    $dk = (int) $dk; $s = intdiv($dk, 60); $d = $dk % 60;
    return $s > 0 ? ($s . 's ' . $d . 'd') : ($d . 'd');
  };
  $sayi = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp

{{-- Dönem sekmeleri (Flutter period seçici: gün/hafta/ay → ?period=) --}}
<div class="rk-bolum" style="justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
    @foreach ($periodlar as $k => $ad)
      <a href="/puantaj?period={{ $k }}"
         class="rk-btn {{ $period === $k ? '' : 'bos' }}"
         style="{{ $period === $k ? 'background:var(--mor1)' : '' }}">{{ $ad }}</a>
    @endforeach
  </div>
  @if (!empty($personeller))
    <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
      <span class="rk-rozet" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 45%,transparent);background:color-mix(in srgb,var(--mor1) 12%,transparent)">
        <span class="material-icons-outlined" style="font-size:16px">group</span>{{ count($personeller) }} personel
      </span>
      <span class="rk-rozet" style="color:var(--sub2);border-color:var(--line);background:var(--card2)">
        <span class="material-icons-outlined" style="font-size:16px">timer</span>{{ $sure($toplamDk) }} toplam
      </span>
    </div>
  @endif
</div>

{{-- Çalışma Süreleri bölüm başlığı (Flutter: puantaj listesi) --}}
<div class="rk-bolum">
  <span class="cubuk" style="background:var(--mor1)"></span>
  <span class="b">Çalışma Süreleri</span>
  @if (!empty($personeller))
    <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($personeller) }}</span>
  @endif
</div>

@if (empty($personeller))
  <div class="rk-card" style="text-align:center;padding:38px">
    <span class="rk-sub">Bu dönemde mesai kaydı yok.</span>
  </div>
@else
  {{-- Flutter: personel bazlı kartlar — genişken ikili grid --}}
  <div class="rk-grid" style="grid-template-columns:repeat(2,minmax(0,1fr));align-items:start">
    @foreach ($personeller as $i => $p)
      @php
        $ad = $p['ad'] ?? '';
        $pDk = (int) ($p['toplam_dk'] ?? 0);
        $acik = !empty($p['acik']);
        $kayitlar = $p['kayitlar'] ?? [];
        $oran = $maxDk > 0 ? round($pDk / $maxDk * 100) : 0;
        $bas = mb_substr($ad !== '' ? $ad : '?', 0, 1);
      @endphp
      <div class="rk-card">
        {{-- Üst satır: avatar + ad + mesaide rozeti + toplam süre (sağ) --}}
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:50%;flex:0 0 34px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 15%,transparent)">{{ $bas }}</div>
          <div style="flex:1;min-width:0">
            <div style="font-size:15.5px;font-weight:800;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ad ?: '-' }}</div>
            <div style="font-size:11.5px;color:var(--sub)">
              {{ count($kayitlar) }} kayıt
              @if ($acik)· <span style="color:var(--yesil);font-weight:700">mesaide</span>@endif
            </div>
          </div>
          <div style="text-align:right">
            <div style="font-size:18px;font-weight:800;color:var(--mor1)">{{ $sure($pDk) }}</div>
            <div style="font-size:10.5px;color:var(--sub)">toplam</div>
          </div>
          @if ($acik)
            <span class="rk-rozet" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 50%,transparent);background:color-mix(in srgb,var(--yesil) 14%,transparent)">Mesaide</span>
          @endif
        </div>

        {{-- Toplam süre oranı (en çok çalışana göre) --}}
        <div style="width:100%;height:6px;border-radius:9px;background:var(--card2);overflow:hidden;margin:12px 0 4px">
          <div style="height:100%;width:{{ $oran }}%;border-radius:9px;background:linear-gradient(90deg,var(--mor1),var(--mor2))"></div>
        </div>

        {{-- Mesai kayıtları tablosu (Flutter kart: giriş/çıkış/süre/adım) --}}
        @if (empty($kayitlar))
          <div class="rk-sub" style="font-size:12px;padding:10px 0 2px">Kayıt yok.</div>
        @else
          <div class="rk-tablo" style="margin-top:12px;box-shadow:none">
            <table>
              <thead>
                <tr>
                  <th>Tarih</th>
                  <th>Giriş</th>
                  <th>Çıkış</th>
                  <th class="sag">Süre</th>
                  <th class="sag">Adım</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($kayitlar as $k)
                  @php
                    $kAcik = ($k['durum'] ?? '') === 'acik';
                    $tip = $k['cikis_tip'] ?? '';
                    // Flutter: açık VEYA eksik çıkış (geofence/kapanış) → uyarı
                    $eksik = !$kAcik && in_array($tip, ['geofence', 'kapanis']);
                  @endphp
                  <tr>
                    <td style="color:var(--sub);white-space:nowrap">{{ $k['tarih'] ?? '-' }}</td>
                    <td style="white-space:nowrap">{{ $k['giris'] ?? '-' }}</td>
                    <td style="white-space:nowrap">
                      @if ($kAcik)
                        <span style="display:inline-flex;align-items:center;gap:5px;color:var(--yesil);font-weight:700">
                          <span style="width:7px;height:7px;border-radius:50%;background:var(--yesil);display:inline-block"></span>devam
                        </span>
                      @elseif ($eksik)
                        <span style="color:var(--amber)" title="Eksik çıkış ({{ $tip }})">{{ $k['cikis'] ?: '?' }} ⚠</span>
                      @else
                        <span>{{ $k['cikis'] ?: '?' }}</span>
                      @endif
                    </td>
                    <td class="sag" style="font-weight:700;white-space:nowrap">{{ $sure($k['dakika'] ?? 0) }}</td>
                    <td class="sag" style="color:var(--sub);white-space:nowrap">{{ $sayi($k['adim'] ?? 0) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    @endforeach
  </div>
@endif

<style>
  @media (max-width:860px){
    .rk-body .rk-grid[style*="repeat(2"]{grid-template-columns:1fr !important}
  }
</style>
@endsection

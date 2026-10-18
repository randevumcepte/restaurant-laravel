@extends('layout.app')
@section('rk_ikon', 'emoji_events')
@section('baslik', 'Garson Performansı')
@section('title', 'Garson Performansı')

@section('content')
@php
  // Flutter garson_performans_screen (karne kısmı) BİREBİR — ısı haritası web'de atlanır.
  $periodlar = ['gunluk' => 'Günlük', 'haftalik' => 'Haftalık', 'aylik' => 'Aylık', 'yillik' => 'Yıllık'];
  $period = in_array($period ?? '', array_keys($periodlar)) ? $period : 'gunluk';
  $garsonlar = $garsonlar ?? [];

  // Flutter: ciroya göre sıralı (karne başlığı "Ciroya göre sıralı")
  usort($garsonlar, fn ($a, $b) => (float) ($b['ciro'] ?? 0) <=> (float) ($a['ciro'] ?? 0));

  $toplamCiro = array_sum(array_column($garsonlar, 'ciro'));
  $toplamAdisyon = array_sum(array_column($garsonlar, 'adisyon'));
  $toplamAdim = array_sum(array_column($garsonlar, 'adim'));

  $tl = fn ($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $sayi = fn ($v) => number_format((int) $v, 0, ',', '.');
@endphp

{{-- Dönem sekmeleri (Flutter period seçici: gün/hafta/ay/yıl → ?period=) --}}
<div class="rk-bolum" style="justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
    @foreach ($periodlar as $k => $ad)
      <a href="/garson-performans?period={{ $k }}"
         class="rk-btn {{ $period === $k ? '' : 'bos' }}"
         style="{{ $period === $k ? 'background:var(--mor1)' : '' }}">{{ $ad }}</a>
    @endforeach
  </div>
  @if (!empty($garsonlar))
    <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
      <span class="rk-rozet" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 45%,transparent);background:color-mix(in srgb,var(--yesil) 12%,transparent)">
        <span class="material-icons-outlined" style="font-size:16px">payments</span>{{ $tl($toplamCiro) }}
      </span>
      <span class="rk-rozet" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 45%,transparent);background:color-mix(in srgb,var(--mor1) 12%,transparent)">
        <span class="material-icons-outlined" style="font-size:16px">receipt_long</span>{{ $sayi($toplamAdisyon) }} adisyon
      </span>
      <span class="rk-rozet" style="color:var(--sub2);border-color:var(--line);background:var(--card2)">
        <span class="material-icons-outlined" style="font-size:16px">directions_walk</span>{{ $sayi($toplamAdim) }} adım
      </span>
    </div>
  @endif
</div>

{{-- Garson Karnesi bölüm başlığı (Flutter: "Garson Karnesi" + "Ciroya göre sıralı") --}}
<div class="rk-bolum">
  <span class="cubuk" style="background:var(--mor1)"></span>
  <span class="b">Garson Karnesi</span>
  @if (!empty($garsonlar))
    <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($garsonlar) }}</span>
  @endif
</div>
<div class="rk-sub" style="font-size:12px;margin:-4px 0 14px">Ciroya göre sıralı</div>

@if (empty($garsonlar))
  <div class="rk-card" style="text-align:center;padding:38px">
    <span class="rk-sub">Bu dönemde veri yok.</span>
  </div>
@else
  {{-- Flutter: genişken ikili grid (Wrap), karne kartları --}}
  <div class="rk-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
    @foreach ($garsonlar as $i => $g)
      @php
        $ad = $g['ad'] ?? '';
        $ciro = (float) ($g['ciro'] ?? 0);
        $adisyon = (int) ($g['adisyon'] ?? 0);
        $kalem = (int) ($g['kalem'] ?? 0);
        $servis = (int) round((float) ($g['ort_servis_dk'] ?? 0));
        $adim = (int) ($g['adim'] ?? 0);
        $saatler = $g['saat_dagilim'] ?? [];
        $maxSaat = 1;
        foreach ($saatler as $s) { $maxSaat = max($maxSaat, (int) ($s['adisyon'] ?? 0)); }
        $bas = mb_substr($ad !== '' ? $ad : '?', 0, 1);
      @endphp
      <div class="rk-card">
        {{-- Üst satır: avatar + ad + ciro (sağ, yeşil) --}}
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:34px;height:34px;border-radius:50%;flex:0 0 34px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 15%,transparent)">{{ $bas }}</div>
          <div style="flex:1;min-width:0;font-size:15.5px;font-weight:800;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ad }}</div>
          <div style="font-size:15px;font-weight:800;color:var(--yesil)">{{ $tl($ciro) }}</div>
        </div>

        {{-- 4 metrik (Flutter _metrik: Masa/Adisyon · Ürün kalemi · Ort. servis · Adım) --}}
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:14px;text-align:center">
          @foreach ([[$adisyon, 'Masa/Adisyon'], [$kalem, 'Ürün kalemi'], [$servis . ' dk', 'Ort. servis'], [$sayi($adim), 'Adım']] as [$deger, $etiket])
            <div>
              <div style="font-size:15px;font-weight:800;color:var(--ink)">{{ $deger }}</div>
              <div style="font-size:10.5px;color:var(--sub);margin-top:2px">{{ $etiket }}</div>
            </div>
          @endforeach
        </div>

        {{-- Saatlik yoğunluk (Flutter mini bar grafiği, 08→23) --}}
        <div style="font-size:11px;color:var(--sub);margin-top:14px">Saatlik yoğunluk</div>
        <div style="display:flex;align-items:flex-end;gap:2px;height:40px;margin-top:6px">
          @if (empty($saatler))
            <div style="flex:1;align-self:center;text-align:center;font-size:11px;color:var(--sub)">Veri yok</div>
          @else
            @foreach ($saatler as $s)
              @php
                $v = (int) ($s['adisyon'] ?? 0);
                $yuzde = $v > 0 ? max(8, round($v / $maxSaat * 100)) : 0;
              @endphp
              <div title="{{ $s['saat'] ?? '' }}:00 · {{ $v }} adisyon"
                   style="flex:1;height:{{ $yuzde }}%;min-height:{{ $v > 0 ? 3 : 0 }}px;border-radius:2px;background:{{ $v > 0 ? 'var(--mor1)' : 'var(--line)' }}"></div>
            @endforeach
          @endif
        </div>
        @if (!empty($saatler))
          <div style="display:flex;justify-content:space-between;font-size:9px;color:var(--sub);margin-top:2px">
            <span>08</span><span>23</span>
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

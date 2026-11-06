@extends('layout.app')
@section('rk_ikon', 'emoji_events')
@section('baslik', 'Garson Performansı')
@section('title', 'Garson Performansı')

@push('head')
<style>
/* ---- CSS değişkenleri (koyu tema override) ---- */
#gp{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #gp{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#6b7a99;--line:#2c3650}

/* Period seçici */
#gp .gp-periods{display:flex;gap:8px;flex-wrap:wrap;background:var(--card);padding:10px 14px;border-radius:14px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#gp .gp-chip{display:inline-flex;align-items:center;padding:7px 16px;border-radius:20px;border:1px solid var(--line);background:var(--card2);color:var(--sub2);font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:none;transition:.12s}
#gp .gp-chip.akt{background:var(--mor1);border-color:var(--mor1);color:#fff}
#gp .gp-chip:hover:not(.akt){border-color:var(--mor1);color:var(--mor1)}

/* Isı haritası bağlantısı */
#gp .isi-bag{display:flex;align-items:center;gap:12px;background:var(--card);border-radius:14px;padding:12px 16px;margin-bottom:16px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#gp .isi-bag .material-icons-outlined{color:var(--mor1)}
#gp .isi-bag div{flex:1;min-width:0;font-size:12.5px;color:var(--sub);line-height:1.4}
#gp .isi-bag b{display:block;font-size:14px;color:var(--ink)}
#gp .isi-bag a{flex:none;padding:8px 14px;border-radius:10px;background:var(--mor1);color:#fff;font-size:12.5px;font-weight:700;text-decoration:none}
@media(max-width:560px){#gp .isi-bag{flex-wrap:wrap}#gp .isi-bag a{flex:1 1 100%;text-align:center}}

/* Adım/mesafe istatistik çifti */
#gp .adim-row{display:flex;gap:10px;margin-bottom:14px}
#gp .adim-stat{flex:1;display:flex;align-items:center;gap:10px;background:var(--card2);border-radius:14px;padding:10px 14px;border:1.5px solid var(--line)}
#gp .adim-ikon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex:none}
#gp .adim-deger{font-size:21px;font-weight:900;color:var(--ink);line-height:1}
#gp .adim-birim{font-size:12px;font-weight:700;color:var(--sub);margin-left:3px}

/* Garson karne bölüm */
#gp .karne-baslik{font-size:16px;font-weight:900;color:var(--ink);margin-bottom:4px}
#gp .karne-alt{font-size:12px;color:var(--sub);margin-bottom:10px}
#gp .karne-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
@media(max-width:780px){#gp .karne-grid{grid-template-columns:1fr}}
#gp .karne-kart{background:var(--card);border-radius:16px;padding:14px;box-shadow:0 2px 8px rgba(0,0,0,.07)}
#gp .karne-ust{display:flex;align-items:center;gap:10px;margin-bottom:12px}
#gp .karne-avatar{width:34px;height:34px;border-radius:50%;background:color-mix(in srgb,var(--mor1) 15%,transparent);
  display:flex;align-items:center;justify-content:center;font-weight:800;font-size:15px;color:var(--mor1);flex:none}
#gp .karne-ad{flex:1;font-size:15.5px;font-weight:800;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#gp .karne-ciro{font-size:15px;font-weight:800;color:var(--yesil)}
#gp .karne-metrikler{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;text-align:center;margin-bottom:14px}
#gp .karne-metrik-deger{font-size:15px;font-weight:800;color:var(--ink)}
#gp .karne-metrik-etiket{font-size:10.5px;color:var(--sub);margin-top:2px}
#gp .karne-saat-baslik{font-size:11px;color:var(--sub);margin-bottom:6px}
#gp .karne-grafik{display:flex;align-items:flex-end;gap:2px;height:40px}
#gp .karne-grafik-bar{flex:1;border-radius:2px;min-width:1px}
#gp .karne-grafik-ax{display:flex;justify-content:space-between;font-size:9px;color:var(--sub);margin-top:2px}

/* Boş durum */
#gp .gp-bos{background:var(--card);border-radius:14px;padding:38px;text-align:center;color:var(--sub)}
</style>
@endpush

@section('content')
@php
// --- Veri hazırlığı ---
$periodlar = ['gunluk' => 'Günlük', 'haftalik' => 'Haftalık', 'aylik' => 'Aylık', 'yillik' => 'Yıllık'];
$period = in_array($period ?? '', array_keys($periodlar)) ? $period : 'gunluk';
$garsonlar = $garsonlar ?? [];
usort($garsonlar, fn ($a, $b) => (float) ($b['ciro'] ?? 0) <=> (float) ($a['ciro'] ?? 0));

// --- Yardımcı formatlar ---
$tl = fn ($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
$sayi = fn ($v) => number_format((int) $v, 0, ',', '.');

// --- Adım toplam (tüm garsonlar) ---
$adimToplam = array_sum(array_column($garsonlar, 'adim'));
$kmToplam = $adimToplam * 0.75 / 1000;
$kmStr = fn ($km) => $km >= 1 ? number_format($km, 1, ',', '.') : number_format($km, 2, ',', '.');

// Yeni ısı haritası Garson Yönetimi'nde; o sayfaya yetkisi olmayana bağlantı gösterilmez
$isiYetki = function_exists('_panelYetkiVar') && function_exists('_posPersonel') && _panelYetkiVar(_posPersonel(), 'gor_personel');
@endphp

<div id="gp">

{{-- ============ PERIOD SEÇİCİ ============ --}}
<div class="gp-periods">
  @foreach ($periodlar as $k => $ad)
    <a href="/garson-performans?period={{ $k }}" class="gp-chip {{ $period === $k ? 'akt' : '' }}">{{ $ad }}</a>
  @endforeach
</div>

@if ($isiYetki)
  <div class="isi-bag">
    <span class="material-icons-outlined">local_fire_department</span>
    <div><b>Isı Haritası ve Alan Uyumu</b>Tüm salonu görün, garsonun kendi alanında mı çalıştığını kontrol edin.</div>
    <a href="/garson-yonetimi#isi">Isı Haritasını Aç</a>
  </div>
@endif

<div class="adim-row">
    <div class="adim-stat">
      <div class="adim-ikon" style="background:color-mix(in srgb,var(--mor1) 14%,transparent)">
        <span class="material-icons-outlined" style="color:var(--mor1);font-size:18px">directions_walk</span>
      </div>
      <div>
        <span class="adim-deger">{{ $sayi($adimToplam) }}</span>
        <span class="adim-birim">adım</span>
      </div>
    </div>
    <div class="adim-stat">
      <div class="adim-ikon" style="background:color-mix(in srgb,#16A34A 14%,transparent)">
        <span class="material-icons-outlined" style="color:#16A34A;font-size:18px">route</span>
      </div>
      <div>
        <span class="adim-deger">{{ $kmStr($kmToplam) }}</span>
        <span class="adim-birim">km</span>
      </div>
    </div>
</div>

{{-- ============ GARSON KARNESİ ============ --}}
<div class="karne-baslik">Garson Karnesi</div>
<div class="karne-alt">Ciroya göre sıralı</div>

@if (empty($garsonlar))
  <div class="gp-bos">Bu dönemde veri yok.</div>
@else
  <div class="karne-grid">
    @foreach ($garsonlar as $g)
      @php
        $ad = $g['ad'] ?? '';
        $ciro = (float) ($g['ciro'] ?? 0);
        $adisyon = (int) ($g['adisyon'] ?? 0);
        $kalem = (int) ($g['kalem'] ?? 0);
        $servis = (int) round((float) ($g['ort_servis_dk'] ?? 0));
        $adim = (int) ($g['adim'] ?? 0);
        $saatler = $g['saat_dagilim'] ?? [];
        $maxSaat = 1;
        foreach ($saatler as $s2) { $maxSaat = max($maxSaat, (int) ($s2['adisyon'] ?? 0)); }
        $bas = mb_substr($ad !== '' ? $ad : '?', 0, 1);
      @endphp
      <div class="karne-kart">
        {{-- Üst satır: avatar + ad + ciro --}}
        <div class="karne-ust">
          <div class="karne-avatar">{{ $bas }}</div>
          <div class="karne-ad">{{ $ad }}</div>
          <div class="karne-ciro">{{ $tl($ciro) }}</div>
        </div>
        {{-- 4 metrik --}}
        <div class="karne-metrikler">
          @foreach ([[$adisyon, 'Masa/Adisyon'], [$kalem, 'Ürün kalemi'], [$servis . ' dk', 'Ort. servis'], [$sayi($adim), 'Adım']] as [$deger, $etiket])
            <div>
              <div class="karne-metrik-deger">{{ $deger }}</div>
              <div class="karne-metrik-etiket">{{ $etiket }}</div>
            </div>
          @endforeach
        </div>
        {{-- Saatlik yoğunluk grafiği --}}
        <div class="karne-saat-baslik">Saatlik yoğunluk</div>
        <div class="karne-grafik">
          @if (empty($saatler))
            <div style="flex:1;align-self:center;text-align:center;font-size:11px;color:var(--sub)">Veri yok</div>
          @else
            @foreach ($saatler as $s2)
              @php
                $v = (int) ($s2['adisyon'] ?? 0);
                $pct = $v > 0 ? max(8, round($v / $maxSaat * 100)) : 0;
              @endphp
              <div class="karne-grafik-bar"
                   style="height:{{ $pct }}%;min-height:{{ $v > 0 ? 3 : 0 }}px;background:{{ $v > 0 ? 'var(--mor1)' : 'var(--line)' }}"
                   title="{{ $s2['saat'] ?? '' }}:00 · {{ $v }} adisyon"></div>
            @endforeach
          @endif
        </div>
        @if (!empty($saatler))
          <div class="karne-grafik-ax"><span>08</span><span>23</span></div>
        @endif
      </div>
    @endforeach
  </div>
@endif

</div>{{-- #gp --}}
@endsection

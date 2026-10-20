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

/* Isı haritası kutusu */
#gp .isi-kutu{background:var(--card);border-radius:18px;padding:14px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:20px}
#gp .isi-baslik{display:flex;align-items:center;gap:6px;font-size:15px;font-weight:900;color:var(--ink);margin-bottom:10px}
/* Garson filtresi chipler (yatay scroll) */
#gp .isi-garsonlar{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:12px;scrollbar-width:none}
#gp .isi-garsonlar::-webkit-scrollbar{display:none}
#gp .isi-g-chip{display:inline-flex;align-items:center;padding:6px 14px;border-radius:18px;border:1px solid var(--line);background:var(--card2);color:var(--sub2);font-weight:700;font-size:12px;cursor:pointer;white-space:nowrap;flex:none}
#gp .isi-g-chip.akt{background:var(--mor1);border-color:var(--mor1);color:#fff}

/* Adım/mesafe istatistik çifti */
#gp .adim-row{display:flex;gap:10px;margin-bottom:14px}
#gp .adim-stat{flex:1;display:flex;align-items:center;gap:10px;background:var(--card2);border-radius:14px;padding:10px 14px;border:1.5px solid var(--line)}
#gp .adim-ikon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex:none}
#gp .adim-deger{font-size:21px;font-weight:900;color:var(--ink);line-height:1}
#gp .adim-birim{font-size:12px;font-weight:700;color:var(--sub);margin-left:3px}

/* Canvas harita kap */
#gp .harita-kap{border-radius:14px;overflow:hidden;position:relative;background:var(--card2)}
#gp canvas{display:block;width:100%;height:auto}

/* Bölge ızgara (sema yokken) */
#gp .bolge-blok{margin-bottom:10px}
#gp .bolge-baslik{display:flex;align-items:center;gap:8px;font-size:13.5px;font-weight:900;color:var(--ink);margin-bottom:8px}
#gp .bolge-rozet{background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1);border-radius:9px;padding:1px 8px;font-size:10.5px;font-weight:700}
#gp .masa-izgara{display:flex;flex-wrap:wrap;gap:8px}
#gp .masa-tile{width:78px;height:58px;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;padding:4px;border:1px solid var(--line);font-size:13px;font-weight:900;color:var(--ink)}
#gp .masa-tile .islem{font-size:9.5px;font-weight:600;opacity:.9}

/* Lejant (yatay) */
#gp .lejant-bar{background:var(--card2);border-radius:14px;padding:14px;margin-top:12px}
#gp .lejant-baslik{font-size:13.5px;font-weight:900;color:var(--ink);margin-bottom:12px}
#gp .lejant-gradient{height:14px;border-radius:7px;background:linear-gradient(to right,#EF4444,#F97316,#EAB308,#22C55E,#06B6D4,#1D4ED8);margin-bottom:8px}
#gp .lejant-etiketler{display:flex;justify-content:space-between;font-size:11px;color:var(--sub2)}

/* Masaüstü dikey lejant kart */
#gp .lejant-dikey{background:var(--card2);border-radius:16px;padding:16px;border:1px solid var(--line)}
#gp .lejant-dikey-baslik{font-size:15.5px;font-weight:900;color:var(--ink);margin-bottom:14px}
#gp .lejant-dikey-ic{display:flex;gap:14px;height:156px}
#gp .lejant-dikey-bar{width:18px;border-radius:9px;flex:none;background:linear-gradient(to bottom,#EF4444,#F97316,#EAB308,#22C55E,#06B6D4,#1D4ED8)}
#gp .lejant-dikey-sat{display:flex;flex-direction:column;justify-content:space-between;flex:1}
#gp .lejant-dikey-row{display:flex;align-items:center;gap:10px;font-size:13.5px;font-weight:600;color:var(--ink)}
#gp .lejant-dikey-dot{width:13px;height:13px;border-radius:50%;flex:none}

/* Gözlemler kart */
#gp .gozlem-kart{background:var(--card2);border-radius:16px;padding:16px;border:1px solid var(--line)}
#gp .gozlem-baslik{font-size:15.5px;font-weight:900;color:var(--ink);margin-bottom:12px}
#gp .gozlem-satir{display:flex;align-items:center;gap:12px;padding:4px 0}
#gp .gozlem-satir+.gozlem-satir{border-top:1px solid var(--line);padding-top:10px;margin-top:6px}
#gp .gozlem-ikon-kap{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none}
#gp .gozlem-ikon-kap .material-icons-outlined{font-size:22px}
#gp .gozlem-metin{font-size:13.5px;font-weight:500;color:var(--ink);line-height:1.35;flex:1}

/* Yürüyüş panel */
#gp .yuruyus-kart{background:var(--card2);border-radius:16px;padding:16px;border:1px solid var(--line)}
#gp .yuruyus-baslik{font-size:15.5px;font-weight:900;color:var(--ink);margin-bottom:12px}
#gp .yuruyus-ic{display:flex;align-items:center;gap:14px}
#gp .yuruyus-ikon{width:46px;height:46px;border-radius:13px;background:color-mix(in srgb,#16A34A 14%,transparent);display:flex;align-items:center;justify-content:center;flex:none}
#gp .yuruyus-km{font-size:30px;font-weight:900;color:var(--ink);line-height:1}
#gp .yuruyus-birim{font-size:15px;font-weight:700;color:var(--sub);margin-left:4px}
#gp .yuruyus-alt{font-size:11.5px;color:var(--sub);margin-top:8px}

/* AI değerlendirme */
#gp .ai-kart{border-radius:14px;padding:14px;border:1px solid color-mix(in srgb,var(--mor1) 35%,transparent);
  background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 14%,transparent),color-mix(in srgb,var(--mor1) 5%,transparent))}
#gp .ai-ust{display:flex;align-items:center;gap:6px;margin-bottom:8px}
#gp .ai-ust .material-icons-outlined{font-size:17px;color:var(--mor1)}
#gp .ai-baslik{font-size:13.5px;font-weight:900;color:var(--ink);flex:1}
#gp .ai-verdict{padding:3px 9px;border-radius:10px;font-size:11.5px;font-weight:900}
#gp .ai-metin{font-size:12.5px;color:var(--sub2);line-height:1.4}

/* Masaüstü 2-kolon düzen */
#gp .isi-masaustu{display:none}
@media(min-width:900px){
  #gp .isi-masaustu{display:flex;gap:16px}
  #gp .isi-masaustu-sol{flex:6;min-width:0}
  #gp .isi-masaustu-sag{flex:4;display:flex;flex-direction:column;gap:14px}
  #gp .isi-telefon{display:none}
}

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// --- Veri hazırlığı ---
$periodlar = ['gunluk' => 'Günlük', 'haftalik' => 'Haftalık', 'aylik' => 'Aylık', 'yillik' => 'Yıllık'];
$period = in_array($period ?? '', array_keys($periodlar)) ? $period : 'gunluk';
$garsonlar = $garsonlar ?? [];
usort($garsonlar, fn ($a, $b) => (float) ($b['ciro'] ?? 0) <=> (float) ($a['ciro'] ?? 0));

// --- GarsonPerformans rapor (isiler dahil) ---
$sid = function_exists('_webSubeId') ? _webSubeId() : (int) (DB::table('subeler')->value('id') ?: 2);
$rapor = [];
try {
    $rapor = (new \App\Services\GarsonPerformans($sid))->rapor($period, null);
} catch (\Throwable $e) {}
$isiler = $rapor['isiler'] ?? [];
// "tum" ısı haritası
$isiTum = $isiler['tum'] ?? [];

// --- Salon şeması (ısı zemini) ---
$semaVeri = null;
$semaMasalar = [];
try {
    if (Schema::hasTable('salon_sema')) {
        $semaRow = DB::table('salon_sema')->where('sube_id', $sid)->first();
        if ($semaRow && $semaRow->veri) {
            $dec = json_decode($semaRow->veri, true);
            if (is_array($dec)) $semaVeri = $dec;
        }
    }
    $semaMasalar = DB::table('masalar')->where('sube_id', $sid)->get(['id', 'sekil', 'kapasite'])->keyBy('id')->all();
} catch (\Throwable $e) {}

// --- Yardımcı formatlar ---
$tl = fn ($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
$sayi = fn ($v) => number_format((int) $v, 0, ',', '.');

// --- Adım toplam (tüm garsonlar) ---
$adimToplam = array_sum(array_column($garsonlar, 'adim'));
$kmToplam = $adimToplam * 0.75 / 1000;
$kmStr = fn ($km) => $km >= 1 ? number_format($km, 1, ',', '.') : number_format($km, 2, ',', '.');

// --- Isı haritası verileri (tüm salon) ---
$isiMasalar = $isiTum['masalar'] ?? [];
$isiBolgeler = $isiTum['bolgeler'] ?? [];
$isiMaxA = (int) ($isiTum['max_agirlik'] ?? 1);
if ($isiMaxA <= 0) $isiMaxA = 1;
$veriVar = !empty($isiMasalar) && $isiMaxA > 0 && array_sum(array_column($isiMasalar, 'agirlik')) > 0;
$semaVar = !empty($semaVeri['masalar']);

// En yoğun masa
$enMasa = null;
$bolgeTop = [];
foreach ($isiMasalar as $m) {
    $bid = (int) ($m['bolge_id'] ?? 0);
    $a = (int) ($m['agirlik'] ?? 0);
    if ($enMasa === null || $a > (int) ($enMasa['agirlik'] ?? 0)) $enMasa = $m;
    $bolgeTop[$bid] = ($bolgeTop[$bid] ?? 0) + $a;
}
$enBolge = -1; $enBolgeVal = -1;
foreach ($bolgeTop as $bid => $v) { if ($v > $enBolgeVal) { $enBolgeVal = $v; $enBolge = $bid; } }
$bolgeAd = function ($id) use ($isiBolgeler) {
    foreach ($isiBolgeler as $b) { if ((int) $b->id === (int) $id) return $b->ad ?? ''; }
    return '';
};

// Sema nokta tipleri
$semaNoktatipleri = [];
foreach ($semaVeri['noktalar'] ?? [] as $nk) { if (!empty($nk['tip'])) $semaNoktatipleri[] = $nk['tip']; }
$semaNoktatipleri = array_unique($semaNoktatipleri);

// Sakin bölgeler
$sakinBolgeler = [];
foreach ($isiBolgeler as $b) {
    $bid = (int) $b->id;
    if (($bolgeTop[$bid] ?? 0) === 0 && !empty($b->ad)) $sakinBolgeler[] = $b->ad;
}

// Sicaklik rengi (0=soguk mavi, 1=sicak kirmizi) — JS'te de kullanılacak, PHP'te tile rengi için
$sicaklik = function (float $o): string {
    if ($o <= 0) return 'rgba(51,65,85,.25)';
    // mavi -> amber -> kirmizi
    if ($o < 0.5) {
        $t = $o / 0.5;
        $r = (int) (59 + (245 - 59) * $t);
        $g = (int) (130 + (158 - 130) * $t);
        $b2 = (int) (246 - 246 * $t + 11 * $t);
        return "rgb($r,$g,$b2)";
    }
    $t = ($o - 0.5) / 0.5;
    $r = (int) (245 + (239 - 245) * $t);
    $g = (int) (158 - 158 * $t);
    $b2 = (int) (11 - 11 * $t);
    return "rgb($r,$g,$b2)";
};

// Gözlemler (Flutter _gozlemler mantığı)
$gozlemRows = [];
if (in_array('giris', $semaNoktatipleri) && in_array('bar', $semaNoktatipleri)) {
    $gozlemRows[] = ['directions_walk', 'En çok giriş – masa – bar hattında hareket ediyor.', '#7C3AED'];
} elseif ($enBolge >= 0 && ($bolgeTop[$enBolge] ?? 0) > 0) {
    $gozlemRows[] = ['directions_walk', 'En çok ' . $bolgeAd($enBolge) . ' çevresinde hareket ediyor.', '#7C3AED'];
}
if ($enBolge >= 0 && ($bolgeTop[$enBolge] ?? 0) > 0) {
    $gozlemRows[] = ['restaurant', 'Servis sırasında en yoğun kullanılan alan: ' . $bolgeAd($enBolge) . '.', '#EA580C'];
}
if (!empty($enMasa)) {
    $gozlemRows[] = ['local_fire_department', 'En yoğun masa: ' . ($enMasa['ad'] ?? '') . ' (' . (int) ($enMasa['agirlik'] ?? 0) . ' işlem).', '#DC2626'];
}
if (in_array('mutfak', $semaNoktatipleri) && in_array('bar', $semaNoktatipleri)) {
    $gozlemRows[] = ['restaurant_menu', 'Mutfak ve bar arasında düzenli geçişler var.', '#0891B2'];
}
if (!empty($sakinBolgeler)) {
    $gozlemRows[] = ['chair_alt', implode(', ', array_slice($sakinBolgeler, 0, 2)) . ' daha az ziyaret ediliyor (köşe/arka bölgeler).', '#16A34A'];
}
if (empty($gozlemRows)) {
    $gozlemRows[] = ['info_outline', 'Bu dönemde yeterli hareket verisi yok. Dönemi genişlet (Aylık) ya da salon şemasını çiz.', '#64748B'];
}

// AI değerlendirmesi (tüm salon — tüm garsonlar toplamı üzerinden)
$aiAdim = $adimToplam;
$aiKm = $aiAdim * 0.75 / 1000;
$aiAdisyon = array_sum(array_column($garsonlar, 'adisyon'));
$aiCiro = array_sum(array_column($garsonlar, 'ciro'));
$aiSaatTop = array_sum(array_map(fn ($g) => (int) ($g['ort_servis_dk'] ?? 0), $garsonlar));
$aiServis = count($garsonlar) > 0 ? round($aiSaatTop / count($garsonlar)) : 0;
$aiKalem = array_sum(array_column($garsonlar, 'kalem'));
$aiPuan = 60;
$aiArti = []; $aiGelisim = [];
if ($aiKm >= 3) { $aiArti[] = 'sahada aktif (' . number_format($aiKm, 1, ',', '.') . ' km)'; $aiPuan += 10; }
elseif ($aiAdim > 0 && $aiKm < 1) { $aiGelisim[] = 'az hareket (' . number_format($aiKm, 1, ',', '.') . ' km); masalara daha sık uğramalı'; $aiPuan -= 10; }
if ($aiServis > 0 && $aiServis <= 7) { $aiArti[] = 'hızlı servis (ort. ' . $aiServis . ' dk)'; $aiPuan += 12; }
elseif ($aiServis >= 13) { $aiGelisim[] = 'servis süresi yüksek (ort. ' . $aiServis . ' dk)'; $aiPuan -= 12; }
if (!empty($sakinBolgeler)) { $aiGelisim[] = implode(', ', array_slice($sakinBolgeler, 0, 2)) . ' bölgesine az uğramış'; $aiPuan -= 8; }
elseif ($enBolge >= 0) { $aiArti[] = 'salonu dengeli dolaşmış'; $aiPuan += 5; }
if ($aiAdisyon >= 8) { $aiArti[] = $aiAdisyon . ' adisyon ile yüksek tempo'; $aiPuan += 8; }
if ($aiKalem > 0 && $aiAdisyon > 0 && $aiKalem / $aiAdisyon >= 4) { $aiArti[] = 'masa başına güçlü satış (ort. ' . number_format($aiKalem / $aiAdisyon, 1, ',', '.') . ' kalem)'; $aiPuan += 6; }
$aiPuan = max(0, min(100, $aiPuan));
$aiVerdict = $aiPuan >= 80 ? 'Çok iyi' : ($aiPuan >= 60 ? 'İyi' : ($aiPuan >= 45 ? 'Orta' : 'Geliştirilebilir'));
$aiVColor = $aiPuan >= 80 ? '#16A34A' : ($aiPuan >= 60 ? '#22C55E' : ($aiPuan >= 45 ? '#F59E0B' : '#EF4444'));
$aiMeyin = 'Ekip';
if ($aiAdisyon > 0) $aiMeyin .= ' bu dönem ' . $aiAdisyon . ' masaya baktı';
if ($aiCiro > 0) $aiMeyin .= ($aiAdisyon > 0 ? ', ' : ' ') . $tl($aiCiro) . ' ciro üretti';
$aiMeyin .= '. ';
if ($enBolge >= 0) $aiMeyin .= 'En çok ' . $bolgeAd($enBolge) . ' bölgesinde çalıştı. ';
if (!empty($aiArti)) $aiMeyin .= 'Güçlü yön: ' . implode(', ', array_slice($aiArti, 0, 3)) . '. ';
if (!empty($aiGelisim)) $aiMeyin .= 'Gelişim: ' . implode(', ', array_slice($aiGelisim, 0, 2)) . '.';
if (empty($aiArti) && empty($aiGelisim)) $aiMeyin .= 'Değerlendirme için yeterli veri yok.';
@endphp

<div id="gp">

{{-- ============ PERIOD SEÇİCİ ============ --}}
<div class="gp-periods">
  @foreach ($periodlar as $k => $ad)
    <a href="/garson-performans?period={{ $k }}" class="gp-chip {{ $period === $k ? 'akt' : '' }}">{{ $ad }}</a>
  @endforeach
</div>

{{-- ============ ISI HARİTASI BÖLÜMÜ ============ --}}
<div class="isi-kutu">
  {{-- MASAÜSTÜ: sol harita + sağ paneller --}}
  <div class="isi-masaustu">
    {{-- Sol: başlık + garson çipleri + canvas harita --}}
    <div class="isi-masaustu-sol">
      <div class="isi-baslik">
        <span>🔥</span>
        <span>Garsonun Isı Haritası</span>
      </div>
      <div class="isi-garsonlar" id="isi-garsonlar-m">
        <div class="isi-g-chip akt" data-id="" onclick="isiSec(null,this,'m')">Tüm salon</div>
        @foreach ($garsonlar as $g)
          <div class="isi-g-chip" data-id="{{ $g['id'] }}" onclick="isiSec({{ $g['id'] }},this,'m')">{{ $g['ad'] ?? '' }}</div>
        @endforeach
      </div>
      <div class="harita-kap" id="harita-kap">
        @if ($semaVar)
          <canvas id="isi-canvas" width="800" height="1080"></canvas>
        @elseif ($veriVar)
          {{-- Bölge ızgara fallback --}}
          @foreach ($isiBolgeler as $b)
            @php
              $bid = (int) $b->id;
              $bMasalar = array_filter($isiMasalar, fn ($m) => (int) ($m['bolge_id'] ?? 0) === $bid);
              usort($bMasalar, fn ($x, $y) => (int) ($y['agirlik'] ?? 0) <=> (int) ($x['agirlik'] ?? 0));
            @endphp
            @if (!empty($bMasalar))
              <div class="bolge-blok">
                <div class="bolge-baslik">
                  {{ $b->ad ?? '' }}
                  @if (($bolgeTop[$bid] ?? 0) > 0)
                    <span class="bolge-rozet">{{ $bolgeTop[$bid] }} işlem</span>
                  @endif
                </div>
                <div class="masa-izgara" id="izgara-{{ $bid }}">
                  @foreach ($bMasalar as $m)
                    @php
                      $a = (int) ($m['agirlik'] ?? 0);
                      $o = $isiMaxA > 0 ? $a / $isiMaxA : 0;
                      $bg = $sicaklik((float) $o);
                      $yazRenk = $a > 0 ? '#fff' : 'var(--ink)';
                      $border = $a > 0 ? 'border:none' : '';
                      $shadow = $o > 0.55 ? 'box-shadow:0 0 10px ' . $bg . '80' : '';
                    @endphp
                    <div class="masa-tile" data-masa-id="{{ $m['id'] }}"
                         style="background:{{ $bg }};color:{{ $yazRenk }};{{ $border }};{{ $shadow }}">
                      <div style="font-size:12px;font-weight:900;text-overflow:ellipsis;overflow:hidden;max-width:66px;white-space:nowrap">{{ $m['ad'] ?? '' }}</div>
                      @if ($a > 0)
                        <div class="islem">{{ $a }} işlem</div>
                      @endif
                    </div>
                  @endforeach
                </div>
              </div>
            @endif
          @endforeach
        @else
          <div style="padding:24px;text-align:center;font-size:12.5px;color:var(--sub);line-height:1.4">
            Salon şemasını "Salon Şeması" ekranından çizip kaydedersen, ısı haritası buraya salon planının üzerinde gelir.
          </div>
        @endif
      </div>{{-- harita-kap --}}
    </div>{{-- sol --}}

    {{-- Sağ: lejant + gözlemler + yürüyüş + AI --}}
    <div class="isi-masaustu-sag">
      {{-- Lejant dikey --}}
      <div class="lejant-dikey">
        <div class="lejant-dikey-baslik">Isı Haritası Renkleri</div>
        <div class="lejant-dikey-ic">
          <div class="lejant-dikey-bar"></div>
          <div class="lejant-dikey-sat">
            <div class="lejant-dikey-row">
              <div class="lejant-dikey-dot" style="background:#EF4444"></div>
              En çok yürüdüğü alanlar
            </div>
            <div class="lejant-dikey-row">
              <div class="lejant-dikey-dot" style="background:#22C55E"></div>
              Sık yürüdüğü alanlar
            </div>
            <div class="lejant-dikey-row">
              <div class="lejant-dikey-dot" style="background:#1D4ED8"></div>
              En az yürüdüğü alanlar
            </div>
          </div>
        </div>
      </div>
      {{-- Gözlemler --}}
      <div class="gozlem-kart">
        <div class="gozlem-baslik">Gözlemler</div>
        @foreach ($gozlemRows as $gr)
          <div class="gozlem-satir">
            <div class="gozlem-ikon-kap" style="background:{{ $gr[2] }}22">
              <span class="material-icons-outlined" style="color:{{ $gr[2] }}">{{ $gr[0] }}</span>
            </div>
            <div class="gozlem-metin">{{ $gr[1] }}</div>
          </div>
        @endforeach
      </div>
      {{-- Yürüyüş --}}
      <div class="yuruyus-kart">
        <div class="yuruyus-baslik">Toplam Yürüyüş Mesafesi</div>
        <div class="yuruyus-ic">
          <div class="yuruyus-ikon">
            <span class="material-icons-outlined" style="color:#16A34A;font-size:25px">route</span>
          </div>
          <div>
            <span class="yuruyus-km" id="yw-km">{{ $kmStr($kmToplam) }}</span>
            <span class="yuruyus-birim">km</span>
          </div>
        </div>
        <div class="yuruyus-alt" id="yw-adim">{{ $sayi($adimToplam) }} adım · tüm garsonlar</div>
      </div>
      {{-- AI --}}
      @if ($veriVar || $aiAdisyon > 0)
      <div class="ai-kart">
        <div class="ai-ust">
          <span class="material-icons-outlined">auto_awesome</span>
          <span class="ai-baslik">AI Değerlendirmesi</span>
          <span class="ai-verdict" style="background:{{ $aiVColor }}28;color:{{ $aiVColor }};border:1px solid {{ $aiVColor }}80">{{ $aiVerdict }} · {{ $aiPuan }}</span>
        </div>
        <div class="ai-metin" id="ai-metin-m">{{ $aiMeyin }}</div>
      </div>
      @endif
    </div>{{-- sag --}}
  </div>{{-- isi-masaustu --}}

  {{-- TELEFON: tek kolon --}}
  <div class="isi-telefon">
    <div class="isi-baslik">
      <span>🔥</span>
      <span>Garsonun Isı Haritası</span>
    </div>
    <div class="isi-garsonlar" id="isi-garsonlar-t">
      <div class="isi-g-chip akt" data-id="" onclick="isiSec(null,this,'t')">Tüm salon</div>
      @foreach ($garsonlar as $g)
        <div class="isi-g-chip" data-id="{{ $g['id'] }}" onclick="isiSec({{ $g['id'] }},this,'t')">{{ $g['ad'] ?? '' }}</div>
      @endforeach
    </div>
    {{-- Adım istatistikleri (telefon, Flutter _adimMesafeKart) --}}
    <div class="adim-row" id="adim-row-t">
      <div class="adim-stat">
        <div class="adim-ikon" style="background:color-mix(in srgb,var(--mor1) 14%,transparent)">
          <span class="material-icons-outlined" style="color:var(--mor1);font-size:18px">directions_walk</span>
        </div>
        <div>
          <span class="adim-deger" id="yt-adim">{{ $sayi($adimToplam) }}</span>
          <span class="adim-birim">adım</span>
        </div>
      </div>
      <div class="adim-stat">
        <div class="adim-ikon" style="background:color-mix(in srgb,#16A34A 14%,transparent)">
          <span class="material-icons-outlined" style="color:#16A34A;font-size:18px">route</span>
        </div>
        <div>
          <span class="adim-deger" id="yt-km">{{ $kmStr($kmToplam) }}</span>
          <span class="adim-birim">km</span>
        </div>
      </div>
    </div>
    {{-- Harita (canvas veya izgara, telefon) --}}
    @if ($semaVar)
      <div class="harita-kap"><canvas id="isi-canvas-t" width="800" height="1080"></canvas></div>
    @elseif ($veriVar)
      <div id="harita-izgara-t">
        @foreach ($isiBolgeler as $b)
          @php
            $bid = (int) $b->id;
            $bMasalar = array_filter($isiMasalar, fn ($m) => (int) ($m['bolge_id'] ?? 0) === $bid);
            usort($bMasalar, fn ($x, $y) => (int) ($y['agirlik'] ?? 0) <=> (int) ($x['agirlik'] ?? 0));
          @endphp
          @if (!empty($bMasalar))
            <div class="bolge-blok">
              <div class="bolge-baslik">
                {{ $b->ad ?? '' }}
                @if (($bolgeTop[$bid] ?? 0) > 0)
                  <span class="bolge-rozet">{{ $bolgeTop[$bid] }} işlem</span>
                @endif
              </div>
              <div class="masa-izgara">
                @foreach ($bMasalar as $m)
                  @php
                    $a = (int) ($m['agirlik'] ?? 0);
                    $o = $isiMaxA > 0 ? $a / $isiMaxA : 0;
                    $bg = $sicaklik((float) $o);
                    $yazRenk = $a > 0 ? '#fff' : 'var(--ink)';
                  @endphp
                  <div class="masa-tile" style="background:{{ $bg }};color:{{ $yazRenk }};{{ $a > 0 ? 'border:none' : '' }}">
                    <div style="font-size:12px;font-weight:900;text-overflow:ellipsis;overflow:hidden;max-width:66px;white-space:nowrap">{{ $m['ad'] ?? '' }}</div>
                    @if ($a > 0)
                      <div class="islem">{{ $a }} işlem</div>
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          @endif
        @endforeach
      </div>
    @else
      <div style="padding:16px 0;text-align:center;font-size:12.5px;color:var(--sub);line-height:1.4">
        Salon şemasını "Salon Şeması" ekranından çizip kaydedersen, ısı haritası buraya salon planının üzerinde gelir.
      </div>
    @endif
    {{-- Lejant (telefon, yatay) --}}
    @if ($semaVar || $veriVar)
      <div class="lejant-bar">
        <div class="lejant-baslik">Isı Haritası Renkleri</div>
        <div class="lejant-gradient"></div>
        <div class="lejant-etiketler">
          <span>En çok yürüdüğü</span>
          <span>Sık</span>
          <span>En az yürüdüğü</span>
        </div>
      </div>
    @endif
    {{-- Gözlemler (telefon) --}}
    @if ($veriVar)
      <div class="gozlem-kart" style="margin-top:12px">
        <div class="gozlem-baslik">Gözlemler</div>
        @foreach ($gozlemRows as $gr)
          <div class="gozlem-satir">
            <div class="gozlem-ikon-kap" style="background:{{ $gr[2] }}22">
              <span class="material-icons-outlined" style="color:{{ $gr[2] }}">{{ $gr[0] }}</span>
            </div>
            <div class="gozlem-metin">{{ $gr[1] }}</div>
          </div>
        @endforeach
      </div>
      <div class="ai-kart" style="margin-top:12px">
        <div class="ai-ust">
          <span class="material-icons-outlined">auto_awesome</span>
          <span class="ai-baslik">AI Değerlendirmesi</span>
          <span class="ai-verdict" style="background:{{ $aiVColor }}28;color:{{ $aiVColor }};border:1px solid {{ $aiVColor }}80">{{ $aiVerdict }} · {{ $aiPuan }}</span>
        </div>
        <div class="ai-metin" id="ai-metin-t">{{ $aiMeyin }}</div>
      </div>
    @endif
  </div>{{-- isi-telefon --}}
</div>{{-- isi-kutu --}}

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

{{-- ============ JAVASCRIPT: ISI HAR İTASI CANVAS + garson filtre ============ --}}
<script>
(function () {
  'use strict';

  /* ---------- ham veriler (PHP'ten) ---------- */
  const ISILER = {!! json_encode($isiler ?: ['tum' => []], 15, 512) !!};
  const SEMA   = {!! json_encode($semaVeri ?: null, 15, 512) !!};
  const SEKIL  = {!! json_encode(collect($semaMasalar)->mapWithKeys(fn($m, $id) => [$id => $m->sekil ?? 'kare'])->all(), 15, 512) !!};
  const KAPASITE = {!! json_encode(collect($semaMasalar)->mapWithKeys(fn($m, $id) => [$id => max(1, min(12, (int) ($m->kapasite ?? 4)))])->all(), 15, 512) !!};
  const GARSONLAR = @json(array_values($garsonlar));
  let seciliId = null; // null = tüm salon

  /* ---------- JS sicaklik rengi (Flutter _sicaklik birebir) ---------- */
  function sicaklik(o) {
    if (o <= 0) return 'rgba(51,65,85,.25)';
    if (o < 0.5) return lerpRGB([59,130,246], [245,158,11], o / 0.5);
    return lerpRGB([245,158,11], [239,68,68], (o - 0.5) / 0.5);
  }
  function jet5(o) {
    o = Math.max(0, Math.min(1, o));
    const stops = [0, 0.3, 0.5, 0.7, 0.85, 1.0];
    const cols = [[29,78,216],[6,182,212],[34,197,94],[234,179,8],[249,115,22],[239,68,68]];
    for (let i = 0; i < stops.length - 1; i++) {
      if (o <= stops[i+1]) return lerpRGB(cols[i], cols[i+1], (o - stops[i]) / (stops[i+1] - stops[i]));
    }
    return 'rgb(239,68,68)';
  }
  function lerpRGB(a, b, t) {
    return 'rgb(' + Math.round(a[0]+(b[0]-a[0])*t) + ',' + Math.round(a[1]+(b[1]-a[1])*t) + ',' + Math.round(a[2]+(b[2]-a[2])*t) + ')';
  }
  function lerpRGBA(a, b, t, alpha) {
    return 'rgba(' + Math.round(a[0]+(b[0]-a[0])*t) + ',' + Math.round(a[1]+(b[1]-a[1])*t) + ',' + Math.round(a[2]+(b[2]-a[2])*t) + ',' + alpha + ')';
  }
  function hexToRgb(hex) {
    const r = parseInt(hex.slice(1,3),16), g = parseInt(hex.slice(3,5),16), b = parseInt(hex.slice(5,7),16);
    return [r,g,b];
  }

  /* ---------- SEMA GÖRSEL KÜTÜPHANESİ (Flutter assets/sema → /sema) ---------- */
  const MASA_OLCEK = 0.9; // Flutter _masaOlcek
  const NOKTA_K = 2.3;    // mutfak/bar büyütme çarpanı (sx orana göre + bu) — uygulamayla senkron
  const SEMA_IMG = {};
  const _sonIsi = {}; // canvasId -> son çizilen ısı verisi (görsel async yüklenince yeniden çiz)
  ['masa_yuvarlak','masa_yuvarlak_2','masa_kare','masa_kare_2','mutfak','bar','giris'].forEach(ad => {
    const im = new Image();
    im.onload = () => { SEMA_IMG[ad] = im; Object.keys(_sonIsi).forEach(id => cizCanvas(id, _sonIsi[id])); };
    im.src = '/sema/' + ad + '.png';
  });

  /* ---------- boyalarCanvas (Flutter _SemaPainter portu) ---------- */
  function cizSema(canvas, isiVeri, koyu) {
    if (!canvas || !SEMA) return;
    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;
    ctx.clearRect(0,0,W,H);

    const masaYerMap = SEMA.masalar || {};
    const parselPts = [];
    (SEMA.parsel || []).forEach(pr => {
      if ((pr.kat || 0) === 0 && Array.isArray(pr.pts)) {
        pr.pts.forEach(p => parselPts.push([+p[0], +p[1]]));
      }
    });
    const zones = (SEMA.bolgeler || []).filter(b => (b.kat||0) === 0);
    const noktalar = (SEMA.noktalar || []).filter(n => (n.kat||0) === 0);

    function sx(v) { return v / 1000 * W; }
    function sy(v) { return v / 1000 * H; }

    // agirlik haritasi
    const agirMap = {};
    const adMap = {};
    ((isiVeri && isiVeri.masalar) || []).forEach(m => {
      agirMap[String(m.id)] = +m.agirlik || 0;
      adMap[String(m.id)] = m.ad || '';
    });
    const maxA = +((isiVeri && isiVeri.max_agirlik) || 1) || 1;

    // parsel path
    const zemin = new Path2D();
    if (parselPts.length >= 3) {
      zemin.moveTo(sx(parselPts[0][0]), sy(parselPts[0][1]));
      for (let i = 1; i < parselPts.length; i++) zemin.lineTo(sx(parselPts[i][0]), sy(parselPts[i][1]));
      zemin.closePath();
    } else {
      zemin.rect(0, 0, W, H);
    }

    // 1. dış arka plan (salon dışı)
    ctx.fillStyle = koyu ? '#0B1020' : '#CBD5E1';
    ctx.fillRect(0, 0, W, H);

    // 2. zemin (ahşap parke fallback)
    ctx.save(); ctx.clip(zemin);
    const zg = ctx.createLinearGradient(0,0,W,H);
    zg.addColorStop(0, '#E4CBA0'); zg.addColorStop(1, '#D3B183');
    ctx.fillStyle = zg; ctx.fillRect(0,0,W,H);
    // parke çizgileri
    ctx.strokeStyle = 'rgba(156,123,84,.22)'; ctx.lineWidth = 1.2;
    const plankH = W / 16;
    for (let y = 0; y < H; y += plankH) ctx.strokeRect(0, y, W, 0);
    const segW = W / 8;
    for (let y = 0; y < H; y += plankH) {
      const off = (Math.floor(y / plankH) % 2) * segW / 2;
      for (let x = off; x < W; x += segW) {
        ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x, y + plankH); ctx.stroke();
      }
    }
    ctx.restore();

    // 3. masalar — ısı ağı önce
    const tablolar = [];
    Object.entries(masaYerMap).forEach(([id, yer]) => {
      if ((yer.kat||0) !== 0) return;
      const a = agirMap[id] || 0;
      const o = maxA > 0 ? a / maxA : 0;
      const boyV = +yer.boy > 0 ? +yer.boy : 150;
      const size = Math.max(32, Math.min(88, sx(boyV)));
      const yuvarlak = (SEKIL[id] || 'kare') === 'yuvarlak';
      tablolar.push({ id, cx: sx(+yer.x), cy: sy(+yer.y), size, o, renk: sicaklik(o), yuvarlak, ad: adMap[id] || '', kapasite: +(KAPASITE[id] || 4) });
    });

    // ısı ağı (Flutter _isiAgCiz)
    ctx.save(); ctx.clip(zemin);
    ctx.fillStyle = 'rgba(30,64,175,.26)'; ctx.fillRect(0,0,W,H); // soğuk taban

    let avgSize = 0;
    tablolar.forEach(m => avgSize += m.size);
    if (tablolar.length > 0) avgSize /= tablolar.length;
    if (avgSize <= 0) avgSize = 40;

    const rota = (isiVeri && isiVeri.rota) || [];
    const byId = {}; tablolar.forEach(m => byId[m.id] = m);
    const segs = [];

    if (rota.length > 0) {
      rota.forEach(r => {
        const a = byId[String(r.a)], b = byId[String(r.b)];
        if (!a || !b) return;
        const ab = +r.ab, ba = +r.ba, w = ab + ba;
        if (w <= 0) return;
        const ileri = ab >= ba;
        segs.push([a.cx, a.cy, b.cx, b.cy, w, ileri ? 0 : 1, ileri ? 1 : 0]);
      });
    }
    if (segs.length === 0) {
      // sentetik komşuluk
      const maxGap = avgSize * 2.7;
      for (let i = 0; i < tablolar.length; i++) {
        for (let j = i+1; j < tablolar.length; j++) {
          const ai = tablolar[i], bi = tablolar[j];
          const dx = ai.cx - bi.cx, dy = ai.cy - bi.cy;
          const dist = Math.sqrt(dx*dx+dy*dy);
          if (dist > maxGap) continue;
          if (Math.abs(dx) > avgSize * 0.8 && Math.abs(dy) > avgSize * 0.8) continue;
          segs.push([ai.cx, ai.cy, bi.cx, bi.cy, (ai.o+bi.o)/2, ai.o, bi.o]);
        }
      }
      noktalar.forEach(nk => {
        if (!['giris','mutfak','bar'].includes(nk.tip)) return;
        const sirali = [...tablolar].sort((a,b) => {
          const da = Math.hypot(a.cx-sx(+nk.x), a.cy-sy(+nk.y));
          const db = Math.hypot(b.cx-sx(+nk.x), b.cy-sy(+nk.y));
          return da - db;
        });
        for (let k = 0; k < Math.min(2, sirali.length); k++) {
          segs.push([sx(+nk.x), sy(+nk.y), sirali[k].cx, sirali[k].cy, 0.6+0.4*sirali[k].o, 0, sirali[k].o]);
        }
      });
    }
    if (segs.length > 0) {
      let maxW = 0; segs.forEach(s => { if (s[4] > maxW) maxW = s[4]; }); if (maxW <= 0) maxW = 1;
      segs.sort((a,b) => a[4]-b[4]);
      const sw = Math.max(12, Math.min(48, avgSize * 0.62));
      segs.forEach(s => {
        const w = Math.pow(Math.max(0, Math.min(1, s[4]/maxW)), 0.7);
        ctx.strokeStyle = jet5(w);
        ctx.lineWidth = sw;
        ctx.lineCap = 'round';
        ctx.shadowBlur = sw * 0.42;
        ctx.shadowColor = jet5(w);
        ctx.globalAlpha = 0.96;
        ctx.beginPath(); ctx.moveTo(s[0],s[1]); ctx.lineTo(s[2],s[3]); ctx.stroke();
      });
      ctx.shadowBlur = 0; ctx.globalAlpha = 1;
      // ısı hale
      let maxO = 0; tablolar.forEach(m => { if (m.o > maxO) maxO = m.o; }); if (maxO <= 0) maxO = 1;
      tablolar.forEach(m => {
        if (m.o < 0.1) return;
        const w = Math.pow(Math.max(0,Math.min(1, m.o/maxO)), 0.7);
        const rad = avgSize * (0.8 + (m.o/maxO) * 1.0);
        const grad = ctx.createRadialGradient(m.cx, m.cy, 0, m.cx, m.cy, rad);
        const col = jet5(w);
        grad.addColorStop(0, col.replace('rgb','rgba').replace(')',',0.72)'));
        grad.addColorStop(1, col.replace('rgb','rgba').replace(')',',0)'));
        ctx.fillStyle = grad; ctx.beginPath(); ctx.arc(m.cx, m.cy, rad, 0, Math.PI*2); ctx.fill();
      });
    }
    ctx.restore();

    // 4. duvar
    if (parselPts.length >= 3) {
      ctx.save(); ctx.lineJoin = 'round';
      ctx.strokeStyle = '#16181D'; ctx.lineWidth = 16; ctx.stroke(zemin);
      ctx.strokeStyle = '#34383F'; ctx.lineWidth = 5; ctx.stroke(zemin);
      ctx.strokeStyle = 'rgba(0,0,0,.45)'; ctx.lineWidth = 1.4; ctx.stroke(zemin);
      ctx.restore();
    }

    // 5. çalı / kenar süsleme (Flutter _kenarSuslemesi — basitleştirilmiş: koyu yeşil şerit)
    if (parselPts.length >= 3) {
      const cx2 = parselPts.reduce((s,p) => s+p[0], 0) / parselPts.length;
      const cy2 = parselPts.reduce((s,p) => s+p[1], 0) / parselPts.length;
      for (let i = 0; i < parselPts.length; i++) {
        const a2 = parselPts[i], b2 = parselPts[(i+1)%parselPts.length];
        const lx = sx(b2[0])-sx(a2[0]), ly = sy(b2[1])-sy(a2[1]);
        const L = Math.sqrt(lx*lx+ly*ly); if (L < 6) continue;
        const dx2 = lx/L, dy2 = ly/L;
        let nx = -dy2, ny = dx2;
        const midx = (sx(a2[0])+sx(b2[0]))/2, midy = (sy(a2[1])+sy(b2[1]))/2;
        if ((sx(cx2)-midx)*nx + (sy(cy2)-midy)*ny < 0) { nx=-nx; ny=-ny; }
        const off2 = 11;
        const a2x = sx(a2[0])+dx2*7+nx*off2, a2y = sy(a2[1])+dy2*7+ny*off2;
        const b2x = sx(b2[0])-dx2*7+nx*off2, b2y = sy(b2[1])-dy2*7+ny*off2;
        ctx.strokeStyle = '#0E3D22'; ctx.lineWidth = 16; ctx.lineCap = 'round';
        ctx.beginPath(); ctx.moveTo(a2x,a2y); ctx.lineTo(b2x,b2y); ctx.stroke();
        ctx.strokeStyle = '#1B5E20'; ctx.lineWidth = 11;
        ctx.beginPath(); ctx.moveTo(a2x,a2y); ctx.lineTo(b2x,b2y); ctx.stroke();
        // yaprak dots
        const adet = Math.max(1, Math.min(80, Math.floor(L/13)));
        for (let k = 0; k <= adet; k++) {
          const t2 = k / (adet||1);
          const px2 = a2x + (b2x-a2x)*t2 + nx*(Math.sin(k*1.9)*off2*0.3);
          const py2 = a2y + (b2y-a2y)*t2 + ny*(Math.sin(k*1.9)*off2*0.3);
          const r2 = 4.5 + (k%3);
          ctx.fillStyle = '#14532D'; ctx.beginPath(); ctx.arc(px2,py2,r2,0,Math.PI*2); ctx.fill();
          ctx.fillStyle = '#2E7D32'; ctx.beginPath(); ctx.arc(px2-1.6,py2-1,r2*.55,0,Math.PI*2); ctx.fill();
        }
      }
    }

    // 6. girişlerde kapı aralığı (clear)
    noktalar.forEach(nk => {
      if (nk.tip !== 'giris') return;
      const s2 = (+nk.boy > 0 ? +nk.boy : 66) * 1.15;
      const cx3 = sx(+nk.x), cy3 = sy(+nk.y);
      ctx.save(); ctx.globalCompositeOperation = 'destination-out';
      ctx.beginPath(); ctx.arc(cx3, cy3, s2/2, 0, Math.PI*2); ctx.fill();
      ctx.restore();
      // zemin tekrar çiz kapı aralığında
      ctx.save(); ctx.beginPath(); ctx.arc(cx3, cy3, s2/2, 0, Math.PI*2); ctx.clip();
      ctx.fillStyle = zg || '#D3B183'; ctx.fillRect(0,0,W,H);
      ctx.restore();
    });

    // 7. masalar — GERÇEK combo görsel (masa+sandalye tek PNG); yoksa vektör fallback
    tablolar.forEach(m => {
      const s = m.size;
      const combo = m.yuvarlak
        ? (m.kapasite <= 2 ? (SEMA_IMG['masa_yuvarlak_2'] || SEMA_IMG['masa_yuvarlak']) : SEMA_IMG['masa_yuvarlak'])
        : (m.kapasite <= 2 ? (SEMA_IMG['masa_kare_2'] || SEMA_IMG['masa_kare']) : SEMA_IMG['masa_kare']);
      if (combo && combo.width) {
        const w = s * 1.3 * MASA_OLCEK;
        const h = w * combo.height / combo.width; // en-boy oranını koru
        ctx.save();
        ctx.shadowBlur = 6; ctx.shadowColor = 'rgba(0,0,0,.22)'; ctx.shadowOffsetY = 3;
        ctx.drawImage(combo, m.cx - w/2, m.cy - h/2, w, h);
        ctx.restore();
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        return; // combo PNG sandalyeyi içeriyor -> vektör çizme
      }
      // Sandalyeler
      const n = Math.max(1, Math.min(12, m.kapasite));
      const g2 = s * 0.13, chH = Math.max(6, Math.min(24, s * 0.26));
      if (m.yuvarlak) {
        const rr = s/2 + g2 + chH/2;
        const cw2 = Math.max(8, Math.min(s*0.6, (2*Math.PI*rr/n) * 0.7));
        for (let i = 0; i < n; i++) {
          const ang = (2*Math.PI*i/n) - Math.PI/2;
          drawChair(ctx, m.cx + Math.cos(ang)*rr, m.cy + Math.sin(ang)*rr, cw2, chH, ang + Math.PI/2);
        }
      } else {
        const d = distribute(n);
        [[0,2],[2,0]].forEach(([side,_]) => { // üst/alt
          const cnt = d[side]; if (!cnt) return;
          const cw2 = Math.max(7, Math.min(s*0.6, s/cnt*0.82));
          const y2 = side===0 ? m.cy-s/2-g2-chH/2 : m.cy+s/2+g2+chH/2;
          for (let k = 0; k < cnt; k++) {
            drawChair(ctx, m.cx-s/2+s/cnt*(k+0.5), y2, cw2, chH, side===0?0:Math.PI);
          }
        });
        [[1,0],[3,0]].forEach(([side,_]) => { // sağ/sol
          const cnt = d[side]; if (!cnt) return;
          const cw2 = Math.max(7, Math.min(s*0.6, s/cnt*0.82));
          const x2 = side===1 ? m.cx+s/2+g2+chH/2 : m.cx-s/2-g2-chH/2;
          for (let k = 0; k < cnt; k++) {
            drawChair(ctx, x2, m.cy-s/2+s/cnt*(k+0.5), cw2, chH, side===1?Math.PI/2:-Math.PI/2);
          }
        });
      }
      // Masa yüzeyi (vektör ahşap)
      const woodGrad = ctx.createRadialGradient(m.cx,m.cy,0, m.cx,m.cy,s/2);
      woodGrad.addColorStop(0,'#C08A54'); woodGrad.addColorStop(0.7,'#7A5230'); woodGrad.addColorStop(1,'#5B3D26');
      // gölge
      ctx.shadowBlur = 5; ctx.shadowColor = 'rgba(0,0,0,.28)'; ctx.shadowOffsetY = 3;
      if (m.yuvarlak) {
        ctx.beginPath(); ctx.arc(m.cx, m.cy, s/2, 0, Math.PI*2);
        ctx.fillStyle = woodGrad; ctx.fill();
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        ctx.strokeStyle = '#3D2817'; ctx.lineWidth = 1.5; ctx.stroke();
      } else {
        roundRect(ctx, m.cx-s/2, m.cy-s/2, s, s, 8);
        ctx.fillStyle = woodGrad; ctx.fill();
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
        ctx.strokeStyle = '#3D2817'; ctx.lineWidth = 1.5; ctx.stroke();
      }
      ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
    });

    // 7.5 servis noktaları — GERÇEK fotoğraf (mutfak/bar/giriş), Flutter _semaNokta
    noktalar.forEach(nk => {
      const img = SEMA_IMG[nk.tip];
      const cx3 = sx(+nk.x), cy3 = sy(+nk.y);
      // Senkron: masalar gibi orana göre (sx) + büyütme çarpanı. NOKTA_K ile Flutter'la birebir boyut.
      const box = Math.max(40, sx(+nk.boy > 0 ? +nk.boy : 66) * NOKTA_K);
      if (img && img.width) {
        const h3 = box * img.height / img.width;
        ctx.save();
        ctx.shadowBlur = 6; ctx.shadowColor = 'rgba(0,0,0,.30)'; ctx.shadowOffsetY = 2;
        ctx.drawImage(img, cx3 - box/2, cy3 - h3/2, box, h3);
        ctx.restore();
        ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
      } else {
        ctx.fillStyle = 'rgba(100,116,139,.92)';
        roundRect(ctx, cx3 - box/2, cy3 - box/2, box, box, 9); ctx.fill();
      }
      if (nk.ad) {
        ctx.font = 'bold 9px sans-serif'; ctx.fillStyle = koyu ? '#e2e8f0' : '#1e293b';
        ctx.textAlign = 'center';
        ctx.fillText(nk.ad, cx3, cy3 + box/2 + 11);
        ctx.textAlign = 'start';
      }
    });

    // 8. yön okları (Flutter _oklariCiz)
    const oklar = [];
    if (segs.length > 0) {
      const ust = [...segs].sort((a,b) => b[4]-a[4]);
      for (let i = 0; i < ust.length && oklar.length < 7; i++) {
        const s2 = ust[i];
        const dx3 = s2[2]-s2[0], dy3 = s2[3]-s2[1];
        const dist2 = Math.sqrt(dx3*dx3+dy3*dy3);
        if (dist2 < avgSize * 0.7) continue;
        if (s2[6] >= s2[5]) oklar.push([s2[0],s2[1],s2[2],s2[3]]);
        else oklar.push([s2[2],s2[3],s2[0],s2[1]]);
      }
    }
    oklar.forEach(o2 => {
      const dx3 = o2[2]-o2[0], dy3 = o2[3]-o2[1];
      const total = Math.sqrt(dx3*dx3+dy3*dy3); if (total < 14) return;
      const ddx = dx3/total, ddy = dy3/total;
      let tipX = o2[0]+ddx*(total*0.58), tipY = o2[1]+ddy*(total*0.58);
      // giriş yakınsa kaçın
      noktalar.forEach(nk => {
        if (nk.tip !== 'giris') return;
        const s3 = +nk.boy > 0 ? +nk.boy : 66;
        const dist3 = Math.hypot(tipX-sx(+nk.x), tipY-sy(+nk.y));
        if (dist3 < s3) { tipX = sx(+nk.x); tipY = sy(+nk.y) - s3*0.95; }
      });
      drawArrow(ctx, tipX, tipY, ddx, ddy);
    });

    // 9. bölge etiketleri
    zones.forEach(b => {
      ctx.font = 'bold 10.5px sans-serif'; ctx.fillStyle = '#7DD3FC';
      ctx.fillText(b.ad || '', sx(+b.x)+6, sy(+b.y)+15);
    });

    // 10. masa adları üst (beyaz)
    tablolar.forEach(m => {
      if (!m.ad) return;
      ctx.font = 'bold 11px sans-serif'; ctx.fillStyle = '#fff';
      ctx.shadowBlur = 2; ctx.shadowColor = 'rgba(0,0,0,.54)';
      ctx.textAlign = 'center';
      ctx.fillText(m.ad, m.cx, m.cy + 4);
      ctx.shadowBlur = 0;
    });
    ctx.textAlign = 'start';
  }

  function drawChair(ctx, cx, cy, cw, ch, angle) {
    ctx.save();
    ctx.translate(cx, cy); ctx.rotate(angle);
    ctx.shadowBlur = 2; ctx.shadowColor = 'rgba(0,0,0,.22)'; ctx.shadowOffsetY = 2;
    const cg = ctx.createLinearGradient(0,-ch/2,0,ch/2);
    cg.addColorStop(0,'#6B4A30'); cg.addColorStop(1,'#4A3220');
    roundRect(ctx, -cw/2, -ch/2, cw, ch, 4);
    ctx.fillStyle = cg; ctx.fill();
    ctx.shadowBlur=0; ctx.shadowOffsetY=0;
    ctx.restore();
  }

  function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x+r,y); ctx.lineTo(x+w-r,y); ctx.arcTo(x+w,y,x+w,y+r,r);
    ctx.lineTo(x+w,y+h-r); ctx.arcTo(x+w,y+h,x+w-r,y+h,r);
    ctx.lineTo(x+r,y+h); ctx.arcTo(x,y+h,x,y+h-r,r);
    ctx.lineTo(x,y+r); ctx.arcTo(x,y,x+r,y,r);
    ctx.closePath();
  }

  function distribute(n) {
    const b = Math.floor(n/4), rem = n%4;
    const s2 = [b,b,b,b];
    [0,2,1,3].forEach((idx,i) => { if (i < rem) s2[idx]++; });
    return s2;
  }

  function drawArrow(ctx, tipX, tipY, ddx, ddy) {
    const perpX = -ddy, perpY = ddx;
    const headLen = 12, halfW = 7, tail = 15;
    const tsX = tipX-ddx*(tail+headLen), tsY = tipY-ddy*(tail+headLen);
    const teX = tipX-ddx*headLen, teY = tipY-ddy*headLen;
    // gölge
    ctx.strokeStyle='rgba(0,0,0,.45)'; ctx.lineWidth=4.8; ctx.lineCap='round';
    ctx.shadowBlur=2; ctx.shadowColor='rgba(0,0,0,.3)';
    ctx.beginPath(); ctx.moveTo(tsX,tsY); ctx.lineTo(teX,teY); ctx.stroke();
    ctx.shadowBlur=0;
    // beyaz gövde
    ctx.strokeStyle='rgba(255,255,255,.96)'; ctx.lineWidth=2.6;
    ctx.beginPath(); ctx.moveTo(tsX,tsY); ctx.lineTo(teX,teY); ctx.stroke();
    // ok başı
    const baseX = tipX-ddx*headLen, baseY = tipY-ddy*headLen;
    ctx.fillStyle='rgba(255,255,255,.96)';
    ctx.beginPath();
    ctx.moveTo(tipX,tipY);
    ctx.lineTo(baseX+perpX*halfW, baseY+perpY*halfW);
    ctx.lineTo(baseX-perpX*halfW, baseY-perpY*halfW);
    ctx.closePath(); ctx.fill();
  }

  /* ---------- canvas boyutlandır ve çiz ---------- */
  function cizCanvas(canvasId, isiVeri) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const koyu = document.body.classList.contains('koyu');
    const kap = canvas.parentElement;
    const W = kap ? kap.clientWidth : 800;
    if (W > 10) { canvas.width = W; canvas.height = Math.round(W * 1.35); }
    _sonIsi[canvasId] = isiVeri; // görsel async yüklenince yeniden çizebilmek için sakla
    cizSema(canvas, isiVeri, koyu);
  }

  /* ---------- garson filtresi ---------- */
  function adimTopla(garsonId) {
    if (garsonId !== null) {
      const g = GARSONLAR.find(x => x.id === garsonId);
      return g ? +g.adim || 0 : 0;
    }
    return GARSONLAR.reduce((s,g) => s + (+g.adim||0), 0);
  }

  window.isiSec = function(garsonId, el, mode) {
    seciliId = garsonId;
    // chip aktif — HER İKİ satırı (mobil+masaüstü) senkronla
    ['m','t'].forEach(md => {
      const cont = document.getElementById('isi-garsonlar-' + md);
      if (cont) cont.querySelectorAll('.isi-g-chip').forEach(c => {
        const cid = c.dataset.id === '' ? null : +c.dataset.id;
        c.classList.toggle('akt', cid === seciliId);
      });
    });
    // isi verisi (seçili garson yoksa 'tum')
    const key = garsonId !== null ? String(garsonId) : 'tum';
    const isiVeri = ISILER[key] || ISILER['tum'] || {};

    // canvas yeniden çiz — HER İKİ canvas'ı da yenile (görünür olan kesin güncellensin)
    if (SEMA) {
      cizCanvas('isi-canvas', isiVeri);
      cizCanvas('isi-canvas-t', isiVeri);
    } else {
      // izgara renk güncelle
      const izgara = mode === 't' ? document.getElementById('harita-izgara-t') : document.getElementById('harita-kap');
      if (izgara) {
        const masalar2 = (isiVeri.masalar || []);
        const maxA2 = +isiVeri.max_agirlik || 1;
        masalar2.forEach(m => {
          const tile = izgara.querySelector('[data-masa-id="' + m.id + '"]');
          if (!tile) return;
          const a2 = +m.agirlik || 0;
          const o2 = maxA2 > 0 ? a2/maxA2 : 0;
          tile.style.background = sicaklik(o2);
          tile.style.color = a2 > 0 ? '#fff' : 'var(--ink)';
          tile.style.border = a2 > 0 ? 'none' : '';
          const islem = tile.querySelector('.islem');
          if (islem) islem.textContent = a2 > 0 ? a2 + ' işlem' : '';
          const ad2 = tile.querySelector('div');
          if (ad2) ad2.textContent = m.ad || '';
        });
      }
    }

    // adım/km güncelle
    const adim2 = adimTopla(garsonId);
    const km2 = adim2 * 0.75 / 1000;
    const kmStr2 = km2 >= 1 ? km2.toFixed(1).replace('.',',') : km2.toFixed(2).replace('.',',');
    const numFmt = n => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const ytAdim = document.getElementById('yt-adim'); if (ytAdim) ytAdim.textContent = numFmt(adim2);
    const ytKm = document.getElementById('yt-km'); if (ytKm) ytKm.textContent = kmStr2;
    const ywKm = document.getElementById('yw-km'); if (ywKm) ywKm.textContent = kmStr2;
    const ywAdim = document.getElementById('yw-adim');
    if (ywAdim) ywAdim.textContent = numFmt(adim2) + ' adım · ' + (garsonId ? (GARSONLAR.find(x=>x.id===garsonId)||{}).ad||'' : 'tüm garsonlar');
  };

  /* ---------- ilk çizim ---------- */
  function init() {
    if (!SEMA) return;
    const isiVeri = ISILER['tum'] || {};
    cizCanvas('isi-canvas', isiVeri);
    cizCanvas('isi-canvas-t', isiVeri);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  // Ekran boyutu değişince yeniden çiz
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      const key = seciliId !== null ? String(seciliId) : 'tum';
      const isiVeri = ISILER[key] || ISILER['tum'] || {};
      cizCanvas('isi-canvas', isiVeri);
      cizCanvas('isi-canvas-t', isiVeri);
    }, 200);
  });
})();
</script>
@endsection

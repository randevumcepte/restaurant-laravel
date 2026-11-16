<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $dosya }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
<link rel="stylesheet" href="/grafik.css?v=2">
@php
  $tema = '#' . ltrim($tema ?? '7C3AED', '#');
  $aiRapor = ($rapor ?? '') === 'ai';
  $hucre = function ($val, $tip) {
      if ($val === null || $val === '') return '';
      if ($tip === 'tl' && is_numeric($val)) return number_format((float) $val, 2, ',', '.') . ' ₺';
      if ($tip === 'y' && is_numeric($val)) return ((float) $val < 0 ? '-%' : '%') . number_format(abs((float) $val), 1, ',', '.');
      if ($tip === 's' && is_numeric($val)) return rtrim(rtrim(number_format((float) $val, 3, ',', '.'), '0'), ',');
      if ($tip === 't' && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $val)) return \Carbon\Carbon::parse($val)->format('d.m.Y');
      return (string) $val;
  };
  $tl0 = fn($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
  $kpiDeg = fn($deg, $fmt) => $fmt === 'tl' ? $tl0($deg) : ($fmt === 'y' ? $hucre($deg, 'y') : ($fmt === 'sayi' && is_numeric($deg) ? number_format((float) $deg, fmod((float) $deg, 1) ? 1 : 0, ',', '.') : $deg));
  $blokDeg = fn($val, $tip) => $tip === 'tl' ? $tl0($val) : $hucre($val, $tip);
  $palet = ['#7C3AED', '#F59E0B', '#0EA5E9', '#10B981', '#EC4899', '#4F46E5', '#F43F5E', '#64748B', '#14B8A6', '#A855F7'];
  $hexA = fn($hex, $a) => 'rgba(' . hexdec(substr(ltrim($hex, '#'), 0, 2)) . ',' . hexdec(substr(ltrim($hex, '#'), 2, 2)) . ',' . hexdec(substr(ltrim($hex, '#'), 4, 2)) . ',' . $a . ')';

  // ===== Grafikler (sunucuda çizilir: önizleme, PDF ve yazdırmada aynı görünür) =====
  // Kategorik sıra sabit (renk körlüğü için doğrulandı); sıra dışı kalanlar "Diğer" griye düşer
  $katRenk = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];
  $kisa = function ($v, $tip) use ($hucre) {
      $v = (float) $v; $a = abs($v);
      if ($tip === 'y') return ($v < 0 ? '-%' : '%') . number_format($a, 1, ',', '.');
      $s = $a >= 1e6 ? number_format($v / 1e6, 1, ',', '.') . ' mn' : ($a >= 1e4 ? number_format($v / 1e3, 1, ',', '.') . ' bin' : number_format($v, $a >= 100 || fmod($a, 1) == 0 ? 0 : 1, ',', '.'));
      return $tip === 'tl' ? $s . ' ₺' : $s;
  };
  $tam = fn($v, $tip) => $tip === 'tl' ? number_format((float) $v, 2, ',', '.') . ' ₺' : ($tip === 'y' ? $hucre($v, 'y') : number_format((float) $v, fmod((float) $v, 1) ? 2 : 0, ',', '.'));
  // Sütun grafiği (zaman serisi): y ekseni 3 kılavuz, en yüksek ve son değer etiketli, x etiketleri seyreltilir
  $grSutun = function (array $et, array $dg, $tip, $renk) use ($kisa, $tam) {
      $n = count($dg); $mx = max(0.0001, ...array_map('floatval', $dg)); $imx = array_search(max($dg), $dg);
      $adim = $n > 16 ? (int) ceil($n / 8) : 1;
      $h = '<div class="gc" style="--c:' . $renk . '"><div class="gc-alan">';
      foreach ([1, 0.5, 0] as $o) $h .= '<div class="gc-cz" style="bottom:' . ($o * 100) . '%"><span>' . e($kisa($mx * $o, $tip)) . '</span></div>';
      $h .= '<div class="gc-bars">';
      foreach ($dg as $i => $v) {
          $yk = $v > 0 ? max(1.5, round($v / $mx * 100, 2)) : 0;
          $et_ = ($i === $imx || $i === $n - 1) && $v > 0 ? '<em style="bottom:calc(' . $yk . '% + 3px)' . ($i === $n - 1 && $n > 4 ? ';right:0' : '') . ($i === 0 && $n > 4 ? ';left:0' : '') . '">' . e($kisa($v, $tip)) . '</em>' : '';
          $h .= '<div class="gc-b" data-tip="' . e($et[$i] . ': ' . $tam($v, $tip)) . '">' . $et_ . '<i style="height:' . $yk . '%"></i></div>';
      }
      $h .= '</div></div><div class="gc-x' . ($n > 6 && $adim === 1 ? ' sik' : '') . '">';
      foreach ($et as $i => $l) $h .= '<span>' . (($i % $adim === 0 || $i === $n - 1) && ($i === $n - 1 || $n - 1 - $i >= $adim || $adim === 1) ? e($l) : '') . '</span>';
      return $h . '</div></div>';
  };
  // Halka grafiği: en çok 7 dilim + Diğer; dilimler arasında 2px boşluk, yanında etiketli lejant
  $grHalka = function (array $et, array $dg, $tip) use ($katRenk, $kisa, $tam) {
      arsort($dg); $sira = array_keys($dg); $dl = [];
      foreach ($sira as $j => $i) { if ($j < 7 || count($sira) === 8) $dl[] = [$et[$i], (float) $dg[$i]]; }
      if (count($sira) > 8) $dl[] = ['Diğer (' . (count($sira) - 7) . ')', array_sum(array_map(fn($i) => (float) $dg[$i], array_slice($sira, 7)))];
      $top = max(0.0001, array_sum(array_column($dl, 1))); $r = 54; $C = 2 * M_PI * $r; $bas = 0; $gap = count($dl) > 1 ? 2 : 0;
      $svg = '<svg class="gh-svg" viewBox="0 0 140 140" width="140" height="140"><circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="#EEF0F7" stroke-width="18"/>';
      $lej = '';
      foreach ($dl as $j => [$ad, $v]) {
          $renk = str_starts_with($ad, 'Diğer (') ? '#94A3B8' : $katRenk[$j % 8]; $uz = $v / $top * $C;
          if ($uz > $gap) $svg .= '<circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="' . $renk . '" stroke-width="18" stroke-dasharray="' . round($uz - $gap, 2) . ' ' . round($C - $uz + $gap, 2) . '" stroke-dashoffset="' . round(-$bas, 2) . '" transform="rotate(-90 70 70)"><title>' . e($ad . ': ' . $tam($v, $tip)) . '</title></circle>';
          $bas += $uz;
          $lej .= '<div data-tip="' . e($ad . ': ' . $tam($v, $tip)) . '"><i style="background:' . $renk . '"></i><span class="a">' . e($ad) . '</span><b>' . e($kisa($v, $tip)) . '</b><span class="p">%' . number_format($v / $top * 100, 1, ',', '.') . '</span></div>';
      }
      $svg .= '<text x="70" y="66" text-anchor="middle" class="gh-t1">Toplam</text><text x="70" y="84" text-anchor="middle" class="gh-t2">' . e($kisa($top, $tip)) . '</text></svg>';
      return '<div class="gh">' . $svg . '<div class="gh-lej">' . $lej . '</div></div>';
  };
  // Yatay çubuk: en çok 10 satır; yüzde değişimde sıfır ortada (artış kırmızı, düşüş mavi)
  $grCubuk = function (array $et, array $dg, $tip, $renk) use ($kisa, $tam) {
      $neg = min($dg) < 0; $mx = max(0.0001, ...array_map(fn($v) => abs((float) $v), $dg)); $h = '<div class="gb">';
      foreach ($dg as $i => $v) {
          $w = round(abs($v) / $mx * ($neg ? 50 : 100), 2);
          $bar = $neg ? '<i style="' . ($v >= 0 ? 'left:50%' : 'right:50%') . ';width:' . $w . '%;background:' . ($v >= 0 ? '#e34948' : '#2a78d6') . '"></i><s></s>' : '<i style="left:0;width:' . max(1, $w) . '%;background:' . $renk . '"></i>';
          $h .= '<div class="gb-s" data-tip="' . e($et[$i] . ': ' . $tam($v, $tip)) . '"><span class="n">' . e($et[$i]) . '</span><span class="t">' . $bar . '</span><b>' . e($kisa($v, $tip)) . '</b></div>';
      }
      return $h . '</div>';
  };
  // Tablodan otomatik grafik: ölçü = en anlamlı sayısal sütun; tarih sütunu → seyir; gruplanabilir metin sütunu → dağılım
  $ayAd = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
  $otoGrafik = function (array $kol, array $duz) use ($ayAd) {
      $n = count($duz); if ($n < 2) return [];
      $skor = []; $tarihK = null; $metinK = [];
      foreach ($kol as $i => $k) {
          $t = $k[1] ?? ''; $b = $k[3] ?? ''; $ad = mb_strtolower($k[0] ?? '');
          if ($t === 't') { $tarihK ??= $i; continue; }
          if (in_array($t, ['tl', 's', 'y'], true)) {
              $sayi = array_filter($duz, fn($r) => is_numeric($r[$i] ?? null)); if (count($sayi) < 2) continue;
              $p = ['tl' => 30, 's' => 10, 'y' => 10][$t] + (str_contains($b, 'top') || str_contains($b, 'bar') ? 10 : 0) + (preg_match('/tutar|harcama|toplam|etki|değer|deger|maliyet|bakiye|fark|alış|alis/u', $ad) ? 5 : 0) - (preg_match('/fiyat|oran|pay|gün|gun|adet|sayı|sayi/u', $ad) && $t !== 'y' ? 4 : 0);
              $skor[$i] = $p + min(4, log10(1 + array_sum(array_map(fn($r) => abs((float) $r[$i]), $sayi))) / 3);
              continue;
          }
          if (preg_match('/^(birim|kod|stok kodu|barkod|no|#|fatura no|sipariş|talep|ettn|vkn|açıklama|not|önceki → yeni|tarih)$|^önceki|fatura no|kodu$|vkn/u', $ad)) continue;
          $d = count(array_unique(array_filter(array_map(fn($r) => trim((string) ($r[$i] ?? '')), $duz), 'strlen')));
          if ($d >= 2) $metinK[$i] = $d;
      }
      $out = [];
      $m = $skor ? array_search(max($skor), $skor) : null; $mt = $m !== null ? $kol[$m][1] : null; $mAd = $m !== null ? $kol[$m][0] : 'Kayıt sayısı';
      // toplanabilir mi: tutar evet; gün, fiyat, seviye, endeks, oran hayır (gruplarken ortalama ya da kayıt sayısı)
      $topBayrak = $m !== null && str_contains($kol[$m][3] ?? '', 'top');
      $toplanir = $m !== null && ($topBayrak || ($mt === 'tl' && !preg_match('/fiyat/u', mb_strtolower($mAd))));
      $topla = fn(array $gr) => $toplanir ? array_map('array_sum', $gr) : array_map(fn($l) => array_sum($l) / count($l), $gr);
      // ilk metin sütunu ay/tarih adı taşıyorsa (Oca 2026, 2026-05…) satırlar zaten zaman sırasında: sütun grafiği
      $ilkMetin = $metinK ? array_key_first($metinK) : null;
      if ($m !== null && $tarihK === null && $ilkMetin !== null && $mt !== 'y') {
          $etk = array_map(fn($r) => trim((string) ($r[$ilkMetin] ?? '')), $duz);
          $zamanli = count(array_filter($etk, fn($x) => preg_match('/^((Oca|Şub|Mar|Nis|May|Haz|Tem|Ağu|Eyl|Eki|Kas|Ara)[a-zçğıöşü]*\.?\s*\d{2,4}|\d{4}-\d{2}(-\d{2})?|\d{1,2}\.\d{1,2}(\.\d{2,4})?|\d{4}\s*-?\s*\d{1,2}\.?\s*hafta)$/u', $x)));
          if ($zamanli >= max(3, count($etk) * 0.8)) {
              $dg = array_map(fn($r) => max(0, (float) ($r[$m] ?? 0)), $duz);
              return [['tur' => 'sutun', 'baslik' => $mAd . ' · ' . mb_strtolower($kol[$ilkMetin][0]) . ' bazında seyir', 'et' => $etk, 'dg' => $dg, 'tip' => $mt]];
          }
      }
      // 1) zaman seyri
      if ($tarihK !== null && $mt !== 'y') {
          $tar = array_filter(array_map(fn($r) => substr((string) ($r[$tarihK] ?? ''), 0, 10), $duz), fn($x) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $x));
          if (count(array_unique($tar)) >= 3) {
              $ilk = min($tar); $son = max($tar); $gun = (strtotime($son) - strtotime($ilk)) / 86400;
              $kova = $gun > 120 ? 'ay' : ($gun > 45 ? 'hafta' : 'gun'); $gr = [];
              foreach ($duz as $r) {
                  $t = substr((string) ($r[$tarihK] ?? ''), 0, 10); if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $t)) continue;
                  $key = $kova === 'ay' ? substr($t, 0, 7) : ($kova === 'hafta' ? date('Y-m-d', strtotime($t . ' monday this week')) : $t);
                  $gr[$key][] = $m !== null ? (float) ($r[$m] ?? 0) : 1;
              }
              // boş günleri/haftaları/ayları sıfırla doldur: seyir kesintisiz okunsun
              $dolu = []; $c = strtotime(min(array_keys($gr)) . ($kova === 'ay' ? '-01' : ''));
              $bitK = max(array_keys($gr));
              for ($g = 0; $g < 400; $g++) {
                  $key = $kova === 'ay' ? date('Y-m', $c) : date('Y-m-d', $c); if ($key > $bitK) break;
                  $dolu[$key] = isset($gr[$key]) ? ($m !== null ? $topla([$gr[$key]])[0] : count($gr[$key])) : 0;
                  $c = strtotime($kova === 'ay' ? '+1 month' : ($kova === 'hafta' ? '+1 week' : '+1 day'), $c);
              }
              $et = array_map(fn($k) => $kova === 'ay' ? $ayAd[(int) substr($k, 5, 2)] . ' ' . substr($k, 2, 2) : date('d.m', strtotime($k)), array_keys($dolu));
              $out[] = ['tur' => 'sutun', 'baslik' => $mAd . ' · ' . ['gun' => 'günlük', 'hafta' => 'haftalık', 'ay' => 'aylık'][$kova] . ' seyir', 'et' => $et, 'dg' => array_values($dolu), 'tip' => $m !== null ? $mt : 's'];
          }
      }
      // 2) dağılım: tekrar eden değerli metin sütunu (tedarikçi, kategori, durum…) önce; yoksa ilk metin sütunu
      if ($metinK) {
          // anlamlı grup sütunu önce (tedarikçi, kategori, departman, durum…), sonra tekrar eden herhangi biri
          $grupK = null; $aday = array_filter($metinK, fn($d) => $d <= min(12, $n - 1));
          foreach ($aday as $i => $d) if (preg_match('/tedarik|kategori|departman|durum|şube|sube|neden|tür|tur|aciliyet|öncelik|oncelik|kaynak|tip|grup/u', mb_strtolower($kol[$i][0]))) { $grupK = $i; break; }
          $grupK ??= array_key_first($aday);
          $k = $grupK ?? array_key_first($metinK);
          if ($grupK === null && ($m === null || $n < 3)) return $out;
          // gruplarken toplanamayan ölçü (gün, fiyat…) yerine kayıt sayısı
          $say = $m === null || ($grupK !== null && !$toplanir && $mt !== 'y');
          $gr = []; foreach ($duz as $r) { $a = trim((string) ($r[$k] ?? '')); if ($a === '') $a = '—'; $gr[$a][] = $say ? 1 : (float) ($r[$m] ?? 0); }
          $dg = $say ? array_map('count', $gr) : $topla($gr);
          $tip = $say ? 's' : $mt;
          if ($say) $mAd = 'Kayıt sayısı';
          elseif ($grupK !== null && !$toplanir) $mAd = 'Ort. ' . mb_strtolower($mAd);
          $pozitif = min($dg) >= 0;
          if ($grupK !== null && $tip !== 'y' && $pozitif && count($dg) >= 2 && count($dg) <= 8) {
              $out[] = ['tur' => 'halka', 'baslik' => $mAd . ' · ' . $kol[$k][0] . ' dağılımı', 'et' => array_keys($dg), 'dg' => array_values($dg), 'tip' => $tip];
          } else {
              uasort($dg, fn($a, $b) => abs($b) <=> abs($a));
              $ust = array_slice($dg, 0, 10, true);
              if ($tip !== 'y' && count($dg) > 10 && $pozitif) $ust['Diğer (' . (count($dg) - 10) . ')'] = array_sum(array_slice($dg, 10));
              $out[] = ['tur' => 'cubuk', 'baslik' => $mAd . ' · ' . ($grupK !== null ? $kol[$k][0] . ' bazında' : 'en yüksek ' . min(10, count($dg)) . ' ' . mb_strtolower($kol[$k][0])), 'et' => array_map('strval', array_keys($ust)), 'dg' => array_values($ust), 'tip' => $tip];
          }
      }
      return $out;
  };
  // ortak kitle aynı görünüm (animasyon, koyu tema, PDF)
  if (function_exists('_grSutun')) { $grSutun = fn($et, $dg, $tip, $renk) => _grSutun($et, $dg, $tip, $renk); $grHalka = fn($et, $dg, $tip) => _grHalka($et, $dg, $tip); $grCubuk = fn($et, $dg, $tip, $renk) => _grCubuk($et, $dg, $tip, $renk); }
  $grCiz = fn(array $g, $renk) => $g['tur'] === 'sutun' ? $grSutun($g['et'], $g['dg'], $g['tip'], $renk) : ($g['tur'] === 'halka' ? $grHalka($g['et'], $g['dg'], $g['tip']) : $grCubuk($g['et'], $g['dg'], $g['tip'], $renk));
@endphp
<style>
  @page{size:A4 landscape;margin:12mm 10mm}
  *{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1E293B;background:#F1F2F9;font-size:11px}
  .sayfa{max-width:1180px;margin:0 auto;padding:22px}
  .ust{border-radius:16px;padding:20px 24px;color:#fff;background:linear-gradient(120deg,{{ $tema }} 0%,{{ $tema }} 35%,#1E1B4B 100%);display:flex;gap:16px;align-items:center}
  .ust .lg{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.18);display:grid;place-items:center}
  .ust .lg .material-icons-outlined{font-size:28px;color:#fff}
  .ust h1{margin:0;font-size:21px;font-weight:900}.ust p{margin:3px 0 0;font-size:11.5px;color:rgba(255,255,255,.88)}
  .ust .rozet{display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:3px 10px;border-radius:99px;font-size:10.5px;font-weight:800;background:rgba(255,255,255,.2);color:#fff}
  .ust .mk{margin-left:auto;text-align:right;font-size:10.5px;color:rgba(255,255,255,.8)}.ust .mk b{display:block;font-size:14px;color:#fff}
  .aciklama{margin:12px 2px 0;font-size:11.5px;color:#475569}
  .uyari{margin-top:12px;border-radius:12px;padding:11px 14px;background:#FAF5FF;border:1px solid #E9D5FF;color:#6B21A8;font-size:11.5px;display:flex;gap:9px;align-items:flex-start}
  .uyari .material-icons-outlined{font-size:18px}
  .arac{display:flex;gap:8px;justify-content:flex-end;margin:12px 0}
  .arac button{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;padding:9px 14px;font-weight:800;font-size:12.5px;cursor:pointer;color:#fff;background:{{ $tema }}}
  .arac button.g{background:#fff;color:#334155;border:1px solid #E2E8F0}
  .kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0}
  .kpi div{border-radius:12px;padding:11px 14px;color:#fff;background:var(--c)}
  .kpi span{font-size:10px;font-weight:700;opacity:.92}.kpi b{display:block;font-size:18px;font-weight:900;margin-top:3px;line-height:1.2}
  .kpi b.metin{font-size:14px}
  .grafikler{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px;margin-bottom:6px;align-items:start}
  .gk{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;page-break-inside:avoid}
  .gk.tam{grid-column:1 / -1}
  .gk h3{margin:0 0 9px;font-size:12px;color:{{ $tema }}}
  .gs{display:flex;align-items:center;gap:8px;margin-bottom:5px;font-size:10.5px}
  .gs .n{width:34%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700}
  .gs .t{flex:1;height:10px;border-radius:99px;background:#EEF0F7;overflow:hidden}.gs .t i{display:block;height:100%;border-radius:99px}
  .gs .v{width:auto;min-width:80px;max-width:46%;text-align:right;font-weight:800;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .sutun{display:flex;align-items:flex-end;gap:6px;height:120px;padding-top:6px}
  .sutun .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;min-width:0}
  .sutun .c{width:100%;max-width:40px;border-radius:6px 6px 2px 2px;background:{{ $tema }}}
  .sutun .v{font-size:9px;font-weight:800;color:#475569}.sutun .e{font-size:9px;color:#64748B;font-weight:700;white-space:nowrap}
  .seyir{display:grid;grid-template-columns:1fr 1fr;gap:6px 18px}
  .seyir div{display:flex;align-items:center;gap:8px;font-size:10.5px}
  .seyir .n{width:30%;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .seyir .y{width:56px;text-align:right;font-weight:800}
  .yy{margin-bottom:9px}.yy .n{font-weight:800;font-size:11px;margin-bottom:3px}
  .yy .s{display:flex;align-items:center;gap:8px;font-size:10px;margin-bottom:2px}
  .yy .s .t{width:30%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .yy .s .cb{flex:1;height:9px;border-radius:99px;background:#EEF0F7;overflow:hidden}.yy .s .cb i{display:block;height:100%;border-radius:99px}
  .yy .s .v{width:80px;text-align:right;font-weight:800}
  .karneler{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .kk{border:1px solid #E4E7F2;border-radius:12px;padding:12px;display:flex;gap:12px;align-items:center}
  .kk .harf{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;font-size:22px;font-weight:900;flex:0 0 auto}
  .kk .b{font-weight:900;font-size:12px}.kk .d{font-size:10px;color:#64748B;margin-top:2px;line-height:1.5}
  .aralik{display:flex;align-items:center;gap:10px;margin-bottom:7px;font-size:10.5px}
  .aralik .n{width:22%;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .aralik .ser{flex:1;position:relative;height:18px}
  .aralik .ser .cizgi{position:absolute;top:7px;height:4px;border-radius:99px;background:#BBF7D0}
  .aralik .ser .ort{position:absolute;top:3px;width:2px;height:12px;background:#059669}
  .aralik .ser .sen{position:absolute;top:3px;width:12px;height:12px;border-radius:50%;margin-left:-6px;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.15)}
  .aralik .v{width:150px;text-align:right;font-size:10px;color:#475569}
  .lejant{display:flex;gap:14px;font-size:9.5px;color:#64748B;margin-bottom:8px}
  .lejant i{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;margin-right:4px}
  h2{font-size:14px;margin:20px 0 4px;font-weight:900;color:{{ $tema }};display:flex;align-items:center;gap:8px;page-break-after:avoid}
  h2::before{content:'';width:4px;height:16px;border-radius:2px;background:{{ $tema }}}
  .not{font-size:10.5px;color:#64748B;margin:0 0 8px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #E4E7F2;border-radius:10px;overflow:hidden}
  thead{display:table-header-group}
  th{background:{{ $tema }};color:#fff;font-size:9.5px;text-transform:uppercase;letter-spacing:.3px;text-align:left;padding:7px 8px;font-weight:800}
  td{padding:6px 8px;border-top:1px solid #EEF0F7;vertical-align:top}
  tr:nth-child(even) td{background:#FAFAFE}
  tr{page-break-inside:avoid}
  table.genis{font-size:9px;table-layout:fixed}
  table.genis th{font-size:7.5px;padding:6px 4px;letter-spacing:0;text-transform:none;overflow-wrap:anywhere;overflow:hidden;line-height:1.25;vertical-align:bottom}
  table.genis td{padding:5px 4px;word-break:break-word}
  tr.top td{background:{{ $hexA($tema, .08) }};font-weight:900;border-top:2px solid {{ $tema }}}
  tr.grup td{background:#F8FAFC !important;font-weight:900;color:{{ $tema }};border-top:2px solid #E2E8F0}
  tr.ara td{font-weight:800;color:#475569;background:#fff !important}
  .r{text-align:right;white-space:nowrap}
  th.r{white-space:normal}
  td.cb{position:relative}
  td.cb .cbi{position:absolute;left:0;top:3px;bottom:3px;border-radius:0 4px 4px 0;background:{{ $hexA($tema, .2) }}}
  td.cb span{position:relative}
  .onc{display:inline-block;padding:2px 7px;border-radius:99px;font-weight:900;font-size:9.5px}
  .bosgrup{margin:16px 0 4px;padding:10px 14px;border:1px dashed #CBD5E1;border-radius:12px;background:#F8FAFC;color:#475569;font-size:11px;line-height:1.5}
  .bg-bas{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
  .bg-bas .material-icons-outlined{font-size:17px;color:#94A3B8}
  .bg-bas b{color:#334155;font-size:11.5px;margin-right:2px}
  .bg-cip{padding:2px 9px;border-radius:99px;background:#fff;border:1px solid #E2E8F0;font-weight:700;color:#475569;font-size:10.5px}
  .bg-neden{margin-top:5px;color:#64748B}.bg-neden b{color:#475569}
  .bos{padding:13px 16px;color:#92400E;background:#FFFBEB;border:1px dashed #FCD34D;border-radius:10px;font-size:11.5px;line-height:1.55}
  .ai{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .ai .k{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px;border-top:3px solid var(--c);page-break-inside:avoid}
  .ai .k h3{margin:0 0 6px;font-size:12px;color:var(--c)}
  .ai .m{padding:6px 0;border-top:1px solid #F1F5F9}.ai .m b{display:block;color:#0F172A}
  .ozet{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.55;margin-bottom:10px;display:flex;gap:14px;align-items:center}
  .skor{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;flex:0 0 auto}
  .skor div{width:50px;height:50px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:18px;font-weight:900}
  .alt{margin-top:18px;font-size:9.5px;color:#94A3B8;text-align:center}
  /* ---- grafikler ---- */
  .gk h3 small{font-weight:600;color:#94A3B8;font-size:10px;margin-left:4px}
  .gc{padding:4px 2px 0}
  .gc-alan{position:relative;height:150px;margin:20px 0 0 52px}
  .gc-cz{position:absolute;left:0;right:0;border-top:1px solid #EEF0F7}
  .gc-cz span{position:absolute;right:calc(100% + 6px);top:-7px;font-size:9px;color:#94A3B8;white-space:nowrap}
  .gc-bars{position:absolute;inset:0;display:flex;align-items:flex-end;gap:2px}
  .gc-b{position:relative;flex:1;height:100%;display:flex;align-items:flex-end;justify-content:center;min-width:0}
  .gc-b i{display:block;width:100%;max-width:38px;border-radius:4px 4px 0 0;background:var(--c)}
  .gc-b em{position:absolute;font-style:normal;font-size:9px;font-weight:800;color:#334155;white-space:nowrap}
  .gc-b:hover i{filter:brightness(1.12)}
  .gc-x{display:flex;gap:2px;margin:4px 0 0 52px;border-top:1px solid #CBD5E1;padding-top:3px}
  .gc-x span{flex:1;min-width:0;text-align:center;font-size:9px;color:#64748B;white-space:nowrap;overflow:visible}
  .gh{display:flex;gap:16px;align-items:center}
  .gh-svg{flex:none}
  .gh-t1{font-size:9px;fill:#94A3B8;font-weight:700}.gh-t2{font-size:12.5px;fill:#0F172A;font-weight:900}
  .gh-lej{flex:1;min-width:0;display:grid;gap:4px}
  .gh-lej div{display:flex;align-items:center;gap:7px;font-size:10.5px}
  .gh-lej i{width:10px;height:10px;border-radius:3px;flex:none}
  .gh-lej .a{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;color:#334155}
  .gh-lej b{color:#0F172A;white-space:nowrap}.gh-lej .p{width:42px;text-align:right;color:#64748B;font-weight:700}
  .gb{display:grid;gap:5px}
  .gb-s{display:flex;align-items:center;gap:8px;font-size:10.5px}
  .gb-s .n{width:34%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700;color:#334155}
  .gb-s .t{flex:1;position:relative;height:12px;border-radius:4px;background:#F1F3F9}
  .gb-s .t i{position:absolute;top:0;bottom:0;border-radius:4px}
  .gb-s .t s{position:absolute;left:50%;top:-2px;bottom:-2px;width:1px;background:#94A3B8}
  .gb-s b{width:84px;text-align:right;color:#0F172A;white-space:nowrap}
  .gb-s:hover .t{background:#E8EBF5}
  .ipucu{position:fixed;z-index:50;pointer-events:none;background:#0F172A;color:#fff;font-size:11px;font-weight:700;padding:5px 9px;border-radius:7px;box-shadow:0 6px 16px rgba(15,23,42,.25);white-space:pre-line;max-width:320px;line-height:1.45;display:none}
  .grafikler.tablo-ust{margin:6px 0 10px}
  .tablo{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;border-radius:10px}
  /* panel içinde önizleme: tam genişlik, okunur yazı */
  body.gomulu{font-size:12px}
  body.gomulu .sayfa{max-width:none;padding:18px 20px}
  body.gomulu table{font-size:12px}
  body.gomulu th{font-size:10.5px}
  body.gomulu table.genis{font-size:10.5px}
  body.gomulu table.genis th{font-size:9px}
  body.gomulu .gs,body.gomulu .seyir div,body.gomulu .aralik{font-size:11.5px}
  body.gomulu .kpi span{font-size:11px}
  body.gomulu .kpi b{font-size:20px}
  @media screen and (max-width:1100px){
    .karneler{grid-template-columns:repeat(2,minmax(0,1fr))}
    .seyir{grid-template-columns:minmax(0,1fr)}
  }
  @media screen and (max-width:860px){
    .grafikler,.ai{grid-template-columns:minmax(0,1fr)}
    .kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
    .tablo table{width:max(100%,640px)}
    .tablo table.genis{width:max(100%,1280px)}
  }
  @media screen and (max-width:560px){
    body.gomulu .sayfa,.sayfa{padding:10px}
    .ust{flex-wrap:wrap;padding:16px;gap:12px}
    .ust h1{font-size:17px}
    .ust .mk{margin-left:0;text-align:left;width:100%}
    .karneler{grid-template-columns:minmax(0,1fr)}
    .kpi b{font-size:16px}
    .gs{flex-wrap:wrap;gap:3px 8px;margin-bottom:9px}
    .gs .n{width:100%}
    .gs .v{width:auto;max-width:60%;text-align:right}
    .seyir .n{width:40%}
    .gc-alan,.gc-x{margin-left:44px}
    .gc-x span{font-size:8px}
    .gc-x.sik span:nth-child(even):not(:last-child),.gc-x.sik span:nth-last-child(2){visibility:hidden}
    .gb-s .n{width:30%}.gb-s b{width:auto;min-width:58px}
    .gh{flex-direction:column;align-items:stretch}.gh-svg{align-self:center}
    .aralik{flex-wrap:wrap}.aralik .n{width:100%}.aralik .v{width:100%;text-align:left}
    .ozet{flex-wrap:wrap}
    .lejant{flex-wrap:wrap}
    .arac{flex-wrap:wrap}
  }
  @media print{body{background:#fff}.sayfa{padding:0;max-width:none}.arac,.ipucu{display:none!important}.tablo{overflow:visible}.gk{break-inside:avoid}}
</style>
</head>
<body class="{{ request()->boolean('onizle') ? 'gomulu' : '' }}">
<div class="sayfa">
  <div class="ust">
    <div class="lg"><span class="material-icons-outlined">{{ $ikon ?? 'radar' }}</span></div>
    <div>
      <h1>{{ $baslik }}</h1><p>{{ $alt }}</p>
      @if ($aiRapor)<span class="rozet">✨ Yapay zekâ tarafından hazırlanmıştır</span>@else<span class="rozet">Sistem verisi · yorum içermez</span>@endif
    </div>
    <div class="mk"><b>ResteOS</b>{{ $marka ?? 'Fiyat Radarı' }}</div>
  </div>
  @if (!empty($aciklama))<p class="aciklama">{{ $aciklama }}</p>@endif
  @if ($aiRapor)
    <div class="uyari"><span class="material-icons-outlined">auto_awesome</span><div>Bu rapordaki <b>yorum, değerlendirme ve öneriler yapay zekâya aittir</b>{{ !empty($ai['_zaman']) ? ' (' . $ai['_zaman'] . ')' : '' }}. Rakamlar sistemdeki alış faturalarından gelir ve raporun sonundaki <b>“Dayanak”</b> tablolarında doğrulanabilir. Öneriler karar desteğidir; son karar satın almacınındır.</div></div>
  @endif
  @if (empty($onizle))
  <div class="arac">
    <button class="g" onclick="window.close()"><span class="material-icons-outlined" style="font-size:16px">close</span> Kapat</button>
    <button onclick="window.print()"><span class="material-icons-outlined" style="font-size:16px">picture_as_pdf</span> PDF olarak kaydet / Yazdır</button>
  </div>
  @endif

  @php
    // Art arda gelen boş tablo bölümleri tek kısa kartta birleşir; panodaki notla aynı neden tekrar yazılmaz
    $panoBos = collect($sayfalar)->firstWhere('tip', 'pano')['bos'] ?? null; $sayfalar2 = [];
    foreach ($sayfalar as $s) {
        $bosMu = ($s['tip'] ?? '') !== 'pano' && !($ai && !empty($s['ai'])) && !count($s['sat'] ?? []);
        if (!$bosMu) { $sayfalar2[] = $s; continue; }
        $neden = $s['bos'] ?? 'Seçili dönem ve filtrelerle eşleşen kayıt yok.';
        $son = count($sayfalar2) - 1;
        if ($son >= 0 && ($sayfalar2[$son]['tip'] ?? '') === 'bosgrup') { $sayfalar2[$son]['adlar'][] = $s['ad']; $sayfalar2[$son]['nedenler'][$neden][] = $s['ad']; }
        else $sayfalar2[] = ['tip' => 'bosgrup', 'adlar' => [$s['ad']], 'nedenler' => [$neden => [$s['ad']]]];
    }
  @endphp
  @foreach ($sayfalar2 as $s)
    @if (($s['tip'] ?? '') === 'bosgrup')
      <div class="bosgrup">
        <div class="bg-bas"><span class="material-icons-outlined">inventory_2</span><b>{{ count($s['adlar']) === 1 ? 'Veri yok' : count($s['adlar']) . ' bölümde veri yok' }}</b>
          @foreach ($s['adlar'] as $ad)<span class="bg-cip">{{ $ad }}</span>@endforeach</div>
        @foreach ($s['nedenler'] as $neden => $kimler)
          @if ($neden !== $panoBos)<div class="bg-neden">@if (count($s['nedenler']) > 1)<b>{{ implode(', ', $kimler) }}:</b> @endif{{ $neden }}</div>@endif
        @endforeach
        @if (isset($s['nedenler'][$panoBos]) && count($s['nedenler']) === 1)<div class="bg-neden">Nedeni yukarıdaki notta.</div>@endif
      </div>
      @continue
    @endif
    @if (($s['tip'] ?? '') === 'pano')
      {{-- ===== PANO: rapora özel kartlar + grafikler ===== --}}
      <div class="kpi">
        @foreach ($s['kpi'] as [$et, $deg, $fmt, $renk])
          <div style="--c:#{{ $renk }}"><span>{{ $et }}</span><b class="{{ $fmt === 'metin' ? 'metin' : '' }}">{{ $kpiDeg($deg, $fmt) }}</b></div>
        @endforeach
      </div>
      @if (!empty($s['bos']))<div class="bos" style="margin-bottom:14px"><b>Not:</b> {{ $s['bos'] }}</div>@endif
      {!! $gorsel ?? '' !!}
      <div class="grafikler">
        @foreach (array_filter($s['bloklar'], fn($b) => !empty($b['pdf']) || count($b['sat']) >= 2) as $bl)
          @php $vt = $bl['kol'][1][1]; $mx = max(1, ...array_map(fn($r) => abs((float) $r[1]), $bl['sat'])); $ozel = $bl['pdf'] ?? null; @endphp
          <div class="gk {{ $ozel || $bl['grafik'] === 'col' && count($bl['sat']) > 8 ? 'tam' : '' }}">
            <h3>{{ $bl['baslik'] }}</h3>
            @if ($ozel === 'seyir')
              {{-- alış alış fiyat çizgisi (sparkline) --}}
              <div class="seyir">
                @foreach ($bl['sat'] as $i => $r)
                  @php
                    $fy = $bl['ek'][$i] ?? []; $mn = $fy ? min($fy) : 0; $mxx = $fy ? max($fy) : 1; $ar = max(0.0001, $mxx - $mn); $nn = count($fy);
                    $pts = []; foreach ($fy as $j => $p) $pts[] = round($nn > 1 ? $j / ($nn - 1) * 150 + 4 : 79, 1) . ',' . round(26 - ($p - $mn) / $ar * 22, 1);
                  @endphp
                  <div><span class="n" title="{{ $r[0] }}">{{ $r[0] }}</span>
                    <svg width="158" height="30" viewBox="0 0 158 30"><polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="{{ $tema }}" stroke-width="2" stroke-linejoin="round"/>@foreach ($pts as $j => $pt)@php [$px, $py] = explode(',', $pt); @endphp<circle cx="{{ $px }}" cy="{{ $py }}" r="{{ $j === $nn - 1 ? 3 : 1.8 }}" fill="{{ $j === $nn - 1 ? $tema : '#94A3B8' }}"/>@endforeach</svg>
                    <span style="color:#64748B;font-size:9.5px">{{ $fy ? number_format($fy[0], 2, ',', '.') . ' → ' . number_format(end($fy), 2, ',', '.') . ' ₺' : '' }}</span>
                    <span class="y" style="color:{{ $tema }}">%{{ number_format($r[1], 1, ',', '.') }}</span></div>
                @endforeach
              </div>
            @elseif ($ozel === 'yanyana')
              {{-- her malzemede tedarikçi fiyatları yan yana --}}
              @foreach ($bl['sat'] as $i => $r)
                @php $tl = $bl['ek'][$i] ?? []; $mt = max(0.01, ...array_map(fn($t) => (float) $t[1], $tl ?: [[0, 1]])); @endphp
                <div class="yy"><div class="n">{{ $r[0] }} <span style="color:#64748B;font-weight:600">· en ucuz ile en pahalı arası %{{ number_format($r[1], 1, ',', '.') }}</span></div>
                  @foreach ($tl as $j => [$tad, $tfy])
                    <div class="s"><span class="t">{{ $tad }}</span><span class="cb"><i style="width:{{ max(3, $tfy / $mt * 100) }}%;background:{{ $j === 0 ? '#10B981' : $hexA($tema, .55) }}"></i></span><span class="v">{{ number_format($tfy, 2, ',', '.') }} ₺</span></div>
                  @endforeach
                </div>
              @endforeach
            @elseif ($ozel === 'karne')
              {{-- tedarikçi karne kartları --}}
              <div class="karneler">
                @foreach ($bl['ek'] as $kx)
                  @php $hr = ['A' => '#059669', 'B' => '#0EA5E9', 'C' => '#F59E0B', 'D' => '#E11D48'][$kx['harf'] ?? 'D'] ?? '#64748B'; @endphp
                  <div class="kk"><div class="harf" style="background:{{ $hr }}">{{ $kx['harf'] ?? '-' }}</div>
                    <div><div class="b">{{ $kx['ted'] }} · {{ $kx['puan'] ?? 0 }}/100</div>
                      <div class="d">{{ $tl0($kx['harcama']) }} · pay %{{ number_format($kx['pay'], 1, ',', '.') }} · {{ $kx['fatura'] }} fatura<br>{{ $kx['malzeme'] }} malzeme · zamlı {{ $kx['zamli'] }} · pahalı kaldığı {{ $kx['pahali'] }}</div></div></div>
                @endforeach
              </div>
            @elseif ($ozel === 'aralik')
              {{-- hal asgari–azami şeridi üstünde senin fiyatın --}}
              <div class="lejant"><span><i style="background:#BBF7D0;border-radius:2px"></i>hal asgari–azami</span><span><i style="background:#059669;width:3px;border-radius:0"></i>hal ortalaması</span><span><i style="background:{{ $tema }}"></i>senin son alışın</span><span><i style="background:#E11D48"></i>%20 üstü</span></div>
              @foreach ($bl['sat'] as $i => $r)
                @php
                  $h = $bl['ek'][$i]; $mn0 = $h['min'] > 0 ? $h['min'] : $h['ort']; $mx0 = $h['max'] > 0 ? $h['max'] : $h['ort'];
                  $lo = min($mn0, $h['sen']) * 0.92; $hi = max($mx0, $h['sen']) * 1.08; $sp = max(0.0001, $hi - $lo); $pos = fn($val) => round(($val - $lo) / $sp * 100, 1);
                @endphp
                <div class="aralik"><span class="n">{{ $r[0] }}</span>
                  <div class="ser"><div class="cizgi" style="left:{{ $pos($mn0) }}%;width:{{ max(1, $pos($mx0) - $pos($mn0)) }}%"></div><div class="ort" style="left:{{ $pos($h['ort']) }}%"></div>
                    <div class="sen" style="left:{{ $pos($h['sen']) }}%;background:{{ $r[2] > 20 ? '#E11D48' : $tema }}"></div></div>
                  <span class="v">{{ number_format($h['sen'], 2, ',', '.') }} ₺ / hal {{ number_format($h['ort'], 2, ',', '.') }} ₺ · <b>%{{ number_format($r[2], 0, ',', '.') }}</b></span></div>
              @endforeach
            @elseif ($bl['grafik'] === 'col' && min(array_map(fn($r) => (float) $r[1], $bl['sat'])) >= 0)
              {!! $grSutun(array_map(fn($r) => (string) $r[0], $bl['sat']), array_map(fn($r) => (float) $r[1], $bl['sat']), $vt, '#' . ltrim($bl['renk'] ?? $tema, '#')) !!}
            @elseif ($bl['grafik'] === 'pie' && count($bl['sat']) >= 2 && min(array_map(fn($r) => (float) $r[1], $bl['sat'])) >= 0)
              {!! $grHalka(array_map(fn($r) => (string) $r[0], $bl['sat']), array_map(fn($r) => (float) $r[1], $bl['sat']), $vt) !!}
            @else
              @foreach ($bl['sat'] as $i => $r)
                <div class="gs" data-tip="{{ $r[0] }}: {{ $blokDeg($r[1], $vt) }}{{ isset($r[2]) && $r[2] !== '' && $bl['kol'][2][0] !== '' ? ' · ' . $hucre($r[2], $bl['kol'][2][1]) : '' }}"><div class="n">{{ $r[0] }}</div>
                  <div class="t"><i style="width:{{ max(2, abs($r[1]) / $mx * 100) }}%;background:{{ $bl['grafik'] === 'pie' ? $palet[$i % 10] : '#' . $bl['renk'] }}"></i></div>
                  <div class="v">{{ $blokDeg($r[1], $vt) }}@if (isset($r[2]) && $r[2] !== '' && $bl['kol'][2][0] !== '') <span style="color:#94A3B8;font-weight:600">· {{ $hucre($r[2], $bl['kol'][2][1]) }}</span>@endif</div></div>
              @endforeach
            @endif
          </div>
        @endforeach
      </div>

      @if ($ai)
        {{-- ===== AI değerlendirmesi (yalnız AI raporu) ===== --}}
        <h2>Yapay zekâ değerlendirmesi</h2>
        @php $sk = max(0, min(100, (int) ($ai['skor'] ?? 0))); $skr = $sk >= 70 ? '#10B981' : ($sk >= 45 ? '#F59E0B' : '#F43F5E'); @endphp
        <div class="ozet">
          <div class="skor" style="background:conic-gradient({{ $skr }} {{ $sk * 3.6 }}deg,#EEF0F7 0)"><div>{{ $sk }}</div></div>
          <div><b>AI satın alma puanı: {{ $sk }}/100</b> · {{ $ai['_zaman'] ?? '' }}<br>{{ $ai['ozet'] ?? '' }}</div>
        </div>
        <div class="ai">
          @foreach (['kritik' => ['Kritik bulgular', '#E11D48'], 'gecmis_hatalar' => ['Geçmiş hatalar', '#D97706'], 'firsatlar' => ['Tasarruf fırsatları', '#059669'], 'gelecek' => ['Gelecek önerileri', '#4F46E5']] as $an => [$bas, $renk])
            @if (!empty($ai[$an]))
              <div class="k" style="--c:{{ $renk }}"><h3>{{ $bas }}</h3>
                @foreach ($ai[$an] as $xx)<div class="m"><b>{{ $xx['baslik'] ?? '' }}@if (!empty($xx['tutar']) && is_numeric($xx['tutar'])) · ~{{ $tl0($xx['tutar']) }}@endif</b>{{ $xx['detay'] ?? '' }}</div>@endforeach
              </div>
            @endif
          @endforeach
          @if (!empty($ai['pazarlik']))
            <div class="k" style="--c:#0284C7"><h3>Pazarlık argümanları</h3>@foreach ($ai['pazarlik'] as $xx)<div class="m"><b>{{ $xx['tedarikci'] ?? '' }}</b>{{ $xx['arguman'] ?? '' }}</div>@endforeach</div>
          @endif
          @if (!empty($ai['aksiyonlar']))
            <div class="k" style="--c:#9333EA"><h3>Önerilen aksiyonlar</h3>@foreach ($ai['aksiyonlar'] as $xx)<div class="m"><b>[{{ ['yuksek' => 'YÜKSEK', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$xx['oncelik'] ?? ''] ?? '' }}] {{ $xx['is'] ?? '' }}</b>{{ $xx['sorumlu'] ?? '' }}</div>@endforeach</div>
          @endif
        </div>
      @endif
      @continue
    @endif

    @continue($ai && !empty($s['ai']))
    {{-- ===== TABLO ===== --}}
    <h2>{{ $s['ad'] }}</h2>
    @if (!empty($s['not']))<p class="not">{{ $s['not'] }}</p>@endif
    @if (!count($s['sat']))
      <div class="bos"><b>Bu bölüm boş.</b> {{ $s['bos'] ?? 'Seçili dönem ve filtrelerle eşleşen kayıt yok.' }}</div>
    @else
      @php
        $duz = array_values(array_filter($s['sat'], fn($r) => !isset($r['_baslik']) && !isset($r['_ara'])));
        $mxK = []; $topK = []; $matMx = 0;
        foreach ($s['kol'] as $i => $k) {
          $b = $k[3] ?? '';
          if (str_contains($b, 'bar')) $mxK[$i] = max(1, ...array_map(fn($r) => abs((float) ($r[$i] ?? 0)), $duz ?: [[0]]));
          if (str_contains($b, 'top')) $topK[$i] = array_sum(array_map(fn($r) => (float) ($r[$i] ?? 0), $duz));
          if (str_contains($b, 'mat')) foreach ($duz as $r) $matMx = max($matMx, (float) ($r[$i] ?? 0));
        }
        $gTop = array_sum(array_map(fn($k) => $k[2] ?? 14, $s['kol'])); $grupTop = [];
        $oto = $otoGrafik($s['kol'], $duz);
      @endphp
      @if ($oto)
        <div class="grafikler tablo-ust">
          @foreach ($oto as $g)
            <div class="gk {{ count($oto) === 1 && $g['tur'] !== 'halka' ? 'tam' : '' }}"><h3>{{ $g['baslik'] }}</h3>{!! $grCiz($g, $tema) !!}</div>
          @endforeach
        </div>
      @endif
      <div class="tablo"><table class="{{ count($s['kol']) > 10 ? 'genis' : '' }}">
        <colgroup>@foreach ($s['kol'] as $k)<col style="width:{{ round(($k[2] ?? 14) / $gTop * 100, 2) }}%">@endforeach</colgroup>
        <thead><tr>@foreach ($s['kol'] as $k)<th class="{{ in_array($k[1], ['tl', 'y', 's'], true) ? 'r' : '' }}">{{ $k[0] }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach ($s['sat'] as $sat)
            @if (isset($sat['_baslik']))
              @php $grupTop = []; @endphp
              <tr class="grup"><td colspan="{{ count($s['kol']) }}">{{ $sat['_baslik'] }}</td></tr>
              @continue
            @endif
            @if (isset($sat['_ara']))
              <tr class="ara">@foreach ($s['kol'] as $i => $k)<td class="{{ isset($topK[$i]) ? 'r' : '' }}">{{ $i === 0 ? $sat['_ara'] : (isset($topK[$i]) ? $hucre($grupTop[$i] ?? 0, $k[1]) : '') }}</td>@endforeach</tr>
              @continue
            @endif
            <tr>@foreach ($s['kol'] as $i => $k)
              @php $val = $sat[$i] ?? null; $b = $k[3] ?? ''; $sag = in_array($k[1], ['tl', 'y', 's'], true); if (isset($topK[$i])) $grupTop[$i] = ($grupTop[$i] ?? 0) + (float) $val; @endphp
              @if (str_contains($b, 'mat'))
                <td class="r" style="background:{{ is_numeric($val) && $matMx > 0 ? $hexA($tema, round(0.06 + (float) $val / $matMx * 0.6, 2)) : 'transparent' }}">{{ $hucre($val, $k[1]) }}</td>
              @elseif (isset($mxK[$i]) && is_numeric($val))
                <td class="r cb"><div class="cbi" style="width:{{ round(abs($val) / $mxK[$i] * 100) }}%"></div><span>{{ $hucre($val, $k[1]) }}</span></td>
              @elseif (preg_match('/esik:([\d.]+)/', $b, $mm))
                <td class="{{ $sag ? 'r' : '' }}" style="{{ is_numeric($val) && $val >= (float) $mm[1] ? 'font-weight:900;color:#BE123C' : '' }}">{{ $hucre($val, $k[1]) }}</td>
              @elseif (str_contains($b, 'onc'))
                @php $oc = ['YÜKSEK' => ['#FFE4E6', '#9F1239'], 'ORTA' => ['#FEF3C7', '#92400E'], 'DÜŞÜK' => ['#F1F5F9', '#475569']][$val] ?? ['#F1F5F9', '#475569']; @endphp
                <td><span class="onc" style="background:{{ $oc[0] }};color:{{ $oc[1] }}">{{ $val }}</span></td>
              @else
                <td class="{{ $sag ? 'r' : '' }}">{{ $hucre($val, $k[1]) }}</td>
              @endif
            @endforeach</tr>
          @endforeach
          @if ($topK)
            <tr class="top">@foreach ($s['kol'] as $i => $k)<td class="{{ isset($topK[$i]) ? 'r' : '' }}">{{ $i === 0 ? 'TOPLAM' : (isset($topK[$i]) ? $hucre($topK[$i], $k[1]) : '') }}</td>@endforeach</tr>
          @endif
        </tbody>
      </table></div>
    @endif
  @endforeach
  <div class="alt">ResteOS · {{ $marka ?? 'Fiyat Radarı' }} · {{ $aiRapor ? 'Yapay zekâ analizi' : 'Sistem verisi (yorumsuz)' }} · {{ now()->format('d.m.Y H:i') }}</div>
</div>
<div class="ipucu" id="ipucu"></div>
<script>
  // grafik ipucu: çubuğun/dilimin tam değeri
  (function () {
    var ip = document.getElementById('ipucu');
    document.addEventListener('mousemove', function (e) {
      var t = e.target.closest ? e.target.closest('[data-tip]') : null;
      if (!t) { ip.style.display = 'none'; return; }
      ip.textContent = t.getAttribute('data-tip'); ip.style.display = 'block';
      var x = e.clientX + 14, y = e.clientY - 34;
      if (x + ip.offsetWidth > window.innerWidth - 8) x = e.clientX - ip.offsetWidth - 14;
      ip.style.left = x + 'px'; ip.style.top = Math.max(4, y) + 'px';
    });
  })();
</script>
@if (empty($onizle))<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>@endif
</body>
</html>

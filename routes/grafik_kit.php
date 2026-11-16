<?php
// =====================================================================
// GRAFİK KİTİ — sunucuda çizilen grafikler (HTML + SVG). Raporlar, PDF ve panel sayfaları ortak kullanır.
// Stil: public/grafik.css (açık/koyu tema değişkenleri). İpucu: _grJs().
// Kategorik renkler sabit sırada var(--k1..k8); sıra dışı kalan "Diğer" gri. Durum renkleri ayrı.
// =====================================================================

if (!function_exists('_grKisa')) {
    function _grKisa($v, $tip = 'tl')
    {
        $v = (float) $v; $a = abs($v);
        if ($tip === 'y') return ($v < 0 ? '-%' : '%') . number_format($a, 1, ',', '.');
        $s = $a >= 1e6 ? number_format($v / 1e6, 1, ',', '.') . ' mn' : ($a >= 1e4 ? number_format($v / 1e3, 1, ',', '.') . ' bin' : number_format($v, $a >= 100 || fmod($a, 1) == 0 ? 0 : 1, ',', '.'));
        return $tip === 'tl' ? $s . ' ₺' : $s;
    }
    function _grTam($v, $tip = 'tl')
    {
        $v = (float) $v;
        if ($tip === 'tl') return number_format($v, 2, ',', '.') . ' ₺';
        if ($tip === 'y') return ($v < 0 ? '-%' : '%') . number_format(abs($v), 1, ',', '.');
        return number_format($v, fmod($v, 1) ? 2 : 0, ',', '.');
    }
    function _grK($i) { return 'var(--k' . (($i % 8) + 1) . ')'; }
    function _grE($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
    // y ekseni için "yuvarlak" üst sınır
    function _grUst($mx)
    {
        if ($mx <= 0) return 1;
        $p = pow(10, floor(log10($mx))); $n = $mx / $p;
        foreach ([1, 1.2, 1.5, 2, 2.5, 3, 4, 5, 6, 8, 10] as $k) if ($n <= $k) return $k * $p;
        return 10 * $p;
    }
    function _grXEt(array $et, $sinif = 'gr-x')
    {
        $n = count($et); $adim = $n > 14 ? (int) ceil($n / 7) : ($n > 8 ? 2 : 1); $h = '<div class="' . $sinif . '">';
        foreach ($et as $i => $l) {
            $goster = $i === $n - 1 || ($i % $adim === 0 && $n - 1 - $i >= $adim);
            $h .= '<span>' . ($goster ? _grE($l) : '') . '</span>';
        }
        return $h . '</div>';
    }
    function _grIzgara($ust, $tip)
    {
        $h = '';
        foreach ([1, 0.5, 0] as $o) $h .= '<div class="gr-cz" style="bottom:' . ($o * 100) . '%"><span>' . _grE(_grKisa($ust * $o, $tip)) . '</span></div>';
        return $h;
    }
}

if (!function_exists('_grSutun')) {
    // Tek seri sütun: en yüksek ve son değer etiketli
    function _grSutun(array $et, array $dg, $tip = 'tl', $renk = 'var(--k1)', $yuk = 170)
    {
        $n = count($dg); if (!$n) return '';
        $ust = _grUst(max(0, ...array_map('floatval', $dg))); $imx = array_search(max($dg), $dg);
        $h = '<div class="gr-sutun" style="--c:' . $renk . '"><div class="gr-alan" style="height:' . $yuk . 'px">' . _grIzgara($ust, $tip) . '<div class="gr-bars">';
        foreach ($dg as $i => $v) {
            $yk = $v > 0 ? max(1.5, round($v / $ust * 100, 2)) : 0;
            $et_ = ($i === $imx || $i === $n - 1) && $v > 0 ? '<em style="bottom:calc(' . $yk . '% + 3px)' . ($i === $n - 1 && $n > 4 ? ';right:0' : '') . ($i === 0 && $n > 4 ? ';left:0' : '') . '">' . _grE(_grKisa($v, $tip)) . '</em>' : '';
            $h .= '<div class="gr-b" data-tip="' . _grE($et[$i] . ': ' . _grTam($v, $tip)) . '">' . $et_ . '<i style="height:' . $yk . '%;animation-delay:' . round($i * 0.025, 3) . 's"></i></div>';
        }
        return $h . '</div></div>' . _grXEt($et) . '</div>';
    }
}

if (!function_exists('_grYigin')) {
    // Yığılmış sütun: $seriler = [['ad' => .., 'dg' => [..]], ...]; en çok 6 seri + Diğer
    function _grYigin(array $et, array $seriler, $tip = 'tl', $yuk = 190)
    {
        $n = count($et); if (!$n || !$seriler) return '';
        $top = array_fill(0, $n, 0.0);
        foreach ($seriler as $s) foreach ($s['dg'] as $i => $v) $top[$i] += max(0, (float) $v);
        $ust = _grUst(max($top));
        $h = '<div class="gr-yigin"><div class="gr-lej">';
        foreach ($seriler as $j => $s) $h .= '<span><i style="background:' . ($s['renk'] ?? _grK($j)) . '"></i>' . _grE($s['ad']) . '</span>';
        $h .= '</div><div class="gr-alan" style="height:' . $yuk . 'px">' . _grIzgara($ust, $tip) . '<div class="gr-bars">';
        for ($i = 0; $i < $n; $i++) {
            $tipYazi = [$et[$i] . ' · toplam ' . _grTam($top[$i], $tip)];
            $par = '';
            foreach ($seriler as $j => $s) {
                $v = max(0, (float) ($s['dg'][$i] ?? 0)); if ($v <= 0) continue;
                $tipYazi[] = $s['ad'] . ': ' . _grTam($v, $tip);
                $par .= '<i style="height:' . round($v / $ust * 100, 3) . '%;background:' . ($s['renk'] ?? _grK($j)) . '"></i>';
            }
            $h .= '<div class="gr-b gr-yb" data-tip="' . _grE(implode("\n", $tipYazi)) . '"><div class="gr-yk" style="animation-delay:' . round($i * 0.03, 3) . 's">' . $par . '</div></div>';
        }
        return $h . '</div></div>' . _grXEt($et) . '</div>';
    }
}

if (!function_exists('_grCizgi')) {
    // Çizgi/alan: $seriler = [['ad', 'dg', 'renk', 'kesik' => bool, 'alan' => bool]]; tek eksen
    function _grCizgi(array $et, array $seriler, $tip = 'tl', $yuk = 200)
    {
        static $no = 0; $no++;
        $n = count($et); if ($n < 2 || !$seriler) return '';
        $hepsi = []; foreach ($seriler as $s) foreach ($s['dg'] as $v) if ($v !== null) $hepsi[] = (float) $v;
        $ust = _grUst(max(0, ...$hepsi));
        $x = fn($i) => round($i / ($n - 1) * 1000, 2); $y = fn($v) => round(100 - (float) $v / $ust * 100, 3);
        $h = '<div class="gr-cizgi">';
        if (count($seriler) > 1) {
            $h .= '<div class="gr-lej">';
            foreach ($seriler as $j => $s) $h .= '<span><i class="' . (!empty($s['kesik']) ? 'kesik' : '') . '" style="background:' . ($s['renk'] ?? _grK($j)) . '"></i>' . _grE($s['ad']) . '</span>';
            $h .= '</div>';
        }
        $h .= '<div class="gr-alan" style="height:' . $yuk . 'px">' . _grIzgara($ust, $tip);
        $svg = '<svg class="gr-svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><defs>';
        foreach ($seriler as $j => $s) $svg .= '<linearGradient id="grg' . $no . '_' . $j . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="' . ($s['renk'] ?? _grK($j)) . '" stop-opacity=".28"/><stop offset="1" stop-color="' . ($s['renk'] ?? _grK($j)) . '" stop-opacity="0"/></linearGradient>';
        $svg .= '</defs>'; $nok = '';
        foreach ($seriler as $j => $s) {
            $renk = $s['renk'] ?? _grK($j); $p = []; $son = null;
            foreach ($s['dg'] as $i => $v) { if ($v === null) continue; $p[] = $x($i) . ',' . $y($v); $son = [$i, $v]; }
            if (count($p) < 2) continue;
            if (!empty($s['alan'])) $svg .= '<path d="M' . explode(',', $p[0])[0] . ',100 L' . implode(' L', $p) . ' L' . explode(',', end($p))[0] . ',100 Z" fill="url(#grg' . $no . '_' . $j . ')" class="gr-dolgu"/>';
            $svg .= '<path d="M' . implode(' L', $p) . '" fill="none" stroke="' . $renk . '" stroke-width="' . (!empty($s['kesik']) ? 1.6 : 2.2) . '" vector-effect="non-scaling-stroke" stroke-linejoin="round" stroke-linecap="round"' . (!empty($s['kesik']) ? ' stroke-dasharray="5 4"' : ' class="gr-cyol"') . '/>';
            if ($son) $nok .= '<span class="gr-nok" style="left:' . ($son[0] / ($n - 1) * 100) . '%;bottom:' . ((float) $son[1] / $ust * 100) . '%;background:' . $renk . '"></span><em class="gr-sondeg" style="bottom:calc(' . ((float) $son[1] / $ust * 100) . '% + 6px)">' . _grE(_grKisa($son[1], $tip)) . '</em>';
        }
        $h .= $svg . '</svg>' . $nok . '<div class="gr-hov">';
        for ($i = 0; $i < $n; $i++) {
            $t = [$et[$i]]; foreach ($seriler as $s) if (($s['dg'][$i] ?? null) !== null) $t[] = $s['ad'] . ': ' . _grTam($s['dg'][$i], $tip);
            $h .= '<div data-tip="' . _grE(implode("\n", $t)) . '"></div>';
        }
        return $h . '</div></div>' . _grXEt($et) . '</div>';
    }
}

if (!function_exists('_grHalka')) {
    // Halka: en çok 7 dilim + Diğer, lejantta isim + değer + yüzde
    function _grHalka(array $et, array $dg, $tip = 'tl', $orta = 'Toplam')
    {
        $c = array_combine(array_map('strval', $et), array_map('floatval', $dg)); arsort($c);
        $dl = []; $i = 0; $diger = 0; $dsay = 0;
        foreach ($c as $ad => $v) { if ($i < 7 || count($c) === 8) $dl[] = [$ad, $v, _grK($i)]; else { $diger += $v; $dsay++; } $i++; }
        if ($dsay) $dl[] = ['Diğer (' . $dsay . ')', $diger, 'var(--gr-diger)'];
        $top = max(0.0001, array_sum(array_column($dl, 1))); $r = 54; $C = 2 * M_PI * $r; $bas = 0; $gap = count($dl) > 1 ? 2 : 0;
        $svg = '<svg class="gr-hsvg" viewBox="0 0 140 140" width="150" height="150"><circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="var(--gr-iz)" stroke-width="18"/>'; $lej = '';
        foreach ($dl as [$ad, $v, $renk]) {
            $uz = $v / $top * $C;
            if ($uz > $gap) $svg .= '<circle class="gr-dilim" cx="70" cy="70" r="' . $r . '" fill="none" stroke="' . $renk . '" stroke-width="18" stroke-dasharray="' . round($uz - $gap, 2) . ' ' . round($C - $uz + $gap, 2) . '" stroke-dashoffset="' . round(-$bas, 2) . '" transform="rotate(-90 70 70)" data-tip="' . _grE($ad . ': ' . _grTam($v, $tip)) . '"/>';
            $bas += $uz;
            $lej .= '<div data-tip="' . _grE($ad . ': ' . _grTam($v, $tip)) . '"><i style="background:' . $renk . '"></i><span class="a">' . _grE($ad) . '</span><b>' . _grE(_grKisa($v, $tip)) . '</b><span class="p">%' . number_format($v / $top * 100, 1, ',', '.') . '</span></div>';
        }
        $svg .= '<text x="70" y="66" text-anchor="middle" class="gr-ht1">' . _grE($orta) . '</text><text x="70" y="84" text-anchor="middle" class="gr-ht2">' . _grE(_grKisa($top, $tip)) . '</text></svg>';
        return '<div class="gr-halka">' . $svg . '<div class="gr-hlej">' . $lej . '</div></div>';
    }
}

if (!function_exists('_grCubuk')) {
    // Yatay çubuk; negatif varsa sıfır ortada (artış kırmızı, düşüş mavi). $ek: satır altı küçük not
    function _grCubuk(array $et, array $dg, $tip = 'tl', $renk = 'var(--k1)', array $ek = [])
    {
        if (!$dg) return '';
        $neg = min($dg) < 0; $mx = max(0.0001, ...array_map(fn($v) => abs((float) $v), $dg)); $h = '<div class="gr-cubuk">';
        foreach (array_values($dg) as $i => $v) {
            $w = round(abs($v) / $mx * ($neg ? 50 : 100), 2);
            $bar = $neg ? '<i style="' . ($v >= 0 ? 'left:50%' : 'right:50%') . ';width:' . $w . '%;background:' . ($v >= 0 ? 'var(--k8)' : 'var(--k1)') . '"></i><s></s>' : '<i style="left:0;width:' . max(1, $w) . '%;background:' . $renk . '"></i>';
            $h .= '<div class="gr-cs" data-tip="' . _grE($et[$i] . ': ' . _grTam($v, $tip) . (isset($ek[$i]) && $ek[$i] !== '' ? ' · ' . $ek[$i] : '')) . '"><span class="n">' . _grE($et[$i]) . '</span><span class="t">' . $bar . '</span><b>' . _grE(_grKisa($v, $tip)) . '</b></div>';
        }
        return $h . '</div>';
    }
}

if (!function_exists('_grIsi')) {
    // Takvim ısı haritası: $gun = ['Y-m-d' => değer], hafta sütunları × 7 gün; tek renk tonlaması (az → çok)
    function _grIsi(array $gun, $bas, $bit, $tip = 'tl')
    {
        $b = strtotime('monday this week', strtotime($bas)); $e = strtotime($bit);
        $mx = max(0.0001, ...array_values($gun ?: [0])); $ton = ['var(--s1)', 'var(--s2)', 'var(--s3)', 'var(--s4)', 'var(--s5)'];
        $ay = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $kol = ''; $ust = ''; $sonAy = null; $hafta = 0;
        for ($w = $b; $w <= $e; $w = strtotime('+1 week', $w)) {
            $m = (int) date('n', $w); $ust .= '<span>' . ($m !== $sonAy ? $ay[$m] : '') . '</span>'; $sonAy = $m;
            $kol .= '<div class="gr-iw">';
            for ($d = 0; $d < 7; $d++) {
                $t = strtotime("+$d day", $w); $k = date('Y-m-d', $t);
                if ($t < strtotime($bas) || $t > $e) { $kol .= '<i class="gr-yok"></i>'; continue; }
                $v = (float) ($gun[$k] ?? 0); $sev = $v > 0 ? min(4, (int) floor(sqrt($v / $mx) * 4.999)) : -1;
                $kol .= '<i style="background:' . ($sev < 0 ? 'var(--gr-iz)' : $ton[$sev]) . '" data-tip="' . _grE(date('d.m.Y', $t) . ' ' . ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'][$d] . ': ' . _grTam($v, $tip)) . '"></i>';
            }
            $kol .= '</div>'; $hafta++;
        }
        $lej = '<div class="gr-ilej">Az <i style="background:var(--gr-iz)"></i>'; foreach ($ton as $t) $lej .= '<i style="background:' . $t . '"></i>'; $lej .= ' Çok</div>';
        return '<div class="gr-isi"><div class="gr-iust" style="--h:' . $hafta . '">' . $ust . '</div><div class="gr-igov"><div class="gr-igun"><span>Pzt</span><span></span><span>Çar</span><span></span><span>Cum</span><span></span><span>Paz</span></div><div class="gr-ihafta" style="--h:' . $hafta . '">' . $kol . '</div></div>' . $lej . '</div>';
    }
}

if (!function_exists('_grPareto')) {
    // ABC eğrisi: kalemlerin yüzde kaçı harcamanın yüzde kaçını yapıyor (tek eksen: yüzde)
    function _grPareto(array $dg)
    {
        $dg = array_values(array_filter(array_map('floatval', $dg), fn($v) => $v > 0)); rsort($dg); $n = count($dg);
        if ($n < 3) return '';
        $top = array_sum($dg); $kum = 0; $p = ['0,100']; $sinir = ['A' => null, 'B' => null];
        foreach ($dg as $i => $v) {
            $kum += $v; $yuz = $kum / $top * 100; $xx = ($i + 1) / $n * 1000;
            $p[] = round($xx, 2) . ',' . round(100 - $yuz, 3);
            if ($sinir['A'] === null && $yuz >= 80) $sinir['A'] = $i + 1;
            if ($sinir['B'] === null && $yuz >= 95) $sinir['B'] = $i + 1;
        }
        $a = $sinir['A'] ?? $n; $bb = max($a, $sinir['B'] ?? $n);
        $xa = $a / $n * 100; $xb = $bb / $n * 100;
        $h = '<div class="gr-pareto"><div class="gr-alan" style="height:190px">';
        foreach ([1, 0.5, 0] as $o) $h .= '<div class="gr-cz" style="bottom:' . ($o * 100) . '%"><span>%' . ($o * 100) . '</span></div>';
        $h .= '<div class="gr-bant a" style="left:0;width:' . $xa . '%"><b>A</b></div><div class="gr-bant b" style="left:' . $xa . '%;width:' . ($xb - $xa) . '%"><b>B</b></div><div class="gr-bant c" style="left:' . $xb . '%;width:' . (100 - $xb) . '%"><b>C</b></div>';
        $h .= '<svg class="gr-svg" viewBox="0 0 1000 100" preserveAspectRatio="none"><path d="M' . implode(' L', $p) . ' L1000,100 L0,100 Z" fill="var(--k1)" fill-opacity=".12"/><path d="M' . implode(' L', $p) . '" fill="none" stroke="var(--k1)" stroke-width="2.2" vector-effect="non-scaling-stroke" class="gr-cyol"/></svg>';
        $h .= '</div><div class="gr-x"><span style="text-align:left">ilk kalem</span><span style="text-align:right">tüm kalemler (' . $n . ')</span></div>';
        $h .= '<div class="gr-abc"><span><b class="a">A</b> ' . $a . ' kalem (%' . number_format($a / $n * 100, 0, ',', '.') . ') → harcamanın %80\'i</span><span><b class="b">B</b> ' . ($bb - $a) . ' kalem → %15</span><span><b class="c">C</b> ' . ($n - $bb) . ' kalem → %5</span></div></div>';
        return $h;
    }
}

if (!function_exists('_grAgac')) {
    // Kutu haritası (squarified): $ogeler = [['ad', 'deger', 'grup']]; renk gruba göre (en çok 7 grup + diğer)
    function _grAgac(array $ogeler, $tip = 'tl', $yuk = 260)
    {
        $ogeler = array_values(array_filter($ogeler, fn($o) => $o['deger'] > 0)); if (count($ogeler) < 2) return '';
        usort($ogeler, fn($a, $b) => $b['deger'] <=> $a['deger']);
        $grT = []; foreach ($ogeler as $o) $grT[$o['grup']] = ($grT[$o['grup']] ?? 0) + $o['deger']; arsort($grT);
        $grRenk = []; $i = 0; foreach ($grT as $g => $_) { $grRenk[$g] = $i < 7 ? _grK($i) : 'var(--gr-diger)'; $i++; }
        $W = 200; $H = 100; $top = array_sum(array_column($ogeler, 'deger')); $alan = array_map(fn($o) => $o['deger'] / $top * $W * $H, $ogeler);
        $kutular = []; $x = 0; $y = 0; $w = $W; $h = $H; $sira = []; $i = 0; $N = count($alan);
        $kotu = function (array $r, $kenar) { $s = array_sum($r); if ($s <= 0) return INF; $mx = max($r); $mn = min($r); return max($kenar * $kenar * $mx / ($s * $s), ($s * $s) / ($kenar * $kenar * $mn)); };
        $yerlestir = function (array $r, array $idx) use (&$x, &$y, &$w, &$h, &$kutular) {
            $s = array_sum($r);
            if ($w >= $h) { $kw = $s / $h; $yy = $y; foreach ($r as $j => $a) { $kh = $a / $kw; $kutular[$idx[$j]] = [$x, $yy, $kw, $kh]; $yy += $kh; } $x += $kw; $w -= $kw; }
            else { $kh = $s / $w; $xx = $x; foreach ($r as $j => $a) { $kw = $a / $kh; $kutular[$idx[$j]] = [$xx, $y, $kw, $kh]; $xx += $kw; } $y += $kh; $h -= $kh; }
        };
        $r = []; $idx = [];
        while ($i < $N) {
            $kenar = min($w, $h); $a = $alan[$i];
            if (!$r || $kotu(array_merge($r, [$a]), $kenar) <= $kotu($r, $kenar)) { $r[] = $a; $idx[] = $i; $i++; }
            else { $yerlestir($r, $idx); $r = []; $idx = []; }
        }
        if ($r) $yerlestir($r, $idx);
        $out = '<div class="gr-agac" style="height:' . $yuk . 'px">';
        foreach ($ogeler as $j => $o) {
            [$kx, $ky, $kw, $kh] = $kutular[$j]; $buyuk = $kw * $kh > 900; $orta = $kw * $kh > 350;
            $out .= '<div style="left:' . round($kx / $W * 100, 3) . '%;top:' . round($ky / $H * 100, 3) . '%;width:' . round($kw / $W * 100, 3) . '%;height:' . round($kh / $H * 100, 3) . '%;background:' . $grRenk[$o['grup']] . '" data-tip="' . _grE($o['ad'] . ' · ' . $o['grup'] . ': ' . _grTam($o['deger'], $tip) . ' (%' . number_format($o['deger'] / $top * 100, 1, ',', '.') . ')') . '">'
                . ($orta ? '<span>' . _grE($o['ad']) . '</span>' : '') . ($buyuk ? '<b>' . _grE(_grKisa($o['deger'], $tip)) . '</b>' : '') . '</div>';
        }
        $out .= '</div><div class="gr-lej" style="margin-top:8px">';
        $i = 0; foreach ($grT as $g => $v) { if ($i++ >= 8) break; $out .= '<span><i style="background:' . $grRenk[$g] . '"></i>' . _grE($g) . ' · ' . _grE(_grKisa($v, $tip)) . '</span>'; }
        return $out . '</div>';
    }
}

if (!function_exists('_grIbre')) {
    // Yarım daire gösterge (örn. bütçe kullanımı). Ölçek 0–120%; 100'de çizgi. Renk + yazı birlikte (renk tek başına anlam taşımaz)
    function _grIbre($oran, $alt = '', $esikler = [80, 100])
    {
        $o = max(0, (float) $oran); $oo = min(120, $o) / 120;
        [$renk, $durum] = $o >= $esikler[1] ? ['#d03b3b', 'Aşıldı'] : ($o >= $esikler[0] ? ['#fab219', 'Sınıra yakın'] : ['#0ca30c', 'Yolunda']);
        $yay = function ($t0, $t1) { $a0 = M_PI * (1 - $t0); $a1 = M_PI * (1 - $t1); return 'M' . round(100 + 80 * cos($a0), 2) . ',' . round(100 - 80 * sin($a0), 2) . ' A80,80 0 0,1 ' . round(100 + 80 * cos($a1), 2) . ',' . round(100 - 80 * sin($a1), 2); };
        $c = 100 / 120; $a = M_PI * (1 - $c);
        return '<div class="gr-ibre"><svg viewBox="0 0 200 112" width="220" height="124"><path d="' . $yay(0, 1) . '" stroke="var(--gr-iz)" stroke-width="16" fill="none" stroke-linecap="round"/>'
            . ($oo > 0.005 ? '<path d="' . $yay(0, $oo) . '" stroke="' . $renk . '" stroke-width="16" fill="none" stroke-linecap="round" pathLength="1" class="gr-yol"/>' : '')
            . '<line x1="' . round(100 + 68 * cos($a), 2) . '" y1="' . round(100 - 68 * sin($a), 2) . '" x2="' . round(100 + 94 * cos($a), 2) . '" y2="' . round(100 - 94 * sin($a), 2) . '" stroke="var(--gr-ink)" stroke-width="2"/>'
            . '<text x="100" y="88" text-anchor="middle" class="gr-it1">%' . number_format($o, 0, ',', '.') . '</text><text x="100" y="106" text-anchor="middle" class="gr-it2">' . _grE($durum) . '</text></svg>'
            . ($alt !== '' ? '<div class="gr-ialt">' . $alt . '</div>' : '') . '</div>';
    }
}

if (!function_exists('_grKpi')) {
    // KPI kutusu: değer + önceki döneme göre ▲▼ (yazıyla) + mini trend. $iyi: 'asagi' (harcama düşmesi iyi) | 'yukari'
    function _grKpi($ad, $deger, $degisim = null, array $spark = [], $iyi = 'asagi', $renk = 'var(--k1)', $alt = '')
    {
        $d = '';
        if ($degisim !== null) {
            $yon = $degisim > 0.05 ? 'up' : ($degisim < -0.05 ? 'down' : 'flat');
            $iyiMi = $yon === 'flat' ? null : (($yon === 'down') === ($iyi === 'asagi'));
            $d = '<span class="gr-kd ' . ($iyiMi === null ? 'n' : ($iyiMi ? 'i' : 'k')) . '">' . ($yon === 'up' ? '▲' : ($yon === 'down' ? '▼' : '■')) . ' %' . number_format(abs($degisim), 1, ',', '.') . '<small> önceki döneme göre</small></span>';
        }
        $s = '';
        if (count($spark) >= 2) {
            $mx = max(0.0001, ...$spark); $n = count($spark); $p = [];
            foreach ($spark as $i => $v) $p[] = round($i / ($n - 1) * 100, 2) . ',' . round(30 - $v / $mx * 26, 2);
            $s = '<svg class="gr-spark" viewBox="0 0 100 32" preserveAspectRatio="none"><path d="M0,32 L' . implode(' L', $p) . ' L100,32 Z" fill="' . $renk . '" fill-opacity=".14"/><path d="M' . implode(' L', $p) . '" fill="none" stroke="' . $renk . '" stroke-width="1.8" vector-effect="non-scaling-stroke"/></svg>';
        }
        return '<div class="gr-kpi"><span class="e">' . _grE($ad) . '</span><b>' . _grE($deger) . '</b>' . $d . ($alt !== '' ? '<span class="a">' . _grE($alt) . '</span>' : '') . $s . '</div>';
    }
}

if (!function_exists('_grJs')) {
    // Fare ipucu (data-tip); bir sayfada bir kez basılır
    function _grJs()
    {
        static $basildi = false; if ($basildi) return ''; $basildi = true;
        return '<div class="gr-ipucu" id="grIpucu"></div><script>(function(){var ip=document.getElementById("grIpucu");document.addEventListener("mousemove",function(e){var t=e.target.closest?e.target.closest("[data-tip]"):null;if(!t){ip.style.display="none";return;}ip.textContent=t.getAttribute("data-tip");ip.style.display="block";var x=e.clientX+14,y=e.clientY-ip.offsetHeight-10;if(x+ip.offsetWidth>window.innerWidth-8)x=e.clientX-ip.offsetWidth-14;ip.style.left=x+"px";ip.style.top=Math.max(4,y)+"px";});})();</script>';
    }
}

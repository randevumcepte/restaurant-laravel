<?php
/*
 * ============================================================================
 *  PDF / FOTOĞRAF FATURA OKUMA — ücretsiz-öncelikli katmanlı motor (efatura_gelen.php'den require)
 *  1) Kural motoru (0 TL): PDF müşterinin TARAYICISINDA pdf.js ile açılır, metin koordinatlarıyla gelir
 *     ([metin, x, y(üstten), genişlik]); tablo başlığından (Miktar / Birim Fiyat / Tutar ...) sütunlar bulunur.
 *     Taranmış sayfa/fotoğraf: tarayıcıda Tesseract.js (OCR, 0 TL) → aynı biçim → aynı motor.
 *  2) Tedarikçi şablonu (0 TL): başlığı tanınmayan düzen için tedarikçi VKN'sine bağlı sütun konumları
 *     (fatura_pdf_sablon). Şablon TÜM işletmelerde ortaktır — bir tedarikçi bir kez öğrenilir.
 *  3) Yapay zekâ (kotalı, isteğe bağlı): yalnız okunamayan sayfa için ve kullanıcı isterse. Sonucundan şablon
 *     çıkarılır → o tedarikçinin sonraki faturaları yine 0 TL.
 *  Her fatura: satır kontrolü (miktar × fiyat − iskonto = tutar) + toplam kontrolü; tutmayan otomatik aktarılmaz.
 *  Okunan fatura e-fatura ile AYNI akışa girer (_efgKaydetVeri): eşleştirme hafızası, alış faturası, denetim.
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_fpEnsure')) {
    function _fpEnsure()
    {
        if (cache()->get('fatura_pdf_ensure_ok1')) return;
        try {
            if (!Schema::hasTable('fatura_pdf_sablon')) {
                Schema::create('fatura_pdf_sablon', function ($t) {
                    $t->id();
                    $t->string('satici_vkn', 16)->unique();
                    $t->text('kolonlar');                               // {"miktar":241,"fiyat":277,...} sütun sol x'leri
                    $t->string('kaynak', 10)->default('kural');         // kural | ai
                    $t->integer('kullanim')->default(0);
                    $t->timestamps();
                });
            }
            cache()->put('fatura_pdf_ensure_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('_fpKucuk')) {
    function _fpKucuk($s) { return mb_strtolower(strtr((string) $s, ['I' => 'ı', 'İ' => 'i']), 'UTF-8'); }
}
// Türkçe biçimli sayı: "1.450,00 TL" → 1450.0 · "5,0" → 5.0 · "% 20,00" → 20.0
if (!function_exists('_fpSayi')) {
    function _fpSayi($s)
    {
        $s = str_replace(['TL', '₺', 'TRY', '%', ' ', "\u{00A0}"], '', (string) $s);
        if (!preg_match('/-?\d[\d.,]*/', $s, $m)) return null;
        $n = rtrim($m[0], '.,');
        if (str_contains($n, ',')) $n = str_replace(['.', ','], ['', '.'], $n);
        elseif (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $n)) $n = str_replace('.', '', $n); // virgülsüz binlik: 1.450
        return is_numeric($n) ? (float) $n : null;
    }
}
if (!function_exists('_fpBirimKod')) {
    function _fpBirimKod($t)
    {
        $t = _fpKucuk(trim((string) $t, " .\t"));
        $h = ['adet' => 'C62', 'ad' => 'C62', 'adt' => 'C62', 'kg' => 'KGM', 'kilogram' => 'KGM', 'kgm' => 'KGM', 'gr' => 'GRM', 'g' => 'GRM', 'gram' => 'GRM',
            'lt' => 'LTR', 'l' => 'LTR', 'litre' => 'LTR', 'ltr' => 'LTR', 'ml' => 'MLT', 'paket' => 'PA', 'pk' => 'PA', 'pkt' => 'PA', 'koli' => 'BX', 'kl' => 'BX',
            'kutu' => 'CA', 'kasa' => 'CS', 'düzine' => 'DZN', 'şişe' => 'BO', 'sise' => 'BO', 'rulo' => 'RO', 'torba' => 'BG', 'çuval' => 'BG', 'metre' => 'MTR',
            'mt' => 'MTR', 'm' => 'MTR', 'ton' => 'TNE', 'set' => 'SET', 'takım' => 'SET', 'çift' => 'PR', 'bidon' => 'CA', 'teneke' => 'CA', 'demet' => 'BG', 'bağ' => 'BG'];
        if (isset($h[$t])) return $h[$t];
        $kod = strtoupper($t); // birim yerine UBL kodu basan şablonlar ("RO", "KGM", "C62")
        return function_exists('_efgBirimHarita') && isset(_efgBirimHarita()[$kod]) ? $kod : null;
    }
}

// Sayfa öğelerini satırlara topla: [['y'=>, 'o'=>[[metin,x,y,w],...], 't'=>'...'], ...]
if (!function_exists('_fpSatirlar')) {
    function _fpSatirlar(array $o)
    {
        $o = array_values(array_filter($o, fn ($i) => is_array($i) && count($i) >= 4 && trim((string) $i[0]) !== ''));
        usort($o, fn ($a, $b) => $a[2] <=> $b[2] ?: $a[1] <=> $b[1]);
        $sat = []; $cur = null;
        foreach ($o as $i) {
            $i = [trim((string) $i[0]), (float) $i[1], (float) $i[2], (float) $i[3]];
            if ($cur && abs($i[2] - $cur['y']) <= 2.5) { $cur['o'][] = $i; continue; }
            if ($cur) $sat[] = $cur;
            $cur = ['y' => $i[2], 'o' => [$i]];
        }
        if ($cur) $sat[] = $cur;
        foreach ($sat as &$s) { usort($s['o'], fn ($a, $b) => $a[1] <=> $b[1]); $s['t'] = implode(' ', array_column($s['o'], 0)); }
        return $sat;
    }
}

// Tablo başlığından sütunlar: ['miktar' => solX, ...] + başlık bandının y'si
if (!function_exists('_fpBaslik')) {
    function _fpBaslik(array $sat)
    {
        foreach ($sat as $i => $s) {
            if (!str_contains(_fpKucuk($s['t']), 'miktar')) continue;
            // başlık bandı: ±9 pt içindeki sayısız satırlar (2-3 satıra yayılan "KDV / Oranı", "Mal Hizmet / Tutarı" başlıkları)
            $bant = []; $bantT = '';
            foreach ($sat as $s2) if (abs($s2['y'] - $s['y']) <= 9 && !preg_match('/\d{2,}[.,]\d/u', $s2['t'])) { foreach ($s2['o'] as $it) $bant[] = $it; $bantT .= ' ' . _fpKucuk($s2['t']); }
            if (!str_contains($bantT, 'fiyat') || !preg_match('/tutar|toplam/u', $bantT)) continue;
            $s['y'] = max(array_column($bant, 2));
            usort($bant, fn ($a, $b) => $a[1] <=> $b[1]);
            $gr = [];
            foreach ($bant as $it) {
                $x0 = $it[1]; $x1 = $it[1] + max($it[3], 4); $kat = false;
                foreach ($gr as &$g) if ($x0 < $g['x1'] - 1 && $x1 > $g['x0'] + 1) { $g['x0'] = min($g['x0'], $x0); $g['x1'] = max($g['x1'], $x1); $g['p'][] = $it; $kat = true; break; }
                unset($g);
                if (!$kat) $gr[] = ['x0' => $x0, 'x1' => $x1, 'p' => [$it]];
            }
            $kol = [];
            foreach ($gr as $g) {
                usort($g['p'], fn ($a, $b) => $a[2] <=> $b[2]);
                $l = _fpKucuk(implode(' ', array_column($g['p'], 0)));
                $tip = null;
                if (str_contains($l, 'isk')) $tip = str_contains($l, 'tutar') ? 'isk_tutar' : 'isk_oran';
                elseif (str_contains($l, 'kdv') || str_contains($l, 'vergi')) $tip = str_contains($l, 'tutar') ? 'kdv_tutar' : 'kdv_oran';
                elseif (str_contains($l, 'miktar')) $tip = 'miktar';
                elseif (str_contains($l, 'fiyat')) $tip = 'fiyat';
                elseif (preg_match('/^birim$/u', trim($l))) $tip = 'birim';
                elseif (preg_match('/tutar|toplam/u', $l)) $tip = 'tutar';
                elseif (preg_match('/kod/u', $l)) $tip = 'kod';
                elseif (preg_match('/açıklama|aciklama|mal|hizmet|ürün|urun|cins|tanım/u', $l)) $tip = 'ad';
                elseif (preg_match('/sıra|^no$/u', $l)) $tip = 'sira';
                if ($tip && $tip !== 'tutar' && isset($kol[$tip])) continue;
                if ($tip) $kol[$tip] = round($g['x0'], 1); // 'tutar' birden çoksa en sağdaki kalır
            }
            if (isset($kol['miktar'], $kol['tutar'])) return ['kol' => $kol, 'y' => $s['y'], 'i' => $i];
        }
        return null;
    }
}

// Tek sayfayı çöz: üst bilgi + kalemler + toplamlar. $sablon: başlık yoksa kullanılacak sütunlar.
if (!function_exists('_fpSayfa')) {
    function _fpSayfa(array $sayfa, $sablonlar = [], $oncekiKol = null)
    {
        $sat = _fpSatirlar($sayfa['o'] ?? []);
        $hepsi = implode("\n", array_column($sat, 't'));
        $u = [];
        if (preg_match('/Fatura\s*No\s*:?\s*([A-Z0-9ĞÜŞİÖÇ]{3}20\d{11})/u', $hepsi, $m) || preg_match('/Fatura\s*(?:No|Numarası)\s*:?\s*([A-Z0-9][A-Z0-9\-\/]{3,30})/u', $hepsi, $m)) $u['fatura_no'] = $m[1];
        if (preg_match('/ETTN\s*:?\s*([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/u', $hepsi, $m)) $u['ettn'] = strtolower($m[1]);
        $tarih = function ($etiket) use ($hepsi) {
            if (!preg_match('/' . $etiket . '\s*:?\s*(\d{1,2})\s*[-.\/]\s*(\d{1,2})\s*[-.\/]\s*(\d{4})/u', $hepsi, $m)) return null;
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        };
        $u['tarih'] = $tarih('(?:Fatura|Düzenleme)\s*Tarihi');
        $u['vade_tarihi'] = $tarih('(?:Vade|Son\s*Ödeme)\s*Tarihi');
        if (preg_match('/Senaryo\s*:?\s*([A-ZİÜĞŞÖÇ]+)/u', $hepsi, $m)) $u['profil'] = $m[1];
        if (preg_match('/Fatura\s*Tipi\s*:?\s*([A-ZİÜĞŞÖÇ]+)/u', $hepsi, $m)) $u['tip'] = $m[1];
        // VKN'ler: ilki satıcı, "SAYIN"dan sonraki alıcı
        $sayinY = null; $vknler = [];
        foreach ($sat as $s) {
            if ($sayinY === null && preg_match('/^SAYIN\b/u', trim($s['t']))) $sayinY = $s['y'];
            if (preg_match('/\b(?:VKN|TCKN|Vergi\s*(?:Kimlik\s*)?No)\b[^0-9]{0,12}(\d{10,11})/u', $s['t'], $m)) $vknler[] = [$s['y'], $m[1]];
        }
        foreach ($vknler as [$y, $v]) {
            if ($sayinY !== null && $y > $sayinY) { $u['alici_vkn'] ??= $v; } else { $u['satici_vkn'] ??= $v; }
        }
        // Satıcı adı: ilk VKN'den önceki blokta şirket ekli en uzun satır
        if (!empty($u['satici_vkn'])) {
            $vy = null; foreach ($vknler as [$y, $v]) if ($v === $u['satici_vkn']) { $vy = $y; break; }
            $aday = null; $puan = -1; $adayI = null;
            $sirket = '/(LTD|ŞTİ|STI|A\.?\s?Ş\.?|ANONİM|LİMİTED|SAN\.|TİC\.|GIDA|TİCARET)/u';
            foreach ($sat as $i => $s) {
                if ($vy !== null && $s['y'] >= $vy) break;
                $t = trim($s['t']);
                if (preg_match('/https?:|www\.|\.jsp|\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}/u', $t) || mb_strlen($t) < 6) continue;
                $p = mb_strlen($t) + (preg_match($sirket, $t) ? 100 : 0) - (preg_match('/SOKAK|SOK\.|CAD|MAH|NO:|\/\s*[A-ZİĞÜŞÖÇ]+$|Mersis|Sicil|Vergi/u', $t) ? 80 : 0);
                if ($p > $puan) { $puan = $p; $aday = $t; $adayI = $i; }
            }
            // iki satıra bölünmüş unvan: "... MEDİKAL DIŞ" + "TİCARET LTD.ŞTİ."
            if ($adayI !== null && isset($sat[$adayI + 1]) && $sat[$adayI + 1]['y'] - $sat[$adayI]['y'] < 12) {
                $sonraki = trim($sat[$adayI + 1]['t']);
                if (preg_match($sirket, $sonraki) && !preg_match('/SOKAK|CAD|MAH|NO:|Mersis|Sicil|Vergi|VKN/u', $sonraki) && mb_strlen($sonraki) < 60) $aday .= ' ' . $sonraki;
            }
            $u['satici_ad'] = $aday;
        }
        // Toplamlar (etiketli satırdaki EN SAĞDAKİ tutar)
        $sonTutar = function ($s) { for ($i = count($s['o']) - 1; $i >= 0; $i--) { $n = _fpSayi($s['o'][$i][0]); if ($n !== null && preg_match('/\d[.,]\d/u', $s['o'][$i][0])) return $n; } return null; };
        $top = ['kdv' => 0.0, 'kdv_var' => false]; $sonK = null;
        $topY = null;
        foreach ($sat as $s) {
            $t = _fpKucuk($s['t']); $v = null;
            $k = null;
            if (preg_match('/mal\s*\/?\s*hizmet\s*toplam|ara\s*toplam|toplam\s*mal\s*hizmet/u', $t)) $k = 'ara';
            elseif (preg_match('/toplam\s*iskonto|iskonto\s*toplam/u', $t)) $k = 'isk';
            elseif (preg_match('/hesaplanan\s*(kdv|katma)|toplam\s*kdv|kdv\s*toplam/u', $t)) $k = 'kdv';
            elseif (preg_match('/vergiler\s*dahil|kdv\s*dahil\s*toplam|genel\s*toplam/u', $t)) $k = 'vergili';
            elseif (preg_match('/ödenecek|odenecek/u', $t)) $k = 'odenecek';
            elseif ($sonK === 'kdv' && preg_match('/^\(?\s*%\s*\d/u', trim($t))) $k = 'kdv'; // etiketsiz ikinci oran: "(%10) 76,78 TL"
            $sonK = $k;
            if (!$k || ($v = $sonTutar($s)) === null) continue;
            $topY ??= $s['y'];
            if ($k === 'kdv') { $top['kdv'] += $v; $top['kdv_var'] = true; } else $top[$k] ??= $v;
        }
        // Tablo
        $b = _fpBaslik($sat); $kol = $b['kol'] ?? null; $sablonla = false;
        if (!$kol && $oncekiKol) $kol = $oncekiKol; // başlıksız devam sayfası: önceki sayfanın sütunları
        if (!$kol && !empty($u['satici_vkn']) && isset($sablonlar[$u['satici_vkn']])) { $kol = $sablonlar[$u['satici_vkn']]; $sablonla = true; }
        $kalemler = [];
        if ($kol) {
            asort($kol);
            $sayisal = array_intersect_key($kol, array_flip(['miktar', 'birim', 'fiyat', 'isk_oran', 'isk_tutar', 'kdv_oran', 'kdv_tutar', 'tutar']));
            // sağa yaslı geniş miktarlar ("125,0") sütun başlığının biraz solundan başlar
            $metinSinir = min($sayisal) - 18;
            $basY = $b['y'] ?? -1; $bitY = $topY ?? INF;
            $adaylar = []; $parcalar = []; $serbest = [];
            foreach ($sat as $s) {
                if ($s['y'] <= $basY + 3 || $s['y'] >= $bitY - 1) continue;
                if (preg_match('/https?:|www\.|\.jsp|^\s*\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}/u', $s['t'])) continue; // tarayıcı yazdırma üst/alt bilgisi
                $hucre = []; $metin = [];
                foreach ($s['o'] as $it) {
                    if ($it[1] < $metinSinir) { $metin[] = $it; continue; }
                    $sec = null; foreach ($sayisal as $ad => $x) if ($it[1] + 7 >= $x) $sec = $ad;
                    $hucre[$sec ?? 'miktar'][] = $it[0];
                }
                $mk = _fpSayi(implode(' ', $hucre['miktar'] ?? []));
                if ($mk !== null && $mk > 0) $adaylar[] = ['y' => $s['y'], 'metin' => $metin, 'hucre' => $hucre];
                elseif ($hucre) $parcalar[] = ['y' => $s['y'], 'hucre' => $hucre, 'metin' => $metin];
                elseif ($metin) $serbest[] = ['y' => $s['y'], 'metin' => $metin];
            }
            // Satırın birkaç punto üstüne/altına yazılmış tutar parçaları ("2.760,00" / "TL") en yakın satıra
            foreach ($parcalar as $p) {
                $en = null; $fark = 8;
                foreach ($adaylar as $i => $a) if (abs($a['y'] - $p['y']) < $fark) { $fark = abs($a['y'] - $p['y']); $en = $i; }
                if ($en === null) { if ($p['metin']) $serbest[] = ['y' => $p['y'], 'metin' => $p['metin']]; continue; }
                foreach ($p['hucre'] as $k => $v) $adaylar[$en]['hucre'][$k] = array_merge($adaylar[$en]['hucre'][$k] ?? [], $v);
                foreach ($p['metin'] as $it) $adaylar[$en]['metin'][] = $it;
            }
            $satirlar = [];
            foreach ($adaylar as $a) {
                $h = $a['hucre']; $j = fn ($k) => implode(' ', $h[$k] ?? []);
                $tt = _fpSayi($j('tutar'));
                if ($tt === null) { if ($a['metin']) $serbest[] = ['y' => $a['y'], 'metin' => $a['metin']]; continue; }
                $birimT = trim($j('birim') ?: preg_replace('/[\d.,\s]+/u', ' ', $j('miktar')));
                $satirlar[] = ['y' => $a['y'], 'metin' => $a['metin'], 'miktar' => _fpSayi($j('miktar')), 'birim' => trim($birimT),
                    'fiyat' => _fpSayi($j('fiyat')), 'isk' => _fpSayi($j('isk_tutar')) ?? 0.0, 'isk_oran' => _fpSayi($j('isk_oran')),
                    'kdv' => _fpSayi($j('kdv_oran')), 'kdv_tutar' => _fpSayi($j('kdv_tutar')) ?? 0.0, 'tutar' => $tt];
            }
            // Bölünmüş ürün adı satırlarını en yakın kalem satırına bağla
            foreach ($serbest as $f) {
                $en = null; $fark = 14;
                foreach ($satirlar as $i => $r) if (abs($r['y'] - $f['y']) < $fark) { $fark = abs($r['y'] - $f['y']); $en = $i; }
                if ($en !== null) foreach ($f['metin'] as $it) $satirlar[$en]['metin'][] = [$it[0], $it[1], $f['y'], $it[3]];
            }
            foreach ($satirlar as $n => $r) {
                $m = $r['metin']; usort($m, fn ($a, $b) => $a[2] <=> $b[2] ?: $a[1] <=> $b[1]);
                $kod = null; $sira = null; $adP = [];
                $kodSinir = isset($kol['kod']) ? (isset($kol['ad']) ? ($kol['kod'] + $kol['ad']) / 2 : $metinSinir) : null;
                foreach ($m as $it) {
                    if (isset($kol['sira']) && $sira === null && preg_match('/^\d{1,4}$/', $it[0]) && (!isset($kol['kod']) || $it[1] < $kol['kod'] - 2)) { $sira = (int) $it[0]; continue; }
                    // stok kodu: kod ve açıklama başlıklarının ortasından solda, boşluksuz; alt satıra taşan parçası ("003-0201-" + "MNS01") birleşir
                    // kod ile ad tek parça gelmiş ("8683989462915 SUGAR HOUSE ..."): ilk sözcük kod
                    if ($kodSinir !== null && $kod === null && $it[1] < $kodSinir && preg_match('/^([A-Z0-9][A-Z0-9\-\/.]{3,})\s+(.+)$/u', $it[0], $mm) && preg_match('/\d/', $mm[1])) { $kod = $mm[1]; $adP[] = $mm[2]; continue; }
                    if ($kodSinir !== null && $it[1] < $kodSinir && !preg_match('/\s/u', $it[0])) { $kod = $kod === null ? $it[0] : $kod . (preg_match('/[-\/.]$/', $kod) ? '' : ' ') . $it[0]; continue; }
                    $adP[] = $it[0];
                }
                $ad = trim(preg_replace('/\s+/u', ' ', implode(' ', $adP)));
                if ($ad === '' && $kod) { $ad = $kod; }
                $kalemler[] = ['sira' => $sira ?: $n + 1, 'stok_kodu' => $kod, 'ad' => $ad ?: 'Kalem ' . ($n + 1), 'miktar' => $r['miktar'], 'birim' => $r['birim'],
                    'fiyat' => $r['fiyat'], 'isk' => $r['isk'], 'isk_oran' => $r['isk_oran'], 'kdv' => $r['kdv'], 'kdv_tutar' => $r['kdv_tutar'], 'tutar' => $r['tutar'], '_y' => $r['y']];
            }
        }
        return ['ust' => array_filter($u, fn ($v) => $v !== null && $v !== ''), 'top' => $top, 'kalemler' => $kalemler, 'kol' => $kol, 'baslik' => (bool) $b, 'sablon' => $sablonla, 'sat' => $sat];
    }
}

// Tüm belge: sayfaları faturalara böl (Fatura No/ETTN = yeni fatura; olmayan sayfa = önceki faturanın devamı)
if (!function_exists('_fpBelge')) {
    function _fpBelge(array $sayfalar)
    {
        _fpEnsure();
        $sablon = [];
        try { foreach (DB::table('fatura_pdf_sablon')->get() as $s) { $k = json_decode($s->kolonlar, true); if ($k) $sablon[$s->satici_vkn] = $k; } } catch (\Throwable $e) {}
        $faturalar = []; $cozulemeyen = []; $cur = null;
        foreach ($sayfalar as $sy) {
            $p = _fpSayfa($sy, $sablon); $no = (int) ($sy['no'] ?? 0);
            if ($cur && !empty($cur['kol']) && !$p['baslik'] && empty($p['ust']['fatura_no']) && empty($p['ust']['ettn'])) $p = _fpSayfa($sy, $sablon, $cur['kol']);
            $yeni = !empty($p['ust']['fatura_no']) || !empty($p['ust']['ettn']);
            if ($yeni && $cur && (($p['ust']['ettn'] ?? null) === ($cur['ust']['ettn'] ?? '#') || (($p['ust']['fatura_no'] ?? null) === ($cur['ust']['fatura_no'] ?? '#') && !isset($p['ust']['ettn'])))) $yeni = false; // başlık her sayfada tekrarlanıyor
            if ($yeni) {
                if ($cur) $faturalar[] = $cur;
                $cur = ['ust' => $p['ust'], 'top' => $p['top'], 'kalemler' => $p['kalemler'], 'sayfalar' => [$no], 'kol' => $p['kol'], 'sablon' => $p['sablon']];
            } elseif ($cur && ($p['kalemler'] || $p['top']['kdv_var'] || isset($p['top']['ara']))) {
                foreach ($p['kalemler'] as $k) $cur['kalemler'][] = $k;
                foreach ($p['top'] as $k => $v) if ($k === 'kdv') $cur['top']['kdv'] += $v; elseif ($k === 'kdv_var') $cur['top']['kdv_var'] = $cur['top']['kdv_var'] || $v; else $cur['top'][$k] ??= $v;
                $cur['sayfalar'][] = $no;
            } elseif (!$cur && $p['kalemler']) {
                $cur = ['ust' => $p['ust'], 'top' => $p['top'], 'kalemler' => $p['kalemler'], 'sayfalar' => [$no], 'kol' => $p['kol'], 'sablon' => $p['sablon']];
            } elseif (preg_match_all('/\d[.,]\d{2}\b/u', implode(' ', array_column($p['sat'], 't'))) >= 3) $cozulemeyen[] = $no;
            // tutar içermeyen sayfa (yalnız banka/IBAN, "Yalnız ... TL" devamı) okunacak bir şey taşımaz → atlanır
        }
        if ($cur) $faturalar[] = $cur;
        // Kalemsiz faturaların sayfaları "okunamadı" listesine
        $tamam = [];
        foreach ($faturalar as $f) { if ($f['kalemler']) $tamam[] = $f; else foreach ($f['sayfalar'] as $n) $cozulemeyen[] = $n; }
        return ['faturalar' => $tamam, 'cozulemeyen' => array_values(array_unique($cozulemeyen))];
    }
}

// Okunan faturayı _efgKaydetVeri biçimine çevir + satır/toplam kontrolleri
if (!function_exists('_fpUbl')) {
    function _fpUbl(array $f, $kaynak)
    {
        $u = $f['ust']; $t = $f['top']; $notlar = []; $elle = $kaynak === 'ocr';
        $kalemler = []; $net = 0.0; $kdvHes = 0.0; $hataliSatir = 0;
        foreach ($f['kalemler'] as $i => $k) {
            $mk = (float) $k['miktar']; $tt = (float) $k['tutar']; $fy = $k['fiyat'] !== null ? (float) $k['fiyat'] : ($mk > 0 ? $tt / $mk : 0);
            $isk = (float) ($k['isk'] ?? 0);
            if ($isk == 0 && ($k['isk_oran'] ?? 0) > 0 && $fy > 0) $isk = round($mk * $fy * $k['isk_oran'] / 100, 2);
            if (abs($mk * $fy - $isk - $tt) > max(0.05, abs($tt) * 0.002)) $hataliSatir++;
            $kdvY = (float) ($k['kdv'] ?? 0);
            $net += $tt; $kdvHes += $kdvY > 0 ? $tt * $kdvY / 100 : (float) ($k['kdv_tutar'] ?? 0);
            $bk = _fpBirimKod($k['birim'] ?? '') ?: 'C62';
            $kalemler[] = ['sira' => (int) ($k['sira'] ?? $i + 1), 'stok_kodu' => $k['stok_kodu'] ? mb_substr($k['stok_kodu'], 0, 60) : null, 'ad' => mb_substr($k['ad'], 0, 255),
                'miktar' => round($mk, 3), 'birim_kodu' => $bk, 'birim_fiyat' => $mk > 0 ? round($tt / $mk, 4) : round($fy, 4), 'liste_fiyat' => round($fy, 4),
                'iskonto' => round($isk, 2), 'kdv_yuzde' => $kdvY, 'kdv_tutar' => round((float) ($k['kdv_tutar'] ?: $tt * $kdvY / 100), 2), 'satir_tutar' => round($tt, 2)];
        }
        $pay = max(0.05, 0.01 * count($kalemler));
        $ara = $t['ara'] ?? null; $isk = (float) ($t['isk'] ?? 0); $kdv = $t['kdv_var'] ? (float) $t['kdv'] : null;
        $vergili = $t['vergili'] ?? null; $oden = $t['odenecek'] ?? $vergili;
        $netTutar = ($ara !== null && (abs($net - $ara) <= $pay || abs($net - ($ara - $isk)) <= $pay))
            || ($vergili !== null && $kdv !== null && abs($net + $kdv - $vergili) <= $pay);
        if ($ara === null && $vergili === null) { $netTutar = false; $notlar[] = 'Fatura toplamı okunamadı'; }
        elseif (!$netTutar) $notlar[] = 'Kalem toplamı (' . number_format($net, 2, ',', '.') . ') fatura toplamını tutmuyor';
        if ($hataliSatir) { $notlar[] = $hataliSatir . ' satırda miktar × fiyat tutarı tutmuyor'; $elle = true; }
        if ($kdv !== null && abs($kdvHes - $kdv) > max(0.1, $pay * 2)) { $notlar[] = 'KDV toplamı satırlarla tutmuyor'; $elle = true; }
        if (empty($u['satici_vkn'])) $notlar[] = 'Satıcı VKN okunamadı';
        $kaynakAd = ['pdf' => 'PDF', 'ocr' => 'Fotoğraf/tarama (OCR)', 'ai' => 'Yapay zekâ'][$kaynak] ?? $kaynak;
        $notlar[] = $kaynakAd . ' ile okundu' . ($elle ? ' — kontrol edin' : '');
        $ettn = $u['ettn'] ?? ('pdf-' . substr(sha1(($u['satici_vkn'] ?? '') . '|' . ($u['fatura_no'] ?? '') . '|' . ($u['tarih'] ?? '') . '|' . round($oden ?? $net, 2)), 0, 32));
        return [
            'ettn' => $ettn, 'fatura_no' => $u['fatura_no'] ?? null, 'profil' => $u['profil'] ?? null, 'tip' => strtoupper($u['tip'] ?? 'SATIS'),
            'tarih' => $u['tarih'] ?? null, 'vade_tarihi' => $u['vade_tarihi'] ?? null, 'satici_vkn' => $u['satici_vkn'] ?? null, 'satici_ad' => $u['satici_ad'] ?? null,
            'alici_vkn' => $u['alici_vkn'] ?? null, 'para_birimi' => 'TRY', 'kur' => 1.0,
            'ara_toplam' => round($net, 2), 'iskonto' => 0.0, 'kdv_toplam' => round($kdv ?? $kdvHes, 2), 'genel_toplam' => round($oden ?? ($net + ($kdv ?? $kdvHes)), 2),
            'tutarli' => $netTutar ? 1 : 0, 'kalemler' => $kalemler, 'notlar' => $notlar, 'elle_kontrol' => $elle ? 1 : 0,
        ];
    }
}

// Başarılı okumadan tedarikçi şablonu öğren (başlığı tanınan düzenin sütunları; AI'dan gelen konumlar)
if (!function_exists('_fpSablonYaz')) {
    function _fpSablonYaz($vkn, array $kol, $kaynak)
    {
        if (!$vkn || !isset($kol['miktar'], $kol['tutar'])) return;
        try {
            $var = DB::table('fatura_pdf_sablon')->where('satici_vkn', $vkn)->first();
            if ($var) { if ($var->kaynak === 'ai' && $kaynak === 'kural') return; DB::table('fatura_pdf_sablon')->where('id', $var->id)->update(['kolonlar' => json_encode($kol), 'kaynak' => $kaynak, 'kullanim' => $var->kullanim + 1, 'updated_at' => now()]); }
            else DB::table('fatura_pdf_sablon')->insert(['satici_vkn' => $vkn, 'kolonlar' => json_encode($kol), 'kaynak' => $kaynak, 'kullanim' => 1, 'created_at' => now(), 'updated_at' => now()]);
        } catch (\Throwable $e) {}
    }
}

// ------------------------------------------------------------------ Yapay zekâ (kotalı) ----
if (!function_exists('_fpAiKota')) {
    // [kalan sayfa, günlük sınır] — env FATURA_AI_GUNLUK (varsayılan 20 sayfa/şube/gün), FATURA_AI_KAPALI=1 tamamen kapatır
    function _fpAiKota($sid)
    {
        if (env('FATURA_AI_KAPALI')) return [0, 0];
        $sinir = (int) env('FATURA_AI_GUNLUK', 20);
        return [max(0, $sinir - (int) cache()->get('fp_ai_' . $sid . '_' . date('Ymd'), 0)), $sinir];
    }
}
if (!function_exists('_fpAiOku')) {
    // Okunamayan sayfaların konumlu metnini modele verir; dönen faturayı _fpBelge çıktısı biçiminde döndürür
    function _fpAiOku($sid, array $sayfalar)
    {
        [$kalan] = _fpAiKota($sid);
        if ($kalan < count($sayfalar)) return ['hata' => 'Günlük yapay zekâ kotası doldu (kalan ' . $kalan . ' sayfa). Yarın tekrar deneyin ya da faturanın XML\'ini yükleyin.'];
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['hata' => 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        $metin = '';
        foreach ($sayfalar as $sy) {
            $metin .= "=== SAYFA " . (int) ($sy['no'] ?? 0) . " ===\n";
            foreach (_fpSatirlar($sy['o'] ?? []) as $s) $metin .= implode('  ', array_map(fn ($i) => '[' . (int) $i[1] . ']' . $i[0], $s['o'])) . "\n";
        }
        $kalemSema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['ad', 'miktar', 'birim', 'birim_fiyat', 'iskonto_tutar', 'kdv_yuzde', 'satir_tutar', 'stok_kodu'],
            'properties' => ['stok_kodu' => ['type' => ['string', 'null']], 'ad' => ['type' => 'string'], 'miktar' => ['type' => 'number'], 'birim' => ['type' => 'string'],
                'birim_fiyat' => ['type' => 'number'], 'iskonto_tutar' => ['type' => 'number'], 'kdv_yuzde' => ['type' => 'number'], 'satir_tutar' => ['type' => 'number']]];
        $faturaSema = ['type' => 'object', 'additionalProperties' => false,
            'required' => ['fatura_no', 'ettn', 'tarih', 'satici_ad', 'satici_vkn', 'alici_vkn', 'tip', 'ara_toplam', 'iskonto_toplam', 'kdv_toplam', 'odenecek', 'kalemler'],
            'properties' => ['fatura_no' => ['type' => ['string', 'null']], 'ettn' => ['type' => ['string', 'null']], 'tarih' => ['type' => ['string', 'null']],
                'satici_ad' => ['type' => ['string', 'null']], 'satici_vkn' => ['type' => ['string', 'null']], 'alici_vkn' => ['type' => ['string', 'null']], 'tip' => ['type' => 'string'],
                'ara_toplam' => ['type' => ['number', 'null']], 'iskonto_toplam' => ['type' => ['number', 'null']], 'kdv_toplam' => ['type' => ['number', 'null']], 'odenecek' => ['type' => ['number', 'null']],
                'kalemler' => ['type' => 'array', 'items' => $kalemSema]]];
        $sema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['faturalar'], 'properties' => ['faturalar' => ['type' => 'array', 'items' => $faturaSema]]];
        $sys = 'Türk alış faturalarının metnini yapılandırılmış veriye çeviriyorsun. Metindeki köşeli parantezli sayılar öğenin yatay konumudur. '
            . 'Sayıları Türkçe biçimden çevir (1.450,00 → 1450.00). Yalnız metinde gerçekten yazan değerleri kullan; hesap yapma, tahmin etme. '
            . 'satir_tutar = satırın KDV hariç, iskonto sonrası tutarı. tarih YYYY-MM-DD. tip: SATIS ya da IADE. Bir sayfa önceki faturanın devamıysa aynı faturaya ekle.';
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
                ->timeout(110)->post('https://api.anthropic.com/v1/messages', [
                    'model' => env('FATURA_AI_MODEL', 'claude-haiku-5-5'), 'max_tokens' => 16000, 'system' => $sys,
                    'output_config' => ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $sema]],
                    'messages' => [['role' => 'user', 'content' => $metin]],
                ]);
        } catch (\Throwable $e) { return ['hata' => 'Yapay zekâ servisine bağlanılamadı.']; }
        if (!$resp->successful()) return ['hata' => 'Yapay zekâ servisi cevap vermedi (' . $resp->status() . ').'];
        cache()->put('fp_ai_' . $sid . '_' . date('Ymd'), (int) cache()->get('fp_ai_' . $sid . '_' . date('Ymd'), 0) + count($sayfalar), now()->addDays(2));
        if ($resp->json('stop_reason') === 'max_tokens') return ['hata' => 'Sayfa çok uzun; daha az sayfayla tekrar deneyin.'];
        $txt = ''; foreach ((array) $resp->json('content') as $bl) if (($bl['type'] ?? '') === 'text') $txt .= $bl['text'];
        $j = json_decode($txt, true);
        if (!is_array($j) || !isset($j['faturalar'])) return ['hata' => 'Yapay zekâ cevabı çözümlenemedi.'];
        $faturalar = [];
        foreach ($j['faturalar'] as $f) {
            if (empty($f['kalemler'])) continue;
            $tarih = (isset($f['tarih']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $f['tarih'])) ? $f['tarih'] : null;
            $ettn = (isset($f['ettn']) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $f['ettn'])) ? strtolower($f['ettn']) : null;
            $vkn = fn ($v) => preg_match('/^\d{10,11}$/', (string) $v) ? (string) $v : null;
            $faturalar[] = [
                'ust' => array_filter(['fatura_no' => $f['fatura_no'] ?? null, 'ettn' => $ettn, 'tarih' => $tarih, 'satici_ad' => $f['satici_ad'] ?? null,
                    'satici_vkn' => $vkn($f['satici_vkn'] ?? null), 'alici_vkn' => $vkn($f['alici_vkn'] ?? null), 'tip' => $f['tip'] ?? 'SATIS']),
                'top' => array_filter(['ara' => $f['ara_toplam'], 'isk' => $f['iskonto_toplam'], 'odenecek' => $f['odenecek']], fn ($v) => $v !== null)
                    + ['kdv' => (float) ($f['kdv_toplam'] ?? 0), 'kdv_var' => $f['kdv_toplam'] !== null],
                'kalemler' => array_map(fn ($k) => ['sira' => null, 'stok_kodu' => $k['stok_kodu'], 'ad' => $k['ad'], 'miktar' => $k['miktar'], 'birim' => $k['birim'],
                    'fiyat' => $k['birim_fiyat'], 'isk' => $k['iskonto_tutar'], 'isk_oran' => null, 'kdv' => $k['kdv_yuzde'], 'kdv_tutar' => 0, 'tutar' => $k['satir_tutar']], $f['kalemler']),
            ];
        }
        // Şablon öğren: AI'nın verdiği miktar/fiyat/tutar değerlerinin sayfadaki x konumları → bu tedarikçi bir daha AI'ya gitmez
        foreach ($faturalar as $f) {
            if (empty($f['ust']['satici_vkn'])) continue;
            $xler = [];
            foreach ($sayfalar as $sy) foreach (_fpSatirlar($sy['o'] ?? []) as $s) foreach ($f['kalemler'] as $k) {
                $sayilar = []; foreach ($s['o'] as $it) { $n = _fpSayi($it[0]); if ($n !== null && preg_match('/\d/', $it[0])) $sayilar[] = [$n, $it[1]]; }
                $bul = function ($deg) use ($sayilar) { foreach ($sayilar as [$n, $x]) if ($deg !== null && abs($n - $deg) < 0.005) return $x; return null; };
                $xm = $bul((float) $k['miktar']); $xt = null; foreach (array_reverse($sayilar) as [$n, $x]) if (abs($n - (float) $k['tutar']) < 0.005) { $xt = $x; break; }
                if ($xm === null || $xt === null || $xt <= $xm) continue;
                $xler['miktar'][] = $xm; $xler['tutar'][] = $xt;
                foreach (['fiyat' => $k['fiyat'], 'kdv_oran' => $k['kdv'] ?: null, 'isk_tutar' => $k['isk'] ?: null] as $ad => $deg) { $x = $bul($deg); if ($x !== null && $x > $xm && $x < $xt) $xler[$ad][] = $x; }
            }
            if (!empty($xler['miktar']) && !empty($xler['tutar'])) _fpSablonYaz($f['ust']['satici_vkn'], array_map(fn ($l) => min($l) - 2, $xler), 'ai');
        }
        return ['faturalar' => $faturalar];
    }
}

// Okunan faturaları kaydet → özet
if (!function_exists('_fpKaydet')) {
    function _fpKaydet($sid, array $faturalar, $kaynak)
    {
        $s = ['yeni' => 0, 'var' => 0, 'aktarilan' => 0, 'kontrol' => 0, 'hatalar' => []]; $yeniler = [];
        foreach ($faturalar as $f) {
            $u = _fpUbl($f, $kaynak);
            if ($kaynak === 'pdf' && !empty($f['kol']) && empty($f['sablon']) && $u['tutarli'] && !$u['elle_kontrol']) _fpSablonYaz($u['satici_vkn'], $f['kol'], 'kural');
            $r = _efgKaydetVeri($sid, $u, $kaynak);
            if ($r['durum'] === 'yeni') { $s['yeni']++; $yeniler[] = $r['id']; if (!empty($r['aktarildi'])) $s['aktarilan']++; if (!$u['tutarli'] || $u['elle_kontrol']) $s['kontrol']++; }
            elseif ($r['durum'] === 'var') $s['var']++;
            else $s['hatalar'][] = ($u['fatura_no'] ?: 'Fatura') . ': ' . ($r['mesaj'] ?? 'kaydedilemedi');
        }
        if (function_exists('_fdTopluDenetle')) _fdTopluDenetle($yeniler);
        return $s;
    }
}

// Sayfa verisini doğrula: [{no, o:[[metin,x,y,w],...]}] — en çok 400 sayfa, sayfa başına 4000 öğe
if (!function_exists('_fpIstekSayfalar')) {
    function _fpIstekSayfalar(Request $r)
    {
        $sy = $r->input('sayfalar');
        if (!is_array($sy)) return [];
        $out = [];
        foreach (array_slice($sy, 0, 400) as $p) {
            if (!is_array($p) || !is_array($p['o'] ?? null)) continue;
            $o = [];
            foreach (array_slice($p['o'], 0, 4000) as $i) if (is_array($i) && count($i) >= 4) $o[] = [mb_substr((string) $i[0], 0, 300), (float) $i[1], (float) $i[2], (float) $i[3]];
            $out[] = ['no' => (int) ($p['no'] ?? count($out) + 1), 'o' => $o];
        }
        return $out;
    }
}

Route::post('/efatura-gelen/pdf-oku', function (Request $r) {
    if (!_efgErisim()) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
    _efgEnsure(); _fpEnsure();
    @set_time_limit(170);
    $sid = _webSubeId(); $sayfalar = _fpIstekSayfalar($r);
    if (!$sayfalar) return response()->json(['ok' => 0, 'hata' => 'Sayfa verisi yok.']);
    $kaynak = $r->input('kaynak') === 'ocr' ? 'ocr' : 'pdf';
    $b = _fpBelge($sayfalar);
    $s = _fpKaydet($sid, $b['faturalar'], $kaynak);
    [$kalan, $sinir] = _fpAiKota($sid);
    return response()->json(['ok' => 1, 'fatura' => count($b['faturalar']), 'cozulemeyen' => $b['cozulemeyen'], 'ai_kalan' => $kalan, 'ai_sinir' => $sinir] + $s);
});

Route::post('/efatura-gelen/pdf-ai', function (Request $r) {
    if (!_efgErisim()) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
    _efgEnsure(); _fpEnsure();
    @set_time_limit(170);
    $sid = _webSubeId(); $sayfalar = array_slice(_fpIstekSayfalar($r), 0, 6);
    if (!$sayfalar) return response()->json(['ok' => 0, 'hata' => 'Sayfa verisi yok.']);
    $a = _fpAiOku($sid, $sayfalar);
    if (!empty($a['hata'])) return response()->json(['ok' => 0, 'hata' => $a['hata']]);
    $s = _fpKaydet($sid, $a['faturalar'], 'ai');
    [$kalan] = _fpAiKota($sid);
    return response()->json(['ok' => 1, 'fatura' => count($a['faturalar']), 'ai_kalan' => $kalan] + $s);
});

<?php
// =====================================================================
// STOK / MALZEME ÇIKTISI — /stok/malzeme/indir?format=xlsx|pdf
// Ekrandaki (stok/index.blade.php) hesapla birebir: stok = şubenin stok hareketleri toplamı,
// değer = max(0, stok) × güncel maliyet, durum: negatif → Veri Hatası, ≤ kritik → Kritik, ≤ kritik×1,5 → Az, diğer → Yeterli.
// Excel/PDF motoru ortak: _fr2Xlsx + fiyat_radari_rapor (A4 yatay). Yetki: '/stok' öneki → gor_stok.
// =====================================================================
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

if (!function_exists('_stokCiktiSatirlar')) {
    function _stokCiktiSatirlar($subeId)
    {
        $birim = DB::table('birimler')->pluck('kisaltma', 'id');
        $kat = DB::table('malzeme_kategorileri')->pluck('ad', 'id');
        $m = _katalogFiltre(DB::table('malzemeler'), 'malzeme', $subeId, 'malzemeler.id')
            ->select('malzemeler.*',
                DB::raw('(SELECT COALESCE(SUM(sh.miktar),0) FROM stok_hareketleri sh WHERE sh.malzeme_id = malzemeler.id AND sh.sube_id = ' . (int) $subeId . ') as stok'),
                DB::raw('(SELECT MAX(sh.created_at) FROM stok_hareketleri sh WHERE sh.malzeme_id = malzemeler.id AND sh.sube_id = ' . (int) $subeId . ') as son_hareket'))
            ->orderBy('malzemeler.ad')->get();
        return $m->map(function ($x) use ($birim, $kat) {
            $stok = (float) ($x->stok ?? 0); $esik = (float) $x->kritik_stok;
            $durum = $stok < 0 ? 'VERİ HATASI' : ($esik > 0 && $stok <= $esik ? 'KRİTİK' : ($esik > 0 && $stok <= $esik * 1.5 ? 'AZ' : 'YETERLİ'));
            $gun = null; $tarih = null;
            if (!empty($x->son_hareket)) { try { $c = \Carbon\Carbon::parse($x->son_hareket); $gun = (int) floor($c->diffInDays(now())); $tarih = $c->format('d.m.Y'); } catch (\Throwable $e) {} }
            return [
                'ad' => $x->ad, 'kategori' => $kat[$x->kategori_id] ?? 'Kategorisiz', 'birim' => $birim[$x->temel_birim_id] ?? '',
                'stok' => round($stok, 3), 'kritik' => $esik, 'maliyet' => (float) $x->guncel_maliyet,
                'deger' => round(max(0, $stok) * (float) $x->guncel_maliyet, 2), 'durum' => $durum,
                'takip' => (int) ($x->stok_takipli ?? 1) ? 'Evet' : 'Hayır',
                'son' => $gun === null ? 'Hareket yok' : (($gun === 0 ? 'Bugün' : ($gun === 1 ? 'Dün' : $gun . ' gün önce')) . ' · ' . $tarih),
                'son_gun' => $gun,
            ];
        })->all();
    }
}

Route::get('/stok/malzeme/indir', function (Request $r) {
    $sid = _aktifSubeId();
    $fmt = $r->query('format') === 'pdf' ? 'pdf' : 'xlsx';
    $s = _stokCiktiSatirlar($sid);
    $say = array_count_values(array_column($s, 'durum'));
    $deger = array_sum(array_column($s, 'deger'));
    $kpi = fn ($et, $d, $f, $renk) => [$et, $d, $f, $renk];
    $blok = fn ($bas, $g, $renk, $kol, $sat) => ['baslik' => $bas, 'grafik' => $g, 'renk' => $renk, 'kol' => $kol, 'sat' => array_values($sat)];

    // kategori ve durum toplamları
    $katDeg = []; foreach ($s as $x) $katDeg[$x['kategori']] = ($katDeg[$x['kategori']] ?? 0) + $x['deger']; arsort($katDeg);
    $enDeg = $s; usort($enDeg, fn ($a, $b) => $b['deger'] <=> $a['deger']);
    $kritik = array_values(array_filter($s, fn ($x) => in_array($x['durum'], ['KRİTİK', 'VERİ HATASI', 'AZ'], true)));
    usort($kritik, fn ($a, $b) => [['VERİ HATASI' => 0, 'KRİTİK' => 1, 'AZ' => 2][$a['durum']], $a['ad']] <=> [['VERİ HATASI' => 0, 'KRİTİK' => 1, 'AZ' => 2][$b['durum']], $b['ad']]);

    $sayfalar = [[
        'ad' => 'Pano', 'tip' => 'pano', 'bos' => $s ? null : 'Bu şubede tanımlı malzeme yok. Malzemeler Stok > Stok / Malzeme ekranından eklenir.',
        'kpi' => [
            $kpi('Toplam stok değeri', $deger, 'tl', '7C3AED'), $kpi('Malzeme', count($s), 'sayi', '4F46E5'),
            $kpi('Kritik stok', $say['KRİTİK'] ?? 0, 'sayi', 'DC2626'), $kpi('Az stok', $say['AZ'] ?? 0, 'sayi', 'EA580C'),
            $kpi('Yeterli', $say['YETERLİ'] ?? 0, 'sayi', '16A34A'), $kpi('Veri hatası (negatif)', $say['VERİ HATASI'] ?? 0, 'sayi', '991B1B'),
            $kpi('Kategori', count($katDeg), 'sayi', '0E7490'), $kpi('Rapor tarihi', now()->format('d.m.Y H:i'), 'metin', '1E293B'),
        ],
        'bloklar' => array_values(array_filter([
            $blok('Kategoriye göre stok değeri', 'pie', '7C3AED', [['Kategori', 'm'], ['Değer', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($katDeg), $katDeg)),
            $blok('Stok durumu (malzeme sayısı)', 'pie', '16A34A', [['Durum', 'm'], ['Malzeme', 's'], ['', 'm']], array_map(fn ($k) => [['KRİTİK' => 'Kritik', 'AZ' => 'Az', 'YETERLİ' => 'Yeterli', 'VERİ HATASI' => 'Veri hatası'][$k], $say[$k]], array_values(array_filter(['KRİTİK', 'AZ', 'YETERLİ', 'VERİ HATASI'], fn ($k) => !empty($say[$k]))))),
            $blok('En değerli 10 malzeme', 'bar', '4F46E5', [['Malzeme', 'm'], ['Stok değeri', 'tl'], ['Mevcut', 'm']], array_map(fn ($x) => [$x['ad'], $x['deger'], rtrim(rtrim(number_format($x['stok'], 3, ',', '.'), '0'), ',') . ' ' . $x['birim']], array_slice($enDeg, 0, 10))),
        ], fn ($b) => count($b['sat']) > 0)),
    ]];
    $mik = fn ($v) => (float) $v;
    $sayfalar[] = ['ad' => 'Alınması Gerekenler', 'not' => 'Kritik, az ve hatalı (eksi) stoktaki malzemeler. Eksik = kritik seviye − mevcut. Veri hatası: stok eksiye düşmüş (sayım ya da hareket kaydı kontrol edilmeli).',
        'kol' => [['Malzeme', 'm', 24], ['Kategori', 'm', 15], ['Mevcut', 's', 10], ['Birim', 'm', 6], ['Kritik Seviye', 's', 10], ['Eksik', 's', 10, 'bar'], ['Stok Değeri', 'tl', 11], ['Durum', 'm', 10, 'onc'], ['Son Hareket', 'm', 15]],
        'sat' => array_map(fn ($x) => [$x['ad'], $x['kategori'], $mik($x['stok']), $x['birim'], $mik($x['kritik']), max(0, round($x['kritik'] - $x['stok'], 3)), $x['deger'], $x['durum'], $x['son']], $kritik),
        'bos' => 'Kritik ya da az stokta malzeme yok; tüm malzemeler kritik seviyenin üstünde.', 'grafiksiz' => true];
    // tüm liste: kategori kategori, her kategorinin sonunda ara toplam
    $tum = []; $gr = []; foreach ($s as $x) $gr[$x['kategori']][] = $x; ksort($gr);
    foreach ($gr as $k => $l) {
        $tum[] = ['_baslik' => $k . ' · ' . count($l) . ' malzeme'];
        foreach ($l as $x) $tum[] = [$x['ad'], $mik($x['stok']), $x['birim'], $mik($x['kritik']), $x['durum'], $x['maliyet'], $x['deger'], $x['takip'], $x['son']];
        $tum[] = ['_ara' => $k . ' toplamı'];
    }
    $sayfalar[] = ['ad' => 'Tüm Malzemeler', 'not' => 'Stok = şubenin stok hareketleri toplamı (temel birim). Stok değeri = mevcut × güncel maliyet; eksi stok değere katılmaz.',
        'kol' => [['Malzeme', 'm', 24], ['Mevcut', 's', 11], ['Birim', 'm', 6], ['Kritik Seviye', 's', 10], ['Durum', 'm', 10, 'onc'], ['Birim Maliyet', 'tl', 11], ['Stok Değeri', 'tl', 12, 'top bar'], ['Takip', 'm', 6], ['Son Hareket', 'm', 16]],
        'sat' => $tum, 'bos' => 'Bu şubede tanımlı malzeme yok.', 'grafiksiz' => true];

    $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
    $baslik = 'Stok / Malzeme Listesi'; $alt = $subeAd . ' · ' . count($s) . ' malzeme · Oluşturma: ' . now()->format('d.m.Y H:i');
    $dosya = 'stok-malzeme-listesi-' . now()->format('Y-m-d');
    if ($fmt === 'pdf') {
        return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => [], 'ai' => null, 'rapor' => 'stok', 'dosya' => $dosya,
            'tema' => '7C3AED', 'ikon' => 'inventory_2', 'aciklama' => 'Şubedeki tüm malzemelerin anlık stok seviyesi, kritik durumu ve stok değeri.', 'marka' => 'Stok Raporu', 'onizle' => $r->boolean('onizle'), 'aiEtiket' => false, 'gorsel' => '']);
    }
    // Excel ilk sekmeyi açar: ürün listesi önde, özet (pano) en sonda
    $xs = [$sayfalar[2], $sayfalar[1], array_merge($sayfalar[0], ['ad' => 'Özet'])];
    $x = _fr2Xlsx($xs, $baslik, $alt, '7C3AED');
    if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
    return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
});

// ---------------------------------------------------------------------
// Tek malzemenin stok hareketleri — /stok/malzeme/hareket-indir?id=&gun=90&format=xlsx|pdf
// Bakiye: güncel stoktan geriye doğru (her hareketten SONRA kalan miktar). gun=0 → tüm geçmiş.
// ---------------------------------------------------------------------
Route::get('/stok/malzeme/hareket-indir', function (Request $r) {
    $sid = _aktifSubeId(); $id = (int) $r->query('id'); $gun = max(0, (int) $r->query('gun', 90));
    $fmt = $r->query('format') === 'pdf' ? 'pdf' : 'xlsx';
    $m = _katalogFiltre(DB::table('malzemeler'), 'malzeme', $sid, 'malzemeler.id')->where('malzemeler.id', $id)->select('malzemeler.*')->first();
    if (!$m) return response('Malzeme bulunamadı.', 404);
    $birim = DB::table('birimler')->where('id', $m->temel_birim_id)->value('kisaltma') ?: '';
    $kat = DB::table('malzeme_kategorileri')->where('id', $m->kategori_id)->value('ad') ?: 'Kategorisiz';
    $mevcut = (float) DB::table('stok_hareketleri')->where('sube_id', $sid)->where('malzeme_id', $id)->sum('miktar');
    $bas = $gun > 0 ? now()->subDays($gun)->startOfDay() : null;
    $q = DB::table('stok_hareketleri')->where('sube_id', $sid)->where('malzeme_id', $id);
    if ($bas) $q->where('created_at', '>=', $bas);
    $h = $q->orderByDesc('created_at')->orderByDesc('id')->get(['tip', 'miktar', 'birim_maliyet', 'aciklama', 'created_at']);
    $tipAd = ['alis' => 'Alış', 'tuketim' => 'Satış tüketimi', 'fire' => 'Fire', 'sayim' => 'Sayım', 'iade' => 'Giriş', 'transfer' => 'Transfer', 'transfer_giris' => 'Şubeden transfer', 'transfer_cikis' => 'Şubeye transfer', 'giris' => 'Giriş', 'duzeltme' => 'Düzeltme'];
    $bakiye = $mevcut; $sat = []; $giris = 0.0; $cikis = 0.0; $tipT = []; $ayT = [];
    foreach ($h as $x) {
        $mk = (float) $x->miktar; $ad = $tipAd[$x->tip] ?? ucfirst((string) $x->tip); $bm = (float) $x->birim_maliyet;
        $sat[] = [date('d.m.Y H:i', strtotime((string) $x->created_at)), $ad, trim((string) $x->aciklama), $mk >= 0 ? $mk : null, $mk < 0 ? -$mk : null, round($bakiye, 3), $bm ?: null, $bm ? round(abs($mk) * $bm, 2) : null];
        $bakiye -= $mk; // bu hareketten önceki stok = bir önceki satırın bakiyesi
        if ($mk >= 0) $giris += $mk; else $cikis += -$mk;
        $tipT[$ad] = ($tipT[$ad] ?? 0) + abs($mk);
        $ay = substr((string) $x->created_at, 0, 7); $ayT[$ay] = ($ayT[$ay] ?? 0) + ($mk < 0 ? -$mk : 0);
    }
    ksort($ayT); arsort($tipT); $ayAd = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
    $donem = $gun > 0 ? 'Son ' . $gun . ' gün (' . $bas->format('d.m.Y') . ' – ' . now()->format('d.m.Y') . ')' : 'Tüm geçmiş';
    $mikY = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',') . ' ' . $birim;
    $sayfalar = [[
        'ad' => 'Pano', 'tip' => 'pano', 'bos' => $sat ? null : 'Seçili dönemde bu malzemede stok hareketi yok. Daha uzun bir dönem seçin.',
        'kpi' => [
            ['Mevcut stok', $mikY($mevcut), 'metin', '7C3AED'], ['Stok değeri', max(0, $mevcut) * (float) $m->guncel_maliyet, 'tl', '4F46E5'],
            ['Dönem girişi', $mikY($giris), 'metin', '16A34A'], ['Dönem çıkışı', $mikY($cikis), 'metin', 'DC2626'],
            ['Dönem başı stok', $mikY($bakiye), 'metin', '0E7490'], ['Hareket sayısı', count($sat), 'sayi', '334155'],
            ['Kritik seviye', $mikY($m->kritik_stok), 'metin', 'EA580C'], ['Dönem', $donem, 'metin', '1E293B'],
        ],
        'bloklar' => array_values(array_filter([
            ['baslik' => 'Hareket türüne göre miktar', 'grafik' => 'pie', 'renk' => '7C3AED', 'kol' => [['Tür', 'm'], ['Miktar (' . $birim . ')', 's'], ['', 'm']], 'sat' => array_map(fn ($k, $v) => [$k, round($v, 3), ''], array_keys($tipT), $tipT)],
            ['baslik' => 'Aylık çıkış (tüketim, fire, transfer)', 'grafik' => 'col', 'renk' => 'DC2626', 'kol' => [['Ay', 'm'], ['Çıkış (' . $birim . ')', 's'], ['', 'm']], 'sat' => array_map(fn ($k, $v) => [$ayAd[(int) substr($k, 5, 2)] . ' ' . substr($k, 2, 2), round($v, 3), ''], array_keys($ayT), $ayT)],
        ], fn ($b) => count($b['sat']) > 0)),
    ], [
        'ad' => 'Hareketler', 'grafiksiz' => true,
        'not' => 'En yeni hareket en üstte. Bakiye = o hareketten sonra kalan stok (güncel stoktan geriye hesaplanır). Miktarlar temel birimdedir (' . $birim . ').',
        'kol' => [['Tarih', 'm', 13], ['Hareket', 'm', 13], ['Açıklama', 'm', 26], ['Giriş', 's', 9, 'top'], ['Çıkış', 's', 9, 'top'], ['Bakiye', 's', 10], ['Birim Maliyet', 'tl', 10], ['Tutar', 'tl', 10, 'top']],
        'sat' => $sat, 'bos' => 'Seçili dönemde bu malzemede stok hareketi yok.',
    ]];
    $baslik = $m->ad . ' · Stok Hareketleri'; $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
    $alt = $subeAd . ' · ' . $kat . ' · ' . $donem . ' · Oluşturma: ' . now()->format('d.m.Y H:i');
    $dosya = \Illuminate\Support\Str::slug($m->ad) . '-stok-hareketleri-' . now()->format('Y-m-d');
    if ($fmt === 'pdf') {
        return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => [], 'ai' => null, 'rapor' => 'stok-hareket', 'dosya' => $dosya,
            'tema' => '7C3AED', 'ikon' => 'swap_vert', 'aciklama' => 'Malzemenin seçili dönemdeki tüm stok giriş ve çıkışları, her hareketten sonra kalan stokla.', 'marka' => 'Stok Raporu', 'onizle' => $r->boolean('onizle'), 'aiEtiket' => false, 'gorsel' => '']);
    }
    $x = _fr2Xlsx([$sayfalar[1], array_merge($sayfalar[0], ['ad' => 'Özet'])], $baslik, $alt, '7C3AED');
    if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
    return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
});

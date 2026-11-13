<?php
// ===== FİYAT RADARI v2 — satın alma rapor merkezi =====
// Kim'den, ne zaman, kaça aldın + neye göre kıyaslandı; Excel (.xlsx) / PDF (yazdır) rapor; AI analiz (geçmiş hata + gelecek önerisi).
// web.php sonunda require edilir. Flutter /api/patron/fiyat-radari ayrı (web.php) — dokunulmadı.

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_fr2Filtre')) {
    // İstekten dönem/tedarikçi/eşik filtresi (tüm sayfa + rapor + AI aynı filtreyi kullanır)
    function _fr2Filtre(Request $r)
    {
        $gun = (int) $r->input('gun', 120);
        if (!in_array($gun, [30, 60, 90, 120, 180, 365], true)) $gun = 120;
        $esik = (float) $r->input('esik', 8);
        if ($esik < 1 || $esik > 100) $esik = 8;
        return ['gun' => $gun, 'ted' => max(0, (int) $r->input('ted', 0)), 'esik' => $esik];
    }
}

if (!function_exists('_fr2Veri')) {
    function _fr2Veri($sid, array $f)
    {
        $v = ['filtre' => $f, 'kpi' => [], 'zamlar' => [], 'malzemeler' => [], 'karsilastirma' => [], 'katalog' => [], 'karne' => [],
            'hal' => ['satirlar' => [], 'bulten' => null, 'urun_say' => 0, 'yillik_fazla' => 0], 'aylik' => [], 'kategori' => [], 'dokum' => [], 'gecmis' => [], 'tedarikciler' => []];
        if (!Schema::hasTable('alis_fatura_kalemleri') || !Schema::hasTable('alis_faturalari')) return $v;
        if (function_exists('_restoTedarikEnsure')) { try { _restoTedarikEnsure(); } catch (\Throwable $e) {} }

        $malzeme = DB::table('malzemeler')->get(['id', 'ad', 'kategori_id'])->keyBy('id');
        $katAd = Schema::hasTable('malzeme_kategorileri') ? DB::table('malzeme_kategorileri')->pluck('ad', 'id') : collect();
        $birimKis = DB::table('birimler')->pluck('kisaltma', 'id');
        $tedAd = DB::table('tedarikciler')->pluck('ad', 'id');
        $gun = $f['gun']; $esik = $f['esik']; $carpan = 365 / $gun;

        $q = DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'k.fatura_id', '=', 'f.id')
            ->where('f.sube_id', $sid)->whereDate('f.tarih', '>=', today()->subDays($gun))->where('k.birim_fiyat', '>', 0);
        if ($f['ted']) $q->where('f.tedarikci_id', $f['ted']);
        $lines = $q->orderBy('f.tarih')->orderBy('k.id')
            ->get(['k.id', 'k.malzeme_id', 'k.alis_birim_id', 'k.miktar', 'k.birim_fiyat', 'k.satir_toplam', 'f.id as fatura_id', 'f.tarih', 'f.tedarikci_id', 'f.fatura_no']);
        $tedIsim = fn ($id) => $id ? ($tedAd[$id] ?? 'Silinmiş tedarikçi') : 'Tedarikçisiz';
        $tarihS = fn ($t) => substr((string) $t, 0, 10);

        // tedarikçi filtresi listesi (dönemde alım yapılanlar)
        $v['tedarikciler'] = DB::table('alis_faturalari')->where('sube_id', $sid)->whereDate('tarih', '>=', today()->subDays($gun))->whereNotNull('tedarikci_id')
            ->distinct()->pluck('tedarikci_id')->map(fn ($id) => ['id' => (int) $id, 'ad' => $tedIsim($id)])->sortBy('ad')->values()->all();

        // ---- Detaylı döküm + malzeme başına geçmiş (önceki alışa göre değişim) ----
        $oncekiFiyat = [];
        foreach ($lines as $l) {
            $tutar = (float) ($l->satir_toplam ?: $l->miktar * $l->birim_fiyat);
            $once = $oncekiFiyat[$l->malzeme_id] ?? null;
            $deg = $once ? round(((float) $l->birim_fiyat - $once) / $once * 100, 1) : null;
            $oncekiFiyat[$l->malzeme_id] = (float) $l->birim_fiyat;
            $m = $malzeme[$l->malzeme_id] ?? null;
            $row = ['tarih' => $tarihS($l->tarih), 'malzeme' => $m->ad ?? '—', 'kategori' => $m && $m->kategori_id ? ($katAd[$m->kategori_id] ?? '') : '',
                'tedarikci' => $tedIsim($l->tedarikci_id), 'fatura_no' => (string) ($l->fatura_no ?: '#' . $l->fatura_id), 'miktar' => (float) $l->miktar,
                'birim' => $birimKis[$l->alis_birim_id] ?? '', 'birim_fiyat' => (float) $l->birim_fiyat, 'tutar' => round($tutar, 2), 'degisim' => $deg];
            $v['dokum'][] = $row;
            $v['gecmis'][$l->malzeme_id][] = $row;
        }
        $v['dokum'] = array_reverse($v['dokum']);

        $toplam = 0; $fatSet = []; $tedSet = [];
        foreach ($lines as $l) { $toplam += (float) ($l->satir_toplam ?: $l->miktar * $l->birim_fiyat); $fatSet[$l->fatura_id] = 1; if ($l->tedarikci_id) $tedSet[$l->tedarikci_id] = 1; }

        // ---- Malzeme analizi + gizli zam + tedarikçi kıyası ----
        $zamlar = []; $malz = []; $kars = []; $tedZam = [];
        foreach ($lines->groupBy('malzeme_id') as $mid => $g) {
            $m = $malzeme[$mid] ?? null; $ad = $m->ad ?? '—';
            $birim = $birimKis[$g->last()->alis_birim_id] ?? '';
            $mik = (float) $g->sum('miktar');
            $harc = (float) $g->sum(fn ($x) => (float) ($x->satir_toplam ?: $x->miktar * $x->birim_fiyat));
            $ort = $mik > 0 ? $harc / $mik : (float) $g->avg('birim_fiyat');
            $minL = $g->sortBy('birim_fiyat')->first(); $maxL = $g->sortByDesc('birim_fiyat')->first();
            $ilk = $g->first(); $son = $g->last();
            $tedSay = $g->pluck('tedarikci_id')->unique()->count();
            $oyn = (float) $minL->birim_fiyat > 0 ? round(((float) $maxL->birim_fiyat - (float) $minL->birim_fiyat) / (float) $minL->birim_fiyat * 100, 1) : 0;
            $malz[] = ['id' => (int) $mid, 'ad' => $ad, 'kategori' => $m && $m->kategori_id ? ($katAd[$m->kategori_id] ?? '') : '', 'birim' => $birim,
                'alis_say' => $g->count(), 'miktar' => round($mik, 3), 'harcama' => round($harc, 2), 'ort' => round($ort, 4),
                'min' => (float) $minL->birim_fiyat, 'min_ted' => $tedIsim($minL->tedarikci_id), 'min_tarih' => $tarihS($minL->tarih),
                'max' => (float) $maxL->birim_fiyat, 'max_ted' => $tedIsim($maxL->tedarikci_id), 'max_tarih' => $tarihS($maxL->tarih),
                'son' => (float) $son->birim_fiyat, 'son_ted' => $tedIsim($son->tedarikci_id), 'son_tarih' => $tarihS($son->tarih),
                'ted_say' => $tedSay, 'oynaklik' => $oyn, 'tasarruf' => round(max(0, ($ort - (float) $minL->birim_fiyat) * $mik), 0)];

            // Gizli zam: dönemin ilk alışı → son alışı. Aynı tedarikçide mi zam, yoksa tedarikçi mi değişti — ikisi ayrı gösterilir.
            if ($g->count() >= 2 && (float) $ilk->birim_fiyat > 0) {
                $yuzde = round(((float) $son->birim_fiyat - (float) $ilk->birim_fiyat) / (float) $ilk->birim_fiyat * 100, 1);
                $ayniTed = $g->where('tedarikci_id', $son->tedarikci_id)->values();
                $ayniYuzde = null;
                if ($ayniTed->count() >= 2 && (float) $ayniTed->first()->birim_fiyat > 0)
                    $ayniYuzde = round(((float) $son->birim_fiyat - (float) $ayniTed->first()->birim_fiyat) / (float) $ayniTed->first()->birim_fiyat * 100, 1);
                if ($ayniYuzde !== null && $son->tedarikci_id) {
                    $tz = &$tedZam[$son->tedarikci_id]; $tz['kalem'] = ($tz['kalem'] ?? 0) + 1;
                    if ($ayniYuzde >= $esik) { $tz['zamli'] = ($tz['zamli'] ?? 0) + 1; $tz['toplam'] = ($tz['toplam'] ?? 0) + $ayniYuzde; }
                    unset($tz);
                }
                if ($yuzde >= $esik) {
                    $zamlar[] = ['id' => (int) $mid, 'ad' => $ad, 'birim' => $birim, 'ilk' => (float) $ilk->birim_fiyat, 'ilk_tarih' => $tarihS($ilk->tarih),
                        'ilk_ted' => $tedIsim($ilk->tedarikci_id), 'ilk_fatura' => (string) ($ilk->fatura_no ?: '#' . $ilk->fatura_id),
                        'son' => (float) $son->birim_fiyat, 'son_tarih' => $tarihS($son->tarih), 'son_ted' => $tedIsim($son->tedarikci_id),
                        'son_fatura' => (string) ($son->fatura_no ?: '#' . $son->fatura_id), 'yuzde' => $yuzde, 'adet' => $g->count(),
                        'ted_degisti' => $ilk->tedarikci_id != $son->tedarikci_id, 'ayni_ted_yuzde' => $ayniYuzde,
                        'en_dusuk' => (float) $minL->birim_fiyat, 'en_dusuk_ted' => $tedIsim($minL->tedarikci_id),
                        'yillik_etki' => round(((float) $son->birim_fiyat - (float) $ilk->birim_fiyat) * $mik * $carpan, 0)];
                }
            }

            // Tedarikçi karşılaştırma (gerçek faturalardan): aynı malzemeyi ≥2 tedarikçiden aldıysan
            if ($tedSay >= 2) {
                $tedler = [];
                foreach ($g->groupBy('tedarikci_id') as $tid => $tg) {
                    $tm = (float) $tg->sum('miktar'); $th = (float) $tg->sum(fn ($x) => (float) ($x->satir_toplam ?: $x->miktar * $x->birim_fiyat));
                    $tedler[] = ['ted' => $tedIsim($tid), 'ort' => round($tm > 0 ? $th / $tm : (float) $tg->avg('birim_fiyat'), 4), 'son' => (float) $tg->last()->birim_fiyat,
                        'son_tarih' => $tarihS($tg->last()->tarih), 'miktar' => round($tm, 3), 'harcama' => round($th, 2), 'alis_say' => $tg->count()];
                }
                usort($tedler, fn ($a, $b) => $a['ort'] <=> $b['ort']);
                $enUcuz = $tedler[0]; $enPahali = end($tedler);
                $kayip = 0; foreach ($tedler as $t) $kayip += max(0, ($t['ort'] - $enUcuz['ort']) * $t['miktar']);
                $kars[] = ['id' => (int) $mid, 'ad' => $ad, 'birim' => $birim, 'tedler' => $tedler, 'en_ucuz_ted' => $enUcuz['ted'], 'en_ucuz' => $enUcuz['ort'],
                    'en_pahali_ted' => $enPahali['ted'], 'en_pahali' => $enPahali['ort'], 'son_ted' => $tedIsim($son->tedarikci_id),
                    'fark' => $enUcuz['ort'] > 0 ? round(($enPahali['ort'] - $enUcuz['ort']) / $enUcuz['ort'] * 100, 1) : 0,
                    'kayip' => round($kayip, 0), 'yillik_kayip' => round($kayip * $carpan, 0)];
            }
        }
        usort($zamlar, fn ($a, $b) => $b['yillik_etki'] <=> $a['yillik_etki']);
        usort($malz, fn ($a, $b) => $b['harcama'] <=> $a['harcama']);
        usort($kars, fn ($a, $b) => $b['kayip'] <=> $a['kayip']);
        $v['zamlar'] = $zamlar; $v['malzemeler'] = $malz; $v['karsilastirma'] = $kars;

        // Katalog fiyatları (tedarikçi kartındaki güncel fiyat) — henüz alınmamış ucuz seçenekler
        if (Schema::hasTable('tedarikci_malzeme')) {
            try {
                $kat = DB::table('tedarikci_malzeme')->where('sube_id', $sid)->where('aktif', 1)->where('guncel_fiyat', '>', 0)->get();
                foreach ($kat->groupBy('malzeme_id') as $mid => $cs) {
                    if ($cs->count() < 2) continue;
                    $s = $cs->sortBy('guncel_fiyat')->values(); $uc = $s->first(); $pa = $s->last();
                    $tas = round(((float) $pa->guncel_fiyat - (float) $uc->guncel_fiyat) / (float) $pa->guncel_fiyat * 100, 1);
                    if ($tas < 5) continue;
                    $v['katalog'][] = ['ad' => $malzeme[$mid]->ad ?? '—', 'en_ucuz_ted' => $tedIsim($uc->tedarikci_id), 'en_ucuz' => (float) $uc->guncel_fiyat,
                        'en_pahali_ted' => $tedIsim($pa->tedarikci_id), 'en_pahali' => (float) $pa->guncel_fiyat, 'tasarruf' => $tas, 'ted_say' => $cs->count()];
                }
                usort($v['katalog'], fn ($a, $b) => $b['tasarruf'] <=> $a['tasarruf']);
            } catch (\Throwable $e) {}
        }

        // ---- Tedarikçi karnesi ----
        $pahaliSay = [];
        foreach ($kars as $k) foreach ($k['tedler'] as $t) if ($k['en_ucuz'] > 0 && $t['ort'] > $k['en_ucuz'] * 1.05) $pahaliSay[$t['ted']] = ($pahaliSay[$t['ted']] ?? 0) + 1;
        foreach ($lines->groupBy('tedarikci_id') as $tid => $tg) {
            $h = (float) $tg->sum(fn ($x) => (float) ($x->satir_toplam ?: $x->miktar * $x->birim_fiyat));
            $ad = $tedIsim($tid); $tz = $tedZam[$tid] ?? [];
            $v['karne'][] = ['ted' => $ad, 'harcama' => round($h, 2), 'pay' => $toplam > 0 ? round($h / $toplam * 100, 1) : 0,
                'fatura' => $tg->pluck('fatura_id')->unique()->count(), 'kalem' => $tg->count(), 'malzeme' => $tg->pluck('malzeme_id')->unique()->count(),
                'zamli' => (int) ($tz['zamli'] ?? 0), 'ort_zam' => !empty($tz['zamli']) ? round($tz['toplam'] / $tz['zamli'], 1) : 0,
                'pahali' => (int) ($pahaliSay[$ad] ?? 0), 'son_alis' => $tarihS($tg->max('tarih'))];
        }
        usort($v['karne'], fn ($a, $b) => $b['harcama'] <=> $a['harcama']);

        // ---- Hal kıyası (+ son alışın tedarikçisi) ----
        try {
            if (function_exists('_restoHalEnsure')) _restoHalEnsure();
            if (function_exists('_radarHalKiyas')) {
                $hal = _radarHalKiyas($sid);
                $sonTed = []; foreach ($lines as $l) $sonTed[$malzeme[$l->malzeme_id]->ad ?? ''] = $tedIsim($l->tedarikci_id);
                foreach ($hal['satirlar'] as &$h) $h['tedarikci'] = $sonTed[$h['ad']] ?? '—';
                unset($h);
                $v['hal'] = $hal;
            }
        } catch (\Throwable $e) {}

        // ---- Aylık harcama + kategori ----
        $ay = [];
        foreach ($lines as $l) { $k = substr((string) $l->tarih, 0, 7); $ay[$k] = ($ay[$k] ?? 0) + (float) ($l->satir_toplam ?: $l->miktar * $l->birim_fiyat); }
        ksort($ay);
        foreach ($ay as $k => $t) $v['aylik'][] = ['ay' => $k, 'tutar' => round($t, 2)];
        $kat = [];
        foreach ($malz as $m) { $k = $m['kategori'] ?: 'Kategorisiz'; $kat[$k] = ($kat[$k] ?? 0) + $m['harcama']; }
        arsort($kat);
        foreach ($kat as $k => $t) $v['kategori'][] = ['ad' => $k, 'tutar' => round($t, 2), 'pay' => $toplam > 0 ? round($t / $toplam * 100, 1) : 0];

        $v['kpi'] = ['toplam' => round($toplam, 2), 'fatura' => count($fatSet), 'tedarikci' => count($tedSet), 'malzeme' => count($malz), 'kalem' => $lines->count(),
            'zam_say' => count($zamlar), 'zam_etki' => array_sum(array_column($zamlar, 'yillik_etki')),
            'ted_kayip' => array_sum(array_column($kars, 'yillik_kayip')), 'hal_fazla' => (float) ($v['hal']['yillik_fazla'] ?? 0),
            'baslangic' => today()->subDays($gun)->toDateString(), 'bitis' => today()->toDateString()];
        return $v;
    }
}

if (!function_exists('_fr2Raporlar')) {
    // Rapor kataloğu: anahtar → [başlık, açıklama, ikon, renk]. 'ai' dışındaki raporlar YORUMSUZ (yalnız sistem verisi).
    function _fr2Raporlar()
    {
        return [
            'tam' => ['Tam Fiyat Radarı Raporu', 'Tüm veri bölümleri tek dosyada: genel pano, gizli zamlar, malzeme fiyatları (ABC), tedarikçi kıyası, karne, hal, aylık, döküm. Yorum içermez.', 'summarize', '#7C3AED'],
            'zam' => ['Gizli Zam Raporu', 'Eşiğin üstünde artan malzemeler: ilk/son alış, tedarikçi, fatura, zammın kaynağı, alış alış fiyat seyri, yıllık etki.', 'trending_up', '#F43F5E'],
            'malzeme' => ['Malzeme Fiyat Analizi', 'ABC analizi (harcamanın %80’ini yapan malzemeler), ortalama/en düşük/en yüksek/son fiyat ve kimden, oynaklık, tasarruf payı.', 'inventory_2', '#4F46E5'],
            'tedarikci' => ['Tedarikçi Karşılaştırma', 'Aynı malzemenin tedarikçi fiyatları yan yana, şu an en ucuzdan alınıyor mu, dönem ve yıllık kayıp, katalog fiyatları.', 'compare_arrows', '#0EA5E9'],
            'karne' => ['Tedarikçi Karnesi', 'Tedarikçi başına harcama, pay, zamlı kalem, pahalı kaldığı malzeme ve formülü açık karne puanı.', 'workspace_premium', '#F59E0B'],
            'hal' => ['Hal Kıyası Raporu', 'Son alış fiyatın hal bülteninin asgari–ortalama–azami şeridinde nerede; bant dağılımı, yıllık fazla.', 'storefront', '#10B981'],
            'dokum' => ['Detaylı Alış Dökümü', 'Fatura fatura gruplu döküm (ara toplamlarla) ve satır satır liste; haftalık alış.', 'receipt_long', '#64748B'],
            'aylik' => ['Aylık Harcama & Kategori', 'Ay ay alış ve değişim, kategori dağılımı, kategori × ay ve tedarikçi × ay ısı tabloları.', 'bar_chart', '#EC4899'],
            'ai' => ['AI Analiz Raporu', 'Yapay zekânın yorumu ve önerileri: puan, bulgular, hatalar, fırsatlar, pazarlık, aksiyon planı + dayandığı rakamlar.', 'auto_awesome', '#9333EA'],
        ];
    }
}

if (!function_exists('_fr2Pano')) {
    // Genel pano (Tam rapor): 8 özet kart + 6 grafik bloğu
    function _fr2Pano(array $v, $ai = null)
    {
        $k = $v['kpi'] ?? [];
        $kpi = [
            ['Toplam alış', (float) ($k['toplam'] ?? 0), 'tl', '4F46E5'],
            ['Gizli zam yıllık etkisi', (float) ($k['zam_etki'] ?? 0), 'tl', 'E11D48'],
            ['En ucuzdan alsaydın (yıllık)', (float) ($k['ted_kayip'] ?? 0), 'tl', '0284C7'],
            ['Hal üstü fazla ödeme (yıllık)', (float) ($k['hal_fazla'] ?? 0), 'tl', '059669'],
            ['Toplam tasarruf potansiyeli', (float) (($k['zam_etki'] ?? 0) + ($k['ted_kayip'] ?? 0) + ($k['hal_fazla'] ?? 0)), 'tl', 'D97706'],
            ['Fatura / alış satırı', ($k['fatura'] ?? 0) . ' / ' . ($k['kalem'] ?? 0), 'metin', '7C3AED'],
            ['Tedarikçi / malzeme', ($k['tedarikci'] ?? 0) . ' / ' . ($k['malzeme'] ?? 0), 'metin', '0F766E'],
            ['Gizli zamlı malzeme', (int) ($k['zam_say'] ?? 0), 'sayi', 'BE123C'],
        ];
        $bloklar = [
            _fr2BlokAylik($v),
            _fr2BlokTedPay($v),
            ['baslik' => 'En çok para giden 10 malzeme', 'grafik' => 'bar', 'renk' => '4F46E5', 'kol' => [['Malzeme', 'm'], ['Harcama', 'tl'], ['Son tedarikçi', 'm']],
                'sat' => array_map(fn ($m) => [$m['ad'], $m['harcama'], $m['son_ted']], array_slice($v['malzemeler'] ?? [], 0, 10))],
            ['baslik' => 'Gizli zamların yıllık etkisi (ilk 10)', 'grafik' => 'bar', 'renk' => 'E11D48', 'kol' => [['Malzeme', 'm'], ['Yıllık etki', 'tl'], ['Artış %', 'y']],
                'sat' => array_map(fn ($z) => [$z['ad'], $z['yillik_etki'], $z['yuzde']], array_slice($v['zamlar'] ?? [], 0, 10))],
            ['baslik' => 'En ucuz tedarikçiden alsaydın — yıllık kayıp (ilk 10)', 'grafik' => 'bar', 'renk' => '0284C7', 'kol' => [['Malzeme', 'm'], ['Yıllık kayıp', 'tl'], ['En ucuz', 'm']],
                'sat' => array_map(fn ($x) => [$x['ad'], $x['yillik_kayip'], $x['en_ucuz_ted']], array_slice(array_values(array_filter($v['karsilastirma'] ?? [], fn ($x) => $x['yillik_kayip'] > 0)), 0, 10))],
            _fr2BlokKategori($v),
        ];
        return ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0))];
    }
}
if (!function_exists('_fr2AyAd')) {
    function _fr2AyAd($ym)
    {
        $ad = ['01' => 'Oca', '02' => 'Şub', '03' => 'Mar', '04' => 'Nis', '05' => 'May', '06' => 'Haz', '07' => 'Tem', '08' => 'Ağu', '09' => 'Eyl', '10' => 'Eki', '11' => 'Kas', '12' => 'Ara'];
        return ($ad[substr($ym, 5, 2)] ?? '') . ' ' . substr($ym, 0, 4);
    }
    function _fr2BlokAylik(array $v)
    {
        $say = []; foreach ($v['dokum'] ?? [] as $d) { $a = substr($d['tarih'], 0, 7); $say[$a] = ($say[$a] ?? 0) + 1; }
        return ['baslik' => 'Aylık alış', 'grafik' => 'col', 'renk' => '8B5CF6', 'kol' => [['Ay', 'm'], ['Toplam alış', 'tl'], ['Alış satırı', 's']],
            'sat' => array_map(fn ($a) => [_fr2AyAd($a['ay']), $a['tutar'], $say[$a['ay']] ?? 0], $v['aylik'] ?? [])];
    }
    function _fr2BlokTedPay(array $v)
    {
        $toplam = max(0.01, (float) ($v['kpi']['toplam'] ?? 0)); $sat = []; $diger = 0;
        foreach ($v['karne'] ?? [] as $i => $x) { if ($i < 6) $sat[] = [$x['ted'], $x['harcama'], round($x['harcama'] / $toplam * 100, 1)]; else $diger += $x['harcama']; }
        if ($diger > 0) $sat[] = ['Diğer', round($diger, 2), round($diger / $toplam * 100, 1)];
        return ['baslik' => 'Tedarikçi payı', 'grafik' => 'pie', 'renk' => 'F59E0B', 'kol' => [['Tedarikçi', 'm'], ['Harcama', 'tl'], ['Pay %', 'y']], 'sat' => $sat];
    }
    function _fr2BlokKategori(array $v)
    {
        return ['baslik' => 'Kategori dağılımı', 'grafik' => 'pie', 'renk' => '0EA5E9', 'kol' => [['Kategori', 'm'], ['Harcama', 'tl'], ['Pay %', 'y']],
            'sat' => array_map(fn ($x) => [$x['ad'], $x['tutar'], $x['pay']], array_slice($v['kategori'] ?? [], 0, 8))];
    }
}

if (!function_exists('_fr2Ek')) {
    // Raporlara özel türetilmiş veriler (yorum değil, hesap): ABC, karne puanı, fatura grupları, matrisler, fiyat seyri
    function _fr2Ek(array $v)
    {
        $e = [];
        // ABC: harcamaya göre sıralı kümülatif pay. A ≤ %80, B ≤ %95, C kalan
        $top = max(0.01, array_sum(array_column($v['malzemeler'], 'harcama'))); $kum = 0; $e['abc'] = [];
        foreach ($v['malzemeler'] as $m) { $kum += $m['harcama']; $p = $kum / $top * 100; $e['abc'][$m['id']] = ['sinif' => $p <= 80.0001 ? 'A' : ($p <= 95.0001 ? 'B' : 'C'), 'kum' => round($p, 1), 'pay' => round($m['harcama'] / $top * 100, 1)]; }
        // ilk malzeme tek başına %80'i geçerse yine A say
        if ($v['malzemeler'] && !in_array('A', array_column($e['abc'], 'sinif'), true)) $e['abc'][$v['malzemeler'][0]['id']]['sinif'] = 'A';
        // Karne puanı (formül açık): 100 − 50 × (pahalı kaldığı / malzeme) − 50 × (zamlı kalem / malzeme)
        $e['puan'] = [];
        foreach ($v['karne'] as $k) { $mz = max(1, $k['malzeme']); $p = (int) round(max(0, min(100, 100 - 50 * $k['pahali'] / $mz - 50 * $k['zamli'] / $mz))); $e['puan'][$k['ted']] = ['puan' => $p, 'harf' => $p >= 85 ? 'A' : ($p >= 70 ? 'B' : ($p >= 50 ? 'C' : 'D'))]; }
        // Fatura grupları (döküm)
        $e['fatura'] = [];
        foreach ($v['dokum'] as $d) { $k = $d['tarih'] . '|' . $d['tedarikci'] . '|' . $d['fatura_no']; $e['fatura'][$k]['tarih'] = $d['tarih']; $e['fatura'][$k]['ted'] = $d['tedarikci']; $e['fatura'][$k]['no'] = $d['fatura_no']; $e['fatura'][$k]['sat'][] = $d; $e['fatura'][$k]['top'] = ($e['fatura'][$k]['top'] ?? 0) + $d['tutar']; }
        // Kategori × ay, tedarikçi × ay matrisleri
        $aylar = array_column($v['aylik'], 'ay'); $e['aylar'] = $aylar; $e['katAy'] = []; $e['tedAy'] = [];
        foreach ($v['dokum'] as $d) { $a = substr($d['tarih'], 0, 7); $kk = $d['kategori'] ?: 'Kategorisiz'; $e['katAy'][$kk][$a] = ($e['katAy'][$kk][$a] ?? 0) + $d['tutar']; $e['tedAy'][$d['tedarikci']][$a] = ($e['tedAy'][$d['tedarikci']][$a] ?? 0) + $d['tutar']; }
        foreach (['katAy', 'tedAy'] as $mk) uasort($e[$mk], fn ($a, $b) => array_sum($b) <=> array_sum($a));
        // Haftalık alış (hafta başı Pazartesi)
        $e['hafta'] = [];
        foreach ($v['dokum'] as $d) { $h = \Carbon\Carbon::parse($d['tarih'])->startOfWeek()->toDateString(); $e['hafta'][$h] = ($e['hafta'][$h] ?? 0) + $d['tutar']; }
        ksort($e['hafta']);
        return $e;
    }
}

if (!function_exists('_fr2RaporPano')) {
    // Her rapora ÖZEL pano: kendi kartları + kendi grafikleri (yorum yok)
    function _fr2RaporPano($rapor, array $v, array $e, $ai = null)
    {
        $tl0 = fn ($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
        $yz = fn ($x) => '%' . number_format((float) $x, 1, ',', '.');
        $f = $v['filtre']; $k = $v['kpi'];
        $p = ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => [], 'bloklar' => []];
        switch ($rapor) {
            case 'zam':
                $z = $v['zamlar']; $n = count($z);
                $ayni = array_filter($z, fn ($x) => !$x['ted_degisti']); $deg = array_filter($z, fn ($x) => $x['ted_degisti']);
                $sert = $z ? array_reduce($z, fn ($c, $x) => !$c || $x['yuzde'] > $c['yuzde'] ? $x : $c) : null;
                $p['kpi'] = [
                    ['Zamlı malzeme', $n, 'sayi', 'E11D48'], ['Ortalama artış', $n ? array_sum(array_column($z, 'yuzde')) / $n : 0, 'y', 'BE123C'],
                    ['En sert zam', $sert ? $sert['ad'] . ' ' . $yz($sert['yuzde']) : '—', 'metin', '9F1239'], ['Toplam yıllık etki', array_sum(array_column($z, 'yillik_etki')), 'tl', 'E11D48'],
                    ['Aynı tedarikçinin zammı', array_sum(array_column($ayni, 'yillik_etki')), 'tl', 'F43F5E'], ['Tedarikçi değişimiyle gelen', array_sum(array_column($deg, 'yillik_etki')), 'tl', 'FB7185'],
                    ['Zam eşiği', '%' . $f['esik'] . ' ve üstü', 'metin', '881337'], ['Dönem', 'Son ' . $f['gun'] . ' gün', 'metin', '881337'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'Yıllık etki (ilk 10)', 'grafik' => 'bar', 'renk' => 'E11D48', 'kol' => [['Malzeme', 'm'], ['Yıllık etki', 'tl'], ['Son tedarikçi', 'm']], 'sat' => array_map(fn ($x) => [$x['ad'], $x['yillik_etki'], $x['son_ted']], array_slice($z, 0, 10))],
                    ['baslik' => 'Artış oranı (ilk 10)', 'grafik' => 'bar', 'renk' => 'FB7185', 'kol' => [['Malzeme', 'm'], ['Artış %', 'y'], ['İlk → son', 'm']],
                        'sat' => array_map(fn ($x) => [$x['ad'], $x['yuzde'], number_format($x['ilk'], 2, ',', '.') . ' → ' . number_format($x['son'], 2, ',', '.') . ' ₺'], array_slice(collect($z)->sortByDesc('yuzde')->values()->all(), 0, 10))],
                    ['baslik' => 'Zammın kaynağı (yıllık etki)', 'grafik' => 'pie', 'renk' => 'F43F5E', 'kol' => [['Kaynak', 'm'], ['Yıllık etki', 'tl'], ['Malzeme', 's']],
                        'sat' => array_values(array_filter([['Aynı tedarikçinin zammı', array_sum(array_column($ayni, 'yillik_etki')), count($ayni)], ['Tedarikçi değişimi', array_sum(array_column($deg, 'yillik_etki')), count($deg)]], fn ($r) => $r[1] > 0))],
                    ['baslik' => 'Alış alış fiyat seyri', 'grafik' => 'tablo', 'pdf' => 'seyir', 'renk' => 'E11D48', 'kol' => [['Malzeme', 'm'], ['Artış %', 'y'], ['Alış', 's']],
                        'sat' => array_map(fn ($x) => [$x['ad'], $x['yuzde'], $x['adet']], array_slice($z, 0, 12)),
                        'ek' => array_map(fn ($x) => array_map(fn ($g) => (float) $g['birim_fiyat'], $v['gecmis'][$x['id']] ?? []), array_slice($z, 0, 12))],
                ];
                break;
            case 'malzeme':
                $abc = ['A' => [0, 0], 'B' => [0, 0], 'C' => [0, 0]];
                foreach ($v['malzemeler'] as $m) { $s = $e['abc'][$m['id']]['sinif'] ?? 'C'; $abc[$s][0] += $m['harcama']; $abc[$s][1]++; }
                $oyn = collect($v['malzemeler'])->sortByDesc('oynaklik')->values()->all(); $n = max(1, count($v['malzemeler']));
                $p['kpi'] = [
                    ['Malzeme sayısı', count($v['malzemeler']), 'sayi', '4F46E5'], ['Toplam harcama', $k['toplam'], 'tl', '4338CA'],
                    ['A sınıfı (harcamanın ~%80’i)', $abc['A'][1] . ' malzeme', 'metin', '312E81'], ['B / C sınıfı', $abc['B'][1] . ' / ' . $abc['C'][1] . ' malzeme', 'metin', '6366F1'],
                    ['En oynak malzeme', $oyn ? $oyn[0]['ad'] . ' ' . $yz($oyn[0]['oynaklik']) : '—', 'metin', '7C3AED'], ['Ortalama oynaklık', array_sum(array_column($v['malzemeler'], 'oynaklik')) / $n, 'y', '6D28D9'],
                    ['Toplam tasarruf payı', array_sum(array_column($v['malzemeler'], 'tasarruf')), 'tl', 'D97706'], ['Birden çok tedarikçili', count(array_filter($v['malzemeler'], fn ($m) => $m['ted_say'] > 1)) . ' malzeme', 'metin', '0F766E'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'ABC analizi (harcama)', 'grafik' => 'pie', 'renk' => '4F46E5', 'kol' => [['Sınıf', 'm'], ['Harcama', 'tl'], ['Malzeme', 's']],
                        'sat' => array_values(array_filter([['A sınıfı', $abc['A'][0], $abc['A'][1]], ['B sınıfı', $abc['B'][0], $abc['B'][1]], ['C sınıfı', $abc['C'][0], $abc['C'][1]]], fn ($r) => $r[2] > 0))],
                    ['baslik' => 'Harcama (ilk 10)', 'grafik' => 'bar', 'renk' => '4F46E5', 'kol' => [['Malzeme', 'm'], ['Harcama', 'tl'], ['ABC', 'm']], 'sat' => array_map(fn ($m) => [$m['ad'], $m['harcama'], $e['abc'][$m['id']]['sinif'] ?? ''], array_slice($v['malzemeler'], 0, 10))],
                    ['baslik' => 'Oynaklık (ilk 10)', 'grafik' => 'bar', 'renk' => '7C3AED', 'kol' => [['Malzeme', 'm'], ['Oynaklık %', 'y'], ['En düşük → en yüksek', 'm']],
                        'sat' => array_map(fn ($m) => [$m['ad'], $m['oynaklik'], number_format($m['min'], 2, ',', '.') . ' → ' . number_format($m['max'], 2, ',', '.') . ' ₺'], array_slice($oyn, 0, 10))],
                    ['baslik' => 'Tasarruf payı (ilk 10)', 'grafik' => 'bar', 'renk' => 'D97706', 'kol' => [['Malzeme', 'm'], ['Tasarruf payı', 'tl'], ['En düşük kimde', 'm']],
                        'sat' => array_map(fn ($m) => [$m['ad'], $m['tasarruf'], $m['min_ted']], array_slice(array_values(array_filter(collect($v['malzemeler'])->sortByDesc('tasarruf')->values()->all(), fn ($m) => $m['tasarruf'] > 0)), 0, 10))],
                ];
                break;
            case 'tedarikci':
                $kr = $v['karsilastirma']; $n = count($kr); $ucuz = count(array_filter($kr, fn ($x) => $x['son_ted'] === $x['en_ucuz_ted']));
                $byk = $kr ? collect($kr)->sortByDesc('fark')->first() : null;
                $p['kpi'] = [
                    ['Kıyaslanabilir malzeme', $n, 'sayi', '0284C7'], ['Şu an en ucuzdan alınan', $ucuz . ' / ' . $n, 'metin', '0369A1'],
                    ['Dönem kaybı', array_sum(array_column($kr, 'kayip')), 'tl', '0EA5E9'], ['Yıllık kayıp', array_sum(array_column($kr, 'yillik_kayip')), 'tl', '0284C7'],
                    ['En büyük fiyat farkı', $byk ? $byk['ad'] . ' ' . $yz($byk['fark']) : '—', 'metin', '075985'], ['Katalog fırsatı', count($v['katalog']), 'sayi', '0E7490'],
                    ['Tedarikçi sayısı', $k['tedarikci'], 'sayi', '155E75'], ['Dönem', 'Son ' . $f['gun'] . ' gün', 'metin', '164E63'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'Yıllık kayıp (ilk 10)', 'grafik' => 'bar', 'renk' => '0284C7', 'kol' => [['Malzeme', 'm'], ['Yıllık kayıp', 'tl'], ['En ucuz', 'm']], 'sat' => array_map(fn ($x) => [$x['ad'], $x['yillik_kayip'], $x['en_ucuz_ted']], array_slice($kr, 0, 10))],
                    ['baslik' => 'Şu an en ucuzdan mı alınıyor?', 'grafik' => 'pie', 'renk' => '0EA5E9', 'kol' => [['Durum', 'm'], ['Malzeme', 's'], ['Yıllık kayıp', 'tl']],
                        'sat' => array_values(array_filter([['Evet, en ucuzdan', $ucuz, 0], ['Hayır, başka tedarikçiden', $n - $ucuz, array_sum(array_map(fn ($x) => $x['son_ted'] !== $x['en_ucuz_ted'] ? $x['yillik_kayip'] : 0, $kr))]], fn ($r) => $r[1] > 0))],
                    ['baslik' => 'Tedarikçi fiyatları yan yana (ilk 8)', 'grafik' => 'tablo', 'pdf' => 'yanyana', 'renk' => '0EA5E9', 'kol' => [['Malzeme', 'm'], ['Fark %', 'y'], ['Tedarikçi', 's']],
                        'sat' => array_map(fn ($x) => [$x['ad'], $x['fark'], count($x['tedler'])], array_slice($kr, 0, 8)), 'ek' => array_map(fn ($x) => array_map(fn ($t) => [$t['ted'], $t['ort']], $x['tedler']), array_slice($kr, 0, 8))],
                ];
                break;
            case 'karne':
                $kn = $v['karne']; $n = max(1, count($kn));
                $bp = $kn ? $kn[0] : null; $cz = $kn ? collect($kn)->sortByDesc('zamli')->first() : null; $cp = $kn ? collect($kn)->sortByDesc('pahali')->first() : null;
                $p['kpi'] = [
                    ['Tedarikçi sayısı', count($kn), 'sayi', 'D97706'], ['Toplam harcama', $k['toplam'], 'tl', 'B45309'],
                    ['En büyük pay', $bp ? $bp['ted'] . ' ' . $yz($bp['pay']) : '—', 'metin', 'F59E0B'], ['Ortalama karne puanı', round(array_sum(array_column($e['puan'], 'puan')) / $n) . ' / 100', 'metin', '92400E'],
                    ['En çok zamlı kalem', $cz && $cz['zamli'] ? $cz['ted'] . ' (' . $cz['zamli'] . ')' : '—', 'metin', 'C2410C'], ['En çok pahalı kaldığı', $cp && $cp['pahali'] ? $cp['ted'] . ' (' . $cp['pahali'] . ')' : '—', 'metin', 'EA580C'],
                    ['Fatura sayısı', $k['fatura'], 'sayi', '78350F'], ['Dönem', 'Son ' . $f['gun'] . ' gün', 'metin', '78350F'],
                ];
                $p['bloklar'] = [
                    _fr2BlokTedPay($v),
                    ['baslik' => 'Karne puanı', 'grafik' => 'bar', 'renk' => 'F59E0B', 'kol' => [['Tedarikçi', 'm'], ['Puan', 's'], ['Not', 'm']], 'sat' => array_map(fn ($x) => [$x['ted'], $e['puan'][$x['ted']]['puan'] ?? 0, $e['puan'][$x['ted']]['harf'] ?? ''], $kn)],
                    ['baslik' => 'Tedarikçi kartları', 'grafik' => 'tablo', 'pdf' => 'karne', 'renk' => 'F59E0B', 'kol' => [['Tedarikçi', 'm'], ['Harcama', 'tl'], ['Puan', 's']],
                        'sat' => array_map(fn ($x) => [$x['ted'], $x['harcama'], $e['puan'][$x['ted']]['puan'] ?? 0], $kn), 'ek' => array_map(fn ($x) => $x + ($e['puan'][$x['ted']] ?? []), $kn)],
                ];
                break;
            case 'hal':
                $hs = $v['hal']['satirlar'] ?? []; $uy = array_values(array_filter($hs, fn ($h) => $h['fark'] !== null));
                $bant = ['Halin altında' => 0, 'Normal bant (%0–20)' => 0, 'Bant üstü (>%20)' => 0, 'Birim farklı (kıyas yok)' => count($hs) - count($uy)];
                foreach ($uy as $h) $bant[$h['fark'] < 0 ? 'Halin altında' : ($h['fark'] <= 20 ? 'Normal bant (%0–20)' : 'Bant üstü (>%20)')]++;
                $p['kpi'] = [
                    ['Halde eşleşen malzeme', count($hs), 'sayi', '059669'], ['Birimi uyumlu', count($uy), 'sayi', '047857'],
                    ['Ortalama hal üstü', $uy ? array_sum(array_column($uy, 'fark')) / count($uy) : 0, 'y', '10B981'], ['Yıllık fazla ödeme', (float) ($v['hal']['yillik_fazla'] ?? 0), 'tl', '065F46'],
                    ['Normal bantta', $bant['Normal bant (%0–20)'], 'sayi', '15803D'], ['Bant üstünde (>%20)', $bant['Bant üstü (>%20)'], 'sayi', 'BE123C'],
                    ['Hal bülteni', !empty($v['hal']['bulten']) ? \Carbon\Carbon::parse($v['hal']['bulten'])->format('d.m.Y') . ' · İzmir' : '—', 'metin', '064E3B'], ['Bültendeki ürün', (int) ($v['hal']['urun_say'] ?? 0), 'sayi', '064E3B'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'Hal ortalamasına göre fark', 'grafik' => 'bar', 'renk' => '10B981', 'kol' => [['Malzeme', 'm'], ['Fark %', 'y'], ['Tedarikçi', 'm']], 'sat' => array_map(fn ($h) => [$h['ad'], $h['fark'], $h['tedarikci'] ?? ''], collect($uy)->sortByDesc('fark')->values()->all())],
                    ['baslik' => 'Bant dağılımı', 'grafik' => 'pie', 'renk' => '10B981', 'kol' => [['Bant', 'm'], ['Malzeme', 's'], ['', 'm']], 'sat' => array_values(array_filter(array_map(fn ($a, $c) => [$a, $c, ''], array_keys($bant), $bant), fn ($r) => $r[1] > 0))],
                    ['baslik' => 'Fiyatın hal şeridinde nerede', 'grafik' => 'tablo', 'pdf' => 'aralik', 'renk' => '10B981', 'kol' => [['Malzeme', 'm'], ['Senin son alış', 'tl'], ['Fark %', 'y']],
                        'sat' => array_map(fn ($h) => [$h['ad'], $h['fiyat_hal_birim'], $h['fark']], $uy), 'ek' => array_map(fn ($h) => ['min' => $h['hal_min'], 'ort' => $h['hal_ort'], 'max' => $h['hal_max'], 'sen' => $h['fiyat_hal_birim'], 'birim' => $h['hal_birim']], $uy)],
                ];
                break;
            case 'dokum':
                $fat = $e['fatura']; $nf = max(1, count($fat)); $byk = $fat ? collect($fat)->sortByDesc('top')->first() : null;
                $p['kpi'] = [
                    ['Fatura', count($fat), 'sayi', '475569'], ['Alış satırı', count($v['dokum']), 'sayi', '334155'], ['Toplam alış', $k['toplam'], 'tl', '1E293B'], ['Ortalama fatura', $k['toplam'] / $nf, 'tl', '64748B'],
                    ['En büyük fatura', $byk ? $byk['no'] . ' · ' . $tl0($byk['top']) : '—', 'metin', '475569'], ['Tedarikçi', $k['tedarikci'], 'sayi', '334155'],
                    ['İlk alış', $v['dokum'] ? \Carbon\Carbon::parse(end($v['dokum'])['tarih'])->format('d.m.Y') : '—', 'metin', '1E293B'], ['Son alış', $v['dokum'] ? \Carbon\Carbon::parse($v['dokum'][0]['tarih'])->format('d.m.Y') : '—', 'metin', '64748B'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'Haftalık alış', 'grafik' => 'col', 'renk' => '64748B', 'kol' => [['Hafta başı', 'm'], ['Alış', 'tl'], ['', 'm']], 'sat' => array_map(fn ($h, $t) => [\Carbon\Carbon::parse($h)->format('d.m'), $t, ''], array_keys($e['hafta']), $e['hafta'])],
                    ['baslik' => 'Tedarikçi bazında fatura tutarı', 'grafik' => 'bar', 'renk' => '475569', 'kol' => [['Tedarikçi', 'm'], ['Harcama', 'tl'], ['Fatura', 's']], 'sat' => array_map(fn ($x) => [$x['ted'], $x['harcama'], $x['fatura']], $v['karne'])],
                ];
                break;
            case 'aylik':
                $ay = $v['aylik']; $n = max(1, count($ay)); $enY = $ay ? collect($ay)->sortByDesc('tutar')->first() : null;
                $sonD = count($ay) >= 2 && $ay[count($ay) - 2]['tutar'] > 0 ? ($ay[count($ay) - 1]['tutar'] - $ay[count($ay) - 2]['tutar']) / $ay[count($ay) - 2]['tutar'] * 100 : 0;
                $p['kpi'] = [
                    ['Toplam alış', $k['toplam'], 'tl', 'DB2777'], ['Aylık ortalama', $k['toplam'] / $n, 'tl', 'BE185D'],
                    ['En yüksek ay', $enY ? _fr2AyAd($enY['ay']) . ' · ' . $tl0($enY['tutar']) : '—', 'metin', 'EC4899'], ['Son ay değişimi', $sonD, 'y', '9D174D'],
                    ['Kategori sayısı', count($v['kategori']), 'sayi', 'A21CAF'], ['En büyük kategori', $v['kategori'] ? $v['kategori'][0]['ad'] . ' ' . $yz($v['kategori'][0]['pay']) : '—', 'metin', '86198F'],
                    ['Ay sayısı', count($ay), 'sayi', '831843'], ['Dönem', 'Son ' . $f['gun'] . ' gün', 'metin', '831843'],
                ];
                $p['bloklar'] = [_fr2BlokAylik($v), _fr2BlokKategori($v),
                    ['baslik' => 'Kategori harcaması', 'grafik' => 'bar', 'renk' => 'EC4899', 'kol' => [['Kategori', 'm'], ['Harcama', 'tl'], ['Pay %', 'y']], 'sat' => array_map(fn ($x) => [$x['ad'], $x['tutar'], $x['pay']], $v['kategori'])]];
                break;
            case 'ai':
                $fr = array_values(array_filter((array) ($ai['firsatlar'] ?? []), fn ($x) => is_numeric($x['tutar'] ?? null) && $x['tutar'] > 0));
                $p['kpi'] = [
                    ['AI satın alma puanı', (int) ($ai['skor'] ?? 0) . ' / 100', 'metin', '9333EA'], ['Kritik bulgu', count((array) ($ai['kritik'] ?? [])), 'sayi', '7E22CE'],
                    ['AI’ın gördüğü tasarruf (yıllık)', array_sum(array_column($fr, 'tutar')), 'tl', 'A21CAF'], ['Yüksek öncelikli aksiyon', count(array_filter((array) ($ai['aksiyonlar'] ?? []), fn ($x) => ($x['oncelik'] ?? '') === 'yuksek')), 'sayi', '6B21A8'],
                    ['Toplam alış', $k['toplam'], 'tl', '4F46E5'], ['Gizli zam yıllık etkisi', $k['zam_etki'], 'tl', 'E11D48'], ['En ucuzdan alsaydın (yıllık)', $k['ted_kayip'], 'tl', '0284C7'],
                    ['Analiz', ($ai['_zaman'] ?? '') . ' · ' . ($ai['_donem'] ?? $f['gun']) . ' gün', 'metin', '581C87'],
                ];
                $p['bloklar'] = [
                    ['baslik' => 'AI’ın önerdiği tasarruflar', 'grafik' => 'bar', 'renk' => '9333EA', 'kol' => [['Öneri', 'm'], ['Yıllık tutar', 'tl'], ['', 'm']], 'sat' => array_map(fn ($x) => [$x['baslik'], $x['tutar'], ''], $fr)],
                    ['baslik' => 'Dayanak: gizli zamların yıllık etkisi', 'grafik' => 'bar', 'renk' => 'E11D48', 'kol' => [['Malzeme', 'm'], ['Yıllık etki', 'tl'], ['Artış %', 'y']], 'sat' => array_map(fn ($z) => [$z['ad'], $z['yillik_etki'], $z['yuzde']], array_slice($v['zamlar'], 0, 8))],
                    ['baslik' => 'Dayanak: tedarikçi farkından yıllık kayıp', 'grafik' => 'bar', 'renk' => '0284C7', 'kol' => [['Malzeme', 'm'], ['Yıllık kayıp', 'tl'], ['En ucuz', 'm']], 'sat' => array_map(fn ($x) => [$x['ad'], $x['yillik_kayip'], $x['en_ucuz_ted']], array_slice($v['karsilastirma'], 0, 8))],
                ];
                break;
            default:
                return _fr2Pano($v);
        }
        $p['bloklar'] = array_values(array_filter($p['bloklar'], fn ($b) => count($b['sat']) > 0));
        return $p;
    }
}

if (!function_exists('_fr2Sayfalar')) {
    // Rapor → sayfalar. Kolon: [başlık, tip(m|tl|y|s|t), genişlik, bayraklar]
    // bayraklar: top=toplam satırı, bar=veri çubuğu (büyüklük), esik:N = N ve üstü kırmızı (tanımlı eşik), mat=ısı matrisi, onc=öncelik rengi, sec=Evet/Hayır listesi
    // Satır: düz dizi; fatura gruplu sayfada ['_baslik'=>metin] grup başlığı, ['_ara'=>true] ara toplam
    function _fr2Sayfalar($rapor, array $v, $ai = null)
    {
        $tr = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('d.m.Y') : '';
        $e = _fr2Ek($v); $es = 'esik:' . $v['filtre']['esik'];
        $pano = _fr2RaporPano($rapor, $v, $e, $ai);
        $zam = ['ad' => 'Gizli Zamlar', 'not' => 'Kıyas: dönemdeki İLK alış → SON alış. "Aynı tedarikçide" = son tedarikçinin kendi ilk fiyatına göre değişim. Yıllık etki = (son − ilk) × dönemdeki miktar, yıla oranlanmış. Kırmızı: zam eşiği (%' . $v['filtre']['esik'] . ') ve üstü.',
            'kol' => [['Malzeme', 'm', 24], ['Birim', 'm', 7], ['İlk Alış Tarihi', 't', 12], ['İlk Tedarikçi', 'm', 22], ['İlk Fatura', 'm', 13], ['İlk Fiyat', 'tl', 12],
                ['Son Alış Tarihi', 't', 12], ['Son Tedarikçi', 'm', 22], ['Son Fatura', 'm', 13], ['Son Fiyat', 'tl', 12], ['Artış %', 'y', 9, $es], ['Aynı Tedarikçide %', 'y', 11, $es],
                ['Zammın Kaynağı', 'm', 18], ['Dönemin En Düşüğü', 'tl', 13], ['En Düşük Kimde', 'm', 22], ['Alış Sayısı', 's', 9], ['Yıllık Etki', 'tl', 14, 'top bar']],
            'sat' => array_map(fn ($z) => [$z['ad'], $z['birim'], $z['ilk_tarih'], $z['ilk_ted'], $z['ilk_fatura'], $z['ilk'], $z['son_tarih'], $z['son_ted'], $z['son_fatura'], $z['son'],
                $z['yuzde'], $z['ayni_ted_yuzde'], $z['ted_degisti'] ? 'Tedarikçi değişimi' : 'Aynı tedarikçi', $z['en_dusuk'], $z['en_dusuk_ted'], $z['adet'], $z['yillik_etki']], $v['zamlar'])];
        $mx = 0; foreach ($v['zamlar'] as $z) $mx = max($mx, count($v['gecmis'][$z['id']] ?? []));
        $mx = min($mx, 10); $seyirKol = [['Malzeme', 'm', 22], ['Birim', 'm', 7]];
        for ($i = 1; $i <= $mx; $i++) $seyirKol[] = [$i . '. alış', 'tl', 11];
        $seyirKol[] = ['İlk → Son %', 'y', 11, $es];
        $seyir = ['ad' => 'Fiyat Seyri', 'not' => 'Zamlı malzemelerin dönemdeki alış fiyatları, tarih sırasıyla (soldan sağa). Son ' . $mx . ' alış gösterilir.', 'kol' => $seyirKol,
            'sat' => array_map(function ($z) use ($v, $mx) {
                $g = array_slice($v['gecmis'][$z['id']] ?? [], -$mx); $r = [$z['ad'], $z['birim']];
                for ($i = 0; $i < $mx; $i++) $r[] = isset($g[$i]) ? (float) $g[$i]['birim_fiyat'] : null;
                $r[] = $z['yuzde']; return $r;
            }, $v['zamlar'])];
        $mlz = ['ad' => 'Malzeme Fiyatları', 'not' => 'Ortalama = miktar ağırlıklı (toplam tutar ÷ toplam miktar). Oynaklık = (en yüksek − en düşük) ÷ en düşük. Tasarruf payı = (ortalama − en düşük) × miktar. ABC: harcamaya göre kümülatif pay ≤%80 A, ≤%95 B, kalan C.',
            'kol' => [['Malzeme', 'm', 24], ['ABC', 'm', 6], ['Harcama Payı %', 'y', 10, 'top'], ['Kümülatif %', 'y', 10], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Alış', 's', 7], ['Miktar', 's', 10], ['Harcama', 'tl', 15, 'top bar'], ['Ort. Fiyat', 'tl', 12],
                ['En Düşük', 'tl', 12], ['En Düşük Kimden', 'm', 20], ['En Yüksek', 'tl', 12], ['En Yüksek Kimden', 'm', 20], ['Son Fiyat', 'tl', 12], ['Son Kimden', 'm', 20],
                ['Son Tarih', 't', 11], ['Tedarikçi', 's', 9], ['Oynaklık %', 'y', 10, 'bar'], ['Tasarruf Payı', 'tl', 14, 'top bar']],
            'sat' => array_map(fn ($m) => [$m['ad'], $e['abc'][$m['id']]['sinif'] ?? '', $e['abc'][$m['id']]['pay'] ?? null, $e['abc'][$m['id']]['kum'] ?? null, $m['kategori'], $m['birim'], $m['alis_say'], $m['miktar'], $m['harcama'], $m['ort'], $m['min'], $m['min_ted'], $m['max'], $m['max_ted'],
                $m['son'], $m['son_ted'], $m['son_tarih'], $m['ted_say'], $m['oynaklik'], $m['tasarruf']], $v['malzemeler'])];
        $tsat = [];
        foreach ($v['karsilastirma'] as $k) foreach ($k['tedler'] as $i => $t)
            $tsat[] = [$k['ad'], $k['birim'], $t['ted'], $i === 0 ? 0.0 : ($k['en_ucuz'] > 0 ? round(($t['ort'] - $k['en_ucuz']) / $k['en_ucuz'] * 100, 1) : 0), $t['ted'] === $k['son_ted'] ? 'Evet' : '',
                $t['ort'], $t['son'], $t['son_tarih'], $t['miktar'], $t['harcama'], $t['alis_say'], $i === 0 ? $k['kayip'] : null, $i === 0 ? $k['yillik_kayip'] : null];
        $ted = ['ad' => 'Tedarikçi Karşılaştırma', 'not' => 'Gerçek alış faturalarından; her malzemede tedarikçiler ucuzdan pahalıya. "En ucuza göre %" = o tedarikçinin ortalamasının en ucuz ortalamaya farkı. Kayıp = her tedarikçiden alınan miktar en ucuz ortalamadan alınsaydı oluşacak fark.',
            'kol' => [['Malzeme', 'm', 24], ['Birim', 'm', 7], ['Tedarikçi', 'm', 24], ['En Ucuza Göre %', 'y', 12], ['Son Alınan', 'm', 10], ['Ort. Fiyat', 'tl', 12], ['Son Fiyat', 'tl', 12], ['Son Tarih', 't', 11],
                ['Miktar', 's', 10], ['Harcama', 'tl', 14, 'top'], ['Alış', 's', 7], ['Dönem Kaybı', 'tl', 14, 'top bar'], ['Yıllık Kayıp', 'tl', 14, 'top bar']], 'sat' => $tsat];
        $katS = ['ad' => 'Katalog Fiyatları', 'not' => 'Tedarikçi kartlarındaki güncel liste fiyatları (alış faturası değil).',
            'kol' => [['Malzeme', 'm', 24], ['En Ucuz Tedarikçi', 'm', 22], ['En Ucuz', 'tl', 12], ['En Pahalı Tedarikçi', 'm', 22], ['En Pahalı', 'tl', 12], ['Fark %', 'y', 10], ['Tedarikçi Sayısı', 's', 10]],
            'sat' => array_map(fn ($k) => [$k['ad'], $k['en_ucuz_ted'], $k['en_ucuz'], $k['en_pahali_ted'], $k['en_pahali'], $k['tasarruf'], $k['ted_say']], $v['katalog'])];
        $karne = ['ad' => 'Tedarikçi Karnesi', 'not' => 'Zamlı kalem = aynı tedarikçide fiyatı %' . $v['filtre']['esik'] . '+ artan malzeme. Pahalı kaldığı = başka tedarikçiden %5+ ucuz alınmış malzeme. Karne puanı = 100 − 50 × (pahalı kaldığı ÷ malzeme) − 50 × (zamlı kalem ÷ malzeme); 85+ A, 70+ B, 50+ C, altı D.',
            'kol' => [['Tedarikçi', 'm', 26], ['Karne Puanı', 's', 10, 'bar'], ['Not', 'm', 6], ['Harcama', 'tl', 15, 'top bar'], ['Pay %', 'y', 9, 'top'], ['Fatura', 's', 8, 'top'], ['Kalem', 's', 8, 'top'], ['Malzeme', 's', 9], ['Zamlı Kalem', 's', 11, 'top'],
                ['Ort. Zam %', 'y', 10], ['Pahalı Kaldığı', 's', 12, 'top'], ['Son Alış', 't', 11]],
            'sat' => array_map(fn ($k) => [$k['ted'], $e['puan'][$k['ted']]['puan'] ?? null, $e['puan'][$k['ted']]['harf'] ?? '', $k['harcama'], $k['pay'], $k['fatura'], $k['kalem'], $k['malzeme'], $k['zamli'], $k['ort_zam'], $k['pahali'], $k['son_alis']], $v['karne'])];
        $hal = ['ad' => 'Hal Kıyası', 'not' => 'İzmir Büyükşehir hal bülteni' . (!empty($v['hal']['bulten']) ? ' (' . $tr($v['hal']['bulten']) . ')' : '') . '. Fark = son alış ÷ hal ortalaması − 1 (yalnız birimler aynıysa). Kırmızı: %20 üstü (hal bandı tanımı).',
            'kol' => [['Malzeme', 'm', 22], ['Son Tedarikçi', 'm', 22], ['Haldeki Karşılığı', 'm', 28], ['Hal Asgari', 'tl', 11], ['Hal Ortalama', 'tl', 12], ['Hal Azami', 'tl', 11],
                ['Hal Birimi', 'm', 8], ['Senin Son Alış', 'tl', 13], ['Senin Birimin', 'm', 9], ['Son Alış Tarihi', 't', 12], ['Fark %', 'y', 9, 'esik:20.01'], ['Bant', 'm', 18], ['Yıllık Fazla', 'tl', 13, 'top bar']],
            'sat' => array_map(fn ($h) => [$h['ad'], $h['tedarikci'] ?? '', $h['hal_ad'], $h['hal_min'] ?: null, $h['hal_ort'], $h['hal_max'] ?: null, $h['hal_birim'],
                $h['uyumlu'] ? $h['fiyat_hal_birim'] : $h['son_fiyat'], $h['uyumlu'] ? $h['hal_birim'] : $h['birim'], $h['son_tarih'], $h['fark'],
                $h['fark'] === null ? 'Birim farklı' : ($h['fark'] < 0 ? 'Halin altında' : ($h['fark'] <= 20 ? 'Normal bant' : 'Bant üstü')), $h['yillik'] ?: null], $v['hal']['satirlar'] ?? [])];
        $dokKol = [['Tarih', 't', 11], ['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Tedarikçi', 'm', 24], ['Fatura No', 'm', 14], ['Miktar', 's', 10], ['Birim', 'm', 7],
            ['Birim Fiyat', 'tl', 12], ['Tutar', 'tl', 14, 'top bar'], ['Önceki Alışa Göre %', 'y', 12]];
        $dokum = ['ad' => 'Satır Dökümü', 'not' => 'Dönemdeki her alış satırı (yeniden eskiye). Önceki alışa göre % = aynı malzemenin bir önceki alışına göre birim fiyat değişimi.', 'kol' => $dokKol,
            'sat' => array_map(fn ($d) => [$d['tarih'], $d['malzeme'], $d['kategori'], $d['tedarikci'], $d['fatura_no'], $d['miktar'], $d['birim'], $d['birim_fiyat'], $d['tutar'], $d['degisim']], $v['dokum'])];
        $fsat = [];
        foreach ($e['fatura'] as $fa) {
            $fsat[] = ['_baslik' => $tr($fa['tarih']) . ' · ' . $fa['ted'] . ' · Fatura ' . $fa['no'] . ' · ' . count($fa['sat']) . ' kalem'];
            foreach ($fa['sat'] as $d) $fsat[] = [$d['tarih'], $d['malzeme'], $d['kategori'], $d['tedarikci'], $d['fatura_no'], $d['miktar'], $d['birim'], $d['birim_fiyat'], $d['tutar'], $d['degisim']];
            $fsat[] = ['_ara' => 'Fatura ' . $fa['no'] . ' toplamı'];
        }
        $faturaS = ['ad' => 'Fatura Bazlı Döküm', 'not' => 'Faturalar tarih sırasıyla (yeniden eskiye); her fatura altında ara toplam. En alttaki toplam ara toplamları iki kez saymaz.', 'kol' => $dokKol, 'sat' => $fsat];
        $ayKol = [['Ay', 'm', 14], ['Toplam Alış', 'tl', 16, 'top bar'], ['Önceki Aya Göre %', 'y', 14], ['Alış Satırı', 's', 11, 'top']];
        $aySay = []; foreach ($v['dokum'] as $d) { $a = substr($d['tarih'], 0, 7); $aySay[$a] = ($aySay[$a] ?? 0) + 1; }
        $aylik = ['ad' => 'Aylık Harcama', 'kol' => $ayKol, 'sat' => array_map(fn ($a, $i) => [_fr2AyAd($a['ay']), $a['tutar'], $i > 0 && $v['aylik'][$i - 1]['tutar'] > 0 ? round(($a['tutar'] - $v['aylik'][$i - 1]['tutar']) / $v['aylik'][$i - 1]['tutar'] * 100, 1) : null, $aySay[$a['ay']] ?? 0], $v['aylik'], array_keys($v['aylik']))];
        $kategori = ['ad' => 'Kategori Dağılımı', 'kol' => [['Kategori', 'm', 24], ['Harcama', 'tl', 16, 'top bar'], ['Pay %', 'y', 9, 'top']], 'sat' => array_map(fn ($k) => [$k['ad'], $k['tutar'], $k['pay']], $v['kategori'])];
        $matris = function ($ad, $bas, $veri) use ($e) {
            $kol = [[$bas, 'm', 24]]; foreach ($e['aylar'] as $a) $kol[] = [_fr2AyAd($a), 'tl', 13, 'top mat']; $kol[] = ['Toplam', 'tl', 15, 'top bar'];
            $sat = []; foreach ($veri as $ad2 => $aylar) { $r = [$ad2]; foreach ($e['aylar'] as $a) $r[] = $aylar[$a] ?? null; $r[] = array_sum($aylar); $sat[] = $r; }
            return ['ad' => $ad, 'not' => 'Hücre rengi koyulaştıkça o ay o satırdaki harcama yüksek (yalnız büyüklük gösterir).', 'kol' => $kol, 'sat' => $sat];
        };
        $katAy = $matris('Kategori × Ay', 'Kategori', $e['katAy']);
        $tedAy = $matris('Tedarikçi × Ay', 'Tedarikçi', $e['tedAy']);
        $aiS = [];
        if ($ai) {
            $uyari = 'Bu sayfa YAPAY ZEKÂ tarafından hazırlanmıştır (' . ($ai['_zaman'] ?? '') . ', son ' . ($ai['_donem'] ?? $v['filtre']['gun']) . ' günün verisi). Öneriler karar desteğidir; son karar satın almacınındır.';
            $bol = ['kritik' => 'Kritik bulgu', 'gecmis_hatalar' => 'Geçmiş hata', 'firsatlar' => 'Tasarruf fırsatı', 'gelecek' => 'Gelecek önerisi'];
            $sat = [['Genel değerlendirme', 'Satın alma puanı ' . (int) ($ai['skor'] ?? 0) . '/100', $ai['ozet'] ?? '', null]];
            foreach ($bol as $kk => $ad) foreach ((array) ($ai[$kk] ?? []) as $x) $sat[] = [$ad, $x['baslik'] ?? '', $x['detay'] ?? '', isset($x['tutar']) && is_numeric($x['tutar']) && $x['tutar'] > 0 ? (float) $x['tutar'] : null];
            $aiS[] = ['ad' => 'AI Değerlendirme', 'ai' => 1, 'not' => $uyari, 'kol' => [['Bölüm', 'm', 18], ['Başlık', 'm', 34], ['AI Yorumu / Gerekçe', 'm', 80], ['Tahmini Yıllık Tutar', 'tl', 18, 'top bar']], 'sat' => $sat];
            $oncAd = ['yuksek' => 'YÜKSEK', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'];
            $aiS[] = ['ad' => 'Aksiyon Planı', 'ai' => 1, 'not' => $uyari . ' "Yapıldı mı?" sütunundan durum seç; takip listesi olarak kullan.',
                'kol' => [['Öncelik', 'm', 11, 'onc'], ['Yapılacak (AI önerisi)', 'm', 80], ['Sorumlu', 'm', 16], ['Yapıldı mı?', 'm', 16, 'sec'], ['Not', 'm', 30]],
                'sat' => array_map(fn ($a) => [$oncAd[$a['oncelik'] ?? ''] ?? mb_strtoupper((string) ($a['oncelik'] ?? ''), 'UTF-8'), $a['is'] ?? '', $a['sorumlu'] ?? '', '', ''], (array) ($ai['aksiyonlar'] ?? []))];
            $kHar = []; foreach ($v['karne'] as $kr) $kHar[$kr['ted']] = $kr;
            $aiS[] = ['ad' => 'Pazarlık Notları', 'ai' => 1, 'not' => $uyari, 'kol' => [['Tedarikçi', 'm', 24], ['AI Pazarlık Argümanı', 'm', 80], ['Dönem Harcaması', 'tl', 16, 'top bar'], ['Pay %', 'y', 9], ['Zamlı Kalem', 's', 11], ['Pahalı Kaldığı', 's', 12]],
                'sat' => array_map(function ($p) use ($kHar) { $kr = $kHar[$p['tedarikci'] ?? ''] ?? null; return [$p['tedarikci'] ?? '', $p['arguman'] ?? '', $kr['harcama'] ?? null, $kr['pay'] ?? null, $kr['zamli'] ?? null, $kr['pahali'] ?? null]; }, (array) ($ai['pazarlik'] ?? []))];
        }
        switch ($rapor) {
            case 'zam': return [$pano, $zam, $seyir];
            case 'malzeme': return [$pano, $mlz];
            case 'tedarikci': return [$pano, $ted, $katS];
            case 'karne': return [$pano, $karne];
            case 'hal': return [$pano, $hal];
            case 'dokum': return [$pano, $faturaS, $dokum];
            case 'aylik': return [$pano, $aylik, $kategori, $katAy, $tedAy];
            case 'ai':
                if (!$aiS) return [$pano, ['ad' => 'AI Analiz', 'kol' => [['Bilgi', 'm', 80]], 'sat' => [['Henüz AI analizi yapılmadı. Fiyat Radarı > AI Analiz sekmesinden "Analiz Et" de.']]]];
                foreach ([$zam, $ted, $karne, $hal] as $kanit) { $kanit['ad'] = 'Dayanak - ' . $kanit['ad']; $aiS[] = $kanit; }
                return array_merge([$pano], $aiS);
            default: return [$pano, $zam, $mlz, $ted, $katS, $karne, $hal, $aylik, $kategori, $katAy, $faturaS];
        }
    }
}

if (!function_exists('_fr2Xlsx')) {
    // Gerçek .xlsx (ZipArchive): rapora özel Pano (kartlar + grafikler), veri çubukları, tanımlı eşik kuralı, ısı matrisi, SUBTOTAL toplam/ara toplam, sabit başlık, filtre, sekme rengi.
    function _fr2Xlsx(array $sayfalar, $baslik, $altBaslik, $tema = '6D28D9')
    {
        if (!class_exists('ZipArchive')) return null;
        $tema = strtoupper(ltrim($tema, '#'));
        $x = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $harf = function ($n) { $s = ''; $n++; while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m) . $s; $n = intdiv($n - 1, 26); } return $s; };
        // ---- stil kayıt defteri (tekilleştirilmiş) ----
        $fonts = ['<font><sz val="10"/><name val="Calibri"/></font>']; $fills = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>'];
        $borders = ['<border/>', '<border><bottom style="thin"><color rgb="FFE4E7F2"/></bottom></border>', '<border><top style="medium"><color rgb="FF' . $tema . '"/></top></border>'];
        $xfs = ['<xf/>']; $xfIdx = ['' => 0];
        $kayit = function (array &$liste, $xml) { $i = array_search($xml, $liste, true); if ($i === false) { $liste[] = $xml; $i = count($liste) - 1; } return $i; };
        // dikkat: arrow fn ($fonts'u kopyalar) değil, referanslı closure
        $font = function ($sz, $renk = '1E293B', $b = false, $i = false) use (&$fonts, $kayit) { return $kayit($fonts, '<font>' . ($b ? '<b/>' : '') . ($i ? '<i/>' : '') . '<sz val="' . $sz . '"/><color rgb="FF' . $renk . '"/><name val="Calibri"/></font>'); };
        $fill = function ($renk) use (&$fills, $kayit) { return $kayit($fills, '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $renk . '"/></patternFill></fill>'); };
        $xf = function ($fontId = 0, $fillId = 0, $numFmt = 0, $border = 0, $hiza = '') use (&$xfs, &$xfIdx) {
            $anah = "$fontId|$fillId|$numFmt|$border|$hiza";
            if (isset($xfIdx[$anah])) return $xfIdx[$anah];
            $xfs[] = '<xf numFmtId="' . $numFmt . '" fontId="' . $fontId . '" fillId="' . $fillId . '" borderId="' . $border . '" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"' . ($hiza ? ' applyAlignment="1">' . $hiza . '</xf>' : '/>');
            return $xfIdx[$anah] = count($xfs) - 1;
        };
        $sar = '<alignment vertical="top" wrapText="1"/>';
        $acik = 'F5F3FF';
        $S = [
            'baslik' => $xf($font(15, $tema, true)), 'alt' => $xf($font(9, '64748B', false, true)),
            'kolon' => $xf($font(10, 'FFFFFF', true), $fill($tema), 0, 0, '<alignment vertical="center" wrapText="1"/>'),
            'tl' => $xf(0, 0, 164, 1), 'y' => $xf(0, 0, 165, 1), 's' => $xf(0, 0, 166, 1), 'm' => $xf(0, 0, 0, 1, $sar),
            'top_m' => $xf($font(10, '1E293B', true), $fill($acik), 0, 2), 'top_tl' => $xf($font(10, '1E293B', true), $fill($acik), 164, 2),
            'top_y' => $xf($font(10, '1E293B', true), $fill($acik), 165, 2), 'top_s' => $xf($font(10, '1E293B', true), $fill($acik), 166, 2),
            'grup' => $xf($font(10, $tema, true), $fill('F8FAFC'), 0, 1), 'ara_m' => $xf($font(9, '475569', true), 0, 0, 1), 'ara_tl' => $xf($font(9, '475569', true), 0, 164, 1),
            'blok' => $xf($font(12, $tema, true)),
        ];
        $tmp = tempnam(sys_get_temp_dir(), 'frx');
        $z = new \ZipArchive();
        if ($z->open($tmp, \ZipArchive::OVERWRITE) !== true) return null;
        $wb = ''; $rels = ''; $ct = ''; $kullanilan = []; $cizimNo = 0; $grafikNo = 0;
        $hucre = function ($ref, $val, $tip, $stil) {
            if ($val === null || $val === '') return '';
            if (in_array($tip, ['tl', 'y', 's', 'n'], true) && is_numeric($val)) return '<c r="' . $ref . '" s="' . $stil . '"><v>' . (float) $val . '</v></c>';
            if ($tip === 't' && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $val)) $val = \Carbon\Carbon::parse($val)->format('d.m.Y');
            return '<c r="' . $ref . '" t="inlineStr" s="' . $stil . '"><is><t xml:space="preserve">' . htmlspecialchars((string) $val, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
        };
        // Grafik XML (sütun/çubuk/halka) — veriler Pano'daki hücrelere bağlı + önbellek değerleri
        $grafikXml = function ($tip, $bas, $sayfa, $catRef, $valRef, array $cats, array $vals, $renk, $fmt) use ($x, $tema) {
            $n = count($cats);
            $strC = '<c:strCache><c:ptCount val="' . $n . '"/>'; foreach ($cats as $i => $c) $strC .= '<c:pt idx="' . $i . '"><c:v>' . $x($c) . '</c:v></c:pt>'; $strC .= '</c:strCache>';
            $numC = '<c:numCache><c:formatCode>General</c:formatCode><c:ptCount val="' . $n . '"/>'; foreach ($vals as $i => $vv) $numC .= '<c:pt idx="' . $i . '"><c:v>' . (float) $vv . '</c:v></c:pt>'; $numC .= '</c:numCache>';
            $ref = fn ($r) => "'" . str_replace("'", "''", $sayfa) . "'!" . $r;
            $catvals = '<c:cat><c:strRef><c:f>' . $x($ref($catRef)) . '</c:f>' . $strC . '</c:strRef></c:cat><c:val><c:numRef><c:f>' . $x($ref($valRef)) . '</c:f>' . $numC . '</c:numRef></c:val>';
            $baslikXml = '<c:title><c:tx><c:rich><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="1200" b="1"/></a:pPr><a:r><a:rPr lang="tr-TR" sz="1200" b="1"><a:solidFill><a:srgbClr val="' . $tema . '"/></a:solidFill></a:rPr><a:t>' . $x($bas) . '</a:t></a:r></a:p></c:rich></c:tx><c:overlay val="0"/></c:title><c:autoTitleDeleted val="0"/>';
            $etiket = fn ($fmtK, $yuzde) => '<c:dLbls><c:numFmt formatCode="' . $x($fmtK) . '" sourceLinked="0"/><c:spPr><a:noFill/><a:ln><a:noFill/></a:ln></c:spPr><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="900" b="1"/></a:pPr><a:endParaRPr lang="tr-TR"/></a:p></c:txPr>'
                . '<c:showLegendKey val="0"/><c:showVal val="' . ($yuzde ? 0 : 1) . '"/><c:showCatName val="0"/><c:showSerName val="0"/><c:showPercent val="' . ($yuzde ? 1 : 0) . '"/><c:showBubbleSize val="0"/></c:dLbls>';
            $sayiFmt = $fmt === 'y' ? '0.0"%"' : '#,##0';
            if ($tip === 'pie') {
                $palet = ['7C3AED', 'F59E0B', '0EA5E9', '10B981', 'EC4899', '4F46E5', 'F43F5E', '64748B', '14B8A6', 'A855F7'];
                $dpt = ''; foreach ($cats as $i => $c) $dpt .= '<c:dPt><c:idx val="' . $i . '"/><c:bubble3D val="0"/><c:spPr><a:solidFill><a:srgbClr val="' . $palet[$i % 10] . '"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></a:ln></c:spPr></c:dPt>';
                $govde = '<c:doughnutChart><c:varyColors val="1"/><c:ser><c:idx val="0"/><c:order val="0"/><c:tx><c:v>' . $x($bas) . '</c:v></c:tx>' . $dpt . $etiket('0%', true) . $catvals . '</c:ser><c:firstSliceAng val="0"/><c:holeSize val="55"/></c:doughnutChart>';
                $alt = '<c:legend><c:legendPos val="r"/><c:overlay val="0"/></c:legend>';
            } else {
                $yatay = $tip === 'bar';
                $govde = '<c:barChart><c:barDir val="' . ($yatay ? 'bar' : 'col') . '"/><c:grouping val="clustered"/><c:varyColors val="0"/><c:ser><c:idx val="0"/><c:order val="0"/><c:tx><c:v>' . $x($bas) . '</c:v></c:tx>'
                    . '<c:spPr><a:solidFill><a:srgbClr val="' . $renk . '"/></a:solidFill></c:spPr><c:invertIfNegative val="0"/>' . $etiket($sayiFmt, false) . $catvals . '</c:ser><c:gapWidth val="55"/><c:axId val="5001"/><c:axId val="5002"/></c:barChart>'
                    . '<c:catAx><c:axId val="5001"/><c:scaling><c:orientation val="' . ($yatay ? 'maxMin' : 'minMax') . '"/></c:scaling><c:delete val="0"/><c:axPos val="' . ($yatay ? 'l' : 'b') . '"/><c:numFmt formatCode="General" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="low"/><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="900"/></a:pPr><a:endParaRPr lang="tr-TR"/></a:p></c:txPr><c:crossAx val="5002"/><c:crosses val="autoZero"/><c:auto val="1"/><c:lblAlgn val="ctr"/><c:lblOffset val="100"/><c:noMultiLvlLbl val="0"/></c:catAx>'
                    . '<c:valAx><c:axId val="5002"/><c:scaling><c:orientation val="minMax"/></c:scaling><c:delete val="0"/><c:axPos val="' . ($yatay ? 't' : 'l') . '"/><c:majorGridlines><c:spPr><a:ln><a:solidFill><a:srgbClr val="E4E7F2"/></a:solidFill></a:ln></c:spPr></c:majorGridlines><c:numFmt formatCode="' . $x($sayiFmt) . '" sourceLinked="0"/><c:majorTickMark val="none"/><c:minorTickMark val="none"/><c:tickLblPos val="nextTo"/><c:txPr><a:bodyPr/><a:lstStyle/><a:p><a:pPr><a:defRPr sz="800"/></a:pPr><a:endParaRPr lang="tr-TR"/></a:p></c:txPr><c:crossAx val="5001"/><c:crosses val="autoZero"/><c:crossBetween val="between"/></c:valAx>';
                $alt = '';
            }
            return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><c:chartSpace xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<c:roundedCorners val="0"/><c:chart>' . $baslikXml . '<c:plotArea><c:layout/>' . $govde . '</c:plotArea>' . $alt . '<c:plotVisOnly val="1"/><c:dispBlanksAs val="gap"/></c:chart>'
                . '<c:spPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:ln><a:solidFill><a:srgbClr val="E4E7F2"/></a:solidFill></a:ln></c:spPr></c:chartSpace>';
        };
        foreach (array_values($sayfalar) as $i => $s) {
            $n = $i + 1;
            $ad = mb_substr(preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', $s['ad']), 0, 31);
            while (isset($kullanilan[$ad])) $ad = mb_substr($ad, 0, 28) . ' ' . $n;
            $kullanilan[$ad] = 1;
            $rows = ''; $merge = []; $cf = ''; $dv = ''; $cols = ''; $cizim = ''; $sheetRels = ''; $pane = true; $filtre = ''; $pri = 1;
            if (($s['tip'] ?? '') === 'pano') {
                // ---------- PANO ----------
                $pane = false;
                $cols = '<cols><col min="1" max="1" width="2" customWidth="1"/><col min="2" max="13" width="12.5" customWidth="1"/></cols>';
                $rows .= '<row r="1" ht="24" customHeight="1"><c r="B1" t="inlineStr" s="' . $S['baslik'] . '"><is><t>' . $x($baslik) . '</t></is></c></row>';
                $rows .= '<row r="2"><c r="B2" t="inlineStr" s="' . $S['alt'] . '"><is><t>' . $x($altBaslik) . '</t></is></c></row>';
                $r = 4;
                foreach (array_chunk($s['kpi'], 4) as $grup) {
                    $l1 = '<row r="' . $r . '" ht="18" customHeight="1">'; $l2 = '<row r="' . ($r + 1) . '" ht="22" customHeight="1">'; $l3 = '<row r="' . ($r + 2) . '" ht="16" customHeight="1">';
                    foreach ($grup as $gi => [$et, $deg, $fmt, $renk]) {
                        $c0 = 1 + $gi * 3; $a = $harf($c0); $b = $harf($c0 + 2);
                        $fl = $fill($renk);
                        $stL = $xf($font(9, 'FFFFFF', true), $fl, 0, 0, '<alignment horizontal="left" vertical="center" indent="1"/>');
                        $stV = $xf($font($fmt === 'metin' ? 13 : 18, 'FFFFFF', true), $fl, $fmt === 'tl' ? 167 : ($fmt === 'y' ? 165 : 0), 0, '<alignment horizontal="left" vertical="center" indent="1" wrapText="1"/>');
                        $l1 .= '<c r="' . $a . $r . '" t="inlineStr" s="' . $stL . '"><is><t>' . $x($et) . '</t></is></c>';
                        for ($c = $c0 + 1; $c <= $c0 + 2; $c++) $l1 .= '<c r="' . $harf($c) . $r . '" s="' . $stL . '"/>';
                        $l2 .= ($fmt === 'metin' ? '<c r="' . $a . ($r + 1) . '" t="inlineStr" s="' . $stV . '"><is><t>' . $x($deg) . '</t></is></c>' : '<c r="' . $a . ($r + 1) . '" s="' . $stV . '"><v>' . (float) $deg . '</v></c>');
                        for ($c = $c0 + 1; $c <= $c0 + 2; $c++) $l2 .= '<c r="' . $harf($c) . ($r + 1) . '" s="' . $stV . '"/>';
                        for ($c = $c0; $c <= $c0 + 2; $c++) $l3 .= '<c r="' . $harf($c) . ($r + 2) . '" s="' . $stV . '"/>';
                        $merge[] = $a . $r . ':' . $b . $r; $merge[] = $a . ($r + 1) . ':' . $b . ($r + 2);
                    }
                    $rows .= $l1 . '</row>' . $l2 . '</row>' . $l3 . '</row>';
                    $r += 4;
                }
                $r += 1;
                $anchors = ''; $drRels = ''; $gSay = 0;
                foreach ($s['bloklar'] as $bl) {
                    $bas = $r; $adet = count($bl['sat']); $vt = $bl['kol'][1][1];
                    $rows .= '<row r="' . $r . '" ht="20" customHeight="1"><c r="B' . $r . '" t="inlineStr" s="' . $S['blok'] . '"><is><t>' . $x($bl['baslik']) . '</t></is></c></row>';
                    $r++;
                    // tablo: B:C (ad, birleşik) · D (değer) · E (ek)
                    $rows .= '<row r="' . $r . '">'; foreach ([['B', 0], ['D', 1], ['E', 2]] as [$ck, $ki]) { $rows .= '<c r="' . $ck . $r . '" t="inlineStr" s="' . $S['kolon'] . '"><is><t>' . $x($bl['kol'][$ki][0]) . '</t></is></c>'; if ($ck === 'B') $rows .= '<c r="C' . $r . '" s="' . $S['kolon'] . '"/>'; }
                    $rows .= '</row>'; $merge[] = 'B' . $r . ':C' . $r; $r++;
                    $ilk = $r; $cats = []; $vals = [];
                    foreach ($bl['sat'] as $sat) {
                        $rows .= '<row r="' . $r . '">' . $hucre('B' . $r, $sat[0], 'm', $S['m']) . '<c r="C' . $r . '" s="' . $S['m'] . '"/>' . $hucre('D' . $r, $sat[1], $vt, $S[$vt] ?? $S['m'])
                            . $hucre('E' . $r, $sat[2] ?? null, $bl['kol'][2][1], $S[$bl['kol'][2][1]] ?? $S['m']) . '</row>';
                        $merge[] = 'B' . $r . ':C' . $r; $cats[] = (string) $sat[0]; $vals[] = (float) $sat[1]; $r++;
                    }
                    $son = $r - 1;
                    if ($vt !== 'y') {
                        $rows .= '<row r="' . $r . '"><c r="B' . $r . '" t="inlineStr" s="' . $S['top_m'] . '"><is><t>TOPLAM</t></is></c><c r="C' . $r . '" s="' . $S['top_m'] . '"/><c r="D' . $r . '" s="' . ($S['top_' . $vt] ?? $S['top_s']) . '"><f>SUM(D' . $ilk . ':D' . $son . ')</f></c><c r="E' . $r . '" s="' . $S['top_m'] . '"/></row>';
                        $merge[] = 'B' . $r . ':C' . $r;
                    }
                    $cf .= '<conditionalFormatting sqref="D' . $ilk . ':D' . $son . '"><cfRule type="dataBar" priority="' . ($pri++) . '"><dataBar><cfvo type="min"/><cfvo type="max"/><color rgb="FF' . $bl['renk'] . '"/></dataBar></cfRule></conditionalFormatting>';
                    $yuk = $adet + 3;
                    if (($bl['grafik'] ?? '') !== 'tablo') {
                        // grafik: G..M sütunları, bloğun yanında
                        $grafikNo++; $gSay++; $gid = $grafikNo;
                        $z->addFromString('xl/charts/chart' . $gid . '.xml', $grafikXml($bl['grafik'], $bl['baslik'], $ad, '$B$' . $ilk . ':$B$' . $son, '$D$' . $ilk . ':$D$' . $son, $cats, $vals, $bl['renk'], $vt));
                        $ct .= '<Override PartName="/xl/charts/chart' . $gid . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawingml.chart+xml"/>';
                        $yuk = max(16, $adet + 4);
                        $anchors .= '<xdr:twoCellAnchor editAs="oneCell"><xdr:from><xdr:col>6</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . ($bas - 1) . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
                            . '<xdr:to><xdr:col>13</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>' . ($bas - 1 + $yuk) . '</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
                            . '<xdr:graphicFrame macro=""><xdr:nvGraphicFramePr><xdr:cNvPr id="' . ($gSay + 1) . '" name="Grafik ' . $gSay . '"/><xdr:cNvGraphicFramePr/></xdr:nvGraphicFramePr><xdr:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/></xdr:xfrm>'
                            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/chart"><c:chart xmlns:c="http://schemas.openxmlformats.org/drawingml/2006/chart" r:id="rId' . $gSay . '"/></a:graphicData></a:graphic></xdr:graphicFrame><xdr:clientData/></xdr:twoCellAnchor>';
                        $drRels .= '<Relationship Id="rId' . $gSay . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/chart" Target="../charts/chart' . $gid . '.xml"/>';
                    }
                    $r = max($r + 2, $bas + $yuk + 1);
                }
                if ($anchors) {
                    $cizimNo++;
                    $z->addFromString('xl/drawings/drawing' . $cizimNo . '.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . $anchors . '</xdr:wsDr>');
                    $z->addFromString('xl/drawings/_rels/drawing' . $cizimNo . '.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $drRels . '</Relationships>');
                    $ct .= '<Override PartName="/xl/drawings/drawing' . $cizimNo . '.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
                    $cizim = '<drawing r:id="rId1"/>';
                    $sheetRels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing' . $cizimNo . '.xml"/>';
                }
            } else {
                // ---------- TABLO SAYFASI ----------
                $kolSay = count($s['kol']); $sonK = $harf($kolSay - 1);
                $cols = '<cols>'; foreach ($s['kol'] as $c => $k) $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . ($k[2] ?? 14) . '" customWidth="1"/>'; $cols .= '</cols>';
                $rows = '<row r="1" ht="22" customHeight="1"><c r="A1" t="inlineStr" s="' . $S['baslik'] . '"><is><t>' . $x($baslik . ' — ' . $s['ad']) . '</t></is></c></row>';
                $rows .= '<row r="2"><c r="A2" t="inlineStr" s="' . $S['alt'] . '"><is><t>' . $x($altBaslik) . '</t></is></c></row>';
                if (!empty($s['not'])) $rows .= '<row r="3"><c r="A3" t="inlineStr" s="' . $S['alt'] . '"><is><t>' . $x($s['not']) . '</t></is></c></row>';
                $rows .= '<row r="4" ht="30" customHeight="1">';
                foreach ($s['kol'] as $c => $k) $rows .= '<c r="' . $harf($c) . '4" t="inlineStr" s="' . $S['kolon'] . '"><is><t>' . $x($k[0]) . '</t></is></c>';
                $rows .= '</row>';
                $r = 5; $grupIlk = null;
                foreach ($s['sat'] as $sat) {
                    if (isset($sat['_baslik'])) {
                        $rows .= '<row r="' . $r . '"><c r="A' . $r . '" t="inlineStr" s="' . $S['grup'] . '"><is><t>' . $x($sat['_baslik']) . '</t></is></c>';
                        for ($c = 1; $c < $kolSay; $c++) $rows .= '<c r="' . $harf($c) . $r . '" s="' . $S['grup'] . '"/>';
                        $rows .= '</row>'; $merge[] = 'A' . $r . ':' . $sonK . $r; $r++; $grupIlk = $r; continue;
                    }
                    if (isset($sat['_ara'])) {
                        $rows .= '<row r="' . $r . '">';
                        foreach ($s['kol'] as $c => $k) {
                            $ref = $harf($c) . $r;
                            if ($c === 0) $rows .= '<c r="' . $ref . '" t="inlineStr" s="' . $S['ara_m'] . '"><is><t>' . $x($sat['_ara']) . '</t></is></c>';
                            elseif (str_contains($k[3] ?? '', 'top') && $grupIlk) $rows .= '<c r="' . $ref . '" s="' . $S['ara_tl'] . '"><f>SUBTOTAL(9,' . $harf($c) . $grupIlk . ':' . $harf($c) . ($r - 1) . ')</f></c>';
                        }
                        $rows .= '</row>'; $r++; continue;
                    }
                    $rows .= '<row r="' . $r . '">';
                    foreach ($s['kol'] as $c => $k) $rows .= $hucre($harf($c) . $r, $sat[$c] ?? null, $k[1], $S[$k[1]] ?? $S['m']);
                    $rows .= '</row>'; $r++;
                }
                $ilk = 5; $son = $r - 1; $veri = count($s['sat']) > 0;
                if ($veri) {
                    $filtre = '<autoFilter ref="A4:' . $sonK . $son . '"/>';
                    $topVar = false; foreach ($s['kol'] as $k) if (str_contains($k[3] ?? '', 'top')) $topVar = true;
                    if ($topVar) {
                        $rows .= '<row r="' . ($r + 1) . '">';
                        foreach ($s['kol'] as $c => $k) {
                            $ref = $harf($c) . ($r + 1); $tip = $k[1];
                            if ($c === 0) $rows .= '<c r="' . $ref . '" t="inlineStr" s="' . $S['top_m'] . '"><is><t>TOPLAM (filtreye göre)</t></is></c>';
                            elseif (str_contains($k[3] ?? '', 'top')) $rows .= '<c r="' . $ref . '" s="' . ($S['top_' . $tip] ?? $S['top_s']) . '"><f>SUBTOTAL(9,' . $harf($c) . $ilk . ':' . $harf($c) . $son . ')</f></c>';
                            else $rows .= '<c r="' . $ref . '" s="' . $S['top_m'] . '"/>';
                        }
                        $rows .= '</row>';
                    }
                    $matIlk = null; $matSon = null;
                    foreach ($s['kol'] as $c => $k) {
                        $b = $k[3] ?? ''; $aralik = $harf($c) . $ilk . ':' . $harf($c) . $son;
                        if (str_contains($b, 'bar')) $cf .= '<conditionalFormatting sqref="' . $aralik . '"><cfRule type="dataBar" priority="' . ($pri++) . '"><dataBar><cfvo type="min"/><cfvo type="max"/><color rgb="FF' . $tema . '"/></dataBar></cfRule></conditionalFormatting>';
                        if (preg_match('/esik:([\d.]+)/', $b, $mm))
                            $cf .= '<conditionalFormatting sqref="' . $aralik . '"><cfRule type="cellIs" dxfId="0" priority="' . ($pri++) . '" operator="greaterThanOrEqual"><formula>' . (float) $mm[1] . '</formula></cfRule></conditionalFormatting>';
                        if (str_contains($b, 'mat')) { $matIlk ??= $c; $matSon = $c; }
                        if (str_contains($b, 'onc')) foreach (['YÜKSEK' => 0, 'ORTA' => 1, 'DÜŞÜK' => 2] as $deg => $dx)
                            $cf .= '<conditionalFormatting sqref="' . $aralik . '"><cfRule type="cellIs" dxfId="' . $dx . '" priority="' . ($pri++) . '" operator="equal"><formula>"' . $deg . '"</formula></cfRule></conditionalFormatting>';
                        if (str_contains($b, 'sec')) $dv .= '<dataValidation type="list" allowBlank="1" showDropDown="0" sqref="' . $aralik . '"><formula1>"Evet,Hayır,Devam ediyor"</formula1></dataValidation>';
                    }
                    // ısı matrisi: tek renk (beyaz → tema) — yalnız büyüklük
                    if ($matIlk !== null) $cf .= '<conditionalFormatting sqref="' . $harf($matIlk) . $ilk . ':' . $harf($matSon) . $son . '"><cfRule type="colorScale" priority="' . ($pri++) . '"><colorScale><cfvo type="min"/><cfvo type="max"/><color rgb="FFFFFFFF"/><color rgb="FF' . $tema . '"/></colorScale></cfRule></conditionalFormatting>';
                }
            }
            $sv = '<sheetViews><sheetView workbookViewId="0"' . ($pane ? '' : ' showGridLines="0"') . ($i === 0 ? ' tabSelected="1"' : '') . '>' . ($pane ? '<pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/>' : '') . '</sheetView></sheetViews>';
            $sekme = !empty($s['ai']) ? '9333EA' : $tema;
            $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheetPr><tabColor rgb="FF' . $sekme . '"/></sheetPr>'
                . $sv . $cols . '<sheetData>' . $rows . '</sheetData>' . $filtre
                . ($merge ? '<mergeCells count="' . count($merge) . '">' . implode('', array_map(fn ($m) => '<mergeCell ref="' . $m . '"/>', $merge)) . '</mergeCells>' : '')
                . $cf . ($dv ? '<dataValidations count="' . substr_count($dv, '<dataValidation ') . '">' . $dv . '</dataValidations>' : '')
                . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup paperSize="9" orientation="landscape"/>' . $cizim . '</worksheet>';
            $z->addFromString('xl/worksheets/sheet' . $n . '.xml', $sheet);
            if ($sheetRels) $z->addFromString('xl/worksheets/_rels/sheet' . $n . '.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $sheetRels . '</Relationships>');
            $wb .= '<sheet name="' . $x($ad) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sonN = $n;
        }
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0.00 &quot;₺&quot;"/><numFmt numFmtId="165" formatCode="0.0&quot;%&quot;"/><numFmt numFmtId="166" formatCode="#,##0.###"/><numFmt numFmtId="167" formatCode="#,##0 &quot;₺&quot;"/></numFmts>'
            . '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts><fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="' . count($borders) . '">' . implode('', $borders) . '</borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '<dxfs count="3"><dxf><font><b/><color rgb="FF9F1239"/></font><fill><patternFill><bgColor rgb="FFFFE4E6"/></patternFill></fill></dxf><dxf><font><b/><color rgb="FF92400E"/></font><fill><patternFill><bgColor rgb="FFFEF3C7"/></patternFill></fill></dxf><dxf><font><color rgb="FF475569"/></font><fill><patternFill><bgColor rgb="FFF1F5F9"/></patternFill></fill></dxf></dxfs>'
            . '</styleSheet>';
        $sonN = ($sonN ?? 0) + 1;
        $rels .= '<Relationship Id="rId' . $sonN . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $ct . '</Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wb . '</sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>');
        $z->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>');
        $z->addFromString('xl/styles.xml', $styles);
        $z->close();
        $icerik = file_get_contents($tmp); @unlink($tmp);
        return $icerik;
    }
}

if (!function_exists('_fr2AiBaglam')) {
    // AI'a giden özet veri (token tasarrufu için kırpılmış)
    function _fr2AiBaglam(array $v)
    {
        $al = fn ($a, $n, $keys) => array_map(fn ($x) => array_intersect_key($x, array_flip($keys)), array_slice($a, 0, $n));
        return [
            'donem_gun' => $v['filtre']['gun'], 'zam_esigi_yuzde' => $v['filtre']['esik'], 'bugun' => today()->toDateString(), 'kpi' => $v['kpi'],
            'gizli_zamlar' => $al($v['zamlar'], 15, ['ad', 'birim', 'ilk', 'ilk_tarih', 'ilk_ted', 'son', 'son_tarih', 'son_ted', 'yuzde', 'ayni_ted_yuzde', 'ted_degisti', 'en_dusuk', 'en_dusuk_ted', 'yillik_etki']),
            'malzeme_fiyatlari' => $al($v['malzemeler'], 25, ['ad', 'kategori', 'birim', 'alis_say', 'miktar', 'harcama', 'ort', 'min', 'min_ted', 'max', 'max_ted', 'son', 'son_ted', 'oynaklik', 'tasarruf']),
            'tedarikci_karsilastirma' => array_map(fn ($k) => ['ad' => $k['ad'], 'birim' => $k['birim'], 'son_ted' => $k['son_ted'], 'fark_yuzde' => $k['fark'], 'kayip' => $k['kayip'], 'yillik_kayip' => $k['yillik_kayip'],
                'tedarikciler' => array_map(fn ($t) => ['ted' => $t['ted'], 'ort' => $t['ort'], 'son' => $t['son'], 'miktar' => $t['miktar']], $k['tedler'])], array_slice($v['karsilastirma'], 0, 12)),
            'katalog_firsat' => array_slice($v['katalog'], 0, 8),
            'tedarikci_karnesi' => array_slice($v['karne'], 0, 12),
            'hal_kiyasi' => array_map(fn ($h) => ['ad' => $h['ad'], 'tedarikci' => $h['tedarikci'] ?? '', 'hal_ort' => $h['hal_ort'], 'hal_min' => $h['hal_min'], 'hal_max' => $h['hal_max'],
                'senin' => $h['uyumlu'] ? $h['fiyat_hal_birim'] : $h['son_fiyat'], 'birim_uyumlu' => $h['uyumlu'], 'fark' => $h['fark'], 'yillik_fazla' => $h['yillik']], array_slice($v['hal']['satirlar'] ?? [], 0, 15)),
            'aylik_harcama' => $v['aylik'], 'kategori' => array_slice($v['kategori'], 0, 10),
        ];
    }
}

if (!function_exists('_fr2AiCagir')) {
    function _fr2AiCagir($sys, $mesaj, $maxTok = 3500)
    {
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['hata' => 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
                ->timeout(90)->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001', 'max_tokens' => $maxTok, 'system' => $sys,
                    'messages' => [['role' => 'user', 'content' => $mesaj]],
                ]);
            if (!$resp->successful()) return ['hata' => 'AI servisi şu an cevap vermedi (' . $resp->status() . ').'];
            $txt = '';
            foreach ((array) $resp->json('content') as $b) if (($b['type'] ?? '') === 'text') $txt .= $b['text'];
            return ['metin' => trim($txt)];
        } catch (\Throwable $e) { return ['hata' => 'AI servisine bağlanılamadı.']; }
    }
}

if (!function_exists('_fr2AiParcali')) {
    // Tam analiz: 2 parça AYNI ANDA (Http::pool) + şemalı araç çağrısı (tool_use) → JSON her zaman geçerli.
    // Eski tek çağrı 3500 token sınırına dayanıp kesiliyordu (~34 sn + "çözümlenemedi").
    function _fr2AiParcali($sys, $mesaj)
    {
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['hata' => 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        $str = ['type' => 'string']; $sayi = ['type' => ['number', 'null']];
        $madde = fn ($ek = []) => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'object', 'properties' => ['baslik' => $str, 'detay' => $str] + $ek, 'required' => ['baslik', 'detay']]];
        // 4 parça aynı anda: her biri ~600-900 token → toplam süre en yavaş parçanın süresi (~8-10 sn)
        $parcalar = [
            'a' => ['tanim' => 'Genel değerlendirme ve kritik bulgular', 'sema' => [
                'ozet' => $str + ['description' => '3-4 cümle genel değerlendirme'],
                'skor' => ['type' => 'integer', 'description' => '0-100 satın alma disiplini puanı'],
                'kritik' => $madde(['tutar' => $sayi]) + ['description' => '3-4 en kritik bulgu'],
            ]],
            'b' => ['tanim' => 'Geçmiş hatalar', 'sema' => [
                'gecmis_hatalar' => $madde() + ['description' => '3-4 geçmiş hata: hangi malzeme, tedarikçi, tarih/fiyat, neden hata'],
            ]],
            'c' => ['tanim' => 'Tasarruf fırsatları ve gelecek önerileri', 'sema' => [
                'firsatlar' => $madde(['tutar' => $sayi]) + ['description' => '3-4 tasarruf fırsatı; tutar = verideki hazır yıllık rakam (yillik_etki / yillik_kayip / tasarruf / yillik_fazla), yoksa null'],
                'gelecek' => $madde() + ['description' => '3 öneri: önümüzdeki 1-3 ay sezon/trend/stoklama/sözleşme'],
            ]],
            'd' => ['tanim' => 'Pazarlık argümanları ve yapılacaklar listesi', 'sema' => [
                'pazarlik' => ['type' => 'array', 'maxItems' => 4, 'description' => 'en çok para giden tedarikçiler için somut rakamlı pazarlık argümanı',
                    'items' => ['type' => 'object', 'properties' => ['tedarikci' => $str, 'arguman' => $str], 'required' => ['tedarikci', 'arguman']]],
                'aksiyonlar' => ['type' => 'array', 'maxItems' => 5, 'description' => '4-5 öncelikli yapılacak iş',
                    'items' => ['type' => 'object', 'properties' => ['oncelik' => ['type' => 'string', 'enum' => ['yuksek', 'orta', 'dusuk']], 'is' => $str,
                        'sorumlu' => ['type' => 'string', 'enum' => ['Satın almacı', 'Müdür', 'Şef', 'Patron']]], 'required' => ['oncelik', 'is', 'sorumlu']]],
            ]],
        ];
        $govde = function ($p) use ($sys, $mesaj) {
            return ['model' => config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001', 'max_tokens' => 2500,
                'system' => $sys . ' Kısa yaz: her "detay"/"arguman" en fazla 2 cümle (~30 kelime), somut malzeme/tedarikçi adı ve rakamla.',
                'tools' => [['name' => 'analiz_kaydet', 'description' => $p['tanim'], 'input_schema' => ['type' => 'object', 'properties' => $p['sema'], 'required' => array_keys($p['sema'])]]],
                'tool_choice' => ['type' => 'tool', 'name' => 'analiz_kaydet'],
                'messages' => [['role' => 'user', 'content' => $mesaj . "\n\nSadece şu bölümleri üret: " . $p['tanim'] . '.']]];
        };
        try {
            $yanit = \Illuminate\Support\Facades\Http::pool(fn ($pool) => array_map(
                fn ($k) => $pool->as($k)->withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(60)->post('https://api.anthropic.com/v1/messages', $govde($parcalar[$k])),
                array_keys($parcalar)));
        } catch (\Throwable $e) { return ['hata' => 'AI servisine bağlanılamadı.']; }
        $sonuc = [];
        foreach (array_keys($parcalar) as $k) {
            $r = $yanit[$k] ?? null;
            if (!$r instanceof \Illuminate\Http\Client\Response || !$r->successful()) {
                \Illuminate\Support\Facades\Log::warning('fiyat radari ai ' . $k . ': ' . ($r instanceof \Throwable ? $r->getMessage() : ($r ? $r->status() . ' ' . mb_substr($r->body(), 0, 300) : 'yanıt yok')));
                return ['hata' => 'AI servisi şu an cevap vermedi' . ($r instanceof \Illuminate\Http\Client\Response ? ' (' . $r->status() . ')' : '') . '. Tekrar dene.'];
            }
            $girdi = null;
            foreach ((array) $r->json('content') as $b) if (($b['type'] ?? '') === 'tool_use') $girdi = $b['input'] ?? null;
            if (!is_array($girdi)) {
                \Illuminate\Support\Facades\Log::warning('fiyat radari ai ' . $k . ' tool_use yok, stop=' . $r->json('stop_reason'));
                return ['hata' => 'AI yanıtı eksik geldi, tekrar dene.'];
            }
            $sonuc += $girdi;
        }
        return ['analiz' => $sonuc];
    }
}

if (!function_exists('_fr2Erisim')) {
    function _fr2Erisim()
    {
        $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
        if (!$bp) return true; // panel oturumu (sahip) — ek kapı zaten /fiyat-radari önekini gor_satinalma ile süzer
        return function_exists('_satinalmaErisim') ? _satinalmaErisim($bp) : true;
    }
}

Route::get('/fiyat-radari', function (Request $r) {
    if (!_fr2Erisim()) return redirect('/pos-giris');
    $sid = _webSubeId(); $f = _fr2Filtre($r);
    $v = ['filtre' => $f, 'kpi' => []];
    try { $v = _fr2Veri($sid, $f); } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('fiyat radari: ' . $e->getMessage()); }
    $v['ai'] = cache()->get('fr2_ai_son_' . $sid);
    $v['raporlar'] = _fr2Raporlar();
    return view('fiyat_radari', $v);
});

if (!function_exists('_fr2IndirYanit')) {
    // Rapor dosyası (xlsx) ya da yazdırma görünümü (pdf) — web ve uygulamanın imzalı linki ortak kullanır
    function _fr2IndirYanit($sid, array $f, $rapor, $format)
    {
        $raporlar = _fr2Raporlar();
        if (!array_key_exists($rapor, $raporlar)) $rapor = 'tam';
        $v = _fr2Veri($sid, $f);
        // AI yalnız AI raporuna girer; diğer raporlar yorumsuz sistem verisi
        $ai = $rapor === 'ai' ? cache()->get('fr2_ai_son_' . $sid) : null;
        $sayfalar = _fr2Sayfalar($rapor, $v, $ai);
        $tema = ltrim($raporlar[$rapor][3], '#');
        $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
        $tedAd = $f['ted'] ? (DB::table('tedarikciler')->where('id', $f['ted'])->value('ad') ?: '') : '';
        $alt = $subeAd . ' · Son ' . $f['gun'] . ' gün (' . \Carbon\Carbon::parse($v['kpi']['baslangic'] ?? today())->format('d.m.Y') . ' – ' . today()->format('d.m.Y') . ')'
            . ($tedAd ? ' · Tedarikçi: ' . $tedAd : '') . ' · Oluşturma: ' . now()->format('d.m.Y H:i');
        $baslik = $raporlar[$rapor][0];
        $dosya = 'Fiyat-Radari-' . \Illuminate\Support\Str::slug($baslik) . '-' . today()->format('Y-m-d');
        if ($format === 'pdf') {
            return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => $v, 'ai' => $ai, 'rapor' => $rapor, 'dosya' => $dosya, 'tema' => $tema, 'ikon' => $raporlar[$rapor][2], 'aciklama' => $raporlar[$rapor][1]]);
        }
        $xlsx = _fr2Xlsx($sayfalar, $baslik, $alt, $tema);
        if ($xlsx !== null) return response($xlsx, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
        // ZipArchive yoksa: ilk sayfa CSV (Excel açar)
        $s = $sayfalar[0]; $out = "\xEF\xBB\xBF" . implode(';', array_column($s['kol'], 0)) . "\n";
        foreach ($s['sat'] as $sat) $out .= implode(';', array_map(fn ($c) => '"' . str_replace('"', '""', is_float($c) ? number_format($c, 2, ',', '') : (string) $c) . '"', $sat)) . "\n";
        return response($out, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.csv"']);
    }
}

if (!function_exists('_fr2AiIslem')) {
    // AI analiz (soru boşsa tam analiz JSON, doluysa serbest cevap). Dönüş: ['ok'=>1,'analiz'|'cevap'] ya da ['ok'=>0,'hata']
    function _fr2AiIslem($sid, array $f, $soru, $yenile)
    {
        $soru = trim(mb_substr((string) $soru, 0, 500));
        $gunKey = 'fr2_ai_gun_' . $sid . '_' . date('Y-m-d');
        if ((int) cache()->get($gunKey, 0) >= 40) return ['ok' => 0, 'hata' => 'Bugünkü AI analiz hakkı doldu (40). Yarın tekrar dene.'];
        $v = _fr2Veri($sid, $f);
        if (empty($v['kpi']['kalem'])) return ['ok' => 0, 'hata' => 'Bu dönemde alış faturası yok — analiz için önce alış girilmeli.'];
        $baglam = _fr2AiBaglam($v);
        $sys = 'Sen deneyimli bir restoran satın alma ve maliyet danışmanısın. Türkçe, net, rakamlarla konuşursun; uydurma rakam vermezsin, yalnız verilen veriyi kullanırsın. '
            . 'Para birimi TL. Hal fiyatı toptan fiyattır; tedarikçinin halin %10-20 üstü normaldir. "ayni_ted_yuzde" aynı tedarikçinin kendi zammıdır, "ted_degisti" tedarikçi değişikliğini gösterir. '
            . 'Birimi uyumsuz (birim_uyumlu=false) hal satırlarından fark çıkarma. '
            // Rakam tutarlılığı: AI kendi çarpma/toplama yapınca aynı kalem için farklı tutarlar çıkıyordu (ör. 878 ₺ / 1.756 ₺)
            . 'TUTARLAR: yıllık etki/kayıp/tasarruf için KENDİN HESAPLAMA; yalnız verideki hazır alanları aynen kullan (gizli_zamlar.yillik_etki, tedarikci_karsilastirma.kayip ve kpi.ted_kayip, malzeme_fiyatlari.tasarruf, hal_kiyasi.yillik_fazla, kpi değerleri). '
            . 'Aynı kalemi farklı bölümlerde anarsan aynı rakamı yaz. Veride karşılığı olmayan yüzde/tutar uydurma; "tutar" alanını o zaman boş (null) bırak. '
            . 'Bugün ' . today()->format('d.m.Y') . '. Bayram, sezon veya tarih iddiasında emin değilsen belirli tarih/ay verme, genel konuş.';
        if ($soru !== '') {
            $res = _fr2AiCagir($sys . ' Kullanıcının sorusunu veriye dayanarak kısa paragraflar ve gerekiyorsa madde işaretleriyle cevapla. Markdown başlık kullanma.',
                'Veri: ' . json_encode($baglam, JSON_UNESCAPED_UNICODE) . "\n\nSoru: " . $soru, 1500);
            if (!empty($res['hata'])) return ['ok' => 0, 'hata' => $res['hata']];
            cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
            // ekran düz metin gösterir: markdown başlık/kalın işaretlerini temizle
            $cevap = preg_replace(['/^#{1,6}\s*/m', '/\*\*(.+?)\*\*/s', '/^\s*[-*]\s+/m'], ['', '$1', '• '], $res['metin']);
            return ['ok' => 1, 'cevap' => trim($cevap)];
        }
        $fp = md5(json_encode($baglam));
        $onb = cache()->get('fr2_ai_' . $sid . '_' . $fp);
        if ($onb && !$yenile) return ['ok' => 1, 'analiz' => $onb, 'onbellek' => 1];
        $res = _fr2AiParcali($sys, 'Son ' . $f['gun'] . ' günün satın alma verisi: ' . json_encode($baglam, JSON_UNESCAPED_UNICODE));
        if (!empty($res['hata'])) return ['ok' => 0, 'hata' => $res['hata']];
        $j = $res['analiz'];
        $j['_zaman'] = now()->format('d.m.Y H:i'); $j['_donem'] = $f['gun'];
        cache()->put('fr2_ai_' . $sid . '_' . $fp, $j, now()->addHours(12));
        cache()->put('fr2_ai_son_' . $sid, $j, now()->addDays(30));
        cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
        return ['ok' => 1, 'analiz' => $j];
    }
}

Route::get('/fiyat-radari/indir', function (Request $r) {
    if (!_fr2Erisim()) return redirect('/pos-giris');
    return _fr2IndirYanit(_webSubeId(), _fr2Filtre($r), (string) $r->query('rapor'), (string) $r->query('format'));
});

Route::post('/fiyat-radari/ai', function (Request $r) {
    if (!_fr2Erisim()) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
    return response()->json(_fr2AiIslem(_webSubeId(), _fr2Filtre($r), $r->input('soru', ''), $r->boolean('yenile')));
});

// ===== UYGULAMA (Flutter telefon + masaüstü) — web v2 ile aynı veri =====
if (!function_exists('_fr2ApiPersonel')) {
    // null = giriş yok, false = satın alma yetkisi yok
    function _fr2ApiPersonel(Request $r)
    {
        $p = function_exists('_apiPersonel') ? _apiPersonel($r) : null;
        if (!$p) return null;
        if (function_exists('_satinalmaErisim') && !_satinalmaErisim($p)) return false;
        return $p;
    }
}

Route::get('/api/patron/fiyat-radari-v2', function (Request $r) {
    $p = _fr2ApiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => $p === false ? 'Satın alma yetkin yok.' : 'Yetkisiz'], $p === false ? 403 : 401);
    $f = _fr2Filtre($r);
    try { $v = _fr2Veri((int) $p->sube_id, $f); } catch (\Throwable $e) { return response()->json(['ok' => 0, 'hata' => 'Veri hazırlanamadı.']); }
    $v['dokum_toplam'] = count($v['dokum']);
    $v['dokum'] = array_slice($v['dokum'], 0, 300);
    $gec = []; foreach ($v['gecmis'] as $mid => $g) $gec[(string) $mid] = array_slice(array_reverse($g), 0, 30);
    $v['gecmis'] = (object) $gec;
    $v['ai'] = cache()->get('fr2_ai_son_' . $p->sube_id);
    $v['raporlar'] = array_map(fn ($k, $x) => ['anahtar' => $k, 'ad' => $x[0], 'aciklama' => $x[1], 'ikon' => $x[2], 'renk' => $x[3]], array_keys(_fr2Raporlar()), _fr2Raporlar());
    $v['ok'] = 1;
    return response()->json($v);
});

Route::post('/api/patron/fiyat-radari-v2/ai', function (Request $r) {
    $p = _fr2ApiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => $p === false ? 'Satın alma yetkin yok.' : 'Yetkisiz'], $p === false ? 403 : 401);
    return response()->json(_fr2AiIslem((int) $p->sube_id, _fr2Filtre($r), $r->input('soru', ''), $r->boolean('yenile')));
});

// Uygulama rapor indirme: harici tarayıcıda oturum yok → 15 dk geçerli imzalı link. İmza şube + filtreyi kilitler (değiştirilemez).
Route::get('/api/patron/fiyat-radari-v2/rapor-link', function (Request $r) {
    $p = _fr2ApiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => $p === false ? 'Satın alma yetkin yok.' : 'Yetkisiz'], $p === false ? 403 : 401);
    $f = _fr2Filtre($r);
    $rapor = array_key_exists($r->query('rapor'), _fr2Raporlar()) ? $r->query('rapor') : 'tam';
    $yol = \Illuminate\Support\Facades\URL::temporarySignedRoute('fr2.rapor', now()->addMinutes(15),
        ['s' => (int) $p->sube_id, 'rapor' => $rapor, 'format' => $r->query('format') === 'pdf' ? 'pdf' : 'xlsx', 'gun' => $f['gun'], 'ted' => $f['ted'], 'esik' => $f['esik']], false);
    return response()->json(['ok' => 1, 'yol' => $yol, 'url' => url($yol)]);
});
Route::get('/rapor-indir/fiyat-radari', function (Request $r) {
    return _fr2IndirYanit((int) $r->query('s'), _fr2Filtre($r), (string) $r->query('rapor'), (string) $r->query('format'));
})->name('fr2.rapor')->middleware('signed:relative');

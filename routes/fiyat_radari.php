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
    // Rapor kataloğu: anahtar → [başlık, açıklama, ikon, renk, sayfa(lar)]
    function _fr2Raporlar()
    {
        return [
            'tam' => ['Tam Fiyat Radarı Raporu', 'Tüm bölümler tek dosyada: özet, gizli zamlar, malzeme fiyatları, tedarikçi kıyası, karne, hal, döküm.', 'summarize', '#7C3AED'],
            'zam' => ['Gizli Zam Raporu', 'Fiyatı eşiğin üstünde artan malzemeler: ilk/son alış, hangi tedarikçiden, hangi fatura, yıllık etki.', 'trending_up', '#F43F5E'],
            'malzeme' => ['Malzeme Fiyat Analizi', 'Her malzeme için ortalama / en düşük / en yüksek / son fiyat ve kimden alındığı, oynaklık, tasarruf payı.', 'inventory_2', '#4F46E5'],
            'tedarikci' => ['Tedarikçi Karşılaştırma', 'Aynı malzemeyi farklı tedarikçilerden aldığın fiyatlar yan yana; en ucuzdan alsaydın kazancın.', 'compare_arrows', '#0EA5E9'],
            'karne' => ['Tedarikçi Karnesi', 'Tedarikçi bazında harcama, pay, zam yapan kalem sayısı, pahalı kaldığı malzemeler.', 'workspace_premium', '#F59E0B'],
            'hal' => ['Hal Kıyası Raporu', 'Son alış fiyatın vs İzmir hal bülteni (asgari/ortalama/azami) ve tedarikçin.', 'storefront', '#10B981'],
            'dokum' => ['Detaylı Alış Dökümü', 'Dönemdeki her alış satırı: tarih, tedarikçi, fatura no, miktar, birim fiyat, önceki alışa göre değişim.', 'receipt_long', '#64748B'],
            'aylik' => ['Aylık Harcama & Kategori', 'Ay ay toplam alış ve kategori dağılımı.', 'bar_chart', '#EC4899'],
            'ai' => ['AI Analiz Raporu', 'Son AI analizi: özet, geçmiş hatalar, fırsatlar, gelecek önerileri, pazarlık ve aksiyon listesi.', 'auto_awesome', '#9333EA'],
        ];
    }
}

if (!function_exists('_fr2Sayfalar')) {
    // Rapor → tablo sayfaları. Kolon tipi: m=metin, tl=para, y=yüzde, s=sayı, t=tarih
    function _fr2Sayfalar($rapor, array $v, $ai = null)
    {
        $tr = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('d.m.Y') : '';
        $S = [];
        $ozet = ['ad' => 'Özet', 'kol' => [['Gösterge', 'm', 38], ['Değer', 'm', 26]], 'sat' => [
            ['Dönem', $tr($v['kpi']['baslangic'] ?? null) . ' – ' . $tr($v['kpi']['bitis'] ?? null)],
            ['Toplam alış', number_format($v['kpi']['toplam'] ?? 0, 2, ',', '.') . ' ₺'],
            ['Fatura / kalem', ($v['kpi']['fatura'] ?? 0) . ' / ' . ($v['kpi']['kalem'] ?? 0)],
            ['Tedarikçi / malzeme', ($v['kpi']['tedarikci'] ?? 0) . ' / ' . ($v['kpi']['malzeme'] ?? 0)],
            ['Gizli zam (eşik %' . $v['filtre']['esik'] . ')', ($v['kpi']['zam_say'] ?? 0) . ' malzeme · yıllık ~' . number_format($v['kpi']['zam_etki'] ?? 0, 0, ',', '.') . ' ₺'],
            ['En ucuz tedarikçiden alsaydın (yıllık)', '~' . number_format($v['kpi']['ted_kayip'] ?? 0, 0, ',', '.') . ' ₺'],
            ['Hal üstü fazla ödeme (yıllık)', '~' . number_format($v['kpi']['hal_fazla'] ?? 0, 0, ',', '.') . ' ₺'],
        ]];
        $zam = ['ad' => 'Gizli Zamlar', 'not' => 'Kıyas: dönemdeki İLK alış → SON alış. "Aynı tedarikçide" = son tedarikçinin kendi ilk fiyatına göre zammı.',
            'kol' => [['Malzeme', 'm', 24], ['Birim', 'm', 7], ['İlk Alış Tarihi', 't', 12], ['İlk Tedarikçi', 'm', 22], ['İlk Fatura', 'm', 13], ['İlk Fiyat', 'tl', 12],
                ['Son Alış Tarihi', 't', 12], ['Son Tedarikçi', 'm', 22], ['Son Fatura', 'm', 13], ['Son Fiyat', 'tl', 12], ['Artış %', 'y', 9], ['Aynı Tedarikçide %', 'y', 11],
                ['Dönemin En Düşüğü', 'tl', 13], ['En Düşük Kimde', 'm', 22], ['Alış Sayısı', 's', 9], ['Yıllık Etki', 'tl', 13]],
            'sat' => array_map(fn ($z) => [$z['ad'], $z['birim'], $z['ilk_tarih'], $z['ilk_ted'], $z['ilk_fatura'], $z['ilk'], $z['son_tarih'], $z['son_ted'], $z['son_fatura'], $z['son'],
                $z['yuzde'], $z['ayni_ted_yuzde'], $z['en_dusuk'], $z['en_dusuk_ted'], $z['adet'], $z['yillik_etki']], $v['zamlar'])];
        $mlz = ['ad' => 'Malzeme Fiyatları', 'not' => 'Ortalama = miktar ağırlıklı (toplam tutar ÷ toplam miktar). Tasarruf payı = (ortalama − en düşük) × miktar.',
            'kol' => [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Alış', 's', 7], ['Miktar', 's', 10], ['Harcama', 'tl', 14], ['Ort. Fiyat', 'tl', 12],
                ['En Düşük', 'tl', 12], ['En Düşük Kimden', 'm', 20], ['En Yüksek', 'tl', 12], ['En Yüksek Kimden', 'm', 20], ['Son Fiyat', 'tl', 12], ['Son Kimden', 'm', 20],
                ['Son Tarih', 't', 11], ['Tedarikçi', 's', 9], ['Oynaklık %', 'y', 10], ['Tasarruf Payı', 'tl', 13]],
            'sat' => array_map(fn ($m) => [$m['ad'], $m['kategori'], $m['birim'], $m['alis_say'], $m['miktar'], $m['harcama'], $m['ort'], $m['min'], $m['min_ted'], $m['max'], $m['max_ted'],
                $m['son'], $m['son_ted'], $m['son_tarih'], $m['ted_say'], $m['oynaklik'], $m['tasarruf']], $v['malzemeler'])];
        $tsat = [];
        foreach ($v['karsilastirma'] as $k) foreach ($k['tedler'] as $i => $t)
            $tsat[] = [$k['ad'], $k['birim'], $t['ted'], $i === 0 ? 'EN UCUZ' : '+' . number_format($k['en_ucuz'] > 0 ? ($t['ort'] - $k['en_ucuz']) / $k['en_ucuz'] * 100 : 0, 1, ',', '.') . '%',
                $t['ort'], $t['son'], $t['son_tarih'], $t['miktar'], $t['harcama'], $t['alis_say'], $i === 0 ? $k['kayip'] : null];
        $ted = ['ad' => 'Tedarikçi Karşılaştırma', 'not' => 'Gerçek alış faturalarından. Kayıp = her tedarikçiden aldığın miktarı en ucuz tedarikçinin ortalamasından alsaydın ödeyeceğin farkın toplamı.',
            'kol' => [['Malzeme', 'm', 24], ['Birim', 'm', 7], ['Tedarikçi', 'm', 24], ['En Ucuza Göre', 'm', 13], ['Ort. Fiyat', 'tl', 12], ['Son Fiyat', 'tl', 12], ['Son Tarih', 't', 11],
                ['Miktar', 's', 10], ['Harcama', 'tl', 13], ['Alış', 's', 7], ['Malzeme Kaybı', 'tl', 13]], 'sat' => $tsat];
        $katS = ['ad' => 'Katalog Fırsatları', 'not' => 'Tedarikçi kartlarındaki güncel liste fiyatları (henüz alınmamış olabilir).',
            'kol' => [['Malzeme', 'm', 24], ['En Ucuz Tedarikçi', 'm', 22], ['En Ucuz', 'tl', 12], ['En Pahalı Tedarikçi', 'm', 22], ['En Pahalı', 'tl', 12], ['Ucuzluk %', 'y', 10], ['Tedarikçi Sayısı', 's', 10]],
            'sat' => array_map(fn ($k) => [$k['ad'], $k['en_ucuz_ted'], $k['en_ucuz'], $k['en_pahali_ted'], $k['en_pahali'], $k['tasarruf'], $k['ted_say']], $v['katalog'])];
        $karne = ['ad' => 'Tedarikçi Karnesi', 'not' => 'Zamlı kalem = aynı tedarikçide fiyatı eşiğin üstünde artan malzeme. Pahalı kaldığı = başka tedarikçiden %5+ ucuz alınabilen malzeme.',
            'kol' => [['Tedarikçi', 'm', 26], ['Harcama', 'tl', 14], ['Pay %', 'y', 8], ['Fatura', 's', 8], ['Kalem', 's', 8], ['Malzeme', 's', 9], ['Zamlı Kalem', 's', 10],
                ['Ort. Zam %', 'y', 10], ['Pahalı Kaldığı', 's', 11], ['Son Alış', 't', 11]],
            'sat' => array_map(fn ($k) => [$k['ted'], $k['harcama'], $k['pay'], $k['fatura'], $k['kalem'], $k['malzeme'], $k['zamli'], $k['ort_zam'], $k['pahali'], $k['son_alis']], $v['karne'])];
        $hal = ['ad' => 'Hal Kıyası', 'not' => 'İzmir Büyükşehir hal bülteni' . (!empty($v['hal']['bulten']) ? ' (' . $tr($v['hal']['bulten']) . ')' : '') . '. Hal toptan fiyattır; %10–20 üstü normal. Fark yalnız birimler aynıysa hesaplanır.',
            'kol' => [['Malzeme', 'm', 22], ['Son Tedarikçi', 'm', 22], ['Haldeki Karşılığı', 'm', 28], ['Hal Asgari', 'tl', 11], ['Hal Ortalama', 'tl', 12], ['Hal Azami', 'tl', 11],
                ['Hal Birimi', 'm', 8], ['Senin Son Alış', 'tl', 13], ['Senin Birimin', 'm', 9], ['Son Alış Tarihi', 't', 12], ['Fark %', 'y', 9], ['Yıllık Fazla', 'tl', 12]],
            'sat' => array_map(fn ($h) => [$h['ad'], $h['tedarikci'] ?? '', $h['hal_ad'], $h['hal_min'] ?: null, $h['hal_ort'], $h['hal_max'] ?: null, $h['hal_birim'],
                $h['uyumlu'] ? $h['fiyat_hal_birim'] : $h['son_fiyat'], $h['uyumlu'] ? $h['hal_birim'] : $h['birim'], $h['son_tarih'], $h['fark'], $h['yillik'] ?: null], $v['hal']['satirlar'] ?? [])];
        $dokum = ['ad' => 'Alış Dökümü', 'not' => 'Değişim = aynı malzemenin bir önceki alışına göre birim fiyat farkı.',
            'kol' => [['Tarih', 't', 11], ['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Tedarikçi', 'm', 24], ['Fatura No', 'm', 14], ['Miktar', 's', 10], ['Birim', 'm', 7],
                ['Birim Fiyat', 'tl', 12], ['Tutar', 'tl', 13], ['Önceki Alışa Göre %', 'y', 12]],
            'sat' => array_map(fn ($d) => [$d['tarih'], $d['malzeme'], $d['kategori'], $d['tedarikci'], $d['fatura_no'], $d['miktar'], $d['birim'], $d['birim_fiyat'], $d['tutar'], $d['degisim']], $v['dokum'])];
        $aylik = ['ad' => 'Aylık Harcama', 'kol' => [['Ay', 'm', 12], ['Toplam Alış', 'tl', 16]], 'sat' => array_map(fn ($a) => [$a['ay'], $a['tutar']], $v['aylik'])];
        $kategori = ['ad' => 'Kategori Dağılımı', 'kol' => [['Kategori', 'm', 24], ['Harcama', 'tl', 16], ['Pay %', 'y', 9]], 'sat' => array_map(fn ($k) => [$k['ad'], $k['tutar'], $k['pay']], $v['kategori'])];
        $aiS = [];
        if ($ai) {
            $aiS[] = ['ad' => 'AI Özet', 'kol' => [['Bölüm', 'm', 22], ['İçerik', 'm', 110]], 'sat' => [['Genel değerlendirme', $ai['ozet'] ?? ''], ['Satın alma skoru', (string) ($ai['skor'] ?? '')], ['Analiz tarihi', $ai['_zaman'] ?? '']]];
            $bol = ['kritik' => 'Kritik Bulgular', 'gecmis_hatalar' => 'Geçmiş Hatalar', 'firsatlar' => 'Tasarruf Fırsatları', 'gelecek' => 'Gelecek Önerileri', 'pazarlik' => 'Pazarlık Önerileri'];
            $sat = [];
            foreach ($bol as $k => $ad) foreach ((array) ($ai[$k] ?? []) as $x) $sat[] = [$ad, $x['baslik'] ?? ($x['tedarikci'] ?? ''), $x['detay'] ?? ($x['arguman'] ?? ''), isset($x['tutar']) && is_numeric($x['tutar']) ? (float) $x['tutar'] : null];
            $aiS[] = ['ad' => 'AI Bulgular', 'kol' => [['Bölüm', 'm', 18], ['Başlık', 'm', 34], ['Detay', 'm', 90], ['Tutar', 'tl', 13]], 'sat' => $sat];
            $aiS[] = ['ad' => 'AI Aksiyonlar', 'kol' => [['Öncelik', 'm', 10], ['Yapılacak', 'm', 90], ['Sorumlu', 'm', 18]],
                'sat' => array_map(fn ($a) => [mb_strtoupper((string) ($a['oncelik'] ?? ''), 'UTF-8'), $a['is'] ?? '', $a['sorumlu'] ?? ''], (array) ($ai['aksiyonlar'] ?? []))];
        }
        switch ($rapor) {
            case 'zam': return [$ozet, $zam];
            case 'malzeme': return [$mlz];
            case 'tedarikci': return [$ted, $katS];
            case 'karne': return [$karne];
            case 'hal': return [$hal];
            case 'dokum': return [$dokum];
            case 'aylik': return [$aylik, $kategori];
            case 'ai': return $aiS ?: [['ad' => 'AI Analiz', 'kol' => [['Bilgi', 'm', 80]], 'sat' => [['Henüz AI analizi yapılmadı. Fiyat Radarı > AI Analiz sekmesinden "Analiz Et" de.']]]];
            default: return array_merge([$ozet, $zam, $mlz, $ted, $katS, $karne, $hal, $aylik, $kategori, $dokum], $aiS);
        }
    }
}

if (!function_exists('_fr2Xlsx')) {
    // Basit ama gerçek .xlsx (ZipArchive): çok sayfa, başlık stili, ₺ ve % biçimi, sabit başlık satırı, filtre. ZipArchive yoksa null.
    function _fr2Xlsx(array $sayfalar, $baslik, $altBaslik)
    {
        if (!class_exists('ZipArchive')) return null;
        $x = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $harf = function ($n) { $s = ''; $n++; while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m) . $s; $n = intdiv($n - 1, 26); } return $s; };
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="3"><numFmt numFmtId="164" formatCode="#,##0.00 &quot;₺&quot;"/><numFmt numFmtId="165" formatCode="0.0&quot;%&quot;"/><numFmt numFmtId="166" formatCode="#,##0.###"/></numFmts>'
            . '<fonts count="4"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="15"/><color rgb="FF4C1D95"/><name val="Calibri"/></font><font><i/><sz val="9"/><color rgb="FF64748B"/><name val="Calibri"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF6D28D9"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF5F3FF"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><bottom style="thin"><color rgb="FFE4E7F2"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="8">'
            . '<xf/>'
            . '<xf fontId="1" fillId="2" applyFont="1" applyFill="1"><alignment vertical="center" wrapText="1"/></xf>'
            . '<xf fontId="2" applyFont="1"/>'
            . '<xf fontId="3" applyFont="1"/>'
            . '<xf numFmtId="164" borderId="1" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf numFmtId="165" borderId="1" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf numFmtId="166" borderId="1" applyNumberFormat="1" applyBorder="1"/>'
            . '<xf borderId="1" applyBorder="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs></styleSheet>';
        $tmp = tempnam(sys_get_temp_dir(), 'frx');
        $z = new \ZipArchive();
        if ($z->open($tmp, \ZipArchive::OVERWRITE) !== true) return null;
        $wb = ''; $rels = ''; $ct = ''; $kullanilan = [];
        foreach (array_values($sayfalar) as $i => $s) {
            $n = $i + 1;
            $ad = mb_substr(preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', $s['ad']), 0, 31);
            while (isset($kullanilan[$ad])) $ad = mb_substr($ad, 0, 28) . ' ' . $n;
            $kullanilan[$ad] = 1;
            $kolSay = count($s['kol']);
            $cols = '<cols>'; foreach ($s['kol'] as $c => $k) $cols .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . ($k[2] ?? 14) . '" customWidth="1"/>'; $cols .= '</cols>';
            $rows = '<row r="1" ht="22" customHeight="1"><c r="A1" t="inlineStr" s="2"><is><t>' . $x($baslik . ' — ' . $s['ad']) . '</t></is></c></row>';
            $rows .= '<row r="2"><c r="A2" t="inlineStr" s="3"><is><t>' . $x($altBaslik . (!empty($s['not']) ? ' · ' . $s['not'] : '')) . '</t></is></c></row>';
            $rows .= '<row r="4" ht="28" customHeight="1">';
            foreach ($s['kol'] as $c => $k) $rows .= '<c r="' . $harf($c) . '4" t="inlineStr" s="1"><is><t>' . $x($k[0]) . '</t></is></c>';
            $rows .= '</row>';
            $r = 5;
            foreach ($s['sat'] as $sat) {
                $rows .= '<row r="' . $r . '">';
                foreach ($s['kol'] as $c => $k) {
                    $val = $sat[$c] ?? null; $ref = $harf($c) . $r;
                    if ($val === null || $val === '') continue;
                    $tip = $k[1];
                    if (in_array($tip, ['tl', 'y', 's'], true) && is_numeric($val)) {
                        $rows .= '<c r="' . $ref . '" s="' . ($tip === 'tl' ? 4 : ($tip === 'y' ? 5 : 6)) . '"><v>' . (float) $val . '</v></c>';
                    } else {
                        if ($tip === 't' && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $val)) $val = \Carbon\Carbon::parse($val)->format('d.m.Y');
                        $rows .= '<c r="' . $ref . '" t="inlineStr" s="7"><is><t xml:space="preserve">' . $x($val) . '</t></is></c>';
                    }
                }
                $rows .= '</row>'; $r++;
            }
            $son = $harf($kolSay - 1) . max(4, $r - 1);
            $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                . $cols . '<sheetData>' . $rows . '</sheetData>' . (count($s['sat']) ? '<autoFilter ref="A4:' . $son . '"/>' : '') . '</worksheet>';
            $z->addFromString('xl/worksheets/sheet' . $n . '.xml', $sheet);
            $wb .= '<sheet name="' . $x($ad) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
            $rels .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sonN = $n;
        }
        $sonN = ($sonN ?? 0) + 1;
        $rels .= '<Relationship Id="rId' . $sonN . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        $z->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . $ct . '</Types>');
        $z->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $z->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $wb . '</sheets></workbook>');
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
            'tedarikci_karsilastirma' => array_map(fn ($k) => ['ad' => $k['ad'], 'birim' => $k['birim'], 'son_ted' => $k['son_ted'], 'fark_yuzde' => $k['fark'], 'kayip' => $k['kayip'],
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
        $ai = cache()->get('fr2_ai_son_' . $sid);
        $sayfalar = _fr2Sayfalar($rapor, $v, $ai);
        $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
        $tedAd = $f['ted'] ? (DB::table('tedarikciler')->where('id', $f['ted'])->value('ad') ?: '') : '';
        $alt = $subeAd . ' · Son ' . $f['gun'] . ' gün (' . \Carbon\Carbon::parse($v['kpi']['baslangic'] ?? today())->format('d.m.Y') . ' – ' . today()->format('d.m.Y') . ')'
            . ($tedAd ? ' · Tedarikçi: ' . $tedAd : '') . ' · Oluşturma: ' . now()->format('d.m.Y H:i');
        $baslik = $raporlar[$rapor][0];
        $dosya = 'Fiyat-Radari-' . \Illuminate\Support\Str::slug($baslik) . '-' . today()->format('Y-m-d');
        if ($format === 'pdf') {
            return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => $v, 'ai' => $rapor === 'ai' || $rapor === 'tam' ? $ai : null, 'rapor' => $rapor, 'dosya' => $dosya]);
        }
        $xlsx = _fr2Xlsx($sayfalar, $baslik, $alt);
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
            . 'Birimi uyumsuz (birim_uyumlu=false) hal satırlarından fark çıkarma.';
        if ($soru !== '') {
            $res = _fr2AiCagir($sys . ' Kullanıcının sorusunu veriye dayanarak kısa paragraflar ve gerekiyorsa madde işaretleriyle cevapla. Markdown başlık kullanma.',
                'Veri: ' . json_encode($baglam, JSON_UNESCAPED_UNICODE) . "\n\nSoru: " . $soru, 1500);
            if (!empty($res['hata'])) return ['ok' => 0, 'hata' => $res['hata']];
            cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
            return ['ok' => 1, 'cevap' => $res['metin']];
        }
        $fp = md5(json_encode($baglam));
        $onb = cache()->get('fr2_ai_' . $sid . '_' . $fp);
        if ($onb && !$yenile) return ['ok' => 1, 'analiz' => $onb, 'onbellek' => 1];
        $sema = '{"ozet":"3-5 cümle genel değerlendirme","skor":0-100 (satın alma disiplini puanı),'
            . '"kritik":[{"baslik":"","detay":"","tutar":sayı veya null}],'
            . '"gecmis_hatalar":[{"baslik":"","detay":"hangi malzeme, hangi tedarikçi, hangi tarih/fiyat — neden hata"}],'
            . '"firsatlar":[{"baslik":"","detay":"","tutar":tahmini yıllık tasarruf sayı veya null}],'
            . '"gelecek":[{"baslik":"","detay":"önümüzdeki 1-3 ay için öngörü/öneri: sezon, trend, stoklama, sözleşme"}],'
            . '"pazarlik":[{"tedarikci":"","arguman":"o tedarikçiyle konuşurken kullanılacak somut rakamlı argüman"}],'
            . '"aksiyonlar":[{"oncelik":"yuksek|orta|dusuk","is":"","sorumlu":"Satın almacı|Müdür|Şef|Patron"}]}';
        $res = _fr2AiCagir($sys . ' SADECE geçerli JSON döndür, başka metin yazma. Şema: ' . $sema . ' Her listede 3-7 madde; somut malzeme/tedarikçi adı ve rakam kullan.',
            'Son ' . $f['gun'] . ' günün satın alma verisi: ' . json_encode($baglam, JSON_UNESCAPED_UNICODE) . "\n\nBu veriyi analiz et: geçmiş hataları, tasarruf fırsatlarını, gelecek önerilerini, pazarlık argümanlarını ve aksiyon listesini çıkar.");
        if (!empty($res['hata'])) return ['ok' => 0, 'hata' => $res['hata']];
        $t = $res['metin']; $s = strpos($t, '{'); $e = strrpos($t, '}');
        $j = ($s !== false && $e > $s) ? json_decode(substr($t, $s, $e - $s + 1), true) : null;
        if (!is_array($j)) return ['ok' => 0, 'hata' => 'AI yanıtı çözümlenemedi, tekrar dene.'];
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

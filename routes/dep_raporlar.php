<?php
// =====================================================================
// DEPARTMAN RAPORLARI ÇATISI — satın alma / mutfak raporlarıyla aynı tasarım, her departman kendi verisiyle.
// Her departman routes/dep_raporlar/<kod>.php içinde _drKaydet('<kod>', [...]) ile modülünü tanımlar; bu dosya:
//   - ortak rotalar: {yol} → {yol}/donemsel, {yol}/indir, {yol}/{kat}, POST {yol}/ai
//   - erişim (sahip/müdür/süpervizör, departman SORUMLUSU, 'gor_<kod>_rapor' yetkisi)
//   - Excel/PDF (fiyat_radari_rapor + _fr2Xlsx), AI özet + soru-cevap (günlük 40 hak), menü kalemleri
// Rapor yardımcıları: _srKpi, _srBlok, _srSayfa, _srDonem, _srIstekDonem, _srYuzde, _srTablo, _srKolon (routes/satinalma_raporlar.php)
//
// Modül anahtarları:
//   ad, baslik, yol, grup (menü grubu), depKodlari (sorumlusu görür), ensure (callable), katalog/kategoriler/veri (callable),
//   bos (callable($neden, $dn) → boş rapor açıklaması), modAd, modLink, rapor (callable($anahtar, $sid, $dn) → sayfalar),
//   gorsel (callable($sid, $dn, $rapor) → html | ''), gorselRaporlar (liste), olcek ['kucuk' => [kat..], 'orta' => [kat..]],
//   ai: [gerekli, hero [başlık, metin], sorular, adimlar, selam, sys, baglam (callable), veriVar (callable($baglam) → bool), nabiz (callable($sid, $dn) → kartlar)]
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (!function_exists('_drKaydet')) {
    function _drModuller() { return $GLOBALS['_drModuller'] ?? []; }
    function _drModul($kod) { return $GLOBALS['_drModuller'][$kod] ?? null; }
    function _drKaydet($kod, array $m)
    {
        $GLOBALS['_drModuller'][$kod] = $m + ['kod' => $kod, 'depKodlari' => [$kod], 'modAd' => [], 'modLink' => [], 'olcek' => [], 'gorselRaporlar' => [], 'gorsel' => null, 'ensure' => null];
        _drRotalar($kod);
    }
    // Rapor sayfası görebilir mi: patron / müdür / süpervizör, ilgili departmanın SORUMLUSU ya da elle verilen 'gor_<kod>_rapor'
    function _drErisim($kod, $p = null)
    {
        $m = _drModul($kod); if (!$m) return false;
        if (!$p) { try { $p = _posPersonel(); } catch (\Throwable $e) {} }
        if (!$p) return false;
        $kad = $p->kademe ?? (function_exists('_rolKademe') ? _rolKademe($p->rol ?? '') : '');
        if (in_array($p->rol ?? '', ['sahip', 'mudur'], true) || in_array($kad, ['sahip', 'supervizor'], true)) return true;
        if (function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_' . $kod . '_rapor')) return true;
        if (!empty($p->departman_sorumlu) && !empty($p->departman_id)) {
            try { $dk = DB::table('departmanlar')->where('id', (int) $p->departman_id)->value('kod'); if (in_array($dk, $m['depKodlari'], true)) return true; } catch (\Throwable $e) {}
        }
        return !empty($m['erisim']) && (bool) ($m['erisim'])($p);
    }
    // Menü kalemi bir departman raporuna mı ait (yol öneki) → o modülün erişimi; değilse null
    function _drYolErisim($url, $p = null)
    {
        foreach (_drModuller() as $kod => $m) if (str_starts_with($url, $m['yol'] . '/')) return _drErisim($kod, $p);
        return null;
    }
    // Kişinin görebileceği kategoriler: modül 'katIzin' (callable($p) → null = hepsi | [kat..]) ile daraltabilir (örn. İK: departman sorumlusu maaş/bordro görmez)
    function _drKatlar($kod, $p = null)
    {
        $m = _drModul($kod); $k = ($m['kategoriler'])();
        if (empty($m['katIzin'])) return $k;
        if (!$p) { try { $p = _posPersonel(); } catch (\Throwable $e) {} }
        $izin = $p ? ($m['katIzin'])($p) : null;
        return $izin === null ? $k : array_intersect_key($k, array_flip($izin));
    }
    // Menüye eklenecek kalemler: kişinin görebildiği her kategori bir link (son kalem AI)
    function _drMenu($kod)
    {
        $m = _drModul($kod); $o = [];
        foreach (_drKatlar($kod) as $k => [$ad, $ik, $acik, $renk]) $o[] = [$m['yol'] . '/' . $k, $ik, $k === 'ai' ? $m['ad'] . ' AI Analiz' : $ad, $renk];
        return $o;
    }
    // İşletme ölçeği (küçük/orta) menüsünde görünecek rapor linkleri: grup => [url..]
    function _drOlcekMenu($olcek)
    {
        $o = [];
        foreach (_drModuller() as $m) {
            $katlar = $m['olcek']['kucuk'] ?? ['donemsel', 'ai'];
            if ($olcek === 'orta') $katlar = array_merge($katlar, $m['olcek']['orta'] ?? []);
            foreach ($katlar as $k) $o[$m['grup']][] = $m['yol'] . '/' . $k;
        }
        return $o;
    }
    function _drVeri($kod, $sid) { $m = _drModul($kod); try { return ($m['veri'])($sid); } catch (\Throwable $e) { return []; } }
}

// ---------- AI: dönem özeti + soru-cevap (departman başına günlük 40 hak, aynı veri 12 saat önbellek) ----------
if (!function_exists('_drAiOzet')) {
    function _drAiHakAnahtar($kod, $sid) { return 'dr_ai_gun_' . $kod . '_' . $sid . '_' . date('Y-m-d'); }
    function _drAiKalan($kod, $sid) { return max(0, 40 - (int) cache()->get(_drAiHakAnahtar($kod, $sid), 0)); }
    function _drAiOzet($kod, $sid, array $dn, $soru = '', $yenile = false)
    {
        $m = _drModul($kod); $ai = $m['ai']; $gunKey = _drAiHakAnahtar($kod, $sid);
        if ((int) cache()->get($gunKey, 0) >= 40) return ['ok' => 0, 'hata' => 'Bugünkü ' . $m['ad'] . ' AI hakkı doldu (40). Yarın tekrar dene.'];
        $b = ($ai['baglam'])($sid, $dn);
        if (!($ai['veriVar'])($b)) return ['ok' => 0, 'hata' => 'Bu dönemde analiz edilecek ' . $m['ad'] . ' verisi yok. Rapor sayfalarındaki "Kullanılan veri" çiplerinden neyin eksik olduğunu görebilirsin.'];
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['ok' => 0, 'hata' => 'AI anahtarı tanımlı değil.'];
        $sys = $ai['sys'] . ' Türkçe, kısa ve rakamlı yaz; yalnız verilen veriyi kullan, rakam uydurma, kendin toplama/çarpma yapma. Para TL; sayıları Türkçe biçimde yaz (1.452,00 TL, %12,5). '
            . '"kullanilan_moduller" içinde 0 olan modülden sonuç çıkarma, "veri yok" de. Bugün ' . today()->format('d.m.Y') . '.';
        if (trim((string) $soru) !== '') {
            $r = function_exists('_fr2AiCagir') ? _fr2AiCagir($sys . ' Soruyu veriye dayanarak kısa paragraflar ve madde işaretleriyle cevapla. Markdown başlık kullanma.', 'Veri: ' . json_encode($b, JSON_UNESCAPED_UNICODE) . "\n\nSoru: " . mb_substr((string) $soru, 0, 500), 1200) : ['hata' => 'AI servisi yok.'];
            if (!empty($r['hata'])) return ['ok' => 0, 'hata' => $r['hata']];
            cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
            return ['ok' => 1, 'cevap' => trim(preg_replace(['/^#{1,6}\s*/m', '/\*\*(.+?)\*\*/s', '/^\s*[-*]\s+/m'], ['', '$1', '• '], $r['metin']))];
        }
        $fp = 'dr_ai_' . $kod . '_' . $sid . '_' . md5(json_encode($b));
        if (!$yenile && ($c = cache()->get($fp))) return ['ok' => 1, 'analiz' => $c, 'onbellek' => 1];
        $str = ['type' => 'string']; $liste = fn ($desc) => ['type' => 'array', 'maxItems' => 5, 'description' => $desc, 'items' => ['type' => 'object', 'properties' => ['baslik' => $str, 'detay' => $str], 'required' => ['baslik', 'detay']]];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001', 'max_tokens' => 2000,
                'system' => $sys . ' Her detay en fazla 2 cümle, somut ad (kişi/ürün/kanal vb.) ve verideki rakamla.',
                'tools' => [['name' => 'departman_ozeti', 'description' => $m['ad'] . ' dönem yönetici özeti', 'input_schema' => ['type' => 'object', 'required' => ['ozet', 'one_cikanlar', 'riskler', 'oneriler'], 'properties' => [
                    'ozet' => $str + ['description' => '3-4 cümle dönem özeti; önceki döneme göre en önemli gelişme'],
                    'one_cikanlar' => $liste('3-5 öne çıkan gelişme'), 'riskler' => $liste('2-4 risk / dikkat edilecek konu'), 'oneriler' => $liste('2-4 somut öneri / yapılacak iş')]]]],
                'tool_choice' => ['type' => 'tool', 'name' => 'departman_ozeti'],
                'messages' => [['role' => 'user', 'content' => $m['ad'] . ' dönem verisi: ' . json_encode($b, JSON_UNESCAPED_UNICODE)]]]);
            if (!$resp->successful()) return ['ok' => 0, 'hata' => 'AI servisi şu an cevap vermedi (' . $resp->status() . ').'];
            $j = null; foreach ((array) $resp->json('content') as $c) if (($c['type'] ?? '') === 'tool_use') $j = $c['input'] ?? null;
            if (!is_array($j)) return ['ok' => 0, 'hata' => 'AI yanıtı eksik geldi, tekrar dene.'];
        } catch (\Throwable $e) { return ['ok' => 0, 'hata' => 'AI servisine bağlanılamadı.']; }
        $j['_zaman'] = now()->format('d.m.Y H:i'); $j['_donem'] = $dn['etiket'];
        cache()->put($fp, $j, now()->addHours(12)); cache()->put('dr_ai_son_' . $kod . '_' . $sid, $j, now()->addDays(30));
        cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
        return ['ok' => 1, 'analiz' => $j];
    }
    // AI özeti Excel/PDF: AI sayfası + dayandığı rakamlar (aylık/haftalık dönemsel raporun sayfaları)
    function _drAiSayfalar($kod, $sid, array $dn, array $ai)
    {
        $m = _drModul($kod);
        $uyari = 'Bu sayfa YAPAY ZEKÂ tarafından hazırlanmıştır (' . ($ai['_zaman'] ?? '') . ', dönem ' . ($ai['_donem'] ?? $dn['etiket']) . '). Öneriler karar desteğidir; son karar yöneticinindir. Rakamlar sonraki "Dayanak" sayfalarında doğrulanabilir.';
        $sat = [['Dönem özeti', '', $ai['ozet'] ?? '']];
        foreach (['one_cikanlar' => 'Öne çıkan', 'riskler' => 'Risk', 'oneriler' => 'Öneri'] as $k => $ad) foreach ((array) ($ai[$k] ?? []) as $x) $sat[] = [$ad, $x['baslik'] ?? '', $x['detay'] ?? ''];
        $s = [['ad' => 'AI ' . $m['ad'] . ' Özeti', 'ai' => 1, 'not' => $uyari, 'kol' => [['Bölüm', 'm', 16], ['Başlık', 'm', 34], ['AI Yorumu', 'm', 90]], 'sat' => $sat]];
        $kat = ($m['katalog'])(); $dayanak = $dn['gun'] > 20 ? 'aylik' : 'haftalik'; if (!isset($kat[$dayanak])) $dayanak = array_key_first($kat);
        foreach (($m['rapor'])($dayanak, $sid, $dn) as $i => $x) { if ($i === 0) { $x['ad'] = 'Dayanak - Pano'; $s = array_merge([$x], $s); continue; } $x['ad'] = mb_substr('Dayanak - ' . $x['ad'], 0, 31); $s[] = $x; }
        return $s;
    }
    function _drAiNabiz($kod, $sid, array $dn)
    {
        $m = _drModul($kod); $kalan = _drAiKalan($kod, $sid);
        try { return ['kalan' => $kalan, 'kartlar' => ($m['ai']['nabiz'])($sid, $dn)]; } catch (\Throwable $e) { return ['kalan' => $kalan, 'kartlar' => null]; }
    }
}

// ---------- Excel / PDF / önizleme ----------
if (!function_exists('_drIndir')) {
    function _drIndir($kod, $sid, $rapor, array $dn, $format, $onizle = false)
    {
        $m = _drModul($kod); $kat = ($m['katalog'])();
        if ($rapor === 'ai') {
            $ai = cache()->get('dr_ai_son_' . $kod . '_' . $sid);
            if (!$ai) return response('Önce ' . $m['baslik'] . ' > AI Analiz bölümünden "Özet Hazırla" deyin.', 404);
            $sayfalar = _drAiSayfalar($kod, $sid, $dn, $ai); $baslik = 'AI ' . $m['ad'] . ' Özeti'; $tema = '9333EA'; $ikon = 'auto_awesome'; $acik = 'Yapay zekânın seçili dönem için özeti, riskleri ve önerileri + dayandığı rakamlar.';
        } else {
            if (!isset($kat[$rapor])) $rapor = array_key_first($kat);
            $sayfalar = ($m['rapor'])($rapor, $sid, $dn); [$_k, $baslik, $acik, $ikon, $renk] = $kat[$rapor]; $tema = ltrim($renk, '#');
        }
        $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
        $alt = $subeAd . ' · ' . $dn['ad'] . ' (' . $dn['etiket'] . ') · Oluşturma: ' . now()->format('d.m.Y H:i');
        $dosya = \Illuminate\Support\Str::slug($baslik) . '-' . $dn['bas'] . ($dn['bit'] !== $dn['bas'] ? '_' . $dn['bit'] : '');
        if ($format === 'pdf' || $format === 'html') {
            $gorsel = '';
            if ($m['gorsel'] && in_array($rapor, $m['gorselRaporlar'], true)) { try { $gorsel = (string) ($m['gorsel'])($sid, $dn, $rapor); } catch (\Throwable $e) { $gorsel = ''; } }
            return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => [], 'ai' => null, 'rapor' => $rapor, 'dosya' => $dosya,
                'tema' => $tema, 'ikon' => $ikon, 'aciklama' => $acik, 'marka' => $m['baslik'], 'onizle' => $onizle || $format === 'html', 'aiEtiket' => $rapor === 'ai', 'gorsel' => $gorsel]);
        }
        $x = _fr2Xlsx($sayfalar, $baslik, $alt, $tema);
        if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
        return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
    }
}

// ---------- Rotalar (modül kaydedilince) ----------
if (!function_exists('_drRotalar')) {
    function _drRotalar($kod)
    {
        $m = _drModul($kod); $yol = $m['yol'];
        $ens = function () use ($m) { if ($m['ensure']) { try { ($m['ensure'])(); } catch (\Throwable $e) {} } };
        Route::get($yol, function (Request $r) use ($yol, $kod) { $qs = $r->getQueryString(); return redirect($yol . '/' . (array_key_first(_drKatlar($kod)) ?: 'donemsel') . ($qs ? '?' . $qs : '')); });
        Route::get($yol . '/indir', function (Request $r) use ($kod, $m, $ens) {
            if (!_drErisim($kod)) return redirect('/pos-giris');
            $ens(); $kat = ($m['katalog'])(); $rapor = (string) $r->query('rapor', 'aylik');
            $izinli = _drKatlar($kod); if (!isset($izinli[$rapor === 'ai' ? 'ai' : ($kat[$rapor][0] ?? '')])) return response('Bu raporu görme yetkin yok.', 403);
            return _drIndir($kod, _webSubeId(), $rapor, _srIstekDonem($r, $kat[$rapor][6] ?? 'bu_ay'), (string) $r->query('format', 'xlsx'), $r->boolean('onizle'));
        });
        Route::get($yol . '/{kat}', function (Request $r, $kat) use ($kod, $m, $ens) {
            if (!_drErisim($kod)) return redirect('/pos-giris');
            if (!isset(($m['kategoriler'])()[$kat])) abort(404);
            $kats = _drKatlar($kod); if (!isset($kats[$kat])) return $kats ? redirect($m['yol'] . '/' . array_key_first($kats)) : redirect('/pos-giris');
            $ens(); $sid = _webSubeId(); $dn = _srIstekDonem($r);
            $ust = ''; if (!empty($m['ust'])) { try { $ust = (string) ($m['ust'])($kat, $sid, $dn); } catch (\Throwable $e) {} }
            return view('dep_raporlar', ['M' => $m, 'kat' => $kat, 'dn' => $dn, 'durum' => _drVeri($kod, $sid), 'katalog' => ($m['katalog'])(), 'kategoriler' => $kats, 'ustHtml' => $ust,
                'ai' => cache()->get('dr_ai_son_' . $kod . '_' . $sid), 'aiNabiz' => $kat === 'ai' ? _drAiNabiz($kod, $sid, $dn) : null]);
        })->where('kat', '[a-z_]+');
        Route::post($yol . '/ai', function (Request $r) use ($kod, $ens) {
            if (!_drErisim($kod) || !isset(_drKatlar($kod)['ai'])) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
            $ens();
            return response()->json(_drAiOzet($kod, _webSubeId(), _srIstekDonem($r), (string) $r->input('soru', ''), $r->boolean('yenile')));
        });
    }
}

// Departman modülleri: routes/dep_raporlar/*.php (her biri _drKaydet çağırır)
foreach (glob(__DIR__ . '/dep_raporlar/*.php') ?: [] as $_drDosya) require $_drDosya;
unset($_drDosya);

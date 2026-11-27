<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ============================================================================
// ÜRÜN BAZLI KDV
// Oran sırası: ürün kdv > kategori kdv > şube varsayılanı (muhasebe_ayarlari.kdv_orani, yoksa %10).
// İşletme oranları istediği zaman değiştirir (yasa değişirse panelden güncellenir).
// Satış KDV'si: dönemdeki/adisyondaki kalemlerin oran payları ile hesaplanır (indirim/servis oransal dağılır).
// ============================================================================
if (!function_exists('_kdvEnsure')) {
    function _kdvEnsure()
    {
        if (cache()->get('kdv_kolon_ok')) return;
        try {
            if (Schema::hasTable('urunler') && !Schema::hasColumn('urunler', 'kdv')) Schema::table('urunler', fn ($t) => $t->decimal('kdv', 5, 2)->nullable());
            if (Schema::hasTable('menu_kategorileri') && !Schema::hasColumn('menu_kategorileri', 'kdv')) Schema::table('menu_kategorileri', fn ($t) => $t->decimal('kdv', 5, 2)->nullable());
            cache()->put('kdv_kolon_ok', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }

    // Şubenin varsayılan satış KDV'si (ürün/kategoride oran yoksa)
    function _kdvVarsayilan($sid)
    {
        static $c = [];
        $sid = (int) $sid;
        if (isset($c[$sid])) return $c[$sid];
        $o = 10.0;
        try { if (Schema::hasTable('muhasebe_ayarlari')) { $x = DB::table('muhasebe_ayarlari')->where('sube_id', $sid)->value('kdv_orani'); if ($x !== null && $x !== '') $o = (float) $x; } } catch (\Throwable $e) {}
        return $c[$sid] = $o;
    }

    // Bir ürünün geçerli KDV oranı
    function _urunKdv($urun)
    {
        _kdvEnsure();
        if (!$urun) return 10.0;
        if (isset($urun->kdv) && $urun->kdv !== null) return (float) $urun->kdv;
        if (!empty($urun->kategori_id)) {
            $k = DB::table('menu_kategorileri')->where('id', $urun->kategori_id)->value('kdv');
            if ($k !== null) return (float) $k;
        }
        return _kdvVarsayilan($urun->sube_id ?? 0);
    }

    // Satış kalemlerinin oran payları: [oran => pay(0..1)]. Adisyon ya da şube+dönem için.
    function _kdvPaylari($subeIds = null, $from = null, $to = null, $adisyonId = null)
    {
        _kdvEnsure();
        $subeIds = array_values(array_filter(array_map('intval', (array) $subeIds)));
        $vars = $subeIds ? _kdvVarsayilan($subeIds[0]) : 10.0;
        try {
            $q = DB::table('adisyon_kalemleri as ak')
                ->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')
                ->leftJoin('urunler as u', 'ak.urun_id', '=', 'u.id')
                ->leftJoin('menu_kategorileri as k', 'u.kategori_id', '=', 'k.id')
                ->where('ak.durum', '!=', 'iptal');
            if ($adisyonId) {
                $q->where('ak.adisyon_id', (int) $adisyonId);
                $sid = (int) DB::table('adisyonlar')->where('id', (int) $adisyonId)->value('sube_id');
                if ($sid) $vars = _kdvVarsayilan($sid);
            } else {
                if ($subeIds) $q->whereIn('a.sube_id', $subeIds);
                if ($from && $to) $q->whereBetween('a.kapanis', [$from, $to]);
            }
            $satir = $q->selectRaw('COALESCE(u.kdv, k.kdv) as oran, SUM(ak.tutar) as t')->groupBy(DB::raw('COALESCE(u.kdv, k.kdv)'))->get();
            $top = []; $genel = 0.0;
            foreach ($satir as $s) {
                $t = (float) $s->t;
                if ($t <= 0) continue;
                $o = $s->oran === null ? $vars : (float) $s->oran;
                $anahtar = rtrim(rtrim(number_format($o, 2, '.', ''), '0'), '.');
                $top[$anahtar] = ($top[$anahtar] ?? 0) + $t;
                $genel += $t;
            }
            if ($genel <= 0) return [(string) $vars => 1.0];
            foreach ($top as $o => $t) $top[$o] = $t / $genel;
            ksort($top, SORT_NUMERIC);
            return $top;
        } catch (\Throwable $e) {
            return [(string) $vars => 1.0];
        }
    }

    // Brüt tutarı oran paylarına göre matrah + KDV'ye böler: ['matrah', 'kdv', 'dagilim' => [oran => ['matrah', 'kdv']]]
    function _kdvBol($brut, array $paylar)
    {
        $brut = (float) $brut;
        $d = []; $mT = 0.0; $kT = 0.0;
        foreach ($paylar as $o => $pay) {
            $b = $brut * $pay;
            $m = round($b / (1 + ((float) $o) / 100), 2);
            $k = round($b - $m, 2);
            $d[$o] = ['matrah' => $m, 'kdv' => $k];
            $mT += $m; $kT += $k;
        }
        return ['matrah' => round($mT, 2), 'kdv' => round($kT, 2), 'dagilim' => $d];
    }

    // Tek oran gereken eski hesaplar için ağırlıklı etkin oran (ör. 12.4)
    function _kdvEtkinOran(array $paylar)
    {
        $f = 0.0;
        foreach ($paylar as $o => $pay) $f += $pay / (1 + ((float) $o) / 100);
        return $f > 0 ? round((1 / $f - 1) * 100, 2) : 10.0;
    }
}

// ============================================================================
// HAZIR MENÜ KÜTÜPHANESİ
// Veri: resources/menu_kutuphane/urunler.tsv  (kod|ad|kategori|paketler|istasyon|kdv|alerjen|açıklama|görsel tarifi)
// Görsel: public/menu-kutuphane/<kod>.jpg (+ <kod>-2.jpg alternatifleri), 600px. Görseli olmayan ürün emoji ile çalışır.
// Eklenen ürün manuel ürünle aynı satırdır (urun-kaydet ile aynı alanlar); farkı: sablon_kod + fiyat_bekliyor.
// Fiyat TAHMİN EDİLMEZ: ürün aktif=0, fiyat_bekliyor=1 gelir → POS/QR/uygulama/WA/AI/offline'da görünmez;
// fiyat girilince kendiliğinden satışa açılır.
// ============================================================================
if (!function_exists('_menuKutuphaneKolonEnsure')) {
    function _menuKutuphaneKolonEnsure()
    {
        _kdvEnsure();
        if (cache()->get('menu_kutuphane_kolon_ok')) return;
        try {
            if (!Schema::hasColumn('urunler', 'istasyon')) Schema::table('urunler', fn ($t) => $t->string('istasyon', 20)->default('mutfak'));
            if (!Schema::hasColumn('urunler', 'sablon_kod')) Schema::table('urunler', fn ($t) => $t->string('sablon_kod', 60)->nullable()->index());
            if (!Schema::hasColumn('urunler', 'fiyat_bekliyor')) Schema::table('urunler', fn ($t) => $t->boolean('fiyat_bekliyor')->default(0));
            if (!Schema::hasColumn('menu_kategorileri', 'sablon')) Schema::table('menu_kategorileri', fn ($t) => $t->boolean('sablon')->default(0));
            if (!Schema::hasColumn('subeler', 'isletme_turu')) Schema::table('subeler', fn ($t) => $t->string('isletme_turu', 20)->nullable());
            if (!Schema::hasColumn('subeler', 'menu_paketleri')) Schema::table('subeler', fn ($t) => $t->string('menu_paketleri', 500)->nullable());
            if (!Schema::hasColumn('subeler', 'menu_kurulum')) Schema::table('subeler', fn ($t) => $t->tinyInteger('menu_kurulum')->default(0)); // 0 sorulmadı, 1 kuruldu, 2 atlandı
            if (function_exists('_kaliteAlerjenKolon')) _kaliteAlerjenKolon();
            cache()->put('menu_kutuphane_kolon_ok', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }

    function _mkKategoriler()
    {
        return ['Çorbalar', 'Kahvaltı', 'Mezeler', 'Ara Sıcaklar', 'Salatalar', 'Zeytinyağlılar', 'Kebap & Izgara', 'Döner & Dürüm', 'Pide & Lahmacun', 'Ana Yemekler',
            'Balık & Deniz Ürünleri', 'Pizza', 'Makarna & Risotto', 'Burger', 'Sandviç & Wrap', 'Atıştırmalıklar', 'Meksika', 'Uzak Doğu', 'Suşi', 'Kore', 'Tayland', 'Hint',
            'Akdeniz', 'Fransız', 'Steak', 'Bowl & Vegan', 'Çocuk Menüsü', 'Tatlılar', 'Pastane', 'Kahveler', 'Soğuk Kahveler', 'Çaylar', 'Sıcak İçecekler', 'Soğuk İçecekler',
            'Smoothie & Shake', 'Kokteyller', 'Biralar', 'Şaraplar', 'Rakı', 'Viski & Diğer'];
    }

    // Seçilebilir paketler (mutfaklar + bölümler)
    function _mkPaketler()
    {
        return [
            'Türk Mutfağı' => ['lokanta' => ['Lokanta / Ev Yemekleri', '🍲'], 'kebap' => ['Kebap & Ocakbaşı', '🔥'], 'pide' => ['Pide & Lahmacun', '🫓'], 'durum' => ['Döner & Dürüm', '🌯'],
                'karadeniz' => ['Karadeniz', '🌊'], 'ege' => ['Ege & Zeytinyağlı', '🫒'], 'guneydogu' => ['Güneydoğu', '🌶️'], 'balik' => ['Balık & Deniz Ürünleri', '🐟'], 'meyhane' => ['Meyhane & Meze', '🥂']],
            'Dünya Mutfakları' => ['italyan' => ['İtalyan', '🍕'], 'amerikan' => ['Amerikan / Burger', '🍔'], 'meksika' => ['Meksika', '🌮'], 'uzakdogu' => ['Uzak Doğu / Çin', '🥢'],
                'japon' => ['Japon / Suşi', '🍣'], 'kore' => ['Kore', '🍱'], 'tayland' => ['Tayland', '🍜'], 'hint' => ['Hint', '🍛'], 'akdeniz' => ['Akdeniz / Yunan', '🥙'],
                'fransiz' => ['Fransız / Bistro', '🥐'], 'steak' => ['Steakhouse', '🥩'], 'vegan' => ['Vegan & Bowl', '🥗']],
            'Bölümler' => ['kahvalti' => ['Kahvaltı', '🍳'], 'tatli' => ['Tatlılar', '🍰'], 'pastane' => ['Pastane', '🧁'], 'kafe' => ['Kafe Yiyecekleri', '🥪'], 'kahve' => ['Kahveler', '☕'],
                'sicak' => ['Çay & Sıcak İçecek', '🫖'], 'soguk' => ['Soğuk İçecekler', '🥤'], 'alkol' => ['Bar / Alkollü', '🍸'], 'atistirmalik' => ['Bar Atıştırmalık', '🥜'], 'cocuk' => ['Çocuk Menüsü', '🧒']],
        ];
    }

    // Bir mutfağa/bölüme ait kategoriler: ürün başka pakete de etiketli olsa (ör. Mango Lassi = soğuk içecek), bu paketlerden
    // biri seçilmedikçe işaretli gelmez; önizlemede ayrı "öneriler" bölümünde işaretsiz durur. Alkol ruhsatsız işletmeye işaretli gelmez.
    function _mkKatSahibi()
    {
        $alkol = ['alkol', 'meyhane'];
        return ['Pizza' => ['italyan', 'amerikan'], 'Makarna & Risotto' => ['italyan'], 'Burger' => ['amerikan'], 'Meksika' => ['meksika'],
            'Uzak Doğu' => ['uzakdogu', 'japon'], 'Suşi' => ['japon'], 'Kore' => ['kore'], 'Tayland' => ['tayland'], 'Hint' => ['hint'], 'Akdeniz' => ['akdeniz'],
            'Fransız' => ['fransiz'], 'Steak' => ['steak'], 'Bowl & Vegan' => ['vegan'],
            'Smoothie & Shake' => ['kafe', 'amerikan', 'vegan', 'cocuk'], 'Pastane' => ['pastane', 'kafe'], 'Soğuk Kahveler' => ['kahve', 'kafe'],
            'Kokteyller' => $alkol, 'Biralar' => $alkol, 'Şaraplar' => $alkol, 'Rakı' => $alkol, 'Viski & Diğer' => $alkol];
    }

    function _mkPaketKodlari() { $o = []; foreach (_mkPaketler() as $g) $o = array_merge($o, array_keys($g)); return $o; }

    // İşletme türleri → varsayılan paketler; 'mutfak' = restoran mutfağı seçimi önerilir
    function _mkTurler()
    {
        return [
            'restoran' => ['ad' => 'Restoran', 'ikon' => '🍽️', 'paket' => ['lokanta', 'tatli', 'sicak', 'soguk'], 'mutfak' => 1],
            'kafe' => ['ad' => 'Kafe', 'ikon' => '☕', 'paket' => ['kafe', 'kahve', 'sicak', 'soguk', 'tatli', 'pastane'], 'mutfak' => 0],
            'bar' => ['ad' => 'Bar / Pub', 'ikon' => '🍺', 'paket' => ['alkol', 'atistirmalik', 'soguk'], 'mutfak' => 0],
            'meyhane' => ['ad' => 'Meyhane', 'ikon' => '🥂', 'paket' => ['meyhane', 'balik', 'alkol', 'soguk'], 'mutfak' => 0],
            'kebapci' => ['ad' => 'Kebapçı / Ocakbaşı', 'ikon' => '🔥', 'paket' => ['kebap', 'durum', 'tatli', 'soguk', 'sicak'], 'mutfak' => 0],
            'pideci' => ['ad' => 'Pide & Lahmacun', 'ikon' => '🫓', 'paket' => ['pide', 'soguk', 'sicak'], 'mutfak' => 0],
            'donerci' => ['ad' => 'Döner / Dürüm / Büfe', 'ikon' => '🌯', 'paket' => ['durum', 'soguk'], 'mutfak' => 0],
            'fastfood' => ['ad' => 'Fast Food / Burger', 'ikon' => '🍔', 'paket' => ['amerikan', 'soguk'], 'mutfak' => 0],
            'pizzaci' => ['ad' => 'Pizzacı / İtalyan', 'ikon' => '🍕', 'paket' => ['italyan', 'soguk', 'tatli'], 'mutfak' => 0],
            'balik' => ['ad' => 'Balık Restoranı', 'ikon' => '🐟', 'paket' => ['balik', 'meyhane', 'soguk', 'alkol'], 'mutfak' => 0],
            'kahvalti' => ['ad' => 'Kahvaltı Salonu', 'ikon' => '🍳', 'paket' => ['kahvalti', 'sicak', 'soguk'], 'mutfak' => 0],
            'pastane' => ['ad' => 'Pastane / Fırın', 'ikon' => '🧁', 'paket' => ['pastane', 'tatli', 'kahve', 'sicak', 'soguk'], 'mutfak' => 0],
            'otel' => ['ad' => 'Otel Restoranı', 'ikon' => '🏨', 'paket' => ['kahvalti', 'lokanta', 'italyan', 'amerikan', 'tatli', 'kahve', 'sicak', 'soguk', 'alkol'], 'mutfak' => 1],
        ];
    }

    function _mkAlerjenAd($kodlar)
    {
        $m = ['G' => 'Gluten', 'K' => 'Kabuklu deniz ürünleri', 'Y' => 'Yumurta', 'B' => 'Balık', 'F' => 'Yer fıstığı', 'S' => 'Soya', 'L' => 'Süt (laktoz)',
            'N' => 'Sert kabuklu yemiş', 'C' => 'Kereviz', 'H' => 'Hardal', 'Z' => 'Susam', 'T' => 'Sülfit', 'P' => 'Acı bakla (lupin)', 'M' => 'Yumuşakça'];
        $o = [];
        foreach (str_split((string) $kodlar) as $h) if (isset($m[$h])) $o[] = $m[$h];
        return array_values(array_unique($o));
    }

    // Kütüphane: [kod => [...]] (dosya değişmedikçe önbellekte)
    function _mkVeri()
    {
        static $v = null;
        if ($v !== null) return $v;
        $f = resource_path('menu_kutuphane/urunler.tsv');
        $anahtar = 'mk_veri_' . (is_file($f) ? filemtime($f) . '_' . filesize($f) : 0);
        return $v = cache()->remember($anahtar, now()->addDay(), function () use ($f) {
            $o = [];
            if (!is_file($f)) return $o;
            foreach (file($f, FILE_IGNORE_NEW_LINES) as $l) {
                $p = explode('|', $l);
                if (count($p) < 9) continue;
                $o[$p[0]] = ['kod' => $p[0], 'ad' => $p[1], 'kategori' => $p[2], 'paketler' => array_values(array_filter(explode(',', $p[3]))), 'istasyon' => $p[4],
                    'kdv' => (float) $p[5], 'alerjen' => $p[6], 'aciklama' => $p[7], 'tarif' => $p[8]];
            }
            return $o;
        });
    }

    // Kodun görsel(ler)i: ['<kod>', '<kod>-2', ...] (dosya varsa)
    function _mkGorseller($kod)
    {
        static $liste = null;
        if ($liste === null) {
            $liste = [];
            $veri = _mkVeri();
            foreach (glob(public_path('menu-kutuphane') . '/*.jpg') ?: [] as $g) {
                $ad = basename($g, '.jpg');
                $k = isset($veri[$ad]) ? $ad : preg_replace('/-\d+$/', '', $ad); // kodun kendisi "-2" ile bitebilir
                $liste[$k][] = $ad;
            }
            foreach ($liste as $k => $v) { sort($v, SORT_NATURAL); $liste[$k] = $v; }
        }
        return $liste[$kod] ?? [];
    }

    function _mkGorselUrl($ad) { return url('/menu-kutuphane/' . $ad . '.jpg'); }

    function _mkNorm($s) { return trim(preg_replace('/\s+/u', ' ', mb_strtolower(strtr((string) $s, ['I' => 'ı', 'İ' => 'i']), 'UTF-8'))); }

    // Şubenin menü kurulum durumu (sihirbaz gösterilsin mi)
    function _menuKurulumDurum($sid)
    {
        _menuKutuphaneKolonEnsure();
        $s = DB::table('subeler')->where('id', (int) $sid)->first(['isletme_turu', 'menu_paketleri', 'menu_kurulum']);
        $urunSay = DB::table('urunler')->where('sube_id', (int) $sid)->count();
        return ['tur' => $s->isletme_turu ?? null, 'paketler' => array_values(array_filter(explode(',', (string) ($s->menu_paketleri ?? '')))),
            'durum' => (int) ($s->menu_kurulum ?? 0), 'goster' => $urunSay === 0 && (int) ($s->menu_kurulum ?? 0) !== 2];
    }

    // Görseli sonradan gelen kütüphane ürünlerine görseli bağla (patronun kendi fotoğrafına dokunmaz)
    function _mkGorselTamamla($sid)
    {
        $k = 'mk_gorsel_tamam_' . (int) $sid;
        if (cache()->get($k)) return;
        cache()->put($k, 1, now()->addMinutes(30));
        try {
            foreach (DB::table('urunler')->where('sube_id', (int) $sid)->whereNotNull('sablon_kod')->where(fn ($q) => $q->whereNull('gorsel')->orWhere('gorsel', ''))->get(['id', 'sablon_kod']) as $u) {
                $g = _mkGorseller($u->sablon_kod);
                if ($g) DB::table('urunler')->where('id', $u->id)->update(['gorsel' => _mkGorselUrl($g[0]), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {}
    }

    // Seçilen paketlerin (ya da kodların) ürünleri, kategoriye göre; şubede zaten olanlar 'var' işaretli
    function _mkOnizle($sid, array $paketler, $ara = '')
    {
        $mevcutKod = DB::table('urunler')->where('sube_id', (int) $sid)->whereNotNull('sablon_kod')->pluck('sablon_kod')->flip()->all();
        $mevcutAd = DB::table('urunler')->where('sube_id', (int) $sid)->pluck('ad')->map(fn ($a) => _mkNorm($a))->flip()->all();
        $kats = [];
        foreach (_mkVeri() as $u) {
            if ($paketler && !array_intersect($u['paketler'], $paketler)) continue;
            if ($ara !== '' && !str_contains(_mkNorm($u['ad']), $ara)) continue;
            $g = _mkGorseller($u['kod']);
            $kats[$u['kategori']][] = ['kod' => $u['kod'], 'ad' => $u['ad'], 'paketler' => $u['paketler'], 'aciklama' => $u['aciklama'], 'istasyon' => $u['istasyon'], 'kdv' => $u['kdv'],
                'alerjenler' => _mkAlerjenAd($u['alerjen']), 'gorsel' => $g ? _mkGorselUrl($g[0]) : null, 'gorseller' => array_map('_mkGorselUrl', $g),
                'var' => isset($mevcutKod[$u['kod']]) || isset($mevcutAd[_mkNorm($u['ad'])])];
        }
        $sira = array_flip(_mkKategoriler());
        $sahip = _mkKatSahibi();
        // öneri: sahibi olan kategori ve sahip paketlerden hiçbiri seçilmemiş -> işaretsiz, listenin sonunda
        $oneri = fn ($ad) => $paketler && isset($sahip[$ad]) && !array_intersect($sahip[$ad], $paketler) ? 1 : 0;
        uksort($kats, fn ($a, $b) => [$oneri($a), $sira[$a] ?? 99] <=> [$oneri($b), $sira[$b] ?? 99]);
        $o = [];
        foreach ($kats as $ad => $urunler) $o[] = ['ad' => $ad, 'oneri' => $oneri($ad), 'urunler' => $urunler];
        return $o;
    }

    // Kategori bul/aç (ada göre, büyük/küçük harf duyarsız)
    function _mkKategoriId($sid, $katAd, &$maxSira, $kdv = null, $sablon = 0)
    {
        $kat = DB::table('menu_kategorileri')->where('sube_id', $sid)->whereRaw('LOWER(ad) = ?', [mb_strtolower($katAd, 'UTF-8')])->first(['id']);
        if ($kat) return [(int) $kat->id, false];
        $maxSira += 10;
        $id = DB::table('menu_kategorileri')->insertGetId(['sube_id' => $sid, 'ad' => $katAd, 'sira' => $maxSira, 'aktif' => 1, 'kdv' => $kdv, 'sablon' => $sablon,
            'created_at' => now(), 'updated_at' => now()]);
        return [$id, true];
    }

    // Kütüphaneden şubeye ekle. $kodlar: seçilen ürün kodları. Döner: [eklenen, atlanan, kategori]
    function _mkEkle($sid, array $kodlar, $personel = null)
    {
        _menuKutuphaneKolonEnsure();
        $sid = (int) $sid;
        $veri = _mkVeri();
        $vars = _kdvVarsayilan($sid);
        $mevcutKod = DB::table('urunler')->where('sube_id', $sid)->whereNotNull('sablon_kod')->pluck('sablon_kod')->flip()->all();
        $mevcutAd = DB::table('urunler')->where('sube_id', $sid)->pluck('ad')->map(fn ($a) => _mkNorm($a))->flip()->all();
        $secilen = [];
        foreach ($kodlar as $k) if (isset($veri[$k]) && !isset($mevcutKod[$k]) && !isset($mevcutAd[_mkNorm($veri[$k]['ad'])])) $secilen[$k] = $veri[$k];
        $atlanan = count(array_unique($kodlar)) - count($secilen);
        if (!$secilen) return ['eklenen' => 0, 'atlanan' => $atlanan, 'kategori' => 0];
        $katSira = array_flip(_mkKategoriler());
        $grup = [];
        foreach ($secilen as $u) $grup[$u['kategori']][] = $u;
        uksort($grup, fn ($a, $b) => ($katSira[$a] ?? 99) <=> ($katSira[$b] ?? 99));
        $alerjenKolon = Schema::hasColumn('urunler', 'alerjenler');
        $eklenen = 0; $yeniKat = 0;
        DB::transaction(function () use ($grup, $sid, $vars, $alerjenKolon, &$eklenen, &$yeniKat) {
            $maxSira = (int) DB::table('menu_kategorileri')->where('sube_id', $sid)->max('sira');
            $simdi = now();
            foreach ($grup as $katAd => $urunler) {
                // Kategori KDV'si: ürünlerin çoğunluk oranı (alkol kategorileri %20); şube varsayılanına eşitse boş (varsayılanı izler)
                $say = array_count_values(array_map(fn ($u) => (string) $u['kdv'], $urunler));
                arsort($say);
                $katKdv = (float) array_key_first($say);
                $katKdvYaz = abs($katKdv - $vars) < 0.001 ? null : $katKdv;
                [$katId, $yeni] = _mkKategoriId($sid, $katAd, $maxSira, $katKdvYaz, 1);
                if ($yeni) $yeniKat++;
                $etkin = DB::table('menu_kategorileri')->where('id', $katId)->value('kdv') ?? $vars;
                foreach ($urunler as $u) {
                    $g = _mkGorseller($u['kod']);
                    $satir = [
                        'sube_id' => $sid, 'kategori_id' => $katId, 'ad' => $u['ad'], 'aciklama' => $u['aciklama'] ?: null, 'fiyat' => 0,
                        'stok_takipli' => 0, 'tukendi' => 0, 'aktif' => 0, 'fiyat_bekliyor' => 1, 'sablon_kod' => $u['kod'],
                        'istasyon' => $u['istasyon'] ?: 'mutfak', 'kdv' => abs($u['kdv'] - (float) $etkin) < 0.001 ? null : $u['kdv'],
                        'gorsel' => $g ? _mkGorselUrl($g[0]) : null, 'created_at' => $simdi, 'updated_at' => $simdi,
                    ];
                    if ($alerjenKolon) { $al = _mkAlerjenAd($u['alerjen']); $satir['alerjenler'] = $al ? implode(',', $al) : null; }
                    DB::table('urunler')->insert($satir);
                    $eklenen++;
                }
            }
        });
        cache()->forget('kds_bar_urun_' . $sid);
        _mkLog($sid, $personel, 'Hazır menü kütüphanesinden ' . $eklenen . ' ürün eklendi (' . $yeniKat . ' yeni kategori; fiyat bekliyor)');
        return ['eklenen' => $eklenen, 'atlanan' => $atlanan, 'kategori' => $yeniKat];
    }

    // Hareketler kaydı (toplu işlem tek satır; aktivite_loglari)
    function _mkLog($sid, $p, $metin)
    {
        try {
            \App\Http\Middleware\AktiviteLog::ensure();
            if (!Schema::hasTable('aktivite_loglari')) return;
            $kol = array_flip(Schema::getColumnListing('aktivite_loglari'));
            $row = array_intersect_key([
                'sube_id' => (int) $sid, 'personel_id' => $p->id ?? null, 'personel_ad' => $p->ad ?? null, 'rol' => $p->rol ?? null, 'kategori' => 'menu',
                'aksiyon' => $metin, 'aciklama' => $metin, 'yol' => request()->path(), 'metod' => 'POST', 'ip' => request()->ip(), 'created_at' => now(),
            ], $kol);
            if ($row) DB::table('aktivite_loglari')->insert($row);
        } catch (\Throwable $e) {}
    }

    // Menü fotoğrafı/PDF → [{kategori, ad, aciklama, fiyat}] (Claude görsel okuma; e-fatura okuyucusuyla aynı servis)
    function _mkFotoOku($sid, $ic, $mime, $sayfa = 1)
    {
        $kotaKey = 'mk_ai_' . (int) $sid . '_' . date('Ymd');
        $sinir = (int) env('MENU_AI_GUNLUK', 10);
        if ((int) cache()->get($kotaKey, 0) + $sayfa > $sinir) return ['hata' => 'Günlük menü okuma sınırı doldu (' . $sinir . ' sayfa). Yarın tekrar deneyin.'];
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['hata' => 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        $kalem = ['type' => 'object', 'additionalProperties' => false, 'required' => ['kategori', 'ad', 'aciklama', 'fiyat'],
            'properties' => ['kategori' => ['type' => 'string'], 'ad' => ['type' => 'string'], 'aciklama' => ['type' => ['string', 'null']], 'fiyat' => ['type' => ['number', 'null']]]];
        $sema = ['type' => 'object', 'additionalProperties' => false, 'required' => ['urunler'], 'properties' => ['urunler' => ['type' => 'array', 'items' => $kalem]]];
        $sys = 'Restoran/kafe menüsünü (fotoğraf ya da PDF) yapılandırılmış veriye çeviriyorsun. Menüde yazan her ürünü, bulunduğu başlık (kategori) ile çıkar. '
            . 'Ürün adını menüde yazdığı gibi, düzgün Türkçe karakterle yaz. Fiyatı Türkçe biçimden sayıya çevir (1.250,00 → 1250). Fiyat okunmuyorsa null; asla tahmin etme. '
            . 'Açıklama menüde yazıyorsa kısa tut, yoksa null. Kategori başlığı yoksa ürüne uygun genel bir kategori adı ver (Ana Yemekler, İçecekler, Tatlılar gibi).';
        $blok = ['type' => $mime === 'application/pdf' ? 'document' : 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => base64_encode($ic)]];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
                ->timeout(150)->post('https://api.anthropic.com/v1/messages', [
                    'model' => env('MENU_AI_MODEL', env('FATURA_AI_MODEL', 'claude-haiku-5-5')), 'max_tokens' => 16000, 'system' => $sys,
                    'output_config' => ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $sema]],
                    'messages' => [['role' => 'user', 'content' => [$blok, ['type' => 'text', 'text' => 'Bu menüdeki tüm ürünleri kategorileriyle çıkar.']]]],
                ]);
        } catch (\Throwable $e) { return ['hata' => 'Yapay zekâ servisine bağlanılamadı.']; }
        if (!$resp->successful()) return ['hata' => 'Yapay zekâ servisi cevap vermedi (' . $resp->status() . ').'];
        cache()->put($kotaKey, (int) cache()->get($kotaKey, 0) + $sayfa, now()->addDays(2));
        if ($resp->json('stop_reason') === 'max_tokens') return ['hata' => 'Menü çok uzun; sayfaları ayrı ayrı yükleyin.'];
        $txt = ''; foreach ((array) $resp->json('content') as $bl) if (($bl['type'] ?? '') === 'text') $txt .= $bl['text'];
        $j = json_decode($txt, true);
        if (!is_array($j) || !isset($j['urunler'])) return ['hata' => 'Yapay zekâ cevabı çözümlenemedi.'];
        // Kütüphane eşleşmesi: aynı ad → görsel/istasyon/KDV/alerjen kütüphaneden gelir
        $adMap = [];
        foreach (_mkVeri() as $u) $adMap[_mkNorm($u['ad'])] = $u;
        $o = [];
        foreach ($j['urunler'] as $u) {
            $ad = trim((string) ($u['ad'] ?? ''));
            if ($ad === '') continue;
            $m = $adMap[_mkNorm($ad)] ?? null;
            $g = $m ? _mkGorseller($m['kod']) : [];
            $o[] = ['kategori' => trim((string) ($u['kategori'] ?? '')) ?: ($m['kategori'] ?? 'Diğer'), 'ad' => mb_substr($ad, 0, 120),
                'aciklama' => $u['aciklama'] ?? ($m['aciklama'] ?? null), 'fiyat' => isset($u['fiyat']) && $u['fiyat'] > 0 ? round((float) $u['fiyat'], 2) : null,
                'sablon_kod' => $m['kod'] ?? null, 'gorsel' => $g ? _mkGorselUrl($g[0]) : null];
        }
        return ['urunler' => $o, 'kalan' => max(0, $sinir - (int) cache()->get($kotaKey, 0))];
    }
}

// ----------------------------------------------------------------------------
// API (Flutter + web panel aynı uçlar; yetki: menü düzenleme = sahip/müdür)
// ----------------------------------------------------------------------------
Route::get('/api/patron/menu-kutuphane/tanim', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    $say = [];
    foreach (_mkVeri() as $u) foreach ($u['paketler'] as $pk) $say[$pk] = ($say[$pk] ?? 0) + 1;
    $gruplar = [];
    foreach (_mkPaketler() as $grup => $pkl) {
        $l = [];
        foreach ($pkl as $k => [$ad, $ikon]) $l[] = ['kod' => $k, 'ad' => $ad, 'ikon' => $ikon, 'adet' => $say[$k] ?? 0];
        $gruplar[] = ['ad' => $grup, 'paketler' => $l];
    }
    $turler = [];
    foreach (_mkTurler() as $k => $t) $turler[] = ['kod' => $k] + $t;
    return ['ok' => 1, 'turler' => $turler, 'gruplar' => $gruplar, 'toplam' => count(_mkVeri()), 'kurulum' => _menuKurulumDurum($p->sube_id)];
});

Route::get('/api/patron/menu-kutuphane/onizle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $paketler = array_values(array_intersect(array_filter(explode(',', (string) $r->input('paketler'))), _mkPaketKodlari()));
    return ['ok' => 1, 'kategoriler' => _mkOnizle($p->sube_id, $paketler, _mkNorm($r->input('ara', '')))];
});

Route::post('/api/patron/menu-kutuphane/ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $kodlar = json_decode((string) $r->input('kodlar'), true);
    if (!is_array($kodlar) || !$kodlar) return ['ok' => 0, 'hata' => 'Ürün seçilmedi'];
    $kodlar = array_slice(array_values(array_filter($kodlar, 'is_string')), 0, 1000);
    // Sihirbazdan gelirse işletme türü + paketler şubeye yazılır (sonraki "Kütüphaneden ekle" bunlarla açılır)
    $upd = ['menu_kurulum' => 1];
    if ($r->filled('tur') && isset(_mkTurler()[$r->input('tur')])) $upd['isletme_turu'] = $r->input('tur');
    if ($r->filled('paketler')) {
        $eski = array_values(array_filter(explode(',', (string) DB::table('subeler')->where('id', $p->sube_id)->value('menu_paketleri'))));
        $upd['menu_paketleri'] = implode(',', array_unique(array_merge($eski, array_intersect(explode(',', (string) $r->input('paketler')), _mkPaketKodlari()))));
    }
    DB::table('subeler')->where('id', $p->sube_id)->update($upd);
    return ['ok' => 1] + _mkEkle($p->sube_id, $kodlar, $p);
});

Route::post('/api/patron/menu-kurulum-atla', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    DB::table('subeler')->where('id', $p->sube_id)->update(['menu_kurulum' => 2]);
    return ['ok' => 1];
});

// Toplu fiyat: {"<urun_id>": 120.5, ...}. Fiyat > 0 girilen "fiyat bekleyen" ürün satışa açılır.
Route::post('/api/patron/menu-fiyat-toplu', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $f = json_decode((string) $r->input('fiyatlar'), true);
    if (!is_array($f) || !$f) return ['ok' => 0, 'hata' => 'Fiyat girilmedi'];
    $n = 0; $acilan = 0;
    foreach ($f as $id => $fiyat) {
        $fiyat = (float) str_replace(',', '.', (string) $fiyat);
        if ($fiyat <= 0) continue;
        $u = DB::table('urunler')->where('id', (int) $id)->where('sube_id', $p->sube_id)->first(['id', 'fiyat_bekliyor']);
        if (!$u) continue;
        $upd = ['fiyat' => round($fiyat, 2), 'updated_at' => now()];
        if ($u->fiyat_bekliyor) { $upd['fiyat_bekliyor'] = 0; $upd['aktif'] = 1; $acilan++; }
        DB::table('urunler')->where('id', $u->id)->update($upd);
        $n++;
    }
    if ($n) _mkLog($p->sube_id, $p, $n . ' ürünün fiyatı toplu güncellendi' . ($acilan ? ' (' . $acilan . ' ürün satışa açıldı)' : ''));
    return ['ok' => 1, 'guncellenen' => $n, 'acilan' => $acilan];
});

// Şablondan gelip HİÇ satılmamış ürünleri temizle (onizle=1 → yalnız sayı). Satışı olan ürüne dokunulmaz.
Route::post('/api/patron/menu-sablon-temizle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $q = DB::table('urunler')->where('sube_id', $p->sube_id)->whereNotNull('sablon_kod')
        ->whereNotExists(fn ($x) => $x->select(DB::raw(1))->from('adisyon_kalemleri')->whereColumn('adisyon_kalemleri.urun_id', 'urunler.id'));
    if (!$r->boolean('hepsi')) $q->where('fiyat_bekliyor', 1); // varsayılan: yalnız fiyatı hiç girilmemişler
    $ids = $q->pluck('id')->all();
    if ($r->boolean('onizle')) return ['ok' => 1, 'adet' => count($ids)];
    $silinen = 0;
    foreach (array_chunk($ids, 200) as $parca) {
        try { $silinen += DB::table('urunler')->whereIn('id', $parca)->delete(); }
        catch (\Throwable $e) {
            foreach ($parca as $id) {
                try { $silinen += DB::table('urunler')->where('id', $id)->delete(); }
                catch (\Throwable $e2) { DB::table('urunler')->where('id', $id)->update(['aktif' => 0, 'updated_at' => now()]); } // bağlı kayıt (reçete vb.) → pasif
            }
        }
    }
    // Kütüphanenin açtığı ve boş kalan kategoriler
    DB::table('menu_kategorileri')->where('sube_id', $p->sube_id)->where('sablon', 1)
        ->whereNotExists(fn ($x) => $x->select(DB::raw(1))->from('urunler')->whereColumn('urunler.kategori_id', 'menu_kategorileri.id'))->delete();
    cache()->forget('kds_bar_urun_' . $p->sube_id);
    if ($silinen) _mkLog($p->sube_id, $p, 'Hiç satılmamış ' . $silinen . ' şablon ürün menüden kaldırıldı');
    return ['ok' => 1, 'silinen' => $silinen];
});

// Ürünün görselini kütüphaneden seç: gorsel = "<kod>" / "<kod>-2" ... ya da "varsayilan" (kod: kütüphane dışı ürün için)
Route::post('/api/patron/urun-gorsel-sec', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $u = DB::table('urunler')->where('id', (int) $r->input('id'))->where('sube_id', $p->sube_id)->first(['id', 'sablon_kod']);
    if (!$u) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
    $sec = (string) $r->input('gorsel', 'varsayilan');
    $kod = $u->sablon_kod ?: (string) $r->input('kod');
    $liste = _mkGorseller($kod);
    if (!$liste && $sec !== 'varsayilan') { $kod = preg_replace('/-\d+$/', '', $sec); $liste = _mkGorseller($kod); }
    if (!$liste) return ['ok' => 0, 'hata' => 'Bu ürün için hazır görsel yok'];
    $ad = $sec === 'varsayilan' ? $liste[0] : (in_array($sec, $liste, true) ? $sec : null);
    if (!$ad) return ['ok' => 0, 'hata' => 'Görsel bulunamadı'];
    foreach (glob(storage_path('app/urun_foto/' . $u->id . '.*')) ?: [] as $eski) @unlink($eski); // yüklenmiş foto yerine kütüphane görseli
    DB::table('urunler')->where('id', $u->id)->update(['gorsel' => _mkGorselUrl($ad), 'updated_at' => now()]);
    return ['ok' => 1, 'gorsel' => _mkGorselUrl($ad) . '?v=' . time()];
});

// Ürün adına uyan kütüphane görselleri (kendi ürününe hazır görsel seçmek / alternatifleri göstermek için)
Route::get('/api/patron/urun-gorsel-oneri', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    $o = [];
    $kod = (string) $r->input('kod');
    if ($kod !== '') foreach (_mkGorseller($kod) as $g) $o[] = ['kod' => $kod, 'ad' => _mkVeri()[$kod]['ad'] ?? $kod, 'gorsel' => $g, 'url' => _mkGorselUrl($g)];
    $ad = _mkNorm($r->input('ad', ''));
    if (mb_strlen($ad) >= 3) foreach (_mkVeri() as $u) {
        if ($u['kod'] === $kod) continue;
        $n = _mkNorm($u['ad']);
        if ($n !== $ad && !str_contains($n, $ad) && !str_contains($ad, $n)) continue;
        foreach (_mkGorseller($u['kod']) as $g) $o[] = ['kod' => $u['kod'], 'ad' => $u['ad'], 'gorsel' => $g, 'url' => _mkGorselUrl($g)];
        if (count($o) >= 12) break;
    }
    return ['ok' => 1, 'gorseller' => $o];
});

// Menü fotoğrafı / PDF oku → önizleme (kayıt yok)
Route::post('/api/patron/menu-foto-oku', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    @set_time_limit(200);
    $f = $r->file('dosya');
    if (!$f || !$f->isValid()) return ['ok' => 0, 'hata' => 'Menü fotoğrafı ya da PDF seçin.'];
    if ($f->getSize() > 15 * 1024 * 1024) return ['ok' => 0, 'hata' => 'Dosya 15 MB\'tan büyük.'];
    $ic = (string) file_get_contents($f->getRealPath());
    $mime = str_starts_with($ic, '%PDF') ? 'application/pdf' : (str_starts_with($ic, "\xFF\xD8") ? 'image/jpeg' : (str_starts_with($ic, "\x89PNG") ? 'image/png' : (substr($ic, 8, 4) === 'WEBP' ? 'image/webp' : null)));
    if (!$mime) return ['ok' => 0, 'hata' => 'Yalnız PDF, JPG, PNG ya da WEBP okunur.'];
    $sayfa = 1;
    if ($mime === 'application/pdf') {
        $sayfa = max(1, preg_match_all('#/Type\s*/Page(?![a-z])#', $ic));
        if ($sayfa > 8) return ['ok' => 0, 'hata' => 'PDF ' . $sayfa . ' sayfa; tek seferde en fazla 8 sayfa okunur.'];
    }
    $a = _mkFotoOku($p->sube_id, $ic, $mime, $sayfa);
    if (!empty($a['hata'])) return ['ok' => 0, 'hata' => $a['hata']];
    if (!$a['urunler']) return ['ok' => 0, 'hata' => 'Menüde okunabilen ürün bulunamadı. Daha net bir fotoğraf çekin.'];
    $mevcutAd = DB::table('urunler')->where('sube_id', $p->sube_id)->pluck('ad')->map(fn ($x) => _mkNorm($x))->flip()->all();
    foreach ($a['urunler'] as $i => $u) $a['urunler'][$i]['var'] = isset($mevcutAd[_mkNorm($u['ad'])]);
    return ['ok' => 1, 'urunler' => $a['urunler'], 'ai_kalan' => $a['kalan']];
});

// Fotoğraftan okunan (patronun düzelttiği) ürünleri ekle. Fiyatı olan satışa açık, fiyatsız "fiyat bekliyor".
Route::post('/api/patron/menu-foto-ekle', function (Request $r) {
    $p = _apiPersonel($r);
    if (!_restoMenuYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Menü düzenleme yetkiniz yok'], $p ? 403 : 401);
    _menuKutuphaneKolonEnsure();
    $liste = json_decode((string) $r->input('urunler'), true);
    if (!is_array($liste) || !$liste) return ['ok' => 0, 'hata' => 'Ürün seçilmedi'];
    $sid = (int) $p->sube_id;
    $veri = _mkVeri();
    $vars = _kdvVarsayilan($sid);
    $mevcutAd = DB::table('urunler')->where('sube_id', $sid)->pluck('ad')->map(fn ($x) => _mkNorm($x))->flip()->all();
    $alerjenKolon = Schema::hasColumn('urunler', 'alerjenler');
    $eklenen = 0; $bekleyen = 0; $yeniKat = 0;
    DB::transaction(function () use ($liste, $sid, $veri, $vars, $alerjenKolon, &$mevcutAd, &$eklenen, &$bekleyen, &$yeniKat) {
        $maxSira = (int) DB::table('menu_kategorileri')->where('sube_id', $sid)->max('sira');
        $katCache = [];
        foreach (array_slice($liste, 0, 600) as $u) {
            $ad = mb_substr(trim((string) ($u['ad'] ?? '')), 0, 120);
            if ($ad === '' || isset($mevcutAd[_mkNorm($ad)])) continue;
            $katAd = mb_substr(trim((string) ($u['kategori'] ?? '')) ?: 'Diğer', 0, 60);
            $kk = mb_strtolower($katAd, 'UTF-8');
            if (!isset($katCache[$kk])) { [$katCache[$kk], $yeni] = _mkKategoriId($sid, $katAd, $maxSira); if ($yeni) $yeniKat++; }
            $m = (!empty($u['sablon_kod']) && isset($veri[$u['sablon_kod']])) ? $veri[$u['sablon_kod']] : null;
            $fiyat = max(0, round((float) str_replace(',', '.', (string) ($u['fiyat'] ?? 0)), 2));
            $g = $m ? _mkGorseller($m['kod']) : [];
            $satir = ['sube_id' => $sid, 'kategori_id' => $katCache[$kk], 'ad' => $ad, 'aciklama' => trim((string) ($u['aciklama'] ?? '')) ?: null, 'fiyat' => $fiyat,
                'stok_takipli' => 0, 'tukendi' => 0, 'aktif' => $fiyat > 0 ? 1 : 0, 'fiyat_bekliyor' => $fiyat > 0 ? 0 : 1, 'sablon_kod' => $m['kod'] ?? null,
                'istasyon' => $m['istasyon'] ?? _mutfakIstasyonTahmin(' ' . $ad . ' ' . $katAd), 'kdv' => ($m && abs($m['kdv'] - $vars) > 0.001) ? $m['kdv'] : null,
                'gorsel' => $g ? _mkGorselUrl($g[0]) : null, 'created_at' => now(), 'updated_at' => now()];
            if ($alerjenKolon && $m) { $al = _mkAlerjenAd($m['alerjen']); $satir['alerjenler'] = $al ? implode(',', $al) : null; }
            DB::table('urunler')->insert($satir);
            $mevcutAd[_mkNorm($ad)] = 1;
            $eklenen++;
            if ($fiyat <= 0) $bekleyen++;
        }
    });
    DB::table('subeler')->where('id', $sid)->where('menu_kurulum', 0)->update(['menu_kurulum' => 1]);
    cache()->forget('kds_bar_urun_' . $sid);
    if ($eklenen) _mkLog($sid, $p, 'Menü fotoğrafından ' . $eklenen . ' ürün eklendi' . ($bekleyen ? ' (' . $bekleyen . ' ürün fiyat bekliyor)' : ''));
    return ['ok' => 1, 'eklenen' => $eklenen, 'bekleyen' => $bekleyen, 'kategori' => $yeniKat];
});

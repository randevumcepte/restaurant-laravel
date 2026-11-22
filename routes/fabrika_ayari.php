<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ===== FABRİKA AYARI (yalnız sistem yöneticisi, /resteos-yonetim/fabrika-ayari) =====
// Sistemi "yeni kurulmuş" hâle döndürür: işaretlenen işletme kaydı + o işletmenin patron (sahip) girişi kalır,
// geri kalan bütün işletme verisi (satış, menü, ürün, reçete, malzeme, masa, personel, stok, fatura, log...) silinir.
// İşaretlenmeyen işletmeler tamamen silinir. Sistem tabloları (yönetici, kredi, katalog, birimler, ayarlar...) korunur.
// Silmeden önce etkilenen BÜTÜN satırlar storage/app/private/fabrika_yedek/ altına yedeklenir; aynı sayfadan geri yüklenebilir.
// Varsayılanlar (departman, sebep, indirim, çark dilimi...) yeni işletmede olduğu gibi ilk açılışta kendiliğinden kurulur.

if (!function_exists('_fabrikaTablolar')) {
    // Hiç dokunulmayan sistem/altyapı tabloları
    function _fabrikaSistem()
    {
        return ['migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'sessions', 'password_reset_tokens', 'users',
            'personal_access_tokens',
            'ayarlar', 'birimler', 'urun_katalogu', 'hal_fiyatlari', 'fatura_pdf_sablon', 'freepbx_ayarlari', 'sube_gruplari', 'isletme_gruplari', 'franchise_baglari', 'subeler'];
    }
    // "Bağlantıları koru" seçiliyse korunan işletme ayar tabloları (kurulumu zahmetli dış bağlantılar)
    function _fabrikaBaglanti()
    {
        return ['wa_hatlar', 'eposta_hesaplari', 'efatura_baglanti', 'edonusum_ayarlari', 'entegrasyonlar', 'odeme_ayarlari',
            'muhasebe_ayarlari', 'santral_ayarlari', 'oauth_uygulamalari', 'isletme_yasal', 'isletme_hatlari', 'cihazlar', 'cihaz_tokenlari'];
    }
    function _fabrikaTablolar()
    {
        $t = [];
        foreach (Schema::getTables() as $x) $t[] = is_array($x) ? $x['name'] : $x->name;
        $t = array_values(array_filter($t, fn ($n) => $n !== 'sqlite_sequence'));
        sort($t);
        return $t;
    }
    // Her tablo için ne yapılacağı: ['islem' => koru|sil|kismi, 'sorgu' => silinecek satırları seçen builder]
    function _fabrikaPlan(array $koruSube, bool $baglantiKoru)
    {
        $sistem = _fabrikaSistem();
        $baglanti = _fabrikaBaglanti();
        $plan = [];
        foreach (_fabrikaTablolar() as $t) {
            $kol = Schema::getColumnListing($t);
            if (in_array($t, $sistem, true) || str_starts_with($t, 'resteos_')) {
                if ($t === 'subeler') {
                    $q = DB::table($t)->whereNotIn('id', $koruSube ?: [0]);
                    $plan[$t] = ['islem' => 'kismi', 'sorgu' => $q, 'not' => 'İşaretli işletmeler kalır'];
                } else {
                    $plan[$t] = ['islem' => 'koru', 'not' => 'Sistem tablosu'];
                }
                continue;
            }
            if ($t === 'personeller') {
                $q = DB::table($t)->where(function ($w) use ($koruSube) {
                    $w->whereNotIn('sube_id', $koruSube ?: [0])->orWhere('rol', '!=', 'sahip')->orWhereNull('rol');
                });
                $plan[$t] = ['islem' => 'kismi', 'sorgu' => $q, 'not' => 'Yalnız patron (sahip) girişi kalır'];
                continue;
            }
            if ($baglantiKoru && in_array($t, $baglanti, true)) {
                $q = DB::table($t);
                if (in_array('sube_id', $kol, true)) $q->whereNotIn('sube_id', $koruSube ?: [0]);
                if ($t === 'cihaz_tokenlari' && in_array('personel_id', $kol, true)) { // yalnız kalan patronun telefon bildirimi kalsın
                    $q->orWhereNotIn('personel_id', DB::table('personeller')->whereIn('sube_id', $koruSube ?: [0])->where('rol', 'sahip')->pluck('id')->all() ?: [0]);
                }
                if (!in_array('sube_id', $kol, true)) { $plan[$t] = ['islem' => 'koru', 'not' => 'Bağlantı ayarı']; continue; }
                $plan[$t] = ['islem' => 'kismi', 'sorgu' => $q, 'not' => 'Bağlantı ayarı (işaretli işletmeninki kalır)'];
                continue;
            }
            $plan[$t] = ['islem' => 'sil', 'sorgu' => DB::table($t), 'not' => ''];
        }
        return $plan;
    }
    function _fabrikaYedekDizin()
    {
        $d = storage_path('app/private/fabrika_yedek');
        if (!is_dir($d)) @mkdir($d, 0775, true);
        return $d;
    }
    function _fabrikaYedekler()
    {
        $l = [];
        foreach (glob(_fabrikaYedekDizin() . '/*.json.gz') ?: [] as $f) $l[] = ['ad' => basename($f), 'boyut' => filesize($f), 'zaman' => filemtime($f)];
        usort($l, fn ($a, $b) => $b['zaman'] <=> $a['zaman']);
        return $l;
    }
}

Route::get('/resteos-yonetim/fabrika-ayari', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $subeler = DB::table('subeler')->orderBy('id')->get();
    $koru = array_map('intval', (array) $r->query('koru', $subeler->count() === 1 ? [$subeler->first()->id] : []));
    $baglantiKoru = $r->query('baglanti', '1') === '1';
    $satirlar = [];
    $toplam = 0;
    foreach (_fabrikaPlan($koru, $baglantiKoru) as $t => $p) {
        $var = DB::table($t)->count();
        $silinecek = $p['islem'] === 'koru' ? 0 : (clone $p['sorgu'])->count();
        $toplam += $silinecek;
        $satirlar[] = ['tablo' => $t, 'var' => $var, 'sil' => $silinecek, 'islem' => $p['islem'], 'not' => $p['not']];
    }
    $sahipler = DB::table('personeller')->whereIn('sube_id', $koru ?: [0])->where('rol', 'sahip')->get(['id', 'sube_id', 'ad']);
    return view('resteos_yonetim.fabrika-ayari', compact('subeler', 'koru', 'baglantiKoru', 'satirlar', 'toplam', 'sahipler') + ['yedekler' => _fabrikaYedekler()]);
});

Route::post('/resteos-yonetim/fabrika-ayari', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $koru = array_map('intval', (array) $r->input('koru', []));
    $baglantiKoru = $r->input('baglanti') === '1';
    $geri = '/resteos-yonetim/fabrika-ayari?' . http_build_query(['koru' => $koru, 'baglanti' => $baglantiKoru ? 1 : 0]);
    if (!$koru || DB::table('subeler')->whereIn('id', $koru)->count() !== count($koru)) return redirect($geri)->with('hata', 'Korunacak en az bir işletme seçin.');
    if (!DB::table('personeller')->whereIn('sube_id', $koru)->where('rol', 'sahip')->exists()) return redirect($geri)->with('hata', 'Seçilen işletmede patron (sahip) girişi yok; sıfırlamadan sonra kimse giremez.');
    if (trim((string) $r->input('onay')) !== 'FABRIKA AYARI') return redirect($geri)->with('hata', 'Onay için kutuya FABRIKA AYARI yazın.');

    @set_time_limit(0);
    @ignore_user_abort(true);
    $plan = _fabrikaPlan($koru, $baglantiKoru);

    // 1) Yedek: silinecek her satır (tablo başına JSON satırları, gzip). Yedek yazılamazsa hiçbir şey silinmez.
    $dosya = _fabrikaYedekDizin() . '/fabrika_' . now()->format('Y-m-d_His') . '.json.gz';
    $gz = @gzopen($dosya, 'wb6');
    if (!$gz) return redirect($geri)->with('hata', 'Yedek dosyası açılamadı; işlem yapılmadı.');
    $ozet = [];
    try {
        foreach ($plan as $t => $p) {
            if ($p['islem'] === 'koru') continue;
            $n = 0;
            $q = (clone $p['sorgu']);
            $sirali = in_array('id', Schema::getColumnListing($t), true);
            $isle = function ($satirlar) use ($gz, $t, &$n) {
                foreach ($satirlar as $s) { gzwrite($gz, json_encode(['t' => $t, 'r' => (array) $s], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n"); $n++; }
            };
            if ($sirali) $q->orderBy('id')->chunk(1000, $isle); else $isle($q->get());
            if ($n) $ozet[$t] = $n;
        }
        gzclose($gz);
    } catch (\Throwable $e) {
        @gzclose($gz); @unlink($dosya);
        return redirect($geri)->with('hata', 'Yedek alınamadı (' . mb_substr($e->getMessage(), 0, 150) . '); hiçbir şey silinmedi.');
    }

    // 2) Silme (yabancı anahtar kontrolü kapalı; tablo sırası önemsiz)
    $hatalar = [];
    Schema::disableForeignKeyConstraints();
    try {
        foreach ($plan as $t => $p) {
            if ($p['islem'] === 'koru' || empty($ozet[$t])) continue;
            try { (clone $p['sorgu'])->delete(); } catch (\Throwable $e) { $hatalar[] = $t . ': ' . mb_substr($e->getMessage(), 0, 120); }
        }
    } finally {
        Schema::enableForeignKeyConstraints();
    }
    // Kalan patron(lar): silinen departmana/şubeye bağlı kalmasın
    try {
        $temiz = [];
        foreach (['departman_id', 'departman_unvan', 'kasa_id'] as $k) if (Schema::hasColumn('personeller', $k)) $temiz[$k] = null;
        if ($temiz) DB::table('personeller')->whereIn('sube_id', $koru)->where('rol', 'sahip')->update($temiz);
    } catch (\Throwable $e) {}
    // Önbellek: "varsayılanlar kuruldu" bayrakları ve eski özetler gitsin ki ilk açılış yeni işletme gibi kursun
    try { cache()->flush(); } catch (\Throwable $e) {}
    // Hazır departmanlar işletme büyüklüğüne göre yeniden kurulur (yeni işletmede sistem panelinden seçilince olduğu gibi)
    foreach ($koru as $sid) {
        try {
            $olcek = Schema::hasColumn('subeler', 'olcek') ? DB::table('subeler')->where('id', $sid)->value('olcek') : null;
            if ($olcek && function_exists('_isletmeOlcekUygula')) _isletmeOlcekUygula($sid, $olcek);
        } catch (\Throwable $e) { $hatalar[] = 'departman kurulumu: ' . mb_substr($e->getMessage(), 0, 120); }
    }

    $silinen = array_sum($ozet);
    _ryLog('fabrika_ayari', 'Fabrika ayarı: ' . $silinen . ' satır silindi (' . count($ozet) . ' tablo), korunan işletme: ' . implode(',', $koru) . ', yedek: ' . basename($dosya)
        . ($hatalar ? ' · HATA: ' . implode(' | ', $hatalar) : ''));
    return redirect($geri)->with($hatalar ? 'hata' : 'ok', $hatalar
        ? 'Sıfırlandı ama bazı tablolar silinemedi: ' . implode(' | ', $hatalar) . ' (yedek: ' . basename($dosya) . ')'
        : 'Sistem fabrika ayarına döndü: ' . number_format($silinen, 0, ',', '.') . ' kayıt silindi. Yedek: ' . basename($dosya));
});

// Yedekten geri yükleme: yedekteki satırları geri ekler (aynı id'li kayıt varsa ona dokunmaz)
Route::post('/resteos-yonetim/fabrika-ayari/geri-yukle', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $ad = basename((string) $r->input('yedek'));
    $dosya = _fabrikaYedekDizin() . '/' . $ad;
    if (!preg_match('/^fabrika_[0-9_-]+\.json\.gz$/', $ad) || !is_file($dosya)) return back()->with('hata', 'Yedek bulunamadı.');
    if (trim((string) $r->input('onay')) !== 'GERI YUKLE') return back()->with('hata', 'Onay için kutuya GERI YUKLE yazın.');
    @set_time_limit(0);
    @ignore_user_abort(true);
    $gz = gzopen($dosya, 'rb');
    $tampon = []; $n = 0; $hatalar = [];
    $bosalt = function () use (&$tampon, &$n, &$hatalar) {
        foreach ($tampon as $t => $satirlar) {
            if (!Schema::hasTable($t)) { $hatalar[$t] = 'tablo yok'; continue; }
            $kol = array_flip(Schema::getColumnListing($t));
            foreach (array_chunk($satirlar, 200) as $parca) {
                $parca = array_map(fn ($s) => array_intersect_key($s, $kol), $parca);
                try { $n += DB::table($t)->insertOrIgnore($parca); } catch (\Throwable $e) { $hatalar[$t] = mb_substr($e->getMessage(), 0, 100); }
            }
        }
        $tampon = [];
    };
    Schema::disableForeignKeyConstraints();
    try {
        $say = 0;
        while (!gzeof($gz)) {
            $satir = gzgets($gz);
            if ($satir === false || trim($satir) === '') continue;
            $j = json_decode($satir, true);
            if (!isset($j['t'], $j['r'])) continue;
            $tampon[$j['t']][] = $j['r'];
            if (++$say % 2000 === 0) $bosalt();
        }
        $bosalt();
    } finally {
        gzclose($gz);
        Schema::enableForeignKeyConstraints();
    }
    try { cache()->flush(); } catch (\Throwable $e) {}
    _ryLog('fabrika_geri_yukle', 'Yedekten geri yüklendi: ' . $ad . ', ' . $n . ' satır' . ($hatalar ? ' · HATA: ' . json_encode($hatalar, JSON_UNESCAPED_UNICODE) : ''));
    return back()->with($hatalar ? 'hata' : 'ok', number_format($n, 0, ',', '.') . ' kayıt geri yüklendi' . ($hatalar ? '; sorunlu tablolar: ' . implode(', ', array_keys($hatalar)) : '.'));
});

<?php
// GEÇİCİ SAAT TEŞHİSİ (salt-okunur, yalnız sahip): PHP/MySQL saat dilimi + tüm tarih kolonlarının tipi ve örnek değerleri.
// Saat dilimi geçişi (UTC → Europe/Istanbul) planı için; iş bitince bu dosya ve require satırı silinecek.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/saat-teshis', function () {
    $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {}
    if (!$p || ($p->rol ?? '') !== 'sahip') abort(404);
    $o = ['php_tz' => date_default_timezone_get(), 'app_tz' => config('app.timezone'), 'php_now' => now()->format('Y-m-d H:i:s'),
        'php_now_tr' => now()->setTimezone('Europe/Istanbul')->format('Y-m-d H:i:s'), 'surucu' => DB::getDriverName(),
        'db_tz_ayar' => config('database.connections.' . config('database.default') . '.timezone')];
    if (DB::getDriverName() === 'mysql') {
        $o['mysql'] = (array) DB::selectOne('SELECT NOW() db_now, UTC_TIMESTAMP() db_utc, @@global.time_zone g, @@session.time_zone s, @@system_time_zone sys');
        $db = DB::getDatabaseName();
        $kol = DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, DATA_TYPE d, COLUMN_DEFAULT def, EXTRA ex FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('datetime','timestamp') ORDER BY TABLE_NAME, COLUMN_NAME", [$db]);
        $o['kolon_say'] = ['datetime' => 0, 'timestamp' => 0];
        $o['kolonlar'] = [];
        foreach ($kol as $k) {
            $o['kolon_say'][$k->d]++;
            $o['kolonlar'][] = $k->t . '.' . $k->c . ' ' . $k->d . ($k->def ? ' def=' . $k->def : '') . ($k->ex ? ' ' . $k->ex : '');
        }
        // aynı satırda PHP'nin yazdığı created_at ile MySQL'in damgaladığı updated_at farkı (yeni eklenen satırlarda ~0 veya ~3 saat)
        foreach (['adisyonlar', 'adisyon_kalemleri', 'odemeler'] as $t) {
            try { $o['ornek'][$t] = DB::table($t)->orderByDesc('id')->limit(3)->get(['id', 'created_at', 'updated_at']); } catch (\Throwable $e) {}
        }
        $o['satir_say'] = [];
        foreach (array_unique(array_map(fn ($k) => $k->t, $kol)) as $t) {
            try { $o['satir_say'][$t] = DB::table($t)->count(); } catch (\Throwable $e) {}
        }
    }
    return response()->json($o, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

// TEK SEFERLİK GEÇİŞ: UTC iken PHP'nin yazdığı eski değerleri +3 saat (Türkiye saati) kaydır.
// ?kesim=Y-m-d H:i:s  → yalnız rapor (dokunmaz).   &uygula=EVET → yedek (_tzy_<tablo>) + kaydırma.
// Kolon başına bir kez (işaret tablosu _tz_gecis) → tekrar çalışırsa çift kaydırmaz, yarıda kalırsa devam eder.
Route::get('/saat-teshis/gecis', function (\Illuminate\Http\Request $r) {
    $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {}
    if (!$p || ($p->rol ?? '') !== 'sahip') abort(404);
    if (DB::getDriverName() !== 'mysql') return response()->json(['hata' => 'yalnız mysql']);
    $kesim = (string) $r->query('kesim', '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $kesim)) return response()->json(['hata' => 'kesim=Y-m-d H:i:s gerekli']);
    $uygula = $r->query('uygula') === 'EVET';
    // MySQL'in kendisinin yazdığı (zaten TR saati) kolonlar: kod insert'te bu kolonu vermiyor → dokunma
    $dbYazan = ['giderler.created_at', 'personel_hareketleri.created_at', 'freepbx_ayarlari.created_at', 'isletme_hatlari.created_at',
        'santral_ayarlari.created_at', 'indirimler.created_at', 'failed_jobs.failed_at'];
    // Kullanıcının TR saatiyle elle girdiği kolonlar → dokunma
    $elle = ['ai_ajanda.baslar_at', 'ai_ajanda.biter_at', 'asistan_cozulmeyen.son_tarih', 'santral_cozulemeyen.son_tarih', 'patron_ai_baglantilar.gecerlilik'];
    // İleri tarihli (now()+N) kolonlar: değere değil satırın oluşturulma zamanına göre seç
    $ileri = ['bitis', 'gecerlilik', 'otp_bitis'];
    $db = DB::getDatabaseName();
    $kol = DB::select("SELECT TABLE_NAME t, COLUMN_NAME c, EXTRA ex FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND DATA_TYPE IN ('datetime','timestamp') AND TABLE_NAME NOT LIKE '\\_tz%' ORDER BY TABLE_NAME, COLUMN_NAME", [$db]);
    $onUpdate = []; $tabloKol = [];
    foreach ($kol as $k) {
        if (stripos((string) $k->ex, 'on update') !== false) { $onUpdate[$k->t] = $k->c; continue; } // karışık damga (sync imleci) → dokunma
        $ad = $k->t . '.' . $k->c;
        if (in_array($ad, $dbYazan, true) || in_array($ad, $elle, true)) continue;
        $tabloKol[$k->t][] = $k->c;
    }
    if ($uygula) DB::statement('CREATE TABLE IF NOT EXISTS `_tz_gecis` (kolon VARCHAR(190) PRIMARY KEY, satir INT, kesim VARCHAR(19), at TIMESTAMP NULL)');
    $yapildi = $uygula ? DB::table('_tz_gecis')->pluck('kolon')->flip()->all() : [];
    $rapor = []; $toplam = 0;
    foreach ($tabloKol as $t => $cols) {
        $hasCreated = in_array('created_at', $cols, true);
        // ileri tarihli kolonlar created_at'ten ÖNCE (koşulları created_at'e bakıyor)
        usort($cols, fn ($a, $b) => (in_array($b, $ileri, true) <=> in_array($a, $ileri, true)));
        $yedekAlindi = false;
        foreach ($cols as $c) {
            $ad = $t . '.' . $c;
            $kosul = (in_array($c, $ileri, true) && $hasCreated) ? "`created_at` IS NOT NULL AND `created_at` <= ?" : "`$c` <= ?";
            $n = (int) DB::selectOne("SELECT COUNT(*) n FROM `$t` WHERE `$c` IS NOT NULL AND $kosul", [$kesim])->n;
            $rapor[$ad] = $n . (isset($yapildi[$ad]) ? ' (zaten yapıldı)' : '');
            if (!$uygula || isset($yapildi[$ad])) continue;
            if ($n > 0) {
                if (!$yedekAlindi && !DB::selectOne('SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$db, '_tzy_' . $t])->n) {
                    DB::statement("CREATE TABLE `_tzy_$t` AS SELECT * FROM `$t`");
                }
                $yedekAlindi = true;
                $koru = isset($onUpdate[$t]) ? ", `{$onUpdate[$t]}` = `{$onUpdate[$t]}`" : ''; // ON UPDATE damgası değişmesin
                DB::update("UPDATE `$t` SET `$c` = `$c` + INTERVAL 3 HOUR$koru WHERE `$c` IS NOT NULL AND $kosul", [$kesim]);
            }
            DB::table('_tz_gecis')->insert(['kolon' => $ad, 'satir' => $n, 'kesim' => $kesim, 'at' => now()]);
            $toplam += $n;
        }
    }
    return response()->json(['uygulandi' => $uygula, 'kesim' => $kesim, 'app_tz' => config('app.timezone'), 'php_now' => now()->format('Y-m-d H:i:s'),
        'kaydirilan_satir' => $toplam, 'atlanan_on_update' => $onUpdate, 'atlanan' => array_merge($dbYazan, $elle), 'rapor' => array_filter($rapor, fn ($v) => $v !== '0')],
        200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
});

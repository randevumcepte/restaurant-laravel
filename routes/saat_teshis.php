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

<?php

/**
 * ============================================================================
 *  LOCAL-FIRST SENKRON API  (Flutter yerel SQLite <-> bulut MySQL)
 *  Tamamen ADDITIVE: mevcut 248 ucu ve web panelini bozmaz.
 *  Auth: _apiPersonel() (web.php) — her islem token'daki sube_id ile kapsanir.
 *
 *  Kimlik koprusu: her senkronlanan tabloya 'uuid' kolonu eklenir (istemci uretir).
 *  uuid <-> server id eslemesi burada yapilir; istemci autoincrement bilmek zorunda degil.
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// Senkronlanan entity'ler: istemci adi => backend eslemesi
//   tablo    : MySQL tablosu
//   alanlar  : backend_kolon => local_payload_anahtari (duz kopya)
//   ref      : backend_kolon => [local_uuid_anahtari, referans_tablo]  (uuid -> id cozulur)
//   zaman    : ms(epoch) -> datetime cevrilecek backend kolonlari
//   sube     : sube_id token'dan zorlanir mi (alt tablolarda ust kayit uzerinden gelir)
// ---------------------------------------------------------------------------
if (!function_exists('_syncEntities')) {
    function _syncEntities(): array
    {
        return [
            'masalar' => [
                'tablo' => 'masalar',
                'alanlar' => ['ad' => 'ad', 'sekil' => 'sekil', 'x' => 'x', 'y' => 'y', 'durum' => 'durum', 'bolge_id' => 'bolge_sid'],
                'ref' => [],
                'zaman' => [],
                'sube' => true,
            ],
            'menu_kategorileri' => [
                'tablo' => 'menu_kategorileri',
                'alanlar' => ['ad' => 'ad', 'sira' => 'sira', 'aktif' => 'aktif'],
                'ref' => [],
                'zaman' => [],
                'sube' => true,
            ],
            'urunler' => [
                'tablo' => 'urunler',
                'alanlar' => ['kategori_id' => 'kategori_sid', 'ad' => 'ad', 'aciklama' => 'aciklama', 'fiyat' => 'fiyat', 'stok_takipli' => 'stok_takipli', 'tukendi' => 'tukendi', 'aktif' => 'aktif', 'gorsel' => 'gorsel'],
                'ref' => [],
                'zaman' => [],
                'sube' => true,
            ],
            'adisyonlar' => [
                'tablo' => 'adisyonlar',
                'alanlar' => ['kanal' => 'kanal', 'durum' => 'durum', 'acan_personel_id' => 'acan_personel_id', 'ara_toplam' => 'ara_toplam', 'indirim' => 'indirim', 'ikram' => 'ikram', 'toplam' => 'toplam'],
                'ref' => ['masa_id' => ['masa_uuid', 'masalar']],
                'zaman' => ['acilis', 'kapanis'],
                'sube' => true,
            ],
            'adisyon_kalemleri' => [
                'tablo' => 'adisyon_kalemleri',
                'alanlar' => ['urun_id' => 'urun_sid', 'urun_adi' => 'urun_adi', 'adet' => 'adet', 'birim_fiyat' => 'birim_fiyat', 'tutar' => 'tutar', 'durum' => 'durum', 'kur' => 'kur', 'not' => 'not_metni', 'personel_id' => 'personel_id'],
                'ref' => ['adisyon_id' => ['adisyon_uuid', 'adisyonlar']],
                'zaman' => ['gonderim_zamani'],
                'sube' => false,
            ],
            'odemeler' => [
                'tablo' => 'odemeler',
                'alanlar' => ['tip' => 'tip', 'tutar' => 'tutar', 'bahsis' => 'bahsis', 'personel_id' => 'personel_id'],
                'ref' => ['adisyon_id' => ['adisyon_uuid', 'adisyonlar']],
                'zaman' => ['created_at'],
                'sube' => false,
            ],
        ];
    }
}

// Senkron kolonlari: her tabloya uuid (+ updated_at zaten var) + idempotency log tablosu.
if (!function_exists('_syncKolonEnsure')) {
    function _syncKolonEnsure(): void
    {
        // PERFORMANS: sema introspection pahali -> her istekte degil, saatte bir calistir.
        // (Kolonlar zaten migration ile olusuyor; bu yalnizca guvenlik agi.)
        if (Cache::get('sync_ensured_v1')) return;
        foreach (_syncEntities() as $cfg) {
            $t = $cfg['tablo'];
            if (!Schema::hasTable($t)) continue;
            if (!Schema::hasColumn($t, 'uuid')) {
                try {
                    Schema::table($t, function ($tbl) {
                        $tbl->string('uuid', 40)->nullable()->unique();
                    });
                } catch (\Throwable $e) {
                }
            }
            if (!Schema::hasColumn($t, 'updated_at')) {
                try {
                    Schema::table($t, function ($tbl) {
                        $tbl->timestamp('updated_at')->nullable();
                    });
                } catch (\Throwable $e) {
                }
            }
        }
        if (!Schema::hasTable('sync_idem')) {
            try {
                Schema::create('sync_idem', function ($t) {
                    $t->increments('id');
                    $t->string('idem_key', 80)->unique();
                    $t->string('entity', 40)->nullable();
                    $t->string('row_uuid', 40)->nullable();
                    $t->unsignedBigInteger('server_id')->nullable();
                    $t->timestamp('created_at')->nullable();
                });
            } catch (\Throwable $e) {
            }
        }
        // PERFORMANS: pull sorgusu updated_at uzerinden filtreliyor -> index sart.
        foreach (_syncEntities() as $cfg) {
            $t = $cfg['tablo'];
            if (!Schema::hasTable($t) || !Schema::hasColumn($t, 'updated_at')) continue;
            try {
                DB::statement("CREATE INDEX ix_{$t}_updated ON `$t` (updated_at)");
            } catch (\Throwable $e) {
                // zaten varsa yok say
            }
        }
        Cache::put('sync_ensured_v1', 1, 3600); // 1 saat: bir daha sema'ya dokunma
    }
}

// Backend-kaynakli (web panelden girilmis) satirlara uuid uret: senkronda referanslanabilsin.
if (!function_exists('_syncUuidBackfill')) {
    function _syncUuidBackfill(string $tablo, int $subeId): void
    {
        if (!Schema::hasColumn($tablo, 'uuid')) return;
        $q = DB::table($tablo)->whereNull('uuid');
        if (Schema::hasColumn($tablo, 'sube_id')) $q->where('sube_id', $subeId);
        foreach ($q->limit(5000)->get(['id']) as $row) {
            DB::table($tablo)->where('id', $row->id)->update(['uuid' => 'srv_' . $tablo . '_' . $row->id]);
        }
        // KRİTİK: updated_at NULL ise pull "WHERE updated_at > X" ile bu satırı ATLAR -> offline'a HİÇ inmez.
        // (Eski/demo/web-panelden açılan açık adisyonlar damgasız kalınca telefonda masa BOŞ görünüyordu.)
        // Damgayı created_at'e (yoksa now) eşitle: yaşını koru, ama senkrona görünür olsun.
        if (Schema::hasColumn($tablo, 'updated_at')) {
            $u = DB::table($tablo)->whereNull('updated_at');
            if (Schema::hasColumn($tablo, 'sube_id')) $u->where('sube_id', $subeId);
            if (Schema::hasColumn($tablo, 'created_at')) {
                $u->update(['updated_at' => DB::raw('COALESCE(created_at, NOW())')]);
            } else {
                $u->update(['updated_at' => now()]);
            }
        }
    }
}

// ms(epoch) -> 'Y-m-d H:i:s' (null guvenli)
if (!function_exists('_syncMsToDt')) {
    function _syncMsToDt($ms)
    {
        if ($ms === null || $ms === '') return null;
        return date('Y-m-d H:i:s', (int) ((int) $ms / 1000));
    }
}

// Tek bir op'u uygula; server_id doner.
if (!function_exists('_syncUpsert')) {
    function _syncUpsert(array $cfg, string $op, string $uuid, array $payload, int $subeId): ?int
    {
        $t = $cfg['tablo'];
        $veri = [];
        foreach ($cfg['alanlar'] as $backendCol => $localKey) {
            if (array_key_exists($localKey, $payload)) $veri[$backendCol] = $payload[$localKey];
        }
        // uuid referanslarini id'ye cevir (ust kayit ayni batch'te daha once islenmis olur)
        foreach ($cfg['ref'] as $backendCol => [$localUuidKey, $refTablo]) {
            $refUuid = $payload[$localUuidKey] ?? null;
            if ($refUuid) {
                $rid = DB::table($refTablo)->where('uuid', $refUuid)->value('id');
                if ($rid) $veri[$backendCol] = $rid;
            }
        }
        // zaman alanlari ms -> datetime
        foreach ($cfg['zaman'] as $zcol) {
            if (array_key_exists($zcol, $payload)) $veri[$zcol] = _syncMsToDt($payload[$zcol]);
        }
        if (!empty($cfg['sube']) && Schema::hasColumn($t, 'sube_id')) $veri['sube_id'] = $subeId;
        if (Schema::hasColumn($t, 'updated_at')) $veri['updated_at'] = now();

        $mevcut = DB::table($t)->where('uuid', $uuid)->first();
        if ($mevcut) {
            if ($op === 'delete') {
                // Backend'de genel 'deleted' kolonu yok; iptal edilebilir kayitlar durum ile isaretlenir.
                if (Schema::hasColumn($t, 'durum')) DB::table($t)->where('id', $mevcut->id)->update(['durum' => 'iptal', 'updated_at' => now()]);
                return (int) $mevcut->id;
            }
            DB::table($t)->where('id', $mevcut->id)->update($veri);
            return (int) $mevcut->id;
        }
        if ($op === 'delete') return null; // yok zaten
        $veri['uuid'] = $uuid;
        if (Schema::hasColumn($t, 'created_at') && !isset($veri['created_at'])) $veri['created_at'] = now();
        return (int) DB::table($t)->insertGetId($veri);
    }
}

// ===========================================================================
// PUSH: outbox batch -> bulut. Idempotent. acks (uuid -> server_id) doner.
// ===========================================================================
Route::post('/api/sync/push', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _syncKolonEnsure();
    $subeId = (int) $p->sube_id;
    $ops = $r->input('ops', []);
    if (!is_array($ops)) return ['ok' => 0, 'hata' => 'ops dizi olmali'];
    $entities = _syncEntities();
    $acks = [];

    foreach ($ops as $o) {
        $entity = (string) ($o['entity'] ?? '');
        $op = (string) ($o['op'] ?? '');
        $uuid = (string) ($o['row_uuid'] ?? ($o['uuid'] ?? ''));
        $idem = (string) ($o['idem_key'] ?? '');
        $payload = $o['payload'] ?? [];
        if (is_string($payload)) $payload = json_decode($payload, true) ?: [];
        if (!isset($entities[$entity]) || $uuid === '' || !in_array($op, ['insert', 'update', 'delete'], true)) continue;

        // Idempotency: daha once uygulandiysa sadece mevcut eslemeyi dondur
        if ($idem !== '') {
            $onceki = DB::table('sync_idem')->where('idem_key', $idem)->first();
            if ($onceki) {
                $acks[] = ['uuid' => $uuid, 'server_id' => $onceki->server_id ? (int) $onceki->server_id : null, 'entity' => $entity, 'idem' => $idem];
                continue;
            }
        }
        try {
            $sid = _syncUpsert($entities[$entity], $op, $uuid, (array) $payload, $subeId);
            if ($idem !== '') {
                DB::table('sync_idem')->insert(['idem_key' => $idem, 'entity' => $entity, 'row_uuid' => $uuid, 'server_id' => $sid, 'created_at' => now()]);
            }
            $acks[] = ['uuid' => $uuid, 'server_id' => $sid, 'entity' => $entity, 'idem' => $idem];
        } catch (\Throwable $e) {
            $acks[] = ['uuid' => $uuid, 'server_id' => null, 'entity' => $entity, 'idem' => $idem, 'hata' => mb_substr($e->getMessage(), 0, 160)];
        }
    }

    return ['ok' => 1, 'acks' => $acks, 'server_zaman' => (int) round(microtime(true) * 1000)];
});

// ===========================================================================
// PULL: cursor(since ms)'dan beri degisen satirlar. Entity bazli, sube kapsamli.
// ===========================================================================
Route::get('/api/sync/pull', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    _syncKolonEnsure();
    $subeId = (int) $p->sube_id;
    $since = (int) $r->query('since', 0);           // ms
    $sinceDt = $since > 0 ? _syncMsToDt($since) : '1970-01-01 00:00:00';
    $istenen = array_filter(array_map('trim', explode(',', (string) $r->query('entities', ''))));
    $entities = _syncEntities();
    $changes = [];
    $maxMs = $since;

    // Gecmisli tablolar: telefonda sadece son N gun tutulur (DB sismesin). Ana veri (masa/menu) tam.
    $gecmisGun = 7;
    $gecmisEsik = date('Y-m-d H:i:s', time() - $gecmisGun * 86400);
    $gecmisliler = ['adisyonlar', 'adisyon_kalemleri', 'odemeler'];

    foreach ($entities as $ad => $cfg) {
        if ($istenen && !in_array($ad, $istenen, true)) continue;
        $t = $cfg['tablo'];
        if (!Schema::hasTable($t)) continue;
        _syncUuidBackfill($t, $subeId);

        $q = DB::table($t);
        // Sipariş/ödeme gecmisini son N gunle sinirla -> ilk senkron yillarca veri cekmez.
        // AMA açık adisyonlar YAŞI NE OLURSA OLSUN gelmeli (offline'da masa dolu görünsün).
        if (in_array($ad, $gecmisliler, true) && Schema::hasColumn($t, 'updated_at')) {
            if ($ad === 'adisyonlar') {
                $q->where(function ($w) use ($gecmisEsik) {
                    $w->where('updated_at', '>', $gecmisEsik)->orWhere('durum', 'acik');
                });
            } elseif (Schema::hasColumn($t, 'adisyon_id')) {
                // Kalem/ödeme: son 7 gün VEYA açık bir adisyona ait olanlar (açık masanın kalemleri eksik kalmasın).
                $acikIdler = DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'acik')->pluck('id')->all();
                $q->where(function ($w) use ($gecmisEsik, $acikIdler) {
                    $w->where('updated_at', '>', $gecmisEsik);
                    if ($acikIdler) $w->orWhereIn('adisyon_id', $acikIdler);
                });
            } else {
                $q->where('updated_at', '>', $gecmisEsik);
            }
        }
        // sube kapsami: dogrudan sube_id varsa onunla; yoksa adisyon uzerinden (kalem/odeme)
        if (Schema::hasColumn($t, 'sube_id')) {
            $q->where('sube_id', $subeId);
        } elseif (Schema::hasColumn($t, 'adisyon_id')) {
            $q->whereIn('adisyon_id', DB::table('adisyonlar')->where('sube_id', $subeId)->pluck('id'));
        }
        if (Schema::hasColumn($t, 'updated_at')) $q->where('updated_at', '>', $sinceDt);
        $rows = $q->limit(2000)->get();

        $liste = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            if (isset($arr['updated_at']) && $arr['updated_at']) {
                $ms = strtotime($arr['updated_at']) * 1000;
                if ($ms > $maxMs) $maxMs = $ms;
                $arr['updated_at'] = $ms;
            }
            $liste[] = $arr;
        }
        if ($liste) $changes[$ad] = $liste;
    }

    return ['ok' => 1, 'cursor' => $maxMs, 'changes' => $changes];
});

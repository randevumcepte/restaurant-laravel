<?php
// ===== GLOBAL KATALOG SAHİPLİĞİ (malzeme / tedarikçi / reçete görünürlük katmanı) =====
// malzemeler, tedarikciler, receteler tablolarında şube kolonu yok → her restoran diğerinin kaydını görüyordu.
// Veri yapısı DEĞİŞMEZ: katalog_sahip (tur, kayit_id, sahip) yalnız "kim görür" bilgisini tutar.
// sahip = 'g'.grup_id (şube bir gruptaysa) | 's'.sube_id. Aynı kaydı birden çok restoran kullanıyorsa birden çok satır (ortak eski kayıt).
// GÜVENLİK: tablo yok / backfill çalışmadı / hata / tek şube → HER ŞEY GÖRÜNÜR (veri asla gizlenmez).
// Sahipsiz kayıt (unutulmuş bir insert noktası) herkese görünür → eski davranış. web.php sonunda require edilir.

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_katalogEnsure')) {
    // tur → tablo
    function _katalogTablo($tur)
    {
        return ['malzeme' => 'malzemeler', 'tedarikci' => 'tedarikciler', 'recete' => 'receteler'][$tur] ?? null;
    }

    // katalog_sahip tablosunu oluşturur. Hazırsa true.
    function _katalogEnsure()
    {
        static $hazir = null;
        if ($hazir !== null) return $hazir;
        try {
            if (!Schema::hasTable('katalog_sahip')) {
                Schema::create('katalog_sahip', function ($t) {
                    $t->id();
                    $t->string('tur', 12);                 // malzeme | tedarikci | recete
                    $t->unsignedBigInteger('kayit_id');
                    $t->string('sahip', 20);               // g{grup_id} | s{sube_id}
                    $t->timestamps();
                    $t->unique(['tur', 'kayit_id', 'sahip']);
                    $t->index(['tur', 'sahip']);
                });
            }
            $hazir = true;
        } catch (\Throwable $e) {
            try { $hazir = Schema::hasTable('katalog_sahip'); } catch (\Throwable $e2) { $hazir = false; }
        }
        return $hazir;
    }

    // Şubenin sahip anahtarı: grubu varsa 'g'.grup_id, yoksa 's'.sube_id
    function _katalogSahip($sid)
    {
        $sid = (int) $sid;
        $g = _katalogGrupId($sid);
        return $g ? 'g' . $g : 's' . $sid;
    }

    function _katalogGrupId($sid)
    {
        static $c = [];
        $sid = (int) $sid;
        if (array_key_exists($sid, $c)) return $c[$sid];
        $g = 0;
        try {
            if (function_exists('_trfGrupId')) $g = (int) _trfGrupId($sid);
            elseif (Schema::hasColumn('subeler', 'grup_id')) $g = (int) DB::table('subeler')->where('id', $sid)->value('grup_id');
        } catch (\Throwable $e) { $g = 0; }
        return $c[$sid] = $g;
    }

    // Görünürlük anahtarları: kendi anahtarı + gruptaysa grup üyelerinin gruba girmeden önceki 's' kayıtları
    function _katalogAnahtarlar($sid)
    {
        static $c = [];
        $sid = (int) $sid;
        if (isset($c[$sid])) return $c[$sid];
        $g = _katalogGrupId($sid);
        if (!$g) return $c[$sid] = ['s' . $sid];
        $a = ['g' . $g, 's' . $sid];
        try {
            foreach (DB::table('subeler')->where('grup_id', $g)->pluck('id') as $x) $a[] = 's' . (int) $x;
        } catch (\Throwable $e) {}
        return $c[$sid] = array_values(array_unique($a));
    }

    // Filtre devrede mi? Tek şube / tablo yok / backfill yok / hata → false (her şey görünür)
    function _katalogAktif()
    {
        static $aktif = null;
        if ($aktif !== null) return $aktif;
        $aktif = false;
        try {
            if ((int) DB::table('subeler')->count() <= 1) return $aktif;
            if (!_katalogEnsure()) return $aktif;
            if (!function_exists('resto_ayar_al')) return $aktif;
            if (!resto_ayar_al('katalog_sahip_v1')) {
                if (!_katalogBackfill()) return $aktif;
            }
            $aktif = true;
        } catch (\Throwable $e) { $aktif = false; }
        return $aktif;
    }

    // Sorguya görünürlük filtresi ekler. Filtre kapalıysa sorguya dokunmaz.
    // $kolon: join'li sorguda tablo nitelikli kolon (örn. 'malzemeler.id', 'm.id')
    function _katalogFiltre($q, $tur, $sid, $kolon = 'id')
    {
        if (!$sid || !_katalogTablo($tur) || !_katalogAktif()) return $q;
        $anahtar = _katalogAnahtarlar($sid);
        return $q->where(function ($w) use ($tur, $anahtar, $kolon) {
            $w->whereIn($kolon, function ($s) use ($tur, $anahtar) {
                $s->select('kayit_id')->from('katalog_sahip')->where('tur', $tur)->whereIn('sahip', $anahtar);
            })->orWhereNotIn($kolon, function ($s) use ($tur) {
                // sahipsiz kayıt herkese görünür (eski davranış)
                $s->select('kayit_id')->from('katalog_sahip')->where('tur', $tur);
            });
        });
    }

    // Şubenin görebildiği kayıt id'leri (istek boyunca önbellekli). Filtre kapalıysa tüm id'ler.
    function _katalogIdler($tur, $sid)
    {
        $tablo = _katalogTablo($tur);
        if (!$tablo) return [];
        $k = $tur . '|' . (int) $sid;
        if (isset($GLOBALS['_katalog_onbellek'][$k])) return $GLOBALS['_katalog_onbellek'][$k];
        try {
            $ids = _katalogFiltre(DB::table($tablo), $tur, (int) $sid)->pluck('id')->map(fn ($x) => (int) $x)->all();
        } catch (\Throwable $e) {
            try { $ids = DB::table($tablo)->pluck('id')->map(fn ($x) => (int) $x)->all(); } catch (\Throwable $e2) { $ids = []; }
        }
        return $GLOBALS['_katalog_onbellek'][$k] = $ids;
    }

    // Yeni kaydı şubeye (grubuna) bağlar. Tek şubede de yazılır (sonradan şube açılınca doğru sahip kalır).
    function _katalogSahiplen($tur, $id, $sid)
    {
        $id = (int) $id; $sid = (int) $sid;
        if ($id <= 0 || $sid <= 0 || !_katalogTablo($tur)) return;
        try {
            if (!_katalogEnsure()) return;
            DB::table('katalog_sahip')->insertOrIgnore(['tur' => $tur, 'kayit_id' => $id, 'sahip' => _katalogSahip($sid), 'created_at' => now(), 'updated_at' => now()]);
            unset($GLOBALS['_katalog_onbellek']);
        } catch (\Throwable $e) {}
    }

    // Kayıt bu şubenin (grubunun) mu? Filtre kapalı / sahipsiz kayıt / hata → true
    function _katalogSahibiMi($tur, $id, $sid)
    {
        $id = (int) $id;
        if ($id <= 0 || !$sid || !_katalogAktif()) return true;
        try {
            $sahipler = DB::table('katalog_sahip')->where('tur', $tur)->where('kayit_id', $id)->pluck('sahip')->all();
            if (!$sahipler) return true;
            return (bool) array_intersect($sahipler, _katalogAnahtarlar($sid));
        } catch (\Throwable $e) { return true; }
    }

    // TEK SEFERLİK BACKFILL: her şubenin KULLANDIĞI katalog kaydını sahiplen; sahipsiz kalanı en küçük şubeye ver.
    // insertOrIgnore → tekrar çalışsa da aynı sonuç. Başarıda resto_ayar 'katalog_sahip_v1' yazılır.
    function _katalogBackfill()
    {
        if (!_katalogEnsure()) return false;
        if (Cache::get('katalog_sahip_hata')) return false;              // son denemede hata → 10 dk bekle
        if (!Cache::add('katalog_sahip_kilit', 1, 300)) return false;    // başka istek çalıştırıyor → bu istek filtresiz
        try {
            $kol = function ($t, $c) { try { return Schema::hasTable($t) && Schema::hasColumn($t, $c); } catch (\Throwable $e) { return false; } };
            $simdi = now();
            $yaz = function ($tur, $satirlar) use ($simdi) {
                // $satirlar: [[kayit_id, sahip], ...]
                $buf = []; $gor = [];
                foreach ($satirlar as $s) {
                    $id = (int) $s[0]; $sh = (string) $s[1];
                    if ($id <= 0 || $sh === '' || isset($gor[$id . '|' . $sh])) continue;
                    $gor[$id . '|' . $sh] = 1;
                    $buf[] = ['tur' => $tur, 'kayit_id' => $id, 'sahip' => $sh, 'created_at' => $simdi, 'updated_at' => $simdi];
                    if (count($buf) >= 400) { DB::table('katalog_sahip')->insertOrIgnore($buf); $buf = []; }
                }
                if ($buf) DB::table('katalog_sahip')->insertOrIgnore($buf);
            };
            // (kayit_id, sube_id) çiftlerini sahip anahtarına çevirip yazar
            $subeYaz = function ($tur, $rows) use ($yaz) {
                $s = [];
                foreach ($rows as $r) { if ((int) $r->sid > 0) $s[] = [(int) $r->kid, _katalogSahip((int) $r->sid)]; }
                $yaz($tur, $s);
            };
            // Adım hatası yutulmaz: yarım backfill yanlış sahip üretebilir → hepsi iptal, bayrak yazılmaz, her şey görünür kalır
            $dene = function ($f) { $f(); };

            // ---- MALZEME: doğrudan kullanım
            foreach ([['stok_hareketleri', 'malzeme_id'], ['stok_partileri', 'malzeme_id'], ['tedarikci_malzeme', 'malzeme_id'], ['sube_malzeme_ayar', 'malzeme_id']] as [$t, $c]) {
                if ($kol($t, $c) && $kol($t, 'sube_id')) $dene(fn () => $subeYaz('malzeme', DB::table($t)->whereNotNull($c)->select($c . ' as kid', 'sube_id as sid')->distinct()->get()));
            }
            // kalem → başlık.sube_id
            foreach ([['alis_fatura_kalemleri', 'fatura_id', 'alis_faturalari'], ['satinalma_siparis_kalemleri', 'siparis_id', 'satinalma_siparisleri'],
                      ['talep_kalemleri', 'talep_id', 'satinalma_talepleri'], ['sayim_kalemleri', 'sayim_id', 'sayimlar']] as [$t, $fk, $bas]) {
                if ($kol($t, 'malzeme_id') && $kol($t, $fk) && $kol($bas, 'sube_id')) $dene(fn () => $subeYaz('malzeme', DB::table($t . ' as k')->join($bas . ' as b', 'b.id', '=', 'k.' . $fk)
                    ->whereNotNull('k.malzeme_id')->select('k.malzeme_id as kid', 'b.sube_id as sid')->distinct()->get()));
            }
            if ($kol('barkodlar', 'hedef_id') && $kol('barkodlar', 'sube_id')) $dene(fn () => $subeYaz('malzeme', DB::table('barkodlar')->where('tur', 'malzeme')->select('hedef_id as kid', 'sube_id as sid')->distinct()->get()));
            if ($kol('sube_transfer_kalemleri', 'malzeme_id') && $kol('sube_transferleri', 'alan_sube_id')) {
                foreach (['gonderen_sube_id', 'alan_sube_id'] as $taraf) {
                    $dene(fn () => $subeYaz('malzeme', DB::table('sube_transfer_kalemleri as k')->join('sube_transferleri as t', 't.id', '=', 'k.transfer_id')
                        ->select('k.malzeme_id as kid', 't.' . $taraf . ' as sid')->distinct()->get()));
                }
            }

            // ---- TEDARİKÇİ
            foreach (['alis_faturalari', 'satinalma_siparisleri', 'tedarikci_malzeme', 'rfq_gonderim', 'mal_kabuller', 'stok_partileri'] as $t) {
                if ($kol($t, 'tedarikci_id') && $kol($t, 'sube_id')) $dene(fn () => $subeYaz('tedarikci', DB::table($t)->whereNotNull('tedarikci_id')->select('tedarikci_id as kid', 'sube_id as sid')->distinct()->get()));
            }
            foreach (['onerilen_tedarikci_id', 'secilen_tedarikci_id'] as $c) {
                if ($kol('talep_kalemleri', $c) && $kol('satinalma_talepleri', 'sube_id')) $dene(fn () => $subeYaz('tedarikci', DB::table('talep_kalemleri as k')->join('satinalma_talepleri as b', 'b.id', '=', 'k.talep_id')
                    ->whereNotNull('k.' . $c)->select('k.' . $c . ' as kid', 'b.sube_id as sid')->distinct()->get()));
            }
            if ($kol('cari_hesaplar', 'tedarikci_id') && $kol('cari_hesaplar', 'sube_id')) $dene(fn () => $subeYaz('tedarikci', DB::table('cari_hesaplar')->whereNotNull('tedarikci_id')->select('tedarikci_id as kid', 'sube_id as sid')->distinct()->get()));
            if ($kol('tedarikciler', 'cari_id') && $kol('cari_hesaplar', 'sube_id')) $dene(fn () => $subeYaz('tedarikci', DB::table('tedarikciler as t')->join('cari_hesaplar as c', 'c.id', '=', 't.cari_id')
                ->select('t.id as kid', 'c.sube_id as sid')->distinct()->get()));

            // ---- REÇETE: ürün reçetesi → urunler.sube_id
            if ($kol('receteler', 'urun_id') && $kol('urunler', 'sube_id')) $dene(fn () => $subeYaz('recete', DB::table('receteler as r')->join('urunler as u', 'u.id', '=', 'r.urun_id')
                ->select('r.id as kid', 'u.sube_id as sid')->distinct()->get()));

            // ---- Yayılım (anahtar uzayında, durağan olana dek, en çok 5 tur):
            // yarı mamül ← ürettiği malzemenin sahipleri; alt reçete ← onu içeren reçetenin sahipleri;
            // malzeme ← sahip olunan reçetenin kalemleri + ürettiği malzeme
            $uretKol = array_values(array_filter(['uretilen_malzeme_id', 'malzeme_id'], fn ($c) => $kol('receteler', $c)));
            $say = fn () => (int) DB::table('katalog_sahip')->count();
            for ($tur = 0; $tur < 5; $tur++) {
                $once = $say();
                foreach ($uretKol as $c) {
                    $dene(fn () => $yaz('recete', DB::table('receteler as r')->join('katalog_sahip as ks', function ($j) use ($c) { $j->on('ks.kayit_id', '=', 'r.' . $c)->where('ks.tur', '=', 'malzeme'); })
                        ->where('r.tip', 'yari_mamul')->select('r.id', 'ks.sahip')->distinct()->get()->map(fn ($x) => [$x->id, $x->sahip])->all()));
                    $dene(fn () => $yaz('malzeme', DB::table('receteler as r')->join('katalog_sahip as ks', function ($j) { $j->on('ks.kayit_id', '=', 'r.id')->where('ks.tur', '=', 'recete'); })
                        ->whereNotNull('r.' . $c)->select('r.' . $c . ' as mid', 'ks.sahip')->distinct()->get()->map(fn ($x) => [$x->mid, $x->sahip])->all()));
                }
                if ($kol('recete_kalemleri', 'alt_recete_id')) $dene(fn () => $yaz('recete', DB::table('recete_kalemleri as k')->join('katalog_sahip as ks', function ($j) { $j->on('ks.kayit_id', '=', 'k.recete_id')->where('ks.tur', '=', 'recete'); })
                    ->whereNotNull('k.alt_recete_id')->select('k.alt_recete_id as rid', 'ks.sahip')->distinct()->get()->map(fn ($x) => [$x->rid, $x->sahip])->all()));
                $dene(fn () => $yaz('malzeme', DB::table('recete_kalemleri as k')->join('katalog_sahip as ks', function ($j) { $j->on('ks.kayit_id', '=', 'k.recete_id')->where('ks.tur', '=', 'recete'); })
                    ->whereNotNull('k.malzeme_id')->select('k.malzeme_id as mid', 'ks.sahip')->distinct()->get()->map(fn ($x) => [$x->mid, $x->sahip])->all()));
                if ($say() === $once) break;
            }

            // ---- Sahipsiz kalan her kayıt → en küçük şube (asıl/ilk restoran; tek restoran davranışı aynen sürer)
            $ilk = (int) DB::table('subeler')->min('id');
            if ($ilk > 0) {
                $anah = _katalogSahip($ilk);
                foreach (['malzeme', 'tedarikci', 'recete'] as $tur) {
                    $tablo = _katalogTablo($tur);
                    if (!Schema::hasTable($tablo)) continue;
                    $yetim = DB::table($tablo)->whereNotIn('id', function ($s) use ($tur) { $s->select('kayit_id')->from('katalog_sahip')->where('tur', $tur); })->pluck('id');
                    $yaz($tur, $yetim->map(fn ($id) => [$id, $anah])->all());
                }
            }

            resto_ayar_yaz('katalog_sahip_v1', $simdi->toDateTimeString());
            unset($GLOBALS['_katalog_onbellek']);
            return true;
        } catch (\Throwable $e) {
            Log::error('katalog_sahip backfill: ' . $e->getMessage());
            Cache::put('katalog_sahip_hata', 1, 600);
            return false;
        } finally {
            Cache::forget('katalog_sahip_kilit');
        }
    }
}

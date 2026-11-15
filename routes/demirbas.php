<?php
// ============================================================================
// DEMİRBAŞ & ETİKET: ekipman/demirbaş kartı + otomatik barkod + etiket basımı (tarayıcı / ZPL / TSPL)
// + okut-bul + zimmet/konum geçmişi + demirbaş sayımı. Web panel ve uygulama (API) aynı fonksiyonları kullanır.
// Vergi amortismanı DEĞİL (o /amortisman). Malzeme/ürün barkod etiketleri de buradaki motordan basılır.
// ============================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_demirbasEnsure')) {
    function _demirbasEnsure()
    {
        if (cache()->get('demirbas_ensure_ok2')) return;
        try {
            if (!Schema::hasTable('demirbaslar')) {
                Schema::create('demirbaslar', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index();
                    $t->string('ad', 150); $t->string('kod', 50)->nullable(); $t->string('kategori', 60)->nullable();
                    $t->date('alis_tarih')->nullable(); $t->decimal('alis_bedel', 14, 2)->default(0);
                    $t->string('lokasyon', 100)->nullable(); $t->unsignedBigInteger('zimmet_personel_id')->nullable();
                    $t->string('durum', 20)->default('aktif'); $t->text('not')->nullable();
                    $t->timestamps();
                });
            }
            $ek = ['barkod' => fn ($t) => $t->string('barkod', 64)->nullable()->index(), 'marka' => fn ($t) => $t->string('marka', 80)->nullable(),
                'model' => fn ($t) => $t->string('model', 80)->nullable(), 'seri_no' => fn ($t) => $t->string('seri_no', 80)->nullable(),
                'tedarikci' => fn ($t) => $t->string('tedarikci', 120)->nullable(), 'garanti_bitis' => fn ($t) => $t->date('garanti_bitis')->nullable(),
                'adet' => fn ($t) => $t->unsignedInteger('adet')->default(1), 'son_sayim' => fn ($t) => $t->dateTime('son_sayim')->nullable()];
            foreach ($ek as $k => $fn) if (!Schema::hasColumn('demirbaslar', $k)) Schema::table('demirbaslar', $fn);
            if (!Schema::hasTable('demirbas_bakim')) {
                Schema::create('demirbas_bakim', function ($t) {
                    $t->id(); $t->unsignedBigInteger('demirbas_id')->index();
                    $t->date('tarih'); $t->string('tip', 20)->default('bakim'); // bakim/ariza
                    $t->text('aciklama')->nullable(); $t->decimal('maliyet', 12, 2)->default(0);
                    $t->timestamps();
                });
            }
            if (!Schema::hasTable('demirbas_hareket')) {
                Schema::create('demirbas_hareket', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('demirbas_id')->index();
                    $t->string('tip', 20); // olustur/duzenle/zimmet/lokasyon/durum/bakim/sayim/etiket
                    $t->string('eski', 150)->nullable(); $t->string('yeni', 150)->nullable(); $t->string('aciklama', 255)->nullable();
                    $t->unsignedBigInteger('personel_id')->nullable(); $t->timestamp('created_at')->nullable();
                });
            }
            if (!Schema::hasTable('demirbas_sayim')) {
                Schema::create('demirbas_sayim', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->string('lokasyon', 100)->nullable();
                    $t->string('durum', 10)->default('acik'); $t->unsignedBigInteger('baslatan_id')->nullable(); $t->unsignedBigInteger('bitiren_id')->nullable();
                    $t->dateTime('baslangic')->nullable(); $t->dateTime('bitis')->nullable(); $t->text('ozet')->nullable(); $t->timestamps();
                });
            }
            if (!Schema::hasTable('demirbas_sayim_satir')) {
                Schema::create('demirbas_sayim_satir', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sayim_id')->index(); $t->unsignedBigInteger('demirbas_id')->nullable();
                    $t->string('kod', 64); $t->string('sonuc', 15); // bulundu/yanlis_yer/bilinmeyen
                    $t->string('lokasyon', 100)->nullable(); $t->unsignedBigInteger('personel_id')->nullable(); $t->timestamp('created_at')->nullable();
                });
            }
            if (!Schema::hasTable('etiket_ayarlari')) {
                Schema::create('etiket_ayarlari', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->unique();
                    $t->string('dil', 10)->default('tarayici'); // tarayici / zpl / tspl / escpos
                    $t->string('boyut', 12)->default('50x30'); $t->unsignedSmallInteger('dpi')->default(203);
                    $t->string('ip', 60)->nullable(); $t->unsignedSmallInteger('port')->default(9100); $t->timestamps();
                });
            }
            cache()->put('demirbas_ensure_ok2', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('_dmYetki')) {
    // 'duzenle' | 'gor' | null. Patron/müdür her şeyi; diğerleri yetki ekranındaki kutucuklarla (varsayılan kapalı).
    function _dmYetki($p)
    {
        if (!$p) return null;
        $rol = mb_strtolower((string) ($p->rol ?? ''));
        if (in_array($rol, ['sahip', 'mudur', 'müdür'], true)) return 'duzenle';
        if (function_exists('_kasiyerMi') && _kasiyerMi($p)) return null;
        $var = fn ($k) => function_exists('_panelYetkiVar') ? _panelYetkiVar($p, $k) : false;
        if ($var('gor_demirbas_duzenle')) return 'duzenle';
        return $var('gor_demirbas') ? 'gor' : null;
    }
    function _dmDurumlar() { return ['aktif' => 'Kullanımda', 'bakimda' => 'Bakımda', 'arizali' => 'Arızalı', 'kayip' => 'Kayıp', 'hurda' => 'Hurda']; }
    function _dmHareket($sid, $dId, $tip, $eski, $yeni, $acik = null, $pid = null)
    {
        try { DB::table('demirbas_hareket')->insert(['sube_id' => $sid, 'demirbas_id' => $dId, 'tip' => $tip, 'eski' => $eski !== null ? mb_substr((string) $eski, 0, 150) : null,
            'yeni' => $yeni !== null ? mb_substr((string) $yeni, 0, 150) : null, 'aciklama' => $acik ? mb_substr((string) $acik, 0, 255) : null, 'personel_id' => $pid, 'created_at' => now()]); } catch (\Throwable $e) {}
    }
    // Otomatik barkod: DM + 6 hane kayıt no (tüm şubelerde tekil). Elle girilmiş barkod varsa dokunmaz.
    function _dmBarkodVer($d)
    {
        if (!empty($d->barkod)) return $d->barkod;
        $kod = 'DM' . str_pad((string) $d->id, 6, '0', STR_PAD_LEFT);
        DB::table('demirbaslar')->where('id', $d->id)->update(['barkod' => $kod]);
        $d->barkod = $kod;
        return $kod;
    }
    // Barkod aynı şubede başka demirbaşta ya da ürün/malzemede kullanılıyor mu?
    function _dmBarkodCakisma($sid, $kod, $haric = 0)
    {
        if ($kod === '') return null;
        $d = DB::table('demirbaslar')->where('sube_id', $sid)->where('barkod', $kod)->where('id', '!=', $haric)->value('ad');
        if ($d) return 'Bu barkod "' . $d . '" demirbaşında kullanılıyor.';
        if (Schema::hasTable('barkodlar') && DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $kod)->exists()) return 'Bu barkod bir ürün/malzemeye tanımlı.';
        return null;
    }
    function _dmBul($sid, $kod)
    {
        $kod = trim((string) $kod);
        if ($kod === '') return null;
        $d = DB::table('demirbaslar')->where('sube_id', $sid)->where('barkod', $kod)->first();
        if (!$d) $d = DB::table('demirbaslar')->where('sube_id', $sid)->where(fn ($q) => $q->where('kod', $kod)->orWhere('seri_no', $kod))->first();
        if (!$d && preg_match('/^DM0*(\d+)$/i', $kod, $m)) $d = DB::table('demirbaslar')->where('sube_id', $sid)->where('id', (int) $m[1])->first();
        return $d;
    }
    function _dmPersonelAd($sid) { return DB::table('personeller')->where('sube_id', $sid)->pluck('ad', 'id'); }
    function _dmSatir($d, $persAd)
    {
        return ['id' => (int) $d->id, 'ad' => $d->ad, 'barkod' => $d->barkod, 'kod' => $d->kod, 'kategori' => $d->kategori, 'lokasyon' => $d->lokasyon,
            'zimmet_personel_id' => $d->zimmet_personel_id ? (int) $d->zimmet_personel_id : null, 'zimmet_ad' => $d->zimmet_personel_id ? ($persAd[$d->zimmet_personel_id] ?? null) : null,
            'durum' => $d->durum, 'durum_ad' => _dmDurumlar()[$d->durum] ?? $d->durum, 'marka' => $d->marka ?? null, 'model' => $d->model ?? null, 'seri_no' => $d->seri_no ?? null,
            'tedarikci' => $d->tedarikci ?? null, 'alis_tarih' => $d->alis_tarih, 'alis_bedel' => (float) $d->alis_bedel, 'garanti_bitis' => $d->garanti_bitis ?? null,
            'adet' => (int) ($d->adet ?? 1), 'son_sayim' => $d->son_sayim ?? null, 'not' => $d->not];
    }

    // Kaydet (yeni ya da düzenle). Web ve API ortak. Dönüş: [ok, mesaj, id]
    function _dmKaydet($sid, $p, array $v)
    {
        $id = (int) ($v['id'] ?? 0);
        $ad = trim((string) ($v['ad'] ?? ''));
        if ($ad === '') return [false, 'Ad zorunlu.', 0];
        $pid = (int) ($v['zimmet_personel_id'] ?? 0) ?: null;
        if ($pid && !DB::table('personeller')->where('id', $pid)->where('sube_id', $sid)->exists()) return [false, 'Zimmet personeli bulunamadı.', 0];
        $barkod = trim((string) ($v['barkod'] ?? ''));
        if ($barkod !== '' && !preg_match('/^[\x21-\x7E]{1,64}$/', $barkod)) return [false, 'Barkod yalnız harf, rakam ve işaret içerebilir (Türkçe karakter ve boşluk olmaz).', 0];
        if ($m = _dmBarkodCakisma($sid, $barkod, $id)) return [false, $m, 0];
        $tarih = fn ($k) => !empty($v[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v[$k]) ? $v[$k] : null;
        $s = fn ($k, $n) => ($x = trim((string) ($v[$k] ?? ''))) !== '' ? mb_substr($x, 0, $n) : null;
        $durum = (string) ($v['durum'] ?? 'aktif');
        if (!isset(_dmDurumlar()[$durum])) $durum = 'aktif';
        $veri = ['ad' => mb_substr($ad, 0, 150), 'kod' => $s('kod', 50), 'kategori' => $s('kategori', 60), 'lokasyon' => $s('lokasyon', 100),
            'marka' => $s('marka', 80), 'model' => $s('model', 80), 'seri_no' => $s('seri_no', 80), 'tedarikci' => $s('tedarikci', 120),
            'alis_tarih' => $tarih('alis_tarih'), 'garanti_bitis' => $tarih('garanti_bitis'), 'alis_bedel' => (float) str_replace(',', '.', (string) ($v['alis_bedel'] ?? 0)),
            'adet' => max(1, (int) ($v['adet'] ?? 1)), 'zimmet_personel_id' => $pid, 'durum' => $durum, 'not' => $s('not', 2000), 'updated_at' => now()];
        if ($barkod !== '') $veri['barkod'] = $barkod;
        $persAd = _dmPersonelAd($sid);
        if ($id) {
            $esk = DB::table('demirbaslar')->where('sube_id', $sid)->where('id', $id)->first();
            if (!$esk) return [false, 'Demirbaş bulunamadı.', 0];
            if ($barkod === '' && !empty($v['barkod_sifirla'])) $veri['barkod'] = null;
            DB::table('demirbaslar')->where('id', $id)->update($veri);
            if ((int) $esk->zimmet_personel_id !== (int) $pid) _dmHareket($sid, $id, 'zimmet', $persAd[$esk->zimmet_personel_id] ?? null, $persAd[$pid] ?? null, null, $p->id ?? null);
            if ((string) $esk->lokasyon !== (string) $veri['lokasyon']) _dmHareket($sid, $id, 'lokasyon', $esk->lokasyon, $veri['lokasyon'], null, $p->id ?? null);
            if ($esk->durum !== $durum) _dmHareket($sid, $id, 'durum', _dmDurumlar()[$esk->durum] ?? $esk->durum, _dmDurumlar()[$durum], null, $p->id ?? null);
            _dmHareket($sid, $id, 'duzenle', null, null, 'Kart bilgileri güncellendi', $p->id ?? null);
        } else {
            $id = DB::table('demirbaslar')->insertGetId($veri + ['sube_id' => $sid, 'created_at' => now()]);
            _dmHareket($sid, $id, 'olustur', null, $veri['lokasyon'], 'Kayıt açıldı', $p->id ?? null);
            if ($pid) _dmHareket($sid, $id, 'zimmet', null, $persAd[$pid] ?? null, null, $p->id ?? null);
        }
        _dmBarkodVer(DB::table('demirbaslar')->where('id', $id)->first());
        return [true, 'Kaydedildi.', $id];
    }

    // Tek alan işlemi: zimmet / lokasyon / durum / bakim / sil. Dönüş: [ok, mesaj]
    function _dmIslem($sid, $p, array $v)
    {
        $id = (int) ($v['id'] ?? 0);
        $d = DB::table('demirbaslar')->where('sube_id', $sid)->where('id', $id)->first();
        if (!$d) return [false, 'Demirbaş bulunamadı.'];
        $islem = (string) ($v['islem'] ?? '');
        $pid = $p->id ?? null;
        $persAd = _dmPersonelAd($sid);
        if ($islem === 'zimmet') {
            $yeni = (int) ($v['zimmet_personel_id'] ?? 0) ?: null;
            if ($yeni && !isset($persAd[$yeni])) return [false, 'Personel bulunamadı.'];
            DB::table('demirbaslar')->where('id', $id)->update(['zimmet_personel_id' => $yeni, 'updated_at' => now()]);
            _dmHareket($sid, $id, 'zimmet', $persAd[$d->zimmet_personel_id] ?? null, $persAd[$yeni] ?? null, trim((string) ($v['aciklama'] ?? '')) ?: null, $pid);
            return [true, $yeni ? 'Zimmet ' . $persAd[$yeni] . ' üzerine alındı.' : 'Zimmet kaldırıldı.'];
        }
        if ($islem === 'lokasyon') {
            $yeni = mb_substr(trim((string) ($v['lokasyon'] ?? '')), 0, 100) ?: null;
            DB::table('demirbaslar')->where('id', $id)->update(['lokasyon' => $yeni, 'updated_at' => now()]);
            _dmHareket($sid, $id, 'lokasyon', $d->lokasyon, $yeni, trim((string) ($v['aciklama'] ?? '')) ?: null, $pid);
            return [true, 'Konum güncellendi.'];
        }
        if ($islem === 'durum') {
            $yeni = (string) ($v['durum'] ?? '');
            if (!isset(_dmDurumlar()[$yeni])) return [false, 'Geçersiz durum.'];
            DB::table('demirbaslar')->where('id', $id)->update(['durum' => $yeni, 'updated_at' => now()]);
            _dmHareket($sid, $id, 'durum', _dmDurumlar()[$d->durum] ?? $d->durum, _dmDurumlar()[$yeni], trim((string) ($v['aciklama'] ?? '')) ?: null, $pid);
            return [true, 'Durum: ' . _dmDurumlar()[$yeni]];
        }
        if ($islem === 'bakim') {
            $tip = ($v['tip'] ?? '') === 'ariza' ? 'ariza' : 'bakim';
            $tarih = !empty($v['tarih']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v['tarih']) ? $v['tarih'] : today()->toDateString();
            $acik = trim((string) ($v['aciklama'] ?? '')) ?: null;
            DB::table('demirbas_bakim')->insert(['demirbas_id' => $id, 'tarih' => $tarih, 'tip' => $tip, 'aciklama' => $acik,
                'maliyet' => (float) str_replace(',', '.', (string) ($v['maliyet'] ?? 0)), 'created_at' => now(), 'updated_at' => now()]);
            _dmHareket($sid, $id, 'bakim', null, $tip === 'ariza' ? 'Arıza' : 'Bakım', $acik, $pid);
            return [true, ($tip === 'ariza' ? 'Arıza' : 'Bakım') . ' kaydedildi.'];
        }
        if ($islem === 'sil') {
            DB::table('demirbaslar')->where('id', $id)->delete();
            DB::table('demirbas_bakim')->where('demirbas_id', $id)->delete();
            DB::table('demirbas_hareket')->where('demirbas_id', $id)->delete();
            return [true, $d->ad . ' silindi.'];
        }
        return [false, 'Bilinmeyen işlem.'];
    }

    function _dmListe($sid)
    {
        $liste = DB::table('demirbaslar')->where('sube_id', $sid)->orderBy('ad')->get();
        foreach ($liste as $d) if (empty($d->barkod)) _dmBarkodVer($d); // eski kayıtlara barkod
        return $liste;
    }
    function _dmOzet($liste)
    {
        $kul = $liste->where('durum', '!=', 'hurda');
        $gb = today()->addDays(30)->toDateString(); $bug = today()->toDateString();
        return ['adet' => $kul->count(), 'deger' => (float) $kul->sum(fn ($d) => (float) $d->alis_bedel * max(1, (int) ($d->adet ?? 1))),
            'zimmetli' => $kul->whereNotNull('zimmet_personel_id')->count(),
            'sorunlu' => $liste->whereIn('durum', ['bakimda', 'arizali', 'kayip'])->count(),
            'garanti' => $kul->filter(fn ($d) => !empty($d->garanti_bitis) && $d->garanti_bitis >= $bug && $d->garanti_bitis <= $gb)->count(),
            'sayilmamis' => $kul->filter(fn ($d) => empty($d->son_sayim) || $d->son_sayim < now()->subDays(90))->count()];
    }

    // ----- SAYIM -----
    function _dmSayimAktif($sid) { return DB::table('demirbas_sayim')->where('sube_id', $sid)->where('durum', 'acik')->orderByDesc('id')->first(); }
    function _dmSayimOkut($sid, $p, $sayim, $kod, $lokasyon = null)
    {
        $kod = trim((string) $kod);
        if ($kod === '') return ['ok' => 0, 'hata' => 'Boş kod'];
        $d = _dmBul($sid, $kod);
        $lok = trim((string) $lokasyon) ?: $sayim->lokasyon;
        if (!$d) {
            DB::table('demirbas_sayim_satir')->insert(['sayim_id' => $sayim->id, 'demirbas_id' => null, 'kod' => mb_substr($kod, 0, 64), 'sonuc' => 'bilinmeyen', 'lokasyon' => $lok, 'personel_id' => $p->id ?? null, 'created_at' => now()]);
            return ['ok' => 1, 'sonuc' => 'bilinmeyen', 'kod' => $kod, 'mesaj' => 'Tanımsız barkod: ' . $kod];
        }
        if (DB::table('demirbas_sayim_satir')->where('sayim_id', $sayim->id)->where('demirbas_id', $d->id)->exists())
            return ['ok' => 1, 'sonuc' => 'tekrar', 'id' => (int) $d->id, 'ad' => $d->ad, 'mesaj' => $d->ad . ' zaten sayıldı'];
        $yanlis = $lok && $d->lokasyon && mb_strtolower(trim($d->lokasyon)) !== mb_strtolower($lok);
        DB::table('demirbas_sayim_satir')->insert(['sayim_id' => $sayim->id, 'demirbas_id' => $d->id, 'kod' => mb_substr($kod, 0, 64), 'sonuc' => $yanlis ? 'yanlis_yer' : 'bulundu', 'lokasyon' => $lok, 'personel_id' => $p->id ?? null, 'created_at' => now()]);
        DB::table('demirbaslar')->where('id', $d->id)->update(['son_sayim' => now()]);
        return ['ok' => 1, 'sonuc' => $yanlis ? 'yanlis_yer' : 'bulundu', 'id' => (int) $d->id, 'ad' => $d->ad, 'kayitli_lokasyon' => $d->lokasyon, 'lokasyon' => $lok,
            'mesaj' => $yanlis ? $d->ad . ' — kayıtta "' . $d->lokasyon . '", burada bulundu' : $d->ad . ' ✓'];
    }
    function _dmSayimRapor($sid, $sayim)
    {
        $satir = DB::table('demirbas_sayim_satir')->where('sayim_id', $sayim->id)->orderByDesc('id')->get();
        $q = DB::table('demirbaslar')->where('sube_id', $sid)->whereNotIn('durum', ['hurda']);
        if ($sayim->lokasyon) $q->whereRaw('LOWER(TRIM(lokasyon)) = ?', [mb_strtolower(trim($sayim->lokasyon))]);
        $kapsam = $q->orderBy('ad')->get(['id', 'ad', 'barkod', 'lokasyon', 'zimmet_personel_id', 'durum']);
        $sayilan = $satir->whereNotNull('demirbas_id')->pluck('demirbas_id')->map(fn ($x) => (int) $x)->all();
        $ad = DB::table('demirbaslar')->whereIn('id', $sayilan ?: [0])->pluck('ad', 'id');
        return ['bulundu' => $satir->where('sonuc', 'bulundu')->map(fn ($s) => ['id' => (int) $s->demirbas_id, 'ad' => $ad[$s->demirbas_id] ?? '—', 'lokasyon' => $s->lokasyon])->values()->all(),
            'yanlis_yer' => $satir->where('sonuc', 'yanlis_yer')->map(fn ($s) => ['id' => (int) $s->demirbas_id, 'ad' => $ad[$s->demirbas_id] ?? '—', 'lokasyon' => $s->lokasyon,
                'kayitli' => DB::table('demirbaslar')->where('id', $s->demirbas_id)->value('lokasyon')])->values()->all(),
            'bilinmeyen' => $satir->where('sonuc', 'bilinmeyen')->map(fn ($s) => ['kod' => $s->kod, 'lokasyon' => $s->lokasyon])->values()->all(),
            'eksik' => $kapsam->filter(fn ($d) => !in_array((int) $d->id, $sayilan, true))->map(fn ($d) => ['id' => (int) $d->id, 'ad' => $d->ad, 'barkod' => $d->barkod, 'lokasyon' => $d->lokasyon])->values()->all(),
            'kapsam' => $kapsam->count()];
    }
    // Bitir: yanlış yerdekilerin konumu sayımda bulunduğu yere çekilir (istenirse), eksikler "kayıp" işaretlenir (istenirse).
    function _dmSayimBitir($sid, $p, $sayim, $konumGuncelle = true, $eksikKayip = false)
    {
        $r = _dmSayimRapor($sid, $sayim);
        if ($konumGuncelle) foreach ($r['yanlis_yer'] as $y) {
            DB::table('demirbaslar')->where('id', $y['id'])->update(['lokasyon' => $y['lokasyon'], 'updated_at' => now()]);
            _dmHareket($sid, $y['id'], 'lokasyon', $y['kayitli'], $y['lokasyon'], 'Sayımda burada bulundu', $p->id ?? null);
        }
        if ($eksikKayip) foreach ($r['eksik'] as $e) {
            DB::table('demirbaslar')->where('id', $e['id'])->where('durum', '!=', 'kayip')->update(['durum' => 'kayip', 'updated_at' => now()]);
            _dmHareket($sid, $e['id'], 'durum', null, 'Kayıp', 'Sayımda bulunamadı', $p->id ?? null);
        }
        foreach ($r['bulundu'] as $b) _dmHareket($sid, $b['id'], 'sayim', null, $b['lokasyon'], 'Sayımda okutuldu', $p->id ?? null);
        $ozet = ['bulundu' => count($r['bulundu']), 'yanlis_yer' => count($r['yanlis_yer']), 'eksik' => count($r['eksik']), 'bilinmeyen' => count($r['bilinmeyen']), 'kapsam' => $r['kapsam'],
            'eksik_liste' => array_slice(array_column($r['eksik'], 'ad'), 0, 50)];
        DB::table('demirbas_sayim')->where('id', $sayim->id)->update(['durum' => 'bitti', 'bitis' => now(), 'bitiren_id' => $p->id ?? null, 'ozet' => json_encode($ozet, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        return $ozet;
    }
}

// ============================================================================
// ETİKET MOTORU: CODE128 (SVG) + ZPL (Zebra) + TSPL (TSC/Godex/Xprinter) + tarayıcı baskısı
// ============================================================================
if (!function_exists('_c128Moduller')) {
    // CODE128 modül genişlikleri (bar,boşluk,bar,...). Tamamı rakam ve çift uzunluksa Kod C (kısa), değilse Kod B.
    function _c128Moduller($s)
    {
        static $P = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132',
            '221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313',
            '231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111',
            '314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111',
            '111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141',
            '114131','311141','411131','211412','211214','211232','2331112'];
        $s = (string) $s;
        if ($s !== '' && ctype_digit($s) && strlen($s) % 2 === 0 && strlen($s) >= 4) {
            $kod = [105]; foreach (str_split($s, 2) as $c) $kod[] = (int) $c;
        } else {
            $kod = [104];
            foreach (str_split($s) as $c) { $o = ord($c); if ($o < 32 || $o > 126) $o = 63; $kod[] = $o - 32; }
        }
        $top = $kod[0]; for ($i = 1; $i < count($kod); $i++) $top += $kod[$i] * $i;
        $kod[] = $top % 103; $kod[] = 106;
        $m = []; foreach ($kod as $k) foreach (str_split($P[$k]) as $w) $m[] = (int) $w;
        return $m;
    }
    function _c128Svg($s, $yuk = 50, $modul = 2, $yazi = true)
    {
        $m = _c128Moduller($s); $sessiz = 10 * $modul;
        $gen = array_sum($m) * $modul + 2 * $sessiz; $x = $sessiz; $r = '';
        foreach ($m as $i => $w) { if ($i % 2 === 0) $r .= '<rect x="' . $x . '" y="0" width="' . ($w * $modul) . '" height="' . $yuk . '"/>'; $x += $w * $modul; }
        $th = $yazi ? 16 : 0;
        $t = $yazi ? '<text x="' . ($gen / 2) . '" y="' . ($yuk + 14) . '" font-family="monospace" font-size="13" text-anchor="middle" letter-spacing="1">' . e($s) . '</text>' : '';
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $gen . ' ' . ($yuk + $th) . '" width="' . $gen . '" height="' . ($yuk + $th) . '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">' . $r . '</g>' . $t . '</svg>';
    }

    function _etiketBoyutlar()
    {
        return ['50x25' => ['50 × 25 mm', 50, 25], '50x30' => ['50 × 30 mm', 50, 30], '58x40' => ['58 × 40 mm', 58, 40], '70x40' => ['70 × 40 mm', 70, 40],
            '100x50' => ['100 × 50 mm', 100, 50], 'a4_3x8' => ['A4 yaprak · 3×8 (70 × 37 mm)', 70, 37], 'a4_3x11' => ['A4 yaprak · 3×11 (70 × 25,4 mm)', 70, 25.4]];
    }
    function _etiketDiller()
    {
        return ['tarayici' => 'Bilgisayardan yazdır (her yazıcı — Zebra/TSC sürücüsü ya da A4 etiket yaprağı)', 'zpl' => 'Zebra dili (ZPL) — ağdaki etiket yazıcısına doğrudan',
            'tspl' => 'TSC / Godex / Xprinter dili (TSPL) — ağdaki etiket yazıcısına doğrudan', 'escpos' => 'Fiş yazıcısı (ESC/POS, 58/80 mm) — yalnız uygulamadan'];
    }
    function _etiketAyar($sid)
    {
        _demirbasEnsure();
        $a = DB::table('etiket_ayarlari')->where('sube_id', $sid)->first();
        $o = ['dil' => $a->dil ?? 'tarayici', 'boyut' => $a->boyut ?? '50x30', 'dpi' => (int) ($a->dpi ?? 203), 'ip' => $a->ip ?? null, 'port' => (int) ($a->port ?? 9100)];
        if (!isset(_etiketBoyutlar()[$o['boyut']])) $o['boyut'] = '50x30';
        return $o;
    }
    function _etiketAyarKaydet($sid, array $v)
    {
        _demirbasEnsure();
        $dil = isset(_etiketDiller()[$v['dil'] ?? '']) ? $v['dil'] : 'tarayici';
        $boyut = isset(_etiketBoyutlar()[$v['boyut'] ?? '']) ? $v['boyut'] : '50x30';
        $ip = trim((string) ($v['ip'] ?? ''));
        if ($ip !== '' && !preg_match('/^[A-Za-z0-9.\-]{3,60}$/', $ip)) return [false, 'Yazıcı IP adresi geçersiz.'];
        DB::table('etiket_ayarlari')->updateOrInsert(['sube_id' => $sid], ['dil' => $dil, 'boyut' => $boyut, 'dpi' => in_array((int) ($v['dpi'] ?? 203), [203, 300], true) ? (int) $v['dpi'] : 203,
            'ip' => $ip ?: null, 'port' => max(1, min(65535, (int) ($v['port'] ?? 9100))), 'updated_at' => now(), 'created_at' => now()]);
        return [true, 'Etiket ayarları kaydedildi.'];
    }

    // Etiket içerikleri: [{ust, ad, alt, barkod, adet}]. tur: demirbas (demirbaş id) | barkod (barkodlar.id, ürün/malzeme)
    function _etiketVeri($sid, $tur, array $ids, $adet = 1)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $isl = (string) (DB::table('subeler')->where('id', $sid)->value('ad') ?? '');
        $adet = max(1, min(500, (int) $adet)); $out = [];
        if ($tur === 'demirbas') {
            $rows = DB::table('demirbaslar')->where('sube_id', $sid)->whereIn('id', $ids ?: [0])->orderBy('ad')->get();
            foreach ($rows as $d) {
                $alt = implode(' · ', array_filter([$d->lokasyon, $d->kategori, $d->seri_no ? 'SN ' . $d->seri_no : null]));
                $out[] = ['ust' => $isl ? $isl . ' · DEMİRBAŞ' : 'DEMİRBAŞ', 'ad' => $d->ad, 'alt' => $alt, 'barkod' => _dmBarkodVer($d), 'adet' => $adet];
            }
        } elseif ($tur === 'barkod' && Schema::hasTable('barkodlar')) {
            $rows = DB::table('barkodlar')->where('sube_id', $sid)->whereIn('id', $ids ?: [0])->get();
            foreach ($rows as $b) {
                if ($b->tur === 'urun') { $u = DB::table('urunler')->where('id', $b->hedef_id)->first(['ad', 'fiyat']); $ad = $u->ad ?? '—'; $alt = $u ? number_format((float) $u->fiyat, 2, ',', '.') . ' ₺' : ''; }
                else { $ad = DB::table('malzemeler')->where('id', $b->hedef_id)->value('ad') ?? '—'; $alt = 'Malzeme'; }
                $out[] = ['ust' => $isl, 'ad' => $ad, 'alt' => $alt, 'barkod' => $b->barkod, 'adet' => $adet];
            }
        }
        return $out;
    }
    // Yazıcı içi fontlarda Türkçe harf yok → ASCII'ye çevir (barkod zaten ASCII)
    function _etiketAscii($s)
    {
        $s = strtr((string) $s, ['ç' => 'c', 'Ç' => 'C', 'ğ' => 'g', 'Ğ' => 'G', 'ı' => 'i', 'İ' => 'I', 'ö' => 'o', 'Ö' => 'O', 'ş' => 's', 'Ş' => 'S', 'ü' => 'u', 'Ü' => 'U', '₺' => 'TL', '×' => 'x', '·' => '-']);
        $s = preg_replace('/[^\x20-\x7E]/', '', $s);
        return trim($s);
    }
    function _etiketKisalt($s, $n) { $s = (string) $s; return mb_strlen($s) > $n ? mb_substr($s, 0, $n - 1) . '.' : $s; }
    // Ham yazıcı komutu (ZPL/TSPL). Barkod yatayda ortalanır.
    function _etiketKomut(array $ayar, array $etiketler)
    {
        [, $wmm, $hmm] = _etiketBoyutlar()[$ayar['boyut']];
        $dpm = ($ayar['dpi'] ?? 203) >= 300 ? 12 : 8; // nokta/mm
        $W = (int) round($wmm * $dpm); $H = (int) round($hmm * $dpm); $kenar = 2 * $dpm;
        $kucuk = $H < 30 * $dpm; $out = '';
        foreach ($etiketler as $e) {
            $kod = (string) $e['barkod']; $mod = count(_c128Moduller($kod));
            $modul = max(1, min(3, (int) floor(($W - 2 * $kenar) / max(1, $mod)))); $bw = $mod * $modul; $bx = max(0, (int) (($W - $bw) / 2));
            $ust = _etiketKisalt(_etiketAscii($e['ust']), $kucuk ? 30 : 36); $ad = _etiketKisalt(_etiketAscii($e['ad']), $kucuk ? 22 : 28); $alt = _etiketKisalt(_etiketAscii($e['alt']), 40);
            $y1 = $kenar; $y2 = $y1 + ($kucuk ? 2 : 3) * $dpm + 4; $by = $y2 + ($kucuk ? 4 : 5) * $dpm + 4;
            $bh = max(6 * $dpm, $H - $by - ($alt !== '' ? 9 : 6) * $dpm); $y3 = $by + $bh + 3 * $dpm + 2;
            if (($ayar['dil'] ?? '') === 'tspl') {
                $out .= "SIZE {$wmm} mm,{$hmm} mm\r\nGAP 2 mm,0 mm\r\nDIRECTION 1\r\nREFERENCE 0,0\r\nCLS\r\n";
                $out .= 'TEXT ' . $kenar . ',' . $y1 . ',"1",0,1,1,"' . str_replace('"', "'", $ust) . "\"\r\n";
                $out .= 'TEXT ' . $kenar . ',' . $y2 . ',"3",0,1,1,"' . str_replace('"', "'", $ad) . "\"\r\n";
                $out .= 'BARCODE ' . $bx . ',' . $by . ',"128M",' . $bh . ',1,0,' . $modul . ',' . $modul . ',"' . $kod . "\"\r\n";
                if ($alt !== '') $out .= 'TEXT ' . $kenar . ',' . $y3 . ',"1",0,1,1,"' . str_replace('"', "'", $alt) . "\"\r\n";
                $out .= 'PRINT 1,' . (int) $e['adet'] . "\r\n";
            } else {
                $fs = fn ($s) => str_replace(['^', '~'], ' ', $s);
                $out .= "^XA^CI28^PW{$W}^LL{$H}^LH0,0\n";
                $out .= '^FO' . $kenar . ',' . $y1 . '^A0N,' . ($kucuk ? 18 : 22) . ',' . ($kucuk ? 18 : 22) . '^FB' . ($W - 2 * $kenar) . ',1,0,C^FD' . $fs($ust) . "^FS\n";
                $out .= '^FO' . $kenar . ',' . $y2 . '^A0N,' . ($kucuk ? 28 : 34) . ',' . ($kucuk ? 28 : 34) . '^FB' . ($W - 2 * $kenar) . ',1,0,C^FD' . $fs($ad) . "^FS\n";
                $out .= '^FO' . $bx . ',' . $by . '^BY' . $modul . '^BCN,' . $bh . ',Y,N,N^FD' . $fs($kod) . "^FS\n";
                if ($alt !== '') $out .= '^FO' . $kenar . ',' . ($y3 + 18) . '^A0N,18,18^FB' . ($W - 2 * $kenar) . ',1,0,C^FD' . $fs($alt) . "^FS\n";
                $out .= '^PQ' . (int) $e['adet'] . "^XZ\n";
            }
        }
        return $out;
    }
    function _etiketIstekIds(Request $r) { return array_filter(array_map('intval', explode(',', (string) $r->input('ids', '')))); }
}

// ============================================================================
// WEB
// ============================================================================
$dmKapi = function () { $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {} return [$p, _dmYetki($p)]; };

// Barkod görseli (yalnız verilen metni çizer, veri okumaz)
Route::get('/etiket-barkod.svg', function (Request $r) {
    $k = mb_substr((string) $r->query('k', ''), 0, 64);
    $svg = _c128Svg($k !== '' ? $k : '0', max(20, min(200, (int) $r->query('h', 50))), max(1, min(4, (int) $r->query('m', 2))), $r->query('yazi', '1') !== '0');
    return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']);
});

Route::get('/demirbas', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if (!$p) return redirect('/pos-giris');
    if (!$y) return response(view('yetkisiz', ['sayfa' => '/demirbas']), 403);
    _demirbasEnsure(); $sid = _webSubeId();
    $liste = _dmListe($sid);
    $personeller = DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->orderBy('ad')->get(['id', 'ad']);
    $persAd = _dmPersonelAd($sid);
    $ozet = _dmOzet($liste);
    $aktifSayim = _dmSayimAktif($sid);
    $lokasyonlar = $liste->pluck('lokasyon')->filter()->unique()->sort()->values();
    $kategoriler = $liste->pluck('kategori')->filter()->unique()->sort()->values();
    $ayar = _etiketAyar($sid);
    return view('demirbas', ['liste' => $liste, 'personeller' => $personeller, 'persAd' => $persAd, 'ozet' => $ozet, 'aktifSayim' => $aktifSayim, 'lokasyonlar' => $lokasyonlar,
        'kategoriler' => $kategoriler, 'duzenler' => $y === 'duzenle', 'ayar' => $ayar, 'durumlar' => _dmDurumlar(), 'boyutlar' => _etiketBoyutlar(), 'diller' => _etiketDiller(), 'ara' => (string) $r->query('q', '')]);
});
// Okut / ara: tek sonuçsa kartı açar
Route::get('/demirbas/bul', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if (!$p || !$y) return redirect('/demirbas');
    _demirbasEnsure(); $sid = _webSubeId(); $kod = trim((string) $r->query('kod', ''));
    $d = _dmBul($sid, $kod);
    if ($d) return redirect('/demirbas/kart/' . $d->id);
    return redirect('/demirbas?q=' . urlencode($kod))->with('hata', '"' . $kod . '" barkodlu demirbaş bulunamadı. Aşağıdaki listede adla arayabilir ya da yeni kayıt açabilirsin.');
});
Route::get('/demirbas/kart/{id}', function ($id) use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if (!$p) return redirect('/pos-giris');
    if (!$y) return response(view('yetkisiz', ['sayfa' => '/demirbas']), 403);
    _demirbasEnsure(); $sid = _webSubeId();
    $d = DB::table('demirbaslar')->where('sube_id', $sid)->where('id', (int) $id)->first();
    if (!$d) return redirect('/demirbas')->with('hata', 'Demirbaş bulunamadı.');
    _dmBarkodVer($d);
    $persAd = _dmPersonelAd($sid);
    $hareket = DB::table('demirbas_hareket')->where('demirbas_id', $d->id)->orderByDesc('id')->limit(100)->get();
    $bakim = DB::table('demirbas_bakim')->where('demirbas_id', $d->id)->orderByDesc('tarih')->orderByDesc('id')->get();
    $personeller = DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->orderBy('ad')->get(['id', 'ad']);
    $lokasyonlar = DB::table('demirbaslar')->where('sube_id', $sid)->whereNotNull('lokasyon')->distinct()->orderBy('lokasyon')->pluck('lokasyon');
    $kategoriler = DB::table('demirbaslar')->where('sube_id', $sid)->whereNotNull('kategori')->distinct()->orderBy('kategori')->pluck('kategori');
    return view('demirbas_kart', ['d' => $d, 'persAd' => $persAd, 'hareket' => $hareket, 'bakim' => $bakim, 'personeller' => $personeller, 'lokasyonlar' => $lokasyonlar,
        'kategoriler' => $kategoriler, 'duzenler' => $y === 'duzenle', 'durumlar' => _dmDurumlar(), 'ayar' => _etiketAyar($sid)]);
});
Route::post('/demirbas/kaydet', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if ($y !== 'duzenle') return back()->with('hata', 'Demirbaş eklemek/düzenlemek için yetkin yok.');
    _demirbasEnsure();
    [$ok, $msg, $id] = _dmKaydet(_webSubeId(), $p, $r->all());
    if (!$ok) return back()->withInput()->with('hata', $msg);
    if ($r->input('sonra') === 'etiket') return redirect('/demirbas/etiket?ids=' . $id);
    return redirect($r->input('donus') === 'kart' ? '/demirbas/kart/' . $id : '/demirbas')->with('ok', $msg);
});
Route::post('/demirbas/islem', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if ($y !== 'duzenle') return back()->with('hata', 'Bu işlem için yetkin yok.');
    _demirbasEnsure();
    [$ok, $msg] = _dmIslem(_webSubeId(), $p, $r->all());
    if ($ok && $r->input('islem') === 'sil') return redirect('/demirbas')->with('ok', $msg);
    return back()->with($ok ? 'ok' : 'hata', $msg);
});
// Eski formlar (geri uyum)
Route::post('/demirbas/ekle', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if ($y !== 'duzenle') return back();
    _demirbasEnsure(); [$ok, $msg] = _dmKaydet(_webSubeId(), $p, $r->except('id'));
    return back()->with($ok ? 'ok' : 'hata', $msg);
});
Route::post('/demirbas/bakim-ekle', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if ($y !== 'duzenle') return back();
    _demirbasEnsure(); [$ok, $msg] = _dmIslem(_webSubeId(), $p, ['id' => $r->input('demirbas_id'), 'islem' => 'bakim'] + $r->all());
    return back()->with($ok ? 'ok' : 'hata', $msg);
});
Route::post('/demirbas/durum', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if ($y !== 'duzenle') return back();
    _demirbasEnsure(); $v = $r->input('sil') ? ['id' => $r->input('id'), 'islem' => 'sil'] : ['id' => $r->input('id'), 'islem' => 'durum', 'durum' => $r->input('yeni_durum')];
    [$ok, $msg] = _dmIslem(_webSubeId(), $p, $v);
    return back()->with($ok ? 'ok' : 'hata', $msg);
});
Route::get('/demirbas/excel', function () use ($dmKapi) {
    [$p, $y] = $dmKapi(); if (!$y) return redirect('/demirbas');
    _demirbasEnsure(); $sid = _webSubeId(); $persAd = _dmPersonelAd($sid); $dur = _dmDurumlar();
    $f = fopen('php://temp', 'w+'); fwrite($f, "\xEF\xBB\xBF");
    fputcsv($f, ['Barkod', 'Ad', 'Kategori', 'Konum', 'Zimmet', 'Durum', 'Marka', 'Model', 'Seri No', 'Tedarikçi', 'Alış Tarihi', 'Alış Bedeli', 'Adet', 'Garanti Bitiş', 'Son Sayım'], ';');
    foreach (_dmListe($sid) as $d) fputcsv($f, [$d->barkod, $d->ad, $d->kategori, $d->lokasyon, $persAd[$d->zimmet_personel_id] ?? '', $dur[$d->durum] ?? $d->durum, $d->marka, $d->model, $d->seri_no, $d->tedarikci,
        $d->alis_tarih, number_format((float) $d->alis_bedel, 2, ',', ''), $d->adet, $d->garanti_bitis, $d->son_sayim], ';');
    rewind($f); $csv = stream_get_contents($f);
    return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="demirbas-listesi-' . date('Y-m-d') . '.csv"']);
});

// ----- Etiket (web) -----
$etiketSayfa = function (Request $r, $tur) {
    $sid = _webSubeId(); $ayar = _etiketAyar($sid);
    if ($r->filled('boyut') && isset(_etiketBoyutlar()[$r->query('boyut')])) $ayar['boyut'] = $r->query('boyut');
    $etiketler = _etiketVeri($sid, $tur, _etiketIstekIds($r), (int) $r->query('adet', 1));
    if (!$etiketler) return back()->with('hata', 'Basılacak etiket seçilmedi.');
    if ($r->query('format') === 'zpl' || $r->query('format') === 'tspl') {
        $ayar['dil'] = $r->query('format');
        return response(_etiketKomut($ayar, $etiketler), 200, ['Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="etiket-' . date('Ymd-His') . '.' . ($ayar['dil'] === 'zpl' ? 'zpl' : 'prn') . '"']);
    }
    if ($tur === 'demirbas') foreach ($etiketler as $e) { $d = _dmBul($sid, $e['barkod']); if ($d) _dmHareket($sid, $d->id, 'etiket', null, null, $e['adet'] . ' etiket basıldı', session('pos_personel_id')); }
    return view('etiket_yazdir', ['etiketler' => $etiketler, 'ayar' => $ayar, 'boyutlar' => _etiketBoyutlar(), 'geri' => $tur === 'demirbas' ? '/demirbas' : '/barkod',
        'ids' => implode(',', _etiketIstekIds($r)), 'adet' => max(1, (int) $r->query('adet', 1)), 'yol' => $tur === 'demirbas' ? '/demirbas/etiket' : '/barkod/etiket']);
};
Route::get('/demirbas/etiket', function (Request $r) use ($dmKapi, $etiketSayfa) {
    [$p, $y] = $dmKapi(); if (!$y) return redirect('/demirbas');
    _demirbasEnsure(); return $etiketSayfa($r, 'demirbas');
});
Route::get('/barkod/etiket', function (Request $r) use ($etiketSayfa) {
    if (!_posPersonel()) return redirect('/pos-giris');
    if (function_exists('_barkodKur')) _barkodKur();
    _demirbasEnsure(); return $etiketSayfa($r, 'barkod');
});
$etiketAyarPost = function (Request $r) {
    $p = _posPersonel();
    if (!$p) return redirect('/pos-giris');
    [$ok, $msg] = _etiketAyarKaydet(_webSubeId(), $r->all());
    return back()->with($ok ? 'ok' : 'hata', $msg);
};
Route::post('/demirbas/etiket-ayar', function (Request $r) use ($dmKapi, $etiketAyarPost) { [$p, $y] = $dmKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.'); return $etiketAyarPost($r); });
Route::post('/barkod/etiket-ayar', $etiketAyarPost);

// ----- Sayım (web) -----
Route::get('/demirbas/sayim', function () use ($dmKapi) {
    [$p, $y] = $dmKapi();
    if (!$p) return redirect('/pos-giris');
    if (!$y) return response(view('yetkisiz', ['sayfa' => '/demirbas']), 403);
    _demirbasEnsure(); $sid = _webSubeId();
    $aktif = _dmSayimAktif($sid);
    $rapor = $aktif ? _dmSayimRapor($sid, $aktif) : null;
    $gecmis = DB::table('demirbas_sayim')->where('sube_id', $sid)->where('durum', 'bitti')->orderByDesc('id')->limit(15)->get();
    $lokasyonlar = DB::table('demirbaslar')->where('sube_id', $sid)->whereNotNull('lokasyon')->distinct()->orderBy('lokasyon')->pluck('lokasyon');
    return view('demirbas_sayim', ['aktif' => $aktif, 'rapor' => $rapor, 'gecmis' => $gecmis, 'lokasyonlar' => $lokasyonlar, 'persAd' => _dmPersonelAd($sid), 'duzenler' => $y === 'duzenle']);
});
Route::post('/demirbas/sayim/baslat', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if (!$y) return back();
    _demirbasEnsure(); $sid = _webSubeId();
    if (_dmSayimAktif($sid)) return redirect('/demirbas/sayim')->with('hata', 'Açık bir sayım zaten var; önce onu bitir.');
    DB::table('demirbas_sayim')->insert(['sube_id' => $sid, 'lokasyon' => trim((string) $r->input('lokasyon')) ?: null, 'durum' => 'acik', 'baslatan_id' => $p->id, 'baslangic' => now(), 'created_at' => now(), 'updated_at' => now()]);
    return redirect('/demirbas/sayim')->with('ok', 'Sayım başladı. Etiketleri okutmaya başla.');
});
Route::post('/demirbas/sayim/okut', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if (!$y) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok'], 403);
    _demirbasEnsure(); $sid = _webSubeId(); $s = _dmSayimAktif($sid);
    if (!$s) return ['ok' => 0, 'hata' => 'Açık sayım yok'];
    $sonuc = _dmSayimOkut($sid, $p, $s, $r->input('kod'), $r->input('lokasyon'));
    $rp = _dmSayimRapor($sid, $s);
    return $sonuc + ['sayac' => ['bulundu' => count($rp['bulundu']), 'yanlis_yer' => count($rp['yanlis_yer']), 'eksik' => count($rp['eksik']), 'bilinmeyen' => count($rp['bilinmeyen']), 'kapsam' => $rp['kapsam']]];
});
Route::post('/demirbas/sayim/bitir', function (Request $r) use ($dmKapi) {
    [$p, $y] = $dmKapi(); if (!$y) return back();
    _demirbasEnsure(); $sid = _webSubeId(); $s = _dmSayimAktif($sid);
    if (!$s) return redirect('/demirbas/sayim');
    if ($r->input('iptal')) { DB::table('demirbas_sayim_satir')->where('sayim_id', $s->id)->delete(); DB::table('demirbas_sayim')->where('id', $s->id)->delete(); return redirect('/demirbas/sayim')->with('ok', 'Sayım iptal edildi.'); }
    $duz = $y === 'duzenle';
    $o = _dmSayimBitir($sid, $p, $s, $duz && $r->boolean('konum_guncelle'), $duz && $r->boolean('eksik_kayip'));
    return redirect('/demirbas/sayim')->with('ok', 'Sayım bitti: ' . $o['bulundu'] . ' yerinde, ' . $o['yanlis_yer'] . ' yanlış yerde, ' . $o['eksik'] . ' bulunamadı, ' . $o['bilinmeyen'] . ' tanımsız barkod.');
});

// ============================================================================
// API (uygulama): Bearer token. Web ile aynı fonksiyonlar.
// ============================================================================
$dmApi = function (Request $r) { $p = _apiPersonel($r); return [$p, _dmYetki($p)]; };
$dmRed = fn ($p) => response()->json(['ok' => 0, 'hata' => $p ? 'Demirbaş yetkin yok' : 'Oturum gerekli'], $p ? 403 : 401);

Route::get('/api/demirbas/liste', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $sid = $p->sube_id; $liste = _dmListe($sid); $persAd = _dmPersonelAd($sid);
    return ['ok' => 1, 'duzenler' => $y === 'duzenle', 'ozet' => _dmOzet($liste), 'liste' => $liste->map(fn ($d) => _dmSatir($d, $persAd))->values(),
        'durumlar' => _dmDurumlar(), 'lokasyonlar' => $liste->pluck('lokasyon')->filter()->unique()->sort()->values(), 'kategoriler' => $liste->pluck('kategori')->filter()->unique()->sort()->values(),
        'personeller' => DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->orderBy('ad')->get(['id', 'ad']),
        'aktif_sayim' => ($s = _dmSayimAktif($sid)) ? ['id' => (int) $s->id, 'lokasyon' => $s->lokasyon] : null, 'etiket_ayar' => _etiketAyar($sid)];
});
Route::get('/api/demirbas/detay', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $sid = $p->sube_id;
    $d = $r->filled('kod') ? _dmBul($sid, $r->query('kod')) : DB::table('demirbaslar')->where('sube_id', $sid)->where('id', (int) $r->query('id'))->first();
    if (!$d) return ['ok' => 0, 'bulundu' => 0, 'hata' => 'Demirbaş bulunamadı', 'kod' => (string) $r->query('kod', '')];
    _dmBarkodVer($d); $persAd = _dmPersonelAd($sid);
    return ['ok' => 1, 'bulundu' => 1, 'duzenler' => $y === 'duzenle', 'demirbas' => _dmSatir($d, $persAd),
        'bakimlar' => DB::table('demirbas_bakim')->where('demirbas_id', $d->id)->orderByDesc('tarih')->get(['id', 'tarih', 'tip', 'aciklama', 'maliyet']),
        'hareketler' => DB::table('demirbas_hareket')->where('demirbas_id', $d->id)->orderByDesc('id')->limit(50)->get()
            ->map(fn ($h) => ['tip' => $h->tip, 'eski' => $h->eski, 'yeni' => $h->yeni, 'aciklama' => $h->aciklama, 'kim' => $persAd[$h->personel_id] ?? null, 'tarih' => (string) $h->created_at])];
});
Route::post('/api/demirbas/kaydet', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if ($y !== 'duzenle') return $dmRed($p);
    _demirbasEnsure(); [$ok, $msg, $id] = _dmKaydet($p->sube_id, $p, $r->all());
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg, 'id' => $id, 'barkod' => $id ? DB::table('demirbaslar')->where('id', $id)->value('barkod') : null];
});
Route::post('/api/demirbas/islem', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if ($y !== 'duzenle') return $dmRed($p);
    _demirbasEnsure(); [$ok, $msg] = _dmIslem($p->sube_id, $p, $r->all());
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg];
});
Route::get('/api/demirbas/sayim', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $s = _dmSayimAktif($p->sube_id);
    return ['ok' => 1, 'aktif' => $s ? ['id' => (int) $s->id, 'lokasyon' => $s->lokasyon, 'baslangic' => (string) $s->baslangic] : null, 'rapor' => $s ? _dmSayimRapor($p->sube_id, $s) : null];
});
Route::post('/api/demirbas/sayim/baslat', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $sid = $p->sube_id;
    if ($s = _dmSayimAktif($sid)) return ['ok' => 1, 'id' => (int) $s->id, 'mesaj' => 'Açık sayıma devam'];
    $id = DB::table('demirbas_sayim')->insertGetId(['sube_id' => $sid, 'lokasyon' => trim((string) $r->input('lokasyon')) ?: null, 'durum' => 'acik', 'baslatan_id' => $p->id, 'baslangic' => now(), 'created_at' => now(), 'updated_at' => now()]);
    return ['ok' => 1, 'id' => $id, 'mesaj' => 'Sayım başladı'];
});
Route::post('/api/demirbas/sayim/okut', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $s = _dmSayimAktif($p->sube_id);
    if (!$s) return ['ok' => 0, 'hata' => 'Açık sayım yok'];
    $sonuc = _dmSayimOkut($p->sube_id, $p, $s, $r->input('kod'), $r->input('lokasyon'));
    $rp = _dmSayimRapor($p->sube_id, $s);
    return $sonuc + ['sayac' => ['bulundu' => count($rp['bulundu']), 'yanlis_yer' => count($rp['yanlis_yer']), 'eksik' => count($rp['eksik']), 'bilinmeyen' => count($rp['bilinmeyen']), 'kapsam' => $rp['kapsam']]];
});
Route::post('/api/demirbas/sayim/bitir', function (Request $r) use ($dmApi, $dmRed) {
    [$p, $y] = $dmApi($r); if (!$y) return $dmRed($p);
    _demirbasEnsure(); $s = _dmSayimAktif($p->sube_id);
    if (!$s) return ['ok' => 0, 'hata' => 'Açık sayım yok'];
    if ($r->boolean('iptal')) { DB::table('demirbas_sayim_satir')->where('sayim_id', $s->id)->delete(); DB::table('demirbas_sayim')->where('id', $s->id)->delete(); return ['ok' => 1, 'mesaj' => 'Sayım iptal edildi']; }
    $duz = $y === 'duzenle';
    return ['ok' => 1, 'ozet' => _dmSayimBitir($p->sube_id, $p, $s, $duz && $r->boolean('konum_guncelle', true), $duz && $r->boolean('eksik_kayip'))];
});
// Etiket: ayar + ham komut (uygulama ağdaki etiket yazıcısına TCP 9100'den gönderir)
Route::get('/api/etiket/ayar', function (Request $r) {
    $p = _apiPersonel($r); if (!$p) return response()->json(['ok' => 0], 401);
    return ['ok' => 1, 'ayar' => _etiketAyar($p->sube_id), 'boyutlar' => collect(_etiketBoyutlar())->map(fn ($b) => $b[0]), 'diller' => _etiketDiller()];
});
Route::post('/api/etiket/ayar', function (Request $r) {
    $p = _apiPersonel($r); if (!$p) return response()->json(['ok' => 0], 401);
    if (!in_array($p->rol, ['sahip', 'mudur'], true) && _dmYetki($p) !== 'duzenle') return response()->json(['ok' => 0, 'hata' => 'Yetkin yok'], 403);
    [$ok, $msg] = _etiketAyarKaydet($p->sube_id, $r->all());
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg, 'ayar' => _etiketAyar($p->sube_id)];
});
Route::post('/api/etiket/komut', function (Request $r) {
    $p = _apiPersonel($r); if (!$p) return response()->json(['ok' => 0], 401);
    $tur = (string) $r->input('tur', 'demirbas');
    if ($tur === 'demirbas' && !_dmYetki($p)) return response()->json(['ok' => 0, 'hata' => 'Demirbaş yetkin yok'], 403);
    _demirbasEnsure(); if (function_exists('_barkodKur')) _barkodKur();
    $ids = is_array($r->input('ids')) ? $r->input('ids') : explode(',', (string) $r->input('ids', ''));
    $ayar = _etiketAyar($p->sube_id);
    if (in_array($r->input('dil'), ['zpl', 'tspl'], true)) $ayar['dil'] = $r->input('dil');
    $et = _etiketVeri($p->sube_id, $tur === 'barkod' ? 'barkod' : 'demirbas', $ids, (int) $r->input('adet', 1));
    if (!$et) return ['ok' => 0, 'hata' => 'Etiket bulunamadı'];
    if (!in_array($ayar['dil'], ['zpl', 'tspl'], true)) return ['ok' => 1, 'dil' => $ayar['dil'], 'etiketler' => $et, 'komut' => null, 'ayar' => $ayar];
    return ['ok' => 1, 'dil' => $ayar['dil'], 'komut' => _etiketKomut($ayar, $et), 'ip' => $ayar['ip'], 'port' => $ayar['port'], 'etiketler' => $et, 'ayar' => $ayar];
});

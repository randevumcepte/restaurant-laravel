<?php
/*
 * ============================================================================
 *  FİYAT ANLAŞMALARI + ÇERÇEVE SÖZLEŞME + BELGE + RAPORLAR (web.php'den taşındı)
 *  - fiyat_anlasmalari: tedarikçi+malzeme dönemsel sabit fiyat (tekli ya da bir sözleşmenin kalemi)
 *  - fiyat_sozlesmeleri: tek belge, çok malzeme; şartlar (vade, teslim, fiyat maddesi, fesih ihbarı)
 *  - sozlesme_belgeleri: PDF/Word/foto; storage/app/sozlesme (dışarıya kapalı, yetkiyle indirilir)
 *  - _anlasmaliFiyat(): acil/otomatik siparişte kullanılır
 *  Erişim: satın alma/patron/müdür yönetir; departman sorumlusu fiyatları salt okur (belge yok)
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_fiyatAnlasmaEnsure')) {
    function _fiyatAnlasmaEnsure()
    {
        if (cache()->get('fiyat_anlasma_ensure_ok3')) return;
        try {
            if (!Schema::hasTable('fiyat_anlasmalari')) {
                Schema::create('fiyat_anlasmalari', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index();
                    $t->unsignedBigInteger('tedarikci_id'); $t->unsignedBigInteger('malzeme_id');
                    $t->decimal('birim_fiyat', 12, 4)->default(0); $t->string('birim', 20)->nullable();
                    $t->date('baslangic')->nullable(); $t->date('bitis')->nullable();
                    $t->text('not')->nullable(); $t->tinyInteger('aktif')->default(1);
                    $t->timestamps(); $t->index(['tedarikci_id', 'malzeme_id']);
                });
            }
            if (!Schema::hasColumn('fiyat_anlasmalari', 'sozlesme_id')) Schema::table('fiyat_anlasmalari', function ($t) { $t->unsignedBigInteger('sozlesme_id')->nullable()->index(); });
            if (!Schema::hasColumn('fiyat_anlasmalari', 'taahhut_miktar')) Schema::table('fiyat_anlasmalari', function ($t) { $t->decimal('taahhut_miktar', 12, 3)->nullable(); });
            if (!Schema::hasTable('fiyat_sozlesmeleri')) {
                Schema::create('fiyat_sozlesmeleri', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('tedarikci_id')->index();
                    $t->string('sozlesme_no', 60)->nullable(); $t->string('baslik', 120)->nullable();
                    $t->date('imza_tarihi')->nullable(); $t->string('imzalayan', 80)->nullable();
                    $t->date('baslangic')->nullable(); $t->date('bitis')->nullable();
                    $t->integer('odeme_vade_gun')->nullable(); $t->string('teslim_suresi', 60)->nullable();
                    $t->string('fiyat_maddesi', 20)->default('sabit'); $t->decimal('artis_tavan', 6, 2)->nullable(); $t->string('fiyat_maddesi_not', 200)->nullable();
                    $t->integer('fesih_ihbar_gun')->nullable(); $t->text('not')->nullable();
                    $t->tinyInteger('aktif')->default(1); $t->unsignedBigInteger('olusturan_id')->nullable();
                    $t->timestamps();
                });
            }
            if (!Schema::hasTable('sozlesme_belgeleri')) {
                Schema::create('sozlesme_belgeleri', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index();
                    $t->unsignedBigInteger('sozlesme_id')->nullable()->index(); $t->unsignedBigInteger('anlasma_id')->nullable()->index();
                    $t->string('yol', 255); $t->string('ad', 160); $t->string('mime', 100)->nullable(); $t->unsignedInteger('boyut')->default(0);
                    $t->unsignedBigInteger('yukleyen_id')->nullable(); $t->timestamp('created_at')->nullable();
                });
            }
            cache()->put('fiyat_anlasma_ensure_ok3', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}
if (!function_exists('_anlasmaliFiyat')) {
    function _anlasmaliFiyat($tedarikciId, $malzemeId, $sube)
    {
        try {
            if (!Schema::hasTable('fiyat_anlasmalari')) return null;
            $bugun = today()->toDateString();
            $a = DB::table('fiyat_anlasmalari')->where('sube_id', $sube)->where('tedarikci_id', (int) $tedarikciId)->where('malzeme_id', (int) $malzemeId)->where('aktif', 1)
                ->where(function ($q) use ($bugun) { $q->whereNull('baslangic')->orWhere('baslangic', '<=', $bugun); })
                ->where(function ($q) use ($bugun) { $q->whereNull('bitis')->orWhere('bitis', '>=', $bugun); })
                ->orderByDesc('id')->first();
            return $a ? (float) $a->birim_fiyat : null;
        } catch (\Throwable $e) { return null; }
    }
}
if (!function_exists('_faYetki')) {
    // Fiyat Anlaşmaları erişim seviyesi: 'duzenle' | 'gor' (sözleşme, belge, rapor) | 'salt' (departman sorumlusu: yalnız fiyatlar) | null
    // Yetki ekranındaki iki kutucuk belirler; kayıtta yoksa varsayılan: satın alma erişimi → düzenle, finans/muhasebe → görüntüle.
    function _faYetki($p)
    {
        if (!$p) return null;
        $rol = mb_strtolower((string) ($p->rol ?? ''), 'UTF-8');
        if (in_array($rol, ['sahip', 'mudur', 'müdür'], true)) return 'duzenle';
        if (function_exists('_kasiyerMi') && _kasiyerMi($p)) return null;
        $sy = (isset($p->sayfa_yetki) && $p->sayfa_yetki) ? json_decode($p->sayfa_yetki, true) : null;
        $var = fn ($k) => is_array($sy) && array_key_exists($k, $sy);
        $duz = $var('gor_fiyat_anlasma_duzenle') ? !empty($sy['gor_fiyat_anlasma_duzenle']) : _faVarsayilan($p, 'duzenle');
        $gor = $duz || ($var('gor_fiyat_anlasma') ? !empty($sy['gor_fiyat_anlasma']) : _faVarsayilan($p, 'gor'));
        if ($duz) return 'duzenle';
        if ($gor) return 'gor';
        return function_exists('_departmanSorumlusuMu') && _departmanSorumlusuMu($p) ? 'salt' : null;
    }
}
if (!function_exists('_faVarsayilan')) {
    // Kayıtta kutucuk yokken: satın alma sayfalarına erişen → düzenler; finans/muhasebe → görür. İK/ofis/idare otomatik görmez.
    function _faVarsayilan($p, $seviye)
    {
        $rol = mb_strtolower((string) ($p->rol ?? ''), 'UTF-8');
        $kademe = $p->kademe ?? (function_exists('_rolKademe') ? _rolKademe($p->rol ?? '') : '');
        if (in_array($kademe, ['supervizor', 'satinalma'], true) || in_array($rol, ['satinalmaci', 'satınalmacı', 'satinalma', 'ust_yonetim', 'yonetim', 'yönetim'], true)) return true;
        $satin = false;
        try { $satin = function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_satinalma'); } catch (\Throwable $e) {}
        if ($satin) return true;
        if ($seviye === 'gor') {
            if (in_array($rol, ['muhasebe', 'finans'], true)) return true;
            try { if (!empty($p->departman_id) && DB::table('departmanlar')->where('id', $p->departman_id)->value('kod') === 'finans') return true; } catch (\Throwable $e) {}
        }
        return false;
    }
}
if (!function_exists('_faYetkiGoc')) {
    // Tek seferlik: yetki kaydı olan personel ve departmanlara iki kutucuğu bugünkü erişimle yazar (yetki ekranı gerçek durumu göstersin, kaydedince kimse erişim kaybetmesin)
    function _faYetkiGoc()
    {
        if (cache()->get('fa_yetki_goc_1')) return;
        try {
            if (!Schema::hasColumn('personeller', 'sayfa_yetki')) return;
            foreach (DB::table('personeller')->whereNotNull('sayfa_yetki')->get() as $p) {
                $sy = json_decode((string) $p->sayfa_yetki, true);
                if (!is_array($sy) || (array_key_exists('gor_fiyat_anlasma', $sy) && array_key_exists('gor_fiyat_anlasma_duzenle', $sy))) continue;
                if (in_array($p->rol ?? '', ['sahip'], true)) continue;
                $duz = _faVarsayilan($p, 'duzenle');
                if (!array_key_exists('gor_fiyat_anlasma_duzenle', $sy)) $sy['gor_fiyat_anlasma_duzenle'] = $duz ? 1 : 0;
                if (!array_key_exists('gor_fiyat_anlasma', $sy)) $sy['gor_fiyat_anlasma'] = ($duz || _faVarsayilan($p, 'gor')) ? 1 : 0;
                DB::table('personeller')->where('id', $p->id)->update(['sayfa_yetki' => json_encode($sy)]);
            }
            if (Schema::hasTable('departmanlar')) foreach (DB::table('departmanlar')->get() as $d) {
                $sy = json_decode((string) $d->sayfa_yetki, true);
                if (!is_array($sy) || array_key_exists('gor_fiyat_anlasma', $sy)) continue;
                $satin = !empty($sy['gor_satinalma']);
                $sy['gor_fiyat_anlasma'] = ($satin || $d->kod === 'finans') ? 1 : 0;
                $sy['gor_fiyat_anlasma_duzenle'] = $satin ? 1 : 0;
                DB::table('departmanlar')->where('id', $d->id)->update(['sayfa_yetki' => json_encode($sy)]);
            }
            cache()->forever('fa_yetki_goc_1', 1);
        } catch (\Throwable $e) {}
    }
}
if (!function_exists('_faSaltGorur')) {
    // Departman sorumlusu: anlaşmalı fiyatları salt okur. Belge ve rapor görmez.
    function _faSaltGorur($p) { return _faYetki($p) === 'salt'; }
}if (!function_exists('_faBirimNorm')) {
    function _faBirimNorm($b) { return mb_strtolower(trim((string) $b), 'UTF-8'); }
}
if (!function_exists('_faBelgeKaydet')) {
    // Yüklenen belgeleri storage/app/sozlesme/{şube}/ altına koyar. [eklenen, hatalar[]]
    function _faBelgeKaydet(Request $r, $sid, $sozId, $anlId, $pid)
    {
        $izinli = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif', 'doc', 'docx', 'xls', 'xlsx'];
        $eklenen = 0; $hata = [];
        $dosyalar = $r->file('belgeler') ?: [];
        if (!is_array($dosyalar)) $dosyalar = [$dosyalar];
        $dir = storage_path('app/sozlesme/' . (int) $sid);
        foreach ($dosyalar as $f) {
            if (!$f) continue;
            $ad = mb_substr((string) $f->getClientOriginalName(), 0, 150) ?: 'belge';
            if (!$f->isValid()) { $hata[] = $ad . ' yüklenemedi (dosya sunucu sınırını aşıyor olabilir: en fazla ' . ini_get('upload_max_filesize') . ').'; continue; }
            $ext = strtolower($f->getClientOriginalExtension() ?: $f->guessExtension() ?: '');
            if (!in_array($ext, $izinli, true)) { $hata[] = $ad . ': bu dosya türü kabul edilmiyor.'; continue; }
            if ($f->getSize() > 20 * 1024 * 1024) { $hata[] = $ad . ': 20 MB üstü.'; continue; }
            try {
                if (!is_dir($dir)) @mkdir($dir, 0775, true);
                $yeni = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $mime = $f->getClientMimeType(); $boyut = (int) $f->getSize();
                $f->move($dir, $yeni);
                DB::table('sozlesme_belgeleri')->insert(['sube_id' => (int) $sid, 'sozlesme_id' => $sozId ?: null, 'anlasma_id' => $anlId ?: null,
                    'yol' => 'sozlesme/' . (int) $sid . '/' . $yeni, 'ad' => $ad, 'mime' => mb_substr((string) $mime, 0, 100), 'boyut' => $boyut, 'yukleyen_id' => $pid, 'created_at' => now()]);
                $eklenen++;
            } catch (\Throwable $e) { $hata[] = $ad . ' kaydedilemedi.'; }
        }
        return [$eklenen, $hata];
    }
}
if (!function_exists('_faBelgeSil')) {
    function _faBelgeSil($belge)
    {
        try { $yol = storage_path('app/' . $belge->yol); if (is_file($yol)) @unlink($yol); } catch (\Throwable $e) {}
        DB::table('sozlesme_belgeleri')->where('id', $belge->id)->delete();
    }
}
if (!function_exists('_faDonemDurum')) {
    // [durum, kalan_gün, ilerleme%] — durum: aktif | yakinda | baslamadi | bitti | durdu
    function _faDonemDurum($aktif, $baslangic, $bitis)
    {
        $bugun = today();
        $bas = $baslangic ? \Carbon\Carbon::parse($baslangic) : null; $bit = $bitis ? \Carbon\Carbon::parse($bitis) : null;
        $kalan = $bit ? (int) $bugun->diffInDays($bit, false) : null;
        $durum = !$aktif ? 'durdu' : ($bit && $kalan < 0 ? 'bitti' : ($bas && $bas->gt($bugun) ? 'baslamadi' : ($bit && $kalan <= 15 ? 'yakinda' : 'aktif')));
        $ilerleme = ($bas && $bit && $bit->gt($bas)) ? max(0, min(100, round($bas->diffInDays($bugun, false) / max(1, $bas->diffInDays($bit)) * 100))) : null;
        return [$durum, $kalan, $ilerleme];
    }
}
if (!function_exists('_faKalemler')) {
    // Şubenin alış faturası kalemleri (tarih aralığı) — rapor ve sayfa ortak
    function _faKalemler($sid, $bas, $bit = null)
    {
        try {
            if (!Schema::hasTable('alis_fatura_kalemleri') || !Schema::hasTable('alis_faturalari')) return collect();
            $q = DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'k.fatura_id', '=', 'f.id')
                ->where('f.sube_id', (int) $sid)->where('k.birim_fiyat', '>', 0)->whereDate('f.tarih', '>=', $bas);
            if ($bit) $q->whereDate('f.tarih', '<=', $bit);
            return $q->orderBy('f.tarih')->orderBy('k.id')->get(['k.id', 'k.malzeme_id', 'k.alis_birim_id', 'k.birim_fiyat', 'k.miktar', 'f.tedarikci_id', 'f.tarih', 'f.fatura_no', 'f.id as fatura_id']);
        } catch (\Throwable $e) { return collect(); }
    }
}
if (!function_exists('_faEslesir')) {
    // Fatura kalemi bu anlaşmaya mı tabi? (tedarikçi + malzeme + dönem + aynı birim)
    function _faEslesir($a, $k, $birimKis)
    {
        if ((int) $k->tedarikci_id !== (int) $a->tedarikci_id || (int) $k->malzeme_id !== (int) $a->malzeme_id) return false;
        $t = substr((string) $k->tarih, 0, 10);
        if ($a->baslangic && $t < substr((string) $a->baslangic, 0, 10)) return false;
        if ($a->bitis && $t > substr((string) $a->bitis, 0, 10)) return false;
        return !$a->birim || !$k->alis_birim_id || _faBirimNorm($birimKis[$k->alis_birim_id] ?? '') === _faBirimNorm($a->birim);
    }
}

// ============================ SAYFA ============================
Route::get('/fiyat-anlasmalari', function () {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    $seviye = _faYetki($bp);
    if (!$seviye) return redirect('/pos-giris');
    $salt = $seviye === 'salt'; $duzenler = $seviye === 'duzenle';
    _fiyatAnlasmaEnsure(); $sid = _webSubeId();
    $liste = DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->orderByDesc('aktif')->orderByDesc('id')->limit(500)->get();
    $sozlesmeler = DB::table('fiyat_sozlesmeleri')->where('sube_id', $sid)->orderByDesc('aktif')->orderByDesc('id')->limit(200)->get();
    $belgeler = $salt ? collect() : DB::table('sozlesme_belgeleri')->where('sube_id', $sid)->orderBy('id')->get(['id', 'sozlesme_id', 'anlasma_id', 'ad', 'mime', 'boyut', 'created_at']);
    $tedAd = DB::table('tedarikciler')->pluck('ad', 'id');
    $malAd = DB::table('malzemeler')->pluck('ad', 'id');
    $tedarikciler = DB::table('tedarikciler')->orderBy('ad')->get(['id', 'ad']);
    $malzemeler = DB::table('malzemeler')->orderBy('ad')->get(['id', 'ad', 'temel_birim_id']);
    $birimKis = Schema::hasTable('birimler') ? DB::table('birimler')->pluck('kisaltma', 'id') : collect();
    $birimler = Schema::hasTable('birimler') ? DB::table('birimler')->orderBy('id')->pluck('kisaltma')->filter()->unique()->values() : collect(['kg', 'lt', 'adet']);
    $kalemler = _faKalemler($sid, today()->subDays(180));
    $bugun = today();
    $sozMap = $sozlesmeler->keyBy('id');
    $belgeAnl = $belgeler->whereNotNull('anlasma_id')->groupBy('anlasma_id');
    $belgeSoz = $belgeler->whereNotNull('sozlesme_id')->groupBy('sozlesme_id');
    $belgeJson = fn ($c) => collect($c)->map(fn ($b) => ['id' => (int) $b->id, 'ad' => $b->ad, 'mime' => $b->mime, 'boyut' => (int) $b->boyut])->values()->all();
    $kpi = ['aktif' => 0, 'yakinda' => 0, 'bitti' => 0, 'ihlal' => 0, 'fazla' => 0.0];
    $kartlar = [];
    foreach ($liste as $a) {
        [$durum, $kalan, $ilerleme] = _faDonemDurum($a->aktif, $a->baslangic, $a->bitis);
        $donem = $kalemler->filter(fn ($k) => _faEslesir($a, $k, $birimKis));
        $af = (float) $a->birim_fiyat; $fazla = 0.0; $ihlalSay = 0;
        foreach ($donem as $k) { if ((float) $k->birim_fiyat > $af * 1.005) { $fazla += ((float) $k->birim_fiyat - $af) * (float) $k->miktar; $ihlalSay++; } }
        $son = $donem->last();
        $piyasa = $kalemler->filter(fn ($k) => (int) $k->malzeme_id === (int) $a->malzeme_id && (int) $k->tedarikci_id !== (int) $a->tedarikci_id
            && substr((string) $k->tarih, 0, 10) >= $bugun->copy()->subDays(90)->toDateString()
            && (!$a->birim || !$k->alis_birim_id || _faBirimNorm($birimKis[$k->alis_birim_id] ?? '') === _faBirimNorm($a->birim)))->min('birim_fiyat');
        if (in_array($durum, ['aktif', 'yakinda', 'baslamadi'], true)) $kpi['aktif']++;
        if ($durum === 'yakinda') $kpi['yakinda']++;
        if ($durum === 'bitti') $kpi['bitti']++;
        if ($ihlalSay) { $kpi['ihlal']++; $kpi['fazla'] += $fazla; }
        $soz = $a->sozlesme_id ? ($sozMap[$a->sozlesme_id] ?? null) : null;
        $kartlar[] = ['id' => (int) $a->id, 'ted_id' => (int) $a->tedarikci_id, 'mal_id' => (int) $a->malzeme_id,
            'ted' => $tedAd[$a->tedarikci_id] ?? '—', 'mal' => $malAd[$a->malzeme_id] ?? '—', 'fiyat' => $af, 'birim' => $a->birim,
            'baslangic' => $a->baslangic, 'bitis' => $a->bitis, 'not' => $a->not, 'aktif' => (int) $a->aktif, 'taahhut' => $a->taahhut_miktar !== null ? (float) $a->taahhut_miktar : null,
            'durum' => $durum, 'kalan' => $kalan, 'ilerleme' => $ilerleme,
            'fatura_say' => $donem->count(), 'ihlal_say' => $ihlalSay, 'fazla' => round($fazla, 2),
            'son_fiyat' => $son ? (float) $son->birim_fiyat : null,
            'piyasa' => $piyasa ? (float) $piyasa : null, 'piyasa_fark' => $piyasa && $piyasa > 0 ? round(($af - $piyasa) / $piyasa * 100) : null,
            'sozlesme_id' => $soz ? (int) $soz->id : null, 'sozlesme_ad' => $soz ? ($soz->sozlesme_no ?: ($soz->baslik ?: 'Sözleşme #' . $soz->id)) : null,
            'belgeler' => $belgeJson($soz ? ($belgeSoz[$soz->id] ?? []) : ($belgeAnl[$a->id] ?? []))];
    }
    $sira = ['yakinda' => 1, 'aktif' => 2, 'baslamadi' => 3, 'bitti' => 4, 'durdu' => 5];
    usort($kartlar, fn ($x, $y) => [$y['ihlal_say'] > 0, -($sira[$x['durum']] ?? 9)] <=> [$x['ihlal_say'] > 0, -($sira[$y['durum']] ?? 9)]);
    // Sözleşme kartları
    $kalemSoz = $liste->whereNotNull('sozlesme_id')->groupBy('sozlesme_id');
    $kartIdx = collect($kartlar)->keyBy('id');
    $sozKart = [];
    foreach ($sozlesmeler as $s) {
        [$durum, $kalan, $ilerleme] = _faDonemDurum($s->aktif, $s->baslangic, $s->bitis);
        $kk = $kalemSoz[$s->id] ?? collect();
        $fesihSon = ($s->bitis && $s->fesih_ihbar_gun) ? \Carbon\Carbon::parse($s->bitis)->subDays((int) $s->fesih_ihbar_gun) : null;
        $sozKart[] = ['id' => (int) $s->id, 'ted_id' => (int) $s->tedarikci_id, 'ted' => $tedAd[$s->tedarikci_id] ?? '—',
            'sozlesme_no' => $s->sozlesme_no, 'baslik' => $s->baslik, 'imza_tarihi' => $s->imza_tarihi, 'imzalayan' => $s->imzalayan,
            'baslangic' => $s->baslangic, 'bitis' => $s->bitis, 'odeme_vade_gun' => $s->odeme_vade_gun, 'teslim_suresi' => $s->teslim_suresi,
            'fiyat_maddesi' => $s->fiyat_maddesi, 'artis_tavan' => $s->artis_tavan !== null ? (float) $s->artis_tavan : null, 'fiyat_maddesi_not' => $s->fiyat_maddesi_not,
            'fesih_ihbar_gun' => $s->fesih_ihbar_gun, 'fesih_son' => $fesihSon ? $fesihSon->toDateString() : null, 'fesih_kalan' => $fesihSon ? (int) $bugun->diffInDays($fesihSon, false) : null,
            'not' => $s->not, 'aktif' => (int) $s->aktif, 'durum' => $durum, 'kalan' => $kalan, 'ilerleme' => $ilerleme,
            'kalemler' => $kk->map(fn ($a) => ['id' => (int) $a->id, 'malzeme_id' => (int) $a->malzeme_id, 'ad' => $malAd[$a->malzeme_id] ?? '—', 'fiyat' => (float) $a->birim_fiyat, 'birim' => $a->birim,
                'taahhut' => $a->taahhut_miktar !== null ? (float) $a->taahhut_miktar : null, 'ihlal' => (int) ($kartIdx[$a->id]['ihlal_say'] ?? 0)])->values()->all(),
            'ihlal' => (int) $kk->sum(fn ($a) => $kartIdx[$a->id]['ihlal_say'] ?? 0), 'fazla' => round((float) $kk->sum(fn ($a) => $kartIdx[$a->id]['fazla'] ?? 0), 2),
            'belgeler' => $belgeJson($belgeSoz[$s->id] ?? [])];
    }
    usort($sozKart, fn ($x, $y) => [$y['ihlal'] > 0, -($sira[$x['durum']] ?? 9)] <=> [$x['ihlal'] > 0, -($sira[$y['durum']] ?? 9)]);
    $ipucu = [];
    foreach ($kalemler as $k) $ipucu[$k->tedarikci_id . '-' . $k->malzeme_id] = ['f' => (float) $k->birim_fiyat, 'b' => $birimKis[$k->alis_birim_id] ?? '', 't' => substr((string) $k->tarih, 0, 10)];
    $malBirim = []; foreach ($malzemeler as $m) $malBirim[$m->id] = $birimKis[$m->temel_birim_id] ?? '';
    $yukLimit = ini_get('upload_max_filesize');
    return view('fiyat_anlasmalari', compact('kartlar', 'sozKart', 'kpi', 'tedarikciler', 'malzemeler', 'birimler', 'ipucu', 'malBirim', 'salt', 'duzenler', 'yukLimit'));
});

// Tekli anlaşma: ekle / düzenle (+ belge)
Route::post('/fiyat-anlasmalari/ekle', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (_faYetki($bp) !== 'duzenle') return back()->with('hata', 'Fiyat anlaşmalarını düzenleme yetkin yok.');
    _fiyatAnlasmaEnsure(); $sid = _webSubeId();
    $tid = (int) $r->input('tedarikci_id'); $mid = (int) $r->input('malzeme_id');
    if (!$tid || !$mid) return back()->with('hata', 'Tedarikçi ve malzeme seçin.');
    $fiyat = (float) str_replace(',', '.', (string) $r->input('birim_fiyat', 0));
    if ($fiyat <= 0) return back()->with('hata', 'Anlaşma fiyatı girin.');
    $bas = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->input('baslangic')) ? $r->input('baslangic') : null;
    $bit = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->input('bitis')) ? $r->input('bitis') : null;
    if ($bas && $bit && $bit < $bas) return back()->with('hata', 'Bitiş tarihi başlangıçtan önce olamaz.');
    $taah = (float) str_replace(',', '.', (string) $r->input('taahhut_miktar'));
    $veri = ['tedarikci_id' => $tid, 'malzeme_id' => $mid, 'birim_fiyat' => $fiyat, 'birim' => mb_substr(trim((string) $r->input('birim')), 0, 20) ?: null,
        'baslangic' => $bas, 'bitis' => $bit, 'not' => trim((string) $r->input('not')) ?: null, 'taahhut_miktar' => $taah > 0 ? $taah : null, 'updated_at' => now()];
    $id = (int) $r->input('id'); $mesaj = 'Fiyat anlaşması eklendi. Bu tedarikçiye verilen siparişlerde bu fiyat kullanılacak.';
    if ($id && DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('id', $id)->exists()) {
        DB::table('fiyat_anlasmalari')->where('id', $id)->update($veri);
        $mesaj = 'Anlaşma güncellendi.';
    } else {
        $id = DB::table('fiyat_anlasmalari')->insertGetId($veri + ['sube_id' => $sid, 'aktif' => 1, 'created_at' => now()]);
    }
    [$n, $hata] = _faBelgeKaydet($r, $sid, null, $id, $bp->id);
    if ($n) $mesaj .= ' ' . $n . ' belge eklendi.';
    return back()->with('ok', $mesaj)->with('hata', $hata ? implode(' ', $hata) : null);
});
Route::post('/fiyat-anlasmalari/durum', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (_faYetki($bp) !== 'duzenle') return back()->with('hata', 'Fiyat anlaşmalarını düzenleme yetkin yok.');
    _fiyatAnlasmaEnsure(); $sid = _webSubeId();
    $a = DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('id', (int) $r->input('id'))->first();
    if ($a) {
        if ($r->input('sil')) {
            foreach (DB::table('sozlesme_belgeleri')->where('sube_id', $sid)->where('anlasma_id', $a->id)->get() as $b) _faBelgeSil($b);
            DB::table('fiyat_anlasmalari')->where('id', $a->id)->delete();
        } else DB::table('fiyat_anlasmalari')->where('id', $a->id)->update(['aktif' => $a->aktif ? 0 : 1, 'updated_at' => now()]);
    }
    return back()->with('ok', $r->input('sil') ? 'Anlaşma silindi.' : 'Anlaşma durumu güncellendi.');
});

// Çerçeve sözleşme: ekle / düzenle — başlık + şartlar + kalemler (her kalem bir fiyat_anlasmalari satırı) + belgeler
Route::post('/fiyat-anlasmalari/sozlesme', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (_faYetki($bp) !== 'duzenle') return back()->with('hata', 'Fiyat anlaşmalarını düzenleme yetkin yok.');
    _fiyatAnlasmaEnsure(); $sid = _webSubeId();
    $tid = (int) $r->input('tedarikci_id');
    if (!$tid) return back()->with('hata', 'Tedarikçi seçin.');
    $tarih = fn ($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->input($k)) ? $r->input($k) : null;
    $bas = $tarih('baslangic'); $bit = $tarih('bitis');
    if ($bas && $bit && $bit < $bas) return back()->with('hata', 'Bitiş tarihi başlangıçtan önce olamaz.');
    $sayi = fn ($k) => ($v = trim((string) $r->input($k))) === '' ? null : (float) str_replace(',', '.', $v);
    // kalemler
    $mids = (array) $r->input('kalem_malzeme', []); $fiyatlar = (array) $r->input('kalem_fiyat', []); $birimler = (array) $r->input('kalem_birim', []);
    $taahlar = (array) $r->input('kalem_taahhut', []); $kids = (array) $r->input('kalem_id', []);
    $satirlar = [];
    foreach ($mids as $i => $m) {
        $m = (int) $m; $f = (float) str_replace(',', '.', (string) ($fiyatlar[$i] ?? 0));
        if (!$m || $f <= 0) continue;
        $tq = (float) str_replace(',', '.', (string) ($taahlar[$i] ?? 0));
        $satirlar[] = ['kid' => (int) ($kids[$i] ?? 0), 'malzeme_id' => $m, 'birim_fiyat' => $f, 'birim' => mb_substr(trim((string) ($birimler[$i] ?? '')), 0, 20) ?: null, 'taahhut_miktar' => $tq > 0 ? $tq : null];
    }
    if (!$satirlar) return back()->with('hata', 'En az bir malzeme ve fiyatı girin.');
    $fm = in_array($r->input('fiyat_maddesi'), ['sabit', 'tavan', 'endeks', 'serbest'], true) ? $r->input('fiyat_maddesi') : 'sabit';
    $veri = ['tedarikci_id' => $tid, 'sozlesme_no' => mb_substr(trim((string) $r->input('sozlesme_no')), 0, 60) ?: null, 'baslik' => mb_substr(trim((string) $r->input('baslik')), 0, 120) ?: null,
        'imza_tarihi' => $tarih('imza_tarihi'), 'imzalayan' => mb_substr(trim((string) $r->input('imzalayan')), 0, 80) ?: null, 'baslangic' => $bas, 'bitis' => $bit,
        'odeme_vade_gun' => ($v = $sayi('odeme_vade_gun')) !== null ? (int) $v : null, 'teslim_suresi' => mb_substr(trim((string) $r->input('teslim_suresi')), 0, 60) ?: null,
        'fiyat_maddesi' => $fm, 'artis_tavan' => $fm === 'tavan' ? $sayi('artis_tavan') : null, 'fiyat_maddesi_not' => mb_substr(trim((string) $r->input('fiyat_maddesi_not')), 0, 200) ?: null,
        'fesih_ihbar_gun' => ($v = $sayi('fesih_ihbar_gun')) !== null ? (int) $v : null, 'not' => trim((string) $r->input('not')) ?: null, 'updated_at' => now()];
    $id = (int) $r->input('id');
    $mevcut = $id ? DB::table('fiyat_sozlesmeleri')->where('sube_id', $sid)->where('id', $id)->first() : null;
    DB::beginTransaction();
    try {
        if ($mevcut) { DB::table('fiyat_sozlesmeleri')->where('id', $id)->update($veri); $aktif = (int) $mevcut->aktif; }
        else { $id = DB::table('fiyat_sozlesmeleri')->insertGetId($veri + ['sube_id' => $sid, 'aktif' => 1, 'olusturan_id' => $bp->id, 'created_at' => now()]); $aktif = 1; }
        $eskiIdler = DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('sozlesme_id', $id)->pluck('id')->map(fn ($x) => (int) $x)->all();
        $tut = [];
        foreach ($satirlar as $s) {
            $kv = ['tedarikci_id' => $tid, 'malzeme_id' => $s['malzeme_id'], 'birim_fiyat' => $s['birim_fiyat'], 'birim' => $s['birim'], 'taahhut_miktar' => $s['taahhut_miktar'],
                'baslangic' => $bas, 'bitis' => $bit, 'aktif' => $aktif, 'sozlesme_id' => $id, 'updated_at' => now()];
            if ($s['kid'] && in_array($s['kid'], $eskiIdler, true)) { DB::table('fiyat_anlasmalari')->where('id', $s['kid'])->update($kv); $tut[] = $s['kid']; }
            else $tut[] = (int) DB::table('fiyat_anlasmalari')->insertGetId($kv + ['sube_id' => $sid, 'created_at' => now()]);
        }
        $silinecek = array_diff($eskiIdler, $tut);
        if ($silinecek) DB::table('fiyat_anlasmalari')->whereIn('id', $silinecek)->delete();
        // düzenlemede kaldırılan belgeler
        $belgeSil = array_filter(array_map('intval', (array) $r->input('belge_sil', [])));
        if ($belgeSil) foreach (DB::table('sozlesme_belgeleri')->where('sube_id', $sid)->where('sozlesme_id', $id)->whereIn('id', $belgeSil)->get() as $b) _faBelgeSil($b);
        DB::commit();
    } catch (\Throwable $e) { DB::rollBack(); return back()->with('hata', 'Sözleşme kaydedilemedi.'); }
    [$n, $hata] = _faBelgeKaydet($r, $sid, $id, null, $bp->id);
    $mesaj = ($mevcut ? 'Sözleşme güncellendi' : 'Sözleşme eklendi') . ' (' . count($satirlar) . ' malzeme' . ($n ? ', ' . $n . ' belge' : '') . '). Bu fiyatlar siparişte kullanılacak.';
    return back()->with('ok', $mesaj)->with('hata', $hata ? implode(' ', $hata) : null)->with('fa_sekme', 'sozlesme');
});
Route::post('/fiyat-anlasmalari/sozlesme-durum', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (_faYetki($bp) !== 'duzenle') return back()->with('hata', 'Fiyat anlaşmalarını düzenleme yetkin yok.');
    _fiyatAnlasmaEnsure(); $sid = _webSubeId();
    $s = DB::table('fiyat_sozlesmeleri')->where('sube_id', $sid)->where('id', (int) $r->input('id'))->first();
    if (!$s) return back();
    if ($r->input('sil')) {
        foreach (DB::table('sozlesme_belgeleri')->where('sube_id', $sid)->where('sozlesme_id', $s->id)->get() as $b) _faBelgeSil($b);
        DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('sozlesme_id', $s->id)->delete();
        DB::table('fiyat_sozlesmeleri')->where('id', $s->id)->delete();
        return back()->with('ok', 'Sözleşme ve malzeme fiyatları silindi.')->with('fa_sekme', 'sozlesme');
    }
    $yeni = $s->aktif ? 0 : 1;
    DB::table('fiyat_sozlesmeleri')->where('id', $s->id)->update(['aktif' => $yeni, 'updated_at' => now()]);
    DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('sozlesme_id', $s->id)->update(['aktif' => $yeni, 'updated_at' => now()]);
    return back()->with('ok', $yeni ? 'Sözleşme yeniden yürürlükte.' : 'Sözleşme durduruldu; fiyatları siparişte kullanılmayacak.')->with('fa_sekme', 'sozlesme');
});
// Belge: görüntüle / indir (yalnız yönetenler — departman sorumlusu belge açamaz)
Route::get('/fiyat-anlasmalari/belge/{id}', function (Request $r, $id) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!in_array(_faYetki($bp), ['gor', 'duzenle'], true)) abort(403);
    _fiyatAnlasmaEnsure();
    $b = DB::table('sozlesme_belgeleri')->where('sube_id', _webSubeId())->where('id', (int) $id)->first();
    if (!$b) abort(404);
    $yol = storage_path('app/' . $b->yol);
    if (!is_file($yol)) abort(404);
    $ad = preg_replace('/[\r\n"]/', '', $b->ad);
    if ($r->query('indir')) return response()->download($yol, $ad);
    return response()->file($yol, ['Content-Type' => $b->mime ?: 'application/octet-stream', 'Content-Disposition' => 'inline; filename="' . $ad . '"', 'X-Content-Type-Options' => 'nosniff']);
})->where('id', '[0-9]+');
Route::post('/fiyat-anlasmalari/belge-sil', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (_faYetki($bp) !== 'duzenle') return back()->with('hata', 'Fiyat anlaşmalarını düzenleme yetkin yok.');
    _fiyatAnlasmaEnsure();
    $b = DB::table('sozlesme_belgeleri')->where('sube_id', _webSubeId())->where('id', (int) $r->input('id'))->first();
    if ($b) _faBelgeSil($b);
    return back()->with('ok', 'Belge silindi.');
});

// ============================ RAPORLAR ============================
if (!function_exists('_faRaporVeri')) {
    function _faRaporVeri($sid, $bas, $bit, $tedId = 0)
    {
        _fiyatAnlasmaEnsure();
        $tedAd = DB::table('tedarikciler')->pluck('ad', 'id'); $tedTel = DB::table('tedarikciler')->pluck('telefon', 'id');
        $malAd = DB::table('malzemeler')->pluck('ad', 'id');
        $birimKis = Schema::hasTable('birimler') ? DB::table('birimler')->pluck('kisaltma', 'id') : collect();
        $anl = DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->get();
        $soz = DB::table('fiyat_sozlesmeleri')->where('sube_id', $sid)->get()->keyBy('id');
        if ($tedId) $anl = $anl->where('tedarikci_id', $tedId)->values();
        // referans için aralıktan 1 yıl önce başlayan kalemler
        $hepsi = _faKalemler($sid, \Carbon\Carbon::parse($bas)->subDays(365)->toDateString(), $bit);
        $aralik = $hepsi->filter(fn ($k) => substr((string) $k->tarih, 0, 10) >= $bas);
        $aralikTed = $tedId ? $aralik->where('tedarikci_id', $tedId) : $aralik;
        $bn = fn ($k) => _faBirimNorm($birimKis[$k->alis_birim_id] ?? '');
        $anlBul = function ($k) use ($anl, $birimKis) {
            foreach ($anl as $a) if ($a->aktif && _faEslesir($a, $k, $birimKis)) return $a;
            return null;
        };
        $out = ['tasarruf' => ['aylik' => [], 'satir' => [], 'toplam' => 0.0, 'kayip' => 0.0], 'fazla' => ['ted' => [], 'toplam' => 0.0, 'adet' => 0],
            'taahhut' => [], 'kapsama' => ['toplam' => 0.0, 'anlasmali' => 0.0, 'oran' => 0, 'oneri' => []], 'zarar' => [], 'yenileme' => ['satir' => [], 'ted' => []],
            'tedarikci' => [], 'takvim' => []];
        $tedOz = [];
        $tedOzEkle = function ($tid) use (&$tedOz, $tedAd) { if (!isset($tedOz[$tid])) $tedOz[$tid] = ['ted' => $tedAd[$tid] ?? '—', 'anlasma' => 0, 'fatura' => 0, 'uyumlu' => 0, 'tasarruf' => 0.0, 'fazla' => 0.0, 'teslim' => null]; };
        // --- 1+2+4: aralıktaki kalemleri tek geçişte işle
        $anlasmaliMal = [];
        foreach ($aralikTed as $k) {
            $tutar = (float) $k->birim_fiyat * (float) $k->miktar;
            $out['kapsama']['toplam'] += $tutar;
            $a = $anlBul($k);
            if (!$a) continue;
            $out['kapsama']['anlasmali'] += $tutar;
            $p = (float) $k->birim_fiyat; $af = (float) $a->birim_fiyat; $q = (float) $k->miktar;
            $tedOzEkle((int) $a->tedarikci_id);
            $tedOz[$a->tedarikci_id]['fatura']++;
            if ($p > $af * 1.005) {
                $fz = ($p - $af) * $q;
                $out['fazla']['toplam'] += $fz; $out['fazla']['adet']++; $tedOz[$a->tedarikci_id]['fazla'] += $fz;
                $out['fazla']['ted'][$a->tedarikci_id]['ted'] = $tedAd[$a->tedarikci_id] ?? '—';
                $out['fazla']['ted'][$a->tedarikci_id]['tel'] = $tedTel[$a->tedarikci_id] ?? null;
                $out['fazla']['ted'][$a->tedarikci_id]['satir'][] = ['tarih' => substr((string) $k->tarih, 0, 10), 'fatura_no' => $k->fatura_no, 'mal' => $malAd[$k->malzeme_id] ?? '—',
                    'birim' => $a->birim ?: ($birimKis[$k->alis_birim_id] ?? ''), 'anlasma' => $af, 'fatura' => $p, 'miktar' => $q, 'fazla' => round($fz, 2)];
            } else $tedOz[$a->tedarikci_id]['uyumlu']++;
            // tasarruf referansı: ±30 gün diğer tedarikçi ortalaması → yoksa anlaşma öncesi son alış
            $t = \Carbon\Carbon::parse($k->tarih);
            $diger = $hepsi->filter(fn ($x) => (int) $x->malzeme_id === (int) $k->malzeme_id && (int) $x->tedarikci_id !== (int) $k->tedarikci_id && $bn($x) === $bn($k)
                && abs($t->diffInDays(\Carbon\Carbon::parse($x->tarih), false)) <= 30);
            $ref = null; $refK = null;
            if ($diger->count()) { $ref = (float) $diger->avg('birim_fiyat'); $refK = 'piyasa'; }
            elseif ($a->baslangic) {
                $onceki = $hepsi->filter(fn ($x) => (int) $x->malzeme_id === (int) $k->malzeme_id && $bn($x) === $bn($k) && substr((string) $x->tarih, 0, 10) < substr((string) $a->baslangic, 0, 10))->last();
                if ($onceki) { $ref = (float) $onceki->birim_fiyat; $refK = 'önceki fiyat'; }
            }
            if ($ref !== null) {
                $ts = ($ref - $p) * $q; $ay = substr((string) $k->tarih, 0, 7);
                $out['tasarruf']['aylik'][$ay] = ($out['tasarruf']['aylik'][$ay] ?? 0) + $ts;
                $out['tasarruf']['toplam'] += $ts; if ($ts < 0) $out['tasarruf']['kayip'] += -$ts;
                $tedOz[$a->tedarikci_id]['tasarruf'] += $ts;
                $sk = (int) $a->id;
                if (!isset($out['tasarruf']['satir'][$sk])) $out['tasarruf']['satir'][$sk] = ['mal' => $malAd[$a->malzeme_id] ?? '—', 'ted' => $tedAd[$a->tedarikci_id] ?? '—', 'anlasma' => $af, 'birim' => $a->birim, 'miktar' => 0.0, 'ref_top' => 0.0, 'ref_say' => 0, 'tasarruf' => 0.0, 'kaynak' => $refK];
                $out['tasarruf']['satir'][$sk]['miktar'] += $q; $out['tasarruf']['satir'][$sk]['ref_top'] += $ref; $out['tasarruf']['satir'][$sk]['ref_say']++; $out['tasarruf']['satir'][$sk]['tasarruf'] += $ts;
            }
        }
        ksort($out['tasarruf']['aylik']);
        $out['tasarruf']['satir'] = collect($out['tasarruf']['satir'])->map(function ($s) { $s['ref'] = $s['ref_say'] ? $s['ref_top'] / $s['ref_say'] : null; return $s; })->sortByDesc('tasarruf')->values()->all();
        $out['fazla']['ted'] = collect($out['fazla']['ted'])->map(function ($t) { $t['toplam'] = array_sum(array_column($t['satir'], 'fazla')); return $t; })->sortByDesc('toplam')->values()->all();
        $out['kapsama']['oran'] = $out['kapsama']['toplam'] > 0 ? round($out['kapsama']['anlasmali'] / $out['kapsama']['toplam'] * 100) : 0;
        // kapsama önerisi: şu an yürürlükte anlaşması olmayan, en çok harcanan malzemeler
        $bugun = today()->toDateString();
        $yururlukMal = $anl->filter(fn ($a) => $a->aktif && (!$a->bitis || $a->bitis >= $bugun))->pluck('malzeme_id')->map(fn ($x) => (int) $x)->unique()->all();
        $out['kapsama']['oneri'] = $aralikTed->filter(fn ($k) => !in_array((int) $k->malzeme_id, $yururlukMal, true))->groupBy('malzeme_id')
            ->map(fn ($g, $mid) => ['mal' => $malAd[$mid] ?? '—', 'harcama' => round($g->sum(fn ($k) => (float) $k->birim_fiyat * (float) $k->miktar), 2), 'alis' => $g->count(),
                'ted_say' => $g->pluck('tedarikci_id')->unique()->count(), 'min' => (float) $g->min('birim_fiyat'), 'max' => (float) $g->max('birim_fiyat'), 'birim' => $birimKis[$g->last()->alis_birim_id] ?? '',
                'oynaklik' => (float) $g->min('birim_fiyat') > 0 ? round(((float) $g->max('birim_fiyat') / (float) $g->min('birim_fiyat') - 1) * 100) : 0])
            ->sortByDesc('harcama')->take(10)->values()->all();
        // --- 3: taahhüt (aylık) — aralıktaki aylar × taahhütlü anlaşmalar
        $aylar = []; $c = \Carbon\Carbon::parse($bas)->startOfMonth(); $son = \Carbon\Carbon::parse($bit)->startOfMonth();
        while ($c->lte($son) && count($aylar) < 24) { $aylar[] = $c->format('Y-m'); $c->addMonth(); }
        foreach ($anl->filter(fn ($a) => (float) $a->taahhut_miktar > 0) as $a) {
            $satir = ['mal' => $malAd[$a->malzeme_id] ?? '—', 'ted' => $tedAd[$a->tedarikci_id] ?? '—', 'taahhut' => (float) $a->taahhut_miktar, 'birim' => $a->birim, 'aylar' => []];
            foreach ($aylar as $ay) {
                $ayBas = $ay . '-01'; $ayBit = \Carbon\Carbon::parse($ayBas)->endOfMonth()->toDateString();
                if (($a->baslangic && $a->baslangic > $ayBit) || ($a->bitis && $a->bitis < $ayBas)) continue;
                $mik = (float) $aralik->filter(fn ($k) => substr((string) $k->tarih, 0, 7) === $ay && _faEslesir($a, $k, $birimKis))->sum('miktar');
                $satir['aylar'][$ay] = ['miktar' => $mik, 'oran' => round($mik / (float) $a->taahhut_miktar * 100)];
            }
            if ($satir['aylar']) $out['taahhut'][] = $satir;
        }
        // --- 5: anlaşma zarara döndü mü — yürürlükteki her anlaşma: piyasa (son 30 gün diğer tedarikçi min) + hal
        $halSon = null; $hal = collect();
        try { if (Schema::hasTable('hal_fiyatlari')) { $halSon = DB::table('hal_fiyatlari')->where('sube_id', $sid)->where('ortalama', '>', 0)->max('tarih'); if ($halSon) $hal = DB::table('hal_fiyatlari')->where('sube_id', $sid)->where('tarih', $halSon)->where('ortalama', '>', 0)->get(['urun_ad', 'birim', 'ortalama']); } } catch (\Throwable $e) {}
        $otuz = today()->subDays(30)->toDateString();
        foreach ($anl->filter(fn ($a) => $a->aktif && (!$a->bitis || $a->bitis >= $bugun)) as $a) {
            $pi = $hepsi->filter(fn ($k) => (int) $k->malzeme_id === (int) $a->malzeme_id && (int) $k->tedarikci_id !== (int) $a->tedarikci_id && substr((string) $k->tarih, 0, 10) >= $otuz
                && (!$a->birim || !$k->alis_birim_id || $bn($k) === _faBirimNorm($a->birim)));
            $piyasa = $pi->count() ? (float) $pi->min('birim_fiyat') : null;
            $piyasaTed = $piyasa ? ($tedAd[$pi->sortBy('birim_fiyat')->first()->tedarikci_id] ?? null) : null;
            $halOrt = null;
            if ($hal->count() && function_exists('_halEslesir') && in_array(_faBirimNorm($a->birim), ['kg', ''], true)) {
                $es = $hal->filter(fn ($h) => _halEslesir($malAd[$a->malzeme_id] ?? '', $h->urun_ad) && mb_strtolower((string) $h->birim, 'UTF-8') === 'kg')->pluck('ortalama')->map(fn ($x) => (float) $x)->sort()->values();
                if ($es->count()) $halOrt = $es[intdiv($es->count(), 2)];
            }
            if ($piyasa === null && $halOrt === null) continue;
            $af = (float) $a->birim_fiyat;
            $pf = $piyasa ? round(($af - $piyasa) / $piyasa * 100) : null; $hf = $halOrt ? round(($af - $halOrt) / $halOrt * 100) : null;
            $out['zarar'][] = ['mal' => $malAd[$a->malzeme_id] ?? '—', 'ted' => $tedAd[$a->tedarikci_id] ?? '—', 'anlasma' => $af, 'birim' => $a->birim, 'bitis' => $a->bitis,
                'piyasa' => $piyasa, 'piyasa_ted' => $piyasaTed, 'piyasa_fark' => $pf, 'hal' => $halOrt, 'hal_fark' => $hf,
                'zarar' => ($pf !== null && $pf >= 5) || ($hf !== null && $hf >= 25)];
        }
        usort($out['zarar'], fn ($x, $y) => [$y['zarar'], $y['piyasa_fark'] ?? -999] <=> [$x['zarar'], $x['piyasa_fark'] ?? -999]);
        $out['hal_tarih'] = $halSon;
        // --- 6: yenileme geçmişi — aynı tedarikçi+malzemenin ardışık anlaşmaları
        $tedArtis = [];
        foreach ($anl->groupBy(fn ($a) => $a->tedarikci_id . '-' . $a->malzeme_id) as $g) {
            if ($g->count() < 2) continue;
            $g = $g->sortBy(fn ($a) => ($a->baslangic ?: substr((string) $a->created_at, 0, 10)) . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT))->values();
            $adimlar = [];
            for ($i = 0; $i < $g->count(); $i++) {
                $a = $g[$i]; $onc = $i ? $g[$i - 1] : null;
                $deg = $onc && (float) $onc->birim_fiyat > 0 ? round(((float) $a->birim_fiyat - (float) $onc->birim_fiyat) / (float) $onc->birim_fiyat * 100, 1) : null;
                $adimlar[] = ['fiyat' => (float) $a->birim_fiyat, 'baslangic' => $a->baslangic ?: substr((string) $a->created_at, 0, 10), 'degisim' => $deg];
                if ($deg !== null) $tedArtis[$a->tedarikci_id][] = $deg;
            }
            $f0 = $adimlar[0]['fiyat']; $fn = end($adimlar)['fiyat'];
            $out['yenileme']['satir'][] = ['mal' => $malAd[$g[0]->malzeme_id] ?? '—', 'ted' => $tedAd[$g[0]->tedarikci_id] ?? '—', 'birim' => $g->last()->birim, 'adimlar' => $adimlar,
                'toplam' => $f0 > 0 ? round(($fn - $f0) / $f0 * 100, 1) : null];
        }
        foreach ($tedArtis as $tid => $l) $out['yenileme']['ted'][] = ['ted' => $tedAd[$tid] ?? '—', 'ort' => round(array_sum($l) / count($l), 1), 'say' => count($l)];
        usort($out['yenileme']['ted'], fn ($x, $y) => $y['ort'] <=> $x['ort']);
        // --- 7: tedarikçi özeti (+ teslim performansı: sipariş beklenen tarih vs mal kabul)
        foreach ($anl as $a) { $tedOzEkle((int) $a->tedarikci_id); if ($a->aktif && (!$a->bitis || $a->bitis >= $bugun)) $tedOz[$a->tedarikci_id]['anlasma']++; }
        try {
            if (Schema::hasTable('satinalma_siparisleri') && Schema::hasTable('mal_kabuller')) {
                foreach (array_keys($tedOz) as $tid) {
                    $sp = DB::table('satinalma_siparisleri')->where('sube_id', $sid)->where('tedarikci_id', $tid)->whereNotNull('beklenen_teslim')->whereBetween('tarih', [$bas, $bit])->whereNotIn('durum', ['iptal', 'taslak'])->get(['id', 'beklenen_teslim', 'tedarikci_teslim']);
                    $say = 0; $zam = 0;
                    foreach ($sp as $s) {
                        $mk = DB::table('mal_kabuller')->where('siparis_id', $s->id)->min('tarih');
                        if (!$mk) continue;
                        $say++; if (substr((string) $mk, 0, 10) <= substr((string) ($s->tedarikci_teslim ?: $s->beklenen_teslim), 0, 10)) $zam++;
                    }
                    if ($say) $tedOz[$tid]['teslim'] = round($zam / $say * 100);
                }
            }
        } catch (\Throwable $e) {}
        $out['tedarikci'] = collect($tedOz)->map(function ($t) { $t['uyum'] = $t['fatura'] ? round($t['uyumlu'] / $t['fatura'] * 100) : null; return $t; })->sortByDesc('tasarruf')->values()->all();
        // --- 8: yenileme takvimi — önümüzdeki 90 gün (sözleşme tek satır; fesih son günü)
        $ufuk = today()->addDays(90)->toDateString();
        foreach ($soz as $s) {
            if (!$s->aktif || !$s->bitis || $s->bitis < $bugun || ($tedId && (int) $s->tedarikci_id !== (int) $tedId)) continue;
            $fesih = $s->fesih_ihbar_gun ? \Carbon\Carbon::parse($s->bitis)->subDays((int) $s->fesih_ihbar_gun)->toDateString() : null;
            $ilk = $fesih && $fesih < $s->bitis ? $fesih : $s->bitis;
            if ($ilk > $ufuk) continue;
            $out['takvim'][] = ['tur' => 'sozlesme', 'ad' => ($s->sozlesme_no ? $s->sozlesme_no . ' · ' : '') . ($s->baslik ?: 'Sözleşme'), 'ted' => $tedAd[$s->tedarikci_id] ?? '—',
                'kalem' => $anl->where('sozlesme_id', $s->id)->count(), 'bitis' => $s->bitis, 'fesih' => $fesih, 'ilk' => $ilk];
        }
        foreach ($anl->filter(fn ($a) => !$a->sozlesme_id && $a->aktif && $a->bitis && $a->bitis >= $bugun && $a->bitis <= $ufuk) as $a)
            $out['takvim'][] = ['tur' => 'anlasma', 'ad' => $malAd[$a->malzeme_id] ?? '—', 'ted' => $tedAd[$a->tedarikci_id] ?? '—', 'kalem' => 1, 'bitis' => $a->bitis, 'fesih' => null, 'ilk' => $a->bitis];
        usort($out['takvim'], fn ($x, $y) => strcmp($x['ilk'], $y['ilk']));
        return $out;
    }
}
if (!function_exists('_faRaporFiltre')) {
    function _faRaporFiltre(Request $r)
    {
        $bit = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bit')) ? $r->query('bit') : today()->toDateString();
        $bas = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bas')) ? $r->query('bas') : today()->subMonths(6)->toDateString();
        if ($bas > $bit) [$bas, $bit] = [$bit, $bas];
        return [$bas, $bit, (int) $r->query('ted')];
    }
}
Route::get('/fiyat-anlasmalari/rapor', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!in_array(_faYetki($bp), ['gor', 'duzenle'], true)) return redirect('/fiyat-anlasmalari');
    $sid = _webSubeId();
    [$bas, $bit, $ted] = _faRaporFiltre($r);
    $rapor = _faRaporVeri($sid, $bas, $bit, $ted);
    $tedarikciler = DB::table('tedarikciler')->orderBy('ad')->get(['id', 'ad']);
    return view('fiyat_anlasma_rapor', compact('rapor', 'bas', 'bit', 'ted', 'tedarikciler'));
});
// Excel (CSV, Türkçe Excel uyumlu: UTF-8 BOM + noktalı virgül)
Route::get('/fiyat-anlasmalari/rapor-indir', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!in_array(_faYetki($bp), ['gor', 'duzenle'], true)) abort(403);
    $sid = _webSubeId();
    [$bas, $bit, $ted] = _faRaporFiltre($r);
    $d = _faRaporVeri($sid, $bas, $bit, $ted);
    $n = fn ($v) => $v === null ? '' : number_format((float) $v, 2, ',', '');
    $tr = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d.m.Y') : '';
    $tur = (string) $r->query('r', 'tasarruf');
    $bas_ = []; $satir = [];
    switch ($tur) {
        case 'fazla':
            $bas_ = ['Tedarikçi', 'Tarih', 'Fatura No', 'Malzeme', 'Birim', 'Anlaşma Fiyatı', 'Faturadaki Fiyat', 'Miktar', 'Fazla Ödenen'];
            foreach ($d['fazla']['ted'] as $t) foreach ($t['satir'] as $s) $satir[] = [$t['ted'], $tr($s['tarih']), $s['fatura_no'], $s['mal'], $s['birim'], $n($s['anlasma']), $n($s['fatura']), $n($s['miktar']), $n($s['fazla'])];
            break;
        case 'taahhut':
            $bas_ = ['Malzeme', 'Tedarikçi', 'Ay', 'Aylık Taahhüt', 'Gerçekleşen', 'Oran %'];
            foreach ($d['taahhut'] as $t) foreach ($t['aylar'] as $ay => $v) $satir[] = [$t['mal'], $t['ted'], $ay, $n($t['taahhut']), $n($v['miktar']), $v['oran']];
            break;
        case 'kapsama':
            $bas_ = ['Malzeme (anlaşmasız)', 'Harcama', 'Alış Sayısı', 'Tedarikçi Sayısı', 'En Düşük', 'En Yüksek', 'Birim', 'Fiyat Oynaklığı %'];
            foreach ($d['kapsama']['oneri'] as $s) $satir[] = [$s['mal'], $n($s['harcama']), $s['alis'], $s['ted_say'], $n($s['min']), $n($s['max']), $s['birim'], $s['oynaklik']];
            break;
        case 'zarar':
            $bas_ = ['Malzeme', 'Tedarikçi', 'Anlaşma Fiyatı', 'Birim', 'Piyasa (30 gün en düşük)', 'Piyasa Farkı %', 'Hal Ortalaması', 'Hal Farkı %', 'Bitiş', 'Durum'];
            foreach ($d['zarar'] as $s) $satir[] = [$s['mal'], $s['ted'], $n($s['anlasma']), $s['birim'], $n($s['piyasa']), $s['piyasa_fark'], $n($s['hal']), $s['hal_fark'], $tr($s['bitis']), $s['zarar'] ? 'Yeniden pazarlık' : 'Uygun'];
            break;
        case 'yenileme':
            $bas_ = ['Malzeme', 'Tedarikçi', 'Başlangıç', 'Fiyat', 'Önceki Anlaşmaya Göre %'];
            foreach ($d['yenileme']['satir'] as $s) foreach ($s['adimlar'] as $a) $satir[] = [$s['mal'], $s['ted'], $tr($a['baslangic']), $n($a['fiyat']), $a['degisim']];
            break;
        case 'tedarikci':
            $bas_ = ['Tedarikçi', 'Yürürlükteki Anlaşma', 'Anlaşmalı Fatura Kalemi', 'Fatura Uyumu %', 'Tasarruf', 'Fazla Ödenen', 'Zamanında Teslim %'];
            foreach ($d['tedarikci'] as $s) $satir[] = [$s['ted'], $s['anlasma'], $s['fatura'], $s['uyum'], $n($s['tasarruf']), $n($s['fazla']), $s['teslim']];
            break;
        case 'takvim':
            $bas_ = ['Tür', 'Ad', 'Tedarikçi', 'Malzeme Sayısı', 'Fesih İçin Son Gün', 'Bitiş'];
            foreach ($d['takvim'] as $s) $satir[] = [$s['tur'] === 'sozlesme' ? 'Sözleşme' : 'Anlaşma', $s['ad'], $s['ted'], $s['kalem'], $tr($s['fesih']), $tr($s['bitis'])];
            break;
        default:
            $tur = 'tasarruf';
            $bas_ = ['Malzeme', 'Tedarikçi', 'Anlaşma Fiyatı', 'Birim', 'Referans Fiyat', 'Referans', 'Alınan Miktar', 'Tasarruf'];
            foreach ($d['tasarruf']['satir'] as $s) $satir[] = [$s['mal'], $s['ted'], $n($s['anlasma']), $s['birim'], $n($s['ref']), $s['kaynak'], $n($s['miktar']), $n($s['tasarruf'])];
    }
    $esc = fn ($v) => '"' . str_replace('"', '""', (string) $v) . '"';
    $csv = "\xEF\xBB\xBF" . implode(';', array_map($esc, $bas_)) . "\r\n";
    foreach ($satir as $s) $csv .= implode(';', array_map($esc, $s)) . "\r\n";
    return response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="fiyat-anlasma-' . $tur . '-' . $bas . '_' . $bit . '.csv"']);
});

<?php
/*
 * ============================================================================
 *  SATIN ALMACI ONAY LİMİTİ (kişi bazlı) + DEPARTMAN KURALI
 *  - personeller.sa_siparis_limit: satın almacı bu tutara kadar patron onayı beklemeden sipariş geçer (boş/0 = kapalı)
 *  - personeller.sa_aylik_limit : ay içinde kendi onayıyla geçebileceği toplam (boş = sınırsız)
 *  - satinalma_limit_departman  : departman kuralı 'limit' (satın almacı limiti geçerli) | 'onay' (her zaman patron onayı)
 *  - Parçalama koruması: aynı gün aynı tedarikçiye kendi onayıyla geçilenler + bu sipariş ≤ sipariş limiti
 *  - Limit içi siparişler satinalma_siparisleri.onay_tipi='limit_ici' ile işaretlenir, üst yönetime bildirim gider
 *  Ayar ekranı: /satinalma-onay-seviye (Onay Seviyeleri & Limitler)
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_saLimitEnsure')) {
    function _saLimitEnsure()
    {
        if (cache()->get('sa_limit_ensure_ok')) return;
        try {
            if (Schema::hasTable('personeller')) {
                if (!Schema::hasColumn('personeller', 'sa_siparis_limit')) Schema::table('personeller', fn ($t) => $t->decimal('sa_siparis_limit', 14, 2)->nullable());
                if (!Schema::hasColumn('personeller', 'sa_aylik_limit')) Schema::table('personeller', fn ($t) => $t->decimal('sa_aylik_limit', 14, 2)->nullable());
            }
            if (Schema::hasTable('satinalma_siparisleri')) {
                if (!Schema::hasColumn('satinalma_siparisleri', 'onay_tipi')) Schema::table('satinalma_siparisleri', fn ($t) => $t->string('onay_tipi', 20)->nullable()->index());
                if (!Schema::hasColumn('satinalma_siparisleri', 'onaylayan_id')) Schema::table('satinalma_siparisleri', fn ($t) => $t->unsignedBigInteger('onaylayan_id')->nullable());
            }
            if (Schema::hasTable('satinalma_talepleri') && !Schema::hasColumn('satinalma_talepleri', 'sa_onay_tipi')) {
                Schema::table('satinalma_talepleri', fn ($t) => $t->string('sa_onay_tipi', 20)->nullable());
            }
            if (!Schema::hasTable('satinalma_limit_departman')) {
                Schema::create('satinalma_limit_departman', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('sube_id');
                    $t->unsignedBigInteger('departman_id');
                    $t->string('kural', 10)->default('limit'); // limit | onay
                    $t->timestamps();
                    $t->unique(['sube_id', 'departman_id']);
                });
            }
            cache()->put('sa_limit_ensure_ok', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

// Talebin departmanı (id) — departman_id yoksa adından bulunur
if (!function_exists('_saLimitTalepDepId')) {
    function _saLimitTalepDepId($talep)
    {
        $id = (int) ($talep->departman_id ?? 0);
        if ($id) return $id;
        try { return (int) DB::table('departmanlar')->where('sube_id', (int) $talep->sube_id)->where('ad', (string) $talep->departman)->value('id'); } catch (\Throwable $e) { return 0; }
    }
}

// Departman kuralı: 'onay' = her zaman patron onayı, aksi 'limit'
if (!function_exists('_saLimitDepKural')) {
    function _saLimitDepKural($sid, $depId)
    {
        if (!$depId) return 'limit';
        try { return DB::table('satinalma_limit_departman')->where('sube_id', (int) $sid)->where('departman_id', (int) $depId)->value('kural') === 'onay' ? 'onay' : 'limit'; } catch (\Throwable $e) { return 'limit'; }
    }
}

// Satın almacının bu ay kendi onayıyla geçtiği toplam
if (!function_exists('_saLimitAyKullanim')) {
    function _saLimitAyKullanim($sid, $pid)
    {
        try {
            return (float) DB::table('satinalma_siparisleri')->where('sube_id', (int) $sid)->where('onay_tipi', 'limit_ici')->where('onaylayan_id', (int) $pid)
                ->whereBetween('tarih', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('genel_toplam');
        } catch (\Throwable $e) { return 0.0; }
    }
}

// Talepte siparişe hazır kalemler: ⭐ önerilen (yoksa en ucuz teklif) tedarikçi + o tedarikçiden fiyat + stok malzemesi
if (!function_exists('_saLimitKalemler')) {
    function _saLimitKalemler($talepId)
    {
        $out = [];
        foreach (DB::table('talep_kalemleri')->where('talep_id', (int) $talepId)->whereNotNull('malzeme_id')->get() as $k) {
            $q = DB::table('talep_teklifleri')->where('talep_kalem_id', $k->id)->where('fiyat', '>', 0)->whereNotNull('tedarikci_id');
            $tek = $k->onerilen_tedarikci_id ? (clone $q)->where('tedarikci_id', $k->onerilen_tedarikci_id)->orderBy('fiyat')->first() : $q->orderBy('fiyat')->first();
            if (!$tek) continue;
            $out[] = ['kalem' => $k, 'tedarikci_id' => (int) $tek->tedarikci_id, 'tutar' => round((float) $tek->fiyat * (float) $k->miktar, 2)];
        }
        return $out;
    }
}

// Bu satın almacı bu talebi kendi limitiyle onaylayabilir mi? Gerekçeli sonuç döner (UI hem düğmeyi hem nedeni gösterir)
if (!function_exists('_saLimitDurum')) {
    function _saLimitDurum($p, $talep)
    {
        _saLimitEnsure();
        $sid = (int) $talep->sube_id;
        $r = ['goster' => false, 'uygun' => false, 'neden' => null, 'tutar' => 0, 'limit' => 0, 'aylik' => null, 'kullanilan' => 0, 'kalem' => 0, 'kalan_kalem' => 0];
        if (($p->rol ?? '') !== 'satinalmaci') return $r; // patron/müdür zaten onaylar; limit yalnız satın almacı içindir
        $pk = DB::table('personeller')->where('id', (int) $p->id)->first(['sa_siparis_limit', 'sa_aylik_limit']);
        $limit = (float) ($pk->sa_siparis_limit ?? 0);
        $aylik = isset($pk->sa_aylik_limit) && $pk->sa_aylik_limit !== null && (float) $pk->sa_aylik_limit > 0 ? (float) $pk->sa_aylik_limit : null;
        $r['limit'] = $limit; $r['aylik'] = $aylik;
        if ($limit <= 0) return $r; // kişiye limit tanımlanmamış → özellik bu kişide kapalı, düğme görünmez
        if (!in_array($talep->durum, ['yeni', 'teklif'], true)) return $r;
        $r['goster'] = true;
        $r['kullanilan'] = round(_saLimitAyKullanim($sid, $p->id), 2);
        if (_saLimitDepKural($sid, _saLimitTalepDepId($talep)) === 'onay') {
            $r['neden'] = $talep->departman . ' siparişleri her zaman patron onayına gider (limit geçerli değil).';
            return $r;
        }
        $kal = _saLimitKalemler($talep->id);
        $r['kalem'] = count($kal);
        $r['kalan_kalem'] = DB::table('talep_kalemleri')->where('talep_id', (int) $talep->id)->count() - count($kal);
        $r['tedarikciler'] = array_values(array_unique(array_column($kal, 'tedarikci_id')));
        if (!$kal) { $r['neden'] = 'Fiyatı gelen kalem yok — önce teklif gelmeli.'; return $r; }
        $tutar = round(array_sum(array_column($kal, 'tutar')), 2);
        $r['tutar'] = $tutar;
        $tl = fn ($x) => number_format($x, 2, ',', '.') . ' ₺';
        if ($tutar > $limit) { $r['neden'] = 'Tutar ' . $tl($tutar) . ', senin limitin ' . $tl($limit) . ' — patron onayı gerekli.'; return $r; }
        if ($aylik !== null && $r['kullanilan'] + $tutar > $aylik) {
            $r['neden'] = 'Aylık limitin dolar: bu ay ' . $tl($r['kullanilan']) . ' kullandın, aylık limit ' . $tl($aylik) . ' — patron onayı gerekli.';
            return $r;
        }
        // Parçalama koruması: bugün aynı tedarikçiye kendi onayınla geçtiklerin + bu sipariş, sipariş limitini aşamaz
        $tedTutar = [];
        foreach ($kal as $x) $tedTutar[$x['tedarikci_id']] = ($tedTutar[$x['tedarikci_id']] ?? 0) + $x['tutar'];
        foreach ($tedTutar as $tedId => $tt) {
            $bugun = (float) DB::table('satinalma_siparisleri')->where('sube_id', $sid)->where('onay_tipi', 'limit_ici')->where('onaylayan_id', (int) $p->id)
                ->where('tedarikci_id', $tedId)->where('tarih', now()->toDateString())->sum('genel_toplam');
            if ($bugun > 0 && $bugun + $tt > $limit) {
                $tedAd = DB::table('tedarikciler')->where('id', $tedId)->value('ad') ?: 'Bu tedarikçi';
                $r['neden'] = $tedAd . ' için bugün kendi onayınla ' . $tl($bugun) . ' sipariş geçtin; bununla ' . $tl($bugun + $tt) . ' olur, limitin ' . $tl($limit) . '. Sipariş bölünemez — patron onayı gerekli.';
                return $r;
            }
        }
        $r['uygun'] = true;
        return $r;
    }
}

// Talep detayında: limit içi onay mümkün mü (düğme + neden)
Route::get('/satinalma-talep/limit-bilgi', function (Request $r) {
    _satinalmaTalepEnsure();
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => false], 401);
    $talep = DB::table('satinalma_talepleri')->where('id', (int) $r->query('id'))->where('sube_id', $p->sube_id)->first();
    if (!$talep) return response()->json(['ok' => false, 'mesaj' => 'Bulunamadı.']);
    return response()->json(['ok' => true] + _saLimitDurum($p, $talep));
});

// Satın almacı: limit içinde kendi onayıyla sipariş geç (patron onayı atlanır, patrona bilgi gider)
Route::post('/satinalma-talep/limit-onay', function (Request $r) {
    _satinalmaTalepEnsure();
    if (function_exists('_restoSatinalmaEnsure')) _restoSatinalmaEnsure();
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => false], 401);
    if (($p->rol ?? '') !== 'satinalmaci') return response()->json(['ok' => false, 'mesaj' => 'Limitli onay yalnız satın almacı içindir.'], 403);
    $id = (int) $r->input('id');
    $talep = DB::table('satinalma_talepleri')->where('id', $id)->where('sube_id', $p->sube_id)->first();
    if (!$talep) return response()->json(['ok' => false, 'mesaj' => 'Bulunamadı.']);
    if ($kapali = _talepSatinalmayaKapali($talep)) return response()->json(['ok' => false, 'mesaj' => $kapali]);
    $kilit = cache()->lock('talep_limit_onay_' . $id, 15);
    if (!$kilit->get()) return response()->json(['ok' => false, 'mesaj' => 'Bu talep şu an işleniyor, birkaç saniye bekleyin.']);
    try {
        $d = _saLimitDurum($p, $talep);
        if (!$d['uygun']) return response()->json(['ok' => false, 'mesaj' => $d['neden'] ?: 'Bu talep limitinle onaylanamaz.']);
        $kal = _saLimitKalemler($id);
        // Aynı ürün başka talepte onayda/onaylı ise ikinci kez sipariş geçilmez
        $malIds = array_values(array_unique(array_map(fn ($x) => (int) $x['kalem']->malzeme_id, $kal)));
        $cift = DB::table('talep_kalemleri as k')->join('satinalma_talepleri as t', 't.id', '=', 'k.talep_id')
            ->where('t.sube_id', $talep->sube_id)->where('t.id', '!=', $id)->whereIn('t.durum', ['onayda', 'fiyat_onay', 'onaylandi'])
            ->whereIn('k.malzeme_id', $malIds)->get(['k.malzeme_id', 't.id as talep_id']);
        if ($cift->isNotEmpty()) {
            $ad = DB::table('malzemeler')->whereIn('id', $cift->pluck('malzeme_id')->all())->pluck('ad', 'id');
            return response()->json(['ok' => false, 'mesaj' => 'Şu ürünler başka talepte zaten onay sürecinde: ' . $cift->map(fn ($c) => ($ad[$c->malzeme_id] ?? 'Ürün') . ' (Talep #' . $c->talep_id . ')')->unique()->implode(', ') . '. Önce onları netleştir.']);
        }
        $teslimler = _talepTeslimGirdisi($r);
        if (is_string($teslimler)) return response()->json(['ok' => false, 'mesaj' => $teslimler]);
        $kalemIds = [];
        foreach ($kal as $x) {
            DB::table('talep_kalemleri')->where('id', $x['kalem']->id)->update(['onerilen_tedarikci_id' => $x['tedarikci_id'], 'secilen_tedarikci_id' => $x['tedarikci_id'], 'updated_at' => now()]);
            $x['kalem']->onerilen_tedarikci_id = $x['tedarikci_id'];
            $x['kalem']->secilen_tedarikci_id = $x['tedarikci_id'];
            $kalemIds[] = $x['kalem']->id;
        }
        [$siparisNolar, $waGonderilen, $gonderim] = _satinalmaPoYap($talep, collect(array_column($kal, 'kalem')), $p, $teslimler);
        $spIds = array_values(array_filter(array_column($gonderim, 'siparis_id')));
        if ($spIds) DB::table('satinalma_siparisleri')->whereIn('id', $spIds)->update(['onay_tipi' => 'limit_ici', 'onaylayan_id' => (int) $p->id]);
        $kalan = DB::table('talep_kalemleri')->where('talep_id', $id)->whereNotIn('id', $kalemIds)->count();
        DB::table('satinalma_talepleri')->where('id', $id)->update(['durum' => $kalan > 0 ? 'teklif' : 'siparis', 'sa_onay_tipi' => 'limit_ici', 'updated_at' => now()]);
        // Patron engellemez ama görür: üst yönetime bilgi
        try {
            _fcmPersoneleGonder($talep->sube_id, _talepUstYonetimIdleri($talep->sube_id), '🟢 Limit içi sipariş',
                ($p->ad ?? 'Satın almacı') . ' kendi limitiyle ' . number_format($d['tutar'], 2, ',', '.') . ' ₺ sipariş geçti (' . $talep->departman . ', Talep #' . $id . ')',
                ['tur' => 'satinalma_talep', 'id' => (string) $id]);
        } catch (\Throwable $e) {}
        return response()->json(['ok' => true, 'siparisler' => $siparisNolar, 'wa' => $waGonderilen, 'kalan' => $kalan, 'gonderim' => $gonderim, 'tutar' => $d['tutar']]);
    } finally { optional($kilit)->release(); }
});

// Ayar ekranı verisi (/satinalma-onay-seviye)
if (!function_exists('_saLimitSayfaVeri')) {
    function _saLimitSayfaVeri($sid)
    {
        _saLimitEnsure();
        if (function_exists('_restoSatinalmaEnsure')) _restoSatinalmaEnsure();
        $satinalmacilar = collect();
        try {
            $satinalmacilar = DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->where('rol', 'satinalmaci')->orderBy('ad')
                ->get(['id', 'ad', 'sa_siparis_limit', 'sa_aylik_limit'])
                ->map(function ($x) use ($sid) { $x->kullanilan = _saLimitAyKullanim($sid, $x->id); return $x; });
        } catch (\Throwable $e) {}
        $departmanlar = collect();
        try {
            $kurallar = DB::table('satinalma_limit_departman')->where('sube_id', (int) $sid)->pluck('kural', 'departman_id');
            $departmanlar = DB::table('departmanlar')->where('sube_id', (int) $sid)->orderBy('ad')->get(['id', 'ad', 'renk', 'ikon'])
                ->map(function ($d) use ($kurallar) { $d->kural = ($kurallar[$d->id] ?? 'limit') === 'onay' ? 'onay' : 'limit'; return $d; });
        } catch (\Throwable $e) {}
        $sonSiparisler = collect();
        try {
            $sonSiparisler = DB::table('satinalma_siparisleri as s')->leftJoin('tedarikciler as t', 't.id', '=', 's.tedarikci_id')->leftJoin('personeller as p', 'p.id', '=', 's.onaylayan_id')
                ->where('s.sube_id', (int) $sid)->where('s.onay_tipi', 'limit_ici')->orderByDesc('s.id')->limit(25)
                ->get(['s.id', 's.siparis_no', 's.tarih', 's.genel_toplam', 's.notlar', 't.ad as tedarikci', 'p.ad as satinalmaci']);
        } catch (\Throwable $e) {}
        return compact('satinalmacilar', 'departmanlar', 'sonSiparisler');
    }
}

Route::post('/satinalma-limit/kaydet', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!$bp || !in_array(($bp->rol ?? ''), ['sahip', 'mudur'])) return back();
    _saLimitEnsure(); $sid = _webSubeId();
    $sayi = function ($v) {
        $v = trim(str_replace([' ', '₺'], '', (string) $v));
        if ($v === '') return null;
        if (strpos($v, ',') !== false) $v = str_replace(',', '.', str_replace('.', '', $v)); // 15.000,50 → 15000.50
        elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) $v = str_replace('.', '', $v);    // 15.000 → 15000 (binlik nokta)
        return is_numeric($v) && (float) $v > 0 ? round((float) $v, 2) : null;
    };
    $gecerli = DB::table('personeller')->where('sube_id', $sid)->pluck('id')->map(fn ($x) => (int) $x)->all();
    foreach ((array) $r->input('limit', []) as $pid => $v) {
        if (!in_array((int) $pid, $gecerli, true)) continue;
        DB::table('personeller')->where('id', (int) $pid)->update(['sa_siparis_limit' => $sayi($v['siparis'] ?? ''), 'sa_aylik_limit' => $sayi($v['aylik'] ?? '')]);
    }
    $depGecerli = DB::table('departmanlar')->where('sube_id', $sid)->pluck('id')->map(fn ($x) => (int) $x)->all();
    foreach ((array) $r->input('dep', []) as $did => $kural) {
        if (!in_array((int) $did, $depGecerli, true)) continue;
        DB::table('satinalma_limit_departman')->updateOrInsert(['sube_id' => $sid, 'departman_id' => (int) $did],
            ['kural' => $kural === 'onay' ? 'onay' : 'limit', 'updated_at' => now(), 'created_at' => now()]);
    }
    return redirect('/satinalma-onay-seviye#limit')->with('ok', 'Onay limitleri kaydedildi.');
});

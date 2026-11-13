<?php
/*
 * ============================================================================
 *  FATURA ANALİZLERİ (efatura_gelen.php'den require) — /fatura-analiz/{sekme} — yapay zekâ YOK, 0 TL
 *  Kaynak: alış faturaları (elle girilen + e-fatura/PDF'ten aktarılan). Fiyatlar malzemenin TEMEL birimine çevrilir;
 *  çevrimi tanımsız birim ayrı satırda kalır (yanlış kıyas yapılmaz).
 *   tasarruf  — Kaçan tasarruf: en ucuz tedarikçiden / en düşük ödediğiniz fiyattan alsaydınız
 *   enflasyon — Zam × TÜFE: ürün ve tedarikçi zamları aynı aylardaki TÜFE ile (TÜFE ayda bir elle girilir)
 *   iz        — Ürün fiyat izi: bir ürünün tüm alışları, tedarikçi tedarikçi grafik
 *   odeme     — Ödeme takvimi: vadeye göre haftalık ödeme yükü (ödemeler carideki tutardan FIFO düşülür)
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_fanEnsure')) {
    function _fanEnsure()
    {
        if (cache()->get('fatura_analiz_ensure_ok1')) return;
        try {
            if (!Schema::hasTable('fatura_tufe')) {
                Schema::create('fatura_tufe', function ($t) {
                    $t->id(); $t->string('ay', 7)->unique();           // 2026-10
                    $t->decimal('yuzde', 6, 2);                         // aylık TÜFE değişimi %
                    $t->timestamps();
                });
            }
            cache()->put('fatura_analiz_ensure_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

// Alış birimi → temel birim karşılığı; [karşılık, biliniyor mu]
if (!function_exists('_fanKarsi')) {
    function _fanKarsi($mid, $bid)
    {
        static $c = [];
        $k = $mid . '|' . $bid;
        if (isset($c[$k])) return $c[$k];
        $temel = DB::table('malzemeler')->where('id', $mid)->value('temel_birim_id');
        if (!$bid || (int) $bid === (int) $temel) return $c[$k] = [1.0, true];
        $cv = DB::table('birim_cevrimleri')->where('malzeme_id', $mid)->where('birim_id', $bid)->value('temel_birim_karsiligi');
        if ($cv > 0) return $c[$k] = [(float) $cv, true];
        $x = function_exists('_restoBirimKarsilik') ? _restoBirimKarsilik($mid, $bid, $temel) : 1.0;
        return $c[$k] = [$x, $x != 1.0];
    }
}

// Dönemdeki alış satırları (temel birime çevrilmiş)
if (!function_exists('_fanSatirlar')) {
    function _fanSatirlar($sid, $bas, $bit)
    {
        if (!Schema::hasTable('alis_fatura_kalemleri')) return collect();
        $r = DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'f.id', '=', 'k.fatura_id')->where('f.sube_id', $sid)
            ->whereBetween('f.tarih', [$bas, $bit])->where('k.miktar', '>', 0)->where('k.birim_fiyat', '>', 0)
            ->select('k.malzeme_id', 'k.alis_birim_id', 'k.miktar', 'k.birim_fiyat', 'k.satir_toplam', 'f.tarih', 'f.tedarikci_id', 'f.fatura_no', 'f.id as fid')->orderBy('f.tarih')->get();
        return $r->map(function ($k) {
            [$x, $bil] = _fanKarsi($k->malzeme_id, $k->alis_birim_id);
            $k->anahtar = $k->malzeme_id . ($bil ? '' : '|b' . $k->alis_birim_id);
            $k->qt = (float) $k->miktar * $x; $k->pt = (float) $k->birim_fiyat / $x; $k->tutar = (float) $k->miktar * (float) $k->birim_fiyat;
            return $k;
        });
    }
}

if (!function_exists('_fanAdlar')) {
    function _fanAdlar($satir)
    {
        $mal = DB::table('malzemeler')->whereIn('id', $satir->pluck('malzeme_id')->unique()->values())->get(['id', 'ad', 'temel_birim_id'])->keyBy('id');
        $birim = DB::table('birimler')->pluck('kisaltma', 'id');
        $ted = DB::table('tedarikciler')->whereIn('id', $satir->pluck('tedarikci_id')->filter()->unique()->values())->pluck('ad', 'id');
        return [$mal, $birim, $ted];
    }
}

if (!function_exists('_fanDonem')) {
    function _fanDonem(Request $r, $varsayilanGun = 90)
    {
        $bit = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bit')) ? $r->query('bit') : today()->toDateString();
        $bas = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bas')) ? $r->query('bas') : \Carbon\Carbon::parse($bit)->subDays($varsayilanGun)->toDateString();
        return [$bas, $bit];
    }
}

// ------------------------------------------------------------------ 1) Kaçan tasarruf ----
if (!function_exists('_fanTasarruf')) {
    function _fanTasarruf($sid, $bas, $bit)
    {
        $s = _fanSatirlar($sid, $bas, $bit);
        [$mal, $birim, $ted] = _fanAdlar($s);
        $o = [];
        foreach ($s->groupBy('anahtar') as $l) {
            $m = $mal[$l->first()->malzeme_id] ?? null; if (!$m) continue;
            $bAd = str_contains($l->first()->anahtar, '|b') ? ($birim[$l->first()->alis_birim_id] ?? '?') : ($birim[$m->temel_birim_id] ?? '');
            // medyanın 3 katından uzak alım = başka ambalaj/birim (aynı stok kodu): kaçan hesabını şişirmesin
            $med = $l->pluck('pt')->sort()->values(); $med = $med[intdiv($med->count(), 2)];
            $l = $l->filter(fn ($k) => $k->pt <= $med * 3 && $k->pt >= $med / 3)->values();
            $tedOrt = $l->groupBy('tedarikci_id')->map(fn ($x) => $x->sum('qt') > 0 ? $x->sum('tutar') / $x->sum('qt') : null)->filter();
            $enIyiTed = $tedOrt->count() >= 2 ? $tedOrt->sort()->keys()->first() : null; $enIyi = $enIyiTed !== null ? $tedOrt[$enIyiTed] : null;
            $minP = $l->min('pt');
            // Kıyas AYNI AY içinde: yıllık en düşük fiyatla kıyas enflasyonu "kaçan tasarruf" gibi gösterirdi
            $kacTed = 0.0; $kacFiyat = 0.0;
            foreach ($l->groupBy(fn ($k) => substr((string) $k->tarih, 0, 7)) as $ay) {
                $ayMin = $ay->min('pt'); $kacFiyat += $ay->sum(fn ($k) => max(0, $k->pt - $ayMin) * $k->qt);
                $ayTed = $ay->groupBy('tedarikci_id')->map(fn ($x) => $x->sum('qt') > 0 ? $x->sum('tutar') / $x->sum('qt') : null)->filter();
                if ($ayTed->count() >= 2) { $iyi = $ayTed->min(); $kacTed += $ay->sum(fn ($k) => max(0, $k->pt - $iyi) * $k->qt); }
            }
            $ortP = $l->sum('qt') > 0 ? $l->sum('tutar') / $l->sum('qt') : 0;
            $minK = $l->sortBy('pt')->first();
            $o[] = ['ad' => $m->ad, 'birim' => $bAd, 'alim' => $l->count(), 'ted_say' => $tedOrt->count(), 'miktar' => $l->sum('qt'), 'tutar' => $l->sum('tutar'), 'ort' => $ortP,
                'en_iyi_ted' => $enIyiTed !== null ? ($ted[$enIyiTed] ?? '—') : null, 'en_iyi' => $enIyi, 'min' => $minP, 'min_ted' => $ted[$minK->tedarikci_id] ?? '—', 'min_tarih' => $minK->tarih,
                'kac_ted' => round($kacTed, 2), 'kac_fiyat' => round($kacFiyat, 2), 'mid' => $m->id];
        }
        usort($o, fn ($a, $b) => [$b['kac_ted'], $b['kac_fiyat']] <=> [$a['kac_ted'], $a['kac_fiyat']]);
        return $o;
    }
}

// ------------------------------------------------------------------ 2) Zam × TÜFE ----
if (!function_exists('_fanEnflasyon')) {
    function _fanEnflasyon($sid, $bas, $bit)
    {
        _fanEnsure();
        $tufe = DB::table('fatura_tufe')->orderBy('ay')->pluck('yuzde', 'ay')->map(fn ($v) => (float) $v);
        $s = _fanSatirlar($sid, $bas, $bit);
        [$mal, $birim, $ted] = _fanAdlar($s);
        // a→b ayları arası birikimli TÜFE (a hariç, b dahil); eksik ay varsa null
        $birikim = function ($a, $b) use ($tufe) {
            if ($a >= $b) return 0.0;
            $c = \Carbon\Carbon::parse($a . '-01')->addMonth(); $son = \Carbon\Carbon::parse($b . '-01'); $x = 1.0;
            while ($c <= $son) { $ay = $c->format('Y-m'); if (!isset($tufe[$ay])) return null; $x *= 1 + $tufe[$ay] / 100; $c->addMonth(); }
            return ($x - 1) * 100;
        };
        $urun = []; $eksikTufe = false; $suphe = [];
        foreach ($s->groupBy(fn ($k) => $k->anahtar . '#' . $k->tedarikci_id) as $l) {
            $aylar = $l->groupBy(fn ($k) => substr((string) $k->tarih, 0, 7))->map(fn ($x) => $x->sum('qt') > 0 ? $x->sum('tutar') / $x->sum('qt') : null)->filter()->sortKeys();
            if ($aylar->count() < 2) continue;
            $a = $aylar->keys()->first(); $b = $aylar->keys()->last(); $pa = $aylar[$a]; $pb = $aylar[$b];
            $m = $mal[$l->first()->malzeme_id] ?? null; if (!$m || $pa <= 0) continue;
            // 3 katından fazla oynayan fiyat zam değil, ambalaj/birim karışıklığı: kıyasa katılmaz, ayrıca listelenir
            if ($pb > $pa * 3 || $pb < $pa / 3) { $suphe[] = $m->ad . ' (' . number_format($pa, 2, ',', '.') . ' → ' . number_format($pb, 2, ',', '.') . ' ₺)'; continue; }
            $zam = ($pb - $pa) / $pa * 100; $t = $birikim($a, $b);
            if ($t === null) $eksikTufe = true;
            $urun[] = ['ad' => $m->ad, 'ted' => $ted[$l->first()->tedarikci_id] ?? '—', 'tid' => $l->first()->tedarikci_id, 'ilk_ay' => $a, 'son_ay' => $b, 'ilk' => $pa, 'son' => $pb,
                'zam' => round($zam, 2), 'tufe' => $t !== null ? round($t, 2) : null, 'fark' => $t !== null ? round($zam - $t, 2) : null, 'tutar' => $l->sum('tutar'),
                'birim' => str_contains($l->first()->anahtar, '|b') ? ($birim[$l->first()->alis_birim_id] ?? '?') : ($birim[$m->temel_birim_id] ?? '')];
        }
        usort($urun, fn ($x, $y) => ($y['fark'] ?? -999) <=> ($x['fark'] ?? -999));
        // tedarikçi: harcama ağırlıklı ortalama zam vs ağırlıklı TÜFE
        $tedarikci = collect($urun)->groupBy('tid')->map(function ($l) {
            $w = $l->sum('tutar') ?: 1; $tl = $l->filter(fn ($x) => $x['tufe'] !== null); $wt = $tl->sum('tutar') ?: 1;
            $zam = $l->sum(fn ($x) => $x['zam'] * $x['tutar']) / $w; $tufe = $tl->count() ? $tl->sum(fn ($x) => $x['tufe'] * $x['tutar']) / $wt : null;
            return ['ted' => $l->first()['ted'], 'urun' => $l->count(), 'tutar' => $l->sum('tutar'), 'zam' => round($zam, 2), 'tufe' => $tufe !== null ? round($tufe, 2) : null,
                'fark' => $tufe !== null ? round($zam - $tufe, 2) : null, 'ustu' => $l->filter(fn ($x) => $x['fark'] !== null && $x['fark'] > 0)->count()];
        })->sortByDesc(fn ($x) => $x['fark'] ?? -999)->values()->all();
        return ['urun' => $urun, 'tedarikci' => $tedarikci, 'tufe' => $tufe, 'eksik' => $eksikTufe, 'suphe' => $suphe];
    }
}

// ------------------------------------------------------------------ 3) Ürün fiyat izi ----
if (!function_exists('_fanIz')) {
    function _fanIz($sid, $bas, $bit, $mid)
    {
        $s = _fanSatirlar($sid, $bas, $bit);
        $liste = $s->groupBy('malzeme_id')->map(fn ($l) => $l->sum('tutar'))->sortDesc();
        [$mal, $birim, $ted] = _fanAdlar($s);
        $secenek = $liste->map(fn ($t, $id) => ['id' => $id, 'ad' => $mal[$id]->ad ?? '#' . $id, 'tutar' => $t])->values();
        if (!$mid) $mid = $liste->keys()->first();
        $l = $s->where('malzeme_id', $mid)->values();
        $m = $mal[$mid] ?? null;
        $sat = $l->map(fn ($k) => ['tarih' => $k->tarih, 'ted' => $ted[$k->tedarikci_id] ?? '—', 'tid' => (int) $k->tedarikci_id, 'fatura' => $k->fatura_no, 'miktar' => (float) $k->miktar,
            'birim' => $birim[$k->alis_birim_id] ?? '', 'fiyat' => (float) $k->birim_fiyat, 'pt' => $k->pt, 'bilinen' => !str_contains($k->anahtar, '|b')])->all();
        return ['secenek' => $secenek, 'mid' => $mid, 'ad' => $m->ad ?? null, 'temel' => $m ? ($birim[$m->temel_birim_id] ?? '') : '', 'sat' => $sat];
    }
}

// ------------------------------------------------------------------ 4) Ödeme takvimi ----
if (!function_exists('_fanOdeme')) {
    function _fanOdeme($sid)
    {
        if (!Schema::hasTable('alis_faturalari')) return ['fatura' => [], 'hafta' => [], 'cari_yok' => 0];
        $f = DB::table('alis_faturalari')->where('sube_id', $sid)->where('tarih', '>=', now()->subDays(400)->toDateString())->whereNotIn('durum', ['iptal', 'reddedildi'])
            ->orderBy('tarih')->orderBy('id')->get(['id', 'tedarikci_id', 'fatura_no', 'tarih', 'toplam', 'vade_tarihi']);
        $tedler = DB::table('tedarikciler')->whereIn('id', $f->pluck('tedarikci_id')->filter()->unique()->values())->get()->keyBy('id');
        $cariVar = Schema::hasTable('cari_hareketler') && Schema::hasColumn('tedarikciler', 'cari_id');
        $acik = []; $cariYok = 0;
        foreach ($f->groupBy('tedarikci_id') as $tid => $l) {
            $t = $tedler[$tid] ?? null; $odenen = null;
            if ($cariVar && $t && $t->cari_id) $odenen = (float) DB::table('cari_hareketler')->where('cari_id', $t->cari_id)->where('tip', '!=', 'borc')->sum('tutar');
            if ($odenen === null) $cariYok++;
            $kalanOdeme = (float) ($odenen ?? 0);
            foreach ($l as $x) { // FIFO: ödemeler en eski faturadan düşülür
                $kalan = (float) $x->toplam;
                if ($kalanOdeme > 0) { $d = min($kalan, $kalanOdeme); $kalan -= $d; $kalanOdeme -= $d; }
                if ($kalan < 0.01) continue;
                $vade = $x->vade_tarihi ?: \Carbon\Carbon::parse($x->tarih)->addDays((int) ($t->odeme_vade_gun ?? 0))->toDateString();
                $acik[] = ['ted' => $t->ad ?? 'Tedarikçi', 'fatura' => $x->fatura_no, 'tarih' => $x->tarih, 'vade' => $vade, 'toplam' => (float) $x->toplam, 'kalan' => round($kalan, 2), 'cari' => $odenen !== null];
            }
        }
        usort($acik, fn ($a, $b) => $a['vade'] <=> $b['vade']);
        $bugun = today(); $hafta = [];
        foreach ($acik as $a) {
            $v = \Carbon\Carbon::parse($a['vade']);
            if ($v->lt($bugun)) $k = '0|Gecikmiş';
            else { $h = (int) floor($bugun->diffInDays($v) / 7); $bas = $bugun->copy()->addDays($h * 7); $k = $h > 11 ? '9|12 hafta sonrası' : sprintf('%d|%s – %s', $h + 1, $bas->format('d.m'), $bas->copy()->addDays(6)->format('d.m')); }
            $hafta[$k]['tutar'] = ($hafta[$k]['tutar'] ?? 0) + $a['kalan']; $hafta[$k]['adet'] = ($hafta[$k]['adet'] ?? 0) + 1;
            $hafta[$k]['ted'][$a['ted']] = ($hafta[$k]['ted'][$a['ted']] ?? 0) + $a['kalan'];
        }
        uksort($hafta, fn ($a, $b) => (int) explode('|', $a)[0] <=> (int) explode('|', $b)[0] ?: strcmp($a, $b));
        return ['fatura' => $acik, 'hafta' => $hafta, 'cari_yok' => $cariYok, 'ted_say' => $f->pluck('tedarikci_id')->unique()->count()];
    }
}

Route::get('/fatura-analiz', fn () => redirect('/fatura-analiz/tasarruf'));
Route::get('/fatura-analiz/{sekme}', function (Request $r, $sekme) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _fanEnsure();
    $sid = _webSubeId();
    [$bas, $bit] = _fanDonem($r, $sekme === 'enflasyon' ? 365 : 90);
    $v = ['sekme' => $sekme, 'bas' => $bas, 'bit' => $bit, 'yonetici' => _efgYonetici()];
    if ($sekme === 'tasarruf') $v['t'] = _fanTasarruf($sid, $bas, $bit);
    elseif ($sekme === 'enflasyon') $v['e'] = _fanEnflasyon($sid, $bas, $bit);
    elseif ($sekme === 'iz') $v['iz'] = _fanIz($sid, $bas, $bit, (int) $r->query('malzeme'));
    else $v['o'] = _fanOdeme($sid);
    if ($r->query('format') === 'xlsx') return _fanExcel($sekme, $v);
    return view('fatura_analiz', $v);
})->where('sekme', 'tasarruf|enflasyon|iz|odeme');

// TÜFE kaydı (yalnız sahip/müdür) — ay başına bir değer
Route::post('/fatura-analiz/tufe', function (Request $r) {
    if (!_efgErisim() || !_efgYonetici()) return back()->with('hata', 'TÜFE değerlerini işletme sahibi ya da müdür girer.');
    _fanEnsure();
    $n = 0;
    foreach ((array) $r->input('tufe', []) as $ay => $deger) {
        if (!preg_match('/^\d{4}-\d{2}$/', (string) $ay)) continue;
        $deger = trim(str_replace(',', '.', (string) $deger));
        if ($deger === '') { DB::table('fatura_tufe')->where('ay', $ay)->delete(); continue; }
        if (!is_numeric($deger) || abs((float) $deger) > 50) continue;
        DB::table('fatura_tufe')->updateOrInsert(['ay' => $ay], ['yuzde' => round((float) $deger, 2), 'updated_at' => now(), 'created_at' => now()]); $n++;
    }
    return back()->with('ok', $n . ' ayın TÜFE değeri kaydedildi');
});

if (!function_exists('_fanExcel')) {
    function _fanExcel($sekme, array $v)
    {
        $alt = \Carbon\Carbon::parse($v['bas'])->format('d.m.Y') . ' – ' . \Carbon\Carbon::parse($v['bit'])->format('d.m.Y') . ' · Oluşturma: ' . now()->format('d.m.Y H:i');
        if ($sekme === 'tasarruf') {
            $s = [['ad' => 'Kaçan Tasarruf', 'not' => 'Fiyatlar temel birime çevrildi. Tedarikçi farkı = en ucuz ortalama veren tedarikçiden alınsaydı. Fiyat farkı = dönemde ödenen en düşük fiyattan alınsaydı.',
                'bos' => 'Dönemde alış faturası yok. Alış faturası elle girildikçe ya da Gelen e-Faturalar\'dan aktarıldıkça dolar.',
                'kol' => [['Ürün', 'm', 34], ['Birim', 'm', 7], ['Alım', 'n', 6], ['Tedarikçi sayısı', 'n', 9], ['Miktar', 'n', 10, 'top'], ['Tutar', 'tl', 13, 'top bar'], ['Ort. fiyat', 'tl', 11],
                    ['En ucuz tedarikçi', 'm', 22], ['Onun ort. fiyatı', 'tl', 11], ['En düşük ödenen', 'tl', 11], ['Kimden', 'm', 20], ['Tarih', 't', 11], ['Tedarikçi farkı', 'tl', 13, 'top bar'], ['Fiyat farkı', 'tl', 13, 'top']],
                'sat' => array_map(fn ($x) => [$x['ad'], $x['birim'], $x['alim'], $x['ted_say'], $x['miktar'], $x['tutar'], $x['ort'], $x['en_iyi_ted'], $x['en_iyi'], $x['min'], $x['min_ted'], $x['min_tarih'], $x['kac_ted'], $x['kac_fiyat']], $v['t'])]];
            $bas = 'Kaçan Tasarruf';
        } elseif ($sekme === 'enflasyon') {
            $e = $v['e'];
            $s = [['ad' => 'Tedarikçi × TÜFE', 'not' => 'Harcama ağırlıklı ortalama zam ile aynı aylardaki birikimli TÜFE. Fark > 0 = enflasyonun üstünde zam.', 'bos' => 'En az iki farklı ayda alınan ürün yok ya da TÜFE girilmemiş.',
                'kol' => [['Tedarikçi', 'm', 30], ['Ürün', 'n', 7], ['Tutar', 'tl', 13, 'top bar'], ['Ort. zam %', 'y', 10], ['TÜFE %', 'y', 10], ['Fark (puan)', 'y', 10, 'esik:0.01'], ['Enflasyon üstü ürün', 'n', 10]],
                'sat' => array_map(fn ($x) => [$x['ted'], $x['urun'], $x['tutar'], $x['zam'], $x['tufe'], $x['fark'], $x['ustu']], $e['tedarikci'])],
                ['ad' => 'Ürün × TÜFE', 'not' => 'İlk ve son alış ayının ortalama fiyatı (temel birim).', 'bos' => 'En az iki farklı ayda alınan ürün yok.',
                'kol' => [['Ürün', 'm', 34], ['Tedarikçi', 'm', 24], ['Birim', 'm', 7], ['İlk ay', 'm', 8], ['İlk fiyat', 'tl', 11], ['Son ay', 'm', 8], ['Son fiyat', 'tl', 11], ['Zam %', 'y', 9], ['TÜFE %', 'y', 9], ['Fark (puan)', 'y', 10, 'esik:0.01'], ['Tutar', 'tl', 12, 'top']],
                'sat' => array_map(fn ($x) => [$x['ad'], $x['ted'], $x['birim'], $x['ilk_ay'], $x['ilk'], $x['son_ay'], $x['son'], $x['zam'], $x['tufe'], $x['fark'], $x['tutar']], $e['urun'])]];
            $bas = 'Zam ve Enflasyon';
        } elseif ($sekme === 'iz') {
            $iz = $v['iz'];
            $s = [['ad' => 'Fiyat İzi', 'not' => ($iz['ad'] ?? '') . ' — temel birim: ' . $iz['temel'], 'bos' => 'Bu ürün için dönemde alış yok.',
                'kol' => [['Tarih', 't', 11], ['Tedarikçi', 'm', 26], ['Fatura', 'm', 18], ['Miktar', 'n', 9], ['Birim', 'm', 8], ['Birim fiyat', 'tl', 12], ['Temel birim fiyatı', 'tl', 14]],
                'sat' => array_map(fn ($x) => [$x['tarih'], $x['ted'], $x['fatura'], $x['miktar'], $x['birim'], $x['fiyat'], $x['bilinen'] ? $x['pt'] : null], $iz['sat'])]];
            $bas = 'Fiyat İzi — ' . ($iz['ad'] ?? '');
        } else {
            $o = $v['o'];
            $s = [['ad' => 'Haftalık', 'not' => 'Ödemeler tedarikçi carisindeki ödemelerden en eski faturadan başlayarak düşüldü.' . ($o['cari_yok'] ? ' ' . $o['cari_yok'] . ' tedarikçinin carisi yok; faturaları açık sayıldı.' : ''),
                'bos' => 'Açık fatura yok.', 'kol' => [['Hafta', 'm', 20], ['Fatura', 'n', 8, 'top'], ['Tutar', 'tl', 14, 'top bar'], ['Tedarikçiler', 'm', 70]],
                'sat' => array_map(fn ($k, $x) => [explode('|', $k, 2)[1], $x['adet'], $x['tutar'], collect($x['ted'])->sortDesc()->map(fn ($t, $a) => $a . ' ' . number_format($t, 0, ',', '.'))->implode(' · ')], array_keys($o['hafta']), $o['hafta'])],
                ['ad' => 'Açık Faturalar', 'not' => '', 'bos' => 'Açık fatura yok.', 'kol' => [['Vade', 't', 11], ['Tedarikçi', 'm', 28], ['Fatura', 'm', 18], ['Tarih', 't', 11], ['Toplam', 'tl', 13], ['Kalan', 'tl', 13, 'top bar'], ['Cari', 'm', 8]],
                'sat' => array_map(fn ($x) => [$x['vade'], $x['ted'], $x['fatura'], $x['tarih'], $x['toplam'], $x['kalan'], $x['cari'] ? 'var' : 'yok'], $o['fatura'])]];
            $bas = 'Ödeme Takvimi'; $alt = 'Oluşturma: ' . now()->format('d.m.Y H:i');
        }
        $x = _fr2Xlsx($s, $bas, $alt, '0E7490');
        if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
        return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="fatura-' . $sekme . '-' . date('Ymd') . '.xlsx"']);
    }
}

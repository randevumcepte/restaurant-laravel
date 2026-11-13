<?php
/*
 *  GELEN e-FATURA — WEB SAYFALARI (efatura_gelen.php'den require)
 *  /efatura-gelen            liste + XML/ZIP yükleme + entegratör bağlantısı
 *  /efatura-gelen/{id}       kalem eşleştirme → alış faturasına aktar, kabul/red, XML indir
 *  Kapı: /efatura-gelen öneki gor_satinalma (sayfa yetki haritası) + _efgErisim; bağlantı ayarı yalnız sahip/müdür
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (!function_exists('_efgErisim')) {
    function _efgErisim()
    {
        $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
        if (!$bp) return true; // panel oturumu (sahip) — ek kapı /efatura-gelen önekini gor_satinalma ile süzer
        return function_exists('_satinalmaErisim') ? _satinalmaErisim($bp) : true;
    }
}
if (!function_exists('_efgYonetici')) {
    function _efgYonetici()
    {
        $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
        if (!$bp) return true;
        return in_array(strtolower((string) ($bp->rol ?? '')), ['sahip', 'mudur', 'müdür'], true);
    }
}
if (!function_exists('_efgPersonelId')) {
    function _efgPersonelId()
    {
        try { $bp = _posPersonel(); return $bp->id ?? null; } catch (\Throwable $e) { return null; }
    }
}
// Benzer ad önerisi (eşleşmeyen kalem için): normalize adlar arası benzerlik ≥ %60
if (!function_exists('_efgOneri')) {
    function _efgOneri($sid, $ad)
    {
        $n = _efgNorm($ad); $en = null; $enP = 0.0;
        foreach (_efgMalzemeler($sid) as $m) {
            $mn = _efgNorm($m->ad); if ($mn === '') continue;
            similar_text($n, $mn, $p);
            if (str_contains($n, $mn) && mb_strlen($mn) >= 4) $p = max($p, 75.0);
            if ($p > $enP) { $enP = $p; $en = $m; }
        }
        return $enP >= 60 ? $en : null;
    }
}

Route::get('/efatura-gelen', function (Request $r) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure();
    $sid = _webSubeId();
    $b = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
    // Bağlantı açıksa sayfa açılışında arka planda yeni faturaları çek (15 dk kilitli)
    if ($b && $b->aktif && _efgSurucu($b->entegrator)) app()->terminating(function () use ($sid) { try { _efgMotor($sid); } catch (\Throwable $e) {} });
    $durum = in_array($r->query('durum'), ['bekliyor', 'aktarildi', 'iade', 'yok_sayildi', 'tumu'], true) ? $r->query('durum') : null;
    $say = DB::table('efatura_gelen')->where('sube_id', $sid)->selectRaw('durum, COUNT(*) c')->groupBy('durum')->pluck('c', 'durum');
    if (!$durum) $durum = ($say['bekliyor'] ?? 0) > 0 ? 'bekliyor' : 'tumu';
    $q = DB::table('efatura_gelen')->where('sube_id', $sid);
    if ($durum !== 'tumu') $q->where('durum', $durum);
    if ($ara = trim((string) $r->query('q'))) $q->where(fn ($w) => $w->where('fatura_no', 'like', '%' . $ara . '%')->orWhere('satici_ad', 'like', '%' . $ara . '%'));
    $liste = $q->orderByDesc('tarih')->orderByDesc('id')->limit(300)->get();
    $eksik = $liste->count() ? DB::table('efatura_gelen_kalem')->whereIn('gelen_id', $liste->pluck('id'))->whereNull('malzeme_id')->selectRaw('gelen_id, COUNT(*) c')->groupBy('gelen_id')->pluck('c', 'gelen_id') : collect();
    $birimBek = $liste->count() ? DB::table('efatura_gelen_kalem')->whereIn('gelen_id', $liste->pluck('id'))->where('eslesme', 'birim')->selectRaw('gelen_id, COUNT(*) c')->groupBy('gelen_id')->pluck('c', 'gelen_id') : collect();
    $kalemSay = $liste->count() ? DB::table('efatura_gelen_kalem')->whereIn('gelen_id', $liste->pluck('id'))->selectRaw('gelen_id, COUNT(*) c')->groupBy('gelen_id')->pluck('c', 'gelen_id') : collect();
    return view('efatura_gelen', [
        'b' => $b, 'liste' => $liste, 'say' => $say, 'durum' => $durum, 'eksik' => $eksik, 'birimBek' => $birimBek, 'kalemSay' => $kalemSay, 'ara' => $ara,
        'entegratorler' => _efgEntegratorler(), 'yonetici' => _efgYonetici(), 'stokModu' => _efgStokModu($sid),
        'anahtarVar' => (bool) _efgAnahtar($b), 'cronKey' => env('SANTRAL_SECRET') ? 1 : 0,
    ]);
});

// XML / ZIP yükleme (birden çok dosya)
Route::post('/efatura-gelen/yukle', function (Request $r) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure();
    $sid = _webSubeId();
    $dosyalar = $r->file('dosyalar') ?: [];
    if (!is_array($dosyalar)) $dosyalar = [$dosyalar];
    if (!$dosyalar) return redirect('/efatura-gelen')->with('hata', 'XML ya da ZIP dosyası seç.');
    @set_time_limit(170);
    $yeni = 0; $var = 0; $aktar = 0; $hatalar = []; $yeniler = [];
    $isle = function ($icerik, $ad) use ($sid, &$yeni, &$var, &$aktar, &$hatalar, &$yeniler) {
        $res = _efgKaydet($sid, $icerik, 'xml');
        if ($res['durum'] === 'yeni') { $yeni++; $yeniler[] = $res['id']; if (!empty($res['aktarildi'])) $aktar++; }
        elseif ($res['durum'] === 'var') $var++;
        else $hatalar[] = $ad . ': ' . ($res['mesaj'] ?? 'okunamadı');
    };
    $toplam = 0;
    foreach ($dosyalar as $f) {
        if (!$f || !$f->isValid()) continue;
        if ($f->getSize() > 40 * 1024 * 1024) { $hatalar[] = $f->getClientOriginalName() . ': 40MB üstü'; continue; }
        $ad = $f->getClientOriginalName(); $ic = (string) file_get_contents($f->getRealPath());
        if (strncmp($ic, "PK\x03\x04", 4) === 0) {
            $xmller = _efgZipXmller($ic, 1000 - $toplam);
            if (!$xmller) $hatalar[] = $ad . ': ZIP içinde XML yok';
            foreach ($xmller as $i => $x) { $isle($x, $ad . ' #' . ($i + 1)); $toplam++; }
        } else { $isle($ic, $ad); $toplam++; }
        if ($toplam >= 1000) { $hatalar[] = 'Tek seferde en fazla 1000 fatura; kalanını ayrı yükle.'; break; }
    }
    if (function_exists('_fdTopluDenetle')) _fdTopluDenetle($yeniler); // ZIP sırası tarih sırası olmayabilir
    $msg = $yeni . ' yeni fatura · ' . $aktar . ' tanesi otomatik alış faturasına aktarıldı' . ($var ? ' · ' . $var . ' zaten kayıtlıydı' : '');
    $bekleyen = $yeni - $aktar;
    if ($bekleyen > 0) $msg .= ' · ' . $bekleyen . ' fatura kalem eşleştirmesi bekliyor';
    $red = redirect('/efatura-gelen')->with('ok', $msg);
    if ($hatalar) $red = $red->with('hata', count($hatalar) . ' dosya okunamadı: ' . implode(' | ', array_slice($hatalar, 0, 4)));
    return $red;
});

// Şimdi çek (bağlantı açıkken elle tetik)
Route::post('/efatura-gelen/cek', function () {
    if (!_efgErisim()) return redirect('/pos-giris');
    $r = _efgMotor(_webSubeId(), true);
    if (!empty($r['hata'])) return redirect('/efatura-gelen')->with('hata', $r['hata']);
    return redirect('/efatura-gelen')->with('ok', ($r['yeni'] ?? 0) . ' yeni fatura çekildi · ' . ($r['aktarilan'] ?? 0) . ' tanesi otomatik aktarıldı' . (!empty($r['devam']) ? ' · kalanlar birkaç dakika içinde çekilecek' : ''));
});

// Bağlantı ayarı (yalnız sahip/müdür)
Route::post('/efatura-gelen/baglanti', function (Request $r) {
    if (!_efgErisim() || !_efgYonetici()) return redirect('/efatura-gelen')->with('hata', 'Bağlantı ayarını yalnız işletme sahibi ya da müdür değiştirebilir.');
    _efgEnsure();
    $sid = _webSubeId();
    $ent = array_key_exists($r->input('entegrator'), _efgEntegratorler()) ? $r->input('entegrator') : null;
    $kol = ['entegrator' => $ent, 'ortam' => $r->input('ortam') === 'test' ? 'test' : 'canli', 'aktif' => $r->boolean('aktif') ? 1 : 0,
        'stok_modu' => in_array($r->input('stok_modu'), ['mal_kabul', 'fatura'], true) ? $r->input('stok_modu') : null,
        'baslangic' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->input('baslangic')) ? $r->input('baslangic') : null, 'updated_at' => now()];
    $anahtar = trim((string) $r->input('api_anahtar'));
    if ($anahtar !== '') $kol['api_anahtar'] = Crypt::encryptString($anahtar);
    if ($r->boolean('anahtar_sil')) $kol['api_anahtar'] = null;
    $eski = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
    if ($eski && $eski->baslangic !== $kol['baslangic']) $kol['son_cekim'] = null; // başlangıç değişti → baştan tara
    if ($eski) DB::table('efatura_baglanti')->where('id', $eski->id)->update($kol);
    else DB::table('efatura_baglanti')->insert($kol + ['sube_id' => $sid, 'created_at' => now()]);
    $b = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
    $s = $ent ? _efgSurucu($ent) : null;
    if (!$s) return redirect('/efatura-gelen')->with('ok', 'Kaydedildi. ' . ($ent ? _efgEntegratorler()[$ent][0] . ' için otomatik bağlantı hazırlanıyor; şimdilik portaldan indirdiğin XML/ZIP dosyalarını yükleyebilirsin.' : ''));
    if (!_efgAnahtar($b)) return redirect('/efatura-gelen')->with('hata', 'Kaydedildi ama API anahtarı yok; otomatik çekim için anahtarı gir.');
    $t = $s['listele']($b, now()->subDay()->utc(), now()->utc(), 1); // bağlantı testi
    if (empty($t['ok'])) return redirect('/efatura-gelen')->with('hata', 'Kaydedildi ama bağlantı testi başarısız: ' . ($t['hata'] ?? '?'));
    return redirect('/efatura-gelen')->with('ok', 'Bağlantı çalışıyor ✓ ' . ($b->aktif ? 'Gelen faturalar 15 dakikada bir otomatik çekilecek. İlk çekim için "Şimdi çek"e basabilirsin.' : 'Otomatik çekimi açmak için "Otomatik çek" kutusunu işaretle.'));
});

Route::get('/efatura-gelen/{id}', function ($id) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure();
    $sid = _webSubeId();
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', $sid)->first();
    if (!$g) return redirect('/efatura-gelen')->with('hata', 'Fatura bulunamadı.');
    $kalemler = DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->orderBy('sira')->get();
    $malzemeler = _efgMalzemeler($sid);
    $malAd = $malzemeler->pluck('ad', 'id'); $malTemel = $malzemeler->pluck('temel_birim_id', 'id');
    $birimler = DB::table('birimler')->orderBy('ad')->get(['id', 'ad', 'kisaltma']);
    $cevrim = DB::table('birim_cevrimleri')->whereIn('malzeme_id', $kalemler->pluck('malzeme_id')->filter()->all() ?: [0])->get()->keyBy(fn ($c) => $c->malzeme_id . '_' . $c->birim_id);
    foreach ($kalemler as $k) {
        $k->malzeme_ad = $k->malzeme_id ? ($malAd[$k->malzeme_id] ?? DB::table('malzemeler')->where('id', $k->malzeme_id)->value('ad')) : null;
        $k->oneri = $k->malzeme_id ? null : _efgOneri($sid, $k->ad);
        $mid = $k->malzeme_id ?: ($k->oneri->id ?? null);
        $k->temel_birim_id = $mid ? (int) ($malTemel[$mid] ?? 0) : null;
        $k->cevrim = ($mid && $k->alis_birim_id) ? ($cevrim[$mid . '_' . $k->alis_birim_id]->temel_birim_karsiligi ?? null) : null;
    }
    $b = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
    $cevapVerilir = $g->profil === 'TICARIFATURA' && !$g->cevap && $b && $b->entegrator && ($s = _efgSurucu($b->entegrator)) && isset($s['cevap'])
        && $g->tarih && \Carbon\Carbon::parse($g->tarih)->gte(today()->subDays(8)) && str_starts_with((string) $g->kaynak, 'api:');
    return view('efatura_gelen_detay', [
        'g' => $g, 'kalemler' => $kalemler, 'malzemeler' => $malzemeler, 'birimler' => $birimler,
        'tedarikci' => $g->tedarikci_id ? DB::table('tedarikciler')->where('id', $g->tedarikci_id)->first() : null,
        'alis' => $g->alis_fatura_id ? DB::table('alis_faturalari')->where('id', $g->alis_fatura_id)->first() : null,
        'stokModu' => _efgStokModu($sid), 'cevapVerilir' => (bool) $cevapVerilir,
    ]);
});

// Kalem eşleştirme kaydet (+ hafıza) → hepsi eşleştiyse aktar
Route::post('/efatura-gelen/{id}/eslestir', function (Request $r, $id) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure();
    $sid = _webSubeId();
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', $sid)->first();
    if (!$g) return redirect('/efatura-gelen')->with('hata', 'Fatura bulunamadı.');
    if ($g->durum === 'aktarildi') return redirect('/efatura-gelen/' . $g->id)->with('hata', 'Bu fatura zaten aktarıldı.');
    $gorunur = _efgMalzemeler($sid)->pluck('temel_birim_id', 'id');
    $girdi = (array) $r->input('kalem', []);
    try {
        DB::transaction(function () use ($g, $girdi, $sid, $gorunur) {
            foreach (DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->get() as $k) {
                $v = $girdi[$k->id] ?? null; if (!is_array($v)) continue;
                $sec = (string) ($v['malzeme'] ?? ''); if ($sec === '') continue;
                $birimId = (int) ($v['birim'] ?? 0) ?: (int) $k->alis_birim_id;
                if ($sec === 'yeni') {
                    $kol = ['ad' => mb_substr(trim((string) ($v['yeni_ad'] ?? '')) ?: $k->ad, 0, 190), 'temel_birim_id' => $birimId, 'stok_takipli' => 1, 'kritik_stok' => 0, 'guncel_maliyet' => $k->birim_fiyat, 'created_at' => now(), 'updated_at' => now()];
                    if (Schema::hasColumn('malzemeler', 'sube_id')) $kol['sube_id'] = $sid;
                    $mid = (int) DB::table('malzemeler')->insertGetId($kol);
                    if (function_exists('_katalogSahiplen')) _katalogSahiplen('malzeme', $mid, $sid);
                    $gorunur[$mid] = $birimId;
                } else {
                    $mid = (int) $sec;
                    if (!isset($gorunur[$mid])) continue; // başka işletmenin malzemesi seçilemez
                }
                // Alış birimi temel birimden farklıysa çevrim (1 koli = X adet) — girildiyse kaydet
                $c = (float) str_replace(',', '.', (string) ($v['cevrim'] ?? ''));
                if ($c > 0 && $birimId && (int) $gorunur[$mid] !== $birimId) {
                    DB::table('birim_cevrimleri')->updateOrInsert(['malzeme_id' => $mid, 'birim_id' => $birimId], ['temel_birim_karsiligi' => $c, 'updated_at' => now(), 'created_at' => now()]);
                }
                DB::table('efatura_gelen_kalem')->where('id', $k->id)->update(['malzeme_id' => $mid, 'alis_birim_id' => $birimId, 'eslesme' => 'elle']);
                $k->malzeme_id = $mid;
                _efgHafizaYaz($sid, $g->satici_vkn, $k, $mid, $birimId);
            }
        });
    } catch (\Throwable $e) { return redirect('/efatura-gelen/' . $g->id)->with('hata', 'Kaydedilemedi: ' . mb_substr($e->getMessage(), 0, 120)); }
    $kalan = DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->whereNull('malzeme_id')->count();
    if ($kalan) return redirect('/efatura-gelen/' . $g->id)->with('ok', 'Kaydedildi. ' . $kalan . ' kalem hâlâ eşleşmedi.');
    if ($g->durum === 'iade' || $g->durum === 'yok_sayildi') return redirect('/efatura-gelen/' . $g->id)->with('ok', 'Eşleştirme kaydedildi.');
    // Diğer bekleyen faturalarda da hafızadan eşleşenleri doldur
    $dolan = 0;
    foreach (DB::table('efatura_gelen')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('satici_vkn', $g->satici_vkn)->where('id', '!=', $g->id)->pluck('id') as $digerId) {
        foreach (DB::table('efatura_gelen_kalem')->where('gelen_id', $digerId)->where(fn ($w) => $w->whereNull('malzeme_id')->orWhere('eslesme', 'birim'))->get() as $dk) {
            if ($dk->malzeme_id) { // birim çevrimi bekliyordu: az önce girildiyse serbest bırak
                if (_efgCevrimVar($dk->malzeme_id, $dk->alis_birim_id)) DB::table('efatura_gelen_kalem')->where('id', $dk->id)->update(['eslesme' => 'hafiza']);
                continue;
            }
            [$m, $b, $es] = _efgKalemBirim(_efgKalemEslestir($sid, $g->satici_vkn, (array) $dk), $dk->birim_kodu, $dk->alis_birim_id);
            if ($m) DB::table('efatura_gelen_kalem')->where('id', $dk->id)->update(['malzeme_id' => $m, 'alis_birim_id' => $b, 'eslesme' => $es]);
        }
        $dg = DB::table('efatura_gelen')->where('id', $digerId)->first();
        if ($dg->tutarli && !DB::table('efatura_gelen_kalem')->where('gelen_id', $digerId)->whereNull('malzeme_id')->exists()) { $a = _efgAktar($digerId, _efgPersonelId()); if (!empty($a['ok'])) $dolan++; }
    }
    $a = _efgAktar($g->id, _efgPersonelId());
    if (empty($a['ok'])) return redirect('/efatura-gelen/' . $g->id)->with('hata', $a['hata'] ?? 'Aktarılamadı');
    $msg = 'Alış faturasına aktarıldı ✓' . (!empty($a['fark']) ? ' Mal kabulle FARK var: ' . implode(' · ', array_slice($a['fark'], 0, 3)) : '') . ($dolan ? ' · Aynı tedarikçinin ' . $dolan . ' faturası daha otomatik aktarıldı.' : '');
    return redirect('/efatura-gelen/' . $g->id)->with('ok', $msg);
});

// İlk kurulum (geçmiş faturalar): eşleşmeyen tüm kalemleri yeni malzeme kartı olarak aç (aynı ürün tek kart) → hafıza → hepsini aktar
// Yalnız sahip/müdür; yalnız toplamı tutan bekleyen faturalar
Route::post('/efatura-gelen/toplu-yeni', function () {
    if (!_efgErisim() || !_efgYonetici()) return redirect('/efatura-gelen')->with('hata', 'Toplu işlemi yalnız işletme sahibi ya da müdür yapabilir.');
    _efgEnsure();
    $sid = _webSubeId();
    @set_time_limit(170);
    $gelenler = DB::table('efatura_gelen')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('tutarli', 1)->orderBy('tarih')->orderBy('id')->get();
    $yeniKart = 0; $aktar = 0; $hata = 0; $birimBek = 0; $basla = microtime(true); $yarim = false;
    foreach ($gelenler as $g) {
        if (microtime(true) - $basla > 120) { $yarim = true; break; } // zaman aşımına düşmeden dur; kalanlar ikinci basışta
        foreach (DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->where(fn ($w) => $w->whereNull('malzeme_id')->orWhere('eslesme', 'birim'))->get() as $k) {
            if ($k->malzeme_id) { if (_efgCevrimVar($k->malzeme_id, $k->alis_birim_id)) DB::table('efatura_gelen_kalem')->where('id', $k->id)->update(['eslesme' => 'hafiza']); continue; }
            $e = _efgKalemEslestir($sid, $g->satici_vkn, (array) $k); // önceki turda açılan kart hafızada olabilir
            // aynı ürün başka birimle (adet ↔ koli) geldiyse çevrimsiz aktarılmaz: 'birim' olarak bekler
            if ($e) { [$mid, $birimId, $kaynak] = _efgKalemBirim($e, $k->birim_kodu, $k->alis_birim_id); }
            else {
                $kol = ['ad' => mb_substr($k->ad, 0, 190), 'temel_birim_id' => $k->alis_birim_id, 'stok_takipli' => 1, 'kritik_stok' => 0, 'guncel_maliyet' => $k->birim_fiyat, 'created_at' => now(), 'updated_at' => now()];
                if (Schema::hasColumn('malzemeler', 'sube_id')) $kol['sube_id'] = $sid;
                $mid = (int) DB::table('malzemeler')->insertGetId($kol);
                if (function_exists('_katalogSahiplen')) _katalogSahiplen('malzeme', $mid, $sid);
                $birimId = $k->alis_birim_id; $kaynak = 'elle'; $yeniKart++;
                _efgHafizaYaz($sid, $g->satici_vkn, $k, $mid, $birimId);
            }
            DB::table('efatura_gelen_kalem')->where('id', $k->id)->update(['malzeme_id' => $mid, 'alis_birim_id' => $birimId, 'eslesme' => $kaynak]);
        }
        if (DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->where('eslesme', 'birim')->exists()) { $birimBek++; continue; }
        $a = _efgAktar($g->id, _efgPersonelId());
        if (!empty($a['ok'])) $aktar++; else $hata++;
    }
    return redirect('/efatura-gelen')->with('ok', $yeniKart . ' yeni malzeme kartı açıldı · ' . $aktar . ' fatura alış faturasına aktarıldı' . ($hata ? ' · ' . $hata . ' aktarılamadı' : '')
        . ($birimBek ? ' · ' . $birimBek . ' fatura birim çevrimi bekliyor (aynı ürün farklı birimle gelmiş; faturayı açıp "1 koli = kaç adet" girin)' : '') . ($yarim ? '. Süre doldu; kalan faturalar için butona bir kez daha basın.' : '. Fiyat Radarı artık bu verilerle çalışıyor.'));
});

// Tutarsız (toplamı tutmayan) faturayı bilerek aktar
Route::post('/efatura-gelen/{id}/aktar', function ($id) {
    if (!_efgErisim()) return redirect('/pos-giris');
    $sid = _webSubeId();
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', $sid)->first();
    if (!$g) return redirect('/efatura-gelen')->with('hata', 'Fatura bulunamadı.');
    $a = _efgAktar($g->id, _efgPersonelId());
    return redirect('/efatura-gelen/' . $g->id)->with(empty($a['ok']) ? 'hata' : 'ok', empty($a['ok']) ? ($a['hata'] ?? 'Aktarılamadı') : 'Alış faturasına aktarıldı ✓');
});

Route::post('/efatura-gelen/{id}/yoksay', function (Request $r, $id) {
    if (!_efgErisim()) return redirect('/pos-giris');
    $sid = _webSubeId();
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', $sid)->first();
    if (!$g || $g->durum === 'aktarildi') return redirect('/efatura-gelen')->with('hata', 'Bu fatura için işlem yapılamaz.');
    $geri = $r->boolean('geri');
    DB::table('efatura_gelen')->where('id', $g->id)->update(['durum' => $geri ? 'bekliyor' : 'yok_sayildi', 'updated_at' => now()]);
    return redirect($geri ? '/efatura-gelen/' . $g->id : '/efatura-gelen')->with('ok', $geri ? 'Fatura tekrar bekleyenlere alındı.' : 'Fatura yok sayıldı (alış faturasına aktarılmayacak).');
});

// Ticari fatura kabul / red (entegratör üzerinden, 8 gün içinde)
Route::post('/efatura-gelen/{id}/cevap', function (Request $r, $id) {
    if (!_efgErisim() || !_efgYonetici()) return redirect('/efatura-gelen/' . (int) $id)->with('hata', 'Kabul/red yalnız işletme sahibi ya da müdür tarafından gönderilebilir.');
    $sid = _webSubeId();
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', $sid)->first();
    $b = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
    $s = ($b && $b->entegrator) ? _efgSurucu($b->entegrator) : null;
    if (!$g || !$s || $g->profil !== 'TICARIFATURA' || $g->cevap) return redirect('/efatura-gelen/' . (int) $id)->with('hata', 'Bu faturaya yanıt verilemez.');
    $kabul = $r->input('islem') === 'kabul';
    $not = trim((string) $r->input('not'));
    if (!$kabul && mb_strlen($not) < 3) return redirect('/efatura-gelen/' . $g->id)->with('hata', 'Red için kısa bir gerekçe yaz.');
    $c = $s['cevap']($b, $g->ettn, $kabul, $not);
    if (empty($c['ok'])) return redirect('/efatura-gelen/' . $g->id)->with('hata', 'Gönderilemedi: ' . ($c['hata'] ?? '?'));
    DB::table('efatura_gelen')->where('id', $g->id)->update(['cevap' => $kabul ? 'kabul' : 'red', 'durum' => $kabul ? $g->durum : 'yok_sayildi',
        'not' => mb_substr(trim(($g->not ? $g->not . ' · ' : '') . ($kabul ? 'Kabul gönderildi' : 'Reddedildi: ' . $not)), 0, 255), 'updated_at' => now()]);
    return redirect('/efatura-gelen/' . $g->id)->with('ok', $kabul ? 'Kabul yanıtı gönderildi ✓' : 'Red yanıtı gönderildi ✓ (fatura alış faturasına aktarılmayacak)');
});

Route::get('/efatura-gelen/{id}/xml', function ($id) {
    if (!_efgErisim()) return redirect('/pos-giris');
    $g = DB::table('efatura_gelen')->where('id', (int) $id)->where('sube_id', _webSubeId())->first();
    if (!$g || !$g->xml_yol || !Storage::disk('local')->exists($g->xml_yol)) return redirect('/efatura-gelen')->with('hata', 'XML dosyası yok.');
    return Storage::disk('local')->download($g->xml_yol, ($g->fatura_no ?: $g->ettn) . '.xml', ['Content-Type' => 'application/xml']);
});

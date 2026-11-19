<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ===================== ÜRÜN NOTU + MASA ALERJİSİ (garson / web POS / QR ortak) =====================
// Kalem notu biçimi: "QR sipariş · Mısırsız · Yemekten sonra getirilsin · ALERJİ: Fıstık, Gluten" (' · ' ayraç).
// Mutfak ekranı (web+Flutter) ve basılı fiş bu parçaları ayrı satır/etiket gösterir; ALERJİ kırmızı şerit olur.
// Masa alerjisi adisyonlar.alerji'de durur: o adisyona sonradan eklenen HER kaleme otomatik yazılır.
// Sağlık konusu: masa alerjisi tek bir üründen silinemez (yalnız masa alerjisinden çıkarılınca kalkar).

if (!function_exists('_adisyonAlerjiEnsure')) {
    function _adisyonAlerjiEnsure()
    {
        static $tamam = false;
        if ($tamam) return;
        try {
            if (!Schema::hasColumn('adisyonlar', 'alerji')) {
                Schema::table('adisyonlar', function ($t) { $t->string('alerji', 250)->nullable(); });
            }
        } catch (\Throwable $e) {
        }
        $tamam = true;
    }
}

if (!function_exists('_alerjiListe')) {
    /** "Fıstık, Gluten" / dizi -> tekil, temiz liste */
    function _alerjiListe($v)
    {
        $l = is_array($v) ? $v : explode(',', (string) $v);
        $out = [];
        foreach ($l as $x) {
            $x = mb_substr(trim(strip_tags((string) $x)), 0, 40);
            if ($x !== '' && !in_array(mb_strtolower($x), array_map('mb_strtolower', $out), true)) $out[] = $x;
        }
        return array_slice($out, 0, 12);
    }
}

if (!function_exists('_adisyonAlerjiler')) {
    function _adisyonAlerjiler($adisyonId)
    {
        _adisyonAlerjiEnsure();
        try { return _alerjiListe((string) DB::table('adisyonlar')->where('id', (int) $adisyonId)->value('alerji')); } catch (\Throwable $e) { return []; }
    }
}

if (!function_exists('_kalemNotKur')) {
    /**
     * Kalem notunu yeniden kurar. $kullanici null ise mevcut (QR/alerji dışı) parçalar korunur.
     * $kullanici içindeki "ALERJİ: X" parçaları ürüne özel alerjidir; $masaAlerji her zaman eklenir; $cikar alerjileri silinir.
     */
    function _kalemNotKur($eski, $kullanici = null, array $masaAlerji = [], array $cikar = [])
    {
        $parca = fn ($s) => array_values(array_filter(array_map('trim', preg_split('/\s*·\s*|\n/u', (string) $s)), 'strlen'));
        $alerjiMi = fn ($p) => (bool) preg_match('/^ALERJ[İI]\s*:/iu', $p);
        $alerjiAd = fn ($p) => _alerjiListe(preg_replace('/^ALERJ[İI]\s*:\s*/iu', '', $p));
        $onEk = [];
        $notlar = [];
        $alerji = [];
        foreach ($parca($eski) as $p) {
            if (mb_strpos($p, 'QR sipariş') === 0) $onEk[] = $p;
            elseif ($alerjiMi($p)) $alerji = array_merge($alerji, $alerjiAd($p));
            elseif ($kullanici === null) $notlar[] = $p;
        }
        if ($kullanici !== null) {
            $alerji = [];   // ürüne özel alerji de kullanıcının gönderdiğinden gelir (masa alerjisi aşağıda geri eklenir)
            foreach (array_slice($parca(mb_substr(strip_tags((string) $kullanici), 0, 800)), 0, 15) as $p) {
                if (mb_strpos($p, 'QR sipariş') === 0) continue;
                if ($alerjiMi($p)) $alerji = array_merge($alerji, $alerjiAd($p));
                elseif (!in_array($p = mb_substr($p, 0, 120), $notlar, true)) $notlar[] = $p;
            }
        }
        $cikarK = array_map('mb_strtolower', $cikar);
        $alerji = array_values(array_filter($alerji, fn ($a) => !in_array(mb_strtolower($a), $cikarK, true)));
        $alerji = _alerjiListe(array_merge($alerji, $masaAlerji));
        $hepsi = array_merge($onEk, $notlar, $alerji ? ['ALERJİ: ' . implode(', ', $alerji)] : []);
        $s = implode(' · ', $hepsi);
        if (mb_strlen($s) > 250 && $alerji) {   // sığmazsa önce serbest notlar kısalır, ALERJİ asla kesilmez
            $a = ' · ALERJİ: ' . implode(', ', $alerji);
            $s = mb_substr(implode(' · ', array_merge($onEk, $notlar)), 0, max(0, 250 - mb_strlen($a))) . $a;
            $s = preg_replace('/^[\s·]+/u', '', $s);
        }
        return $s === '' ? null : mb_substr($s, 0, 250);
    }
}

if (!function_exists('_notAlerjileri')) {
    /** Kalem notundaki "ALERJİ: X, Y" -> [X, Y] */
    function _notAlerjileri($not)
    {
        return preg_match('/ALERJ[İI]\s*:\s*([^·]+)/iu', (string) $not, $m) ? _alerjiListe($m[1]) : [];
    }
}

if (!function_exists('_alerjiRiskliUrunler')) {
    /** Bu alerjilere göre riskli ürün adları (ürünlerin alerjen kaydından). Kayıt yoksa boş. */
    function _alerjiRiskliUrunler($subeId, array $alerjiler, array $urunAdlari)
    {
        if (!$alerjiler || !$urunAdlari) return [];
        try { return (new \App\Services\MusteriAsistan($subeId))->alerjiRiskli($alerjiler, $urunAdlari); } catch (\Throwable $e) { return []; }
    }
}

if (!function_exists('_adisyonAlerjiAyarla')) {
    /** Masa alerjisini kaydeder, adisyonun bitmemiş BÜTÜN kalemlerine yansıtır. Dönüş: [alerjiler, riskli ürünler] */
    function _adisyonAlerjiAyarla($a, array $yeni)
    {
        _adisyonAlerjiEnsure();
        $eski = _alerjiListe((string) ($a->alerji ?? ''));
        $yeniK = array_map('mb_strtolower', $yeni);
        $cikan = array_values(array_filter($eski, fn ($x) => !in_array(mb_strtolower($x), $yeniK, true)));
        DB::table('adisyonlar')->where('id', $a->id)->update(['alerji' => $yeni ? implode(', ', $yeni) : null, 'updated_at' => now()]);
        $adlar = [];
        $degisen = 0;
        foreach (DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->whereNotIn('durum', ['iptal', 'servis'])->get(['id', 'not', 'urun_adi']) as $k) {
            $n = _kalemNotKur($k->not, null, $yeni, $cikan);
            if ($n !== $k->not) { DB::table('adisyon_kalemleri')->where('id', $k->id)->update(['not' => $n, 'updated_at' => now()]); $degisen++; }
            $adlar[] = $k->urun_adi;
        }
        return [$yeni, _alerjiRiskliUrunler($a->sube_id, $yeni, array_values(array_unique($adlar))), $degisen];
    }
}

if (!function_exists('_kalemNotGuncelle')) {
    function _kalemNotGuncelle($subeId, $kalemId, $not)
    {
        $k = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyonlar.id', '=', 'adisyon_kalemleri.adisyon_id')
            ->where('adisyon_kalemleri.id', (int) $kalemId)->where('adisyonlar.sube_id', (int) $subeId)
            ->first(['adisyon_kalemleri.id', 'adisyon_kalemleri.not', 'adisyon_kalemleri.durum', 'adisyon_kalemleri.adisyon_id', 'adisyon_kalemleri.urun_adi']);
        if (!$k) return ['ok' => 0, 'hata' => 'Ürün bulunamadı'];
        if (in_array($k->durum, ['iptal', 'servis'], true)) return ['ok' => 0, 'hata' => 'Servis edilmiş/iptal ürüne not yazılamaz.'];
        $yeni = _kalemNotKur($k->not, (string) $not, _adisyonAlerjiler($k->adisyon_id));
        DB::table('adisyon_kalemleri')->where('id', $k->id)->update(['not' => $yeni, 'updated_at' => now()]);
        $uyari = in_array($k->durum, ['hazir'], true) ? 'Ürün hazırlanmış; mutfağa sözlü de haber verin.' : null;
        return ['ok' => 1, 'not' => $yeni, 'uyari' => $uyari];
    }
}

// ---- Flutter (garson / masaüstü) ----
Route::post('/api/patron/kalem-not', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Sipariş düzenleme yetkiniz yok.'];
    return _kalemNotGuncelle($p->sube_id, (int) $r->input('kalem_id'), (string) $r->input('not', ''));
});

Route::post('/api/patron/adisyon-alerji', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 401);
    if (!_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Sipariş düzenleme yetkiniz yok.'];
    _adisyonAlerjiEnsure();
    $a = DB::table('adisyonlar')->where('id', (int) $r->input('adisyon_id'))->where('sube_id', $p->sube_id)->first();
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    [$al, $riskli] = _adisyonAlerjiAyarla($a, _alerjiListe($r->input('alerji', '')));
    return ['ok' => 1, 'alerji' => $al, 'riskli' => $riskli];
});

// ---- Web POS (oturum) ----
Route::post('/pos/kalem-not', function (Request $r) {
    if ($g = _posKapi()) return $g;
    return _kalemNotGuncelle(_aktifSubeId(), (int) $r->input('kalem_id'), (string) $r->input('not', ''));
});

Route::post('/pos/adisyon-alerji', function (Request $r) {
    if ($g = _posKapi()) return $g;
    _adisyonAlerjiEnsure();
    $a = DB::table('adisyonlar')->where('id', (int) $r->input('adisyon_id'))->where('sube_id', _aktifSubeId())->first();
    if (!$a) return ['ok' => 0, 'hata' => 'Adisyon bulunamadı'];
    [$al, $riskli] = _adisyonAlerjiAyarla($a, _alerjiListe($r->input('alerji', '')));
    return ['ok' => 1, 'alerji' => $al, 'riskli' => $riskli];
});

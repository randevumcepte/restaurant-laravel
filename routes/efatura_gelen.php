<?php
/*
 * ============================================================================
 *  GELEN e-FATURA (UBL-TR) → ALIŞ FATURASI  (web.php sonunda require)
 *  Elektra modeli: işletme kendi entegratörünü seçer, biz onun API'sinden gelen kutusunu çekeriz.
 *  Kanallar: entegratör API (sürücü) · XML/ZIP yükleme (her entegratörde çalışır)
 *  Akış: XML → çöz → tutarlılık (kalem toplamı = fatura toplamı) → tedarikçi (VKN) → kalem eşleştirme
 *        (hafıza: VKN+stok kodu → malzeme) → hepsi eşleştiyse otomatik alış faturası → Fiyat Radarı / katalog
 *  Stok: 'mal_kabul' modunda fatura yalnız finansal (stok mal kabulde yazıldı) + mal kabulle eşleşir;
 *        'fatura' modunda stok girişini fatura yapar (mal kabul kullanmayan küçük işletme).
 *  Tablolar: efatura_baglanti · efatura_gelen · efatura_gelen_kalem · efatura_eslesme
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

if (!function_exists('_efgEnsure')) {
    function _efgEnsure()
    {
        if (cache()->get('efatura_gelen_ensure_ok1')) return;
        try {
            if (!Schema::hasTable('efatura_baglanti')) {
                Schema::create('efatura_baglanti', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->unique();
                    $t->string('entegrator', 20)->nullable();          // nilvera | izibiz | uyumsoft | qnb | edm | turkcell | diger
                    $t->text('api_anahtar')->nullable();                // Crypt ile şifreli
                    $t->string('ortam', 8)->default('canli');           // canli | test
                    $t->tinyInteger('aktif')->default(0);
                    $t->string('stok_modu', 12)->nullable();            // mal_kabul | fatura (NULL = otomatik tespit)
                    $t->date('baslangic')->nullable();                  // ilk çekimde bu tarihten itibaren
                    $t->dateTime('son_cekim')->nullable();
                    $t->string('son_sonuc', 255)->nullable();
                    $t->string('son_hata', 255)->nullable();
                    $t->timestamps();
                });
            }
            if (!Schema::hasTable('efatura_gelen')) {
                Schema::create('efatura_gelen', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id');
                    $t->string('ettn', 64);
                    $t->string('fatura_no', 40)->nullable();
                    $t->string('profil', 30)->nullable();               // TICARIFATURA | TEMELFATURA ...
                    $t->string('tip', 20)->nullable();                  // SATIS | IADE ...
                    $t->date('tarih')->nullable();
                    $t->string('satici_vkn', 16)->nullable();
                    $t->string('satici_ad', 200)->nullable();
                    $t->string('alici_vkn', 16)->nullable();
                    $t->unsignedBigInteger('tedarikci_id')->nullable();
                    $t->string('para_birimi', 3)->default('TRY');
                    $t->decimal('kur', 12, 6)->default(1);
                    $t->decimal('ara_toplam', 14, 2)->default(0);       // kalemlerin net toplamı (LineExtension)
                    $t->decimal('iskonto', 14, 2)->default(0);          // belge altı iskonto
                    $t->decimal('kdv_toplam', 14, 2)->default(0);
                    $t->decimal('genel_toplam', 14, 2)->default(0);     // ödenecek (Payable)
                    $t->date('vade_tarihi')->nullable();
                    $t->tinyInteger('tutarli')->default(1);             // kalem toplamı = belge toplamı
                    $t->string('kaynak', 20)->default('xml');           // xml | api:nilvera ...
                    $t->string('durum', 16)->default('bekliyor');       // bekliyor | aktarildi | yok_sayildi | iade
                    $t->string('not', 255)->nullable();
                    $t->unsignedBigInteger('alis_fatura_id')->nullable();
                    $t->string('cevap', 10)->nullable();                // kabul | red (ticari fatura yanıtı)
                    $t->string('xml_yol', 200)->nullable();
                    $t->timestamps();
                    $t->unique(['sube_id', 'ettn']);
                    $t->index(['sube_id', 'durum']);
                });
            }
            if (!Schema::hasTable('efatura_gelen_kalem')) {
                Schema::create('efatura_gelen_kalem', function ($t) {
                    $t->id(); $t->unsignedBigInteger('gelen_id')->index();
                    $t->integer('sira')->default(0);
                    $t->string('stok_kodu', 60)->nullable();
                    $t->string('ad', 255);
                    $t->decimal('miktar', 14, 3)->default(0);
                    $t->string('birim_kodu', 8)->nullable();            // UBL unitCode (C62, KGM, BX ...)
                    $t->decimal('birim_fiyat', 14, 4)->default(0);      // NET (iskonto sonrası) birim fiyat, TL
                    $t->decimal('liste_fiyat', 14, 4)->default(0);      // faturadaki PriceAmount
                    $t->decimal('iskonto', 14, 2)->default(0);
                    $t->decimal('kdv_yuzde', 6, 2)->default(0);
                    $t->decimal('kdv_tutar', 14, 2)->default(0);
                    $t->decimal('satir_tutar', 14, 2)->default(0);      // net satır tutarı, TL
                    $t->unsignedBigInteger('malzeme_id')->nullable();
                    $t->unsignedBigInteger('alis_birim_id')->nullable();
                    $t->string('eslesme', 10)->nullable();              // hafiza | ad | elle
                });
            }
            if (!Schema::hasTable('efatura_eslesme')) {
                Schema::create('efatura_eslesme', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id');
                    $t->string('satici_vkn', 16);
                    $t->string('anahtar', 120);                          // 'kod:<stok kodu>' | 'ad:<normalize ad>'
                    $t->unsignedBigInteger('malzeme_id');
                    $t->unsignedBigInteger('alis_birim_id')->nullable();
                    $t->integer('kullanim')->default(0);
                    $t->timestamps();
                    $t->unique(['sube_id', 'satici_vkn', 'anahtar']);
                });
            }
            if (Schema::hasTable('alis_faturalari')) {
                if (!Schema::hasColumn('alis_faturalari', 'ettn')) Schema::table('alis_faturalari', fn ($t) => $t->string('ettn', 64)->nullable()->index());
                if (!Schema::hasColumn('alis_faturalari', 'kaynak')) Schema::table('alis_faturalari', fn ($t) => $t->string('kaynak', 20)->nullable());
            }
            cache()->put('efatura_gelen_ensure_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

// Ad normalize (eşleştirme): küçük harf, Türkçe sade, tek boşluk
if (!function_exists('_efgNorm')) {
    function _efgNorm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $s = strtr($s, ['ı' => 'i', 'i̇' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'â' => 'a', 'î' => 'i', 'û' => 'u']);
        return trim(preg_replace('/\s+/', ' ', $s));
    }
}

// UBL birim kodu → [ad, kısaltma, tip]
if (!function_exists('_efgBirimHarita')) {
    function _efgBirimHarita()
    {
        return [
            'C62' => ['Adet', 'adet', 'adet'], 'NIU' => ['Adet', 'adet', 'adet'], 'H87' => ['Adet', 'adet', 'adet'], 'EA' => ['Adet', 'adet', 'adet'],
            'KGM' => ['Kilogram', 'kg', 'agirlik'], 'GRM' => ['Gram', 'g', 'agirlik'], 'TNE' => ['Ton', 'ton', 'agirlik'],
            'LTR' => ['Litre', 'lt', 'hacim'], 'MLT' => ['Mililitre', 'ml', 'hacim'],
            'PA' => ['Paket', 'paket', 'adet'], 'PK' => ['Paket', 'paket', 'adet'], 'PAK' => ['Paket', 'paket', 'adet'],
            'BX' => ['Koli', 'koli', 'adet'], 'CT' => ['Koli', 'koli', 'adet'], 'CS' => ['Kasa', 'kasa', 'adet'], 'KOLI' => ['Koli', 'koli', 'adet'],
            'SET' => ['Set', 'set', 'adet'], 'KT' => ['Set', 'set', 'adet'], 'DZN' => ['Düzine', 'duzine', 'adet'], 'PR' => ['Çift', 'cift', 'adet'],
            'RO' => ['Rulo', 'rulo', 'adet'], 'BG' => ['Torba', 'torba', 'adet'], 'BO' => ['Şişe', 'sise', 'adet'], 'CA' => ['Kutu', 'kutu', 'adet'],
            'MTR' => ['Metre', 'm', 'adet'], 'MTK' => ['Metrekare', 'm2', 'adet'], 'MTQ' => ['Metreküp', 'm3', 'hacim'],
        ];
    }
}
// UBL birim kodunun birimler.id karşılığı (yoksa birimi açar)
if (!function_exists('_efgBirimId')) {
    function _efgBirimId($kod)
    {
        $kod = strtoupper(trim((string) $kod));
        $h = _efgBirimHarita()[$kod] ?? null;
        if (!$h) $h = [$kod ?: 'Adet', mb_strtolower($kod ?: 'adet', 'UTF-8'), 'adet'];
        $id = DB::table('birimler')->where('kisaltma', $h[1])->value('id');
        if ($id) return (int) $id;
        $id = DB::table('birimler')->whereRaw('LOWER(ad) = ?', [mb_strtolower($h[0], 'UTF-8')])->value('id');
        if ($id) return (int) $id;
        return (int) DB::table('birimler')->insertGetId(['ad' => $h[0], 'kisaltma' => $h[1], 'tip' => $h[2], 'created_at' => now(), 'updated_at' => now()]);
    }
}

// UBL-TR XML'i çöz → başlık + kalemler. Hata: ['hata' => ...]
if (!function_exists('_efgUblCoz')) {
    function _efgUblCoz($xml)
    {
        $xml = (string) $xml;
        if (strncmp($xml, "\xEF\xBB\xBF", 3) === 0) $xml = substr($xml, 3);
        if (trim($xml) === '') return ['hata' => 'Boş dosya'];
        $dom = new \DOMDocument();
        $eski = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_COMPACT);
        libxml_clear_errors(); libxml_use_internal_errors($eski);
        if (!$ok || !$dom->documentElement) return ['hata' => 'XML okunamadı'];
        if ($dom->documentElement->localName !== 'Invoice') return ['hata' => 'UBL Invoice değil (' . $dom->documentElement->localName . ')'];
        $x = new \DOMXPath($dom);
        $x->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $x->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $root = $dom->documentElement;
        $metin = function ($yol, $bag = null) use ($x, $root) { $n = $x->query($yol, $bag ?: $root); return ($n && $n->length) ? trim($n->item(0)->textContent) : null; };
        $sayi = function ($yol, $bag = null) use ($metin) { $v = $metin($yol, $bag); return ($v === null || $v === '') ? null : (float) $v; };
        $taraf = function ($yol) use ($x, $root, $metin) {
            $p = $x->query($yol . '/cac:Party', $root); if (!$p || !$p->length) return ['vkn' => null, 'ad' => null];
            $p = $p->item(0); $vkn = null;
            foreach ($x->query('cac:PartyIdentification/cbc:ID', $p) as $id) {
                $sc = strtoupper((string) $id->getAttribute('schemeID'));
                if ($sc === 'VKN' || $sc === 'TCKN') { $vkn = preg_replace('/\D/', '', $id->textContent); break; }
            }
            $ad = $metin('cac:PartyName/cbc:Name', $p);
            if (!$ad) $ad = trim(($metin('cac:Person/cbc:FirstName', $p) ?? '') . ' ' . ($metin('cac:Person/cbc:FamilyName', $p) ?? ''));
            return ['vkn' => $vkn ?: null, 'ad' => $ad ?: null];
        };
        $satici = $taraf('cac:AccountingSupplierParty');
        $alici = $taraf('cac:AccountingCustomerParty');
        $para = strtoupper($metin('cbc:DocumentCurrencyCode') ?: 'TRY');
        if ($para === 'TL') $para = 'TRY';
        $kur = 1.0;
        if ($para !== 'TRY') { $kur = (float) ($sayi('cac:PricingExchangeRate/cbc:CalculationRate') ?: 0); if ($kur <= 0) return ['hata' => 'Döviz faturası ama kur yok (' . $para . ')']; }

        $kalemler = []; $kalemNet = 0.0;
        foreach ($x->query('cac:InvoiceLine', $root) as $i => $ln) {
            $miktarN = $x->query('cbc:InvoicedQuantity', $ln)->item(0);
            $miktar = $miktarN ? (float) $miktarN->textContent : 0.0;
            $birim = $miktarN ? strtoupper((string) $miktarN->getAttribute('unitCode')) : null;
            $net = (float) ($sayi('cbc:LineExtensionAmount', $ln) ?? 0);
            $liste = (float) ($sayi('cac:Price/cbc:PriceAmount', $ln) ?? 0);
            $isk = 0.0;
            foreach ($x->query('cac:AllowanceCharge', $ln) as $ac) {
                if (strtolower($metin('cbc:ChargeIndicator', $ac) ?? 'false') === 'false') $isk += (float) ($sayi('cbc:Amount', $ac) ?? 0);
            }
            $kdvY = 0.0; $kdvT = 0.0;
            foreach ($x->query('cac:TaxTotal/cac:TaxSubtotal', $ln) as $ts) {
                if (($metin('cac:TaxCategory/cac:TaxScheme/cbc:TaxTypeCode', $ts) ?? '0015') !== '0015') continue; // yalnız KDV
                $kdvY = (float) ($sayi('cbc:Percent', $ts) ?? $kdvY); $kdvT += (float) ($sayi('cbc:TaxAmount', $ts) ?? 0);
            }
            $ad = $metin('cac:Item/cbc:Name', $ln) ?: ($metin('cac:Item/cbc:Description', $ln) ?: 'Kalem ' . ($i + 1));
            $kod = $metin('cac:Item/cac:SellersItemIdentification/cbc:ID', $ln) ?: $metin('cac:Item/cac:ManufacturersItemIdentification/cbc:ID', $ln);
            $kalemNet += $net;
            $kalemler[] = [
                'sira' => (int) ($metin('cbc:ID', $ln) ?: $i + 1), 'stok_kodu' => $kod ? mb_substr($kod, 0, 60) : null, 'ad' => mb_substr(preg_replace('/\s+/u', ' ', $ad), 0, 255),
                'miktar' => round($miktar, 3), 'birim_kodu' => $birim ? mb_substr($birim, 0, 8) : null,
                'birim_fiyat' => $miktar > 0 ? round($net / $miktar * $kur, 4) : round($liste * $kur, 4),
                'liste_fiyat' => round($liste * $kur, 4), 'iskonto' => round($isk * $kur, 2),
                'kdv_yuzde' => $kdvY, 'kdv_tutar' => round($kdvT * $kur, 2), 'satir_tutar' => round($net * $kur, 2),
            ];
        }
        if (!$kalemler) return ['hata' => 'Faturada kalem yok'];
        $lm = 'cac:LegalMonetaryTotal/';
        $ara = $sayi($lm . 'cbc:LineExtensionAmount') ?? $kalemNet;
        $iskB = (float) ($sayi($lm . 'cbc:AllowanceTotalAmount') ?? 0);
        $odenecek = $sayi($lm . 'cbc:PayableAmount') ?? $sayi($lm . 'cbc:TaxInclusiveAmount') ?? 0;
        $kdvTop = 0.0;
        foreach ($x->query('cac:TaxTotal/cac:TaxSubtotal', $root) as $ts) {
            if (($metin('cac:TaxCategory/cac:TaxScheme/cbc:TaxTypeCode', $ts) ?? '0015') === '0015') $kdvTop += (float) ($sayi('cbc:TaxAmount', $ts) ?? 0);
        }
        // Tutarlılık: kalemlerin net toplamı belge toplamını tutmalı (yuvarlama payı: 1 kuruş/kalem, en az 0,05)
        $pay = max(0.05, 0.01 * count($kalemler));
        $tutarli = abs($kalemNet - $ara) <= $pay ? 1 : 0;
        $tip = strtoupper($metin('cbc:InvoiceTypeCode') ?: 'SATIS');
        $vade = $metin('cac:PaymentMeans/cbc:PaymentDueDate') ?: $metin('cac:PaymentTerms/cbc:PaymentDueDate');
        return [
            'ettn' => $metin('cbc:UUID'), 'fatura_no' => $metin('cbc:ID'), 'profil' => $metin('cbc:ProfileID'), 'tip' => $tip,
            'tarih' => $metin('cbc:IssueDate'), 'vade_tarihi' => ($vade && preg_match('/^\d{4}-\d{2}-\d{2}$/', $vade)) ? $vade : null,
            'satici_vkn' => $satici['vkn'], 'satici_ad' => $satici['ad'], 'alici_vkn' => $alici['vkn'],
            'para_birimi' => $para, 'kur' => $kur,
            'ara_toplam' => round($ara * $kur, 2), 'iskonto' => round($iskB * $kur, 2), 'kdv_toplam' => round($kdvTop * $kur, 2),
            'genel_toplam' => round($odenecek * $kur, 2), 'tutarli' => $tutarli, 'kalemler' => $kalemler,
        ];
    }
}

// Şubenin görebildiği malzemeler (id, ad, temel_birim_id) — istek boyunca önbellekli
if (!function_exists('_efgMalzemeler')) {
    function _efgMalzemeler($sid)
    {
        static $c = [];
        if (isset($c[$sid])) return $c[$sid];
        $q = DB::table('malzemeler');
        if (function_exists('_katalogFiltre')) $q = _katalogFiltre($q, 'malzeme', $sid, 'malzemeler.id');
        elseif (Schema::hasColumn('malzemeler', 'sube_id')) $q->where('sube_id', $sid);
        return $c[$sid] = $q->orderBy('ad')->get(['malzemeler.id', 'malzemeler.ad', 'malzemeler.temel_birim_id']);
    }
}

// Tedarikçiyi VKN ile bul; yoksa aç (yalnız bu işletmeye görünür)
if (!function_exists('_efgTedarikci')) {
    function _efgTedarikci($sid, $vkn, $ad)
    {
        if (function_exists('_restoTedarikEnsure')) _restoTedarikEnsure();
        $vknVar = Schema::hasColumn('tedarikciler', 'vkn_tckn');
        if ($vkn && $vknVar) {
            $q = DB::table('tedarikciler')->where('vkn_tckn', $vkn);
            if (function_exists('_katalogFiltre')) $q = _katalogFiltre($q, 'tedarikci', $sid, 'tedarikciler.id');
            $id = $q->value('tedarikciler.id');
            if ($id) return (int) $id;
        }
        if ($ad) {
            $q = DB::table('tedarikciler')->whereRaw('LOWER(ad) = ?', [mb_strtolower($ad, 'UTF-8')]);
            if (function_exists('_katalogFiltre')) $q = _katalogFiltre($q, 'tedarikci', $sid, 'tedarikciler.id');
            $id = $q->value('tedarikciler.id');
            if ($id) { if ($vkn && $vknVar) DB::table('tedarikciler')->where('id', $id)->whereNull('vkn_tckn')->update(['vkn_tckn' => $vkn]); return (int) $id; }
        }
        $kol = ['ad' => mb_substr($ad ?: ('VKN ' . $vkn), 0, 190), 'created_at' => now(), 'updated_at' => now()];
        foreach (['vkn_tckn' => $vkn, 'unvan' => $ad ? mb_substr($ad, 0, 160) : null, 'aktif' => 1, 'sube_id' => $sid] as $k => $v) if (Schema::hasColumn('tedarikciler', $k)) $kol[$k] = $v;
        $id = (int) DB::table('tedarikciler')->insertGetId($kol);
        if (function_exists('_katalogSahiplen')) _katalogSahiplen('tedarikci', $id, $sid);
        return $id;
    }
}

// Kalem için hafızadan / birebir addan malzeme bul
if (!function_exists('_efgKalemEslestir')) {
    function _efgKalemEslestir($sid, $vkn, $k)
    {
        $vkn = (string) $vkn;
        $anahtarlar = [];
        if (!empty($k['stok_kodu'])) $anahtarlar[] = 'kod:' . mb_substr(trim($k['stok_kodu']), 0, 110);
        $anahtarlar[] = 'ad:' . mb_substr(_efgNorm($k['ad']), 0, 115);
        foreach ($anahtarlar as $a) {
            $h = DB::table('efatura_eslesme')->where('sube_id', $sid)->where('satici_vkn', $vkn)->where('anahtar', $a)->first();
            if ($h && DB::table('malzemeler')->where('id', $h->malzeme_id)->exists()) return ['malzeme_id' => (int) $h->malzeme_id, 'alis_birim_id' => $h->alis_birim_id ? (int) $h->alis_birim_id : null, 'eslesme' => 'hafiza'];
        }
        $n = _efgNorm($k['ad']);
        foreach (_efgMalzemeler($sid) as $m) if (_efgNorm($m->ad) === $n) return ['malzeme_id' => (int) $m->id, 'alis_birim_id' => null, 'eslesme' => 'ad'];
        return null;
    }
}
// Eşleştirmeyi hafızaya yaz (stok kodu varsa onunla, yoksa adla)
if (!function_exists('_efgHafizaYaz')) {
    function _efgHafizaYaz($sid, $vkn, $kalem, $malzemeId, $birimId)
    {
        if (!$vkn || !$malzemeId) return;
        $a = !empty($kalem->stok_kodu) ? 'kod:' . mb_substr(trim($kalem->stok_kodu), 0, 110) : 'ad:' . mb_substr(_efgNorm($kalem->ad), 0, 115);
        $var = DB::table('efatura_eslesme')->where('sube_id', $sid)->where('satici_vkn', $vkn)->where('anahtar', $a)->first();
        if ($var) DB::table('efatura_eslesme')->where('id', $var->id)->update(['malzeme_id' => $malzemeId, 'alis_birim_id' => $birimId, 'kullanim' => $var->kullanim + 1, 'updated_at' => now()]);
        else DB::table('efatura_eslesme')->insert(['sube_id' => $sid, 'satici_vkn' => $vkn, 'anahtar' => $a, 'malzeme_id' => $malzemeId, 'alis_birim_id' => $birimId, 'kullanim' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }
}

// Stok modu: ayar yoksa son 90 günde mal kabul kaydı olan işletme 'mal_kabul', olmayan 'fatura'
if (!function_exists('_efgStokModu')) {
    function _efgStokModu($sid)
    {
        $m = DB::table('efatura_baglanti')->where('sube_id', $sid)->value('stok_modu');
        if (in_array($m, ['mal_kabul', 'fatura'], true)) return $m;
        try { if (Schema::hasTable('mal_kabuller') && DB::table('mal_kabuller')->where('sube_id', $sid)->where('created_at', '>=', now()->subDays(90))->exists()) return 'mal_kabul'; } catch (\Throwable $e) {}
        return 'fatura';
    }
}

// XML'i kaydet: çöz + mükerrer engeli + tedarikçi + eşleştirme + (hepsi eşleştiyse) otomatik aktar
// Dönüş: ['durum' => yeni|var|hata, 'id' =>, 'aktarildi' => bool, 'mesaj' =>]
if (!function_exists('_efgKaydet')) {
    function _efgKaydet($sid, $xml, $kaynak = 'xml')
    {
        _efgEnsure();
        $u = _efgUblCoz($xml);
        if (!empty($u['hata'])) return ['durum' => 'hata', 'mesaj' => $u['hata']];
        if (empty($u['ettn'])) return ['durum' => 'hata', 'mesaj' => 'ETTN (UUID) yok'];
        $var = DB::table('efatura_gelen')->where('sube_id', $sid)->where('ettn', $u['ettn'])->value('id');
        if ($var) return ['durum' => 'var', 'id' => (int) $var, 'mesaj' => ($u['fatura_no'] ?: $u['ettn']) . ' zaten kayıtlı'];
        $yol = null;
        try { $yol = 'efatura/' . $sid . '/' . preg_replace('/[^A-Za-z0-9\-]/', '', $u['ettn']) . '.xml'; Storage::disk('local')->put($yol, $xml); } catch (\Throwable $e) { $yol = null; }
        $tedId = ($u['satici_vkn'] || $u['satici_ad']) ? _efgTedarikci($sid, $u['satici_vkn'], $u['satici_ad']) : null;
        $iade = in_array($u['tip'], ['IADE', 'TEVKIFATIADE', 'YTBIADE'], true);
        $notlar = [];
        if (!$u['tutarli']) $notlar[] = 'Kalem toplamı belge toplamını tutmuyor';
        $bizimVkn = null;
        try { $bizimVkn = preg_replace('/\D/', '', (string) DB::table('edonusum_ayarlari')->where('sube_id', $sid)->value('vkn_tckn')); } catch (\Throwable $e) {}
        if ($bizimVkn && $u['alici_vkn'] && $bizimVkn !== $u['alici_vkn']) $notlar[] = 'Alıcı VKN (' . $u['alici_vkn'] . ') bu işletmenin VKN\'si değil';
        $id = DB::transaction(function () use ($sid, $u, $kaynak, $yol, $tedId, $iade, $notlar) {
            $id = (int) DB::table('efatura_gelen')->insertGetId([
                'sube_id' => $sid, 'ettn' => $u['ettn'], 'fatura_no' => $u['fatura_no'] ? mb_substr($u['fatura_no'], 0, 40) : null,
                'profil' => $u['profil'] ? mb_substr($u['profil'], 0, 30) : null, 'tip' => mb_substr($u['tip'], 0, 20),
                'tarih' => ($u['tarih'] && preg_match('/^\d{4}-\d{2}-\d{2}$/', $u['tarih'])) ? $u['tarih'] : null,
                'satici_vkn' => $u['satici_vkn'], 'satici_ad' => $u['satici_ad'] ? mb_substr($u['satici_ad'], 0, 200) : null, 'alici_vkn' => $u['alici_vkn'],
                'tedarikci_id' => $tedId, 'para_birimi' => $u['para_birimi'], 'kur' => $u['kur'],
                'ara_toplam' => $u['ara_toplam'], 'iskonto' => $u['iskonto'], 'kdv_toplam' => $u['kdv_toplam'], 'genel_toplam' => $u['genel_toplam'],
                'vade_tarihi' => $u['vade_tarihi'], 'tutarli' => $u['tutarli'], 'kaynak' => mb_substr($kaynak, 0, 20),
                'durum' => $iade ? 'iade' : 'bekliyor', 'not' => $notlar ? mb_substr(implode(' · ', $notlar), 0, 255) : null,
                'xml_yol' => $yol, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($u['kalemler'] as $k) {
                $e = _efgKalemEslestir($sid, $u['satici_vkn'], $k);
                $birimId = ($e && $e['alis_birim_id']) ? $e['alis_birim_id'] : _efgBirimId($k['birim_kodu']);
                DB::table('efatura_gelen_kalem')->insert($k + ['gelen_id' => $id, 'malzeme_id' => $e['malzeme_id'] ?? null, 'alis_birim_id' => $birimId, 'eslesme' => $e['eslesme'] ?? null]);
            }
            return $id;
        });
        $aktarildi = false;
        if (!$iade && $u['tutarli'] && !DB::table('efatura_gelen_kalem')->where('gelen_id', $id)->whereNull('malzeme_id')->exists()) {
            $a = _efgAktar($id, null);
            $aktarildi = !empty($a['ok']);
        }
        return ['durum' => 'yeni', 'id' => $id, 'aktarildi' => $aktarildi, 'mesaj' => ($u['fatura_no'] ?: $u['ettn'])];
    }
}

// Gelen e-faturayı alış faturasına aktar (tüm kalemler eşleşmiş olmalı)
if (!function_exists('_efgAktar')) {
    function _efgAktar($gelenId, $personelId = null)
    {
        _efgEnsure();
        $g = DB::table('efatura_gelen')->where('id', $gelenId)->first();
        if (!$g) return ['ok' => 0, 'hata' => 'Fatura bulunamadı'];
        if ($g->durum === 'aktarildi') return ['ok' => 1, 'id' => (int) $g->alis_fatura_id, 'zaten' => 1];
        if ($g->durum === 'iade') return ['ok' => 0, 'hata' => 'İade faturası alış faturasına aktarılmaz'];
        $kalemler = DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->orderBy('sira')->get();
        if (!$kalemler->count() || $kalemler->whereNull('malzeme_id')->count()) return ['ok' => 0, 'hata' => 'Eşleşmeyen kalem var'];
        $sid = (int) $g->sube_id;
        if (function_exists('_restoSatinalmaEnsure')) _restoSatinalmaEnsure();
        if (function_exists('_restoGiderEnsure')) _restoGiderEnsure();
        $mod = _efgStokModu($sid);
        try {
            return DB::transaction(function () use ($g, $kalemler, $sid, $mod, $personelId) {
                // Aynı fatura elle girilmişse ikinci kez yazma: ona bağla
                $mevcut = null;
                if ($g->fatura_no) $mevcut = DB::table('alis_faturalari')->where('sube_id', $sid)->where('fatura_no', $g->fatura_no)
                    ->where(function ($w) use ($g) { $w->where('tedarikci_id', $g->tedarikci_id)->orWhereNull('tedarikci_id'); })->value('id');
                if ($mevcut) {
                    DB::table('alis_faturalari')->where('id', $mevcut)->update(['ettn' => $g->ettn, 'updated_at' => now()]);
                    DB::table('efatura_gelen')->where('id', $g->id)->update(['durum' => 'aktarildi', 'alis_fatura_id' => $mevcut, 'not' => 'Bu fatura önceden elle girilmişti; ona bağlandı', 'updated_at' => now()]);
                    return ['ok' => 1, 'id' => (int) $mevcut, 'zaten' => 1];
                }
                $tarih = $g->tarih ?: date('Y-m-d');
                $vade = $g->vade_tarihi;
                if (!$vade && $g->tedarikci_id && Schema::hasColumn('tedarikciler', 'odeme_vade_gun')) {
                    $gun = (int) DB::table('tedarikciler')->where('id', $g->tedarikci_id)->value('odeme_vade_gun');
                    if ($gun > 0) $vade = \Carbon\Carbon::parse($tarih)->addDays($gun)->toDateString();
                }
                // Mal kabul eşleşmesi (aynı tedarikçi, fatura tarihinden 7 gün önce – 3 gün sonra, faturası olmayan)
                $mk = null; $eslesme = null;
                if ($mod === 'mal_kabul' && $g->tedarikci_id && Schema::hasTable('mal_kabuller')) {
                    $faturali = DB::table('alis_faturalari')->whereNotNull('mal_kabul_id')->pluck('mal_kabul_id')->all();
                    $mk = DB::table('mal_kabuller')->where('sube_id', $sid)->where('tedarikci_id', $g->tedarikci_id)
                        ->whereBetween('tarih', [\Carbon\Carbon::parse($tarih)->subDays(7)->toDateString(), \Carbon\Carbon::parse($tarih)->addDays(3)->toDateString()])
                        ->whereNotIn('id', $faturali ?: [0])->get()
                        ->sortBy(fn ($m) => abs(strtotime((string) $m->tarih) - strtotime($tarih)))->first(); // en yakın tarih (MySQL/SQLite ortak)
                    $eslesme = 'beklemede';
                }
                $frow = ['sube_id' => $sid, 'tedarikci_id' => $g->tedarikci_id, 'fatura_no' => $g->fatura_no, 'tarih' => $tarih, 'toplam' => $g->genel_toplam,
                    'durum' => 'onaylandi', 'giren_personel_id' => $personelId, 'onaylayan_personel_id' => $personelId, 'created_at' => now(), 'updated_at' => now()];
                foreach (['ara_toplam' => round($g->ara_toplam - $g->iskonto, 2), 'iskonto' => $g->iskonto, 'kdv_toplam' => $g->kdv_toplam, 'vade_tarihi' => $vade,
                    'mal_kabul_id' => $mk->id ?? null, 'siparis_id' => $mk->siparis_id ?? null, 'irsaliye_no' => $mk->irsaliye_no ?? null, 'eslesme_durumu' => $eslesme,
                    'ettn' => $g->ettn, 'kaynak' => 'efatura'] as $kol => $v) if (Schema::hasColumn('alis_faturalari', $kol)) $frow[$kol] = $v;
                $fid = (int) DB::table('alis_faturalari')->insertGetId($frow);
                $farklar = [];
                $mkMiktar = $mk ? DB::table('mal_kabul_kalemleri')->where('mal_kabul_id', $mk->id)->selectRaw('malzeme_id, SUM(kabul_miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id') : collect();
                $fMiktar = [];
                foreach ($kalemler as $k) {
                    $onceki = (float) DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'f.id', '=', 'k.fatura_id')->where('f.sube_id', $sid)
                        ->where('k.malzeme_id', $k->malzeme_id)->where('k.alis_birim_id', $k->alis_birim_id)->where('f.id', '!=', $fid)
                        ->orderByDesc('f.tarih')->orderByDesc('k.id')->value('k.birim_fiyat');
                    $fark = $onceki > 0 ? round(($k->birim_fiyat - $onceki) / $onceki * 100, 2) : 0.0;
                    $uyari = 'yesil'; if ($onceki > 0) { $a = abs($fark); $uyari = $a >= 15 ? 'kirmizi' : ($a >= 5 ? 'sari' : 'yesil'); }
                    $krow = ['fatura_id' => $fid, 'malzeme_id' => $k->malzeme_id, 'alis_birim_id' => $k->alis_birim_id, 'miktar' => $k->miktar, 'birim_fiyat' => $k->birim_fiyat,
                        'satir_toplam' => $k->satir_tutar, 'onceki_fiyat' => $onceki ?: null, 'fiyat_farki_yuzde' => $onceki > 0 ? $fark : null,
                        'fiyat_farki_tutar' => $onceki > 0 ? round(($k->birim_fiyat - $onceki) * $k->miktar, 2) : null, 'uyari' => $uyari, 'created_at' => now(), 'updated_at' => now()];
                    foreach (['kdv_yuzde' => $k->kdv_yuzde, 'kdv_tutar' => $k->kdv_tutar] as $kol => $v) if (Schema::hasColumn('alis_fatura_kalemleri', $kol)) $krow[$kol] = $v;
                    DB::table('alis_fatura_kalemleri')->insert($krow);
                    if ($g->tedarikci_id && function_exists('_tedarikMalzemeFiyat')) _tedarikMalzemeFiyat($sid, $g->tedarikci_id, $k->malzeme_id, $k->alis_birim_id, $k->birim_fiyat, $tarih);
                    $fMiktar[$k->malzeme_id] = ($fMiktar[$k->malzeme_id] ?? 0) + (float) $k->miktar;
                    if ($mod === 'fatura') _efgStokGiris($sid, $k, $fid, $personelId);
                }
                if ($mk) {
                    $adlar = DB::table('malzemeler')->whereIn('id', array_keys($fMiktar))->pluck('ad', 'id');
                    foreach ($fMiktar as $mid => $m) { $kab = (float) ($mkMiktar[$mid] ?? 0); if (abs($kab - $m) > 0.001) $farklar[] = ($adlar[$mid] ?? '#' . $mid) . ': irsaliye ' . $kab . ' ≠ fatura ' . $m; }
                    DB::table('alis_faturalari')->where('id', $fid)->update(['eslesme_durumu' => $farklar ? 'fark_var' : 'eslesti']);
                }
                if (Schema::hasTable('giderler')) {
                    $tedAd = $g->tedarikci_id ? DB::table('tedarikciler')->where('id', $g->tedarikci_id)->value('ad') : ($g->satici_ad ?: 'Tedarikçi');
                    DB::table('giderler')->insert(['sube_id' => $sid, 'kategori' => 'malzeme', 'aciklama' => mb_substr($tedAd . ($g->fatura_no ? ' · ' . $g->fatura_no : '') . ' (e-fatura)', 0, 250),
                        'tutar' => $g->genel_toplam, 'tarih' => $tarih, 'created_by' => $personelId]);
                }
                $not = $mk ? ('Mal kabul #' . $mk->id . ($farklar ? ' ile FARK: ' . implode(' · ', array_slice($farklar, 0, 3)) : ' ile eşleşti')) : ($mod === 'mal_kabul' ? 'Mal kabul bekleniyor' : 'Stok girişi faturadan yapıldı');
                DB::table('efatura_gelen')->where('id', $g->id)->update(['durum' => 'aktarildi', 'alis_fatura_id' => $fid, 'not' => mb_substr(trim(($g->not ? $g->not . ' · ' : '') . $not), 0, 255), 'updated_at' => now()]);
                return ['ok' => 1, 'id' => $fid, 'fark' => $farklar];
            });
        } catch (\Throwable $e) { return ['ok' => 0, 'hata' => 'Aktarılamadı: ' . mb_substr($e->getMessage(), 0, 120)]; }
    }
}

// 'fatura' modunda stok girişi (elle alış faturası girişiyle aynı mantık: temel birime çevir + ağırlıklı ortalama maliyet)
if (!function_exists('_efgStokGiris')) {
    function _efgStokGiris($sid, $k, $faturaId, $personelId)
    {
        if (!Schema::hasTable('stok_hareketleri')) return;
        $m = DB::table('malzemeler')->where('id', $k->malzeme_id)->first();
        if (!$m || (isset($m->stok_takipli) && !$m->stok_takipli)) return;
        $kars = function_exists('_restoBirimKarsilik') ? (float) _restoBirimKarsilik($m->id, $k->alis_birim_id, $m->temel_birim_id) : 1.0;
        if ($kars <= 0) $kars = 1.0;
        $temelMiktar = (float) $k->miktar * $kars; $temelMaliyet = (float) $k->birim_fiyat / $kars;
        $eskiStok = (float) DB::table('stok_hareketleri')->where('sube_id', $sid)->where('malzeme_id', $m->id)->sum('miktar');
        DB::table('stok_hareketleri')->insert(['sube_id' => $sid, 'malzeme_id' => $m->id, 'tip' => 'alis', 'miktar' => $temelMiktar, 'birim_maliyet' => $temelMaliyet,
            'kaynak_tip' => 'fatura', 'kaynak_id' => $faturaId, 'aciklama' => 'Alış faturası (e-fatura)', 'personel_id' => $personelId]);
        $eski = (float) $m->guncel_maliyet;
        $yeni = ($eskiStok > 0 && $eski > 0) ? ($eskiStok * $eski + $temelMiktar * $temelMaliyet) / ($eskiStok + $temelMiktar) : $temelMaliyet;
        DB::table('malzemeler')->where('id', $m->id)->update(['guncel_maliyet' => round($yeni, 4), 'updated_at' => now()]);
    }
}

require __DIR__ . '/efatura_gelen_surucu.php';
require __DIR__ . '/efatura_gelen_sayfa.php';

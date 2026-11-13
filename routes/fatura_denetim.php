<?php
/*
 * ============================================================================
 *  FATURA DENETİMİ + FATURA EXCEL'İ (efatura_gelen.php'den require) — yapay zekâ YOK, 0 TL
 *  Her gelen fatura (XML / PDF / OCR / API) kaydedilince ve alış faturasına aktarılınca _fdDenetle çalışır.
 *  Kontroller (kalem): hesap hatası · sözleşme fiyatı · sipariş fiyatı · mal kabulden fazla faturalama ·
 *    gizli zam (ürün küçülmüş) · iskonto kaybı · KDV oranı değişimi · zam (önceki alıma göre)
 *  Kontroller (fatura): toplam tutmuyor · mükerrer fatura · vade sözleşmeden kısa
 *  "Geri istenecek" (fark_tutar) yalnız belgeye dayanan alacaklar: sözleşme/sipariş fiyatı farkı, fazla faturalama,
 *    hesap hatası, iskonto kaybı, mükerrer. Zam / gizli zam "maliyet etkisi" (etki_tutar), alacak sayılmaz.
 *  /fatura-denetim          bulgular + tedarikçiye itiraz yazısı (şablon, ücretsiz) + durum takibi
 *  /efatura-gelen/excel     fatura Excel'i (pano + kalemler + faturalar + fiyat değişimi + zam izi + bulgular)
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_fdEnsure')) {
    function _fdEnsure()
    {
        if (cache()->get('fatura_denetim_ensure_ok1')) return;
        try {
            if (!Schema::hasTable('efatura_denetim')) {
                Schema::create('efatura_denetim', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id');
                    $t->unsignedBigInteger('gelen_id')->index();
                    $t->unsignedBigInteger('kalem_id')->nullable();
                    $t->unsignedBigInteger('tedarikci_id')->nullable();
                    $t->string('tur', 16);                               // hesap|sozlesme|siparis|mal_kabul|gizli_zam|iskonto|kdv|zam|toplam|mukerrer|vade
                    $t->string('seviye', 8);                             // kritik|uyari|bilgi
                    $t->string('baslik', 160);
                    $t->string('aciklama', 400)->nullable();
                    $t->decimal('fark_tutar', 14, 2)->default(0);        // tedarikçiden geri istenecek (belgeye dayalı)
                    $t->decimal('etki_tutar', 14, 2)->default(0);        // maliyet etkisi (zam vb., alacak değil)
                    $t->string('durum', 10)->default('acik');            // acik|itiraz|cozuldu|yoksay
                    $t->string('not', 255)->nullable();
                    $t->string('anahtar', 60);
                    $t->timestamps();
                    $t->unique(['gelen_id', 'anahtar']);
                    $t->index(['sube_id', 'durum']);
                });
            }
            cache()->put('fatura_denetim_ensure_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

// Ürün adındaki ambalaj boyutu: ['agirlik' => kg, 'hacim' => lt, 'adet' => n]
if (!function_exists('_fdBoyut')) {
    function _fdBoyut($ad)
    {
        $t = _fpKucuk($ad); $o = [];
        $n = fn ($s) => (float) str_replace(',', '.', $s);
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|kilo)\b/u', $t, $m)) $o['agirlik'] = $n($m[1]);
        elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(gr|g|gram)\b/u', $t, $m)) $o['agirlik'] = $n($m[1]) / 1000;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(lt|l|litre)\b/u', $t, $m)) $o['hacim'] = $n($m[1]);
        elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*ml\b/u', $t, $m)) $o['hacim'] = $n($m[1]) / 1000;
        elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*cl\b/u', $t, $m)) $o['hacim'] = $n($m[1]) / 100;
        if (preg_match('/(\d+)\s*[\'’]?\s*(li|lı|lu|lü|adet|ad\.?)(?![a-zçğıöşü])/u', $t, $m)) $o['adet'] = (float) $m[1];
        return array_filter($o, fn ($v) => $v > 0);
    }
}

// Aynı tedarikçiden aynı ürünün bir önceki alımı (stok kodu > malzeme > ad)
if (!function_exists('_fdOnceki')) {
    function _fdOnceki($g, $k)
    {
        if (!$g->satici_vkn) return null;
        $q = DB::table('efatura_gelen_kalem as k')->join('efatura_gelen as g', 'g.id', '=', 'k.gelen_id')
            ->where('g.sube_id', $g->sube_id)->where('g.satici_vkn', $g->satici_vkn)->where('g.id', '!=', $g->id)->whereNotIn('g.durum', ['yok_sayildi', 'iade'])
            ->where(function ($w) use ($g) { $w->where('g.tarih', '<', $g->tarih)->orWhere(function ($w2) use ($g) { $w2->where('g.tarih', $g->tarih)->where('g.id', '<', $g->id); }); });
        if ($k->stok_kodu) $q->where('k.stok_kodu', $k->stok_kodu);
        elseif ($k->malzeme_id) $q->where('k.malzeme_id', $k->malzeme_id);
        else $q->where('k.ad', $k->ad);
        return $q->orderByDesc('g.tarih')->orderByDesc('g.id')->select('k.*', 'g.tarih', 'g.fatura_no')->first();
    }
}

// Birim metninden birim id (anlaşmadaki serbest "kg", "koli" ...)
if (!function_exists('_fdBirimId')) {
    function _fdBirimId($metin)
    {
        $t = _fpKucuk(trim((string) $metin));
        if ($t === '') return null;
        static $c = [];
        return $c[$t] ??= (DB::table('birimler')->whereRaw('LOWER(kisaltma) = ?', [$t])->value('id') ?: DB::table('birimler')->whereRaw('LOWER(ad) = ?', [$t])->value('id'));
    }
}

if (!function_exists('_fdSayi')) {
    function _fdSayi($v) { return rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ','); }
}

if (!function_exists('_fdDenetle')) {
    function _fdDenetle($gelenId)
    {
        _fdEnsure();
        $g = DB::table('efatura_gelen')->where('id', $gelenId)->first();
        if (!$g) return [];
        $sid = (int) $g->sube_id; $tarih = $g->tarih ?: date('Y-m-d');
        $kalemler = DB::table('efatura_gelen_kalem')->where('gelen_id', $g->id)->orderBy('sira')->get();
        $tl = fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
        $yz = fn ($v) => ($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '.') . '%';
        $b = []; // anahtar => bulgu
        $ekle = function ($anahtar, $kalem, $tur, $seviye, $baslik, $aciklama, $fark = 0, $etki = 0) use (&$b) {
            $b[$anahtar] = ['kalem_id' => $kalem->id ?? null, 'tur' => $tur, 'seviye' => $seviye, 'baslik' => mb_substr($baslik, 0, 160), 'aciklama' => mb_substr((string) $aciklama, 0, 400),
                'fark_tutar' => round(max(0, $fark), 2), 'etki_tutar' => round($etki, 2)];
        };
        if ($g->durum !== 'iade') {
            // ---------- fatura düzeyi ----------
            if (!$g->tutarli) $ekle('toplam', null, 'toplam', 'kritik', 'Kalem toplamı fatura toplamını tutmuyor', 'Faturanın altındaki toplam ile satırların toplamı farklı; tedarikçiden düzeltilmiş fatura isteyin.');
            if ($g->satici_vkn) {
                $esler = DB::table('efatura_gelen')->where('sube_id', $sid)->where('id', '<', $g->id)->where('satici_vkn', $g->satici_vkn)
                    ->whereNotIn('durum', ['yok_sayildi', 'iade'])->whereRaw('ABS(genel_toplam - ?) < 0.01', [$g->genel_toplam])
                    ->whereBetween('tarih', [\Carbon\Carbon::parse($tarih)->subDays(20)->toDateString(), \Carbon\Carbon::parse($tarih)->addDays(20)->toDateString()])->get();
                foreach ($esler as $e) {
                    if ($e->fatura_no === $g->fatura_no) continue; // aynı numara = aynı belge
                    $ekle('mukerrer', null, 'mukerrer', 'kritik', 'Mükerrer fatura şüphesi', 'Aynı tedarikçiden aynı tutarda (' . $tl($g->genel_toplam) . ') ' . ($e->fatura_no ?: '#' . $e->id) . ' no\'lu fatura '
                        . ($e->tarih ? \Carbon\Carbon::parse($e->tarih)->format('d.m.Y') : '') . ' tarihinde de gelmiş. Aynı mal iki kez faturalanmış olabilir; kalemleri karşılaştırın.', $g->genel_toplam);
                    break;
                }
            }
            if ($g->vade_tarihi && $g->tedarikci_id) {
                $gun = null; $kaynak = 'sözleşme';
                try { if (Schema::hasTable('fiyat_sozlesmeleri')) $gun = DB::table('fiyat_sozlesmeleri')->where('sube_id', $sid)->where('tedarikci_id', $g->tedarikci_id)->where('aktif', 1)->whereNotNull('odeme_vade_gun')->orderByDesc('id')->value('odeme_vade_gun'); } catch (\Throwable $e) {}
                if (!$gun) { $gun = (int) DB::table('tedarikciler')->where('id', $g->tedarikci_id)->value('odeme_vade_gun'); $kaynak = 'tedarikçi kartı'; }
                $fGun = \Carbon\Carbon::parse($tarih)->diffInDays(\Carbon\Carbon::parse($g->vade_tarihi), false);
                if ($gun > 0 && $fGun < $gun - 1) $ekle('vade', null, 'vade', 'uyari', 'Vade anlaşılandan kısa', 'Faturada vade ' . (int) $fGun . ' gün, ' . $kaynak . ' ' . $gun . ' gün diyor. Ödemeyi ' . $gun . ' güne göre planlayın ya da düzeltme isteyin.');
            }
            // ---------- kalem düzeyi ----------
            $mk = $g->alis_fatura_id ? DB::table('alis_faturalari')->where('id', $g->alis_fatura_id)->value('mal_kabul_id') : null;
            $mkMiktar = $mk ? DB::table('mal_kabul_kalemleri')->where('mal_kabul_id', $mk)->selectRaw('malzeme_id, SUM(kabul_miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id') : collect();
            $fMiktar = [];
            $anlasmaVar = Schema::hasTable('fiyat_anlasmalari'); $siparisVar = Schema::hasTable('satinalma_siparis_kalemleri');
            foreach ($kalemler as $k) {
                $ad = $k->ad; $mik = (float) $k->miktar; $f = (float) $k->birim_fiyat;
                // hesap: miktar × liste fiyat − iskonto = satır
                if ((float) $k->liste_fiyat > 0) {
                    $bek = $mik * (float) $k->liste_fiyat - (float) $k->iskonto;
                    if ($k->satir_tutar - $bek > max(0.05, abs($bek) * 0.002))
                        $ekle('hesap:' . $k->id, $k, 'hesap', 'kritik', $ad . ': satır tutarı fazla', _fdSayi($mik) . ' × ' . $tl($k->liste_fiyat) . ' − iskonto ' . $tl($k->iskonto) . ' = ' . $tl($bek) . ' olmalı; faturada ' . $tl($k->satir_tutar) . '.', $k->satir_tutar - $bek);
                }
                // sözleşme fiyatı (birimi farklıysa malzemenin temel birimine çevrilerek)
                if ($k->malzeme_id && $g->tedarikci_id && $anlasmaVar) {
                    $a = DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('tedarikci_id', $g->tedarikci_id)->where('malzeme_id', $k->malzeme_id)->where('aktif', 1)
                        ->where(fn ($q) => $q->whereNull('baslangic')->orWhere('baslangic', '<=', $tarih))->where(fn ($q) => $q->whereNull('bitis')->orWhere('bitis', '>=', $tarih))->orderByDesc('id')->first();
                    if ($a && (float) $a->birim_fiyat > 0) {
                        $aBirim = _fdBirimId($a->birim); $fK = 1.0; $aK = 1.0; $kiyas = true;
                        if ($aBirim && $k->alis_birim_id && (int) $aBirim !== (int) $k->alis_birim_id && function_exists('_restoBirimKarsilik')) {
                            $fK = _restoBirimKarsilik($k->malzeme_id, $k->alis_birim_id); $aK = _restoBirimKarsilik($k->malzeme_id, $aBirim);
                            if ($fK <= 0 || $aK <= 0 || ($fK == 1.0 && $aK == 1.0)) $kiyas = false; // çevrim tanımsız: kıyaslama yapma
                        }
                        $fT = $f / $fK; $aT = (float) $a->birim_fiyat / $aK;
                        if ($kiyas && $fT > $aT * 1.005) {
                            $fark = ($fT - $aT) * $fK * $mik;
                            $ekle('sozlesme:' . $k->id, $k, 'sozlesme', 'kritik', $ad . ': sözleşme fiyatının üstünde', 'Sözleşme ' . $tl($a->birim_fiyat) . ($a->birim ? ' / ' . $a->birim : '') . ', faturada ' . $tl($f) . ' (' . $yz(($fT - $aT) / $aT * 100) . '). Fark × miktar geri istenmeli.', $fark, $fark);
                        }
                    }
                }
                // sipariş fiyatı (son 45 gün, aynı tedarikçi + malzeme + birim)
                if ($k->malzeme_id && $g->tedarikci_id && $siparisVar && !isset($b['sozlesme:' . $k->id])) {
                    $p = DB::table('satinalma_siparis_kalemleri as sk')->join('satinalma_siparisleri as s', 's.id', '=', 'sk.siparis_id')->where('s.sube_id', $sid)->where('s.tedarikci_id', $g->tedarikci_id)
                        ->where('sk.malzeme_id', $k->malzeme_id)->where('sk.alis_birim_id', $k->alis_birim_id)->whereNotIn('s.durum', ['iptal', 'taslak'])
                        ->whereBetween('s.tarih', [\Carbon\Carbon::parse($tarih)->subDays(45)->toDateString(), $tarih . ' 23:59:59'])->orderByDesc('s.tarih')->select('sk.birim_fiyat', 'sk.iskonto_yuzde', 's.siparis_no')->first();
                    if ($p && (float) $p->birim_fiyat > 0) {
                        $pf = (float) $p->birim_fiyat * (1 - (float) ($p->iskonto_yuzde ?? 0) / 100);
                        if ($f > $pf * 1.005) $ekle('siparis:' . $k->id, $k, 'siparis', 'kritik', $ad . ': sipariş fiyatının üstünde', ($p->siparis_no ?: 'Sipariş') . ' ' . $tl($pf) . ', faturada ' . $tl($f) . ' (' . $yz(($f - $pf) / $pf * 100) . ').', ($f - $pf) * $mik, ($f - $pf) * $mik);
                    }
                }
                if ($k->malzeme_id) $fMiktar[$k->malzeme_id][] = $k;
                // önceki alıma göre: gizli zam / zam / iskonto kaybı / KDV
                $o = _fdOnceki($g, $k);
                if (!$o) continue;
                $of = (float) $o->birim_fiyat; $oTarih = $o->tarih ? \Carbon\Carbon::parse($o->tarih)->format('d.m.Y') : '';
                $bo = _fdBoyut($o->ad); $by = _fdBoyut($ad); $kucul = null;
                foreach ($by as $tip => $deger) if (isset($bo[$tip]) && $deger < $bo[$tip] * 0.999) { $kucul = [$tip, $bo[$tip], $deger]; break; }
                if ($kucul && $o->birim_kodu === $k->birim_kodu && $of > 0) {
                    $esdeger = $of * $kucul[2] / $kucul[1]; // eski fiyatın yeni boyuttaki karşılığı
                    $etkiY = ($f - $esdeger) / $esdeger * 100;
                    if ($etkiY > 1) $ekle('gizli:' . $k->id, $k, 'gizli_zam', 'uyari', $ad . ': ürün küçülmüş', '"' . $o->ad . '" (' . $oTarih . ') → "' . $ad . '". Boyut ' . _fdSayi($kucul[1]) . ' → ' . _fdSayi($kucul[2])
                        . ', birim fiyat ' . $tl($of) . ' → ' . $tl($f) . '. Gerçek zam ' . $yz($etkiY) . '.', 0, ($f - $esdeger) * $mik);
                } elseif ($o->birim_kodu === $k->birim_kodu && $of > 0) {
                    $z = ($f - $of) / $of * 100;
                    // 3 katından fazla değişim zam değil, çoğunlukla farklı ambalaj (aynı stok kodu 50'li / 1000'li paket)
                    if ($f > $of * 3 || $f < $of / 3) $ekle('zam:' . $k->id, $k, 'zam', 'uyari', $ad . ': fiyat ' . number_format($f / $of, 1, ',', '.') . ' katına ' . ($f > $of ? 'çıktı' : 'indi') . ' — ambalaj/birim farklı olabilir',
                        $oTarih . ' tarihli faturada ' . $tl($of) . ' (' . $o->ad . '), şimdi ' . $tl($f) . '. Aynı ürünse fatura hatalı; farklı ambalajsa malzeme eşleşmesini ayırın.');
                    elseif ($z >= 5) $ekle('zam:' . $k->id, $k, 'zam', $z >= 15 ? 'uyari' : 'bilgi', $ad . ': ' . $yz($z) . ' zam', $oTarih . ' tarihli ' . ($o->fatura_no ?: 'önceki') . ' faturada ' . $tl($of) . ', şimdi ' . $tl($f) . '.', 0, ($f - $of) * $mik);
                }
                $oBrut = (float) $o->liste_fiyat * (float) $o->miktar; $yBrut = (float) $k->liste_fiyat * $mik;
                $oIsk = $oBrut > 0 ? (float) $o->iskonto / $oBrut * 100 : 0; $yIsk = $yBrut > 0 ? (float) $k->iskonto / $yBrut * 100 : 0;
                if ($oIsk >= 0.5 && $yIsk < $oIsk - 0.25) $ekle('isk:' . $k->id, $k, 'iskonto', 'uyari', $ad . ': iskonto düşmüş', 'Önceki faturada %' . number_format($oIsk, 1, ',', '.') . ' iskonto vardı (' . $oTarih . '), bu faturada %' . number_format($yIsk, 1, ',', '.') . '. Kaybedilen iskonto istenebilir.',
                    ($oIsk - $yIsk) / 100 * $yBrut, ($oIsk - $yIsk) / 100 * $yBrut);
                if (abs((float) $o->kdv_yuzde - (float) $k->kdv_yuzde) >= 0.5) $ekle('kdv:' . $k->id, $k, 'kdv', 'bilgi', $ad . ': KDV oranı değişmiş', 'Önceki faturada %' . (float) $o->kdv_yuzde . ', bu faturada %' . (float) $k->kdv_yuzde . '. Oran yanlışsa fatura düzeltilmeli; doğruysa muhasebeye bilgi verin.');
            }
            // mal kabulden fazla faturalama (alış faturasına aktarılıp mal kabule bağlanmış faturada)
            if ($mk) foreach ($fMiktar as $mid => $liste) {
                $fm = array_sum(array_map(fn ($k) => (float) $k->miktar, $liste)); $km = (float) ($mkMiktar[$mid] ?? 0);
                if ($fm - $km > 0.001) {
                    $k = $liste[0]; $fazla = ($fm - $km) * (float) $k->birim_fiyat;
                    $ekle('mk:' . $mid, $k, 'mal_kabul', 'kritik', $k->ad . ': teslim alınandan fazla faturalanmış', 'Mal kabulde ' . _fdSayi($km) . ', faturada ' . _fdSayi($fm) . ' birim.', $fazla, $fazla);
                }
            }
        }
        // Kaydet: kullanıcının dokunduğu (itiraz/çözüldü/yok say) bulgular korunur; artık geçerli olmayan AÇIK bulgular silinir
        $mevcut = DB::table('efatura_denetim')->where('gelen_id', $g->id)->get()->keyBy('anahtar');
        foreach ($b as $anahtar => $v) {
            if (isset($mevcut[$anahtar])) DB::table('efatura_denetim')->where('id', $mevcut[$anahtar]->id)->update($v + ['tedarikci_id' => $g->tedarikci_id, 'updated_at' => now()]);
            else DB::table('efatura_denetim')->insert($v + ['sube_id' => $sid, 'gelen_id' => $g->id, 'tedarikci_id' => $g->tedarikci_id, 'anahtar' => $anahtar, 'durum' => 'acik', 'created_at' => now(), 'updated_at' => now()]);
        }
        $sil = $mevcut->filter(fn ($m, $a) => !isset($b[$a]) && $m->durum === 'acik')->pluck('id');
        if ($sil->count()) DB::table('efatura_denetim')->whereIn('id', $sil)->delete();
        return $b;
    }
}

// Toplu yükleme sonrası: belge sırası yeniden eskiye olabilir (PDF/ZIP) → önceki alımla kıyas tarih sırasıyla yeniden
if (!function_exists('_fdTopluDenetle')) {
    function _fdTopluDenetle(array $ids)
    {
        if (count($ids) < 2) return;
        $t0 = microtime(true);
        foreach (DB::table('efatura_gelen')->whereIn('id', $ids)->orderBy('tarih')->orderBy('id')->pluck('id') as $id) {
            try { _fdDenetle($id); } catch (\Throwable $e) {}
            if (microtime(true) - $t0 > 60) break; // kalanı "Yeniden denetle" ya da sonraki aktarım tamamlar
        }
    }
}

if (!function_exists('_fdTurler')) {
    function _fdTurler()
    {
        return ['sozlesme' => ['Sözleşme fiyatı', 'gavel'], 'siparis' => ['Sipariş fiyatı', 'receipt_long'], 'mal_kabul' => ['Fazla faturalama', 'inventory'],
            'hesap' => ['Hesap hatası', 'calculate'], 'mukerrer' => ['Mükerrer fatura', 'content_copy'], 'toplam' => ['Toplam tutmuyor', 'error'],
            'iskonto' => ['İskonto kaybı', 'percent'], 'gizli_zam' => ['Gizli zam (küçülme)', 'compress'], 'zam' => ['Zam', 'trending_up'],
            'kdv' => ['KDV oranı', 'account_balance'], 'vade' => ['Vade', 'event']];
    }
}

// Bulgu listesi (filtreli) — sayfa + Excel ortak
if (!function_exists('_fdListe')) {
    function _fdListe($sid, array $f)
    {
        $q = DB::table('efatura_denetim as d')->join('efatura_gelen as g', 'g.id', '=', 'd.gelen_id')->where('d.sube_id', $sid)
            ->select('d.*', 'g.fatura_no', 'g.tarih', 'g.satici_ad', 'g.satici_vkn', 'g.ettn');
        if (!empty($f['durum']) && $f['durum'] !== 'tumu') $q->where('d.durum', $f['durum']);
        if (!empty($f['tur'])) $q->where('d.tur', $f['tur']);
        if (!empty($f['ted'])) $q->where('g.satici_vkn', $f['ted']);
        if (!empty($f['bas'])) $q->where('g.tarih', '>=', $f['bas']);
        if (!empty($f['bit'])) $q->where('g.tarih', '<=', $f['bit']);
        if (empty($f['bilgi']) && empty($f['tur'])) $q->where('d.seviye', '!=', 'bilgi');
        return $q->orderByRaw("CASE d.seviye WHEN 'kritik' THEN 0 WHEN 'uyari' THEN 1 ELSE 2 END")->orderByDesc('d.fark_tutar')->orderByDesc('d.etki_tutar')->orderByDesc('g.tarih')->limit(2000)->get();
    }
}

// Tedarikçiye itiraz yazısı (şablon — yapay zekâ gerekmez)
if (!function_exists('_fdItirazMetni')) {
    function _fdItirazMetni($sid, $satir)
    {
        $tl = fn ($v) => number_format((float) $v, 2, ',', '.') . ' TL';
        $isletme = null; try { $isletme = DB::table('subeler')->where('id', $sid)->value('ad'); } catch (\Throwable $e) {}
        $ilk = $satir->first();
        $m = 'Sayın ' . ($ilk->satici_ad ?: 'Yetkili') . ",\n\nFirmanızdan gelen faturaların kontrolünde aşağıdaki farklar tespit edilmiştir:\n\n";
        foreach ($satir->groupBy('fatura_no') as $no => $l) {
            $t = $l->first()->tarih ? \Carbon\Carbon::parse($l->first()->tarih)->format('d.m.Y') : '';
            $m .= '• ' . ($no ?: 'Fatura') . ($t ? ' (' . $t . ')' : '') . "\n";
            foreach ($l as $d) $m .= '   – ' . $d->baslik . ': ' . str_replace('₺', 'TL', $d->aciklama) . ($d->fark_tutar > 0 ? ' Fark: ' . $tl($d->fark_tutar) . '.' : '') . "\n";
        }
        $m .= "\nToplam fark: " . $tl($satir->sum('fark_tutar')) . "\n\n"
            . "Bu tutar için fiyat farkı / iade faturası düzenlemenizi ya da bir sonraki faturanızdan düşmenizi rica ederiz.\n\nSaygılarımızla,\n" . trim(($isletme ?: '') . ' Satın Alma');
        return $m;
    }
}

Route::get('/fatura-denetim', function (Request $r) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure(); _fdEnsure();
    $sid = _webSubeId();
    $durum = in_array($r->query('durum'), ['acik', 'itiraz', 'cozuldu', 'yoksay', 'tumu'], true) ? $r->query('durum') : 'acik';
    $f = ['durum' => $durum, 'tur' => array_key_exists((string) $r->query('tur'), _fdTurler()) ? $r->query('tur') : null, 'ted' => preg_replace('/\D/', '', (string) $r->query('ted')) ?: null,
        'bas' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bas')) ? $r->query('bas') : null, 'bit' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bit')) ? $r->query('bit') : null,
        'bilgi' => $r->boolean('bilgi')];
    $liste = _fdListe($sid, $f);
    $hepsi = DB::table('efatura_denetim')->where('sube_id', $sid)->where('seviye', '!=', 'bilgi');
    $ozet = [
        'acik' => (clone $hepsi)->where('durum', 'acik')->count(), 'acik_tl' => (float) (clone $hepsi)->where('durum', 'acik')->sum('fark_tutar'),
        'itiraz' => (clone $hepsi)->where('durum', 'itiraz')->count(), 'itiraz_tl' => (float) (clone $hepsi)->where('durum', 'itiraz')->sum('fark_tutar'),
        'cozuldu_tl' => (float) (clone $hepsi)->where('durum', 'cozuldu')->sum('fark_tutar'),
        'etki_tl' => (float) DB::table('efatura_denetim')->where('sube_id', $sid)->whereIn('tur', ['zam', 'gizli_zam'])->where('durum', '!=', 'yoksay')->sum('etki_tutar'),
        'fatura' => DB::table('efatura_gelen')->where('sube_id', $sid)->count(),
    ];
    $tedler = DB::table('efatura_gelen')->where('sube_id', $sid)->whereNotNull('satici_vkn')->select('satici_vkn', DB::raw('MAX(satici_ad) ad'))->groupBy('satici_vkn')->orderBy('ad')->get();
    $tq = DB::table('efatura_denetim')->where('sube_id', $sid); if ($durum !== 'tumu') $tq->where('durum', $durum);
    $turSay = $tq->selectRaw('tur, COUNT(*) c')->groupBy('tur')->pluck('c', 'tur');
    return view('fatura_denetim', ['liste' => $liste, 'f' => $f, 'ozet' => $ozet, 'tedler' => $tedler, 'turler' => _fdTurler(), 'turSay' => $turSay]);
});

// Durum değiştir (tek ya da çoklu)
Route::post('/fatura-denetim/durum', function (Request $r) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _fdEnsure();
    $durum = $r->input('durum');
    if (!in_array($durum, ['acik', 'itiraz', 'cozuldu', 'yoksay'], true)) return back()->with('hata', 'Geçersiz durum');
    $ids = array_values(array_filter(array_map('intval', (array) $r->input('ids', []))));
    $guncel = ['durum' => $durum, 'updated_at' => now()];
    if (trim((string) $r->input('not')) !== '') $guncel['not'] = mb_substr(trim((string) $r->input('not')), 0, 255);
    $n = $ids ? DB::table('efatura_denetim')->where('sube_id', _webSubeId())->whereIn('id', $ids)->update($guncel) : 0;
    if ($r->expectsJson()) return response()->json(['ok' => 1, 'n' => $n]);
    return back()->with('ok', $n . ' bulgu güncellendi');
});

// Tedarikçiye itiraz yazısı (açık, tutarlı bulgular)
Route::get('/fatura-denetim/itiraz', function (Request $r) {
    if (!_efgErisim()) return response()->json(['ok' => 0], 403);
    _fdEnsure();
    $sid = _webSubeId(); $vkn = preg_replace('/\D/', '', (string) $r->query('ted'));
    $l = _fdListe($sid, ['durum' => 'acik', 'ted' => $vkn])->filter(fn ($d) => $d->fark_tutar > 0 || in_array($d->tur, ['toplam', 'vade'], true))->values();
    if ($l->isEmpty()) return response()->json(['ok' => 0, 'hata' => 'Bu tedarikçi için geri istenecek açık bulgu yok.']);
    $ted = $vkn ? DB::table('tedarikciler')->where('vkn_tckn', $vkn)->first() : null;
    $tel = preg_replace('/\D/', '', (string) ($ted->telefon ?? ''));
    if ($tel && strlen($tel) === 10) $tel = '90' . $tel; elseif ($tel && str_starts_with($tel, '0')) $tel = '9' . $tel;
    return response()->json(['ok' => 1, 'metin' => _fdItirazMetni($sid, $l), 'ids' => $l->pluck('id'), 'tutar' => round($l->sum('fark_tutar'), 2), 'telefon' => $tel ?: null, 'email' => $ted->email ?? null]);
});

// Tüm faturaları yeniden denetle (sözleşme/sipariş girildikten sonra)
Route::post('/fatura-denetim/yenile', function () {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure(); _fdEnsure(); @set_time_limit(170);
    $t0 = microtime(true); $n = 0; $ids = DB::table('efatura_gelen')->where('sube_id', _webSubeId())->where('tarih', '>=', now()->subDays(400)->toDateString())->orderBy('tarih')->orderBy('id')->pluck('id');
    foreach ($ids as $id) { _fdDenetle($id); $n++; if (microtime(true) - $t0 > 150) break; }
    return back()->with('ok', $n . ' / ' . count($ids) . ' fatura yeniden denetlendi');
});

// ------------------------------------------------------------------ Excel ----
Route::get('/efatura-gelen/excel', function (Request $r) {
    if (!_efgErisim()) return redirect('/pos-giris');
    _efgEnsure(); _fdEnsure();
    if (!function_exists('_fr2Xlsx')) return response('Excel motoru yok', 500);
    @set_time_limit(170);
    $sid = _webSubeId();
    $bas = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bas')) ? $r->query('bas') : null;
    $bit = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $r->query('bit')) ? $r->query('bit') : null;
    $ted = preg_replace('/\D/', '', (string) $r->query('ted')) ?: null;
    $q = DB::table('efatura_gelen_kalem as k')->join('efatura_gelen as g', 'g.id', '=', 'k.gelen_id')->where('g.sube_id', $sid)->where('g.durum', '!=', 'yok_sayildi');
    if ($bas) $q->where('g.tarih', '>=', $bas);
    if ($bit) $q->where('g.tarih', '<=', $bit);
    if ($ted) $q->where('g.satici_vkn', $ted);
    $v = $q->orderBy('g.tarih')->orderBy('g.id')->orderBy('k.sira')
        ->select('k.*', 'g.tarih', 'g.fatura_no', 'g.satici_ad', 'g.satici_vkn', 'g.durum as fdurum', 'g.tip', 'g.genel_toplam', 'g.kdv_toplam', 'g.ara_toplam', 'g.vade_tarihi', 'g.kaynak', 'g.id as gid')->get();
    $birimAd = collect(_efgBirimHarita())->map(fn ($h) => $h[0])->all();
    $malAd = DB::table('malzemeler')->whereIn('id', $v->pluck('malzeme_id')->filter()->unique()->values())->pluck('ad', 'id');
    $kaynakAd = fn ($k) => str_starts_with((string) $k, 'api') ? 'Entegratör' : (['xml' => 'XML', 'pdf' => 'PDF', 'ocr' => 'Fotoğraf (OCR)', 'ai' => 'Yapay zekâ'][$k] ?? $k);
    // Kalemler — Malzeme/Hizmet Açıklaması, Miktar, Birim Fiyat ...
    $kalem = $v->map(fn ($k) => [$k->tarih, $k->fatura_no, $k->satici_ad, $k->stok_kodu, $k->ad, (float) $k->miktar, $birimAd[$k->birim_kodu] ?? $k->birim_kodu, (float) $k->liste_fiyat ?: (float) $k->birim_fiyat,
        (float) $k->iskonto, (float) $k->birim_fiyat, (float) $k->kdv_yuzde, (float) $k->satir_tutar, $k->malzeme_id ? ($malAd[$k->malzeme_id] ?? '') : ''])->all();
    $bulguSay = DB::table('efatura_denetim')->where('sube_id', $sid)->where('seviye', '!=', 'bilgi')->where('durum', '!=', 'yoksay')->selectRaw('gelen_id, COUNT(*) c, SUM(fark_tutar) t')->groupBy('gelen_id')->get()->keyBy('gelen_id');
    $faturalar = $v->groupBy('gid')->map(function ($l) use ($bulguSay, $kaynakAd) { $k = $l->first(); $bg = $bulguSay[$k->gid] ?? null;
        return [$k->tarih, $k->fatura_no, $k->satici_ad, $k->satici_vkn, $l->count(), (float) $k->ara_toplam, (float) $k->kdv_toplam, (float) $k->genel_toplam, $k->vade_tarihi, $kaynakAd($k->kaynak),
            ['bekliyor' => 'Eşleştirme bekliyor', 'aktarildi' => 'Aktarıldı', 'iade' => 'İade'][$k->fdurum] ?? $k->fdurum, $bg ? (int) $bg->c : 0, $bg ? (float) $bg->t : 0]; })->values()->all();
    // Fiyat değişimi: ürün (malzeme > tedarikçi stok kodu > ad) + birim
    $grup = $v->filter(fn ($k) => $k->tip !== 'IADE' && (float) $k->miktar > 0)
        ->groupBy(fn ($k) => ($k->malzeme_id ? 'm' . $k->malzeme_id : ($k->stok_kodu ? 'k' . $k->satici_vkn . '|' . $k->stok_kodu : 'a' . _efgNorm($k->ad))) . '|' . $k->birim_kodu);
    $degisim = []; $iz = [];
    foreach ($grup as $l) {
        $l = $l->sortBy(fn ($k) => $k->tarih . sprintf('%09d', $k->gid))->values();
        $ilk = $l->first(); $son = $l->last(); $fiyat = $l->map(fn ($k) => (float) $k->birim_fiyat);
        $zamSay = 0; $once = null;
        foreach ($fiyat as $x) { if ($once !== null && $x > $once * 1.001) $zamSay++; $once = $x; }
        $mik = $l->sum(fn ($k) => (float) $k->miktar); $tut = $l->sum(fn ($k) => (float) $k->satir_tutar);
        $f0 = (float) $ilk->birim_fiyat; $deg = $f0 > 0 ? ((float) $son->birim_fiyat - $f0) / $f0 * 100 : 0;
        $ad = $ilk->malzeme_id ? ($malAd[$ilk->malzeme_id] ?? $ilk->ad) : $ilk->ad; $bAd = $birimAd[$ilk->birim_kodu] ?? $ilk->birim_kodu;
        $ek = $l->slice(1)->sum(fn ($k) => ((float) $k->birim_fiyat - $f0) * (float) $k->miktar);
        $degisim[] = [$ad, $bAd, $l->pluck('satici_ad')->unique()->implode(', '), $l->count(), $mik, $tut, $f0, $ilk->tarih, (float) $son->birim_fiyat, $son->tarih,
            $fiyat->min(), $fiyat->max(), $mik > 0 ? $tut / $mik : 0, round($deg, 2), $zamSay, round($ek, 2)];
        if ($fiyat->unique()->count() > 1) {
            $iz[] = ['_baslik' => $ad . ' (' . $bAd . ')']; $once = null;
            foreach ($l as $k) { $x = (float) $k->birim_fiyat;
                $iz[] = [$k->tarih, $k->fatura_no, $k->satici_ad, (float) $k->miktar, $x, $once, $once ? round(($x - $once) / $once * 100, 2) : null, $once ? round(($x - $once) * (float) $k->miktar, 2) : null]; $once = $x; }
        }
    }
    usort($degisim, fn ($a, $b) => $b[13] <=> $a[13]);
    $turAd = _fdTurler();
    $bulgu = _fdListe($sid, ['durum' => 'tumu', 'bas' => $bas, 'bit' => $bit, 'ted' => $ted, 'bilgi' => 1])->filter(fn ($d) => $d->durum !== 'yoksay')
        ->map(fn ($d) => [$d->tarih, $d->fatura_no, $d->satici_ad, $turAd[$d->tur][0] ?? $d->tur, ['kritik' => 'YÜKSEK', 'uyari' => 'ORTA', 'bilgi' => 'DÜŞÜK'][$d->seviye] ?? $d->seviye,
            $d->baslik, str_replace('₺', 'TL', (string) $d->aciklama), (float) $d->fark_tutar, (float) $d->etki_tutar, ['acik' => 'Açık', 'itiraz' => 'İtiraz edildi', 'cozuldu' => 'Çözüldü'][$d->durum] ?? $d->durum])->values()->all();
    $tedTop = collect($faturalar)->groupBy(2)->map(fn ($l) => array_sum(array_column($l->all(), 7)))->sortDesc();
    $zamTop = collect($degisim)->filter(fn ($d) => $d[13] > 0.1)->sortByDesc(13)->take(10);
    $sayfalar = [
        ['ad' => 'Pano', 'tip' => 'pano', 'bos' => $v->isEmpty() ? 'Seçilen aralıkta fatura yok. Gelen e-Faturalar ekranından XML/ZIP/PDF yüklenince ya da entegratörden çekilince dolar.' : null,
            'kpi' => [['Fatura', count($faturalar), 's', '0E7490'], ['Kalem', count($kalem), 's', '0EA5E9'], ['Toplam (KDV dahil)', array_sum(array_column($faturalar, 7)), 'tl', '059669'], ['Tedarikçi', $tedTop->count(), 's', 'F59E0B'],
                ['Farklı ürün', count($degisim), 's', '4F46E5'], ['Zamlanan ürün', $zamTop->count() ? count(array_filter($degisim, fn ($d) => $d[13] > 0.1)) : 0, 's', 'DC2626'],
                ['Denetim bulgusu', count(array_filter($bulgu, fn ($b) => $b[4] !== 'DÜŞÜK')), 's', 'EA580C'], ['Geri istenecek', array_sum(array_column($bulgu, 7)), 'tl', 'BE123C']],
            'bloklar' => array_values(array_filter([
                ['baslik' => 'Tedarikçiye göre tutar', 'grafik' => 'bar', 'renk' => '0E7490', 'kol' => [['Tedarikçi', 'm'], ['Tutar', 'tl'], ['', 'm']], 'sat' => $tedTop->take(10)->map(fn ($t, $a) => [$a, $t, null])->values()->all()],
                ['baslik' => 'En çok zamlanan ürünler (%)', 'grafik' => 'bar', 'renk' => 'DC2626', 'kol' => [['Ürün', 'm'], ['Değişim %', 'y'], ['Son fiyat', 'tl']], 'sat' => $zamTop->map(fn ($d) => [$d[0], $d[13], $d[8]])->values()->all()],
            ], fn ($bl) => count($bl['sat']) > 0))],
        ['ad' => 'Kalemler', 'not' => 'Faturalardaki tüm satırlar. Net birim fiyat = iskonto sonrası, KDV hariç.', 'sat' => $kalem, 'bos' => 'Seçilen aralıkta fatura satırı yok.',
            'kol' => [['Tarih', 't', 11], ['Fatura No', 'm', 18], ['Tedarikçi', 'm', 26], ['Stok Kodu', 'm', 12], ['Malzeme / Hizmet Açıklaması', 'm', 42], ['Miktar', 'n', 9, 'top'], ['Birim', 'm', 8],
                ['Birim Fiyat', 'tl', 12], ['İskonto', 'tl', 10, 'top'], ['Net Birim Fiyat', 'tl', 12], ['KDV %', 'n', 7], ['Tutar', 'tl', 13, 'top bar'], ['Eşleşen Malzeme', 'm', 24]]],
        ['ad' => 'Faturalar', 'not' => 'Bulgu = Fatura Denetimi\'nin bu faturada bulduğu sorun sayısı; geri istenecek = belgeye dayalı alacak.', 'sat' => $faturalar, 'bos' => 'Seçilen aralıkta fatura yok.',
            'kol' => [['Tarih', 't', 11], ['Fatura No', 'm', 18], ['Tedarikçi', 'm', 28], ['VKN', 'm', 12], ['Kalem', 'n', 7, 'top'], ['Ara Toplam', 'tl', 13, 'top'], ['KDV', 'tl', 12, 'top'],
                ['Genel Toplam', 'tl', 14, 'top bar'], ['Vade', 't', 11], ['Kaynak', 'm', 12], ['Durum', 'm', 16], ['Bulgu', 'n', 7, 'top'], ['Geri istenecek', 'tl', 13, 'top']]],
        ['ad' => 'Fiyat Değişimi', 'not' => 'Ürün + birim bazında ilk ve son alış fiyatı. Değişim % = (son − ilk) / ilk. Ek maliyet = ilk alıştan sonraki alımlarda ilk fiyata göre fazladan ödenen.', 'sat' => $degisim, 'bos' => 'Seçilen aralıkta alım yok.',
            'kol' => [['Ürün', 'm', 40], ['Birim', 'm', 8], ['Tedarikçi', 'm', 22], ['Alım', 'n', 6], ['Toplam Miktar', 'n', 10, 'top'], ['Toplam Tutar', 'tl', 13, 'top bar'], ['İlk Fiyat', 'tl', 11], ['İlk Tarih', 't', 11],
                ['Son Fiyat', 'tl', 11], ['Son Tarih', 't', 11], ['En Düşük', 'tl', 11], ['En Yüksek', 'tl', 11], ['Ort. Fiyat', 'tl', 11], ['Değişim %', 'y', 10, 'esik:15'], ['Zam Sayısı', 'n', 8], ['Ek Maliyet', 'tl', 12, 'top']]],
        ['ad' => 'Zam İzi', 'not' => 'Fiyatı en az bir kez değişen ürünlerin alış sırası.', 'sat' => $iz, 'bos' => 'Seçilen aralıkta fiyatı değişen ürün yok.',
            'kol' => [['Tarih', 't', 11], ['Fatura No', 'm', 18], ['Tedarikçi', 'm', 26], ['Miktar', 'n', 9], ['Birim Fiyat', 'tl', 12], ['Önceki Fiyat', 'tl', 12], ['Değişim %', 'y', 10, 'esik:15'], ['Fark × Miktar', 'tl', 13]]],
        ['ad' => 'Denetim Bulguları', 'not' => 'Geri istenecek = belgeye dayalı alacak (sözleşme/sipariş fiyatı, fazla faturalama, hesap hatası, iskonto kaybı, mükerrer). Maliyet etkisi = zam vb., alacak değildir.', 'sat' => $bulgu,
            'bos' => 'Denetimde sorun bulunmadı ya da bulgular "yok say" işaretlendi. Sözleşme fiyatları girildikçe ve siparişler sistemden verildikçe kontroller genişler.',
            'kol' => [['Tarih', 't', 11], ['Fatura No', 'm', 18], ['Tedarikçi', 'm', 24], ['Tür', 'm', 16], ['Önem', 'm', 9, 'onc'], ['Bulgu', 'm', 34], ['Açıklama', 'm', 60], ['Geri istenecek', 'tl', 13, 'top bar'], ['Maliyet etkisi', 'tl', 13, 'top'], ['Durum', 'm', 13]]],
    ];
    $subeAd = null; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad'); } catch (\Throwable $e) {}
    $aralik = ($bas || $bit) ? ($bas ? \Carbon\Carbon::parse($bas)->format('d.m.Y') : '…') . ' – ' . ($bit ? \Carbon\Carbon::parse($bit)->format('d.m.Y') : '…') : 'Tüm faturalar';
    $x = _fr2Xlsx($sayfalar, 'Fatura Analizi', trim(($subeAd ? $subeAd . ' · ' : '') . $aralik . ' · Oluşturma: ' . now()->format('d.m.Y H:i')), '0E7490');
    if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
    return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="fatura-analizi-' . date('Ymd') . '.xlsx"']);
});

<?php
// =====================================================================
// FİNANS RAPORLAR & ANALİZLER — /finans-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: adisyonlar (ciro, indirim, ikram, kanal, platform, misafir), odemeler (tip, bahşiş), adisyon_kalemleri × reçete maliyeti (SMM),
//       giderler (kategori), alis_faturalari (+ _tedarikciYaslandirma), personeller + personel_mesai + personel_hareketleri (işçilik, ödemeler),
//       kasa_oturumlari / kasa_hareketleri (sayım farkı), cari_hesaplar / cari_hareketler, banka_hesaplar / banka_hareketler, cek_senetler,
//       tekrarlayan_giderler, demirbaslar (amortisman), iptal_indirim_loglari, stok_hareketleri (fire), sayim_kalemleri, muhasebe_ayarlari (KDV oranı).
// Çift sayım kuralı (gelir tablosu): giderler'deki "malzeme" (alış faturasından otomatik düşer) SMM ile, "maas" (avans / maaş ödemesi) işçilik ile
//       zaten karşılandığı için işletme giderine eklenmez; nakit akışında ise giderler defteri olduğu gibi (hepsi) çıkış sayılır.
// Faz 2 (şema değişmez): hedef & bütçe ayarları şube ayarında tek JSON ('fnr_ayar' → _subeAyarAl/_subeAyarYaz): gıda / işçilik / ana maliyet
//       hedefi, aylık ciro hedefi, kategori bazında aylık gider bütçesi, online platform komisyon oranları, maaş ödeme günü. Kayıt: POST /finans-raporlar/ayar.
// Erişim: patron / müdür / süpervizör, Finans departmanı sorumlusu, 'gor_finans_rapor' + (finans ekranlarını zaten gören) 'gor_muhasebe' / 'gor_finans'.
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ---------- Ayarlar (Faz 2: hedef & bütçe) ----------
if (!function_exists('_fnrAyar')) {
    function _fnrGiderKatlar() { return ['kira' => 'Kira', 'fatura' => 'Faturalar (elektrik, su, doğalgaz…)', 'malzeme' => 'Malzeme / alış', 'maas' => 'Maaş & avans ödemeleri', 'vergi' => 'Vergi & harç', 'diger' => 'Diğer']; }
    function _fnrGiderAd($k) { return _fnrGiderKatlar()[$k] ?? ($k ? mb_convert_case((string) $k, MB_CASE_TITLE, 'UTF-8') : 'Diğer'); }
    function _fnrPlatformlar() { return ['yemeksepeti' => 'Yemeksepeti', 'getir' => 'Getir', 'trendyol' => 'Trendyol Yemek', 'migros' => 'Migros Yemek', 'diger' => 'Diğer platformlar']; }
    function _fnrAyarVarsayilan()
    {
        return ['hedef_fc' => 30.0, 'hedef_isc' => 30.0, 'hedef_prime' => 62.0, 'ciro_hedef' => 0.0, 'maas_gunu' => 1,
            'butce' => array_fill_keys(array_keys(_fnrGiderKatlar()), 0.0), 'kom' => ['yemeksepeti' => 18.0, 'getir' => 18.0, 'trendyol' => 18.0, 'migros' => 18.0, 'diger' => 15.0]];
    }
    function _fnrAyar($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        $v = _fnrAyarVarsayilan(); $j = [];
        try { if (function_exists('_subeAyarAl')) $j = json_decode((string) _subeAyarAl($sid, 'fnr_ayar', ''), true) ?: []; } catch (\Throwable $e) {}
        foreach (['hedef_fc', 'hedef_isc', 'hedef_prime', 'ciro_hedef'] as $k) if (isset($j[$k]) && is_numeric($j[$k])) $v[$k] = max(0, (float) $j[$k]);
        if (isset($j['maas_gunu'])) $v['maas_gunu'] = max(1, min(28, (int) $j['maas_gunu']));
        foreach (['butce', 'kom'] as $g) foreach ($v[$g] as $k => $_) if (isset($j[$g][$k]) && is_numeric($j[$g][$k])) $v[$g][$k] = max(0, (float) $j[$g][$k]);
        $v['butce_var'] = array_sum($v['butce']) > 0 || $v['ciro_hedef'] > 0;
        return $c[$sid] = $v;
    }
    function _fnrKdvOran($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        $o = 10; try { if (_srTablo('muhasebe_ayarlari')) { $x = (int) DB::table('muhasebe_ayarlari')->where('sube_id', $sid)->value('kdv_orani'); if ($x > 0) $o = $x; } } catch (\Throwable $e) {}
        return $c[$sid] = $o;
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_fnrSatis')) {
    function _fnrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _fnrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    function _fnrAyAd($ym) { $a = ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara']; return ($a[(int) substr($ym, 5, 2)] ?? '') . ' ' . substr($ym, 0, 4); }
    function _fnrPersonelAd($id) { static $m = null; if ($m === null) { try { $m = DB::table('personeller')->pluck('ad', 'id')->all(); } catch (\Throwable $e) { $m = []; } } return $id ? ($m[$id] ?? ('#' . $id)) : '—'; }
    function _fnrKanalAd($k) { return ['salon' => 'Salon (masa)', 'masa' => 'Salon (masa)', 'paket' => 'Paket', 'qr' => 'QR sipariş', 'gelal' => 'Gel-al', 'online' => 'Online'][$k] ?? ($k ? mb_convert_case((string) $k, MB_CASE_TITLE, 'UTF-8') : 'Salon (masa)'); }
    function _fnrOdemeAd($t) { return ['nakit' => 'Nakit', 'kredi' => 'Kredi kartı', 'kart' => 'Kart', 'kart_kapida' => 'Kapıda kart', 'yemek_karti' => 'Yemek kartı', 'online' => 'Online (platform)', 'acik_hesap' => 'Açık hesap (veresiye)', 'havale' => 'Havale / EFT', 'puan' => 'Puan / ödül'][$t] ?? ($t ? mb_convert_case((string) $t, MB_CASE_TITLE, 'UTF-8') : 'Belirsiz'); }
    function _fnrPlatformKod($p)
    {
        $k = preg_replace('/[^a-z]/', '', strtr(mb_strtolower((string) $p, 'UTF-8'), ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c']));
        foreach (['yemeksepeti', 'getir', 'trendyol', 'migros'] as $x) if (str_starts_with($k, $x)) return $x;
        return 'diger';
    }
    // Ödenmiş adisyonlar (kapanış tarihine göre)
    function _fnrSatis($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('adisyonlar')) return $c[$key] = [];
        $sel = ['id', 'toplam', 'kapanis']; foreach (['indirim', 'ikram', 'kanal', 'platform', 'misafir_sayisi', 'acan_personel_id', 'acilis'] as $k) $sel[] = _srKolon('adisyonlar', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereBetween('kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $t = strtotime((string) $r->kapanis); $ac = $r->acilis ? strtotime((string) $r->acilis) : null;
            $o[] = ['id' => (int) $r->id, 'toplam' => (float) $r->toplam, 'indirim' => (float) $r->indirim, 'ikram' => (float) $r->ikram, 'kanal' => (string) ($r->kanal ?: 'salon'), 'platform' => trim((string) $r->platform),
                'misafir' => (int) $r->misafir_sayisi, 'acan' => (int) $r->acan_personel_id, 'tarih' => date('Y-m-d', $t), 'ay' => date('Y-m', $t), 'saat' => (int) date('G', $t), 'gun' => (int) date('N', $t),
                'sure' => $ac && $t > $ac && $t - $ac < 86400 ? round(($t - $ac) / 60) : null];
        }
        return $c[$key] = $o;
    }
    function _fnrCiro($sid, $bas, $bit) { return round(array_sum(array_column(_fnrSatis($sid, $bas, $bit), 'toplam')), 2); }
    // Ödemeler (ödeme anına göre; Z raporuyla aynı)
    function _fnrOdeme($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('odemeler') || !_srTablo('adisyonlar')) return $c[$key] = [];
        $sel = ['o.tip', 'o.tutar', 'o.created_at', _srKolon('odemeler', 'bahsis') ? 'o.bahsis' : DB::raw('0 as bahsis')];
        try { $rows = DB::table('odemeler as o')->join('adisyonlar as a', 'o.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->whereBetween('o.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['tip' => (string) ($r->tip ?: 'belirsiz'), 'tutar' => (float) $r->tutar, 'bahsis' => (float) $r->bahsis, 'tarih' => substr((string) $r->created_at, 0, 10)];
        return $c[$key] = $o;
    }
    function _fnrGider($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('giderler')) return $c[$key] = [];
        try { $rows = DB::table('giderler')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->orderBy('tarih')->get(['id', 'kategori', 'aciklama', 'tutar', 'tarih']); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['id' => (int) $r->id, 'kat' => (string) ($r->kategori ?: 'diger'), 'aciklama' => (string) ($r->aciklama ?? ''), 'tutar' => (float) $r->tutar, 'tarih' => substr((string) $r->tarih, 0, 10)];
        return $c[$key] = $o;
    }
    function _fnrGiderKat(array $g) { $o = []; foreach ($g as $x) $o[$x['kat']] = ($o[$x['kat']] ?? 0) + $x['tutar']; arsort($o); return array_map(fn ($v) => round($v, 2), $o); }
    function _fnrTedAdlar() { static $m = null; if ($m === null) { try { $m = _srTablo('tedarikciler') ? DB::table('tedarikciler')->pluck('ad', 'id')->all() : []; } catch (\Throwable $e) { $m = []; } } return $m; }
    // Alış faturaları (iptal hariç)
    function _fnrAlis($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('alis_faturalari')) return $c[$key] = [];
        $sel = ['id', 'tedarikci_id', 'fatura_no', 'tarih', 'toplam']; foreach (['kdv_toplam', 'durum', 'vade_tarihi'] as $k) $sel[] = _srKolon('alis_faturalari', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $q = DB::table('alis_faturalari')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit]); if (_srKolon('alis_faturalari', 'durum')) $q->where(fn ($w) => $w->whereNull('durum')->orWhere('durum', '!=', 'iptal')); $rows = $q->get($sel); } catch (\Throwable $e) { $rows = []; }
        $ted = _fnrTedAdlar(); $o = [];
        foreach ($rows as $r) $o[] = ['ted' => $ted[(int) $r->tedarikci_id] ?? 'Tedarikçisiz', 'no' => (string) $r->fatura_no, 'tarih' => substr((string) $r->tarih, 0, 10), 'ay' => substr((string) $r->tarih, 0, 7), 'toplam' => (float) $r->toplam, 'kdv' => (float) $r->kdv_toplam, 'durum' => (string) $r->durum];
        return $c[$key] = $o;
    }
    // Satılan malın maliyeti: satılan kalem × reçete maliyeti; reçetesi / maliyeti olmayan kalemde satışın %30'u (Kâr-Zarar ekranıyla aynı kural, "tahmini")
    function _fnrSmm($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $o = ['toplam' => 0.0, 'tahmini' => 0.0, 'satis' => 0.0, 'gun' => [], 'urun' => []];
        if (!_srTablo('adisyon_kalemleri') || !_srTablo('adisyonlar')) return $c[$key] = $o;
        $map = function_exists('_mrMaliyetMap') ? _mrMaliyetMap() : (function_exists('_restoUrunMaliyetMap') ? _restoUrunMaliyetMap() : ['id' => [], 'ad' => []]);
        try {
            $rows = DB::table('adisyon_kalemleri as ak')->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->where('a.durum', 'odendi')
                ->whereBetween('a.kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->where(fn ($w) => $w->whereNull('ak.durum')->orWhere('ak.durum', '!=', 'iptal'))
                ->groupBy(DB::raw('DATE(a.kapanis)'), 'ak.urun_id', 'ak.urun_adi')->selectRaw('DATE(a.kapanis) t, ak.urun_id, ak.urun_adi, SUM(ak.adet) adet, SUM(ak.tutar) satis')->get();
        } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) {
            $b = (float) ($map['id'][(int) $r->urun_id] ?? ($map['ad'][$r->urun_adi] ?? 0)); $m = (float) $r->adet * $b; $tah = false;
            if ($m <= 0 && (float) $r->satis > 0) { $m = (float) $r->satis * 0.30; $tah = true; }
            $t = substr((string) $r->t, 0, 10); $o['gun'][$t] = ($o['gun'][$t] ?? 0) + $m; $o['toplam'] += $m; $o['satis'] += (float) $r->satis; if ($tah) $o['tahmini'] += $m;
            $u = &$o['urun'][(string) $r->urun_adi]; $u['adet'] = ($u['adet'] ?? 0) + (float) $r->adet; $u['satis'] = ($u['satis'] ?? 0) + (float) $r->satis; $u['maliyet'] = ($u['maliyet'] ?? 0) + $m; $u['tahmini'] = ($u['tahmini'] ?? false) || $tah; unset($u);
        }
        $o['toplam'] = round($o['toplam'], 2); $o['tahmini'] = round($o['tahmini'], 2);
        return $c[$key] = $o;
    }
    // İşçilik maliyeti (İK raporuyla aynı esas, kapsam kısıtı olmadan): aylık maaş /30 × kadro günü; günlük × mesai günü; saatlik × QR mesai saati
    // (mesai kaydı hiç yoksa ayda 26 gün / günde 8 saat varsayılır, "tahmini"). Üstüne prim + ek ödeme + ciro primi; hepsi × İK işveren çarpanı.
    function _fnrIscilik($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $gunler = _fnrGunler($bas, $bit); $o = ['toplam' => 0.0, 'gun' => array_fill_keys($gunler, 0.0), 'kisi' => [], 'tahmini' => false, 'carpan' => 1.0];
        if (!_srTablo('personeller')) return $c[$key] = $o;
        try { $o['carpan'] = function_exists('_ikrAyar') ? (float) _ikrAyar($sid)['carpan'] : 1.0; } catch (\Throwable $e) {}
        try { $per = DB::table('personeller')->where('sube_id', $sid)->where('rol', '!=', 'sahip')->get(); } catch (\Throwable $e) { $per = []; }
        $ms = [];
        if (_srTablo('personel_mesai')) {
            try { foreach (DB::table('personel_mesai')->where('sube_id', $sid)->whereBetween('giris_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['personel_id', 'giris_at', 'cikis_at']) as $m) {
                if (!$m->cikis_at) continue; $h = (strtotime((string) $m->cikis_at) - strtotime((string) $m->giris_at)) / 3600; if ($h <= 0 || $h > 16) continue;
                $t = substr((string) $m->giris_at, 0, 10); $ms[(int) $m->personel_id][$t] = ($ms[(int) $m->personel_id][$t] ?? 0) + $h; } } catch (\Throwable $e) {}
        }
        $ek = [];
        if (_srTablo('personel_hareketleri')) { try { foreach (DB::table('personel_hareketleri')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->whereIn('tur', ['prim', 'ek_odeme'])->get(['personel_id', 'tutar', 'tarih']) as $h) { $t = substr((string) $h->tarih, 0, 10); $ek[(int) $h->personel_id][$t] = ($ek[(int) $h->personel_id][$t] ?? 0) + (float) $h->tutar; } } catch (\Throwable $e) {} }
        $cp = []; foreach (_fnrSatis($sid, $bas, $bit) as $a) if ($a['acan']) $cp[$a['acan']][$a['tarih']] = ($cp[$a['acan']][$a['tarih']] ?? 0) + $a['toplam'];
        $bugun = date('Y-m-d');
        foreach ($per as $p) {
            $maas = (float) ($p->maas ?? 0); $tip = $p->maas_tipi ?? 'aylik'; $gir = substr((string) (($p->ise_baslama ?? null) ?: ($p->created_at ?? '')), 0, 10) ?: null;
            $cik = !empty($p->ise_cikis) ? substr((string) $p->ise_cikis, 0, 10) : null; if (!(int) ($p->aktif ?? 1) && !$cik) $cik = substr((string) ($p->updated_at ?? ''), 0, 10) ?: '0000-00-00';
            $mesaiVar = !empty($ms[(int) $p->id]); $top = 0.0; $tah = false; $primT = 0.0;
            foreach ($gunler as $t) {
                if ($t > $bugun) break;
                $kadro = (!$gir || $gir <= $t) && (!$cik || $cik > $t); $tm = 0.0;
                if ($tip === 'saatlik') { $h = $mesaiVar ? ($ms[$p->id][$t] ?? 0) : ($kadro ? 8 * 26 / 30 : 0); $tm = $maas * $h; if (!$mesaiVar && $h) $tah = true; }
                elseif ($tip === 'gunluk') { $tm = $mesaiVar ? (isset($ms[$p->id][$t]) ? $maas : 0) : ($kadro ? $maas * 26 / 30 : 0); if (!$mesaiVar && $tm) $tah = true; }
                elseif ($kadro) $tm = $maas / 30;
                $pr = ($ek[$p->id][$t] ?? 0) + (($p->prim_tipi ?? 'yok') === 'ciro' ? ($cp[$p->id][$t] ?? 0) * (float) ($p->prim_oran ?? 0) / 100 : 0);
                $v = ($tm + $pr) * $o['carpan']; $o['gun'][$t] += $v; $top += $v; $primT += $pr * $o['carpan'];
            }
            if ($top <= 0) continue;
            $o['tahmini'] = $o['tahmini'] || $tah;
            $o['kisi'][] = ['pid' => (int) $p->id, 'ad' => (string) $p->ad, 'rol' => (string) $p->rol, 'tip' => ['aylik' => 'Aylık', 'gunluk' => 'Günlük', 'saatlik' => 'Saatlik'][$tip] ?? $tip, 'maas' => $maas,
                'prim' => round($primT, 2), 'toplam' => round($top, 2), 'saat' => round(array_sum($ms[$p->id] ?? []), 1), 'tahmini' => $tah];
        }
        usort($o['kisi'], fn ($a, $b) => $b['toplam'] <=> $a['toplam']);
        $o['gun'] = array_map(fn ($x) => round($x, 2), $o['gun']); $o['toplam'] = round(array_sum(array_column($o['kisi'], 'toplam')), 2);
        return $c[$key] = $o;
    }
    // Online platform komisyonu (oranlar Faz 2 ayarından; varsayılan Online Komisyon ekranıyla aynı)
    function _fnrKomisyon($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $kom = _fnrAyar($sid)['kom']; $p = []; $gun = [];
        foreach (_fnrSatis($sid, $bas, $bit) as $a) {
            if ($a['platform'] === '') continue;
            $k = _fnrPlatformKod($a['platform']); $or = $kom[$k] ?? $kom['diger']; $ad = $k === 'diger' ? mb_convert_case($a['platform'], MB_CASE_TITLE, 'UTF-8') : _fnrPlatformlar()[$k];
            $x = &$p[$ad]; $x['adet'] = ($x['adet'] ?? 0) + 1; $x['ciro'] = ($x['ciro'] ?? 0) + $a['toplam']; $x['oran'] = $or; $x['kom'] = ($x['kom'] ?? 0) + $a['toplam'] * $or / 100; unset($x);
            $gun[$a['tarih']] = ($gun[$a['tarih']] ?? 0) + $a['toplam'] * $or / 100;
        }
        uasort($p, fn ($a, $b) => $b['ciro'] <=> $a['ciro']);
        return $c[$key] = ['liste' => $p, 'ciro' => round(array_sum(array_column($p, 'ciro')), 2), 'toplam' => round(array_sum(array_column($p, 'kom')), 2), 'gun' => $gun];
    }
    // Demirbaş amortismanı (Amortisman ekranıyla aynı oran tablosu, normal yöntem)
    function _fnrAmortisman($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('demirbaslar')) return $c[$sid] = [];
        $oran = ['bilgisayar' => 25, 'yazilim' => 33, 'mobilya' => 20, 'demirbas' => 20, 'ekipman' => 20, 'mutfak' => 20, 'arac' => 20, 'tasit' => 20, 'bina' => 2, 'tesisat' => 10, 'diger' => 20];
        try { $rows = DB::table('demirbaslar')->where('sube_id', $sid)->where('alis_bedel', '>', 0)->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $d) {
            $kat = mb_strtolower((string) ($d->kategori ?? 'diger'), 'UTF-8'); $or = $oran[$kat] ?? 20; $bedel = (float) $d->alis_bedel; $yil = $d->alis_tarih ? max(0, (int) floor((time() - strtotime((string) $d->alis_tarih)) / (365 * 86400))) : 0;
            $yillik = round($bedel * $or / 100, 2); $bir = min($bedel, round($yillik * $yil, 2)); $durum = (string) ($d->durum ?? '');
            $o[] = ['ad' => (string) $d->ad, 'kat' => (string) ($d->kategori ?: 'diğer'), 'tarih' => substr((string) $d->alis_tarih, 0, 10) ?: null, 'bedel' => $bedel, 'oran' => $or, 'yillik' => $yillik, 'yil' => $yil, 'birikmis' => $bir,
                'net' => round($bedel - $bir, 2), 'aktif' => $bedel - $bir > 0 && !in_array($durum, ['hurda', 'satildi', 'kayip'], true), 'durum' => $durum];
        }
        usort($o, fn ($a, $b) => $b['bedel'] <=> $a['bedel']);
        return $c[$sid] = $o;
    }
    function _fnrAmortDonem($sid, $gun) { return round(array_sum(array_map(fn ($x) => $x['aktif'] ? $x['yillik'] * $gun / 365 : 0, _fnrAmortisman($sid))), 2); }
    // Gelir tablosu (P&L) — tüm finans raporlarının ortak hesabı
    function _fnrPl($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $s = _fnrSatis($sid, $bas, $bit); $ciro = round(array_sum(array_column($s, 'toplam')), 2); $ind = round(array_sum(array_column($s, 'indirim')), 2); $ikr = round(array_sum(array_column($s, 'ikram')), 2);
        $oran = _fnrKdvOran($sid); $net = round($ciro / (1 + $oran / 100), 2); $kdv = round($ciro - $net, 2);
        $smm = _fnrSmm($sid, $bas, $bit); $kom = _fnrKomisyon($sid, $bas, $bit); $isc = _fnrIscilik($sid, $bas, $bit); $gk = _fnrGiderKat(_fnrGider($sid, $bas, $bit));
        $iscTl = $isc['toplam'] > 0 ? $isc['toplam'] : (float) ($gk['maas'] ?? 0); $iscKay = $isc['toplam'] > 0 ? 'maas' : (($gk['maas'] ?? 0) > 0 ? 'gider' : 'yok');
        $isletme = array_diff_key($gk, ['malzeme' => 1, 'maas' => 1]); $isl = round(array_sum($isletme), 2);
        $gun = count(_fnrGunler($bas, $bit)); $amort = _fnrAmortDonem($sid, $gun);
        $brutKar = round($net - $smm['toplam'], 2); $favok = round($brutKar - $kom['toplam'] - $iscTl - $isl, 2); $netKar = round($favok - $amort, 2);
        $y = fn ($a) => $net > 0 ? round($a / $net * 100, 1) : null;
        $mis = array_sum(array_column($s, 'misafir'));
        return $c[$key] = ['ciro' => $ciro, 'brut' => round($ciro + $ind + $ikr, 2), 'indirim' => $ind, 'ikram' => $ikr, 'kdv_oran' => $oran, 'kdv' => $kdv, 'net' => $net, 'smm' => $smm['toplam'], 'smm_tahmini' => $smm['tahmini'],
            'brut_kar' => $brutKar, 'komisyon' => $kom['toplam'], 'iscilik' => round($iscTl, 2), 'isc_kaynak' => $iscKay, 'isc_tahmini' => $isc['tahmini'], 'isletme' => $isletme, 'isletme_top' => $isl,
            'gider_malzeme' => (float) ($gk['malzeme'] ?? 0), 'gider_maas' => (float) ($gk['maas'] ?? 0), 'gider_top' => round(array_sum($gk), 2), 'amort' => $amort, 'favok' => $favok, 'net_kar' => $netKar,
            'fc' => $y($smm['toplam']), 'isc_y' => $y($iscTl), 'prime' => $y($smm['toplam'] + $iscTl), 'marj' => $y($netKar), 'adisyon' => count($s), 'misafir' => $mis,
            'ort' => count($s) ? round($ciro / count($s), 2) : null, 'kisi_basi' => $mis > 0 ? round($ciro / $mis, 2) : null, 'gun' => $gun];
    }
    function _fnrKasaOturum($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('kasa_oturumlari')) return $c[$key] = [];
        $kasa = []; try { if (_srTablo('kasalar')) $kasa = DB::table('kasalar')->where('sube_id', $sid)->pluck('ad', 'id')->all(); } catch (\Throwable $e) {}
        try { $rows = DB::table('kasa_oturumlari')->where('sube_id', $sid)->where('durum', 'kapali')->whereBetween('kapanis_zamani', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('kapanis_zamani')->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $a = $r->acilis_zamani ? strtotime((string) $r->acilis_zamani) : null; $k = strtotime((string) $r->kapanis_zamani);
            $o[] = ['tarih' => date('Y-m-d', $k), 'kasa' => !empty($r->kasa_id) ? ($kasa[(int) $r->kasa_id] ?? 'Kasa #' . $r->kasa_id) : 'Ana kasa', 'acan' => _fnrPersonelAd($r->acan_personel_id), 'kapatan' => _fnrPersonelAd($r->kapatan_personel_id),
                'saat' => ($a ? date('H:i', $a) : '—') . ' – ' . date('H:i', $k), 'devir' => (float) $r->acilis_devir, 'beklenen' => (float) $r->beklenen_nakit, 'sayilan' => (float) $r->sayilan_nakit,
                'fark' => round((float) ($r->fark ?? ((float) $r->sayilan_nakit - (float) $r->beklenen_nakit)), 2), 'not' => (string) ($r->not ?? '')];
        }
        return $c[$key] = $o;
    }
    function _fnrKasaTipAd($t) { return ['devir' => 'Açılış devri', 'satis' => 'Nakit satış', 'tahsilat' => 'Tahsilat (cari / paket)', 'gider' => 'Gider ödemesi', 'personel' => 'Personel ödemesi / avans', 'al' => 'Kasadan para alındı', 'koy' => 'Kasaya para konuldu'][$t] ?? ($t ?: '—'); }
    function _fnrKasaHareket($sid, $bas, $bit)
    {
        if (!_srTablo('kasa_hareketleri')) return [];
        try { return DB::table('kasa_hareketleri')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(['tip', 'yon', 'tutar', 'aciklama', 'personel_id', 'created_at'])
            ->map(fn ($h) => ['tip' => (string) $h->tip, 'yon' => (string) $h->yon, 'tutar' => (float) $h->tutar, 'aciklama' => (string) ($h->aciklama ?? ''), 'kisi' => _fnrPersonelAd($h->personel_id), 'tarih' => substr((string) $h->created_at, 0, 10), 'saat' => substr((string) $h->created_at, 11, 5)])->all(); } catch (\Throwable $e) { return []; }
    }
    // Kasadaki nakit: açık vardiya varsa beklenen (giriş − çıkış), yoksa son kapanışta sayılan
    function _fnrKasaNakit($sid)
    {
        if (!_srTablo('kasa_oturumlari') || !_srTablo('kasa_hareketleri')) return ['tutar' => 0.0, 'not' => 'Kasa vardiyası yok'];
        try {
            $acik = DB::table('kasa_oturumlari')->where('sube_id', $sid)->where('durum', 'acik')->pluck('id')->all();
            if ($acik) { $g = (float) DB::table('kasa_hareketleri')->whereIn('oturum_id', $acik)->where('yon', 'giris')->sum('tutar'); $c = (float) DB::table('kasa_hareketleri')->whereIn('oturum_id', $acik)->where('yon', 'cikis')->sum('tutar'); return ['tutar' => round($g - $c, 2), 'not' => 'Açık vardiyada beklenen nakit']; }
            $s = DB::table('kasa_oturumlari')->where('sube_id', $sid)->where('durum', 'kapali')->orderByDesc('id')->first();
            return $s ? ['tutar' => (float) $s->sayilan_nakit, 'not' => 'Son kapanışta sayılan (' . date('d.m.Y', strtotime((string) $s->kapanis_zamani)) . ')'] : ['tutar' => 0.0, 'not' => 'Kasa vardiyası yok'];
        } catch (\Throwable $e) { return ['tutar' => 0.0, 'not' => 'Okunamadı']; }
    }
    // Müşteri cari alacakları: tahsilatlar en eski borçtan düşülür (FIFO), kalan borç yaşına göre kovalara ayrılır
    function _fnrCariTipAd($t) { return ['patron' => 'Patron', 'musteri' => 'Müşteri', 'firma' => 'Firma', 'personel' => 'Personel'][$t] ?? ($t ?: '—'); }
    function _fnrCari($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('cari_hesaplar') || !_srTablo('cari_hareketler')) return $c[$sid] = [];
        try { $q = DB::table('cari_hesaplar')->where('sube_id', $sid)->where(fn ($w) => $w->whereNull('tip')->orWhere('tip', '!=', 'tedarikci')); if (_srKolon('cari_hesaplar', 'tedarikci_id')) $q->whereNull('tedarikci_id'); $cari = $q->get(); } catch (\Throwable $e) { return $c[$sid] = []; }
        if ($cari->isEmpty()) return $c[$sid] = [];
        try { $har = DB::table('cari_hareketler')->whereIn('cari_id', $cari->pluck('id'))->orderBy('created_at')->get(['cari_id', 'tip', 'tutar', 'created_at'])->groupBy('cari_id'); } catch (\Throwable $e) { $har = collect(); }
        $bugun = strtotime(date('Y-m-d')); $o = [];
        foreach ($cari as $k) {
            $l = $har[$k->id] ?? collect(); if ($l->isEmpty()) continue;
            $borc = $l->where('tip', 'borc'); $tah = (float) $l->where('tip', '!=', 'borc')->sum('tutar'); $kalan = $tah; $kova = [0, 0, 0, 0]; $enEski = null;
            foreach ($borc as $b) {
                $t = (float) $b->tutar; $d = min($kalan, $t); $kalan -= $d; $acik = $t - $d; if ($acik <= 0.009) continue;
                $yas = max(0, (int) floor(($bugun - strtotime(substr((string) $b->created_at, 0, 10))) / 86400)); $enEski = $enEski === null ? $yas : max($enEski, $yas);
                $kova[$yas <= 30 ? 0 : ($yas <= 60 ? 1 : ($yas <= 90 ? 2 : 3))] += $acik;
            }
            $son = $l->where('tip', '!=', 'borc')->max('created_at');
            $o[] = ['ad' => (string) $k->ad, 'tip' => _fnrCariTipAd($k->tip ?? ''), 'borc' => round((float) $borc->sum('tutar'), 2), 'tahsilat' => round($tah, 2), 'bakiye' => round((float) $borc->sum('tutar') - $tah, 2),
                'k' => array_map(fn ($x) => round($x, 2), $kova), 'en_eski' => $enEski, 'son_tahsilat' => $son ? substr((string) $son, 0, 10) : null, 'son_satis' => substr((string) $borc->max('created_at'), 0, 10) ?: null];
        }
        usort($o, fn ($a, $b) => $b['bakiye'] <=> $a['bakiye']);
        return $c[$sid] = $o;
    }
    function _fnrCariDonem($sid, $bas, $bit)
    {
        $o = ['satis' => 0.0, 'tahsilat' => 0.0, 'gun' => []];
        if (!_srTablo('cari_hareketler')) return $o;
        try { foreach (DB::table('cari_hareketler')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['tip', 'tutar', 'created_at']) as $h) {
            $k = $h->tip === 'borc' ? 'satis' : 'tahsilat'; $o[$k] += (float) $h->tutar; if ($k === 'tahsilat') { $t = substr((string) $h->created_at, 0, 10); $o['gun'][$t] = ($o['gun'][$t] ?? 0) + (float) $h->tutar; } } } catch (\Throwable $e) {}
        return $o;
    }
    // Tedarikçi borcu: ödenmemiş alış faturaları (Tedarikçi Yaşlandırma ekranının hesabı)
    function _fnrTedBorc($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        $bos = ['tedarikciler' => [], 'toplam' => 0, 'gecikmis' => 0, 'hafta' => 0, 'ay' => 0, 'ileri' => 0];
        if (!_srTablo('alis_faturalari') || !_srTablo('tedarikciler') || !function_exists('_tedarikciYaslandirma')) return $c[$sid] = $bos;
        try { return $c[$sid] = _tedarikciYaslandirma($sid) + $bos; } catch (\Throwable $e) { return $c[$sid] = $bos; }
    }
    function _fnrBanka($sid)
    {
        if (!_srTablo('banka_hesaplar')) return [];
        try { return DB::table('banka_hesaplar')->where('sube_id', $sid)->orderByDesc('aktif')->orderByDesc('bakiye')->get()->all(); } catch (\Throwable $e) { return []; }
    }
    function _fnrBankaHareket($sid, $bas, $bit)
    {
        if (!_srTablo('banka_hareketler')) return [];
        $hes = []; foreach (_fnrBanka($sid) as $h) $hes[(int) $h->id] = $h->banka_adi . ($h->hesap_adi ? ' · ' . $h->hesap_adi : '');
        try { return DB::table('banka_hareketler')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->orderBy('tarih')->orderBy('id')->get()
            ->map(fn ($h) => ['tarih' => substr((string) $h->tarih, 0, 10), 'hesap' => $hes[(int) $h->hesap_id] ?? 'Silinmiş hesap', 'tip' => $h->tip === 'cikis' ? 'cikis' : 'giris', 'tutar' => (float) $h->tutar, 'aciklama' => (string) ($h->aciklama ?? '')])->all(); } catch (\Throwable $e) { return []; }
    }
    function _fnrCek($sid)
    {
        if (!_srTablo('cek_senetler')) return [];
        try { return DB::table('cek_senetler')->where('sube_id', $sid)->orderBy('vade')->get()->all(); } catch (\Throwable $e) { return []; }
    }
    function _fnrCekDurumAd($d) { return ['portfoyde' => 'Portföyde', 'tahsil' => 'Tahsil edildi', 'odendi' => 'Ödendi', 'karsiliksiz' => 'Karşılıksız', 'ciro' => 'Ciro edildi'][$d] ?? ($d ?: '—'); }
    function _fnrSabit($sid)
    {
        if (!_srTablo('tekrarlayan_giderler')) return [];
        try { return DB::table('tekrarlayan_giderler')->where('sube_id', $sid)->orderByDesc('aktif')->orderBy('gun')->get()->all(); } catch (\Throwable $e) { return []; }
    }
    // Aylık sabit gider yükü: aktif tekrarlayan aylık + yıllık/12
    function _fnrSabitAylik($sid) { $t = 0.0; foreach (_fnrSabit($sid) as $g) if ((int) $g->aktif) $t += (float) $g->tutar / (($g->periyot ?? 'aylik') === 'yillik' ? 12 : 1); return round($t, 2); }
    function _fnrIptalTipAd($t) { return ['void' => 'Ürün iptali (void)', 'ikram' => 'İkram', 'indirim' => 'İndirim', 'odeme_iptal' => 'Ödeme iptali', 'iptal' => 'Adisyon iptali', 'puan' => 'Puan / ödül indirimi'][$t] ?? ($t ?: '—'); }
    function _fnrIptal($sid, $bas, $bit)
    {
        if (!_srTablo('iptal_indirim_loglari')) return [];
        try { return DB::table('iptal_indirim_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(['tip', 'tutar', 'sebep', 'personel_id', 'created_at'])
            ->map(fn ($r) => ['tip' => (string) $r->tip, 'tutar' => abs((float) $r->tutar), 'sebep' => trim((string) ($r->sebep ?? '')) ?: 'Belirtilmemiş', 'kisi' => _fnrPersonelAd($r->personel_id), 'tarih' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
    function _fnrIptalAdisyon($sid, $bas, $bit)
    {
        if (!_srTablo('adisyonlar')) return ['n' => 0, 'tl' => 0.0];
        try { $q = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'iptal')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59']); return ['n' => (int) (clone $q)->count(), 'tl' => round((float) $q->sum('toplam'), 2)]; } catch (\Throwable $e) { return ['n' => 0, 'tl' => 0.0]; }
    }
    function _fnrFire($sid, $bas, $bit)
    {
        $o = ['fire' => [], 'sayim' => []]; $malz = []; try { if (_srTablo('malzemeler')) $malz = DB::table('malzemeler')->pluck('ad', 'id')->all(); } catch (\Throwable $e) {}
        if (_srTablo('stok_hareketleri')) {
            $fs = _srKolon('stok_hareketleri', 'fire_sebep');
            try { foreach (DB::table('stok_hareketleri')->where('sube_id', $sid)->where('tip', 'fire')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get() as $h)
                $o['fire'][] = ['tarih' => substr((string) $h->created_at, 0, 10), 'malzeme' => $malz[(int) $h->malzeme_id] ?? '—', 'miktar' => abs((float) $h->miktar), 'maliyet' => round(abs((float) $h->miktar) * (float) $h->birim_maliyet, 2),
                    'kaynak' => ($h->kaynak_tip ?? '') === 'void' ? 'Pişmiş ürün iptali' : 'Elle girilen fire', 'sebep' => trim((string) (($fs ? $h->fire_sebep : null) ?: ($h->aciklama ?? ''))) ?: 'Belirtilmemiş']; } catch (\Throwable $e) {}
        }
        if (_srTablo('sayimlar') && _srTablo('sayim_kalemleri')) {
            try { foreach (DB::table('sayim_kalemleri as k')->join('sayimlar as s', 'k.sayim_id', '=', 's.id')->where('s.sube_id', $sid)->whereBetween('s.tarih', [$bas, $bit . ' 23:59:59'])->where('k.fark', '!=', 0)->get(['s.tarih', 'k.malzeme_id', 'k.teorik', 'k.sayilan', 'k.fark', 'k.fark_maliyet']) as $x)
                $o['sayim'][] = ['tarih' => substr((string) $x->tarih, 0, 10), 'malzeme' => $malz[(int) $x->malzeme_id] ?? '—', 'teorik' => (float) $x->teorik, 'sayilan' => (float) $x->sayilan, 'fark' => (float) $x->fark, 'maliyet' => round((float) $x->fark_maliyet, 2)]; } catch (\Throwable $e) {}
        }
        return $o;
    }
    function _fnrPersHareket($sid, $bas, $bit)
    {
        if (!_srTablo('personel_hareketleri')) return [];
        try { return DB::table('personel_hareketleri')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->orderBy('tarih')->get(['personel_id', 'tur', 'tutar', 'tarih', 'aciklama'])
            ->map(fn ($h) => ['pid' => (int) $h->personel_id, 'ad' => _fnrPersonelAd($h->personel_id), 'tur' => (string) $h->tur, 'tutar' => (float) $h->tutar, 'tarih' => substr((string) $h->tarih, 0, 10), 'aciklama' => (string) ($h->aciklama ?? '')])->all(); } catch (\Throwable $e) { return []; }
    }
    // KDV: ay ay hesaplanan (satış, KDV dahil ciro / (1+oran)) − indirilecek (alış faturası KDV'si; girilmemişse aynı oranla tahmini)
    function _fnrKdv($sid, $bas, $bit)
    {
        $oran = _fnrKdvOran($sid); $ay = [];
        foreach (_fnrSatis($sid, $bas, $bit) as $a) $ay[$a['ay']]['ciro'] = ($ay[$a['ay']]['ciro'] ?? 0) + $a['toplam'];
        foreach (_fnrAlis($sid, $bas, $bit) as $f) { $x = &$ay[$f['ay']]; $x['alis'] = ($x['alis'] ?? 0) + $f['toplam']; if ($f['kdv'] > 0) $x['kdv'] = ($x['kdv'] ?? 0) + $f['kdv']; else { $x['tah'] = ($x['tah'] ?? 0) + $f['toplam'] - $f['toplam'] / (1 + $oran / 100); } unset($x); }
        ksort($ay); $o = [];
        foreach ($ay as $m => $v) { $c = $v['ciro'] ?? 0; $hes = $c - $c / (1 + $oran / 100); $ind = ($v['kdv'] ?? 0) + ($v['tah'] ?? 0); $o[] = ['ay' => $m, 'ciro' => round($c, 2), 'hes' => round($hes, 2), 'alis' => round($v['alis'] ?? 0, 2), 'ind' => round($ind, 2), 'tah' => ($v['tah'] ?? 0) > 0, 'oden' => round($hes - $ind, 2)]; }
        return $o;
    }
    // Önümüzdeki ödeme / tahsilat olayları (vadesi geçmiş dahil): tedarikçi faturası, çek / senet, tekrarlayan gider, aylık maaş
    function _fnrOlaylar($sid, $gunSayisi = 35)
    {
        $bugun = date('Y-m-d'); $son = date('Y-m-d', strtotime("+$gunSayisi day")); $o = [];
        foreach (_fnrTedBorc($sid)['tedarikciler'] as $t) foreach ($t['faturalar'] as $f) if ($f['vade'] <= $son) $o[] = ['tarih' => $f['vade'], 'tur' => 'Tedarikçi faturası', 'kisi' => $t['ad'], 'aciklama' => 'Fatura ' . ($f['no'] ?: '#' . $f['id']), 'tutar' => (float) $f['tutar'], 'yon' => -1];
        foreach (_fnrCek($sid) as $c) if (($c->durum ?? '') === 'portfoyde' && $c->vade && substr((string) $c->vade, 0, 10) <= $son)
            $o[] = ['tarih' => substr((string) $c->vade, 0, 10), 'tur' => ($c->tip === 'senet' ? 'Senet' : 'Çek') . ($c->yon === 'verilen' ? ' (verilen)' : ' (alınan)'), 'kisi' => (string) ($c->kisi ?? '—'), 'aciklama' => trim(($c->banka ?? '') . ' ' . ($c->no ?? '')), 'tutar' => (float) $c->tutar, 'yon' => $c->yon === 'verilen' ? -1 : 1];
        $islenmis = [];
        if (_srTablo('giderler')) { try { foreach (DB::table('giderler')->where('sube_id', $sid)->where('aciklama', 'like', '[Sabit]%')->where('tarih', '>=', date('Y-m-01'))->get(['aciklama', 'tarih']) as $g) $islenmis[substr((string) $g->tarih, 0, 7) . '|' . trim(substr((string) $g->aciklama, 8))] = 1; } catch (\Throwable $e) {} }
        $aylar = []; for ($t = strtotime(date('Y-m-01')); $t <= strtotime($son); $t = strtotime('+1 month', $t)) $aylar[] = date('Y-m', $t);
        foreach (_fnrSabit($sid) as $g) {
            if (!(int) $g->aktif || ($g->periyot ?? 'aylik') !== 'aylik') continue;
            foreach ($aylar as $a) { $t = $a . '-' . str_pad((string) min(28, max(1, (int) $g->gun)), 2, '0', STR_PAD_LEFT); if ($t < $bugun || $t > $son || isset($islenmis[$a . '|' . $g->ad])) continue; $o[] = ['tarih' => $t, 'tur' => 'Sabit gider', 'kisi' => _fnrGiderAd($g->kategori), 'aciklama' => (string) $g->ad, 'tutar' => (float) $g->tutar, 'yon' => -1]; }
        }
        $maas = 0.0; try { if (_srTablo('personeller')) $maas = (float) DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->where('rol', '!=', 'sahip')->where(fn ($w) => $w->whereNull('maas_tipi')->orWhere('maas_tipi', 'aylik'))->sum('maas'); } catch (\Throwable $e) {}
        if ($maas > 0) { $mg = _fnrAyar($sid)['maas_gunu']; foreach ($aylar as $a) { $t = $a . '-' . str_pad((string) $mg, 2, '0', STR_PAD_LEFT); if ($t >= $bugun && $t <= $son) $o[] = ['tarih' => $t, 'tur' => 'Maaş ödemesi', 'kisi' => 'Aylık maaşlı personel', 'aciklama' => 'Personel kartlarındaki aylık maaş toplamı', 'tutar' => round($maas, 2), 'yon' => -1]; } }
        foreach ($o as &$x) $x['kalan'] = (int) round((strtotime($x['tarih']) - strtotime($bugun)) / 86400); unset($x);
        usort($o, fn ($a, $b) => [$a['tarih'], $a['yon']] <=> [$b['tarih'], $b['yon']]);
        return $o;
    }
    // 4 haftalık nakit tahmini: banka + kasa ile başla; ortalama günlük tahsilat ve olağan gider (son 28 gün) + vadeli olaylar
    function _fnrNakitTahmin($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        $banka = round(array_sum(array_map(fn ($h) => (int) ($h->aktif ?? 1) ? (float) $h->bakiye : 0, _fnrBanka($sid))), 2); $kasa = _fnrKasaNakit($sid);
        $b28 = date('Y-m-d', strtotime('-28 day')); $d1 = date('Y-m-d', strtotime('-1 day'));
        $tah = array_sum(array_map(fn ($o) => $o['tip'] === 'acik_hesap' ? 0 : $o['tutar'], _fnrOdeme($sid, $b28, $d1))) / 4;
        $olagan = array_sum(array_map(fn ($g) => in_array($g['kat'], ['fatura', 'vergi', 'diger'], true) && !str_starts_with($g['aciklama'], '[Sabit]') ? $g['tutar'] : 0, _fnrGider($sid, $b28, $d1))) / 4;
        $ol = _fnrOlaylar($sid, 27); $bas = $banka + $kasa['tutar']; $hafta = []; $bugun = date('Y-m-d');
        for ($w = 0; $w < 4; $w++) {
            $hb = date('Y-m-d', strtotime("+" . ($w * 7) . " day")); $he = date('Y-m-d', strtotime("+" . ($w * 7 + 6) . " day"));
            $x = ['bas' => $hb, 'bit' => $he, 'acilis' => round($bas, 2), 'tahsilat' => round($tah, 2), 'cek_al' => 0.0, 'ted' => 0.0, 'cek_ver' => 0.0, 'sabit' => 0.0, 'maas' => 0.0, 'olagan' => round($olagan, 2)];
            foreach ($ol as $e) {
                if (!($w === 0 ? $e['tarih'] <= $he : ($e['tarih'] >= $hb && $e['tarih'] <= $he))) continue;
                $k = $e['tur'] === 'Tedarikçi faturası' ? 'ted' : ($e['tur'] === 'Sabit gider' ? 'sabit' : ($e['tur'] === 'Maaş ödemesi' ? 'maas' : ($e['yon'] > 0 ? 'cek_al' : 'cek_ver')));
                $x[$k] += $e['tutar'];
            }
            $x['kapanis'] = round($bas + $x['tahsilat'] + $x['cek_al'] - $x['ted'] - $x['cek_ver'] - $x['sabit'] - $x['maas'] - $x['olagan'], 2); $bas = $x['kapanis']; $hafta[] = $x;
        }
        return $c[$sid] = ['banka' => $banka, 'kasa' => $kasa, 'baslangic' => round($banka + $kasa['tutar'], 2), 'tahsilat_hafta' => round($tah, 2), 'olagan_hafta' => round($olagan, 2), 'hafta' => $hafta,
            'en_dusuk' => $hafta ? min(array_column($hafta, 'kapanis')) : null, 'gecikmis' => round(array_sum(array_map(fn ($e) => $e['yon'] < 0 && $e['tarih'] < $bugun ? $e['tutar'] : 0, $ol)), 2)];
    }
    // Stok değeri (Bilanço ekranının hesabı)
    function _fnrStokDeger($sid)
    {
        $s = 0.0;
        try { if (_srKolon('malzemeler', 'guncel_maliyet') && _srKolon('malzemeler', 'mevcut') && _srKolon('malzemeler', 'sube_id')) $s = (float) DB::table('malzemeler')->where('sube_id', $sid)->selectRaw('SUM(COALESCE(mevcut,0)*COALESCE(guncel_maliyet,0)) t')->value('t'); } catch (\Throwable $e) {}
        if ($s <= 0 && _srTablo('stok_hareketleri') && _srKolon('malzemeler', 'guncel_maliyet')) { try { $s = max(0, (float) DB::table('stok_hareketleri as h')->join('malzemeler as m', 'm.id', '=', 'h.malzeme_id')->where('h.sube_id', $sid)->selectRaw('SUM(h.miktar * COALESCE(m.guncel_maliyet,0)) t')->value('t')); } catch (\Throwable $e) {} }
        return round($s, 2);
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_fnrVeriDurumu')) {
    function _fnrVeriDurumu($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t); if (_srKolon($t, 'sube_id')) $q->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d = ['satis' => $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')), 'gider' => $say('giderler', 'tarih'), 'alis' => $say('alis_faturalari', 'tarih'),
            'kasa' => $say('kasa_oturumlari', 'kapanis_zamani', fn ($q) => $q->where('durum', 'kapali')), 'kasa_hareket' => $say('kasa_hareketleri', 'created_at'),
            'cari' => $say('cari_hareketler', 'created_at'), 'banka' => $say('banka_hesaplar', null), 'banka_hareket' => $say('banka_hareketler', 'tarih'), 'cek' => $say('cek_senetler', null),
            'sabit' => $say('tekrarlayan_giderler', null, fn ($q) => $q->where('aktif', 1)), 'demirbas' => $say('demirbaslar', null, fn ($q) => $q->where('alis_bedel', '>', 0)),
            'iptal' => $say('iptal_indirim_loglari', 'created_at'), 'fire' => $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('tip', 'fire')),
            'pers_hareket' => $say('personel_hareketleri', 'tarih'), 'maas' => $say('personeller', null, fn ($q) => $q->where('aktif', 1)->where('maas', '>', 0)->where('rol', '!=', 'sahip')),
            'odeme' => 0, 'online' => 0, 'recete' => 0, 'butce' => _fnrAyar($sid)['butce_var'] ? 1 : 0];
        if (_srTablo('odemeler') && _srTablo('adisyonlar')) { try { $d['odeme'] = (int) DB::table('odemeler as o')->join('adisyonlar as a', 'o.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->where('o.created_at', '>=', $s)->count(); } catch (\Throwable $e) {} }
        if (_srKolon('adisyonlar', 'platform')) $d['online'] = $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')->whereNotNull('platform')->where('platform', '!=', ''));
        if (_srTablo('receteler')) { try { $d['recete'] = (int) DB::table('receteler')->whereNotNull('urun_id')->count(); } catch (\Throwable $e) {} }
        return $c[$sid] = $d;
    }
    function _fnrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'satis_yok' => 'Ödenmiş adisyon yok: son 1 yılda POS\'ta kapatılmış satış bulunamadı. Ciro, ortalama hesap, KDV ve kârlılık raporları ödemesi alınıp kapatılan adisyonlardan hesaplanır. POS\'ta masa / paket adisyonları ödeme alınarak kapatıldıkça dolar.',
            'satis' => 'Seçili dönemde (' . $donem . ') ödenmiş adisyon yok.',
            'odeme' => 'Seçili dönemde (' . $donem . ') ödeme kaydı yok. Ödeme tipleri POS\'ta adisyon kapatılırken seçilen ödeme şeklinden (nakit, kart, yemek kartı…) gelir.',
            'gider_yok' => 'Gider kaydı yok. Kira, fatura, vergi gibi giderler Finans / Muhasebe > Giderler ekranından girilir; personel avans / maaş ödemeleri ve alış faturaları oraya otomatik düşer. Gider girilmezse kâr olduğundan yüksek görünür.',
            'gider' => 'Seçili dönemde (' . $donem . ') gider kaydı yok. Giderler Finans / Muhasebe > Giderler ekranından girilir.',
            'kasa_yok' => 'Kasa vardiyası kaydı yok: kasa farkı, kasa açılırken girilen devir ve gün sonunda sayılan nakit ile hesaplanır. Finans / Muhasebe > Kasa (Vardiya) ekranından her gün "Kasa Aç", gün sonunda nakdi sayıp "Kasa Kapat" yapın.',
            'kasa' => 'Seçili dönemde (' . $donem . ') kapatılmış kasa vardiyası yok.',
            'kasa_hareket' => 'Seçili dönemde (' . $donem . ') kasa hareketi yok. Kasa hareketleri yalnız açık bir kasa vardiyası varken yazılır (Kasa (Vardiya) > Kasa Aç).',
            'cari_yok' => 'Cari (açık hesap) kaydı yok. Müşteriye veresiye satış POS\'ta "Açık Hesap" ödemesiyle, tahsilatlar Finans / Muhasebe > Cari Hesaplar ekranından girilir.',
            'cari' => 'Bakiyesi açık (borçlu) müşteri carisi yok.',
            'alis_yok' => 'Alış faturası yok. Tedarikçi borcu ve indirilecek KDV alış faturalarından hesaplanır: Satın Alma > Alış Faturası ya da e-fatura gelen kutusundan girin. Fatura vade tarihi ya da tedarikçi kartında ödeme vadesi (gün) girilirse yaşlandırma doğru çıkar.',
            'ted_borc' => 'Ödenmemiş tedarikçi faturası yok (hepsi "ödendi" işaretlenmiş).',
            'banka_yok' => 'Banka hesabı tanımlı değil. Finans / Muhasebe > Banka Hesapları ekranından hesap ve hareket ekleyin; nakit tahmini ve bilanço banka bakiyesini buradan alır.',
            'banka_hareket' => 'Seçili dönemde (' . $donem . ') banka hareketi girilmemiş.',
            'cek_yok' => 'Çek / senet kaydı yok. Alınan ve verilen çek / senetler Finans / Muhasebe > Çek / Senet ekranından girilir.',
            'cek' => 'Portföyde vadesi bekleyen çek / senet yok.',
            'sabit_yok' => 'Tekrarlayan (sabit) gider tanımı yok. Kira, aidat, internet, muhasebe ücreti gibi her ay tekrar eden giderleri Finans / Muhasebe > Tekrarlayan Giderler ekranından tanımlayın; nakit tahmini ve başabaş noktası bunlardan hesaplanır.',
            'butce_yok' => 'Bütçe girilmemiş. Bu sayfanın üstündeki "Hedef & bütçe ayarları" kutusundan gider kategorileri için aylık bütçe ve aylık ciro hedefi girin; rapor bütçe ile gerçekleşeni karşılaştırır.',
            'online_yok' => 'Online platform siparişi yok. Yemeksepeti / Getir / Trendyol gibi platform siparişleri entegrasyonla ya da Paket ekranında platform seçilerek girildiğinde kanal komisyonu hesaplanır.',
            'iptal' => 'Seçili dönemde (' . $donem . ') iptal, ikram ya da indirim kaydı yok.',
            'fire_yok' => 'Fire ya da sayım farkı kaydı yok. Stok > Fire girişi ve Stok > Sayım ekranlarından girilir; pişmiş ürün iptalleri (void) fireye otomatik düşer.',
            'fire' => 'Seçili dönemde (' . $donem . ') fire ya da sayım farkı yok.',
            'demirbas_yok' => 'Alış bedeli girilmiş demirbaş yok. Finans / Muhasebe > Demirbaş & Etiket ekranında demirbaşların kategori, alış tarihi ve bedelini girin; amortisman bundan hesaplanır.',
            'pers_hareket' => 'Seçili dönemde (' . $donem . ') personele ödeme, avans, prim ya da kesinti kaydı yok. İnsan Kaynakları > Personel & Maaş ekranından girilir.',
            'maas_yok' => 'Personel kartlarında maaş girilmemiş: işçilik maliyeti maaştan hesaplanır, yoksa giderlerdeki maaş ödemeleri kullanılır. İnsan Kaynakları > Personel & Maaş\'ta maaş ve maaş tipini girin.',
            'acik' => 'Açık kalmış, tahsil edilmemiş ya da veresiye satış yok.',
            'buyuk' => 'Seçili dönemde (' . $donem . ') olağandışı ya da mükerrer görünen gider yok.',
            'olay' => 'Önümüzdeki günlerde vadesi gelen tedarikçi faturası, çek / senet, sabit gider ya da maaş ödemesi yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_fnrKatalog')) {
    function _fnrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Finans Bülteni', 'Dünün cirosu, ödeme tipleri, kasa sayım farkı, giderler, iptal-indirim kaybı, tahmini kâr ve önümüzdeki 7 günün ödemeleri.', 'today', '#4F46E5', ['satis', 'odeme', 'kasa'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Finans Raporu', 'Haftanın gelir tablosu, gün gün ciro-maliyet-kâr, ödeme tipleri, gider dağılımı, kasa farkları ve önceki haftayla kıyas.', 'date_range', '#4338CA', ['satis', 'gider'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Finans Yönetim Raporu', 'Ayın gelir tablosu (P&L), ana maliyet, bütçe-gerçek, KDV özeti, alacak-borç durumu ve nakit tahmini — patron ve muhasebeci için tek dosya.', 'calendar_month', '#3730A3', ['satis', 'gider', 'maas'], 'gecen_ay'],
            'kar_zarar' => ['karlilik', 'Gelir Tablosu (Kâr-Zarar)', 'Brüt satış → indirim / ikram → KDV → net satış → satılan malın maliyeti → brüt kâr → komisyon, işçilik, işletme giderleri → FAVÖK → amortisman → net kâr; önceki dönemle kıyas.', 'account_balance', '#059669', ['satis', 'recete', 'gider', 'maas'], null],
            'prime_cost' => ['karlilik', 'Ana Maliyet (Gıda + İşçilik)', 'Gıda maliyeti ve işçilik maliyetinin net satıştaki payı (prime cost); hedefle kıyas, gün gün seyir ve en yüksek maliyetli ürünler.', 'pie_chart', '#047857', ['recete', 'maas', 'satis'], null],
            'gun_kar' => ['karlilik', 'Gün Gün Kârlılık', 'Her gün ciro, SMM, işçilik, gider, komisyon ve tahmini kâr; haftanın günlerine göre ortalama kâr.', 'stacked_line_chart', '#0F766E', ['satis', 'gider'], null],
            'basabas' => ['karlilik', 'Başabaş Noktası', 'Sabit giderleri karşılamak için gereken aylık / günlük ciro, katkı marjı ve güvenlik payı (gerçekleşen ciro başabaşın ne kadar üstünde).', 'balance', '#115E59', ['sabit', 'satis', 'maas'], null],
            'ciro' => ['gelir', 'Ciro Analizi', 'Gün gün ciro ve adisyon, önceki döneme göre değişim, haftanın günleri, saat dağılımı ve 12 aylık ciro trendi.', 'insights', '#2563EB', ['satis'], null],
            'ort_hesap' => ['gelir', 'Ortalama Hesap & Kuver', 'Ortalama adisyon tutarı, kişi başı harcama, misafir sayısı; kanala, güne ve saate göre; hesap büyüklüğü dağılımı.', 'groups', '#1D4ED8', ['satis'], null],
            'kanal' => ['gelir', 'Kanal & Online Komisyon', 'Salon, paket, QR ve online platform cirosu; platform bazında komisyon, komisyon sonrası net gelir ve sipariş başı tutar.', 'delivery_dining', '#1E40AF', ['satis', 'online'], null],
            'odeme_tipi' => ['tahsilat', 'Ödeme Tipi Dağılımı', 'Nakit, kart, yemek kartı, online ve açık hesap tahsilatları; gün gün ödeme tipleri ve bahşiş.', 'credit_card', '#0EA5E9', ['odeme'], null],
            'kasa_fark' => ['tahsilat', 'Kasa Sayım Farkları', 'Her kasa kapanışında beklenen ve sayılan nakit, açık / fazla; kasa ve kapatan kişi bazında toplam fark.', 'point_of_sale', '#0284C7', ['kasa'], null],
            'kasa_hareket' => ['tahsilat', 'Kasa Hareketleri', 'Kasaya giren ve çıkan nakit: türe göre özet, kasadan alınan / konulan paralar ve gün gün nakit akışı.', 'swap_vert', '#0369A1', ['kasa_hareket'], null],
            'acik_tahsilat' => ['tahsilat', 'Tahsil Edilmemiş Satışlar', 'Açık kalmış eski adisyonlar, parası kuryede kalan paket siparişler ve dönemdeki veresiye (açık hesap) satışlar.', 'pending_actions', '#075985', ['satis', 'cari'], null],
            'gider_dagilim' => ['gider', 'Gider Dağılımı & Trend', 'Kategoriye göre giderler, cirodaki payı, önceki döneme göre değişim ve son 6 ayın kategori trendi.', 'donut_large', '#F59E0B', ['gider'], null],
            'gider_buyuk' => ['gider', 'Büyük & Şüpheli Giderler', 'Dönemin en büyük giderleri, kategorisine göre olağandışı yüksek kayıtlar ve aynı tutarla tekrarlanmış olası mükerrer girişler.', 'manage_search', '#D97706', ['gider'], null],
            'sabit_gider' => ['gider', 'Sabit Giderler (Plan & Gerçek)', 'Tekrarlayan giderler: aylık yük, bu ay işlendi mi, kategori bazında plan ile gerçekleşen.', 'event_repeat', '#B45309', ['sabit', 'gider'], null],
            'butce' => ['gider', 'Bütçe & Hedef Karşılaştırması', 'Kategori bütçeleri ile gerçekleşen gider, ciro hedefi ile gerçekleşen ciro, gıda / işçilik / ana maliyet hedefleri.', 'flag', '#92400E', ['butce', 'gider', 'satis'], null],
            'nakit_akis' => ['nakit', 'Nakit Akışı', 'Gün gün tahsilat (ödeme tiplerine göre) + cari tahsilatlar ile gider çıkışları; net nakit ve kümülatif akış.', 'account_balance_wallet', '#0D9488', ['odeme', 'gider'], null],
            'nakit_tahmin' => ['nakit', 'Nakit Tahmini (4 Hafta)', 'Banka + kasa ile başlayıp ortalama tahsilat, tedarikçi vadeleri, çek / senet, sabit gider ve maaşlarla önümüzdeki 4 haftanın tahmini nakit durumu.', 'query_stats', '#0F766E', ['banka', 'alis', 'sabit'], null],
            'banka' => ['nakit', 'Banka Hesapları', 'Hesap bakiyeleri, dönemdeki giriş-çıkış hareketleri ve hesap bazında net akış.', 'account_balance', '#115E59', ['banka', 'banka_hareket'], null],
            'cari_alacak' => ['alacak', 'Müşteri Alacak Yaşlandırma', 'Açık hesap müşteri bakiyeleri: 0-30 / 31-60 / 61-90 / 90+ gün, son tahsilat tarihi ve en eski açık borç.', 'hourglass_bottom', '#7C3AED', ['cari'], null],
            'tedarikci_borc' => ['alacak', 'Tedarikçi Borç Yaşlandırma', 'Ödenmemiş alış faturaları: gecikmiş, 7 gün, 30 gün ve ileri vadeli borç; tedarikçi ve fatura bazında.', 'local_shipping', '#6D28D9', ['alis'], null],
            'cek_senet' => ['alacak', 'Çek & Senet Vade Takvimi', 'Portföydeki alınan / verilen çek ve senetler: vade kovaları, vadesi geçenler, karşılıksızlar.', 'request_quote', '#5B21B6', ['cek'], null],
            'kdv' => ['vergi', 'KDV Özeti', 'Ay ay hesaplanan KDV (satış), indirilecek KDV (alış faturası) ve ödenecek KDV tahmini; tedarikçi bazında indirilecek KDV.', 'receipt_long', '#E11D48', ['satis', 'alis'], null],
            'vergi_takvim' => ['vergi', 'Vergi Takvimi & Tahmini Yük', 'Yaklaşan beyanname ve ödeme tarihleri, geçen ayın tahmini ödenecek KDV\'si ve kalan gün.', 'event_available', '#BE123C', ['satis'], null],
            'iptal_indirim' => ['kayip', 'İptal, İkram & İndirim Kaybı', 'İptal, ikram, indirim ve ödeme iptali: türe, sebebe ve personele göre tutar; ciroya oranı ve iptal edilen adisyonlar.', 'money_off', '#DC2626', ['iptal', 'satis'], null],
            'fire_kayip' => ['kayip', 'Fire & Sayım Farkı Maliyeti', 'Fire (zayi) ve sayım farklarının maliyeti: malzemeye ve sebebe göre; ciroya oranı.', 'delete_sweep', '#B91C1C', ['fire'], null],
            'personel_gider' => ['varlik', 'Personel Maliyeti & Ödemeleri', 'Hesaplanan işçilik maliyeti ve cirodaki payı; personele yapılan maaş, avans, prim ve kesinti ödemeleri; bahşiş.', 'badge', '#64748B', ['maas', 'pers_hareket'], null],
            'amortisman' => ['varlik', 'Amortisman', 'Demirbaşların alış bedeli, oranı, yıllık ve dönem amortismanı, birikmiş amortisman ve net defter değeri.', 'trending_down', '#475569', ['demirbas'], null],
            'bilanco' => ['varlik', 'Özet Bilanço', 'Kasa, banka, alacaklar, çek, stok ve demirbaş ile tedarikçi borcu, verilen çekler ve KDV borcu; tahmini özkaynak.', 'balance', '#334155', ['banka', 'alis', 'cari'], null],
        ];
    }
    function _fnrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron ve muhasebe için hazır raporlar: günlük finans bülteni, haftalık rapor ve aylık yönetim raporu.', '#4F46E5'],
            'karlilik' => ['Kârlılık', 'trending_up', 'Gelir tablosu (P&L), ana maliyet, gün gün kârlılık ve başabaş noktası.', '#059669'],
            'gelir' => ['Gelir & Satış', 'insights', 'Ciro trendi, ortalama hesap ve kuver, kanallar ve online platform komisyonu.', '#2563EB'],
            'tahsilat' => ['Tahsilat & Kasa', 'point_of_sale', 'Ödeme tipleri, kasa sayım farkları, kasa hareketleri ve tahsil edilmemiş satışlar.', '#0EA5E9'],
            'gider' => ['Giderler & Bütçe', 'receipt_long', 'Gider dağılımı, büyük ve şüpheli giderler, sabit giderler, bütçe ve hedeflerle karşılaştırma.', '#F59E0B'],
            'nakit' => ['Nakit Akışı', 'account_balance_wallet', 'Gün gün nakit giriş-çıkış, önümüzdeki 4 haftanın nakit tahmini ve banka hesapları.', '#0D9488'],
            'alacak' => ['Alacak & Borç', 'swap_horiz', 'Müşteri alacak yaşlandırma, tedarikçi borç yaşlandırma, çek ve senet vadeleri.', '#7C3AED'],
            'vergi' => ['Vergi & KDV', 'receipt', 'Hesaplanan ve indirilecek KDV, ödenecek KDV tahmini ve vergi takvimi.', '#E11D48'],
            'kayip' => ['Kayıp & Kaçak', 'money_off', 'İptal, ikram ve indirim kaybı; fire ve sayım farkı maliyeti.', '#DC2626'],
            'varlik' => ['Personel & Varlık', 'inventory_2', 'Personel maliyeti ve ödemeleri, amortisman ve özet bilanço.', '#64748B'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı finans özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_fnrSayfaPl')) {
    // Gelir tablosu satırları: [Kalem, Tutar, Net satışa %, Önceki dönem, Değişim %]
    function _fnrPlSatir(array $p, array $o)
    {
        $y = fn ($a) => $p['net'] > 0 ? round($a / $p['net'] * 100, 1) : null; $dg = fn ($a, $b) => _srYuzde($a, $b);
        $s = [['Brüt satış (indirim / ikram öncesi, KDV dahil)', $p['brut'], null, $o['brut'], $dg($p['brut'], $o['brut'])], ['− İndirimler', -$p['indirim'], $y(-$p['indirim']), -$o['indirim'], null], ['− İkramlar', -$p['ikram'], $y(-$p['ikram']), -$o['ikram'], null],
            ['Ciro (KDV dahil)', $p['ciro'], null, $o['ciro'], $dg($p['ciro'], $o['ciro'])], ['− Hesaplanan KDV (%' . $p['kdv_oran'] . ')', -$p['kdv'], null, -$o['kdv'], null],
            ['NET SATIŞ', $p['net'], 100.0, $o['net'], $dg($p['net'], $o['net'])], ['− Satılan malın maliyeti (SMM)' . ($p['smm_tahmini'] > 0 ? ' *' : ''), -$p['smm'], $y(-$p['smm']), -$o['smm'], $dg($p['smm'], $o['smm'])],
            ['BRÜT KÂR', $p['brut_kar'], $y($p['brut_kar']), $o['brut_kar'], $dg($p['brut_kar'], $o['brut_kar'])], ['− Online platform komisyonu (tahmini)', -$p['komisyon'], $y(-$p['komisyon']), -$o['komisyon'], $dg($p['komisyon'], $o['komisyon'])],
            ['− İşçilik maliyeti' . ($p['isc_kaynak'] === 'gider' ? ' (giderlerdeki maaş ödemeleri)' : ($p['isc_tahmini'] ? ' (kısmen tahmini)' : '')), -$p['iscilik'], $y(-$p['iscilik']), -$o['iscilik'], $dg($p['iscilik'], $o['iscilik'])]];
        $katlar = array_unique(array_merge(array_keys($p['isletme']), array_keys($o['isletme'])));
        foreach ($katlar as $k) $s[] = ['− ' . _fnrGiderAd($k), -($p['isletme'][$k] ?? 0), $y(-($p['isletme'][$k] ?? 0)), -($o['isletme'][$k] ?? 0), $dg($p['isletme'][$k] ?? 0, $o['isletme'][$k] ?? 0)];
        $s[] = ['FAVÖK (faaliyet kârı)', $p['favok'], $y($p['favok']), $o['favok'], $o['favok'] > 0 ? $dg($p['favok'], $o['favok']) : null];
        $s[] = ['− Amortisman (dönem payı)', -$p['amort'], $y(-$p['amort']), -$o['amort'], null];
        $s[] = ['NET KÂR (vergi öncesi, tahmini)', $p['net_kar'], $y($p['net_kar']), $o['net_kar'], $o['net_kar'] > 0 ? $dg($p['net_kar'], $o['net_kar']) : null];
        return $s;
    }
    function _fnrSayfaPl(array $p, array $o, $bos)
    {
        $not = 'Net satış = KDV dahil ciro ÷ (1 + %' . $p['kdv_oran'] . '). SMM = satılan ürün × reçete maliyeti' . ($p['smm_tahmini'] > 0 ? ' (* reçetesi / maliyeti olmayan ürünlerde satışın %30\'u tahmini sayıldı: ' . number_format($p['smm_tahmini'], 2, ',', '.') . ' TL)' : '') . '. '
            . 'Giderlerdeki "malzeme" (alış faturaları, ' . number_format($p['gider_malzeme'], 2, ',', '.') . ' TL) SMM ile, "maaş" ödemeleri (' . number_format($p['gider_maas'], 2, ',', '.') . ' TL) işçilikle çift sayılmasın diye ayrıca düşülmez. Resmi mali tablo değildir.';
        return ['grafiksiz' => true] + _srSayfa('Gelir Tablosu', $not, [['Kalem', 'm', 46], ['Tutar', 'tl', 15], ['Net Satışa %', 'y', 11], ['Önceki Dönem', 'tl', 15], ['Değişim %', 'y', 10]], _fnrPlSatir($p, $o), $bos);
    }
    // Gün gün: ciro, adisyon, ort. hesap, SMM, işçilik, işletme gideri, komisyon, tahmini kâr, kâr %
    function _fnrGunTablo($sid, $bas, $bit)
    {
        $oran = _fnrKdvOran($sid); $smm = _fnrSmm($sid, $bas, $bit)['gun']; $isc = _fnrIscilik($sid, $bas, $bit)['gun']; $kom = _fnrKomisyon($sid, $bas, $bit)['gun'];
        $ciro = []; $ad = []; foreach (_fnrSatis($sid, $bas, $bit) as $a) { $ciro[$a['tarih']] = ($ciro[$a['tarih']] ?? 0) + $a['toplam']; $ad[$a['tarih']] = ($ad[$a['tarih']] ?? 0) + 1; }
        $gd = []; $gm = []; foreach (_fnrGider($sid, $bas, $bit) as $g) { if (!in_array($g['kat'], ['malzeme', 'maas'], true)) $gd[$g['tarih']] = ($gd[$g['tarih']] ?? 0) + $g['tutar']; elseif ($g['kat'] === 'maas') $gm[$g['tarih']] = ($gm[$g['tarih']] ?? 0) + $g['tutar']; }
        $iscVar = array_sum($isc) > 0; $o = [];
        foreach (_fnrGunler($bas, $bit) as $t) {
            $c = round($ciro[$t] ?? 0, 2); $n = $c / (1 + $oran / 100); $s = round($smm[$t] ?? 0, 2); $i = round($iscVar ? ($isc[$t] ?? 0) : ($gm[$t] ?? 0), 2); $g = round($gd[$t] ?? 0, 2); $k = round($kom[$t] ?? 0, 2);
            $kar = round($n - $s - $i - $g - $k, 2);
            $o[] = [$t, _fnrGunAd(date('N', strtotime($t))), $c, $ad[$t] ?? 0, !empty($ad[$t]) ? round($c / $ad[$t], 2) : null, $s, $i, $g, $k, $kar, $n > 0 ? round($kar / $n * 100, 1) : null];
        }
        return $o;
    }
    function _fnrSayfaGun(array $sat, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa('Gün Gün Kârlılık', 'Tahmini kâr = net satış (KDV hariç) − SMM − işçilik − işletme gideri − online komisyon. Kira gibi tek günde girilen giderler o günün kârını düşürür; dönem toplamına bakın.',
            [['Tarih', 't', 10], ['Gün', 'm', 10], ['Ciro', 'tl', 13, 'top bar'], ['Adisyon', 's', 8, 'top'], ['Ort. Hesap', 'tl', 11], ['SMM', 'tl', 12, 'top'], ['İşçilik', 'tl', 12, 'top'], ['Gider', 'tl', 12, 'top'], ['Komisyon', 'tl', 11, 'top'], ['Tahmini Kâr', 'tl', 13, 'top'], ['Kâr %', 'y', 8]], $sat, $bos);
    }
    function _fnrOdemeOzet(array $od)
    {
        $t = []; foreach ($od as $x) { $k = $x['tip']; $t[$k]['n'] = ($t[$k]['n'] ?? 0) + 1; $t[$k]['tl'] = ($t[$k]['tl'] ?? 0) + $x['tutar']; $t[$k]['b'] = ($t[$k]['b'] ?? 0) + $x['bahsis']; }
        uasort($t, fn ($a, $b) => $b['tl'] <=> $a['tl']); $top = max(0.01, array_sum(array_column($t, 'tl')));
        return array_map(fn ($k, $v) => [_fnrOdemeAd($k), $v['n'], round($v['tl'], 2), round($v['tl'] / $top * 100, 1), round($v['tl'] / max(1, $v['n']), 2), round($v['b'], 2)], array_keys($t), array_values($t));
    }
    function _fnrSayfaOdeme(array $od, $bos)
    {
        return _srSayfa('Ödeme Tipleri', 'Ödeme anına göre (Z raporuyla aynı). Açık hesap tahsil edilmiş para değildir; cari tahsilatı yapılınca nakde döner.', [['Ödeme Tipi', 'm', 22], ['İşlem', 's', 8, 'top'], ['Tutar', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Ortalama', 'tl', 11], ['Bahşiş', 'tl', 11, 'top']], _fnrOdemeOzet($od), $bos);
    }
    function _fnrGiderKatSat(array $g, array $go, $ciro)
    {
        $k = _fnrGiderKat($g); $ko = _fnrGiderKat($go); $o = [];
        foreach (array_unique(array_merge(array_keys($k), array_keys($ko))) as $x) { $v = $k[$x] ?? 0; $o[] = [_fnrGiderAd($x), count(array_filter($g, fn ($y) => $y['kat'] === $x)), $v, $ciro > 0 ? round($v / $ciro * 100, 1) : null, $ko[$x] ?? 0, _srYuzde($v, $ko[$x] ?? 0)]; }
        usort($o, fn ($a, $b) => $b[2] <=> $a[2]);
        return $o;
    }
    function _fnrSayfaGiderKat(array $sat, $bos)
    {
        return _srSayfa('Gider Kategorileri', 'Giderler defterindeki tüm kayıtlar (alış faturasından düşen malzeme ve personel avans / maaş ödemeleri dahil). Ciroya % = kategori ÷ KDV dahil ciro.', [['Kategori', 'm', 30], ['Kayıt', 's', 7, 'top'], ['Tutar', 'tl', 14, 'top bar'], ['Ciroya %', 'y', 9], ['Önceki Dönem', 'tl', 14, 'top'], ['Değişim %', 'y', 10]], $sat, $bos);
    }
    function _fnrSayfaGiderListe($ad, $not, array $g, $bos)
    {
        return _srSayfa($ad, $not, [['Tarih', 't', 11], ['Kategori', 'm', 24], ['Açıklama', 'm', 40], ['Tutar', 'tl', 13, 'top bar']], array_map(fn ($x) => [$x['tarih'], _fnrGiderAd($x['kat']), $x['aciklama'] ?: '—', $x['tutar']], $g), $bos);
    }
    function _fnrSayfaKasa(array $ko, $bos)
    {
        return _srSayfa('Kasa Kapanışları', 'Fark = sayılan − beklenen nakit (eksi = kasa açığı, artı = fazla). Beklenen = açılış devri + nakit girişler − nakit çıkışlar.',
            [['Tarih', 't', 10], ['Kasa', 'm', 12], ['Saat', 'm', 12], ['Açan', 'm', 14], ['Kapatan', 'm', 14], ['Devir', 'tl', 11], ['Beklenen', 'tl', 12], ['Sayılan', 'tl', 12], ['Fark', 'tl', 11, 'top'], ['Not', 'm', 24]],
            array_map(fn ($x) => [$x['tarih'], $x['kasa'], $x['saat'], $x['acan'], $x['kapatan'], $x['devir'], $x['beklenen'], $x['sayilan'], $x['fark'], $x['not']], $ko), $bos);
    }
    function _fnrSayfaOlay($ad, array $ol, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa($ad, 'Negatif kalan gün = vadesi geçmiş. Tahsilat: alınan çek / senet; ödeme: tedarikçi faturası, verilen çek / senet, sabit gider (Tekrarlayan Giderler) ve aylık maaş (personel kartı, hedef ayarlarındaki maaş gününde).',
            [['Vade', 't', 11], ['Tür', 'm', 18], ['Karşı Taraf', 'm', 22], ['Açıklama', 'm', 26], ['Yön', 'm', 9], ['Tutar', 'tl', 13, 'top bar'], ['Kalan Gün', 's', 9]],
            array_map(fn ($e) => [$e['tarih'], $e['tur'], $e['kisi'], $e['aciklama'] ?: '—', $e['yon'] > 0 ? 'Tahsilat' : 'Ödeme', $e['tutar'], $e['kalan']], $ol), $bos);
    }
    function _fnrSayfaKdv(array $k, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa('KDV (Ay Ay)', 'Hesaplanan KDV ciro (KDV dahil) içinden ayrıştırılır; indirilecek KDV alış faturasındaki KDV tutarıdır (girilmemişse aynı oranla tahmini). Giderlerdeki (elektrik, kira vb.) KDV dahil değildir; kesin beyan için mali müşavirinize danışın.',
            [['Ay', 'm', 12], ['Satış (KDV dahil)', 'tl', 15, 'top'], ['Hesaplanan KDV', 'tl', 14, 'top'], ['Alış (KDV dahil)', 'tl', 15, 'top'], ['İndirilecek KDV', 'tl', 14, 'top'], ['Ödenecek KDV', 'tl', 14, 'top bar'], ['Not', 'm', 26]],
            array_map(fn ($x) => [_fnrAyAd($x['ay']), $x['ciro'], $x['hes'], $x['alis'], $x['ind'], $x['oden'], $x['oden'] < 0 ? 'Devreden KDV (sonraki aya)' : ($x['tah'] ? 'İndirilecek KDV kısmen tahmini' : '')], $k), $bos);
    }
    function _fnrSayfaTedBorc(array $y, $bos)
    {
        $sat = []; foreach ($y['tedarikciler'] as $t) { $en = 0; foreach ($t['faturalar'] as $f) $en = min($en, $f['gun']); $sat[] = [$t['ad'], count($t['faturalar']), round($t['toplam'], 2), round($t['gecikmis'], 2), round($t['hafta'], 2), round($t['ay'], 2), round($t['ileri'], 2), $en < 0 ? -$en : 0]; }
        return _srSayfa('Tedarikçi Borçları', 'Vade = fatura vade tarihi; yoksa fatura tarihi + tedarikçi kartındaki ödeme vadesi (gün). Gecikme günü = en eski gecikmiş faturanın vadesinden bu yana geçen gün.',
            [['Tedarikçi', 'm', 24], ['Fatura', 's', 7, 'top'], ['Toplam Borç', 'tl', 13, 'top bar'], ['Gecikmiş', 'tl', 12, 'top'], ['0-7 Gün', 'tl', 12, 'top'], ['8-30 Gün', 'tl', 12, 'top'], ['31+ Gün', 'tl', 12, 'top'], ['Gecikme (gün)', 's', 10, 'esik:30']], $sat, $bos);
    }
    function _fnrCariSat(array $c) { return array_map(fn ($x) => [$x['ad'], $x['tip'], $x['bakiye'], $x['k'][0], $x['k'][1], $x['k'][2], $x['k'][3], $x['en_eski'], $x['son_tahsilat'], $x['son_satis']], array_values(array_filter($c, fn ($x) => $x['bakiye'] > 0.009))); }
    function _fnrSayfaCari(array $sat, $bos)
    {
        return _srSayfa('Müşteri Alacakları', 'Tahsilatlar en eski borçtan düşülür; kalan borç yaşına göre ayrılır. En eski = ödenmemiş en eski borcun gün sayısı.',
            [['Cari', 'm', 22], ['Tür', 'm', 10], ['Bakiye', 'tl', 13, 'top bar'], ['0-30 Gün', 'tl', 12, 'top'], ['31-60 Gün', 'tl', 12, 'top'], ['61-90 Gün', 'tl', 12, 'top'], ['90+ Gün', 'tl', 12, 'top'], ['En Eski (gün)', 's', 10, 'esik:60'], ['Son Tahsilat', 't', 11], ['Son Satış', 't', 11]], $sat, $bos);
    }
}

// ---------- Raporlar ----------
if (!function_exists('_fnrRapor')) {
    function _fnrRapor($anahtar, $sid, array $dn)
    {
        $d = _fnrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit']; $obas = $dn['obas']; $obit = $dn['obit']; $ay = _fnrAyar($sid);
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $bosSatis = !$d['satis'] ? _fnrBos('satis_yok', $dn) : _fnrBos('satis', $dn);
        $bosGider = !$d['gider'] ? _fnrBos('gider_yok', $dn) : _fnrBos('gider', $dn);
        $bosKasa = !$d['kasa'] ? _fnrBos('kasa_yok', $dn) : _fnrBos('kasa', $dn);
        $bosOdeme = !$d['odeme'] ? _fnrBos('satis_yok', $dn) : _fnrBos('odeme', $dn);
        $yuz = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : null;
        $blokGiderPie = fn ($g, $renk) => _srBlok('Kategoriye göre giderler', 'pie', $renk, [['Kategori', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [_fnrGiderAd($k), $v, ''], array_keys(_fnrGiderKat($g)), array_values(_fnrGiderKat($g))));
        $blokOdemePie = fn ($od, $renk) => _srBlok('Ödeme tiplerine göre tahsilat', 'pie', $renk, [['Ödeme', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], _fnrOdemeOzet($od)));
        $plKpi = fn ($p, $o) => [_srKpi('Ciro (KDV dahil)', $p['ciro'], 'tl', '2563EB'), $kpiDeg('Ciro, önceki döneme göre', _srYuzde($p['ciro'], $o['ciro']), '1D4ED8'), _srKpi('Net satış', $p['net'], 'tl', '1E40AF'),
            _srKpi('Brüt kâr', $p['brut_kar'], 'tl', '059669'), _srKpi('Net kâr (tahmini)', $p['net_kar'], 'tl', $p['net_kar'] < 0 ? 'DC2626' : '047857'), $kpiY('Net kâr marjı', $p['marj'], '065F46'),
            $kpiY('Gıda maliyeti %', $p['fc'], $p['fc'] !== null && $p['fc'] > $ay['hedef_fc'] ? 'DC2626' : '0F766E'), $kpiY('İşçilik %', $p['isc_y'], $p['isc_y'] !== null && $p['isc_y'] > $ay['hedef_isc'] ? 'DC2626' : '0D9488'),
            $kpiY('Ana maliyet % (gıda + işçilik)', $p['prime'], $p['prime'] !== null && $p['prime'] > $ay['hedef_prime'] ? 'DC2626' : '115E59'), _srKpi('Adisyon', $p['adisyon'], 'sayi', '0EA5E9'), $kpiTl('Ortalama hesap', $p['ort'], '0284C7'), $kpiTl('Kişi başı', $p['kisi_basi'], '0369A1')];

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $obas, $obit); $od = _fnrOdeme($sid, $bas, $bit); $ko = _fnrKasaOturum($sid, $bas, $bit); $g = _fnrGider($sid, $bas, $bit); $ip = _fnrIptal($sid, $bas, $bit);
                $ol = array_values(array_filter(_fnrOlaylar($sid, 7), fn ($e) => $e['kalan'] <= 7)); $saat = []; foreach (_fnrSatis($sid, $bas, $bit) as $a) $saat[$a['saat']] = ($saat[$a['saat']] ?? 0) + $a['toplam']; ksort($saat);
                $nakit = array_sum(array_map(fn ($x) => $x['tip'] === 'nakit' ? $x['tutar'] : 0, $od)); $fark = array_sum(array_column($ko, 'fark'));
                return [
                    $pano([_srKpi('Ciro (KDV dahil)', $p['ciro'], 'tl', '4F46E5'), $kpiDeg('Önceki güne göre', _srYuzde($p['ciro'], $o['ciro']), '4338CA'), _srKpi('Adisyon', $p['adisyon'], 'sayi', '3730A3'), $kpiTl('Ortalama hesap', $p['ort'], '312E81'),
                        _srKpi('Nakit tahsilat', $nakit, 'tl', '0EA5E9'), _srKpi('Kasa sayım farkı', count($ko) ? $fark : 'Kapanış yok', count($ko) ? 'tl' : 'metin', $fark < 0 ? 'DC2626' : '059669'), _srKpi('Gider', array_sum(array_column($g, 'tutar')), 'tl', 'F59E0B'),
                        _srKpi('İptal + ikram + indirim', array_sum(array_column($ip, 'tutar')), 'tl', 'DC2626'), $kpiY('Gıda maliyeti %', $p['fc'], '0F766E'), _srKpi('Tahmini net kâr', $p['net_kar'], 'tl', $p['net_kar'] < 0 ? 'DC2626' : '059669'),
                        _srKpi('7 gün içinde ödenecek', array_sum(array_map(fn ($e) => $e['yon'] < 0 ? $e['tutar'] : 0, $ol)), 'tl', 'B91C1C')],
                        [_srBlok('Saatlere göre ciro', 'col', '4F46E5', [['Saat', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($h, $v) => [sprintf('%02d:00', $h), round($v, 2), ''], array_keys($saat), array_values($saat))), $blokOdemePie($od, '0EA5E9')],
                        !$p['adisyon'] && !$od && !$g ? $bosSatis : null),
                    _fnrSayfaOdeme($od, $bosOdeme),
                    _fnrSayfaKasa($ko, $bosKasa),
                    _fnrSayfaGiderListe('Giderler', 'Bu güne girilen giderler.', $g, $bosGider),
                    _srSayfa('İptal / İkram / İndirim', '', [['Tür', 'm', 18], ['Sebep', 'm', 28], ['Personel', 'm', 18], ['Tutar', 'tl', 12, 'top bar']], array_map(fn ($x) => [_fnrIptalTipAd($x['tip']), $x['sebep'], $x['kisi'], $x['tutar']], $ip), _fnrBos('iptal', $dn)),
                    _fnrSayfaOlay('Önümüzdeki 7 Günün Ödemeleri', $ol, _fnrBos('olay', $dn)),
                ];
            case 'haftalik':
            case 'aylik':
                $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $obas, $obit); $od = _fnrOdeme($sid, $bas, $bit); $g = _fnrGider($sid, $bas, $bit); $go = _fnrGider($sid, $obas, $obit); $ko = _fnrKasaOturum($sid, $bas, $bit); $gt = _fnrGunTablo($sid, $bas, $bit);
                $kpi = array_merge($plKpi($p, $o), [_srKpi('Gider (defter toplamı)', $p['gider_top'], 'tl', 'F59E0B'), _srKpi('Kasa sayım farkı', count($ko) ? array_sum(array_column($ko, 'fark')) : 'Kapanış yok', count($ko) ? 'tl' : 'metin', 'DC2626')]);
                $bloklar = [_srBlok('Günlere göre ciro ve toplam maliyet', 'col', '4F46E5', [['Gün', 'm'], ['Ciro', 'tl'], ['Maliyet + gider', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], round($x[5] + $x[6] + $x[7] + $x[8], 2)], $gt)), $blokGiderPie($g, 'F59E0B'), $blokOdemePie($od, '0EA5E9')];
                $sayfalar = [_fnrSayfaPl($p, $o, $bosSatis), _fnrSayfaGun($gt, $bosSatis), _fnrSayfaOdeme($od, $bosOdeme), _fnrSayfaGiderKat(_fnrGiderKatSat($g, $go, $p['ciro']), $bosGider), _fnrSayfaKasa($ko, $bosKasa)];
                if ($anahtar === 'aylik') {
                    $tb = _fnrTedBorc($sid); $cari = _fnrCari($sid); $nt = _fnrNakitTahmin($sid); $kdv = _fnrKdv($sid, $bas, $bit);
                    $kpi = array_merge($kpi, [_srKpi('Ödenecek KDV (tahmini)', array_sum(array_column($kdv, 'oden')), 'tl', 'E11D48'), _srKpi('Tedarikçi borcu', $tb['toplam'], 'tl', '6D28D9'), _srKpi('Gecikmiş tedarikçi borcu', $tb['gecikmis'], 'tl', 'DC2626'),
                        _srKpi('Müşteri alacağı', array_sum(array_map(fn ($x) => max(0, $x['bakiye']), $cari)), 'tl', '7C3AED'), _srKpi('Nakit (banka + kasa)', $nt['baslangic'], 'tl', '0D9488'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _fnrSayfaKdv($kdv, $bosSatis);
                    $sayfalar[] = _fnrButceSayfa($sid, $dn, $p);
                    $sayfalar[] = _fnrSayfaTedBorc($tb, !$d['alis'] ? _fnrBos('alis_yok', $dn) : _fnrBos('ted_borc', $dn));
                    $sayfalar[] = _fnrSayfaCari(_fnrCariSat($cari), !$d['cari'] ? _fnrBos('cari_yok', $dn) : _fnrBos('cari', $dn));
                    $sayfalar[] = _fnrNakitTahminSayfa($nt);
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$d['satis'] && !$d['gider'] ? $bosSatis : null)], $sayfalar);

            // ---------------- KÂRLILIK ----------------
            case 'kar_zarar':
                $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $obas, $obit);
                $sel = [['SMM', $p['smm']], ['İşçilik', $p['iscilik']], ['Komisyon', $p['komisyon']]]; foreach ($p['isletme'] as $k => $v) $sel[] = [_fnrGiderAd($k), $v]; $sel[] = ['Amortisman', $p['amort']]; if ($p['net_kar'] > 0) $sel[] = ['Net kâr', $p['net_kar']];
                $not = []; if (!$d['recete']) $not[] = _fnrBos('recete_yok', $dn); if (!$d['maas'] && $p['isc_kaynak'] !== 'maas') $not[] = _fnrBos('maas_yok', $dn); if (!$d['gider']) $not[] = _fnrBos('gider_yok', $dn);
                return [
                    $pano($plKpi($p, $o), [_srBlok('Net satışın dağılımı', 'pie', '059669', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], round($x[1], 2), ''], array_filter($sel, fn ($x) => $x[1] > 0))),
                        _srBlok('Bu dönem ve önceki dönem', 'col', '047857', [['Kalem', 'm'], ['Bu dönem', 'tl'], ['Önceki', 'tl']], array_values(array_filter([['Net satış', $p['net'], $o['net']], ['Brüt kâr', $p['brut_kar'], $o['brut_kar']], ['FAVÖK', $p['favok'], $o['favok']], ['Net kâr', $p['net_kar'], $o['net_kar']]], fn ($x) => $x[1] >= 0 && $x[2] >= 0)))],
                        !$p['adisyon'] ? $bosSatis : ($not ? implode(' ', $not) : null)),
                    _fnrSayfaPl($p, $o, $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('İşletme Giderleri', 'Gelir tablosuna giren giderler (malzeme ve maaş hariç).', [['Tarih', 't', 11], ['Kategori', 'm', 24], ['Açıklama', 'm', 40], ['Tutar', 'tl', 13, 'top bar']],
                        array_map(fn ($x) => [$x['tarih'], _fnrGiderAd($x['kat']), $x['aciklama'] ?: '—', $x['tutar']], array_values(array_filter(_fnrGider($sid, $bas, $bit), fn ($x) => !in_array($x['kat'], ['malzeme', 'maas'], true)))), $bosGider),
                ];
            case 'prime_cost':
                $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $obas, $obit); $gt = _fnrGunTablo($sid, $bas, $bit); $oran = _fnrKdvOran($sid);
                $ur = []; foreach (_fnrSmm($sid, $bas, $bit)['urun'] as $ad => $u) { $n = $u['satis'] / (1 + $oran / 100); $ur[] = [$ad, round($u['adet'], 1), round($u['satis'], 2), round($u['maliyet'], 2), $n > 0 ? round($u['maliyet'] / $n * 100, 1) : null, $u['tahmini'] ? 'Reçete / maliyet yok (%30 tahmini)' : 'Reçeteden']; }
                usort($ur, fn ($a, $b) => $b[3] <=> $a[3]);
                $gp = array_map(fn ($x) => [$x[0], _fnrGunAd(date('N', strtotime($x[0]))), $x[2], $x[5], $x[6], round($x[5] + $x[6], 2), ($n = $x[2] / (1 + $oran / 100)) > 0 ? round(($x[5] + $x[6]) / $n * 100, 1) : null], $gt);
                return [
                    $pano([$kpiY('Ana maliyet %', $p['prime'], $p['prime'] !== null && $p['prime'] > $ay['hedef_prime'] ? 'DC2626' : '047857'), _srKpi('Hedef', '%' . number_format($ay['hedef_prime'], 1, ',', '.'), 'metin', '64748B'), $kpiY('Önceki dönem', $o['prime'], '065F46'),
                        _srKpi('Gıda maliyeti (SMM)', $p['smm'], 'tl', '0F766E'), $kpiY('Gıda %', $p['fc'], '0D9488'), _srKpi('Gıda hedefi', '%' . number_format($ay['hedef_fc'], 1, ',', '.'), 'metin', '64748B'),
                        _srKpi('İşçilik maliyeti', $p['iscilik'], 'tl', '115E59'), $kpiY('İşçilik %', $p['isc_y'], '134E4A'), _srKpi('İşçilik hedefi', '%' . number_format($ay['hedef_isc'], 1, ',', '.'), 'metin', '64748B'),
                        _srKpi('SMM\'nin tahmini kısmı', $p['smm_tahmini'], 'tl', 'D97706')],
                        [_srBlok('Günlere göre ana maliyet %', 'col', '047857', [['Gün', 'm'], ['Ana maliyet %', 'y'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[6], ''], array_filter($gp, fn ($x) => $x[6] !== null))),
                         _srBlok('Ana maliyetin bileşenleri', 'pie', '0F766E', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], array_filter([['Gıda (SMM)', $p['smm'], ''], ['İşçilik', $p['iscilik'], '']], fn ($x) => $x[1] > 0))],
                        !$p['adisyon'] ? $bosSatis : (!$d['recete'] ? _fnrBos('recete_yok', $dn) : (!$d['maas'] && !$p['iscilik'] ? _fnrBos('maas_yok', $dn) : null))),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Ana Maliyet', 'Ana maliyet % = (SMM + işçilik) ÷ net satış (KDV hariç). Restoranlarda genelde %60–65 hedeflenir.', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Ciro', 'tl', 13, 'top'], ['SMM', 'tl', 12, 'top'], ['İşçilik', 'tl', 12, 'top'], ['Ana Maliyet', 'tl', 13, 'top bar'], ['Ana Maliyet %', 'y', 11, 'esik:' . (int) $ay['hedef_prime']]], $gp, $bosSatis),
                    _srSayfa('Ürün Maliyetleri', 'Ürünün dönemdeki toplam maliyeti ve maliyetin net satışa oranı (gıda maliyeti %).', [['Ürün', 'm', 26], ['Adet', 's', 8, 'top'], ['Satış (KDV dahil)', 'tl', 14, 'top'], ['Maliyet', 'tl', 13, 'top bar'], ['Gıda %', 'y', 9, 'esik:' . (int) max(35, $ay['hedef_fc'] + 5)], ['Maliyet Kaynağı', 'm', 26]], array_slice($ur, 0, 300), $bosSatis),
                ];
            case 'gun_kar':
                $gt = _fnrGunTablo($sid, $bas, $bit); $p = _fnrPl($sid, $bas, $bit); $ga = [];
                foreach ($gt as $x) { $n = $x[1]; $ga[$n]['c'] = ($ga[$n]['c'] ?? 0) + $x[2]; $ga[$n]['k'] = ($ga[$n]['k'] ?? 0) + $x[9]; $ga[$n]['g'] = ($ga[$n]['g'] ?? 0) + 1; }
                $gas = []; foreach ([1, 2, 3, 4, 5, 6, 7] as $i) { $n = _fnrGunAd($i); if (!isset($ga[$n])) continue; $gas[] = [$n, $ga[$n]['g'], round($ga[$n]['c'] / $ga[$n]['g'], 2), round($ga[$n]['k'] / $ga[$n]['g'], 2)]; }
                $zarar = count(array_filter($gt, fn ($x) => $x[2] > 0 && $x[9] < 0)); $iyi = $gt ? array_reduce($gt, fn ($c, $x) => !$c || $x[9] > $c[9] ? $x : $c) : null;
                return [
                    $pano([_srKpi('Dönem tahmini kârı', array_sum(array_column($gt, 9)), 'tl', '0F766E'), _srKpi('Günlük ortalama kâr', $gt ? round(array_sum(array_column($gt, 9)) / count($gt), 2) : 0, 'tl', '115E59'), _srKpi('Zararlı gün', $zarar, 'sayi', 'DC2626'),
                        _srKpi('En kârlı gün', $iyi && $iyi[9] > 0 ? date('d.m.Y', strtotime($iyi[0])) . ' · ' . number_format($iyi[9], 0, ',', '.') . ' TL' : '—', 'metin', '059669'), _srKpi('Satış olan gün', count(array_filter($gt, fn ($x) => $x[2] > 0)), 'sayi', '64748B')],
                        [_srBlok('Günlere göre ciro ve toplam maliyet', 'col', '0F766E', [['Gün', 'm'], ['Ciro', 'tl'], ['Maliyet + gider', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], round($x[5] + $x[6] + $x[7] + $x[8], 2)], $gt))], !$p['adisyon'] ? $bosSatis : null),
                    _fnrSayfaGun($gt, $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('Haftanın Günleri', 'Ortalamalar o günün dönemdeki tekrar sayısına bölünerek bulunur.', [['Gün', 'm', 12], ['Gün Sayısı', 's', 9], ['Ortalama Ciro', 'tl', 14, 'bar'], ['Ortalama Kâr', 'tl', 14]], $gas, $bosSatis),
                ];
            case 'basabas':
                $p = _fnrPl($sid, $bas, $bit); $gun = max(1, $p['gun']); $sabitAy = _fnrSabitAylik($sid);
                $maasAy = 0.0; try { if (_srTablo('personeller')) $maasAy = (float) DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->where('rol', '!=', 'sahip')->where(fn ($w) => $w->whereNull('maas_tipi')->orWhere('maas_tipi', 'aylik'))->sum('maas'); } catch (\Throwable $e) {}
                $carp = 1.0; try { if (function_exists('_ikrAyar')) $carp = (float) _ikrAyar($sid)['carpan']; } catch (\Throwable $e) {}
                $maasAy = round($maasAy * $carp, 2); $amAy = _fnrAmortDonem($sid, 30);
                $digIsl = $sabitAy > 0 ? 0 : round($p['isletme_top'] / $gun * 30, 2); // sabit gider tanımı yoksa dönemin işletme giderleri aylığa çevrilir
                $sabitTop = round($sabitAy + $maasAy + $amAy + $digIsl, 2);
                $degisken = $p['net'] > 0 ? ($p['smm'] + $p['komisyon'] + max(0, $p['iscilik'] - $maasAy * $gun / 30)) / $p['net'] : null; $km = $degisken !== null ? 1 - $degisken : null;
                $bbNet = $km && $km > 0 ? $sabitTop / $km : null; $bbCiro = $bbNet !== null ? round($bbNet * (1 + $p['kdv_oran'] / 100), 2) : null; $aylikCiro = round($p['ciro'] / $gun * 30, 2);
                $guv = $bbCiro && $aylikCiro > 0 ? round(($aylikCiro - $bbCiro) / $aylikCiro * 100, 1) : null;
                $sat = [['Tekrarlayan (sabit) giderler', $sabitAy, $sabitAy > 0 ? 'Tekrarlayan Giderler ekranındaki aktif tanımlar (yıllık olanlar ÷ 12)' : 'Tanım yok'], ['Aylık maaşlar (× işveren çarpanı)', $maasAy, 'Personel kartlarındaki aylık maaş toplamı'],
                    ['Amortisman (aylık)', $amAy, 'Demirbaşlardan'], ['Diğer işletme giderleri (aylığa çevrilmiş)', $digIsl, $digIsl > 0 ? 'Sabit gider tanımı olmadığı için dönemin işletme giderleri kullanıldı' : 'Sabit gider tanımı olduğu için eklenmedi']];
                return [
                    $pano([_srKpi('Aylık sabit gider', $sabitTop, 'tl', '115E59'), $kpiY('Katkı marjı', $km !== null ? round($km * 100, 1) : null, '0F766E'), $kpiTl('Başabaş cirosu (aylık, KDV dahil)', $bbCiro, '047857'),
                        $kpiTl('Başabaş cirosu (günlük)', $bbCiro !== null ? round($bbCiro / 30, 2) : null, '059669'), _srKpi('Gerçekleşen ciro (aylığa çevrilmiş)', $aylikCiro, 'tl', '2563EB'),
                        $kpiY('Güvenlik payı', $guv, $guv !== null && $guv < 0 ? 'DC2626' : '059669'), _srKpi('Durum', $guv === null ? 'Hesaplanamadı' : ($guv < 0 ? 'Başabaşın altında' : ($guv < 10 ? 'Sınırda' : 'Başabaşın üstünde')), 'metin', $guv !== null && $guv < 10 ? 'DC2626' : '059669')],
                        [_srBlok('Gerekli ve gerçekleşen aylık ciro', 'bar', '0F766E', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], $bbCiro !== null ? [['Başabaş cirosu', $bbCiro, ''], ['Gerçekleşen (aylık)', $aylikCiro, '']] : []),
                         _srBlok('Sabit giderin bileşenleri', 'pie', '115E59', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($sat, fn ($x) => $x[1] > 0)))],
                        !$p['adisyon'] ? $bosSatis : (!$d['sabit'] ? _fnrBos('sabit_yok', $dn) : null)),
                    ['grafiksiz' => true] + _srSayfa('Sabit Gider Hesabı', 'Başabaş cirosu = aylık sabit gider ÷ katkı marjı. Katkı marjı = 1 − (SMM + komisyon + saatlik / günlük işçilik) ÷ net satış. Güvenlik payı = (gerçekleşen − başabaş) ÷ gerçekleşen.',
                        [['Kalem', 'm', 40], ['Aylık Tutar', 'tl', 14, 'top bar'], ['Kaynak', 'm', 50]], $sat, _fnrBos('sabit_yok', $dn)),
                ];

            // ---------------- GELİR ----------------
            case 'ciro':
                $s = _fnrSatis($sid, $bas, $bit); $c = _fnrCiro($sid, $bas, $bit); $co = _fnrCiro($sid, $obas, $obit);
                $gun = []; foreach (_fnrGunler($bas, $bit) as $t) $gun[$t] = ['c' => 0, 'n' => 0]; foreach ($s as $a) { $gun[$a['tarih']]['c'] += $a['toplam']; $gun[$a['tarih']]['n']++; }
                $ot = []; foreach (_fnrSatis($sid, $obas, $obit) as $a) $ot[] = $a['toplam'];
                $gs = []; $i = 0; $ogun = _fnrGunler($obas, $obit); $oc = []; foreach (_fnrSatis($sid, $obas, $obit) as $a) $oc[$a['tarih']] = ($oc[$a['tarih']] ?? 0) + $a['toplam'];
                foreach ($gun as $t => $v) { $kar = $ogun[$i] ?? null; $gs[] = [$t, _fnrGunAd(date('N', strtotime($t))), round($v['c'], 2), $v['n'], $v['n'] ? round($v['c'] / $v['n'], 2) : null, $kar ? round($oc[$kar] ?? 0, 2) : null, $kar ? _srYuzde($v['c'], $oc[$kar] ?? 0) : null]; $i++; }
                $hg = []; foreach ($s as $a) { $n = $a['gun']; $hg[$n]['c'] = ($hg[$n]['c'] ?? 0) + $a['toplam']; $hg[$n]['n'] = ($hg[$n]['n'] ?? 0) + 1; } ksort($hg);
                $gSay = []; foreach (array_keys($gun) as $t) { $n = (int) date('N', strtotime($t)); $gSay[$n] = ($gSay[$n] ?? 0) + 1; }
                $saat = []; foreach ($s as $a) $saat[$a['saat']] = ($saat[$a['saat']] ?? 0) + $a['toplam']; ksort($saat);
                $trend = []; for ($m = 11; $m >= 0; $m--) { $mb = date('Y-m-01', strtotime("first day of -$m month")); $me = min(date('Y-m-t', strtotime($mb)), date('Y-m-d')); $trend[] = [_fnrAyAd(substr($mb, 0, 7)), _fnrCiro($sid, $mb, $me), '']; }
                $enIyi = $gs ? array_reduce($gs, fn ($c, $x) => !$c || $x[2] > $c[2] ? $x : $c) : null;
                return [
                    $pano([_srKpi('Ciro (KDV dahil)', $c, 'tl', '2563EB'), _srKpi('Önceki dönem', $co, 'tl', '1D4ED8'), $kpiDeg('Değişim', _srYuzde($c, $co), '1E40AF'), _srKpi('Adisyon', count($s), 'sayi', '1E3A8A'),
                        _srKpi('Günlük ortalama ciro', $dn['gun'] ? round($c / $dn['gun'], 2) : 0, 'tl', '0EA5E9'), _srKpi('En yüksek gün', $enIyi && $enIyi[2] > 0 ? date('d.m.Y', strtotime($enIyi[0])) . ' · ' . number_format($enIyi[2], 0, ',', '.') . ' TL' : '—', 'metin', '0284C7')],
                        [_srBlok('Son 12 ay ciro', 'col', '2563EB', [['Ay', 'm'], ['Ciro', 'tl'], ['', 'm']], array_filter($trend, fn ($x) => $x[1] > 0) ? $trend : []),
                         _srBlok('Saatlere göre ciro', 'col', '1D4ED8', [['Saat', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($h, $v) => [sprintf('%02d:00', $h), round($v, 2), ''], array_keys($saat), array_values($saat)))], !$s ? $bosSatis : null),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Ciro', 'Önceki dönem: aynı uzunluktaki bir önceki dönemin karşılık gelen günü.', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Ciro', 'tl', 13, 'top bar'], ['Adisyon', 's', 8, 'top'], ['Ort. Hesap', 'tl', 11], ['Önceki Dönem', 'tl', 13, 'top'], ['Değişim %', 'y', 9]], $gs, $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('Haftanın Günleri', 'Gün başına ortalama = o günün toplam cirosu ÷ dönemdeki o gün sayısı.', [['Gün', 'm', 12], ['Ciro', 'tl', 13, 'top'], ['Adisyon', 's', 8, 'top'], ['Gün Başına Ortalama', 'tl', 15, 'bar'], ['Ort. Hesap', 'tl', 11]],
                        array_map(fn ($n, $v) => [_fnrGunAd($n), round($v['c'], 2), $v['n'], round($v['c'] / max(1, $gSay[$n] ?? 1), 2), round($v['c'] / max(1, $v['n']), 2)], array_keys($hg), array_values($hg)), $bosSatis),
                    _srSayfa('Aylık Ciro (12 Ay)', 'Bu ay bugüne kadar.', [['Ay', 'm', 12], ['Ciro', 'tl', 14, 'bar']], array_map(fn ($x) => [$x[0], $x[1]], $trend), $bosSatis),
                ];
            case 'ort_hesap':
                $s = _fnrSatis($sid, $bas, $bit); $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $obas, $obit);
                $grp = function ($fn) use ($s) { $g = []; foreach ($s as $a) { $k = $fn($a); $g[$k]['c'] = ($g[$k]['c'] ?? 0) + $a['toplam']; $g[$k]['n'] = ($g[$k]['n'] ?? 0) + 1; $g[$k]['m'] = ($g[$k]['m'] ?? 0) + $a['misafir']; $g[$k]['s'][] = $a['sure']; } return $g; };
                $sat = fn ($g) => array_map(fn ($k, $v) => [$k, $v['n'], round($v['c'], 2), round($v['c'] / max(1, $v['n']), 2), $v['m'], $v['m'] > 0 ? round($v['c'] / $v['m'], 2) : null, ($ss = array_filter($v['s'], fn ($x) => $x !== null)) ? round(array_sum($ss) / count($ss)) : null], array_keys($g), array_values($g));
                $kanal = $grp(fn ($a) => $a['platform'] !== '' ? 'Online · ' . mb_convert_case($a['platform'], MB_CASE_TITLE, 'UTF-8') : _fnrKanalAd($a['kanal'])); uasort($kanal, fn ($a, $b) => $b['c'] <=> $a['c']);
                $gunG = $grp(fn ($a) => $a['gun']); ksort($gunG); $gunG = array_combine(array_map('_fnrGunAd', array_keys($gunG)), array_values($gunG)) ?: [];
                $dil = $grp(fn ($a) => $a['saat'] < 11 ? '1 · Kahvaltı (–11:00)' : ($a['saat'] < 16 ? '2 · Öğle (11–16)' : ($a['saat'] < 22 ? '3 · Akşam (16–22)' : '4 · Gece (22+)'))); ksort($dil);
                $bk = ['0–250 TL' => 0, '250–500 TL' => 0, '500–1.000 TL' => 0, '1.000–2.000 TL' => 0, '2.000–5.000 TL' => 0, '5.000 TL +' => 0];
                foreach ($s as $a) { $t = $a['toplam']; $bk[$t < 250 ? '0–250 TL' : ($t < 500 ? '250–500 TL' : ($t < 1000 ? '500–1.000 TL' : ($t < 2000 ? '1.000–2.000 TL' : ($t < 5000 ? '2.000–5.000 TL' : '5.000 TL +'))))]++; }
                $kol = [['Grup', 'm', 22], ['Adisyon', 's', 8, 'top'], ['Ciro', 'tl', 13, 'top'], ['Ort. Hesap', 'tl', 11, 'bar'], ['Misafir', 's', 8, 'top'], ['Kişi Başı', 'tl', 10], ['Ort. Süre (dk)', 's', 10]];
                return [
                    $pano([$kpiTl('Ortalama hesap', $p['ort'], '1D4ED8'), $kpiTl('Önceki dönem', $o['ort'], '1E40AF'), $kpiDeg('Değişim', $p['ort'] && $o['ort'] ? _srYuzde($p['ort'], $o['ort']) : null, '1E3A8A'),
                        _srKpi('Misafir (kuver)', $p['misafir'], 'sayi', '2563EB'), $kpiTl('Kişi başı harcama', $p['kisi_basi'], '0EA5E9'), $kpiTl('Önceki dönem kişi başı', $o['kisi_basi'], '0284C7'), _srKpi('Adisyon', $p['adisyon'], 'sayi', '64748B')],
                        [_srBlok('Hesap büyüklüğü dağılımı', 'col', '1D4ED8', [['Tutar aralığı', 'm'], ['Adisyon', 's'], ['', 'm']], $s ? array_map(fn ($k, $v) => [$k, $v, ''], array_keys($bk), array_values($bk)) : []),
                         _srBlok('Kanala göre ortalama hesap', 'bar', '2563EB', [['Kanal', 'm'], ['Ort. hesap', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], $sat($kanal)))], !$s ? $bosSatis : (!$p['misafir'] ? 'Misafir sayısı girilmemiş: kişi başı harcama POS\'ta masa açılırken girilen misafir (kuver) sayısından hesaplanır.' : null)),
                    _srSayfa('Kanala Göre', 'Süre: adisyon açılışından kapanışına (masa devir süresi göstergesi).', $kol, $sat($kanal), $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('Haftanın Günlerine Göre', '', $kol, $sat($gunG), $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('Öğünlere Göre', 'Kapanış saatine göre.', $kol, $sat($dil), $bosSatis),
                ];
            case 'kanal':
                $s = _fnrSatis($sid, $bas, $bit); $km = _fnrKomisyon($sid, $bas, $bit); $c = _fnrCiro($sid, $bas, $bit);
                $kn = []; foreach ($s as $a) { $k = $a['platform'] !== '' ? 'Online platform' : _fnrKanalAd($a['kanal']); $kn[$k]['c'] = ($kn[$k]['c'] ?? 0) + $a['toplam']; $kn[$k]['n'] = ($kn[$k]['n'] ?? 0) + 1; } uasort($kn, fn ($a, $b) => $b['c'] <=> $a['c']);
                $pl = array_map(fn ($ad, $x) => [$ad, $x['adet'], round($x['ciro'], 2), round($x['ciro'] / $x['adet'], 2), $x['oran'], round($x['kom'], 2), round($x['ciro'] - $x['kom'], 2)], array_keys($km['liste']), array_values($km['liste']));
                $bosOn = !$d['online'] ? _fnrBos('online_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') online platform siparişi yok.';
                return [
                    $pano([_srKpi('Toplam ciro', $c, 'tl', '1E40AF'), _srKpi('Online platform cirosu', $km['ciro'], 'tl', '2563EB'), $kpiY('Online payı', $yuz($km['ciro'], $c), '1D4ED8'), _srKpi('Tahmini komisyon', $km['toplam'], 'tl', 'DC2626'),
                        $kpiY('Komisyonun online ciroya oranı', $yuz($km['toplam'], $km['ciro']), 'B91C1C'), _srKpi('Komisyon sonrası online net', round($km['ciro'] - $km['toplam'], 2), 'tl', '059669')],
                        [_srBlok('Kanallara göre ciro', 'pie', '1E40AF', [['Kanal', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v['c'], 2), ''], array_keys($kn), array_values($kn))),
                         _srBlok('Platformlara göre ciro ve komisyon', 'bar', 'DC2626', [['Platform', 'm'], ['Ciro', 'tl'], ['Komisyon', 'tl']], array_map(fn ($x) => [$x[0], $x[2], $x[5]], $pl))], !$s ? $bosSatis : null),
                    _srSayfa('Kanallar', '', [['Kanal', 'm', 22], ['Adisyon', 's', 8, 'top'], ['Ciro', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Ort. Hesap', 'tl', 11]], array_map(fn ($k, $v) => [$k, $v['n'], round($v['c'], 2), $yuz($v['c'], $c), round($v['c'] / $v['n'], 2)], array_keys($kn), array_values($kn)), $bosSatis),
                    _srSayfa('Online Platformlar', 'Komisyon oranları "Hedef & bütçe ayarları"ndan değiştirilebilir (anlaşmanızdaki oranı girin); hesap KDV dahil sipariş tutarı üzerinden tahminidir, platform hak ediş raporuyla kontrol edin.',
                        [['Platform', 'm', 20], ['Sipariş', 's', 8, 'top'], ['Ciro', 'tl', 13, 'top'], ['Sipariş Başı', 'tl', 11], ['Komisyon %', 'y', 10], ['Komisyon', 'tl', 12, 'top bar'], ['Net Gelir', 'tl', 13, 'top']], $pl, $bosOn),
                ];

            // ---------------- TAHSİLAT & KASA ----------------
            case 'odeme_tipi':
                $od = _fnrOdeme($sid, $bas, $bit); $oz = _fnrOdemeOzet($od); $tip = array_values(array_unique(array_column($od, 'tip')));
                $gun = []; foreach ($od as $x) $gun[$x['tarih']][$x['tip']] = ($gun[$x['tarih']][$x['tip']] ?? 0) + $x['tutar']; ksort($gun);
                $gsat = array_map(fn ($t, $v) => array_merge([$t], array_map(fn ($k) => round($v[$k] ?? 0, 2), $tip), [round(array_sum($v), 2)]), array_keys($gun), array_values($gun));
                $top = array_sum(array_column($od, 'tutar')); $kart = array_sum(array_map(fn ($x) => in_array($x['tip'], ['kredi', 'kart', 'kart_kapida'], true) ? $x['tutar'] : 0, $od));
                return [
                    $pano([_srKpi('Toplam tahsilat', $top, 'tl', '0EA5E9'), _srKpi('Nakit', array_sum(array_map(fn ($x) => $x['tip'] === 'nakit' ? $x['tutar'] : 0, $od)), 'tl', '0284C7'), _srKpi('Kart', $kart, 'tl', '0369A1'),
                        _srKpi('Yemek kartı', array_sum(array_map(fn ($x) => $x['tip'] === 'yemek_karti' ? $x['tutar'] : 0, $od)), 'tl', '075985'), _srKpi('Açık hesap (veresiye)', array_sum(array_map(fn ($x) => $x['tip'] === 'acik_hesap' ? $x['tutar'] : 0, $od)), 'tl', 'D97706'),
                        $kpiY('Nakit payı', $top > 0 ? round(array_sum(array_map(fn ($x) => $x['tip'] === 'nakit' ? $x['tutar'] : 0, $od)) / $top * 100, 1) : null, '0C4A6E'), _srKpi('Bahşiş', array_sum(array_column($od, 'bahsis')), 'tl', '059669')],
                        [$blokOdemePie($od, '0EA5E9'), _srBlok('Günlere göre tahsilat', 'col', '0284C7', [['Gün', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), end($x), ''], $gsat))], !$od ? $bosOdeme : null),
                    _fnrSayfaOdeme($od, $bosOdeme),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Ödeme Tipleri', '', array_merge([['Tarih', 't', 11]], array_map(fn ($k) => [_fnrOdemeAd($k), 'tl', 13, 'top'], $tip), [['Toplam', 'tl', 14, 'top bar']]), $gsat, $bosOdeme),
                ];
            case 'kasa_fark':
                $ko = _fnrKasaOturum($sid, $bas, $bit); $acik = array_sum(array_map(fn ($x) => $x['fark'] < 0 ? $x['fark'] : 0, $ko)); $faz = array_sum(array_map(fn ($x) => $x['fark'] > 0 ? $x['fark'] : 0, $ko));
                $kis = []; foreach ($ko as $x) { $k = $x['kapatan'] . ' · ' . $x['kasa']; $kis[$k]['n'] = ($kis[$k]['n'] ?? 0) + 1; $kis[$k]['f'] = ($kis[$k]['f'] ?? 0) + $x['fark']; $kis[$k]['a'] = ($kis[$k]['a'] ?? 0) + ($x['fark'] < -0.009 ? 1 : 0); $kis[$k]['m'] = min($kis[$k]['m'] ?? 0, $x['fark']); }
                uasort($kis, fn ($a, $b) => $a['f'] <=> $b['f']);
                return [
                    $pano([_srKpi('Kapanış', count($ko), 'sayi', '0284C7'), _srKpi('Net fark', round($acik + $faz, 2), 'tl', $acik + $faz < 0 ? 'DC2626' : '059669'), _srKpi('Toplam açık', $acik, 'tl', 'DC2626'), _srKpi('Toplam fazla', $faz, 'tl', 'D97706'),
                        $kpiY('Tutan kapanış oranı', count($ko) ? round(count(array_filter($ko, fn ($x) => abs($x['fark']) < 1)) / count($ko) * 100, 1) : null, '059669'), _srKpi('En büyük açık', $ko ? min(0, min(array_column($ko, 'fark'))) : 0, 'tl', '991B1B')],
                        [_srBlok('Kapanışlara göre fark', 'col', 'DC2626', [['Kapanış', 'm'], ['Fark', 'tl'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x['tarih'])) . ' ' . $x['kasa'], $x['fark'], ''], $ko))], !$ko ? $bosKasa : null),
                    _fnrSayfaKasa($ko, $bosKasa),
                    _srSayfa('Kapatan Kişi & Kasa', 'Sürekli aynı kişide / kasada açık çıkıyorsa nakit kontrolünü sıklaştırın; fazla çıkması da kayıt dışı ödeme ya da girilmemiş satış işaretidir.', [['Kapatan · Kasa', 'm', 28], ['Kapanış', 's', 8, 'top'], ['Açık Veren', 's', 9, 'top esik:2'], ['Net Fark', 'tl', 12, 'top'], ['En Büyük Açık', 'tl', 12]],
                        array_map(fn ($k, $v) => [$k, $v['n'], $v['a'], round($v['f'], 2), round($v['m'], 2)], array_keys($kis), array_values($kis)), $bosKasa),
                ];
            case 'kasa_hareket':
                $h = _fnrKasaHareket($sid, $bas, $bit); $tp = []; foreach ($h as $x) { $k = $x['tip'] . '|' . $x['yon']; $tp[$k]['n'] = ($tp[$k]['n'] ?? 0) + 1; $tp[$k]['tl'] = ($tp[$k]['tl'] ?? 0) + $x['tutar']; } uasort($tp, fn ($a, $b) => $b['tl'] <=> $a['tl']);
                $gun = []; foreach ($h as $x) { $gun[$x['tarih']][$x['yon']] = ($gun[$x['tarih']][$x['yon']] ?? 0) + $x['tutar']; } ksort($gun);
                $man = array_values(array_filter($h, fn ($x) => in_array($x['tip'], ['al', 'koy'], true))); $bh = !$d['kasa_hareket'] ? _fnrBos('kasa_yok', $dn) : _fnrBos('kasa_hareket', $dn);
                $gir = array_sum(array_map(fn ($x) => $x['yon'] === 'giris' && $x['tip'] !== 'devir' ? $x['tutar'] : 0, $h)); $cik = array_sum(array_map(fn ($x) => $x['yon'] === 'cikis' ? $x['tutar'] : 0, $h));
                return [
                    $pano([_srKpi('Nakit giriş (devir hariç)', $gir, 'tl', '059669'), _srKpi('Nakit çıkış', $cik, 'tl', 'DC2626'), _srKpi('Net', round($gir - $cik, 2), 'tl', '0369A1'), _srKpi('Kasadan alınan (elle)', array_sum(array_map(fn ($x) => $x['tip'] === 'al' ? $x['tutar'] : 0, $h)), 'tl', 'B91C1C'),
                        _srKpi('Kasaya konulan (elle)', array_sum(array_map(fn ($x) => $x['tip'] === 'koy' ? $x['tutar'] : 0, $h)), 'tl', '0284C7'), _srKpi('Hareket', count($h), 'sayi', '64748B')],
                        [_srBlok('Türe göre kasa hareketleri', 'bar', '0369A1', [['Tür', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [_fnrKasaTipAd(explode('|', $k)[0]) . (explode('|', $k)[1] === 'giris' ? ' (+)' : ' (−)'), round($v['tl'], 2), ''], array_keys($tp), array_values($tp)))], !$h ? $bh : null),
                    _srSayfa('Türe Göre', '', [['Tür', 'm', 30], ['Yön', 'm', 8], ['Hareket', 's', 8, 'top'], ['Tutar', 'tl', 13, 'bar']], array_map(fn ($k, $v) => [_fnrKasaTipAd(explode('|', $k)[0]), explode('|', $k)[1] === 'giris' ? 'Giriş' : 'Çıkış', $v['n'], round($v['tl'], 2)], array_keys($tp), array_values($tp)), $bh),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Kasa', 'Giriş açılış devrini de içerir.', [['Tarih', 't', 11], ['Giriş', 'tl', 13, 'top'], ['Çıkış', 'tl', 13, 'top'], ['Net', 'tl', 13, 'top']], array_map(fn ($t, $v) => [$t, round($v['giris'] ?? 0, 2), round($v['cikis'] ?? 0, 2), round(($v['giris'] ?? 0) - ($v['cikis'] ?? 0), 2)], array_keys($gun), array_values($gun)), $bh),
                    _srSayfa('Elle Alınan / Konulan Paralar', 'Satış, gider ya da personel ödemesine bağlı olmayan, elle yapılan kasa hareketleri: açıklaması boş ya da sık olanları sorgulayın.', [['Tarih', 't', 11], ['Saat', 'm', 7], ['Tür', 'm', 20], ['Açıklama', 'm', 34], ['Personel', 'm', 16], ['Tutar', 'tl', 12, 'top bar']],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _fnrKasaTipAd($x['tip']), $x['aciklama'] ?: '—', $x['kisi'], $x['tutar']], $man), 'Seçili dönemde kasadan elle para alınmamış / konulmamış.'),
                ];
            case 'acik_tahsilat':
                $acik = []; $paket = [];
                if (_srTablo('adisyonlar')) {
                    try { foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'acik')->where('created_at', '<', date('Y-m-d') . ' 00:00:00')->orderBy('created_at')->limit(300)->get() as $a)
                        $acik[] = [substr((string) $a->created_at, 0, 10), _fnrKanalAd($a->kanal ?? ''), _fnrPersonelAd($a->acan_personel_id ?? null), (float) $a->toplam, (int) floor((time() - strtotime((string) $a->created_at)) / 86400)]; } catch (\Throwable $e) {}
                    if (_srKolon('adisyonlar', 'tahsilat_durumu')) { try { foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('tahsilat_durumu', 'kuryede')->where('durum', '!=', 'odendi')->orderBy('created_at')->limit(300)->get() as $a)
                        $paket[] = [substr((string) $a->created_at, 0, 10), (string) ($a->platform ?: 'Paket'), _fnrPersonelAd($a->kurye_id ?? null), (float) $a->toplam, (int) floor((time() - strtotime((string) $a->created_at)) / 3600)]; } catch (\Throwable $e) {} }
                }
                $ver = array_values(array_filter(_fnrOdeme($sid, $bas, $bit), fn ($x) => $x['tip'] === 'acik_hesap')); $cd = _fnrCariDonem($sid, $bas, $bit);
                return [
                    $pano([_srKpi('Dünden açık kalan adisyon', count($acik), 'sayi', 'DC2626'), _srKpi('Açık adisyon tutarı', array_sum(array_column($acik, 3)), 'tl', 'B91C1C'), _srKpi('Parası kuryede', array_sum(array_column($paket, 3)), 'tl', 'D97706'),
                        _srKpi('Dönemde veresiye satış', array_sum(array_column($ver, 'tutar')), 'tl', '7C3AED'), _srKpi('Dönemde cari tahsilat', $cd['tahsilat'], 'tl', '059669'), _srKpi('Toplam müşteri alacağı', array_sum(array_map(fn ($x) => max(0, $x['bakiye']), _fnrCari($sid))), 'tl', '6D28D9')], [],
                        !$acik && !$paket && !$ver ? _fnrBos('acik', $dn) : null),
                    _srSayfa('Açık Kalmış Adisyonlar', 'Bugünden önce açılıp kapatılmamış adisyonlar: ya unutulmuş masa ya da tahsil edilmemiş satış. POS\'ta kapatın ya da iptal edin.', [['Açılış', 't', 11], ['Kanal', 'm', 14], ['Açan', 'm', 18], ['Tutar', 'tl', 12, 'top bar'], ['Gün', 's', 7, 'esik:2']], $acik, 'Dünden açık kalan adisyon yok.'),
                    _srSayfa('Parası Kuryede Olan Paketler', 'Teslim edilmiş ama kasaya tahsil edilmemiş paket siparişler (Paket > kasa tahsilatı).', [['Tarih', 't', 11], ['Kaynak', 'm', 14], ['Kurye', 'm', 18], ['Tutar', 'tl', 12, 'top bar'], ['Saat', 's', 7, 'esik:12']], $paket, 'Parası kuryede bekleyen paket yok.'),
                    _srSayfa('Veresiye (Açık Hesap) Satışlar', 'Dönemde "Açık Hesap" ödemesiyle kapatılan satışlar; tahsilat Cari Hesaplar\'dan girilir.', [['Tarih', 't', 11], ['Tutar', 'tl', 13, 'top bar']], array_map(fn ($x) => [$x['tarih'], $x['tutar']], $ver), 'Seçili dönemde veresiye satış yok.'),
                ];

            // ---------------- GİDER ----------------
            case 'gider_dagilim':
                $g = _fnrGider($sid, $bas, $bit); $go = _fnrGider($sid, $obas, $obit); $c = _fnrCiro($sid, $bas, $bit); $top = array_sum(array_column($g, 'tutar')); $topO = array_sum(array_column($go, 'tutar'));
                $aylar = []; for ($m = 5; $m >= 0; $m--) $aylar[] = date('Y-m', strtotime("first day of -$m month"));
                $tr = []; foreach (_fnrGider($sid, $aylar[0] . '-01', date('Y-m-d')) as $x) $tr[$x['kat']][substr($x['tarih'], 0, 7)] = ($tr[$x['kat']][substr($x['tarih'], 0, 7)] ?? 0) + $x['tutar'];
                $trs = array_map(fn ($k, $v) => array_merge([_fnrGiderAd($k)], array_map(fn ($a) => round($v[$a] ?? 0, 2), $aylar)), array_keys($tr), array_values($tr));
                return [
                    $pano([_srKpi('Toplam gider', $top, 'tl', 'F59E0B'), _srKpi('Önceki dönem', $topO, 'tl', 'D97706'), $kpiDeg('Değişim', _srYuzde($top, $topO), 'B45309'), $kpiY('Ciroya oranı', $yuz($top, $c), '92400E'), _srKpi('Kayıt', count($g), 'sayi', '64748B')],
                        [$blokGiderPie($g, 'F59E0B'), _srBlok('Bu dönem ve önceki dönem', 'bar', 'D97706', [['Kategori', 'm'], ['Bu dönem', 'tl'], ['Önceki', 'tl']], array_map(fn ($x) => [$x[0], $x[2], $x[4]], _fnrGiderKatSat($g, $go, $c)))], !$g ? $bosGider : null),
                    _fnrSayfaGiderKat(_fnrGiderKatSat($g, $go, $c), $bosGider),
                    ['grafiksiz' => true] + _srSayfa('Son 6 Ay (Kategori × Ay)', 'Bu ay bugüne kadar.', array_merge([['Kategori', 'm', 26]], array_map(fn ($a) => [_fnrAyAd($a), 'tl', 12, 'top mat'], $aylar)), $trs, !$d['gider'] ? _fnrBos('gider_yok', $dn) : 'Son 6 ayda gider yok.'),
                    _fnrSayfaGiderListe('Tüm Giderler', '', $g, $bosGider),
                ];
            case 'gider_buyuk':
                $g = _fnrGider($sid, $bas, $bit); $buyuk = $g; usort($buyuk, fn ($a, $b) => $b['tutar'] <=> $a['tutar']);
                $tar = _fnrGider($sid, date('Y-m-d', strtotime($bas . ' -180 day')), $bit); $med = [];
                foreach ($tar as $x) $med[$x['kat']][] = $x['tutar']; foreach ($med as $k => $l) { sort($l); $med[$k] = count($l) >= 4 ? $l[intdiv(count($l), 2)] : null; }
                $olag = []; foreach ($g as $x) if (($m = $med[$x['kat']] ?? null) && $x['tutar'] >= 3 * $m && $x['tutar'] >= 500) $olag[] = [$x['tarih'], _fnrGiderAd($x['kat']), $x['aciklama'] ?: '—', $x['tutar'], round($m, 2), round($x['tutar'] / $m, 1)];
                $muk = []; $n = count($g);
                for ($i = 0; $i < $n; $i++) for ($j = $i + 1; $j < $n; $j++) { $a = $g[$i]; $b = $g[$j]; if ($a['kat'] === $b['kat'] && abs($a['tutar'] - $b['tutar']) < 0.01 && abs(strtotime($a['tarih']) - strtotime($b['tarih'])) <= 3 * 86400 && mb_strtolower(trim($a['aciklama'])) === mb_strtolower(trim($b['aciklama'])))
                    $muk[] = [$a['tarih'], $b['tarih'], _fnrGiderAd($a['kat']), $a['aciklama'] ?: '—', $a['tutar']]; if (count($muk) > 200) break 2; }
                $top = array_sum(array_column($g, 'tutar')); $ilk10 = array_sum(array_column(array_slice($buyuk, 0, 10), 'tutar'));
                return [
                    $pano([_srKpi('Toplam gider', $top, 'tl', 'D97706'), $kpiY('En büyük 10 kaydın payı', $yuz($ilk10, $top), 'B45309'), _srKpi('Olağandışı yüksek kayıt', count($olag), 'sayi', 'DC2626'), _srKpi('Olası mükerrer kayıt', count($muk), 'sayi', 'B91C1C')],
                        [_srBlok('En büyük 10 gider', 'bar', 'D97706', [['Gider', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [mb_substr(($x['aciklama'] ?: _fnrGiderAd($x['kat'])), 0, 40) . ' (' . date('d.m', strtotime($x['tarih'])) . ')', $x['tutar'], ''], array_slice($buyuk, 0, 10)))], !$g ? $bosGider : null),
                    _fnrSayfaGiderListe('En Büyük Giderler', 'Tutara göre sıralı ilk 50 kayıt.', array_slice($buyuk, 0, 50), $bosGider),
                    _srSayfa('Olağandışı Yüksek Giderler', 'Kategorisinin son 6 aylık ortanca (medyan) tutarının en az 3 katı olan ve 500 TL üstü kayıtlar.', [['Tarih', 't', 11], ['Kategori', 'm', 22], ['Açıklama', 'm', 32], ['Tutar', 'tl', 12, 'top bar'], ['Kategori Ortancası', 'tl', 13], ['Kat', 's', 7, 'esik:5']], $olag, _fnrBos('buyuk', $dn)),
                    _srSayfa('Olası Mükerrer Girişler', 'Aynı kategori, aynı açıklama ve aynı tutarla 3 gün içinde iki kez girilmiş giderler; çift girilmişse Giderler ekranından silin.', [['1. Kayıt', 't', 11], ['2. Kayıt', 't', 11], ['Kategori', 'm', 22], ['Açıklama', 'm', 32], ['Tutar', 'tl', 12, 'top bar']], $muk, 'Mükerrer görünen gider yok.'),
                ];
            case 'sabit_gider':
                $sb = _fnrSabit($sid); $ayb = date('Y-m'); $isl = [];
                if (_srTablo('giderler')) { try { foreach (DB::table('giderler')->where('sube_id', $sid)->where('aciklama', 'like', '[Sabit]%')->where('tarih', '>=', $ayb . '-01')->get(['aciklama', 'tutar']) as $x) $isl[trim(substr((string) $x->aciklama, 8))] = ($isl[trim(substr((string) $x->aciklama, 8))] ?? 0) + (float) $x->tutar; } catch (\Throwable $e) {} }
                $sat = array_map(fn ($x) => [(string) $x->ad, _fnrGiderAd($x->kategori), ($x->periyot ?? 'aylik') === 'yillik' ? 'Yıllık' : 'Aylık', (int) $x->gun, (float) $x->tutar, round((float) $x->tutar / ((($x->periyot ?? 'aylik') === 'yillik') ? 12 : 1), 2), (int) $x->aktif ? 'Aktif' : 'Pasif',
                    !(int) $x->aktif || ($x->periyot ?? 'aylik') === 'yillik' ? '—' : (isset($isl[$x->ad]) ? 'İşlendi' : ((int) $x->gun <= (int) date('j') ? 'İşlenmedi (günü geçti)' : 'Bekliyor'))], $sb);
                $gun = max(1, $dn['gun']); $g = _fnrGiderKat(_fnrGider($sid, $bas, $bit)); $pk = []; foreach ($sb as $x) if ((int) $x->aktif) $pk[$x->kategori ?: 'diger'] = ($pk[$x->kategori ?: 'diger'] ?? 0) + (float) $x->tutar / ((($x->periyot ?? 'aylik') === 'yillik') ? 12 : 1);
                $kp = array_map(fn ($k) => [_fnrGiderAd($k), round(($pk[$k] ?? 0) * $gun / 30, 2), $g[$k] ?? 0, round(($g[$k] ?? 0) - ($pk[$k] ?? 0) * $gun / 30, 2)], array_keys($pk));
                $bs = !$d['sabit'] ? _fnrBos('sabit_yok', $dn) : null;
                return [
                    $pano([_srKpi('Aylık sabit gider yükü', _fnrSabitAylik($sid), 'tl', 'B45309'), _srKpi('Aktif tanım', count(array_filter($sb, fn ($x) => (int) $x->aktif)), 'sayi', '92400E'), _srKpi('Bu ay işlenmemiş (günü geçmiş)', count(array_filter($sat, fn ($x) => str_starts_with($x[7], 'İşlenmedi'))), 'sayi', 'DC2626'),
                        $kpiY('Sabit giderin ciroya oranı (aylık)', $yuz(_fnrSabitAylik($sid), _fnrCiro($sid, $bas, $bit) / $gun * 30), 'D97706')],
                        [_srBlok('Kategorilere göre sabit gider', 'pie', 'B45309', [['Kategori', 'm'], ['Aylık', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [_fnrGiderAd($k), round($v, 2), ''], array_keys($pk), array_values($pk)))], $bs),
                    _srSayfa('Tekrarlayan Giderler', '"İşlenmedi": tanımın ayın gününü geçtiği halde bu ay Giderler\'e "[Sabit]" kaydı düşmemiş (Finans AI\'ya "tekrarlayan giderleri işle" diyebilir ya da elle girebilirsiniz).',
                        [['Gider', 'm', 26], ['Kategori', 'm', 22], ['Periyot', 'm', 8], ['Gün', 's', 6], ['Tutar', 'tl', 12], ['Aylık Karşılığı', 'tl', 12, 'top bar'], ['Durum', 'm', 8], ['Bu Ay', 'm', 20]], $sat, $bs ?? 'Tanım yok.'),
                    ['grafiksiz' => true] + _srSayfa('Plan & Gerçekleşen (Kategori)', 'Plan = tekrarlayan tanımların aylık karşılığı × dönem günü / 30; gerçekleşen = Giderler\'deki aynı kategorinin dönem toplamı (sabit olmayan kayıtlar dahil).',
                        [['Kategori', 'm', 26], ['Plan', 'tl', 13, 'top'], ['Gerçekleşen', 'tl', 13, 'top'], ['Fark', 'tl', 13, 'top']], $kp, $bs ?? 'Aktif tanım yok.'),
                ];
            case 'butce':
                $p = _fnrPl($sid, $bas, $bit); $sy = _fnrButceSayfa($sid, $dn, $p); $gun = max(1, $dn['gun']); $bt = array_sum($ay['butce']) * $gun / 30; $gt = $p['gider_top'];
                $hd = round($ay['ciro_hedef'] * $gun / 30, 2);
                return [
                    $pano([_srKpi('Gider bütçesi (dönem)', round($bt, 2), 'tl', '92400E'), _srKpi('Gerçekleşen gider', $gt, 'tl', 'D97706'), $kpiY('Bütçe kullanımı', $bt > 0 ? round($gt / $bt * 100, 1) : null, $bt > 0 && $gt > $bt ? 'DC2626' : '059669'),
                        _srKpi('Ciro hedefi (dönem)', $hd ?: 'Girilmedi', $hd ? 'tl' : 'metin', '2563EB'), _srKpi('Gerçekleşen ciro', $p['ciro'], 'tl', '1D4ED8'), $kpiY('Hedefe ulaşma', $hd > 0 ? round($p['ciro'] / $hd * 100, 1) : null, '1E40AF')],
                        [_srBlok('Kategori bütçesi ve gerçekleşen', 'bar', 'F59E0B', [['Kategori', 'm'], ['Bütçe', 'tl'], ['Gerçekleşen', 'tl']], array_map(fn ($x) => [$x[0], $x[1], $x[2]], array_filter($sy['sat'], fn ($x) => $x[1] > 0 || $x[2] > 0)))],
                        !$ay['butce_var'] ? _fnrBos('butce_yok', $dn) : null),
                    $sy,
                ];

            // ---------------- NAKİT ----------------
            case 'nakit_akis':
                $od = _fnrOdeme($sid, $bas, $bit); $g = _fnrGider($sid, $bas, $bit); $cd = _fnrCariDonem($sid, $bas, $bit); $gun = [];
                foreach (_fnrGunler($bas, $bit) as $t) $gun[$t] = ['n' => 0, 'k' => 0, 'y' => 0, 'o' => 0, 'c' => $cd['gun'][$t] ?? 0, 'g' => 0];
                foreach ($od as $x) { if (!isset($gun[$x['tarih']]) || $x['tip'] === 'acik_hesap') continue; $k = $x['tip'] === 'nakit' ? 'n' : (in_array($x['tip'], ['kredi', 'kart', 'kart_kapida'], true) ? 'k' : ($x['tip'] === 'yemek_karti' ? 'y' : 'o')); $gun[$x['tarih']][$k] += $x['tutar']; }
                foreach ($g as $x) if (isset($gun[$x['tarih']])) $gun[$x['tarih']]['g'] += $x['tutar'];
                $kum = 0; $sat = [];
                foreach ($gun as $t => $v) { $gir = $v['n'] + $v['k'] + $v['y'] + $v['o'] + $v['c']; $net = $gir - $v['g']; $kum += $net; $sat[] = [$t, _fnrGunAd(date('N', strtotime($t))), round($v['n'], 2), round($v['k'], 2), round($v['y'], 2), round($v['o'], 2), round($v['c'], 2), round($gir, 2), round($v['g'], 2), round($net, 2), round($kum, 2)]; }
                $gir = array_sum(array_column($sat, 7)); $cik = array_sum(array_column($sat, 8));
                return [
                    $pano([_srKpi('Nakit girişi', $gir, 'tl', '059669'), _srKpi('Nakit çıkışı (giderler)', $cik, 'tl', 'DC2626'), _srKpi('Net nakit akışı', round($gir - $cik, 2), 'tl', $gir - $cik < 0 ? 'DC2626' : '0D9488'), _srKpi('Cari tahsilat', $cd['tahsilat'], 'tl', '7C3AED'),
                        _srKpi('Veresiye (girişe sayılmadı)', array_sum(array_map(fn ($x) => $x['tip'] === 'acik_hesap' ? $x['tutar'] : 0, $od)), 'tl', 'D97706'), _srKpi('Negatif gün', count(array_filter($sat, fn ($x) => $x[9] < 0)), 'sayi', 'B91C1C')],
                        [_srBlok('Günlere göre giriş ve çıkış', 'col', '0D9488', [['Gün', 'm'], ['Giriş', 'tl'], ['Çıkış', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[7], $x[8]], $sat)), $blokGiderPie($g, 'DC2626')],
                        !$od && !$g ? (!$d['odeme'] && !$d['gider'] ? _fnrBos('satis_yok', $dn) . ' ' . _fnrBos('gider_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') tahsilat ya da gider yok.') : null),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Nakit Akışı', 'Giriş: POS tahsilatları (açık hesap hariç) + cari tahsilatlar. Çıkış: Giderler defteri (alış faturasından düşen malzeme ve personel ödemeleri dahil). Kart ve yemek kartı paraları bankaya birkaç gün sonra geçer; online platform hak edişleri dönemsel ödenir.',
                        [['Tarih', 't', 10], ['Gün', 'm', 10], ['Nakit', 'tl', 12, 'top'], ['Kart', 'tl', 12, 'top'], ['Yemek Kartı', 'tl', 11, 'top'], ['Online / Diğer', 'tl', 11, 'top'], ['Cari Tahsilat', 'tl', 11, 'top'], ['Toplam Giriş', 'tl', 13, 'top'], ['Çıkış', 'tl', 12, 'top'], ['Net', 'tl', 12, 'top'], ['Kümülatif', 'tl', 13]], $sat, $bosOdeme),
                    _fnrSayfaGiderListe('Çıkışlar (Giderler)', '', $g, $bosGider),
                ];
            case 'nakit_tahmin':
                $nt = _fnrNakitTahmin($sid); $ol = _fnrOlaylar($sid, 27);
                $bos = !$d['banka'] && !$d['alis'] && !$d['sabit'] && !$d['odeme'] ? _fnrBos('banka_yok', $dn) . ' ' . _fnrBos('sabit_yok', $dn) : (!$d['banka'] ? _fnrBos('banka_yok', $dn) : null);
                return [
                    $pano([_srKpi('Başlangıç nakdi (banka + kasa)', $nt['baslangic'], 'tl', '0D9488'), _srKpi('Banka', $nt['banka'], 'tl', '0F766E'), _srKpi('Kasa', $nt['kasa']['tutar'], 'tl', '115E59'), _srKpi('Haftalık ortalama tahsilat', $nt['tahsilat_hafta'], 'tl', '059669'),
                        _srKpi('Vadesi geçmiş ödeme', $nt['gecikmis'], 'tl', 'DC2626'), _srKpi('4 hafta içindeki en düşük bakiye', $nt['en_dusuk'] ?? 0, 'tl', ($nt['en_dusuk'] ?? 0) < 0 ? 'DC2626' : '047857'),
                        _srKpi('Durum', ($nt['en_dusuk'] ?? 0) < 0 ? 'Nakit açığı riski' : 'Nakit yeterli görünüyor', 'metin', ($nt['en_dusuk'] ?? 0) < 0 ? 'DC2626' : '059669')],
                        [_srBlok('Haftalara göre beklenen giriş ve çıkış', 'col', '0D9488', [['Hafta', 'm'], ['Giriş', 'tl'], ['Çıkış', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x['bas'])) . '–' . date('d.m', strtotime($x['bit'])), round($x['tahsilat'] + $x['cek_al'], 2), round($x['ted'] + $x['cek_ver'] + $x['sabit'] + $x['maas'] + $x['olagan'], 2)], $nt['hafta']))], $bos),
                    _fnrNakitTahminSayfa($nt),
                    _fnrSayfaOlay('Vadeli Ödeme & Tahsilatlar (4 Hafta)', $ol, _fnrBos('olay', $dn)),
                ];
            case 'banka':
                $hs = _fnrBanka($sid); $bh = _fnrBankaHareket($sid, $bas, $bit); $hn = []; foreach ($bh as $x) { $hn[$x['hesap']][$x['tip']] = ($hn[$x['hesap']][$x['tip']] ?? 0) + $x['tutar']; }
                $bb = !$d['banka'] ? _fnrBos('banka_yok', $dn) : _fnrBos('banka_hareket', $dn);
                return [
                    $pano([_srKpi('Toplam bakiye (aktif)', array_sum(array_map(fn ($h) => (int) ($h->aktif ?? 1) ? (float) $h->bakiye : 0, $hs)), 'tl', '0D9488'), _srKpi('Hesap', count($hs), 'sayi', '115E59'),
                        _srKpi('Dönem girişi', array_sum(array_map(fn ($x) => $x['tip'] === 'giris' ? $x['tutar'] : 0, $bh)), 'tl', '059669'), _srKpi('Dönem çıkışı', array_sum(array_map(fn ($x) => $x['tip'] === 'cikis' ? $x['tutar'] : 0, $bh)), 'tl', 'DC2626')],
                        [_srBlok('Hesaplara göre bakiye', 'pie', '0D9488', [['Hesap', 'm'], ['Bakiye', 'tl'], ['', 'm']], array_map(fn ($h) => [$h->banka_adi . ($h->hesap_adi ? ' · ' . $h->hesap_adi : ''), (float) $h->bakiye, ''], array_filter($hs, fn ($h) => (float) $h->bakiye > 0)))], !$hs ? _fnrBos('banka_yok', $dn) : null),
                    _srSayfa('Hesaplar', 'Bakiye, hesaba girilen hareketlerle güncellenir; banka ekstresiyle düzenli karşılaştırın.', [['Banka', 'm', 18], ['Hesap', 'm', 16], ['IBAN', 'm', 30], ['Para Birimi', 'm', 8], ['Bakiye', 'tl', 14, 'top bar'], ['Dönem Giriş', 'tl', 12, 'top'], ['Dönem Çıkış', 'tl', 12, 'top'], ['Durum', 'm', 8]],
                        array_map(fn ($h) => [(string) $h->banka_adi, (string) ($h->hesap_adi ?? ''), (string) ($h->iban ?? ''), (string) ($h->para_birimi ?? 'TRY'), (float) $h->bakiye, round($hn[$h->banka_adi . ($h->hesap_adi ? ' · ' . $h->hesap_adi : '')]['giris'] ?? 0, 2), round($hn[$h->banka_adi . ($h->hesap_adi ? ' · ' . $h->hesap_adi : '')]['cikis'] ?? 0, 2), (int) ($h->aktif ?? 1) ? 'Aktif' : 'Pasif'], $hs), _fnrBos('banka_yok', $dn)),
                    _srSayfa('Hareketler', '', [['Tarih', 't', 11], ['Hesap', 'm', 24], ['Yön', 'm', 8], ['Açıklama', 'm', 34], ['Tutar', 'tl', 13, 'bar']], array_map(fn ($x) => [$x['tarih'], $x['hesap'], $x['tip'] === 'giris' ? 'Giriş' : 'Çıkış', $x['aciklama'] ?: '—', $x['tip'] === 'giris' ? $x['tutar'] : -$x['tutar']], $bh), $bb),
                ];

            // ---------------- ALACAK & BORÇ ----------------
            case 'cari_alacak':
                $c = _fnrCari($sid); $sat = _fnrCariSat($c); $k = [0, 0, 0, 0]; foreach ($c as $x) if ($x['bakiye'] > 0) foreach ($x['k'] as $i => $v) $k[$i] += $v;
                $cd = _fnrCariDonem($sid, $bas, $bit); $bc = !$d['cari'] && !$c ? _fnrBos('cari_yok', $dn) : _fnrBos('cari', $dn);
                return [
                    $pano([_srKpi('Toplam alacak', array_sum(array_column($sat, 2)), 'tl', '7C3AED'), _srKpi('Borçlu cari', count($sat), 'sayi', '6D28D9'), _srKpi('90 günü geçen', $k[3], 'tl', 'DC2626'), _srKpi('61-90 gün', $k[2], 'tl', 'D97706'),
                        _srKpi('Dönemde veresiye satış', $cd['satis'], 'tl', '5B21B6'), _srKpi('Dönemde tahsilat', $cd['tahsilat'], 'tl', '059669'), $kpiY('Tahsilat / satış', $yuz($cd['tahsilat'], $cd['satis']), '4C1D95')],
                        [_srBlok('Yaşa göre alacak', 'col', '7C3AED', [['Yaş', 'm'], ['Tutar', 'tl'], ['', 'm']], array_sum($k) > 0 ? [['0-30 gün', round($k[0], 2), ''], ['31-60 gün', round($k[1], 2), ''], ['61-90 gün', round($k[2], 2), ''], ['90+ gün', round($k[3], 2), '']] : []),
                         _srBlok('En çok borçlu 10 cari', 'bar', '6D28D9', [['Cari', 'm'], ['Bakiye', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], array_slice($sat, 0, 10)))], !$sat ? $bc : null),
                    _fnrSayfaCari($sat, $bc),
                    _srSayfa('Tüm Cariler', 'Eksi bakiye = carinin alacağı (fazla ödeme / avans).', [['Cari', 'm', 24], ['Tür', 'm', 10], ['Toplam Borç', 'tl', 13, 'top'], ['Toplam Tahsilat', 'tl', 13, 'top'], ['Bakiye', 'tl', 13, 'top bar'], ['Son Tahsilat', 't', 11]],
                        array_map(fn ($x) => [$x['ad'], $x['tip'], $x['borc'], $x['tahsilat'], $x['bakiye'], $x['son_tahsilat']], $c), !$d['cari'] ? _fnrBos('cari_yok', $dn) : 'Hareketi olan cari yok.'),
                ];
            case 'tedarikci_borc':
                $y = _fnrTedBorc($sid); $fat = []; foreach ($y['tedarikciler'] as $t) foreach ($t['faturalar'] as $f) $fat[] = [$t['ad'], $f['no'] ?: '#' . $f['id'], $f['tarih'], $f['vade'], round($f['tutar'], 2), $f['gun'] < 0 ? -$f['gun'] : 0, $f['gun'] < 0 ? 'Gecikmiş' : ($f['gun'] <= 7 ? 'Bu hafta' : ($f['gun'] <= 30 ? '30 gün içinde' : 'İleri vade'))];
                usort($fat, fn ($a, $b) => $a[3] <=> $b[3]); $bt = !$d['alis'] ? _fnrBos('alis_yok', $dn) : _fnrBos('ted_borc', $dn);
                $alisD = array_sum(array_column(_fnrAlis($sid, $bas, $bit), 'toplam'));
                return [
                    $pano([_srKpi('Toplam tedarikçi borcu', $y['toplam'], 'tl', '6D28D9'), _srKpi('Gecikmiş', $y['gecikmis'], 'tl', 'DC2626'), _srKpi('7 gün içinde', $y['hafta'], 'tl', 'D97706'), _srKpi('8-30 gün', $y['ay'], 'tl', '7C3AED'), _srKpi('31+ gün', $y['ileri'], 'tl', '5B21B6'),
                        _srKpi('Dönemde alış', $alisD, 'tl', '64748B'), $kpiY('Gecikmiş payı', $yuz($y['gecikmis'], $y['toplam']), 'B91C1C')],
                        [_srBlok('Vadeye göre borç', 'col', '6D28D9', [['Vade', 'm'], ['Tutar', 'tl'], ['', 'm']], $y['toplam'] > 0 ? [['Gecikmiş', round($y['gecikmis'], 2), ''], ['0-7 gün', round($y['hafta'], 2), ''], ['8-30 gün', round($y['ay'], 2), ''], ['31+ gün', round($y['ileri'], 2), '']] : [])], !$y['toplam'] ? $bt : null),
                    _fnrSayfaTedBorc($y, $bt),
                    _srSayfa('Ödenmemiş Faturalar', 'Ödenen faturalar Tedarikçi Yaşlandırma ekranında "Ödendi" işaretlenmelidir; işaretlenmezse borç olduğundan yüksek görünür.', [['Tedarikçi', 'm', 22], ['Fatura No', 'm', 14], ['Fatura Tarihi', 't', 11], ['Vade', 't', 11], ['Tutar', 'tl', 13, 'top bar'], ['Gecikme (gün)', 's', 10, 'esik:15'], ['Durum', 'm', 14]], $fat, $bt),
                ];
            case 'cek_senet':
                $cs = _fnrCek($sid); $bug = date('Y-m-d'); $port = array_values(array_filter($cs, fn ($c) => ($c->durum ?? '') === 'portfoyde'));
                $kova = function ($yon) use ($port, $bug) { $k = ['Vadesi geçmiş' => 0, '0-7 gün' => 0, '8-30 gün' => 0, '31-90 gün' => 0, '90+ gün / vadesiz' => 0]; foreach ($port as $c) { if ($c->yon !== $yon) continue; $g = $c->vade ? (int) round((strtotime(substr((string) $c->vade, 0, 10)) - strtotime($bug)) / 86400) : null; $k[$g === null ? '90+ gün / vadesiz' : ($g < 0 ? 'Vadesi geçmiş' : ($g <= 7 ? '0-7 gün' : ($g <= 30 ? '8-30 gün' : ($g <= 90 ? '31-90 gün' : '90+ gün / vadesiz'))))] += (float) $c->tutar; } return $k; };
                $ka = $kova('alinan'); $kv = $kova('verilen');
                $sat = array_map(fn ($c) => [$c->vade ? substr((string) $c->vade, 0, 10) : null, $c->tip === 'senet' ? 'Senet' : 'Çek', $c->yon === 'verilen' ? 'Verilen' : 'Alınan', (string) ($c->kisi ?? '—'), trim(($c->banka ?? '') . ' ' . ($c->no ?? '')) ?: '—', (float) $c->tutar, _fnrCekDurumAd($c->durum), $c->vade ? (int) round((strtotime(substr((string) $c->vade, 0, 10)) - strtotime($bug)) / 86400) : null], $cs);
                $bc = !$d['cek'] ? _fnrBos('cek_yok', $dn) : _fnrBos('cek', $dn);
                return [
                    $pano([_srKpi('Portföyde alınan', array_sum($ka), 'tl', '059669'), _srKpi('Portföyde verilen', array_sum($kv), 'tl', 'DC2626'), _srKpi('Net (alınan − verilen)', round(array_sum($ka) - array_sum($kv), 2), 'tl', '5B21B6'),
                        _srKpi('7 gün içinde ödenecek', $kv['0-7 gün'] + $kv['Vadesi geçmiş'], 'tl', 'B91C1C'), _srKpi('7 gün içinde tahsil edilecek', $ka['0-7 gün'] + $ka['Vadesi geçmiş'], 'tl', '047857'), _srKpi('Karşılıksız', count(array_filter($cs, fn ($c) => ($c->durum ?? '') === 'karsiliksiz')), 'sayi', '991B1B')],
                        [_srBlok('Vadeye göre alınan ve verilen', 'col', '5B21B6', [['Vade', 'm'], ['Alınan', 'tl'], ['Verilen', 'tl']], $port ? array_map(fn ($k) => [$k, round($ka[$k], 2), round($kv[$k], 2)], array_keys($ka)) : [])], !$port ? $bc : null),
                    _srSayfa('Portföydeki Çek & Senetler', 'Negatif kalan gün = vadesi geçmiş; tahsil / ödeme yapıldıysa Çek / Senet ekranından durumunu güncelleyin.', [['Vade', 't', 11], ['Tür', 'm', 7], ['Yön', 'm', 8], ['Kişi / Firma', 'm', 22], ['Banka / No', 'm', 20], ['Tutar', 'tl', 13, 'top bar'], ['Durum', 'm', 12], ['Kalan Gün', 's', 9]],
                        array_values(array_filter($sat, fn ($x) => $x[6] === 'Portföyde')), $bc),
                    _srSayfa('Tüm Kayıtlar', '', [['Vade', 't', 11], ['Tür', 'm', 7], ['Yön', 'm', 8], ['Kişi / Firma', 'm', 22], ['Banka / No', 'm', 20], ['Tutar', 'tl', 13, 'top'], ['Durum', 'm', 12], ['Kalan Gün', 's', 9]], $sat, _fnrBos('cek_yok', $dn)),
                ];

            // ---------------- VERGİ ----------------
            case 'kdv':
                $k = _fnrKdv($sid, $bas, $bit); $al = _fnrAlis($sid, $bas, $bit); $oran = _fnrKdvOran($sid); $td = [];
                foreach ($al as $f) { $x = &$td[$f['ted']]; $x['n'] = ($x['n'] ?? 0) + 1; $x['t'] = ($x['t'] ?? 0) + $f['toplam']; $x['k'] = ($x['k'] ?? 0) + ($f['kdv'] > 0 ? $f['kdv'] : $f['toplam'] - $f['toplam'] / (1 + $oran / 100)); $x['tah'] = ($x['tah'] ?? false) || $f['kdv'] <= 0; unset($x); }
                uasort($td, fn ($a, $b) => $b['k'] <=> $a['k']);
                $hes = array_sum(array_column($k, 'hes')); $ind = array_sum(array_column($k, 'ind'));
                return [
                    $pano([_srKpi('Hesaplanan KDV', $hes, 'tl', 'E11D48'), _srKpi('İndirilecek KDV', $ind, 'tl', '059669'), _srKpi('Ödenecek KDV (tahmini)', round($hes - $ind, 2), 'tl', $hes - $ind > 0 ? 'BE123C' : '059669'), _srKpi('Satış KDV oranı', '%' . $oran, 'metin', '64748B'),
                        _srKpi('KDV\'si girilmemiş alış faturası', count(array_filter($al, fn ($f) => $f['kdv'] <= 0)), 'sayi', 'D97706')],
                        [_srBlok('Aylara göre hesaplanan ve indirilecek KDV', 'col', 'E11D48', [['Ay', 'm'], ['Hesaplanan', 'tl'], ['İndirilecek', 'tl']], array_map(fn ($x) => [_fnrAyAd($x['ay']), $x['hes'], $x['ind']], $k))],
                        !$k ? $bosSatis : (!$d['alis'] ? _fnrBos('alis_yok', $dn) : null)),
                    _fnrSayfaKdv($k, $bosSatis),
                    _srSayfa('Tedarikçiye Göre İndirilecek KDV', 'Faturada KDV tutarı girilmemişse satış oranıyla (%' . $oran . ') tahmin edildi; e-faturadan gelen faturalarda gerçek KDV kullanılır.', [['Tedarikçi', 'm', 26], ['Fatura', 's', 7, 'top'], ['Alış (KDV dahil)', 'tl', 14, 'top'], ['İndirilecek KDV', 'tl', 14, 'top bar'], ['Not', 'm', 20]],
                        array_map(fn ($a, $x) => [$a, $x['n'], round($x['t'], 2), round($x['k'], 2), $x['tah'] ? 'Kısmen tahmini' : ''], array_keys($td), array_values($td)), !$d['alis'] ? _fnrBos('alis_yok', $dn) : 'Seçili dönemde alış faturası yok.'),
                ];
            case 'vergi_takvim':
                $vt = function_exists('_vergiTakvimi') ? _vergiTakvimi() : []; $gb = date('Y-m-01', strtotime('first day of last month')); $ge = date('Y-m-t', strtotime($gb));
                $kg = _fnrKdv($sid, $gb, $ge); $kdvT = round(array_sum(array_column($kg, 'oden')), 2);
                $sat = array_map(fn ($v) => [$v['ad'], $v['tarih'] instanceof \DateTimeInterface ? $v['tarih']->format('Y-m-d') : substr((string) $v['tarih'], 0, 10), (int) $v['kalan'], str_starts_with($v['ad'], 'KDV') ? max(0, $kdvT) : null, $v['aciklama']], $vt);
                return [
                    $pano([_srKpi('En yakın yükümlülük', $sat ? $sat[0][0] : '—', 'metin', 'BE123C'), _srKpi('Kalan gün', $sat ? $sat[0][2] : 0, 'sayi', 'E11D48'), _srKpi('Geçen ayın tahmini ödenecek KDV\'si', $kdvT, 'tl', '9F1239'),
                        _srKpi('7 gün içinde', count(array_filter($sat, fn ($x) => $x[2] <= 7)), 'sayi', 'DC2626')], [], !$sat ? 'Vergi takvimi yüklenemedi.' : null),
                    ['grafiksiz' => true] + _srSayfa('Vergi Takvimi', 'Standart son tarihler (yaklaşık; resmî tatil kaydırmaları için mali müşavirinize teyit ettirin). KDV tahmini: geçen ayın hesaplanan − indirilecek KDV\'si; muhtasar / SGK tutarı bordrodan çıkar, burada hesaplanmaz.',
                        [['Yükümlülük', 'm', 28], ['Son Tarih', 't', 11], ['Kalan Gün', 's', 9], ['Tahmini Tutar', 'tl', 13], ['Açıklama', 'm', 44]], $sat, 'Vergi takvimi yüklenemedi.'),
                    _fnrSayfaKdv($kg, $bosSatis),
                ];

            // ---------------- KAYIP & KAÇAK ----------------
            case 'iptal_indirim':
                $ip = _fnrIptal($sid, $bas, $bit); $c = _fnrCiro($sid, $bas, $bit); $ia = _fnrIptalAdisyon($sid, $bas, $bit); $p = _fnrPl($sid, $bas, $bit);
                $tip = []; foreach ($ip as $x) { $tip[$x['tip']]['n'] = ($tip[$x['tip']]['n'] ?? 0) + 1; $tip[$x['tip']]['tl'] = ($tip[$x['tip']]['tl'] ?? 0) + $x['tutar']; } uasort($tip, fn ($a, $b) => $b['tl'] <=> $a['tl']);
                $seb = collect($ip)->groupBy(fn ($x) => _fnrIptalTipAd($x['tip']) . ' · ' . $x['sebep'])->map(fn ($g, $k) => [$k, $g->count(), round($g->sum('tutar'), 2)])->sortByDesc(2)->values()->all();
                $kis = collect($ip)->groupBy('kisi')->map(fn ($g, $k) => [$k, $g->count(), round($g->where('tip', 'void')->sum('tutar') + $g->where('tip', 'odeme_iptal')->sum('tutar'), 2), round($g->where('tip', 'ikram')->sum('tutar'), 2), round($g->where('tip', 'indirim')->sum('tutar'), 2), round($g->sum('tutar'), 2)])->sortByDesc(5)->values()->all();
                $top = array_sum(array_column($ip, 'tutar')); $bi = !$d['iptal'] ? 'İptal / ikram / indirim kaydı yok. POS\'ta ürün iptali, ikram ve indirim yapıldıkça sebebiyle birlikte otomatik kaydedilir (Ayarlar > Sebep Yönetimi\'nden sebepler tanımlanır).' : _fnrBos('iptal', $dn);
                return [
                    $pano([_srKpi('Toplam kayıp', $top, 'tl', 'DC2626'), $kpiY('Ciroya oranı', $yuz($top, $c), 'B91C1C'), _srKpi('İndirim (adisyon)', $p['indirim'], 'tl', 'D97706'), _srKpi('İkram (adisyon)', $p['ikram'], 'tl', 'B45309'),
                        _srKpi('İptal edilen adisyon', $ia['n'], 'sayi', '991B1B'), _srKpi('İptal edilen adisyon tutarı', $ia['tl'], 'tl', '7F1D1D')],
                        [_srBlok('Türe göre', 'pie', 'DC2626', [['Tür', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [_fnrIptalTipAd($k), round($v['tl'], 2), ''], array_keys($tip), array_values($tip))),
                         _srBlok('En çok tekrarlanan sebepler', 'bar', 'B91C1C', [['Sebep', 'm'], ['Tutar', 'tl'], ['Kez', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[1]], array_slice($seb, 0, 10)))], !$ip && !$ia['n'] ? $bi : null),
                    _srSayfa('Türe Göre', '', [['Tür', 'm', 22], ['Kez', 's', 8, 'top'], ['Tutar', 'tl', 13, 'top bar'], ['Ciroya %', 'y', 9]], array_map(fn ($k, $v) => [_fnrIptalTipAd($k), $v['n'], round($v['tl'], 2), $yuz($v['tl'], $c)], array_keys($tip), array_values($tip)), $bi),
                    _srSayfa('Sebebe Göre', '', [['Tür · Sebep', 'm', 40], ['Kez', 's', 8, 'top'], ['Tutar', 'tl', 13, 'top bar']], $seb, $bi),
                    _srSayfa('Personele Göre', 'İptal = ürün iptali + ödeme iptali.', [['Personel', 'm', 22], ['Kez', 's', 7, 'top'], ['İptal', 'tl', 12, 'top'], ['İkram', 'tl', 12, 'top'], ['İndirim', 'tl', 12, 'top'], ['Toplam', 'tl', 13, 'top bar']], $kis, $bi),
                ];
            case 'fire_kayip':
                $f = _fnrFire($sid, $bas, $bit); $c = _fnrCiro($sid, $bas, $bit); $ft = array_sum(array_column($f['fire'], 'maliyet')); $se = array_sum(array_map(fn ($x) => $x['maliyet'] < 0 ? -$x['maliyet'] : 0, $f['sayim'])); $sf = array_sum(array_map(fn ($x) => $x['maliyet'] > 0 ? $x['maliyet'] : 0, $f['sayim']));
                $mz = collect($f['fire'])->groupBy('malzeme')->map(fn ($g, $k) => [$k, $g->count(), round($g->sum('maliyet'), 2)])->sortByDesc(2)->values()->all();
                $sb = collect($f['fire'])->groupBy('sebep')->map(fn ($g, $k) => [$k, $g->count(), round($g->sum('maliyet'), 2)])->sortByDesc(2)->values()->all();
                $bf = !$d['fire'] && !$f['sayim'] ? _fnrBos('fire_yok', $dn) : _fnrBos('fire', $dn);
                return [
                    $pano([_srKpi('Fire maliyeti', $ft, 'tl', 'B91C1C'), _srKpi('Sayım eksiği maliyeti', $se, 'tl', 'DC2626'), _srKpi('Sayım fazlası', $sf, 'tl', 'D97706'), _srKpi('Toplam kayıp', round($ft + $se - $sf, 2), 'tl', '7F1D1D'), $kpiY('Ciroya oranı', $yuz($ft + $se - $sf, $c), '991B1B')],
                        [_srBlok('En çok fire veren malzemeler', 'bar', 'B91C1C', [['Malzeme', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], array_slice($mz, 0, 10))),
                         _srBlok('Fire sebepleri', 'pie', 'DC2626', [['Sebep', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], $sb))], !$f['fire'] && !$f['sayim'] ? $bf : null),
                    _srSayfa('Malzemeye Göre Fire', '', [['Malzeme', 'm', 26], ['Kayıt', 's', 8, 'top'], ['Maliyet', 'tl', 13, 'top bar']], $mz, $bf),
                    _srSayfa('Fire Kayıtları', '', [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Miktar', 's', 9], ['Kaynak', 'm', 18], ['Sebep', 'm', 24], ['Maliyet', 'tl', 12, 'top bar']], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['miktar'], $x['kaynak'], $x['sebep'], $x['maliyet']], $f['fire']), $bf),
                    _srSayfa('Sayım Farkları', 'Eksi maliyet = sayımda eksik çıkan (kayıp), artı = fazla.', [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Teorik', 's', 9], ['Sayılan', 's', 9], ['Fark', 's', 9], ['Maliyet', 'tl', 12, 'top']], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['teorik'], $x['sayilan'], $x['fark'], $x['maliyet']], $f['sayim']), 'Seçili dönemde sayım farkı yok.'),
                ];

            // ---------------- PERSONEL & VARLIK ----------------
            case 'personel_gider':
                $isc = _fnrIscilik($sid, $bas, $bit); $p = _fnrPl($sid, $bas, $bit); $ph = _fnrPersHareket($sid, $bas, $bit); $od = _fnrOdeme($sid, $bas, $bit);
                $tur = ['odeme' => 'Maaş / ödeme', 'avans' => 'Avans', 'prim' => 'Prim', 'ek_odeme' => 'Ek ödeme', 'kesinti' => 'Kesinti'];
                $kh = []; foreach ($ph as $x) { $kh[$x['ad']][$x['tur']] = ($kh[$x['ad']][$x['tur']] ?? 0) + $x['tutar']; }
                $ks = array_map(fn ($a, $v) => [$a, round($v['odeme'] ?? 0, 2), round($v['avans'] ?? 0, 2), round(($v['prim'] ?? 0) + ($v['ek_odeme'] ?? 0), 2), round($v['kesinti'] ?? 0, 2), round(($v['odeme'] ?? 0) + ($v['avans'] ?? 0), 2)], array_keys($kh), array_values($kh));
                usort($ks, fn ($a, $b) => $b[5] <=> $a[5]); $bm = !$d['maas'] ? _fnrBos('maas_yok', $dn) : null;
                return [
                    $pano([_srKpi('Hesaplanan işçilik maliyeti', $isc['toplam'], 'tl', '64748B'), $kpiY('İşçilik / net satış', $p['isc_y'], $p['isc_y'] !== null && $p['isc_y'] > $ay['hedef_isc'] ? 'DC2626' : '059669'),
                        _srKpi('Ödenen maaş', array_sum(array_column($ks, 1)), 'tl', '475569'), _srKpi('Verilen avans', array_sum(array_column($ks, 2)), 'tl', 'D97706'), _srKpi('Prim + ek ödeme', array_sum(array_column($ks, 3)), 'tl', '334155'),
                        _srKpi('Bahşiş (personele, gider değil)', array_sum(array_column($od, 'bahsis')), 'tl', '059669'), _srKpi('İşveren çarpanı', number_format($isc['carpan'], 2, ',', '.'), 'metin', '94A3B8')],
                        [_srBlok('En yüksek maliyetli 10 kişi', 'bar', '64748B', [['Personel', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($x) => [$x['ad'], $x['toplam'], ''], array_slice($isc['kisi'], 0, 10)))], $bm),
                    _srSayfa('Hesaplanan İşçilik Maliyeti', 'İK raporlarıyla aynı esas: aylık maaş /30 × kadro günü, günlük / saatlik × QR mesai (mesai yoksa ayda 26 gün / günde 8 saat varsayılır = tahmini), + prim / ek ödeme / ciro primi; × işveren çarpanı (İK Raporları > Hesap ayarları).',
                        [['Personel', 'm', 22], ['Rol', 'm', 10], ['Maaş Tipi', 'm', 9], ['Maaş', 'tl', 12], ['Prim / Ek', 'tl', 11, 'top'], ['Dönem Maliyeti', 'tl', 14, 'top bar'], ['QR Mesai Saati', 's', 10, 'top'], ['Not', 'm', 14]],
                        array_map(fn ($x) => [$x['ad'], $x['rol'], $x['tip'], $x['maas'], $x['prim'], $x['toplam'], $x['saat'], $x['tahmini'] ? 'Mesai yok, tahmini' : ''], $isc['kisi']), $bm ?? 'Maliyeti olan personel yok.'),
                    _srSayfa('Personel Ödemeleri', 'Personel & Maaş ekranından girilen hareketler; maaş ve avans ödemeleri Giderler\'e "maaş" olarak da düşer.', [['Personel', 'm', 22], ['Maaş / Ödeme', 'tl', 13, 'top'], ['Avans', 'tl', 12, 'top'], ['Prim / Ek', 'tl', 12, 'top'], ['Kesinti', 'tl', 11, 'top'], ['Toplam Çıkan Para', 'tl', 14, 'top bar']], $ks, _fnrBos('pers_hareket', $dn)),
                ];
            case 'amortisman':
                $am = _fnrAmortisman($sid); $gun = max(1, $dn['gun']); $kt = collect($am)->groupBy('kat')->map(fn ($g, $k) => [$k, $g->count(), round($g->sum('bedel'), 2), round($g->sum('net'), 2)])->sortByDesc(2)->values()->all();
                $ba = !$d['demirbas'] ? _fnrBos('demirbas_yok', $dn) : null;
                return [
                    $pano([_srKpi('Demirbaş alış bedeli', array_sum(array_column($am, 'bedel')), 'tl', '475569'), _srKpi('Birikmiş amortisman', array_sum(array_column($am, 'birikmis')), 'tl', '64748B'), _srKpi('Net defter değeri', array_sum(array_column($am, 'net')), 'tl', '334155'),
                        _srKpi('Yıllık amortisman', array_sum(array_map(fn ($x) => $x['aktif'] ? $x['yillik'] : 0, $am)), 'tl', '1E293B'), _srKpi('Dönem amortismanı', _fnrAmortDonem($sid, $gun), 'tl', '0F172A'), _srKpi('Tamamen itfa olan', count(array_filter($am, fn ($x) => $x['net'] <= 0)), 'sayi', '94A3B8')],
                        [_srBlok('Kategoriye göre net değer', 'pie', '475569', [['Kategori', 'm'], ['Net', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], array_filter($kt, fn ($x) => $x[3] > 0)))], $ba),
                    _srSayfa('Demirbaş Amortismanı', 'Normal amortisman, VUK faydalı ömür oranları (bilgisayar %25, mobilya / ekipman / taşıt %20, tesisat %10, bina %2). Tam yıl esası; kesin hesap için mali müşavir.',
                        [['Demirbaş', 'm', 24], ['Kategori', 'm', 12], ['Alış Tarihi', 't', 11], ['Alış Bedeli', 'tl', 13, 'top'], ['Oran %', 'y', 7], ['Yıllık', 'tl', 12, 'top'], ['Geçen Yıl', 's', 8], ['Birikmiş', 'tl', 12, 'top'], ['Net Değer', 'tl', 13, 'top bar'], ['Dönem Payı', 'tl', 11, 'top']],
                        array_map(fn ($x) => [$x['ad'], $x['kat'], $x['tarih'], $x['bedel'], (float) $x['oran'], $x['yillik'], $x['yil'], $x['birikmis'], $x['net'], $x['aktif'] ? round($x['yillik'] * $gun / 365, 2) : 0], $am), $ba ?? 'Demirbaş yok.'),
                    _srSayfa('Kategoriye Göre', '', [['Kategori', 'm', 18], ['Adet', 's', 7, 'top'], ['Alış Bedeli', 'tl', 13, 'top'], ['Net Değer', 'tl', 13, 'top bar']], $kt, $ba ?? 'Demirbaş yok.'),
                ];
            case 'bilanco':
                $nt = _fnrNakitTahmin($sid); $cari = _fnrCari($sid); $cs = _fnrCek($sid); $tb = _fnrTedBorc($sid); $am = _fnrAmortisman($sid);
                $kdvB = max(0, round(array_sum(array_column(_fnrKdv($sid, date('Y-m-01'), date('Y-m-d')), 'oden')), 2));
                $cekA = array_sum(array_map(fn ($c) => ($c->durum ?? '') === 'portfoyde' && $c->yon === 'alinan' ? (float) $c->tutar : 0, $cs)); $cekV = array_sum(array_map(fn ($c) => ($c->durum ?? '') === 'portfoyde' && $c->yon === 'verilen' ? (float) $c->tutar : 0, $cs));
                $v = [['Kasa', $nt['kasa']['tutar'], $nt['kasa']['not']], ['Banka', $nt['banka'], 'Aktif banka hesaplarının bakiyesi'], ['Müşteri alacakları (cari)', round(array_sum(array_map(fn ($x) => max(0, $x['bakiye']), $cari)), 2), 'Açık hesap bakiyeleri'],
                    ['Alınan çek / senet', round($cekA, 2), 'Portföyde'], ['Stok (maliyet)', _fnrStokDeger($sid), 'Malzeme stoku × güncel maliyet'], ['Demirbaş (net)', round(array_sum(array_column($am, 'net')), 2), 'Alış bedeli − birikmiş amortisman']];
                $b = [['Tedarikçi borcu', round($tb['toplam'], 2), 'Ödenmemiş alış faturaları'], ['Verilen çek / senet', round($cekV, 2), 'Portföyde'], ['Ödenecek KDV (bu ay, tahmini)', $kdvB, 'Bu ayın hesaplanan − indirilecek KDV\'si']];
                $tv = array_sum(array_column($v, 1)); $tbr = array_sum(array_column($b, 1));
                $sat = array_merge(array_map(fn ($x) => ['Varlık', $x[0], $x[1], $tv > 0 ? round($x[1] / $tv * 100, 1) : null, $x[2]], $v), array_map(fn ($x) => ['Borç', $x[0], $x[1], $tv > 0 ? round($x[1] / $tv * 100, 1) : null, $x[2]], $b), [['Özkaynak', 'Tahmini özkaynak (varlık − borç)', round($tv - $tbr, 2), $tv > 0 ? round(($tv - $tbr) / $tv * 100, 1) : null, 'Ortak sermayesi ve kredi borçları dahil değil']]);
                $tum = !$d['banka'] && !$d['alis'] && !$d['cari'] && !$d['demirbas'] && !$d['kasa'];
                return [
                    $pano([_srKpi('Toplam varlık', $tv, 'tl', '334155'), _srKpi('Toplam borç', $tbr, 'tl', 'DC2626'), _srKpi('Tahmini özkaynak', round($tv - $tbr, 2), 'tl', $tv - $tbr < 0 ? 'DC2626' : '059669'),
                        _srKpi('Cari oran (dönen varlık ÷ borç)', $tbr > 0 ? number_format(($tv - $v[5][1]) / $tbr, 2, ',', '.') : '—', 'metin', '475569'), _srKpi('Nakit (kasa + banka)', $nt['baslangic'], 'tl', '0D9488')],
                        [_srBlok('Varlıklar', 'pie', '334155', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($v, fn ($x) => $x[1] > 0))),
                         _srBlok('Borçlar', 'pie', 'DC2626', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($b, fn ($x) => $x[1] > 0)))],
                        $tum ? 'Bilanço için veri yok: kasa vardiyası, banka hesabı, cari, alış faturası ya da demirbaş girilmemiş. ' . _fnrBos('banka_yok', $dn) : null),
                    ['grafiksiz' => true] + _srSayfa('Özet Bilanço', 'Ekranlardaki kayıtlardan anlık özet; resmi bilanço değildir (ortak cari, kredi, SGK / vergi borçları, peşin giderler vb. yoktur).', [['Taraf', 'm', 10], ['Kalem', 'm', 30], ['Tutar', 'tl', 15], ['Varlığa %', 'y', 9], ['Kaynak / Not', 'm', 40]], $sat, null),
                ];
        }
        return [];
    }
    // Bütçe & hedef sayfası (Faz 2 ayarlarından)
    function _fnrButceSayfa($sid, array $dn, array $p)
    {
        $ay = _fnrAyar($sid); $gun = max(1, $dn['gun']); $gk = _fnrGiderKat(_fnrGider($sid, $dn['bas'], $dn['bit'])); $sat = [];
        foreach (_fnrGiderKatlar() as $k => $ad) { $b = round($ay['butce'][$k] * $gun / 30, 2); $g = $gk[$k] ?? 0; if (!$b && !$g) continue; $sat[] = [$ad, $b, $g, round($g - $b, 2), $b > 0 ? round($g / $b * 100, 1) : null]; }
        foreach (array_diff_key($gk, _fnrGiderKatlar()) as $k => $g) $sat[] = [_fnrGiderAd($k), 0, $g, $g, null];
        $hd = round($ay['ciro_hedef'] * $gun / 30, 2);
        if ($hd > 0) $sat[] = ['CİRO (hedef / gerçekleşen)', $hd, $p['ciro'], round($p['ciro'] - $hd, 2), round($p['ciro'] / $hd * 100, 1)];
        foreach ([['Gıda maliyeti % (hedef / gerçek)', $ay['hedef_fc'], $p['fc']], ['İşçilik % (hedef / gerçek)', $ay['hedef_isc'], $p['isc_y']], ['Ana maliyet % (hedef / gerçek)', $ay['hedef_prime'], $p['prime']]] as [$a, $h, $g]) if ($g !== null) $sat[] = [$a, $h, $g, round($g - $h, 1), $h > 0 ? round($g / $h * 100, 1) : null];
        return ['grafiksiz' => true] + _srSayfa('Bütçe & Hedef', 'Aylık bütçe / hedef × dönem günü ÷ 30. Gider satırlarında kullanım %100\'ü geçerse bütçe aşılmıştır; ciro satırında %100 altı hedefin gerisinde demektir. Yüzde satırlarında tutarlar yüzde puanıdır.',
            [['Kalem', 'm', 34], ['Bütçe / Hedef', 'tl', 14], ['Gerçekleşen', 'tl', 14], ['Fark', 'tl', 13], ['Kullanım %', 'y', 10, 'esik:100']], $sat, !$ay['butce_var'] ? _fnrBos('butce_yok', $dn) : 'Bütçe girilen kategoride gider yok.');
    }
    function _fnrNakitTahminSayfa(array $nt)
    {
        return ['grafiksiz' => true] + _srSayfa('4 Haftalık Nakit Tahmini', 'Başlangıç = banka + kasa (' . $nt['kasa']['not'] . '). Tahsilat = son 28 günün haftalık ortalaması (açık hesap hariç); olağan gider = son 28 günün fatura / vergi / diğer giderlerinin haftalık ortalaması. Vadesi geçmiş ödemeler 1. haftaya yazılır. Yeni mal alımları ve yıllık sabit giderler dahil değildir.',
            [['Hafta', 'm', 14], ['Açılış', 'tl', 13], ['Tahsilat', 'tl', 12, 'top'], ['Alınan Çek', 'tl', 11, 'top'], ['Tedarikçi', 'tl', 12, 'top'], ['Verilen Çek', 'tl', 11, 'top'], ['Sabit Gider', 'tl', 11, 'top'], ['Maaş', 'tl', 11, 'top'], ['Olağan Gider', 'tl', 11, 'top'], ['Kapanış', 'tl', 13]],
            array_map(fn ($x) => [date('d.m', strtotime($x['bas'])) . '–' . date('d.m', strtotime($x['bit'])), $x['acilis'], $x['tahsilat'], $x['cek_al'], $x['ted'], $x['cek_ver'], $x['sabit'], $x['maas'], $x['olagan'], $x['kapanis']], $nt['hafta']), null);
    }
}

// ---------- AI ----------
if (!function_exists('_fnrAiBaglam')) {
    function _fnrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $p = _fnrPl($sid, $bas, $bit); $o = _fnrPl($sid, $dn['obas'], $dn['obit']); $ay = _fnrAyar($sid);
        $od = _fnrOdemeOzet(_fnrOdeme($sid, $bas, $bit)); $ko = _fnrKasaOturum($sid, $bas, $bit); $tb = _fnrTedBorc($sid); $cari = _fnrCari($sid); $nt = _fnrNakitTahmin($sid); $ip = _fnrIptal($sid, $bas, $bit);
        $km = _fnrKomisyon($sid, $bas, $bit); $kdv = _fnrKdv($sid, $bas, $bit); $g = _fnrGider($sid, $bas, $bit); $go = _fnrGider($sid, $dn['obas'], $dn['obit']); $f = _fnrFire($sid, $bas, $bit);
        $plOz = fn ($x) => ['ciro_kdv_dahil' => $x['ciro'], 'net_satis' => $x['net'], 'indirim' => $x['indirim'], 'ikram' => $x['ikram'], 'smm' => $x['smm'], 'smm_tahmini_kisim' => $x['smm_tahmini'], 'brut_kar' => $x['brut_kar'], 'online_komisyon' => $x['komisyon'],
            'iscilik' => $x['iscilik'], 'isletme_giderleri' => $x['isletme_top'], 'favok' => $x['favok'], 'amortisman' => $x['amort'], 'net_kar' => $x['net_kar'], 'net_kar_marji_yuzde' => $x['marj'], 'gida_maliyeti_yuzde' => $x['fc'], 'iscilik_yuzde' => $x['isc_y'],
            'ana_maliyet_yuzde' => $x['prime'], 'adisyon' => $x['adisyon'], 'ortalama_hesap' => $x['ort'], 'kisi_basi' => $x['kisi_basi']];
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _fnrVeriDurumu($sid),
            'gelir_tablosu' => $plOz($p), 'onceki_gelir_tablosu' => $plOz($o), 'hedefler_yuzde' => ['gida' => $ay['hedef_fc'], 'iscilik' => $ay['hedef_isc'], 'ana_maliyet' => $ay['hedef_prime'], 'aylik_ciro_hedefi_tl' => $ay['ciro_hedef']],
            'odeme_tipleri' => array_map(fn ($x) => ['tip' => $x[0], 'tutar' => $x[2], 'pay_yuzde' => $x[3]], $od),
            'giderler' => ['toplam' => round(array_sum(array_column($g, 'tutar')), 2), 'onceki_toplam' => round(array_sum(array_column($go, 'tutar')), 2), 'kategori' => array_combine(array_map('_fnrGiderAd', array_keys(_fnrGiderKat($g))), array_values(_fnrGiderKat($g))) ?: [],
                'onceki_kategori' => array_combine(array_map('_fnrGiderAd', array_keys(_fnrGiderKat($go))), array_values(_fnrGiderKat($go))) ?: [], 'aylik_sabit_gider' => _fnrSabitAylik($sid)],
            'kasa' => ['kapanis' => count($ko), 'net_fark' => round(array_sum(array_column($ko, 'fark')), 2), 'acik_toplam' => round(array_sum(array_map(fn ($x) => min(0, $x['fark']), $ko)), 2), 'acik_veren_kapanis' => count(array_filter($ko, fn ($x) => $x['fark'] < -0.009)),
                'en_buyuk_aciklar' => array_map(fn ($x) => ['tarih' => $x['tarih'], 'kapatan' => $x['kapatan'], 'fark' => $x['fark']], array_slice(collect($ko)->sortBy('fark')->values()->all(), 0, 3))],
            'nakit' => ['banka' => $nt['banka'], 'kasa' => $nt['kasa']['tutar'], 'haftalik_ort_tahsilat' => $nt['tahsilat_hafta'], 'vadesi_gecmis_odeme' => $nt['gecikmis'], 'dort_hafta_en_dusuk_bakiye' => $nt['en_dusuk'], 'hafta_kapanislari' => array_column($nt['hafta'], 'kapanis')],
            'alacak' => ['musteri_alacagi' => round(array_sum(array_map(fn ($x) => max(0, $x['bakiye']), $cari)), 2), 'doksan_gun_ustu' => round(array_sum(array_map(fn ($x) => $x['bakiye'] > 0 ? $x['k'][3] : 0, $cari)), 2), 'en_borclu' => array_map(fn ($x) => ['cari' => $x['ad'], 'bakiye' => $x['bakiye']], array_slice($cari, 0, 5))],
            'tedarikci_borcu' => ['toplam' => round($tb['toplam'], 2), 'gecikmis' => round($tb['gecikmis'], 2), 'yedi_gun' => round($tb['hafta'], 2), 'en_cok_gecikmis' => array_map(fn ($t) => ['tedarikci' => $t['ad'], 'gecikmis' => round($t['gecikmis'], 2)], array_slice(array_values(array_filter($tb['tedarikciler'], fn ($t) => $t['gecikmis'] > 0)), 0, 5))],
            'kdv' => ['hesaplanan' => round(array_sum(array_column($kdv, 'hes')), 2), 'indirilecek' => round(array_sum(array_column($kdv, 'ind')), 2), 'odenecek_tahmini' => round(array_sum(array_column($kdv, 'oden')), 2)],
            'online' => ['ciro' => $km['ciro'], 'komisyon' => $km['toplam'], 'platform' => array_map(fn ($ad, $x) => ['platform' => $ad, 'ciro' => round($x['ciro'], 2), 'oran' => $x['oran']], array_keys($km['liste']), array_values($km['liste']))],
            'kayip' => ['iptal_ikram_indirim_tl' => round(array_sum(array_column($ip, 'tutar')), 2), 'tur' => collect($ip)->groupBy(fn ($x) => _fnrIptalTipAd($x['tip']))->map(fn ($l) => round($l->sum('tutar'), 2))->all(), 'fire_tl' => round(array_sum(array_column($f['fire'], 'maliyet')), 2)]];
    }
    function _fnrAiNabiz($sid, array $dn)
    {
        $b = _fnrAiBaglam($sid, $dn); $p = $b['gelir_tablosu']; $o = $b['onceki_gelir_tablosu']; $h = $b['hedefler_yuzde'];
        $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺'; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $dg = $o['net_kar'] != 0 ? _srYuzde($p['net_kar'], abs($o['net_kar'])) : null; $en = $b['nakit']['dort_hafta_en_dusuk_bakiye'];
        return [
            ['renk' => '#059669', 'kat' => 'karlilik', 'ikon' => 'account_balance', 'etiket' => 'Net kâr (tahmini)', 'deger' => $tl($p['net_kar']), 'alt' => 'Marj ' . $y($p['net_kar_marji_yuzde']) . ' · ciro ' . $tl($p['ciro_kdv_dahil']), 'sinif' => $p['net_kar'] < 0 ? 'kirmizi' : 'yesil',
                'ok' => $dg !== null ? ($p['net_kar'] >= $o['net_kar'] ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#047857', 'kat' => 'karlilik', 'ikon' => 'pie_chart', 'etiket' => 'Ana maliyet (gıda + işçilik)', 'deger' => $y($p['ana_maliyet_yuzde']), 'alt' => 'Gıda ' . $y($p['gida_maliyeti_yuzde']) . ' · işçilik ' . $y($p['iscilik_yuzde']) . ' · hedef ' . $y($h['ana_maliyet']), 'sinif' => $p['ana_maliyet_yuzde'] !== null && $p['ana_maliyet_yuzde'] > $h['ana_maliyet'] ? 'kirmizi' : 'yesil'],
            ['renk' => '#0EA5E9', 'kat' => 'tahsilat', 'ikon' => 'point_of_sale', 'etiket' => 'Kasa sayım farkı', 'deger' => $b['kasa']['kapanis'] ? $tl($b['kasa']['net_fark']) : '—', 'alt' => $b['kasa']['kapanis'] ? $b['kasa']['kapanis'] . ' kapanış · ' . $b['kasa']['acik_veren_kapanis'] . ' açık verdi' : 'Kasa vardiyası kapatılmamış', 'sinif' => $b['kasa']['net_fark'] < -0.009 ? 'kirmizi' : ($b['kasa']['kapanis'] ? 'yesil' : '')],
            ['renk' => '#7C3AED', 'kat' => 'alacak', 'ikon' => 'local_shipping', 'etiket' => 'Gecikmiş tedarikçi borcu', 'deger' => $tl($b['tedarikci_borcu']['gecikmis']), 'alt' => 'Toplam borç ' . $tl($b['tedarikci_borcu']['toplam']) . ' · 7 günde ' . $tl($b['tedarikci_borcu']['yedi_gun']), 'sinif' => $b['tedarikci_borcu']['gecikmis'] > 0 ? 'turuncu' : ''],
            ['renk' => '#0D9488', 'kat' => 'nakit', 'ikon' => 'query_stats', 'etiket' => '4 haftada en düşük nakit', 'deger' => $en !== null ? $tl($en) : '—', 'alt' => 'Bugün banka + kasa ' . $tl($b['nakit']['banka'] + $b['nakit']['kasa']), 'sinif' => $en !== null && $en < 0 ? 'kirmizi' : 'yesil'],
            ['renk' => '#E11D48', 'kat' => 'vergi', 'ikon' => 'receipt_long', 'etiket' => 'Ödenecek KDV (tahmini)', 'deger' => $tl($b['kdv']['odenecek_tahmini']), 'alt' => 'Hesaplanan ' . $tl($b['kdv']['hesaplanan']) . ' · indirilecek ' . $tl($b['kdv']['indirilecek']), 'sinif' => $b['kdv']['odenecek_tahmini'] > 0 ? 'turuncu' : ''],
        ];
    }
}

// ---------- Hedef & bütçe ayar kartı (Faz 2) ----------
if (!function_exists('_fnrUst')) {
    function _fnrUst($kat, $sid)
    {
        if (!in_array($kat, ['donemsel', 'karlilik', 'gelir', 'gider'], true)) return '';
        $a = _fnrAyar($sid); $e = fn ($x) => htmlspecialchars((string) $x, ENT_QUOTES); $n = fn ($x) => $e(rtrim(rtrim(number_format((float) $x, 2, ',', ''), '0'), ','));
        $inp = 'width:78px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:5px 7px;color:var(--ink)';
        $lbl = fn ($ad, $ad2, $v, $son = '', $title = '') => '<label style="display:inline-flex;align-items:center;gap:6px"' . ($title ? ' title="' . $e($title) . '"' : '') . '>' . $e($ad) . ' <input name="' . $e($ad2) . '" value="' . $n($v) . '" inputmode="decimal" style="' . $inp . '">' . $son . '</label>';
        $but = ''; foreach (_fnrGiderKatlar() as $k => $ad) $but .= $lbl($ad, 'butce[' . $k . ']', $a['butce'][$k], ' ₺');
        $kom = ''; foreach (_fnrPlatformlar() as $k => $ad) $kom .= $lbl($ad, 'kom[' . $k . ']', $a['kom'][$k], ' %');
        $ozet = 'Gıda %' . $n($a['hedef_fc']) . ' · işçilik %' . $n($a['hedef_isc']) . ' · ana maliyet %' . $n($a['hedef_prime']) . ($a['ciro_hedef'] > 0 ? ' · aylık ciro hedefi ' . number_format($a['ciro_hedef'], 0, ',', '.') . ' ₺' : '') . (array_sum($a['butce']) > 0 ? ' · aylık gider bütçesi ' . number_format(array_sum($a['butce']), 0, ',', '.') . ' ₺' : ' · bütçe girilmedi');
        return '<details style="padding:11px 16px;border-radius:16px;background:var(--card);border:1px solid var(--line);font-size:12.5px;color:var(--sub2)">'
            . '<summary style="cursor:pointer;display:flex;align-items:center;gap:8px;flex-wrap:wrap;list-style:none"><span class="material-icons-outlined" style="font-size:18px;color:#059669">tune</span><b style="color:var(--ink)">Hedef & bütçe ayarları</b><span style="color:var(--sub)">' . $e($ozet) . '</span></summary>'
            . '<form method="POST" action="/finans-raporlar/ayar" style="display:flex;flex-direction:column;gap:12px;margin-top:12px">'
            . '<input type="hidden" name="_token" value="' . $e(csrf_token()) . '"><input type="hidden" name="geri" value="' . $e(request()->getRequestUri()) . '">'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center"><b style="color:var(--ink);min-width:110px">Hedefler</b>'
            . $lbl('Gıda maliyeti', 'hedef_fc', $a['hedef_fc'], ' %', 'SMM ÷ net satış; restoranlarda genelde %28-35.') . $lbl('İşçilik', 'hedef_isc', $a['hedef_isc'], ' %', 'İşçilik maliyeti ÷ net satış; genelde %25-35.')
            . $lbl('Ana maliyet', 'hedef_prime', $a['hedef_prime'], ' %', 'Gıda + işçilik; genelde %60-65.') . $lbl('Aylık ciro hedefi', 'ciro_hedef', $a['ciro_hedef'], ' ₺') . $lbl('Maaş ödeme günü', 'maas_gunu', $a['maas_gunu'], '', 'Nakit tahmininde aylık maaşların çıkacağı gün (1-28).') . '</div>'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center"><b style="color:var(--ink);min-width:110px">Aylık gider bütçesi</b>' . $but . '</div>'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center"><b style="color:var(--ink);min-width:110px">Platform komisyonu</b>' . $kom . '</div>'
            . '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><button type="submit" style="border:0;border-radius:9px;padding:7px 13px;font-weight:800;background:#059669;color:#fff;cursor:pointer">Kaydet</button>'
            . '<span style="font-size:11.5px;color:var(--sub)">Bütçe ve hedefler aylıktır; raporlar seçili dönemin gün sayısına oranlar. Komisyon oranlarını platform sözleşmenizdeki oranlarla güncelleyin.</span></div></form></details>';
    }
}

// ---------- Modül kaydı ----------
_drKaydet('finans', [
    'ad' => 'Finans', 'baslik' => 'Finans Raporları', 'yol' => '/finans-raporlar', 'grup' => 'Finans / Muhasebe', 'depKodlari' => ['finans'],
    'erisim' => fn ($p) => function_exists('_panelYetkiVar') && (_panelYetkiVar($p, 'gor_muhasebe') || _panelYetkiVar($p, 'gor_finans')), // finans ekranlarını zaten görenler
    'katalog' => '_fnrKatalog', 'kategoriler' => '_fnrKategoriler', 'veri' => '_fnrVeriDurumu', 'rapor' => '_fnrRapor',
    'ust' => fn ($kat, $sid) => _fnrUst($kat, $sid),
    'olcek' => ['kucuk' => ['donemsel', 'karlilik', 'tahsilat', 'ai'], 'orta' => ['gelir', 'gider', 'nakit', 'alacak']],
    'modAd' => ['satis' => 'Satış (adisyon)', 'odeme' => 'Ödeme kayıtları', 'gider' => 'Giderler', 'alis' => 'Alış faturaları', 'recete' => 'Reçete maliyeti', 'maas' => 'Personel maaşı', 'kasa' => 'Kasa kapanışı',
        'kasa_hareket' => 'Kasa hareketleri', 'cari' => 'Cari hareketleri', 'banka' => 'Banka hesabı', 'banka_hareket' => 'Banka hareketi', 'cek' => 'Çek / senet', 'sabit' => 'Tekrarlayan gider', 'demirbas' => 'Demirbaş bedeli',
        'iptal' => 'İptal / ikram', 'fire' => 'Fire', 'pers_hareket' => 'Personel ödemeleri', 'online' => 'Online platform siparişi', 'butce' => 'Bütçe / hedef'],
    'modLink' => ['satis' => ['/pos', 'POS\'ta adisyon kapatma'], 'odeme' => ['/pos', 'POS\'ta ödeme alma'], 'gider' => ['/gider', 'Giderler'], 'alis' => ['/alis-fatura', 'Alış Faturası'], 'recete' => ['/recete', 'Reçeteler + malzeme maliyeti'],
        'maas' => ['/personel', 'Personel kartında maaş'], 'kasa' => ['/kasa', 'Kasa (Vardiya): Kasa Aç / Kasa Kapat'], 'kasa_hareket' => ['/kasa', 'Açık kasa vardiyası'], 'cari' => ['/cari', 'Cari Hesaplar'], 'banka' => ['/banka', 'Banka Hesapları'],
        'banka_hareket' => ['/banka', 'Banka hesabına hareket ekle'], 'cek' => ['/cek-senet', 'Çek / Senet'], 'sabit' => ['/tekrarlayan-gider', 'Tekrarlayan Giderler'], 'demirbas' => ['/demirbas', 'Demirbaş kartında alış bedeli'],
        'iptal' => [null, 'POS\'ta iptal / ikram / indirim'], 'fire' => ['/stok', 'Stok > Fire girişi'], 'pers_hareket' => ['/personel', 'Personel > maaş / avans / prim'], 'online' => ['/paket', 'Paket / platform siparişleri'], 'butce' => [null, 'Raporun üstündeki "Hedef & bütçe ayarları"']],
    'ai' => [
        'gerekli' => ['satis', 'odeme', 'gider', 'recete', 'maas', 'kasa', 'alis'],
        'hero' => ['İşletmenin parasını 20 saniyede özetleyeyim.', 'Gelir tablosu ve kâr marjı, gıda ve işçilik maliyeti, ödeme tipleri ve kasa farkları, giderler ve bütçe, nakit tahmini, alacak-borç, KDV ve iptal-fire kayıplarını okur.'],
        'sorular' => ['Bu dönem neden daha az kâr ettim?', 'Gıda ve işçilik maliyetim hedefin üstünde mi?', 'Önümüzdeki 4 haftada nakit sıkışır mıyım?', 'Hangi giderlerim kontrolden çıkıyor?', 'Kasa farkları ve iptaller kayıp-kaçak işareti mi?', 'Önce hangi tedarikçiye ödeme yapmalıyım?'],
        'adimlar' => ['Satış ve ödeme kayıtları okunuyor…', 'Gelir tablosu çıkarılıyor…', 'Gıda, işçilik ve giderler hesaplanıyor…', 'Kasa farkları ve kayıplar inceleniyor…', 'Nakit, alacak ve borçlar kontrol ediliyor…', 'Öneriler yazılıyor…'],
        'selam' => 'finans',
        'sys' => 'Sen bir restoranın finansını patron ve muhasebe sorumlusu için analiz eden deneyimli bir restoran finans analisti ve mali kontrolörsün. Restoran ölçütlerini bil: gıda maliyeti (SMM ÷ net satış) genelde %28-35, işçilik %25-35, ana maliyet (gıda + işçilik) %60-65, net kâr marjı %5-15; '
            . 'kasa sayım farkı, iptal-ikram-indirim ve fire kayıp-kaçak göstergesidir. Net satış KDV hariçtir; kâr rakamları işletmenin girdiği kayıtlardan tahmini çıkarılır, resmi mali tablo değildir (SMM\'nin tahmini kısmı ve işçilik tahmini varsa bunu belirt). '
            . 'Vergi konusunda kesin hüküm verme, mali müşavire teyit ettirilmesini öner. Nakit tahmininde vadesi geçmiş borçları ve en düşük bakiyeyi öne çıkar. Kişileri suçlayıcı dil kullanma.',
        'baglam' => '_fnrAiBaglam',
        'veriVar' => fn ($b) => ($b['gelir_tablosu']['ciro_kdv_dahil'] ?? 0) > 0 || ($b['giderler']['toplam'] ?? 0) > 0,
        'nabiz' => '_fnrAiNabiz',
    ],
]);

// Hedef & bütçe ayarı kaydı (Faz 2 — şube ayarı 'fnr_ayar' JSON)
Route::post('/finans-raporlar/ayar', function (Request $r) {
    if (!_drErisim('finans')) return redirect('/pos-giris');
    $sid = _webSubeId(); $sayi = fn ($v, $mx) => max(0, min($mx, (float) str_replace(',', '.', (string) $v)));
    $v = ['hedef_fc' => $sayi($r->input('hedef_fc', 30), 100), 'hedef_isc' => $sayi($r->input('hedef_isc', 30), 100), 'hedef_prime' => $sayi($r->input('hedef_prime', 62), 100), 'ciro_hedef' => $sayi($r->input('ciro_hedef', 0), 1e10),
        'maas_gunu' => max(1, min(28, (int) $r->input('maas_gunu', 1))), 'butce' => [], 'kom' => []];
    foreach (array_keys(_fnrGiderKatlar()) as $k) $v['butce'][$k] = $sayi($r->input('butce.' . $k, 0), 1e10);
    foreach (array_keys(_fnrPlatformlar()) as $k) $v['kom'][$k] = $sayi($r->input('kom.' . $k, 0), 60);
    if (function_exists('_subeAyarYaz')) _subeAyarYaz($sid, 'fnr_ayar', json_encode($v));
    $geri = (string) $r->input('geri', '/finans-raporlar'); if (!str_starts_with($geri, '/finans-raporlar')) $geri = '/finans-raporlar';
    return redirect($geri)->with('ok', 'Finans hedef ve bütçe ayarları kaydedildi.');
});

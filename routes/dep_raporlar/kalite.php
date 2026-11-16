<?php
// =====================================================================
// KALİTE / HACCP RAPORLAR & ANALİZLER — /kalite-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Mutfak raporları mutfağın kendi defterine (sıcaklık / fire / açılış-kapanış) bakar; burası İŞLETMENİN TAMAMI için gıda güvenliği:
// Veri: departman_isler (departman_kod='kalite': kontrol listesi + sıcaklık ölçümü + olay/DÖF), mutfak_kayitlari (sıcaklık, mutfak defteri),
//       kalite_kayitlari (şahit numune, ilaçlama, kalibrasyon, tedarikçi belgesi, denetim), urunler.alerjenler (alerjen matrisi),
//       stok_partileri (SKT / lot), stok_hareketleri (fire / imha), mal_kabul_kalemleri (red, lot, SKT), personel_belge / personel_egitim
//       (sağlık-hijyen belgesi, hijyen eğitimi), memnuniyet + degerlendirmeler (kalite kaynaklı müşteri şikayeti), adisyonlar (açık gün).
// Faz 2 gerekmez: ölçüm, DÖF açılış/kapanış ve kayıt defteri zaten zaman damgalı tutuluyor.
// Erişim: patron / müdür / süpervizör / Kalite sorumlusu / 'gor_kalite_rapor'.
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_klrEnsure')) {
    function _klrEnsure()
    {
        if (cache()->get('klr_ensure_v1')) return;
        foreach (['_departmanIsEnsure', '_kaliteKayitEnsure', '_mutfakDefterEnsure', '_kaliteAlerjenKolon'] as $f) if (function_exists($f)) { try { $f(); } catch (\Throwable $e) {} }
        cache()->put('klr_ensure_v1', 1, now()->addHours(12));
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_klrIsler')) {
    function _klrGun($x) { return $x ? substr((string) $x, 0, 10) : null; }
    function _klrGunler($bas, $bit) { $o = []; $son = min($bit, date('Y-m-d')); for ($t = strtotime($bas); $t <= strtotime($son); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _klrKalan($tarih) { return $tarih ? (int) round((strtotime($tarih) - strtotime(date('Y-m-d'))) / 86400) : null; }
    function _klrTakvimDurum($son, $esik = 30) { if (!$son) return 'Tarih girilmemiş'; $k = _klrKalan($son); return $k < 0 ? 'Süresi geçti' : ($k <= $esik ? 'Yaklaşıyor' : 'Geçerli'); }
    function _klrSayi($v) { if ($v === null || $v === '') return null; if (preg_match('/-?\d+(?:[.,]\d+)?/', (string) $v, $m)) return (float) str_replace(',', '.', $m[0]); return null; }
    function _klrSonucAd($s) { return ['zamaninda' => 'Zamanında yapıldı', 'gec' => 'Geç yapıldı', 'kacirildi' => 'Yapılmadı (gecikti)', 'bekliyor' => 'Bekliyor', 'iptal' => 'İptal'][$s] ?? $s; }
    function _klrTipAd($t) { return ['kontrol' => 'Kontrol', 'olay' => 'Uygunsuzluk / olay', 'gorev' => 'Görev', 'kayit' => 'Kayıt', 'ariza' => 'Arıza', 'bakim' => 'Bakım'][$t] ?? ($t ?: '—'); }
    function _klrTekrarAd($t) { return ['gunluk' => 'Günlük', 'haftalik' => 'Haftalık', 'aylik' => 'Aylık'][$t] ?? 'Tek seferlik'; }
    function _klrSicakMi($baslik) { return function_exists('_kaliteSicaklikLimit') ? _kaliteSicaklikLimit($baslik) !== null : (bool) preg_match('/s[ıi]cakl[ıi]k|°c/iu', (string) $baslik); }
    function _klrLimitMetin($lim) { if (!$lim) return '—'; return $lim[0] <= -99 ? '≤ ' . $lim[1] . ' °C' : ($lim[1] >= 95 ? '≥ ' . $lim[0] . ' °C' : $lim[0] . ' – ' . $lim[1] . ' °C'); }
    // Kalite departmanının iş / kontrol / olay kayıtları (dönemle ilgili olanlar: planı, açılışı ya da tamamlanması dönemde olan + hâlâ açık olanlar)
    function _klrIsNorm($x)
    {
        $bugun = date('Y-m-d'); $son = _klrGun($x->son_tarih); $ac = _klrGun($x->created_at); $tm = _klrGun($x->tamamlanan_at); $durum = (string) $x->durum;
        if ($durum === 'tamam') $s = (!$son || ($tm && $tm <= $son)) ? 'zamaninda' : 'gec';
        elseif ($durum === 'iptal') $s = 'iptal';
        else $s = ($son && $son < $bugun) ? 'kacirildi' : 'bekliyor';
        $saat = ($x->tamamlanan_at && $x->created_at) ? round((strtotime((string) $x->tamamlanan_at) - strtotime((string) $x->created_at)) / 3600, 1) : null; if ($saat !== null && $saat < 0) $saat = null;
        $gec = $s === 'gec' ? (int) round((strtotime($tm) - strtotime($son)) / 86400) : ($s === 'kacirildi' ? (int) round((strtotime($bugun) - strtotime($son)) / 86400) : 0);
        return ['id' => (int) $x->id, 'kod' => (string) $x->departman_kod, 'tip' => (string) $x->tip, 'baslik' => (string) $x->baslik, 'aciklama' => (string) ($x->aciklama ?? ''), 'durum' => $durum, 'sonuc' => $s,
            'oncelik' => (string) ($x->oncelik ?: 'orta'), 'deger' => (string) ($x->deger ?? ''), 'plan' => $son ?: $ac, 'son' => $son, 'acilis' => $ac, 'acilis_ts' => (string) $x->created_at, 'tamam' => $tm,
            'tamam_saat' => $x->tamamlanan_at ? substr((string) $x->tamamlanan_at, 11, 5) : '', 'saat' => $saat, 'gecikme' => $gec, 'tekrar' => (string) ($x->tekrar ?? ''),
            'atanan' => (string) ($x->atanan_ad ?: ''), 'olusturan' => (string) ($x->olusturan_ad ?: ''), 'oto' => ($x->olusturan_ad ?? '') === 'HACCP otomatik'];
    }
    function _klrIsler($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('departman_isler')) return $c[$key] = [];
        try {
            $rows = DB::table('departman_isler')->where('sube_id', $sid)->where('departman_kod', 'kalite')->where(function ($q) use ($bas, $bit) {
                $q->whereBetween('son_tarih', [$bas, $bit])->orWhereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orWhereBetween('tamamlanan_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])
                  ->orWhere(function ($q2) use ($bit) { $q2->whereIn('durum', ['acik', 'yapiliyor'])->where('created_at', '<=', $bit . ' 23:59:59'); });
            })->orderBy('created_at')->get();
        } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $x) $o[] = _klrIsNorm($x);
        return $c[$key] = $o;
    }
    // Periyodik kontrol listesi uyumu: tekrarlı (günlük / haftalık / aylık) her kontrol için dönemde beklenen ve yapılan sayısı.
    // Periyodik iş tamamlanınca bir sonrakini ürettiği için yapılmayan günler ayrı kayıt olmaz → beklenen sayı takvimden hesaplanır.
    function _klrUyum($sid, $bas, $bit, $tipFiltre = null, $kod = 'kalite')
    {
        static $c = []; $key = "$sid|$bas|$bit|$kod|" . json_encode($tipFiltre); if (isset($c[$key])) return $c[$key];
        if (!_srTablo('departman_isler')) return $c[$key] = [];
        try { $q = DB::table('departman_isler')->where('sube_id', $sid)->where('departman_kod', $kod)->whereNotNull('tekrar')->where('created_at', '<=', $bit . ' 23:59:59'); if ($tipFiltre) $q->whereIn('tip', (array) $tipFiltre); $rows = $q->get(['baslik', 'tip', 'tekrar', 'created_at', 'tamamlanan_at', 'durum', 'atanan_ad', 'deger']); } catch (\Throwable $e) { $rows = collect(); }
        $g = []; $bitG = min($bit, date('Y-m-d'));
        foreach ($rows as $r) {
            $k = trim((string) $r->baslik); if ($k === '' || !in_array($r->tekrar, ['gunluk', 'haftalik', 'aylik'], true)) continue;
            $x = &$g[$k]; $x['baslik'] = $k; $x['tip'] = $r->tip; $x['tekrar'] = $r->tekrar; $ilk = _klrGun($r->created_at); if (empty($x['ilk']) || $ilk < $x['ilk']) $x['ilk'] = $ilk;
            if ($r->durum === 'tamam' && $r->tamamlanan_at) { $t = _klrGun($r->tamamlanan_at); if ($t >= $bas && $t <= $bitG) { $x['gunler'][$t] = 1; if ($r->atanan_ad) $x['kim'][$r->atanan_ad] = 1; } if (empty($x['son']) || $t > $x['son']) $x['son'] = $t; }
            unset($x);
        }
        $o = [];
        foreach ($g as $x) {
            $b = max($bas, $x['ilk']); if ($b > $bitG) continue;
            $gunler = _klrGunler($b, $bitG); $yap = array_keys($x['gunler'] ?? []);
            $bugun = date('Y-m-d'); // bugün / içinde bulunulan hafta-ay henüz bitmedi: yapılmadıysa "eksik" sayılmaz
            if ($x['tekrar'] === 'gunluk') { if (!in_array($bugun, $yap, true)) $gunler = array_values(array_diff($gunler, [$bugun])); $bek = count($gunler); $yapS = count($yap); $eksik = array_values(array_diff($gunler, $yap)); }
            else {
                $fmt = $x['tekrar'] === 'haftalik' ? 'o-W' : 'Y-m'; $per = array_unique(array_map(fn ($t) => date($fmt, strtotime($t)), $gunler)); $yp = array_unique(array_map(fn ($t) => date($fmt, strtotime($t)), $yap));
                if (!in_array(date($fmt), $yp, true)) $per = array_values(array_diff($per, [date($fmt)]));
                $bek = count($per); $yapS = count(array_intersect($per, $yp)); $eksik = array_values(array_diff($per, $yp));
            }
            $o[] = ['baslik' => $x['baslik'], 'ilk' => $b, 'tip' => $x['tip'], 'tekrar' => $x['tekrar'], 'sicak' => _klrSicakMi($x['baslik']), 'beklenen' => $bek, 'yapilan' => min($yapS, $bek), 'uyum' => $bek ? round(min($yapS, $bek) / $bek * 100, 1) : null,
                'eksik' => $eksik, 'son' => $x['son'] ?? null, 'kim' => implode(', ', array_keys($x['kim'] ?? [])), 'gunler' => $x['gunler'] ?? []];
        }
        usort($o, fn ($a, $b) => [$a['uyum'] ?? 101, $a['baslik']] <=> [$b['uyum'] ?? 101, $b['baslik']]);
        return $c[$key] = $o;
    }
    function _klrUyumOran(array $u) { $b = array_sum(array_column($u, 'beklenen')); return $b ? round(array_sum(array_column($u, 'yapilan')) / $b * 100, 1) : null; }
    // Sıcaklık ölçümleri: kalite kontrol listesi (departman_isler, değer girilerek tamamlanan) + mutfak kontrol defteri (mutfak_kayitlari)
    function _klrSicaklik($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $o = [];
        if (_srTablo('departman_isler') && function_exists('_kaliteSicaklikLimit')) {
            try { $rows = DB::table('departman_isler')->where('sube_id', $sid)->where('departman_kod', 'kalite')->where('durum', 'tamam')->whereNotNull('deger')->where('deger', '!=', '')->whereBetween('tamamlanan_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) {
                $lim = _kaliteSicaklikLimit($r->baslik); if (!$lim) continue;
                $u = function_exists('_kaliteSicaklikUygunMu') ? _kaliteSicaklikUygunMu($r->baslik, $r->deger) : null;
                $o[] = ['tarih' => _klrGun($r->tamamlanan_at), 'saat' => substr((string) $r->tamamlanan_at, 11, 5), 'ekipman' => trim((string) $r->baslik), 'deger' => (string) $r->deger, 'sayi' => _klrSayi($r->deger),
                    'limit' => _klrLimitMetin($lim), 'uygun' => $u, 'kaynak' => 'Kalite kontrol listesi', 'personel' => (string) ($r->atanan_ad ?: '—')];
            }
        }
        if (_srTablo('mutfak_kayitlari')) {
            try { $rows = DB::table('mutfak_kayitlari')->where('sube_id', $sid)->where('tip', 'sicaklik')->whereRaw('COALESCE(tarih, DATE(created_at)) >= ?', [$bas])->whereRaw('COALESCE(tarih, DATE(created_at)) <= ?', [$bit])->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) $o[] = ['tarih' => (string) ($r->tarih ?: _klrGun($r->created_at)), 'saat' => substr((string) $r->created_at, 11, 5), 'ekipman' => trim((string) $r->baslik) ?: '—', 'deger' => (string) $r->deger, 'sayi' => _klrSayi($r->deger),
                'limit' => (string) ($r->deger2 ?: '—'), 'uygun' => $r->sonuc === 'uygunsuz' ? false : ($r->sonuc === 'uygun' ? true : null), 'kaynak' => 'Mutfak kontrol defteri', 'personel' => (string) ($r->personel_ad ?: '—')];
        }
        usort($o, fn ($a, $b) => [$a['tarih'], $a['saat']] <=> [$b['tarih'], $b['saat']]);
        return $c[$key] = $o;
    }
    function _klrSicakSay(array $s) { $n = count($s); $u = count(array_filter($s, fn ($x) => $x['uygun'] === false)); return ['n' => $n, 'uygunsuz' => $u, 'oran' => $n ? round($u / $n * 100, 1) : null]; }
    // Uygunsuzluk / düzeltici faaliyet (DÖF): kalite 'olay' kayıtları (limit dışı sıcaklıkta otomatik açılanlar dahil)
    function _klrDof($sid, $bas, $bit) { return array_values(array_filter(_klrIsler($sid, $bas, $bit), fn ($x) => $x['tip'] === 'olay' && ($x['acilis'] >= $bas && $x['acilis'] <= $bit || in_array($x['durum'], ['acik', 'yapiliyor'], true)))); }
    function _klrKayitTipAd($t) { return ['numune' => 'Şahit numune', 'ilaclama' => 'Haşere / ilaçlama', 'kalibrasyon' => 'Kalibrasyon', 'tedarikci_belge' => 'Tedarikçi belgesi', 'denetim' => 'Denetim / teftiş'][$t] ?? $t; }
    function _klrKayit($sid, $tip)
    {
        static $c = []; $key = "$sid|" . json_encode($tip); if (isset($c[$key])) return $c[$key];
        if (!_srTablo('kalite_kayitlari')) return $c[$key] = [];
        try { $rows = DB::table('kalite_kayitlari')->where('sube_id', $sid)->whereIn('tip', (array) $tip)->orderBy('tarih')->orderBy('id')->get(); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['id' => (int) $r->id, 'tip' => (string) $r->tip, 'baslik' => trim((string) $r->baslik), 'deger' => (string) ($r->deger ?? ''), 'tarih' => _klrGun($r->tarih) ?: _klrGun($r->created_at),
            'son' => _klrGun($r->son_tarih), 'personel' => (string) ($r->personel_ad ?: '—'), 'not' => (string) ($r->not ?? '')];
        return $c[$key] = $o;
    }
    // Takvim (ilaçlama / kalibrasyon / tedarikçi belgesi): her kaydın en son yapılan hali; sonraki tarih girilmemişse varsayılan periyot
    function _klrTakvim($sid, $tip, $varsayilanGun)
    {
        $g = [];
        foreach (_klrKayit($sid, $tip) as $k) { $a = mb_strtolower($k['baslik'] . '|' . ($tip === 'tedarikci_belge' ? $k['deger'] : '')); $g[$a][] = $k; }
        $o = [];
        foreach ($g as $l) {
            $son = end($l); $tarihler = array_values(array_unique(array_filter(array_column($l, 'tarih')))); sort($tarihler); $ara = [];
            for ($i = 1; $i < count($tarihler); $i++) $ara[] = (int) round((strtotime($tarihler[$i]) - strtotime($tarihler[$i - 1])) / 86400);
            $sonraki = $son['son'] ?: ($son['tarih'] && $varsayilanGun ? date('Y-m-d', strtotime($son['tarih'] . " +$varsayilanGun day")) : null);
            $o[] = ['baslik' => $son['baslik'], 'deger' => $son['deger'], 'son_yapilis' => $son['tarih'], 'sonraki' => $sonraki, 'tahmini' => !$son['son'] && $sonraki, 'kalan' => _klrKalan($sonraki), 'durum' => _klrTakvimDurum($sonraki, $tip === 'ilaclama' ? 15 : 30),
                'kayit' => count($l), 'ort_ara' => $ara ? round(array_sum($ara) / count($ara)) : null, 'personel' => $son['personel'], 'not' => $son['not']];
        }
        usort($o, fn ($a, $b) => [$a['kalan'] ?? 99999] <=> [$b['kalan'] ?? 99999]);
        return $o;
    }
    // İşletmenin açık olduğu günler: ödenmiş adisyon olan günler (satış verisi yoksa takvimdeki tüm günler)
    function _klrAcikGunler($sid, $bas, $bit)
    {
        $tum = _klrGunler($bas, $bit);
        if (!_srTablo('adisyonlar')) return [$tum, false];
        try { $g = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereBetween('kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->pluck('kapanis')->map(fn ($x) => _klrGun($x))->unique()->sort()->values()->all(); } catch (\Throwable $e) { $g = []; }
        return $g ? [array_values(array_intersect($tum, $g)), true] : [$tum, false];
    }
    function _klrNumune($sid, $bas, $bit)
    {
        [$gunler, $satis] = _klrAcikGunler($sid, $bas, $bit); $n = [];
        foreach (_klrKayit($sid, 'numune') as $k) if ($k['tarih'] >= $bas && $k['tarih'] <= $bit) $n[$k['tarih']][] = $k;
        $eksik = array_values(array_filter($gunler, fn ($t) => empty($n[$t])));
        return ['gunler' => $gunler, 'satis' => $satis, 'kayit' => $n, 'eksik' => $eksik, 'alinan' => count(array_intersect($gunler, array_keys($n))), 'uyum' => $gunler ? round(count(array_intersect($gunler, array_keys($n))) / count($gunler) * 100, 1) : null];
    }
    function _klrUrunler($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('urunler') || !_srKolon('urunler', 'alerjenler')) return $c[$sid] = [];
        $kat = []; try { if (_srTablo('menu_kategorileri')) $kat = DB::table('menu_kategorileri')->pluck('ad', 'id')->all(); } catch (\Throwable $e) {}
        try { $q = DB::table('urunler')->where('sube_id', $sid); if (_srKolon('urunler', 'aktif')) $q->where('aktif', 1); $sel = ['id', 'ad', 'alerjenler']; if (_srKolon('urunler', 'kategori_id')) $sel[] = 'kategori_id'; $rows = $q->orderBy('ad')->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) { $a = array_values(array_filter(array_map('trim', explode(',', (string) $r->alerjenler)))); $o[] = ['id' => (int) $r->id, 'ad' => (string) $r->ad, 'kategori' => $kat[$r->kategori_id ?? 0] ?? 'Kategorisiz', 'alerjen' => $a, 'girilmis' => trim((string) $r->alerjenler) !== '']; }
        return $c[$sid] = $o;
    }
    function _klrAlerjenListe() { return function_exists('_kaliteAlerjenListe') ? _kaliteAlerjenListe() : ['Gluten', 'Kabuklu deniz ürünleri', 'Yumurta', 'Balık', 'Yer fıstığı', 'Soya', 'Süt (laktoz)', 'Sert kabuklu yemiş', 'Kereviz', 'Hardal', 'Susam', 'Sülfit', 'Acı bakla (lupin)', 'Yumuşakça']; }
    // SKT / lot: elde kalan partiler (anlık durum)
    function _klrPartiler($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('stok_partileri')) return $c[$sid] = [];
        try { $rows = DB::table('stok_partileri')->where('sube_id', $sid)->where('kalan_miktar', '>', 0.0001)->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $p) {
            $skt = _klrGun($p->skt); $k = _klrKalan($skt);
            $o[] = ['malzeme' => _srMalz($p->malzeme_id), 'kategori' => _srKat($p->malzeme_id), 'lot' => (string) ($p->lot_no ?? ''), 'skt' => $skt, 'kalan' => $k, 'miktar' => round((float) $p->kalan_miktar, 3), 'birim' => function_exists('_mrBirim') ? _mrBirim($p->malzeme_id) : '',
                'deger' => round((float) $p->kalan_miktar * (float) $p->birim_maliyet, 2), 'tedarikci' => $p->tedarikci_id ? _srTed($p->tedarikci_id) : '—', 'giris' => _klrGun($p->giris_tarihi) ?: _klrGun($p->created_at),
                'durum' => !$skt ? 'SKT girilmemiş' : ($k < 0 ? 'SKT geçti' : ($k <= 7 ? '7 gün içinde' : ($k <= 30 ? '30 gün içinde' : 'Normal')))];
        }
        usort($o, fn ($a, $b) => [$a['kalan'] ?? 99999] <=> [$b['kalan'] ?? 99999]);
        return $c[$sid] = $o;
    }
    // Fire / imha (dönem): SKT / bozulma kaynaklı olanlar işaretlenir
    function _klrFire($sid, $bas, $bit)
    {
        if (!_srTablo('stok_hareketleri')) return [];
        $fs = _srKolon('stok_hareketleri', 'fire_sebep');
        try { $rows = DB::table('stok_hareketleri')->where('sube_id', $sid)->where('tip', 'fire')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $h) {
            $seb = trim((string) (($fs ? $h->fire_sebep : null) ?: '')) ?: (trim((string) ($h->aciklama ?? '')) ?: 'Belirtilmemiş');
            $o[] = ['tarih' => _klrGun($h->created_at), 'malzeme' => _srMalz($h->malzeme_id), 'miktar' => abs((float) $h->miktar), 'birim' => function_exists('_mrBirim') ? _mrBirim($h->malzeme_id) : '', 'maliyet' => round(abs((float) $h->miktar) * (float) ($h->birim_maliyet ?? 0), 2),
                'sebep' => $seb, 'skt' => (bool) preg_match('/skt|son kullan|bozul|küf|kuf|ekşi|eksi|çürü|curu|tarih/iu', $seb)];
        }
        return $o;
    }
    // Mal kabul kalite kontrolü: red edilen kalemler, lot / SKT girilmeden kabul, kısa SKT ile kabul
    function _klrMalKabul($sid, $bas, $bit)
    {
        if (!_srTablo('mal_kabuller') || !_srTablo('mal_kabul_kalemleri')) return [];
        $sel = ['k.malzeme_id', 'k.gelen_miktar', 'k.kabul_miktar', 'm.tarih', 'm.created_at', 'm.tedarikci_id', 'm.irsaliye_no'];
        foreach (['red_miktar', 'red_sebep', 'lot_no', 'skt'] as $k) $sel[] = _srKolon('mal_kabul_kalemleri', $k) ? 'k.' . $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('mal_kabul_kalemleri as k')->join('mal_kabuller as m', 'k.mal_kabul_id', '=', 'm.id')->where('m.sube_id', $sid)->whereRaw('COALESCE(m.tarih, DATE(m.created_at)) >= ?', [$bas])->whereRaw('COALESCE(m.tarih, DATE(m.created_at)) <= ?', [$bit])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $t = _klrGun($r->tarih) ?: _klrGun($r->created_at); $skt = _klrGun($r->skt); $kalan = $skt ? (int) round((strtotime($skt) - strtotime($t)) / 86400) : null;
            $o[] = ['tarih' => $t, 'malzeme' => _srMalz($r->malzeme_id), 'tedarikci' => $r->tedarikci_id ? _srTed($r->tedarikci_id) : 'Tedarikçisiz', 'irsaliye' => (string) ($r->irsaliye_no ?? ''), 'gelen' => (float) $r->gelen_miktar, 'kabul' => (float) $r->kabul_miktar,
                'red' => (float) ($r->red_miktar ?? 0), 'sebep' => (string) ($r->red_sebep ?? ''), 'lot' => (string) ($r->lot_no ?? ''), 'skt' => $skt, 'skt_gun' => $kalan];
        }
        return $o;
    }
    // Personel sağlık / hijyen belgesi + hijyen eğitimi (aktif personel)
    function _klrHijyen($sid)
    {
        if (!_srTablo('personeller')) return [];
        $dep = []; try { if (_srTablo('departmanlar')) $dep = DB::table('departmanlar')->pluck('ad', 'id')->all(); } catch (\Throwable $e) {}
        try { $per = DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->where('rol', '!=', 'sahip')->orderBy('ad')->get(); } catch (\Throwable $e) { $per = collect(); }
        $belge = []; $egitim = [];
        if (_srTablo('personel_belge')) { try { foreach (DB::table('personel_belge')->where('sube_id', $sid)->whereIn('tip', ['saglik', 'hijyen'])->get() as $b) $belge[(int) $b->personel_id][$b->tip][] = _klrGun($b->son_gecerlilik) ?: 'tarihsiz'; } catch (\Throwable $e) {} }
        if (_srTablo('personel_egitim')) { try { foreach (DB::table('personel_egitim')->where('sube_id', $sid)->get() as $e2) if (preg_match('/hijyen|haccp|gıda|gida|alerjen|iso 22000/iu', (string) $e2->konu)) $egitim[(int) $e2->personel_id][] = _klrGun($e2->egitim_tarihi) ?: ''; } catch (\Throwable $e) {} }
        $rolAd = ['mudur' => 'Müdür', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak', 'satinalmaci' => 'Satın Alma'];
        $o = [];
        foreach ($per as $p) {
            $d = ['ad' => (string) $p->ad, 'dep' => $dep[$p->departman_id ?? 0] ?? ($rolAd[$p->rol] ?? $p->rol), 'gida' => in_array($p->rol, ['mutfak', 'garson'], true)];
            foreach (['saglik', 'hijyen'] as $tip) {
                $l = $belge[(int) $p->id][$tip] ?? []; $tarihli = array_filter($l, fn ($x) => $x !== 'tarihsiz'); $son = $tarihli ? max($tarihli) : null;
                $d[$tip] = $son; $d[$tip . '_durum'] = !$l ? 'Belge yok' : (!$son ? 'Tarihsiz' : _klrTakvimDurum($son));
            }
            $eg = array_filter($egitim[(int) $p->id] ?? []); $d['egitim'] = $eg ? max($eg) : null;
            $o[] = $d;
        }
        return $o;
    }
    // Kalite kaynaklı müşteri geri bildirimi: QR memnuniyette Yemek / Temizlik seçilen ya da hijyen-bozulma anlatan; değerlendirmede lezzet 1-2
    function _klrKaliteKelime($t) { return (bool) preg_match('/bayat|bozuk|bozul|çiğ|pis |kıl |kil |saç|sac |böcek|bocek|haşere|hasere|kirli|pis|mide|zehir|hijyen|koku|ekşi|eksi|küf|kuf|soğuk geldi|soguk geldi|yanık|yanik/iu', ' ' . $t . ' '); }
    function _klrSikayet($sid, $bas, $bit)
    {
        $o = []; $top = 0;
        if (_srTablo('memnuniyet')) {
            try { $rows = DB::table('memnuniyet')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) {
                $top++; $seb = array_filter(array_map('trim', explode(',', (string) $r->sebepler))); $konu = array_values(array_intersect($seb, ['Yemek', 'Temizlik'])); $kel = _klrKaliteKelime((string) $r->not);
                if (!$konu && !$kel) continue;
                $o[] = ['tarih' => _klrGun($r->created_at), 'kaynak' => 'QR memnuniyet', 'masa' => (string) ($r->masa_ad ?: '—'), 'puan' => (int) $r->puan, 'konu' => $konu ? implode(', ', $konu) : 'Yorumda hijyen / bozulma', 'not' => (string) ($r->not ?? ''),
                    'durum' => $r->durum === 'cozuldu' ? 'Çözüldü' : 'Bekliyor', 'cozuldu' => $r->durum === 'cozuldu'];
            }
        }
        if (_srTablo('degerlendirmeler')) {
            try { $rows = DB::table('degerlendirmeler')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) {
                $top++; $lez = (int) ($r->lezzet ?? 0); $kel = _klrKaliteKelime((string) ($r->yorum ?? ''));
                if (!($lez >= 1 && $lez <= 2) && !$kel) continue;
                $o[] = ['tarih' => _klrGun($r->created_at), 'kaynak' => 'Değerlendirme', 'masa' => '—', 'puan' => (int) $r->puan, 'konu' => $lez >= 1 && $lez <= 2 ? 'Lezzet ' . $lez . '/5' : 'Yorumda hijyen / bozulma', 'not' => (string) ($r->yorum ?? ''), 'durum' => '—', 'cozuldu' => false];
            }
        }
        usort($o, fn ($a, $b) => strcmp($b['tarih'], $a['tarih']));
        return ['liste' => $o, 'toplam' => $top];
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_klrVeriDurumu')) {
    function _klrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t)->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $kal = fn ($tip) => $say('kalite_kayitlari', null, fn ($q) => $q->where('tip', $tip));
        $u = _klrUrunler($sid);
        $d = ['kontrol' => $say('departman_isler', 'created_at', fn ($q) => $q->where('departman_kod', 'kalite')->where('tip', 'kontrol')),
            'sicaklik' => $say('departman_isler', 'created_at', fn ($q) => $q->where('departman_kod', 'kalite')->where('tip', 'kontrol')->where('baslik', 'like', '%cakl%')) + $say('mutfak_kayitlari', 'created_at', fn ($q) => $q->where('tip', 'sicaklik')),
            'olay' => $say('departman_isler', 'created_at', fn ($q) => $q->where('departman_kod', 'kalite')->where('tip', 'olay')),
            'numune' => $kal('numune'), 'ilaclama' => $kal('ilaclama'), 'kalibrasyon' => $kal('kalibrasyon'), 'tedarikci_belge' => $kal('tedarikci_belge'), 'denetim' => $kal('denetim'),
            'urun' => count($u), 'alerjen' => count(array_filter($u, fn ($x) => $x['girilmis'])),
            'parti' => $say('stok_partileri', 'created_at'), 'fire' => $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('tip', 'fire')),
            'mal_kabul' => $say('mal_kabuller', 'created_at'), 'belge' => $say('personel_belge', null, fn ($q) => $q->whereIn('tip', ['saglik', 'hijyen'])),
            'memnuniyet' => $say('memnuniyet', 'created_at') + $say('degerlendirmeler', 'created_at'), 'satis' => $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi'))];
        return $d;
    }
    function _klrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'kontrol_yok' => 'Kalite kontrol listesi yok. Kontrol listesi uyumu, Kalite / HACCP > Kontrol Listesi (/kalite) ekranındaki "Hızlı ekle" şablonlarından (buzdolabı, dondurucu, sıcak tutma, el yıkama, yüzey temizliği) GÜNLÜK tekrarlı kontrol açılıp her gün "Tamam" denince ölçülür.',
            'kontrol' => 'Seçili dönemde (' . $donem . ') takip edilen tekrarlı kalite kontrolü yok.',
            'sicaklik_yok' => 'Sıcaklık ölçümü yok. Ölçümler iki yerden gelir: Kalite / HACCP > Kontrol Listesi\'nde sıcaklık kontrolü °C değeri girilerek tamamlanınca ya da Mutfak > Kontrol Defteri > Sıcaklık sekmesinden. Limit dışı değer girilince sistem otomatik uygunsuzluk (DÖF) açar.',
            'sicaklik' => 'Seçili dönemde (' . $donem . ') sıcaklık ölçümü girilmemiş.',
            'uygunsuz' => 'Seçili dönemde (' . $donem . ') limit dışı sıcaklık yok.',
            'olay_yok' => 'Uygunsuzluk / düzeltici faaliyet (DÖF) kaydı yok. Kalite / HACCP > Kontrol Listesi\'nde "Uygunsuzluk / düzeltici aksiyon" ile açılır; limit dışı sıcaklıkta sistem kendisi açar. Kapatmak için kayıt "Tamam" yapılır.',
            'olay' => 'Seçili dönemde (' . $donem . ') açılan ya da açık kalan uygunsuzluk yok.',
            'numune_yok' => 'Şahit numune kaydı yok. Her servis gününde her menü grubundan numune alınıp 72 saat saklanmalı (Türk Gıda Kodeksi). Kalite / HACCP > Kayıt Defteri > Şahit Numune (/kalite-kayit?tip=numune) ekranından günlük girilir.',
            'numune' => 'Seçili dönemde (' . $donem . ') şahit numunesi eksik gün yok.',
            'ilaclama_yok' => 'İlaçlama / kalibrasyon kaydı yok. Haşere ilaçlaması (genelde ayda bir) ve termometre / terazi kalibrasyonu Kalite / HACCP > Kayıt Defteri\'nden (/kalite-kayit?tip=ilaclama ve ?tip=kalibrasyon) yapıldığı tarih ve "sonraki tarih" ile girilir.',
            'denetim_yok' => 'Denetim / teftiş kaydı yok. Bakanlık, belediye, müşteri ya da iç denetimler Kalite / HACCP > Kayıt Defteri > Denetim (/kalite-kayit?tip=denetim) ekranından girilir: "Değer" alanına puanı (örn. 86), nota bulguları yazın; sonraki denetim tarihi takvime girer.',
            'denetim' => 'Seçili dönemde (' . $donem . ') denetim kaydı yok.',
            'tedarikci_yok' => 'Tedarikçi belgesi kaydı yok. Tedarikçilerin işletme kayıt / onay belgesi, ISO 22000, helal, analiz raporu gibi belgeleri Kalite / HACCP > Kayıt Defteri > Tedarikçi Belgesi ekranından geçerlilik tarihiyle girilir.',
            'urun_yok' => 'Aktif menü ürünü yok ya da alerjen alanı henüz oluşmamış. Ürünler Menü ekranından eklenir; alerjenler Kalite / HACCP > Alerjen Matrisi (/alerjen) ekranından işaretlenir.',
            'alerjen' => 'Tüm aktif ürünlerde alerjen bilgisi girilmiş.',
            'parti_yok' => 'Partili (SKT / lot takipli) stok yok. Partiler Satın Alma > Mal Kabul sırasında SKT ve lot girilince oluşur; durumları Stok > SKT / Parti Takibi (/parti-takip) ekranında görünür.',
            'skt' => 'Elde SKT\'si geçmiş ya da 30 gün içinde dolacak parti yok.',
            'fire_yok' => 'Fire / imha kaydı yok. Fireler Mutfak > Kontrol Defteri > Fire ya da Stok > Fire ekranından sebebiyle girilir ("Bozulma / SKT geçti" seçilirse burada SKT kaynaklı imha olarak sayılır).',
            'fire' => 'Seçili dönemde (' . $donem . ') fire / imha kaydı yok.',
            'mal_kabul_yok' => 'Mal kabul kaydı yok. Satın Alma > Mal Kabul ekranında gelen ürünler kalem kalem kabul / red edilir; red miktarı ve sebebi, lot ve SKT burada tutulur.',
            'mal_kabul' => 'Seçili dönemde (' . $donem . ') mal kabul kaydı yok.',
            'belge' => 'Aktif personel yok.',
            'sikayet_yok' => 'Müşteri geri bildirimi yok. QR memnuniyet anketi ve ödeme sonrası değerlendirme açık olmalı; mutsuz müşteri "Yemek" ya da "Temizlik" seçerse kalite kaynaklı şikayet sayılır.',
            'sikayet' => 'Seçili dönemde (' . $donem . ') kalite kaynaklı müşteri şikayeti yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_klrKatalog')) {
    function _klrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Kalite Bülteni', 'Dün yapılan ve kaçırılan kontroller, sıcaklık ölçümleri ve limit dışılar, açık uygunsuzluklar, şahit numune, SKT\'si geçen partiler ve yaklaşan takvim.', 'today', '#16A34A', ['kontrol', 'sicaklik'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık HACCP Raporu', 'Kontrol listesi uyumu, sıcaklık uygunsuzluk oranı, DÖF açılış-kapanış, numune eksik günleri, SKT riskleri ve önceki haftayla kıyas.', 'date_range', '#15803D', ['kontrol', 'sicaklik', 'olay'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Gıda Güvenliği Raporu', 'Denetime hazır dosya: kontrol uyumu, sıcaklık, DÖF, numune, ilaçlama-kalibrasyon takvimi, denetimler, alerjen, mal kabul, imha, personel hijyen belgesi ve müşteri şikayetleri.', 'calendar_month', '#166534', ['kontrol', 'sicaklik', 'numune'], 'gecen_ay'],
            'kontrol_uyum' => ['kontrol', 'Kontrol Listesi Tamamlanma', 'Her tekrarlı kontrol (sıcaklık, el yıkama, yüzey temizliği…) için dönemde beklenen ve yapılan sayısı, uyum yüzdesi ve son yapılma.', 'checklist', '#059669', ['kontrol'], null],
            'kontrol_gun' => ['kontrol', 'Gün Gün Kontrol Çizelgesi', 'Günlük kontroller × günler: hangi gün hangi kontrol yapılmadı; HACCP denetiminde istenen çizelge.', 'calendar_view_month', '#047857', ['kontrol'], null],
            'kontrol_gecikme' => ['kontrol', 'Geciken & Kaçırılan Kontroller', 'Son tarihinden sonra yapılan ya da hâlâ yapılmamış kontroller ve görevler; kaç gün gecikti, kime atanmıştı.', 'alarm', '#B45309', ['kontrol'], null],
            'sicaklik_olcum' => ['sicaklik', 'Sıcaklık Ölçümleri & Uygunsuzluklar', 'Kontrol listesi ve mutfak defterindeki tüm ölçümler, limit (soğuk 0–4 °C, dondurucu ≤ −18 °C, sıcak tutma ≥ 63 °C) ve limit dışı olanlar.', 'thermostat', '#0284C7', ['sicaklik'], null],
            'sicaklik_ekipman' => ['sicaklik', 'Ekipman & Saat Analizi', 'Hangi dolap / ünite sık limit dışı çıkıyor, en düşük-en yüksek-ortalama değer; günün hangi saatinde ölçülüyor ve hangi saatte sorun çıkıyor.', 'kitchen', '#0369A1', ['sicaklik'], null],
            'dof' => ['dof', 'Uygunsuzluk & Düzeltici Faaliyet (DÖF)', 'Açılan, kapanan ve açık uygunsuzluklar; kapanma süresi, 48 saati aşanlar, sıcaklık kaynaklı otomatik açılanlar.', 'report_problem', '#DC2626', ['olay'], null],
            'hazirlik' => ['dof', 'Denetime Hazırlık Listesi', 'Bugün denetim gelse eksik çıkacaklar: açık DÖF, bekleyen ölçüm, limit dışı, SKT geçmiş parti, numune, alerjen, belge ve takvim; hazırlık puanı.', 'verified_user', '#B91C1C', ['kontrol'], 'bugun'],
            'numune' => ['kayit', 'Şahit Numune Uyumu', 'İşletmenin açık olduğu (satış yapılan) her gün numune alınmış mı; eksik günler ve 72 saatten kısa saklananlar.', 'science', '#7C3AED', ['numune'], null],
            'ilaclama' => ['kayit', 'İlaçlama & Kalibrasyon Takvimi', 'Haşere ilaçlaması ve ekipman kalibrasyonunun son yapılışı, sonraki tarihi, süresi geçenler ve iki uygulama arası ortalama gün.', 'pest_control', '#6D28D9', ['ilaclama'], null],
            'denetim' => ['kayit', 'Denetim Bulguları & Puanları', 'Dönemdeki denetimler, puanlar, bulgular, önceki denetime göre değişim ve sıradaki denetim.', 'fact_check', '#5B21B6', ['denetim'], null],
            'tedarikci_belge' => ['kayit', 'Tedarikçi Belge Geçerliliği', 'Tedarikçi onay / kayıt belgeleri, sertifikalar ve analiz raporları: geçerli, yaklaşan ve süresi geçenler.', 'workspace_premium', '#4C1D95', ['tedarikci_belge'], null],
            'alerjen' => ['gida', 'Alerjen Bilgisi & Matris', '14 majör alerjene göre menü matrisi; alerjen bilgisi girilmemiş aktif ürünler (kategoriye göre).', 'no_food', '#EA580C', ['urun'], null],
            'skt' => ['gida', 'SKT & Lot Riskleri', 'Elde kalan partiler: SKT\'si geçen, 7 / 30 gün içinde dolacak, SKT ya da lot girilmemiş olanlar ve risk altındaki stok değeri.', 'event_busy', '#C2410C', ['parti'], null],
            'imha' => ['gida', 'İmha & SKT Kaynaklı Fire', 'Dönemdeki fire / imha kayıtları; bozulma ve SKT kaynaklı olanların tutarı, malzemeye ve sebebe göre.', 'delete_forever', '#9A3412', ['fire'], null],
            'mal_kabul' => ['gida', 'Mal Kabul Kalite Kontrolü', 'Kabulde red edilen kalemler ve sebepleri, lot / SKT girilmeden kabul, kısa SKT ile kabul; tedarikçiye göre red oranı.', 'local_shipping', '#7C2D12', ['mal_kabul'], null],
            'hijyen_belge' => ['uyum', 'Personel Sağlık & Hijyen Belgeleri', 'Aktif personelin sağlık raporu ve hijyen eğitim belgesi: geçerli, yaklaşan, süresi geçen, hiç olmayan; gıdaya temas eden personel önce.', 'badge', '#0F766E', ['belge'], null],
            'sikayet' => ['uyum', 'Kalite Kaynaklı Müşteri Şikayetleri', 'QR memnuniyet ve değerlendirmelerden yemek / temizlik / hijyen kaynaklı şikayetler ve çözüm durumu.', 'sentiment_dissatisfied', '#0E7490', ['memnuniyet'], null],
        ];
    }
    function _klrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Günlük bülten, haftalık HACCP raporu ve denetime hazır aylık gıda güvenliği raporu.', '#16A34A'],
            'kontrol' => ['Kontrol Listesi', 'checklist', 'Tekrarlı kalite kontrollerinin tamamlanma oranı, gün gün çizelge, geciken ve kaçırılan kontroller.', '#059669'],
            'sicaklik' => ['Sıcaklık (Soğuk Zincir)', 'thermostat', 'Tüm ölçümler, limit dışılar, ekipman ve saat analizi.', '#0284C7'],
            'dof' => ['Uygunsuzluk & Denetime Hazırlık', 'report_problem', 'Düzeltici faaliyetler (DÖF) ve bugün denetim gelse eksik çıkacaklar.', '#DC2626'],
            'kayit' => ['Kayıt Defteri & Denetim', 'fact_check', 'Şahit numune, ilaçlama-kalibrasyon takvimi, denetim bulguları, tedarikçi belgeleri.', '#7C3AED'],
            'gida' => ['Ürün & Gıda Güvenliği', 'no_food', 'Alerjen matrisi, SKT / lot riskleri, imha ve mal kabul kalite kontrolü.', '#EA580C'],
            'uyum' => ['Personel & Müşteri', 'badge', 'Personel sağlık-hijyen belgeleri ve kalite kaynaklı müşteri şikayetleri.', '#0F766E'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı gıda güvenliği özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_klrSayfaUyum')) {
    function _klrSayfaUyum(array $u, $bos, $ad = 'Kontrol Uyumu')
    {
        return _srSayfa($ad, 'Beklenen = kontrolün tekrarına göre dönemdeki gün / hafta / ay sayısı (kontrolün açıldığı günden itibaren); yapılan = o gün / hafta / ay en az bir kez "Tamam" denmiş.',
            [['Kontrol', 'm', 34], ['Tür', 'm', 12], ['Tekrar', 'm', 9], ['Beklenen', 's', 9, 'top'], ['Yapılan', 's', 9, 'top'], ['Eksik', 's', 8, 'top esik:1'], ['Uyum %', 'y', 9], ['Son Yapılma', 't', 11], ['Yapan', 'm', 22]],
            array_map(fn ($x) => [$x['baslik'], $x['sicak'] ? 'Sıcaklık' : _klrTipAd($x['tip']), _klrTekrarAd($x['tekrar']), $x['beklenen'], $x['yapilan'], $x['beklenen'] - $x['yapilan'], $x['uyum'], $x['son'], $x['kim'] ?: '—'], $u), $bos);
    }
    function _klrSayfaSicak(array $s, $bos, $ad = 'Sıcaklık Ölçümleri')
    {
        return _srSayfa($ad, 'Limit: kontrol listesinde başlığa göre (buzdolabı 0–4 °C, dondurucu ≤ −18 °C, sıcak tutma ≥ 63 °C); mutfak defterinde kayıttaki hedef.',
            [['Tarih', 't', 11], ['Saat', 'm', 6], ['Ekipman / Kontrol', 'm', 30], ['Ölçüm', 'm', 9], ['Limit', 'm', 14], ['Sonuç', 'm', 12], ['Kaynak', 'm', 20], ['Ölçen', 'm', 16]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ekipman'], $x['deger'], $x['limit'], $x['uygun'] === false ? 'LİMİT DIŞI' : ($x['uygun'] === true ? 'Uygun' : 'Değerlendirilemedi'), $x['kaynak'], $x['personel']], $s), $bos);
    }
    function _klrSayfaDof(array $l, $bos, $ad = 'Uygunsuzluklar (DÖF)')
    {
        $bugun = date('Y-m-d');
        return _srSayfa($ad, 'Kapanma süresi = açılıştan "Tamam"a kadar geçen saat. Açık kayıtlarda "Açık gün" bugüne kadar geçen süredir.',
            [['Açılış', 't', 11], ['Uygunsuzluk', 'm', 40], ['Öncelik', 'm', 8], ['Kaynak', 'm', 14], ['Durum', 'm', 11], ['Kapanış', 't', 11], ['Kapanma (saat)', 's', 11], ['Açık Gün', 's', 8, 'esik:2'], ['Sorumlu', 'm', 16]],
            array_map(fn ($x) => [$x['acilis'], $x['baslik'], ['yuksek' => 'Yüksek', 'orta' => 'Orta', 'dusuk' => 'Düşük'][$x['oncelik']] ?? $x['oncelik'], $x['oto'] ? 'Otomatik (sıcaklık)' : ($x['olusturan'] ?: 'Elle'),
                $x['durum'] === 'tamam' ? 'Kapandı' : ($x['durum'] === 'iptal' ? 'İptal' : 'Açık'), $x['tamam'], $x['saat'], in_array($x['durum'], ['acik', 'yapiliyor'], true) ? (int) round((strtotime($bugun) - strtotime($x['acilis'])) / 86400) : 0, $x['atanan'] ?: '—'], $l), $bos);
    }
    function _klrSayfaTakvim(array $t, $bos, $ad, $not)
    {
        return _srSayfa($ad, $not, [['Kayıt', 'm', 30], ['Firma / Değer', 'm', 18], ['Son Yapılış', 't', 11], ['Sonraki / Geçerlilik', 't', 13], ['Kalan Gün', 's', 9], ['Durum', 'm', 14], ['Kayıt Sayısı', 's', 9], ['Ort. Ara (gün)', 's', 11], ['Giren', 'm', 14]],
            array_map(fn ($x) => [$x['baslik'], $x['deger'] ?: '—', $x['son_yapilis'], $x['sonraki'], $x['kalan'], $x['durum'] . ($x['tahmini'] ? ' (tahmini)' : ''), $x['kayit'], $x['ort_ara'], $x['personel']], $t), $bos);
    }
    function _klrSayfaParti(array $p, $bos, $ad = 'SKT Riskli Partiler')
    {
        return _srSayfa($ad, 'Elde kalan miktarı olan partiler (anlık durum, dönemden bağımsız). Risk değeri = kalan miktar × parti maliyeti. Negatif kalan gün = SKT geçmiş.',
            [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 6], ['Risk Değeri', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 18], ['Durum', 'm', 14]],
            array_map(fn ($x) => [$x['malzeme'], $x['kategori'], $x['lot'] ?: '—', $x['skt'], $x['kalan'], $x['miktar'], $x['birim'], $x['deger'], $x['tedarikci'], $x['durum']], $p), $bos);
    }
    function _klrHazirlik($sid)
    {
        $bugun = date('Y-m-d'); $o = [];
        $acik = array_values(array_filter(_klrIsler($sid, $bugun, $bugun), fn ($x) => in_array($x['durum'], ['acik', 'yapiliyor'], true)));
        $dof = array_filter($acik, fn ($x) => $x['tip'] === 'olay'); $bek = array_filter($acik, fn ($x) => $x['tip'] === 'kontrol' && $x['sonuc'] === 'kacirildi');
        $o[] = ['Açık uygunsuzluk (DÖF)', count($dof), 'Kalite / HACCP > Kontrol Listesi', 'Her uygunsuzluğun düzeltici faaliyeti yazılıp kapatılmalı.'];
        $o[] = ['Süresi geçmiş (yapılmamış) kontrol', count($bek), 'Kalite / HACCP > Kontrol Listesi', 'Son tarihi geçmiş açık kontroller.'];
        $sic = _klrSicaklik($sid, date('Y-m-d', strtotime('-1 day')), $bugun); $o[] = ['Son 2 günde limit dışı sıcaklık', count(array_filter($sic, fn ($x) => $x['uygun'] === false)), 'Sıcaklık ölçümleri', 'Ürünlerin durumu ve ekipman kontrolü kayıtlı olmalı.'];
        $o[] = ['Son 2 günde sıcaklık ölçümü yapılmamış', $sic ? 0 : 1, 'Kontrol Listesi / Mutfak Defteri', 'Soğuk zincir ölçümü her gün yapılmalı.'];
        $pr = _klrPartiler($sid); $o[] = ['SKT\'si geçmiş parti elde', count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')), 'Stok > SKT / Parti Takibi', 'Ayrılıp imha edilmeli, imha fire olarak girilmeli.'];
        $nm = _klrNumune($sid, date('Y-m-d', strtotime('-2 day')), $bugun); $o[] = ['Son 3 günde numunesi eksik gün', count($nm['eksik']), 'Kayıt Defteri > Şahit Numune', '72 saatlik numune zinciri kopmamalı.'];
        $u = _klrUrunler($sid); $o[] = ['Alerjen bilgisi girilmemiş ürün', count(array_filter($u, fn ($x) => !$x['girilmis'])), 'Alerjen Matrisi', 'Menüdeki her ürünün alerjen bilgisi bulunmalı.'];
        $h = _klrHijyen($sid); $o[] = ['Sağlık / hijyen belgesi eksik ya da süresi geçmiş personel', count(array_filter($h, fn ($x) => $x['gida'] && (in_array($x['saglik_durum'], ['Belge yok', 'Süresi geçti'], true) || in_array($x['hijyen_durum'], ['Belge yok', 'Süresi geçti'], true)))), 'İK Sicil > Belgeler', 'Mutfak ve servis personelinde portör / hijyen belgesi zorunludur.'];
        foreach ([['ilaclama', 'İlaçlama', 30], ['kalibrasyon', 'Kalibrasyon', 365], ['tedarikci_belge', 'Tedarikçi belgesi', 0]] as [$t, $ad, $g]) $o[] = [$ad . ' süresi geçmiş', count(array_filter(_klrTakvim($sid, $t, $g), fn ($x) => $x['durum'] === 'Süresi geçti')), 'Kayıt Defteri', 'Yenilenip kayda girilmeli.'];
        $o[] = ['İlaçlama kaydı hiç yok', _klrKayit($sid, 'ilaclama') ? 0 : 1, 'Kayıt Defteri > İlaçlama', 'Haşere kontrol sözleşmesi ve uygulama raporları denetimde istenir.'];
        return array_map(fn ($x) => [$x[0], $x[1] > 0 ? 'Eksik' : 'Tamam', $x[1], $x[2], $x[3]], $o);
    }
}

// ---------- Raporlar ----------
if (!function_exists('_klrRapor')) {
    function _klrRapor($anahtar, $sid, array $dn)
    {
        _klrEnsure();
        $d = _klrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $bosKontrol = !$d['kontrol'] ? _klrBos('kontrol_yok', $dn) : _klrBos('kontrol', $dn);
        $bosSicak = !$d['sicaklik'] ? _klrBos('sicaklik_yok', $dn) : _klrBos('sicaklik', $dn);
        $bosDof = !$d['olay'] ? _klrBos('olay_yok', $dn) : _klrBos('olay', $dn);
        $sayDagilim = fn (array $l, $fn) => collect($l)->groupBy($fn)->map->count()->sortDesc()->all();
        $blok = fn ($bas2, $tip, $renk, $kolAd, array $map) => _srBlok($bas2, $tip, $renk, [[$kolAd, 'm'], ['Sayı', 's'], ['', 'm']], array_map(fn ($a, $v) => [(string) $a, $v, ''], array_keys($map), array_values($map)));

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $u = _klrUyum($sid, $bas, $bit); $s = _klrSicaklik($sid, $bas, $bit); $ss = _klrSicakSay($s); $is = _klrIsler($sid, $bas, $bit);
                $dof = _klrDof($sid, $bas, $bit); $acikDof = array_values(array_filter($dof, fn ($x) => in_array($x['durum'], ['acik', 'yapiliyor'], true)));
                $nm = _klrNumune($sid, $bas, $bit); $pr = array_values(array_filter(_klrPartiler($sid), fn ($x) => in_array($x['durum'], ['SKT geçti', '7 gün içinde'], true)));
                $tak = []; foreach ([['ilaclama', 'İlaçlama', 30], ['kalibrasyon', 'Kalibrasyon', 365], ['tedarikci_belge', 'Tedarikçi belgesi', 0], ['denetim', 'Denetim', 0]] as [$t, $ad, $g]) foreach (_klrTakvim($sid, $t, $g) as $x) if ($x['kalan'] !== null && $x['kalan'] <= 15) $tak[] = [$ad, $x['baslik'], $x['sonraki'], $x['kalan'], $x['durum']];
                $yapilmayan = array_values(array_filter($is, fn ($x) => $x['tip'] !== 'olay' && in_array($x['sonuc'], ['kacirildi', 'gec'], true)));
                return [
                    $pano([$kpiY('Kontrol uyumu', _klrUyumOran($u), '16A34A'), _srKpi('Yapılan kontrol', array_sum(array_column($u, 'yapilan')), 'sayi', '15803D'), _srKpi('Eksik kontrol', array_sum(array_column($u, 'beklenen')) - array_sum(array_column($u, 'yapilan')), 'sayi', 'B45309'),
                        _srKpi('Sıcaklık ölçümü', $ss['n'], 'sayi', '0284C7'), _srKpi('Limit dışı', $ss['uygunsuz'], 'sayi', 'DC2626'), _srKpi('Açık DÖF', count($acikDof), 'sayi', 'B91C1C'),
                        _srKpi('Şahit numune', $nm['eksik'] ? 'Alınmadı' : ($nm['gunler'] ? 'Alındı' : '—'), 'metin', $nm['eksik'] ? 'DC2626' : '16A34A'), _srKpi('SKT geçmiş / 7 gün', count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')) . ' / ' . count(array_filter($pr, fn ($x) => $x['durum'] === '7 gün içinde')), 'metin', 'C2410C'),
                        _srKpi('15 gün içinde takvim', count($tak), 'sayi', '7C3AED')],
                        [$blok('Ekipmana göre limit dışı', 'bar', 'DC2626', 'Ekipman', $sayDagilim(array_filter($s, fn ($x) => $x['uygun'] === false), fn ($x) => $x['ekipman']))], !$u && !$s && !$is ? $bosKontrol : null),
                    _klrSayfaUyum($u, $bosKontrol, 'Kontrol Listesi (Dün)'),
                    _klrSayfaSicak($s, $bosSicak),
                    _srSayfa('Geciken / Yapılmayan', 'Son tarihi bu güne düşen ama geç yapılan ya da hâlâ yapılmamış kontrol ve görevler.', [['Plan', 't', 11], ['Kontrol / Görev', 'm', 34], ['Durum', 'm', 18], ['Gecikme (gün)', 's', 11], ['Sorumlu', 'm', 16]],
                        array_map(fn ($x) => [$x['plan'], $x['baslik'], _klrSonucAd($x['sonuc']), $x['gecikme'], $x['atanan'] ?: '—'], $yapilmayan), 'Geciken ya da yapılmayan kontrol yok.'),
                    _klrSayfaDof($acikDof, $bosDof, 'Açık Uygunsuzluklar'),
                    _klrSayfaParti($pr, !$d['parti'] ? _klrBos('parti_yok', $dn) : 'SKT\'si geçen ya da 7 gün içinde dolacak parti yok.', 'SKT Geçen / 7 Gün'),
                    _srSayfa('Yaklaşan Takvim (15 gün)', 'İlaçlama, kalibrasyon, tedarikçi belgesi ve denetim tarihleri. Negatif gün = süresi geçmiş.', [['Tür', 'm', 16], ['Kayıt', 'm', 30], ['Tarih', 't', 11], ['Kalan Gün', 's', 9], ['Durum', 'm', 14]], $tak, 'Önümüzdeki 15 günde takvimde bir şey yok.'),
                ];
            case 'haftalik':
            case 'aylik':
                $u = _klrUyum($sid, $bas, $bit); $uo = _klrUyum($sid, $dn['obas'], $dn['obit']); $s = _klrSicaklik($sid, $bas, $bit); $ss = _klrSicakSay($s); $sso = _klrSicakSay(_klrSicaklik($sid, $dn['obas'], $dn['obit']));
                $dof = _klrDof($sid, $bas, $bit); $acilan = array_filter($dof, fn ($x) => $x['acilis'] >= $bas); $kapanan = array_filter($dof, fn ($x) => $x['durum'] === 'tamam' && $x['tamam'] >= $bas && $x['tamam'] <= $bit);
                $kap = array_filter(array_column($kapanan, 'saat'), fn ($x) => $x !== null); $nm = _klrNumune($sid, $bas, $bit); $pr = _klrPartiler($sid);
                $kpi = [$kpiY('Kontrol uyumu', _klrUyumOran($u), '16A34A'), $kpiY('Önceki dönem uyum', _klrUyumOran($uo), '15803D'), _srKpi('Sıcaklık ölçümü', $ss['n'], 'sayi', '0284C7'), $kpiY('Limit dışı oranı', $ss['oran'], 'DC2626'), $kpiY('Önceki dönem limit dışı', $sso['oran'], 'B91C1C'),
                    _srKpi('Açılan DÖF', count($acilan), 'sayi', 'DC2626'), _srKpi('Kapanan DÖF', count($kapanan), 'sayi', '059669'), _srKpi('Ort. kapanma (saat)', $kap ? round(array_sum($kap) / count($kap), 1) : 'Ölçüm yok', $kap ? 'sayi' : 'metin', '0F766E'),
                    $kpiY('Numune uyumu', $nm['uyum'], '7C3AED'), _srKpi('SKT geçmiş parti (şu an)', count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')), 'sayi', 'C2410C')];
                $gunluk = []; foreach (_klrGunler($bas, $bit) as $t) { $l = array_filter($s, fn ($x) => $x['tarih'] === $t); $gunluk[] = [date('d.m', strtotime($t)), count($l), count(array_filter($l, fn ($x) => $x['uygun'] === false))]; }
                $bloklar = [_srBlok('Günlere göre ölçüm ve limit dışı', 'col', '0284C7', [['Gün', 'm'], ['Ölçüm', 's'], ['Limit dışı', 's']], $gunluk), $blok('Ekipmana göre limit dışı', 'bar', 'DC2626', 'Ekipman', $sayDagilim(array_filter($s, fn ($x) => $x['uygun'] === false), fn ($x) => $x['ekipman']))];
                $sayfalar = [_klrSayfaUyum($u, $bosKontrol), _klrSayfaSicak(array_values(array_filter($s, fn ($x) => $x['uygun'] === false)), !$d['sicaklik'] ? _klrBos('sicaklik_yok', $dn) : _klrBos('uygunsuz', $dn), 'Limit Dışı Ölçümler'), _klrSayfaDof($dof, $bosDof),
                    _srSayfa('Numunesi Eksik Günler', $nm['satis'] ? 'Satış yapılan (ödenmiş adisyon olan) günler esas alındı.' : 'Satış verisi olmadığı için takvimdeki tüm günler esas alındı.', [['Tarih', 't', 11], ['Gün', 'm', 12]], array_map(fn ($t) => [$t, ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) date('N', strtotime($t))]], $nm['eksik']), !$d['numune'] ? _klrBos('numune_yok', $dn) : _klrBos('numune', $dn)),
                    _klrSayfaParti(array_values(array_filter($pr, fn ($x) => in_array($x['durum'], ['SKT geçti', '7 gün içinde', '30 gün içinde'], true))), !$d['parti'] ? _klrBos('parti_yok', $dn) : _klrBos('skt', $dn))];
                if ($anahtar === 'aylik') {
                    $den = array_values(array_filter(_klrKayit($sid, 'denetim'), fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit)); $al = array_filter(_klrUrunler($sid), fn ($x) => !$x['girilmis']);
                    $mk = _klrMalKabul($sid, $bas, $bit); $fr = _klrFire($sid, $bas, $bit); $sk = _klrSikayet($sid, $bas, $bit); $hj = _klrHijyen($sid);
                    $kpi = array_merge($kpi, [_srKpi('Denetim', count($den), 'sayi', '5B21B6'), _srKpi('Alerjeni eksik ürün', count($al), 'sayi', 'EA580C'), _srKpi('Mal kabulde red kalem', count(array_filter($mk, fn ($x) => $x['red'] > 0)), 'sayi', '7C2D12'),
                        _srKpi('SKT / bozulma imhası', round(array_sum(array_column(array_filter($fr, fn ($x) => $x['skt']), 'maliyet')), 2), 'tl', '9A3412'), _srKpi('Kalite kaynaklı şikayet', count($sk['liste']), 'sayi', '0E7490'),
                        _srKpi('Belgesi eksik / geçmiş personel', count(array_filter($hj, fn ($x) => in_array($x['saglik_durum'], ['Belge yok', 'Süresi geçti'], true) || in_array($x['hijyen_durum'], ['Belge yok', 'Süresi geçti'], true))), 'sayi', '0F766E'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _klrSayfaTakvim(array_merge(_klrTakvim($sid, 'ilaclama', 30), _klrTakvim($sid, 'kalibrasyon', 365)), !$d['ilaclama'] && !$d['kalibrasyon'] ? _klrBos('ilaclama_yok', $dn) : 'Takvimde kayıt yok.', 'İlaçlama & Kalibrasyon', 'Sonraki tarih girilmemişse ilaçlama 30, kalibrasyon 365 gün sonrası "tahmini" yazılır.');
                    $sayfalar[] = _srSayfa('Denetimler', 'Puan = kayıttaki "Değer" alanındaki sayı.', [['Tarih', 't', 11], ['Denetim', 'm', 28], ['Puan / Sonuç', 'm', 12], ['Bulgular', 'm', 50], ['Sonraki', 't', 11]], array_map(fn ($x) => [$x['tarih'], $x['baslik'], $x['deger'] ?: '—', $x['not'], $x['son']], $den), !$d['denetim'] ? _klrBos('denetim_yok', $dn) : _klrBos('denetim', $dn));
                    $sayfalar[] = _srSayfa('Mal Kabul Redleri', '', [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Tedarikçi', 'm', 18], ['Gelen', 's', 9], ['Red', 's', 9, 'top'], ['Sebep', 'm', 28]], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['tedarikci'], $x['gelen'], $x['red'], $x['sebep'] ?: 'Belirtilmemiş'], array_values(array_filter($mk, fn ($x) => $x['red'] > 0))), !$d['mal_kabul'] ? _klrBos('mal_kabul_yok', $dn) : 'Seçili dönemde mal kabulde red edilen kalem yok.');
                    $sayfalar[] = _srSayfa('Kalite Kaynaklı Şikayetler', '', [['Tarih', 't', 11], ['Kaynak', 'm', 14], ['Masa', 'm', 9], ['Puan', 's', 6], ['Konu', 'm', 22], ['Not / Yorum', 'm', 44], ['Durum', 'm', 10]], array_map(fn ($x) => [$x['tarih'], $x['kaynak'], $x['masa'], $x['puan'], $x['konu'], $x['not'], $x['durum']], $sk['liste']), !$d['memnuniyet'] ? _klrBos('sikayet_yok', $dn) : _klrBos('sikayet', $dn));
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$d['kontrol'] && !$d['sicaklik'] && !$d['olay'] ? _klrBos('kontrol_yok', $dn) : null)], $sayfalar);

            // ---------------- KONTROL LİSTESİ ----------------
            case 'kontrol_uyum':
                $u = _klrUyum($sid, $bas, $bit); $uo = _klrUyum($sid, $dn['obas'], $dn['obit']); $or = _klrUyumOran($u);
                $tip = []; foreach ($u as $x) { $k = $x['sicak'] ? 'Sıcaklık' : 'Hijyen / diğer'; $tip[$k]['b'] = ($tip[$k]['b'] ?? 0) + $x['beklenen']; $tip[$k]['y'] = ($tip[$k]['y'] ?? 0) + $x['yapilan']; }
                return [
                    $pano([$kpiY('Genel uyum', $or, '059669'), $kpiY('Önceki dönem', _klrUyumOran($uo), '047857'), _srKpi('Takip edilen kontrol', count($u), 'sayi', '0F766E'), _srKpi('Beklenen', array_sum(array_column($u, 'beklenen')), 'sayi', '115E59'),
                        _srKpi('Yapılan', array_sum(array_column($u, 'yapilan')), 'sayi', '16A34A'), _srKpi('%80 altı kontrol', count(array_filter($u, fn ($x) => $x['uyum'] !== null && $x['uyum'] < 80)), 'sayi', 'DC2626'),
                        _srKpi('Durum', $or === null ? 'Veri yok' : ($or >= 95 ? 'Çok iyi' : ($or >= 80 ? 'Kabul edilebilir' : 'Yetersiz')), 'metin', $or !== null && $or < 80 ? 'DC2626' : '059669')],
                        [_srBlok('Kontrol başına uyum (%)', 'bar', '059669', [['Kontrol', 'm'], ['Uyum %', 'y'], ['', 'm']], array_map(fn ($x) => [$x['baslik'], $x['uyum'], ''], array_slice($u, 0, 15))),
                         _srBlok('Türe göre beklenen / yapılan', 'col', '0F766E', [['Tür', 'm'], ['Beklenen', 's'], ['Yapılan', 's']], array_map(fn ($a, $v) => [$a, $v['b'], $v['y']], array_keys($tip), array_values($tip)))], !$u ? $bosKontrol : null),
                    _klrSayfaUyum($u, $bosKontrol),
                    _srSayfa('Eksik Günler / Dönemler', 'Kontrolün yapılmadığı gün (günlük), hafta (haftalık: yıl-hafta) ya da ay.', [['Kontrol', 'm', 34], ['Tekrar', 'm', 9], ['Eksik Sayısı', 's', 10, 'top bar'], ['Eksik Gün / Hafta / Ay', 'm', 60]],
                        array_map(fn ($x) => [$x['baslik'], _klrTekrarAd($x['tekrar']), count($x['eksik']), implode(', ', array_map(fn ($t) => strlen($t) === 10 ? date('d.m', strtotime($t)) : $t, array_slice($x['eksik'], 0, 40))) . (count($x['eksik']) > 40 ? ' …' : '')], array_values(array_filter($u, fn ($x) => $x['eksik']))), $u ? 'Eksik kalan kontrol yok, tebrikler.' : $bosKontrol),
                ];
            case 'kontrol_gun':
                $u = array_values(array_filter(_klrUyum($sid, $bas, $bit), fn ($x) => $x['tekrar'] === 'gunluk')); $gunler = array_slice(_klrGunler($bas, $bit), -31);
                $kol = [['Kontrol', 'm', 30]]; foreach ($gunler as $t) $kol[] = [date('d.m', strtotime($t)), 'm', 5]; $kol[] = ['Uyum %', 'y', 8];
                $sat = []; foreach ($u as $x) { $r = [$x['baslik']]; foreach ($gunler as $t) $r[] = isset($x['gunler'][$t]) ? '✓' : ($t < $x['ilk'] ? '' : (in_array($t, $x['eksik'], true) ? '✗' : '')); $r[] = $x['uyum']; $sat[] = $r; }
                $gunT = []; foreach ($gunler as $t) { $b = 0; $y = 0; foreach ($u as $x) { if (in_array($t, $x['eksik'], true)) $b++; elseif (isset($x['gunler'][$t])) { $b++; $y++; } } $gunT[] = [date('d.m', strtotime($t)), $y, $b - $y]; }
                return [
                    $pano([_srKpi('Günlük kontrol', count($u), 'sayi', '047857'), $kpiY('Uyum', _klrUyumOran($u), '059669'), _srKpi('Hiç eksiği olmayan gün', count(array_filter($gunT, fn ($x) => $x[2] === 0 && $x[1] > 0)), 'sayi', '16A34A'), _srKpi('Gösterilen gün', count($gunler), 'sayi', '64748B')],
                        [_srBlok('Günlere göre yapılan / eksik', 'col', '047857', [['Gün', 'm'], ['Yapılan', 's'], ['Eksik', 's']], $gunT)], !$u ? $bosKontrol : null),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün Çizelge', '✓ = o gün yapıldı, ✗ = yapılmadı, boş = kontrol henüz açılmamıştı. En fazla son 31 gün gösterilir.', $kol, $sat, $bosKontrol),
                ];
            case 'kontrol_gecikme':
                $is = array_values(array_filter(_klrIsler($sid, $bas, $bit), fn ($x) => $x['tip'] !== 'olay' && in_array($x['sonuc'], ['gec', 'kacirildi'], true)));
                usort($is, fn ($a, $b) => $b['gecikme'] <=> $a['gecikme']);
                return [
                    $pano([_srKpi('Geç yapılan', count(array_filter($is, fn ($x) => $x['sonuc'] === 'gec')), 'sayi', 'B45309'), _srKpi('Hâlâ yapılmamış', count(array_filter($is, fn ($x) => $x['sonuc'] === 'kacirildi')), 'sayi', 'DC2626'),
                        _srKpi('Ort. gecikme (gün)', $is ? round(array_sum(array_column($is, 'gecikme')) / count($is), 1) : 0, 'sayi', '92400E')],
                        [$blok('Kontrole göre gecikme', 'bar', 'B45309', 'Kontrol', $sayDagilim($is, fn ($x) => $x['baslik'])), $blok('Sorumluya göre', 'pie', '92400E', 'Sorumlu', $sayDagilim($is, fn ($x) => $x['atanan'] ?: 'Atanmamış'))], !$d['kontrol'] ? _klrBos('kontrol_yok', $dn) : null),
                    _srSayfa('Geciken & Kaçırılan', 'Son tarih = kontrolün yapılması gereken gün. Tekrarlı kontrolde geç yapılan iş, bir sonrakini de geç başlatır.', [['Son Tarih', 't', 11], ['Kontrol / Görev', 'm', 34], ['Tekrar', 'm', 10], ['Durum', 'm', 18], ['Yapıldığı', 't', 11], ['Gecikme (gün)', 's', 11, 'top esik:2'], ['Sorumlu', 'm', 16]],
                        array_map(fn ($x) => [$x['son'], $x['baslik'], _klrTekrarAd($x['tekrar']), _klrSonucAd($x['sonuc']), $x['tamam'], $x['gecikme'], $x['atanan'] ?: '—'], $is), !$d['kontrol'] ? _klrBos('kontrol_yok', $dn) : 'Seçili dönemde geciken ya da kaçırılan kontrol yok.'),
                ];

            // ---------------- SICAKLIK ----------------
            case 'sicaklik_olcum':
                $s = _klrSicaklik($sid, $bas, $bit); $ss = _klrSicakSay($s); $so = _klrSicakSay(_klrSicaklik($sid, $dn['obas'], $dn['obit']));
                $uygunsuz = array_values(array_filter($s, fn ($x) => $x['uygun'] === false));
                return [
                    $pano([_srKpi('Ölçüm', $ss['n'], 'sayi', '0284C7'), _srKpi('Limit dışı', $ss['uygunsuz'], 'sayi', 'DC2626'), $kpiY('Limit dışı oranı', $ss['oran'], 'B91C1C'), $kpiY('Önceki dönem oranı', $so['oran'], '7F1D1D'),
                        _srKpi('Ölçülen ekipman', count(array_unique(array_column($s, 'ekipman'))), 'sayi', '0369A1'), _srKpi('Ölçüm yapılan gün', count(array_unique(array_column($s, 'tarih'))) . ' / ' . count(_klrGunler($bas, $bit)), 'metin', '075985')],
                        [$blok('Kaynağa göre ölçüm', 'pie', '0284C7', 'Kaynak', $sayDagilim($s, fn ($x) => $x['kaynak'])), $blok('Ekipmana göre limit dışı', 'bar', 'DC2626', 'Ekipman', $sayDagilim($uygunsuz, fn ($x) => $x['ekipman']))], !$s ? $bosSicak : null),
                    _klrSayfaSicak($uygunsuz, !$d['sicaklik'] ? _klrBos('sicaklik_yok', $dn) : _klrBos('uygunsuz', $dn), 'Limit Dışı Ölçümler'),
                    _klrSayfaSicak($s, $bosSicak),
                ];
            case 'sicaklik_ekipman':
                $s = _klrSicaklik($sid, $bas, $bit); $eq = [];
                foreach ($s as $x) { $e = &$eq[$x['ekipman']]; $e['n'] = ($e['n'] ?? 0) + 1; if ($x['uygun'] === false) $e['u'] = ($e['u'] ?? 0) + 1; if ($x['sayi'] !== null) $e['v'][] = $x['sayi']; $e['limit'] = $x['limit']; $e['son'] = $x['tarih']; unset($e); }
                $sat = []; foreach ($eq as $a => $e) { $v = $e['v'] ?? []; $sat[] = [$a, $e['limit'], $e['n'], $e['u'] ?? 0, round(($e['u'] ?? 0) / $e['n'] * 100, 1), $v ? min($v) : null, $v ? max($v) : null, $v ? round(array_sum($v) / count($v), 1) : null, $e['son']]; }
                usort($sat, fn ($a, $b) => [$b[4], $b[2]] <=> [$a[4], $a[2]]);
                $saat = []; foreach ($s as $x) { $h = $x['saat'] !== '' ? substr($x['saat'], 0, 2) . ':00' : 'Saatsiz'; $saat[$h]['n'] = ($saat[$h]['n'] ?? 0) + 1; if ($x['uygun'] === false) $saat[$h]['u'] = ($saat[$h]['u'] ?? 0) + 1; } ksort($saat);
                $saatSat = array_map(fn ($a, $v) => [$a, $v['n'], $v['u'] ?? 0, round(($v['u'] ?? 0) / $v['n'] * 100, 1)], array_keys($saat), array_values($saat));
                return [
                    $pano([_srKpi('Ekipman', count($eq), 'sayi', '0369A1'), _srKpi('En sorunlu', $sat && $sat[0][3] > 0 ? $sat[0][0] : 'Yok', 'metin', 'DC2626'), _srKpi('Limit dışı', array_sum(array_column($sat, 3)), 'sayi', 'B91C1C')],
                        [_srBlok('Saatlere göre ölçüm / limit dışı', 'col', '0369A1', [['Saat', 'm'], ['Ölçüm', 's'], ['Limit dışı', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[2]], $saatSat)),
                         _srBlok('Ekipmana göre limit dışı oranı (%)', 'bar', 'DC2626', [['Ekipman', 'm'], ['Oran %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[4], ''], array_slice($sat, 0, 12)))], !$s ? $bosSicak : null),
                    _srSayfa('Ekipman Analizi', 'En düşük / en yüksek / ortalama ölçülen °C. Sürekli sınırda seyreden ekipman bakım ister.', [['Ekipman / Kontrol', 'm', 30], ['Limit', 'm', 14], ['Ölçüm', 's', 8, 'top'], ['Limit Dışı', 's', 9, 'top esik:1'], ['Limit Dışı %', 'y', 10], ['En Düşük', 's', 9], ['En Yüksek', 's', 9], ['Ortalama', 's', 9], ['Son Ölçüm', 't', 11]], $sat, $bosSicak),
                    _srSayfa('Saat Analizi', 'Ölçümlerin günün hangi saatinde yapıldığı; aynı saatte toplanan ölçüm, gün içi takibin zayıf olduğunu gösterir.', [['Saat', 'm', 10], ['Ölçüm', 's', 9, 'top'], ['Limit Dışı', 's', 9, 'top'], ['Limit Dışı %', 'y', 10]], $saatSat, $bosSicak),
                ];

            // ---------------- UYGUNSUZLUK / HAZIRLIK ----------------
            case 'dof':
                $dof = _klrDof($sid, $bas, $bit); $acik = array_filter($dof, fn ($x) => in_array($x['durum'], ['acik', 'yapiliyor'], true)); $kapanan = array_filter($dof, fn ($x) => $x['durum'] === 'tamam');
                $kap = array_values(array_filter(array_column($kapanan, 'saat'), fn ($x) => $x !== null)); $uzun = array_filter($acik, fn ($x) => (strtotime(date('Y-m-d')) - strtotime($x['acilis'])) / 3600 > 48);
                return [
                    $pano([_srKpi('Dönemde açılan', count(array_filter($dof, fn ($x) => $x['acilis'] >= $bas)), 'sayi', 'DC2626'), _srKpi('Kapanan', count($kapanan), 'sayi', '059669'), _srKpi('Hâlâ açık', count($acik), 'sayi', 'B91C1C'),
                        _srKpi('48 saati aşan açık', count($uzun), 'sayi', '7F1D1D'), _srKpi('Ort. kapanma (saat)', $kap ? round(array_sum($kap) / count($kap), 1) : 'Ölçüm yok', $kap ? 'sayi' : 'metin', '0F766E'),
                        _srKpi('Sıcaklıktan otomatik açılan', count(array_filter($dof, fn ($x) => $x['oto'])), 'sayi', '0284C7')],
                        [$blok('Kaynağa göre', 'pie', 'DC2626', 'Kaynak', $sayDagilim($dof, fn ($x) => $x['oto'] ? 'Otomatik (sıcaklık)' : 'Elle açılan')), $blok('Önceliğe göre', 'col', 'B91C1C', 'Öncelik', $sayDagilim($dof, fn ($x) => ['yuksek' => 'Yüksek', 'orta' => 'Orta', 'dusuk' => 'Düşük'][$x['oncelik']] ?? $x['oncelik']))], !$dof ? $bosDof : null),
                    _klrSayfaDof($dof, $bosDof),
                ];
            case 'hazirlik':
                $h = _klrHazirlik($sid); $tamam = count(array_filter($h, fn ($x) => $x[1] === 'Tamam')); $puan = $h ? round($tamam / count($h) * 100) : null;
                return [
                    $pano([_srKpi('Hazırlık puanı', $puan . ' / 100', 'metin', $puan >= 80 ? '059669' : 'DC2626'), _srKpi('Tamam olan madde', $tamam, 'sayi', '16A34A'), _srKpi('Eksik madde', count($h) - $tamam, 'sayi', 'DC2626'), _srKpi('Kontrol tarihi', date('d.m.Y'), 'metin', '64748B')],
                        [_srBlok('Eksik maddeler (adet)', 'bar', 'DC2626', [['Madde', 'm'], ['Adet', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], array_values(array_filter($h, fn ($x) => $x[1] === 'Eksik'))))]),
                    ['grafiksiz' => true] + _srSayfa('Denetime Hazırlık', 'Anlık durum (dönem seçiminden bağımsız). Her "Eksik" madde denetimde bulgu olarak yazılabilir; "Ekran" sütunu düzeltileceği yeri gösterir.', [['Madde', 'm', 44], ['Durum', 'm', 9], ['Adet', 's', 7, 'esik:1'], ['Ekran', 'm', 30], ['Ne yapılmalı', 'm', 50]], $h),
                ];

            // ---------------- KAYIT DEFTERİ ----------------
            case 'numune':
                $nm = _klrNumune($sid, $bas, $bit); $kay = []; foreach ($nm['kayit'] as $t => $l) foreach ($l as $k) $kay[] = $k; $kisa = array_values(array_filter($kay, fn ($k) => $k['son'] && (strtotime($k['son']) - strtotime($k['tarih'])) / 86400 < 3));
                $bos = !$d['numune'] ? _klrBos('numune_yok', $dn) : null;
                return [
                    $pano([$kpiY('Numune uyumu', $nm['uyum'], '7C3AED'), _srKpi('Esas alınan gün', count($nm['gunler']), 'sayi', '6D28D9'), _srKpi('Numune alınan gün', $nm['alinan'], 'sayi', '16A34A'), _srKpi('Eksik gün', count($nm['eksik']), 'sayi', 'DC2626'),
                        _srKpi('Numune kaydı', count($kay), 'sayi', '5B21B6'), _srKpi('72 saatten kısa saklanan', count($kisa), 'sayi', 'B45309'), _srKpi('Esas', $nm['satis'] ? 'Satış yapılan günler' : 'Takvim günleri', 'metin', '64748B')],
                        [_srBlok('Günlere göre numune sayısı', 'col', '7C3AED', [['Gün', 'm'], ['Numune', 's'], ['', 'm']], array_map(fn ($t) => [date('d.m', strtotime($t)), count($nm['kayit'][$t] ?? []), ''], $nm['gunler']))], $bos),
                    _srSayfa('Numunesi Eksik Günler', $nm['satis'] ? 'Ödenmiş adisyon olan (işletmenin açık olduğu) günler esas alındı.' : 'Satış verisi olmadığı için takvimdeki tüm günler esas alındı.', [['Tarih', 't', 11], ['Gün', 'm', 12]],
                        array_map(fn ($t) => [$t, ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) date('N', strtotime($t))]], $nm['eksik']), $bos ?? _klrBos('numune', $dn)),
                    _srSayfa('Numune Kayıtları', '"Son tarih" girilmişse saklama süresi kontrol edilir: en az 72 saat (3 gün).', [['Tarih', 't', 11], ['Numune', 'm', 30], ['Değer / Not', 'm', 16], ['Saklama Sonu', 't', 12], ['Saklama (gün)', 's', 11], ['Alan', 'm', 16], ['Not', 'm', 28]],
                        array_map(fn ($k) => [$k['tarih'], $k['baslik'], $k['deger'] ?: '—', $k['son'], $k['son'] ? (int) round((strtotime($k['son']) - strtotime($k['tarih'])) / 86400) : null, $k['personel'], $k['not']], $kay), $bos ?? 'Seçili dönemde numune kaydı yok.'),
                ];
            case 'ilaclama':
                $il = _klrTakvim($sid, 'ilaclama', 30); $ka = _klrTakvim($sid, 'kalibrasyon', 365); $tum = array_merge($il, $ka);
                $donem = array_values(array_filter(_klrKayit($sid, ['ilaclama', 'kalibrasyon']), fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit));
                $bos = !$d['ilaclama'] && !$d['kalibrasyon'] ? _klrBos('ilaclama_yok', $dn) : null;
                return [
                    $pano([_srKpi('Süresi geçen', count(array_filter($tum, fn ($x) => $x['durum'] === 'Süresi geçti')), 'sayi', 'DC2626'), _srKpi('Yaklaşan', count(array_filter($tum, fn ($x) => $x['durum'] === 'Yaklaşıyor')), 'sayi', 'D97706'),
                        _srKpi('Takipteki ilaçlama', count($il), 'sayi', '6D28D9'), _srKpi('Takipteki kalibrasyon', count($ka), 'sayi', '5B21B6'), _srKpi('Dönemde yapılan', count($donem), 'sayi', '16A34A'),
                        _srKpi('Son ilaçlama', $il ? max(array_column($il, 'son_yapilis')) : 'Kayıt yok', $il ? 'metin' : 'metin', '7C3AED')],
                        [$blok('Durum', 'pie', '6D28D9', 'Durum', $sayDagilim($tum, fn ($x) => $x['durum']))], $bos),
                    _klrSayfaTakvim($il, $bos ?? 'İlaçlama kaydı yok.', 'İlaçlama Takvimi', 'Sonraki tarih girilmemişse son uygulamadan 30 gün sonrası "tahmini" yazılır. Ort. ara = iki uygulama arası ortalama gün.'),
                    _klrSayfaTakvim($ka, $bos ?? 'Kalibrasyon kaydı yok.', 'Kalibrasyon Takvimi', 'Sonraki tarih girilmemişse son kalibrasyondan 365 gün sonrası "tahmini" yazılır.'),
                    _srSayfa('Dönemde Yapılanlar', '', [['Tarih', 't', 11], ['Tür', 'm', 16], ['Kayıt', 'm', 30], ['Firma / Değer', 'm', 18], ['Sonraki', 't', 11], ['Giren', 'm', 16], ['Not', 'm', 30]],
                        array_map(fn ($x) => [$x['tarih'], _klrKayitTipAd($x['tip']), $x['baslik'], $x['deger'] ?: '—', $x['son'], $x['personel'], $x['not']], $donem), $bos ?? 'Seçili dönemde ilaçlama ya da kalibrasyon yapılmamış.'),
                ];
            case 'denetim':
                $tum = _klrKayit($sid, 'denetim'); $den = array_values(array_filter($tum, fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit)); $p = array_values(array_filter(array_map(fn ($x) => _klrSayi($x['deger']), $den), fn ($x) => $x !== null));
                $sonraki = collect($tum)->filter(fn ($x) => $x['son'] && $x['son'] >= date('Y-m-d'))->sortBy('son')->first();
                $once = null; $sat = []; foreach ($tum as $x) { $v = _klrSayi($x['deger']); if ($x['tarih'] >= $bas && $x['tarih'] <= $bit) $sat[] = [$x['tarih'], $x['baslik'], $x['deger'] ?: '—', $v, $v !== null && $once !== null ? round($v - $once, 1) : null, $x['not'] ?: '—', $x['personel'], $x['son']]; if ($v !== null) $once = $v; }
                $bos = !$d['denetim'] ? _klrBos('denetim_yok', $dn) : _klrBos('denetim', $dn);
                return [
                    $pano([_srKpi('Dönemde denetim', count($den), 'sayi', '5B21B6'), _srKpi('Ortalama puan', $p ? round(array_sum($p) / count($p), 1) : 'Puan yok', $p ? 'sayi' : 'metin', '7C3AED'), _srKpi('En düşük puan', $p ? min($p) : '—', $p ? 'sayi' : 'metin', 'DC2626'),
                        _srKpi('Bulgu yazılmış', count(array_filter($den, fn ($x) => trim($x['not']) !== '')), 'sayi', 'B45309'), _srKpi('Sıradaki denetim', $sonraki ? date('d.m.Y', strtotime($sonraki['son'])) . ' (' . $sonraki['baslik'] . ')' : 'Planlı yok', 'metin', '4C1D95')],
                        [_srBlok('Denetim puanları (tüm zamanlar)', 'col', '7C3AED', [['Tarih', 'm'], ['Puan', 's'], ['', 'm']], array_values(array_filter(array_map(fn ($x) => [date('d.m.y', strtotime($x['tarih'])), _klrSayi($x['deger']), ''], $tum), fn ($x) => $x[1] !== null)))], !$den ? $bos : null),
                    _srSayfa('Denetimler', 'Puan = "Değer" alanındaki sayı (örn. 86 ya da 86/100). Değişim = bir önceki puanlı denetime göre fark.', [['Tarih', 't', 11], ['Denetim', 'm', 26], ['Sonuç', 'm', 12], ['Puan', 's', 7], ['Değişim', 's', 8], ['Bulgular', 'm', 50], ['Giren', 'm', 14], ['Sonraki', 't', 11]], $sat, $bos),
                ];
            case 'tedarikci_belge':
                $t = _klrTakvim($sid, 'tedarikci_belge', 0); $bos = !$d['tedarikci_belge'] ? _klrBos('tedarikci_yok', $dn) : null;
                return [
                    $pano([_srKpi('Takipteki belge', count($t), 'sayi', '4C1D95'), _srKpi('Süresi geçen', count(array_filter($t, fn ($x) => $x['durum'] === 'Süresi geçti')), 'sayi', 'DC2626'), _srKpi('30 gün içinde dolacak', count(array_filter($t, fn ($x) => $x['durum'] === 'Yaklaşıyor')), 'sayi', 'D97706'),
                        _srKpi('Tarihi girilmemiş', count(array_filter($t, fn ($x) => $x['durum'] === 'Tarih girilmemiş')), 'sayi', '64748B')],
                        [$blok('Belge durumu', 'pie', '4C1D95', 'Durum', $sayDagilim($t, fn ($x) => $x['durum']))], $bos),
                    _klrSayfaTakvim($t, $bos ?? 'Tedarikçi belgesi kaydı yok.', 'Tedarikçi Belgeleri', 'Belge başlığı + firma (Değer alanı) bazında en son kayıt. Anlık durum; dönemden bağımsız.'),
                ];

            // ---------------- ÜRÜN & GIDA ----------------
            case 'alerjen':
                $u = _klrUrunler($sid); $eksik = array_values(array_filter($u, fn ($x) => !$x['girilmis'])); $al = _klrAlerjenListe(); $mat = array_fill_keys($al, 0);
                foreach ($u as $x) foreach ($x['alerjen'] as $a) $mat[$a] = ($mat[$a] ?? 0) + 1;
                $kat = []; foreach ($u as $x) { $kat[$x['kategori']]['n'] = ($kat[$x['kategori']]['n'] ?? 0) + 1; if (!$x['girilmis']) $kat[$x['kategori']]['e'] = ($kat[$x['kategori']]['e'] ?? 0) + 1; }
                $bos = !$d['urun'] ? _klrBos('urun_yok', $dn) : null;
                return [
                    $pano([_srKpi('Aktif ürün', count($u), 'sayi', 'EA580C'), _srKpi('Alerjeni girilmiş', count($u) - count($eksik), 'sayi', '059669'), _srKpi('Alerjeni girilmemiş', count($eksik), 'sayi', 'DC2626'),
                        $kpiY('Tamamlanma', $u ? round((count($u) - count($eksik)) / count($u) * 100, 1) : null, 'C2410C'), _srKpi('En yaygın alerjen', $mat && max($mat) > 0 ? array_search(max($mat), $mat) : '—', 'metin', '9A3412')],
                        [_srBlok('Alerjene göre ürün sayısı', 'bar', 'EA580C', [['Alerjen', 'm'], ['Ürün', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($mat), array_values($mat))),
                         _srBlok('Kategoriye göre eksik', 'col', 'DC2626', [['Kategori', 'm'], ['Eksik', 's'], ['Toplam', 's']], array_map(fn ($a, $v) => [$a, $v['e'] ?? 0, $v['n']], array_keys($kat), array_values($kat)))], $bos),
                    _srSayfa('Alerjeni Girilmemiş Ürünler', 'Alerjen Matrisi\'nde hiç işaretlenmemiş ürünler. Alerjen içermeyen ürün de bir kez kaydedilmeli; aksi halde "bilgi yok" sayılır.', [['Ürün', 'm', 30], ['Kategori', 'm', 20]], array_map(fn ($x) => [$x['ad'], $x['kategori']], $eksik), $bos ?? _klrBos('alerjen', $dn)),
                    _srSayfa('Alerjen Matrisi', 'Ürün başına işaretli alerjenler (14 majör alerjen, TR gıda etiketleme).', [['Ürün', 'm', 30], ['Kategori', 'm', 18], ['Alerjen Sayısı', 's', 10], ['Alerjenler', 'm', 60]], array_map(fn ($x) => [$x['ad'], $x['kategori'], count($x['alerjen']), $x['girilmis'] ? (implode(', ', $x['alerjen']) ?: 'Alerjen yok') : 'GİRİLMEMİŞ'], $u), $bos),
                ];
            case 'skt':
                $pr = _klrPartiler($sid); $risk = array_values(array_filter($pr, fn ($x) => in_array($x['durum'], ['SKT geçti', '7 gün içinde', '30 gün içinde'], true))); $bos = !$d['parti'] ? _klrBos('parti_yok', $dn) : null;
                $deg = fn ($l) => round(array_sum(array_column($l, 'deger')), 2);
                return [
                    $pano([_srKpi('Elde parti', count($pr), 'sayi', 'C2410C'), _srKpi('SKT geçti', count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')), 'sayi', 'DC2626'), _srKpi('SKT geçmiş stok değeri', $deg(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')), 'tl', 'B91C1C'),
                        _srKpi('7 gün içinde', count(array_filter($pr, fn ($x) => $x['durum'] === '7 gün içinde')), 'sayi', 'EA580C'), _srKpi('30 gün içinde', count(array_filter($pr, fn ($x) => $x['durum'] === '30 gün içinde')), 'sayi', 'D97706'),
                        _srKpi('Risk altındaki değer (30 gün)', $deg($risk), 'tl', '9A3412'), _srKpi('SKT girilmemiş', count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT girilmemiş')), 'sayi', '64748B'), _srKpi('Lot girilmemiş', count(array_filter($pr, fn ($x) => $x['lot'] === '')), 'sayi', '64748B')],
                        [$blok('Parti durumu', 'pie', 'C2410C', 'Durum', $sayDagilim($pr, fn ($x) => $x['durum'])), _srBlok('Kategoriye göre risk değeri', 'bar', '9A3412', [['Kategori', 'm'], ['Değer', 'tl'], ['', 'm']], collect($risk)->groupBy('kategori')->map(fn ($g, $k) => [$k, round($g->sum('deger'), 2), ''])->sortByDesc(1)->values()->all())], $bos),
                    _klrSayfaParti($risk, $bos ?? _klrBos('skt', $dn)),
                    _klrSayfaParti(array_values(array_filter($pr, fn ($x) => $x['durum'] === 'SKT girilmemiş' || $x['lot'] === '')), $bos ?? 'Tüm partilerde SKT ve lot girilmiş.', 'SKT / Lot Eksik Partiler'),
                ];
            case 'imha':
                $fr = _klrFire($sid, $bas, $bit); $skt = array_values(array_filter($fr, fn ($x) => $x['skt'])); $fo = _klrFire($sid, $dn['obas'], $dn['obit']);
                $bos = !$d['fire'] ? _klrBos('fire_yok', $dn) : _klrBos('fire', $dn); $tl = fn ($l) => round(array_sum(array_column($l, 'maliyet')), 2);
                return [
                    $pano([_srKpi('Toplam fire / imha', $tl($fr), 'tl', '9A3412'), _srKpi('SKT / bozulma kaynaklı', $tl($skt), 'tl', 'DC2626'), $kpiY('SKT payı', $tl($fr) > 0 ? round($tl($skt) / $tl($fr) * 100, 1) : null, 'B91C1C'),
                        _srKpi('Önceki dönem SKT kaynaklı', $tl(array_filter($fo, fn ($x) => $x['skt'])), 'tl', '7F1D1D'), _srKpi('Kayıt', count($fr), 'sayi', '64748B')],
                        [_srBlok('Sebebe göre tutar', 'pie', '9A3412', [['Sebep', 'm'], ['Tutar', 'tl'], ['', 'm']], collect($fr)->groupBy('sebep')->map(fn ($g, $k) => [$k, round($g->sum('maliyet'), 2), ''])->sortByDesc(1)->values()->all()),
                         _srBlok('SKT kaynaklı en çok imha edilen', 'bar', 'DC2626', [['Malzeme', 'm'], ['Tutar', 'tl'], ['', 'm']], collect($skt)->groupBy('malzeme')->map(fn ($g, $k) => [$k, round($g->sum('maliyet'), 2), ''])->sortByDesc(1)->take(10)->values()->all())], !$fr ? $bos : null),
                    _srSayfa('SKT / Bozulma Kaynaklı İmha', 'Sebebinde SKT, son kullanma, bozulma, küf, ekşime geçen fireler.', [['Tarih', 't', 11], ['Malzeme', 'm', 24], ['Miktar', 's', 9], ['Birim', 'm', 6], ['Maliyet', 'tl', 12, 'top bar'], ['Sebep', 'm', 30]], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['miktar'], $x['birim'], $x['maliyet'], $x['sebep']], $skt), $fr ? 'Seçili dönemde SKT / bozulma kaynaklı imha yok.' : $bos),
                    _srSayfa('Tüm Fire / İmha', '', [['Tarih', 't', 11], ['Malzeme', 'm', 24], ['Miktar', 's', 9], ['Birim', 'm', 6], ['Maliyet', 'tl', 12, 'top'], ['Sebep', 'm', 30], ['SKT Kaynaklı', 'm', 10]], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['miktar'], $x['birim'], $x['maliyet'], $x['sebep'], $x['skt'] ? 'Evet' : ''], $fr), $bos),
                ];
            case 'mal_kabul':
                $mk = _klrMalKabul($sid, $bas, $bit); $red = array_values(array_filter($mk, fn ($x) => $x['red'] > 0)); $kisa = array_values(array_filter($mk, fn ($x) => $x['kabul'] > 0 && $x['skt_gun'] !== null && $x['skt_gun'] < 3));
                $eksik = array_filter($mk, fn ($x) => $x['kabul'] > 0 && ($x['lot'] === '' || !$x['skt'])); $bos = !$d['mal_kabul'] ? _klrBos('mal_kabul_yok', $dn) : _klrBos('mal_kabul', $dn);
                $ted = collect($mk)->groupBy('tedarikci')->map(fn ($g, $k) => [$k, $g->count(), $g->where('red', '>', 0)->count(), round($g->where('red', '>', 0)->count() / max(1, $g->count()) * 100, 1), $g->filter(fn ($x) => $x['lot'] === '' || !$x['skt'])->count()])->sortByDesc(2)->values()->all();
                return [
                    $pano([_srKpi('Kabul edilen kalem', count($mk), 'sayi', '7C2D12'), _srKpi('Red edilen kalem', count($red), 'sayi', 'DC2626'), $kpiY('Red oranı', $mk ? round(count($red) / count($mk) * 100, 1) : null, 'B91C1C'),
                        _srKpi('Lot / SKT girilmeden kabul', count($eksik), 'sayi', 'D97706'), _srKpi('3 günden kısa SKT ile kabul', count($kisa), 'sayi', 'EA580C')],
                        [_srBlok('Red sebepleri', 'pie', 'DC2626', [['Sebep', 'm'], ['Kalem', 's'], ['', 'm']], collect($red)->groupBy(fn ($x) => $x['sebep'] ?: 'Belirtilmemiş')->map(fn ($g, $k) => [$k, $g->count(), ''])->sortByDesc(1)->values()->all()),
                         _srBlok('Tedarikçiye göre red', 'bar', '7C2D12', [['Tedarikçi', 'm'], ['Red kalem', 's'], ['Toplam', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[1]], array_slice($ted, 0, 10)))], !$mk ? $bos : null),
                    _srSayfa('Red Edilen Kalemler', '', [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Tedarikçi', 'm', 18], ['İrsaliye', 'm', 12], ['Gelen', 's', 9], ['Red', 's', 9, 'top'], ['Sebep', 'm', 28]], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['tedarikci'], $x['irsaliye'] ?: '—', $x['gelen'], $x['red'], $x['sebep'] ?: 'Belirtilmemiş'], $red), $mk ? 'Seçili dönemde red edilen kalem yok.' : $bos),
                    _srSayfa('Tedarikçi Kalite Karnesi', 'Lot / SKT eksik = kabul edilen kalemde lot ya da SKT girilmemiş (geri çağırmada izlenemez).', [['Tedarikçi', 'm', 24], ['Kalem', 's', 8, 'top'], ['Red Kalem', 's', 9, 'top'], ['Red %', 'y', 8, 'esik:10'], ['Lot / SKT Eksik', 's', 12, 'top']], $ted, $bos),
                    _srSayfa('Kısa SKT ile Kabul', 'Kabul gününde SKT\'sine 3 günden az kalan kalemler.', [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Tedarikçi', 'm', 18], ['Kabul', 's', 9], ['SKT', 't', 11], ['Kabulde Kalan Gün', 's', 13]], array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['tedarikci'], $x['kabul'], $x['skt'], $x['skt_gun']], $kisa), $mk ? 'Kısa SKT ile kabul edilen kalem yok.' : $bos),
                ];

            // ---------------- PERSONEL & MÜŞTERİ ----------------
            case 'hijyen_belge':
                $h = _klrHijyen($sid); usort($h, fn ($a, $b) => [!$a['gida'], $a['ad']] <=> [!$b['gida'], $b['ad']]); $sorun = fn ($x) => in_array($x['saglik_durum'], ['Belge yok', 'Süresi geçti'], true) || in_array($x['hijyen_durum'], ['Belge yok', 'Süresi geçti'], true);
                return [
                    $pano([_srKpi('Aktif personel', count($h), 'sayi', '0F766E'), _srKpi('Gıdaya temas eden (mutfak + servis)', count(array_filter($h, fn ($x) => $x['gida'])), 'sayi', '115E59'),
                        _srKpi('Belgesi eksik / geçmiş', count(array_filter($h, $sorun)), 'sayi', 'DC2626'), _srKpi('Gıdaya temas edip eksik', count(array_filter($h, fn ($x) => $x['gida'] && $sorun($x))), 'sayi', 'B91C1C'),
                        _srKpi('30 gün içinde dolacak', count(array_filter($h, fn ($x) => $x['saglik_durum'] === 'Yaklaşıyor' || $x['hijyen_durum'] === 'Yaklaşıyor')), 'sayi', 'D97706'), _srKpi('Hijyen eğitimi kayıtlı', count(array_filter($h, fn ($x) => $x['egitim'])), 'sayi', '059669')],
                        [$blok('Sağlık raporu durumu', 'pie', '0F766E', 'Durum', $sayDagilim($h, fn ($x) => $x['saglik_durum'])), $blok('Hijyen belgesi durumu', 'pie', '0E7490', 'Durum', $sayDagilim($h, fn ($x) => $x['hijyen_durum']))], !$h ? _klrBos('belge', $dn) : (!$d['belge'] ? 'Personel belgesi girilmemiş: sağlık raporu (portör) ve hijyen eğitim belgesi İK Sicil > Belgeler ekranından "Sağlık" / "Hijyen" türüyle ve son geçerlilik tarihiyle girilir.' : null)),
                    _srSayfa('Personel Belge Durumu', 'Belge türü İK Sicil > Belgeler\'de "Sağlık" ve "Hijyen" seçilen kayıtlardır. Hijyen eğitimi: Eğitimler\'de konusu hijyen / HACCP / gıda geçen en son eğitim.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Gıdaya Temas', 'm', 10], ['Sağlık Raporu', 't', 12], ['Sağlık Durumu', 'm', 14], ['Hijyen Belgesi', 't', 12], ['Hijyen Durumu', 'm', 14], ['Son Hijyen Eğitimi', 't', 14]],
                        array_map(fn ($x) => [$x['ad'], $x['dep'], $x['gida'] ? 'Evet' : '', $x['saglik'], $x['saglik_durum'], $x['hijyen'], $x['hijyen_durum'], $x['egitim']], $h), _klrBos('belge', $dn)),
                ];
            case 'sikayet':
                $sk = _klrSikayet($sid, $bas, $bit); $l = $sk['liste']; $bos = !$d['memnuniyet'] ? _klrBos('sikayet_yok', $dn) : _klrBos('sikayet', $dn); $so = _klrSikayet($sid, $dn['obas'], $dn['obit']);
                return [
                    $pano([_srKpi('Geri bildirim', $sk['toplam'], 'sayi', '0E7490'), _srKpi('Kalite kaynaklı şikayet', count($l), 'sayi', 'DC2626'), $kpiY('Oranı', $sk['toplam'] ? round(count($l) / $sk['toplam'] * 100, 1) : null, 'B91C1C'),
                        _srKpi('Önceki dönem', count($so['liste']), 'sayi', '7F1D1D'), _srKpi('Çözülen', count(array_filter($l, fn ($x) => $x['cozuldu'])), 'sayi', '059669')],
                        [$blok('Konuya göre', 'pie', '0E7490', 'Konu', $sayDagilim($l, fn ($x) => $x['konu'])), $blok('Günlere göre', 'col', 'DC2626', 'Gün', $sayDagilim($l, fn ($x) => date('d.m', strtotime($x['tarih']))))], !$l ? $bos : null),
                    _srSayfa('Kalite Kaynaklı Şikayetler', 'QR memnuniyette "Yemek" / "Temizlik" seçilen ya da yorumunda bayat, bozuk, kıl, böcek, kirli, mide vb. geçen bildirimler; değerlendirmede lezzet puanı 1-2 olanlar.', [['Tarih', 't', 11], ['Kaynak', 'm', 14], ['Masa', 'm', 9], ['Puan', 's', 6], ['Konu', 'm', 22], ['Not / Yorum', 'm', 50], ['Durum', 'm', 10]],
                        array_map(fn ($x) => [$x['tarih'], $x['kaynak'], $x['masa'], $x['puan'], $x['konu'], $x['not'], $x['durum']], $l), $bos),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_klrAiBaglam')) {
    function _klrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $u = _klrUyum($sid, $bas, $bit); $s = _klrSicaklik($sid, $bas, $bit); $ss = _klrSicakSay($s); $dof = _klrDof($sid, $bas, $bit);
        $pr = _klrPartiler($sid); $nm = _klrNumune($sid, $bas, $bit); $fr = _klrFire($sid, $bas, $bit); $mk = _klrMalKabul($sid, $bas, $bit); $sk = _klrSikayet($sid, $bas, $bit); $h = _klrHijyen($sid); $ur = _klrUrunler($sid);
        $tak = fn ($t, $g) => collect(_klrTakvim($sid, $t, $g))->map(fn ($x) => ['kayit' => $x['baslik'], 'sonraki' => $x['sonraki'], 'durum' => $x['durum']])->take(8)->values()->all();
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _klrVeriDurumu($sid),
            'kontrol_listesi' => ['uyum_yuzde' => _klrUyumOran($u), 'onceki_uyum_yuzde' => _klrUyumOran(_klrUyum($sid, $dn['obas'], $dn['obit'])), 'en_dusuk' => array_map(fn ($x) => ['kontrol' => $x['baslik'], 'uyum' => $x['uyum'], 'eksik' => count($x['eksik'])], array_slice($u, 0, 6))],
            'sicaklik' => $ss + ['onceki_oran' => _klrSicakSay(_klrSicaklik($sid, $dn['obas'], $dn['obit']))['oran'], 'ekipman_limit_disi' => collect($s)->filter(fn ($x) => $x['uygun'] === false)->groupBy('ekipman')->map->count()->sortDesc()->take(6)->all()],
            'dof' => ['acilan' => count(array_filter($dof, fn ($x) => $x['acilis'] >= $bas)), 'acik' => count(array_filter($dof, fn ($x) => in_array($x['durum'], ['acik', 'yapiliyor'], true))), 'otomatik_sicaklik' => count(array_filter($dof, fn ($x) => $x['oto'])),
                'ort_kapanma_saat' => ($k = array_filter(array_column(array_filter($dof, fn ($x) => $x['durum'] === 'tamam'), 'saat'), fn ($x) => $x !== null)) ? round(array_sum($k) / count($k), 1) : null],
            'numune' => ['uyum_yuzde' => $nm['uyum'], 'eksik_gun' => count($nm['eksik'])], 'ilaclama' => $tak('ilaclama', 30), 'kalibrasyon' => $tak('kalibrasyon', 365), 'tedarikci_belge' => $tak('tedarikci_belge', 0),
            'denetim' => array_map(fn ($x) => ['tarih' => $x['tarih'], 'denetim' => $x['baslik'], 'sonuc' => $x['deger'], 'bulgu' => mb_substr($x['not'], 0, 200)], array_slice(_klrKayit($sid, 'denetim'), -4)),
            'alerjen' => ['aktif_urun' => count($ur), 'girilmemis' => count(array_filter($ur, fn ($x) => !$x['girilmis']))],
            'skt' => ['gecmis_parti' => count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT geçti')), 'yedi_gun' => count(array_filter($pr, fn ($x) => $x['durum'] === '7 gün içinde')), 'risk_tl_30_gun' => round(array_sum(array_column(array_filter($pr, fn ($x) => in_array($x['durum'], ['SKT geçti', '7 gün içinde', '30 gün içinde'], true)), 'deger')), 2), 'skt_girilmemis' => count(array_filter($pr, fn ($x) => $x['durum'] === 'SKT girilmemiş'))],
            'imha' => ['toplam_tl' => round(array_sum(array_column($fr, 'maliyet')), 2), 'skt_kaynakli_tl' => round(array_sum(array_column(array_filter($fr, fn ($x) => $x['skt']), 'maliyet')), 2)],
            'mal_kabul' => ['kalem' => count($mk), 'red' => count(array_filter($mk, fn ($x) => $x['red'] > 0)), 'lot_skt_eksik' => count(array_filter($mk, fn ($x) => $x['kabul'] > 0 && ($x['lot'] === '' || !$x['skt'])))],
            'personel_belge' => ['aktif' => count($h), 'eksik_veya_gecmis' => count(array_filter($h, fn ($x) => in_array($x['saglik_durum'], ['Belge yok', 'Süresi geçti'], true) || in_array($x['hijyen_durum'], ['Belge yok', 'Süresi geçti'], true)))],
            'musteri_sikayet' => ['geri_bildirim' => $sk['toplam'], 'kalite_kaynakli' => count($sk['liste']), 'konular' => collect($sk['liste'])->groupBy('konu')->map->count()->all()],
            'denetime_hazirlik_eksikleri' => array_values(array_map(fn ($x) => $x[0] . ': ' . $x[2], array_filter(_klrHazirlik($sid), fn ($x) => $x[1] === 'Eksik')))];
    }
    function _klrAiNabiz($sid, array $dn)
    {
        $b = _klrAiBaglam($sid, $dn); $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $uy = $b['kontrol_listesi']['uyum_yuzde']; $hz = count($b['denetime_hazirlik_eksikleri']);
        return [
            ['renk' => '#059669', 'kat' => 'kontrol', 'ikon' => 'checklist', 'etiket' => 'Kontrol uyumu', 'deger' => $y($uy), 'alt' => $b['kontrol_listesi']['onceki_uyum_yuzde'] !== null ? 'Önceki dönem ' . $y($b['kontrol_listesi']['onceki_uyum_yuzde']) : 'Tekrarlı kontrol yok', 'sinif' => $uy === null ? '' : ($uy >= 90 ? 'yesil' : ($uy >= 75 ? 'turuncu' : 'kirmizi'))],
            ['renk' => '#0284C7', 'kat' => 'sicaklik', 'ikon' => 'thermostat', 'etiket' => 'Limit dışı sıcaklık', 'deger' => (string) $b['sicaklik']['uygunsuz'], 'alt' => $b['sicaklik']['n'] . ' ölçümün ' . $y($b['sicaklik']['oran']), 'sinif' => $b['sicaklik']['uygunsuz'] ? 'kirmizi' : ($b['sicaklik']['n'] ? 'yesil' : '')],
            ['renk' => '#DC2626', 'kat' => 'dof', 'ikon' => 'report_problem', 'etiket' => 'Açık uygunsuzluk', 'deger' => (string) $b['dof']['acik'], 'alt' => $b['dof']['acilan'] . ' açıldı · ort. ' . ($b['dof']['ort_kapanma_saat'] !== null ? number_format($b['dof']['ort_kapanma_saat'], 1, ',', '.') . ' saatte kapandı' : 'kapanan yok'), 'sinif' => $b['dof']['acik'] ? 'kirmizi' : 'yesil'],
            ['renk' => '#7C3AED', 'kat' => 'kayit', 'ikon' => 'science', 'etiket' => 'Şahit numune', 'deger' => $y($b['numune']['uyum_yuzde']), 'alt' => $b['numune']['eksik_gun'] . ' gün numune eksik', 'sinif' => $b['numune']['eksik_gun'] ? 'turuncu' : 'yesil'],
            ['renk' => '#C2410C', 'kat' => 'gida', 'ikon' => 'event_busy', 'etiket' => 'SKT geçmiş parti', 'deger' => (string) $b['skt']['gecmis_parti'], 'alt' => number_format($b['skt']['risk_tl_30_gun'], 0, ',', '.') . ' ₺ stok 30 gün içinde riskte', 'sinif' => $b['skt']['gecmis_parti'] ? 'kirmizi' : ''],
            ['renk' => '#B91C1C', 'kat' => 'dof', 'ikon' => 'verified_user', 'etiket' => 'Denetime hazırlık', 'deger' => $hz ? $hz . ' eksik' : 'Hazır', 'alt' => $hz ? mb_substr($b['denetime_hazirlik_eksikleri'][0], 0, 48) : 'Açık bulgu yok', 'sinif' => $hz ? 'turuncu' : 'yesil'],
        ];
    }
}

// ---------- Modül kaydı ----------
_drKaydet('kalite', [
    'ad' => 'Kalite', 'baslik' => 'Kalite Raporları', 'yol' => '/kalite-raporlar', 'grup' => 'Kalite / HACCP', 'depKodlari' => ['kalite'],
    'ensure' => '_klrEnsure', 'katalog' => '_klrKatalog', 'kategoriler' => '_klrKategoriler', 'veri' => '_klrVeriDurumu', 'rapor' => '_klrRapor',
    'bosTablolar' => ['kalite_kayitlari', 'mutfak_kayitlari', 'stok_partileri', 'memnuniyet'],
    'olcek' => ['kucuk' => ['donemsel', 'kontrol', 'ai'], 'orta' => ['sicaklik', 'dof', 'gida']],
    'modAd' => ['kontrol' => 'Kalite kontrol listesi', 'sicaklik' => 'Sıcaklık ölçümü', 'olay' => 'Uygunsuzluk (DÖF)', 'numune' => 'Şahit numune', 'ilaclama' => 'İlaçlama', 'kalibrasyon' => 'Kalibrasyon',
        'tedarikci_belge' => 'Tedarikçi belgesi', 'denetim' => 'Denetim kaydı', 'urun' => 'Menü ürünü', 'alerjen' => 'Alerjen bilgisi', 'parti' => 'SKT / lot partisi', 'fire' => 'Fire / imha',
        'mal_kabul' => 'Mal kabul', 'belge' => 'Personel sağlık-hijyen belgesi', 'memnuniyet' => 'Müşteri geri bildirimi', 'satis' => 'Satış (açık gün)'],
    'modLink' => ['kontrol' => ['/kalite', 'Kalite / HACCP > Kontrol Listesi (tekrarlı kontrol)'], 'sicaklik' => ['/kalite', 'Kontrol Listesi\'nde °C girerek ya da Mutfak > Kontrol Defteri'], 'olay' => ['/kalite', 'Kontrol Listesi > Uygunsuzluk / düzeltici aksiyon'],
        'numune' => ['/kalite-kayit?tip=numune', 'Kayıt Defteri > Şahit Numune'], 'ilaclama' => ['/kalite-kayit?tip=ilaclama', 'Kayıt Defteri > İlaçlama'], 'kalibrasyon' => ['/kalite-kayit?tip=kalibrasyon', 'Kayıt Defteri > Kalibrasyon'],
        'tedarikci_belge' => ['/kalite-kayit?tip=tedarikci_belge', 'Kayıt Defteri > Tedarikçi Belgesi'], 'denetim' => ['/kalite-kayit?tip=denetim', 'Kayıt Defteri > Denetim'], 'urun' => ['/menu', 'Menü ürünleri'],
        'alerjen' => ['/alerjen', 'Alerjen Matrisi'], 'parti' => ['/parti-takip', 'Mal kabulde SKT / lot girilince'], 'fire' => ['/mutfak-defteri?tip=fire', 'Fire kaydı (sebebiyle)'], 'mal_kabul' => ['/mal-kabul', 'Mal Kabul'],
        'belge' => ['/ik-sicil', 'İK Sicil > Belgeler (Sağlık / Hijyen)'], 'memnuniyet' => [null, 'QR memnuniyet anketi / değerlendirme'], 'satis' => ['/pos', 'POS / adisyon']],
    'ai' => [
        'gerekli' => ['kontrol', 'sicaklik', 'olay', 'numune', 'parti'],
        'hero' => ['Gıda güvenliğinin dönemini 20 saniyede özetleyeyim.', 'Kontrol listesi uyumu, sıcaklık ve limit dışılar, uygunsuzluk ve kapanma süresi, şahit numune, ilaçlama-kalibrasyon takvimi, denetimler, alerjen, SKT / lot, imha, mal kabul, personel belgeleri ve müşteri şikayetlerini okur.'],
        'sorular' => ['Bugün denetim gelse nerede açık veririm?', 'Hangi dolap sık limit dışı çıkıyor?', 'Hangi kontroller aksıyor, kim sorumlu?', 'SKT yüzünden ne kadar ürün çöpe gidiyor?', 'Hangi tedarikçiden gelen mal sorunlu?', 'Belgesi eksik personel var mı?'],
        'adimlar' => ['Kontrol listesi ve ölçümler okunuyor…', 'Sıcaklık limitleri karşılaştırılıyor…', 'Uygunsuzluklar ve kapanma süreleri inceleniyor…', 'Numune, ilaçlama ve denetim kayıtları kontrol ediliyor…', 'SKT, imha ve mal kabul tarandı…', 'Öneriler yazılıyor…'],
        'selam' => 'kalite ve gıda güvenliği',
        'sys' => 'Sen bir restoranın gıda güvenliğini (HACCP) patron ve kalite sorumlusu için analiz eden deneyimli bir gıda mühendisi / kalite denetçisisin. Türkiye mevzuatını bil: Türk Gıda Kodeksi, 5996 sayılı kanun, şahit numune 72 saat saklama, soğuk muhafaza 0-4 °C, dondurucu ≤ -18 °C, sıcak tutma ≥ 63 °C, 14 majör alerjen, personel portör / hijyen belgesi. '
            . 'Önceliği halk sağlığı riskine ver (limit dışı sıcaklık, SKT geçmiş ürün, açık uygunsuzluk), sonra kayıt eksiklerine. Denetçi gözüyle somut düzeltici faaliyet öner; panik dili ve suçlayıcı dil kullanma.',
        'baglam' => '_klrAiBaglam',
        'veriVar' => fn ($b) => array_sum(array_map('intval', $b['kullanilan_moduller'])) > 0,
        'nabiz' => '_klrAiNabiz',
    ],
]);

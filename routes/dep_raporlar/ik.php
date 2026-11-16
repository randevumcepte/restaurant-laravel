<?php
// =====================================================================
// İK RAPORLAR & ANALİZLER — /ik-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: personeller (maaş, giriş/çıkış), personel_vardiya (plan), personel_mesai (QR fiili), personel_izinler, personel_hareketleri,
//       adisyonlar (ciro, açan garson), odemeler.bahsis, iptal_indirim_loglari, personel_adim, personel_belge/egitim/zimmet, ik_adaylar, personel_yemek.
// Faz 2 (bundan sonrasını ölçer): personeller.ayrilis_sebep + pasife alınca ise_cikis otomatik (_ikrAyrilisIsle),
//       ik_adaylar.durum_at / ise_alindi_at (_ikrAdayDamga), işveren maliyet çarpanı + geç kalma toleransı (_ikrAyar).
// Erişim: patron / müdür / süpervizör / İK sorumlusu / 'gor_ik_rapor' her şeyi; diğer departman sorumluları yalnız KENDİ ekibinin
//       devam, vardiya, izin, performans ve uyum raporlarını görür (maaş / maliyet / bordro / AI yok).
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2 alanları + ayarlar ----------
if (!function_exists('_ikrEnsure')) {
    function _ikrEnsure()
    {
        if (cache()->get('ikr_ensure_v1')) return;
        try { if (function_exists('_restoPersonelKolonEnsure')) _restoPersonelKolonEnsure(); } catch (\Throwable $e) {}
        try { if (Schema::hasTable('personeller') && !Schema::hasColumn('personeller', 'ayrilis_sebep')) Schema::table('personeller', function ($t) { $t->string('ayrilis_sebep', 24)->nullable(); }); } catch (\Throwable $e) {}
        try { if (function_exists('_ikAdayEnsure')) _ikAdayEnsure(); } catch (\Throwable $e) {}
        foreach (['durum_at', 'ise_alindi_at'] as $k) {
            try { if (Schema::hasTable('ik_adaylar') && !Schema::hasColumn('ik_adaylar', $k)) Schema::table('ik_adaylar', function ($t) use ($k) { $t->timestamp($k)->nullable(); }); } catch (\Throwable $e) {}
        }
        cache()->put('ikr_ensure_v1', 1, now()->addHours(12));
    }
    function _ikrAyrilisSebepleri()
    {
        return ['istifa' => 'İstifa (kendi isteğiyle)', 'cikarma' => 'İşten çıkarma', 'deneme' => 'Deneme süresinde ayrıldı', 'devamsizlik' => 'Devamsızlık / haber vermeden bıraktı',
            'sezon' => 'Sezon / sözleşme bitti', 'emeklilik' => 'Emeklilik', 'nakil' => 'Başka şubeye geçti', 'diger' => 'Diğer'];
    }
    // Personel pasife alınınca ayrılış tarihi (+ seçildiyse sebebi) yazılır, tekrar aktif olunca silinir. $hedef eski kayıt, $veri güncelleme dizisi.
    function _ikrAyrilisIsle($hedef, array $veri, $r)
    {
        try {
            _ikrEnsure();
            if (!$hedef || !array_key_exists('aktif', $veri) || !Schema::hasColumn('personeller', 'ise_cikis')) return $veri;
            $eski = (int) ($hedef->aktif ?? 1); $yeni = (int) $veri['aktif']; $sebepVar = Schema::hasColumn('personeller', 'ayrilis_sebep');
            $tarih = (string) $r->input('ise_cikis', ''); $tarih = preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih) ? $tarih : null; $sebep = (string) $r->input('ayrilis_sebep', '');
            if ($yeni === 0) {
                if ($tarih) $veri['ise_cikis'] = $tarih; elseif ($eski === 1 || empty($hedef->ise_cikis)) $veri['ise_cikis'] = today()->toDateString();
                if ($sebepVar && isset(_ikrAyrilisSebepleri()[$sebep])) $veri['ayrilis_sebep'] = $sebep;
            } elseif ($eski === 0) {
                $veri['ise_cikis'] = null; if ($sebepVar) $veri['ayrilis_sebep'] = null;
            }
        } catch (\Throwable $e) {}
        return $veri;
    }
    // Aday durumu değişince zaman damgası (işe alım süresi raporu)
    function _ikrAdayDamga(array $upd)
    {
        try {
            _ikrEnsure();
            if (isset($upd['durum']) && Schema::hasColumn('ik_adaylar', 'durum_at')) $upd['durum_at'] = now();
            if (($upd['durum'] ?? '') === 'islendi' && Schema::hasColumn('ik_adaylar', 'ise_alindi_at')) $upd['ise_alindi_at'] = now();
        } catch (\Throwable $e) {}
        return $upd;
    }
    // Şube ayarı: işveren maliyet çarpanı (maaş × çarpan = gerçek maliyet) ve geç kalma toleransı (dk)
    function _ikrAyar($sid)
    {
        $al = fn ($k, $v) => function_exists('_subeAyarAl') ? _subeAyarAl($sid, $k, $v) : $v;
        $c = (float) str_replace(',', '.', (string) $al('ikr_isveren_carpan', '1')); $t = (int) $al('ikr_gec_tolerans', '5');
        return ['carpan' => ($c >= 1 && $c <= 3) ? round($c, 2) : 1.0, 'tolerans' => max(0, min(60, $t))];
    }
}

// ---------- Kapsam: tam erişim mi, yoksa yalnız kendi departmanı mı ----------
if (!function_exists('_ikrTam')) {
    function _ikrTam($p = null)
    {
        if (!$p) { try { $p = _posPersonel(); } catch (\Throwable $e) {} }
        if (!$p) return true; // komut satırı / iç çağrı
        $kad = $p->kademe ?? (function_exists('_rolKademe') ? _rolKademe($p->rol ?? '') : '');
        if (in_array($p->rol ?? '', ['sahip', 'mudur'], true) || in_array($kad, ['sahip', 'supervizor'], true)) return true;
        if (function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_ik_rapor')) return true;
        if (!empty($p->departman_sorumlu) && !empty($p->departman_id)) { try { return DB::table('departmanlar')->where('id', (int) $p->departman_id)->value('kod') === 'ik'; } catch (\Throwable $e) {} }
        return false;
    }
    function _ikrKapsamDep()
    {
        $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {}
        return (!$p || _ikrTam($p)) ? null : (int) ($p->departman_id ?? -1);
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_ikrPersonel')) {
    function _ikrRolAd($r) { return ['sahip' => 'Patron', 'mudur' => 'Müdür', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak', 'satinalmaci' => 'Satın Alma'][$r] ?? ($r ?: '—'); }
    // Şubenin personeli (patron hariç; kapsam kısıtlıysa yalnız kendi departmanı). Pasif ve ayrılış tarihi yoksa son güncelleme tarihi "tahmini ayrılış" sayılır.
    function _ikrPersonel($sid)
    {
        static $c = [];
        $dep = _ikrKapsamDep(); $key = $sid . '|' . $dep;
        if (isset($c[$key])) return $c[$key];
        if (!_srTablo('personeller')) return $c[$key] = [];
        $depAd = []; try { if (_srTablo('departmanlar')) $depAd = DB::table('departmanlar')->pluck('ad', 'id')->all(); } catch (\Throwable $e) {}
        try { $rows = DB::table('personeller')->where('sube_id', $sid)->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $p) {
            if (($p->rol ?? '') === 'sahip') continue;
            if ($dep !== null && (int) ($p->departman_id ?? 0) !== $dep) continue;
            $cik = !empty($p->ise_cikis) ? substr((string) $p->ise_cikis, 0, 10) : null; $tahmini = false;
            if (!(int) $p->aktif && !$cik) { $cik = substr((string) ($p->updated_at ?? $p->created_at ?? ''), 0, 10) ?: null; $tahmini = (bool) $cik; }
            $o[(int) $p->id] = (object) ['id' => (int) $p->id, 'ad' => (string) $p->ad, 'rol' => (string) $p->rol, 'rolAd' => _ikrRolAd($p->rol), 'aktif' => (int) $p->aktif,
                'dep' => $depAd[$p->departman_id ?? 0] ?? _ikrRolAd($p->rol), 'dep_id' => (int) ($p->departman_id ?? 0),
                'maas' => (float) ($p->maas ?? 0), 'maas_tipi' => $p->maas_tipi ?? 'aylik', 'prim_tipi' => $p->prim_tipi ?? 'yok', 'prim_oran' => (float) ($p->prim_oran ?? 0),
                'giris' => !empty($p->ise_baslama) ? substr((string) $p->ise_baslama, 0, 10) : null, 'kayit' => substr((string) ($p->created_at ?? ''), 0, 10) ?: null,
                'cikis' => $cik, 'cikis_tahmini' => $tahmini, 'sebep' => $p->ayrilis_sebep ?? null, 'dogum' => !empty($p->dogum_tarihi) ? substr((string) $p->dogum_tarihi, 0, 10) : null,
                'tc' => $p->tc ?? null, 'iban' => $p->iban ?? null, 'acil' => $p->acil_tel ?? null, 'telefon' => $p->telefon ?? null, 'ham' => $p];
        }
        return $c[$key] = $o;
    }
    function _ikrBaslangic($p) { return $p->giris ?: $p->kayit; }
    function _ikrKadroda($p, $tarih) { $g = _ikrBaslangic($p); return (!$g || $g <= $tarih) && (!$p->cikis || $p->cikis > $tarih) && ($p->aktif || $p->cikis); }
    function _ikrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _ikrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    // QR mesai kayıtları (giriş tarihine göre). Açık mesai bugünse şu ana kadar sayılır; 16 saati aşan (unutulmuş) ölçüme katılmaz.
    function _ikrMesai($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit|" . _ikrKapsamDep(); if (isset($c[$key])) return $c[$key];
        if (!_srTablo('personel_mesai')) return $c[$key] = [];
        $per = _ikrPersonel($sid); $o = []; $simdi = time(); $bugun = date('Y-m-d');
        try { $rows = DB::table('personel_mesai')->where('sube_id', $sid)->whereBetween('giris_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('giris_at')->get(['personel_id', 'giris_at', 'cikis_at']); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) {
            $pid = (int) $r->personel_id; if (!isset($per[$pid]) || !$r->giris_at) continue;
            $g = strtotime((string) $r->giris_at); $cx = $r->cikis_at ? strtotime((string) $r->cikis_at) : null;
            $son = $cx ?: (date('Y-m-d', $g) === $bugun ? $simdi : null);
            $saat = $son ? round(($son - $g) / 3600, 2) : null; if ($saat !== null && ($saat < 0 || $saat > 16)) $saat = null;
            $o[] = ['pid' => $pid, 'tarih' => date('Y-m-d', $g), 'giris' => $g, 'cikis' => $cx, 'son' => $son, 'saat' => $saat, 'acik' => !$cx];
        }
        return $c[$key] = $o;
    }
    // Vardiya planı; bitiş başlangıçtan önceyse gece vardiyası (ertesi gün biter)
    function _ikrVardiya($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit|" . _ikrKapsamDep(); if (isset($c[$key])) return $c[$key];
        if (!_srTablo('personel_vardiya')) return $c[$key] = [];
        $per = _ikrPersonel($sid); $o = [];
        try { $rows = DB::table('personel_vardiya')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->get(['personel_id', 'tarih', 'baslangic', 'bitis', 'pozisyon']); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) {
            $pid = (int) $r->personel_id; if (!isset($per[$pid]) || !$r->baslangic || !$r->bitis) continue;
            $t = substr((string) $r->tarih, 0, 10); $b = strtotime($t . ' ' . $r->baslangic); $e = strtotime($t . ' ' . $r->bitis); if ($e <= $b) $e += 86400;
            $o[] = ['pid' => $pid, 'tarih' => $t, 'bas' => $b, 'bit' => $e, 'saat' => round(($e - $b) / 3600, 2), 'pozisyon' => (string) ($r->pozisyon ?: $per[$pid]->dep)];
        }
        return $c[$key] = $o;
    }
    function _ikrIzinTipAd($t) { return ['yillik' => 'Yıllık izin', 'haftalik' => 'Haftalık izin', 'rapor' => 'Rapor (hastalık)', 'mazeret' => 'Mazeret izni', 'ucretsiz' => 'Ücretsiz izin', 'dogum' => 'Doğum izni'][$t] ?? ($t ?: '—'); }
    function _ikrIzinDurumAd($d) { return ['talep' => 'Onay bekliyor', 'onay' => 'Onaylandı', 'ret' => 'Reddedildi', 'red' => 'Reddedildi'][$d] ?? ($d ?: '—'); }
    // Dönemle kesişen izinler ($durum null = hepsi)
    function _ikrIzinler($sid, $bas, $bit, $durum = 'onay')
    {
        if (!_srTablo('personel_izinler')) return [];
        $per = _ikrPersonel($sid); $o = [];
        try { $q = DB::table('personel_izinler')->where('sube_id', $sid)->where('baslangic', '<=', $bit)->where('bitis', '>=', $bas); if ($durum) $q->where('durum', $durum); $rows = $q->orderBy('baslangic')->get(); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) {
            $pid = (int) $r->personel_id; if (!isset($per[$pid])) continue;
            $b = substr((string) $r->baslangic, 0, 10); $e = substr((string) $r->bitis, 0, 10);
            $kb = max($b, $bas); $ke = min($e, $bit); $donemGun = $ke >= $kb ? count(_ikrGunler($kb, $ke)) : 0;
            $o[] = ['pid' => $pid, 'ad' => $per[$pid]->ad, 'dep' => $per[$pid]->dep, 'tip' => (string) $r->tip, 'bas' => $b, 'bit' => $e, 'gun' => (float) ($r->gun ?: count(_ikrGunler($b, $e))),
                'donem_gun' => $donemGun, 'durum' => (string) $r->durum, 'aciklama' => (string) ($r->aciklama ?? ''), 'talep' => substr((string) ($r->created_at ?? ''), 0, 10), 'karar' => substr((string) ($r->karar_at ?? ''), 0, 10)];
        }
        return $o;
    }
    function _ikrIzinGun($sid, $bas, $bit)
    {
        $o = []; foreach (_ikrIzinler($sid, $bas, $bit) as $z) foreach (_ikrGunler(max($z['bas'], $bas), min($z['bit'], $bit)) as $t) $o[$z['pid']][$t] = $z['tip'];
        return $o;
    }
    // Ödenmiş adisyonlar (ciro, açan garson, misafir)
    function _ikrAdisyon($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('adisyonlar')) return $c[$key] = [];
        $sel = ['id', 'toplam', 'kapanis']; foreach (['acan_personel_id', 'misafir_sayisi'] as $k) $sel[] = _srKolon('adisyonlar', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereBetween('kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) { $t = strtotime((string) $r->kapanis); $o[] = ['id' => (int) $r->id, 'toplam' => (float) $r->toplam, 'tarih' => date('Y-m-d', $t), 'saat' => (int) date('G', $t), 'gun' => (int) date('N', $t), 'acan' => (int) $r->acan_personel_id, 'misafir' => (int) $r->misafir_sayisi]; }
        return $c[$key] = $o;
    }
    function _ikrCiro($sid, $bas, $bit) { return round(array_sum(array_column(_ikrAdisyon($sid, $bas, $bit), 'toplam')), 2); }
    function _ikrHareket($sid, $bas, $bit)
    {
        if (!_srTablo('personel_hareketleri')) return [];
        $per = _ikrPersonel($sid); $o = [];
        try { $rows = DB::table('personel_hareketleri')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->get(['personel_id', 'tur', 'tutar', 'tarih', 'aciklama']); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) if (isset($per[(int) $r->personel_id])) $o[] = ['pid' => (int) $r->personel_id, 'tur' => (string) $r->tur, 'tutar' => (float) $r->tutar, 'tarih' => substr((string) $r->tarih, 0, 10), 'aciklama' => (string) $r->aciklama];
        return $o;
    }
    // İşçilik maliyeti kişi × gün: aylık maaş /30 her kadro günü; günlük maaş × mesaiye gelinen gün; saatlik × fiili saat (mesai yoksa vardiya planı).
    // Üstüne prim / ek ödeme (hareket tarihinde) ve ciro primi (adisyon kapanış gününde); hepsi × işveren çarpanı.
    function _ikrMaliyet($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit|" . _ikrKapsamDep(); if (isset($c[$key])) return $c[$key];
        $per = _ikrPersonel($sid); $ay = _ikrAyar($sid); $gunler = _ikrGunler($bas, $bit);
        $fs = []; $fg = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) { $fs[$m['pid']][$m['tarih']] = ($fs[$m['pid']][$m['tarih']] ?? 0) + ($m['saat'] ?? 0); $fg[$m['pid']][$m['tarih']] = 1; }
        $ps = []; foreach (_ikrVardiya($sid, $bas, $bit) as $v) $ps[$v['pid']][$v['tarih']] = ($ps[$v['pid']][$v['tarih']] ?? 0) + $v['saat'];
        $ek = []; foreach (_ikrHareket($sid, $bas, $bit) as $h) if (in_array($h['tur'], ['prim', 'ek_odeme'], true)) $ek[$h['pid']][$h['tarih']] = ($ek[$h['pid']][$h['tarih']] ?? 0) + $h['tutar'];
        $ciroP = []; foreach (_ikrAdisyon($sid, $bas, $bit) as $a) if ($a['acan'] && isset($per[$a['acan']]) && $per[$a['acan']]->prim_tipi === 'ciro') $ciroP[$a['acan']][$a['tarih']] = ($ciroP[$a['acan']][$a['tarih']] ?? 0) + $a['toplam'] * $per[$a['acan']]->prim_oran / 100;
        $kisi = []; $gun = array_fill_keys($gunler, 0.0);
        foreach ($per as $p) {
            $temel = 0; $prim = 0; $kadroGun = 0; $planli = false;
            $saatToplam = array_sum($fs[$p->id] ?? []); $mesaiVar = $saatToplam > 0;
            foreach ($gunler as $t) {
                $tm = 0;
                if ($p->maas_tipi === 'saatlik') { $s = $mesaiVar ? ($fs[$p->id][$t] ?? 0) : ($ps[$p->id][$t] ?? 0); $tm = $p->maas * $s; if (!$mesaiVar && $s) $planli = true; }
                elseif ($p->maas_tipi === 'gunluk') { $g = $mesaiVar ? isset($fg[$p->id][$t]) : isset($ps[$p->id][$t]); $tm = $g ? $p->maas : 0; if (!$mesaiVar && $g) $planli = true; }
                elseif (_ikrKadroda($p, $t)) { $tm = $p->maas / 30; $kadroGun++; }
                $pr = ($ek[$p->id][$t] ?? 0) + ($ciroP[$p->id][$t] ?? 0);
                $temel += $tm; $prim += $pr; $gun[$t] += ($tm + $pr) * $ay['carpan'];
            }
            if ($temel <= 0 && $prim <= 0 && $saatToplam <= 0 && !$kadroGun) continue;
            $top = round(($temel + $prim) * $ay['carpan'], 2);
            $baz = $p->maas_tipi === 'saatlik' ? 'Saatlik' . ($planli ? ' (plan saati)' : '') : ($p->maas_tipi === 'gunluk' ? 'Günlük' . ($planli ? ' (plan günü)' : '') : 'Aylık · ' . $kadroGun . ' gün');
            $kisi[$p->id] = ['pid' => $p->id, 'ad' => $p->ad, 'dep' => $p->dep, 'baz' => $baz, 'maas' => $p->maas, 'temel' => round($temel, 2), 'prim' => round($prim, 2), 'toplam' => $top,
                'saat' => round($saatToplam, 1), 'saatlik' => $saatToplam > 0 ? round($top / $saatToplam, 2) : null, 'maassiz' => $p->maas <= 0];
        }
        uasort($kisi, fn ($a, $b) => $b['toplam'] <=> $a['toplam']);
        return $c[$key] = ['kisi' => $kisi, 'gun' => array_map(fn ($x) => round($x, 2), $gun), 'toplam' => round(array_sum(array_column($kisi, 'toplam')), 2), 'carpan' => $ay['carpan']];
    }
    function _ikrSaat($sid, $bas, $bit) { return round(array_sum(array_map(fn ($m) => $m['saat'] ?? 0, _ikrMesai($sid, $bas, $bit))), 1); }
    // Devam: vardiya planı ile QR mesai eşleşmesi (aynı kişi, aynı gün)
    function _ikrDevam($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit|" . _ikrKapsamDep(); if (isset($c[$key])) return $c[$key];
        $per = _ikrPersonel($sid); $tol = _ikrAyar($sid)['tolerans']; $mes = _ikrMesai($sid, $bas, $bit); $izin = _ikrIzinGun($sid, $bas, $bit); $simdi = time();
        $mp = []; foreach ($mes as $i => $m) $mp[$m['pid']][$m['tarih']][] = $i;
        $kul = []; $o = [];
        $bos = fn ($pid, $t) => ['pid' => $pid, 'ad' => $per[$pid]->ad, 'dep' => $per[$pid]->dep, 'tarih' => $t, 'plan_bas' => '', 'plan_bit' => '', 'plan_saat' => 0, 'giris' => '', 'cikis' => '', 'fiili' => 0, 'gec' => 0, 'erken' => 0, 'fazla' => 0, 'durum' => ''];
        foreach (_ikrVardiya($sid, $bas, $bit) as $v) {
            $s = $bos($v['pid'], $v['tarih']); $s['plan_bas'] = date('H:i', $v['bas']); $s['plan_bit'] = date('H:i', $v['bit']); $s['plan_saat'] = $v['saat'];
            $ml = $mp[$v['pid']][$v['tarih']] ?? [];
            if (!$ml) {
                if (isset($izin[$v['pid']][$v['tarih']])) $s['durum'] = 'izinli';
                elseif ($v['bas'] > $simdi) continue; // vardiya henüz başlamadı
                else $s['durum'] = 'gelmedi';
                $o[] = $s; continue;
            }
            foreach ($ml as $i) $kul[$i] = 1;
            $g = min(array_map(fn ($i) => $mes[$i]['giris'], $ml)); $cs = array_filter(array_map(fn ($i) => $mes[$i]['cikis'], $ml)); $cx = $cs ? max($cs) : null;
            $s['giris'] = date('H:i', $g); $s['cikis'] = $cx ? date('H:i', $cx) : ''; $s['fiili'] = round(array_sum(array_map(fn ($i) => $mes[$i]['saat'] ?? 0, $ml)), 2);
            $s['gec'] = max(0, (int) round(($g - $v['bas']) / 60)); $s['erken'] = $cx ? max(0, (int) round(($v['bit'] - $cx) / 60)) : 0; $s['fazla'] = $cx ? max(0, (int) round(($cx - $v['bit']) / 60)) : 0;
            $gm = $s['gec'] > $tol; $em = $s['erken'] > $tol;
            $s['durum'] = !$cx ? ($gm ? 'gec_acik' : 'acik') : ($gm && $em ? 'gec_erken' : ($gm ? 'gec' : ($em ? 'erken' : 'zamaninda')));
            $o[] = $s;
        }
        foreach ($mes as $i => $m) {
            if (isset($kul[$i])) continue;
            $s = $bos($m['pid'], $m['tarih']); $s['giris'] = date('H:i', $m['giris']); $s['cikis'] = $m['cikis'] ? date('H:i', $m['cikis']) : ''; $s['fiili'] = (float) ($m['saat'] ?? 0); $s['durum'] = 'plansiz';
            $o[] = $s;
        }
        usort($o, fn ($a, $b) => [$a['tarih'], $a['ad']] <=> [$b['tarih'], $b['ad']]);
        return $c[$key] = $o;
    }
    function _ikrDurumAd($d) { return ['zamaninda' => 'Zamanında', 'gec' => 'Geç geldi', 'erken' => 'Erken çıktı', 'gec_erken' => 'Geç geldi + erken çıktı', 'gelmedi' => 'Gelmedi', 'izinli' => 'İzinli', 'plansiz' => 'Plansız mesai', 'acik' => 'Çıkış yapılmadı', 'gec_acik' => 'Geç geldi, çıkış yok'][$d] ?? $d; }
    function _ikrGecMi($d) { return in_array($d, ['gec', 'gec_erken', 'gec_acik'], true); }
    function _ikrDevamSay(array $dv)
    {
        $s = ['plan' => 0, 'gelmedi' => 0, 'gec' => 0, 'erken' => 0, 'plansiz' => 0, 'izinli' => 0, 'acik' => 0, 'gec_dk' => 0];
        foreach ($dv as $x) {
            if ($x['durum'] !== 'plansiz') $s['plan']++;
            if ($x['durum'] === 'gelmedi') $s['gelmedi']++; if ($x['durum'] === 'izinli') $s['izinli']++; if ($x['durum'] === 'plansiz') $s['plansiz']++;
            if (_ikrGecMi($x['durum'])) { $s['gec']++; $s['gec_dk'] += $x['gec']; }
            if (in_array($x['durum'], ['erken', 'gec_erken'], true)) $s['erken']++; if (in_array($x['durum'], ['acik', 'gec_acik'], true)) $s['acik']++;
        }
        $beklenen = $s['plan'] - $s['izinli']; $s['devam'] = $beklenen > 0 ? round(($beklenen - $s['gelmedi']) / $beklenen * 100, 1) : null;
        return $s;
    }
    // Haftalık çalışma (ISO hafta): 45 saati aşan kısım fazla mesai
    function _ikrHaftalik($sid, $bas, $bit)
    {
        $per = _ikrPersonel($sid); $h = [];
        foreach (_ikrMesai($sid, $bas, $bit) as $m) { $w = date('o-\WW', $m['giris']); $h[$m['pid']][$w] = ($h[$m['pid']][$w] ?? 0) + ($m['saat'] ?? 0); }
        $o = [];
        foreach ($h as $pid => $ws) foreach ($ws as $w => $s) { [$y, $n] = explode('-W', $w); $pzt = date('Y-m-d', strtotime($y . 'W' . $n)); $o[] = ['pid' => $pid, 'ad' => $per[$pid]->ad, 'dep' => $per[$pid]->dep, 'hafta' => $pzt, 'saat' => round($s, 1), 'fazla' => round(max(0, $s - 45), 1)]; }
        usort($o, fn ($a, $b) => [$b['hafta'], $b['saat']] <=> [$a['hafta'], $a['saat']]);
        return $o;
    }
    // Yıllık izin hakkı (4857 s. İK md. 53): 1-5 yıl 14, 5-15 yıl 20, 15+ yıl 26 gün; 18 yaş altı ve 50 yaş üstü en az 20 gün. Hak 1. yıl dolunca doğar.
    function _ikrIzinHak($p, $tarih)
    {
        $g = _ikrBaslangic($p); if (!$g) return null;
        $yil = 0; while (date('Y-m-d', strtotime($g . ' +' . ($yil + 1) . ' year')) <= $tarih) $yil++;
        $hak = $yil < 1 ? 0 : ($yil < 5 ? 14 : ($yil < 15 ? 20 : 26));
        if ($yil >= 1 && $p->dogum) { $yas = (int) floor((strtotime($tarih) - strtotime($p->dogum)) / (365.25 * 86400)); if ($yas < 18 || $yas >= 50) $hak = max($hak, 20); }
        return ['yil' => $yil, 'hak' => $hak, 'donem_bas' => date('Y-m-d', strtotime($g . ' +' . $yil . ' year')), 'sonraki' => date('Y-m-d', strtotime($g . ' +' . ($yil + 1) . ' year'))];
    }
    function _ikrKidemAy($p, $tarih) { $g = _ikrBaslangic($p); return $g ? max(0, (int) floor((strtotime($tarih) - strtotime($g)) / (30.44 * 86400))) : null; }
    function _ikrKidemMetin($ay) { if ($ay === null) return 'Giriş tarihi yok'; $y = intdiv($ay, 12); $a = $ay % 12; return trim(($y ? $y . ' yıl ' : '') . ($a || !$y ? $a . ' ay' : '')); }
    function _ikrKidemGrup($ay) { if ($ay === null) return 'Bilinmiyor'; return $ay < 3 ? '0-3 ay' : ($ay < 6 ? '3-6 ay' : ($ay < 12 ? '6-12 ay' : ($ay < 24 ? '1-2 yıl' : ($ay < 60 ? '2-5 yıl' : '5 yıl +')))); }
    function _ikrDevir($sid, $bas, $bit)
    {
        $per = _ikrPersonel($sid); $gun = count(_ikrGunler($bas, $bit));
        $basK = count(array_filter($per, fn ($p) => _ikrKadroda($p, $bas))); $sonK = count(array_filter($per, fn ($p) => _ikrKadroda($p, $bit)));
        $giren = array_values(array_filter($per, fn ($p) => ($g = _ikrBaslangic($p)) && $g >= $bas && $g <= $bit));
        $ayrilan = array_values(array_filter($per, fn ($p) => $p->cikis && $p->cikis >= $bas && $p->cikis <= $bit));
        $ort = ($basK + $sonK) / 2; $oran = $ort > 0 ? round(count($ayrilan) / $ort * 100, 1) : null;
        return ['bas' => $basK, 'son' => $sonK, 'giren' => $giren, 'ayrilan' => $ayrilan, 'oran' => $oran, 'yillik' => $oran !== null ? round($oran * 365 / max(1, $gun), 1) : null];
    }
    // Garson / satış performansı: açtığı ödenmiş adisyonlar, bahşiş, adım, iptal-ikram, saat başı satış
    function _ikrGarson($sid, $bas, $bit)
    {
        $per = _ikrPersonel($sid); $g = [];
        foreach (_ikrAdisyon($sid, $bas, $bit) as $a) { if (!$a['acan'] || !isset($per[$a['acan']])) continue; $x = &$g[$a['acan']]; $x['ciro'] = ($x['ciro'] ?? 0) + $a['toplam']; $x['adisyon'] = ($x['adisyon'] ?? 0) + 1; $x['misafir'] = ($x['misafir'] ?? 0) + $a['misafir']; unset($x); }
        if (_srKolon('odemeler', 'bahsis') && _srKolon('odemeler', 'personel_id')) {
            try { foreach (DB::table('odemeler as o')->join('adisyonlar as a', 'o.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->whereBetween('o.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->where('o.bahsis', '>', 0)->groupBy('o.personel_id')->selectRaw('o.personel_id pid, SUM(o.bahsis) t')->get() as $r) if (isset($per[(int) $r->pid])) $g[(int) $r->pid]['bahsis'] = (float) $r->t; } catch (\Throwable $e) {}
        }
        if (_srTablo('personel_adim')) { try { foreach (DB::table('personel_adim')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->groupBy('personel_id')->selectRaw('personel_id pid, SUM(adim) t')->get() as $r) if (isset($per[(int) $r->pid])) $g[(int) $r->pid]['adim'] = (int) $r->t; } catch (\Throwable $e) {} }
        foreach (_ikrIptal($sid, $bas, $bit) as $pid => $ip) if (isset($g[$pid]) || ($per[$pid]->rol ?? '') === 'garson') $g[$pid]['iptal'] = $ip;
        $saat = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) $saat[$m['pid']] = ($saat[$m['pid']] ?? 0) + ($m['saat'] ?? 0);
        $o = [];
        foreach ($g as $pid => $x) {
            $ciro = round($x['ciro'] ?? 0, 2); $ad = (int) ($x['adisyon'] ?? 0); $s = round($saat[$pid] ?? 0, 1);
            $o[] = ['pid' => $pid, 'ad' => $per[$pid]->ad, 'dep' => $per[$pid]->dep, 'ciro' => $ciro, 'adisyon' => $ad, 'misafir' => (int) ($x['misafir'] ?? 0), 'ort' => $ad ? round($ciro / $ad, 2) : 0,
                'kisi_basi' => !empty($x['misafir']) ? round($ciro / $x['misafir'], 2) : null, 'bahsis' => round($x['bahsis'] ?? 0, 2), 'adim' => (int) ($x['adim'] ?? 0), 'saat' => $s, 'saat_basi' => $s > 0 ? round($ciro / $s, 2) : null,
                'iptal_tl' => round(($x['iptal']['void']['tl'] ?? 0) + ($x['iptal']['odeme_iptal']['tl'] ?? 0), 2), 'ikram_tl' => round($x['iptal']['ikram']['tl'] ?? 0, 2), 'indirim_tl' => round($x['iptal']['indirim']['tl'] ?? 0, 2)];
        }
        usort($o, fn ($a, $b) => $b['ciro'] <=> $a['ciro']);
        return $o;
    }
    // İptal / ikram / indirim: personel → tip → [n, tl]
    function _ikrIptal($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit|" . _ikrKapsamDep(); if (isset($c[$key])) return $c[$key];
        if (!_srTablo('iptal_indirim_loglari')) return $c[$key] = [];
        $per = _ikrPersonel($sid); $o = [];
        try { $rows = DB::table('iptal_indirim_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->whereIn('tip', ['void', 'ikram', 'indirim', 'odeme_iptal'])->groupBy('personel_id', 'tip')->selectRaw('personel_id pid, tip, COUNT(*) n, SUM(tutar) tl')->get(); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) if (isset($per[(int) $r->pid])) $o[(int) $r->pid][$r->tip] = ['n' => (int) $r->n, 'tl' => (float) $r->tl];
        return $c[$key] = $o;
    }
    function _ikrBelgeler($sid, $tablo, $tarihKol, $adKol)
    {
        if (!_srTablo($tablo)) return [];
        $per = _ikrPersonel($sid); $o = [];
        try { $rows = DB::table($tablo)->where('sube_id', $sid)->get(); } catch (\Throwable $e) { $rows = []; }
        foreach ($rows as $r) if (isset($per[(int) $r->personel_id])) $o[] = ['pid' => (int) $r->personel_id, 'ad' => $per[(int) $r->personel_id]->ad, 'dep' => $per[(int) $r->personel_id]->dep, 'aktif' => $per[(int) $r->personel_id]->aktif,
            'baslik' => (string) ($r->{$adKol} ?? ''), 'tip' => (string) ($r->tip ?? ''), 'tarih' => substr((string) ($r->{$tarihKol} ?? ''), 0, 10), 'son' => substr((string) ($r->son_gecerlilik ?? ''), 0, 10)];
        return $o;
    }
    function _ikrAdaylar($sid)
    {
        if (!_srTablo('ik_adaylar')) return [];
        try { return DB::table('ik_adaylar')->where('sube_id', $sid)->get()->map(fn ($r) => (array) $r)->all(); } catch (\Throwable $e) { return []; }
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_ikrVeriDurumu')) {
    function _ikrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString(); $per = _ikrPersonel($sid);
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t)->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d = ['personel' => count(array_filter($per, fn ($p) => $p->aktif)), 'maas' => count(array_filter($per, fn ($p) => $p->aktif && $p->maas > 0)),
            'giris' => count(array_filter($per, fn ($p) => $p->giris)), 'cikis' => count(array_filter($per, fn ($p) => $p->cikis)),
            'mesai' => $say('personel_mesai', 'giris_at'), 'vardiya' => $say('personel_vardiya', 'tarih'), 'izin' => $say('personel_izinler', 'baslangic'),
            'hareket' => $say('personel_hareketleri', 'tarih'), 'avans' => $say('personel_hareketleri', 'tarih', fn ($q) => $q->where('tur', 'avans')),
            'satis' => $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')), 'adim' => $say('personel_adim', 'tarih'),
            'iptal' => $say('iptal_indirim_loglari', 'created_at'), 'belge' => $say('personel_belge', null), 'egitim' => $say('personel_egitim', null), 'zimmet' => $say('personel_zimmet', null),
            'aday' => $say('ik_adaylar', 'created_at'), 'yemek' => $say('personel_yemek', 'tarih'),
            'recete' => _srTablo('receteler') ? (int) DB::table('receteler')->whereNotNull('urun_id')->count() : 0,
            'kds' => 0];
        if (_srKolon('adisyon_kalemleri', 'hazirlayan_id')) { try { $d['kds'] = (int) DB::table('adisyon_kalemleri as ak')->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->whereNotNull('ak.hazirlayan_id')->where('ak.created_at', '>=', $s)->count(); } catch (\Throwable $e) {} }
        return $d;
    }
    function _ikrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'personel' => 'Personel kaydı yok. Raporlar personel kartlarından hesaplanır. İnsan Kaynakları > Personel & Maaş\'tan personel ekleyin.',
            'maas_yok' => 'Personel kartlarında maaş girilmemiş. İşçilik maliyeti maaş × işveren çarpanı ile hesaplanır; maaş yoksa 0 görünür. Personel & Maaş\'ta her kişiye maaş ve maaş tipini (aylık / günlük / saatlik) girin.',
            'mesai_yok' => 'QR mesai kaydı yok: son 1 yılda kimse mesaiye QR ile giriş-çıkış yapmamış. Çalışılan saat, geç kalma, fazla mesai ve saat başı ciro bu kayıtlardan hesaplanır. Dolması için personel her gün mesaiyi telefonundan QR okutarak başlatıp bitirmeli.',
            'mesai' => 'Seçili dönemde (' . $donem . ') QR mesai kaydı yok.',
            'vardiya_yok' => 'Vardiya planı yok. Geç kalma, gelmeyen ve plan–gerçek karşılaştırması planlanan vardiya ile QR mesai eşleştirilerek bulunur. Dolması için İnsan Kaynakları > Vardiya Planlama\'dan haftalık vardiya girin (AI ile de planlatabilirsiniz).',
            'vardiya' => 'Seçili dönemde (' . $donem . ') planlanmış vardiya yok.',
            'devam' => 'Seçili dönemde (' . $donem . ') vardiya ya da mesai kaydı yok.',
            'gec' => 'Seçili dönemde (' . $donem . ') tolerans süresini aşan geç kalma ya da erken çıkış yok.',
            'gelmedi' => 'Seçili dönemde (' . $donem . ') vardiyası olup gelmeyen ya da plansız mesai yapan yok.',
            'fazla' => 'Seçili dönemde haftalık 45 saati aşan çalışma yok.',
            'satis' => 'Seçili dönemde (' . $donem . ') ödenmiş adisyon (ciro) yok.',
            'izin_yok' => 'İzin kaydı yok. İzinler İnsan Kaynakları > İzin Yönetimi\'nden ya da personelin uygulamadan gönderdiği izin talebinden gelir.',
            'izin' => 'Seçili dönemde (' . $donem . ') izin kaydı yok.',
            'giris_yok' => 'Personel kartlarında işe giriş tarihi yok. Kıdem, yıllık izin hakkı ve devir bu tarihten hesaplanır; girilmemişse personelin sisteme eklendiği tarih kullanılır. Personel & Maaş\'ta "İşe başlama" tarihini girin.',
            'devir' => 'Seçili dönemde (' . $donem . ') işe giren ya da ayrılan personel yok.',
            'erken' => 'Son 1 yılda işe girip ilk 90 günde ayrılan personel yok.',
            'garson' => 'Seçili dönemde (' . $donem . ') personelin açtığı ödenmiş adisyon yok.',
            'kds_yok' => 'Mutfak ekibi performansı, KDS\'de "Başla / Hazır"a basan aşçı kaydından hesaplanır; henüz bu kayıt yok. Mutfak personeli KDS\'ye kendi PIN\'iyle girmeli.',
            'iptal' => 'Seçili dönemde (' . $donem . ') iptal, ikram ya da indirim kaydı yok.',
            'hareket' => 'Seçili dönemde (' . $donem . ') avans, prim, kesinti ya da ödeme kaydı yok.',
            'avans' => 'Seçili dönemde (' . $donem . ') avans verilmemiş.',
            'yemek' => 'Personel yemeği kaydı yok. CRM > Personel Yemeği ekranından günlük personel yemeği girilir.',
            'belge' => 'Süresi geçmiş ya da 30 gün içinde dolacak belge / eğitim yok (ya da belgelere geçerlilik tarihi girilmemiş). İK Sicil > Belgeler / Eğitimler.',
            'zimmet' => 'Teslim edilmiş ve iade alınmamış zimmet yok. İK Sicil > Zimmet.',
            'ozluk' => 'Tüm aktif personelin özlük bilgileri tam.',
            'aday_yok' => 'Aday kaydı yok. İnsan Kaynakları > İşe Alım / Aday Havuzu\'ndan ya da web başvuru formundan adaylar gelir.',
            'aday' => 'Seçili dönemde (' . $donem . ') gelen aday yok.',
            'alim' => 'Seçili dönemde (' . $donem . ') aday havuzundan işe alınan yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_ikrKatalog')) {
    function _ikrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük İK Bülteni', 'Dün kim geldi, kim gelmedi, kim geç kaldı; çalışılan saat, işçilik maliyeti ve cirodaki payı, izinliler, bekleyen talepler, süresi dolan belgeler.', 'today', '#2563EB', ['mesai', 'vardiya'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık İK Raporu', 'Günlere göre ciro, çalışılan saat ve işçilik oranı; devam özeti, fazla mesai, izinler ve önceki haftayla kıyas.', 'date_range', '#1D4ED8', ['mesai', 'maas'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık İK Yönetim Raporu', 'Kadro, giren-çıkan ve devir oranı, işçilik maliyeti ve oranı, saat başı ciro, devam, izin, bordro ve uyum — patron için tek dosyada ay özeti.', 'calendar_month', '#1E40AF', ['maas', 'mesai'], 'gecen_ay'],
            'iscilik' => ['maliyet', 'İşçilik Maliyeti & Oranı', 'Gün gün işçilik maliyeti, ciro ve işçilik oranı (maliyet ÷ ciro); restoranlarda hedef genelde %25–35.', 'percent', '#059669', ['maas', 'satis'], null],
            'verim' => ['maliyet', 'Saat Başı Ciro (Verimlilik)', 'Bir çalışma saatine düşen ciro; günlere ve departmanlara göre, önceki dönemle kıyas.', 'speed', '#047857', ['mesai', 'satis'], null],
            'kisi_maliyet' => ['maliyet', 'Kişi & Departman Maliyeti', 'Her personelin dönem maliyeti (maaş + prim), çalıştığı saat ve saatlik gerçek maliyeti; departman toplamları.', 'payments', '#0F766E', ['maas'], null],
            'prime' => ['maliyet', 'Ana Maliyet (Yemek + İşçilik)', 'Yemek maliyeti ile işçilik maliyetinin cirodaki toplam payı (prime cost); restoranlarda genelde %60–65.', 'pie_chart', '#0D9488', ['maas', 'recete', 'satis'], null],
            'plan_fiili' => ['devam', 'Plan ve Gerçek', 'Vardiyada planlanan saat ile QR mesaiyle çalışılan saatin kişi ve gün bazında karşılaştırması; devam oranı.', 'compare_arrows', '#DC2626', ['vardiya', 'mesai'], null],
            'gecikme' => ['devam', 'Geç Kalma & Erken Çıkış', 'Tolerans süresini aşan geç girişler ve erken çıkışlar; kişi bazında sayı ve toplam dakika.', 'alarm', '#B91C1C', ['vardiya', 'mesai'], null],
            'gelmeyen' => ['devam', 'Gelmeyen & Plansız Mesai', 'Vardiyası olduğu halde gelmeyenler (izinliler hariç) ve vardiyası olmadan mesai açanlar.', 'person_off', '#9F1239', ['vardiya', 'mesai'], null],
            'fazla_mesai' => ['devam', 'Fazla Mesai (45 saat)', 'Haftalık 45 saati aşan çalışmalar ve yıl içindeki toplam fazla mesai (yasal sınır 270 saat).', 'more_time', '#C2410C', ['mesai'], null],
            'isi' => ['vardiya', 'Saatlik Kadro & Ciro Isı Haritası', 'Gün × saat: ortalama çalışan sayısı, ortalama ciro ve çalışan başına ciro; fazla ve eksik eleman olan saatler.', 'grid_on', '#7C3AED', ['mesai', 'satis'], null],
            'vardiya_dagilim' => ['vardiya', 'Vardiya Dağılımı', 'Kişi, pozisyon ve güne göre planlanan vardiya sayısı ve saati; haftalık yük dengesi.', 'calendar_view_week', '#6D28D9', ['vardiya'], null],
            'izin_bakiye' => ['izin', 'Yıllık İzin Hakları', 'Kıdeme göre yıllık izin hakkı (14 / 20 / 26 gün), bu hizmet yılında kullanılan ve kalan gün; yeni hak doğacak tarih.', 'beach_access', '#0891B2', ['giris'], null],
            'izin_kullanim' => ['izin', 'İzin Kullanımı', 'Dönemde kullanılan izinler: türe, kişiye, departmana ve aya göre; rapor (hastalık) günleri.', 'event_busy', '#0E7490', ['izin'], null],
            'izin_talep' => ['izin', 'İzin Talepleri', 'Gelen izin talepleri: onaylanan, reddedilen, bekleyen ve karar süresi.', 'pending_actions', '#155E75', ['izin'], null],
            'devir' => ['devir', 'Giren-Çıkan & Devir Oranı', 'Dönem başı ve sonu kadro, işe girenler, ayrılanlar, ayrılış sebepleri ve devir oranı (yıllık karşılığıyla).', 'sync_alt', '#4F46E5', ['giris', 'cikis'], null],
            'kidem' => ['devir', 'Kıdem Dağılımı', 'Aktif kadronun kıdem grupları, departmana göre ortalama kıdem ve kadro listesi.', 'military_tech', '#4338CA', ['giris'], null],
            'erken_kayip' => ['devir', 'İlk 90 Günde Ayrılanlar', 'Son 1 yılda işe girip ilk 90 günde ayrılanlar: kaç günde ayrıldı, departman ve sebep.', 'running_with_errors', '#3730A3', ['cikis'], null],
            'garson' => ['performans', 'Satış Performansı', 'Personelin açtığı ödenmiş adisyonlar: ciro, adisyon, ortalama hesap, kişi başı, bahşiş, adım, saat başı satış.', 'emoji_events', '#D97706', ['satis'], null],
            'mutfak_ekip' => ['performans', 'Mutfak Ekibi Performansı', 'KDS\'de "Başla / Hazır"a basan aşçıya göre hazırlanan kalem, ortalama süre ve hedef süreye uyum.', 'soup_kitchen', '#B45309', ['kds'], null],
            'iptal_personel' => ['performans', 'Personel İptal & İkram', 'Personel bazında iptal, ödeme iptali, ikram ve indirim sayısı ve tutarı; satışa oranı.', 'cancel', '#BE123C', ['iptal'], null],
            'bordro' => ['bordro', 'Bordro Özeti', 'Kişi bazında maaş, hesaplanan prim, ek ödeme, kesinti, avans, ödenen ve net ödenecek tutar (işletmenin girdiği tutarlarla; resmi bordro değildir).', 'receipt_long', '#0284C7', ['maas'], 'bu_ay'],
            'avans' => ['bordro', 'Avans Analizi', 'Dönemde verilen avanslar: kişi, sayı, tutar ve maaşa oranı; sık avans alanlar.', 'request_quote', '#0369A1', ['avans'], null],
            'yemek' => ['bordro', 'Personel Yemeği', 'Kişi ve gün bazında personel yemeği sayısı ve maliyeti.', 'lunch_dining', '#075985', ['yemek'], null],
            'belge' => ['uyum', 'Belge & Eğitim Süreleri', 'Süresi geçmiş ya da 30 gün içinde dolacak belgeler (sağlık, hijyen vb.) ve eğitimler; hijyen belgesi olmayan aktif personel.', 'verified', '#16A34A', ['belge'], null],
            'zimmet' => ['uyum', 'Açık Zimmetler', 'Teslim edilmiş, iade alınmamış zimmetler; ayrılan personelde kalan zimmetler.', 'inventory', '#15803D', ['zimmet'], null],
            'ozluk' => ['uyum', 'Eksik Özlük Bilgisi', 'Aktif personelde eksik TC, işe giriş tarihi, doğum tarihi, acil durum telefonu, IBAN ve maaş bilgisi.', 'badge', '#166534', ['personel'], null],
            'aday' => ['alim', 'Aday Hunisi & Kaynak', 'Dönemde gelen adaylar: aşamalara göre (yeni → mülakat → teklif → işe alındı), kaynak ve pozisyon dağılımı.', 'filter_alt', '#DB2777', ['aday'], null],
            'ise_alim' => ['alim', 'İşe Alınanlar & Süre', 'Aday havuzundan işe alınanlar, başvurudan işe alıma geçen gün ve işe alındıktan sonra hâlâ çalışıyor mu.', 'how_to_reg', '#BE185D', ['aday'], null],
        ];
    }
    function _ikrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron ve İK için hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#2563EB'],
            'maliyet' => ['İşçilik Maliyeti', 'percent', 'İşçilik maliyeti ve cirodaki payı, saat başı ciro, kişi maliyeti, ana maliyet (yemek + işçilik).', '#059669'],
            'devam' => ['Mesai & Devam', 'alarm', 'Plan ve gerçek, geç kalma, gelmeyenler, plansız mesai ve fazla mesai.', '#DC2626'],
            'vardiya' => ['Vardiya Verimi', 'grid_on', 'Saat saat kadro ve ciro: hangi saatte fazla, hangi saatte eksik eleman var.', '#7C3AED'],
            'izin' => ['İzin', 'beach_access', 'Yıllık izin hakları, izin kullanımı ve talepler.', '#0891B2'],
            'devir' => ['Devir & Kıdem', 'sync_alt', 'Giren-çıkan, devir oranı, kıdem ve ilk 90 günde ayrılanlar.', '#4F46E5'],
            'performans' => ['Performans', 'emoji_events', 'Satış performansı, mutfak ekibi ve personel bazında iptal-ikram.', '#D97706'],
            'bordro' => ['Bordro & Avans', 'receipt_long', 'Hakediş ve net ödenecek, avans analizi, personel yemeği.', '#0284C7'],
            'uyum' => ['Belge & Uyum', 'verified', 'Süresi dolan belge ve eğitimler, açık zimmetler, eksik özlük bilgisi.', '#16A34A'],
            'alim' => ['İşe Alım', 'how_to_reg', 'Aday hunisi, kaynaklar ve işe alım süresi.', '#DB2777'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı İK özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_ikrSayfaDevamKisi')) {
    function _ikrSayfaDevamKisi(array $dv, $bos)
    {
        $g = []; foreach ($dv as $x) $g[$x['pid']][] = $x;
        $sat = [];
        foreach ($g as $l) { $s = _ikrDevamSay($l); $sat[] = [$l[0]['ad'], $l[0]['dep'], $s['plan'], $s['plan'] - $s['gelmedi'] - $s['izinli'], $s['gelmedi'], $s['izinli'], $s['gec'], $s['gec_dk'], $s['erken'], $s['plansiz'], round(array_sum(array_column($l, 'plan_saat')), 1), round(array_sum(array_column($l, 'fiili')), 1), $s['devam']]; }
        usort($sat, fn ($a, $b) => [$b[4], $b[6]] <=> [$a[4], $a[6]]);
        return _srSayfa('Devam Özeti (Kişi)', 'Vardiya planı ile QR mesainin eşleşmesi. Devam oranı = gelinen vardiya ÷ (planlı − izinli).', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Planlı Vardiya', 's', 9], ['Geldi', 's', 7], ['Gelmedi', 's', 8, 'top esik:1'], ['İzinli', 's', 7], ['Geç', 's', 6, 'top esik:3'], ['Geç (dk)', 's', 8, 'top'], ['Erken Çıkış', 's', 8], ['Plansız', 's', 8], ['Plan Saat', 's', 9, 'top'], ['Fiili Saat', 's', 9, 'top'], ['Devam %', 'y', 9]], $sat, $bos);
    }
    function _ikrSayfaDevamListe($ad, $not, array $dv, $bos)
    {
        return _srSayfa($ad, $not, [['Tarih', 't', 10], ['Personel', 'm', 18], ['Departman', 'm', 13], ['Plan', 'm', 12], ['Giriş', 'm', 7], ['Çıkış', 'm', 7], ['Geç (dk)', 's', 8, 'esik:15'], ['Erken (dk)', 's', 9, 'esik:15'], ['Fiili Saat', 's', 9, 'top'], ['Durum', 'm', 18]],
            array_map(fn ($x) => [$x['tarih'], $x['ad'], $x['dep'], $x['plan_bas'] ? $x['plan_bas'] . '–' . $x['plan_bit'] : '—', $x['giris'] ?: '—', $x['cikis'] ?: '—', $x['gec'], $x['erken'], $x['fiili'], _ikrDurumAd($x['durum'])], $dv), $bos);
    }
    // Gün gün: ciro, çalışan, saat, işçilik maliyeti, oran, saat başı ciro, gelmeyen, geç
    function _ikrGunTablo($sid, $bas, $bit)
    {
        $ml = _ikrMaliyet($sid, $bas, $bit); $ciro = []; foreach (_ikrAdisyon($sid, $bas, $bit) as $a) $ciro[$a['tarih']] = ($ciro[$a['tarih']] ?? 0) + $a['toplam'];
        $saat = []; $kisi = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) { $saat[$m['tarih']] = ($saat[$m['tarih']] ?? 0) + ($m['saat'] ?? 0); $kisi[$m['tarih']][$m['pid']] = 1; }
        $dv = []; foreach (_ikrDevam($sid, $bas, $bit) as $x) { if ($x['durum'] === 'gelmedi') $dv[$x['tarih']]['g'] = ($dv[$x['tarih']]['g'] ?? 0) + 1; if (_ikrGecMi($x['durum'])) $dv[$x['tarih']]['c'] = ($dv[$x['tarih']]['c'] ?? 0) + 1; }
        $o = [];
        foreach (_ikrGunler($bas, $bit) as $t) {
            $c = round($ciro[$t] ?? 0, 2); $m = $ml['gun'][$t] ?? 0; $s = round($saat[$t] ?? 0, 1);
            $o[] = [$t, _ikrGunAd(date('N', strtotime($t))), $c, count($kisi[$t] ?? []), $s, $m, $c > 0 ? round($m / $c * 100, 1) : null, $s > 0 ? round($c / $s, 2) : null, $dv[$t]['g'] ?? 0, $dv[$t]['c'] ?? 0];
        }
        return $o;
    }
    function _ikrSayfaGun(array $sat, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa('Günlük İşçilik', 'İşçilik oranı = o günün işçilik maliyeti ÷ ciro. Saat başı ciro = ciro ÷ QR mesaiyle çalışılan toplam saat.', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Ciro', 'tl', 13, 'top bar'], ['Çalışan', 's', 8], ['Çalışılan Saat', 's', 11, 'top'], ['İşçilik Maliyeti', 'tl', 14, 'top'], ['İşçilik %', 'y', 10, 'esik:35'], ['Saat Başı Ciro', 'tl', 12], ['Gelmeyen', 's', 8, 'top esik:1'], ['Geç', 's', 6, 'top']], $sat, $bos);
    }
    function _ikrSayfaKisiMaliyet(array $ml, $bos)
    {
        return _srSayfa('Kişi Maliyeti', 'Aylık maaş /30 × kadroda olunan gün; günlük maaş × mesaiye gelinen gün; saatlik × fiili saat. Prim = elle girilen prim + ek ödeme + ciro primi. İşveren çarpanı: ' . number_format($ml['carpan'], 2, ',', '.') . '.',
            [['Personel', 'm', 20], ['Departman', 'm', 14], ['Hesap', 'm', 18], ['Maaş', 'tl', 11], ['Temel', 'tl', 12, 'top'], ['Prim / Ek', 'tl', 11, 'top'], ['Toplam Maliyet', 'tl', 14, 'top bar'], ['Fiili Saat', 's', 9, 'top'], ['Saatlik Maliyet', 'tl', 12]],
            array_map(fn ($x) => [$x['ad'] . ($x['maassiz'] ? ' (maaş yok)' : ''), $x['dep'], $x['baz'], $x['maas'], $x['temel'], $x['prim'], $x['toplam'], $x['saat'], $x['saatlik']], array_values($ml['kisi'])), $bos);
    }
    function _ikrDepMaliyet(array $ml)
    {
        $d = []; foreach ($ml['kisi'] as $x) { $d[$x['dep']]['n'] = ($d[$x['dep']]['n'] ?? 0) + 1; $d[$x['dep']]['tl'] = ($d[$x['dep']]['tl'] ?? 0) + $x['toplam']; $d[$x['dep']]['s'] = ($d[$x['dep']]['s'] ?? 0) + $x['saat']; }
        uasort($d, fn ($a, $b) => $b['tl'] <=> $a['tl']); $t = max(0.01, array_sum(array_column($d, 'tl')));
        return array_map(fn ($a, $v) => [$a, $v['n'], round($v['tl'], 2), round($v['tl'] / $t * 100, 1), round($v['s'], 1), $v['s'] > 0 ? round($v['tl'] / $v['s'], 2) : null], array_keys($d), array_values($d));
    }
    function _ikrSayfaDevir(array $dv, $bos)
    {
        $seb = _ikrAyrilisSebepleri(); $sat = [];
        foreach ($dv['giren'] as $p) $sat[] = ['Giriş', $p->ad, $p->dep, _ikrBaslangic($p), $p->giris ? '' : 'kayıt tarihi', '', $p->aktif ? 'Çalışıyor' : 'Ayrıldı'];
        foreach ($dv['ayrilan'] as $p) { $g = _ikrBaslangic($p); $sat[] = ['Çıkış', $p->ad, $p->dep, $p->cikis, $p->cikis_tahmini ? 'tahmini (pasife alındığı gün)' : '', $p->sebep ? ($seb[$p->sebep] ?? $p->sebep) : 'Girilmemiş', $g ? _ikrKidemMetin(_ikrKidemAy($p, $p->cikis)) . ' çalıştı' : '']; }
        return _srSayfa('Giren ve Ayrılanlar', 'Ayrılış tarihi personel Pasif yapıldığında yazılır; eski kayıtlarda tarih yoksa pasife alındığı gün "tahmini" sayılır.', [['Hareket', 'm', 8], ['Personel', 'm', 20], ['Departman', 'm', 14], ['Tarih', 't', 11], ['Tarih Notu', 'm', 22], ['Ayrılış Sebebi', 'm', 22], ['Durum / Süre', 'm', 18]], $sat, $bos);
    }
    function _ikrSayfaIzin(array $iz, $bos)
    {
        return _srSayfa('İzinler', 'Dönemle kesişen onaylı izinler; "Dönemdeki gün" yalnız seçili döneme düşen takvim günü.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Tür', 'm', 15], ['Başlangıç', 't', 11], ['Bitiş', 't', 11], ['Gün', 's', 7, 'top'], ['Dönemdeki Gün', 's', 10, 'top'], ['Açıklama', 'm', 28]],
            array_map(fn ($z) => [$z['ad'], $z['dep'], _ikrIzinTipAd($z['tip']), $z['bas'], $z['bit'], $z['gun'], $z['donem_gun'], $z['aciklama']], $iz), $bos);
    }
    function _ikrBordroSat($sid, $bas, $bit)
    {
        $o = []; if (!function_exists('_restoPersonelHesap')) return $o;
        foreach (_ikrPersonel($sid) as $p) {
            if (!$p->aktif && !($p->cikis && $p->cikis >= $bas)) continue;
            try { $h = _restoPersonelHesap($sid, $p->ham, $bas . ' 00:00:00', $bit . ' 23:59:59'); } catch (\Throwable $e) { continue; }
            $o[] = [$p->ad . ($p->aktif ? '' : ' (ayrıldı)'), $p->dep, ['aylik' => 'Aylık', 'gunluk' => 'Günlük', 'saatlik' => 'Saatlik'][$h['maas_tipi']] ?? $h['maas_tipi'], $h['maas'], round($h['prim_hesap'] + $h['prim_manuel'], 2), $h['ek_odeme'], $h['kesinti'], $h['hakedis'], $h['avans'], $h['odenen'], $h['net']];
        }
        usort($o, fn ($a, $b) => $b[10] <=> $a[10]);
        return $o;
    }
    function _ikrSayfaBordro(array $sat, $bos)
    {
        return _srSayfa('Bordro Özeti', 'Hakediş = maaş + prim + ek ödeme − kesinti; Net ödenecek = hakediş − avans − ödenen. Aylık maaş seçili dönemde TAM yazılır (dönem bir ay olmalı). Resmi bordro değildir.',
            [['Personel', 'm', 20], ['Departman', 'm', 13], ['Maaş Tipi', 'm', 9], ['Maaş', 'tl', 12, 'top'], ['Prim', 'tl', 11, 'top'], ['Ek Ödeme', 'tl', 11, 'top'], ['Kesinti', 'tl', 11, 'top'], ['Hakediş', 'tl', 12, 'top'], ['Avans', 'tl', 11, 'top'], ['Ödenen', 'tl', 11, 'top'], ['Net Ödenecek', 'tl', 13, 'top bar']], $sat, $bos);
    }
    function _ikrBelgeUyari($sid, $gun = 30)
    {
        $sinir = date('Y-m-d', strtotime("+$gun day")); $bugun = date('Y-m-d'); $o = [];
        foreach ([['personel_belge', 'belge_tarihi', 'baslik', 'Belge'], ['personel_egitim', 'egitim_tarihi', 'konu', 'Eğitim']] as [$t, $tk, $ak, $tur])
            foreach (_ikrBelgeler($sid, $t, $tk, $ak) as $b) if ($b['aktif'] && $b['son'] && $b['son'] <= $sinir) $o[] = [$b['ad'], $b['dep'], $tur, $b['baslik'] ?: $b['tip'], $b['son'], (int) round((strtotime($b['son']) - strtotime($bugun)) / 86400), $b['son'] < $bugun ? 'Süresi geçti' : 'Yaklaşıyor'];
        usort($o, fn ($a, $b) => $a[5] <=> $b[5]);
        return $o;
    }
    function _ikrSayfaBelge(array $sat, $bos, $gun = 30)
    {
        return _srSayfa('Süresi Dolan Belge & Eğitim', 'Aktif personelde geçerlilik tarihi geçmiş ya da ' . $gun . ' gün içinde dolacak kayıtlar (negatif gün = süresi geçmiş).', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Tür', 'm', 9], ['Belge / Eğitim', 'm', 26], ['Son Geçerlilik', 't', 12], ['Kalan Gün', 's', 9], ['Durum', 'm', 13]], $sat, $bos);
    }
}

// ---------- Raporlar ----------
if (!function_exists('_ikrRapor')) {
    function _ikrRapor($anahtar, $sid, array $dn)
    {
        _ikrEnsure();
        $d = _ikrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0 && array_sum(array_map(fn ($r) => abs((float) ($r[1] ?? 0)), $b['sat'])) > 0)), 'bos' => $bos]; // hepsi 0 olan grafik çizilmez
        $yuzde = fn ($a, $b) => _srYuzde($a, $b);
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $per = _ikrPersonel($sid); $aktif = array_filter($per, fn ($p) => $p->aktif);
        $bosMesai = !$d['mesai'] ? _ikrBos('mesai_yok', $dn) : _ikrBos('mesai', $dn);
        $bosVardiya = !$d['vardiya'] ? _ikrBos('vardiya_yok', $dn) : (!$d['mesai'] ? _ikrBos('mesai_yok', $dn) : _ikrBos('devam', $dn));
        $bosMaas = !$d['maas'] ? _ikrBos('maas_yok', $dn) : null;
        $oran = fn ($m, $c) => $c > 0 ? round($m / $c * 100, 1) : null;
        $splh = fn ($c, $s) => $s > 0 ? round($c / $s, 2) : null;
        $blokDep = fn ($ml, $renk) => _srBlok('Departmanlara göre işçilik maliyeti', 'pie', $renk, [['Departman', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], _ikrDepMaliyet($ml)));
        $blokGec = fn ($dv, $renk) => _srBlok('En çok geç kalanlar (kez)', 'bar', $renk, [['Personel', 'm'], ['Geç', 's'], ['Toplam dk', 's']], collect($dv)->filter(fn ($x) => _ikrGecMi($x['durum']))->groupBy('ad')->map(fn ($g, $a) => [$a, $g->count(), $g->sum('gec')])->sortByDesc(1)->take(10)->values()->all());

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $dv = _ikrDevam($sid, $bas, $bit); $s = _ikrDevamSay($dv); $ml = _ikrMaliyet($sid, $bas, $bit); $c = _ikrCiro($sid, $bas, $bit); $sa = _ikrSaat($sid, $bas, $bit);
                $gelen = count(array_unique(array_column(_ikrMesai($sid, $bas, $bit), 'pid'))); $izinli = _ikrIzinler($sid, $bas, $bit);
                $bekleyen = _ikrIzinler($sid, '2000-01-01', '2100-01-01', 'talep'); $belge = _ikrBelgeUyari($sid, 7);
                $saatlik = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) if ($m['son']) for ($h = (int) date('G', $m['giris']); $h <= (int) date('G', $m['son']) && $h < 24; $h++) $saatlik[$h][$m['pid']] = 1;
                ksort($saatlik);
                return [
                    $pano([_srKpi('Aktif kadro', count($aktif), 'sayi', '2563EB'), _srKpi('Mesaiye gelen', $gelen, 'sayi', '1D4ED8'), _srKpi('Çalışılan saat', $sa, 'sayi', '1E40AF'),
                        _srKpi('İşçilik maliyeti', $ml['toplam'], 'tl', '059669'), _srKpi('Ciro', $c, 'tl', '047857'), $kpiY('İşçilik oranı', $oran($ml['toplam'], $c), '0F766E'), $kpiTl('Saat başı ciro', $splh($c, $sa), '0D9488'),
                        _srKpi('Gelmeyen', $s['gelmedi'], 'sayi', 'DC2626'), _srKpi('Geç kalan', $s['gec'], 'sayi', 'B91C1C'), _srKpi('İzinli', count($izinli), 'sayi', '0891B2'), _srKpi('Plansız mesai', $s['plansiz'], 'sayi', 'C2410C'),
                        _srKpi('Bekleyen izin talebi', count($bekleyen), 'sayi', 'D97706')],
                        [_srBlok('Saatlere göre çalışan sayısı', 'col', '2563EB', [['Saat', 'm'], ['Çalışan', 's'], ['', 'm']], array_map(fn ($h, $l) => [sprintf('%02d:00', $h), count($l), ''], array_keys($saatlik), array_values($saatlik))),
                         $blokDep($ml, '059669')], !$dv && !$gelen ? $bosVardiya : null),
                    _ikrSayfaDevamListe('Devam Listesi', 'Dünün vardiyaları ve QR mesaileri; geç / erken tolerans ' . _ikrAyar($sid)['tolerans'] . ' dk.', $dv, $bosVardiya),
                    _ikrSayfaIzin($izinli, 'Bu gün izinli personel yok.'),
                    _srSayfa('Bekleyen İzin Talepleri', 'Onay bekleyen tüm izin talepleri (İnsan Kaynakları > İzin Yönetimi).', [['Personel', 'm', 20], ['Tür', 'm', 15], ['Başlangıç', 't', 11], ['Bitiş', 't', 11], ['Gün', 's', 7], ['Talep Tarihi', 't', 11], ['Açıklama', 'm', 28]],
                        array_map(fn ($z) => [$z['ad'], _ikrIzinTipAd($z['tip']), $z['bas'], $z['bit'], $z['gun'], $z['talep'], $z['aciklama']], $bekleyen), 'Onay bekleyen izin talebi yok.'),
                    _ikrSayfaBelge($belge, 'Süresi 7 gün içinde dolan ya da geçmiş belge / eğitim yok.', 7),
                ];
            case 'haftalik':
            case 'aylik':
                $o = ['bas' => $dn['obas'], 'bit' => $dn['obit']];
                $dv = _ikrDevam($sid, $bas, $bit); $s = _ikrDevamSay($dv); $ml = _ikrMaliyet($sid, $bas, $bit); $mlo = _ikrMaliyet($sid, $o['bas'], $o['bit']);
                $c = _ikrCiro($sid, $bas, $bit); $co = _ikrCiro($sid, $o['bas'], $o['bit']); $sa = _ikrSaat($sid, $bas, $bit); $sao = _ikrSaat($sid, $o['bas'], $o['bit']);
                $fz = array_filter(_ikrHaftalik($sid, $bas, $bit), fn ($x) => $x['fazla'] > 0); $iz = _ikrIzinler($sid, $bas, $bit); $gt = _ikrGunTablo($sid, $bas, $bit);
                $kpi = [_srKpi('Aktif kadro', count($aktif), 'sayi', '2563EB'), _srKpi('Çalışılan saat', $sa, 'sayi', '1D4ED8'), $kpiDeg('Saat, önceki döneme göre', $yuzde($sa, $sao), '1E40AF'),
                    _srKpi('İşçilik maliyeti', $ml['toplam'], 'tl', '059669'), $kpiY('İşçilik oranı', $oran($ml['toplam'], $c), '0F766E'), $kpiY('Önceki dönem oranı', $oran($mlo['toplam'], $co), '115E59'),
                    $kpiTl('Saat başı ciro', $splh($c, $sa), '0D9488'), $kpiTl('Önceki dönem saat başı', $splh($co, $sao), '134E4A'),
                    $kpiY('Devam oranı', $s['devam'], '7C3AED'), _srKpi('Gelmeyen', $s['gelmedi'], 'sayi', 'DC2626'), _srKpi('Geç kalma', $s['gec'], 'sayi', 'B91C1C'),
                    _srKpi('Fazla mesai (saat)', round(array_sum(array_column($fz, 'fazla')), 1), 'sayi', 'C2410C'), _srKpi('İzin günü', array_sum(array_column($iz, 'donem_gun')), 'sayi', '0891B2')];
                $bloklar = [_srBlok('Günlere göre ciro ve işçilik maliyeti', 'col', '2563EB', [['Gün', 'm'], ['Ciro', 'tl'], ['İşçilik', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[5]], $gt)), $blokDep($ml, '059669'), $blokGec($dv, 'B91C1C')];
                $sayfalar = [];
                if ($anahtar === 'aylik') {
                    $dvr = _ikrDevir($sid, $bas, $bit); $bord = _ikrBordroSat($sid, $bas, $bit); $avans = array_sum(array_map(fn ($h) => $h['tur'] === 'avans' ? $h['tutar'] : 0, _ikrHareket($sid, $bas, $bit)));
                    $kpi = array_merge($kpi, [_srKpi('Giren / ayrılan', count($dvr['giren']) . ' / ' . count($dvr['ayrilan']), 'metin', '4F46E5'), $kpiY('Devir oranı (yıllık karşılığı)', $dvr['yillik'], '4338CA'),
                        _srKpi('Avans', $avans, 'tl', '0284C7'), _srKpi('Net ödenecek (bordro)', array_sum(array_column($bord, 10)), 'tl', '0369A1'), _srKpi('Süresi dolan belge', count(_ikrBelgeUyari($sid, 0)), 'sayi', '16A34A'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $kid = []; foreach ($aktif as $p) { $k = _ikrKidemGrup(_ikrKidemAy($p, $bit)); $kid[$k] = ($kid[$k] ?? 0) + 1; }
                    $bloklar[] = _srBlok('Kıdem dağılımı (aktif)', 'pie', '4F46E5', [['Kıdem', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($kid), array_values($kid)));
                    $sayfalar = [_ikrSayfaKisiMaliyet($ml, $bosMaas ?? _ikrBos('personel', $dn)), _ikrSayfaDevamKisi($dv, $bosVardiya), _ikrSayfaDevir($dvr, _ikrBos('devir', $dn)), _ikrSayfaIzin($iz, !$d['izin'] ? _ikrBos('izin_yok', $dn) : _ikrBos('izin', $dn)),
                        _ikrSayfaBordro($bord, $bosMaas ?? _ikrBos('personel', $dn)), _ikrSayfaBelge(_ikrBelgeUyari($sid), _ikrBos('belge', $dn))];
                } else {
                    $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                    $sayfalar = [_ikrSayfaDevamKisi($dv, $bosVardiya), _ikrSayfaDevamListe('Geç / Gelmeyen / Plansız', 'Yalnız sorunlu kayıtlar.', array_values(array_filter($dv, fn ($x) => !in_array($x['durum'], ['zamaninda', 'izinli'], true))), _ikrBos('gelmedi', $dn)),
                        _srSayfa('Fazla Mesai', 'Haftalık 45 saati aşan çalışma.', [['Hafta (Pzt)', 't', 11], ['Personel', 'm', 20], ['Departman', 'm', 14], ['Çalışılan Saat', 's', 11], ['Fazla Mesai (saat)', 's', 13, 'top bar']], array_map(fn ($x) => [$x['hafta'], $x['ad'], $x['dep'], $x['saat'], $x['fazla']], array_values($fz)), _ikrBos('fazla', $dn)),
                        _ikrSayfaIzin($iz, !$d['izin'] ? _ikrBos('izin_yok', $dn) : _ikrBos('izin', $dn))];
                }
                return array_merge([$pano($kpi, $bloklar, !$per ? _ikrBos('personel', $dn) : null), _ikrSayfaGun($gt, _ikrBos('satis', $dn))], $sayfalar);

            // ---------------- İŞÇİLİK MALİYETİ ----------------
            case 'iscilik':
                $ml = _ikrMaliyet($sid, $bas, $bit); $c = _ikrCiro($sid, $bas, $bit); $mlo = _ikrMaliyet($sid, $dn['obas'], $dn['obit']); $co = _ikrCiro($sid, $dn['obas'], $dn['obit']); $gt = _ikrGunTablo($sid, $bas, $bit);
                $or = $oran($ml['toplam'], $c);
                return [
                    $pano([_srKpi('İşçilik maliyeti', $ml['toplam'], 'tl', '059669'), _srKpi('Ciro', $c, 'tl', '047857'), $kpiY('İşçilik oranı', $or, '0F766E'), $kpiY('Önceki dönem oranı', $oran($mlo['toplam'], $co), '115E59'),
                        _srKpi('Hedef aralık', '%25 – %35', 'metin', '64748B'), _srKpi('Durum', $or === null ? 'Ciro yok' : ($or > 35 ? 'Yüksek' : ($or < 20 ? 'Düşük (eksik kadro?)' : 'Normal')), 'metin', $or !== null && $or > 35 ? 'DC2626' : '059669'),
                        _srKpi('Maaşı girilmemiş personel', count(array_filter($aktif, fn ($p) => $p->maas <= 0)), 'sayi', 'D97706'), _srKpi('İşveren çarpanı', number_format($ml['carpan'], 2, ',', '.'), 'metin', '64748B')],
                        [_srBlok('Günlere göre işçilik oranı (%)', 'col', '059669', [['Gün', 'm'], ['İşçilik %', 'y'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[6], ''], array_filter($gt, fn ($x) => $x[6] !== null))),
                         $blokDep($ml, '0F766E')], $bosMaas),
                    _ikrSayfaGun($gt, _ikrBos('satis', $dn)),
                    _srSayfa('Departman Maliyeti', 'Departman toplamı ve saatlik maliyeti.', [['Departman', 'm', 20], ['Kişi', 's', 7], ['Maliyet', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Fiili Saat', 's', 10, 'top'], ['Saatlik Maliyet', 'tl', 12]], _ikrDepMaliyet($ml), $bosMaas),
                ];
            case 'verim':
                $c = _ikrCiro($sid, $bas, $bit); $sa = _ikrSaat($sid, $bas, $bit); $co = _ikrCiro($sid, $dn['obas'], $dn['obit']); $sao = _ikrSaat($sid, $dn['obas'], $dn['obit']); $gt = _ikrGunTablo($sid, $bas, $bit);
                $gunAd = []; foreach ($gt as $x) { $n = _ikrGunAd(date('N', strtotime($x[0]))); $gunAd[$n]['c'] = ($gunAd[$n]['c'] ?? 0) + $x[2]; $gunAd[$n]['s'] = ($gunAd[$n]['s'] ?? 0) + $x[4]; }
                $ds = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) { $dp = $per[$m['pid']]->dep; $ds[$dp] = ($ds[$dp] ?? 0) + ($m['saat'] ?? 0); } arsort($ds);
                return [
                    $pano([$kpiTl('Saat başı ciro', $splh($c, $sa), '047857'), $kpiTl('Önceki dönem', $splh($co, $sao), '065F46'), $kpiDeg('Değişim', $splh($c, $sa) && $splh($co, $sao) ? $yuzde($splh($c, $sa), $splh($co, $sao)) : null, '0F766E'),
                        _srKpi('Ciro', $c, 'tl', '059669'), _srKpi('Çalışılan saat', $sa, 'sayi', '0D9488'), _srKpi('Misafir başına saat', ($mis = array_sum(array_column(_ikrAdisyon($sid, $bas, $bit), 'misafir'))) > 0 ? round($sa / $mis, 2) : 'Misafir sayısı yok', $mis > 0 ? 'sayi' : 'metin', '115E59')],
                        [_srBlok('Haftanın günlerine göre saat başı ciro', 'col', '047857', [['Gün', 'm'], ['Saat başı ciro', 'tl'], ['', 'm']], array_map(fn ($a, $v) => [$a, $splh($v['c'], $v['s']) ?? 0, ''], array_keys($gunAd), array_values($gunAd))),
                         _srBlok('Departmanlara göre çalışılan saat', 'pie', '0D9488', [['Departman', 'm'], ['Saat', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, round($v, 1), ''], array_keys($ds), array_values($ds)))], !$d['mesai'] ? _ikrBos('mesai_yok', $dn) : null),
                    _ikrSayfaGun($gt, _ikrBos('satis', $dn)),
                    _srSayfa('Departman Saatleri', 'Departmanın QR mesaiyle çalıştığı saat ve toplam saatteki payı.', [['Departman', 'm', 22], ['Saat', 's', 10, 'top bar'], ['Pay %', 'y', 9]], array_map(fn ($a, $v) => [$a, round($v, 1), $sa > 0 ? round($v / $sa * 100, 1) : null], array_keys($ds), array_values($ds)), $bosMesai),
                ];
            case 'kisi_maliyet':
                $ml = _ikrMaliyet($sid, $bas, $bit);
                $pahali = array_values(array_filter($ml['kisi'], fn ($x) => $x['saatlik'] !== null)); usort($pahali, fn ($a, $b) => $b['saatlik'] <=> $a['saatlik']);
                return [
                    $pano([_srKpi('Toplam işçilik maliyeti', $ml['toplam'], 'tl', '0F766E'), _srKpi('Kişi', count($ml['kisi']), 'sayi', '115E59'), _srKpi('Kişi başı ortalama', count($ml['kisi']) ? round($ml['toplam'] / count($ml['kisi']), 2) : 0, 'tl', '134E4A'),
                        _srKpi('Prim + ek ödeme', array_sum(array_column($ml['kisi'], 'prim')), 'tl', '059669'), _srKpi('Maaşı girilmemiş', count(array_filter($ml['kisi'], fn ($x) => $x['maassiz'])), 'sayi', 'D97706')],
                        [_srBlok('En yüksek maliyetli 10 kişi', 'bar', '0F766E', [['Personel', 'm'], ['Maliyet', 'tl'], ['Saat', 's']], array_map(fn ($x) => [$x['ad'], $x['toplam'], $x['saat']], array_slice(array_values($ml['kisi']), 0, 10))),
                         _srBlok('Saatlik gerçek maliyet (en yüksek 10)', 'bar', 'D97706', [['Personel', 'm'], ['Saatlik', 'tl'], ['Saat', 's']], array_map(fn ($x) => [$x['ad'], $x['saatlik'], $x['saat']], array_slice($pahali, 0, 10)))], $bosMaas),
                    _ikrSayfaKisiMaliyet($ml, $bosMaas ?? _ikrBos('personel', $dn)),
                    _srSayfa('Departman Maliyeti', 'Departman toplamı ve saatlik maliyeti.', [['Departman', 'm', 20], ['Kişi', 's', 7], ['Maliyet', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Fiili Saat', 's', 10, 'top'], ['Saatlik Maliyet', 'tl', 12]], _ikrDepMaliyet($ml), $bosMaas),
                ];
            case 'prime':
                $ml = _ikrMaliyet($sid, $bas, $bit); $c = _ikrCiro($sid, $bas, $bit); $fc = null; $yemek = null; $not = 'Mutfak raporları yüklü değil.';
                if (function_exists('_mrKalemler') && function_exists('_mrFoodCost')) {
                    $k = _mrKalemler($sid, $bas, $bit); $f = _mrFoodCost($k); $fc = $f['fc'];
                    $kalemSatis = array_sum(array_map(fn ($x) => $x['ikram'] ? 0 : $x['tutar'], _mrSatis($k)));
                    $yemek = $fc !== null ? round($kalemSatis * $fc / 100, 2) : null;
                    $not = $fc !== null ? 'Yemek maliyeti = reçeteli ürünlerin food cost oranı (%' . number_format($fc, 1, ',', '.') . ') × tüm ürün satışı; reçetesiz ürünlere aynı oran uygulanır (tahmin).' : 'Reçete olmadığı için yemek maliyeti hesaplanamadı.';
                }
                $prime = $yemek !== null && $c > 0 ? round(($yemek + $ml['toplam']) / $c * 100, 1) : null;
                return [
                    $pano([_srKpi('Ciro', $c, 'tl', '0D9488'), $kpiTl('Yemek maliyeti (tahmini)', $yemek, 'C2410C'), _srKpi('İşçilik maliyeti', $ml['toplam'], 'tl', '059669'),
                        $kpiY('Yemek maliyeti oranı', $yemek !== null && $c > 0 ? round($yemek / $c * 100, 1) : null, 'EA580C'), $kpiY('İşçilik oranı', $oran($ml['toplam'], $c), '0F766E'), $kpiY('Ana maliyet oranı', $prime, $prime !== null && $prime > 65 ? 'DC2626' : '0D9488'), _srKpi('Hedef', '%60 – %65', 'metin', '64748B')],
                        [_srBlok('Cironun dağılımı', 'pie', '0D9488', [['Kalem', 'm'], ['Tutar', 'tl'], ['', 'm']], $c > 0 && $yemek !== null ? [['Yemek maliyeti', $yemek, ''], ['İşçilik', $ml['toplam'], ''], ['Kalan (diğer gider + kâr)', max(0, round($c - $yemek - $ml['toplam'], 2)), '']] : [])],
                        $c <= 0 ? _ikrBos('satis', $dn) : ($yemek === null ? 'Ana maliyet için reçete gerekli: Mutfak / BOH > Reçeteler / Yarı Mamül. ' . $not : null)),
                    _srSayfa('Hesap Notu', $not, [['Kalem', 'm', 30], ['Tutar', 'tl', 16], ['Ciroya Oran', 'y', 12]], $c > 0 ? [['Ciro', $c, 100], ['Yemek maliyeti (tahmini)', $yemek, $yemek !== null ? round($yemek / $c * 100, 1) : null], ['İşçilik maliyeti', $ml['toplam'], $oran($ml['toplam'], $c)], ['Ana maliyet', $yemek !== null ? $yemek + $ml['toplam'] : null, $prime]] : [], _ikrBos('satis', $dn)),
                ];

            // ---------------- MESAİ & DEVAM ----------------
            case 'plan_fiili':
                $dv = _ikrDevam($sid, $bas, $bit); $s = _ikrDevamSay($dv); $ps = round(array_sum(array_column($dv, 'plan_saat')), 1); $fs = round(array_sum(array_column($dv, 'fiili')), 1);
                $gun = []; foreach ($dv as $x) { $gun[$x['tarih']]['p'] = ($gun[$x['tarih']]['p'] ?? 0) + $x['plan_saat']; $gun[$x['tarih']]['f'] = ($gun[$x['tarih']]['f'] ?? 0) + $x['fiili']; } ksort($gun);
                return [
                    $pano([_srKpi('Planlı vardiya', $s['plan'], 'sayi', 'DC2626'), $kpiY('Devam oranı', $s['devam'], '7C3AED'), _srKpi('Planlanan saat', $ps, 'sayi', '991B1B'), _srKpi('Çalışılan saat', $fs, 'sayi', 'B91C1C'),
                        $kpiDeg('Fark (gerçek − plan)', $ps > 0 ? round(($fs - $ps) / $ps * 100, 1) : null, '7F1D1D'), _srKpi('Gelmeyen', $s['gelmedi'], 'sayi', 'DC2626'), _srKpi('Plansız mesai', $s['plansiz'], 'sayi', 'C2410C'), _srKpi('Çıkış yapılmamış', $s['acik'], 'sayi', 'D97706')],
                        [_srBlok('Günlere göre plan ve gerçek saat', 'col', 'DC2626', [['Gün', 'm'], ['Plan', 's'], ['Gerçek', 's']], array_map(fn ($t, $v) => [date('d.m', strtotime($t)), round($v['p'], 1), round($v['f'], 1)], array_keys($gun), array_values($gun)))], !$dv ? $bosVardiya : null),
                    _ikrSayfaDevamKisi($dv, $bosVardiya),
                    _ikrSayfaDevamListe('Tüm Kayıtlar', 'Her vardiya ve plansız mesai tek satır.', $dv, $bosVardiya),
                ];
            case 'gecikme':
                $dv = _ikrDevam($sid, $bas, $bit); $sor = array_values(array_filter($dv, fn ($x) => _ikrGecMi($x['durum']) || in_array($x['durum'], ['erken', 'gec_erken'], true)));
                $saat = []; foreach ($sor as $x) if (_ikrGecMi($x['durum'])) { $h = substr($x['plan_bas'], 0, 2) . ':00'; $saat[$h] = ($saat[$h] ?? 0) + 1; } ksort($saat);
                $gunN = []; foreach ($sor as $x) if (_ikrGecMi($x['durum'])) { $n = _ikrGunAd(date('N', strtotime($x['tarih']))); $gunN[$n] = ($gunN[$n] ?? 0) + 1; }
                $s = _ikrDevamSay($dv);
                return [
                    $pano([_srKpi('Geç kalma', $s['gec'], 'sayi', 'B91C1C'), _srKpi('Toplam geç (dk)', $s['gec_dk'], 'sayi', '991B1B'), _srKpi('Ortalama gecikme (dk)', $s['gec'] ? round($s['gec_dk'] / $s['gec'], 1) : 0, 'sayi', '7F1D1D'),
                        _srKpi('Erken çıkış', $s['erken'], 'sayi', 'C2410C'), _srKpi('Tolerans', _ikrAyar($sid)['tolerans'] . ' dk', 'metin', '64748B')],
                        [$blokGec($dv, 'B91C1C'), _srBlok('Vardiya başlangıç saatine göre geç kalma', 'col', 'DC2626', [['Vardiya', 'm'], ['Geç', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($saat), array_values($saat))),
                         _srBlok('Günlere göre geç kalma', 'col', '991B1B', [['Gün', 'm'], ['Geç', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($gunN), array_values($gunN)))], !$dv ? $bosVardiya : (!$sor ? _ikrBos('gec', $dn) : null)),
                    _ikrSayfaDevamKisi($dv, $bosVardiya),
                    _ikrSayfaDevamListe('Geç Giriş & Erken Çıkış Kayıtları', 'Toleransı aşan kayıtlar.', $sor, !$dv ? $bosVardiya : _ikrBos('gec', $dn)),
                ];
            case 'gelmeyen':
                $dv = _ikrDevam($sid, $bas, $bit); $gel = array_values(array_filter($dv, fn ($x) => $x['durum'] === 'gelmedi')); $pl = array_values(array_filter($dv, fn ($x) => $x['durum'] === 'plansiz'));
                $kisi = collect($gel)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->count()])->sortByDesc(1)->take(10)->values()->all();
                return [
                    $pano([_srKpi('Gelmeyen (vardiya)', count($gel), 'sayi', '9F1239'), _srKpi('Gelmeyen kişi', count(array_unique(array_column($gel, 'pid'))), 'sayi', 'BE123C'), $kpiY('Devamsızlık oranı', ($s = _ikrDevamSay($dv))['plan'] - $s['izinli'] > 0 ? round($s['gelmedi'] / ($s['plan'] - $s['izinli']) * 100, 1) : null, '881337'),
                        _srKpi('Plansız mesai', count($pl), 'sayi', 'C2410C'), _srKpi('Plansız saat', round(array_sum(array_column($pl, 'fiili')), 1), 'sayi', '9A3412')],
                        [_srBlok('En çok gelmeyenler', 'bar', '9F1239', [['Personel', 'm'], ['Gün', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $kisi))], !$dv ? $bosVardiya : (!$gel && !$pl ? _ikrBos('gelmedi', $dn) : null)),
                    _ikrSayfaDevamListe('Gelmeyenler', 'Vardiyası olup QR mesai açmayan, izinli olmayan personel. (Mesaiyi QR ile açmayı unutan da burada görünür.)', $gel, !$dv ? $bosVardiya : 'Seçili dönemde vardiyasına gelmeyen yok.'),
                    _ikrSayfaDevamListe('Plansız Mesai', 'Vardiyası olmadığı günde QR ile mesai açanlar.', $pl, 'Seçili dönemde plansız mesai yok.'),
                ];
            case 'fazla_mesai':
                $hf = _ikrHaftalik($sid, $bas, $bit); $fz = array_values(array_filter($hf, fn ($x) => $x['fazla'] > 0));
                $yb = substr($bit, 0, 4) . '-01-01'; $yil = []; foreach (_ikrHaftalik($sid, $yb, $bit) as $x) $yil[$x['pid']] = ($yil[$x['pid']] ?? 0) + $x['fazla'];
                arsort($yil); $yilSat = array_map(fn ($pid, $v) => [$per[$pid]->ad, $per[$pid]->dep, round($v, 1), round(270 - $v, 1), round($v / 270 * 100, 1)], array_keys($yil), array_values($yil)); $yilSat = array_values(array_filter($yilSat, fn ($x) => $x[2] > 0));
                return [
                    $pano([_srKpi('Fazla mesai (saat)', round(array_sum(array_column($fz, 'fazla')), 1), 'sayi', 'C2410C'), _srKpi('Fazla mesai yapan', count(array_unique(array_column($fz, 'pid'))), 'sayi', '9A3412'),
                        _srKpi('45 saati aşan hafta', count($fz), 'sayi', '7C2D12'), _srKpi('Yıllık 270 saate yaklaşan (≥%80)', count(array_filter($yilSat, fn ($x) => $x[4] >= 80)), 'sayi', 'DC2626'),
                        _srKpi('Ortalama haftalık saat', $hf ? round(array_sum(array_column($hf, 'saat')) / count($hf), 1) : 0, 'sayi', '64748B')],
                        [_srBlok('Kişi başı fazla mesai (saat)', 'bar', 'C2410C', [['Personel', 'm'], ['Saat', 's'], ['', 'm']], collect($fz)->groupBy('ad')->map(fn ($g, $a) => [$a, round($g->sum('fazla'), 1), ''])->sortByDesc(1)->take(10)->values()->all())],
                        !$d['mesai'] ? _ikrBos('mesai_yok', $dn) : (!$fz ? _ikrBos('fazla', $dn) : null)),
                    _srSayfa('Haftalık Çalışma', 'Kişinin her ISO haftasında QR mesaiyle çalıştığı toplam saat; 45 saati aşan kısım fazla mesaidir (İş K. md. 41, %50 zamlı ödenir).', [['Hafta (Pzt)', 't', 11], ['Personel', 'm', 20], ['Departman', 'm', 14], ['Çalışılan Saat', 's', 11, 'esik:45.1'], ['Fazla Mesai (saat)', 's', 13, 'top']], array_map(fn ($x) => [$x['hafta'], $x['ad'], $x['dep'], $x['saat'], $x['fazla']], $hf), $bosMesai),
                    _srSayfa('Yıllık Fazla Mesai (' . substr($bit, 0, 4) . ')', 'Yıl başından bu yana toplam fazla mesai; yasal üst sınır yılda 270 saat.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Fazla Mesai (saat)', 's', 13, 'top bar'], ['Kalan (270)', 's', 11], ['Kullanım %', 'y', 10, 'esik:80']], $yilSat, 'Bu yıl fazla mesai yok.'),
                ];

            // ---------------- VARDİYA VERİMİ ----------------
            case 'isi':
                $cs = []; $sa = []; $gunSay = [];
                foreach (_ikrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gunSay[$n] = ($gunSay[$n] ?? 0) + 1; }
                foreach (_ikrMesai($sid, $bas, $bit) as $m) {
                    if (!$m['son']) continue;
                    for ($h = (int) floor($m['giris'] / 3600) * 3600; $h < $m['son']; $h += 3600) { $pay = (min($h + 3600, $m['son']) - max($h, $m['giris'])) / 3600; if ($pay <= 0) continue; $n = (int) date('N', $h); $s = (int) date('G', $h); $cs[$n][$s] = ($cs[$n][$s] ?? 0) + $pay; }
                }
                foreach (_ikrAdisyon($sid, $bas, $bit) as $a) $sa[$a['gun']][$a['saat']] = ($sa[$a['gun']][$a['saat']] ?? 0) + $a['toplam'];
                $saatler = []; foreach ([$cs, $sa] as $m) foreach ($m as $r) foreach ($r as $h => $v) $saatler[$h] = 1; ksort($saatler); $saatler = array_keys($saatler);
                $mat = function ($f) use ($saatler, $gunSay) { $o = []; for ($n = 1; $n <= 7; $n++) { if (empty($gunSay[$n])) continue; $o[] = array_merge([_ikrGunAd($n)], array_map(fn ($h) => $f($n, $h, $gunSay[$n]), $saatler)); } return $o; };
                $kol = array_merge([['Gün', 'm', 11]], array_map(fn ($h) => [sprintf('%02d', $h), 's', 6, 'mat'], $saatler));
                $toplamC = array_sum(array_map('array_sum', $sa)); $toplamS = array_sum(array_map('array_sum', $cs)); $ort = $toplamS > 0 ? $toplamC / $toplamS : 0;
                $dikkat = []; $slot = []; foreach ($sa as $n => $r) foreach ($r as $h => $v) if ($v > 0) $slot[] = $v / $gunSay[$n]; $ortCiro = $slot ? array_sum($slot) / count($slot) : 0; // ciro olan gün-saatlerin ortalaması
                foreach ($cs as $n => $r) foreach ($r as $h => $v) { $kisi = $v / $gunSay[$n]; $ciro = ($sa[$n][$h] ?? 0) / $gunSay[$n]; $sp = $v > 0 ? ($sa[$n][$h] ?? 0) / $v : 0;
                    if ($kisi >= 1.5 && $ort > 0 && $sp < $ort * 0.5) $dikkat[] = [_ikrGunAd($n), sprintf('%02d:00', $h), round($kisi, 1), round($ciro, 2), round($sp, 2), 'Fazla kadro: ciroya göre kalabalık'];
                    elseif ($ort > 0 && $sp > $ort * 1.6 && $ciro >= $ortCiro) $dikkat[] = [_ikrGunAd($n), sprintf('%02d:00', $h), round($kisi, 1), round($ciro, 2), round($sp, 2), 'Yoğun: kadro yetersiz olabilir']; }
                usort($dikkat, fn ($a, $b) => $b[3] <=> $a[3]);
                $bos = !$d['mesai'] ? _ikrBos('mesai_yok', $dn) : (!$cs ? _ikrBos('mesai', $dn) : null);
                return [
                    $pano([$kpiTl('Ortalama saat başı ciro', $toplamS > 0 ? round($ort, 2) : null, '7C3AED'), _srKpi('Fazla kadrolu saat', count(array_filter($dikkat, fn ($x) => str_starts_with($x[5], 'Fazla'))), 'sayi', 'D97706'),
                        _srKpi('Yetersiz kadrolu saat', count(array_filter($dikkat, fn ($x) => str_starts_with($x[5], 'Yoğun'))), 'sayi', 'DC2626')], [], $bos),
                    ['_oto' => []] + _srSayfa('Ortalama Çalışan Sayısı', 'Gün × saat: o saatte mesaide olan ortalama kişi (QR mesai). Koyu = kalabalık.', $kol, $mat(fn ($n, $h, $g) => round(($cs[$n][$h] ?? 0) / $g, 1)), $bos ?? _ikrBos('mesai', $dn)),
                    ['_oto' => []] + _srSayfa('Ortalama Ciro', 'Gün × saat: o saatte kapanan adisyonların ortalama cirosu.', $kol, $mat(fn ($n, $h, $g) => round(($sa[$n][$h] ?? 0) / $g)), !$d['satis'] ? _ikrBos('satis', $dn) : _ikrBos('satis', $dn)),
                    ['_oto' => []] + _srSayfa('Çalışan Başına Ciro', 'Gün × saat: ciro ÷ çalışan-saat. Düşük = o saatte ciroya göre fazla kişi var.', $kol, $mat(fn ($n, $h, $g) => ($cs[$n][$h] ?? 0) > 0 ? round(($sa[$n][$h] ?? 0) / $cs[$n][$h]) : 0), $bos ?? _ikrBos('mesai', $dn)),
                    ['grafiksiz' => true] + _srSayfa('Dikkat Edilecek Saatler', 'Ortalama saat başı cironun yarısının altında kalan kalabalık saatler (fazla kadro) ve cirosu ortalamanın üstünde olup saat başı cirosu 1,6 katını aşan yoğun saatler (eksik kadro).', [['Gün', 'm', 11], ['Saat', 'm', 7], ['Ort. Çalışan', 's', 10], ['Ort. Ciro', 'tl', 12], ['Çalışan Başına Ciro', 'tl', 14], ['Değerlendirme', 'm', 30]], $dikkat, $bos ?? 'Belirgin fazla ya da eksik kadrolu saat yok.'),
                ];
            case 'vardiya_dagilim':
                $v = _ikrVardiya($sid, $bas, $bit);
                $kisi = []; foreach ($v as $x) { $k = &$kisi[$x['pid']]; $k['n'] = ($k['n'] ?? 0) + 1; $k['s'] = ($k['s'] ?? 0) + $x['saat']; $k['gun'][date('N', $x['bas'])] = ($k['gun'][date('N', $x['bas'])] ?? 0) + 1; $k['aksam'] = ($k['aksam'] ?? 0) + ((int) date('G', $x['bas']) >= 15 ? 1 : 0); unset($k); }
                $haftaSay = max(1, $dn['gun'] / 7);
                $sat = array_map(fn ($pid, $k) => array_merge([$per[$pid]->ad, $per[$pid]->dep, $k['n'], round($k['s'], 1), round($k['s'] / $haftaSay, 1)], array_map(fn ($n) => $k['gun'][$n] ?? 0, range(1, 7)), [$k['aksam']]), array_keys($kisi), array_values($kisi));
                usort($sat, fn ($a, $b) => $b[3] <=> $a[3]);
                $poz = collect($v)->groupBy('pozisyon')->map(fn ($g, $a) => [$a, $g->count(), round($g->sum('saat'), 1)])->sortByDesc(2)->values()->all();
                return [
                    $pano([_srKpi('Vardiya', count($v), 'sayi', '6D28D9'), _srKpi('Planlanan saat', round(array_sum(array_column($v, 'saat')), 1), 'sayi', '5B21B6'), _srKpi('Kişi', count($kisi), 'sayi', '4C1D95'),
                        _srKpi('Kişi başı haftalık saat', $kisi ? round(array_sum(array_column($v, 'saat')) / count($kisi) / $haftaSay, 1) : 0, 'sayi', '7C3AED')],
                        [_srBlok('Pozisyona göre planlanan saat', 'pie', '6D28D9', [['Pozisyon', 'm'], ['Saat', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], $poz))], !$v ? (!$d['vardiya'] ? _ikrBos('vardiya_yok', $dn) : _ikrBos('vardiya', $dn)) : null),
                    _srSayfa('Kişi Bazında Vardiya', 'Planlanan vardiya sayısı, saati, haftalık ortalama ve günlere dağılımı; akşam = 15:00 ve sonrası başlayan vardiya.', array_merge([['Personel', 'm', 18], ['Departman', 'm', 13], ['Vardiya', 's', 8, 'top'], ['Saat', 's', 8, 'top bar'], ['Haftalık Ort.', 's', 10, 'esik:45.1']], array_map(fn ($n) => [mb_substr(_ikrGunAd($n), 0, 3), 's', 5], range(1, 7)), [['Akşam', 's', 7]]), $sat, !$d['vardiya'] ? _ikrBos('vardiya_yok', $dn) : _ikrBos('vardiya', $dn)),
                    _srSayfa('Pozisyon Bazında', 'Vardiya planındaki pozisyonlar (pozisyon yoksa departman).', [['Pozisyon', 'm', 22], ['Vardiya', 's', 9, 'top'], ['Saat', 's', 9, 'top bar']], $poz, _ikrBos('vardiya', $dn)),
                ];

            // ---------------- İZİN ----------------
            case 'izin_bakiye':
                $sat = []; $bugun = date('Y-m-d'); $yillik = [];
                foreach (_ikrIzinler($sid, '2000-01-01', '2100-01-01') as $z) if ($z['tip'] === 'yillik') $yillik[$z['pid']][] = $z;
                foreach ($aktif as $p) {
                    $h = _ikrIzinHak($p, $bugun); if (!$h) { $sat[] = [$p->ad, $p->dep, '—', 'Giriş tarihi yok', null, null, null, null, '']; continue; }
                    $kul = array_sum(array_map(fn ($z) => $z['bas'] >= $h['donem_bas'] ? $z['gun'] : 0, $yillik[$p->id] ?? []));
                    $sat[] = [$p->ad, $p->dep, _ikrBaslangic($p), _ikrKidemMetin(_ikrKidemAy($p, $bugun)), $h['hak'], $kul, $h['hak'] - $kul, $h['yil'] < 1 ? null : $h['donem_bas'], $h['sonraki']];
                }
                usort($sat, fn ($a, $b) => ($b[6] ?? -99) <=> ($a[6] ?? -99));
                $kalan = array_sum(array_map(fn ($x) => max(0, (float) ($x[6] ?? 0)), $sat));
                return [
                    $pano([_srKpi('Aktif personel', count($aktif), 'sayi', '0891B2'), _srKpi('Toplam kalan izin (gün)', $kalan, 'sayi', '0E7490'), _srKpi('Kişi başı kalan', count($aktif) ? round($kalan / count($aktif), 1) : 0, 'sayi', '155E75'),
                        _srKpi('Hak bekleyen (< 1 yıl)', count(array_filter($sat, fn ($x) => $x[4] === 0)), 'sayi', '64748B'), _srKpi('Giriş tarihi eksik', count(array_filter($sat, fn ($x) => $x[2] === '—')), 'sayi', 'D97706')],
                        [_srBlok('En çok kalan izin', 'bar', '0891B2', [['Personel', 'm'], ['Kalan gün', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[6], ''], array_slice(array_values(array_filter($sat, fn ($x) => ($x[6] ?? 0) > 0)), 0, 10)))], !$aktif ? _ikrBos('personel', $dn) : null),
                    _srSayfa('Yıllık İzin Hakları', 'Bugüne göre: hak bu hizmet yılı için (1–5 yıl 14, 5–15 yıl 20, 15+ yıl 26 gün; 18 yaş altı / 50 yaş üstü en az 20). Kullanılan = bu hizmet yılı başından beri onaylı yıllık izin. Önceki yıllardan devreden izin dahil değildir.',
                        [['Personel', 'm', 20], ['Departman', 'm', 13], ['İşe Giriş', 't', 11], ['Kıdem', 'm', 11], ['Hak (gün)', 's', 8, 'top'], ['Kullanılan', 's', 9, 'top'], ['Kalan', 's', 8, 'top bar'], ['Hizmet Yılı Başı', 't', 12], ['Sonraki Hak', 't', 11]], $sat, _ikrBos('personel', $dn)),
                ];
            case 'izin_kullanim':
                $iz = _ikrIzinler($sid, $bas, $bit); $tip = []; foreach ($iz as $z) $tip[_ikrIzinTipAd($z['tip'])] = ($tip[_ikrIzinTipAd($z['tip'])] ?? 0) + $z['donem_gun']; arsort($tip);
                $kisi = collect($iz)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->first()['dep'], $g->sum('donem_gun'), $g->where('tip', 'yillik')->sum('donem_gun'), $g->where('tip', 'rapor')->sum('donem_gun'), $g->whereNotIn('tip', ['yillik', 'rapor'])->sum('donem_gun')])->sortByDesc(2)->values()->all();
                $ay = []; foreach (_ikrIzinler($sid, date('Y-m-d', strtotime($bit . ' -365 day')), $bit) as $z) foreach (_ikrGunler($z['bas'], $z['bit']) as $t) if ($t <= $bit) $ay[substr($t, 0, 7)] = ($ay[substr($t, 0, 7)] ?? 0) + 1; ksort($ay);
                $bos = !$d['izin'] ? _ikrBos('izin_yok', $dn) : _ikrBos('izin', $dn);
                return [
                    $pano([_srKpi('İzin günü (dönem)', array_sum(array_column($iz, 'donem_gun')), 'sayi', '0E7490'), _srKpi('İzin kullanan', count($kisi), 'sayi', '155E75'), _srKpi('Rapor (hastalık) günü', $tip['Rapor (hastalık)'] ?? 0, 'sayi', 'DC2626'), _srKpi('Yıllık izin günü', $tip['Yıllık izin'] ?? 0, 'sayi', '0891B2')],
                        [_srBlok('İzin türleri (gün)', 'pie', '0E7490', [['Tür', 'm'], ['Gün', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($tip), array_values($tip))),
                         _srBlok('Son 12 ay: aylara göre izin günü', 'col', '0891B2', [['Ay', 'm'], ['Gün', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($ay), array_values($ay)))], !$iz ? $bos : null),
                    _srSayfa('Kişi Bazında', 'Dönemdeki onaylı izin günleri.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Toplam', 's', 8, 'top bar'], ['Yıllık', 's', 8, 'top'], ['Rapor', 's', 8, 'top esik:3'], ['Diğer', 's', 8, 'top']], $kisi, $bos),
                    _ikrSayfaIzin($iz, $bos),
                ];
            case 'izin_talep':
                $iz = array_values(array_filter(_ikrIzinler($sid, '2000-01-01', '2100-01-01', null), fn ($z) => $z['talep'] >= $bas && $z['talep'] <= $bit));
                $dur = []; foreach ($iz as $z) $dur[_ikrIzinDurumAd($z['durum'])] = ($dur[_ikrIzinDurumAd($z['durum'])] ?? 0) + 1;
                $karar = array_values(array_filter(array_map(fn ($z) => $z['karar'] && $z['talep'] ? (strtotime($z['karar']) - strtotime($z['talep'])) / 86400 : null, $iz), fn ($x) => $x !== null));
                return [
                    $pano([_srKpi('Talep', count($iz), 'sayi', '155E75'), _srKpi('Onaylanan', $dur['Onaylandı'] ?? 0, 'sayi', '059669'), _srKpi('Reddedilen', $dur['Reddedildi'] ?? 0, 'sayi', 'DC2626'), _srKpi('Bekleyen', $dur['Onay bekliyor'] ?? 0, 'sayi', 'D97706'),
                        _srKpi('Ort. karar süresi (gün)', $karar ? round(array_sum($karar) / count($karar), 1) : '—', $karar ? 'sayi' : 'metin', '0E7490')],
                        [_srBlok('Talep durumları', 'pie', '155E75', [['Durum', 'm'], ['Talep', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($dur), array_values($dur)))], !$iz ? (!$d['izin'] ? _ikrBos('izin_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') açılan izin talebi yok.') : null),
                    _srSayfa('İzin Talepleri', 'Seçili dönemde açılan talepler.', [['Talep Tarihi', 't', 11], ['Personel', 'm', 20], ['Tür', 'm', 15], ['Başlangıç', 't', 11], ['Bitiş', 't', 11], ['Gün', 's', 7, 'top'], ['Durum', 'm', 13], ['Karar Tarihi', 't', 11], ['Açıklama', 'm', 24]],
                        array_map(fn ($z) => [$z['talep'], $z['ad'], _ikrIzinTipAd($z['tip']), $z['bas'], $z['bit'], $z['gun'], _ikrIzinDurumAd($z['durum']), $z['karar'] ?: null, $z['aciklama']], $iz), 'Seçili dönemde açılan izin talebi yok.'),
                ];

            // ---------------- DEVİR & KIDEM ----------------
            case 'devir':
                $dvr = _ikrDevir($sid, $bas, $bit); $seb = _ikrAyrilisSebepleri(); $sb = []; foreach ($dvr['ayrilan'] as $p) { $k = $p->sebep ? ($seb[$p->sebep] ?? $p->sebep) : 'Girilmemiş'; $sb[$k] = ($sb[$k] ?? 0) + 1; } arsort($sb);
                $dp = []; foreach ($dvr['ayrilan'] as $p) $dp[$p->dep] = ($dp[$p->dep] ?? 0) + 1; arsort($dp);
                $ay = []; for ($i = 11; $i >= 0; $i--) { $a = date('Y-m', strtotime(date('Y-m-01', strtotime($bit)) . " -$i month")); $ay[$a] = [0, 0]; }
                foreach ($per as $p) { if (($g = _ikrBaslangic($p)) && isset($ay[substr($g, 0, 7)])) $ay[substr($g, 0, 7)][0]++; if ($p->cikis && isset($ay[substr($p->cikis, 0, 7)])) $ay[substr($p->cikis, 0, 7)][1]++; }
                return [
                    $pano([_srKpi('Dönem başı kadro', $dvr['bas'], 'sayi', '4F46E5'), _srKpi('Dönem sonu kadro', $dvr['son'], 'sayi', '4338CA'), _srKpi('İşe giren', count($dvr['giren']), 'sayi', '059669'), _srKpi('Ayrılan', count($dvr['ayrilan']), 'sayi', 'DC2626'),
                        $kpiY('Devir oranı (dönem)', $dvr['oran'], '3730A3'), $kpiY('Yıllık karşılığı', $dvr['yillik'], '312E81'), _srKpi('Sektör (restoran)', 'yılda ~%70+', 'metin', '64748B'), _srKpi('Ayrılış sebebi girilmemiş', $sb['Girilmemiş'] ?? 0, 'sayi', 'D97706')],
                        [_srBlok('Son 12 ay: giren ve ayrılan', 'col', '4F46E5', [['Ay', 'm'], ['Giren', 's'], ['Ayrılan', 's']], array_map(fn ($a, $v) => [$a, $v[0], $v[1]], array_keys($ay), array_values($ay))),
                         _srBlok('Ayrılış sebepleri', 'pie', 'DC2626', [['Sebep', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($sb), array_values($sb))),
                         _srBlok('Departmana göre ayrılan', 'bar', '4338CA', [['Departman', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($dp), array_values($dp)))], !$per ? _ikrBos('personel', $dn) : null),
                    _ikrSayfaDevir($dvr, _ikrBos('devir', $dn)),
                ];
            case 'kidem':
                $kid = []; $dep = []; $sat = [];
                foreach ($aktif as $p) { $a = _ikrKidemAy($p, $bit); $k = _ikrKidemGrup($a); $kid[$k] = ($kid[$k] ?? 0) + 1; if ($a !== null) $dep[$p->dep][] = $a; $sat[] = [$p->ad, $p->dep, _ikrBaslangic($p), $p->giris ? '' : 'kayıt tarihi', _ikrKidemMetin($a), $a, $k]; }
                usort($sat, fn ($a, $b) => ($b[5] ?? -1) <=> ($a[5] ?? -1));
                $sira = ['0-3 ay', '3-6 ay', '6-12 ay', '1-2 yıl', '2-5 yıl', '5 yıl +', 'Bilinmiyor']; $kidS = []; foreach ($sira as $s) if (isset($kid[$s])) $kidS[$s] = $kid[$s];
                $tum = array_filter(array_column($sat, 5), fn ($x) => $x !== null);
                return [
                    $pano([_srKpi('Aktif kadro', count($aktif), 'sayi', '4338CA'), _srKpi('Ortalama kıdem', $tum ? _ikrKidemMetin((int) round(array_sum($tum) / count($tum))) : '—', 'metin', '3730A3'), _srKpi('6 aydan yeni', ($kid['0-3 ay'] ?? 0) + ($kid['3-6 ay'] ?? 0), 'sayi', 'D97706'), _srKpi('2 yıldan eski', ($kid['2-5 yıl'] ?? 0) + ($kid['5 yıl +'] ?? 0), 'sayi', '059669')],
                        [_srBlok('Kıdem grupları', 'col', '4338CA', [['Kıdem', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($kidS), array_values($kidS))),
                         _srBlok('Departmana göre ortalama kıdem (ay)', 'bar', '4F46E5', [['Departman', 'm'], ['Ay', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, round(array_sum($v) / count($v), 1), ''], array_keys($dep), array_values($dep)))], !$aktif ? _ikrBos('personel', $dn) : null),
                    _srSayfa('Kadro ve Kıdem', 'Kıdem dönem sonuna göre; işe giriş tarihi yoksa personelin sisteme eklendiği tarih kullanılır.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['İşe Giriş', 't', 11], ['Not', 'm', 12], ['Kıdem', 'm', 12], ['Ay', 's', 7], ['Grup', 'm', 10]], $sat, _ikrBos('personel', $dn)),
                ];
            case 'erken_kayip':
                $yb = date('Y-m-d', strtotime($bit . ' -365 day')); $seb = _ikrAyrilisSebepleri(); $sat = []; $giren = 0;
                foreach ($per as $p) { $g = _ikrBaslangic($p); if (!$g || $g < $yb || $g > $bit) continue; $giren++; if (!$p->cikis) continue; $gun = (int) round((strtotime($p->cikis) - strtotime($g)) / 86400); if ($gun <= 90) $sat[] = [$p->ad, $p->dep, $g, $p->cikis, $gun, $p->sebep ? ($seb[$p->sebep] ?? $p->sebep) : 'Girilmemiş']; }
                usort($sat, fn ($a, $b) => $a[4] <=> $b[4]);
                $dp = []; foreach ($sat as $x) $dp[$x[1]] = ($dp[$x[1]] ?? 0) + 1; arsort($dp);
                return [
                    $pano([_srKpi('Son 1 yılda işe giren', $giren, 'sayi', '3730A3'), _srKpi('İlk 90 günde ayrılan', count($sat), 'sayi', 'DC2626'), $kpiY('Erken kayıp oranı', $giren ? round(count($sat) / $giren * 100, 1) : null, 'B91C1C'),
                        _srKpi('Ortalama kalış (gün)', $sat ? round(array_sum(array_column($sat, 4)) / count($sat)) : '—', $sat ? 'sayi' : 'metin', '7F1D1D')],
                        [_srBlok('Departmana göre erken kayıp', 'bar', 'DC2626', [['Departman', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($dp), array_values($dp)))], !$sat ? _ikrBos('erken', $dn) : null),
                    _srSayfa('İlk 90 Günde Ayrılanlar', 'İşe alım ve oryantasyon kalitesinin göstergesi: yeni giren personelin ilk 3 ayda ayrılması.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['İşe Giriş', 't', 11], ['Ayrılış', 't', 11], ['Kaç Günde', 's', 9, 'bar'], ['Sebep', 'm', 24]], $sat, _ikrBos('erken', $dn)),
                ];

            // ---------------- PERFORMANS ----------------
            case 'garson':
                $g = _ikrGarson($sid, $bas, $bit); $go = collect(_ikrGarson($sid, $dn['obas'], $dn['obit']))->keyBy('pid');
                $sat = array_map(fn ($x) => [$x['ad'], $x['dep'], $x['ciro'], _srYuzde($x['ciro'], $go[$x['pid']]['ciro'] ?? 0), $x['adisyon'], $x['ort'], $x['misafir'] ?: null, $x['kisi_basi'], $x['bahsis'], $x['adim'] ?: null, $x['saat'] ?: null, $x['saat_basi'], $x['iptal_tl'], $x['ikram_tl']], $g);
                return [
                    $pano([_srKpi('Satış yapan personel', count($g), 'sayi', 'D97706'), _srKpi('Toplam ciro', array_sum(array_column($g, 'ciro')), 'tl', 'B45309'), _srKpi('Ortalama hesap', ($n = array_sum(array_column($g, 'adisyon'))) ? round(array_sum(array_column($g, 'ciro')) / $n, 2) : 0, 'tl', '92400E'),
                        _srKpi('Toplam bahşiş', array_sum(array_column($g, 'bahsis')), 'tl', '059669'), _srKpi('En çok satan', $g[0]['ad'] ?? '—', 'metin', 'D97706')],
                        [_srBlok('Ciroya göre personel', 'bar', 'D97706', [['Personel', 'm'], ['Ciro', 'tl'], ['Adisyon', 's']], array_map(fn ($x) => [$x['ad'], $x['ciro'], $x['adisyon']], array_slice($g, 0, 10))),
                         _srBlok('Ortalama hesap', 'bar', 'B45309', [['Personel', 'm'], ['Ort. hesap', 'tl'], ['', 'm']], array_map(fn ($x) => [$x['ad'], $x['ort'], ''], array_slice(collect($g)->sortByDesc('ort')->values()->all(), 0, 10)))], !$g ? _ikrBos('garson', $dn) : null),
                    _srSayfa('Satış Performansı', 'Ciro = personelin açtığı ve ödenen adisyonların toplamı. Saat başı satış = ciro ÷ QR mesai saati. Değişim önceki döneme göre.',
                        [['Personel', 'm', 18], ['Departman', 'm', 12], ['Ciro', 'tl', 13, 'top bar'], ['Değişim %', 'y', 9], ['Adisyon', 's', 8, 'top'], ['Ort. Hesap', 'tl', 11], ['Misafir', 's', 8, 'top'], ['Kişi Başı', 'tl', 10], ['Bahşiş', 'tl', 10, 'top'], ['Adım', 's', 9], ['Saat', 's', 7, 'top'], ['Saat Başı Satış', 'tl', 12], ['İptal', 'tl', 10, 'top'], ['İkram', 'tl', 10, 'top']], $sat, _ikrBos('garson', $dn)),
                ];
            case 'mutfak_ekip':
                $sat = [];
                if (function_exists('_mrKalemler')) {
                    $gr = []; foreach (_mrKalemler($sid, $bas, $bit) as $x) if ($x['hazirlayan'] && isset($per[$x['hazirlayan']]) && $x['top'] !== null && !$x['iptal']) $gr[$x['hazirlayan']][] = $x;
                    $saat = []; foreach (_ikrMesai($sid, $bas, $bit) as $m) $saat[$m['pid']] = ($saat[$m['pid']] ?? 0) + ($m['saat'] ?? 0);
                    foreach ($gr as $pid => $l) { $n = count($l); $ad = array_sum(array_column($l, 'adet')); $s = round($saat[$pid] ?? 0, 1); $sat[] = [$per[$pid]->ad, $per[$pid]->dep, $n, round($ad, 1), round(array_sum(array_column($l, 'top')) / $n, 1), round(count(array_filter($l, fn ($x) => $x['zamaninda'])) / $n * 100, 1), $s ?: null, $s > 0 ? round($ad / $s, 1) : null]; }
                    usort($sat, fn ($a, $b) => $b[3] <=> $a[3]);
                }
                return [
                    $pano([_srKpi('Ölçülen aşçı', count($sat), 'sayi', 'B45309'), _srKpi('Hazırlanan porsiyon', array_sum(array_column($sat, 3)), 'sayi', '92400E'), $kpiY('Ortalama hedefe uyum', $sat ? round(array_sum(array_column($sat, 5)) / count($sat), 1) : null, '059669')],
                        [_srBlok('Porsiyon (aşçı)', 'bar', 'B45309', [['Aşçı', 'm'], ['Porsiyon', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[3], $x[5]], array_slice($sat, 0, 10)))], !$sat ? (!$d['kds'] ? _ikrBos('kds_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') KDS\'de aşçıya yazılmış ölçüm yok.') : null),
                    _srSayfa('Mutfak Ekibi', 'KDS\'de "Başla / Hazır"a basan personele göre; süre = sipariş mutfağa gidişten hazıra. Ayrıntı için Mutfak Raporları > Ekip & Talepler.', [['Aşçı', 'm', 20], ['Departman', 'm', 14], ['Kalem', 's', 8, 'top'], ['Porsiyon', 's', 9, 'top bar'], ['Ort. Süre (dk)', 's', 11], ['Hedefe Uyum %', 'y', 12], ['Mesai Saati', 's', 10, 'top'], ['Saat Başı Porsiyon', 's', 13]], $sat, !$d['kds'] ? _ikrBos('kds_yok', $dn) : 'Seçili dönemde ölçüm yok.'),
                ];
            case 'iptal_personel':
                $ip = _ikrIptal($sid, $bas, $bit); $g = collect(_ikrGarson($sid, $bas, $bit))->keyBy('pid'); $sat = [];
                foreach ($ip as $pid => $t) { $iptal = ($t['void']['tl'] ?? 0) + ($t['odeme_iptal']['tl'] ?? 0); $ciro = $g[$pid]['ciro'] ?? 0; $sat[] = [$per[$pid]->ad, $per[$pid]->dep, $t['void']['n'] ?? 0, round($t['void']['tl'] ?? 0, 2), $t['odeme_iptal']['n'] ?? 0, round($t['odeme_iptal']['tl'] ?? 0, 2), $t['ikram']['n'] ?? 0, round($t['ikram']['tl'] ?? 0, 2), $t['indirim']['n'] ?? 0, round($t['indirim']['tl'] ?? 0, 2), $ciro > 0 ? round(($iptal + ($t['ikram']['tl'] ?? 0) + ($t['indirim']['tl'] ?? 0)) / $ciro * 100, 1) : null]; }
                usort($sat, fn ($a, $b) => ($b[3] + $b[5] + $b[7] + $b[9]) <=> ($a[3] + $a[5] + $a[7] + $a[9]));
                return [
                    $pano([_srKpi('İptal tutarı', array_sum(array_column($sat, 3)) + array_sum(array_column($sat, 5)), 'tl', 'BE123C'), _srKpi('İkram tutarı', array_sum(array_column($sat, 7)), 'tl', '9F1239'), _srKpi('İndirim tutarı', array_sum(array_column($sat, 9)), 'tl', '881337'), _srKpi('Kişi', count($sat), 'sayi', '64748B')],
                        [_srBlok('Personele göre iptal + ikram + indirim', 'bar', 'BE123C', [['Personel', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], round($x[3] + $x[5] + $x[7] + $x[9], 2), ''], array_slice($sat, 0, 10)))], !$sat ? _ikrBos('iptal', $dn) : null),
                    _srSayfa('Personel İptal & İkram', 'İşlemi yapan personele göre. Satışa oran = (iptal + ikram + indirim) ÷ personelin cirosu; yüksek oran kontrol gerektirir.', [['Personel', 'm', 18], ['Departman', 'm', 12], ['Ürün İptali', 's', 9, 'top'], ['İptal ₺', 'tl', 11, 'top'], ['Ödeme İptali', 's', 9, 'top'], ['Ödeme İptal ₺', 'tl', 11, 'top'], ['İkram', 's', 7, 'top'], ['İkram ₺', 'tl', 10, 'top'], ['İndirim', 's', 7, 'top'], ['İndirim ₺', 'tl', 10, 'top'], ['Satışa Oran %', 'y', 11, 'esik:5']], $sat, _ikrBos('iptal', $dn)),
                ];

            // ---------------- BORDRO & AVANS ----------------
            case 'bordro':
                $sat = _ikrBordroSat($sid, $bas, $bit); $tl = fn ($i) => round(array_sum(array_column($sat, $i)), 2);
                return [
                    $pano([_srKpi('Hakediş', $tl(7), 'tl', '0284C7'), _srKpi('Avans', $tl(8), 'tl', '0369A1'), _srKpi('Ödenen', $tl(9), 'tl', '075985'), _srKpi('Net ödenecek', $tl(10), 'tl', 'DC2626'), _srKpi('Prim + ek ödeme', $tl(4) + $tl(5), 'tl', '059669'), _srKpi('Kesinti', $tl(6), 'tl', 'B91C1C'), _srKpi('Kişi', count($sat), 'sayi', '64748B')],
                        [_srBlok('Net ödenecek (kişi)', 'bar', '0284C7', [['Personel', 'm'], ['Net', 'tl'], ['Hakediş', 'tl']], array_map(fn ($x) => [$x[0], $x[10], $x[7]], array_slice($sat, 0, 12)))], !$sat ? _ikrBos('personel', $dn) : $bosMaas),
                    _ikrSayfaBordro($sat, $bosMaas ?? _ikrBos('personel', $dn)),
                    _srSayfa('Hareketler', 'Dönemdeki avans, ödeme, prim, kesinti ve ek ödeme kayıtları.', [['Tarih', 't', 11], ['Personel', 'm', 20], ['Tür', 'm', 10], ['Tutar', 'tl', 12, 'top'], ['Açıklama', 'm', 34]],
                        array_map(fn ($h) => [$h['tarih'], $per[$h['pid']]->ad, ['avans' => 'Avans', 'odeme' => 'Ödeme', 'prim' => 'Prim', 'kesinti' => 'Kesinti', 'ek_odeme' => 'Ek ödeme'][$h['tur']] ?? $h['tur'], $h['tutar'], $h['aciklama']], _ikrHareket($sid, $bas, $bit)), _ikrBos('hareket', $dn)),
                ];
            case 'avans':
                $av = array_values(array_filter(_ikrHareket($sid, $bas, $bit), fn ($h) => $h['tur'] === 'avans')); $k = [];
                foreach ($av as $h) { $k[$h['pid']]['n'] = ($k[$h['pid']]['n'] ?? 0) + 1; $k[$h['pid']]['tl'] = ($k[$h['pid']]['tl'] ?? 0) + $h['tutar']; }
                $ayOran = max(1, $dn['gun'] / 30);
                $sat = array_map(fn ($pid, $v) => [$per[$pid]->ad, $per[$pid]->dep, $v['n'], round($v['tl'], 2), $per[$pid]->maas, $per[$pid]->maas > 0 && $per[$pid]->maas_tipi === 'aylik' ? round($v['tl'] / ($per[$pid]->maas * $ayOran) * 100, 1) : null], array_keys($k), array_values($k));
                usort($sat, fn ($a, $b) => $b[3] <=> $a[3]);
                return [
                    $pano([_srKpi('Avans toplamı', array_sum(array_column($sat, 3)), 'tl', '0369A1'), _srKpi('Avans sayısı', count($av), 'sayi', '075985'), _srKpi('Avans alan', count($sat), 'sayi', '0284C7'), _srKpi('Maaşının %50+ avans alan', count(array_filter($sat, fn ($x) => ($x[5] ?? 0) >= 50)), 'sayi', 'DC2626')],
                        [_srBlok('Kişi başı avans', 'bar', '0369A1', [['Personel', 'm'], ['Tutar', 'tl'], ['Kez', 's']], array_map(fn ($x) => [$x[0], $x[3], $x[2]], array_slice($sat, 0, 10)))], !$av ? _ikrBos('avans', $dn) : null),
                    _srSayfa('Avans Özeti', 'Maaşa oran: aylık maaşlılarda dönem avansı ÷ (maaş × dönemdeki ay). Sık ve yüksek avans, maddi sıkıntı ya da ayrılma riski işareti olabilir.', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Kez', 's', 6, 'top esik:3'], ['Tutar', 'tl', 12, 'top bar'], ['Maaş', 'tl', 12], ['Maaşa Oran %', 'y', 12, 'esik:50']], $sat, _ikrBos('avans', $dn)),
                    _srSayfa('Avans Kayıtları', '', [['Tarih', 't', 11], ['Personel', 'm', 20], ['Tutar', 'tl', 12, 'top'], ['Açıklama', 'm', 34]], array_map(fn ($h) => [$h['tarih'], $per[$h['pid']]->ad, $h['tutar'], $h['aciklama']], $av), _ikrBos('avans', $dn)),
                ];
            case 'yemek':
                $y = []; if (_srTablo('personel_yemek')) { try { $y = DB::table('personel_yemek')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->get()->all(); } catch (\Throwable $e) {} }
                $y = array_values(array_filter($y, fn ($r) => isset($per[(int) $r->personel_id])));
                $k = collect($y)->groupBy('personel_id')->map(fn ($g, $pid) => [$per[$pid]->ad, $per[$pid]->dep, $g->count(), round($g->sum('tutar'), 2)])->sortByDesc(3)->values()->all();
                $gun = collect($y)->groupBy(fn ($r) => substr((string) $r->tarih, 0, 10))->map(fn ($g, $t) => [$t, $g->count(), round($g->sum('tutar'), 2)])->sortKeys()->values()->all();
                $bos = !$d['yemek'] ? _ikrBos('yemek', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') personel yemeği kaydı yok.';
                return [
                    $pano([_srKpi('Yemek sayısı', count($y), 'sayi', '075985'), _srKpi('Toplam maliyet', array_sum(array_column($k, 3)), 'tl', '0369A1'), _srKpi('Öğün başı', count($y) ? round(array_sum(array_column($k, 3)) / count($y), 2) : 0, 'tl', '0284C7'), _srKpi('Kişi', count($k), 'sayi', '64748B')],
                        [_srBlok('Günlere göre yemek maliyeti', 'col', '075985', [['Gün', 'm'], ['Tutar', 'tl'], ['Öğün', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[1]], $gun))], !$y ? $bos : null),
                    _srSayfa('Kişi Bazında', '', [['Personel', 'm', 20], ['Departman', 'm', 14], ['Öğün', 's', 8, 'top'], ['Tutar', 'tl', 12, 'top bar']], $k, $bos),
                    _srSayfa('Gün Bazında', '', [['Tarih', 't', 11], ['Öğün', 's', 8, 'top'], ['Tutar', 'tl', 12, 'top bar']], $gun, $bos),
                ];

            // ---------------- BELGE & UYUM ----------------
            case 'belge':
                $u = _ikrBelgeUyari($sid); $hij = [];
                $bl = _ikrBelgeler($sid, 'personel_belge', 'belge_tarihi', 'baslik'); $eg = _ikrBelgeler($sid, 'personel_egitim', 'egitim_tarihi', 'konu'); $bugun = date('Y-m-d');
                foreach ($aktif as $p) {
                    $hb = array_filter($bl, fn ($b) => $b['pid'] === $p->id && (in_array($b['tip'], ['hijyen'], true) || stripos($b['baslik'], 'hijyen') !== false) && (!$b['son'] || $b['son'] >= $bugun));
                    $he = array_filter($eg, fn ($b) => $b['pid'] === $p->id && stripos($b['baslik'], 'hijyen') !== false && (!$b['son'] || $b['son'] >= $bugun));
                    $sg = array_filter($bl, fn ($b) => $b['pid'] === $p->id && $b['tip'] === 'saglik' && (!$b['son'] || $b['son'] >= $bugun));
                    if (!$hb && !$he || !$sg) $hij[] = [$p->ad, $p->dep, ($hb || $he) ? 'Var' : 'YOK', $sg ? 'Var' : 'YOK'];
                }
                return [
                    $pano([_srKpi('Süresi geçmiş', count(array_filter($u, fn ($x) => $x[5] < 0)), 'sayi', 'DC2626'), _srKpi('30 gün içinde dolacak', count(array_filter($u, fn ($x) => $x[5] >= 0)), 'sayi', 'D97706'),
                        _srKpi('Hijyen belgesi / eğitimi yok', count(array_filter($hij, fn ($x) => $x[2] === 'YOK')), 'sayi', 'B91C1C'), _srKpi('Geçerli sağlık raporu yok', count(array_filter($hij, fn ($x) => $x[3] === 'YOK')), 'sayi', '991B1B')], [], !$aktif ? _ikrBos('personel', $dn) : null),
                    _ikrSayfaBelge($u, _ikrBos('belge', $dn)),
                    _srSayfa('Hijyen & Sağlık Eksikleri', 'Gıda ile çalışan personelde hijyen eğitimi belgesi ve sağlık raporu yasal zorunluluktur. Belge türü "hijyen" / "sağlık" ya da adında "hijyen" geçen geçerli kayıt aranır (İK Sicil > Belgeler / Eğitimler).', [['Personel', 'm', 22], ['Departman', 'm', 16], ['Hijyen Belgesi', 'm', 13], ['Sağlık Raporu', 'm', 13]], $hij, 'Tüm aktif personelde geçerli hijyen belgesi ve sağlık raporu var.'),
                ];
            case 'zimmet':
                $z = []; if (_srTablo('personel_zimmet')) { try { $z = DB::table('personel_zimmet')->where('sube_id', $sid)->where('durum', 'teslim')->get()->all(); } catch (\Throwable $e) {} }
                $sat = []; foreach ($z as $r) { $p = $per[(int) $r->personel_id] ?? null; if (!$p) continue; $sat[] = [$p->ad . ($p->aktif ? '' : ' (AYRILDI)'), $p->dep, $r->urun, (int) $r->adet, substr((string) $r->teslim_tarihi, 0, 10), $r->teslim_tarihi ? (int) round((time() - strtotime((string) $r->teslim_tarihi)) / 86400) : null, $p->aktif ? 'Çalışıyor' : 'Ayrıldı — iade alınmalı', (string) ($r->not ?? '')]; }
                usort($sat, fn ($a, $b) => [str_contains($b[6], 'Ayrıldı'), $b[5]] <=> [str_contains($a[6], 'Ayrıldı'), $a[5]]);
                return [
                    $pano([_srKpi('Açık zimmet', count($sat), 'sayi', '15803D'), _srKpi('Zimmetli kişi', count(array_unique(array_column($sat, 0))), 'sayi', '166534'), _srKpi('Ayrılan personelde kalan', count(array_filter($sat, fn ($x) => str_contains($x[6], 'Ayrıldı'))), 'sayi', 'DC2626')], [], !$sat ? _ikrBos('zimmet', $dn) : null),
                    _srSayfa('Açık Zimmetler', 'Teslim edilmiş, iade alınmamış zimmetler; ayrılan personeldekiler önce.', [['Personel', 'm', 22], ['Departman', 'm', 14], ['Zimmet', 'm', 20], ['Adet', 's', 6, 'top'], ['Teslim', 't', 11], ['Gün', 's', 7], ['Durum', 'm', 22], ['Not', 'm', 20]], $sat, _ikrBos('zimmet', $dn)),
                ];
            case 'ozluk':
                $alan = ['tc' => 'TC kimlik', 'giris' => 'İşe giriş tarihi', 'dogum' => 'Doğum tarihi', 'acil' => 'Acil durum telefonu', 'iban' => 'IBAN', 'telefon' => 'Telefon'];
                $sat = []; $say = array_fill_keys(array_values($alan), 0);
                foreach ($aktif as $p) { $eks = []; foreach ($alan as $k => $ad) if (empty($p->{$k})) { $eks[] = $ad; $say[$ad]++; } if ($p->maas <= 0) { $eks[] = 'Maaş'; $say['Maaş'] = ($say['Maaş'] ?? 0) + 1; } if ($eks) $sat[] = [$p->ad, $p->dep, count($eks), implode(', ', $eks)]; }
                usort($sat, fn ($a, $b) => $b[2] <=> $a[2]); arsort($say); $say = array_filter($say);
                return [
                    $pano([_srKpi('Aktif personel', count($aktif), 'sayi', '166534'), _srKpi('Bilgisi eksik', count($sat), 'sayi', 'D97706'), $kpiY('Tam dolu oranı', count($aktif) ? round((count($aktif) - count($sat)) / count($aktif) * 100, 1) : null, '15803D')],
                        [_srBlok('En çok eksik alan', 'bar', 'D97706', [['Alan', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($say), array_values($say)))], !$aktif ? _ikrBos('personel', $dn) : (!$sat ? _ikrBos('ozluk', $dn) : null)),
                    _srSayfa('Eksik Özlük Bilgisi', 'İnsan Kaynakları > Personel & Maaş ya da İK Sicil\'den tamamlanır. Kıdem, izin hakkı ve devir raporları işe giriş tarihine dayanır.', [['Personel', 'm', 22], ['Departman', 'm', 14], ['Eksik Sayısı', 's', 10, 'bar'], ['Eksik Alanlar', 'm', 50]], $sat, _ikrBos('ozluk', $dn)),
                ];

            // ---------------- İŞE ALIM ----------------
            case 'aday':
                $tum = _ikrAdaylar($sid); $ad = array_values(array_filter($tum, fn ($a) => substr((string) ($a['created_at'] ?? ''), 0, 10) >= $bas && substr((string) ($a['created_at'] ?? ''), 0, 10) <= $bit));
                $dAd = function_exists('_ikAdayDurumlar') ? _ikAdayDurumlar() : []; $dur = []; foreach (array_keys($dAd ?: ['yeni' => 1]) as $k) $dur[$dAd[$k] ?? $k] = 0;
                foreach ($ad as $a) { $k = $dAd[$a['durum']] ?? $a['durum']; $dur[$k] = ($dur[$k] ?? 0) + 1; }
                $kay = collect($ad)->groupBy(fn ($a) => $a['kaynak'] ?: 'Bilinmiyor')->map(fn ($g, $k) => [$k, $g->count(), $g->where('durum', 'islendi')->count()])->sortByDesc(1)->values()->all();
                $poz = collect($ad)->groupBy(fn ($a) => $a['pozisyon'] ?: 'Belirtilmemiş')->map(fn ($g, $k) => [$k, $g->count(), $g->where('durum', 'islendi')->count(), round($g->avg('puan') ?? 0, 1) ?: null])->sortByDesc(1)->values()->all();
                $bos = !$d['aday'] ? _ikrBos('aday_yok', $dn) : _ikrBos('aday', $dn);
                return [
                    $pano([_srKpi('Gelen aday', count($ad), 'sayi', 'DB2777'), _srKpi('Mülakata çağrılan', count(array_filter($ad, fn ($a) => in_array($a['durum'], ['mulakat', 'teklif', 'islendi'], true))), 'sayi', 'BE185D'),
                        _srKpi('İşe alınan', count(array_filter($ad, fn ($a) => $a['durum'] === 'islendi')), 'sayi', '059669'), $kpiY('Adaydan işe alım', count($ad) ? round(count(array_filter($ad, fn ($a) => $a['durum'] === 'islendi')) / count($ad) * 100, 1) : null, '9D174D'),
                        _srKpi('Bekleyen (yeni + inceleme)', count(array_filter($ad, fn ($a) => in_array($a['durum'], ['yeni', 'inceleme'], true))), 'sayi', 'D97706')],
                        [_srBlok('Aday hunisi', 'col', 'DB2777', [['Aşama', 'm'], ['Aday', 's'], ['', 'm']], array_map(fn ($a, $v) => [$a, $v, ''], array_keys($dur), array_values($dur))),
                         _srBlok('Kaynaklar', 'pie', 'BE185D', [['Kaynak', 'm'], ['Aday', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $kay))], !$ad ? $bos : null),
                    _srSayfa('Kaynağa Göre', 'Adayın geldiği kanal ve o kanaldan işe alınan.', [['Kaynak', 'm', 18], ['Aday', 's', 8, 'top bar'], ['İşe Alınan', 's', 10, 'top']], $kay, $bos),
                    _srSayfa('Pozisyona Göre', 'Ortalama puan: aday kartında verilen 1–5 puan.', [['Pozisyon', 'm', 22], ['Aday', 's', 8, 'top bar'], ['İşe Alınan', 's', 10, 'top'], ['Ort. Puan', 's', 9]], $poz, $bos),
                    _srSayfa('Adaylar', '', [['Başvuru', 't', 11], ['Ad', 'm', 20], ['Pozisyon', 'm', 16], ['Kaynak', 'm', 10], ['Aşama', 'm', 12], ['Puan', 's', 6]], array_map(fn ($a) => [substr((string) $a['created_at'], 0, 10), $a['ad'], $a['pozisyon'], $a['kaynak'], $dAd[$a['durum']] ?? $a['durum'], $a['puan']], $ad), $bos),
                ];
            case 'ise_alim':
                $tum = _ikrAdaylar($sid); $sat = []; $sure = [];
                foreach ($tum as $a) {
                    if ($a['durum'] !== 'islendi') continue;
                    $p = $per[(int) ($a['personel_id'] ?? 0)] ?? null; $alim = !empty($a['ise_alindi_at']) ? substr((string) $a['ise_alindi_at'], 0, 10) : ($p ? _ikrBaslangic($p) : null);
                    if (!$alim || $alim < $bas || $alim > $bit) continue;
                    $gun = !empty($a['ise_alindi_at']) ? (int) round((strtotime((string) $a['ise_alindi_at']) - strtotime((string) $a['created_at'])) / 86400) : null; if ($gun !== null) $sure[] = $gun;
                    $sat[] = [$a['ad'], $a['pozisyon'], $a['kaynak'], substr((string) $a['created_at'], 0, 10), $alim, $gun, $p ? ($p->aktif ? 'Çalışıyor (' . _ikrKidemMetin(_ikrKidemAy($p, date('Y-m-d'))) . ')' : 'Ayrıldı (' . $p->cikis . ')') : 'Personel kaydı silinmiş'];
                }
                return [
                    $pano([_srKpi('İşe alınan', count($sat), 'sayi', 'BE185D'), _srKpi('Ortalama işe alım süresi (gün)', $sure ? round(array_sum($sure) / count($sure), 1) : 'Ölçüm yok', $sure ? 'sayi' : 'metin', '9D174D'),
                        _srKpi('Hâlâ çalışan', count(array_filter($sat, fn ($x) => str_starts_with($x[6], 'Çalışıyor'))), 'sayi', '059669'), _srKpi('Ayrılan', count(array_filter($sat, fn ($x) => str_starts_with($x[6], 'Ayrıldı'))), 'sayi', 'DC2626')], [],
                        !$sat ? (!$d['aday'] ? _ikrBos('aday_yok', $dn) : _ikrBos('alim', $dn)) : (!$sure ? 'İşe alım süresi bu özellik eklendikten sonra işe alınanlarda ölçülür (başvuru → "İşe Al" tarihi).' : null)),
                    _srSayfa('İşe Alınanlar', 'Süre = başvuru tarihinden "İşe Al"a basılan güne. Eski kayıtlarda bu tarih tutulmadığı için boş görünür.', [['Ad', 'm', 20], ['Pozisyon', 'm', 16], ['Kaynak', 'm', 10], ['Başvuru', 't', 11], ['İşe Alım', 't', 11], ['Süre (gün)', 's', 9], ['Şu An', 'm', 26]], $sat, !$d['aday'] ? _ikrBos('aday_yok', $dn) : _ikrBos('alim', $dn)),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_ikrAiBaglam')) {
    function _ikrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $per = _ikrPersonel($sid); $aktif = array_filter($per, fn ($p) => $p->aktif);
        $ml = _ikrMaliyet($sid, $bas, $bit); $mlo = _ikrMaliyet($sid, $dn['obas'], $dn['obit']); $c = _ikrCiro($sid, $bas, $bit); $co = _ikrCiro($sid, $dn['obas'], $dn['obit']);
        $sa = _ikrSaat($sid, $bas, $bit); $sao = _ikrSaat($sid, $dn['obas'], $dn['obit']); $dv = _ikrDevam($sid, $bas, $bit); $s = _ikrDevamSay($dv); $dvr = _ikrDevir($sid, $bas, $bit);
        $iz = _ikrIzinler($sid, $bas, $bit); $fz = array_filter(_ikrHaftalik($sid, $bas, $bit), fn ($x) => $x['fazla'] > 0); $g = _ikrGarson($sid, $bas, $bit); $seb = _ikrAyrilisSebepleri();
        $av = array_values(array_filter(_ikrHareket($sid, $bas, $bit), fn ($h) => $h['tur'] === 'avans'));
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _ikrVeriDurumu($sid),
            'kadro' => ['aktif' => count($aktif), 'departman' => collect($aktif)->groupBy('dep')->map->count()->all(), 'giren' => count($dvr['giren']), 'ayrilan' => count($dvr['ayrilan']), 'devir_orani_yuzde' => $dvr['oran'], 'devir_yillik_yuzde' => $dvr['yillik'],
                'ayrilis_sebepleri' => collect($dvr['ayrilan'])->groupBy(fn ($p) => $p->sebep ? ($seb[$p->sebep] ?? $p->sebep) : 'girilmemiş')->map->count()->all()],
            'iscilik' => ['maliyet_tl' => $ml['toplam'], 'onceki_maliyet_tl' => $mlo['toplam'], 'ciro_tl' => $c, 'onceki_ciro_tl' => $co, 'oran_yuzde' => $c > 0 ? round($ml['toplam'] / $c * 100, 1) : null, 'onceki_oran_yuzde' => $co > 0 ? round($mlo['toplam'] / $co * 100, 1) : null,
                'isveren_carpani' => $ml['carpan'], 'maasi_girilmemis_kisi' => count(array_filter($aktif, fn ($p) => $p->maas <= 0)), 'departman' => array_map(fn ($x) => ['departman' => $x[0], 'tl' => $x[2], 'saat' => $x[4]], _ikrDepMaliyet($ml))],
            'verim' => ['calisilan_saat' => $sa, 'onceki_saat' => $sao, 'saat_basi_ciro' => $sa > 0 ? round($c / $sa, 2) : null, 'onceki_saat_basi_ciro' => $sao > 0 ? round($co / $sao, 2) : null],
            'devam' => $s + ['en_cok_gec' => collect($dv)->filter(fn ($x) => _ikrGecMi($x['durum']))->groupBy('ad')->map(fn ($g) => ['kez' => $g->count(), 'dk' => $g->sum('gec')])->sortByDesc('kez')->take(6)->all(),
                'gelmeyenler' => collect($dv)->where('durum', 'gelmedi')->groupBy('ad')->map->count()->sortDesc()->take(6)->all(), 'gec_tolerans_dk' => _ikrAyar($sid)['tolerans']],
            'fazla_mesai' => ['toplam_saat' => round(array_sum(array_column($fz, 'fazla')), 1), 'kisi' => collect($fz)->groupBy('ad')->map(fn ($g) => round($g->sum('fazla'), 1))->sortDesc()->take(6)->all()],
            'izin' => ['gun_tur' => collect($iz)->groupBy(fn ($z) => _ikrIzinTipAd($z['tip']))->map(fn ($g) => $g->sum('donem_gun'))->all(), 'bekleyen_talep' => count(_ikrIzinler($sid, '2000-01-01', '2100-01-01', 'talep'))],
            'satis_performansi' => array_map(fn ($x) => ['personel' => $x['ad'], 'ciro' => $x['ciro'], 'ort_hesap' => $x['ort'], 'saat_basi' => $x['saat_basi'], 'iptal_tl' => $x['iptal_tl'], 'ikram_tl' => $x['ikram_tl']], array_slice($g, 0, 8)),
            'avans' => ['toplam_tl' => round(array_sum(array_column($av, 'tutar')), 2), 'kisi' => collect($av)->groupBy('pid')->map(fn ($l, $pid) => ['personel' => $per[$pid]->ad, 'tl' => round($l->sum('tutar'), 2), 'kez' => $l->count()])->sortByDesc('tl')->take(5)->values()->all()],
            'uyum' => ['suresi_gecen_belge' => count(array_filter(_ikrBelgeUyari($sid, 0))), 'otuz_gunde_dolacak' => count(_ikrBelgeUyari($sid)) - count(_ikrBelgeUyari($sid, 0)),
                'ayrilan_personelde_zimmet' => _srTablo('personel_zimmet') ? (int) DB::table('personel_zimmet')->where('sube_id', $sid)->where('durum', 'teslim')->whereIn('personel_id', array_keys(array_filter($per, fn ($p) => !$p->aktif)))->count() : 0,
                'ozluk_eksik_kisi' => count(array_filter($aktif, fn ($p) => !$p->tc || !$p->giris || !$p->acil))],
            'aday' => ['donemde_gelen' => count(array_filter(_ikrAdaylar($sid), fn ($a) => substr((string) ($a['created_at'] ?? ''), 0, 10) >= $bas && substr((string) ($a['created_at'] ?? ''), 0, 10) <= $bit))]];
    }
    function _ikrAiNabiz($sid, array $dn)
    {
        $b = _ikrAiBaglam($sid, $dn); $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺'; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $or = $b['iscilik']['oran_yuzde']; $sp = $b['verim']['saat_basi_ciro']; $spo = $b['verim']['onceki_saat_basi_ciro']; $dg = $sp && $spo ? _srYuzde($sp, $spo) : null;
        return [
            ['renk' => '#2563EB', 'kat' => 'devir', 'ikon' => 'groups', 'etiket' => 'Aktif kadro', 'deger' => (string) $b['kadro']['aktif'], 'alt' => $b['kadro']['giren'] . ' giren · ' . $b['kadro']['ayrilan'] . ' ayrılan', 'sinif' => $b['kadro']['ayrilan'] > $b['kadro']['giren'] ? 'turuncu' : ''],
            ['renk' => '#059669', 'kat' => 'maliyet', 'ikon' => 'percent', 'etiket' => 'İşçilik oranı', 'deger' => $y($or), 'alt' => $tl($b['iscilik']['maliyet_tl']) . ' işçilik maliyeti', 'sinif' => $or !== null && $or > 35 ? 'kirmizi' : 'yesil'],
            ['renk' => '#0D9488', 'kat' => 'maliyet', 'ikon' => 'speed', 'etiket' => 'Saat başı ciro', 'deger' => $sp !== null ? $tl($sp) : '—', 'alt' => $dg !== null ? '%' . number_format(abs($dg), 1, ',', '.') . ($dg >= 0 ? ' arttı' : ' düştü') : ($sp === null ? 'QR mesai yok' : 'Önceki dönemde veri yok'), 'sinif' => $dg !== null ? ($dg >= 0 ? 'yesil' : 'kirmizi') : '', 'ok' => $dg !== null ? ($dg >= 0 ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#DC2626', 'kat' => 'devam', 'ikon' => 'alarm', 'etiket' => 'Gelmeyen / geç kalan', 'deger' => $b['devam']['gelmedi'] . ' / ' . $b['devam']['gec'], 'alt' => $b['devam']['devam'] !== null ? 'Devam oranı ' . $y($b['devam']['devam']) : 'Vardiya planı yok', 'sinif' => $b['devam']['gelmedi'] ? 'kirmizi' : 'yesil'],
            ['renk' => '#C2410C', 'kat' => 'devam', 'ikon' => 'more_time', 'etiket' => 'Fazla mesai', 'deger' => number_format($b['fazla_mesai']['toplam_saat'], 1, ',', '.') . ' saat', 'alt' => count($b['fazla_mesai']['kisi']) . ' kişi 45 saati aştı', 'sinif' => $b['fazla_mesai']['toplam_saat'] > 0 ? 'turuncu' : ''],
            ['renk' => '#16A34A', 'kat' => 'uyum', 'ikon' => 'verified', 'etiket' => 'Süresi geçen belge', 'deger' => (string) $b['uyum']['suresi_gecen_belge'], 'alt' => $b['uyum']['otuz_gunde_dolacak'] . ' belge 30 gün içinde dolacak', 'sinif' => $b['uyum']['suresi_gecen_belge'] ? 'kirmizi' : ''],
        ];
    }
}

// ---------- Ayar kartı (Maliyet / Devam / Dönemsel sayfalarının üstünde) ----------
if (!function_exists('_ikrUst')) {
    function _ikrUst($kat, $sid)
    {
        if (!in_array($kat, ['maliyet', 'devam', 'donemsel'], true) || !_ikrTam()) return '';
        $a = _ikrAyar($sid); $e = fn ($x) => htmlspecialchars((string) $x, ENT_QUOTES);
        return '<form method="POST" action="/ik-raporlar/ayar" style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;padding:11px 16px;border-radius:16px;background:var(--card);border:1px solid var(--line);font-size:12.5px;color:var(--sub2)">'
            . '<input type="hidden" name="_token" value="' . $e(csrf_token()) . '"><input type="hidden" name="geri" value="' . $e(request()->getRequestUri()) . '">'
            . '<span style="display:inline-flex;align-items:center;gap:5px;font-weight:900;color:var(--ink)"><span class="material-icons-outlined" style="font-size:18px;color:#2563EB">tune</span>Hesap ayarları</span>'
            . '<label style="display:inline-flex;align-items:center;gap:6px" title="Maaş × çarpan = işverene gerçek maliyet (SGK işveren payı vb.). Brüt maaş giriyorsanız yaklaşık 1,18; net maaş giriyorsanız yaklaşık 1,40 (asgari ücret civarı). Kesin oran için muhasebecinize sorun.">İşveren maliyet çarpanı <input name="carpan" value="' . $e(number_format($a['carpan'], 2, ',', '')) . '" inputmode="decimal" style="width:62px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:5px 7px;color:var(--ink)"></label>'
            . '<label style="display:inline-flex;align-items:center;gap:6px" title="Vardiya başlangıcından bu kadar dakika sonrasına kadar giriş geç sayılmaz; erken çıkış için de aynı.">Geç kalma toleransı <input name="tolerans" type="number" min="0" max="60" value="' . (int) $a['tolerans'] . '" style="width:56px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:5px 7px;color:var(--ink)"> dk</label>'
            . '<button type="submit" style="border:0;border-radius:9px;padding:7px 13px;font-weight:800;background:#2563EB;color:#fff;cursor:pointer">Kaydet</button>'
            . '<span style="flex-basis:100%;font-size:11.5px;color:var(--sub)">Maliyetler personel kartındaki maaş × çarpan ile hesaplanır (resmi bordro değildir). Çarpan 1 ise maaş olduğu gibi alınır.</span></form>';
    }
}

// ---------- Modül kaydı ----------
_drKaydet('ik', [
    'ad' => 'İK', 'baslik' => 'İK Raporları', 'yol' => '/ik-raporlar', 'grup' => 'İnsan Kaynakları', 'depKodlari' => ['ik'],
    'erisim' => fn ($p) => !empty($p->departman_sorumlu), // diğer departman sorumluları: yalnız kendi ekibi (katIzin ile daraltılır)
    'katIzin' => fn ($p) => _ikrTam($p) ? null : ['devam', 'vardiya', 'izin', 'performans', 'uyum'],
    'ensure' => '_ikrEnsure', 'katalog' => '_ikrKatalog', 'kategoriler' => '_ikrKategoriler', 'veri' => '_ikrVeriDurumu', 'rapor' => '_ikrRapor',
    'ust' => fn ($kat, $sid) => _ikrUst($kat, $sid),
    'olcek' => ['kucuk' => ['donemsel', 'maliyet', 'devam', 'ai'], 'orta' => ['izin', 'devir', 'performans', 'bordro']],
    'modAd' => ['personel' => 'Personel', 'maas' => 'Maaş bilgisi', 'giris' => 'İşe giriş tarihi', 'cikis' => 'Ayrılış kaydı', 'mesai' => 'QR mesai', 'vardiya' => 'Vardiya planı', 'izin' => 'İzin',
        'hareket' => 'Avans / prim / kesinti', 'avans' => 'Avans', 'satis' => 'Satış (adisyon)', 'adim' => 'Adım sayısı', 'iptal' => 'İptal / ikram', 'kds' => 'KDS aşçı kaydı', 'recete' => 'Reçete',
        'belge' => 'Belgeler', 'egitim' => 'Eğitimler', 'zimmet' => 'Zimmet', 'aday' => 'Aday havuzu', 'yemek' => 'Personel yemeği'],
    'modLink' => ['personel' => ['/personel', 'Personel & Maaş'], 'maas' => ['/personel', 'Personel kartında maaş ve maaş tipi'], 'giris' => ['/personel', 'Personel kartında işe başlama tarihi'],
        'cikis' => ['/personel', 'Personeli "Pasif" yapınca ayrılış tarihi ve sebebi'], 'mesai' => [null, 'Personelin telefonundan QR okutarak mesai başlatması'], 'vardiya' => ['/ik-vardiya', 'Vardiya Planlama'],
        'izin' => ['/ik-izin', 'İzin Yönetimi'], 'hareket' => ['/personel', 'Personel > avans / prim / kesinti'], 'avans' => ['/personel', 'Personel > avans'], 'satis' => ['/pos', 'POS / adisyon'],
        'adim' => [null, 'Garson uygulamasında adım izni'], 'iptal' => [null, 'POS\'ta iptal / ikram'], 'kds' => ['/mutfak', 'KDS\'ye kendi PIN\'iyle girip Başla / Hazır'], 'recete' => ['/recete', 'Reçeteler'],
        'belge' => ['/ik-sicil', 'İK Sicil > Belgeler'], 'egitim' => ['/ik-sicil', 'İK Sicil > Eğitimler'], 'zimmet' => ['/ik-sicil', 'İK Sicil > Zimmet'], 'aday' => ['/ik-aday', 'İşe Alım / Aday Havuzu'], 'yemek' => ['/personel-yemek', 'Personel Yemeği']],
    'ai' => [
        'gerekli' => ['maas', 'mesai', 'vardiya', 'izin', 'satis', 'giris'],
        'hero' => ['Ekibinin dönemini 20 saniyede özetleyeyim.', 'Kadro ve devir, işçilik maliyeti ve cirodaki payı, saat başı ciro, devam ve geç kalma, fazla mesai, izinler, satış performansı, avans ve belge uyumunu okur.'],
        'sorular' => ['İşçilik maliyetim neden yükseldi?', 'Hangi saatlerde fazla eleman çalıştırıyorum?', 'Kim sık geç kalıyor, ne yapmalıyım?', 'Ayrılma riski olan kim var?', 'Fazla mesai yasal sınıra yaklaşan var mı?', 'Hangi garson daha verimli?'],
        'adimlar' => ['Kadro ve mesai kayıtları okunuyor…', 'Önceki dönemle karşılaştırılıyor…', 'İşçilik maliyeti ve ciro hesaplanıyor…', 'Devam, geç kalma ve fazla mesai inceleniyor…', 'İzin, avans ve belgeler kontrol ediliyor…', 'Öneriler yazılıyor…'],
        'selam' => 'insan kaynakları',
        'sys' => 'Sen bir restoranın insan kaynaklarını patron ve İK sorumlusu için analiz eden deneyimli bir İK ve işgücü planlama danışmanısın. Türkiye iş mevzuatını bil: haftalık 45 saat, yıllık 270 saat fazla mesai sınırı, yıllık izin 1-5 yıl 14 / 5-15 yıl 20 / 15+ yıl 26 gün. '
            . 'İşçilik oranı = işçilik maliyeti ÷ ciro (restoranda hedef %25-35); maliyetler işletmenin girdiği maaş × işveren çarpanı ile hesaplanır, resmi bordro değildir. Kişilerin sağlığı ya da özel hayatı hakkında yorum yapma; performans yorumunu yalnız veriye dayandır, suçlayıcı dil kullanma.',
        'baglam' => '_ikrAiBaglam',
        'veriVar' => fn ($b) => $b['kadro']['aktif'] > 0,
        'nabiz' => '_ikrAiNabiz',
    ],
]);

// Ayar kaydı (tam erişimli)
Route::post('/ik-raporlar/ayar', function (Request $r) {
    if (!_drErisim('ik') || !_ikrTam()) return redirect('/pos-giris');
    $sid = _webSubeId(); $c = (float) str_replace(',', '.', (string) $r->input('carpan', '1')); $t = (int) $r->input('tolerans', 5);
    if (function_exists('_subeAyarYaz')) { _subeAyarYaz($sid, 'ikr_isveren_carpan', (string) max(1, min(3, $c))); _subeAyarYaz($sid, 'ikr_gec_tolerans', (string) max(0, min(60, $t))); }
    $geri = (string) $r->input('geri', '/ik-raporlar'); if (!str_starts_with($geri, '/ik-raporlar')) $geri = '/ik-raporlar';
    return redirect($geri)->with('ok', 'İK rapor ayarları kaydedildi.');
});

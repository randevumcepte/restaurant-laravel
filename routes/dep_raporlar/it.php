<?php
// =====================================================================
// IT / BİLGİ İŞLEM RAPORLAR & ANALİZLER — /it-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Restoran IT ekibinin raporladığı başlıklar: destek talebi hacmi ve çözüm süresi (MTTR / SLA), açık talep yaşlanması, varlık envanteri ve
// son görülme, sürüm uyumu, lisans / SSL / domain yenileme takvimi, yedek uyumu (son yedeğin yaşı), KVKK kayıtları, erişim denetimi
// (pasif hesapta açık oturum, ortak / zayıf PIN, uzun süre girmeyen hesap), giriş ve başarısız deneme, mesai dışı işlem, yetki değişiklikleri,
// AI kullanım kotası ve AI eylem denetimi, entegrasyon sağlığı (WhatsApp teslim, e-belge / mali fiş hataları, çevrimdışı satış senkronu).
// Veri: it_kayitlari (ariza / lisans / yedek / kvkk / bakim), departman_isler (departman_kod='it'), cihazlar, cihaz_tokenlari, ayarlar (yazıcı),
//       demirbaslar + demirbas_bakim (BT donanımı), personeller, aktivite_loglari, ai_eylem_log, patron_ai_tuketim, wa_hatlar, wa_mesaj_loglari,
//       e_faturalar, adisyonlar.uuid (uygulamadan senkronla gelen satış).
// Faz 2 (bundan sonrasını ölçer): it_kayitlari.kapanis_at (_itrKapanisDamga → arıza çözüm süresi), başarısız PIN denemesi kaydı (_itrGirisHata).
// Erişim: patron / müdür / süpervizör / IT sorumlusu / 'gor_it_rapor'.
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2 alanları ----------
if (!function_exists('_itrEnsure')) {
    function _itrEnsure()
    {
        if (cache()->get('itr_ensure_v1')) return;
        foreach (['_itKayitEnsure', '_departmanIsEnsure', '_restoCihazEnsure', '_aiEylemLogEnsure', '_patronAiTuketimEnsure'] as $f) if (function_exists($f)) { try { $f(); } catch (\Throwable $e) {} }
        try { \App\Http\Middleware\AktiviteLog::ensure(); } catch (\Throwable $e) {}
        try { if (Schema::hasTable('it_kayitlari') && !Schema::hasColumn('it_kayitlari', 'kapanis_at')) Schema::table('it_kayitlari', function ($t) { $t->timestamp('kapanis_at')->nullable(); }); } catch (\Throwable $e) {}
        cache()->put('itr_ensure_v1', 1, now()->addHours(12));
    }
    // Arıza kaydı kapatılınca kapanış zamanı (çözüm süresi raporu). Kullanım: ->update(_itrKapanisDamga(['durum' => 'kapandi']))
    function _itrKapanisDamga(array $upd)
    {
        try { _itrEnsure(); if (($upd['durum'] ?? '') === 'kapandi' && Schema::hasColumn('it_kayitlari', 'kapanis_at')) $upd['kapanis_at'] = now(); } catch (\Throwable $e) {}
        return $upd;
    }
    // Hatalı PIN denemesi: hangi işletmeye ait olduğu, aynı IP'den son 30 günde başarılı giriş yapılan şubeden bulunur (terminal / ağ aynı)
    function _itrGirisHata($kanal = 'Web')
    {
        try {
            \App\Http\Middleware\AktiviteLog::ensure(); $ip = request()->ip();
            $sid = DB::table('aktivite_loglari')->where('ip', $ip)->where('kategori', 'giris')->where('created_at', '>=', now()->subDays(30))->orderByDesc('id')->value('sube_id');
            DB::table('aktivite_loglari')->insert(['sube_id' => $sid, 'personel_id' => null, 'personel_ad' => null, 'rol' => null, 'kategori' => 'giris_hata', 'aksiyon' => 'Hatalı PIN denemesi (' . $kanal . ')',
                'yol' => ltrim(request()->path(), '/'), 'metod' => request()->method(), 'ip' => $ip, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_itrArizalar')) {
    function _itrGun($x) { return $x ? substr((string) $x, 0, 10) : null; }
    function _itrGunler($bas, $bit) { $o = []; $son = min($bit, date('Y-m-d')); for ($t = strtotime($bas); $t <= strtotime($son); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _itrKalan($t) { return $t ? (int) round((strtotime($t) - strtotime(date('Y-m-d'))) / 86400) : null; }
    function _itrOncAd($o) { return ['yuksek' => 'Yüksek', 'orta' => 'Orta', 'dusuk' => 'Düşük'][$o] ?? ($o ?: 'Orta'); }
    function _itrSla($o) { return ['yuksek' => 4, 'orta' => 24, 'dusuk' => 72][$o] ?? 24; } // hedef çözüm süresi (saat)
    function _itrPersonelAd($sid) { static $c = []; if (!isset($c[$sid])) { try { $c[$sid] = DB::table('personeller')->where('sube_id', $sid)->pluck('ad', 'id')->all(); } catch (\Throwable $e) { $c[$sid] = []; } } return $c[$sid]; }
    // Arıza / destek talepleri: IT kayıt defteri (it_kayitlari tip=ariza) + IT departman iş ekranı (departman_isler kod=it, tip=ariza)
    function _itrArizalar($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $o = []; $simdi = time();
        $ekle = function ($kaynak, $baslik, $cihaz, $onc, $acilis, $kapanis, $durum, $kim, $not) use (&$o, $simdi) {
            $a = strtotime((string) $acilis); $k = $kapanis ? strtotime((string) $kapanis) : null; $sure = $k && $k >= $a ? round(($k - $a) / 3600, 1) : null;
            $o[] = ['kaynak' => $kaynak, 'baslik' => $baslik, 'cihaz' => $cihaz ?: '—', 'oncelik' => $onc ?: 'orta', 'acilis' => date('Y-m-d', $a), 'acilis_saat' => date('H:i', $a), 'kapanis' => $k ? date('Y-m-d', $k) : null,
                'durum' => $durum, 'sure' => $sure, 'yas' => $durum === 'acik' ? round(($simdi - $a) / 86400, 1) : null, 'sla' => $sure !== null ? ($sure <= _itrSla($onc) ? 'Hedefte' : 'Aştı') : ($durum === 'acik' && ($simdi - $a) / 3600 > _itrSla($onc) ? 'Aştı (açık)' : '—'),
                'kim' => $kim ?: '—', 'not' => (string) $not];
        };
        $ac = fn ($q, $kol) => $q->where(function ($w) use ($bas, $bit, $kol) { $w->whereBetween($kol, [$bas . ' 00:00:00', $bit . ' 23:59:59']); });
        if (_srTablo('it_kayitlari')) {
            $kap = _srKolon('it_kayitlari', 'kapanis_at');
            try { $rows = DB::table('it_kayitlari')->where('sube_id', $sid)->where('tip', 'ariza')->where(function ($w) use ($bas, $bit) { $w->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orWhere(fn ($w2) => $w2->where('durum', 'acik')->where('created_at', '<=', $bit . ' 23:59:59')); })->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) $ekle('IT kayıt defteri', (string) $r->baslik, (string) ($r->deger ?? ''), (string) ($r->oncelik ?? 'orta'), $r->created_at, $kap ? ($r->kapanis_at ?? null) : null, $r->durum === 'acik' ? 'acik' : 'cozuldu', (string) ($r->personel_ad ?? ''), $r->not ?? '');
        }
        if (_srTablo('departman_isler')) {
            try { $rows = DB::table('departman_isler')->where('sube_id', $sid)->where('departman_kod', 'it')->where('tip', 'ariza')->where(function ($w) use ($bas, $bit) { $w->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orWhere(fn ($w2) => $w2->whereIn('durum', ['acik', 'yapiliyor'])->where('created_at', '<=', $bit . ' 23:59:59')); })->get(); } catch (\Throwable $e) { $rows = []; }
            foreach ($rows as $r) $ekle('IT iş ekranı', (string) $r->baslik, (string) ($r->deger ?? ''), (string) ($r->oncelik ?? 'orta'), $r->created_at, $r->durum === 'tamam' ? $r->tamamlanan_at : null, in_array($r->durum, ['acik', 'yapiliyor'], true) ? 'acik' : ($r->durum === 'iptal' ? 'iptal' : 'cozuldu'), (string) ($r->atanan_ad ?: $r->olusturan_ad), $r->aciklama ?? '');
        }
        usort($o, fn ($a, $b) => strcmp($b['acilis'] . $b['acilis_saat'], $a['acilis'] . $a['acilis_saat']));
        return $c[$key] = $o;
    }
    function _itrDurumAd($d) { return ['acik' => 'Açık', 'cozuldu' => 'Çözüldü', 'iptal' => 'İptal'][$d] ?? $d; }
    function _itrKayit($sid, $tip)
    {
        if (!_srTablo('it_kayitlari')) return [];
        try { $rows = DB::table('it_kayitlari')->where('sube_id', $sid)->where('tip', $tip)->orderBy('tarih')->orderBy('id')->get(); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['baslik' => trim((string) $r->baslik), 'deger' => (string) ($r->deger ?? ''), 'tarih' => _itrGun($r->tarih) ?: _itrGun($r->created_at), 'son' => _itrGun($r->son_tarih), 'durum' => (string) $r->durum,
            'oncelik' => (string) ($r->oncelik ?? 'orta'), 'personel' => (string) ($r->personel_ad ?: '—'), 'not' => (string) ($r->not ?? '')];
        return $o;
    }
    // Lisans / domain / SSL / bakım takvimi: her kalemin en son kaydı
    function _itrTakvim($sid, $tip, $esik = 30)
    {
        $g = []; foreach (_itrKayit($sid, $tip) as $k) $g[mb_strtolower($k['baslik'] . '|' . $k['deger'])] = $k;
        $o = [];
        foreach ($g as $k) { $kal = _itrKalan($k['son']); $o[] = $k + ['kalan' => $kal, 'drm' => !$k['son'] ? 'Bitiş tarihi yok' : ($kal < 0 ? 'Süresi geçti' : ($kal <= $esik ? 'Yaklaşıyor' : 'Geçerli'))]; }
        usort($o, fn ($a, $b) => ($a['kalan'] ?? 99999) <=> ($b['kalan'] ?? 99999));
        return $o;
    }
    // Yedekleme: kayıtlı yedek tarihleri; son yedeğin yaşı ve dönemdeki en uzun boşluk
    function _itrYedek($sid, $bas, $bit)
    {
        $t = array_values(array_unique(array_filter(array_column(_itrKayit($sid, 'yedek'), 'tarih')))); sort($t);
        $son = $t ? end($t) : null; $don = array_values(array_filter($t, fn ($x) => $x >= $bas && $x <= $bit));
        $nokta = array_merge([$bas], $don, [min($bit, date('Y-m-d'))]); $bosluk = 0; $bosBas = null;
        for ($i = 1; $i < count($nokta); $i++) { $g = (int) round((strtotime($nokta[$i]) - strtotime($nokta[$i - 1])) / 86400); if ($g > $bosluk) { $bosluk = $g; $bosBas = $nokta[$i - 1]; } }
        return ['tum' => $t, 'donem' => $don, 'son' => $son, 'yas' => $son ? (int) round((strtotime(date('Y-m-d')) - strtotime($son)) / 86400) : null, 'en_uzun' => $bosluk, 'en_uzun_bas' => $bosBas];
    }
    function _itrCihazlar($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('cihazlar')) return $c[$sid] = [];
        try { $rows = DB::table('cihazlar')->where('sube_id', $sid)->get(); } catch (\Throwable $e) { $rows = []; }
        $o = []; $simdi = time();
        foreach ($rows as $r) {
            $sg = $r->son_gorulme ? strtotime((string) $r->son_gorulme) : null; $sn = $sg ? $simdi - $sg : null;
            $durum = $sn === null ? 'Hiç bağlanmadı' : ($sn <= 120 ? 'Çevrimiçi' : ($sn <= 86400 ? 'Son 24 saatte' : ($sn <= 7 * 86400 ? '1-7 gün önce' : '7 günden eski')));
            $tip = mb_strtolower((string) ($r->tip ?? ''));
            $o[] = ['ad' => (string) ($r->ad ?: $r->kimlik), 'tip' => (string) ($r->tip ?: '—'), 'yazici' => in_array($tip, ['yazici', 'printer', 'okc', 'yazarkasa', 'cekmece'], true), 'platform' => (string) ($r->platform ?? ''), 'surum' => (string) ($r->surum ?? ''),
                'rol' => (string) ($r->rol ?? ''), 'ip' => (string) ($r->ip ?? ''), 'son' => $sg ? date('Y-m-d H:i', $sg) : '', 'gun' => $sn !== null ? round($sn / 86400, 1) : null, 'durum' => $durum,
                'manuel' => (int) ($r->manuel ?? 0) === 1, 'aktif' => (int) ($r->aktif ?? 1) === 1, 'marka' => (string) ($r->marka ?? ''), 'seri' => (string) ($r->seri_no ?? ''), 'ilk' => _itrGun($r->created_at)];
        }
        usort($o, fn ($a, $b) => [$a['yazici'], $b['son']] <=> [$b['yazici'], $a['son']]);
        return $c[$sid] = $o;
    }
    function _itrSurumKars($a, $b) { if (function_exists('_itSurumKarsilastir')) return _itSurumKarsilastir($a, $b); return version_compare(preg_replace('/[^0-9.]/', '', (string) $a), preg_replace('/[^0-9.]/', '', (string) $b)); }
    // Sürüm uyumu: platform başına en yeni sürüm ve geride kalan cihazlar (yazıcı / manuel cihaz hariç)
    function _itrSurum($sid)
    {
        $l = array_values(array_filter(_itrCihazlar($sid), fn ($x) => !$x['yazici'] && $x['surum'] !== '')); $en = [];
        foreach ($l as $x) { $p = $x['platform'] ?: '—'; if (!isset($en[$p]) || _itrSurumKars($en[$p], $x['surum']) < 0) $en[$p] = $x['surum']; }
        foreach ($l as &$x) { $x['en_yeni'] = $en[$x['platform'] ?: '—']; $x['guncel'] = _itrSurumKars($x['surum'], $x['en_yeni']) >= 0; } unset($x);
        return ['liste' => $l, 'en' => $en];
    }
    function _itrYazicilar($sid)
    {
        if (!_srTablo('ayarlar')) return [];
        try { $a = DB::table('ayarlar')->where('anahtar', 'like', 'yazici_' . $sid . '_%')->pluck('deger', 'anahtar')->all(); } catch (\Throwable $e) { return []; }
        $g = fn ($k) => $a['yazici_' . $sid . '_' . $k] ?? ''; $o = [];
        foreach ([['ip', 'Kasa / fiş yazıcısı'], ['mutfak_ip', 'Mutfak yazıcısı'], ['bar_ip', 'Bar yazıcısı'], ['izgara_ip', 'Izgara yazıcısı']] as [$k, $ad]) if (trim((string) $g($k)) !== '') $o[] = [$ad, $g($k), $k === 'ip' ? ($g('port') ?: '9100') : '9100', $k === 'ip' ? ($g('dar') === '1' ? '58 mm' : '80 mm') : '—'];
        return $o;
    }
    // BT donanımı (demirbaş kaydından): bilgisayar, tablet, yazıcı, modem, POS vb.
    function _itrDonanim($sid)
    {
        if (!_srTablo('demirbaslar')) return [];
        try { $rows = DB::table('demirbaslar')->where('sube_id', $sid)->get(); } catch (\Throwable $e) { return []; }
        $bak = []; if (_srTablo('demirbas_bakim')) { try { foreach (DB::table('demirbas_bakim')->whereIn('demirbas_id', $rows->pluck('id')->all() ?: [0])->get() as $b) { $bak[$b->demirbas_id]['n'] = ($bak[$b->demirbas_id]['n'] ?? 0) + 1; $bak[$b->demirbas_id]['tl'] = ($bak[$b->demirbas_id]['tl'] ?? 0) + (float) $b->maliyet; if ($b->tip === 'ariza') $bak[$b->demirbas_id]['a'] = ($bak[$b->demirbas_id]['a'] ?? 0) + 1; } } catch (\Throwable $e) {} }
        $durumAd = ['aktif' => 'Kullanımda', 'bakimda' => 'Bakımda', 'arizali' => 'Arızalı', 'kayip' => 'Kayıp', 'hurda' => 'Hurda']; $o = [];
        foreach ($rows as $r) {
            if (!preg_match('/bilgisayar|laptop|notebook|tablet|telefon|yazıcı|yazici|printer|modem|router|switch|access|kamera|nvr|dvr|sunucu|server|monitör|monitor|ekran|pos|yazarkasa|ökc|okc|barkod|tarayıcı|ups|el terminali|bilişim|it /iu', ($r->kategori ?? '') . ' ' . $r->ad)) continue;
            $gar = _itrGun($r->garanti_bitis ?? null);
            $o[] = ['ad' => (string) $r->ad, 'kategori' => (string) ($r->kategori ?: '—'), 'marka' => trim(($r->marka ?? '') . ' ' . ($r->model ?? '')) ?: '—', 'seri' => (string) ($r->seri_no ?? ''), 'lokasyon' => (string) ($r->lokasyon ?: '—'), 'durum' => $durumAd[$r->durum] ?? $r->durum, 'ham_durum' => (string) $r->durum,
                'alis' => _itrGun($r->alis_tarih), 'bedel' => (float) $r->alis_bedel, 'garanti' => $gar, 'garanti_kalan' => _itrKalan($gar), 'ariza' => (int) ($bak[$r->id]['a'] ?? 0), 'bakim' => (int) ($bak[$r->id]['n'] ?? 0), 'bakim_tl' => round($bak[$r->id]['tl'] ?? 0, 2)];
        }
        return $o;
    }
    // Aktivite / giriş kayıtları (AktiviteLog middleware + _personelLog + panel ayar logu)
    function _itrAktivite($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('aktivite_loglari')) return $c[$key] = [];
        try { $rows = DB::table('aktivite_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->limit(20000)->get(); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) { $t = strtotime((string) $r->created_at); $o[] = ['ts' => $t, 'tarih' => date('Y-m-d', $t), 'saat' => (int) date('G', $t), 'hm' => date('H:i', $t), 'pid' => (int) $r->personel_id, 'ad' => (string) ($r->personel_ad ?: ($r->personel_id ? '#' . $r->personel_id : '—')),
            'rol' => (string) ($r->rol ?? ''), 'kategori' => (string) ($r->kategori ?: 'diger'), 'aksiyon' => (string) ($r->aksiyon ?? ''), 'aciklama' => (string) ($r->aciklama ?? ''), 'yol' => (string) ($r->yol ?? ''), 'ip' => (string) ($r->ip ?? '')]; }
        return $c[$key] = $o;
    }
    function _itrKatAd($k) { return ['giris' => 'Giriş', 'giris_hata' => 'Hatalı giriş', 'guvenlik' => 'Hesap güvenliği', 'personel' => 'Personel / yetki', 'ayar' => 'Ayar', 'menu' => 'Menü', 'recete' => 'Reçete', 'stok' => 'Stok', 'satis' => 'Satış', 'cagri' => 'Garson çağrı', 'mesai' => 'Mesai', 'diger' => 'Diğer'][$k] ?? $k; }
    function _itrMesaiDisi($saat) { return $saat < 6; } // 00:00–05:59
    // Hesap güvenliği (anlık): PIN değeri ASLA gösterilmez, yalnız sayılır
    function _itrHesap($sid)
    {
        if (!_srTablo('personeller')) return ['kisi' => [], 'ortak_pin_grup' => 0];
        try { $per = DB::table('personeller')->where('sube_id', $sid)->get(); } catch (\Throwable $e) { $per = collect(); }
        $tok = []; if (_srTablo('cihaz_tokenlari')) { try { $tok = DB::table('cihaz_tokenlari')->where('sube_id', $sid)->groupBy('personel_id')->selectRaw('personel_id, COUNT(*) n')->pluck('n', 'personel_id')->all(); } catch (\Throwable $e) {} }
        $son = []; if (_srTablo('aktivite_loglari')) { try { $son = DB::table('aktivite_loglari')->where('sube_id', $sid)->where('kategori', 'giris')->groupBy('personel_id')->selectRaw('personel_id, MAX(created_at) s')->pluck('s', 'personel_id')->all(); } catch (\Throwable $e) {} }
        $pinSay = []; foreach ($per as $p) if ((int) $p->aktif && (string) $p->pin !== '') $pinSay[(string) $p->pin] = ($pinSay[(string) $p->pin] ?? 0) + 1;
        $zayif = function ($pin) { $pin = (string) $pin; if ($pin === '') return false; if (strlen($pin) < 4) return true; if (count(array_unique(str_split($pin))) === 1) return true; return str_contains('0123456789', $pin) || str_contains('9876543210', $pin) || in_array($pin, ['1122', '1212', '2580', '1100', '1903', '1907', '1905'], true); };
        $rolAd = ['sahip' => 'Patron', 'mudur' => 'Müdür', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak', 'satinalmaci' => 'Satın Alma'];
        $o = [];
        foreach ($per as $p) {
            $sg = $son[$p->id] ?? null; $gun = $sg ? (int) floor((time() - strtotime((string) $sg)) / 86400) : null; $bul = [];
            if (!(int) $p->aktif && (!empty($p->api_token) || !empty($tok[$p->id]))) $bul[] = 'Pasif hesapta açık oturum / bildirim cihazı';
            if ((int) $p->aktif && ($pinSay[(string) $p->pin] ?? 0) > 1) $bul[] = 'Başka personelle aynı PIN';
            if ((int) $p->aktif && $zayif($p->pin)) $bul[] = 'Tahmin edilebilir PIN';
            if ((int) $p->aktif && ($gun === null || $gun > 60)) $bul[] = $gun === null ? 'Hiç giriş kaydı yok' : $gun . ' gündür giriş yok';
            $o[] = ['ad' => (string) $p->ad, 'rol' => $rolAd[$p->rol] ?? (string) $p->rol, 'yetkili' => in_array($p->rol, ['sahip', 'mudur'], true), 'aktif' => (int) $p->aktif === 1, 'son_giris' => $sg ? substr((string) $sg, 0, 16) : '', 'gun' => $gun,
                'oturum' => !empty($p->api_token), 'cihaz' => (int) ($tok[$p->id] ?? 0), 'bulgu' => $bul];
        }
        usort($o, fn ($a, $b) => [count($b['bulgu']), $a['ad']] <=> [count($a['bulgu']), $b['ad']]);
        return ['kisi' => $o, 'ortak_pin_grup' => count(array_filter($pinSay, fn ($n) => $n > 1))];
    }
    // AI kullanımı (fair-use sayaç: patron_ai_tuketim, aylık) + kota
    function _itrAiKullanim($sid, $bas, $bit)
    {
        $aylar = []; for ($t = strtotime(substr($bas, 0, 7) . '-01'); $t <= strtotime($bit); $t = strtotime('+1 month', $t)) $aylar[] = date('Y-m', $t);
        $hak = null; try { if (function_exists('_aiAylikHak')) $hak = _aiAylikHak(DB::table('subeler')->where('id', $sid)->first()); } catch (\Throwable $e) {}
        $rows = []; if (_srTablo('patron_ai_tuketim')) { try { $rows = DB::table('patron_ai_tuketim')->where('sube_id', $sid)->whereIn('yil_ay', $aylar)->get(); } catch (\Throwable $e) {} }
        $ad = _itrPersonelAd($sid); $ay = []; $kisi = [];
        foreach ($rows as $r) { $ay[$r->yil_ay] = ($ay[$r->yil_ay] ?? 0) + (int) $r->kullanim; $k = (int) $r->personel_id ? ($ad[(int) $r->personel_id] ?? '#' . $r->personel_id) : 'Sistem (brifing / otomatik)'; $kisi[$k] = ($kisi[$k] ?? 0) + (int) $r->kullanim; }
        arsort($kisi); ksort($ay);
        return ['aylar' => $aylar, 'ay' => $ay, 'kisi' => $kisi, 'hak' => $hak, 'toplam' => array_sum($ay)];
    }
    // AI eylem günlüğü: öneri → karar (onay / red / zaman aşımı) → yürütme (başarılı / hata)
    function _itrAiEylem($sid, $bas, $bit)
    {
        if (!_srTablo('ai_eylem_log')) return [];
        try { $on = DB::table('ai_eylem_log')->where('sube_id', $sid)->where('tip', 'onerildi')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('id')->get(); } catch (\Throwable $e) { return []; }
        $k = collect(); if ($on->count()) { try { $k = DB::table('ai_eylem_log')->whereIn('ref_id', $on->pluck('id')->all())->get()->groupBy('ref_id'); } catch (\Throwable $e) {} }
        $o = [];
        foreach ($on as $x) {
            $l = $k[$x->id] ?? collect(); $karar = $l->firstWhere('tip', 'onaylandi') ? 'Onaylandı' : ($l->firstWhere('tip', 'reddedildi') ? 'Reddedildi' : ($l->firstWhere('tip', 'zamanasimi') ? 'Zaman aşımı' : 'Bekliyor'));
            $y = $l->firstWhere('tip', 'yurutme_hatasi') ? 'Hata' : ($l->firstWhere('tip', 'yurutuldu') ? 'Yürütüldü' : '—');
            $o[] = ['tarih' => _itrGun($x->created_at), 'saat' => substr((string) $x->created_at, 11, 5), 'modul' => (string) $x->modul, 'intent' => (string) $x->intent, 'ozet' => (string) ($x->ozet ?? ''), 'kim' => (string) ($x->kullanici_ad ?: '—'), 'rol' => (string) ($x->kullanici_rol ?? ''), 'karar' => $karar, 'yurutme' => $y,
                'mesaj' => (string) (optional($l->firstWhere('tip', 'yurutme_hatasi'))->sonuc_mesaj ?? optional($l->firstWhere('tip', 'yurutuldu'))->sonuc_mesaj ?? '')];
        }
        return $o;
    }
    function _itrModulAd($m) { return ['ik' => 'İK', 'satinalma' => 'Satın alma', 'patron' => 'Patron', 'kalite' => 'Kalite', 'it' => 'IT', 'crm' => 'CRM', 'mutfak' => 'Mutfak', 'depo' => 'Depo', 'kasa' => 'Kasa'][$m] ?? ($m ?: '—'); }
    function _itrZincir()
    {
        $son = 0; try { $son = (int) DB::table('ai_eylem_log')->max('id'); } catch (\Throwable $e) {} // yeni kayıt gelince yeniden doğrulanır
        return cache()->remember('itr_ai_zincir_' . $son, now()->addHour(), function () { try { return function_exists('_aiEylemZincirDogrula') ? _aiEylemZincirDogrula() : null; } catch (\Throwable $e) { return null; } });
    }
    // WhatsApp: hatlar (anlık) + mesaj teslim günlüğü (1 gönderildi · 2 başarısız · 3 SMS'e düştü · 0 kuyrukta)
    function _itrWaHat($sid)
    {
        if (!_srTablo('wa_hatlar')) return [];
        try { return DB::table('wa_hatlar')->where('sube_id', $sid)->get()->map(fn ($h) => ['ad' => (string) $h->ad, 'amac' => ['musteri' => 'Müşteri', 'satinalma' => 'Satın alma', 'yonetim' => 'Yönetim', 'genel' => 'Genel'][$h->amac] ?? (string) $h->amac,
            'numara' => $h->numara ? substr((string) $h->numara, 0, -4) . '••••' : '—', 'durum' => $h->durum === 'bagli' ? 'Bağlı' : ($h->durum ? 'Bağlı değil (' . $h->durum . ')' : 'Bağlı değil'), 'bagli' => $h->durum === 'bagli', 'aktif' => (int) $h->aktif === 1, 'varsayilan' => (int) $h->varsayilan === 1,
            'guncel' => substr((string) ($h->updated_at ?? ''), 0, 16)])->all(); } catch (\Throwable $e) { return []; }
    }
    function _itrWaMesaj($sid, $bas, $bit)
    {
        if (!_srTablo('wa_mesaj_loglari')) return [];
        try { return DB::table('wa_mesaj_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['yon', 'durum', 'hata', 'created_at'])->map(fn ($m) => ['tarih' => _itrGun($m->created_at), 'yon' => (string) $m->yon, 'durum' => (int) $m->durum, 'hata' => trim((string) ($m->hata ?? ''))])->all(); } catch (\Throwable $e) { return []; }
    }
    function _itrWaDurumAd($d) { return [0 => 'Kuyrukta', 1 => 'Gönderildi', 2 => 'Başarısız', 3 => 'SMS\'e düştü'][$d] ?? (string) $d; }
    function _itrEbelge($sid, $bas, $bit)
    {
        if (!_srTablo('e_faturalar')) return [];
        try { return DB::table('e_faturalar')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('id')->get()->map(fn ($e) => ['tarih' => _itrGun($e->created_at), 'saat' => substr((string) $e->created_at, 11, 5), 'tip' => (string) ($e->tip ?? ''), 'no' => (string) ($e->belge_no ?? ''),
            'tutar' => (float) ($e->toplam ?? 0), 'durum' => (string) ($e->durum ?? ''), 'hata' => trim((string) ($e->hata ?? '')), 'entegrator' => (string) ($e->entegrator ?? '')])->all(); } catch (\Throwable $e) { return []; }
    }
    function _itrEbelgeTipAd($t) { return ['okc_fis' => 'ÖKC / mali fiş', 'e_arsiv' => 'e-Arşiv', 'e_fatura' => 'e-Fatura', 'earsiv' => 'e-Arşiv', 'efatura' => 'e-Fatura'][$t] ?? ($t ?: '—'); }
    // Uygulamadan senkronla (çevrimdışı kuyruktan) gelen adisyonlar: istemcinin ürettiği uuid ile gelir ('srv_' önekliler web panelinde açılmıştır)
    function _itrOffline($sid, $bas, $bit)
    {
        if (!_srKolon('adisyonlar', 'uuid')) return [];
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->whereNotNull('uuid')->where('uuid', 'not like', 'srv_%')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['id', 'acilis', 'kapanis', 'created_at', 'toplam', 'durum', 'acan_personel_id']); } catch (\Throwable $e) { return []; }
        $ad = _itrPersonelAd($sid); $o = [];
        foreach ($rows as $r) { $a = $r->acilis ? strtotime((string) $r->acilis) : null; $c = strtotime((string) $r->created_at); $o[] = ['tarih' => date('Y-m-d', $c), 'acilis' => $a ? date('Y-m-d H:i', $a) : '', 'gelis' => date('Y-m-d H:i', $c), 'gecikme' => $a && $c >= $a ? round(($c - $a) / 60) : null,
            'toplam' => (float) $r->toplam, 'durum' => (string) $r->durum, 'kim' => $ad[(int) $r->acan_personel_id] ?? '—']; }
        return $o;
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_itrVeriDurumu')) {
    function _itrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t)->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $it = fn ($tip) => $say('it_kayitlari', null, fn ($q) => $q->where('tip', $tip));
        return ['ariza' => $it('ariza') + $say('departman_isler', 'created_at', fn ($q) => $q->where('departman_kod', 'it')->where('tip', 'ariza')),
            'lisans' => $it('lisans'), 'yedek' => $it('yedek'), 'kvkk' => $it('kvkk'), 'bakim' => $it('bakim'), 'it_gorev' => $say('departman_isler', 'created_at', fn ($q) => $q->where('departman_kod', 'it')->whereNotNull('tekrar')),
            'cihaz' => $say('cihazlar', null), 'donanim' => count(_itrDonanim($sid)), 'aktivite' => $say('aktivite_loglari', 'created_at'), 'giris' => $say('aktivite_loglari', 'created_at', fn ($q) => $q->where('kategori', 'giris')),
            'giris_hata' => $say('aktivite_loglari', 'created_at', fn ($q) => $q->where('kategori', 'giris_hata')), 'personel' => $say('personeller', null),
            'ai_kullanim' => $say('patron_ai_tuketim', null), 'ai_eylem' => $say('ai_eylem_log', 'created_at'), 'wa_hat' => $say('wa_hatlar', null), 'wa_mesaj' => $say('wa_mesaj_loglari', 'created_at'),
            'ebelge' => $say('e_faturalar', 'created_at'), 'offline' => _srKolon('adisyonlar', 'uuid') ? $say('adisyonlar', 'created_at', fn ($q) => $q->whereNotNull('uuid')->where('uuid', 'not like', 'srv_%')) : 0];
    }
    function _itrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'ariza_yok' => 'Arıza / destek kaydı yok. Arızalar IT / Bilgi İşlem iş ekranında (/it-destek) "Cihaz / yazıcı arızası" hızlı kaydıyla ya da IT Kayıt Defteri\'nden öncelik seçilerek açılır; çözülünce "Tamam" (kapat) denir. Çözüm süresi açılış ile kapanış arasından hesaplanır.',
            'ariza' => 'Seçili dönemde (' . $donem . ') açılan ya da açık kalan arıza yok.',
            'acik' => 'Şu an açık arıza / destek talebi yok.',
            'lisans_yok' => 'Lisans / domain / SSL kaydı yok. Alan adı, SSL sertifikası, yazılım lisansları, internet / hat sözleşmesi, mali onaylı ÖKC servis sözleşmesi gibi yenilenmesi gereken her şey IT Kayıt Defteri > Lisans / Domain / SSL bölümüne bitiş tarihiyle girilmeli.',
            'lisans' => 'Takvimde süresi geçen ya da 30 gün içinde dolacak kayıt yok.',
            'yedek_yok' => 'Yedekleme kaydı yok. Her yedek alındığında (bulut yedeği, harici disk, POS veri yedeği) IT Kayıt Defteri > Yedekleme bölümüne tarihiyle girilmeli; son yedeğin yaşı buradan ölçülür. İyi uygulama: günlük otomatik + haftalık dış ortam yedeği.',
            'kvkk_yok' => 'KVKK / veri güvenliği kaydı yok. VERBİS kaydı, aydınlatma metni, açık rıza, veri imha tutanağı, ihlal bildirimi ve erişim yetki gözden geçirmeleri IT Kayıt Defteri > KVKK bölümünden tarihleriyle girilmeli.',
            'cihaz_yok' => 'Bağlı cihaz kaydı yok. Uygulama açık olan tablet / telefon / bilgisayarlar kendiliğinden "Bağlı Cihazlar" listesine düşer (heartbeat); yazıcı ve yazarkasa gibi cihazlar Bağlı Cihazlar ekranından elle eklenir.',
            'surum' => 'Sürüm bilgisi gönderen cihaz yok (uygulamanın yeni sürümleri sürüm bilgisini otomatik gönderir).',
            'donanim_yok' => 'Demirbaş kayıtlarında BT donanımı (bilgisayar, tablet, yazıcı, modem, kamera, POS…) yok. Demirbaş ekranından kategori ya da adında bu kelimeler geçecek şekilde, garanti bitiş tarihiyle eklenirse garanti ve arıza maliyeti izlenir.',
            'aktivite_yok' => 'Aktivite kaydı yok. Uygulamadaki her değiştiren işlem ve her giriş otomatik kaydedilir; personel uygulamaya / panele kendi PIN\'iyle girmeye başlayınca dolar.',
            'aktivite' => 'Seçili dönemde (' . $donem . ') aktivite kaydı yok.',
            'giris' => 'Seçili dönemde (' . $donem . ') giriş kaydı yok.',
            'giris_hata' => 'Hatalı PIN denemeleri henüz kaydedilmiyor (sistem yalnız geçici kilit uyguluyor). Kayıt başladığında burada IP ve saat bazında görünür.',
            'ai_yok' => 'AI kullanım kaydı yok. Sesli / yazılı asistanlar ve departman AI\'ları gerçek yapay zekâ çağrısı yaptıkça aylık sayaç dolar (kural ve önbellekten gelen cevaplar sayılmaz).',
            'ai_eylem_yok' => 'AI eylem kaydı yok. AI bir iş yapmayı önerdiğinde (bildirim gönder, sipariş taslağı vb.) öneri, kullanıcının onayı / reddi ve sonuç değiştirilemez zincir halinde AI Eylem Günlüğü\'ne (/ai-eylem-log) yazılır.',
            'ai_eylem' => 'Seçili dönemde (' . $donem . ') AI eylem önerisi yok.',
            'wa_yok' => 'WhatsApp hattı ya da mesaj kaydı yok. Hatlar WhatsApp Yönetimi ekranından QR okutularak bağlanır; gönderilen her mesaj teslim durumuyla kaydedilir.',
            'wa' => 'Seçili dönemde (' . $donem . ') WhatsApp mesajı yok.',
            'ebelge_yok' => 'e-Belge / mali fiş kaydı yok. e-Arşiv ya da ÖKC (yazarkasa) modu Yasal / e-Dönüşüm ayarlarından açılınca her belge durumuyla (gönderildi / hata) kaydedilir.',
            'ebelge' => 'Seçili dönemde (' . $donem . ') e-belge / mali fiş yok.',
            'offline_yok' => 'Uygulamadan çevrimdışı senkronla gelen satış yok (internet kesintisinde tabletin kendi sıraya aldığı adisyonlar bağlantı gelince buraya düşer). Bu iyi haber: kesinti yaşanmamış ya da çevrimdışı mod kullanılmamış.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_itrKatalog')) {
    function _itrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük IT Bülteni', 'Dün açılan / çözülen arızalar, açık talepler, çevrimdışı cihazlar, girişler ve mesai dışı işlemler, hatalı e-belge ve WhatsApp teslim sorunları, yaklaşan yenilemeler.', 'today', '#4F46E5', ['ariza', 'cihaz', 'aktivite'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık IT Raporu', 'Talep hacmi ve çözüm süresi, SLA, cihaz sağlığı ve sürüm uyumu, yedek yaşı, erişim olayları, entegrasyon hataları, AI kullanımı; önceki haftayla kıyas.', 'date_range', '#4338CA', ['ariza', 'cihaz', 'aktivite'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık IT Yönetim Raporu', 'Patron için tek dosya: destek performansı, varlık envanteri, lisans / SSL / yedek / KVKK takvimi, hesap güvenliği bulguları, AI kotası ve eylem denetimi, entegrasyon sağlığı.', 'calendar_month', '#3730A3', ['ariza', 'lisans', 'yedek'], 'gecen_ay'],
            'ariza' => ['destek', 'Arıza Kayıtları & Çözüm Süresi', 'Dönemde açılan talepler, önceliğe göre ortalama çözüm süresi (MTTR), hedef süre (yüksek 4 / orta 24 / düşük 72 saat) uyumu, en çok arıza veren cihaz.', 'build_circle', '#DC2626', ['ariza'], null],
            'acik_ariza' => ['destek', 'Açık Talepler & Yaşlanma', 'Şu an açık olan arıza / destek talepleri: kaç gündür açık, hedef süreyi aşanlar, önceliğe ve sorumluya göre.', 'pending_actions', '#B91C1C', ['ariza'], 'bugun'],
            'lisans' => ['takvim', 'Lisans / Domain / SSL Takvimi', 'Yenilenmesi gereken lisans, alan adı, SSL, hat ve servis sözleşmeleri: süresi geçen, 30 gün içinde dolacak, bitiş tarihi girilmemiş.', 'verified', '#0891B2', ['lisans'], null],
            'yedek' => ['takvim', 'Yedekleme Uyumu', 'Son yedeğin yaşı, dönemde alınan yedek sayısı, en uzun yedeksiz kalınan süre ve yedek geçmişi.', 'backup', '#0E7490', ['yedek'], null],
            'kvkk_bakim' => ['takvim', 'KVKK & Periyodik Bakım', 'KVKK / veri güvenliği kayıtları, periyodik IT bakımları ve IT rutin kontrollerinin (yedek kontrolü, ağ kontrolü…) yapılma oranı.', 'policy', '#155E75', ['kvkk'], null],
            'envanter' => ['cihaz', 'Cihaz Envanteri & Son Görülme', 'Tüm bağlı cihazlar ve yazıcılar: tip, platform, sürüm, IP, son görülme; çevrimdışı ve 7 günden uzun görünmeyen cihazlar, tanımlı ağ yazıcıları.', 'devices', '#7C3AED', ['cihaz'], null],
            'surum' => ['cihaz', 'Uygulama Sürüm Uyumu', 'Platform başına en yeni sürüm ve geride kalan cihazlar; eski sürüm hata ve güvenlik riski taşır.', 'system_update', '#6D28D9', ['cihaz'], null],
            'donanim' => ['cihaz', 'BT Donanımı, Garanti & Arıza Maliyeti', 'Demirbaştaki bilgisayar, tablet, yazıcı, modem, kamera, POS vb.: durum, garanti bitişi, arıza sayısı ve bakım-onarım maliyeti.', 'memory', '#5B21B6', ['donanim'], null],
            'aktivite' => ['erisim', 'Kullanıcı Aktivitesi', 'Kişi ve kategori bazında işlem hacmi, saatlere göre dağılım, mesai dışı (00:00–06:00) işlemler ve kullanılan IP adresleri.', 'manage_search', '#475569', ['aktivite'], null],
            'giris' => ['erisim', 'Girişler & Hatalı Denemeler', 'Kim, ne zaman, hangi kanaldan (web / uygulama) ve hangi IP\'den girdi; hatalı PIN denemeleri; alışılmadık saatte giriş.', 'login', '#334155', ['giris'], null],
            'hesap' => ['erisim', 'Hesap Güvenliği Denetimi', 'Pasif personelde açık oturum, birden fazla kişide aynı PIN, tahmin edilebilir PIN, uzun süredir girmeyen hesap; yetkili hesap sayısı (PIN\'ler gösterilmez).', 'admin_panel_settings', '#1E293B', ['personel'], 'bugun'],
            'yetki' => ['erisim', 'Yetki & Ayar Değişiklikleri', 'Personel yetkisi, personel kartı, PIN / kişisel bilgi, işletme ayarı ve menü-reçete değişiklikleri: kim, ne zaman, neyi değiştirdi.', 'rule_settings', '#0F172A', ['aktivite'], null],
            'ai_kullanim' => ['yz', 'AI Kullanım Hacmi & Kota', 'Aylık AI yanıt kullanımı, paketin aylık hakkına göre doluluk, kişi bazında kullanım ve tahmini ay sonu.', 'smart_toy', '#9333EA', ['ai_kullanim'], 'bu_ay'],
            'ai_eylem' => ['yz', 'AI Eylem Denetimi', 'AI\'nın önerdiği işlemler: modül, onaylanan / reddedilen / zaman aşımına uğrayan, yürütme hatası; günlüğün değiştirilmezlik (imza zinciri) kontrolü.', 'gavel', '#7E22CE', ['ai_eylem'], null],
            'whatsapp' => ['entegrasyon', 'WhatsApp Hatları & Mesaj Teslimi', 'Hatların bağlantı durumu, gönderilen / başarısız / SMS\'e düşen mesajlar, günlük teslim oranı ve en sık hata.', 'chat', '#16A34A', ['wa_mesaj'], null],
            'ebelge' => ['entegrasyon', 'e-Belge & Mali Fiş Hataları', 'e-Arşiv, e-Fatura ve ÖKC mali fiş gönderimleri: başarılı / hatalı, hata mesajları ve günlere göre hata.', 'receipt_long', '#D97706', ['ebelge'], null],
            'offline' => ['entegrasyon', 'Çevrimdışı Satış Senkronu', 'İnternet kesintisinde tabletin sıraya alıp sonra gönderdiği adisyonlar: gün, tutar ve açılıştan sunucuya ulaşana kadar geçen süre.', 'cloud_sync', '#0369A1', ['offline'], null],
        ];
    }
    function _itrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Günlük IT bülteni, haftalık rapor ve aylık IT yönetim raporu.', '#4F46E5'],
            'destek' => ['Arıza & Destek', 'build_circle', 'Talep hacmi, çözüm süresi, hedef süre uyumu ve açık talep yaşlanması.', '#DC2626'],
            'takvim' => ['Lisans, Yedek & KVKK', 'verified', 'Lisans / domain / SSL yenileme takvimi, yedek uyumu, KVKK ve periyodik bakım.', '#0891B2'],
            'cihaz' => ['Cihaz & Donanım', 'devices', 'Bağlı cihaz envanteri, son görülme, sürüm uyumu, BT donanımı ve garanti.', '#7C3AED'],
            'erisim' => ['Erişim & Güvenlik', 'admin_panel_settings', 'Kullanıcı aktivitesi, girişler, hesap güvenliği ve yetki değişiklikleri.', '#475569'],
            'yz' => ['AI Kullanımı', 'smart_toy', 'AI kota kullanımı ve AI eylem denetimi.', '#9333EA'],
            'entegrasyon' => ['Entegrasyon Sağlığı', 'hub', 'WhatsApp teslimi, e-belge / mali fiş hataları ve çevrimdışı satış senkronu.', '#16A34A'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı IT özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_itrSayfaAriza')) {
    function _itrSayfaAriza(array $l, $bos, $ad = 'Arıza / Destek Talepleri')
    {
        return _srSayfa($ad, 'Çözüm süresi = açılıştan kapanışa geçen saat. Hedef: yüksek öncelik 4, orta 24, düşük 72 saat. IT kayıt defterindeki eski kayıtlarda kapanış saati tutulmadığından süre boş görünebilir.',
            [['Açılış', 't', 11], ['Saat', 'm', 6], ['Talep', 'm', 32], ['Cihaz', 'm', 14], ['Öncelik', 'm', 8], ['Durum', 'm', 9], ['Çözüm (saat)', 's', 10], ['Açık (gün)', 's', 9, 'esik:3'], ['Hedef', 'm', 11], ['Sorumlu', 'm', 14], ['Kaynak', 'm', 14]],
            array_map(fn ($x) => [$x['acilis'], $x['acilis_saat'], $x['baslik'], $x['cihaz'], _itrOncAd($x['oncelik']), _itrDurumAd($x['durum']), $x['sure'], $x['yas'], $x['sla'], $x['kim'], $x['kaynak']], $l), $bos);
    }
    function _itrArizaOzet(array $l)
    {
        $coz = array_filter($l, fn ($x) => $x['durum'] === 'cozuldu'); $sure = array_values(array_filter(array_column($coz, 'sure'), fn ($x) => $x !== null));
        $sla = array_filter($coz, fn ($x) => $x['sure'] !== null); $hedef = array_filter($sla, fn ($x) => $x['sla'] === 'Hedefte');
        return ['n' => count($l), 'cozulen' => count($coz), 'acik' => count(array_filter($l, fn ($x) => $x['durum'] === 'acik')), 'mttr' => $sure ? round(array_sum($sure) / count($sure), 1) : null,
            'sla' => $sla ? round(count($hedef) / count($sla) * 100, 1) : null, 'yuksek' => count(array_filter($l, fn ($x) => $x['oncelik'] === 'yuksek'))];
    }
    function _itrSayfaCihaz(array $c, $bos)
    {
        return _srSayfa('Cihaz Envanteri', 'Son görülme: uygulama açıkken cihaz 1-2 dakikada bir sinyal gönderir; 2 dakikadan eskiyse çevrimdışı sayılır. Elle eklenen yazıcı / yazarkasa sinyal göndermeyebilir.',
            [['Cihaz', 'm', 22], ['Tip', 'm', 10], ['Platform', 'm', 10], ['Sürüm', 'm', 9], ['Rol', 'm', 9], ['IP', 'm', 13], ['Son Görülme', 'm', 15], ['Durum', 'm', 14], ['Gün', 's', 6, 'esik:7'], ['Marka / Seri', 'm', 18], ['Kayıt', 't', 11]],
            array_map(fn ($x) => [$x['ad'] . ($x['aktif'] ? '' : ' (pasif)'), $x['tip'], $x['platform'] ?: '—', $x['surum'] ?: '—', $x['rol'] ?: '—', $x['ip'] ?: '—', $x['son'] ?: 'hiç', $x['durum'], $x['gun'], trim($x['marka'] . ' ' . $x['seri']) ?: '—', $x['ilk']], $c), $bos);
    }
    function _itrSayfaTakvim(array $t, $bos, $ad, $not)
    {
        return _srSayfa($ad, $not, [['Kayıt', 'm', 30], ['Sağlayıcı / Değer', 'm', 18], ['Başlangıç / Yapılış', 't', 13], ['Bitiş / Sonraki', 't', 12], ['Kalan Gün', 's', 9], ['Durum', 'm', 14], ['Giren', 'm', 14], ['Not', 'm', 28]],
            array_map(fn ($x) => [$x['baslik'], $x['deger'] ?: '—', $x['tarih'], $x['son'], $x['kalan'], $x['drm'], $x['personel'], $x['not']], $t), $bos);
    }
    function _itrSayfaHesap(array $h, $bos)
    {
        return _srSayfa('Hesap Güvenliği', 'PIN değerleri gösterilmez. "Açık oturum" = uygulamada geçerli giriş anahtarı var; pasif personelde bu, işten ayrılan kişinin hâlâ girebileceği anlamına gelir (Personel kartından oturumu sonlandırın / PIN\'i değiştirin).',
            [['Personel', 'm', 20], ['Rol', 'm', 10], ['Durum', 'm', 8], ['Son Giriş', 'm', 16], ['Gün', 's', 6], ['Açık Oturum', 'm', 10], ['Bildirim Cihazı', 's', 10], ['Bulgu Sayısı', 's', 10, 'top esik:1'], ['Bulgular', 'm', 50]],
            array_map(fn ($x) => [$x['ad'], $x['rol'] . ($x['yetkili'] ? ' ★' : ''), $x['aktif'] ? 'Aktif' : 'Pasif', $x['son_giris'] ?: '—', $x['gun'], $x['oturum'] ? 'Var' : '—', $x['cihaz'], count($x['bulgu']), implode(' · ', $x['bulgu']) ?: 'Sorun yok'], $h), $bos);
    }
    function _itrTakvimHepsi($sid)
    {
        $o = [];
        foreach ([['lisans', 'Lisans / domain / SSL'], ['bakim', 'Periyodik bakım'], ['kvkk', 'KVKK']] as [$t, $ad]) foreach (_itrTakvim($sid, $t) as $x) if ($x['kalan'] !== null && $x['kalan'] <= 30) $o[] = [$ad, $x['baslik'], $x['son'], $x['kalan'], $x['drm']];
        foreach (_itrDonanim($sid) as $x) if ($x['garanti_kalan'] !== null && $x['garanti_kalan'] <= 30 && $x['garanti_kalan'] >= -30) $o[] = ['Donanım garantisi', $x['ad'], $x['garanti'], $x['garanti_kalan'], $x['garanti_kalan'] < 0 ? 'Süresi geçti' : 'Yaklaşıyor'];
        usort($o, fn ($a, $b) => $a[3] <=> $b[3]);
        return $o;
    }
}

// ---------- Raporlar ----------
if (!function_exists('_itrRapor')) {
    function _itrRapor($anahtar, $sid, array $dn)
    {
        _itrEnsure();
        $d = _itrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiS = fn ($ad, $v, $renk, $bos = 'Ölçüm yok') => _srKpi($ad, $v ?? $bos, $v === null ? 'metin' : 'sayi', $renk);
        $sayD = fn (array $l, $fn) => collect($l)->groupBy($fn)->map->count()->sortDesc()->all();
        $blok = fn ($b, $tip, $renk, $kolAd, array $map) => _srBlok($b, $tip, $renk, [[$kolAd, 'm'], ['Sayı', 's'], ['', 'm']], array_map(fn ($a, $v) => [(string) $a, $v, ''], array_keys($map), array_values($map)));
        $bosAriza = !$d['ariza'] ? _itrBos('ariza_yok', $dn) : _itrBos('ariza', $dn);
        $bosAkt = !$d['aktivite'] ? _itrBos('aktivite_yok', $dn) : _itrBos('aktivite', $dn);

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $ar = _itrArizalar($sid, $bas, $bit); $ao = _itrArizaOzet($ar); $c = _itrCihazlar($sid); $off = array_values(array_filter($c, fn ($x) => !$x['yazici'] && $x['aktif'] && $x['durum'] !== 'Çevrimiçi'));
                $ak = _itrAktivite($sid, $bas, $bit); $gir = array_filter($ak, fn ($x) => $x['kategori'] === 'giris'); $md = array_values(array_filter($ak, fn ($x) => _itrMesaiDisi($x['saat'])));
                $eb = array_values(array_filter(_itrEbelge($sid, $bas, $bit), fn ($x) => $x['durum'] === 'hata')); $wa = _itrWaMesaj($sid, $bas, $bit); $tak = _itrTakvimHepsi($sid);
                return [
                    $pano([_srKpi('Açılan talep', count(array_filter($ar, fn ($x) => $x['acilis'] >= $bas)), 'sayi', '4F46E5'), _srKpi('Çözülen', $ao['cozulen'], 'sayi', '059669'), _srKpi('Açık talep (toplam)', $ao['acik'], 'sayi', 'DC2626'),
                        _srKpi('Çevrimdışı cihaz (şu an)', count($off), 'sayi', 'B91C1C'), _srKpi('Giriş', count($gir), 'sayi', '334155'), _srKpi('Giriş yapan kişi', count(array_unique(array_column($gir, 'pid'))), 'sayi', '475569'),
                        _srKpi('Mesai dışı işlem (00-06)', count($md), 'sayi', 'C2410C'), _srKpi('Hatalı e-belge', count($eb), 'sayi', 'D97706'), _srKpi('Başarısız WhatsApp', count(array_filter($wa, fn ($x) => $x['durum'] === 2)), 'sayi', '16A34A'),
                        _srKpi('30 gün içinde yenileme', count($tak), 'sayi', '0891B2')],
                        [$blok('Kategoriye göre işlem', 'pie', '475569', 'Kategori', $sayD($ak, fn ($x) => _itrKatAd($x['kategori']))), _srBlok('Saatlere göre işlem', 'col', '4F46E5', [['Saat', 'm'], ['İşlem', 's'], ['', 'm']], collect($ak)->groupBy('saat')->sortKeys()->map(fn ($g, $h) => [sprintf('%02d:00', $h), $g->count(), ''])->values()->all())],
                        !$ar && !$ak && !$c ? _itrBos('aktivite_yok', $dn) : null),
                    _itrSayfaAriza($ar, $bosAriza),
                    _itrSayfaCihaz($off, !$d['cihaz'] ? _itrBos('cihaz_yok', $dn) : 'Tüm cihazlar çevrimiçi.'),
                    _srSayfa('Mesai Dışı İşlemler', '00:00–05:59 arası yapılan işlemler. Kapanış sonrası işlem normal olabilir; tanımadığınız kişi / IP varsa inceleyin.', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Personel', 'm', 18], ['İşlem', 'm', 30], ['Kategori', 'm', 14], ['IP', 'm', 14]],
                        array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['ad'], $x['aksiyon'], _itrKatAd($x['kategori']), $x['ip'] ?: '—'], $md), 'Mesai dışı işlem yok.'),
                    _srSayfa('Hatalı e-Belge / Mali Fiş', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Tür', 'm', 14], ['Belge No', 'm', 16], ['Tutar', 'tl', 12], ['Hata', 'm', 50]], array_map(fn ($x) => [$x['tarih'], $x['saat'], _itrEbelgeTipAd($x['tip']), $x['no'] ?: '—', $x['tutar'], $x['hata'] ?: '—'], $eb), 'Hatalı e-belge yok.'),
                    _srSayfa('Yaklaşan Yenilemeler (30 gün)', 'Lisans / domain / SSL, bakım, KVKK ve donanım garantisi. Negatif gün = süresi geçmiş.', [['Tür', 'm', 18], ['Kayıt', 'm', 30], ['Tarih', 't', 11], ['Kalan Gün', 's', 9], ['Durum', 'm', 14]], $tak, !$d['lisans'] ? _itrBos('lisans_yok', $dn) : _itrBos('lisans', $dn)),
                ];
            case 'haftalik':
            case 'aylik':
                $ar = _itrArizalar($sid, $bas, $bit); $ao = _itrArizaOzet($ar); $aoo = _itrArizaOzet(_itrArizalar($sid, $dn['obas'], $dn['obit'])); $c = _itrCihazlar($sid); $sv = _itrSurum($sid);
                $ak = _itrAktivite($sid, $bas, $bit); $yd = _itrYedek($sid, $bas, $bit); $wa = _itrWaMesaj($sid, $bas, $bit); $eb = _itrEbelge($sid, $bas, $bit); $ai = _itrAiKullanim($sid, $bas, $bit);
                $waOk = count(array_filter($wa, fn ($x) => $x['yon'] !== 'gelen')); $waBas = count(array_filter($wa, fn ($x) => $x['yon'] !== 'gelen' && $x['durum'] === 2));
                $kpi = [_srKpi('Açılan talep', count(array_filter($ar, fn ($x) => $x['acilis'] >= $bas)), 'sayi', '4F46E5'), _srKpi('Önceki dönem talep', $aoo['n'], 'sayi', '4338CA'), $kpiS('Ort. çözüm (saat)', $ao['mttr'], '059669'), $kpiY('Hedef süre uyumu', $ao['sla'], '047857'),
                    _srKpi('Açık talep', $ao['acik'], 'sayi', 'DC2626'), _srKpi('Cihaz (çevrimiçi / toplam)', count(array_filter($c, fn ($x) => $x['durum'] === 'Çevrimiçi')) . ' / ' . count($c), 'metin', '7C3AED'),
                    _srKpi('Eski sürümde cihaz', count(array_filter($sv['liste'], fn ($x) => !$x['guncel'])), 'sayi', '6D28D9'), _srKpi('Son yedek', $yd['son'] ? $yd['yas'] . ' gün önce' : 'Kayıt yok', 'metin', $yd['yas'] === null || $yd['yas'] > 7 ? 'DC2626' : '0E7490'),
                    _srKpi('İşlem / giriş', count($ak) . ' / ' . count(array_filter($ak, fn ($x) => $x['kategori'] === 'giris')), 'metin', '475569'), _srKpi('Mesai dışı işlem', count(array_filter($ak, fn ($x) => _itrMesaiDisi($x['saat']))), 'sayi', 'C2410C'),
                    $kpiY('WhatsApp başarısız oranı', $waOk ? round($waBas / $waOk * 100, 1) : null, '16A34A'), _srKpi('Hatalı e-belge', count(array_filter($eb, fn ($x) => $x['durum'] === 'hata')), 'sayi', 'D97706'), _srKpi('AI kullanım (ay)', $ai['toplam'], 'sayi', '9333EA')];
                $gun = []; foreach (_itrGunler($bas, $bit) as $t) $gun[] = [date('d.m', strtotime($t)), count(array_filter($ar, fn ($x) => $x['acilis'] === $t)), count(array_filter($ar, fn ($x) => $x['kapanis'] === $t))];
                $bloklar = [_srBlok('Günlere göre açılan / çözülen talep', 'col', '4F46E5', [['Gün', 'm'], ['Açılan', 's'], ['Çözülen', 's']], $gun), $blok('Cihaz durumu', 'pie', '7C3AED', 'Durum', $sayD($c, fn ($x) => $x['durum'])), $blok('Kategoriye göre işlem', 'bar', '475569', 'Kategori', $sayD($ak, fn ($x) => _itrKatAd($x['kategori'])))];
                $sayfalar = [_itrSayfaAriza($ar, $bosAriza), _itrSayfaCihaz(array_values(array_filter($c, fn ($x) => $x['durum'] !== 'Çevrimiçi')), !$d['cihaz'] ? _itrBos('cihaz_yok', $dn) : 'Tüm cihazlar çevrimiçi.'),
                    _srSayfa('Kişi Bazında Aktivite', '', [['Personel', 'm', 20], ['Rol', 'm', 10], ['İşlem', 's', 9, 'top bar'], ['Giriş', 's', 8, 'top'], ['Mesai Dışı', 's', 9, 'top esik:1'], ['Farklı IP', 's', 9], ['Son İşlem', 'm', 16]],
                        collect($ak)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->first()['rol'] ?: '—', $g->count(), $g->where('kategori', 'giris')->count(), $g->filter(fn ($x) => _itrMesaiDisi($x['saat']))->count(), $g->pluck('ip')->filter()->unique()->count(), date('Y-m-d H:i', $g->max('ts'))])->sortByDesc(2)->values()->all(), $bosAkt)];
                if ($anahtar === 'aylik') {
                    $h = _itrHesap($sid); $bul = array_values(array_filter($h['kisi'], fn ($x) => $x['bulgu'])); $ae = _itrAiEylem($sid, $bas, $bit); $lis = _itrTakvim($sid, 'lisans');
                    $kpi = array_merge($kpi, [_srKpi('Hesap güvenliği bulgusu', count($bul), 'sayi', '1E293B'), _srKpi('Süresi geçen lisans / SSL', count(array_filter($lis, fn ($x) => $x['drm'] === 'Süresi geçti')), 'sayi', '0891B2'),
                        _srKpi('AI eylem önerisi', count($ae), 'sayi', '7E22CE'), _srKpi('AI yürütme hatası', count(array_filter($ae, fn ($x) => $x['yurutme'] === 'Hata')), 'sayi', 'DC2626'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _itrSayfaTakvim($lis, !$d['lisans'] ? _itrBos('lisans_yok', $dn) : 'Lisans kaydı yok.', 'Lisans / Domain / SSL', 'Her kalemin en son kaydı. Anlık durum.');
                    $sayfalar[] = _srSayfa('Yedek Geçmişi', 'Son yedek ' . ($yd['son'] ? $yd['son'] . ' (' . $yd['yas'] . ' gün önce)' : 'yok') . '. Dönemdeki en uzun yedeksiz süre: ' . $yd['en_uzun'] . ' gün.', [['Yedek Tarihi', 't', 12], ['Önceki Yedekten (gün)', 's', 16, 'esik:8']],
                        array_map(fn ($t, $i) => [$t, $i > 0 ? (int) round((strtotime($t) - strtotime($yd['tum'][$i - 1])) / 86400) : null], $yd['tum'], array_keys($yd['tum'])), _itrBos('yedek_yok', $dn));
                    $sayfalar[] = _itrSayfaHesap($bul, 'Hesap güvenliği bulgusu yok.');
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$ar && !$ak && !$c ? _itrBos('aktivite_yok', $dn) : null)], $sayfalar);

            // ---------------- ARIZA & DESTEK ----------------
            case 'ariza':
                $ar = array_values(array_filter(_itrArizalar($sid, $bas, $bit), fn ($x) => $x['acilis'] >= $bas)); $ao = _itrArizaOzet($ar); $aoo = _itrArizaOzet(array_values(array_filter(_itrArizalar($sid, $dn['obas'], $dn['obit']), fn ($x) => $x['acilis'] >= $dn['obas'])));
                $onc = []; foreach (['yuksek', 'orta', 'dusuk'] as $o) { $l = array_values(array_filter($ar, fn ($x) => $x['oncelik'] === $o)); $z = _itrArizaOzet($l); $onc[] = [_itrOncAd($o), _itrSla($o), $z['n'], $z['cozulen'], $z['acik'], $z['mttr'], $z['sla']]; }
                $cih = collect($ar)->groupBy(fn ($x) => $x['cihaz'] !== '—' ? $x['cihaz'] : $x['baslik'])->map(fn ($g, $k) => [$k, $g->count(), $g->where('durum', 'acik')->count(), ($s = $g->pluck('sure')->filter(fn ($v) => $v !== null)) ->count() ? round($s->avg(), 1) : null])->sortByDesc(1)->values()->all();
                return [
                    $pano([_srKpi('Açılan talep', $ao['n'], 'sayi', 'DC2626'), _srKpi('Önceki dönem', $aoo['n'], 'sayi', 'B91C1C'), _srKpi('Çözülen', $ao['cozulen'], 'sayi', '059669'), _srKpi('Hâlâ açık', $ao['acik'], 'sayi', 'B45309'),
                        $kpiS('Ort. çözüm süresi (saat)', $ao['mttr'], '047857'), $kpiS('Önceki dönem (saat)', $aoo['mttr'], '065F46'), $kpiY('Hedef süre uyumu', $ao['sla'], '0F766E'), _srKpi('Yüksek öncelikli', $ao['yuksek'], 'sayi', '7F1D1D')],
                        [_srBlok('Önceliğe göre talep / çözülen', 'col', 'DC2626', [['Öncelik', 'm'], ['Talep', 's'], ['Çözülen', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[3]], $onc)), _srBlok('En çok arıza veren cihaz / konu', 'bar', 'B91C1C', [['Cihaz / konu', 'm'], ['Talep', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_slice($cih, 0, 10)))], !$ar ? $bosAriza : null),
                    ['grafiksiz' => true] + _srSayfa('Önceliğe Göre Performans', 'Ort. çözüm = kapanışı bilinen taleplerin ortalaması. Hedef uyum = hedef süre içinde çözülenlerin oranı.', [['Öncelik', 'm', 10], ['Hedef (saat)', 's', 10], ['Talep', 's', 8, 'top'], ['Çözülen', 's', 8, 'top'], ['Açık', 's', 7, 'top'], ['Ort. Çözüm (saat)', 's', 14], ['Hedef Uyum %', 'y', 12]], $onc, $bosAriza),
                    _srSayfa('Cihaz / Konuya Göre', 'Tekrar eden arıza, cihazın değişim ya da bakım ihtiyacını gösterir.', [['Cihaz / konu', 'm', 30], ['Talep', 's', 8, 'top bar'], ['Açık', 's', 7, 'top'], ['Ort. Çözüm (saat)', 's', 14]], $cih, $bosAriza),
                    _itrSayfaAriza($ar, $bosAriza),
                ];
            case 'acik_ariza':
                $ac = array_values(array_filter(_itrArizalar($sid, '2000-01-01', date('Y-m-d')), fn ($x) => $x['durum'] === 'acik')); usort($ac, fn ($a, $b) => $b['yas'] <=> $a['yas']);
                $grup = fn ($y) => $y < 1 ? '1 günden az' : ($y < 3 ? '1-3 gün' : ($y < 7 ? '3-7 gün' : '7 gün +'));
                return [
                    $pano([_srKpi('Açık talep', count($ac), 'sayi', 'B91C1C'), _srKpi('Hedef süreyi aşan', count(array_filter($ac, fn ($x) => $x['sla'] === 'Aştı (açık)')), 'sayi', 'DC2626'), _srKpi('7 günden uzun açık', count(array_filter($ac, fn ($x) => $x['yas'] >= 7)), 'sayi', '7F1D1D'),
                        _srKpi('En eski', $ac ? number_format($ac[0]['yas'], 1, ',', '.') . ' gün' : '—', 'metin', '991B1B'), _srKpi('Yüksek öncelikli açık', count(array_filter($ac, fn ($x) => $x['oncelik'] === 'yuksek')), 'sayi', 'DC2626')],
                        [$blok('Yaşa göre', 'col', 'B91C1C', 'Açık süre', $sayD($ac, fn ($x) => $grup($x['yas']))), $blok('Sorumluya göre', 'pie', '991B1B', 'Sorumlu', $sayD($ac, fn ($x) => $x['kim']))], !$ac ? (!$d['ariza'] ? _itrBos('ariza_yok', $dn) : _itrBos('acik', $dn)) : null),
                    _itrSayfaAriza($ac, !$d['ariza'] ? _itrBos('ariza_yok', $dn) : _itrBos('acik', $dn), 'Açık Talepler'),
                ];

            // ---------------- LİSANS, YEDEK, KVKK ----------------
            case 'lisans':
                $l = _itrTakvim($sid, 'lisans'); $bos = !$d['lisans'] ? _itrBos('lisans_yok', $dn) : null;
                return [
                    $pano([_srKpi('Takipteki kalem', count($l), 'sayi', '0891B2'), _srKpi('Süresi geçen', count(array_filter($l, fn ($x) => $x['drm'] === 'Süresi geçti')), 'sayi', 'DC2626'), _srKpi('30 gün içinde', count(array_filter($l, fn ($x) => $x['drm'] === 'Yaklaşıyor')), 'sayi', 'D97706'),
                        _srKpi('Bitiş tarihi yok', count(array_filter($l, fn ($x) => $x['drm'] === 'Bitiş tarihi yok')), 'sayi', '64748B'), _srKpi('En yakın', ($y = collect($l)->first(fn ($x) => $x['kalan'] !== null && $x['kalan'] >= 0)) ? $y['baslik'] . ' (' . $y['kalan'] . ' gün)' : '—', 'metin', '0E7490')],
                        [$blok('Durum', 'pie', '0891B2', 'Durum', $sayD($l, fn ($x) => $x['drm']))], $bos),
                    _itrSayfaTakvim($l, $bos ?? 'Lisans kaydı yok.', 'Lisans / Domain / SSL', 'Başlık + sağlayıcı bazında en son kayıt (yenilendiğinde yeni bitiş tarihiyle tekrar girin). Anlık durum; dönemden bağımsız.'),
                ];
            case 'yedek':
                $yd = _itrYedek($sid, $bas, $bit); $kay = array_values(array_filter(_itrKayit($sid, 'yedek'), fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit)); $bos = !$d['yedek'] ? _itrBos('yedek_yok', $dn) : null;
                $gun = count(_itrGunler($bas, $bit));
                return [
                    $pano([_srKpi('Son yedek', $yd['son'] ? date('d.m.Y', strtotime($yd['son'])) : 'Kayıt yok', 'metin', '0E7490'), _srKpi('Son yedeğin yaşı (gün)', $yd['yas'] ?? 'Yedek yok', $yd['yas'] === null ? 'metin' : 'sayi', $yd['yas'] === null || $yd['yas'] > 7 ? 'DC2626' : '059669'),
                        _srKpi('Dönemde yedek günü', count($yd['donem']) . ' / ' . $gun, 'metin', '155E75'), _srKpi('En uzun yedeksiz süre (gün)', $yd['en_uzun'], 'sayi', $yd['en_uzun'] > 7 ? 'DC2626' : '0891B2'),
                        _srKpi('Durum', $yd['yas'] === null ? 'Yedek kaydı yok' : ($yd['yas'] <= 1 ? 'Güncel' : ($yd['yas'] <= 7 ? 'Kabul edilebilir' : 'Riskli')), 'metin', $yd['yas'] !== null && $yd['yas'] <= 7 ? '059669' : 'DC2626')],
                        [_srBlok('Günlere göre yedek', 'col', '0E7490', [['Gün', 'm'], ['Yedek', 's'], ['', 'm']], array_map(fn ($t) => [date('d.m', strtotime($t)), in_array($t, $yd['donem'], true) ? 1 : 0, ''], _itrGunler($bas, $bit)))], $bos),
                    _srSayfa('Dönemdeki Yedekler', 'Yedek yaşı > 7 gün ise veri kaybı riski yüksektir. İyi uygulama: günlük otomatik yedek + haftalık işletme dışı (bulut / harici disk) kopya.', [['Tarih', 't', 11], ['Yedek', 'm', 30], ['Yer / Yöntem', 'm', 18], ['Giren', 'm', 14], ['Not', 'm', 30]],
                        array_map(fn ($x) => [$x['tarih'], $x['baslik'], $x['deger'] ?: '—', $x['personel'], $x['not']], $kay), $bos ?? 'Seçili dönemde yedek kaydı yok.'),
                ];
            case 'kvkk_bakim':
                $kv = _itrKayit($sid, 'kvkk'); $bk = _itrTakvim($sid, 'bakim'); $uy = function_exists('_klrUyum') ? _klrUyum($sid, $bas, $bit, null, 'it') : []; $ub = array_sum(array_column($uy, 'beklenen'));
                return [
                    $pano([_srKpi('KVKK kaydı', count($kv), 'sayi', '155E75'), _srKpi('Son KVKK kaydı', $kv ? date('d.m.Y', strtotime(max(array_column($kv, 'tarih')))) : 'Yok', 'metin', !$kv ? 'DC2626' : '0E7490'),
                        _srKpi('Bakım kalemi', count($bk), 'sayi', '0891B2'), _srKpi('Süresi geçen bakım', count(array_filter($bk, fn ($x) => $x['drm'] === 'Süresi geçti')), 'sayi', 'DC2626'),
                        $kpiY('IT rutin kontrol uyumu', $ub ? round(array_sum(array_column($uy, 'yapilan')) / $ub * 100, 1) : null, '0F766E')],
                        [_srBlok('IT rutin kontrolleri (%)', 'bar', '0F766E', [['Kontrol', 'm'], ['Uyum %', 'y'], ['', 'm']], array_map(fn ($x) => [$x['baslik'], $x['uyum'], ''], $uy))], !$kv && !$bk && !$uy ? _itrBos('kvkk_yok', $dn) : null),
                    _srSayfa('KVKK / Veri Güvenliği Kayıtları', 'VERBİS, aydınlatma metni, açık rıza, imha tutanağı, ihlal bildirimi, erişim yetki gözden geçirmesi vb.', [['Tarih', 't', 11], ['Kayıt', 'm', 32], ['Değer', 'm', 16], ['Sonraki', 't', 11], ['Giren', 'm', 14], ['Not', 'm', 36]],
                        array_map(fn ($x) => [$x['tarih'], $x['baslik'], $x['deger'] ?: '—', $x['son'], $x['personel'], $x['not']], array_reverse($kv)), _itrBos('kvkk_yok', $dn)),
                    _itrSayfaTakvim($bk, 'Periyodik IT bakım kaydı yok (IT Kayıt Defteri > Periyodik Bakım: sunucu / ağ / UPS / yazıcı bakımı, sonraki tarihiyle).', 'Periyodik Bakım', 'Kalem başına en son bakım ve sonraki tarih.'),
                    _srSayfa('IT Rutin Kontrolleri', 'IT iş ekranındaki tekrarlı görevler (Yedekleme kontrolü, İnternet / ağ kontrolü…): beklenen ve yapılan.', [['Kontrol', 'm', 30], ['Tekrar', 'm', 10], ['Beklenen', 's', 9, 'top'], ['Yapılan', 's', 9, 'top'], ['Uyum %', 'y', 9], ['Son Yapılma', 't', 11]],
                        array_map(fn ($x) => [$x['baslik'], ['gunluk' => 'Günlük', 'haftalik' => 'Haftalık', 'aylik' => 'Aylık'][$x['tekrar']] ?? $x['tekrar'], $x['beklenen'], $x['yapilan'], $x['uyum'], $x['son']], $uy), 'IT iş ekranında (/it-destek) tekrarlı görev yok. "Yedekleme kontrolü" ve "İnternet / ağ kontrolü" hızlı şablonları günlük tekrarla eklenirse uyumu ölçülür.'),
                ];

            // ---------------- CİHAZ & DONANIM ----------------
            case 'envanter':
                $c = _itrCihazlar($sid); $yz = _itrYazicilar($sid); $bos = !$d['cihaz'] ? _itrBos('cihaz_yok', $dn) : null;
                return [
                    $pano([_srKpi('Toplam cihaz', count($c), 'sayi', '7C3AED'), _srKpi('Çevrimiçi', count(array_filter($c, fn ($x) => $x['durum'] === 'Çevrimiçi')), 'sayi', '059669'), _srKpi('7 günden uzun görünmeyen', count(array_filter($c, fn ($x) => in_array($x['durum'], ['7 günden eski', 'Hiç bağlanmadı'], true))), 'sayi', 'DC2626'),
                        _srKpi('Yazıcı / yazarkasa', count(array_filter($c, fn ($x) => $x['yazici'])), 'sayi', '6D28D9'), _srKpi('Ayarlı ağ yazıcısı', count($yz), 'sayi', '5B21B6'), _srKpi('Pasif cihaz', count(array_filter($c, fn ($x) => !$x['aktif'])), 'sayi', '64748B')],
                        [$blok('Son görülme', 'pie', '7C3AED', 'Durum', $sayD($c, fn ($x) => $x['durum'])), $blok('Tipe göre', 'bar', '6D28D9', 'Tip', $sayD($c, fn ($x) => $x['tip'])), $blok('Platforma göre', 'pie', '5B21B6', 'Platform', $sayD(array_filter($c, fn ($x) => $x['platform'] !== ''), fn ($x) => $x['platform']))], $bos),
                    _itrSayfaCihaz($c, $bos ?? 'Cihaz yok.'),
                    _srSayfa('Tanımlı Ağ Yazıcıları', 'Yazıcı Ayarları ekranındaki ESC/POS ağ yazıcıları (IP:port). Yazdırma hatası şu an kaydedilmiyor; cihaz kopukluğu Bağlı Cihazlar\'dan izlenir.', [['Yazıcı', 'm', 22], ['IP', 'm', 16], ['Port', 'm', 8], ['Kâğıt', 'm', 8]], $yz, 'Yazıcı Ayarları\'nda (/yazici-ayarlari) ağ yazıcısı tanımlı değil.'),
                ];
            case 'surum':
                $sv = _itrSurum($sid); $l = $sv['liste']; usort($l, fn ($a, $b) => [$a['guncel'], $a['platform']] <=> [$b['guncel'], $b['platform']]);
                $bos = !$d['cihaz'] ? _itrBos('cihaz_yok', $dn) : (!$l ? _itrBos('surum', $dn) : null);
                return [
                    $pano([_srKpi('Sürüm bildiren cihaz', count($l), 'sayi', '6D28D9'), _srKpi('Güncel', count(array_filter($l, fn ($x) => $x['guncel'])), 'sayi', '059669'), _srKpi('Eski sürümde', count(array_filter($l, fn ($x) => !$x['guncel'])), 'sayi', 'DC2626'),
                        $kpiY('Güncellik oranı', $l ? round(count(array_filter($l, fn ($x) => $x['guncel'])) / count($l) * 100, 1) : null, '7C3AED'), _srKpi('En yeni sürümler', implode(' · ', array_map(fn ($p, $v) => ($p !== '—' ? $p . ' ' : '') . $v, array_keys($sv['en']), array_values($sv['en']))) ?: '—', 'metin', '4C1D95')],
                        [$blok('Sürüme göre cihaz', 'bar', '6D28D9', 'Sürüm', $sayD($l, fn ($x) => ($x['platform'] ? $x['platform'] . ' ' : '') . $x['surum']))], $bos),
                    _srSayfa('Cihaz Sürümleri', 'En yeni = aynı platformdaki cihazlarda görülen en yüksek sürüm.', [['Cihaz', 'm', 22], ['Platform', 'm', 10], ['Sürüm', 'm', 10], ['En Yeni', 'm', 10], ['Durum', 'm', 12], ['Son Görülme', 'm', 16], ['Rol', 'm', 10]],
                        array_map(fn ($x) => [$x['ad'], $x['platform'] ?: '—', $x['surum'], $x['en_yeni'], $x['guncel'] ? 'Güncel' : 'GÜNCELLENMELİ', $x['son'] ?: 'hiç', $x['rol'] ?: '—'], $l), $bos),
                ];
            case 'donanim':
                $h = _itrDonanim($sid); $bos = !$d['donanim'] ? _itrBos('donanim_yok', $dn) : null;
                return [
                    $pano([_srKpi('BT donanımı', count($h), 'sayi', '5B21B6'), _srKpi('Arızalı / bakımda', count(array_filter($h, fn ($x) => in_array($x['ham_durum'], ['arizali', 'bakimda'], true))), 'sayi', 'DC2626'), _srKpi('Kayıp / hurda', count(array_filter($h, fn ($x) => in_array($x['ham_durum'], ['kayip', 'hurda'], true))), 'sayi', '64748B'),
                        _srKpi('Garantisi 30 günde biten', count(array_filter($h, fn ($x) => $x['garanti_kalan'] !== null && $x['garanti_kalan'] >= 0 && $x['garanti_kalan'] <= 30)), 'sayi', 'D97706'), _srKpi('Toplam alış bedeli', round(array_sum(array_column($h, 'bedel')), 2), 'tl', '4C1D95'),
                        _srKpi('Bakım / onarım maliyeti', round(array_sum(array_column($h, 'bakim_tl')), 2), 'tl', 'B45309')],
                        [$blok('Duruma göre', 'pie', '5B21B6', 'Durum', $sayD($h, fn ($x) => $x['durum'])), _srBlok('En çok onarım maliyeti', 'bar', 'B45309', [['Donanım', 'm'], ['Maliyet', 'tl'], ['', 'm']], collect($h)->filter(fn ($x) => $x['bakim_tl'] > 0)->sortByDesc('bakim_tl')->take(10)->map(fn ($x) => [$x['ad'], $x['bakim_tl'], ''])->values()->all())], $bos),
                    _srSayfa('BT Donanımı', 'Demirbaş kayıtlarından kategori ya da adında bilgisayar, tablet, yazıcı, modem, kamera, POS vb. geçenler. Bakım / arıza tüm zamanlar.', [['Donanım', 'm', 22], ['Kategori', 'm', 14], ['Marka / Model', 'm', 16], ['Seri No', 'm', 12], ['Konum', 'm', 12], ['Durum', 'm', 10], ['Alış', 't', 11], ['Bedel', 'tl', 11], ['Garanti Bitiş', 't', 11], ['Garanti (gün)', 's', 10], ['Arıza', 's', 7, 'top'], ['Bakım Maliyeti', 'tl', 12, 'top']],
                        array_map(fn ($x) => [$x['ad'], $x['kategori'], $x['marka'], $x['seri'] ?: '—', $x['lokasyon'], $x['durum'], $x['alis'], $x['bedel'], $x['garanti'], $x['garanti_kalan'], $x['ariza'], $x['bakim_tl']], $h), $bos ?? 'BT donanımı yok.'),
                ];

            // ---------------- ERİŞİM & GÜVENLİK ----------------
            case 'aktivite':
                $ak = _itrAktivite($sid, $bas, $bit); $ako = _itrAktivite($sid, $dn['obas'], $dn['obit']); $md = array_values(array_filter($ak, fn ($x) => _itrMesaiDisi($x['saat'])));
                $kisi = collect($ak)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->first()['rol'] ?: '—', $g->count(), $g->where('kategori', 'giris')->count(), $g->filter(fn ($x) => _itrMesaiDisi($x['saat']))->count(), $g->pluck('ip')->filter()->unique()->count(), $g->pluck('kategori')->countBy()->sortDesc()->keys()->map(fn ($k) => _itrKatAd($k))->take(3)->implode(', '), date('Y-m-d H:i', $g->max('ts'))])->sortByDesc(2)->values()->all();
                $saat = []; for ($h = 0; $h < 24; $h++) $saat[$h] = 0; foreach ($ak as $x) $saat[$x['saat']]++;
                return [
                    $pano([_srKpi('İşlem', count($ak), 'sayi', '475569'), _srKpi('Önceki dönem', count($ako), 'sayi', '334155'), _srKpi('İşlem yapan kişi', count($kisi), 'sayi', '1E293B'), _srKpi('Mesai dışı (00-06)', count($md), 'sayi', 'C2410C'),
                        _srKpi('Farklı IP', count(array_unique(array_filter(array_column($ak, 'ip')))), 'sayi', '64748B'), _srKpi('En yoğun saat', count($ak) ? sprintf('%02d:00', array_search(max($saat), $saat)) : '—', 'metin', '4F46E5')],
                        [_srBlok('Saatlere göre işlem', 'col', '475569', [['Saat', 'm'], ['İşlem', 's'], ['', 'm']], array_map(fn ($h, $v) => [sprintf('%02d', $h), $v, ''], array_keys($saat), array_values($saat))), $blok('Kategoriye göre', 'pie', '334155', 'Kategori', $sayD($ak, fn ($x) => _itrKatAd($x['kategori'])))], !$ak ? $bosAkt : null),
                    _srSayfa('Kişi Bazında', 'Mesai dışı = 00:00–05:59. Kayıt yalnız değiştiren işlemleri ve girişleri içerir (listeleme / görüntüleme kaydedilmez; finansal işlemler kendi kayıtlarında).', [['Personel', 'm', 20], ['Rol', 'm', 10], ['İşlem', 's', 8, 'top bar'], ['Giriş', 's', 7, 'top'], ['Mesai Dışı', 's', 9, 'top esik:1'], ['Farklı IP', 's', 8], ['En Çok', 'm', 28], ['Son İşlem', 'm', 16]], $kisi, $bosAkt),
                    _srSayfa('Mesai Dışı İşlemler', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Personel', 'm', 18], ['İşlem', 'm', 32], ['Kategori', 'm', 14], ['IP', 'm', 14]], array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['ad'], $x['aksiyon'], _itrKatAd($x['kategori']), $x['ip'] ?: '—'], $md), $ak ? 'Mesai dışı işlem yok.' : $bosAkt),
                    _srSayfa('En Sık İşlemler', '', [['İşlem', 'm', 40], ['Kategori', 'm', 14], ['Adet', 's', 8, 'top bar'], ['Kişi', 's', 7]], collect($ak)->groupBy('aksiyon')->map(fn ($g, $a) => [$a ?: '—', _itrKatAd($g->first()['kategori']), $g->count(), $g->pluck('pid')->unique()->count()])->sortByDesc(2)->take(40)->values()->all(), $bosAkt),
                ];
            case 'giris':
                $ak = _itrAktivite($sid, $bas, $bit); $gir = array_values(array_filter($ak, fn ($x) => $x['kategori'] === 'giris')); $hat = array_values(array_filter($ak, fn ($x) => $x['kategori'] === 'giris_hata'));
                $kanal = fn ($x) => str_contains($x['aksiyon'], 'Uygulama') ? 'Uygulama' : (str_contains($x['aksiyon'], 'Web') ? 'Web panel' : 'Diğer');
                $bos = !$d['giris'] ? _itrBos('aktivite_yok', $dn) : _itrBos('giris', $dn);
                return [
                    $pano([_srKpi('Giriş', count($gir), 'sayi', '334155'), _srKpi('Giriş yapan kişi', count(array_unique(array_column($gir, 'pid'))), 'sayi', '475569'), _srKpi('Hatalı PIN denemesi', $d['giris_hata'] ? count($hat) : 'Kaydedilmiyor', $d['giris_hata'] ? 'sayi' : 'metin', 'DC2626'),
                        _srKpi('Mesai dışı giriş', count(array_filter($gir, fn ($x) => _itrMesaiDisi($x['saat']))), 'sayi', 'C2410C'), _srKpi('Farklı IP', count(array_unique(array_filter(array_column($gir, 'ip')))), 'sayi', '64748B')],
                        [$blok('Kanala göre', 'pie', '334155', 'Kanal', $sayD($gir, $kanal)), _srBlok('Günlere göre giriş', 'col', '475569', [['Gün', 'm'], ['Giriş', 's'], ['Hatalı', 's']], array_map(fn ($t) => [date('d.m', strtotime($t)), count(array_filter($gir, fn ($x) => $x['tarih'] === $t)), count(array_filter($hat, fn ($x) => $x['tarih'] === $t))], _itrGunler($bas, $bit)))], !$gir ? $bos : null),
                    _srSayfa('Kişi Bazında Girişler', '', [['Personel', 'm', 20], ['Rol', 'm', 10], ['Giriş', 's', 8, 'top bar'], ['Web', 's', 7], ['Uygulama', 's', 9], ['Mesai Dışı', 's', 9, 'esik:1'], ['IP Sayısı', 's', 8], ['İlk', 'm', 16], ['Son', 'm', 16]],
                        collect($gir)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->first()['rol'] ?: '—', $g->count(), $g->filter(fn ($x) => $kanal($x) === 'Web panel')->count(), $g->filter(fn ($x) => $kanal($x) === 'Uygulama')->count(), $g->filter(fn ($x) => _itrMesaiDisi($x['saat']))->count(), $g->pluck('ip')->filter()->unique()->count(), date('Y-m-d H:i', $g->min('ts')), date('Y-m-d H:i', $g->max('ts'))])->sortByDesc(2)->values()->all(), $bos),
                    _srSayfa('Hatalı PIN Denemeleri', 'Aynı IP\'den kısa sürede çok deneme PIN tahmini girişimi olabilir (sistem 10 hatalı denemede geçici kilit uygular).', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Kanal', 'm', 22], ['IP', 'm', 16]], array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['aksiyon'], $x['ip'] ?: '—'], $hat), $d['giris_hata'] ? 'Seçili dönemde hatalı PIN denemesi yok.' : _itrBos('giris_hata', $dn)),
                    _srSayfa('Giriş Kayıtları', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Personel', 'm', 20], ['Rol', 'm', 10], ['Kanal', 'm', 12], ['IP', 'm', 16]], array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['ad'], $x['rol'] ?: '—', $kanal($x), $x['ip'] ?: '—'], array_reverse($gir)), $bos),
                ];
            case 'hesap':
                $h = _itrHesap($sid); $k = $h['kisi']; $bul = array_values(array_filter($k, fn ($x) => $x['bulgu'])); $sayB = []; foreach ($k as $x) foreach ($x['bulgu'] as $b) { $b = preg_replace('/^\d+ gündür giriş yok$/u', '60+ gündür giriş yok', $b); $sayB[$b] = ($sayB[$b] ?? 0) + 1; }
                return [
                    $pano([_srKpi('Aktif hesap', count(array_filter($k, fn ($x) => $x['aktif'])), 'sayi', '1E293B'), _srKpi('Yetkili (patron / müdür)', count(array_filter($k, fn ($x) => $x['aktif'] && $x['yetkili'])), 'sayi', '334155'),
                        _srKpi('Pasif hesapta açık oturum', count(array_filter($k, fn ($x) => !$x['aktif'] && ($x['oturum'] || $x['cihaz']))), 'sayi', 'DC2626'), _srKpi('Ortak PIN grubu', $h['ortak_pin_grup'], 'sayi', 'B91C1C'),
                        _srKpi('Tahmin edilebilir PIN', $sayB['Tahmin edilebilir PIN'] ?? 0, 'sayi', 'C2410C'), _srKpi('Bulgusu olan hesap', count($bul), 'sayi', '7F1D1D')],
                        [$blok('Bulgu türüne göre', 'bar', '1E293B', 'Bulgu', $sayB)], !$k ? 'Personel kaydı yok.' : null),
                    _itrSayfaHesap($bul, 'Hesap güvenliği bulgusu yok.'),
                    array_merge(_itrSayfaHesap($k, 'Personel kaydı yok.'), ['ad' => 'Tüm Hesaplar']),
                ];
            case 'yetki':
                $ak = array_values(array_filter(_itrAktivite($sid, $bas, $bit), fn ($x) => in_array($x['kategori'], ['personel', 'guvenlik', 'ayar', 'menu', 'recete'], true) || str_starts_with($x['yol'], 'api/profil/')));
                $kritik = fn ($x) => $x['kategori'] === 'guvenlik' || str_contains(mb_strtolower($x['aksiyon']), 'yetki') || str_contains(mb_strtolower($x['aksiyon']), 'pin');
                return [
                    $pano([_srKpi('Değişiklik', count($ak), 'sayi', '0F172A'), _srKpi('Yetki / PIN / güvenlik', count(array_filter($ak, $kritik)), 'sayi', 'DC2626'), _srKpi('Ayar değişikliği', count(array_filter($ak, fn ($x) => $x['kategori'] === 'ayar')), 'sayi', '334155'),
                        _srKpi('Menü / reçete değişikliği', count(array_filter($ak, fn ($x) => in_array($x['kategori'], ['menu', 'recete'], true))), 'sayi', '475569'), _srKpi('Değişiklik yapan kişi', count(array_unique(array_column($ak, 'pid'))), 'sayi', '64748B')],
                        [$blok('Kategoriye göre', 'pie', '0F172A', 'Kategori', $sayD($ak, fn ($x) => _itrKatAd($x['kategori']))), $blok('Kişiye göre', 'bar', '334155', 'Personel', $sayD($ak, fn ($x) => $x['ad']))], !$ak ? (!$d['aktivite'] ? _itrBos('aktivite_yok', $dn) : 'Seçili dönemde yetki, ayar, menü ya da reçete değişikliği yok.') : null),
                    _srSayfa('Yetki, PIN & Güvenlik', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Kim', 'm', 18], ['Rol', 'm', 9], ['Değişiklik', 'm', 34], ['Ayrıntı', 'm', 30], ['IP', 'm', 14]],
                        array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['ad'], $x['rol'] ?: '—', $x['aksiyon'], $x['aciklama'] ?: '—', $x['ip'] ?: '—'], array_reverse(array_values(array_filter($ak, $kritik)))), 'Seçili dönemde yetki / PIN / güvenlik değişikliği yok.'),
                    _srSayfa('Tüm Değişiklikler', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Kim', 'm', 18], ['Kategori', 'm', 13], ['Değişiklik', 'm', 34], ['Ayrıntı', 'm', 30]],
                        array_map(fn ($x) => [$x['tarih'], $x['hm'], $x['ad'], _itrKatAd($x['kategori']), $x['aksiyon'], $x['aciklama'] ?: '—'], array_reverse($ak)), !$d['aktivite'] ? _itrBos('aktivite_yok', $dn) : 'Seçili dönemde değişiklik yok.'),
                ];

            // ---------------- AI KULLANIMI ----------------
            case 'ai_kullanim':
                $ai = _itrAiKullanim($sid, $bas, $bit); $buAy = date('Y-m'); $kul = $ai['ay'][$buAy] ?? 0; $gunNo = (int) date('j'); $ayGun = (int) date('t');
                $tahmin = in_array($buAy, $ai['aylar'], true) && $gunNo > 0 ? (int) round($kul / $gunNo * $ayGun) : null; $bos = !$d['ai_kullanim'] ? _itrBos('ai_yok', $dn) : null;
                return [
                    $pano([_srKpi('Dönemde AI yanıtı', $ai['toplam'], 'sayi', '9333EA'), _srKpi('Aylık hak (paket)', $ai['hak'] ?? 'Bilinmiyor', $ai['hak'] ? 'sayi' : 'metin', '7E22CE'), $kpiY('Bu ay doluluk', $ai['hak'] ? round($kul / $ai['hak'] * 100, 1) : null, $ai['hak'] && $kul / max(1, $ai['hak']) > 0.8 ? 'DC2626' : '6B21A8'),
                        $kpiS('Bu ay tahmini ay sonu', $tahmin, '581C87', '—'), _srKpi('Kullanan kişi', count($ai['kisi']), 'sayi', 'A855F7')],
                        [_srBlok('Aylara göre kullanım', 'col', '9333EA', [['Ay', 'm'], ['Kullanım', 's'], ['Hak', 's']], array_map(fn ($a) => [$a, $ai['ay'][$a] ?? 0, $ai['hak'] ?? 0], $ai['aylar'])), $blok('Kişiye göre', 'pie', '7E22CE', 'Kişi', $ai['kisi'])], $bos),
                    ['grafiksiz' => true] + _srSayfa('Aylık Kullanım', 'Sayaç yalnız gerçek yapay zekâ çağrılarını sayar (kural motoru ve önbellekten gelen cevaplar bedava, sayılmaz). Ay bazında tutulur; dönem seçimi ilgili ayları kapsar.', [['Ay', 'm', 10], ['Kullanım', 's', 10, 'top'], ['Aylık Hak', 's', 10], ['Doluluk %', 'y', 10, 'esik:80']],
                        array_map(fn ($a) => [$a, $ai['ay'][$a] ?? 0, $ai['hak'], $ai['hak'] ? round(($ai['ay'][$a] ?? 0) / $ai['hak'] * 100, 1) : null], $ai['aylar']), $bos ?? 'Bu aylarda AI kullanımı yok.'),
                    _srSayfa('Kişi Bazında', '', [['Kişi', 'm', 26], ['Kullanım', 's', 10, 'top bar'], ['Pay %', 'y', 8]], array_map(fn ($k, $v) => [$k, $v, $ai['toplam'] ? round($v / $ai['toplam'] * 100, 1) : null], array_keys($ai['kisi']), array_values($ai['kisi'])), $bos ?? 'Bu aylarda AI kullanımı yok.'),
                ];
            case 'ai_eylem':
                $ae = _itrAiEylem($sid, $bas, $bit); $z = _itrZincir(); $bos = !$d['ai_eylem'] ? _itrBos('ai_eylem_yok', $dn) : _itrBos('ai_eylem', $dn);
                $mod = collect($ae)->groupBy('modul')->map(fn ($g, $m) => [_itrModulAd($m), $g->count(), $g->where('karar', 'Onaylandı')->count(), $g->where('karar', 'Reddedildi')->count(), $g->where('karar', 'Zaman aşımı')->count(), $g->where('yurutme', 'Hata')->count(), round($g->where('karar', 'Onaylandı')->count() / max(1, $g->count()) * 100, 1)])->sortByDesc(1)->values()->all();
                return [
                    $pano([_srKpi('AI önerisi', count($ae), 'sayi', '7E22CE'), _srKpi('Onaylanan', count(array_filter($ae, fn ($x) => $x['karar'] === 'Onaylandı')), 'sayi', '059669'), _srKpi('Reddedilen', count(array_filter($ae, fn ($x) => $x['karar'] === 'Reddedildi')), 'sayi', 'B45309'),
                        _srKpi('Zaman aşımı / bekleyen', count(array_filter($ae, fn ($x) => in_array($x['karar'], ['Zaman aşımı', 'Bekliyor'], true))), 'sayi', '64748B'), _srKpi('Yürütme hatası', count(array_filter($ae, fn ($x) => $x['yurutme'] === 'Hata')), 'sayi', 'DC2626'),
                        _srKpi('Günlük bütünlüğü', is_array($z) ? (($z['saglam'] ?? false) ? 'Sağlam (' . number_format((int) ($z['sayi'] ?? 0), 0, ',', '.') . ' kayıt)' : 'KIRIK — kayıt #' . ($z['ilk_kirik_id'] ?? '?')) : 'Kontrol edilemedi', 'metin', is_array($z) && empty($z['saglam']) ? 'DC2626' : '059669')],
                        [$blok('Karara göre', 'pie', '7E22CE', 'Karar', $sayD($ae, fn ($x) => $x['karar'])), _srBlok('Modüle göre öneri', 'bar', '9333EA', [['Modül', 'm'], ['Öneri', 's'], ['Onay', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[2]], $mod))], !$ae ? $bos : null),
                    ['grafiksiz' => true] + _srSayfa('Modüle Göre', 'Onay oranı düşük modülde AI önerileri işletmeye uymuyor olabilir; yürütme hatası entegrasyon / yetki sorununa işaret eder. Bütünlük kontrolü tüm günlüğün imza zincirini doğrular (sistem geneli).', [['Modül', 'm', 14], ['Öneri', 's', 8, 'top'], ['Onay', 's', 7, 'top'], ['Red', 's', 7, 'top'], ['Zaman Aşımı', 's', 10, 'top'], ['Yürütme Hatası', 's', 12, 'top esik:1'], ['Onay %', 'y', 8]], $mod, $bos),
                    _srSayfa('Öneriler', '', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Modül', 'm', 10], ['Eylem', 'm', 18], ['Öneri', 'm', 40], ['Kullanıcı', 'm', 14], ['Karar', 'm', 11], ['Yürütme', 'm', 10], ['Sonuç', 'm', 30]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _itrModulAd($x['modul']), $x['intent'], mb_substr($x['ozet'], 0, 160), $x['kim'], $x['karar'], $x['yurutme'], mb_substr($x['mesaj'], 0, 120)], array_reverse($ae)), $bos),
                ];

            // ---------------- ENTEGRASYON ----------------
            case 'whatsapp':
                $h = _itrWaHat($sid); $m = _itrWaMesaj($sid, $bas, $bit); $gid = array_values(array_filter($m, fn ($x) => $x['yon'] !== 'gelen')); $bas2 = count(array_filter($gid, fn ($x) => $x['durum'] === 2)); $sms = count(array_filter($gid, fn ($x) => $x['durum'] === 3));
                $bos = !$d['wa_hat'] && !$d['wa_mesaj'] ? _itrBos('wa_yok', $dn) : null;
                $gun = array_map(fn ($t) => [date('d.m', strtotime($t)), count(array_filter($gid, fn ($x) => $x['tarih'] === $t && $x['durum'] === 1)), count(array_filter($gid, fn ($x) => $x['tarih'] === $t && in_array($x['durum'], [2, 3], true)))], _itrGunler($bas, $bit));
                return [
                    $pano([_srKpi('Hat (bağlı / toplam)', count(array_filter($h, fn ($x) => $x['bagli'])) . ' / ' . count($h), 'metin', '16A34A'), _srKpi('Gönderilen mesaj', count($gid), 'sayi', '15803D'), _srKpi('Başarısız', $bas2, 'sayi', 'DC2626'), _srKpi('SMS\'e düşen', $sms, 'sayi', 'D97706'),
                        $kpiY('Teslim oranı', $gid ? round(count(array_filter($gid, fn ($x) => $x['durum'] === 1)) / count($gid) * 100, 1) : null, '166534'), _srKpi('Gelen mesaj', count($m) - count($gid), 'sayi', '0F766E')],
                        [_srBlok('Günlere göre gönderilen / sorunlu', 'col', '16A34A', [['Gün', 'm'], ['Gönderildi', 's'], ['Sorunlu', 's']], $gun), $blok('En sık hata', 'bar', 'DC2626', 'Hata', collect($gid)->filter(fn ($x) => $x['hata'] !== '')->groupBy(fn ($x) => mb_substr($x['hata'], 0, 60))->map->count()->sortDesc()->take(8)->all())], $bos),
                    _srSayfa('WhatsApp Hatları', 'Anlık durum. "Bağlı değil" hatta mesaj gitmez; sistem SMS\'e düşebilir. Numaranın son 4 hanesi gizlendi.', [['Hat', 'm', 20], ['Amaç', 'm', 12], ['Numara', 'm', 16], ['Durum', 'm', 22], ['Aktif', 'm', 7], ['Varsayılan', 'm', 9], ['Son Güncelleme', 'm', 16]],
                        array_map(fn ($x) => [$x['ad'], $x['amac'], $x['numara'], $x['durum'], $x['aktif'] ? 'Evet' : 'Hayır', $x['varsayilan'] ? 'Evet' : '', $x['guncel']], $h), $bos ?? 'Çoklu WhatsApp hattı tanımlı değil (tek hat kullanılıyor olabilir).'),
                    _srSayfa('Mesaj Durumu', '', [['Durum', 'm', 16], ['Mesaj', 's', 9, 'top bar'], ['Pay %', 'y', 8]], collect($gid)->groupBy('durum')->map(fn ($g, $k) => [_itrWaDurumAd((int) $k), $g->count(), round($g->count() / max(1, count($gid)) * 100, 1)])->values()->all(), $d['wa_mesaj'] ? _itrBos('wa', $dn) : _itrBos('wa_yok', $dn)),
                ];
            case 'ebelge':
                $eb = _itrEbelge($sid, $bas, $bit); $hata = array_values(array_filter($eb, fn ($x) => $x['durum'] === 'hata')); $bos = !$d['ebelge'] ? _itrBos('ebelge_yok', $dn) : _itrBos('ebelge', $dn);
                return [
                    $pano([_srKpi('Belge', count($eb), 'sayi', 'D97706'), _srKpi('Hatalı', count($hata), 'sayi', 'DC2626'), $kpiY('Hata oranı', $eb ? round(count($hata) / count($eb) * 100, 1) : null, 'B91C1C'), _srKpi('Hatalı belge tutarı', round(array_sum(array_column($hata, 'tutar')), 2), 'tl', '92400E'),
                        _srKpi('Son hata', $hata ? end($hata)['tarih'] . ' ' . end($hata)['saat'] : 'Yok', 'metin', '64748B')],
                        [$blok('Türe göre', 'pie', 'D97706', 'Tür', $sayD($eb, fn ($x) => _itrEbelgeTipAd($x['tip']))), $blok('Duruma göre', 'bar', 'B45309', 'Durum', $sayD($eb, fn ($x) => $x['durum'] ?: '—')), $blok('En sık hata', 'bar', 'DC2626', 'Hata', collect($hata)->groupBy(fn ($x) => mb_substr($x['hata'] ?: 'Mesajsız', 0, 60))->map->count()->sortDesc()->take(8)->all())], !$eb ? $bos : null),
                    _srSayfa('Hatalı Belgeler', 'Hatalı mali fiş / e-belge yasal yükümlülük doğurur: ÖKC bağlantısı, kâğıt, entegratör kimliği ve sertifika kontrol edilmeli; belge tekrar gönderilmeli.', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Tür', 'm', 14], ['Belge No', 'm', 16], ['Entegratör', 'm', 12], ['Tutar', 'tl', 12, 'top'], ['Hata', 'm', 50]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _itrEbelgeTipAd($x['tip']), $x['no'] ?: '—', $x['entegrator'] ?: '—', $x['tutar'], $x['hata'] ?: '—'], $hata), $eb ? 'Seçili dönemde hatalı belge yok.' : $bos),
                ];
            case 'offline':
                $of = _itrOffline($sid, $bas, $bit); $gk = array_values(array_filter(array_column($of, 'gecikme'), fn ($x) => $x !== null)); $bos = !$d['offline'] ? _itrBos('offline_yok', $dn) : 'Seçili dönemde çevrimdışı senkronla gelen adisyon yok.';
                return [
                    $pano([_srKpi('Senkronla gelen adisyon', count($of), 'sayi', '0369A1'), _srKpi('Tutar', round(array_sum(array_column($of, 'toplam')), 2), 'tl', '075985'), _srKpi('Etkilenen gün', count(array_unique(array_column($of, 'tarih'))), 'sayi', '0C4A6E'),
                        $kpiS('Ort. ulaşma süresi (dk)', $gk ? round(array_sum($gk) / count($gk)) : null, '0284C7', '—'), $kpiS('En uzun (dk)', $gk ? max($gk) : null, 'DC2626', '—')],
                        [$blok('Günlere göre', 'col', '0369A1', 'Gün', $sayD($of, fn ($x) => date('d.m', strtotime($x['tarih']))))], !$of ? $bos : null),
                    _srSayfa('Senkronla Gelen Adisyonlar', 'Ulaşma süresi = adisyonun tablette açıldığı an ile sunucuya ulaştığı an arası (dakika). Uzun süre = uzun internet kesintisi; o arada patron paneli bu satışları görmez.', [['Açılış', 'm', 16], ['Sunucuya Ulaştı', 'm', 16], ['Süre (dk)', 's', 9, 'esik:60'], ['Tutar', 'tl', 12, 'top'], ['Durum', 'm', 10], ['Açan', 'm', 18]],
                        array_map(fn ($x) => [$x['acilis'], $x['gelis'], $x['gecikme'], $x['toplam'], $x['durum'], $x['kim']], $of), $bos),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_itrAiBaglam')) {
    function _itrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $ar = _itrArizalar($sid, $bas, $bit); $ao = _itrArizaOzet($ar); $c = _itrCihazlar($sid); $sv = _itrSurum($sid); $ak = _itrAktivite($sid, $bas, $bit);
        $h = _itrHesap($sid); $yd = _itrYedek($sid, $bas, $bit); $wa = array_filter(_itrWaMesaj($sid, $bas, $bit), fn ($x) => $x['yon'] !== 'gelen'); $eb = _itrEbelge($sid, $bas, $bit); $ai = _itrAiKullanim($sid, $bas, $bit); $ae = _itrAiEylem($sid, $bas, $bit);
        $bul = []; foreach ($h['kisi'] as $x) foreach ($x['bulgu'] as $b) { $b = preg_replace('/^\d+ gündür giriş yok$/u', '60+ gündür giriş yok', $b); $bul[$b] = ($bul[$b] ?? 0) + 1; }
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _itrVeriDurumu($sid),
            'destek' => $ao + ['onceki' => _itrArizaOzet(_itrArizalar($sid, $dn['obas'], $dn['obit'])), 'hedef_saat' => ['yuksek' => 4, 'orta' => 24, 'dusuk' => 72], 'acik_en_eski' => array_map(fn ($x) => ['talep' => $x['baslik'], 'gun' => $x['yas'], 'oncelik' => $x['oncelik']], array_slice(array_values(array_filter($ar, fn ($x) => $x['durum'] === 'acik')), 0, 5)),
                'tekrar_eden' => collect($ar)->groupBy(fn ($x) => $x['cihaz'] !== '—' ? $x['cihaz'] : $x['baslik'])->map->count()->filter(fn ($n) => $n > 1)->sortDesc()->take(5)->all()],
            'cihaz' => ['toplam' => count($c), 'durum' => collect($c)->groupBy('durum')->map->count()->all(), 'eski_surum' => count(array_filter($sv['liste'], fn ($x) => !$x['guncel'])), 'en_yeni_surum' => $sv['en'],
                'gorunmeyen' => array_map(fn ($x) => $x['ad'] . ' (' . $x['durum'] . ')', array_slice(array_values(array_filter($c, fn ($x) => in_array($x['durum'], ['7 günden eski', 'Hiç bağlanmadı'], true))), 0, 6))],
            'takvim' => ['lisans' => array_map(fn ($x) => ['kalem' => $x['baslik'], 'bitis' => $x['son'], 'durum' => $x['drm']], array_slice(_itrTakvim($sid, 'lisans'), 0, 8)), 'son_yedek' => $yd['son'], 'yedek_yasi_gun' => $yd['yas'], 'donemde_en_uzun_yedeksiz_gun' => $yd['en_uzun'], 'kvkk_kaydi' => count(_itrKayit($sid, 'kvkk'))],
            'erisim' => ['islem' => count($ak), 'giris' => count(array_filter($ak, fn ($x) => $x['kategori'] === 'giris')), 'hatali_giris' => count(array_filter($ak, fn ($x) => $x['kategori'] === 'giris_hata')), 'mesai_disi_islem' => count(array_filter($ak, fn ($x) => _itrMesaiDisi($x['saat']))),
                'yetki_guvenlik_degisikligi' => count(array_filter($ak, fn ($x) => $x['kategori'] === 'guvenlik' || str_contains(mb_strtolower($x['aksiyon']), 'yetki'))), 'hesap_bulgulari' => $bul, 'ortak_pin_grubu' => $h['ortak_pin_grup']],
            'ai' => ['kullanim' => $ai['toplam'], 'aylik_hak' => $ai['hak'], 'ay' => $ai['ay'], 'eylem_onerisi' => count($ae), 'onay' => count(array_filter($ae, fn ($x) => $x['karar'] === 'Onaylandı')), 'red' => count(array_filter($ae, fn ($x) => $x['karar'] === 'Reddedildi')), 'yurutme_hatasi' => count(array_filter($ae, fn ($x) => $x['yurutme'] === 'Hata'))],
            'entegrasyon' => ['whatsapp_gonderilen' => count($wa), 'whatsapp_basarisiz' => count(array_filter($wa, fn ($x) => $x['durum'] === 2)), 'whatsapp_sms_dusen' => count(array_filter($wa, fn ($x) => $x['durum'] === 3)), 'whatsapp_hat_bagli_degil' => count(array_filter(_itrWaHat($sid), fn ($x) => $x['aktif'] && !$x['bagli'])),
                'ebelge' => count($eb), 'ebelge_hata' => count(array_filter($eb, fn ($x) => $x['durum'] === 'hata')), 'cevrimdisi_senkron_adisyon' => count(_itrOffline($sid, $bas, $bit))]];
    }
    function _itrAiNabiz($sid, array $dn)
    {
        $b = _itrAiBaglam($sid, $dn); $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.'); $ds = $b['destek']; $cd = $b['cihaz']['durum'];
        $gor = ($cd['7 günden eski'] ?? 0) + ($cd['Hiç bağlanmadı'] ?? 0); $hb = array_sum($b['erisim']['hesap_bulgulari']); $yy = $b['takvim']['yedek_yasi_gun'];
        return [
            ['renk' => '#DC2626', 'kat' => 'destek', 'ikon' => 'build_circle', 'etiket' => 'Açık talep', 'deger' => (string) $ds['acik'], 'alt' => $ds['n'] . ' talep · ort. ' . ($ds['mttr'] !== null ? number_format($ds['mttr'], 1, ',', '.') . ' saatte çözüldü' : 'çözüm süresi ölçülemedi'), 'sinif' => $ds['acik'] ? 'turuncu' : 'yesil'],
            ['renk' => '#047857', 'kat' => 'destek', 'ikon' => 'timer', 'etiket' => 'Hedef süre uyumu', 'deger' => $y($ds['sla']), 'alt' => 'Yüksek 4 / orta 24 / düşük 72 saat', 'sinif' => $ds['sla'] === null ? '' : ($ds['sla'] >= 80 ? 'yesil' : 'kirmizi')],
            ['renk' => '#7C3AED', 'kat' => 'cihaz', 'ikon' => 'devices', 'etiket' => 'Görünmeyen cihaz', 'deger' => (string) $gor, 'alt' => ($cd['Çevrimiçi'] ?? 0) . ' / ' . $b['cihaz']['toplam'] . ' çevrimiçi · ' . $b['cihaz']['eski_surum'] . ' eski sürüm', 'sinif' => $gor ? 'turuncu' : 'yesil'],
            ['renk' => '#0E7490', 'kat' => 'takvim', 'ikon' => 'backup', 'etiket' => 'Son yedek', 'deger' => $yy === null ? 'Yok' : $yy . ' gün önce', 'alt' => 'En uzun yedeksiz: ' . $b['takvim']['donemde_en_uzun_yedeksiz_gun'] . ' gün', 'sinif' => $yy === null || $yy > 7 ? 'kirmizi' : 'yesil'],
            ['renk' => '#1E293B', 'kat' => 'erisim', 'ikon' => 'admin_panel_settings', 'etiket' => 'Hesap güvenliği bulgusu', 'deger' => (string) $hb, 'alt' => $b['erisim']['mesai_disi_islem'] . ' mesai dışı işlem', 'sinif' => $hb ? 'turuncu' : 'yesil'],
            ['renk' => '#9333EA', 'kat' => 'yz', 'ikon' => 'smart_toy', 'etiket' => 'AI kullanımı', 'deger' => number_format($b['ai']['kullanim'], 0, ',', '.'), 'alt' => $b['ai']['aylik_hak'] ? 'Aylık hak ' . number_format($b['ai']['aylik_hak'], 0, ',', '.') . ' · ' . $b['ai']['yurutme_hatasi'] . ' eylem hatası' : $b['ai']['eylem_onerisi'] . ' AI eylem önerisi', 'sinif' => $b['ai']['yurutme_hatasi'] ? 'kirmizi' : ''],
        ];
    }
}

// ---------- Modül kaydı ----------
_drKaydet('it', [
    'ad' => 'IT', 'baslik' => 'IT Raporları', 'yol' => '/it-raporlar', 'grup' => 'IT / Bilgi İşlem', 'depKodlari' => ['it'],
    'ensure' => '_itrEnsure', 'katalog' => '_itrKatalog', 'kategoriler' => '_itrKategoriler', 'veri' => '_itrVeriDurumu', 'rapor' => '_itrRapor',
    'bosTablolar' => ['it_kayitlari', 'cihazlar', 'aktivite_loglari', 'ai_eylem_log', 'patron_ai_tuketim', 'wa_hatlar', 'wa_mesaj_loglari', 'e_faturalar', 'cihaz_tokenlari'],
    'olcek' => ['kucuk' => ['donemsel', 'destek', 'ai'], 'orta' => ['cihaz', 'erisim', 'takvim']],
    'modAd' => ['ariza' => 'Arıza / destek kaydı', 'lisans' => 'Lisans / domain / SSL', 'yedek' => 'Yedek kaydı', 'kvkk' => 'KVKK kaydı', 'bakim' => 'Periyodik bakım', 'it_gorev' => 'IT rutin görevi', 'cihaz' => 'Bağlı cihaz', 'donanim' => 'BT donanımı (demirbaş)',
        'aktivite' => 'Aktivite kaydı', 'giris' => 'Giriş kaydı', 'giris_hata' => 'Hatalı PIN kaydı', 'personel' => 'Personel hesabı', 'ai_kullanim' => 'AI kullanım sayacı', 'ai_eylem' => 'AI eylem günlüğü',
        'wa_hat' => 'WhatsApp hattı', 'wa_mesaj' => 'WhatsApp mesaj günlüğü', 'ebelge' => 'e-Belge / mali fiş', 'offline' => 'Çevrimdışı senkron'],
    'modLink' => ['ariza' => ['/it-destek', 'IT / Bilgi İşlem > Arıza kaydı'], 'lisans' => ['/it-kayit?tip=lisans', 'IT Kayıt Defteri > Lisans / Domain / SSL'], 'yedek' => ['/it-kayit?tip=yedek', 'IT Kayıt Defteri > Yedekleme'],
        'kvkk' => ['/it-kayit?tip=kvkk', 'IT Kayıt Defteri > KVKK'], 'bakim' => ['/it-kayit?tip=bakim', 'IT Kayıt Defteri > Periyodik Bakım'], 'it_gorev' => ['/it-destek', 'IT iş ekranında tekrarlı görev'], 'cihaz' => ['/bagli-cihazlar', 'Bağlı Cihazlar'],
        'donanim' => ['/demirbas', 'Demirbaş (BT donanımı, garanti tarihiyle)'], 'aktivite' => ['/hareketler', 'Hareketler (otomatik)'], 'giris' => ['/hareketler', 'PIN ile giriş (otomatik)'], 'giris_hata' => [null, 'Hatalı PIN kaydı (Faz 2)'],
        'personel' => ['/personel', 'Personel'], 'ai_kullanim' => [null, 'AI asistan kullanımı (otomatik)'], 'ai_eylem' => ['/ai-eylem-log', 'AI Eylem Günlüğü'], 'wa_hat' => ['/whatsapp', 'WhatsApp Yönetimi'],
        'wa_mesaj' => ['/whatsapp', 'WhatsApp mesaj gönderimi (otomatik)'], 'ebelge' => [null, 'e-Arşiv / ÖKC ayarları'], 'offline' => [null, 'Tablet çevrimdışı modu (otomatik)']],
    'ai' => [
        'gerekli' => ['ariza', 'cihaz', 'aktivite', 'yedek', 'lisans'],
        'hero' => ['IT tarafını 20 saniyede özetleyeyim.', 'Arıza talepleri ve çözüm süresi, cihaz sağlığı ve sürüm uyumu, lisans / SSL / yedek / KVKK takvimi, girişler ve hesap güvenliği, AI kullanımı ve eylem denetimi, WhatsApp, e-belge ve çevrimdışı senkron sağlığını okur.'],
        'sorular' => ['Sistemde şu an en büyük risk ne?', 'Hangi cihaz sürekli arıza veriyor?', 'Yedeklerim yeterli mi?', 'İşten ayrılan biri hâlâ girebilir mi?', 'Hangi lisans / SSL yakında bitiyor?', 'AI kotam ay sonuna yeter mi?'],
        'adimlar' => ['Arıza talepleri okunuyor…', 'Çözüm süreleri hedefle karşılaştırılıyor…', 'Cihaz, sürüm ve yedek durumu kontrol ediliyor…', 'Giriş ve hesap güvenliği taranıyor…', 'AI, WhatsApp ve e-belge kayıtları inceleniyor…', 'Öneriler yazılıyor…'],
        'selam' => 'bilgi işlem',
        'sys' => 'Sen bir restoranın bilgi işlemini patron ve IT sorumlusu için analiz eden deneyimli bir BT operasyon ve bilgi güvenliği danışmanısın (ITIL, KVKK 6698, ÖKC / e-Arşiv mevzuatını bil). '
            . 'Önceliği satışı durduran risklere (kasa / yazıcı / mali fiş / internet), veri kaybına (yedek yaşı) ve yetkisiz erişime (pasif hesapta açık oturum, ortak PIN) ver. Çözüm süresi hedefleri: yüksek 4, orta 24, düşük 72 saat. '
            . 'PIN, IP gibi ayrıntıları tekrarlama; teknik olmayan patrona sade dille, somut adımla yaz.',
        'baglam' => '_itrAiBaglam',
        'veriVar' => fn ($b) => array_sum(array_map('intval', $b['kullanilan_moduller'])) > 0,
        'nabiz' => '_itrAiNabiz',
    ],
]);

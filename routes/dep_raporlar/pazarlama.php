<?php
// =====================================================================
// PAZARLAMA RAPORLAR & ANALİZLER — /pazarlama-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: adisyonlar (kanal, platform, indirim, ikram, musteri_id), iptal_indirim_loglari (indirim/ikram sebepleri), indirimler (kurallar + kupon),
//       happy_hour + adisyon_kalemleri (happy hour satışı), fix_menu, kampanyalar (sadakat kampanyaları), kampanya_gonderimleri (WhatsApp),
//       kampanya_gonderim (push), app_banner + musteri_kart_kullanim (uygulama kampanya kartları), rezervasyonlar.kaynak, wa_mesaj_loglari,
//       wa_bildirim_gecmisi (otomatik mesajlar), cihaz_tokenlari (uygulama / push abonesi), social_posts (içerik), musteriler.
// Sadakat puanı, ödül, çark, memnuniyet ve müşteri değeri (RFM/CLV) CRM raporlarındadır (routes/dep_raporlar/crm.php).
// Faz 2 (bundan sonrasını ölçer): pzr_kampanya_alici — kampanyayı alan müşteri listesi (_pzrAliciKaydet); alıcı bazlı dönüşüm raporu bu tablodan.
// =====================================================================

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2: kampanya alıcı kaydı ----------
if (!function_exists('_pzrEnsure')) {
    function _pzrEnsure()
    {
        if (cache()->get('pzr_ensure_v1')) return;
        try {
            if (!Schema::hasTable('pzr_kampanya_alici')) Schema::create('pzr_kampanya_alici', function ($t) {
                $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->string('kanal', 8); $t->unsignedBigInteger('gonderim_id');
                $t->unsignedBigInteger('musteri_id')->index(); $t->timestamp('created_at')->nullable(); $t->index(['kanal', 'gonderim_id']);
            });
        } catch (\Throwable $e) {}
        cache()->put('pzr_ensure_v1', 1, now()->addHours(12));
    }
    // Kampanya gönderilince alıcıları yaz ($kanal 'wa' = kampanya_gonderimleri.id, 'push' = kampanya_gonderim.id).
    // Push'ta yalnız uygulaması (bildirim izni) olan müşteriler gerçekten alıcıdır; onlar süzülür. Hata olursa sessizce geçer (gönderimi bozmaz).
    function _pzrAliciKaydet($sid, $kanal, $gonderimId, array $musteriIds)
    {
        try {
            _pzrEnsure();
            $ids = array_values(array_unique(array_filter(array_map('intval', $musteriIds))));
            if (!$ids || !$gonderimId || !Schema::hasTable('pzr_kampanya_alici')) return;
            if ($kanal === 'push' && Schema::hasTable('cihaz_tokenlari')) {
                $tok = DB::table('cihaz_tokenlari')->where('sube_id', $sid)->where('kullanici_tipi', 'musteri')->whereNotNull('personel_id')->distinct()->pluck('personel_id')->map(fn ($x) => (int) $x)->all();
                $ids = array_values(array_intersect($ids, $tok));
            }
            $now = now();
            foreach (array_chunk($ids, 400) as $parca) DB::table('pzr_kampanya_alici')->insert(array_map(fn ($m) => ['sube_id' => (int) $sid, 'kanal' => $kanal === 'push' ? 'push' : 'wa', 'gonderim_id' => (int) $gonderimId, 'musteri_id' => $m, 'created_at' => $now], $parca));
        } catch (\Throwable $e) {}
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_pzrAdisyon')) {
    function _pzrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    function _pzrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _pzrTlf($t) { $d = preg_replace('/\D/', '', (string) $t); return strlen($d) >= 7 ? substr($d, 0, 4) . '***' . substr($d, -2) : ($d ?: '—'); }
    // Satış kanalı adı: platform (uygulama, Getir, Yemeksepeti…) varsa o; yoksa kanal (salon, QR, paket)
    function _pzrKanalAd($kanal, $platform)
    {
        $p = mb_strtolower(trim((string) $platform), 'UTF-8'); $k = mb_strtolower(trim((string) $kanal), 'UTF-8');
        $pl = ['app' => 'Uygulama', 'uygulama' => 'Uygulama', 'getir' => 'Getir', 'yemeksepeti' => 'Yemeksepeti', 'trendyol' => 'Trendyol Yemek', 'migros' => 'Migros Yemek',
            'telefon' => 'Telefon', 'whatsapp' => 'WhatsApp', 'web' => 'Web sitesi', 'qr' => 'QR menü (masadan)'];
        if ($p !== '') return $pl[$p] ?? mb_convert_case($p, MB_CASE_TITLE, 'UTF-8');
        return ['salon' => 'Salon', 'qr' => 'QR menü (masadan)', 'paket' => 'Paket (telefon / elden)', 'gelal' => 'Gel-al', 'gel_al' => 'Gel-al'][$k] ?? ($k !== '' ? mb_convert_case($k, MB_CASE_TITLE, 'UTF-8') : 'Salon');
    }
    function _pzrOnline($a) { return !in_array($a['ham'], ['salon', 'qr', ''], true) || $a['platform'] !== ''; }
    // Ödenmiş adisyonlar (kapanış tarihine göre)
    function _pzrAdisyon($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('adisyonlar')) return $c[$key] = [];
        $sel = ['id', 'toplam', 'kapanis']; foreach (['ara_toplam', 'indirim', 'ikram', 'kanal', 'platform', 'musteri_id', 'misafir_sayisi'] as $k) $sel[] = _srKolon('adisyonlar', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereBetween('kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $t = strtotime((string) $r->kapanis); $top = (float) $r->toplam; $ind = (float) $r->indirim; $ikr = (float) $r->ikram; $ara = (float) $r->ara_toplam;
            $o[] = ['id' => (int) $r->id, 'toplam' => $top, 'ara' => $ara > 0 ? $ara : $top + $ind + $ikr, 'indirim' => $ind, 'ikram' => $ikr, 'tarih' => date('Y-m-d', $t), 'saat' => (int) date('G', $t), 'gun' => (int) date('N', $t),
                'kanal' => _pzrKanalAd($r->kanal, $r->platform), 'ham' => mb_strtolower((string) $r->kanal, 'UTF-8'), 'platform' => mb_strtolower(trim((string) $r->platform), 'UTF-8'), 'mid' => (int) $r->musteri_id, 'misafir' => (int) $r->misafir_sayisi];
        }
        return $c[$key] = $o;
    }
    function _pzrCiro(array $a) { return round(array_sum(array_column($a, 'toplam')), 2); }
    // Müşterinin tüm zamanlar sipariş geçmişi (ödenmiş, müşteri bağlı adisyonlar): ilk sipariş + kanalı, 2. sipariş, son, sayı, ciro, tarihler
    function _pzrGecmis($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srKolon('adisyonlar', 'musteri_id')) return $c[$sid] = [];
        $sel = ['musteri_id', 'kapanis', 'toplam']; foreach (['kanal', 'platform'] as $k) $sel[] = _srKolon('adisyonlar', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereNotNull('musteri_id')->where('musteri_id', '>', 0)->whereNotNull('kapanis')->orderBy('kapanis')->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $m = (int) $r->musteri_id; $t = substr((string) $r->kapanis, 0, 10);
            if (!isset($o[$m])) $o[$m] = ['ilk' => $t, 'ilkKanal' => _pzrKanalAd($r->kanal, $r->platform), 'ilkTutar' => (float) $r->toplam, 'ikinci' => null, 'son' => $t, 'n' => 0, 'ciro' => 0.0, 'tarihler' => []];
            elseif ($o[$m]['ikinci'] === null) $o[$m]['ikinci'] = $t;
            $o[$m]['son'] = $t; $o[$m]['n']++; $o[$m]['ciro'] += (float) $r->toplam; $o[$m]['tarihler'][] = $t;
        }
        return $c[$sid] = $o;
    }
    function _pzrSiparisVar(array $g, $mid, $bas, $bit) { foreach ($g[$mid]['tarihler'] ?? [] as $t) if ($t >= $bas && $t <= $bit) return true; return false; }
    function _pzrMusteriler($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('musteriler')) return $c[$sid] = [];
        try { $q = DB::table('musteriler')->where('sube_id', $sid); if (_srKolon('musteriler', 'silindi_at')) $q->whereNull('silindi_at'); $rows = $q->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) $o[(int) $r->id] = (object) ['id' => (int) $r->id, 'ad' => trim((string) ($r->ad ?? '') . ' ' . (string) ($r->soyad ?? '')) ?: 'Müşteri #' . $r->id, 'telefon' => (string) ($r->telefon ?? ''),
            'kayit' => substr((string) ($r->created_at ?? ''), 0, 10) ?: null, 'puan' => (int) ($r->puan ?? 0), 'harcama' => (float) ($r->toplam_harcama ?? 0), 'siparis' => (int) ($r->siparis_sayisi ?? 0)];
        return $c[$sid] = $o;
    }
    // Uygulaması olan (bildirim izni veren) müşteriler: id => ilk kayıt tarihi
    function _pzrPushAbone($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('cihaz_tokenlari')) return $c[$sid] = [];
        $o = [];
        try { foreach (DB::table('cihaz_tokenlari')->where('sube_id', $sid)->where('kullanici_tipi', 'musteri')->whereNotNull('personel_id')->get(['personel_id', 'platform', 'created_at']) as $r) {
            $id = (int) $r->personel_id; $t = substr((string) ($r->created_at ?? ''), 0, 10) ?: null;
            if (!isset($o[$id]) || ($t && (!$o[$id]['tarih'] || $t < $o[$id]['tarih']))) $o[$id] = ['tarih' => $t, 'platform' => mb_strtolower((string) ($r->platform ?: 'bilinmiyor'), 'UTF-8')];
        } } catch (\Throwable $e) {}
        return $c[$sid] = $o;
    }
    function _pzrSegmentAd($s)
    {
        $s = (string) $s; $crm = str_starts_with($s, 'crm_'); $k = $crm ? substr($s, 4) : $s;
        $ad = ['tumu' => 'Tüm müşteriler', 'yeni' => 'Yeni üyeler', 'sadik' => 'Sadık müşteriler', 'uyuyan' => 'Uyuyan müşteriler', 'yuksek_puan' => 'Puanı yüksek', 'winback' => 'Geri kazan',
            'dogum' => 'Doğum günü', 'vip' => 'VIP / sadık', 'kayip' => 'Kayıp müşteriler', 'sessiz_saat' => 'Sessiz saat', 'arkadas' => 'Arkadaşını getir'][$k] ?? null;
        if (!$ad && function_exists('_kampanyaSegmentler')) { try { $ks = _kampanyaSegmentler(); if (isset($ks[$k])) $ad = $ks[$k][0]; } catch (\Throwable $e) {} }
        return ($ad ?: ($k ?: '—')) . ($crm ? ' (CRM AI)' : '');
    }
    // Kampanya gönderimleri: WhatsApp (kampanya_gonderimleri) + uygulama bildirimi (kampanya_gonderim)
    function _pzrGonderim($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        $o = []; $ar = [$bas . ' 00:00:00', $bit . ' 23:59:59'];
        if (_srTablo('kampanya_gonderimleri')) { try { foreach (DB::table('kampanya_gonderimleri')->where('sube_id', $sid)->whereBetween('created_at', $ar)->get() as $r) {
            $o[] = ['id' => (int) $r->id, 'kod' => 'wa', 'kanal' => 'WhatsApp', 'ts' => strtotime((string) $r->created_at), 'segment' => _pzrSegmentAd($r->segment), 'baslik' => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $r->mesaj)), 0, 70),
                'hedef' => (int) $r->hedef, 'ulasan' => (int) $r->gonderilen, 'atlanan' => (int) ($r->atlanan ?? max(0, (int) $r->hedef - (int) $r->gonderilen)), 'gonderen' => (string) ($r->gonderen_ad ?? '')];
        } } catch (\Throwable $e) {} }
        if (_srTablo('kampanya_gonderim')) { try { foreach (DB::table('kampanya_gonderim')->where('sube_id', $sid)->whereBetween('created_at', $ar)->get() as $r) {
            $o[] = ['id' => (int) $r->id, 'kod' => 'push', 'kanal' => 'Uygulama bildirimi', 'ts' => strtotime((string) $r->created_at), 'segment' => _pzrSegmentAd($r->segment), 'baslik' => mb_substr((string) $r->baslik, 0, 70),
                'hedef' => (int) $r->hedef, 'ulasan' => (int) $r->gonderilen, 'atlanan' => max(0, (int) $r->hedef - (int) $r->gonderilen), 'gonderen' => ''];
        } } catch (\Throwable $e) {} }
        usort($o, fn ($a, $b) => $a['ts'] <=> $b['ts']);
        return $c[$key] = $o;
    }
    // İndirim / ikram kayıtları (sebepli)
    function _pzrIndirimLog($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('iptal_indirim_loglari')) return $c[$key] = [];
        try { $rows = DB::table('iptal_indirim_loglari')->where('sube_id', $sid)->whereIn('tip', ['indirim', 'ikram'])->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['tip', 'tutar', 'sebep', 'created_at']); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['tip' => (string) $r->tip, 'tutar' => (float) $r->tutar, 'sebep' => trim((string) $r->sebep) ?: 'Sebep yazılmamış', 'tur' => _pzrIndirimTur((string) $r->tip, trim((string) $r->sebep)), 'tarih' => substr((string) $r->created_at, 0, 10)];
        return $c[$key] = $o;
    }
    function _pzrIndirimTur($tip, $sebep)
    {
        if (str_starts_with($sebep, 'Ödül:')) return 'Sadakat ödülü';
        if ($tip === 'ikram') return 'İkram';
        if (preg_match('/^%\d+(\.\d+)? iskonto$/u', $sebep)) return 'Personel iskontosu (sebepsiz)';
        return 'İndirim (sebepli)';
    }
    function _pzrKuralTipAd($t) { return ['online_odeme' => 'Online ödeme', 'uygulama' => 'Uygulamadan sipariş', 'kupon' => 'Kupon kodu', 'ilk_siparis' => 'İlk sipariş', 'tutar_ustu' => 'Tutar üstü', 'happy_hour' => 'Saat aralığı', 'gun' => 'Gün indirimi', 'dogum_gunu' => 'Doğum günü', 'urun' => 'Ürün indirimi'][$t] ?? ($t ?: '—'); }
    function _pzrGunMaske($m) { $g = ['', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz']; $o = []; foreach (array_filter(array_map('trim', explode(',', (string) $m))) as $x) $o[] = $g[(int) $x] ?? $x; return $o ? implode(', ', $o) : 'Her gün'; }
    // Happy hour: kural, an (unix), ürün, kategori eşleşiyor mu
    function _pzrHhUyar($h, $ts, $urunId, $katId)
    {
        $gun = date('Y-m-d', $ts); $saat = date('H:i', $ts); $n = (string) date('N', $ts);
        if (!empty($h->baslangic_tarih) && $gun < substr((string) $h->baslangic_tarih, 0, 10)) return false;
        if (!empty($h->bitis_tarih) && $gun > substr((string) $h->bitis_tarih, 0, 10)) return false;
        if (!empty($h->gunler) && !in_array($n, array_map('trim', explode(',', (string) $h->gunler)), true)) return false;
        if (!empty($h->baslangic_saat) && !empty($h->bitis_saat)) {
            $b = substr((string) $h->baslangic_saat, 0, 5); $e = substr((string) $h->bitis_saat, 0, 5);
            if ($b <= $e ? !($saat >= $b && $saat <= $e) : !($saat >= $b || $saat <= $e)) return false;
        }
        if (!empty($h->urun_id)) return (int) $h->urun_id === (int) $urunId;
        if (!empty($h->kategori_id)) return (int) $h->kategori_id === (int) $katId;
        return true;
    }
    // Satılan kalemler (ödenmiş adisyonlar, iptal hariç); an = kalemin yazıldığı zaman
    function _pzrKalemler($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('adisyon_kalemleri') || !_srTablo('adisyonlar')) return $c[$key] = [];
        $sel = ['ak.urun_id', 'ak.urun_adi', 'ak.adet', 'ak.birim_fiyat', 'ak.tutar', 'ak.created_at', 'a.kapanis']; $sel[] = _srKolon('adisyon_kalemleri', 'ikram') ? 'ak.ikram' : DB::raw('0 as ikram');
        try { $rows = DB::table('adisyon_kalemleri as ak')->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->where('a.durum', 'odendi')->where('ak.durum', '!=', 'iptal')
            ->whereBetween('a.kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[] = ['urun_id' => (int) $r->urun_id, 'ad' => (string) $r->urun_adi, 'adet' => (float) $r->adet, 'bf' => (float) $r->birim_fiyat, 'tutar' => (float) $r->tutar, 'ikram' => (int) $r->ikram, 'ts' => strtotime((string) ($r->created_at ?: $r->kapanis))];
        return $c[$key] = $o;
    }
    function _pzrUrunler($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('urunler')) return $c[$sid] = [];
        try { return $c[$sid] = DB::table('urunler')->where('sube_id', $sid)->get(['id', 'ad', 'fiyat', 'kategori_id'])->keyBy('id')->all(); } catch (\Throwable $e) { return $c[$sid] = []; }
    }
    function _pzrHhKurallar($sid) { if (!_srTablo('happy_hour')) return []; try { return DB::table('happy_hour')->where('sube_id', $sid)->orderByDesc('aktif')->orderBy('id')->get()->all(); } catch (\Throwable $e) { return []; } }
    // Happy hour analizi: her kural için satış, verilen indirim (ürün kart fiyatı − satış fiyatı) ve önceki dönem aynı saat/ürün satışı
    function _pzrHappy($sid, array $dn)
    {
        $kur = _pzrHhKurallar($sid); $ur = _pzrUrunler($sid); $o = [];
        foreach ($kur as $h) $o[$h->id] = ['h' => $h, 'adet' => 0, 'ciro' => 0, 'indirim' => 0, 'oadet' => 0, 'ociro' => 0];
        if (!$kur) return $o;
        foreach ([[$dn['bas'], $dn['bit'], ''], [$dn['obas'], $dn['obit'], 'o']] as [$b, $e, $p]) {
            foreach (_pzrKalemler($sid, $b, $e) as $k) {
                if ($k['ikram']) continue; $u = $ur[$k['urun_id']] ?? null; $kat = $u->kategori_id ?? 0;
                foreach ($kur as $h) {
                    if ($p === '' && !(int) $h->aktif) { /* pasif kural: yine de dönemde geçerliyse sayılır */ }
                    if (!_pzrHhUyar($h, $k['ts'], $k['urun_id'], $kat)) continue;
                    $o[$h->id][$p . 'adet'] += $k['adet']; $o[$h->id][$p . 'ciro'] += $k['tutar'];
                    if ($p === '' && $u && (float) $u->fiyat > $k['bf']) $o[$h->id]['indirim'] += ((float) $u->fiyat - $k['bf']) * $k['adet'];
                    break; // bir kalem tek kurala yazılır
                }
            }
        }
        return $o;
    }
    function _pzrRezervasyon($sid, $bas, $bit)
    {
        if (!_srTablo('rezervasyonlar')) return [];
        try { return DB::table('rezervasyonlar')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->get(['kaynak', 'kisi', 'durum', 'tarih'])->map(fn ($r) => ['kaynak' => _pzrRezKaynakAd($r->kaynak), 'kisi' => (int) $r->kisi, 'durum' => (string) $r->durum, 'gun' => (int) date('N', strtotime((string) $r->tarih))])->all(); } catch (\Throwable $e) { return []; }
    }
    function _pzrRezKaynakAd($k) { $k = mb_strtolower(trim((string) $k), 'UTF-8'); return ['admin' => 'Panel (elle girildi)', 'telefon' => 'Telefon / AI santral', 'web' => 'Web formu', 'app' => 'Müşteri uygulaması', 'uygulama' => 'Müşteri uygulaması', 'whatsapp' => 'WhatsApp', 'qr' => 'QR menü', 'ai' => 'AI asistan', 'instagram' => 'Instagram', 'google' => 'Google'][$k] ?? ($k !== '' ? mb_convert_case($k, MB_CASE_TITLE, 'UTF-8') : 'Belirtilmemiş'); }
    function _pzrWaLog($sid, $bas, $bit)
    {
        if (!_srTablo('wa_mesaj_loglari')) return [];
        $sel = ['yon', 'durum', 'created_at']; $sel[] = _srKolon('wa_mesaj_loglari', 'hata') ? 'hata' : DB::raw('NULL as hata');
        try { return DB::table('wa_mesaj_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get($sel)->map(fn ($r) => ['yon' => (string) $r->yon, 'durum' => (int) $r->durum, 'tarih' => substr((string) $r->created_at, 0, 10), 'hata' => trim((string) $r->hata)])->all(); } catch (\Throwable $e) { return []; }
    }
    function _pzrOtomatikMesaj($sid, $bas, $bit)
    {
        if (!_srTablo('wa_bildirim_gecmisi')) return [];
        $ad = ['rez_hatirlat' => 'Rezervasyon hatırlatma', 'dogum' => 'Doğum günü kutlaması', 'anket' => 'Memnuniyet anketi', 'winback' => 'Geri kazanım', 'duyuru' => 'Duyuru'];
        try { return DB::table('wa_bildirim_gecmisi')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->groupBy('tur')->selectRaw('tur, COUNT(*) n')->get()->map(fn ($r) => [$ad[$r->tur] ?? $r->tur, (int) $r->n])->sortByDesc(1)->values()->all(); } catch (\Throwable $e) { return []; }
    }
    function _pzrIcerik($sid, $bas, $bit)
    {
        if (!_srTablo('social_posts')) return [];
        try { return DB::table('social_posts')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(['platform', 'konu', 'durum', 'created_at'])->map(fn ($r) => ['platform' => mb_convert_case((string) ($r->platform ?: 'diğer'), MB_CASE_TITLE, 'UTF-8'), 'konu' => (string) $r->konu, 'durum' => ['taslak' => 'Taslak', 'yayinda' => 'Yayında', 'yayinlandi' => 'Yayında', 'planli' => 'Planlandı'][$r->durum] ?? (string) $r->durum, 'tarih' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
    // Kampanya kartı kullanımları (müşteri uygulamasında "Kullan")
    function _pzrKartKullanim($sid, $bas, $bit)
    {
        if (!_srTablo('musteri_kart_kullanim') || !_srTablo('app_banner')) return [];
        try { return DB::table('musteri_kart_kullanim as k')->join('app_banner as b', 'k.banner_id', '=', 'b.id')->where('b.sube_id', $sid)->whereBetween('k.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['k.banner_id', 'k.musteri_id', 'k.created_at'])->map(fn ($r) => ['bid' => (int) $r->banner_id, 'mid' => (int) $r->musteri_id, 'tarih' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
    // Faz 2: alıcı listesi kaydedilmiş gönderimler → [kanal|gonderim_id] => [musteri_id..]
    function _pzrAlicilar($sid, array $g)
    {
        if (!$g || !_srTablo('pzr_kampanya_alici')) return [];
        $o = [];
        try { foreach (['wa', 'push'] as $k) { $ids = array_column(array_filter($g, fn ($x) => $x['kod'] === $k), 'id'); if (!$ids) continue;
            foreach (DB::table('pzr_kampanya_alici')->where('sube_id', $sid)->where('kanal', $k)->whereIn('gonderim_id', $ids)->get(['gonderim_id', 'musteri_id']) as $r) $o[$k . '|' . $r->gonderim_id][] = (int) $r->musteri_id; } } catch (\Throwable $e) {}
        return $o;
    }
    // Kampanya etkisi: gönderimden önceki 7 gün / sonraki 7 gün (gönderim günü dahil) ciro + müşterili sipariş; alıcı listesi varsa dönüşüm
    function _pzrEtki($sid, array $dn)
    {
        $g = _pzrGonderim($sid, $dn['bas'], $dn['bit']); if (!$g) return [];
        $bugun = date('Y-m-d'); $b = date('Y-m-d', strtotime($dn['bas'] . ' -7 day')); $e = min($bugun, date('Y-m-d', strtotime($dn['bit'] . ' +7 day')));
        $gc = []; $gm = []; foreach (_pzrAdisyon($sid, $b, $e) as $a) { $gc[$a['tarih']] = ($gc[$a['tarih']] ?? 0) + $a['toplam']; if ($a['mid']) $gm[$a['tarih']] = ($gm[$a['tarih']] ?? 0) + 1; }
        $al = _pzrAlicilar($sid, $g); $gec = $al ? _pzrGecmis($sid) : []; $o = [];
        foreach ($g as $x) {
            $t = date('Y-m-d', $x['ts']); $once = _pzrGunler(date('Y-m-d', strtotime($t . ' -7 day')), date('Y-m-d', strtotime($t . ' -1 day'))); $sonra = array_filter(_pzrGunler($t, date('Y-m-d', strtotime($t . ' +6 day'))), fn ($d) => $d <= $bugun);
            $oc = array_sum(array_map(fn ($d) => $gc[$d] ?? 0, $once)); $sc = array_sum(array_map(fn ($d) => $gc[$d] ?? 0, $sonra));
            $ocG = $oc / 7; $scG = count($sonra) ? $sc / count($sonra) : 0;
            $ids = $al[$x['kod'] . '|' . $x['id']] ?? null; $donen = null; $donCiro = null;
            if ($ids !== null) {
                $son = date('Y-m-d', strtotime($t . ' +6 day')); $donen = 0; $donCiro = 0;
                foreach (array_unique($ids) as $m) { $var = false; foreach ($gec[$m]['tarihler'] ?? [] as $i => $d) if ($d >= $t && $d <= $son) { $var = true; } if ($var) $donen++; }
                foreach (_pzrAdisyon($sid, $t, min($bugun, $son)) as $a) if ($a['mid'] && in_array($a['mid'], $ids, true)) $donCiro += $a['toplam'];
            }
            $o[] = ['tarih' => $t, 'kanal' => $x['kanal'], 'segment' => $x['segment'], 'ulasan' => $x['ulasan'], 'once_ciro' => round($oc, 2), 'sonra_ciro' => round($sc, 2), 'sonra_gun' => count($sonra),
                'degisim' => $ocG > 0 ? round(($scG - $ocG) / $ocG * 100, 1) : null, 'once_sip' => array_sum(array_map(fn ($d) => $gm[$d] ?? 0, $once)), 'sonra_sip' => array_sum(array_map(fn ($d) => $gm[$d] ?? 0, $sonra)),
                'alici' => $ids !== null ? count(array_unique($ids)) : null, 'donen' => $donen, 'donusum' => $ids ? round($donen / count(array_unique($ids)) * 100, 1) : null, 'alici_ciro' => $donCiro !== null ? round($donCiro, 2) : null];
        }
        return $o;
    }
    // Pazarlama segmentleri (Müşteri Segmentleri ekranıyla aynı tanımlar) + erişim
    function _pzrSegmentler($sid)
    {
        $mu = _pzrMusteriler($sid); $g = _pzrGecmis($sid); $push = _pzrPushAbone($sid); $ay = date('Y-m-01'); $s30 = date('Y-m-d', strtotime('-30 day'));
        $c30 = []; foreach (_pzrAdisyon($sid, date('Y-m-d', strtotime('-29 day')), date('Y-m-d')) as $a) if ($a['mid']) $c30[$a['mid']] = ($c30[$a['mid']] ?? 0) + $a['toplam'];
        $tan = ['tumu' => ['Tüm müşteriler', 'Kayıtlı tüm müşteriler'], 'yeni' => ['Yeni üyeler', 'Bu ay kaydolanlar'], 'sadik' => ['Sadık müşteriler', '5+ sipariş ya da 1.000 ₺+ harcama'],
            'uyuyan' => ['Uyuyan müşteriler', 'Sipariş vermiş, 30+ gündür gelmeyen'], 'yuksek_puan' => ['Puanı yüksek', '100+ sadakat puanı']];
        $o = []; foreach ($tan as $k => $v) $o[$k] = ['ad' => $v[0], 'tanim' => $v[1], 'n' => 0, 'push' => 0, 'tel' => 0, 'c30' => 0.0, 'omur' => 0.0];
        foreach ($mu as $m) {
            $n = max($g[$m->id]['n'] ?? 0, $m->siparis); $ciro = max($g[$m->id]['ciro'] ?? 0, $m->harcama); $son = $g[$m->id]['son'] ?? null;
            $uy = ['tumu' => true, 'yeni' => $m->kayit && $m->kayit >= $ay, 'sadik' => $n >= 5 || $ciro >= 1000, 'uyuyan' => $n > 0 && (!$son || $son < $s30), 'yuksek_puan' => $m->puan >= 100];
            foreach ($uy as $k => $v) if ($v) { $o[$k]['n']++; if (isset($push[$m->id])) $o[$k]['push']++; if (strlen(preg_replace('/\D/', '', $m->telefon)) >= 10) $o[$k]['tel']++; $o[$k]['c30'] += $c30[$m->id] ?? 0; $o[$k]['omur'] += $ciro; }
        }
        return $o;
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_pzrVeriDurumu')) {
    function _pzrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t); if (_srKolon($t, 'sube_id')) $q->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d = ['satis' => $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')), 'musteri' => $say('musteriler', null),
            'musteri_satis' => _srKolon('adisyonlar', 'musteri_id') ? $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')->whereNotNull('musteri_id')->where('musteri_id', '>', 0)) : 0,
            'wa_kampanya' => $say('kampanya_gonderimleri', 'created_at'), 'push_kampanya' => $say('kampanya_gonderim', 'created_at'),
            'indirim' => $say('iptal_indirim_loglari', 'created_at', fn ($q) => $q->whereIn('tip', ['indirim', 'ikram'])), 'kural' => $say('indirimler', null), 'happy' => $say('happy_hour', null),
            'fix' => $say('fix_menu', null), 'kart' => $say('app_banner', null), 'kart_kullanim' => 0, 'rezervasyon' => $say('rezervasyonlar', 'tarih'),
            'wa_log' => $say('wa_mesaj_loglari', 'created_at'), 'app' => $say('cihaz_tokenlari', null, fn ($q) => $q->where('kullanici_tipi', 'musteri')), 'icerik' => $say('social_posts', 'created_at'),
            'alici' => $say('pzr_kampanya_alici', 'created_at')];
        $d['kart_kullanim'] = count(_pzrKartKullanim($sid, $s, date('Y-m-d')));
        return $d;
    }
    function _pzrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'satis_yok' => 'Son 1 yılda ödenmiş adisyon yok. Kanal, indirim, kampanya etkisi ve yoğunluk raporları POS\'ta kapanan (ödenmiş) adisyonlardan hesaplanır.',
            'satis' => 'Seçili dönemde (' . $donem . ') ödenmiş adisyon yok.',
            'musteri_yok' => 'Müşteri kaydı yok. Kazanım, segment ve erişim raporları müşteri kartlarından hesaplanır. Müşteriler paket / uygulama siparişinde otomatik, salonda ise POS\'ta adisyona müşteri seçilince oluşur (CRM > Müşteri 360).',
            'bagli_yok' => 'Adisyonlara müşteri bağlanmamış: son 1 yılda müşteri seçilmiş ödenmiş adisyon yok. Yeni / geri dönen müşteri, kazanım ve kampanya dönüşümü bu bağdan ölçülür. POS\'ta adisyona müşteri seçin (paket ve uygulama siparişlerinde otomatik bağlanır).',
            'bagli' => 'Seçili dönemde (' . $donem . ') müşteriye bağlı ödenmiş adisyon yok.',
            'gonderim_yok' => 'Kampanya gönderimi yok. WhatsApp kampanyaları Pazarlama > Kampanya Stüdyosu\'ndan, uygulama bildirimleri Kampanya Stüdyosu (push) ve Pazarlama AI\'dan gönderilir; her gönderim burada raporlanır.',
            'gonderim' => 'Seçili dönemde (' . $donem . ') kampanya gönderimi yok.',
            'alici_yok' => 'Alıcı bazlı dönüşüm henüz ölçülmüyor: kampanya gönderilirken alıcı listesi kaydedilmeye başlanınca "kampanyayı alanların kaçı 7 gün içinde sipariş verdi" hesaplanır. Şimdilik gönderim öncesi ve sonrası 7 günün genel ciro karşılaştırması gösteriliyor (başka etkenler de rol oynar).',
            'indirim' => 'Seçili dönemde (' . $donem . ') indirim ya da ikram yapılmamış.',
            'kural_yok' => 'İndirim kuralı tanımlı değil. Kupon kodu, ilk sipariş, tutar üstü, gün ve saat indirimleri İndirimler ekranından tanımlanır; her kullanımda sayaç artar.',
            'hh_yok' => 'Happy hour tanımı yok. Happy Hour ekranından ürün ya da kategori, gün ve saat aralığı girin; etki o saatlerde satılan kalemlerden ölçülür.',
            'hh' => 'Seçili dönemde (' . $donem . ') happy hour saatlerine denk gelen satış yok.',
            'fix_yok' => 'Set / fix menü tanımlı değil (Fix Menü ekranı). Satış adedi, POS\'ta menü adıyla aynı isimli ürün satıldığında ölçülür.',
            'kart_yok' => 'Uygulama kampanya kartı yok. Kartlar App Yönetimi\'nden eklenir; müşteri uygulamada kartta "Kullan"a bastığında kullanım kaydı düşer.',
            'kart' => 'Seçili dönemde (' . $donem . ') uygulamada kampanya kartı kullanılmamış.',
            'rez_yok' => 'Rezervasyon kaydı yok. Rezervasyonlar Rezervasyon Satış > Rezervasyonlar ekranından, web formundan ya da AI santralden gelir; kaynak alanı otomatik dolar.',
            'rez' => 'Seçili dönemde (' . $donem . ') rezervasyon yok.',
            'online' => 'Seçili dönemde (' . $donem . ') paket / online sipariş yok. Getir, Yemeksepeti, Trendyol entegrasyonu ve müşteri uygulaması siparişleri buraya düşer.',
            'wa_yok' => 'WhatsApp mesaj kaydı yok. WhatsApp hattı bağlanınca (Ayarlar > WhatsApp) giden ve gelen her mesaj kaydedilir.',
            'wa' => 'Seçili dönemde (' . $donem . ') WhatsApp mesajı yok.',
            'app_yok' => 'Uygulamayı indirip bildirim izni veren müşteri yok. Markalı müşteri uygulaması kurulup bildirim izni verildiğinde kayıt düşer; push kampanyaları yalnız bu müşterilere gider.',
            'icerik_yok' => 'Sosyal medya içerik kaydı yok. CRM > AI Sosyal Medya ekranında üretilip "Kaydet" denen paylaşım taslakları burada sayılır (Sosyal İçerik Takvimi\'nde üretilen plan kaydedilmez).',
            'icerik' => 'Seçili dönemde (' . $donem . ') içerik kaydı yok.',
            'yeni' => 'Seçili dönemde (' . $donem . ') ilk siparişini veren yeni müşteri yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_pzrKatalog')) {
    function _pzrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Pazarlama Bülteni', 'Dünün cirosu ve kanalları, yeni ve geri dönen müşteri, indirim + ikram, giden kampanya ve mesajlar, yeni uygulama abonesi.', 'today', '#E11D48', ['satis', 'musteri_satis'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Pazarlama Raporu', 'Gün gün ciro, yeni müşteri ve indirim; kanal karşılaştırması, kampanyalar ve önceki haftayla kıyas.', 'date_range', '#BE123C', ['satis', 'musteri_satis'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Pazarlama Yönetim Raporu', 'Kanal, kazanım, indirim maliyeti, kampanya etkisi, rezervasyon kaynakları ve hedef kitle — patron için tek dosyada ay özeti.', 'calendar_month', '#9F1239', ['satis', 'musteri_satis', 'wa_kampanya'], 'gecen_ay'],
            'kampanya' => ['kampanya', 'Kampanya Gönderimleri', 'WhatsApp ve uygulama bildirimi kampanyaları: hedef kitle, ulaşan, atlanan ve ulaşma oranı; segmente göre.', 'campaign', '#DB2777', ['wa_kampanya', 'push_kampanya'], null],
            'kampanya_etki' => ['kampanya', 'Kampanya Etkisi & Dönüşüm', 'Her gönderimden önceki ve sonraki 7 günün cirosu ve müşterili siparişi; alıcı listesi tutulan gönderimlerde kaç alıcının geldiği ve getirdiği ciro.', 'trending_up', '#BE185D', ['wa_kampanya', 'satis'], null],
            'kart' => ['kampanya', 'Uygulama Kampanya Kartları', 'Müşteri uygulamasındaki kampanya kartlarının kullanımı, kupon ve ödül puanı; kartı kullanan kaç kişi 7 gün içinde sipariş verdi.', 'style', '#9D174D', ['kart', 'kart_kullanim'], null],
            'indirim_maliyet' => ['indirim', 'İndirim & İkram Maliyeti', 'Brüt satışa göre indirim + ikram oranı; gün, tür, sebep ve kanala göre; sebep kaydı olmayan (kural / online) indirim.', 'sell', '#EA580C', ['satis', 'indirim'], null],
            'kural' => ['indirim', 'İndirim Kuralları, Kupon & Set Menü', 'Tanımlı indirim kuralları ve kupon kodları: kullanım sayacı, limit, süre; sadakat kampanyaları ve set / fix menü satışları.', 'local_offer', '#C2410C', ['kural'], null],
            'happy_hour' => ['indirim', 'Happy Hour Etkisi', 'Happy hour saatlerinde satılan adet ve ciro, verilen indirim ve önceki dönem aynı saat / ürün satışına göre değişim.', 'local_bar', '#9A3412', ['happy', 'satis'], null],
            'kanal' => ['kanal', 'Satış Kanalı Karşılaştırması', 'Salon, QR menü, paket, uygulama ve platformlar: ciro, pay, adisyon, ortalama hesap, indirim oranı ve önceki döneme göre değişim.', 'hub', '#2563EB', ['satis'], null],
            'online' => ['kanal', 'Online & Paket Platformları', 'Uygulama, Getir, Yemeksepeti, Trendyol ve telefon siparişleri: sepet, tekil ve yeni müşteri, tekrar eden müşteri oranı, saat dağılımı.', 'delivery_dining', '#1D4ED8', ['satis'], null],
            'rezervasyon_kaynak' => ['kanal', 'Rezervasyon Kaynakları', 'Rezervasyonların geldiği kanal (telefon, web, uygulama, panel…): kişi, gelme ve gelmeme (no-show) oranı.', 'event_available', '#1E40AF', ['rezervasyon'], null],
            'kazanim' => ['musteri', 'Yeni Müşteri Kazanımı', 'İlk siparişini veren müşteriler, geldikleri kanal, ilk sepet tutarı ve 2. siparişe dönüş oranı; yeni kayıtlar.', 'person_add', '#059669', ['musteri_satis'], null],
            'yeni_donen' => ['musteri', 'Yeni ve Geri Dönen Müşteri', 'Ciro ve müşteri sayısının yeni / geri dönen / müşteri bağlanmamış dağılımı; günlere ve kanallara göre.', 'autorenew', '#047857', ['musteri_satis'], null],
            'kitle' => ['musteri', 'Hedef Kitle & Erişim', 'Pazarlama segmentlerinin büyüklüğü; uygulama bildirimi ve WhatsApp ile ulaşılabilen kişi, son 30 gün ve ömür boyu ciro.', 'groups', '#0F766E', ['musteri'], null],
            'mesaj' => ['iletisim', 'WhatsApp Mesaj Trafiği', 'Giden ve gelen mesajlar, başarılı / başarısız / SMS\'e düşen gönderimler, başarısızlık sebepleri ve otomatik mesaj türleri.', 'forum', '#7C3AED', ['wa_log'], null],
            'app' => ['iletisim', 'Uygulama & Bildirim Abonesi', 'Uygulamayı indirip bildirim izni veren müşteri sayısı ve büyümesi, platform dağılımı, uygulama siparişi ve cirosu.', 'install_mobile', '#6D28D9', ['app'], null],
            'icerik' => ['icerik', 'Sosyal İçerik Üretimi', 'Haftalık içerik sayısı ve haftada 3 paylaşım hedefine uyum, platform dağılımı, içeriksiz haftalar.', 'photo_camera', '#0891B2', ['icerik'], null],
            'sessiz' => ['icerik', 'Sessiz Saat & Gün Haritası', 'Gün × saat ortalama ciro ve adisyon; kampanya ve happy hour için en sessiz dilimler.', 'grid_on', '#0E7490', ['satis'], null],
        ];
    }
    function _pzrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron ve pazarlama sorumlusu için hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#E11D48'],
            'kampanya' => ['Kampanyalar', 'campaign', 'WhatsApp ve uygulama kampanyaları: ulaşım, etki ve dönüşüm, uygulama kampanya kartları.', '#DB2777'],
            'indirim' => ['İndirim & Promosyon', 'sell', 'İndirim ve ikram maliyeti, indirim kuralları ve kuponlar, happy hour etkisi, set menü.', '#EA580C'],
            'kanal' => ['Satış Kanalları', 'hub', 'Kanal karşılaştırması, online platformlar ve rezervasyon kaynakları.', '#2563EB'],
            'musteri' => ['Müşteri Kazanımı', 'person_add', 'Yeni müşteri kazanımı, yeni ve geri dönen müşteri, hedef kitle ve erişim.', '#059669'],
            'iletisim' => ['Mesaj & Uygulama', 'forum', 'WhatsApp mesaj trafiği, uygulama ve bildirim aboneleri.', '#7C3AED'],
            'icerik' => ['İçerik & Zamanlama', 'photo_camera', 'Sosyal içerik üretimi ve kampanya zamanlaması için sessiz saat haritası.', '#0891B2'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı pazarlama özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_pzrKanalSat')) {
    // Kanal tablosu satırları: [kanal, ciro, pay, adisyon, ort, misafir, kişi başı, indirim+ikram %, önceki ciro, değişim]
    function _pzrKanalSat(array $a, array $ao)
    {
        $k = []; foreach ($a as $x) { $y = &$k[$x['kanal']]; $y['c'] = ($y['c'] ?? 0) + $x['toplam']; $y['n'] = ($y['n'] ?? 0) + 1; $y['m'] = ($y['m'] ?? 0) + $x['misafir']; $y['b'] = ($y['b'] ?? 0) + $x['ara']; $y['i'] = ($y['i'] ?? 0) + $x['indirim'] + $x['ikram']; unset($y); }
        $o = []; foreach ($ao as $x) $o[$x['kanal']] = ($o[$x['kanal']] ?? 0) + $x['toplam'];
        foreach ($o as $kn => $_) if (!isset($k[$kn])) $k[$kn] = ['c' => 0, 'n' => 0, 'm' => 0, 'b' => 0, 'i' => 0];
        $top = max(0.01, array_sum(array_column($k, 'c'))); $sat = [];
        foreach ($k as $kn => $v) $sat[] = [$kn, round($v['c'], 2), round($v['c'] / $top * 100, 1), $v['n'], $v['n'] ? round($v['c'] / $v['n'], 2) : 0, $v['m'], $v['m'] ? round($v['c'] / $v['m'], 2) : null, $v['b'] > 0 ? round($v['i'] / $v['b'] * 100, 1) : null, round($o[$kn] ?? 0, 2), _srYuzde($v['c'], $o[$kn] ?? 0)];
        usort($sat, fn ($x, $y) => $y[1] <=> $x[1]);
        return $sat;
    }
    function _pzrSayfaKanal(array $sat, $bos)
    {
        return _srSayfa('Kanal Karşılaştırma', 'Ödenmiş adisyonlar, kapanış tarihine göre. İndirim + ikram oranı brüt satışa (indirim öncesi) göre. Kişi başı: misafir sayısı girilen adisyonlarda.',
            [['Kanal', 'm', 22], ['Ciro', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Adisyon', 's', 9, 'top'], ['Ort. Hesap', 'tl', 12], ['Misafir', 's', 8, 'top'], ['Kişi Başı', 'tl', 11], ['İndirim + İkram %', 'y', 12, 'esik:15'], ['Önceki Dönem Ciro', 'tl', 14, 'top'], ['Değişim %', 'y', 10]], $sat, $bos);
    }
    function _pzrSayfaGonderim(array $g, $bos)
    {
        return _srSayfa('Kampanya Gönderimleri', 'Hedef = segmentteki müşteri; Ulaşan = mesajı başarıyla giden; Atlanan = telefonu / uygulaması olmayan ya da gönderilemeyen. Uygulama bildirimi yalnız bildirim izni veren müşterilere gider.',
            [['Tarih', 't', 11], ['Saat', 'm', 6], ['Kanal', 'm', 16], ['Segment', 'm', 18], ['Mesaj / Başlık', 'm', 40], ['Hedef', 's', 8, 'top'], ['Ulaşan', 's', 8, 'top bar'], ['Atlanan', 's', 8, 'top'], ['Ulaşma %', 'y', 9]],
            array_map(fn ($x) => [date('Y-m-d', $x['ts']), date('H:i', $x['ts']), $x['kanal'], $x['segment'], $x['baslik'], $x['hedef'], $x['ulasan'], $x['atlanan'], $x['hedef'] > 0 ? round($x['ulasan'] / $x['hedef'] * 100, 1) : null], $g), $bos);
    }
    // İndirim sebep tablosu: [tür, sebep, adet, tutar, pay]; sebep kaydı olmayan indirim farkı en altta
    function _pzrIndirimSat(array $a, array $log)
    {
        $g = []; foreach ($log as $l) { $k = $l['tur'] . '|' . $l['sebep']; $g[$k]['tur'] = $l['tur']; $g[$k]['sebep'] = $l['sebep']; $g[$k]['n'] = ($g[$k]['n'] ?? 0) + 1; $g[$k]['tl'] = ($g[$k]['tl'] ?? 0) + $l['tutar']; }
        $logInd = array_sum(array_map(fn ($l) => $l['tip'] === 'indirim' ? $l['tutar'] : 0, $log)); $logIkr = array_sum(array_map(fn ($l) => $l['tip'] === 'ikram' ? $l['tutar'] : 0, $log));
        $adInd = array_sum(array_column($a, 'indirim')); $adIkr = array_sum(array_column($a, 'ikram'));
        if ($adInd - $logInd > 0.5) $g['k'] = ['tur' => 'Kural / online indirim', 'sebep' => 'Sebep kaydı yok (indirim kuralı, kupon ya da online ödeme indirimi)', 'n' => null, 'tl' => $adInd - $logInd];
        if ($adIkr - $logIkr > 0.5) $g['i'] = ['tur' => 'İkram', 'sebep' => 'Sebep kaydı yok', 'n' => null, 'tl' => $adIkr - $logIkr];
        $top = max(0.01, array_sum(array_column($g, 'tl')));
        $sat = array_map(fn ($v) => [$v['tur'], $v['sebep'], $v['n'], round($v['tl'], 2), round($v['tl'] / $top * 100, 1)], array_values($g));
        usort($sat, fn ($x, $y) => $y[3] <=> $x[3]);
        return $sat;
    }
    function _pzrSayfaIndirim(array $sat, $bos)
    {
        return _srSayfa('İndirim & İkram Sebepleri', 'POS\'ta girilen iskonto / ikram sebepleri ve sadakat ödülleri. Kural ve online indirimler sebep kaydı tutmaz; adisyon toplamıyla aradaki fark ayrı satırda gösterilir.',
            [['Tür', 'm', 22], ['Sebep', 'm', 40], ['Adet', 's', 7, 'top'], ['Tutar', 'tl', 13, 'top bar'], ['Pay %', 'y', 8]], $sat, $bos);
    }
    function _pzrKazanimSat(array $g, $bas, $bit)
    {
        $k = [];
        foreach ($g as $mid => $x) {
            if ($x['ilk'] < $bas || $x['ilk'] > $bit) continue;
            $y = &$k[$x['ilkKanal']]; $y['n'] = ($y['n'] ?? 0) + 1; $y['t'] = ($y['t'] ?? 0) + $x['ilkTutar'];
            if ($x['ikinci']) { $y['d'] = ($y['d'] ?? 0) + 1; $y['gun'][] = (strtotime($x['ikinci']) - strtotime($x['ilk'])) / 86400; }
            unset($y);
        }
        $sat = array_map(fn ($kn, $v) => [$kn, $v['n'], round($v['t'] / $v['n'], 2), $v['d'] ?? 0, round(($v['d'] ?? 0) / $v['n'] * 100, 1), !empty($v['gun']) ? round(array_sum($v['gun']) / count($v['gun']), 1) : null], array_keys($k), array_values($k));
        usort($sat, fn ($x, $y) => $y[1] <=> $x[1]);
        return $sat;
    }
    function _pzrSayfaKazanim(array $sat, $bos)
    {
        return _srSayfa('Kanala Göre Kazanım', 'Yeni müşteri = tüm zamanlardaki ilk siparişi bu dönemde olan (müşteriye bağlı adisyon). 2. sipariş: bugüne kadar ikinci kez sipariş veren; yeni müşteride henüz zaman geçmemiş olabilir.',
            [['İlk Sipariş Kanalı', 'm', 22], ['Yeni Müşteri', 's', 11, 'top bar'], ['İlk Sepet Ort.', 'tl', 12], ['2. Sipariş Veren', 's', 12, 'top'], ['Dönüş %', 'y', 9], ['2. Siparişe Ort. Gün', 's', 13]], $sat, $bos);
    }
    function _pzrRezSat(array $rz)
    {
        $k = []; foreach ($rz as $r) { $y = &$k[$r['kaynak']]; $y['n'] = ($y['n'] ?? 0) + 1; $y['k'] = ($y['k'] ?? 0) + $r['kisi']; $y[$r['durum']] = ($y[$r['durum']] ?? 0) + 1; unset($y); }
        $sat = [];
        foreach ($k as $kn => $v) { $kes = ($v['geldi'] ?? 0) + ($v['gelmedi'] ?? 0); $sat[] = [$kn, $v['n'], $v['k'], $v['geldi'] ?? 0, $v['gelmedi'] ?? 0, $v['iptal'] ?? 0, ($v['bekliyor'] ?? 0) + ($v['onaylandi'] ?? 0), $kes ? round(($v['geldi'] ?? 0) / $kes * 100, 1) : null, $kes ? round(($v['gelmedi'] ?? 0) / $kes * 100, 1) : null]; }
        usort($sat, fn ($x, $y) => $y[1] <=> $x[1]);
        return $sat;
    }
    function _pzrSayfaRez(array $sat, $bos)
    {
        return _srSayfa('Kaynağa Göre Rezervasyon', 'Rezervasyon tarihine göre. Gelme / gelmeme oranı yalnız sonuçlanmış (Geldi ya da Gelmedi işaretlenmiş) rezervasyonlardan.',
            [['Kaynak', 'm', 22], ['Rezervasyon', 's', 11, 'top bar'], ['Kişi', 's', 8, 'top'], ['Geldi', 's', 8, 'top'], ['Gelmedi', 's', 8, 'top esik:1'], ['İptal', 's', 7, 'top'], ['Bekleyen / Onaylı', 's', 12, 'top'], ['Gelme %', 'y', 9], ['No-show %', 'y', 10, 'esik:15']], $sat, $bos);
    }
    function _pzrSayfaSegment(array $sg, $bos)
    {
        return _srSayfa('Segmentler', 'Müşteri Segmentleri ekranıyla aynı tanımlar (bugüne göre). Push açık = uygulamayı indirip bildirim izni veren; Telefonu var = WhatsApp kampanyası gidebilir.',
            [['Segment', 'm', 18], ['Tanım', 'm', 30], ['Müşteri', 's', 9, 'bar'], ['Push Açık', 's', 9], ['Telefonu Var', 's', 10], ['Son 30 Gün Ciro', 'tl', 14], ['Kişi Başı Ömür Boyu Ciro', 'tl', 16]],
            array_map(fn ($v) => [$v['ad'], $v['tanim'], $v['n'], $v['push'], $v['tel'], round($v['c30'], 2), $v['n'] ? round($v['omur'] / $v['n'], 2) : null], array_values($sg)), $bos);
    }
}

// ---------- Raporlar ----------
if (!function_exists('_pzrRapor')) {
    function _pzrRapor($anahtar, $sid, array $dn)
    {
        _pzrEnsure();
        $d = _pzrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $oran = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : null;
        $bosSatis = !$d['satis'] ? _pzrBos('satis_yok', $dn) : _pzrBos('satis', $dn);
        $bosBagli = !$d['musteri_satis'] ? _pzrBos('bagli_yok', $dn) : _pzrBos('bagli', $dn);
        $bosGon = !$d['wa_kampanya'] && !$d['push_kampanya'] ? _pzrBos('gonderim_yok', $dn) : _pzrBos('gonderim', $dn);
        $blokKanal = fn ($sat, $renk) => _srBlok('Kanallara göre ciro', 'pie', $renk, [['Kanal', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($sat, fn ($x) => $x[1] > 0)));
        $blokSaat = function ($a, $renk) { $h = []; foreach ($a as $x) $h[$x['saat']] = ($h[$x['saat']] ?? 0) + $x['toplam']; ksort($h); return _srBlok('Saatlere göre ciro', 'col', $renk, [['Saat', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($s, $v) => [sprintf('%02d:00', $s), round($v, 2), ''], array_keys($h), array_values($h))); };
        // Yeni / geri dönen ayrımı: müşterinin tüm zamanlardaki ilk siparişi dönemdeyse yeni
        $ayir = function (array $a, $b0) use ($sid) { $g = _pzrGecmis($sid); $y = ['yc' => 0, 'dc' => 0, 'ac' => 0, 'yk' => [], 'dk' => []];
            foreach ($a as $x) { if (!$x['mid']) { $y['ac'] += $x['toplam']; continue; } if (($g[$x['mid']]['ilk'] ?? $x['tarih']) >= $b0) { $y['yc'] += $x['toplam']; $y['yk'][$x['mid']] = 1; } else { $y['dc'] += $x['toplam']; $y['dk'][$x['mid']] = 1; } }
            return $y; };

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $a = _pzrAdisyon($sid, $bas, $bit); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $c = _pzrCiro($a); $ks = _pzrKanalSat($a, $ao); $y = $ayir($a, $bas);
                $brut = array_sum(array_column($a, 'ara')); $ii = array_sum(array_column($a, 'indirim')) + array_sum(array_column($a, 'ikram'));
                $g = _pzrGonderim($sid, $bas, $bit); $wa = _pzrWaLog($sid, $bas, $bit); $ab = array_filter(_pzrPushAbone($sid), fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit);
                $rz = _pzrRezervasyon($sid, $bas, $bit); $log = _pzrIndirimLog($sid, $bas, $bit);
                return [
                    $pano([_srKpi('Ciro', $c, 'tl', 'E11D48'), _srKpi('Adisyon', count($a), 'sayi', 'BE123C'), $kpiTl('Ortalama hesap', count($a) ? round($c / count($a), 2) : null, '9F1239'),
                        $kpiY('Müşteriye bağlı satış', $oran($y['yc'] + $y['dc'], $c), '64748B'), _srKpi('Yeni müşteri', count($y['yk']), 'sayi', '059669'), _srKpi('Geri dönen müşteri', count($y['dk']), 'sayi', '047857'),
                        _srKpi('İndirim + ikram', round($ii, 2), 'tl', 'EA580C'), $kpiY('İndirim + ikram oranı', $oran($ii, $brut), 'C2410C'),
                        _srKpi('Kampanya ulaşan', array_sum(array_column($g, 'ulasan')), 'sayi', 'DB2777'), _srKpi('Giden WhatsApp', count(array_filter($wa, fn ($x) => $x['yon'] !== 'gelen')), 'sayi', '7C3AED'),
                        _srKpi('Yeni uygulama abonesi', count($ab), 'sayi', '6D28D9'), _srKpi('Rezervasyon', count($rz), 'sayi', '1E40AF')],
                        [$blokKanal($ks, 'E11D48'), $blokSaat($a, 'BE123C')], !$a ? $bosSatis : null),
                    _pzrSayfaKanal($ks, $bosSatis),
                    _pzrSayfaGonderim($g, $bosGon),
                    _pzrSayfaIndirim(_pzrIndirimSat($a, $log), _pzrBos('indirim', $dn)),
                ];
            case 'haftalik':
            case 'aylik':
                $a = _pzrAdisyon($sid, $bas, $bit); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $c = _pzrCiro($a); $co = _pzrCiro($ao); $ks = _pzrKanalSat($a, $ao);
                $y = $ayir($a, $bas); $yo = $ayir($ao, $dn['obas']); $g = _pzrGonderim($sid, $bas, $bit); $log = _pzrIndirimLog($sid, $bas, $bit);
                $brut = array_sum(array_column($a, 'ara')); $ii = array_sum(array_column($a, 'indirim')) + array_sum(array_column($a, 'ikram'));
                $bruto = array_sum(array_column($ao, 'ara')); $iio = array_sum(array_column($ao, 'indirim')) + array_sum(array_column($ao, 'ikram'));
                $onl = array_sum(array_map(fn ($x) => _pzrOnline($x) ? $x['toplam'] : 0, $a));
                $kpi = [_srKpi('Ciro', $c, 'tl', 'E11D48'), $kpiDeg('Ciro, önceki döneme göre', _srYuzde($c, $co), 'BE123C'), _srKpi('Adisyon', count($a), 'sayi', '9F1239'),
                    $kpiTl('Ortalama hesap', count($a) ? round($c / count($a), 2) : null, '881337'), $kpiTl('Önceki dönem ort. hesap', count($ao) ? round($co / count($ao), 2) : null, '64748B'),
                    _srKpi('Yeni müşteri', count($y['yk']), 'sayi', '059669'), $kpiDeg('Yeni müşteri, önceki döneme göre', _srYuzde(count($y['yk']), count($yo['yk'])), '047857'),
                    $kpiY('Geri dönen müşteri cirosu payı', $oran($y['dc'], $y['yc'] + $y['dc']), '0F766E'), $kpiY('Müşteriye bağlı satış', $oran($y['yc'] + $y['dc'], $c), '64748B'),
                    $kpiY('İndirim + ikram oranı', $oran($ii, $brut), 'EA580C'), $kpiY('Önceki dönem oranı', $oran($iio, $bruto), '9A3412'), $kpiY('Paket / online payı', $oran($onl, $c), '2563EB'),
                    _srKpi('Kampanya gönderimi', count($g), 'sayi', 'DB2777'), _srKpi('Kampanya ulaşan kişi', array_sum(array_column($g, 'ulasan')), 'sayi', 'BE185D')];
                $gc = []; $gn = []; $gy = []; $gi = []; $gu = [];
                foreach ($a as $x) { $gc[$x['tarih']] = ($gc[$x['tarih']] ?? 0) + $x['toplam']; $gn[$x['tarih']] = ($gn[$x['tarih']] ?? 0) + 1; $gi[$x['tarih']] = ($gi[$x['tarih']] ?? 0) + $x['indirim'] + $x['ikram']; }
                foreach (_pzrGecmis($sid) as $x) if ($x['ilk'] >= $bas && $x['ilk'] <= $bit) $gy[$x['ilk']] = ($gy[$x['ilk']] ?? 0) + 1;
                foreach ($g as $x) { $t = date('Y-m-d', $x['ts']); $gu[$t] = ($gu[$t] ?? 0) + $x['ulasan']; }
                $gt = array_map(fn ($t) => [$t, _pzrGunAd(date('N', strtotime($t))), round($gc[$t] ?? 0, 2), $gn[$t] ?? 0, !empty($gn[$t]) ? round($gc[$t] / $gn[$t], 2) : null, $gy[$t] ?? 0, round($gi[$t] ?? 0, 2), $gu[$t] ?? 0], _pzrGunler($bas, $bit));
                $bloklar = [_srBlok('Günlere göre ciro ve indirim + ikram', 'col', 'E11D48', [['Gün', 'm'], ['Ciro', 'tl'], ['İndirim + ikram', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[6]], $gt)), $blokKanal($ks, '2563EB'),
                    _srBlok('Yeni / geri dönen / bağlanmamış ciro', 'pie', '059669', [['Grup', 'm'], ['Ciro', 'tl'], ['', 'm']], array_filter([['Yeni müşteri', round($y['yc'], 2), ''], ['Geri dönen', round($y['dc'], 2), ''], ['Müşteri bağlanmamış', round($y['ac'], 2), '']], fn ($x) => $x[1] > 0))];
                $sayfalar = [['grafiksiz' => true] + _srSayfa('Günlük Tablo', 'Yeni müşteri = tüm zamanlardaki ilk siparişini o gün veren. Kampanya ulaşan = o gün giden WhatsApp + uygulama bildirimi.', [['Tarih', 't', 11], ['Gün', 'm', 10], ['Ciro', 'tl', 13, 'top bar'], ['Adisyon', 's', 8, 'top'], ['Ort. Hesap', 'tl', 11], ['Yeni Müşteri', 's', 10, 'top'], ['İndirim + İkram', 'tl', 13, 'top'], ['Kampanya Ulaşan', 's', 12, 'top']], $gt, $bosSatis),
                    _pzrSayfaKanal($ks, $bosSatis), _pzrSayfaGonderim($g, $bosGon), _pzrSayfaIndirim(_pzrIndirimSat($a, $log), _pzrBos('indirim', $dn))];
                if ($anahtar === 'aylik') {
                    $rz = _pzrRezervasyon($sid, $bas, $bit); $ab = array_filter(_pzrPushAbone($sid), fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit); $ic = _pzrIcerik($sid, $bas, $bit); $et = _pzrEtki($sid, $dn);
                    $etD = array_values(array_filter(array_column($et, 'degisim'), fn ($v) => $v !== null));
                    $kpi = array_merge($kpi, [$kpiDeg('Kampanya sonrası 7 gün ciro değişimi (ort.)', $etD ? round(array_sum($etD) / count($etD), 1) : null, '9D174D'), _srKpi('Rezervasyon', count($rz), 'sayi', '1E40AF'),
                        _srKpi('Yeni uygulama abonesi', count($ab), 'sayi', '6D28D9'), _srKpi('Sosyal içerik', count($ic), 'sayi', '0891B2'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _pzrSayfaKazanim(_pzrKazanimSat(_pzrGecmis($sid), $bas, $bit), !$d['musteri_satis'] ? _pzrBos('bagli_yok', $dn) : _pzrBos('yeni', $dn));
                    $sayfalar[] = _pzrSayfaRez(_pzrRezSat($rz), !$d['rezervasyon'] ? _pzrBos('rez_yok', $dn) : _pzrBos('rez', $dn));
                    $sayfalar[] = _pzrSayfaSegment(_pzrSegmentler($sid), _pzrBos('musteri_yok', $dn));
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$a ? $bosSatis : null)], $sayfalar);

            // ---------------- KAMPANYALAR ----------------
            case 'kampanya':
                $g = _pzrGonderim($sid, $bas, $bit); $go = _pzrGonderim($sid, $dn['obas'], $dn['obit']); $h = array_sum(array_column($g, 'hedef')); $u = array_sum(array_column($g, 'ulasan'));
                $seg = []; foreach ($g as $x) { $s = &$seg[$x['segment']]; $s['n'] = ($s['n'] ?? 0) + 1; $s['h'] = ($s['h'] ?? 0) + $x['hedef']; $s['u'] = ($s['u'] ?? 0) + $x['ulasan']; unset($s); }
                $segSat = array_map(fn ($k, $v) => [$k, $v['n'], $v['h'], $v['u'], $v['h'] ? round($v['u'] / $v['h'] * 100, 1) : null], array_keys($seg), array_values($seg)); usort($segSat, fn ($a, $b) => $b[3] <=> $a[3]);
                $kn = []; foreach ($g as $x) $kn[$x['kanal']] = ($kn[$x['kanal']] ?? 0) + $x['ulasan'];
                return [
                    $pano([_srKpi('Gönderim', count($g), 'sayi', 'DB2777'), _srKpi('Önceki dönem gönderim', count($go), 'sayi', '9D174D'), _srKpi('Hedef kişi', $h, 'sayi', 'BE185D'), _srKpi('Ulaşan kişi', $u, 'sayi', 'DB2777'),
                        $kpiY('Ulaşma oranı', $oran($u, $h), '059669'), _srKpi('Atlanan', array_sum(array_column($g, 'atlanan')), 'sayi', 'D97706'),
                        _srKpi('WhatsApp / uygulama', count(array_filter($g, fn ($x) => $x['kod'] === 'wa')) . ' / ' . count(array_filter($g, fn ($x) => $x['kod'] === 'push')), 'metin', '7C3AED')],
                        [_srBlok('Kanala göre ulaşan', 'pie', 'DB2777', [['Kanal', 'm'], ['Ulaşan', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($kn), array_values($kn))),
                         _srBlok('Segmente göre ulaşan', 'bar', 'BE185D', [['Segment', 'm'], ['Ulaşan', 's'], ['Hedef', 's']], array_map(fn ($x) => [$x[0], $x[3], $x[2]], $segSat))], !$g ? $bosGon : null),
                    _pzrSayfaGonderim($g, $bosGon),
                    _srSayfa('Segmente Göre', 'Atlananların çoğu telefonu olmayan ya da uygulaması olmayan müşteridir; Hedef Kitle & Erişim raporundan segmentlerin erişimini görebilirsiniz.', [['Segment', 'm', 24], ['Gönderim', 's', 9, 'top'], ['Hedef', 's', 9, 'top'], ['Ulaşan', 's', 9, 'top bar'], ['Ulaşma %', 'y', 9]], $segSat, $bosGon),
                ];
            case 'kampanya_etki':
                $et = _pzrEtki($sid, $dn); $alVar = count(array_filter($et, fn ($x) => $x['alici'] !== null)); $etD = array_values(array_filter(array_column($et, 'degisim'), fn ($v) => $v !== null));
                $al = array_sum(array_map(fn ($x) => (int) $x['alici'], $et)); $don = array_sum(array_map(fn ($x) => (int) $x['donen'], $et));
                return [
                    $pano([_srKpi('Gönderim', count($et), 'sayi', 'BE185D'), $kpiDeg('Sonraki 7 gün günlük ciro değişimi (ort.)', $etD ? round(array_sum($etD) / count($etD), 1) : null, '9D174D'),
                        _srKpi('Ciro artan gönderim', count(array_filter($etD, fn ($v) => $v > 0)) . ' / ' . count($etD), 'metin', '059669'),
                        _srKpi('Alıcı listesi olan gönderim', $alVar, 'sayi', '64748B'), $kpiY('Alıcı dönüşümü (7 gün)', $alVar ? $oran($don, $al) : null, 'DB2777'),
                        $kpiTl('Alıcıların getirdiği ciro', $alVar ? round(array_sum(array_map(fn ($x) => (float) $x['alici_ciro'], $et)), 2) : null, '047857')],
                        [_srBlok('Gönderim sonrası 7 gün ciro değişimi (%)', 'col', 'BE185D', [['Gönderim', 'm'], ['Değişim %', 'y'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x['tarih'])) . ' ' . $x['segment'], $x['degisim'], ''], array_filter($et, fn ($x) => $x['degisim'] !== null)))],
                        !$et ? $bosGon : (!$alVar ? _pzrBos('alici_yok', $dn) : null)),
                    ['grafiksiz' => true] + _srSayfa('Gönderim Etkisi', 'Önceki 7 gün = gönderimden önceki 7 gün; sonraki = gönderim günü dahil 7 gün (bugünü aşmaz). Değişim günlük ortalama ciroya göre; hava, maç, tatil gibi etkenler de rol oynar. Alıcı / dönüşüm sütunları alıcı listesi kaydedilen gönderimlerde dolar.',
                        [['Tarih', 't', 11], ['Kanal', 'm', 16], ['Segment', 'm', 18], ['Ulaşan', 's', 8, 'top'], ['Ciro Önceki 7 Gün', 'tl', 14, 'top'], ['Ciro Sonraki 7 Gün', 'tl', 14, 'top'], ['Değişim %', 'y', 9], ['Müşterili Sipariş Önce', 's', 13, 'top'], ['Müşterili Sipariş Sonra', 's', 13, 'top'], ['Alıcı', 's', 7], ['Gelen Alıcı', 's', 9], ['Dönüşüm %', 'y', 9], ['Alıcı Cirosu', 'tl', 12]],
                        array_map(fn ($x) => [$x['tarih'], $x['kanal'], $x['segment'], $x['ulasan'], $x['once_ciro'], $x['sonra_ciro'], $x['degisim'], $x['once_sip'], $x['sonra_sip'], $x['alici'], $x['donen'], $x['donusum'], $x['alici_ciro']], $et), $bosGon),
                ];
            case 'kart':
                $kartlar = []; if (_srTablo('app_banner')) { try { $kartlar = DB::table('app_banner')->where('sube_id', $sid)->orderBy('sira')->orderBy('id')->get()->all(); } catch (\Throwable $e) {} }
                $ku = _pzrKartKullanim($sid, $bas, $bit); $gec = _pzrGecmis($sid); $by = [];
                foreach ($ku as $x) { $by[$x['bid']]['n'] = ($by[$x['bid']]['n'] ?? 0) + 1; $by[$x['bid']]['m'][$x['mid']] = 1; if (_pzrSiparisVar($gec, $x['mid'], $x['tarih'], date('Y-m-d', strtotime($x['tarih'] . ' +6 day')))) $by[$x['bid']]['s'][$x['mid']] = 1; }
                $sat = array_map(fn ($b) => [(string) ($b->baslik ?: 'Kart #' . $b->id), ['hero' => 'Ana sayfa afişi', 'kampanya' => 'Kampanya kartı'][$b->tip ?? ''] ?? (string) ($b->tip ?? ''), (string) ($b->kupon_kodu ?? '') ?: '—', (int) ($b->odul_puan ?? 0), (int) ($b->aktif ?? 1) ? 'Yayında' : 'Kapalı',
                    $by[$b->id]['n'] ?? 0, count($by[$b->id]['m'] ?? []), count($by[$b->id]['s'] ?? []), !empty($by[$b->id]['m']) ? round(count($by[$b->id]['s'] ?? []) / count($by[$b->id]['m']) * 100, 1) : null], $kartlar);
                usort($sat, fn ($a, $b) => $b[5] <=> $a[5]);
                $bos = !$kartlar ? _pzrBos('kart_yok', $dn) : _pzrBos('kart', $dn);
                return [
                    $pano([_srKpi('Kart', count($kartlar), 'sayi', '9D174D'), _srKpi('Yayında', count(array_filter($kartlar, fn ($b) => (int) ($b->aktif ?? 1))), 'sayi', 'BE185D'), _srKpi('Kullanım', count($ku), 'sayi', 'DB2777'),
                        _srKpi('Kullanan müşteri', count(array_unique(array_column($ku, 'mid'))), 'sayi', 'EC4899'), _srKpi('7 gün içinde sipariş veren', array_sum(array_column($sat, 7)), 'sayi', '059669'),
                        _srKpi('Dağıtılan ödül puanı', array_sum(array_map(fn ($x) => $x[3] * $x[5], $sat)), 'sayi', 'D97706')],
                        [_srBlok('Karta göre kullanım', 'bar', 'DB2777', [['Kart', 'm'], ['Kullanım', 's'], ['Sipariş veren', 's']], array_map(fn ($x) => [$x[0], $x[5], $x[7]], array_filter($sat, fn ($x) => $x[5] > 0)))], !$ku ? $bos : null),
                    _srSayfa('Kampanya Kartları', 'Kullanım = müşterinin uygulamada karta "Kullan" demesi (kişi başı bir kez). Sipariş veren = kullandıktan sonraki 7 gün içinde (aynı gün dahil) müşteriye bağlı sipariş veren.',
                        [['Kart', 'm', 28], ['Tür', 'm', 14], ['Kupon', 'm', 12], ['Ödül Puan', 's', 9], ['Durum', 'm', 9], ['Kullanım', 's', 9, 'top bar'], ['Kullanan', 's', 9, 'top'], ['Sipariş Veren', 's', 11, 'top'], ['Dönüşüm %', 'y', 10]], $sat, !$kartlar ? _pzrBos('kart_yok', $dn) : null),
                ];

            // ---------------- İNDİRİM & PROMOSYON ----------------
            case 'indirim_maliyet':
                $a = _pzrAdisyon($sid, $bas, $bit); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $log = _pzrIndirimLog($sid, $bas, $bit); $isat = _pzrIndirimSat($a, $log);
                $brut = array_sum(array_column($a, 'ara')); $ind = array_sum(array_column($a, 'indirim')); $ikr = array_sum(array_column($a, 'ikram'));
                $bruto = array_sum(array_column($ao, 'ara')); $iio = array_sum(array_column($ao, 'indirim')) + array_sum(array_column($ao, 'ikram'));
                $gun = []; foreach ($a as $x) { $y = &$gun[$x['tarih']]; $y['b'] = ($y['b'] ?? 0) + $x['ara']; $y['i'] = ($y['i'] ?? 0) + $x['indirim']; $y['k'] = ($y['k'] ?? 0) + $x['ikram']; $y['n'] = ($y['n'] ?? 0) + ($x['indirim'] + $x['ikram'] > 0 ? 1 : 0); unset($y); }
                $gt = array_map(fn ($t) => [$t, _pzrGunAd(date('N', strtotime($t))), round($gun[$t]['b'] ?? 0, 2), round($gun[$t]['i'] ?? 0, 2), round($gun[$t]['k'] ?? 0, 2), round(($gun[$t]['i'] ?? 0) + ($gun[$t]['k'] ?? 0), 2), !empty($gun[$t]['b']) ? round((($gun[$t]['i'] ?? 0) + ($gun[$t]['k'] ?? 0)) / $gun[$t]['b'] * 100, 1) : null, $gun[$t]['n'] ?? 0], _pzrGunler($bas, $bit));
                $kn = []; foreach ($a as $x) { $y = &$kn[$x['kanal']]; $y['b'] = ($y['b'] ?? 0) + $x['ara']; $y['i'] = ($y['i'] ?? 0) + $x['indirim']; $y['k'] = ($y['k'] ?? 0) + $x['ikram']; unset($y); }
                $knSat = array_map(fn ($k, $v) => [$k, round($v['b'], 2), round($v['i'], 2), round($v['k'], 2), $v['b'] > 0 ? round(($v['i'] + $v['k']) / $v['b'] * 100, 1) : null], array_keys($kn), array_values($kn)); usort($knSat, fn ($x, $y) => $y[1] <=> $x[1]);
                $tur = []; foreach ($isat as $x) $tur[$x[0]] = ($tur[$x[0]] ?? 0) + $x[3];
                $indN = count(array_filter($a, fn ($x) => $x['indirim'] + $x['ikram'] > 0));
                return [
                    $pano([_srKpi('İndirim', round($ind, 2), 'tl', 'EA580C'), _srKpi('İkram', round($ikr, 2), 'tl', 'C2410C'), $kpiY('İndirim + ikram / brüt satış', $oran($ind + $ikr, $brut), '9A3412'),
                        $kpiY('Önceki dönem oranı', $oran($iio, $bruto), '64748B'), _srKpi('İndirimli / ikramlı adisyon', $indN, 'sayi', 'D97706'), $kpiY('Adisyonlardaki payı', $oran($indN, count($a)), 'B45309'),
                        _srKpi('Sadakat ödülü maliyeti', round($tur['Sadakat ödülü'] ?? 0, 2), 'tl', 'DB2777'), _srKpi('Sebepsiz personel iskontosu', round($tur['Personel iskontosu (sebepsiz)'] ?? 0, 2), 'tl', 'DC2626')],
                        [_srBlok('Türe göre indirim + ikram', 'pie', 'EA580C', [['Tür', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($tur), array_values($tur))),
                         _srBlok('Günlere göre indirim ve ikram', 'col', 'C2410C', [['Gün', 'm'], ['İndirim', 'tl'], ['İkram', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[3], $x[4]], $gt))], !$a ? $bosSatis : (!($ind + $ikr) ? _pzrBos('indirim', $dn) : null)),
                    ['grafiksiz' => true] + _srSayfa('Günlük', 'Brüt satış = indirim ve ikram öncesi adisyon tutarı. Oran %10\'u geçen günler kırmızı.', [['Tarih', 't', 11], ['Gün', 'm', 10], ['Brüt Satış', 'tl', 14, 'top'], ['İndirim', 'tl', 12, 'top'], ['İkram', 'tl', 12, 'top'], ['Toplam', 'tl', 12, 'top bar'], ['Oran %', 'y', 9, 'esik:10'], ['İndirimli Adisyon', 's', 12, 'top']], $gt, $bosSatis),
                    _pzrSayfaIndirim($isat, _pzrBos('indirim', $dn)),
                    _srSayfa('Kanala Göre', 'Hangi kanalda ne kadar indirim / ikram veriliyor.', [['Kanal', 'm', 22], ['Brüt Satış', 'tl', 14, 'top bar'], ['İndirim', 'tl', 12, 'top'], ['İkram', 'tl', 12, 'top'], ['Oran %', 'y', 9, 'esik:15']], $knSat, $bosSatis),
                ];
            case 'kural':
                $kr = []; if (_srTablo('indirimler')) { try { $kr = DB::table('indirimler')->where('sube_id', $sid)->orderByDesc('aktif')->orderBy('id')->get()->all(); } catch (\Throwable $e) {} }
                $bugun = date('Y-m-d');
                $durum = function ($x) use ($bugun) { if (!(int) $x->aktif) return 'Pasif'; if (!empty($x->bitis) && substr((string) $x->bitis, 0, 10) < $bugun) return 'Süresi doldu (hâlâ aktif)'; if (!empty($x->baslangic) && substr((string) $x->baslangic, 0, 10) > $bugun) return 'Henüz başlamadı'; if (!empty($x->kullanim_limiti) && (int) $x->kullanim_sayisi >= (int) $x->kullanim_limiti) return 'Limit doldu'; return 'Aktif'; };
                $sat = array_map(fn ($x) => [(string) $x->ad, _pzrKuralTipAd($x->tip), ($x->deger_tipi === 'tutar' ? number_format((float) $x->deger, 0, ',', '.') . ' ₺' : '%' . rtrim(rtrim(number_format((float) $x->deger, 2, ',', ''), '0'), ',')) . (!empty($x->max_indirim) ? ' (en çok ' . number_format((float) $x->max_indirim, 0, ',', '.') . ' ₺)' : ''),
                    trim(implode(' · ', array_filter([!empty($x->kupon_kodu) ? 'Kupon: ' . $x->kupon_kodu : '', !empty($x->min_tutar) ? 'Min ' . number_format((float) $x->min_tutar, 0, ',', '.') . ' ₺' : '', !empty($x->saat_bas) ? $x->saat_bas . '–' . $x->saat_bit : '', !empty($x->gun_maskesi) ? _pzrGunMaske($x->gun_maskesi) : '']))) ?: '—',
                    (!empty($x->baslangic) || !empty($x->bitis)) ? (substr((string) $x->baslangic, 0, 10) ?: '…') . ' → ' . (substr((string) $x->bitis, 0, 10) ?: '…') : 'Süresiz', (int) $x->kullanim_sayisi, !empty($x->kullanim_limiti) ? (int) $x->kullanim_limiti : null, $durum($x)], $kr);
                $kmp = []; if (_srTablo('kampanyalar')) { try { $kmp = DB::table('kampanyalar')->where('sube_id', $sid)->orderByDesc('aktif')->orderBy('id')->get()->all(); } catch (\Throwable $e) {} }
                $kmpTip = ['yuzde' => 'Yüzde indirim', 'tutar' => 'Tutar indirim', 'ikinci_bedava' => 'İkincisi bedava', 'urun_hediye' => 'Ürün hediye', 'puan_carpani' => 'Puan çarpanı'];
                $kmpSat = array_map(fn ($x) => [(string) $x->ad, $kmpTip[$x->tip] ?? (string) $x->tip, (float) $x->deger, (float) $x->min_tutar, (!empty($x->baslangic) || !empty($x->bitis)) ? (substr((string) $x->baslangic, 0, 10) ?: '…') . ' → ' . (substr((string) $x->bitis, 0, 10) ?: '…') : 'Süresiz', (int) $x->kullanim, (int) $x->aktif ? 'Aktif' : 'Pasif'], $kmp);
                $fix = []; if (_srTablo('fix_menu')) { try { $fix = DB::table('fix_menu')->where('sube_id', $sid)->get()->all(); } catch (\Throwable $e) {} }
                $kal = []; if ($fix && _srTablo('fix_menu_kalem')) { try { foreach (DB::table('fix_menu_kalem')->whereIn('fix_menu_id', array_map(fn ($f) => $f->id, $fix))->get(['fix_menu_id']) as $r) $kal[$r->fix_menu_id] = ($kal[$r->fix_menu_id] ?? 0) + 1; } catch (\Throwable $e) {} }
                $fs = []; if ($fix) foreach (_pzrKalemler($sid, $bas, $bit) as $k) { $ad = mb_strtolower(trim($k['ad']), 'UTF-8'); $fs[$ad]['n'] = ($fs[$ad]['n'] ?? 0) + $k['adet']; $fs[$ad]['t'] = ($fs[$ad]['t'] ?? 0) + $k['tutar']; }
                $fixSat = array_map(fn ($f) => [(string) $f->ad, (float) $f->fiyat, $kal[$f->id] ?? 0, $fs[mb_strtolower(trim((string) $f->ad), 'UTF-8')]['n'] ?? 0, round($fs[mb_strtolower(trim((string) $f->ad), 'UTF-8')]['t'] ?? 0, 2), (int) $f->aktif ? 'Aktif' : 'Pasif'], $fix);
                return [
                    $pano([_srKpi('İndirim kuralı', count($kr), 'sayi', 'C2410C'), _srKpi('Aktif kural', count(array_filter($sat, fn ($x) => $x[7] === 'Aktif')), 'sayi', '059669'), _srKpi('Kupon kodlu kural', count(array_filter($kr, fn ($x) => !empty($x->kupon_kodu))), 'sayi', 'EA580C'),
                        _srKpi('Toplam kullanım (sayaç)', array_sum(array_column($sat, 5)), 'sayi', '9A3412'), _srKpi('Süresi dolmuş ama aktif', count(array_filter($sat, fn ($x) => str_starts_with($x[7], 'Süresi doldu'))), 'sayi', 'DC2626'),
                        _srKpi('Limiti dolan', count(array_filter($sat, fn ($x) => $x[7] === 'Limit doldu')), 'sayi', 'D97706'), _srKpi('Sadakat kampanyası', count($kmp), 'sayi', 'DB2777'), _srKpi('Set / fix menü', count($fix), 'sayi', '64748B')],
                        [_srBlok('Kurala göre kullanım (tüm zamanlar)', 'bar', 'C2410C', [['Kural', 'm'], ['Kullanım', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[5], ''], array_slice(array_values(array_filter($sat, fn ($x) => $x[5] > 0)), 0, 12)))], !$kr ? _pzrBos('kural_yok', $dn) : null),
                    _srSayfa('İndirim Kuralları', 'Kullanım sayacı kuralın tüm zamanlardaki kullanımıdır (her kullanımda bir artar; dönemle süzülmez). Aynı anda birden çok kural uygunsa en yüksek indirim uygulanır.',
                        [['Kural', 'm', 24], ['Tür', 'm', 16], ['Değer', 'm', 14], ['Koşul', 'm', 28], ['Geçerlilik', 'm', 20], ['Kullanım', 's', 9, 'top bar'], ['Limit', 's', 7], ['Durum', 'm', 20]], $sat, _pzrBos('kural_yok', $dn)),
                    _srSayfa('Sadakat Kampanyaları', 'Sadakat / Puan ekranındaki kampanyalar; kullanım tüm zamanlar sayacıdır.', [['Kampanya', 'm', 24], ['Tip', 'm', 16], ['Değer', 's', 9], ['Min. Tutar', 'tl', 11], ['Geçerlilik', 'm', 22], ['Kullanım', 's', 9, 'top bar'], ['Durum', 'm', 9]], $kmpSat, 'Sadakat kampanyası tanımlı değil (CRM > Sadakat / Puan).'),
                    _srSayfa('Set / Fix Menüler', 'Satış: seçili dönemde POS\'ta menü adıyla aynı isimli ürünün satışı (' . $dn['etiket'] . ').', [['Menü', 'm', 26], ['Fiyat', 'tl', 11], ['Seçenek', 's', 9], ['Satış Adedi', 's', 10, 'top'], ['Ciro', 'tl', 12, 'top bar'], ['Durum', 'm', 9]], $fixSat, _pzrBos('fix_yok', $dn)),
                ];
            case 'happy_hour':
                $hh = _pzrHappy($sid, $dn); $a = _pzrAdisyon($sid, $bas, $bit); $c = _pzrCiro($a); $ur = _pzrUrunler($sid);
                $katAd = []; if (_srTablo('menu_kategorileri')) { try { $katAd = DB::table('menu_kategorileri')->pluck('ad', 'id')->all(); } catch (\Throwable $e) {} }
                $sat = array_map(fn ($v) => [(string) ($v['h']->ad ?: 'Happy hour #' . $v['h']->id), !empty($v['h']->urun_id) ? 'Ürün: ' . ($ur[$v['h']->urun_id]->ad ?? '#' . $v['h']->urun_id) : (!empty($v['h']->kategori_id) ? 'Kategori: ' . ($katAd[$v['h']->kategori_id] ?? '#' . $v['h']->kategori_id) : 'Tüm menü'),
                    _pzrGunMaske($v['h']->gunler) . ' ' . (substr((string) $v['h']->baslangic_saat, 0, 5) ?: '') . ((string) $v['h']->bitis_saat !== '' ? '–' . substr((string) $v['h']->bitis_saat, 0, 5) : ''),
                    (float) $v['h']->happy_fiyat > 0 ? 'Sabit ' . number_format((float) $v['h']->happy_fiyat, 2, ',', '.') . ' ₺' : '%' . rtrim(rtrim(number_format((float) $v['h']->indirim_yuzde, 2, ',', ''), '0'), ','),
                    (int) $v['h']->aktif ? 'Aktif' : 'Pasif', round($v['adet'], 1), round($v['ciro'], 2), round($v['indirim'], 2), round($v['oadet'], 1), _srYuzde($v['adet'], $v['oadet'])], array_values($hh));
                $hc = array_sum(array_column($sat, 6)); $hi = array_sum(array_column($sat, 7)); $ad = array_sum(array_column($sat, 5)); $oad = array_sum(array_column($sat, 8));
                $saatC = []; $saatO = []; foreach ($a as $x) $saatC[$x['saat']] = ($saatC[$x['saat']] ?? 0) + $x['toplam']; foreach (_pzrAdisyon($sid, $dn['obas'], $dn['obit']) as $x) $saatO[$x['saat']] = ($saatO[$x['saat']] ?? 0) + $x['toplam'];
                $saatler = array_unique(array_merge(array_keys($saatC), array_keys($saatO))); sort($saatler);
                $bos = !$hh ? _pzrBos('hh_yok', $dn) : (!$ad ? _pzrBos('hh', $dn) : null);
                return [
                    $pano([_srKpi('Happy hour tanımı', count($hh), 'sayi', '9A3412'), _srKpi('Happy hour satışı (adet)', round($ad, 1), 'sayi', 'EA580C'), _srKpi('Happy hour cirosu', round($hc, 2), 'tl', 'C2410C'),
                        $kpiY('Toplam cirodaki payı', $oran($hc, $c), 'B45309'), _srKpi('Verilen indirim (tahmini)', round($hi, 2), 'tl', 'DC2626'), $kpiDeg('Adet, önceki dönem aynı saat/ürüne göre', _srYuzde($ad, $oad), '059669')],
                        [_srBlok('Saatlere göre ciro: bu dönem ve önceki dönem', 'col', 'EA580C', [['Saat', 'm'], ['Bu dönem', 'tl'], ['Önceki dönem', 'tl']], array_map(fn ($s) => [sprintf('%02d:00', $s), round($saatC[$s] ?? 0, 2), round($saatO[$s] ?? 0, 2)], $saatler))], $bos),
                    _srSayfa('Happy Hour Tanımları', 'Satış: dönemde happy hour gün / saat / ürün koşuluna uyan kalemler (pasif tanım da koşul tutarsa sayılır). İndirim = ürün kartındaki güncel fiyat − satış fiyatı (tahmini). Önceki dönem: aynı koşula uyan satış (indirim o zaman açık olmasa da).',
                        [['Tanım', 'm', 20], ['Kapsam', 'm', 22], ['Gün / Saat', 'm', 22], ['İndirim', 'm', 12], ['Durum', 'm', 8], ['Adet', 's', 8, 'top'], ['Ciro', 'tl', 12, 'top bar'], ['Verilen İndirim', 'tl', 13, 'top'], ['Önceki Dönem Adet', 's', 13, 'top'], ['Adet Değişim %', 'y', 12]], $sat, _pzrBos('hh_yok', $dn)),
                ];

            // ---------------- SATIŞ KANALLARI ----------------
            case 'kanal':
                $a = _pzrAdisyon($sid, $bas, $bit); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $ks = _pzrKanalSat($a, $ao); $c = _pzrCiro($a);
                $onl = array_sum(array_map(fn ($x) => _pzrOnline($x) ? $x['toplam'] : 0, $a)); $kAd = array_column($ks, 0);
                $gun = []; foreach ($a as $x) $gun[$x['tarih']][$x['kanal']] = ($gun[$x['tarih']][$x['kanal']] ?? 0) + $x['toplam'];
                $gSat = array_map(fn ($t) => array_merge([$t], array_map(fn ($k) => round($gun[$t][$k] ?? 0, 2), $kAd), [round(array_sum($gun[$t] ?? []), 2)]), _pzrGunler($bas, $bit));
                return [
                    $pano([_srKpi('Ciro', $c, 'tl', '2563EB'), _srKpi('Aktif kanal', count(array_filter($ks, fn ($x) => $x[1] > 0)), 'sayi', '1D4ED8'), _srKpi('En büyük kanal', $ks && $ks[0][1] > 0 ? $ks[0][0] . ' (%' . number_format($ks[0][2], 1, ',', '.') . ')' : '—', 'metin', '1E40AF'),
                        $kpiY('Paket / online payı', $oran($onl, $c), '0EA5E9'), $kpiTl('Ortalama hesap', count($a) ? round($c / count($a), 2) : null, '0284C7'), $kpiDeg('Ciro, önceki döneme göre', _srYuzde($c, _pzrCiro($ao)), '64748B')],
                        [$blokKanal($ks, '2563EB'), _srBlok('Kanala göre ortalama hesap', 'col', '1D4ED8', [['Kanal', 'm'], ['Ort. hesap', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[4], ''], array_filter($ks, fn ($x) => $x[3] > 0)))], !$a ? $bosSatis : null),
                    _pzrSayfaKanal($ks, $bosSatis),
                    ['grafiksiz' => true] + _srSayfa('Günlere Göre Kanal Cirosu', '', array_merge([['Tarih', 't', 11]], array_map(fn ($k) => [$k, 'tl', 13, 'top'], $kAd), [['Toplam', 'tl', 13, 'top bar']]), $kAd ? $gSat : [], $bosSatis),
                ];
            case 'online':
                $a = array_values(array_filter(_pzrAdisyon($sid, $bas, $bit), '_pzrOnline')); $tum = _pzrCiro(_pzrAdisyon($sid, $bas, $bit)); $gec = _pzrGecmis($sid); $c = _pzrCiro($a);
                $p = []; foreach ($a as $x) { $y = &$p[$x['kanal']]; $y['n'] = ($y['n'] ?? 0) + 1; $y['c'] = ($y['c'] ?? 0) + $x['toplam']; if ($x['mid']) { $y['m'][$x['mid']] = 1; if (($gec[$x['mid']]['ilk'] ?? '') === $x['tarih'] && ($gec[$x['mid']]['ilk'] ?? '') >= $bas) $y['y'][$x['mid']] = 1; if (($gec[$x['mid']]['n'] ?? 0) >= 2) $y['t'][$x['mid']] = 1; } unset($y); }
                $sat = array_map(fn ($k, $v) => [$k, $v['n'], round($v['c'], 2), $c > 0 ? round($v['c'] / $c * 100, 1) : null, round($v['c'] / $v['n'], 2), count($v['m'] ?? []), count($v['y'] ?? []), !empty($v['m']) ? round(count($v['t'] ?? []) / count($v['m']) * 100, 1) : null], array_keys($p), array_values($p));
                usort($sat, fn ($x, $y) => $y[2] <=> $x[2]);
                $h = []; foreach ($a as $x) { $h[$x['saat']]['n'] = ($h[$x['saat']]['n'] ?? 0) + 1; $h[$x['saat']]['c'] = ($h[$x['saat']]['c'] ?? 0) + $x['toplam']; } ksort($h);
                $hSat = array_map(fn ($s, $v) => [sprintf('%02d:00', $s), $v['n'], round($v['c'], 2)], array_keys($h), array_values($h));
                $app = array_sum(array_map(fn ($x) => $x['platform'] === 'app' ? $x['toplam'] : 0, $a));
                $bos = !$d['satis'] ? _pzrBos('satis_yok', $dn) : _pzrBos('online', $dn);
                return [
                    $pano([_srKpi('Paket / online ciro', $c, 'tl', '1D4ED8'), $kpiY('Toplam cirodaki payı', $oran($c, $tum), '2563EB'), _srKpi('Sipariş', count($a), 'sayi', '1E40AF'), $kpiTl('Ortalama sepet', count($a) ? round($c / count($a), 2) : null, '0284C7'),
                        $kpiY('Komisyonsuz uygulama payı', $oran($app, $c), '059669'), _srKpi('Yeni müşteri (ilk sipariş)', array_sum(array_column($sat, 6)), 'sayi', '047857')],
                        [_srBlok('Platforma göre ciro', 'pie', '1D4ED8', [['Platform', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], $sat)),
                         _srBlok('Saatlere göre sipariş', 'col', '2563EB', [['Saat', 'm'], ['Sipariş', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $hSat))], !$a ? $bos : null),
                    _srSayfa('Platformlar', 'Yeni müşteri = ilk siparişini bu dönemde bu platformdan veren. Tekrar eden % = platformda sipariş veren müşterilerden tüm zamanlarda 2+ siparişi olanlar. Komisyonlu platformdaki tekrar eden müşteriyi uygulamaya taşımak komisyon tasarrufu sağlar.',
                        [['Platform', 'm', 22], ['Sipariş', 's', 9, 'top'], ['Ciro', 'tl', 14, 'top bar'], ['Pay %', 'y', 8], ['Ort. Sepet', 'tl', 11], ['Tekil Müşteri', 's', 11, 'top'], ['Yeni Müşteri', 's', 11, 'top'], ['Tekrar Eden %', 'y', 11]], $sat, $bos),
                    _srSayfa('Saatlere Göre', 'Paket / online siparişin kapanış saatine göre dağılımı.', [['Saat', 'm', 8], ['Sipariş', 's', 9, 'top'], ['Ciro', 'tl', 14, 'top bar']], $hSat, $bos),
                ];
            case 'rezervasyon_kaynak':
                $rz = _pzrRezervasyon($sid, $bas, $bit); $rzo = _pzrRezervasyon($sid, $dn['obas'], $dn['obit']); $sat = _pzrRezSat($rz);
                $gl = count(array_filter($rz, fn ($r) => $r['durum'] === 'geldi')); $gm = count(array_filter($rz, fn ($r) => $r['durum'] === 'gelmedi'));
                $gn = []; foreach ($rz as $r) { $gn[$r['gun']]['n'] = ($gn[$r['gun']]['n'] ?? 0) + 1; $gn[$r['gun']]['k'] = ($gn[$r['gun']]['k'] ?? 0) + $r['kisi']; $gn[$r['gun']]['g'] = ($gn[$r['gun']]['g'] ?? 0) + ($r['durum'] === 'gelmedi' ? 1 : 0); } ksort($gn);
                $bos = !$d['rezervasyon'] ? _pzrBos('rez_yok', $dn) : _pzrBos('rez', $dn);
                return [
                    $pano([_srKpi('Rezervasyon', count($rz), 'sayi', '1E40AF'), $kpiDeg('Önceki döneme göre', _srYuzde(count($rz), count($rzo)), '1D4ED8'), _srKpi('Kişi', array_sum(array_column($rz, 'kisi')), 'sayi', '2563EB'),
                        $kpiY('Gelme oranı', $oran($gl, $gl + $gm), '059669'), $kpiY('No-show oranı', $oran($gm, $gl + $gm), 'DC2626'), _srKpi('En çok gelen kaynak', $sat ? $sat[0][0] : '—', 'metin', '64748B')],
                        [_srBlok('Kaynağa göre rezervasyon', 'pie', '1E40AF', [['Kaynak', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)),
                         _srBlok('Haftanın günlerine göre', 'col', '2563EB', [['Gün', 'm'], ['Rezervasyon', 's'], ['Gelmedi', 's']], array_map(fn ($n, $v) => [_pzrGunAd($n), $v['n'], $v['g']], array_keys($gn), array_values($gn)))], !$rz ? $bos : null),
                    _pzrSayfaRez($sat, $bos),
                    _srSayfa('Haftanın Günlerine Göre', '', [['Gün', 'm', 12], ['Rezervasyon', 's', 11, 'top bar'], ['Kişi', 's', 8, 'top'], ['Gelmedi', 's', 8, 'top esik:1']], array_map(fn ($n, $v) => [_pzrGunAd($n), $v['n'], $v['k'], $v['g']], array_keys($gn), array_values($gn)), $bos),
                ];

            // ---------------- MÜŞTERİ KAZANIMI ----------------
            case 'kazanim':
                $gec = _pzrGecmis($sid); $mu = _pzrMusteriler($sid); $yeni = array_filter($gec, fn ($x) => $x['ilk'] >= $bas && $x['ilk'] <= $bit); $yo = array_filter($gec, fn ($x) => $x['ilk'] >= $dn['obas'] && $x['ilk'] <= $dn['obit']);
                $kayit = array_filter($mu, fn ($m) => $m->kayit && $m->kayit >= $bas && $m->kayit <= $bit); $ks = _pzrKazanimSat($gec, $bas, $bit);
                $don = count(array_filter($yeni, fn ($x) => $x['ikinci'])); $gunler = array_values(array_filter(array_map(fn ($x) => $x['ikinci'] ? (strtotime($x['ikinci']) - strtotime($x['ilk'])) / 86400 : null, $yeni), fn ($v) => $v !== null));
                $gy = []; foreach ($yeni as $x) $gy[$x['ilk']] = ($gy[$x['ilk']] ?? 0) + 1; $gk = []; foreach ($kayit as $m) $gk[$m->kayit] = ($gk[$m->kayit] ?? 0) + 1;
                $gt = array_map(fn ($t) => [$t, $gy[$t] ?? 0, $gk[$t] ?? 0], _pzrGunler($bas, $bit));
                $liste = []; foreach ($yeni as $mid => $x) $liste[] = [$x['ilk'], $mu[$mid]->ad ?? 'Müşteri #' . $mid, $x['ilkKanal'], round($x['ilkTutar'], 2), $x['n'], round($x['ciro'], 2), $x['ikinci']];
                usort($liste, fn ($a, $b) => $b[0] <=> $a[0]);
                $bos = !$d['musteri_satis'] ? _pzrBos('bagli_yok', $dn) : _pzrBos('yeni', $dn);
                return [
                    $pano([_srKpi('Yeni müşteri (ilk sipariş)', count($yeni), 'sayi', '059669'), _srKpi('Önceki dönem', count($yo), 'sayi', '047857'), $kpiDeg('Değişim', _srYuzde(count($yeni), count($yo)), '0F766E'),
                        _srKpi('Yeni kayıt (müşteri kartı)', count($kayit), 'sayi', '15803D'), $kpiTl('İlk sepet ortalaması', $yeni ? round(array_sum(array_column($yeni, 'ilkTutar')) / count($yeni), 2) : null, '166534'),
                        $kpiY('2. siparişe dönüş', $oran($don, count($yeni)), 'DB2777'), _srKpi('2. siparişe ort. gün', $gunler ? round(array_sum($gunler) / count($gunler), 1) : '—', $gunler ? 'sayi' : 'metin', '64748B')],
                        [_srBlok('İlk sipariş kanalına göre yeni müşteri', 'pie', '059669', [['Kanal', 'm'], ['Yeni müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $ks)),
                         _srBlok('Günlere göre yeni müşteri', 'col', '047857', [['Gün', 'm'], ['Yeni (ilk sipariş)', 's'], ['Yeni kayıt', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $gt))], !$yeni ? $bos : null),
                    _pzrSayfaKazanim($ks, $bos),
                    ['grafiksiz' => true] + _srSayfa('Günlük', 'Yeni kayıt = müşteri kartının açıldığı gün (sipariş vermemiş olabilir).', [['Tarih', 't', 11], ['Yeni Müşteri (ilk sipariş)', 's', 18, 'top bar'], ['Yeni Kayıt', 's', 11, 'top']], $gt, $bos),
                    _srSayfa('Yeni Müşteriler', 'Bu dönemde ilk siparişini veren müşteriler; toplam sipariş ve ciro bugüne kadarki.', [['İlk Sipariş', 't', 11], ['Müşteri', 'm', 22], ['Kanal', 'm', 18], ['İlk Tutar', 'tl', 11], ['Toplam Sipariş', 's', 11, 'top'], ['Toplam Ciro', 'tl', 12, 'top bar'], ['2. Sipariş', 't', 11]], $liste, $bos),
                ];
            case 'yeni_donen':
                $a = _pzrAdisyon($sid, $bas, $bit); $y = $ayir($a, $bas); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $yo = $ayir($ao, $dn['obas']); $c = _pzrCiro($a); $gec = _pzrGecmis($sid);
                $gun = []; $kn = [];
                foreach ($a as $x) {
                    $tip = !$x['mid'] ? 'a' : (($gec[$x['mid']]['ilk'] ?? $x['tarih']) >= $bas ? 'y' : 'd');
                    $gun[$x['tarih']][$tip] = ($gun[$x['tarih']][$tip] ?? 0) + $x['toplam']; if ($x['mid']) $gun[$x['tarih']][$tip . 'k'][$x['mid']] = 1;
                    $kn[$x['kanal']][$tip] = ($kn[$x['kanal']][$tip] ?? 0) + $x['toplam'];
                }
                $gt = array_map(fn ($t) => [$t, round($gun[$t]['y'] ?? 0, 2), round($gun[$t]['d'] ?? 0, 2), round($gun[$t]['a'] ?? 0, 2), count($gun[$t]['yk'] ?? []), count($gun[$t]['dk'] ?? [])], _pzrGunler($bas, $bit));
                $knSat = array_map(fn ($k, $v) => [$k, round($v['y'] ?? 0, 2), round($v['d'] ?? 0, 2), round($v['a'] ?? 0, 2), (($v['y'] ?? 0) + ($v['d'] ?? 0)) > 0 ? round(($v['d'] ?? 0) / (($v['y'] ?? 0) + ($v['d'] ?? 0)) * 100, 1) : null], array_keys($kn), array_values($kn));
                usort($knSat, fn ($p, $q) => ($q[1] + $q[2] + $q[3]) <=> ($p[1] + $p[2] + $p[3]));
                $yN = count($y['yk']); $dN = count($y['dk']);
                return [
                    $pano([$kpiY('Müşteriye bağlı satış', $oran($y['yc'] + $y['dc'], $c), '64748B'), _srKpi('Yeni müşteri cirosu', round($y['yc'], 2), 'tl', '059669'), _srKpi('Geri dönen müşteri cirosu', round($y['dc'], 2), 'tl', '047857'),
                        $kpiY('Geri dönen payı (bağlı satışta)', $oran($y['dc'], $y['yc'] + $y['dc']), '0F766E'), $kpiY('Önceki dönem geri dönen payı', $oran($yo['dc'], $yo['yc'] + $yo['dc']), '115E59'),
                        _srKpi('Yeni / geri dönen müşteri', $yN . ' / ' . $dN, 'metin', '15803D'), $kpiTl('Yeni müşteri başı ciro', $yN ? round($y['yc'] / $yN, 2) : null, '166534'), $kpiTl('Geri dönen müşteri başı ciro', $dN ? round($y['dc'] / $dN, 2) : null, '14532D')],
                        [_srBlok('Ciro dağılımı', 'pie', '059669', [['Grup', 'm'], ['Ciro', 'tl'], ['', 'm']], array_filter([['Yeni müşteri', round($y['yc'], 2), ''], ['Geri dönen', round($y['dc'], 2), ''], ['Müşteri bağlanmamış', round($y['ac'], 2), '']], fn ($x) => $x[1] > 0)),
                         _srBlok('Günlere göre yeni ve geri dönen cirosu', 'col', '047857', [['Gün', 'm'], ['Yeni', 'tl'], ['Geri dönen', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $gt))],
                        !$a ? $bosSatis : (!($y['yc'] + $y['dc']) ? $bosBagli : null)),
                    ['grafiksiz' => true] + _srSayfa('Günlük', 'Yeni = tüm zamanlardaki ilk siparişi bu dönemde olan müşteri; geri dönen = daha önce de sipariş vermiş. Müşteri bağlanmamış = adisyonda müşteri seçilmemiş (çoğunlukla salon).',
                        [['Tarih', 't', 11], ['Yeni Müşteri Cirosu', 'tl', 15, 'top'], ['Geri Dönen Cirosu', 'tl', 15, 'top bar'], ['Bağlanmamış Ciro', 'tl', 14, 'top'], ['Yeni (kişi)', 's', 9], ['Geri Dönen (kişi)', 's', 12]], $gt, $bosSatis),
                    _srSayfa('Kanala Göre', '', [['Kanal', 'm', 22], ['Yeni Müşteri Cirosu', 'tl', 15, 'top'], ['Geri Dönen Cirosu', 'tl', 15, 'top bar'], ['Bağlanmamış Ciro', 'tl', 14, 'top'], ['Geri Dönen Payı %', 'y', 13]], $knSat, $bosSatis),
                ];
            case 'kitle':
                $sg = _pzrSegmentler($sid); $mu = _pzrMusteriler($sid); $push = _pzrPushAbone($sid); $tum = $sg['tumu'];
                $hic = count(array_filter($mu, fn ($m) => !isset($push[$m->id]) && strlen(preg_replace('/\D/', '', $m->telefon)) < 10));
                return [
                    $pano([_srKpi('Kayıtlı müşteri', $tum['n'], 'sayi', '0F766E'), _srKpi('Uygulama bildirimi açık', $tum['push'], 'sayi', '6D28D9'), $kpiY('Push erişim oranı', $oran($tum['push'], $tum['n']), '7C3AED'),
                        _srKpi('Telefonu kayıtlı (WhatsApp)', $tum['tel'], 'sayi', '059669'), _srKpi('Hiçbir kanaldan ulaşılamayan', $hic, 'sayi', 'DC2626'), _srKpi('Uyuyan müşteri', $sg['uyuyan']['n'], 'sayi', 'D97706')],
                        [_srBlok('Segment büyüklüğü ve push erişimi', 'bar', '0F766E', [['Segment', 'm'], ['Müşteri', 's'], ['Push açık', 's']], array_map(fn ($v) => [$v['ad'], $v['n'], $v['push']], array_values($sg)))], !$mu ? _pzrBos('musteri_yok', $dn) : null),
                    _pzrSayfaSegment($sg, _pzrBos('musteri_yok', $dn)),
                ];

            // ---------------- MESAJ & UYGULAMA ----------------
            case 'mesaj':
                $wa = _pzrWaLog($sid, $bas, $bit); $wo = _pzrWaLog($sid, $dn['obas'], $dn['obit']); $ot = _pzrOtomatikMesaj($sid, $bas, $bit);
                $gid = array_filter($wa, fn ($x) => $x['yon'] !== 'gelen'); $gel = count($wa) - count($gid); $ok = count(array_filter($gid, fn ($x) => $x['durum'] === 1)); $hat = count(array_filter($gid, fn ($x) => $x['durum'] === 2)); $sms = count(array_filter($gid, fn ($x) => $x['durum'] === 3));
                $gun = []; foreach ($wa as $x) { $k = $x['yon'] === 'gelen' ? 'gel' : ['0' => 'kuy', '1' => 'ok', '2' => 'hata', '3' => 'sms'][(string) $x['durum']] ?? 'ok'; $gun[$x['tarih']][$k] = ($gun[$x['tarih']][$k] ?? 0) + 1; }
                $gt = array_map(function ($t) use ($gun) { $v = $gun[$t] ?? []; $gd = ($v['ok'] ?? 0) + ($v['hata'] ?? 0) + ($v['sms'] ?? 0) + ($v['kuy'] ?? 0); return [$t, $gd, $v['ok'] ?? 0, $v['hata'] ?? 0, $v['sms'] ?? 0, $v['gel'] ?? 0, $gd ? round(($v['ok'] ?? 0) / $gd * 100, 1) : null]; }, _pzrGunler($bas, $bit));
                $hs = []; foreach ($gid as $x) if ($x['durum'] === 2) { $h = $x['hata'] !== '' ? mb_substr($x['hata'], 0, 80) : 'Sebep kaydı yok'; $hs[$h] = ($hs[$h] ?? 0) + 1; } arsort($hs);
                $bos = !$d['wa_log'] ? _pzrBos('wa_yok', $dn) : _pzrBos('wa', $dn);
                return [
                    $pano([_srKpi('Giden mesaj', count($gid), 'sayi', '7C3AED'), $kpiDeg('Önceki döneme göre', _srYuzde(count($gid), count(array_filter($wo, fn ($x) => $x['yon'] !== 'gelen'))), '6D28D9'), $kpiY('Başarı oranı', $oran($ok, count($gid)), '059669'),
                        _srKpi('Başarısız', $hat, 'sayi', 'DC2626'), _srKpi('SMS\'e düşen', $sms, 'sayi', 'D97706'), _srKpi('Gelen mesaj (müşteri yazdı)', $gel, 'sayi', '0EA5E9'), _srKpi('Otomatik mesaj', array_sum(array_column($ot, 1)), 'sayi', '64748B')],
                        [_srBlok('Günlere göre giden ve gelen', 'col', '7C3AED', [['Gün', 'm'], ['Giden', 's'], ['Gelen', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[5]], $gt)),
                         _srBlok('Otomatik mesaj türleri', 'pie', '6D28D9', [['Tür', 'm'], ['Mesaj', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $ot))], !$wa ? $bos : null),
                    ['grafiksiz' => true] + _srSayfa('Günlük Mesaj', 'Durum WhatsApp köprüsünden gelir: başarılı, başarısız, SMS\'e düştü (WhatsApp gitmeyince SMS ile gönderildi).', [['Tarih', 't', 11], ['Giden', 's', 8, 'top bar'], ['Başarılı', 's', 9, 'top'], ['Başarısız', 's', 9, 'top esik:1'], ['SMS\'e Düşen', 's', 10, 'top'], ['Gelen', 's', 8, 'top'], ['Başarı %', 'y', 9]], $gt, $bos),
                    _srSayfa('Otomatik Mesajlar', 'Sistemin kendiliğinden gönderdiği mesajlar (rezervasyon hatırlatma, doğum günü, anket vb.).', [['Tür', 'm', 28], ['Mesaj', 's', 9, 'top bar']], $ot, 'Seçili dönemde otomatik mesaj gönderilmemiş.'),
                    _srSayfa('Başarısızlık Sebepleri', '', [['Sebep', 'm', 50], ['Adet', 's', 8, 'top bar']], array_map(fn ($k, $v) => [$k, $v], array_keys($hs), array_values($hs)), 'Seçili dönemde başarısız gönderim yok.'),
                ];
            case 'app':
                $push = _pzrPushAbone($sid); $mu = _pzrMusteriler($sid); $yeni = array_filter($push, fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit); $yo = array_filter($push, fn ($x) => $x['tarih'] >= $dn['obas'] && $x['tarih'] <= $dn['obit']);
                $a = _pzrAdisyon($sid, $bas, $bit); $app = array_values(array_filter($a, fn ($x) => $x['platform'] === 'app')); $ac = _pzrCiro($app); $onl = array_values(array_filter($a, fn ($x) => _pzrOnline($x) && $x['platform'] !== 'app'));
                $pl = []; foreach ($push as $x) { $k = ['ios' => 'iOS', 'android' => 'Android', 'web' => 'Web'][$x['platform']] ?? 'Bilinmiyor'; $pl[$k] = ($pl[$k] ?? 0) + 1; } arsort($pl);
                $gy = []; foreach ($yeni as $x) $gy[$x['tarih']] = ($gy[$x['tarih']] ?? 0) + 1; $gs = []; $gc = []; foreach ($app as $x) { $gs[$x['tarih']] = ($gs[$x['tarih']] ?? 0) + 1; $gc[$x['tarih']] = ($gc[$x['tarih']] ?? 0) + $x['toplam']; }
                $gt = array_map(fn ($t) => [$t, $gy[$t] ?? 0, $gs[$t] ?? 0, round($gc[$t] ?? 0, 2)], _pzrGunler($bas, $bit));
                $bos = !$d['app'] ? _pzrBos('app_yok', $dn) : null;
                return [
                    $pano([_srKpi('Bildirim abonesi (toplam)', count($push), 'sayi', '6D28D9'), _srKpi('Dönemde yeni abone', count($yeni), 'sayi', '7C3AED'), $kpiDeg('Önceki döneme göre', _srYuzde(count($yeni), count($yo)), '8B5CF6'),
                        $kpiY('Müşterilerin uygulamadaki payı', $oran(count(array_intersect_key($push, $mu)), count($mu)), '5B21B6'), _srKpi('Uygulama siparişi', count($app), 'sayi', '059669'), _srKpi('Uygulama cirosu', $ac, 'tl', '047857'),
                        $kpiTl('Uygulama ort. sepet', count($app) ? round($ac / count($app), 2) : null, '0F766E'), $kpiTl('Diğer online ort. sepet', count($onl) ? round(_pzrCiro($onl) / count($onl), 2) : null, '64748B')],
                        [_srBlok('Platform dağılımı (abone)', 'pie', '6D28D9', [['Platform', 'm'], ['Abone', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($pl), array_values($pl))),
                         _srBlok('Günlere göre yeni abone ve uygulama siparişi', 'col', '7C3AED', [['Gün', 'm'], ['Yeni abone', 's'], ['Sipariş', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $gt))], $bos),
                    ['grafiksiz' => true] + _srSayfa('Günlük', 'Yeni abone = bildirim iznini ilk kez veren müşteri (cihaz kaydı). Uygulama siparişi = müşteri uygulamasından gelen ödenmiş sipariş.', [['Tarih', 't', 11], ['Yeni Abone', 's', 10, 'top'], ['Uygulama Siparişi', 's', 13, 'top'], ['Uygulama Cirosu', 'tl', 14, 'top bar']], $gt, $bos ?? _pzrBos('satis', $dn)),
                    _srSayfa('Platform', 'Abonelerin cihaz platformu.', [['Platform', 'm', 16], ['Abone', 's', 9, 'top bar']], array_map(fn ($k, $v) => [$k, $v], array_keys($pl), array_values($pl)), _pzrBos('app_yok', $dn)),
                ];

            // ---------------- İÇERİK & ZAMANLAMA ----------------
            case 'icerik':
                $ic = _pzrIcerik($sid, $bas, $bit); $hedef = 3; $hf = [];
                for ($t = strtotime('monday this week', strtotime($bas)); $t <= strtotime($bit); $t += 7 * 86400) $hf[date('Y-m-d', $t)] = 0;
                foreach ($ic as $x) { $w = date('Y-m-d', strtotime('monday this week', strtotime($x['tarih']))); $hf[$w] = ($hf[$w] ?? 0) + 1; }
                $hSat = array_map(fn ($w, $n) => [$w, $n, $hedef, min(100, round($n / $hedef * 100, 1))], array_keys($hf), array_values($hf));
                $pl = []; foreach ($ic as $x) $pl[$x['platform']] = ($pl[$x['platform']] ?? 0) + 1; arsort($pl);
                $bos = !$d['icerik'] ? _pzrBos('icerik_yok', $dn) : _pzrBos('icerik', $dn);
                return [
                    $pano([_srKpi('İçerik', count($ic), 'sayi', '0891B2'), _srKpi('Haftalık ortalama', $hf ? round(count($ic) / count($hf), 1) : 0, 'sayi', '0E7490'), $kpiY('Hedefe uyum (haftada ' . $hedef . ')', $hSat ? round(array_sum(array_column($hSat, 3)) / count($hSat), 1) : null, '155E75'),
                        _srKpi('İçeriksiz hafta', count(array_filter($hf, fn ($n) => $n === 0)), 'sayi', 'DC2626'), _srKpi('Platform', count($pl), 'sayi', '64748B')],
                        [_srBlok('Haftalık içerik', 'col', '0891B2', [['Hafta', 'm'], ['İçerik', 's'], ['Hedef', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $hSat)),
                         _srBlok('Platforma göre', 'pie', '0E7490', [['Platform', 'm'], ['İçerik', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($pl), array_values($pl)))], !$ic ? $bos : null),
                    ['grafiksiz' => true] + _srSayfa('Haftalık', 'Hedef: haftada ' . $hedef . ' paylaşım (restoranlarda önerilen alt sınır). Taslak kaydedilen içerik sayılır.', [['Hafta (Pzt)', 't', 11], ['İçerik', 's', 8, 'top bar'], ['Hedef', 's', 7], ['Uyum %', 'y', 9]], $hSat, $bos),
                    _srSayfa('İçerikler', '', [['Tarih', 't', 11], ['Platform', 'm', 12], ['Konu', 'm', 46], ['Durum', 'm', 10]], array_map(fn ($x) => [$x['tarih'], $x['platform'], $x['konu'] ?: '—', $x['durum']], $ic), $bos),
                ];
            case 'sessiz':
                $a = _pzrAdisyon($sid, $bas, $bit); $gs = []; foreach (_pzrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gs[$n] = ($gs[$n] ?? 0) + 1; }
                $m = []; $saatler = []; foreach ($a as $x) { $m[$x['gun']][$x['saat']]['c'] = ($m[$x['gun']][$x['saat']]['c'] ?? 0) + $x['toplam']; $m[$x['gun']][$x['saat']]['n'] = ($m[$x['gun']][$x['saat']]['n'] ?? 0) + 1; $saatler[$x['saat']] = 1; }
                ksort($saatler); $saatler = array_keys($saatler);
                $mat = []; $dil = [];
                for ($n = 1; $n <= 7; $n++) { if (empty($gs[$n])) continue; $mat[] = array_merge([_pzrGunAd($n)], array_map(fn ($h) => isset($m[$n][$h]) ? round($m[$n][$h]['c'] / $gs[$n], 0) : null, $saatler));
                    foreach ($saatler as $h) $dil[] = [_pzrGunAd($n), sprintf('%02d:00', $h), round(($m[$n][$h]['c'] ?? 0) / $gs[$n], 2), round(($m[$n][$h]['n'] ?? 0) / $gs[$n], 2)]; }
                $max = $dil ? max(array_column($dil, 2)) : 0; foreach ($dil as &$x) $x[] = $max > 0 ? round($x[2] / $max * 100, 1) : null; unset($x);
                $sirali = $dil; usort($sirali, fn ($p, $q) => $p[2] <=> $q[2]); $yogun = $dil; usort($yogun, fn ($p, $q) => $q[2] <=> $p[2]);
                $gunC = []; foreach ($m as $n => $hs) $gunC[$n] = array_sum(array_column($hs, 'c')) / max(1, $gs[$n] ?? 1); ksort($gunC);
                $hiC = 0; $hiN = 0; $hsC = 0; $hsN = 0; foreach ($gunC as $n => $v) if ($n <= 5) { $hiC += $v; $hiN++; } else { $hsC += $v; $hsN++; }
                $saatC = []; foreach ($a as $x) $saatC[$x['saat']] = ($saatC[$x['saat']] ?? 0) + $x['toplam']; ksort($saatC);
                return [
                    $pano([_srKpi('En yoğun dilim', $yogun ? $yogun[0][0] . ' ' . $yogun[0][1] : '—', 'metin', '0E7490'), _srKpi('En sessiz dilim', $sirali ? $sirali[0][0] . ' ' . $sirali[0][1] : '—', 'metin', 'D97706'),
                        $kpiTl('Hafta içi ort. günlük ciro', $hiN ? round($hiC / $hiN, 2) : null, '0891B2'), $kpiTl('Hafta sonu ort. günlük ciro', $hsN ? round($hsC / $hsN, 2) : null, '155E75'),
                        _srKpi('Ciroya kapalı dilim (ort. %20 altı)', count(array_filter($dil, fn ($x) => ($x[4] ?? 0) < 20)), 'sayi', 'DC2626')],
                        [_srBlok('Haftanın günlerine göre ortalama ciro', 'col', '0891B2', [['Gün', 'm'], ['Ort. ciro', 'tl'], ['', 'm']], array_map(fn ($n, $v) => [_pzrGunAd($n), round($v, 2), ''], array_keys($gunC), array_values($gunC))),
                         _srBlok('Saatlere göre toplam ciro', 'col', '0E7490', [['Saat', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($s, $v) => [sprintf('%02d:00', $s), round($v, 2), ''], array_keys($saatC), array_values($saatC)))], !$a ? $bosSatis : null),
                    _srSayfa('Gün × Saat Ortalama Ciro', 'O gün ve saatte kapanan adisyonların dönemdeki o günün sayısına bölünmüş cirosu (₺). Koyu = yoğun, açık = sessiz.', array_merge([['Gün', 'm', 11]], array_map(fn ($h) => [sprintf('%02d', $h), 's', 6, 'mat'], $saatler)), $mat, $bosSatis),
                    _srSayfa('En Sessiz 15 Dilim', 'Satış olan saatler içinde ortalama cirosu en düşük gün-saatler; happy hour, mesai saatine özel kampanya ve bildirim için aday. "En yoğuna göre" = en yoğun dilimin yüzde kaçı.', [['Gün', 'm', 11], ['Saat', 'm', 7], ['Ort. Ciro', 'tl', 12], ['Ort. Adisyon', 's', 10], ['En Yoğuna Göre %', 'y', 13]], array_slice($sirali, 0, 15), $bosSatis),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_pzrAiBaglam')) {
    function _pzrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $a = _pzrAdisyon($sid, $bas, $bit); $ao = _pzrAdisyon($sid, $dn['obas'], $dn['obit']); $c = _pzrCiro($a); $co = _pzrCiro($ao);
        $gec = _pzrGecmis($sid); $sp = function ($a, $b0) use ($gec) { $y = ['yeni_ciro' => 0, 'donen_ciro' => 0, 'baglanmamis_ciro' => 0, 'yeni_kisi' => [], 'donen_kisi' => []];
            foreach ($a as $x) { if (!$x['mid']) { $y['baglanmamis_ciro'] += $x['toplam']; continue; } if (($gec[$x['mid']]['ilk'] ?? $x['tarih']) >= $b0) { $y['yeni_ciro'] += $x['toplam']; $y['yeni_kisi'][$x['mid']] = 1; } else { $y['donen_ciro'] += $x['toplam']; $y['donen_kisi'][$x['mid']] = 1; } }
            $y['yeni_kisi'] = count($y['yeni_kisi']); $y['donen_kisi'] = count($y['donen_kisi']); foreach (['yeni_ciro', 'donen_ciro', 'baglanmamis_ciro'] as $k) $y[$k] = round($y[$k], 2); return $y; };
        $brut = array_sum(array_column($a, 'ara')); $ii = array_sum(array_column($a, 'indirim')) + array_sum(array_column($a, 'ikram')); $bruto = array_sum(array_column($ao, 'ara')); $iio = array_sum(array_column($ao, 'indirim')) + array_sum(array_column($ao, 'ikram'));
        $g = _pzrGonderim($sid, $bas, $bit); $et = _pzrEtki($sid, $dn); $hh = _pzrHappy($sid, $dn); $sg = _pzrSegmentler($sid); $wa = _pzrWaLog($sid, $bas, $bit); $push = _pzrPushAbone($sid); $rz = _pzrRezSat(_pzrRezervasyon($sid, $bas, $bit));
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _pzrVeriDurumu($sid),
            'satis' => ['ciro_tl' => $c, 'onceki_ciro_tl' => $co, 'adisyon' => count($a), 'onceki_adisyon' => count($ao), 'ort_hesap_tl' => count($a) ? round($c / count($a), 2) : null, 'onceki_ort_hesap_tl' => count($ao) ? round($co / count($ao), 2) : null],
            'kanallar' => array_map(fn ($x) => ['kanal' => $x[0], 'ciro_tl' => $x[1], 'pay_yuzde' => $x[2], 'adisyon' => $x[3], 'ort_hesap_tl' => $x[4], 'indirim_ikram_yuzde' => $x[7], 'onceki_ciro_tl' => $x[8], 'degisim_yuzde' => $x[9]], _pzrKanalSat($a, $ao)),
            'musteri' => ['bu_donem' => $sp($a, $bas), 'onceki_donem' => $sp($ao, $dn['obas']), 'yeni_ilk_siparis_kanallari' => array_map(fn ($x) => ['kanal' => $x[0], 'yeni' => $x[1], 'ikinci_siparis_donus_yuzde' => $x[4]], _pzrKazanimSat($gec, $bas, $bit))],
            'indirim' => ['indirim_ikram_tl' => round($ii, 2), 'brut_satisa_oran_yuzde' => $brut > 0 ? round($ii / $brut * 100, 1) : null, 'onceki_oran_yuzde' => $bruto > 0 ? round($iio / $bruto * 100, 1) : null,
                'tur_sebep' => array_map(fn ($x) => ['tur' => $x[0], 'sebep' => $x[1], 'tl' => $x[3]], array_slice(_pzrIndirimSat($a, _pzrIndirimLog($sid, $bas, $bit)), 0, 8))],
            'happy_hour' => array_values(array_map(fn ($v) => ['ad' => $v['h']->ad, 'aktif' => (int) $v['h']->aktif, 'adet' => round($v['adet'], 1), 'ciro_tl' => round($v['ciro'], 2), 'indirim_tl' => round($v['indirim'], 2), 'onceki_adet' => round($v['oadet'], 1)], $hh)),
            'kampanya' => ['gonderim' => count($g), 'hedef' => array_sum(array_column($g, 'hedef')), 'ulasan' => array_sum(array_column($g, 'ulasan')),
                'etki' => array_map(fn ($x) => ['tarih' => $x['tarih'], 'kanal' => $x['kanal'], 'segment' => $x['segment'], 'ulasan' => $x['ulasan'], 'sonraki_7_gun_ciro_degisim_yuzde' => $x['degisim'], 'alici_donusum_yuzde' => $x['donusum']], array_slice($et, -10))],
            'segmentler' => array_map(fn ($v) => ['segment' => $v['ad'], 'musteri' => $v['n'], 'push_acik' => $v['push'], 'telefonu_var' => $v['tel'], 'son30_ciro_tl' => round($v['c30'], 2)], array_values($sg)),
            'whatsapp' => ['giden' => count(array_filter($wa, fn ($x) => $x['yon'] !== 'gelen')), 'basarisiz' => count(array_filter($wa, fn ($x) => $x['yon'] !== 'gelen' && $x['durum'] === 2)), 'gelen' => count(array_filter($wa, fn ($x) => $x['yon'] === 'gelen'))],
            'uygulama' => ['bildirim_abonesi' => count($push), 'donemde_yeni_abone' => count(array_filter($push, fn ($x) => $x['tarih'] >= $bas && $x['tarih'] <= $bit)), 'uygulama_ciro_tl' => _pzrCiro(array_filter($a, fn ($x) => $x['platform'] === 'app'))],
            'rezervasyon_kaynak' => array_map(fn ($x) => ['kaynak' => $x[0], 'rezervasyon' => $x[1], 'no_show_yuzde' => $x[8]], $rz),
            'sosyal_icerik' => count(_pzrIcerik($sid, $bas, $bit))];
    }
    function _pzrAiNabiz($sid, array $dn)
    {
        $b = _pzrAiBaglam($sid, $dn); $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺'; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $dg = _srYuzde($b['satis']['ciro_tl'], $b['satis']['onceki_ciro_tl']); $ka = $b['kanallar'][0] ?? null; $io = $b['indirim']['brut_satisa_oran_yuzde']; $ioo = $b['indirim']['onceki_oran_yuzde'];
        $m = $b['musteri']['bu_donem']; $bag = $m['yeni_ciro'] + $m['donen_ciro']; $dp = $bag > 0 ? round($m['donen_ciro'] / $bag * 100, 1) : null; $mo = $b['musteri']['onceki_donem'];
        return [
            ['renk' => '#2563EB', 'kat' => 'kanal', 'ikon' => 'hub', 'etiket' => 'Ciro', 'deger' => $tl($b['satis']['ciro_tl']), 'alt' => $dg !== null ? '%' . number_format(abs($dg), 1, ',', '.') . ($dg >= 0 ? ' arttı' : ' düştü') . ($ka ? ' · en büyük: ' . $ka['kanal'] : '') : 'Önceki dönemde satış yok', 'sinif' => $dg !== null ? ($dg >= 0 ? 'yesil' : 'kirmizi') : '', 'ok' => $dg !== null ? ($dg >= 0 ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#EA580C', 'kat' => 'indirim', 'ikon' => 'sell', 'etiket' => 'İndirim + ikram oranı', 'deger' => $y($io), 'alt' => $tl($b['indirim']['indirim_ikram_tl']) . ($ioo !== null ? ' · önceki ' . $y($ioo) : ''), 'sinif' => $io !== null && $io > 10 ? 'kirmizi' : ($io !== null ? 'yesil' : '')],
            ['renk' => '#059669', 'kat' => 'musteri', 'ikon' => 'person_add', 'etiket' => 'Yeni müşteri', 'deger' => (string) $m['yeni_kisi'], 'alt' => 'Önceki dönem ' . $mo['yeni_kisi'], 'sinif' => $m['yeni_kisi'] >= $mo['yeni_kisi'] ? 'yesil' : 'turuncu'],
            ['renk' => '#047857', 'kat' => 'musteri', 'ikon' => 'autorenew', 'etiket' => 'Geri dönen payı', 'deger' => $y($dp), 'alt' => $m['donen_kisi'] . ' geri dönen müşteri', 'sinif' => $dp === null ? '' : ($dp >= 50 ? 'yesil' : 'turuncu')],
            ['renk' => '#DB2777', 'kat' => 'kampanya', 'ikon' => 'campaign', 'etiket' => 'Kampanya ulaşan', 'deger' => number_format($b['kampanya']['ulasan'], 0, ',', '.'), 'alt' => $b['kampanya']['gonderim'] . ' gönderim', 'sinif' => $b['kampanya']['gonderim'] ? '' : 'turuncu'],
            ['renk' => '#6D28D9', 'kat' => 'iletisim', 'ikon' => 'install_mobile', 'etiket' => 'Bildirim abonesi', 'deger' => number_format($b['uygulama']['bildirim_abonesi'], 0, ',', '.'), 'alt' => '+' . $b['uygulama']['donemde_yeni_abone'] . ' bu dönem', 'sinif' => $b['uygulama']['donemde_yeni_abone'] ? 'yesil' : ''],
        ];
    }
}

// ---------- Modül kaydı ----------
_drKaydet('pazarlama', [
    'ad' => 'Pazarlama', 'baslik' => 'Pazarlama Raporları', 'yol' => '/pazarlama-raporlar', 'grup' => 'Pazarlama', 'depKodlari' => ['pazarlama'],
    'ensure' => '_pzrEnsure', 'katalog' => '_pzrKatalog', 'kategoriler' => '_pzrKategoriler', 'veri' => '_pzrVeriDurumu', 'rapor' => '_pzrRapor',
    'olcek' => ['kucuk' => ['donemsel', 'indirim', 'kanal', 'ai'], 'orta' => ['kampanya', 'musteri']],
    'modAd' => ['satis' => 'Satış (adisyon)', 'musteri' => 'Müşteri kartı', 'musteri_satis' => 'Müşteriye bağlı adisyon', 'wa_kampanya' => 'WhatsApp kampanyası', 'push_kampanya' => 'Uygulama bildirimi kampanyası',
        'indirim' => 'İndirim / ikram kaydı', 'kural' => 'İndirim kuralı', 'happy' => 'Happy hour', 'fix' => 'Set / fix menü', 'kart' => 'Uygulama kampanya kartı', 'kart_kullanim' => 'Kampanya kartı kullanımı',
        'rezervasyon' => 'Rezervasyon', 'wa_log' => 'WhatsApp mesaj kaydı', 'app' => 'Uygulama / bildirim abonesi', 'icerik' => 'Sosyal içerik', 'alici' => 'Kampanya alıcı listesi'],
    'modLink' => ['satis' => ['/pos', 'POS / adisyon'], 'musteri' => ['/musteri-360', 'CRM > Müşteri 360 (paket / uygulama siparişinde otomatik)'], 'musteri_satis' => ['/pos', 'POS\'ta adisyona müşteri seçmek'],
        'wa_kampanya' => ['/kampanya', 'Kampanya Stüdyosu (WhatsApp)'], 'push_kampanya' => ['/kampanya-push', 'Kampanya Stüdyosu (uygulama bildirimi)'], 'indirim' => ['/pos', 'POS\'ta iskonto / ikram (sebebiyle)'],
        'kural' => ['/indirimler', 'İndirimler'], 'happy' => ['/happy-hour', 'Happy Hour'], 'fix' => ['/fix-menu', 'Fix Menü'], 'kart' => [null, 'App Yönetimi > kampanya kartları'], 'kart_kullanim' => [null, 'Müşteri uygulamasında kartta "Kullan"'],
        'rezervasyon' => ['/rezervasyon', 'Rezervasyonlar'], 'wa_log' => [null, 'WhatsApp hattı bağlantısı (otomatik kayıt)'], 'app' => [null, 'Müşteri uygulamasında bildirim izni'], 'icerik' => ['/social-media', 'AI Sosyal Medya > Kaydet'],
        'alici' => [null, 'Kampanya gönderiminde otomatik (Faz 2)']],
    'ai' => [
        'gerekli' => ['satis', 'musteri_satis', 'wa_kampanya', 'indirim', 'musteri'],
        'hero' => ['Pazarlamanın dönemini 20 saniyede özetleyeyim.', 'Kanalları, yeni ve geri dönen müşteriyi, indirim ve ikram maliyetini, happy hour\'u, kampanyaların etkisini, segment erişimini, WhatsApp ve uygulama abonelerini okur.'],
        'sorular' => ['Hangi kanal büyüyor, hangisi geriliyor?', 'İndirimlerim kârı yiyor mu?', 'Son kampanyam işe yaradı mı?', 'Yeni müşteri kazanımı neden düştü?', 'Uyuyan müşterileri geri getirmek için ne yapayım?', 'Happy hour\'u hangi saate taşımalıyım?'],
        'adimlar' => ['Satış ve kanallar okunuyor…', 'Önceki dönemle karşılaştırılıyor…', 'Yeni ve geri dönen müşteriler ayrılıyor…', 'İndirim, ikram ve happy hour hesaplanıyor…', 'Kampanya etkisi ve segment erişimi inceleniyor…', 'Öneriler yazılıyor…'],
        'selam' => 'pazarlama',
        'sys' => 'Sen bir restoranın pazarlamasını patron ve pazarlama sorumlusu için analiz eden deneyimli bir restoran pazarlama ve büyüme danışmanısın. Kanal karmasını, yeni müşteri kazanımını, geri dönen müşteri payını, indirim ve ikramın brüt satışa oranını (restoranda genelde %5-10 sağlıklı), happy hour etkisini ve kampanyaların gönderim sonrası etkisini yorumla. '
            . 'Kampanya etkisi gönderim öncesi/sonrası 7 günün genel karşılaştırmasıdır; alıcı dönüşümü yoksa nedensellik iddia etme. Komisyonlu platformlardaki tekrar eden müşteriyi komisyonsuz uygulamaya taşımayı, toplu WhatsApp yerine segmentli ve izinli iletişimi öner. Müşterilerin kişisel verisini yorumlama.',
        'baglam' => '_pzrAiBaglam',
        'veriVar' => fn ($b) => $b['satis']['adisyon'] > 0 || $b['kampanya']['gonderim'] > 0,
        'nabiz' => '_pzrAiNabiz',
    ],
]);

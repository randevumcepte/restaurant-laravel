<?php
// =====================================================================
// REZERVASYON RAPORLAR & ANALİZLER — /rezervasyon-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: rezervasyonlar (tarih/saat, kişi, durum, kaynak, masa, not, etiketler, musteri_id, adisyon_id), rezervasyon_kalemleri (ön sipariş),
//       adisyonlar (oturtulan rezervasyonun cirosu, rezervasyonsuz salon karşılaştırması), masalar (kapasite), depozitolar (kapora),
//       bekleme_listesi (karşılama / walk-in sırası), musteriler (CRM notu, doğum günü), santral_oturumlari (AI Santral'dan gelen).
// Grup / etkinlik: kişi sayısı ≥ grup eşiği (şube ayarı, varsayılan 8) ya da depozitosu olan rezervasyon.
// Faz 2 (bundan sonrasını ölçer): rezervasyonlar.durum_at — durum değişince damga (_rzrDurumDamga) → son dakika iptali, geç gelme.
// Erişim: patron / müdür / süpervizör / Rezervasyon Satış (satis_etkinlik) SORUMLUSU / 'gor_satis_etkinlik_rapor'.
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2 alanı + ayarlar ----------
if (!function_exists('_rzrEnsure')) {
    function _rzrEnsure()
    {
        if (cache()->get('rzr_ensure_v1')) return;
        try { if (Schema::hasTable('rezervasyonlar') && !Schema::hasColumn('rezervasyonlar', 'durum_at')) Schema::table('rezervasyonlar', function ($t) { $t->timestamp('durum_at')->nullable(); }); } catch (\Throwable $e) {}
        cache()->put('rzr_ensure_v1', 1, now()->addHours(12));
    }
    // Rezervasyon durumu değişince zaman damgası (iptal ne zaman yapıldı, misafir ne zaman geldi). Güncelleme dizisini döndürür.
    function _rzrDurumDamga(array $upd)
    {
        try { if (isset($upd['durum']) && Schema::hasColumn('rezervasyonlar', 'durum_at')) $upd['durum_at'] = now(); } catch (\Throwable $e) {}
        return $upd;
    }
    // Şube ayarı: grup / etkinlik eşiği (kişi)
    function _rzrAyar($sid)
    {
        $v = function_exists('_subeAyarAl') ? (int) _subeAyarAl($sid, 'rzr_grup_esik', '8') : 8;
        return ['grup' => max(4, min(50, $v ?: 8))];
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_rzrRez')) {
    function _rzrTel($t) { $d = preg_replace('/\D/', '', (string) $t); return strlen($d) >= 10 ? substr($d, -10) : null; }
    function _rzrMaske($t) { $d = _rzrTel($t); return $d ? '0' . substr($d, 0, 3) . ' *** ' . substr($d, 6, 2) . ' ' . substr($d, 8, 2) : '—'; }
    function _rzrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    function _rzrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _rzrDurumAd($d) { return ['bekliyor' => 'Onay bekliyor', 'onaylandi' => 'Onaylandı', 'geldi' => 'Geldi', 'gelmedi' => 'Gelmedi (no-show)', 'iptal' => 'İptal'][$d] ?? ($d ?: '—'); }
    function _rzrKaynakAd($k) { return ['santral' => 'AI Santral (telefon)', 'telefon' => 'Telefon (personel)', 'web' => 'Web / online', 'qr' => 'QR menü', 'walk_in' => 'Kapıdan (walk-in)', 'admin' => 'Panel (personel)', 'wa' => 'WhatsApp', 'whatsapp' => 'WhatsApp', 'app' => 'Müşteri uygulaması'][$k] ?? ($k ? ucfirst($k) : 'Belirsiz'); }
    function _rzrOzelAd($k) { return ['dogum' => 'Doğum günü', 'yildonumu' => 'Yıldönümü / evlilik', 'alerji' => 'Alerji / diyet', 'vip' => 'VIP', 'bebek' => 'Bebek sandalyesi', 'engelli' => 'Tekerlekli sandalye / erişim', 'manzara' => 'Pencere / manzara', 'sessiz' => 'Sessiz köşe', 'is' => 'İş yemeği', 'diger' => 'Diğer istek'][$k] ?? $k; }
    // Etiket + not metninden özel gün / istek türleri
    function _rzrOzelTur($etiketler, $not)
    {
        $m = mb_strtolower(implode(' ', $etiketler) . ' ' . (string) $not, 'UTF-8'); $o = [];
        foreach (['dogum' => '/doğum ?gün|dogum ?gun|birthday|pasta/u', 'yildonumu' => '/yıldönüm|yildonum|evlilik|nişan|nisan|teklif/u', 'alerji' => '/alerj|glüten|gluten|vegan|vejetaryen|laktoz|fıstık|fistik|diyet|çölyak|colyak/u',
            'vip' => '/\bvip\b/u', 'bebek' => '/bebek|mama sandalye/u', 'engelli' => '/tekerlekli|engelli|erişim|erisim/u', 'manzara' => '/pencere|manzara|teras|cam kenar/u', 'sessiz' => '/sessiz|sakin köşe/u', 'is' => '/iş yemeği|is yemegi|toplantı|toplanti/u'] as $k => $re)
            if (preg_match($re, $m)) $o[] = $k;
        if (!$o && $etiketler) $o[] = 'diger';
        return $o;
    }
    // Dönemdeki rezervasyonlar (rezervasyon TARİHİNE göre). Lead = rezervasyonun alındığı an → rezervasyon saati.
    function _rzrRez($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('rezervasyonlar')) return $c[$key] = [];
        $sel = ['id', 'ad', 'telefon', 'kisi', 'tarih', 'saat', 'durum', 'kaynak', 'not', 'created_at', 'masa_id'];
        foreach (['musteri_id', 'etiketler', 'adisyon_id', 'durum_at'] as $k) $sel[] = _srKolon('rezervasyonlar', $k) ? $k : DB::raw('NULL as ' . $k);
        try { $rows = DB::table('rezervasyonlar')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->orderBy('tarih')->orderBy('saat')->get($sel); } catch (\Throwable $e) { $rows = []; }
        $masa = []; try { if (_srTablo('masalar')) $masa = DB::table('masalar')->where('sube_id', $sid)->get(['id', 'ad', 'kapasite'])->keyBy('id')->all(); } catch (\Throwable $e) {}
        $adIds = []; foreach ($rows as $r) if ($r->adisyon_id) $adIds[] = (int) $r->adisyon_id;
        $ad = [];
        if ($adIds && _srTablo('adisyonlar')) { try { foreach (array_chunk(array_unique($adIds), 500) as $ch) foreach (DB::table('adisyonlar')->whereIn('id', $ch)->get(['id', 'toplam', 'durum', 'misafir_sayisi']) as $a) $ad[(int) $a->id] = $a; } catch (\Throwable $e) {} }
        $kalem = [];
        if (_srTablo('rezervasyon_kalemleri') && count($rows)) { try { foreach (array_chunk(array_map(fn ($r) => (int) $r->id, $rows instanceof \Illuminate\Support\Collection ? $rows->all() : $rows), 500) as $ch) foreach (DB::table('rezervasyon_kalemleri')->whereIn('rezervasyon_id', $ch)->groupBy('rezervasyon_id')->selectRaw('rezervasyon_id rid, SUM(adet) n, SUM(adet * fiyat) tl')->get() as $k) $kalem[(int) $k->rid] = ['n' => (int) $k->n, 'tl' => (float) $k->tl]; } catch (\Throwable $e) {} }
        $o = []; $bugun = date('Y-m-d');
        foreach ($rows as $r) {
            $t = substr((string) $r->tarih, 0, 10); $s = preg_match('/^\d{1,2}:\d{2}/', (string) $r->saat) ? substr(str_pad((string) $r->saat, 5, '0', STR_PAD_LEFT), 0, 5) : '00:00';
            $ts = strtotime("$t $s"); $cr = $r->created_at ? strtotime((string) $r->created_at) : null;
            $et = array_values(array_filter(array_map(fn ($x) => trim(preg_replace('/[^\p{L}\p{N}\s\-\/]/u', '', $x)), explode(',', (string) $r->etiketler))));
            $not = trim((string) $r->not); $santral = str_starts_with($not, 'AI Santral');
            $kay = $santral ? 'santral' : (string) ($r->kaynak ?: 'belirsiz');
            $a = $r->adisyon_id ? ($ad[(int) $r->adisyon_id] ?? null) : null; $ciro = $a && $a->durum === 'odendi' ? (float) $a->toplam : 0.0;
            $mk = $r->masa_id ? ($masa[(int) $r->masa_id] ?? null) : null;
            $o[] = ['id' => (int) $r->id, 'ad' => (string) $r->ad, 'tel' => _rzrTel($r->telefon), 'kisi' => max(1, (int) $r->kisi), 'tarih' => $t, 'saat' => $s, 'h' => (int) substr($s, 0, 2), 'gun' => (int) date('N', strtotime($t)),
                'durum' => (string) $r->durum, 'kaynak' => $kay, 'not' => $santral ? trim(preg_replace('/^AI Santral\s*[—-]?\s*/u', '', $not)) : $not, 'etiket' => $et, 'ozel' => _rzrOzelTur($et, $not),
                'lead' => $cr ? max(0, round(($ts - $cr) / 3600, 1)) : null, 'olusma' => $cr ? date('Y-m-d H:i', $cr) : '', 'durum_at' => $r->durum_at ? strtotime((string) $r->durum_at) : null, 'ts' => $ts,
                'masa_id' => (int) $r->masa_id, 'masa' => $mk ? (string) $mk->ad : '', 'kapasite' => $mk ? (int) $mk->kapasite : 0, 'musteri_id' => (int) $r->musteri_id,
                'adisyon_id' => (int) $r->adisyon_id, 'ciro' => $ciro, 'misafir' => $a ? (int) $a->misafir_sayisi : 0, 'on_n' => $kalem[(int) $r->id]['n'] ?? 0, 'on_tl' => round($kalem[(int) $r->id]['tl'] ?? 0, 2),
                'acik' => in_array($r->durum, ['bekliyor', 'onaylandi'], true) && $t < $bugun];
        }
        return $c[$key] = $o;
    }
    // Özet sayılar: no-show oranı yalnız SONUÇLANAN (geldi + gelmedi) rezervasyonlardan
    function _rzrOzet(array $l)
    {
        $s = ['n' => count($l), 'kisi' => 0, 'geldi' => 0, 'gelmedi' => 0, 'iptal' => 0, 'bekliyor' => 0, 'onaylandi' => 0, 'acik' => 0, 'gelen_kisi' => 0, 'ciro' => 0.0, 'ciro_n' => 0, 'ciro_kisi' => 0, 'aktif_kisi' => 0];
        foreach ($l as $x) {
            $s['kisi'] += $x['kisi']; if (isset($s[$x['durum']])) $s[$x['durum']]++; if ($x['acik']) $s['acik']++;
            if ($x['durum'] !== 'iptal') $s['aktif_kisi'] += $x['kisi'];
            if ($x['durum'] === 'geldi') { $s['gelen_kisi'] += $x['kisi']; if ($x['ciro'] > 0) { $s['ciro'] += $x['ciro']; $s['ciro_n']++; $s['ciro_kisi'] += $x['misafir'] ?: $x['kisi']; } }
        }
        $son = $s['geldi'] + $s['gelmedi'];
        $s['noshow'] = $son > 0 ? round($s['gelmedi'] / $son * 100, 1) : null; $s['iptal_or'] = $s['n'] > 0 ? round($s['iptal'] / $s['n'] * 100, 1) : null;
        $s['kisi_basi'] = $s['ciro_kisi'] > 0 ? round($s['ciro'] / $s['ciro_kisi'], 2) : null; $s['ort_kisi'] = $s['n'] > 0 ? round($s['kisi'] / $s['n'], 1) : null; $s['ciro'] = round($s['ciro'], 2);
        return $s;
    }
    function _rzrKapasite($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('masalar')) return $c[$sid] = 0;
        try { $q = DB::table('masalar')->where('sube_id', $sid); if (_srKolon('masalar', 'arsiv')) $q->where(function ($w) { $w->whereNull('arsiv')->orWhere('arsiv', 0); }); return $c[$sid] = (int) $q->sum('kapasite'); } catch (\Throwable $e) { return $c[$sid] = 0; }
    }
    // Rezervasyonsuz salon adisyonları (aynı dönemde): ortalama hesap ve kişi başı (karşılaştırma için)
    function _rzrSalonDis($sid, $bas, $bit, array $rezAdIds)
    {
        if (!_srTablo('adisyonlar')) return ['n' => 0, 'ciro' => 0, 'kisi' => 0];
        $set = array_flip($rezAdIds); $n = 0; $ciro = 0; $kisi = 0;
        try {
            $q = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereNotNull('masa_id')->whereBetween('kapanis', [$bas . ' 00:00:00', $bit . ' 23:59:59']);
            foreach ($q->get(['id', 'toplam', 'misafir_sayisi']) as $a) { if (isset($set[(int) $a->id])) continue; $n++; $ciro += (float) $a->toplam; $kisi += (int) $a->misafir_sayisi; }
        } catch (\Throwable $e) {}
        return ['n' => $n, 'ciro' => round($ciro, 2), 'kisi' => $kisi];
    }
    // Depozitolar: etkinlik tarihi dönemde (tarihsizse kayıt tarihi). Eşleşen rezervasyon (telefon + tarih) ile yanma / kullanım tespiti.
    function _rzrDepozito($sid, $bas, $bit)
    {
        if (!_srTablo('depozitolar')) return [];
        try { $rows = DB::table('depozitolar')->where('sube_id', $sid)->where(function ($w) use ($bas, $bit) { $w->whereBetween('etkinlik_tarih', [$bas, $bit])->orWhere(function ($w2) use ($bas, $bit) { $w2->whereNull('etkinlik_tarih')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59']); }); })->orderBy('etkinlik_tarih')->get(); } catch (\Throwable $e) { $rows = []; }
        $tarihler = []; foreach ($rows as $r) if ($r->etkinlik_tarih) $tarihler[] = substr((string) $r->etkinlik_tarih, 0, 10);
        $rez = [];
        if ($tarihler && _srTablo('rezervasyonlar')) { try { foreach (DB::table('rezervasyonlar')->where('sube_id', $sid)->whereIn('tarih', array_unique($tarihler))->get(['id', 'telefon', 'tarih', 'durum', 'kisi']) as $x) { $tl = _rzrTel($x->telefon); if ($tl) $rez[$tl . '|' . substr((string) $x->tarih, 0, 10)] = $x; } } catch (\Throwable $e) {} }
        $o = []; $bugun = date('Y-m-d');
        foreach ($rows as $r) {
            $t = $r->etkinlik_tarih ? substr((string) $r->etkinlik_tarih, 0, 10) : null; $tl = _rzrTel($r->telefon); $es = ($tl && $t) ? ($rez[$tl . '|' . $t] ?? null) : null;
            $tutar = (float) $r->tutar; $alinan = (float) $r->alinan; $d = (string) $r->durum; $gecti = $t && $t < $bugun;
            $sonuc = match (true) {
                $d === 'iade' => 'İade edildi', $d === 'yandi' => 'Yandı (gelmedi)', $d === 'tamam' => 'Kullanıldı / kapatıldı',
                $gecti && $es && $es->durum === 'gelmedi' && $alinan > 0 => 'Yanma adayı: misafir gelmedi',
                $gecti && $es && $es->durum === 'geldi' => 'Misafir geldi, kapora kapatılmamış',
                $gecti && $alinan <= 0 => 'Tarih geçti, tahsil edilmedi',
                $gecti => 'Tarih geçti, sonuç girilmemiş',
                $alinan >= $tutar && $tutar > 0 => 'Tahsil edildi (yaklaşan)',
                default => 'Tahsilat bekliyor (yaklaşan)'};
            $o[] = ['ad' => (string) $r->ad, 'tel' => $tl, 'tarih' => $t, 'kisi' => (int) $r->kisi, 'tutar' => $tutar, 'alinan' => $alinan, 'kalan' => max(0, round($tutar - $alinan, 2)), 'durum' => $d, 'sonuc' => $sonuc,
                'rez' => $es ? _rzrDurumAd($es->durum) : ($t ? 'Eşleşen rezervasyon yok' : 'Tarih girilmemiş'), 'not' => (string) $r->not, 'kayit' => substr((string) $r->created_at, 0, 10), 'gecti' => $gecti];
        }
        return $o;
    }
    // Bekleme listesi (karşılama / walk-in): gerçek bekleme = çağrılma (yoksa oturtulma) − sıraya giriş
    function _rzrBekleme($sid, $bas, $bit)
    {
        if (!_srTablo('bekleme_listesi')) return [];
        try { $rows = DB::table('bekleme_listesi')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) {
            $g = strtotime((string) $r->created_at); $c = $r->cagrildi_at ? strtotime((string) $r->cagrildi_at) : (($r->durum === 'oturtuldu' && $r->updated_at) ? strtotime((string) $r->updated_at) : null);
            $dk = $c && $c >= $g ? (int) round(($c - $g) / 60) : null; if ($dk !== null && $dk > 300) $dk = null;
            $o[] = ['ad' => (string) $r->ad, 'kisi' => (int) $r->kisi, 'tarih' => date('Y-m-d', $g), 'saat' => date('H:i', $g), 'h' => (int) date('G', $g), 'tahmini' => $r->tahmini_dk !== null ? (int) $r->tahmini_dk : null, 'gercek' => $dk,
                'durum' => (string) $r->durum, 'adisyon_id' => (int) ($r->adisyon_id ?? 0), 'not' => (string) ($r->not ?? '')];
        }
        return $o;
    }
    function _rzrBeklemeDurumAd($d) { return ['bekliyor' => 'Sırada', 'cagrildi' => 'Çağrıldı', 'oturtuldu' => 'Oturtuldu', 'iptal' => 'Vazgeçti / iptal'][$d] ?? $d; }
    function _rzrLeadGrup($h) { if ($h === null) return 'Bilinmiyor'; return $h < 3 ? '3 saatten az' : ($h < 24 ? '3–24 saat' : ($h < 72 ? '1–3 gün' : ($h < 168 ? '4–7 gün' : ($h < 720 ? '8–30 gün' : '30 gün +')))); }
    function _rzrBoyGrup($k) { return $k <= 2 ? '1–2 kişi' : ($k <= 4 ? '3–4 kişi' : ($k <= 7 ? '5–7 kişi' : ($k <= 12 ? '8–12 kişi' : '13 kişi +'))); }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_rzrVeriDurumu')) {
    function _rzrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t)->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d = ['rezervasyon' => $say('rezervasyonlar', 'tarih'), 'sonuc' => $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereIn('durum', ['geldi', 'gelmedi'])),
            'masa_atama' => $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereNotNull('masa_id')),
            'adisyon_bag' => _srKolon('rezervasyonlar', 'adisyon_id') ? $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereNotNull('adisyon_id')) : 0,
            'etiket' => _srKolon('rezervasyonlar', 'etiketler') ? $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereNotNull('etiketler')->where('etiketler', '!=', '')) : 0,
            'telefon' => $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereNotNull('telefon')->where('telefon', '!=', '')),
            'santral' => $say('rezervasyonlar', 'tarih', fn ($q) => $q->where('not', 'like', 'AI Santral%')),
            'kapasite' => _srTablo('masalar') ? (int) DB::table('masalar')->where('sube_id', $sid)->where('kapasite', '>', 0)->count() : 0,
            'depozito' => $say('depozitolar', 'created_at'), 'bekleme' => $say('bekleme_listesi', 'created_at'), 'on_siparis' => 0, 'durum_at' => 0];
        if (_srTablo('rezervasyon_kalemleri') && _srTablo('rezervasyonlar')) { try { $d['on_siparis'] = (int) DB::table('rezervasyon_kalemleri as k')->join('rezervasyonlar as r', 'k.rezervasyon_id', '=', 'r.id')->where('r.sube_id', $sid)->where('r.tarih', '>=', $s)->count(); } catch (\Throwable $e) {} }
        if (_srKolon('rezervasyonlar', 'durum_at')) $d['durum_at'] = $say('rezervasyonlar', 'tarih', fn ($q) => $q->whereNotNull('durum_at'));
        return $d;
    }
    function _rzrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'rez_yok' => 'Rezervasyon kaydı yok: son 1 yılda bu şubeye rezervasyon girilmemiş. Raporlar Rezervasyon Satış > Rezervasyonlar ekranından, Patron uygulamasından, web rezervasyon formundan ve AI Santral\'ın aldığı telefon rezervasyonlarından beslenir.',
            'rez' => 'Seçili dönemde (' . $donem . ') rezervasyon yok.',
            'sonuc_yok' => 'Rezervasyonların sonucu işaretlenmemiş: misafir gelince "Geldi" (ya da Patron uygulamasında "Oturt"), gelmeyince "Gelmedi" seçilmeli. Gelmeyen (no-show) oranı ve rezervasyon cirosu bu işaretten hesaplanır; tarihi geçip "Onaylandı / Bekliyor"da kalan kayıtlar "Sonuçlanmamış" sayılır.',
            'noshow' => 'Seçili dönemde (' . $donem . ') gelmeyen (no-show) rezervasyon yok.',
            'iptal' => 'Seçili dönemde (' . $donem . ') iptal edilen rezervasyon yok.',
            'acik' => 'Tarihi geçip sonucu girilmemiş rezervasyon yok — tüm kayıtlar kapatılmış.',
            'ciro_yok' => 'Gelen rezervasyonların adisyonu yok: ciro, rezervasyon Patron uygulamasında "Oturt" ile masaya açılınca (rezervasyon → adisyon bağı) ölçülür. Webde yalnız "Geldi" işaretlemek adisyonu bağlamaz.',
            'kapasite_yok' => 'Masa kapasitesi girilmemiş. Doluluk = rezervasyonlu kişi ÷ toplam masa kapasitesi. Masalar ekranında her masaya kişi kapasitesi girin.',
            'masa_yok' => 'Rezervasyonlara masa atanmamış. Masa kullanımı raporu için rezervasyonu alırken ya da onaylarken masa seçin.',
            'on_siparis' => 'Seçili dönemde (' . $donem . ') ön siparişli rezervasyon yok. Patron uygulamasında rezervasyon alırken "Ön sipariş" ile misafirin ne yiyeceği girilebilir; gelince adisyona otomatik düşer.',
            'grup' => 'Seçili dönemde (' . $donem . ') grup / etkinlik büyüklüğünde (eşik üstü kişi) rezervasyon yok.',
            'pipeline' => 'Önümüzdeki 60 günde grup, özel gün ya da depozitolu rezervasyon yok.',
            'ozel' => 'Seçili dönemde (' . $donem . ') özel gün / istek etiketi ya da notu olan rezervasyon yok. Patron uygulamasında rezervasyonda Doğum günü, Yıldönümü, Alerji, VIP gibi etiketler seçilebilir.',
            'depozito_yok' => 'Depozito (kapora) kaydı yok. Grup ve etkinlik rezervasyonlarında no-show\'u azaltmak için Rezervasyon Satış > Depozito / Kapora ekranından kapora kaydı tutun.',
            'depozito' => 'Seçili dönemde (' . $donem . ') etkinlik tarihi düşen depozito yok.',
            'tel_yok' => 'Rezervasyonlarda telefon yok. Tekrar gelen misafir ve no-show geçmişi telefon numarasından eşleştirilir; rezervasyonda telefon girin.',
            'tekrar' => 'Son 1 yılda birden fazla rezervasyon yapan misafir yok.',
            'crm' => 'Seçili dönemde ve önümüzdeki 14 günde alerji, özel gün ya da notu olan rezervasyon yok.',
            'bekleme_yok' => 'Bekleme listesi kaydı yok. Kapıdan gelen ve masa bekleyen misafirler Karşılama (Host) ekranındaki bekleme listesine yazılınca bekleme süresi, vazgeçme ve oturtma oranı ölçülür.',
            'bekleme' => 'Seçili dönemde (' . $donem . ') bekleme listesine giren misafir yok.',
            'durum_at' => 'İptal / geliş zamanı bu rapor modülü kurulduktan sonra durum değişikliklerinde kaydedilir; eski kayıtlarda boş görünür.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_rzrKatalog')) {
    function _rzrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Rezervasyon Bülteni', 'Dün kaç rezervasyon geldi, kim gelmedi, iptaller, ciro ve kişi başı; bugün ve yarın gelecek misafirlerin alerji / özel gün notları; bekleme listesi.', 'today', '#A855F7', ['rezervasyon'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Rezervasyon Raporu', 'Gün gün rezervasyon ve kişi, kanallar, no-show ve iptal oranı, doluluk, grup rezervasyonları ve önceki haftayla kıyas.', 'date_range', '#9333EA', ['rezervasyon', 'sonuc'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Rezervasyon Yönetim Raporu', 'Ay boyunca rezervasyon hacmi, kanal performansı, no-show, rezervasyon cirosu, doluluk, grup / etkinlik, depozito ve sadık misafirler — patron için tek dosya.', 'calendar_month', '#7E22CE', ['rezervasyon', 'sonuc'], 'gecen_ay'],
            'hacim' => ['hacim', 'Rezervasyon & Kişi Trendi', 'Gün gün rezervasyon sayısı, kişi, durum dağılımı ve önceki döneme göre değişim; haftanın günlerine göre yoğunluk.', 'trending_up', '#2563EB', ['rezervasyon'], null],
            'kaynak' => ['hacim', 'Kaynak / Kanal Analizi', 'Rezervasyon nereden geliyor (AI Santral, telefon, web, QR, kapıdan, panel): sayı, kişi, gelme ve no-show oranı, ciro ve kişi başı.', 'hub', '#1D4ED8', ['rezervasyon'], null],
            'lead' => ['hacim', 'Önceden Rezervasyon Süresi', 'Rezervasyon kaç saat / gün önceden yapılıyor; kanallara göre ortalama ve önceden alınma süresine göre no-show.', 'schedule', '#1E40AF', ['rezervasyon'], null],
            'boyut' => ['hacim', 'Masa Büyüklüğü (Kişi Sayısı)', '1–2, 3–4, 5–7, 8+ kişilik rezervasyonların dağılımı, gelme oranı ve kişi başı ciro.', 'groups', '#3B82F6', ['rezervasyon'], null],
            'noshow' => ['gelmeyen', 'Gelmeyen (No-show) Analizi', 'Sonuçlanan rezervasyonlarda gelmeme oranı; güne, saate, kanala ve kişi sayısına göre; birden fazla kez gelmeyen misafirler.', 'event_busy', '#DC2626', ['sonuc'], null],
            'iptal' => ['gelmeyen', 'İptal Analizi', 'İptal oranı, kanal ve önceden alınma süresine göre iptaller; iptal zamanı tutuluyorsa son dakika (24 saat içinde) iptaller.', 'cancel', '#B91C1C', ['rezervasyon'], null],
            'acik' => ['gelmeyen', 'Sonuçlanmamış Kayıtlar', 'Tarihi geçtiği halde "Bekliyor / Onaylandı"da kalan rezervasyonlar — no-show ve ciro ölçümünü bozan kapatılmamış kayıtlar.', 'pending_actions', '#991B1B', ['rezervasyon'], null],
            'ciro' => ['ciro', 'Rezervasyon Cirosu & Kişi Başı', 'Gelen rezervasyonların adisyon cirosu, kişi başı ve ortalama hesap; rezervasyonsuz salon masalarıyla karşılaştırma; kanala göre ciro.', 'payments', '#059669', ['adisyon_bag'], null],
            'on_siparis' => ['ciro', 'Ön Sipariş', 'Rezervasyonla birlikte önceden sipariş edilen ürünler: ön siparişli rezervasyon, adet, tutar ve en çok ön sipariş edilen ürünler.', 'restaurant_menu', '#047857', ['on_siparis'], null],
            'isi' => ['doluluk', 'Gün × Saat Isı Haritası', 'Haftanın günü × saat: ortalama rezervasyonlu kişi ve rezervasyon sayısı; en yoğun ve boş kalan dilimler.', 'grid_on', '#D97706', ['rezervasyon'], null],
            'doluluk' => ['doluluk', 'Günlük Doluluk', 'Gün gün rezervasyonlu ve gelen kişinin masa kapasitesine oranı; boş kalan günler (kampanya fırsatı).', 'event_seat', '#B45309', ['rezervasyon', 'kapasite'], null],
            'masa' => ['doluluk', 'Masa Kullanımı', 'Masa başına rezervasyon, kişi ve kapasite uyumu; büyük masaya küçük grup (verimsiz atama) ve kapasite aşımı.', 'table_restaurant', '#92400E', ['masa_atama'], null],
            'grup' => ['etkinlik', 'Grup & Etkinlik Rezervasyonları', 'Grup eşiği ve üstü kişilik rezervasyonlar: sayı, kişi, gelme, ciro ve kişi başı; depozito alınmış mı.', 'celebration', '#DB2777', ['rezervasyon'], null],
            'pipeline' => ['etkinlik', 'Etkinlik Takvimi & Pipeline', 'Önümüzdeki 60 gün: grup, özel gün ve depozitolu rezervasyonlar; onay ve kapora durumu, ön sipariş — satış takibi.', 'view_timeline', '#BE185D', ['rezervasyon'], null],
            'ozel_gun' => ['etkinlik', 'Özel Gün & İstekler', 'Doğum günü, yıldönümü, alerji, VIP, bebek sandalyesi gibi etiket ve notların dağılımı; özel gün rezervasyonlarının gelme oranı ve cirosu.', 'cake', '#9D174D', ['etiket'], null],
            'depozito' => ['depozito', 'Depozito Tahsilat Durumu', 'Kapora tutarı, alınan, kalan; tahsil edilen, iade edilen, kullanılan ve yaklaşan etkinliklerin kapora durumu.', 'account_balance_wallet', '#0891B2', ['depozito'], null],
            'depozito_risk' => ['depozito', 'Yanan & Riskli Depozito', 'Misafiri gelmediği halde kaporası açık kalanlar (yanma adayı), tarihi geçip tahsil edilmeyen ya da kapatılmayan kaporalar.', 'report', '#0E7490', ['depozito'], null],
            'tekrar' => ['musteri', 'Tekrar Gelen Misafirler', 'Telefon numarasına göre son 1 yılda birden fazla rezervasyon yapanlar: kaç kez, kaç kez geldi / gelmedi, son ziyaret, toplam ciro.', 'loyalty', '#4F46E5', ['telefon'], null],
            'crm_not' => ['musteri', 'Alerji, Özel Gün & Notlar', 'Dönemdeki ve önümüzdeki 14 günün rezervasyonlarında alerji, özel gün, istek ve müşteri kartı notları — servis hazırlığı listesi.', 'sticky_note_2', '#4338CA', ['rezervasyon'], null],
            'bekleme' => ['karsilama', 'Bekleme Listesi (Walk-in)', 'Kapıdan gelen ve sıraya alınan misafirler: tahmini ve gerçek bekleme süresi, oturtulma ve vazgeçme oranı, saatlere göre yoğunluk.', 'hourglass_top', '#0D9488', ['bekleme'], null],
        ];
    }
    function _rzrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron ve rezervasyon sorumlusu için hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#A855F7'],
            'hacim' => ['Hacim & Kanal', 'trending_up', 'Rezervasyon ve kişi trendi, kanallar, önceden rezervasyon süresi ve masa büyüklüğü.', '#2563EB'],
            'gelmeyen' => ['No-show & İptal', 'event_busy', 'Gelmeyenler, iptaller ve sonucu girilmemiş rezervasyonlar.', '#DC2626'],
            'ciro' => ['Ciro', 'payments', 'Rezervasyon cirosu, kişi başı ve ön sipariş.', '#059669'],
            'doluluk' => ['Doluluk & Masa', 'grid_on', 'Gün × saat ısı haritası, günlük doluluk ve masa kullanımı.', '#D97706'],
            'etkinlik' => ['Grup & Etkinlik', 'celebration', 'Grup rezervasyonları, etkinlik pipeline\'ı ve özel günler.', '#DB2777'],
            'depozito' => ['Depozito', 'account_balance_wallet', 'Kapora tahsilatı, iade ve yanan depozitolar.', '#0891B2'],
            'musteri' => ['Misafir & CRM', 'loyalty', 'Tekrar gelen misafirler, alerji ve özel gün notları.', '#4F46E5'],
            'karsilama' => ['Karşılama', 'hourglass_top', 'Bekleme listesi: kapıdan gelen misafirin bekleme süresi ve oturtulma oranı.', '#0D9488'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı rezervasyon özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_rzrSayfaListe')) {
    function _rzrSayfaListe($ad, $not, array $l, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa($ad, $not, [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Telefon', 'm', 15], ['Kişi', 's', 6, 'top'], ['Kanal', 'm', 16], ['Masa', 'm', 9], ['Durum', 'm', 15], ['Ciro', 'tl', 11, 'top'], ['Not / İstek', 'm', 32]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], _rzrMaske($x['tel']), $x['kisi'], _rzrKaynakAd($x['kaynak']), $x['masa'] ?: '—', _rzrDurumAd($x['durum']) . ($x['acik'] ? ' (sonuç girilmedi)' : ''), $x['ciro'] ?: null,
                trim(implode(', ', $x['etiket']) . ($x['etiket'] && $x['not'] ? ' · ' : '') . $x['not'])], $l), $bos);
    }
    function _rzrGunTablo(array $l, $bas, $bit, $kap)
    {
        $g = []; foreach ($l as $x) $g[$x['tarih']][] = $x;
        $o = [];
        foreach (_rzrGunler($bas, $bit) as $t) { $s = _rzrOzet($g[$t] ?? []); $o[] = [$t, _rzrGunAd(date('N', strtotime($t))), $s['n'], $s['aktif_kisi'], $s['geldi'], $s['gelmedi'], $s['iptal'], $s['noshow'], $s['gelen_kisi'], $kap > 0 ? round($s['aktif_kisi'] / $kap * 100, 1) : null, $s['ciro'] ?: null]; }
        return $o;
    }
    function _rzrSayfaGun(array $sat, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa('Gün Gün', 'Kişi = iptal hariç rezervasyonlu kişi. No-show % = gelmedi ÷ (geldi + gelmedi). Doluluk = rezervasyonlu kişi ÷ masa kapasitesi.',
            [['Tarih', 't', 10], ['Gün', 'm', 10], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['Geldi', 's', 7, 'top'], ['Gelmedi', 's', 8, 'top esik:1'], ['İptal', 's', 7, 'top'], ['No-show %', 'y', 9, 'esik:15'], ['Gelen Kişi', 's', 9, 'top'], ['Doluluk %', 'y', 9], ['Ciro', 'tl', 12, 'top']], $sat, $bos);
    }
    function _rzrKanalSat(array $l)
    {
        $g = []; foreach ($l as $x) $g[$x['kaynak']][] = $x; $n = max(1, count($l)); $o = [];
        foreach ($g as $k => $xs) {
            $s = _rzrOzet($xs); $ld = array_filter(array_column($xs, 'lead'), fn ($v) => $v !== null);
            $o[] = [_rzrKaynakAd($k), $s['n'], $s['aktif_kisi'], round($s['n'] / $n * 100, 1), $s['geldi'], $s['gelmedi'], $s['iptal'], $s['noshow'], $s['ort_kisi'], $ld ? round(array_sum($ld) / count($ld) / 24, 1) : null, $s['ciro'] ?: null, $s['kisi_basi']];
        }
        usort($o, fn ($a, $b) => $b[1] <=> $a[1]);
        return $o;
    }
    function _rzrSayfaKanal(array $sat, $bos)
    {
        return _srSayfa('Kanallar', 'Kanal = rezervasyonun geldiği yer; kişi iptal hariç. AI Santral: telefonu yapay zekânın açtığı aramalardan gelen rezervasyonlar. Önceden = rezervasyon kaç gün önce alındı (ortalama).',
            [['Kanal', 'm', 20], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['Pay %', 'y', 8], ['Geldi', 's', 7, 'top'], ['Gelmedi', 's', 8, 'top'], ['İptal', 's', 7, 'top'], ['No-show %', 'y', 9, 'esik:15'], ['Ort. Kişi', 's', 8], ['Önceden (gün)', 's', 10], ['Ciro', 'tl', 12, 'top'], ['Kişi Başı', 'tl', 10]], $sat, $bos);
    }
    function _rzrSayfaDepozito($ad, $not, array $dl, $bos)
    {
        return _srSayfa($ad, $not, [['Etkinlik Tarihi', 't', 11], ['Misafir', 'm', 20], ['Telefon', 'm', 15], ['Kişi', 's', 6, 'top'], ['Tutar', 'tl', 11, 'top'], ['Alınan', 'tl', 11, 'top'], ['Kalan', 'tl', 11, 'top'], ['Rezervasyon', 'm', 18], ['Durum / Sonuç', 'm', 28], ['Not', 'm', 22]],
            array_map(fn ($x) => [$x['tarih'], $x['ad'], _rzrMaske($x['tel']), $x['kisi'], $x['tutar'], $x['alinan'], $x['kalan'], $x['rez'], $x['sonuc'], $x['not']], $dl), $bos);
    }
    // Telefon numarasına göre misafir geçmişi (son 365 gün, dönem sonuna kadar)
    function _rzrMisafir($sid, $bit)
    {
        $l = _rzrRez($sid, date('Y-m-d', strtotime($bit . ' -365 day')), $bit); $g = [];
        foreach ($l as $x) if ($x['tel']) $g[$x['tel']][] = $x;
        $o = [];
        foreach ($g as $tl => $xs) {
            $s = _rzrOzet($xs); $gel = array_values(array_filter($xs, fn ($x) => $x['durum'] === 'geldi'));
            $o[$tl] = ['tel' => $tl, 'ad' => end($xs)['ad'], 'n' => $s['n'], 'geldi' => $s['geldi'], 'gelmedi' => $s['gelmedi'], 'iptal' => $s['iptal'], 'ilk' => $xs[0]['tarih'], 'son' => end($xs)['tarih'],
                'son_gelis' => $gel ? end($gel)['tarih'] : null, 'ort_kisi' => $s['ort_kisi'], 'ciro' => $s['ciro'], 'ozel' => array_values(array_unique(array_merge(...array_map(fn ($x) => $x['ozel'], $xs))))];
        }
        uasort($o, fn ($a, $b) => [$b['n'], $b['ciro']] <=> [$a['n'], $a['ciro']]);
        return $o;
    }
    function _rzrMusteriNot($sid, array $ids)
    {
        $ids = array_values(array_filter(array_unique($ids))); if (!$ids || !_srTablo('musteriler')) return [];
        $o = []; $dog = _srKolon('musteriler', 'dogum_tarihi');
        try { foreach (array_chunk($ids, 500) as $ch) foreach (DB::table('musteriler')->whereIn('id', $ch)->get(array_merge(['id', 'notlar'], $dog ? ['dogum_tarihi'] : [])) as $m) $o[(int) $m->id] = ['not' => (string) $m->notlar, 'dogum' => $dog && $m->dogum_tarihi ? substr((string) $m->dogum_tarihi, 5, 5) : null]; } catch (\Throwable $e) {}
        return $o;
    }
    function _rzrSayfaCrm($ad, $not, array $l, array $mn, $bos)
    {
        $sat = [];
        foreach ($l as $x) {
            $k = $mn[$x['musteri_id']] ?? null; $dg = $k && $k['dogum'] && $k['dogum'] === substr($x['tarih'], 5, 5) ? 'Bugün doğum günü' : '';
            if (!$x['etiket'] && !$x['not'] && !($k['not'] ?? '') && !$dg) continue;
            $sat[] = [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], $x['masa'] ?: '—', implode(', ', array_map('_rzrOzelAd', $x['ozel'])) ?: '—', implode(', ', $x['etiket']), $x['not'], trim(($k['not'] ?? '') . ($dg ? ' · ' . $dg : ''), ' ·'), _rzrDurumAd($x['durum'])];
        }
        return [_srSayfa($ad, $not, [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6], ['Masa', 'm', 9], ['Tür', 'm', 18], ['Etiketler', 'm', 22], ['Rezervasyon Notu', 'm', 30], ['Müşteri Kartı Notu', 'm', 28], ['Durum', 'm', 14]], $sat, $bos), count($sat)];
    }
}

// ---------- Raporlar ----------
if (!function_exists('_rzrRapor')) {
    function _rzrRapor($anahtar, $sid, array $dn)
    {
        _rzrEnsure();
        $d = _rzrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit']; $ay = _rzrAyar($sid); $kap = _rzrKapasite($sid);
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $l = _rzrRez($sid, $bas, $bit); $s = _rzrOzet($l);
        $bosRez = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : _rzrBos('rez', $dn);
        $bosSonuc = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$d['sonuc'] ? _rzrBos('sonuc_yok', $dn) : null);
        $bosCiro = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$d['adisyon_bag'] ? _rzrBos('ciro_yok', $dn) : null);
        $dol = fn ($kisi, $gun) => $kap > 0 && $gun > 0 ? round($kisi / ($kap * $gun) * 100, 1) : null;
        $blokKanal = fn ($l, $renk) => _srBlok('Kanallara göre rezervasyon', 'pie', $renk, [['Kanal', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], _rzrKanalSat($l)));
        $blokDurum = fn ($s, $renk) => _srBlok('Durum dağılımı', 'pie', $renk, [['Durum', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_values(array_filter([['Geldi', $s['geldi'], ''], ['Gelmedi', $s['gelmedi'], ''], ['İptal', $s['iptal'], ''], ['Onaylandı (yaklaşan)', $s['onaylandi'] - min($s['onaylandi'], $s['acik']), ''], ['Onay bekliyor', $s['bekliyor'], ''], ['Sonucu girilmemiş', $s['acik'], '']], fn ($x) => $x[1] > 0)));
        $grupMu = fn ($x) => $x['kisi'] >= $ay['grup'];

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $yarin = date('Y-m-d', strtotime($bit . ' +2 day')); $gelecek = array_values(array_filter(_rzrRez($sid, date('Y-m-d', strtotime($bit . ' +1 day')), $yarin), fn ($x) => $x['durum'] !== 'iptal'));
                $mn = _rzrMusteriNot($sid, array_column($gelecek, 'musteri_id')); $bk = _rzrBekleme($sid, $bas, $bit); $dp = _rzrDepozito($sid, $bas, $bit);
                $saat = []; foreach ($l as $x) if ($x['durum'] !== 'iptal') { $saat[$x['saat']] = ($saat[$x['saat']] ?? 0) + $x['kisi']; } ksort($saat);
                [$crmSayfa, $crmN] = _rzrSayfaCrm('Bugün & Yarın: Notlar', 'Gelecek 2 günün rezervasyonlarında alerji, özel gün, istek ve müşteri kartı notları — servis ve mutfak hazırlığı.', $gelecek, $mn, 'Bugün ve yarın için notu / özel isteği olan rezervasyon yok.');
                return [
                    $pano([_srKpi('Rezervasyon', $s['n'], 'sayi', 'A855F7'), _srKpi('Kişi', $s['aktif_kisi'], 'sayi', '9333EA'), _srKpi('Geldi', $s['geldi'], 'sayi', '059669'), _srKpi('Gelmedi', $s['gelmedi'], 'sayi', 'DC2626'),
                        _srKpi('İptal', $s['iptal'], 'sayi', 'B91C1C'), $kpiY('No-show oranı', $s['noshow'], 'DC2626'), _srKpi('Rezervasyon cirosu', $s['ciro'], 'tl', '059669'), $kpiTl('Kişi başı', $s['kisi_basi'], '047857'),
                        $kpiY('Doluluk', $dol($s['aktif_kisi'], 1), 'D97706'), _srKpi('Sonucu girilmemiş', $s['acik'], 'sayi', 'C2410C'), _srKpi('Bekleme listesi', count($bk), 'sayi', '0D9488'), _srKpi('Bugün & yarın gelecek', count($gelecek), 'sayi', '4F46E5')],
                        [_srBlok('Saatlere göre rezervasyonlu kişi', 'col', 'A855F7', [['Saat', 'm'], ['Kişi', 's'], ['', 'm']], array_map(fn ($h, $v) => [$h, $v, ''], array_keys($saat), array_values($saat))), $blokKanal($l, '2563EB'), $blokDurum($s, '7E22CE')], !$l ? $bosRez : null),
                    _rzrSayfaListe('Rezervasyonlar', 'Seçili günün tüm rezervasyonları.', $l, $bosRez),
                    _rzrSayfaListe('Gelmeyen & İptal', 'Gelmeyen (no-show) ve iptal edilen rezervasyonlar.', array_values(array_filter($l, fn ($x) => in_array($x['durum'], ['gelmedi', 'iptal'], true) || $x['acik'])), 'Gelmeyen, iptal ya da sonucu girilmemiş rezervasyon yok.'),
                    $crmSayfa,
                    _srSayfa('Bekleme Listesi', 'Karşılama ekranındaki walk-in sırası.', [['Saat', 'm', 7], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top'], ['Tahmini (dk)', 's', 10], ['Gerçek (dk)', 's', 10, 'esik:30'], ['Durum', 'm', 16]], array_map(fn ($x) => [$x['saat'], $x['ad'], $x['kisi'], $x['tahmini'], $x['gercek'], _rzrBeklemeDurumAd($x['durum'])], $bk), !$d['bekleme'] ? _rzrBos('bekleme_yok', $dn) : _rzrBos('bekleme', $dn)),
                    _rzrSayfaDepozito('Depozito (Bu Gün)', 'Etkinlik tarihi seçili güne düşen kaporalar.', $dp, !$d['depozito'] ? _rzrBos('depozito_yok', $dn) : _rzrBos('depozito', $dn)),
                ];
            case 'haftalik':
            case 'aylik':
                $lo = _rzrRez($sid, $dn['obas'], $dn['obit']); $so = _rzrOzet($lo); $gt = _rzrGunTablo($l, $bas, $bit, $kap); $grup = array_values(array_filter($l, $grupMu));
                $kpi = [_srKpi('Rezervasyon', $s['n'], 'sayi', 'A855F7'), $kpiDeg('Önceki döneme göre', _srYuzde($s['n'], $so['n']), '9333EA'), _srKpi('Kişi', $s['aktif_kisi'], 'sayi', '7E22CE'),
                    _srKpi('Gelen kişi', $s['gelen_kisi'], 'sayi', '059669'), $kpiY('No-show oranı', $s['noshow'], 'DC2626'), $kpiY('Önceki dönem no-show', $so['noshow'], '991B1B'), $kpiY('İptal oranı', $s['iptal_or'], 'B91C1C'),
                    _srKpi('Rezervasyon cirosu', $s['ciro'], 'tl', '059669'), $kpiDeg('Ciro, önceki döneme göre', _srYuzde($s['ciro'], $so['ciro']), '047857'), $kpiTl('Kişi başı', $s['kisi_basi'], '0F766E'),
                    $kpiY('Ortalama doluluk', $dol($s['aktif_kisi'], $dn['gun']), 'D97706'), _srKpi('Grup / etkinlik', count($grup), 'sayi', 'DB2777'), _srKpi('Sonucu girilmemiş', $s['acik'], 'sayi', 'C2410C')];
                $bloklar = [_srBlok('Günlere göre rezervasyonlu kişi', 'col', 'A855F7', [['Gün', 'm'], ['Kişi', 's'], ['Gelen', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[3], $x[8]], $gt)), $blokKanal($l, '2563EB'), $blokDurum($s, '7E22CE')];
                $sayfalar = [_rzrSayfaKanal(_rzrKanalSat($l), $bosRez), _rzrSayfaListe('Gelmeyenler', 'Gelmeyen (no-show) rezervasyonlar.', array_values(array_filter($l, fn ($x) => $x['durum'] === 'gelmedi')), $bosSonuc ?? _rzrBos('noshow', $dn)),
                    _rzrSayfaListe('Grup & Etkinlik', $ay['grup'] . ' kişi ve üstü rezervasyonlar.', $grup, _rzrBos('grup', $dn))];
                if ($anahtar === 'aylik') {
                    $dp = _rzrDepozito($sid, $bas, $bit); $ms = array_values(array_filter(_rzrMisafir($sid, $bit), fn ($m) => $m['n'] >= 2 && $m['son'] >= $bas));
                    $kpi = array_merge($kpi, [_srKpi('Depozito alınan', round(array_sum(array_column($dp, 'alinan')), 2), 'tl', '0891B2'), _srKpi('Tekrar gelen misafir', count($ms), 'sayi', '4F46E5'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _rzrSayfaDepozito('Depozito', 'Etkinlik tarihi bu aya düşen kaporalar.', $dp, !$d['depozito'] ? _rzrBos('depozito_yok', $dn) : _rzrBos('depozito', $dn));
                    $sayfalar[] = _srSayfa('Tekrar Gelen Misafirler', 'Son 1 yılda en az 2 rezervasyonu olan ve bu ay rezervasyon yapan misafirler.', [['Misafir', 'm', 20], ['Telefon', 'm', 15], ['Rezervasyon', 's', 10, 'top bar'], ['Geldi', 's', 7], ['Gelmedi', 's', 8, 'esik:2'], ['Son Geliş', 't', 11], ['Ciro (1 yıl)', 'tl', 12, 'top']],
                        array_map(fn ($m) => [$m['ad'], _rzrMaske($m['tel']), $m['n'], $m['geldi'], $m['gelmedi'], $m['son_gelis'], $m['ciro'] ?: null], array_slice($ms, 0, 40)), !$d['telefon'] ? _rzrBos('tel_yok', $dn) : _rzrBos('tekrar', $dn));
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$l ? $bosRez : null), _rzrSayfaGun($gt, $bosRez)], $sayfalar);

            // ---------------- HACİM & KANAL ----------------
            case 'hacim':
                $lo = _rzrRez($sid, $dn['obas'], $dn['obit']); $so = _rzrOzet($lo); $gt = _rzrGunTablo($l, $bas, $bit, $kap);
                $gn = []; $gs = []; foreach (_rzrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gs[$n] = ($gs[$n] ?? 0) + 1; } foreach ($l as $x) if ($x['durum'] !== 'iptal') { $gn[$x['gun']]['n'] = ($gn[$x['gun']]['n'] ?? 0) + 1; $gn[$x['gun']]['k'] = ($gn[$x['gun']]['k'] ?? 0) + $x['kisi']; }
                $gsat = []; for ($n = 1; $n <= 7; $n++) if (!empty($gs[$n])) $gsat[] = [_rzrGunAd($n), $gn[$n]['n'] ?? 0, $gn[$n]['k'] ?? 0, round(($gn[$n]['k'] ?? 0) / $gs[$n], 1)];
                return [
                    $pano([_srKpi('Rezervasyon', $s['n'], 'sayi', '2563EB'), _srKpi('Önceki dönem', $so['n'], 'sayi', '1E40AF'), $kpiDeg('Değişim', _srYuzde($s['n'], $so['n']), '1D4ED8'), _srKpi('Kişi', $s['aktif_kisi'], 'sayi', '3B82F6'),
                        $kpiDeg('Kişi, önceki döneme göre', _srYuzde($s['aktif_kisi'], $so['aktif_kisi']), '1E3A8A'), _srKpi('Ortalama kişi', $s['ort_kisi'] ?? 0, 'sayi', '60A5FA'), _srKpi('Günlük ortalama', round($s['n'] / max(1, $dn['gun']), 1), 'sayi', '2563EB')],
                        [_srBlok('Günlere göre rezervasyon', 'col', '2563EB', [['Gün', 'm'], ['Rezervasyon', 's'], ['Kişi', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[3]], $gt)),
                         _srBlok('Haftanın günlerine göre ortalama kişi', 'col', '1D4ED8', [['Gün', 'm'], ['Ort. kişi', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], $gsat)), $blokDurum($s, '7E22CE')], !$l ? $bosRez : null),
                    _rzrSayfaGun($gt, $bosRez),
                    _srSayfa('Haftanın Günleri', 'İptal hariç; ortalama = toplam kişi ÷ dönemdeki o günün sayısı.', [['Gün', 'm', 12], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['Ort. Kişi / Gün', 's', 12]], $gsat, $bosRez),
                    _rzrSayfaListe('Tüm Rezervasyonlar', '', $l, $bosRez),
                ];
            case 'kaynak':
                $ks = _rzrKanalSat($l); $en = $ks[0][0] ?? '—';
                $san = array_values(array_filter($l, fn ($x) => $x['kaynak'] === 'santral'));
                return [
                    $pano([_srKpi('Rezervasyon', $s['n'], 'sayi', '1D4ED8'), _srKpi('Kanal sayısı', count($ks), 'sayi', '2563EB'), _srKpi('En çok gelen kanal', $en, 'metin', '1E40AF'), _srKpi('AI Santral\'dan', count($san), 'sayi', '7C3AED'),
                        $kpiY('Online pay (web + QR + uygulama)', $s['n'] ? round(count(array_filter($l, fn ($x) => in_array($x['kaynak'], ['web', 'qr', 'app'], true))) / $s['n'] * 100, 1) : null, '0EA5E9')],
                        [_srBlok('Kanallara göre rezervasyon', 'pie', '1D4ED8', [['Kanal', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $ks)),
                         _srBlok('Kanallara göre no-show (%)', 'bar', 'DC2626', [['Kanal', 'm'], ['No-show %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[7], ''], array_filter($ks, fn ($x) => $x[7] !== null))),
                         _srBlok('Kanallara göre ciro', 'bar', '059669', [['Kanal', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[10], ''], array_filter($ks, fn ($x) => $x[10])))], !$l ? $bosRez : null),
                    _rzrSayfaKanal($ks, $bosRez),
                    _rzrSayfaListe('AI Santral Rezervasyonları', 'Telefonu yapay zekânın açtığı aramalardan oluşan rezervasyonlar (not "AI Santral" ile başlar).', $san, 'Seçili dönemde AI Santral\'dan gelen rezervasyon yok.'),
                ];
            case 'lead':
                $g = []; foreach ($l as $x) $g[_rzrLeadGrup($x['lead'])][] = $x; $sira = ['3 saatten az', '3–24 saat', '1–3 gün', '4–7 gün', '8–30 gün', '30 gün +', 'Bilinmiyor'];
                $sat = []; foreach ($sira as $k) if (!empty($g[$k])) { $o = _rzrOzet($g[$k]); $sat[] = [$k, $o['n'], round($o['n'] / max(1, $s['n']) * 100, 1), $o['ort_kisi'], $o['geldi'], $o['gelmedi'], $o['iptal'], $o['noshow'], $o['iptal_or']]; }
                $ld = array_filter(array_column($l, 'lead'), fn ($v) => $v !== null); sort($ld); $med = $ld ? $ld[(int) floor(count($ld) / 2)] : null;
                $kn = []; foreach ($l as $x) if ($x['lead'] !== null) $kn[$x['kaynak']][] = $x['lead'];
                $ksat = array_map(fn ($k, $v) => [_rzrKaynakAd($k), count($v), round(array_sum($v) / count($v) / 24, 1), round(min($v) / 24, 1), round(max($v) / 24, 1)], array_keys($kn), array_values($kn));
                return [
                    $pano([_srKpi('Ortalama önceden (gün)', $ld ? round(array_sum($ld) / count($ld) / 24, 1) : 'Veri yok', $ld ? 'sayi' : 'metin', '1E40AF'), _srKpi('Ortanca (saat)', $med ?? 'Veri yok', $med === null ? 'metin' : 'sayi', '1D4ED8'),
                        $kpiY('Aynı gün (24 saatten az)', $ld ? round(count(array_filter($ld, fn ($v) => $v < 24)) / count($ld) * 100, 1) : null, '2563EB'), $kpiY('1 haftadan önce', $ld ? round(count(array_filter($ld, fn ($v) => $v >= 168)) / count($ld) * 100, 1) : null, '3B82F6')],
                        [_srBlok('Önceden alınma süresine göre rezervasyon', 'col', '1E40AF', [['Süre', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)),
                         _srBlok('Süreye göre no-show (%)', 'col', 'DC2626', [['Süre', 'm'], ['No-show %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[7], ''], array_filter($sat, fn ($x) => $x[7] !== null)))], !$l ? $bosRez : null),
                    _srSayfa('Süre Grupları', 'Önceden = rezervasyonun alındığı an ile rezervasyon saati arası. Kapıdan (walk-in) kayıtlar genelde "3 saatten az" düşer.', [['Önceden', 'm', 14], ['Rezervasyon', 's', 10, 'top bar'], ['Pay %', 'y', 8], ['Ort. Kişi', 's', 8], ['Geldi', 's', 7], ['Gelmedi', 's', 8], ['İptal', 's', 7], ['No-show %', 'y', 9, 'esik:15'], ['İptal %', 'y', 8]], $sat, $bosRez),
                    _srSayfa('Kanallara Göre', 'Kanal başına ortalama, en kısa ve en uzun önceden alınma süresi (gün).', [['Kanal', 'm', 20], ['Rezervasyon', 's', 10, 'top'], ['Ortalama (gün)', 's', 11, 'bar'], ['En Kısa (gün)', 's', 10], ['En Uzun (gün)', 's', 10]], $ksat, $bosRez),
                ];
            case 'boyut':
                $g = []; foreach ($l as $x) $g[_rzrBoyGrup($x['kisi'])][] = $x; $sira = ['1–2 kişi', '3–4 kişi', '5–7 kişi', '8–12 kişi', '13 kişi +'];
                $sat = []; foreach ($sira as $k) if (!empty($g[$k])) { $o = _rzrOzet($g[$k]); $sat[] = [$k, $o['n'], $o['kisi'], round($o['n'] / max(1, $s['n']) * 100, 1), $o['noshow'], $o['iptal_or'], $o['ciro'] ?: null, $o['kisi_basi'], $o['ciro_n'] ? round($o['ciro'] / $o['ciro_n'], 2) : null]; }
                return [
                    $pano([_srKpi('Ortalama kişi', $s['ort_kisi'] ?? 0, 'sayi', '3B82F6'), _srKpi('2 kişi ve altı', count(array_filter($l, fn ($x) => $x['kisi'] <= 2)), 'sayi', '2563EB'), _srKpi('Grup (' . $ay['grup'] . '+)', count(array_filter($l, $grupMu)), 'sayi', 'DB2777'), _srKpi('En kalabalık', $l ? max(array_column($l, 'kisi')) . ' kişi' : '—', 'metin', '1E40AF')],
                        [_srBlok('Kişi sayısına göre rezervasyon', 'col', '3B82F6', [['Grup', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)),
                         _srBlok('Kişi sayısına göre kişi başı ciro', 'col', '059669', [['Grup', 'm'], ['Kişi başı', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[7], ''], array_filter($sat, fn ($x) => $x[7] !== null)))], !$l ? $bosRez : null),
                    _srSayfa('Masa Büyüklüğü', 'Kişi başı ve ortalama hesap yalnız adisyonu bağlı (oturtulmuş) gelen rezervasyonlardan.', [['Kişi Sayısı', 'm', 12], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['Pay %', 'y', 8], ['No-show %', 'y', 9, 'esik:15'], ['İptal %', 'y', 8], ['Ciro', 'tl', 12, 'top'], ['Kişi Başı', 'tl', 10], ['Ort. Hesap', 'tl', 11]], $sat, $bosRez),
                ];

            // ---------------- NO-SHOW & İPTAL ----------------
            case 'noshow':
                $son = array_values(array_filter($l, fn ($x) => in_array($x['durum'], ['geldi', 'gelmedi'], true))); $gm = array_values(array_filter($son, fn ($x) => $x['durum'] === 'gelmedi'));
                $kir = function ($f) use ($son) { $g = []; foreach ($son as $x) { $k = $f($x); $g[$k]['n'] = ($g[$k]['n'] ?? 0) + 1; $g[$k]['g'] = ($g[$k]['g'] ?? 0) + ($x['durum'] === 'gelmedi' ? 1 : 0); $g[$k]['k'] = ($g[$k]['k'] ?? 0) + ($x['durum'] === 'gelmedi' ? $x['kisi'] : 0); } return $g; };
                $gg = $kir(fn ($x) => $x['gun']); ksort($gg); $sg = $kir(fn ($x) => sprintf('%02d:00', $x['h'])); ksort($sg); $kg = $kir(fn ($x) => _rzrKaynakAd($x['kaynak']));
                $tab = fn ($g, $etiket) => array_map(fn ($k, $v) => [$etiket ? $etiket($k) : $k, $v['n'], $v['g'], $v['k'], round($v['g'] / $v['n'] * 100, 1)], array_keys($g), array_values($g));
                $tekrar = array_values(array_filter(_rzrMisafir($sid, $bit), fn ($m) => $m['gelmedi'] >= 2)); $kayip = $s['kisi_basi'] ? round(array_sum(array_column($gm, 'kisi')) * $s['kisi_basi'], 2) : null;
                $kol = fn ($ad) => [[$ad, 'm', 14], ['Sonuçlanan', 's', 10, 'top'], ['Gelmedi', 's', 8, 'top bar'], ['Gelmeyen Kişi', 's', 11, 'top'], ['No-show %', 'y', 9, 'esik:15']];
                return [
                    $pano([$kpiY('No-show oranı', $s['noshow'], 'DC2626'), _srKpi('Gelmeyen rezervasyon', $s['gelmedi'], 'sayi', 'B91C1C'), _srKpi('Gelmeyen kişi', array_sum(array_column($gm, 'kisi')), 'sayi', '991B1B'),
                        $kpiTl('Tahmini kayıp ciro', $kayip, '7F1D1D'), _srKpi('Sonucu girilmemiş', $s['acik'], 'sayi', 'C2410C'), _srKpi('2+ kez gelmeyen misafir', count($tekrar), 'sayi', 'DC2626')],
                        [_srBlok('Haftanın günlerine göre no-show (%)', 'col', 'DC2626', [['Gün', 'm'], ['No-show %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[4], ''], $tab($gg, '_rzrGunAd'))),
                         _srBlok('Saatlere göre gelmeyen', 'col', 'B91C1C', [['Saat', 'm'], ['Gelmedi', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], $tab($sg, null))),
                         _srBlok('Kanallara göre no-show (%)', 'bar', '991B1B', [['Kanal', 'm'], ['No-show %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[4], ''], $tab($kg, null)))], $bosSonuc ?? (!$son ? _rzrBos('sonuc_yok', $dn) : null)),
                    _rzrSayfaListe('Gelmeyenler', 'Tahmini kayıp = gelmeyen kişi × dönemin kişi başı cirosu.', $gm, $bosSonuc ?? _rzrBos('noshow', $dn)),
                    _srSayfa('Güne Göre', '', $kol('Gün'), $tab($gg, '_rzrGunAd'), $bosSonuc ?? _rzrBos('noshow', $dn)),
                    _srSayfa('Saate Göre', '', $kol('Saat'), $tab($sg, null), $bosSonuc ?? _rzrBos('noshow', $dn)),
                    _srSayfa('Kanala Göre', '', $kol('Kanal'), $tab($kg, null), $bosSonuc ?? _rzrBos('noshow', $dn)),
                    _srSayfa('Tekrar Gelmeyenler', 'Son 1 yılda en az 2 kez gelmeyen misafirler (telefona göre) — depozito / teyit araması önerilir.', [['Misafir', 'm', 20], ['Telefon', 'm', 15], ['Rezervasyon', 's', 10], ['Geldi', 's', 7], ['Gelmedi', 's', 8, 'top bar esik:2'], ['Son Rezervasyon', 't', 12]],
                        array_map(fn ($m) => [$m['ad'], _rzrMaske($m['tel']), $m['n'], $m['geldi'], $m['gelmedi'], $m['son']], $tekrar), !$d['telefon'] ? _rzrBos('tel_yok', $dn) : 'Son 1 yılda 2 ve üstü kez gelmeyen misafir yok.'),
                ];
            case 'iptal':
                $ip = array_values(array_filter($l, fn ($x) => $x['durum'] === 'iptal'));
                $kg = []; foreach ($l as $x) { $k = _rzrKaynakAd($x['kaynak']); $kg[$k]['n'] = ($kg[$k]['n'] ?? 0) + 1; $kg[$k]['i'] = ($kg[$k]['i'] ?? 0) + ($x['durum'] === 'iptal' ? 1 : 0); }
                $ksat = array_map(fn ($k, $v) => [$k, $v['n'], $v['i'], round($v['i'] / $v['n'] * 100, 1)], array_keys($kg), array_values($kg)); usort($ksat, fn ($a, $b) => $b[2] <=> $a[2]);
                $zam = array_values(array_filter($ip, fn ($x) => $x['durum_at'])); $sonDk = array_values(array_filter($zam, fn ($x) => $x['ts'] - $x['durum_at'] < 86400));
                return [
                    $pano([_srKpi('İptal', $s['iptal'], 'sayi', 'B91C1C'), $kpiY('İptal oranı', $s['iptal_or'], '991B1B'), _srKpi('İptal edilen kişi', array_sum(array_column($ip, 'kisi')), 'sayi', '7F1D1D'),
                        _srKpi('Son dakika (24 saat içinde)', $zam ? count($sonDk) : 'Ölçüm yok', $zam ? 'sayi' : 'metin', 'DC2626'), _srKpi('Grup iptali', count(array_filter($ip, $grupMu)), 'sayi', 'DB2777')],
                        [_srBlok('Kanallara göre iptal', 'bar', 'B91C1C', [['Kanal', 'm'], ['İptal', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], array_filter($ksat, fn ($x) => $x[2] > 0)))],
                        !$l ? $bosRez : (!$ip ? _rzrBos('iptal', $dn) : (!$zam ? _rzrBos('durum_at', $dn) : null))),
                    _srSayfa('Kanala Göre İptal', '', [['Kanal', 'm', 20], ['Rezervasyon', 's', 10, 'top'], ['İptal', 's', 8, 'top bar'], ['İptal %', 'y', 9, 'esik:20']], $ksat, $bosRez),
                    _srSayfa('İptaller', 'İptal zamanı (Faz 2) tutulan kayıtlarda "Kaç saat önce" dolu: rezervasyon saatinden kaç saat önce iptal edildi.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top'], ['Kanal', 'm', 16], ['Önceden Alınma (saat)', 's', 14], ['İptal Zamanı', 'm', 15], ['Kaç Saat Önce', 's', 11, 'bar'], ['Not', 'm', 26]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], _rzrKaynakAd($x['kaynak']), $x['lead'], $x['durum_at'] ? date('d.m.Y H:i', $x['durum_at']) : '—', $x['durum_at'] ? round(($x['ts'] - $x['durum_at']) / 3600, 1) : null, $x['not']], $ip), !$l ? $bosRez : _rzrBos('iptal', $dn)),
                ];
            case 'acik':
                $ac = array_values(array_filter($l, fn ($x) => $x['acik']));
                $kg = []; foreach ($ac as $x) { $k = _rzrKaynakAd($x['kaynak']); $kg[$k] = ($kg[$k] ?? 0) + 1; } arsort($kg);
                $gecmis = array_values(array_filter($l, fn ($x) => $x['tarih'] < date('Y-m-d')));
                return [
                    $pano([_srKpi('Sonucu girilmemiş', count($ac), 'sayi', '991B1B'), $kpiY('Geçmiş rezervasyonlarda payı', $gecmis ? round(count($ac) / count($gecmis) * 100, 1) : null, 'C2410C'), _srKpi('Kişi', array_sum(array_column($ac, 'kisi')), 'sayi', '7F1D1D'),
                        _srKpi('Onay bekliyor kalan', count(array_filter($ac, fn ($x) => $x['durum'] === 'bekliyor')), 'sayi', 'D97706')],
                        [_srBlok('Kanallara göre sonuçlanmamış', 'bar', '991B1B', [['Kanal', 'm'], ['Kayıt', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($kg), array_values($kg)))], !$l ? $bosRez : (!$ac ? _rzrBos('acik', $dn) : 'Bu kayıtlar "Geldi" ya da "Gelmedi" olarak kapatılmadıkça no-show oranı ve rezervasyon cirosu eksik çıkar. Rezervasyon Satış > Rezervasyonlar ekranında ilgili günü açıp durumu güncelleyin.')),
                    _rzrSayfaListe('Sonuçlanmamış Rezervasyonlar', 'Tarihi geçtiği halde "Bekliyor / Onaylandı"da kalanlar.', $ac, !$l ? $bosRez : _rzrBos('acik', $dn)),
                ];

            // ---------------- CİRO ----------------
            case 'ciro':
                $gel = array_values(array_filter($l, fn ($x) => $x['durum'] === 'geldi')); $bag = array_values(array_filter($gel, fn ($x) => $x['ciro'] > 0));
                $dis = _rzrSalonDis($sid, $bas, $bit, array_column($bag, 'adisyon_id')); $disKb = $dis['kisi'] > 0 ? round($dis['ciro'] / $dis['kisi'], 2) : null; $disOrt = $dis['n'] ? round($dis['ciro'] / $dis['n'], 2) : null;
                $ort = $bag ? round($s['ciro'] / count($bag), 2) : null; $ks = _rzrKanalSat($l);
                $gun = []; foreach ($bag as $x) $gun[$x['tarih']] = ($gun[$x['tarih']] ?? 0) + $x['ciro']; ksort($gun);
                return [
                    $pano([_srKpi('Rezervasyon cirosu', $s['ciro'], 'tl', '059669'), $kpiTl('Kişi başı', $s['kisi_basi'], '047857'), $kpiTl('Ortalama hesap', $ort, '0F766E'), $kpiTl('Rezervasyonsuz salon kişi başı', $disKb, '64748B'),
                        $kpiDeg('Rezervasyonlu fark (kişi başı)', $s['kisi_basi'] && $disKb ? _srYuzde($s['kisi_basi'], $disKb) : null, '065F46'), $kpiY('Adisyonu bağlı gelen', $gel ? round(count($bag) / count($gel) * 100, 1) : null, 'D97706'),
                        $kpiTl('Ön sipariş tutarı', round(array_sum(array_column($l, 'on_tl')), 2), '115E59')],
                        [_srBlok('Günlere göre rezervasyon cirosu', 'col', '059669', [['Gün', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($t, $v) => [date('d.m', strtotime($t)), round($v, 2), ''], array_keys($gun), array_values($gun))),
                         _srBlok('Kanallara göre ciro', 'pie', '047857', [['Kanal', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[10], ''], array_filter($ks, fn ($x) => $x[10])))], $bosCiro ?? (!$bag ? _rzrBos('ciro_yok', $dn) : null)),
                    _srSayfa('Rezervasyonlu ve Rezervasyonsuz', 'Rezervasyonsuz = aynı dönemde masaya açılmış, rezervasyona bağlı olmayan ödenmiş salon adisyonları. Kişi başı misafir sayısı girilen adisyonlardan.', [['Grup', 'm', 22], ['Adisyon', 's', 9, 'top'], ['Ciro', 'tl', 14, 'top bar'], ['Ort. Hesap', 'tl', 12], ['Kişi Başı', 'tl', 11]],
                        [['Rezervasyonlu (gelen)', count($bag), $s['ciro'], $ort, $s['kisi_basi']], ['Rezervasyonsuz salon', $dis['n'], $dis['ciro'], $disOrt, $disKb]], $bosCiro),
                    _rzrSayfaKanal($ks, $bosRez),
                    _srSayfa('Gelen Rezervasyonlar', 'Ciro boşsa adisyon bağlanmamış (rezervasyon "Oturt" ile açılmamış).', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top'], ['Kanal', 'm', 16], ['Masa', 'm', 9], ['Ön Sipariş', 'tl', 11, 'top'], ['Ciro', 'tl', 12, 'top bar'], ['Kişi Başı', 'tl', 10]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], _rzrKaynakAd($x['kaynak']), $x['masa'] ?: '—', $x['on_tl'] ?: null, $x['ciro'] ?: null, $x['ciro'] > 0 ? round($x['ciro'] / max(1, $x['misafir'] ?: $x['kisi']), 2) : null], $gel), $bosSonuc ?? 'Seçili dönemde gelen rezervasyon yok.'),
                ];
            case 'on_siparis':
                $on = array_values(array_filter($l, fn ($x) => $x['on_n'] > 0)); $ur = [];
                if ($on && _srTablo('rezervasyon_kalemleri')) { try { foreach (array_chunk(array_column($on, 'id'), 500) as $ch) foreach (DB::table('rezervasyon_kalemleri')->whereIn('rezervasyon_id', $ch)->groupBy('urun_adi')->selectRaw('urun_adi, SUM(adet) n, SUM(adet * fiyat) tl, COUNT(DISTINCT rezervasyon_id) r')->get() as $k) { $u = &$ur[(string) $k->urun_adi]; $u['n'] = ($u['n'] ?? 0) + (int) $k->n; $u['tl'] = ($u['tl'] ?? 0) + (float) $k->tl; $u['r'] = ($u['r'] ?? 0) + (int) $k->r; unset($u); } } catch (\Throwable $e) {} }
                uasort($ur, fn ($a, $b) => $b['tl'] <=> $a['tl']); $usat = array_map(fn ($a, $v) => [$a, $v['n'], $v['r'], round($v['tl'], 2)], array_keys($ur), array_values($ur));
                $bos = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$on ? _rzrBos('on_siparis', $dn) : null);
                return [
                    $pano([_srKpi('Ön siparişli rezervasyon', count($on), 'sayi', '047857'), $kpiY('Rezervasyonlarda payı', $s['n'] ? round(count($on) / $s['n'] * 100, 1) : null, '059669'), _srKpi('Ön sipariş tutarı', round(array_sum(array_column($on, 'on_tl')), 2), 'tl', '0F766E'),
                        _srKpi('Ürün adedi', array_sum(array_column($on, 'on_n')), 'sayi', '115E59'), $kpiY('Ön siparişlilerde no-show', _rzrOzet($on)['noshow'], 'DC2626')],
                        [_srBlok('En çok ön sipariş edilen ürünler (tutar)', 'bar', '047857', [['Ürün', 'm'], ['Tutar', 'tl'], ['Adet', 's']], array_map(fn ($x) => [$x[0], $x[3], $x[1]], array_slice($usat, 0, 10)))], $bos),
                    _srSayfa('Ürünler', '', [['Ürün', 'm', 28], ['Adet', 's', 8, 'top'], ['Rezervasyon', 's', 10], ['Tutar', 'tl', 12, 'top bar']], $usat, $bos ?? _rzrBos('on_siparis', $dn)),
                    _srSayfa('Ön Siparişli Rezervasyonlar', '', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6], ['Ürün Adedi', 's', 9, 'top'], ['Ön Sipariş', 'tl', 12, 'top bar'], ['Durum', 'm', 15], ['Ciro', 'tl', 12, 'top']],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], $x['on_n'], $x['on_tl'], _rzrDurumAd($x['durum']), $x['ciro'] ?: null], $on), $bos ?? _rzrBos('on_siparis', $dn)),
                ];

            // ---------------- DOLULUK & MASA ----------------
            case 'isi':
                $gs = []; foreach (_rzrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gs[$n] = ($gs[$n] ?? 0) + 1; }
                $k = []; $r = []; $saatler = [];
                foreach ($l as $x) { if ($x['durum'] === 'iptal') continue; $k[$x['gun']][$x['h']] = ($k[$x['gun']][$x['h']] ?? 0) + $x['kisi']; $r[$x['gun']][$x['h']] = ($r[$x['gun']][$x['h']] ?? 0) + 1; $saatler[$x['h']] = 1; }
                ksort($saatler); $saatler = array_keys($saatler);
                $mat = function ($f) use ($saatler, $gs) { $o = []; for ($n = 1; $n <= 7; $n++) { if (empty($gs[$n])) continue; $o[] = array_merge([_rzrGunAd($n)], array_map(fn ($h) => $f($n, $h, $gs[$n]), $saatler)); } return $o; };
                $kol = array_merge([['Gün', 'm', 11]], array_map(fn ($h) => [sprintf('%02d', $h), 's', 6, 'mat'], $saatler));
                $dil = []; foreach ($k as $n => $hs) foreach ($hs as $h => $v) $dil[] = [_rzrGunAd($n), sprintf('%02d:00', $h), round($v / $gs[$n], 1), $r[$n][$h], $kap > 0 ? round($v / $gs[$n] / $kap * 100, 1) : null];
                usort($dil, fn ($a, $b) => $b[2] <=> $a[2]);
                $bos = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$k ? _rzrBos('rez', $dn) : null);
                return [
                    $pano([_srKpi('En yoğun dilim', $dil ? $dil[0][0] . ' ' . $dil[0][1] : '—', 'metin', 'D97706'), _srKpi('O dilimde ortalama kişi', $dil[0][2] ?? 0, 'sayi', 'B45309'), _srKpi('Masa kapasitesi', $kap ?: 'Girilmemiş', $kap ? 'sayi' : 'metin', '92400E'), _srKpi('Rezervasyonlu saat dilimi', count($dil), 'sayi', 'F59E0B')], [], $bos),
                    ['_oto' => []] + _srSayfa('Ortalama Rezervasyonlu Kişi', 'Gün × saat (rezervasyon saati): o dilimde ortalama kaç kişi rezervasyonla geliyor (iptal hariç). Koyu = yoğun.', $kol, $mat(fn ($n, $h, $g) => round(($k[$n][$h] ?? 0) / $g, 1)), $bos ?? _rzrBos('rez', $dn)),
                    ['_oto' => []] + _srSayfa('Rezervasyon Sayısı', 'Gün × saat: dönem boyunca toplam rezervasyon sayısı.', $kol, $mat(fn ($n, $h, $g) => $r[$n][$h] ?? 0), $bos ?? _rzrBos('rez', $dn)),
                    ['grafiksiz' => true] + _srSayfa('Dilimler (Yoğundan Boşa)', 'Kapasiteye oran = ortalama kişi ÷ toplam masa kapasitesi (aynı anda oturma varsayımı).', [['Gün', 'm', 11], ['Saat', 'm', 7], ['Ort. Kişi', 's', 9, 'bar'], ['Rezervasyon', 's', 10], ['Kapasiteye Oran %', 'y', 13]], $dil, $bos ?? _rzrBos('rez', $dn)),
                ];
            case 'doluluk':
                $gt = _rzrGunTablo($l, $bas, $bit, $kap); $bosGun = array_values(array_filter($gt, fn ($x) => $x[9] !== null && $x[9] < 20 && $x[0] >= date('Y-m-d')));
                $ort = $dol($s['aktif_kisi'], $dn['gun']); $gelOrt = $dol($s['gelen_kisi'], $dn['gun']);
                $bos = !$kap ? _rzrBos('kapasite_yok', $dn) : (!$l ? $bosRez : null);
                return [
                    $pano([_srKpi('Masa kapasitesi (kişi)', $kap ?: 'Girilmemiş', $kap ? 'sayi' : 'metin', 'B45309'), $kpiY('Ortalama rezervasyon doluluğu', $ort, 'D97706'), $kpiY('Gelen misafirle doluluk', $gelOrt, '059669'),
                        _srKpi('En dolu gün', ($mx = collect($gt)->sortByDesc(9)->first()) && $mx[9] !== null ? date('d.m', strtotime($mx[0])) . ' · %' . number_format($mx[9], 1, ',', '.') : '—', 'metin', '92400E'), _srKpi('Önümüzdeki boş günler (<%20)', count($bosGun), 'sayi', 'DC2626')],
                        [_srBlok('Günlere göre rezervasyon doluluğu (%)', 'col', 'D97706', [['Gün', 'm'], ['Doluluk %', 'y'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[9], ''], array_filter($gt, fn ($x) => $x[9] !== null)))], $bos),
                    _rzrSayfaGun($gt, $bosRez),
                    _srSayfa('Boş Kalan Yaklaşan Günler', 'Bugünden sonra rezervasyon doluluğu %20\'nin altında kalan günler — kampanya / grup teklifi için fırsat.', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Rezervasyon', 's', 10], ['Kişi', 's', 8], ['Doluluk %', 'y', 9]], array_map(fn ($x) => [$x[0], $x[1], $x[2], $x[3], $x[9]], $bosGun), !$kap ? _rzrBos('kapasite_yok', $dn) : 'Seçili dönemde boş kalan yaklaşan gün yok (ya da dönem geçmişte).'),
                ];
            case 'masa':
                $m = []; $atanmamis = 0;
                foreach ($l as $x) { if ($x['durum'] === 'iptal') continue; if (!$x['masa_id']) { $atanmamis++; continue; } $q = &$m[$x['masa_id']]; $q['ad'] = $x['masa'] ?: ('#' . $x['masa_id']); $q['kap'] = $x['kapasite']; $q['n'] = ($q['n'] ?? 0) + 1; $q['k'] = ($q['k'] ?? 0) + $x['kisi']; $q['bos'] = ($q['bos'] ?? 0) + ($x['kapasite'] > 0 && $x['kisi'] * 2 <= $x['kapasite'] ? 1 : 0); $q['asim'] = ($q['asim'] ?? 0) + ($x['kapasite'] > 0 && $x['kisi'] > $x['kapasite'] ? 1 : 0); $q['ciro'] = ($q['ciro'] ?? 0) + $x['ciro']; unset($q); }
                $sat = array_map(fn ($v) => [$v['ad'], $v['kap'] ?: null, $v['n'], $v['k'], round($v['k'] / $v['n'], 1), $v['kap'] > 0 ? round($v['k'] / $v['n'] / $v['kap'] * 100, 1) : null, $v['bos'], $v['asim'], round($v['ciro'], 2) ?: null], array_values($m));
                usort($sat, fn ($a, $b) => $b[2] <=> $a[2]); $topN = array_sum(array_column($sat, 2));
                $bos = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$d['masa_atama'] ? _rzrBos('masa_yok', $dn) : (!$sat ? 'Seçili dönemde masası atanmış rezervasyon yok.' : null));
                return [
                    $pano([_srKpi('Masa atanan rezervasyon', $topN, 'sayi', '92400E'), _srKpi('Masa atanmamış', $atanmamis, 'sayi', 'D97706'), _srKpi('Kullanılan masa', count($sat), 'sayi', 'B45309'),
                        _srKpi('Büyük masaya küçük grup', array_sum(array_column($sat, 6)), 'sayi', 'DC2626'), _srKpi('Kapasite aşımı', array_sum(array_column($sat, 7)), 'sayi', '991B1B')],
                        [_srBlok('En çok rezerve edilen masalar', 'bar', '92400E', [['Masa', 'm'], ['Rezervasyon', 's'], ['Kişi', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[3]], array_slice($sat, 0, 12)))], $bos),
                    _srSayfa('Masalar', 'Doluluk = ortalama kişi ÷ masa kapasitesi. Büyük masaya küçük grup = kişi sayısı kapasitenin yarısı ya da altı (masa verimsiz kullanılmış).', [['Masa', 'm', 12], ['Kapasite', 's', 9], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['Ort. Kişi', 's', 8], ['Doluluk %', 'y', 9], ['Küçük Grup', 's', 9, 'top esik:3'], ['Kapasite Aşımı', 's', 11, 'top esik:1'], ['Ciro', 'tl', 12, 'top']], $sat, $bos),
                ];

            // ---------------- GRUP & ETKİNLİK ----------------
            case 'grup':
                $gr = array_values(array_filter($l, $grupMu)); $gs = _rzrOzet($gr); $dp = _rzrDepozito($sid, $bas, $bit); $dpTel = []; foreach ($dp as $x) if ($x['tel'] && $x['tarih']) $dpTel[$x['tel'] . '|' . $x['tarih']] = $x;
                $sat = array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], _rzrKaynakAd($x['kaynak']), _rzrDurumAd($x['durum']), ($q = $dpTel[$x['tel'] . '|' . $x['tarih']] ?? null) ? $q['alinan'] : null, $q ? $q['sonuc'] : 'Depozito yok', $x['on_tl'] ?: null, $x['ciro'] ?: null, $x['not']], $gr);
                $dpsiz = count(array_filter($sat, fn ($x) => $x[7] === 'Depozito yok'));
                return [
                    $pano([_srKpi('Grup rezervasyonu', $gs['n'], 'sayi', 'DB2777'), _srKpi('Grup kişi', $gs['aktif_kisi'], 'sayi', 'BE185D'), $kpiY('Kişilerin içindeki payı', $s['aktif_kisi'] ? round($gs['aktif_kisi'] / $s['aktif_kisi'] * 100, 1) : null, '9D174D'),
                        $kpiY('Grup no-show', $gs['noshow'], 'DC2626'), _srKpi('Grup cirosu', $gs['ciro'], 'tl', '059669'), $kpiTl('Grup kişi başı', $gs['kisi_basi'], '047857'), _srKpi('Depozitosuz grup', $dpsiz, 'sayi', 'D97706'), _srKpi('Grup eşiği', $ay['grup'] . ' kişi', 'metin', '64748B')],
                        [_srBlok('Günlere göre grup kişi', 'col', 'DB2777', [['Gün', 'm'], ['Kişi', 's'], ['', 'm']], collect($gr)->groupBy('tarih')->map(fn ($g, $t) => [date('d.m', strtotime($t)), $g->sum('kisi'), ''])->values()->all()), $blokKanal($gr, 'BE185D')], !$gr ? (!$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : _rzrBos('grup', $dn)) : null),
                    _srSayfa('Grup Rezervasyonları', 'Depozito = telefon + etkinlik tarihi eşleşen kapora kaydı.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top bar'], ['Kanal', 'm', 15], ['Durum', 'm', 14], ['Depozito', 'tl', 11, 'top'], ['Depozito Durumu', 'm', 24], ['Ön Sipariş', 'tl', 11, 'top'], ['Ciro', 'tl', 12, 'top'], ['Not', 'm', 24]], $sat, !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : _rzrBos('grup', $dn)),
                ];
            case 'pipeline':
                $pb = date('Y-m-d'); $pe = date('Y-m-d', strtotime('+60 day')); $pl = _rzrRez($sid, $pb, $pe); $dp = _rzrDepozito($sid, $pb, $pe); $dpTel = []; foreach ($dp as $x) if ($x['tel'] && $x['tarih']) $dpTel[$x['tel'] . '|' . $x['tarih']] = $x;
                $sat = []; $kul = [];
                foreach ($pl as $x) {
                    if ($x['durum'] === 'iptal') continue; $q = $x['tel'] && isset($dpTel[$x['tel'] . '|' . $x['tarih']]) ? $dpTel[$x['tel'] . '|' . $x['tarih']] : null;
                    if (!$grupMu($x) && !$x['ozel'] && !$q && !$x['on_n']) continue; if ($q) $kul[$x['tel'] . '|' . $x['tarih']] = 1;
                    $sat[] = [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], implode(', ', array_merge($grupMu($x) ? ['Grup'] : [], array_map('_rzrOzelAd', array_diff($x['ozel'], ['diger'])))) ?: 'Ön sipariş', _rzrDurumAd($x['durum']), $q ? $q['tutar'] : null, $q ? $q['alinan'] : null, $q ? $q['sonuc'] : ($grupMu($x) ? 'Depozito alınmamış' : '—'), $x['on_tl'] ?: null, $x['not']];
                }
                foreach ($dp as $x) if (!isset($kul[($x['tel'] ?? '') . '|' . $x['tarih']])) $sat[] = [$x['tarih'], '—', $x['ad'], $x['kisi'], 'Yalnız depozito kaydı', 'Rezervasyon girilmemiş', $x['tutar'], $x['alinan'], $x['sonuc'], null, $x['not']];
                usort($sat, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
                $ay7 = count(array_filter($sat, fn ($x) => $x[0] <= date('Y-m-d', strtotime('+7 day'))));
                return [
                    $pano([_srKpi('Pipeline (60 gün)', count($sat), 'sayi', 'BE185D'), _srKpi('Kişi', array_sum(array_column($sat, 3)), 'sayi', 'DB2777'), _srKpi('Önümüzdeki 7 gün', $ay7, 'sayi', '9D174D'),
                        _srKpi('Onay bekleyen', count(array_filter($sat, fn ($x) => $x[5] === 'Onay bekliyor')), 'sayi', 'D97706'), _srKpi('Kapora bekleyen tutar', round(array_sum(array_map(fn ($x) => max(0, ($x[6] ?? 0) - ($x[7] ?? 0)), $sat)), 2), 'tl', '0891B2'),
                        _srKpi('Depozitosuz grup', count(array_filter($sat, fn ($x) => $x[8] === 'Depozito alınmamış')), 'sayi', 'DC2626')],
                        [_srBlok('Haftalara göre etkinlik kişi', 'col', 'BE185D', [['Hafta', 'm'], ['Kişi', 's'], ['', 'm']], collect($sat)->groupBy(fn ($x) => date('d.m', strtotime('monday this week', strtotime($x[0]))))->map(fn ($g, $h) => [$h, $g->sum(3), ''])->values()->all())],
                        !$sat ? (!$d['rezervasyon'] && !$d['depozito'] ? _rzrBos('rez_yok', $dn) : _rzrBos('pipeline', $dn)) : null),
                    _srSayfa('Etkinlik Pipeline', 'Bugünden itibaren 60 gün (seçili dönemden bağımsız): grup (' . $ay['grup'] . '+ kişi), özel gün, depozitolu ya da ön siparişli rezervasyonlar ve rezervasyonu girilmemiş kapora kayıtları.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top'], ['Tür', 'm', 20], ['Rezervasyon', 'm', 15], ['Kapora', 'tl', 11, 'top'], ['Alınan', 'tl', 11, 'top'], ['Kapora Durumu', 'm', 26], ['Ön Sipariş', 'tl', 11, 'top'], ['Not', 'm', 24]], $sat, _rzrBos('pipeline', $dn)),
                ];
            case 'ozel_gun':
                $oz = array_values(array_filter($l, fn ($x) => (bool) $x['ozel'])); $g = []; foreach ($oz as $x) foreach ($x['ozel'] as $k) $g[$k][] = $x;
                $sat = array_map(fn ($k, $xs) => [_rzrOzelAd($k), count($xs), array_sum(array_column($xs, 'kisi')), ($o = _rzrOzet($xs))['noshow'], $o['ciro'] ?: null, $o['kisi_basi']], array_keys($g), array_values($g)); usort($sat, fn ($a, $b) => $b[1] <=> $a[1]);
                $ozs = _rzrOzet($oz); $bos = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$oz ? _rzrBos('ozel', $dn) : null);
                return [
                    $pano([_srKpi('Özel istekli rezervasyon', count($oz), 'sayi', '9D174D'), $kpiY('Rezervasyonlarda payı', $s['n'] ? round(count($oz) / $s['n'] * 100, 1) : null, 'BE185D'), _srKpi('Doğum günü', count($g['dogum'] ?? []), 'sayi', 'DB2777'),
                        _srKpi('Alerji / diyet', count($g['alerji'] ?? []), 'sayi', 'DC2626'), $kpiTl('Özel gün kişi başı', $ozs['kisi_basi'], '059669'), $kpiTl('Genel kişi başı', $s['kisi_basi'], '64748B')],
                        [_srBlok('İstek türleri', 'pie', '9D174D', [['Tür', 'm'], ['Rezervasyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat))], $bos),
                    _srSayfa('Türe Göre', 'Türler rezervasyon etiketlerinden ve not metninden çıkarılır (ör. "doğum günü", "glütensiz").', [['Tür', 'm', 24], ['Rezervasyon', 's', 10, 'top bar'], ['Kişi', 's', 8, 'top'], ['No-show %', 'y', 9], ['Ciro', 'tl', 12, 'top'], ['Kişi Başı', 'tl', 10]], $sat, $bos),
                    _rzrSayfaListe('Özel İstekli Rezervasyonlar', '', $oz, $bos ?? _rzrBos('ozel', $dn)),
                ];

            // ---------------- DEPOZİTO ----------------
            case 'depozito':
                $dp = _rzrDepozito($sid, $bas, $bit); $g = []; foreach ($dp as $x) { $g[$x['sonuc']]['n'] = ($g[$x['sonuc']]['n'] ?? 0) + 1; $g[$x['sonuc']]['tl'] = ($g[$x['sonuc']]['tl'] ?? 0) + $x['alinan']; }
                $tut = array_sum(array_column($dp, 'tutar')); $al = array_sum(array_column($dp, 'alinan'));
                $bos = !$d['depozito'] ? _rzrBos('depozito_yok', $dn) : (!$dp ? _rzrBos('depozito', $dn) : null);
                return [
                    $pano([_srKpi('Depozito kaydı', count($dp), 'sayi', '0891B2'), _srKpi('Toplam tutar', round($tut, 2), 'tl', '0E7490'), _srKpi('Alınan', round($al, 2), 'tl', '059669'), _srKpi('Kalan (tahsil edilmemiş)', round(array_sum(array_column($dp, 'kalan')), 2), 'tl', 'D97706'),
                        $kpiY('Tahsilat oranı', $tut > 0 ? round($al / $tut * 100, 1) : null, '155E75'), _srKpi('İade edilen', round(array_sum(array_map(fn ($x) => $x['durum'] === 'iade' ? $x['alinan'] : 0, $dp)), 2), 'tl', '64748B'),
                        _srKpi('Yanan / yanma adayı', count(array_filter($dp, fn ($x) => str_starts_with($x['sonuc'], 'Yan'))), 'sayi', 'DC2626')],
                        [_srBlok('Duruma göre depozito (alınan)', 'pie', '0891B2', [['Durum', 'm'], ['Alınan', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v['tl'], 2), ''], array_keys($g), array_values($g)))], $bos),
                    _srSayfa('Durum Özeti', '', [['Durum', 'm', 32], ['Kayıt', 's', 8, 'top'], ['Alınan', 'tl', 13, 'top bar']], array_map(fn ($k, $v) => [$k, $v['n'], round($v['tl'], 2)], array_keys($g), array_values($g)), $bos),
                    _rzrSayfaDepozito('Depozitolar', 'Etkinlik tarihi dönemde olan (tarihsizse kayıt tarihi dönemde olan) kaporalar. Rezervasyon = aynı telefon ve tarihteki rezervasyonun durumu.', $dp, $bos),
                ];
            case 'depozito_risk':
                $dp = array_values(array_filter(_rzrDepozito($sid, $bas, $bit), fn ($x) => $x['gecti'] && !in_array($x['durum'], ['iade', 'tamam'], true)));
                $yan = array_values(array_filter($dp, fn ($x) => str_starts_with($x['sonuc'], 'Yan')));
                $bos = !$d['depozito'] ? _rzrBos('depozito_yok', $dn) : (!$dp ? 'Tarihi geçmiş ve kapatılmamış depozito yok — tüm kaporalar iade edildi ya da kullanıldı olarak kapatılmış.' : null);
                return [
                    $pano([_srKpi('Kapatılmamış geçmiş kapora', count($dp), 'sayi', '0E7490'), _srKpi('Yanma adayı (gelmedi)', count($yan), 'sayi', 'DC2626'), _srKpi('Yanma adayı tutar', round(array_sum(array_column($yan, 'alinan')), 2), 'tl', 'B91C1C'),
                        _srKpi('Tahsil edilmeden geçen', count(array_filter($dp, fn ($x) => $x['alinan'] <= 0)), 'sayi', 'D97706'), _srKpi('Geldi ama kapatılmamış', count(array_filter($dp, fn ($x) => str_starts_with($x['sonuc'], 'Misafir geldi'))), 'sayi', '0891B2')], [], $bos),
                    _rzrSayfaDepozito('Kapatılması Gereken Kaporalar', 'Depozito / Kapora ekranında durumu "İade" ya da "Tamam" yapılmamış, etkinlik tarihi geçmiş kayıtlar. Yanma adayı: kapora alınmış, aynı telefon ve tarihteki rezervasyon "Gelmedi".', $dp, $bos),
                ];

            // ---------------- MİSAFİR & CRM ----------------
            case 'tekrar':
                $ms = _rzrMisafir($sid, $bit); $donem = array_filter($ms, fn ($m) => $m['son'] >= $bas); $tek = array_values(array_filter($ms, fn ($m) => $m['n'] >= 2));
                $yeni = 0; $eski = 0; foreach ($l as $x) { if (!$x['tel']) continue; $ilk = $ms[$x['tel']]['ilk'] ?? $x['tarih']; if ($ilk < $bas) $eski++; else $yeni++; }
                $bos = !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : (!$d['telefon'] ? _rzrBos('tel_yok', $dn) : (!$tek ? _rzrBos('tekrar', $dn) : null));
                return [
                    $pano([_srKpi('Dönemde rezervasyon yapan misafir', count($donem), 'sayi', '4F46E5'), _srKpi('Daha önce gelmiş (dönem öncesi)', $eski, 'sayi', '4338CA'), _srKpi('İlk kez', $yeni, 'sayi', '6366F1'),
                        $kpiY('Tekrar eden payı', ($eski + $yeni) ? round($eski / ($eski + $yeni) * 100, 1) : null, '3730A3'), _srKpi('2+ rezervasyonlu (1 yıl)', count($tek), 'sayi', '312E81')],
                        [_srBlok('En sık rezervasyon yapanlar', 'bar', '4F46E5', [['Misafir', 'm'], ['Rezervasyon', 's'], ['Geldi', 's']], array_map(fn ($m) => [$m['ad'], $m['n'], $m['geldi']], array_slice($tek, 0, 10)))], $bos),
                    _srSayfa('Tekrar Gelen Misafirler', 'Son 1 yıl (dönem sonuna kadar), telefon numarasına göre. Ciro yalnız adisyonu bağlı gelişlerden.', [['Misafir', 'm', 20], ['Telefon', 'm', 15], ['Rezervasyon', 's', 10, 'top bar'], ['Geldi', 's', 7, 'top'], ['Gelmedi', 's', 8, 'esik:2'], ['İptal', 's', 7], ['İlk', 't', 10], ['Son Geliş', 't', 11], ['Ort. Kişi', 's', 8], ['Özel Gün / İstek', 'm', 22], ['Ciro', 'tl', 12, 'top']],
                        array_map(fn ($m) => [$m['ad'], _rzrMaske($m['tel']), $m['n'], $m['geldi'], $m['gelmedi'], $m['iptal'], $m['ilk'], $m['son_gelis'], $m['ort_kisi'], implode(', ', array_map('_rzrOzelAd', $m['ozel'])), $m['ciro'] ?: null], $tek), $bos),
                ];
            case 'crm_not':
                $ileri = _rzrRez($sid, date('Y-m-d'), date('Y-m-d', strtotime('+14 day'))); $hepsi = []; foreach (array_merge($l, $ileri) as $x) if ($x['durum'] !== 'iptal') $hepsi[$x['id']] = $x;
                $hepsi = array_values($hepsi); usort($hepsi, fn ($a, $b) => [$a['tarih'], $a['saat']] <=> [$b['tarih'], $b['saat']]); $mn = _rzrMusteriNot($sid, array_column($hepsi, 'musteri_id'));
                [$sayfa, $n] = _rzrSayfaCrm('Notlu Rezervasyonlar', 'Seçili dönem + önümüzdeki 14 gün. Müşteri kartı notu (alerji / tercih) rezervasyon telefonla eşleşen müşteri kartından.', $hepsi, $mn, !$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : _rzrBos('crm', $dn));
                $al = count(array_filter($hepsi, fn ($x) => in_array('alerji', $x['ozel'], true) || preg_match('/alerj/iu', ($mn[$x['musteri_id']]['not'] ?? ''))));
                return [
                    $pano([_srKpi('Notlu / istekli rezervasyon', $n, 'sayi', '4338CA'), _srKpi('Alerji / diyet', $al, 'sayi', 'DC2626'), _srKpi('Özel gün', count(array_filter($hepsi, fn ($x) => array_intersect(['dogum', 'yildonumu'], $x['ozel']))), 'sayi', 'DB2777'),
                        _srKpi('Müşteri kartına bağlı', count(array_filter($hepsi, fn ($x) => $x['musteri_id'])), 'sayi', '4F46E5'), _srKpi('Önümüzdeki 14 gün rezervasyon', count(array_filter($ileri, fn ($x) => $x['durum'] !== 'iptal')), 'sayi', '6366F1')], [], !$n ? (!$d['rezervasyon'] ? _rzrBos('rez_yok', $dn) : _rzrBos('crm', $dn)) : null),
                    $sayfa,
                ];

            // ---------------- KARŞILAMA ----------------
            case 'bekleme':
                $bk = _rzrBekleme($sid, $bas, $bit); $gr = array_filter(array_column($bk, 'gercek'), fn ($v) => $v !== null); $th = array_filter(array_map(fn ($x) => $x['gercek'] !== null && $x['tahmini'] !== null ? $x['gercek'] - $x['tahmini'] : null, $bk), fn ($v) => $v !== null);
                $ot = count(array_filter($bk, fn ($x) => $x['durum'] === 'oturtuldu')); $ip = count(array_filter($bk, fn ($x) => $x['durum'] === 'iptal'));
                $saat = []; foreach ($bk as $x) { $h = sprintf('%02d:00', $x['h']); $saat[$h]['n'] = ($saat[$h]['n'] ?? 0) + 1; $saat[$h]['k'] = ($saat[$h]['k'] ?? 0) + $x['kisi']; if ($x['gercek'] !== null) $saat[$h]['b'][] = $x['gercek']; if ($x['durum'] === 'iptal') $saat[$h]['i'] = ($saat[$h]['i'] ?? 0) + 1; } ksort($saat);
                $ssat = array_map(fn ($h, $v) => [$h, $v['n'], $v['k'], !empty($v['b']) ? round(array_sum($v['b']) / count($v['b']), 1) : null, $v['i'] ?? 0], array_keys($saat), array_values($saat));
                $bos = !$d['bekleme'] ? _rzrBos('bekleme_yok', $dn) : (!$bk ? _rzrBos('bekleme', $dn) : null);
                return [
                    $pano([_srKpi('Sıraya giren', count($bk), 'sayi', '0D9488'), _srKpi('Kişi', array_sum(array_column($bk, 'kisi')), 'sayi', '0F766E'), _srKpi('Ortalama gerçek bekleme (dk)', $gr ? round(array_sum($gr) / count($gr), 1) : 'Ölçüm yok', $gr ? 'sayi' : 'metin', '115E59'),
                        _srKpi('Tahminden sapma (dk)', $th ? round(array_sum($th) / count($th), 1) : '—', $th ? 'sayi' : 'metin', 'D97706'), $kpiY('Oturtulma oranı', $bk ? round($ot / count($bk) * 100, 1) : null, '059669'), $kpiY('Vazgeçme oranı', $bk ? round($ip / count($bk) * 100, 1) : null, 'DC2626')],
                        [_srBlok('Saatlere göre sıraya giren', 'col', '0D9488', [['Saat', 'm'], ['Kayıt', 's'], ['Vazgeçen', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[4]], $ssat))], $bos),
                    _srSayfa('Saatlere Göre', 'Gerçek bekleme = sıraya giriş → çağrılma (yoksa oturtulma) arası dakika.', [['Saat', 'm', 8], ['Kayıt', 's', 8, 'top bar'], ['Kişi', 's', 8, 'top'], ['Ort. Bekleme (dk)', 's', 13, 'esik:30'], ['Vazgeçen', 's', 9, 'top']], $ssat, $bos),
                    _srSayfa('Bekleme Listesi', '', [['Tarih', 't', 10], ['Saat', 'm', 7], ['Misafir', 'm', 20], ['Kişi', 's', 6, 'top'], ['Tahmini (dk)', 's', 10], ['Gerçek (dk)', 's', 10, 'esik:30'], ['Durum', 'm', 16], ['Not', 'm', 26]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ad'], $x['kisi'], $x['tahmini'], $x['gercek'], _rzrBeklemeDurumAd($x['durum']), $x['not']], $bk), $bos),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_rzrAiBaglam')) {
    function _rzrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $l = _rzrRez($sid, $bas, $bit); $s = _rzrOzet($l); $so = _rzrOzet(_rzrRez($sid, $dn['obas'], $dn['obit'])); $ay = _rzrAyar($sid); $kap = _rzrKapasite($sid);
        $gr = array_values(array_filter($l, fn ($x) => $x['kisi'] >= $ay['grup'])); $dp = _rzrDepozito($sid, $bas, $bit); $bk = _rzrBekleme($sid, $bas, $bit); $gb = array_filter(array_column($bk, 'gercek'), fn ($v) => $v !== null);
        $gun = collect($l)->where('durum', '!=', 'iptal')->groupBy(fn ($x) => _rzrGunAd($x['gun']))->map(fn ($g) => $g->sum('kisi'))->sortDesc()->all();
        $ms = _rzrMisafir($sid, $bit); $ps = array_filter(_rzrRez($sid, date('Y-m-d'), date('Y-m-d', strtotime('+14 day'))), fn ($x) => $x['durum'] !== 'iptal');
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _rzrVeriDurumu($sid),
            'hacim' => ['rezervasyon' => $s['n'], 'onceki' => $so['n'], 'kisi' => $s['aktif_kisi'], 'onceki_kisi' => $so['aktif_kisi'], 'ort_kisi' => $s['ort_kisi'], 'gune_gore_kisi' => $gun],
            'sonuc' => ['geldi' => $s['geldi'], 'gelmedi' => $s['gelmedi'], 'iptal' => $s['iptal'], 'sonucu_girilmemis' => $s['acik'], 'noshow_yuzde' => $s['noshow'], 'onceki_noshow_yuzde' => $so['noshow'], 'iptal_yuzde' => $s['iptal_or']],
            'kanallar' => array_map(fn ($x) => ['kanal' => $x[0], 'rezervasyon' => $x[1], 'noshow_yuzde' => $x[7], 'ciro' => $x[10], 'once_gun' => $x[9]], _rzrKanalSat($l)),
            'ciro' => ['rezervasyon_cirosu' => $s['ciro'], 'onceki' => $so['ciro'], 'kisi_basi' => $s['kisi_basi'], 'adisyonu_bagli_gelen' => $s['ciro_n'], 'gelen' => $s['geldi'], 'on_siparis_tl' => round(array_sum(array_column($l, 'on_tl')), 2)],
            'doluluk' => ['masa_kapasitesi' => $kap, 'ortalama_doluluk_yuzde' => $kap > 0 ? round($s['aktif_kisi'] / ($kap * max(1, $dn['gun'])) * 100, 1) : null],
            'grup' => ['esik_kisi' => $ay['grup'], 'adet' => count($gr), 'kisi' => array_sum(array_column($gr, 'kisi')), 'noshow_yuzde' => _rzrOzet($gr)['noshow'], 'ciro' => _rzrOzet($gr)['ciro']],
            'depozito' => ['kayit' => count($dp), 'tutar' => round(array_sum(array_column($dp, 'tutar')), 2), 'alinan' => round(array_sum(array_column($dp, 'alinan')), 2), 'yanma_adayi' => count(array_filter($dp, fn ($x) => str_starts_with($x['sonuc'], 'Yan'))), 'tahsil_edilmeden_gecen' => count(array_filter($dp, fn ($x) => $x['gecti'] && $x['alinan'] <= 0))],
            'misafir' => ['iki_ve_ustu_rezervasyon_1yil' => count(array_filter($ms, fn ($m) => $m['n'] >= 2)), 'iki_ve_ustu_gelmeyen' => count(array_filter($ms, fn ($m) => $m['gelmedi'] >= 2)), 'ozel_istekli' => count(array_filter($l, fn ($x) => (bool) $x['ozel']))],
            'bekleme_listesi' => ['kayit' => count($bk), 'ort_bekleme_dk' => $gb ? round(array_sum($gb) / count($gb), 1) : null, 'vazgecen' => count(array_filter($bk, fn ($x) => $x['durum'] === 'iptal'))],
            'onumuzdeki_14_gun' => ['rezervasyon' => count($ps), 'kisi' => array_sum(array_column($ps, 'kisi')), 'onay_bekleyen' => count(array_filter($ps, fn ($x) => $x['durum'] === 'bekliyor'))]];
    }
    function _rzrAiNabiz($sid, array $dn)
    {
        $b = _rzrAiBaglam($sid, $dn); $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺'; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $dg = _srYuzde($b['hacim']['rezervasyon'], $b['hacim']['onceki']); $ns = $b['sonuc']['noshow_yuzde'];
        return [
            ['renk' => '#A855F7', 'kat' => 'hacim', 'ikon' => 'event_available', 'etiket' => 'Rezervasyon', 'deger' => (string) $b['hacim']['rezervasyon'], 'alt' => $b['hacim']['kisi'] . ' kişi' . ($dg !== null ? ' · %' . number_format(abs($dg), 1, ',', '.') . ($dg >= 0 ? ' arttı' : ' düştü') : ''), 'sinif' => $dg !== null ? ($dg >= 0 ? 'yesil' : 'kirmizi') : '', 'ok' => $dg !== null ? ($dg >= 0 ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#DC2626', 'kat' => 'gelmeyen', 'ikon' => 'event_busy', 'etiket' => 'No-show', 'deger' => $y($ns), 'alt' => $b['sonuc']['gelmedi'] . ' gelmedi · ' . $b['sonuc']['sonucu_girilmemis'] . ' sonuçsuz', 'sinif' => $ns !== null && $ns > 15 ? 'kirmizi' : ($ns ? 'turuncu' : 'yesil')],
            ['renk' => '#059669', 'kat' => 'ciro', 'ikon' => 'payments', 'etiket' => 'Rezervasyon cirosu', 'deger' => $tl($b['ciro']['rezervasyon_cirosu']), 'alt' => $b['ciro']['kisi_basi'] ? 'Kişi başı ' . $tl($b['ciro']['kisi_basi']) : 'Adisyon bağı yok', 'sinif' => $b['ciro']['rezervasyon_cirosu'] > 0 ? 'yesil' : ''],
            ['renk' => '#D97706', 'kat' => 'doluluk', 'ikon' => 'event_seat', 'etiket' => 'Ortalama doluluk', 'deger' => $y($b['doluluk']['ortalama_doluluk_yuzde']), 'alt' => $b['doluluk']['masa_kapasitesi'] ? $b['doluluk']['masa_kapasitesi'] . ' kişilik kapasite' : 'Masa kapasitesi girilmemiş', 'sinif' => ''],
            ['renk' => '#DB2777', 'kat' => 'etkinlik', 'ikon' => 'celebration', 'etiket' => 'Grup / etkinlik', 'deger' => (string) $b['grup']['adet'], 'alt' => $b['grup']['kisi'] . ' kişi · ' . $b['onumuzdeki_14_gun']['rezervasyon'] . ' rezervasyon önümüzdeki 14 gün', 'sinif' => ''],
            ['renk' => '#0891B2', 'kat' => 'depozito', 'ikon' => 'account_balance_wallet', 'etiket' => 'Depozito alınan', 'deger' => $tl($b['depozito']['alinan']), 'alt' => $b['depozito']['yanma_adayi'] . ' yanma adayı · ' . $b['depozito']['kayit'] . ' kayıt', 'sinif' => $b['depozito']['yanma_adayi'] ? 'turuncu' : ''],
        ];
    }
}

// ---------- Ayar kartı (grup eşiği) ----------
if (!function_exists('_rzrUst')) {
    function _rzrUst($kat, $sid)
    {
        if (!in_array($kat, ['etkinlik', 'donemsel', 'hacim'], true)) return '';
        $a = _rzrAyar($sid); $e = fn ($x) => htmlspecialchars((string) $x, ENT_QUOTES);
        return '<form method="POST" action="/rezervasyon-raporlar/ayar" style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;padding:11px 16px;border-radius:16px;background:var(--card);border:1px solid var(--line);font-size:12.5px;color:var(--sub2)">'
            . '<input type="hidden" name="_token" value="' . $e(csrf_token()) . '"><input type="hidden" name="geri" value="' . $e(request()->getRequestUri()) . '">'
            . '<span style="display:inline-flex;align-items:center;gap:5px;font-weight:900;color:var(--ink)"><span class="material-icons-outlined" style="font-size:18px;color:#A855F7">tune</span>Hesap ayarları</span>'
            . '<label style="display:inline-flex;align-items:center;gap:6px" title="Bu kişi sayısı ve üstündeki rezervasyonlar grup / etkinlik sayılır.">Grup / etkinlik eşiği <input name="grup" type="number" min="4" max="50" value="' . (int) $a['grup'] . '" style="width:60px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:5px 7px;color:var(--ink)"> kişi</label>'
            . '<button type="submit" style="border:0;border-radius:9px;padding:7px 13px;font-weight:800;background:#A855F7;color:#fff;cursor:pointer">Kaydet</button></form>';
    }
}

// ---------- Modül kaydı ----------
_drKaydet('satis_etkinlik', [
    'ad' => 'Rezervasyon', 'baslik' => 'Rezervasyon Raporları', 'yol' => '/rezervasyon-raporlar', 'grup' => 'Rezervasyon Satış', 'depKodlari' => ['satis_etkinlik'],
    'ensure' => '_rzrEnsure', 'katalog' => '_rzrKatalog', 'kategoriler' => '_rzrKategoriler', 'veri' => '_rzrVeriDurumu', 'rapor' => '_rzrRapor',
    'ust' => fn ($kat, $sid) => _rzrUst($kat, $sid),
    'bosTablolar' => ['rezervasyonlar', 'depozitolar', 'bekleme_listesi'],
    'olcek' => ['kucuk' => ['donemsel', 'gelmeyen', 'ai'], 'orta' => ['hacim', 'ciro', 'doluluk', 'etkinlik', 'depozito']],
    'modAd' => ['rezervasyon' => 'Rezervasyon', 'sonuc' => 'Geldi / Gelmedi işareti', 'masa_atama' => 'Masa ataması', 'adisyon_bag' => 'Oturtma (adisyon bağı)', 'etiket' => 'Özel istek etiketi', 'telefon' => 'Misafir telefonu',
        'santral' => 'AI Santral rezervasyonu', 'kapasite' => 'Masa kapasitesi', 'depozito' => 'Depozito / kapora', 'bekleme' => 'Bekleme listesi', 'on_siparis' => 'Ön sipariş', 'durum_at' => 'İptal / geliş zamanı'],
    'modLink' => ['rezervasyon' => ['/rezervasyon', 'Rezervasyonlar'], 'sonuc' => ['/rezervasyon', 'Rezervasyonda "Geldi / Gelmedi" seçin'], 'masa_atama' => ['/rezervasyon', 'Rezervasyonda masa seçin'],
        'adisyon_bag' => [null, 'Patron uygulamasında rezervasyonu "Oturt"'], 'etiket' => [null, 'Patron uygulamasında rezervasyon etiketleri'], 'telefon' => ['/rezervasyon', 'Rezervasyonda telefon girin'],
        'santral' => ['/santral-kayitlar', 'AI Santral çağrıları'], 'kapasite' => ['/masalar', 'Masalarda kişi kapasitesi'], 'depozito' => ['/depozito', 'Depozito / Kapora'], 'bekleme' => ['/karsilama', 'Karşılama > Bekleme listesi'],
        'on_siparis' => [null, 'Patron uygulamasında rezervasyonda ön sipariş'], 'durum_at' => [null, 'Durum değişince otomatik (Faz 2)']],
    'ai' => [
        'gerekli' => ['rezervasyon', 'sonuc', 'adisyon_bag', 'kapasite'],
        'hero' => ['Rezervasyon dönemini 20 saniyede özetleyeyim.', 'Rezervasyon ve kişi trendi, kanallar, gelmeyen ve iptal oranı, rezervasyon cirosu ve kişi başı, doluluk, grup / etkinlik, depozito ve tekrar gelen misafirleri okur.'],
        'sorular' => ['No-show oranım neden yükseldi, ne yapmalıyım?', 'Hangi kanal en değerli rezervasyonu getiriyor?', 'Hangi gün ve saatler boş kalıyor?', 'Depozito almam gereken rezervasyonlar hangileri?', 'Grup ve etkinlik satışlarım nasıl gidiyor?', 'Rezervasyonlu misafir daha mı çok harcıyor?'],
        'adimlar' => ['Rezervasyonlar okunuyor…', 'Önceki dönemle karşılaştırılıyor…', 'Gelmeyen ve iptaller inceleniyor…', 'Ciro ve doluluk hesaplanıyor…', 'Grup, depozito ve misafirler kontrol ediliyor…', 'Öneriler yazılıyor…'],
        'selam' => 'rezervasyon',
        'sys' => 'Sen bir restoranın rezervasyon, grup / etkinlik satışı ve misafir deneyimini patron ve rezervasyon sorumlusu için analiz eden deneyimli bir restoran gelir yönetimi (revenue management) danışmanısın. '
            . 'No-show oranı yalnız sonuçlanan (geldi + gelmedi) rezervasyonlardan hesaplanır; "sonucu girilmemiş" kayıtlar varsa oranın eksik olabileceğini söyle. Restoranlarda no-show %10 üstü dikkat, %15 üstü risktir; depozito, teyit mesajı ve bekleme listesi öner. '
            . 'Rezervasyon cirosu yalnız adisyonu bağlı (oturtulmuş) rezervasyonlardan gelir. Misafirlerin kişisel bilgileri hakkında yorum yapma, suçlayıcı dil kullanma.',
        'baglam' => '_rzrAiBaglam',
        'veriVar' => fn ($b) => $b['hacim']['rezervasyon'] > 0 || $b['depozito']['kayit'] > 0 || $b['bekleme_listesi']['kayit'] > 0,
        'nabiz' => '_rzrAiNabiz',
    ],
]);

// Ayar kaydı
Route::post('/rezervasyon-raporlar/ayar', function (Request $r) {
    if (!_drErisim('satis_etkinlik')) return redirect('/pos-giris');
    $sid = _webSubeId(); $g = (int) $r->input('grup', 8);
    if (function_exists('_subeAyarYaz')) _subeAyarYaz($sid, 'rzr_grup_esik', (string) max(4, min(50, $g)));
    $geri = (string) $r->input('geri', '/rezervasyon-raporlar'); if (!str_starts_with($geri, '/rezervasyon-raporlar')) $geri = '/rezervasyon-raporlar';
    return redirect($geri)->with('ok', 'Rezervasyon rapor ayarları kaydedildi.');
});

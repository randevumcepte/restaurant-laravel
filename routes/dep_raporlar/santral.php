<?php
// =====================================================================
// AI SANTRAL RAPORLAR & ANALİZLER — /santral-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: santral_oturumlari (her çağrı: telefon, konuşma geçmişi JSON, sonuç, sipariş/rezervasyon bağı, başlangıç/son güncelleme),
//       adisyonlar (AI'nin aldığı telefon siparişi), rezervasyonlar (AI'nin aldığı rezervasyon + oturtulunca cirosu), musteriler (arayan tanıma),
//       santral_geri_arama (yarıda kalan çağrıyı geri arama kuyruğu), santral_ses_kayit (AI / aktarma ses kayıtları),
//       santral_cozulemeyen / santral_kalip / santral_ai_ogrenilen (eğitim), santral_ayarlari (aktarma + teslimat kuralı), cagri_loglari (sabit hat Caller ID).
// Süre: kapanış (son güncelleme) − başlangıç; çağrı kapanmadıysa ses kaydı boyutundan tahmin (8 kHz, 16 bit).
// Niyet: müşterinin cümlelerinden anahtar kelime ile (kural tabanlı) çıkarılır.
// Faz 2 (bundan sonrasını ölçer): santral_cevap_log — her cevabın kaynağı (hazır kalıp / öğrenilen / yapay zekâ / filtre) ve süresi (_snrCevapLog);
//       maliyet ayarları: hat dakika ücreti + yapay zekâ cevap başı maliyet (şube ayarı).
// Erişim: patron / müdür / süpervizör / 'gor_santral_rapor' / AI Santral sayfa yetkisi (gor_santral).
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2: cevap kaynağı kaydı + ayarlar ----------
if (!function_exists('_snrEnsure')) {
    function _snrEnsure()
    {
        if (cache()->get('snr_ensure_v1')) return;
        try {
            if (!Schema::hasTable('santral_cevap_log')) Schema::create('santral_cevap_log', function ($t) {
                $t->id(); $t->unsignedBigInteger('sube_id'); $t->unsignedBigInteger('oturum_id')->nullable(); $t->string('kaynak', 40); $t->string('aksiyon', 20)->nullable();
                $t->unsignedInteger('ms')->nullable(); $t->unsignedSmallInteger('kelime')->default(0); $t->timestamp('created_at')->useCurrent(); $t->index(['sube_id', 'created_at']);
            });
        } catch (\Throwable $e) {}
        cache()->put('snr_ensure_v1', 1, now()->addHours(12));
    }
    // /api/santral/konus içinden: her müşteri cümlesine verilen cevabın kaynağı (SantralAsistan->teshis), aksiyonu ve üretim süresi
    function _snrCevapLog($o, $res, $teshis, $metin = '', $ms = null)
    {
        try {
            _snrEnsure();
            DB::table('santral_cevap_log')->insert(['sube_id' => (int) ($o->sube_id ?? 0), 'oturum_id' => (int) ($o->id ?? 0) ?: null, 'kaynak' => mb_substr((string) $teshis, 0, 40) ?: 'bilinmiyor',
                'aksiyon' => is_array($res) && !empty($res['aksiyon']) ? mb_substr((string) $res['aksiyon'], 0, 20) : null, 'ms' => $ms !== null ? max(0, (int) $ms) : null,
                'kelime' => min(65000, count(preg_split('/\s+/u', trim((string) $metin), -1, PREG_SPLIT_NO_EMPTY))), 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }
    // Şube ayarı: hat dakika ücreti (TL/dk) + yapay zekâ cevap başı maliyet (TL)
    function _snrAyar($sid)
    {
        $al = fn ($k, $v) => function_exists('_subeAyarAl') ? _subeAyarAl($sid, $k, $v) : $v;
        $f = fn ($x) => max(0, min(1000, round((float) str_replace(',', '.', (string) $x), 4)));
        return ['dk' => $f($al('snr_dk_ucret', '0')), 'ai' => $f($al('snr_ai_ucret', '0'))];
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_snrOturum')) {
    function _snrTel($t) { $d = preg_replace('/\D/', '', (string) $t); return strlen($d) >= 10 ? substr($d, -10) : null; }
    function _snrMaske($t) { $d = _snrTel($t); return $d ? '0' . substr($d, 0, 3) . ' *** ' . substr($d, 6, 2) . ' ' . substr($d, 8, 2) : 'Gizli numara'; }
    function _snrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    function _snrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _snrSonucAd($k) { return ['siparis' => 'Sipariş alındı', 'rezervasyon' => 'Rezervasyon alındı', 'bilgi' => 'Bilgi verildi', 'aktar' => 'Personele aktarıldı', 'kacan' => 'Kaçan / yarıda kaldı', 'karsilama' => 'Karşılamada kapattı', 'devam' => 'Görüşme sürüyor'][$k] ?? $k; }
    function _snrArayanAd($k) { return ['kayitli' => 'Kayıtlı müşteri', 'tekrar' => 'Daha önce aramış (kartı yok)', 'yeni' => 'İlk kez arayan', 'gizli' => 'Gizli / numara yok'][$k] ?? $k; }
    function _snrNiyetler()
    {
        return ['sikayet' => ['Şikâyet', '/şikayet|şikâyet|sikayet|soğuk gel|soguk gel|berbat|rezalet|eksik gel|yanlış gel|yanlis gel|memnun değil|memnun degil|bozuk|kötüydü|kotuydu/u'],
            'takip' => ['Sipariş takibi', '/siparişim nerede|siparisim nerede|ne zaman gel|hala gelmedi|hâlâ gelmedi|gelmedi daha|kurye|yolda mı|yolda mi|gecikti/u'],
            'iptal' => ['İptal / değişiklik', '/iptal|vazgeç|vazgec|değiştir|degistir|ertele/u'],
            'siparis' => ['Sipariş', '/sipariş|siparis|paket|getir|gönder|gonder|eve |adrese|porsiyon|tane |adet/u'],
            'rezervasyon' => ['Rezervasyon', '/rezervasyon|masa ayır|masa ayir|yer ayır|yer ayir|kişilik|kisilik|kişi için|kisi icin|rezerve/u'],
            'menu' => ['Menü / fiyat', '/menü|menu|fiyat|kaç lira|kac lira|ücret|ucret|ne var|çeşit|cesit|vegan|glüten|gluten|alerj|içinde ne|icinde ne/u'],
            'bilgi' => ['Saat / adres / genel bilgi', '/saat kaç|kaçta|kacta|açık mı|acik mi|kapanış|kapanis|adres|nerede|nerdesiniz|konum|yol tarifi|otopark|park yeri|wifi|bahçe|bahce|çocuk|cocuk|teslimat bölge|bölgeye|bolgeye/u'],
            'kampanya' => ['Kampanya / indirim', '/kampanya|indirim|fırsat|firsat|kupon|puan/u'],
            'is' => ['İş başvurusu / tedarikçi', '/iş başvuru|is basvuru|eleman arıyor|eleman ariyor|tedarikçi|tedarikci|fatura|muhasebe/u'],
            'diger' => ['Belirsiz / diğer', null]];
    }
    function _snrNiyetAd($k) { return _snrNiyetler()[$k][0] ?? $k; }
    function _snrNiyet($metin, $sonuc)
    {
        $m = mb_strtolower((string) $metin, 'UTF-8');
        if ($sonuc === 'siparis' && !preg_match(_snrNiyetler()['sikayet'][1], $m)) return 'siparis';
        if ($sonuc === 'rezervasyon') return 'rezervasyon';
        if (trim($m) === '') return 'diger';
        foreach (_snrNiyetler() as $k => [$ad, $re]) if ($re && preg_match($re, $m)) return $k;
        return 'diger';
    }
    // Dönemdeki çağrılar (başlangıç zamanına göre) + sonuç, niyet, süre, sipariş/rezervasyon tutarı, arayan tanıma
    function _snrOturum($sid, $bas, $bit)
    {
        static $c = []; $key = "$sid|$bas|$bit"; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('santral_oturumlari')) return $c[$key] = [];
        $sel = ['id', 'telefon', 'gecmis', 'durum', 'sonuc', 'rezervasyon_id', 'adisyon_id', 'created_at', 'updated_at']; $sel[] = _srKolon('santral_oturumlari', 'geri_arama') ? 'geri_arama' : DB::raw('0 as geri_arama');
        try { $rows = DB::table('santral_oturumlari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get($sel)->all(); } catch (\Throwable $e) { $rows = []; }
        $adIds = []; $rzIds = []; $oIds = [];
        foreach ($rows as $r) { if ($r->adisyon_id) $adIds[] = (int) $r->adisyon_id; if ($r->rezervasyon_id) $rzIds[] = (int) $r->rezervasyon_id; $oIds[] = (int) $r->id; }
        $rz = [];
        if ($rzIds && _srTablo('rezervasyonlar')) { $rs = ['id', 'durum', 'kisi', 'tarih', 'saat']; $rs[] = _srKolon('rezervasyonlar', 'adisyon_id') ? 'adisyon_id' : DB::raw('NULL as adisyon_id'); try { foreach (array_chunk(array_unique($rzIds), 500) as $ch) foreach (DB::table('rezervasyonlar')->whereIn('id', $ch)->get($rs) as $x) { $rz[(int) $x->id] = $x; if ($x->adisyon_id) $adIds[] = (int) $x->adisyon_id; } } catch (\Throwable $e) {} }
        $ad = [];
        if ($adIds && _srTablo('adisyonlar')) { $as = ['id', 'toplam', 'durum']; foreach (['odeme_yontemi', 'teslimat_durumu'] as $k) $as[] = _srKolon('adisyonlar', $k) ? $k : DB::raw('NULL as ' . $k); try { foreach (array_chunk(array_unique($adIds), 500) as $ch) foreach (DB::table('adisyonlar')->whereIn('id', $ch)->get($as) as $x) $ad[(int) $x->id] = $x; } catch (\Throwable $e) {} }
        $ses = [];
        if ($oIds && _srTablo('santral_ses_kayit')) { try { foreach (array_chunk($oIds, 500) as $ch) foreach (DB::table('santral_ses_kayit')->whereIn('oturum_id', $ch)->get(['oturum_id', 'tur', 'boyut']) as $x) { $q = &$ses[(int) $x->oturum_id]; $q['n'] = ($q['n'] ?? 0) + 1; $q[$x->tur] = ($q[$x->tur] ?? 0) + (int) $x->boyut; unset($q); } } catch (\Throwable $e) {} }
        $o = []; $simdi = time();
        foreach ($rows as $r) {
            $g = json_decode((string) $r->gecmis, true); $g = is_array($g) ? $g : [];
            $u = []; $nA = 0; foreach ($g as $m) { if (($m['role'] ?? '') === 'user') $u[] = trim((string) ($m['content'] ?? '')); elseif (($m['role'] ?? '') === 'assistant') $nA++; }
            $u = array_values(array_filter($u, fn ($x) => $x !== '')); $cr = strtotime((string) $r->created_at); $up = $r->updated_at ? strtotime((string) $r->updated_at) : null;
            $sn = ($up && $r->durum === 'kapandi') ? $up - $cr : null; if ($sn !== null && ($sn < 0 || $sn > 3600)) $sn = null; $tahmini = false;
            if ($sn === null && !empty($ses[(int) $r->id]['ai'])) { $sn = (int) round(max(0, $ses[(int) $r->id]['ai'] - 44) / 16000); $tahmini = true; if ($sn > 3600) $sn = null; }
            $son = (string) $r->sonuc;
            if (!in_array($son, ['siparis', 'rezervasyon', 'aktar', 'kacan', 'bilgi'], true)) $son = $r->durum === 'acik' && $simdi - $cr < 900 ? 'devam' : 'bilgi';
            if ($son === 'bilgi' && !$u) $son = 'karsilama';
            $a = $r->adisyon_id ? ($ad[(int) $r->adisyon_id] ?? null) : null; $rr = $r->rezervasyon_id ? ($rz[(int) $r->rezervasyon_id] ?? null) : null; $ra = $rr && $rr->adisyon_id ? ($ad[(int) $rr->adisyon_id] ?? null) : null;
            $o[] = ['id' => (int) $r->id, 'tel' => _snrTel($r->telefon), 'ts' => $cr, 'tarih' => date('Y-m-d', $cr), 'saat' => date('H:i', $cr), 'h' => (int) date('G', $cr), 'gun' => (int) date('N', $cr),
                'sonuc' => $son, 'nU' => count($u), 'nA' => $nA, 'ilk' => $u[0] ?? '', 'sonCumle' => $u ? end($u) : '', 'metin' => implode(' | ', $u), 'niyet' => _snrNiyet(implode(' ', $u), $son),
                'sn' => $sn, 'tahmini' => $tahmini, 'geri_arama' => (int) $r->geri_arama, 'ses' => $ses[(int) $r->id]['n'] ?? 0, 'ses_aktarma' => !empty($ses[(int) $r->id]['aktarma']),
                'adisyon_id' => (int) $r->adisyon_id, 'sp_tl' => $a && $a->durum !== 'iptal' ? (float) $a->toplam : 0.0, 'sp_durum' => $a ? (string) $a->durum : '', 'sp_odeme' => $a ? (string) $a->odeme_yontemi : '', 'sp_teslimat' => $a ? (string) $a->teslimat_durumu : '',
                'rez_id' => (int) $r->rezervasyon_id, 'rez_durum' => $rr ? (string) $rr->durum : '', 'rez_kisi' => $rr ? (int) $rr->kisi : 0, 'rez_tarih' => $rr ? substr((string) $rr->tarih, 0, 10) : '', 'rez_tl' => $ra && $ra->durum === 'odendi' ? (float) $ra->toplam : 0.0, 'arayan' => 'gizli'];
        }
        _snrTanima($sid, $o, $bas, $bit);
        return $c[$key] = $o;
    }
    // Arayan tanıma: müşteri kartı çağrıdan önce var mı; yoksa bu numara daha önce (365 gün) aramış mı
    function _snrTanima($sid, array &$o, $bas, $bit)
    {
        $tels = array_values(array_unique(array_filter(array_column($o, 'tel')))); if (!$tels) return;
        $mk = [];
        if (_srTablo('musteriler')) {
            try {
                if (_srKolon('musteriler', 'telefon_norm')) { foreach (array_chunk($tels, 500) as $ch) foreach (DB::table('musteriler')->where('sube_id', $sid)->whereIn('telefon_norm', $ch)->get(['telefon_norm', 'created_at']) as $m) { $t = strtotime((string) $m->created_at) ?: 0; $mk[$m->telefon_norm] = isset($mk[$m->telefon_norm]) ? min($mk[$m->telefon_norm], $t) : $t; } }
                $set = array_flip($tels);
                foreach (DB::table('musteriler')->where('sube_id', $sid)->whereNotNull('telefon')->orderByDesc('id')->limit(20000)->get(['telefon', 'created_at']) as $m) { $tl = _snrTel($m->telefon); if ($tl && isset($set[$tl])) { $t = strtotime((string) $m->created_at) ?: 0; $mk[$tl] = isset($mk[$tl]) ? min($mk[$tl], $t) : $t; } }
            } catch (\Throwable $e) {}
        }
        $once = [];
        try { foreach (DB::table('santral_oturumlari')->where('sube_id', $sid)->whereNotNull('telefon')->whereBetween('created_at', [date('Y-m-d', strtotime($bas . ' -365 day')) . ' 00:00:00', $bit . ' 23:59:59'])->get(['telefon', 'created_at']) as $x) { $tl = _snrTel($x->telefon); if ($tl) $once[$tl][] = strtotime((string) $x->created_at); } } catch (\Throwable $e) {}
        foreach ($o as &$x) {
            if (!$x['tel']) { $x['arayan'] = 'gizli'; continue; }
            if (isset($mk[$x['tel']]) && $mk[$x['tel']] < $x['ts'] - 60) { $x['arayan'] = 'kayitli'; continue; }
            $x['arayan'] = count(array_filter($once[$x['tel']] ?? [], fn ($t) => $t < $x['ts'] - 5)) ? 'tekrar' : 'yeni';
        }
        unset($x);
    }
    function _snrOzet(array $l)
    {
        $s = ['n' => count($l), 'konusulan' => 0, 'kacan' => 0, 'karsilama' => 0, 'aktar' => 0, 'siparis' => 0, 'rezervasyon' => 0, 'bilgi' => 0, 'devam' => 0, 'sp_tl' => 0.0, 'rez_tl' => 0.0, 'rez_kisi' => 0, 'sn' => 0, 'sn_n' => 0, 'tur' => 0, 'yeni' => 0, 'kayitli' => 0, 'tekrar' => 0, 'gizli' => 0, 'geri' => 0];
        foreach ($l as $x) {
            if ($x['nU'] > 0) { $s['konusulan']++; $s['tur'] += $x['nU']; }
            if (isset($s[$x['sonuc']])) $s[$x['sonuc']]++; $s[$x['arayan']]++; if ($x['geri_arama']) $s['geri']++;
            $s['sp_tl'] += $x['sp_tl']; $s['rez_tl'] += $x['rez_tl']; if ($x['sonuc'] === 'rezervasyon') $s['rez_kisi'] += $x['rez_kisi'];
            if ($x['sn'] !== null && $x['nU'] > 0) { $s['sn'] += $x['sn']; $s['sn_n']++; }
        }
        $k = $s['konusulan']; $s['kayip'] = $s['kacan'] + $s['karsilama']; $s['cozuldu'] = $s['siparis'] + $s['rezervasyon'] + $s['bilgi'];
        $s['donusum'] = $k ? round(($s['siparis'] + $s['rezervasyon']) / $k * 100, 1) : null; $s['aktar_or'] = $k ? round($s['aktar'] / $k * 100, 1) : null; $s['cozum_or'] = $k ? round($s['cozuldu'] / $k * 100, 1) : null;
        $s['kayip_or'] = $s['n'] ? round($s['kayip'] / $s['n'] * 100, 1) : null; $s['ort_dk'] = $s['sn_n'] ? round($s['sn'] / $s['sn_n'] / 60, 2) : null; $s['dk'] = round($s['sn'] / 60, 1); $s['ort_tur'] = $k ? round($s['tur'] / $k, 1) : null;
        $s['ciro'] = round($s['sp_tl'] + $s['rez_tl'], 2); $s['sp_tl'] = round($s['sp_tl'], 2); $s['rez_tl'] = round($s['rez_tl'], 2);
        return $s;
    }
    function _snrGeriArama($sid, $bas, $bit)
    {
        if (!_srTablo('santral_geri_arama')) return [];
        try { $rows = DB::table('santral_geri_arama')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get(); } catch (\Throwable $e) { return []; }
        $sonra = _snrOturum($sid, $bas, date('Y-m-d', strtotime($bit . ' +2 day'))); $o = [];
        foreach ($rows as $r) {
            $t = strtotime((string) $r->created_at); $tl = _snrTel($r->telefon); $k = null;
            foreach ($sonra as $x) if ($x['tel'] === $tl && $x['ts'] > $t && $x['ts'] - $t <= 172800 && in_array($x['sonuc'], ['siparis', 'rezervasyon'], true)) { $k = $x; break; }
            $o[] = ['tel' => $tl, 'tarih' => date('Y-m-d', $t), 'saat' => date('H:i', $t), 'durum' => (string) $r->durum, 'deneme' => (int) $r->deneme, 'son' => $r->son_deneme_at ? date('d.m H:i', strtotime((string) $r->son_deneme_at)) : '',
                'kurtarildi' => $k ? _snrSonucAd($k['sonuc']) . ($k['geri_arama'] ? ' (geri aramada)' : ' (müşteri kendi aradı)') : '', 'tl' => $k ? $k['sp_tl'] + $k['rez_tl'] : 0.0];
        }
        return $o;
    }
    function _snrGeriDurumAd($d) { return ['bekliyor' => 'Sırada', 'araniyor' => 'Aranıyor', 'basarili' => 'Ulaşıldı', 'cevapsiz' => 'Cevap vermedi', 'vazgecildi' => 'Müşteri kendisi aradı / iptal'][$d] ?? $d; }
    function _snrCallerId($sid, $bas, $bit)
    {
        if (!_srTablo('cagri_loglari')) return [];
        try { return DB::table('cagri_loglari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get()->map(fn ($r) => ['tel' => _snrTel($r->telefon), 'ts' => strtotime((string) $r->created_at), 'yon' => (string) $r->yon, 'sonuc' => (string) $r->sonuc, 'hat' => (string) ($r->hat ?? ''), 'musteri' => (int) $r->musteri_id, 'adisyon' => (int) $r->adisyon_id])->all(); } catch (\Throwable $e) { return []; }
    }
    function _snrKaynakAd($k)
    {
        if (str_starts_with($k, 'http_') || str_starts_with($k, 'curl') || str_starts_with($k, 'exception')) return 'Yapay zekâ hatası → aktarıldı';
        return ['ok' => 'Yapay zekâ (dil modeli)', 'zorla_aksiyon' => 'Yapay zekâ (kayıt güvenlik ağı)', 'kalip' => 'Hazır cevap (kalıp / SSS)', 'ogrenilen' => 'Öğrenilmiş cevap (önbellek)', 'mevcut_rez' => 'Mevcut rezervasyon uyarısı',
            'kufur' => 'Küfür filtresi', 'anahtar_yok' => 'AI anahtarı yok → aktarıldı', 'cevap_yok' => 'AI cevap vermedi → aktarıldı'][$k] ?? ($k ?: 'Bilinmiyor');
    }
    function _snrUcretsizMi($k) { return in_array($k, ['kalip', 'ogrenilen', 'mevcut_rez', 'kufur'], true); }
    function _snrCevapLoglari($sid, $bas, $bit)
    {
        if (!_srTablo('santral_cevap_log')) return [];
        try { return DB::table('santral_cevap_log')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['oturum_id', 'kaynak', 'aksiyon', 'ms', 'created_at'])->map(fn ($r) => (array) $r)->all(); } catch (\Throwable $e) { return []; }
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_snrVeriDurumu')) {
    function _snrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString();
        $say = function ($t, $kol, $ek = null, $subeli = true) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t); if ($subeli) $q->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        return ['cagri' => $say('santral_oturumlari', 'created_at'), 'konusma' => $say('santral_oturumlari', 'created_at', fn ($q) => $q->where('gecmis', 'like', '%"user"%')),
            'siparis' => $say('santral_oturumlari', 'created_at', fn ($q) => $q->whereNotNull('adisyon_id')), 'rezervasyon' => $say('santral_oturumlari', 'created_at', fn ($q) => $q->whereNotNull('rezervasyon_id')),
            'aktar' => $say('santral_oturumlari', 'created_at', fn ($q) => $q->where('sonuc', 'aktar')), 'geri_arama' => $say('santral_geri_arama', 'created_at'), 'ses' => $say('santral_ses_kayit', 'created_at'),
            'cozulemeyen' => $say('santral_cozulemeyen', null), 'kalip' => $say('santral_kalip', null), 'ogrenilen' => $say('santral_ai_ogrenilen', null), 'ayar' => $say('santral_ayarlari', null),
            'callerid' => $say('cagri_loglari', 'created_at'), 'cevap_log' => $say('santral_cevap_log', 'created_at')];
    }
    function _snrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'cagri_yok' => 'AI Santral çağrı kaydı yok: son 1 yılda bu şubenin numarasına yapay zekânın açtığı arama gelmemiş. Kayıtlar santral köprüsü (Asterisk) her çağrıyı açtığında oluşur. Hat bağlı değilse AI Santral > Ayarlar\'dan işletme hattını (DID) tanımlayın; test için AI Santral > Test ekranını kullanın.',
            'cagri' => 'Seçili dönemde (' . $donem . ') AI Santral\'a çağrı gelmemiş.',
            'konusma' => 'Seçili dönemde müşterinin konuştuğu (en az bir cümle söylediği) çağrı yok — aramalar karşılamada kapanmış.',
            'kacan' => 'Seçili dönemde (' . $donem . ') kaçan, yarıda kalan ya da karşılamada kapanan çağrı yok.',
            'geri_yok' => 'Geri arama kaydı yok. Görüşme yarıda kalınca (müşteri konuştu ama sipariş / rezervasyon tamamlanmadı) numara otomatik geri arama sırasına girer; bu özellik santral köprüsünün geri arama görevi çalıştığında dolar.',
            'geri' => 'Seçili dönemde (' . $donem . ') geri arama sırasına giren çağrı yok.',
            'callerid_yok' => 'Sabit hat Caller ID kaydı yok. Santral (FreePBX / Asterisk) gelen aramayı /api/callerid/gelen adresine bildirdiğinde Çağrılar ve Çağrı Ekranı dolar; bu rapor o kayıtları okur.',
            'callerid' => 'Seçili dönemde (' . $donem . ') sabit hattan Caller ID kaydı yok.',
            'aktar' => 'Seçili dönemde (' . $donem . ') personele aktarılan çağrı yok.',
            'siparis' => 'Seçili dönemde (' . $donem . ') AI Santral\'ın aldığı telefon siparişi yok.',
            'rez' => 'Seçili dönemde (' . $donem . ') AI Santral\'ın aldığı rezervasyon yok.',
            'cevap_log' => 'Cevap kaynağı (hazır kalıp / öğrenilen / yapay zekâ) bu rapor modülü kurulduktan sonra her müşteri cümlesinde kaydedilir; kayıt başlaması için santral konuşma ucuna tek satırlık kayıt bağlanmalı (Faz 2). Bağlandıktan sonraki çağrılarda dolar.',
            'cozulemeyen' => 'Çözülemeyen soru yok: AI aktardığı çağrılarda müşterinin sorusunu buraya yazar. AI Santral > Eğitim ekranından bu sorulara hazır cevap (kalıp) ekleyebilirsiniz.',
            'egitim' => 'AI Santral eğitim kaydı yok. AI Santral > Eğitim ekranında sık sorulara hazır cevap (kalıp) girin ya da menü / SSS PDF\'i yükleyin; AI öğrendiği cevapları da burada biriktirir.',
            'tekrar' => 'Seçili dönemde birden fazla kez arayan numara yok.',
            'maliyet' => 'Maliyet için AI Santral rapor ayarlarında hat dakika ücreti ve / veya yapay zekâ cevap başı maliyet girin (Hacim, Maliyet ya da Dönemsel sayfasının üstündeki kart).',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_snrKatalog')) {
    function _snrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Santral Bülteni', 'Dün kaç çağrı geldi, kaçı konuştu, kaçı kaçtı, kaçı personele aktarıldı; alınan sipariş ve rezervasyon, ciro, geri arama sırası ve çözülemeyen sorular.', 'today', '#7C3AED', ['cagri'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Santral Raporu', 'Gün gün çağrı, dönüşüm ve ciro; arama nedenleri, aktarılan ve kaçan çağrılar, önceki haftayla kıyas.', 'date_range', '#6D28D9', ['cagri', 'konusma'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Santral Yönetim Raporu', 'AI Santral\'ın ay boyunca karşıladığı çağrılar, çözüm ve aktarma oranı, sipariş / rezervasyon cirosu, arayan profili, tekrar arayanlar, eğitim ihtiyacı ve maliyet.', 'calendar_month', '#5B21B6', ['cagri', 'konusma'], 'gecen_ay'],
            'hacim' => ['hacim', 'Çağrı Hacmi & Trend', 'Gün gün gelen çağrı, konuşulan, kaçan, aktarılan ve dönüşen çağrılar; haftanın günlerine göre ortalama.', 'call', '#2563EB', ['cagri'], null],
            'isi' => ['hacim', 'Saat × Gün Isı Haritası', 'Haftanın günü × saat: ortalama çağrı ve kaçan çağrı; en yoğun saatler ve personel desteği gereken dilimler.', 'grid_on', '#1D4ED8', ['cagri'], null],
            'sure' => ['hacim', 'Görüşme Süresi', 'Süre dağılımı (30 sn altı … 8 dk üstü), sonuca göre ortalama süre ve konuşma turu; en uzun görüşmeler.', 'timer', '#1E40AF', ['konusma'], null],
            'kacan' => ['cevap', 'Kaçan & Yarıda Kalan Çağrılar', 'Karşılamada kapatan, yarıda kalan ve sonuçsuz çağrılar: saatlere göre, geri arandı mı, sonra tekrar aradı mı.', 'phone_missed', '#DC2626', ['cagri'], null],
            'geri_arama' => ['cevap', 'Geri Arama Kurtarma', 'Yarıda kalan çağrıların otomatik geri arama sırası: ulaşılan, cevapsız, müşterinin kendisi aradığı; sonradan siparişe / rezervasyona dönen (kurtarılan) ciro.', 'phone_callback', '#B91C1C', ['geri_arama'], null],
            'callerid' => ['cevap', 'Sabit Hat (Caller ID) Çağrıları', 'Çağrı Ekranı / Çağrılar\'a düşen sabit hat aramaları: gelen, kaçan, siparişe dönen; hat ve saat bazında.', 'contact_phone', '#991B1B', ['callerid'], null],
            'cozum' => ['cozum', 'AI Çözdü / Personele Aktarıldı', 'Konuşulan çağrıların sonucu: AI\'nin kendi çözdükleri (sipariş, rezervasyon, bilgi) ve personele aktarılanlar; niyete göre çözüm oranı.', 'smart_toy', '#059669', ['konusma'], null],
            'aktarma' => ['cozum', 'Aktarma Analizi', 'Personele aktarılan çağrılar: saat, arama nedeni, müşterinin son cümlesi, aktarma kaydı; aktarma ayarları (hedef, strateji, zil süresi).', 'transfer_within_a_station', '#047857', ['aktar'], null],
            'cevap_kaynak' => ['cozum', 'Cevap Kaynağı & Hız', 'Her cevap nereden geldi: hazır kalıp, öğrenilmiş cevap, yapay zekâ ya da filtre; ücretsiz cevap oranı ve ortalama cevap süresi (Faz 2).', 'bolt', '#0F766E', ['cevap_log'], null],
            'donusum' => ['donusum', 'Dönüşüm Hunisi', 'Gelen çağrı → konuşma → sipariş / rezervasyon niyeti → kaydedildi → gerçekleşti: her aşamada kayıp.', 'filter_alt', '#D97706', ['konusma'], null],
            'siparis' => ['donusum', 'Telefonla Alınan Siparişler', 'AI\'nin aldığı paket siparişleri: tutar, ödeme şekli, durum ve teslimat; tüm telefon siparişleri içindeki payı.', 'delivery_dining', '#B45309', ['siparis'], null],
            'rezervasyon' => ['donusum', 'Santral Rezervasyonları', 'AI\'nin aldığı rezervasyonlar: kişi, geldi / gelmedi, no-show oranı ve oturtulan masaların cirosu; diğer kanallarla kıyas.', 'event_available', '#92400E', ['rezervasyon'], null],
            'niyet' => ['niyet', 'Arama Nedenleri (Niyet)', 'Müşteri neden arıyor: sipariş, rezervasyon, menü / fiyat, saat / adres, sipariş takibi, şikâyet…; her nedenin çözüm ve aktarma oranı, örnek cümleler.', 'psychology', '#DB2777', ['konusma'], null],
            'cozulemeyen' => ['niyet', 'Çözülemeyen Sorular (Eğitim İhtiyacı)', 'AI\'nin cevaplayamayıp aktardığı sorular, kaç kez soruldukları ve son tarih; dönemde aktarılan çağrılarda müşterinin son cümlesi.', 'help', '#BE185D', ['cozulemeyen'], null],
            'egitim' => ['niyet', 'Eğitim Kapsamı', 'Hazır cevap (kalıp) sayısı ve kategorileri, öğrenilmiş cevaplar ve en çok kullanılanlar; eğitimin cevap verme gücü.', 'school', '#9D174D', ['kalip'], null],
            'tanima' => ['arayan', 'Arayan Tanıma (Yeni / Mevcut)', 'Arayanların ne kadarı kayıtlı müşteri, ne kadarı ilk kez arıyor, gizli numara payı; arayan tipine göre dönüşüm.', 'badge', '#4F46E5', ['cagri'], null],
            'tekrar' => ['arayan', 'Tekrar Arayanlar', 'Aynı numaradan birden fazla çağrı: kaç kez, sonuçları, 2 saat içinde tekrar arama (çözülmemiş sorun işareti), sipariş ve ciro.', 'repeat', '#4338CA', ['cagri'], null],
            'maliyet' => ['maliyet', 'Konuşma Dakikası & Maliyet', 'Toplam konuşma dakikası, ses kaydı, yapay zekâ cevap sayısı; girilen ücretlerle maliyet, sipariş / rezervasyon başına maliyet ve ciroya oranı.', 'savings', '#0891B2', ['konusma'], 'bu_ay'],
        ];
    }
    function _snrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron için hazır santral raporları: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#7C3AED'],
            'hacim' => ['Çağrı Hacmi', 'call', 'Gün gün çağrı trendi, saat × gün ısı haritası ve görüşme süresi.', '#2563EB'],
            'cevap' => ['Kaçan & Geri Arama', 'phone_missed', 'Kaçan ve yarıda kalan çağrılar, geri arama kurtarma, sabit hat Caller ID.', '#DC2626'],
            'cozum' => ['AI Çözüm', 'smart_toy', 'AI\'nin çözdüğü ve personele aktardığı çağrılar, aktarma analizi, cevap kaynağı.', '#059669'],
            'donusum' => ['Sipariş & Rezervasyon', 'filter_alt', 'Dönüşüm hunisi, telefonla alınan siparişler ve santral rezervasyonları.', '#D97706'],
            'niyet' => ['Niyet & Eğitim', 'psychology', 'Arama nedenleri, çözülemeyen sorular ve AI eğitim kapsamı.', '#DB2777'],
            'arayan' => ['Arayanlar', 'badge', 'Yeni / kayıtlı müşteri ve tekrar arayanlar.', '#4F46E5'],
            'maliyet' => ['Süre & Maliyet', 'savings', 'Konuşma dakikası, yapay zekâ kullanımı ve maliyet.', '#0891B2'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı santral özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_snrSayfaListe')) {
    function _snrSayfaListe($ad, $not, array $l, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa($ad, $not, [['Tarih', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Arayan', 'm', 16], ['Neden', 'm', 16], ['Sonuç', 'm', 18], ['Tur', 's', 5], ['Süre (dk)', 's', 8, 'top'], ['Tutar', 'tl', 11, 'top'], ['Müşterinin İlk Cümlesi', 'm', 40]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrArayanAd($x['arayan']), _snrNiyetAd($x['niyet']), _snrSonucAd($x['sonuc']) . ($x['geri_arama'] ? ' (geri arama)' : ''), $x['nU'], $x['sn'] !== null ? round($x['sn'] / 60, 1) : null, ($x['sp_tl'] + $x['rez_tl']) ?: null, mb_substr($x['ilk'], 0, 160)], $l), $bos);
    }
    function _snrGunTablo(array $l, $bas, $bit)
    {
        $g = []; foreach ($l as $x) $g[$x['tarih']][] = $x; $o = [];
        foreach (_snrGunler($bas, $bit) as $t) { $s = _snrOzet($g[$t] ?? []); $o[] = [$t, _snrGunAd(date('N', strtotime($t))), $s['n'], $s['konusulan'], $s['kayip'], $s['aktar'], $s['siparis'], $s['rezervasyon'], $s['donusum'], $s['ort_dk'], $s['ciro'] ?: null]; }
        return $o;
    }
    function _snrSayfaGun(array $sat, $bos)
    {
        return ['grafiksiz' => true] + _srSayfa('Gün Gün', 'Konuşulan = müşterinin en az bir cümle söylediği çağrı. Kaçan = karşılamada kapatılan + yarıda kalan. Dönüşüm = (sipariş + rezervasyon) ÷ konuşulan.',
            [['Tarih', 't', 10], ['Gün', 'm', 10], ['Çağrı', 's', 7, 'top bar'], ['Konuşulan', 's', 9, 'top'], ['Kaçan', 's', 7, 'top esik:3'], ['Aktarılan', 's', 9, 'top'], ['Sipariş', 's', 7, 'top'], ['Rezervasyon', 's', 10, 'top'], ['Dönüşüm %', 'y', 10], ['Ort. Süre (dk)', 's', 11], ['Ciro', 'tl', 12, 'top']], $sat, $bos);
    }
    function _snrNiyetSat(array $l)
    {
        $g = []; foreach ($l as $x) if ($x['nU'] > 0) $g[$x['niyet']][] = $x; $n = max(1, array_sum(array_map('count', $g))); $o = [];
        foreach ($g as $k => $xs) { $s = _snrOzet($xs); $o[] = [_snrNiyetAd($k), $s['n'], round($s['n'] / $n * 100, 1), $s['cozuldu'], $s['aktar'], $s['kacan'], $s['siparis'], $s['rezervasyon'], $s['cozum_or']]; }
        usort($o, fn ($a, $b) => $b[1] <=> $a[1]);
        return $o;
    }
    function _snrSayfaNiyet(array $sat, $bos)
    {
        return _srSayfa('Arama Nedenleri', 'Neden, müşterinin cümlelerindeki anahtar kelimelerden çıkarılır (sipariş / rezervasyon alınan çağrıda o neden). Yalnız konuşulan çağrılar. Çözüm % = (sipariş + rezervasyon + bilgi) ÷ çağrı.',
            [['Neden', 'm', 24], ['Çağrı', 's', 8, 'top bar'], ['Pay %', 'y', 8], ['Çözüldü', 's', 8, 'top'], ['Aktarıldı', 's', 9, 'top'], ['Yarıda Kaldı', 's', 10, 'top'], ['Sipariş', 's', 7, 'top'], ['Rezervasyon', 's', 10, 'top'], ['Çözüm %', 'y', 9]], $sat, $bos);
    }
    function _snrSonucSat(array $l)
    {
        $g = []; foreach ($l as $x) $g[$x['sonuc']][] = $x; $n = max(1, count($l)); $o = [];
        foreach (['siparis', 'rezervasyon', 'bilgi', 'aktar', 'kacan', 'karsilama', 'devam'] as $k) if (!empty($g[$k])) { $sn = array_filter(array_column($g[$k], 'sn'), fn ($v) => $v !== null); $o[] = [_snrSonucAd($k), count($g[$k]), round(count($g[$k]) / $n * 100, 1), $sn ? round(array_sum($sn) / count($sn) / 60, 2) : null, round(array_sum(array_column($g[$k], 'nU')) / count($g[$k]), 1)]; }
        return $o;
    }
    function _snrSayfaSonuc(array $sat, $bos)
    {
        return _srSayfa('Sonuçlar', 'Tur = müşterinin konuşma sayısı (cümle).', [['Sonuç', 'm', 24], ['Çağrı', 's', 8, 'top bar'], ['Pay %', 'y', 8], ['Ort. Süre (dk)', 's', 11], ['Ort. Tur', 's', 8]], $sat, $bos);
    }
    function _snrCozulemeyen($sid, $lim = 300)
    {
        if (!_srTablo('santral_cozulemeyen')) return [];
        try { return DB::table('santral_cozulemeyen')->where('sube_id', $sid)->orderByDesc('adet')->limit($lim)->get(['ham', 'soru_norm', 'adet', 'son_tarih', 'created_at'])->map(fn ($r) => ['soru' => (string) ($r->ham ?: $r->soru_norm), 'adet' => (int) $r->adet, 'son' => substr((string) ($r->son_tarih ?: $r->created_at), 0, 10), 'ilk' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
}

// ---------- Raporlar ----------
if (!function_exists('_snrRapor')) {
    function _snrRapor($anahtar, $sid, array $dn)
    {
        _snrEnsure();
        $d = _snrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $l = _snrOturum($sid, $bas, $bit); $s = _snrOzet($l); $kon = array_values(array_filter($l, fn ($x) => $x['nU'] > 0));
        $bosC = !$d['cagri'] ? _snrBos('cagri_yok', $dn) : _snrBos('cagri', $dn);
        $bosK = !$d['cagri'] ? _snrBos('cagri_yok', $dn) : (!$l ? _snrBos('cagri', $dn) : _snrBos('konusma', $dn));
        $blokSonuc = fn ($l, $renk) => _srBlok('Çağrı sonuçları', 'pie', $renk, [['Sonuç', 'm'], ['Çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], _snrSonucSat($l)));
        $blokNiyet = fn ($l, $renk) => _srBlok('Arama nedenleri', 'bar', $renk, [['Neden', 'm'], ['Çağrı', 's'], ['Çözüldü', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[3]], array_slice(_snrNiyetSat($l), 0, 10)));
        $saatBlok = function ($l, $renk) { $h = []; foreach ($l as $x) { $k = sprintf('%02d:00', $x['h']); $h[$k]['n'] = ($h[$k]['n'] ?? 0) + 1; $h[$k]['k'] = ($h[$k]['k'] ?? 0) + (in_array($x['sonuc'], ['kacan', 'karsilama'], true) ? 1 : 0); } ksort($h); return _srBlok('Saatlere göre çağrı', 'col', $renk, [['Saat', 'm'], ['Çağrı', 's'], ['Kaçan', 's']], array_map(fn ($k, $v) => [$k, $v['n'], $v['k']], array_keys($h), array_values($h))); };

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $ga = _snrGeriArama($sid, $bas, $bit); $bek = 0; if (_srTablo('santral_geri_arama')) { try { $bek = (int) DB::table('santral_geri_arama')->where('sube_id', $sid)->whereIn('durum', ['bekliyor', 'araniyor'])->count(); } catch (\Throwable $e) {} }
                $cz = array_values(array_filter(_snrCozulemeyen($sid), fn ($x) => $x['son'] >= $bas && $x['son'] <= $bit));
                return [
                    $pano([_srKpi('Gelen çağrı', $s['n'], 'sayi', '7C3AED'), _srKpi('Konuşulan', $s['konusulan'], 'sayi', '6D28D9'), _srKpi('Kaçan / yarıda', $s['kayip'], 'sayi', 'DC2626'), _srKpi('Personele aktarılan', $s['aktar'], 'sayi', 'D97706'),
                        _srKpi('Sipariş', $s['siparis'], 'sayi', 'B45309'), _srKpi('Rezervasyon', $s['rezervasyon'], 'sayi', '92400E'), $kpiY('Dönüşüm', $s['donusum'], '059669'), _srKpi('Ciro', $s['ciro'], 'tl', '047857'),
                        _srKpi('Ortalama süre (dk)', $s['ort_dk'] ?? '—', $s['ort_dk'] === null ? 'metin' : 'sayi', '2563EB'), _srKpi('İlk kez arayan', $s['yeni'], 'sayi', '4F46E5'), _srKpi('Geri arama sırası', $bek, 'sayi', 'B91C1C'), _srKpi('Yeni çözülemeyen soru', count($cz), 'sayi', 'BE185D')],
                        [$saatBlok($l, '7C3AED'), $blokSonuc($l, '6D28D9')], !$l ? $bosC : null),
                    _snrSayfaListe('Çağrılar', 'Seçili günün tüm AI Santral çağrıları. Telefonlar maskelidir (KVKK).', $l, $bosC),
                    _snrSayfaListe('Kaçan & Aktarılan', 'Karşılamada kapatılan, yarıda kalan ve personele aktarılan çağrılar — geri dönüş listesi.', array_values(array_filter($l, fn ($x) => in_array($x['sonuc'], ['kacan', 'karsilama', 'aktar'], true))), !$l ? $bosC : _snrBos('kacan', $dn)),
                    _srSayfa('Geri Arama', 'Yarıda kalan çağrılar için otomatik geri arama sırası.', [['Saat', 'm', 7], ['Telefon', 'm', 15], ['Durum', 'm', 24], ['Deneme', 's', 7], ['Son Deneme', 'm', 12], ['Kurtarıldı mı', 'm', 30], ['Ciro', 'tl', 11, 'top']],
                        array_map(fn ($x) => [$x['saat'], _snrMaske($x['tel']), _snrGeriDurumAd($x['durum']), $x['deneme'], $x['son'], $x['kurtarildi'] ?: '—', $x['tl'] ?: null], $ga), !$d['geri_arama'] ? _snrBos('geri_yok', $dn) : _snrBos('geri', $dn)),
                    _srSayfa('Çözülemeyen Sorular', 'Bu gün AI\'nin cevaplayamayıp aktardığı sorular — AI Santral > Eğitim\'den hazır cevap ekleyin.', [['Soru', 'm', 50], ['Toplam Soruldu', 's', 12, 'top bar'], ['Son', 't', 11]], array_map(fn ($x) => [$x['soru'], $x['adet'], $x['son']], $cz), 'Bu gün yeni çözülemeyen soru yok.'),
                ];
            case 'haftalik':
            case 'aylik':
                $lo = _snrOturum($sid, $dn['obas'], $dn['obit']); $so = _snrOzet($lo); $gt = _snrGunTablo($l, $bas, $bit);
                $kpi = [_srKpi('Gelen çağrı', $s['n'], 'sayi', '7C3AED'), $kpiDeg('Önceki döneme göre', _srYuzde($s['n'], $so['n']), '6D28D9'), _srKpi('Konuşulan', $s['konusulan'], 'sayi', '5B21B6'),
                    $kpiY('AI çözüm oranı', $s['cozum_or'], '059669'), $kpiY('Aktarma oranı', $s['aktar_or'], 'D97706'), $kpiY('Kaçan oranı', $s['kayip_or'], 'DC2626'), $kpiY('Dönüşüm', $s['donusum'], '047857'), $kpiY('Önceki dönem dönüşüm', $so['donusum'], '065F46'),
                    _srKpi('Sipariş / rezervasyon', $s['siparis'] . ' / ' . $s['rezervasyon'], 'metin', 'B45309'), _srKpi('Ciro', $s['ciro'], 'tl', '059669'), $kpiDeg('Ciro, önceki döneme göre', _srYuzde($s['ciro'], $so['ciro']), '0F766E'),
                    _srKpi('Konuşma dakikası', $s['dk'], 'sayi', '2563EB'), _srKpi('İlk kez arayan', $s['yeni'], 'sayi', '4F46E5')];
                $bloklar = [_srBlok('Günlere göre çağrı ve dönüşen', 'col', '7C3AED', [['Gün', 'm'], ['Çağrı', 's'], ['Sipariş + rez.', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[6] + $x[7]], $gt)), $blokSonuc($l, '6D28D9'), $blokNiyet($l, 'DB2777')];
                $sayfalar = [_snrSayfaSonuc(_snrSonucSat($l), $bosC), _snrSayfaNiyet(_snrNiyetSat($l), $bosK), _snrSayfaListe('Aktarılan & Kaçan', 'Personele aktarılan ve yarıda kalan çağrılar.', array_values(array_filter($l, fn ($x) => in_array($x['sonuc'], ['kacan', 'aktar'], true))), !$l ? $bosC : _snrBos('kacan', $dn))];
                if ($anahtar === 'aylik') {
                    $tk = collect($l)->filter(fn ($x) => $x['tel'])->groupBy('tel')->filter(fn ($g) => $g->count() >= 2);
                    $cz = _snrCozulemeyen($sid, 30); $ay = _snrAyar($sid); $log = _snrCevapLoglari($sid, $bas, $bit); $aiN = count(array_filter($log, fn ($x) => !_snrUcretsizMi($x['kaynak'])));
                    $mal = ($ay['dk'] > 0 || $ay['ai'] > 0) ? round($s['dk'] * $ay['dk'] + $aiN * $ay['ai'], 2) : null;
                    $kpi = array_merge($kpi, [_srKpi('Tekrar arayan numara', $tk->count(), 'sayi', '4338CA'), _srKpi('Çözülemeyen soru (toplam)', count(_snrCozulemeyen($sid)), 'sayi', 'BE185D'), $kpiTl('Tahmini maliyet', $mal, '0891B2'), _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B')]);
                    $sayfalar[] = _srSayfa('Tekrar Arayanlar', 'Aynı numaradan dönemde 2+ çağrı.', [['Telefon', 'm', 15], ['Çağrı', 's', 7, 'top bar'], ['Sipariş', 's', 7], ['Rezervasyon', 's', 10], ['Aktarılan', 's', 9], ['Kaçan', 's', 7], ['Ciro', 'tl', 12, 'top']],
                        $tk->map(fn ($g, $t) => [_snrMaske($t), $g->count(), $g->where('sonuc', 'siparis')->count(), $g->where('sonuc', 'rezervasyon')->count(), $g->where('sonuc', 'aktar')->count(), $g->whereIn('sonuc', ['kacan', 'karsilama'])->count(), round($g->sum('sp_tl') + $g->sum('rez_tl'), 2) ?: null])->sortByDesc(1)->values()->all(), _snrBos('tekrar', $dn));
                    $sayfalar[] = _srSayfa('Eğitim İhtiyacı', 'En çok sorulup AI\'nin cevaplayamadığı sorular (tüm zamanlar, ilk 30). AI Santral > Eğitim\'den kalıp ekleyince bir daha aktarılmaz.', [['Soru', 'm', 50], ['Soruldu', 's', 9, 'top bar'], ['İlk', 't', 11], ['Son', 't', 11]], array_map(fn ($x) => [$x['soru'], $x['adet'], $x['ilk'], $x['son']], $cz), _snrBos('cozulemeyen', $dn));
                } else $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                return array_merge([$pano($kpi, $bloklar, !$l ? $bosC : null), _snrSayfaGun($gt, $bosC)], $sayfalar);

            // ---------------- ÇAĞRI HACMİ ----------------
            case 'hacim':
                $lo = _snrOturum($sid, $dn['obas'], $dn['obit']); $so = _snrOzet($lo); $gt = _snrGunTablo($l, $bas, $bit);
                $gs = []; foreach (_snrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gs[$n] = ($gs[$n] ?? 0) + 1; } $gn = []; foreach ($l as $x) $gn[$x['gun']] = ($gn[$x['gun']] ?? 0) + 1;
                $gsat = []; for ($n = 1; $n <= 7; $n++) if (!empty($gs[$n])) $gsat[] = [_snrGunAd($n), $gn[$n] ?? 0, round(($gn[$n] ?? 0) / $gs[$n], 1)];
                return [
                    $pano([_srKpi('Gelen çağrı', $s['n'], 'sayi', '2563EB'), _srKpi('Önceki dönem', $so['n'], 'sayi', '1E40AF'), $kpiDeg('Değişim', _srYuzde($s['n'], $so['n']), '1D4ED8'), _srKpi('Günlük ortalama', round($s['n'] / max(1, $dn['gun']), 1), 'sayi', '3B82F6'),
                        _srKpi('Konuşulan', $s['konusulan'], 'sayi', '1E3A8A'), _srKpi('Geri arama çağrısı', $s['geri'], 'sayi', '60A5FA'), _srKpi('Ses kayıtlı çağrı', count(array_filter($l, fn ($x) => $x['ses'] > 0)), 'sayi', '64748B')],
                        [_srBlok('Günlere göre çağrı', 'col', '2563EB', [['Gün', 'm'], ['Çağrı', 's'], ['Konuşulan', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[3]], $gt)),
                         _srBlok('Haftanın günlerine göre ortalama çağrı', 'col', '1D4ED8', [['Gün', 'm'], ['Ort. çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[2], ''], $gsat)), $blokSonuc($l, '6D28D9')], !$l ? $bosC : null),
                    _snrSayfaGun($gt, $bosC),
                    _srSayfa('Haftanın Günleri', 'Ortalama = toplam çağrı ÷ dönemdeki o günün sayısı.', [['Gün', 'm', 12], ['Çağrı', 's', 9, 'top bar'], ['Ort. / Gün', 's', 10]], $gsat, $bosC),
                    _snrSayfaListe('Tüm Çağrılar', '', $l, $bosC),
                ];
            case 'isi':
                $gs = []; foreach (_snrGunler($bas, $bit) as $t) { $n = (int) date('N', strtotime($t)); $gs[$n] = ($gs[$n] ?? 0) + 1; }
                $c = []; $k = []; $hs = []; foreach ($l as $x) { $c[$x['gun']][$x['h']] = ($c[$x['gun']][$x['h']] ?? 0) + 1; if (in_array($x['sonuc'], ['kacan', 'karsilama', 'aktar'], true)) $k[$x['gun']][$x['h']] = ($k[$x['gun']][$x['h']] ?? 0) + 1; $hs[$x['h']] = 1; }
                ksort($hs); $hs = array_keys($hs);
                $mat = function ($f) use ($hs, $gs) { $o = []; for ($n = 1; $n <= 7; $n++) { if (empty($gs[$n])) continue; $o[] = array_merge([_snrGunAd($n)], array_map(fn ($h) => $f($n, $h, $gs[$n]), $hs)); } return $o; };
                $kol = array_merge([['Gün', 'm', 11]], array_map(fn ($h) => [sprintf('%02d', $h), 's', 6, 'mat'], $hs));
                $dil = []; foreach ($c as $n => $r) foreach ($r as $h => $v) $dil[] = [_snrGunAd($n), sprintf('%02d:00', $h), round($v / $gs[$n], 1), $v, $k[$n][$h] ?? 0, round(($k[$n][$h] ?? 0) / $v * 100, 1)];
                usort($dil, fn ($a, $b) => $b[3] <=> $a[3]);
                return [
                    $pano([_srKpi('En yoğun dilim', $dil ? $dil[0][0] . ' ' . $dil[0][1] : '—', 'metin', '1D4ED8'), _srKpi('O dilimde çağrı', $dil[0][3] ?? 0, 'sayi', '2563EB'),
                        _srKpi('En çok kaçan / aktarılan dilim', ($q = collect($dil)->sortByDesc(4)->first()) && $q[4] > 0 ? $q[0] . ' ' . $q[1] : '—', 'metin', 'DC2626')], [], !$l ? $bosC : null),
                    ['_oto' => []] + _srSayfa('Ortalama Çağrı', 'Gün × saat: o dilimde ortalama kaç çağrı geliyor. Koyu = yoğun.', $kol, $mat(fn ($n, $h, $g) => round(($c[$n][$h] ?? 0) / $g, 1)), $bosC),
                    ['_oto' => []] + _srSayfa('Kaçan + Aktarılan', 'Gün × saat: AI\'nin tek başına bitiremediği çağrı sayısı (toplam) — bu saatlerde personel hazır olmalı.', $kol, $mat(fn ($n, $h, $g) => $k[$n][$h] ?? 0), $bosC),
                    ['grafiksiz' => true] + _srSayfa('Dilimler', '', [['Gün', 'm', 11], ['Saat', 'm', 7], ['Ort. Çağrı', 's', 9], ['Toplam Çağrı', 's', 11, 'bar'], ['Kaçan + Aktarılan', 's', 13], ['Oran %', 'y', 8, 'esik:30']], $dil, $bosC),
                ];
            case 'sure':
                $gr = ['30 sn altı' => [0, 30], '30–60 sn' => [30, 60], '1–2 dk' => [60, 120], '2–4 dk' => [120, 240], '4–8 dk' => [240, 480], '8 dk +' => [480, 99999]];
                $sat = []; $sl = array_values(array_filter($kon, fn ($x) => $x['sn'] !== null));
                foreach ($gr as $ad => [$a, $b]) { $xs = array_filter($sl, fn ($x) => $x['sn'] >= $a && $x['sn'] < $b); if (!$xs) continue; $o = _snrOzet($xs); $sat[] = [$ad, count($xs), round(count($xs) / max(1, count($sl)) * 100, 1), $o['siparis'] + $o['rezervasyon'], $o['aktar'], $o['donusum']]; }
                usort($sl, fn ($a, $b) => $b['sn'] <=> $a['sn']); $tah = count(array_filter($sl, fn ($x) => $x['tahmini']));
                return [
                    $pano([_srKpi('Ortalama süre (dk)', $s['ort_dk'] ?? 'Ölçüm yok', $s['ort_dk'] === null ? 'metin' : 'sayi', '1E40AF'), _srKpi('Toplam konuşma (dk)', $s['dk'], 'sayi', '1D4ED8'), _srKpi('Ortalama tur', $s['ort_tur'] ?? 0, 'sayi', '2563EB'),
                        _srKpi('En uzun (dk)', $sl ? round($sl[0]['sn'] / 60, 1) : 0, 'sayi', '3B82F6'), _srKpi('Süresi ses kaydından tahmini', $tah, 'sayi', '64748B')],
                        [_srBlok('Süre dağılımı', 'col', '1E40AF', [['Süre', 'm'], ['Çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)),
                         _srBlok('Sonuca göre ortalama süre (dk)', 'bar', '2563EB', [['Sonuç', 'm'], ['Ort. dk', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], array_filter(_snrSonucSat($kon), fn ($x) => $x[3] !== null)))], !$kon ? $bosK : (!$sl ? 'Süre ölçülemedi: çağrılar kapanış kaydı almamış (köprü "bitir" çağrısı gelmemiş) ve ses kaydı yok.' : null)),
                    _srSayfa('Süre Grupları', 'Süre = çağrı başlangıcı → son konuşma / kapanış. Kapanmamış çağrıda ses kaydı boyutundan tahmin (8 kHz, 16 bit).', [['Süre', 'm', 12], ['Çağrı', 's', 8, 'top bar'], ['Pay %', 'y', 8], ['Sipariş + Rez.', 's', 12, 'top'], ['Aktarılan', 's', 9, 'top'], ['Dönüşüm %', 'y', 10]], $sat, $bosK),
                    _snrSayfaSonuc(_snrSonucSat($kon), $bosK),
                    _snrSayfaListe('En Uzun Görüşmeler', 'İlk 25.', array_slice($sl, 0, 25), $bosK),
                ];

            // ---------------- KAÇAN & GERİ ARAMA ----------------
            case 'kacan':
                $kc = array_values(array_filter($l, fn ($x) => in_array($x['sonuc'], ['kacan', 'karsilama'], true))); $ga = []; foreach (_snrGeriArama($sid, $bas, $bit) as $x) if ($x['tel']) $ga[$x['tel']] = $x;
                $sonra = _snrOturum($sid, $bas, date('Y-m-d', strtotime($bit . ' +2 day')));
                $sat = array_map(function ($x) use ($ga, $sonra) {
                    $t = null; foreach ($sonra as $y) if ($x['tel'] && $y['tel'] === $x['tel'] && $y['ts'] > $x['ts'] && $y['ts'] - $x['ts'] <= 172800) { $t = $y; break; }
                    return [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrSonucAd($x['sonuc']), _snrNiyetAd($x['niyet']), $x['nU'], $x['tel'] && isset($ga[$x['tel']]) ? _snrGeriDurumAd($ga[$x['tel']]['durum']) : ($x['nU'] ? 'Sıraya girmedi' : '—'), $t ? _snrSonucAd($t['sonuc']) . ' (' . date('d.m H:i', $t['ts']) . ')' : 'Tekrar aramadı', mb_substr($x['sonCumle'], 0, 140)];
                }, $kc);
                $donen = count(array_filter($sat, fn ($x) => $x[7] !== 'Tekrar aramadı'));
                return [
                    $pano([_srKpi('Kaçan / yarıda', count($kc), 'sayi', 'DC2626'), $kpiY('Tüm çağrılara oranı', $s['kayip_or'], 'B91C1C'), _srKpi('Karşılamada kapattı', $s['karsilama'], 'sayi', '991B1B'), _srKpi('Konuşup yarıda kaldı', $s['kacan'], 'sayi', '7F1D1D'),
                        _srKpi('48 saat içinde tekrar ulaşan', $donen, 'sayi', '059669'), _srKpi('Gizli numara', count(array_filter($kc, fn ($x) => !$x['tel'])), 'sayi', '64748B')],
                        [$saatBlok($kc, 'DC2626'), _srBlok('Yarıda kalanların nedeni', 'pie', 'B91C1C', [['Neden', 'm'], ['Çağrı', 's'], ['', 'm']], collect($kc)->filter(fn ($x) => $x['nU'] > 0)->groupBy(fn ($x) => _snrNiyetAd($x['niyet']))->map(fn ($g, $a) => [$a, $g->count(), ''])->values()->all())], !$l ? $bosC : (!$kc ? _snrBos('kacan', $dn) : null)),
                    _srSayfa('Kaçan & Yarıda Kalan Çağrılar', 'Karşılamada kapattı = müşteri hiç konuşmadan kapattı (yanlış arama ya da bekleme). Yarıda kaldı = konuşma başladı ama sipariş / rezervasyon / bilgi tamamlanmadı. "Sonra" sütunu: aynı numara 48 saat içinde tekrar aradı mı.',
                        [['Tarih', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Durum', 'm', 18], ['Neden', 'm', 16], ['Tur', 's', 5], ['Geri Arama', 'm', 22], ['Sonra', 'm', 26], ['Son Cümle', 'm', 34]], $sat, !$l ? $bosC : _snrBos('kacan', $dn)),
                ];
            case 'geri_arama':
                $ga = _snrGeriArama($sid, $bas, $bit); $g = []; foreach ($ga as $x) $g[$x['durum']] = ($g[$x['durum']] ?? 0) + 1; $kur = array_values(array_filter($ga, fn ($x) => $x['kurtarildi'] !== ''));
                $bos = !$d['geri_arama'] ? _snrBos('geri_yok', $dn) : (!$ga ? _snrBos('geri', $dn) : null);
                return [
                    $pano([_srKpi('Geri arama sırasına giren', count($ga), 'sayi', 'B91C1C'), _srKpi('Ulaşılan', $g['basarili'] ?? 0, 'sayi', '059669'), _srKpi('Cevapsız', $g['cevapsiz'] ?? 0, 'sayi', 'DC2626'), _srKpi('Müşteri kendisi aradı', $g['vazgecildi'] ?? 0, 'sayi', '2563EB'),
                        _srKpi('Kurtarılan (sipariş / rez.)', count($kur), 'sayi', '047857'), _srKpi('Kurtarılan ciro', round(array_sum(array_column($kur, 'tl')), 2), 'tl', '059669'), $kpiY('Kurtarma oranı', $ga ? round(count($kur) / count($ga) * 100, 1) : null, '0F766E')],
                        [_srBlok('Geri arama durumları', 'pie', 'B91C1C', [['Durum', 'm'], ['Kayıt', 's'], ['', 'm']], array_map(fn ($k, $v) => [_snrGeriDurumAd($k), $v, ''], array_keys($g), array_values($g)))], $bos),
                    _srSayfa('Geri Arama Kayıtları', 'Kurtarıldı = aynı numara 48 saat içinde (geri aramada ya da kendisi arayarak) sipariş / rezervasyon verdi.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Durum', 'm', 24], ['Deneme', 's', 7, 'top'], ['Son Deneme', 'm', 12], ['Kurtarıldı', 'm', 30], ['Ciro', 'tl', 11, 'top bar']],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrGeriDurumAd($x['durum']), $x['deneme'], $x['son'], $x['kurtarildi'] ?: '—', $x['tl'] ?: null], $ga), $bos),
                ];
            case 'callerid':
                $cl = _snrCallerId($sid, $bas, $bit); $gel = array_values(array_filter($cl, fn ($x) => $x['yon'] === 'gelen'));
                $g = []; foreach ($gel as $x) { $t = date('Y-m-d', $x['ts']); $g[$t]['n'] = ($g[$t]['n'] ?? 0) + 1; $g[$t][$x['sonuc']] = ($g[$t][$x['sonuc']] ?? 0) + 1; }
                $gsat = array_map(fn ($t) => [$t, _snrGunAd(date('N', strtotime($t))), $g[$t]['n'] ?? 0, $g[$t]['cevaplandi'] ?? 0, $g[$t]['siparis'] ?? 0, $g[$t]['kacan'] ?? 0], _snrGunler($bas, $bit));
                $hat = collect($gel)->groupBy(fn ($x) => $x['hat'] ?: 'Belirtilmemiş')->map(fn ($xs, $h) => [$h, $xs->count(), $xs->where('sonuc', 'kacan')->count(), $xs->where('sonuc', 'siparis')->count()])->values()->all();
                $kac = count(array_filter($gel, fn ($x) => $x['sonuc'] === 'kacan')); $bos = !$d['callerid'] ? _snrBos('callerid_yok', $dn) : (!$cl ? _snrBos('callerid', $dn) : null);
                return [
                    $pano([_srKpi('Gelen (sabit hat)', count($gel), 'sayi', '991B1B'), _srKpi('Kaçan', $kac, 'sayi', 'DC2626'), $kpiY('Kaçan oranı', $gel ? round($kac / count($gel) * 100, 1) : null, 'B91C1C'), _srKpi('Siparişe dönen', count(array_filter($gel, fn ($x) => $x['sonuc'] === 'siparis')), 'sayi', '059669'),
                        _srKpi('Kayıtlı müşteri', count(array_filter($gel, fn ($x) => $x['musteri'])), 'sayi', '4F46E5'), _srKpi('Giden arama', count($cl) - count($gel), 'sayi', '64748B')],
                        [$saatBlok(array_map(fn ($x) => ['h' => (int) date('G', $x['ts']), 'sonuc' => $x['sonuc']], $gel), '991B1B'), _srBlok('Hatlara göre', 'bar', 'B91C1C', [['Hat', 'm'], ['Çağrı', 's'], ['Kaçan', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[2]], $hat))], $bos),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün', 'Kaynak: Çağrılar / Çağrı Ekranı (sabit hat Caller ID). AI Santral çağrılarından ayrıdır.', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Gelen', 's', 8, 'top bar'], ['Cevaplandı', 's', 10, 'top'], ['Sipariş', 's', 8, 'top'], ['Kaçan', 's', 8, 'top esik:1']], $gsat, $bos),
                    _srSayfa('Hatlar', '', [['Hat', 'm', 20], ['Çağrı', 's', 9, 'top bar'], ['Kaçan', 's', 8, 'top'], ['Sipariş', 's', 8, 'top']], $hat, $bos),
                    _srSayfa('Kaçan Sabit Hat Aramaları', 'Geri aranmalı.', [['Tarih', 'm', 15], ['Telefon', 'm', 15], ['Hat', 'm', 14], ['Müşteri', 'm', 14]], array_map(fn ($x) => [date('d.m.Y H:i', $x['ts']), _snrMaske($x['tel']), $x['hat'] ?: '—', $x['musteri'] ? 'Kayıtlı' : 'Yeni'], array_values(array_filter($gel, fn ($x) => $x['sonuc'] === 'kacan'))), $bos ?? 'Kaçan sabit hat araması yok.'),
                ];

            // ---------------- AI ÇÖZÜM ----------------
            case 'cozum':
                $ns = _snrNiyetSat($l);
                return [
                    $pano([_srKpi('Konuşulan çağrı', $s['konusulan'], 'sayi', '059669'), $kpiY('AI çözüm oranı', $s['cozum_or'], '047857'), _srKpi('AI çözdü', $s['cozuldu'], 'sayi', '0F766E'), _srKpi('Personele aktarıldı', $s['aktar'], 'sayi', 'D97706'),
                        $kpiY('Aktarma oranı', $s['aktar_or'], 'B45309'), _srKpi('Konuşup yarıda kaldı', $s['kacan'], 'sayi', 'DC2626'), _srKpi('Sadece bilgi verildi', $s['bilgi'], 'sayi', '2563EB')],
                        [_srBlok('Konuşulan çağrıların sonucu', 'pie', '059669', [['Sonuç', 'm'], ['Çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], _snrSonucSat($kon))),
                         _srBlok('Nedene göre çözüm oranı (%)', 'bar', '047857', [['Neden', 'm'], ['Çözüm %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[8], ''], array_filter($ns, fn ($x) => $x[8] !== null)))], !$kon ? $bosK : null),
                    _snrSayfaSonuc(_snrSonucSat($kon), $bosK),
                    _snrSayfaNiyet($ns, $bosK),
                ];
            case 'aktarma':
                $ak = array_values(array_filter($l, fn ($x) => $x['sonuc'] === 'aktar')); $ayar = null; if (_srTablo('santral_ayarlari')) { try { $ayar = DB::table('santral_ayarlari')->where('sube_id', $sid)->first(); } catch (\Throwable $e) {} }
                $hed = $ayar && $ayar->hedefler ? (json_decode((string) $ayar->hedefler, true) ?: []) : []; $cz = count(_snrCozulemeyen($sid));
                $ayarSat = [['Aktarma', $ayar ? ((int) $ayar->aktarma_aktif ? 'Açık' : 'Kapalı — AI aktaramaz, müşteri geri aranır') : 'Ayar kaydı yok (varsayılan: açık)'], ['Strateji', $ayar ? (($ayar->strateji ?? 'hepsi') === 'sirali' ? 'Sıralı (biri açmazsa sonraki)' : 'Hepsi aynı anda çalar') : '—'],
                    ['Aktarma hedefi', $hed ? count($hed) . ' hedef (' . implode(', ', array_map(fn ($h) => ($h['tip'] ?? '') === 'dis' ? 'dış hat' : 'dahili ' . ($h['numara'] ?? ''), $hed)) . ')' : ($ayar && $ayar->numara ? '1 hedef (' . $ayar->numara . ')' : 'Tanımlı değil')],
                    ['Zil süresi', $ayar ? (int) $ayar->zil_sure . ' sn' : '—'], ['Teslimat bölgesi kuralı', $ayar && trim((string) ($ayar->teslimat_bolge ?? '')) !== '' ? 'Tanımlı: ' . mb_substr(trim((string) $ayar->teslimat_bolge), 0, 120) : 'Tanımlı değil — AI bölge dışı adresleri ayırt edemez'],
                    ['Çözülemeyen soru havuzu', $cz . ' soru (Eğitim ekranında hazır cevaba çevrilebilir)']];
                return [
                    $pano([_srKpi('Aktarılan çağrı', count($ak), 'sayi', '047857'), $kpiY('Konuşulanlara oranı', $s['aktar_or'], 'D97706'), _srKpi('Aktarma ses kaydı olan', count(array_filter($ak, fn ($x) => $x['ses_aktarma'])), 'sayi', '0F766E'),
                        _srKpi('Aktarmadan önce ort. tur', $ak ? round(array_sum(array_column($ak, 'nU')) / count($ak), 1) : 0, 'sayi', '115E59'), _srKpi('Aktarma ayarı', $ayar ? ((int) $ayar->aktarma_aktif ? 'Açık' : 'Kapalı') : 'Varsayılan', 'metin', '64748B')],
                        [$saatBlok($ak, '047857'), _srBlok('Aktarılanların nedeni', 'pie', 'D97706', [['Neden', 'm'], ['Çağrı', 's'], ['', 'm']], collect($ak)->groupBy(fn ($x) => _snrNiyetAd($x['niyet']))->map(fn ($g, $a) => [$a, $g->count(), ''])->values()->all())], !$l ? $bosC : (!$ak ? _snrBos('aktar', $dn) : null)),
                    _srSayfa('Aktarılan Çağrılar', 'Son cümle = AI aktarmaya karar vermeden önce müşterinin söylediği. Aktarma kaydı = personelle yapılan görüşmenin ses kaydı var.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Arayan', 'm', 16], ['Neden', 'm', 16], ['Tur', 's', 5], ['Aktarma Kaydı', 'm', 11], ['Son Cümle', 'm', 44]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrArayanAd($x['arayan']), _snrNiyetAd($x['niyet']), $x['nU'], $x['ses_aktarma'] ? 'Var' : '—', mb_substr($x['sonCumle'], 0, 180)], $ak), !$l ? $bosC : _snrBos('aktar', $dn)),
                    ['grafiksiz' => true] + _srSayfa('Aktarma & Teslimat Ayarları', 'AI Santral > Ayarlar ekranındaki güncel değerler.', [['Ayar', 'm', 26], ['Değer', 'm', 70]], $ayarSat, null),
                ];
            case 'cevap_kaynak':
                $lg = _snrCevapLoglari($sid, $bas, $bit); $g = []; foreach ($lg as $x) { $k = _snrKaynakAd((string) $x['kaynak']); $g[$k]['n'] = ($g[$k]['n'] ?? 0) + 1; if ($x['ms'] !== null) $g[$k]['ms'][] = (int) $x['ms']; $g[$k]['bedava'] = _snrUcretsizMi((string) $x['kaynak']); }
                uasort($g, fn ($a, $b) => $b['n'] <=> $a['n']); $n = max(1, count($lg));
                $sat = array_map(fn ($k, $v) => [$k, $v['n'], round($v['n'] / $n * 100, 1), $v['bedava'] ? 'Ücretsiz' : 'Yapay zekâ / hata', !empty($v['ms']) ? round(array_sum($v['ms']) / count($v['ms'])) : null, !empty($v['ms']) ? max($v['ms']) : null], array_keys($g), array_values($g));
                $bed = count(array_filter($lg, fn ($x) => _snrUcretsizMi((string) $x['kaynak']))); $ms = array_filter(array_column($lg, 'ms'), fn ($v) => $v !== null);
                $bos = !$lg ? (!$d['cevap_log'] ? _snrBos('cevap_log', $dn) : 'Seçili dönemde cevap kaydı yok.') : null;
                return [
                    $pano([_srKpi('Cevap (müşteri cümlesi)', count($lg), 'sayi', '0F766E'), $kpiY('Ücretsiz cevap oranı', $lg ? round($bed / count($lg) * 100, 1) : null, '059669'), _srKpi('Yapay zekâ çağrısı', count($lg) - $bed, 'sayi', '7C3AED'),
                        _srKpi('Ortalama cevap süresi (ms)', $ms ? (int) round(array_sum($ms) / count($ms)) : 'Ölçüm yok', $ms ? 'sayi' : 'metin', '2563EB'), _srKpi('AI hatası / anahtar yok', count(array_filter($lg, fn ($x) => str_contains(_snrKaynakAd((string) $x['kaynak']), '→'))), 'sayi', 'DC2626')],
                        [_srBlok('Cevap kaynakları', 'pie', '0F766E', [['Kaynak', 'm'], ['Cevap', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat))], $bos),
                    _srSayfa('Cevap Kaynakları', 'Ücretsiz = hazır kalıp, öğrenilmiş cevap ve filtreler (dil modeli çağrılmaz). Kalıp / öğrenilen oranı arttıkça maliyet düşer ve cevap hızlanır.', [['Kaynak', 'm', 30], ['Cevap', 's', 9, 'top bar'], ['Pay %', 'y', 8], ['Tür', 'm', 16], ['Ort. Süre (ms)', 's', 12], ['En Uzun (ms)', 's', 11]], $sat, $bos),
                ];

            // ---------------- SİPARİŞ & REZERVASYON ----------------
            case 'donusum':
                $niyetli = array_values(array_filter($kon, fn ($x) => in_array($x['niyet'], ['siparis', 'rezervasyon'], true) || in_array($x['sonuc'], ['siparis', 'rezervasyon'], true)));
                $kayit = array_values(array_filter($l, fn ($x) => in_array($x['sonuc'], ['siparis', 'rezervasyon'], true)));
                $ger = array_values(array_filter($kayit, fn ($x) => ($x['sonuc'] === 'siparis' && $x['adisyon_id'] && $x['sp_durum'] !== 'iptal') || ($x['sonuc'] === 'rezervasyon' && $x['rez_durum'] === 'geldi')));
                $asama = [['Gelen çağrı', $s['n']], ['Konuşma başladı', count($kon)], ['Sipariş / rezervasyon niyeti', count($niyetli)], ['Kaydedildi', count($kayit)], ['Gerçekleşti (adisyon açıldı / misafir geldi)', count($ger)]];
                $sat = []; foreach ($asama as $i => [$ad, $v]) $sat[] = [$ad, $v, $i ? ($asama[$i - 1][1] > 0 ? round($v / $asama[$i - 1][1] * 100, 1) : null) : 100.0, $s['n'] > 0 ? round($v / $s['n'] * 100, 1) : null];
                $kacNiyet = array_values(array_filter($niyetli, fn ($x) => !in_array($x['sonuc'], ['siparis', 'rezervasyon'], true)));
                return [
                    $pano([_srKpi('Gelen çağrı', $s['n'], 'sayi', 'D97706'), _srKpi('Niyetli', count($niyetli), 'sayi', 'B45309'), _srKpi('Kaydedilen', count($kayit), 'sayi', '92400E'), _srKpi('Gerçekleşen', count($ger), 'sayi', '059669'),
                        $kpiY('Niyet → kayıt', count($niyetli) ? round(count($kayit) / count($niyetli) * 100, 1) : null, '047857'), _srKpi('Niyetli ama kaçan', count($kacNiyet), 'sayi', 'DC2626'), _srKpi('Ciro', $s['ciro'], 'tl', '059669')],
                        [_srBlok('Dönüşüm hunisi', 'bar', 'D97706', [['Aşama', 'm'], ['Çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat))], !$l ? $bosC : null),
                    ['grafiksiz' => true] + _srSayfa('Huni', 'Niyet = müşteri sipariş ya da rezervasyon cümlesi kurdu. Gerçekleşti = sipariş adisyona düştü (iptal değil) ya da rezervasyonlu misafir geldi.', [['Aşama', 'm', 40], ['Çağrı', 's', 9, 'bar'], ['Önceki Aşamaya %', 'y', 14], ['Başa Göre %', 'y', 11]], $sat, $bosC),
                    _snrSayfaListe('Niyetli Ama Kaydedilmeyenler', 'Sipariş / rezervasyon istediği halde kayıt oluşmayan çağrılar — kayıp satış adayları.', $kacNiyet, !$l ? $bosC : 'Niyetli olup kaydedilmeyen çağrı yok.'),
                ];
            case 'siparis':
                $sp = array_values(array_filter($l, fn ($x) => $x['sonuc'] === 'siparis')); $ok = array_values(array_filter($sp, fn ($x) => $x['adisyon_id'] && $x['sp_durum'] !== 'iptal'));
                $tum = 0; if (_srKolon('adisyonlar', 'platform')) { try { $tum = (int) DB::table('adisyonlar')->where('sube_id', $sid)->where('platform', 'telefon')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->count(); } catch (\Throwable $e) {} }
                $od = collect($ok)->groupBy(fn ($x) => ['nakit' => 'Kapıda nakit', 'kart_kapida' => 'Kapıda kart', 'online' => 'Online'][$x['sp_odeme']] ?? ($x['sp_odeme'] ?: 'Belirtilmemiş'))->map(fn ($g, $a) => [$a, $g->count(), round($g->sum('sp_tl'), 2)])->values()->all();
                $bos = !$d['siparis'] && !$sp ? (!$d['cagri'] ? _snrBos('cagri_yok', $dn) : _snrBos('siparis', $dn)) : (!$sp ? _snrBos('siparis', $dn) : null);
                return [
                    $pano([_srKpi('AI\'nin aldığı sipariş', count($sp), 'sayi', 'B45309'), _srKpi('Adisyona düşen', count($ok), 'sayi', '92400E'), _srKpi('Sipariş tutarı', $s['sp_tl'], 'tl', '059669'), $kpiTl('Ortalama sepet', $ok ? round($s['sp_tl'] / count($ok), 2) : null, '047857'),
                        _srKpi('İptal edilen', count(array_filter($sp, fn ($x) => $x['sp_durum'] === 'iptal')), 'sayi', 'DC2626'), _srKpi('Adisyona düşmeyen', count(array_filter($sp, fn ($x) => !$x['adisyon_id'])), 'sayi', 'D97706'),
                        $kpiY('Tüm telefon siparişlerinde AI payı', $tum ? min(100, round(count($ok) / $tum * 100, 1)) : null, '7C3AED')],
                        [$saatBlok($sp, 'B45309'), _srBlok('Ödeme şekli', 'pie', '059669', [['Ödeme', 'm'], ['Sipariş', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $od))], $bos),
                    _srSayfa('Siparişler', 'Adisyona düşmeyen = AI siparişi aldı ama menüde ürün eşleşmediği ya da şube bilgisi eksik olduğu için adisyon açılamadı (ham veri çağrı kaydında).', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Arayan', 'm', 16], ['Tutar', 'tl', 11, 'top bar'], ['Ödeme', 'm', 12], ['Adisyon Durumu', 'm', 14], ['Teslimat', 'm', 12], ['Tur', 's', 5], ['Süre (dk)', 's', 8]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrArayanAd($x['arayan']), $x['sp_tl'] ?: null, ['nakit' => 'Kapıda nakit', 'kart_kapida' => 'Kapıda kart', 'online' => 'Online'][$x['sp_odeme']] ?? ($x['sp_odeme'] ?: '—'), $x['adisyon_id'] ? ($x['sp_durum'] ?: '—') : 'Adisyon açılamadı', $x['sp_teslimat'] ?: '—', $x['nU'], $x['sn'] !== null ? round($x['sn'] / 60, 1) : null], $sp), $bos),
                    _srSayfa('Ödeme Şekli', '', [['Ödeme', 'm', 18], ['Sipariş', 's', 9, 'top'], ['Tutar', 'tl', 13, 'top bar']], $od, $bos),
                ];
            case 'rezervasyon':
                $rz = array_values(array_filter($l, fn ($x) => $x['sonuc'] === 'rezervasyon' || $x['rez_id'])); $gel = count(array_filter($rz, fn ($x) => $x['rez_durum'] === 'geldi')); $gm = count(array_filter($rz, fn ($x) => $x['rez_durum'] === 'gelmedi'));
                $diger = null; if (_srTablo('rezervasyonlar')) { try { $q = DB::table('rezervasyonlar')->where('sube_id', $sid)->whereBetween('tarih', [$bas, $bit])->where(function ($w) { $w->whereNull('not')->orWhere('not', 'not like', 'AI Santral%'); }); $dg = (clone $q)->where('durum', 'geldi')->count(); $dm = (clone $q)->where('durum', 'gelmedi')->count(); $diger = $dg + $dm ? round($dm / ($dg + $dm) * 100, 1) : null; } catch (\Throwable $e) {} }
                $bos = !$rz ? (!$d['cagri'] ? _snrBos('cagri_yok', $dn) : _snrBos('rez', $dn)) : null; $ns = $gel + $gm ? round($gm / ($gel + $gm) * 100, 1) : null;
                return [
                    $pano([_srKpi('AI\'nin aldığı rezervasyon', count($rz), 'sayi', '92400E'), _srKpi('Kişi', array_sum(array_column($rz, 'rez_kisi')), 'sayi', 'B45309'), _srKpi('Geldi / gelmedi', $gel . ' / ' . $gm, 'metin', 'D97706'),
                        $kpiY('Santral no-show', $ns, 'DC2626'), $kpiY('Diğer kanallar no-show', $diger, '64748B'), _srKpi('Oturtulan masaların cirosu', $s['rez_tl'], 'tl', '059669'),
                        _srKpi('Onay bekleyen', count(array_filter($rz, fn ($x) => $x['rez_durum'] === 'bekliyor')), 'sayi', 'D97706')],
                        [_srBlok('Rezervasyon durumları', 'pie', '92400E', [['Durum', 'm'], ['Rezervasyon', 's'], ['', 'm']], collect($rz)->groupBy(fn ($x) => ['bekliyor' => 'Onay bekliyor', 'onaylandi' => 'Onaylandı', 'geldi' => 'Geldi', 'gelmedi' => 'Gelmedi', 'iptal' => 'İptal'][$x['rez_durum']] ?? 'Kayıt bulunamadı')->map(fn ($g, $a) => [$a, $g->count(), ''])->values()->all())], $bos),
                    _srSayfa('Santral Rezervasyonları', 'Çağrı tarihi dönemde olan; rezervasyon tarihi ayrıdır. AI Santral rezervasyonları "Onay bekliyor" düşer, Rezervasyonlar ekranından onaylanır.', [['Çağrı', 't', 10], ['Saat', 'm', 6], ['Telefon', 'm', 15], ['Arayan', 'm', 16], ['Rez. Tarihi', 't', 11], ['Kişi', 's', 6, 'top'], ['Durum', 'm', 14], ['Ciro', 'tl', 11, 'top bar'], ['Tur', 's', 5]],
                        array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrMaske($x['tel']), _snrArayanAd($x['arayan']), $x['rez_tarih'] ?: null, $x['rez_kisi'], ['bekliyor' => 'Onay bekliyor', 'onaylandi' => 'Onaylandı', 'geldi' => 'Geldi', 'gelmedi' => 'Gelmedi', 'iptal' => 'İptal'][$x['rez_durum']] ?? 'Kayıt bulunamadı', $x['rez_tl'] ?: null, $x['nU']], $rz), $bos),
                ];

            // ---------------- NİYET & EĞİTİM ----------------
            case 'niyet':
                $ns = _snrNiyetSat($l); $orn = [];
                foreach (collect($kon)->groupBy('niyet') as $k => $g) foreach ($g->sortByDesc('nU')->take(4) as $x) $orn[] = [_snrNiyetAd($k), _snrSonucAd($x['sonuc']), mb_substr($x['ilk'], 0, 200), $x['nU']];
                return [
                    $pano([_srKpi('Konuşulan çağrı', count($kon), 'sayi', 'DB2777'), _srKpi('En sık neden', $ns[0][0] ?? '—', 'metin', 'BE185D'), _srKpi('Şikâyet', count(array_filter($kon, fn ($x) => $x['niyet'] === 'sikayet')), 'sayi', 'DC2626'),
                        _srKpi('Sipariş takibi', count(array_filter($kon, fn ($x) => $x['niyet'] === 'takip')), 'sayi', 'D97706'), _srKpi('Belirsiz', count(array_filter($kon, fn ($x) => $x['niyet'] === 'diger')), 'sayi', '64748B')],
                        [$blokNiyet($l, 'DB2777')], !$kon ? $bosK : null),
                    _snrSayfaNiyet($ns, $bosK),
                    _srSayfa('Örnek Cümleler', 'Her nedenden en uzun 4 görüşmenin ilk cümlesi (sınıflamayı doğrulamak ve kalıp yazmak için).', [['Neden', 'm', 20], ['Sonuç', 'm', 18], ['Müşterinin İlk Cümlesi', 'm', 60], ['Tur', 's', 5]], $orn, $bosK),
                ];
            case 'cozulemeyen':
                $cz = _snrCozulemeyen($sid); $donem = array_values(array_filter($cz, fn ($x) => $x['son'] >= $bas && $x['son'] <= $bit));
                $ak = array_values(array_filter($l, fn ($x) => $x['sonuc'] === 'aktar' && $x['sonCumle'] !== ''));
                $bos = !$cz ? _snrBos('cozulemeyen', $dn) : null;
                return [
                    $pano([_srKpi('Çözülemeyen soru (toplam)', count($cz), 'sayi', 'BE185D'), _srKpi('Dönemde soruldu', count($donem), 'sayi', 'DB2777'), _srKpi('Toplam soruluş', array_sum(array_column($cz, 'adet')), 'sayi', '9D174D'),
                        _srKpi('En çok sorulan', $cz ? mb_substr($cz[0]['soru'], 0, 40) : '—', 'metin', '831843'), _srKpi('Dönemde aktarılan çağrı', count($ak), 'sayi', 'D97706')],
                        [_srBlok('En çok sorulan cevapsız sorular', 'bar', 'BE185D', [['Soru', 'm'], ['Soruldu', 's'], ['', 'm']], array_map(fn ($x) => [mb_substr($x['soru'], 0, 50), $x['adet'], ''], array_slice($cz, 0, 10)))], $bos),
                    _srSayfa('Çözülemeyen Sorular', 'AI aktardığında müşterinin sorusu buraya yazılır (tüm zamanlar, soruluş sayısına göre). Dönemde = son soruluş seçili dönemde. AI Santral > Eğitim > Çözülemeyenler\'den tek tıkla hazır cevaba çevrilir.', [['Soru', 'm', 56], ['Soruldu', 's', 9, 'top bar'], ['İlk', 't', 11], ['Son', 't', 11], ['Dönemde', 'm', 9]],
                        array_map(fn ($x) => [$x['soru'], $x['adet'], $x['ilk'], $x['son'], $x['son'] >= $bas && $x['son'] <= $bit ? 'Evet' : ''], $cz), $bos),
                    _srSayfa('Aktarılan Çağrıların Son Cümlesi', 'Seçili dönem; AI\'nin aktarmadan önce duyduğu son cümle.', [['Tarih', 't', 10], ['Saat', 'm', 6], ['Neden', 'm', 18], ['Son Cümle', 'm', 60]], array_map(fn ($x) => [$x['tarih'], $x['saat'], _snrNiyetAd($x['niyet']), mb_substr($x['sonCumle'], 0, 220)], $ak), !$l ? $bosC : _snrBos('aktar', $dn)),
                ];
            case 'egitim':
                $kl = []; $og = [];
                if (_srTablo('santral_kalip')) { try { $kl = DB::table('santral_kalip')->where('sube_id', $sid)->orderByDesc('id')->get(['aktif', 'tetikleyiciler', 'cevap', 'kategori', 'created_at'])->all(); } catch (\Throwable $e) {} }
                if (_srTablo('santral_ai_ogrenilen')) { try { $og = DB::table('santral_ai_ogrenilen')->where('sube_id', $sid)->orderByDesc('kullanim')->limit(200)->get(['soru_key', 'cevap', 'kullanim', 'created_at'])->all(); } catch (\Throwable $e) {} }
                $kat = collect($kl)->groupBy(fn ($k) => $k->kategori ?: 'genel')->map(fn ($g, $a) => [$a, $g->count(), $g->where('aktif', 1)->count()])->values()->all();
                $lg = _snrCevapLoglari($sid, $bas, $bit); $kz = count(array_filter($lg, fn ($x) => $x['kaynak'] === 'kalip')); $oz = count(array_filter($lg, fn ($x) => $x['kaynak'] === 'ogrenilen'));
                $bos = !$kl && !$og ? _snrBos('egitim', $dn) : null;
                return [
                    $pano([_srKpi('Hazır cevap (kalıp)', count($kl), 'sayi', '9D174D'), _srKpi('Aktif kalıp', count(array_filter($kl, fn ($k) => (int) $k->aktif)), 'sayi', 'BE185D'), _srKpi('Öğrenilmiş cevap', count($og), 'sayi', 'DB2777'),
                        _srKpi('Öğrenilmiş cevap kullanımı', array_sum(array_map(fn ($o) => (int) $o->kullanim, $og)), 'sayi', '831843'), _srKpi('Çözülemeyen soru', count(_snrCozulemeyen($sid)), 'sayi', 'DC2626'),
                        _srKpi('Dönemde kalıpla / öğrenilenle cevap', $lg ? $kz . ' / ' . $oz : 'Ölçüm yok (Faz 2)', 'metin', '0F766E')],
                        [_srBlok('Kategorilere göre kalıp', 'pie', '9D174D', [['Kategori', 'm'], ['Kalıp', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $kat))], $bos),
                    _srSayfa('Kalıp Kategorileri', '', [['Kategori', 'm', 22], ['Kalıp', 's', 8, 'top bar'], ['Aktif', 's', 8, 'top']], $kat, $bos ?? 'Hazır cevap (kalıp) yok.'),
                    _srSayfa('Hazır Cevaplar (Kalıp)', 'Müşteri tetikleyici kelimelerden birini söylerse AI bu cevabı ücretsiz verir.', [['Tetikleyiciler', 'm', 34], ['Cevap', 'm', 50], ['Kategori', 'm', 12], ['Durum', 'm', 8], ['Eklendi', 't', 11]],
                        array_map(fn ($k) => [mb_substr((string) $k->tetikleyiciler, 0, 160), mb_substr((string) $k->cevap, 0, 220), $k->kategori ?: 'genel', (int) $k->aktif ? 'Aktif' : 'Pasif', substr((string) $k->created_at, 0, 10)], $kl), $bos ?? 'Hazır cevap (kalıp) yok.'),
                    _srSayfa('Öğrenilmiş Cevaplar', 'AI\'nin bir kez yapay zekâyla cevaplayıp önbelleğe aldığı bilgi soruları; tekrar sorulunca ücretsiz cevaplanır (en çok kullanılan 200).', [['Soru (normalize)', 'm', 40], ['Cevap', 'm', 50], ['Kullanım', 's', 9, 'top bar'], ['Öğrenildi', 't', 11]],
                        array_map(fn ($o) => [(string) $o->soru_key, mb_substr((string) $o->cevap, 0, 220), (int) $o->kullanim, substr((string) $o->created_at, 0, 10)], $og), $bos ?? 'Öğrenilmiş cevap yok.'),
                ];

            // ---------------- ARAYANLAR ----------------
            case 'tanima':
                $g = []; foreach ($l as $x) $g[$x['arayan']][] = $x;
                $sat = []; foreach (['kayitli', 'tekrar', 'yeni', 'gizli'] as $k) if (!empty($g[$k])) { $o = _snrOzet($g[$k]); $sat[] = [_snrArayanAd($k), $o['n'], round($o['n'] / max(1, $s['n']) * 100, 1), $o['kayip'], $o['aktar'], $o['siparis'], $o['rezervasyon'], $o['donusum'], $o['ciro'] ?: null]; }
                return [
                    $pano([_srKpi('Kayıtlı müşteri', $s['kayitli'], 'sayi', '4F46E5'), _srKpi('İlk kez arayan', $s['yeni'], 'sayi', '6366F1'), _srKpi('Daha önce aramış (kartı yok)', $s['tekrar'], 'sayi', '4338CA'), _srKpi('Gizli numara', $s['gizli'], 'sayi', '64748B'),
                        $kpiY('Tanınan payı', $s['n'] ? round($s['kayitli'] / $s['n'] * 100, 1) : null, '3730A3')],
                        [_srBlok('Arayan tipi', 'pie', '4F46E5', [['Tip', 'm'], ['Çağrı', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)), _srBlok('Arayan tipine göre dönüşüm (%)', 'bar', '059669', [['Tip', 'm'], ['Dönüşüm %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[7], ''], array_filter($sat, fn ($x) => $x[7] !== null)))], !$l ? $bosC : null),
                    _srSayfa('Arayan Tipleri', 'Kayıtlı = numaraya ait müşteri kartı çağrıdan önce vardı (AI ismiyle karşılar). Daha önce aramış = son 1 yılda bu numaradan çağrı var ama müşteri kartı yok.', [['Arayan', 'm', 26], ['Çağrı', 's', 8, 'top bar'], ['Pay %', 'y', 8], ['Kaçan', 's', 7, 'top'], ['Aktarılan', 's', 9, 'top'], ['Sipariş', 's', 7, 'top'], ['Rezervasyon', 's', 10, 'top'], ['Dönüşüm %', 'y', 10], ['Ciro', 'tl', 12, 'top']], $sat, $bosC),
                ];
            case 'tekrar':
                $tk = collect($l)->filter(fn ($x) => $x['tel'])->groupBy('tel')->filter(fn ($g) => $g->count() >= 2);
                $sat = $tk->map(function ($g, $t) { $ts = $g->pluck('ts')->sort()->values()->all(); $hz = 0; for ($i = 1; $i < count($ts); $i++) if ($ts[$i] - $ts[$i - 1] <= 7200) $hz++;
                    return [_snrMaske($t), _snrArayanAd($g->last()['arayan']), $g->count(), $hz, $g->where('sonuc', 'siparis')->count(), $g->where('sonuc', 'rezervasyon')->count(), $g->where('sonuc', 'aktar')->count(), $g->whereIn('sonuc', ['kacan', 'karsilama'])->count(), date('d.m H:i', $g->first()['ts']), date('d.m H:i', $g->last()['ts']), round($g->sum('sp_tl') + $g->sum('rez_tl'), 2) ?: null, _snrNiyetAd($g->countBy('niyet')->sortDesc()->keys()->first())]; })->sortByDesc(2)->values()->all();
                $hizli = array_sum(array_column($sat, 3));
                return [
                    $pano([_srKpi('Tekrar arayan numara', count($sat), 'sayi', '4338CA'), _srKpi('Bu numaralardan çağrı', array_sum(array_column($sat, 2)), 'sayi', '4F46E5'), _srKpi('2 saat içinde tekrar arama', $hizli, 'sayi', 'DC2626'),
                        $kpiY('Tekrar arayanların çağrı payı', $s['n'] ? round(array_sum(array_column($sat, 2)) / $s['n'] * 100, 1) : null, '3730A3'), _srKpi('Tekrar arayanların cirosu', round(array_sum(array_column($sat, 10)), 2), 'tl', '059669')],
                        [_srBlok('En çok arayan numaralar', 'bar', '4338CA', [['Telefon', 'm'], ['Çağrı', 's'], ['2 saat içinde', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[3]], array_slice($sat, 0, 10)))], !$l ? $bosC : (!$sat ? _snrBos('tekrar', $dn) : null)),
                    _srSayfa('Tekrar Arayanlar', '2 saat içinde tekrar = aynı sorun için yeniden aramış olabilir (ilk görüşme çözmedi). En sık neden = bu numaranın çağrılarındaki en sık arama nedeni.', [['Telefon', 'm', 15], ['Arayan', 'm', 18], ['Çağrı', 's', 7, 'top bar'], ['2 Saat İçinde', 's', 11, 'top esik:1'], ['Sipariş', 's', 7], ['Rezervasyon', 's', 10], ['Aktarılan', 's', 9], ['Kaçan', 's', 7], ['İlk', 'm', 11], ['Son', 'm', 11], ['Ciro', 'tl', 11, 'top'], ['En Sık Neden', 'm', 18]], $sat, !$l ? $bosC : _snrBos('tekrar', $dn)),
                ];

            // ---------------- SÜRE & MALİYET ----------------
            case 'maliyet':
                $ay = _snrAyar($sid); $lg = _snrCevapLoglari($sid, $bas, $bit); $aiN = count(array_filter($lg, fn ($x) => !_snrUcretsizMi((string) $x['kaynak'])));
                $hat = round($s['dk'] * $ay['dk'], 2); $ai = round($aiN * $ay['ai'], 2); $top = $hat + $ai; $don = $s['siparis'] + $s['rezervasyon']; $ayarYok = $ay['dk'] <= 0 && $ay['ai'] <= 0;
                $sesN = 0; $sesB = 0; if (_srTablo('santral_ses_kayit')) { try { $q = DB::table('santral_ses_kayit')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59']); $sesN = (int) (clone $q)->count(); $sesB = (int) (clone $q)->sum('boyut'); } catch (\Throwable $e) {} }
                $gt = _snrGunTablo($l, $bas, $bit); $gdk = []; foreach ($l as $x) if ($x['sn'] !== null && $x['nU'] > 0) $gdk[$x['tarih']] = ($gdk[$x['tarih']] ?? 0) + $x['sn'] / 60;
                $sat = [['Konuşma dakikası', $s['dk'], $ay['dk'] > 0 ? $ay['dk'] : null, $ay['dk'] > 0 ? $hat : null], ['Yapay zekâ cevabı (dil modeli)', $lg ? $aiN : null, $ay['ai'] > 0 ? $ay['ai'] : null, $ay['ai'] > 0 && $lg ? $ai : null], ['Toplam', null, null, $ayarYok ? null : round($top, 2)]];
                return [
                    $pano([_srKpi('Konuşma dakikası', $s['dk'], 'sayi', '0891B2'), _srKpi('Yapay zekâ cevabı', $lg ? $aiN : 'Ölçüm yok (Faz 2)', $lg ? 'sayi' : 'metin', '7C3AED'), _srKpi('Ses kaydı', $sesN . ' kayıt · ' . number_format($sesB / 1048576, 1, ',', '.') . ' MB', 'metin', '64748B'),
                        $kpiTl('Tahmini maliyet', $ayarYok ? null : round($top, 2), '0E7490'), $kpiTl('Sipariş / rezervasyon başına', !$ayarYok && $don ? round($top / $don, 2) : null, '155E75'), $kpiY('Ciroya oranı', !$ayarYok && $s['ciro'] > 0 ? round($top / $s['ciro'] * 100, 2) : null, '164E63'),
                        _srKpi('Dakika ücreti', $ay['dk'] > 0 ? number_format($ay['dk'], 2, ',', '.') . ' ₺/dk' : 'Girilmemiş', 'metin', '64748B')],
                        [_srBlok('Günlere göre konuşma dakikası', 'col', '0891B2', [['Gün', 'm'], ['Dakika', 's'], ['', 'm']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), round($gdk[$x[0]] ?? 0, 1), ''], $gt))], !$l ? $bosC : ($ayarYok ? _snrBos('maliyet', $dn) : null)),
                    _srSayfa('Maliyet Hesabı', 'Maliyet = konuşma dakikası × hat dakika ücreti + yapay zekâ cevabı × cevap başı maliyet (rapor ayarlarından). Hazır kalıp ve öğrenilmiş cevaplar ücretsizdir. Tahmindir; fatura yerine geçmez.', [['Kalem', 'm', 32], ['Miktar', 's', 11], ['Birim Ücret', 'tl', 12], ['Tutar', 'tl', 13, 'top']], $sat, $bosC),
                    ['grafiksiz' => true] + _srSayfa('Gün Gün', '', [['Tarih', 't', 10], ['Gün', 'm', 10], ['Çağrı', 's', 7, 'top'], ['Dakika', 's', 8, 'top bar'], ['Sipariş + Rez.', 's', 12, 'top'], ['Ciro', 'tl', 12, 'top']], array_map(fn ($x) => [$x[0], $x[1], $x[2], round($gdk[$x[0]] ?? 0, 1), $x[6] + $x[7], $x[10]], $gt), $bosC),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_snrAiBaglam')) {
    function _snrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $l = _snrOturum($sid, $bas, $bit); $s = _snrOzet($l); $so = _snrOzet(_snrOturum($sid, $dn['obas'], $dn['obit'])); $ga = _snrGeriArama($sid, $bas, $bit);
        $lg = _snrCevapLoglari($sid, $bas, $bit); $ay = _snrAyar($sid); $saat = collect($l)->groupBy(fn ($x) => sprintf('%02d', $x['h']))->map->count()->sortDesc()->take(5)->all();
        $kz = collect($l)->filter(fn ($x) => in_array($x['sonuc'], ['kacan', 'karsilama', 'aktar'], true))->groupBy(fn ($x) => sprintf('%02d', $x['h']))->map->count()->sortDesc()->take(5)->all();
        unset($s['sn'], $s['sn_n'], $s['tur']);
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _snrVeriDurumu($sid),
            'cagri' => $s, 'onceki' => ['cagri' => $so['n'], 'konusulan' => $so['konusulan'], 'donusum_yuzde' => $so['donusum'], 'aktar_yuzde' => $so['aktar_or'], 'ciro' => $so['ciro']],
            'yogun_saatler' => $saat, 'kacan_aktarilan_saatler' => $kz,
            'nedenler' => array_map(fn ($x) => ['neden' => $x[0], 'cagri' => $x[1], 'cozuldu' => $x[3], 'aktarildi' => $x[4], 'cozum_yuzde' => $x[8]], _snrNiyetSat($l)),
            'geri_arama' => ['kayit' => count($ga), 'kurtarilan' => count(array_filter($ga, fn ($x) => $x['kurtarildi'] !== '')), 'kurtarilan_ciro' => round(array_sum(array_column($ga, 'tl')), 2)],
            'cozulemeyen_ilk10' => array_map(fn ($x) => ['soru' => mb_substr($x['soru'], 0, 120), 'adet' => $x['adet']], _snrCozulemeyen($sid, 10)),
            'cevap_kaynagi' => $lg ? collect($lg)->groupBy(fn ($x) => _snrKaynakAd((string) $x['kaynak']))->map->count()->all() : 'ölçüm yok',
            'tekrar_arayan_numara' => collect($l)->filter(fn ($x) => $x['tel'])->groupBy('tel')->filter(fn ($g) => $g->count() >= 2)->count(),
            'maliyet_ayari' => ['dakika_ucreti' => $ay['dk'], 'ai_cevap_ucreti' => $ay['ai']]];
    }
    function _snrAiNabiz($sid, array $dn)
    {
        $b = _snrAiBaglam($sid, $dn); $c = $b['cagri']; $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺'; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $dg = _srYuzde($c['n'], $b['onceki']['cagri']);
        return [
            ['renk' => '#7C3AED', 'kat' => 'hacim', 'ikon' => 'call', 'etiket' => 'Gelen çağrı', 'deger' => (string) $c['n'], 'alt' => $c['konusulan'] . ' konuşulan' . ($dg !== null ? ' · %' . number_format(abs($dg), 1, ',', '.') . ($dg >= 0 ? ' arttı' : ' düştü') : ''), 'sinif' => $dg !== null ? ($dg >= 0 ? 'yesil' : 'turuncu') : '', 'ok' => $dg !== null ? ($dg >= 0 ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#059669', 'kat' => 'cozum', 'ikon' => 'smart_toy', 'etiket' => 'AI çözüm oranı', 'deger' => $y($c['cozum_or']), 'alt' => $c['aktar'] . ' çağrı personele aktarıldı', 'sinif' => $c['cozum_or'] !== null ? ($c['cozum_or'] >= 70 ? 'yesil' : 'turuncu') : ''],
            ['renk' => '#DC2626', 'kat' => 'cevap', 'ikon' => 'phone_missed', 'etiket' => 'Kaçan / yarıda', 'deger' => (string) $c['kayip'], 'alt' => $b['geri_arama']['kurtarilan'] . ' çağrı geri aramayla kurtarıldı', 'sinif' => $c['kayip_or'] !== null && $c['kayip_or'] > 20 ? 'kirmizi' : ($c['kayip'] ? 'turuncu' : 'yesil')],
            ['renk' => '#D97706', 'kat' => 'donusum', 'ikon' => 'filter_alt', 'etiket' => 'Dönüşüm', 'deger' => $y($c['donusum']), 'alt' => $c['siparis'] . ' sipariş · ' . $c['rezervasyon'] . ' rezervasyon', 'sinif' => ''],
            ['renk' => '#047857', 'kat' => 'donusum', 'ikon' => 'payments', 'etiket' => 'Santral cirosu', 'deger' => $tl($c['ciro']), 'alt' => 'Sipariş ' . $tl($c['sp_tl']) . ' · Rezervasyon ' . $tl($c['rez_tl']), 'sinif' => $c['ciro'] > 0 ? 'yesil' : ''],
            ['renk' => '#BE185D', 'kat' => 'niyet', 'ikon' => 'help', 'etiket' => 'Çözülemeyen soru', 'deger' => (string) count($b['cozulemeyen_ilk10']), 'alt' => $b['cozulemeyen_ilk10'] ? 'En çok: ' . mb_substr($b['cozulemeyen_ilk10'][0]['soru'], 0, 34) : 'Eğitim ihtiyacı görünmüyor', 'sinif' => $b['cozulemeyen_ilk10'] ? 'turuncu' : ''],
        ];
    }
}

// ---------- Ayar kartı (maliyet) ----------
if (!function_exists('_snrUst')) {
    function _snrUst($kat, $sid)
    {
        if (!in_array($kat, ['maliyet', 'donemsel', 'hacim'], true)) return '';
        $a = _snrAyar($sid); $e = fn ($x) => htmlspecialchars((string) $x, ENT_QUOTES); $inp = 'width:70px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:5px 7px;color:var(--ink)';
        return '<form method="POST" action="/santral-raporlar/ayar" style="display:flex;flex-wrap:wrap;gap:10px 16px;align-items:center;padding:11px 16px;border-radius:16px;background:var(--card);border:1px solid var(--line);font-size:12.5px;color:var(--sub2)">'
            . '<input type="hidden" name="_token" value="' . $e(csrf_token()) . '"><input type="hidden" name="geri" value="' . $e(request()->getRequestUri()) . '">'
            . '<span style="display:inline-flex;align-items:center;gap:5px;font-weight:900;color:var(--ink)"><span class="material-icons-outlined" style="font-size:18px;color:#7C3AED">tune</span>Maliyet ayarları</span>'
            . '<label style="display:inline-flex;align-items:center;gap:6px" title="Operatör / SIP hattının dakika ücreti (gelen çağrı ücretliyse) + ses (TTS / STT) dakika maliyeti.">Hat + ses dakika ücreti <input name="dk" value="' . $e(number_format($a['dk'], 2, ',', '')) . '" inputmode="decimal" style="' . $inp . '"> ₺/dk</label>'
            . '<label style="display:inline-flex;align-items:center;gap:6px" title="Yapay zekâ (dil modeli) her cevap ürettiğinde ortalama maliyet. Hazır kalıp ve öğrenilmiş cevaplar ücretsizdir.">Yapay zekâ cevap başı <input name="ai" value="' . $e(number_format($a['ai'], 2, ',', '')) . '" inputmode="decimal" style="' . $inp . '"> ₺</label>'
            . '<button type="submit" style="border:0;border-radius:9px;padding:7px 13px;font-weight:800;background:#7C3AED;color:#fff;cursor:pointer">Kaydet</button>'
            . '<span style="flex-basis:100%;font-size:11.5px;color:var(--sub)">0 bırakılırsa maliyet hesaplanmaz. Tahmindir; operatör ve yapay zekâ faturası yerine geçmez.</span></form>';
    }
}

// ---------- Modül kaydı ----------
_drKaydet('santral', [
    'ad' => 'Santral', 'baslik' => 'Santral Raporları', 'yol' => '/santral-raporlar', 'grup' => 'AI Santral', 'depKodlari' => [],
    'erisim' => fn ($p) => function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_santral'),
    'ensure' => '_snrEnsure', 'katalog' => '_snrKatalog', 'kategoriler' => '_snrKategoriler', 'veri' => '_snrVeriDurumu', 'rapor' => '_snrRapor',
    'ust' => fn ($kat, $sid) => _snrUst($kat, $sid),
    'bosTablolar' => ['santral_oturumlari', 'santral_geri_arama', 'santral_ses_kayit', 'santral_cozulemeyen', 'santral_kalip', 'santral_ai_ogrenilen', 'cagri_loglari', 'santral_cevap_log', 'santral_ayarlari'],
    'olcek' => ['kucuk' => ['donemsel', 'cevap', 'ai'], 'orta' => ['hacim', 'cozum', 'donusum', 'niyet']],
    'modAd' => ['cagri' => 'AI Santral çağrısı', 'konusma' => 'Konuşulan çağrı', 'siparis' => 'Telefon siparişi', 'rezervasyon' => 'Santral rezervasyonu', 'aktar' => 'Aktarma', 'geri_arama' => 'Geri arama', 'ses' => 'Ses kaydı',
        'cozulemeyen' => 'Çözülemeyen soru', 'kalip' => 'Hazır cevap (kalıp)', 'ogrenilen' => 'Öğrenilmiş cevap', 'ayar' => 'Santral ayarı', 'callerid' => 'Sabit hat Caller ID', 'cevap_log' => 'Cevap kaynağı kaydı'],
    'modLink' => ['cagri' => ['/santral-kayitlar', 'AI Santral > Çağrı Kayıtları'], 'konusma' => ['/santral-kayitlar', 'AI Santral > Çağrı Kayıtları'], 'siparis' => ['/santral-kayitlar', 'AI\'nin aldığı siparişler'],
        'rezervasyon' => ['/rezervasyon', 'Rezervasyonlar (AI Santral notlu)'], 'aktar' => ['/santral-ayar', 'AI Santral > Ayarlar (aktarma hedefi)'], 'geri_arama' => [null, 'Yarıda kalan çağrıda otomatik'], 'ses' => ['/santral-kayitlar', 'Çağrı kayıtlarında ses'],
        'cozulemeyen' => ['/santral-egitim', 'AI Santral > Eğitim'], 'kalip' => ['/santral-egitim', 'AI Santral > Eğitim > Kalıplar'], 'ogrenilen' => ['/santral-egitim', 'AI Santral > Eğitim > Öğrenilenler'], 'ayar' => ['/santral-ayar', 'AI Santral > Ayarlar'],
        'callerid' => ['/cagrilar', 'Çağrılar / Çağrı Ekranı'], 'cevap_log' => [null, 'Santral konuşma ucunda otomatik (Faz 2)']],
    'ai' => [
        'gerekli' => ['cagri', 'konusma', 'siparis', 'rezervasyon'],
        'hero' => ['Santralin dönemini 20 saniyede özetleyeyim.', 'Çağrı hacmi ve yoğun saatler, AI\'nin çözdüğü ve aktardığı çağrılar, kaçan ve geri aranan çağrılar, sipariş / rezervasyon dönüşümü ve cirosu, arama nedenleri, çözülemeyen sorular ve maliyeti okur.'],
        'sorular' => ['AI hangi konularda personele aktarıyor, nasıl azaltırım?', 'Hangi saatlerde çağrı kaçırıyorum?', 'Santral bana ne kadar ciro getirdi?', 'AI\'ye hangi soruları öğretmeliyim?', 'Tekrar arayan müşteriler neden tekrar arıyor?', 'Santralin maliyeti getirisine değiyor mu?'],
        'adimlar' => ['Çağrı kayıtları okunuyor…', 'Önceki dönemle karşılaştırılıyor…', 'Sonuçlar ve arama nedenleri sınıflanıyor…', 'Sipariş, rezervasyon ve ciro eşleştiriliyor…', 'Kaçan, geri arama ve çözülemeyen sorular inceleniyor…', 'Öneriler yazılıyor…'],
        'selam' => 'santral',
        'sys' => 'Sen bir restoranın telefonunu açan yapay zekâ santralini (AI çağrı merkezi) patron için analiz eden deneyimli bir çağrı merkezi ve müşteri deneyimi danışmanısın. '
            . 'Konuşulan çağrı = müşterinin en az bir cümle söylediği çağrı; çözüm oranı = (sipariş + rezervasyon + bilgi) ÷ konuşulan; aktarma = AI\'nin personele devrettiği; kaçan = karşılamada kapatılan + yarıda kalan. '
            . 'Arama nedenleri anahtar kelimeyle sınıflanır, kesin değildir. Eğitim önerisi verirken "çözülemeyen sorular" listesindeki somut soruları an; hazır cevap (kalıp) eklemeyi öner. Arayanların kimliği hakkında yorum yapma.',
        'baglam' => '_snrAiBaglam',
        'veriVar' => fn ($b) => $b['cagri']['n'] > 0 || $b['geri_arama']['kayit'] > 0,
        'nabiz' => '_snrAiNabiz',
    ],
]);

// Maliyet ayarı kaydı
Route::post('/santral-raporlar/ayar', function (Request $r) {
    if (!_drErisim('santral')) return redirect('/pos-giris');
    $sid = _webSubeId(); $f = fn ($x) => (string) max(0, min(1000, round((float) str_replace(',', '.', (string) $x), 4)));
    if (function_exists('_subeAyarYaz')) { _subeAyarYaz($sid, 'snr_dk_ucret', $f($r->input('dk', '0'))); _subeAyarYaz($sid, 'snr_ai_ucret', $f($r->input('ai', '0'))); }
    $geri = (string) $r->input('geri', '/santral-raporlar'); if (!str_starts_with($geri, '/santral-raporlar')) $geri = '/santral-raporlar';
    return redirect($geri)->with('ok', 'Santral rapor ayarları kaydedildi.');
});

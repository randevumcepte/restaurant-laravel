<?php
// =====================================================================
// CRM RAPORLAR & ANALİZLER — /crm-raporlar (çatı: routes/dep_raporlar.php, motor: satinalma_raporlar _sr*)
// Veri: musteriler (kayıt, puan, doğum tarihi, cüzdan), adisyonlar (müşteriye bağlı ödenmiş siparişler: yaşam döngüsü, RFM, CLV, kohort),
//       memnuniyet (QR "nasıldı?" puanı + şikayet çözümü), degerlendirmeler (sipariş değerlendirmesi), urun_puanlari (QR menü ürün puanı),
//       odul_kullanimlari + puan_odulleri (ödül), carkifelek_log (çark), cuzdan_hareketleri (cüzdan), musteri_kart_kullanim + app_banner (kart puanı),
//       iptal_indirim_loglari ("Ödül:" sebepli indirim/ikram = ödül maliyeti).
// Kampanya, kanal, indirim ve yeni müşteri kazanımı Pazarlama raporlarındadır (routes/dep_raporlar/pazarlama.php).
// Puan defteri ayrı tutulmadığı için kazanılan puan türetilir: her ödenmiş müşterili adisyon ⌊toplam/10⌋ + çark puanı + kampanya kartı puanı; harcanan = kullanılan ödül puanı.
// Faz 2: musteriler.dogum_tarihi mevcut _crmDogumKolon() ile garanti edilir (yeni tablo yok).
// =====================================================================

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_crrEnsure')) {
    function _crrEnsure()
    {
        if (cache()->get('crr_ensure_v1')) return;
        try { if (function_exists('_crmDogumKolon')) _crmDogumKolon(); } catch (\Throwable $e) {}
        cache()->put('crr_ensure_v1', 1, now()->addHours(12));
    }
}

// ---------- Veri katmanı ----------
if (!function_exists('_crrSiparis')) {
    function _crrGunAd($n) { return ['', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'][(int) $n] ?? ''; }
    function _crrGunler($bas, $bit) { $o = []; for ($t = strtotime($bas); $t <= strtotime($bit); $t += 86400) $o[] = date('Y-m-d', $t); return $o; }
    function _crrGunFark($a, $b) { return (int) round((strtotime($b) - strtotime($a)) / 86400); }
    function _crrTlf($t) { $d = preg_replace('/\D/', '', (string) $t); return strlen($d) >= 7 ? substr($d, 0, 4) . '***' . substr($d, -2) : ($d ?: '—'); }
    function _crrAyAd($ym) { [$y, $m] = explode('-', $ym); return ['', 'Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'][(int) $m] . ' ' . $y; }
    // Müşteriye bağlı ödenmiş siparişler (tüm zamanlar): mid => [[tarih, toplam], ...] (tarihe göre sıralı)
    function _crrSiparis($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srKolon('adisyonlar', 'musteri_id')) return $c[$sid] = [];
        try { $rows = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'odendi')->whereNotNull('musteri_id')->where('musteri_id', '>', 0)->whereNotNull('kapanis')->orderBy('kapanis')->get(['musteri_id', 'kapanis', 'toplam']); } catch (\Throwable $e) { $rows = []; }
        $o = []; foreach ($rows as $r) $o[(int) $r->musteri_id][] = [substr((string) $r->kapanis, 0, 10), (float) $r->toplam];
        return $c[$sid] = $o;
    }
    function _crrMusteriler($sid)
    {
        static $c = []; if (isset($c[$sid])) return $c[$sid];
        if (!_srTablo('musteriler')) return $c[$sid] = [];
        try { $q = DB::table('musteriler')->where('sube_id', $sid); if (_srKolon('musteriler', 'silindi_at')) $q->whereNull('silindi_at'); $rows = $q->get(); } catch (\Throwable $e) { $rows = []; }
        $o = [];
        foreach ($rows as $r) $o[(int) $r->id] = (object) ['id' => (int) $r->id, 'ad' => trim((string) ($r->ad ?? '') . ' ' . (string) ($r->soyad ?? '')), 'telefon' => (string) ($r->telefon ?? ''),
            'kayit' => substr((string) ($r->created_at ?? ''), 0, 10) ?: null, 'puan' => (int) ($r->puan ?? 0), 'harcama' => (float) ($r->toplam_harcama ?? 0), 'siparis' => (int) ($r->siparis_sayisi ?? 0),
            'dogum' => !empty($r->dogum_tarihi) ? substr((string) $r->dogum_tarihi, 0, 10) : null, 'cuzdan' => (float) ($r->cuzdan_bakiye ?? 0)];
        return $c[$sid] = $o;
    }
    function _crrAd($m, $id) { return ($m && $m->ad !== '') ? $m->ad : 'Müşteri #' . $id; }
    // Müşteri profili $ref gününe kadar: n, ciro, ilk, son, gün (son ziyaretten beri), ortalama aralık. Sipariş geçmişi yoksa kart alanları (eski kayıt).
    function _crrProfil($sid, $ref)
    {
        static $c = []; $key = "$sid|$ref"; if (isset($c[$key])) return $c[$key];
        $sp = _crrSiparis($sid); $o = [];
        foreach (_crrMusteriler($sid) as $id => $m) {
            if ($m->kayit && $m->kayit > $ref) continue;
            $l = array_values(array_filter($sp[$id] ?? [], fn ($x) => $x[0] <= $ref)); $n = count($l);
            $p = ['id' => $id, 'ad' => _crrAd($m, $id), 'tel' => $m->telefon, 'n' => $n, 'ciro' => round(array_sum(array_column($l, 1)), 2), 'ilk' => $l[0][0] ?? null, 'son' => $n ? $l[$n - 1][0] : null, 'gun' => null, 'aralik' => null, 'kartlik' => false];
            if (!$n && $m->siparis > 0) { $p['n'] = $m->siparis; $p['ciro'] = $m->harcama; $p['kartlik'] = true; }
            if ($p['son']) $p['gun'] = _crrGunFark($p['son'], $ref);
            if ($n >= 2) { $tar = array_values(array_unique(array_column($l, 0))); $p['aralik'] = count($tar) >= 2 ? round(_crrGunFark($tar[0], $tar[count($tar) - 1]) / (count($tar) - 1), 1) : null; }
            $o[$id] = $p;
        }
        return $c[$key] = $o;
    }
    // Yaşam döngüsü durumu (Müşteri 360 ile aynı eşikler: 30 / 60 / 120 gün)
    function _crrDurum(array $p)
    {
        if (!$p['n']) return 'Sipariş vermemiş';
        if ($p['gun'] === null) return 'Ziyaret tarihi yok';
        if ($p['n'] === 1 && $p['gun'] <= 30) return 'Yeni';
        if ($p['gun'] <= 30) return 'Aktif';
        if ($p['gun'] <= 60) return 'Risk altında';
        if ($p['gun'] <= 120) return 'Kayıp';
        return 'Uyuyan';
    }
    function _crrDurumlar() { return ['Yeni', 'Aktif', 'Risk altında', 'Kayıp', 'Uyuyan', 'Sipariş vermemiş', 'Ziyaret tarihi yok']; }
    // RFM: R son ziyaret günü, F sipariş sayısı (eşik), M ciro (beşte birlik dilim) → segment
    function _crrRfm(array $prof)
    {
        $l = array_filter($prof, fn ($p) => $p['n'] > 0 && $p['gun'] !== null); if (!$l) return [];
        $cirolar = array_column($l, 'ciro'); sort($cirolar); $q = fn ($v) => (function () use ($cirolar, $v) { $n = count($cirolar); $i = 0; while ($i < $n && $cirolar[$i] < $v) $i++; return min(5, 1 + (int) floor($i / max(1, $n) * 5)); })();
        $o = [];
        foreach ($l as $id => $p) {
            $r = $p['gun'] <= 14 ? 5 : ($p['gun'] <= 30 ? 4 : ($p['gun'] <= 60 ? 3 : ($p['gun'] <= 120 ? 2 : 1)));
            $f = $p['n'] >= 10 ? 5 : ($p['n'] >= 6 ? 4 : ($p['n'] >= 3 ? 3 : ($p['n'] === 2 ? 2 : 1))); $m = $q($p['ciro']);
            if ($r >= 4 && $f >= 4) $s = 'Şampiyonlar';
            elseif ($r >= 3 && $f >= 3) $s = 'Sadık müşteriler';
            elseif ($r >= 4 && $f === 1) $s = 'Yeni müşteriler';
            elseif ($r >= 4) $s = 'Potansiyel sadık';
            elseif ($r === 3) $s = 'İlgi bekleyen';
            elseif ($f >= 3) $s = $m >= 4 ? 'Kaybedilmemeli' : 'Risk altında';
            elseif ($r === 2) $s = 'Uykuya dalıyor';
            else $s = 'Kayıp';
            $o[$id] = ['r' => $r, 'f' => $f, 'm' => $m, 'seg' => $s];
        }
        return $o;
    }
    function _crrRfmAksiyon($s)
    {
        return ['Şampiyonlar' => 'Teşekkür, VIP ikram, yeni ürünü ilk onlara tattır; tavsiye iste.', 'Sadık müşteriler' => 'Sadakat puanı ve kişisel ikramla bağı güçlendir.',
            'Potansiyel sadık' => 'Sadakat programına davet et; 3. siparişe teşvik.', 'Yeni müşteriler' => 'Hoş geldin mesajı + 2. siparişe küçük teşvik.',
            'İlgi bekleyen' => 'Sınırlı süreli kişisel teklif ile ziyaret sıklığını artır.', 'Kaybedilmemeli' => 'Hemen kişisel arama / özel teklif: en değerli müşterilerin uzaklaşıyor.',
            'Risk altında' => 'Geri kazanım mesajı, "seni özledik" teklifi.', 'Uykuya dalıyor' => 'Hatırlatma ve yeni menü duyurusu.', 'Kayıp' => 'Düşük maliyetli geri kazanım denemesi; yanıt yoksa listeden çıkar.'][$s] ?? '';
    }
    function _crrSegSira() { return ['Şampiyonlar', 'Sadık müşteriler', 'Potansiyel sadık', 'Yeni müşteriler', 'İlgi bekleyen', 'Kaybedilmemeli', 'Risk altında', 'Uykuya dalıyor', 'Kayıp']; }
    // Dönemdeki müşterili siparişler: [mid, tarih, toplam]
    function _crrDonemSiparis($sid, $bas, $bit)
    {
        $o = []; foreach (_crrSiparis($sid) as $mid => $l) foreach ($l as $x) if ($x[0] >= $bas && $x[0] <= $bit) $o[] = ['mid' => $mid, 'tarih' => $x[0], 'toplam' => $x[1]];
        return $o;
    }
    // Geri kazanılanlar: dönemde, bir önceki siparişinden 60+ gün sonra yeniden gelen müşteriler
    function _crrGeriKazanim($sid, $bas, $bit, $esik = 60)
    {
        $o = [];
        foreach (_crrSiparis($sid) as $mid => $l) {
            for ($i = 1; $i < count($l); $i++) {
                if ($l[$i][0] < $bas || $l[$i][0] > $bit) continue;
                $ara = _crrGunFark($l[$i - 1][0], $l[$i][0]); if ($ara < $esik) continue;
                $donemCiro = array_sum(array_map(fn ($x) => $x[0] >= $bas && $x[0] <= $bit ? $x[1] : 0, $l)); $donemN = count(array_filter($l, fn ($x) => $x[0] >= $bas && $x[0] <= $bit));
                $o[$mid] = ['mid' => $mid, 'onceki' => $l[$i - 1][0], 'donus' => $l[$i][0], 'ara' => $ara, 'n' => $donemN, 'ciro' => round($donemCiro, 2)]; break;
            }
        }
        return $o;
    }
    // Memnuniyet (QR) + sipariş değerlendirmeleri
    function _crrMemnuniyet($sid, $bas, $bit)
    {
        if (!_srTablo('memnuniyet')) return [];
        try { return DB::table('memnuniyet')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get()->map(fn ($r) => ['puan' => (int) $r->puan, 'sebepler' => array_values(array_filter(array_map('trim', explode(',', (string) ($r->sebepler ?? ''))))),
            'not' => trim((string) ($r->not ?? '')), 'masa' => (string) ($r->masa_ad ?? '') ?: '—', 'kanal' => (string) ($r->kanal ?? 'qr'), 'durum' => (string) ($r->durum ?? 'yeni'), 'ts' => strtotime((string) $r->created_at), 'tarih' => substr((string) $r->created_at, 0, 10),
            'cozuldu' => !empty($r->cozuldu_at) ? strtotime((string) $r->cozuldu_at) : null])->all(); } catch (\Throwable $e) { return []; }
    }
    function _crrDegerlendirme($sid, $bas, $bit)
    {
        if (!_srTablo('degerlendirmeler')) return [];
        try { return DB::table('degerlendirmeler')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orderBy('created_at')->get()->map(fn ($r) => ['puan' => (int) $r->puan, 'lezzet' => (int) ($r->lezzet ?? 0), 'servis' => (int) ($r->servis ?? 0), 'hiz' => (int) ($r->hiz ?? 0),
            'yorum' => trim((string) ($r->yorum ?? '')), 'mid' => (int) ($r->musteri_id ?? 0), 'tarih' => substr((string) $r->created_at, 0, 10)])->filter(fn ($x) => $x['puan'] > 0)->values()->all(); } catch (\Throwable $e) { return []; }
    }
    // Tavsiye skoru (1-5 ölçek): %5 puan − %1-3 puan
    function _crrTavsiye(array $puanlar) { $n = count($puanlar); if (!$n) return null; return round((count(array_filter($puanlar, fn ($p) => $p === 5)) - count(array_filter($puanlar, fn ($p) => $p <= 3))) / $n * 100, 1); }
    function _crrOrt(array $l) { $l = array_values(array_filter($l, fn ($x) => $x > 0)); return $l ? round(array_sum($l) / count($l), 2) : null; }
    function _crrOdul($sid, $bas, $bit)
    {
        if (!_srTablo('odul_kullanimlari')) return [];
        $q = DB::table('odul_kullanimlari as o'); if (_srKolon('odul_kullanimlari', 'sube_id')) $q->where('o.sube_id', $sid); else $q->join('musteriler as m', 'm.id', '=', 'o.musteri_id')->where('m.sube_id', $sid);
        $sel = ['o.musteri_id', 'o.odul_ad', 'o.puan', 'o.durum', 'o.bitis', 'o.kasiyer_id', 'o.kullanildi_at', 'o.created_at']; $sel[] = _srKolon('odul_kullanimlari', 'kaynak') ? 'o.kaynak' : DB::raw('NULL as kaynak');
        try { $rows = $q->where(function ($w) use ($bas, $bit) { $w->whereBetween('o.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->orWhereBetween('o.kullanildi_at', [$bas . ' 00:00:00', $bit . ' 23:59:59']); })->get($sel); } catch (\Throwable $e) { $rows = []; }
        $simdi = date('Y-m-d H:i:s');
        return array_map(fn ($r) => ['mid' => (int) $r->musteri_id, 'ad' => (string) $r->odul_ad, 'puan' => (int) $r->puan, 'kaynak' => $r->kaynak === 'cark' ? 'Çarkıfelek kuponu' : 'Puan ödülü',
            'durum' => $r->durum === 'kullanildi' ? 'Kullanıldı' : ($r->bitis && (string) $r->bitis < $simdi ? 'Süresi doldu' : 'Bekliyor'), 'olustu' => substr((string) $r->created_at, 0, 10), 'kullanildi' => $r->kullanildi_at ? substr((string) $r->kullanildi_at, 0, 10) : null,
            'kasiyer' => (int) $r->kasiyer_id, 'sure' => $r->kullanildi_at ? round((strtotime((string) $r->kullanildi_at) - strtotime((string) $r->created_at)) / 60, 1) : null], is_array($rows) ? $rows : $rows->all());
    }
    function _crrCark($sid, $bas, $bit)
    {
        if (!_srTablo('carkifelek_log')) return [];
        try { return DB::table('carkifelek_log')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['musteri_id', 'tip', 'deger', 'dilim_ad', 'kupon_kodu', 'created_at'])->map(fn ($r) => ['mid' => (int) $r->musteri_id, 'tip' => (string) $r->tip, 'deger' => (float) $r->deger, 'dilim' => (string) ($r->dilim_ad ?: $r->tip), 'kupon' => (string) ($r->kupon_kodu ?? ''), 'tarih' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
    function _crrCarkTipAd($t) { return ['puan' => 'Puan', 'yuzde' => 'Yüzde indirim', 'tutar' => 'Tutar indirim', 'urun_indirimi' => 'Ürün indirimi', 'hizmet_indirimi' => 'İndirim', 'paket_indirimi' => 'Paket indirimi', 'tekrar' => 'Tekrar dene', 'tekrar_dene' => 'Tekrar dene', 'bos' => 'Boş'][$t] ?? ($t ?: '—'); }
    // Puan hareketleri (türetilmiş defter): kazanım (sipariş, çark, kart) ve harcama (kullanılan ödül)
    function _crrPuan($sid, $bas, $bit)
    {
        $o = ['siparis' => [], 'cark' => [], 'kart' => [], 'harcama' => []];
        foreach (_crrDonemSiparis($sid, $bas, $bit) as $x) { $p = (int) floor($x['toplam'] / 10); if ($p > 0) $o['siparis'][] = ['mid' => $x['mid'], 'tarih' => $x['tarih'], 'puan' => $p]; }
        foreach (_crrCark($sid, $bas, $bit) as $x) if ($x['tip'] === 'puan' && $x['deger'] > 0) $o['cark'][] = ['mid' => $x['mid'], 'tarih' => $x['tarih'], 'puan' => (int) $x['deger']];
        if (_srTablo('musteri_kart_kullanim') && _srKolon('app_banner', 'odul_puan')) { try { foreach (DB::table('musteri_kart_kullanim as k')->join('app_banner as b', 'k.banner_id', '=', 'b.id')->where('b.sube_id', $sid)->where('b.odul_puan', '>', 0)->whereBetween('k.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['k.musteri_id', 'k.created_at', 'b.odul_puan']) as $r) $o['kart'][] = ['mid' => (int) $r->musteri_id, 'tarih' => substr((string) $r->created_at, 0, 10), 'puan' => (int) $r->odul_puan]; } catch (\Throwable $e) {} }
        foreach (_crrOdul($sid, $bas, $bit) as $x) if ($x['durum'] === 'Kullanıldı' && $x['kullanildi'] >= $bas && $x['kullanildi'] <= $bit && $x['puan'] > 0) $o['harcama'][] = ['mid' => $x['mid'], 'tarih' => $x['kullanildi'], 'puan' => $x['puan']];
        return $o;
    }
    function _crrOdulMaliyet($sid, $bas, $bit)
    {
        if (!_srTablo('iptal_indirim_loglari')) return 0.0;
        try { return round((float) DB::table('iptal_indirim_loglari')->where('sube_id', $sid)->whereIn('tip', ['indirim', 'ikram'])->where('sebep', 'like', 'Ödül:%')->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->sum('tutar'), 2); } catch (\Throwable $e) { return 0.0; }
    }
    function _crrCuzdan($sid, $bas, $bit)
    {
        if (!_srTablo('cuzdan_hareketleri') || !_srTablo('musteriler')) return [];
        try { return DB::table('cuzdan_hareketleri as c')->join('musteriler as m', 'm.id', '=', 'c.musteri_id')->where('m.sube_id', $sid)->whereBetween('c.created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['c.musteri_id', 'c.tip', 'c.tutar', 'c.created_at'])->map(fn ($r) => ['mid' => (int) $r->musteri_id, 'tip' => (string) $r->tip, 'tutar' => abs((float) $r->tutar), 'tarih' => substr((string) $r->created_at, 0, 10)])->all(); } catch (\Throwable $e) { return []; }
    }
    // Kayıp riski: en az 2 siparişi olan, son ziyaretinden bu yana kendi ortalama aralığının 2 katından (en az 21 gün) fazla geçmiş, 180 günü aşmamış müşteri
    function _crrRisk($sid, $ref)
    {
        $prof = _crrProfil($sid, $ref); $rfm = _crrRfm($prof); $o = [];
        foreach ($prof as $id => $p) {
            if ($p['n'] < 2 || $p['kartlik'] || $p['aralik'] === null || $p['gun'] === null || $p['gun'] > 180) continue;
            $esik = max(21, 2 * $p['aralik']); if ($p['gun'] <= $esik) continue;
            $o[] = ['id' => $id, 'ad' => $p['ad'], 'tel' => _crrTlf($p['tel']), 'n' => $p['n'], 'ciro' => $p['ciro'], 'aralik' => $p['aralik'], 'son' => $p['son'], 'gun' => $p['gun'], 'kat' => round($p['gun'] / max(1, $p['aralik']), 1), 'seg' => $rfm[$id]['seg'] ?? '—',
                'yillik' => $p['aralik'] > 0 ? round($p['ciro'] / $p['n'] * min(365 / $p['aralik'], 104), 2) : null];
        }
        usort($o, fn ($a, $b) => $b['ciro'] <=> $a['ciro']);
        return $o;
    }
    function _crrDogum($sid, $bas, $gun = 30)
    {
        $o = []; $sp = _crrSiparis($sid);
        foreach (_crrMusteriler($sid) as $id => $m) {
            if (!$m->dogum || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $m->dogum)) continue;
            $y = (int) substr($bas, 0, 4); $md = substr($m->dogum, 5); if ($md === '02-29' && !checkdate(2, 29, $y)) $md = '02-28';
            $t = $y . '-' . $md; if ($t < $bas) { $y++; $t = $y . '-' . $md; }
            $kalan = _crrGunFark($bas, $t); if ($kalan > $gun) continue;
            $l = $sp[$id] ?? []; $o[] = [$t, $kalan, _crrAd($m, $id), _crrTlf($m->telefon), $y - (int) substr($m->dogum, 0, 4), count($l) ?: $m->siparis, $l ? $l[count($l) - 1][0] : null];
        }
        usort($o, fn ($a, $b) => $a[1] <=> $b[1]);
        return $o;
    }
}

// ---------- Veri durumu + boş rapor açıklamaları ----------
if (!function_exists('_crrVeriDurumu')) {
    function _crrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString(); $mu = _crrMusteriler($sid);
        $say = function ($t, $kol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t); if (_srKolon($t, 'sube_id')) $q->where('sube_id', $sid); if ($kol && _srKolon($t, $kol)) $q->where($kol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        return ['musteri' => count($mu), 'siparis' => _srKolon('adisyonlar', 'musteri_id') ? $say('adisyonlar', 'kapanis', fn ($q) => $q->where('durum', 'odendi')->whereNotNull('musteri_id')->where('musteri_id', '>', 0)) : 0,
            'memnuniyet' => $say('memnuniyet', 'created_at'), 'degerlendirme' => $say('degerlendirmeler', 'created_at'), 'urun_puan' => $say('urun_puanlari', 'created_at'),
            'odul' => count(_crrOdul($sid, $s, date('Y-m-d'))), 'cark' => $say('carkifelek_log', 'created_at'), 'cuzdan' => count(_crrCuzdan($sid, $s, date('Y-m-d'))),
            'puan' => count(array_filter($mu, fn ($m) => $m->puan > 0)), 'dogum' => count(array_filter($mu, fn ($m) => $m->dogum)), 'telefon' => count(array_filter($mu, fn ($m) => strlen(preg_replace('/\D/', '', $m->telefon)) >= 10))];
    }
    function _crrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'musteri_yok' => 'Müşteri kaydı yok. CRM raporları müşteri kartlarından hesaplanır. Müşteriler paket / uygulama siparişinde otomatik, salonda ise POS\'ta adisyona müşteri seçilince oluşur (CRM > Müşteri 360).',
            'siparis_yok' => 'Müşteriye bağlı sipariş yok: son 1 yılda müşteri seçilmiş ödenmiş adisyon yok. Ziyaret sıklığı, yaşam boyu değer, RFM ve kayıp riski bu bağdan hesaplanır. POS\'ta adisyona müşteri seçin (paket ve uygulama siparişlerinde otomatik bağlanır).',
            'siparis' => 'Seçili dönemde (' . $donem . ') müşteriye bağlı ödenmiş sipariş yok.',
            'tekrar' => 'İki ve daha fazla siparişi olan müşteri yok; ziyaret aralığı ancak ikinci siparişten sonra ölçülür.',
            'kohort' => 'Son 12 ayda ilk siparişini veren müşteri yok.',
            'risk' => 'Alışkanlığını kıran müşteri yok: en az 2 siparişi olan herkes kendi ortalama ziyaret aralığının 2 katı dolmadan geri gelmiş (ya da 180 günden uzun süredir gelmiyor; onlar Yaşam Döngüsü\'nde "Uyuyan").',
            'geri' => 'Seçili dönemde (' . $donem . ') 60 günden uzun aradan sonra geri gelen müşteri yok.',
            'vip' => 'Ömür boyu 5.000 ₺ ve üzeri harcayan (VIP) müşteri yok.',
            'mem_yok' => 'Memnuniyet kaydı yok. Müşteri masadaki QR\'ı okutup "nasıldı?" sorusuna puan verince kayıt düşer (İtibar / Memnuniyet ekranından masa QR\'larını yazdırın).',
            'mem' => 'Seçili dönemde (' . $donem . ') memnuniyet puanı verilmemiş.',
            'sikayet' => 'Seçili dönemde (' . $donem . ') düşük puan (1-2) ya da şikayet yok.',
            'deg_yok' => 'Sipariş değerlendirmesi yok. Müşteri uygulamasında / sipariş sonrası anket linkinde lezzet, servis ve hız puanı verilince kayıt düşer.',
            'deg' => 'Seçili dönemde (' . $donem . ') sipariş değerlendirmesi yok.',
            'urun_yok' => 'Ürün puanı yok. QR menüde müşteri ürüne yıldız verdiğinde kayıt düşer.',
            'urun' => 'Seçili dönemde (' . $donem . ') ürün puanı verilmemiş.',
            'odul_yok' => 'Ödül kullanımı yok. Müşteri uygulamada puanıyla ödül seçip kodu kasada okutunca (Ödül Doğrula) kayıt düşer; ödül merdiveni Sadakat / Puan ekranından tanımlanır.',
            'odul' => 'Seçili dönemde (' . $donem . ') ödül kodu üretilmemiş ya da kullanılmamış.',
            'cark_yok' => 'Çarkıfelek kaydı yok. Çark Çarkıfelek ekranından açılır; müşteri uygulamada çevirince kayıt düşer.',
            'cark' => 'Seçili dönemde (' . $donem . ') çark çevrilmemiş.',
            'puan' => 'Seçili dönemde (' . $donem . ') puan kazanımı ya da harcaması yok. Puan, müşteriye bağlı her ödenmiş adisyonda her 10 ₺ için 1 puan olarak kazanılır.',
            'cuzdan_yok' => 'Cüzdan hareketi yok. Müşteri uygulamada cüzdana para yükleyip siparişte cüzdanla ödeyince kayıt düşer (Cüzdan Ayarları).',
            'cuzdan' => 'Seçili dönemde (' . $donem . ') cüzdan hareketi yok.',
            'dogum_yok' => 'Müşteri kartlarında doğum tarihi yok. Doğum günü kampanyası için Müşteri 360\'ta ya da müşteri uygulamasında doğum tarihi girilmeli.',
            'dogum' => 'Önümüzdeki 30 günde doğum günü olan müşteri yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// ---------- Katalog ----------
if (!function_exists('_crrKatalog')) {
    function _crrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük CRM Bülteni', 'Dün gelen müşteriler, yeni ve geri kazanılanlar, memnuniyet ve açık şikayetler, ödül ve puan, önümüzdeki 7 günün doğum günleri.', 'today', '#0D9488', ['siparis', 'memnuniyet'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık CRM Raporu', 'Gün gün müşteri, müşteri başı harcama, memnuniyet; kayıp riski listesi, şikayetler ve önceki haftayla kıyas.', 'date_range', '#0F766E', ['siparis', 'memnuniyet'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık CRM Yönetim Raporu', 'Müşteri tabanı ve yaşam döngüsü, RFM segmentleri, tekrar oranı, memnuniyet ve tavsiye skoru, sadakat — patron için tek dosyada ay özeti.', 'calendar_month', '#115E59', ['siparis', 'memnuniyet', 'odul'], 'gecen_ay'],
            'yasam' => ['taban', 'Müşteri Yaşam Döngüsü', 'Yeni, aktif, risk altında, kayıp, uyuyan müşteri sayıları (30/60/120 gün) ve önceki dönem sonuna göre değişim.', 'donut_large', '#0D9488', ['musteri', 'siparis'], null],
            'tekrar' => ['taban', 'Tekrar Ziyaret & Sıklık', 'Dönemde birden çok kez gelen müşteri oranı, ömür boyu sipariş sayısı dağılımı, ziyaretler arası gün.', 'repeat', '#0F766E', ['siparis'], null],
            'kohort' => ['taban', 'Kohort: Müşteri Tutma', 'İlk siparişini aynı ayda veren müşterilerin sonraki aylarda geri gelme yüzdesi (son 12 ay).', 'grid_on', '#115E59', ['siparis'], null],
            'rfm' => ['deger', 'RFM Segmentleri', 'Yakınlık (son ziyaret), sıklık ve harcamaya göre Şampiyonlar, Sadık, Risk altında, Kaybedilmemeli… segmentleri ve önerilen aksiyon.', 'scatter_plot', '#7C3AED', ['siparis'], null],
            'clv' => ['deger', 'Müşteri Yaşam Boyu Değeri', 'Müşteri başı ömür boyu ciro, sipariş, ortalama sepet, aylık sıklık ve tahmini yıllık değer; değer bantları.', 'savings', '#6D28D9', ['siparis'], null],
            'harcama' => ['deger', 'Müşteri Başına Harcama', 'Dönemde müşteri başı harcama ve sepet; harcama bantları, en çok harcayanlar, önceki dönem kıyası.', 'payments', '#5B21B6', ['siparis'], null],
            'vip' => ['deger', 'VIP & Pareto', 'Ömür boyu 5.000 ₺+ harcayan VIP\'ler, uzaklaşan VIP\'ler ve müşterilerin ilk %20\'sinin cirodaki payı.', 'workspace_premium', '#4C1D95', ['siparis'], null],
            'risk' => ['kayip', 'Kayıp Riski (Alışkanlığını Kıranlar)', 'Kendi ortalama ziyaret aralığının 2 katından uzun süredir gelmeyen düzenli müşteriler; ömür boyu ciro ve tahmini yıllık değer kaybı.', 'person_off', '#DC2626', ['siparis'], null],
            'geri_kazanim' => ['kayip', 'Geri Kazanılan Müşteriler', '60 günden uzun aradan sonra dönemde yeniden gelen müşteriler: ara, dönüşte harcama.', 'restart_alt', '#B91C1C', ['siparis'], null],
            'memnuniyet' => ['memnuniyet', 'Memnuniyet & Tavsiye Skoru', 'QR memnuniyet ve sipariş değerlendirmesi puanları: ortalama, memnun oranı, tavsiye skoru (5 − 1/2/3), gün ve masa dağılımı.', 'sentiment_satisfied', '#16A34A', ['memnuniyet', 'degerlendirme'], null],
            'sikayet' => ['memnuniyet', 'Şikayet Takibi & Çözüm', 'Düşük puanlar (1-2): şikayet sebepleri, masalar, çözülen / açık ve ortalama çözüm süresi; açık şikayet listesi.', 'report_problem', '#15803D', ['memnuniyet'], null],
            'degerlendirme' => ['memnuniyet', 'Sipariş Değerlendirmeleri', 'Lezzet, servis ve hız alt puanları, en zayıf alan, yorumlar.', 'rate_review', '#166534', ['degerlendirme'], null],
            'urun_puan' => ['memnuniyet', 'Ürün Puanları (QR Menü)', 'Müşterinin QR menüde ürünlere verdiği yıldızlar: ortalama, oy sayısı, düşük puanlı ürünler.', 'star_half', '#14532D', ['urun_puan'], null],
            'puan' => ['sadakat', 'Puan Kazanım & Harcama', 'Siparişten, çarktan ve kampanya kartından kazanılan puan; ödüle harcanan puan; puan bakiyesi ve en yüksek bakiyeler.', 'stars', '#DB2777', ['siparis', 'puan'], null],
            'odul' => ['sadakat', 'Ödül Kullanımı', 'Üretilen, kullanılan ve süresi dolan ödül kodları; en çok kullanılan ödül, harcanan puan ve ödül maliyeti.', 'redeem', '#BE185D', ['odul'], null],
            'cark' => ['sadakat', 'Çarkıfelek', 'Çevirme sayısı, çeviren müşteri, dilim dağılımı, verilen puan ve kupon; kuponların kullanılma oranı ve çevirdikten sonra sipariş.', 'casino', '#9D174D', ['cark'], null],
            'cuzdan' => ['sadakat', 'Cüzdan (Ön Ödeme)', 'Cüzdana yüklenen ve harcanan tutar, otomatik yükleme, müşterilerdeki toplam bakiye (işletmenin borcu).', 'account_balance_wallet', '#831843', ['cuzdan'], null],
            'dogum' => ['ozel', 'Doğum Günleri', 'Önümüzdeki 30 günde doğum günü olan müşteriler, doğum tarihi doluluk oranı; dönemde doğum günü olanlardan kaçı geldi.', 'cake', '#D97706', ['dogum'], null],
            'veri' => ['ozel', 'Müşteri Veri Kalitesi', 'Telefon, doğum tarihi ve isim doluluğu, mükerrer telefonlar, hiç sipariş vermemiş kayıtlar.', 'fact_check', '#B45309', ['musteri'], null],
        ];
    }
    function _crrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Patron ve CRM sorumlusu için hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#0D9488'],
            'taban' => ['Müşteri Tabanı', 'donut_large', 'Yaşam döngüsü, tekrar ziyaret ve sıklık, kohort ile müşteri tutma.', '#0F766E'],
            'deger' => ['Müşteri Değeri', 'savings', 'RFM segmentleri, yaşam boyu değer, müşteri başına harcama, VIP ve Pareto.', '#7C3AED'],
            'kayip' => ['Kayıp & Geri Kazanım', 'person_off', 'Alışkanlığını kıran düzenli müşteriler ve uzun aradan sonra geri gelenler.', '#DC2626'],
            'memnuniyet' => ['Memnuniyet & Şikayet', 'sentiment_satisfied', 'Memnuniyet ve tavsiye skoru, şikayet çözümü, sipariş değerlendirmesi, ürün puanları.', '#16A34A'],
            'sadakat' => ['Sadakat & Ödül', 'loyalty', 'Puan kazanım ve harcaması, ödül kullanımı, çarkıfelek, cüzdan.', '#DB2777'],
            'ozel' => ['Özel Gün & Veri', 'cake', 'Doğum günleri ve müşteri veri kalitesi.', '#D97706'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı CRM özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// ---------- Sayfa kurucuları ----------
if (!function_exists('_crrSayfaRisk')) {
    function _crrSayfaRisk(array $r, $bos)
    {
        return _srSayfa('Kayıp Riski Listesi', 'Gecikme katı = son ziyaretten beri geçen gün ÷ müşterinin ortalama ziyaret aralığı. Tahmini yıllık değer = ortalama sepet × yıllık ziyaret sıklığı (kaybedilirse eksilecek ciro). Telefon KVKK için maskeli; tam numara Müşteri 360\'ta.',
            [['Müşteri', 'm', 20], ['Telefon', 'm', 12], ['RFM Segmenti', 'm', 16], ['Sipariş', 's', 7], ['Ömür Boyu Ciro', 'tl', 13, 'top bar'], ['Ort. Aralık (gün)', 's', 11], ['Son Ziyaret', 't', 11], ['Geçen Gün', 's', 9], ['Gecikme Katı', 's', 9, 'esik:4'], ['Tahmini Yıllık Değer', 'tl', 14, 'top']],
            array_map(fn ($x) => [$x['ad'], $x['tel'], $x['seg'], $x['n'], $x['ciro'], $x['aralik'], $x['son'], $x['gun'], $x['kat'], $x['yillik']], $r), $bos);
    }
    function _crrSayfaSikayet(array $s, $bos)
    {
        return _srSayfa('Açık Şikayetler', 'Puanı 1-2 olup "çözüldü" işaretlenmemiş geri bildirimler (İtibar / Memnuniyet ekranından çözülür).', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Masa', 'm', 10], ['Puan', 's', 6], ['Sebepler', 'm', 24], ['Not', 'm', 40], ['Bekleyen Gün', 's', 10, 'esik:2']],
            array_map(fn ($x) => [$x['tarih'], date('H:i', $x['ts']), $x['masa'], $x['puan'], implode(', ', $x['sebepler']) ?: '—', $x['not'] ?: '—', (int) floor((time() - $x['ts']) / 86400)], $s), $bos);
    }
    function _crrRfmSat(array $prof, array $rfm)
    {
        $g = []; foreach ($rfm as $id => $x) { $p = $prof[$id]; $y = &$g[$x['seg']]; $y['n'] = ($y['n'] ?? 0) + 1; $y['gun'] = ($y['gun'] ?? 0) + $p['gun']; $y['sip'] = ($y['sip'] ?? 0) + $p['n']; $y['ciro'] = ($y['ciro'] ?? 0) + $p['ciro']; unset($y); }
        $top = max(1, count($rfm)); $tc = max(0.01, array_sum(array_column($g, 'ciro'))); $o = [];
        foreach (_crrSegSira() as $s) if (isset($g[$s])) { $v = $g[$s]; $o[] = [$s, $v['n'], round($v['n'] / $top * 100, 1), round($v['gun'] / $v['n'], 1), round($v['sip'] / $v['n'], 1), round($v['ciro'] / $v['n'], 2), round($v['ciro'], 2), round($v['ciro'] / $tc * 100, 1), _crrRfmAksiyon($s)]; }
        return $o;
    }
    function _crrSayfaRfm(array $sat, $bos)
    {
        return _srSayfa('RFM Segmentleri', 'R: son ziyaret (≤14 gün 5 … 120+ gün 1), F: sipariş sayısı (10+ 5 … 1 sipariş 1), M: ömür boyu ciroya göre beşte birlik dilim. Yalnız müşteriye bağlı siparişi olan müşteriler.',
            [['Segment', 'm', 18], ['Müşteri', 's', 8, 'top bar'], ['Pay %', 'y', 7], ['Ort. Son Ziyaret (gün)', 's', 13], ['Ort. Sipariş', 's', 9], ['Ort. Ciro', 'tl', 11], ['Toplam Ciro', 'tl', 13, 'top'], ['Ciro Payı %', 'y', 9], ['Önerilen Aksiyon', 'm', 46]], $sat, $bos);
    }
    function _crrYasamSat(array $prof, array $profO)
    {
        $s = array_fill_keys(_crrDurumlar(), ['n' => 0, 'c' => 0]); $so = array_fill_keys(_crrDurumlar(), 0);
        foreach ($prof as $p) { $d = _crrDurum($p); $s[$d]['n']++; $s[$d]['c'] += $p['ciro']; }
        foreach ($profO as $p) $so[_crrDurum($p)]++;
        $top = max(1, count($prof)); $o = [];
        foreach ($s as $d => $v) if ($v['n'] || $so[$d]) $o[] = [$d, $v['n'], round($v['n'] / $top * 100, 1), $so[$d], $v['n'] - $so[$d], round($v['c'], 2), $v['n'] ? round($v['c'] / $v['n'], 2) : null];
        return $o;
    }
    function _crrSayfaYasam(array $sat, $bos)
    {
        return _srSayfa('Yaşam Döngüsü', 'Dönem sonuna göre: Yeni = tek siparişi son 30 günde; Aktif = son ziyaret ≤30 gün; Risk altında 31-60; Kayıp 61-120; Uyuyan 120+ gün (Müşteri 360 ile aynı eşikler). Önceki = önceki dönem sonundaki durum.',
            [['Durum', 'm', 20], ['Müşteri', 's', 9, 'top bar'], ['Pay %', 'y', 8], ['Önceki Dönem Sonu', 's', 13], ['Fark', 's', 7], ['Ömür Boyu Ciro', 'tl', 14, 'top'], ['Kişi Başı Ciro', 'tl', 12]], $sat, $bos);
    }
}

// ---------- Raporlar ----------
if (!function_exists('_crrRapor')) {
    function _crrRapor($anahtar, $sid, array $dn)
    {
        _crrEnsure();
        $d = _crrVeriDurumu($sid); $bas = $dn['bas']; $bit = $dn['bit'];
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiTl = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'tl', $renk);
        $kpiS = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'sayi', $renk);
        $kpiDeg = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? '—' : ($v >= 0 ? '+' : '') . number_format($v, 1, ',', '.') . '%', 'metin', $renk);
        $oran = fn ($a, $b) => $b > 0 ? round($a / $b * 100, 1) : null;
        $mu = _crrMusteriler($sid);
        $bosMus = !$d['musteri'] ? _crrBos('musteri_yok', $dn) : null;
        $bosSip = !$d['musteri'] ? _crrBos('musteri_yok', $dn) : (!$d['siparis'] ? _crrBos('siparis_yok', $dn) : _crrBos('siparis', $dn));
        $bosSipTum = !$d['musteri'] ? _crrBos('musteri_yok', $dn) : (!$d['siparis'] && !_crrSiparis($sid) ? _crrBos('siparis_yok', $dn) : null);
        $bosMem = !$d['memnuniyet'] ? _crrBos('mem_yok', $dn) : _crrBos('mem', $dn);
        $musteriSay = function ($l) { $o = []; foreach ($l as $x) $o[$x['mid']] = ($o[$x['mid']] ?? 0) + 1; return $o; };
        $acikSikayet = fn () => array_values(array_filter(_crrMemnuniyet($sid, '2000-01-01', date('Y-m-d')), fn ($x) => $x['puan'] <= 2 && $x['durum'] !== 'cozuldu'));

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
            case 'haftalik':
            case 'aylik':
                $s = _crrDonemSiparis($sid, $bas, $bit); $so = _crrDonemSiparis($sid, $dn['obas'], $dn['obit']); $ms = $musteriSay($s); $mso = $musteriSay($so);
                $c = array_sum(array_column($s, 'toplam')); $co = array_sum(array_column($so, 'toplam')); $sp = _crrSiparis($sid);
                $yeni = array_filter(array_keys($ms), fn ($m) => ($sp[$m][0][0] ?? '') >= $bas); $gk = _crrGeriKazanim($sid, $bas, $bit);
                $mem = _crrMemnuniyet($sid, $bas, $bit); $memo = _crrMemnuniyet($sid, $dn['obas'], $dn['obit']); $deg = _crrDegerlendirme($sid, $bas, $bit); $puanlar = array_merge(array_column($mem, 'puan'), array_column($deg, 'puan'));
                $acik = $acikSikayet(); $od = array_filter(_crrOdul($sid, $bas, $bit), fn ($x) => $x['kullanildi'] && $x['kullanildi'] >= $bas && $x['kullanildi'] <= $bit);
                $pu = _crrPuan($sid, $bas, $bit); $kaz = array_sum(array_column(array_merge($pu['siparis'], $pu['cark'], $pu['kart']), 'puan')); $dg7 = _crrDogum($sid, date('Y-m-d'), 7);
                $risk = _crrRisk($sid, $bit);
                $kpi = [_srKpi('Gelen müşteri', count($ms), 'sayi', '0D9488'), $kpiDeg('Önceki döneme göre', _srYuzde(count($ms), count($mso)), '0F766E'), _srKpi('Yeni müşteri', count($yeni), 'sayi', '059669'),
                    _srKpi('Geri kazanılan (60+ gün sonra)', count($gk), 'sayi', '047857'), $kpiY('Tekrar gelen oranı', $oran(count(array_filter($ms, fn ($n) => $n >= 2)), count($ms)), '115E59'),
                    $kpiTl('Müşteri başı harcama', count($ms) ? round($c / count($ms), 2) : null, '7C3AED'), $kpiTl('Önceki dönem', count($mso) ? round($co / count($mso), 2) : null, '64748B'),
                    $kpiS('Memnuniyet ortalaması (5)', _crrOrt($puanlar), '16A34A'), $kpiS('Önceki dönem', _crrOrt(array_column($memo, 'puan')), '64748B'), $kpiY('Tavsiye skoru', _crrTavsiye($puanlar), '15803D'),
                    _srKpi('Açık şikayet (tümü)', count($acik), 'sayi', 'DC2626'), _srKpi('Kullanılan ödül', count($od), 'sayi', 'DB2777'), _srKpi('Kazanılan puan', $kaz, 'sayi', 'BE185D'),
                    _srKpi('Kayıp riskindeki düzenli müşteri', count($risk), 'sayi', 'B91C1C'), _srKpi('7 gün içinde doğum günü', count($dg7), 'sayi', 'D97706')];
                $gun = []; foreach ($s as $x) { $gun[$x['tarih']]['m'][$x['mid']] = 1; $gun[$x['tarih']]['c'] = ($gun[$x['tarih']]['c'] ?? 0) + $x['toplam']; }
                $gy = []; foreach ($yeni as $m) $gy[$sp[$m][0][0]] = ($gy[$sp[$m][0][0]] ?? 0) + 1;
                $gm = []; foreach ($mem as $x) { $gm[$x['tarih']]['p'][] = $x['puan']; if ($x['puan'] <= 2) $gm[$x['tarih']]['d'] = ($gm[$x['tarih']]['d'] ?? 0) + 1; }
                $go = []; foreach ($od as $x) $go[$x['kullanildi']] = ($go[$x['kullanildi']] ?? 0) + 1;
                $gt = array_map(fn ($t) => [$t, _crrGunAd(date('N', strtotime($t))), count($gun[$t]['m'] ?? []), $gy[$t] ?? 0, round($gun[$t]['c'] ?? 0, 2), isset($gm[$t]['p']) ? _crrOrt($gm[$t]['p']) : null, $gm[$t]['d'] ?? 0, $go[$t] ?? 0], _crrGunler($bas, $bit));
                $prof = _crrProfil($sid, $bit); $ys = _crrYasamSat($prof, _crrProfil($sid, $dn['obit']));
                $bloklar = [_srBlok('Müşteri yaşam döngüsü (dönem sonu)', 'pie', '0D9488', [['Durum', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($ys, fn ($x) => $x[1] > 0))),
                    _srBlok('Memnuniyet puan dağılımı', 'col', '16A34A', [['Puan', 'm'], ['Adet', 's'], ['', 'm']], array_filter(array_map(fn ($p) => [$p . ' yıldız', count(array_filter($puanlar, fn ($x) => $x === $p)), ''], [5, 4, 3, 2, 1]), fn ($x) => $x[1] > 0))];
                if ($anahtar !== 'gunluk') array_unshift($bloklar, _srBlok('Günlere göre gelen müşteri ve yeni müşteri', 'col', '0F766E', [['Gün', 'm'], ['Müşteri', 's'], ['Yeni', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[2], $x[3]], $gt)));
                $kpi[] = _srKpi('Dönem', $dn['etiket'], 'metin', '1E293B');
                $sayfalar = [];
                if ($anahtar === 'gunluk') {
                    $gelen = []; foreach ($ms as $m => $n) { $p = $prof[$m] ?? null; $l = $sp[$m] ?? []; $once = array_values(array_filter($l, fn ($x) => $x[0] < $bas)); $gelen[] = [_crrAd($mu[$m] ?? null, $m), $n, round(array_sum(array_map(fn ($x) => $x['mid'] === $m ? $x['toplam'] : 0, $s)), 2), count($l), $once ? $once[count($once) - 1][0] : null, $once ? 'Geri dönen' : 'Yeni']; }
                    usort($gelen, fn ($a, $b) => $b[2] <=> $a[2]);
                    $sayfalar = [_srSayfa('Gelen Müşteriler', 'Dün müşteriye bağlı siparişi olanlar.', [['Müşteri', 'm', 22], ['Sipariş', 's', 8, 'top'], ['Harcama', 'tl', 12, 'top bar'], ['Toplam Sipariş', 's', 11], ['Önceki Ziyaret', 't', 12], ['Tip', 'm', 12]], $gelen, $bosSip),
                        _crrSayfaSikayet($acik, 'Açık (çözülmemiş) şikayet yok.'),
                        _srSayfa('Önümüzdeki 7 Gün Doğum Günleri', '', [['Doğum Günü', 't', 11], ['Kalan Gün', 's', 9], ['Müşteri', 'm', 22], ['Telefon', 'm', 12], ['Yaş', 's', 6], ['Sipariş', 's', 8], ['Son Ziyaret', 't', 11]], $dg7, !$d['dogum'] ? _crrBos('dogum_yok', $dn) : 'Önümüzdeki 7 günde doğum günü olan müşteri yok.')];
                } else {
                    $sayfalar = [['grafiksiz' => true] + _srSayfa('Günlük Tablo', 'Müşteri = o gün müşteriye bağlı siparişi olan tekil kişi. Memnuniyet = QR memnuniyet puanı ortalaması.', [['Tarih', 't', 11], ['Gün', 'm', 10], ['Müşteri', 's', 8, 'top bar'], ['Yeni', 's', 6, 'top'], ['Müşterili Ciro', 'tl', 13, 'top'], ['Memnuniyet', 's', 10], ['Düşük Puan', 's', 9, 'top esik:1'], ['Ödül Kullanımı', 's', 11, 'top']], $gt, $bosSip),
                        _crrSayfaRisk(array_slice($risk, 0, 50), $bosSipTum ?? _crrBos('risk', $dn)), _crrSayfaSikayet($acik, 'Açık (çözülmemiş) şikayet yok.')];
                    if ($anahtar === 'aylik') { $sayfalar[] = _crrSayfaYasam($ys, $bosSipTum); $sayfalar[] = _crrSayfaRfm(_crrRfmSat($prof, _crrRfm($prof)), $bosSipTum ?? _crrBos('siparis_yok', $dn)); }
                }
                return array_merge([$pano($kpi, $bloklar, !$mu ? _crrBos('musteri_yok', $dn) : (!$s ? $bosSip : null))], $sayfalar);

            // ---------------- MÜŞTERİ TABANI ----------------
            case 'yasam':
                $prof = _crrProfil($sid, $bit); $profO = _crrProfil($sid, $dn['obit']); $ys = _crrYasamSat($prof, $profO); $by = array_column($ys, 1, 0);
                $liste = []; foreach ($prof as $p) { $dr = _crrDurum($p); if (in_array($dr, ['Risk altında', 'Kayıp'], true)) $liste[] = [$p['ad'], _crrTlf($p['tel']), $dr, $p['n'], $p['ciro'], $p['son'], $p['gun']]; }
                usort($liste, fn ($a, $b) => $b[4] <=> $a[4]);
                return [
                    $pano([_srKpi('Kayıtlı müşteri', count($prof), 'sayi', '0D9488'), _srKpi('Aktif + yeni', ($by['Aktif'] ?? 0) + ($by['Yeni'] ?? 0), 'sayi', '059669'), $kpiY('Aktif oranı (sipariş verenler içinde)', $oran(($by['Aktif'] ?? 0) + ($by['Yeni'] ?? 0), count(array_filter($prof, fn ($p) => $p['n'] > 0))), '047857'),
                        _srKpi('Risk altında', $by['Risk altında'] ?? 0, 'sayi', 'D97706'), _srKpi('Kayıp', $by['Kayıp'] ?? 0, 'sayi', 'DC2626'), _srKpi('Uyuyan', $by['Uyuyan'] ?? 0, 'sayi', '991B1B'), _srKpi('Hiç sipariş vermemiş', $by['Sipariş vermemiş'] ?? 0, 'sayi', '64748B')],
                        [_srBlok('Yaşam döngüsü', 'pie', '0D9488', [['Durum', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], array_filter($ys, fn ($x) => $x[1] > 0))),
                         _srBlok('Bu dönem sonu ve önceki dönem sonu', 'col', '0F766E', [['Durum', 'm'], ['Şimdi', 's'], ['Önceki', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[3]], $ys))], $bosSipTum),
                    _crrSayfaYasam($ys, $bosMus ?? _crrBos('siparis_yok', $dn)),
                    _srSayfa('Risk Altında ve Kayıp Müşteriler', 'Son ziyareti 31-120 gün önce olanlar; geri kazanım için en değerli olanlar üstte.', [['Müşteri', 'm', 22], ['Telefon', 'm', 12], ['Durum', 'm', 12], ['Sipariş', 's', 8], ['Ömür Boyu Ciro', 'tl', 13, 'top bar'], ['Son Ziyaret', 't', 11], ['Geçen Gün', 's', 9]], $liste, 'Risk altında ya da kayıp müşteri yok.'),
                ];
            case 'tekrar':
                $s = _crrDonemSiparis($sid, $bas, $bit); $so = _crrDonemSiparis($sid, $dn['obas'], $dn['obit']); $ms = $musteriSay($s); $mso = $musteriSay($so); $prof = _crrProfil($sid, $bit);
                $grp = ['1 sipariş' => [1, 1], '2 sipariş' => [2, 2], '3-5 sipariş' => [3, 5], '6-10 sipariş' => [6, 10], '11+ sipariş' => [11, 99999]]; $fs = [];
                foreach ($grp as $k => $_) $fs[$k] = ['n' => 0, 'c' => 0]; foreach ($prof as $p) if ($p['n'] > 0) foreach ($grp as $k => [$a, $b]) if ($p['n'] >= $a && $p['n'] <= $b) { $fs[$k]['n']++; $fs[$k]['c'] += $p['ciro']; }
                $fTop = max(1, array_sum(array_column($fs, 'n'))); $cTop = max(0.01, array_sum(array_column($fs, 'c')));
                $fSat = array_map(fn ($k, $v) => [$k, $v['n'], round($v['n'] / $fTop * 100, 1), round($v['c'], 2), round($v['c'] / $cTop * 100, 1)], array_keys($fs), array_values($fs));
                $ab = ['0-7 gün' => [0, 7], '8-14 gün' => [8, 14], '15-30 gün' => [15, 30], '31-60 gün' => [31, 60], '61-120 gün' => [61, 120], '120+ gün' => [121, 99999]]; $as = array_fill_keys(array_keys($ab), 0); $araliklar = [];
                $yil = date('Y-m-d', strtotime($bit . ' -365 day'));
                foreach (_crrSiparis($sid) as $l) { $t = array_values(array_unique(array_column(array_filter($l, fn ($x) => $x[0] <= $bit), 0))); for ($i = 1; $i < count($t); $i++) { if ($t[$i] < $yil) continue; $g = _crrGunFark($t[$i - 1], $t[$i]); $araliklar[] = $g; foreach ($ab as $k => [$a, $b]) if ($g >= $a && $g <= $b) $as[$k]++; } }
                $aTop = max(1, count($araliklar)); sort($araliklar);
                $sik = []; foreach ($ms as $m => $n) if ($n >= 2) $sik[] = [_crrAd($mu[$m] ?? null, $m), $n, round(array_sum(array_map(fn ($x) => $x['mid'] === $m ? $x['toplam'] : 0, $s)), 2), $prof[$m]['n'] ?? $n, $prof[$m]['aralik'] ?? null];
                usort($sik, fn ($a, $b) => $b[1] <=> $a[1]);
                $tek = count(array_filter($ms, fn ($n) => $n >= 2)); $teko = count(array_filter($mso, fn ($n) => $n >= 2));
                return [
                    $pano([_srKpi('Dönemde gelen müşteri', count($ms), 'sayi', '0F766E'), _srKpi('Birden çok kez gelen', $tek, 'sayi', '0D9488'), $kpiY('Tekrar gelen oranı', $oran($tek, count($ms)), '059669'), $kpiY('Önceki dönem', $oran($teko, count($mso)), '64748B'),
                        $kpiS('Dönemde müşteri başı sipariş', count($ms) ? round(count($s) / count($ms), 2) : null, '115E59'), $kpiY('Tek siparişte kalan müşteri', $oran($fs['1 sipariş']['n'], $fTop), 'D97706'),
                        $kpiS('Ziyaretler arası medyan gün', $araliklar ? $araliklar[intdiv(count($araliklar), 2)] : null, '7C3AED')],
                        [_srBlok('Ömür boyu sipariş sayısına göre müşteri', 'col', '0F766E', [['Grup', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $fSat)),
                         _srBlok('Ziyaretler arası gün (son 1 yıl)', 'col', '7C3AED', [['Aralık', 'm'], ['Ziyaret', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($as), array_values($as)))], $bosSipTum ?? (!$s ? $bosSip : null)),
                    _srSayfa('Sipariş Sayısı Dağılımı', 'Müşterinin dönem sonuna kadarki ömür boyu sipariş sayısı. "1 sipariş" grubu büyükse ikinci siparişe teşvik en kârlı adımdır.', [['Grup', 'm', 14], ['Müşteri', 's', 9, 'top bar'], ['Pay %', 'y', 8], ['Ömür Boyu Ciro', 'tl', 14, 'top'], ['Ciro Payı %', 'y', 10]], $fSat, $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                    _srSayfa('Ziyaretler Arası Gün', 'Aynı müşterinin art arda iki ziyareti arasındaki gün (son 1 yıl, aynı gün tekrarları tek sayılır).', [['Aralık', 'm', 12], ['Ziyaret', 's', 9, 'top bar'], ['Pay %', 'y', 8]], array_map(fn ($k, $v) => [$k, $v, round($v / $aTop * 100, 1)], array_keys($as), array_values($as)), $araliklar ? null : _crrBos('tekrar', $dn)),
                    _srSayfa('Dönemde En Sık Gelenler', '', [['Müşteri', 'm', 22], ['Dönemde Sipariş', 's', 13, 'top bar'], ['Dönem Harcaması', 'tl', 13, 'top'], ['Ömür Boyu Sipariş', 's', 13], ['Ort. Aralık (gün)', 's', 12]], array_slice($sik, 0, 50), 'Seçili dönemde birden çok kez gelen müşteri yok.'),
                ];
            case 'kohort':
                $sp = _crrSiparis($sid); $bitAy = substr($bit, 0, 7); $aylar = []; for ($i = 11; $i >= 0; $i--) $aylar[] = date('Y-m', strtotime(substr($bit, 0, 7) . '-01 -' . $i . ' month'));
                $koh = []; foreach ($sp as $l) { $ilk = substr($l[0][0], 0, 7); if (!in_array($ilk, $aylar, true)) continue; $koh[$ilk]['n'] = ($koh[$ilk]['n'] ?? 0) + 1; $koh[$ilk]['c'] = ($koh[$ilk]['c'] ?? 0) + array_sum(array_column($l, 1));
                    $ayl = array_unique(array_map(fn ($x) => substr($x[0], 0, 7), $l)); foreach ($ayl as $a) { $fark = ((int) substr($a, 0, 4) - (int) substr($ilk, 0, 4)) * 12 + (int) substr($a, 5) - (int) substr($ilk, 5); if ($fark >= 1 && $fark <= 6) $koh[$ilk]['r'][$fark] = ($koh[$ilk]['r'][$fark] ?? 0) + 1; } }
                $mat = []; $ort = array_fill(1, 6, []);
                foreach ($aylar as $a) { if (empty($koh[$a])) continue; $v = $koh[$a]; $sat = [_crrAyAd($a), $v['n']];
                    for ($k = 1; $k <= 6; $k++) { $hedefAy = date('Y-m', strtotime($a . '-01 +' . $k . ' month')); if ($hedefAy > $bitAy) { $sat[] = null; continue; } $y = round(($v['r'][$k] ?? 0) / $v['n'] * 100, 1); $sat[] = $y; $ort[$k][] = $y; }
                    $sat[] = round($v['c'] / $v['n'], 2); $mat[] = $sat; }
                $ortSat = array_map(fn ($k) => ['Ay ' . $k, $ort[$k] ? round(array_sum($ort[$k]) / count($ort[$k]), 1) : null, ''], range(1, 6));
                return [
                    $pano([_srKpi('Kohort (ay)', count($mat), 'sayi', '115E59'), _srKpi('Kohorttaki müşteri', array_sum(array_column($mat, 1)), 'sayi', '0F766E'), $kpiY('1. ay geri gelme (ort.)', $ortSat[0][1], '059669'), $kpiY('3. ay geri gelme (ort.)', $ortSat[2][1], '047857'), $kpiY('6. ay geri gelme (ort.)', $ortSat[5][1], '064E3B')],
                        [_srBlok('İlk siparişten sonraki aylarda geri gelme (ortalama %)', 'col', '115E59', [['Ay', 'm'], ['Geri gelen %', 'y'], ['', 'm']], array_filter($ortSat, fn ($x) => $x[1] !== null))], $bosSipTum ?? (!$mat ? _crrBos('kohort', $dn) : null)),
                    ['grafiksiz' => true] + _srSayfa('Kohort Tablosu', 'Satır = ilk siparişini o ay veren müşteriler; Ay 1…6 = ilk siparişten 1…6 ay sonra en az bir kez gelenlerin yüzdesi. Boş hücre: o ay henüz gelmedi. Dönem sonu: ' . $dn['bit'] . '.',
                        array_merge([['İlk Sipariş Ayı', 'm', 13], ['Müşteri', 's', 8, 'top']], array_map(fn ($k) => ['Ay ' . $k, 'y', 8, 'mat'], range(1, 6)), [['Kişi Başı Ömür Ciro', 'tl', 14]]), $mat, $bosSipTum ?? _crrBos('kohort', $dn)),
                ];

            // ---------------- MÜŞTERİ DEĞERİ ----------------
            case 'rfm':
                $prof = _crrProfil($sid, $bit); $rfm = _crrRfm($prof); $sat = _crrRfmSat($prof, $rfm); $by = array_column($sat, 1, 0);
                $liste = []; foreach ($rfm as $id => $x) $liste[] = [$prof[$id]['ad'], $x['seg'], $x['r'], $x['f'], $x['m'], $prof[$id]['son'], $prof[$id]['gun'], $prof[$id]['n'], $prof[$id]['ciro']];
                usort($liste, fn ($a, $b) => [array_search($a[1], _crrSegSira()), -$a[8]] <=> [array_search($b[1], _crrSegSira()), -$b[8]]);
                return [
                    $pano([_srKpi('Segmentlenen müşteri', count($rfm), 'sayi', '7C3AED'), _srKpi('Şampiyonlar', $by['Şampiyonlar'] ?? 0, 'sayi', '059669'), _srKpi('Sadık', $by['Sadık müşteriler'] ?? 0, 'sayi', '047857'),
                        _srKpi('Kaybedilmemeli', $by['Kaybedilmemeli'] ?? 0, 'sayi', 'DC2626'), _srKpi('Risk altında', $by['Risk altında'] ?? 0, 'sayi', 'D97706'),
                        $kpiY('Şampiyon + sadıkların ciro payı', ($t = array_sum(array_column($sat, 6))) > 0 ? round(array_sum(array_map(fn ($x) => in_array($x[0], ['Şampiyonlar', 'Sadık müşteriler'], true) ? $x[6] : 0, $sat)) / $t * 100, 1) : null, '6D28D9')],
                        [_srBlok('Segmentlere göre müşteri', 'bar', '7C3AED', [['Segment', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $sat)),
                         _srBlok('Segmentlere göre ömür boyu ciro', 'pie', '6D28D9', [['Segment', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[6], ''], $sat))], $bosSipTum),
                    _crrSayfaRfm($sat, $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                    _srSayfa('Müşteri Listesi', 'R / F / M puanı 1-5 (5 en iyi).', [['Müşteri', 'm', 22], ['Segment', 'm', 16], ['R', 's', 4], ['F', 's', 4], ['M', 's', 4], ['Son Ziyaret', 't', 11], ['Geçen Gün', 's', 9], ['Sipariş', 's', 8], ['Ömür Boyu Ciro', 'tl', 13, 'top bar']], array_slice($liste, 0, 500), $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                ];
            case 'clv':
                $prof = array_filter(_crrProfil($sid, $bit), fn ($p) => $p['n'] > 0); $cirolar = array_column($prof, 'ciro'); sort($cirolar);
                $sat = []; $yillik = [];
                foreach ($prof as $p) {
                    $omur = $p['ilk'] && $p['son'] ? _crrGunFark($p['ilk'], $p['son']) : 0; $aylik = $p['n'] >= 2 && $omur > 0 ? round($p['n'] / max(1, $omur / 30.44), 2) : null;
                    $y = $aylik !== null && $p['gun'] !== null && $p['gun'] <= 120 ? round($p['ciro'] / $p['n'] * min($aylik, 30) * 12, 2) : null; if ($y !== null) $yillik[] = $y;
                    $sat[] = [$p['ad'], $p['ilk'], $p['son'], $p['n'], $p['ciro'], round($p['ciro'] / $p['n'], 2), $aylik, $y, _crrDurum($p)];
                }
                usort($sat, fn ($a, $b) => $b[4] <=> $a[4]);
                $bant = ['0 – 500 ₺' => [0, 500], '500 – 1.000 ₺' => [500, 1000], '1.000 – 2.500 ₺' => [1000, 2500], '2.500 – 5.000 ₺' => [2500, 5000], '5.000 ₺ +' => [5000, INF]]; $bs = [];
                foreach ($bant as $k => [$a, $b]) { $l = array_filter($cirolar, fn ($v) => $v >= $a && $v < $b); $bs[] = [$k, count($l), round(count($l) / max(1, count($cirolar)) * 100, 1), round(array_sum($l), 2), round(array_sum($l) / max(0.01, array_sum($cirolar)) * 100, 1)]; }
                $omurler = array_map(fn ($p) => $p['ilk'] && $p['son'] ? _crrGunFark($p['ilk'], $p['son']) : 0, array_filter($prof, fn ($p) => $p['n'] >= 2));
                return [
                    $pano([$kpiTl('Ort. ömür boyu ciro', $cirolar ? round(array_sum($cirolar) / count($cirolar), 2) : null, '6D28D9'), $kpiTl('Medyan', $cirolar ? $cirolar[intdiv(count($cirolar), 2)] : null, '7C3AED'),
                        $kpiS('Ort. sipariş sayısı', $prof ? round(array_sum(array_column($prof, 'n')) / count($prof), 2) : null, '5B21B6'), $kpiS('Ort. müşteri ömrü (gün, 2+ sipariş)', $omurler ? round(array_sum($omurler) / count($omurler), 0) : null, '4C1D95'),
                        $kpiTl('Ort. tahmini yıllık değer (aktif tekrarcı)', $yillik ? round(array_sum($yillik) / count($yillik), 2) : null, '059669'), _srKpi('Müşteri', count($prof), 'sayi', '64748B')],
                        [_srBlok('Değer bantlarına göre müşteri ve ciro', 'col', '6D28D9', [['Bant', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $bs)),
                         _srBlok('Bantların ciro payı', 'pie', '7C3AED', [['Bant', 'm'], ['Ciro', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], array_filter($bs, fn ($x) => $x[3] > 0)))], $bosSipTum),
                    _srSayfa('En Değerli 100 Müşteri', 'Aylık sıklık = sipariş ÷ (ilk–son ziyaret arası ay). Tahmini yıllık değer = ortalama sepet × aylık sıklık × 12; yalnız 2+ siparişli ve son 120 günde gelmiş müşteride.',
                        [['Müşteri', 'm', 22], ['İlk Sipariş', 't', 11], ['Son Sipariş', 't', 11], ['Sipariş', 's', 8, 'top'], ['Ömür Boyu Ciro', 'tl', 13, 'top bar'], ['Ort. Sepet', 'tl', 11], ['Aylık Sıklık', 's', 9], ['Tahmini Yıllık Değer', 'tl', 14], ['Durum', 'm', 12]], array_slice($sat, 0, 100), $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                    _srSayfa('Değer Bantları', 'Ömür boyu ciroya göre.', [['Bant', 'm', 16], ['Müşteri', 's', 9, 'top'], ['Pay %', 'y', 8], ['Ciro', 'tl', 14, 'top bar'], ['Ciro Payı %', 'y', 10]], $bs, $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                ];
            case 'harcama':
                $s = _crrDonemSiparis($sid, $bas, $bit); $so = _crrDonemSiparis($sid, $dn['obas'], $dn['obit']); $k = []; foreach ($s as $x) { $k[$x['mid']]['c'] = ($k[$x['mid']]['c'] ?? 0) + $x['toplam']; $k[$x['mid']]['n'] = ($k[$x['mid']]['n'] ?? 0) + 1; }
                $ko = []; foreach ($so as $x) $ko[$x['mid']] = ($ko[$x['mid']] ?? 0) + $x['toplam'];
                $c = array_sum(array_column($s, 'toplam')); $co = array_sum($ko); $prof = _crrProfil($sid, $bit);
                $bant = ['0 – 250 ₺' => [0, 250], '250 – 500 ₺' => [250, 500], '500 – 1.000 ₺' => [500, 1000], '1.000 – 2.500 ₺' => [1000, 2500], '2.500 ₺ +' => [2500, INF]]; $bs = [];
                foreach ($bant as $bk => [$a, $b]) { $l = array_filter($k, fn ($v) => $v['c'] >= $a && $v['c'] < $b); $bs[] = [$bk, count($l), round(count($l) / max(1, count($k)) * 100, 1), round(array_sum(array_column($l, 'c')), 2)]; }
                $top = array_map(fn ($m, $v) => [_crrAd($mu[$m] ?? null, $m), $v['n'], round($v['c'], 2), round($v['c'] / $v['n'], 2), round($ko[$m] ?? 0, 2), isset($prof[$m]) ? _crrDurum($prof[$m]) : '—'], array_keys($k), array_values($k));
                usort($top, fn ($a, $b) => $b[2] <=> $a[2]);
                $hf = []; foreach ($s as $x) { $w = date('Y-m-d', strtotime('monday this week', strtotime($x['tarih']))); $hf[$w]['c'] = ($hf[$w]['c'] ?? 0) + $x['toplam']; $hf[$w]['n'] = ($hf[$w]['n'] ?? 0) + 1; } ksort($hf);
                return [
                    $pano([$kpiTl('Müşteri başı harcama', count($k) ? round($c / count($k), 2) : null, '5B21B6'), $kpiTl('Önceki dönem', count($ko) ? round($co / count($ko), 2) : null, '64748B'),
                        $kpiDeg('Değişim', count($k) && count($ko) ? _srYuzde($c / count($k), $co / count($ko)) : null, '7C3AED'), $kpiTl('Ortalama sepet (müşterili)', count($s) ? round($c / count($s), 2) : null, '6D28D9'),
                        _srKpi('Müşterili ciro', round($c, 2), 'tl', '4C1D95'), _srKpi('Harcayan müşteri', count($k), 'sayi', '0D9488')],
                        [_srBlok('Harcama bantlarına göre müşteri', 'col', '5B21B6', [['Bant', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $bs)),
                         _srBlok('Haftalara göre ortalama sepet', 'col', '7C3AED', [['Hafta', 'm'], ['Ort. sepet', 'tl'], ['', 'm']], array_map(fn ($w, $v) => [date('d.m', strtotime($w)), round($v['c'] / $v['n'], 2), ''], array_keys($hf), array_values($hf)))], !$s ? $bosSip : null),
                    _srSayfa('Harcama Bantları', 'Müşterinin bu dönemdeki toplam harcamasına göre.', [['Bant', 'm', 16], ['Müşteri', 's', 9, 'top bar'], ['Pay %', 'y', 8], ['Ciro', 'tl', 14, 'top']], $bs, $bosSip),
                    _srSayfa('En Çok Harcayanlar', '', [['Müşteri', 'm', 22], ['Sipariş', 's', 8, 'top'], ['Dönem Harcaması', 'tl', 14, 'top bar'], ['Ort. Sepet', 'tl', 11], ['Önceki Dönem', 'tl', 12], ['Durum', 'm', 12]], array_slice($top, 0, 100), $bosSip),
                ];
            case 'vip':
                $prof = array_filter(_crrProfil($sid, $bit), fn ($p) => $p['n'] > 0); $s = _crrDonemSiparis($sid, $bas, $bit); $dc = []; foreach ($s as $x) $dc[$x['mid']] = ($dc[$x['mid']] ?? 0) + $x['toplam'];
                $vip = array_filter($prof, fn ($p) => $p['ciro'] >= 5000); uasort($vip, fn ($a, $b) => $b['ciro'] <=> $a['ciro']);
                $vSat = array_map(fn ($p) => [$p['ad'], _crrTlf($p['tel']), $p['ciro'], $p['n'], $p['son'], $p['gun'], _crrDurum($p), round($dc[$p['id']] ?? 0, 2)], array_values($vip));
                $cir = array_column($prof, 'ciro'); rsort($cir); $tc = max(0.01, array_sum($cir)); $n = count($cir);
                $par = []; foreach ([['İlk %10', 0.1], ['İlk %20', 0.2], ['İlk %50', 0.5], ['Tümü', 1]] as [$ad, $o]) { $k = max(1, (int) ceil($n * $o)); $par[] = [$ad, $n ? $k : 0, round(array_sum(array_slice($cir, 0, $k)), 2), $n ? round(array_sum(array_slice($cir, 0, $k)) / $tc * 100, 1) : null]; }
                $uz = count(array_filter($vip, fn ($p) => $p['gun'] !== null && $p['gun'] > 30));
                return [
                    $pano([_srKpi('VIP müşteri (5.000 ₺+)', count($vip), 'sayi', '4C1D95'), $kpiY('VIP\'lerin ciro payı', $oran(array_sum(array_column($vip, 'ciro')), $tc), '6D28D9'), $kpiY('İlk %20 müşterinin ciro payı', $n ? $par[1][3] : null, '7C3AED'),
                        _srKpi('30+ gündür gelmeyen VIP', $uz, 'sayi', 'DC2626'), _srKpi('Dönemde gelen VIP', count(array_filter($vip, fn ($p) => isset($dc[$p['id']]))), 'sayi', '059669')],
                        [_srBlok('Pareto: müşteri dilimlerinin ciro payı', 'col', '4C1D95', [['Dilim', 'm'], ['Ciro payı %', 'y'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[3], ''], array_slice($par, 0, 3)))], $bosSipTum ?? (!$vip ? _crrBos('vip', $dn) : null)),
                    _srSayfa('VIP Müşteriler', 'Ömür boyu 5.000 ₺ ve üzeri harcayanlar (Müşteri 360 VIP tanımı). 30 günden uzun süredir gelmeyen VIP\'e kişisel ilgi gösterin.', [['Müşteri', 'm', 22], ['Telefon', 'm', 12], ['Ömür Boyu Ciro', 'tl', 14, 'top bar'], ['Sipariş', 's', 8], ['Son Ziyaret', 't', 11], ['Geçen Gün', 's', 9, 'esik:31'], ['Durum', 'm', 12], ['Dönem Harcaması', 'tl', 13, 'top']], $vSat, $bosSipTum ?? _crrBos('vip', $dn)),
                    _srSayfa('Pareto (Ciro Yoğunlaşması)', 'Müşteriler ömür boyu ciroya göre sıralanıp dilimlere ayrıldı. İlk %20 genelde cironun %60-80\'ini getirir; yüksekse bu küçük grubu kaybetmek riskli.', [['Dilim', 'm', 12], ['Müşteri', 's', 9], ['Ciro', 'tl', 14, 'bar'], ['Ciro Payı %', 'y', 10]], $par, $bosSipTum ?? _crrBos('siparis_yok', $dn)),
                ];

            // ---------------- KAYIP & GERİ KAZANIM ----------------
            case 'risk':
                $r = _crrRisk($sid, $bit); $seg = []; foreach ($r as $x) $seg[$x['seg']] = ($seg[$x['seg']] ?? 0) + 1;
                return [
                    $pano([_srKpi('Alışkanlığını kıran müşteri', count($r), 'sayi', 'DC2626'), _srKpi('Ömür boyu ciroları', array_sum(array_column($r, 'ciro')), 'tl', 'B91C1C'), _srKpi('Tahmini yıllık değer kaybı', array_sum(array_column($r, 'yillik')), 'tl', '991B1B'),
                        _srKpi('Kaybedilmemeli segmentinden', $seg['Kaybedilmemeli'] ?? 0, 'sayi', '7F1D1D'), $kpiS('Ort. gecikme katı', $r ? round(array_sum(array_column($r, 'kat')) / count($r), 1) : null, '64748B')],
                        [_srBlok('En değerli 10 risk', 'bar', 'DC2626', [['Müşteri', 'm'], ['Ömür ciro', 'tl'], ['Geçen gün', 's']], array_map(fn ($x) => [$x['ad'], $x['ciro'], $x['gun']], array_slice($r, 0, 10))),
                         _srBlok('RFM segmentine göre', 'pie', 'B91C1C', [['Segment', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($seg), array_values($seg)))], $bosSipTum ?? (!$r ? _crrBos('risk', $dn) : null)),
                    _crrSayfaRisk($r, $bosSipTum ?? _crrBos('risk', $dn)),
                ];
            case 'geri_kazanim':
                $g = _crrGeriKazanim($sid, $bas, $bit); $go = _crrGeriKazanim($sid, $dn['obas'], $dn['obit']);
                $sat = array_map(fn ($x) => [_crrAd($mu[$x['mid']] ?? null, $x['mid']), $x['onceki'], $x['donus'], $x['ara'], $x['n'], $x['ciro']], array_values($g)); usort($sat, fn ($a, $b) => $b[5] <=> $a[5]);
                $ab = ['61-90 gün' => [61, 90], '91-180 gün' => [91, 180], '181-365 gün' => [181, 365], '1 yıl +' => [366, 99999]]; $as = [];
                foreach ($ab as $k => [$a, $b]) $as[] = [$k, count(array_filter($g, fn ($x) => $x['ara'] >= $a && $x['ara'] <= $b)), ''];
                return [
                    $pano([_srKpi('Geri kazanılan müşteri', count($g), 'sayi', 'B91C1C'), _srKpi('Önceki dönem', count($go), 'sayi', '64748B'), _srKpi('Dönemde getirdikleri ciro', array_sum(array_column($g, 'ciro')), 'tl', '059669'),
                        $kpiS('Ort. ara (gün)', $g ? round(array_sum(array_column($g, 'ara')) / count($g), 0) : null, '991B1B'), _srKpi('Dönemde 2+ kez gelen', count(array_filter($g, fn ($x) => $x['n'] >= 2)), 'sayi', '047857')],
                        [_srBlok('Aradan geçen süreye göre', 'col', 'B91C1C', [['Ara', 'm'], ['Müşteri', 's'], ['', 'm']], $as)], $bosSipTum ?? (!$g ? _crrBos('geri', $dn) : null)),
                    _srSayfa('Geri Kazanılanlar', 'Önceki siparişinden en az 60 gün sonra bu dönemde yeniden sipariş veren müşteriler. Dönemde 2+ kez gelmişse geri kazanım kalıcılaşıyor demektir.', [['Müşteri', 'm', 22], ['Önceki Ziyaret', 't', 12], ['Dönüş', 't', 11], ['Ara (gün)', 's', 9], ['Dönemde Sipariş', 's', 12, 'top'], ['Dönem Harcaması', 'tl', 13, 'top bar']], $sat, $bosSipTum ?? _crrBos('geri', $dn)),
                ];

            // ---------------- MEMNUNİYET & ŞİKAYET ----------------
            case 'memnuniyet':
                $mem = _crrMemnuniyet($sid, $bas, $bit); $memo = _crrMemnuniyet($sid, $dn['obas'], $dn['obit']); $deg = _crrDegerlendirme($sid, $bas, $bit);
                $tum = array_merge(array_column($mem, 'puan'), array_column($deg, 'puan')); $tumo = array_merge(array_column($memo, 'puan'), array_column(_crrDegerlendirme($sid, $dn['obas'], $dn['obit']), 'puan'));
                $gun = []; foreach ($mem as $x) $gun[$x['tarih']][] = $x['puan']; foreach ($deg as $x) $gun[$x['tarih']][] = $x['puan'];
                $gt = array_map(fn ($t) => [$t, count($gun[$t] ?? []), _crrOrt($gun[$t] ?? []), count(array_filter($gun[$t] ?? [], fn ($p) => $p >= 4)), count(array_filter($gun[$t] ?? [], fn ($p) => $p <= 2))], _crrGunler($bas, $bit));
                $ms = []; foreach ($mem as $x) { $ms[$x['masa']][] = $x['puan']; } $mSat = array_map(fn ($k, $v) => [$k, count($v), _crrOrt($v), count(array_filter($v, fn ($p) => $p <= 2))], array_keys($ms), array_values($ms)); usort($mSat, fn ($a, $b) => [$a[2], -$a[1]] <=> [$b[2], -$b[1]]);
                $ks = []; foreach ($mem as $x) $ks[$x['kanal'] === 'qr' ? 'Masa QR' : ucfirst($x['kanal'])][] = $x['puan']; if ($deg) $ks['Sipariş değerlendirmesi'] = array_column($deg, 'puan');
                $bos = !$d['memnuniyet'] && !$d['degerlendirme'] ? _crrBos('mem_yok', $dn) : _crrBos('mem', $dn);
                return [
                    $pano([_srKpi('Geri bildirim', count($tum), 'sayi', '16A34A'), $kpiS('Ortalama puan (5)', _crrOrt($tum), '15803D'), $kpiS('Önceki dönem', _crrOrt($tumo), '64748B'),
                        $kpiY('Memnun oranı (4-5)', $oran(count(array_filter($tum, fn ($p) => $p >= 4)), count($tum)), '059669'), $kpiY('Tavsiye skoru', _crrTavsiye($tum), '166534'), $kpiY('Önceki dönem tavsiye skoru', _crrTavsiye($tumo), '64748B'),
                        _srKpi('Düşük puan (1-2)', count(array_filter($tum, fn ($p) => $p <= 2)), 'sayi', 'DC2626')],
                        [_srBlok('Puan dağılımı', 'col', '16A34A', [['Puan', 'm'], ['Adet', 's'], ['', 'm']], array_map(fn ($p) => [$p . ' yıldız', count(array_filter($tum, fn ($x) => $x === $p)), ''], [5, 4, 3, 2, 1])),
                         _srBlok('Kanala göre ortalama puan', 'bar', '15803D', [['Kanal', 'm'], ['Ort. puan', 's'], ['Adet', 's']], array_map(fn ($k, $v) => [$k, _crrOrt($v), count($v)], array_keys($ks), array_values($ks)))], !$tum ? $bos : null),
                    ['grafiksiz' => true] + _srSayfa('Günlük', 'Tavsiye skoru 1-5 ölçekte hesaplanır: (5 verenler − 1/2/3 verenler) ÷ toplam. Gerçek NPS 0-10 sorulur; bu ona yakın bir göstergedir.', [['Tarih', 't', 11], ['Geri Bildirim', 's', 11, 'top bar'], ['Ort. Puan', 's', 9], ['Memnun (4-5)', 's', 11, 'top'], ['Düşük (1-2)', 's', 10, 'top esik:1']], $gt, $bos),
                    _srSayfa('Masaya Göre', 'QR memnuniyet puanı; en düşük ortalama üstte.', [['Masa', 'm', 14], ['Geri Bildirim', 's', 11, 'top bar'], ['Ort. Puan', 's', 9], ['Düşük (1-2)', 's', 10, 'top esik:1']], $mSat, !$d['memnuniyet'] ? _crrBos('mem_yok', $dn) : _crrBos('mem', $dn)),
                ];
            case 'sikayet':
                $mem = _crrMemnuniyet($sid, $bas, $bit); $dus = array_values(array_filter($mem, fn ($x) => $x['puan'] <= 2)); $cz = array_filter($dus, fn ($x) => $x['durum'] === 'cozuldu');
                $sure = array_values(array_filter(array_map(fn ($x) => $x['cozuldu'] ? ($x['cozuldu'] - $x['ts']) / 3600 : null, $cz), fn ($v) => $v !== null && $v >= 0));
                $seb = []; foreach ($dus as $x) foreach ($x['sebepler'] ?: ['Sebep seçilmemiş'] as $s) $seb[$s] = ($seb[$s] ?? 0) + 1; arsort($seb);
                $ms = []; foreach ($dus as $x) $ms[$x['masa']] = ($ms[$x['masa']] ?? 0) + 1; arsort($ms);
                $acik = $acikSikayet(); $tumMem = count($mem);
                $liste = array_map(fn ($x) => [$x['tarih'], date('H:i', $x['ts']), $x['masa'], $x['puan'], implode(', ', $x['sebepler']) ?: '—', $x['not'] ?: '—', $x['durum'] === 'cozuldu' ? 'Çözüldü' : 'Açık', $x['cozuldu'] ? round(($x['cozuldu'] - $x['ts']) / 3600, 1) : null], $dus);
                $bos = !$d['memnuniyet'] ? _crrBos('mem_yok', $dn) : _crrBos('sikayet', $dn);
                return [
                    $pano([_srKpi('Düşük puan (1-2)', count($dus), 'sayi', 'DC2626'), $kpiY('Tüm geri bildirimdeki payı', $oran(count($dus), $tumMem), 'B91C1C'), _srKpi('Çözülen', count($cz), 'sayi', '059669'), $kpiY('Çözüm oranı', $oran(count($cz), count($dus)), '15803D'),
                        $kpiS('Ort. çözüm süresi (saat)', $sure ? round(array_sum($sure) / count($sure), 1) : null, '0F766E'), _srKpi('Şu an açık (tüm zamanlar)', count($acik), 'sayi', '991B1B'), _srKpi('En sık sebep', $seb ? array_key_first($seb) : '—', 'metin', '64748B')],
                        [_srBlok('Şikayet sebepleri', 'bar', 'DC2626', [['Sebep', 'm'], ['Adet', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($seb), array_values($seb))),
                         _srBlok('Masalara göre', 'pie', 'B91C1C', [['Masa', 'm'], ['Şikayet', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_slice(array_keys($ms), 0, 10), array_slice(array_values($ms), 0, 10)))], !$dus ? $bos : null),
                    _srSayfa('Düşük Puanlar', 'Dönemde 1-2 puan verilen QR memnuniyet kayıtları. Çözüm süresi = kayıttan "çözüldü" işaretine kadar (saat).', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Masa', 'm', 10], ['Puan', 's', 6], ['Sebepler', 'm', 22], ['Not', 'm', 36], ['Durum', 'm', 9], ['Çözüm (saat)', 's', 10, 'esik:24']], $liste, $bos),
                    _srSayfa('Sebebe Göre', 'Bir şikayette birden çok sebep seçilebilir.', [['Sebep', 'm', 24], ['Adet', 's', 8, 'top bar'], ['Pay %', 'y', 8]], array_map(fn ($k, $v) => [$k, $v, round($v / max(1, count($dus)) * 100, 1)], array_keys($seb), array_values($seb)), $bos),
                    _crrSayfaSikayet($acik, 'Açık (çözülmemiş) şikayet yok.'),
                ];
            case 'degerlendirme':
                $deg = _crrDegerlendirme($sid, $bas, $bit); $dego = _crrDegerlendirme($sid, $dn['obas'], $dn['obit']);
                $alt = ['Lezzet' => _crrOrt(array_column($deg, 'lezzet')), 'Servis' => _crrOrt(array_column($deg, 'servis')), 'Hız' => _crrOrt(array_column($deg, 'hiz'))]; $altF = array_filter($alt, fn ($v) => $v !== null); asort($altF);
                $altO = ['Lezzet' => _crrOrt(array_column($dego, 'lezzet')), 'Servis' => _crrOrt(array_column($dego, 'servis')), 'Hız' => _crrOrt(array_column($dego, 'hiz'))];
                $yor = array_map(fn ($x) => [$x['tarih'], $x['puan'], $x['lezzet'] ?: null, $x['servis'] ?: null, $x['hiz'] ?: null, $x['mid'] ? _crrAd($mu[$x['mid']] ?? null, $x['mid']) : 'Anonim', $x['yorum'] ?: '—'], array_reverse($deg));
                $bos = !$d['degerlendirme'] ? _crrBos('deg_yok', $dn) : _crrBos('deg', $dn);
                return [
                    $pano([_srKpi('Değerlendirme', count($deg), 'sayi', '166534'), $kpiS('Genel puan (5)', _crrOrt(array_column($deg, 'puan')), '15803D'), $kpiS('Lezzet', $alt['Lezzet'], '16A34A'), $kpiS('Servis', $alt['Servis'], '059669'), $kpiS('Hız', $alt['Hız'], '047857'),
                        _srKpi('En zayıf alan', $altF ? array_key_first($altF) : '—', 'metin', 'DC2626'), _srKpi('Yorum yazılan', count(array_filter($deg, fn ($x) => $x['yorum'] !== '')), 'sayi', '64748B')],
                        [_srBlok('Alt puanlar: bu dönem ve önceki', 'col', '166534', [['Alan', 'm'], ['Bu dönem', 's'], ['Önceki', 's']], array_map(fn ($k) => [$k, $alt[$k] ?? 0, $altO[$k] ?? 0], array_keys($alt)))], !$deg ? $bos : null),
                    _srSayfa('Değerlendirmeler ve Yorumlar', 'En yeni üstte. Alt puan verilmemişse boş.', [['Tarih', 't', 11], ['Genel', 's', 6], ['Lezzet', 's', 7], ['Servis', 's', 7], ['Hız', 's', 6], ['Müşteri', 'm', 18], ['Yorum', 'm', 56]], $yor, $bos),
                ];
            case 'urun_puan':
                $up = []; if (_srTablo('urun_puanlari')) { try { $up = DB::table('urun_puanlari')->where('sube_id', $sid)->whereBetween('created_at', [$bas . ' 00:00:00', $bit . ' 23:59:59'])->get(['urun_id', 'puan'])->all(); } catch (\Throwable $e) {} }
                $ad = []; if (_srTablo('urunler')) { try { $ad = DB::table('urunler')->where('sube_id', $sid)->pluck('ad', 'id')->all(); } catch (\Throwable $e) {} }
                $g = []; foreach ($up as $r) $g[(int) $r->urun_id][] = (int) $r->puan;
                $sat = array_map(fn ($u, $l) => [$ad[$u] ?? 'Ürün #' . $u, count($l), _crrOrt($l), count(array_filter($l, fn ($p) => $p >= 4)), count(array_filter($l, fn ($p) => $p <= 2)), round(count(array_filter($l, fn ($p) => $p <= 2)) / count($l) * 100, 1)], array_keys($g), array_values($g));
                usort($sat, fn ($a, $b) => [$a[2], -$a[1]] <=> [$b[2], -$b[1]]);
                $iyi = $sat; usort($iyi, fn ($a, $b) => [$b[2], $b[1]] <=> [$a[2], $a[1]]);
                $bos = !$d['urun_puan'] ? _crrBos('urun_yok', $dn) : _crrBos('urun', $dn);
                return [
                    $pano([_srKpi('Oy', count($up), 'sayi', '14532D'), _srKpi('Puanlanan ürün', count($g), 'sayi', '166534'), $kpiS('Ortalama puan', _crrOrt(array_map(fn ($r) => (int) $r->puan, $up)), '15803D'),
                        _srKpi('Ortalaması 3\'ün altındaki ürün', count(array_filter($sat, fn ($x) => $x[2] !== null && $x[2] < 3)), 'sayi', 'DC2626'), _srKpi('En beğenilen', $iyi ? $iyi[0][0] : '—', 'metin', '059669')],
                        [_srBlok('En düşük puanlı 10 ürün', 'bar', 'DC2626', [['Ürün', 'm'], ['Ort. puan', 's'], ['Oy', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[1]], array_slice($sat, 0, 10))),
                         _srBlok('En yüksek puanlı 10 ürün', 'bar', '059669', [['Ürün', 'm'], ['Ort. puan', 's'], ['Oy', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[1]], array_slice($iyi, 0, 10)))], !$up ? $bos : null),
                    _srSayfa('Ürün Puanları', 'QR menüde verilen 1-5 yıldız; aynı cihaz aynı ürüne bir kez oy verir. Az oylu ürünlerde ortalama yanıltıcı olabilir. En düşük ortalama üstte.', [['Ürün', 'm', 26], ['Oy', 's', 7, 'top bar'], ['Ort. Puan', 's', 9], ['4-5 Yıldız', 's', 9, 'top'], ['1-2 Yıldız', 's', 9, 'top esik:1'], ['Düşük Oy %', 'y', 10, 'esik:30']], $sat, $bos),
                ];

            // ---------------- SADAKAT & ÖDÜL ----------------
            case 'puan':
                $pu = _crrPuan($sid, $bas, $bit); $kaz = ['Siparişten' => array_sum(array_column($pu['siparis'], 'puan')), 'Çarkıfelekten' => array_sum(array_column($pu['cark'], 'puan')), 'Kampanya kartından' => array_sum(array_column($pu['kart'], 'puan'))];
                $harc = array_sum(array_column($pu['harcama'], 'puan')); $bakiye = array_sum(array_map(fn ($m) => max(0, $m->puan), $mu));
                $gun = []; foreach (['siparis', 'cark', 'kart'] as $k) foreach ($pu[$k] as $x) $gun[$x['tarih']]['k'] = ($gun[$x['tarih']]['k'] ?? 0) + $x['puan']; foreach ($pu['harcama'] as $x) $gun[$x['tarih']]['h'] = ($gun[$x['tarih']]['h'] ?? 0) + $x['puan'];
                $gt = array_map(fn ($t) => [$t, $gun[$t]['k'] ?? 0, $gun[$t]['h'] ?? 0], _crrGunler($bas, $bit));
                $ust = $mu; usort($ust, fn ($a, $b) => $b->puan <=> $a->puan); $sp = _crrSiparis($sid);
                $ustSat = array_map(fn ($m) => [_crrAd($m, $m->id), $m->puan, count($sp[$m->id] ?? []) ?: $m->siparis, ($l = $sp[$m->id] ?? []) ? $l[count($l) - 1][0] : null], array_slice(array_values(array_filter($ust, fn ($m) => $m->puan > 0)), 0, 50));
                $bant = ['1-99' => [1, 99], '100-199' => [100, 199], '200-499' => [200, 499], '500+' => [500, PHP_INT_MAX]]; $bs = []; foreach ($bant as $k => [$a, $b]) $bs[] = [$k . ' puan', count(array_filter($mu, fn ($m) => $m->puan >= $a && $m->puan <= $b)), ''];
                $topKaz = array_sum($kaz); $bos = !$d['siparis'] && !$d['puan'] ? _crrBos('siparis_yok', $dn) : _crrBos('puan', $dn);
                return [
                    $pano([_srKpi('Kazanılan puan', $topKaz, 'sayi', 'DB2777'), _srKpi('Harcanan puan (ödül)', $harc, 'sayi', 'BE185D'), $kpiY('Harcama / kazanım', $oran($harc, $topKaz), '9D174D'),
                        _srKpi('Müşterilerdeki toplam bakiye', $bakiye, 'sayi', '831843'), _srKpi('Puanı olan müşteri', count(array_filter($mu, fn ($m) => $m->puan > 0)), 'sayi', '64748B'),
                        _srKpi('Ödüle yetecek puanı olan (100+)', count(array_filter($mu, fn ($m) => $m->puan >= 100)), 'sayi', '059669')],
                        [_srBlok('Kazanım kaynağı', 'pie', 'DB2777', [['Kaynak', 'm'], ['Puan', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($kaz), array_values($kaz))),
                         _srBlok('Günlere göre kazanılan ve harcanan', 'col', 'BE185D', [['Gün', 'm'], ['Kazanılan', 's'], ['Harcanan', 's']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $gt)),
                         _srBlok('Puan bakiyesine göre müşteri', 'col', '9D174D', [['Bakiye', 'm'], ['Müşteri', 's'], ['', 'm']], $bs)], !$topKaz && !$harc ? $bos : null),
                    ['grafiksiz' => true] + _srSayfa('Günlük Puan', 'Kazanım türetilir: müşteriye bağlı her ödenmiş adisyonda her 10 ₺ = 1 puan + çark puanı + kampanya kartı puanı. Harcama: kasada onaylanan ödül kodunun puanı.', [['Tarih', 't', 11], ['Kazanılan', 's', 10, 'top bar'], ['Harcanan', 's', 10, 'top']], $gt, $bos),
                    _srSayfa('En Yüksek Bakiyeler', 'Bugünkü puan bakiyesi. Yüksek bakiyeli ama uzun süredir gelmeyen müşteriye "puanın seni bekliyor" hatırlatması etkilidir.', [['Müşteri', 'm', 22], ['Puan', 's', 9, 'top bar'], ['Sipariş', 's', 8], ['Son Ziyaret', 't', 11]], $ustSat, 'Puan bakiyesi olan müşteri yok.'),
                ];
            case 'odul':
                $od = _crrOdul($sid, $bas, $bit); $kul = array_values(array_filter($od, fn ($x) => $x['durum'] === 'Kullanıldı' && $x['kullanildi'] >= $bas && $x['kullanildi'] <= $bit)); $ure = array_values(array_filter($od, fn ($x) => $x['olustu'] >= $bas && $x['olustu'] <= $bit));
                $per = []; if (_srTablo('personeller')) { try { $per = DB::table('personeller')->where('sube_id', $sid)->pluck('ad', 'id')->all(); } catch (\Throwable $e) {} }
                $g = []; foreach ($ure as $x) { $y = &$g[$x['ad'] . '|' . $x['kaynak']]; $y['ad'] = $x['ad']; $y['k'] = $x['kaynak']; $y['u'] = ($y['u'] ?? 0) + 1; if ($x['durum'] === 'Kullanıldı') $y['kl'] = ($y['kl'] ?? 0) + 1; if ($x['durum'] === 'Süresi doldu') $y['sd'] = ($y['sd'] ?? 0) + 1; $y['p'] = ($y['p'] ?? 0) + ($x['durum'] === 'Kullanıldı' ? $x['puan'] : 0); unset($y); }
                $gSat = array_map(fn ($v) => [$v['ad'], $v['k'], $v['u'], $v['kl'] ?? 0, $v['sd'] ?? 0, round(($v['kl'] ?? 0) / $v['u'] * 100, 1), $v['p'] ?? 0], array_values($g)); usort($gSat, fn ($a, $b) => $b[3] <=> $a[3]);
                $ks = []; foreach ($kul as $x) { $k = $per[$x['kasiyer']] ?? ($x['kasiyer'] ? 'Personel #' . $x['kasiyer'] : 'Bilinmiyor'); $ks[$k] = ($ks[$k] ?? 0) + 1; } arsort($ks);
                $liste = array_map(fn ($x) => [$x['olustu'], $x['kullanildi'], _crrAd($mu[$x['mid']] ?? null, $x['mid']), $x['ad'], $x['kaynak'], $x['puan'], $x['durum'], $per[$x['kasiyer']] ?? '—'], $od);
                $mal = _crrOdulMaliyet($sid, $bas, $bit); $bos = !$d['odul'] ? _crrBos('odul_yok', $dn) : _crrBos('odul', $dn);
                return [
                    $pano([_srKpi('Üretilen ödül kodu', count($ure), 'sayi', 'BE185D'), _srKpi('Kullanılan', count($kul), 'sayi', '059669'), $kpiY('Kullanılma oranı (üretilenler)', $oran(count(array_filter($ure, fn ($x) => $x['durum'] === 'Kullanıldı')), count($ure)), 'DB2777'),
                        _srKpi('Süresi dolan', count(array_filter($ure, fn ($x) => $x['durum'] === 'Süresi doldu')), 'sayi', 'D97706'), _srKpi('Harcanan puan', array_sum(array_column($kul, 'puan')), 'sayi', '9D174D'),
                        _srKpi('Ödül maliyeti (hesaba işlenen)', $mal, 'tl', 'DC2626'), _srKpi('Kullanan müşteri', count(array_unique(array_column($kul, 'mid'))), 'sayi', '64748B')],
                        [_srBlok('Ödüle göre kullanım', 'bar', 'BE185D', [['Ödül', 'm'], ['Kullanılan', 's'], ['Üretilen', 's']], array_map(fn ($x) => [$x[0], $x[3], $x[2]], array_slice($gSat, 0, 10))),
                         _srBlok('Onaylayan kasiyer', 'pie', '9D174D', [['Kasiyer', 'm'], ['Ödül', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v, ''], array_keys($ks), array_values($ks)))], !$od ? $bos : null),
                    _srSayfa('Ödüle Göre', 'Üretilen = müşterinin uygulamada "Kullan" deyip kod aldığı (puan kodu 10 dk geçerlidir); kullanılan = kasada Ödül Doğrula ile onaylanan. Maliyet adisyona işlenen indirim / ikram tutarıdır (iptal_indirim kaydı "Ödül:").',
                        [['Ödül', 'm', 26], ['Kaynak', 'm', 16], ['Üretilen', 's', 9, 'top'], ['Kullanılan', 's', 10, 'top bar'], ['Süresi Dolan', 's', 11, 'top'], ['Kullanım %', 'y', 10], ['Harcanan Puan', 's', 12, 'top']], $gSat, $bos),
                    _srSayfa('Ödül Kodları', '', [['Oluşma', 't', 11], ['Kullanım', 't', 11], ['Müşteri', 'm', 20], ['Ödül', 'm', 24], ['Kaynak', 'm', 16], ['Puan', 's', 7], ['Durum', 'm', 12], ['Kasiyer', 'm', 14]], $liste, $bos),
                ];
            case 'cark':
                $ck = _crrCark($sid, $bas, $bit); $cko = _crrCark($sid, $dn['obas'], $dn['obit']); $sp = _crrSiparis($sid);
                $dil = []; foreach ($ck as $x) { $y = &$dil[$x['dilim']]; $y['t'] = _crrCarkTipAd($x['tip']); $y['n'] = ($y['n'] ?? 0) + 1; $y['d'] = ($y['d'] ?? 0) + $x['deger']; unset($y); } uasort($dil, fn ($a, $b) => $b['n'] <=> $a['n']);
                $kup = array_values(array_filter(array_column($ck, 'kupon'))); $kulKup = 0;
                if ($kup && _srTablo('odul_kullanimlari')) { try { $kulKup = (int) DB::table('odul_kullanimlari')->whereIn('kod', $kup)->where('durum', 'kullanildi')->count(); } catch (\Throwable $e) {} }
                $mus = []; foreach ($ck as $x) $mus[$x['mid']] = $x['tarih']; $sonra = 0; foreach ($ck as $x) { foreach ($sp[$x['mid']] ?? [] as $s) if ($s[0] >= $x['tarih'] && $s[0] <= date('Y-m-d', strtotime($x['tarih'] . ' +13 day'))) { $sonra++; break; } }
                $bos = !$d['cark'] ? _crrBos('cark_yok', $dn) : _crrBos('cark', $dn);
                return [
                    $pano([_srKpi('Çevirme', count($ck), 'sayi', '9D174D'), $kpiDeg('Önceki döneme göre', _srYuzde(count($ck), count($cko)), '64748B'), _srKpi('Çeviren müşteri', count($mus), 'sayi', 'BE185D'),
                        _srKpi('Verilen puan', array_sum(array_map(fn ($x) => $x['tip'] === 'puan' ? $x['deger'] : 0, $ck)), 'sayi', 'DB2777'), _srKpi('Verilen kupon', count($kup), 'sayi', 'EC4899'), $kpiY('Kupon kullanım oranı', $oran($kulKup, count($kup)), '059669'),
                        $kpiY('Çevirdikten sonra 14 günde sipariş', $oran($sonra, count($ck)), '047857')],
                        [_srBlok('Dilimlere göre çıkış', 'pie', '9D174D', [['Dilim', 'm'], ['Çevirme', 's'], ['', 'm']], array_map(fn ($k, $v) => [$k, $v['n'], ''], array_keys($dil), array_values($dil)))], !$ck ? $bos : null),
                    _srSayfa('Dilimler', 'Değer: puan dilimlerinde puan, indirim dilimlerinde yüzde / tutar toplamı.', [['Dilim', 'm', 24], ['Tür', 'm', 16], ['Çevirme', 's', 9, 'top bar'], ['Pay %', 'y', 8], ['Değer Toplamı', 's', 12, 'top']], array_map(fn ($k, $v) => [$k, $v['t'], $v['n'], round($v['n'] / max(1, count($ck)) * 100, 1), round($v['d'], 2)], array_keys($dil), array_values($dil)), $bos),
                ];
            case 'cuzdan':
                $cz = _crrCuzdan($sid, $bas, $bit); $tip = ['yukleme' => 'Yükleme', 'otomatik' => 'Otomatik yükleme', 'harcama' => 'Harcama', 'iade' => 'İade'];
                $t = []; foreach ($cz as $x) { $k = $tip[$x['tip']] ?? ucfirst($x['tip']); $t[$k]['n'] = ($t[$k]['n'] ?? 0) + 1; $t[$k]['tl'] = ($t[$k]['tl'] ?? 0) + $x['tutar']; }
                $yuk = array_sum(array_map(fn ($x) => in_array($x['tip'], ['yukleme', 'otomatik'], true) ? $x['tutar'] : 0, $cz)); $har = array_sum(array_map(fn ($x) => $x['tip'] === 'harcama' ? $x['tutar'] : 0, $cz));
                $bak = array_sum(array_map(fn ($m) => $m->cuzdan, $mu));
                $gun = []; foreach ($cz as $x) { $k = $x['tip'] === 'harcama' ? 'h' : 'y'; $gun[$x['tarih']][$k] = ($gun[$x['tarih']][$k] ?? 0) + $x['tutar']; }
                $gt = array_map(fn ($g) => [$g, round($gun[$g]['y'] ?? 0, 2), round($gun[$g]['h'] ?? 0, 2)], _crrGunler($bas, $bit));
                $ust = array_values(array_filter($mu, fn ($m) => $m->cuzdan > 0)); usort($ust, fn ($a, $b) => $b->cuzdan <=> $a->cuzdan);
                $bos = !$d['cuzdan'] ? _crrBos('cuzdan_yok', $dn) : _crrBos('cuzdan', $dn);
                return [
                    $pano([_srKpi('Yüklenen', round($yuk, 2), 'tl', '831843'), _srKpi('Harcanan', round($har, 2), 'tl', '9D174D'), _srKpi('Net (yükleme − harcama)', round($yuk - $har, 2), 'tl', 'BE185D'),
                        _srKpi('Müşterilerdeki bakiye (işletmenin borcu)', round($bak, 2), 'tl', 'DC2626'), _srKpi('Bakiyesi olan müşteri', count($ust), 'sayi', '64748B'), _srKpi('Cüzdan kullanan', count(array_unique(array_column($cz, 'mid'))), 'sayi', 'DB2777')],
                        [_srBlok('Günlere göre yükleme ve harcama', 'col', '831843', [['Gün', 'm'], ['Yükleme', 'tl'], ['Harcama', 'tl']], array_map(fn ($x) => [date('d.m', strtotime($x[0])), $x[1], $x[2]], $gt))], !$cz ? $bos : null),
                    _srSayfa('Hareket Türleri', 'Yüklemedeki bonus dahil. Bakiye müşterinin önceden ödediği paradır; muhasebede yükümlülük olarak izlenmelidir.', [['Tür', 'm', 20], ['Adet', 's', 8, 'top'], ['Tutar', 'tl', 14, 'top bar']], array_map(fn ($k, $v) => [$k, $v['n'], round($v['tl'], 2)], array_keys($t), array_values($t)), $bos),
                    _srSayfa('En Yüksek Bakiyeler', 'Bugünkü cüzdan bakiyesi.', [['Müşteri', 'm', 22], ['Bakiye', 'tl', 13, 'top bar']], array_map(fn ($m) => [_crrAd($m, $m->id), round($m->cuzdan, 2)], array_slice($ust, 0, 50)), 'Cüzdan bakiyesi olan müşteri yok.'),
                ];

            // ---------------- ÖZEL GÜN & VERİ ----------------
            case 'dogum':
                $yak = _crrDogum($sid, date('Y-m-d'), 30); $sp = _crrSiparis($sid); $donem = []; $gel = 0;
                foreach ($mu as $id => $m) { if (!$m->dogum) continue; foreach ([(int) substr($bas, 0, 4), (int) substr($bit, 0, 4)] as $y) { $t = $y . substr($m->dogum, 4); if ($t < $bas || $t > $bit || isset($donem[$id])) continue; $donem[$id] = $t;
                    foreach ($sp[$id] ?? [] as $s) if (abs(_crrGunFark($t, $s[0])) <= 7) { $gel++; break; } } }
                return [
                    $pano([_srKpi('30 gün içinde doğum günü', count($yak), 'sayi', 'D97706'), _srKpi('7 gün içinde', count(array_filter($yak, fn ($x) => $x[1] <= 7)), 'sayi', 'B45309'), $kpiY('Doğum tarihi dolu müşteri', $oran($d['dogum'], $d['musteri']), '92400E'),
                        _srKpi('Dönemde doğum günü olan', count($donem), 'sayi', '64748B'), $kpiY('Doğum gününe ±7 gün içinde gelen', $oran($gel, count($donem)), '059669')],
                        [], !$d['musteri'] ? _crrBos('musteri_yok', $dn) : (!$d['dogum'] ? _crrBos('dogum_yok', $dn) : null)),
                    _srSayfa('Önümüzdeki 30 Gün', 'Bugünden itibaren; kutlama mesajı ve doğum günü indirimi (İndirimler > Doğum günü) için. Telefon KVKK için maskeli.', [['Doğum Günü', 't', 11], ['Kalan Gün', 's', 9], ['Müşteri', 'm', 22], ['Telefon', 'm', 12], ['Yaş', 's', 6], ['Sipariş', 's', 8], ['Son Ziyaret', 't', 11]], $yak, !$d['dogum'] ? _crrBos('dogum_yok', $dn) : _crrBos('dogum', $dn)),
                ];
            case 'veri':
                $tel = []; foreach ($mu as $m) { $t = preg_replace('/\D/', '', $m->telefon); if (strlen($t) >= 10) $tel[substr($t, -10)][] = $m; }
                $muk = array_filter($tel, fn ($l) => count($l) > 1); $sp = _crrSiparis($sid);
                $mSat = []; foreach ($muk as $t => $l) $mSat[] = [_crrTlf($t), count($l), implode(', ', array_map(fn ($m) => _crrAd($m, $m->id), $l)), array_sum(array_map(fn ($m) => count($sp[$m->id] ?? []) ?: $m->siparis, $l))];
                usort($mSat, fn ($a, $b) => $b[1] <=> $a[1]);
                $n = count($mu); $gecerli = $d['telefon']; $bosTel = count(array_filter($mu, fn ($m) => trim($m->telefon) === '')); $hatali = $n - $gecerli - $bosTel;
                $adsiz = count(array_filter($mu, fn ($m) => $m->ad === '')); $sipsiz = count(array_filter($mu, fn ($m) => empty($sp[$m->id]) && !$m->siparis));
                $alan = [['Geçerli telefon (10+ hane)', $gecerli], ['Telefon boş', $bosTel], ['Telefon hatalı / kısa', max(0, $hatali)], ['Doğum tarihi dolu', $d['dogum']], ['İsim boş', $adsiz], ['Hiç sipariş vermemiş', $sipsiz]];
                return [
                    $pano([_srKpi('Müşteri kaydı', $n, 'sayi', 'B45309'), $kpiY('Geçerli telefon', $oran($gecerli, $n), '059669'), $kpiY('Doğum tarihi dolu', $oran($d['dogum'], $n), 'D97706'),
                        _srKpi('Mükerrer telefon grubu', count($muk), 'sayi', 'DC2626'), _srKpi('Mükerrer kayıt', array_sum(array_map('count', $muk)) - count($muk), 'sayi', '991B1B'), _srKpi('Hiç sipariş vermemiş', $sipsiz, 'sayi', '64748B')],
                        [_srBlok('Alan doluluğu', 'bar', 'B45309', [['Alan', 'm'], ['Müşteri', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], $alan))], $bosMus),
                    _srSayfa('Alan Doluluğu', 'Telefon olmadan WhatsApp / SMS, doğum tarihi olmadan doğum günü kampanyası yapılamaz.', [['Alan', 'm', 26], ['Müşteri', 's', 9, 'bar'], ['Pay %', 'y', 8]], array_map(fn ($x) => [$x[0], $x[1], $n ? round($x[1] / $n * 100, 1) : null], $alan), $bosMus),
                    _srSayfa('Mükerrer Telefonlar', 'Aynı telefonla açılmış birden çok müşteri kartı: sipariş geçmişi bölünür, segment ve sadakat puanı yanlış hesaplanır. Müşteri 360\'ta birleştirin / silin.', [['Telefon', 'm', 14], ['Kart', 's', 6, 'top'], ['Kayıtlar', 'm', 50], ['Toplam Sipariş', 's', 12]], $mSat, 'Mükerrer telefon yok.'),
                ];
        }
        return [];
    }
}

// ---------- AI ----------
if (!function_exists('_crrAiBaglam')) {
    function _crrAiBaglam($sid, array $dn)
    {
        $bas = $dn['bas']; $bit = $dn['bit']; $s = _crrDonemSiparis($sid, $bas, $bit); $so = _crrDonemSiparis($sid, $dn['obas'], $dn['obit']);
        $ms = []; foreach ($s as $x) $ms[$x['mid']] = ($ms[$x['mid']] ?? 0) + 1; $mso = []; foreach ($so as $x) $mso[$x['mid']] = ($mso[$x['mid']] ?? 0) + 1;
        $c = array_sum(array_column($s, 'toplam')); $co = array_sum(array_column($so, 'toplam')); $prof = _crrProfil($sid, $bit); $rfm = _crrRfm($prof); $sp = _crrSiparis($sid);
        $mem = _crrMemnuniyet($sid, $bas, $bit); $memo = _crrMemnuniyet($sid, $dn['obas'], $dn['obit']); $deg = _crrDegerlendirme($sid, $bas, $bit); $tum = array_merge(array_column($mem, 'puan'), array_column($deg, 'puan'));
        $dus = array_filter($mem, fn ($x) => $x['puan'] <= 2); $seb = []; foreach ($dus as $x) foreach ($x['sebepler'] as $sb) $seb[$sb] = ($seb[$sb] ?? 0) + 1; arsort($seb);
        $risk = _crrRisk($sid, $bit); $gk = _crrGeriKazanim($sid, $bas, $bit); $pu = _crrPuan($sid, $bas, $bit); $od = _crrOdul($sid, $bas, $bit);
        $yasam = []; foreach ($prof as $p) { $dr = _crrDurum($p); $yasam[$dr] = ($yasam[$dr] ?? 0) + 1; }
        $vip = array_filter($prof, fn ($p) => $p['ciro'] >= 5000);
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _crrVeriDurumu($sid),
            'musteri_tabani' => ['kayitli' => count($prof), 'yasam_dongusu' => $yasam, 'vip_sayisi' => count($vip), 'vip_30_gundur_gelmeyen' => count(array_filter($vip, fn ($p) => $p['gun'] !== null && $p['gun'] > 30))],
            'donem_ziyaret' => ['gelen_musteri' => count($ms), 'onceki_gelen' => count($mso), 'yeni' => count(array_filter(array_keys($ms), fn ($m) => ($sp[$m][0][0] ?? '') >= $bas)), 'tekrar_gelen_yuzde' => count($ms) ? round(count(array_filter($ms, fn ($n) => $n >= 2)) / count($ms) * 100, 1) : null,
                'musteri_basi_harcama_tl' => count($ms) ? round($c / count($ms), 2) : null, 'onceki_musteri_basi_tl' => count($mso) ? round($co / count($mso), 2) : null, 'geri_kazanilan' => count($gk)],
            'rfm' => array_map(fn ($x) => ['segment' => $x[0], 'musteri' => $x[1], 'ciro_payi_yuzde' => $x[7]], _crrRfmSat($prof, $rfm)),
            'kayip_riski' => ['sayi' => count($risk), 'omur_boyu_ciro_tl' => round(array_sum(array_column($risk, 'ciro')), 2), 'tahmini_yillik_kayip_tl' => round(array_sum(array_column($risk, 'yillik')), 2), 'en_degerli' => array_map(fn ($x) => ['musteri' => $x['ad'], 'ciro' => $x['ciro'], 'gecen_gun' => $x['gun'], 'ort_aralik' => $x['aralik']], array_slice($risk, 0, 5))],
            'memnuniyet' => ['geri_bildirim' => count($tum), 'ortalama' => _crrOrt($tum), 'onceki_ortalama' => _crrOrt(array_column($memo, 'puan')), 'tavsiye_skoru' => _crrTavsiye($tum), 'dusuk_puan' => count($dus),
                'cozulen_dusuk' => count(array_filter($dus, fn ($x) => $x['durum'] === 'cozuldu')), 'sikayet_sebepleri' => array_slice($seb, 0, 6, true),
                'alt_puanlar' => ['lezzet' => _crrOrt(array_column($deg, 'lezzet')), 'servis' => _crrOrt(array_column($deg, 'servis')), 'hiz' => _crrOrt(array_column($deg, 'hiz'))]],
            'sadakat' => ['kazanilan_puan' => array_sum(array_column(array_merge($pu['siparis'], $pu['cark'], $pu['kart']), 'puan')), 'harcanan_puan' => array_sum(array_column($pu['harcama'], 'puan')),
                'odul_kodu_uretilen' => count(array_filter($od, fn ($x) => $x['olustu'] >= $bas)), 'odul_kullanilan' => count(array_filter($od, fn ($x) => $x['durum'] === 'Kullanıldı')), 'odul_maliyeti_tl' => _crrOdulMaliyet($sid, $bas, $bit), 'cark_cevirme' => count(_crrCark($sid, $bas, $bit))],
            'dogum_gunu_7_gun' => count(_crrDogum($sid, date('Y-m-d'), 7))];
    }
    function _crrAiNabiz($sid, array $dn)
    {
        $b = _crrAiBaglam($sid, $dn); $z = $b['donem_ziyaret']; $m = $b['memnuniyet']; $y = fn ($x) => $x === null ? '—' : '%' . number_format($x, 1, ',', '.');
        $dg = _srYuzde($z['gelen_musteri'], $z['onceki_gelen']); $ya = $b['musteri_tabani']['yasam_dongusu']; $sp = $b['sadakat'];
        return [
            ['renk' => '#0D9488', 'kat' => 'taban', 'ikon' => 'groups', 'etiket' => 'Gelen müşteri', 'deger' => (string) $z['gelen_musteri'], 'alt' => $dg !== null ? '%' . number_format(abs($dg), 1, ',', '.') . ($dg >= 0 ? ' arttı' : ' düştü') . ' · ' . $z['yeni'] . ' yeni' : $z['yeni'] . ' yeni müşteri', 'sinif' => $dg !== null ? ($dg >= 0 ? 'yesil' : 'kirmizi') : '', 'ok' => $dg !== null ? ($dg >= 0 ? 'trending_up' : 'trending_down') : null],
            ['renk' => '#0F766E', 'kat' => 'taban', 'ikon' => 'repeat', 'etiket' => 'Tekrar gelen oranı', 'deger' => $y($z['tekrar_gelen_yuzde']), 'alt' => 'Müşteri başı ' . ($z['musteri_basi_harcama_tl'] !== null ? number_format($z['musteri_basi_harcama_tl'], 0, ',', '.') . ' ₺' : '—'), 'sinif' => ''],
            ['renk' => '#DC2626', 'kat' => 'kayip', 'ikon' => 'person_off', 'etiket' => 'Kayıp riski', 'deger' => (string) $b['kayip_riski']['sayi'], 'alt' => number_format($b['kayip_riski']['tahmini_yillik_kayip_tl'], 0, ',', '.') . ' ₺ yıllık değer', 'sinif' => $b['kayip_riski']['sayi'] ? 'kirmizi' : 'yesil'],
            ['renk' => '#16A34A', 'kat' => 'memnuniyet', 'ikon' => 'sentiment_satisfied', 'etiket' => 'Memnuniyet', 'deger' => $m['ortalama'] !== null ? number_format($m['ortalama'], 2, ',', '.') . ' / 5' : '—', 'alt' => 'Tavsiye skoru ' . $y($m['tavsiye_skoru']) . ' · ' . $m['geri_bildirim'] . ' oy', 'sinif' => $m['ortalama'] !== null ? ($m['ortalama'] >= 4 ? 'yesil' : 'turuncu') : ''],
            ['renk' => '#15803D', 'kat' => 'memnuniyet', 'ikon' => 'report_problem', 'etiket' => 'Düşük puan', 'deger' => (string) $m['dusuk_puan'], 'alt' => $m['cozulen_dusuk'] . ' çözüldü', 'sinif' => $m['dusuk_puan'] > $m['cozulen_dusuk'] ? 'kirmizi' : ''],
            ['renk' => '#DB2777', 'kat' => 'sadakat', 'ikon' => 'redeem', 'etiket' => 'Kullanılan ödül', 'deger' => (string) $sp['odul_kullanilan'], 'alt' => number_format($sp['kazanilan_puan'], 0, ',', '.') . ' puan kazanıldı · ' . (($ya['Aktif'] ?? 0) + ($ya['Yeni'] ?? 0)) . ' aktif müşteri', 'sinif' => ''],
        ];
    }
}

// ---------- Modül kaydı ----------
_drKaydet('crm', [
    'ad' => 'CRM', 'baslik' => 'CRM Raporları', 'yol' => '/crm-raporlar', 'grup' => 'CRM / Müşteri İlişkileri', 'depKodlari' => ['crm'],
    'ensure' => '_crrEnsure', 'katalog' => '_crrKatalog', 'kategoriler' => '_crrKategoriler', 'veri' => '_crrVeriDurumu', 'rapor' => '_crrRapor',
    'olcek' => ['kucuk' => ['donemsel', 'memnuniyet', 'kayip', 'ai'], 'orta' => ['taban', 'deger', 'sadakat']],
    'modAd' => ['musteri' => 'Müşteri kartı', 'siparis' => 'Müşteriye bağlı sipariş', 'memnuniyet' => 'QR memnuniyet', 'degerlendirme' => 'Sipariş değerlendirmesi', 'urun_puan' => 'Ürün puanı',
        'odul' => 'Ödül kullanımı', 'cark' => 'Çarkıfelek', 'cuzdan' => 'Cüzdan hareketi', 'puan' => 'Puan bakiyesi', 'dogum' => 'Doğum tarihi', 'telefon' => 'Telefon'],
    'modLink' => ['musteri' => ['/musteri-360', 'Müşteri 360 (paket / uygulama siparişinde otomatik)'], 'siparis' => ['/pos', 'POS\'ta adisyona müşteri seçmek'], 'memnuniyet' => ['/itibar', 'İtibar / Memnuniyet (masa QR)'],
        'degerlendirme' => [null, 'Müşteri uygulaması / sipariş sonrası anket'], 'urun_puan' => [null, 'QR menüde ürüne yıldız'], 'odul' => ['/odul-dogrula', 'Ödül Doğrula (kasada)'], 'cark' => ['/cark-yonetim', 'Çarkıfelek'],
        'cuzdan' => ['/cuzdan-ayar', 'Cüzdan Ayarları'], 'puan' => ['/sadakat', 'Sadakat / Puan'], 'dogum' => ['/musteri-360', 'Müşteri 360 > doğum tarihi'], 'telefon' => ['/musteri-360', 'Müşteri 360 > telefon']],
    'ai' => [
        'gerekli' => ['musteri', 'siparis', 'memnuniyet', 'odul'],
        'hero' => ['Müşteri ilişkilerinin dönemini 20 saniyede özetleyeyim.', 'Müşteri tabanını ve yaşam döngüsünü, RFM segmentlerini, tekrar ziyaret ve harcamayı, kayıp riskini, memnuniyet ve şikayetleri, sadakat puanı ve ödülleri okur.'],
        'sorular' => ['Hangi değerli müşterilerimi kaybetmek üzereyim?', 'Müşteriler neden şikayet ediyor?', 'Tekrar gelen müşteri oranım iyi mi?', 'Sadakat programım işe yarıyor mu?', 'Şampiyon müşterilerime ne yapmalıyım?', 'Tek siparişte kalan müşterileri nasıl geri getiririm?'],
        'adimlar' => ['Müşteri kartları ve siparişler okunuyor…', 'Yaşam döngüsü ve RFM hesaplanıyor…', 'Önceki dönemle karşılaştırılıyor…', 'Memnuniyet ve şikayetler inceleniyor…', 'Sadakat puanı ve ödüller kontrol ediliyor…', 'Öneriler yazılıyor…'],
        'selam' => 'müşteri ilişkileri',
        'sys' => 'Sen bir restoranın müşteri ilişkilerini (CRM) patron ve CRM sorumlusu için analiz eden deneyimli bir müşteri sadakati ve deneyim danışmanısın. Yaşam döngüsü eşikleri: aktif ≤30 gün, risk 31-60, kayıp 61-120, uyuyan 120+; RFM segmentlerini, tekrar gelme oranını, müşteri başı harcamayı, kayıp riskini (kendi ziyaret aralığının 2 katını aşan düzenli müşteri), memnuniyet ve tavsiye skorunu (1-5 ölçekte 5 verenler − 1/2/3 verenler), şikayet çözümünü ve sadakat programını yorumla. '
            . 'Yalnız müşteriye bağlı siparişler ölçülür (salonda müşteri seçilmeyen adisyonlar dışarıda kalır), bunu gerektiğinde hatırlat. Müşteri adlarını yalnız aksiyon için an; telefon ya da kişisel veri yazma, kişiler hakkında yargıda bulunma.',
        'baglam' => '_crrAiBaglam',
        'veriVar' => fn ($b) => $b['musteri_tabani']['kayitli'] > 0,
        'nabiz' => '_crrAiNabiz',
    ],
]);

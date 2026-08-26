<?php

namespace App\Services;

/**
 * Google Cloud TTS ile metni MP3'e cevirir + SUNUCUDA onbellege alir (storage/app/tts).
 * Musteri QR asistani icin kaliteli ERKEK ses (tr-TR-Wavenet-D). Randevumcepte'den taşındı.
 * Anahtar yoksa null -> cagiran taraf tarayici sesine duser (regresyon yok).
 */
class SeslendirmeServisi
{
    protected $klasor;
    protected $subeId;   // HER restoran/sube kendi aylik limitine sahip (multi-tenant)
    public $sonHata = null;

    public function __construct($subeId = null)
    {
        $this->subeId = (int) $subeId; // null/0 = genel havuz (test vb.)
        $this->klasor = storage_path('app/tts');
        if (!is_dir($this->klasor)) @mkdir($this->klasor, 0775, true);
    }

    public function uret($metin, $ses = null)
    {
        $metin = trim((string) $metin);
        if ($metin === '') return null;
        $ses = $ses ?: (string) config('services.google_tts.voice', 'tr-TR-Wavenet-D');
        $okunacak = $this->okunusHazirla($metin);
        if ($okunacak === '') return null;

        $ad = md5($ses . '|' . $okunacak) . '.mp3';
        $yol = $this->klasor . '/' . $ad;
        // ONBELLEK: daha once uretilmisse diskten don (karakter YAKMAZ, limite saymaz)
        // @touch: kullanilan dosyayi "taze" tut -> otomatik temizlik onu silmesin (sik kullanilanlar kalir)
        if (is_file($yol) && filesize($yol) > 0) { @touch($yol); return $ad; }

        // SERT AYLIK LIMIT: kota dolduysa Cloud'a gitme -> cagiran taraf bedava cihaz sesine duser
        if (!$this->kotaVar($okunacak)) { $this->sonHata = ['kota' => 'asildi']; return null; }

        $mp3 = $this->googleUret($okunacak, $ses);
        if ($mp3 === null || $mp3 === '') return null;
        @file_put_contents($yol, $mp3);
        if (is_file($yol) && filesize($yol) > 0) {
            $this->kotaEkle(mb_strlen($okunacak)); // sadece GERCEK uretimi say
            return $ad;
        }
        return null;
    }

    /** Bu ay + BU SUBE'nin anahtari: tts_kota_{subeId}_{YYYYMM} (her restoran ayri sayilir) */
    protected function ayAnahtar() { return 'tts_kota_' . ((int) $this->subeId) . '_' . date('Ym'); }

    /** Bu ay kullanilan karakter sayisi (onbellekten gelenler sayilmaz). */
    public function ayKullanim()
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('ayarlar')) return 0;
            return (int) \Illuminate\Support\Facades\DB::table('ayarlar')->where('anahtar', $this->ayAnahtar())->value('deger');
        } catch (\Throwable $e) { return 0; }
    }

    /** Aylik limit asilmadi mi? (limit<=0 ise sinirsiz). Hata olursa engelleme (ses kesilmesin). */
    protected function kotaVar($okunacak)
    {
        $limit = (int) config('services.google_tts.aylik_limit', 900000);
        if ($limit <= 0) return true;
        return ($this->ayKullanim() + mb_strlen($okunacak)) <= $limit;
    }

    /** Bu ayin sayacini artir. */
    protected function kotaEkle($n)
    {
        try {
            $Schema = \Illuminate\Support\Facades\Schema::class;
            if (!$Schema::hasTable('ayarlar')) {
                $Schema::create('ayarlar', function ($t) { $t->string('anahtar', 60)->primary(); $t->text('deger')->nullable(); });
            }
            $k = $this->ayAnahtar();
            $DB = \Illuminate\Support\Facades\DB::class;
            $mevcut = (int) $DB::table('ayarlar')->where('anahtar', $k)->value('deger');
            $DB::table('ayarlar')->updateOrInsert(['anahtar' => $k], ['deger' => (string) ($mevcut + (int) $n)]);
        } catch (\Throwable $e) { /* sayac tutulamazsa sesi engelleme */ }
    }

    public function dosyaYolu($ad)
    {
        $ad = basename((string) $ad);
        if (!preg_match('/^[a-f0-9]{32}\.mp3$/', $ad)) return null;
        $yol = $this->klasor . '/' . $ad;
        if (is_file($yol)) { @touch($yol); return $yol; } // calindi -> taze tut (silinmesin)
        return null;
    }

    /**
     * Uzun suredir KULLANILMAYAN MP3'leri sil (disk sabit kalir).
     * Sik kullanilanlar (menu/karsilama) her calindiginda @touch ile tazelendigi icin silinmez;
     * sadece tek-seferlik (AI serbest) cumleler duser. Gerekirse tekrar uretilir.
     */
    public function eskileriTemizle($gun = 45)
    {
        $gun = max(1, (int) $gun);
        $sinir = time() - ($gun * 86400);
        $silinen = 0; $bayt = 0; $kalan = 0;
        foreach (glob($this->klasor . '/*.mp3') ?: [] as $f) {
            if (@filemtime($f) < $sinir) {
                $b = (int) @filesize($f);
                if (@unlink($f)) { $silinen++; $bayt += $b; }
            } else { $kalan++; }
        }
        return ['silinen_dosya' => $silinen, 'bosalan_mb' => round($bayt / 1048576, 2), 'kalan_dosya' => $kalan];
    }

    protected function googleUret($metin, $ses)
    {
        $key = (string) config('services.google_tts.key', '');
        if ($key === '') return null;
        $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . urlencode($key);
        $audioConfig = ['audioEncoding' => 'MP3', 'speakingRate' => 1.0];
        // Chirp3-HD sesleri pitch desteklemez -> gonderme (aksi halde hata)
        if (strpos($ses, 'Chirp') === false) $audioConfig['pitch'] = 0.0;
        $govde = json_encode([
            'input' => ['text' => $metin],
            'voice' => ['languageCode' => 'tr-TR', 'name' => $ses],
            'audioConfig' => $audioConfig,
        ], JSON_UNESCAPED_UNICODE);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_POSTFIELDS => $govde, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $res = curl_exec($ch);
        $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $this->sonHata = ['http' => $kod, 'curl_err' => $curlErr, 'govde' => is_string($res) ? mb_substr($res, 0, 250) : '(bos)'];
        if ($kod !== 200 || !$res) return null;
        $j = json_decode($res, true);
        if (empty($j['audioContent'])) return null;
        $bin = base64_decode($j['audioContent']);
        return ($bin !== false && $bin !== '') ? $bin : null;
    }

    /** Rakamlari yaziya cevir (TTS dogru okusun): "245" -> "iki yuz kirk bes", "14:30" -> "on dort otuz". */
    public function okunusHazirla($metin)
    {
        $metin = (string) $metin;
        if (!mb_check_encoding($metin, 'UTF-8')) $metin = @mb_convert_encoding($metin, 'UTF-8', 'UTF-8');
        $m = ' ' . trim($metin) . ' ';
        // Binlik ayiraci noktasini kaldir: "1.250" -> "1250" (sesli vurguyu bozmasin)
        $m = preg_replace('/(\d)\.(\d{3})(?=\D|$)/u', '$1$2', $m);
        $m = preg_replace('/(\d)\.(\d{3})(?=\D|$)/u', '$1$2', $m); // "1.250.000" gibi ikili
        // Para birimi: "₺75" / "75₺" / "75 TL" -> "75 lira" (rakam sonra yaziya cevrilir)
        $m = preg_replace('/₺\s*(\d+)/u', '$1 lira', $m);
        $m = preg_replace('/(\d+)\s*(?:₺|tl)\b/iu', '$1 lira', $m);
        $m = str_replace('₺', ' lira', $m);
        $t = preg_replace_callback('/\b([01]?\d|2[0-3]):([0-5]\d)\b/u', function ($x) {
            $s = $this->sayiYazi((int) $x[1]);
            $d = ((int) $x[2]) > 0 ? ' ' . $this->sayiYazi((int) $x[2]) : '';
            return $s . $d;
        }, $m);
        if ($t !== null) $m = $t;
        $t = preg_replace_callback('/\d+/u', fn ($x) => $this->sayiYazi((int) $x[0]), $m);
        if ($t !== null) $m = $t;
        $t = preg_replace('/\s+/u', ' ', $m);
        if ($t !== null) $m = $t;
        $m = trim($m);
        return $m !== '' ? $m : trim((string) $metin);
    }

    protected function sayiYazi($n)
    {
        if ($n === 0) return 'sıfır';
        $on = '';
        if ($n < 0) { $on = 'eksi '; $n = -$n; }
        if ($n >= 1000000) return $on . (string) $n;
        $bin = intdiv($n, 1000);
        $kalan = $n % 1000;
        $out = '';
        if ($bin > 0) $out .= ($bin === 1) ? 'bin ' : $this->ucBasamak($bin) . ' bin ';
        if ($kalan > 0) $out .= $this->ucBasamak($kalan);
        return $on . trim($out);
    }

    protected function ucBasamak($n)
    {
        $birler = ['', 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
        $onlar = ['', 'on', 'yirmi', 'otuz', 'kırk', 'elli', 'altmış', 'yetmiş', 'seksen', 'doksan'];
        $out = '';
        $yuz = intdiv($n, 100);
        $k = $n % 100;
        if ($yuz > 0) $out .= (($yuz === 1) ? 'yüz' : $birler[$yuz] . ' yüz') . ' ';
        $o = intdiv($k, 10);
        $b = $k % 10;
        if ($o > 0) $out .= $onlar[$o] . ' ';
        if ($b > 0) $out .= $birler[$b] . ' ';
        return trim($out);
    }
}

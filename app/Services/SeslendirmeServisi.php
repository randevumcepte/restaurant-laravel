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
    public $sonHata = null;

    public function __construct()
    {
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
        if (is_file($yol) && filesize($yol) > 0) return $ad;

        $mp3 = $this->googleUret($okunacak, $ses);
        if ($mp3 === null || $mp3 === '') return null;
        @file_put_contents($yol, $mp3);
        return (is_file($yol) && filesize($yol) > 0) ? $ad : null;
    }

    public function dosyaYolu($ad)
    {
        $ad = basename((string) $ad);
        if (!preg_match('/^[a-f0-9]{32}\.mp3$/', $ad)) return null;
        $yol = $this->klasor . '/' . $ad;
        return is_file($yol) ? $yol : null;
    }

    protected function googleUret($metin, $ses)
    {
        $key = (string) config('services.google_tts.key', '');
        if ($key === '') return null;
        $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . urlencode($key);
        $govde = json_encode([
            'input' => ['text' => $metin],
            'voice' => ['languageCode' => 'tr-TR', 'name' => $ses],
            'audioConfig' => ['audioEncoding' => 'MP3', 'speakingRate' => 1.0, 'pitch' => 0.0],
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

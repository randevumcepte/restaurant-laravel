<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AKTİVİTE LOG — her değiştiren (POST/PUT/PATCH/DELETE) API çağrısını otomatik loglar.
 * terminate() YANIT GÖNDERİLDİKTEN SONRA çalışır → isteği asla yavaşlatmaz/bozmaz (try/catch'li).
 * Finansal endpoint'ler (ödeme/stok/kasa/cari/gider…) burada ATLANIR; onlar zaten kendi
 * domain loglarında tutulur → /api/patron/hareketler ikisini birleştirir (çift kayıt olmaz).
 */
class AktiviteLog
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) return;
            $path = ltrim($request->path(), '/');
            if (!str_starts_with($path, 'api/')) return;

            // Gürültü (heartbeat/polling) + finansal (domain loglarında) -> merkezi loga YAZMA
            $atla = [
                'api/cihaz/ping', 'api/mesai/ping', 'api/adim-kaydet', 'api/qr/stt', 'api/tts',
                // finansal — domain loglarindan gelir
                'api/patron/adisyon-islem', 'api/patron/kasa-ac', 'api/patron/kasa-kapat', 'api/patron/kasa-hareket',
                'api/patron/stok-hareket', 'api/patron/cari-tahsilat', 'api/patron/cari-ekle',
                'api/patron/personel-hareket', 'api/patron/gider-ekle', 'api/patron/gider-sil',
                'api/patron/sayim-kaydet', 'api/patron/alis-fatura-kaydet',
            ];
            foreach ($atla as $s) if (str_starts_with($path, $s)) return;

            $token = $request->bearerToken();
            $p = $token ? DB::table('personeller')->where('api_token', $token)->first() : null;
            if (!$p) return; // giris yapmamis istek -> atla

            self::ensure();

            [$kategori, $aksiyon] = $this->etiket($path, $request);
            DB::table('aktivite_loglari')->insert([
                'sube_id' => $p->sube_id ?? null, 'personel_id' => $p->id, 'personel_ad' => $p->ad ?? null, 'rol' => $p->rol ?? null,
                'kategori' => $kategori, 'aksiyon' => $aksiyon, 'aciklama' => $this->aciklama($request),
                'yol' => $path, 'metod' => $request->method(), 'ip' => $request->ip(), 'created_at' => now(),
            ]);
        } catch (\Throwable $e) { /* asla bozma */ }
    }

    public static function ensure(): void
    {
        if (Schema::hasTable('aktivite_loglari')) return;
        Schema::create('aktivite_loglari', function ($t) {
            $t->id();
            $t->unsignedBigInteger('sube_id')->nullable();
            $t->unsignedBigInteger('personel_id')->nullable();
            $t->string('personel_ad')->nullable();
            $t->string('rol')->nullable();
            $t->string('kategori')->default('diger');
            $t->string('aksiyon')->nullable();
            $t->text('aciklama')->nullable();
            $t->string('yol')->nullable();
            $t->string('metod', 10)->nullable();
            $t->string('ip')->nullable();
            $t->timestamp('created_at')->nullable();
            $t->index(['sube_id', 'created_at']);
        });
    }

    private function etiket(string $path, Request $r): array
    {
        $map = [
            'api/patron/urun-kaydet' => ['menu', 'Ürün kaydedildi/güncellendi'],
            'api/patron/urun-sil' => ['menu', 'Ürün silindi'],
            'api/patron/kategori-kaydet' => ['menu', 'Kategori kaydedildi'],
            'api/patron/recete-kaydet' => ['recete', 'Reçete güncellendi'],
            'api/patron/yarimamul-kaydet' => ['recete', 'Yarı mamül kaydedildi'],
            'api/patron/yarimamul-sil' => ['recete', 'Yarı mamül silindi'],
            'api/patron/malzeme-kaydet' => ['stok', 'Malzeme kaydedildi'],
            'api/patron/malzeme-sil' => ['stok', 'Malzeme silindi'],
            'api/patron/tedarikci-kaydet' => ['stok', 'Tedarikçi kaydedildi'],
            'api/patron/tedarikci-sil' => ['stok', 'Tedarikçi silindi'],
            'api/patron/yetki-kaydet' => ['personel', 'Personel yetkisi değişti'],
            'api/patron/personel-kaydet' => ['personel', 'Personel kaydedildi'],
            'api/patron/masa-ac' => ['satis', 'Masa açıldı'],
            'api/patron/atama-kaydet' => ['ayar', 'Masa/bölge ataması değişti'],
            'api/patron/salon-sema-kaydet' => ['ayar', 'Salon şeması kaydedildi'],
            'api/patron/one-sira-kaydet' => ['menu', 'Öne çıkanlar sıralandı'],
            'tema/kaydet' => ['ayar', 'QR menü teması değişti'],
            'api/patron/kategori-sil' => ['menu', 'Kategori silindi'],
            'api/garson-cagri-kapat' => ['cagri', 'Garson çağrısı kapatıldı'],
            'api/login' => ['giris', 'Giriş yapıldı'],
        ];
        foreach ($map as $k => $v) if (str_starts_with($path, $k)) return $v;
        if (str_starts_with($path, 'api/mesai/okut')) return ['mesai', 'Mesai QR okutuldu'];
        // fallback: path'ten okunur uret
        $ad = str_replace(['api/patron/', 'api/'], '', $path);
        $ad = ucfirst(trim(str_replace(['-', '/'], [' ', ' › '], $ad)));
        return ['diger', $ad];
    }

    private function aciklama(Request $r): ?string
    {
        $anahtar = ['ad', 'tip', 'islem', 'oran', 'tutar', 'miktar', 'durum', 'ay', 'sebep', 'marka'];
        $parca = [];
        foreach ($anahtar as $k) {
            $v = $r->input($k);
            if ($v !== null && $v !== '' && !is_array($v)) $parca[] = $k . ': ' . mb_substr((string) $v, 0, 40);
        }
        return $parca ? implode(' · ', array_slice($parca, 0, 4)) : null;
    }
}

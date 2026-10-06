<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WhatsApp kontör (kredi) yönetimi — Randevumcepte KontorServisi'nin ResteOS uyarlaması.
 * 1 mesaj = 1 kontör. Bakiye subeler.whatsapp_kontor'da; her hareket whatsapp_kontor_hareketleri'nde.
 *
 * - Şube başına ÜCRETSİZ deneme: subeler.whatsapp_deneme_bitis'e kadar kontör düşmez/engel yok.
 * - Deneme bitince: her mesaj 1 kontör düşer; bakiye yoksa gönderilmez (arayan SMS'e düşürebilir).
 * - deneme_bitis yoksa (kolon yok/null) fallback global BASLANGIC.
 *
 * GÜVENLİ VARSAYILAN: BASLANGIC ileri tarih -> ResteOS kurulumunda kontör ENGELLEMEZ
 * (mevcut WhatsApp sipariş kanalı bozulmasın). Billing aktif edilince deneme_bitis geçmişe çekilir.
 */
class KontorServisi
{
    /** Fallback: hiçbir şube-özel deneme bitişi yoksa kullanılan global tarih (ileri -> varsayılan ücretsiz). */
    const BASLANGIC = '2027-12-31';

    /** Kontörlü dönem başladı mı? Şube-özel deneme_bitis'e, yoksa global BASLANGIC'e bakar. */
    public static function kontorluDonemMi($sube = null)
    {
        if ($sube) {
            $bitis = is_object($sube) ? ($sube->whatsapp_deneme_bitis ?? null) : null;
            if (empty($bitis)) {
                // HIZ: hasColumn (information_schema) YAPMA; direkt value() dene, kolon yoksa catch.
                try {
                    $id = is_object($sube) ? ($sube->id ?? null) : (int) $sube;
                    if ($id) {
                        $bitis = DB::table('subeler')->where('id', (int) $id)->value('whatsapp_deneme_bitis');
                    }
                } catch (\Throwable $e) {
                    $bitis = null;
                }
            }
            if (!empty($bitis)) {
                $s = substr((string) $bitis, 0, 10);
                if ($s !== '' && $s !== '0000-00-00') return date('Y-m-d') > $s;
            }
        }
        return date('Y-m-d') >= self::BASLANGIC;
    }

    protected static function kolonVar()
    {
        try { return Schema::hasColumn('subeler', 'whatsapp_kontor'); }
        catch (\Throwable $e) { return false; }
    }

    public static function selfHeal()
    {
        try {
            if (!Schema::hasColumn('subeler', 'whatsapp_kontor')) {
                DB::statement('ALTER TABLE subeler ADD COLUMN whatsapp_kontor INT NOT NULL DEFAULT 0');
            }
            if (!Schema::hasColumn('subeler', 'whatsapp_deneme_bitis')) {
                DB::statement('ALTER TABLE subeler ADD COLUMN whatsapp_deneme_bitis DATE NULL');
            }
            if (!Schema::hasTable('whatsapp_kontor_hareketleri')) {
                DB::statement('CREATE TABLE whatsapp_kontor_hareketleri (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    sube_id BIGINT UNSIGNED,
                    tip VARCHAR(20),
                    adet INT,
                    bakiye_sonrasi INT NULL,
                    aciklama VARCHAR(160) NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX (sube_id, created_at)
                )');
            }
        } catch (\Throwable $e) {
        }
    }

    /** Şubenin güncel kontör bakiyesi. */
    public static function bakiye($sube)
    {
        $id = is_object($sube) ? ($sube->id ?? null) : $sube;
        if (!$id) return 0;
        try { return (int) DB::table('subeler')->where('id', $id)->value('whatsapp_kontor'); }
        catch (\Throwable $e) { return 0; }
    }

    /** Gönderim için kontör yeterli mi? Ücretsiz dönemde / kolon yoksa her zaman true. */
    public static function yeterliMi($sube, $adet = 1)
    {
        if (!self::kontorluDonemMi($sube)) return true;
        if (!self::kolonVar()) return true;
        return self::bakiye($sube) >= $adet;
    }

    /** Kontör düşer (gönderim başarılı olunca). Ücretsiz dönemde hiçbir şey yapmaz. Atomik, negatife düşmez. */
    public static function dus($sube, $adet = 1, $aciklama = 'whatsapp-mesaj')
    {
        if (!self::kontorluDonemMi($sube)) return true;
        $id = is_object($sube) ? ($sube->id ?? null) : $sube;
        if (!$id || $adet < 1) return false;
        self::selfHeal();
        try {
            $etkilenen = DB::table('subeler')->where('id', $id)->where('whatsapp_kontor', '>=', $adet)
                ->update(['whatsapp_kontor' => DB::raw('whatsapp_kontor - ' . (int) $adet)]);
            if (!$etkilenen) return false;
            $kalan = self::bakiye($id);
            self::hareket($id, 'harcama', -abs((int) $adet), $kalan, $aciklama);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Kontör yükler (manuel). Bakiyeyi artırır + hareket loglar. */
    public static function yukle($subeId, $adet, $aciklama = 'manuel-yukleme')
    {
        $adet = (int) $adet;
        if (!$subeId || $adet == 0) return ['ok' => false, 'mesaj' => 'Geçersiz miktar.'];
        self::selfHeal();
        try {
            DB::table('subeler')->where('id', $subeId)->update(['whatsapp_kontor' => DB::raw('whatsapp_kontor + ' . $adet)]);
            $kalan = self::bakiye($subeId);
            self::hareket($subeId, $adet > 0 ? 'yukleme' : 'harcama', $adet, $kalan, $aciklama);
            return ['ok' => true, 'bakiye' => $kalan];
        } catch (\Throwable $e) {
            return ['ok' => false, 'mesaj' => $e->getMessage()];
        }
    }

    /** WhatsApp kontör paket merdiveni (Randevumcepte ile aynı onaylı fiyatlar). */
    public static function paketler()
    {
        return [
            'kontor_10000'  => ['ad' => '10.000 Kontör',  'adet' => 10000,  'fiyat' => '2.850 TL'],
            'kontor_20000'  => ['ad' => '20.000 Kontör',  'adet' => 20000,  'fiyat' => '5.300 TL'],
            'kontor_40000'  => ['ad' => '40.000 Kontör',  'adet' => 40000,  'fiyat' => '9.800 TL'],
            'kontor_60000'  => ['ad' => '60.000 Kontör',  'adet' => 60000,  'fiyat' => '13.800 TL'],
            'kontor_80000'  => ['ad' => '80.000 Kontör',  'adet' => 80000,  'fiyat' => '17.000 TL'],
            'kontor_100000' => ['ad' => '100.000 Kontör', 'adet' => 100000, 'fiyat' => '20.000 TL'],
        ];
    }

    protected static function hareket($subeId, $tip, $adet, $bakiyeSonrasi, $aciklama)
    {
        try {
            DB::table('whatsapp_kontor_hareketleri')->insert([
                'sube_id' => $subeId, 'tip' => $tip, 'adet' => (int) $adet,
                'bakiye_sonrasi' => (int) $bakiyeSonrasi, 'aciklama' => mb_substr((string) $aciklama, 0, 160),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }
    }
}

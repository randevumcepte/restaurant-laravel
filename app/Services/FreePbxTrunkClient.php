<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FreePBX TRUNK + DID istemcisi.
 *
 * NEDEN AYRI? FreePBX GraphQL API'sinde Trunks modulu yok -> trunk (chan_sip/pjsip)
 * GraphQL ile ACILAMAZ. Bu yuzden FreePBX sunucuya konan santral-trunk.php ucunu
 * (BMO addTrunk + did_contexts) HTTP ile cagiririz.
 *
 * Uc: {trunk_api_url}  ornek: https://santral.ornek.com/monitor/api/santral-trunk.php
 * Guvenlik: paylasilan gizli anahtar (trunk_api_secret) — script'teki SANTRAL_SECRET ile AYNI.
 * Ayarlar freepbx_ayarlari tablosunda (GraphQL ayarlariyla ayni yerde) tutulur.
 */
class FreePbxTrunkClient
{
    protected $apiUrl;
    protected $secret;

    public $hata = null;
    public $sonHam = null;

    public function __construct($ayar = null)
    {
        self::ensure();
        $ayar = $ayar ?: DB::table('freepbx_ayarlari')->first();
        $this->apiUrl = rtrim((string) ($ayar->trunk_api_url ?? ''), '/');
        $this->secret = (string) ($ayar->trunk_api_secret ?? '');
    }

    /** freepbx_ayarlari tablosuna trunk sutunlarini ekle (yoksa). */
    public static function ensure(): void
    {
        FreePbxClient::ensure(); // tabloyu garanti et
        if (!Schema::hasColumn('freepbx_ayarlari', 'trunk_api_url')) {
            Schema::table('freepbx_ayarlari', function ($t) {
                $t->string('trunk_api_url')->nullable();
            });
        }
        if (!Schema::hasColumn('freepbx_ayarlari', 'trunk_api_secret')) {
            Schema::table('freepbx_ayarlari', function ($t) {
                $t->text('trunk_api_secret')->nullable();
            });
        }
    }

    public function ayarliMi(): bool
    {
        return $this->apiUrl !== '' && $this->secret !== '';
    }

    /**
     * TEK AKIS: chan_sip trunk ekle + DID'i context'e bagla + reload.
     * @param array $p host, username, sip_secret, did, context, tech, ad, register
     */
    public function kur(array $p): array
    {
        return $this->cagir('kur', $p);
    }

    public function trunkEkle(array $p): array
    {
        return $this->cagir('trunk_ekle', $p);
    }

    public function didBagla(string $did, string $context): array
    {
        return $this->cagir('did_bagla', ['did' => $did, 'context' => $context]);
    }

    public function didSil(string $did): array
    {
        return $this->cagir('did_sil', ['did' => $did]);
    }

    public function didListe(): array
    {
        return $this->cagir('did_liste', []);
    }

    public function trunkListe(): array
    {
        return $this->cagir('trunk_liste', []);
    }

    // ---- HTTP cagrisi (POST, paylasilan secret) ----
    protected function cagir(string $islem, array $p): array
    {
        if (!$this->ayarliMi()) {
            return ['ok' => 0, 'hata' => 'Trunk API ayarli degil (FreePBX API ayar sayfasindan URL + secret girin).'];
        }
        $p['islem'] = $islem;
        $p['secret'] = $this->secret;

        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($p),
                CURLOPT_TIMEOUT => 60, // fwconsole reload uzun surebilir
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $r = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $this->sonHam = $r;

            if ($r === false) {
                $this->hata = 'Baglanti hatasi: ' . $err;
                return ['ok' => 0, 'hata' => $this->hata];
            }
            $j = json_decode((string) $r, true);
            if (!is_array($j)) {
                $this->hata = 'Gecersiz yanit (HTTP ' . $code . '): ' . substr((string) $r, 0, 300);
                return ['ok' => 0, 'hata' => $this->hata, 'ham' => $r];
            }
            if (empty($j['ok'])) {
                $this->hata = $j['hata'] ?? ('Islem basarisiz (HTTP ' . $code . ')');
            }
            return $j;
        } catch (\Throwable $e) {
            $this->hata = $e->getMessage();
            return ['ok' => 0, 'hata' => $this->hata];
        }
    }
}

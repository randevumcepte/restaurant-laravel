<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FreePBX 16 GraphQL API istemcisi — dahilileri (SIP hesaplari) PANELDEN yonet.
 * FreePBX'i BOZMAZ: sadece onun resmi API'sine (OAuth2 client_credentials -> GraphQL) yazar.
 * FreePBX yine kaynaktir; biz sadece uzaktan "dahili ekle/sil/sifre" deriz.
 *
 * Uclar (FreePBX 16): {base}/admin/api/api/token , {base}/admin/api/api/gql
 * Kurulum: Admin -> Module Admin -> API (install+enable) ; Admin -> API -> Applications (Client ID/Secret).
 */
class FreePbxClient
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $token = null;

    public $hata = null;     // insan-okur hata
    public $sonHam = null;   // ham yanit (teshis icin)

    public function __construct($ayar = null)
    {
        $ayar = $ayar ?: self::ayar();
        $this->baseUrl = rtrim((string) ($ayar->base_url ?? ''), '/');
        $this->clientId = (string) ($ayar->client_id ?? '');
        $this->clientSecret = (string) ($ayar->client_secret ?? '');
    }

    public static function ensure()
    {
        if (!Schema::hasTable('freepbx_ayarlari')) {
            Schema::create('freepbx_ayarlari', function ($t) {
                $t->id();
                $t->string('base_url')->nullable();       // https://pbx... (admin URL)
                $t->string('client_id')->nullable();
                $t->text('client_secret')->nullable();
                $t->boolean('aktif')->default(0);
                $t->timestamp('updated_at')->nullable();
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    public static function ayar()
    {
        self::ensure();
        return DB::table('freepbx_ayarlari')->first();
    }

    public function ayarliMi(): bool
    {
        return $this->baseUrl !== '' && $this->clientId !== '' && $this->clientSecret !== '';
    }

    // ---- OAuth2 token (client_credentials) ----
    protected function token()
    {
        if ($this->token) return $this->token;
        if (!$this->ayarliMi()) { $this->hata = 'FreePBX API ayarı eksik (base URL / client id / secret).'; return null; }
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);
        $res = $this->http($this->baseUrl . '/admin/api/api/token', $body, ['Content-Type: application/x-www-form-urlencoded']);
        if ($res === null) return null;
        $j = json_decode($res, true);
        if (empty($j['access_token'])) { $this->hata = 'Token alınamadı (client id/secret/scope kontrol et): ' . substr($res, 0, 300); return null; }
        return $this->token = $j['access_token'];
    }

    // ---- GraphQL cagrisi ----
    public function gql($query)
    {
        $tok = $this->token();
        if (!$tok) return null;
        $res = $this->http($this->baseUrl . '/admin/api/api/gql', json_encode(['query' => $query]), [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $tok,
        ]);
        if ($res === null) return null;
        $this->sonHam = $res;
        $j = json_decode($res, true);
        if (isset($j['errors'])) $this->hata = 'GraphQL hata: ' . json_encode($j['errors'], JSON_UNESCAPED_UNICODE);
        return $j;
    }

    protected function http($url, $body, $headers)
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_SSL_VERIFYPEER => false, // FreePBX cogu kez self-signed
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $r = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($r === false) { $this->hata = 'Bağlantı hatası: ' . $err; return null; }
            if ($code >= 400) { $this->hata = 'HTTP ' . $code . ': ' . substr((string) $r, 0, 300); return $r; }
            return $r;
        } catch (\Throwable $e) {
            $this->hata = $e->getMessage();
            return null;
        }
    }

    // ================= DAHILI ISLEMLERI =================

    public function testBaglanti(): array
    {
        $tok = $this->token();
        if (!$tok) return ['ok' => 0, 'hata' => $this->hata ?: 'Token alınamadı'];
        return ['ok' => 1, 'mesaj' => 'Bağlantı başarılı, token alındı.'];
    }

    /** Tum dahilileri listele (numara + ad). */
    public function liste(): array
    {
        $q = 'query { fetchAllExtensions { status message totalCount extension { user { extension name } } } }';
        $j = $this->gql($q);
        $out = [];
        $ext = $j['data']['fetchAllExtensions']['extension'] ?? null;
        if (is_array($ext)) {
            foreach ($ext as $e) {
                $num = $e['user']['extension'] ?? ($e['extensionId'] ?? '');
                if ($num === '') continue;
                $out[] = ['numara' => (string) $num, 'ad' => (string) ($e['user']['name'] ?? '')];
            }
            usort($out, fn ($a, $b) => strnatcmp($a['numara'], $b['numara']));
        }
        return $out;
    }

    /**
     * Yeni dahili ekle (numara + ad + SIP sifresi). tech varsayilan pjsip.
     * NOT: FreePBX 16 addExtension SIFRE ALMAZ -> once olustur, sonra updateExtension ile extPassword ata.
     */
    public function ekle($numara, $ad, $sifre, $tech = 'pjsip'): array
    {
        $num = (int) $numara;
        $adM = $this->kacar(trim((string) $ad) !== '' ? $ad : ('Dahili ' . $num));
        $tech = $this->kacar($tech);
        $m = 'mutation { addExtension(input: { extensionId: ' . $num . ', name: "' . $adM . '", email: "", tech: "' . $tech . '", vmEnable: false, umEnable: false }) { status message } }';
        $j = $this->gql($m);
        $st = $j['data']['addExtension']['status'] ?? null;
        if (!$st) {
            return ['ok' => 0, 'hata' => $this->hata ?: ($j['data']['addExtension']['message'] ?? 'Eklenemedi'), 'ham' => $this->sonHam];
        }
        // SIP sifresini ata (updateExtension extPassword) — sifre() zaten reload eder
        if (trim((string) $sifre) !== '') {
            $up = $this->sifre($num, $sifre);
            if (empty($up['ok'])) {
                return ['ok' => 1, 'uyari' => 1, 'mesaj' => 'Dahili eklendi ama SIP şifresi atanamadı: ' . ($up['hata'] ?? ''), 'ham' => $up['ham'] ?? null];
            }
            return ['ok' => 1, 'mesaj' => 'Dahili eklendi (PJSIP) ve şifre atandı'];
        }
        $this->reload();
        return ['ok' => 1, 'mesaj' => 'Dahili eklendi (PJSIP)'];
    }

    /** Dahili sil. */
    public function sil($numara): array
    {
        $num = (int) $numara;
        $m = 'mutation { deleteExtension(input: { extensionId: ' . $num . ' }) { status message } }';
        $j = $this->gql($m);
        $st = $j['data']['deleteExtension']['status'] ?? null;
        if ($st) { $this->reload(); return ['ok' => 1]; }
        return ['ok' => 0, 'hata' => $this->hata ?: 'Silinemedi', 'ham' => $this->sonHam];
    }

    /** Dahili SIP sifresini degistir. */
    public function sifre($numara, $sifre): array
    {
        $num = (int) $numara;
        $sifre = $this->kacar($sifre);
        $m = 'mutation { updateExtension(input: { extensionId: ' . $num . ', extPassword: "' . $sifre . '" }) { status message } }';
        $j = $this->gql($m);
        $st = $j['data']['updateExtension']['status'] ?? null;
        if ($st) { $this->reload(); return ['ok' => 1]; }
        return ['ok' => 0, 'hata' => $this->hata ?: 'Güncellenemedi', 'ham' => $this->sonHam];
    }

    /** TESHIS: bir input type'in kabul ettigi alanlari sema'dan (introspection) getir. */
    public function inputAlanlari($typeName): array
    {
        $q = 'query { __type(name: "' . $typeName . '") { inputFields { name type { kind name ofType { kind name } } } } }';
        $j = $this->gql($q);
        $fields = $j['data']['__type']['inputFields'] ?? null;
        $out = [];
        if (is_array($fields)) {
            foreach ($fields as $f) {
                $kind = $f['type']['kind'] ?? '';
                $ad = $f['type']['name'] ?? ($f['type']['ofType']['name'] ?? '');
                $out[] = ['ad' => $f['name'], 'tip' => $ad ?: $kind, 'zorunlu' => ($kind === 'NON_NULL')];
            }
        }
        return $out;
    }

    /** Degisiklikleri uygula (fwconsole reload karsiligi). Hata olsa da islemi bozmaz. */
    public function reload(): void
    {
        try { $this->gql('mutation { reload(input: {}) { status message } }'); } catch (\Throwable $e) {}
    }

    protected function kacar($s): string
    {
        // GraphQL string kacisi (cift tirnak + ters bolu)
        return str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', ' ', ' '], (string) $s);
    }
}

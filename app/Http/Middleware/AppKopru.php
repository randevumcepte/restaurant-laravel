<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UYGULAMA KÖPRÜSÜ (routes/app_kopru.php) — yalnız "X-Resteos-Kopru: 1" + Bearer token taşıyan istekte devreye girer.
 * - Web sayfasının döndürdüğü view → sayfanın verisi JSON (aynı route, aynı yetki kapıları, aynı hesap)
 * - Yönlendirme → {ok, yonlendir, mesaj|hata} (flash mesajıyla); giriş sayfasına yönlenme → 401
 * - "yetkisiz" sayfası → 403 JSON
 * Başlık yoksa hiçbir şeye dokunmaz (tarayıcı paneli aynen çalışır).
 */
class AppKopru
{
    private const ATLA = ['__env', 'app', 'errors', '__data', 'obLevel', '__path', '__currentLoopData', 'loop'];

    public function handle(Request $request, Closure $next)
    {
        if (!$this->kopruMu($request)) return $next($request);

        $resp = $next($request);

        if ($resp instanceof JsonResponse) return $resp;

        if ($resp instanceof RedirectResponse) {
            $hedef = (string) parse_url($resp->getTargetUrl(), PHP_URL_PATH);
            if (in_array(rtrim($hedef, '/'), ['/pos-giris', '/giris', '/login'], true)) {
                // Token geçerliyse sayfa yetki yüzünden girişe atıyor → 403 (uygulama oturumu kapatmasın); geçersizse 401
                if (function_exists('_kopruPersonel') && _kopruPersonel()) return response()->json(['ok' => 0, 'hata' => 'Bu sayfa için yetkiniz yok.', 'yetkisiz' => 1], 403);
                return response()->json(['ok' => 0, 'hata' => 'Oturum gerekli.', 'giris' => 1], 401);
            }
            [$mesaj, $hata] = $this->flash($request, $resp->getSession());
            return response()->json(['ok' => $hata ? 0 : 1, 'yonlendir' => $hedef ?: '/', 'mesaj' => $mesaj, 'hata' => $hata]);
        }

        $orig = $resp->original ?? null;
        if ($orig instanceof \Illuminate\View\View) {
            if ($orig->name() === 'yetkisiz') return response()->json(['ok' => 0, 'hata' => 'Bu sayfa için yetkiniz yok.', 'yetkisiz' => 1], 403);
            [$mesaj, $hata] = $this->flash($request);
            return response()->json([
                'ok' => $resp->getStatusCode() < 400 ? 1 : 0, '_view' => $orig->name(),
                'mesaj' => $mesaj, 'hata' => $hata, 'veri' => $this->temizle($orig->getData(), 0),
            ], $resp->getStatusCode());
        }

        // Dosya (Excel/PDF) ve ham HTML olduğu gibi döner; uygulama içerik türüne bakar
        $resp->headers->set('X-Resteos-Kopru', 'ham');
        return $resp;
    }

    private function kopruMu(Request $r): bool
    {
        return $r->headers->get('X-Resteos-Kopru') === '1' && (string) $r->bearerToken() !== '';
    }

    // Oturum flash mesajı (sayfalar ok/basari/hata vb. anahtarlar kullanıyor)
    private function flash(Request $r, $oturum = null): array
    {
        $mesaj = null; $hata = null;
        try {
            $s = $oturum ?: $r->session(); // yönlendirmenin kendi oturumu (back()->with() buraya yazar)
            foreach (['ok', 'basari', 'success', 'mesaj', 'status', 'bilgi'] as $k) { $v = $s->get($k); if (is_string($v) && $v !== '') { $mesaj = $v; break; } }
            foreach (['hata', 'error', 'uyari'] as $k) { $v = $s->get($k); if (is_string($v) && $v !== '') { $hata = $v; break; } }
            if (!$hata && ($e = $s->get('errors')) && method_exists($e, 'all') && $e->all()) $hata = implode(' ', $e->all());
            // Sayfaya özel anahtarlar: sebep_hata, bc_hata, yazici_ok, x_mesaj ... (bu istekte flash'lananlar)
            foreach ((array) $s->get('_flash.new', []) as $k) {
                $v = $s->get($k);
                if (!is_string($v) || $v === '') continue;
                if (!$hata && preg_match('/_(hata|error|uyari)$/', $k)) $hata = $v;
                elseif (!$mesaj && preg_match('/_(ok|mesaj|basari|bilgi)$/', $k)) $mesaj = $v;
            }
        } catch (\Throwable $e) {}
        return [$mesaj, $hata];
    }

    // Web sayfası bu alanları ekrana basmasa da view'a geçebiliyor (personel kaydı vb.) → köprüden ASLA çıkmaz
    private function gizliAnahtar(string $k): bool
    {
        $k = strtolower($k);
        if (in_array($k, ['pin', 'api_token', 'password', 'sifre', 'parola', 'remember_token', 'secret', 'client_secret', 'api_key', 'apikey', 'private_key', 'offline_pin', 'pin_hash', 'webhook_token'], true)) return true;
        foreach (['_secret', '_api_key', '_apikey', '_password', '_sifre', '_private_key', '_pin'] as $son) if (str_ends_with($k, $son)) return true;
        return false;
    }

    // View verisini JSON'a uygun hale getir (closure/servis nesneleri atılır)
    private function temizle($v, int $d)
    {
        if ($d > 9) return null;
        if ($v === null || is_scalar($v)) {
            if (is_string($v) && strlen($v) > 400000) return mb_substr($v, 0, 100000) . '…';
            if (is_float($v) && (is_nan($v) || is_infinite($v))) return null;
            return $v;
        }
        if ($v instanceof \Closure) return null;
        if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d H:i:s');
        if ($v instanceof \Illuminate\Support\HtmlString) return (string) $v;
        if ($v instanceof \Illuminate\Contracts\Support\Arrayable) $v = $v->toArray();
        elseif ($v instanceof \JsonSerializable) $v = $v->jsonSerialize();
        elseif ($v instanceof \stdClass) $v = get_object_vars($v);
        elseif (is_object($v)) return null;
        if (!is_array($v)) return $this->temizle($v, $d + 1);
        $o = [];
        foreach ($v as $k => $x) {
            if ($d === 0 && is_string($k) && in_array($k, self::ATLA, true)) continue;
            if (is_string($k) && $this->gizliAnahtar($k)) continue; // PIN/token/şifre view'da olsa da uygulamaya gitmez
            if ($x instanceof \Closure) continue;
            $o[$k] = $this->temizle($x, $d + 1);
        }
        return $o;
    }
}

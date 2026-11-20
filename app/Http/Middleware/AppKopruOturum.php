<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Uygulama köprüsü isteği (X-Resteos-Kopru: 1 + Bearer): oturum diske yazılmaz (her istek için dosya/çerez oluşmasın).
 * web grubunun EN BAŞINDA çalışır (StartSession'dan önce). Başlık yoksa dokunmaz.
 */
class AppKopruOturum
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->headers->get('X-Resteos-Kopru') === '1' && (string) $request->bearerToken() !== '') {
            config(['session.driver' => 'array']);
            // Sayfalar hata/doğrulama cevabını JSON istesin diye (ekran açıklaması JSON olarak gelir)
            $request->headers->set('Accept', 'application/json');
        }
        return $next($request);
    }
}

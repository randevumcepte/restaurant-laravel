<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

/**
 * CSRF: tarayıcıda aynen (çerezli oturum). Uygulama köprüsü isteği çerez değil Bearer token taşır;
 * token geçerli bir personele aitse CSRF aranmaz (token tarayıcıda kendiliğinden gönderilmez → CSRF riski yok).
 */
class KopruCsrf extends PreventRequestForgery
{
    protected function tokensMatch($request)
    {
        if (function_exists('_kopruPersonel') && $request->headers->get('X-Resteos-Kopru') === '1' && _kopruPersonel()) return true;
        return parent::tokensMatch($request);
    }
}

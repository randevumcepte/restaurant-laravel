<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Disaridan POST eden yollar (paket webhook + Flutter API) -> CSRF muafiyeti
        $middleware->validateCsrfTokens(except: ['webhook/*', 'api/*', 'rez/*', 'kurye/*', 'ode/*', 'satinalma-talep/*']);
        // Aktivite log: her degistiren API cagrisini terminate'de (yanit sonrasi) loglar -> istegi bozmaz
        $middleware->append(\App\Http\Middleware\AktiviteLog::class);
        // Web panel sayfa kapisi: menudeki sayfalar yetkiyle korunur (session'dan sonra calisir)
        // Santral uclarinin tek kapisi (panel + kasa uygulamasi + Sistem Yonetimi; kopru uclari haric)
        // Uygulama köprüsü (X-Resteos-Kopru + Bearer): oturum diske yazılmaz (en başta), CSRF yerine token,
        // web sayfası verisi JSON (PanelYetki'nin dışında: 403/yönlendirme de JSON olur). Başlıksız istekte etkisiz.
        $middleware->web(
            append: [\App\Http\Middleware\AppKopru::class, \App\Http\Middleware\PanelYetki::class, \App\Http\Middleware\SantralKapi::class],
            prepend: [\App\Http\Middleware\AppKopruOturum::class],
            replace: [\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class => \App\Http\Middleware\KopruCsrf::class],
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

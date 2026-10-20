<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Web panel sayfa kapısı: menüdeki her sayfa yetkiyle korunur.
 * - Sayfa _sayfaYetkiHaritasi()'nda değilse (müşteri/API/herkese açık) → dokunma.
 * - Kapılıysa: oturumda personel yoksa → /pos-giris'e yönlendir (hedef saklanır).
 * - Personel varsa: sahip her zaman; değilse _panelYetkiVar() ile kontrol → yoksa 403.
 * Böylece yetkisi olmayan sayfaya URL yazsa bile giremez (menüde de görünmez).
 */
class PanelYetki
{
    public function handle(Request $request, Closure $next)
    {
        // Yalnızca GET sayfa görüntülemelerini kapıla (POST aksiyonlar kendi route'unda zaten aynı sayfadan tetiklenir)
        $path = '/' . trim($request->path(), '/');

        if (!function_exists('_sayfaYetkiHaritasi')) return $next($request);
        $harita = _sayfaYetkiHaritasi();

        // En uzun eşleşen öneki bul (ör. /pos/masa/5 -> /pos)
        $eslesen = null; $key = null;
        foreach ($harita as $onek => $yk) {
            if ($path === $onek || str_starts_with($path, $onek . '/')) {
                if ($eslesen === null || strlen($onek) > strlen($eslesen)) { $eslesen = $onek; $key = $yk; }
            }
        }
        if ($eslesen === null) return $next($request); // kapılı değil → serbest

        if (function_exists('_panelYetkiEnsure')) { try { _panelYetkiEnsure(); } catch (\Throwable $e) {} }

        // Oturumdaki personel
        $p = null;
        try { if (function_exists('_posPersonel')) $p = _posPersonel(); } catch (\Throwable $e) {}

        if (!$p) {
            try { $request->session()->put('panel_hedef', $request->fullUrl()); } catch (\Throwable $e) {}
            return redirect('/pos-giris');
        }

        // Yetki kontrolü
        $izin = true;
        try { $izin = function_exists('_panelYetkiVar') ? _panelYetkiVar($p, $key) : true; } catch (\Throwable $e) { $izin = true; }
        if (!$izin) {
            return response(view('yetkisiz', ['sayfa' => $eslesen]), 403);
        }

        return $next($request);
    }
}

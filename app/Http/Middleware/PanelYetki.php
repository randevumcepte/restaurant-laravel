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
        if (function_exists('_masaYetkiTemizle')) _masaYetkiTemizle();
        if (function_exists('_stokYetkiBolmeTemizle')) _stokYetkiBolmeTemizle();
        if (function_exists('_mutfakSefYetkiTemizle')) _mutfakSefYetkiTemizle();
        if (function_exists('_talepYetkiTemizle')) _talepYetkiTemizle();
        if (function_exists('_mutfakSefMenuYetki')) _mutfakSefMenuYetki();
        if ($sinir = $this->ucretliSinir($request, $path)) return $sinir;
        if ($ekRed = $this->ekKapi($request, $path)) return $ekRed;
        if ($subeRed = $this->subeKapi($request, $path)) return $subeRed;
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
        // Uygulama (Flutter) oturum değil Bearer token kullanır: aynı yetki kontrolünden geçer, girişe yönlenmez.
        if (!$p && $request->bearerToken() && function_exists('_apiPersonel')) { try { $p = _apiPersonel($request); } catch (\Throwable $e) {} }

        if (!$p) {
            try { $request->session()->put('panel_hedef', $request->fullUrl()); } catch (\Throwable $e) {}
            return redirect('/pos-giris');
        }

        // Yetki kontrolü
        $izin = true;
        try { $izin = function_exists('_panelYetkiVar') ? _panelYetkiVar($p, $key) : true; } catch (\Throwable $e) { $izin = true; }
        if ($izin && function_exists('_kasiyerSayfaIzinli')) $izin = _kasiyerSayfaIzinli($p, $eslesen);
        if ($izin && in_array($eslesen, ['/uretim-riski', '/parti-takip', '/sayim'], true) && function_exists('_mutfakAltIstasyonMu') && _mutfakAltIstasyonMu($p)) $izin = false; // mutfak geneli ekranlar: şef ve yönetici
        if ($izin && $eslesen === '/bulasik' && function_exists('_bulasikGorur') && !_bulasikGorur($p)) $izin = false;
        if (!$izin) {
            return response(view('yetkisiz', ['sayfa' => $eslesen]), 403);
        }

        return $next($request);
    }

    // _ekYetkiHaritasi: menü dışı yönetim sayfaları + /api uçları. Şube parametresi kişinin şubesine sabitlenir. null = geçti.
    private function ekKapi(Request $request, string $path)
    {
        if (!function_exists('_ekYetkiAnahtari')) return null;
        $anahtar = _ekYetkiAnahtari($path);
        if ($anahtar === null) return null;
        // Demo/hata ayıklama uçları yalnız .env RESTEOS_DEMO_UCLARI=1 ile açılır (canlı sunucu APP_ENV=local olabildiği için ortama bakılmaz)
        if ($anahtar === 'yerel') return filter_var(env('RESTEOS_DEMO_UCLARI', false), FILTER_VALIDATE_BOOLEAN) ? null : response()->json(['ok' => 0], 404);
        if (session('ry_admin_id')) return null; // Sistem Yönetimi tüm şubelerde çalışır

        $api = str_starts_with($path, '/api/');
        $p = null;
        try { $p = _posPersonel(); } catch (\Throwable $e) {}
        if (!$p && $request->bearerToken()) { try { $p = _apiPersonel($request); } catch (\Throwable $e) {} }
        if (!$p) {
            if ($api) return response()->json(['ok' => 0, 'hata' => 'Oturum gerekli'], 401);
            try { $request->session()->put('panel_hedef', $request->fullUrl()); } catch (\Throwable $e) {}
            return redirect('/pos-giris');
        }
        if (!_ekYetkiVar($p, $anahtar)) {
            if ($api) return response()->json(['ok' => 0, 'hata' => 'Bu işlem için yetkiniz yok'], 403);
            return response(view('yetkisiz', ['sayfa' => $path]), 403);
        }
        if ($request->has('sube')) { $request->query->set('sube', $p->sube_id); $request->request->set('sube', $p->sube_id); }
        return null;
    }

    // Personelin istekte verdiği adisyon/masa/ürün... numarası başka şubenin kaydıysa reddet. Tek restoranda etkisiz.
    private function subeKapi(Request $request, string $path)
    {
        if (!function_exists('_subeKaydiTablosu') || session('ry_admin_id')) return null;
        $p = null;
        try { $p = _posPersonel(); } catch (\Throwable $e) {}
        if (!$p && $request->bearerToken()) { try { $p = _apiPersonel($request); } catch (\Throwable $e) {} }
        if (!$p) return null;
        foreach (_subeKaydiTablosu($path) as $alan => $tablo) {
            $deger = $request->input($alan);
            if (!is_scalar($deger) || (int) $deger <= 0) continue;
            $sube = $this->kayitSubesi($tablo, (int) $deger);
            if ($sube !== null && $sube !== (int) $p->sube_id) {
                return str_starts_with($path, '/api/') ? response()->json(['ok' => 0, 'hata' => 'Kayıt bu işletmeye ait değil'], 403) : response(view('yetkisiz', ['sayfa' => $path]), 403);
            }
        }
        return null;
    }

    // Kaydın şubesi; tablo/kolon yoksa ya da kayıt bulunamazsa null (karar route'a kalır)
    private function kayitSubesi(string $tablo, int $id): ?int
    {
        try {
            $db = \Illuminate\Support\Facades\DB::table($tablo === 'adisyon_kalemleri' || $tablo === 'odemeler' ? $tablo . ' as k' : $tablo);
            if ($tablo === 'adisyon_kalemleri' || $tablo === 'odemeler') {
                $v = $db->join('adisyonlar', 'adisyonlar.id', '=', 'k.adisyon_id')->where('k.id', $id)->value('adisyonlar.sube_id');
            } else {
                $kolon = cache()->remember('sube_kolon_' . $tablo, 3600, fn () => \Illuminate\Support\Facades\Schema::hasTable($tablo) && \Illuminate\Support\Facades\Schema::hasColumn($tablo, 'sube_id'));
                if (!$kolon) return null;
                $v = $db->where('id', $id)->value('sube_id');
            }
            return $v === null ? null : (int) $v;
        } catch (\Throwable $e) { return null; }
    }

    // Ücretli dış servis çağıran açık uçlar (ses, çeviri, QR asistan): IP başına dakikada sınır
    private function ucretliSinir(Request $request, string $path)
    {
        if (!in_array($path, ['/api/tts', '/api/m/ses', '/api/qr/stt', '/api/qr/cevir', '/api/qr/asistan'], true)) return null;
        $anahtar = 'ucretli:' . $path . ':' . $request->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($anahtar, 120)) return response()->json(['ok' => 0, 'hata' => 'Çok fazla istek, biraz sonra tekrar deneyin.'], 429);
        \Illuminate\Support\Facades\RateLimiter::hit($anahtar, 60);
        return null;
    }
}

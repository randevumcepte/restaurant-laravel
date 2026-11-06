<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI Santral uçlarının tek kapısı (panel, kasa uygulaması, Sistem Yönetimi).
 * Giren kişi = Sistem Yönetimi oturumu, panel oturumu veya uygulama Bearer'ı.
 * Personelin şubesi isteğe zorla yazılır; id ile gelen kayıt başka şubeninse reddedilir.
 * Köprünün uçları (baslat/konus/bitir/geri-arama/kayit-yukle/aktarma-hedef) burada DEĞİL:
 * onları route içindeki _santralAuthGuard (SANTRAL_SECRET) korur.
 */
class SantralKapi
{
    // Toplu silme ve teşhis: yalnız Sistem Yönetimi veya köprü anahtarı
    private const YONETICI = ['api/santral/test-temizle', 'api/santral/musteri-sil', 'api/santral/musteri-teshis', 'api/santral/teshis-ozet', 'api/santral/sube-teshis', 'api/dahili/sema'];
    // Tüm restoranların ortak santral sunucusu: Sistem Yönetimi veya ana işletmenin (en küçük şube) yetkilisi
    private const SUNUCU = ['api/freepbx/', 'api/dahili/', 'freepbx-ayar', 'freepbx-ayar-kaydet', 'dahili-yonetim'];
    private const HAT = ['api/hat/', 'hat-yonetim'];
    private const PANEL = ['api/santral-kayit/', 'api/santral-egitim/', 'api/santral-ses/', 'api/santral/ayar', 'api/santral/ayar-kaydet', 'api/santral/subeler', 'santral-ses-dinle/', 'callerid-test', 'callerid-kur'];
    // id ile çalışan uç => kaydın tablosu (şube sahipliği denetimi)
    private const KAYIT_TABLOSU = [
        'api/santral-egitim/kalip-guncelle' => 'santral_kalip', 'api/santral-egitim/kalip-sil' => 'santral_kalip',
        'api/santral-egitim/ogrenilen-guncelle' => 'santral_ai_ogrenilen', 'api/santral-egitim/ogrenilen-sil' => 'santral_ai_ogrenilen',
        'api/santral-egitim/cozulemeyen-kalipla' => 'santral_cozulemeyen', 'api/santral-egitim/cozulemeyen-sil' => 'santral_cozulemeyen',
        'api/santral-egitim/telaffuz-sil' => 'santral_telaffuz',
    ];

    public function handle(Request $request, Closure $next)
    {
        $path = trim($request->path(), '/');
        if (in_array($path, self::YONETICI, true)) {
            if ($this->anahtarGecerli($request) || $this->yonetici()) return $next($request);
            return $this->red(401, 'Yetkisiz');
        }
        if ($path === 'api/callerid-goruldu') return $this->cagriGoruldu($request, $next);

        $sinif = $this->eslesir($path, self::SUNUCU) ? 'sunucu' : ($this->eslesir($path, self::HAT) ? 'hat' : ($this->eslesir($path, self::PANEL) ? 'panel' : null));
        if (!$sinif) return $next($request);

        // Sesler: Sistem Yönetimi/oturum yoksa kayıt detayının verdiği süreli imzalı adres (uygulamanın ses çalarında başlık gönderilemez)
        if (str_starts_with($path, 'santral-ses-dinle/') && $this->sesImzasiGecerli($request, $path)) {
            $request->attributes->set('santral_sube', null);
            return $next($request);
        }

        if ($this->yonetici()) {
            $request->attributes->set('santral_sube', session('ry_imp_sube') ? (int) session('ry_imp_sube') : null);
            return $next($request);
        }

        $p = _apiPersonel($request);
        if (!$p) return $this->red(401, 'Oturum gerekli', $request);

        $yetki = $sinif === 'panel' ? 'gor_santral' : 'gor_odeme_ayar';
        if (!_panelYetkiVar($p, $yetki)) return $this->red(403, 'Bu işlem için yetkiniz yok', $request);

        $sube = (int) $p->sube_id;
        if ($sinif === 'sunucu' && $sube !== (int) DB::table('subeler')->min('id')) return $this->red(403, 'Santral sunucusu Sistem Yönetimi\'nden yönetilir', $request);

        $request->attributes->set('santral_sube', $sube);
        $request->merge(['sube_id' => $sube]);

        $tablo = self::KAYIT_TABLOSU[$path] ?? null;
        if ($tablo && Schema::hasTable($tablo)) {
            $kayitSube = DB::table($tablo)->where('id', (int) $request->input('id'))->value('sube_id');
            if ($kayitSube !== null && (int) $kayitSube !== $sube) return $this->red(404, 'Kayıt yok');
        }
        return $next($request);
    }

    private function eslesir($path, array $liste)
    {
        foreach ($liste as $l) {
            if (str_ends_with($l, '/') ? str_starts_with($path, $l) : $path === $l) return true;
        }
        return false;
    }

    private function yonetici()
    {
        return function_exists('_ryAdmin') && session('ry_admin_id') && _ryAdmin();
    }

    private function anahtarGecerli(Request $r)
    {
        $beklenen = (string) env('SANTRAL_SECRET', '');
        $gelen = (string) ($r->header('X-Santral-Secret') ?: $r->input('santral_secret') ?: '');
        return $beklenen !== '' && hash_equals($beklenen, $gelen);
    }

    private function sesImzasiGecerli(Request $r, $path)
    {
        $id = (int) substr($path, strlen('santral-ses-dinle/'));
        $son = (int) $r->query('son');
        $imza = (string) $r->query('imza');
        return $imza !== '' && $son >= time() && hash_equals(_santralSesImza($id, $son), $imza);
    }

    private function cagriGoruldu(Request $r, Closure $next)
    {
        $p = _apiPersonel($r);
        if (!$p) return $this->red(401, 'Oturum gerekli');
        $kayitSube = DB::table('cagri_loglari')->where('id', (int) $r->input('cagri_id'))->value('sube_id');
        if ($kayitSube !== null && (int) $kayitSube !== (int) $p->sube_id) return $this->red(404, 'Kayıt yok');
        return $next($r);
    }

    private function red($kod, $mesaj, ?Request $r = null)
    {
        // Sayfa açılışı girişe gider; API her zaman JSON döner (uygulama yanıtı JSON okur)
        if ($r && $kod === 401 && !$r->is('api/*') && !$r->is('santral-ses-dinle/*')) return redirect('/pos-giris');
        return response()->json(['ok' => 0, 'hata' => $mesaj], $kod, [], JSON_UNESCAPED_UNICODE);
    }
}

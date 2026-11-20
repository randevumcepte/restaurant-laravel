<?php
// =====================================================================
// UYGULAMA KÖPRÜSÜ — uygulama (Flutter) web panel sayfasını Bearer token ile açar; sayfa aynı kapılardan geçer.
// İstek: Authorization: Bearer <api_token> + X-Resteos-Kopru: 1
//  - _posPersonel() oturum yoksa kişiyi token'dan bulur (routes/web.php) → sayfa web'deki kuralla çalışır
//  - App\Http\Middleware\AppKopru: oturum diske yazılmaz, CSRF yerine token; view → JSON (sayfanın verisi)
// Tarayıcı istekleri (başlık yok) hiç etkilenmez.
// =====================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (!function_exists('_kopruIstek')) {
    function _kopruIstek($r = null)
    {
        try {
            $r = $r ?: request();
            return $r->headers->get('X-Resteos-Kopru') === '1' && (string) $r->bearerToken() !== '';
        } catch (\Throwable $e) { return false; }
    }
    // Köprü isteğinin personeli (token geçerli, aktif, şubesi kilitli değil); değilse null
    function _kopruPersonel()
    {
        static $onbellek = [];
        if (!_kopruIstek()) return null;
        $tok = (string) request()->bearerToken();
        if (!array_key_exists($tok, $onbellek)) {
            $p = DB::table('personeller')->where('api_token', $tok)->where('aktif', 1)->first();
            if ($p && ($p->rol ?? '') !== 'sahip' && function_exists('_subeKilitli') && _subeKilitli($p->sube_id)) $p = null;
            $onbellek[$tok] = $p;
        }
        return $onbellek[$tok];
    }
}

if (!function_exists('_panelMenuJson')) {
    // Web sol menüsünün aynısı (sıra, kategori, grup, görünürlük, rozet) — uygulama menüsü bundan çizilir
    function _panelMenuJson()
    {
        extract(_panelMenuVeri());
        $grupAdi = fn ($ad) => ['Departman Operasyon' => 'Operasyon'][$ad] ?? $ad;
        $grupJson = function ($ad, $items, $basliksiz) use ($_pp, $_harita, $_menuBadge, $grupAdi) {
            $o = []; $say = 0;
            foreach ($items as $it) {
                if (!_menuOgeGorunur($_pp, $_harita, $it)) continue;
                $rz = (int) ($_menuBadge[$it[0]] ?? 0); $say += $rz;
                $o[] = ['url' => $it[0], 'ikon' => $it[1], 'ad' => $it[2], 'renk' => $it[3] ?? null, 'rozet' => $rz, 'rapor' => _menuRaporMu($it[0])];
            }
            return $o ? ['anahtar' => $ad, 'ad' => $grupAdi($ad), 'ikon' => _menuGrupIkon($ad), 'basliksiz' => (bool) $basliksiz, 'rozet' => $say, 'ogeler' => $o] : null;
        };
        $bolumler = [];
        $ekle = function ($baslik, $ikon, array $gruplar_) use (&$bolumler) { $g = array_values(array_filter($gruplar_)); if ($g) $bolumler[] = ['baslik' => $baslik, 'ikon' => $ikon, 'gruplar' => $g]; };
        if ($_duzMenu) {
            $sira = array_merge(array_diff(array_keys($gruplar), ['Ajanda', 'Ayarlar & Entegrasyon']), ['Ajanda', 'Ayarlar & Entegrasyon']);
            $gorulen = []; $gs = [];
            foreach ($sira as $ad) {
                if (!isset($gruplar[$ad])) continue;
                $items = array_values(array_filter($gruplar[$ad], function ($it) use (&$gorulen) { if (isset($gorulen[$it[0]])) return false; $gorulen[$it[0]] = 1; return true; }));
                $gs[] = $grupJson($ad, $items, true);
            }
            $ekle(null, null, $gs);
        } else {
            foreach ($gruplar as $ad => $items) if (!isset($_grupKat[$ad]) && $ad !== 'Ajanda') $ekle(null, null, [$grupJson($ad, $items, $ad === 'Brifing')]);
            foreach ($_kategoriler as $katAd => $katGruplar) {
                if ($katAd === 'AYARLAR' && isset($gruplar['Ajanda'])) $ekle(null, null, [$grupJson('Ajanda', $gruplar['Ajanda'], false)]);
                $gs = [];
                foreach ($katGruplar as $g) if (isset($gruplar[$g]) && $_grupGorunur($gruplar[$g])) $gs[] = $grupJson($g, $gruplar[$g], in_array($g, ['Ayarlar & Entegrasyon', 'Menü & Ürünler'], true));
                if (!$gs) continue;
                if ($_katsiz && $katAd !== 'AYARLAR') { foreach ($gs as $g) $ekle(null, null, [$g ? ['basliksiz' => false] + $g : null]); continue; }
                $ekle(['YÖNETİM' => 'DEPARTMANLAR'][$katAd] ?? $katAd, $_katIkon[$katAd] ?? 'folder', $gs);
            }
        }
        return [
            'ok' => 1, 'kasiyer' => (bool) $_kasiyerMenu, 'duz' => (bool) $_duzMenu,
            'kisi' => ['ad' => $kulAd, 'rol' => $_pp->rol ?? null, 'unvan' => $kulRol, 'sube' => $aktifSubeAd, 'foto' => !empty($_pp->foto)],
            'bolumler' => $bolumler,
        ];
    }
}

// Uygulama menüsü: web sol menüsüyle aynı (yetki/departman/büyüklük/kasiyer kuralları tek yerde)
Route::get('/panel-menu', function () {
    if (!_posPersonel()) return response()->json(['ok' => 0, 'hata' => 'Oturum gerekli', 'giris' => 1], 401);
    return response()->json(_panelMenuJson());
});

// Eski "Memnuniyet / Şikayet" bağlantısı (rotası yoktu, 404): brifing kartları ve bildirimler hâlâ bu adresi verebilir → İtibar / Memnuniyet
Route::get('/memnuniyet', fn () => redirect('/itibar'));

// Web şablonunun içinde hesaplanan sayfa verileri tek yerde: şablon da uygulama (köprü JSON'u) da aynısını kullanır
if (!function_exists('_departmanBriefEk')) {
    function _departmanBriefEk($kod)
    {
        $sid = function_exists('_webSubeId') ? _webSubeId() : null;
        $bp = function_exists('_posPersonel') ? _posPersonel() : null;
        // DEPARTMAN SORUMLUSU: onayını bekleyen satın alma talepleri
        $onayN = 0;
        try {
            if ($bp && $sid && function_exists('_sorumluDepartmanIdleri')) {
                $sd = _sorumluDepartmanIdleri($sid, $bp->id ?? 0);
                if ($sd) $onayN = (int) DB::table('satinalma_talepleri')->where('sube_id', $sid)->where('durum', 'departman_onay')->whereIn('departman_id', $sd)->count();
            }
        } catch (\Throwable $e) {}
        // Mutfak (BOH) alt departman sekmeleri: işletmede kurulu olanlar (+ ana mutfak ve açık olan); istasyon çalışanı yalnız kendi sekmesini görür
        $alt = [];
        try {
            $aktif = function_exists('_aktifDepartmanKodlari') ? _aktifDepartmanKodlari($sid) : [];
            $boh = array_filter(function_exists('_departmanSablonlari') ? _departmanSablonlari() : [], fn ($t, $k) => ($t['grup'] ?? '') === 'Mutfak (BOH)' && ($k === 'mutfak' || $k === $kod || in_array($k, $aktif, true)), ARRAY_FILTER_USE_BOTH);
            $boh = array_filter($boh, fn ($k) => $k === $kod || _departmanBriefIzinli($bp, $sid, $k), ARRAY_FILTER_USE_KEY);
            if (isset($boh[$kod]) && count($boh) > 1) {
                foreach ($boh as $k => $t) $alt[] = ['kod' => $k, 'ad' => $k === 'mutfak' ? 'Genel / Sıcak' : trim(explode('/', $t['ad'])[0]), 'ikon' => $t['ikon'], 'renk' => $t['renk'], 'aktif' => $k === $kod];
            }
        } catch (\Throwable $e) {}
        return ['onayBekleyenTalep' => $onayN, 'altDepartmanlar' => $alt];
    }
}
if (!function_exists('_profilimEk')) {
    function _profilimEk($p, $yetki)
    {
        $para = fn ($v) => '₺' . number_format((float) $v, 0, ',', '.');
        $yetkiVar = fn ($k) => _panelYetkiVar($p, $k);
        // Sayfa kapısı (PanelYetki) ile aynı kural: gösterilen her bağlantı gerçekten açılır
        $harita = _sayfaYetkiHaritasi();
        $sayfaAcik = fn ($url) => !isset($harita[$url]) || _panelYetkiVar($p, $harita[$url]);
        $kisayollar = array_values(array_filter([
            ['/bildirim-ayar', 'notifications_active', '#0EA5E9', 'Bildirim ayarları'],
            ['/puantaj', 'history', '#4F46E5', 'Puantajım'],
            ['/ik-izin', 'beach_access', '#14B8A6', 'İzin / Avans'],
            ['/garson-performans', 'emoji_events', '#10B981', 'Performansım'],
            ['/servis', 'room_service', '#4F46E5', 'İş Akışım'],
            ['/garson-cagrilari', 'notifications_active', '#F43F5E', 'Çağrılar'],
            ['/patron-ai', 'auto_awesome', '#7C3AED', 'Sabah Brifingi'],
            ['/ik-ozet', 'groups', '#10B981', 'İK Özet'],
            ['/ik-risk', 'crisis_alert', '#F97316', 'Risk Radarı'],
            ['/personel', 'badge', '#4F46E5', 'Personel yönetimi'],
            ['/bagli-cihazlar', 'devices_other', '#0EA5E9', 'Bağlı cihazlar'],
            ['/kasa', 'account_balance', '#F59E0B', 'Kasa (Vardiya)'],
        ], fn ($k) => $sayfaAcik($k[0])));
        if (in_array($p->rol ?? '', ['satinalmaci', 'satinalma'])) {
            $kisayollar[] = ['/departman-brief?dep=satinalma', 'wb_sunny', '#7C3AED', 'Satınalma Brifingi'];
            if ($sayfaAcik('/satinalma-asistan')) $kisayollar[] = ['/satinalma-asistan', 'smart_toy', '#7C3AED', 'AI Asistan'];
        }
        return [
            'kisayollar' => $kisayollar,
            'yetkiSol' => [
                ['shopping_cart', 'Sipariş Yönetimi', $yetkiVar('gor_pos')],
                ['storefront', 'Masa Yönetimi', $yetkiVar('gor_masa_yonetim')],
                ['sell', 'İndirim', ($yetki['iskonto_limit'] ?? 0) > 0 ? '%' . number_format($yetki['iskonto_limit'], 0) : null],
                ['card_giftcard', 'İkram', ($yetki['ikram_limit'] ?? 0) > 0 ? $para($yetki['ikram_limit']) : null],
            ],
            'yetkiSag' => [
                ['bar_chart', 'Raporlar', $yetkiVar('gor_raporlar')],
                ['group', 'Personel Yönetimi', $yetkiVar('gor_personel')],
                ['insights', 'Finans', $yetkiVar('gor_finans')],
            ],
        ];
    }
}
\Illuminate\Support\Facades\View::composer('departman_brief', fn ($v) => $v->with(_departmanBriefEk($v->getData()['kod'] ?? null)));
\Illuminate\Support\Facades\View::composer('profilim', function ($v) {
    $d = $v->getData();
    if (isset($d['p'])) $v->with(_profilimEk($d['p'], $d['yetki'] ?? []));
});

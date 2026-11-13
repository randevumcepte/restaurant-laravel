<?php
/*
 *  GELEN e-FATURA — ENTEGRATÖR SÜRÜCÜLERİ + OTOMATİK ÇEKİM MOTORU (efatura_gelen.php'den require)
 *  Sürücü sözleşmesi:
 *    listele($b, $bas, $bit, $sayfa) → ['ok'=>1, 'kayitlar'=>[['uuid'=>..,'no'=>..,'tarih'=>..], ...], 'son_sayfa'=>bool] | ['ok'=>0,'hata'=>..]
 *    xml($b, $uuid)                  → ['ok'=>1, 'xml'=>string] | ['ok'=>0,'hata'=>..]
 *    cevap($b, $uuid, $kabul, $not)  → ['ok'=>1] | ['ok'=>0,'hata'=>..]   (ticari fatura kabul/red)
 *  Nilvera: developer.nilvera.com (GET /einvoice/Purchase, GET /Purchase/{UUID}/xml, POST /Purchase/SendAnswer, Bearer anahtar)
 *  Diğerleri listede görünür; sürücüsü yazılana kadar XML yükleme ile çalışılır.
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

if (!function_exists('_efgEntegratorler')) {
    function _efgEntegratorler()
    {
        // kod => [ad, sürücü hazır mı]
        return [
            'nilvera' => ['Nilvera', true], 'izibiz' => ['İzibiz', false], 'uyumsoft' => ['Uyumsoft', false], 'qnb' => ['QNB eSolutions (eFinans)', false],
            'edm' => ['EDM Bilişim', false], 'turkcell' => ['Turkcell e-Şirket', false], 'logo' => ['Logo e-Fatura', false], 'diger' => ['Diğer / Kurum portalı', false],
        ];
    }
}

if (!function_exists('_efgAnahtar')) {
    function _efgAnahtar($b)
    {
        if (!$b || !$b->api_anahtar) return null;
        try { return Crypt::decryptString($b->api_anahtar); } catch (\Throwable $e) { return null; }
    }
}

if (!function_exists('_efgSurucu')) {
    function _efgSurucu($kod)
    {
        if ($kod !== 'nilvera') return null;
        $taban = fn ($b) => (($b->ortam ?? 'canli') === 'test' ? 'https://apitest.nilvera.com' : 'https://api.nilvera.com') . '/einvoice';
        $istek = function ($b) { $a = _efgAnahtar($b); return $a ? Http::withToken($a)->acceptJson()->timeout(40) : null; };
        $hata = function ($res) {
            $j = null; try { $j = $res->json(); } catch (\Throwable $e) {}
            $m = is_array($j) ? ($j['Errors'][0]['Description'] ?? $j['Message'] ?? $j['title'] ?? null) : null;
            if ($res->status() === 401 || $res->status() === 403) return 'API anahtarı geçersiz ya da yetkisi yok (HTTP ' . $res->status() . ')';
            return 'Nilvera HTTP ' . $res->status() . ($m ? ': ' . mb_substr((string) $m, 0, 120) : '');
        };
        return [
            'listele' => function ($b, $bas, $bit, $sayfa) use ($taban, $istek, $hata) {
                $h = $istek($b); if (!$h) return ['ok' => 0, 'hata' => 'API anahtarı girilmemiş'];
                // CreatedDate: Nilvera kutusuna düştüğü an — eski tarihli ama geç gelen fatura da yakalanır
                $res = $h->get($taban($b) . '/Purchase', ['StartDate' => $bas->format('Y-m-d\TH:i:s.000\Z'), 'EndDate' => $bit->format('Y-m-d\TH:i:s.000\Z'),
                    'DateFilterType' => 'CreatedDate', 'Page' => $sayfa, 'PageSize' => 50, 'SortColumn' => 'CreatedDate', 'SortType' => 'ASC']);
                if (!$res->successful()) return ['ok' => 0, 'hata' => $hata($res)];
                $j = $res->json();
                $k = [];
                foreach ((array) ($j['Content'] ?? []) as $c) if (!empty($c['UUID'])) $k[] = ['uuid' => (string) $c['UUID'], 'no' => $c['InvoiceNumber'] ?? null, 'tarih' => $c['IssueDate'] ?? null];
                $top = (int) ($j['TotalPages'] ?? 0);
                return ['ok' => 1, 'kayitlar' => $k, 'son_sayfa' => $top ? $sayfa >= $top : count($k) < 50];
            },
            'xml' => function ($b, $uuid) use ($taban, $istek, $hata) {
                $h = $istek($b); if (!$h) return ['ok' => 0, 'hata' => 'API anahtarı girilmemiş'];
                $res = $h->accept('*/*')->get($taban($b) . '/Purchase/' . rawurlencode($uuid) . '/xml');
                if (!$res->successful()) return ['ok' => 0, 'hata' => $hata($res)];
                $x = _efgHamXml($res->body());
                return $x ? ['ok' => 1, 'xml' => $x] : ['ok' => 0, 'hata' => 'XML biçimi tanınmadı'];
            },
            'cevap' => function ($b, $uuid, $kabul, $not) use ($taban, $istek, $hata) {
                $h = $istek($b); if (!$h) return ['ok' => 0, 'hata' => 'API anahtarı girilmemiş'];
                $res = $h->post($taban($b) . '/Purchase/SendAnswer', ['UUID' => $uuid, 'AnswerCode' => $kabul ? 'approved' : 'rejected', 'RejectNote' => $kabul ? null : mb_substr((string) $not, 0, 250)]);
                return $res->successful() ? ['ok' => 1] : ['ok' => 0, 'hata' => $hata($res)];
            },
        ];
    }
}

// Entegratör yanıtı: ham XML | JSON içinde (base64) metin | ZIP → ilk .xml
if (!function_exists('_efgHamXml')) {
    function _efgHamXml($govde)
    {
        $g = (string) $govde;
        if (strncmp($g, "\xEF\xBB\xBF", 3) === 0) $g = substr($g, 3);
        $t = ltrim($g);
        if ($t !== '' && $t[0] === '<') return $t;
        if (strncmp($g, "PK\x03\x04", 4) === 0) { $l = _efgZipXmller($g, 1); return $l[0] ?? null; }
        $j = json_decode($g, true);
        if (is_string($j)) $g = $j;
        elseif (is_array($j)) { foreach (['Content', 'content', 'Xml', 'xml', 'Data', 'data'] as $a) if (!empty($j[$a]) && is_string($j[$a])) { $g = $j[$a]; break; } }
        $t = ltrim($g);
        if ($t !== '' && $t[0] === '<') return $t;
        $d = base64_decode($g, true);
        if ($d !== false && $d !== '') return _efgHamXml($d) ?: null;
        return null;
    }
}
// ZIP içindeki .xml dosyalarının içeriği (en fazla $limit)
if (!function_exists('_efgZipXmller')) {
    function _efgZipXmller($ikili, $limit = 1000)
    {
        if (!class_exists('ZipArchive')) return [];
        $gecici = tempnam(sys_get_temp_dir(), 'efg');
        file_put_contents($gecici, $ikili);
        $out = [];
        $z = new \ZipArchive();
        if ($z->open($gecici) === true) {
            for ($i = 0; $i < $z->numFiles && count($out) < $limit; $i++) {
                $ad = (string) $z->getNameIndex($i);
                if (!preg_match('/\.xml$/i', $ad) || str_contains($ad, '__MACOSX')) continue;
                $st = $z->statIndex($i); if (($st['size'] ?? 0) > 15 * 1024 * 1024) continue;
                $c = $z->getFromIndex($i); if ($c !== false) $out[] = $c;
            }
            $z->close();
        }
        @unlink($gecici);
        return $out;
    }
}

// Otomatik çekim: son çekimden (2 gün geri payla) bugüne kadar yeni gelen faturaları indir + kaydet
// $zorla=false: 15 dakikada bir (cache kilidi). Dönüş: ['ok'=>, 'yeni'=>, 'aktarilan'=>, 'hata'=>]
if (!function_exists('_efgMotor')) {
    function _efgMotor($sid, $zorla = false)
    {
        _efgEnsure();
        $b = DB::table('efatura_baglanti')->where('sube_id', $sid)->first();
        if (!$b || !$b->aktif || !$b->entegrator) return ['ok' => 0, 'hata' => 'Bağlantı kapalı'];
        $s = _efgSurucu($b->entegrator);
        if (!$s) return ['ok' => 0, 'hata' => 'Bu entegratörün otomatik bağlantısı henüz yok'];
        $kilit = 'efg_motor_' . $sid;
        if (!$zorla && cache()->get($kilit)) return ['ok' => 1, 'atlandi' => 1];
        cache()->put($kilit, 1, now()->addMinutes(15));
        @set_time_limit(170);
        $basla = now();
        $bas = $b->son_cekim ? \Carbon\Carbon::parse($b->son_cekim)->subDays(2) : ($b->baslangic ? \Carbon\Carbon::parse($b->baslangic) : now()->subDays(30));
        $bit = now()->addMinutes(5);
        $yeni = 0; $aktarilan = 0; $hatali = 0; $bitti = true; $hata = null; $indirilen = 0;
        try {
            for ($sayfa = 1; $sayfa <= 40; $sayfa++) {
                $l = $s['listele']($b, $bas->copy()->utc(), $bit->copy()->utc(), $sayfa);
                if (empty($l['ok'])) { $hata = $l['hata'] ?? 'Liste alınamadı'; $bitti = false; break; }
                foreach ($l['kayitlar'] as $k) {
                    if (DB::table('efatura_gelen')->where('sube_id', $sid)->where('ettn', $k['uuid'])->exists()) continue;
                    if ($indirilen >= 300 || (now()->diffInSeconds($basla, true) > 150)) { $bitti = false; break 2; } // kalanı sonraki tura
                    $x = $s['xml']($b, $k['uuid']); $indirilen++;
                    if (empty($x['ok'])) { $hatali++; $hata = $x['hata'] ?? 'XML indirilemedi'; continue; }
                    $r = _efgKaydet($sid, $x['xml'], 'api:' . $b->entegrator);
                    if (($r['durum'] ?? '') === 'yeni') { $yeni++; if (!empty($r['aktarildi'])) $aktarilan++; } elseif (($r['durum'] ?? '') === 'hata') $hatali++;
                }
                if (!empty($l['son_sayfa'])) break;
            }
        } catch (\Throwable $e) { $hata = 'Bağlantı hatası: ' . mb_substr($e->getMessage(), 0, 120); $bitti = false; }
        $upd = ['son_sonuc' => mb_substr(now()->format('d.m.Y H:i') . ' · ' . $yeni . ' yeni, ' . $aktarilan . ' otomatik aktarıldı' . ($hatali ? ', ' . $hatali . ' hatalı' : ''), 0, 255),
            'son_hata' => $hata ? mb_substr($hata, 0, 255) : null, 'updated_at' => now()];
        if ($bitti && !$hata) $upd['son_cekim'] = $basla;
        DB::table('efatura_baglanti')->where('id', $b->id)->update($upd);
        if (!$bitti) cache()->forget($kilit); // yarım kaldıysa bir sonraki çağrıda devam
        return ['ok' => $hata ? 0 : 1, 'yeni' => $yeni, 'aktarilan' => $aktarilan, 'hatali' => $hatali, 'hata' => $hata, 'devam' => !$bitti];
    }
}

// Cron: tüm aktif bağlantılar (OS cron: curl "/api/efatura/motor?key=SANTRAL_SECRET" — 15 dk'da bir)
Route::get('/api/efatura/motor', function (Request $r) {
    $anahtar = (string) env('SANTRAL_SECRET', '');
    if ($anahtar === '' || !hash_equals($anahtar, (string) $r->query('key'))) return response()->json(['ok' => 0], 403);
    _efgEnsure();
    $sonuc = [];
    foreach (DB::table('efatura_baglanti')->where('aktif', 1)->pluck('sube_id') as $sid) $sonuc[$sid] = _efgMotor((int) $sid);
    return response()->json(['ok' => 1, 'subeler' => $sonuc]);
});

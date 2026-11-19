<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// MÜŞTERİ AI KONUŞMA KAYITLARI: QR masa asistanının her turu (müşteri ne dedi / STT ne duydu → asistan ne cevap
// verdi, cevabı kim verdi: kural / niyet(AI) / haiku / öğrenilen / ekran) kaydedilir. Hatalar ekran görüntüsü
// beklemeden buradan incelenir. 30 günden eski kayıtlar silinir (KVKK: müşteri sözleri süresiz tutulmaz).
//   /ai-kayitlar            → Sistem Yönetimi girişiyle okunur sayfa
//   /ai-kayitlar.json       → ?anahtar=... ile salt-okunur JSON (inceleme için paylaşılan link)
// ---------------------------------------------------------------------------
if (!function_exists('_aiKayitEnsure')) {
    function _aiKayitEnsure()
    {
        if (Cache::get('ai_kayit_tablo_ok1')) return;
        if (!Schema::hasTable('musteri_ai_kayit')) {
            Schema::create('musteri_ai_kayit', function ($t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('sube_id')->index();
                $t->unsignedBigInteger('masa_id')->nullable();
                $t->string('oturum', 32)->index();
                $t->string('kanal', 8)->default('');      // ses / yazi
                $t->text('soru')->nullable();              // müşterinin sözü (sesliyse STT'nin duyduğu)
                $t->text('cevap')->nullable();             // asistanın gösterdiği / okuduğu cevap
                $t->string('kaynak', 24)->default('');    // kural / niyet / haiku / ogrenilen / ekran / stt
                $t->string('aksiyon', 24)->default('');
                $t->text('sepet')->nullable();             // tur sonunda sepet ("2x Kola, 1x Margarita")
                $t->string('dil', 5)->default('tr');
                $t->timestamp('created_at')->useCurrent()->index();
            });
        }
        Cache::put('ai_kayit_tablo_ok1', 1, 3600);
    }

    // İnceleme linkinin anahtarı (bir kez üretilir, Sistem Yönetimi sayfasında görünür)
    function _aiKayitAnahtar()
    {
        $k = resto_ayar_al('ai_kayit_anahtar', null);
        if (!$k) { $k = bin2hex(random_bytes(16)); resto_ayar_yaz('ai_kayit_anahtar', $k); }
        return $k;
    }

    function _aiKayitListe(Request $r)
    {
        _aiKayitEnsure();
        $gun = max(1, min(30, (int) $r->query('gun', 1)));
        $q = DB::table('musteri_ai_kayit')->leftJoin('masalar', 'masalar.id', '=', 'musteri_ai_kayit.masa_id')
            ->leftJoin('subeler', 'subeler.id', '=', 'musteri_ai_kayit.sube_id')
            ->where('musteri_ai_kayit.created_at', '>=', now()->subDays($gun))
            ->select('musteri_ai_kayit.*', 'masalar.ad as masa', 'subeler.ad as sube');
        if ($r->filled('sube')) $q->where('musteri_ai_kayit.sube_id', (int) $r->query('sube'));
        if ($r->filled('oturum')) $q->where('musteri_ai_kayit.oturum', (string) $r->query('oturum'));
        $satirlar = $q->orderBy('musteri_ai_kayit.id')->limit(3000)->get();
        // Oturum (bir QR açılışı) bazında konuşmalar, en yenisi üstte
        $ot = [];
        foreach ($satirlar as $s) {
            $k = $s->oturum;
            if (!isset($ot[$k])) $ot[$k] = ['oturum' => $k, 'sube' => $s->sube, 'masa' => $s->masa, 'basladi' => (string) $s->created_at, 'turlar' => []];
            $ot[$k]['turlar'][] = ['saat' => substr((string) $s->created_at, 11, 8), 'kanal' => $s->kanal, 'soru' => $s->soru, 'cevap' => $s->cevap,
                'kaynak' => $s->kaynak, 'aksiyon' => $s->aksiyon, 'sepet' => $s->sepet, 'dil' => $s->dil];
        }
        return array_reverse(array_values($ot));
    }
}

// Müşteri ekranı her turu yazar (QR anahtarı şart; başka masaya/restorana kayıt atılamaz)
Route::post('/api/qr/ai-kayit', function (Request $r) {
    if ($_g = _qrKapiZorunlu($r)) return $_g;
    _aiKayitEnsure();
    $masa = DB::table('masalar')->find((int) $r->input('masa'));
    $kes = fn ($v, $n) => mb_substr(trim((string) $v), 0, $n);
    $oturum = preg_replace('/[^a-z0-9]/', '', strtolower((string) $r->input('oturum')));
    if ($oturum === '') return ['ok' => 0];
    try {
        DB::table('musteri_ai_kayit')->insert([
            'sube_id' => $masa->sube_id, 'masa_id' => $masa->id, 'oturum' => substr($oturum, 0, 32),
            'kanal' => $kes($r->input('kanal'), 8), 'soru' => $kes($r->input('soru'), 600), 'cevap' => $kes($r->input('cevap'), 1500),
            'kaynak' => $kes($r->input('kaynak'), 24), 'aksiyon' => $kes($r->input('aksiyon'), 24), 'sepet' => $kes($r->input('sepet'), 600),
            'dil' => $kes($r->input('dil', 'tr'), 5), 'created_at' => now(),
        ]);
        if (random_int(1, 200) === 1) DB::table('musteri_ai_kayit')->where('created_at', '<', now()->subDays(30))->delete();
    } catch (\Throwable $e) { return ['ok' => 0]; }
    return ['ok' => 1];
});

Route::get('/ai-kayitlar.json', function (Request $r) {
    $verilen = (string) $r->query('anahtar', '');
    $dogru = (function_exists('_ryAdmin') && _ryAdmin()) || ($verilen !== '' && hash_equals((string) resto_ayar_al('ai_kayit_anahtar', ''), $verilen));
    if (!$dogru) return response()->json(['ok' => 0, 'hata' => 'Yetkisiz'], 403);
    return response()->json(['ok' => 1, 'oturumlar' => _aiKayitListe($r)], 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

Route::get('/ai-kayitlar', function (Request $r) {
    if (!(function_exists('_ryAdmin') && _ryAdmin())) return redirect('/resteos-yonetim');
    return view('ai_kayitlar', [
        'oturumlar' => _aiKayitListe($r), 'gun' => max(1, min(30, (int) $r->query('gun', 1))),
        'link' => url('/ai-kayitlar.json') . '?anahtar=' . _aiKayitAnahtar(),
    ]);
});

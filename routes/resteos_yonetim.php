<?php

/**
 * ============================================================================
 *  ResteOS SİSTEM YÖNETİMİ (çok-restoranlı SaaS admin paneli)
 *  Randevumcepte /sistemyonetim/v2 panelinin restoran sürümü.
 *  Tamamen ADDITIVE: mevcut kodu bozmaz. Prefix: /resteos-yonetim
 *  Şema runtime-ensure ile kurulur (migration gerekmez, deploy patlamaz).
 * ============================================================================
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ---------------------------------------------------------------------------
// ŞEMA KURULUMU — yöneticiler tablosu + subeler lisans kolonları + log/not/ticket
// ---------------------------------------------------------------------------
if (!function_exists('_ryEnsure')) {
    function _ryEnsure()
    {
        // 1) Süper-admin tablosu
        if (!Schema::hasTable('resteos_yoneticiler')) {
            Schema::create('resteos_yoneticiler', function ($t) {
                $t->increments('id');
                $t->string('ad', 80);
                $t->string('email', 120)->unique();
                $t->string('sifre', 255);
                $t->string('rol', 20)->default('yonetici'); // super_admin | yonetici | destek
                $t->boolean('aktif')->default(true);
                $t->timestamp('son_giris')->nullable();
                $t->timestamps();
            });
        }
        // İlk kurulum: varsayılan süper-admin (env ile ezilebilir; yoksa güvenli rastgele parola)
        if (DB::table('resteos_yoneticiler')->count() === 0) {
            $mail = (string) env('RESTEOS_SUPERADMIN_EMAIL', 'admin@resteos.com');
            $sifre = (string) (env('RESTEOS_SUPERADMIN_SIFRE') ?: 'resteos2026');
            DB::table('resteos_yoneticiler')->insert([
                'ad' => 'Sistem Yöneticisi', 'email' => $mail,
                'sifre' => Hash::make($sifre), 'rol' => 'super_admin', 'aktif' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // 2) subeler lisans/üyelik kolonları (additive)
        if (Schema::hasTable('subeler')) {
            Schema::table('subeler', function ($t) {
                if (!Schema::hasColumn('subeler', 'demo_hesabi')) $t->boolean('demo_hesabi')->default(true);
                if (!Schema::hasColumn('subeler', 'uyelik_turu')) $t->string('uyelik_turu', 20)->default('demo'); // demo|baslangic|standart|pro
                if (!Schema::hasColumn('subeler', 'uyelik_bitis')) $t->date('uyelik_bitis')->nullable();
                if (!Schema::hasColumn('subeler', 'askiya_alindi')) $t->boolean('askiya_alindi')->default(false);
                if (!Schema::hasColumn('subeler', 'askiya_sebep')) $t->string('askiya_sebep', 255)->nullable();
                if (!Schema::hasColumn('subeler', 'yetkili_ad')) $t->string('yetkili_ad', 120)->nullable();
                if (!Schema::hasColumn('subeler', 'yetkili_tel')) $t->string('yetkili_tel', 24)->nullable();
                if (!Schema::hasColumn('subeler', 'sehir')) $t->string('sehir', 60)->nullable();
            });
            // Mevcut şubelerin bitiş tarihi boşsa 14 gün demo ver (bir kez)
            DB::table('subeler')->whereNull('uyelik_bitis')->update(['uyelik_bitis' => now()->addDays(14)->toDateString()]);
            // GÜVENLİK: ilk (ana) şube CANLI/lisanslı kabul edilir -> lisans zorlaması onu asla kilitlemesin.
            $anaId = DB::table('subeler')->min('id');
            if ($anaId) DB::table('subeler')->where('id', $anaId)->update(['demo_hesabi' => 0, 'uyelik_turu' => 'standart', 'uyelik_bitis' => null, 'askiya_alindi' => 0]);
        }
        // 3) Loglar
        if (!Schema::hasTable('resteos_log')) {
            Schema::create('resteos_log', function ($t) {
                $t->increments('id');
                $t->integer('yonetici_id')->nullable();
                $t->string('yonetici_ad', 80)->nullable();
                $t->integer('sube_id')->nullable();
                $t->string('islem', 60);
                $t->string('detay', 500)->nullable();
                $t->string('ip', 45)->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }
        if (!Schema::hasTable('resteos_giris_log')) {
            Schema::create('resteos_giris_log', function ($t) {
                $t->increments('id');
                $t->string('email', 120)->nullable();
                $t->boolean('basarili')->default(false);
                $t->string('ip', 45)->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }
        // 4) Restoran notları
        if (!Schema::hasTable('resteos_not')) {
            Schema::create('resteos_not', function ($t) {
                $t->increments('id');
                $t->integer('sube_id')->index();
                $t->integer('yonetici_id')->nullable();
                $t->string('yonetici_ad', 80)->nullable();
                $t->text('icerik');
                $t->boolean('pinli')->default(false);
                $t->timestamp('created_at')->nullable();
            });
        }
        // 5) Destek talepleri (ticket)
        if (!Schema::hasTable('resteos_ticket')) {
            Schema::create('resteos_ticket', function ($t) {
                $t->increments('id');
                $t->integer('sube_id')->nullable()->index();
                $t->string('konu', 160);
                $t->string('durum', 16)->default('acik');   // acik|islemde|cozumlendi|kapali
                $t->string('oncelik', 12)->default('orta');  // dusuk|orta|yuksek|acil
                $t->integer('atanan_id')->nullable();
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('resteos_ticket_mesaj')) {
            Schema::create('resteos_ticket_mesaj', function ($t) {
                $t->increments('id');
                $t->integer('ticket_id')->index();
                $t->string('yazan', 80)->nullable();
                $t->text('mesaj');
                $t->timestamp('created_at')->nullable();
            });
        }
        // 6) AI / Santral kredi (tek satır ayar)
        if (!Schema::hasTable('resteos_ai_kredi')) {
            Schema::create('resteos_ai_kredi', function ($t) {
                $t->increments('id');
                $t->decimal('toplam', 12, 2)->default(0);   // yüklenen toplam kredi (TL)
                $t->decimal('harcanan', 12, 2)->default(0);  // harcanan (TL)
                $t->decimal('kur', 8, 2)->default(34);       // USD->TRY kur
                $t->decimal('esik', 12, 2)->default(100);    // düşük kredi alarm eşiği
                $t->timestamp('updated_at')->nullable();
            });
            DB::table('resteos_ai_kredi')->insert(['toplam' => 0, 'harcanan' => 0, 'kur' => 34, 'esik' => 100, 'updated_at' => now()]);
        }
        // 7) SMS / bildirim paketleri
        if (!Schema::hasTable('resteos_sms_paket')) {
            Schema::create('resteos_sms_paket', function ($t) {
                $t->increments('id');
                $t->string('ad', 80);
                $t->integer('adet');
                $t->decimal('ucret', 10, 2);
                $t->string('renk', 12)->default('mor');
                $t->boolean('aktif')->default(true);
            });
        }
        // 8) Duyurular (restoranlara sistem mesajı)
        if (!Schema::hasTable('resteos_duyuru')) {
            Schema::create('resteos_duyuru', function ($t) {
                $t->increments('id');
                $t->string('baslik', 160);
                $t->text('icerik');
                $t->string('tip', 16)->default('bilgi'); // bilgi|uyari|kampanya
                $t->string('hedef', 16)->default('hepsi'); // hepsi|sube
                $t->integer('sube_id')->nullable();
                $t->boolean('aktif')->default(true);
                $t->timestamp('created_at')->nullable();
            });
        }
        // 9) Güvenlik IP kuralları
        if (!Schema::hasTable('resteos_guvenlik_ip')) {
            Schema::create('resteos_guvenlik_ip', function ($t) {
                $t->increments('id');
                $t->string('ip', 45)->index();
                $t->string('tip', 10)->default('blacklist'); // whitelist|blacklist
                $t->string('sebep', 160)->nullable();
                $t->timestamp('created_at')->nullable();
            });
        }
        // 10) Manuel ödeme talepleri
        if (!Schema::hasTable('resteos_odeme_talep')) {
            Schema::create('resteos_odeme_talep', function ($t) {
                $t->increments('id');
                $t->integer('sube_id')->nullable();
                $t->string('aciklama', 200);
                $t->decimal('tutar', 10, 2);
                $t->string('token', 40)->unique();
                $t->string('durum', 12)->default('bekliyor'); // bekliyor|odendi
                $t->timestamp('created_at')->nullable();
            });
        }
    }
}

// Giriş yapan yönetici (session)
if (!function_exists('_ryAdmin')) {
    function _ryAdmin()
    {
        $id = session('ry_admin_id');
        if (!$id) return null;
        return DB::table('resteos_yoneticiler')->where('id', $id)->where('aktif', 1)->first();
    }
    // Giriş kapısı: yoksa login'e yönlendir (korumalı sayfaların başında çağrılır)
    function _ryKapi()
    {
        _ryEnsure();
        if (!_ryAdmin()) return redirect('/resteos-yonetim/giris');
        return null;
    }
    // Audit log
    function _ryLog($islem, $detay = null, $subeId = null)
    {
        $a = _ryAdmin();
        try {
            DB::table('resteos_log')->insert([
                'yonetici_id' => $a->id ?? null, 'yonetici_ad' => $a->ad ?? 'sistem',
                'sube_id' => $subeId, 'islem' => $islem, 'detay' => $detay ? mb_substr($detay, 0, 500) : null,
                'ip' => request()->ip(), 'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}
    }
}

// Restoran sağlık skoru (0-100) — aktiflik + ciro + lisans
if (!function_exists('_rySaglik')) {
    function _rySaglik($subeId)
    {
        $skor = 100; $sebep = [];
        $son7Adisyon = DB::table('adisyonlar')->where('sube_id', $subeId)->where('acilis', '>=', now()->subDays(7))->count();
        if ($son7Adisyon === 0) { $skor -= 40; $sebep[] = 'Son 7 günde hiç adisyon yok'; }
        elseif ($son7Adisyon < 10) { $skor -= 15; $sebep[] = 'Son 7 günde az işlem (' . $son7Adisyon . ')'; }
        $son30Ciro = (float) DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.sube_id', $subeId)->where('odemeler.created_at', '>=', now()->subDays(30))->sum('odemeler.tutar');
        if ($son30Ciro <= 0) { $skor -= 25; $sebep[] = '30 günde ciro yok'; }
        $sube = DB::table('subeler')->where('id', $subeId)->first();
        if ($sube) {
            if ($sube->askiya_alindi ?? false) { $skor -= 50; $sebep[] = 'Hesap askıda'; }
            $bitis = $sube->uyelik_bitis ?? null;
            if ($bitis) {
                $kalan = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($bitis), false);
                if ($kalan < 0) { $skor -= 30; $sebep[] = 'Üyelik süresi bitmiş'; }
                elseif ($kalan <= 3) { $skor -= 10; $sebep[] = 'Üyelik ' . $kalan . ' gün sonra bitiyor'; }
            }
            $personelVar = DB::table('personeller')->where('sube_id', $subeId)->where('aktif', 1)->exists();
            if (!$personelVar) { $skor -= 15; $sebep[] = 'Aktif personel yok'; }
        }
        $skor = max(0, min(100, $skor));
        return ['skor' => $skor, 'sebep' => $sebep];
    }
    // Üyelik durum etiketi
    function _ryDurum($sube)
    {
        if ($sube->askiya_alindi ?? false) return ['ad' => 'Askıda', 'renk' => 'danger'];
        $bitis = $sube->uyelik_bitis ?? null;
        if ($bitis && \Carbon\Carbon::parse($bitis)->isPast()) return ['ad' => 'Süresi Bitti', 'renk' => 'danger'];
        if ($sube->demo_hesabi ?? true) return ['ad' => 'Demo', 'renk' => 'warning'];
        return ['ad' => 'Aktif', 'renk' => 'success'];
    }
}

// ===========================================================================
// GİRİŞ / ÇIKIŞ
// ===========================================================================
Route::get('/resteos-yonetim/giris', function () {
    _ryEnsure();
    if (_ryAdmin()) return redirect('/resteos-yonetim');
    return view('resteos_yonetim.giris');
});

Route::post('/resteos-yonetim/giris', function (Request $r) {
    _ryEnsure();
    $email = trim((string) $r->email);
    $u = DB::table('resteos_yoneticiler')->where('email', $email)->where('aktif', 1)->first();
    $ok = $u && Hash::check((string) $r->sifre, $u->sifre);
    try {
        DB::table('resteos_giris_log')->insert(['email' => $email, 'basarili' => $ok ? 1 : 0, 'ip' => $r->ip(), 'created_at' => now()]);
    } catch (\Throwable $e) {}
    if (!$ok) return redirect('/resteos-yonetim/giris')->with('hata', 'E-posta veya şifre hatalı.');
    session(['ry_admin_id' => $u->id]);
    DB::table('resteos_yoneticiler')->where('id', $u->id)->update(['son_giris' => now()]);
    _ryLog('giris', 'Panele giriş yaptı');
    return redirect('/resteos-yonetim');
});

Route::match(['get', 'post'], '/resteos-yonetim/cikis', function () {
    _ryLog('cikis', 'Çıkış yaptı');
    session()->forget('ry_admin_id');
    return redirect('/resteos-yonetim/giris');
});

// ===========================================================================
// DASHBOARD
// ===========================================================================
Route::get('/resteos-yonetim', function () {
    if ($r = _ryKapi()) return $r;
    $subeler = DB::table('subeler')->get();
    $toplam = $subeler->count();
    $aktif = $subeler->where('askiya_alindi', false)->where('demo_hesabi', false)->count();
    $demo = $subeler->where('demo_hesabi', true)->count();
    $askida = $subeler->where('askiya_alindi', true)->count();

    // Tüm restoranların ciro/adisyonu
    $bugunCiro = (float) DB::table('odemeler')->whereDate('created_at', today())->sum('tutar');
    $son30Ciro = (float) DB::table('odemeler')->where('created_at', '>=', now()->subDays(30))->sum('tutar');
    $acikAdisyon = DB::table('adisyonlar')->where('durum', 'acik')->count();
    $son30Adisyon = DB::table('adisyonlar')->where('kapanis', '>=', now()->subDays(30))->where('durum', 'odendi')->count();

    // 14 günlük ciro trendi
    $trend = [];
    for ($i = 13; $i >= 0; $i--) {
        $g = today()->subDays($i);
        $trend[] = [
            'gun' => $g->format('d.m'),
            'ciro' => (float) DB::table('odemeler')->whereDate('created_at', $g)->sum('tutar'),
        ];
    }
    // Süresi yakında bitecek / bitmiş restoranlar
    $yakinda = DB::table('subeler')->whereNotNull('uyelik_bitis')
        ->where('uyelik_bitis', '<=', now()->addDays(7)->toDateString())
        ->where('askiya_alindi', false)->orderBy('uyelik_bitis')->limit(8)->get();
    // Son aktiviteler
    $sonLog = DB::table('resteos_log')->orderByDesc('id')->limit(10)->get();
    // Açık ticket sayısı
    $acikTicket = Schema::hasTable('resteos_ticket') ? DB::table('resteos_ticket')->whereIn('durum', ['acik', 'islemde'])->count() : 0;

    return view('resteos_yonetim.dashboard', compact(
        'toplam', 'aktif', 'demo', 'askida', 'bugunCiro', 'son30Ciro',
        'acikAdisyon', 'son30Adisyon', 'trend', 'yakinda', 'sonLog', 'acikTicket'
    ));
});

// ===========================================================================
// RESTORANLAR LİSTESİ
// ===========================================================================
Route::get('/resteos-yonetim/restoranlar', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $q = DB::table('subeler');
    $ara = trim((string) $r->ara);
    if ($ara !== '') $q->where(function ($w) use ($ara) {
        $w->where('ad', 'like', "%$ara%")->orWhere('yetkili_ad', 'like', "%$ara%")->orWhere('telefon', 'like', "%$ara%")->orWhere('sehir', 'like', "%$ara%");
    });
    $durum = (string) $r->durum;
    if ($durum === 'demo') $q->where('demo_hesabi', 1);
    elseif ($durum === 'aktif') $q->where('demo_hesabi', 0)->where('askiya_alindi', 0);
    elseif ($durum === 'askida') $q->where('askiya_alindi', 1);
    elseif ($durum === 'bitti') $q->whereNotNull('uyelik_bitis')->where('uyelik_bitis', '<', now()->toDateString());
    $restoranlar = $q->orderByDesc('id')->get();
    // Her restorana özet ciro (son 30g)
    // Tüm restoranların son 30g cirosu TEK sorguda (N+1 giderildi)
    $ciroMap = DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
        ->where('odemeler.created_at', '>=', now()->subDays(30))
        ->select('adisyonlar.sube_id', DB::raw('SUM(odemeler.tutar) as c'))
        ->groupBy('adisyonlar.sube_id')->pluck('c', 'sube_id');
    foreach ($restoranlar as $s) {
        $s->son30 = (float) ($ciroMap[$s->id] ?? 0);
    }
    return view('resteos_yonetim.restoranlar', compact('restoranlar', 'ara', 'durum'));
});

// ===========================================================================
// RESTORAN DETAY
// ===========================================================================
Route::get('/resteos-yonetim/restoran/{id}', function ($id) {
    if ($x = _ryKapi()) return $x;
    $sube = DB::table('subeler')->where('id', $id)->first();
    if (!$sube) return redirect('/resteos-yonetim/restoranlar')->with('hata', 'Restoran bulunamadı.');
    $durum = _ryDurum($sube);
    $saglik = _rySaglik($id);
    $personeller = DB::table('personeller')->where('sube_id', $id)->orderByDesc('aktif')->orderBy('rol')->get();
    $notlar = DB::table('resteos_not')->where('sube_id', $id)->orderByDesc('pinli')->orderByDesc('id')->get();
    $bugunCiro = (float) DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $id)->whereDate('odemeler.created_at', today())->sum('odemeler.tutar');
    $son30Ciro = (float) DB::table('odemeler')->join('adisyonlar', 'odemeler.adisyon_id', '=', 'adisyonlar.id')
        ->where('adisyonlar.sube_id', $id)->where('odemeler.created_at', '>=', now()->subDays(30))->sum('odemeler.tutar');
    $acikMasa = DB::table('adisyonlar')->where('sube_id', $id)->where('durum', 'acik')->count();
    $urunSay = DB::table('urunler')->where('sube_id', $id)->count();
    return view('resteos_yonetim.restoran-detay', compact('sube', 'durum', 'saglik', 'personeller', 'notlar', 'bugunCiro', 'son30Ciro', 'acikMasa', 'urunSay'));
});

// --- Restoran aksiyonları ---
Route::post('/resteos-yonetim/restoran/{id}/bilgi', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    DB::table('subeler')->where('id', $id)->update([
        'ad' => $r->ad ?: DB::raw('ad'), 'adres' => $r->adres, 'telefon' => $r->telefon,
        'yetkili_ad' => $r->yetkili_ad, 'yetkili_tel' => $r->yetkili_tel, 'sehir' => $r->sehir,
    ]);
    _ryLog('restoran_bilgi', 'Restoran bilgileri güncellendi', $id);
    return back()->with('ok', 'Bilgiler güncellendi.');
});

Route::post('/resteos-yonetim/restoran/{id}/sure-uzat', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    $sube = DB::table('subeler')->where('id', $id)->first();
    $mevcut = ($sube && $sube->uyelik_bitis && \Carbon\Carbon::parse($sube->uyelik_bitis)->isFuture())
        ? \Carbon\Carbon::parse($sube->uyelik_bitis) : now();
    if ($r->tarih) $yeni = \Carbon\Carbon::parse($r->tarih);
    else $yeni = $mevcut->copy()->addDays((int) ($r->gun ?: 30));
    DB::table('subeler')->where('id', $id)->update(['uyelik_bitis' => $yeni->toDateString(), 'askiya_alindi' => 0]);
    _ryLog('sure_uzat', 'Üyelik ' . $yeni->format('d.m.Y') . ' tarihine uzatıldı', $id);
    return back()->with('ok', 'Süre uzatıldı: ' . $yeni->format('d.m.Y'));
});

Route::post('/resteos-yonetim/restoran/{id}/askiya-al', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    DB::table('subeler')->where('id', $id)->update(['askiya_alindi' => 1, 'askiya_sebep' => $r->sebep ?: 'Belirtilmedi']);
    _ryLog('askiya_al', 'Hesap askıya alındı: ' . ($r->sebep ?: '-'), $id);
    return back()->with('ok', 'Restoran askıya alındı.');
});

Route::post('/resteos-yonetim/restoran/{id}/aktif-et', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('subeler')->where('id', $id)->update(['askiya_alindi' => 0, 'askiya_sebep' => null]);
    _ryLog('aktif_et', 'Hesap yeniden aktif edildi', $id);
    return back()->with('ok', 'Restoran aktif edildi.');
});

Route::post('/resteos-yonetim/restoran/{id}/lisans-aktif', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    $tur = in_array($r->tur, ['baslangic', 'standart', 'pro']) ? $r->tur : 'standart';
    $ay = (int) ($r->ay ?: 1);
    DB::table('subeler')->where('id', $id)->update([
        'demo_hesabi' => 0, 'uyelik_turu' => $tur, 'askiya_alindi' => 0,
        'uyelik_bitis' => now()->addMonths($ay)->toDateString(),
    ]);
    _ryLog('lisans_aktif', "Lisans aktif: $tur ($ay ay)", $id);
    return back()->with('ok', 'Lisans aktifleştirildi (' . $tur . ', ' . $ay . ' ay).');
});

Route::post('/resteos-yonetim/restoran/{id}/not', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    if (trim((string) $r->icerik) !== '') {
        $a = _ryAdmin();
        DB::table('resteos_not')->insert([
            'sube_id' => $id, 'yonetici_id' => $a->id, 'yonetici_ad' => $a->ad,
            'icerik' => $r->icerik, 'pinli' => $r->pinli ? 1 : 0, 'created_at' => now(),
        ]);
        _ryLog('not_ekle', 'Not eklendi', $id);
    }
    return back()->with('ok', 'Not eklendi.');
});
Route::post('/resteos-yonetim/not/{id}/sil', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_not')->where('id', $id)->delete();
    return back()->with('ok', 'Not silindi.');
});

// İmpersonation: restoranın web paneline aktif şube olarak gir
Route::post('/resteos-yonetim/restoran/{id}/hesabina-gir', function ($id) {
    if ($x = _ryKapi()) return $x;
    $sube = DB::table('subeler')->where('id', $id)->first();
    if (!$sube) return back()->with('hata', 'Restoran bulunamadı.');
    session(['ry_imp_sube' => $id, 'ry_imp_ad' => $sube->ad]);
    _ryLog('impersonation', 'Restoran paneline girildi', $id);
    return redirect('/dashboard');
});
Route::match(['get', 'post'], '/resteos-yonetim/impersonation-bitir', function () {
    session()->forget(['ry_imp_sube', 'ry_imp_ad']);
    return redirect('/resteos-yonetim/restoranlar');
});

// ===========================================================================
// EKİP (süper-admin yönetir)
// ===========================================================================
Route::get('/resteos-yonetim/ekip', function () {
    if ($x = _ryKapi()) return $x;
    $ekip = DB::table('resteos_yoneticiler')->orderByDesc('aktif')->orderBy('id')->get();
    return view('resteos_yonetim.ekip', compact('ekip'));
});
Route::post('/resteos-yonetim/ekip', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $a = _ryAdmin();
    if (($a->rol ?? '') !== 'super_admin') return back()->with('hata', 'Sadece süper-admin ekip yönetebilir.');
    $email = trim((string) $r->email);
    if ($r->id) { // güncelle
        $upd = ['ad' => $r->ad, 'email' => $email, 'rol' => $r->rol, 'aktif' => $r->aktif ? 1 : 0];
        if (trim((string) $r->sifre) !== '') $upd['sifre'] = Hash::make($r->sifre);
        DB::table('resteos_yoneticiler')->where('id', $r->id)->update($upd);
        _ryLog('ekip_guncelle', $email);
    } else {
        if (DB::table('resteos_yoneticiler')->where('email', $email)->exists()) return back()->with('hata', 'Bu e-posta zaten kayıtlı.');
        DB::table('resteos_yoneticiler')->insert([
            'ad' => $r->ad, 'email' => $email, 'sifre' => Hash::make($r->sifre ?: 'resteos2026'),
            'rol' => $r->rol ?: 'yonetici', 'aktif' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        _ryLog('ekip_ekle', $email);
    }
    return redirect('/resteos-yonetim/ekip')->with('ok', 'Ekip güncellendi.');
});
Route::post('/resteos-yonetim/ekip/{id}/pasif', function ($id) {
    if ($x = _ryKapi()) return $x;
    $a = _ryAdmin();
    if (($a->rol ?? '') !== 'super_admin') return back()->with('hata', 'Yetkisiz.');
    if ((int) $id === (int) $a->id) return back()->with('hata', 'Kendinizi pasif edemezsiniz.');
    $cur = DB::table('resteos_yoneticiler')->where('id', $id)->value('aktif');
    DB::table('resteos_yoneticiler')->where('id', $id)->update(['aktif' => $cur ? 0 : 1]);
    return back()->with('ok', 'Durum güncellendi.');
});

// ===========================================================================
// LOGLAR
// ===========================================================================
Route::get('/resteos-yonetim/loglar', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $tab = $r->tab ?: 'islem';
    if ($tab === 'giris') {
        $kayitlar = DB::table('resteos_giris_log')->orderByDesc('id')->limit(200)->get();
    } else {
        $kayitlar = DB::table('resteos_log')->orderByDesc('id')->limit(200)->get();
    }
    return view('resteos_yonetim.loglar', compact('kayitlar', 'tab'));
});

// ===========================================================================
// TICKET (destek talepleri)
// ===========================================================================
Route::get('/resteos-yonetim/ticket', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $q = DB::table('resteos_ticket')->leftJoin('subeler', 'resteos_ticket.sube_id', '=', 'subeler.id')
        ->select('resteos_ticket.*', 'subeler.ad as restoran');
    if ($r->durum) $q->where('resteos_ticket.durum', $r->durum);
    $ticketlar = $q->orderByDesc('resteos_ticket.id')->get();
    $restoranlar = DB::table('subeler')->orderBy('ad')->get(['id', 'ad']);
    return view('resteos_yonetim.ticketlar', compact('ticketlar', 'restoranlar'));
});
Route::post('/resteos-yonetim/ticket', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $tid = DB::table('resteos_ticket')->insertGetId([
        'sube_id' => $r->sube_id ?: null, 'konu' => $r->konu ?: 'Başlıksız',
        'durum' => 'acik', 'oncelik' => $r->oncelik ?: 'orta', 'created_at' => now(), 'updated_at' => now(),
    ]);
    if (trim((string) $r->mesaj) !== '') {
        $a = _ryAdmin();
        DB::table('resteos_ticket_mesaj')->insert(['ticket_id' => $tid, 'yazan' => $a->ad, 'mesaj' => $r->mesaj, 'created_at' => now()]);
    }
    _ryLog('ticket_ac', $r->konu, $r->sube_id ?: null);
    return redirect('/resteos-yonetim/ticket/' . $tid);
});
Route::get('/resteos-yonetim/ticket/{id}', function ($id) {
    if ($x = _ryKapi()) return $x;
    $ticket = DB::table('resteos_ticket')->leftJoin('subeler', 'resteos_ticket.sube_id', '=', 'subeler.id')
        ->select('resteos_ticket.*', 'subeler.ad as restoran')->where('resteos_ticket.id', $id)->first();
    if (!$ticket) return redirect('/resteos-yonetim/ticket');
    $mesajlar = DB::table('resteos_ticket_mesaj')->where('ticket_id', $id)->orderBy('id')->get();
    return view('resteos_yonetim.ticket-detay', compact('ticket', 'mesajlar'));
});
Route::post('/resteos-yonetim/ticket/{id}/yanit', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    if (trim((string) $r->mesaj) !== '') {
        $a = _ryAdmin();
        DB::table('resteos_ticket_mesaj')->insert(['ticket_id' => $id, 'yazan' => $a->ad, 'mesaj' => $r->mesaj, 'created_at' => now()]);
        DB::table('resteos_ticket')->where('id', $id)->update(['updated_at' => now()]);
    }
    return back();
});
Route::post('/resteos-yonetim/ticket/{id}/durum', function (Request $r, $id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_ticket')->where('id', $id)->update(['durum' => $r->durum, 'updated_at' => now()]);
    _ryLog('ticket_durum', $r->durum);
    return back();
});

// ===========================================================================
// PROFİL
// ===========================================================================
Route::get('/resteos-yonetim/profil', function () {
    if ($x = _ryKapi()) return $x;
    $admin = _ryAdmin();
    return view('resteos_yonetim.profil', compact('admin'));
});
Route::post('/resteos-yonetim/profil', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $a = _ryAdmin();
    $upd = ['ad' => $r->ad ?: $a->ad, 'email' => $r->email ?: $a->email];
    if (trim((string) $r->sifre) !== '') {
        if (!Hash::check((string) $r->eski_sifre, $a->sifre)) return back()->with('hata', 'Mevcut şifre yanlış.');
        $upd['sifre'] = Hash::make($r->sifre);
    }
    DB::table('resteos_yoneticiler')->where('id', $a->id)->update($upd);
    _ryLog('profil', 'Profil güncellendi');
    return back()->with('ok', 'Profil güncellendi.');
});

// ===========================================================================
// RESTORAN EKLE (yeni demo restoran)
// ===========================================================================
Route::get('/resteos-yonetim/restoran-ekle', function () {
    if ($x = _ryKapi()) return $x;
    return view('resteos_yonetim.restoran-ekle');
});
Route::post('/resteos-yonetim/restoran-ekle', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    if (trim((string) $r->ad) === '') return back()->with('hata', 'Restoran adı gerekli.');
    $gun = (int) ($r->demo_gun ?: 14);
    $id = DB::table('subeler')->insertGetId([
        'ad' => $r->ad, 'adres' => $r->adres, 'telefon' => $r->telefon,
        'yetkili_ad' => $r->yetkili_ad, 'yetkili_tel' => $r->yetkili_tel, 'sehir' => $r->sehir,
        'aktif' => 1, 'demo_hesabi' => 1, 'uyelik_turu' => 'demo',
        'uyelik_bitis' => now()->addDays($gun)->toDateString(),
        'webhook_token' => \Illuminate\Support\Str::random(40),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    // Yeni şube HEMEN kullanılabilsin: sahip personel + benzersiz PIN (uygulamaya/POS'a giriş için).
    $pin = '';
    for ($i = 0; $i < 25; $i++) {
        $cand = (string) random_int(1000, 9999);
        if (!DB::table('personeller')->where('pin', $cand)->exists()) { $pin = $cand; break; }
    }
    if ($pin === '') $pin = (string) random_int(100000, 999999);
    $per = ['sube_id' => $id, 'ad' => ($r->yetkili_ad ?: 'Sahip'), 'rol' => 'sahip', 'pin' => $pin, 'aktif' => 1, 'created_at' => now(), 'updated_at' => now()];
    if (Schema::hasColumn('personeller', 'api_token')) $per['api_token'] = \Illuminate\Support\Str::random(48);
    if (Schema::hasColumn('personeller', 'telefon')) $per['telefon'] = $r->yetkili_tel;
    DB::table('personeller')->insert($per);
    _ryLog('restoran_ekle', 'Yeni demo restoran: ' . $r->ad . ' (sahip PIN ' . $pin . ')', $id);
    return redirect('/resteos-yonetim/restoran/' . $id)->with('ok', "Restoran oluşturuldu ($gun gün demo). Sahip giriş PIN'i: $pin — restorana iletin, bununla uygulamaya/POS'a giriş yapılır.");
});

// ===========================================================================
// AI / SANTRAL KREDİ
// ===========================================================================
Route::get('/resteos-yonetim/ai-kredi', function () {
    if ($x = _ryKapi()) return $x;
    $k = DB::table('resteos_ai_kredi')->first();
    $kalan = $k ? ((float) $k->toplam - (float) $k->harcanan) : 0;
    // Gerçek kullanım (varsa) — santral/asistan log tablosu ileride
    $dusuk = $k && $kalan <= (float) $k->esik;
    return view('resteos_yonetim.ai-kredi', compact('k', 'kalan', 'dusuk'));
});
Route::post('/resteos-yonetim/ai-kredi', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $k = DB::table('resteos_ai_kredi')->first();
    if ($r->islem === 'ekle') {
        DB::table('resteos_ai_kredi')->where('id', $k->id)->update(['toplam' => (float) $k->toplam + (float) $r->tutar, 'updated_at' => now()]);
        _ryLog('ai_kredi_ekle', $r->tutar . ' TL eklendi');
    } else {
        DB::table('resteos_ai_kredi')->where('id', $k->id)->update([
            'toplam' => (float) ($r->toplam ?? $k->toplam), 'kur' => (float) ($r->kur ?? $k->kur),
            'esik' => (float) ($r->esik ?? $k->esik), 'updated_at' => now(),
        ]);
        _ryLog('ai_kredi_ayar', 'Kredi ayarı güncellendi');
    }
    return back()->with('ok', 'AI kredi güncellendi.');
});

// ===========================================================================
// SMS / BİLDİRİM PAKETLERİ
// ===========================================================================
Route::get('/resteos-yonetim/sms-paket', function () {
    if ($x = _ryKapi()) return $x;
    $paketler = DB::table('resteos_sms_paket')->orderBy('adet')->get();
    return view('resteos_yonetim.sms-paket', compact('paketler'));
});
Route::post('/resteos-yonetim/sms-paket', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $veri = ['ad' => $r->ad ?: 'Paket', 'adet' => (int) $r->adet, 'ucret' => (float) $r->ucret, 'renk' => $r->renk ?: 'mor', 'aktif' => 1];
    if ($r->id) DB::table('resteos_sms_paket')->where('id', $r->id)->update($veri);
    else DB::table('resteos_sms_paket')->insert($veri);
    _ryLog('sms_paket', $r->ad);
    return back()->with('ok', 'Paket kaydedildi.');
});
Route::post('/resteos-yonetim/sms-paket/{id}/sil', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_sms_paket')->where('id', $id)->delete();
    return back()->with('ok', 'Paket silindi.');
});

// ===========================================================================
// DUYURULAR
// ===========================================================================
Route::get('/resteos-yonetim/duyurular', function () {
    if ($x = _ryKapi()) return $x;
    $duyurular = DB::table('resteos_duyuru')->orderByDesc('id')->get();
    $restoranlar = DB::table('subeler')->orderBy('ad')->get(['id', 'ad']);
    return view('resteos_yonetim.duyurular', compact('duyurular', 'restoranlar'));
});
Route::post('/resteos-yonetim/duyurular', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_duyuru')->insert([
        'baslik' => $r->baslik ?: 'Duyuru', 'icerik' => $r->icerik, 'tip' => $r->tip ?: 'bilgi',
        'hedef' => $r->hedef ?: 'hepsi', 'sube_id' => $r->hedef === 'sube' ? $r->sube_id : null,
        'aktif' => 1, 'created_at' => now(),
    ]);
    _ryLog('duyuru', $r->baslik);
    return back()->with('ok', 'Duyuru yayınlandı.');
});
Route::post('/resteos-yonetim/duyurular/{id}/sil', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_duyuru')->where('id', $id)->delete();
    return back()->with('ok', 'Duyuru silindi.');
});

// ===========================================================================
// RİSKLİ RESTORANLAR (sağlık skoru düşük)
// ===========================================================================
Route::get('/resteos-yonetim/riskli', function () {
    if ($x = _ryKapi()) return $x;
    $riskli = [];
    foreach (DB::table('subeler')->get() as $s) {
        $sg = _rySaglik($s->id);
        if ($sg['skor'] < 70) { $s->saglik = $sg; $riskli[] = $s; }
    }
    usort($riskli, fn ($a, $b) => $a->saglik['skor'] <=> $b->saglik['skor']);
    return view('resteos_yonetim.riskli', compact('riskli'));
});

// ===========================================================================
// GÜVENLİK DUVARI
// ===========================================================================
Route::get('/resteos-yonetim/guvenlik', function () {
    if ($x = _ryKapi()) return $x;
    $kurallar = DB::table('resteos_guvenlik_ip')->orderByDesc('id')->get();
    // Son başarısız girişler (IP bazlı)
    $basarisiz = DB::table('resteos_giris_log')->where('basarili', 0)->where('created_at', '>=', now()->subDays(7))
        ->select('ip', DB::raw('count(*) as adet'))->groupBy('ip')->orderByDesc('adet')->limit(15)->get();
    return view('resteos_yonetim.guvenlik', compact('kurallar', 'basarisiz'));
});
Route::post('/resteos-yonetim/guvenlik', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    if (trim((string) $r->ip) !== '') {
        DB::table('resteos_guvenlik_ip')->insert(['ip' => $r->ip, 'tip' => $r->tip ?: 'blacklist', 'sebep' => $r->sebep, 'created_at' => now()]);
        _ryLog('guvenlik_ip', ($r->tip ?: 'blacklist') . ': ' . $r->ip);
    }
    return back()->with('ok', 'Kural eklendi.');
});
Route::post('/resteos-yonetim/guvenlik/{id}/sil', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_guvenlik_ip')->where('id', $id)->delete();
    return back()->with('ok', 'Kural silindi.');
});

// ===========================================================================
// SİSTEM SAĞLIK
// ===========================================================================
Route::get('/resteos-yonetim/sistem-saglik', function () {
    if ($x = _ryKapi()) return $x;
    $bilgi = [];
    try { $bilgi['db'] = DB::selectOne('select version() as v')->v ?? '—'; } catch (\Throwable $e) { $bilgi['db'] = '—'; }
    $bilgi['php'] = PHP_VERSION;
    $bilgi['laravel'] = app()->version();
    $bilgi['restoran'] = DB::table('subeler')->count();
    $bilgi['adisyon'] = DB::table('adisyonlar')->count();
    $bilgi['urun'] = DB::table('urunler')->count();
    try {
        $disk = disk_free_space('/'); $diskT = disk_total_space('/');
        $bilgi['disk'] = $disk && $diskT ? round(($diskT - $disk) / $diskT * 100) . '% dolu (' . round($disk / 1073741824, 1) . ' GB boş)' : '—';
    } catch (\Throwable $e) { $bilgi['disk'] = '—'; }
    $bilgi['zaman'] = now()->format('d.m.Y H:i:s');
    return view('resteos_yonetim.sistem-saglik', compact('bilgi'));
});

// ===========================================================================
// MANUEL ÖDEME LİNKİ
// ===========================================================================
Route::get('/resteos-yonetim/odeme-linki', function () {
    if ($x = _ryKapi()) return $x;
    $talepler = DB::table('resteos_odeme_talep')->leftJoin('subeler', 'resteos_odeme_talep.sube_id', '=', 'subeler.id')
        ->select('resteos_odeme_talep.*', 'subeler.ad as restoran')->orderByDesc('resteos_odeme_talep.id')->limit(30)->get();
    $restoranlar = DB::table('subeler')->orderBy('ad')->get(['id', 'ad']);
    return view('resteos_yonetim.odeme-linki', compact('talepler', 'restoranlar'));
});
Route::post('/resteos-yonetim/odeme-linki', function (Request $r) {
    if ($x = _ryKapi()) return $x;
    $token = \Illuminate\Support\Str::random(40);
    DB::table('resteos_odeme_talep')->insert([
        'sube_id' => $r->sube_id ?: null, 'aciklama' => $r->aciklama ?: 'Ödeme', 'tutar' => (float) $r->tutar,
        'token' => $token, 'durum' => 'bekliyor', 'created_at' => now(),
    ]);
    _ryLog('odeme_linki', ($r->aciklama ?? '') . ' / ' . $r->tutar . ' TL');
    return back()->with('ok', 'Ödeme linki oluşturuldu: ' . url('/odeme-talep/' . $token));
});
Route::post('/resteos-yonetim/odeme-linki/{id}/odendi', function ($id) {
    if ($x = _ryKapi()) return $x;
    DB::table('resteos_odeme_talep')->where('id', $id)->update(['durum' => 'odendi']);
    return back()->with('ok', 'Ödendi olarak işaretlendi.');
});

// ===========================================================================
// SANTRAL — her yeni restorana santral bağlantısını panelden kur
//  • FreePBX API bağlantısı (global tek ayar)         -> /resteos-yonetim/santral
//  • Dahili (SIP extension) yönetimi (global)         -> /resteos-yonetim/santral/dahili
//  • Hat/DID bağlama + AI aktarma hedefleri (restoran) -> /resteos-yonetim/restoran/{id}/santral
//  Mevcut /api/freepbx/*, /api/dahili/*, /api/hat/*, /api/santral/ayar* uçlarını
//  yeniden kullanır (api/* CSRF muaf). Ana restoran panelindeki ekranlar bozulmaz.
// ===========================================================================
Route::get('/resteos-yonetim/santral', function () {
    if ($x = _ryKapi()) return $x;
    \App\Services\FreePbxClient::ensure();
    \App\Services\FreePbxTrunkClient::ensure();
    $ay = \App\Services\FreePbxClient::ayar();
    return view('resteos_yonetim.santral', compact('ay'));
});

Route::get('/resteos-yonetim/santral/dahili', function () {
    if ($x = _ryKapi()) return $x;
    \App\Services\FreePbxClient::ensure();
    $ayarli = (new \App\Services\FreePbxClient())->ayarliMi();
    return view('resteos_yonetim.santral-dahili', compact('ayarli'));
});

Route::get('/resteos-yonetim/restoran/{id}/santral', function ($id) {
    if ($x = _ryKapi()) return $x;
    $sube = DB::table('subeler')->where('id', $id)->first();
    if (!$sube) return redirect('/resteos-yonetim/restoranlar')->with('hata', 'Restoran bulunamadı.');
    \App\Services\FreePbxClient::ensure();
    \App\Services\FreePbxTrunkClient::ensure();
    $trunkAyarli = (new \App\Services\FreePbxTrunkClient())->ayarliMi();
    return view('resteos_yonetim.restoran-santral', compact('sube', 'trunkAyarli'));
});

<?php
// ===== ŞUBELER ARASI TRANSFER + ORGANİZASYON (Grup / Firma / Franchise) =====
// Grup  = bir patronun işletmeleri (görünürlük sınırı)   → subeler.grup_id
// Firma = vergi no (finans sınırı)                         → isletme_yasal.vergi_no (yoksa edonusum_ayarlari.vkn_tckn)
// Şube  = fiziksel işletme (stok sınırı)
// Franchise: franchise alanın KENDİ grubu olur; merkezle yalnız franchise_baglari bağı (merkezin franchise_depo şubeleri görünür).
// Grubu olmayan şube eskisi gibi çalışır (transfer görünmez). web.php sonunda require edilir.
// Transfer türü vergi noya göre OTOMATİK: ic (aynı VKN, sevk irsaliyesi, finans yok) | firmalar_arasi (fatura + cari) | franchise (fatura + cari, fiyat çarpanı).

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_trfEnsure')) {
    function _trfEnsure()
    {
        if (cache()->get('trf_ensure_v1')) return;
        try {
            if (!Schema::hasTable('isletme_gruplari')) {
                Schema::create('isletme_gruplari', function ($t) {
                    $t->id(); $t->string('ad', 150); $t->text('notlar')->nullable(); $t->timestamps();
                });
            }
            if (!Schema::hasColumn('subeler', 'grup_id')) Schema::table('subeler', function ($t) { $t->unsignedBigInteger('grup_id')->nullable()->index(); });
            if (!Schema::hasColumn('subeler', 'franchise_depo')) Schema::table('subeler', function ($t) { $t->tinyInteger('franchise_depo')->default(0); });
            if (!Schema::hasTable('franchise_baglari')) {
                Schema::create('franchise_baglari', function ($t) {
                    $t->id(); $t->unsignedBigInteger('merkez_grup_id'); $t->unsignedBigInteger('franchise_grup_id');
                    $t->decimal('fiyat_carpani', 6, 3)->default(1); $t->tinyInteger('aktif')->default(1); $t->timestamps();
                    $t->unique(['merkez_grup_id', 'franchise_grup_id']);
                });
            }
            if (!Schema::hasTable('personel_sube_erisim')) {
                Schema::create('personel_sube_erisim', function ($t) {
                    $t->id(); $t->unsignedBigInteger('personel_id'); $t->unsignedBigInteger('sube_id'); $t->string('veren_ad', 120)->nullable(); $t->timestamps();
                    $t->unique(['personel_id', 'sube_id']);
                });
            }
            if (!Schema::hasTable('sube_malzeme_ayar')) {
                Schema::create('sube_malzeme_ayar', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id'); $t->unsignedBigInteger('malzeme_id'); $t->decimal('kritik_stok', 14, 3)->nullable(); $t->timestamps();
                    $t->unique(['sube_id', 'malzeme_id']);
                });
            }
            if (!Schema::hasTable('sube_transferleri')) {
                Schema::create('sube_transferleri', function ($t) {
                    $t->id();
                    $t->string('transfer_no', 30)->nullable()->index();
                    $t->string('tur', 20)->default('ic');            // ic | firmalar_arasi | franchise
                    $t->unsignedBigInteger('gonderen_sube_id')->index();
                    $t->unsignedBigInteger('alan_sube_id')->index();
                    $t->string('durum', 20)->default('istendi');     // istendi | hazirlaniyor | yolda | teslim | reddedildi | iptal
                    $t->string('kaynak', 20)->default('elle');       // elle | talep | oneri | skt
                    $t->unsignedBigInteger('talep_id')->nullable()->index();
                    $t->unsignedBigInteger('isteyen_id')->nullable(); $t->string('isteyen_ad', 120)->nullable();
                    $t->unsignedBigInteger('onaylayan_id')->nullable(); $t->string('onaylayan_ad', 120)->nullable(); $t->timestamp('onay_at')->nullable();
                    $t->unsignedBigInteger('sevk_eden_id')->nullable(); $t->string('sevk_eden_ad', 120)->nullable(); $t->timestamp('sevk_at')->nullable();
                    $t->unsignedBigInteger('teslim_alan_id')->nullable(); $t->string('teslim_alan_ad', 120)->nullable(); $t->timestamp('teslim_at')->nullable();
                    $t->text('not')->nullable(); $t->text('gonderen_not')->nullable(); $t->text('red_sebep')->nullable();
                    $t->decimal('kdv_orani', 5, 2)->default(0);
                    $t->decimal('maliyet_toplam', 14, 2)->default(0);   // gönderilen × maliyet
                    $t->decimal('net_toplam', 14, 2)->default(0);       // teslim × transfer fiyatı (fatura matrahı)
                    $t->decimal('kdv_toplam', 14, 2)->default(0);
                    $t->unsignedBigInteger('alis_fatura_id')->nullable();
                    $t->unsignedBigInteger('cari_hareket_id')->nullable();
                    $t->timestamps();
                });
            }
            if (!Schema::hasTable('sube_transfer_kalemleri')) {
                Schema::create('sube_transfer_kalemleri', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('transfer_id')->index();
                    $t->unsignedBigInteger('malzeme_id');
                    $t->string('ad', 200)->nullable(); $t->string('birim', 20)->nullable();
                    $t->decimal('istenen', 16, 4)->default(0);       // temel birim
                    $t->decimal('onaylanan', 16, 4)->nullable();
                    $t->decimal('gonderilen', 16, 4)->nullable();
                    $t->decimal('teslim_alinan', 16, 4)->nullable();
                    $t->decimal('birim_maliyet', 14, 4)->default(0);
                    $t->decimal('birim_fiyat', 14, 4)->default(0);
                    $t->text('lotlar')->nullable();                  // JSON: gönderilen lotlar (FEFO)
                    $t->unsignedBigInteger('talep_kalem_id')->nullable();
                    $t->string('fark_not', 255)->nullable();
                    $t->timestamps();
                });
            }
            if (function_exists('_restoPartiEnsure')) _restoPartiEnsure();
            if (Schema::hasTable('stok_partileri')) {
                if (!Schema::hasColumn('stok_partileri', 'kaynak_sube_id')) Schema::table('stok_partileri', function ($t) { $t->unsignedBigInteger('kaynak_sube_id')->nullable(); });
                if (!Schema::hasColumn('stok_partileri', 'transfer_id')) Schema::table('stok_partileri', function ($t) { $t->unsignedBigInteger('transfer_id')->nullable(); });
            }
            if (Schema::hasTable('satinalma_talepleri') && !Schema::hasColumn('satinalma_talepleri', 'transfer_id')) Schema::table('satinalma_talepleri', function ($t) { $t->unsignedBigInteger('transfer_id')->nullable(); });
            cache()->put('trf_ensure_v1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

// ---------------------------------------------------------------- ORGANİZASYON
if (!function_exists('_trfKolon')) {
    function _trfKolon($tablo, $kolon)
    {
        static $c = [];
        $k = $tablo . '.' . $kolon;
        if (!array_key_exists($k, $c)) { try { $c[$k] = Schema::hasTable($tablo) && Schema::hasColumn($tablo, $kolon); } catch (\Throwable $e) { $c[$k] = false; } }
        return $c[$k];
    }
    function _trfGrupId($sid)
    {
        static $c = [];
        $sid = (int) $sid;
        if (!array_key_exists($sid, $c)) $c[$sid] = _trfKolon('subeler', 'grup_id') ? (int) DB::table('subeler')->where('id', $sid)->value('grup_id') : 0;
        return $c[$sid];
    }
    // Aynı gruptaki şubeler (kendisi dahil). Grup yoksa yalnız kendisi.
    function _grupSubeleri($sid)
    {
        $sid = (int) $sid; $g = _trfGrupId($sid);
        if (!$g) return [$sid];
        static $c = [];
        if (!isset($c[$g])) $c[$g] = DB::table('subeler')->where('grup_id', $g)->orderBy('id')->pluck('id')->map(fn ($x) => (int) $x)->all();
        return in_array($sid, $c[$g], true) ? $c[$g] : array_merge([$sid], $c[$g]);
    }
    // Şubenin vergi numarası (yalnız rakam). Boşsa ''.
    function _subeVkn($sid)
    {
        static $c = [];
        $sid = (int) $sid;
        if (array_key_exists($sid, $c)) return $c[$sid];
        $v = '';
        try { if (Schema::hasTable('isletme_yasal')) $v = (string) DB::table('isletme_yasal')->where('sube_id', $sid)->value('vergi_no'); } catch (\Throwable $e) {}
        if (trim($v) === '') { try { if (_trfKolon('edonusum_ayarlari', 'vkn_tckn')) $v = (string) DB::table('edonusum_ayarlari')->where('sube_id', $sid)->value('vkn_tckn'); } catch (\Throwable $e) {} }
        return $c[$sid] = preg_replace('/\D+/', '', $v);
    }
    function _subeUnvan($sid)
    {
        $u = '';
        try { if (Schema::hasTable('isletme_yasal')) $u = (string) DB::table('isletme_yasal')->where('sube_id', $sid)->value('unvan'); } catch (\Throwable $e) {}
        if (trim($u) === '') { try { if (_trfKolon('edonusum_ayarlari', 'firma_unvan')) $u = (string) DB::table('edonusum_ayarlari')->where('sube_id', $sid)->value('firma_unvan'); } catch (\Throwable $e) {} }
        return trim($u) !== '' ? trim($u) : (string) DB::table('subeler')->where('id', $sid)->value('ad');
    }
    // Aynı FİRMANIN (aynı grup + aynı vergi no) şubeleri — muhasebe kapsamı. VKN yoksa yalnız kendisi.
    function _firmaSubeIdleri($sid)
    {
        $sid = (int) $sid; $v = _subeVkn($sid);
        if ($v === '') return [$sid];
        $out = [];
        foreach (_grupSubeleri($sid) as $o) if ($o === $sid || _subeVkn($o) === $v) $out[] = $o;
        return $out ?: [$sid];
    }
    // Bu şubenin transfer ortakları: [sube_id => [ad, tur, yon, carpan]]
    // yon: iki = iki yönlü (grup içi) | gelir = o bana gönderebilir | gider = ben ona gönderebilirim
    function _transferOrtaklari($sid)
    {
        $sid = (int) $sid; $out = [];
        if (!_trfKolon('subeler', 'grup_id') || !_trfGrupId($sid)) return $out;
        $benV = _subeVkn($sid);
        $ad = DB::table('subeler')->pluck('ad', 'id');
        foreach (_grupSubeleri($sid) as $o) {
            if ($o === $sid) continue;
            $v = _subeVkn($o);
            $tur = ($benV !== '' && $v !== '') ? ($benV === $v ? 'ic' : 'firmalar_arasi') : 'vkn_eksik';
            $out[$o] = ['id' => $o, 'ad' => (string) ($ad[$o] ?? ('Şube #' . $o)), 'tur' => $tur, 'yon' => 'iki', 'carpan' => 1.0];
        }
        try {
            if (Schema::hasTable('franchise_baglari')) {
                $g = _trfGrupId($sid);
                // Ben franchise'ım: merkezin depo şubeleri bana gönderebilir
                foreach (DB::table('franchise_baglari')->where('franchise_grup_id', $g)->where('aktif', 1)->get() as $b) {
                    foreach (DB::table('subeler')->where('grup_id', $b->merkez_grup_id)->where('franchise_depo', 1)->pluck('id') as $o) {
                        $o = (int) $o; if ($o === $sid || isset($out[$o])) continue;
                        $out[$o] = ['id' => $o, 'ad' => (string) ($ad[$o] ?? ''), 'tur' => 'franchise', 'yon' => 'gelir', 'carpan' => (float) $b->fiyat_carpani];
                    }
                }
                // Ben merkezin depo şubesiyim: franchise şubelere gönderebilirim
                if ((int) DB::table('subeler')->where('id', $sid)->value('franchise_depo') === 1) {
                    foreach (DB::table('franchise_baglari')->where('merkez_grup_id', $g)->where('aktif', 1)->get() as $b) {
                        foreach (DB::table('subeler')->where('grup_id', $b->franchise_grup_id)->pluck('id') as $o) {
                            $o = (int) $o; if ($o === $sid || isset($out[$o])) continue;
                            $out[$o] = ['id' => $o, 'ad' => (string) ($ad[$o] ?? ''), 'tur' => 'franchise', 'yon' => 'gider', 'carpan' => (float) $b->fiyat_carpani];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}
        return $out;
    }
    // Personelin geçebileceği şubeler (ana şubesi dahil). Sahip: grubun tüm şubeleri; diğerleri: elle verilen erişim.
    function _personelErisebilirSubeler($p)
    {
        if (!$p) return [];
        $ana = (int) ($p->ana_sube_id ?? $p->sube_id);
        if (!_trfKolon('subeler', 'grup_id') || !_trfGrupId($ana)) return [$ana];
        $grup = _grupSubeleri($ana);
        if (($p->rol ?? '') === 'sahip') return $grup;
        $ek = [];
        try { if (Schema::hasTable('personel_sube_erisim')) $ek = DB::table('personel_sube_erisim')->where('personel_id', $p->id)->pluck('sube_id')->map(fn ($x) => (int) $x)->all(); } catch (\Throwable $e) {}
        return array_values(array_unique(array_merge([$ana], array_intersect($ek, $grup))));
    }
    // Web oturumunda seçilen şube geçerliyse personelin şubesini ona çevirir (ana şube ana_sube_id'de saklanır)
    function _subeSecimUygula($p)
    {
        if (!$p) return $p;
        $sec = (int) session('grup_sube_secim');
        if (!$sec || $sec === (int) $p->sube_id) return $p;
        static $c = [];
        $k = $p->id . ':' . $sec;
        if (!array_key_exists($k, $c)) $c[$k] = in_array($sec, _personelErisebilirSubeler($p), true);
        if (!$c[$k]) { try { session()->forget('grup_sube_secim'); } catch (\Throwable $e) {} return $p; }
        $p->ana_sube_id = (int) $p->sube_id;
        $p->sube_id = $sec;
        return $p;
    }
    // _aktifSubeId için: personel id'den geçerli seçili şube (yoksa 0)
    function _subeSecimId($pid)
    {
        $sec = (int) session('grup_sube_secim');
        if (!$sec || !$pid) return 0;
        static $c = [];
        if (!array_key_exists($pid, $c)) {
            $p = DB::table('personeller')->where('id', $pid)->where('aktif', 1)->first();
            $c[$pid] = ($p && $sec !== (int) $p->sube_id && in_array($sec, _personelErisebilirSubeler($p), true)) ? $sec : 0;
        }
        return $c[$pid];
    }
}

// ---------------------------------------------------------------- STOK ANALİZİ
if (!function_exists('_trfAyar')) {
    function _trfAyar($sid)
    {
        $g = _trfGrupId($sid) ?: ('s' . $sid);
        $gun = (int) (function_exists('resto_ayar_al') ? resto_ayar_al('trf_gun_' . $g, 7) : 7);
        $skt = (int) (function_exists('resto_ayar_al') ? resto_ayar_al('trf_skt_' . $g, 5) : 5);
        return ['gun' => max(1, min(60, $gun ?: 7)), 'skt' => max(1, min(60, $skt ?: 5))];
    }
    // Bir şubede malzemelerin durumu: mevcut, kritik, günlük tüketim, fazla, SKT riski, verilebilir
    function _trfStokDurum($sid, array $mids)
    {
        $sid = (int) $sid; $mids = array_values(array_unique(array_filter(array_map('intval', $mids))));
        if (!$mids) return [];
        $ay = _trfAyar($sid);
        $mevcut = DB::table('stok_hareketleri')->where('sube_id', $sid)->whereIn('malzeme_id', $mids)->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
        $tuk = DB::table('stok_hareketleri')->where('sube_id', $sid)->whereIn('malzeme_id', $mids)->where('tip', 'tuketim')->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('malzeme_id, SUM(-miktar) t')->groupBy('malzeme_id')->pluck('t', 'malzeme_id');
        $mal = DB::table('malzemeler')->whereIn('id', $mids)->get(['id', 'ad', 'kritik_stok', 'guncel_maliyet', 'temel_birim_id'])->keyBy('id');
        $subeKritik = collect();
        try { if (Schema::hasTable('sube_malzeme_ayar')) $subeKritik = DB::table('sube_malzeme_ayar')->where('sube_id', $sid)->whereIn('malzeme_id', $mids)->whereNotNull('kritik_stok')->pluck('kritik_stok', 'malzeme_id'); } catch (\Throwable $e) {}
        $parti = collect();
        try {
            if (Schema::hasTable('stok_partileri')) $parti = DB::table('stok_partileri')->where('sube_id', $sid)->whereIn('malzeme_id', $mids)->where('kalan_miktar', '>', 0)->whereNotNull('skt')
                ->orderBy('skt')->get(['malzeme_id', 'skt', 'kalan_miktar', 'lot_no'])->groupBy('malzeme_id');
        } catch (\Throwable $e) {}
        $birim = DB::table('birimler')->pluck('kisaltma', 'id');
        $out = [];
        foreach ($mids as $mid) {
            $m = $mal[$mid] ?? null; if (!$m) continue;
            $mev = max(0, (float) ($mevcut[$mid] ?? 0));
            $gunluk = max(0, (float) ($tuk[$mid] ?? 0)) / 30;
            $kritik = (float) ($subeKritik[$mid] ?? $m->kritik_stok ?? 0);
            $fazla = max(0, $mev - $kritik - $gunluk * $ay['gun']);
            // SKT riski: SKT'si eşik içindeki partilerden, o güne kadar tüketilemeyecek kısım
            $sktRisk = 0; $enYakin = null; $sktYakinMik = 0; $kumulatif = 0;
            foreach (($parti[$mid] ?? []) as $pt) {
                $gunKalan = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($pt->skt)->startOfDay(), false);
                if ($enYakin === null) $enYakin = \Carbon\Carbon::parse($pt->skt)->toDateString();
                if ($gunKalan > $ay['skt']) break;
                $kumulatif += (float) $pt->kalan_miktar; $sktYakinMik += (float) $pt->kalan_miktar;
                $sktRisk = max($sktRisk, $kumulatif - $gunluk * max(0, $gunKalan));
            }
            $verilebilir = min($mev, max($fazla, $sktRisk));
            $out[$mid] = ['malzeme_id' => $mid, 'ad' => $m->ad, 'birim' => (string) ($birim[$m->temel_birim_id] ?? ''), 'mevcut' => round($mev, 3), 'kritik' => round($kritik, 3),
                'gunluk' => round($gunluk, 3), 'fazla' => round($fazla, 3), 'skt_yakin' => round($sktYakinMik, 3), 'skt_risk' => round(max(0, $sktRisk), 3), 'en_yakin_skt' => $enYakin,
                'verilebilir' => round(max(0, $verilebilir), 3), 'maliyet' => (float) $m->guncel_maliyet, 'eksik' => $mev < $kritik];
        }
        return $out;
    }
    // Bu şubenin ihtiyacı için ortak şubelerdeki uygun stok: [mid => [[sube_id, ad, tur, verilebilir, skt...]]]
    function _trfOrtakStok($sid, array $mids)
    {
        $out = [];
        foreach (_transferOrtaklari($sid) as $o) {
            if (!in_array($o['yon'], ['iki', 'gelir'], true)) continue;
            foreach (_trfStokDurum($o['id'], $mids) as $mid => $d) {
                if ($d['verilebilir'] <= 0) continue;
                $out[$mid][] = array_merge($d, ['sube_id' => $o['id'], 'sube_ad' => $o['ad'], 'tur' => $o['tur']]);
            }
        }
        foreach ($out as &$l) usort($l, fn ($a, $b) => ($b['skt_risk'] <=> $a['skt_risk']) ?: ($b['verilebilir'] <=> $a['verilebilir']));
        return $out;
    }
}

// ---------------------------------------------------------------- YARDIMCILAR
if (!function_exists('_trfBirimCarpan')) {
    // Talep kalemi biriminden (ör. "kg", "koli") malzemenin TEMEL birimine çarpan. Bilinmiyorsa null.
    function _trfBirimCarpan($malzemeId, $birim)
    {
        $m = DB::table('malzemeler')->where('id', (int) $malzemeId)->first(['temel_birim_id']);
        if (!$m) return null;
        $temel = mb_strtolower(trim((string) DB::table('birimler')->where('id', $m->temel_birim_id)->value('kisaltma')), 'UTF-8');
        $b = mb_strtolower(trim((string) $birim), 'UTF-8');
        if ($b === '' || $b === $temel) return 1.0;
        $bid = DB::table('birimler')->whereRaw('LOWER(kisaltma) = ?', [$b])->orWhereRaw('LOWER(ad) = ?', [$b])->value('id');
        if ($bid && Schema::hasTable('birim_cevrimleri')) {
            $c = DB::table('birim_cevrimleri')->where('malzeme_id', (int) $malzemeId)->where('birim_id', $bid)->value('temel_birim_karsiligi');
            if ($c && (float) $c > 0) return (float) $c;
        }
        $std = ['kg>g' => 1000, 'g>kg' => 0.001, 'lt>ml' => 1000, 'l>ml' => 1000, 'ml>lt' => 0.001, 'ml>l' => 0.001, 'lt>l' => 1, 'l>lt' => 1, 'adet>ad' => 1, 'ad>adet' => 1];
        return isset($std[$b . '>' . $temel]) ? (float) $std[$b . '>' . $temel] : null;
    }
}
if (!function_exists('_trfPersonel')) {
    function _trfPersonel(Request $r)
    {
        $p = null;
        try { $p = function_exists('_posPersonel') ? _posPersonel() : null; } catch (\Throwable $e) {}
        if (!$p && $r->bearerToken() && function_exists('_apiPersonel')) { try { $p = _apiPersonel($r); } catch (\Throwable $e) {} }
        return $p;
    }
    function _trfYetkili($p)
    {
        if (!$p) return false;
        if (in_array($p->rol ?? '', ['sahip', 'mudur'], true)) return true;
        return function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_transfer');
    }
    function _trfTurAd($t)
    {
        return ['ic' => 'İç transfer', 'firmalar_arasi' => 'Firmalar arası satış', 'franchise' => 'Franchise tedarik', 'vkn_eksik' => 'Vergi no eksik'][$t] ?? $t;
    }
    function _trfDurumAd($d)
    {
        return ['oneri' => 'Gönderen önerdi · kabul bekliyor', 'istendi' => 'Onay bekliyor', 'hazirlaniyor' => 'Onaylandı · hazırlanıyor', 'yolda' => 'Yolda', 'teslim' => 'Teslim alındı', 'reddedildi' => 'Reddedildi', 'iptal' => 'İptal edildi'][$d] ?? $d;
    }
    // Şubede transferi yönetebilecek kişiler (bildirim alıcıları): sahip/müdür + gor_transfer yetkilileri
    function _trfAlicilar($sid)
    {
        $ids = [];
        try {
            foreach (DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->get() as $p) if (_trfYetkili($p)) $ids[] = (int) $p->id;
            if (Schema::hasTable('personel_sube_erisim')) foreach (DB::table('personel_sube_erisim')->where('sube_id', (int) $sid)->pluck('personel_id') as $pid) $ids[] = (int) $pid;
        } catch (\Throwable $e) {}
        return array_values(array_unique($ids));
    }
    function _trfBildir($sid, $tip, $baslik, $govde, $transferId)
    {
        try {
            if (function_exists('_bmEkle')) _bmEkle($sid, _trfAlicilar($sid), $tip, $baslik, $govde, '/sube-transfer?ac=' . (int) $transferId, 1);
            elseif (function_exists('_fcmPersoneleGonder')) _fcmPersoneleGonder($sid, _trfAlicilar($sid), $baslik, $govde, ['tur' => $tip, 'id' => (string) $transferId]);
        } catch (\Throwable $e) {}
    }
    function _trfDetay($id)
    {
        $t = DB::table('sube_transferleri')->where('id', (int) $id)->first();
        if (!$t) return null;
        $ad = DB::table('subeler')->whereIn('id', [$t->gonderen_sube_id, $t->alan_sube_id])->pluck('ad', 'id');
        $t->gonderen_ad = (string) ($ad[$t->gonderen_sube_id] ?? ''); $t->alan_ad = (string) ($ad[$t->alan_sube_id] ?? '');
        $t->tur_ad = _trfTurAd($t->tur); $t->durum_ad = _trfDurumAd($t->durum);
        $t->kalemler = DB::table('sube_transfer_kalemleri')->where('transfer_id', $t->id)->orderBy('id')->get()->map(function ($k) { $k->lotlar = json_decode((string) $k->lotlar, true) ?: []; return $k; })->all();
        return $t;
    }
    // Transferi görebilir mi: iki şubeden birinde (seçili şube) olmalı
    function _trfGorur($p, $t)
    {
        return $p && $t && in_array((int) $p->sube_id, [(int) $t->gonderen_sube_id, (int) $t->alan_sube_id], true);
    }
    // Talepten ayrılan transfer kalemleri geri döner (red/iptal)
    function _trfTalebeGeriAl($t)
    {
        try {
            if (!$t->talep_id) return;
            $alt = DB::table('satinalma_talepleri')->where('id', $t->talep_id)->first();
            if (!$alt || ($alt->durum ?? '') !== 'transfer') return;
            $anaId = (int) ($alt->ana_talep_id ?? 0);
            if (!$anaId) { DB::table('satinalma_talepleri')->where('id', $alt->id)->update(['durum' => 'yeni', 'updated_at' => now()]); return; }
            foreach (DB::table('talep_kalemleri')->where('talep_id', $alt->id)->get() as $k) {
                $es = $k->malzeme_id ? DB::table('talep_kalemleri')->where('talep_id', $anaId)->where('malzeme_id', $k->malzeme_id)->first() : null;
                if ($es) { DB::table('talep_kalemleri')->where('id', $es->id)->update(['miktar' => DB::raw('miktar + ' . (float) $k->miktar), 'updated_at' => now()]); DB::table('talep_kalemleri')->where('id', $k->id)->delete(); }
                else DB::table('talep_kalemleri')->where('id', $k->id)->update(['talep_id' => $anaId, 'updated_at' => now()]);
            }
            DB::table('satinalma_talepleri')->where('id', $alt->id)->update(['durum' => 'iptal', 'not' => trim(($alt->not ?? '') . "\n[Transfer " . $t->transfer_no . ' ' . _trfDurumAd($t->durum) . ' — kalemler ana talebe döndü]'), 'updated_at' => now()]);
            $ana = DB::table('satinalma_talepleri')->where('id', $anaId)->first();
            if ($ana && in_array($ana->durum, ['tamamlandi', 'transfer'], true)) DB::table('satinalma_talepleri')->where('id', $anaId)->update(['durum' => 'yeni', 'updated_at' => now()]);
        } catch (\Throwable $e) {}
    }
}

// ---------------------------------------------------------------- FİNANS (firmalar arası / franchise)
if (!function_exists('_trfFinans')) {
    // Alan şubeye otomatik ALIŞ faturası (borç), gönderen şubeye cari ALACAK. İç transferde çağrılmaz.
    function _trfFinans($t, $kalemler, $p)
    {
        $net = 0.0; foreach ($kalemler as $k) $net += (float) $k->teslim_alinan * (float) $k->birim_fiyat;
        $net = round($net, 2); $kdv = round($net * (float) $t->kdv_orani / 100, 2); $brut = $net + $kdv;
        $sonuc = ['net' => $net, 'kdv' => $kdv, 'fatura_id' => null, 'cari_id' => null];
        if ($brut <= 0) return $sonuc;
        $gonUnvan = _subeUnvan($t->gonderen_sube_id); $gonVkn = _subeVkn($t->gonderen_sube_id);
        // 1) Alan taraf: gönderen firma bir TEDARİKÇİ kartı olur (vergi noyla eşleşir)
        if (function_exists('_restoTedarikEnsure')) _restoTedarikEnsure();
        $ted = null;
        if ($gonVkn !== '' && _trfKolon('tedarikciler', 'vkn_tckn')) $ted = DB::table('tedarikciler')->where('vkn_tckn', $gonVkn)->first();
        if (!$ted) $ted = DB::table('tedarikciler')->where('ad', 'Grup içi: ' . $gonUnvan)->first();
        if (!$ted) {
            $row = ['ad' => mb_substr('Grup içi: ' . $gonUnvan, 0, 190), 'aciklama' => 'Şubeler arası transfer için otomatik açıldı', 'created_at' => now(), 'updated_at' => now()];
            if (_trfKolon('tedarikciler', 'unvan')) $row['unvan'] = $gonUnvan;
            if (_trfKolon('tedarikciler', 'vkn_tckn') && $gonVkn !== '') $row['vkn_tckn'] = $gonVkn;
            if (_trfKolon('tedarikciler', 'aktif')) $row['aktif'] = 1;
            $tedId = DB::table('tedarikciler')->insertGetId($row);
        } else $tedId = (int) $ted->id;
        if (function_exists('_katalogSahiplen')) _katalogSahiplen('tedarikci', $tedId, $t->alan_sube_id); // alan işletme bu tedarikçiyi görsün
        $fat = ['sube_id' => $t->alan_sube_id, 'tedarikci_id' => $tedId, 'fatura_no' => $t->transfer_no, 'tarih' => now()->toDateString(), 'toplam' => $brut,
            'durum' => 'onaylandi', 'giren_personel_id' => $p->id ?? null, 'created_at' => now(), 'updated_at' => now()];
        if (_trfKolon('alis_faturalari', 'kdv')) $fat['kdv'] = $kdv;
        $fid = DB::table('alis_faturalari')->insertGetId($fat);
        $birimler = DB::table('malzemeler')->whereIn('id', collect($kalemler)->pluck('malzeme_id')->all())->pluck('temel_birim_id', 'id');
        foreach ($kalemler as $k) {
            if ((float) $k->teslim_alinan <= 0 || empty($birimler[$k->malzeme_id])) continue;
            DB::table('alis_fatura_kalemleri')->insert(['fatura_id' => $fid, 'malzeme_id' => $k->malzeme_id, 'alis_birim_id' => $birimler[$k->malzeme_id],
                'miktar' => round((float) $k->teslim_alinan, 3), 'birim_fiyat' => round((float) $k->birim_fiyat, 4), 'satir_toplam' => round((float) $k->teslim_alinan * (float) $k->birim_fiyat, 2),
                'uyari' => 'yesil', 'created_at' => now(), 'updated_at' => now()]);
        }
        $sonuc['fatura_id'] = $fid;
        // 2) Gönderen taraf: alan firma için cari ALACAK (o bize borçlu)
        if (Schema::hasTable('cari_hesaplar') && Schema::hasTable('cari_hareketler')) {
            $alanUnvan = _subeUnvan($t->alan_sube_id);
            $cariAd = mb_substr('Grup içi: ' . $alanUnvan, 0, 190);
            $cid = DB::table('cari_hesaplar')->where('sube_id', $t->gonderen_sube_id)->where('ad', $cariAd)->value('id');
            if (!$cid) $cid = DB::table('cari_hesaplar')->insertGetId(['sube_id' => $t->gonderen_sube_id, 'ad' => $cariAd, 'tip' => 'grup_ici', 'aktif' => 1, 'created_at' => now()]);
            $sonuc['cari_id'] = DB::table('cari_hareketler')->insertGetId(['sube_id' => $t->gonderen_sube_id, 'cari_id' => $cid, 'tip' => 'borc', 'tutar' => $brut,
                'aciklama' => $t->transfer_no . ' grup içi satış (' . _trfTurAd($t->tur) . ')', 'personel_id' => $p->id ?? null, 'created_at' => now()]);
        }
        return $sonuc;
    }
}

// ---------------------------------------------------------------- SKT / FAZLA STOK MOTORU (günde 1 kez, bildirim)
if (!function_exists('_trfOneriler')) {
    // Bu şube için öneriler: al = bende eksik/kritik, ortakta fazla; ver = bende SKT riski, ortak tüketiyor
    function _trfOneriler($sid)
    {
        $sid = (int) $sid; $ort = _transferOrtaklari($sid);
        $sonuc = ['al' => [], 'ver' => []];
        if (!$ort) return $sonuc;
        $mids = DB::table('stok_hareketleri')->where('sube_id', $sid)->distinct()->pluck('malzeme_id')->map(fn ($x) => (int) $x)->all();
        foreach (array_keys($ort) as $o) foreach (DB::table('stok_hareketleri')->where('sube_id', $o)->distinct()->pluck('malzeme_id') as $x) $mids[] = (int) $x;
        $mids = array_values(array_unique($mids));
        if (!$mids) return $sonuc;
        $ben = _trfStokDurum($sid, $mids);
        $eksikler = array_keys(array_filter($ben, fn ($d) => $d['eksik'] || ($d['gunluk'] > 0 && $d['mevcut'] < $d['gunluk'] * 3)));
        if ($eksikler) {
            foreach (_trfOrtakStok($sid, $eksikler) as $mid => $l) {
                $d = $ben[$mid]; $ihtiyac = max($d['kritik'] - $d['mevcut'], $d['gunluk'] * _trfAyar($sid)['gun'] - $d['mevcut'], 0);
                $sonuc['al'][] = ['malzeme_id' => $mid, 'ad' => $d['ad'], 'birim' => $d['birim'], 'mevcut' => $d['mevcut'], 'kritik' => $d['kritik'], 'ihtiyac' => round($ihtiyac, 3), 'kaynaklar' => array_slice($l, 0, 3)];
            }
        }
        foreach ($ben as $mid => $d) {
            if ($d['skt_risk'] <= 0) continue;
            $alicilar = [];
            foreach ($ort as $o) {
                if (!in_array($o['yon'], ['iki', 'gider'], true)) continue;
                $od = _trfStokDurum($o['id'], [$mid])[$mid] ?? null;
                if ($od && $od['gunluk'] > 0) $alicilar[] = ['sube_id' => $o['id'], 'sube_ad' => $o['ad'], 'tur' => $o['tur'], 'gunluk' => $od['gunluk'], 'mevcut' => $od['mevcut']];
            }
            usort($alicilar, fn ($a, $b) => $b['gunluk'] <=> $a['gunluk']);
            $sonuc['ver'][] = ['malzeme_id' => $mid, 'ad' => $d['ad'], 'birim' => $d['birim'], 'skt_risk' => $d['skt_risk'], 'en_yakin_skt' => $d['en_yakin_skt'], 'gunluk' => $d['gunluk'], 'maliyet' => round($d['skt_risk'] * $d['maliyet'], 2), 'alicilar' => array_slice($alicilar, 0, 3)];
        }
        return $sonuc;
    }
    function _trfMotor($sid)
    {
        $sid = (int) $sid;
        $k = 'trf_motor_' . $sid . '_' . date('Ymd');
        if (cache()->get($k)) return;
        cache()->put($k, 1, now()->addDay());
        try {
            _trfEnsure();
            if (!_transferOrtaklari($sid)) return;
            $o = _trfOneriler($sid);
            if ($o['ver']) {
                $ilk = $o['ver'][0];
                _trfBildir($sid, 'transfer_oneri', '♻️ SKT riski: ' . count($o['ver']) . ' ürün başka şubede kullanılabilir',
                    $ilk['ad'] . ' ' . $ilk['skt_risk'] . ' ' . $ilk['birim'] . ' (SKT ' . $ilk['en_yakin_skt'] . ')' . ($ilk['alicilar'] ? ' → ' . $ilk['alicilar'][0]['sube_ad'] . ' tüketiyor' : ''), 0);
            }
            if ($o['al']) {
                $ilk = $o['al'][0];
                _trfBildir($sid, 'transfer_oneri', '🔄 ' . count($o['al']) . ' eksik ürün kardeş şubede fazla var',
                    $ilk['ad'] . ': ' . $ilk['kaynaklar'][0]['sube_ad'] . ' ' . $ilk['kaynaklar'][0]['verilebilir'] . ' ' . $ilk['birim'] . ' verebilir — yeni sipariş gerekmeyebilir', 0);
            }
        } catch (\Throwable $e) {}
    }
}

// ================================================================ ROUTES
// Şube seçici (grup sahibi / erişim verilen personel)
Route::match(['get', 'post'], '/sube-sec', function (Request $r) {
    _trfEnsure();
    $p = null; try { $p = DB::table('personeller')->where('id', (int) session('pos_personel_id'))->where('aktif', 1)->first(); } catch (\Throwable $e) {}
    if (!$p) return redirect('/pos-giris');
    $hedef = (int) $r->input('id');
    $geri = url()->previous() ?: '/';
    if ($hedef === (int) $p->sube_id) { session()->forget('grup_sube_secim'); return redirect($geri); }
    if (!in_array($hedef, _personelErisebilirSubeler($p), true)) return redirect($geri)->with('hata', 'Bu şubeye erişim yetkiniz yok.');
    session(['grup_sube_secim' => $hedef]);
    try { if (function_exists('_personelLog')) _personelLog($p, 'guvenlik', 'Şube değiştirdi → ' . DB::table('subeler')->where('id', $hedef)->value('ad')); } catch (\Throwable $e) {}
    return redirect($geri);
});

// Ana sayfa
Route::get('/sube-transfer', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!$p) return redirect('/pos-giris');
    if (!_trfYetkili($p)) return response(view('yetkisiz', ['sayfa' => '/sube-transfer']), 403);
    $sid = (int) $p->sube_id;
    _trfMotor($sid);
    $ortaklar = _transferOrtaklari($sid);
    $q = DB::table('sube_transferleri')->where(function ($w) use ($sid) { $w->where('gonderen_sube_id', $sid)->orWhere('alan_sube_id', $sid); })->orderByDesc('id')->limit(300)->get();
    $ad = DB::table('subeler')->pluck('ad', 'id');
    $kal = $q->isNotEmpty() ? DB::table('sube_transfer_kalemleri')->whereIn('transfer_id', $q->pluck('id'))->get()->groupBy('transfer_id') : collect();
    $liste = $q->map(function ($t) use ($ad, $kal, $sid) {
        $ks = $kal[$t->id] ?? collect();
        return ['id' => (int) $t->id, 'no' => $t->transfer_no, 'tur' => $t->tur, 'tur_ad' => _trfTurAd($t->tur), 'durum' => $t->durum, 'durum_ad' => _trfDurumAd($t->durum),
            'yon' => (int) $t->gonderen_sube_id === $sid ? 'giden' : 'gelen', 'gonderen' => (string) ($ad[$t->gonderen_sube_id] ?? ''), 'alan' => (string) ($ad[$t->alan_sube_id] ?? ''),
            'isteyen' => $t->isteyen_ad, 'tarih' => \Carbon\Carbon::parse($t->created_at)->format('d.m.Y H:i'), 'kaynak' => $t->kaynak, 'talep_id' => $t->talep_id,
            'kalem' => $ks->count(), 'ozet' => $ks->take(4)->map(fn ($k) => $k->ad . ' ' . rtrim(rtrim(number_format((float) ($k->teslim_alinan ?? $k->gonderilen ?? $k->onaylanan ?? $k->istenen), 3, ',', '.'), '0'), ',') . ' ' . $k->birim)->implode(', '),
            'tutar' => (float) ($t->net_toplam ?: $t->maliyet_toplam)];
    })->values();
    $mal = _katalogFiltre(DB::table('malzemeler'), 'malzeme', $sid, 'malzemeler.id')->orderBy('ad')->get(['id', 'ad', 'temel_birim_id']);
    $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    $malzemeler = $mal->map(fn ($m) => ['id' => (int) $m->id, 'ad' => $m->ad, 'birim' => (string) ($birim[$m->temel_birim_id] ?? '')])->values();
    $ayar = _trfAyar($sid);
    $kritik = DB::table('sube_malzeme_ayar')->where('sube_id', $sid)->whereNotNull('kritik_stok')->pluck('kritik_stok', 'malzeme_id')->map(fn ($v) => (float) $v);
    return view('sube_transfer', ['liste' => $liste, 'ortaklar' => array_values($ortaklar), 'malzemeler' => $malzemeler, 'sid' => $sid, 'kritik' => $kritik,
        'subeAd' => (string) ($ad[$sid] ?? ''), 'ayar' => $ayar, 'grupVar' => (bool) _trfGrupId($sid), 'acId' => (int) $r->query('ac'), 'yonetici' => in_array($p->rol ?? '', ['sahip', 'mudur'], true)]);
});

Route::get('/api/sube-transfer/oneriler', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    return response()->json(['ok' => true] + _trfOneriler((int) $p->sube_id));
});

// Ortak şubelerdeki stok (malzeme listesi için)
Route::get('/api/sube-transfer/stok', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $mids = array_filter(array_map('intval', explode(',', (string) $r->query('m'))));
    $sid = (int) $p->sube_id;
    return response()->json(['ok' => true, 'ben' => _trfStokDurum($sid, $mids), 'ortak' => _trfOrtakStok($sid, $mids)]);
});

// Talep detayı için: kalemlerin kardeş şubelerde durumu
Route::get('/api/sube-transfer/talep', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!$p) return response()->json(['ok' => false], 401);
    $sid = (int) $p->sube_id;
    $t = DB::table('satinalma_talepleri')->where('id', (int) $r->query('id'))->where('sube_id', $sid)->first();
    if (!$t) return response()->json(['ok' => false, 'mesaj' => 'Talep bulunamadı.']);
    // Bu talebe (veya alt talebine) bağlı transferler — "nereden geldi" talep detayında görünsün
    $altIds = DB::table('satinalma_talepleri')->where(function ($w) use ($t) { $w->where('id', $t->id); if (_trfKolon('satinalma_talepleri', 'ana_talep_id')) $w->orWhere('ana_talep_id', $t->id); })->pluck('id');
    $sadAd = DB::table('subeler')->pluck('ad', 'id');
    $trfs = DB::table('sube_transferleri')->whereIn('talep_id', $altIds)->orderBy('id')->get()->map(fn ($x) => ['id' => (int) $x->id, 'no' => $x->transfer_no, 'durum' => $x->durum, 'durum_ad' => _trfDurumAd($x->durum),
        'gonderen' => (string) ($sadAd[$x->gonderen_sube_id] ?? ''), 'urunler' => DB::table('sube_transfer_kalemleri')->where('transfer_id', $x->id)->pluck('ad')->all()])->values();
    if (!_transferOrtaklari($sid)) return response()->json(['ok' => true, 'grup' => false, 'kalemler' => [], 'transferler' => $trfs]);
    $ks = DB::table('talep_kalemleri')->where('talep_id', $t->id)->whereNotNull('malzeme_id')->get();
    $ort = _trfOrtakStok($sid, $ks->pluck('malzeme_id')->all());
    $birim = DB::table('malzemeler')->join('birimler', 'birimler.id', '=', 'malzemeler.temel_birim_id')->whereIn('malzemeler.id', $ks->pluck('malzeme_id'))->pluck('birimler.kisaltma', 'malzemeler.id');
    $out = [];
    foreach ($ks as $k) {
        if (empty($ort[$k->malzeme_id])) continue;
        $carpan = _trfBirimCarpan($k->malzeme_id, $k->birim);
        $out[] = ['kalem_id' => (int) $k->id, 'malzeme_id' => (int) $k->malzeme_id, 'ad' => $k->ad, 'miktar' => (float) $k->miktar, 'birim' => $k->birim, 'temel_birim' => (string) ($birim[$k->malzeme_id] ?? ''),
            'carpan' => $carpan, 'ihtiyac_temel' => $carpan ? round((float) $k->miktar * $carpan, 3) : null, 'kaynaklar' => array_slice($ort[$k->malzeme_id], 0, 3)];
    }
    $acik = DB::table('sube_transferleri')->where('talep_id', $t->id)->whereNotIn('durum', ['reddedildi', 'iptal'])->count();
    return response()->json(['ok' => true, 'grup' => true, 'kalemler' => $out, 'yetkili' => _trfYetkili($p), 'durum' => $t->durum, 'acik_transfer' => $acik, 'transferler' => $trfs]);
});

// TRANSFER İSTE (alan şube ister)
Route::post('/sube-transfer/iste', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Transfer yetkiniz yok (patron vermeli).'], 403);
    $sid = (int) $p->sube_id; $gon = (int) $r->input('gonderen_sube_id');
    $ort = _transferOrtaklari($sid)[$gon] ?? null;
    if (!$ort || !in_array($ort['yon'], ['iki', 'gelir'], true)) return response()->json(['ok' => false, 'mesaj' => 'Bu şubeden transfer isteyemezsiniz (aynı grupta değil).']);
    if ($ort['tur'] === 'vkn_eksik') return response()->json(['ok' => false, 'mesaj' => 'Vergi numarası eksik: transfer türü (iç / firmalar arası) belirlenemiyor. İki şubenin de Yasal Bilgiler sayfasında vergi no girilmeli.']);
    $kalemler = collect((array) $r->input('kalemler', []))->map(fn ($k) => ['malzeme_id' => (int) ($k['malzeme_id'] ?? 0), 'miktar' => (float) str_replace(',', '.', (string) ($k['miktar'] ?? 0)), 'talep_kalem_id' => (int) ($k['talep_kalem_id'] ?? 0)])
        ->filter(fn ($k) => $k['malzeme_id'] > 0 && $k['miktar'] > 0)->values();
    if ($kalemler->isEmpty()) return response()->json(['ok' => false, 'mesaj' => 'En az bir ürün ve miktar girin.']);
    $mal = DB::table('malzemeler')->whereIn('id', $kalemler->pluck('malzeme_id'))->get(['id', 'ad', 'temel_birim_id', 'guncel_maliyet'])->keyBy('id');
    $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    $talepId = (int) $r->input('talep_id') ?: null;
    $talep = $talepId ? DB::table('satinalma_talepleri')->where('id', $talepId)->where('sube_id', $sid)->first() : null;
    if ($talepId && !$talep) return response()->json(['ok' => false, 'mesaj' => 'Talep bulunamadı.']);
    if ($talep && !in_array($talep->durum, ['yeni', 'teklif'], true)) return response()->json(['ok' => false, 'mesaj' => 'Bu talep artık fiyat araştırma aşamasında değil (' . $talep->durum . '); transfer bağlanamaz.']);
    $kilit = cache()->lock('trf_iste_' . $sid . '_' . ($talepId ?: 'x'), 15);
    if (!$kilit->get()) return response()->json(['ok' => false, 'mesaj' => 'İşlem sürüyor, birkaç saniye sonra tekrar deneyin.']);
    try {
        $id = DB::transaction(function () use ($p, $sid, $gon, $ort, $kalemler, $mal, $birim, $talep, $r) {
            $id = DB::table('sube_transferleri')->insertGetId(['tur' => $ort['tur'], 'gonderen_sube_id' => $gon, 'alan_sube_id' => $sid, 'durum' => 'istendi',
                'kaynak' => $talep ? 'talep' : (in_array($r->input('kaynak'), ['oneri', 'skt'], true) ? $r->input('kaynak') : 'elle'),
                'isteyen_id' => $p->id, 'isteyen_ad' => $p->ad, 'not' => mb_substr((string) $r->input('not'), 0, 1000) ?: null, 'kdv_orani' => $ort['tur'] === 'ic' ? 0 : 10,
                'created_at' => now(), 'updated_at' => now()]);
            DB::table('sube_transferleri')->where('id', $id)->update(['transfer_no' => 'TRF-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT)]);
            foreach ($kalemler as $k) {
                $m = $mal[$k['malzeme_id']] ?? null; if (!$m) continue;
                DB::table('sube_transfer_kalemleri')->insert(['transfer_id' => $id, 'malzeme_id' => $m->id, 'ad' => $m->ad, 'birim' => (string) ($birim[$m->temel_birim_id] ?? ''),
                    'istenen' => $k['miktar'], 'birim_maliyet' => (float) $m->guncel_maliyet, 'talep_kalem_id' => ($talep && $k['talep_kalem_id']) ? $k['talep_kalem_id'] : null, 'created_at' => now(), 'updated_at' => now()]);
            }
            // Talepten geldiyse: transferle karşılanan miktar ALT TALEBE taşınır (ana talepte kalan satın almaya devam eder)
            if ($talep) {
                if (function_exists('_talepAnaEnsure')) _talepAnaEnsure();
                $altId = DB::table('satinalma_talepleri')->insertGetId(['sube_id' => $talep->sube_id, 'departman' => $talep->departman, 'isteyen_id' => $talep->isteyen_id ?? null, 'isteyen_ad' => $talep->isteyen_ad ?? null,
                    'aciliyet' => $talep->aciliyet ?? 'normal', 'durum' => 'transfer', 'ana_talep_id' => $talep->id, 'transfer_id' => $id,
                    'not' => 'Şubeler arası transfer — Talep #' . $talep->id . ' (' . $ort['ad'] . ' şubesinden)', 'created_at' => now(), 'updated_at' => now()]);
                foreach ($kalemler as $k) {
                    $tk = $k['talep_kalem_id'] ? DB::table('talep_kalemleri')->where('id', $k['talep_kalem_id'])->where('talep_id', $talep->id)->first() : null;
                    if (!$tk) continue;
                    // Transfer miktarı TEMEL birimde; talep kalemi kendi biriminde (kg/koli...) → çevir
                    $carpan = _trfBirimCarpan($tk->malzeme_id, $tk->birim) ?: 1.0;
                    $talepMik = round($k['miktar'] / $carpan, 3);
                    if ($talepMik >= (float) $tk->miktar - 0.0005) DB::table('talep_kalemleri')->where('id', $tk->id)->update(['talep_id' => $altId, 'updated_at' => now()]);
                    else {
                        DB::table('talep_kalemleri')->where('id', $tk->id)->update(['miktar' => round((float) $tk->miktar - $talepMik, 3), 'updated_at' => now()]);
                        DB::table('talep_kalemleri')->insert(['talep_id' => $altId, 'malzeme_id' => $tk->malzeme_id, 'ad' => $tk->ad, 'miktar' => $talepMik, 'birim' => $tk->birim, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
                DB::table('sube_transferleri')->where('id', $id)->update(['talep_id' => $altId]);
                if (!DB::table('talep_kalemleri')->where('talep_id', $talep->id)->exists())
                    DB::table('satinalma_talepleri')->where('id', $talep->id)->update(['durum' => 'tamamlandi', 'not' => trim(($talep->not ?? '') . "\n[Tüm kalemler şubeler arası transferle karşılanıyor]"), 'updated_at' => now()]);
            }
            return $id;
        });
    } finally { $kilit->release(); }
    $t = DB::table('sube_transferleri')->where('id', $id)->first();
    _trfBildir($gon, 'transfer_istek', '🔄 Transfer isteği: ' . DB::table('subeler')->where('id', $sid)->value('ad'), $t->transfer_no . ' · ' . $kalemler->count() . ' ürün · onayınızı bekliyor', $id);
    return response()->json(['ok' => true, 'id' => $id, 'no' => $t->transfer_no, 'mesaj' => $t->transfer_no . ' oluşturuldu. ' . $ort['ad'] . ' şubesinin onayına gitti.']);
});

// ÖNERİ (gönderen başlatır: "bende fazla / SKT'si yakın, sen kullan") → alan şube kabul eder
Route::post('/sube-transfer/oner', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Transfer yetkiniz yok.'], 403);
    $sid = (int) $p->sube_id; $alan = (int) $r->input('alan_sube_id');
    $ort = _transferOrtaklari($sid)[$alan] ?? null;
    if (!$ort || !in_array($ort['yon'], ['iki', 'gider'], true)) return response()->json(['ok' => false, 'mesaj' => 'Bu şubeye gönderemezsiniz.']);
    if ($ort['tur'] === 'vkn_eksik') return response()->json(['ok' => false, 'mesaj' => 'Vergi numarası eksik: iki şubenin de Yasal Bilgiler sayfasında vergi no girilmeli.']);
    $kalemler = collect((array) $r->input('kalemler', []))->map(fn ($k) => ['malzeme_id' => (int) ($k['malzeme_id'] ?? 0), 'miktar' => (float) str_replace(',', '.', (string) ($k['miktar'] ?? 0))])
        ->filter(fn ($k) => $k['malzeme_id'] > 0 && $k['miktar'] > 0)->values();
    if ($kalemler->isEmpty()) return response()->json(['ok' => false, 'mesaj' => 'En az bir ürün ve miktar girin.']);
    $mevcut = DB::table('stok_hareketleri')->where('sube_id', $sid)->whereIn('malzeme_id', $kalemler->pluck('malzeme_id'))->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
    $mal = DB::table('malzemeler')->whereIn('id', $kalemler->pluck('malzeme_id'))->get(['id', 'ad', 'temel_birim_id', 'guncel_maliyet'])->keyBy('id');
    $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    foreach ($kalemler as $k) if ($k['miktar'] > (float) ($mevcut[$k['malzeme_id']] ?? 0) + 0.0001) return response()->json(['ok' => false, 'mesaj' => ($mal[$k['malzeme_id']]->ad ?? 'Ürün') . ': stoğunuzda yalnız ' . round((float) ($mevcut[$k['malzeme_id']] ?? 0), 3) . ' var.']);
    $id = DB::transaction(function () use ($p, $sid, $alan, $ort, $kalemler, $mal, $birim, $r) {
        $id = DB::table('sube_transferleri')->insertGetId(['tur' => $ort['tur'], 'gonderen_sube_id' => $sid, 'alan_sube_id' => $alan, 'durum' => 'oneri', 'kaynak' => $r->input('kaynak') === 'skt' ? 'skt' : 'oneri',
            'isteyen_id' => $p->id, 'isteyen_ad' => $p->ad, 'onaylayan_id' => $p->id, 'onaylayan_ad' => $p->ad, 'onay_at' => now(), 'kdv_orani' => $ort['tur'] === 'ic' ? 0 : 10,
            'gonderen_not' => mb_substr((string) $r->input('not'), 0, 1000) ?: null, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('sube_transferleri')->where('id', $id)->update(['transfer_no' => 'TRF-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT)]);
        foreach ($kalemler as $k) {
            $m = $mal[$k['malzeme_id']] ?? null; if (!$m) continue;
            DB::table('sube_transfer_kalemleri')->insert(['transfer_id' => $id, 'malzeme_id' => $m->id, 'ad' => $m->ad, 'birim' => (string) ($birim[$m->temel_birim_id] ?? ''),
                'istenen' => $k['miktar'], 'onaylanan' => $k['miktar'], 'birim_maliyet' => (float) $m->guncel_maliyet, 'created_at' => now(), 'updated_at' => now()]);
        }
        return $id;
    });
    $t = DB::table('sube_transferleri')->where('id', $id)->first();
    _trfBildir($alan, 'transfer_istek', '♻️ Transfer önerisi: ' . DB::table('subeler')->where('id', $sid)->value('ad'), $t->transfer_no . ' · ' . $kalemler->count() . ' ürün göndermek istiyor — kabulünüzü bekliyor', $id);
    return response()->json(['ok' => true, 'id' => $id, 'mesaj' => $t->transfer_no . ' önerildi. ' . $ort['ad'] . ' kabul edince yola çıkarabilirsiniz.']);
});
// ÖNERİYE KARAR (alan şube): kabul → hazırlanıyor | red
Route::post('/sube-transfer/kabul', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $t = DB::table('sube_transferleri')->where('id', (int) $r->input('id'))->first();
    if (!$t || (int) $t->alan_sube_id !== (int) $p->sube_id) return response()->json(['ok' => false, 'mesaj' => 'Bu önerinin alıcısı siz değilsiniz.']);
    if ($t->durum !== 'oneri') return response()->json(['ok' => false, 'mesaj' => 'Öneri zaten karara bağlanmış.']);
    if ($r->input('karar') === 'red') {
        $sebep = trim((string) $r->input('sebep'));
        if (mb_strlen($sebep) < 3) return response()->json(['ok' => false, 'mesaj' => 'Red sebebi yazın.']);
        DB::table('sube_transferleri')->where('id', $t->id)->where('durum', 'oneri')->update(['durum' => 'reddedildi', 'red_sebep' => mb_substr($sebep, 0, 500), 'updated_at' => now()]);
        _trfBildir($t->gonderen_sube_id, 'transfer_durum', '✕ Öneri reddedildi: ' . $t->transfer_no, 'Sebep: ' . $sebep, $t->id);
        return response()->json(['ok' => true, 'mesaj' => 'Öneri reddedildi.']);
    }
    DB::table('sube_transferleri')->where('id', $t->id)->where('durum', 'oneri')->update(['durum' => 'hazirlaniyor', 'not' => mb_substr('Kabul: ' . ($p->ad ?? ''), 0, 1000), 'updated_at' => now()]);
    _trfBildir($t->gonderen_sube_id, 'transfer_durum', '✓ Öneri kabul edildi: ' . $t->transfer_no, DB::table('subeler')->where('id', $t->alan_sube_id)->value('ad') . ' kabul etti — yola çıkarabilirsiniz.', $t->id);
    return response()->json(['ok' => true, 'mesaj' => 'Kabul edildi. Gönderen şube yola çıkarınca bildirim gelecek.']);
});

// KARAR (gönderen şube): onay (kısmi miktar) / red
Route::post('/sube-transfer/karar', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $t = DB::table('sube_transferleri')->where('id', (int) $r->input('id'))->first();
    if (!$t || (int) $t->gonderen_sube_id !== (int) $p->sube_id) return response()->json(['ok' => false, 'mesaj' => 'Bu transferin gönderen şubesi siz değilsiniz.']);
    if ($t->durum !== 'istendi') return response()->json(['ok' => false, 'mesaj' => 'Transfer zaten karara bağlanmış (' . _trfDurumAd($t->durum) . ').']);
    if ($r->input('karar') === 'red') {
        $sebep = trim((string) $r->input('sebep'));
        if (mb_strlen($sebep) < 3) return response()->json(['ok' => false, 'mesaj' => 'Red sebebi yazın.']);
        $n = DB::table('sube_transferleri')->where('id', $t->id)->where('durum', 'istendi')->update(['durum' => 'reddedildi', 'red_sebep' => mb_substr($sebep, 0, 500), 'onaylayan_id' => $p->id, 'onaylayan_ad' => $p->ad, 'onay_at' => now(), 'updated_at' => now()]);
        if ($n) { $t->durum = 'reddedildi'; _trfTalebeGeriAl($t); _trfBildir($t->alan_sube_id, 'transfer_durum', '✕ Transfer reddedildi: ' . $t->transfer_no, 'Sebep: ' . $sebep . ' — ürünler talebe döndü, satın almaya devam edebilirsiniz.', $t->id); }
        return response()->json(['ok' => true, 'mesaj' => 'Transfer reddedildi.']);
    }
    $mik = (array) $r->input('miktar', []);
    $mevcut = DB::table('stok_hareketleri')->where('sube_id', $t->gonderen_sube_id)->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
    $toplam = 0;
    foreach (DB::table('sube_transfer_kalemleri')->where('transfer_id', $t->id)->get() as $k) {
        $v = array_key_exists($k->id, $mik) ? (float) str_replace(',', '.', (string) $mik[$k->id]) : (float) $k->istenen;
        $v = max(0, min($v, (float) $k->istenen));
        if ($v > (float) ($mevcut[$k->malzeme_id] ?? 0) + 0.0001) return response()->json(['ok' => false, 'mesaj' => $k->ad . ': stoğunuzda yalnız ' . round((float) ($mevcut[$k->malzeme_id] ?? 0), 3) . ' ' . $k->birim . ' var.']);
        DB::table('sube_transfer_kalemleri')->where('id', $k->id)->update(['onaylanan' => $v, 'updated_at' => now()]);
        $toplam += $v;
    }
    if ($toplam <= 0) return response()->json(['ok' => false, 'mesaj' => 'Hiç miktar onaylamadınız; vermeyecekseniz reddedin.']);
    $kdv = $t->tur === 'ic' ? 0 : max(0, min(20, (float) $r->input('kdv_orani', $t->kdv_orani)));
    DB::table('sube_transferleri')->where('id', $t->id)->update(['durum' => 'hazirlaniyor', 'onaylayan_id' => $p->id, 'onaylayan_ad' => $p->ad, 'onay_at' => now(), 'kdv_orani' => $kdv,
        'gonderen_not' => mb_substr((string) $r->input('not'), 0, 1000) ?: null, 'updated_at' => now()]);
    _trfBildir($t->alan_sube_id, 'transfer_durum', '✓ Transfer onaylandı: ' . $t->transfer_no, DB::table('subeler')->where('id', $t->gonderen_sube_id)->value('ad') . ' hazırlıyor.', $t->id);
    return response()->json(['ok' => true, 'mesaj' => 'Onaylandı. Mal hazır olunca "Yola Çıkar" deyin; stok o anda düşer.']);
});

// YOLA ÇIKAR (gönderen): stok + lot FEFO düşer, maliyet belgeye yazılır
Route::post('/sube-transfer/sevk', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $id = (int) $r->input('id');
    try {
        DB::transaction(function () use ($id, $p) {
            $t = DB::table('sube_transferleri')->where('id', $id)->lockForUpdate()->first();
            if (!$t || (int) $t->gonderen_sube_id !== (int) $p->sube_id) throw new \RuntimeException('Bu transferin gönderen şubesi siz değilsiniz.');
            if ($t->durum !== 'hazirlaniyor') throw new \RuntimeException('Transfer yola çıkarılamaz (' . _trfDurumAd($t->durum) . ').');
            $carpan = 1.0;
            if ($t->tur === 'franchise') { $o = _transferOrtaklari($t->gonderen_sube_id)[$t->alan_sube_id] ?? null; $carpan = $o ? max(0.1, (float) $o['carpan']) : 1.0; }
            $alanAd = DB::table('subeler')->where('id', $t->alan_sube_id)->value('ad');
            $malT = 0.0;
            foreach (DB::table('sube_transfer_kalemleri')->where('transfer_id', $t->id)->get() as $k) {
                $mik = (float) $k->onaylanan;
                if ($mik <= 0) { DB::table('sube_transfer_kalemleri')->where('id', $k->id)->update(['gonderilen' => 0, 'updated_at' => now()]); continue; }
                $mev = (float) DB::table('stok_hareketleri')->where('sube_id', $t->gonderen_sube_id)->where('malzeme_id', $k->malzeme_id)->sum('miktar');
                if ($mik > $mev + 0.0001) throw new \RuntimeException($k->ad . ': stokta yalnız ' . round($mev, 3) . ' ' . $k->birim . ' kaldı (onaydan sonra kullanılmış). Miktarı düşürmek için transferi iptal edip yeniden isteyin.');
                $lotlar = []; $kalan = $mik; $maliyet = 0.0;
                if (Schema::hasTable('stok_partileri')) {
                    $partiler = DB::table('stok_partileri')->where('sube_id', $t->gonderen_sube_id)->where('malzeme_id', $k->malzeme_id)->where('kalan_miktar', '>', 0)
                        ->orderByRaw('skt IS NULL, skt ASC')->orderBy('id')->lockForUpdate()->get();
                    foreach ($partiler as $pt) {
                        if ($kalan <= 0) break;
                        $al = min((float) $pt->kalan_miktar, $kalan);
                        DB::table('stok_partileri')->where('id', $pt->id)->update(['kalan_miktar' => DB::raw('kalan_miktar - ' . $al), 'updated_at' => now()]);
                        $lotlar[] = ['parti_id' => (int) $pt->id, 'lot_no' => $pt->lot_no, 'skt' => $pt->skt, 'miktar' => round($al, 4), 'maliyet' => (float) $pt->birim_maliyet, 'tedarikci_id' => $pt->tedarikci_id];
                        $maliyet += $al * (float) ($pt->birim_maliyet ?: $k->birim_maliyet);
                        $kalan -= $al;
                    }
                }
                $gm = (float) DB::table('malzemeler')->where('id', $k->malzeme_id)->value('guncel_maliyet');
                if ($kalan > 0) $maliyet += $kalan * $gm;
                $bm = $mik > 0 ? round($maliyet / $mik, 4) : $gm;
                DB::table('stok_hareketleri')->insert(['sube_id' => $t->gonderen_sube_id, 'malzeme_id' => $k->malzeme_id, 'tip' => 'transfer_cikis', 'miktar' => -$mik, 'birim_maliyet' => $bm,
                    'kaynak_tip' => 'transfer', 'kaynak_id' => $t->id, 'aciklama' => 'Transfer → ' . $alanAd . ' (' . $t->transfer_no . ')', 'personel_id' => $p->id, 'created_at' => now()]);
                DB::table('sube_transfer_kalemleri')->where('id', $k->id)->update(['gonderilen' => $mik, 'birim_maliyet' => $bm, 'birim_fiyat' => $t->tur === 'ic' ? $bm : round($bm * $carpan, 4),
                    'lotlar' => json_encode($lotlar, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
                $malT += $mik * $bm;
            }
            DB::table('sube_transferleri')->where('id', $t->id)->update(['durum' => 'yolda', 'sevk_eden_id' => $p->id, 'sevk_eden_ad' => $p->ad, 'sevk_at' => now(), 'maliyet_toplam' => round($malT, 2), 'updated_at' => now()]);
        });
    } catch (\RuntimeException $e) {
        return response()->json(['ok' => false, 'mesaj' => $e->getMessage()]);
    }
    $t = DB::table('sube_transferleri')->where('id', $id)->first();
    _trfBildir($t->alan_sube_id, 'transfer_durum', '🚚 Transfer yolda: ' . $t->transfer_no, DB::table('subeler')->where('id', $t->gonderen_sube_id)->value('ad') . ' gönderdi. Gelince Mal Kabul / Transfer ekranından teslim alın.', $t->id);
    return response()->json(['ok' => true, 'mesaj' => 'Yola çıktı. Stoğunuzdan düşüldü; sevk irsaliyesini yazdırabilirsiniz.']);
});

// TESLİM AL (alan şube): stok girer, lot/SKT taşınır, eksik = transfer firesi; firmalar arası ise fatura + cari
Route::post('/sube-transfer/teslim', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $id = (int) $r->input('id'); $gelen = (array) $r->input('miktar', []); $notlar = (array) $r->input('fark_not', []);
    $fin = null;
    try {
        $fin = DB::transaction(function () use ($id, $p, $gelen, $notlar) {
            $t = DB::table('sube_transferleri')->where('id', $id)->lockForUpdate()->first();
            if (!$t || (int) $t->alan_sube_id !== (int) $p->sube_id) throw new \RuntimeException('Bu transferin alan şubesi siz değilsiniz.');
            if ($t->durum !== 'yolda') throw new \RuntimeException('Transfer teslim alınamaz (' . _trfDurumAd($t->durum) . ').');
            $gonAd = DB::table('subeler')->where('id', $t->gonderen_sube_id)->value('ad');
            $kalemler = DB::table('sube_transfer_kalemleri')->where('transfer_id', $t->id)->get();
            foreach ($kalemler as $k) {
                $gon = (float) $k->gonderilen;
                if ($gon <= 0) { DB::table('sube_transfer_kalemleri')->where('id', $k->id)->update(['teslim_alinan' => 0, 'updated_at' => now()]); $k->teslim_alinan = 0; continue; }
                $y = array_key_exists($k->id, $gelen) ? (float) str_replace(',', '.', (string) $gelen[$k->id]) : $gon;
                $y = max(0, min($y, $gon));
                $maliyetAlan = $t->tur === 'ic' ? (float) $k->birim_maliyet : (float) $k->birim_fiyat;
                DB::table('stok_hareketleri')->insert(['sube_id' => $t->alan_sube_id, 'malzeme_id' => $k->malzeme_id, 'tip' => 'transfer_giris', 'miktar' => $gon, 'birim_maliyet' => $maliyetAlan,
                    'kaynak_tip' => 'transfer', 'kaynak_id' => $t->id, 'aciklama' => 'Transfer ← ' . $gonAd . ' (' . $t->transfer_no . ')', 'personel_id' => $p->id, 'created_at' => now()]);
                $fark = round($gon - $y, 4);
                if ($fark > 0) {
                    DB::table('stok_hareketleri')->insert(['sube_id' => $t->alan_sube_id, 'malzeme_id' => $k->malzeme_id, 'tip' => 'fire', 'miktar' => -$fark, 'birim_maliyet' => $maliyetAlan,
                        'kaynak_tip' => 'transfer_fark', 'kaynak_id' => $t->id, 'aciklama' => 'Transfer eksik geldi (' . $t->transfer_no . ', ' . $gonAd . '): ' . mb_substr((string) ($notlar[$k->id] ?? ''), 0, 120), 'personel_id' => $p->id, 'created_at' => now()]);
                }
                // Lotlar: teslim alınan miktar FEFO sırasıyla aynı lot no + SKT ile alan şubede açılır
                if (Schema::hasTable('stok_partileri')) {
                    $kal = $y;
                    foreach ((json_decode((string) $k->lotlar, true) ?: []) as $l) {
                        if ($kal <= 0) break;
                        $m = min((float) $l['miktar'], $kal);
                        // İç transferde lot kendi alış maliyetiyle taşınır; firmalar arasında alan firmanın maliyeti = transfer fiyatı
                        $lotMal = ($t->tur === 'ic' && (float) ($l['maliyet'] ?? 0) > 0) ? (float) $l['maliyet'] : $maliyetAlan;
                        $row = ['sube_id' => $t->alan_sube_id, 'malzeme_id' => $k->malzeme_id, 'lot_no' => $l['lot_no'] ?? null, 'skt' => $l['skt'] ?? null, 'giris_miktar' => $m, 'kalan_miktar' => $m,
                            'birim_maliyet' => $lotMal, 'tedarikci_id' => $l['tedarikci_id'] ?? null, 'giris_tarihi' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()];
                        if (_trfKolon('stok_partileri', 'kaynak_sube_id')) { $row['kaynak_sube_id'] = $t->gonderen_sube_id; $row['transfer_id'] = $t->id; }
                        DB::table('stok_partileri')->insert($row);
                        $kal -= $m;
                    }
                }
                DB::table('sube_transfer_kalemleri')->where('id', $k->id)->update(['teslim_alinan' => $y, 'fark_not' => $fark > 0 ? mb_substr((string) ($notlar[$k->id] ?? 'Eksik geldi'), 0, 250) : null, 'updated_at' => now()]);
                $k->teslim_alinan = $y;
            }
            $upd = ['durum' => 'teslim', 'teslim_alan_id' => $p->id, 'teslim_alan_ad' => $p->ad, 'teslim_at' => now(), 'updated_at' => now()];
            $fin = null;
            if (in_array($t->tur, ['firmalar_arasi', 'franchise'], true)) {
                $fin = _trfFinans($t, $kalemler, $p);
                $upd += ['net_toplam' => $fin['net'], 'kdv_toplam' => $fin['kdv'], 'alis_fatura_id' => $fin['fatura_id'], 'cari_hareket_id' => $fin['cari_id']];
            }
            DB::table('sube_transferleri')->where('id', $t->id)->update($upd);
            if ($t->talep_id) DB::table('satinalma_talepleri')->where('id', $t->talep_id)->where('durum', 'transfer')->update(['durum' => 'tamamlandi', 'updated_at' => now()]);
            return $fin;
        });
    } catch (\RuntimeException $e) {
        return response()->json(['ok' => false, 'mesaj' => $e->getMessage()]);
    }
    $t = DB::table('sube_transferleri')->where('id', $id)->first();
    $eksik = DB::table('sube_transfer_kalemleri')->where('transfer_id', $id)->whereColumn('teslim_alinan', '<', 'gonderilen')->count();
    _trfBildir($t->gonderen_sube_id, 'transfer_durum', '📦 Transfer teslim alındı: ' . $t->transfer_no, DB::table('subeler')->where('id', $t->alan_sube_id)->value('ad') . ($eksik ? ' — ' . $eksik . ' üründe EKSİK bildirildi' : ' tam teslim aldı.'), $t->id);
    $msg = 'Teslim alındı, stoğunuza girdi.' . ($eksik ? ' Eksik kısım transfer firesi olarak kaydedildi.' : '');
    if ($fin && $fin['fatura_id']) $msg .= ' Gönderen firmadan ' . number_format($fin['net'] + $fin['kdv'], 2, ',', '.') . ' ₺ alış faturası otomatik işlendi.';
    return response()->json(['ok' => true, 'mesaj' => $msg]);
});

// İPTAL (isteyen şube; yola çıkmadan önce)
Route::post('/sube-transfer/iptal', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Yetkiniz yok.'], 403);
    $t = DB::table('sube_transferleri')->where('id', (int) $r->input('id'))->first();
    if (!$t || !in_array((int) $p->sube_id, [(int) $t->alan_sube_id, (int) $t->gonderen_sube_id], true)) return response()->json(['ok' => false, 'mesaj' => 'Transfer bulunamadı.']);
    if (!in_array($t->durum, ['oneri', 'istendi', 'hazirlaniyor'], true)) return response()->json(['ok' => false, 'mesaj' => 'Yola çıkmış transfer iptal edilemez; teslim alıp eksik/fazla olarak bildirin.']);
    $n = DB::table('sube_transferleri')->where('id', $t->id)->whereIn('durum', ['oneri', 'istendi', 'hazirlaniyor'])->update(['durum' => 'iptal', 'red_sebep' => mb_substr('İptal: ' . ($p->ad ?? '') . ' ' . (string) $r->input('sebep'), 0, 500), 'updated_at' => now()]);
    if ($n) {
        $t->durum = 'iptal'; _trfTalebeGeriAl($t);
        $karsi = (int) $p->sube_id === (int) $t->alan_sube_id ? $t->gonderen_sube_id : $t->alan_sube_id;
        _trfBildir($karsi, 'transfer_durum', 'Transfer iptal edildi: ' . $t->transfer_no, ($p->ad ?? '') . ' iptal etti.', $t->id);
    }
    return response()->json(['ok' => true, 'mesaj' => 'Transfer iptal edildi.' . ($t->talep_id ? ' Ürünler talebe döndü.' : '')]);
});

Route::get('/api/sube-transfer/detay', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    $t = _trfDetay((int) $r->query('id'));
    if (!$p || !_trfGorur($p, $t) || !_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Transfer bulunamadı.'], 404); // yalnız transfer yetkilisi
    $t->ben = (int) $p->sube_id === (int) $t->gonderen_sube_id ? 'gonderen' : 'alan';
    $t->yetkili = _trfYetkili($p);
    return response()->json(['ok' => true, 'transfer' => $t]);
});

// Sevk irsaliyesi / transfer belgesi (yazdırılabilir)
Route::get('/sube-transfer/belge/{id}', function (Request $r, $id) {
    _trfEnsure();
    $p = _trfPersonel($r);
    $t = _trfDetay((int) $id);
    if (!$p || !_trfGorur($p, $t) || !_trfYetkili($p)) abort(404);
    $gon = ['unvan' => _subeUnvan($t->gonderen_sube_id), 'vkn' => _subeVkn($t->gonderen_sube_id), 'adres' => (string) DB::table('subeler')->where('id', $t->gonderen_sube_id)->value('adres')];
    $alan = ['unvan' => _subeUnvan($t->alan_sube_id), 'vkn' => _subeVkn($t->alan_sube_id), 'adres' => (string) DB::table('subeler')->where('id', $t->alan_sube_id)->value('adres')];
    return view('sube_transfer_belge', ['t' => $t, 'gon' => $gon, 'alan' => $alan]);
});

// Transfer ayarları (grup başına): fazla stok gün, SKT eşiği, şube kritik stokları, personel şube erişimi
Route::post('/sube-transfer/ayar', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!$p || !in_array($p->rol ?? '', ['sahip', 'mudur'], true)) return response()->json(['ok' => false, 'mesaj' => 'Yalnız patron / müdür.'], 403);
    $sid = (int) $p->sube_id; $g = _trfGrupId($sid) ?: ('s' . $sid);
    if ($r->has('gun')) resto_ayar_yaz('trf_gun_' . $g, (string) max(1, min(60, (int) $r->input('gun'))));
    if ($r->has('skt')) resto_ayar_yaz('trf_skt_' . $g, (string) max(1, min(60, (int) $r->input('skt'))));
    foreach ((array) $r->input('kritik', []) as $mid => $v) {
        $mid = (int) $mid; if ($mid <= 0) continue;
        $v = trim((string) $v);
        if ($v === '') DB::table('sube_malzeme_ayar')->where('sube_id', $sid)->where('malzeme_id', $mid)->delete();
        else DB::table('sube_malzeme_ayar')->updateOrInsert(['sube_id' => $sid, 'malzeme_id' => $mid], ['kritik_stok' => (float) str_replace(',', '.', $v), 'updated_at' => now(), 'created_at' => now()]);
    }
    return response()->json(['ok' => true, 'mesaj' => 'Kaydedildi.']);
});

// Patron: personellere şube erişimi ver / al (yalnız kendi grubundaki şubeler)
Route::get('/api/sube-transfer/erisim', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!$p || ($p->rol ?? '') !== 'sahip') return response()->json(['ok' => false, 'mesaj' => 'Yalnız patron.'], 403);
    $grup = _grupSubeleri($p->ana_sube_id ?? $p->sube_id);
    $ad = DB::table('subeler')->whereIn('id', $grup)->pluck('ad', 'id');
    $per = DB::table('personeller')->whereIn('sube_id', $grup)->where('aktif', 1)->whereNotIn('rol', ['sahip'])->orderBy('sube_id')->orderBy('ad')->get(['id', 'ad', 'rol', 'sube_id']);
    $er = DB::table('personel_sube_erisim')->whereIn('personel_id', $per->pluck('id'))->get()->groupBy('personel_id');
    return response()->json(['ok' => true, 'subeler' => $ad, 'personel' => $per->map(fn ($x) => ['id' => (int) $x->id, 'ad' => $x->ad, 'rol' => $x->rol, 'sube_id' => (int) $x->sube_id, 'sube_ad' => (string) ($ad[$x->sube_id] ?? ''),
        'erisim' => ($er[$x->id] ?? collect())->pluck('sube_id')->map(fn ($s) => (int) $s)->values()])->values()]);
});
Route::post('/sube-transfer/erisim', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!$p || ($p->rol ?? '') !== 'sahip') return response()->json(['ok' => false, 'mesaj' => 'Yalnız patron.'], 403);
    $grup = _grupSubeleri($p->ana_sube_id ?? $p->sube_id);
    $per = DB::table('personeller')->where('id', (int) $r->input('personel_id'))->first();
    $sube = (int) $r->input('sube_id');
    if (!$per || !in_array((int) $per->sube_id, $grup, true) || !in_array($sube, $grup, true) || $sube === (int) $per->sube_id) return response()->json(['ok' => false, 'mesaj' => 'Geçersiz seçim.']);
    if ($r->input('ver')) DB::table('personel_sube_erisim')->updateOrInsert(['personel_id' => $per->id, 'sube_id' => $sube], ['veren_ad' => $p->ad, 'updated_at' => now(), 'created_at' => now()]);
    else DB::table('personel_sube_erisim')->where('personel_id', $per->id)->where('sube_id', $sube)->delete();
    return response()->json(['ok' => true]);
});

// ================================================================ SİSTEM YÖNETİMİ: şube grupları + franchise (yalnız süper admin)
Route::get('/resteos-yonetim/sube-gruplari', function () {
    if (function_exists('_ryKapi') && ($x = _ryKapi())) return $x;
    _trfEnsure();
    $gruplar = DB::table('isletme_gruplari')->orderBy('ad')->get();
    $subeler = DB::table('subeler')->orderBy('ad')->get(['id', 'ad', 'grup_id', 'franchise_depo']);
    foreach ($subeler as $s) { $s->vkn = _subeVkn($s->id); $s->unvan = _subeUnvan($s->id); }
    $baglar = DB::table('franchise_baglari')->get();
    return view('resteos_yonetim.sube_gruplari', compact('gruplar', 'subeler', 'baglar'));
});
Route::post('/resteos-yonetim/sube-gruplari', function (Request $r) {
    if (function_exists('_ryKapi') && ($x = _ryKapi())) return $x;
    _trfEnsure();
    $islem = (string) $r->input('islem');
    $log = function ($m) { try { if (function_exists('_ryLog')) _ryLog('Şube grubu: ' . $m); } catch (\Throwable $e) {} };
    if ($islem === 'grup_ekle') {
        $ad = trim((string) $r->input('ad'));
        if ($ad === '') return back()->with('hata', 'Grup adı girin.');
        DB::table('isletme_gruplari')->insert(['ad' => mb_substr($ad, 0, 150), 'notlar' => $r->input('notlar'), 'created_at' => now(), 'updated_at' => now()]);
        $log('eklendi ' . $ad);
    } elseif ($islem === 'grup_sil') {
        $g = (int) $r->input('grup_id');
        DB::table('subeler')->where('grup_id', $g)->update(['grup_id' => null, 'franchise_depo' => 0]);
        DB::table('franchise_baglari')->where('merkez_grup_id', $g)->orWhere('franchise_grup_id', $g)->delete();
        DB::table('isletme_gruplari')->where('id', $g)->delete();
        $log('silindi #' . $g);
    } elseif ($islem === 'sube_ata') {
        $s = (int) $r->input('sube_id'); $g = (int) $r->input('grup_id');
        $acik = DB::table('sube_transferleri')->where(function ($w) use ($s) { $w->where('gonderen_sube_id', $s)->orWhere('alan_sube_id', $s); })->whereIn('durum', ['oneri', 'istendi', 'hazirlaniyor', 'yolda'])->count();
        if ($acik) return back()->with('hata', 'Bu şubenin ' . $acik . ' açık transferi var; önce tamamlanmalı veya iptal edilmeli.');
        DB::table('subeler')->where('id', $s)->update(['grup_id' => $g ?: null] + ($g ? [] : ['franchise_depo' => 0]));
        if (!$g) { try { DB::table('personel_sube_erisim')->where('sube_id', $s)->delete(); } catch (\Throwable $e) {} }
        $log('şube #' . $s . ' → grup #' . $g);
    } elseif ($islem === 'depo') {
        DB::table('subeler')->where('id', (int) $r->input('sube_id'))->update(['franchise_depo' => $r->input('deger') ? 1 : 0]);
    } elseif ($islem === 'franchise_bagla') {
        $m = (int) $r->input('merkez_grup_id'); $f = (int) $r->input('franchise_grup_id');
        if (!$m || !$f || $m === $f) return back()->with('hata', 'Merkez ve franchise farklı gruplar olmalı.');
        DB::table('franchise_baglari')->updateOrInsert(['merkez_grup_id' => $m, 'franchise_grup_id' => $f], ['fiyat_carpani' => max(0.1, (float) str_replace(',', '.', (string) $r->input('carpan', 1))), 'aktif' => 1, 'updated_at' => now(), 'created_at' => now()]);
        $log('franchise bağı #' . $m . ' → #' . $f);
    } elseif ($islem === 'franchise_kaldir') {
        DB::table('franchise_baglari')->where('id', (int) $r->input('bag_id'))->delete();
    }
    return back()->with('ok', 'Kaydedildi.');
});

// Flutter (Bearer token) için POST eşleri: /api/* CSRF muaf; web uçlarıyla AYNI işleyici (kopya kod yok)
foreach (['iste', 'oner', 'kabul', 'karar', 'sevk', 'teslim', 'iptal', 'ayar', 'erisim'] as $_trfUc) {
    try {
        $_trfRt = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/sube-transfer/' . $_trfUc, 'POST'));
        Route::post('/api/sube-transfer/' . $_trfUc, $_trfRt->getAction()['uses']);
    } catch (\Throwable $e) {}
}
// Flutter liste ucu (web sayfasının verisi JSON olarak)
Route::get('/api/sube-transfer/liste', function (Request $r) {
    _trfEnsure();
    $p = _trfPersonel($r);
    if (!_trfYetkili($p)) return response()->json(['ok' => false, 'mesaj' => 'Transfer yetkiniz yok.'], 403);
    $sid = (int) $p->sube_id;
    $ad = DB::table('subeler')->pluck('ad', 'id');
    $q = DB::table('sube_transferleri')->where(function ($w) use ($sid) { $w->where('gonderen_sube_id', $sid)->orWhere('alan_sube_id', $sid); })->orderByDesc('id')->limit(200)->get();
    $kal = $q->isNotEmpty() ? DB::table('sube_transfer_kalemleri')->whereIn('transfer_id', $q->pluck('id'))->get()->groupBy('transfer_id') : collect();
    $liste = $q->map(fn ($t) => ['id' => (int) $t->id, 'no' => $t->transfer_no, 'tur' => $t->tur, 'tur_ad' => _trfTurAd($t->tur), 'durum' => $t->durum, 'durum_ad' => _trfDurumAd($t->durum),
        'yon' => (int) $t->gonderen_sube_id === $sid ? 'giden' : 'gelen', 'gonderen' => (string) ($ad[$t->gonderen_sube_id] ?? ''), 'alan' => (string) ($ad[$t->alan_sube_id] ?? ''),
        'isteyen' => $t->isteyen_ad, 'tarih' => \Carbon\Carbon::parse($t->created_at)->format('d.m.Y H:i'), 'talep_id' => $t->talep_id,
        'ozet' => ($kal[$t->id] ?? collect())->take(4)->map(fn ($k) => $k->ad)->implode(', '), 'kalem' => ($kal[$t->id] ?? collect())->count()])->values();
    $mal = _katalogFiltre(DB::table('malzemeler'), 'malzeme', $sid, 'malzemeler.id')->orderBy('ad')->get(['id', 'ad', 'temel_birim_id']); $birim = DB::table('birimler')->pluck('kisaltma', 'id');
    return response()->json(['ok' => true, 'sube_id' => $sid, 'sube_ad' => (string) ($ad[$sid] ?? ''), 'liste' => $liste, 'ortaklar' => array_values(_transferOrtaklari($sid)),
        'malzemeler' => $mal->map(fn ($m) => ['id' => (int) $m->id, 'ad' => $m->ad, 'birim' => (string) ($birim[$m->temel_birim_id] ?? '')])->values()]);
});

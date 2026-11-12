<?php
// GEÇİCİ WA/RFQ TEŞHİSİ (salt-okunur, yalnız sahip): Fiyat Araştır WhatsApp'ı neden gitmedi. İş bitince dosya + require silinecek.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/wa-teshis', function () {
    $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {}
    if (!$p || ($p->rol ?? '') !== 'sahip') abort(404);
    $sid = (int) $p->sube_id;
    $o = ['sube' => $sid, 'sidecar_var' => resto_ayar_al('wa_sidecar_url', '') !== '', 'token_var' => resto_ayar_al('wa_servis_token', '') !== ''];
    try { $o['kontor_yeterli'] = \App\Services\KontorServisi::yeterliMi($sid); $o['kontorlu'] = \App\Services\KontorServisi::kontorluDonemMi($sid); $o['bakiye'] = \App\Services\KontorServisi::bakiye($sid); } catch (\Throwable $e) { $o['kontor_hata'] = $e->getMessage(); }
    try { $o['tedarikciler'] = DB::table('tedarikciler')->where('sube_id', $sid)->get(['id', 'ad', 'telefon', 'email', 'aktif', 'anlasmali'])
        ->map(function ($t) { $t->tel_norm = function_exists('_waTelNorm') ? _waTelNorm($t->telefon) : null;
            $t->katalog = DB::table('tedarikci_malzeme')->where('tedarikci_id', $t->id)->where('aktif', 1)->count(); return $t; }); } catch (\Throwable $e) { $o['ted_hata'] = $e->getMessage(); }
    try { $o['hatlar'] = DB::table('wa_hatlar')->where('sube_id', $sid)->get(); } catch (\Throwable $e) { $o['hat_hata'] = $e->getMessage(); }
    $o['satinalma_key'] = function_exists('_waHatAnahtari') ? _waHatAnahtari($sid, 'satinalma') : null;
    try { $o['bridge_durum'] = _waBridge('GET', '/session/' . $o['satinalma_key'] . '/status'); } catch (\Throwable $e) { $o['bridge_hata'] = $e->getMessage(); }
    try { $o['rfq'] = DB::table('rfq_gonderim')->where('sube_id', $sid)->orderByDesc('id')->limit(6)->get(['id', 'talep_id', 'tedarikci_id', 'kanal', 'durum', 'created_at']); } catch (\Throwable $e) {}
    try { $o['wa_log'] = DB::table('wa_mesaj_loglari')->where('sube_id', $sid)->orderByDesc('id')->limit(8)->get(['id', 'telefon', 'yon', 'durum', 'hata', 'created_at']); } catch (\Throwable $e) {}
    return response()->json($o, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

<?php
// GEÇİCİ RFQ KATALOG TEŞHİSİ (salt-okunur, yalnız sahip): talep kalemleri hangi tedarikçiye bağlı. İş bitince dosya + require silinecek.
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/rfq-teshis', function () {
    $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {}
    if (!$p || ($p->rol ?? '') !== 'sahip') abort(404);
    $o = [];
    $o['tedarikciler'] = DB::table('tedarikciler')->get(['id', 'ad', 'aktif', 'anlasmali'])->map(function ($t) {
        $t->tel = DB::table('tedarikciler')->where('id', $t->id)->value('telefon') ? 'var' : 'yok';
        $t->katalog = DB::table('tedarikci_malzeme')->where('tedarikci_id', $t->id)->where('aktif', 1)->count(); return $t; });
    $o['talep12'] = DB::table('talep_kalemleri')->where('talep_id', 12)->get(['ad', 'malzeme_id'])->map(function ($k) {
        $k->tedarikciler = DB::table('tedarikci_malzeme')->where('malzeme_id', $k->malzeme_id)->get(['tedarikci_id', 'aktif']); return $k; });
    $o['rfq'] = DB::table('rfq_gonderim')->orderByDesc('id')->limit(3)->get(['id', 'talep_id', 'tedarikci_id', 'kanal', 'kalemler_json']);
    return response()->json($o, 200, [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

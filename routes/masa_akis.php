<?php
// ============================================================================
// MASA AKIŞI ortak kuralları: QR sipariş → mutfak/bar (KDS) → "servise hazır" garsona → servis → ödeme → hesap kapanışı.
// - Bar ayrımı: şubede bar istasyonu (bar departmanı/barmen) varsa içecekler mutfak ekranına düşmez, bar seçilince görünür.
// - KDS fişi = adisyon + tur: 2. tur ayrı fiş; Başla/Hazır yalnız o fişin kalemlerine (kalem_ids) uygulanır.
// - Ödenmiş ama mutfağı bitmemiş adisyon KDS'de kalır (ön ödeme / erken ödeme; kapanıştan sonra 3 saat).
// - "Servise hazır": masa çağrısı + o masanın garsonuna push; masada hazır kalem kalmayınca çağrı kapanır.
// - QR ödeme: tutar ikram/iskonto düşülmüş net; kalem ayırma atomik (iki telefon aynı kalemi ödeyemez);
//   kapanışta stok/CRM/mali fiş + bekleyen çağrı/ödeme temizliği (kasadan kapatınca da).
// ============================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_kdsBarVar')) {
    // Şubede ayrı bar istasyonu var mı (bar departmanı ya da bar rolünde aktif personel). Yoksa içecekler eskisi gibi mutfakta görünür.
    function _kdsBarVar($sid)
    {
        return cache()->remember('kds_bar_var_' . (int) $sid, 120, function () use ($sid) {
            try {
                if (Schema::hasTable('departmanlar') && DB::table('departmanlar')->where('sube_id', (int) $sid)->where('kod', 'bar')->exists()) return true;
                return DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->whereIn('rol', ['bar', 'barmen', 'barista'])->exists();
            } catch (\Throwable $e) { return false; }
        });
    }
    // Bar ürünleri: istasyonu 'bar' olanlar + istasyonu girilmemiş/varsayılan kalmış içecekler (ad ya da kategori adından)
    function _kdsBarUrunIds($sid)
    {
        return cache()->remember('kds_bar_urun_' . (int) $sid, 60, function () use ($sid) {
            $q = DB::table('urunler')->where('urunler.sube_id', (int) $sid);
            $katVar = Schema::hasTable('menu_kategorileri');
            if ($katVar) $q->leftJoin('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id');
            $rows = $q->get(array_merge(['urunler.id', 'urunler.ad', 'urunler.istasyon'], $katVar ? ['menu_kategorileri.ad as kat'] : []));
            $ids = [];
            foreach ($rows as $u) {
                $ist = (string) ($u->istasyon ?? '');
                if ($ist === 'bar') { $ids[] = (int) $u->id; continue; }
                if ($ist !== '' && $ist !== 'mutfak') continue; // başka istasyona elle atanmış
                $kat = mb_strtolower((string) ($u->kat ?? ''), 'UTF-8');
                if (preg_match('/içecek|icecek|meşrubat|mesrubat|kahve|bar\b|kokteyl|alkol|soğuk içe|sıcak içe/u', $kat)
                    || (function_exists('_mutfakIstasyonTahmin') && _mutfakIstasyonTahmin(' ' . $u->ad) === 'bar')) $ids[] = (int) $u->id;
            }
            return $ids;
        });
    }
    function _kdsBarPersoneliMi($p)
    {
        if (!$p) return false;
        if (in_array($p->rol ?? '', ['bar', 'barmen', 'barista'], true)) return true;
        try { return function_exists('_departmanBriefKod') && _departmanBriefKod($p, (int) $p->sube_id) === 'bar'; } catch (\Throwable $e) { return false; }
    }
    // Ekranın istasyonu: boş/'hepsi' → bar personeli için 'bar', diğerleri için 'hepsi' (= bar hariç tüm mutfak)
    function _kdsIstasyonCoz($p, $ist)
    {
        $ist = preg_replace('/[^a-z_]/', '', (string) $ist);
        if ($ist === '' || $ist === 'hepsi') return ($p && _kdsBarPersoneliMi($p)) ? 'bar' : 'hepsi';
        return $ist;
    }
    // Kalem sorgusunu istasyona daralt ('tum' = daraltma yok)
    function _kdsIstasyonKapsamla($q, $sid, $ist)
    {
        if ($ist === 'tum') return $q;
        if ($ist === 'bar') return $q->whereIn('adisyon_kalemleri.urun_id', _kdsBarUrunIds($sid) ?: [0]);
        $bar = _kdsBarVar($sid) ? _kdsBarUrunIds($sid) : []; // bar istasyonu yoksa içecekler mutfakta kalır (onları yapan biri olmalı)
        if ($ist === 'hepsi') {
            if ($bar) $q->where(fn ($w) => $w->whereNotIn('adisyon_kalemleri.urun_id', $bar)->orWhereNull('adisyon_kalemleri.urun_id'));
            return $q;
        }
        $ids = DB::table('urunler')->where('sube_id', (int) $sid)
            ->where(fn ($w) => $ist === 'mutfak' ? $w->where('istasyon', 'mutfak')->orWhereNull('istasyon') : $w->where('istasyon', $ist))->pluck('id')->all();
        if ($ist === 'mutfak' && $bar) $ids = array_values(array_diff($ids, $bar));
        return $q->where(function ($w) use ($ids, $ist) {
            $w->whereIn('adisyon_kalemleri.urun_id', $ids ?: [0]);
            if ($ist === 'mutfak') $w->orWhereNull('adisyon_kalemleri.urun_id');
        });
    }
    // KDS'de görünen adisyonlar: açık + son 3 saatte ödenip kapanmış (mutfağı bitmemiş olabilir: ön ödeme / erken ödeme)
    function _kdsAdisyonKosulu($q)
    {
        return $q->where(function ($w) {
            $w->where('adisyonlar.durum', 'acik')
              ->orWhere(fn ($x) => $x->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', now()->subHours(3)));
        });
    }
    function _kdsKalemIdleri(Request $r)
    {
        $v = $r->input('kalem_ids');
        if (is_string($v)) $v = explode(',', $v);
        return array_values(array_filter(array_map('intval', (array) $v)));
    }
    // KDS işleminin hedef kalemleri: kalem_ids (fiş) > adisyon_id (+istasyon) > kalem_id. Şube dışı kalem asla.
    function _kdsHedef(Request $r, $p, $sid, $istasyonUygula = true)
    {
        $q = _mutfakKapsamla(DB::table('adisyon_kalemleri'), $p)
            ->whereIn('adisyon_kalemleri.adisyon_id', fn ($s) => $s->select('id')->from('adisyonlar')->where('sube_id', (int) $sid));
        $ids = _kdsKalemIdleri($r);
        if ($ids) return $q->whereIn('adisyon_kalemleri.id', $ids);
        if ($r->filled('adisyon_id')) {
            $q->where('adisyon_kalemleri.adisyon_id', (int) $r->input('adisyon_id'));
            if ($istasyonUygula || $r->filled('istasyon')) _kdsIstasyonKapsamla($q, $sid, _kdsIstasyonCoz($p, $r->input('istasyon')));
            return $q;
        }
        return $q->where('adisyon_kalemleri.id', (int) $r->input('kalem_id'));
    }
    // "Yemekten sonra getirilsin" notlu kalem, ana yemekle AYNI basışta işlenmez (kart Başla/Hazır tatlıyı da başlatıyordu).
    // Ana yemek hazır olunca kartta yalnız tatlı kalır -> o zaman Başla/Hazır ona uygulanır. Tek kaleme basış her zaman geçer.
    function _kdsSonraAyikla(array $ids)
    {
        if (count($ids) < 2) return $ids;
        $notlar = DB::table('adisyon_kalemleri')->whereIn('id', $ids)->pluck('not', 'id')->all();
        $sonra = array_keys(array_filter($notlar, fn ($n) => mb_stripos((string) $n, 'sonra getir') !== false));
        if (!$sonra || count($sonra) === count($notlar)) return $ids;
        return array_values(array_diff($ids, $sonra));
    }
    function _siparisCagriKapat(array $kalemIds, $kim)
    {
        try {
            if (!$kalemIds || !Schema::hasTable('masa_cagrilari')) return;
            $masalar = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')->whereIn('adisyon_kalemleri.id', $kalemIds)
                ->whereNotNull('adisyonlar.masa_id')->distinct()->pluck('adisyonlar.masa_id')->all();
            if ($masalar) DB::table('masa_cagrilari')->whereIn('masa_id', $masalar)->where('tip', 'siparis')->where('durum', 'bekliyor')->update(_cagriKapat($kim));
        } catch (\Throwable $e) {}
    }
    function _kdsBasla(Request $r, $p, $sid)
    {
        if (function_exists('_kdsKolonEnsure')) _kdsKolonEnsure();
        $upd = ['durum' => 'hazirlaniyor', 'updated_at' => now()];
        if (Schema::hasColumn('adisyon_kalemleri', 'basla_zamani')) $upd['basla_zamani'] = now();
        if (function_exists('_mrKdsDamga')) $upd = _mrKdsDamga($upd, $p);
        $ids = _kdsSonraAyikla(_kdsHedef($r, $p, $sid)->where('adisyon_kalemleri.durum', 'gonderildi')->pluck('adisyon_kalemleri.id')->all());
        if (!$ids) return 0;
        $n = DB::table('adisyon_kalemleri')->whereIn('id', $ids)->where('durum', 'gonderildi')->update($upd);
        _siparisCagriKapat($ids, 'Mutfak aldı');
        return $n;
    }
    // Hazır: yalnız pişen kalemler (iptal/servis edilmiş kalem dirilmez) → garsona "servise hazır"
    function _kdsHazir(Request $r, $p, $sid)
    {
        $ids = _kdsSonraAyikla(_kdsHedef($r, $p, $sid)->whereIn('adisyon_kalemleri.durum', ['gonderildi', 'hazirlaniyor'])->pluck('adisyon_kalemleri.id')->all());
        if (!$ids) return 0;
        $upd = ['durum' => 'hazir', 'updated_at' => now()];
        if (Schema::hasColumn('adisyon_kalemleri', 'hazir_zamani')) $upd['hazir_zamani'] = now();
        if (function_exists('_mrKdsDamga')) $upd = _mrKdsDamga($upd, $p);
        DB::table('adisyon_kalemleri')->whereIn('id', $ids)->whereIn('durum', ['gonderildi', 'hazirlaniyor'])->update($upd);
        _siparisCagriKapat($ids, 'Mutfak hazırladı');
        _servisHazirBildir($sid, $ids);
        return count($ids);
    }
    // Servis edildi (garson aldı): hazır kalemler → servis (kim servis etti yazılır); masada hazır kalmadıysa çağrı kapanır
    function _kdsServis(Request $r, $p, $sid)
    {
        $ids = _kdsHedef($r, $p, $sid, false)->where('adisyon_kalemleri.durum', 'hazir')->pluck('adisyon_kalemleri.id')->all();
        foreach ($ids as $id) _kalemServisIsaretle($id, $p);
        return count($ids);
    }
}

if (!function_exists('_servisHazirBildir')) {
    // "Servise hazır": masa başına tek bekleyen çağrı (tazelenir) + o masanın garsonuna push ("Masa 32 · servise hazır: 2× Adana, Ayran")
    function _servisHazirBildir($sid, array $kalemIds)
    {
        if (!$kalemIds) return;
        try {
            if (function_exists('_masaCagriEnsure')) _masaCagriEnsure();
            $acanVar = Schema::hasColumn('adisyonlar', 'acan_personel_id');
            $rows = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')
                ->whereIn('adisyon_kalemleri.id', $kalemIds)->where('adisyonlar.sube_id', (int) $sid)->whereNotNull('adisyonlar.masa_id')
                ->get(array_merge(['adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.adet', 'adisyonlar.id as adisyon_id', 'adisyonlar.masa_id', 'masalar.ad as masa', 'masalar.bolge_id'],
                    $acanVar ? ['adisyonlar.acan_personel_id'] : []));
            foreach ($rows->groupBy('masa_id') as $masaId => $grup) {
                $ilk = $grup->first();
                $masaAd = $ilk->masa ?: ('Masa ' . $masaId);
                $var = DB::table('masa_cagrilari')->where('masa_id', $masaId)->where('tip', 'servise_hazir')->where('durum', 'bekliyor')->value('id');
                // Detay: masada servis bekleyen (hazır) bütün ürünler -> "Margarita, Kola"
                $hazirIds = _kdsAdisyonKosulu(DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                    ->where('adisyonlar.masa_id', $masaId)->where('adisyon_kalemleri.durum', 'hazir'))->pluck('adisyon_kalemleri.id')->all();
                $det = function_exists('_cagriDetay') ? _cagriDetay($hazirIds ?: $grup->pluck('id')->all()) : [];
                if ($var) DB::table('masa_cagrilari')->where('id', $var)->update(['created_at' => now()] + $det);
                else DB::table('masa_cagrilari')->insert(['sube_id' => (int) $sid, 'masa_id' => $masaId, 'tip' => 'servise_hazir', 'durum' => 'bekliyor', 'created_at' => now()] + $det);
                $parca = $grup->map(fn ($k) => ((float) $k->adet > 1 ? rtrim(rtrim(number_format((float) $k->adet, 2, ',', ''), '0'), ',') . '× ' : '') . $k->urun_adi)->values();
                $govde = $parca->take(4)->implode(', ') . ($parca->count() > 4 ? ' ve ' . ($parca->count() - 4) . ' ürün daha' : '') . ' — hemen masaya götürün.';
                $baslik = '🛎️ ' . $masaAd . ' · servise hazır';
                $data = ['tur' => 'servise_hazir', 'masa_id' => (int) $masaId, 'adisyon_id' => (int) $ilk->adisyon_id];
                $hedef = _masaGarsonlari($sid, (int) $masaId, $ilk->bolge_id ?? null, $acanVar ? ($ilk->acan_personel_id ?? null) : null);
                // Masanın garsonu yoksa / mesaide değilse servis ekibi (mesaidekiler) — eskiden yalnız 'garson' rolüne gidiyordu, servis departmanı kaçıyordu
                if (function_exists('_fcmPersoneleGonder')) _fcmPersoneleGonder((int) $sid, $hedef ?: _servisEkibi($sid), $baslik, $govde, $data);
            }
        } catch (\Throwable $e) {}
    }
    // Masanın garson(lar)ı: masaya/bölgesine atanmış aktif personel + adisyonu açan garson. Boşsa null (tüm garsonlara gider).
    function _masaGarsonlari($sid, $masaId, $bolgeId = null, $acanId = null)
    {
        $ids = [];
        try {
            if (Schema::hasTable('personel_atama')) {
                $ids = DB::table('personel_atama')->where('sube_id', (int) $sid)
                    ->where(function ($w) use ($masaId, $bolgeId) {
                        $w->where(fn ($x) => $x->where('tip', 'masa')->where('hedef_id', $masaId));
                        if ($bolgeId) $w->orWhere(fn ($x) => $x->where('tip', 'bolge')->where('hedef_id', $bolgeId));
                    })->pluck('personel_id')->map(fn ($x) => (int) $x)->all();
            }
        } catch (\Throwable $e) {}
        if ($acanId) $ids[] = (int) $acanId;
        if (!$ids) return null;
        $ids = DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->whereIn('id', array_unique($ids))
            ->whereNotIn('rol', ['mutfak', 'sahip'])->pluck('id')->map(fn ($x) => (int) $x)->all();
        if (!$ids) return null;
        // Mesai kullanılıyorsa: masanın garsonu mesaide değilse (izinli / çıkmış) null → servis ekibine gider (bildirim boşa düşmez)
        try {
            if (Schema::hasTable('personel_mesai') && cache()->remember('mesai_kullanim_' . (int) $sid, 600, fn () => DB::table('personel_mesai')->where('sube_id', (int) $sid)->where('giris_at', '>', now()->subDays(7))->exists() ? 1 : 0)) {
                $ids = DB::table('personel_mesai')->where('sube_id', (int) $sid)->where('durum', 'acik')->whereIn('personel_id', $ids)->pluck('personel_id')->map(fn ($x) => (int) $x)->unique()->values()->all();
            }
        } catch (\Throwable $e) {}
        return $ids ?: null;
    }
    // Masada servis bekleyen (hazır) kalem kalmadıysa "servise hazır" çağrısını kapat
    function _servisHazirKapatKontrol($masaIds)
    {
        try {
            foreach (array_unique(array_filter(array_map('intval', (array) $masaIds))) as $mid) {
                $q = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                    ->where('adisyonlar.masa_id', $mid)->where('adisyon_kalemleri.durum', 'hazir');
                if (!_kdsAdisyonKosulu($q)->exists()) {
                    DB::table('masa_cagrilari')->where('masa_id', $mid)->where('tip', 'servise_hazir')->where('durum', 'bekliyor')->update(_cagriKapat('Servis edildi'));
                }
            }
        } catch (\Throwable $e) {}
    }
}

// ============================ DÖNGÜ BİLDİRİM ALICILARI ============================
// Garson–mutfak döngüsünün bildirimleri yalnız işi yapan ekibe gider (satın alma/muhasebe telefonu çalmaz).
// İşletme mesai (QR puantaj) kullanıyorsa yalnız MESAİDE olanlar; mesaide kimse yoksa ekibin tamamı (bildirim kaybolmaz).
if (!function_exists('_dongudeMesaide')) {
    function _dongudeMesaide($sid, array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return [];
        try {
            if (!Schema::hasTable('personel_mesai')) return $ids;
            $kullaniyor = cache()->remember('mesai_kullanim_' . (int) $sid, 600, fn () => DB::table('personel_mesai')->where('sube_id', (int) $sid)->where('giris_at', '>', now()->subDays(7))->exists() ? 1 : 0);
            if (!$kullaniyor) return $ids;
            $acik = DB::table('personel_mesai')->where('sube_id', (int) $sid)->where('durum', 'acik')->whereIn('personel_id', $ids)->pluck('personel_id')->map(fn ($x) => (int) $x)->unique()->values()->all();
            return $acik ?: $ids;
        } catch (\Throwable $e) { return $ids; }
    }
    // Rol ya da departman koduna göre aktif personel id'leri
    function _dongudeEkip($sid, array $roller, array $depKodlari)
    {
        try {
            $depIds = ($depKodlari && Schema::hasTable('departmanlar')) ? DB::table('departmanlar')->where('sube_id', (int) $sid)->whereIn('kod', $depKodlari)->pluck('id')->all() : [];
            return DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)
                ->where(function ($w) use ($roller, $depIds) { $w->whereIn('rol', $roller ?: ['-']); if ($depIds) $w->orWhereIn('departman_id', $depIds); })
                ->pluck('id')->map(fn ($x) => (int) $x)->all();
        } catch (\Throwable $e) { return []; }
    }
    // Servis ekibi: garson / komi / şef garson + Servis departmanı (ödeme çağrısında kasa da)
    function _servisEkibi($sid, $kasaDahil = false)
    {
        $ids = _dongudeEkip($sid, $kasaDahil ? ['garson', 'komi', 'sef_garson', 'kasa'] : ['garson', 'komi', 'sef_garson'], $kasaDahil ? ['servis', 'kasa'] : ['servis']);
        return _dongudeMesaide($sid, $ids);
    }
    // Mutfak + bar ekibi (yeni sipariş haberi)
    function _mutfakBarEkibi($sid)
    {
        return _dongudeMesaide($sid, _dongudeEkip($sid, ['mutfak', 'bar', 'barmen'], ['mutfak', 'soguk_mutfak', 'pastane', 'kasap', 'bar']));
    }
    // Döngü bildirimi: kişinin "garson_cagri" vb. tercihini kapatanlar düşer (kritik olmayan tip)
    function _dongudePush($sid, array $ids, $baslik, $govde, array $data, $tercihTip = null)
    {
        if ($tercihTip && function_exists('_bildirimTercihKapali')) $ids = array_values(array_filter($ids, fn ($id) => !_bildirimTercihKapali($id, $tercihTip)));
        if (!$ids || !function_exists('_fcmPersoneleGonder')) return false;
        try { return _fcmPersoneleGonder((int) $sid, $ids, $baslik, $govde, $data); } catch (\Throwable $e) { return false; }
    }
    // Müşteri masadan garson / hesap / acil çağırdı (QR menü + müşteri uygulaması ortak).
    // Aynı masa aynı tip için bekleyen çağrı varsa YENİ kayıt açılmaz: ilk 90 sn sessiz (art arda basma telefonu çaldırmaz),
    // sonra en fazla 90 sn'de bir "tekrar çağırıyor · N dk'dır bekliyor" hatırlatması gider. Bildirim o masanın garsonuna (yoksa servis ekibine).
    function _garsonCagriEkle($sid, $masaId, $tip, $ek = null)
    {
        $tip = in_array($tip, ['garson', 'hesap', 'acil'], true) ? $tip : 'garson';
        if (function_exists('_masaCagriEnsure')) _masaCagriEnsure();
        $masa = DB::table('masalar')->find((int) $masaId);
        if (!$masa) return ['ok' => 0];
        $masaAd = $masa->ad ?: ('Masa ' . $masa->id);
        $etiket = ['hesap' => 'Hesap istendi', 'acil' => 'Acil çağrı', 'garson' => 'Garson çağrısı'][$tip];
        $ikon = ['hesap' => '💳', 'acil' => '🔴', 'garson' => '🙋'][$tip];
        $data = ['tur' => 'garson_cagri', 'masa_id' => (int) $masa->id, 'tip' => $tip];
        $acik = DB::table('adisyonlar')->where('masa_id', $masa->id)->where('durum', 'acik')->orderByDesc('id')->first();
        $hedef = function_exists('_masaGarsonlari') ? _masaGarsonlari($sid, (int) $masa->id, $masa->bolge_id ?? null, $acik->acan_personel_id ?? null) : null;
        $alici = $hedef ?: _servisEkibi($sid, $tip === 'hesap');
        $var = DB::table('masa_cagrilari')->where('masa_id', $masa->id)->where('tip', $tip)->where('durum', 'bekliyor')->orderByDesc('id')->first();
        if ($var) {
            $gecen = (int) abs(\Carbon\Carbon::parse($var->created_at)->diffInSeconds(now()));
            $anahtar = 'cagri_hatirlat_' . $var->id;
            if ($gecen < 90 || cache()->has($anahtar)) return ['ok' => 1, 'tekrar' => 1];
            cache()->put($anahtar, 1, now()->addSeconds(90));
            _dongudePush($sid, $alici, '🔁 ' . $masaAd . ' tekrar çağırıyor', $etiket . ' · ' . max(1, intdiv($gecen, 60)) . ' dk\'dır bekliyor' . ($ek ? ' · ' . $ek : ''), $data + ['tekrar' => '1'], 'garson_cagri');
            return ['ok' => 1, 'tekrar' => 1];
        }
        DB::table('masa_cagrilari')->insert(['sube_id' => (int) $sid, 'masa_id' => (int) $masa->id, 'tip' => $tip, 'durum' => 'bekliyor', 'created_at' => now()]);
        _dongudePush($sid, $alici, $ikon . ' ' . $etiket, $masaAd . ($ek ? ' · ' . $ek : ''), $data, 'garson_cagri');
        return ['ok' => 1, 'tekrar' => 0, 'masa' => $masaAd];
    }
}

if (!function_exists('_qrAcikAdisyon')) {
    // Masanın açık adisyonu: her uçta AYNI kural (en yeni açık)
    function _qrAcikAdisyon($masaId)
    {
        return DB::table('adisyonlar')->where('masa_id', (int) $masaId)->where('durum', 'acik')->orderByDesc('id')->first();
    }
    // Ön ödemede terk edilen sepet: hiçbir kalemi ödenmemiş/mutfağa gitmemiş, 30 dk'dır dokunulmamış adisyon iptal → masa yeni müşteriye temiz açılır
    function _onOdemeTerkTemizle($masaId)
    {
        try {
            $a = _qrAcikAdisyon($masaId);
            if (!$a) return;
            $k = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal');
            if ((clone $k)->count() === 0 || (clone $k)->where('durum', '!=', 'odeme_bekliyor')->exists()) return;
            if ((clone $k)->max('created_at') > now()->subMinutes(30)) return;
            if (DB::table('odemeler')->where('adisyon_id', $a->id)->exists()) return;
            if (DB::table('odeme_islemleri')->where('adisyon_id', $a->id)->where('durum', 'bekliyor')->exists()) return;
            DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', 'odeme_bekliyor')->update(['durum' => 'iptal', 'updated_at' => now()]);
            DB::table('adisyonlar')->where('id', $a->id)->where('durum', 'acik')->update(['durum' => 'iptal', 'kapanis' => now(), 'updated_at' => now()]);
            if (!_qrAcikAdisyon($masaId)) DB::table('masalar')->where('id', $masaId)->update(['durum' => 'bos']);
        } catch (\Throwable $e) {}
    }
    // İşletmenin QR'da sunduğu ödeme yolları. Online yalnız gerçekten tamamlanabiliyorsa (sağlayıcı imzalı geri dönüşü bağlanana kadar yalnız demo/simülasyon).
    function _qrOdemeAyar($sid)
    {
        if (function_exists('_odemeEnsure')) _odemeEnsure();
        try {
            if (!Schema::hasColumn('odeme_ayarlari', 'qr_online')) Schema::table('odeme_ayarlari', fn ($t) => $t->tinyInteger('qr_online')->default(1));
            if (!Schema::hasColumn('odeme_ayarlari', 'qr_kasa')) Schema::table('odeme_ayarlari', fn ($t) => $t->tinyInteger('qr_kasa')->default(1));
        } catch (\Throwable $e) {}
        $a = DB::table('odeme_ayarlari')->where('sube_id', (int) $sid)->first();
        $online = !$a || (int) ($a->qr_online ?? 1) === 1;
        $kasa = !$a || (int) ($a->qr_kasa ?? 1) === 1;
        $calisir = _odemeSaglayici($sid) === 'simulasyon' ? _qrSimulasyonAcik($sid) : false;
        if (!$kasa && !($online && $calisir)) $kasa = true; // müşteri ödeyemez halde kalmasın
        return ['online' => $online && $calisir, 'kasa' => $kasa, 'online_secili' => $online];
    }
    // Simülasyonla online ödeme (demo): yalnız demo hesabı / demo uçları açık ortam
    function _qrSimulasyonAcik($sid)
    {
        if (filter_var(env('RESTEOS_DEMO_UCLARI', false), FILTER_VALIDATE_BOOLEAN)) return true;
        try { return (int) DB::table('subeler')->where('id', (int) $sid)->value('demo_hesabi') === 1; } catch (\Throwable $e) { return false; }
    }
    // Ödenecek NET tutar: ikram kalemler düşülür, adisyon iskontosu orantılı dağıtılır; açık kalan tüm kalemler seçildiyse tam kalan (kuruş farkı kalmaz)
    // İskonto oranı: ikram dışı kalemlerin ne kadarı ödenecek (toplam / ikram dışı brüt). Müşteri ekranında kalem başı net için de kullanılır.
    function _qrNetOran($a)
    {
        $araNet = (float) DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')
            ->where(fn ($w) => Schema::hasColumn('adisyon_kalemleri', 'ikram') ? $w->where('ikram', 0)->orWhereNull('ikram') : $w)->sum('tutar');
        return $araNet > 0 ? max(0, min(1, (float) $a->toplam / $araNet)) : 0;
    }
    function _qrOdenecekTutar($a, $kalemler)
    {
        $tum = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->get();
        $ikram = fn ($k) => (int) ($k->ikram ?? 0) === 1;
        $oran = _qrNetOran($a);
        $tutar = round((float) collect($kalemler)->reject($ikram)->sum('tutar') * $oran, 2);
        $kalan = max(0, round((float) $a->toplam - (float) DB::table('odemeler')->where('adisyon_id', $a->id)->sum('tutar'), 2));
        $acikSay = $tum->filter(fn ($k) => ($k->odeme_durum ?? 'acik') !== 'odendi')->count();
        if ($acikSay > 0 && count($kalemler) >= $acikSay) return $kalan;
        return min($tutar, $kalan);
    }
    // Kalemleri bu ödemeye ATOMİK ayır: başka telefon/kasa aynı anda ayırdıysa hiçbirini alma
    function _qrKalemRezerve(array $ids, $token)
    {
        $n = DB::table('adisyon_kalemleri')->whereIn('id', $ids)->where('odeme_durum', 'acik')->update(['odeme_durum' => 'beklemede', 'odeme_token' => $token]);
        if ($n === count($ids)) return true;
        DB::table('adisyon_kalemleri')->whereIn('id', $ids)->where('odeme_token', $token)->update(['odeme_durum' => 'acik', 'odeme_token' => null]);
        return false;
    }
    // Tüm kalemler ödendiyse adisyonu kapat (atomik) + stok/CRM/mali fiş + çağrı/ödeme temizliği. Kapattıysa true.
    function _qrKapanisKontrol($adisyonId, $personelId = null)
    {
        $a = DB::table('adisyonlar')->find((int) $adisyonId);
        if (!$a || $a->durum !== 'acik') return false;
        $acikKalan = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->where('odeme_durum', '!=', 'odendi')->count();
        if ($acikKalan > 0) return false;
        $claim = DB::table('adisyonlar')->where('id', $a->id)->where('durum', 'acik')->update(['durum' => 'odendi', 'kapanis' => now(), 'updated_at' => now()]);
        if (!$claim) return false;
        try { _restoStokTuket($a->id, $a->sube_id, $personelId); } catch (\Throwable $e) {}
        try { _adisyonKapanisIsle($a->id); } catch (\Throwable $e) {}
        _adisyonKapanisTemizle($a->id);
        return true;
    }
    // Hesap kapandı (QR ya da kasa): bekleyen ödeme talepleri iptal, kalemler ödendi, masa boş, masadaki bekleyen çağrılar kapanır
    // (servise hazır çağrısı yalnız masada servis bekleyen kalem kalmadıysa kapanır).
    function _adisyonKapanisTemizle($adisyonId)
    {
        try {
            $a = DB::table('adisyonlar')->find((int) $adisyonId);
            if (!$a) return;
            if (Schema::hasTable('odeme_islemleri')) {
                $tok = DB::table('odeme_islemleri')->where('adisyon_id', $a->id)->where('durum', 'bekliyor')->pluck('token')->all();
                if ($tok) DB::table('odeme_islemleri')->whereIn('token', $tok)->update(['durum' => 'iptal']);
            }
            if (Schema::hasColumn('adisyon_kalemleri', 'odeme_durum')) {
                DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('durum', '!=', 'iptal')->where('odeme_durum', '!=', 'odendi')
                    ->update(['odeme_durum' => 'odendi', 'odeme_token' => null]);
            }
            if ($a->masa_id && !_qrAcikAdisyon($a->masa_id)) {
                DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'bos']);
                if (Schema::hasTable('masa_cagrilari')) {
                    DB::table('masa_cagrilari')->where('masa_id', $a->masa_id)->where('durum', 'bekliyor')->where('tip', '!=', 'servise_hazir')->update(_cagriKapat('Ödeme alındı'));
                }
                _servisHazirKapatKontrol([$a->masa_id]);
            }
        } catch (\Throwable $e) {}
    }
}

// ============================================================================
// QR SİPARİŞİN MUTFAK/BAR FİŞİ: QR'da araya tablet girmediği için fiş, restorandaki yazıcılı cihaz tarafından basılır.
// QR'dan gelen (ve ön ödemede ödenip mutfağa düşen) kalem "fis_bekliyor=1" olur. Cihaz /api/mutfak/fis-al ile alır (=2, kiralama),
// basınca /api/mutfak/fis-sonuc basarili=1 (=0). Basamazsa ya da 90 sn içinde dönmezse kalem tekrar sıraya girer:
// iki cihaz aynı fişi basmaz, yazıcıya ulaşamayan cihaz fişi kaybettirmez. Garsonun girdiği kalemi garsonun cihazı zaten basar (0 kalır).
// Fiş yalnız O AN gelen kalemleri basar; başlığında aynı adisyon no + "2. TUR".
// ============================================================================
if (!function_exists('_fisKolonEnsure')) {
    function _fisKolonEnsure()
    {
        if (cache()->get('fis_kolon_ok1')) return;
        try {
            if (!Schema::hasColumn('adisyon_kalemleri', 'fis_bekliyor')) Schema::table('adisyon_kalemleri', fn ($t) => $t->tinyInteger('fis_bekliyor')->default(0)->index());
            if (!Schema::hasColumn('adisyon_kalemleri', 'fis_alan_at')) Schema::table('adisyon_kalemleri', fn ($t) => $t->timestamp('fis_alan_at')->nullable());
            cache()->put('fis_kolon_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
    function _qrFisIsaretle(array $kalemIds)
    {
        if (!$kalemIds) return;
        _fisKolonEnsure();
        try { DB::table('adisyon_kalemleri')->whereIn('id', $kalemIds)->update(['fis_bekliyor' => 1, 'fis_alan_at' => null]); } catch (\Throwable $e) {}
    }
    // Adisyonun kaçıncı gönderim turu (aynı dakikada gönderilenler aynı tur; KDS ile aynı kural)
    function _adisyonTurNo($adisyonId, $zaman)
    {
        if (!$zaman) return 1;
        $hedef = \Carbon\Carbon::parse($zaman)->format('Y-m-d H:i');
        $gzler = DB::table('adisyon_kalemleri')->where('adisyon_id', (int) $adisyonId)->whereNotNull('gonderim_zamani')->where('durum', '!=', 'iptal')
            ->pluck('gonderim_zamani')->map(fn ($z) => \Carbon\Carbon::parse($z)->format('Y-m-d H:i'))->unique()->sort()->values()->all();
        $i = array_search($hedef, $gzler, true);
        return $i === false ? max(1, count($gzler)) : $i + 1;
    }
}

// Yazıcılı cihaz: basılacak QR fişlerini al (kiralar). Adisyon + tur başına bir fiş; kalemlerin istasyonu (bar ayrı yazıcıya gider).
Route::post('/api/mutfak/fis-al', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _fisKolonEnsure();
    $sid = (int) $p->sube_id;
    $subeAd = fn ($q) => $q->whereIn('adisyon_id', fn ($s) => $s->select('id')->from('adisyonlar')->where('sube_id', $sid));
    // süresi dolan kiralar (cihaz basamadan kapandı / ağ koptu) sıraya geri döner
    $subeAd(DB::table('adisyon_kalemleri'))->where('fis_bekliyor', 2)->where('fis_alan_at', '<', now()->subSeconds(90))->update(['fis_bekliyor' => 1]);
    // çok eski bekleyenler basılmaz (dün kalmış kalem yazıcıdan çıkmasın)
    $subeAd(DB::table('adisyon_kalemleri'))->where('fis_bekliyor', 1)->where('created_at', '<', now()->subHours(3))->update(['fis_bekliyor' => 0]);
    $adaylar = $subeAd(DB::table('adisyon_kalemleri'))->where('fis_bekliyor', 1)->whereIn('durum', ['gonderildi', 'hazirlaniyor'])->orderBy('id')->limit(60)->pluck('id')->all();
    $alinan = [];
    foreach ($adaylar as $id) { // atomik: aynı kalemi iki cihaz alamaz
        if (DB::table('adisyon_kalemleri')->where('id', $id)->where('fis_bekliyor', 1)->update(['fis_bekliyor' => 2, 'fis_alan_at' => now()])) $alinan[] = $id;
    }
    if (!$alinan) return ['ok' => 1, 'fisler' => []];
    $barIds = array_flip(_kdsBarUrunIds($sid));
    $rows = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
        ->leftJoin('masalar', 'adisyonlar.masa_id', '=', 'masalar.id')->leftJoin('urunler', 'adisyon_kalemleri.urun_id', '=', 'urunler.id')
        ->whereIn('adisyon_kalemleri.id', $alinan)->orderBy('adisyon_kalemleri.id')
        ->get(['adisyon_kalemleri.id', 'adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi', 'adisyon_kalemleri.adet', 'adisyon_kalemleri.not',
            'adisyon_kalemleri.gonderim_zamani', 'adisyonlar.id as adisyon_id', 'adisyonlar.kanal', 'masalar.ad as masa', 'urunler.istasyon']);
    $fisler = [];
    foreach ($rows as $k) {
        $tur = _adisyonTurNo($k->adisyon_id, $k->gonderim_zamani);
        $key = $k->adisyon_id . '-' . $tur;
        if (!isset($fisler[$key])) $fisler[$key] = ['adisyon_id' => (int) $k->adisyon_id, 'tur' => $tur, 'masa' => $k->masa ?: ucfirst((string) $k->kanal),
            'kaynak' => 'QR', 'saat' => $k->gonderim_zamani ? \Carbon\Carbon::parse($k->gonderim_zamani)->format('d.m H:i') : now()->format('d.m H:i'), 'kalemler' => []];
        $not = trim(preg_replace('/^QR sipariş( \(ön ödeme\))?(\s*·\s*)?/u', '', (string) $k->not)); // "QR sipariş · Yemekten sonra" -> "Yemekten sonra"
        $fisler[$key]['kalemler'][] = ['id' => (int) $k->id, 'ad' => $k->urun_adi, 'adet' => (float) $k->adet, 'not' => $not,
            'istasyon' => isset($barIds[(int) $k->urun_id]) ? 'bar' : ($k->istasyon ?: 'mutfak')];
    }
    return ['ok' => 1, 'fisler' => array_values($fisler)];
});
// Basım sonucu: basarili=1 → basıldı (bir daha gelmez); 0 → sıraya geri (başka cihaz/ sonraki deneme basar)
Route::post('/api/mutfak/fis-sonuc', function (Request $r) {
    $p = _apiPersonel($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _fisKolonEnsure();
    $ids = _kdsKalemIdleri($r);
    if (!$ids) return ['ok' => 0];
    DB::table('adisyon_kalemleri')->whereIn('id', $ids)->where('fis_bekliyor', 2)
        ->whereIn('adisyon_id', fn ($s) => $s->select('id')->from('adisyonlar')->where('sube_id', (int) $p->sube_id))
        ->update(['fis_bekliyor' => $r->boolean('basarili') ? 0 : 1, 'fis_alan_at' => null]);
    return ['ok' => 1];
});

// Müşteri mutfağa GİTMİŞ ürün için not verdi ("künefeyi yemekten sonra getirin", "pizza mısırsız olsun"): yeni ürün eklenmez,
// o masanın açık adisyonundaki henüz hazırlanmamış kalemin notuna eklenir. tumu=1 (alerji): masanın servis edilmemiş BÜTÜN kalemlerine.
Route::post('/api/qr/kalem-not', function (Request $r) {
    if ($_g = _qrKapiZorunlu($r)) return $_g;
    $not = mb_substr(trim(strip_tags((string) $r->input('not'))), 0, 120);
    $a = _qrAcikAdisyon((int) $r->input('masa'));
    if (!$a || $not === '') return ['ok' => 0];
    if ($r->boolean('tumu')) {
        if (function_exists('_notAlerjileri') && ($yeniAl = _notAlerjileri($not))) {
            // Alerji masa alerjisine eklenir (garson ekranında görünür, sonraki her kaleme geçer); kalemlerde tek "ALERJİ:" parçası olur
            [, , $n] = _adisyonAlerjiAyarla($a, _alerjiListe(array_merge(_adisyonAlerjiler($a->id), $yeniAl)));
            return ['ok' => 1, 'adet' => $n];
        }
        $n = 0;
        foreach (DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->whereIn('durum', ['odeme_bekliyor', 'gonderildi', 'hazirlaniyor', 'hazir'])->get(['id', 'not']) as $k) {
            if (mb_stripos((string) $k->not, $not) !== false) continue;
            DB::table('adisyon_kalemleri')->where('id', $k->id)->update(['not' => mb_substr(trim((string) $k->not) !== '' ? trim((string) $k->not) . ' · ' . $not : $not, 0, 250), 'updated_at' => now()]);
            $n++;
        }
        return ['ok' => 1, 'adet' => $n];
    }
    $k = DB::table('adisyon_kalemleri')->where('adisyon_id', $a->id)->where('urun_id', (int) $r->input('urun_id'))
        ->whereIn('durum', ['odeme_bekliyor', 'gonderildi', 'hazirlaniyor'])->orderByDesc('id')->first();
    if (!$k) return ['ok' => 0, 'hazir' => 1]; // hazırlanmış/servis edilmiş: not anlamsız
    if (mb_stripos((string) $k->not, $not) === false) {
        DB::table('adisyon_kalemleri')->where('id', $k->id)->update(['not' => mb_substr(trim((string) $k->not) !== '' ? trim((string) $k->not) . ' · ' . $not : $not, 0, 250), 'updated_at' => now()]);
    }
    return ['ok' => 1, 'ad' => $k->urun_adi];
});

// ===== TEST SIFIRLAMA (patron/müdür): şubenin bütün AÇIK hesaplarını iptal eder, masaları boşaltır, çağrıları temizler =====
// Ödenmiş/kapanmış hesaplar, menü, masalar, QR'lar ve AI konuşma kayıtları KORUNUR. Temiz baştan test için.
if (!function_exists('_testSifirlaOzet')) {
    // Açık hesaplar: şube kaydı ya da bu şubenin MASASI (eski demo kayıtlarında sube_id farklı kalmış olabilir; masalar ekranı masaya göre gösterir)
    function _testSifirlaAcik($sid)
    {
        $masaIds = DB::table('masalar')->where('sube_id', $sid)->pluck('id')->all();
        return DB::table('adisyonlar')->where('durum', 'acik')->where(function ($q) use ($sid, $masaIds) { $q->where('sube_id', $sid); if ($masaIds) $q->orWhereIn('masa_id', $masaIds); });
    }
    function _testSifirlaOzet($sid)
    {
        $acik = _testSifirlaAcik($sid);
        return [
            'adisyon' => (clone $acik)->count(),
            'tutar' => (float) (clone $acik)->sum('toplam'),
            'masa' => DB::table('masalar')->where('sube_id', $sid)->where('durum', '!=', 'bos')->count(),
            'cagri' => Schema::hasTable('masa_cagrilari') ? DB::table('masa_cagrilari')->where('sube_id', $sid)->count() : 0,
        ];
    }
}
Route::get('/masalar-test-sifirla', function () {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!$bp) return redirect('/pos-giris');
    if (!in_array(($bp->rol ?? ''), ['sahip', 'mudur'], true)) return redirect('/pos')->with('hata', 'Bu işlem yalnız patron/müdür içindir.');
    return view('masalar_test_sifirla', ['ozet' => _testSifirlaOzet((int) $bp->sube_id), 'sonuc' => session('sonuc')]);
});
Route::post('/masalar-test-sifirla', function (Request $r) {
    $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
    if (!$bp || !in_array(($bp->rol ?? ''), ['sahip', 'mudur'], true)) return redirect('/pos-giris');
    if ($r->input('onay') !== 'SIFIRLA') return redirect('/masalar-test-sifirla')->with('hata', 'Onay için kutuya SIFIRLA yazın.');
    $sid = (int) $bp->sube_id; // masalar ekranı (/pos) ile aynı şube
    $once = _testSifirlaOzet($sid);
    DB::transaction(function () use ($sid) {
        $ids = _testSifirlaAcik($sid)->pluck('id')->all();
        foreach (array_chunk($ids, 500) as $parca) {
            $kUpd = ['durum' => 'iptal', 'updated_at' => now()];
            if (Schema::hasColumn('adisyon_kalemleri', 'fis_bekliyor')) $kUpd['fis_bekliyor'] = 0; // yazıcı eski fişi basmasın
            DB::table('adisyon_kalemleri')->whereIn('adisyon_id', $parca)->where('durum', '!=', 'iptal')->update($kUpd);
            DB::table('adisyonlar')->whereIn('id', $parca)->update(['durum' => 'iptal', 'updated_at' => now()]);
            if (Schema::hasTable('odeme_islemleri')) DB::table('odeme_islemleri')->whereIn('adisyon_id', $parca)->where('durum', 'bekliyor')->update(['durum' => 'iptal']);
        }
        DB::table('masalar')->where('sube_id', $sid)->update(['durum' => 'bos']);
        if (Schema::hasTable('masa_cagrilari')) DB::table('masa_cagrilari')->where('sube_id', $sid)->delete();
    });
    try { if (function_exists('_aktiviteYaz')) _aktiviteYaz($sid, $bp->id, 'test_sifirla', 'Test sıfırlama: ' . $once['adisyon'] . ' açık hesap iptal'); } catch (\Throwable $e) {}
    return redirect('/masalar-test-sifirla')->with('sonuc', $once);
});

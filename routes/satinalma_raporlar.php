<?php
// ===== SATIN ALMA — RAPORLAR & ANALİZLER =====
// Dönemsel (günlük/haftalık/aylık) yönetim raporları + analiz kütüphanesi + AI dönem özeti.
// Kural: veri raporları YORUMSUZ sistem verisidir; boş kalan her bölüm nedenini yazar (ne eksik / neden gerekir / hangi ekrandan dolar).
// Excel/PDF motoru Fiyat Radarı ile ortak (_fr2Xlsx, fiyat_radari_rapor görünümü). routes/fiyat_radari.php sonunda require edilir.

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_srDonem')) {
    // Dönem: hazır seçenekler + özel aralık. Önceki dönem = aynı uzunlukta hemen öncesi (karşılaştırma için)
    function _srDonem($tip, $bas = null, $bit = null)
    {
        $t = today();
        $ad = ['bugun' => 'Bugün', 'dun' => 'Dün', 'bu_hafta' => 'Bu hafta', 'gecen_hafta' => 'Geçen hafta', 'bu_ay' => 'Bu ay', 'gecen_ay' => 'Geçen ay',
            'son30' => 'Son 30 gün', 'son90' => 'Son 90 gün', 'bu_yil' => 'Bu yıl', 'ozel' => 'Özel aralık'];
        if (!isset($ad[$tip])) $tip = 'bu_ay';
        switch ($tip) {
            case 'bugun': [$b, $e] = [$t->copy(), $t->copy()]; break;
            case 'dun': [$b, $e] = [$t->copy()->subDay(), $t->copy()->subDay()]; break;
            case 'bu_hafta': [$b, $e] = [$t->copy()->startOfWeek(), $t->copy()]; break;
            case 'gecen_hafta': [$b, $e] = [$t->copy()->startOfWeek()->subWeek(), $t->copy()->startOfWeek()->subDay()]; break;
            case 'gecen_ay': [$b, $e] = [$t->copy()->startOfMonth()->subMonthNoOverflow(), $t->copy()->startOfMonth()->subDay()]; break;
            case 'son30': [$b, $e] = [$t->copy()->subDays(29), $t->copy()]; break;
            case 'son90': [$b, $e] = [$t->copy()->subDays(89), $t->copy()]; break;
            case 'bu_yil': [$b, $e] = [$t->copy()->startOfYear(), $t->copy()]; break;
            case 'ozel':
                try { $b = \Carbon\Carbon::parse($bas)->startOfDay(); $e = \Carbon\Carbon::parse($bit)->startOfDay(); } catch (\Throwable $x) { $b = $t->copy()->startOfMonth(); $e = $t->copy(); }
                if ($e->lt($b)) [$b, $e] = [$e, $b];
                if ($e->gt($t)) $e = $t->copy();
                if ($b->diffInDays($e) > 730) $b = $e->copy()->subDays(730);
                break;
            default: [$b, $e] = [$t->copy()->startOfMonth(), $t->copy()];
        }
        $gun = (int) $b->diffInDays($e) + 1;
        $etiket = $tip === 'ozel' || $gun > 1 ? $b->format('d.m.Y') . ' – ' . $e->format('d.m.Y') : $b->format('d.m.Y');
        // Önceki dönem: ay/yıl seçimlerinde takvim karşılığı (Ekim → Eylül 1–30), diğerlerinde aynı uzunlukta hemen öncesi
        if ($tip === 'gecen_ay') [$ob, $oe] = [$b->copy()->subMonthNoOverflow()->startOfMonth(), $b->copy()->subMonthNoOverflow()->endOfMonth()];
        elseif ($tip === 'bu_ay') [$ob, $oe] = [$b->copy()->subMonthNoOverflow(), $e->copy()->subMonthNoOverflow()];
        elseif ($tip === 'bu_yil') [$ob, $oe] = [$b->copy()->subYear(), $e->copy()->subYear()];
        else [$ob, $oe] = [$b->copy()->subDays($gun), $b->copy()->subDay()];
        return ['tip' => $tip, 'ad' => $ad[$tip], 'bas' => $b->toDateString(), 'bit' => $e->toDateString(), 'gun' => $gun, 'etiket' => $etiket,
            'obas' => $ob->toDateString(), 'obit' => $oe->toDateString()];
    }
}

if (!function_exists('_srMeta')) {
    // İsim haritaları (istek başına bir kez)
    function _srMeta()
    {
        static $m = null;
        if ($m) return $m;
        $m = ['malz' => DB::table('malzemeler')->get(['id', 'ad', 'kategori_id', 'temel_birim_id', 'kritik_stok', 'guncel_maliyet'])->keyBy('id'),
            'kat' => Schema::hasTable('malzeme_kategorileri') ? DB::table('malzeme_kategorileri')->pluck('ad', 'id') : collect(),
            'birim' => DB::table('birimler')->pluck('kisaltma', 'id'), 'ted' => DB::table('tedarikciler')->pluck('ad', 'id')];
        return $m;
    }
    function _srTed($id) { $m = _srMeta(); return $id ? ($m['ted'][$id] ?? 'Silinmiş tedarikçi') : 'Tedarikçisiz'; }
    function _srMalz($id) { $m = _srMeta(); return $m['malz'][$id]->ad ?? '—'; }
    function _srKat($id) { $m = _srMeta(); $k = $m['malz'][$id]->kategori_id ?? null; return $k ? ($m['kat'][$k] ?? 'Kategorisiz') : 'Kategorisiz'; }
    function _srTablo($t) { static $c = []; return $c[$t] ??= Schema::hasTable($t); }
    function _srKolon($t, $k) { static $c = []; return $c[$t . '.' . $k] ??= (_srTablo($t) && Schema::hasColumn($t, $k)); }
}

if (!function_exists('_srAlis')) {
    // Alış faturası kalemleri [bas, bit] (dahil). Önceki-alış fiyatı için $geri gün öncesinden de okur.
    function _srAlis($sid, $bas, $bit, $geri = 0)
    {
        if (!_srTablo('alis_faturalari') || !_srTablo('alis_fatura_kalemleri')) return collect();
        $b0 = $geri ? \Carbon\Carbon::parse($bas)->subDays($geri)->toDateString() : $bas;
        return DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'k.fatura_id', '=', 'f.id')
            ->where('f.sube_id', $sid)->whereDate('f.tarih', '>=', $b0)->whereDate('f.tarih', '<=', $bit)->where('k.birim_fiyat', '>', 0)
            ->orderBy('f.tarih')->orderBy('k.id')
            ->get(['k.id', 'k.malzeme_id', 'k.alis_birim_id', 'k.miktar', 'k.birim_fiyat', 'k.satir_toplam', 'f.id as fatura_id', 'f.fatura_no', 'f.tarih', 'f.tedarikci_id'])
            ->map(function ($l) { $l->tarih = substr((string) $l->tarih, 0, 10); $l->tutar = (float) ($l->satir_toplam ?: $l->miktar * $l->birim_fiyat); return $l; });
    }
    function _srToplam($sat) { return round($sat->sum('tutar'), 2); }
    function _srYuzde($yeni, $eski) { return $eski > 0 ? round(($yeni - $eski) / $eski * 100, 1) : null; }
}

if (!function_exists('_srVeriDurumu')) {
    // Modül kullanımı (rozetler + boş-neden metinleri için): son 365 gün kayıt sayıları
    function _srVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString(); $d = [];
        $say = function ($t, $tarihKol, $ek = null) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t)->where('sube_id', $sid); if ($tarihKol && _srKolon($t, $tarihKol)) $q->whereDate($tarihKol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d['fatura'] = $say('alis_faturalari', 'tarih');
        $d['siparis'] = $say('satinalma_siparisleri', 'tarih', fn ($q) => $q->where('durum', '!=', 'taslak'));
        $d['malkabul'] = $say('mal_kabuller', 'tarih');
        $d['malkabul_sip'] = $say('mal_kabuller', 'tarih', fn ($q) => $q->whereNotNull('siparis_id'));
        $d['talep'] = $say('satinalma_talepleri', 'created_at');
        $d['rfq'] = $say('rfq_gonderim', 'created_at');
        $d['parti'] = $say('stok_partileri', null, fn ($q) => $q->whereNotNull('skt'));
        $d['fire'] = $say('stok_hareketleri', 'created_at', fn ($q) => $q->whereIn('tip', ['fire', 'iade']));
        $d['tuketim'] = $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('tip', 'tuketim'));
        $d['sayim'] = $say('sayimlar', 'tarih');
        $d['butce'] = $say('satinalma_butce', null);
        $d['cari'] = _srTablo('cari_hesaplar') ? (int) DB::table('cari_hesaplar')->where('sube_id', $sid)->where(function ($q) { $q->where('tip', 'tedarikci'); if (_srKolon('cari_hesaplar', 'tedarikci_id')) $q->orWhereNotNull('tedarikci_id'); })->count() : 0;
        $d['recete'] = _srTablo('receteler') ? (int) DB::table('receteler')->whereNotNull('urun_id')->count() : 0;
        $d['sube'] = _srTablo('subeler') ? (int) DB::table('subeler')->count() : 1;
        return $d;
    }
}

if (!function_exists('_srBos')) {
    // Boş-neden metinleri (ne eksik + neden gerekir + nasıl dolar)
    function _srBos($neden, array $dn, array $d = [])
    {
        $donem = $dn['etiket'];
        $m = [
            'fatura' => 'Seçili dönemde (' . $donem . ') alış faturası kaydı yok. Bu bölüm alış faturalarındaki kalemlerden (malzeme, miktar, fiyat, tedarikçi) hesaplanır. Dolması için alışları Satın Alma > Mal Kabul / İrsaliye ya da Satın Alma > Fatura OCR (AI) ekranından girin; ya da daha uzun bir dönem seçin.',
            'siparis_yok' => 'İşletme sipariş modülünü kullanmıyor: son 1 yılda sistemde gönderilmiş satın alma siparişi yok. Bu bölüm siparişteki beklenen teslim tarihi ve sipariş miktarı/fiyatı ile çalışır. Dolması için alımları Satın Alma > Sipariş İşlemleri\'nden sipariş açarak yapın.',
            'siparis' => 'Seçili dönemde (' . $donem . ') bu ölçüte giren sipariş yok.',
            'mk_bagsiz' => 'Mal kabuller siparişe bağlanmıyor: son 1 yılda siparişe bağlı mal kabul kaydı yok. Teslim performansı, siparişteki beklenen tarih ile mal kabul tarihi karşılaştırılarak hesaplanır. Dolması için siparişle gelen malı Satın Alma > Mal Kabul / İrsaliye\'de ilgili siparişi seçerek teslim alın.',
            'mk' => 'Seçili dönemde (' . $donem . ') siparişe bağlı mal kabul yok.',
            'red' => 'Seçili dönemde (' . $donem . ') mal kabulde reddedilen ya da eksik gelen ürün yok (mal kabul ' . ($d['malkabul'] ?? 0) . ' kayıt/yıl). Red kaydı mal kabul ekranında "red miktarı" ve sebebi girilince oluşur.',
            'talep_yok' => 'İşletme satın alma talep modülünü kullanmıyor: son 1 yılda talep kaydı yok. Bu bölüm departmanların açtığı taleplerden hesaplanır. Dolması için ihtiyaçları Satın Alma > Satın Alma Talepleri\'nden talep olarak açın.',
            'talep' => 'Seçili dönemde (' . $donem . ') bu ölçüte giren talep yok.',
            'rfq_yok' => 'Teklif (RFQ) modülü kullanılmıyor: son 1 yılda tedarikçilere teklif isteği gönderilmemiş. Teklif analizi, gönderilen isteklere gelen fiyatlardan yapılır. Dolması için Satın Alma Talepleri\'nden tedarikçilere teklif isteği gönderin.',
            'parti_yok' => 'Lot / son kullanma tarihi (SKT) takibi yapılmıyor: stokta SKT\'li parti kaydı yok. SKT riski, mal kabulde girilen lot ve SKT bilgisinden hesaplanır. Dolması için Mal Kabul\'de ürünlerin SKT\'sini girin.',
            'skt' => 'Önümüzdeki 30 gün içinde SKT\'si dolan ya da dolmuş, stokta kalan parti yok.',
            'fire_yok' => 'Fire / iade / sayım kaydı yok. Bu bölüm stok hareketlerindeki fire ve iade kayıtları ile sayım farklarından hesaplanır. Dolması için fireleri Stok ekranından fire olarak düşün, dönemsel sayımı Satın Alma > Sayım (Envanter)\'den yapın.',
            'fire' => 'Seçili dönemde (' . $donem . ') fire, iade ya da sayım farkı kaydı yok.',
            'tuketim_yok' => 'Stok tüketimi kaydı yok: satışlar reçete üzerinden stoktan düşmüyor. Devir hızı, tüketim ile mevcut stok oranından hesaplanır. Dolması için ürünlere reçete tanımlayın (Menü > Reçeteler); satış olunca malzeme otomatik düşer.',
            'butce_yok' => 'Satın alma bütçesi tanımlanmamış. Bütçe gerçekleşme, aylık bütçe ile harcamanın karşılaştırılmasıdır. Dolması için Satın Alma > Satınalma Bütçe\'den aylık bütçe girin.',
            'cari_yok' => 'Tedarikçi cari hesabı yok. Borç tablosu tedarikçi cari hareketlerinden (alış borcu, ödeme) hesaplanır. Dolması için alış faturalarını onaylayın (tedarikçi carisine borç yazılır) ve ödemeleri cariye işleyin.',
            'recete_yok' => 'Ürün reçetesi tanımlı değil. Menüye zam etkisi, reçetedeki malzeme miktarları ile alış fiyat değişiminden hesaplanır. Dolması için Menü > Reçeteler\'den ürünlere reçete girin.',
            'recete' => 'Seçili dönemde reçetelerdeki malzemelerin alış fiyatı değişmemiş (ya da bu malzemeler dönemde alınmamış).',
            'tek_sube' => 'Tek şube var (ya da yalnız kendi şubenizi görme yetkiniz var). Şubeler arası fiyat farkı en az 2 şubenin aynı malzemeyi alması ile hesaplanır.',
            'cok_sube' => 'Seçili dönemde birden çok şubenin ortak aldığı malzeme yok.',
            'degisim' => 'Seçili dönemde (' . $donem . ') fiyatı önceki alışa göre değişen kalem yok (ya da malzemeler dönemde ilk kez alındı; kıyas için daha önce de alınmış olması gerekir).',
            'endeks' => 'Fiyat endeksi için en az 2 ayda ortak alınan malzeme gerekir. Daha uzun bir dönem seçin (ör. Bu yıl / Son 90 gün).',
            'tek_ted' => 'Seçili dönemde her malzeme birden çok tedarikçiden ya da hiç alınmamış; tek kaynağa bağımlı malzeme yok.',
            'siparissiz' => 'Seçili dönemde siparişsiz alım yok: tüm alış faturaları siparişe bağlı mal kabulle gelmiş.',
            'uyum' => 'Seçili dönemde siparişe bağlı mal kabulle gelen ve faturası eşleşen kalem yok; sipariş fiyatı ile fatura fiyatı kıyaslanamıyor.',
            'kritik' => 'Kritik stok altında malzeme yok (ya da malzemelere kritik stok seviyesi tanımlanmamış: Stok ekranında malzeme kartına kritik stok girin).',
            'teslim_bugun' => 'Bugün teslim beklenen açık sipariş yok.',
            'geciken' => 'Geciken açık sipariş yok.',
            'onay' => 'Onay bekleyen talep yok.',
        ];
        $t = $m[$neden] ?? 'Seçili dönem için kayıt yok.';
        return $t;
    }
}

if (!function_exists('_srSiparis')) {
    // Siparişler + mal kabul eşleşmesi (teslim performansı / süreç / uyum için)
    function _srSiparisler($sid, $bas = null, $bit = null)
    {
        if (!_srTablo('satinalma_siparisleri')) return collect();
        $q = DB::table('satinalma_siparisleri')->where('sube_id', $sid)->where('durum', '!=', 'taslak');
        if ($bas) $q->whereDate('tarih', '>=', $bas); if ($bit) $q->whereDate('tarih', '<=', $bit);
        return $q->orderBy('tarih')->get();
    }
    function _srMkBySiparis($sid, array $sipIds)
    {
        if (!$sipIds || !_srTablo('mal_kabuller')) return collect();
        return DB::table('mal_kabuller')->where('sube_id', $sid)->whereIn('siparis_id', $sipIds)->orderBy('tarih')->get()->groupBy('siparis_id');
    }
    function _srAcikSiparis($sid)
    {
        if (!_srTablo('satinalma_siparisleri')) return collect();
        return DB::table('satinalma_siparisleri')->where('sube_id', $sid)->whereIn('durum', ['gonderildi', 'onaylandi', 'kismi'])->orderBy('beklenen_teslim')->get();
    }
}

if (!function_exists('_srStok')) {
    function _srStokMevcut($sid)
    {
        if (!_srTablo('stok_hareketleri')) return collect();
        return DB::table('stok_hareketleri')->where('sube_id', $sid)->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
    }
    // Alış birim fiyatını temel birime çevir (reçete/stok maliyeti için)
    function _srTemelFiyat($mid, $alisBirimId, $birimFiyat)
    {
        $m = _srMeta()['malz'][$mid] ?? null;
        if (!$m) return (float) $birimFiyat;
        $k = function_exists('_restoBirimKarsilik') ? (float) _restoBirimKarsilik($mid, $alisBirimId, $m->temel_birim_id) : 1.0;
        return $k > 0 ? (float) $birimFiyat / $k : (float) $birimFiyat;
    }
}

// =====================================================================
// RAPOR KATALOĞU — [kategori, başlık, açıklama, ikon, renk, gerekli modüller, varsayılan dönem (dönemsel raporlarda)]
// =====================================================================
if (!function_exists('_srKatalog')) {
    function _srKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Satın Alma Bülteni', 'Dünün faturaları ve fiyat değişimleri; bugün beklenen ve geciken teslimatlar, onay bekleyen talepler, red/eksik mal kabul, kritik stok, SKT yaklaşanlar, bütçe durumu.', 'today', '#7C3AED', ['fatura'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Satın Alma Raporu', 'Haftalık harcama ve önceki haftayla kıyas, günlere/kategoriye/tedarikçiye göre dağılım, en çok zamlanan/ucuzlayan kalemler, teslim performansı, talep ve teklif özeti.', 'date_range', '#4F46E5', ['fatura'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Yönetim Raporu', 'Aylık harcama ve önceki ay, bütçe gerçekleşme, fiyat endeksi, fire/sayım maliyeti, tedarikçi performansı, borç durumu, menüye zam etkisi — tek dosyada yönetim özeti.', 'calendar_month', '#9333EA', ['fatura'], 'gecen_ay'],
            'harcama' => ['harcama', 'Harcama Analizi', 'Dönem harcaması ile önceki dönemin kıyası: kategori, tedarikçi ve malzeme bazında artış/azalış.', 'payments', '#2563EB', ['fatura'], null],
            'degisim' => ['harcama', 'Fiyat Değişim Raporu', 'Dönemde alınan her kalemin bir önceki alışa göre fiyat değişimi (▲▼) ve tutar etkisi.', 'swap_vert', '#DC2626', ['fatura'], null],
            'endeks' => ['maliyet', 'Satın Alma Fiyat Endeksi', 'Sabit sepetin aylık maliyet değişimi (işletmenin kendi enflasyonu) ve endekse en çok etki eden malzemeler.', 'show_chart', '#BE185D', ['fatura'], null],
            'menu' => ['maliyet', 'Menüye Zam Etkisi', 'Malzeme fiyat değişimlerinin reçete üzerinden menü ürünlerinin maliyetine ve food cost oranına etkisi.', 'restaurant_menu', '#EA580C', ['fatura', 'recete'], null],
            'butce' => ['maliyet', 'Bütçe Gerçekleşme', 'Aylık satın alma bütçesi ile sipariş ve fatura gerçekleşmesi, sapma ve içinde bulunulan ay için ay sonu tahmini.', 'savings', '#059669', ['butce'], null],
            'teslim' => ['tedarikci', 'Tedarikçi Teslim Performansı', 'Siparişte beklenen tarih ile mal kabul tarihi: zamanında teslim oranı, ortalama gecikme, eksik teslim — tedarikçi bazında.', 'local_shipping', '#0284C7', ['siparis', 'malkabul_sip'], null],
            'red' => ['tedarikci', 'Red / Eksik Teslim Analizi', 'Mal kabulde reddedilen ve eksik gelen ürünler: tedarikçi, malzeme ve sebep bazında oranlar.', 'report_problem', '#E11D48', ['malkabul'], null],
            'uyum' => ['tedarikci', 'Sipariş–Fatura Fiyat Uyumu', 'Siparişte anlaşılan birim fiyat ile faturada kesilen fiyatın kalem kalem kıyası (3-way match).', 'rule', '#7C2D12', ['siparis', 'malkabul_sip'], null],
            'bagimlilik' => ['tedarikci', 'Tedarikçi Bağımlılığı', 'Tek tedarikçiden alınan malzemeler ve harcamanın tedarikçilere yoğunlaşması (ilk tedarikçinin payı).', 'hub', '#0F766E', ['fatura'], null],
            'surec' => ['surec', 'Talep → Teslim Süre Analizi', 'Talepten siparişe, siparişten teslime geçen gün sayıları; departman bazında süreler ve darboğaz.', 'timelapse', '#6D28D9', ['talep', 'siparis'], null],
            'departman' => ['surec', 'Departman Talep Raporu', 'Departmanların talep sayısı, acil talep oranı, durum dağılımı ve en çok istenen malzemeler.', 'groups', '#A21CAF', ['talep'], null],
            'rfq' => ['surec', 'Teklif (RFQ) Analizi', 'Gönderilen teklif istekleri, yanıt oranı, tedarikçi bazında yanıt süresi ve gelen fiyatlar.', 'request_quote', '#0369A1', ['rfq'], null],
            'skt' => ['stok', 'SKT Risk Raporu', 'Son kullanma tarihi geçmiş ya da 30 gün içinde dolacak partiler ve stokta kalan değerleri.', 'event_busy', '#B91C1C', ['parti'], null],
            'fire' => ['stok', 'Fire / İade / Sayım Farkı', 'Dönemdeki fire ve iade maliyeti, sayım farkları; malzeme ve sebep bazında.', 'delete_sweep', '#C2410C', ['fire'], null],
            'devir' => ['stok', 'Stok Devir Hızı', 'Malzeme başına dönem tüketimi, mevcut stok ve stokta kalma gün sayısı; kritik stok altındakiler.', 'autorenew', '#15803D', ['tuketim'], null],
            'borc' => ['finans', 'Tedarikçi Borç Durumu', 'Tedarikçi başına cari bakiye, son 30/60/90 gün borçlanma, son ödeme tarihi.', 'account_balance_wallet', '#1D4ED8', ['cari'], null],
            'siparissiz' => ['finans', 'Siparişsiz Alımlar', 'Siparişe bağlı mal kabul olmadan gelen faturalar: satın alma sürecinin dışında kalan harcama ve payı.', 'gpp_maybe', '#B45309', ['fatura'], null],
            'sube' => ['sube', 'Şubeler Arası Fiyat Farkı', 'Aynı malzemenin şubelere göre ortalama alış fiyatı ve en ucuz şubeye göre fark.', 'store', '#0D9488', ['fatura'], null],
        ];
    }
    function _srKategoriler()
    {
        // [ad, ikon, açıklama, renk] — sol menüde "Raporlar & Analizler" altında her biri ayrı sayfa (/satinalma-raporlar/{anahtar})
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Üst yönetime sunulan hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#4F46E5'],
            'harcama' => ['Harcama & Fiyat', 'payments', 'Ne kadar, kimden, neye harcandı; fiyatı değişen kalemler ve tutar etkisi.', '#0891B2'],
            'maliyet' => ['Maliyet & Bütçe', 'savings', 'İşletmenin kendi enflasyonu, zamların menü maliyetine etkisi ve bütçe gerçekleşmesi.', '#059669'],
            'tedarikci' => ['Tedarikçi Raporları', 'local_shipping', 'Teslim performansı, red/eksik teslim, sipariş–fatura fiyat uyumu ve tedarikçi bağımlılığı.', '#0284C7'],
            'surec' => ['Süreç & Talep', 'timelapse', 'Talepten teslime süreler, departman talepleri ve teklif (RFQ) analizi.', '#7C3AED'],
            'stok' => ['Stok & Fire', 'inventory_2', 'SKT riski, fire/iade/sayım farkları ve stok devir hızı.', '#D97706'],
            'finans' => ['Finans & Denetim', 'account_balance', 'Tedarikçi borç durumu ve siparişsiz yapılan alımlar.', '#BE123C'],
            'sube' => ['Çok Şube', 'store', 'Aynı malzemenin şubeler arasındaki alış fiyatı farkı.', '#0F766E'],
            'radar' => ['Fiyat Radarı Raporları', 'radar', 'Gizli zam, tedarikçi kıyası, hal karşılaştırması ve tedarikçi karnesi raporları.', '#DB2777'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı yönetici özeti; soru da sorabilirsiniz.', '#9333EA']];
    }
}

// =====================================================================
// ORTAK BÖLÜM ÜRETİCİLERİ (raporlar bunları birleştirir)
// =====================================================================
if (!function_exists('_srBolumler')) {
    function _srKpi($et, $deg, $fmt, $renk) { return [$et, $deg, $fmt, $renk]; }
    function _srBlok($bas, $grafik, $renk, array $kol, array $sat) { return ['baslik' => $bas, 'grafik' => $grafik, 'renk' => $renk, 'kol' => $kol, 'sat' => array_values($sat)]; }
    function _srSayfa($ad, $not, array $kol, array $sat, $bos = null) { return ['ad' => $ad, 'not' => $not, 'kol' => $kol, 'sat' => array_values($sat), 'bos' => $bos]; }

    // Fiyat değişimleri: dönemdeki her alış satırı, aynı malzemenin bir önceki alışına göre
    function _srDegisimler($sid, array $dn)
    {
        $l = _srAlis($sid, $dn['bas'], $dn['bit'], 365); $son = []; $out = [];
        foreach ($l as $x) {
            $once = $son[$x->malzeme_id] ?? null;
            if ($x->tarih >= $dn['bas'] && $once && abs($once['fiyat'] - $x->birim_fiyat) > 0.0001 && (int) $once['birim'] === (int) $x->alis_birim_id) {
                $out[] = ['malzeme' => _srMalz($x->malzeme_id), 'kategori' => _srKat($x->malzeme_id), 'birim' => _srMeta()['birim'][$x->alis_birim_id] ?? '', 'tarih' => $x->tarih,
                    'ted' => _srTed($x->tedarikci_id), 'onceki_ted' => _srTed($once['ted']), 'onceki_tarih' => $once['tarih'], 'onceki' => $once['fiyat'], 'yeni' => (float) $x->birim_fiyat,
                    'yuzde' => _srYuzde((float) $x->birim_fiyat, $once['fiyat']), 'miktar' => (float) $x->miktar, 'etki' => round(((float) $x->birim_fiyat - $once['fiyat']) * (float) $x->miktar, 2), 'fatura' => (string) ($x->fatura_no ?: '#' . $x->fatura_id)];
            }
            $son[$x->malzeme_id] = ['fiyat' => (float) $x->birim_fiyat, 'birim' => $x->alis_birim_id, 'ted' => $x->tedarikci_id, 'tarih' => $x->tarih];
        }
        return $out;
    }
    function _srDegisimSayfa(array $deg, array $dn)
    {
        return _srSayfa('Fiyat Değişimleri', 'Dönemdeki her alış, aynı malzemenin (aynı birimle) bir önceki alışına göre. Etki = (yeni − önceki) × alınan miktar.',
            [['Tarih', 't', 11], ['Malzeme', 'm', 22], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Önceki Fiyat', 'tl', 12], ['Önceki Tarih', 't', 11], ['Önceki Tedarikçi', 'm', 20], ['Yeni Fiyat', 'tl', 12],
                ['Tedarikçi', 'm', 20], ['Değişim %', 'y', 10, 'esik:10'], ['Miktar', 's', 9], ['Tutar Etkisi', 'tl', 13, 'top bar'], ['Fatura', 'm', 12]],
            array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['kategori'], $x['birim'], $x['onceki'], $x['onceki_tarih'], $x['onceki_ted'], $x['yeni'], $x['ted'], $x['yuzde'], $x['miktar'], $x['etki'], $x['fatura']], $deg),
            _srBos('degisim', $dn));
    }
    function _srFaturaSayfa($l, array $dn, $ad = 'Alış Faturaları')
    {
        $f = []; foreach ($l as $x) { $k = $x->fatura_id; $f[$k] ??= ['tarih' => $x->tarih, 'ted' => _srTed($x->tedarikci_id), 'no' => (string) ($x->fatura_no ?: '#' . $x->fatura_id), 'kalem' => 0, 'tutar' => 0]; $f[$k]['kalem']++; $f[$k]['tutar'] += $x->tutar; }
        usort($f, fn ($a, $b) => strcmp($b['tarih'], $a['tarih']));
        return _srSayfa($ad, 'Dönemdeki alış faturaları (kalem toplamları).', [['Tarih', 't', 11], ['Tedarikçi', 'm', 26], ['Fatura No', 'm', 16], ['Kalem', 's', 8, 'top'], ['Tutar', 'tl', 15, 'top bar']],
            array_map(fn ($x) => [$x['tarih'], $x['ted'], $x['no'], $x['kalem'], round($x['tutar'], 2)], $f), _srBos('fatura', $dn));
    }
    function _srDagilim($l, $anahtarFn)
    {
        $o = []; foreach ($l as $x) { $k = $anahtarFn($x); $o[$k] = ($o[$k] ?? 0) + $x->tutar; } arsort($o); return $o;
    }
    function _srKiyasSayfa($ad, $bas, array $simdi, array $once, array $dn)
    {
        $tum = array_unique(array_merge(array_keys($simdi), array_keys($once))); $top = max(0.01, array_sum($simdi)); $sat = [];
        foreach ($tum as $k) { $a = $simdi[$k] ?? 0; $b = $once[$k] ?? 0; $sat[] = [$k, round($a, 2), round($a / $top * 100, 1), round($b, 2), round($a - $b, 2), _srYuzde($a, $b)]; }
        usort($sat, fn ($x, $y) => $y[1] <=> $x[1]);
        return _srSayfa($ad, 'Seçili dönem (' . $dn['etiket'] . ') ile önceki dönem (' . \Carbon\Carbon::parse($dn['obas'])->format('d.m.Y') . ' – ' . \Carbon\Carbon::parse($dn['obit'])->format('d.m.Y') . ') kıyası.',
            [[$bas, 'm', 26], ['Bu Dönem', 'tl', 15, 'top bar'], ['Pay %', 'y', 9, 'top'], ['Önceki Dönem', 'tl', 15, 'top'], ['Fark', 'tl', 14, 'top'], ['Değişim %', 'y', 10]], $sat, _srBos('fatura', $dn));
    }

    // Teslim performansı satırları (sipariş bazında)
    function _srTeslimler($sid, array $dn)
    {
        $sip = _srSiparisler($sid, $dn['bas'], $dn['bit']); if ($sip->isEmpty()) return [];
        $mk = _srMkBySiparis($sid, $sip->pluck('id')->all());
        $kal = _srTablo('satinalma_siparis_kalemleri') ? DB::table('satinalma_siparis_kalemleri')->whereIn('siparis_id', $sip->pluck('id'))->get()->groupBy('siparis_id') : collect();
        $mkk = _srTablo('mal_kabul_kalemleri') && $mk->isNotEmpty() ? DB::table('mal_kabul_kalemleri')->whereIn('mal_kabul_id', $mk->flatten()->pluck('id'))->get()->groupBy('mal_kabul_id') : collect();
        $out = [];
        foreach ($sip as $s) {
            $m = $mk[$s->id] ?? collect(); if ($m->isEmpty()) continue;
            $ilkMk = substr((string) $m->first()->tarih, 0, 10); $bek = $s->beklenen_teslim ? substr((string) $s->beklenen_teslim, 0, 10) : null;
            $gec = $bek ? (int) \Carbon\Carbon::parse($bek)->diffInDays(\Carbon\Carbon::parse($ilkMk), false) : null;
            $sipMik = (float) ($kal[$s->id] ?? collect())->sum('miktar'); $gelen = 0; $red = 0;
            foreach ($m as $x) { $g = $mkk[$x->id] ?? collect(); $gelen += (float) $g->sum('gelen_miktar'); $red += (float) $g->sum('red_miktar'); }
            $out[] = ['no' => $s->siparis_no ?: '#' . $s->id, 'ted' => _srTed($s->tedarikci_id), 'tarih' => substr((string) $s->tarih, 0, 10), 'beklenen' => $bek, 'teslim' => $ilkMk,
                'gecikme' => $gec, 'zamaninda' => $gec !== null ? ($gec <= 0 ? 'Evet' : 'Hayır') : '—', 'siparis_mik' => $sipMik, 'gelen' => $gelen, 'red' => $red,
                'tamlik' => $sipMik > 0 ? round(min(100, $gelen / $sipMik * 100), 1) : null, 'tutar' => (float) $s->genel_toplam, 'sure' => (int) \Carbon\Carbon::parse(substr((string) $s->tarih, 0, 10))->diffInDays(\Carbon\Carbon::parse($ilkMk))];
        }
        return $out;
    }
    function _srTeslimTedarikci(array $ts)
    {
        $g = []; foreach ($ts as $t) $g[$t['ted']][] = $t; $o = [];
        foreach ($g as $ted => $l) {
            $tarihli = array_filter($l, fn ($x) => $x['gecikme'] !== null); $z = count(array_filter($tarihli, fn ($x) => $x['gecikme'] <= 0));
            $gec = array_filter($tarihli, fn ($x) => $x['gecikme'] > 0); $sm = array_sum(array_column($l, 'siparis_mik'));
            $o[] = [$ted, count($l), $tarihli ? round($z / count($tarihli) * 100, 1) : null, $gec ? round(array_sum(array_column($gec, 'gecikme')) / count($gec), 1) : 0, $sm > 0 ? round(min(100, array_sum(array_column($l, 'gelen')) / $sm * 100), 1) : null,
                round(array_sum(array_column($l, 'sure')) / max(1, count($l)), 1), array_sum(array_column($l, 'red'))];
        }
        usort($o, fn ($a, $b) => ($a[2] ?? 999) <=> ($b[2] ?? 999));
        return $o;
    }
    function _srTeslimSayfalari($sid, array $dn, array $d)
    {
        $ts = _srTeslimler($sid, $dn);
        $bos = !$d['siparis'] ? _srBos('siparis_yok', $dn) : (!$d['malkabul_sip'] ? _srBos('mk_bagsiz', $dn, $d) : _srBos('mk', $dn));
        return [$ts, [_srSayfa('Teslim Performansı (Tedarikçi)', 'Zamanında = ilk mal kabul tarihi ≤ siparişteki beklenen teslim tarihi. Tamlık = gelen miktar ÷ sipariş miktarı. Süre = sipariş tarihinden ilk teslime gün.',
                [['Tedarikçi', 'm', 26], ['Teslim Edilen Sipariş', 's', 12, 'top'], ['Zamanında %', 'y', 12, 'bar'], ['Ort. Gecikme (gün)', 's', 12], ['Tamlık %', 'y', 10], ['Ort. Süre (gün)', 's', 11], ['Red Miktarı', 's', 10, 'top']], _srTeslimTedarikci($ts), $bos),
            _srSayfa('Teslim Edilen Siparişler', 'Gecikme = ilk mal kabul − beklenen teslim (gün; eksi = erken).',
                [['Sipariş', 'm', 13], ['Tedarikçi', 'm', 22], ['Sipariş Tarihi', 't', 11], ['Beklenen', 't', 11], ['İlk Teslim', 't', 11], ['Gecikme (gün)', 's', 10, 'esik:1'], ['Zamanında', 'm', 9], ['Sipariş Mik.', 's', 10], ['Gelen', 's', 9], ['Tamlık %', 'y', 9], ['Red', 's', 7], ['Tutar', 'tl', 13, 'top']],
                array_map(fn ($x) => [$x['no'], $x['ted'], $x['tarih'], $x['beklenen'], $x['teslim'], $x['gecikme'], $x['zamaninda'], $x['siparis_mik'], $x['gelen'], $x['tamlik'], $x['red'], $x['tutar']], $ts), $bos)]];
    }
    function _srRedler($sid, array $dn)
    {
        if (!_srTablo('mal_kabul_kalemleri') || !_srTablo('mal_kabuller')) return [];
        return DB::table('mal_kabul_kalemleri as k')->join('mal_kabuller as m', 'k.mal_kabul_id', '=', 'm.id')->where('m.sube_id', $sid)
            ->whereDate('m.tarih', '>=', $dn['bas'])->whereDate('m.tarih', '<=', $dn['bit'])
            ->get(['k.malzeme_id', 'k.siparis_miktar', 'k.gelen_miktar', 'k.kabul_miktar', 'k.red_miktar', 'k.red_sebep', 'k.birim_fiyat', 'm.tarih', 'm.tedarikci_id', 'm.irsaliye_no', 'm.siparis_id'])
            ->map(fn ($x) => ['malzeme' => _srMalz($x->malzeme_id), 'ted' => _srTed($x->tedarikci_id), 'tarih' => substr((string) $x->tarih, 0, 10), 'irsaliye' => (string) $x->irsaliye_no,
                'siparis' => (float) $x->siparis_miktar, 'gelen' => (float) $x->gelen_miktar, 'kabul' => (float) $x->kabul_miktar, 'red' => (float) $x->red_miktar,
                'eksik' => max(0, (float) $x->siparis_miktar - (float) $x->gelen_miktar), 'sebep' => trim((string) $x->red_sebep) ?: ((float) $x->red_miktar > 0 ? 'Belirtilmemiş' : ''), 'tutar' => round((float) $x->red_miktar * (float) $x->birim_fiyat, 2)])->all();
    }
    function _srRedSayfa(array $r, array $dn, array $d)
    {
        $sorun = array_values(array_filter($r, fn ($x) => $x['red'] > 0 || $x['eksik'] > 0));
        return _srSayfa('Red ve Eksik Teslimler', 'Red = mal kabulde geri çevrilen miktar. Eksik = sipariş miktarı − gelen miktar (siparişe bağlı kabullerde). Red tutarı = red miktarı × birim fiyat.',
            [['Tarih', 't', 11], ['Tedarikçi', 'm', 22], ['Malzeme', 'm', 22], ['İrsaliye', 'm', 12], ['Sipariş', 's', 9], ['Gelen', 's', 9], ['Kabul', 's', 9], ['Red', 's', 8, 'top'], ['Eksik', 's', 8, 'top'], ['Sebep', 'm', 22], ['Red Tutarı', 'tl', 12, 'top bar']],
            array_map(fn ($x) => [$x['tarih'], $x['ted'], $x['malzeme'], $x['irsaliye'], $x['siparis'] ?: null, $x['gelen'], $x['kabul'], $x['red'] ?: null, $x['eksik'] ?: null, $x['sebep'], $x['tutar'] ?: null], $sorun),
            !$d['malkabul'] ? 'Mal kabul kaydı yok: son 1 yılda mal kabul yapılmamış. Red/eksik analizi mal kabul ekranında girilen gelen, kabul ve red miktarlarından hesaplanır. Dolması için malı Satın Alma > Mal Kabul / İrsaliye\'den teslim alın.' : _srBos('red', $dn, $d));
    }

    // Onay bekleyen talepler
    function _srOnayBekleyen($sid)
    {
        if (!_srTablo('satinalma_talepleri')) return [];
        return DB::table('satinalma_talepleri')->where('sube_id', $sid)->whereIn('durum', ['yeni', 'onayda', 'fiyat_onay', 'teklif'])->orderBy('created_at')->get()
            ->map(fn ($t) => ['no' => '#' . $t->id, 'departman' => (string) ($t->departman ?: '—'), 'isteyen' => (string) ($t->isteyen_ad ?: '—'), 'aciliyet' => $t->aciliyet === 'acil' ? 'ACİL' : 'Normal',
                'durum' => ['yeni' => 'Yeni', 'onayda' => 'Onayda', 'fiyat_onay' => 'Fiyat onayı', 'teklif' => 'Teklif toplanıyor'][$t->durum] ?? $t->durum, 'tarih' => substr((string) $t->created_at, 0, 10),
                'gun' => (int) \Carbon\Carbon::parse(substr((string) $t->created_at, 0, 10))->diffInDays(today())])->all();
    }
    function _srKritikStok($sid)
    {
        $st = _srStokMevcut($sid); $o = [];
        foreach (_srMeta()['malz'] as $m) {
            if ((float) $m->kritik_stok <= 0) continue; $mev = (float) ($st[$m->id] ?? 0);
            if ($mev < (float) $m->kritik_stok) $o[] = [$m->ad, _srKat($m->id), _srMeta()['birim'][$m->temel_birim_id] ?? '', round($mev, 3), (float) $m->kritik_stok, round((float) $m->kritik_stok - $mev, 3)];
        }
        usort($o, fn ($a, $b) => ($a[3] / max(0.001, $a[4])) <=> ($b[3] / max(0.001, $b[4])));
        return $o;
    }
    function _srSktPartiler($sid, $gun = 30)
    {
        if (!_srTablo('stok_partileri')) return [];
        return DB::table('stok_partileri')->where('sube_id', $sid)->where('kalan_miktar', '>', 0)->whereNotNull('skt')->whereDate('skt', '<=', today()->addDays($gun))->orderBy('skt')->get()
            ->map(fn ($p) => ['malzeme' => _srMalz($p->malzeme_id), 'lot' => (string) $p->lot_no, 'skt' => substr((string) $p->skt, 0, 10), 'kalan_gun' => (int) today()->diffInDays(\Carbon\Carbon::parse(substr((string) $p->skt, 0, 10)), false),
                'kalan' => (float) $p->kalan_miktar, 'birim' => _srMeta()['birim'][_srMeta()['malz'][$p->malzeme_id]->temel_birim_id ?? 0] ?? '', 'deger' => round((float) $p->kalan_miktar * (float) $p->birim_maliyet, 2), 'ted' => _srTed($p->tedarikci_id)])->all();
    }
    function _srButceAy($sid, $ym)
    {
        $b = _srTablo('satinalma_butce') ? (float) DB::table('satinalma_butce')->where('sube_id', $sid)->where('yil_ay', $ym)->value('tutar') : 0;
        $sip = _srTablo('satinalma_siparisleri') ? (float) DB::table('satinalma_siparisleri')->where('sube_id', $sid)->whereNotIn('durum', ['iptal', 'taslak'])->where('tarih', 'like', $ym . '%')->sum('genel_toplam') : 0;
        $fat = _srTablo('alis_faturalari') ? (float) DB::table('alis_faturalari')->where('sube_id', $sid)->where('tarih', 'like', $ym . '%')->sum('toplam') : 0;
        return ['butce' => $b, 'siparis' => $sip, 'fatura' => $fat];
    }
    function _srFireler($sid, array $dn)
    {
        $o = ['hareket' => [], 'sayim' => []];
        if (_srTablo('stok_hareketleri')) $o['hareket'] = DB::table('stok_hareketleri')->where('sube_id', $sid)->whereIn('tip', ['fire', 'iade'])
            ->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->orderByDesc('created_at')->get()
            ->map(fn ($h) => ['tarih' => substr((string) $h->created_at, 0, 10), 'tip' => $h->tip === 'fire' ? 'Fire' : 'İade', 'malzeme' => _srMalz($h->malzeme_id), 'kategori' => _srKat($h->malzeme_id),
                'miktar' => abs((float) $h->miktar), 'birim' => _srMeta()['birim'][_srMeta()['malz'][$h->malzeme_id]->temel_birim_id ?? 0] ?? '', 'maliyet' => round(abs((float) $h->miktar) * (float) $h->birim_maliyet, 2), 'aciklama' => (string) ($h->aciklama ?? '')])->all();
        if (_srTablo('sayimlar') && _srTablo('sayim_kalemleri')) $o['sayim'] = DB::table('sayim_kalemleri as k')->join('sayimlar as s', 'k.sayim_id', '=', 's.id')->where('s.sube_id', $sid)
            ->whereDate('s.tarih', '>=', $dn['bas'])->whereDate('s.tarih', '<=', $dn['bit'])->where('k.fark', '!=', 0)->get(['s.tarih', 'k.malzeme_id', 'k.teorik', 'k.sayilan', 'k.fark', 'k.fark_maliyet'])
            ->map(fn ($x) => ['tarih' => substr((string) $x->tarih, 0, 10), 'malzeme' => _srMalz($x->malzeme_id), 'teorik' => (float) $x->teorik, 'sayilan' => (float) $x->sayilan, 'fark' => (float) $x->fark, 'maliyet' => (float) $x->fark_maliyet])->all();
        return $o;
    }
    function _srBorclar($sid)
    {
        if (!_srTablo('cari_hesaplar') || !_srTablo('cari_hareketler')) return [];
        $tk = _srKolon('cari_hesaplar', 'tedarikci_id');
        $cari = DB::table('cari_hesaplar')->where('sube_id', $sid)->where(function ($q) use ($tk) { $q->where('tip', 'tedarikci'); if ($tk) $q->orWhereNotNull('tedarikci_id'); })->get();
        if ($cari->isEmpty()) return [];
        $h = DB::table('cari_hareketler')->whereIn('cari_id', $cari->pluck('id'))->get()->groupBy('cari_id'); $o = [];
        foreach ($cari as $c) {
            $l = $h[$c->id] ?? collect(); $borc = (float) $l->where('tip', 'borc')->sum('tutar'); $ode = (float) $l->where('tip', '!=', 'borc')->sum('tutar');
            $yeni = fn ($g) => (float) $l->where('tip', 'borc')->filter(fn ($x) => substr((string) $x->created_at, 0, 10) >= today()->subDays($g)->toDateString())->sum('tutar');
            $sonOde = $l->where('tip', '!=', 'borc')->max('created_at');
            $o[] = [$c->ad, round($borc, 2), round($ode, 2), round($borc - $ode, 2), round($yeni(30), 2), round($yeni(60), 2), round($yeni(90), 2), $sonOde ? substr((string) $sonOde, 0, 10) : null];
        }
        usort($o, fn ($a, $b) => $b[3] <=> $a[3]);
        return $o;
    }
}

// =====================================================================
// RAPORLAR — her biri sayfalar dizisi döner (Pano + tablolar)
// =====================================================================
if (!function_exists('_srRapor')) {
    function _srRapor($anahtar, $sid, array $dn)
    {
        $d = _srVeriDurumu($sid);
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $l = _srAlis($sid, $dn['bas'], $dn['bit']); $lo = _srAlis($sid, $dn['obas'], $dn['obit']);
        $top = _srToplam($l); $otop = _srToplam($lo);
        $bosF = $l->isEmpty() ? _srBos('fatura', $dn) : null;
        $tl0 = fn ($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $deg = _srDegisimler($sid, $dn); $acik = _srAcikSiparis($sid); $bugun = today()->toDateString();
                $bekl = $acik->filter(fn ($s) => $s->beklenen_teslim && substr((string) $s->beklenen_teslim, 0, 10) === $bugun);
                $gec = $acik->filter(fn ($s) => $s->beklenen_teslim && substr((string) $s->beklenen_teslim, 0, 10) < $bugun);
                $onay = _srOnayBekleyen($sid); $red = array_values(array_filter(_srRedler($sid, $dn), fn ($x) => $x['red'] > 0 || $x['eksik'] > 0));
                $krit = _srKritikStok($sid); $skt = array_values(array_filter(_srSktPartiler($sid, 3), fn ($x) => true)); $bu = _srButceAy($sid, today()->format('Y-m'));
                $sipSat = fn ($c, $gecikme) => $c->map(fn ($s) => [$s->siparis_no ?: '#' . $s->id, _srTed($s->tedarikci_id), substr((string) $s->tarih, 0, 10), substr((string) $s->beklenen_teslim, 0, 10),
                    $gecikme ? (int) \Carbon\Carbon::parse(substr((string) $s->beklenen_teslim, 0, 10))->diffInDays(today()) : null, ['gonderildi' => 'Gönderildi', 'onaylandi' => 'Onaylandı', 'kismi' => 'Kısmi teslim'][$s->durum] ?? $s->durum, (float) $s->genel_toplam])->values()->all();
                $sipKol = [['Sipariş', 'm', 14], ['Tedarikçi', 'm', 24], ['Sipariş Tarihi', 't', 11], ['Beklenen Teslim', 't', 12], ['Gecikme (gün)', 's', 11, 'esik:1'], ['Durum', 'm', 12], ['Tutar', 'tl', 14, 'top']];
                $sipBos = !$d['siparis'] ? _srBos('siparis_yok', $dn) : null;
                return [
                    $pano([_srKpi('Alış (' . $dn['ad'] . ')', $top, 'tl', '7C3AED'), _srKpi('Önceki güne göre', _srYuzde($top, $otop) ?? '—', _srYuzde($top, $otop) === null ? 'metin' : 'y', '6D28D9'), _srKpi('Fatura', $l->pluck('fatura_id')->unique()->count(), 'sayi', '5B21B6'), _srKpi('Fiyatı değişen kalem', count($deg), 'sayi', 'DC2626'),
                        _srKpi('Bugün beklenen teslimat', $bekl->count(), 'sayi', '0284C7'), _srKpi('Geciken sipariş', $gec->count(), 'sayi', 'B91C1C'), _srKpi('Onay bekleyen talep', count($onay), 'sayi', 'D97706'),
                        _srKpi('Bu ay bütçe kullanımı', $bu['butce'] > 0 ? round($bu['siparis'] / $bu['butce'] * 100, 1) : 'Bütçe yok', $bu['butce'] > 0 ? 'y' : 'metin', '059669'),
                        _srKpi('Kritik stok altında', count($krit), 'sayi', 'C2410C'), _srKpi('SKT 3 gün içinde', count($skt), 'sayi', '9F1239'), _srKpi('Red / eksik teslim', count($red), 'sayi', 'E11D48'), _srKpi('Rapor tarihi', today()->format('d.m.Y'), 'metin', '1E293B')],
                        [_srBlok('Tedarikçilere göre alış', 'bar', '7C3AED', [['Tedarikçi', 'm'], ['Alış', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dg = _srDagilim($l, fn ($x) => _srTed($x->tedarikci_id))), $dg)),
                         _srBlok('En büyük fiyat değişimleri', 'bar', 'DC2626', [['Malzeme', 'm'], ['Değişim %', 'y'], ['Önceki → yeni', 'm']], array_map(fn ($x) => [$x['malzeme'], $x['yuzde'], number_format($x['onceki'], 2, ',', '.') . ' → ' . number_format($x['yeni'], 2, ',', '.') . ' ₺'], array_slice(collect($deg)->sortByDesc(fn ($x) => abs($x['yuzde']))->values()->all(), 0, 10)))], $bosF),
                    _srFaturaSayfa($l, $dn, 'Faturalar (' . $dn['ad'] . ')'),
                    _srDegisimSayfa($deg, $dn),
                    _srSayfa('Bugün Beklenen Teslimatlar', 'Durumu açık (gönderildi/onaylandı/kısmi) ve beklenen teslim tarihi bugün olan siparişler.', $sipKol, $sipSat($bekl, false), $sipBos ?? _srBos('teslim_bugun', $dn)),
                    _srSayfa('Geciken Siparişler', 'Beklenen teslim tarihi geçmiş, hâlâ açık siparişler. Gecikme = bugün − beklenen teslim.', $sipKol, $sipSat($gec, true), $sipBos ?? _srBos('geciken', $dn)),
                    _srSayfa('Onay Bekleyen Talepler', 'Durumu yeni / onayda / fiyat onayı / teklif toplanıyor olan talepler; bekleme = bugün − açılış.',
                        [['Talep', 'm', 9], ['Departman', 'm', 16], ['İsteyen', 'm', 18], ['Aciliyet', 'm', 9], ['Durum', 'm', 16], ['Açılış', 't', 11], ['Bekleme (gün)', 's', 11, 'esik:3']],
                        array_map(fn ($x) => [$x['no'], $x['departman'], $x['isteyen'], $x['aciliyet'], $x['durum'], $x['tarih'], $x['gun']], $onay), !$d['talep'] ? _srBos('talep_yok', $dn) : _srBos('onay', $dn)),
                    _srRedSayfa(_srRedler($sid, $dn), $dn, $d),
                    _srSayfa('Kritik Stok', 'Mevcut stok (stok hareketleri toplamı) kritik stok seviyesinin altında olan malzemeler.', [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Mevcut', 's', 10], ['Kritik Seviye', 's', 11], ['Eksik', 's', 10, 'bar']], $krit, _srBos('kritik', $dn)),
                    _srSayfa('SKT Yaklaşan Partiler (3 gün)', 'Stokta kalan ve son kullanma tarihi 3 gün içinde dolan ya da geçmiş partiler.', [['Malzeme', 'm', 22], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 7], ['Değer', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 20]],
                        array_map(fn ($x) => [$x['malzeme'], $x['lot'], $x['skt'], $x['kalan_gun'], $x['kalan'], $x['birim'], $x['deger'], $x['ted']], $skt), !$d['parti'] ? _srBos('parti_yok', $dn) : 'SKT\'si 3 gün içinde dolan ya da geçmiş, stokta kalan parti yok.'),
                ];
            case 'haftalik':
                $deg = _srDegisimler($sid, $dn); [$ts, $tSay] = _srTeslimSayfalari($sid, $dn, $d);
                $gunler = []; for ($t = \Carbon\Carbon::parse($dn['bas']); $t->lte(\Carbon\Carbon::parse($dn['bit'])); $t->addDay()) $gunler[$t->toDateString()] = 0;
                foreach ($l as $x) if (isset($gunler[$x->tarih])) $gunler[$x->tarih] += $x->tutar;
                $gAd = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                $talep = _srTablo('satinalma_talepleri') ? DB::table('satinalma_talepleri')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->get() : collect();
                $rfq = _srTablo('rfq_gonderim') ? DB::table('rfq_gonderim')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->get() : collect();
                $zz = collect($deg)->sortByDesc('yuzde')->values()->all(); $uc = collect($deg)->sortBy('yuzde')->values()->all();
                $tarihli = array_filter($ts, fn ($x) => $x['gecikme'] !== null);
                return [
                    $pano([_srKpi('Haftalık alış', $top, 'tl', '4F46E5'), _srKpi('Önceki haftaya göre', _srYuzde($top, $otop) ?? '—', _srYuzde($top, $otop) === null ? 'metin' : 'y', '4338CA'), _srKpi('Fatura / tedarikçi', $l->pluck('fatura_id')->unique()->count() . ' / ' . $l->pluck('tedarikci_id')->unique()->count(), 'metin', '3730A3'),
                        _srKpi('Fiyatı artan / düşen', count(array_filter($deg, fn ($x) => $x['yuzde'] > 0)) . ' / ' . count(array_filter($deg, fn ($x) => $x['yuzde'] < 0)), 'metin', 'DC2626'),
                        _srKpi('Zamanında teslim', $tarihli ? round(count(array_filter($tarihli, fn ($x) => $x['gecikme'] <= 0)) / count($tarihli) * 100, 1) : 'Veri yok', $tarihli ? 'y' : 'metin', '0284C7'),
                        _srKpi('Yeni talep / acil', $talep->count() . ' / ' . $talep->where('aciliyet', 'acil')->count(), 'metin', 'A21CAF'), _srKpi('Teklif isteği / yanıt', $rfq->count() . ' / ' . $rfq->whereNotNull('yanit_at')->count(), 'metin', '0369A1'),
                        _srKpi('Fiyat değişim etkisi', array_sum(array_column($deg, 'etki')), 'tl', 'B91C1C')],
                        [_srBlok('Günlere göre alış', 'col', '4F46E5', [['Gün', 'm'], ['Alış', 'tl'], ['', 'm']], array_map(fn ($g, $v) => [$gAd[\Carbon\Carbon::parse($g)->dayOfWeekIso - 1] . ' ' . \Carbon\Carbon::parse($g)->format('d.m'), round($v, 2), ''], array_keys($gunler), $gunler)),
                         _srBlok('Kategoriye göre', 'pie', '6366F1', [['Kategori', 'm'], ['Alış', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dk = _srDagilim($l, fn ($x) => _srKat($x->malzeme_id))), $dk)),
                         _srBlok('En çok zamlanan 10 kalem', 'bar', 'DC2626', [['Malzeme', 'm'], ['Değişim %', 'y'], ['Tedarikçi', 'm']], array_map(fn ($x) => [$x['malzeme'], $x['yuzde'], $x['ted']], array_slice(array_filter($zz, fn ($x) => $x['yuzde'] > 0), 0, 10))),
                         _srBlok('En çok ucuzlayan 10 kalem', 'bar', '059669', [['Malzeme', 'm'], ['Değişim %', 'y'], ['Tedarikçi', 'm']], array_map(fn ($x) => [$x['malzeme'], abs($x['yuzde']), $x['ted']], array_slice(array_filter($uc, fn ($x) => $x['yuzde'] < 0), 0, 10)))], $bosF),
                    _srKiyasSayfa('Tedarikçiye Göre', 'Tedarikçi', _srDagilim($l, fn ($x) => _srTed($x->tedarikci_id)), _srDagilim($lo, fn ($x) => _srTed($x->tedarikci_id)), $dn),
                    _srKiyasSayfa('Kategoriye Göre', 'Kategori', _srDagilim($l, fn ($x) => _srKat($x->malzeme_id)), _srDagilim($lo, fn ($x) => _srKat($x->malzeme_id)), $dn),
                    _srDegisimSayfa($deg, $dn), $tSay[0], $tSay[1],
                    _srSayfa('Haftanın Talepleri', 'Bu dönemde açılan talepler.', [['Talep', 'm', 9], ['Tarih', 't', 11], ['Departman', 'm', 16], ['İsteyen', 'm', 18], ['Aciliyet', 'm', 9], ['Durum', 'm', 14]],
                        $talep->map(fn ($t) => ['#' . $t->id, substr((string) $t->created_at, 0, 10), (string) ($t->departman ?: '—'), (string) ($t->isteyen_ad ?: '—'), $t->aciliyet === 'acil' ? 'ACİL' : 'Normal', (string) $t->durum])->all(),
                        !$d['talep'] ? _srBos('talep_yok', $dn) : _srBos('talep', $dn)),
                    _srFaturaSayfa($l, $dn),
                ];
            case 'aylik':
                $ym = substr($dn['bas'], 0, 7); $bu = _srButceAy($sid, $ym); $deg = _srDegisimler($sid, $dn); [$ts, $tSay] = _srTeslimSayfalari($sid, $dn, $d);
                $fr = _srFireler($sid, $dn); $frTop = array_sum(array_column($fr['hareket'], 'maliyet')); $syTop = array_sum(array_column($fr['sayim'], 'maliyet'));
                $borc = _srBorclar($sid); $endeks = _srEndeks($sid, 6, $dn['bit']); $menu = _srMenuEtki($sid, $dn);
                $son = end($endeks['aylar']) ?: null; $tarihli = array_filter($ts, fn ($x) => $x['gecikme'] !== null);
                return [
                    $pano([_srKpi('Aylık alış (fatura)', $top, 'tl', '9333EA'), _srKpi('Önceki aya göre', _srYuzde($top, $otop) ?? '—', _srYuzde($top, $otop) === null ? 'metin' : 'y', '7E22CE'),
                        _srKpi('Bütçe kullanımı (sipariş)', $bu['butce'] > 0 ? round($bu['siparis'] / $bu['butce'] * 100, 1) : 'Bütçe yok', $bu['butce'] > 0 ? 'y' : 'metin', '059669'),
                        _srKpi('Fiyat endeksi (son ay)', $son ? round($son['endeks'], 1) : 'Veri yok', $son ? 'sayi' : 'metin', 'BE185D'),
                        _srKpi('Fire + iade maliyeti', $frTop, 'tl', 'C2410C'), _srKpi('Sayım farkı', $syTop, 'tl', '9A3412'),
                        _srKpi('Zamanında teslim', $tarihli ? round(count(array_filter($tarihli, fn ($x) => $x['gecikme'] <= 0)) / count($tarihli) * 100, 1) : 'Veri yok', $tarihli ? 'y' : 'metin', '0284C7'),
                        _srKpi('Tedarikçi borç bakiyesi', $borc ? array_sum(array_column($borc, 3)) : 'Cari yok', $borc ? 'tl' : 'metin', '1D4ED8'),
                        _srKpi('Maliyeti artan menü ürünü', count(array_filter($menu, fn ($x) => $x[4] > 0)), 'sayi', 'EA580C'), _srKpi('Fiyatı değişen kalem', count($deg), 'sayi', 'DC2626'),
                        _srKpi('Fatura / tedarikçi', $l->pluck('fatura_id')->unique()->count() . ' / ' . $l->pluck('tedarikci_id')->unique()->count(), 'metin', '581C87'), _srKpi('Dönem', $dn['etiket'], 'metin', '581C87')],
                        [_srBlok('Fiyat endeksi (son 6 ay, ilk ay = 100)', 'col', 'BE185D', [['Ay', 'm'], ['Endeks', 's'], ['Aylık değişim %', 'y']], array_map(fn ($a) => [_fr2AyAd($a['ay']), round($a['endeks'], 1), $a['degisim']], $endeks['aylar'])),
                         _srBlok('Kategoriye göre alış', 'pie', '9333EA', [['Kategori', 'm'], ['Alış', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dk = _srDagilim($l, fn ($x) => _srKat($x->malzeme_id))), $dk)),
                         _srBlok('Tedarikçiye göre alış', 'bar', '7C3AED', [['Tedarikçi', 'm'], ['Alış', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dg = _srDagilim($l, fn ($x) => _srTed($x->tedarikci_id))), $dg)),
                         _srBlok('Maliyeti en çok artan menü ürünleri', 'bar', 'EA580C', [['Ürün', 'm'], ['Maliyet artışı', 'tl'], ['Food cost (sonra) %', 'y']], array_map(fn ($x) => [$x[0], $x[4], $x[7]], array_slice(array_filter($menu, fn ($x) => $x[4] > 0), 0, 10)))], $bosF),
                    _srKiyasSayfa('Kategoriye Göre', 'Kategori', $dk, _srDagilim($lo, fn ($x) => _srKat($x->malzeme_id)), $dn),
                    _srKiyasSayfa('Tedarikçiye Göre', 'Tedarikçi', $dg, _srDagilim($lo, fn ($x) => _srTed($x->tedarikci_id)), $dn),
                    _srButceSayfa($sid, $dn, $d), _srEndeksSayfa($endeks, $dn), _srMenuSayfa($menu, $dn, $d), $tSay[0],
                    _srFireSayfa($fr, $dn, $d), _srBorcSayfa($borc, $dn, $d), _srDegisimSayfa($deg, $dn),
                ];

            // ---------------- ANALİZLER ----------------
            case 'harcama':
                $ml = _srDagilim($l, fn ($x) => _srMalz($x->malzeme_id)); $mlo = _srDagilim($lo, fn ($x) => _srMalz($x->malzeme_id));
                $artan = []; foreach ($ml as $k => $v) $artan[] = [$k, round($v - ($mlo[$k] ?? 0), 2), _srYuzde($v, $mlo[$k] ?? 0)];
                usort($artan, fn ($a, $b) => $b[1] <=> $a[1]);
                return [$pano([_srKpi('Dönem harcaması', $top, 'tl', '2563EB'), _srKpi('Önceki dönem', $otop, 'tl', '1E40AF'), _srKpi('Değişim', _srYuzde($top, $otop) ?? '—', _srYuzde($top, $otop) === null ? 'metin' : 'y', '1D4ED8'), _srKpi('Fark', $top - $otop, 'tl', '3B82F6'),
                        _srKpi('Fatura', $l->pluck('fatura_id')->unique()->count(), 'sayi', '1E3A8A'), _srKpi('Tedarikçi', $l->pluck('tedarikci_id')->unique()->count(), 'sayi', '1E3A8A'), _srKpi('Malzeme', $l->pluck('malzeme_id')->unique()->count(), 'sayi', '1E3A8A'),
                        _srKpi('Ortalama fatura', $l->pluck('fatura_id')->unique()->count() ? $top / $l->pluck('fatura_id')->unique()->count() : 0, 'tl', '1E3A8A')],
                        [_srBlok('Kategoriye göre', 'pie', '2563EB', [['Kategori', 'm'], ['Harcama', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dk = _srDagilim($l, fn ($x) => _srKat($x->malzeme_id))), $dk)),
                         _srBlok('Tedarikçiye göre', 'bar', '2563EB', [['Tedarikçi', 'm'], ['Harcama', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dg = _srDagilim($l, fn ($x) => _srTed($x->tedarikci_id))), $dg)),
                         _srBlok('Önceki döneme göre en çok artan 10 malzeme', 'bar', 'DC2626', [['Malzeme', 'm'], ['Artış', 'tl'], ['Değişim %', 'y']], array_slice(array_filter($artan, fn ($x) => $x[1] > 0), 0, 10))], $bosF),
                    _srKiyasSayfa('Kategoriye Göre', 'Kategori', $dk, _srDagilim($lo, fn ($x) => _srKat($x->malzeme_id)), $dn),
                    _srKiyasSayfa('Tedarikçiye Göre', 'Tedarikçi', $dg, _srDagilim($lo, fn ($x) => _srTed($x->tedarikci_id)), $dn),
                    _srKiyasSayfa('Malzemeye Göre', 'Malzeme', $ml, $mlo, $dn), _srFaturaSayfa($l, $dn)];
            case 'degisim':
                $deg = _srDegisimler($sid, $dn);
                return [$pano([_srKpi('Fiyatı değişen alış', count($deg), 'sayi', 'DC2626'), _srKpi('Artan / düşen', count(array_filter($deg, fn ($x) => $x['yuzde'] > 0)) . ' / ' . count(array_filter($deg, fn ($x) => $x['yuzde'] < 0)), 'metin', 'B91C1C'),
                        _srKpi('Toplam tutar etkisi', array_sum(array_column($deg, 'etki')), 'tl', '991B1B'), _srKpi('Ortalama değişim', $deg ? array_sum(array_column($deg, 'yuzde')) / count($deg) : 0, 'y', '7F1D1D')],
                        [_srBlok('En büyük artışlar (%)', 'bar', 'DC2626', [['Malzeme', 'm'], ['Değişim %', 'y'], ['Tedarikçi', 'm']], array_map(fn ($x) => [$x['malzeme'] . ' · ' . \Carbon\Carbon::parse($x['tarih'])->format('d.m'), $x['yuzde'], $x['ted']], array_slice(array_filter(collect($deg)->sortByDesc('yuzde')->values()->all(), fn ($x) => $x['yuzde'] > 0), 0, 12))),
                         _srBlok('En büyük tutar etkisi', 'bar', 'B91C1C', [['Malzeme', 'm'], ['Etki', 'tl'], ['Değişim %', 'y']], array_map(fn ($x) => [$x['malzeme'] . ' · ' . \Carbon\Carbon::parse($x['tarih'])->format('d.m'), $x['etki'], $x['yuzde']], array_slice(array_filter(collect($deg)->sortByDesc('etki')->values()->all(), fn ($x) => $x['etki'] > 0), 0, 12))),
                         _srBlok('En büyük düşüşler (%)', 'bar', '059669', [['Malzeme', 'm'], ['Düşüş %', 'y'], ['Tedarikçi', 'm']], array_map(fn ($x) => [$x['malzeme'] . ' · ' . \Carbon\Carbon::parse($x['tarih'])->format('d.m'), abs($x['yuzde']), $x['ted']], array_slice(array_filter(collect($deg)->sortBy('yuzde')->values()->all(), fn ($x) => $x['yuzde'] < 0), 0, 12)))],
                        $bosF ?? (!$deg ? _srBos('degisim', $dn) : null)), _srDegisimSayfa($deg, $dn)];
            case 'endeks':
                $ay = max(3, min(24, (int) ceil($dn['gun'] / 30))); $e = _srEndeks($sid, $ay, $dn['bit']); $son = end($e['aylar']) ?: null; $ilk = $e['aylar'][0] ?? null;
                return [$pano([_srKpi('Son ay endeksi (ilk ay = 100)', $son ? round($son['endeks'], 1) : 'Veri yok', $son ? 'sayi' : 'metin', 'BE185D'), _srKpi('Son ay değişimi', $son['degisim'] ?? 0, 'y', '9D174D'),
                        _srKpi('Sepetteki malzeme', count($e['sepet']), 'sayi', '831843'), _srKpi('Dönem', ($ilk ? _fr2AyAd($ilk['ay']) : '—') . ' – ' . ($son ? _fr2AyAd($son['ay']) : '—'), 'metin', '831843')],
                        [_srBlok('Fiyat endeksi', 'col', 'BE185D', [['Ay', 'm'], ['Endeks', 's'], ['Aylık değişim %', 'y']], array_map(fn ($a) => [_fr2AyAd($a['ay']), round($a['endeks'], 1), $a['degisim']], $e['aylar'])),
                         _srBlok('Endekse en çok etki eden malzemeler', 'bar', 'DB2777', [['Malzeme', 'm'], ['Etki (puan)', 's'], ['Fiyat değişimi %', 'y']], array_map(fn ($x) => [$x[0], $x[4], $x[3]], array_slice($e['etki'], 0, 10)))], count($e['aylar']) < 2 ? _srBos('endeks', $dn) : null),
                    _srEndeksSayfa($e, $dn),
                    _srSayfa('Sepet ve Etki', 'Sepet = ilk ayda ve son ayda alınan malzemeler; ağırlık = ilk aydaki alım tutarı payı. Etki (puan) = endeksin bu malzemeden gelen değişimi.',
                        [['Malzeme', 'm', 24], ['İlk Ay Ort. Fiyat', 'tl', 14], ['Son Ay Ort. Fiyat', 'tl', 14], ['Değişim %', 'y', 11], ['Endekse Etki (puan)', 's', 13, 'bar'], ['Ağırlık %', 'y', 10, 'top']], $e['etki'], _srBos('endeks', $dn))];
            case 'menu':
                $menu = _srMenuEtki($sid, $dn); $art = array_filter($menu, fn ($x) => $x[4] > 0);
                return [$pano([_srKpi('Reçeteli ürün', count($menu), 'sayi', 'EA580C'), _srKpi('Maliyeti artan', count($art), 'sayi', 'C2410C'), _srKpi('Maliyeti düşen', count(array_filter($menu, fn ($x) => $x[4] < 0)), 'sayi', '15803D'),
                        _srKpi('Ortalama maliyet değişimi', $menu ? array_sum(array_column($menu, 5)) / count($menu) : 0, 'y', '9A3412')],
                        [_srBlok('Maliyeti en çok artan ürünler', 'bar', 'EA580C', [['Ürün', 'm'], ['Maliyet artışı', 'tl'], ['Değişim %', 'y']], array_map(fn ($x) => [$x[0], $x[4], $x[5]], array_slice($art, 0, 12))),
                         _srBlok('Food cost oranı en çok yükselenler (puan)', 'bar', 'C2410C', [['Ürün', 'm'], ['Food cost artışı (puan)', 's'], ['Food cost (sonra) %', 'y']], array_map(fn ($x) => [$x[0], round(($x[7] ?? 0) - ($x[6] ?? 0), 1), $x[7]], array_slice(collect($menu)->filter(fn ($x) => $x[6] !== null)->sortByDesc(fn ($x) => $x[7] - $x[6])->values()->all(), 0, 12)))],
                        !$d['recete'] ? _srBos('recete_yok', $dn) : null), _srMenuSayfa($menu, $dn, $d)];
            case 'butce':
                $s = _srButceSayfa($sid, $dn, $d); $bu = _srButceAy($sid, today()->format('Y-m')); $gecen = (int) today()->day; $ayGun = (int) today()->daysInMonth;
                $tahmin = $gecen > 0 ? $bu['siparis'] / $gecen * $ayGun : 0;
                return [$pano([_srKpi('Bu ay bütçe', $bu['butce'], 'tl', '059669'), _srKpi('Bu ay sipariş', $bu['siparis'], 'tl', '047857'), _srKpi('Bu ay fatura', $bu['fatura'], 'tl', '065F46'),
                        _srKpi('Kullanım (sipariş)', $bu['butce'] > 0 ? round($bu['siparis'] / $bu['butce'] * 100, 1) : 'Bütçe yok', $bu['butce'] > 0 ? 'y' : 'metin', '15803D'),
                        _srKpi('Ay sonu tahmini (sipariş hızıyla)', round($tahmin, 2), 'tl', '166534'), _srKpi('Tahmini sapma', $bu['butce'] > 0 ? round($tahmin - $bu['butce'], 2) : 'Bütçe yok', $bu['butce'] > 0 ? 'tl' : 'metin', '14532D')],
                        [_srBlok('Aylık bütçe ve sipariş', 'col', '059669', [['Ay', 'm'], ['Sipariş', 'tl'], ['Bütçe', 'tl']], array_map(fn ($r) => [$r[0], $r[2], $r[1]], array_filter($s['sat'], fn ($r) => $r[1] || $r[2])))],
                        !$d['butce'] ? _srBos('butce_yok', $dn) : null), $s];
            case 'teslim':
                [$ts, $tSay] = _srTeslimSayfalari($sid, $dn, $d); $tarihli = array_filter($ts, fn ($x) => $x['gecikme'] !== null); $gec = array_filter($tarihli, fn ($x) => $x['gecikme'] > 0);
                $tt = _srTeslimTedarikci($ts);
                return [$pano([_srKpi('Teslim edilen sipariş', count($ts), 'sayi', '0284C7'), _srKpi('Zamanında teslim', $tarihli ? round((count($tarihli) - count($gec)) / count($tarihli) * 100, 1) : 'Veri yok', $tarihli ? 'y' : 'metin', '0369A1'),
                        _srKpi('Ortalama gecikme (geciken)', $gec ? round(array_sum(array_column($gec, 'gecikme')) / count($gec), 1) . ' gün' : '—', 'metin', '075985'),
                        _srKpi('Ortalama tamlık', $ts ? round(array_sum(array_filter(array_column($ts, 'tamlik'), 'is_numeric')) / max(1, count(array_filter(array_column($ts, 'tamlik'), 'is_numeric'))), 1) : 'Veri yok', $ts ? 'y' : 'metin', '0C4A6E'),
                        _srKpi('Açık sipariş', _srAcikSiparis($sid)->count(), 'sayi', '0E7490'), _srKpi('Geciken açık sipariş', _srAcikSiparis($sid)->filter(fn ($s) => $s->beklenen_teslim && substr((string) $s->beklenen_teslim, 0, 10) < today()->toDateString())->count(), 'sayi', 'B91C1C')],
                        [_srBlok('Tedarikçiye göre zamanında teslim %', 'bar', '0284C7', [['Tedarikçi', 'm'], ['Zamanında %', 'y'], ['Sipariş', 's']], array_map(fn ($x) => [$x[0], $x[2] ?? 0, $x[1]], array_filter($tt, fn ($x) => $x[2] !== null))),
                         _srBlok('Tedarikçiye göre ortalama süre (gün)', 'bar', '0369A1', [['Tedarikçi', 'm'], ['Gün', 's'], ['Sipariş', 's']], array_map(fn ($x) => [$x[0], $x[5], $x[1]], $tt))],
                        !$ts ? $tSay[0]['bos'] : null), $tSay[0], $tSay[1]];
            case 'red':
                $r = _srRedler($sid, $dn); $redT = array_sum(array_column($r, 'red')); $gelT = array_sum(array_column($r, 'gelen'));
                $byT = []; foreach ($r as $x) { $byT[$x['ted']] ??= [0, 0, 0]; $byT[$x['ted']][0] += $x['gelen']; $byT[$x['ted']][1] += $x['red']; $byT[$x['ted']][2] += $x['tutar']; }
                $bySeb = []; foreach ($r as $x) if ($x['red'] > 0) $bySeb[$x['sebep']] = ($bySeb[$x['sebep']] ?? 0) + $x['tutar'];
                arsort($bySeb);
                return [$pano([_srKpi('Mal kabul kalemi', count($r), 'sayi', 'E11D48'), _srKpi('Red oranı (miktar)', $gelT > 0 ? round($redT / $gelT * 100, 1) : 0, 'y', 'BE123C'), _srKpi('Red tutarı', array_sum(array_column($r, 'tutar')), 'tl', '9F1239'),
                        _srKpi('Eksik gelen kalem', count(array_filter($r, fn ($x) => $x['eksik'] > 0)), 'sayi', '881337')],
                        [_srBlok('Tedarikçiye göre red oranı %', 'bar', 'E11D48', [['Tedarikçi', 'm'], ['Red %', 'y'], ['Red tutarı', 'tl']], array_map(fn ($k, $v) => [$k, $v[0] > 0 ? round($v[1] / $v[0] * 100, 1) : 0, round($v[2], 2)], array_keys($byT), $byT)),
                         _srBlok('Red sebepleri (tutar)', 'pie', 'BE123C', [['Sebep', 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($bySeb), $bySeb))],
                        !$d['malkabul'] ? _srRedSayfa([], $dn, $d)['bos'] : null), _srRedSayfa($r, $dn, $d)];
            case 'uyum':
                $u = _srUyum($sid, $dn); $sap = array_filter($u, fn ($x) => abs($x[7] ?? 0) > 0.5);
                $bos = !$d['siparis'] ? _srBos('siparis_yok', $dn) : (!$d['malkabul_sip'] ? _srBos('mk_bagsiz', $dn, $d) : _srBos('uyum', $dn));
                return [$pano([_srKpi('Eşleşen kalem', count($u), 'sayi', '7C2D12'), _srKpi('Fiyatı farklı kalem (>%0,5)', count($sap), 'sayi', '9A3412'), _srKpi('Faturada fazla ödenen', array_sum(array_map(fn ($x) => max(0, $x[8]), $u)), 'tl', 'B91C1C'),
                        _srKpi('Uyum oranı', $u ? round((count($u) - count($sap)) / count($u) * 100, 1) : 'Veri yok', $u ? 'y' : 'metin', '78350F')],
                        [_srBlok('Fatura fiyatı siparişten en çok sapan kalemler', 'bar', 'B45309', [['Malzeme', 'm'], ['Fark %', 'y'], ['Tedarikçi', 'm']], array_map(fn ($x) => [$x[2], $x[7], $x[1]], array_slice(collect($sap)->sortByDesc(fn ($x) => abs($x[7]))->values()->all(), 0, 12)))], !$u ? $bos : null),
                    _srSayfa('Sipariş–Fatura Kıyası', 'Sipariş kalemi ↔ aynı siparişin mal kabulüne bağlı faturadaki aynı malzeme. Fark % = (fatura − sipariş) ÷ sipariş. Fazla ödeme = fark × faturadaki miktar.',
                        [['Sipariş', 'm', 13], ['Tedarikçi', 'm', 20], ['Malzeme', 'm', 22], ['Fatura', 'm', 13], ['Fatura Tarihi', 't', 11], ['Sipariş Fiyatı', 'tl', 12], ['Fatura Fiyatı', 'tl', 12], ['Fark %', 'y', 9, 'esik:0.51'], ['Fazla Ödeme', 'tl', 12, 'top bar']], $u, $bos)];
            case 'bagimlilik':
                $l180 = _srAlis($sid, \Carbon\Carbon::parse($dn['bit'])->subDays(max(180, $dn['gun']))->toDateString(), $dn['bit']);
                $tek = []; foreach ($l180->groupBy('malzeme_id') as $mid => $g) if ($g->pluck('tedarikci_id')->unique()->count() === 1) $tek[] = [_srMalz($mid), _srKat($mid), _srTed($g->first()->tedarikci_id), $g->count(), round($g->sum('tutar'), 2)];
                usort($tek, fn ($a, $b) => $b[4] <=> $a[4]); $dg = _srDagilim($l180, fn ($x) => _srTed($x->tedarikci_id)); $t180 = max(0.01, _srToplam($l180)); $ilkT = $dg ? array_key_first($dg) : null;
                return [$pano([_srKpi('Tek tedarikçili malzeme', count($tek), 'sayi', '0F766E'), _srKpi('Tek kaynaklı harcama', array_sum(array_column($tek, 4)), 'tl', '115E59'),
                        _srKpi('En büyük tedarikçi payı', $ilkT ? $ilkT . ' %' . number_format($dg[$ilkT] / $t180 * 100, 1, ',', '.') : '—', 'metin', '134E4A'), _srKpi('Tedarikçi sayısı', count($dg), 'sayi', '042F2E')],
                        [_srBlok('Tedarikçi payları (son ' . max(180, $dn['gun']) . ' gün)', 'pie', '0F766E', [['Tedarikçi', 'm'], ['Harcama', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($dg), $dg)),
                         _srBlok('Tek kaynaklı malzemeler (harcama)', 'bar', '115E59', [['Malzeme', 'm'], ['Harcama', 'tl'], ['Tek tedarikçi', 'm']], array_map(fn ($x) => [$x[0], $x[4], $x[2]], array_slice($tek, 0, 12)))], $l180->isEmpty() ? _srBos('fatura', $dn) : null),
                    _srSayfa('Tek Tedarikçili Malzemeler', 'Son ' . max(180, $dn['gun']) . ' günde yalnız tek tedarikçiden alınan malzemeler (alternatif fiyat bilgisi yok).', [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Tedarikçi', 'm', 24], ['Alış Sayısı', 's', 10], ['Harcama', 'tl', 15, 'top bar']], $tek, $l180->isEmpty() ? _srBos('fatura', $dn) : _srBos('tek_ted', $dn)),
                    _srSayfa('Tedarikçi Yoğunlaşması', 'Harcamanın tedarikçilere dağılımı; kümülatif pay.', [['Tedarikçi', 'm', 26], ['Harcama', 'tl', 15, 'top bar'], ['Pay %', 'y', 9, 'top'], ['Kümülatif %', 'y', 11]],
                        (function () use ($dg, $t180) { $k = 0; $o = []; foreach ($dg as $a => $v) { $k += $v; $o[] = [$a, round($v, 2), round($v / $t180 * 100, 1), round($k / $t180 * 100, 1)]; } return $o; })(), _srBos('fatura', $dn))];
            case 'surec':
                $s = _srSurec($sid, $dn); $dep = [];
                foreach ($s as $x) { $dep[$x[2]] ??= [[], []]; if ($x[6] !== null) $dep[$x[2]][0][] = $x[6]; if ($x[8] !== null) $dep[$x[2]][1][] = $x[8]; }
                $ort = fn ($a) => $a ? round(array_sum($a) / count($a), 1) : null; $a1 = array_filter(array_column($s, 6), fn ($x) => $x !== null); $a2 = array_filter(array_column($s, 8), fn ($x) => $x !== null);
                $bos = !$d['talep'] ? _srBos('talep_yok', $dn) : (!$d['siparis'] ? _srBos('siparis_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') açılıp siparişe dönüşen talep yok. Süre, talebin açılışı ile o talepten açılan siparişin tarihi (ve mal kabulü) arasından ölçülür; siparişi talep ekranından "siparişe çevir" ile açmak gerekir.');
                return [$pano([_srKpi('Siparişe dönen talep', count($s), 'sayi', '6D28D9'), _srKpi('Talep → sipariş (ort. gün)', $ort($a1) ?? 'Veri yok', $a1 ? 'sayi' : 'metin', '5B21B6'), _srKpi('Sipariş → teslim (ort. gün)', $ort($a2) ?? 'Veri yok', $a2 ? 'sayi' : 'metin', '4C1D95'),
                        _srKpi('Toplam (ort. gün)', ($a1 || $a2) ? round(($ort($a1) ?? 0) + ($ort($a2) ?? 0), 1) : 'Veri yok', ($a1 || $a2) ? 'sayi' : 'metin', '2E1065')],
                        [_srBlok('Departmana göre talep → sipariş (gün)', 'bar', '6D28D9', [['Departman', 'm'], ['Ort. gün', 's'], ['Talep', 's']], array_map(fn ($k, $v) => [$k, $ort($v[0]) ?? 0, count($v[0])], array_keys($dep), $dep)),
                         _srBlok('Departmana göre sipariş → teslim (gün)', 'bar', '4C1D95', [['Departman', 'm'], ['Ort. gün', 's'], ['Teslim', 's']], array_map(fn ($k, $v) => [$k, $ort($v[1]) ?? 0, count($v[1])], array_keys($dep), $dep))], !$s ? $bos : null),
                    _srSayfa('Talep Süreleri', 'Talep açılışı → siparişin tarihi → ilk mal kabul. Gün farkları.', [['Talep', 'm', 9], ['Açılış', 't', 11], ['Departman', 'm', 16], ['Aciliyet', 'm', 9], ['Sipariş', 'm', 13], ['Sipariş Tarihi', 't', 11], ['Talep→Sipariş (gün)', 's', 13, 'esik:3'], ['İlk Teslim', 't', 11], ['Sipariş→Teslim (gün)', 's', 13, 'esik:3']], $s, $bos)];
            case 'departman':
                $t = _srTablo('satinalma_talepleri') ? DB::table('satinalma_talepleri')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->get() : collect();
                $k = _srTablo('talep_kalemleri') && $t->isNotEmpty() ? DB::table('talep_kalemleri')->whereIn('talep_id', $t->pluck('id'))->get() : collect();
                $dep = $t->groupBy(fn ($x) => $x->departman ?: 'Belirtilmemiş')->map(fn ($g) => [$g->count(), $g->where('aciliyet', 'acil')->count(), $g->whereIn('durum', ['tamamlandi', 'siparis'])->count(), $g->where('durum', 'reddedildi')->count()])->sortByDesc(fn ($x) => $x[0]);
                $durum = $t->groupBy('durum')->map->count()->sortDesc(); $enCok = $k->groupBy(fn ($x) => $x->malzeme_id ? _srMalz($x->malzeme_id) : ($x->ad ?: '—'))->map(fn ($g) => [$g->count(), (float) $g->sum('miktar')])->sortByDesc(fn ($x) => $x[0])->take(15);
                $bos = !$d['talep'] ? _srBos('talep_yok', $dn) : _srBos('talep', $dn);
                return [$pano([_srKpi('Talep', $t->count(), 'sayi', 'A21CAF'), _srKpi('Acil talep oranı', $t->count() ? round($t->where('aciliyet', 'acil')->count() / $t->count() * 100, 1) : 0, 'y', '86198F'),
                        _srKpi('Tamamlanan / siparişte', $t->whereIn('durum', ['tamamlandi', 'siparis'])->count(), 'sayi', '701A75'), _srKpi('Reddedilen', $t->where('durum', 'reddedildi')->count(), 'sayi', 'BE123C')],
                        [_srBlok('Departmana göre talep', 'bar', 'A21CAF', [['Departman', 'm'], ['Talep', 's'], ['Acil', 's']], $dep->map(fn ($v, $k) => [$k, $v[0], $v[1]])->values()->all()),
                         _srBlok('Durum dağılımı', 'pie', '86198F', [['Durum', 'm'], ['Talep', 's'], ['', 'm']], $durum->map(fn ($v, $k) => [$k, $v, ''])->values()->all()),
                         _srBlok('En çok istenen malzemeler', 'bar', '701A75', [['Malzeme', 'm'], ['Talep sayısı', 's'], ['Toplam miktar', 's']], $enCok->map(fn ($v, $k) => [$k, $v[0], $v[1]])->values()->all())], $t->isEmpty() ? $bos : null),
                    _srSayfa('Departman Özeti', 'Acil oranı yüksekse planlama sorunu olabilir (veri: talep aciliyet alanı).', [['Departman', 'm', 22], ['Talep', 's', 9, 'top bar'], ['Acil', 's', 8, 'top'], ['Acil %', 'y', 9], ['Tamamlanan', 's', 11, 'top'], ['Reddedilen', 's', 10, 'top']],
                        $dep->map(fn ($v, $k) => [$k, $v[0], $v[1], $v[0] ? round($v[1] / $v[0] * 100, 1) : 0, $v[2], $v[3]])->values()->all(), $bos),
                    _srSayfa('Talepler', 'Dönemde açılan tüm talepler.', [['Talep', 'm', 9], ['Tarih', 't', 11], ['Departman', 'm', 16], ['İsteyen', 'm', 18], ['Aciliyet', 'm', 9], ['Durum', 'm', 14], ['Kalem', 's', 8, 'top']],
                        $t->map(fn ($x) => ['#' . $x->id, substr((string) $x->created_at, 0, 10), (string) ($x->departman ?: '—'), (string) ($x->isteyen_ad ?: '—'), $x->aciliyet === 'acil' ? 'ACİL' : 'Normal', (string) $x->durum, $k->where('talep_id', $x->id)->count()])->all(), $bos)];
            case 'rfq':
                $g = _srTablo('rfq_gonderim') ? DB::table('rfq_gonderim')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->get() : collect();
                $tk = _srTablo('rfq_teklif') && $g->isNotEmpty() ? DB::table('rfq_teklif')->whereIn('rfq_id', $g->pluck('id'))->get() : collect();
                $byT = $g->groupBy('tedarikci_id')->map(function ($x) use ($tk) {
                    $yan = $x->filter(fn ($r) => !empty($r->yanit_at)); $sure = $yan->map(fn ($r) => \Carbon\Carbon::parse($r->created_at)->diffInHours(\Carbon\Carbon::parse($r->yanit_at)))->all();
                    return [$x->count(), $yan->count(), $x->count() ? round($yan->count() / $x->count() * 100, 1) : 0, $sure ? round(array_sum($sure) / count($sure), 1) : null, $tk->whereIn('rfq_id', $x->pluck('id'))->count()];
                });
                $bos = !$d['rfq'] ? _srBos('rfq_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') gönderilen teklif isteği yok.';
                $fiy = $tk->filter(fn ($x) => $x->fiyat > 0)->map(fn ($x) => [_srTed($x->tedarikci_id), $x->malzeme_id ? _srMalz($x->malzeme_id) : (string) $x->urun_ad, (float) $x->fiyat, (string) $x->kaynak]);
                return [$pano([_srKpi('Teklif isteği', $g->count(), 'sayi', '0369A1'), _srKpi('Yanıt oranı', $g->count() ? round($g->filter(fn ($x) => !empty($x->yanit_at))->count() / $g->count() * 100, 1) : 0, 'y', '075985'),
                        _srKpi('Gelen fiyat satırı', $tk->count(), 'sayi', '0C4A6E'), _srKpi('Teklif istenen tedarikçi', $g->pluck('tedarikci_id')->unique()->count(), 'sayi', '082F49')],
                        [_srBlok('Tedarikçiye göre yanıt oranı %', 'bar', '0369A1', [['Tedarikçi', 'm'], ['Yanıt %', 'y'], ['İstek', 's']], $byT->map(fn ($v, $k) => [_srTed($k), $v[2], $v[0]])->values()->all())], $g->isEmpty() ? $bos : null),
                    _srSayfa('Tedarikçi Yanıtları', 'Yanıt süresi = istek gönderimi → tedarikçi yanıtı (saat).', [['Tedarikçi', 'm', 24], ['İstek', 's', 8, 'top'], ['Yanıt', 's', 8, 'top'], ['Yanıt %', 'y', 9, 'bar'], ['Ort. Yanıt (saat)', 's', 12], ['Gelen Fiyat Satırı', 's', 12, 'top']],
                        $byT->map(fn ($v, $k) => [_srTed($k), $v[0], $v[1], $v[2], $v[3], $v[4]])->values()->all(), $bos),
                    _srSayfa('Gelen Teklif Fiyatları', 'Tedarikçilerden gelen teklif satırları (portal / e-posta / WhatsApp).', [['Tedarikçi', 'm', 22], ['Malzeme / Ürün', 'm', 26], ['Fiyat', 'tl', 12], ['Kaynak', 'm', 12]], $fiy->values()->all(), $bos)];
            case 'skt':
                $p = _srSktPartiler($sid, 30); $gecmis = array_filter($p, fn ($x) => $x['kalan_gun'] < 0); $yedi = array_filter($p, fn ($x) => $x['kalan_gun'] >= 0 && $x['kalan_gun'] <= 7);
                $bos = !$d['parti'] ? _srBos('parti_yok', $dn) : _srBos('skt', $dn);
                return [$pano([_srKpi('Riskli parti (30 gün)', count($p), 'sayi', 'B91C1C'), _srKpi('Risk altındaki değer', array_sum(array_column($p, 'deger')), 'tl', '991B1B'),
                        _srKpi('SKT\'si geçmiş', count($gecmis), 'sayi', '7F1D1D'), _srKpi('7 gün içinde dolacak', count($yedi), 'sayi', 'DC2626')],
                        [_srBlok('Değere göre riskli partiler', 'bar', 'B91C1C', [['Malzeme · SKT', 'm'], ['Değer', 'tl'], ['Kalan gün', 's']], array_map(fn ($x) => [$x['malzeme'] . ' · ' . \Carbon\Carbon::parse($x['skt'])->format('d.m'), $x['deger'], $x['kalan_gun']], array_slice(collect($p)->sortByDesc('deger')->values()->all(), 0, 12)))], !$p ? $bos : null),
                    _srSayfa('Riskli Partiler', 'Stokta kalan ve SKT\'si 30 gün içinde dolan ya da geçmiş partiler. Değer = kalan miktar × parti birim maliyeti. Kırmızı: SKT geçmiş (kalan gün < 0).',
                        [['Malzeme', 'm', 22], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 7], ['Değer', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 20]],
                        array_map(fn ($x) => [$x['malzeme'], $x['lot'], $x['skt'], $x['kalan_gun'], $x['kalan'], $x['birim'], $x['deger'], $x['ted']], $p), $bos)];
            case 'fire':
                $fr = _srFireler($sid, $dn); $byM = []; foreach ($fr['hareket'] as $x) $byM[$x['malzeme']] = ($byM[$x['malzeme']] ?? 0) + $x['maliyet']; arsort($byM);
                $bySeb = []; foreach ($fr['hareket'] as $x) { $s = $x['aciklama'] ?: 'Açıklamasız'; $bySeb[$s] = ($bySeb[$s] ?? 0) + $x['maliyet']; } arsort($bySeb);
                return [$pano([_srKpi('Fire maliyeti', array_sum(array_column(array_filter($fr['hareket'], fn ($x) => $x['tip'] === 'Fire'), 'maliyet')), 'tl', 'C2410C'),
                        _srKpi('İade maliyeti', array_sum(array_column(array_filter($fr['hareket'], fn ($x) => $x['tip'] === 'İade'), 'maliyet')), 'tl', '9A3412'),
                        _srKpi('Sayım farkı (net)', array_sum(array_column($fr['sayim'], 'maliyet')), 'tl', '7C2D12'), _srKpi('Fire / iade kaydı', count($fr['hareket']), 'sayi', '431407')],
                        [_srBlok('Malzemeye göre fire + iade', 'bar', 'C2410C', [['Malzeme', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_slice(array_keys($byM), 0, 12), array_slice($byM, 0, 12))),
                         _srBlok('Sebebe göre', 'pie', '9A3412', [['Sebep', 'm'], ['Maliyet', 'tl'], ['', 'm']], array_map(fn ($k, $v) => [$k, round($v, 2), ''], array_keys($bySeb), $bySeb))],
                        (!$fr['hareket'] && !$fr['sayim']) ? (!$d['fire'] && !$d['sayim'] ? _srBos('fire_yok', $dn) : _srBos('fire', $dn)) : null), _srFireSayfa($fr, $dn, $d), _srSayimSayfa($fr, $dn, $d)];
            case 'devir':
                $tk = _srTablo('stok_hareketleri') ? DB::table('stok_hareketleri')->where('sube_id', $sid)->where('tip', 'tuketim')->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->selectRaw('malzeme_id, SUM(-miktar) t')->groupBy('malzeme_id')->pluck('t', 'malzeme_id') : collect();
                $st = _srStokMevcut($sid); $o = [];
                foreach ($tk as $mid => $t) { $m = _srMeta()['malz'][$mid] ?? null; if (!$m || $t <= 0) continue; $gunluk = $t / max(1, $dn['gun']); $mev = (float) ($st[$mid] ?? 0);
                    $o[] = [$m->ad, _srKat($mid), _srMeta()['birim'][$m->temel_birim_id] ?? '', round($t, 3), round($gunluk, 3), round($mev, 3), $gunluk > 0 ? round($mev / $gunluk, 1) : null, round($mev * (float) $m->guncel_maliyet, 2), (float) $m->kritik_stok > 0 && $mev < (float) $m->kritik_stok ? 'Evet' : ''];
                }
                usort($o, fn ($a, $b) => ($b[6] ?? 0) <=> ($a[6] ?? 0));
                $bos = !$d['tuketim'] ? _srBos('tuketim_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') stok tüketimi kaydı yok.';
                return [$pano([_srKpi('Tüketilen malzeme', count($o), 'sayi', '15803D'), _srKpi('Stok değeri (bu malzemeler)', array_sum(array_column($o, 7)), 'tl', '166534'),
                        _srKpi('30+ gün stoklu', count(array_filter($o, fn ($x) => ($x[6] ?? 0) > 30)), 'sayi', 'B45309'), _srKpi('Kritik altında', count(array_filter($o, fn ($x) => $x[8] === 'Evet')), 'sayi', 'B91C1C')],
                        [_srBlok('Stokta kalma günü en yüksek', 'bar', '15803D', [['Malzeme', 'm'], ['Gün', 's'], ['Stok değeri', 'tl']], array_map(fn ($x) => [$x[0], $x[6] ?? 0, $x[7]], array_slice($o, 0, 12)))], !$o ? $bos : null),
                    _srSayfa('Devir Hızı', 'Günlük tüketim = dönem tüketimi ÷ gün. Stokta kalma = mevcut stok ÷ günlük tüketim. Değer = mevcut × güncel maliyet.',
                        [['Malzeme', 'm', 22], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Dönem Tüketimi', 's', 12], ['Günlük Tüketim', 's', 12], ['Mevcut Stok', 's', 11], ['Stokta Kalma (gün)', 's', 13, 'bar'], ['Stok Değeri', 'tl', 13, 'top'], ['Kritik Altında', 'm', 10]], $o, $bos)];
            case 'borc':
                $b = _srBorclar($sid);
                return [$pano([_srKpi('Toplam bakiye (borç)', array_sum(array_column($b, 3)), 'tl', '1D4ED8'), _srKpi('Son 30 gün borçlanma', array_sum(array_column($b, 4)), 'tl', '1E40AF'),
                        _srKpi('Borçlu olunan tedarikçi', count(array_filter($b, fn ($x) => $x[3] > 0)), 'sayi', '1E3A8A'), _srKpi('Toplam ödeme', array_sum(array_column($b, 2)), 'tl', '172554')],
                        [_srBlok('Tedarikçi bakiyeleri', 'bar', '1D4ED8', [['Tedarikçi', 'm'], ['Bakiye', 'tl'], ['Son ödeme', 't']], array_map(fn ($x) => [$x[0], $x[3], $x[7]], array_filter($b, fn ($x) => $x[3] > 0)))], !$b ? _srBos('cari_yok', $dn) : null),
                    _srBorcSayfa($b, $dn, $d)];
            case 'siparissiz':
                $f = _srTablo('alis_faturalari') ? DB::table('alis_faturalari')->where('sube_id', $sid)->whereDate('tarih', '>=', $dn['bas'])->whereDate('tarih', '<=', $dn['bit'])->get() : collect();
                $mkSip = _srTablo('mal_kabuller') ? DB::table('mal_kabuller')->whereNotNull('siparis_id')->pluck('id')->flip() : collect();
                $ss = $f->filter(fn ($x) => empty($x->mal_kabul_id) || !isset($mkSip[$x->mal_kabul_id])); $fTop = (float) $f->sum('toplam'); $sTop = (float) $ss->sum('toplam');
                $byT = $ss->groupBy('tedarikci_id')->map(fn ($g) => [$g->count(), (float) $g->sum('toplam')])->sortByDesc(fn ($x) => $x[1]);
                $bos = $f->isEmpty() ? _srBos('fatura', $dn) : _srBos('siparissiz', $dn);
                return [$pano([_srKpi('Siparişsiz fatura', $ss->count() . ' / ' . $f->count(), 'metin', 'B45309'), _srKpi('Siparişsiz tutar', $sTop, 'tl', '92400E'), _srKpi('Toplam içindeki payı', $fTop > 0 ? round($sTop / $fTop * 100, 1) : 0, 'y', '78350F'),
                        _srKpi('Sipariş modülü', $d['siparis'] ? 'Kullanılıyor' : 'Kullanılmıyor', 'metin', '451A03')],
                        [_srBlok('Tedarikçiye göre siparişsiz tutar', 'bar', 'B45309', [['Tedarikçi', 'm'], ['Tutar', 'tl'], ['Fatura', 's']], $byT->map(fn ($v, $k) => [_srTed($k), $v[1], $v[0]])->values()->all())], $f->isEmpty() ? $bos : null),
                    _srSayfa('Siparişsiz Faturalar', 'Siparişe bağlı bir mal kabulle eşleşmeyen alış faturaları (doğrudan girilen ya da siparişsiz teslim alınan).' . (!$d['siparis'] ? ' Not: işletme sipariş modülünü kullanmadığı için tüm faturalar siparişsiz görünür.' : ''),
                        [['Tarih', 't', 11], ['Tedarikçi', 'm', 26], ['Fatura No', 'm', 16], ['Tutar', 'tl', 15, 'top bar'], ['Mal Kabul', 'm', 12]],
                        $ss->sortByDesc('tarih')->map(fn ($x) => [substr((string) $x->tarih, 0, 10), _srTed($x->tedarikci_id), (string) ($x->fatura_no ?: '#' . $x->id), (float) $x->toplam, $x->mal_kabul_id ? 'Var (siparişsiz)' : 'Yok'])->values()->all(), $bos)];
            case 'sube':
                $p = function_exists('_posPersonel') ? _posPersonel() : null; $sahip = !$p || ($p->rol ?? '') === 'sahip';
                $subeler = _srTablo('subeler') ? DB::table('subeler')->pluck('ad', 'id') : collect();
                $o = []; $bos = _srBos('tek_sube', $dn);
                if ($sahip && $subeler->count() > 1) {
                    $hepsi = DB::table('alis_fatura_kalemleri as k')->join('alis_faturalari as f', 'k.fatura_id', '=', 'f.id')->whereDate('f.tarih', '>=', $dn['bas'])->whereDate('f.tarih', '<=', $dn['bit'])->where('k.birim_fiyat', '>', 0)
                        ->get(['k.malzeme_id', 'k.alis_birim_id', 'k.miktar', 'k.birim_fiyat', 'k.satir_toplam', 'f.sube_id']);
                    foreach ($hepsi->groupBy(fn ($x) => $x->malzeme_id . '|' . $x->alis_birim_id) as $key => $g) {
                        $bs = $g->groupBy('sube_id'); if ($bs->count() < 2) continue;
                        $ort = $bs->map(fn ($x) => (float) $x->sum(fn ($y) => (float) ($y->satir_toplam ?: $y->miktar * $y->birim_fiyat)) / max(0.0001, (float) $x->sum('miktar')))->sort();
                        $min = $ort->first(); [$mid, $bid] = explode('|', $key);
                        foreach ($ort as $sb => $v) $o[] = [_srMalz($mid), _srMeta()['birim'][$bid] ?? '', $subeler[$sb] ?? ('Şube ' . $sb), round($v, 2), $sb == $ort->keys()->first() ? 'EN UCUZ' : '', _srYuzde($v, $min) ?? 0, (float) $bs[$sb]->sum('miktar')];
                    }
                    $bos = _srBos('cok_sube', $dn);
                }
                return [$pano([_srKpi('Şube', $subeler->count(), 'sayi', '0D9488'), _srKpi('Ortak alınan malzeme', count(array_unique(array_column($o, 0))), 'sayi', '0F766E'),
                        _srKpi('En büyük fark', $o ? max(array_column($o, 5)) : 0, 'y', '115E59')],
                        [_srBlok('En büyük şube farkları %', 'bar', '0D9488', [['Malzeme · şube', 'm'], ['En ucuza göre %', 'y'], ['Ort. fiyat', 'tl']], array_map(fn ($x) => [$x[0] . ' · ' . $x[2], $x[5], $x[3]], array_slice(collect($o)->sortByDesc(5)->filter(fn ($x) => $x[5] > 0)->values()->all(), 0, 12)))], !$o ? $bos : null),
                    _srSayfa('Şubelere Göre Fiyat', 'Aynı malzeme ve aynı alış birimiyle en az 2 şubenin aldığı kalemler; miktar ağırlıklı ortalama fiyat.', [['Malzeme', 'm', 22], ['Birim', 'm', 7], ['Şube', 'm', 22], ['Ort. Fiyat', 'tl', 12], ['', 'm', 9], ['En Ucuza Göre %', 'y', 12, 'esik:5'], ['Miktar', 's', 10]], $o, $bos)];
        }
        return [$pano([], [], 'Bilinmeyen rapor.')];
    }
}

// ---------- rapor yardımcıları (endeks, menü, bütçe, uyum, süreç, fire, borç sayfaları) ----------
if (!function_exists('_srEndeks')) {
    // Laspeyres: sabit sepet (ilk ay miktarları), aylık ort. fiyatla. Sepet = ilk ve son ayda alınan malzemeler (aynı birim).
    function _srEndeks($sid, $ay, $bit = null)
    {
        $bit = $bit ?: today()->toDateString(); $bas = \Carbon\Carbon::parse($bit)->startOfMonth()->subMonths($ay - 1)->toDateString();
        $l = _srAlis($sid, $bas, $bit); $aylar = []; $p = [];
        foreach ($l as $x) { $k = $x->malzeme_id . '|' . $x->alis_birim_id; $a = substr($x->tarih, 0, 7); $p[$k][$a]['t'] = ($p[$k][$a]['t'] ?? 0) + $x->tutar; $p[$k][$a]['m'] = ($p[$k][$a]['m'] ?? 0) + (float) $x->miktar; $aylar[$a] = 1; }
        ksort($aylar); $aylar = array_keys($aylar); if (count($aylar) < 2) return ['aylar' => [], 'sepet' => [], 'etki' => []];
        $a0 = $aylar[0]; $sepet = [];
        foreach ($p as $k => $ay2) if (isset($ay2[$a0]) && $ay2[$a0]['m'] > 0) $sepet[$k] = ['q' => $ay2[$a0]['m'], 'p0' => $ay2[$a0]['t'] / $ay2[$a0]['m']];
        $son = []; $out = []; $once = null; $taban = array_sum(array_map(fn ($s) => $s['q'] * $s['p0'], $sepet));
        foreach ($aylar as $a) {
            $deg = 0; foreach ($sepet as $k => $s) { if (isset($p[$k][$a]) && $p[$k][$a]['m'] > 0) $son[$k] = $p[$k][$a]['t'] / $p[$k][$a]['m']; $deg += $s['q'] * ($son[$k] ?? $s['p0']); }
            $e = $taban > 0 ? $deg / $taban * 100 : 100; $out[] = ['ay' => $a, 'endeks' => $e, 'degisim' => $once ? round(($e - $once) / $once * 100, 1) : null]; $once = $e;
        }
        $etki = [];
        foreach ($sepet as $k => $s) { [$mid] = explode('|', $k); $pn = $son[$k] ?? $s['p0']; $etki[] = [_srMalz($mid), round($s['p0'], 2), round($pn, 2), _srYuzde($pn, $s['p0']), $taban > 0 ? round($s['q'] * ($pn - $s['p0']) / $taban * 100, 2) : 0, $taban > 0 ? round($s['q'] * $s['p0'] / $taban * 100, 1) : 0]; }
        usort($etki, fn ($a, $b) => abs($b[4]) <=> abs($a[4]));
        return ['aylar' => $out, 'sepet' => $sepet, 'etki' => $etki];
    }
    function _srEndeksSayfa(array $e, array $dn)
    {
        return _srSayfa('Fiyat Endeksi', 'Laspeyres: ilk ayda alınan malzemelerin ilk ay miktarları sabit tutulur, her ayın ortalama alış fiyatıyla maliyeti hesaplanır (ilk ay = 100). O ay alınmayan malzemede son bilinen fiyat kullanılır.',
            [['Ay', 'm', 14], ['Endeks', 's', 11, 'bar'], ['Aylık Değişim %', 'y', 13, 'esik:3']], array_map(fn ($a) => [_fr2AyAd($a['ay']), round($a['endeks'], 1), $a['degisim']], $e['aylar']), _srBos('endeks', $dn));
    }
    // Menü etkisi: reçete hammaddesi (temel birim) × (dönem başı fiyat → güncel fiyat). Dönem başı = dönemden önceki son alış (yoksa dönemin ilk alışı)
    function _srMenuEtki($sid, array $dn)
    {
        if (!_srTablo('receteler') || !_srTablo('urunler') || !function_exists('_restoReceteHammadde')) return [];
        $l = _srAlis($sid, $dn['bas'], $dn['bit'], 365); $once = []; $son = [];
        foreach ($l as $x) { $tf = _srTemelFiyat($x->malzeme_id, $x->alis_birim_id, $x->birim_fiyat); if ($x->tarih < $dn['bas']) $once[$x->malzeme_id] = $tf; else { $once[$x->malzeme_id] ??= $tf; $son[$x->malzeme_id] = $tf; } }
        if (!$son) return [];
        $out = [];
        $rec = DB::table('receteler as r')->join('urunler as u', 'r.urun_id', '=', 'u.id')->whereNotNull('r.urun_id')->get(['r.id', 'u.ad', 'u.fiyat']);
        foreach ($rec as $r) {
            $h = []; try { _restoReceteHammadde($r->id, 1, $h); } catch (\Throwable $e) { continue; }
            $c0 = 0; $c1 = 0; $etkili = false; $surucu = null; $sMax = 0;
            foreach ($h as $mid => $q) { $p0 = $once[$mid] ?? $son[$mid] ?? null; $p1 = $son[$mid] ?? $p0; if ($p0 === null) continue; $c0 += $q * $p0; $c1 += $q * $p1;
                if (abs($p1 - $p0) > 0.00001) { $etkili = true; if (abs($q * ($p1 - $p0)) > $sMax) { $sMax = abs($q * ($p1 - $p0)); $surucu = _srMalz($mid); } } }
            if (!$etkili || $c0 <= 0) continue;
            $fiyat = (float) $r->fiyat;
            $out[] = [$r->ad, round($fiyat, 2), round($c0, 2), round($c1, 2), round($c1 - $c0, 2), _srYuzde($c1, $c0), $fiyat > 0 ? round($c0 / $fiyat * 100, 1) : null, $fiyat > 0 ? round($c1 / $fiyat * 100, 1) : null, $surucu];
        }
        usort($out, fn ($a, $b) => $b[4] <=> $a[4]);
        return $out;
    }
    function _srMenuSayfa(array $menu, array $dn, array $d)
    {
        return _srSayfa('Menüye Zam Etkisi', 'Ürün maliyeti = reçetedeki hammaddeler (alt reçeteler dahil) × alış fiyatı (temel birime çevrilmiş). Önce = dönemden önceki son alış fiyatı; sonra = dönemdeki son alış. Food cost = maliyet ÷ satış fiyatı.',
            [['Ürün', 'm', 24], ['Satış Fiyatı', 'tl', 12], ['Maliyet (önce)', 'tl', 12], ['Maliyet (sonra)', 'tl', 12], ['Maliyet Farkı', 'tl', 12, 'bar'], ['Değişim %', 'y', 10, 'esik:5'], ['Food Cost Önce %', 'y', 11], ['Food Cost Sonra %', 'y', 11], ['En Çok Etkileyen', 'm', 18]],
            $menu, !$d['recete'] ? _srBos('recete_yok', $dn) : _srBos('recete', $dn));
    }
    function _srButceSayfa($sid, array $dn, array $d)
    {
        $o = []; for ($i = 11; $i >= 0; $i--) { $ym = today()->startOfMonth()->subMonths($i)->format('Y-m'); $b = _srButceAy($sid, $ym); $o[] = [_fr2AyAd($ym), $b['butce'] ?: null, $b['siparis'], $b['fatura'], $b['butce'] > 0 ? round($b['siparis'] / $b['butce'] * 100, 1) : null, $b['butce'] > 0 ? round($b['siparis'] - $b['butce'], 2) : null]; }
        $o = array_values(array_filter($o, fn ($r) => $r[1] || $r[2] || $r[3])); // tamamen boş ayları gizle
        return _srSayfa('Bütçe Gerçekleşme (12 ay)', 'Bütçe = Satınalma Bütçe ekranındaki aylık tutar. Sipariş = iptal/taslak dışı siparişlerin genel toplamı; Fatura = alış faturaları toplamı. Kullanım = sipariş ÷ bütçe.',
            [['Ay', 'm', 12], ['Bütçe', 'tl', 14, 'top'], ['Sipariş', 'tl', 14, 'top bar'], ['Fatura', 'tl', 14, 'top'], ['Kullanım %', 'y', 11, 'esik:100.01'], ['Sapma', 'tl', 13]], $o, !$d['butce'] ? _srBos('butce_yok', $dn) : null);
    }
    function _srUyum($sid, array $dn)
    {
        if (!_srTablo('mal_kabuller') || !_srTablo('satinalma_siparis_kalemleri') || !_srKolon('alis_faturalari', 'mal_kabul_id')) return [];
        $f = DB::table('alis_faturalari as f')->join('mal_kabuller as m', 'f.mal_kabul_id', '=', 'm.id')->join('satinalma_siparisleri as s', 'm.siparis_id', '=', 's.id')
            ->where('f.sube_id', $sid)->whereDate('f.tarih', '>=', $dn['bas'])->whereDate('f.tarih', '<=', $dn['bit'])->get(['f.id', 'f.fatura_no', 'f.tarih', 'f.tedarikci_id', 's.id as sid', 's.siparis_no']);
        if ($f->isEmpty()) return [];
        $fk = DB::table('alis_fatura_kalemleri')->whereIn('fatura_id', $f->pluck('id'))->get()->groupBy('fatura_id');
        $sk = DB::table('satinalma_siparis_kalemleri')->whereIn('siparis_id', $f->pluck('sid'))->get()->groupBy('siparis_id'); $o = [];
        foreach ($f as $x) foreach ($fk[$x->id] ?? [] as $k) {
            $s = collect($sk[$x->sid] ?? [])->first(fn ($y) => (int) $y->malzeme_id === (int) $k->malzeme_id && (int) $y->alis_birim_id === (int) $k->alis_birim_id); if (!$s || (float) $s->birim_fiyat <= 0) continue;
            $fy = (float) $k->birim_fiyat; $sy = (float) $s->birim_fiyat;
            $o[] = [$x->siparis_no ?: '#' . $x->sid, _srTed($x->tedarikci_id), _srMalz($k->malzeme_id), (string) ($x->fatura_no ?: '#' . $x->id), substr((string) $x->tarih, 0, 10), $sy, $fy, _srYuzde($fy, $sy), round(($fy - $sy) * (float) $k->miktar, 2)];
        }
        return $o;
    }
    function _srSurec($sid, array $dn)
    {
        if (!_srTablo('satinalma_talepleri') || !_srKolon('satinalma_siparisleri', 'talep_id')) return [];
        $t = DB::table('satinalma_talepleri')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->get(); if ($t->isEmpty()) return [];
        $s = DB::table('satinalma_siparisleri')->whereIn('talep_id', $t->pluck('id'))->where('durum', '!=', 'taslak')->orderBy('tarih')->get()->groupBy('talep_id');
        $mk = _srMkBySiparis($sid, $s->flatten()->pluck('id')->all()); $o = [];
        foreach ($t as $x) {
            $sp = ($s[$x->id] ?? collect())->first(); if (!$sp) continue;
            $ac = substr((string) $x->created_at, 0, 10); $st = substr((string) $sp->tarih, 0, 10); $m = ($mk[$sp->id] ?? collect())->first(); $te = $m ? substr((string) $m->tarih, 0, 10) : null;
            $o[] = ['#' . $x->id, $ac, (string) ($x->departman ?: 'Belirtilmemiş'), $x->aciliyet === 'acil' ? 'ACİL' : 'Normal', $sp->siparis_no ?: '#' . $sp->id, $st, (int) \Carbon\Carbon::parse($ac)->diffInDays(\Carbon\Carbon::parse($st)), $te, $te ? (int) \Carbon\Carbon::parse($st)->diffInDays(\Carbon\Carbon::parse($te)) : null];
        }
        return $o;
    }
    function _srFireSayfa(array $fr, array $dn, array $d)
    {
        return _srSayfa('Fire ve İade Kayıtları', 'Maliyet = miktar × kayıttaki birim maliyet.', [['Tarih', 't', 11], ['Tür', 'm', 7], ['Malzeme', 'm', 22], ['Kategori', 'm', 14], ['Miktar', 's', 9], ['Birim', 'm', 7], ['Maliyet', 'tl', 12, 'top bar'], ['Açıklama / Sebep', 'm', 26]],
            array_map(fn ($x) => [$x['tarih'], $x['tip'], $x['malzeme'], $x['kategori'], $x['miktar'], $x['birim'], $x['maliyet'], $x['aciklama']], $fr['hareket']), !$d['fire'] ? _srBos('fire_yok', $dn) : _srBos('fire', $dn));
    }
    function _srSayimSayfa(array $fr, array $dn, array $d)
    {
        return _srSayfa('Sayım Farkları', 'Fark = sayılan − teorik (sistem) stok. Eksi = kayıp, artı = fazla.', [['Sayım Tarihi', 't', 11], ['Malzeme', 'm', 22], ['Teorik', 's', 10], ['Sayılan', 's', 10], ['Fark', 's', 9], ['Fark Maliyeti', 'tl', 13, 'top bar']],
            array_map(fn ($x) => [$x['tarih'], $x['malzeme'], $x['teorik'], $x['sayilan'], $x['fark'], $x['maliyet']], $fr['sayim']),
            !$d['sayim'] ? 'Sayım yapılmamış: son 1 yılda envanter sayımı kaydı yok. Sayım farkı, sistemdeki teorik stok ile sayılan miktarın farkıdır. Dolması için Satın Alma > Sayım (Envanter)\'den sayım yapın.' : 'Seçili dönemde (' . $dn['etiket'] . ') sayım farkı yok.');
    }
    function _srBorcSayfa(array $b, array $dn, array $d)
    {
        return _srSayfa('Tedarikçi Borç Durumu', 'Borç = tedarikçi carisine yazılan alış borçları; ödeme = cariye işlenen ödemeler. Bakiye = borç − ödeme. 30/60/90 gün = son N günde yazılan borç.',
            [['Tedarikçi', 'm', 26], ['Toplam Borç', 'tl', 14, 'top'], ['Toplam Ödeme', 'tl', 14, 'top'], ['Bakiye', 'tl', 14, 'top bar'], ['Son 30 Gün Borç', 'tl', 13, 'top'], ['Son 60 Gün', 'tl', 13], ['Son 90 Gün', 'tl', 13], ['Son Ödeme', 't', 11]], $b, _srBos('cari_yok', $dn));
    }
}

// ---------- AI dönem özeti (yalnız AI bölümünde; "AI hazırladı" etiketli) ----------
if (!function_exists('_srAiOzet')) {
    function _srAiBaglam($sid, array $dn)
    {
        $l = _srAlis($sid, $dn['bas'], $dn['bit']); $lo = _srAlis($sid, $dn['obas'], $dn['obit']); $deg = _srDegisimler($sid, $dn); $d = _srVeriDurumu($sid);
        $ts = _srTeslimler($sid, $dn); $tarihli = array_filter($ts, fn ($x) => $x['gecikme'] !== null); $bu = _srButceAy($sid, today()->format('Y-m')); $fr = _srFireler($sid, $dn);
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(),
            'kullanilan_moduller' => $d, 'alis_toplam' => _srToplam($l), 'onceki_alis_toplam' => _srToplam($lo), 'fatura_sayisi' => $l->pluck('fatura_id')->unique()->count(),
            'kategori' => array_slice(_srDagilim($l, fn ($x) => _srKat($x->malzeme_id)), 0, 10, true), 'tedarikci' => array_slice(_srDagilim($l, fn ($x) => _srTed($x->tedarikci_id)), 0, 10, true),
            'fiyat_degisimleri' => array_map(fn ($x) => array_intersect_key($x, array_flip(['malzeme', 'tarih', 'ted', 'onceki_ted', 'onceki', 'yeni', 'yuzde', 'etki'])), array_slice(collect($deg)->sortByDesc(fn ($x) => abs($x['etki']))->values()->all(), 0, 15)),
            'teslim' => ['teslim_edilen' => count($ts), 'zamaninda_yuzde' => $tarihli ? round(count(array_filter($tarihli, fn ($x) => $x['gecikme'] <= 0)) / count($tarihli) * 100, 1) : null, 'tedarikci' => array_slice(_srTeslimTedarikci($ts), 0, 8)],
            'acik_siparis' => _srAcikSiparis($sid)->count(), 'geciken_siparis' => _srAcikSiparis($sid)->filter(fn ($s) => $s->beklenen_teslim && substr((string) $s->beklenen_teslim, 0, 10) < today()->toDateString())->count(),
            'onay_bekleyen_talep' => count(_srOnayBekleyen($sid)), 'kritik_stok' => array_slice(_srKritikStok($sid), 0, 10), 'skt_30_gun' => array_slice(_srSktPartiler($sid, 30), 0, 10),
            'bu_ay_butce' => $bu, 'fire_iade_maliyeti' => array_sum(array_column($fr['hareket'], 'maliyet')), 'sayim_farki' => array_sum(array_column($fr['sayim'], 'maliyet')),
            'menu_zam_etkisi' => array_slice(_srMenuEtki($sid, $dn), 0, 8)];
    }
    function _srAiOzet($sid, array $dn, $soru = '', $yenile = false)
    {
        $gunKey = 'sr_ai_gun_' . $sid . '_' . date('Y-m-d');
        if ((int) cache()->get($gunKey, 0) >= 40) return ['ok' => 0, 'hata' => 'Bugünkü AI hakkı doldu (40). Yarın tekrar dene.'];
        $b = _srAiBaglam($sid, $dn);
        if (!$b['alis_toplam'] && !$b['acik_siparis'] && !$b['onay_bekleyen_talep']) return ['ok' => 0, 'hata' => 'Bu dönemde analiz edilecek satın alma verisi yok (fatura, açık sipariş, talep yok).'];
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['ok' => 0, 'hata' => 'AI anahtarı tanımlı değil.'];
        $sys = 'Sen bir restoranın satın alma departmanında üst yönetime rapor hazırlayan analistsin. Türkçe, kısa ve rakamlı yaz; yalnız verilen veriyi kullan, rakam uydurma, kendin toplama/çarpma yapma. '
            . 'Para TL; sayıları Türkçe biçimde yaz (1.452,00 TL, %12,5). "kullanilan_moduller" 0 olan modülden sonuç çıkarma (örn. sipariş modülü kullanılmıyorsa teslim performansı yorumu yapma; bunun yerine "veri yok" de). Bugün ' . today()->format('d.m.Y') . '.';
        if (trim((string) $soru) !== '') {
            $r = _fr2AiCagir($sys . ' Soruyu veriye dayanarak kısa paragraflar ve madde işaretleriyle cevapla. Markdown başlık kullanma.', 'Veri: ' . json_encode($b, JSON_UNESCAPED_UNICODE) . "\n\nSoru: " . mb_substr((string) $soru, 0, 500), 1200);
            if (!empty($r['hata'])) return ['ok' => 0, 'hata' => $r['hata']];
            cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
            return ['ok' => 1, 'cevap' => trim(preg_replace(['/^#{1,6}\s*/m', '/\*\*(.+?)\*\*/s', '/^\s*[-*]\s+/m'], ['', '$1', '• '], $r['metin']))];
        }
        $fp = 'sr_ai_' . $sid . '_' . md5(json_encode($b));
        if (!$yenile && ($c = cache()->get($fp))) return ['ok' => 1, 'analiz' => $c, 'onbellek' => 1];
        $str = ['type' => 'string']; $liste = fn ($desc) => ['type' => 'array', 'maxItems' => 5, 'description' => $desc, 'items' => ['type' => 'object', 'properties' => ['baslik' => $str, 'detay' => $str], 'required' => ['baslik', 'detay']]];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001', 'max_tokens' => 2000,
                'system' => $sys . ' Her detay en fazla 2 cümle, somut malzeme/tedarikçi adı ve verideki rakamla.',
                'tools' => [['name' => 'yonetici_ozeti', 'description' => 'Dönem yönetici özeti', 'input_schema' => ['type' => 'object', 'required' => ['ozet', 'one_cikanlar', 'riskler', 'oneriler'], 'properties' => [
                    'ozet' => $str + ['description' => '3-4 cümle dönem özeti (harcama, önceki döneme göre, en önemli gelişme)'],
                    'one_cikanlar' => $liste('3-5 öne çıkan gelişme'), 'riskler' => $liste('2-4 risk / dikkat edilecek konu'), 'oneriler' => $liste('2-4 öneri / yapılacak iş')]]]],
                'tool_choice' => ['type' => 'tool', 'name' => 'yonetici_ozeti'],
                'messages' => [['role' => 'user', 'content' => 'Satın alma dönem verisi: ' . json_encode($b, JSON_UNESCAPED_UNICODE)]]]);
            if (!$resp->successful()) return ['ok' => 0, 'hata' => 'AI servisi şu an cevap vermedi (' . $resp->status() . ').'];
            $j = null; foreach ((array) $resp->json('content') as $c) if (($c['type'] ?? '') === 'tool_use') $j = $c['input'] ?? null;
            if (!is_array($j)) return ['ok' => 0, 'hata' => 'AI yanıtı eksik geldi, tekrar dene.'];
        } catch (\Throwable $e) { return ['ok' => 0, 'hata' => 'AI servisine bağlanılamadı.']; }
        $j['_zaman'] = now()->format('d.m.Y H:i'); $j['_donem'] = $dn['etiket'];
        cache()->put($fp, $j, now()->addHours(12)); cache()->put('sr_ai_son_' . $sid, $j, now()->addDays(30));
        cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
        return ['ok' => 1, 'analiz' => $j];
    }
    // AI özeti raporu (Excel/PDF): AI sayfası + dayanak (aylık/haftalık panodaki rakamlar)
    function _srAiSayfalar($sid, array $dn, array $ai)
    {
        $uyari = 'Bu sayfa YAPAY ZEKÂ tarafından hazırlanmıştır (' . ($ai['_zaman'] ?? '') . ', dönem ' . ($ai['_donem'] ?? $dn['etiket']) . '). Öneriler karar desteğidir; son karar satın almacınındır. Rakamlar sonraki "Dayanak" sayfalarında doğrulanabilir.';
        $sat = [['Dönem özeti', '', $ai['ozet'] ?? '']];
        foreach (['one_cikanlar' => 'Öne çıkan', 'riskler' => 'Risk', 'oneriler' => 'Öneri'] as $k => $ad) foreach ((array) ($ai[$k] ?? []) as $x) $sat[] = [$ad, $x['baslik'] ?? '', $x['detay'] ?? ''];
        $s = [['ad' => 'AI Yönetici Özeti', 'ai' => 1, 'not' => $uyari, 'kol' => [['Bölüm', 'm', 16], ['Başlık', 'm', 34], ['AI Yorumu', 'm', 90]], 'sat' => $sat]];
        foreach (_srRapor($dn['gun'] > 20 ? 'aylik' : 'haftalik', $sid, $dn) as $i => $x) { if ($i === 0) { $x['ad'] = 'Dayanak - Pano'; $s = array_merge([$x], $s); continue; } $x['ad'] = mb_substr('Dayanak - ' . $x['ad'], 0, 31); $s[] = $x; }
        return $s;
    }
}

// =====================================================================
// ROTALAR
// =====================================================================
if (!function_exists('_srErisim')) {
    function _srErisim()
    {
        $bp = null; try { $bp = _posPersonel(); } catch (\Throwable $e) {}
        if (!$bp) return true;
        return function_exists('_satinalmaErisim') ? _satinalmaErisim($bp) : true;
    }
    function _srIstekDonem(Request $r, $varsayilan = 'bu_ay') { return _srDonem((string) $r->input('donem', $varsayilan), $r->input('bas'), $r->input('bit')); }
    function _srIndir($sid, $rapor, array $dn, $format, $onizle = false)
    {
        $kat = _srKatalog();
        if ($rapor === 'ai') {
            $ai = cache()->get('sr_ai_son_' . $sid);
            if (!$ai) return response('Önce Raporlar & Analizler > AI Analiz bölümünden "Özet Hazırla" deyin.', 404);
            $sayfalar = _srAiSayfalar($sid, $dn, $ai); $baslik = 'AI Yönetici Özeti'; $tema = '9333EA'; $ikon = 'auto_awesome'; $acik = 'Yapay zekânın seçili dönem için yönetici özeti, riskleri ve önerileri + dayandığı rakamlar.';
        } else {
            if (!isset($kat[$rapor])) $rapor = 'aylik';
            $sayfalar = _srRapor($rapor, $sid, $dn); [$_k, $baslik, $acik, $ikon, $renk] = $kat[$rapor]; $tema = ltrim($renk, '#'); $ai = null;
        }
        $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
        $alt = $subeAd . ' · ' . $dn['ad'] . ' (' . $dn['etiket'] . ') · Oluşturma: ' . now()->format('d.m.Y H:i');
        $dosya = \Illuminate\Support\Str::slug($baslik) . '-' . $dn['bas'] . ($dn['bit'] !== $dn['bas'] ? '_' . $dn['bit'] : '');
        if ($format === 'pdf' || $format === 'html') {
            return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => [], 'ai' => $ai ? null : null, 'rapor' => $rapor === 'ai' ? 'ai' : $rapor, 'dosya' => $dosya,
                'tema' => $tema, 'ikon' => $ikon, 'aciklama' => $acik, 'marka' => 'Satın Alma Raporları', 'onizle' => $onizle || $format === 'html', 'aiEtiket' => $rapor === 'ai']);
        }
        $x = _fr2Xlsx($sayfalar, $baslik, $alt, $tema);
        if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
        return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
    }
}

// Eski tek sayfa adresi → ilk kategori (sorgu korunur)
Route::get('/satinalma-raporlar', function (Request $r) {
    $qs = $r->getQueryString();
    return redirect('/satinalma-raporlar/donemsel' . ($qs ? '?' . $qs : ''));
});
Route::get('/satinalma-raporlar/{kat}', function (Request $r, $kat) {
    if (!_srErisim()) return redirect('/pos-giris');
    $sid = _webSubeId(); $dn = _srIstekDonem($r);
    $d = []; try { $d = _srVeriDurumu($sid); } catch (\Throwable $e) {}
    return view('satinalma_raporlar', ['kat' => $kat, 'dn' => $dn, 'durum' => $d, 'katalog' => _srKatalog(), 'kategoriler' => _srKategoriler(), 'radar' => array_diff_key(_fr2Raporlar(), ['ai' => 1]),
        'ai' => cache()->get('sr_ai_son_' . $sid)]);
})->where('kat', 'donemsel|harcama|maliyet|tedarikci|surec|stok|finans|sube|radar|ai');
Route::get('/satinalma-raporlar/indir', function (Request $r) {
    if (!_srErisim()) return redirect('/pos-giris');
    $kat = _srKatalog(); $rapor = (string) $r->query('rapor', 'aylik');
    $dn = _srIstekDonem($r, $kat[$rapor][6] ?? 'bu_ay');
    return _srIndir(_webSubeId(), $rapor, $dn, (string) $r->query('format', 'xlsx'), $r->boolean('onizle'));
});
Route::post('/satinalma-raporlar/ai', function (Request $r) {
    if (!_srErisim()) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
    return response()->json(_srAiOzet(_webSubeId(), _srIstekDonem($r), (string) $r->input('soru', ''), $r->boolean('yenile')));
});

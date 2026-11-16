<?php
// ===== MUTFAK — RAPORLAR & ANALİZLER =====
// Satın Alma Raporlar ile aynı motor (_srDonem, _srSayfa/_srBlok/_srKpi, _fr2Xlsx, fiyat_radari_rapor görünümü) üstüne mutfak raporları.
// Kural: veri raporları YORUMSUZ sistem verisidir; boş kalan her bölüm nedenini yazar (ne eksik / neden gerekir / hangi ekrandan dolar).
// Kaynaklar: KDS zamanları (adisyon_kalemleri), reçete maliyeti, stok hareketleri (tüketim/fire/sayım/üretim), Kontrol Defteri,
// kalite kayıtları, iptal/ikram logları, mesai, mutfak talepleri. routes/web.php sonunda require edilir (satinalma_raporlar.php'den sonra).

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// ---------- Faz 2 veri eklemeleri: aşçı + istasyon damgası, tükendi geçmişi, seçmeli fire sebebi ----------
if (!function_exists('_mrEnsure')) {
    function _mrEnsure()
    {
        if (cache()->get('mr_ensure_v1')) return;
        try {
            if (Schema::hasTable('adisyon_kalemleri')) {
                if (!Schema::hasColumn('adisyon_kalemleri', 'hazirlayan_id')) Schema::table('adisyon_kalemleri', function ($t) { $t->unsignedBigInteger('hazirlayan_id')->nullable()->index(); });
                if (!Schema::hasColumn('adisyon_kalemleri', 'istasyon')) Schema::table('adisyon_kalemleri', function ($t) { $t->string('istasyon', 20)->nullable(); });
            }
            if (Schema::hasTable('stok_hareketleri') && !Schema::hasColumn('stok_hareketleri', 'fire_sebep')) Schema::table('stok_hareketleri', function ($t) { $t->string('fire_sebep', 40)->nullable(); });
            if (!Schema::hasTable('urun_tukendi_log')) Schema::create('urun_tukendi_log', function ($t) {
                $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('urun_id')->index(); $t->string('urun_ad', 150)->nullable();
                $t->boolean('tukendi'); $t->unsignedBigInteger('personel_id')->nullable(); $t->timestamp('created_at')->useCurrent(); $t->index(['sube_id', 'created_at']);
            });
            cache()->put('mr_ensure_v1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
    // KDS "Başla / Hazır" anında: ilk basan personel (hazırlayan) + ürünün o anki istasyonu kaleme yazılır (sonradan ürün değişse de rapor bozulmaz)
    function _mrKdsDamga(array $upd, $p)
    {
        _mrEnsure();
        try {
            $pid = (int) ($p->id ?? 0);
            if ($pid && _srKolon('adisyon_kalemleri', 'hazirlayan_id')) $upd['hazirlayan_id'] = DB::raw('COALESCE(hazirlayan_id, ' . $pid . ')');
            if (_srKolon('adisyon_kalemleri', 'istasyon') && _srKolon('urunler', 'istasyon')) $upd['istasyon'] = DB::raw('COALESCE(istasyon, (SELECT u.istasyon FROM urunler u WHERE u.id = adisyon_kalemleri.urun_id))');
        } catch (\Throwable $e) {}
        return $upd;
    }
    // Ürün tükendi (86) / tekrar satışta değişimi geçmişe yazılır → ne zaman, kaç saat tükendi
    function _mrTukendiLog($urun, $yeni, $p = null)
    {
        _mrEnsure();
        try {
            if (!$urun || !Schema::hasTable('urun_tukendi_log')) return;
            DB::table('urun_tukendi_log')->insert(['sube_id' => (int) $urun->sube_id, 'urun_id' => (int) $urun->id, 'urun_ad' => mb_substr((string) $urun->ad, 0, 150),
                'tukendi' => $yeni ? 1 : 0, 'personel_id' => $p->id ?? null, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }
    function _mrFireSebepleri()
    {
        return ['Bozulma / SKT geçti', 'Pişirme / hazırlama hatası', 'Düşme / döküm', 'Hazırlık artığı (ayıklama)', 'Fazla hazırlık (satılmadı)', 'Müşteri iadesi', 'Diğer'];
    }
}

// ---------- veri katmanı ----------
if (!function_exists('_mrKalemler')) {
    function _mrDepKodlari() { return ['mutfak', 'soguk_mutfak', 'pastane', 'kasap', 'bulasik']; }
    function _mrIstAd($kod) { $e = function_exists('_mutfakIstasyonlar') ? _mutfakIstasyonlar() : []; return $e[$kod] ?? ($kod ?: 'Sıcak'); }
    function _mrDk($a, $b) { if (!$a || !$b) return null; $d = (strtotime((string) $b) - strtotime((string) $a)) / 60; return $d < 0 ? null : round($d, 1); }
    function _mrOrt(array $l) { $l = array_values(array_filter($l, fn ($x) => $x !== null)); return $l ? round(array_sum($l) / count($l), 1) : null; }
    function _mrYuzdelik(array $l, $p)
    {
        $l = array_values(array_filter($l, fn ($x) => $x !== null)); if (!$l) return null; sort($l);
        $i = ($p / 100) * (count($l) - 1); $a = (int) floor($i); $b = (int) ceil($i);
        return round($l[$a] + ($l[$b] - $l[$a]) * ($i - $a), 1);
    }
    // Dönemde mutfağa giden / satılan kalemler (KDS zamanları + istasyon + hedef süre). İstek başına önbellekli.
    function _mrKalemler($sid, $bas, $bit)
    {
        static $c = [];
        $key = $sid . '|' . $bas . '|' . $bit; if (isset($c[$key])) return $c[$key];
        if (!_srTablo('adisyon_kalemleri') || !_srTablo('adisyonlar')) return $c[$key] = [];
        $zKol = _srKolon('adisyon_kalemleri', 'gonderim_zamani') ? 'COALESCE(ak.gonderim_zamani, ak.created_at)' : 'ak.created_at';
        $sel = ['ak.id', 'ak.urun_id', 'ak.urun_adi', 'ak.adet', 'ak.tutar', 'ak.birim_fiyat', 'ak.durum', 'ak.personel_id', 'ak.created_at', 'a.id as adisyon_id', 'a.kanal', 'a.misafir_sayisi', 'a.durum as adurum', DB::raw($zKol . ' as zaman')];
        foreach (['gonderim_zamani', 'basla_zamani', 'hazir_zamani', 'servis_zamani', 'servis_personel_id', 'ikram', 'hazirlayan_id', 'istasyon'] as $k) $sel[] = _srKolon('adisyon_kalemleri', $k) ? 'ak.' . $k : DB::raw('NULL as ' . $k);
        $sel[] = _srKolon('urunler', 'istasyon') ? 'u.istasyon as u_ist' : DB::raw('NULL as u_ist');
        $sel[] = _srKolon('urunler', 'hazirlik_dk') ? 'u.hazirlik_dk' : DB::raw('0 as hazirlik_dk');
        $sel[] = 'u.kategori_id';
        try {
            $rows = DB::table('adisyon_kalemleri as ak')->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')->leftJoin('urunler as u', 'ak.urun_id', '=', 'u.id')
                ->where('a.sube_id', $sid)->whereRaw($zKol . ' >= ?', [$bas . ' 00:00:00'])->whereRaw($zKol . ' <= ?', [$bit . ' 23:59:59'])->get($sel);
        } catch (\Throwable $e) { return $c[$key] = []; }
        $o = [];
        foreach ($rows as $r) {
            $ist = $r->istasyon ?: ($r->u_ist ?: 'mutfak'); $hedef = (int) $r->hazirlik_dk > 0 ? (int) $r->hazirlik_dk : 15;
            $top = _mrDk($r->gonderim_zamani, $r->hazir_zamani); if ($top !== null && $top > 180) $top = null; // ekranda unutulmuş: ölçüme katma
            $bek = _mrDk($r->gonderim_zamani, $r->basla_zamani); $haz = _mrDk($r->basla_zamani, $r->hazir_zamani);
            if ($top === null) { $bek = null; $haz = null; }
            $pass = _mrDk($r->hazir_zamani, $r->servis_zamani); if ($pass !== null && $pass > 120) $pass = null;
            $o[] = ['id' => (int) $r->id, 'urun_id' => (int) $r->urun_id, 'ad' => (string) $r->urun_adi, 'adet' => (float) $r->adet, 'tutar' => (float) $r->tutar, 'durum' => (string) $r->durum,
                'iptal' => $r->durum === 'iptal' || $r->adurum === 'iptal', 'ikram' => !empty($r->ikram), 'zaman' => (string) $r->zaman, 'tarih' => substr((string) $r->zaman, 0, 10),
                'saat' => (int) date('G', strtotime((string) $r->zaman)), 'gun' => (int) date('N', strtotime((string) $r->zaman)), 'ist' => $ist, 'bar' => $ist === 'bar', 'hedef' => $hedef,
                'bek' => $bek, 'haz' => $haz, 'top' => $top, 'pass' => $pass, 'gec' => $top !== null ? max(0, round($top - $hedef, 1)) : null, 'zamaninda' => $top !== null ? $top <= $hedef : null,
                'hazirlayan' => $r->hazirlayan_id ? (int) $r->hazirlayan_id : null, 'adisyon_id' => (int) $r->adisyon_id, 'kanal' => (string) $r->kanal, 'misafir' => (int) $r->misafir_sayisi,
                'kategori_id' => $r->kategori_id, 'gonderildi' => !empty($r->gonderim_zamani), 'basladi' => !empty($r->basla_zamani)];
        }
        return $c[$key] = $o;
    }
    function _mrSatis(array $k) { return array_values(array_filter($k, fn ($x) => !$x['iptal'])); }
    function _mrOlcum(array $k) { return array_values(array_filter($k, fn ($x) => !$x['iptal'] && !$x['bar'] && $x['top'] !== null)); }
    function _mrHiz(array $k)
    {
        $o = _mrOlcum($k); $n = count($o);
        $z = count(array_filter($o, fn ($x) => $x['zamaninda']));
        return ['n' => $n, 'bek' => _mrOrt(array_column($o, 'bek')), 'haz' => _mrOrt(array_column($o, 'haz')), 'top' => _mrOrt(array_column($o, 'top')),
            'medyan' => _mrYuzdelik(array_column($o, 'top'), 50), 'p90' => _mrYuzdelik(array_column($o, 'top'), 90), 'uyum' => $n ? round($z / $n * 100, 1) : null,
            'geciken' => $n - $z, 'gec_ort' => _mrOrt(array_map(fn ($x) => $x['gec'] > 0 ? $x['gec'] : null, $o))];
    }
    function _mrPorsiyon(array $k) { return round(array_sum(array_column(_mrSatis($k), 'adet')), 1); }
    function _mrKuver($sid, $bas, $bit)
    {
        if (!_srTablo('adisyonlar')) return 0;
        try { return (int) DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', '!=', 'iptal')->whereDate('created_at', '>=', $bas)->whereDate('created_at', '<=', $bit)->sum('misafir_sayisi'); } catch (\Throwable $e) { return 0; }
    }
    // Stok hareketleri (tip filtreli) dönem içi
    function _mrHareket($sid, $bas, $bit, array $tip, $kaynak = null)
    {
        if (!_srTablo('stok_hareketleri')) return collect();
        $q = DB::table('stok_hareketleri')->where('sube_id', $sid)->whereIn('tip', $tip)->whereDate('created_at', '>=', $bas)->whereDate('created_at', '<=', $bit);
        if ($kaynak) $q->whereIn('kaynak_tip', (array) $kaynak);
        try { return $q->orderBy('created_at')->get(); } catch (\Throwable $e) { return collect(); }
    }
    function _mrBirim($mid) { $m = _srMeta(); return $m['birim'][$m['malz'][$mid]->temel_birim_id ?? 0] ?? ''; }
    function _mrFireler($sid, $bas, $bit)
    {
        $fs = _srKolon('stok_hareketleri', 'fire_sebep');
        return _mrHareket($sid, $bas, $bit, ['fire'])->map(fn ($h) => ['tarih' => substr((string) $h->created_at, 0, 10), 'saat' => substr((string) $h->created_at, 11, 5), 'malzeme' => _srMalz($h->malzeme_id), 'mid' => (int) $h->malzeme_id,
            'kategori' => _srKat($h->malzeme_id), 'miktar' => abs((float) $h->miktar), 'birim' => _mrBirim($h->malzeme_id), 'maliyet' => round(abs((float) $h->miktar) * (float) $h->birim_maliyet, 2),
            'kaynak' => $h->kaynak_tip === 'void' ? 'Pişmiş ürün iptali' : 'Elle girilen fire', 'sebep' => trim((string) (($fs ? $h->fire_sebep : null) ?: '')) ?: (trim((string) $h->aciklama) ?: 'Belirtilmemiş'),
            'personel' => $h->personel_id ? _mrPersonelAd($h->personel_id) : '—'])->all();
    }
    function _mrPersonelAd($id) { static $m = null; if ($m === null) { try { $m = DB::table('personeller')->pluck('ad', 'id'); } catch (\Throwable $e) { $m = collect(); } } return $id ? ($m[$id] ?? ('#' . $id)) : '—'; }
    function _mrMutfakDepIds($sid)
    {
        if (!_srTablo('departmanlar')) return [];
        try { return DB::table('departmanlar')->where('sube_id', $sid)->whereIn('kod', _mrDepKodlari())->pluck('kod', 'id')->all(); } catch (\Throwable $e) { return []; }
    }
    function _mrEkip($sid)
    {
        $dep = _mrMutfakDepIds($sid);
        try {
            return DB::table('personeller')->where('sube_id', $sid)->where(function ($q) use ($dep) { $q->where('rol', 'mutfak'); if ($dep && _srKolon('personeller', 'departman_id')) $q->orWhereIn('departman_id', array_keys($dep)); })
                ->get()->keyBy('id');
        } catch (\Throwable $e) { return collect(); }
    }
    function _mrDefter($sid, $bas, $bit, $tip = null)
    {
        if (!_srTablo('mutfak_kayitlari')) return collect();
        $q = DB::table('mutfak_kayitlari')->where('sube_id', $sid)->whereRaw('COALESCE(tarih, DATE(created_at)) >= ?', [$bas])->whereRaw('COALESCE(tarih, DATE(created_at)) <= ?', [$bit]);
        if ($tip) $q->whereIn('tip', (array) $tip);
        try { return $q->orderBy('created_at')->get(); } catch (\Throwable $e) { return collect(); }
    }
    function _mrMaliyetMap() { static $m = null; if ($m === null) $m = function_exists('_restoUrunMaliyetMap') ? _restoUrunMaliyetMap() : ['id' => [], 'ad' => []]; return $m; }
    function _mrUrunMaliyet($uid, $ad) { $m = _mrMaliyetMap(); return $m['id'][$uid] ?? ($m['ad'][$ad] ?? null); }
}

if (!function_exists('_mrVeriDurumu')) {
    function _mrVeriDurumu($sid)
    {
        $s = today()->subDays(365)->toDateString(); $d = [];
        $say = function ($t, $tarihKol, $ek = null, $subeli = true) use ($sid, $s) {
            if (!_srTablo($t)) return 0;
            try { $q = DB::table($t); if ($subeli) $q->where('sube_id', $sid); if ($tarihKol && _srKolon($t, $tarihKol)) $q->whereDate($tarihKol, '>=', $s); if ($ek) $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $kalem = function ($ek) use ($sid, $s) {
            if (!_srTablo('adisyon_kalemleri')) return 0;
            try { $q = DB::table('adisyon_kalemleri as ak')->join('adisyonlar as a', 'ak.adisyon_id', '=', 'a.id')->where('a.sube_id', $sid)->whereDate('ak.created_at', '>=', $s); $ek($q); return (int) $q->count(); } catch (\Throwable $e) { return 0; }
        };
        $d['satis'] = $kalem(fn ($q) => $q->where('ak.durum', '!=', 'iptal'));
        $d['kds'] = _srKolon('adisyon_kalemleri', 'hazir_zamani') ? $kalem(fn ($q) => $q->whereNotNull('ak.hazir_zamani')) : 0;
        $d['basla'] = _srKolon('adisyon_kalemleri', 'basla_zamani') ? $kalem(fn ($q) => $q->whereNotNull('ak.basla_zamani')) : 0;
        $d['servis'] = _srKolon('adisyon_kalemleri', 'servis_zamani') ? $kalem(fn ($q) => $q->whereNotNull('ak.servis_zamani')) : 0;
        $d['hazirlayan'] = _srKolon('adisyon_kalemleri', 'hazirlayan_id') ? $kalem(fn ($q) => $q->whereNotNull('ak.hazirlayan_id')) : 0;
        $d['recete'] = _srTablo('receteler') ? (int) DB::table('receteler')->whereNotNull('urun_id')->count() : 0;
        $d['tuketim'] = $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('tip', 'tuketim'));
        $d['fire'] = $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('tip', 'fire'));
        $d['sayim'] = $say('sayimlar', 'tarih');
        $d['parti'] = $say('stok_partileri', null, fn ($q) => $q->whereNotNull('skt'));
        $d['uretim'] = $say('stok_hareketleri', 'created_at', fn ($q) => $q->where('kaynak_tip', 'uretim'));
        $d['defter'] = $say('mutfak_kayitlari', 'created_at');
        $d['sicaklik'] = $say('mutfak_kayitlari', 'created_at', fn ($q) => $q->where('tip', 'sicaklik'));
        $d['kalite'] = $say('kalite_kayitlari', null);
        $d['iptal'] = $say('iptal_indirim_loglari', 'created_at', fn ($q) => $q->whereIn('tip', ['void', 'ikram']));
        $d['mesai'] = $say('personel_mesai', 'giris_at');
        $d['talep'] = $say('satinalma_talepleri', 'created_at');
        $d['tukendi'] = $say('urun_tukendi_log', 'created_at');
        $d['fatura'] = $say('alis_faturalari', 'tarih');
        return $d;
    }
    function _mrBos($neden, array $dn)
    {
        $donem = $dn['etiket'];
        $m = [
            'satis' => 'Seçili dönemde (' . $donem . ') satış / mutfağa giden sipariş kalemi yok. Daha uzun bir dönem seçin.',
            'kds_yok' => 'Mutfak ekranı (KDS) kullanılmıyor: son 1 yılda "Hazır" işaretlenen sipariş kalemi yok. Süre raporları, sipariş mutfağa gittiği an ile KDS\'de "Hazır"a basıldığı an arasından hesaplanır. Dolması için mutfak siparişleri Mutfak / BOH > Mutfak / KDS Ekranı\'ndan (ya da tabletteki mutfak ekranından) "Başla" ve "Hazır" ile ilerletilmeli.',
            'kds' => 'Seçili dönemde (' . $donem . ') KDS\'de "Hazır" işaretlenmiş (ölçülebilen) mutfak kalemi yok. 3 saati aşan ölçümler (ekranda unutulmuş sipariş) hesaba katılmaz.',
            'servis_yok' => 'Hazır → servis süresi ölçülemiyor: son 1 yılda servis zamanı yazılmış kalem yok. Servis zamanı, hazır olan tabak mutfak/expo ekranında ya da garson ekranında "Servis edildi" işaretlenince yazılır.',
            'hazirlayan_yok' => 'Aşçı bilgisi henüz yok. Bu özellik yeni eklendi: bundan sonra KDS\'de "Başla" ya da "Hazır"a basan personel kaleme yazılıyor. Rapor, KDS\'ye herkes kendi PIN\'i ile girdiğinde anlamlı olur; ortak tek hesapla girilirse tek kişi görünür.',
            'tukendi_yok' => 'Tükendi (86) geçmişi henüz yok. Bu özellik yeni eklendi: bundan sonra ürün KDS\'den, menüden ya da tabletten "tükendi" / "tekrar satışta" yapıldığında geçmişe yazılıyor.',
            'recete_yok' => 'Ürün reçetesi tanımlı değil. Food cost ve menü mühendisliği, reçetedeki malzeme miktarları × malzeme maliyetinden hesaplanır. Dolması için Mutfak / BOH > Reçeteler / Yarı Mamül\'den ürünlere reçete girin.',
            'tuketim_yok' => 'Reçeteli stok tüketimi yok: satışlar reçete üzerinden stoktan düşmüyor. Teorik tüketim, satış kapandığında reçeteye göre stoktan düşülen miktardır. Dolması için ürünlere reçete tanımlayın; adisyon kapanınca malzeme otomatik düşer.',
            'tuketim' => 'Seçili dönemde (' . $donem . ') reçeteli tüketim kaydı yok.',
            'fire_yok' => 'Fire kaydı yok: son 1 yılda stoktan fire düşülmemiş. Fire raporu, Stok ekranında "Fire" ile düşülen kayıtlardan ve pişmeye başlamış ürün iptallerinin otomatik fire kaydından hesaplanır. Dolması için fireleri Stok > malzeme > Fire ile sebebini seçerek girin.',
            'fire' => 'Seçili dönemde (' . $donem . ') fire kaydı yok.',
            'sayim_yok' => 'Sayım yapılmamış: son 1 yılda envanter sayımı yok. Sayım farkı, sistemdeki teorik stok ile sayılan miktarın farkıdır. Dolması için Mutfak / BOH > Sayım (Envanter)\'den sayım yapın.',
            'sayim' => 'Seçili dönemde (' . $donem . ') sayım farkı yok.',
            'parti_yok' => 'Lot / son kullanma tarihi (SKT) takibi yapılmıyor: stokta SKT\'li parti yok. Dolması için mal kabulde SKT girin, yarı mamül üretiminde SKT verin.',
            'uretim_yok' => 'Yarı mamül üretimi kaydı yok. Bu rapor, Reçeteler / Yarı Mamül ekranındaki "Üret" kayıtlarından hesaplanır (üretilen miktar, harcanan hammadde, lot ve SKT). Dolması için yarı mamülleri (sos, hamur, marine et vb.) üretim olarak girin.',
            'uretim' => 'Seçili dönemde (' . $donem . ') yarı mamül üretimi yok.',
            'defter_yok' => 'Kontrol Defteri kullanılmıyor: son 1 yılda sıcaklık, açılış/kapanış kontrolü ya da prep kaydı yok. Dolması için Mutfak / BOH > Kontrol Defteri\'nden günlük kayıt girin (dolap/derin dondurucu sıcaklıkları, açılış-kapanış listesi).',
            'sicaklik' => 'Seçili dönemde (' . $donem . ') sıcaklık kaydı yok.',
            'kontrol' => 'Seçili dönemde (' . $donem . ') açılış/kapanış kontrolü ya da prep kaydı yok.',
            'kalite_yok' => 'Kalite kaydı yok (numune, ilaçlama, kalibrasyon, tedarikçi belgesi, denetim). Dolması için Kalite / HACCP ekranından kayıt ve geçerlilik tarihi girin.',
            'iptal' => 'Seçili dönemde (' . $donem . ') iptal ya da ikram kaydı yok.',
            'mesai_yok' => 'Mesai (giriş-çıkış) kaydı yok. Ekip raporu, personelin QR ile mesai başlatıp bitirmesinden hesaplanır. Dolması için mutfak ekibi mesaiyi QR ile başlatmalı.',
            'mesai' => 'Seçili dönemde (' . $donem . ') mutfak ekibine ait mesai kaydı yok.',
            'ekip_yok' => 'Mutfak ekibi tanımlı değil: rolü "Mutfak" olan ya da mutfak departmanlarına (Mutfak, Soğuk Mutfak, Pastane, Kasap, Bulaşıkhane) bağlı personel yok. Departman Yönetimi\'nden personeli mutfak departmanına bağlayın.',
            'talep' => 'Seçili dönemde (' . $donem . ') mutfak departmanlarından açılan satın alma talebi yok.',
            'kritik' => 'Kritik stok altında malzeme yok (ya da malzemelere kritik stok seviyesi tanımlanmamış: Stok ekranında malzeme kartına kritik stok girin).',
            'skt' => 'SKT\'si geçmiş ya da 7 gün içinde dolacak, stokta kalan parti yok.',
        ];
        return $m[$neden] ?? 'Seçili dönem için kayıt yok.';
    }
}

// =====================================================================
// RAPOR KATALOĞU — [kategori, başlık, açıklama, ikon, renk, gerekli modüller, varsayılan dönem (dönemsel raporlarda)]
// =====================================================================
if (!function_exists('_mrKatalog')) {
    function _mrKatalog()
    {
        return [
            'gunluk' => ['donemsel', 'Günlük Mutfak Bülteni', 'Dünün mutfağı: satılan porsiyon, mutfak süresi ve hedefe uyum, geciken siparişler, fire, mutfak kaynaklı iptaller, sıcaklık uygunsuzlukları, kritik stok ve SKT yaklaşanlar.', 'today', '#EA580C', ['satis', 'kds'], 'dun'],
            'haftalik' => ['donemsel', 'Haftalık Mutfak Raporu', 'Günlere göre porsiyon ve süre, istasyon performansı, en yavaş ürünler, fire sebepleri, en çok satanlar ve önceki haftayla kıyas.', 'date_range', '#D97706', ['satis', 'kds'], 'gecen_hafta'],
            'aylik' => ['donemsel', 'Aylık Mutfak Yönetim Raporu', 'Food cost, teorik ve gerçek tüketim farkı, fire, menü mühendisliği özeti, hız, hijyen ve ekip — şef ve patron için tek dosyada ay özeti.', 'calendar_month', '#C2410C', ['satis', 'recete'], 'gecen_ay'],
            'hiz' => ['hiz', 'Mutfak Hız Analizi', 'Bekleme (sipariş → başla), hazırlık (başla → hazır) ve toplam süre; medyan, %90, hedefe uyum oranı; saat ve gün kırılımı.', 'timer', '#DC2626', ['kds'], null],
            'urun_sure' => ['hiz', 'Ürün Bazında Süreler', 'Her ürünün ortalama hazırlanma süresi, hedef süresi ve hedefe uyum oranı; en çok geciken ürünler.', 'fastfood', '#E11D48', ['kds'], null],
            'istasyon' => ['hiz', 'İstasyon Performansı', 'Izgara, fırın, sıcak, soğuk, tatlı istasyonlarının yükü, ortalama süresi, hedefe uyumu ve en yoğun saati.', 'countertops', '#F97316', ['kds'], null],
            'pass' => ['hiz', 'Hazır → Servis (Pass) Süresi', 'Tabak hazır olduktan sonra garson alana kadar geçen süre; tezgâhta bekleyen tabaklar.', 'room_service', '#B45309', ['servis'], null],
            'tukendi' => ['hiz', 'Tükendi (86) Geçmişi', 'Hangi ürün ne zaman, kaç kez ve toplam kaç saat tükendi; şu an tükenmiş ürünler.', 'remove_shopping_cart', '#9F1239', ['tukendi'], null],
            'foodcost' => ['maliyet', 'Ürün Food Cost', 'Satılan her ürünün reçete maliyeti, satış tutarı, food cost oranı ve katkı payı; reçetesiz satılan ürünler.', 'percent', '#059669', ['satis', 'recete'], null],
            'avt' => ['maliyet', 'Teorik ve Gerçek Tüketim', 'Reçeteye göre harcanması gereken (teorik) ile fire ve sayım farkı eklenmiş gerçek tüketim; malzeme bazında fark.', 'compare_arrows', '#047857', ['tuketim'], null],
            'zam' => ['maliyet', 'Menüye Zam Etkisi', 'Malzeme alış fiyatı değişimlerinin reçete üzerinden ürün maliyetine ve food cost oranına etkisi.', 'trending_up', '#0F766E', ['fatura', 'recete'], null],
            'menu_muh' => ['menu', 'Menü Mühendisliği', '⭐ En İyi Ürünler: çok satıyor ve çok kazandırıyor. 💰 Zam Adayları: çok satıyor ama az kazandırıyor. 📣 Öne Çıkarılacaklar: az satıyor ama çok kazandırıyor. ❌ Çıkarma Adayları: hem az satıyor hem az kazandırıyor.', 'auto_graph', '#7C3AED', ['satis', 'recete'], null],
            'satis' => ['menu', 'Ürün Satışları', 'Ürün başına satılan porsiyon ve tutar, önceki döneme göre değişim, saat dağılımı.', 'restaurant_menu', '#6D28D9', ['satis'], null],
            'fire' => ['fire', 'Fire & İsraf Analizi', 'Fire maliyeti; sebebe, malzemeye, kaynağa ve güne göre; alışa ve kuvere oranı.', 'delete_sweep', '#C2410C', ['fire'], null],
            'skt_kayip' => ['fire', 'SKT Kaybı & Riski', 'Son kullanma tarihi geçmiş ya da 7 gün içinde dolacak stoktaki partiler ve değeri; SKT nedeniyle atılan fire.', 'event_busy', '#B91C1C', ['parti'], null],
            'uretim' => ['uretim', 'Yarı Mamül Üretimleri', 'Dönemde üretilen yarı mamüller: miktar, maliyet, lot ve SKT, üreten personel.', 'blender', '#0891B2', ['uretim'], null],
            'uretim_kullanim' => ['uretim', 'Üretilen ve Kullanılan', 'Her yarı mamülde üretilen miktar ile satışta kullanılan miktar; fazla hazırlık (over-prep) göstergesi.', 'balance', '#0E7490', ['uretim'], null],
            'tuketim' => ['stok', 'En Çok Tüketilen Malzemeler', 'Reçeteli satışla tüketilen malzemeler: miktar ve maliyet, önceki döneme göre değişim.', 'kitchen', '#2563EB', ['tuketim'], null],
            'sayim' => ['stok', 'Sayım Farkları', 'Sayımda sistem stoğu ile sayılan arasındaki fark (kayıp / fazla) ve maliyeti.', 'fact_check', '#1D4ED8', ['sayim'], null],
            'kritik' => ['stok', 'Kritik Stok & SKT', 'Kritik seviyenin altına düşen malzemeler ve SKT\'si yaklaşan partiler.', 'warning_amber', '#D97706', ['parti'], null],
            'sicaklik' => ['hijyen', 'Sıcaklık Kayıtları (HACCP)', 'Dolap, derin dondurucu ve sıcak tutma ölçümleri: uygunsuz ölçümler, ekipman bazında uygunsuzluk oranı, kayıt girilmeyen günler.', 'thermostat', '#0284C7', ['sicaklik'], null],
            'kontrol' => ['hijyen', 'Kontrol Listeleri & Görevler', 'Açılış / kapanış kontrolleri ve prep kayıtlarının günlere göre tamamlanması; açık ve geciken mutfak görevleri, arızalar.', 'checklist', '#16A34A', ['defter'], null],
            'kalite' => ['hijyen', 'Kalite & Belge Takibi', 'Numune, ilaçlama, kalibrasyon, tedarikçi belgesi ve denetim kayıtları; süresi geçen ve yaklaşanlar.', 'verified', '#0D9488', ['kalite'], null],
            'iptal' => ['iptal', 'İptal & İkram Analizi', 'İptal ve ikramlar: sebep, ürün ve personel bazında; mutfağa gittikten / pişmeye başladıktan sonra iptal edilenler.', 'cancel', '#BE123C', ['iptal'], null],
            'ekip' => ['ekip', 'Mutfak Ekibi Mesai', 'Mutfak personelinin çalışılan gün ve saatleri, planlanan vardiya, saat başına porsiyon, personel yemeği.', 'groups', '#4F46E5', ['mesai'], null],
            'asci' => ['ekip', 'Aşçı Performansı', 'KDS\'de "Başla / Hazır"a basan personele göre hazırlanan kalem, ortalama süre ve hedefe uyum.', 'person_pin', '#4338CA', ['hazirlayan'], null],
            'talep' => ['ekip', 'Mutfak Satın Alma Talepleri', 'Mutfak departmanlarının açtığı talepler: sayı, acil oranı, onay / red, karar süresi ve en çok istenen malzemeler.', 'assignment', '#7C3AED', ['talep'], null],
        ];
    }
    function _mrKategoriler()
    {
        return ['donemsel' => ['Dönemsel Raporlar', 'event_note', 'Şef ve yönetime sunulan hazır raporlar: günlük bülten, haftalık rapor, aylık yönetim raporu.', '#EA580C'],
            'hiz' => ['Hız & Servis', 'timer', 'Mutfak süreleri, ürün ve istasyon performansı, pass süresi ve tükendi geçmişi.', '#DC2626'],
            'maliyet' => ['Food Cost & Tüketim', 'percent', 'Ürün food cost oranları, teorik ve gerçek tüketim farkı, zamların menüye etkisi.', '#059669'],
            'menu' => ['Menü Mühendisliği', 'auto_graph', 'Ürünlerin satış ve kârlılığa göre sınıflandırılması ve ürün satışları.', '#7C3AED'],
            'fire' => ['Fire & İsraf', 'delete_sweep', 'Fire maliyeti, sebepleri ve SKT kaybı.', '#C2410C'],
            'uretim' => ['Üretim & Hazırlık', 'blender', 'Yarı mamül üretimleri ve üretilen–kullanılan karşılaştırması.', '#0891B2'],
            'stok' => ['Stok & Sayım', 'kitchen', 'Tüketilen malzemeler, sayım farkları, kritik stok ve SKT.', '#2563EB'],
            'hijyen' => ['Hijyen & Kontrol', 'health_and_safety', 'Sıcaklık (HACCP), açılış/kapanış kontrolleri, görevler ve kalite belgeleri.', '#16A34A'],
            'iptal' => ['İptal & İkram', 'cancel', 'Mutfak kaynaklı iptaller ve ikramlar.', '#BE123C'],
            'ekip' => ['Ekip & Talepler', 'groups', 'Mutfak ekibinin mesaisi, aşçı performansı ve mutfaktan açılan talepler.', '#4F46E5'],
            'ai' => ['AI Analiz', 'auto_awesome', 'Seçili dönem için yapay zekânın hazırladığı mutfak özeti; şefe soru da sorabilirsiniz.', '#9333EA']];
    }
}

// =====================================================================
// ORTAK HESAPLAR (raporlar bunları birleştirir)
// =====================================================================
if (!function_exists('_mrUrunSure')) {
    function _mrUrunSure(array $k)
    {
        $g = []; foreach (_mrOlcum($k) as $x) $g[$x['ad']][] = $x; $o = [];
        foreach ($g as $ad => $l) {
            $z = count(array_filter($l, fn ($x) => $x['zamaninda']));
            $o[] = [$ad, _mrIstAd($l[0]['ist']), count($l), $l[0]['hedef'], _mrOrt(array_column($l, 'bek')), _mrOrt(array_column($l, 'haz')), _mrOrt(array_column($l, 'top')), max(array_column($l, 'top')), round($z / count($l) * 100, 1), count($l) - $z];
        }
        usort($o, fn ($a, $b) => $a[8] <=> $b[8] ?: $b[6] <=> $a[6]);
        return $o;
    }
    function _mrIstasyonlar(array $k)
    {
        $g = []; foreach (_mrSatis($k) as $x) if (!$x['bar']) $g[$x['ist']][] = $x; $o = [];
        foreach ($g as $ist => $l) {
            $ol = array_values(array_filter($l, fn ($x) => $x['top'] !== null)); $z = count(array_filter($ol, fn ($x) => $x['zamaninda']));
            $saat = array_count_values(array_column($l, 'saat')); arsort($saat); $ys = key($saat);
            $u = []; foreach ($ol as $x) $u[$x['ad']][] = $x['top']; $yav = null; $mx = -1; foreach ($u as $a => $t) { $av = array_sum($t) / count($t); if (count($t) >= 2 && $av > $mx) { $mx = $av; $yav = $a; } }
            $o[] = [_mrIstAd($ist), round(array_sum(array_column($l, 'adet')), 1), count($ol), _mrOrt(array_column($ol, 'top')), $ol ? round($z / count($ol) * 100, 1) : null, count($ol) - $z, $ys !== null ? sprintf('%02d:00', $ys) : '—', $yav ?: '—'];
        }
        usort($o, fn ($a, $b) => $b[1] <=> $a[1]);
        return $o;
    }
    function _mrSaatlik(array $k)
    {
        $s = []; foreach (_mrSatis($k) as $x) if (!$x['bar']) { $h = $x['saat']; $s[$h]['adet'] = ($s[$h]['adet'] ?? 0) + $x['adet']; if ($x['top'] !== null) { $s[$h]['t'][] = $x['top']; $s[$h]['z'] = ($s[$h]['z'] ?? 0) + ($x['zamaninda'] ? 1 : 0); } }
        ksort($s); $o = [];
        foreach ($s as $h => $v) { $n = count($v['t'] ?? []); $o[] = [sprintf('%02d:00', $h), round($v['adet'], 1), $n, _mrOrt($v['t'] ?? []), $n ? round(($v['z'] ?? 0) / $n * 100, 1) : null]; }
        return $o;
    }
    function _mrGunluk(array $k, array $dn, $fire = [])
    {
        $gAd = ['', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz']; $g = [];
        for ($t = \Carbon\Carbon::parse($dn['bas']); $t->lte(\Carbon\Carbon::parse($dn['bit'])); $t->addDay()) $g[$t->toDateString()] = [];
        foreach (_mrSatis($k) as $x) if (isset($g[$x['tarih']])) $g[$x['tarih']][] = $x;
        $fg = []; foreach ($fire as $f) $fg[$f['tarih']] = ($fg[$f['tarih']] ?? 0) + $f['maliyet'];
        $ip = []; foreach ($k as $x) if ($x['iptal']) $ip[$x['tarih']] = ($ip[$x['tarih']] ?? 0) + 1;
        $o = [];
        foreach ($g as $t => $l) { $h = _mrHiz($l); $o[] = [$gAd[(int) date('N', strtotime($t))] . ' ' . date('d.m', strtotime($t)), round(array_sum(array_column($l, 'adet')), 1), round(array_sum(array_column($l, 'tutar')), 2), $h['n'], $h['top'], $h['uyum'], round($fg[$t] ?? 0, 2), $ip[$t] ?? 0]; }
        return $o;
    }
    function _mrGunlukSayfa(array $sat, $bos)
    {
        return _srSayfa('Günlere Göre', 'Porsiyon = iptal dışı satılan adet. Ölçülen = KDS\'de hazır işaretlenen (bar hariç) kalem. Uyum = hedef süre içinde hazır olanların oranı.',
            [['Gün', 'm', 12], ['Porsiyon', 's', 10, 'top bar'], ['Satış', 'tl', 13, 'top'], ['Ölçülen Kalem', 's', 11, 'top'], ['Ort. Süre (dk)', 's', 11], ['Hedefe Uyum %', 'y', 12], ['Fire', 'tl', 11, 'top'], ['İptal (kalem)', 's', 10, 'top']], $sat, $bos);
    }
    function _mrGecikenler(array $k, $limit = 300)
    {
        $l = array_values(array_filter(_mrOlcum($k), fn ($x) => !$x['zamaninda'])); usort($l, fn ($a, $b) => $b['gec'] <=> $a['gec']);
        return array_map(fn ($x) => [$x['tarih'], substr($x['zaman'], 11, 5), $x['ad'], _mrIstAd($x['ist']), $x['adet'], $x['hedef'], $x['bek'], $x['haz'], $x['top'], $x['gec'], $x['hazirlayan'] ? _mrPersonelAd($x['hazirlayan']) : '—'], array_slice($l, 0, $limit));
    }
    function _mrGecikenSayfa(array $sat, $bos)
    {
        return _srSayfa('Geciken Kalemler', 'Toplam süre (sipariş mutfağa gidiş → hazır) ürünün hedef süresini aşan kalemler. Hedef = ürün kartındaki hazırlık süresi (girilmemişse 15 dk). Gecikme = toplam − hedef.',
            [['Tarih', 't', 11], ['Saat', 'm', 7], ['Ürün', 'm', 24], ['İstasyon', 'm', 12], ['Adet', 's', 7], ['Hedef (dk)', 's', 9], ['Bekleme (dk)', 's', 10], ['Hazırlık (dk)', 's', 10], ['Toplam (dk)', 's', 10], ['Gecikme (dk)', 's', 10, 'bar'], ['Hazırlayan', 'm', 16]], $sat, $bos);
    }
    function _mrUrunSureSayfa(array $sat, $bos)
    {
        return _srSayfa('Ürün Süreleri', 'Bekleme = sipariş mutfağa gidiş → "Başla". Hazırlık = "Başla" → "Hazır". Toplam = sipariş → hazır. Uyum = hedef süre içinde hazır olanların oranı (düşükten yükseğe sıralı).',
            [['Ürün', 'm', 24], ['İstasyon', 'm', 12], ['Ölçülen', 's', 9, 'top'], ['Hedef (dk)', 's', 9], ['Ort. Bekleme', 's', 10], ['Ort. Hazırlık', 's', 10], ['Ort. Toplam', 's', 10, 'bar'], ['En Uzun', 's', 9], ['Hedefe Uyum %', 'y', 11], ['Geciken', 's', 9, 'top']], $sat, $bos);
    }
    function _mrIstasyonSayfa(array $sat, $bos)
    {
        return _srSayfa('İstasyonlar', 'İstasyon, kalem KDS\'de işlenirken ürünün istasyonundan alınır (bar hariç). Yük = iptal dışı porsiyon.',
            [['İstasyon', 'm', 16], ['Porsiyon', 's', 10, 'top bar'], ['Ölçülen', 's', 9, 'top'], ['Ort. Toplam (dk)', 's', 12], ['Hedefe Uyum %', 'y', 12], ['Geciken', 's', 9, 'top'], ['En Yoğun Saat', 'm', 11], ['En Yavaş Ürün', 'm', 22]], $sat, $bos);
    }
    function _mrSaatlikSayfa(array $sat, $bos)
    {
        return _srSayfa('Saatlere Göre', 'Siparişin mutfağa gittiği saat. Ortalama süre ve uyum yalnız ölçülen (hazır işaretlenen) kalemlerden.',
            [['Saat', 'm', 9], ['Porsiyon', 's', 10, 'top bar'], ['Ölçülen', 's', 9, 'top'], ['Ort. Toplam (dk)', 's', 12], ['Hedefe Uyum %', 'y', 12]], $sat, $bos);
    }
    // Food cost: ürün bazında (reçete maliyeti × adet) / satış
    function _mrFoodCost(array $k)
    {
        $g = []; foreach (_mrSatis($k) as $x) { if ($x['ikram']) continue; $a = $x['urun_id'] ?: $x['ad']; $g[$a]['ad'] = $x['ad']; $g[$a]['uid'] = $x['urun_id']; $g[$a]['adet'] = ($g[$a]['adet'] ?? 0) + $x['adet']; $g[$a]['tutar'] = ($g[$a]['tutar'] ?? 0) + $x['tutar']; }
        $var = []; $yok = [];
        foreach ($g as $v) {
            $bm = _mrUrunMaliyet($v['uid'], $v['ad']);
            if ($bm === null || $bm <= 0) { $yok[] = [$v['ad'], round($v['adet'], 1), round($v['tutar'], 2)]; continue; }
            $fiyat = $v['adet'] > 0 ? $v['tutar'] / $v['adet'] : 0; $tm = $bm * $v['adet'];
            $var[] = ['ad' => $v['ad'], 'adet' => round($v['adet'], 1), 'tutar' => round($v['tutar'], 2), 'fiyat' => round($fiyat, 2), 'bm' => round($bm, 2), 'tm' => round($tm, 2),
                'fc' => $v['tutar'] > 0 ? round($tm / $v['tutar'] * 100, 1) : null, 'cm' => round($fiyat - $bm, 2), 'tcm' => round($v['tutar'] - $tm, 2)];
        }
        usort($var, fn ($a, $b) => ($b['fc'] ?? 0) <=> ($a['fc'] ?? 0)); usort($yok, fn ($a, $b) => $b[2] <=> $a[2]);
        $ts = array_sum(array_column($var, 'tutar')); $tmm = array_sum(array_column($var, 'tm'));
        return ['var' => $var, 'yok' => $yok, 'fc' => $ts > 0 ? round($tmm / $ts * 100, 1) : null, 'satis' => $ts, 'maliyet' => $tmm, 'yok_tutar' => array_sum(array_column($yok, 2))];
    }
    // Menü mühendisliği (Kasavana-Smith): popüler = satış payı ≥ %70 × (1 / ürün sayısı); kârlı = katkı payı ≥ ağırlıklı ortalama katkı payı
    function _mrSinifAd() { return ['Yıldız' => '⭐ En İyi Ürünler', 'İş Atı' => '💰 Zam Adayları', 'Bilmece' => '📣 Öne Çıkarılacaklar', 'Zayıf' => '❌ Çıkarma Adayları']; }
    function _mrMenuMuh(array $k)
    {
        $fc = _mrFoodCost($k); $l = $fc['var']; $n = count($l); if (!$n) return ['sat' => [], 'esik_pop' => null, 'esik_cm' => null, 'sinif' => []];
        $topAdet = array_sum(array_column($l, 'adet')); $ortCm = $topAdet > 0 ? array_sum(array_column($l, 'tcm')) / $topAdet : 0; $esikPop = 70 / $n;
        $ne = ['Yıldız' => 'Koru: kaliteyi ve porsiyonu sabit tut, menüde en görünür yerde kalsın.', 'İş Atı' => 'Çok satıyor ama az kazandırıyor: küçük fiyat artışı ya da porsiyon/malzeme maliyetini düşür.',
            'Bilmece' => 'Kazandırıyor ama az satıyor: menüde öne çıkar, garsona önerdir, adını/sunumunu güçlendir.', 'Zayıf' => 'Az satıyor ve az kazandırıyor: menüden çıkarmayı ya da yeniden tasarlamayı düşün.'];
        $sat = []; $sinif = ['Yıldız' => 0, 'İş Atı' => 0, 'Bilmece' => 0, 'Zayıf' => 0];
        foreach ($l as $x) {
            $pay = $topAdet > 0 ? $x['adet'] / $topAdet * 100 : 0; $pop = $pay >= $esikPop; $kar = $x['cm'] >= $ortCm;
            $s = $pop && $kar ? 'Yıldız' : ($pop ? 'İş Atı' : ($kar ? 'Bilmece' : 'Zayıf')); $sinif[$s]++;
            $sat[] = [$x['ad'], $s, $x['adet'], round($pay, 1), $x['fiyat'], $x['bm'], $x['cm'], $x['tcm'], $x['fc'], $ne[$s]];
        }
        $sira = ['Yıldız' => 0, 'İş Atı' => 1, 'Bilmece' => 2, 'Zayıf' => 3];
        usort($sat, fn ($a, $b) => $sira[$a[1]] <=> $sira[$b[1]] ?: $b[7] <=> $a[7]);
        $ad = _mrSinifAd(); foreach ($sat as &$x) $x[1] = $ad[$x[1]]; unset($x);
        return ['sat' => $sat, 'esik_pop' => round($esikPop, 2), 'esik_cm' => round($ortCm, 2), 'sinif' => $sinif, 'yok' => $fc['yok']];
    }
    // Teorik ve gerçek tüketim (malzeme bazında): teorik = reçeteli satış tüketimi; gerçek = teorik + fire + sayım kaybı (sayım fazlası düşer)
    function _mrAvt($sid, array $dn)
    {
        $tk = _mrHareket($sid, $dn['bas'], $dn['bit'], ['tuketim'], ['adisyon']); $fr = _mrHareket($sid, $dn['bas'], $dn['bit'], ['fire']); $sy = _mrHareket($sid, $dn['bas'], $dn['bit'], ['sayim'], ['sayim']);
        $m = [];
        $ekle = function ($l, $alan) use (&$m) { foreach ($l as $h) { $id = (int) $h->malzeme_id; $q = (float) $h->miktar; $m[$id][$alan . '_q'] = ($m[$id][$alan . '_q'] ?? 0) + $q; $m[$id][$alan . '_t'] = ($m[$id][$alan . '_t'] ?? 0) + $q * (float) $h->birim_maliyet; } };
        $ekle($tk, 'tk'); $ekle($fr, 'fr'); $ekle($sy, 'sy');
        $o = [];
        foreach ($m as $id => $v) {
            $teoQ = -($v['tk_q'] ?? 0); $teoT = -($v['tk_t'] ?? 0); $frQ = -($v['fr_q'] ?? 0); $frT = -($v['fr_t'] ?? 0); $syQ = -($v['sy_q'] ?? 0); $syT = -($v['sy_t'] ?? 0); // sayım: eksi hareket = kayıp → tüketime eklenir
            $gerQ = $teoQ + $frQ + $syQ; $gerT = $teoT + $frT + $syT; if (abs($teoT) < 0.005 && abs($gerT) < 0.005) continue;
            $o[] = [_srMalz($id), _srKat($id), _mrBirim($id), round($teoQ, 3), round($teoT, 2), round($frQ, 3), round($frT, 2), round($syQ, 3), round($syT, 2), round($gerQ, 3), round($gerT, 2), round($gerT - $teoT, 2), $teoT > 0 ? round(($gerT - $teoT) / $teoT * 100, 1) : null];
        }
        usort($o, fn ($a, $b) => $b[11] <=> $a[11]);
        return ['sat' => $o, 'teo' => array_sum(array_column($o, 4)), 'ger' => array_sum(array_column($o, 10)), 'fire' => array_sum(array_column($o, 6)), 'sayim' => array_sum(array_column($o, 8)), 'sayim_var' => $sy->isNotEmpty()];
    }
    function _mrAvtSayfa(array $a, array $dn, array $d)
    {
        return _srSayfa('Teorik ve Gerçek Tüketim', 'Teorik = satışlarda reçeteye göre stoktan düşen. Fire = fire kayıtları. Sayım = sayım düzeltmesi (eksi çıkan kayıp gerçeğe eklenir, fazla çıkan düşülür). Gerçek = teorik + fire + sayım kaybı. Fark = gerçek − teorik.' . (!$a['sayim_var'] ? ' DİKKAT: bu dönemde sayım yapılmamış; gerçek tüketim yalnız fireyi içerir, sayımla ortaya çıkacak kayıp henüz görünmez.' : ''),
            [['Malzeme', 'm', 22], ['Kategori', 'm', 13], ['Birim', 'm', 6], ['Teorik Miktar', 's', 11], ['Teorik ₺', 'tl', 12, 'top'], ['Fire Miktar', 's', 10], ['Fire ₺', 'tl', 11, 'top'], ['Sayım Kaybı', 's', 10], ['Sayım ₺', 'tl', 11, 'top'], ['Gerçek Miktar', 's', 11], ['Gerçek ₺', 'tl', 12, 'top'], ['Fark ₺', 'tl', 12, 'top bar'], ['Fark %', 'y', 9, 'esik:3']],
            $a['sat'], !$d['tuketim'] ? _mrBos('tuketim_yok', $dn) : _mrBos('tuketim', $dn));
    }
    function _mrMenuSayfa(array $mm, array $dn, array $d)
    {
        return _srSayfa('Menü Mühendisliği', 'Katkı payı = ortalama satış fiyatı − reçete maliyeti. Popüler: satış payı ≥ %' . number_format((float) $mm['esik_pop'], 2, ',', '.') . ' (%70 × 1/ürün sayısı). Kârlı: katkı payı ≥ ' . number_format((float) $mm['esik_cm'], 2, ',', '.') . ' ₺ (ağırlıklı ortalama). Yalnız reçetesi olan ürünler; ikramlar hariç. Grup: çok satıyor mu (satış payı eşiği) × tabak başına çok kazandırıyor mu (katkı payı eşiği).',
            [['Ürün', 'm', 22], ['Grup', 'm', 24], ['Adet', 's', 8, 'top'], ['Satış Payı %', 'y', 10], ['Ort. Fiyat', 'tl', 11], ['Maliyet', 'tl', 10], ['Katkı Payı', 'tl', 11], ['Toplam Katkı', 'tl', 12, 'top bar'], ['Food Cost %', 'y', 10, 'esik:35'], ['Ne Yapmalı', 'm', 44]],
            $mm['sat'], !$d['recete'] ? _mrBos('recete_yok', $dn) : _mrBos('satis', $dn));
    }
    function _mrFireSayfa(array $f, array $dn, array $d)
    {
        return _srSayfa('Fire Kayıtları', 'Maliyet = miktar × kayıttaki birim maliyet. "Pişmiş ürün iptali" = pişmeye başlamış ürün iptal edilince reçetesi otomatik fire yazılır. Sebep = fire girilirken seçilen sebep (yoksa açıklama).',
            [['Tarih', 't', 11], ['Saat', 'm', 6], ['Malzeme', 'm', 22], ['Kategori', 'm', 13], ['Miktar', 's', 9], ['Birim', 'm', 6], ['Maliyet', 'tl', 11, 'top bar'], ['Kaynak', 'm', 16], ['Sebep', 'm', 24], ['Personel', 'm', 14]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['malzeme'], $x['kategori'], $x['miktar'], $x['birim'], $x['maliyet'], $x['kaynak'], $x['sebep'], $x['personel']], $f), !$d['fire'] ? _mrBos('fire_yok', $dn) : _mrBos('fire', $dn));
    }
    function _mrIptaller($sid, array $dn)
    {
        if (!_srTablo('iptal_indirim_loglari')) return [];
        try {
            $l = DB::table('iptal_indirim_loglari')->where('sube_id', $sid)->whereIn('tip', ['void', 'ikram'])->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])->orderByDesc('created_at')->get();
        } catch (\Throwable $e) { return []; }
        $kIds = $l->pluck('adisyon_kalem_id')->filter()->unique()->values()->all(); $kal = collect();
        if ($kIds) { $sel = ['id', 'urun_adi', 'adet']; foreach (['gonderim_zamani', 'basla_zamani', 'hazir_zamani'] as $c) if (_srKolon('adisyon_kalemleri', $c)) $sel[] = $c; try { $kal = DB::table('adisyon_kalemleri')->whereIn('id', $kIds)->get($sel)->keyBy('id'); } catch (\Throwable $e) {} }
        return $l->map(function ($x) use ($kal) {
            $k = $x->adisyon_kalem_id ? ($kal[$x->adisyon_kalem_id] ?? null) : null;
            $asama = !$k ? 'Adisyon geneli' : (!empty($k->hazir_zamani) ? 'Hazırlanmıştı' : (!empty($k->basla_zamani) ? 'Pişiyordu' : (!empty($k->gonderim_zamani) ? 'Mutfağa gitmişti' : 'Mutfağa gitmemişti')));
            return ['tarih' => substr((string) $x->created_at, 0, 10), 'saat' => substr((string) $x->created_at, 11, 5), 'tip' => $x->tip === 'void' ? 'İptal' : 'İkram', 'urun' => $k ? (string) $k->urun_adi : '—', 'adet' => $k ? (float) $k->adet : null,
                'tutar' => (float) $x->tutar, 'sebep' => trim((string) $x->sebep) ?: 'Belirtilmemiş', 'asama' => $asama, 'mutfak' => in_array($asama, ['Hazırlanmıştı', 'Pişiyordu', 'Mutfağa gitmişti'], true), 'personel' => _mrPersonelAd($x->personel_id)];
        })->all();
    }
    function _mrIptalSayfa(array $ip, array $dn)
    {
        return _srSayfa('İptal ve İkramlar', 'Aşama = iptal anında kalemin mutfaktaki durumu. "Pişiyordu / Hazırlanmıştı" iptaller malzeme kaybıdır (reçetesi fire yazılır).',
            [['Tarih', 't', 11], ['Saat', 'm', 6], ['Tür', 'm', 7], ['Ürün', 'm', 22], ['Adet', 's', 6], ['Tutar', 'tl', 11, 'top bar'], ['Sebep', 'm', 22], ['Aşama', 'm', 15], ['Personel', 'm', 14]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['tip'], $x['urun'], $x['adet'], $x['tutar'], $x['sebep'], $x['asama'], $x['personel']], $ip), _mrBos('iptal', $dn));
    }
    function _mrSicaklik($sid, array $dn)
    {
        return _mrDefter($sid, $dn['bas'], $dn['bit'], 'sicaklik')->map(fn ($x) => ['tarih' => (string) ($x->tarih ?: substr((string) $x->created_at, 0, 10)), 'saat' => substr((string) $x->created_at, 11, 5), 'ekipman' => trim((string) $x->baslik) ?: '—',
            'kategori' => (string) ($x->kategori ?: ''), 'deger' => (string) $x->deger, 'hedef' => (string) $x->deger2, 'birim' => (string) ($x->birim ?: '°C'), 'sonuc' => (string) $x->sonuc, 'uygunsuz' => $x->sonuc === 'uygunsuz', 'personel' => (string) ($x->personel_ad ?: '—'), 'not' => (string) ($x->not ?? '')])->all();
    }
    function _mrSicaklikSayfa(array $s, array $dn, array $d, $sadeceUygunsuz = false)
    {
        $l = $sadeceUygunsuz ? array_values(array_filter($s, fn ($x) => $x['uygunsuz'])) : $s;
        return _srSayfa($sadeceUygunsuz ? 'Uygunsuz Sıcaklıklar' : 'Sıcaklık Ölçümleri', 'Kontrol Defteri\'ne girilen ölçümler. Hedef = kayıttaki hedef/aralık; sonuç kayıt anında hesaplanır.',
            [['Tarih', 't', 11], ['Saat', 'm', 6], ['Ekipman', 'm', 22], ['Ölçüm', 'm', 9], ['Hedef', 'm', 11], ['Birim', 'm', 6], ['Sonuç', 'm', 10], ['Giren', 'm', 16], ['Not', 'm', 26]],
            array_map(fn ($x) => [$x['tarih'], $x['saat'], $x['ekipman'], $x['deger'], $x['hedef'], $x['birim'], $x['uygunsuz'] ? 'UYGUNSUZ' : ($x['sonuc'] === 'uygun' ? 'Uygun' : ($x['sonuc'] ?: '—')), $x['personel'], $x['not']], $l),
            !$d['defter'] ? _mrBos('defter_yok', $dn) : ($sadeceUygunsuz && $s ? 'Seçili dönemde uygunsuz sıcaklık ölçümü yok.' : _mrBos('sicaklik', $dn)));
    }
    // Geri alınan (uretim_iptal) üretimler raporlara girmez
    function _mrIptalUretim($sid) { static $c = []; if (!isset($c[$sid])) { try { $c[$sid] = DB::table('stok_hareketleri')->where('sube_id', $sid)->where('kaynak_tip', 'uretim_iptal')->pluck('kaynak_id')->flip()->all(); } catch (\Throwable $e) { $c[$sid] = []; } } return $c[$sid]; }
    function _mrUretimler($sid, array $dn)
    {
        $ip = _mrIptalUretim($sid); $l = _mrHareket($sid, $dn['bas'], $dn['bit'], ['iade'], ['uretim'])->reject(fn ($h) => isset($ip[$h->kaynak_id]))->values(); if ($l->isEmpty()) return [];
        $p = _srTablo('stok_partileri') ? DB::table('stok_partileri')->whereIn('id', $l->pluck('kaynak_id')->filter()->all())->get()->keyBy('id') : collect();
        return $l->map(function ($h) use ($p) { $pt = $h->kaynak_id ? ($p[$h->kaynak_id] ?? null) : null;
            return [substr((string) $h->created_at, 0, 10), substr((string) $h->created_at, 11, 5), _srMalz($h->malzeme_id), round((float) $h->miktar, 3), _mrBirim($h->malzeme_id), round((float) $h->miktar * (float) $h->birim_maliyet, 2),
                $pt ? (string) $pt->lot_no : '—', $pt && $pt->skt ? substr((string) $pt->skt, 0, 10) : null, $pt ? round((float) $pt->kalan_miktar, 3) : null, _mrPersonelAd($h->personel_id)]; })->all();
    }
    function _mrUretimKullanim($sid, array $dn)
    {
        if (!_srTablo('receteler')) return [];
        $ym = DB::table('receteler')->whereNotNull('uretilen_malzeme_id')->pluck('uretilen_malzeme_id')->unique()->all(); if (!$ym) return [];
        $ip = _mrIptalUretim($sid); $ur = _mrHareket($sid, $dn['bas'], $dn['bit'], ['iade'], ['uretim'])->reject(fn ($h) => isset($ip[$h->kaynak_id]))->whereIn('malzeme_id', $ym)->groupBy('malzeme_id');
        $tk = _mrHareket($sid, $dn['bas'], $dn['bit'], ['tuketim'], ['adisyon', 'uretim_hammadde'])->whereIn('malzeme_id', $ym)->groupBy('malzeme_id');
        $fr = _mrHareket($sid, $dn['bas'], $dn['bit'], ['fire'])->whereIn('malzeme_id', $ym)->groupBy('malzeme_id');
        $st = function_exists('_srStokMevcut') ? _srStokMevcut($sid) : collect(); $o = [];
        foreach ($ym as $mid) {
            $u = (float) ($ur[$mid] ?? collect())->sum('miktar'); $t = -(float) ($tk[$mid] ?? collect())->sum('miktar'); $f = -(float) ($fr[$mid] ?? collect())->sum('miktar');
            if ($u <= 0 && $t <= 0) continue;
            $o[] = [_srMalz($mid), _mrBirim($mid), round($u, 3), round($t, 3), round($f, 3), $u > 0 ? round($t / $u * 100, 1) : null, $u > 0 ? round($f / $u * 100, 1) : null, round((float) ($st[$mid] ?? 0), 3)];
        }
        usort($o, fn ($a, $b) => ($a[5] ?? 999) <=> ($b[5] ?? 999));
        return $o;
    }
    function _mrMesai($sid, array $dn)
    {
        $ekip = _mrEkip($sid); if ($ekip->isEmpty()) return ['ekip' => $ekip, 'sat' => []];
        $ids = $ekip->keys()->all(); $o = [];
        $ms = _srTablo('personel_mesai') ? DB::table('personel_mesai')->where('sube_id', $sid)->whereIn('personel_id', $ids)->whereDate('giris_at', '>=', $dn['bas'])->whereDate('giris_at', '<=', $dn['bit'])->get()->groupBy('personel_id') : collect();
        $vd = _srTablo('personel_vardiya') ? DB::table('personel_vardiya')->where('sube_id', $sid)->whereIn('personel_id', $ids)->whereDate('tarih', '>=', $dn['bas'])->whereDate('tarih', '<=', $dn['bit'])->get()->groupBy('personel_id') : collect();
        $ym = _srTablo('personel_yemek') ? DB::table('personel_yemek')->where('sube_id', $sid)->whereIn('personel_id', $ids)->whereDate('tarih', '>=', $dn['bas'])->whereDate('tarih', '<=', $dn['bit'])->get()->groupBy('personel_id') : collect();
        $depAd = _srTablo('departmanlar') ? DB::table('departmanlar')->pluck('ad', 'id') : collect();
        foreach ($ekip as $id => $p) {
            $m = $ms[$id] ?? collect(); $saat = 0; $gun = [];
            foreach ($m as $x) { if (!$x->giris_at) continue; $bit = $x->cikis_at ?: null; if ($bit) $saat += max(0, (strtotime((string) $bit) - strtotime((string) $x->giris_at)) / 3600); $gun[substr((string) $x->giris_at, 0, 10)] = 1; }
            $plan = 0; foreach ($vd[$id] ?? [] as $v) { $b = strtotime('2000-01-01 ' . $v->baslangic); $e = strtotime('2000-01-01 ' . $v->bitis); if ($e <= $b) $e += 86400; $plan += ($e - $b) / 3600; }
            $o[] = [(string) $p->ad, (string) ($depAd[$p->departman_id ?? 0] ?? ($p->rol === 'mutfak' ? 'Mutfak' : '—')), !empty($p->departman_sorumlu) ? 'Sorumlu' : '', count($gun), round($saat, 1), count($gun) ? round($saat / count($gun), 1) : null, round($plan, 1) ?: null,
                $m->whereNull('cikis_at')->count(), round((float) ($ym[$id] ?? collect())->sum('tutar'), 2)];
        }
        usort($o, fn ($a, $b) => $b[4] <=> $a[4]);
        return ['ekip' => $ekip, 'sat' => $o];
    }
    function _mrTalepler($sid, array $dn)
    {
        if (!_srTablo('satinalma_talepleri')) return collect();
        $dep = _mrMutfakDepIds($sid); $depAd = _srTablo('departmanlar') ? DB::table('departmanlar')->pluck('ad', 'id') : collect();
        try {
            return DB::table('satinalma_talepleri')->where('sube_id', $sid)->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit'])
                ->where(function ($q) use ($dep) {
                    if ($dep && _srKolon('satinalma_talepleri', 'departman_id')) $q->whereIn('departman_id', array_keys($dep));
                    foreach (['mutfak', 'pastane', 'kasap', 'bulaşık', 'bulasik', 'soğuk', 'tatlı'] as $w) $q->orWhereRaw('LOWER(departman) LIKE ?', ['%' . $w . '%']);
                })->orderByDesc('created_at')->get()->map(function ($t) use ($depAd) { $t->dep_ad = ($t->departman_id ?? null) ? ($depAd[$t->departman_id] ?? $t->departman) : ($t->departman ?: 'Mutfak'); return $t; });
        } catch (\Throwable $e) { return collect(); }
    }
    function _mrTukendi($sid, array $dn)
    {
        if (!_srTablo('urun_tukendi_log')) return [];
        $bas = $dn['bas'] . ' 00:00:00'; $bit = min(now()->toDateTimeString(), $dn['bit'] . ' 23:59:59');
        $l = DB::table('urun_tukendi_log')->where('sube_id', $sid)->where('created_at', '<=', $bit)->orderBy('created_at')->get()->groupBy('urun_id'); $o = [];
        foreach ($l as $uid => $g) {
            $acik = null; $sure = 0; $kez = 0; $ilk = null; $son = null; $ad = (string) $g->last()->urun_ad;
            foreach ($g as $x) {
                $t = (string) $x->created_at;
                if ($x->tukendi) { if ($acik === null) { $acik = max($t, $bas); if ($t >= $bas) { $kez++; $ilk = $ilk ?: $t; $son = $t; } } }
                elseif ($acik !== null) { if ($t >= $bas) $sure += max(0, strtotime($t) - strtotime($acik)); $acik = null; }
            }
            $simdi = false; if ($acik !== null) { $sure += max(0, strtotime($bit) - strtotime($acik)); $simdi = true; }
            if (!$kez && !$simdi) continue;
            $o[] = [$ad, $kez, round($sure / 3600, 1), $ilk ? substr($ilk, 0, 16) : '—', $son ? substr($son, 0, 16) : '—', $simdi ? 'TÜKENMİŞ' : 'Satışta'];
        }
        usort($o, fn ($a, $b) => $b[2] <=> $a[2]);
        return $o;
    }
}

// =====================================================================
// RAPORLAR — her biri sayfalar dizisi döner (Pano + tablolar)
// =====================================================================
if (!function_exists('_mrRapor')) {
    function _mrRapor($anahtar, $sid, array $dn)
    {
        _mrEnsure();
        $d = _mrVeriDurumu($sid);
        $pano = fn (array $kpi, array $bloklar, $bos = null) => ['ad' => 'Pano', 'tip' => 'pano', 'kpi' => $kpi, 'bloklar' => array_values(array_filter($bloklar, fn ($b) => count($b['sat']) > 0)), 'bos' => $bos];
        $K = fn () => _mrKalemler($sid, $dn['bas'], $dn['bit']); $KO = fn () => _mrKalemler($sid, $dn['obas'], $dn['obit']);
        $bosKds = !$d['kds'] ? _mrBos('kds_yok', $dn) : _mrBos('kds', $dn);
        $yuzde = fn ($a, $b) => _srYuzde($a, $b);
        $kpiY = fn ($ad, $v, $renk) => _srKpi($ad, $v ?? 'Veri yok', $v === null ? 'metin' : 'y', $renk);
        $kpiD = fn ($ad, $v, $renk) => _srKpi($ad, $v === null ? 'Veri yok' : number_format($v, 1, ',', '.') . ' dk', 'metin', $renk);
        $blokSaat = fn ($k, $renk) => _srBlok('Saatlere göre porsiyon', 'col', $renk, [['Saat', 'm'], ['Porsiyon', 's'], ['Ort. süre (dk)', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[3]], _mrSaatlik($k)));
        $blokYavas = fn ($k, $renk) => _srBlok('En yavaş ürünler (ort. toplam dk)', 'bar', $renk, [['Ürün', 'm'], ['Ort. dk', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[6], $x[8]], array_slice(collect(_mrUrunSure($k))->sortByDesc(6)->values()->all(), 0, 10)));
        $blokCok = fn ($k, $renk) => _srBlok('En çok satan ürünler (porsiyon)', 'bar', $renk, [['Ürün', 'm'], ['Porsiyon', 's'], ['Satış', 'tl']], collect(_mrSatis($k))->groupBy('ad')->map(fn ($g, $a) => [$a, round($g->sum('adet'), 1), round($g->sum('tutar'), 2)])->sortByDesc(1)->take(10)->values()->all());
        $dagil = function (array $l, $anahtar, $deger) { $o = []; foreach ($l as $x) $o[$x[$anahtar]] = ($o[$x[$anahtar]] ?? 0) + $x[$deger]; arsort($o); return $o; };
        $blokDagil = fn ($bas, $graf, $renk, $kol, array $m, $lim = 12) => _srBlok($bas, $graf, $renk, [[$kol, 'm'], ['Tutar', 'tl'], ['', 'm']], array_map(fn ($a, $v) => [$a, round($v, 2), ''], array_slice(array_keys($m), 0, $lim), array_slice(array_values($m), 0, $lim)));

        switch ($anahtar) {
            // ---------------- DÖNEMSEL ----------------
            case 'gunluk':
                $k = $K(); $h = _mrHiz($k); $ko = $KO(); $fire = _mrFireler($sid, $dn['bas'], $dn['bit']); $ip = array_values(array_filter(_mrIptaller($sid, $dn), fn ($x) => $x['mutfak']));
                $sc = _mrSicaklik($sid, $dn); $krit = _srKritikStok($sid); $skt = _srSktPartiler($sid, 3);
                $tuk = _srTablo('urunler') ? DB::table('urunler')->where('sube_id', $sid)->where('tukendi', 1)->pluck('ad')->all() : [];
                return [
                    $pano([_srKpi('Porsiyon (' . $dn['ad'] . ')', _mrPorsiyon($k), 'sayi', 'EA580C'), _srKpi('Önceki güne göre', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) ?? '—', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) === null ? 'metin' : 'y', 'C2410C'),
                        $kpiD('Ort. mutfak süresi', $h['top'], 'DC2626'), $kpiY('Hedefe uyum', $h['uyum'], '059669'), _srKpi('Geciken kalem', $h['geciken'], 'sayi', 'B91C1C'),
                        _srKpi('Fire', array_sum(array_column($fire, 'maliyet')), 'tl', 'C2410C'), _srKpi('Mutfak kaynaklı iptal', count($ip), 'sayi', 'BE123C'), _srKpi('Uygunsuz sıcaklık', count(array_filter($sc, fn ($x) => $x['uygunsuz'])), 'sayi', '0284C7'),
                        _srKpi('Şu an tükenmiş ürün', count($tuk), 'sayi', '9F1239'), _srKpi('Kritik stok altında', count($krit), 'sayi', 'D97706'), _srKpi('SKT 3 gün içinde', count($skt), 'sayi', 'B91C1C'), _srKpi('Rapor tarihi', today()->format('d.m.Y'), 'metin', '1E293B')],
                        [$blokSaat($k, 'EA580C'), $blokYavas($k, 'DC2626'), $blokCok($k, 'C2410C')], !$k ? _mrBos('satis', $dn) : null),
                    _mrGecikenSayfa(_mrGecikenler($k), $bosKds),
                    _mrUrunSureSayfa(_mrUrunSure($k), $bosKds),
                    _mrFireSayfa($fire, $dn, $d),
                    _mrIptalSayfa($ip, $dn),
                    _mrSicaklikSayfa($sc, $dn, $d, true),
                    _srSayfa('Kritik Stok', 'Mevcut stok (stok hareketleri toplamı) kritik seviyenin altında olan malzemeler.', [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Mevcut', 's', 10], ['Kritik Seviye', 's', 11], ['Eksik', 's', 10, 'bar']], $krit, _mrBos('kritik', $dn)),
                    _srSayfa('SKT Yaklaşan Partiler (3 gün)', 'Stokta kalan ve son kullanma tarihi 3 gün içinde dolan ya da geçmiş partiler.', [['Malzeme', 'm', 22], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 7], ['Değer', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 20]],
                        array_map(fn ($x) => [$x['malzeme'], $x['lot'], $x['skt'], $x['kalan_gun'], $x['kalan'], $x['birim'], $x['deger'], $x['ted']], $skt), !$d['parti'] ? _mrBos('parti_yok', $dn) : 'SKT\'si 3 gün içinde dolan ya da geçmiş, stokta kalan parti yok.'),
                    _srSayfa('Şu An Tükenmiş Ürünler', 'Rapor anında "tükendi (86)" işaretli ürünler.', [['Ürün', 'm', 30]], array_map(fn ($a) => [$a], $tuk), 'Şu an tükenmiş ürün yok.'),
                ];
            case 'haftalik':
                $k = $K(); $ko = $KO(); $h = _mrHiz($k); $ho = _mrHiz($ko); $fire = _mrFireler($sid, $dn['bas'], $dn['bit']); $ip = _mrIptaller($sid, $dn);
                $fSeb = $dagil($fire, 'sebep', 'maliyet');
                return [
                    $pano([_srKpi('Haftalık porsiyon', _mrPorsiyon($k), 'sayi', 'D97706'), _srKpi('Önceki haftaya göre', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) ?? '—', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) === null ? 'metin' : 'y', 'B45309'),
                        $kpiD('Ort. mutfak süresi', $h['top'], 'DC2626'), $kpiD('Önceki hafta süre', $ho['top'], '991B1B'), $kpiY('Hedefe uyum', $h['uyum'], '059669'), _srKpi('Geciken kalem', $h['geciken'], 'sayi', 'B91C1C'),
                        _srKpi('Fire', array_sum(array_column($fire, 'maliyet')), 'tl', 'C2410C'), _srKpi('İptal / ikram tutarı', array_sum(array_column($ip, 'tutar')), 'tl', 'BE123C')],
                        [_srBlok('Günlere göre porsiyon', 'col', 'D97706', [['Gün', 'm'], ['Porsiyon', 's'], ['Ort. süre (dk)', 's']], array_map(fn ($x) => [$x[0], $x[1], $x[4]], _mrGunluk($k, $dn))),
                         _srBlok('İstasyonlara göre porsiyon', 'pie', 'F97316', [['İstasyon', 'm'], ['Porsiyon', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1], ''], _mrIstasyonlar($k))),
                         $blokYavas($k, 'DC2626'), $blokDagil('Fire sebepleri', 'pie', 'C2410C', 'Sebep', $fSeb), $blokCok($k, 'B45309')], !$k ? _mrBos('satis', $dn) : null),
                    _mrGunlukSayfa(_mrGunluk($k, $dn, $fire), _mrBos('satis', $dn)), _mrIstasyonSayfa(_mrIstasyonlar($k), $bosKds), _mrUrunSureSayfa(_mrUrunSure($k), $bosKds),
                    _mrGecikenSayfa(_mrGecikenler($k), $bosKds), _mrFireSayfa($fire, $dn, $d), _mrIptalSayfa($ip, $dn),
                ];
            case 'aylik':
                $k = $K(); $ko = $KO(); $h = _mrHiz($k); $fire = _mrFireler($sid, $dn['bas'], $dn['bit']); $fc = _mrFoodCost($k); $fco = _mrFoodCost($ko); $avt = _mrAvt($sid, $dn); $mm = _mrMenuMuh($k);
                $ip = _mrIptaller($sid, $dn); $sc = _mrSicaklik($sid, $dn); $alis = function_exists('_srAlis') ? _srToplam(_srAlis($sid, $dn['bas'], $dn['bit'])) : 0; $frT = array_sum(array_column($fire, 'maliyet')); $kv = _mrKuver($sid, $dn['bas'], $dn['bit']);
                $ms = _mrMesai($sid, $dn); $saat = array_sum(array_column($ms['sat'], 4));
                return [
                    $pano([_srKpi('Aylık porsiyon', _mrPorsiyon($k), 'sayi', 'C2410C'), _srKpi('Önceki aya göre', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) ?? '—', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) === null ? 'metin' : 'y', '9A3412'),
                        $kpiY('Teorik food cost', $fc['fc'], '059669'), $kpiY('Önceki ay food cost', $fco['fc'], '047857'), _srKpi('Tüketim farkı (gerçek − teorik)', round($avt['ger'] - $avt['teo'], 2), 'tl', 'B91C1C'),
                        _srKpi('Fire', $frT, 'tl', 'C2410C'), $kpiY('Fire / alış', $alis > 0 ? round($frT / $alis * 100, 1) : null, '9A3412'), _srKpi('Kuver başına fire', $kv > 0 ? round($frT / $kv, 2) : 'Kuver yok', $kv > 0 ? 'tl' : 'metin', '7C2D12'),
                        $kpiD('Ort. mutfak süresi', $h['top'], 'DC2626'), $kpiY('Hedefe uyum', $h['uyum'], '059669'), _srKpi('En iyi / en zayıf ürün sayısı', ($mm['sinif']['Yıldız'] ?? 0) . ' / ' . ($mm['sinif']['Zayıf'] ?? 0), 'metin', '7C3AED'),
                        _srKpi('Uygunsuz sıcaklık', count(array_filter($sc, fn ($x) => $x['uygunsuz'])), 'sayi', '0284C7'), _srKpi('Saat başına porsiyon', $saat > 0 ? round(_mrPorsiyon($k) / $saat, 1) : 'Mesai yok', $saat > 0 ? 'sayi' : 'metin', '4F46E5'), _srKpi('Dönem', $dn['etiket'], 'metin', '431407')],
                        [_srBlok('Menü grupları (ürün sayısı)', 'pie', '7C3AED', [['Grup', 'm'], ['Ürün', 's'], ['', 'm']], array_map(fn ($a, $v) => [_mrSinifAd()[$a], $v, ''], array_keys($mm['sinif']), array_values($mm['sinif']))),
                         _srBlok('Food cost oranı en yüksek ürünler', 'bar', '059669', [['Ürün', 'm'], ['Food cost %', 'y'], ['Satış', 'tl']], array_map(fn ($x) => [$x['ad'], $x['fc'], $x['tutar']], array_slice($fc['var'], 0, 10))),
                         _srBlok('Tüketim farkı en büyük malzemeler', 'bar', 'B91C1C', [['Malzeme', 'm'], ['Fark ₺', 'tl'], ['Fark %', 'y']], array_map(fn ($x) => [$x[0], $x[11], $x[12]], array_slice(array_filter($avt['sat'], fn ($x) => $x[11] > 0), 0, 10))),
                         $blokDagil('Fire sebepleri', 'pie', 'C2410C', 'Sebep', $dagil($fire, 'sebep', 'maliyet'))], !$k ? _mrBos('satis', $dn) : null),
                    _mrGunlukSayfa(_mrGunluk($k, $dn, $fire), _mrBos('satis', $dn)), _mrMenuSayfa($mm, $dn, $d), _mrAvtSayfa($avt, $dn, $d),
                    _srSayfa('Ürün Food Cost', 'Food cost = reçete maliyeti × adet ÷ satış tutarı. Maliyet güncel malzeme maliyetiyle hesaplanır.', [['Ürün', 'm', 24], ['Adet', 's', 8, 'top'], ['Satış', 'tl', 12, 'top'], ['Ort. Fiyat', 'tl', 11], ['Birim Maliyet', 'tl', 11], ['Toplam Maliyet', 'tl', 12, 'top'], ['Food Cost %', 'y', 10, 'esik:35'], ['Katkı Payı', 'tl', 11], ['Toplam Katkı', 'tl', 12, 'top bar']],
                        array_map(fn ($x) => [$x['ad'], $x['adet'], $x['tutar'], $x['fiyat'], $x['bm'], $x['tm'], $x['fc'], $x['cm'], $x['tcm']], $fc['var']), !$d['recete'] ? _mrBos('recete_yok', $dn) : _mrBos('satis', $dn)),
                    _mrIstasyonSayfa(_mrIstasyonlar($k), $bosKds), _mrFireSayfa($fire, $dn, $d), _mrIptalSayfa($ip, $dn), _mrSicaklikSayfa($sc, $dn, $d, true),
                    _srSayfa('Mutfak Ekibi', 'Mesai = QR ile giriş-çıkış (kapanmamış mesai saate katılmaz).', [['Personel', 'm', 20], ['Departman', 'm', 14], ['', 'm', 8], ['Gün', 's', 6, 'top'], ['Saat', 's', 8, 'top bar'], ['Ort. Günlük', 's', 9], ['Planlanan Saat', 's', 10, 'top'], ['Kapanmamış', 's', 9], ['Personel Yemeği', 'tl', 11, 'top']], $ms['sat'], $ms['ekip']->isEmpty() ? _mrBos('ekip_yok', $dn) : (!$d['mesai'] ? _mrBos('mesai_yok', $dn) : _mrBos('mesai', $dn))),
                ];

            // ---------------- HIZ & SERVİS ----------------
            case 'hiz':
                $k = $K(); $h = _mrHiz($k); $ho = _mrHiz($KO()); $gAd = ['', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                $gs = []; foreach (_mrOlcum($k) as $x) { $gs[$x['gun']][$x['saat']][] = $x['top']; }
                $saatler = []; foreach ($gs as $g) foreach ($g as $s => $v) $saatler[$s] = 1; ksort($saatler); $saatler = array_keys($saatler);
                $isi = []; for ($g = 1; $g <= 7; $g++) { if (!isset($gs[$g])) continue; $r = [$gAd[$g]]; foreach ($saatler as $s) $r[] = isset($gs[$g][$s]) ? _mrOrt($gs[$g][$s]) : null; $isi[] = $r; }
                return [$pano([_srKpi('Ölçülen kalem', $h['n'], 'sayi', 'DC2626'), $kpiD('Ort. bekleme', $h['bek'], 'F97316'), $kpiD('Ort. hazırlık', $h['haz'], 'EA580C'), $kpiD('Ort. toplam', $h['top'], 'DC2626'),
                        $kpiD('Medyan', $h['medyan'], 'B91C1C'), $kpiD('%90 (en yavaş %10 sınırı)', $h['p90'], '991B1B'), $kpiY('Hedefe uyum', $h['uyum'], '059669'), $kpiY('Önceki dönem uyum', $ho['uyum'], '047857'),
                        _srKpi('Geciken kalem', $h['geciken'], 'sayi', 'B91C1C'), $kpiD('Gecikenlerde ort. aşım', $h['gec_ort'], '7F1D1D')],
                        [_srBlok('Saatlere göre ortalama süre (dk)', 'col', 'DC2626', [['Saat', 'm'], ['Ort. dk', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[3] ?? 0, $x[4]], array_filter(_mrSaatlik($k), fn ($x) => $x[2] > 0))),
                         _srBlok('Günlere göre ortalama süre (dk)', 'col', 'F97316', [['Gün', 'm'], ['Ort. dk', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[4] ?? 0, $x[5]], array_filter(_mrGunluk($k, $dn), fn ($x) => $x[3] > 0)))], !$h['n'] ? $bosKds : null),
                    ['_oto' => []] + _srSayfa('Gün × Saat (ort. dk)', 'Her hücre o gün ve saatte mutfağa giden kalemlerin ortalama toplam süresi (dk). Boş = sipariş yok. Yoğun saatlerde sürenin nasıl uzadığını gösterir.',
                        array_merge([['Gün', 'm', 8]], array_map(fn ($s) => [sprintf('%02d', $s), 's', 6, 'mat'], $saatler)), $isi, $bosKds),
                    _mrSaatlikSayfa(_mrSaatlik($k), $bosKds), _mrGunlukSayfa(_mrGunluk($k, $dn, _mrFireler($sid, $dn['bas'], $dn['bit'])), _mrBos('satis', $dn)), _mrGecikenSayfa(_mrGecikenler($k), $bosKds)];
            case 'urun_sure':
                $k = $K(); $u = _mrUrunSure($k);
                return [$pano([_srKpi('Ölçülen ürün', count($u), 'sayi', 'E11D48'), _srKpi('Hedefe uyumu %80 altı', count(array_filter($u, fn ($x) => $x[8] < 80)), 'sayi', 'B91C1C'), $kpiY('Genel uyum', _mrHiz($k)['uyum'], '059669')],
                        [$blokYavas($k, 'E11D48'), _srBlok('En düşük hedef uyumu (%)', 'bar', 'B91C1C', [['Ürün', 'm'], ['Uyum %', 'y'], ['Ölçülen', 's']], array_map(fn ($x) => [$x[0], $x[8], $x[2]], array_slice(array_filter($u, fn ($x) => $x[2] >= 2), 0, 10)))], !$u ? $bosKds : null),
                    _mrUrunSureSayfa($u, $bosKds), _mrGecikenSayfa(_mrGecikenler($k), $bosKds)];
            case 'istasyon':
                $k = $K(); $i = _mrIstasyonlar($k);
                $isat = []; foreach (_mrSatis($k) as $x) if (!$x['bar']) $isat[_mrIstAd($x['ist'])][$x['saat']] = ($isat[_mrIstAd($x['ist'])][$x['saat']] ?? 0) + $x['adet'];
                $saat = []; foreach ($isat as $v) foreach ($v as $s => $_) $saat[$s] = 1; ksort($saat); $saat = array_keys($saat);
                return [$pano([_srKpi('İstasyon', count($i), 'sayi', 'F97316'), _srKpi('En yoğun', $i[0][0] ?? '—', 'metin', 'EA580C'), _srKpi('En düşük uyum', ($m = collect($i)->filter(fn ($x) => $x[4] !== null)->sortBy(4)->first()) ? $m[0] . ' (%' . number_format($m[4], 1, ',', '.') . ')' : '—', 'metin', 'B91C1C')],
                        [_srBlok('İstasyonlara göre porsiyon', 'bar', 'F97316', [['İstasyon', 'm'], ['Porsiyon', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[1], $x[4]], $i)),
                         _srBlok('İstasyonlara göre ortalama süre (dk)', 'bar', 'DC2626', [['İstasyon', 'm'], ['Ort. dk', 's'], ['Ölçülen', 's']], array_map(fn ($x) => [$x[0], $x[3] ?? 0, $x[2]], array_filter($i, fn ($x) => $x[2] > 0)))], !$i ? _mrBos('satis', $dn) : null),
                    _mrIstasyonSayfa($i, $bosKds),
                    ['_oto' => []] + _srSayfa('İstasyon × Saat (porsiyon)', 'Her hücre o istasyona o saatte giden porsiyon. Yükün hangi saatte hangi istasyonda toplandığını gösterir (personel yerleşimi için).',
                        array_merge([['İstasyon', 'm', 14]], array_map(fn ($s) => [sprintf('%02d', $s), 's', 6, 'mat'], $saat)), array_map(fn ($ist, $v) => array_merge([$ist], array_map(fn ($s) => isset($v[$s]) ? round($v[$s], 1) : null, $saat)), array_keys($isat), $isat), _mrBos('satis', $dn))];
            case 'pass':
                $k = array_values(array_filter(_mrSatis($K()), fn ($x) => $x['pass'] !== null)); $bos = !$d['servis'] ? _mrBos('servis_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') servis zamanı yazılmış kalem yok.';
                $u = collect($k)->groupBy('ad')->map(fn ($g, $a) => [$a, $g->count(), _mrOrt($g->pluck('pass')->all()), $g->max('pass'), $g->filter(fn ($x) => $x['pass'] > 5)->count()])->sortByDesc(2)->values()->all();
                $kn = collect($k)->groupBy(fn ($x) => $x['kanal'] ?: 'masa')->map(fn ($g, $a) => [ucfirst($a), $g->count(), _mrOrt($g->pluck('pass')->all())])->values()->all();
                $uzun = collect($k)->filter(fn ($x) => $x['pass'] > 5)->sortByDesc('pass')->take(200)->map(fn ($x) => [$x['tarih'], substr($x['zaman'], 11, 5), $x['ad'], $x['pass'], ucfirst($x['kanal'] ?: 'masa')])->values()->all();
                return [$pano([_srKpi('Ölçülen tabak', count($k), 'sayi', 'B45309'), $kpiD('Ort. pass süresi', _mrOrt(array_column($k, 'pass')), '92400E'), $kpiD('%90', _mrYuzdelik(array_column($k, 'pass'), 90), '78350F'),
                        _srKpi('5 dk+ bekleyen tabak', count(array_filter($k, fn ($x) => $x['pass'] > 5)), 'sayi', 'B91C1C')],
                        [_srBlok('Saatlere göre ort. pass (dk)', 'col', 'B45309', [['Saat', 'm'], ['Ort. dk', 's'], ['Tabak', 's']], collect($k)->groupBy('saat')->sortKeys()->map(fn ($g, $s) => [sprintf('%02d:00', $s), _mrOrt($g->pluck('pass')->all()), $g->count()])->values()->all())], !$k ? $bos : null),
                    _srSayfa('Ürüne Göre Pass', 'Pass = KDS\'de "Hazır" → servis edildi (dk). Uzun pass = tabak tezgâhta soğuyor; garson akışını ya da expo düzenini gösterir.', [['Ürün', 'm', 26], ['Tabak', 's', 8, 'top'], ['Ort. Pass (dk)', 's', 11, 'bar'], ['En Uzun', 's', 9], ['5 dk+', 's', 8, 'top']], $u, $bos),
                    _srSayfa('Kanala Göre', 'Masa, paket, gel-al vb.', [['Kanal', 'm', 16], ['Tabak', 's', 8, 'top'], ['Ort. Pass (dk)', 's', 11, 'bar']], $kn, $bos),
                    _srSayfa('5 Dakikadan Uzun Bekleyenler', 'Hazır olduktan sonra 5 dakikadan fazla tezgâhta bekleyen tabaklar.', [['Tarih', 't', 11], ['Saat', 'm', 7], ['Ürün', 'm', 26], ['Pass (dk)', 's', 9, 'bar'], ['Kanal', 'm', 10]], $uzun, $bos)];
            case 'tukendi':
                $t = _mrTukendi($sid, $dn); $simdi = _srTablo('urunler') ? DB::table('urunler')->where('sube_id', $sid)->where('tukendi', 1)->pluck('ad')->all() : [];
                $bos = !$d['tukendi'] ? _mrBos('tukendi_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') tükendi işaretlenen ürün yok.';
                return [$pano([_srKpi('Tükenen ürün', count($t), 'sayi', '9F1239'), _srKpi('Toplam tükenme', array_sum(array_column($t, 1)), 'sayi', '881337'), _srKpi('Toplam tükenik süre', round(array_sum(array_column($t, 2)), 1) . ' saat', 'metin', '831843'), _srKpi('Şu an tükenmiş', count($simdi), 'sayi', 'BE123C')],
                        [_srBlok('En uzun süre tükenik kalanlar (saat)', 'bar', '9F1239', [['Ürün', 'm'], ['Saat', 's'], ['Kez', 's']], array_map(fn ($x) => [$x[0], $x[2], $x[1]], array_slice($t, 0, 12)))], !$t ? $bos : null),
                    _srSayfa('Tükendi Geçmişi', 'Süre = tükendi işaretlendiği andan tekrar satışa açılana kadar (dönem içindeki kısım). Sık tükenen ürün = hazırlık / sipariş miktarı az.', [['Ürün', 'm', 26], ['Tükenme (kez)', 's', 11, 'top'], ['Tükenik Süre (saat)', 's', 13, 'top bar'], ['İlk', 'm', 16], ['Son', 'm', 16], ['Şu An', 'm', 10]], $t, $bos),
                    _srSayfa('Şu An Tükenmiş', 'Rapor anında tükendi işaretli ürünler.', [['Ürün', 'm', 30]], array_map(fn ($a) => [$a], $simdi), 'Şu an tükenmiş ürün yok.')];

            // ---------------- FOOD COST & TÜKETİM ----------------
            case 'foodcost':
                $k = $K(); $fc = _mrFoodCost($k); $fco = _mrFoodCost($KO()); $bos = !$d['recete'] ? _mrBos('recete_yok', $dn) : _mrBos('satis', $dn);
                return [$pano([$kpiY('Teorik food cost', $fc['fc'], '059669'), $kpiY('Önceki dönem', $fco['fc'], '047857'), _srKpi('Reçeteli satış', $fc['satis'], 'tl', '065F46'), _srKpi('Reçete maliyeti', $fc['maliyet'], 'tl', '064E3B'),
                        _srKpi('Reçetesiz ürün satışı', $fc['yok_tutar'], 'tl', 'B45309'), $kpiY('Reçetesiz satış payı', ($fc['satis'] + $fc['yok_tutar']) > 0 ? round($fc['yok_tutar'] / ($fc['satis'] + $fc['yok_tutar']) * 100, 1) : null, '92400E'), _srKpi('%35 üstü food cost ürün', count(array_filter($fc['var'], fn ($x) => $x['fc'] > 35)), 'sayi', 'B91C1C')],
                        [_srBlok('Food cost oranı en yüksek ürünler', 'bar', '059669', [['Ürün', 'm'], ['Food cost %', 'y'], ['Satış', 'tl']], array_map(fn ($x) => [$x['ad'], $x['fc'], $x['tutar']], array_slice($fc['var'], 0, 12))),
                         _srBlok('En çok katkı getiren ürünler', 'bar', '047857', [['Ürün', 'm'], ['Toplam katkı', 'tl'], ['Food cost %', 'y']], array_map(fn ($x) => [$x['ad'], $x['tcm'], $x['fc']], array_slice(collect($fc['var'])->sortByDesc('tcm')->values()->all(), 0, 12)))], !$fc['var'] ? $bos : null),
                    _srSayfa('Ürün Food Cost', 'Food cost = reçete maliyeti × adet ÷ satış tutarı (ikramlar hariç). Birim maliyet reçetedeki malzemelerin GÜNCEL maliyetidir (alt reçeteler dahil). Katkı payı = ortalama fiyat − birim maliyet.',
                        [['Ürün', 'm', 24], ['Adet', 's', 8, 'top'], ['Satış', 'tl', 12, 'top'], ['Ort. Fiyat', 'tl', 11], ['Birim Maliyet', 'tl', 11], ['Toplam Maliyet', 'tl', 12, 'top'], ['Food Cost %', 'y', 10, 'esik:35'], ['Katkı Payı', 'tl', 11], ['Toplam Katkı', 'tl', 12, 'top bar']],
                        array_map(fn ($x) => [$x['ad'], $x['adet'], $x['tutar'], $x['fiyat'], $x['bm'], $x['tm'], $x['fc'], $x['cm'], $x['tcm']], $fc['var']), $bos),
                    _srSayfa('Reçetesiz Satılan Ürünler', 'Reçetesi olmayan ürünlerin maliyeti bilinmez; food cost ve stok düşümü bu ürünlerde çalışmaz. Reçeteler ekranından reçete girin.', [['Ürün', 'm', 30], ['Adet', 's', 9, 'top'], ['Satış', 'tl', 13, 'top bar']], $fc['yok'], 'Seçili dönemde reçetesiz satılan ürün yok.')];
            case 'avt':
                $a = _mrAvt($sid, $dn); $bos = !$d['tuketim'] ? _mrBos('tuketim_yok', $dn) : _mrBos('tuketim', $dn);
                return [$pano([_srKpi('Teorik tüketim', $a['teo'], 'tl', '047857'), _srKpi('Gerçek tüketim', $a['ger'], 'tl', '065F46'), _srKpi('Fark (gerçek − teorik)', round($a['ger'] - $a['teo'], 2), 'tl', 'B91C1C'),
                        $kpiY('Fark oranı', $a['teo'] > 0 ? round(($a['ger'] - $a['teo']) / $a['teo'] * 100, 1) : null, '991B1B'), _srKpi('Bunun fire kısmı', $a['fire'], 'tl', 'C2410C'), _srKpi('Bunun sayım kısmı', $a['sayim'], 'tl', '7C2D12'), _srKpi('Dönemde sayım', $a['sayim_var'] ? 'Yapıldı' : 'Yapılmadı', 'metin', '1E293B')],
                        [_srBlok('Farkı en büyük malzemeler (₺)', 'bar', 'B91C1C', [['Malzeme', 'm'], ['Fark ₺', 'tl'], ['Fark %', 'y']], array_map(fn ($x) => [$x[0], $x[11], $x[12]], array_slice(array_filter($a['sat'], fn ($x) => $x[11] > 0), 0, 12))),
                         _srBlok('Teorik tüketim en yüksek malzemeler (₺)', 'bar', '047857', [['Malzeme', 'm'], ['Teorik ₺', 'tl'], ['Gerçek ₺', 'tl']], array_map(fn ($x) => [$x[0], $x[4], $x[10]], array_slice(collect($a['sat'])->sortByDesc(4)->values()->all(), 0, 12)))], !$a['sat'] ? $bos : null),
                    _mrAvtSayfa($a, $dn, $d)];
            case 'zam':
                $menu = function_exists('_srMenuEtki') ? _srMenuEtki($sid, $dn) : []; $sd = function_exists('_srVeriDurumu') ? _srVeriDurumu($sid) : ['recete' => $d['recete']];
                return [$pano([_srKpi('Maliyeti değişen ürün', count($menu), 'sayi', '0F766E'), _srKpi('Maliyeti artan', count(array_filter($menu, fn ($x) => $x[4] > 0)), 'sayi', 'B91C1C'), _srKpi('Toplam porsiyon maliyet artışı', array_sum(array_map(fn ($x) => max(0, $x[4]), $menu)), 'tl', '991B1B')],
                        [_srBlok('Maliyeti en çok artan ürünler', 'bar', '0F766E', [['Ürün', 'm'], ['Maliyet artışı', 'tl'], ['Food cost (sonra) %', 'y']], array_map(fn ($x) => [$x[0], $x[4], $x[7]], array_slice(array_filter($menu, fn ($x) => $x[4] > 0), 0, 12)))], !$menu ? (!$d['fatura'] ? 'Alış faturası yok: zam etkisi alış fiyatı değişimlerinden hesaplanır.' : 'Seçili dönemde reçetelerdeki malzemelerin alış fiyatı değişmemiş.') : null),
                    _srMenuSayfa($menu, $dn, $sd)];

            // ---------------- MENÜ ----------------
            case 'menu_muh':
                $mm = _mrMenuMuh($K()); $s = $mm['sinif'];
                return [$pano([_srKpi('⭐ En İyi Ürünler', $s['Yıldız'] ?? 0, 'sayi', 'A16207'), _srKpi('💰 Zam Adayları', $s['İş Atı'] ?? 0, 'sayi', '2563EB'), _srKpi('📣 Öne Çıkarılacaklar', $s['Bilmece'] ?? 0, 'sayi', '7C3AED'), _srKpi('❌ Çıkarma Adayları', $s['Zayıf'] ?? 0, 'sayi', 'B91C1C'),
                        _srKpi('"Çok satıyor" sınırı (satış payı)', $mm['esik_pop'] ?? '—', $mm['esik_pop'] === null ? 'metin' : 'y', '475569'), _srKpi('"Çok kazandırıyor" sınırı (tabak başı kazanç)', $mm['esik_cm'] ?? 'Veri yok', $mm['esik_cm'] === null ? 'metin' : 'tl', '475569')],
                        [_srBlok('Gruplara göre ürün sayısı', 'pie', '7C3AED', [['Grup', 'm'], ['Ürün', 's'], ['', 'm']], array_map(fn ($a, $v) => [_mrSinifAd()[$a], $v, ''], array_keys($s), array_values($s))),
                         _srBlok('Toplam katkıya göre ürünler', 'bar', 'CA8A04', [['Ürün', 'm'], ['Toplam katkı', 'tl'], ['Grup', 'm']], array_map(fn ($x) => [$x[0], $x[7], $x[1]], array_slice(collect($mm['sat'])->sortByDesc(7)->values()->all(), 0, 12)))], !$mm['sat'] ? (!$d['recete'] ? _mrBos('recete_yok', $dn) : _mrBos('satis', $dn)) : null),
                    _mrMenuSayfa($mm, $dn, $d),
                    ['_oto' => []] + _srSayfa('Gruplar Ne Demek?', 'Her ürün iki soruya göre gruplanır: çok satıyor mu (satış payı) ve tabak başına çok kazandırıyor mu (katkı payı). Sınırlar senin menünün ortalamasına göre hesaplanır.', [['Grup', 'm', 30], ['Satış', 'm', 9], ['Tabak Başı Kazanç', 'm', 14], ['Ne yapılır', 'm', 60]], [['⭐ En İyi Ürünler', 'Çok', 'Yüksek', 'Menünün en iyi ürünü. Kaliteyi ve porsiyonu koru, en görünür yerde tut.'], ['💰 Zam Adayları', 'Çok', 'Düşük', 'Sevilen ama az kazandıran. Küçük fiyat artışı, porsiyon/malzeme maliyetini düşür, yanına kârlı ürün eşleştir.'],
                        ['📣 Öne Çıkarılacaklar', 'Az', 'Yüksek', 'Kazandıran ama az satan. Menüde öne çıkar, garsona önerdir, sunumunu/adını güçlendir.'], ['❌ Çıkarma Adayları', 'Az', 'Düşük', 'Menüden çıkarmayı ya da yeniden tasarlamayı düşün (müşteri için zorunlu bir ürün değilse).']], null),
                    _srSayfa('Reçetesiz (Sınıflanamayan) Ürünler', 'Maliyeti bilinmediği için matrise girmeyen ürünler.', [['Ürün', 'm', 30], ['Adet', 's', 9, 'top'], ['Satış', 'tl', 13, 'top bar']], $mm['yok'] ?? [], 'Reçetesiz satılan ürün yok.')];
            case 'satis':
                $k = _mrSatis($K()); $ko = _mrSatis($KO()); $g = collect($k)->groupBy('ad'); $go = collect($ko)->groupBy('ad'); $top = max(0.01, array_sum(array_column($k, 'tutar')));
                $o = $g->map(fn ($l, $a) => [$a, round($l->sum('adet'), 1), round($l->sum('tutar'), 2), round($l->sum('tutar') / $top * 100, 1), round(($go[$a] ?? collect())->sum('adet'), 1), _srYuzde($l->sum('adet'), ($go[$a] ?? collect())->sum('adet')), $l->where('ikram', true)->sum('adet') ?: null])->sortByDesc(2)->values()->all();
                $dus = $go->keys()->diff($g->keys())->map(fn ($a) => [$a, round($go[$a]->sum('adet'), 1)])->values()->all();
                return [$pano([_srKpi('Porsiyon', _mrPorsiyon($k), 'sayi', '6D28D9'), _srKpi('Satış tutarı', array_sum(array_column($k, 'tutar')), 'tl', '5B21B6'), _srKpi('Önceki döneme göre (porsiyon)', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) ?? '—', $yuzde(_mrPorsiyon($k), _mrPorsiyon($ko)) === null ? 'metin' : 'y', '4C1D95'), _srKpi('Satılan farklı ürün', $g->count(), 'sayi', '4C1D95')],
                        [$blokCok($k, '6D28D9'), $blokSaat($k, '7C3AED')], !$k ? _mrBos('satis', $dn) : null),
                    _srSayfa('Ürün Satışları', 'İptaller hariç. Değişim = porsiyonun önceki döneme göre değişimi.', [['Ürün', 'm', 26], ['Porsiyon', 's', 10, 'top'], ['Satış', 'tl', 13, 'top bar'], ['Pay %', 'y', 8], ['Önceki Dönem', 's', 11, 'top'], ['Değişim %', 'y', 10], ['İkram (adet)', 's', 10, 'top']], $o, _mrBos('satis', $dn)),
                    _srSayfa('Bu Dönem Hiç Satmayanlar', 'Önceki dönemde satıp bu dönem hiç satmayan ürünler.', [['Ürün', 'm', 30], ['Önceki Dönem Porsiyon', 's', 16, 'bar']], $dus, 'Önceki dönemde satıp bu dönem satmayan ürün yok.'),
                    _mrSaatlikSayfa(_mrSaatlik($k), _mrBos('satis', $dn))];

            // ---------------- FİRE ----------------
            case 'fire':
                $f = _mrFireler($sid, $dn['bas'], $dn['bit']); $fo = _mrFireler($sid, $dn['obas'], $dn['obit']); $t = array_sum(array_column($f, 'maliyet')); $to = array_sum(array_column($fo, 'maliyet'));
                $alis = function_exists('_srAlis') ? _srToplam(_srAlis($sid, $dn['bas'], $dn['bit'])) : 0; $kv = _mrKuver($sid, $dn['bas'], $dn['bit']);
                $not = _mrDefter($sid, $dn['bas'], $dn['bit'], 'fire')->map(fn ($x) => [(string) ($x->tarih ?: substr((string) $x->created_at, 0, 10)), (string) $x->baslik, (string) $x->deger, (string) ($x->birim ?? ''), (string) ($x->kategori ?? ''), (string) ($x->personel_ad ?: '—'), (string) ($x->not ?? '')])->all();
                return [$pano([_srKpi('Fire maliyeti', $t, 'tl', 'C2410C'), _srKpi('Önceki döneme göre', $yuzde($t, $to) ?? '—', $yuzde($t, $to) === null ? 'metin' : 'y', '9A3412'), $kpiY('Alışa oranı', $alis > 0 ? round($t / $alis * 100, 1) : null, '7C2D12'),
                        _srKpi('Kuver başına', $kv > 0 ? round($t / $kv, 2) : 'Kuver yok', $kv > 0 ? 'tl' : 'metin', '431407'), _srKpi('Pişmiş ürün iptali kaynaklı', array_sum(array_column(array_filter($f, fn ($x) => $x['kaynak'] === 'Pişmiş ürün iptali'), 'maliyet')), 'tl', 'BE123C'), _srKpi('Fire kaydı', count($f), 'sayi', '78350F')],
                        [$blokDagil('Sebebe göre', 'pie', 'C2410C', 'Sebep', $dagil($f, 'sebep', 'maliyet')), $blokDagil('Malzemeye göre', 'bar', '9A3412', 'Malzeme', $dagil($f, 'malzeme', 'maliyet')),
                         $blokDagil('Kategoriye göre', 'pie', 'EA580C', 'Kategori', $dagil($f, 'kategori', 'maliyet')), _srBlok('Günlere göre fire', 'col', 'C2410C', [['Gün', 'm'], ['Fire', 'tl'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[6], ''], _mrGunluk([], $dn, $f)))], !$f ? (!$d['fire'] ? _mrBos('fire_yok', $dn) : _mrBos('fire', $dn)) : null),
                    _srSayfa('Sebebe Göre', 'Fire girilirken seçilen sebep (eski kayıtlarda açıklama).', [['Sebep', 'm', 30], ['Maliyet', 'tl', 13, 'top bar'], ['Kayıt', 's', 8, 'top']], array_map(fn ($a, $v) => [$a, round($v, 2), count(array_filter($f, fn ($x) => $x['sebep'] === $a))], array_keys($m = $dagil($f, 'sebep', 'maliyet')), $m), !$d['fire'] ? _mrBos('fire_yok', $dn) : _mrBos('fire', $dn)),
                    _srSayfa('Malzemeye Göre', 'Malzeme bazında fire.', [['Malzeme', 'm', 26], ['Kategori', 'm', 14], ['Miktar', 's', 10], ['Birim', 'm', 6], ['Maliyet', 'tl', 13, 'top bar']], collect($f)->groupBy('malzeme')->map(fn ($g, $a) => [$a, $g->first()['kategori'], round($g->sum('miktar'), 3), $g->first()['birim'], round($g->sum('maliyet'), 2)])->sortByDesc(4)->values()->all(), !$d['fire'] ? _mrBos('fire_yok', $dn) : _mrBos('fire', $dn)),
                    _mrFireSayfa($f, $dn, $d),
                    _srSayfa('Kontrol Defteri Fire Notları', 'Kontrol Defteri\'ne "Fire / İsraf" olarak yazılan notlar (stoktan düşülmez, maliyeti yoktur; stoktan düşmek için Stok > Fire kullanın).', [['Tarih', 't', 11], ['Başlık', 'm', 24], ['Miktar', 'm', 9], ['Birim', 'm', 7], ['Kategori', 'm', 12], ['Giren', 'm', 14], ['Not', 'm', 26]], $not, 'Seçili dönemde Kontrol Defteri\'ne fire notu yazılmamış.')];
            case 'skt_kayip':
                $p = _srSktPartiler($sid, 7); $gec = array_values(array_filter($p, fn ($x) => $x['kalan_gun'] < 0)); $f = array_values(array_filter(_mrFireler($sid, $dn['bas'], $dn['bit']), fn ($x) => preg_match('/skt|bozul|son kullanma|tarihi geç/ui', $x['sebep'])));
                $bos = !$d['parti'] ? _mrBos('parti_yok', $dn) : _mrBos('skt', $dn);
                return [$pano([_srKpi('SKT\'si geçmiş parti', count($gec), 'sayi', 'B91C1C'), _srKpi('Geçmiş partilerin değeri', array_sum(array_column($gec, 'deger')), 'tl', '991B1B'), _srKpi('7 gün içinde dolacak', count($p) - count($gec), 'sayi', 'DC2626'), _srKpi('Dönemde SKT/bozulma firesi', array_sum(array_column($f, 'maliyet')), 'tl', '7F1D1D')],
                        [_srBlok('Değere göre riskli partiler', 'bar', 'B91C1C', [['Malzeme · SKT', 'm'], ['Değer', 'tl'], ['Kalan gün', 's']], array_map(fn ($x) => [$x['malzeme'] . ' · ' . \Carbon\Carbon::parse($x['skt'])->format('d.m'), $x['deger'], $x['kalan_gun']], array_slice(collect($p)->sortByDesc('deger')->values()->all(), 0, 12)))], !$p && !$f ? $bos : null),
                    _srSayfa('Riskli Partiler', 'Stokta kalan ve SKT\'si 7 gün içinde dolan ya da geçmiş partiler. Değer = kalan miktar × parti birim maliyeti. Kalan gün < 0 = SKT geçmiş.', [['Malzeme', 'm', 22], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 7], ['Değer', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 20]],
                        array_map(fn ($x) => [$x['malzeme'], $x['lot'], $x['skt'], $x['kalan_gun'], $x['kalan'], $x['birim'], $x['deger'], $x['ted']], $p), $bos),
                    _mrFireSayfa($f, $dn, ['fire' => $d['fire']])];

            // ---------------- ÜRETİM ----------------
            case 'uretim':
                $u = _mrUretimler($sid, $dn); $bos = !$d['uretim'] ? _mrBos('uretim_yok', $dn) : _mrBos('uretim', $dn);
                $by = []; foreach ($u as $x) { $by[$x[2]] = ($by[$x[2]] ?? 0) + $x[5]; } arsort($by);
                $ipU = _mrIptalUretim($sid); $hm = _mrHareket($sid, $dn['bas'], $dn['bit'], ['tuketim'], ['uretim_hammadde'])->reject(fn ($h) => isset($ipU[$h->kaynak_id]))->values();
                return [$pano([_srKpi('Üretim kaydı', count($u), 'sayi', '0891B2'), _srKpi('Üretilen değer', array_sum(array_column($u, 5)), 'tl', '0E7490'), _srKpi('Farklı yarı mamül', count($by), 'sayi', '155E75'), _srKpi('Harcanan hammadde', -$hm->sum(fn ($h) => (float) $h->miktar * (float) $h->birim_maliyet), 'tl', '164E63')],
                        [$blokDagil('Yarı mamüle göre üretim değeri', 'bar', '0891B2', 'Yarı mamül', $by)], !$u ? $bos : null),
                    _srSayfa('Üretimler', 'Reçeteler / Yarı Mamül ekranındaki "Üret" kayıtları. Kalan = partide şu an kalan miktar.', [['Tarih', 't', 11], ['Saat', 'm', 6], ['Yarı Mamül', 'm', 24], ['Miktar', 's', 10], ['Birim', 'm', 6], ['Değer', 'tl', 12, 'top bar'], ['Lot', 'm', 14], ['SKT', 't', 11], ['Partide Kalan', 's', 11], ['Üreten', 'm', 16]], $u, $bos),
                    _srSayfa('Harcanan Hammadde', 'Üretimlerde stoktan düşen hammaddeler.', [['Malzeme', 'm', 26], ['Miktar', 's', 11], ['Birim', 'm', 6], ['Maliyet', 'tl', 13, 'top bar']],
                        $hm->groupBy('malzeme_id')->map(fn ($g, $m) => [_srMalz($m), round(-$g->sum('miktar'), 3), _mrBirim($m), round(-$g->sum(fn ($h) => (float) $h->miktar * (float) $h->birim_maliyet), 2)])->sortByDesc(3)->values()->all(), $bos)];
            case 'uretim_kullanim':
                $o = _mrUretimKullanim($sid, $dn); $bos = !$d['uretim'] ? _mrBos('uretim_yok', $dn) : _mrBos('uretim', $dn);
                return [$pano([_srKpi('Yarı mamül', count($o), 'sayi', '0E7490'), _srKpi('Kullanım oranı %70 altı', count(array_filter($o, fn ($x) => $x[5] !== null && $x[5] < 70)), 'sayi', 'B45309'), _srKpi('Fire oranı %10 üstü', count(array_filter($o, fn ($x) => $x[6] !== null && $x[6] > 10)), 'sayi', 'B91C1C')],
                        [_srBlok('Kullanım oranı (kullanılan ÷ üretilen %)', 'bar', '0E7490', [['Yarı mamül', 'm'], ['Kullanım %', 'y'], ['Fire %', 'y']], array_map(fn ($x) => [$x[0], $x[5] ?? 0, $x[6]], array_slice($o, 0, 12)))], !$o ? $bos : null),
                    _srSayfa('Üretilen ve Kullanılan', 'Üretilen = dönemde üretim kaydı. Kullanılan = satışta reçeteyle düşen. Kullanım % düşükse fazla hazırlanıyor (over-prep); fire % yüksekse hazırlanan bozuluyor/atılıyor. Önceki dönemden kalan stok kullanıldıysa %100\'ü geçebilir.',
                        [['Yarı Mamül', 'm', 24], ['Birim', 'm', 6], ['Üretilen', 's', 10, 'top'], ['Kullanılan', 's', 10, 'top'], ['Fire', 's', 9, 'top'], ['Kullanım %', 'y', 10, 'bar'], ['Fire %', 'y', 9, 'esik:10'], ['Şu An Stok', 's', 10]], $o, $bos)];

            // ---------------- STOK ----------------
            case 'tuketim':
                $ipU = _mrIptalUretim($sid); $tkf = fn ($b, $e) => _mrHareket($sid, $b, $e, ['tuketim'], ['adisyon', 'uretim_hammadde'])->reject(fn ($h) => $h->kaynak_tip === 'uretim_hammadde' && isset($ipU[$h->kaynak_id]))->groupBy('malzeme_id');
                $t = $tkf($dn['bas'], $dn['bit']); $to = $tkf($dn['obas'], $dn['obit']);
                $tl = fn ($g) => -$g->sum(fn ($h) => (float) $h->miktar * (float) $h->birim_maliyet);
                $o = $t->map(fn ($g, $m) => [_srMalz($m), _srKat($m), _mrBirim($m), round(-$g->sum('miktar'), 3), round($tl($g), 2), round(isset($to[$m]) ? $tl($to[$m]) : 0, 2), _srYuzde($tl($g), isset($to[$m]) ? $tl($to[$m]) : 0)])->sortByDesc(4)->values()->all();
                $bos = !$d['tuketim'] ? _mrBos('tuketim_yok', $dn) : _mrBos('tuketim', $dn); $kat = []; foreach ($o as $x) $kat[$x[1]] = ($kat[$x[1]] ?? 0) + $x[4]; arsort($kat);
                return [$pano([_srKpi('Tüketim maliyeti', array_sum(array_column($o, 4)), 'tl', '2563EB'), _srKpi('Önceki dönem', array_sum(array_column($o, 5)), 'tl', '1E40AF'), _srKpi('Değişim', _srYuzde(array_sum(array_column($o, 4)), array_sum(array_column($o, 5))) ?? '—', 'y', '1D4ED8'), _srKpi('Tüketilen malzeme', count($o), 'sayi', '1E3A8A')],
                        [_srBlok('En çok tüketilen malzemeler (₺)', 'bar', '2563EB', [['Malzeme', 'm'], ['Maliyet', 'tl'], ['Değişim %', 'y']], array_map(fn ($x) => [$x[0], $x[4], $x[6]], array_slice($o, 0, 12))), $blokDagil('Kategoriye göre', 'pie', '1D4ED8', 'Kategori', $kat)], !$o ? $bos : null),
                    _srSayfa('Tüketilen Malzemeler', 'Satış (reçete) ve yarı mamül üretimiyle stoktan düşen malzemeler. Maliyet = miktar × kayıttaki birim maliyet.', [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Birim', 'm', 6], ['Miktar', 's', 11], ['Maliyet', 'tl', 13, 'top bar'], ['Önceki Dönem', 'tl', 13, 'top'], ['Değişim %', 'y', 10]], $o, $bos)];
            case 'sayim':
                $fr = _srFireler($sid, $dn); $s = $fr['sayim']; $kay = array_sum(array_map(fn ($x) => min(0, $x['maliyet']), $s)); $faz = array_sum(array_map(fn ($x) => max(0, $x['maliyet']), $s));
                $sd = ['sayim' => $d['sayim'], 'fire' => $d['fire']];
                return [$pano([_srKpi('Sayım farkı (net)', array_sum(array_column($s, 'maliyet')), 'tl', '1D4ED8'), _srKpi('Kayıp (eksik çıkan)', $kay, 'tl', 'B91C1C'), _srKpi('Fazla çıkan', $faz, 'tl', '059669'), _srKpi('Farklı çıkan kalem', count($s), 'sayi', '1E3A8A')],
                        [_srBlok('En büyük kayıplar', 'bar', 'B91C1C', [['Malzeme', 'm'], ['Fark ₺', 'tl'], ['Fark', 's']], array_map(fn ($x) => [$x['malzeme'], $x['maliyet'], $x['fark']], array_slice(collect($s)->sortBy('maliyet')->filter(fn ($x) => $x['maliyet'] < 0)->values()->all(), 0, 12)))], !$s ? (!$d['sayim'] ? _mrBos('sayim_yok', $dn) : _mrBos('sayim', $dn)) : null),
                    _srSayimSayfa($fr, $dn, $sd)];
            case 'kritik':
                $kr = _srKritikStok($sid); $p = _srSktPartiler($sid, 7);
                return [$pano([_srKpi('Kritik stok altında', count($kr), 'sayi', 'D97706'), _srKpi('SKT 7 gün içinde / geçmiş', count($p), 'sayi', 'B91C1C'), _srKpi('Riskli parti değeri', array_sum(array_column($p, 'deger')), 'tl', '991B1B')], [], !$kr && !$p ? 'Kritik stok altında malzeme ve SKT riski olan parti yok.' : null),
                    _srSayfa('Kritik Stok', 'Mevcut stok (stok hareketleri toplamı) kritik seviyenin altında olan malzemeler; en kritikten.', [['Malzeme', 'm', 24], ['Kategori', 'm', 14], ['Birim', 'm', 7], ['Mevcut', 's', 10], ['Kritik Seviye', 's', 11], ['Eksik', 's', 10, 'bar']], $kr, _mrBos('kritik', $dn)),
                    _srSayfa('SKT Yaklaşan Partiler (7 gün)', 'Stokta kalan ve SKT\'si 7 gün içinde dolan ya da geçmiş partiler.', [['Malzeme', 'm', 22], ['Lot', 'm', 12], ['SKT', 't', 11], ['Kalan Gün', 's', 9], ['Kalan Miktar', 's', 11], ['Birim', 'm', 7], ['Değer', 'tl', 12, 'top bar'], ['Tedarikçi', 'm', 20]],
                        array_map(fn ($x) => [$x['malzeme'], $x['lot'], $x['skt'], $x['kalan_gun'], $x['kalan'], $x['birim'], $x['deger'], $x['ted']], $p), !$d['parti'] ? _mrBos('parti_yok', $dn) : _mrBos('skt', $dn))];

            // ---------------- HİJYEN ----------------
            case 'sicaklik':
                $s = _mrSicaklik($sid, $dn); $u = array_filter($s, fn ($x) => $x['uygunsuz']);
                $eq = collect($s)->groupBy('ekipman')->map(fn ($g, $a) => [$a, $g->count(), $g->where('uygunsuz', true)->count(), round($g->where('uygunsuz', true)->count() / max(1, $g->count()) * 100, 1), $g->max('tarih')])->sortByDesc(3)->values()->all();
                $gunler = []; for ($t = \Carbon\Carbon::parse($dn['bas']); $t->lte(\Carbon\Carbon::parse($dn['bit'])); $t->addDay()) $gunler[$t->toDateString()] = 0;
                foreach ($s as $x) if (isset($gunler[$x['tarih']])) $gunler[$x['tarih']]++;
                $bosGun = array_keys(array_filter($gunler, fn ($v) => $v === 0));
                return [$pano([_srKpi('Ölçüm', count($s), 'sayi', '0284C7'), _srKpi('Uygunsuz', count($u), 'sayi', 'B91C1C'), $kpiY('Uygunsuzluk oranı', $s ? round(count($u) / count($s) * 100, 1) : null, '991B1B'), _srKpi('Kayıt girilmeyen gün', count($bosGun) . ' / ' . count($gunler), 'metin', 'B45309'), _srKpi('Ekipman', count($eq), 'sayi', '075985')],
                        [_srBlok('Ekipmana göre uygunsuzluk %', 'bar', '0284C7', [['Ekipman', 'm'], ['Uygunsuz %', 'y'], ['Ölçüm', 's']], array_map(fn ($x) => [$x[0], $x[3], $x[1]], array_slice($eq, 0, 12))),
                         _srBlok('Günlere göre ölçüm sayısı', 'col', '0EA5E9', [['Gün', 'm'], ['Ölçüm', 's'], ['', 'm']], array_map(fn ($t, $v) => [date('d.m', strtotime($t)), $v, ''], array_keys($gunler), $gunler))], !$s ? (!$d['defter'] ? _mrBos('defter_yok', $dn) : _mrBos('sicaklik', $dn)) : null),
                    _mrSicaklikSayfa($s, $dn, $d, true),
                    _srSayfa('Ekipman Özeti', 'Uygunsuzluk oranı yüksek ekipman = arıza / ayar sorunu ya da kapı açık bırakılıyor olabilir.', [['Ekipman', 'm', 26], ['Ölçüm', 's', 9, 'top'], ['Uygunsuz', 's', 9, 'top'], ['Uygunsuz %', 'y', 10, 'bar'], ['Son Ölçüm', 't', 11]], $eq, !$d['defter'] ? _mrBos('defter_yok', $dn) : _mrBos('sicaklik', $dn)),
                    _srSayfa('Kayıt Girilmeyen Günler', 'Hiç sıcaklık ölçümü girilmeyen günler. HACCP denetiminde her gün kayıt beklenir.', [['Tarih', 't', 12]], array_map(fn ($t) => [$t], $bosGun), 'Dönemin her gününde en az bir sıcaklık ölçümü var.'),
                    _mrSicaklikSayfa($s, $dn, $d)];
            case 'kontrol':
                $c = _mrDefter($sid, $dn['bas'], $dn['bit'], ['checklist', 'prep']);
                $gunler = []; for ($t = \Carbon\Carbon::parse($dn['bas']); $t->lte(\Carbon\Carbon::parse($dn['bit'])); $t->addDay()) $gunler[$t->toDateString()] = ['c' => 0, 'p' => 0, 'y' => 0];
                foreach ($c as $x) { $t = (string) ($x->tarih ?: substr((string) $x->created_at, 0, 10)); if (!isset($gunler[$t])) continue; $gunler[$t][$x->tip === 'prep' ? 'p' : 'c']++; if ($x->sonuc === 'yapildi' || $x->sonuc === 'uygun') $gunler[$t]['y']++; }
                $gAd = ['', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
                $gs = array_map(fn ($t, $v) => [$gAd[(int) date('N', strtotime($t))] . ' ' . date('d.m', strtotime($t)), $v['c'], $v['p'], $v['y'], $v['c'] + $v['p'] === 0 ? 'KAYIT YOK' : ''], array_keys($gunler), $gunler);
                $mad = $c->groupBy(fn ($x) => trim((string) $x->baslik) ?: '—')->map(fn ($g, $a) => [$a, $g->first()->tip === 'prep' ? 'Prep' : 'Kontrol', $g->count(), $g->filter(fn ($x) => in_array($x->sonuc, ['yapildi', 'uygun'], true))->count(), $g->where('sonuc', 'uygunsuz')->count()])->sortByDesc(2)->values()->all();
                $is = _srTablo('departman_isler') ? DB::table('departman_isler')->where('sube_id', $sid)->whereIn('departman_kod', _mrDepKodlari())->where(function ($q) use ($dn) { $q->whereIn('durum', ['acik', 'yapiliyor'])->orWhere(function ($q2) use ($dn) { $q2->whereDate('created_at', '>=', $dn['bas'])->whereDate('created_at', '<=', $dn['bit']); }); })->orderByDesc('created_at')->get() : collect();
                $isSat = $is->map(fn ($x) => [substr((string) $x->created_at, 0, 10), ['gorev' => 'Görev', 'kontrol' => 'Kontrol', 'olay' => 'Olay', 'ariza' => 'Arıza', 'bakim' => 'Bakım'][$x->tip] ?? $x->tip, (string) $x->baslik, ['acik' => 'Açık', 'yapiliyor' => 'Yapılıyor', 'tamam' => 'Tamam', 'iptal' => 'İptal'][$x->durum] ?? $x->durum,
                    ['dusuk' => 'Düşük', 'orta' => 'Orta', 'yuksek' => 'Yüksek'][$x->oncelik] ?? $x->oncelik, $x->son_tarih ? substr((string) $x->son_tarih, 0, 10) : null, $x->son_tarih && in_array($x->durum, ['acik', 'yapiliyor'], true) && substr((string) $x->son_tarih, 0, 10) < today()->toDateString() ? 'GECİKMİŞ' : '', (string) ($x->atanan_ad ?: '—')])->all();
                $bosG = count(array_filter($gunler, fn ($v) => $v['c'] + $v['p'] === 0));
                return [$pano([_srKpi('Kontrol kaydı', $c->where('tip', 'checklist')->count(), 'sayi', '16A34A'), _srKpi('Prep kaydı', $c->where('tip', 'prep')->count(), 'sayi', '15803D'), _srKpi('Kayıtsız gün', $bosG . ' / ' . count($gunler), 'metin', 'B45309'),
                        _srKpi('Açık mutfak görevi', $is->whereIn('durum', ['acik', 'yapiliyor'])->count(), 'sayi', 'D97706'), _srKpi('Gecikmiş görev', count(array_filter($isSat, fn ($x) => $x[6] === 'GECİKMİŞ')), 'sayi', 'B91C1C'), _srKpi('Açık arıza', $is->where('tip', 'ariza')->whereIn('durum', ['acik', 'yapiliyor'])->count(), 'sayi', '991B1B')],
                        [_srBlok('Günlere göre kontrol + prep kaydı', 'col', '16A34A', [['Gün', 'm'], ['Kayıt', 's'], ['', 'm']], array_map(fn ($x) => [$x[0], $x[1] + $x[2], ''], $gs))], $c->isEmpty() && $is->isEmpty() ? (!$d['defter'] ? _mrBos('defter_yok', $dn) : _mrBos('kontrol', $dn)) : null),
                    _srSayfa('Günlere Göre', 'Kontrol = açılış / kapanış listesi kayıtları; Prep = hazırlık / par kayıtları. "KAYIT YOK" günlerinde listeler doldurulmamış.', [['Gün', 'm', 12], ['Kontrol', 's', 9, 'top'], ['Prep', 's', 8, 'top'], ['Yapıldı / Uygun', 's', 12, 'top'], ['', 'm', 11]], $gs, !$d['defter'] ? _mrBos('defter_yok', $dn) : null),
                    _srSayfa('Madde Bazında', 'Her kontrol / prep maddesinin dönemde kaç kez kaydedildiği.', [['Madde', 'm', 30], ['Tür', 'm', 9], ['Kayıt', 's', 8, 'top bar'], ['Yapıldı / Uygun', 's', 12, 'top'], ['Uygunsuz', 's', 9, 'top']], $mad, !$d['defter'] ? _mrBos('defter_yok', $dn) : _mrBos('kontrol', $dn)),
                    _srSayfa('Mutfak Görevleri & Arızalar', 'Mutfak departmanlarının açık görevleri ve dönemde açılanlar (Departman brifingi > görevler).', [['Açılış', 't', 11], ['Tür', 'm', 8], ['Başlık', 'm', 30], ['Durum', 'm', 10], ['Öncelik', 'm', 8], ['Son Tarih', 't', 11], ['', 'm', 10], ['Atanan', 'm', 14]], $isSat, 'Açık ya da dönemde açılmış mutfak görevi yok.')];
            case 'kalite':
                $q = _srTablo('kalite_kayitlari') ? DB::table('kalite_kayitlari')->where('sube_id', $sid)->orderBy('son_tarih')->get() : collect(); $bugun = today()->toDateString(); $ay = today()->addDays(30)->toDateString();
                $tipAd = ['numune' => 'Numune', 'ilaclama' => 'İlaçlama', 'kalibrasyon' => 'Kalibrasyon', 'tedarikci_belge' => 'Tedarikçi belgesi', 'denetim' => 'Denetim'];
                $sat = $q->map(fn ($x) => [$tipAd[$x->tip] ?? $x->tip, (string) $x->baslik, (string) ($x->deger ?? ''), $x->tarih ? substr((string) $x->tarih, 0, 10) : null, $x->son_tarih ? substr((string) $x->son_tarih, 0, 10) : null,
                    $x->son_tarih ? (int) today()->diffInDays(\Carbon\Carbon::parse(substr((string) $x->son_tarih, 0, 10)), false) : null, !$x->son_tarih ? '' : (substr((string) $x->son_tarih, 0, 10) < $bugun ? 'SÜRESİ GEÇTİ' : (substr((string) $x->son_tarih, 0, 10) <= $ay ? '30 gün içinde' : 'Geçerli')), (string) ($x->personel_ad ?? '')])->all();
                $donemde = $q->filter(fn ($x) => $x->tarih && substr((string) $x->tarih, 0, 10) >= $dn['bas'] && substr((string) $x->tarih, 0, 10) <= $dn['bit'])->count();
                return [$pano([_srKpi('Kayıt', $q->count(), 'sayi', '0D9488'), _srKpi('Süresi geçen', count(array_filter($sat, fn ($x) => $x[6] === 'SÜRESİ GEÇTİ')), 'sayi', 'B91C1C'), _srKpi('30 gün içinde dolacak', count(array_filter($sat, fn ($x) => $x[6] === '30 gün içinde')), 'sayi', 'D97706'), _srKpi('Dönemde yapılan', $donemde, 'sayi', '0F766E')],
                        [_srBlok('Türlere göre kayıt', 'pie', '0D9488', [['Tür', 'm'], ['Kayıt', 's'], ['', 'm']], $q->groupBy(fn ($x) => $tipAd[$x->tip] ?? $x->tip)->map(fn ($g, $a) => [$a, $g->count(), ''])->values()->all())], $q->isEmpty() ? _mrBos('kalite_yok', $dn) : null),
                    _srSayfa('Kalite Kayıtları', 'Kalan gün = son geçerlilik tarihine kalan gün (eksi = geçmiş). Süresi geçenler denetimde eksik sayılır.', [['Tür', 'm', 14], ['Başlık', 'm', 26], ['Değer / Firma', 'm', 16], ['Yapıldı', 't', 11], ['Geçerlilik', 't', 11], ['Kalan Gün', 's', 9], ['Durum', 'm', 13], ['Giren', 'm', 14]], $sat, _mrBos('kalite_yok', $dn))];

            // ---------------- İPTAL ----------------
            case 'iptal':
                $ip = _mrIptaller($sid, $dn); $v = array_values(array_filter($ip, fn ($x) => $x['tip'] === 'İptal')); $ik = array_values(array_filter($ip, fn ($x) => $x['tip'] === 'İkram')); $mf = array_values(array_filter($v, fn ($x) => $x['mutfak']));
                $gec = array_filter($ip, fn ($x) => preg_match('/geç|gec|bekle|soğuk|soguk|yanl|beğen|begen/ui', $x['sebep']));
                return [$pano([_srKpi('İptal tutarı', array_sum(array_column($v, 'tutar')), 'tl', 'BE123C'), _srKpi('İkram tutarı', array_sum(array_column($ik, 'tutar')), 'tl', '9F1239'), _srKpi('Mutfağa gittikten sonra iptal', count($mf), 'sayi', 'B91C1C'),
                        _srKpi('Bunların tutarı', array_sum(array_column($mf, 'tutar')), 'tl', '991B1B'), _srKpi('Mutfak kaynaklı sebep (geç / beğenmedi / yanlış)', count($gec), 'sayi', '7F1D1D'), _srKpi('Kayıt', count($ip), 'sayi', '881337')],
                        [$blokDagil('Sebebe göre (tutar)', 'pie', 'BE123C', 'Sebep', $dagil($ip, 'sebep', 'tutar')), $blokDagil('Ürüne göre (tutar)', 'bar', '9F1239', 'Ürün', $dagil(array_filter($ip, fn ($x) => $x['urun'] !== '—'), 'urun', 'tutar')),
                         $blokDagil('Aşamaya göre (iptal)', 'pie', '881337', 'Aşama', $dagil($v, 'asama', 'tutar'))], !$ip ? _mrBos('iptal', $dn) : null),
                    _srSayfa('Sebebe Göre', 'İptal ve ikram sebepleri (POS\'ta seçilen).', [['Sebep', 'm', 30], ['Kayıt', 's', 8, 'top'], ['Tutar', 'tl', 13, 'top bar']], array_map(fn ($a, $t) => [$a, count(array_filter($ip, fn ($x) => $x['sebep'] === $a)), round($t, 2)], array_keys($m = $dagil($ip, 'sebep', 'tutar')), $m), _mrBos('iptal', $dn)),
                    _srSayfa('Personele Göre', 'İptal / ikramı giren personel.', [['Personel', 'm', 22], ['İptal', 's', 8, 'top'], ['İkram', 's', 8, 'top'], ['Tutar', 'tl', 13, 'top bar']], collect($ip)->groupBy('personel')->map(fn ($g, $a) => [$a, $g->where('tip', 'İptal')->count(), $g->where('tip', 'İkram')->count(), round($g->sum('tutar'), 2)])->sortByDesc(3)->values()->all(), _mrBos('iptal', $dn)),
                    _mrIptalSayfa($ip, $dn)];

            // ---------------- EKİP ----------------
            case 'ekip':
                $ms = _mrMesai($sid, $dn); $k = $K(); $saat = array_sum(array_column($ms['sat'], 4));
                $bos = $ms['ekip']->isEmpty() ? _mrBos('ekip_yok', $dn) : (!$d['mesai'] ? _mrBos('mesai_yok', $dn) : _mrBos('mesai', $dn));
                return [$pano([_srKpi('Mutfak ekibi', $ms['ekip']->count(), 'sayi', '4F46E5'), _srKpi('Toplam çalışılan saat', round($saat, 1), 'sayi', '4338CA'), _srKpi('Planlanan vardiya saati', round(array_sum(array_column($ms['sat'], 6)), 1), 'sayi', '3730A3'),
                        _srKpi('Saat başına porsiyon', $saat > 0 ? round(_mrPorsiyon($k) / $saat, 1) : 'Mesai yok', $saat > 0 ? 'sayi' : 'metin', '312E81'), _srKpi('Personel yemeği', array_sum(array_column($ms['sat'], 8)), 'tl', '1E1B4B'), _srKpi('Kapanmamış mesai', array_sum(array_column($ms['sat'], 7)), 'sayi', 'B45309')],
                        [_srBlok('Personele göre çalışılan saat', 'bar', '4F46E5', [['Personel', 'm'], ['Saat', 's'], ['Gün', 's']], array_map(fn ($x) => [$x[0], $x[4], $x[3]], array_filter($ms['sat'], fn ($x) => $x[4] > 0)))], !array_filter($ms['sat'], fn ($x) => $x[3] > 0) ? $bos : null),
                    _srSayfa('Mutfak Ekibi Mesai', 'Saat = QR ile giriş → çıkış (kapanmamış mesai saate katılmaz, "Kapanmamış" sütununda sayılır). Planlanan = vardiya planı. Saat başına porsiyon = dönem porsiyonu ÷ toplam mutfak saati.',
                        [['Personel', 'm', 20], ['Departman', 'm', 14], ['', 'm', 8], ['Gün', 's', 6, 'top'], ['Saat', 's', 8, 'top bar'], ['Ort. Günlük', 's', 9], ['Planlanan Saat', 's', 10, 'top'], ['Kapanmamış', 's', 9, 'top'], ['Personel Yemeği', 'tl', 11, 'top']], $ms['sat'], $bos)];
            case 'asci':
                $l = array_values(array_filter(_mrOlcum($K()), fn ($x) => $x['hazirlayan'])); $bos = !$d['hazirlayan'] ? _mrBos('hazirlayan_yok', $dn) : 'Seçili dönemde (' . $dn['etiket'] . ') hazırlayanı kayıtlı kalem yok.';
                $o = collect($l)->groupBy('hazirlayan')->map(function ($g, $pid) { $z = $g->where('zamaninda', true)->count();
                    return [_mrPersonelAd($pid), $g->count(), round($g->sum('adet'), 1), _mrOrt($g->pluck('haz')->all()), _mrOrt($g->pluck('top')->all()), round($z / max(1, $g->count()) * 100, 1), $g->count() - $z, $g->groupBy('ist')->sortByDesc(fn ($x) => $x->count())->keys()->map(fn ($i) => _mrIstAd($i))->first()]; })->sortByDesc(1)->values()->all();
                return [$pano([_srKpi('Aşçı', count($o), 'sayi', '4338CA'), _srKpi('Ölçülen kalem', count($l), 'sayi', '3730A3'), $kpiY('Genel uyum', _mrHiz($l)['uyum'], '059669')],
                        [_srBlok('Aşçıya göre kalem', 'bar', '4338CA', [['Aşçı', 'm'], ['Kalem', 's'], ['Uyum %', 'y']], array_map(fn ($x) => [$x[0], $x[1], $x[5]], $o)), _srBlok('Aşçıya göre ort. hazırlık (dk)', 'bar', '6366F1', [['Aşçı', 'm'], ['Ort. dk', 's'], ['Kalem', 's']], array_map(fn ($x) => [$x[0], $x[3] ?? 0, $x[1]], $o))], !$o ? $bos : null),
                    _srSayfa('Aşçı Performansı', 'Hazırlayan = KDS\'de kaleme ilk "Başla" ya da "Hazır" basan personel. Hazırlık = başla → hazır; Toplam = sipariş → hazır. Not: ürün zorluğu farklıdır; kıyas aynı istasyondaki aşçılar arasında anlamlıdır.',
                        [['Aşçı', 'm', 20], ['Kalem', 's', 8, 'top'], ['Porsiyon', 's', 9, 'top'], ['Ort. Hazırlık (dk)', 's', 12], ['Ort. Toplam (dk)', 's', 12, 'bar'], ['Hedefe Uyum %', 'y', 12], ['Geciken', 's', 9, 'top'], ['Ana İstasyon', 'm', 14]], $o, $bos)];
            case 'talep':
                $t = _mrTalepler($sid, $dn); $bos = _mrBos('talep', $dn);
                $kal = _srTablo('talep_kalemleri') && $t->isNotEmpty() ? DB::table('talep_kalemleri')->whereIn('talep_id', $t->pluck('id'))->get() : collect();
                $kar = $t->filter(fn ($x) => !empty($x->karar_at))->map(fn ($x) => (strtotime((string) $x->karar_at) - strtotime((string) $x->created_at)) / 3600)->filter(fn ($h) => $h >= 0)->values()->all();
                $durAd = ['departman_onay' => 'Sorumlu onayında', 'yeni' => 'Yeni', 'teklif' => 'Teklif toplanıyor', 'onayda' => 'Patron onayında', 'fiyat_onay' => 'Fiyat onayı', 'onaylandi' => 'Onaylandı', 'siparis' => 'Siparişte', 'tamamlandi' => 'Tamamlandı', 'reddedildi' => 'Reddedildi', 'iptal' => 'İptal'];
                $enCok = $kal->groupBy(fn ($x) => $x->malzeme_id ? _srMalz($x->malzeme_id) : ($x->ad ?: '—'))->map(fn ($g, $a) => [$a, $g->count(), round((float) $g->sum('miktar'), 2)])->sortByDesc(1)->take(20)->values()->all();
                return [$pano([_srKpi('Talep', $t->count(), 'sayi', '7C3AED'), $kpiY('Acil oranı', $t->count() ? round($t->where('aciliyet', 'acil')->count() / $t->count() * 100, 1) : null, '6D28D9'), _srKpi('Onaylanan / siparişte', $t->whereIn('durum', ['onaylandi', 'siparis', 'tamamlandi'])->count(), 'sayi', '059669'),
                        _srKpi('Reddedilen', $t->where('durum', 'reddedildi')->count(), 'sayi', 'B91C1C'), _srKpi('Ort. karar süresi', $kar ? number_format(array_sum($kar) / count($kar), 1, ',', '.') . ' saat' : 'Veri yok', 'metin', '4C1D95')],
                        [_srBlok('Departmana göre', 'bar', '7C3AED', [['Departman', 'm'], ['Talep', 's'], ['Acil', 's']], $t->groupBy('dep_ad')->map(fn ($g, $a) => [$a, $g->count(), $g->where('aciliyet', 'acil')->count()])->values()->all()),
                         _srBlok('Durum dağılımı', 'pie', '6D28D9', [['Durum', 'm'], ['Talep', 's'], ['', 'm']], $t->groupBy(fn ($x) => $durAd[$x->durum] ?? $x->durum)->map(fn ($g, $a) => [$a, $g->count(), ''])->values()->all()),
                         _srBlok('En çok istenen malzemeler', 'bar', '5B21B6', [['Malzeme', 'm'], ['Talep', 's'], ['Toplam miktar', 's']], $enCok)], $t->isEmpty() ? $bos : null),
                    _srSayfa('Mutfak Talepleri', 'Mutfak departmanlarından (Mutfak, Soğuk Mutfak, Pastane, Kasap, Bulaşıkhane) açılan talepler. Karar = onay/red verildiği an.',
                        [['Talep', 'm', 8], ['Tarih', 't', 11], ['Departman', 'm', 16], ['İsteyen', 'm', 16], ['Aciliyet', 'm', 8], ['Durum', 'm', 16], ['Kalem', 's', 7, 'top'], ['Karar Süresi (saat)', 's', 13]],
                        $t->map(fn ($x) => ['#' . $x->id, substr((string) $x->created_at, 0, 10), (string) $x->dep_ad, (string) ($x->isteyen_ad ?: '—'), $x->aciliyet === 'acil' ? 'ACİL' : 'Normal', $durAd[$x->durum] ?? $x->durum, $kal->where('talep_id', $x->id)->count(),
                            !empty($x->karar_at) ? round((strtotime((string) $x->karar_at) - strtotime((string) $x->created_at)) / 3600, 1) : null])->values()->all(), $bos),
                    _srSayfa('En Çok İstenen Malzemeler', 'Talep kalemlerinde en sık geçen malzemeler.', [['Malzeme', 'm', 28], ['Talep Sayısı', 's', 11, 'top bar'], ['Toplam Miktar', 's', 12]], $enCok, $bos)];
        }
        return [$pano([], [], 'Bilinmeyen rapor.')];
    }
}

// ---------- Görsel özet (PDF/önizleme): KPI şeridi + gün × saat ısı haritası + kümülatif porsiyon ----------
if (!function_exists('_mrGorsel')) {
    function _mrGorsel($sid, array $dn)
    {
        if (!function_exists('_grKpi') || !function_exists('_grCizgi')) return '';
        $k = _mrKalemler($sid, $dn['bas'], $dn['bit']); $ko = _mrKalemler($sid, $dn['obas'], $dn['obit']); if (!_mrSatis($k)) return '';
        $h = _mrHiz($k); $ho = _mrHiz($ko); $yuz = fn ($a, $b) => $b > 0 ? ($a - $b) / $b * 100 : null;
        $L = $dn['gun']; $gun = []; $ogun = []; foreach (_mrSatis($k) as $x) $gun[$x['tarih']] = ($gun[$x['tarih']] ?? 0) + $x['adet']; foreach (_mrSatis($ko) as $x) $ogun[$x['tarih']] = ($ogun[$x['tarih']] ?? 0) + $x['adet'];
        $et = []; $kum = []; $okum = []; $a = 0; $b = 0; $sp = [];
        for ($i = 0; $i < $L; $i++) { $t = date('Y-m-d', strtotime($dn['bas'] . " +$i day")); $to = date('Y-m-d', strtotime($dn['obas'] . " +$i day")); $a += $gun[$t] ?? 0; $b += $ogun[$to] ?? 0; $kum[] = round($a, 1); $okum[] = $to <= $dn['obit'] ? round($b, 1) : null; $et[] = date('d.m', strtotime($t)); $sp[] = $gun[$t] ?? 0; }
        $html = '<section class="gorsel"><h2>Görsel Özet</h2><p class="not">' . _grE($dn['etiket']) . ' mutfak verisi; kıyas: önceki eşit uzunlukta dönem.</p><div class="gr-kpis">'
            . _grKpi('Porsiyon', number_format(_mrPorsiyon($k), 0, ',', '.'), $yuz(_mrPorsiyon($k), _mrPorsiyon($ko)), $sp, 'yukari', 'var(--k1)', 'günlük porsiyon')
            . _grKpi('Ort. mutfak süresi', $h['top'] !== null ? number_format($h['top'], 1, ',', '.') . ' dk' : '—', $h['top'] !== null && $ho['top'] ? $yuz($h['top'], $ho['top']) : null, [], 'asagi', 'var(--k3)')
            . _grKpi('Hedefe uyum', $h['uyum'] !== null ? '%' . number_format($h['uyum'], 1, ',', '.') : '—', null, [], 'yukari', 'var(--k2)')
            . _grKpi('Geciken kalem', (string) $h['geciken'], null, [], 'asagi', 'var(--k7)') . '</div><div class="gr-izgara" style="margin-top:12px">'
            . '<div class="gr-kart tam"><h4>Kümülatif porsiyon · bu dönem ve önceki dönem</h4><p class="gr-ac">Dönem başından itibaren biriken porsiyon; kesikli çizgi önceki dönemin aynı günü.</p>'
            . _grCizgi($et, [['ad' => 'Bu dönem', 'dg' => $kum, 'renk' => 'var(--k1)', 'alan' => true], ['ad' => 'Önceki dönem', 'dg' => $okum, 'renk' => 'var(--gr-mut)', 'kesik' => true]], 'sayi', 200) . '</div>';
        if (function_exists('_grIsi')) {
            $isiBas = date('Y-m-d', strtotime($dn['bit'] . ' -90 day')); $ig = []; foreach (_mrSatis(_mrKalemler($sid, $isiBas, $dn['bit'])) as $x) $ig[$x['tarih']] = ($ig[$x['tarih']] ?? 0) + $x['adet'];
            $html .= '<div class="gr-kart tam"><h4>Porsiyon takvimi · 13 hafta</h4><p class="gr-ac">Her kare bir gün; koyulaştıkça mutfak o gün daha yoğun.</p>' . _grIsi($ig, $isiBas, $dn['bit']) . '</div>';
        }
        return $html . '</div></section>';
    }
}

// ---------- AI dönem özeti (yalnız AI bölümünde; "AI hazırladı" etiketli) ----------
if (!function_exists('_mrAiOzet')) {
    function _mrAiBaglam($sid, array $dn)
    {
        $k = _mrKalemler($sid, $dn['bas'], $dn['bit']); $ko = _mrKalemler($sid, $dn['obas'], $dn['obit']); $h = _mrHiz($k); $ho = _mrHiz($ko);
        $fc = _mrFoodCost($k); $mm = _mrMenuMuh($k); $avt = _mrAvt($sid, $dn); $fire = _mrFireler($sid, $dn['bas'], $dn['bit']); $ip = _mrIptaller($sid, $dn); $sc = _mrSicaklik($sid, $dn);
        $topla = function (array $l, $a, $v, $n = 8) { $o = []; foreach ($l as $x) $o[$x[$a]] = round(($o[$x[$a]] ?? 0) + $x[$v], 2); arsort($o); return array_slice($o, 0, $n, true); };
        $alis = function_exists('_srAlis') ? _srToplam(_srAlis($sid, $dn['bas'], $dn['bit'])) : 0;
        return ['donem' => $dn['etiket'], 'onceki_donem' => $dn['obas'] . ' – ' . $dn['obit'], 'bugun' => today()->toDateString(), 'kullanilan_moduller' => _mrVeriDurumu($sid),
            'porsiyon' => _mrPorsiyon($k), 'onceki_porsiyon' => _mrPorsiyon($ko), 'satis_tutari' => round(array_sum(array_column(_mrSatis($k), 'tutar')), 2), 'kuver' => _mrKuver($sid, $dn['bas'], $dn['bit']),
            'hiz_dk' => $h, 'onceki_hiz_dk' => $ho, 'istasyonlar' => array_map(fn ($x) => ['istasyon' => $x[0], 'porsiyon' => $x[1], 'ort_dk' => $x[3], 'uyum_yuzde' => $x[4], 'geciken' => $x[5], 'yogun_saat' => $x[6]], _mrIstasyonlar($k)),
            'en_yavas_urunler' => array_map(fn ($x) => ['urun' => $x[0], 'olculen' => $x[2], 'hedef_dk' => $x[3], 'ort_dk' => $x[6], 'uyum_yuzde' => $x[8]], array_slice(collect(_mrUrunSure($k))->filter(fn ($x) => $x[2] >= 2)->sortBy(8)->values()->all(), 0, 8)),
            'saatlik' => array_map(fn ($x) => ['saat' => $x[0], 'porsiyon' => $x[1], 'ort_dk' => $x[3], 'uyum' => $x[4]], _mrSaatlik($k)),
            'en_cok_satan' => collect(_mrSatis($k))->groupBy('ad')->map(fn ($g) => round($g->sum('adet'), 1))->sortDesc()->take(10)->all(),
            'teorik_food_cost_yuzde' => $fc['fc'], 'food_cost_yuksek_urunler' => array_map(fn ($x) => ['urun' => $x['ad'], 'fc' => $x['fc'], 'satis' => $x['tutar']], array_slice($fc['var'], 0, 8)), 'recetesiz_satis_tl' => $fc['yok_tutar'],
            'menu_gruplari' => array_combine(array_map(fn ($a) => _mrSinifAd()[$a], array_keys($mm['sinif'])), array_values($mm['sinif'])), 'menu_ornekleri' => collect($mm['sat'])->groupBy(1)->map(fn ($g) => $g->take(4)->map(fn ($x) => $x[0])->values()->all())->all(),
            'tuketim' => ['teorik_tl' => round($avt['teo'], 2), 'gercek_tl' => round($avt['ger'], 2), 'fire_tl' => round($avt['fire'], 2), 'sayim_kaybi_tl' => round($avt['sayim'], 2), 'sayim_yapildi' => $avt['sayim_var'], 'en_buyuk_fark' => array_map(fn ($x) => ['malzeme' => $x[0], 'fark_tl' => $x[11], 'fark_yuzde' => $x[12]], array_slice(array_filter($avt['sat'], fn ($x) => $x[11] > 0), 0, 8))],
            'fire' => ['toplam_tl' => round(array_sum(array_column($fire, 'maliyet')), 2), 'alisa_orani_yuzde' => $alis > 0 ? round(array_sum(array_column($fire, 'maliyet')) / $alis * 100, 1) : null, 'sebep' => $topla($fire, 'sebep', 'maliyet'), 'malzeme' => $topla($fire, 'malzeme', 'maliyet')],
            'iptal_ikram' => ['iptal_tl' => round(array_sum(array_column(array_filter($ip, fn ($x) => $x['tip'] === 'İptal'), 'tutar')), 2), 'ikram_tl' => round(array_sum(array_column(array_filter($ip, fn ($x) => $x['tip'] === 'İkram'), 'tutar')), 2),
                'mutfaga_gittikten_sonra_iptal' => count(array_filter($ip, fn ($x) => $x['tip'] === 'İptal' && $x['mutfak'])), 'sebep' => $topla($ip, 'sebep', 'tutar')],
            'hijyen' => ['sicaklik_olcum' => count($sc), 'uygunsuz' => count(array_filter($sc, fn ($x) => $x['uygunsuz'])), 'uygunsuz_ekipman' => collect($sc)->where('uygunsuz', true)->groupBy('ekipman')->map->count()->all()],
            'tukendi' => array_slice(array_map(fn ($x) => ['urun' => $x[0], 'kez' => $x[1], 'saat' => $x[2]], _mrTukendi($sid, $dn)), 0, 8),
            'uretim_kullanim' => array_map(fn ($x) => ['yari_mamul' => $x[0], 'uretilen' => $x[2], 'kullanilan' => $x[3], 'kullanim_yuzde' => $x[5], 'fire_yuzde' => $x[6]], array_slice(_mrUretimKullanim($sid, $dn), 0, 8)),
            'kritik_stok' => array_slice(array_map(fn ($x) => $x[0], _srKritikStok($sid)), 0, 10), 'skt_7_gun' => array_slice(array_map(fn ($x) => $x['malzeme'] . ' (' . $x['kalan_gun'] . ' gün)', _srSktPartiler($sid, 7)), 0, 10),
            'ekip_saat' => round(array_sum(array_column(_mrMesai($sid, $dn)['sat'], 4)), 1), 'mutfak_talepleri' => _mrTalepler($sid, $dn)->count()];
    }
    function _mrAiOzet($sid, array $dn, $soru = '', $yenile = false)
    {
        $gunKey = 'mr_ai_gun_' . $sid . '_' . date('Y-m-d');
        if ((int) cache()->get($gunKey, 0) >= 40) return ['ok' => 0, 'hata' => 'Bugünkü mutfak AI hakkı doldu (40). Yarın tekrar dene.'];
        $b = _mrAiBaglam($sid, $dn);
        if (!$b['porsiyon'] && !$b['fire']['toplam_tl'] && !$b['hijyen']['sicaklik_olcum']) return ['ok' => 0, 'hata' => 'Bu dönemde analiz edilecek mutfak verisi yok (satış, fire, kontrol kaydı yok).'];
        $key = config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
        if (!$key) return ['ok' => 0, 'hata' => 'AI anahtarı tanımlı değil.'];
        $sys = 'Sen bir restoranın mutfağını şef ve üst yönetim için analiz eden deneyimli bir mutfak operasyon danışmanısın. Türkçe, kısa ve rakamlı yaz; yalnız verilen veriyi kullan, rakam uydurma, kendin toplama/çarpma yapma. '
            . 'Süreler dakika (dk), para TL; sayıları Türkçe biçimde yaz (1.452,00 TL, %12,5). "kullanilan_moduller" 0 olan modülden sonuç çıkarma (örn. kds 0 ise hız yorumu yapma, "veri yok" de). '
            . 'Teorik food cost reçete maliyeti ÷ satıştır; tüketim farkı (gerçek − teorik) fire ve sayım kaybından gelir. Menü grupları: "En İyi Ürünler", "Zam Adayları", "Öne Çıkarılacaklar", "Çıkarma Adayları" (bu adlarla yaz; Yıldız/İş Atı gibi İngilizce kökenli adlar kullanma). Bugün ' . today()->format('d.m.Y') . '.';
        if (trim((string) $soru) !== '') {
            $r = function_exists('_fr2AiCagir') ? _fr2AiCagir($sys . ' Soruyu veriye dayanarak kısa paragraflar ve madde işaretleriyle cevapla. Markdown başlık kullanma.', 'Veri: ' . json_encode($b, JSON_UNESCAPED_UNICODE) . "\n\nSoru: " . mb_substr((string) $soru, 0, 500), 1200) : ['hata' => 'AI servisi yok.'];
            if (!empty($r['hata'])) return ['ok' => 0, 'hata' => $r['hata']];
            cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
            return ['ok' => 1, 'cevap' => trim(preg_replace(['/^#{1,6}\s*/m', '/\*\*(.+?)\*\*/s', '/^\s*[-*]\s+/m'], ['', '$1', '• '], $r['metin']))];
        }
        $fp = 'mr_ai_' . $sid . '_' . md5(json_encode($b));
        if (!$yenile && ($c = cache()->get($fp))) return ['ok' => 1, 'analiz' => $c, 'onbellek' => 1];
        $str = ['type' => 'string']; $liste = fn ($desc) => ['type' => 'array', 'maxItems' => 5, 'description' => $desc, 'items' => ['type' => 'object', 'properties' => ['baslik' => $str, 'detay' => $str], 'required' => ['baslik', 'detay']]];
        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key, 'anthropic-version' => '2023-06-01'])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001', 'max_tokens' => 2000,
                'system' => $sys . ' Her detay en fazla 2 cümle, somut ürün/malzeme/istasyon adı ve verideki rakamla.',
                'tools' => [['name' => 'mutfak_ozeti', 'description' => 'Dönem mutfak yönetici özeti', 'input_schema' => ['type' => 'object', 'required' => ['ozet', 'one_cikanlar', 'riskler', 'oneriler'], 'properties' => [
                    'ozet' => $str + ['description' => '3-4 cümle dönem özeti (porsiyon, hız, food cost, fire; önceki döneme göre, en önemli gelişme)'],
                    'one_cikanlar' => $liste('3-5 öne çıkan gelişme'), 'riskler' => $liste('2-4 risk / dikkat edilecek konu (hijyen, gecikme, fire, maliyet)'), 'oneriler' => $liste('2-4 şefe somut öneri / yapılacak iş')]]]],
                'tool_choice' => ['type' => 'tool', 'name' => 'mutfak_ozeti'],
                'messages' => [['role' => 'user', 'content' => 'Mutfak dönem verisi: ' . json_encode($b, JSON_UNESCAPED_UNICODE)]]]);
            if (!$resp->successful()) return ['ok' => 0, 'hata' => 'AI servisi şu an cevap vermedi (' . $resp->status() . ').'];
            $j = null; foreach ((array) $resp->json('content') as $c) if (($c['type'] ?? '') === 'tool_use') $j = $c['input'] ?? null;
            if (!is_array($j)) return ['ok' => 0, 'hata' => 'AI yanıtı eksik geldi, tekrar dene.'];
        } catch (\Throwable $e) { return ['ok' => 0, 'hata' => 'AI servisine bağlanılamadı.']; }
        $j['_zaman'] = now()->format('d.m.Y H:i'); $j['_donem'] = $dn['etiket'];
        cache()->put($fp, $j, now()->addHours(12)); cache()->put('mr_ai_son_' . $sid, $j, now()->addDays(30));
        cache()->put($gunKey, (int) cache()->get($gunKey, 0) + 1, now()->endOfDay());
        return ['ok' => 1, 'analiz' => $j];
    }
    function _mrAiSayfalar($sid, array $dn, array $ai)
    {
        $uyari = 'Bu sayfa YAPAY ZEKÂ tarafından hazırlanmıştır (' . ($ai['_zaman'] ?? '') . ', dönem ' . ($ai['_donem'] ?? $dn['etiket']) . '). Öneriler karar desteğidir; son karar şefindir. Rakamlar sonraki "Dayanak" sayfalarında doğrulanabilir.';
        $sat = [['Dönem özeti', '', $ai['ozet'] ?? '']];
        foreach (['one_cikanlar' => 'Öne çıkan', 'riskler' => 'Risk', 'oneriler' => 'Öneri'] as $k => $ad) foreach ((array) ($ai[$k] ?? []) as $x) $sat[] = [$ad, $x['baslik'] ?? '', $x['detay'] ?? ''];
        $s = [['ad' => 'AI Mutfak Özeti', 'ai' => 1, 'not' => $uyari, 'kol' => [['Bölüm', 'm', 16], ['Başlık', 'm', 34], ['AI Yorumu', 'm', 90]], 'sat' => $sat]];
        foreach (_mrRapor($dn['gun'] > 20 ? 'aylik' : 'haftalik', $sid, $dn) as $i => $x) { if ($i === 0) { $x['ad'] = 'Dayanak - Pano'; $s = array_merge([$x], $s); continue; } $x['ad'] = mb_substr('Dayanak - ' . $x['ad'], 0, 31); $s[] = $x; }
        return $s;
    }
    // AI sayfasının üst şeridi: yapay zekâ çağırmadan gerçek rakamlar + bugünkü kalan AI hakkı
    function _mrAiNabiz($sid, array $dn)
    {
        $kalan = max(0, 40 - (int) cache()->get('mr_ai_gun_' . $sid . '_' . date('Y-m-d'), 0));
        try {
            $k = _mrKalemler($sid, $dn['bas'], $dn['bit']); $ko = _mrKalemler($sid, $dn['obas'], $dn['obit']); $h = _mrHiz($k); $fc = _mrFoodCost($k);
            $fire = array_sum(array_column(_mrFireler($sid, $dn['bas'], $dn['bit']), 'maliyet')); $alis = function_exists('_srAlis') ? _srToplam(_srAlis($sid, $dn['bas'], $dn['bit'])) : 0;
            $ip = _mrIptaller($sid, $dn); $mf = array_filter($ip, fn ($x) => $x['tip'] === 'İptal' && $x['mutfak']); $sc = _mrSicaklik($sid, $dn);
            return ['kalan' => $kalan, 'veri' => ['porsiyon' => _mrPorsiyon($k), 'degisim' => _srYuzde(_mrPorsiyon($k), _mrPorsiyon($ko)), 'sure' => $h['top'], 'uyum' => $h['uyum'], 'geciken' => $h['geciken'], 'olcum' => $h['n'],
                'fc' => $fc['fc'], 'recetesiz' => $fc['yok_tutar'], 'fire' => round($fire, 2), 'fire_oran' => $alis > 0 ? round($fire / $alis * 100, 1) : null, 'iptal' => count($mf), 'iptal_tl' => round(array_sum(array_column($mf, 'tutar')), 2),
                'sicaklik' => count($sc), 'uygunsuz' => count(array_filter($sc, fn ($x) => $x['uygunsuz'])), 'kritik' => count(_srKritikStok($sid)), 'skt' => count(_srSktPartiler($sid, 7))]];
        } catch (\Throwable $e) { return ['kalan' => $kalan, 'veri' => null]; }
    }
}

// =====================================================================
// ROTALAR
// =====================================================================
if (!function_exists('_mrErisim')) {
    // Şef / sous şef (mutfak departmanı sorumlusu), müdür, patron ve süpervizör görür; aşçı / komi görmez (ya da elle verilen 'gor_mutfak_rapor')
    function _mrErisim($p = null)
    {
        if (!$p) { try { $p = _posPersonel(); } catch (\Throwable $e) {} }
        if (!$p) return false;
        $kad = $p->kademe ?? (function_exists('_rolKademe') ? _rolKademe($p->rol ?? '') : '');
        if (in_array($p->rol ?? '', ['sahip', 'mudur'], true) || in_array($kad, ['sahip', 'supervizor'], true)) return true;
        if (function_exists('_panelYetkiVar') && _panelYetkiVar($p, 'gor_mutfak_rapor')) return true;
        if (!empty($p->departman_sorumlu) && !empty($p->departman_id)) {
            try { $kod = DB::table('departmanlar')->where('id', (int) $p->departman_id)->value('kod'); return in_array($kod, _mrDepKodlari(), true); } catch (\Throwable $e) {}
        }
        return false;
    }
    function _mrIndir($sid, $rapor, array $dn, $format, $onizle = false)
    {
        $kat = _mrKatalog();
        if ($rapor === 'ai') {
            $ai = cache()->get('mr_ai_son_' . $sid);
            if (!$ai) return response('Önce Mutfak Raporları > AI Analiz bölümünden "Özet Hazırla" deyin.', 404);
            $sayfalar = _mrAiSayfalar($sid, $dn, $ai); $baslik = 'AI Mutfak Özeti'; $tema = '9333EA'; $ikon = 'auto_awesome'; $acik = 'Yapay zekânın seçili dönem için mutfak özeti, riskleri ve önerileri + dayandığı rakamlar.';
        } else {
            if (!isset($kat[$rapor])) $rapor = 'aylik';
            $sayfalar = _mrRapor($rapor, $sid, $dn); [$_k, $baslik, $acik, $ikon, $renk] = $kat[$rapor]; $tema = ltrim($renk, '#');
        }
        $subeAd = 'ResteOS'; try { $subeAd = DB::table('subeler')->where('id', $sid)->value('ad') ?: 'ResteOS'; } catch (\Throwable $e) {}
        $alt = $subeAd . ' · ' . $dn['ad'] . ' (' . $dn['etiket'] . ') · Oluşturma: ' . now()->format('d.m.Y H:i');
        $dosya = \Illuminate\Support\Str::slug($baslik) . '-' . $dn['bas'] . ($dn['bit'] !== $dn['bas'] ? '_' . $dn['bit'] : '');
        if ($format === 'pdf' || $format === 'html') {
            return response()->view('fiyat_radari_rapor', ['baslik' => $baslik, 'alt' => $alt, 'sayfalar' => $sayfalar, 'v' => [], 'ai' => null, 'rapor' => $rapor, 'dosya' => $dosya,
                'tema' => $tema, 'ikon' => $ikon, 'aciklama' => $acik, 'marka' => 'Mutfak Raporları', 'onizle' => $onizle || $format === 'html', 'aiEtiket' => $rapor === 'ai',
                'gorsel' => in_array($rapor, ['gunluk', 'haftalik', 'aylik', 'hiz', 'satis'], true) ? _mrGorsel($sid, $dn) : '']);
        }
        $x = _fr2Xlsx($sayfalar, $baslik, $alt, $tema);
        if ($x === null) return response('Excel üretilemedi (sunucuda ZipArchive yok).', 500);
        return response($x, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="' . $dosya . '.xlsx"']);
    }
}

Route::get('/mutfak-raporlar', function (Request $r) {
    $qs = $r->getQueryString();
    return redirect('/mutfak-raporlar/donemsel' . ($qs ? '?' . $qs : ''));
});
Route::get('/mutfak-raporlar/indir', function (Request $r) {
    if (!_mrErisim()) return redirect('/pos-giris');
    $kat = _mrKatalog(); $rapor = (string) $r->query('rapor', 'aylik');
    $dn = _srIstekDonem($r, $kat[$rapor][6] ?? 'bu_ay');
    return _mrIndir(_webSubeId(), $rapor, $dn, (string) $r->query('format', 'xlsx'), $r->boolean('onizle'));
});
Route::get('/mutfak-raporlar/{kat}', function (Request $r, $kat) {
    if (!_mrErisim()) return redirect('/pos-giris');
    _mrEnsure();
    $sid = _webSubeId(); $dn = _srIstekDonem($r);
    $d = []; try { $d = _mrVeriDurumu($sid); } catch (\Throwable $e) {}
    return view('mutfak_raporlar', ['kat' => $kat, 'dn' => $dn, 'durum' => $d, 'katalog' => _mrKatalog(), 'kategoriler' => _mrKategoriler(),
        'ai' => cache()->get('mr_ai_son_' . $sid), 'aiNabiz' => $kat === 'ai' ? _mrAiNabiz($sid, $dn) : null]);
})->where('kat', 'donemsel|hiz|maliyet|menu|fire|uretim|stok|hijyen|iptal|ekip|ai');
Route::post('/mutfak-raporlar/ai', function (Request $r) {
    if (!_mrErisim()) return response()->json(['ok' => 0, 'hata' => 'Yetkin yok.'], 403);
    return response()->json(_mrAiOzet(_webSubeId(), _srIstekDonem($r), (string) $r->input('soru', ''), $r->boolean('yenile')));
});

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * RESTORAN PATRON ASISTANI — niyet cozumleme + dogal dil cevap (KURAL MOTORU).
 * (Randevumcepte salon copilot'undan restorana uyarlandi.)
 *
 * Akis: patron serbest sorar (sesli->STT cihazda / yazili) -> niyetCoz (LLM YOK, bedava)
 *  -> gercek veri DB'den cekilir (rakam ASLA uydurulmaz) -> dogal Turkce cevap + kart.
 *  Cozulemezse Haiku (tool ile niyet) -> yine olmazsa sohbetAI. Cozulen OGRENILIR (bedava tekrar).
 */
class RestoAsistan
{
    public $aiTeshis = null;

    protected $niyetAnahtarlari = [
        'ciro' => ['ciro', 'kasa', 'hasilat', 'kazanc', 'kazandik', 'ne kadar kazan', 'kac para', 'ne kadar para',
            'tahsilat', 'gelir', 'kac lira', 'ne kadar sattik', 'ne kadar satis', 'satis ne kadar', 'toplam satis',
            'bugun ne kadar', 'kasada ne', 'kasa ne durumda', 'kac tl',
            'kasa rapor', 'kasa raporu', 'ciro rapor', 'gelir rapor', 'satis rapor', 'hasilat rapor', 'kazanc rapor',
            'hesabi cikar', 'hesap durumu', 'kasa durumu', 'kasa dokum'],
        'garson' => ['garson', 'personel', 'eleman', 'kim satti', 'en cok kim', 'kim en cok', 'hangi garson', 'hangi personel',
            'hangi eleman', 'kim ne satti', 'en iyi garson', 'en iyi personel', 'en cok satan personel', 'en cok satan garson',
            'en cok satan kim', 'satan kim', 'kim getirdi', 'kim calisti', 'performans',
            'en dusuk', 'en az satan', 'en kotu garson'],
        'urun' => ['urun', 'yemek', 'hangi urun', 'hangi yemek', 'en cok satan', 'cok satilan', 'satan urun', 'populer',
            'ne satiyor', 'en cok yenen', 'ne sattik', 'ne satti', 'ne satildi', 'en cok ne', 'en cok hangi',
            'cok satan', 'cok satti', 'en cok satis', 'en cok satilan'],
        'masa' => ['masa', 'acik masa', 'dolu masa', 'bos masa', 'kac masa', 'masalar', 'oturan', 'acik adisyon'],
        'paket' => ['paket', 'kurye', 'paket siparis', 'gel al', 'teslimat', 'motorcu', 'kac paket'],
        'maliyet' => ['maliyet', 'food cost', 'foodcost', 'food-cost', 'kar', 'karlilik', 'brut kar', 'gider orani', 'malzeme maliyet'],
        'kayip' => ['kayip', 'sizinti', 'kacak', 'iskonto', 'indirim', 'ikram', 'fire', 'zayi', 'silinen', 'iptal urun', 'suistimal'],
        'iptal' => ['iptal adisyon', 'iptal', 'iptaller', 'iptal olan', 'kac iptal', 'iptal edilen'],
        'musteri' => ['musteri', 'kac kisi', 'kac musteri', 'yeni musteri', 'sadik musteri', 'misafir sayisi', 'kac misafir'],
        'ozet' => ['ozet', 'genel durum', 'gun sonu', 'nasil gidiyor', 'nasil gecti', 'isler nasil', 'bugun nasil',
            'ne alemde', 'durum ne', 'rapor ver', 'gunumuz nasil',
            'rapor', 'raporu', 'raporunu', 'rapor cikar', 'raporu cikar', 'durum raporu', 'genel rapor',
            'isletme raporu', 'ozet cikar', 'ozetle'],
        // --- yeni modüller ---
        'tespit' => ['terslik', 'goremedigim', 'gormedigim', 'goremedigim ne', 'nerede para', 'para kaciyor', 'para kaciriyoruz',
            'kacan para', 'dikkat etmem', 'endiselendirecek', 'sorun var mi', 'ters giden', 'beni uzecek', 'ne yapmaliyim',
            'anormallik', 'gizli firsat', 'firsat var mi', 'kacak var mi', 'beni koru', 'neyi kacir', 'gozden kacan',
            'dikkatimi ceken', 'onemli bir sey', 'nasil gidiyor sence', 'sence nasil', 'ne var ne yok',
            'gozune carpan', 'gozune batan', 'gozune takilan', 'yanlis giden', 'yanlis bir sey', 'ters bir sey',
            'sikinti var', 'problem var', 'yolunda gitmeyen', 'kotu giden', 'dikkat cekici', 'fark ettin', 'farkettin'],
        'finans' => ['net kar', 'kar zarar', 'zarar mi', 'karda mi', 'zararda mi', 'kar mi ediyor', 'kar mi zarar',
            'gelir gider', 'net kazanc', 'ay sonu kar', 'karli mi', 'kar ettim mi', 'ne kar ettim', 'kara mi geciyor',
            'zarardayiz', 'kar mi ettim', 'kar ettim', 'kar mi', 'kar mi var', 'kazandim mi', 'para kaldi mi'],
        'satinalma' => ['satin alma', 'satinalma', 'alis', 'alim', 'tedarik', 'tedarikci', 'ne kadar alis', 'ne aldim',
            'alis fatura', 'fatura girdim', 'fiyat artan', 'fiyati artan', 'zamlanan', 'zam gelen', 'en cok aldigim', 'hangi tedarikci', 'kimden aldim'],
        'maas' => ['maas', 'avans', 'hakedis', 'prim', 'maas gideri', 'ne kadar maas', 'odenecek maas',
            'personel odeme', 'personele odeme', 'personel maas', 'kime avans', 'net odenecek', 'maas verdim'],
        'stok' => ['stok', 'malzeme', 'depo', 'envanter', 'kritik stok', 'stok durum', 'stok degeri',
            'ne kadar malzeme', 'malzeme bitti', 'stok azaldi', 'biten malzeme', 'eksilen malzeme', 'kalan malzeme', 'siparis vermem'],
        'gider' => ['gider', 'masraf', 'kira', 'harcama', 'sabit gider', 'ne kadar gider', 'gider ne',
            'giderim', 'giderler', 'elektrik', 'su faturasi', 'dogalgaz', 'ne kadar masraf'],
    ];

    // -------------------- NIYET --------------------
    public function niyetCoz($metin)
    {
        $ham = trim((string) $metin);
        $norm = $this->normalize($ham);
        $donem = $this->donemCoz($norm);
        $skor = array_fill_keys(array_keys($this->niyetAnahtarlari), 0);
        foreach ($this->niyetAnahtarlari as $niyet => $kelimeler) {
            foreach ($kelimeler as $k) {
                if (strpos($norm, $this->normalize($k)) !== false) $skor[$niyet]++;
            }
        }
        $oncelik = ['tespit', 'finans', 'satinalma', 'maas', 'iptal', 'kayip', 'maliyet', 'stok', 'gider', 'garson', 'urun', 'masa', 'paket', 'musteri', 'ciro', 'ozet'];
        $enIyi = 'bilinmiyor';
        $enYuksek = 0;
        foreach ($oncelik as $n) {
            if (($skor[$n] ?? 0) > $enYuksek) { $enYuksek = $skor[$n]; $enIyi = $n; }
        }
        return [
            'intent' => $enYuksek > 0 ? $enIyi : 'bilinmiyor',
            'donem' => $donem['anahtar'], 'donemAdi' => $donem['ad'], 'ham' => $ham,
        ];
    }

    protected function donemCoz($norm)
    {
        // "bugun/gunluk" acikca istenirse gune sabitle
        if (preg_match('/\b(bugun|bu ?gun|gunluk|bugunku|son ?24|gun ?ici)\b/', $norm)) return ['anahtar' => 'gunluk', 'ad' => 'bugün'];
        if (preg_match('/\b(bu ?yil|bu ?yilki|yillik|yilki|gecen ?yil|senelik|bir ?yil|son ?365|son ?12 ?ay|yil ?ici|yil ?boyunca|yilin)\b/', $norm)) return ['anahtar' => 'yillik', 'ad' => 'bu yıl'];
        if (preg_match('/\b(bu ?ay|bu ?ayki|ayki|aylik|son ?30|son ?bir ?ay|gecen ?ay|bir ?ay|ay ?ici|ay ?boyunca|ayin|aya ?ait|gecen ?aya)\b/', $norm)) return ['anahtar' => 'aylik', 'ad' => 'bu ay'];
        if (preg_match('/\b(bu ?hafta|bu ?haftaki|haftaki|haftalik|son ?7|son ?bir ?hafta|gecen ?hafta|bir ?hafta|hafta ?ici|hafta ?boyunca|haftanin)\b/', $norm)) return ['anahtar' => 'haftalik', 'ad' => 'bu hafta'];
        return ['anahtar' => 'gunluk', 'ad' => 'bugün'];
    }

    /** [from, to] araligi. */
    protected function aralik($donem)
    {
        $now = now();
        switch ($donem) {
            case 'yillik': return [(clone $now)->subDays(365), $now];
            case 'aylik': return [(clone $now)->subDays(30), $now];
            case 'haftalik': return [(clone $now)->subDays(7), $now];
            default: return [today()->startOfDay(), $now];
        }
    }

    // -------------------- VERI + CEVAP --------------------
    /** Niyete gore gercek veriyi ceker ve dogal cevap uretir. */
    public function cevapla(array $niyet)
    {
        [$from, $to] = $this->aralik($niyet['donem']);
        $d = $niyet['donemAdi'];
        switch ($niyet['intent']) {
            case 'ciro':    return $this->ciCiro($from, $to, $d, $niyet);
            case 'garson':  return $this->cvGarson($from, $to, $d, $niyet);
            case 'urun':    return $this->cvUrun($from, $to, $d, $niyet);
            case 'masa':    return $this->cvMasa($d, $niyet);
            case 'paket':   return $this->cvPaket($d, $niyet);
            case 'maliyet': return $this->cvMaliyet($from, $to, $d, $niyet);
            case 'kayip':   return $this->cvKayip($from, $to, $d, $niyet);
            case 'iptal':   return $this->cvIptal($from, $to, $d, $niyet);
            case 'musteri': return $this->cvMusteri($from, $to, $d, $niyet);
            case 'ozet':    return $this->cvOzet($from, $to, $d, $niyet);
            case 'finans':  return $this->cvFinans($niyet);
            case 'satinalma': return $this->cvSatinAlma($niyet);
            case 'maas':    return $this->cvMaas($niyet);
            case 'stok':    return $this->cvStok($niyet);
            case 'gider':   return $this->cvGider($niyet);
            default:        return null;
        }
    }

    /** Bu ay [başlangıç, şimdi]. Finans/gider/maaş/alış aylık kavramlar. */
    protected function ayAralik()
    {
        return [now()->startOfMonth(), now()];
    }

    protected function cvFinans($niyet)
    {
        try {
            [$bas, $to] = $this->ayAralik();
            $gelir = (float) DB::table('odemeler')->whereBetween('created_at', [$bas, $to])->sum('tutar');
            $gider = 0.0;
            if (Schema::hasTable('giderler')) $gider = (float) DB::table('giderler')->whereBetween('tarih', [$bas->toDateString(), $to->toDateString()])->sum('tutar');
            $net = $gelir - $gider;
            if ($gelir <= 0 && $gider <= 0) return $this->paket_('finans', 'Bu ay için henüz gelir veya gider hareketi görünmüyor.', $niyet);
            $durum = $net >= 0 ? 'net kâr' : 'net zarar';
            $cevap = 'Bu ay geliriniz ' . $this->tl($gelir) . ', gideriniz ' . $this->tl($gider) . '. Yani ' . $durum . ' ' . $this->tl(abs($net)) . '.';
            if ($net < 0) $cevap .= ' Giderler geliri aşmış, kalemleri gözden geçirmekte fayda var.';
            elseif ($gelir > 0 && $net < $gelir * 0.1) $cevap .= ' Kâr marjı ince, maliyet tarafına dikkat.';
            return $this->cvp('finans', $cevap, $niyet, ['tip' => 'finans', 'baslik' => 'Kasa · Bu Ay', 'kv' => [
                ['k' => 'Gelir', 'v' => $this->tl($gelir)], ['k' => 'Gider', 'v' => $this->tl($gider)], ['k' => $durum === 'net kâr' ? 'Net Kâr' : 'Net Zarar', 'v' => $this->tl(abs($net))],
            ]]);
        } catch (\Throwable $e) {
            return $this->paket_('finans', 'Finansal veriye şu an ulaşamadım, birazdan tekrar deneyin.', $niyet);
        }
    }

    protected function cvSatinAlma($niyet)
    {
        try {
            if (!Schema::hasTable('alis_faturalari')) return $this->paket_('satinalma', 'Henüz alış faturası kaydı yok.', $niyet);
            [$bas, $to] = $this->ayAralik();
            $b = $bas->toDateString(); $e = $to->toDateString();
            $toplam = (float) DB::table('alis_faturalari')->whereBetween('tarih', [$b, $e])->sum('toplam');
            if ($toplam <= 0) return $this->paket_('satinalma', 'Bu ay henüz alış faturası girilmemiş.', $niyet);
            $topTed = DB::table('alis_faturalari')->join('tedarikciler', 'alis_faturalari.tedarikci_id', '=', 'tedarikciler.id')
                ->whereBetween('alis_faturalari.tarih', [$b, $e])->select('tedarikciler.ad', DB::raw('SUM(alis_faturalari.toplam) as t'))
                ->groupBy('tedarikciler.id', 'tedarikciler.ad')->orderByDesc('t')->first();
            $zamlar = DB::table('alis_fatura_kalemleri')->join('alis_faturalari', 'alis_fatura_kalemleri.fatura_id', '=', 'alis_faturalari.id')
                ->join('malzemeler', 'alis_fatura_kalemleri.malzeme_id', '=', 'malzemeler.id')
                ->whereBetween('alis_faturalari.tarih', [$b, $e])->whereIn('alis_fatura_kalemleri.uyari', ['sari', 'kirmizi'])
                ->select('malzemeler.ad', 'alis_fatura_kalemleri.fiyat_farki_yuzde')->orderByDesc('alis_fatura_kalemleri.fiyat_farki_yuzde')->limit(3)->get();
            $cevap = 'Bu ay toplam ' . $this->tl($toplam) . ' alış yapmışsınız';
            if ($topTed) $cevap .= '. En çok ' . $topTed->ad . ' tedarikçisinden (' . $this->tl((float) $topTed->t) . ')';
            $cevap .= '.';
            $kv = [['k' => 'Toplam Alış', 'v' => $this->tl($toplam)]];
            if ($topTed) $kv[] = ['k' => 'En çok', 'v' => $topTed->ad];
            if ($zamlar->isNotEmpty()) {
                $isim = [];
                foreach ($zamlar as $z) { $isim[] = $z->ad . ' %' . round((float) $z->fiyat_farki_yuzde); $kv[] = ['k' => $z->ad, 'v' => '%' . round((float) $z->fiyat_farki_yuzde) . ' zam']; }
                $cevap .= ' Dikkat, fiyatı artan malzemeler: ' . implode(', ', $isim) . '.';
            }
            return $this->cvp('satinalma', $cevap, $niyet, ['tip' => 'satinalma', 'baslik' => 'Satın Alma · Bu Ay', 'kv' => $kv]);
        } catch (\Throwable $e) {
            return $this->paket_('satinalma', 'Alış verisine şu an ulaşamadım, birazdan tekrar deneyin.', $niyet);
        }
    }

    protected function cvMaas($niyet)
    {
        try {
            if (!Schema::hasTable('personel_hareketleri')) return $this->paket_('maas', 'Henüz personel maaş/ödeme kaydı yok. Personel sayfasından maaş tanımlayabilirsiniz.', $niyet);
            [$bas, $to] = $this->ayAralik();
            $b = $bas->toDateString(); $e = $to->toDateString();
            $norm = $this->normalize($niyet['ham']);
            // İsim geçiyor mu? (kişiye özel cevap)
            $kisi = null;
            foreach (DB::table('personeller')->where('aktif', 1)->get(['id', 'ad', 'maas']) as $p) {
                $ad = $this->normalize($p->ad);
                if ($ad && strpos($norm, $ad) !== false) { $kisi = $p; break; }
                $ilk = explode(' ', $ad)[0] ?? '';
                if (mb_strlen($ilk) >= 3 && strpos($norm, $ilk) !== false) { $kisi = $p; break; }
            }
            if ($kisi) {
                $avans = (float) DB::table('personel_hareketleri')->where('personel_id', $kisi->id)->where('tur', 'avans')->whereBetween('tarih', [$b, $e])->sum('tutar');
                $odenen = (float) DB::table('personel_hareketleri')->where('personel_id', $kisi->id)->where('tur', 'odeme')->whereBetween('tarih', [$b, $e])->sum('tutar');
                $cevap = $kisi->ad . ' için bu ay maaş ' . $this->tl((float) $kisi->maas) . '. Şu ana kadar ' . $this->tl($odenen) . ' ödenmiş, ' . $this->tl($avans) . ' avans verilmiş.';
                return $this->cvp('maas', $cevap, $niyet, ['tip' => 'maas', 'baslik' => $kisi->ad . ' · Maaş', 'kv' => [
                    ['k' => 'Maaş', 'v' => $this->tl((float) $kisi->maas)], ['k' => 'Ödenen', 'v' => $this->tl($odenen)], ['k' => 'Avans', 'v' => $this->tl($avans)],
                ]]);
            }
            $maasToplam = (float) DB::table('personeller')->where('aktif', 1)->sum('maas');
            $avans = (float) DB::table('personel_hareketleri')->where('tur', 'avans')->whereBetween('tarih', [$b, $e])->sum('tutar');
            $odenen = (float) DB::table('personel_hareketleri')->where('tur', 'odeme')->whereBetween('tarih', [$b, $e])->sum('tutar');
            $prim = (float) DB::table('personel_hareketleri')->where('tur', 'prim')->whereBetween('tarih', [$b, $e])->sum('tutar');
            $cevap = 'Bu ay toplam maaş tahakkuku ' . $this->tl($maasToplam) . '. Şu ana kadar ' . $this->tl($odenen) . ' ödenmiş, ' . $this->tl($avans) . ' avans verilmiş';
            if ($prim > 0) $cevap .= ', ' . $this->tl($prim) . ' de prim';
            $cevap .= '.';
            return $this->cvp('maas', $cevap, $niyet, ['tip' => 'maas', 'baslik' => 'Personel · Bu Ay', 'kv' => [
                ['k' => 'Maaş tahakkuk', 'v' => $this->tl($maasToplam)], ['k' => 'Ödenen', 'v' => $this->tl($odenen)], ['k' => 'Avans', 'v' => $this->tl($avans)],
            ]]);
        } catch (\Throwable $e) {
            return $this->paket_('maas', 'Personel maaş verisine şu an ulaşamadım, birazdan tekrar deneyin.', $niyet);
        }
    }

    protected function cvStok($niyet)
    {
        try {
            if (!Schema::hasTable('malzemeler')) return $this->paket_('stok', 'Henüz stok/malzeme kaydı yok.', $niyet);
            $mevcut = DB::table('stok_hareketleri')->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
            $malz = DB::table('malzemeler')->get(['id', 'ad', 'kritik_stok', 'guncel_maliyet', 'stok_takipli']);
            if ($malz->isEmpty()) return $this->paket_('stok', 'Henüz tanımlı malzeme yok. Stok ekranından ekleyebilirsiniz.', $niyet);
            $toplamDeger = 0.0; $kritikler = [];
            foreach ($malz as $m) {
                $stok = (float) ($mevcut[$m->id] ?? 0);
                $toplamDeger += $stok * (float) $m->guncel_maliyet;
                if ($m->stok_takipli && (float) $m->kritik_stok > 0 && $stok <= (float) $m->kritik_stok) $kritikler[] = $m->ad;
            }
            $cevap = 'Toplam stok değeriniz yaklaşık ' . $this->tl($toplamDeger) . '.';
            $kv = [['k' => 'Stok değeri', 'v' => $this->tl($toplamDeger)], ['k' => 'Malzeme çeşidi', 'v' => (string) $malz->count()]];
            if (count($kritikler) > 0) {
                $ilk = array_slice($kritikler, 0, 5);
                $cevap .= ' ' . count($kritikler) . ' malzeme kritik seviyede: ' . implode(', ', $ilk) . (count($kritikler) > 5 ? ' ve diğerleri' : '') . '. Sipariş vermeniz gerekebilir.';
                $kv[] = ['k' => 'Kritik malzeme', 'v' => (string) count($kritikler)];
            } else {
                $cevap .= ' Kritik seviyede malzeme yok, stok rahat görünüyor.';
            }
            return $this->cvp('stok', $cevap, $niyet, ['tip' => 'stok', 'baslik' => 'Stok Durumu', 'kv' => $kv]);
        } catch (\Throwable $e) {
            return $this->paket_('stok', 'Stok verisine şu an ulaşamadım, birazdan tekrar deneyin.', $niyet);
        }
    }

    protected function cvGider($niyet)
    {
        try {
            if (!Schema::hasTable('giderler')) return $this->paket_('gider', 'Bu ay henüz gider kaydı yok.', $niyet);
            [$bas, $to] = $this->ayAralik();
            $rows = DB::table('giderler')->whereBetween('tarih', [$bas->toDateString(), $to->toDateString()])
                ->selectRaw('kategori, SUM(tutar) t')->groupBy('kategori')->orderByDesc('t')->get();
            if ($rows->isEmpty()) return $this->paket_('gider', 'Bu ay henüz gider kaydı görünmüyor.', $niyet);
            $adlar = ['kira' => 'kira', 'fatura' => 'fatura', 'malzeme' => 'malzeme/alış', 'maas' => 'maaş', 'vergi' => 'vergi', 'diger' => 'diğer'];
            $toplam = 0.0; $parca = []; $kv = [];
            foreach ($rows as $r) {
                $toplam += (float) $r->t;
                $ad = $adlar[$r->kategori] ?? $r->kategori;
                $parca[] = $ad . ' ' . $this->tl((float) $r->t);
                $kv[] = ['k' => ucfirst($ad), 'v' => $this->tl((float) $r->t)];
            }
            $cevap = 'Bu ay toplam gideriniz ' . $this->tl($toplam) . '. Dağılım: ' . implode(', ', array_slice($parca, 0, 5)) . '.';
            return $this->cvp('gider', $cevap, $niyet, ['tip' => 'gider', 'baslik' => 'Giderler · Bu Ay', 'kv' => $kv]);
        } catch (\Throwable $e) {
            return $this->paket_('gider', 'Gider verisine şu an ulaşamadım, birazdan tekrar deneyin.', $niyet);
        }
    }

    protected function ciCiro($from, $to, $d, $niyet)
    {
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        $adet = DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->count();
        if ($ciro <= 0) return $this->paket_('ciro', ucfirst($d) . ' için henüz bir tahsilat görünmüyor.', $niyet);
        $cevap = ucfirst($d) . ' toplam ' . $this->tl($ciro) . ' ciro gerçekleşmiş, ' . $adet . ' adisyon kapanmış.';
        if ($adet > 0) $cevap .= ' Adisyon ortalaması ' . $this->tl($ciro / $adet) . '.';
        return $this->cvp('ciro', $cevap, $niyet, ['tip' => 'ciro', 'baslik' => 'Ciro · ' . ucfirst($d), 'ciro' => $ciro, 'adet' => $adet]);
    }

    protected function cvGarson($from, $to, $d, $niyet)
    {
        $liste = DB::table('adisyonlar')->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->select('personeller.ad', DB::raw('COUNT(*) as adet'), DB::raw('SUM(adisyonlar.toplam) as ciro'))
            ->groupBy('personeller.id', 'personeller.ad')->orderByDesc('ciro')->get();
        if ($liste->isEmpty()) return $this->paket_('garson', ucfirst($d) . ' için henüz bir personel satışı görünmüyor.', $niyet);
        $norm = $this->normalize($niyet['ham']);
        $dusuk = strpos($norm, 'en dusuk') !== false || strpos($norm, 'en az') !== false || strpos($norm, 'en kotu') !== false;
        if ($dusuk) {
            $p = $liste->last();
            $cevap = ucfirst($d) . ' en düşük performans ' . $p->ad . ' tarafında; ' . $this->tl((float) $p->ciro) . ' ciro, ' . (int) $p->adet . ' adisyon.';
            return $this->cvp('garson', $cevap, $niyet, ['tip' => 'garson', 'baslik' => 'Personel · ' . ucfirst($d), 'satirlar' => $liste]);
        }
        $ilk3 = $liste->take(3);
        $siralar = ['ilk sırada', 'ikinci sırada', 'üçüncü sırada'];
        $parca = [];
        foreach ($ilk3->values() as $i => $p) $parca[] = $siralar[$i] . ' ' . $p->ad . ', ' . $this->tl((float) $p->ciro);
        $cevap = ucfirst($d) . ' en çok satan personeller; ' . implode(', ', $parca) . '.';
        return $this->cvp('garson', $cevap, $niyet, ['tip' => 'garson', 'baslik' => 'Personel · ' . ucfirst($d), 'satirlar' => $liste]);
    }

    protected function cvUrun($from, $to, $d, $niyet)
    {
        $liste = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('urun_adi', DB::raw('SUM(adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as ciro'))
            ->groupBy('urun_adi')->orderByDesc('adet')->limit(10)->get();
        if ($liste->isEmpty()) return $this->paket_('urun', ucfirst($d) . ' için henüz bir ürün satışı görünmüyor.', $niyet);
        $ilk3 = $liste->take(3);
        $siralar = ['ilk sırada', 'ikinci sırada', 'üçüncü sırada'];
        $parca = [];
        foreach ($ilk3->values() as $i => $u) $parca[] = $siralar[$i] . ' ' . $u->urun_adi . ', ' . (int) $u->adet . ' adet';
        $cevap = ucfirst($d) . ' en çok satan ürünler; ' . implode(', ', $parca) . '.';
        return $this->cvp('urun', $cevap, $niyet, ['tip' => 'urun', 'baslik' => 'Ürün · ' . ucfirst($d), 'satirlar' => $liste]);
    }

    protected function cvMasa($d, $niyet)
    {
        $acik = DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')->count();
        $toplam = DB::table('masalar')->count();
        $tutar = (float) DB::table('adisyonlar')->where('durum', 'acik')->whereNotNull('masa_id')->sum('toplam');
        $cevap = $acik > 0
            ? 'Şu an ' . $acik . ' masa dolu (' . $toplam . ' masadan), açık adisyonlarda toplam ' . $this->tl($tutar) . ' bekliyor.'
            : 'Şu an açık masa yok, tüm masalar boş.';
        return $this->cvp('masa', $cevap, $niyet, ['tip' => 'masa', 'baslik' => 'Masalar', 'acik' => $acik, 'toplam' => $toplam, 'tutar' => $tutar]);
    }

    protected function cvPaket($d, $niyet)
    {
        $acik = DB::table('adisyonlar')->where('kanal', 'paket')->where('durum', 'acik')->count();
        $cevap = $acik > 0 ? 'Şu an ' . $acik . ' aktif paket/kurye siparişi var.' : 'Şu an aktif paket siparişi yok.';
        return $this->cvp('paket', $cevap, $niyet, ['tip' => 'paket', 'baslik' => 'Paket', 'acik' => $acik]);
    }

    protected function cvMaliyet($from, $to, $d, $niyet)
    {
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        $maliyet = 0.0;
        if (function_exists('_restoUrunMaliyetMap')) {
            $map = _restoUrunMaliyetMap();
            $satir = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
                ->select('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi', DB::raw('SUM(adet) as adet'), DB::raw('SUM(adisyon_kalemleri.tutar) as satis'))
                ->groupBy('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi')->get();
            foreach ($satir as $s) {
                $b = $map['id'][(int) $s->urun_id] ?? ($map['ad'][$s->urun_adi] ?? 0);
                $m = (float) $s->adet * (float) $b;
                if ($m <= 0 && $s->satis > 0) $m = (float) $s->satis * 0.30;
                $maliyet += $m;
            }
        } else {
            $maliyet = $ciro * 0.30;
        }
        $yuzde = $ciro > 0 ? round($maliyet / $ciro * 100) : 0;
        if ($ciro <= 0) return $this->paket_('maliyet', ucfirst($d) . ' için henüz satış yok, maliyet hesaplanamıyor.', $niyet);
        $cevap = ucfirst($d) . ' malzeme maliyeti yaklaşık ' . $this->tl($maliyet) . ', yani food-cost oranı %' . $yuzde
            . '. Brüt kâr ' . $this->tl($ciro - $maliyet) . '.';
        if ($yuzde >= 38) $cevap .= ' Bu oran biraz yüksek, reçete ve porsiyonları gözden geçirmekte fayda var.';
        return $this->cvp('maliyet', $cevap, $niyet, ['tip' => 'maliyet', 'baslik' => 'Food-Cost · ' . ucfirst($d), 'ciro' => $ciro, 'maliyet' => round($maliyet), 'yuzde' => $yuzde]);
    }

    protected function cvKayip($from, $to, $d, $niyet)
    {
        $q = fn () => DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to]);
        $iskonto = (float) $q()->sum('indirim');
        $ikram = (float) $q()->sum('ikram');
        $silinen = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyon_kalemleri.durum', 'iptal')->whereBetween('adisyonlar.acilis', [$from, $to])->sum('adisyon_kalemleri.tutar');
        $fire = 0.0;
        try {
            $fire = (float) DB::table('stok_hareketleri')->where('tip', 'fire')->whereBetween('created_at', [$from, $to])
                ->select(DB::raw('COALESCE(SUM(ABS(miktar)*birim_maliyet),0) as t'))->value('t');
        } catch (\Throwable $e) {
        }
        $iptal = (float) DB::table('adisyonlar')->where('durum', 'iptal')->whereBetween('acilis', [$from, $to])->sum('toplam');
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        $toplamKayip = $iskonto + $ikram + $silinen + $fire + $iptal;
        $cevap = ucfirst($d) . ' toplam sızıntı yaklaşık ' . $this->tl($toplamKayip) . '. '
            . 'İskonto ' . $this->tl($iskonto) . ', ikram ' . $this->tl($ikram) . ', silinen ürün ' . $this->tl($silinen)
            . ', iptal adisyon ' . $this->tl($iptal) . ', fire ' . $this->tl($fire) . '.';
        if ($ciro > 0 && $iskonto > $ciro * 0.05) $cevap .= ' İskonto oranı ciroya göre yüksek, dikkat etmekte fayda var.';
        return $this->cvp('kayip', $cevap, $niyet, ['tip' => 'kayip', 'baslik' => 'Kayıp Radarı · ' . ucfirst($d),
            'iskonto' => $iskonto, 'ikram' => $ikram, 'silinen' => $silinen, 'iptal' => $iptal, 'fire' => $fire, 'toplam' => $toplamKayip]);
    }

    protected function cvIptal($from, $to, $d, $niyet)
    {
        $q = DB::table('adisyonlar')->where('durum', 'iptal')->whereBetween('acilis', [$from, $to]);
        $adet = (clone $q)->count();
        $tutar = (float) (clone $q)->sum('toplam');
        $cevap = $adet > 0
            ? ucfirst($d) . ' toplam ' . $adet . ' adisyon iptal edilmiş, tutarı ' . $this->tl($tutar) . '.'
            : ucfirst($d) . ' iptal edilen adisyon yok, güzel.';
        return $this->cvp('iptal', $cevap, $niyet, ['tip' => 'iptal', 'baslik' => 'İptaller · ' . ucfirst($d), 'adet' => $adet, 'tutar' => $tutar]);
    }

    protected function cvMusteri($from, $to, $d, $niyet)
    {
        $misafir = (int) DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->sum('misafir_sayisi');
        $folyo = DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->count();
        if ($folyo === 0) return $this->paket_('musteri', ucfirst($d) . ' için henüz bir müşteri hareketi görünmüyor.', $niyet);
        $cevap = ucfirst($d) . ' toplam ' . $misafir . ' misafir ağırlanmış, ' . $folyo . ' adisyonda.';
        return $this->cvp('musteri', $cevap, $niyet, ['tip' => 'musteri', 'baslik' => 'Müşteri · ' . ucfirst($d), 'misafir' => $misafir, 'folyo' => $folyo]);
    }

    protected function cvOzet($from, $to, $d, $niyet)
    {
        $ciro = (float) DB::table('odemeler')->whereBetween('created_at', [$from, $to])->sum('tutar');
        $folyo = DB::table('adisyonlar')->where('durum', 'odendi')->whereBetween('kapanis', [$from, $to])->count();
        $topUrun = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('urun_adi', DB::raw('SUM(adet) as adet'))->groupBy('urun_adi')->orderByDesc('adet')->first();
        $topGarson = DB::table('adisyonlar')->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
            ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$from, $to])
            ->select('personeller.ad', DB::raw('SUM(adisyonlar.toplam) as ciro'))->groupBy('personeller.id', 'personeller.ad')->orderByDesc('ciro')->first();
        $acik = DB::table('adisyonlar')->where('durum', 'acik')->count();
        $cumleler = [];
        $cumleler[] = $ciro > 0 ? (ucfirst($d) . ' toplam ' . $this->tl($ciro) . ' ciro, ' . $folyo . ' adisyon') : (ucfirst($d) . ' henüz satış görünmüyor');
        if ($topUrun) $cumleler[] = 'En çok satan ' . $topUrun->urun_adi . ' (' . (int) $topUrun->adet . ' adet)';
        if ($topGarson) $cumleler[] = 'En çok satan personel ' . $topGarson->ad;
        if ($acik > 0) $cumleler[] = $acik . ' masa hâlâ açık';
        $yorum = $ciro > 0 ? ['Gün güzel gidiyor, eline sağlık.', 'İşler yolunda, aynen devam.', 'Bereketli görünüyor.'][array_rand([0, 1, 2])] : 'Gün yeni, bereketli olsun.';
        $cevap = implode('. ', $cumleler) . '. ' . $yorum;
        return $this->cvp('ozet', $cevap, $niyet, ['tip' => 'ozet', 'baslik' => 'Özet · ' . ucfirst($d), 'ciro' => $ciro, 'folyo' => $folyo, 'acik' => $acik]);
    }

    // -------------------- KALIP KUTUPHANESI (bedava soru-cevap) --------------------
    protected function _kaliplar()
    {
        try {
            // Cache'e DUZ DIZI koy (stdClass cache'te "incomplete object"e donusup patlayabiliyor)
            return Cache::remember('resto_kalip_liste_v2', now()->addMinutes(5), function () {
                if (!Schema::hasTable('asistan_kalip')) return [];
                return DB::table('asistan_kalip')->where('aktif', 1)->select('id', 'tetikleyiciler', 'cevap')->get()
                    ->map(fn ($x) => ['id' => (int) $x->id, 'tetikleyiciler' => (string) $x->tetikleyiciler, 'cevap' => (string) $x->cevap])->all();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Kalip kutuphanesinden BEDAVA cevap; yoksa null. AI'dan ONCE cagirilir.
     *  TURKCE EK TOLERANSLI: "cay" tetigi "cayim/caydan/caylar"i da yakalar. En UZUN eslesme kazanir. */
    public function kalipCevabi($metin)
    {
        $liste = $this->_kaliplar();
        if (empty($liste)) return null;
        $n = ' ' . $this->normalize($metin) . ' ';
        $enIyi = null;
        $enSkor = 0;
        foreach ($liste as $k) {
            // Hem dizi (yeni cache) hem stdClass (eski) ile calis
            $tetik = is_array($k) ? ($k['tetikleyiciler'] ?? '') : ($k->tetikleyiciler ?? '');
            foreach (preg_split('/[\r\n,;]+/', (string) $tetik) as $t) {
                $t = trim($this->normalize($t));
                if (mb_strlen($t) < 2) continue;
                if (preg_match('/(?:^| )' . preg_quote($t, '/') . '[a-z]*(?= |$)/u', $n)) {
                    $skor = mb_strlen($t);
                    if ($skor > $enSkor) { $enSkor = $skor; $enIyi = $k; }
                }
            }
        }
        if (!$enIyi) return null;
        $eId = is_array($enIyi) ? ($enIyi['id'] ?? 0) : ($enIyi->id ?? 0);
        $eCevap = is_array($enIyi) ? ($enIyi['cevap'] ?? '') : ($enIyi->cevap ?? '');
        try { DB::table('asistan_kalip')->where('id', $eId)->increment('kullanim_sayisi'); } catch (\Throwable $e) {}
        return ['basarili' => true, 'intent' => 'kalip', 'seslendir' => true, 'cevap' => $this->_cevapSec($eCevap), 'kart' => null];
    }

    /** Cevap havuzu ("---" ile ayrilmis) -> rastgele biri (cesitlilik). */
    protected function _cevapSec($cevap)
    {
        $c = (string) $cevap;
        if (strpos($c, '---') === false) return trim($c);
        $parcalar = array_values(array_filter(array_map('trim', preg_split('/^\s*-{3,}\s*$/m', $c)), fn ($p) => $p !== ''));
        return empty($parcalar) ? trim($c) : $parcalar[array_rand($parcalar)];
    }

    // -------------------- OGRENEN ONBELLEK + GECMIS --------------------
    public function ogrenilenNiyet($metin)
    {
        try {
            $v = Cache::get('resto_asistan_ogr:' . md5($this->normalize($metin)));
            if (is_array($v) && !empty($v['intent'])) {
                // Donemi HER ZAMAN metinden taze coz (eski yanlis donem takili kalmasin)
                $d = $this->donemCoz($this->normalize($metin));
                $v['ham'] = trim((string) $metin);
                $v['donem'] = $d['anahtar'];
                $v['donemAdi'] = $d['ad'];
                return $v;
            }
        } catch (\Throwable $e) {
        }
        return null;
    }

    public function ogren($metin, array $niyet)
    {
        if (($niyet['intent'] ?? 'bilinmiyor') === 'bilinmiyor') return;
        try {
            Cache::forever('resto_asistan_ogr:' . md5($this->normalize($metin)), [
                'intent' => $niyet['intent'], 'donem' => $niyet['donem'] ?? 'gunluk', 'donemAdi' => $niyet['donemAdi'] ?? 'bugün',
            ]);
        } catch (\Throwable $e) {
        }
    }

    public function gecmisGetir($userId)
    {
        try { $v = Cache::get('resto_asistan_gecmis:' . (int) $userId); if (is_array($v)) return $v; } catch (\Throwable $e) {}
        return [];
    }

    public function gecmisEkle($userId, $soru, $cevap)
    {
        $soru = trim((string) $soru); $cevap = trim((string) $cevap);
        if ($soru === '' || $cevap === '') return;
        try {
            $g = $this->gecmisGetir($userId);
            $g[] = ['role' => 'user', 'content' => mb_substr($soru, 0, 400)];
            $g[] = ['role' => 'assistant', 'content' => mb_substr($cevap, 0, 600)];
            if (count($g) > 6) $g = array_slice($g, -6);
            Cache::put('resto_asistan_gecmis:' . (int) $userId, $g, now()->addMinutes(30));
        } catch (\Throwable $e) {
        }
    }

    // -------------------- HAIKU (niyet + sohbet) --------------------
    protected function apiKey()
    {
        return config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
    }

    protected function model()
    {
        return config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001';
    }

    /** Haiku ile niyet cozumu (tool). Rakam URETMEZ; sadece intent+donem. */
    public function niyetCozAI($metin, $gecmis = [])
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) { $this->aiTeshis = 'anahtar_yok'; return null; }
        $ham = trim((string) $metin);
        $tools = [[
            'name' => 'rapor_sec', 'description' => 'Patronun sorusuna gore hangi restoran raporunu hangi donem icin getirecegini secer.',
            'input_schema' => ['type' => 'object', 'properties' => [
                'intent' => ['type' => 'string', 'enum' => ['ciro', 'garson', 'urun', 'masa', 'paket', 'maliyet', 'kayip', 'iptal', 'musteri', 'ozet', 'finans', 'satinalma', 'maas', 'stok', 'gider', 'sohbet', 'bilinmiyor'],
                    'description' => 'ciro=kasa/hasilat/tahsilat, garson=kim ne satti, urun=en cok satan yemek, masa=acik/dolu masa, paket=paket/kurye siparis, maliyet=food-cost/brut kar, kayip=iskonto/ikram/fire/silinen sizinti, iptal=iptal adisyon, musteri=misafir sayisi, ozet=genel gun sonu, finans=net kar/zarar veya gelir-gider dengesi, satinalma=alis faturasi/tedarikci/malzeme fiyat artisi, maas=personel maas/avans/prim/hakedis, stok=malzeme stok durumu/kritik stok/stok degeri, gider=isletme gideri kira/fatura/masraf, sohbet=selam/kimlik/genel, bilinmiyor=anlasilamadi'],
                'donem' => ['type' => 'string', 'enum' => ['gunluk', 'haftalik', 'aylik', 'yillik'], 'description' => 'gunluk=bugun, haftalik=bu hafta, aylik=bu ay, yillik=bu yil'],
            ], 'required' => ['intent', 'donem']],
        ]];
        $govde = [
            'model' => $this->model(), 'max_tokens' => 200, 'tool_choice' => ['type' => 'tool', 'name' => 'rapor_sec'], 'tools' => $tools,
            'system' => 'Sen bir RESTORAN isletme yonetim panelinin asistanisin. Patronun Turkce (argo/yarim cumle olabilir) sorusunu oku ve rapor_sec aracini cagir. Takip sorularini onceki konusmaya gore yorumla. Rakam URETME, sadece araci cagir.',
            'messages' => array_merge($this->gecmisMesajlari($gecmis), [['role' => 'user', 'content' => $ham]]),
        ];
        $data = $this->cagir($govde);
        if (!$data || empty($data['content'])) { return null; }
        $tool = null;
        foreach ($data['content'] as $b) {
            if (($b['type'] ?? '') === 'tool_use' && ($b['name'] ?? '') === 'rapor_sec') { $tool = $b['input'] ?? null; break; }
        }
        if (!is_array($tool) || empty($tool['intent'])) { $this->aiTeshis = 'tool_yok'; return null; }
        $donem = in_array($tool['donem'] ?? '', ['gunluk', 'haftalik', 'aylik', 'yillik'], true) ? $tool['donem'] : 'gunluk';
        $donemAdi = ['gunluk' => 'bugün', 'haftalik' => 'bu hafta', 'aylik' => 'bu ay', 'yillik' => 'bu yıl'][$donem];
        $this->aiTeshis = 'ok';
        return ['intent' => $tool['intent'], 'donem' => $donem, 'donemAdi' => $donemAdi, 'ham' => $ham, '_kaynak' => 'ai'];
    }

    /** Haiku ile dogal sohbet cevabi (TTS icin duz metin). Rakam uydurmaz. */
    public function sohbetAI($metin, $gecmis = [])
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) { $this->aiTeshis = 'anahtar_yok'; return null; }
        $ham = trim((string) $metin);
        if ($ham === '') return null;
        $sistem = 'Sen bir RESTORAN patronunun EN YAKIN IS ARKADASISIN; onu koruyup kollayan, samimi ve guvenilir bir dost gibisin. '
            . 'Adin yok, kendini "restoranınızın asistanı" diye tanit. Patronla isleri hakkinda ARKADAS gibi sohbet edebilirsin; moral ver, yol goster. '
            . 'Kisa (en fazla iki-uc cumle), sicak ve NET konus; mumkunse kucuk bir yorum ya da oneri kat ama ABARTMA. TTS ile seslendirilecegin icin DUZ yaz: emoji, madde, yildiz, tirnak KULLANMA. '
            . 'Yapabildiklerin: ciro/kasa, en cok satan urun, personel performansi, acik masalar, paket siparisler, food-cost/kar, kayip radari (iskonto/ikram/fire), '
            . 'iptaller, misafir sayisi, gunluk ozet, net kar-zarar (finans), stok durumu ve kritik malzeme, personel maas/avans/prim, isletme giderleri ve satin alma/tedarikci/malzeme fiyat artisi. '
            . 'RAKAM veya VERI UYDURMA; patron rakam isterse "bugun ciro ne kadar diye sorabilirsiniz" de. '
            . 'Restoranla ilgisiz konularda nazikce restoranıyla yardimci olabilecegini soyle. "Buyurun" kelimesini KULLANMA. Sadece Turkce yanit ver.';
        $mesajlar = $this->gecmisMesajlari($gecmis);
        $mesajlar[] = ['role' => 'user', 'content' => $ham];
        $govde = ['model' => $this->model(), 'max_tokens' => 200, 'system' => $sistem, 'messages' => $mesajlar];
        $data = $this->cagir($govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if ($t === '') { $this->aiTeshis = 'metin_bos'; return null; }
        $this->aiTeshis = 'ok_sohbet';
        return $t;
    }

    protected function cagir($govde)
    {
        try {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 14,
                CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $this->apiKey(), 'anthropic-version: 2023-06-01'],
                CURLOPT_POSTFIELDS => json_encode($govde, JSON_UNESCAPED_UNICODE),
            ]);
            $yanit = curl_exec($ch);
            $kod = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($yanit === false || $kod !== 200) {
                $this->aiTeshis = $yanit === false ? ('curl_HATA: ' . $err) : ('http_' . $kod);
                return null;
            }
            return json_decode($yanit, true);
        } catch (\Throwable $e) {
            $this->aiTeshis = 'exception: ' . $e->getMessage();
            return null;
        }
    }

    protected function gecmisMesajlari($gecmis)
    {
        if (!is_array($gecmis) || empty($gecmis)) return [];
        $out = [];
        foreach ($gecmis as $m) {
            $rol = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $ic = trim((string) ($m['content'] ?? ''));
            if ($ic === '') continue;
            if (empty($out) && $rol !== 'user') continue;
            $out[] = ['role' => $rol, 'content' => $ic];
        }
        return $out;
    }

    // -------------------- YARDIMCILAR --------------------
    protected function cvp($intent, $cevap, $niyet, $kart = null)
    {
        return ['basarili' => true, 'intent' => $intent, 'cevap' => $cevap, 'seslendir' => true, 'kart' => $kart, 'niyet' => $niyet];
    }

    protected function paket_($intent, $metin, $niyet)
    {
        return ['basarili' => true, 'intent' => $intent, 'cevap' => $metin, 'seslendir' => true, 'kart' => null, 'niyet' => $niyet];
    }

    public function yardimCevabi($niyet = [])
    {
        $c = 'Restoranınızın asistanıyım. Ciro, en çok satan ürün, personel performansı, açık masalar, paket siparişler, '
            . 'food-cost, kayıp radarı, net kâr-zarar, stok durumu, personel maaş ve avansları, giderler ve satın alma için sorabilirsiniz. '
            . 'Örneğin: bu ay kâr mı ettim, stokta kritik malzeme var mı, bu ay giderim ne kadar, en çok hangi tedarikçiden aldım.';
        return ['basarili' => true, 'intent' => 'yardim', 'cevap' => $c, 'seslendir' => true, 'kart' => null, 'niyet' => $niyet];
    }

    /**
     * PROAKTIF TESPITLER — patronun GÖREMEDIGI yerleri tarar (kaçak/risk/fırsat).
     * Asistan açılışta sormadan sunar: "en yakın iş arkadaşı" mantığı; her tespit
     * samimi yorum + somut öneri içerir. Hepsi gerçek veriden, rakam uydurmaz.
     */
    public function tespitler($subeId)
    {
        $t = [];
        $now = now();
        $buBas = (clone $now)->subDays(7);
        $onBas = (clone $now)->subDays(14);
        $pick = fn ($a) => $a[array_rand($a)];

        // 1) Ciro trendi (bu hafta vs geçen hafta)
        try {
            $bu = (float) DB::table('odemeler')->whereBetween('created_at', [$buBas, $now])->sum('tutar');
            $on = (float) DB::table('odemeler')->whereBetween('created_at', [$onBas, $buBas])->sum('tutar');
            if ($on > 0) {
                $deg = (int) round(($bu - $on) / $on * 100);
                if ($deg <= -10) {
                    $t[] = ['seviye' => 'risk', 'baslik' => 'Ciro düşüşte',
                        'mesaj' => 'Bu hafta ciro geçen haftaya göre yüzde ' . abs($deg) . ' düşmüş. Geçen hafta ' . $this->tl($on) . ' iken bu hafta ' . $this->tl($bu) . ' olmuş. Menüde, serviste ya da yoğun saatlerde ne değiştiğine birlikte bakalım istersen.',
                        'kv' => [['k' => 'Geçen hafta', 'v' => $this->tl($on)], ['k' => 'Bu hafta', 'v' => $this->tl($bu)]]];
                } elseif ($deg >= 12) {
                    $t[] = ['seviye' => 'iyi', 'baslik' => 'Ciro artışta',
                        'mesaj' => 'Güzel haber; bu hafta ciro geçen haftaya göre %' . $deg . ' artmış. Neyi doğru yaptıysan aynen devam, eline sağlık.',
                        'kv' => [['k' => 'Geçen hafta', 'v' => $this->tl($on)], ['k' => 'Bu hafta', 'v' => $this->tl($bu)]]];
                }
            }
        } catch (\Throwable $e) {
        }

        // 2) Personel suistimal radar (yüksek ikram/iskonto/iptal)
        try {
            $rows = DB::table('iptal_indirim_loglari')->join('personeller', 'iptal_indirim_loglari.personel_id', '=', 'personeller.id')
                ->where('iptal_indirim_loglari.sube_id', $subeId)->whereBetween('iptal_indirim_loglari.created_at', [$buBas, $now])
                ->groupBy('personeller.id', 'personeller.ad')->select('personeller.ad', DB::raw('SUM(iptal_indirim_loglari.tutar) t'))
                ->orderByDesc('t')->get();
            if ($rows->count() >= 2) {
                $top = $rows->first();
                $ort = (float) $rows->avg('t');
                if ((float) $top->t > 300 && (float) $top->t > $ort * 1.8) {
                    $t[] = ['seviye' => 'risk', 'baslik' => 'Personelde göze batan hareket',
                        'mesaj' => $top->ad . ' bu hafta ' . $this->tl((float) $top->t) . ' iskonto/ikram/iptal yapmış — ekibin ortalamasının belirgin üstünde. Kötü niyet demiyorum ama seni korumak benim işim; bir sohbet etmekte fayda var.',
                        'kv' => [['k' => $top->ad, 'v' => $this->tl((float) $top->t)], ['k' => 'Ekip ort.', 'v' => $this->tl($ort)]]];
                }
            }
        } catch (\Throwable $e) {
        }

        // 3) Maliyeti yiyen zamlar (kırmızı uyarılı alış kalemleri, bu ay)
        try {
            $zam = DB::table('alis_fatura_kalemleri')->join('alis_faturalari', 'alis_fatura_kalemleri.fatura_id', '=', 'alis_faturalari.id')
                ->join('malzemeler', 'alis_fatura_kalemleri.malzeme_id', '=', 'malzemeler.id')
                ->where('alis_faturalari.sube_id', $subeId)->where('alis_faturalari.tarih', '>=', (clone $now)->subDays(30)->toDateString())
                ->where('alis_fatura_kalemleri.uyari', 'kirmizi')
                ->select('malzemeler.ad', 'alis_fatura_kalemleri.fiyat_farki_yuzde')->orderByDesc('alis_fatura_kalemleri.fiyat_farki_yuzde')->limit(3)->get();
            if ($zam->isNotEmpty()) {
                $isim = []; $kv = [];
                foreach ($zam as $z) { $isim[] = $z->ad . ' %' . round((float) $z->fiyat_farki_yuzde); $kv[] = ['k' => $z->ad, 'v' => '%' . round((float) $z->fiyat_farki_yuzde) . ' zam']; }
                $t[] = ['seviye' => 'risk', 'baslik' => 'Maliyetini yiyen zamlar',
                    'mesaj' => 'Bu ay şu malzemelere ciddi zam gelmiş: ' . implode(', ', $isim) . '. Sen farkında olmadan kârın eriyor; ya tedarikçiyle konuş ya da menü fiyatını güncelle.',
                    'kv' => $kv];
            }
        } catch (\Throwable $e) {
        }

        // 4) Kritik stok
        try {
            $mevcut = DB::table('stok_hareketleri')->where('sube_id', $subeId)->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
            $krit = [];
            foreach (DB::table('malzemeler')->get(['id', 'ad', 'kritik_stok', 'stok_takipli']) as $m) {
                if (!$m->stok_takipli || (float) $m->kritik_stok <= 0) continue;
                if ((float) ($mevcut[$m->id] ?? 0) <= (float) $m->kritik_stok) $krit[] = $m->ad;
            }
            if (count($krit) > 0) {
                $t[] = ['seviye' => 'uyari', 'baslik' => 'Bitmek üzere olan stok',
                    'mesaj' => count($krit) . ' malzeme kritik seviyede: ' . implode(', ', array_slice($krit, 0, 5)) . '. Servisin ortasında bitmesin, bugün sipariş vermene bakalım.',
                    'kv' => [['k' => 'Kritik malzeme', 'v' => (string) count($krit)]]];
            }
        } catch (\Throwable $e) {
        }

        // 5/6) Ürün kârlılığı: "çok satıyor ama kârsız" + "az satan gizli kârlı"
        try {
            if (function_exists('_restoUrunMaliyetMap')) {
                $map = _restoUrunMaliyetMap();
                $sat = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                    ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$buBas, $now])->where('adisyon_kalemleri.durum', '!=', 'iptal')
                    ->groupBy('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi')
                    ->select('adisyon_kalemleri.urun_id', 'adisyon_kalemleri.urun_adi', DB::raw('SUM(adet) adet'), DB::raw('SUM(adisyon_kalemleri.tutar) ciro'))->get();
                if ($sat->count() > 0) {
                    $ortAdet = (float) $sat->avg('adet');
                    $dusuk = null; $firsat = null;
                    foreach ($sat as $s) {
                        $mal = $map['id'][(int) $s->urun_id] ?? ($map['ad'][$s->urun_adi] ?? 0);
                        $fiyat = $s->adet > 0 ? (float) $s->ciro / (float) $s->adet : 0;
                        if ($mal <= 0 || $fiyat <= 0) continue;
                        $fc = (int) round($mal / $fiyat * 100);
                        if ($fc >= 42 && (float) $s->adet >= $ortAdet && (!$dusuk || (float) $s->ciro > $dusuk['ciro'])) $dusuk = ['ad' => $s->urun_adi, 'fc' => $fc, 'ciro' => (float) $s->ciro];
                        if ($fc > 0 && $fc <= 24 && (float) $s->adet <= $ortAdet * 0.6 && (!$firsat || $fc < $firsat['fc'])) $firsat = ['ad' => $s->urun_adi, 'fc' => $fc, 'adet' => (int) $s->adet];
                    }
                    if ($dusuk) $t[] = ['seviye' => 'risk', 'baslik' => 'Çok satıyor ama kâr yok',
                        'mesaj' => $dusuk['ad'] . ' bu hafta çok satmış ama food-cost’u %' . $dusuk['fc'] . ' — neredeyse kârsız çalışıyorsun. Porsiyonu ya da fiyatı ayarlamazsan sattıkça yoruluyorsun, para kalmıyor.',
                        'kv' => [['k' => $dusuk['ad'], 'v' => 'food-cost %' . $dusuk['fc']]]];
                    if ($firsat) $t[] = ['seviye' => 'firsat', 'baslik' => 'Gizli para: az itilen kârlı ürün',
                        'mesaj' => $firsat['ad'] . ' çok kârlı (food-cost sadece %' . $firsat['fc'] . ') ama az satıyor (' . $firsat['adet'] . ' adet). Garsonlara bunu önerdir, masaya ilk bunu taşısınlar — direkt cebine kâr.',
                        'kv' => [['k' => $firsat['ad'], 'v' => 'food-cost %' . $firsat['fc']]]];
                }
            }
        } catch (\Throwable $e) {
        }

        // 7) Uzun süredir açık masa
        try {
            $eski = DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'acik')->whereNotNull('masa_id')->where('acilis', '<', (clone $now)->subHours(4))->count();
            if ($eski > 0) $t[] = ['seviye' => 'uyari', 'baslik' => 'Saatlerdir açık masa',
                'mesaj' => $eski . ' masa 4 saatten uzundur açık. Ya hesap alınmadı ya unutuldu; kaçan ciro olmasın, bir kontrol ettir.',
                'kv' => [['k' => 'Açık masa', 'v' => (string) $eski]]];
        } catch (\Throwable $e) {
        }

        // 8) Avans yükü
        try {
            if (Schema::hasTable('personel_hareketleri')) {
                $ayBas = (clone $now)->startOfMonth()->toDateString();
                $agir = [];
                foreach (DB::table('personeller')->where('sube_id', $subeId)->where('aktif', 1)->where('maas', '>', 0)->get(['id', 'ad', 'maas']) as $p) {
                    $av = (float) DB::table('personel_hareketleri')->where('personel_id', $p->id)->where('tur', 'avans')->where('tarih', '>=', $ayBas)->sum('tutar');
                    if ($av > (float) $p->maas * 0.4) $agir[] = $p->ad . ' (' . $this->tl($av) . ')';
                }
                if (count($agir) > 0) $t[] = ['seviye' => 'uyari', 'baslik' => 'Avans yükü birikmiş',
                    'mesaj' => 'Şu kişiler maaşının önemli kısmını avans almış: ' . implode(', ', array_slice($agir, 0, 4)) . '. Maaş gününde nakit sıkışmayasın diye şimdiden hatırlatıyorum.',
                    'kv' => null];
            }
        } catch (\Throwable $e) {
        }

        // Sırala: risk > firsat > uyari > iyi
        $agirlik = ['risk' => 0, 'firsat' => 1, 'uyari' => 2, 'iyi' => 3, 'bilgi' => 4];
        usort($t, fn ($a, $b) => ($agirlik[$a['seviye']] ?? 9) <=> ($agirlik[$b['seviye']] ?? 9));
        $t = array_slice($t, 0, 6);

        $risk = count(array_filter($t, fn ($x) => $x['seviye'] === 'risk'));
        if (empty($t)) {
            $selam = $pick([
                'İşler yolunda, göze batan bir sorun görmüyorum. Yine de aklına takılanı sor, birlikte bakalım.',
                'Şu an tabloda seni üzecek bir şey yok. İstersen ciro, kâr ya da stok hakkında konuşalım.',
            ]);
        } elseif ($risk > 0) {
            $selam = $pick([
                'Gözüne çarpmayan ' . count($t) . ' şey buldum, birkaçı önemli. Bak istersen.',
                'Otur bir çayını al; senin adına ' . count($t) . ' konuya göz attım, ' . $risk . ' tanesi dikkat ister.',
            ]);
        } else {
            $selam = 'Genel tablo iyi ama ' . count($t) . ' küçük not var, göz gezdir istersen.';
        }
        return ['selam' => $selam, 'tespitler' => array_values($t)];
    }

    protected function tl($v)
    {
        return number_format((float) $v, 0, ',', '.') . 'TL';
    }

    protected function normalize($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
        $s = strtr($s, $tr);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }

    // ==================== PATRON AI KARAKTER (işletme ortağı) ====================
    /**
     * Karakterin üzerinde AKIL YÜRÜTECEĞİ gerçek veri brifingi. Rakam BURADA
     * hesaplanır (LLM uydurmaz, sadece bunu yorumlar). 60 sn önbellek.
     */
    public function patronBrief($subeId)
    {
        try {
            return Cache::remember('resto_patron_brief:' . (int) $subeId, now()->addSeconds(60), function () use ($subeId) {
                $now = now();
                $g0 = today()->startOfDay();
                $h0 = (clone $now)->subDays(7);
                $h1 = (clone $now)->subDays(14);
                $b = ['tarih' => $now->format('d.m.Y H:i')];

                // Ciro: bugün / bu hafta / geçen hafta
                $ciroBu = (float) DB::table('odemeler')->whereBetween('created_at', [$h0, $now])->sum('tutar');
                $ciroOn = (float) DB::table('odemeler')->whereBetween('created_at', [$h1, $h0])->sum('tutar');
                $b['ciro_bugun'] = round((float) DB::table('odemeler')->where('created_at', '>=', $g0)->sum('tutar'));
                $b['ciro_bu_hafta'] = round($ciroBu);
                $b['ciro_gecen_hafta'] = round($ciroOn);
                if ($ciroOn > 0) $b['ciro_degisim_yuzde'] = (int) round(($ciroBu - $ciroOn) / $ciroOn * 100);

                // Misafir + kişi başı harcama (bu hafta vs geçen)
                $mBu = (int) DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'odendi')->whereBetween('kapanis', [$h0, $now])->sum('misafir_sayisi');
                $mOn = (int) DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'odendi')->whereBetween('kapanis', [$h1, $h0])->sum('misafir_sayisi');
                $b['misafir_bu_hafta'] = $mBu;
                $b['misafir_gecen_hafta'] = $mOn;
                $b['kisi_basi_bu_hafta'] = $mBu > 0 ? round($ciroBu / $mBu) : 0;
                $b['kisi_basi_gecen_hafta'] = $mOn > 0 ? round($ciroOn / $mOn) : 0;

                // Vardiya kırılımı (öğle <17:00, akşam >=17:00) bu hafta
                try {
                    $ogle = (float) DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'odendi')->whereBetween('kapanis', [$h0, $now])->whereRaw('HOUR(kapanis) < 17')->sum('toplam');
                    $aksam = (float) DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'odendi')->whereBetween('kapanis', [$h0, $now])->whereRaw('HOUR(kapanis) >= 17')->sum('toplam');
                    $b['vardiya_bu_hafta'] = ['ogle' => round($ogle), 'aksam' => round($aksam)];
                } catch (\Throwable $e) {
                }

                // Ek ürün (içecek/tatlı/bar/kahve) payı — kaçan fırsat sinyali
                $ekUrun = [];
                try {
                    $ekKat = DB::table('kategoriler')->where(function ($q) {
                        foreach (['icecek', 'içecek', 'tatli', 'tatlı', 'bar', 'kahve', 'kokteyl', 'meşrubat', 'mesrubat'] as $k) $q->orWhereRaw('LOWER(ad) LIKE ?', ['%' . mb_strtolower($k) . '%']);
                    })->pluck('id')->toArray();
                    if ($ekKat) {
                        $ekUrun = DB::table('urunler')->whereIn('kategori_id', $ekKat)->pluck('id')->toArray();
                        if ($ekUrun) {
                            $ek = fn ($f, $t) => (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                                ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$f, $t])->where('adisyon_kalemleri.durum', '!=', 'iptal')
                                ->whereIn('adisyon_kalemleri.urun_id', $ekUrun)->sum('adisyon_kalemleri.tutar');
                            $ekBu = $ek($h0, $now); $ekOn = $ek($h1, $h0);
                            $b['ek_urun_pay_bu_hafta'] = $ciroBu > 0 ? round($ekBu / $ciroBu * 100) : 0;
                            $b['ek_urun_pay_gecen_hafta'] = $ciroOn > 0 ? round($ekOn / $ciroOn * 100) : 0;
                        }
                    }
                } catch (\Throwable $e) {
                }

                // Personel kırılımı (bu hafta): ciro, adisyon, kişi başı, ek ürün payı
                try {
                    $rows = DB::table('adisyonlar')->join('personeller', 'adisyonlar.acan_personel_id', '=', 'personeller.id')
                        ->where('adisyonlar.sube_id', $subeId)->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$h0, $now])
                        ->groupBy('personeller.id', 'personeller.ad')
                        ->select('personeller.id', 'personeller.ad', DB::raw('COUNT(*) adisyon'), DB::raw('SUM(adisyonlar.toplam) ciro'), DB::raw('SUM(adisyonlar.misafir_sayisi) misafir'))
                        ->orderByDesc('ciro')->limit(12)->get();
                    $per = [];
                    foreach ($rows as $r) {
                        $sat = ['ad' => $r->ad, 'ciro' => round((float) $r->ciro), 'adisyon' => (int) $r->adisyon, 'kisi_basi' => $r->misafir > 0 ? round((float) $r->ciro / $r->misafir) : 0];
                        if ($ekUrun) {
                            $pe = (float) DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                                ->where('adisyonlar.acan_personel_id', $r->id)->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$h0, $now])
                                ->where('adisyon_kalemleri.durum', '!=', 'iptal')->whereIn('adisyon_kalemleri.urun_id', $ekUrun)->sum('adisyon_kalemleri.tutar');
                            $sat['ek_urun_pay'] = (float) $r->ciro > 0 ? round($pe / (float) $r->ciro * 100) : 0;
                        }
                        $per[] = $sat;
                    }
                    $b['personel_bu_hafta'] = $per;
                } catch (\Throwable $e) {
                }

                // Kayıp özet (bu hafta) + en çok yapan personel
                try {
                    $q = fn () => DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'odendi')->whereBetween('kapanis', [$h0, $now]);
                    $b['kayip_bu_hafta'] = ['iskonto' => round((float) $q()->sum('indirim')), 'ikram' => round((float) $q()->sum('ikram'))];
                    $suz = DB::table('iptal_indirim_loglari')->join('personeller', 'iptal_indirim_loglari.personel_id', '=', 'personeller.id')
                        ->where('iptal_indirim_loglari.sube_id', $subeId)->whereBetween('iptal_indirim_loglari.created_at', [$h0, $now])
                        ->groupBy('personeller.id', 'personeller.ad')->select('personeller.ad', DB::raw('SUM(iptal_indirim_loglari.tutar) t'))->orderByDesc('t')->limit(3)->get();
                    if ($suz->isNotEmpty()) $b['kayip_personel'] = $suz->map(fn ($x) => ['ad' => $x->ad, 'tutar' => round((float) $x->t)])->all();
                } catch (\Throwable $e) {
                }

                // Ürün: en çok satan 5 + kârlılık uçları
                try {
                    $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
                        ->where('adisyonlar.durum', 'odendi')->whereBetween('adisyonlar.kapanis', [$h0, $now])->where('adisyon_kalemleri.durum', '!=', 'iptal')
                        ->groupBy('adisyon_kalemleri.urun_adi')->select('adisyon_kalemleri.urun_adi', DB::raw('SUM(adet) adet'))->orderByDesc('adet')->limit(5)->get();
                    $b['en_cok_satan'] = $top->map(fn ($x) => ['urun' => $x->urun_adi, 'adet' => (int) $x->adet])->all();
                } catch (\Throwable $e) {
                }

                // Stok kritik
                try {
                    $mevcut = DB::table('stok_hareketleri')->where('sube_id', $subeId)->selectRaw('malzeme_id, SUM(miktar) m')->groupBy('malzeme_id')->pluck('m', 'malzeme_id');
                    $krit = [];
                    foreach (DB::table('malzemeler')->get(['id', 'ad', 'kritik_stok', 'stok_takipli']) as $m) {
                        if (!$m->stok_takipli || (float) $m->kritik_stok <= 0) continue;
                        if ((float) ($mevcut[$m->id] ?? 0) <= (float) $m->kritik_stok) $krit[] = $m->ad;
                    }
                    if ($krit) $b['kritik_stok'] = array_slice($krit, 0, 8);
                } catch (\Throwable $e) {
                }

                // Finans (bu ay net)
                try {
                    $ayBas = (clone $now)->startOfMonth();
                    $gelir = (float) DB::table('odemeler')->whereBetween('created_at', [$ayBas, $now])->sum('tutar');
                    $gider = Schema::hasTable('giderler') ? (float) DB::table('giderler')->where('sube_id', $subeId)->where('tarih', '>=', $ayBas->toDateString())->sum('tutar') : 0;
                    $b['finans_bu_ay'] = ['gelir' => round($gelir), 'gider' => round($gider), 'net' => round($gelir - $gider)];
                } catch (\Throwable $e) {
                }

                // Açık masa
                try {
                    $b['acik_masa'] = (int) DB::table('adisyonlar')->where('sube_id', $subeId)->where('durum', 'acik')->whereNotNull('masa_id')->count();
                } catch (\Throwable $e) {
                }

                // Proaktif tespitler (başlık + mesaj)
                try {
                    $t = $this->tespitler($subeId);
                    $b['tespitler'] = array_map(fn ($x) => ['seviye' => $x['seviye'], 'baslik' => $x['baslik'], 'mesaj' => $x['mesaj']], $t['tespitler']);
                } catch (\Throwable $e) {
                }

                return $b;
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Soru "bir terslik/sorun/fırsat var mı, sence nasıl" gibi YORUM isteyen bir soru mu?
     *  Kelime listesine takılmadan geniş yakalar -> tespit motoruna yönlendirilir (bedava). */
    public function analitikMi($metin)
    {
        $n = $this->normalize($metin);
        return (bool) preg_match(
            '/(var mi|sorun|sikinti|problem|yanlis|ters|dikkat|kotu (giden|mu|gidiyor)|endise|iyi mi|iyi gidiyor mu|sence|gozune (carpan|batan|takilan)|gozden kacan|fark ?ettin|yolunda|olumsuz|riskli|tehlike|kayg|neyi kacir|nerede (kayb|para)|bir terslik|dusen bir sey)/',
            $n
        );
    }

    /** BEDAVA tespit cevabı (LLM'siz): "benim göremediğim ne var / nerede para kaçıyor" gibi
     *  meta sorulara proaktif tespitleri doğal cümleyle özetler + kart döner. */
    public function tespitCevap($subeId)
    {
        $v = $this->tespitler($subeId);
        $list = $v['tespitler'] ?? [];
        if (empty($list)) {
            return ['basarili' => true, 'intent' => 'tespit', 'seslendir' => true, 'kart' => null,
                'cevap' => 'Şu an göze batan bir sorun görmüyorum, tablo temiz görünüyor. Yine de aklına takılanı sor, birlikte bakarız.'];
        }
        $onemli = array_slice($list, 0, 3);
        $cumle = ($v['selam'] ?? '') . ' ';
        foreach ($onemli as $t) $cumle .= ' ' . $t['mesaj'];
        $etiket = ['risk' => 'önemli', 'firsat' => 'fırsat', 'uyari' => 'dikkat', 'iyi' => 'iyi', 'bilgi' => 'not'];
        $kv = array_map(fn ($t) => ['k' => $t['baslik'], 'v' => $etiket[$t['seviye']] ?? ''], $list);
        return ['basarili' => true, 'intent' => 'tespit', 'seslendir' => true,
            'cevap' => trim($cumle), 'kart' => ['tip' => 'tespit', 'baslik' => 'Senin İçin Baktım', 'kv' => $kv]];
    }

    /** PATRON AI karakteri: gerçek veri brifingiyle beslenmiş işletme ortağı sohbeti. */
    public function patronSohbet($metin, $gecmis, $brief)
    {
        $apiKey = $this->apiKey();
        if (!$apiKey) { $this->aiTeshis = 'anahtar_yok'; return null; }
        $ham = trim((string) $metin);
        if ($ham === '') return null;

        $karakter = <<<'PROMPT'
Sen bir restoran patronunun İŞLETME ORTAĞI ve en güvendiği yol arkadaşısın. Adın yok; kendini "restoranınızın asistanı" diye tanıt. Bir rapor ekranı DEĞİLSİN; patronun göremediğini fark eden, onunla dürüstçe sohbet eden, riskleri ve fırsatları sezen bir ortak gibisin. Patron sana "bu programı aldığımdan beri restoranı benden iyi takip ediyor" diyebilmeli.

KARAKTERİN:
- Sıcak, samimi, sakin ve güven veren. Patronu korur kollarsın ama ASLA panikletmezsin.
- Kısa konuşursun: en fazla üç-dört cümle. Uzun rapor dökmezsin, sohbet edersin. Gerekince "istersen birlikte bakalım" diye kapı aralarsın.
- Kesin/suçlayıcı konuşmazsın: "olabilir, etkili olabilir, birlikte netleştirelim" gibi temkinli ama net.
- Yağcılık yapmazsın, dürüstsün. İyi gideni de över (neden iyi gittiğini söyleyerek), kötüyü nazikçe söylersin.
- Kullanıcıya "patron" diye hitap ETME; sürekli hitap sert ve sıkıcı olur. Doğrudan, doğal konuş.

DÜŞÜNME BİÇİMİN (her önemli tespitte, ama madde madde değil, doğal cümleyle): BULGU (ne görüyorum) -> NEDEN (neden olmuş olabilir) -> RİSK ya da FIRSAT -> ÖNERİ.

NASIL DAVRANIRSIN:
- "Nasıl gidiyor?" gibi açık sorularda sadece ciro söyleme; dikkatini çeken bir şeyi öne çıkar (kişi başı harcama, vardiya farkı, bir personel, kaçan içecek/tatlı satışı gibi) ve "istersen bakalım" de.
- Patron "bak bakalım / kim / neden" derse veriyle derinleş; ama tek veriyle suçlama, "baktığı masa tipi ya da vardiya da etkili olabilir" gibi adil ol.
- Önceki konuşmayı sürdür. Patron kısa "evet, bak, kim" dese bile bağlamı hatırla.
- İyi gideni de fark et.

MUTLAK KURALLAR:
- Sana verilen GERÇEK VERİ dışında RAKAM ya da İSİM UYDURMA. Bir veri yoksa "elimde şu an o veri yok, şöyle sorabilirsin" de. Emin değilsen kesin konuşma.
- Sesli okunacaksın: DÜZ metin yaz. Emoji, yıldız, madde işareti, tırnak, başlık KULLANMA. Sadece Türkçe konuş.
PROMPT;

        $veriBlok = "\n\n--- RESTORANIN GÜNCEL GERÇEK VERİSİ (yalnızca bunu kullan) ---\n" . json_encode($brief, JSON_UNESCAPED_UNICODE);
        $sistem = $karakter . $veriBlok;

        $mesajlar = $this->gecmisMesajlari($gecmis);
        $mesajlar[] = ['role' => 'user', 'content' => $ham];
        $govde = ['model' => $this->model(), 'max_tokens' => 350, 'system' => $sistem, 'messages' => $mesajlar];
        $data = $this->cagir($govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $bl) if (($bl['type'] ?? '') === 'text') $t .= $bl['text'] ?? '';
        $t = trim($t);
        if ($t === '') { $this->aiTeshis = 'metin_bos'; return null; }
        $this->aiTeshis = 'ok_patron';
        return $t;
    }
}

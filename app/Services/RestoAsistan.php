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
            'tahsilat', 'gelir', 'kac lira', 'satis ne', 'ne sattik', 'toplam satis'],
        'garson' => ['garson', 'personel', 'eleman', 'kim satti', 'en cok kim', 'hangi garson', 'hangi personel',
            'kim ne satti', 'en iyi garson', 'en iyi personel', 'kim calisti', 'performans',
            'en dusuk', 'en az satan', 'en kotu garson'],
        'urun' => ['urun', 'yemek', 'hangi urun', 'en cok satan', 'cok satilan', 'satan urun', 'populer', 'ne satiyor', 'en cok yenen'],
        'masa' => ['masa', 'acik masa', 'dolu masa', 'bos masa', 'kac masa', 'masalar', 'oturan', 'acik adisyon'],
        'paket' => ['paket', 'kurye', 'paket siparis', 'gel al', 'teslimat', 'motorcu', 'kac paket'],
        'maliyet' => ['maliyet', 'food cost', 'foodcost', 'food-cost', 'kar', 'karlilik', 'brut kar', 'gider orani', 'malzeme maliyet'],
        'kayip' => ['kayip', 'sizinti', 'kacak', 'iskonto', 'indirim', 'ikram', 'fire', 'zayi', 'silinen', 'iptal urun', 'suistimal'],
        'iptal' => ['iptal adisyon', 'iptal', 'iptaller', 'iptal olan', 'kac iptal', 'iptal edilen'],
        'musteri' => ['musteri', 'kac kisi', 'kac musteri', 'yeni musteri', 'sadik musteri', 'misafir sayisi', 'kac misafir'],
        'ozet' => ['ozet', 'genel durum', 'gun sonu', 'nasil gidiyor', 'nasil gecti', 'isler nasil', 'bugun nasil',
            'ne alemde', 'durum ne', 'rapor ver', 'gunumuz nasil'],
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
        $oncelik = ['iptal', 'kayip', 'maliyet', 'garson', 'urun', 'masa', 'paket', 'musteri', 'ciro', 'ozet'];
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
        if (preg_match('/\b(bu ?yil|yillik|gecen ?yil|senelik)\b/', $norm)) return ['anahtar' => 'yillik', 'ad' => 'bu yıl'];
        if (preg_match('/\b(bu ?ay|aylik|son ?30|gecen ?ay|ay ?boyunca)\b/', $norm)) return ['anahtar' => 'aylik', 'ad' => 'bu ay'];
        if (preg_match('/\b(bu ?hafta|haftalik|son ?7|hafta ?boyunca)\b/', $norm)) return ['anahtar' => 'haftalik', 'ad' => 'bu hafta'];
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
            default:        return null;
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

    // -------------------- OGRENEN ONBELLEK + GECMIS --------------------
    public function ogrenilenNiyet($metin)
    {
        try {
            $v = Cache::get('resto_asistan_ogr:' . md5($this->normalize($metin)));
            if (is_array($v) && !empty($v['intent'])) { $v['ham'] = trim((string) $metin); return $v; }
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
                'intent' => ['type' => 'string', 'enum' => ['ciro', 'garson', 'urun', 'masa', 'paket', 'maliyet', 'kayip', 'iptal', 'musteri', 'ozet', 'sohbet', 'bilinmiyor'],
                    'description' => 'ciro=kasa/hasilat, garson=kim ne satti, urun=en cok satan yemek, masa=acik/dolu masa, paket=paket/kurye siparis, maliyet=food-cost/kar, kayip=iskonto/ikram/fire/silinen sizinti, iptal=iptal adisyon, musteri=misafir sayisi, ozet=genel gun sonu, sohbet=selam/kimlik/genel, bilinmiyor=anlasilamadi'],
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
        $sistem = 'Sen bir RESTORAN patronu icin calisan sesli asistansin. Adin yok, kendini "restoranınızın asistanı" diye tanit. '
            . 'Kisa (en fazla iki cumle), sicak ve NET konus. TTS ile seslendirilecegin icin DUZ yaz: emoji, madde, yildiz, tirnak KULLANMA. '
            . 'Yapabildiklerin: ciro/kasa, en cok satan urun, personel performansi, acik masalar, paket siparisler, food-cost/kar, kayip radari (iskonto/ikram/fire), '
            . 'iptaller, misafir sayisi ve gunluk ozet. RAKAM veya VERI UYDURMA; patron rakam isterse "bugun ciro ne kadar diye sorabilirsiniz" de. '
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
            . 'food-cost, kayıp radarı ve günlük özet için sorabilirsiniz. Örneğin: bugün ciro ne kadar, en çok kim sattı, food-cost ne durumda.';
        return ['basarili' => true, 'intent' => 'yardim', 'cevap' => $c, 'seslendir' => true, 'kart' => null, 'niyet' => $niyet];
    }

    protected function tl($v)
    {
        return '₺' . number_format((float) $v, 0, ',', '.');
    }

    protected function normalize($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
        $s = strtr($s, $tr);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }
}

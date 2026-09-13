<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEF GARSON AI — garsonun "gozu": tum acik salon masalarini surekli inceler,
 * satisa ve misafir memnuniyetine yonelik UYARILAR uretir (Gozcu, BEDAVA kural motoru)
 * ve "bu masaya ne satayim" sorusuna somut ONERI verir (Koc, gerekirse Haiku + ogrenen onbellek).
 *
 * Mimari: MusteriAsistan/RestoAsistan ile ayni desen -> ONCE kural (bedava), sonra
 * ogrenilen onbellek, en son (sadece kacakta) Haiku. Maliyet kontrollu.
 */
class SefGarsonAI
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    // ======================================================================
    // GOZCU — tum acik masalari tara, uyari listesi uret (BEDAVA)
    // ======================================================================
    /**
     * @param int|null $garsonId  Verilirse sadece o garsonun actigi masalar.
     * @return array  Uyari kartlari (oncelige gore sirali).
     */
    public function masalariTara($garsonId = null)
    {
        if (!(bool) config('sefgarson.acik', true)) return [];

        $q = DB::table('adisyonlar as a')
            ->leftJoin('masalar as m', 'a.masa_id', '=', 'm.id')
            ->leftJoin('personeller as p', 'a.acan_personel_id', '=', 'p.id')
            ->where('a.sube_id', $this->subeId)
            ->where('a.durum', 'acik')
            ->where('a.kanal', 'salon');       // paket/qr degil, salon masalari
        if ($garsonId) $q->where('a.acan_personel_id', (int) $garsonId);
        $adisyonlar = $q->get(['a.id', 'a.masa_id', 'a.acilis', 'a.created_at as a_created', 'a.misafir_sayisi', 'a.toplam', 'a.acan_personel_id', 'm.ad as masa_adi', 'p.ad as garson_adi']);
        if ($adisyonlar->isEmpty()) return [];

        // Kalemleri tek sorguda cek, adisyona gore grupla
        $ids = $adisyonlar->pluck('id')->all();
        $kalemler = DB::table('adisyon_kalemleri as k')
            ->leftJoin('urunler as u', 'k.urun_id', '=', 'u.id')
            ->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->whereIn('k.adisyon_id', $ids)
            ->where('k.durum', '!=', 'iptal')
            ->get(['k.adisyon_id', 'k.kur', 'k.urun_adi', 'k.created_at', 'k.gonderim_zamani', 'u.istasyon', 'mk.ad as kat'])
            ->groupBy('adisyon_id');

        $kapatilan = $this->kapatilanlar($ids);   // [adisyon_id.tip => true] (cooldown icinde susturulmus)

        $bosDk      = (int) config('sefgarson.bos_masa_dk', 12);
        $durgunDk   = (int) config('sefgarson.durgun_dk', 18);
        $tatliDk    = (int) config('sefgarson.tatli_dk', 22);
        $kalabalik  = (int) config('sefgarson.kalabalik_kisi', 4);

        $uyarilar = [];
        foreach ($adisyonlar as $a) {
            $kl = $kalemler[$a->id] ?? collect();
            $masaAd = $a->masa_adi ?: ('#' . $a->masa_id);
            $acilis = $a->acilis ?: $a->a_created;
            $acikDk = $acilis ? $this->dkGecti($acilis) : 0;

            $hasAna = false; $hasTatli = false; $hasIcecek = false;
            $sonKalemAt = null; $sonAnaAt = null;
            foreach ($kl as $k) {
                $t = $k->created_at ?: $k->gonderim_zamani;
                if ($t && (!$sonKalemAt || $t > $sonKalemAt)) $sonKalemAt = $t;
                if ($this->isAna($k))    { $hasAna = true; if ($t && (!$sonAnaAt || $t > $sonAnaAt)) $sonAnaAt = $t; }
                if ($this->isTatli($k))  $hasTatli = true;
                if ($this->isIcecek($k)) $hasIcecek = true;
            }
            $kalemSayi = $kl->count();

            $ekle = function ($tip, $oncelik, $baslik, $mesaj, $ikon) use (&$uyarilar, $a, $masaAd, $kapatilan) {
                if (!empty($kapatilan[$a->id . '.' . $tip])) return;   // garson kapatti, cooldown icinde
                $uyarilar[] = [
                    'adisyon_id' => (int) $a->id,
                    'masa_id'    => (int) $a->masa_id,
                    'masa_adi'   => (string) $masaAd,
                    'garson_id'  => (int) $a->acan_personel_id,
                    'garson_adi' => (string) ($a->garson_adi ?? ''),
                    'tip'        => $tip,
                    'oncelik'    => $oncelik,     // 3=yuksek 2=orta 1=dusuk
                    'baslik'     => $baslik,
                    'mesaj'      => $mesaj,
                    'ikon'       => $ikon,
                ];
            };

            // --- KURALLAR (senin satis aklin; istedigin kadar eklenir) ---

            // 1) BOS MASA: acildi ama hic siparis yok
            if ($kalemSayi === 0 && $acikDk >= $bosDk) {
                $ekle('bos_masa', 3, $masaAd . ' hala sipariş vermedi',
                    $masaAd . ' masası ' . $acikDk . ' dakikadır açık ama henüz sipariş yok. Git bir uğra, "bir şey almak ister misiniz" diye sor.', '🕐');
            }
            // 2) TATLI/ÇAY FIRSATI: ana yemek yendi, tatli yok
            elseif ($hasAna && !$hasTatli && $sonAnaAt && $this->dkGecti($sonAnaAt) >= $tatliDk) {
                $ekle('tatli_firsati', 3, $masaAd . ' tatlı zamanı',
                    $masaAd . ' ana yemekte ilerledi. Şimdi tatlı + çay/kahve önermenin tam zamanı, sıcak öner.', '🍰');
            }
            // 3) DURGUN MASA: bir sure yeni siparis yok
            elseif ($kalemSayi > 0 && $sonKalemAt && $this->dkGecti($sonKalemAt) >= $durgunDk) {
                $ekle('durgun', 2, $masaAd . ' durgun',
                    $masaAd . ' masasında ' . $this->dkGecti($sonKalemAt) . ' dakikadır yeni sipariş yok. Uğra; tatlı, içecek ya da bir şey daha ister mi diye sor.', '💤');
            }

            // 4) ICECEK FIRSATI (birlikte gosterilebilir): yemek var ama icecek yok
            if ($kalemSayi > 0 && $hasAna && !$hasIcecek) {
                $ekle('icecek_firsati', 1, $masaAd . ' içeceksiz',
                    $masaAd . ' yemek aldı ama içecek yok. Ayran, şalgam, soda ya da bir içecek öner.', '🥤');
            }
            // 5) KALABALIK MASA baslangic firsati: cok kisi, hesap dusuk/erken
            if ($a->misafir_sayisi >= $kalabalik && $kalemSayi > 0 && !$hasTatli && $acikDk <= 25) {
                $ekle('kalabalik_baslangic', 2, $masaAd . ' kalabalık masa',
                    $masaAd . ' ' . $a->misafir_sayisi . ' kişilik. Ortaya paylaşımlık başlangıç/meze öner; masanın ortalama hesabını büyütür.', '👥');
            }
        }

        // Oncelik + masa adina gore sirala
        usort($uyarilar, fn ($x, $y) => ($y['oncelik'] <=> $x['oncelik']) ?: strcmp($x['masa_adi'], $y['masa_adi']));
        return $uyarilar;
    }

    // ======================================================================
    // KOC — "bu masaya ne satayim?" somut oneri (kural -> onbellek -> Haiku)
    // ======================================================================
    public function oneriSor($adisyonId, $derin = false)
    {
        $a = DB::table('adisyonlar')->where('id', (int) $adisyonId)->where('sube_id', $this->subeId)->first();
        if (!$a) return ['ok' => 0, 'mesaj' => 'Masa bulunamadı.'];

        $kl = DB::table('adisyon_kalemleri as k')
            ->leftJoin('urunler as u', 'k.urun_id', '=', 'u.id')
            ->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('k.adisyon_id', $a->id)->where('k.durum', '!=', 'iptal')
            ->get(['k.kur', 'k.urun_adi', 'u.istasyon', 'mk.ad as kat']);

        $hasAna = $kl->contains(fn ($k) => $this->isAna($k));
        $hasTatli = $kl->contains(fn ($k) => $this->isTatli($k));
        $hasIcecek = $kl->contains(fn ($k) => $this->isIcecek($k));
        $bos = $kl->isEmpty();

        // 1) KURAL: eksik olana gore somut urun sec (BEDAVA)
        if ($bos) {
            $urunler = $this->favoriUrunler(3);
            $mesaj = 'Masa yeni açıldı. Önce popüler başlangıç/ana yemekle karşıla: ' . $this->adListe($urunler) . '.';
        } elseif ($hasAna && !$hasTatli) {
            $urunler = array_merge($this->kategoriUrun(['tatli'], 2), $this->icecekUrun(['sicak'], 1));
            $mesaj = 'Ana yemek ilerledi — tatlı + sıcak içecek tam zamanı: ' . $this->adListe($urunler) . '.';
        } elseif (!$hasIcecek) {
            $urunler = $this->icecekUrun(['soguk', 'sicak'], 3);
            $mesaj = 'Masada içecek yok. Öner: ' . $this->adListe($urunler) . '.';
        } else {
            $urunler = array_merge($this->kategoriUrun(['tatli'], 1), $this->favoriUrunler(2));
            $mesaj = 'Masayı büyütmek için öner: ' . $this->adListe($urunler) . '.';
        }
        $urunler = array_values(array_filter($urunler));

        // 2) Derin oneri istenirse: ogrenilen onbellek -> Haiku (garson agziyla kisa koc notu)
        if ($derin) {
            $ozet = $this->adisyonOzet($kl) . ' | misafir:' . (int) $a->misafir_sayisi;
            $ai = $this->haikuKoc($ozet, $this->adListe($urunler));
            if ($ai) $mesaj = $ai;
        }

        return ['ok' => 1, 'mesaj' => $mesaj, 'urunler' => $urunler];
    }

    // ======================================================================
    // UYARI KAPATMA (garson "tamam/gördüm" derse cooldown boyunca susar)
    // ======================================================================
    public function uyariKapat($adisyonId, $tip, $garsonId = null)
    {
        $this->kapatmaTablo();
        try {
            DB::table('sef_garson_kapatma')->insert([
                'sube_id' => $this->subeId, 'adisyon_id' => (int) $adisyonId,
                'tip' => mb_substr((string) $tip, 0, 40), 'garson_id' => $garsonId ? (int) $garsonId : null,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {}
        return ['ok' => 1];
    }

    // ---------------------------------------------------------------- helpers

    /** Cooldown icinde kapatilmis (adisyon_id.tip) anahtarlari. */
    protected function kapatilanlar(array $adisyonIds)
    {
        $this->kapatmaTablo();
        $cool = (int) config('sefgarson.kapatma_cooldown_dk', 15);
        try {
            $rows = DB::table('sef_garson_kapatma')
                ->where('sube_id', $this->subeId)
                ->whereIn('adisyon_id', $adisyonIds)
                ->where('created_at', '>=', now()->subMinutes($cool))
                ->get(['adisyon_id', 'tip']);
            $map = [];
            foreach ($rows as $r) $map[$r->adisyon_id . '.' . $r->tip] = true;
            return $map;
        } catch (\Throwable $e) { return []; }
    }

    protected function kapatmaTablo()
    {
        if (Schema::hasTable('sef_garson_kapatma')) return;
        try {
            Schema::create('sef_garson_kapatma', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->unsignedBigInteger('adisyon_id');
                $t->string('tip', 40);
                $t->unsignedBigInteger('garson_id')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'adisyon_id', 'created_at']);
            });
        } catch (\Throwable $e) {}
    }

    protected function isAna($k)
    {
        if (($k->kur ?? '') === 'ana') return true;
        if (in_array($k->istasyon ?? '', ['izgara', 'firin', 'mutfak'])) return true;
        return strpos($this->norm($k->kat ?? ''), 'ana yemek') !== false;
    }

    protected function isTatli($k)
    {
        if (($k->kur ?? '') === 'tatli') return true;
        if (($k->istasyon ?? '') === 'tatli') return true;
        return strpos($this->norm($k->kat ?? ''), 'tatli') !== false;
    }

    protected function isIcecek($k)
    {
        if (($k->istasyon ?? '') === 'bar') return true;
        $kat = $this->norm($k->kat ?? '');
        if (strpos($kat, 'icecek') !== false || strpos($kat, 'cay') !== false || strpos($kat, 'kahve') !== false) return true;
        $ad = $this->norm($k->urun_adi ?? '');
        foreach (['cay', 'kahve', 'kola', 'ayran', 'soda', 'su', 'salgam', 'limonata', 'mesrubat', 'sarap', 'bira', 'meyve suyu', 'espresso', 'latte', 'cappuccino'] as $w) {
            if (strpos(' ' . $ad . ' ', ' ' . $w . ' ') !== false) return true;
        }
        return false;
    }

    /** Verilen istasyon anahtarina gore icecek urunleri (soguk/sicak). */
    protected function icecekUrun(array $tur, $limit)
    {
        $rows = DB::table('urunler as u')->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('u.sube_id', $this->subeId)->where('u.aktif', 1)->where('u.tukendi', 0)
            ->where(function ($w) {
                $w->where('u.istasyon', 'bar')->orWhere('mk.ad', 'like', '%içecek%')
                  ->orWhere('mk.ad', 'like', '%çay%')->orWhere('mk.ad', 'like', '%kahve%');
            })
            ->limit(max(3, $limit * 2))->pluck('u.ad')->all();
        return array_slice($rows, 0, $limit);
    }

    /** Kategori anahtar kelimesiyle urun adlari ( or istasyon). */
    protected function kategoriUrun(array $anahtarlar, $limit)
    {
        $q = DB::table('urunler as u')->leftJoin('menu_kategorileri as mk', 'u.kategori_id', '=', 'mk.id')
            ->where('u.sube_id', $this->subeId)->where('u.aktif', 1)->where('u.tukendi', 0);
        $q->where(function ($w) use ($anahtarlar) {
            foreach ($anahtarlar as $a) {
                $w->orWhere('u.istasyon', $a)->orWhere('mk.ad', 'like', '%' . $a . '%');
            }
        });
        return array_slice($q->limit(max(3, $limit * 2))->pluck('u.ad')->all(), 0, $limit);
    }

    /** Son 30 gunun cok satan urunleri; yoksa rastgele aktif. */
    protected function favoriUrunler($limit)
    {
        $top = DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')
            ->where('a.sube_id', $this->subeId)->where('a.durum', 'odendi')
            ->where('a.kapanis', '>=', now()->subDays(30))->where('k.durum', '!=', 'iptal')
            ->select('k.urun_adi', DB::raw('SUM(k.adet) as adet'))
            ->groupBy('k.urun_adi')->orderByDesc('adet')->limit($limit)->pluck('urun_adi')->all();
        if (count($top) < $limit) {
            $ek = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('tukendi', 0)
                ->whereNotIn('ad', $top ?: [''])->inRandomOrder()->limit($limit - count($top))->pluck('ad')->all();
            $top = array_merge($top, $ek);
        }
        return $top;
    }

    protected function adisyonOzet($kl)
    {
        if ($kl->isEmpty()) return 'masa bos (siparis yok)';
        $adlar = $kl->pluck('urun_adi')->filter()->take(12)->implode(', ');
        return 'masadaki siparis: ' . $adlar;
    }

    protected function adListe(array $adlar)
    {
        $adlar = array_values(array_filter($adlar));
        if (empty($adlar)) return 'menüden uygun bir seçenek';
        if (count($adlar) === 1) return $adlar[0];
        $son = array_pop($adlar);
        return implode(', ', $adlar) . ' veya ' . $son;
    }

    /** Kac dakika gecti (timestamp string/Carbon). */
    protected function dkGecti($t)
    {
        try { return now()->diffInMinutes(\Illuminate\Support\Carbon::parse($t)); }
        catch (\Throwable $e) { return 0; }
    }

    protected function norm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i̇' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'İ' => 'i'];
        return strtr($s, $tr);
    }

    // ---------------------------------------------------- HAIKU KOC + ONBELLEK
    protected function haikuKoc($ozet, $oneriMetin)
    {
        if (!(bool) config('services.anthropic.sohbet_acik', true)) return null;
        $anahtar = (string) (config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY'));
        if ($anahtar === '') return null;

        $key = $this->norm($ozet);
        $cev = $this->ogrenilen($key);
        if ($cev !== null && $cev !== '') return $cev;

        $limit = (int) config('sefgarson.gunluk_limit', config('services.anthropic.sohbet_gunluk_limit', 200));
        if ($limit > 0 && $this->gunlukSayac() >= $limit) return null;

        $sistem = 'Sen tecrubeli bir SEF GARSONSUN ve genc garsona KISA, net satis koclugu yapiyorsun (en fazla iki cumle). '
            . 'Amac: ek satis (tatli, icecek, cay/kahve, meze) + misafir memnuniyeti. Emri kibar ve uygulanabilir ver. '
            . 'Emoji, madde, tirnak KULLANMA, sadece Turkce duz metin. Menudeki su onerileri kullanabilirsin: ' . $oneriMetin;
        $govde = [
            'model' => (string) (config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001'),
            'max_tokens' => 160, 'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => 'Masanin durumu: ' . $ozet . '. Garsona ne yapmasini soylersin?']],
        ];
        $data = $this->cagirAnthropic($anahtar, $govde);
        if (!$data || empty($data['content'])) return null;
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if ($t === '') return null;
        $this->ogret($key, $t);
        return $t;
    }

    protected function cagirAnthropic($anahtar, $govde)
    {
        try {
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 14,
                CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . $anahtar, 'anthropic-version: 2023-06-01'],
                CURLOPT_POSTFIELDS => json_encode($govde, JSON_UNESCAPED_UNICODE),
            ]);
            $yanit = curl_exec($ch);
            $kod = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($yanit === false || $kod !== 200) return null;
            return json_decode($yanit, true);
        } catch (\Throwable $e) { return null; }
    }

    protected function ogrenTablo()
    {
        if (Schema::hasTable('sef_garson_ogrenilen')) return;
        try {
            Schema::create('sef_garson_ogrenilen', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('durum_key', 191);
                $t->text('cevap');
                $t->unsignedInteger('kullanim')->default(1);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'durum_key']);
            });
        } catch (\Throwable $e) {}
    }

    protected function ogrenilen($key)
    {
        try {
            $this->ogrenTablo();
            $row = DB::table('sef_garson_ogrenilen')->where('sube_id', $this->subeId)->where('durum_key', mb_substr($key, 0, 191))->first();
            if ($row) { try { DB::table('sef_garson_ogrenilen')->where('id', $row->id)->increment('kullanim'); } catch (\Throwable $e) {} return $row->cevap; }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function ogret($key, $cevap)
    {
        try {
            $this->ogrenTablo();
            DB::table('sef_garson_ogrenilen')->insert(['sube_id' => $this->subeId, 'durum_key' => mb_substr($key, 0, 191), 'cevap' => $cevap, 'kullanim' => 1, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }

    protected function gunlukSayac()
    {
        try { $this->ogrenTablo(); return (int) DB::table('sef_garson_ogrenilen')->where('sube_id', $this->subeId)->whereDate('created_at', today())->count(); }
        catch (\Throwable $e) { return 0; }
    }
}

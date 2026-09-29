<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI SANTRAL — telefonla arayan musteriye yanit veren yapay zeka gorevlisi.
 *
 * Akis: Asterisk -> restaurant-santral-ai (Node kopru) -> STT metin -> BU BEYIN -> cevap metni (+aksiyon)
 *  -> TTS -> Asterisk. Bu sinif SADECE metin girer / metin+aksiyon dondurur (ses yok).
 *  Boylece Postman ile yazisarak da test edilebilir (Faz 1).
 *
 * Beyin Anthropic Haiku'yu, RestoAsistan/MusteriAsistan ile AYNI cagri kalibiyla kullanir.
 */
class SantralAsistan
{
    public $teshis = null;
    protected $subeId;
    protected $sube;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
        $this->sube = DB::table('subeler')->where('id', $this->subeId)->first();
    }

    /** Cagri acilinca ilk karsilama (LLM'e gerek yok, sabit + sicak). */
    public function karsilama(): string
    {
        $ad = $this->sube->ad ?? 'restoranımız';
        return $ad . "'a hoş geldiniz, ben yapay zeka asistanınızım. Size nasıl yardımcı olabilirim?";
    }

    /**
     * Musteri konustu -> cevap uret.
     * @param string $metin  STT'den gelen musteri cumlesi
     * @param array  $gecmis [['role'=>'user'|'assistant','content'=>...], ...]
     * @return array ['cevap'=>string, 'aksiyon'=>null|'rezervasyon'|'siparis'|'aktar'|'veda', 'veri'=>array, 'bitir'=>bool]
     */
    public function konus(string $metin, array $gecmis = []): array
    {
        $ham = trim($metin);
        if ($ham === '') {
            return ['cevap' => 'Buyurun, sizi dinliyorum.', 'aksiyon' => null, 'veri' => [], 'bitir' => false];
        }

        $apiKey = $this->apiKey();
        if (!$apiKey) {
            $this->teshis = 'anahtar_yok';
            return ['cevap' => 'Sizi hemen yetkiliye bağlıyorum, lütfen hatta kalın.', 'aksiyon' => 'aktar', 'veri' => [], 'bitir' => false];
        }

        $mesajlar = $this->gecmisMesajlari($gecmis);
        $mesajlar[] = ['role' => 'user', 'content' => $ham];

        $govde = [
            'model' => $this->model(),
            'max_tokens' => 200, // telefon: kisa yanit = daha hizli
            // system'i dizi + cache_control ile ver: menu iceren uzun prompt her turda ONBELLEKTEN okunur
            // -> beyin daha HIZLI cevap verir ve maliyet duser (Anthropic prompt caching)
            'system' => [[
                'type' => 'text',
                'text' => $this->sistemPromptu(),
                'cache_control' => ['type' => 'ephemeral'],
            ]],
            'tools' => [$this->aksiyonAraci()],
            'messages' => $mesajlar,
        ];

        $data = $this->cagir($govde);
        if (!$data || empty($data['content'])) {
            $this->teshis = 'cevap_yok';
            return ['cevap' => 'Kusura bakmayın, sizi yetkiliye aktarıyorum.', 'aksiyon' => 'aktar', 'veri' => [], 'bitir' => false];
        }

        $cevap = '';
        $aksiyon = null;
        $veri = [];
        foreach ($data['content'] as $b) {
            $tip = $b['type'] ?? '';
            if ($tip === 'text') {
                $cevap .= $b['text'] ?? '';
            } elseif ($tip === 'tool_use' && ($b['name'] ?? '') === 'santral_aksiyon') {
                $in = $b['input'] ?? [];
                $niyet = $in['niyet'] ?? null;
                if (in_array($niyet, ['rezervasyon', 'siparis', 'aktar', 'veda'], true) && !empty($in['tamam'])) {
                    $aksiyon = $niyet;
                    $veri = $in;
                }
            }
        }

        $cevap = $this->ttsTemizle($cevap);
        if ($cevap === '') {
            $cevap = $aksiyon === 'aktar'
                ? 'Sizi yetkiliye bağlıyorum, lütfen hatta kalın.'
                : 'Anladım, devam edelim.';
        }

        $this->teshis = 'ok';
        return [
            'cevap' => $cevap,
            'aksiyon' => $aksiyon,
            'veri' => $veri,
            'bitir' => $aksiyon === 'veda',
        ];
    }

    // -------------------- PERSONA + BAGLAM --------------------

    protected function sistemPromptu(): string
    {
        $ad = $this->sube->ad ?? 'restoran';
        $adres = $this->sube->adres ?? null;
        $tel = $this->sube->telefon ?? null;

        $p = "Sen $ad adlı restoranın telefonla arayan müşterilerine yanıt veren yapay zeka SANTRAL görevlisisin. ";
        $p .= "Doğal, sıcak ve ÇOK KISA Türkçe konuş; genellikle tek cümle, en fazla iki kısa cümle. Gereksiz nezaket/uzatma yok, doğrudan konuya gir. ";
        $p .= "TTS ile seslendirileceğin için DÜZ metin yaz: emoji, madde işareti, yıldız, tırnak KULLANMA. ";
        $p .= "Görevlerin: karşılama; çalışma saati, adres ve menü hakkında bilgi vermek; REZERVASYON almak; PAKET SİPARİŞ almak; gerektiğinde yetkiliye aktarmak. ";
        $p .= "REZERVASYON için gereken bilgiler: ad, kişi sayısı, tarih ve saat. Eksik olanları TEK TEK, kısa sorularla iste; hepsi tamamlanınca müşteriye tekrar edip onay al, sonra santral_aksiyon aracını niyet=rezervasyon ve tamam=true ile çağır. ";
        $p .= "PAKET SİPARİŞ için: ürün ve adetler (SADECE menüdeki ürünlerden, olmayan ürünü uydurma), teslimat adresi ve telefon. Tamamlanınca onay al ve santral_aksiyon aracını niyet=siparis, tamam=true ile çağır. ";
        // Garson AI koclugu: uygun bir noktada NAZIKCE tek bir ek satis onerisi (icecek/tatli), israr etme.
        $p .= "Sipariş alırken uygun bir yerde yanına bir içecek ya da tatlı önerebilirsin (menüden, tek cümle, kibar, ısrarcı olma). Müşteri istemezse hemen geç. ";
        // Menu tanitimi: telefonda UZUN liste okuma; birkac one cikan urunu/kategoriyi kisaca soyle, sonra ne istedigini sor. ASLA aktarma.
        $p .= "Müşteri 'neler var', 'menüde ne var', 'tanıtır mısın' gibi bir şey sorarsa: menüden EN FAZLA üç dört öne çıkan ürünü ya da ana yemek türlerini KISACA say (telefonda tüm listeyi okuma), sonra 'ne almak istersiniz?' diye sor. Bu durumda ASLA yetkiliye aktarma. ";
        // Aktarma cok kisitli: sadece sikayet / menu disi cok ozel istek / cozemeyecegin durum. Menu, fiyat, siparis, rezervasyon icin ASLA aktarma.
        $p .= "Yetkiliye aktarmayı SADECE şu durumlarda yap: ciddi şikayet, menüde hiç olmayan çok özel bir talep, ya da gerçekten çözemeyeceğin bir konu. Menü, fiyat, sipariş ve rezervasyon senin işin; bunlar için ASLA aktarma ve telefonu kapatma. Aktarırken santral_aksiyon niyet=aktar, tamam=true kullan. ";
        $p .= "Müşteri açıkça vedalaşır ya da 'kapatabilirsin' derse kibarca veda et ve santral_aksiyon niyet=veda, tamam=true ile çağır. Aksi halde görüşmeyi sürdür, kendiliğinden kapatma. ";
        $p .= "'Buyurun' kelimesini tekrar tekrar kullanma. Sadece Türkçe konuş.";

        if ($adres) $p .= " Restoranın adresi: $adres.";
        if ($tel) $p .= " Restoranın telefonu: $tel.";

        $p .= $this->menuBaglami();

        return $p;
    }

    /** Anthropic tool tanimi: yapisal aksiyon yakalama. */
    protected function aksiyonAraci(): array
    {
        return [
            'name' => 'santral_aksiyon',
            'description' => 'Bir aksiyon TAMAMLANDIĞINDA (müşteri onayı alındıktan sonra) çağrılır. Eksik bilgi varken çağırma.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'niyet' => ['type' => 'string', 'enum' => ['rezervasyon', 'siparis', 'aktar', 'veda']],
                    'tamam' => ['type' => 'boolean', 'description' => 'Aksiyon için tüm bilgiler tam ve müşteri onayladıysa true'],
                    'rezervasyon' => [
                        'type' => 'object',
                        'properties' => [
                            'ad' => ['type' => 'string'],
                            'telefon' => ['type' => 'string'],
                            'kisi' => ['type' => 'integer'],
                            'tarih' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                            'saat' => ['type' => 'string', 'description' => 'HH:MM'],
                        ],
                    ],
                    'siparis' => [
                        'type' => 'object',
                        'properties' => [
                            'kalemler' => [
                                'type' => 'array',
                                'items' => ['type' => 'object', 'properties' => [
                                    'urun' => ['type' => 'string'],
                                    'adet' => ['type' => 'integer'],
                                ]],
                            ],
                            'adres' => ['type' => 'string'],
                            'telefon' => ['type' => 'string'],
                        ],
                    ],
                ],
                'required' => ['niyet', 'tamam'],
            ],
        ];
    }

    /**
     * Menu -> kategori grupli kompakt metin (MusteriAsistan.menuOzetMetni ile AYNI kaynak/mantik).
     * Boylece telefon AI'si masadaki QR asistaniyla ayni menuyu gorur.
     */
    protected function menuOzeti(): string
    {
        try {
            if (!Schema::hasTable('urunler')) return '';
            $kats = Schema::hasTable('menu_kategorileri')
                ? DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->get(['id', 'ad'])
                : collect();
            $q = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1);
            if (Schema::hasColumn('urunler', 'tukendi')) $q->where('tukendi', 0);
            $urunler = $q->get(['ad', 'fiyat', 'kategori_id']);
            if ($urunler->isEmpty()) return '';
            $byKat = [];
            foreach ($urunler as $u) {
                $fiyat = is_numeric($u->fiyat) ? (' (' . number_format((float) $u->fiyat, 0, ',', '.') . ' TL)') : '';
                $byKat[(int) $u->kategori_id][] = $u->ad . $fiyat;
            }
            $lines = [];
            $bilinen = [];
            foreach ($kats as $k) {
                $bilinen[] = (int) $k->id;
                if (!empty($byKat[$k->id])) $lines[] = $k->ad . ': ' . implode(', ', array_slice($byKat[$k->id], 0, 20));
            }
            // Kategorisi tanimsiz kalan urunler
            $kalan = [];
            foreach ($byKat as $kid => $arr) { if (!in_array((int) $kid, $bilinen, true)) $kalan = array_merge($kalan, $arr); }
            if ($kalan) $lines[] = 'Diğer: ' . implode(', ', array_slice($kalan, 0, 20));
            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** One cikan urunler (Sefin Onerisi) — urunler.one_cikan=1. */
    protected function oneCikanlar(int $limit = 5): array
    {
        try {
            if (!Schema::hasColumn('urunler', 'one_cikan')) return [];
            $q = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('one_cikan', 1);
            if (Schema::hasColumn('urunler', 'tukendi')) $q->where('tukendi', 0);
            if (Schema::hasColumn('urunler', 'one_sira')) $q->orderBy('one_sira');
            return $q->limit($limit)->pluck('ad')->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Son 30 gunun en cok satilanlari ("bugun ne iyi / ne oneriyorsun"). */
    protected function favoriUrunler(int $limit = 5): array
    {
        try {
            if (!Schema::hasTable('adisyon_kalemleri') || !Schema::hasTable('adisyonlar')) return [];
            $top = DB::table('adisyon_kalemleri as k')
                ->join('adisyonlar as a', 'k.adisyon_id', '=', 'a.id')
                ->where('a.sube_id', $this->subeId)
                ->where('a.kapanis', '>=', now()->subDays(30))
                ->where('k.durum', '!=', 'iptal')
                ->groupBy('k.urun_adi')
                ->orderByRaw('SUM(k.adet) DESC')
                ->limit($limit)
                ->pluck('k.urun_adi')->all();
            return array_values(array_filter($top, fn ($x) => trim((string) $x) !== ''));
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Haiku'ya verilecek TAM menu baglami: gruplu menu + Sefin Onerisi + populer (bugun ne var).
     * Menu bossa: telefonu kapatma/aktarma, serbest metin siparis al.
     */
    protected function menuBaglami(): string
    {
        $menu = $this->menuOzeti();
        if ($menu === '') {
            return " Menü listesi şu an elimde yok; yine de müşteriye ne yemek istediğini sor ve siparişini serbest metin olarak al, telefonu KAPATMA ve aktarma.";
        }
        $p = " GÜNCEL MENÜ (yalnızca bunlardan öner ve sat, kategoriye göre):\n" . $menu;
        $sef = $this->oneCikanlar(5);
        if ($sef) $p .= "\nŞefin önerileri (öne çıkanlar): " . implode(', ', $sef) . '.';
        $pop = $this->favoriUrunler(5);
        if ($pop) $p .= "\nBugünlerde en çok tercih edilenler: " . implode(', ', $pop) . '.';
        $p .= "\n'Bugün ne var', 'ne önerirsin', 'en çok ne satıyor' gibi sorularda önce Şefin önerileri ve en çok tercih edilenlerden birkaçını KISACA söyle.";
        return $p;
    }

    /** Teshis: bu sube icin kac aktif urun var (menu bos mu kontrolu). */
    public function menuAdet(): int
    {
        try {
            if (!Schema::hasTable('urunler')) return -1; // tablo yok
            $q = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1);
            if (Schema::hasColumn('urunler', 'tukendi')) $q->where('tukendi', 0);
            return (int) $q->count();
        } catch (\Throwable $e) {
            return -2; // hata
        }
    }

    // -------------------- ANTHROPIC (RestoAsistan ile ayni kalip) --------------------

    protected function apiKey()
    {
        return config('services.anthropic.key') ?: env('ANTHROPIC_API_KEY');
    }

    protected function model()
    {
        return config('services.anthropic.model') ?: 'claude-haiku-4-5-20251001';
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
                $this->teshis = $yanit === false ? ('curl_HATA: ' . $err) : ('http_' . $kod . ': ' . substr((string) $yanit, 0, 300));
                return null;
            }
            return json_decode($yanit, true);
        } catch (\Throwable $e) {
            $this->teshis = 'exception: ' . $e->getMessage();
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
            if (empty($out) && $rol !== 'user') continue; // ilk mesaj user olmali
            $out[] = ['role' => $rol, 'content' => $ic];
        }
        return $out;
    }

    /** TTS icin metni sadelestir: tirnak/yildiz/emoji/madde temizle. */
    protected function ttsTemizle($t): string
    {
        $t = trim((string) $t);
        if ($t === '') return '';
        $t = str_replace(['*', '_', '`', '"', '“', '”', '•', '- ', '\n'], ['', '', '', '', '', '', '', '', ' '], $t);
        $t = preg_replace('/\s+/u', ' ', $t);
        return trim($t);
    }
}

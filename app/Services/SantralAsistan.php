<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * AI SANTRAL — telefonla arayan musteriye yanit veren yapay zeka gorevlisi.
 *
 * Akis: Asterisk -> santral-ai (Node kopru) -> STT metin -> BU BEYIN -> cevap metni (+aksiyon)
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
            'max_tokens' => 320,
            'system' => $this->sistemPromptu(),
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
        $p .= "Doğal, sıcak ve KISA Türkçe konuş; her yanıtın en fazla iki cümle olsun. ";
        $p .= "TTS ile seslendirileceğin için DÜZ metin yaz: emoji, madde işareti, yıldız, tırnak KULLANMA. ";
        $p .= "Görevlerin: karşılama; çalışma saati, adres ve menü hakkında bilgi vermek; REZERVASYON almak; PAKET SİPARİŞ almak; gerektiğinde yetkiliye aktarmak. ";
        $p .= "REZERVASYON için gereken bilgiler: ad, kişi sayısı, tarih ve saat. Eksik olanları TEK TEK, kısa sorularla iste; hepsi tamamlanınca müşteriye tekrar edip onay al, sonra santral_aksiyon aracını niyet=rezervasyon ve tamam=true ile çağır. ";
        $p .= "PAKET SİPARİŞ için: ürün ve adetler (SADECE menüdeki ürünlerden, olmayan ürünü uydurma), teslimat adresi ve telefon. Tamamlanınca onay al ve santral_aksiyon aracını niyet=siparis, tamam=true ile çağır. ";
        $p .= "Bilmediğin ya da emin olmadığın bir bilgi sorulursa (fiyat/uygunluk/özel istek) UYDURMA; 'sizi hemen yetkiliye bağlıyorum' deyip santral_aksiyon aracını niyet=aktar, tamam=true ile çağır. ";
        $p .= "Müşteri teşekkür edip görüşme biterse kibarca veda et ve santral_aksiyon aracını niyet=veda, tamam=true ile çağır. ";
        $p .= "'Buyurun' kelimesini tekrar tekrar kullanma. Sadece Türkçe konuş.";

        if ($adres) $p .= " Restoranın adresi: $adres.";
        if ($tel) $p .= " Restoranın telefonu: $tel.";

        $menu = $this->menuOzeti();
        if ($menu) $p .= " Güncel menü (yalnızca bunları öner ve sat): " . $menu;
        else $p .= " Menü bilgisi şu an sistemde yok; menü/fiyat sorulursa yetkiliye aktar.";

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

    /** Kompakt menu ozeti (kategori: urun (fiyat), ...). Uydurma engellemek icin gercek veriden. */
    protected function menuOzeti(): string
    {
        try {
            if (!Schema::hasTable('urunler')) return '';
            $q = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1);
            if (Schema::hasColumn('urunler', 'tukendi')) $q->where('tukendi', 0);
            $urunler = $q->orderBy('ad')->limit(60)->get(['ad', 'fiyat']);
            if ($urunler->isEmpty()) return '';
            $parcalar = [];
            foreach ($urunler as $u) {
                $fiyat = is_numeric($u->fiyat) ? (' ' . rtrim(rtrim(number_format((float) $u->fiyat, 2, ',', '.'), '0'), ',') . ' TL') : '';
                $parcalar[] = $u->ad . $fiyat;
            }
            return implode('; ', $parcalar);
        } catch (\Throwable $e) {
            return '';
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

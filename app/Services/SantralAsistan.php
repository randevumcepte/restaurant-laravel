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
    protected $telefon;       // arayan numara (CallerID)
    protected $musteri;       // kayitli musteri (obj|null) — tanima icin
    protected $sonSiparis;    // gecen siparis kalemleri [{urun,adet}] — "ayni siparis" icin
    protected $_telaffuzMap = null; // TTS telaffuz sozlugu onbellegi (kelime=>okunus)
    protected $mevcutRez = [];      // arayanin yaklasan rezervasyonlari (cift rezervasyonu onle)
    protected $haricRezId = 0;      // BU cagride olusturulan rezervasyon (mevcut-rez sayilmasin -> "zaten var" demesin)

    public function __construct($subeId, $telefon = null, $haricRezId = 0)
    {
        $this->subeId = (int) $subeId;
        $this->sube = DB::table('subeler')->where('id', $this->subeId)->first();
        $this->telefon = $telefon ? preg_replace('/\D/', '', (string) $telefon) : null;
        $this->haricRezId = (int) $haricRezId;
        $this->musteriYukle();
        $this->mevcutRezYukle();
    }

    /** Arayanin (telefonuna gore) yaklasan aktif rezervasyonlari -> cift rezervasyonu onlemek + hatirlatmak. */
    protected function mevcutRezYukle(): void
    {
        $this->mevcutRez = [];
        try {
            if (!$this->telefon || !Schema::hasTable('rezervasyonlar')) return;
            $son10 = substr($this->telefon, -10);
            $bugun = now()->setTimezone('Europe/Istanbul')->format('Y-m-d');
            $q = DB::table('rezervasyonlar')->where('sube_id', $this->subeId)
                ->whereIn('durum', ['bekliyor', 'onaylandi'])->where('tarih', '>=', $bugun);
            if ($this->haricRezId) $q->where('id', '!=', $this->haricRezId); // bu cagride olusani hariç tut
            foreach ($q->orderBy('tarih')->orderBy('saat')->limit(200)->get(['id', 'tarih', 'saat', 'kisi', 'telefon']) as $r) {
                if (substr(preg_replace('/\D/', '', (string) $r->telefon), -10) === $son10) {
                    $this->mevcutRez[] = ['tarih' => $r->tarih, 'saat' => substr($r->saat, 0, 5), 'kisi' => (int) $r->kisi];
                }
            }
        } catch (\Throwable $e) {}
    }

    /** CallerID ile kayitli musteriyi + gecen siparisini yukle (tanima/kisisellestirme). */
    protected function musteriYukle(): void
    {
        $this->musteri = null;
        $this->sonSiparis = [];
        try {
            if (!$this->telefon || mb_strlen($this->telefon) < 7 || !Schema::hasTable('musteriler')) return;
            $son10 = substr($this->telefon, -10);
            // 1) Hizli: like son-10 hane
            $m = DB::table('musteriler')->where('sube_id', $this->subeId)
                ->where('telefon', 'like', '%' . $son10)->orderByDesc('id')->first();
            // 2) Format-bagimsiz (bu sube): kayitli telefonu normalize edip son-10 hane karsilastir
            if (!$m) {
                foreach (DB::table('musteriler')->where('sube_id', $this->subeId)->whereNotNull('telefon')
                    ->orderByDesc('id')->limit(5000)->get(['id', 'ad', 'telefon', 'adres']) as $cand) {
                    if (substr(preg_replace('/\D/', '', (string) $cand->telefon), -10) === $son10) { $m = $cand; break; }
                }
            }
            // 3) SUBE-BAGIMSIZ son care (tek restoran; siparis farkli sube_id'ye yazildiysa yine tani)
            if (!$m) {
                foreach (DB::table('musteriler')->whereNotNull('telefon')
                    ->orderByDesc('id')->limit(5000)->get(['id', 'ad', 'telefon', 'adres']) as $cand) {
                    if (substr(preg_replace('/\D/', '', (string) $cand->telefon), -10) === $son10) { $m = $cand; break; }
                }
            }
            if (!$m) return;
            $this->musteri = $m;
            if (Schema::hasTable('adisyonlar') && Schema::hasTable('adisyon_kalemleri')) {
                // son siparis: musteri_id yeterli (sube filtresi yok -> kacirmasin)
                $sonAd = DB::table('adisyonlar')->where('musteri_id', $m->id)
                    ->orderByDesc('id')->first(['id']);
                if ($sonAd) {
                    $this->sonSiparis = DB::table('adisyon_kalemleri')->where('adisyon_id', $sonAd->id)
                        ->where('durum', '!=', 'iptal')->get(['urun_adi', 'adet'])
                        ->map(fn ($x) => ['urun' => $x->urun_adi, 'adet' => (int) $x->adet])->all();
                }
            }
        } catch (\Throwable $e) {
            $this->musteri = null;
        }
    }

    protected function genelAd($ad): bool
    {
        $n = mb_strtolower(trim((string) $ad), 'UTF-8');
        return $n === '' || strpos($n, 'paket') !== false || strpos($n, 'telefon') !== false || strpos($n, 'müşteri') !== false || strpos($n, 'musteri') !== false;
    }

    protected function ilkIsim($ad): string
    {
        $p = preg_split('/\s+/', trim((string) $ad));
        return $p[0] ?? (string) $ad;
    }

    /** Teshis/log: taninan musteri adi (yoksa null, kayitli ama isim genelse '(isim yok)'). */
    public function taninanMusteri(): ?string
    {
        if (!$this->musteri) return null;
        return $this->genelAd($this->musteri->ad) ? '(isim yok)' : $this->ilkIsim($this->musteri->ad);
    }

    /** Teshis/log: gecen siparis ozeti (yoksa ''). */
    public function sonSiparisMetni(): string
    {
        if (empty($this->sonSiparis)) return '';
        return implode(', ', array_map(fn ($k) => $k['adet'] . ' ' . $k['urun'], $this->sonSiparis));
    }

    /** Cagri acilinca ilk karsilama — kayitli musteriyi ADIYLA karsilar. */
    public function karsilama(): string
    {
        $ad = $this->sube->ad ?? 'restoranımız';
        // SADECE gercek adi olan kayitli musteriyi ADIYLA + "tekrar" karsila.
        if ($this->musteri && !$this->genelAd($this->musteri->ad)) {
            $isim = $this->ilkIsim($this->musteri->ad);
            return "Merhaba $isim, " . $ad . "'a tekrar hoş geldiniz. Size nasıl yardımcı olabilirim?";
        }
        // Adi olmayan (generic 'Telefon...') ya da hic kayitsiz -> YENI musteri gibi karsila (yoksa AI 'sizi taniyorum' diye yanlis tanir).
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

        // KUFUR / HAKARET -> once SAYGIYA DAVET; ayni cagride TEKRAR ederse gorusmeyi KAPAT.
        // Kural motoru (bedava, LLM'siz); MusteriAsistan.kufurMu ile ayni tespit.
        if ($this->kufurMu($ham)) {
            $this->teshis = 'kufur';
            $uyariVerildi = false;
            foreach ($gecmis as $m) {
                if (($m['role'] ?? '') === 'assistant'
                    && mb_stripos((string) ($m['content'] ?? ''), 'saygıya davet') !== false) {
                    $uyariVerildi = true;
                    break;
                }
            }
            if ($uyariVerildi) {
                return ['cevap' => 'Maalesef bu şekilde devam edemeyeceğim, görüşmeyi burada sonlandırıyorum. İyi günler dilerim.', 'aksiyon' => 'veda', 'veri' => [], 'bitir' => true];
            }
            return ['cevap' => 'Efendim, sizi saygıya davet etmek istiyorum. Eğer böyle konuşmaya devam ederseniz maalesef görüşmeyi sonlandırmak zorunda kalacağım.', 'aksiyon' => null, 'veri' => [], 'bitir' => false];
        }

        // MEVCUT REZERVASYON — GARANTI UYARI (Haiku'ya birakma; model gomulu kurali atliyor).
        // Arayanin aktif rezervasyonu varsa + rezervasyon niyeti varsa + daha once uyarilmadiysa:
        // deterministik olarak hatirlat (cift rezervasyonu/karisikligi onler).
        if (!empty($this->mevcutRez) && ($this->rezervasyonNiyeti($ham) || $this->gecmisteRezervasyonKonusu($gecmis))) {
            $uyarildi = false;
            foreach ($gecmis as $m) {
                if (($m['role'] ?? '') === 'assistant' && mb_stripos((string) ($m['content'] ?? ''), 'rezervasyonunuz görünüyor') !== false) { $uyarildi = true; break; }
            }
            if (!$uyarildi) {
                $liste = implode('; ', array_map(fn ($r) => $this->dogalTarih($r['tarih']) . ' saat ' . $r['saat'] . "'de, " . $r['kisi'] . ' kişilik', $this->mevcutRez));
                $this->teshis = 'mevcut_rez';
                return ['cevap' => "Zaten $liste rezervasyonunuz görünüyor. Bunu mu değiştirmek istersiniz, iptal mi, yoksa farklı bir gün için ek bir rezervasyon mu?", 'aksiyon' => null, 'veri' => [], 'bitir' => false];
            }
        }

        // --- EGITIM KATMANLARI (Haiku'dan ONCE, bedava) ---
        // 1) KALIP / SSS (sahibin girdigi tetikleyici -> hazir cevap). Her turda; sahip kontrol eder.
        $kalip = $this->kalipCevap($ham);
        if ($kalip !== null) {
            $this->teshis = 'kalip';
            return ['cevap' => $this->ttsTemizle($kalip), 'aksiyon' => null, 'veri' => [], 'bitir' => false];
        }
        // 2) OGRENILEN ONBELLEK — SADECE bilgi sorularinda (siparis/rezervasyon baglamini bozmasin)
        if ($this->bilgiSorusuMu($ham)) {
            $ogr = $this->ogrenilenCevap($this->norm($ham));
            if ($ogr !== null && $ogr !== '') {
                $this->teshis = 'ogrenilen';
                return ['cevap' => $this->ttsTemizle($ogr), 'aksiyon' => null, 'veri' => [], 'bitir' => false];
            }
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
            'max_tokens' => 1000, // kapanis METNI (duygusal karsilik + notlar uzun olabilir) + santral_aksiyon ARAC cagrisi BIRLIKTE sigsin; dar limit araci kesiyor -> rezervasyon/siparis kaydolmuyor
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

        // GUVENLIK AGI: model rezervasyon/siparisi SOZEL tamamladi ("...aldim/olusturdum") ama araci CAGIRMADIYSA
        // -> tool_choice ile ZORLA cikar + kaydet (model araci atlasa bile kayit garanti olsun).
        if ($aksiyon === null && $this->tamamlamaMetni($cevap)) {
            [$za, $zv] = $this->zorlaAksiyonCikar($mesajlar, $cevap);
            if ($za) { $aksiyon = $za; $veri = $zv; $this->teshis = 'zorla_aksiyon'; }
        }

        $cevap = $this->ttsTemizle($cevap);
        if ($cevap === '') {
            // Model sadece arac cagirip metin dondurmediyse: aksiyona gore GARANTI kapanis/metin
            if ($aksiyon === 'aktar') $cevap = 'Sizi yetkiliye bağlıyorum, lütfen hatta kalın.';
            elseif ($aksiyon === 'siparis') $cevap = 'Siparişinizi aldım. Başka bir arzunuz var mı?';
            elseif ($aksiyon === 'rezervasyon') $cevap = 'Rezervasyonunuzu aldım. Başka bir arzunuz var mı?';
            elseif ($aksiyon === 'veda') $cevap = 'Teşekkür ederiz, iyi günler dileriz.';
            else $cevap = 'Anladım, devam edelim.';
        }

        // --- OGRENME: bilgi sorusuysa + aksiyon yoksa + cevap SORU degilse -> onbellege al (tekrar bedava) ---
        if ($aksiyon === null && $cevap !== '' && $this->bilgiSorusuMu($ham)
            && !preg_match('/\?\s*$/u', $cevap) && mb_strlen($cevap) <= 300) {
            $this->ogren($this->norm($ham), $cevap);
        }
        // --- COZULEMEYEN: AI cozemeyip aktardiysa soruyu kaydet (panelde "en cok sorulan cevapsizlar") ---
        if ($aksiyon === 'aktar') $this->cozulemeyenKaydet($ham);

        $this->teshis = 'ok';
        return [
            'cevap' => $cevap,
            'aksiyon' => $aksiyon,
            'veri' => $veri,
            // Hatti SADECE 'veda'da kapat. Siparis/rezervasyon KAYDOLUR ama kapatMAZ ->
            // AI "baska bir arzunuz var mi?" deyip insani sekilde devam eder; musteri bitirince veda.
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
        // EN BASA: mevcut rezervasyon uyarisi (asagida gomulu kalinca model atliyor). Kisi sormadan ONCE soylet.
        if (!empty($this->mevcutRez)) {
            $liste = implode('; ', array_map(fn ($r) => $this->dogalTarih($r['tarih']) . ' saat ' . $r['saat'] . "'de, " . $r['kisi'] . ' kişilik', $this->mevcutRez));
            $p .= "!!! EN ÖNEMLİ KURAL — BU MÜŞTERİNİN ZATEN AKTİF REZERVASYONU VAR: $liste. Müşteri rezervasyondan bahseder bahsetmez (yeni rezervasyon / değişiklik / tarih söyleme) İLK CÜMLENDE, kişi sayısı/tarih SORMADAN ÖNCE bunu MUTLAKA söyle: 'Zaten $liste için rezervasyonunuz görünüyor.' Sonra bunu mu değiştirmek, iptal etmek mi, yoksa farklı bir gün/saat için EK rezervasyon mu istediğini sor. AYNI gün/saate İKİNCİ rezervasyon OLUŞTURMA. Bu kuralı ATLAMA. ";
        }
        // YENI musteri: adini MUTLAKA al (ilk aramada musteri kaydi olussun, sonra adiyla taninsin).
        if (empty($this->musteri) || $this->genelAd($this->musteri->ad ?? '')) {
            $p .= "!!! ÖNEMLİ — ARAYAN YENİ/İSİMSİZ MÜŞTERİ: Rezervasyon veya sipariş alırken ADINI MUTLAKA sor ('Adınızı alabilir miyim?') ve santral_aksiyon'un 'ad' alanına yaz. AD ALMADAN rezervasyon/siparişi tamam=true ile TAMAMLAMA. Böylece ilk aramada müşteri kaydı oluşur, sonraki aramalarda adıyla tanınır. ";
        }
        $p .= "Doğal, sıcak ve ÇOK KISA Türkçe konuş; genellikle tek cümle, en fazla iki kısa cümle. Gereksiz nezaket/uzatma yok, doğrudan konuya gir. ";
        $p .= "ASLA BİLGİ UYDURMA: adres, isim, fiyat, menüde olmayan ürün, geçmiş sipariş/rezervasyon detayı — emin değilsen ya da kayıtlı değilse MÜŞTERİYE SOR. Kayıtlı olmayan bir adresi 'yine şuraya mı' diye VARSAYMA. ";
        $p .= "DUYGUSAL ZEKA: Müşteri özel/duygusal bir durumdan bahsederse önce KISA ve İÇTEN bir karşılık ver, sonra yardıma devam et. Örnekler: doğum günü/yıl dönümü/kutlama -> 'Eşinizin doğum gününü şimdiden kutlarız, çok özel bir akşam olsun'; yıl dönümü -> 'Yıl dönümünüz kutlu olsun'; özür/şikayet -> önce samimi özür; kötü/üzücü haber -> kısa geçmiş olsun/başsağlığı. Tek cümle, samimi ama abartısız; ardından işlemi (rezervasyon/sipariş) sürdür. Özel günse ilgili notu da al (ör. doğum günü -> rezervasyon notuna 'doğum günü'). ";
        $p .= "Müşterinin sözünü KESME; cevabını bitirmesini bekle, yarım duyduysan acele onaylama, 'tam söyleyebilir misiniz?' de. Aynı soruyu döngüye sokma. ";
        // ILK SELAM ZATEN YAPILDI -> tekrar selamlama (en kritik: yoksa her turda 'hos geldiniz' deyip takiliyor)
        $p .= "ÇOK ÖNEMLİ: İlk karşılama (merhaba / hoş geldiniz) ZATEN yapıldı. Bundan sonraki yanıtlarında TEKRAR selam verme, 'hoş geldiniz' DEME, kendini tekrar tanıtma. Doğrudan müşterinin söylediğine yanıt ver. Örnek: müşteri 'sipariş vermek istiyorum' derse SADECE 'Tabii, ne almak istersiniz?' de (yeniden hoş geldiniz deme). ";
        $p .= "TTS ile seslendirileceğin için DÜZ metin yaz: emoji, madde işareti, yıldız, tırnak KULLANMA. ";
        $p .= "Görüşmeyi kapatırken DOĞAL ve kısa veda et: 'İyi günler', 'Afiyet olsun, iyi günler' veya 'Görüşmek üzere, iyi günler'. 'Hoş kalın' / 'hoşça kalın' DEME (telefonda tuhaf/samimiyetsiz duruyor). ";
        $p .= "Görevlerin: karşılama; çalışma saati, adres ve menü hakkında bilgi vermek; REZERVASYON almak; PAKET SİPARİŞ almak; gerektiğinde yetkiliye aktarmak. ";
        // ZAMAN BAGLAMI: AI bugunun tarihini bilmezse "yarin" deyince "ayin kaci" diye sorar -> tarihi enjekte et.
        // NOT: dakika/saat KOYMA (her turda degisir -> prompt cache bozulur). Tarih + kaba dilim (cagri boyunca sabit).
        $ist = now()->setTimezone('Europe/Istanbul');
        $gunAdlari = ['Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba', 'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi', 'Sunday' => 'Pazar'];
        $gunAd = $gunAdlari[$ist->format('l')] ?? '';
        $saatN = (int) $ist->format('H');
        $dilim = $saatN < 6 ? 'gece' : ($saatN < 11 ? 'sabah' : ($saatN < 17 ? 'gündüz' : ($saatN < 22 ? 'akşam' : 'gece')));
        $p .= "ZAMAN: Bugün $gunAd, " . $ist->format('Y-m-d') . " (Türkiye, şu an $dilim). 'Bugün' = " . $ist->format('Y-m-d') . ", 'yarın' = " . $ist->copy()->addDay()->format('Y-m-d') . ". Müşteri 'bugün / yarın / bu akşam / hafta sonu / cumartesi' gibi derse tarihi SEN hesapla ve YYYY-MM-DD'ye çevir; müşteriye ASLA 'ayın kaçı' diye SORMA. Saati de HH:MM yap ('akşam 8' = 20:00, 'öğlen' = 12:00). ";
        $p .= "Müşteriye tarihi SÖYLERKEN doğal/Türkçe konuş, ham tarih (2026-10-07 gibi) ya da robotik ifade KULLANMA: bugünse 'bugün', yarınsa 'yarın', bu haftaysa gün adı ('bu çarşamba' / 'çarşamba'), gelecek haftaysa 'haftaya çarşamba'; yalnızca 2+ hafta ilerideyse '21 Ekim' gibi gün+ay söyle. (santral_aksiyon aracına ise HER ZAMAN YYYY-MM-DD ver.) ";
        $p .= "SAAT + KİŞİ SAYISINI peş peşe söyleme; araya 'de' ve VİRGÜL koy (ör. 'saat 20:00'de, 2 kişilik'), yoksa seslendirmede '20:00 2' birleşip '22 kişi' gibi duyulur. ";
        $p .= "REZERVASYON için gereken bilgiler: kişi sayısı, tarih ve saat. TELEFON numarasını arayan hattan biliyorsun; SORMA ve sesli OKUMA/tekrar etme. ADINI: kayıtlı müşteriyse zaten biliyorsun, TEKRAR SORMA; kayıtlı değilse adını yalnızca BİR KEZ nazikçe sor. Göreceli tarih ifadelerini (yarın, bu akşam) kendin çöz, müşteriye tarih/ayın kaçı diye sorma. Eksik olanları (kişi/tarih/saat) TEK TEK, kısa sorularla iste; tamamlanınca müşteriye SADECE kişi sayısı/tarih/saat'i tekrar edip onay al, sonra santral_aksiyon aracını niyet=rezervasyon, tarih=YYYY-MM-DD, saat=HH:MM ve tamam=true ile çağır. ";
        $p .= "EN KRİTİK KAYIT KURALI: Müşteri onay verdiği an (tamam / olur / onaylıyorum / evet) AYNI yanıtında MUTLAKA santral_aksiyon aracını çağır — rezervasyonda niyet=rezervasyon + kisi + tarih=YYYY-MM-DD + saat=HH:MM + varsa not; siparişte niyet=siparis + kalemler + odeme; her ikisinde tamam=true. Sadece 'rezervasyonunuzu/siparişinizi aldım, onaylıyorum' DEMEK YETMEZ; aracı çağırmazsan sisteme HİÇBİR ŞEY KAYDEDİLMEZ. Onay anındaki metnini kısa tut ki araç çağrısı da sığsın. ";
        $p .= "KAPANIŞ (insani): Rezervasyon/sipariş aracını çağırdıktan sonra görüşmeyi HEMEN KAPATMA ve 'iyi günler' deyip bitirme. Kısaca teyit et ve 'Başka bir arzunuz var mı?' / 'Yardımcı olabileceğim başka bir şey var mı?' diye sor. Müşteri 'yok / hayır / teşekkürler / sağ olun' gibi bitirdiğinde SICAK bir veda et (ör. 'Rica ederiz, iyi günler, görüşmek üzere') ve santral_aksiyon niyet=veda, tamam=true çağır — hat ANCAK o zaman kapanır. Müşteri başka bir şey isterse yardıma devam et. ";
        $p .= "ÖZEL NOT/İSTEK: Müşteri rezervasyona özel bir not/istek eklemek isterse (ör. 'özel bir şey isteyeceğim', 'not alır mısınız', pencere kenarı, doğum günü, pasta, bebek sandalyesi, alerji, sessiz köşe) bunu YETKİLİYE AKTARMA; SEN al. 'Tabii, notunuzu alıyorum, buyurun' de, dinle, sonra kısaca teyit et ('... diye not düştüm') ve bu metni santral_aksiyon rezervasyon.not alanına yaz. Not, rezervasyon onayından önce alınır. ";
        // PAKET SIPARIS: KESIN SIRALI script. Adimlari ATLAMA, KARISTIRMA, geri donme.
        $p .= "PAKET SİPARİŞ tam olarak bu SIRAYLA ilerler, adımları karıştırma: ";
        $p .= "1) Ürün ve adetleri al (SADECE menüden, olmayan ürünü uydurma). ";
        $p .= "2) Ürünleri aldıktan hemen sonra, SİPARİŞİ BİTİRMEDEN ÖNCE, menüden tek bir içecek VEYA tatlı öner (tek cümle, kibar). Müşteri istemezse ya da 'istemiyorum/olmasın' derse HEMEN kabul et, bir daha önerme ve 3. adıma geç. ";
        $p .= "3) Teslimat adresini sor. Müşteri adresi (sokak, kapı no, mahalle, ilçe) söylerken SÖZÜNÜ KESME, TAMAMINI bekle; parça parça sorgulama. Sadece gerçekten eksik bir parça varsa o parçayı bir kez sor. Müşteri 'adres yanlış' derse ya da düzeltmek isterse: 'Tam adresinizi baştan söyleyebilir misiniz?' de ve tamamını dinle, acele onaylama. Adresi onaylarken müşterinin söylediği HER parçayı (sokak+no+mahalle+ilçe) eksiksiz tekrar et. ";
        $p .= "4) Telefon numarasını sor (arayan numarası biliniyorsa tekrar sorma). ";
        $p .= "4b) Müşterinin adını BİLMİYORSAN (yeni ya da kayıtlı ama ismi olmayan müşteri) adını bir kez nazikçe sor; biliyorsan sorma. Aldığın adı MUTLAKA santral_aksiyon siparis.ad alanına yaz (CRM için kaydedilecek, sonraki aramada adıyla karşılanacak). ";
        $p .= "5) Siparişi KISACA özetle ve onay al. ";
        $p .= "6) Onaydan sonra ödeme yöntemini sor: 'Ödemeyi kapıda nakit mi, kartla mı almamı istersiniz?'. ";
        $p .= "7) Ödeme yanıtını alır almaz BAŞKA HİÇBİR ŞEY SORMA/ÖNERME; santral_aksiyon aracını niyet=siparis, tamam=true ve siparis.odeme (kapida_nakit veya kapida_kart) ile çağır ve AYNI yanıtta KAPANIŞ cümlesini söyle: 'Siparişinizi ilettim, en kısa sürede hazırlayıp yola çıkaracağız, afiyet olsun, iyi günler'. ";
        $p .= "KURAL: İçecek/tatlı önerisi SADECE 2. adımda bir kez yapılır. 5., 6. ve 7. adımlardan sonra ASLA yeni ürün önerme, ekstra soru sorma; sadece akışı ilerlet ve kapat. Müşteri bir adımda 'istemiyorum' derse o adımı kapat ve BİR SONRAKİ adıma geç, takılıp bekleme. ";
        // Menu tanitimi: telefonda UZUN liste okuma; birkac one cikan urunu/kategoriyi kisaca soyle, sonra ne istedigini sor. ASLA aktarma.
        $p .= "Müşteri 'neler var', 'menüde ne var', 'tanıtır mısın' gibi genel sorarsa: menüden EN FAZLA üç dört öne çıkan ürünü ya da ana yemek türlerini KISACA say, sonra 'ne almak istersiniz?' diye sor. ";
        // Belirli bir tur/cesit sorulunca MENUDEN FIYATLARIYLA say; asla bos birakma.
        $p .= "Müşteri belirli bir tür için seçenek sorarsa (örn. 'hangi pizzalar var', 'pizza çeşitleri', 'ne tür burger var'): o türe uyan ürünleri MENÜDEN, EN FAZLA 5-6 tanesini FİYATLARIYLA kısaca say (örnek: 'Margarita 120 lira, Karışık 150 lira'). Seçenek/çeşit sorulduğunda ASLA boş bırakma, 'hangi çeşit' diye geri sorma; doğrudan menüden oku. ";
        // Fiyat: her zaman menudeki gercek fiyat, 'lira' diyerek.
        $p .= "Fiyat sorulduğunda ya da ürün önerir/eklerken fiyatını menüden 'lira' diyerek söyle (ör. '120 lira'). Menüde olmayan ürünün fiyatını UYDURMA. ";
        $p .= "Menü/çeşit/fiyat sorularında ASLA yetkiliye aktarma. ";
        // Aktarma cok kisitli: sadece sikayet / menu disi cok ozel istek / cozemeyecegin durum. Menu, fiyat, siparis, rezervasyon icin ASLA aktarma.
        $p .= "Yetkiliye aktarmayı SADECE şu durumlarda yap: ciddi şikayet ya da gerçekten çözemeyeceğin bir konu. Menü, fiyat, sipariş, rezervasyon ve rezervasyon/sipariş NOTLARI senin işin; bunlar için ASLA aktarma ve telefonu kapatma. Müşterinin özel isteği/notu AKTARMA SEBEBİ DEĞİLDİR — notu sen al, ilgili aksiyonun not alanına yaz. Aktarırken santral_aksiyon niyet=aktar, tamam=true kullan. ";
        $p .= "Müşteri açıkça vedalaşır ya da 'kapatabilirsin' derse kibarca veda et ve santral_aksiyon niyet=veda, tamam=true ile çağır. Aksi halde görüşmeyi sürdür, kendiliğinden kapatma. ";
        $p .= "'Buyurun' kelimesini tekrar tekrar kullanma. Sadece Türkçe konuş.";

        if ($adres) $p .= " Restoranın adresi: $adres.";
        if ($tel) $p .= " Restoranın telefonu: $tel.";

        $p .= $this->musteriBaglami();
        $p .= $this->rezervasyonBaglami();
        $p .= $this->teslimatBaglami();
        $p .= $this->menuBaglami();

        return $p;
    }

    /** Mevcut rezervasyon (cift onle) + kapasite/doluluk (dolu ise kibarca reddet). */
    protected function rezervasyonBaglami(): string
    {
        $p = ''; // mevcut rezervasyon uyarisi artik promptun EN BASINDA (sistemPromptu) veriliyor.
        try {
            if (Schema::hasTable('masalar') && Schema::hasColumn('masalar', 'kapasite')) {
                $kap = (int) DB::table('masalar')->where('sube_id', $this->subeId)->sum('kapasite');
                if ($kap > 0) {
                    $ist = now()->setTimezone('Europe/Istanbul');
                    $bugun = $ist->format('Y-m-d');
                    $son = $ist->copy()->addDays(7)->format('Y-m-d');
                    $satir = [];
                    foreach (DB::table('rezervasyonlar')->where('sube_id', $this->subeId)
                        ->whereIn('durum', ['bekliyor', 'onaylandi'])->where('tarih', '>=', $bugun)->where('tarih', '<=', $son)
                        ->select('tarih', DB::raw('SUM(kisi) as kisi'))->groupBy('tarih')->orderBy('tarih')->get() as $y) {
                        $satir[] = $y->tarih . ': ' . (int) $y->kisi . '/' . $kap;
                    }
                    $p .= " RESTORAN KAPASİTESİ yaklaşık $kap kişi. Yaklaşan günlerin doluluğu (rezerve kişi/kapasite): " . ($satir ? implode(', ', $satir) : 'tüm günler boş') . ". ";
                    $p .= "Rezervasyon alırken: istenen gün+kişi sayısı kapasiteyi aşıyorsa ya da gün doluysa, KAYDETME; kibarca o günün/saatin dolu olduğunu söyle ve en yakın uygun gün/saati öner. Uygunsa normal devam et. (Doluluk yaklaşıktır, emin değilsen kibarca teyit ederek ilerle.) ";
                }
            }
        } catch (\Throwable $e) {}
        return $p;
    }

    /** Teslimat bolgesi kurali (panelden): AI adres alinca bolge disini nazikce reddeder. */
    protected function teslimatBaglami(): string
    {
        $bolge = '';
        try {
            if (Schema::hasTable('santral_ayarlari') && Schema::hasColumn('santral_ayarlari', 'teslimat_bolge')) {
                $bolge = trim((string) DB::table('santral_ayarlari')->where('sube_id', $this->subeId)->value('teslimat_bolge'));
            }
        } catch (\Throwable $e) {}
        if ($bolge === '') return '';
        $p = " TESLİMAT BÖLGESİ (paket sipariş için ZORUNLU kural): " . $bolge . " ";
        $p .= "Paket siparişte teslimat adresini aldıktan HEMEN SONRA, siparişi tamamlamadan ÖNCE adresin bu bölge içinde olup olmadığını değerlendir. ";
        $p .= "Adres bölge DIŞINDAYSA (örn. başka il/ilçe, çok uzak) siparişi ALMA; nazikçe söyle: 'Maalesef o adrese teslimat yapamıyoruz, teslimat bölgemiz: " . mb_substr($bolge, 0, 160) . ". Dilerseniz gel-al olarak hazırlayabiliriz.' ";
        $p .= "Bölge içindeyse normal devam et. Sınırda/emin olamadığın adreste kibarca 'adresinizi tam söyler misiniz, teslimat alanımızda mı teyit edeyim' de. İstanbul gibi apaçık uzak bir adres verilirse kesinlikle kabul etme. ";
        return $p;
    }

    /** Arayan musteri baglami: tanima + adiyla hitap + kayitli adres/telefon + gecen siparis + "ayni siparis". */
    protected function musteriBaglami(): string
    {
        // YENI musteri
        if (!$this->musteri) {
            $p = " ARAYAN YENİ müşteri (sistemde kaydı yok). ÖNEMLİ: Rezervasyon VEYA sipariş alırken İLK İŞ olarak adını nazikçe sor ('Adınızı alabilir miyim?') ve kaydet; AD ALINMADAN rezervasyon/siparişi tamamlama (santral_aksiyon tamam=true çağırma). Adı santral_aksiyon'un 'ad' alanına MUTLAKA yaz (bu ilk aramada müşteri kaydı oluşturulur, sonraki aramalarda adıyla tanınır). ";
            if ($this->telefon) $p .= "Telefon numarasını TEKRAR SORMA; arayan numarası ($this->telefon) otomatik kullanılır. Pakette teslimat adresini de sor. ";
            return $p;
        }
        // KAYITLI musteri
        $m = $this->musteri;
        // Adsiz (generic 'Telefon...') + gecmis siparis/adres YOK -> aslinda TANINMIYOR (onceki yarim cagriden kalan
        // telefon kaydi). "Sizi taniyorum" dememeli; YENI musteri gibi davranip adini sormali.
        if ($this->genelAd($m->ad) && empty($this->sonSiparis) && empty($m->adres)) {
            $p = " ARAYAN YENİ müşteri gibi (numara sistemde var ama adı/geçmişi YOK). ÖNEMLİ: 'Sizi tanıyorum' DEME, önceki görüşmeye atıf YAPMA. Rezervasyon veya sipariş alırken ADINI MUTLAKA sor ('Adınızı alabilir miyim?') ve santral_aksiyon 'ad' alanına yaz; AD ALMADAN tamam=true yapma. ";
            if ($this->telefon) $p .= "Telefon numarasını arayan hattan biliyorsun; SORMA. Pakette teslimat adresini sor. ";
            return $p;
        }
        $p = " ARAYAN KAYITLI MÜŞTERİ (daha önce aramış, onu tanıyorsun).";
        if (!$this->genelAd($m->ad)) $p .= " Adı: " . $m->ad . " (uygun yerde adıyla hitap et, ama yeniden selam/hoş geldin deme).";
        else $p .= " Adını henüz bilmiyoruz; uygun bir yerde (ör. sipariş alırken) BİR KEZ nazikçe adını sor, kaydedilecek. ";
        if (!empty($m->adres)) $p .= " Kayıtlı teslimat adresi: " . $m->adres . ".";
        if (!empty($m->telefon)) $p .= " Telefonu: " . $m->telefon . ".";
        if (!empty($this->sonSiparis)) {
            $ozet = implode(', ', array_map(fn ($k) => $k['adet'] . ' ' . $k['urun'], $this->sonSiparis));
            $p .= " Geçen siparişi: " . $ozet . ".";
        }
        $p .= " KAYITLI MÜŞTERİ KURALLARI: Adını ve telefonunu TEKRAR SORMA. ";
        if (!empty($m->adres)) {
            $p .= "Teslimat adresi KAYITLI (" . $m->adres . "): paket siparişte tekrar sorma, sadece 'Teslimat yine $m->adres olsun mu?' diye ONAYLA. ";
        } else {
            // KRITIK: kayitli adres YOKSA adres UYDURMA -> MUTLAKA sor (halüsinasyon kacagi).
            $p .= "Teslimat adresi KAYITLI DEĞİL. Paket siparişte teslimat adresini MUTLAKA müşteriye sor; ASLA bir adres UYDURMA, varsayma veya 'yine şuraya mı' deme. ";
        }
        $p .= "Rezervasyonda sadece kişi sayısı, tarih ve saati sor. Telefon numarasını sesli OKUMA. ";
        if (!empty($this->sonSiparis)) {
            $ozet = implode(', ', array_map(fn ($k) => $k['adet'] . ' ' . $k['urun'], $this->sonSiparis));
            $p .= "ÖNEMLİ — GEÇEN SİPARİŞ: Müşteri sipariş vermek isteyince (örn. 'sipariş vermek istiyorum') İLK İŞ olarak geçen siparişini PROAKTİF hatırlat ve aynısını isteyip istemediğini sor: 'Tabii, geçen sefer $ozet almıştınız; aynısını ister misiniz, yoksa farklı bir şey mi?'. ";
            $p .= "Müşteri 'evet / aynısı / geçen seferki gibi / her zamanki' derse geçen siparişteki ürünleri ($ozet) sipariş kalemleri olarak AL, teslimat adresini kayıtlıdan onayla, ödeme yöntemini sor ve siparişi tamamla. Farklı bir şey isterse normal akışla devam et. ";
        }
        $p .= "Adres değiştiyse yeni adresi al. Yeni müşteri değil, onu tanıdığını hissettir. ";
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
                            'not' => ['type' => 'string', 'description' => 'Musterinin ozel istegi/notu ( or. pencere kenari, dogum gunu, bebek sandalyesi, alerji). Varsa buraya yaz.'],
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
                            'ad' => ['type' => 'string', 'description' => 'Musteri adi (bilinmiyorsa sorulup buraya yazilir; CRM/tanima icin kaydedilir)'],
                            'adres' => ['type' => 'string'],
                            'telefon' => ['type' => 'string'],
                            'odeme' => ['type' => 'string', 'enum' => ['kapida_nakit', 'kapida_kart', 'online'], 'description' => 'Odeme yontemi: kapida_nakit | kapida_kart | online (kredi karti link)'],
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

    /** Genis kufur/hakaret tespiti (kelime sinirinda; sikayet/sikinti/malzeme gibi masum kelimeleri tetiklemez). MusteriAsistan ile ayni. */
    protected function kufurMu($c)
    {
        $n = ' ' . $this->norm($c) . ' ';
        $set = [
            'amk', 'amq', 'aq', 'amina', 'aminako', 'amcik', 'amcigin', 'amina koyay', 'amina kodu', 'aminakoyum',
            'anani sik', 'ananisik', 'anasini sik', 'avradini', 'avradina', 'avradinin', 'sulaleni', 'sulaleni sik', 'sikeyim seni',
            'orospu', 'orospucocu', 'orospu cocu', 'o cocugu', 'pic ', 'picler', 'piclik', 'kahpe', 'kahpelik',
            'surtuk', 'yavsak', 'yavsagi', 'pezevenk', 'gavat', 'godos', 'ibne', 'ibnelik', 'pust', 'kaltak', 'kevase',
            'serefsiz', 'namussuz', 'sik tir', 'siktir', 'sikeyim', 'sikeym', 'sikik', 'sikko', 'sikici', 'siktigim', 'sikims', 'sikimde', 'sikimsonik',
            'yarrak', 'yarrag', 'yarak', 'tasak', 'tasagi', 'gotveren', 'gotlek', 'gotunden', 'gotune koy',
            'boktan', 'bokla', 'boklu', 'bok herif', 'bok cuval', 'sicayim', 'sicarim', 'osuruk',
            'salak', 'aptal', 'gerizekali', 'gerzek', 'dangalak', 'denyo', 'embesil', 'ahmak', 'dallama', 'hoduk', 'mal herif', 'mal misin', 'defol', 'gebersin', 'geber',
        ];
        foreach ($set as $k) {
            $kk = $this->norm($k);
            if ($kk === '') continue;
            if (preg_match('/(?:^| )' . preg_quote($kk, '/') . '/u', $n)) return true;
        }
        return false;
    }

    /** YYYY-MM-DD -> dogal Turkce ifade: bugün / yarın / bu çarşamba / haftaya çarşamba / '21 Ekim'. */
    protected function dogalTarih($ymd): string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $ymd)) return (string) $ymd;
        $hedefTs = strtotime($ymd . ' 00:00:00');
        $bugunTs = strtotime(now()->setTimezone('Europe/Istanbul')->format('Y-m-d') . ' 00:00:00');
        if (!$hedefTs) return (string) $ymd;
        $fark = (int) round(($hedefTs - $bugunTs) / 86400);
        if ($fark === 0) return 'bugün';
        if ($fark === 1) return 'yarın';
        if ($fark === -1) return 'dün';
        $gunAd = [1 => 'pazartesi', 2 => 'salı', 3 => 'çarşamba', 4 => 'perşembe', 5 => 'cuma', 6 => 'cumartesi', 7 => 'pazar'];
        $gun = $gunAd[(int) date('N', $hedefTs)] ?? '';
        $hbBugun = $bugunTs - ((int) date('N', $bugunTs) - 1) * 86400; // haftanin pazartesisi
        $hbHedef = $hedefTs - ((int) date('N', $hedefTs) - 1) * 86400;
        $haftaFark = (int) round(($hbHedef - $hbBugun) / (7 * 86400));
        if ($fark > 1 && $haftaFark === 0) return 'bu ' . $gun;
        if ($haftaFark === 1) return 'haftaya ' . $gun;
        $aylar = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
        return (int) date('j', $hedefTs) . ' ' . ($aylar[(int) date('n', $hedefTs)] ?? '');
    }

    /** Mesaj rezervasyon niyeti tasiyor mu? (mevcut rezervasyon garanti-uyarisi icin) */
    protected function rezervasyonNiyeti($q): bool
    {
        $n = ' ' . $this->norm($q) . ' ';
        foreach (['rezervasyon', 'rezerve', 'yer ayirt', 'yer ayir', 'masa ayirt', 'masa ayir', 'yer bakt', 'masa bakt'] as $a) {
            if (strpos($n, ' ' . $this->norm($a)) !== false) return true;
        }
        return false;
    }

    /** Gecmiste rezervasyon konusu gecti mi? (callback'te "olur" deyince de mevcut-rez uyarisi tetiklensin) */
    protected function gecmisteRezervasyonKonusu(array $gecmis): bool
    {
        foreach ($gecmis as $m) {
            if (strpos($this->norm((string) ($m['content'] ?? '')), 'rezervasyon') !== false) return true;
        }
        return false;
    }

    /** Arayanin mevcut aktif rezervasyonlari — DOGAL metin (karsilama/uyari icin). Bossa ''. */
    public function mevcutRezMetni(): string
    {
        if (empty($this->mevcutRez)) return '';
        return implode('; ', array_map(fn ($r) => $this->dogalTarih($r['tarih']) . ' saat ' . $r['saat'] . "'de, " . $r['kisi'] . ' kişilik', $this->mevcutRez));
    }

    protected function norm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
        $s = strtr($s, $tr);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }

    // ==================== EGITIM: kalip + ogrenen onbellek + cozulemeyen ====================

    /** Soru bagimsiz BILGI sorusu mu? (onbellek/ogrenme yalniz bunlarda; siparis/rezervasyon baglamini bozmaz) */
    protected function bilgiSorusuMu($q): bool
    {
        $n = ' ' . $this->norm($q) . ' ';
        $anahtarlar = [
            'saat', 'acik', 'kapali', 'kacta', 'kacda', 'acilis', 'kapanis', 'aciksiniz', 'kapaniyor',
            'nerede', 'neredesiniz', 'adres', 'yol', 'tarif', 'konum', 'otopark', 'park yeri',
            'wifi', 'kablosuz', 'internet sifre', 'ne kadar', 'kaca', 'kac para', 'fiyat', 'ucret',
            'paket var', 'paket geliyor', 'kurye', 'teslimat', 'minimum', 'sepet tutar', 'kac tl',
            'calisiyor', 'calisma saat', 'servis var', 'rezervasyon aliyor',
        ];
        foreach ($anahtarlar as $a) {
            $a = $this->norm($a);
            if ($a !== '' && strpos($n, ' ' . $a) !== false) return true;
        }
        return false;
    }

    protected function _tetikVar($n, $t): bool
    {
        $t = trim($t);
        if ($t === '') return false;
        $nn = ' ' . $n . ' ';
        if (strpos($nn, ' ' . $t . ' ') !== false) return true;
        if (mb_strlen($t) >= 4 && strpos($nn, $t) !== false) return true;
        // cogul toleransi
        $tt = preg_replace('/(lar|ler)$/u', '', $t);
        if ($tt !== $t && mb_strlen($tt) >= 3 && strpos($nn, $tt) !== false) return true;
        return false;
    }

    // ---- KALIP / SSS (sahip tanimli tetikleyici -> hazir cevap) ----
    public static function kalipTablo()
    {
        if (!Schema::hasTable('santral_kalip')) {
            Schema::create('santral_kalip', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->boolean('aktif')->default(1);
                $t->text('tetikleyiciler');   // virgulle ayrilmis anahtar kelimeler
                $t->text('cevap');
                $t->string('kategori', 40)->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'aktif']);
            });
        }
    }

    protected function kalipCevap($q)
    {
        try {
            self::kalipTablo();
            $n = $this->norm($q);
            if ($n === '') return null;
            $rows = DB::table('santral_kalip')->where('sube_id', $this->subeId)->where('aktif', 1)->get(['tetikleyiciler', 'cevap']);
            foreach ($rows as $r) {
                foreach (explode(',', (string) $r->tetikleyiciler) as $tet) {
                    $tn = $this->norm($tet);
                    if ($tn !== '' && $this->_tetikVar($n, $tn)) return $r->cevap;
                }
            }
        } catch (\Throwable $e) {}
        return null;
    }

    // ---- OGRENEN ONBELLEK (soru -> Haiku cevabi; tekrar bedava) ----
    public static function ogrenTablo()
    {
        if (!Schema::hasTable('santral_ai_ogrenilen')) {
            Schema::create('santral_ai_ogrenilen', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('soru_key', 191);
                $t->text('cevap');
                $t->unsignedInteger('kullanim')->default(1);
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'soru_key']);
            });
        }
    }

    protected function ogrenilenCevap($key)
    {
        try {
            self::ogrenTablo();
            $row = DB::table('santral_ai_ogrenilen')->where('sube_id', $this->subeId)->where('soru_key', mb_substr($key, 0, 191))->first();
            if ($row) {
                try { DB::table('santral_ai_ogrenilen')->where('id', $row->id)->increment('kullanim'); } catch (\Throwable $e) {}
                return $row->cevap;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    protected function ogren($key, $cevap)
    {
        try {
            self::ogrenTablo();
            $key = mb_substr($key, 0, 191);
            // ayni soru zaten ogrenildiyse tekrar ekleme
            if (DB::table('santral_ai_ogrenilen')->where('sube_id', $this->subeId)->where('soru_key', $key)->exists()) return;
            DB::table('santral_ai_ogrenilen')->insert(['sube_id' => $this->subeId, 'soru_key' => $key, 'cevap' => $cevap, 'kullanim' => 1, 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }

    // ---- COZULEMEYEN (AI cozemeyip aktardigi sorular; panelde egitim geri bildirimi) ----
    public static function cozulemeyenTablo()
    {
        if (!Schema::hasTable('santral_cozulemeyen')) {
            Schema::create('santral_cozulemeyen', function ($t) {
                $t->increments('id');
                $t->unsignedBigInteger('sube_id');
                $t->string('soru_norm', 191);
                $t->text('ham')->nullable();
                $t->unsignedInteger('adet')->default(1);
                $t->timestamp('son_tarih')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['sube_id', 'soru_norm']);
            });
        }
    }

    protected function cozulemeyenKaydet($soru)
    {
        try {
            $ham = trim((string) $soru);
            if (mb_strlen($ham) < 2) return;
            $norm = mb_substr($this->norm($ham), 0, 191);
            if ($norm === '') return;
            self::cozulemeyenTablo();
            $row = DB::table('santral_cozulemeyen')->where('sube_id', $this->subeId)->where('soru_norm', $norm)->first();
            if ($row) DB::table('santral_cozulemeyen')->where('id', $row->id)->update(['adet' => $row->adet + 1, 'son_tarih' => now(), 'ham' => $ham]);
            else DB::table('santral_cozulemeyen')->insert(['sube_id' => $this->subeId, 'soru_norm' => $norm, 'ham' => $ham, 'adet' => 1, 'son_tarih' => now(), 'created_at' => now()]);
        } catch (\Throwable $e) {}
    }

    /** PDF (base64) -> Claude soru-cevap kaliplari cikarir (JSON). Doner: [ok(bool), veri(dizi|hata-metni)]. */
    public function pdftenKalipCikar($base64Pdf, $mediaType = 'application/pdf')
    {
        if (!$this->apiKey()) return [false, 'AI anahtarı tanımlı değil (ANTHROPIC_API_KEY).'];
        $sistem = 'Sen bir RESTORANIN TELEFON asistani icin SORU-CEVAP kalibi cikaran yardimcisin. Verilen belgeden (menu, SSS, kurumsal bilgi) telefonla arayan musterilerin soracagi olasi sorulari ve KISA cevaplari cikar. '
            . 'KURALLAR: 1) SADECE gecerli JSON DIZI dondur, baska hicbir metin yazma. '
            . '2) Her oge tam olarak: {"tetikleyiciler":"...","cevap":"...","kategori":"..."} . '
            . '3) tetikleyiciler CUMLE DEGIL, virgulle ayrilmis KISA anahtar kelimeler (2-5 tane, es anlamli). Ornek: "otopark, park yeri, arac". '
            . '4) cevap KISA/net, telefonda seslendirilecek (duz metin, 1-2 cumle, emoji/tirnak yok). 5) Belgede OLMAYAN bilgi UYDURMA. En fazla 40 oge.';
        $govde = [
            'model' => $this->model(),
            'max_tokens' => 4000,
            'system' => $sistem,
            'messages' => [['role' => 'user', 'content' => [
                ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => $mediaType, 'data' => $base64Pdf]],
                ['type' => 'text', 'text' => 'Bu belgeden telefon asistani icin soru-cevap kaliplarini cikar ve SADECE JSON dizi dondur.'],
            ]]],
        ];
        $data = $this->cagir($govde);
        if (!$data || empty($data['content'])) return [false, 'AI yanıt vermedi (anahtar/bakiye kontrol edin).'];
        $t = '';
        foreach ($data['content'] as $b) if (($b['type'] ?? '') === 'text') $t .= $b['text'] ?? '';
        $t = trim($t);
        if (preg_match('/\[.*\]/s', $t, $m)) $t = $m[0];
        $arr = json_decode($t, true);
        if (!is_array($arr)) return [false, 'AI çıktısı JSON olarak çözümlenemedi.'];
        $out = [];
        foreach ($arr as $o) {
            if (!is_array($o)) continue;
            $tet = trim((string) ($o['tetikleyiciler'] ?? ''));
            $cev = trim((string) ($o['cevap'] ?? ''));
            if ($tet === '' || $cev === '') continue;
            $out[] = ['tetikleyiciler' => mb_substr($tet, 0, 500), 'cevap' => mb_substr($cev, 0, 2000), 'kategori' => mb_substr((trim((string) ($o['kategori'] ?? '')) ?: 'pdf'), 0, 40)];
        }
        if (empty($out)) return [false, 'Belgeden kalıp çıkarılamadı.'];
        return [true, $out];
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

    /** Cevap metni rezervasyon/siparisin SOZEL tamamlandigini gosteriyor mu? (guvenlik agi tetigi) */
    protected function tamamlamaMetni($cevap): bool
    {
        $n = $this->norm($cevap);
        foreach (['rezervasyonunuzu aldim', 'rezervasyonunuz alindi', 'rezervasyonu aldim', 'rezervasyonunuzu olusturdum',
                  'siparisinizi aldim', 'siparisiniz alindi', 'siparisi aldim',
                  'olusturdum', 'kaydettim', 'ayirttim', 'onayliyorum', 'onayladim', 'yola cikacak', 'hazirlayip'] as $k) {
            if (strpos($n, $this->norm($k)) !== false) return true;
        }
        return false;
    }

    /** Model araci cagirmayi atladiysa: tool_choice ile ZORLA rezervasyon/siparis cikar (kayit garanti). */
    protected function zorlaAksiyonCikar(array $mesajlar, $sonCevap): array
    {
        try {
            $m = $mesajlar;
            $m[] = ['role' => 'assistant', 'content' => (string) $sonCevap];
            $bugun = now()->setTimezone('Europe/Istanbul')->format('Y-m-d');
            $govde = [
                'model' => $this->model(),
                'max_tokens' => 500,
                'system' => [['type' => 'text', 'text' =>
                    'Yukarıdaki görüşmede TAMAMLANMIŞ bir rezervasyon veya paket sipariş var. santral_aksiyon aracını DOLDURUP çağır: '
                    . 'niyet=rezervasyon ya da siparis, tamam=true. Görüşmeden çıkardığın TÜM alanları yaz '
                    . '(rezervasyonda ad/kisi/tarih/saat/not; siparişte kalemler/ad/adres/odeme). '
                    . 'Göreceli tarihleri (yarın, cumartesi, önümüzdeki cumartesi) bugüne göre YYYY-MM-DD çevir; saat HH:MM. Bugün: ' . $bugun . '.']],
                'tools' => [$this->aksiyonAraci()],
                'tool_choice' => ['type' => 'tool', 'name' => 'santral_aksiyon'],
                'messages' => $m,
            ];
            $data = $this->cagir($govde);
            if ($data && !empty($data['content'])) {
                foreach ($data['content'] as $b) {
                    if (($b['type'] ?? '') === 'tool_use' && ($b['name'] ?? '') === 'santral_aksiyon') {
                        $in = $b['input'] ?? [];
                        $niyet = $in['niyet'] ?? null;
                        if (in_array($niyet, ['rezervasyon', 'siparis'], true)) return [$niyet, $in];
                    }
                }
            }
        } catch (\Throwable $e) {}
        return [null, []];
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
        // TELEFON NUMARASI: TTS "beş yüz kırk bir milyar..." diye OKUMASIN -> 10+ haneli
        // numarayi (araya bosluk/tire/parantez girebilir) rakam rakam okut. Tarih(8)/fiyat/saat/yil
        // etkilenmez (esik 10 hane = telefon). Ornek: "5412948144" -> "5 4 1 2 9 4 8 1 4 4".
        $t = preg_replace_callback('/\d[\d \-\.\(\)]{6,}\d/u', function ($m) {
            $d = preg_replace('/\D/', '', $m[0]);
            if (strlen($d) < 10) return $m[0]; // telefon degil -> dokunma
            return implode(' ', str_split($d));
        }, $t);
        // TELAFFUZ: TTS'in yanlis okudugu yabanci kelimeleri net Turkce karsiligiyla degistir
        // (or. burger -> hamburger). Varsayilan liste + panelden eklenen (santral_telaffuz).
        $t = $this->telaffuz($t);
        $t = preg_replace('/\s+/u', ' ', $t);
        return trim($t);
    }

    /** TTS'in yanlis/tuhaf okudugu kelimelerin net Turkce karsiligi (kullanicinin kulagiyla duzenlenebilir). */
    public const VARSAYILAN_TELAFFUZ = [
        'burger' => 'hamburger',
        'cheeseburger' => 'çizburger',
        'sandwich' => 'sandviç',
        'nugget' => 'naget',
        'nuggets' => 'naget',
        'wrap' => 'dürüm',
        'barbecue' => 'barbekü',
        'bbq' => 'barbekü',
        'ketchup' => 'keçap',
    ];

    public static function telaffuzTablo()
    {
        if (!Schema::hasTable('santral_telaffuz')) {
            Schema::create('santral_telaffuz', function ($t) {
                $t->id();
                $t->unsignedBigInteger('sube_id')->default(0)->index(); // 0 = tum subeler
                $t->string('kelime', 120);
                $t->string('okunus', 200);
                $t->timestamp('created_at')->useCurrent();
            });
        }
    }

    /** kelime(kucuk) => okunus — varsayilanlar + bu subenin (ve global) ozel kayitlari (ozel ezer). */
    protected function telaffuzMap(): array
    {
        if ($this->_telaffuzMap !== null) return $this->_telaffuzMap;
        $map = [];
        foreach (static::VARSAYILAN_TELAFFUZ as $k => $v) $map[mb_strtolower($k, 'UTF-8')] = $v;
        try {
            self::telaffuzTablo();
            foreach (DB::table('santral_telaffuz')->whereIn('sube_id', [0, $this->subeId])->get(['kelime', 'okunus']) as $r) {
                $k = mb_strtolower(trim((string) $r->kelime), 'UTF-8');
                if ($k !== '') $map[$k] = (string) $r->okunus;
            }
        } catch (\Throwable $e) {}
        return $this->_telaffuzMap = $map;
    }

    protected function telaffuz($t)
    {
        $map = $this->telaffuzMap();
        if (!$map) return $t;
        // TAM KELIME degistir (buyuk/kucuk duyarsiz), Turkce harfler dahil.
        return preg_replace_callback('/[\p{L}]+/u', function ($m) use ($map) {
            $k = mb_strtolower($m[0], 'UTF-8');
            return $map[$k] ?? $m[0];
        }, $t);
    }
}

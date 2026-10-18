<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * YasalMetin — Restoran başına yasal belge üretici.
 *
 * Mimari: ŞABLON (genel, bu sınıfta) + İŞLETME VERİSİ (isletme_yasal tablosu, şube bazlı).
 * İşletme sahibi /yasal-bilgiler sayfasından şirket verilerini doldurur; bu sınıf
 * şablona o verileri enjekte ederek sözleşme/politika metnini otomatik üretir.
 *
 * Belge tipleri: gizlilik, kullanim, kvkk, mesafeli, cerez, iletisim, sss
 */
class YasalMetin
{
    protected int $subeId;
    protected array $v = [];       // doldurulmuş değerler
    protected array $eksik = [];   // boş bırakılan zorunlu alanlar

    /** isletme_yasal alan tanımları: anahtar => [etiket, zorunlu?, placeholder/ipucu] */
    public static function alanlar(): array
    {
        return [
            'unvan'          => ['Ticari Ünvan', true,  'Örn: XYZ Gıda Turizm Ltd. Şti. / Ahmet Yılmaz'],
            'marka_adi'      => ['Marka / İşletme Adı', true, 'Müşteriye görünen ad (örn: Cafe Lezzet)'],
            'adres'          => ['Açık Adres', true,  'Mahalle, cadde, no, ilçe/il'],
            'vergi_dairesi'  => ['Vergi Dairesi', true, 'Örn: Muratpaşa V.D.'],
            'vergi_no'       => ['Vergi No / TCKN', true, 'Tüzel kişi VKN, şahıs işletmesi TCKN'],
            'mersis_no'      => ['MERSİS No', false, 'Varsa (16 hane)'],
            'telefon'        => ['Telefon', true,  '0242 000 00 00'],
            'eposta'         => ['İletişim / KVKK E-posta', true, 'iletisim@isletme.com'],
            'website'        => ['Web Sitesi', false, 'www.isletme.com'],
            'kep_adresi'     => ['KEP Adresi', false, 'Varsa (tüzel kişiler için)'],
            'veri_sorumlusu' => ['KVKK Veri Sorumlusu', false, 'Boşsa ticari ünvan kullanılır'],
            'kvkk_irtibat'   => ['KVKK İrtibat Kişisi', false, 'Boşsa iletişim e-postası kullanılır'],
            'iade_suresi'    => ['Cayma Hakkı Süresi (gün)', false, 'Boşsa 14 gün'],
            'teslimat_bilgi' => ['Teslimat / Servis Şekli', false, 'Örn: Masaya servis, paket, gel-al'],
        ];
    }

    /** Üretilebilen belge tipleri => başlık */
    public static function tipler(): array
    {
        return [
            'gizlilik' => 'Gizlilik Politikası',
            'kullanim' => 'Kullanım Koşulları',
            'kvkk'     => 'KVKK Aydınlatma Metni',
            'mesafeli' => 'Mesafeli Satış Sözleşmesi',
            'cerez'    => 'Çerez Politikası',
            'iletisim' => 'Bize Ulaşın',
            'sss'      => 'Sıkça Sorulan Sorular',
        ];
    }

    public function __construct(int $subeId)
    {
        $this->subeId = $subeId;
        $this->yukle();
    }

    public static function kur(): void
    {
        if (!Schema::hasTable('isletme_yasal')) {
            Schema::create('isletme_yasal', function ($t) {
                $t->increments('id');
                $t->integer('sube_id')->index();
                $t->string('unvan')->nullable();
                $t->string('marka_adi')->nullable();
                $t->text('adres')->nullable();
                $t->string('vergi_dairesi')->nullable();
                $t->string('vergi_no')->nullable();
                $t->string('mersis_no')->nullable();
                $t->string('telefon')->nullable();
                $t->string('eposta')->nullable();
                $t->string('website')->nullable();
                $t->string('kep_adresi')->nullable();
                $t->string('veri_sorumlusu')->nullable();
                $t->string('kvkk_irtibat')->nullable();
                $t->integer('iade_suresi')->nullable();
                $t->text('teslimat_bilgi')->nullable();
                $t->timestamp('guncelleme_tarihi')->nullable();
                $t->timestamps();
            });
        }
    }

    protected function yukle(): void
    {
        self::kur();
        $row = DB::table('isletme_yasal')->where('sube_id', $this->subeId)->first();
        $sube = DB::table('subeler')->where('id', $this->subeId)->first();

        $g = function ($col) use ($row) { return $row && isset($row->$col) ? trim((string) $row->$col) : ''; };

        $this->v = [
            'UNVAN'          => $g('unvan'),
            'MARKA'          => $g('marka_adi') ?: ($sube->ad ?? ''),
            'ADRES'          => $g('adres') ?: ($sube->adres ?? ''),
            'VD'             => $g('vergi_dairesi'),
            'VKN'            => $g('vergi_no'),
            'MERSIS'         => $g('mersis_no'),
            'TEL'            => $g('telefon'),
            'EPOSTA'         => $g('eposta'),
            'WEB'            => $g('website'),
            'KEP'            => $g('kep_adresi'),
            'VERI_SORUMLUSU' => $g('veri_sorumlusu') ?: $g('unvan'),
            'KVKK_IRTIBAT'   => $g('kvkk_irtibat') ?: $g('eposta'),
            'IADE_SURE'      => $g('iade_suresi') ?: '14',
            'TESLIMAT'       => $g('teslimat_bilgi') ?: 'masaya servis, paket servis ve gel-al',
        ];
        $this->v['TARIH'] = $row && $row->guncelleme_tarihi
            ? date('d.m.Y', strtotime($row->guncelleme_tarihi))
            : ($row && $row->updated_at ? date('d.m.Y', strtotime($row->updated_at)) : date('d.m.Y'));

        // Zorunlu ve boş olanları işaretle
        foreach (self::alanlar() as $k => $meta) {
            if (!($meta[1] ?? false)) continue;
            $map = ['unvan' => 'UNVAN', 'marka_adi' => 'MARKA', 'adres' => 'ADRES', 'vergi_dairesi' => 'VD', 'vergi_no' => 'VKN', 'telefon' => 'TEL', 'eposta' => 'EPOSTA'];
            $vk = $map[$k] ?? null;
            if ($vk && ($this->v[$vk] ?? '') === '') $this->eksik[] = $meta[0];
        }
    }

    /** İşletme verileri tam mı (tüm zorunlu alanlar dolu mu)? */
    public function tamMi(): bool { return count($this->eksik) === 0; }
    public function eksikler(): array { return $this->eksik; }

    /** Bir değeri getir; boşsa köşeli parantezli uyarı döndürür (sahibe görünür) */
    protected function d(string $key, string $ipucu = ''): string
    {
        $val = $this->v[$key] ?? '';
        if ($val !== '') return $val;
        return '[' . ($ipucu ?: $key) . ' giriniz]';
    }

    protected function uygula(string $sablon): string
    {
        $out = $sablon;
        foreach ($this->v as $k => $val) {
            $yer = $val !== '' ? $val : '[' . $k . ' — bilgi girilmedi]';
            $out = str_replace('{' . $k . '}', $yer, $out);
        }
        return trim($out);
    }

    /** Belgeyi üret: ['baslik'=>, 'metin'=>, 'guncelleme'=>, 'tam'=>bool] */
    public function belge(string $tip): array
    {
        $tipler = self::tipler();
        if (!isset($tipler[$tip])) $tip = 'gizlilik';
        $metod = 't_' . $tip;
        $metin = method_exists($this, $metod) ? $this->uygula($this->$metod()) : '';
        return [
            'baslik'     => $tipler[$tip],
            'metin'      => $metin,
            'guncelleme' => $this->v['TARIH'] ?? date('d.m.Y'),
            'tam'        => $this->tamMi(),
        ];
    }

    // ===================== ŞABLONLAR =====================

    protected function baslikSerit(): string
    {
        return "{MARKA}\nSon güncelleme: {TARIH}\n\n";
    }

    protected function t_gizlilik(): string
    {
        return $this->baslikSerit() .
"GİZLİLİK POLİTİKASI

{UNVAN} (\"İşletme\") olarak, {MARKA} mobil uygulaması ve dijital hizmetlerini kullanan müşterilerimizin gizliliğine önem veriyoruz. Bu politika, kişisel verilerinizin hangi amaçlarla işlendiğini açıklar.

1. TOPLANAN BİLGİLER
Uygulamayı kullanırken şu bilgileriniz toplanabilir: ad-soyad, telefon numarası, e-posta adresi, doğum tarihi, sipariş ve sadakat (puan/ödül) geçmişi, varsa teslimat adresi ve uygulama kullanım kayıtları.

2. KULLANIM AMAÇLARI
Bilgileriniz; siparişlerinizin alınması ve hazırlanması, sadakat puanı ve kampanyaların yürütülmesi, bildirim/iletişim gönderimi, hizmet kalitesinin iyileştirilmesi ve yasal yükümlülüklerin yerine getirilmesi amacıyla işlenir.

3. PAYLAŞIM
Kişisel verileriniz, yasal zorunluluklar ve hizmetin sağlanması için gerekli olan tedarikçiler (ör. ödeme, bildirim, SMS altyapısı) dışında üçüncü kişilerle paylaşılmaz, pazarlama amacıyla satılmaz.

4. SAKLAMA VE GÜVENLİK
Veriler, işleme amacının gerektirdiği süre ve ilgili mevzuattaki zamanaşımı süreleri boyunca saklanır; yetkisiz erişime karşı idari ve teknik tedbirlerle korunur.

5. HAKLARINIZ
6698 sayılı KVKK kapsamındaki haklarınızı kullanmak için {EPOSTA} adresine başvurabilirsiniz. Detaylar için KVKK Aydınlatma Metni'ni inceleyiniz.

6. İLETİŞİM
{UNVAN}
Adres: {ADRES}
Telefon: {TEL}
E-posta: {EPOSTA}";
    }

    protected function t_kullanim(): string
    {
        return $this->baslikSerit() .
"KULLANIM KOŞULLARI

Bu koşullar, {MARKA} uygulamasının kullanımına ilişkin kuralları belirler. Uygulamayı kullanarak aşağıdaki koşulları kabul etmiş sayılırsınız.

1. TARAFLAR
Hizmet sağlayıcı {UNVAN}, kullanıcı ise uygulamayı indiren/kullanan gerçek kişidir.

2. HİZMETİN KAPSAMI
Uygulama üzerinden menü görüntüleme, sipariş verme, sadakat puanı kazanma/kullanma, kampanyalardan yararlanma ve varsa dijital cüzdan işlemleri yapılabilir.

3. ÜYELİK VE HESAP
Kullanıcı, verdiği bilgilerin doğru ve güncel olmasından sorumludur. Hesap güvenliği (telefon doğrulama/oturum) kullanıcının sorumluluğundadır.

4. SADAKAT PUANI VE ÖDÜLLER
Kazanılan puan ve ödüller yalnızca {MARKA} bünyesinde geçerlidir, nakde çevrilemez ve devredilemez. İşletme, kampanya koşullarını önceden duyurarak değiştirme hakkını saklı tutar.

5. SORUMLULUK
İşletme, hizmeti kesintisiz sunmak için makul çabayı gösterir; mücbir sebep ve teknik arızalardan doğan kesintilerden sorumlu tutulamaz.

6. FİKRİ MÜLKİYET
Uygulamadaki marka, logo, görsel ve içerikler {UNVAN}'a aittir; izinsiz kullanılamaz.

7. YÜRÜRLÜK VE DEĞİŞİKLİK
İşletme bu koşulları güncelleyebilir; güncel sürüm uygulamada yayımlandığı tarihte yürürlüğe girer.

İletişim: {EPOSTA} — {TEL}";
    }

    protected function t_kvkk(): string
    {
        return $this->baslikSerit() .
"KİŞİSEL VERİLERİN KORUNMASI (KVKK) AYDINLATMA METNİ

6698 sayılı Kişisel Verilerin Korunması Kanunu (\"KVKK\") uyarınca, veri sorumlusu sıfatıyla {VERI_SORUMLUSU} tarafından hazırlanmıştır.

1. VERİ SORUMLUSU
{VERI_SORUMLUSU}
Adres: {ADRES}
Vergi Dairesi / No: {VD} / {VKN}
MERSİS: {MERSIS}
İrtibat: {KVKK_IRTIBAT}

2. İŞLENEN KİŞİSEL VERİLER
Kimlik (ad-soyad), iletişim (telefon, e-posta, adres), müşteri işlem (sipariş, puan, ödül) ve işlem güvenliği verileri.

3. İŞLEME AMAÇLARI
Mal/hizmet satış süreçlerinin yürütülmesi, müşteri ilişkileri ve sadakat yönetimi, iletişim faaliyetleri, finans/muhasebe işlemleri ve hukuki yükümlülüklerin yerine getirilmesi.

4. HUKUKİ SEBEP (KVKK m.5)
Bir sözleşmenin kurulması/ifası, hukuki yükümlülük, meşru menfaat ve gerekli hallerde açık rızanıza dayanılarak işlenir.

5. AKTARIM
Veriler; mevzuatın izin verdiği ölçüde yetkili kamu kurumlarına ve hizmetin ifası için çalışılan tedarikçilere (ödeme, bildirim, SMS) aktarılabilir.

6. TOPLAMA YÖNTEMİ
Veriler, uygulama ve dijital kanallar aracılığıyla elektronik ortamda toplanır.

7. İLGİLİ KİŞİNİN HAKLARI (KVKK m.11)
Verilerinizin işlenip işlenmediğini öğrenme, düzeltilmesini/silinmesini isteme, işlemeye itiraz etme ve zararın giderilmesini talep etme haklarına sahipsiniz. Başvurularınızı {KVKK_IRTIBAT} adresine iletebilirsiniz.";
    }

    protected function t_mesafeli(): string
    {
        return $this->baslikSerit() .
"MESAFELİ SATIŞ SÖZLEŞMESİ

Bu sözleşme, 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği uyarınca, aşağıdaki taraflar arasında kurulmuştur.

1. SATICI
Ünvan: {UNVAN}
Adres: {ADRES}
Telefon: {TEL}
E-posta: {EPOSTA}
Vergi Dairesi / No: {VD} / {VKN}

2. ALICI
Uygulama üzerinden sipariş veren ve bilgilerini beyan eden kullanıcı.

3. SÖZLEŞMENİN KONUSU
Alıcının, {MARKA} uygulaması üzerinden elektronik ortamda sipariş verdiği ürün/hizmetlerin satışı ve teslimi/sunumu.

4. ÜRÜN, BEDEL VE ÖDEME
Siparişe konu ürünlerin nitelikleri ve KDV dahil toplam bedeli, sipariş ekranında belirtilir. Ödeme, uygulamadaki yöntemlerle (nakit, kart veya dijital cüzdan) yapılır.

5. TESLİMAT / İFA
Hizmet; {TESLIMAT} şeklinde sunulur. Hazır yemek/içecek ve anlık tüketime yönelik ürünlerde ifa, sipariş onayı sonrası gerçekleştirilir.

6. CAYMA HAKKI
Mevzuat gereği; çabuk bozulabilen gıda ürünleri ile anında ifa edilen hizmetlerde cayma hakkı bulunmamaktadır. Cayma hakkının geçerli olduğu ürünlerde süre teslimden itibaren {IADE_SURE} gündür.

7. UYUŞMAZLIK
Uyuşmazlıklarda, Ticaret Bakanlığınca ilan edilen parasal sınırlar dâhilinde Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri yetkilidir.

Satıcı: {UNVAN} — {EPOSTA}";
    }

    protected function t_cerez(): string
    {
        return $this->baslikSerit() .
"ÇEREZ (COOKIE) POLİTİKASI

{MARKA} uygulaması ve dijital hizmetlerinde, deneyiminizi iyileştirmek için çerezler ve benzeri teknolojiler kullanılabilir.

1. ÇEREZ NEDİR?
Çerezler, uygulamayı/siteyi kullanırken cihazınıza kaydedilen küçük veri dosyalarıdır.

2. KULLANILAN ÇEREZ TÜRLERİ
- Zorunlu çerezler: Oturum ve temel işlevler için gereklidir.
- İşlevsel çerezler: Tercihlerinizi (dil, tema) hatırlar.
- Analitik çerezler: Hizmet kullanımını ölçerek iyileştirme sağlar.

3. YÖNETİM
Çerez tercihlerinizi cihaz/tarayıcı ayarlarınızdan yönetebilir veya silebilirsiniz. Zorunlu çerezlerin kapatılması bazı işlevleri etkileyebilir.

4. İLETİŞİM
Sorularınız için: {EPOSTA}
{UNVAN} — {ADRES}";
    }

    protected function t_iletisim(): string
    {
        return
"BİZE ULAŞIN

{MARKA}

Adres:
{ADRES}

Telefon: {TEL}
E-posta: {EPOSTA}
Web: {WEB}

Ünvan: {UNVAN}
Vergi Dairesi / No: {VD} / {VKN}

Görüş, öneri ve şikâyetlerinizde size en kısa sürede dönüş yapacağız.";
    }

    protected function t_sss(): string
    {
        return
"SIKÇA SORULAN SORULAR — {MARKA}

1. Puanları nasıl kazanırım?
Uygulama üzerinden verdiğiniz her siparişte sadakat puanı kazanırsınız. Puanlarınızı \"Puanlarım & Ödüller\" bölümünden görebilirsiniz.

2. Ödülleri nasıl kullanırım?
Yeterli puana ulaştığınızda ödülü seçip \"Kullan\" diyerek tek seferlik kod oluşturursunuz; kasada bu kodu gösterirsiniz.

3. Kampanya kodumu nerede görürüm?
Aldığınız kampanyalar \"Kuponlarım\" sekmesinde listelenir; koda dokunarak kopyalayabilirsiniz.

4. Siparişimi nasıl takip ederim?
\"Siparişlerim\" bölümünden güncel ve geçmiş siparişlerinizi görüntüleyebilirsiniz.

5. Bilgilerimi nasıl güncellerim?
Ayarlar > Hesap Ayarları bölümünden ad, e-posta, doğum tarihi gibi bilgilerinizi güncelleyebilirsiniz.

6. Hesabımı nasıl silerim?
Ayarlar > Hesap Ayarları > Hesabımı Sil adımlarını izleyebilirsiniz.

Başka sorularınız için: {EPOSTA} — {TEL}";
    }
}

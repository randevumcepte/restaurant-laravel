<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DEMO RESTORAN — zengin fake data.
 * Bos sistem anlasilmaz; bu seeder gercekci bir isletmeyi doldurur:
 * sube+personel, salon+masalar, birim/malzeme/recete, tedarikci+dalgali fiyatli
 * faturalar (yesil/sari/kirmizi), stok hareketleri, ~40 gunluk gecmis adisyon,
 * su an ACIK masalar, void/indirim/ikram loglari ve bir sayim.
 *
 * Idempotent: her calismada tablolari temizleyip yeniden doldurur.
 */
class DatabaseSeeder extends Seeder
{
    private int $subeId;
    private array $birim = [];      // kisaltma => id
    private array $personel = [];   // ['id'=>, 'ad'=>, 'rol'=>]
    private array $garsonlar = [];
    private array $masalar = [];    // id listesi
    private array $malzeme = [];    // ad => ['id','temel_birim','maliyet','kritik','alis_birim','alis_cevrim']
    private array $urunler = [];    // id => ['id','ad','fiyat','kategori']
    private array $modifierler = []; // id => ['ad','ek_fiyat']
    private array $musteriIds = [];
    private array $kuryeIds = [];

    public function run(): void
    {
        DB::disableQueryLog();
        $this->temizle();
        $this->subeVePersonel();
        $this->salon();
        $this->birimler();
        $this->malzemeler();
        $this->menu();
        $this->modifierGruplari();
        $this->receteler();
        $this->tedarikciVeFaturalar();
        $this->stokHareketleriBaslangic();
        $this->musteriler();
        $this->kuryeler();
        $this->gecmisAdisyonlar();
        $this->acikAdisyonlar();
        $this->paketSiparisler();
        $this->cagriLoglari();
        $this->entegrasyonlar();
        $this->sayim();

        $this->command->info('Demo restoran dolduruldu: '
            . DB::table('adisyonlar')->count() . ' adisyon, '
            . DB::table('urunler')->count() . ' urun, '
            . DB::table('malzemeler')->count() . ' malzeme.');
    }

    private function temizle(): void
    {
        $tablolar = [
            'entegrasyonlar',
            'cagri_loglari',
            'adisyon_masa_loglari', 'iptal_indirim_loglari', 'odemeler',
            'adisyon_kalem_secenekleri', 'adisyon_kalemleri', 'adisyonlar',
            'kuryeler', 'musteriler',
            'sayim_kalemleri', 'sayimlar', 'stok_hareketleri',
            'recete_kalemleri', 'receteler', 'urun_modifier_gruplari',
            'modifierlar', 'modifier_gruplari', 'urunler', 'menu_kategorileri',
            'alis_fatura_kalemleri', 'alis_faturalari', 'tedarikciler',
            'birim_cevrimleri', 'malzemeler', 'malzeme_kategorileri', 'birimler',
            'masalar', 'bolgeler', 'personeller', 'subeler',
        ];
        Schema::disableForeignKeyConstraints();
        foreach ($tablolar as $t) {
            if (Schema::hasTable($t)) DB::table($t)->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    private function subeVePersonel(): void
    {
        $now = now();
        $subeVeri = [
            'ad' => 'Lezzet Duragi', 'adres' => 'Bagdat Cad. No:120, Kadikoy/Istanbul',
            'telefon' => '0216 555 12 34', 'aktif' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ];
        if (Schema::hasColumn('subeler', 'webhook_token')) {
            $subeVeri['webhook_token'] = \Illuminate\Support\Str::random(32);
        }
        $this->subeId = DB::table('subeler')->insertGetId($subeVeri);

        $kadro = [
            ['Ahmet Yilmaz', 'sahip'], ['Elif Demir', 'mudur'],
            ['Mehmet Kaya', 'garson'], ['Zeynep Sahin', 'garson'],
            ['Can Ozturk', 'garson'], ['Deniz Arslan', 'garson'],
            ['Hasan Celik', 'mutfak'], ['Ayse Yildiz', 'mutfak'],
            ['Burak Aydin', 'kasa'],
        ];
        $pin = 1000;
        foreach ($kadro as [$ad, $rol]) {
            $id = DB::table('personeller')->insertGetId([
                'sube_id' => $this->subeId, 'ad' => $ad,
                'telefon' => '05' . random_int(300000000, 599999999),
                'rol' => $rol, 'pin' => (string) (++$pin), 'aktif' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->personel[] = ['id' => $id, 'ad' => $ad, 'rol' => $rol];
            if ($rol === 'garson') $this->garsonlar[] = $id;
        }
    }

    private function salon(): void
    {
        $now = now();
        $bolgeler = ['Ic Salon' => 16, 'Bahce' => 12, 'Teras' => 8];
        $sira = 0;
        foreach ($bolgeler as $ad => $masaSayisi) {
            $bolgeId = DB::table('bolgeler')->insertGetId([
                'sube_id' => $this->subeId, 'ad' => $ad, 'sira' => $sira++,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            for ($i = 1; $i <= $masaSayisi; $i++) {
                $this->masalar[] = DB::table('masalar')->insertGetId([
                    'sube_id' => $this->subeId, 'bolge_id' => $bolgeId,
                    'ad' => ($ad === 'Ic Salon' ? (string) $i : $ad . ' ' . $i),
                    'kapasite' => [2, 2, 4, 4, 4, 6][random_int(0, 5)],
                    'sekil' => random_int(0, 1) ? 'kare' : 'yuvarlak',
                    'x' => ($i % 6) * 120, 'y' => intdiv($i, 6) * 120,
                    'durum' => 'bos',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function birimler(): void
    {
        $now = now();
        $liste = [
            ['Gram', 'g', 'agirlik'], ['Kilogram', 'kg', 'agirlik'],
            ['Mililitre', 'ml', 'hacim'], ['Litre', 'lt', 'hacim'],
            ['Adet', 'ad', 'adet'], ['Koli', 'koli', 'adet'],
            ['Cuval', 'cuval', 'agirlik'], ['Teneke', 'tnk', 'hacim'],
            ['Demet', 'demet', 'adet'], ['Paket', 'pkt', 'adet'],
        ];
        foreach ($liste as [$ad, $k, $tip]) {
            $this->birim[$k] = DB::table('birimler')->insertGetId([
                'ad' => $ad, 'kisaltma' => $k, 'tip' => $tip,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function malzemeler(): void
    {
        $now = now();
        $katMap = [];
        foreach (['Et & Tavuk', 'Sebze & Meyve', 'Sut Urunleri', 'Bakliyat & Makarna', 'Icecek', 'Baharat & Sos', 'Ekmek & Un', 'Donuk'] as $k) {
            $katMap[$k] = DB::table('malzeme_kategorileri')->insertGetId([
                'ad' => $k, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // [ad, kategori, temel_birim, maliyet(temel birim basina TL), kritik_stok(temel), alis_birim, alis_cevrim]
        $liste = [
            ['Dana Kiyma', 'Et & Tavuk', 'g', 0.42, 3000, 'kg', 1000],
            ['Dana Antrikot', 'Et & Tavuk', 'g', 0.68, 2000, 'kg', 1000],
            ['Tavuk Gogus', 'Et & Tavuk', 'g', 0.18, 4000, 'kg', 1000],
            ['Kuzu Pirzola', 'Et & Tavuk', 'g', 0.75, 1500, 'kg', 1000],
            ['Sucuk', 'Et & Tavuk', 'g', 0.35, 1000, 'kg', 1000],
            ['Domates', 'Sebze & Meyve', 'g', 0.025, 5000, 'kg', 1000],
            ['Salatalik', 'Sebze & Meyve', 'g', 0.02, 3000, 'kg', 1000],
            ['Sogan', 'Sebze & Meyve', 'g', 0.018, 8000, 'cuval', 25000],
            ['Patates', 'Sebze & Meyve', 'g', 0.022, 10000, 'cuval', 25000],
            ['Marul', 'Sebze & Meyve', 'ad', 12.0, 20, 'ad', 1],
            ['Biber', 'Sebze & Meyve', 'g', 0.03, 2000, 'kg', 1000],
            ['Mantar', 'Sebze & Meyve', 'g', 0.06, 1000, 'kg', 1000],
            ['Maydanoz', 'Sebze & Meyve', 'demet', 8.0, 10, 'demet', 1],
            ['Beyaz Peynir', 'Sut Urunleri', 'g', 0.22, 2000, 'kg', 1000],
            ['Kasar Peyniri', 'Sut Urunleri', 'g', 0.32, 2500, 'kg', 1000],
            ['Mozzarella', 'Sut Urunleri', 'g', 0.38, 1500, 'kg', 1000],
            ['Tereyagi', 'Sut Urunleri', 'g', 0.28, 1000, 'kg', 1000],
            ['Yogurt', 'Sut Urunleri', 'g', 0.05, 3000, 'kg', 1000],
            ['Sut', 'Sut Urunleri', 'ml', 0.03, 5000, 'lt', 1000],
            ['Yumurta', 'Sut Urunleri', 'ad', 3.5, 60, 'koli', 30],
            ['Makarna', 'Bakliyat & Makarna', 'g', 0.04, 3000, 'pkt', 500],
            ['Pirinc', 'Bakliyat & Makarna', 'g', 0.05, 4000, 'cuval', 25000],
            ['Bulgur', 'Bakliyat & Makarna', 'g', 0.035, 3000, 'cuval', 25000],
            ['Mercimek', 'Bakliyat & Makarna', 'g', 0.045, 2000, 'cuval', 25000],
            ['Kola', 'Icecek', 'ad', 12.0, 48, 'koli', 24],
            ['Ayran', 'Icecek', 'ad', 6.0, 60, 'koli', 24],
            ['Su', 'Icecek', 'ad', 2.5, 120, 'koli', 12],
            ['Cay', 'Icecek', 'g', 0.4, 500, 'kg', 1000],
            ['Turk Kahvesi', 'Icecek', 'g', 1.2, 300, 'kg', 1000],
            ['Zeytinyagi', 'Baharat & Sos', 'ml', 0.18, 3000, 'tnk', 5000],
            ['Aycicek Yagi', 'Baharat & Sos', 'ml', 0.08, 5000, 'tnk', 5000],
            ['Tuz', 'Baharat & Sos', 'g', 0.01, 2000, 'kg', 1000],
            ['Karabiber', 'Baharat & Sos', 'g', 0.9, 200, 'kg', 1000],
            ['Domates Salcasi', 'Baharat & Sos', 'g', 0.06, 2000, 'kg', 1000],
            ['Un', 'Ekmek & Un', 'g', 0.02, 8000, 'cuval', 25000],
            ['Ekmek', 'Ekmek & Un', 'ad', 5.0, 40, 'ad', 1],
            ['Hamburger Ekmegi', 'Ekmek & Un', 'ad', 6.5, 50, 'pkt', 6],
            ['Patates (Donuk)', 'Donuk', 'g', 0.05, 5000, 'pkt', 2500],
            ['Dondurma', 'Donuk', 'g', 0.09, 2000, 'pkt', 1000],
        ];

        foreach ($liste as [$ad, $kat, $tb, $maliyet, $kritik, $alisBirim, $cevrim]) {
            $id = DB::table('malzemeler')->insertGetId([
                'kategori_id' => $katMap[$kat],
                'temel_birim_id' => $this->birim[$tb],
                'ad' => $ad, 'stok_takipli' => 1,
                'kritik_stok' => $kritik, 'guncel_maliyet' => $maliyet,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $this->malzeme[$ad] = [
                'id' => $id, 'temel_birim' => $tb, 'maliyet' => $maliyet, 'kritik' => $kritik,
                'alis_birim' => $alisBirim, 'alis_cevrim' => $cevrim,
            ];
            if ($alisBirim !== $tb) {
                DB::table('birim_cevrimleri')->insert([
                    'malzeme_id' => $id, 'birim_id' => $this->birim[$alisBirim],
                    'temel_birim_karsiligi' => $cevrim,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function menu(): void
    {
        $now = now();
        $menu = [
            'Baslangiclar' => [['Mercimek Corbasi', 65], ['Ezogelin Corbasi', 65], ['Sigara Boregi', 85], ['Humus', 95], ['Haydari', 75]],
            'Salatalar' => [['Coban Salata', 90], ['Sezar Salata', 145], ['Mevsim Salata', 85], ['Ton Balikli Salata', 165]],
            'Izgaralar' => [['Adana Kebap', 285], ['Urfa Kebap', 285], ['Kuzu Pirzola', 420], ['Tavuk Sis', 235], ['Karisik Izgara', 480], ['Kofte', 245]],
            'Ana Yemekler' => [['Izgara Kofte', 245], ['Tavuk Sote', 225], ['Et Sote', 320], ['Guvec', 295], ['Manti', 185]],
            'Burgerler' => [['Klasik Burger', 245], ['Cheeseburger', 275], ['Double Burger', 345], ['Tavuk Burger', 225]],
            'Pizzalar' => [['Margarita', 215], ['Sucuklu Pizza', 245], ['Karisik Pizza', 285], ['Vejetaryen Pizza', 235]],
            'Makarnalar' => [['Bolonez', 195], ['Alfredo', 205], ['Pesto', 195], ['Napoliten', 175]],
            'Tatlilar' => [['Sutlac', 95], ['Kunefe', 145], ['Baklava', 135], ['Dondurma', 75], ['Brownie', 115]],
            'Soguk Icecekler' => [['Kola', 45], ['Ayran', 30], ['Su', 15], ['Meyve Suyu', 55], ['Limonata', 60]],
            'Sicak Icecekler' => [['Cay', 20], ['Turk Kahvesi', 55], ['Filtre Kahve', 65], ['Latte', 75], ['Espresso', 55]],
        ];
        $sira = 0;
        foreach ($menu as $kat => $urunler) {
            $katId = DB::table('menu_kategorileri')->insertGetId([
                'sube_id' => $this->subeId, 'ad' => $kat, 'sira' => $sira++, 'aktif' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($urunler as [$ad, $fiyat]) {
                $id = DB::table('urunler')->insertGetId([
                    'sube_id' => $this->subeId, 'kategori_id' => $katId,
                    'ad' => $ad, 'aciklama' => null, 'fiyat' => $fiyat,
                    'stok_takipli' => 1, 'tukendi' => 0, 'aktif' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $this->urunler[$id] = ['id' => $id, 'ad' => $ad, 'fiyat' => $fiyat, 'kategori' => $kat];
            }
        }
        // Birkac urunu "tukendi" (86) yap
        foreach ((array) array_rand($this->urunler, 2) as $uid) {
            DB::table('urunler')->where('id', $uid)->update(['tukendi' => 1]);
        }
    }

    private function modifierGruplari(): void
    {
        $now = now();
        $gruplar = [
            ['Pisirme', 1, 1, [['Az pismis', 0], ['Orta', 0], ['Iyi pismis', 0]]],
            ['Ekstralar', 0, 5, [['Ekstra peynir', 25], ['Ekstra sos', 15], ['Bacon', 35], ['Cift kofte', 60]]],
            ['Icecek Boyu', 1, 1, [['Kucuk', 0], ['Orta', 10], ['Buyuk', 20]]],
        ];
        foreach ($gruplar as [$ad, $min, $max, $mods]) {
            $gid = DB::table('modifier_gruplari')->insertGetId([
                'ad' => $ad, 'min_secim' => $min, 'max_secim' => $max,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($mods as [$mad, $ek]) {
                $mid = DB::table('modifierlar')->insertGetId([
                    'grup_id' => $gid, 'ad' => $mad, 'ek_fiyat' => $ek,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $this->modifierler[$mid] = ['ad' => $mad, 'ek_fiyat' => $ek];
            }
            if (in_array($ad, ['Pisirme', 'Ekstralar'])) {
                foreach ($this->urunler as $uid => $u) {
                    if (in_array($u['kategori'], ['Burgerler', 'Izgaralar', 'Ana Yemekler'])) {
                        DB::table('urun_modifier_gruplari')->insert(['urun_id' => $uid, 'grup_id' => $gid]);
                    }
                }
            }
        }
    }

    private function receteler(): void
    {
        $now = now();
        // YARI MAMUL: Domates Sos (uretilir -> stoga girer -> pizza/makarnada kullanilir)
        $sosMalzemeId = DB::table('malzemeler')->insertGetId([
            'kategori_id' => DB::table('malzeme_kategorileri')->where('ad', 'Baharat & Sos')->value('id'),
            'temel_birim_id' => $this->birim['ml'], 'ad' => 'Domates Sos (Yari Mamul)',
            'stok_takipli' => 1, 'kritik_stok' => 2000, 'guncel_maliyet' => 0.04,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $sosReceteId = DB::table('receteler')->insertGetId([
            'ad' => 'Domates Sos', 'tip' => 'yari_mamul', 'urun_id' => null,
            'uretilen_malzeme_id' => $sosMalzemeId, 'verim_miktar' => 5000,
            'verim_birim_id' => $this->birim['ml'],
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('recete_kalemleri')->insert([
            ['recete_id' => $sosReceteId, 'malzeme_id' => $this->malzeme['Domates']['id'], 'alt_recete_id' => null, 'miktar' => 4000, 'birim_id' => $this->birim['g'], 'created_at' => $now, 'updated_at' => $now],
            ['recete_id' => $sosReceteId, 'malzeme_id' => $this->malzeme['Domates Salcasi']['id'], 'alt_recete_id' => null, 'miktar' => 500, 'birim_id' => $this->birim['g'], 'created_at' => $now, 'updated_at' => $now],
            ['recete_id' => $sosReceteId, 'malzeme_id' => $this->malzeme['Zeytinyagi']['id'], 'alt_recete_id' => null, 'miktar' => 300, 'birim_id' => $this->birim['ml'], 'created_at' => $now, 'updated_at' => $now],
            ['recete_id' => $sosReceteId, 'malzeme_id' => $this->malzeme['Sogan']['id'], 'alt_recete_id' => null, 'miktar' => 500, 'birim_id' => $this->birim['g'], 'created_at' => $now, 'updated_at' => $now],
        ]);

        $urunAdaId = [];
        foreach ($this->urunler as $uid => $u) $urunAdaId[$u['ad']] = $uid;

        $receteler = [
            'Klasik Burger' => [['Dana Kiyma', 150, 'g'], ['Hamburger Ekmegi', 1, 'ad'], ['Kasar Peyniri', 30, 'g'], ['Domates', 40, 'g'], ['Marul', 0.2, 'ad']],
            'Cheeseburger' => [['Dana Kiyma', 150, 'g'], ['Hamburger Ekmegi', 1, 'ad'], ['Kasar Peyniri', 50, 'g'], ['Domates', 40, 'g']],
            'Adana Kebap' => [['Dana Kiyma', 200, 'g'], ['Biber', 30, 'g'], ['Sogan', 40, 'g'], ['Ekmek', 1, 'ad']],
            'Margarita' => [['@Domates Sos', 120, 'ml'], ['Mozzarella', 120, 'g'], ['Un', 200, 'g']],
            'Sucuklu Pizza' => [['@Domates Sos', 120, 'ml'], ['Mozzarella', 120, 'g'], ['Sucuk', 60, 'g'], ['Un', 200, 'g']],
            'Bolonez' => [['Makarna', 120, 'g'], ['Dana Kiyma', 100, 'g'], ['@Domates Sos', 100, 'ml']],
            'Mercimek Corbasi' => [['Mercimek', 80, 'g'], ['Sogan', 20, 'g'], ['Tereyagi', 15, 'g']],
            'Tavuk Sis' => [['Tavuk Gogus', 220, 'g'], ['Biber', 30, 'g'], ['Ekmek', 1, 'ad']],
            'Coban Salata' => [['Domates', 100, 'g'], ['Salatalik', 80, 'g'], ['Sogan', 30, 'g'], ['Zeytinyagi', 20, 'ml']],
            'Cay' => [['Cay', 5, 'g']],
            'Turk Kahvesi' => [['Turk Kahvesi', 12, 'g']],
        ];
        foreach ($receteler as $urunAd => $kalemler) {
            if (!isset($urunAdaId[$urunAd])) continue;
            $rid = DB::table('receteler')->insertGetId([
                'ad' => $urunAd, 'tip' => 'urun', 'urun_id' => $urunAdaId[$urunAd],
                'uretilen_malzeme_id' => null, 'verim_miktar' => 1, 'verim_birim_id' => $this->birim['ad'],
                'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach ($kalemler as [$mad, $miktar, $b]) {
                $malzemeId = null; $altRecete = null;
                if (str_starts_with($mad, '@')) {
                    $altRecete = $sosReceteId;
                } else {
                    $malzemeId = $this->malzeme[$mad]['id'] ?? null;
                    if (!$malzemeId) continue;
                }
                DB::table('recete_kalemleri')->insert([
                    'recete_id' => $rid, 'malzeme_id' => $malzemeId, 'alt_recete_id' => $altRecete,
                    'miktar' => $miktar, 'birim_id' => $this->birim[$b],
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    private function tedarikciVeFaturalar(): void
    {
        $now = now();
        $tedarikciler = [];
        foreach (['Metro Toptan', 'Yerel Manav', 'Et Dunyasi', 'Sut & Kahvaltilik A.S.', 'Icecek Dagitim Ltd.'] as $t) {
            $tedarikciler[] = DB::table('tedarikciler')->insertGetId([
                'ad' => $t, 'telefon' => '0212 ' . random_int(2000000, 8999999),
                'aciklama' => null, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $sahip = $this->personel[0]['id'];
        $malzemeAdlari = array_keys($this->malzeme);

        for ($f = 0; $f < 14; $f++) {
            $tarih = now()->subDays(random_int(1, 60));
            $faturaId = DB::table('alis_faturalari')->insertGetId([
                'sube_id' => $this->subeId, 'tedarikci_id' => $tedarikciler[array_rand($tedarikciler)],
                'fatura_no' => 'F' . now()->year . '-' . str_pad((string) ($f + 1), 4, '0', STR_PAD_LEFT),
                'tarih' => $tarih->toDateString(), 'toplam' => 0,
                'durum' => 'onaylandi', 'giren_personel_id' => $sahip, 'onaylayan_personel_id' => $sahip,
                'created_at' => $tarih, 'updated_at' => $tarih,
            ]);
            $toplam = 0;
            $secilen = (array) array_rand(array_flip($malzemeAdlari), random_int(3, 6));
            foreach ($secilen as $mad) {
                $m = $this->malzeme[$mad];
                $cevrim = $m['alis_cevrim'];
                $temelFiyat = $m['maliyet'];
                $dalga = random_int(-5, 25) / 100;
                $yeniTemel = round($temelFiyat * (1 + $dalga), 4);
                $birimFiyat = round($yeniTemel * $cevrim, 4);
                $oncekiFiyat = round($temelFiyat * $cevrim, 4);
                $farkYuzde = $oncekiFiyat > 0 ? round(($birimFiyat - $oncekiFiyat) / $oncekiFiyat * 100, 2) : 0;
                $miktar = random_int(2, 20);
                $satir = round($birimFiyat * $miktar, 2);
                $farkTutar = round(($birimFiyat - $oncekiFiyat) * $miktar, 2);
                $uyari = $farkYuzde >= 15 ? 'kirmizi' : ($farkYuzde >= 5 ? 'sari' : 'yesil');
                DB::table('alis_fatura_kalemleri')->insert([
                    'fatura_id' => $faturaId, 'malzeme_id' => $m['id'], 'alis_birim_id' => $this->birim[$m['alis_birim']],
                    'miktar' => $miktar, 'birim_fiyat' => $birimFiyat, 'satir_toplam' => $satir,
                    'onceki_fiyat' => $oncekiFiyat, 'fiyat_farki_yuzde' => $farkYuzde, 'fiyat_farki_tutar' => $farkTutar,
                    'uyari' => $uyari, 'created_at' => $tarih, 'updated_at' => $tarih,
                ]);
                $toplam += $satir;
                DB::table('stok_hareketleri')->insert([
                    'sube_id' => $this->subeId, 'malzeme_id' => $m['id'], 'tip' => 'alis',
                    'miktar' => $miktar * $cevrim, 'birim_maliyet' => $yeniTemel,
                    'kaynak_tip' => 'fatura', 'kaynak_id' => $faturaId, 'aciklama' => 'Alis faturasi',
                    'personel_id' => $sahip, 'created_at' => $tarih,
                ]);
            }
            DB::table('alis_faturalari')->where('id', $faturaId)->update(['toplam' => $toplam]);
        }
    }

    private function stokHareketleriBaslangic(): void
    {
        $sahip = $this->personel[0]['id'];
        foreach ($this->malzeme as $ad => $m) {
            // Acilis stok: kritigin 3-8 kati
            $acilis = $m['kritik'] * random_int(3, 8);
            DB::table('stok_hareketleri')->insert([
                'sube_id' => $this->subeId, 'malzeme_id' => $m['id'], 'tip' => 'alis',
                'miktar' => $acilis, 'birim_maliyet' => $m['maliyet'],
                'kaynak_tip' => 'sayim', 'kaynak_id' => null, 'aciklama' => 'Acilis stok',
                'personel_id' => $sahip, 'created_at' => now()->subDays(60),
            ]);
            // Tuketim acilisin %40-95'i kadar -> stok DAIMA pozitif, bazilari kritik alti kalir
            $tuketim = round($acilis * random_int(40, 95) / 100, 2);
            DB::table('stok_hareketleri')->insert([
                'sube_id' => $this->subeId, 'malzeme_id' => $m['id'], 'tip' => 'tuketim',
                'miktar' => -1 * $tuketim, 'birim_maliyet' => $m['maliyet'],
                'kaynak_tip' => 'adisyon', 'kaynak_id' => null, 'aciklama' => 'Donemsel tuketim',
                'personel_id' => null, 'created_at' => now()->subDays(random_int(1, 30)),
            ]);
        }
    }

    private function gecmisAdisyonlar(): void
    {
        $urunIdler = array_keys($this->urunler);
        $modIdler = array_keys($this->modifierler);
        for ($d = 40; $d >= 0; $d--) {
            $adetGun = random_int(8, 16);
            for ($o = 0; $o < $adetGun; $o++) {
                $aksam = random_int(0, 1);
                $saat = $aksam ? random_int(19, 22) : random_int(12, 14);
                $acilis = now()->subDays($d)->setTime($saat, random_int(0, 59), 0);
                $kapanis = (clone $acilis)->addMinutes(random_int(35, 95));
                // Bugun icin gelecek zaman damgasi olusmasin
                if ($acilis->isFuture()) {
                    $acilis = now()->subMinutes(random_int(30, 240));
                    $kapanis = (clone $acilis)->addMinutes(random_int(30, 80));
                }
                if ($kapanis->isFuture()) $kapanis = now();
                $garson = $this->garsonlar[array_rand($this->garsonlar)];
                $masa = $this->masalar[array_rand($this->masalar)];

                $adisyonId = DB::table('adisyonlar')->insertGetId([
                    'sube_id' => $this->subeId, 'masa_id' => $masa, 'kanal' => 'salon',
                    'misafir_sayisi' => random_int(1, 6), 'durum' => 'odendi', 'acan_personel_id' => $garson,
                    'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
                    'acilis' => $acilis, 'kapanis' => $kapanis,
                    'created_at' => $acilis, 'updated_at' => $kapanis,
                ]);

                $araToplam = 0;
                for ($k = 0, $kn = random_int(2, 6); $k < $kn; $k++) {
                    $uid = $urunIdler[array_rand($urunIdler)];
                    $u = $this->urunler[$uid];
                    $adet = random_int(1, 3);
                    $ekFiyat = 0;
                    $kalemId = DB::table('adisyon_kalemleri')->insertGetId([
                        'adisyon_id' => $adisyonId, 'urun_id' => $uid, 'urun_adi' => $u['ad'],
                        'adet' => $adet, 'birim_fiyat' => $u['fiyat'], 'tutar' => 0,
                        'durum' => 'hazir', 'kur' => null, 'seat' => null, 'not' => null,
                        'personel_id' => $garson, 'gonderim_zamani' => $acilis,
                        'created_at' => $acilis, 'updated_at' => $acilis,
                    ]);
                    if ($modIdler && random_int(0, 3) === 0) {
                        $mid = $modIdler[array_rand($modIdler)];
                        $mod = $this->modifierler[$mid];
                        $ekFiyat += $mod['ek_fiyat'];
                        DB::table('adisyon_kalem_secenekleri')->insert([
                            'adisyon_kalem_id' => $kalemId, 'modifier_id' => $mid,
                            'modifier_adi' => $mod['ad'], 'ek_fiyat' => $mod['ek_fiyat'],
                        ]);
                    }
                    $tutar = ($u['fiyat'] + $ekFiyat) * $adet;
                    DB::table('adisyon_kalemleri')->where('id', $kalemId)->update(['tutar' => $tutar]);
                    $araToplam += $tutar;
                }

                $indirim = 0; $ikram = 0;
                $r = random_int(0, 11);
                if ($r === 0) {
                    $indirim = round($araToplam * 0.1, 2);
                    DB::table('iptal_indirim_loglari')->insert([
                        'sube_id' => $this->subeId, 'adisyon_id' => $adisyonId, 'adisyon_kalem_id' => null,
                        'tip' => 'indirim', 'tutar' => $indirim, 'sebep' => 'Musteri memnuniyeti',
                        'personel_id' => $garson, 'created_at' => $kapanis,
                    ]);
                } elseif ($r === 1) {
                    $ikram = min(75, $araToplam);
                    DB::table('iptal_indirim_loglari')->insert([
                        'sube_id' => $this->subeId, 'adisyon_id' => $adisyonId, 'adisyon_kalem_id' => null,
                        'tip' => 'ikram', 'tutar' => $ikram, 'sebep' => 'Cay ikrami',
                        'personel_id' => $garson, 'created_at' => $kapanis,
                    ]);
                }

                $toplam = max(0, $araToplam - $indirim - $ikram);
                DB::table('adisyonlar')->where('id', $adisyonId)->update([
                    'ara_toplam' => $araToplam, 'indirim' => $indirim, 'ikram' => $ikram, 'toplam' => $toplam,
                ]);

                $tip = ['nakit', 'kredi', 'kredi', 'yemek_karti'][random_int(0, 3)];
                DB::table('odemeler')->insert([
                    'adisyon_id' => $adisyonId, 'tip' => $tip, 'tutar' => $toplam,
                    'bahsis' => random_int(0, 4) === 0 ? round($toplam * 0.05, 2) : 0,
                    'personel_id' => $garson, 'created_at' => $kapanis,
                ]);
            }
        }
    }

    private function acikAdisyonlar(): void
    {
        $urunIdler = array_keys($this->urunler);
        foreach ((array) array_rand(array_flip($this->masalar), 7) as $masa) {
            $garson = $this->garsonlar[array_rand($this->garsonlar)];
            $acilis = now()->subMinutes(random_int(10, 80));
            $adisyonId = DB::table('adisyonlar')->insertGetId([
                'sube_id' => $this->subeId, 'masa_id' => $masa, 'kanal' => 'salon',
                'misafir_sayisi' => random_int(1, 5), 'durum' => 'acik', 'acan_personel_id' => $garson,
                'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
                'acilis' => $acilis, 'kapanis' => null,
                'created_at' => $acilis, 'updated_at' => $acilis,
            ]);
            $araToplam = 0;
            for ($k = 0, $kn = random_int(1, 4); $k < $kn; $k++) {
                $uid = $urunIdler[array_rand($urunIdler)];
                $u = $this->urunler[$uid];
                $adet = random_int(1, 2);
                $tutar = $u['fiyat'] * $adet;
                DB::table('adisyon_kalemleri')->insert([
                    'adisyon_id' => $adisyonId, 'urun_id' => $uid, 'urun_adi' => $u['ad'],
                    'adet' => $adet, 'birim_fiyat' => $u['fiyat'], 'tutar' => $tutar,
                    'durum' => random_int(0, 1) ? 'gonderildi' : 'hazir', 'kur' => null, 'seat' => null,
                    'not' => null, 'personel_id' => $garson, 'gonderim_zamani' => $acilis,
                    'created_at' => $acilis, 'updated_at' => $acilis,
                ]);
                $araToplam += $tutar;
            }
            DB::table('adisyonlar')->where('id', $adisyonId)->update(['ara_toplam' => $araToplam, 'toplam' => $araToplam]);
            DB::table('masalar')->where('id', $masa)->update(['durum' => 'dolu']);
        }
    }

    private function musteriler(): void
    {
        $now = now();
        $adlar = ['Ali Vural', 'Ayse Kaya', 'Mehmet Demir', 'Fatma Sahin', 'Mustafa Yildiz', 'Emine Celik',
            'Huseyin Aydin', 'Hatice Ozturk', 'Ibrahim Arslan', 'Zeynep Dogan', 'Osman Kilic', 'Elif Yilmaz',
            'Ahmet Koc', 'Meryem Aksoy', 'Yusuf Erdogan', 'Sultan Polat', 'Kadir Ozdemir', 'Havva Turan',
            'Ramazan Sen', 'Esra Bulut', 'Omer Kurt', 'Betul Simsek', 'Halil Aslan', 'Merve Cetin'];
        $sokaklar = ['Bagdat Cad.', 'Bahariye Cad.', 'Moda Cad.', 'Fenerbahce Cad.', 'Cadde-i Kebir', 'Kusdili Cad.'];
        foreach ($adlar as $ad) {
            $siparis = random_int(1, 40);
            $harcama = $siparis * random_int(150, 600);
            $this->musteriIds[] = DB::table('musteriler')->insertGetId([
                'sube_id' => $this->subeId, 'ad' => $ad,
                'telefon' => '0532' . random_int(1000000, 9999999),
                'adres' => $sokaklar[array_rand($sokaklar)] . ' No:' . random_int(1, 200) . ', Kadikoy',
                'puan' => (int) ($harcama / 100), 'siparis_sayisi' => $siparis, 'toplam_harcama' => $harcama,
                'notlar' => null, 'created_at' => now()->subDays(random_int(30, 400)), 'updated_at' => $now,
            ]);
        }
    }

    private function kuryeler(): void
    {
        $now = now();
        // Kadikoy civari koordinatlar
        $adlar = ['Serkan Yildirim', 'Baris Ozkan', 'Emre Sahin', 'Tolga Demir', 'Gokhan Aydin'];
        foreach ($adlar as $i => $ad) {
            $this->kuryeIds[] = DB::table('kuryeler')->insertGetId([
                'sube_id' => $this->subeId, 'ad' => $ad,
                'telefon' => '0505' . random_int(1000000, 9999999),
                'aktif' => 1, 'durum' => $i < 3 ? 'teslimatta' : 'musait',
                'son_lat' => 40.98 + random_int(-120, 120) / 10000,
                'son_lng' => 29.03 + random_int(-120, 120) / 10000,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function paketSiparisler(): void
    {
        if (!$this->musteriIds) return;
        $urunIdler = array_keys($this->urunler);
        $platformlar = ['getir', 'yemeksepeti', 'trendyol', 'migros', 'gofody', 'telefon', 'whatsapp'];

        // Gecmis teslim edilmis paket siparisler (son 40 gun)
        for ($i = 0; $i < 60; $i++) {
            $tarih = now()->subDays(random_int(1, 40))->setTime(random_int(11, 22), random_int(0, 59));
            $musteri = $this->musteriIds[array_rand($this->musteriIds)];
            $platform = $platformlar[array_rand($platformlar)];
            $adisyonId = DB::table('adisyonlar')->insertGetId([
                'sube_id' => $this->subeId, 'masa_id' => null, 'musteri_id' => $musteri, 'kurye_id' => $this->kuryeIds[array_rand($this->kuryeIds)],
                'kanal' => 'paket', 'platform' => $platform, 'platform_siparis_no' => strtoupper(substr($platform, 0, 3)) . random_int(100000, 999999),
                'teslimat_adres' => DB::table('musteriler')->where('id', $musteri)->value('adres'),
                'teslimat_durumu' => 'teslim', 'teslim_zamani' => (clone $tarih)->addMinutes(random_int(25, 55)),
                'misafir_sayisi' => 1, 'durum' => 'odendi', 'acan_personel_id' => $this->personel[8]['id'] ?? null,
                'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
                'acilis' => $tarih, 'kapanis' => (clone $tarih)->addMinutes(random_int(25, 55)),
                'created_at' => $tarih, 'updated_at' => $tarih,
            ]);
            $ara = 0;
            for ($k = 0, $kn = random_int(1, 4); $k < $kn; $k++) {
                $uid = $urunIdler[array_rand($urunIdler)];
                $u = $this->urunler[$uid];
                $adet = random_int(1, 2);
                $tutar = $u['fiyat'] * $adet;
                DB::table('adisyon_kalemleri')->insert([
                    'adisyon_id' => $adisyonId, 'urun_id' => $uid, 'urun_adi' => $u['ad'],
                    'adet' => $adet, 'birim_fiyat' => $u['fiyat'], 'tutar' => $tutar, 'durum' => 'hazir',
                    'kur' => null, 'seat' => null, 'not' => null, 'personel_id' => null, 'gonderim_zamani' => $tarih,
                    'created_at' => $tarih, 'updated_at' => $tarih,
                ]);
                $ara += $tutar;
            }
            DB::table('adisyonlar')->where('id', $adisyonId)->update(['ara_toplam' => $ara, 'toplam' => $ara]);
            DB::table('odemeler')->insert([
                'adisyon_id' => $adisyonId, 'tip' => in_array($platform, ['telefon', 'whatsapp']) ? 'nakit' : 'kredi',
                'tutar' => $ara, 'bahsis' => 0, 'personel_id' => null, 'created_at' => (clone $tarih)->addMinutes(30),
            ]);
        }

        // AKTIF paket siparisler (su an hazirlaniyor/yolda) -> sipariş merkezi + kurye harita canli
        $durumlar = ['hazirlaniyor', 'hazirlaniyor', 'yolda', 'yolda', 'hazir'];
        for ($i = 0; $i < 9; $i++) {
            $acilis = now()->subMinutes(random_int(3, 40));
            $musteri = $this->musteriIds[array_rand($this->musteriIds)];
            $platform = $platformlar[array_rand($platformlar)];
            $durum = $durumlar[array_rand($durumlar)];
            $kurye = $durum === 'yolda' ? $this->kuryeIds[array_rand($this->kuryeIds)] : null;
            $adisyonId = DB::table('adisyonlar')->insertGetId([
                'sube_id' => $this->subeId, 'masa_id' => null, 'musteri_id' => $musteri, 'kurye_id' => $kurye,
                'kanal' => 'paket', 'platform' => $platform, 'platform_siparis_no' => strtoupper(substr($platform, 0, 3)) . random_int(100000, 999999),
                'teslimat_adres' => DB::table('musteriler')->where('id', $musteri)->value('adres'),
                'teslimat_durumu' => $durum, 'teslim_zamani' => null,
                'misafir_sayisi' => 1, 'durum' => 'acik', 'acan_personel_id' => $this->personel[8]['id'] ?? null,
                'ara_toplam' => 0, 'indirim' => 0, 'ikram' => 0, 'toplam' => 0,
                'acilis' => $acilis, 'kapanis' => null, 'created_at' => $acilis, 'updated_at' => $acilis,
            ]);
            $ara = 0;
            for ($k = 0, $kn = random_int(1, 4); $k < $kn; $k++) {
                $uid = $urunIdler[array_rand($urunIdler)];
                $u = $this->urunler[$uid];
                $adet = random_int(1, 3);
                $tutar = $u['fiyat'] * $adet;
                DB::table('adisyon_kalemleri')->insert([
                    'adisyon_id' => $adisyonId, 'urun_id' => $uid, 'urun_adi' => $u['ad'],
                    'adet' => $adet, 'birim_fiyat' => $u['fiyat'], 'tutar' => $tutar,
                    'durum' => $durum === 'hazirlaniyor' ? 'gonderildi' : 'hazir',
                    'kur' => null, 'seat' => null, 'not' => null, 'personel_id' => null, 'gonderim_zamani' => $acilis,
                    'created_at' => $acilis, 'updated_at' => $acilis,
                ]);
                $ara += $tutar;
            }
            DB::table('adisyonlar')->where('id', $adisyonId)->update(['ara_toplam' => $ara, 'toplam' => $ara]);
        }
    }

    private function cagriLoglari(): void
    {
        if (!$this->musteriIds) return;
        for ($i = 0; $i < 40; $i++) {
            $musteri = random_int(0, 3) === 0 ? null : $this->musteriIds[array_rand($this->musteriIds)];
            $tel = $musteri ? DB::table('musteriler')->where('id', $musteri)->value('telefon') : '0532' . random_int(1000000, 9999999);
            $sonuc = ['cevaplandi', 'siparis', 'siparis', 'kacan'][random_int(0, 3)];
            DB::table('cagri_loglari')->insert([
                'sube_id' => $this->subeId, 'telefon' => $tel, 'musteri_id' => $musteri,
                'yon' => 'gelen', 'sonuc' => $sonuc, 'adisyon_id' => null,
                'created_at' => now()->subMinutes(random_int(5, 60 * 24 * 20)),
            ]);
        }
    }

    private function entegrasyonlar(): void
    {
        if (!Schema::hasTable('entegrasyonlar')) return;
        $now = now();
        // getir/ubereats birlesti; demo: yemeksepeti+trendyol aktif, digerleri pasif
        $liste = [
            ['yemeksepeti', true], ['trendyol', true], ['getir', false], ['migros', false], ['ubereats', false],
        ];
        foreach ($liste as [$p, $aktif]) {
            DB::table('entegrasyonlar')->insert([
                'sube_id' => $this->subeId, 'platform' => $p, 'aktif' => $aktif ? 1 : 0,
                'magaza_id' => $aktif ? strtoupper(substr($p, 0, 3)) . random_int(10000, 99999) : null,
                'api_key' => null, 'otomatik_onay' => 1,
                'son_siparis_at' => $aktif ? now()->subMinutes(random_int(5, 120)) : null,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    private function sayim(): void
    {
        $now = now();
        $sayimId = DB::table('sayimlar')->insertGetId([
            'sube_id' => $this->subeId, 'tarih' => now()->subDays(2)->toDateString(),
            'durum' => 'kapandi', 'personel_id' => $this->personel[1]['id'],
            'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
        ]);
        foreach ($this->malzeme as $ad => $m) {
            $teorik = (float) DB::table('stok_hareketleri')->where('malzeme_id', $m['id'])->sum('miktar');
            $sayilan = round($teorik * (1 + random_int(-8, 2) / 100), 3);
            $fark = round($sayilan - $teorik, 3);
            DB::table('sayim_kalemleri')->insert([
                'sayim_id' => $sayimId, 'malzeme_id' => $m['id'],
                'sayilan' => $sayilan, 'teorik' => $teorik, 'fark' => $fark,
                'fark_maliyet' => round($fark * $m['maliyet'], 2),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}

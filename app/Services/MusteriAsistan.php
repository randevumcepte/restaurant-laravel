<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MUSTERI (QR) ASISTANI — masadaki QR'dan acilir, musteriyle konusur:
 * menu/gunun yemegi/tatli/icecek tanitimi, oneri, WiFi/saat/adres (kalip), garson cagirma.
 * Faz 1: tanitim + sohbet (siparis "garsonu cagir" ile). Rakam/menu GERCEK veriden.
 */
class MusteriAsistan
{
    protected $subeId;

    public function __construct($subeId)
    {
        $this->subeId = (int) $subeId;
    }

    public function cevapla($soru)
    {
        $c = $this->norm($soru);
        if ($c === '') return $this->cvp('Sizi dinliyorum. Menümüzü sorabilir, öneri isteyebilir ya da garson çağırabilirsiniz.');

        // 1) Kimlik / selam / tesekkur (musteri dostu)
        if ($this->has($c, ['sen kimsin', 'kimsin', 'adin ne', 'nesin', 'ne yapabilir', 'neler yapabilir', 'ne ise yara', 'gorevin ne'])) {
            return $this->cvp('Ben masanızın dijital asistanıyım. Menüyü tanıtabilir, öneride bulunabilir, günün yemeğini söyleyebilir ya da garson çağırabilirim. Ne yapmak istersiniz?');
        }
        if ($this->has($c, ['merhaba', 'selam', 'gunaydin', 'iyi gunler', 'iyi aksamlar', 'alo', 'hey'])) {
            return $this->cvp('Hoş geldiniz! 😊 Menümüzü mü tanıtayım yoksa bir önerim mi olsun?');
        }
        if ($this->has($c, ['tesekkur', 'sagol', 'sag ol', 'eyvallah', 'minnettar'])) {
            return $this->cvp('Rica ederim, afiyet olsun! 😊');
        }

        // 2) Garson cagir / hesap
        if ($this->has($c, ['garson', 'biri gelsin', 'cagir', 'yardim istiyorum', 'hesap', 'odeme', 'odeyecegim', 'hesabi getir', 'adisyon'])) {
            $hesap = $this->has($c, ['hesap', 'odeme', 'odeyecegim', 'hesabi getir']);
            return $this->cvp($hesap ? 'Garsonumuza hesabınızı iletmesini söyledim, birazdan yanınızda olacak. 🙋'
                : 'Garsonumuzu masanıza çağırdım, birazdan geliyor. 🙋', ['aksiyon' => 'garson_cagir']);
        }

        // 3) Gunun yemegi / sef onerisi
        if ($this->has($c, ['gunun yemegi', 'gunun spesiyali', 'sef onerisi', 'spesiyal', 'bugun ne var', 'ne onerirsin', 'ne tavsiye', 'oneri', 'populer', 'en cok', 'en sevilen', 'favori', 'ne yesem', 'ne yiyeyim'])) {
            return $this->oneri();
        }

        // 4) Kategori tanitim
        if ($this->has($c, ['tatli', 'tatlilar', 'ne tatli'])) return $this->kategori(['tatlilar'], 'Tatlılarımız', '🍰');
        if ($this->has($c, ['icecek', 'icecekler', 'ne icilir', 'soguk icecek', 'sicak icecek'])) return $this->kategori(['soguk icecekler', 'sicak icecekler'], 'İçeceklerimiz', '🥤');
        if ($this->has($c, ['salata', 'salatalar'])) return $this->kategori(['salatalar'], 'Salatalarımız', '🥗');
        if ($this->has($c, ['corba', 'baslangic', 'baslangiclar'])) return $this->kategori(['baslangiclar'], 'Başlangıçlarımız', '🍲');
        if ($this->has($c, ['pizza', 'pizzalar'])) return $this->kategori(['pizzalar'], 'Pizzalarımız', '🍕');
        if ($this->has($c, ['burger', 'burgerler', 'hamburger'])) return $this->kategori(['burgerler'], 'Burgerlerimiz', '🍔');
        if ($this->has($c, ['makarna', 'makarnalar', 'pasta'])) return $this->kategori(['makarnalar'], 'Makarnalarımız', '🍝');
        if ($this->has($c, ['izgara', 'kebap', 'et', 'et yemek'])) return $this->kategori(['izgaralar', 'ana yemekler'], 'Izgara ve Ana Yemekler', '🍖');
        if ($this->has($c, ['vejetaryen', 'etsiz', 'vegan'])) return $this->vejetaryen();
        if ($this->has($c, ['menu', 'ne var', 'neler var', 'yemek listesi', 'kategoriler', 'neler yapiyorsunuz'])) return $this->menu();

        // 5) Belirli urun (fiyat / nasil / icinde ne)
        $urun = $this->urunBul($c);
        if ($urun) return $this->urunTanit($urun, $c);

        // 6) Kalip kutuphanesi (wifi/saat/adres... — kimlik/sohbet disi)
        $kalip = $this->kalip($soru);
        if ($kalip) return $kalip;

        // 7) Fallback
        return $this->cvp('Bunu tam anlayamadım 🙂 Menümüzü sorabilir, "günün yemeği ne" diyebilir, öneri isteyebilir ya da garson çağırabilirsiniz.');
    }

    // -------- MENU HANDLERS --------
    protected function menu()
    {
        $kats = DB::table('menu_kategorileri')->where('sube_id', $this->subeId)->where('aktif', 1)->orderBy('sira')->pluck('ad')->all();
        if (empty($kats)) return $this->cvp('Menü şu an hazırlanıyor, birazdan hazır olacak.');
        return $this->cvp('Menümüzde şu kategoriler var: ' . implode(', ', $kats) . '. Hangisini tanıtayım? Örneğin "tatlılar" ya da "günün yemeği" diyebilirsiniz.',
            ['tip' => 'kategoriler', 'liste' => $kats]);
    }

    protected function kategori(array $normAdlar, $baslik, $emoji = '')
    {
        $urunler = DB::table('urunler')->join('menu_kategorileri', 'urunler.kategori_id', '=', 'menu_kategorileri.id')
            ->where('urunler.sube_id', $this->subeId)->where('urunler.aktif', 1)->where('urunler.tukendi', 0)
            ->select('urunler.ad', 'urunler.fiyat', 'urunler.aciklama', 'menu_kategorileri.ad as kat')
            ->get()->filter(fn ($u) => in_array($this->norm($u->kat), $normAdlar))->values();
        if ($urunler->isEmpty()) return $this->cvp($baslik . ' şu an listede görünmüyor.');
        $isimler = $urunler->take(6)->map(fn ($u) => $u->ad . ' (' . $this->tl($u->fiyat) . ')')->implode(', ');
        return $this->cvp("$emoji $baslik: $isimler. Detay ya da öneri isterseniz sorabilirsiniz.",
            ['tip' => 'urunler', 'baslik' => $baslik, 'liste' => $urunler->map(fn ($u) => ['ad' => $u->ad, 'fiyat' => (float) $u->fiyat, 'aciklama' => $u->aciklama])->all()]);
    }

    protected function vejetaryen()
    {
        // Et/tavuk/kofte/sucuk/kebap gecmeyen urunler
        $etli = ['et', 'tavuk', 'kofte', 'sucuk', 'kebap', 'pirzola', 'burger', 'sote', 'antrikot', 'bolonez', 'ton'];
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->where('tukendi', 0)
            ->select('ad', 'fiyat')->get()->filter(function ($u) use ($etli) {
                $n = $this->norm($u->ad);
                foreach ($etli as $e) { if (strpos($n, $e) !== false) return false; }
                return true;
            })->values();
        if ($urunler->isEmpty()) return $this->cvp('Vejetaryen seçeneklerimiz için garsonumuza sorabilirim, çağırayım mı?');
        $isim = $urunler->take(6)->map(fn ($u) => $u->ad)->implode(', ');
        return $this->cvp("🥗 Etsiz seçeneklerimizden bazıları: $isim. Detay ister misiniz?",
            ['tip' => 'urunler', 'baslik' => 'Vejetaryen', 'liste' => $urunler->map(fn ($u) => ['ad' => $u->ad, 'fiyat' => (float) $u->fiyat])->all()]);
    }

    protected function oneri()
    {
        // En cok satan urunler (son 30 gun)
        $top = DB::table('adisyon_kalemleri')->join('adisyonlar', 'adisyon_kalemleri.adisyon_id', '=', 'adisyonlar.id')
            ->where('adisyonlar.durum', 'odendi')->where('adisyonlar.kapanis', '>=', now()->subDays(30))->where('adisyon_kalemleri.durum', '!=', 'iptal')
            ->select('urun_adi', DB::raw('SUM(adet) as adet'))->groupBy('urun_adi')->orderByDesc('adet')->limit(4)->pluck('urun_adi')->all();
        if (empty($top)) {
            $top = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->inRandomOrder()->limit(3)->pluck('ad')->all();
        }
        $bas = $top[0] ?? 'Köfte';
        return $this->cvp("Misafirlerimizin en çok tercih ettikleri: " . implode(', ', $top) . ". Özellikle $bas çok seviliyor, gönül rahatlığıyla önerebilirim. 😊",
            ['tip' => 'oneri', 'liste' => $top]);
    }

    protected function urunBul($c)
    {
        $urunler = DB::table('urunler')->where('sube_id', $this->subeId)->where('aktif', 1)->select('id', 'ad', 'fiyat', 'aciklama', 'tukendi')->get();
        $enIyi = null;
        $enSkor = 0;
        foreach ($urunler as $u) {
            $n = $this->norm($u->ad);
            if (mb_strlen($n) < 3) continue;
            if (strpos(' ' . $c . ' ', ' ' . $n . ' ') !== false || strpos($c, $n) !== false) {
                if (mb_strlen($n) > $enSkor) { $enSkor = mb_strlen($n); $enIyi = $u; }
            }
        }
        return $enIyi;
    }

    protected function urunTanit($u, $c)
    {
        if ($u->tukendi) return $this->cvp($u->ad . ' bugün maalesef tükendi. Dilerseniz benzeri için öneride bulunabilirim.');
        // Recete malzemelerinden kisa "icinde ne var"
        $malz = DB::table('receteler')->where('urun_id', $u->id)->where('tip', 'urun')->value('id');
        $icindekiler = [];
        if ($malz) {
            $icindekiler = DB::table('recete_kalemleri')->join('malzemeler', 'recete_kalemleri.malzeme_id', '=', 'malzemeler.id')
                ->where('recete_kalemleri.recete_id', $malz)->orderByDesc('recete_kalemleri.miktar')->limit(4)->pluck('malzemeler.ad')->all();
        }
        $cevap = $u->ad . ', ' . $this->tl($u->fiyat) . '.';
        if (!empty($u->aciklama)) $cevap .= ' ' . $u->aciklama;
        if (!empty($icindekiler)) $cevap .= ' İçinde ' . implode(', ', $icindekiler) . ' var.';
        $cevap .= ' Sipariş vermek isterseniz garsonu çağırabilirim.';
        return $this->cvp($cevap, ['tip' => 'urun', 'ad' => $u->ad, 'fiyat' => (float) $u->fiyat, 'icindekiler' => $icindekiler]);
    }

    // -------- KALIP (kimlik/sohbet disi; wifi/saat/adres...) --------
    protected function kalip($soru)
    {
        try {
            if (!Schema::hasTable('asistan_kalip')) return null;
            $liste = DB::table('asistan_kalip')->where('aktif', 1)
                ->whereNotIn('kategori', ['kimlik', 'sohbet'])->select('id', 'tetikleyiciler', 'cevap')->get();
            if ($liste->isEmpty()) return null;
            $n = ' ' . $this->norm($soru) . ' ';
            $enIyi = null;
            $enSkor = 0;
            foreach ($liste as $k) {
                foreach (preg_split('/[\r\n,;]+/', (string) $k->tetikleyiciler) as $t) {
                    $t = trim($this->norm($t));
                    if (mb_strlen($t) < 2) continue;
                    if (preg_match('/(?:^| )' . preg_quote($t, '/') . '[a-z]*(?= |$)/u', $n)) {
                        if (mb_strlen($t) > $enSkor) { $enSkor = mb_strlen($t); $enIyi = $k; }
                    }
                }
            }
            if (!$enIyi) return null;
            try { DB::table('asistan_kalip')->where('id', $enIyi->id)->increment('kullanim_sayisi'); } catch (\Throwable $e) {}
            $cev = (string) $enIyi->cevap;
            if (strpos($cev, '---') !== false) {
                $p = array_values(array_filter(array_map('trim', preg_split('/^\s*-{3,}\s*$/m', $cev)), fn ($x) => $x !== ''));
                $cev = empty($p) ? trim($cev) : $p[array_rand($p)];
            }
            return $this->cvp(trim($cev), ['kaynak' => 'kalip']);
        } catch (\Throwable $e) {
            return null;
        }
    }

    // -------- YARDIMCILAR --------
    protected function cvp($metin, $ek = [])
    {
        return array_merge(['ok' => 1, 'cevap' => $metin, 'seslendir' => true], $ek);
    }

    protected function tl($v)
    {
        return '₺' . number_format((float) $v, 0, ',', '.');
    }

    protected function has($c, array $ks)
    {
        foreach ($ks as $k) {
            if (strpos(' ' . $c . ' ', ' ' . $this->norm($k)) !== false || strpos($c, $this->norm($k)) !== false) return true;
        }
        return false;
    }

    protected function norm($s)
    {
        $s = mb_strtolower(trim((string) $s), 'UTF-8');
        $tr = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'İ' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u'];
        $s = strtr($s, $tr);
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        return preg_replace('/\s+/', ' ', trim($s));
    }
}

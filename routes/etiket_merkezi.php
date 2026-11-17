<?php
// ============================================================================
// BARKOD & ETİKET MERKEZİ: market gibi satılan ürünler (kasap, şarküteri, kendi üretimi şişe/kavanoz)
// - Etiketli Ürünler: menü ürününe bağlı ya da bağımsız; resmi 869 barkod ya da mağaza içi barkod
// - Ürün etiketi (paketli): üretim/SKT/parti; tam etiket, küçük tarih etiketi (matbaa etiketinin üstüne) ya da yüklenen tasarım + alanlar
// - Tartılı ürün: ayarlanabilir terazi barkodu (önek + PLU + ağırlık/fiyat), PLU dosyası, kasada barkod çözme, terazisiz tartı etiketi
// - Raf kartı: rulo (80×38 / 100×38) ya da A4 yaprak; indirimde eski (son 30 gün en düşük) + yeni fiyat; fiyat değişince yeniden basılacaklar
// - Serbest barkod / QR üretimi. Demirbaş ayrı dosyada (routes/demirbas.php) ve bu sayfanın bir sekmesi.
// Yetki: demirbaş kutucukları (gor_demirbas / gor_demirbas_duzenle) — _dmYetki. Yazıcı ayarı etiket_ayarlari tablosu (ortak).
// ============================================================================

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

if (!function_exists('_emEnsure')) {
    function _emEnsure()
    {
        if (function_exists('_demirbasEnsure')) _demirbasEnsure();
        if (function_exists('_barkodKur')) _barkodKur();
        if (cache()->get('em_ensure_ok1')) return;
        try {
            if (!Schema::hasTable('etiket_urunler')) {
                Schema::create('etiket_urunler', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('urun_id')->nullable()->index();
                    $t->string('ad', 150); $t->string('kategori', 60)->nullable();
                    $t->string('birim', 6)->default('adet');              // adet | kg | lt — fiyat neye göre
                    $t->decimal('fiyat', 12, 2)->nullable();               // boş + menüye bağlı → menü fiyatı
                    $t->decimal('net_miktar', 10, 3)->nullable(); $t->string('net_birim', 6)->default('g'); // paket içeriği: 500 g, 1 lt…
                    $t->decimal('dara_g', 8, 1)->default(0);
                    $t->string('barkod', 32)->nullable()->index(); $t->string('barkod_tip', 6)->default('yok'); // resmi | ic | yok
                    $t->unsignedInteger('plu')->nullable();
                    $t->unsignedSmallInteger('raf_omru_gun')->nullable();
                    $t->text('icindekiler')->nullable(); $t->string('alerjen', 255)->nullable(); $t->string('saklama', 150)->nullable();
                    $t->string('uretici', 255)->nullable(); $t->string('uretim_yeri', 100)->nullable(); $t->boolean('yerli')->default(false);
                    $t->string('turler', 30)->default('paket,raf');       // paket, tarti, raf
                    $t->unsignedBigInteger('sablon_id')->nullable();
                    $t->decimal('indirimli_fiyat', 12, 2)->nullable(); $t->date('indirim_bitis')->nullable();
                    $t->decimal('raf_fiyat', 12, 2)->nullable(); $t->dateTime('raf_basim')->nullable(); // son basılan raf kartındaki fiyat
                    $t->boolean('aktif')->default(true);
                    $t->timestamps();
                });
            }
            if (!Schema::hasTable('etiket_fiyat_gecmis')) {
                Schema::create('etiket_fiyat_gecmis', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('eu_id')->index();
                    $t->decimal('fiyat', 12, 2); $t->timestamp('created_at')->nullable();
                });
            }
            if (!Schema::hasTable('etiket_basimlar')) {
                Schema::create('etiket_basimlar', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->unsignedBigInteger('eu_id')->nullable()->index();
                    $t->string('tur', 10); $t->string('mod', 10)->nullable(); $t->string('boyut', 12)->nullable();
                    $t->string('parti_no', 30)->nullable(); $t->date('uretim_tarih')->nullable(); $t->date('skt')->nullable();
                    $t->unsignedInteger('adet')->default(1); $t->decimal('miktar', 10, 3)->nullable(); $t->decimal('tutar', 12, 2)->nullable();
                    $t->string('barkod', 32)->nullable(); $t->unsignedBigInteger('personel_id')->nullable(); $t->timestamp('created_at')->nullable();
                });
            }
            if (!Schema::hasTable('etiket_sablonlar')) {
                Schema::create('etiket_sablonlar', function ($t) {
                    $t->id(); $t->unsignedBigInteger('sube_id')->index(); $t->string('ad', 100);
                    $t->decimal('gen_mm', 6, 1)->default(58); $t->decimal('yuk_mm', 6, 1)->default(40);
                    $t->string('arka', 255)->nullable(); $t->text('alanlar')->nullable(); $t->timestamps();
                });
            }
            $ek = ['terazi_onek' => fn ($t) => $t->string('terazi_onek', 4)->default('27'), 'terazi_plu_hane' => fn ($t) => $t->unsignedTinyInteger('terazi_plu_hane')->default(5),
                'terazi_deger' => fn ($t) => $t->string('terazi_deger', 8)->default('agirlik'), 'terazi_ondalik' => fn ($t) => $t->unsignedTinyInteger('terazi_ondalik')->default(3),
                'raf_bicim' => fn ($t) => $t->string('raf_bicim', 12)->default('rulo_80x38'), 'urun_boyut' => fn ($t) => $t->string('urun_boyut', 12)->default('58x40'),
                'uretici_varsayilan' => fn ($t) => $t->string('uretici_varsayilan', 255)->nullable()];
            foreach ($ek as $k => $fn) if (!Schema::hasColumn('etiket_ayarlari', $k)) Schema::table('etiket_ayarlari', $fn);
            cache()->put('em_ensure_ok1', 1, now()->addHours(12));
        } catch (\Throwable $e) { report($e); }
    }

    function _emTurler() { return ['paket' => 'Paketli ürün etiketi', 'tarti' => 'Tartılı ürün (terazi)', 'raf' => 'Raf kartı']; }
    function _emBirimler() { return ['adet' => 'Adet / paket', 'kg' => 'Kilogram', 'lt' => 'Litre']; }
    function _emNetBirimler() { return ['g' => 'g', 'kg' => 'kg', 'ml' => 'ml', 'lt' => 'lt', 'adet' => 'adet']; }
    function _emUrunBoyutlar() { return ['40x30' => ['40 × 30 mm', 40, 30], '50x30' => ['50 × 30 mm', 50, 30], '58x40' => ['58 × 40 mm', 58, 40], '60x40' => ['60 × 40 mm', 60, 40], '70x50' => ['70 × 50 mm', 70, 50], '100x50' => ['100 × 50 mm', 100, 50], '100x70' => ['100 × 70 mm', 100, 70]]; }
    // Raf kartı biçimleri: [ad, en mm, boy mm, A4 sütun, A4 satır] (sütun 0 = rulo)
    function _emRafBicimler() { return ['rulo_80x38' => ['Rulo 80 × 38 mm', 80, 38, 0, 0], 'rulo_100x38' => ['Rulo 100 × 38 mm', 100, 38, 0, 0],
        'a4_8' => ['A4 yaprak · 8 kart (105 × 74 mm)', 105, 74.2, 2, 4], 'a4_12' => ['A4 yaprak · 12 kart (70 × 74 mm)', 70, 74.2, 3, 4], 'a4_21' => ['A4 yaprak · 21 kart (70 × 42 mm)', 70, 42.4, 3, 7]]; }
    function _emSablonAlanlar() { return ['ad' => 'Ürün adı', 'fiyat' => 'Fiyat', 'birim_fiyat' => 'Birim fiyat', 'net' => 'Net miktar', 'uretim' => 'Üretim tarihi', 'skt' => 'Son tüketim (SKT)',
        'parti' => 'Parti no', 'barkod' => 'Barkod', 'icindekiler' => 'İçindekiler', 'alerjen' => 'Alerjen', 'saklama' => 'Saklama koşulu', 'uretici' => 'Üretici', 'uretim_yeri' => 'Üretim yeri', 'serbest' => 'Serbest yazı']; }

    function _emAyar($sid)
    {
        _emEnsure();
        $a = DB::table('etiket_ayarlari')->where('sube_id', $sid)->first();
        $o = function_exists('_etiketAyar') ? _etiketAyar($sid) : ['dil' => 'tarayici', 'boyut' => '50x30', 'dpi' => 203, 'ip' => null, 'port' => 9100];
        $o['terazi_onek'] = (string) ($a->terazi_onek ?? '27'); $o['terazi_plu_hane'] = (int) ($a->terazi_plu_hane ?? 5);
        $o['terazi_deger'] = ($a->terazi_deger ?? 'agirlik') === 'fiyat' ? 'fiyat' : 'agirlik'; $o['terazi_ondalik'] = (int) ($a->terazi_ondalik ?? ($o['terazi_deger'] === 'fiyat' ? 2 : 3));
        $o['raf_bicim'] = isset(_emRafBicimler()[$a->raf_bicim ?? '']) ? $a->raf_bicim : 'rulo_80x38';
        $o['urun_boyut'] = isset(_emUrunBoyutlar()[$a->urun_boyut ?? '']) ? $a->urun_boyut : '58x40';
        $o['uretici_varsayilan'] = $a->uretici_varsayilan ?? null;
        return $o;
    }
    function _emAyarKaydet($sid, array $v)
    {
        _emEnsure();
        $mevcut = _emAyar($sid); unset($mevcut['dil']); $v = $v + $mevcut; // formda olmayan alan mevcut değerini korur
        $onek = preg_replace('/\D/', '', (string) ($v['terazi_onek'] ?? '27'));
        if (!preg_match('/^2\d$/', $onek)) return [false, 'Terazi barkod öneki 20 ile 29 arasında olmalı (mağaza içi barkodlar 2 ile başlar).'];
        $plu = (int) ($v['terazi_plu_hane'] ?? 5);
        if ($plu < 4 || $plu > 6) return [false, 'PLU hane sayısı 4, 5 ya da 6 olabilir.'];
        $deger = ($v['terazi_deger'] ?? 'agirlik') === 'fiyat' ? 'fiyat' : 'agirlik';
        $ond = max(0, min(3, (int) ($v['terazi_ondalik'] ?? ($deger === 'fiyat' ? 2 : 3))));
        $raf = isset(_emRafBicimler()[$v['raf_bicim'] ?? '']) ? $v['raf_bicim'] : 'rulo_80x38';
        $boy = isset(_emUrunBoyutlar()[$v['urun_boyut'] ?? '']) ? $v['urun_boyut'] : '58x40';
        $uret = mb_substr(trim((string) ($v['uretici_varsayilan'] ?? '')), 0, 255) ?: null;
        $veri = ['terazi_onek' => $onek, 'terazi_plu_hane' => $plu, 'terazi_deger' => $deger, 'terazi_ondalik' => $ond, 'raf_bicim' => $raf, 'urun_boyut' => $boy, 'uretici_varsayilan' => $uret, 'updated_at' => now()];
        if (DB::table('etiket_ayarlari')->where('sube_id', $sid)->exists()) DB::table('etiket_ayarlari')->where('sube_id', $sid)->update($veri);
        else DB::table('etiket_ayarlari')->insert($veri + ['sube_id' => $sid, 'created_at' => now()]);
        if (array_key_exists('dil', $v) && function_exists('_etiketAyarKaydet')) {
            $mev = _etiketAyar($sid);
            [$ok, $m] = _etiketAyarKaydet($sid, ['dil' => $v['dil'], 'boyut' => $v['boyut'] ?? $mev['boyut'], 'dpi' => $v['dpi'] ?? $mev['dpi'], 'ip' => $v['ip'] ?? $mev['ip'], 'port' => $v['port'] ?? $mev['port']]);
            if (!$ok) return [false, $m];
        }
        return [true, 'Ayarlar kaydedildi.'];
    }

    // ---------- EAN-13 ----------
    function _ean13Kontrol($ilk12)
    {
        $t = 0; for ($i = 0; $i < 12; $i++) $t += (int) $ilk12[$i] * ($i % 2 ? 3 : 1);
        return (string) ((10 - $t % 10) % 10);
    }
    function _ean13Gecerli($kod) { return is_string($kod) && preg_match('/^\d{13}$/', $kod) && _ean13Kontrol(substr($kod, 0, 12)) === $kod[12]; }
    function _ean13Svg($kod, $yuk = 50, $modul = 2)
    {
        $kod = preg_replace('/\D/', '', (string) $kod);
        if (strlen($kod) === 12) $kod .= _ean13Kontrol($kod);
        if (strlen($kod) !== 13) return function_exists('_c128Svg') ? _c128Svg($kod ?: '0', $yuk, $modul) : '';
        $L = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
        $G = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
        $R = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];
        $P = ['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'];
        $desen = $P[(int) $kod[0]]; $bit = '101';
        for ($i = 1; $i <= 6; $i++) $bit .= $desen[$i - 1] === 'L' ? $L[(int) $kod[$i]] : $G[(int) $kod[$i]];
        $bit .= '01010';
        for ($i = 7; $i <= 12; $i++) $bit .= $R[(int) $kod[$i]];
        $bit .= '101';
        $sol = 11 * $modul; $sag = 7 * $modul; $gen = strlen($bit) * $modul + $sol + $sag; $r = '';
        $uzun = fn ($i) => $i < 3 || ($i >= 45 && $i < 50) || $i >= 92;
        $fs = 9 * $modul; $yaziY = $yuk + $fs - 1;
        for ($i = 0; $i < strlen($bit); $i++) if ($bit[$i] === '1') $r .= '<rect x="' . ($sol + $i * $modul) . '" y="0" width="' . $modul . '" height="' . ($uzun($i) ? $yuk + $fs / 2 : $yuk) . '"/>';
        $t = '<text x="' . ($sol - 2 * $modul) . '" y="' . $yaziY . '" text-anchor="end">' . $kod[0] . '</text>'
            . '<text x="' . ($sol + 24 * $modul) . '" y="' . $yaziY . '" text-anchor="middle" letter-spacing="' . $modul . '">' . substr($kod, 1, 6) . '</text>'
            . '<text x="' . ($sol + 71 * $modul) . '" y="' . $yaziY . '" text-anchor="middle" letter-spacing="' . $modul . '">' . substr($kod, 7, 6) . '</text>';
        $h = $yuk + $fs + 2;
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $gen . ' ' . $h . '" width="' . $gen . '" height="' . $h . '" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="#fff"/><g fill="#000">' . $r . '</g><g font-family="monospace" font-size="' . $fs . '" fill="#000">' . $t . '</g></svg>';
    }
    // Barkod görsel adresi: 13 haneli geçerli EAN → EAN-13, diğer → CODE128
    function _emBarkodUrl($kod, $h = 50)
    {
        $kod = (string) $kod;
        return _ean13Gecerli($kod) ? '/etiket-ean13.svg?k=' . $kod . '&h=' . $h : '/etiket-barkod.svg?k=' . urlencode($kod) . '&h=' . $h . '&m=2';
    }

    // Barkod başka bir yerde kullanılıyor mu? (etiketli ürün, ürün/malzeme barkodu, demirbaş)
    function _emBarkodCakisma($sid, $kod, $haricEu = 0, $haricUrun = 0)
    {
        if ($kod === '' || $kod === null) return null;
        $e = DB::table('etiket_urunler')->where('sube_id', $sid)->where('barkod', $kod)->where('id', '!=', $haricEu)->value('ad');
        if ($e) return 'Bu barkod "' . $e . '" ürününde kullanılıyor.';
        if (Schema::hasTable('barkodlar')) {
            $b = DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $kod)->first();
            if ($b && !($b->tur === 'urun' && $haricUrun && (int) $b->hedef_id === (int) $haricUrun)) return 'Bu barkod başka bir ürün/malzemeye tanımlı (Barkod Yönetimi).';
        }
        if (Schema::hasTable('demirbaslar') && DB::table('demirbaslar')->where('sube_id', $sid)->where('barkod', $kod)->exists()) return 'Bu barkod bir demirbaşta kullanılıyor.';
        return null;
    }
    // Mağaza içi EAN-13 (2x önekli, terazi önekiyle çakışmaz). Yalnız bu işletmenin kasasında geçerlidir.
    function _emIcBarkodUret($sid)
    {
        $ayar = _emAyar($sid);
        $onek = $ayar['terazi_onek'] === '20' ? '21' : '20';
        for ($i = 0; $i < 40; $i++) {
            $ilk = $onek . str_pad((string) random_int(0, 9999999999), 10, '0', STR_PAD_LEFT);
            $kod = $ilk . _ean13Kontrol($ilk);
            if (!_emBarkodCakisma($sid, $kod)) return $kod;
        }
        return null;
    }

    // ---------- Fiyat ----------
    function _emMenuFiyatlar($sid, array $urunIds)
    {
        $urunIds = array_values(array_filter(array_unique($urunIds)));
        return $urunIds ? DB::table('urunler')->where('sube_id', $sid)->whereIn('id', $urunIds)->get(['id', 'ad', 'fiyat', 'aktif'])->keyBy('id') : collect();
    }
    function _emNetKg($u)
    {
        $n = (float) ($u->net_miktar ?? 0); if ($n <= 0) return null;
        return match ($u->net_birim ?? 'g') { 'g' => $n / 1000, 'kg' => $n, 'ml' => $n / 1000, 'lt' => $n, default => null };
    }
    // Ürünün o anki fiyat bilgisi: taban (menü ya da elle), geçerli (indirimliyse indirimli), eski (son 30 gün en düşük), birim fiyat metni
    function _emFiyatBilgi($u, $menu = null, $enDusuk30 = null)
    {
        $taban = $u->fiyat !== null && $u->fiyat !== '' ? (float) $u->fiyat : ($menu ? (float) $menu->fiyat : 0.0);
        $ind = (float) ($u->indirimli_fiyat ?? 0) > 0 && (float) $u->indirimli_fiyat < $taban && (empty($u->indirim_bitis) || $u->indirim_bitis >= today()->toDateString());
        $gecerli = $ind ? (float) $u->indirimli_fiyat : $taban;
        $eski = $ind ? ($enDusuk30 !== null && $enDusuk30 > 0 ? min($taban, (float) $enDusuk30) : $taban) : null;
        if ($eski !== null && $eski <= $gecerli) $eski = $taban; // 30 gün içinde daha düşük fiyattan satıldıysa indirim sayılmaz; tabanı göster
        $birimAd = null; $birimFiyat = null;
        if (in_array($u->birim, ['kg', 'lt'], true)) { $birimAd = $u->birim === 'kg' ? 'kg' : 'lt'; $birimFiyat = $gecerli; }
        elseif (($kg = _emNetKg($u)) && $kg > 0) { $birimAd = in_array($u->net_birim, ['ml', 'lt'], true) ? 'lt' : 'kg'; $birimFiyat = round($gecerli / $kg, 2); }
        return ['taban' => round($taban, 2), 'gecerli' => round($gecerli, 2), 'indirimde' => $ind, 'eski' => $eski !== null ? round($eski, 2) : null,
            'birim_ad' => $birimAd, 'birim_fiyat' => $birimFiyat, 'menu_fiyat' => $menu ? (float) $menu->fiyat : null];
    }
    function _emTl($v) { return number_format((float) $v, 2, ',', '.'); }
    function _emNetMetin($u)
    {
        $n = (float) ($u->net_miktar ?? 0); if ($n <= 0) return null;
        $s = rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
        return $s . ' ' . ($u->net_birim ?? 'g');
    }
    // Taban fiyat değiştiyse geçmişe yaz (son 30 gün en düşük fiyat hesabı için)
    function _emFiyatGecmisYaz($sid, $euId, $fiyat)
    {
        if ($fiyat === null || $fiyat <= 0) return;
        $son = DB::table('etiket_fiyat_gecmis')->where('eu_id', $euId)->orderByDesc('id')->value('fiyat');
        if ($son === null || abs((float) $son - $fiyat) > 0.004) DB::table('etiket_fiyat_gecmis')->insert(['sube_id' => $sid, 'eu_id' => $euId, 'fiyat' => round($fiyat, 2), 'created_at' => now()]);
    }

    // Liste: her ürün + fiyat bilgisi + raf kartı yeniden basılmalı mı
    function _emListe($sid, $sadeceAktif = false)
    {
        _emEnsure();
        $q = DB::table('etiket_urunler')->where('sube_id', $sid);
        if ($sadeceAktif) $q->where('aktif', 1);
        $rows = $q->orderBy('ad')->get();
        $menu = _emMenuFiyatlar($sid, $rows->pluck('urun_id')->all());
        $dus = $rows->isEmpty() ? collect() : DB::table('etiket_fiyat_gecmis')->where('sube_id', $sid)->where('created_at', '>=', now()->subDays(30))->groupBy('eu_id')->select('eu_id', DB::raw('MIN(fiyat) as m'))->pluck('m', 'eu_id');
        foreach ($rows as $u) {
            $m = $u->urun_id ? ($menu[$u->urun_id] ?? null) : null;
            if ($u->urun_id && $m && ($u->fiyat === null || $u->fiyat === '')) _emFiyatGecmisYaz($sid, $u->id, (float) $m->fiyat); // menüden gelen fiyat değişimi
            $u->menu_ad = $m->ad ?? null;
            $u->fb = _emFiyatBilgi($u, $m, $dus[$u->id] ?? null);
            $u->tur_liste = array_values(array_filter(explode(',', (string) $u->turler)));
            $u->raf_bekliyor = in_array('raf', $u->tur_liste, true) && $u->aktif && ($u->raf_fiyat === null || abs((float) $u->raf_fiyat - $u->fb['gecerli']) > 0.004);
        }
        return $rows;
    }
    function _emBul($sid, $id) { return DB::table('etiket_urunler')->where('sube_id', $sid)->where('id', (int) $id)->first(); }
    function _emTekBilgi($sid, $u)
    {
        $m = $u->urun_id ? DB::table('urunler')->where('sube_id', $sid)->where('id', $u->urun_id)->first(['id', 'ad', 'fiyat']) : null;
        $d = DB::table('etiket_fiyat_gecmis')->where('eu_id', $u->id)->where('created_at', '>=', now()->subDays(30))->min('fiyat');
        $u->menu_ad = $m->ad ?? null;
        $u->fb = _emFiyatBilgi($u, $m, $d !== null ? (float) $d : null);
        $u->tur_liste = array_values(array_filter(explode(',', (string) $u->turler)));
        return $u;
    }

    function _emKaydet($sid, $p, array $v)
    {
        _emEnsure();
        $id = (int) ($v['id'] ?? 0);
        $esk = $id ? _emBul($sid, $id) : null;
        if ($id && !$esk) return [false, 'Ürün bulunamadı.', 0];
        $urunId = (int) ($v['urun_id'] ?? 0) ?: null;
        $menu = $urunId ? DB::table('urunler')->where('sube_id', $sid)->where('id', $urunId)->first(['id', 'ad', 'fiyat']) : null;
        if ($urunId && !$menu) return [false, 'Bağlanan menü ürünü bulunamadı.', 0];
        $ad = trim((string) ($v['ad'] ?? '')) ?: ($menu->ad ?? '');
        if ($ad === '') return [false, 'Ürün adı zorunlu.', 0];
        $sayi = fn ($k) => ($x = trim(str_replace(',', '.', (string) ($v[$k] ?? '')))) !== '' && is_numeric($x) ? (float) $x : null;
        $s = fn ($k, $n) => ($x = trim((string) ($v[$k] ?? ''))) !== '' ? mb_substr($x, 0, $n) : null;
        $fiyat = $sayi('fiyat');
        if (!$menu && ($fiyat === null || $fiyat <= 0)) return [false, 'Menüye bağlı olmayan ürün için fiyat girilmeli.', 0];
        $turler = $v['turler'] ?? [];
        if (is_string($turler)) $turler = explode(',', $turler);
        $turler = array_values(array_intersect(array_keys(_emTurler()), array_map('strval', (array) $turler)));
        if (!$turler) return [false, 'En az bir etiket türü seç (paket, tartılı, raf kartı).', 0];
        $birim = isset(_emBirimler()[$v['birim'] ?? '']) ? $v['birim'] : 'adet';
        if (in_array('tarti', $turler, true) && $birim !== 'kg') return [false, 'Tartılı satılan ürünün fiyat birimi kilogram olmalı (terazi kg fiyatıyla hesaplar).', 0];
        // Barkod
        $barkod = preg_replace('/\s+/', '', (string) ($v['barkod'] ?? ''));
        $btip = 'yok';
        if (($v['barkod_islem'] ?? '') === 'ic_uret' && $barkod === '') { $barkod = _emIcBarkodUret($sid) ?? ''; if ($barkod === '') return [false, 'Mağaza içi barkod üretilemedi, tekrar dene.', 0]; $btip = 'ic'; }
        elseif ($barkod !== '') {
            if (!preg_match('/^[\x21-\x7E]{4,32}$/', $barkod)) return [false, 'Barkod yalnız harf ve rakamdan oluşabilir (4–32 karakter).', 0];
            if (preg_match('/^\d{13}$/', $barkod) && !_ean13Gecerli($barkod)) return [false, '13 haneli barkodun son hanesi (kontrol hanesi) tutmuyor. Barkodu ambalajdan tekrar okut.', 0];
            $btip = preg_match('/^2\d{12}$/', $barkod) ? 'ic' : 'resmi';
            if ($esk && $esk->barkod === $barkod) $btip = $esk->barkod_tip ?: $btip;
        }
        if ($barkod !== '' && ($m = _emBarkodCakisma($sid, $barkod, $id, $urunId))) return [false, $m, 0];
        $plu = (int) ($v['plu'] ?? 0) ?: null;
        if (in_array('tarti', $turler, true)) {
            $ayar = _emAyar($sid); $max = (int) str_repeat('9', $ayar['terazi_plu_hane']);
            if (!$plu) { $plu = (int) DB::table('etiket_urunler')->where('sube_id', $sid)->max('plu') + 1; }
            if ($plu > $max) return [false, 'PLU numarası ' . $ayar['terazi_plu_hane'] . ' haneyi geçemez (en çok ' . $max . ').', 0];
            $pv = DB::table('etiket_urunler')->where('sube_id', $sid)->where('plu', $plu)->where('id', '!=', $id)->value('ad');
            if ($pv) return [false, 'PLU ' . $plu . ' "' . $pv . '" ürününde kullanılıyor.', 0];
        }
        $ind = $sayi('indirimli_fiyat');
        $tarih = fn ($k) => !empty($v[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v[$k]) ? $v[$k] : null;
        $sablon = (int) ($v['sablon_id'] ?? 0) ?: null;
        if ($sablon && !DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', $sablon)->exists()) $sablon = null;
        $veri = ['urun_id' => $urunId, 'ad' => mb_substr($ad, 0, 150), 'kategori' => $s('kategori', 60), 'birim' => $birim,
            'fiyat' => $fiyat !== null && $fiyat > 0 ? round($fiyat, 2) : null,
            'net_miktar' => ($n = $sayi('net_miktar')) !== null && $n > 0 ? $n : null, 'net_birim' => isset(_emNetBirimler()[$v['net_birim'] ?? '']) ? $v['net_birim'] : 'g',
            'dara_g' => max(0, (float) ($sayi('dara_g') ?? 0)), 'barkod' => $barkod !== '' ? $barkod : null, 'barkod_tip' => $barkod !== '' ? $btip : 'yok', 'plu' => $plu,
            'raf_omru_gun' => ($r = (int) ($v['raf_omru_gun'] ?? 0)) > 0 ? min(3650, $r) : null,
            'icindekiler' => $s('icindekiler', 3000), 'alerjen' => $s('alerjen', 255), 'saklama' => $s('saklama', 150), 'uretici' => $s('uretici', 255),
            'uretim_yeri' => $s('uretim_yeri', 100), 'yerli' => !empty($v['yerli']) ? 1 : 0, 'turler' => implode(',', $turler), 'sablon_id' => $sablon,
            'indirimli_fiyat' => $ind !== null && $ind > 0 ? round($ind, 2) : null, 'indirim_bitis' => $tarih('indirim_bitis'),
            'aktif' => array_key_exists('aktif', $v) ? (!empty($v['aktif']) ? 1 : 0) : 1, 'updated_at' => now()];
        DB::transaction(function () use ($sid, &$id, $esk, $veri, $urunId, $barkod) {
            if ($esk) DB::table('etiket_urunler')->where('id', $id)->update($veri);
            else $id = DB::table('etiket_urunler')->insertGetId($veri + ['sube_id' => $sid, 'created_at' => now()]);
            // Menüye bağlıysa barkodu kasanın tanıdığı barkod tablosuna da yaz (eskisini kaldır)
            if (Schema::hasTable('barkodlar')) {
                if ($esk && $esk->barkod && $esk->urun_id && ($esk->barkod !== $barkod || (int) $esk->urun_id !== (int) $urunId))
                    DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $esk->barkod)->where('tur', 'urun')->where('hedef_id', $esk->urun_id)->delete();
                if ($urunId && $barkod !== '' && !DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $barkod)->exists())
                    DB::table('barkodlar')->insert(['sube_id' => $sid, 'barkod' => $barkod, 'tur' => 'urun', 'hedef_id' => $urunId, 'created_at' => now()]);
            }
        });
        $u = _emTekBilgi($sid, _emBul($sid, $id));
        _emFiyatGecmisYaz($sid, $id, $u->fb['taban']);
        return [true, $esk ? 'Ürün güncellendi.' : 'Ürün eklendi.', $id];
    }
    function _emSil($sid, $id)
    {
        $u = _emBul($sid, $id);
        if (!$u) return [false, 'Ürün bulunamadı.'];
        if ($u->barkod && $u->urun_id && Schema::hasTable('barkodlar'))
            DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $u->barkod)->where('tur', 'urun')->where('hedef_id', $u->urun_id)->delete();
        DB::table('etiket_urunler')->where('id', $u->id)->delete();
        DB::table('etiket_fiyat_gecmis')->where('eu_id', $u->id)->delete();
        return [true, $u->ad . ' silindi. Basım geçmişi korunur.'];
    }

    // ---------- Terazi barkodu ----------
    function _emTeraziBarkod($ayar, $plu, $deger)
    {
        $ph = $ayar['terazi_plu_hane']; $dh = 10 - $ph;
        $d = (int) round($deger * (10 ** $ayar['terazi_ondalik']));
        if ($d < 0 || strlen((string) $d) > $dh || strlen((string) $plu) > $ph) return null;
        $ilk = $ayar['terazi_onek'] . str_pad((string) $plu, $ph, '0', STR_PAD_LEFT) . str_pad((string) $d, $dh, '0', STR_PAD_LEFT);
        return $ilk . _ean13Kontrol($ilk);
    }
    // Terazi barkodunu çöz: ['plu','deger','urun'(etiket_urunler satırı),'kg','tutar'] ya da null
    function _emTeraziCoz($sid, $kod)
    {
        $kod = trim((string) $kod);
        if (!_ean13Gecerli($kod)) return null;
        $ayar = _emAyar($sid);
        if (substr($kod, 0, 2) !== $ayar['terazi_onek']) return null;
        $ph = $ayar['terazi_plu_hane'];
        $plu = (int) substr($kod, 2, $ph); $ham = (int) substr($kod, 2 + $ph, 10 - $ph);
        $deger = $ham / (10 ** $ayar['terazi_ondalik']);
        $u = DB::table('etiket_urunler')->where('sube_id', $sid)->where('plu', $plu)->first();
        $o = ['plu' => $plu, 'deger' => $deger, 'tip' => $ayar['terazi_deger'], 'urun' => $u, 'kg' => null, 'tutar' => null, 'kg_fiyat' => null];
        if ($u) {
            $u = _emTekBilgi($sid, $u); $kgF = $u->fb['gecerli']; $o['urun'] = $u; $o['kg_fiyat'] = $kgF;
            if ($ayar['terazi_deger'] === 'fiyat') { $o['tutar'] = round($deger, 2); $o['kg'] = $kgF > 0 ? round($deger / $kgF, 3) : null; }
            else { $o['kg'] = round($deger, 3); $o['tutar'] = round($deger * $kgF, 2); }
        }
        return $o;
    }
    // PLU dosyası (CSV; çoğu terazi yazılımı içe alır: CAS CL-Works, Dibal, Bizerba…)
    function _emPluCsv($sid)
    {
        $f = fopen('php://temp', 'w+'); fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, ['PLU', 'Ürün Adı', 'Birim Fiyat (kg)', 'Barkod Öneki', 'Raf Ömrü (gün)', 'Dara (g)', 'İçindekiler'], ';');
        $ayar = _emAyar($sid);
        foreach (_emListe($sid, true) as $u) if (in_array('tarti', $u->tur_liste, true) && $u->plu)
            fputcsv($f, [$u->plu, $u->ad, number_format($u->fb['gecerli'], 2, ',', ''), $ayar['terazi_onek'], $u->raf_omru_gun, rtrim(rtrim(number_format((float) $u->dara_g, 1, ',', ''), '0'), ','), $u->icindekiler], ';');
        rewind($f); return stream_get_contents($f);
    }

    // ---------- Basım ----------
    function _emPartiNo($sid, $euId, $tarih)
    {
        $gun = date('ymd', strtotime($tarih));
        $n = DB::table('etiket_basimlar')->where('sube_id', $sid)->where('eu_id', $euId)->where('parti_no', 'like', $gun . '-%')->count() + 1;
        return $gun . '-' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    }
    // Basım kaydı aç: paket ya da tartı. Dönüş [ok, mesaj, basim_id]
    function _emBasimAc($sid, $p, array $v)
    {
        _emEnsure();
        $u = _emBul($sid, $v['id'] ?? 0);
        if (!$u) return [false, 'Ürün bulunamadı.', 0];
        $u = _emTekBilgi($sid, $u);
        $tur = ($v['tur'] ?? 'paket') === 'tarti' ? 'tarti' : 'paket';
        if (!in_array($tur, $u->tur_liste, true)) return [false, $u->ad . ' için "' . _emTurler()[$tur] . '" seçilmemiş. Ürün kartından etiket türünü aç.', 0];
        $adet = max(1, min(500, (int) ($v['adet'] ?? 1)));
        $uretim = !empty($v['uretim']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v['uretim']) ? $v['uretim'] : today()->toDateString();
        $skt = !empty($v['skt']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v['skt']) ? $v['skt'] : ($u->raf_omru_gun ? date('Y-m-d', strtotime($uretim . ' +' . (int) $u->raf_omru_gun . ' days')) : null);
        if ($skt && $skt < $uretim) return [false, 'Son tüketim tarihi üretim tarihinden önce olamaz.', 0];
        $mod = in_array($v['mod'] ?? 'tam', ['tam', 'kucuk', 'sablon'], true) ? ($v['mod'] ?? 'tam') : 'tam';
        if ($mod === 'sablon' && !$u->sablon_id) return [false, 'Bu ürüne tasarım (şablon) atanmamış. Ürün Etiketi sekmesinden tasarım yükleyip ürüne ata.', 0];
        $boyut = isset(_emUrunBoyutlar()[$v['boyut'] ?? '']) ? $v['boyut'] : _emAyar($sid)['urun_boyut'];
        $miktar = null; $tutar = null; $barkod = $u->barkod;
        if ($tur === 'tarti') {
            $g = (float) str_replace(',', '.', (string) ($v['gram'] ?? 0));
            if ($g <= 0) return [false, 'Tartı ağırlığını gram olarak gir.', 0];
            $netG = max(0, $g - (!empty($v['dara_dus']) ? (float) $u->dara_g : 0));
            if ($netG <= 0) return [false, 'Dara düşülünce ağırlık sıfır kalıyor.', 0];
            $miktar = round($netG / 1000, 3); $tutar = round($miktar * $u->fb['gecerli'], 2);
            if (!$u->plu) return [false, 'Ürünün PLU numarası yok. Ürün kartını kaydet, PLU otomatik verilir.', 0];
            $ayar = _emAyar($sid);
            $barkod = _emTeraziBarkod($ayar, $u->plu, $ayar['terazi_deger'] === 'fiyat' ? $tutar : $miktar);
            if (!$barkod) return [false, 'Değer terazi barkoduna sığmıyor (çok büyük). Terazi ayarındaki hane sayısını kontrol et.', 0];
        }
        $id = DB::table('etiket_basimlar')->insertGetId(['sube_id' => $sid, 'eu_id' => $u->id, 'tur' => $tur, 'mod' => $tur === 'tarti' ? 'tam' : $mod, 'boyut' => $boyut,
            'parti_no' => _emPartiNo($sid, $u->id, $uretim), 'uretim_tarih' => $uretim, 'skt' => $skt, 'adet' => $adet, 'miktar' => $miktar, 'tutar' => $tutar,
            'barkod' => $barkod, 'personel_id' => $p->id ?? null, 'created_at' => now()]);
        return [true, $adet . ' etiket hazır.', $id];
    }
    // Ekranda / yazıcıda kullanılacak etiket verisi (basım kaydından)
    function _emBasimEtiket($sid, $b)
    {
        $u = DB::table('etiket_urunler')->where('id', $b->eu_id)->first();
        if (!$u) return null;
        $u = _emTekBilgi($sid, $u);
        $d = fn ($t) => $t ? date('d.m.Y', strtotime($t)) : null;
        $ayar = _emAyar($sid);
        $e = ['ad' => $u->ad, 'tur' => $b->tur, 'mod' => $b->mod, 'adet' => (int) $b->adet, 'barkod' => $b->barkod, 'parti' => $b->parti_no,
            'uretim' => $d($b->uretim_tarih), 'skt' => $d($b->skt), 'net' => _emNetMetin($u), 'fiyat' => _emTl($u->fb['gecerli']) . ' ₺',
            'birim_fiyat' => $u->fb['birim_fiyat'] ? '1 ' . $u->fb['birim_ad'] . ': ' . _emTl($u->fb['birim_fiyat']) . ' ₺' : null,
            'icindekiler' => $u->icindekiler, 'alerjen' => $u->alerjen, 'saklama' => $u->saklama, 'uretici' => $u->uretici ?: $ayar['uretici_varsayilan'],
            'uretim_yeri' => $u->uretim_yeri, 'yerli' => (bool) $u->yerli, 'sablon_id' => $u->sablon_id];
        if ($b->tur === 'tarti') {
            $e['net'] = rtrim(rtrim(number_format((float) $b->miktar, 3, ',', '.'), '0'), ',') . ' kg';
            $e['fiyat'] = _emTl($b->tutar) . ' ₺';
            $e['birim_fiyat'] = '1 kg: ' . _emTl($u->fb['gecerli']) . ' ₺';
        }
        return $e;
    }
    // Raf kartı verisi
    function _emRafEtiket($sid, $u)
    {
        $isl = (string) (DB::table('subeler')->where('id', $sid)->value('ad') ?? '');
        return ['ad' => $u->ad, 'fiyat' => _emTl($u->fb['gecerli']), 'eski' => $u->fb['indirimde'] && $u->fb['eski'] ? _emTl($u->fb['eski']) : null,
            'birim' => $u->birim === 'kg' ? 'kg' : ($u->birim === 'lt' ? 'lt' : null),
            'birim_fiyat' => $u->fb['birim_fiyat'] && $u->birim === 'adet' ? '1 ' . $u->fb['birim_ad'] . ': ' . _emTl($u->fb['birim_fiyat']) . ' ₺' : null,
            'net' => $u->birim === 'adet' ? _emNetMetin($u) : null, 'uretim_yeri' => $u->uretim_yeri, 'yerli' => (bool) $u->yerli,
            'barkod' => $u->barkod, 'tarih' => date('d.m.Y'), 'isletme' => $isl, 'indirim_bitis' => $u->fb['indirimde'] && $u->indirim_bitis ? date('d.m.Y', strtotime($u->indirim_bitis)) : null];
    }
    function _emRafBasildi($sid, array $ids)
    {
        foreach (_emListe($sid)->whereIn('id', $ids) as $u)
            DB::table('etiket_urunler')->where('id', $u->id)->update(['raf_fiyat' => $u->fb['gecerli'], 'raf_basim' => now()]);
    }

    // ---------- Etiket yazıcısı komutu (ZPL / TSPL) — satır listesi + barkod ----------
    // $etiketler: [['satirlar' => [[metin, boy(1-4), kalin]], 'barkod' => '...', 'adet' => n]]
    function _emKomut(array $ayar, $wmm, $hmm, array $etiketler)
    {
        $dpm = ($ayar['dpi'] ?? 203) >= 300 ? 12 : 8;
        $W = (int) round($wmm * $dpm); $H = (int) round($hmm * $dpm); $k = 2 * $dpm; $out = '';
        $yuk = [1 => 2.6, 2 => 3.4, 3 => 4.6, 4 => 7.0]; // mm
        foreach ($etiketler as $e) {
            $kod = (string) ($e['barkod'] ?? ''); $ean = _ean13Gecerli($kod);
            $bh = $kod !== '' ? (int) round(max(6, min(14, $hmm * 0.28)) * $dpm) : 0;
            $tspl = ($ayar['dil'] ?? '') === 'tspl';
            if ($tspl) $out .= "SIZE {$wmm} mm,{$hmm} mm\r\nGAP 2 mm,0 mm\r\nDIRECTION 1\r\nREFERENCE 0,0\r\nCLS\r\n";
            else $out .= "^XA^CI28^PW{$W}^LL{$H}^LH0,0\n";
            $y = $k; $sinir = $H - $k - ($bh ? $bh + 3 * $dpm : 0);
            foreach ($e['satirlar'] as $s) {
                [$metin, $boy, $kalin] = $s + [null, 2, false];
                $metin = trim((string) $metin); if ($metin === '') continue;
                $tf = [1 => ['2', 12, 20], 2 => ['3', 16, 24], 3 => ['4', 24, 32], 4 => ['5', 32, 48]][$boy]; // TSPL yazıcı içi font: [no, en, boy] nokta (203 dpi)
                $hh = $tspl ? (int) round($tf[2] * $dpm / 8) : (int) round($yuk[$boy] * $dpm); if ($y + $hh > $sinir) break;
                $maks = max(6, (int) floor(($W - 2 * $k) / ($tspl ? $tf[1] * $dpm / 8 : $hh * 0.55)));
                $t = _etiketKisalt(_etiketAscii($metin), $maks);
                if ($tspl) $out .= 'TEXT ' . $k . ',' . $y . ',"' . $tf[0] . '",0,1,1,"' . str_replace('"', "'", $t) . "\"\r\n";
                else $out .= '^FO' . $k . ',' . $y . '^A0N,' . $hh . ',' . ($kalin ? (int) ($hh * 1.05) : $hh) . '^FB' . ($W - 2 * $k) . ',1,0,L^FD' . str_replace(['^', '~'], ' ', $t) . "^FS\n";
                $y += $hh + (int) ($dpm * 0.8);
            }
            if ($kod !== '') {
                $by = $H - $k - $bh - 3 * $dpm;
                if ($ean) { $modul = max(1, min(3, (int) floor(($W - 2 * $k) / 113))); $bx = max(0, (int) (($W - 95 * $modul) / 2));
                    $out .= $tspl ? 'BARCODE ' . $bx . ',' . $by . ',"EAN13",' . $bh . ',1,0,' . $modul . ',' . $modul . ',"' . substr($kod, 0, 12) . "\"\r\n" : '^FO' . $bx . ',' . $by . '^BY' . $modul . '^BEN,' . $bh . ',Y,N^FD' . substr($kod, 0, 12) . "^FS\n"; }
                else { $mod = count(_c128Moduller($kod)); $modul = max(1, min(3, (int) floor(($W - 2 * $k) / max(1, $mod)))); $bx = max(0, (int) (($W - $mod * $modul) / 2));
                    $out .= $tspl ? 'BARCODE ' . $bx . ',' . $by . ',"128M",' . $bh . ',1,0,' . $modul . ',' . $modul . ',"' . $kod . "\"\r\n" : '^FO' . $bx . ',' . $by . '^BY' . $modul . '^BCN,' . $bh . ',Y,N,N^FD' . str_replace(['^', '~'], ' ', $kod) . "^FS\n"; }
            }
            $out .= $tspl ? 'PRINT 1,' . (int) $e['adet'] . "\r\n" : '^PQ' . (int) $e['adet'] . "^XZ\n";
        }
        return $out;
    }
    function _emPaketSatirlar($e)
    {
        if ($e['mod'] === 'kucuk') return [[($e['uretim'] ? 'ÜT: ' . $e['uretim'] : ''), 1, false], [($e['skt'] ? 'SKT: ' . $e['skt'] : ''), 2, true], ['Parti: ' . $e['parti'], 1, false]];
        return [[$e['ad'], 3, true], [trim(($e['net'] ?? '') . '   ' . $e['fiyat']), 2, true], [$e['birim_fiyat'], 1, false],
            [trim(($e['uretim'] ? 'ÜT: ' . $e['uretim'] : '') . ($e['skt'] ? '  SKT: ' . $e['skt'] : '')), 1, false], ['Parti: ' . $e['parti'], 1, false]];
    }
    function _emRafSatirlar($r)
    {
        return [[$r['ad'], 2, true], [$r['eski'] ? 'INDIRIM - eski fiyat: ' . $r['eski'] . ' TL' : '', 1, true], [$r['fiyat'] . ' TL' . ($r['birim'] ? ' / ' . $r['birim'] : ''), 4, true],
            [trim(($r['birim_fiyat'] ?? '') . ($r['net'] ? '  ' . $r['net'] : '')), 1, false], [trim(($r['uretim_yeri'] ? 'Üretim yeri: ' . $r['uretim_yeri'] : '') . ($r['yerli'] ? '  YERLİ ÜRETİM' : '')), 1, false], [$r['tarih'], 1, false]];
    }
}

// ============================================================================
// WEB
// ============================================================================
$emKapi = function () { $p = null; try { $p = _posPersonel(); } catch (\Throwable $e) {} return [$p, function_exists('_dmYetki') ? _dmYetki($p) : null]; };
$emRed = function ($p) { return $p ? response(view('yetkisiz', ['sayfa' => '/barkod-etiket']), 403) : redirect('/pos-giris'); };

Route::get('/etiket-ean13.svg', function (Request $r) {
    $k = preg_replace('/\D/', '', (string) $r->query('k', ''));
    return response(_ean13Svg($k !== '' ? substr($k, 0, 13) : '0000000000000', max(20, min(200, (int) $r->query('h', 50))), max(1, min(4, (int) $r->query('m', 2)))), 200,
        ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=86400']);
});

$emSayfa = function (Request $r, $sekme) use ($emKapi, $emRed) {
    [$p, $y] = $emKapi();
    if (!$p || !$y) return $emRed($p);
    _emEnsure(); $sid = _webSubeId();
    $liste = _emListe($sid);
    $ayar = _emAyar($sid);
    $veri = ['sekme' => $sekme, 'liste' => $liste, 'ayar' => $ayar, 'duzenler' => $y === 'duzenle', 'ara' => (string) $r->query('q', '')];
    $veri['menu'] = DB::table('urunler')->where('sube_id', $sid)->orderBy('ad')->get(['id', 'ad', 'fiyat']);
    $veri['sablonlar'] = DB::table('etiket_sablonlar')->where('sube_id', $sid)->orderBy('ad')->get();
    $veri['ozet'] = ['adet' => $liste->where('aktif', 1)->count(), 'bagli' => $liste->whereNotNull('urun_id')->count(),
        'tarti' => $liste->filter(fn ($u) => in_array('tarti', $u->tur_liste, true))->count(), 'raf' => $liste->where('raf_bekliyor', true)->count(),
        'barkodsuz' => $liste->filter(fn ($u) => !$u->barkod && !in_array('tarti', $u->tur_liste, true))->count(),
        'basim7' => (int) DB::table('etiket_basimlar')->where('sube_id', $sid)->where('created_at', '>=', now()->subDays(7))->sum('adet')];
    if (in_array($sekme, ['urun', 'tarti'], true)) {
        $veri['basimlar'] = DB::table('etiket_basimlar')->where('sube_id', $sid)->where('tur', $sekme === 'tarti' ? 'tarti' : 'paket')->orderByDesc('id')->limit(40)->get();
        $veri['persAd'] = DB::table('personeller')->where('sube_id', $sid)->pluck('ad', 'id');
    }
    if ($sekme === 'urun' && $r->filled('sablon')) $veri['sablonDuz'] = DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', (int) $r->query('sablon'))->first();
    $veri['secili'] = (int) $r->query('id', 0);
    $veri['test'] = null;
    if ($sekme === 'tarti' && $r->filled('coz')) $veri['test'] = ['kod' => (string) $r->query('coz'), 'sonuc' => _emTeraziCoz($sid, (string) $r->query('coz'))];
    return view('etiket_merkezi', $veri);
};
Route::get('/barkod-etiket', fn (Request $r) => $emSayfa($r, 'urunler'));
Route::get('/barkod-etiket/urun-etiketi', fn (Request $r) => $emSayfa($r, 'urun'));
Route::get('/barkod-etiket/tarti', fn (Request $r) => $emSayfa($r, 'tarti'));
Route::get('/barkod-etiket/raf', fn (Request $r) => $emSayfa($r, 'raf'));
Route::get('/barkod-etiket/barkod', fn (Request $r) => $emSayfa($r, 'barkod'));
Route::get('/barkod-etiket/ayarlar', fn (Request $r) => $emSayfa($r, 'ayar'));

Route::post('/barkod-etiket/kaydet', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi();
    if ($y !== 'duzenle') return back()->with('hata', 'Ürün eklemek/düzenlemek için yetkin yok.');
    [$ok, $msg, $id] = _emKaydet(_webSubeId(), $p, $r->all());
    if (!$ok) return back()->withInput()->with('hata', $msg)->with('em_pop', $r->input('id') ? 'duz' . $r->input('id') : 'yeni');
    return redirect('/barkod-etiket')->with('ok', $msg);
});
Route::post('/barkod-etiket/sil', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi();
    if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); [$ok, $msg] = _emSil(_webSubeId(), (int) $r->input('id'));
    return redirect('/barkod-etiket')->with($ok ? 'ok' : 'hata', $msg);
});
Route::post('/barkod-etiket/ayar', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi();
    if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    [$ok, $msg] = _emAyarKaydet(_webSubeId(), $r->all());
    return back()->with($ok ? 'ok' : 'hata', $msg);
});
Route::get('/barkod-etiket/plu.csv', function () use ($emKapi) {
    [$p, $y] = $emKapi(); if (!$y) return redirect('/barkod-etiket');
    _emEnsure();
    return response(_emPluCsv(_webSubeId()), 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="terazi-plu-' . date('Y-m-d') . '.csv"']);
});

// Basım: kayıt aç → yazdırma sayfası (ürün etiketi + tartı). Etiket basmak "görüntüle" yetkisiyle de olur (sahada personel basar).
Route::post('/barkod-etiket/bas', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); $js = $r->expectsJson();
    if (!$y) return $js ? response()->json(['ok' => false, 'msg' => 'Yetkin yok.'], 403) : back()->with('hata', 'Yetkin yok.');
    [$ok, $msg, $id] = _emBasimAc(_webSubeId(), $p, $r->all());
    if (!$ok) return $js ? response()->json(['ok' => false, 'msg' => $msg], 422) : back()->withInput()->with('hata', $msg);
    if ($js) return response()->json(['ok' => true, 'url' => '/barkod-etiket/yazdir/' . $id]); // sayfa içi popup basım
    return redirect('/barkod-etiket/yazdir/' . $id . ($r->input('format') ? '?format=' . urlencode($r->input('format')) : ''));
});
Route::get('/barkod-etiket/yazdir/{id}', function (Request $r, $id) use ($emKapi, $emRed) {
    [$p, $y] = $emKapi(); if (!$p || !$y) return $emRed($p);
    _emEnsure(); $sid = _webSubeId();
    $b = DB::table('etiket_basimlar')->where('sube_id', $sid)->where('id', (int) $id)->first();
    if (!$b) return redirect('/barkod-etiket/urun-etiketi')->with('hata', 'Basım bulunamadı.');
    $e = _emBasimEtiket($sid, $b);
    if (!$e) return redirect('/barkod-etiket/urun-etiketi')->with('hata', 'Ürün silinmiş.');
    $boy = _emUrunBoyutlar()[$b->boyut] ?? _emUrunBoyutlar()['58x40'];
    $sablon = $b->mod === 'sablon' && $e['sablon_id'] ? DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', $e['sablon_id'])->first() : null;
    if ($sablon) $boy = [$sablon->ad, (float) $sablon->gen_mm, (float) $sablon->yuk_mm];
    if (in_array($r->query('format'), ['zpl', 'tspl'], true)) {
        $ayar = _emAyar($sid); $ayar['dil'] = $r->query('format');
        return response(_emKomut($ayar, $boy[1], $boy[2], [['satirlar' => _emPaketSatirlar($e), 'barkod' => $e['barkod'], 'adet' => $e['adet']]]), 200,
            ['Content-Type' => 'text/plain; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="etiket-' . $b->parti_no . '.' . ($ayar['dil'] === 'zpl' ? 'zpl' : 'prn') . '"']);
    }
    return view('etiket_merkezi_yazdir', ['tip' => $b->tur === 'tarti' ? 'tarti' : 'paket', 'etiketler' => [$e], 'w' => $boy[1], 'h' => $boy[2], 'a4' => null, 'sablon' => $sablon,
        'geri' => $b->tur === 'tarti' ? '/barkod-etiket/tarti' : '/barkod-etiket/urun-etiketi', 'zplUrl' => '/barkod-etiket/yazdir/' . $b->id, 'baslik' => $e['ad'] . ' · parti ' . $b->parti_no,
        'ayar' => _emAyar($sid)]);
});

// Raf kartı: seçilenleri bas + "basıldı" işaretle (fiyat değişince yeniden kuyruğa düşer)
Route::get('/barkod-etiket/raf-yazdir', function (Request $r) use ($emKapi, $emRed) {
    [$p, $y] = $emKapi(); if (!$p || !$y) return $emRed($p);
    _emEnsure(); $sid = _webSubeId();
    $ids = array_filter(array_map('intval', explode(',', (string) $r->query('ids', ''))));
    $adet = max(1, min(20, (int) $r->query('adet', 1)));
    $ayar = _emAyar($sid);
    $bicim = isset(_emRafBicimler()[$r->query('bicim', '')]) ? $r->query('bicim') : $ayar['raf_bicim'];
    $liste = _emListe($sid)->whereIn('id', $ids)->values();
    if ($liste->isEmpty()) return redirect('/barkod-etiket/raf')->with('hata', 'Basılacak raf kartı seçilmedi.');
    $kartlar = []; foreach ($liste as $u) for ($i = 0; $i < $adet; $i++) $kartlar[] = _emRafEtiket($sid, $u);
    $b = _emRafBicimler()[$bicim];
    if (in_array($r->query('format'), ['zpl', 'tspl'], true) && !$b[3]) {
        $ayar['dil'] = $r->query('format');
        _emRafBasildi($sid, $ids);
        return response(_emKomut($ayar, $b[1], $b[2], array_map(fn ($k) => ['satirlar' => _emRafSatirlar($k), 'barkod' => $k['barkod'], 'adet' => 1], $kartlar)), 200,
            ['Content-Type' => 'text/plain; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="raf-karti-' . date('Ymd-His') . '.' . ($ayar['dil'] === 'zpl' ? 'zpl' : 'prn') . '"']);
    }
    return view('etiket_merkezi_yazdir', ['tip' => 'raf', 'etiketler' => $kartlar, 'w' => $b[1], 'h' => $b[2], 'a4' => $b[3] ? [$b[3], $b[4]] : null, 'sablon' => null, 'bicim' => $bicim,
        'geri' => '/barkod-etiket/raf', 'zplUrl' => $b[3] ? null : '/barkod-etiket/raf-yazdir?' . http_build_query(['ids' => implode(',', $ids), 'adet' => $adet, 'bicim' => $bicim]),
        'baslik' => count($kartlar) . ' raf kartı', 'ayar' => $ayar, 'rafIds' => implode(',', $ids)]);
});
Route::post('/barkod-etiket/raf-basildi', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); if (!$y) return response()->json(['ok' => 0], 403);
    _emEnsure(); _emRafBasildi(_webSubeId(), array_filter(array_map('intval', explode(',', (string) $r->input('ids', '')))));
    return ['ok' => 1];
});

// Serbest barkod / QR yazdırma (ürüne bağlı değil): yalnız verilen metni basar
Route::get('/barkod-etiket/serbest', function (Request $r) use ($emKapi, $emRed) {
    [$p, $y] = $emKapi(); if (!$p || !$y) return $emRed($p);
    _emEnsure(); $sid = _webSubeId();
    $tip = in_array($r->query('tip'), ['c128', 'ean13', 'qr'], true) ? $r->query('tip') : 'c128';
    $kod = trim((string) $r->query('kod', ''));
    $satirlar = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $kod))));
    if (!$satirlar) return redirect('/barkod-etiket/barkod')->with('hata', 'Barkoda dönüşecek metni yaz.');
    $satirlar = array_slice($satirlar, 0, 200);
    foreach ($satirlar as &$s) {
        if ($tip === 'ean13') { $d = preg_replace('/\D/', '', $s); if (strlen($d) === 12) $d .= _ean13Kontrol($d); if (!_ean13Gecerli($d)) return redirect('/barkod-etiket/barkod')->with('hata', '"' . $s . '" geçerli bir EAN-13 değil (12 ya da 13 rakam olmalı, son hane kontrol hanesi).'); $s = $d; }
        elseif ($tip === 'c128' && !preg_match('/^[\x20-\x7E]{1,48}$/', $s)) return redirect('/barkod-etiket/barkod')->with('hata', '"' . $s . '" barkoda çevrilemez: Türkçe karakter olmadan en çok 48 karakter.');
        else $s = mb_substr($s, 0, 300);
    } unset($s);
    $boyutlar = _emUrunBoyutlar(); $boy = $boyutlar[$r->query('boyut')] ?? $boyutlar['50x30'];
    $adet = max(1, min(100, (int) $r->query('adet', 1)));
    $ust = mb_substr(trim((string) $r->query('ust', '')), 0, 60);
    $et = []; foreach ($satirlar as $s) $et[] = ['kod' => $s, 'ust' => $ust, 'adet' => $adet];
    return view('etiket_merkezi_yazdir', ['tip' => 'serbest', 'kodTip' => $tip, 'etiketler' => $et, 'w' => $boy[1], 'h' => $boy[2], 'a4' => null, 'sablon' => null,
        'geri' => '/barkod-etiket/barkod', 'zplUrl' => null, 'baslik' => count($et) . ' barkod', 'ayar' => _emAyar($sid)]);
});

// İç barkod üret (barkodsuz ürünlere toplu) — yalnız menüye bağlı olmasa da işletme içi okutma için
Route::post('/barkod-etiket/ic-barkod', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); $sid = _webSubeId(); $n = 0;
    $ids = array_filter(array_map('intval', (array) $r->input('ids', [])));
    $q = DB::table('etiket_urunler')->where('sube_id', $sid)->whereNull('barkod');
    if ($ids) $q->whereIn('id', $ids);
    foreach ($q->get() as $u) {
        if (in_array('tarti', explode(',', (string) $u->turler), true) && !in_array('paket', explode(',', (string) $u->turler), true)) continue; // tartılı ürünün barkodu teraziden gelir
        $kod = _emIcBarkodUret($sid); if (!$kod) continue;
        DB::table('etiket_urunler')->where('id', $u->id)->update(['barkod' => $kod, 'barkod_tip' => 'ic', 'updated_at' => now()]);
        if ($u->urun_id && Schema::hasTable('barkodlar') && !DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $kod)->exists())
            DB::table('barkodlar')->insert(['sube_id' => $sid, 'barkod' => $kod, 'tur' => 'urun', 'hedef_id' => $u->urun_id, 'created_at' => now()]);
        $n++;
    }
    return back()->with($n ? 'ok' : 'hata', $n ? $n . ' ürüne mağaza içi barkod verildi.' : 'Barkodsuz ürün yok.');
});

// Şablon (kendi tasarımı): yükle / alanları kaydet / sil
Route::post('/barkod-etiket/sablon', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); $sid = _webSubeId();
    $id = (int) $r->input('id');
    $esk = $id ? DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', $id)->first() : null;
    if ($id && !$esk) return back()->with('hata', 'Tasarım bulunamadı.');
    $ad = mb_substr(trim((string) $r->input('ad', '')), 0, 100) ?: ($esk->ad ?? 'Tasarım');
    $gen = max(20, min(200, (float) str_replace(',', '.', (string) $r->input('gen_mm', $esk->gen_mm ?? 58))));
    $yuk = max(15, min(300, (float) str_replace(',', '.', (string) $r->input('yuk_mm', $esk->yuk_mm ?? 40))));
    $veri = ['ad' => $ad, 'gen_mm' => $gen, 'yuk_mm' => $yuk, 'updated_at' => now()];
    if ($r->hasFile('arka')) {
        $f = $r->file('arka');
        if (!$f->isValid() || !in_array(strtolower($f->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'webp'], true) || $f->getSize() > 4 * 1024 * 1024)
            return back()->with('hata', 'Tasarım PNG, JPG ya da WEBP olmalı (en çok 4 MB). PDF ise önce resim olarak kaydet.');
        $klasor = public_path('uploads/etiket_sablon');
        if (!is_dir($klasor)) @mkdir($klasor, 0775, true);
        $adi = $sid . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($f->getClientOriginalExtension());
        $f->move($klasor, $adi);
        $veri['arka'] = '/uploads/etiket_sablon/' . $adi;
    }
    if ($r->filled('alanlar')) {
        $al = json_decode((string) $r->input('alanlar'), true);
        $temiz = [];
        foreach (is_array($al) ? array_slice($al, 0, 30) : [] as $a) {
            if (!isset(_emSablonAlanlar()[$a['k'] ?? ''])) continue;
            $temiz[] = ['k' => $a['k'], 'x' => round(max(0, min(100, (float) ($a['x'] ?? 0))), 2), 'y' => round(max(0, min(100, (float) ($a['y'] ?? 0))), 2),
                'w' => round(max(3, min(100, (float) ($a['w'] ?? 30))), 2), 'h' => round(max(2, min(100, (float) ($a['h'] ?? 10))), 2),
                'fs' => round(max(4, min(40, (float) ($a['fs'] ?? 8))), 1), 'b' => !empty($a['b']) ? 1 : 0, 'hz' => in_array($a['hz'] ?? 'l', ['l', 'c', 'r'], true) ? $a['hz'] : 'l',
                't' => mb_substr((string) ($a['t'] ?? ''), 0, 120)];
        }
        $veri['alanlar'] = json_encode($temiz, JSON_UNESCAPED_UNICODE);
    } elseif (!$esk) {
        $veri['alanlar'] = json_encode([['k' => 'skt', 'x' => 5, 'y' => 70, 'w' => 45, 'h' => 10, 'fs' => 7, 'b' => 1, 'hz' => 'l', 't' => ''],
            ['k' => 'parti', 'x' => 5, 'y' => 82, 'w' => 45, 'h' => 10, 'fs' => 6, 'b' => 0, 'hz' => 'l', 't' => ''],
            ['k' => 'barkod', 'x' => 52, 'y' => 66, 'w' => 44, 'h' => 30, 'fs' => 7, 'b' => 0, 'hz' => 'c', 't' => '']], JSON_UNESCAPED_UNICODE);
    }
    if ($esk) DB::table('etiket_sablonlar')->where('id', $esk->id)->update($veri);
    else $id = DB::table('etiket_sablonlar')->insertGetId($veri + ['sube_id' => $sid, 'created_at' => now()]);
    return redirect('/barkod-etiket/urun-etiketi?sablon=' . $id . '#sbDuzen')->with('ok', $esk ? 'Yerleşim kaydedildi. Sıradaki adım: aşağıdaki "Bu tasarımı ürüne bağla" bölümünden ürünü seç.' : 'Tasarım yüklendi. Şimdi SKT, parti ve barkod kutularını tasarımdaki boş yerlere sürükle, sonra "Yerleşimi kaydet".');
});
// Hazır örnek tasarım (Nar Ekşisi 58×40): kutular boş yerlere yerleştirilmiş gelir; denemek ve öğrenmek için
Route::post('/barkod-etiket/sablon-ornek', function () use ($emKapi) {
    [$p, $y] = $emKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); $sid = _webSubeId();
    $alan = fn ($k, $x, $yy, $w, $h, $fs, $b, $hz) => ['k' => $k, 'x' => $x, 'y' => $yy, 'w' => $w, 'h' => $h, 'fs' => $fs, 'b' => $b, 'hz' => $hz, 't' => ''];
    $id = DB::table('etiket_sablonlar')->insertGetId(['sube_id' => $sid, 'ad' => 'Örnek: Nar Ekşisi 500 ml', 'gen_mm' => 58, 'yuk_mm' => 40, 'arka' => '/img/etiket-ornek-nar.svg',
        'alanlar' => json_encode([$alan('skt', 5.5, 65.5, 43, 10, 7.5, 1, 'l'), $alan('uretim', 5.5, 76.5, 43, 8.5, 6, 0, 'l'), $alan('parti', 5.5, 85.5, 43, 8.5, 6, 0, 'l'),
            $alan('barkod', 53.5, 65, 42, 30, 7, 0, 'c')], JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
    return redirect('/barkod-etiket/urun-etiketi?sablon=' . $id . '#sbDuzen')->with('ok', 'Örnek tasarım eklendi. Kutuları sürükleyip dene; beğenirsen aşağıdan bir ürüne bağlayıp bas. İstemezsen "Sil" ile kaldırabilirsin.');
});
// Tasarımı ürüne bağla (ürün kartına gitmeden)
Route::post('/barkod-etiket/sablon-bagla', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); $sid = _webSubeId();
    $s = DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', (int) $r->input('sablon_id'))->first();
    $u = _emBul($sid, (int) $r->input('eu_id'));
    if (!$s || !$u) return back()->with('hata', 'Tasarım ya da ürün bulunamadı.');
    if (!in_array('paket', explode(',', (string) $u->turler), true)) return back()->with('hata', $u->ad . ' için "Paketli ürün etiketi" seçili değil; ürün kartından aç.');
    DB::table('etiket_urunler')->where('id', $u->id)->update(['sablon_id' => $s->id, 'updated_at' => now()]);
    return redirect('/barkod-etiket/urun-etiketi?id=' . $u->id . '&mod=sablon#emBasForm')->with('ok', '"' . $s->ad . '" tasarımı ' . $u->ad . ' ürününe bağlandı. Son adım: "Kendi tasarımım" seçili olarak etiketi bas.');
});
Route::post('/barkod-etiket/sablon-sil', function (Request $r) use ($emKapi) {
    [$p, $y] = $emKapi(); if ($y !== 'duzenle') return back()->with('hata', 'Yetkin yok.');
    _emEnsure(); $sid = _webSubeId();
    $s = DB::table('etiket_sablonlar')->where('sube_id', $sid)->where('id', (int) $r->input('id'))->first();
    if (!$s) return back()->with('hata', 'Tasarım bulunamadı.');
    DB::table('etiket_sablonlar')->where('id', $s->id)->delete();
    DB::table('etiket_urunler')->where('sube_id', $sid)->where('sablon_id', $s->id)->update(['sablon_id' => null]);
    if ($s->arka && str_starts_with($s->arka, '/uploads/etiket_sablon/')) @unlink(public_path(ltrim($s->arka, '/')));
    return redirect('/barkod-etiket/urun-etiketi')->with('ok', 'Tasarım silindi.');
});

// Web POS: arama kutusuna barkod okutulunca (normal ürün barkodu ya da terazi etiketi) adisyona ekle
Route::post('/pos/barkod-ekle', function (Request $r) {
    if ($g = _posKapi()) return $g;
    $p = _posPersonel(); $sid = $p->sube_id;
    $a = DB::table('adisyonlar')->where('id', (int) $r->input('adisyon_id'))->where('sube_id', $sid)->first();
    if (!$a || $a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Adisyon açık değil.'];
    $sonuc = _emKasaBarkod($sid, (string) $r->input('kod'));
    if (!$sonuc['ok']) return $sonuc;
    _emKalemEkle($a, $sonuc, (int) $r->input('garson_id') ?: null, 'yeni');
    $t = _adisyonToplamGuncelle($a->id);
    return ['ok' => 1, 'mesaj' => $sonuc['ad_kalem'] . ' eklendi.'] + (is_array($t) ? $t : []);
});

if (!function_exists('_emKasaBarkod')) {
    // Kasada okutulan barkod → satılacak menü ürünü (+ tartılıysa kg/tutar). Dönüş ['ok', 'urun', 'adet', 'tutar', 'ad_kalem'] ya da ['ok'=>0,'hata']
    function _emKasaBarkod($sid, $kod)
    {
        _emEnsure();
        $kod = trim($kod);
        if ($kod === '') return ['ok' => 0, 'hata' => 'Boş barkod.'];
        $t = _emTeraziCoz($sid, $kod);
        if ($t) {
            if (!$t['urun']) return ['ok' => 0, 'hata' => 'Terazi etiketi okundu ama PLU ' . $t['plu'] . ' hiçbir ürüne tanımlı değil (Barkod & Etiket → Tartılı Ürün).'];
            if (!$t['urun']->urun_id) return ['ok' => 0, 'hata' => $t['urun']->ad . ' menüdeki bir ürüne bağlı değil; kasada satmak için ürün kartından menü ürününe bağla.'];
            $m = DB::table('urunler')->where('sube_id', $sid)->where('id', $t['urun']->urun_id)->first();
            if (!$m) return ['ok' => 0, 'hata' => 'Bağlı menü ürünü silinmiş.'];
            if ($t['kg'] === null || $t['tutar'] === null || $t['tutar'] <= 0) return ['ok' => 0, 'hata' => 'Terazi etiketindeki ağırlık/fiyat okunamadı.'];
            $kgS = rtrim(rtrim(number_format($t['kg'], 3, ',', '.'), '0'), ',');
            return ['ok' => 1, 'tarti' => 1, 'urun' => $m, 'adet' => max(0.01, round($t['kg'], 2)), 'tutar' => $t['tutar'], 'birim_fiyat' => $t['kg_fiyat'], 'ad_kalem' => $m->ad . ' (' . $kgS . ' kg)', 'kg' => $t['kg']];
        }
        $b = Schema::hasTable('barkodlar') ? DB::table('barkodlar')->where('sube_id', $sid)->where('barkod', $kod)->where('tur', 'urun')->first() : null;
        $uid = $b->hedef_id ?? null;
        if (!$uid) { $e = DB::table('etiket_urunler')->where('sube_id', $sid)->where('barkod', $kod)->first();
            if ($e && !$e->urun_id) return ['ok' => 0, 'hata' => $e->ad . ' menüdeki bir ürüne bağlı değil; kasada satmak için Barkod & Etiket\'te menü ürününe bağla.'];
            $uid = $e->urun_id ?? null; }
        if (!$uid) return ['ok' => 0, 'hata' => 'Bu barkod tanımlı değil: ' . $kod];
        $m = DB::table('urunler')->where('sube_id', $sid)->where('id', $uid)->first();
        if (!$m) return ['ok' => 0, 'hata' => 'Ürün silinmiş.'];
        if ($m->tukendi) return ['ok' => 0, 'hata' => $m->ad . ' tükendi olarak işaretli.'];
        $f = function_exists('_happyHourFiyat') ? _happyHourFiyat($m->id, (float) $m->fiyat, $sid) : (float) $m->fiyat;
        return ['ok' => 1, 'tarti' => 0, 'urun' => $m, 'adet' => 1, 'tutar' => $f, 'birim_fiyat' => $f, 'ad_kalem' => $m->ad];
    }
    function _emKalemEkle($a, array $s, $personelId, $durum = 'gonderildi')
    {
        DB::table('adisyon_kalemleri')->insert(['adisyon_id' => $a->id, 'urun_id' => $s['urun']->id, 'urun_adi' => mb_substr($s['ad_kalem'], 0, 190), 'adet' => $s['adet'],
            'birim_fiyat' => round((float) $s['birim_fiyat'], 2), 'tutar' => round((float) $s['tutar'], 2), 'durum' => $durum, 'personel_id' => $personelId,
            'gonderim_zamani' => $durum === 'gonderildi' ? now() : null, 'created_at' => now(), 'updated_at' => now()]);
    }
}

// ============================================================================
// API (uygulama): Bearer token. Web ile aynı fonksiyonlar.
// ============================================================================
$emApi = function (Request $r) { $p = _apiPersonel($r); return [$p, $p && function_exists('_dmYetki') ? _dmYetki($p) : null]; };
$emApiRed = fn ($p) => response()->json(['ok' => 0, 'hata' => $p ? 'Barkod & Etiket yetkin yok' : 'Oturum gerekli'], $p ? 403 : 401);
$emSatir = function ($u) {
    return ['id' => (int) $u->id, 'ad' => $u->ad, 'urun_id' => $u->urun_id ? (int) $u->urun_id : null, 'menu_ad' => $u->menu_ad ?? null, 'kategori' => $u->kategori,
        'birim' => $u->birim, 'fiyat' => $u->fiyat !== null ? (float) $u->fiyat : null, 'net_miktar' => $u->net_miktar !== null ? (float) $u->net_miktar : null, 'net_birim' => $u->net_birim,
        'dara_g' => (float) $u->dara_g, 'barkod' => $u->barkod, 'barkod_tip' => $u->barkod_tip, 'plu' => $u->plu ? (int) $u->plu : null, 'raf_omru_gun' => $u->raf_omru_gun ? (int) $u->raf_omru_gun : null,
        'icindekiler' => $u->icindekiler, 'alerjen' => $u->alerjen, 'saklama' => $u->saklama, 'uretici' => $u->uretici, 'uretim_yeri' => $u->uretim_yeri, 'yerli' => (bool) $u->yerli,
        'turler' => $u->tur_liste, 'sablon_id' => $u->sablon_id ? (int) $u->sablon_id : null, 'indirimli_fiyat' => $u->indirimli_fiyat !== null ? (float) $u->indirimli_fiyat : null,
        'indirim_bitis' => $u->indirim_bitis, 'aktif' => (bool) $u->aktif, 'fb' => $u->fb, 'raf_bekliyor' => (bool) ($u->raf_bekliyor ?? false)];
};

Route::get('/api/etiket-merkezi/liste', function (Request $r) use ($emApi, $emApiRed, $emSatir) {
    [$p, $y] = $emApi($r); if (!$y) return $emApiRed($p);
    _emEnsure(); $sid = $p->sube_id; $liste = _emListe($sid);
    return ['ok' => 1, 'duzenler' => $y === 'duzenle', 'liste' => $liste->map($emSatir)->values(), 'ayar' => _emAyar($sid),
        'menu' => DB::table('urunler')->where('sube_id', $sid)->orderBy('ad')->get(['id', 'ad', 'fiyat']),
        'sablonlar' => DB::table('etiket_sablonlar')->where('sube_id', $sid)->orderBy('ad')->get(['id', 'ad', 'gen_mm', 'yuk_mm']),
        'turler' => _emTurler(), 'birimler' => _emBirimler(), 'net_birimler' => array_keys(_emNetBirimler()),
        'urun_boyutlar' => collect(_emUrunBoyutlar())->map(fn ($b) => $b[0]), 'raf_bicimler' => collect(_emRafBicimler())->map(fn ($b) => $b[0]),
        'raf_bekleyen' => $liste->where('raf_bekliyor', true)->count(),
        'son_basimlar' => DB::table('etiket_basimlar')->where('sube_id', $sid)->orderByDesc('id')->limit(30)->get(['id', 'eu_id', 'tur', 'parti_no', 'uretim_tarih', 'skt', 'adet', 'miktar', 'tutar', 'created_at'])];
});
Route::post('/api/etiket-merkezi/kaydet', function (Request $r) use ($emApi, $emApiRed) {
    [$p, $y] = $emApi($r); if ($y !== 'duzenle') return $emApiRed($p);
    [$ok, $msg, $id] = _emKaydet($p->sube_id, $p, $r->all());
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg, 'id' => $id];
});
Route::post('/api/etiket-merkezi/sil', function (Request $r) use ($emApi, $emApiRed) {
    [$p, $y] = $emApi($r); if ($y !== 'duzenle') return $emApiRed($p);
    _emEnsure(); [$ok, $msg] = _emSil($p->sube_id, (int) $r->input('id'));
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg];
});
Route::post('/api/etiket-merkezi/ayar', function (Request $r) use ($emApi, $emApiRed) {
    [$p, $y] = $emApi($r); if ($y !== 'duzenle') return $emApiRed($p);
    [$ok, $msg] = _emAyarKaydet($p->sube_id, $r->all());
    return ['ok' => $ok ? 1 : 0, ($ok ? 'mesaj' : 'hata') => $msg, 'ayar' => _emAyar($p->sube_id)];
});
// Bas: paket | tarti | raf → yazıcı komutu (ZPL/TSPL) + yapılandırılmış etiketler (fiş yazıcısı yedeği için ad/barkod)
Route::post('/api/etiket-merkezi/bas', function (Request $r) use ($emApi, $emApiRed) {
    [$p, $y] = $emApi($r); if (!$y) return $emApiRed($p);
    _emEnsure(); $sid = $p->sube_id; $ayar = _emAyar($sid);
    if (in_array($r->input('dil'), ['zpl', 'tspl'], true)) $ayar['dil'] = $r->input('dil');
    $tur = (string) $r->input('tur', 'paket');
    if ($tur === 'raf') {
        $ids = is_array($r->input('ids')) ? $r->input('ids') : explode(',', (string) $r->input('ids', ''));
        $ids = array_filter(array_map('intval', $ids));
        $liste = _emListe($sid)->whereIn('id', $ids)->values();
        if ($liste->isEmpty()) return ['ok' => 0, 'hata' => 'Raf kartı seçilmedi.'];
        $bicim = isset(_emRafBicimler()[$r->input('bicim', '')]) ? $r->input('bicim') : $ayar['raf_bicim'];
        $b = _emRafBicimler()[$bicim];
        if ($b[3]) $b = _emRafBicimler()['rulo_80x38']; // A4 yaprak uygulamadan değil, web'den basılır
        $kartlar = $liste->map(fn ($u) => _emRafEtiket($sid, $u))->all();
        _emRafBasildi($sid, $ids);
        return ['ok' => 1, 'dil' => $ayar['dil'], 'ip' => $ayar['ip'], 'port' => $ayar['port'],
            'komut' => in_array($ayar['dil'], ['zpl', 'tspl'], true) ? _emKomut($ayar, $b[1], $b[2], array_map(fn ($k) => ['satirlar' => _emRafSatirlar($k), 'barkod' => $k['barkod'], 'adet' => max(1, (int) $r->input('adet', 1))], $kartlar)) : null,
            'etiketler' => array_map(fn ($k) => ['ad' => $k['ad'] . ' ' . $k['fiyat'] . ' TL', 'barkod' => $k['barkod'] ?: 'RAF', 'adet' => max(1, (int) $r->input('adet', 1))], $kartlar),
            'mesaj' => count($kartlar) . ' raf kartı'];
    }
    [$ok, $msg, $id] = _emBasimAc($sid, $p, $r->all());
    if (!$ok) return ['ok' => 0, 'hata' => $msg];
    $b = DB::table('etiket_basimlar')->where('id', $id)->first();
    $e = _emBasimEtiket($sid, $b);
    $boy = _emUrunBoyutlar()[$b->boyut] ?? _emUrunBoyutlar()['58x40'];
    return ['ok' => 1, 'dil' => $ayar['dil'], 'ip' => $ayar['ip'], 'port' => $ayar['port'], 'basim_id' => $id, 'parti' => $b->parti_no, 'barkod' => $b->barkod,
        'komut' => in_array($ayar['dil'], ['zpl', 'tspl'], true) ? _emKomut($ayar, $boy[1], $boy[2], [['satirlar' => _emPaketSatirlar($e), 'barkod' => $e['barkod'], 'adet' => $e['adet']]]) : null,
        'etiketler' => [['ad' => $e['ad'] . ($b->tur === 'tarti' ? ' ' . $e['net'] . ' ' . $e['fiyat'] : ($e['skt'] ? ' SKT ' . $e['skt'] : '')), 'barkod' => $e['barkod'] ?: $b->parti_no, 'adet' => $e['adet']]],
        'etiket' => $e, 'mesaj' => $msg];
});
// Kasada barkod okut → adisyona ekle (terazi etiketi dahil). Satış ekranı /api/barkod/coz 'tarti' dönünce bunu çağırır.
Route::post('/api/etiket-merkezi/kasa-ekle', function (Request $r) {
    $p = _apiPersonel($r); if (!$p) return response()->json(['ok' => 0, 'hata' => 'Oturum gerekli'], 401);
    if (function_exists('_restoYetkiVar') && !_restoYetkiVar($p, 'adisyon_ac')) return ['ok' => 0, 'hata' => 'Sipariş ekleme yetkiniz yok.'];
    $a = DB::table('adisyonlar')->where('id', (int) $r->input('adisyon_id'))->where('sube_id', $p->sube_id)->first();
    if (!$a || $a->durum !== 'acik') return ['ok' => 0, 'hata' => 'Adisyon açık değil.'];
    $s = _emKasaBarkod($p->sube_id, (string) $r->input('kod'));
    if (!$s['ok']) return $s;
    _emKalemEkle($a, $s, $p->id, 'gonderildi');
    $t = _adisyonToplamGuncelle($a->id);
    if ($a->masa_id) DB::table('masalar')->where('id', $a->masa_id)->update(['durum' => 'dolu']);
    return ['ok' => 1, 'mesaj' => $s['ad_kalem'] . ' eklendi.', 'ad' => $s['ad_kalem'], 'tutar' => $s['tutar']] + (is_array($t) ? $t : []);
});

<?php

/**
 * ============================================================================
 *  BİLDİRİM MERKEZİ — departman sorumluları için kapsamlı, kişiye özel bildirim
 *  - Katalog: her bildirim tipi hangi departmana ait, kime gider (sorumlu / tüm ekip), kritik mi
 *  - Kutu: bildirim_kutusu (panel zili + /api/bildirim/kutu); aynı olay tekrar tekrar düşmez
 *  - Motor: brifing uyarıları (sorumlunun kendi brifingi) + anlık olay kontrolleri; panel açıkken
 *    birkaç dakikada bir arka planda, ayrıca cron ucu /api/bildirim/motor?key=SANTRAL_SECRET
 *  - Telefon: yeni kayıt FCM'e de gider (Firebase kimliği varsa)
 *  Tamamen ADDITIVE: mevcut push akışları aynen çalışır, ek olarak kutuya da yazılır.
 * ============================================================================
 */

use Illuminate\Http\Request;

if (!function_exists('_bmKatalog')) {
    // tip => [etiket, departman kodları ('*' = her departmanın sorumlusu, 'herkes' = kişisel), kim (s=sorumlu, u=sorumlu+ekip), kritik(kapatılamaz)]
    function _bmKatalog()
    {
        static $k = null;
        if ($k !== null) return $k;
        $mutfakAile = ['mutfak', 'soguk_mutfak', 'pastane', 'kasap'];
        return $k = [
            // ---- Ekip yönetimi (her departman sorumlusu) ----
            'ekip_gelmedi'        => ['Ekibimden biri vardiyasına gelmedi', ['*'], 's', 0],
            'ekip_gec_kaldi'      => ['Ekibimden biri işe geç kaldı', ['*'], 's', 0],
            'ekip_erken_cikti'    => ['Ekibimden biri mesaiyi erken bitirdi', ['*'], 's', 0],
            'ekip_izin_talebi'    => ['Ekibimden izin talebi geldi', ['*', 'ik'], 's', 0],
            'ekip_avans_talebi'   => ['Ekibimden avans talebi geldi', ['*', 'ik'], 's', 0],
            'ekip_talep_onay'     => ['Ekibimden satın alma talebi onayımı bekliyor', ['*'], 's', 0],
            'yarin_ekip_eksik'    => ['Yarın ekibimde izinli / vardiyası olmayan kişi var', ['*'], 's', 0],
            'ekip_belge_doluyor'  => ['Ekibimden birinin sağlık/hijyen belgesi doluyor', ['*', 'ik', 'kalite'], 's', 0],
            'gorev_gecikti'       => ['Departman görevinin süresi geçti', ['*'], 's', 0],
            // ---- Kişisel (herkes) ----
            'gorev_atandi'        => ['Bana görev atandı', ['herkes'], 'u', 0],
            'talep_sonuc'         => ['Talebim onaylandı / reddedildi / teslim alındı', ['herkes'], 'u', 0],
            // ---- Mutfak ailesi ----
            'kds_gecikme'         => ['KDS: sipariş 15 dakikayı geçti', array_merge($mutfakAile, ['bar']), 'u', 0],
            'kds_bekleyen'        => ['Hazırlanmayı bekleyen ürün birikti', array_merge($mutfakAile, ['bar']), 's', 0],
            'stok_kritik'         => ['Malzeme kritik seviyede', array_merge($mutfakAile, ['bar', 'satinalma', 'depo', 'mal_kabul', 'kalite', 'operasyon', 'ust_yonetim']), 's', 0],
            'uretim_riski'        => ['Üretim riski (malzeme bitti / bitiyor)', ['mutfak', 'satinalma'], 's', 0],
            'skt_gecti'           => ['SKT\'si geçmiş parti', array_merge($mutfakAile, ['kalite', 'depo']), 's', 0],
            'skt_yaklasan'        => ['SKT\'si 7 gün içinde dolacak parti', array_merge($mutfakAile, ['kalite', 'depo', 'satinalma']), 's', 0],
            'kuver'               => ['Bugün beklenen kuver / rezervasyon yoğunluğu', ['mutfak', 'karsilama', 'servis'], 's', 0],
            'rez_grup'            => ['Kalabalık grup / ön siparişli rezervasyon geldi', ['mutfak', 'karsilama', 'satis_etkinlik', 'servis'], 's', 0],
            'urun_tukendi'        => ['Menüde ürün tükendi (86)', ['mutfak', 'servis', 'bar', 'kasa'], 's', 0],
            'iptal_iade'          => ['İptal / iade yapıldı', ['mutfak', 'servis', 'bar', 'kasa'], 's', 0],
            'mal_geldi'           => ['Siparişin geldi (mal kabul tamamlandı)', ['mutfak', 'satinalma', 'depo', 'mal_kabul'], 's', 0],
            'kontrol_defteri'     => ['Sabah kontrol defteri doldurulmadı', ['mutfak'], 's', 0],
            // ---- Kalite / HACCP ----
            'sicaklik_limit'      => ['Sıcaklık LİMİT DIŞI (acil)', ['kalite', 'mutfak'], 's', 1],
            'sicaklik_olcum'      => ['Sıcaklık ölçümü yapılmadı', ['kalite', 'mutfak'], 's', 0],
            'hijyen_kontrol'      => ['Hijyen / temizlik kontrolü bekliyor', ['kalite', 'temizlik', 'bulasik'], 's', 0],
            'dof_acik'            => ['Açık uygunsuzluk (DÖF)', ['kalite'], 's', 0],
            'numune'              => ['Bugün şahit numune alınmadı', ['kalite', 'mutfak'], 's', 0],
            'alerjen_eksik'       => ['Alerjeni tanımlanmamış ürün', ['kalite', 'mutfak'], 's', 0],
            'denetim_yaklasan'    => ['Denetim / ilaçlama / kalibrasyon yaklaşıyor', ['kalite', 'teknik'], 's', 0],
            // ---- Bulaşıkhane / görev ----
            'gorev_acik'          => ['Açık görev var', ['bulasik', 'temizlik', 'teknik', 'guvenlik'], 's', 0],
            // ---- Servis / Karşılama ----
            'garson_cagri'        => ['Garson / masa çağrısı', ['servis'], 'u', 0],
            'cagri_yanitsiz'      => ['Masa çağrısı 3 dakikadır yanıtsız', ['servis'], 's', 0],
            'masa_uzun'           => ['Masa 2,5 saatten uzun süredir açık', ['servis', 'kasa'], 's', 0],
            'iskonto_ikram'       => ['Büyük iskonto / ikram yapıldı', ['servis', 'kasa', 'bar'], 's', 0],
            'memnuniyet_dusuk'    => ['Düşük memnuniyet puanı geldi', ['servis', 'crm', 'pazarlama'], 's', 0],
            'yeni_rezervasyon'    => ['Yeni rezervasyon', ['karsilama', 'satis_etkinlik', 'servis'], 'u', 0],
            'rez_gelmedi'         => ['Rezervasyon 15 dakikayı geçti, misafir gelmedi', ['karsilama', 'satis_etkinlik'], 'u', 0],
            'bekleme_uzun'        => ['Bekleme listesinde 20 dakikayı geçen misafir', ['karsilama'], 'u', 0],
            'vip_geliyor'         => ['VIP misafir bugün geliyor', ['karsilama', 'crm', 'servis'], 's', 0],
            'rez_onay'            => ['Rezervasyon onay bekliyor', ['karsilama', 'satis_etkinlik'], 's', 0],
            'santral_rez'         => ['Santraldan rezervasyon geldi / kaçan çağrı', ['karsilama', 'satis_etkinlik'], 's', 0],
            'rez_yarin'           => ['Yarınki rezervasyonlara hatırlatma', ['karsilama', 'satis_etkinlik'], 's', 0],
            'noshow'              => ['No-show oranı yükseldi', ['satis_etkinlik', 'karsilama'], 's', 0],
            'etkinlik'            => ['Grup / etkinlik takibi', ['satis_etkinlik'], 's', 0],
            // ---- Bar ----
            'happy_hour'          => ['Happy Hour başlıyor / bitiyor', ['bar'], 'u', 0],
            // ---- Kasa / Paket ----
            'acik_adisyon'        => ['Dünden açık kalan adisyon', ['kasa', 'finans', 'servis', 'operasyon'], 's', 0],
            'yeni_paket'          => ['Yeni paket / QR / WhatsApp siparişi', ['kasa', 'paket_kurye'], 'u', 0],
            'paket_bekliyor'      => ['Paket hazır, 10 dakikadır kurye almadı', ['paket_kurye', 'kasa'], 'u', 0],
            'kurye_gecikti'       => ['Kurye 45 dakikayı geçti', ['paket_kurye', 'kasa'], 's', 0],
            'tahsil_bekliyor'     => ['Teslim edildi ama tahsil edilmedi', ['kasa', 'paket_kurye', 'finans'], 's', 0],
            'kasa_acik_kaldi'     => ['Kasa dünden açık (Z / gün sonu alınmadı)', ['kasa', 'finans'], 's', 0],
            'kasa_fark'           => ['Kasa sayımında fark çıktı', ['kasa', 'finans'], 's', 0],
            // ---- Satın Alma / Depo ----
            'talep_yeni'          => ['Yeni satın alma talebi düştü', ['satinalma'], 's', 0],
            'talep_onayda'        => ['Talep patron onayında', ['satinalma'], 's', 0],
            'teklif_geldi'        => ['Tedarikçiden teklif geldi', ['satinalma'], 's', 0],
            'siparis_onay'        => ['Sipariş patrondan onaylandı / reddedildi', ['satinalma'], 's', 0],
            'siparis_gecikti'     => ['Tedarikçi siparişi gecikti', ['satinalma', 'depo', 'mal_kabul'], 's', 0],
            'teslimat_bugun'      => ['Bugün beklenen teslimatlar', ['satinalma', 'depo', 'mal_kabul', 'mutfak'], 's', 0],
            'tedarikci_yanit'     => ['Tedarikçi siparişi onayladı / değişiklik istedi', ['satinalma'], 's', 0],
            'butce'               => ['Satın alma bütçesi %80 / %100\'e ulaştı', ['satinalma', 'finans'], 's', 0],
            'fiyat_anlasma'       => ['Fiyat anlaşmasının süresi doluyor', ['satinalma'], 's', 0],
            'fiyat_zam'           => ['Malzemede ani fiyat artışı', ['satinalma', 'mutfak'], 's', 0],
            'sayim_fark'          => ['Sayımda yüksek fark çıktı', ['depo', 'satinalma', 'mutfak', 'finans'], 's', 0],
            // ---- İK ----
            'ozluk_eksik'         => ['Özlük / maaş bilgisi eksik personel', ['ik'], 's', 0],
            'izin_bekliyor'       => ['Bekleyen izin / avans talepleri', ['ik'], 's', 0],
            // ---- Finans ----
            'beyanname'           => ['Beyanname / vergi tarihi yaklaşıyor', ['finans'], 's', 0],
            'tekrarlayan_gider'   => ['Tekrarlayan gider bu ay işlenecek', ['finans'], 's', 0],
            'muhasebe_aktarim'    => ['Mahsup fişi muhasebeye aktarılmadı', ['finans'], 's', 0],
            'cek_senet'           => ['Çek / senet vadesi geliyor', ['finans'], 's', 0],
            // ---- Pazarlama / CRM ----
            'uyuyan_musteri'      => ['Uyuyan müşteri arttı', ['pazarlama', 'crm'], 's', 0],
            'icerik_takvimi'      => ['Haftalık içerik takvimi hazırlanmadı', ['pazarlama'], 's', 0],
            'sikayet_acik'        => ['Açık şikayet çözüm bekliyor', ['crm', 'servis'], 's', 0],
            'musteri_kayip'       => ['Müşteri kaybolmak üzere', ['crm', 'pazarlama'], 's', 0],
            'dogum_gunu'          => ['Bu hafta doğum günü olan müşteriler', ['crm', 'pazarlama'], 's', 0],
            // ---- IT / Teknik ----
            'cihaz_offline'       => ['Cihaz / yazıcı çevrimdışı', ['it'], 's', 0],
            'wa_hat_koptu'        => ['WhatsApp hattı koptu / çıkış yaptı (hat sahibi + yönetim)', ['it'], 's', 1],
            'mali_fis_hata'       => ['Mali fiş / ÖKC hatası', ['it', 'kasa'], 's', 1],
            'surum_eski'          => ['Eski sürüm cihaz', ['it'], 's', 0],
            'ariza_acik'          => ['Açık arıza / destek kaydı', ['it', 'teknik'], 's', 0],
            'it_guvenlik'         => ['Yedek / KVKK / lisans / pasif hesap uyarısı', ['it'], 's', 0],
        ];
    }
}

if (!function_exists('_bmEnsure')) {
    function _bmEnsure()
    {
        if (cache()->get('bm_ensure_v1')) return;
        try {
            if (!Schema::hasTable('bildirim_kutusu')) {
                Schema::create('bildirim_kutusu', function ($t) {
                    $t->id();
                    $t->unsignedBigInteger('sube_id')->index();
                    $t->unsignedBigInteger('personel_id')->index();
                    $t->string('tip', 40);
                    $t->string('baslik', 190);
                    $t->string('govde', 500)->nullable();
                    $t->string('link', 190)->nullable();
                    $t->tinyInteger('oncelik')->default(0);
                    $t->string('anahtar', 190)->nullable();
                    $t->integer('deger')->nullable();
                    $t->timestamp('okundu_at')->nullable();
                    $t->timestamp('created_at')->nullable();
                    $t->index(['personel_id', 'anahtar']);
                });
            }
            cache()->put('bm_ensure_v1', 1, now()->addHours(12));
        } catch (\Throwable $e) {}
    }
}

if (!function_exists('_bmKisiDepler')) {
    // Kişinin departmanları: [kod => sorumlu mu]. Rol yedekleri: satın almacı = satın alma sorumlusu, kasiyer = kasa ekibi.
    function _bmKisiDepler($p)
    {
        static $bellek = [];
        if (!$p) return [];
        $id = (int) ($p->id ?? 0);
        if ($id && array_key_exists($id, $bellek)) return $bellek[$id];
        $out = [];
        try {
            if (!empty($p->departman_id)) { $kod = _personelDepartmanKodu($p); if ($kod) $out[$kod] = !empty($p->departman_sorumlu); }
            if ($id && Schema::hasTable('personel_departman')) {
                foreach (DB::table('personel_departman as pd')->join('departmanlar as d', 'd.id', '=', 'pd.departman_id')->where('pd.personel_id', $id)->get(['d.kod', 'pd.sorumlu']) as $r)
                    if ($r->kod) $out[$r->kod] = ($out[$r->kod] ?? false) || !empty($r->sorumlu);
            }
        } catch (\Throwable $e) {}
        if (in_array($p->rol ?? '', ['satinalmaci', 'satinalma'], true)) $out['satinalma'] = true;
        if (function_exists('_kasiyerMi') && _kasiyerMi($p) && !isset($out['kasa'])) $out['kasa'] = false;
        return $id ? ($bellek[$id] = $out) : $out;
    }
}

if (!function_exists('_bmTipAlir')) {
    // Kişi bu katalog tipini alır mı (ve ayarlarda görür mü)
    function _bmTipAlir($p, $tip)
    {
        $info = _bmKatalog()[$tip] ?? null;
        if (!$info || !$p) return false;
        [, $depler, $kim] = $info;
        if (in_array('herkes', $depler, true)) return true;
        $kd = _bmKisiDepler($p);
        $sorumlu = in_array(true, $kd, true);
        if (in_array('*', $depler, true) && $sorumlu) return true;
        foreach ($kd as $kod => $s) if (in_array($kod, $depler, true) && ($s || $kim === 'u')) return true;
        return false;
    }
}

if (!function_exists('_bmAlicilar')) {
    // Tipi alması gereken aktif personel (şube). $depFiltre verilirse yalnız o departman(lar)ın kişileri.
    function _bmAlicilar($sid, $tip, $depFiltre = null)
    {
        $out = [];
        try {
            foreach (DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->get() as $p) {
                if (in_array($p->rol ?? '', ['sahip', 'mudur'], true) && empty($p->departman_id)) continue; // patron kendi özetlerini alır
                if (!_bmTipAlir($p, $tip)) continue;
                if ($depFiltre !== null && !array_intersect(array_keys(_bmKisiDepler($p)), (array) $depFiltre)) continue;
                $out[] = $p;
            }
        } catch (\Throwable $e) {}
        return $out;
    }
}

if (!function_exists('_bmEkle')) {
    // Kutuya yaz (tekrar koruması: aynı anahtar bugün zaten düştüyse ve değer artmadıysa atla). Yeni kayıtlar telefona da gider.
    // $kisiler: personel id listesi. Dönüş: yazılan kişi sayısı.
    function _bmEkle($sid, $kisiler, $tip, $baslik, $govde = null, $link = null, $oncelik = 0, $anahtar = null, $deger = null, $push = true)
    {
        _bmEnsure();
        $kisiler = array_values(array_unique(array_filter(array_map('intval', (array) $kisiler))));
        if (!$kisiler) return 0;
        $kritik = !empty(_bmKatalog()[$tip][3]);
        $yazilan = [];
        foreach ($kisiler as $pid) {
            try {
                if (!$kritik && Schema::hasTable('bildirim_tercihleri') && DB::table('bildirim_tercihleri')->where('personel_id', $pid)->where('tip', $tip)->value('acik') === 0) continue; // tercih her seferinde taze
                if ($anahtar !== null) {
                    $son = DB::table('bildirim_kutusu')->where('personel_id', $pid)->where('anahtar', $anahtar)->orderByDesc('id')->first(['created_at', 'deger']);
                    if ($son && substr((string) $son->created_at, 0, 10) === date('Y-m-d') && ($deger === null || (int) $son->deger >= (int) $deger)) continue;
                }
                DB::table('bildirim_kutusu')->insert(['sube_id' => (int) $sid, 'personel_id' => $pid, 'tip' => mb_substr($tip, 0, 40), 'baslik' => mb_substr((string) $baslik, 0, 190),
                    'govde' => $govde !== null ? mb_substr((string) $govde, 0, 500) : null, 'link' => $link ? mb_substr($link, 0, 190) : null, 'oncelik' => (int) $oncelik,
                    'anahtar' => $anahtar !== null ? mb_substr($anahtar, 0, 190) : null, 'deger' => $deger !== null ? (int) $deger : null, 'created_at' => now()]);
                $yazilan[] = $pid;
            } catch (\Throwable $e) {}
        }
        if ($push && $yazilan && function_exists('_fcmPersoneleGonder')) {
            try { _fcmPersoneleGonder($sid, $yazilan, $baslik, (string) ($govde ?? ''), ['tur' => $tip, 'link' => (string) $link, 'kaynak' => 'bm']); } catch (\Throwable $e) {}
        }
        return count($yazilan);
    }
}

if (!function_exists('_bmTipeGonder')) {
    // Katalog tipine göre alıcıları bulup kutuya yaz
    function _bmTipeGonder($sid, $tip, $baslik, $govde = null, $link = null, $oncelik = 0, $anahtar = null, $deger = null, $depFiltre = null)
    {
        $ids = array_map(fn ($p) => (int) $p->id, _bmAlicilar($sid, $tip, $depFiltre));
        return _bmEkle($sid, $ids, $tip, $baslik, $govde, $link, $oncelik, $anahtar, $deger);
    }
}

if (!function_exists('_bmFcmKoprusu')) {
    // Mevcut push akışlarını (talep, teklif, kalite, IT, ajanda...) kutuya da yaz. _fcmSubeyeGonder / _fcmPersoneleGonder çağırır.
    function _bmFcmKoprusu($sid, $ids, $baslik, $govde, $data, $tip = null)
    {
        if (($data['kaynak'] ?? '') === 'bm') return; // kendi gönderimimiz
        $tur = (string) ($data['tur'] ?? '');
        $linkler = ['satinalma_talep' => '/satinalma-talep', 'talep_onay' => '/talep-onaylarim', 'talep_red' => '/reddedilen-talepler', 'rfq_teklif' => '/gelen-teklifler',
            'siparis_onay' => '/satinalma-siparis', 'teslim' => '/mal-kabul', 'teslim_gec' => '/satinalma-siparis', 'geciken_siparis' => '/satinalma-siparis', 'kalite_sicaklik' => '/kalite',
            'kalite_skt' => '/parti-takip', 'kalite_denetim' => '/kalite', 'kalite_belge' => '/kalite', 'it_okc' => '/it-destek', 'it_cihaz' => '/it-destek', 'it_surum' => '/it-destek',
            'garson_cagri' => '/garson-cagrilari', 'rezervasyon' => '/rezervasyon', 'noshow' => '/rezervasyon', 'memnuniyet' => '/memnuniyet', 'tahsilat' => '/kasa', 'acik_adisyon' => '/pos',
            'patron_ai_brief' => '/patron-ai', 'gelmeyen_ik' => '/puantaj', 'beyanname' => '/vergi-takvimi', 'mali_ozet' => '/raporlar', 'ajanda' => '/ajanda', 'cek_senet' => '/cek-senet'];
        // Kişisel talep sonuçları ve sorumlu onay kutusu katalog tiplerine bağlanır (ayarlardan açılıp kapanır)
        if ($tip && isset(_bmTipTakma()[$tip])) $tip = _bmTipTakma()[$tip]; // eski tip adı → katalog
        $katTip = ['talep_red' => 'talep_sonuc', 'talep_onay' => 'ekip_talep_onay', 'rfq_teklif' => 'teklif_geldi', 'siparis_onay' => 'siparis_onay', 'garson_cagri' => 'garson_cagri'][$tur] ?? null;
        if ($tur === 'satinalma_talep' && str_contains((string) $baslik, 'Talebiniz')) $katTip = 'talep_sonuc';
        $oncelik = in_array($tur, ['kalite_sicaklik', 'it_okc'], true) ? 2 : 0;
        $anahtar = 'fcm:' . $tur . ':' . md5($baslik . '|' . $govde . '|' . date('Y-m-d H:i'));
        _bmEkle($sid, $ids, $katTip ?: ($tip ?: ($tur ?: 'genel')), $baslik, $govde, $linkler[$tur] ?? null, $oncelik, $anahtar, null, false);
    }
}

// ============================ MOTOR ============================
if (!function_exists('_bmBrifTip')) {
    // Brifing iş satırı → katalog tipi (metne göre). null = bildirime çevrilmez.
    function _bmBrifTip($kod, $is)
    {
        $b = strip_tags((string) ($is['baslik'] ?? ''));
        $grup = (string) ($is['grup'] ?? '');
        if (in_array($grup, ['KURULUM', 'MÜŞTERİ'], true)) return null;
        $harita = [
            '/belgesi doluyor/u' => 'ekip_belge_doluyor', '/LİMİT DIŞI/u' => 'sicaklik_limit', '/sıcaklık ölçümü bekliyor/u' => 'sicaklik_olcum', '/kritik seviyede/u' => 'stok_kritik',
            '/sipariş gecikti/u' => 'siparis_gecikti', '/teklif/u' => 'teklif_geldi', '/yeni talebi/u' => 'talep_yeni', '/talep onayda/u' => 'talep_onayda',
            '/talep onay(ını)? bekliyor/u' => 'ekip_talep_onay', "/SKT'si geçmiş|SKT geçmiş/u" => 'skt_gecti', '/gün içinde doluyor/u' => 'skt_yaklasan',
            '/hazırlanmayı bekliyor/u' => 'kds_bekleyen', '/kuver bekleniyor/u' => 'kuver', '/adisyon dünden açık/u' => 'acik_adisyon',
            '/tekrarlayan gider/u' => 'tekrarlayan_gider', '/mahsup/u' => 'muhasebe_aktarim', '/Beyanname|Vergi|Muhtasar|KDV/u' => 'beyanname',
            '/özlük|maaşı girilmemiş/u' => 'ozluk_eksik', '/izin|avans/u' => 'izin_bekliyor', '/düşük puanlı/u' => 'memnuniyet_dusuk',
            '/uyuyan/u' => 'uyuyan_musteri', '/içerik takvimi/u' => 'icerik_takvimi', '/şikayet/u' => 'sikayet_acik', '/kaybolmak/u' => 'musteri_kayip',
            '/VIP/u' => 'vip_geliyor', '/doğum günü/u' => 'dogum_gunu', '/santraldan|kaçan çağrı/u' => 'santral_rez', '/Yarın .*rezervasyon/u' => 'rez_yarin',
            '/rezervasyon onay bekliyor/u' => 'rez_onay', '/rezervasyon bugün/u' => 'kuver', '/No-show/u' => 'noshow', '/etkinlik/u' => 'etkinlik',
            '/uygunsuzluk/u' => 'dof_acik', '/hijyen|temizlik kontrol/u' => 'hijyen_kontrol', '/şahit numune/u' => 'numune',
            '/alerjen/u' => 'alerjen_eksik', '/^Yaklaşan/u' => 'denetim_yaklasan', '/çevrimdışı/u' => 'cihaz_offline', '/mali fiş/ui' => 'mali_fis_hata',
            '/eski sürüm/u' => 'surum_eski', '/arıza/u' => 'ariza_acik', '/pasif|KVKK|Yedek|Lisans|SSL/u' => 'it_guvenlik',
            '/görevin son tarihi/u' => 'gorev_gecikti', '/açık görev/u' => 'gorev_acik', '/giriş yapmadı/u' => 'ekip_gelmedi',
        ];
        foreach ($harita as $re => $tip) if (preg_match($re, $b)) return $tip;
        return null;
    }
}

if (!function_exists('_bmBrifingTara')) {
    // Her sorumlu için kendi brifingindeki uyarılar → yalnız ona bildirim (brifinginde ne görüyorsa onu)
    function _bmBrifingTara($sid)
    {
        $say = 0;
        if (!function_exists('_departmanBriefVeri')) return 0;
        foreach (DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->get() as $p) {
            foreach (_bmKisiDepler($p) as $kod => $sorumlu) {
                if (!$sorumlu) continue;
                try { $v = _departmanBriefVeri((int) $sid, $p, $kod); } catch (\Throwable $e) { continue; }
                foreach ($v['isler'] ?? [] as $is) {
                    $tip = _bmBrifTip($kod, $is);
                    if (!$tip || !_bmTipAlir($p, $tip)) continue;
                    $b = trim(strip_tags((string) ($is['baslik'] ?? '')));
                    $deger = preg_match('/\d+/', $b, $m) ? (int) $m[0] : null;
                    $oncelik = $tip === 'sicaklik_limit' || $tip === 'mali_fis_hata' ? 2 : (in_array($tip, ['skt_gecti', 'stok_kritik', 'siparis_gecikti', 'ekip_talep_onay'], true) ? 1 : 0);
                    $say += _bmEkle($sid, [(int) $p->id], $tip, $b, trim(strip_tags((string) ($is['aciklama'] ?? ''))) ?: null, $is['aksiyon']['url'] ?? null, $oncelik,
                        'brif:' . $kod . ':' . $tip . ':' . preg_replace('/\d+/', '#', $b), $deger);
                }
            }
        }
        return $say;
    }
}

if (!function_exists('_bmOlayTara')) {
    // Anlık olay kontrolleri (brifingde olmayan, dakikalar içinde haber verilmesi gerekenler)
    function _bmOlayTara($sid)
    {
        $sid = (int) $sid; $say = 0; $bugun = date('Y-m-d'); $simdi = now();
        $t = fn ($n) => Schema::hasTable($n);
        $c = fn ($n, $k) => Schema::hasColumn($n, $k);

        // --- KDS gecikmesi: mutfağa gönderilmiş, 15 dk'dır hazır olmayan kalem (istasyona göre)
        try {
            $gec = DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'a.id', '=', 'k.adisyon_id')->where('a.sube_id', $sid)->where('a.durum', 'acik')
                ->whereIn('k.durum', ['gonderildi', 'hazirlaniyor'])->whereNotNull('k.gonderim_zamani')->where('k.gonderim_zamani', '<', $simdi->copy()->subMinutes(15))
                ->get(['k.id', 'k.urun_id', 'k.urun_adi', 'k.adet', 'a.masa_id']);
            if ($gec->count()) {
                foreach (_bmAlicilar($sid, 'kds_gecikme') as $p) {
                    $kapsam = function_exists('_mutfakIstasyonUrunleri') ? _mutfakIstasyonUrunleri($p) : null;
                    $liste = $kapsam === null ? $gec : $gec->filter(fn ($k) => in_array((int) $k->urun_id, $kapsam, true));
                    if (!$liste->count()) continue;
                    $ornek = $liste->take(3)->map(fn ($k) => $k->adet . '× ' . $k->urun_adi)->implode(', ');
                    $say += _bmEkle($sid, [(int) $p->id], 'kds_gecikme', '⏱️ ' . $liste->count() . ' ürün 15 dakikayı geçti', $ornek, '/mutfak', 1, 'kds:' . $bugun . ':' . $liste->max('id'), $liste->count());
                }
            }
        } catch (\Throwable $e) {}

        // --- Masa çağrısı 3 dk'dır yanıtsız
        try { if ($t('masa_cagrilari')) {
            $n = (int) DB::table('masa_cagrilari')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('created_at', '<', $simdi->copy()->subMinutes(3))->where('created_at', '>', $simdi->copy()->subHours(2))->count();
            if ($n) $say += _bmTipeGonder($sid, 'cagri_yanitsiz', '🔔 ' . $n . ' masa çağrısı 3 dakikadır yanıtsız', 'Garson çağrıları ekranından bak.', '/garson-cagrilari', 1, 'cagri:' . $bugun . ':' . DB::table('masa_cagrilari')->where('sube_id', $sid)->where('durum', 'bekliyor')->max('id'), $n);
        } } catch (\Throwable $e) {}

        // --- Masa 2,5 saatten uzun açık
        try {
            $uzun = DB::table('adisyonlar')->where('sube_id', $sid)->where('durum', 'acik')->whereNotNull('masa_id')->where('acilis', '<', $simdi->copy()->subMinutes(150))->where('acilis', '>', $simdi->copy()->subHours(12))->count();
            if ($uzun) $say += _bmTipeGonder($sid, 'masa_uzun', '🕑 ' . $uzun . ' masa 2,5 saattir açık', 'Hesap istenmiş olabilir, masayı kontrol et.', '/pos', 0, 'masauzun:' . $bugun, $uzun);
        } catch (\Throwable $e) {}

        // --- Büyük iskonto / ikram (bugün, ara toplamın %20'si ve üstü)
        try {
            foreach (DB::table('adisyonlar')->where('sube_id', $sid)->whereDate('acilis', $bugun)->where('ara_toplam', '>', 0)->whereRaw('(COALESCE(indirim,0) + COALESCE(ikram,0)) >= ara_toplam * 0.2')->get(['id', 'masa_id', 'indirim', 'ikram', 'ara_toplam']) as $a) {
                $tut = (float) $a->indirim + (float) $a->ikram;
                $say += _bmTipeGonder($sid, 'iskonto_ikram', '🎁 Büyük iskonto/ikram: ' . number_format($tut, 0, ',', '.') . ' ₺', 'Adisyon #' . $a->id . ' · ara toplam ' . number_format((float) $a->ara_toplam, 0, ',', '.') . ' ₺', '/pos', 0, 'iskonto:' . $a->id);
            }
        } catch (\Throwable $e) {}

        // --- İptal / iade (bugün; sayı arttıkça)
        try {
            $ip = (int) DB::table('adisyon_kalemleri as k')->join('adisyonlar as a', 'a.id', '=', 'k.adisyon_id')->where('a.sube_id', $sid)->where('k.durum', 'iptal')->whereDate('k.updated_at', $bugun)->count();
            if ($ip) $say += _bmTipeGonder($sid, 'iptal_iade', '↩️ Bugün ' . $ip . ' kalem iptal/iade edildi', 'Sebepleri kontrol et.', '/raporlar', 0, 'iptal:' . $bugun, $ip);
        } catch (\Throwable $e) {}

        // --- Ürün tükendi (86)
        try { if ($c('urunler', 'tukendi')) {
            $tk = DB::table('urunler')->where('sube_id', $sid)->where('tukendi', 1)->where('aktif', 1)->pluck('ad');
            if ($tk->count()) $say += _bmTipeGonder($sid, 'urun_tukendi', '🚫 ' . $tk->count() . ' ürün tükendi', $tk->take(5)->implode(', '), '/menu', 0, 'tukendi:' . $bugun . ':' . md5($tk->implode('|')), $tk->count());
        } } catch (\Throwable $e) {}

        // --- Rezervasyonlar: yeni, kalabalık grup, 15 dk gelmedi, VIP, bugünkü kuver
        try { if ($t('rezervasyonlar')) {
            foreach (DB::table('rezervasyonlar')->where('sube_id', $sid)->where('created_at', '>', $simdi->copy()->subMinutes(30))->get() as $r) {
                $ne = $r->ad . ' · ' . $r->kisi . ' kişi · ' . \Carbon\Carbon::parse($r->tarih)->format('d.m') . ' ' . substr((string) $r->saat, 0, 5);
                $say += _bmTipeGonder($sid, 'yeni_rezervasyon', '📅 Yeni rezervasyon', $ne, '/rezervasyon?tarih=' . $r->tarih, 0, 'rez:' . $r->id);
                if ((int) $r->kisi >= 8) $say += _bmTipeGonder($sid, 'rez_grup', '👥 Kalabalık grup: ' . $r->kisi . ' kişi', $ne, '/rezervasyon?tarih=' . $r->tarih, 1, 'rezgrup:' . $r->id);
            }
            $gelmeyen = DB::table('rezervasyonlar')->where('sube_id', $sid)->where('tarih', $bugun)->whereIn('durum', ['bekliyor', 'onaylandi'])->get()
                ->filter(fn ($r) => $r->saat && strtotime($bugun . ' ' . substr($r->saat, 0, 5)) < time() - 900 && strtotime($bugun . ' ' . substr($r->saat, 0, 5)) > time() - 7200);
            foreach ($gelmeyen as $r) $say += _bmTipeGonder($sid, 'rez_gelmedi', '⌛ ' . $r->ad . ' gelmedi (' . substr($r->saat, 0, 5) . ')', $r->kisi . ' kişi · ' . ($r->telefon ?: 'telefon yok') . ' — ara veya masayı serbest bırak', '/rezervasyon?tarih=' . $bugun, 1, 'rezgel:' . $r->id);
            if ($c('rezervasyonlar', 'etiketler')) foreach (DB::table('rezervasyonlar')->where('sube_id', $sid)->where('tarih', $bugun)->whereIn('durum', ['bekliyor', 'onaylandi'])->where('etiketler', 'like', '%vip%')->get() as $r)
                $say += _bmTipeGonder($sid, 'vip_geliyor', '⭐ VIP bugün: ' . $r->ad, substr((string) $r->saat, 0, 5) . ' · ' . $r->kisi . ' kişi', '/rezervasyon?tarih=' . $bugun, 0, 'vip:' . $r->id);
            $kuv = DB::table('rezervasyonlar')->where('sube_id', $sid)->where('tarih', $bugun)->whereIn('durum', ['bekliyor', 'onaylandi', 'geldi'])->selectRaw('COUNT(*) n, COALESCE(SUM(kisi),0) k')->first();
            if ($kuv && (int) $kuv->n > 0) $say += _bmTipeGonder($sid, 'kuver', '🍽️ Bugün ' . (int) $kuv->k . ' kuver bekleniyor', (int) $kuv->n . ' rezervasyon', '/rezervasyon', 0, 'kuver:' . $bugun, (int) $kuv->k);
        } } catch (\Throwable $e) {}

        // --- Bekleme listesi 20 dk'yı geçti
        try { if ($t('bekleme_listesi')) {
            $n = (int) DB::table('bekleme_listesi')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('created_at', '<', $simdi->copy()->subMinutes(20))->whereDate('created_at', $bugun)->count();
            if ($n) $say += _bmTipeGonder($sid, 'bekleme_uzun', '🧍 ' . $n . ' misafir 20 dakikadır bekliyor', 'Boşalan masaya yerleştir.', '/karsilama', 1, 'bekleme:' . $bugun, $n);
        } } catch (\Throwable $e) {}

        // --- Paket / QR / WhatsApp: yeni sipariş, kurye almadı, kurye gecikti, tahsil edilmedi
        try {
            foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('created_at', '>', $simdi->copy()->subMinutes(30))->where(function ($w) { $w->whereIn('kanal', ['paket', 'qr'])->orWhereNotNull('platform'); })->get(['id', 'kanal', 'platform', 'toplam']) as $a) {
                $kanal = $a->platform ? ucfirst($a->platform) : ($a->kanal === 'qr' ? 'QR' : 'Paket');
                $say += _bmTipeGonder($sid, 'yeni_paket', '🛵 Yeni ' . $kanal . ' siparişi #' . $a->id, number_format((float) $a->toplam, 0, ',', '.') . ' ₺', '/paket', 1, 'paket:' . $a->id);
            }
            if ($c('adisyonlar', 'teslimat_durumu')) {
                foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('teslimat_durumu', 'hazir')->where('updated_at', '<', $simdi->copy()->subMinutes(10))->whereDate('created_at', $bugun)->get(['id']) as $a)
                    $say += _bmTipeGonder($sid, 'paket_bekliyor', '📦 Paket #' . $a->id . ' hazır, kurye almadı', '10 dakikadır bekliyor.', '/paket', 1, 'pkbek:' . $a->id);
                foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('teslimat_durumu', 'yolda')->where('updated_at', '<', $simdi->copy()->subMinutes(45))->whereDate('created_at', $bugun)->get(['id']) as $a)
                    $say += _bmTipeGonder($sid, 'kurye_gecikti', '🛵 Paket #' . $a->id . ' 45 dakikadır yolda', 'Kurye ile iletişime geç.', '/paket', 1, 'kurye:' . $a->id);
            }
            if ($c('adisyonlar', 'tahsilat_durumu')) {
                foreach (DB::table('adisyonlar')->where('sube_id', $sid)->where('tahsilat_durumu', 'kuryede')->where('updated_at', '<', $simdi->copy()->subMinutes(30))->where('created_at', '>', $simdi->copy()->subDays(2))->get(['id', 'toplam']) as $a)
                    $say += _bmTipeGonder($sid, 'tahsil_bekliyor', '💵 Paket #' . $a->id . ' teslim edildi, tahsilat kuryede', number_format((float) $a->toplam, 0, ',', '.') . ' ₺ kasaya girmedi.', '/paket', 0, 'tahsil:' . $a->id);
            }
        } catch (\Throwable $e) {}

        // --- Kasa: dünden açık oturum, kapanışta fark
        try { if ($t('kasa_oturumlari')) {
            $acik = DB::table('kasa_oturumlari')->where('sube_id', $sid)->where('durum', 'acik')->whereDate('acilis_zamani', '<', $bugun)->count();
            if ($acik) $say += _bmTipeGonder($sid, 'kasa_acik_kaldi', '🧾 ' . $acik . ' kasa dünden açık', 'Gün sonu / Z raporu alınmadı.', '/kasa', 1, 'kasaacik:' . $bugun, $acik);
            foreach (DB::table('kasa_oturumlari')->where('sube_id', $sid)->whereNotNull('kapanis_zamani')->where('kapanis_zamani', '>', $simdi->copy()->subDay())->whereRaw('ABS(COALESCE(fark,0)) >= 50')->get(['id', 'fark']) as $o)
                $say += _bmTipeGonder($sid, 'kasa_fark', '⚖️ Kasa farkı: ' . number_format((float) $o->fark, 0, ',', '.') . ' ₺', 'Kapanış sayımı sistemle tutmadı.', '/kasa', 1, 'kasafark:' . $o->id);
        } } catch (\Throwable $e) {}

        // --- Memnuniyet: 1-2 puan anında
        try { if ($t('memnuniyet')) foreach (DB::table('memnuniyet')->where('sube_id', $sid)->where('puan', '<=', 2)->where('created_at', '>', $simdi->copy()->subHours(2))->get(['id', 'masa_ad', 'puan', 'not']) as $m)
            $say += _bmTipeGonder($sid, 'memnuniyet_dusuk', '😞 ' . ($m->masa_ad ?: 'Bir masa') . ': ' . $m->puan . '/5 puan', $m->not ?: 'Misafirle hemen ilgilen.', '/memnuniyet', 1, 'mem:' . $m->id);
        } catch (\Throwable $e) {}

        // --- Happy Hour başlıyor (15 dk kala) / bitiyor
        try { if ($t('happy_hour')) foreach (DB::table('happy_hour')->where('sube_id', $sid)->where('aktif', 1)->get() as $h) {
            if ($h->baslangic_tarih && $bugun < $h->baslangic_tarih) continue;
            if ($h->bitis_tarih && $bugun > $h->bitis_tarih) continue;
            if ($h->gunler && !in_array((string) date('N'), array_map('trim', explode(',', (string) $h->gunler)), true)) continue;
            foreach (['baslangic_saat' => 'başlıyor', 'bitis_saat' => 'bitiyor'] as $alan => $ne) {
                if (!$h->$alan) continue;
                $fark = strtotime($bugun . ' ' . substr($h->$alan, 0, 5)) - time();
                if ($fark > 0 && $fark <= 900) $say += _bmTipeGonder($sid, 'happy_hour', '🍹 ' . ($h->ad ?: 'Happy Hour') . ' ' . substr($h->$alan, 0, 5) . "'te " . $ne, '', '/happy-hour', 0, 'hh:' . $h->id . ':' . $alan . ':' . $bugun);
            }
        } } catch (\Throwable $e) {}

        // --- Satın alma: bugün beklenen teslimatlar (sabah), gelen mal, tedarikçi yanıtı, bütçe, fiyat anlaşması, sayım farkı
        try { if ($t('satinalma_siparisleri')) {
            $bek = DB::table('satinalma_siparisleri')->where('sube_id', $sid)->whereDate('beklenen_teslim', $bugun)->whereIn('durum', ['gonderildi', 'onaylandi', 'kismi', 'siparis'])->count();
            if ($bek) $say += _bmTipeGonder($sid, 'teslimat_bugun', '🚚 Bugün ' . $bek . ' teslimat bekleniyor', 'Mal kabule hazırlıklı ol.', '/mal-kabul', 0, 'teslimbugun:' . $bugun, $bek);
            if ($c('satinalma_siparisleri', 'onay_durum')) foreach (DB::table('satinalma_siparisleri')->where('sube_id', $sid)->whereIn('onay_durum', ['onaylandi', 'degisiklik'])->where('updated_at', '>', $simdi->copy()->subHours(6))->get(['id', 'siparis_no', 'onay_durum']) as $s)
                $say += _bmTipeGonder($sid, 'tedarikci_yanit', $s->onay_durum === 'onaylandi' ? '✅ Tedarikçi ' . $s->siparis_no . ' siparişini onayladı' : '✏️ Tedarikçi ' . $s->siparis_no . ' için değişiklik istedi', '', '/satinalma-siparis-detay?id=' . $s->id, $s->onay_durum === 'degisiklik' ? 1 : 0, 'tedyanit:' . $s->id . ':' . $s->onay_durum);
        } } catch (\Throwable $e) {}
        try { if ($t('mal_kabuller')) foreach (DB::table('mal_kabuller')->where('sube_id', $sid)->where('created_at', '>', $simdi->copy()->subHours(3))->get(['id']) as $mk)
            $say += _bmTipeGonder($sid, 'mal_geldi', '📥 Mal kabul yapıldı (#' . $mk->id . ')', 'Siparişin depoya girdi.', '/mal-kabul', 0, 'malkabul:' . $mk->id);
        } catch (\Throwable $e) {}
        try { if ($t('satinalma_butce') && $t('satinalma_siparisleri')) {
            $butce = (float) DB::table('satinalma_butce')->where('sube_id', $sid)->where('yil_ay', date('Y-m'))->value('tutar');
            if ($butce > 0) {
                $harc = (float) DB::table('satinalma_siparisleri')->where('sube_id', $sid)->where('durum', '!=', 'iptal')->where('tarih', '>=', date('Y-m-01'))->sum('genel_toplam');
                $oran = (int) floor($harc / $butce * 100);
                if ($oran >= 80) $say += _bmTipeGonder($sid, 'butce', ($oran >= 100 ? '🔴 Satın alma bütçesi aşıldı' : '🟠 Satın alma bütçesinin %' . $oran . "'i kullanıldı"), number_format($harc, 0, ',', '.') . ' / ' . number_format($butce, 0, ',', '.') . ' ₺', '/satinalma-butce', $oran >= 100 ? 1 : 0, 'butce:' . date('Y-m') . ':' . ($oran >= 100 ? 100 : 80));
            }
        } } catch (\Throwable $e) {}
        try { if ($t('fiyat_anlasmalari')) {
            $n = (int) DB::table('fiyat_anlasmalari')->where('sube_id', $sid)->where('aktif', 1)->whereNotNull('bitis')->whereBetween('bitis', [$bugun, date('Y-m-d', strtotime('+15 day'))])->count();
            if ($n) $say += _bmTipeGonder($sid, 'fiyat_anlasma', '📄 ' . $n . ' fiyat anlaşması 15 gün içinde bitiyor', 'Tedarikçiyle yenile.', '/fiyat-anlasmalari', 0, 'anlasma:' . $bugun, $n);
        } } catch (\Throwable $e) {}
        try { if ($t('sayimlar') && $t('sayim_kalemleri')) foreach (DB::table('sayimlar')->where('sube_id', $sid)->where('created_at', '>', $simdi->copy()->subDays(2))->get(['id']) as $sy) {
            $fm = (float) DB::table('sayim_kalemleri')->where('sayim_id', $sy->id)->sum(DB::raw('ABS(COALESCE(fark_maliyet,0))'));
            if ($fm >= 500) $say += _bmTipeGonder($sid, 'sayim_fark', '📊 Sayımda ' . number_format($fm, 0, ',', '.') . ' ₺ fark çıktı', 'Sayım #' . $sy->id . ' — fire / kayıp kontrolü.', '/sayim', 1, 'sayim:' . $sy->id);
        } } catch (\Throwable $e) {}
        try { if ($t('hal_fiyatlari') && $c('hal_fiyatlari', 'urun_ad') && $c('hal_fiyatlari', 'ortalama')) {
            // Hal fiyatı: ürünün son kaydı bir öncekine göre %15+ arttıysa (son 24 saatte girilen kayıtlar)
            $kullanilan = function_exists('_halEslesir') ? DB::table('malzemeler')->pluck('ad')->all() : null;
            foreach (DB::table('hal_fiyatlari')->where('sube_id', $sid)->where('created_at', '>', $simdi->copy()->subDay())->where('ortalama', '>', 0)->get(['id', 'urun_ad', 'ortalama', 'tarih']) as $h) {
                if ($kullanilan !== null && !collect($kullanilan)->contains(fn ($m) => _halEslesir($m, $h->urun_ad))) continue;
                $onceki = (float) DB::table('hal_fiyatlari')->where('sube_id', $sid)->where('urun_ad', $h->urun_ad)->where('id', '!=', $h->id)->where('tarih', '<', $h->tarih)->orderByDesc('tarih')->value('ortalama');
                if ($onceki > 0 && (float) $h->ortalama >= $onceki * 1.15)
                    $say += _bmTipeGonder($sid, 'fiyat_zam', '📈 ' . (function_exists('_halAdGoster') ? _halAdGoster($h->urun_ad) : $h->urun_ad) . ' %' . (int) round(((float) $h->ortalama / $onceki - 1) * 100) . ' zamlandı (hal)', number_format($onceki, 2, ',', '.') . ' → ' . number_format((float) $h->ortalama, 2, ',', '.') . ' ₺', '/hal-fiyatlari', 0, 'zam:' . md5($h->urun_ad) . ':' . $h->tarih);
            }
        } } catch (\Throwable $e) {}
        // --- Ekip: gelmedi / geç kaldı / erken çıktı (vardiyaya göre), izin & avans talebi, yarın eksik, belge doluyor
        try { if ($t('personel_vardiya')) {
            $mesai = $t('personel_mesai');
            foreach (DB::table('personel_vardiya')->where('sube_id', $sid)->where('tarih', $bugun)->get() as $v) {
                $kisi = DB::table('personeller')->where('id', $v->personel_id)->first(['id', 'ad', 'departman_id']);
                if (!$kisi || !$v->baslangic) continue;
                $bas = strtotime($bugun . ' ' . substr($v->baslangic, 0, 5));
                $giris = $mesai ? DB::table('personel_mesai')->where('personel_id', $v->personel_id)->whereDate('giris_at', $bugun)->orderBy('giris_at')->first(['giris_at', 'cikis_at']) : null;
                $sorumlular = _bmEkipSorumlulari($sid, $kisi);
                if (!$sorumlular) continue;
                if (!$giris && time() > $bas + 900) $say += _bmEkle($sid, $sorumlular, 'ekip_gelmedi', '🙋 ' . $kisi->ad . ' vardiyasına gelmedi', 'Vardiya ' . substr($v->baslangic, 0, 5) . ' — giriş yok.', '/puantaj', 1, 'gelmedi:' . $v->id);
                elseif ($giris && strtotime($giris->giris_at) > $bas + 600) $say += _bmEkle($sid, $sorumlular, 'ekip_gec_kaldi', '⏰ ' . $kisi->ad . ' ' . (int) round((strtotime($giris->giris_at) - $bas) / 60) . ' dk geç geldi', 'Vardiya ' . substr($v->baslangic, 0, 5) . ', giriş ' . date('H:i', strtotime($giris->giris_at)), '/puantaj', 0, 'gec:' . $v->id);
                if ($giris && $giris->cikis_at && $v->bitis && strtotime($giris->cikis_at) < strtotime($bugun . ' ' . substr($v->bitis, 0, 5)) - 900)
                    $say += _bmEkle($sid, $sorumlular, 'ekip_erken_cikti', '🚪 ' . $kisi->ad . ' mesaiyi erken bitirdi', 'Çıkış ' . date('H:i', strtotime($giris->cikis_at)) . ', vardiya bitişi ' . substr($v->bitis, 0, 5), '/puantaj', 0, 'erken:' . $v->id);
            }
            // Yarın: ekipte izinli olan / vardiya planı yapılmamış
            $yarin = date('Y-m-d', strtotime('+1 day'));
            if ((int) date('G') >= 15 || !empty($GLOBALS['bm_zorla'])) foreach (DB::table('personeller')->where('sube_id', $sid)->where('aktif', 1)->get() as $sor) {
                $ekip = _bmEkibim($sid, $sor);
                if (!$ekip) continue;
                $izinli = $t('personel_izinler') ? DB::table('personel_izinler')->whereIn('personel_id', $ekip)->where('durum', 'onaylandi')->where('baslangic', '<=', $yarin)->where('bitis', '>=', $yarin)->count() : 0;
                $planli = DB::table('personel_vardiya')->whereIn('personel_id', $ekip)->where('tarih', $yarin)->distinct()->count('personel_id');
                $planKullan = DB::table('personel_vardiya')->whereIn('personel_id', $ekip)->where('tarih', '>=', date('Y-m-d', strtotime('-14 day')))->exists();
                if ($izinli || ($planKullan && $planli < count($ekip) - $izinli))
                    $say += _bmEkle($sid, [(int) $sor->id], 'yarin_ekip_eksik', '📆 Yarın ekibinden ' . ($izinli ? $izinli . ' kişi izinli' : '') . ($izinli && $planKullan ? ', ' : '') . ($planKullan ? $planli . '/' . count($ekip) . ' kişinin vardiyası var' : ''), 'Yarının planını kontrol et.', '/ik-vardiya', 0, 'yarin:' . $yarin);
            }
        } } catch (\Throwable $e) {}
        try { if ($t('personel_izinler')) foreach (DB::table('personel_izinler')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('created_at', '>', $simdi->copy()->subDays(3))->get() as $iz) {
            $kisi = DB::table('personeller')->where('id', $iz->personel_id)->first(['id', 'ad', 'departman_id']);
            if (!$kisi) continue;
            $hedef = array_unique(array_merge(_bmEkipSorumlulari($sid, $kisi), array_map(fn ($p) => (int) $p->id, _bmAlicilar($sid, 'ekip_izin_talebi', ['ik']))));
            $say += _bmEkle($sid, $hedef, 'ekip_izin_talebi', '🏖️ ' . $kisi->ad . ' izin istiyor', (['yillik' => 'Yıllık izin', 'ucretsiz' => 'Ücretsiz izin', 'rapor' => 'Rapor', 'mazeret' => 'Mazeret izni'][$iz->tip] ?? ($iz->tip ? mb_convert_case($iz->tip, MB_CASE_TITLE) : 'İzin')) . ' · ' . \Carbon\Carbon::parse($iz->baslangic)->format('d.m') . ' – ' . \Carbon\Carbon::parse($iz->bitis)->format('d.m'), '/ik-izin', 0, 'izin:' . $iz->id);
        } } catch (\Throwable $e) {}
        try { if ($t('ik_self_talep')) foreach (DB::table('ik_self_talep')->where('sube_id', $sid)->where('durum', 'bekliyor')->where('created_at', '>', $simdi->copy()->subDays(3))->get() as $tl) {
            $kisi = DB::table('personeller')->where('id', $tl->personel_id)->first(['id', 'ad', 'departman_id']);
            if (!$kisi) continue;
            $hedef = array_unique(array_merge(_bmEkipSorumlulari($sid, $kisi), array_map(fn ($p) => (int) $p->id, _bmAlicilar($sid, 'ekip_avans_talebi', ['ik']))));
            $say += _bmEkle($sid, $hedef, 'ekip_avans_talebi', '💳 ' . $kisi->ad . ' ' . ($tl->tip ?: 'avans') . ' talep etti', $tl->tutar ? number_format((float) $tl->tutar, 0, ',', '.') . ' ₺' : null, '/ik-ozet', 0, 'avans:' . $tl->id);
        } } catch (\Throwable $e) {}
        try { if ($t('personel_belge')) foreach (DB::table('personel_belge')->where('sube_id', $sid)->whereNotNull('son_gecerlilik')->whereBetween('son_gecerlilik', [$bugun, date('Y-m-d', strtotime('+30 day'))])->get() as $bl) {
            $kisi = DB::table('personeller')->where('id', $bl->personel_id)->first(['id', 'ad', 'departman_id']);
            if (!$kisi) continue;
            $hedef = array_unique(array_merge(_bmEkipSorumlulari($sid, $kisi), array_map(fn ($p) => (int) $p->id, _bmAlicilar($sid, 'ekip_belge_doluyor', ['ik', 'kalite']))));
            $say += _bmEkle($sid, $hedef, 'ekip_belge_doluyor', '📋 ' . $kisi->ad . ': ' . ($bl->baslik ?: $bl->tip ?: 'belge') . ' doluyor', \Carbon\Carbon::parse($bl->son_gecerlilik)->format('d.m.Y') . ' tarihinde sona eriyor.', '/personel', 0, 'belge:' . $bl->id);
        } } catch (\Throwable $e) {}

        // --- Görevler: atandı (kişiye), süresi geçti (departman sorumlusuna)
        try { if ($t('departman_isler')) {
            foreach (DB::table('departman_isler')->where('sube_id', $sid)->whereNotNull('atanan_id')->where('durum', '!=', 'tamam')->where('created_at', '>', $simdi->copy()->subDay())->get() as $g)
                $say += _bmEkle($sid, [(int) $g->atanan_id], 'gorev_atandi', '📌 Yeni görev: ' . $g->baslik, $g->son_tarih ? 'Son tarih ' . \Carbon\Carbon::parse($g->son_tarih)->format('d.m H:i') : null, _bmGorevLink($g->departman_kod), 0, 'gorev:' . $g->id);
            foreach (DB::table('departman_isler')->where('sube_id', $sid)->where('durum', '!=', 'tamam')->whereNotNull('son_tarih')->where('son_tarih', '<', $simdi)->where('son_tarih', '>', $simdi->copy()->subDays(3))->get() as $g) {
                $depId = (int) DB::table('departmanlar')->where('sube_id', $sid)->where('kod', $g->departman_kod)->value('id');
                if ($depId) $say += _bmEkle($sid, _departmanSorumluIdleri($sid, $depId), 'gorev_gecikti', '⚠️ Görev gecikti: ' . $g->baslik, ($g->atanan_ad ? $g->atanan_ad . ' · ' : '') . 'son tarih ' . \Carbon\Carbon::parse($g->son_tarih)->format('d.m H:i'), _bmGorevLink($g->departman_kod), 1, 'gorevgec:' . $g->id);
            }
        } } catch (\Throwable $e) {}

        // --- Üretim riski: malzemesi bitmiş/bitmek üzere olduğu için yapılamayacak ürün (sayı arttıkça)
        try { if (function_exists('_restoUretimRiski')) {
            $risk = _restoUretimRiski($sid, 15);
            $rl = collect($risk['riskli'] ?? []);
            if ($rl->count()) $say += _bmTipeGonder($sid, 'uretim_riski', '⚠️ ' . $rl->count() . ' ürün üretim riskinde', $rl->take(4)->pluck('urun')->implode(', ') . ' — malzemesi yetmiyor.', '/uretim-riski', 1, 'uretim:' . $bugun, $rl->count());
        } } catch (\Throwable $e) {}
        // --- Mutfak kontrol defteri: uygunsuz sıcaklık (acil), açılış kontrolü girilmedi
        try { if ($t('mutfak_kayitlari')) {
            foreach (DB::table('mutfak_kayitlari')->where('sube_id', $sid)->where('tip', 'sicaklik')->where('sonuc', 'uygunsuz')->where('created_at', '>', $simdi->copy()->subHours(2))->get(['id', 'baslik', 'deger', 'birim', 'personel_ad']) as $k)
                $say += _bmTipeGonder($sid, 'sicaklik_limit', '🌡️ LİMİT DIŞI: ' . $k->baslik . ' ' . $k->deger . ($k->birim ?: '°C'), 'Ölçen: ' . ($k->personel_ad ?: '—') . ' — ürünü kontrol et, DÖF aç.', '/mutfak-defteri?tip=sicaklik', 2, 'sicak:' . $k->id);
            $kullaniliyor = DB::table('mutfak_kayitlari')->where('sube_id', $sid)->where('tip', 'checklist')->where('tarih', '>=', date('Y-m-d', strtotime('-14 day')))->exists();
            if ($kullaniliyor && ((int) date('G') >= 11 || !empty($GLOBALS['bm_zorla'])) && !DB::table('mutfak_kayitlari')->where('sube_id', $sid)->where('tip', 'checklist')->where('tarih', $bugun)->exists())
                $say += _bmTipeGonder($sid, 'kontrol_defteri', '📋 Bugünkü açılış kontrolü girilmedi', 'Mutfak kontrol defterini doldur.', '/mutfak-defteri?tip=checklist', 0, 'defter:' . $bugun);
        } } catch (\Throwable $e) {}

        // --- IT / teknik: yeni arıza kaydı
        try { if ($t('it_kayitlari')) foreach (DB::table('it_kayitlari')->where('sube_id', $sid)->where('tip', 'ariza')->where('created_at', '>', $simdi->copy()->subHours(6))->get() as $ar)
            $say += _bmTipeGonder($sid, 'ariza_acik', '🛠️ Arıza kaydı: ' . $ar->baslik, $ar->personel_ad ? 'Açan: ' . $ar->personel_ad : null, '/it-destek?tip=ariza', 1, 'ariza:' . ($ar->id ?? md5($ar->baslik . $ar->created_at)));
        } catch (\Throwable $e) {}

        return $say;
    }
}

if (!function_exists('_bmEkipSorumlulari')) {
    // Bir çalışanın departman sorumluları (kendisi hariç)
    function _bmEkipSorumlulari($sid, $kisi)
    {
        $ids = [];
        if (!empty($kisi->departman_id)) $ids = _departmanSorumluIdleri($sid, (int) $kisi->departman_id);
        // istasyon çalışanı → ana mutfak şefi de
        if (!empty($kisi->departman_id) && function_exists('_talepAnaMutfakDepId') && ($ana = _talepAnaMutfakDepId($sid, (int) $kisi->departman_id))) $ids = array_merge($ids, _departmanSorumluIdleri($sid, $ana));
        return array_values(array_diff(array_unique($ids), [(int) $kisi->id]));
    }
}
if (!function_exists('_bmEkibim')) {
    // Sorumlunun ekibi (sorumlu olduğu departmanların aktif üyeleri, kendisi hariç)
    function _bmEkibim($sid, $sor)
    {
        $depIds = function_exists('_sorumluDepartmanIdleri') ? _sorumluDepartmanIdleri($sid, (int) $sor->id) : [];
        if (!$depIds) return [];
        $ids = DB::table('personeller')->where('sube_id', (int) $sid)->where('aktif', 1)->whereIn('departman_id', $depIds)->pluck('id')->all();
        try { if (Schema::hasTable('personel_departman')) $ids = array_merge($ids, DB::table('personel_departman')->whereIn('departman_id', $depIds)->pluck('personel_id')->all()); } catch (\Throwable $e) {}
        return array_values(array_diff(array_unique(array_map('intval', $ids)), [(int) $sor->id]));
    }
}

if (!function_exists('_bmMotor')) {
    // Motoru çalıştır: olaylar 2 dk'da bir, brifing uyarıları 15 dk'da bir (şube başına kilit). $zorla: kilidi yok say (cron/test)
    function _bmMotor($sid, $zorla = false)
    {
        $sid = (int) $sid; $sonuc = ['olay' => 0, 'brif' => 0];
        if (!$sid) return $sonuc;
        _bmEnsure();
        $GLOBALS['bm_zorla'] = (bool) $zorla;
        if ($zorla || cache()->add('bm_olay_' . $sid, 1, 120)) { try { $sonuc['olay'] = _bmOlayTara($sid); } catch (\Throwable $e) {} }
        if ($zorla || cache()->add('bm_brif_' . $sid, 1, 900)) { try { $sonuc['brif'] = _bmBrifingTara($sid); } catch (\Throwable $e) {} }
        // WhatsApp hat bekçisi (web.php _waHatSaglik): kopan/çıkış yapan hattı 3 dk'da bir yakala
        if (function_exists('_waHatSaglik') && ($zorla || cache()->add('wa_saglik_' . $sid, 1, 180))) { try { $sonuc['wa'] = _waHatSaglik($sid); } catch (\Throwable $e) {} }
        // İzmir hal bülteni: tüm şubelere günde bir (yayınlanmadıysa saatte bir dener) — web.php _halOtoGuncelle
        if (function_exists('_halOtoGuncelle')) { try { $sonuc['hal'] = _halOtoGuncelle(); } catch (\Throwable $e) {} }
        return $sonuc;
    }
}

// ============================ UÇLAR ============================
if (!function_exists('_bmKisi')) {
    function _bmKisi(Request $r)
    {
        $p = null;
        try { $p = _posPersonel(); } catch (\Throwable $e) {}
        if (!$p && $r->bearerToken() && function_exists('_apiPersonel')) { try { $p = _apiPersonel($r); } catch (\Throwable $e) {} }
        return $p;
    }
}

// Zil: okunmamış sayısı + son 40 bildirim. Panel açıkken motor arka planda (yanıt gittikten sonra) çalışır.
Route::get('/api/bildirim/kutu', function (Request $r) {
    $p = _bmKisi($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _bmEnsure();
    $sid = (int) $p->sube_id;
    app()->terminating(function () use ($sid) { try { _bmMotor($sid); } catch (\Throwable $e) {} });
    $q = DB::table('bildirim_kutusu')->where('personel_id', (int) $p->id);
    $liste = (clone $q)->orderByDesc('id')->limit(40)->get(['id', 'tip', 'baslik', 'govde', 'link', 'oncelik', 'okundu_at', 'created_at']);
    return response()->json(['ok' => 1, 'okunmamis' => (int) (clone $q)->whereNull('okundu_at')->count(), 'liste' => $liste->map(function ($b) {
        $b->zaman = $b->created_at ? \Carbon\Carbon::parse($b->created_at)->locale('tr')->diffForHumans() : '';
        $b->okundu = $b->okundu_at !== null; unset($b->okundu_at);
        return $b;
    })->values()]);
});
Route::post('/api/bildirim/okundu', function (Request $r) {
    $p = _bmKisi($r);
    if (!$p) return response()->json(['ok' => 0], 401);
    _bmEnsure();
    $q = DB::table('bildirim_kutusu')->where('personel_id', (int) $p->id)->whereNull('okundu_at');
    if ($r->input('id')) $q->where('id', (int) $r->input('id'));
    $q->update(['okundu_at' => now()]);
    return ['ok' => 1];
});
// Cron: tüm şubeler için motor (OS cron: curl "/api/bildirim/motor?key=SANTRAL_SECRET")
Route::get('/api/bildirim/motor', function (Request $r) {
    $anahtar = (string) env('SANTRAL_SECRET', '');
    if ($anahtar === '' || !hash_equals($anahtar, (string) $r->input('key'))) return response()->json(['ok' => 0], 403);
    $out = [];
    foreach (DB::table('personeller')->where('aktif', 1)->distinct()->pluck('sube_id') as $sid) $out[$sid] = _bmMotor((int) $sid);
    return ['ok' => 1, 'sube' => $out];
});

if (!function_exists('_bmAyarBolumleri')) {
    // Bildirim ayarları ekranı: kişinin alabileceği tipler bölüm bölüm. [bölüm => [tip => ['ad', 'kritik', 'acik']]]
    function _bmAyarBolumleri($p)
    {
        if (!$p) return [];
        $mevcut = function_exists('_bildirimTercihMap') ? _bildirimTercihMap($p->id) : [];
        $acik = fn ($tip, $vars = 1) => array_key_exists($tip, $mevcut) ? ((int) $mevcut[$tip] === 1) : ((int) $vars === 1);
        $kat = _bmKatalog(); $goster = []; $out = [];
        $ekle = function ($bolum, $tip, $ad, $kritik, $vars = 1) use (&$out, &$goster, $acik) {
            if (isset($goster[$tip])) return;
            $goster[$tip] = 1;
            $out[$bolum][$tip] = ['ad' => $ad, 'kritik' => (bool) $kritik, 'acik' => $kritik ? true : $acik($tip, $vars)];
        };
        $kd = _bmKisiDepler($p);
        $sab = function_exists('_departmanSablonlari') ? _departmanSablonlari() : [];
        foreach ($kat as $tip => $i) if (in_array('*', $i[1], true) && _bmTipAlir($p, $tip)) $ekle('Ekip Yönetimi', $tip, $i[0], $i[3]);
        foreach ($kd as $kod => $s) foreach ($kat as $tip => $i) if (in_array($kod, $i[1], true) && _bmTipAlir($p, $tip)) $ekle($sab[$kod]['ad'] ?? ucfirst($kod), $tip, $i[0], $i[3]);
        foreach ($kat as $tip => $i) if (in_array('herkes', $i[1], true)) $ekle('Kişisel', $tip, $i[0], $i[3]);
        // Eski (rol bazlı) tipler: patron özetleri, satın alma uyarıları vb. — katalogda zaten gösterilmediyse
        if (function_exists('_bildirimTipleri')) foreach (_bildirimTipleri() as $tip => $info)
            if (!isset($goster[$tip]) && !isset($kat[$tip]) && !isset(_bmTipTakma()[$tip]) && (!function_exists('_bildirimTipGorur') || _bildirimTipGorur($p, $tip))) $ekle(in_array($p->rol ?? '', ['sahip', 'mudur'], true) ? 'Yönetim Özetleri' : 'Genel', $tip, $info[0], 0, $info[1] ?? 1);
        return $out;
    }
}

if (!function_exists('_bmGorevLink')) {
    function _bmGorevLink($kod)
    {
        return in_array($kod, ['bulasik', 'temizlik', 'teknik', 'guvenlik'], true) ? '/' . $kod : '/departman-brief';
    }
}

if (!function_exists('_bmTipTakma')) {
    // Eski push tipi => katalog tipi (aynı olay; ayarlarda tek anahtar)
    function _bmTipTakma()
    {
        return ['kritik_stok' => 'stok_kritik', 'gec_tedarik' => 'siparis_gecikti'];
    }
}
if (!function_exists('_bmTakmaEsitle')) {
    // Katalog anahtarının değerini eski tip adına da yaz (eski push akışı _bildirimTercihKapali ile eski adı okur)
    function _bmTakmaEsitle($p)
    {
        try {
            foreach (_bmTipTakma() as $eski => $yeni) {
                $acik = DB::table('bildirim_tercihleri')->where('personel_id', (int) $p->id)->where('tip', $yeni)->value('acik');
                if ($acik === null) continue;
                $var = DB::table('bildirim_tercihleri')->where('personel_id', (int) $p->id)->where('tip', $eski)->first();
                if ($var) DB::table('bildirim_tercihleri')->where('id', $var->id)->update(['acik' => (int) $acik, 'updated_at' => now()]);
                else DB::table('bildirim_tercihleri')->insert(['sube_id' => (int) $p->sube_id, 'personel_id' => (int) $p->id, 'tip' => $eski, 'acik' => (int) $acik, 'created_at' => now(), 'updated_at' => now()]);
            }
        } catch (\Throwable $e) {}
    }
}

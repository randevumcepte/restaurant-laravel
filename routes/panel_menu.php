<?php
// =====================================================================
// PANEL MENÜSÜ — web sol menüsü ve uygulama menüsü (/panel-menu) TEK KAYNAK.
// _panelMenuVeri(): layout/app.blade.php'nin kullandığı tüm değişkenler ($gruplar, $_harita, $aktifMi, $kulAd ...).
// Kurallar (yetki, departman, işletme büyüklüğü, kasiyer, mutfak ekibi, satın almacı) burada; uygulamaya ayrıca yazılmaz.
// =====================================================================

if (!function_exists('_panelMenuVeri')) {
    function _panelMenuVeri()
    {
        // Gruplu menü — başlık => [[url, material-icon, etiket, renk(null=ink)], ...]
        $gruplar = [
            'Panel' => [
                ['/departman-brief', 'wb_sunny', 'Sabah Brifingim', '#F59E0B'],
                ['/dashboard', 'dashboard', 'Özet', null],
                ['/pos', 'table_restaurant', 'Masalar', null],
                ['/mutfak', 'soup_kitchen', 'Mutfak', null],
                ['/rezervasyon', 'event_available', 'Rezervasyon', null],
                ['/paket', 'delivery_dining', 'Paket', null],
            ],
            'Satış & Servis' => [
                ['/servis', 'room_service', 'Servis Ekranı (İş Akışım)', '#4F46E5'],
                ['/karsilama', 'co_present', 'Karşılama / Host (Bekleme + Oturt)', '#0EA5E9'],
                ['/garson-cagrilari', 'notifications_active', 'Garson Çağrıları', '#F43F5E'],
            ],
            'Departman Operasyon' => [
                ['/temizlik', 'cleaning_services', 'Temizlik / Hijyen', '#0EA5E9'],
                ['/guvenlik', 'security', 'Güvenlik', '#475569'],
                ['/teknik', 'build', 'Teknik Servis / Bakım', '#F97316'],
                ['/bulasik', 'wash', 'Bulaşıkhane / Steward', '#06B6D4'],
            ],
            'Menü & Ürünler' => [
                ['/menu', 'restaurant_menu', 'Menü Yönetimi', null],
                ['/recete', 'menu_book', 'Reçeteler (Yarı Mamül)', '#7C3AED'],
                ['/app-yonetim', 'palette', 'App Kartları / QR Menü', '#F6CE63'],
                ['/qr-menu-rengi', 'color_lens', 'QR Menü Rengi', '#EC4899'],
                ['/indirimler', 'local_offer', 'İndirimler', '#22C55E'],
                ['/happy-hour', 'local_bar', 'Happy Hour', '#F59E0B'],
                ['/fix-menu', 'dinner_dining', 'Fix Menü', '#8B5CF6'],
                ['/barkod', 'qr_code', 'Barkod', null],
            ],
            'Satın Alma' => [
                ['/satinalma-talep', 'assignment', 'Satın Alma Talepleri', '#7C3AED'],
                ['/gelen-teklifler', 'mark_email_read', 'Gelen Teklifler (AI)', '#10B981'],
                ['/talep-onaylarim', 'fact_check', 'Onay Bekleyen Talepler', '#F59E0B'],
                ['/reddedilen-talepler', 'block', 'Reddedilen Talepler', '#F43F5E'],
                // Üretim planı / özel gün listesini mutfak şefi hazırlar → Mutfak / BOH grubunda
                ['/stok', 'inventory_2', 'Stok & Satın Alma', null],
                ['/siparis-onerisi', 'auto_awesome', 'Otomatik Sipariş Önerisi', '#7C3AED'],
                ['/satinalma-siparis', 'shopping_cart_checkout', 'Sipariş İşlemleri', '#4F46E5'],
                ['/mal-kabul', 'inventory', 'Mal Kabul / İrsaliye', '#10B981'],
                ['/efatura-gelen', 'move_to_inbox', 'Gelen e-Faturalar', '#0EA5E9'],
                ['/fatura-denetim', 'fact_check', 'Fatura Denetimi', '#E11D48'],
                ['/fatura-analiz', 'query_stats', 'Fatura Analizleri', '#0E7490'],
                ['/fatura-eslestir', 'fact_check', 'Fatura Eşleştir (3-way)', '#F59E0B'],
                ['/fatura-ocr', 'document_scanner', 'Fatura OCR (AI)', '#7C3AED'],
                ['/satinalma-raporlar/donemsel', 'event_note', 'Dönemsel Raporlar', '#4F46E5'],
                ['/satinalma-raporlar/harcama', 'payments', 'Harcama & Fiyat', '#0891B2'],
                ['/satinalma-raporlar/maliyet', 'request_quote', 'Maliyet & Bütçe', '#059669'],
                ['/satinalma-raporlar/tedarikci', 'local_shipping', 'Tedarikçi Raporları', '#0284C7'],
                ['/satinalma-raporlar/surec', 'timelapse', 'Süreç & Talep', '#7C3AED'],
                ['/satinalma-raporlar/stok', 'inventory_2', 'Stok & Fire', '#D97706'],
                ['/satinalma-raporlar/finans', 'account_balance', 'Finans & Denetim', '#BE123C'],
                ['/satinalma-raporlar/sube', 'store', 'Çok Şube', '#0F766E'],
                ['/satinalma-raporlar/radar', 'radar', 'Fiyat Radarı Raporları', '#DB2777'],
                ['/satinalma-raporlar/ai', 'auto_awesome', 'AI Analiz', '#9333EA'],
                ['/fiyat-radari', 'radar', 'Fiyat Radarı · Gizli Zam', '#EC4899'],
                ['/hal-fiyatlari', 'storefront', 'Hal Fiyatları (sen vs hal)', '#059669'],
                ['/satinalma-analiz', 'leaderboard', 'Satınalma Analizi · Karne', '#4F46E5'],
                ['/tedarikci-karne', 'grading', 'Tedarikçi Karnesi', '#0EA5E9'],
                ['/mustahsil', 'receipt_long', 'Müstahsil Makbuzu', '#0EA5E9'],
                ['/fiyat-anlasmalari', 'handshake', 'Fiyat Anlaşmaları', '#10B981'],
                ['/satinalma-onay-seviye', 'approval', 'Onay Limitleri & Seviyeleri', '#8B5CF6'],
                ['/satinalma-butce', 'account_balance_wallet', 'Satınalma Bütçe', '#10B981'],
                ['/satinalma-asistan', 'smart_toy', 'Satınalma AI Asistanı', '#7C3AED'],
                ['/departman-brief?dep=satinalma', 'wb_sunny', 'Satınalma Sabah Brifingi', '#7C3AED'],
                ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
                ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
                ['/barkod-etiket', 'qr_code_scanner', 'Barkod & Etiket', '#0EA5E9'],
                ['/tedarikci', 'local_shipping', 'Tedarikçiler', null],
                ['/eposta-hesaplari', 'alternate_email', 'E-posta Hesapları (RFQ)', '#7C3AED'],
                ['/satinalma-islemleri', 'apps', 'Satın Alma İşlemleri', '#7C3AED'],
            ],
            'İnsan Kaynakları' => [
                ['/personel', 'badge', 'Personel & Maaş', null],
                ['/puantaj', 'schedule', 'Puantaj', null],
                ['/garson-performans', 'emoji_events', 'Garson Performansı', '#10B981'],
            ],
            'Ayarlar & Entegrasyon' => [
                ['/isletme-duzeni', 'space_dashboard', 'İşletme Düzeni (Bölge/Masa/Garson/Kasa)', '#7C3AED'],
                ['/masa-yonetim', 'qr_code_2', 'Masa & QR Yönetimi', '#7C3AED'], // İşletme Düzeni > Genel Ayarlar kartları menüde de
                ['/salon-sema', 'map', 'Salon Şema', '#0EA5E9'],
                ['/odeme-ayar', 'shield', 'Kaçak Önleme / Ödeme', '#F43F5E'],
                ['/whatsapp', 'chat', 'WhatsApp Yönetimi', '#22C55E'],
                ['/wa-hatlar', 'forum', 'WhatsApp Hatları (çoklu no)', '#25D366'],
                ['/isletme-konum', 'my_location', 'İşletme Konum', null],
                ['/yazici-ayarlari', 'print', 'Yazıcı Ayarları', '#475569'],
                ['/bagli-cihazlar', 'devices', 'Bağlı Cihazlar', '#0EA5E9'],
                ['/yasal-bilgiler', 'gavel', 'Yasal Bilgiler & Sözleşmeler', '#7C3AED'],
                ['/sebep-yonetimi', 'rule_folder', 'İptal / İkram Sebepleri', null],
                ['/hareketler', 'history', 'Hareketler (Log)', '#7C3AED'],
                ['/ai-eylem-log', 'verified_user', 'AI Eylem Günlüğü', '#7C3AED'],
            ],
        ];
        // DEPARTMAN + BİLDİRİM (additif, flag ile). gor_personel yetkisi olan departmanı görür; bildirim herkese açık.
        if (config('services.resteos.departman_acik', true)) {
            $gruplar['İnsan Kaynakları'][] = ['/departman-yonetim', 'groups', 'Departman & Yetki', '#4F46E5'];
            $gruplar['Ayarlar & Entegrasyon'][] = ['/bildirim-ayar', 'notifications_active', 'Bildirim Ayarları', '#0EA5E9'];
        }
        // İNSAN KAYNAKLARI — özet (grubun başına) + izin yönetimi. gor_personel yetkisi olana görünür.
        if (config('services.resteos.ik_acik', true)) {
            array_unshift($gruplar['İnsan Kaynakları'], ['/departman-brief?dep=ik', 'wb_sunny', 'İK Sabah Brifingi', '#F59E0B'], ['/ik-asistan', 'smart_toy', 'İK AI Asistanı', '#7C3AED']);
            $gruplar['İnsan Kaynakları'][] = ['/ik-vardiya', 'calendar_month', 'Vardiya Planlama', '#10B981'];
            $gruplar['İnsan Kaynakları'][] = ['/ik-risk', 'crisis_alert', 'Risk Radarı', '#F97316'];
            $gruplar['İnsan Kaynakları'][] = ['/ik-izin', 'event_available', 'İzin Yönetimi', '#10B981'];
            $gruplar['İnsan Kaynakları'][] = ['/ik-aday', 'how_to_reg', 'İşe Alım / Aday Havuzu', '#10B981'];
        }
        // DEPARTMAN AI ASİSTANLARI (PREMIUM — gor_ai_asistan + departman yetkisi olana). Menüde AI yetkisine göre süzülür.
        // (Mutfak AI Asistanı + Mutfak Brifingi artık "Mutfak / BOH" grubunda — Panel'den taşındı, mükerrer olmasın)

        // ===== MUHASEBE MERKEZİ — başlıklar halinde (yetki haritası süzer; sahip/müdür hepsini görür) =====
        $gruplar['Finans / Muhasebe'] = [
            ['/departman-brief?dep=finans', 'wb_sunny', 'Finans / Muhasebe Brifingi', '#F59E0B'],
            ['/muhasebe-sohbet', 'smart_toy', 'Finans / Muhasebe AI (iş yapar)', '#7C3AED'],
            ['/kasa', 'account_balance', 'Kasa (Vardiya)', '#0EA5E9'],
            ['/finans', 'analytics', 'Finans / Kâr-Zarar (özet)', '#10B981'],
            ['/raporlar', 'bar_chart', 'Raporlar', '#4F46E5'],
            ['/muhasebe-entegrasyon', 'sync_alt', 'Muhasebe Entegrasyonu (Logo/Netsis)', '#0EA5E9'],
            ['/vergi-takvimi', 'event_available', 'Vergi Takvimi', '#F43F5E'],
            ['/kdv-ozet', 'receipt_long', 'KDV Özeti', '#8B5CF6'],
            ['/kar-zarar', 'account_balance', 'Kâr-Zarar (P&L)', '#10B981'],
            ['/bilanco', 'balance', 'Bilanço (özet)', '#4F46E5'],
            ['/beyanname', 'description', 'Beyanname Hazırlık', '#EC4899'],
            ['/cari', 'account_balance_wallet', 'Cari Hesaplar', '#4F46E5'],
            ['/banka', 'account_balance', 'Banka Hesapları', '#0EA5E9'],
            ['/cek-senet', 'request_quote', 'Çek / Senet', '#F59E0B'],
            ['/gider', 'receipt_long', 'Giderler', '#F59E0B'],
            ['/tekrarlayan-gider', 'event_repeat', 'Tekrarlayan Giderler', '#8B5CF6'],
            ['/online-komisyon', 'percent', 'Online Kanal Komisyon', '#F43F5E'],
            ['/z-raporu', 'summarize', 'Z Raporu', '#0EA5E9'],
            ['/tedarikci-yaslandirma', 'hourglass_bottom', 'Tedarikçi Yaşlandırma', '#F43F5E'],
            ['/amortisman', 'trending_down', 'Amortisman', '#64748B'],
            ['/demirbas', 'inventory_2', 'Demirbaş', '#64748B'],
            ['/e-defter', 'menu_book', 'e-Defter (hazırlık)', '#7C3AED'],
            ['/muhasebe', 'book', 'Muhasebe Defteri', '#7C3AED'],
        ];

        // ===== PAZARLAMA MERKEZİ — başlıklar (yetki süzer; sahip/müdür hepsini görür) =====
        $gruplar['Pazarlama'] = [
            ['/departman-brief?dep=pazarlama', 'wb_sunny', 'Pazarlama Brifingi', '#F59E0B'],
            ['/pazarlama-sohbet', 'smart_toy', 'Pazarlama AI (iş yapar)', '#7C3AED'],
            ['/kampanya', 'campaign', 'Kampanya Stüdyosu', '#F43F5E'],
            ['/segmentler', 'groups', 'Müşteri Segmentleri', '#8B5CF6'],
            ['/icerik-takvimi', 'calendar_month', 'Sosyal İçerik Takvimi', '#0EA5E9'],
            ['/kampanya-rapor', 'leaderboard', 'Kampanya Raporu', '#4F46E5'],
        ];

        // ===== SATIŞ & ETKİNLİK MERKEZİ — başlıklar =====
        $gruplar['Rezervasyon Satış'] = [
            ['/departman-brief?dep=satis_etkinlik', 'wb_sunny', 'Rezervasyon Satış Brifingi', '#F59E0B'],
            ['/satis-sohbet', 'smart_toy', 'Rezervasyon Satış AI (iş yapar)', '#A855F7'],
            ['/rezervasyon', 'event_available', 'Rezervasyonlar', '#A855F7'],
            ['/etkinlik-takvim', 'celebration', 'Etkinlik / Grup Takvimi', '#0EA5E9'],
            ['/depozito', 'payments', 'Depozito / Kapora', '#10B981'],
        ];

        // ===== CRM / MÜŞTERİ İLİŞKİLERİ MERKEZİ — başlıklar =====
        $gruplar['CRM / Müşteri İlişkileri'] = [
            ['/departman-brief?dep=crm', 'wb_sunny', 'CRM Brifingi', '#F59E0B'],
            ['/crm-sohbet', 'smart_toy', 'CRM AI (iş yapar)', '#14B8A6'],
            ['/musteri-360', 'person_search', 'Müşteri 360', '#7C3AED'],
            ['/musteri-360?seg=vip', 'workspace_premium', 'VIP Müşteriler', '#F6A623'],
            ['/musteri-360?seg=kayip', 'person_off', 'Kayıp Müşteriler', '#F5576C'],
            ['/sadakat', 'loyalty', 'Sadakat / Puan', '#EC4899'],
            ['/itibar', 'reviews', 'İtibar / Memnuniyet', '#F59E0B'],
            ['/social-media', 'share', 'AI Sosyal Medya', '#EC4899'],
            ['/odul-dogrula', 'qr_code_scanner', 'Ödül Doğrula', '#22C55E'],
            ['/odul-raporu', 'emoji_events', 'Ödül Raporu', '#F6CE63'],
            ['/cark-yonetim', 'casino', 'Çarkıfelek', '#7C3AED'],
            ['/personel-yemek', 'lunch_dining', 'Personel Yemeği', '#F59E0B'],
        ];

        // ===== KALİTE / GIDA GÜVENLİĞİ (HACCP) MERKEZİ — başlıklar =====
        $gruplar['Kalite / HACCP'] = [
            ['/departman-brief?dep=kalite', 'wb_sunny', 'Kalite Brifingi', '#F59E0B'],
            ['/kalite-sohbet', 'smart_toy', 'Kalite AI (iş yapar)', '#16A34A'],
            ['/kalite', 'checklist', 'Kontrol Listesi (sıcaklık/hijyen)', '#0EA5E9'],
            ['/kalite-kayit?tip=numune', 'science', 'Şahit Numune', '#7C3AED'],
            ['/kalite-kayit?tip=ilaclama', 'pest_control', 'İlaçlama / Kalibrasyon', '#EA580C'],
            ['/kalite-kayit?tip=denetim', 'verified_user', 'Denetim / Teftiş', '#16A34A'],
            ['/alerjen', 'warning', 'Alerjen Matrisi', '#EF4444'],
            ['/parti-takip', 'inventory_2', 'SKT / Parti Takibi', '#64748B'],
        ];

        // ===== IT / BİLGİ İŞLEM MERKEZİ — başlıklar =====
        $gruplar['IT / Bilgi İşlem'] = [
            ['/departman-brief?dep=it', 'wb_sunny', 'IT Brifingi', '#F59E0B'],
            ['/it-sohbet', 'smart_toy', 'IT AI (iş yapar)', '#6366F1'],
            ['/bagli-cihazlar', 'devices', 'Bağlı Cihazlar', '#0EA5E9'],
            ['/yazici-ayarlari', 'print', 'Yazıcı Ayarları', '#475569'],
            ['/it-kayit?tip=ariza', 'build', 'Arıza / Destek Defteri', '#F97316'],
            ['/it-kayit?tip=lisans', 'verified', 'Lisans / SSL / Yedek', '#4F46E5'],
            ['/it-kayit?tip=kvkk', 'policy', 'KVKK / Veri Güvenliği', '#14B8A6'],
        ];
        // ===== MUTFAK / BOH (Back of House) MERKEZİ — başlıklar =====
        $gruplar['Mutfak / BOH'] = [
            ['/departman-brief?dep=mutfak', 'wb_sunny', 'Mutfak Brifingi', '#F59E0B'],
            ['/departman-asistan?dep=mutfak', 'soup_kitchen', 'Mutfak AI Asistanı', '#F59E0B'],
            ['/departman-brief?dep=soguk_mutfak', 'kitchen', 'Soğuk Mutfak', '#10B981'],
            ['/departman-brief?dep=pastane', 'cake', 'Pastane / Tatlı', '#EC4899'],
            ['/departman-brief?dep=kasap', 'kebab_dining', 'Kasap / Et Hazırlık', '#DC2626'],
            ['/departman-brief?dep=bulasik', 'wash', 'Bulaşıkhane', '#06B6D4'],
            ['/mutfak', 'restaurant', 'Mutfak / KDS Ekranı', '#F97316'],
            ['/uretim-plani', 'auto_awesome', 'Üretim Planı / Özel Gün', '#10B981'],
            ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
            ['/satinalma-talep', 'assignment', 'Satın Alma Talebi', '#7C3AED'],
            ['/parti-takip', 'event_busy', 'SKT / Parti Takibi', '#64748B'],
            ['/recete', 'menu_book', 'Reçeteler / Yarı Mamül', '#7C3AED'],
            ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
            ['/mutfak-defteri', 'checklist', 'Kontrol Defteri (HACCP/Fire/Prep)', '#16A34A'],
            ['/rezervasyon', 'event_seat', 'Beklenen Kuver (Rezervasyon)', '#A855F7'],
        ];
        // ===== MUTFAK RAPORLARI — şef / sous şef / müdür / patron (aşçı görmez: _mrErisim) =====
        $gruplar['Mutfak Raporları'] = [
            ['/mutfak-raporlar/donemsel', 'event_note', 'Dönemsel Raporlar', '#EA580C'],
            ['/mutfak-raporlar/hiz', 'timer', 'Hız & Servis', '#DC2626'],
            ['/mutfak-raporlar/maliyet', 'percent', 'Food Cost & Tüketim', '#059669'],
            ['/mutfak-raporlar/menu', 'auto_graph', 'Menü Mühendisliği', '#7C3AED'],
            ['/mutfak-raporlar/fire', 'delete_sweep', 'Fire & İsraf', '#C2410C'],
            ['/mutfak-raporlar/uretim', 'blender', 'Üretim & Hazırlık', '#0891B2'],
            ['/mutfak-raporlar/stok', 'kitchen', 'Stok & Sayım', '#2563EB'],
            ['/mutfak-raporlar/hijyen', 'health_and_safety', 'Hijyen & Kontrol', '#16A34A'],
            ['/mutfak-raporlar/iptal', 'cancel', 'İptal & İkram', '#BE123C'],
            ['/mutfak-raporlar/ekip', 'groups', 'Ekip & Talepler', '#4F46E5'],
            ['/mutfak-raporlar/ai', 'auto_awesome', 'Mutfak AI Analiz', '#9333EA'],
        ];
        // ===== AI SANTRAL — telefonu açan asistan: kayıtlar, arayan tanıma, aktarma, eğitim (gor_santral) =====
        $gruplar['AI Santral'] = [
            ['/santral-kayitlar', 'call', 'Çağrı Kayıtları', '#4F46E5'],
            ['/cagrilar', 'contact_phone', 'Çağrı Merkezi (Arayan Tanıma)', '#0EA5E9'],
            ['/cagri-ekran', 'phone_in_talk', 'Gelen Çağrı Ekranı', '#10B981'],
            ['/santral-ayar', 'phone_forwarded', 'Aktarma & Teslimat Ayarları', '#F59E0B'],
            ['/santral-egitim', 'school', 'AI Santral Eğitimi', '#7C3AED'],
        ];
        // ===== DEPARTMAN RAPORLARI (routes/dep_raporlar): her departmanın rapor kategorileri kendi menü grubunun sonunda (görünürlük _drErisim) =====
        try { if (function_exists('_drModuller')) foreach (_drModuller() as $_drKod => $_drM) if (isset($gruplar[$_drM['grup']])) $gruplar[$_drM['grup']] = array_merge($gruplar[$_drM['grup']], _drMenu($_drKod)); } catch (\Throwable $e) {}
        // PATRON AI — sabah brifingi + Genel Müdür + ajanda (en üste). gor_copilot yetkisi olana görünür.
        $gruplar = ['Patron AI' => [
            ['/patron-ai', 'auto_awesome', 'Sabah Brifingi', '#7C3AED'],
            ['/patron-ai?gm=1', 'manage_accounts', 'Genel Müdür', '#4F46E5'],
        ], 'Ajanda' => [
            // Herkese açık kişisel ajanda — her personel kendi randevu/hatırlatıcısını girer (veri personel_id bazlı)
            ['/ajanda', 'event_note', 'Ajanda & Hatırlatıcı', '#4F46E5'],
        ]] + $gruplar;
        // Patron ile Genel Müdür brifingi aynı ekran: patron yalnız kendi Sabah Brifingi'ni, diğer yöneticiler (genel müdür) GM brifingini görür
        try {
            $_gmRol = optional(_posPersonel())->rol ?? '';
            $gruplar['Patron AI'] = $_gmRol === 'sahip' ? [$gruplar['Patron AI'][0]] : [['/patron-ai?gm=1', 'auto_awesome', 'Sabah Brifingi', '#7C3AED']];
            $gruplar['Patron AI'][] = ['/patron-ai-sohbet', 'smart_toy', $_gmRol === 'sahip' ? 'Patron AI Sohbet' : 'AI Sohbet', '#4F46E5']; // sayfa yalnız sahip/müdüre açılır; diğerlerinde haritada olmadığı için gizli
        } catch (\Throwable $e) {}
        // İŞLETME BÜYÜKLÜĞÜ: küçük/orta işletmede menü sadeleşir; kurulu olmayan alt departman hiçbir ölçekte görünmez.
        $_olcekSor = false;
        try {
            if (function_exists('_menuOlcekSuz')) {
                $_osid = _webSubeId(); $_orol = optional(_posPersonel())->rol ?? '';
                $gruplar = _menuOlcekSuz($gruplar, $_osid, $_orol);
                $_od = _isletmeOlcek($_osid);
                // büyüklük artık ResteOS sistem panelinde seçiliyor: işletmeye "seçin" bandı gösterilmez
            }
        } catch (\Throwable $e) {}
        // Kasiyer yalnız Panel'i görür; kasa noktası ayarı hangi sayfaların açık olduğunu belirler
        $_kasiyerMenu = false;
        try { $_kp = _posPersonel(); if ($_kp && _kasiyerMi($_kp)) { $gruplar = _kasiyerMenu($gruplar, $_kp); $_kasiyerMenu = true; } } catch (\Throwable $e) {}
        // Panel başlığı yalnız kasalarda; diğer herkeste Mutfak ve Rezervasyon kendi gruplarında, kalanı Satış & Servis'e geçer
        if (!$_kasiyerMenu && isset($gruplar['Panel'])) {
            $_panelTasi = array_values(array_filter($gruplar['Panel'], fn ($it) => in_array($it[0], ['/dashboard', '/pos', '/paket'], true)));
            $gruplar['Satış & Servis'] = array_merge($_panelTasi, $gruplar['Satış & Servis'] ?? []);
            unset($gruplar['Panel']);
            // Sabah Brifingim satışla ilgisiz: en üstte tek bağlantı (sahip/müdürün Patron AI Sabah Brifingi var)
            if ($_kp && !in_array($_kp->rol ?? '', ['sahip', 'mudur'], true)) {
                $_brf = ['Brifing' => [['/departman-brief', 'wb_sunny', 'Sabah Brifingim', '#F59E0B']]];
                $_yeniG = [];
                foreach ($gruplar as $_ga => $_gi) { $_yeniG[$_ga] = $_gi; if ($_ga === 'Ajanda') $_yeniG += $_brf; }
                $gruplar = $_yeniG + $_brf;
                // Kendi departmanının brifingi zaten en üstte: grup içindeki aynı bağlantı çift olmasın
                $_kendiBrf = '/departman-brief?dep=' . _departmanBriefKod($_kp, (int) ($_kp->sube_id ?? 0));
                foreach ($gruplar as $_ga => $_gi) if ($_ga !== 'Brifing') $gruplar[$_ga] = array_values(array_filter($_gi, fn ($it) => $it[0] !== $_kendiBrf));
            }
        }
        // Mutfak ekibi (şef dahil) Satın Alma grubunu görmez; şefin stok bağlantısı Mutfak / BOH'ta
        if ($_kp && isset($gruplar['Satın Alma']) && _mutfakEkibiMi($_kp)) {
            unset($gruplar['Satın Alma']);
            $gruplar['Mutfak / BOH'][] = ['/stok', 'inventory_2', 'Stok', '#0EA5E9'];
            if (function_exists('_faYetki') && _faYetki($_kp)) $gruplar['Mutfak / BOH'][] = ['/fiyat-anlasmalari', 'handshake', 'Anlaşmalı Fiyatlar', '#10B981']; // şef: salt okunur (ya da verilen yetki)
        }
        // Şefin Menü Yönetimi de Mutfak / BOH'ta; Menü & Ürünler (Happy Hour, Fix Menü, kampanya) mutfak ekibinde gizli
        if ($_kp && isset($gruplar['Menü & Ürünler']) && _mutfakEkibiMi($_kp)) {
            unset($gruplar['Menü & Ürünler']);
            $gruplar['Mutfak / BOH'][] = ['/menu', 'restaurant_menu', 'Menü Yönetimi', '#7C3AED'];
        }
        // Mutfak ekibi rezervasyonu Mutfak / BOH'taki Rezervasyonlar'dan görür; satış/karşılama grupları gizli
        if ($_kp && _mutfakEkibiMi($_kp)) {
            unset($gruplar['Rezervasyon Satış'], $gruplar['Satış & Servis']);
            foreach ($gruplar['Mutfak / BOH'] ?? [] as $_i => $_it) if ($_it[0] === '/rezervasyon') $gruplar['Mutfak / BOH'][$_i][2] = 'Rezervasyonlar';
        }
        // Mutfak ekibinde Departman Operasyon grubu yok: içindeki (Bulaşıkhane görevleri vb.) Mutfak / BOH altında
        if ($_kp && isset($gruplar['Departman Operasyon']) && _mutfakEkibiMi($_kp)) {
            $gruplar['Mutfak / BOH'] = array_merge($gruplar['Mutfak / BOH'] ?? [], $gruplar['Departman Operasyon']);
            unset($gruplar['Departman Operasyon']);
        }
        // Bulaşıkhane görevleri aşçıya ve istasyon çalışanına görünmez
        if ($_kp && !_bulasikGorur($_kp)) {
            foreach ($gruplar as $_ga => $_gi) $gruplar[$_ga] = array_values(array_filter($_gi, fn ($it) => $it[0] !== '/bulasik'));
        }
        // Alt istasyon çalışanı mutfak geneli ekranları görmez (şef ve yönetici görür)
        if ($_kp && _mutfakAltIstasyonMu($_kp)) {
            foreach ($gruplar as $_ga => $_gi) $gruplar[$_ga] = array_values(array_filter($_gi, fn ($it) => !in_array($it[0], ['/uretim-plani', '/uretim-riski', '/parti-takip', '/sayim'], true)));
        }
        // Şubeler Arası Transfer: yalnız transfer ortağı olan (gruplu / franchise) şubede; Satın Alma'da Gelen Teklifler'in altına (yetki aşağıda süzülür)
        try {
            if ($_kp && function_exists('_transferOrtaklari') && _transferOrtaklari((int) $_kp->sube_id)) {
                $_trfIt = ['/sube-transfer', 'swap_horiz', 'Şubeler Arası Transfer', '#0891B2'];
                $_trfG = isset($gruplar['Satın Alma']) ? 'Satın Alma' : 'Mutfak / BOH';
                $_trfYeni = []; $_trfKondu = false;
                foreach ($gruplar[$_trfG] ?? [] as $_it) { $_trfYeni[] = $_it; if ($_it[0] === '/gelen-teklifler') { $_trfYeni[] = $_trfIt; $_trfKondu = true; } }
                if (!$_trfKondu) $_trfYeni[] = $_trfIt;
                $gruplar[$_trfG] = $_trfYeni;
            }
        } catch (\Throwable $e) {}
        // Mutfak raporları ayrı grup değil, Mutfak / BOH grubunun sonunda (görünürlük yine _mrErisim: şef / sous şef / müdür / patron)
        if (isset($gruplar['Mutfak / BOH'], $gruplar['Mutfak Raporları'])) $gruplar['Mutfak / BOH'] = array_merge($gruplar['Mutfak / BOH'], $gruplar['Mutfak Raporları']);
        unset($gruplar['Mutfak Raporları']);

        // İşe göre 4 kısa grup: günlük akış / fatura-fiyat / tedarikçi-analiz / stok-üretim (listede olmayan günlük akışa)
        $_saBol = [ // satın alma raporları Satın Alma grubunun içinde kalır (satın almacının raporları)
            'Fatura & Fiyat' => ['/efatura-gelen', '/fatura-denetim', '/fatura-analiz', '/fatura-eslestir', '/fatura-ocr', '/fiyat-radari', '/hal-fiyatlari', '/fiyat-anlasmalari', '/mustahsil'],
            'Tedarikçi & Analiz' => ['/tedarikci', '/tedarikci-karne', '/satinalma-analiz', '/satinalma-butce', '/satinalma-onay-seviye', '/eposta-hesaplari'],
            'Stok & Üretim' => ['/uretim-plani', '/uretim-riski', '/sayim', '/barkod-etiket']];
        // ===== YÖNETİM — her departman KENDİ akordiyon grubu olarak en üste alınır (başlığa tıkla → sayfaları açılır; ilk sırada brifing). Sadece sahip/müdür için öne çıkar. =====
        try {
            $_yp = function_exists('_posPersonel') ? _posPersonel() : null;
            if ($_yp && in_array(($_yp->rol ?? ''), ['sahip', 'mudur'])) {
                if (!empty($gruplar['Satın Alma'])) { // satın almacı menüsüyle aynı bölme: Satın Alma / Fatura & Fiyat / Tedarikçi & Analiz / Stok & Üretim / Raporlar & Analizler
                    $_saP = ['Satın Alma' => []] + array_fill_keys(array_keys($_saBol), []);
                    foreach ($gruplar['Satın Alma'] as $_it) { $_g = 'Satın Alma'; foreach ($_saBol as $_bg => $_bu) if (in_array(strtok($_it[0], '?'), $_bu, true)) { $_g = $_bg; break; } $_saP[$_g][] = $_it; }
                    $_saSira = ['/departman-brief?dep=satinalma' => 0, '/satinalma-asistan' => 1];
                    usort($_saP['Satın Alma'], fn ($a, $b) => ($_saSira[$a[0]] ?? 2) <=> ($_saSira[$b[0]] ?? 2)); // brifing → AI → diğerleri (sıra korunur)
                    unset($gruplar['Satın Alma']);
                    foreach ($_saP as $_bg => $_its) if ($_its) $gruplar[$_bg] = array_merge($gruplar[$_bg] ?? [], $_its);
                }
                $_deptSira = ['Mutfak / BOH', 'İnsan Kaynakları', 'Finans / Muhasebe', 'Satın Alma', 'Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim', 'Pazarlama', 'Rezervasyon Satış', 'AI Santral', 'CRM / Müşteri İlişkileri', 'Kalite / HACCP', 'IT / Bilgi İşlem', 'Departman Operasyon'];
                $_patronAi = ['Patron AI' => $gruplar['Patron AI'] ?? []];
                unset($gruplar['Patron AI']);
                $_dept = [];
                foreach ($_deptSira as $_d) { if (isset($gruplar[$_d])) { $_dept[$_d] = $gruplar[$_d]; unset($gruplar[$_d]); } }
                $gruplar = $_patronAi + $_dept + $gruplar; // Patron AI + departman akordiyonları en üstte, altında operasyon grupları
            }
        } catch (\Throwable $e) {}

        // ===== SATIN ALMACI'ya ÖZEL MENÜ DÜZENİ =====
        // 1) Üstte 3 standalone büyük link: Özet · AI Asistan · Stok & Satın Alma
        // 2) "İŞLEMLER" grubu: diğer satın alma iş akışları (talep, sipariş, OCR, karne vb.)
        // 3) Kasa & Finans (Cari)
        // 4) Departman Operasyon (en alt)
        $_pp = null; try { if (function_exists('_posPersonel')) $_pp = _posPersonel(); } catch (\Throwable $e) {}
        $_ppRol = $_pp->rol ?? '';
        if ($_pp && in_array($_ppRol, ['satinalmaci','satinalma'])) {
            if (isset($gruplar['Menü & Ürünler'])) unset($gruplar['Menü & Ürünler']);

            $_ozetI = null; $_aiI = null; $_stokI = null;
            if (isset($gruplar['Satın Alma'])) {
                $gruplar['Satın Alma'] = array_values(array_filter($gruplar['Satın Alma'], function ($it) use (&$_ozetI, &$_aiI, &$_stokI) {
                    if ($it[0] === '/departman-brief?dep=satinalma')   { $_ozetI = $it; return false; }
                    if ($it[0] === '/satinalma-asistan') { $_aiI  = $it; return false; }
                    if ($it[0] === '/stok')              { $_stokI = $it; return false; }
                    return true;
                }));
            }
            // Etiketleri sadeleştir
            if ($_ozetI) $_ozetI[2] = 'Brifing';
            if ($_aiI)   $_aiI[2]   = 'AI Asistan';
            if ($_stokI) $_stokI[2] = 'Stok & Satın Alma';

            // Tek "Satın Alma" grubu: Stok + AI başta, brifing üstteki Sabah Brifingim'de (yoksa grubun başında); Mutfak / BOH satın almacıda yok
            $_bas = [];
            if ($_ozetI && !isset($gruplar['Brifing'])) $_bas[] = $_ozetI;
            if ($_aiI && !isset($gruplar['Brifing'])) $_bas[] = $_aiI;
            if ($_aiI && isset($gruplar['Brifing'])) $gruplar['Brifing'][] = $_aiI; // brifing → AI (diğer departmanlar gibi)
            if ($_stokI) $_bas[] = $_stokI;
            $_sa = array_merge($_bas, $gruplar['Satın Alma'] ?? []);
            unset($gruplar['Satın Alma'], $gruplar['Mutfak / BOH'], $gruplar['Kalite / HACCP']); // kalite mutfağın işi; satın almanın kontrolü Mal Kabul'de
            $_yeni = isset($gruplar['Brifing']) ? ['Brifing' => $gruplar['Brifing']] : [];
            unset($gruplar['Brifing']);
            // Satın alma sayfaları işe göre gruplara bölünür ($_saBol yukarıda; listede olmayan günlük akışa)
            $_saGrup = ['Satın Alma' => []] + array_fill_keys(array_keys($_saBol), []);
            foreach ($_sa as $_it) { $_g = 'Satın Alma'; foreach ($_saBol as $_bg => $_bu) if (in_array($_it[0], $_bu, true)) { $_g = $_bg; break; } $_saGrup[$_g][] = $_it; }
            foreach ($_saGrup as $_bg => $_its) if ($_its) $_yeni[$_bg] = $_its;

            $_dept = $gruplar['Departman Operasyon'] ?? null;
            unset($gruplar['Departman Operasyon']);
            foreach ($gruplar as $_gad => $_items) { $_yeni[$_gad] = $_items; }
            if ($_dept) $_yeni['Departman Operasyon'] = $_dept;

            $gruplar = $_yeni;
        }

        $path = '/' . trim(request()->path(), '/');
        $_reqUri = request()->getRequestUri();
        // Profilimden açılan sayfada "Geri" profile döner; sayfa içi kayıt/filtre yenilemelerinde de ok kalır
        $_refYol = rtrim((string) parse_url((string) request()->headers->get('referer'), PHP_URL_PATH), '/');
        if ($_refYol === '/profilim' && $path !== '/profilim') session(['profil_geri_yol' => $path]);
        elseif ($_refYol !== $path) session()->forget('profil_geri_yol');
        $_profilGeri = session('profil_geri_yol') === $path;
        $aktifMi = function ($url) use ($path, $_reqUri) {
            if ($url === '/patron-ai?gm=1') return $path === '/patron-ai' && request()->boolean('gm'); // GM brifingi sabah/akşam sekmesinde de seçili
            if (strpos($url, '?') !== false) return $_reqUri === $url;                 // sorgulu link: tam eşleşme
            if ($url === '/') return $path === '/';
            if ($url === '/patron-ai' && request()->boolean('gm')) return false;        // GM modunda düz patron-ai seçili sayılmaz
            return \Illuminate\Support\Str::startsWith($path, $url);
        };
        $aktifSubeAd = 'ResteOS';
        try { if (function_exists('_aktifSubeId')) { $aktifSubeAd = optional(\DB::table('subeler')->where('id', _aktifSubeId())->first())->ad ?: 'ResteOS'; } } catch (\Throwable $e) {}
        if ($aktifSubeAd === 'ResteOS') { try { $aktifSubeAd = optional(\DB::table('subeler')->first())->ad ?: 'ResteOS'; } catch (\Throwable $e) {} }
        $kulAd = 'Yönetim'; $kulRol = 'Panel'; $_pp = null;
        try { if (function_exists('_posPersonel')) $_pp = _posPersonel(); } catch (\Throwable $e) {}
        if ($_pp) {
            $kulAd = $_pp->ad ?? 'Yönetim';
            $_kad = $_pp->kademe ?? (function_exists('_rolKademe') ? _rolKademe($_pp->rol ?? '') : ($_pp->rol ?? ''));
            $_kadlar = function_exists('_panelKademeler') ? _panelKademeler() : [];
            $kulRol = $_kadlar[$_kad]['ad'] ?? (['sahip'=>'Sahip','mudur'=>'Müdür','kasa'=>'Kasa','garson'=>'Garson','mutfak'=>'Mutfak'][$_pp->rol ?? ''] ?? ($_pp->rol ?? 'Panel'));
        }
        $_harita = function_exists('_sayfaYetkiHaritasi') ? _sayfaYetkiHaritasi() : [];
        // Menu badge sayilari (yeni talep, kritik stok, cagri vb.) — 60sn cache
        $_menuBadge = [];
        try { if (function_exists('_menuBadgeler') && function_exists('_aktifSubeId')) $_menuBadge = _menuBadgeler(_aktifSubeId()); } catch (\Throwable $e) {}
        $_badgeMetin = fn($n) => $n > 99 ? '99+' : (string) $n;

        // ---- kategori yapısı + görünürlük (eskiden <nav> içindeydi) ----
        // ÜST BAŞLIK (kategori) yapısı — gruplar bölümlere ayrılır; kategorisiz gruplar (Patron AI, __PIN__) en üstte standalone kalır
        $_kategoriler = [
            'YÖNETİM' => ['Mutfak / BOH', 'İnsan Kaynakları', 'Finans / Muhasebe', 'Satın Alma', 'Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim', 'Pazarlama', 'Rezervasyon Satış', 'AI Santral', 'CRM / Müşteri İlişkileri', 'Kalite / HACCP', 'IT / Bilgi İşlem', 'Departman Operasyon'],
            'MENÜ' => ['Menü & Ürünler'],
            'OPERASYON' => ['Satış & Servis'],
            'AYARLAR' => ['Ayarlar & Entegrasyon'],
        ];
        $_katIkon = ['YÖNETİM' => 'workspace_premium', 'MENÜ' => 'restaurant_menu', 'OPERASYON' => 'tune', 'AYARLAR' => 'settings'];
        $_grupKat = [];
        foreach ($_kategoriler as $_kk => $_gs) foreach ($_gs as $_g) $_grupKat[$_g] = $_kk;
        // Mutfak rolüne yetkisiz ekran gösteren istisna kalktı: her bağlantı kendi yetkisine bakar (aşçı satın almayı/sayımı görmez)
        $_itemGorunur = fn ($it) => _menuOgeGorunur($_pp, $_harita, $it);
        $_grupGorunur = function ($items) use ($_itemGorunur) { foreach ($items as $it) if ($_itemGorunur($it)) return true; return false; };
        // En dar personel (yönetici/sorumlu değil, kademe Personel/Mutfak): kategori ve grup başlığı yok, ilgili sayfalar düz liste
        $_kadM = $_pp ? ($_pp->kademe ?? _rolKademe($_pp->rol ?? '')) : null;
        // Departman çalışanı (yönetici/satın almacı değil): kategori başlıkları (YÖNETİM, OPERASYON) gösterilmez
        $_satinalmaciMenu = $_pp && in_array($_pp->rol ?? '', ['satinalmaci', 'satinalma'], true);
        $_katsiz = $_satinalmaciMenu || ($_pp && !empty($_pp->departman_id) && !$_kasiyerMenu && !in_array($_pp->rol ?? '', ['sahip', 'mudur'], true) && !in_array($_kadM, ['sahip', 'supervizor', 'satinalma'], true));
        if ($_satinalmaciMenu) { $_kategoriler['YÖNETİM'] = array_values(array_unique(array_merge(['Satın Alma', 'Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim'], $_kategoriler['YÖNETİM']))); foreach (['Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim'] as $_g) $_grupKat[$_g] = 'YÖNETİM'; } // satın alma grupları en üstte; raporlar Finans'ın altında
        // Patron / müdür: Satış & Servis ayrı OPERASYON başlığında değil, DEPARTMANLAR'ın içinde (en sonda)
        if ($_pp && in_array($_pp->rol ?? '', ['sahip', 'mudur'], true)) { $_kategoriler['YÖNETİM'][] = 'Satış & Servis'; unset($_kategoriler['OPERASYON']); $_grupKat['Satış & Servis'] = 'YÖNETİM'; }
        $_duzMenu = $_pp && !$_kasiyerMenu && !in_array($_pp->rol ?? '', ['sahip', 'mudur'], true) && in_array($_kadM, ['personel', 'mutfak'], true) && empty($_pp->departman_sorumlu);
        return get_defined_vars();
    }
}

if (!function_exists('_menuOgeGorunur')) {
    // Menü bağlantısı bu kişiye görünür mü (web sol menü + uygulama menüsü ortak kural). $it = [url, ikon, ad, renk]
    function _menuOgeGorunur($_pp, $_harita, $it)
    {
        if (!$_pp) return true;
        if (($_pp->rol ?? '') === 'sahip') return true;                        // işletme sahibi her şeyi görür
        if ($it[0] === '/odeme-ayar') return false;                            // sayfa yalnız sahibe açık (müdür 403 alıyordu)
        if ($it[0] === '/satinalma-onay-seviye') return ($_pp->rol ?? '') === 'mudur'; // sayfa yalnız sahip/müdür (satın almacı 403 alıyordu)
        if (($_pp->rol ?? '') === 'mudur') {
            // Müdür her şeyi görür; yalnız patronun ELLE verdiği premium/hassas yetkiler (AI asistan, malzeme kartı) verilmediyse gizli
            if (($_ai = _aiAsistanLinkAcik($_pp, $it[0])) !== null) return $_ai;
            if (isset($_harita[$it[0]]) && in_array($_harita[$it[0]], ['gor_ai_asistan', 'gor_odeme_ayar', 'gor_malzeme_kart'], true)) return _panelYetkiVar($_pp, $_harita[$it[0]]);
            return true;
        }
        if (\Illuminate\Support\Str::startsWith($it[0], '/departman-brief')) return _departmanBriefLinkAcik($_pp, $it[0]); // başka departmanın brifingi görünmez
        if ($it[0] === '/wa-hatlar') return _waHatGorur($_pp); // hattı olmayana boş sayfa göstermesin
        if (($_ai = _aiAsistanLinkAcik($_pp, $it[0])) !== null) return $_ai; // AI izni + departman yetkisi (şefe İK AI görünmesin)
        if ($it[0] === '/talep-onaylarim') return _talepUstYonetimMu($_pp); // sorumlu onayları Satın Alma Talepleri sayfasında (tek sayfa) yönetir
        if (in_array($it[0], ['/demirbas', '/barkod-etiket'], true) && function_exists('_dmYetki')) return (bool) _dmYetki($_pp); if ($it[0] === '/fiyat-anlasmalari' && function_exists('_faYetki')) return (bool) _faYetki($_pp); // görüntüle/düzenle yetkisi ya da sorumlu salt okur
        if (str_starts_with($it[0], '/satinalma-raporlar/')) return _panelYetkiVar($_pp, 'gor_satinalma'); // rapor kategorileri tek yetki
        if (str_starts_with($it[0], '/mutfak-raporlar/')) return function_exists('_mrErisim') && _mrErisim($_pp); // şef / sous şef / müdür / patron
        if (function_exists('_drYolErisim') && ($_dr = _drYolErisim($it[0], $_pp)) !== null) return $_dr; // departman raporları (İK, Finans, ...): yönetici / departman sorumlusu
        if ($it[0] === '/patron-ai?gm=1') return _panelYetkiVar($_pp, 'gor_copilot'); // Genel Müdür brifingi: /patron-ai ile aynı yetki
        if (in_array($it[0], ['/bildirim-ayar', '/profilim', '/ajanda'])) return true; // kişisel ajanda herkese açık
        if (isset($_harita[$it[0]])) return _panelYetkiVar($_pp, $_harita[$it[0]]); // haritadaki = yetkiye göre
        return false;                                                          // haritasız = kısıtlı role gizle
    }
}

if (!function_exists('_menuGrupIkon')) {
    // Menü grubu başlık ikonu (material icon adı)
    function _menuGrupIkon($grupAd)
    {
        return ['Panel' => 'dashboard', 'Satış & Servis' => 'point_of_sale', 'Departman Operasyon' => 'apartment', 'Menü & Ürünler' => 'restaurant_menu',
        'Satın Alma' => 'shopping_cart', 'Fatura & Fiyat' => 'receipt_long', 'Tedarikçi & Analiz' => 'local_shipping', 'Stok & Üretim' => 'inventory_2',
        'Raporlar & Analizler' => 'insights', 'İnsan Kaynakları' => 'badge', 'Finans / Muhasebe' => 'account_balance', 'Mutfak / BOH' => 'soup_kitchen',
        'Pazarlama' => 'campaign', 'Rezervasyon Satış' => 'event_seat', 'AI Santral' => 'support_agent', 'CRM / Müşteri İlişkileri' => 'diversity_3',
        'Kalite / HACCP' => 'verified', 'IT / Bilgi İşlem' => 'computer', 'Ajanda' => 'event_note', 'Patron AI' => 'auto_awesome', 'Ayarlar & Entegrasyon' => 'settings'][$grupAd] ?? 'folder_open';
    }
    // Rapor sayfası mı (grubun içinde "Raporlar" alt başlığında toplanır): satın alma / mutfak / departman raporları
    function _menuRaporMu($u)
    {
        return str_starts_with($u, '/satinalma-raporlar/') || str_starts_with($u, '/mutfak-raporlar/') || (function_exists('_drYolMu') && _drYolMu($u));
    }
}

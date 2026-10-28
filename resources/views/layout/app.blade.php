<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    {{-- Favicon: SVG (modern tarayıcılar) + fallback --}}
    <link rel="icon" type="image/svg+xml" href="/icon.svg">
    <link rel="apple-touch-icon" href="/icon.svg">
    <meta name="theme-color" content="#7C3AED">
    <title>@yield('title', 'ResteOS') — ResteOS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="/panel-kit.css?v=11">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>try{if(localStorage.getItem('app_koyu')==='1')document.documentElement.classList.add('_koyu');}catch(e){}</script>
    <style>
    html._koyu body{}
    .rk-grp{display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;
      font-size:12px;font-weight:900;letter-spacing:.8px;text-transform:uppercase;opacity:1;color:#1e293b;
      padding:15px 16px 9px;margin-top:6px;border-top:1px solid rgba(120,120,140,.18)}
    body.koyu .rk-grp{color:#e2e8f0}
    .rk-grp:first-of-type{border-top:0;margin-top:0}
    .rk-grp::before{content:'';display:inline-block;width:3px;height:12px;background:linear-gradient(180deg,#7C3AED,#4F46E5);border-radius:2px;margin-right:8px;vertical-align:-1px}
    .rk-grp:hover{opacity:1;color:#7C3AED}
    body.koyu .rk-grp:hover{color:#a78bfa}
    .rk-grp .rk-grp-ok{font-size:19px;transition:transform .2s ease;opacity:.6}
    .rk-grp.kapali .rk-grp-ok{transform:rotate(-90deg)}
    .rk-grp.kapali + .rk-grp-body{display:none}
    /* PINNED LINKS (satın almacı — Özet · AI Asistan · Stok & Satın Alma) */
    .rk-pin-pano{display:flex;flex-direction:column;gap:6px;padding:12px 10px 10px;margin-bottom:8px;border-bottom:1px solid rgba(120,120,140,.14)}
    .rk-pin{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;text-decoration:none;color:#1e293b;background:#fff;border:1.5px solid #e2e8f0;font-size:14.5px;font-weight:800;transition:all .14s;position:relative;overflow:hidden}
    body.koyu .rk-pin{background:#1b2235;border-color:#2c3650;color:#e2e8f0}
    .rk-pin::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(180deg,#7C3AED,#4F46E5);opacity:0;transition:opacity .14s}
    .rk-pin:hover{transform:translateX(2px);border-color:#7C3AED;color:#7C3AED;box-shadow:0 4px 14px rgba(124,58,237,.18)}
    .rk-pin:hover::before{opacity:1}
    .rk-pin .pi{position:relative;width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,rgba(124,58,237,.14),rgba(79,70,229,.08));display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background .14s}
    .rk-pin .pi .material-icons-outlined{font-size:20px;color:#7C3AED}
    .rk-pin .pt{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;letter-spacing:-.2px}
    .rk-pin .pok{font-size:18px;color:#94a3b8;opacity:.5;transition:all .14s}
    .rk-pin:hover .pok{color:#7C3AED;opacity:1;transform:translateX(2px)}
    .rk-pin.aktif{background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;border-color:transparent;box-shadow:0 6px 18px rgba(124,58,237,.35)}
    .rk-pin.aktif::before{opacity:0}
    .rk-pin.aktif .pi{background:rgba(255,255,255,.22)}
    .rk-pin.aktif .pi .material-icons-outlined{color:#fff}
    .rk-pin.aktif .pok{color:#fff;opacity:.9}
    /* Collapsed sidebar (>=641px, hover degil): pin'ler sade ikon olsun */
    @media (min-width:641px){
      .rk-side:not(:hover) .rk-pin-pano{padding:8px 0 10px;margin-bottom:4px;gap:2px;border-bottom:1px solid rgba(120,120,140,.14)}
      .rk-side:not(:hover) .rk-pin{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;justify-content:center;height:44px;margin:1px 10px;border-radius:11px;transform:none !important}
      .rk-side:not(:hover) .rk-pin::before{display:none}
      .rk-side:not(:hover) .rk-pin:hover{background:color-mix(in srgb,#7C3AED 12%,transparent) !important}
      .rk-side:not(:hover) .rk-pin .pi{background:transparent !important;width:auto !important;height:auto !important}
      .rk-side:not(:hover) .rk-pin .pi .material-icons-outlined{color:#7C3AED !important;font-size:22px !important}
      .rk-side:not(:hover) .rk-pin .pt,
      .rk-side:not(:hover) .rk-pin .pok{display:none}
      .rk-side:not(:hover) .rk-pin.aktif{background:linear-gradient(135deg,#7C3AED,#4F46E5) !important;box-shadow:0 4px 12px rgba(124,58,237,.3) !important}
      .rk-side:not(:hover) .rk-pin.aktif .pi .material-icons-outlined{color:#fff !important}
    }
    /* ORTAK MODAL / OVERLAY — panel-kit.css'te yoktu, modal'lar akışta alta düşüyordu */
    .rk-modal-arka{position:fixed;inset:0;background:rgba(15,15,25,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px}
    .rk-modal-arka.acik{display:flex}
    .rk-modal{background:var(--card,#fff);border:1px solid var(--line,#e5e7eb);border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.35);width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
    .rk-modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line,#e5e7eb);font-weight:800;font-size:15px}
    .rk-modal-kapa{background:none;border:0;font-size:26px;line-height:1;color:var(--sub,#94a3b8);cursor:pointer;padding:0 4px}
    .rk-modal-govde{padding:16px 20px}
    .rk-modal-foot{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid var(--line,#e5e7eb)}
    /* ====== RK DIYALOG (alert/confirm/prompt yerine kendi popup'imiz) ====== */
    .rkd-arka{position:fixed;inset:0;background:rgba(10,15,30,.55);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;z-index:999999;padding:16px;opacity:0;animation:rkdFadeIn .18s ease forwards}
    .rkd-arka.kapaniyor{animation:rkdFadeOut .14s ease forwards}
    @keyframes rkdFadeIn{from{opacity:0}to{opacity:1}}
    @keyframes rkdFadeOut{from{opacity:1}to{opacity:0}}
    .rkd-kutu{background:var(--card,#fff);color:var(--ink,#1e293b);border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.35);width:100%;max-width:440px;overflow:hidden;transform:scale(.94);animation:rkdPop .22s cubic-bezier(.34,1.56,.64,1) forwards}
    @keyframes rkdPop{to{transform:scale(1)}}
    .rkd-ust{display:flex;align-items:center;gap:12px;padding:20px 22px 10px}
    .rkd-ikon{width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .rkd-ikon .material-icons-outlined{font-size:26px;color:#fff}
    .rkd-baslik{font-size:16px;font-weight:800;color:var(--ink,#1e293b);line-height:1.3}
    .rkd-mesaj{padding:4px 22px 18px;font-size:14px;color:var(--ink,#1e293b);line-height:1.5;word-break:break-word;white-space:pre-wrap}
    .rkd-input{display:block;width:calc(100% - 44px);margin:4px 22px 16px;padding:11px 13px;border:1.5px solid var(--line,#e2e8f0);border-radius:11px;font-size:14px;font-weight:600;background:var(--card,#fff);color:var(--ink,#1e293b);box-sizing:border-box}
    .rkd-input:focus{outline:none;border-color:#7C3AED;box-shadow:0 0 0 3px rgba(124,58,237,.15)}
    .rkd-foot{display:flex;gap:10px;justify-content:flex-end;padding:14px 22px 20px;flex-wrap:wrap}
    .rkd-btn{border:0;border-radius:11px;padding:10px 20px;font-weight:700;font-size:13.5px;cursor:pointer;transition:transform .12s,box-shadow .12s,background .12s;display:inline-flex;align-items:center;gap:6px;min-width:88px;justify-content:center}
    .rkd-btn:hover{transform:translateY(-1px)}
    .rkd-btn.iptal{background:var(--card2,#f1f5f9);color:var(--ink,#1e293b)}
    .rkd-btn.iptal:hover{background:var(--line,#e2e8f0)}
    .rkd-btn.ok{background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.3)}
    .rkd-btn.ok:hover{box-shadow:0 6px 16px rgba(124,58,237,.4)}
    .rkd-btn.tehlike{background:linear-gradient(135deg,#F43F5E,#E11D48);color:#fff;box-shadow:0 4px 12px rgba(244,63,94,.3)}
    .rkd-btn.tehlike:hover{box-shadow:0 6px 16px rgba(244,63,94,.4)}
    .rkd-i-ok{background:linear-gradient(135deg,#10B981,#059669)}
    .rkd-i-wn{background:linear-gradient(135deg,#F59E0B,#D97706)}
    .rkd-i-er{background:linear-gradient(135deg,#F43F5E,#E11D48)}
    .rkd-i-info{background:linear-gradient(135deg,#4F46E5,#7C3AED)}
    .rkd-i-sor{background:linear-gradient(135deg,#A855F7,#7C3AED)}
    /* Toast */
    .rkd-toast-pano{position:fixed;top:16px;right:16px;z-index:999998;display:flex;flex-direction:column;gap:10px;max-width:420px;pointer-events:none}
    .rkd-toast{background:var(--card,#fff);color:var(--ink,#1e293b);border-radius:12px;box-shadow:0 10px 28px rgba(0,0,0,.18);padding:12px 14px;display:flex;align-items:flex-start;gap:10px;border-left:4px solid #4F46E5;animation:rkdTIn .25s cubic-bezier(.34,1.56,.64,1);pointer-events:auto;font-size:13.5px;font-weight:600;line-height:1.4}
    .rkd-toast.cikis{animation:rkdTOut .2s ease forwards}
    .rkd-toast.ok{border-left-color:#10B981}
    .rkd-toast.wn{border-left-color:#F59E0B}
    .rkd-toast.er{border-left-color:#F43F5E}
    .rkd-toast .ti{font-size:19px;flex-shrink:0}
    .rkd-toast.ok .ti{color:#10B981}
    .rkd-toast.wn .ti{color:#F59E0B}
    .rkd-toast.er .ti{color:#F43F5E}
    .rkd-toast.info .ti{color:#4F46E5}
    @keyframes rkdTIn{from{transform:translateX(50px);opacity:0}to{transform:translateX(0);opacity:1}}
    @keyframes rkdTOut{to{transform:translateX(50px);opacity:0}}
    @media(max-width:560px){.rkd-toast-pano{top:auto;bottom:16px;left:16px;right:16px;max-width:none}@keyframes rkdTIn{from{transform:translateY(30px);opacity:0}to{transform:translateY(0);opacity:1}}}
    </style>
    @stack('head')
</head>
<body>
<script>if(document.documentElement.classList.contains('_koyu'))document.body.classList.add('koyu');</script>
@php
    // Gruplu menü — başlık => [[url, material-icon, etiket, renk(null=ink)], ...]
    $gruplar = [
        'Panel' => [
            ['/dashboard', 'dashboard', 'Özet', null],
            ['/pos', 'table_restaurant', 'Masalar', null],
            ['/mutfak', 'soup_kitchen', 'Mutfak', null],
            ['/rezervasyon', 'event_available', 'Rezervasyon', null],
            ['/paket', 'delivery_dining', 'Paket', null],
        ],
        'Satış & Servis' => [
            ['/isletme-duzeni', 'space_dashboard', 'İşletme Düzeni (Bölge/Masa/Garson/Kasa)', '#7C3AED'],
            ['/servis', 'room_service', 'Servis Ekranı (İş Akışım)', '#4F46E5'],
            ['/karsilama', 'co_present', 'Karşılama / Host (Bekleme + Oturt)', '#0EA5E9'],
            ['/garson-cagrilari', 'notifications_active', 'Garson Çağrıları', '#F43F5E'],
        ],
        'Departman Operasyon' => [
            ['/kalite', 'verified_user', 'Kalite / Gıda Güvenliği (HACCP)', '#16A34A'],
            ['/temizlik', 'cleaning_services', 'Temizlik / Hijyen', '#0EA5E9'],
            ['/guvenlik', 'security', 'Güvenlik', '#475569'],
            ['/teknik', 'build', 'Teknik Servis / Bakım', '#F97316'],
            ['/it-destek', 'dns', 'IT / Bilgi İşlem', '#6366F1'],
            ['/bulasik', 'wash', 'Bulaşıkhane / Steward', '#06B6D4'],
            ['/etkinlik', 'celebration', 'Satış / Etkinlik', '#A855F7'],
        ],
        'Menü & Ürünler' => [
            ['/menu', 'restaurant_menu', 'Menü Yönetimi', null],
            ['/recete', 'menu_book', 'Reçeteler (Yarı Mamül)', '#7C3AED'],
            ['/app-yonetim', 'palette', 'App Kartları / QR Menü', '#F6CE63'],
            ['/qr-menu-rengi', 'color_lens', 'QR Menü Rengi', '#EC4899'],
            ['/indirimler', 'local_offer', 'İndirimler', '#22C55E'],
            ['/barkod', 'qr_code', 'Barkod', null],
        ],
        'Stok & Tedarik' => [
            ['/satinalma-islemleri', 'apps', 'Satın Alma İşlemleri', '#7C3AED'],
            ['/satinalma-ozet', 'space_dashboard', 'Satın Alma Özeti', '#7C3AED'],
            ['/satinalma-talep', 'assignment', 'Satın Alma Talebi Oluştur', '#7C3AED'],
            ['/stok', 'inventory_2', 'Stok & Satın Alma', null],
            ['/siparis-onerisi', 'auto_awesome', 'Otomatik Sipariş Önerisi', '#7C3AED'],
            ['/satinalma-siparis', 'shopping_cart_checkout', 'Sipariş İşlemleri', '#4F46E5'],
            ['/mal-kabul', 'inventory', 'Mal Kabul / İrsaliye', '#10B981'],
            ['/fatura-eslestir', 'fact_check', 'Fatura Eşleştir (3-way)', '#F59E0B'],
            ['/fatura-ocr', 'document_scanner', 'Fatura OCR (AI)', '#7C3AED'],
            ['/parti-takip', 'event_busy', 'Parti / SKT Takip', '#F43F5E'],
            ['/fiyat-radari', 'radar', 'Fiyat Radarı · Gizli Zam', '#EC4899'],
            ['/hal-fiyatlari', 'storefront', 'Hal Fiyatları (sen vs hal)', '#059669'],
            ['/satinalma-analiz', 'leaderboard', 'Satınalma Analizi · Karne', '#4F46E5'],
            ['/tedarikci-karne', 'grading', 'Tedarikçi Karnesi', '#0EA5E9'],
            ['/satinalma-butce', 'savings', 'Satınalma Bütçe', '#10B981'],
            ['/satinalma-asistan', 'smart_toy', 'Satınalma AI Asistanı', '#7C3AED'],
            ['/satinalma-brief', 'wb_sunny', 'Satınalma Sabah Brifingi', '#7C3AED'],
            ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
            ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
            ['/tedarikci', 'local_shipping', 'Tedarikçiler', null],
            ['/eposta-hesaplari', 'alternate_email', 'E-posta Hesapları (RFQ)', '#7C3AED'],
            ['/gelen-teklifler', 'mark_email_read', 'Gelen Teklifler (AI)', '#10B981'],
        ],
        'Kasa & Finans' => [
            ['/kasa', 'account_balance', 'Kasa (Vardiya)', null],
            ['/finans', 'analytics', 'Finans / Kâr-Zarar', null],
            ['/gider', 'receipt_long', 'Giderler', null],
            ['/cari', 'account_balance_wallet', 'Cari / Açık Hesaplar', null],
            ['/muhasebe', 'account_balance', 'Muhasebe / Cari', null],
            ['/raporlar', 'bar_chart', 'Raporlar', null],
            ['/z-raporu', 'summarize', 'Z Raporu', null],
        ],
        'Personel' => [
            ['/personel', 'badge', 'Personel & Maaş', null],
            ['/puantaj', 'schedule', 'Puantaj', null],
            ['/garson-performans', 'emoji_events', 'Garson Performansı', '#10B981'],
        ],
        'Sadakat & Müşteri' => [
            ['/odul-dogrula', 'qr_code_scanner', 'Ödül Doğrula', '#22C55E'],
            ['/odul-raporu', 'emoji_events', 'Ödül Raporu', '#F6CE63'],
            ['/cark-yonetim', 'casino', 'Çarkıfelek', '#7C3AED'],
        ],
        'Ayarlar & Entegrasyon' => [
            ['/odeme-ayar', 'shield', 'Kaçak Önleme / Ödeme', '#F43F5E'],
            ['/whatsapp', 'chat', 'WhatsApp Yönetimi', '#22C55E'],
            ['/bagli-cihazlar', 'devices_other', 'Bağlı Cihazlar', '#0EA5E9'],
            ['/yazici-ayarlari', 'print', 'Yazıcı Ayarları', '#14B8A6'],
            ['/isletme-konum', 'my_location', 'İşletme Konum', null],
            ['/yasal-bilgiler', 'gavel', 'Yasal Bilgiler & Sözleşmeler', '#7C3AED'],
            ['/sebep-yonetimi', 'rule_folder', 'İptal / İkram Sebepleri', null],
            ['/hareketler', 'history', 'Hareketler (Log)', '#7C3AED'],
            ['/ai-eylem-log', 'verified_user', 'AI Eylem Günlüğü', '#7C3AED'],
        ],
    ];
    // DEPARTMAN + BİLDİRİM (additif, flag ile). gor_personel yetkisi olan departmanı görür; bildirim herkese açık.
    if (config('services.resteos.departman_acik', true)) {
        $gruplar['Personel'][] = ['/departman-yonetim', 'groups', 'Departman & Yetki', '#4F46E5'];
        $gruplar['Ayarlar & Entegrasyon'][] = ['/bildirim-ayar', 'notifications_active', 'Bildirim Ayarları', '#0EA5E9'];
    }
    // PROFİLİM — her roldeki personele görünür (kişisel bilgi + PIN + oturum). herkese açık (bildirim-ayar gibi).
    $gruplar['Ayarlar & Entegrasyon'][] = ['/profilim', 'account_circle', 'Profilim', '#7C3AED'];
    // İNSAN KAYNAKLARI — özet (grubun başına) + izin yönetimi. gor_personel yetkisi olana görünür.
    if (config('services.resteos.ik_acik', true)) {
        array_unshift($gruplar['Personel'], ['/ik-ozet', 'groups', 'İK — Özet', '#10B981'], ['/ik-asistan', 'smart_toy', 'İK AI Asistanı', '#7C3AED']);
        $gruplar['Personel'][] = ['/ik-vardiya', 'calendar_month', 'Vardiya Planlama', '#10B981'];
        $gruplar['Personel'][] = ['/ik-risk', 'crisis_alert', 'Risk Radarı', '#F97316'];
        $gruplar['Personel'][] = ['/ik-izin', 'event_available', 'İzin Yönetimi', '#10B981'];
        $gruplar['Personel'][] = ['/ik-aday', 'how_to_reg', 'İşe Alım / Aday Havuzu', '#10B981'];
    }
    // PATRON AI — sabah brifingi + entegrasyonlar (en üste, Panel grubunun başına). gor_copilot yetkisi olana görünür.
    $gruplar = ['Patron AI' => [
        ['/patron-ai', 'auto_awesome', 'Sabah Brifingi', '#7C3AED'],
    ]] + $gruplar;

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
        if (isset($gruplar['Stok & Tedarik'])) {
            $gruplar['Stok & Tedarik'] = array_values(array_filter($gruplar['Stok & Tedarik'], function ($it) use (&$_ozetI, &$_aiI, &$_stokI) {
                if ($it[0] === '/satinalma-ozet')    { $_ozetI = $it; return false; }
                if ($it[0] === '/satinalma-asistan') { $_aiI  = $it; return false; }
                if ($it[0] === '/stok')              { $_stokI = $it; return false; }
                return true;
            }));
        }
        // Etiketleri sadeleştir
        if ($_ozetI) $_ozetI[2] = 'Özet';
        if ($_aiI)   $_aiI[2]   = 'AI Asistan';
        if ($_stokI) $_stokI[2] = 'Stok & Satın Alma';

        $_yeni = [];
        $_pin = [];
        if ($_ozetI) $_pin[] = $_ozetI;
        if ($_aiI)   $_pin[] = $_aiI;
        if ($_stokI) $_pin[] = $_stokI;
        if ($_pin) $_yeni['__PIN__'] = $_pin;

        if (isset($gruplar['Stok & Tedarik']) && count($gruplar['Stok & Tedarik']) > 0) {
            $_yeni['İşlemler'] = $gruplar['Stok & Tedarik'];
        }
        unset($gruplar['Stok & Tedarik']);

        $_dept = $gruplar['Departman Operasyon'] ?? null;
        unset($gruplar['Departman Operasyon']);
        foreach ($gruplar as $_gad => $_items) { $_yeni[$_gad] = $_items; }
        if ($_dept) $_yeni['Departman Operasyon'] = $_dept;

        $gruplar = $_yeni;
    }

    $path = '/' . trim(request()->path(), '/');
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
@endphp
<div class="rk-wrap">
    <div class="rk-side-backdrop" id="rkSideBackdrop" onclick="rkSideTog(false)"></div>
    <aside class="rk-side" id="rkSide">
        <a href="/profilim" class="rk-side-head" style="text-decoration:none; color:inherit; display:block" title="Profilim">
            <div class="ad">🍔 ResteOS</div>
            <div class="sube">{{ $aktifSubeAd }}</div>
            <div class="kul">{{ $kulAd }} · {{ $kulRol }}</div>
        </a>
        <nav class="rk-menu">
            @foreach ($gruplar as $grupAd => $items)
                @php
                    $gorunur = array_values(array_filter($items, function ($it) use ($_pp, $_harita) {
                        if (!$_pp) return true;
                        if (in_array(($_pp->rol ?? ''), ['sahip', 'mudur'])) return true;     // yöneticiler her şeyi görür
                        if (in_array($it[0], ['/bildirim-ayar', '/profilim'])) return true;    // herkese açık (kişisel ayarlar)
                        if (isset($_harita[$it[0]])) return _panelYetkiVar($_pp, $_harita[$it[0]]); // haritadaki = yetkiye göre
                        return false;                                                          // haritasız = kısıtlı role gizle (sızma önleme)
                    }));
                    $grupAktif = collect($gorunur)->contains(fn ($it) => $it[0] === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $it[0]));
                @endphp
                @if ($grupAd === '__PIN__' && count($gorunur))
                    {{-- SATIN ALMACI: üstte 3 standalone büyük link (grup değil) --}}
                    <div class="rk-pin-pano">
                        @foreach ($gorunur as [$url, $ikon, $ad, $renk])
                            @php
                                $aktif = ($url === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $url));
                                $badge = $_menuBadge[$url] ?? 0;
                            @endphp
                            <a href="{{ $url }}" class="rk-pin{{ $aktif ? ' aktif' : '' }}">
                                <span class="pi">
                                    <span class="material-icons-outlined">{{ $ikon }}</span>
                                    @if ($badge > 0)<span class="rk-badge{{ $badge > 99 ? ' cok' : '' }}">{{ $_badgeMetin($badge) }}</span>@endif
                                </span>
                                <span class="pt">{{ $ad }}</span>
                                <span class="material-icons-outlined pok">chevron_right</span>
                            </a>
                        @endforeach
                    </div>
                @elseif (count($gorunur))
                    <div class="rk-grp{{ $grupAktif ? '' : ' kapali' }}" data-grp="{{ $grupAd }}" onclick="rkGrpTog(this)">{{ $grupAd }}<span class="material-icons-outlined rk-grp-ok">expand_more</span></div>
                    <div class="rk-grp-body">
                        @foreach ($gorunur as [$url, $ikon, $ad, $renk])
                            @php
                                $aktif = ($url === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $url));
                                $badge = $_menuBadge[$url] ?? 0;
                            @endphp
                            <a href="{{ $url }}" class="{{ $aktif ? 'aktif' : '' }}">
                                <span class="rk-ikon-wrap">
                                    <span class="material-icons-outlined">{{ $ikon }}</span>
                                    @if ($badge > 0)<span class="rk-badge{{ $badge > 99 ? ' cok' : '' }}">{{ $_badgeMetin($badge) }}</span>@endif
                                </span>
                                {{ $ad }}
                            </a>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="rk-side-foot">
            <a href="/pos-cikis"><span class="material-icons-outlined">logout</span> Çıkış</a>
        </div>
    </aside>
    <main class="rk-main">
        <div class="rk-header">
            <button class="rk-hamburger" onclick="rkSideTog()" aria-label="Menü"><span class="material-icons-outlined">menu</span></button>
            @hasSection('rk_geri')
                <a href="@yield('rk_geri')" class="rk-geri" title="Geri"><span class="material-icons-outlined">arrow_back</span><span class="rk-geri-metin">Geri</span></a>
            @endif
            <div class="ikon"><span class="material-icons-outlined">@yield('rk_ikon', 'dashboard')</span></div>
            <div>
                <div class="baslik">@yield('baslik', 'Panel')</div>
                @hasSection('rk_alt')<div class="alt">@yield('rk_alt')</div>@endif
            </div>
            <div class="sp"></div>
            @yield('rk_araclar')
            <button class="rk-tema" onclick="rkTema()" title="Gece / Gündüz"><span class="material-icons-outlined" id="rkTemaIk">dark_mode</span></button>
        </div>
        <div class="rk-body">
            @yield('content')
        </div>
    </main>
</div>
<script>
    window.csrf = document.querySelector('meta[name=csrf-token]').content;
    function rkTema(){
        var k = document.body.classList.toggle('koyu');
        try{ localStorage.setItem('app_koyu', k ? '1':'0'); }catch(e){}
        var ik = document.getElementById('rkTemaIk'); if(ik) ik.textContent = k ? 'light_mode' : 'dark_mode';
    }
    (function(){ var ik=document.getElementById('rkTemaIk'); if(ik && document.body.classList.contains('koyu')) ik.textContent='light_mode'; })();
    function rkSideTog(ac){
        var s = document.getElementById('rkSide'); var b = document.getElementById('rkSideBackdrop'); if(!s||!b) return;
        var acik = (ac === true) ? true : (ac === false ? false : !s.classList.contains('acik'));
        s.classList.toggle('acik', acik); b.classList.toggle('acik', acik);
    }
    // Menuden bir sayfaya tiklanirsa drawer'i kapat (mobilde)
    (function(){ var m=document.querySelector('.rk-side .rk-menu'); if(m) m.addEventListener('click', function(e){ if(e.target.closest('a') && window.innerWidth <= 900) rkSideTog(false); }); })();
    // ESC tuslariyla kapat
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') rkSideTog(false); });
    async function api(url, data = null) {
        const opt = { headers: { 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json' } };
        if (data) {
            try { var g = localStorage.getItem('pos_garson_id'); if (g && data.garson_id === undefined) data.garson_id = g; } catch (e) {}
            opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(data);
        }
        const r = await fetch(url, opt);
        return r.json();
    }
    function rkGrpTog(el){
        el.classList.toggle('kapali');
        try{
            var a = JSON.parse(localStorage.getItem('rk_grp_acik')||'[]');
            var ad = el.getAttribute('data-grp'); var i = a.indexOf(ad);
            if(el.classList.contains('kapali')){ if(i>=0) a.splice(i,1); } // kapandi
            else if(i<0){ a.push(ad); } // acildi
            localStorage.setItem('rk_grp_acik', JSON.stringify(a));
        }catch(e){}
    }
    (function(){ try{ // varsayilan KAPALI; daha once elle acilan gruplari geri ac (aktif grup zaten acik)
        var a = JSON.parse(localStorage.getItem('rk_grp_acik')||'[]');
        document.querySelectorAll('.rk-grp').forEach(function(h){
            if(a.indexOf(h.getAttribute('data-grp'))>=0){ h.classList.remove('kapali'); }
        });
    }catch(e){} })();
    // Sol menu kaydirma konumunu koru (sayfa degisince en uste / dashboard'a ziplamasin)
    (function(){
        var menu = document.querySelector('.rk-menu'); if(!menu) return;
        try{ var s = sessionStorage.getItem('rk_menu_scroll'); if(s!==null) menu.scrollTop = parseInt(s,10)||0; }catch(e){}
        var t; function kaydet(){ try{ sessionStorage.setItem('rk_menu_scroll', menu.scrollTop); }catch(e){} }
        menu.addEventListener('scroll', function(){ clearTimeout(t); t=setTimeout(kaydet, 80); }, {passive:true});
        menu.addEventListener('click', kaydet); // link tiklaninca reload oncesi kaydet
    })();
    function para(v) { return new Intl.NumberFormat('tr-TR').format(Math.round(v)) + ' ₺'; }
    if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {})); }
</script>
{{-- ORTAK ONAY POPUP — native confirm() yerine sistem içi şık onay (rkOnay / rkOnayForm) --}}
<div id="rkOnayModal" class="rk-modal-arka" onclick="if(event.target===this)rkOnayKapat()">
  <div class="rk-modal" style="max-width:400px">
    <div class="rk-modal-govde" style="padding:24px 22px 6px;text-align:center">
      <div id="rkOnayIco" style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
        <span class="material-icons-outlined" style="color:#fff;font-size:28px">help_outline</span>
      </div>
      <div id="rkOnayMsg" style="font-size:14.5px;line-height:1.55;color:var(--ink)"></div>
    </div>
    <div class="rk-modal-foot" style="justify-content:center;gap:10px;padding:16px 22px 20px">
      <button type="button" class="rk-btn bos" onclick="rkOnayKapat()">Vazgeç</button>
      <button type="button" class="rk-btn" id="rkOnayOk" style="background:var(--mor1)">Onayla</button>
    </div>
  </div>
</div>
<script>
  var _rkOnayCb = null, _rkOnayIptalCb = null;
  function rkOnay(mesaj, cb, renk, okLabel, iptalCb){
    _rkOnayCb = cb || null;
    _rkOnayIptalCb = iptalCb || null;
    document.getElementById('rkOnayMsg').textContent = mesaj;
    var b = document.getElementById('rkOnayOk');
    b.style.background = renk || 'var(--mor1)';
    b.textContent = okLabel || 'Onayla';
    try { document.getElementById('rkOnayIco').style.background = (renk && renk.indexOf('kirmizi')>=0) ? 'var(--kirmizi)' : 'linear-gradient(135deg,var(--mor1),var(--mavi))'; } catch(e){}
    document.getElementById('rkOnayModal').classList.add('acik');
    return false;
  }
  function rkOnayForm(form, mesaj, renk, okLabel){
    return rkOnay(mesaj, function(){ HTMLFormElement.prototype.submit.call(form); }, renk, okLabel);
  }
  // Promise sürümü: const ok = await rkOnayP('mesaj', 'var(--kirmizi)', 'Sil'); if (!ok) return;
  window.rkOnayP = function(mesaj, renk, okLabel){
    return new Promise(function(res){ rkOnay(mesaj, function(){ res(true); }, renk, okLabel, function(){ res(false); }); });
  };
  function rkOnayKapat(){
    var ic = _rkOnayIptalCb;
    document.getElementById('rkOnayModal').classList.remove('acik');
    _rkOnayCb = null; _rkOnayIptalCb = null;
    if (ic) try { ic(); } catch(e){}
  }
  document.getElementById('rkOnayOk').addEventListener('click', function(){ var cb = _rkOnayCb; _rkOnayCb = null; _rkOnayIptalCb = null; document.getElementById('rkOnayModal').classList.remove('acik'); if (cb) cb(); });
</script>

{{-- ====== RK DİYALOG: toast (rkBilgi) + prompt (rkIste) + alert/prompt override ====== --}}
<div id="rkdToastPano" class="rkd-toast-pano" aria-live="polite"></div>
<script>
  (function(){
    // ---- Toast (alert replacement) ----
    var _rkdIcons = { ok:'check_circle', wn:'warning', er:'error', info:'info' };
    window.rkBilgi = function(mesaj, tip, sure){
      tip = tip || 'info'; sure = sure || (tip==='er' ? 5500 : 3200);
      var p = document.getElementById('rkdToastPano'); if(!p) return;
      var t = document.createElement('div'); t.className = 'rkd-toast '+tip;
      t.innerHTML = '<span class="material-icons-outlined ti">'+(_rkdIcons[tip]||'info')+'</span><span>'+String(mesaj).replace(/</g,'&lt;').replace(/\n/g,'<br>')+'</span>';
      p.appendChild(t);
      t.addEventListener('click', function(){ kapat(); });
      var zt = setTimeout(kapat, sure);
      function kapat(){ clearTimeout(zt); if(!t.parentNode) return; t.classList.add('cikis'); setTimeout(function(){ try{ p.removeChild(t); }catch(e){} }, 220); }
    };
    // alert → toast (otomatik tip algıla)
    var _oriAlert = window.alert;
    window.alert = function(m){
      var s = String(m==null?'':m);
      var tip = /hata|başarısız|basarisiz|olmadı|olmadi|yok\b|eksik|hatalı|hatali|geçersiz|gecersiz/i.test(s) ? 'er'
              : /dikkat|uyarı|uyari|zaten|önce|once/i.test(s) ? 'wn'
              : /oldu|başarı|basari|güncellendi|guncellendi|silindi|kaydedildi|eklendi|tamamlandı|tamamlandi/i.test(s) ? 'ok'
              : 'info';
      try { window.rkBilgi(s, tip); } catch(e){ _oriAlert(m); }
    };

    // ---- Prompt modal (callback) ----
    var _rkdIsteKutu = null;
    window.rkIste = function(mesaj, cb, opts){
      opts = opts || {};
      if (_rkdIsteKutu) return; // bir seferde tek
      var ark = document.createElement('div'); ark.className='rkd-arka';
      ark.innerHTML = '<div class="rkd-kutu" role="dialog" aria-modal="true">'
        + '<div class="rkd-ust"><div class="rkd-ikon rkd-i-sor"><span class="material-icons-outlined">edit</span></div><div class="rkd-baslik">'+(opts.baslik||'Bilgi gir')+'</div></div>'
        + '<div class="rkd-mesaj">'+String(mesaj||'').replace(/</g,'&lt;')+'</div>'
        + '<input class="rkd-input" type="'+(opts.tip||'text')+'" placeholder="'+(opts.placeholder||'')+'" value="'+(opts.varsayilan||'').toString().replace(/"/g,'&quot;')+'">'
        + '<div class="rkd-foot"><button type="button" class="rkd-btn iptal">'+(opts.iptal||'İptal')+'</button><button type="button" class="rkd-btn ok">'+(opts.ok||'Tamam')+'</button></div>'
        + '</div>';
      document.body.appendChild(ark);
      _rkdIsteKutu = ark;
      var inp = ark.querySelector('.rkd-input');
      var btnOk = ark.querySelector('.rkd-btn.ok');
      var btnIp = ark.querySelector('.rkd-btn.iptal');
      setTimeout(function(){ inp.focus(); inp.select(); }, 60);
      function kapat(deger){
        ark.classList.add('kapaniyor');
        setTimeout(function(){ try{ document.body.removeChild(ark); }catch(e){} _rkdIsteKutu=null; }, 150);
        if (cb) cb(deger);
      }
      btnOk.onclick = function(){ kapat(inp.value); };
      btnIp.onclick = function(){ kapat(null); };
      ark.addEventListener('click', function(e){ if (e.target===ark) kapat(null); });
      inp.addEventListener('keydown', function(e){ if (e.key==='Enter'){ e.preventDefault(); kapat(inp.value); } if (e.key==='Escape') kapat(null); });
    };

    // ---- Onay modal yeni API (varyant) — rkOnay zaten var, bu da alternatif ----
    // Tehlike + özelleştirilebilir ikon/metin isteyenler için (kirmizi tema otomatik)
    window.rkSor = function(mesaj, cb, opts){
      opts = opts || {};
      var tehlike = !!opts.tehlike;
      return window.rkOnay(mesaj, cb, tehlike ? 'var(--kirmizi)' : (opts.renk||null), opts.ok || (tehlike?'Sil':'Onayla'));
    };

    // ---- window.prompt override (kullanıcılara uyarı — sync değildir) ----
    var _oriPrompt = window.prompt;
    window.prompt = function(mesaj, varsayilan){
      // Sync beklentisi olan eski kodu kırmamak için konsola uyar + modal göster (geri değer dönmez)
      try { console.warn('[rkDiyalog] prompt() yerine rkIste(mesaj, cb) kullanın. Çağrı:', mesaj); } catch(e){}
      window.rkIste(mesaj, function(){}, { varsayilan: varsayilan||'' });
      return null;
    };
  })();
</script>
@stack('scripts')
</body>
</html>

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
    <link rel="stylesheet" href="/panel-kit.css?v=14">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>try{if(localStorage.getItem('app_koyu')==='1')document.documentElement.classList.add('_koyu');}catch(e){}</script>
    <style>
    html._koyu body{}
    .rk-grp{display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;
      font-size:12px;font-weight:600;letter-spacing:.6px;text-transform:uppercase;opacity:1;color:#475569;
      padding:15px 16px 9px;margin-top:6px;border-top:1px solid rgba(120,120,140,.18)}
    body.koyu .rk-grp{color:#e2e8f0}
    .rk-grp:first-of-type{border-top:0;margin-top:0}
    .rk-grp::before{content:'';display:inline-block;width:3px;height:12px;background:linear-gradient(180deg,#7C3AED,#4F46E5);border-radius:2px;margin-right:8px;vertical-align:-1px}
    .rk-grp:hover{opacity:1;color:#7C3AED}
    body.koyu .rk-grp:hover{color:#a78bfa}
    .rk-grp .rk-grp-ok{font-size:19px;transition:transform .2s ease;opacity:.6}
    .rk-grp.kapali .rk-grp-ok{transform:rotate(-90deg)}
    .rk-grp.kapali + .rk-grp-body{display:none}
    /* ÜST BAŞLIK (kategori) — açılır bölüm başlığı */
    .rk-cat{display:flex;align-items:center;gap:9px;cursor:pointer;user-select:none;padding:12px 16px;margin-top:8px;font-size:11.5px;font-weight:600;letter-spacing:.7px;text-transform:uppercase;color:var(--mor1,#7C3AED);background:color-mix(in srgb,var(--mor1,#7C3AED) 9%,transparent);border-top:1px solid var(--line)}
    .rk-cat:first-of-type{margin-top:2px}
    .rk-cat .rk-cat-ik{font-size:17px}
    .rk-cat .rk-cat-ad{flex:1}
    .rk-cat .rk-cat-ok{font-size:18px;transition:transform .2s ease;opacity:.6}
    .rk-cat.kapali .rk-cat-ok{transform:rotate(-90deg)}
    .rk-cat.kapali + .rk-cat-body{display:none}
    body.koyu .rk-cat{color:#a78bfa}
    .rk-cat-body .rk-grp{padding-left:22px}
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
      .rk-side:not(:hover):not(.genis).rk-pin-pano{padding:8px 0 10px;margin-bottom:4px;gap:2px;border-bottom:1px solid rgba(120,120,140,.14)}
      .rk-side:not(:hover):not(.genis).rk-pin{background:transparent !important;border:0 !important;box-shadow:none !important;padding:0 !important;justify-content:center;height:44px;margin:1px 10px;border-radius:11px;transform:none !important}
      .rk-side:not(:hover):not(.genis).rk-pin::before{display:none}
      .rk-side:not(:hover):not(.genis).rk-pin:hover{background:color-mix(in srgb,#7C3AED 12%,transparent) !important}
      .rk-side:not(:hover):not(.genis).rk-pin .pi{background:transparent !important;width:auto !important;height:auto !important}
      .rk-side:not(:hover):not(.genis).rk-pin .pi .material-icons-outlined{color:#7C3AED !important;font-size:22px !important}
      .rk-side:not(:hover):not(.genis).rk-pin .pt,
      .rk-side:not(:hover):not(.genis).rk-pin .pok{display:none}
      .rk-side:not(:hover):not(.genis).rk-pin.aktif{background:linear-gradient(135deg,#7C3AED,#4F46E5) !important;box-shadow:0 4px 12px rgba(124,58,237,.3) !important}
      .rk-side:not(:hover):not(.genis).rk-pin.aktif .pi .material-icons-outlined{color:#fff !important}
    }
    /* Sağ üst profil avatarı */
    .rk-profil{flex:none;width:40px;height:40px;border-radius:50%;padding:2px;background:linear-gradient(135deg,var(--mor1),var(--mavi));text-decoration:none;transition:transform .12s,box-shadow .15s}
    .rk-profil:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(124,58,237,.35)}
    .rk-profil.aktif{box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 25%,transparent)}
    .rk-profil-ic{width:100%;height:100%;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,var(--card));color:var(--mor1)}
    .rk-profil-ic img{width:100%;height:100%;object-fit:cover}
    .rk-profil-ic .material-icons-outlined{font-size:23px}
    @media(min-width:641px){.rk-profil{display:none}} /* masaüstünde profil menünün altında; telefonda menü kapalı olduğu için üstte kalır */
    /* Menü altı: profil kartı + Çıkış Yap */
    .rk-side-foot{display:flex;flex-direction:column;gap:8px;padding:10px}
    .rk-side-foot a{border:1px solid var(--line);border-radius:14px;text-decoration:none;transition:background .12s,border-color .12s}
    .rk-side-foot a:hover{background:color-mix(in srgb,var(--mor1) 6%,var(--card))}
    .rk-side-foot a.rk-sp{display:flex;align-items:center;gap:10px;height:auto;padding:8px 10px;color:var(--ink)}
    .rk-side-foot a.rk-sp.aktif{border-color:color-mix(in srgb,var(--mor1) 45%,var(--line));background:color-mix(in srgb,var(--mor1) 8%,var(--card))}
    .rk-sp-foto{flex:none;width:40px;height:40px;border-radius:50%;overflow:hidden;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;font-size:16px;font-weight:800}
    .rk-sp-foto img{width:100%;height:100%;object-fit:cover}
    .rk-sp-ad{flex:1;min-width:0;display:flex;flex-direction:column;line-height:1.25}
    .rk-sp-ad b{font-size:14px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .rk-sp-ad small{font-size:12px;font-weight:600;color:var(--sub)}
    .rk-sp-ok{flex:none;font-size:20px;color:var(--sub)}
    .rk-side-foot a.rk-sp-cikis{padding:11px 14px;font-weight:500}
    /* Menü yazıları yumuşak: bağlantı normal kalınlık, seçili sayfa hafif kalın (panel-kit 600/700 idi) */
    .rk-menu a{font-weight:400}
    .rk-menu a.aktif{font-weight:600}
    .rk-pin .pt{font-weight:500}
    @media(min-width:641px){
        .rk-side:not(:hover):not(.genis) .rk-side-foot{padding:10px 8px}
        .rk-side:not(:hover):not(.genis) .rk-side-foot a{border-color:transparent;justify-content:center;padding-left:0;padding-right:0}
        .rk-side:not(:hover):not(.genis) .rk-sp-ad,.rk-side:not(:hover):not(.genis) .rk-sp-ok{display:none}
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
            ['/satinalma-islemleri', 'apps', 'Satın Alma İşlemleri', '#7C3AED'],
            ['/satinalma-talep', 'assignment', 'Satın Alma Talebi Oluştur', '#7C3AED'],
            ['/talep-onaylarim', 'fact_check', 'Onay Bekleyen Talepler', '#F59E0B'],
            ['/reddedilen-talepler', 'block', 'Reddedilen Talepler', '#F43F5E'],
            // Üretim planı / özel gün listesini mutfak şefi hazırlar → Mutfak / BOH grubunda
            ['/stok', 'inventory_2', 'Stok & Satın Alma', null],
            ['/siparis-onerisi', 'auto_awesome', 'Otomatik Sipariş Önerisi', '#7C3AED'],
            ['/satinalma-siparis', 'shopping_cart_checkout', 'Sipariş İşlemleri', '#4F46E5'],
            ['/mal-kabul', 'inventory', 'Mal Kabul / İrsaliye', '#10B981'],
            ['/fatura-eslestir', 'fact_check', 'Fatura Eşleştir (3-way)', '#F59E0B'],
            ['/fatura-ocr', 'document_scanner', 'Fatura OCR (AI)', '#7C3AED'],
            ['/fiyat-radari', 'radar', 'Fiyat Radarı · Gizli Zam', '#EC4899'],
            ['/hal-fiyatlari', 'storefront', 'Hal Fiyatları (sen vs hal)', '#059669'],
            ['/satinalma-analiz', 'leaderboard', 'Satınalma Analizi · Karne', '#4F46E5'],
            ['/tedarikci-karne', 'grading', 'Tedarikçi Karnesi', '#0EA5E9'],
            ['/mustahsil', 'receipt_long', 'Müstahsil Makbuzu', '#0EA5E9'],
            ['/fiyat-anlasmalari', 'handshake', 'Fiyat Anlaşmaları', '#10B981'],
            ['/satinalma-onay-seviye', 'approval', 'Onay Seviyeleri', '#8B5CF6'],
            ['/satinalma-butce', 'savings', 'Satınalma Bütçe', '#10B981'],
            ['/satinalma-asistan', 'smart_toy', 'Satınalma AI Asistanı', '#7C3AED'],
            ['/departman-brief?dep=satinalma', 'wb_sunny', 'Satınalma Sabah Brifingi', '#7C3AED'],
            ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
            ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
            ['/tedarikci', 'local_shipping', 'Tedarikçiler', null],
            ['/eposta-hesaplari', 'alternate_email', 'E-posta Hesapları (RFQ)', '#7C3AED'],
            ['/gelen-teklifler', 'mark_email_read', 'Gelen Teklifler (AI)', '#10B981'],
        ],
        'İnsan Kaynakları' => [
            ['/personel', 'badge', 'Personel & Maaş', null],
            ['/puantaj', 'schedule', 'Puantaj', null],
            ['/garson-performans', 'emoji_events', 'Garson Performansı', '#10B981'],
        ],
        'Ayarlar & Entegrasyon' => [
            ['/isletme-duzeni', 'space_dashboard', 'İşletme Düzeni (Bölge/Masa/Garson/Kasa)', '#7C3AED'],
            ['/salon-sema', 'map', 'Salon Şema', '#0EA5E9'],
            ['/odeme-ayar', 'shield', 'Kaçak Önleme / Ödeme', '#F43F5E'],
            ['/whatsapp', 'chat', 'WhatsApp Yönetimi', '#22C55E'],
            ['/wa-hatlar', 'forum', 'WhatsApp Hatları (çoklu no)', '#25D366'],
            ['/isletme-konum', 'my_location', 'İşletme Konum', null],
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
        ['/demirbas', 'chair', 'Demirbaş / Ekipman', '#64748B'],
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
        ['/memnuniyet', 'sentiment_satisfied', 'Memnuniyet / Şikayet', '#10B981'],
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
        ['/it-destek?tip=ariza', 'build', 'Arıza / Destek Defteri', '#F97316'],
        ['/it-destek?tip=lisans', 'verified', 'Lisans / SSL / Yedek', '#4F46E5'],
        ['/it-destek?tip=kvkk', 'policy', 'KVKK / Veri Güvenliği', '#14B8A6'],
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
    // ===== AI SANTRAL — telefonu açan asistan: kayıtlar, arayan tanıma, aktarma, eğitim (gor_santral) =====
    $gruplar['AI Santral'] = [
        ['/santral-kayitlar', 'call', 'Çağrı Kayıtları', '#4F46E5'],
        ['/cagrilar', 'contact_phone', 'Çağrı Merkezi (Arayan Tanıma)', '#0EA5E9'],
        ['/cagri-ekran', 'phone_in_talk', 'Gelen Çağrı Ekranı', '#10B981'],
        ['/santral-ayar', 'phone_forwarded', 'Aktarma & Teslimat Ayarları', '#F59E0B'],
        ['/santral-egitim', 'school', 'AI Santral Eğitimi', '#7C3AED'],
    ];
    // PATRON AI — sabah brifingi + Genel Müdür + ajanda (en üste). gor_copilot yetkisi olana görünür.
    $gruplar = ['Patron AI' => [
        ['/patron-ai', 'auto_awesome', 'Sabah Brifingi', '#7C3AED'],
        ['/patron-ai?gm=1', 'manage_accounts', 'Genel Müdür', '#4F46E5'],
    ], 'Ajanda' => [
        // Herkese açık kişisel ajanda — her personel kendi randevu/hatırlatıcısını girer (veri personel_id bazlı)
        ['/ajanda', 'event_note', 'Ajanda & Hatırlatıcı', '#4F46E5'],
    ]] + $gruplar;
    // İŞLETME BÜYÜKLÜĞÜ: küçük/orta işletmede menü sadeleşir; kurulu olmayan alt departman hiçbir ölçekte görünmez.
    $gruplar['Ayarlar & Entegrasyon'][] = ['/isletme-olcegi', 'tune', 'İşletme Büyüklüğü', '#7C3AED'];
    $_olcekSor = false;
    try {
        if (function_exists('_menuOlcekSuz')) {
            $_osid = _webSubeId(); $_orol = optional(_posPersonel())->rol ?? '';
            $gruplar = _menuOlcekSuz($gruplar, $_osid, $_orol);
            $_od = _isletmeOlcek($_osid);
            $_olcekSor = $_od['olcek'] && !$_od['secildi'] && in_array($_orol, ['sahip', 'mudur']);
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

    // ===== YÖNETİM — her departman KENDİ akordiyon grubu olarak en üste alınır (başlığa tıkla → sayfaları açılır; ilk sırada brifing). Sadece sahip/müdür için öne çıkar. =====
    try {
        $_yp = function_exists('_posPersonel') ? _posPersonel() : null;
        if ($_yp && in_array(($_yp->rol ?? ''), ['sahip', 'mudur'])) {
            $_deptSira = ['Mutfak / BOH', 'İnsan Kaynakları', 'Finans / Muhasebe', 'Satın Alma', 'Pazarlama', 'Rezervasyon Satış', 'AI Santral', 'CRM / Müşteri İlişkileri', 'Kalite / HACCP', 'IT / Bilgi İşlem'];
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
        if ($_stokI) $_bas[] = $_stokI;
        if ($_aiI)   $_bas[] = $_aiI;
        $_sa = array_merge($_bas, $gruplar['Satın Alma'] ?? []);
        unset($gruplar['Satın Alma'], $gruplar['Mutfak / BOH'], $gruplar['Kalite / HACCP']); // kalite mutfağın işi; satın almanın kontrolü Mal Kabul'de
        $_yeni = isset($gruplar['Brifing']) ? ['Brifing' => $gruplar['Brifing']] : [];
        unset($gruplar['Brifing']);
        // İşe göre 4 kısa grup: günlük akış / fatura-fiyat / tedarikçi-analiz / stok-üretim (listede olmayan günlük akışa)
        $_saBol = ['Fatura & Fiyat' => ['/fatura-eslestir', '/fatura-ocr', '/fiyat-radari', '/hal-fiyatlari', '/fiyat-anlasmalari', '/mustahsil'],
            'Tedarikçi & Analiz' => ['/tedarikci', '/tedarikci-karne', '/satinalma-analiz', '/satinalma-butce', '/satinalma-onay-seviye', '/eposta-hesaplari'],
            'Stok & Üretim' => ['/uretim-plani', '/uretim-riski', '/sayim']];
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
@endphp
<div class="rk-wrap">
    <div class="rk-side-backdrop" id="rkSideBackdrop" onclick="rkSideTog(false)"></div>
    <aside class="rk-side" id="rkSide">
        <a href="/profilim" class="rk-side-head" style="text-decoration:none; color:inherit; display:block" title="Profilim">
            <div class="ad">🍔 ResteOS</div>
            <div class="sube">{{ $aktifSubeAd }}</div>
        </a>
        <nav class="rk-menu">
            @php
                // ÜST BAŞLIK (kategori) yapısı — gruplar bölümlere ayrılır; kategorisiz gruplar (Patron AI, __PIN__) en üstte standalone kalır
                $_kategoriler = [
                    'YÖNETİM' => ['Mutfak / BOH', 'İnsan Kaynakları', 'Finans / Muhasebe', 'Satın Alma', 'Pazarlama', 'Rezervasyon Satış', 'AI Santral', 'CRM / Müşteri İlişkileri', 'Kalite / HACCP', 'IT / Bilgi İşlem'],
                    'OPERASYON' => ['Satış & Servis', 'Departman Operasyon', 'Menü & Ürünler'],
                    'AYARLAR' => ['Ayarlar & Entegrasyon'],
                ];
                $_katIkon = ['YÖNETİM' => 'workspace_premium', 'OPERASYON' => 'tune', 'AYARLAR' => 'settings'];
                $_grupKat = [];
                foreach ($_kategoriler as $_kk => $_gs) foreach ($_gs as $_g) $_grupKat[$_g] = $_kk;
                // Mutfak rolüne yetkisiz ekran gösteren istisna kalktı: her bağlantı kendi yetkisine bakar (aşçı satın almayı/sayımı görmez)
                $_itemGorunur = function ($it) use ($_pp, $_harita) {
                    if (!$_pp) return true;
                    if (in_array(($_pp->rol ?? ''), ['sahip', 'mudur'])) return true;
                    if (\Illuminate\Support\Str::startsWith($it[0], '/departman-brief')) return _departmanBriefLinkAcik($_pp, $it[0]);
                    if ($it[0] === '/wa-hatlar') return _waHatGorur($_pp);
                    if (($_ai = _aiAsistanLinkAcik($_pp, $it[0])) !== null) return $_ai;
                    if ($it[0] === '/talep-onaylarim') return _talepUstYonetimMu($_pp); // sorumlu onayları Satın Alma Talepleri sayfasında yönetir
                    if (in_array($it[0], ['/bildirim-ayar', '/profilim', '/ajanda'])) return true;
                    if (isset($_harita[$it[0]])) return _panelYetkiVar($_pp, $_harita[$it[0]]);
                    return false;
                };
                $_grupGorunur = function ($items) use ($_itemGorunur) { foreach ($items as $it) if ($_itemGorunur($it)) return true; return false; };
            @endphp
            @php
                // En dar personel (yönetici/sorumlu değil, kademe Personel/Mutfak): kategori ve grup başlığı yok, ilgili sayfalar düz liste
                $_kadM = $_pp ? ($_pp->kademe ?? _rolKademe($_pp->rol ?? '')) : null;
                // Departman çalışanı (yönetici/satın almacı değil): kategori başlıkları (YÖNETİM, OPERASYON) gösterilmez
                $_satinalmaciMenu = $_pp && in_array($_pp->rol ?? '', ['satinalmaci', 'satinalma'], true);
                $_katsiz = $_satinalmaciMenu || ($_pp && !empty($_pp->departman_id) && !$_kasiyerMenu && !in_array($_pp->rol ?? '', ['sahip', 'mudur'], true) && !in_array($_kadM, ['sahip', 'supervizor', 'satinalma'], true));
                if ($_satinalmaciMenu) { $_kategoriler['YÖNETİM'] = array_values(array_unique(array_merge(['Satın Alma', 'Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim'], $_kategoriler['YÖNETİM']))); foreach (['Fatura & Fiyat', 'Tedarikçi & Analiz', 'Stok & Üretim'] as $_g) $_grupKat[$_g] = 'YÖNETİM'; } // satın alma grupları en üstte
                $_duzMenu = $_pp && !$_kasiyerMenu && !in_array($_pp->rol ?? '', ['sahip', 'mudur'], true) && in_array($_kadM, ['personel', 'mutfak'], true) && empty($_pp->departman_sorumlu);
            @endphp
            @if ($_duzMenu)
                @php
                    // Ajanda ayarların üstünde; aynı sayfa iki grupta varsa bir kez
                    $_sira = array_merge(array_diff(array_keys($gruplar), ['Ajanda', 'Ayarlar & Entegrasyon']), ['Ajanda', 'Ayarlar & Entegrasyon']);
                    $_gorulen = [];
                @endphp
                @foreach ($_sira as $grupAd)
                    @continue(!isset($gruplar[$grupAd]))
                    @php $items = array_values(array_filter($gruplar[$grupAd], function ($it) use (&$_gorulen) { if (isset($_gorulen[$it[0]])) return false; $_gorulen[$it[0]] = 1; return true; })); @endphp
                    @include('layout._menu_grup', ['grupAd' => $grupAd, 'items' => $items, 'basliksiz' => true])
                @endforeach
            @else
            {{-- Kategorisiz (standalone) üst gruplar: Patron AI, __PIN__ — sırayı koru (Ajanda AYARLAR'ın üstünde) --}}
            @foreach ($gruplar as $grupAd => $items)
                @if (!isset($_grupKat[$grupAd]) && $grupAd !== 'Ajanda')
                    @include('layout._menu_grup', ['grupAd' => $grupAd, 'items' => $items, 'basliksiz' => $grupAd === 'Brifing'])
                @endif
            @endforeach
            {{-- ÜST BAŞLIKLAR (açılır) → departman grupları --}}
            @foreach ($_kategoriler as $katAd => $_katGrupAdlari)
                @if ($katAd === 'AYARLAR' && isset($gruplar['Ajanda']))
                    @include('layout._menu_grup', ['grupAd' => 'Ajanda', 'items' => $gruplar['Ajanda']])
                @endif
                @php
                    $_catGruplar = [];
                    foreach ($_katGrupAdlari as $_g) if (isset($gruplar[$_g]) && $_grupGorunur($gruplar[$_g])) $_catGruplar[$_g] = $gruplar[$_g];
                    $_catAktif = false;
                    foreach ($_catGruplar as $_g => $_gi) { foreach ($_gi as $_it) { if ($aktifMi($_it[0])) { $_catAktif = true; break 2; } } }
                @endphp
                @if (count($_catGruplar) && $_katsiz && $katAd !== 'AYARLAR')
                    {{-- Departman çalışanı (yönetici değil): YÖNETİM/OPERASYON başlığı yok, gruplar doğrudan --}}
                    @foreach ($_catGruplar as $grupAd => $items)
                        @include('layout._menu_grup', ['grupAd' => $grupAd, 'items' => $items])
                    @endforeach
                @elseif (count($_catGruplar))
                    <div class="rk-cat{{ ($_catAktif || $_kasiyerMenu) ? '' : ' kapali' }}" data-cat="{{ $katAd }}" onclick="rkCatTog(this)"><span class="material-icons-outlined rk-cat-ik">{{ $_katIkon[$katAd] ?? 'folder' }}</span><span class="rk-cat-ad">{{ $katAd }}</span><span class="material-icons-outlined rk-cat-ok">expand_more</span></div>
                    <div class="rk-cat-body">
                        @foreach ($_catGruplar as $grupAd => $items)
                            {{-- AYARLAR kategorisinin tek grubu başlıksız: sayfalar doğrudan kategorinin altında --}}
                            @include('layout._menu_grup', ['grupAd' => $grupAd, 'items' => $items, 'basliksiz' => $grupAd === 'Ayarlar & Entegrasyon'])
                        @endforeach
                    </div>
                @endif
            @endforeach
            @endif
        </nav>
        <div class="rk-side-foot">
            @if ($_pp)
                <a href="/profilim" class="rk-sp{{ $path === '/profilim' ? ' aktif' : '' }}" title="{{ $kulAd }} · Profilim">
                    <span class="rk-sp-foto">
                        @if (!empty($_pp->foto) && str_starts_with((string) $_pp->foto, 'data:image'))
                            <img src="/profilim-avatar?v={{ crc32($_pp->foto) }}" alt="">
                        @else
                            {{ mb_strtoupper(mb_substr((string) $kulAd, 0, 1)) }}
                        @endif
                    </span>
                    <span class="rk-sp-ad"><b>{{ $kulAd }}</b><small>{{ $kulRol }}</small></span>
                    <span class="material-icons-outlined rk-sp-ok">chevron_right</span>
                </a>
            @endif
            <a href="/pos-cikis" class="rk-sp-cikis" title="Çıkış Yap"><span class="material-icons-outlined">logout</span> Çıkış Yap</a>
        </div>
    </aside>
    {{-- Menu durumu icerik cizilmeden uygulanir: sayfa gecisinde menu kipirdamasin --}}
    <script>
    function rkGrpTog(el){
        // Grup basligi tiklaninca AC/KAPA (akordiyon toggle)
        el.classList.toggle('kapali');
    }
    function rkCatTog(el){
        // Ust baslik (kategori) tiklaninca AC/KAPA
        el.classList.toggle('kapali');
    }
    // Acik/kapali basliklar saklanmaz: her yuklemede baslangic duzeni + bulunulan sayfanin grubu acik (kullanici istegi)
    (function(){
        var side = document.getElementById('rkSide'); if(!side) return;
        var menu = side.querySelector('.rk-menu');
        var mq = window.matchMedia('(min-width:641px)');
        try{ localStorage.removeItem('rk_menu_durum'); localStorage.removeItem('rk_panel_ust'); }catch(e){} // eski kayitlar
        // Bulunulan sayfanin basliklari her zaman acik
        var aktifA = menu.querySelector('a.aktif');
        if(aktifA){
            var aktifGovde = aktifA.closest('.rk-grp-body'), aktifKat = aktifA.closest('.rk-cat-body');
            var aktifGrp = aktifGovde && aktifGovde.previousElementSibling;
            if(aktifGrp && aktifGrp.classList.contains('rk-grp')) aktifGrp.classList.remove('kapali'); // başlıksız grubun önceki kardeşi başka grup olabilir
            if(aktifKat) aktifKat.previousElementSibling.classList.remove('kapali');
        }

        // Kaydirma konumu yalniz GENIS menu icin tutulur; dar menu (hepsi acik ikon listesi) bambaska yukseklikte
        var genisKonum = 0, t;
        try{ genisKonum = parseInt(sessionStorage.getItem('rk_menu_scroll'),10)||0; }catch(e){}
        function konumKaydet(){ try{ sessionStorage.setItem('rk_menu_scroll', genisKonum); }catch(e){} }
        function genisMi(){ return !mq.matches || side.classList.contains('genis'); }
        // Dar menude bulunulan sayfanin ikonu gorunur olsun
        function darKonum(){
            if(!aktifA) { menu.scrollTop = 0; return; }
            var m = menu.getBoundingClientRect(), a = aktifA.getBoundingClientRect();
            menu.scrollTop += a.top - m.top - menu.clientHeight/2;
        }
        // Masaustu mini menu (>=641px): menuye gelince acilir; FARE MENUDEN CIKINCA daralir.
        function ac(){
            if(!mq.matches || side.classList.contains('genis')) return;
            side.classList.add('genis');
            menu.scrollTop = genisKonum;
        }
        function kapa(){
            if(!side.classList.contains('genis')) return;
            side.classList.remove('genis');
            darKonum();
        }
        side.addEventListener('pointerenter', ac);
        side.addEventListener('pointerdown', ac);
        // FARE menuden cikinca kapat (dokunustan dogan sahte leave'i yok say)
        side.addEventListener('pointerleave', function(e){ if(mq.matches && e.pointerType === 'mouse') kapa(); });
        // menu DISINA tiklayinca/dokununca kapat
        document.addEventListener('pointerdown', function(e){ if(mq.matches && !side.contains(e.target)) kapa(); });
        // Menudeki bir LINK'e tiklayinca: "yeni sayfada acik gel" tek-seferlik isaret (fare hareketi bunu SILMEZ)
        side.addEventListener('click', function(e){ if(mq.matches && e.target.closest('a')){ try{ sessionStorage.setItem('rk_menu_nav_acik','1'); }catch(_){} } });
        menu.addEventListener('scroll', function(){
            if(!genisMi()) return;
            genisKonum = menu.scrollTop;
            clearTimeout(t); t = setTimeout(konumKaydet, 80);
        }, {passive:true});
        menu.addEventListener('click', konumKaydet); // link tiklaninca reload oncesi kaydet
        // Yeni sayfada: isaret varsa menu ACIK gelsin ve isareti TUKET (tek sefer). Fare ustundeyse acik kalir, cikinca pointerleave kapatir.
        var navAcik = false;
        try{ navAcik = mq.matches && sessionStorage.getItem('rk_menu_nav_acik')==='1'; if(navAcik) sessionStorage.removeItem('rk_menu_nav_acik'); }catch(e){}
        if(navAcik) side.classList.add('genis');
        if(genisMi()) menu.scrollTop = genisKonum; else darKonum();
    })();
    </script>
    <main class="rk-main">
        <div class="rk-header">
            <button class="rk-hamburger" onclick="rkSideTog()" aria-label="Menü"><span class="material-icons-outlined">menu</span></button>
            @if ($_profilGeri)
                <a href="/profilim" class="rk-geri" title="Profilime dön"><span class="material-icons-outlined">arrow_back</span><span class="rk-geri-metin">Geri</span></a>
            @else
                @hasSection('rk_geri')
                    <a href="@yield('rk_geri')" class="rk-geri" title="Geri"><span class="material-icons-outlined">arrow_back</span><span class="rk-geri-metin">Geri</span></a>
                @endif
            @endif
            <div class="ikon"><span class="material-icons-outlined">@yield('rk_ikon', 'dashboard')</span></div>
            <div>
                <div class="baslik">@yield('baslik', 'Panel')</div>
                @hasSection('rk_alt')<div class="alt">@yield('rk_alt')</div>@endif
            </div>
            <div class="sp"></div>
            @yield('rk_araclar')
            @if ($_pp) @include('layout._bildirim_zil') @endif
            <button class="rk-tema" onclick="rkTema()" title="Gece / Gündüz"><span class="material-icons-outlined" id="rkTemaIk">dark_mode</span></button>
            @if ($_pp)
                <a href="/profilim" class="rk-profil{{ $path === '/profilim' ? ' aktif' : '' }}" title="{{ $kulAd }} · Profilim" aria-label="Profilim">
                    <span class="rk-profil-ic">
                        @if (!empty($_pp->foto) && str_starts_with((string) $_pp->foto, 'data:image'))
                            <img src="/profilim-avatar?v={{ crc32($_pp->foto) }}" alt="">
                        @else
                            <span class="material-icons-outlined">person</span>
                        @endif
                    </span>
                </a>
            @endif
        </div>
        <div class="rk-body">
            @if ($_olcekSor && $path !== '/isletme-olcegi')
                <a href="/isletme-olcegi" style="display:flex;align-items:center;gap:10px;background:color-mix(in srgb,var(--mor1) 10%,var(--card));border:1px solid color-mix(in srgb,var(--mor1) 30%,var(--line));color:var(--ink);text-decoration:none;border-radius:14px;padding:12px 15px;margin-bottom:12px;font-size:13px;font-weight:700">
                    <span class="material-icons-outlined" style="color:var(--mor1)">tune</span>
                    <span style="flex:1">İşletmeniz ne büyüklükte? Seçin, menünüz size göre düzenlensin.</span>
                    <span class="material-icons-outlined" style="color:var(--mor1)">chevron_right</span>
                </a>
            @endif
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
      <div id="rkOnayMsg" style="font-size:14.5px;line-height:1.55;color:var(--ink);white-space:pre-line"></div>
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

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7C3AED">
    <title>@yield('title', 'ResteOS') — ResteOS</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
    <link rel="stylesheet" href="/panel-kit.css?v=3">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>try{if(localStorage.getItem('app_koyu')==='1')document.documentElement.classList.add('_koyu');}catch(e){}</script>
    <style>
    html._koyu body{}
    .rk-grp{display:flex;align-items:center;justify-content:space-between;cursor:pointer;user-select:none;
      font-size:11px;font-weight:800;letter-spacing:.7px;text-transform:uppercase;opacity:.72;
      padding:12px 16px 7px;margin-top:3px;border-top:1px solid rgba(120,120,140,.14)}
    .rk-grp:first-of-type{border-top:0;margin-top:0}
    .rk-grp:hover{opacity:1}
    .rk-grp .rk-grp-ok{font-size:19px;transition:transform .2s ease;opacity:.7}
    .rk-grp.kapali .rk-grp-ok{transform:rotate(-90deg)}
    .rk-grp.kapali + .rk-grp-body{display:none}
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
            ['/garson-cagrilari', 'notifications_active', 'Garson Çağrıları', '#F43F5E'],
        ],
        'Menü & Ürünler' => [
            ['/menu', 'restaurant_menu', 'Menü Yönetimi', null],
            ['/app-yonetim', 'palette', 'App Kartları / QR Menü', '#F6CE63'],
            ['/qr-menu-rengi', 'color_lens', 'QR Menü Rengi', '#EC4899'],
            ['/indirimler', 'local_offer', 'İndirimler', '#22C55E'],
            ['/barkod', 'qr_code', 'Barkod', null],
        ],
        'Stok & Tedarik' => [
            ['/satinalma-talep', 'assignment', 'Satın Alma Talebi Oluştur', '#7C3AED'],
            ['/stok', 'inventory_2', 'Stok & Satın Alma', null],
            ['/recete', 'menu_book', 'Reçeteler (Yarı Mamül)', '#7C3AED'],
            ['/siparis-onerisi', 'auto_awesome', 'Otomatik Sipariş Önerisi', '#7C3AED'],
            ['/satinalma-siparis', 'shopping_cart_checkout', 'Satınalma Siparişi', '#4F46E5'],
            ['/mal-kabul', 'inventory', 'Mal Kabul / İrsaliye', '#10B981'],
            ['/fatura-eslestir', 'fact_check', 'Fatura Eşleştir (3-way)', '#F59E0B'],
            ['/parti-takip', 'event_busy', 'Parti / SKT Takip', '#F43F5E'],
            ['/fiyat-radari', 'radar', 'Fiyat Radarı · Gizli Zam', '#EC4899'],
            ['/hal-fiyatlari', 'storefront', 'Hal Fiyatları (sen vs hal)', '#059669'],
            ['/satinalma-analiz', 'leaderboard', 'Satınalma Analizi · Karne', '#4F46E5'],
            ['/satinalma-asistan', 'smart_toy', 'Satınalma AI Asistanı', '#7C3AED'],
            ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
            ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
            ['/tedarikci', 'local_shipping', 'Tedarikçiler', null],
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
        ],
    ];
    // DEPARTMAN + BİLDİRİM (additif, flag ile). gor_personel yetkisi olan departmanı görür; bildirim herkese açık.
    if (config('services.resteos.departman_acik', true)) {
        $gruplar['Personel'][] = ['/departman-yonetim', 'groups', 'Departman & Yetki', '#4F46E5'];
        $gruplar['Ayarlar & Entegrasyon'][] = ['/bildirim-ayar', 'notifications_active', 'Bildirim Ayarları', '#0EA5E9'];
    }
    // PATRON AI — sabah brifingi + entegrasyonlar (en üste, Panel grubunun başına). gor_copilot yetkisi olana görünür.
    $gruplar = ['Patron AI' => [
        ['/patron-ai', 'auto_awesome', 'Sabah Brifingi', '#7C3AED'],
        ['/entegrasyonlar', 'hub', 'Entegrasyonlar (Gmail/Takvim)', '#4F46E5'],
    ]] + $gruplar;
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
@endphp
<div class="rk-wrap">
    <aside class="rk-side">
        <div class="rk-side-head">
            <div class="ad">🍔 ResteOS</div>
            <div class="sube">{{ $aktifSubeAd }}</div>
            <div class="kul">{{ $kulAd }} · {{ $kulRol }}</div>
        </div>
        <nav class="rk-menu">
            @foreach ($gruplar as $grupAd => $items)
                @php
                    $gorunur = array_values(array_filter($items, fn ($it) => !($_pp && isset($_harita[$it[0]]) && !_panelYetkiVar($_pp, $_harita[$it[0]]))));
                    $grupAktif = collect($gorunur)->contains(fn ($it) => $it[0] === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $it[0]));
                @endphp
                @if (count($gorunur))
                    <div class="rk-grp{{ $grupAktif ? '' : ' kapali' }}" data-grp="{{ $grupAd }}" onclick="rkGrpTog(this)">{{ $grupAd }}<span class="material-icons-outlined rk-grp-ok">expand_more</span></div>
                    <div class="rk-grp-body">
                        @foreach ($gorunur as [$url, $ikon, $ad, $renk])
                            @php $aktif = ($url === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $url)); @endphp
                            <a href="{{ $url }}" class="{{ $aktif ? 'aktif' : '' }}" @if($renk && !$aktif) style="color:{{ $renk }}" @endif>
                                <span class="material-icons-outlined" @if($renk && !$aktif) style="color:{{ $renk }}" @endif>{{ $ikon }}</span> {{ $ad }}
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
@stack('scripts')
</body>
</html>

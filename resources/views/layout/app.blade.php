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
    <style>html._koyu body{}</style>
    @stack('head')
</head>
<body>
<script>if(document.documentElement.classList.contains('_koyu'))document.body.classList.add('koyu');</script>
@php
    // Flutter YonetimDrawer ile BİREBİR: [url, material-icon, etiket, renk(null=ink)]
    $nav = [
        ['/dashboard', 'dashboard', 'Dashboard', null],
        ['/pos', 'point_of_sale', 'POS / Masa', null],
        ['/odul-dogrula', 'qr_code_scanner', 'Ödül Doğrula', '#22C55E'],
        ['/kasa', 'account_balance', 'Kasa (Vardiya)', null],
        ['/finans', 'analytics', 'Finans / Kâr-Zarar', null],
        ['/stok', 'inventory_2', 'Stok & Satın Alma', null],
        ['/uretim-riski', 'warning_amber', 'Üretim Riski', '#F59E0B'],
        ['/sayim', 'fact_check', 'Sayım (Envanter)', '#0EA5E9'],
        ['/menu', 'restaurant_menu', 'Menü Yönetimi', null],
        ['/app-yonetim', 'palette', 'App Kartları / QR Menü', '#F6CE63'],
        ['/indirimler', 'local_offer', 'İndirimler', '#22C55E'],
        ['/odeme-ayar', 'shield', 'Kaçak Önleme / Ödeme', '#F43F5E'],
        ['/garson-cagrilari', 'notifications_active', 'Garson Çağrıları', '#F43F5E'],
        ['/cari', 'account_balance_wallet', 'Cari / Açık Hesaplar', null],
        ['/rezervasyon', 'event_available', 'Rezervasyonlar', null],
        ['/kurye', 'map', 'Canlı Kurye Haritası', '#10B981'],
        ['/masa-yonetim', 'qr_code_2', 'Masa & QR Yönetimi', '#F6CE63'],
        ['/personel', 'badge', 'Personel & Maaş', null],
        ['/puantaj', 'schedule', 'Puantaj', null],
        ['/garson-performans', 'emoji_events', 'Garson Performansı', '#10B981'],
        ['/salon-sema', 'grid_on', 'Salon Şeması', '#0EA5E9'],
        ['/gider', 'receipt_long', 'Giderler', null],
        ['/raporlar', 'bar_chart', 'Raporlar', null],
        ['/z-raporu', 'summarize', 'Z Raporu', null],
        ['/muhasebe', 'account_balance', 'Muhasebe / Cari', null],
        ['/bagli-cihazlar', 'devices_other', 'Bağlı Cihazlar', '#0EA5E9'],
        ['/yazici-ayarlari', 'print', 'Yazıcı Ayarları', '#14B8A6'],
        ['/isletme-konum', 'my_location', 'İşletme Konum', null],
        ['/hareketler', 'history', 'Hareketler (Log)', '#7C3AED'],
        ['/sebep-yonetimi', 'rule_folder', 'İptal / İkram Sebepleri', null],
        ['/tedarikci', 'local_shipping', 'Tedarikçiler', null],
        ['/barkod', 'qr_code', 'Barkod', null],
        ['/odul-raporu', 'emoji_events', 'Ödül Raporu', '#F6CE63'],
        ['/whatsapp', 'chat', 'WhatsApp Yönetimi', '#22C55E'],
        ['/mutfak', 'soup_kitchen', 'Mutfak (KDS)', null],
        ['/paket', 'delivery_dining', 'Paket Siparişler', null],
        ['/copilot', 'auto_awesome', 'Patron Asistan', '#C4B5FD'],
    ];
    $path = '/' . trim(request()->path(), '/');
    $aktifSubeAd = 'ResteOS';
    try { if (function_exists('_aktifSubeId')) { $aktifSubeAd = optional(\DB::table('subeler')->where('id', _aktifSubeId())->first())->ad ?: 'ResteOS'; } } catch (\Throwable $e) {}
    if ($aktifSubeAd === 'ResteOS') { try { $aktifSubeAd = optional(\DB::table('subeler')->first())->ad ?: 'ResteOS'; } catch (\Throwable $e) {} }
    $kulAd = 'Yönetim'; $kulRol = 'Panel';
    try { if (function_exists('_posPersonel') && _posPersonel()) { $p = _posPersonel(); $kulAd = $p->ad ?? 'Yönetim'; $kulRol = ['sahip'=>'Sahip','mudur'=>'Müdür','kasa'=>'Kasa','garson'=>'Garson'][$p->rol ?? ''] ?? ($p->rol ?? 'Panel'); } } catch (\Throwable $e) {}
@endphp
<div class="rk-wrap">
    <aside class="rk-side">
        <div class="rk-side-head">
            <div class="ad">🍔 ResteOS</div>
            <div class="sube">{{ $aktifSubeAd }}</div>
            <div class="kul">{{ $kulAd }} · {{ $kulRol }}</div>
        </div>
        <nav class="rk-menu">
            @foreach ($nav as [$url, $ikon, $ad, $renk])
                @php $aktif = ($url === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $url)); @endphp
                <a href="{{ $url }}" class="{{ $aktif ? 'aktif' : '' }}" @if($renk && !$aktif) style="color:{{ $renk }}" @endif>
                    <span class="material-icons-outlined" @if($renk && !$aktif) style="color:{{ $renk }}" @endif>{{ $ikon }}</span> {{ $ad }}
                </a>
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
    function para(v) { return new Intl.NumberFormat('tr-TR').format(Math.round(v)) + ' ₺'; }
    if ('serviceWorker' in navigator) { window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {})); }
</script>
@stack('scripts')
</body>
</html>

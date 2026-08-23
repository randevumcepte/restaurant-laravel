<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <title>@yield('title', 'Lezzet Duragi') — RestoOS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px}</style>
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800">
@php
    $nav = [
        ['/', '📊', 'Dashboard'],
        ['/patron', '👑', 'Patron Özet'],
        ['/pos', '🍽️', 'POS / Adisyon'],
        ['/paket', '🛵', 'Paket Siparişler'],
        ['/entegrasyon', '🔌', 'Entegrasyonlar'],
        ['/kurye', '🗺️', 'Kurye Takip'],
        ['/mutfak', '👨‍🍳', 'Mutfak (KDS)'],
        ['/kiosk', '🖥️', 'Kiosk (Self-Servis)'],
        ['/menu', '📋', 'Menü Yönetimi'],
        ['/stok', '📦', 'Stok & Reçete'],
        ['/satinalma', '🧾', 'Satın Alma'],
        ['/teklif', '📊', 'Teklifler'],
        ['/musteriler', '👥', 'Müşteriler'],
        ['/sadakat', '🎁', 'Sadakat'],
        ['/cagrilar', '📞', 'Çağrı Merkezi'],
        ['/raporlar', '📈', 'Raporlar'],
        ['/muhasebe', '💰', 'Muhasebe / Cari'],
        ['/edonusum', '🧾', 'E-Dönüşüm'],
        ['/copilot', '🤖', 'AI Copilot'],
        ['/fiyatlandirma', '🏷️', 'Fiyatlandırma'],
    ];
    $path = '/' . trim(request()->path(), '/');
    if ($path === '/') $path = '/';
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="w-60 shrink-0 bg-slate-900 text-slate-300 flex flex-col fixed h-screen">
        <div class="px-5 py-4 border-b border-slate-700/50">
            <div class="text-white font-bold text-lg">🍔 RestoOS</div>
            <div class="text-xs text-slate-400">Lezzet Duragi</div>
        </div>
        <nav class="flex-1 overflow-y-auto py-3">
            @foreach ($nav as [$url, $ikon, $ad])
                @php $aktif = ($url === '/' ? $path === '/' : \Illuminate\Support\Str::startsWith($path, $url)); @endphp
                <a href="{{ $url }}"
                   class="flex items-center gap-3 px-5 py-2.5 text-sm transition {{ $aktif ? 'bg-indigo-600 text-white font-semibold' : 'hover:bg-slate-800' }}">
                    <span class="text-base">{{ $ikon }}</span> {{ $ad }}
                </a>
            @endforeach
        </nav>
        <div class="px-5 py-3 border-t border-slate-700/50 text-xs text-slate-500">
            RestoOS MVP · {{ now()->format('d.m.Y') }}
        </div>
    </aside>

    {{-- Icerik --}}
    <main class="flex-1 ml-60 min-h-screen">
        <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-10">
            <h1 class="text-lg font-bold text-slate-900">@yield('baslik', 'Dashboard')</h1>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Canlı
                </span>
                <span class="text-sm text-slate-500">{{ now()->translatedFormat('d M Y, H:i') }}</span>
            </div>
        </header>
        <div class="p-6">
            @yield('content')
        </div>
    </main>
</div>

<script>
    window.csrf = document.querySelector('meta[name=csrf-token]').content;
    async function api(url, data = null) {
        const opt = { headers: { 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json' } };
        if (data) { opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(data); }
        const r = await fetch(url, opt);
        return r.json();
    }
    function para(v) { return new Intl.NumberFormat('tr-TR').format(Math.round(v)) + ' ₺'; }
    // PWA: offline temeli (service worker)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restoran Sistemi — Kurulum</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 max-w-lg text-center">
        <div class="text-4xl mb-3">🍽️</div>
        <h1 class="text-xl font-bold text-slate-900 mb-2">Restoran Yönetim Sistemi</h1>
        <p class="text-slate-500 mb-4">Veritabanı henüz doldurulmamış. Demo veriyi yüklemek için sunucuda:</p>
        <pre class="bg-slate-900 text-slate-100 text-left text-sm rounded-xl p-4 overflow-x-auto">php artisan migrate --force
php artisan db:seed --force</pre>
        <p class="text-xs text-slate-400 mt-4">Ardından bu sayfayı yenileyin.</p>
    </div>
</body>
</html>

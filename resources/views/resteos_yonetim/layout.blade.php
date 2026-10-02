<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('baslik','Sistem Yönetimi') · ResteOS</title>
  <link rel="stylesheet" href="{{ asset('css/resteos-yonetim.css') }}?v=3">
</head>
<body>
@php
  $admin = _ryAdmin();
  $acikTicket = \Illuminate\Support\Facades\Schema::hasTable('resteos_ticket') ? \Illuminate\Support\Facades\DB::table('resteos_ticket')->whereIn('durum',['acik','islemde'])->count() : 0;
  $p = request()->path();
  if(!function_exists('_ryAktif')){ function _ryAktif($yol){ $p=request()->path(); return $p===$yol || ($yol!=='resteos-yonetim' && str_starts_with($p,$yol)) ? 'aktif':''; } }
@endphp
<div class="ry-shell">
  <aside class="ry-sidebar">
    <div class="ry-brand">
      <div class="ry-logo">🍽️</div>
      <div>ResteOS<small>SİSTEM YÖNETİMİ</small></div>
    </div>
    <nav class="ry-nav">
      <div class="ry-grp">Genel</div>
      <a href="/resteos-yonetim" class="{{ _ryAktif('resteos-yonetim') }}"><span class="ic">📊</span> Dashboard</a>
      <a href="/resteos-yonetim/restoranlar" class="{{ _ryAktif('resteos-yonetim/restoranlar') }}"><span class="ic">🏪</span> Restoranlar</a>
      <a href="/resteos-yonetim/restoran-ekle" class="{{ _ryAktif('resteos-yonetim/restoran-ekle') }}"><span class="ic">➕</span> Restoran Ekle</a>
      <a href="/resteos-yonetim/riskli" class="{{ _ryAktif('resteos-yonetim/riskli') }}"><span class="ic">⚠️</span> Riskli Restoranlar</a>

      <div class="ry-grp">Destek & İletişim</div>
      <a href="/resteos-yonetim/ticket" class="{{ _ryAktif('resteos-yonetim/ticket') }}"><span class="ic">🎫</span> Destek Talepleri @if($acikTicket)<span class="ry-rozet">{{ $acikTicket }}</span>@endif</a>
      <a href="/resteos-yonetim/duyurular" class="{{ _ryAktif('resteos-yonetim/duyurular') }}"><span class="ic">📢</span> Duyurular</a>

      <div class="ry-grp">Finans</div>
      <a href="/resteos-yonetim/ai-kredi" class="{{ _ryAktif('resteos-yonetim/ai-kredi') }}"><span class="ic">🤖</span> AI / Santral Kredi</a>
      <a href="/resteos-yonetim/sms-paket" class="{{ _ryAktif('resteos-yonetim/sms-paket') }}"><span class="ic">💬</span> SMS Paketleri</a>
      <a href="/resteos-yonetim/odeme-linki" class="{{ _ryAktif('resteos-yonetim/odeme-linki') }}"><span class="ic">💳</span> Manuel Ödeme Linki</a>

      <div class="ry-grp">Sistem</div>
      <a href="/resteos-yonetim/ekip" class="{{ _ryAktif('resteos-yonetim/ekip') }}"><span class="ic">👥</span> Ekip & Roller</a>
      <a href="/resteos-yonetim/guvenlik" class="{{ _ryAktif('resteos-yonetim/guvenlik') }}"><span class="ic">🛡️</span> Güvenlik Duvarı</a>
      <a href="/wa-yonetim" target="_blank"><span class="ic">🟢</span> Sistem WhatsApp</a>
      <a href="/resteos-yonetim/sistem-saglik" class="{{ _ryAktif('resteos-yonetim/sistem-saglik') }}"><span class="ic">💚</span> Sistem Sağlık</a>
      <a href="/resteos-yonetim/loglar" class="{{ _ryAktif('resteos-yonetim/loglar') }}"><span class="ic">📜</span> Loglar</a>
      <a href="/resteos-yonetim/profil" class="{{ _ryAktif('resteos-yonetim/profil') }}"><span class="ic">⚙️</span> Profil</a>
    </nav>
    <div style="padding:14px 16px;border-top:1px solid rgba(255,255,255,.08)">
      <a href="/resteos-yonetim/cikis" class="ry-btn ry-btn-danger ry-btn-blok ry-btn-sm">↩ Çıkış Yap</a>
    </div>
  </aside>

  <div class="ry-main">
    @if(session('ry_imp_sube'))
      <div class="ry-imp-bar">🔓 <b>{{ session('ry_imp_ad') }}</b> restoranının paneline bağlısınız (impersonation).
        <a href="/resteos-yonetim/impersonation-bitir">Yönetime Dön</a></div>
    @endif
    <div class="ry-topbar">
      <h1>@yield('baslik','Dashboard')</h1>
      <div class="ry-sag">
        <a href="/resteos-yonetim/profil" class="ry-user">
          <span class="ry-ava">{{ mb_substr($admin->ad ?? 'A',0,1) }}</span>
          <span>{{ $admin->ad ?? '—' }}</span>
        </a>
      </div>
    </div>
    <div class="ry-content">
      @if(session('ok'))<div class="ry-alert ok">✅ {{ session('ok') }}</div>@endif
      @if(session('hata'))<div class="ry-alert hata">⚠️ {{ session('hata') }}</div>@endif
      @yield('content')
    </div>
  </div>
</div>
</body>
</html>

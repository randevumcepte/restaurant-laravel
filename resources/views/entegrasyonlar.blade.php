@extends('layout.app')
@section('rk_ikon', 'hub')
@section('baslik', 'Entegrasyonlar')
@section('title', 'Entegrasyonlar')

@section('content')
<div class="rk-body">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:12px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Dış Kaynak Bağlantıları</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-4px 2px 16px">
    Patron AI'ın sabah/akşam brifingine <b>yanıt bekleyen e-postalar</b> ve <b>bugünkü takvim</b> eklenir.
    Erişim <b>salt-okunurdur</b> (sadece konu/gönderen/saat okunur, e-posta gövdesi alınmaz). Token'lar şifreli saklanır; istediğin an kaldırabilirsin.
  </div>

  <div class="rk-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px">

    {{-- Google --}}
    <div class="rk-card" style="padding:16px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <span class="material-icons-outlined" style="font-size:28px;color:#EA4335">mail</span>
        <div style="flex:1"><div style="font-weight:800;font-size:15px">Google</div><div class="rk-sub" style="font-size:11px">Gmail + Google Takvim</div></div>
        @if ($google) <span class="rk-rozet" style="background:var(--yesil)">Bağlı</span> @endif
      </div>
      @if (!$googleVar)
        <div class="rk-sub" style="font-size:11.5px">Yapılandırılmamış — <code>GOOGLE_OAUTH_CLIENT_ID/SECRET</code> gerekli.</div>
      @elseif ($google)
        <div class="rk-sub" style="font-size:12px;margin-bottom:10px">Hesap: {{ $google->hesap_email ?: '—' }}</div>
        <form method="POST" action="/baglanti/kaldir">@csrf<input type="hidden" name="saglayici" value="google">
          <button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Bağlantıyı Kaldır</button></form>
      @else
        <a href="/baglanti/google" class="rk-btn" style="background:#EA4335">Google ile Bağlan</a>
      @endif
    </div>

    {{-- Microsoft --}}
    <div class="rk-card" style="padding:16px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
        <span class="material-icons-outlined" style="font-size:28px;color:#0078D4">business</span>
        <div style="flex:1"><div style="font-weight:800;font-size:15px">Microsoft 365</div><div class="rk-sub" style="font-size:11px">Outlook + Takvim</div></div>
        @if ($graph) <span class="rk-rozet" style="background:var(--yesil)">Bağlı</span> @endif
      </div>
      @if (!$graphVar)
        <div class="rk-sub" style="font-size:11.5px">Yapılandırılmamış — <code>GRAPH_OAUTH_CLIENT_ID/SECRET</code> gerekli.</div>
      @elseif ($graph)
        <div class="rk-sub" style="font-size:12px;margin-bottom:10px">Hesap: {{ $graph->hesap_email ?: '—' }}</div>
        <form method="POST" action="/baglanti/kaldir">@csrf<input type="hidden" name="saglayici" value="graph">
          <button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Bağlantıyı Kaldır</button></form>
      @else
        <a href="/baglanti/graph" class="rk-btn" style="background:#0078D4">Microsoft ile Bağlan</a>
      @endif
    </div>

  </div>

  <div class="rk-sub" style="font-size:11px;margin-top:18px">
    Not: Bağlantı için önce Google Cloud / Azure portföyünde OAuth uygulaması oluşturup
    redirect URI olarak <code>{{ url('/baglanti/google/callback') }}</code> ve <code>{{ url('/baglanti/graph/callback') }}</code> tanımlanmalıdır.
  </div>
</div>
@endsection

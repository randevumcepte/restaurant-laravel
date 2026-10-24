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
        <div class="rk-sub" style="font-size:11.5px">Tek-tık bağlantı için aşağıdan <b>kendi Google anahtarını</b> gir. (Ya da departman sayfasından uygulama şifresiyle bağlan.)</div>
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
        <div class="rk-sub" style="font-size:11.5px">Tek-tık bağlantı için aşağıdan <b>kendi Azure anahtarını</b> gir. (Ya da departman sayfasından uygulama şifresiyle bağlan.)</div>
      @elseif ($graph)
        <div class="rk-sub" style="font-size:12px;margin-bottom:10px">Hesap: {{ $graph->hesap_email ?: '—' }}</div>
        <form method="POST" action="/baglanti/kaldir">@csrf<input type="hidden" name="saglayici" value="graph">
          <button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Bağlantıyı Kaldır</button></form>
      @else
        <a href="/baglanti/graph" class="rk-btn" style="background:#0078D4">Microsoft ile Bağlan</a>
      @endif
    </div>

  </div>

  {{-- ==================== SELF-SERVICE OAUTH UYGULAMA ANAHTARLARI ==================== --}}
  <div class="rk-bolum" style="margin:24px 0 8px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Tek-Tık için Kendi Uygulaman (ücretsiz, panelden)</span>
  </div>
  <div class="rk-sub" style="font-size:12px;margin:-2px 2px 14px">
    Çalışanların "İzin Ver" diyerek <b>tek tıkla</b> bağlanması için restoranın kendi Google/Microsoft uygulamasını buraya girer — bize/sana ek ücret çıkmaz.
    <b>Uygulama şifresi (SMTP/IMAP) kullanacaksan buna gerek yok:</b> doğrudan departman → 🔌 → Kurumsal E-posta'dan bağlanırsın.
  </div>

  <div class="rk-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(330px,1fr));gap:14px">
    {{-- Google uygulaması --}}
    <div class="rk-card" style="padding:16px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <span style="font-weight:800;font-size:14px">🔑 Google Uygulaması</span>
        @if ($gCid)<span class="rk-rozet" style="background:var(--yesil)">Tanımlı</span>@endif
      </div>
      <div class="rk-sub" style="font-size:11.5px;margin-bottom:8px">Google Cloud Console → <b>APIs &amp; Services → Credentials → OAuth Client ID (Web)</b>. Kapsam: <code>gmail.send</code> + <code>gmail.readonly</code>.</div>
      <div class="rk-sub" style="font-size:11px;margin-bottom:10px;background:var(--card2);border-radius:8px;padding:8px">Redirect URI (Google'a ekle):<br><code style="user-select:all;word-break:break-all">{{ $gRedirect }}</code></div>
      <form method="POST" action="/oauth-uygulama-kaydet">@csrf<input type="hidden" name="saglayici" value="google">
        <label class="rk-lbl">Client ID</label>
        <input class="rk-inp" name="client_id" value="{{ $gCid }}" placeholder="xxxx.apps.googleusercontent.com">
        <label class="rk-lbl">Client Secret</label>
        <input class="rk-inp" name="client_secret" type="password" placeholder="{{ $gCid ? 'değiştirmezsen boş bırak' : 'GOCSPX-...' }}">
        <button class="rk-btn" style="background:var(--mor1);margin-top:10px" type="submit">Kaydet</button>
      </form>
    </div>
    {{-- Microsoft uygulaması --}}
    <div class="rk-card" style="padding:16px">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <span style="font-weight:800;font-size:14px">🔑 Microsoft (Azure) Uygulaması</span>
        @if ($mCid)<span class="rk-rozet" style="background:var(--yesil)">Tanımlı</span>@endif
      </div>
      <div class="rk-sub" style="font-size:11.5px;margin-bottom:8px">Azure Portal → <b>App registrations → Yeni kayıt</b>. İzinler: <code>Mail.Send</code> + <code>Mail.Read</code>. Secret: <b>Certificates &amp; secrets</b>.</div>
      <div class="rk-sub" style="font-size:11px;margin-bottom:10px;background:var(--card2);border-radius:8px;padding:8px">Redirect URI (Azure'a ekle):<br><code style="user-select:all;word-break:break-all">{{ $mRedirect }}</code></div>
      <form method="POST" action="/oauth-uygulama-kaydet">@csrf<input type="hidden" name="saglayici" value="graph">
        <label class="rk-lbl">Client ID (Application ID)</label>
        <input class="rk-inp" name="client_id" value="{{ $mCid }}" placeholder="00000000-0000-...">
        <label class="rk-lbl">Directory (tenant) — tek şirket için tenant ID, herkese açıksa common</label>
        <input class="rk-inp" name="tenant" value="{{ $mTenant }}" placeholder="common">
        <label class="rk-lbl">Client Secret</label>
        <input class="rk-inp" name="client_secret" type="password" placeholder="{{ $mCid ? 'değiştirmezsen boş bırak' : 'secret değeri' }}">
        <button class="rk-btn" style="background:var(--mavi);margin-top:10px" type="submit">Kaydet</button>
      </form>
    </div>
  </div>
</div>
@endsection

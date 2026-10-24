@extends('layout.app')
@section('rk_ikon', 'hub')
@section('baslik', 'Bağlantı Yönetimi')
@section('title', $dep->ad . ' — Bağlantılar')

@push('head')
<style>
  #db{max-width:760px}
  .db-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .db-h{display:flex;align-items:center;gap:10px;margin-bottom:6px}
  .db-h .ik{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff}
  .db-h .b{font-weight:800;font-size:15px}
  .db-durum{font-size:11px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff}
  .db-not{font-size:12px;color:var(--sub);line-height:1.5;margin:6px 0 12px}
  .db-btn-g{background:#EA4335}.db-btn-o{background:#0A66C2}.db-btn-s{background:#6B7280}.db-btn-w{background:#22C55E}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="db">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <a href="/departman-yonetim" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:28px;color:{{ $dep->renk ?: 'var(--mor1)' }}">{{ $dep->ikon ?: 'apartment' }}</span>
    <div style="flex:1">
      <div style="font-size:19px;font-weight:800;color:var(--ink)">{{ $dep->ad }} · Bağlantılar</div>
      <div class="rk-sub" style="font-size:12px">Bu departman adına AI mail/WhatsApp gönderir ve gelen yanıtları okur.</div>
    </div>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- GMAIL --}}
  <div class="db-kart">
    <div class="db-h">
      <span class="ik db-btn-g"><span class="material-icons-outlined" style="font-size:20px">mail</span></span>
      <span class="b">Gmail</span>
      @if ($google)<span class="db-durum" style="background:var(--yesil);margin-left:auto">BAĞLI</span>
      @else<span class="db-durum" style="background:var(--sub);margin-left:auto">BAĞLI DEĞİL</span>@endif
    </div>
    @if ($google)
      <div class="db-not">✅ <b>{{ $google->hesap_email ?: 'Gmail hesabı' }}</b> bağlı. AI bu kutudan RFQ/sipariş gönderir ve gelen fiyat tekliflerini okur.</div>
      <form method="POST" action="/baglanti/kaldir" onsubmit="return rkOnayForm(this, 'Gmail bağlantısı bu departmandan kaldırılsın mı?', 'var(--kirmizi)', 'Evet, kaldır')">
        @csrf<input type="hidden" name="saglayici" value="google"><input type="hidden" name="dep" value="{{ $dep->id }}">
        <button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Bağlantıyı Kes</button>
      </form>
    @elseif ($googleVar)
      <div class="db-not">Tek tıkla bağlan — gönderme + okuma Gmail üzerinden olur (ekstra kurulum/şifre gerekmez).</div>
      <a href="/baglanti/google?dep={{ $dep->id }}" class="rk-btn db-btn-g"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">link</span> Gmail ile Bağlan</a>
    @else
      <div class="db-not">⚠️ Google tek-tık bağlantısı için sistem yöneticisinin Google Cloud OAuth anahtarlarını kurması gerekir. Alternatif: aşağıdan <b>Kurumsal E-posta (SMTP)</b> ile Gmail uygulama şifresini gir.</div>
    @endif
  </div>

  {{-- OUTLOOK / 365 --}}
  <div class="db-kart">
    <div class="db-h">
      <span class="ik db-btn-o"><span class="material-icons-outlined" style="font-size:20px">mail</span></span>
      <span class="b">Outlook / Microsoft 365</span>
      @if ($graph)<span class="db-durum" style="background:var(--yesil);margin-left:auto">BAĞLI</span>
      @else<span class="db-durum" style="background:var(--sub);margin-left:auto">BAĞLI DEĞİL</span>@endif
    </div>
    @if ($graph)
      <div class="db-not">✅ <b>{{ $graph->hesap_email ?: 'Outlook hesabı' }}</b> bağlı. AI bu kutudan gönderir ve gelen yanıtları okur.</div>
      <form method="POST" action="/baglanti/kaldir" onsubmit="return rkOnayForm(this, 'Outlook bağlantısı bu departmandan kaldırılsın mı?', 'var(--kirmizi)', 'Evet, kaldır')">
        @csrf<input type="hidden" name="saglayici" value="graph"><input type="hidden" name="dep" value="{{ $dep->id }}">
        <button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Bağlantıyı Kes</button>
      </form>
    @elseif ($graphVar)
      <div class="db-not">Tek tıkla bağlan — gönderme + okuma Microsoft Graph üzerinden olur.</div>
      <a href="/baglanti/graph?dep={{ $dep->id }}" class="rk-btn db-btn-o"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">link</span> Outlook ile Bağlan</a>
    @else
      <div class="db-not">⚠️ Outlook tek-tık bağlantısı için sistem yöneticisinin Azure OAuth anahtarlarını kurması gerekir.</div>
    @endif
  </div>

  {{-- KURUMSAL SMTP --}}
  <div class="db-kart">
    <div class="db-h">
      <span class="ik db-btn-s"><span class="material-icons-outlined" style="font-size:20px">dns</span></span>
      <span class="b">Kurumsal E-posta (SMTP)</span>
      @if ($smtp)<span class="db-durum" style="background:var(--yesil);margin-left:auto">TANIMLI</span>@endif
    </div>
    <div class="db-not">Kendi mail sunucun (ör. mail.firman.com) veya Gmail/Outlook <b>uygulama şifresi</b> ile gönderim. Okuma için sunucuda IMAP gerekir (opsiyonel).</div>
    <form method="POST" action="/departman-baglanti/smtp">
      @csrf<input type="hidden" name="dep" value="{{ $dep->id }}">
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px"><label class="rk-lbl">E-posta Adresi</label><input class="rk-inp" name="email" type="email" value="{{ $smtp->email ?? '' }}" placeholder="satinalma@firma.com" required></div>
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Gönderen Adı</label><input class="rk-inp" name="gonderen_ad" value="{{ $smtp->gonderen_ad ?? '' }}" placeholder="{{ $dep->ad }}"></div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:2;min-width:200px"><label class="rk-lbl">SMTP Sunucu</label><input class="rk-inp" name="smtp_host" value="{{ $smtp->smtp_host ?? '' }}" placeholder="smtp.gmail.com / smtp.office365.com"></div>
        <div style="width:90px"><label class="rk-lbl">Port</label><input class="rk-inp" name="smtp_port" type="number" value="{{ $smtp->smtp_port ?? 587 }}"></div>
        <div style="width:110px"><label class="rk-lbl">Güvenlik</label><select class="rk-inp" name="smtp_guvenlik">
          @foreach (['tls'=>'TLS','ssl'=>'SSL','yok'=>'Yok'] as $k=>$v)<option value="{{ $k }}" @selected(($smtp->smtp_guvenlik ?? 'tls')===$k)>{{ $v }}</option>@endforeach
        </select></div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Kullanıcı Adı</label><input class="rk-inp" name="smtp_user" value="{{ $smtp->smtp_user ?? '' }}" placeholder="genelde e-posta adresi"></div>
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Şifre / Uygulama Şifresi</label><input class="rk-inp" name="smtp_sifre" type="password" placeholder="{{ $smtp ? 'değiştirmezsen boş bırak' : '' }}"></div>
      </div>
      <div style="display:flex;gap:8px;margin-top:12px">
        <button class="rk-btn db-btn-s" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">save</span> Kaydet</button>
        @if ($smtp)
        <form method="POST" action="/departman-baglanti/smtp-sil" style="display:inline" onsubmit="return rkOnayForm(this, 'Kurumsal SMTP kaldırılsın mı?', 'var(--kirmizi)', 'Evet, kaldır')">
          @csrf<input type="hidden" name="dep" value="{{ $dep->id }}"><button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Kaldır</button>
        </form>
        @endif
      </div>
    </form>
  </div>

  {{-- WHATSAPP --}}
  <div class="db-kart">
    <div class="db-h">
      <span class="ik db-btn-w"><span class="material-icons-outlined" style="font-size:20px">chat</span></span>
      <span class="b">WhatsApp</span>
      @if ($dep->wa_numara ?? null)<span class="db-durum" style="background:var(--yesil);margin-left:auto">TANIMLI</span>@endif
    </div>
    <div class="db-not">Bu departman adına WhatsApp mesajları işletmenin bağlı WhatsApp hattından gönderilir; buraya departmanın iletişim numarasını yaz (tedarikçiye cevap için).</div>
    <form method="POST" action="/departman-baglanti/whatsapp" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
      @csrf<input type="hidden" name="dep" value="{{ $dep->id }}">
      <div style="flex:1;min-width:200px"><label class="rk-lbl">WhatsApp Numarası</label><input class="rk-inp" name="wa_numara" value="{{ $dep->wa_numara ?? '' }}" placeholder="90 5xx xxx xx xx"></div>
      <button class="rk-btn db-btn-w" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">save</span> Kaydet</button>
    </form>
  </div>

</div>
</div>
@endsection

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

  {{-- GMAIL (OAuth tek-tık — opsiyonel; yalnız OAuth uygulaması tanımlı VEYA bağlıysa görünür) --}}
  @if ($googleVar || $google)
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
      <div class="db-not">Tek-tık Gmail için bir kez <a href="/entegrasyonlar" style="color:var(--mor1);font-weight:700">Entegrasyonlar</a> sayfasından kendi Google anahtarını gir. <b>Ya da</b> hemen aşağıdaki <b>Kurumsal E-posta (SMTP)</b> kartına Gmail uygulama şifreni girip bağlan (en pratik).</div>
    @endif
  </div>

  @endif

  {{-- OUTLOOK / 365 (OAuth tek-tık — opsiyonel; yalnız OAuth uygulaması tanımlı VEYA bağlıysa görünür) --}}
  @if ($graphVar || $graph)
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
      <div class="db-not">Tek-tık Outlook için bir kez <a href="/entegrasyonlar" style="color:var(--mor1);font-weight:700">Entegrasyonlar</a> sayfasından kendi Azure anahtarını gir. <b>Ya da</b> aşağıdaki <b>Kurumsal E-posta (SMTP)</b> kartına Outlook uygulama şifreni gir.</div>
    @endif
  </div>

  @endif

  {{-- KURUMSAL SMTP --}}
  <div class="db-kart">
    <div class="db-h">
      <span class="ik db-btn-s"><span class="material-icons-outlined" style="font-size:20px">dns</span></span>
      <span class="b">Kurumsal E-posta (SMTP)</span>
      @if ($smtp)
        @php $st = $smtp->son_test ?? null; @endphp
        <span class="db-durum" style="margin-left:auto;background:{{ $st==='ok' ? 'var(--yesil)' : ($st==='hata' ? 'var(--kirmizi)' : 'var(--sub)') }}">{{ $st==='ok' ? '✅ ÇALIŞIYOR' : ($st==='hata' ? '❌ HATA' : 'TANIMLI') }}</span>
      @endif
    </div>
    @if ($smtp && ($smtp->son_test_not ?? null))
      <div class="db-not" style="color:{{ ($smtp->son_test ?? '')==='ok' ? 'var(--yesil)' : 'var(--kirmizi)' }};font-weight:600">{{ $smtp->son_test_not }}@if ($smtp->son_test_at ?? null) · {{ \Carbon\Carbon::parse($smtp->son_test_at)->diffForHumans() }}@endif</div>
    @endif
    <div class="db-not">Kendi mail sunucun (ör. mail.firman.com) veya Gmail/Outlook <b>uygulama şifresi</b> ile gönderim. Okuma için sunucuda IMAP gerekir (opsiyonel).</div>
    <form method="POST" action="/departman-baglanti/smtp">
      @csrf<input type="hidden" name="dep" value="{{ $dep->id }}">
      <label class="rk-lbl">Sağlayıcı — seç, sunucu ayarları otomatik dolsun</label>
      <select class="rk-inp" id="f_saglayici" onchange="saglayiciDoldur(this.value)" style="margin-bottom:10px">
        <option value="">— Sağlayıcı seç —</option>
        <option value="gmail">Gmail / Google Workspace</option>
        <option value="hotmail">Outlook.com / Hotmail / Live (kişisel)</option>
        <option value="outlook">Microsoft 365 / Exchange Online (bulut)</option>
        <option value="onprem">On-prem Exchange / Kendi Mail Sunucun</option>
        <option value="diger">Diğer (IMAP/SMTP elle gir)</option>
      </select>
      <div class="rk-sub" style="font-size:11px;margin:-4px 2px 10px">
        ⚠️ <b>Microsoft bulut hesapları</b> (Outlook.com, Hotmail, Microsoft 365, Exchange Online) artık uygulama şifresiyle SMTP'yi <b>kapattı</b> → bunlar için <a href="/entegrasyonlar" style="color:var(--mor1);font-weight:700">OAuth</a> kullan (hem gönderir hem okur, imap eklentisi gerekmez).
        <b>Kendi/on-prem mail sunucun</b> ise aşağıya bilgileri elle gir.
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px"><label class="rk-lbl">E-posta Adresi</label><input class="rk-inp" name="email" type="email" value="{{ $smtp->email ?? '' }}" placeholder="satinalma@firma.com" required></div>
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Gönderen Adı</label><input class="rk-inp" name="gonderen_ad" value="{{ $smtp->gonderen_ad ?? '' }}" placeholder="{{ $dep->ad }}"></div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:2;min-width:200px"><label class="rk-lbl">SMTP Sunucu</label><input class="rk-inp" id="f_smtp_host" name="smtp_host" value="{{ $smtp->smtp_host ?? '' }}" placeholder="smtp.gmail.com / smtp.office365.com"></div>
        <div style="width:90px"><label class="rk-lbl">Port</label><input class="rk-inp" id="f_smtp_port" name="smtp_port" type="number" value="{{ $smtp->smtp_port ?? 587 }}"></div>
        <div style="width:110px"><label class="rk-lbl">Güvenlik</label><select class="rk-inp" id="f_smtp_guvenlik" name="smtp_guvenlik">
          @foreach (['tls'=>'TLS','ssl'=>'SSL','yok'=>'Yok'] as $k=>$v)<option value="{{ $k }}" @selected(($smtp->smtp_guvenlik ?? 'tls')===$k)>{{ $v }}</option>@endforeach
        </select></div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Kullanıcı Adı</label><input class="rk-inp" name="smtp_user" value="{{ $smtp->smtp_user ?? '' }}" placeholder="genelde e-posta adresi"></div>
        <div style="flex:1;min-width:160px"><label class="rk-lbl">Şifre / Uygulama Şifresi</label><input class="rk-inp" name="smtp_sifre" type="password" placeholder="{{ $smtp ? 'değiştirmezsen boş bırak' : '' }}"></div>
      </div>
      <div style="border-top:1px dashed var(--line);margin:12px 0 10px;padding-top:10px">
        <div class="rk-lbl" style="margin-bottom:4px">📥 Gelen Mail Okuma (IMAP) — AI tedarikçi yanıtlarını buradan okur</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <div style="flex:2;min-width:200px"><label class="rk-lbl">IMAP Sunucu</label><input class="rk-inp" id="f_imap_host" name="imap_host" value="{{ $smtp->imap_host ?? '' }}" placeholder="imap.gmail.com / outlook.office365.com"></div>
          <div style="width:90px"><label class="rk-lbl">Port</label><input class="rk-inp" id="f_imap_port" name="imap_port" type="number" value="{{ $smtp->imap_port ?? 993 }}"></div>
          <div style="width:110px"><label class="rk-lbl">Güvenlik</label><select class="rk-inp" id="f_imap_guvenlik" name="imap_guvenlik">
            @foreach (['ssl'=>'SSL','tls'=>'TLS','yok'=>'Yok'] as $k=>$v)<option value="{{ $k }}" @selected(($smtp->imap_guvenlik ?? 'ssl')===$k)>{{ $v }}</option>@endforeach
          </select></div>
        </div>
        <div class="rk-sub" style="font-size:11px;margin-top:4px">Boş bırakırsan AI yalnız gönderir, okumaz. Gmail: imap.gmail.com · 993 · SSL</div>
      </div>
      <div style="margin-top:12px">
        <button class="rk-btn db-btn-s" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">save</span> Kaydet ve Test Et</button>
      </div>
    </form>
    @if ($smtp)
    <div style="display:flex;gap:8px;margin-top:8px">
      <form method="POST" action="/departman-baglanti/smtp-test" style="display:inline">
        @csrf<input type="hidden" name="dep" value="{{ $dep->id }}"><button class="rk-btn bos" style="color:var(--mavi)" type="submit"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">wifi_tethering</span> Yeniden Test Et</button>
      </form>
      <form method="POST" action="/departman-baglanti/smtp-sil" style="display:inline" onsubmit="return rkOnayForm(this, 'Kurumsal SMTP kaldırılsın mı?', 'var(--kirmizi)', 'Evet, kaldır')">
        @csrf<input type="hidden" name="dep" value="{{ $dep->id }}"><button class="rk-btn bos" style="color:var(--kirmizi)" type="submit">Kaldır</button>
      </form>
    </div>
    @endif
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

@push('scripts')
<script>
var SAGLAYICI_PRESET = {
  gmail:   { smtp_host:'smtp.gmail.com',       smtp_port:465, smtp_guvenlik:'ssl', imap_host:'imap.gmail.com',        imap_port:993, imap_guvenlik:'ssl' },
  hotmail: { smtp_host:'smtp-mail.outlook.com', smtp_port:587, smtp_guvenlik:'tls', imap_host:'outlook.office365.com', imap_port:993, imap_guvenlik:'ssl' },
  outlook: { smtp_host:'smtp.office365.com',   smtp_port:587, smtp_guvenlik:'tls', imap_host:'outlook.office365.com', imap_port:993, imap_guvenlik:'ssl' }
};
function saglayiciDoldur(v) {
  if (v === 'diger' || v === 'onprem') { document.getElementById('f_smtp_host').value=''; document.getElementById('f_imap_host').value=''; document.getElementById('f_smtp_host').focus(); return; }
  var p = SAGLAYICI_PRESET[v]; if (!p) return;
  document.getElementById('f_smtp_host').value = p.smtp_host;
  document.getElementById('f_smtp_port').value = p.smtp_port;
  document.getElementById('f_smtp_guvenlik').value = p.smtp_guvenlik;
  document.getElementById('f_imap_host').value = p.imap_host;
  document.getElementById('f_imap_port').value = p.imap_port;
  document.getElementById('f_imap_guvenlik').value = p.imap_guvenlik;
}
(function () {
  var sel = document.getElementById('f_saglayici'); if (!sel) return;
  var h = (document.getElementById('f_smtp_host') || {}).value || '';
  if (/gmail/i.test(h)) sel.value = 'gmail';
  else if (/smtp-mail\.outlook/i.test(h)) sel.value = 'hotmail';
  else if (/office365|outlook|exchange/i.test(h)) sel.value = 'outlook';
  else if (h) sel.value = 'diger';
})();
</script>
@endpush
@endsection

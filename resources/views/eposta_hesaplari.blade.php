@extends('layout.app')
@section('rk_ikon', 'alternate_email')
@section('baslik', 'E-posta Hesapları')
@section('title', 'E-posta Hesapları')

@section('content')
<div class="rk-body">
<div style="max-width:800px">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:8px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Gönderim Hesapları</span>
    <span class="sayi">{{ count($hesaplar) }}</span>
    @if ($duzenleyebilir)<button class="rk-btn" style="margin-left:auto;padding:6px 14px;background:var(--mor1)" onclick="hesapAc(null)"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">add</span> Hesap Ekle</button>@endif
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 16px">
    Satın alma; RFQ (fiyat isteği) ve siparişleri bu hesaplardan e-posta ile gönderir. Birden çok hesap ekleyebilirsin (kurumsal, Gmail, Outlook).
    <b>Not:</b> Gmail/Outlook için normal şifre değil <b>uygulama şifresi (app password)</b> gerekir.
  </div>

  <div class="rk-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px">
    @forelse ($hesaplar as $h)
      <div class="rk-card" style="padding:14px">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <span class="material-icons-outlined" style="font-size:24px;color:var(--mor1)">alternate_email</span>
          <div style="flex:1;min-width:0"><div style="font-weight:800;font-size:14px">{{ $h->ad }}</div><div class="rk-sub" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $h->email }}</div></div>
          @if ($h->varsayilan)<span class="rk-rozet" style="background:var(--yesil)">Varsayılan</span>@endif
          @if (!$h->aktif)<span class="rk-rozet" style="background:var(--kirmizi)">Pasif</span>@endif
        </div>
        <div class="rk-sub" style="font-size:11.5px;margin-bottom:10px">{{ $h->smtp_host }}:{{ $h->smtp_port }} ({{ strtoupper($h->smtp_guvenlik) }})</div>
        @if ($duzenleyebilir)
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="rk-btn bos" style="padding:5px 10px" onclick='hesapAc(@json(["id"=>(int)$h->id,"ad"=>$h->ad,"gonderen_ad"=>$h->gonderen_ad,"email"=>$h->email,"smtp_host"=>$h->smtp_host,"smtp_port"=>(int)$h->smtp_port,"smtp_user"=>$h->smtp_user,"smtp_guvenlik"=>$h->smtp_guvenlik,"varsayilan"=>(int)$h->varsayilan,"aktif"=>(int)$h->aktif]))'>Düzenle</button>
          <form method="POST" action="/eposta-hesap-test" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $h->id }}"><button class="rk-btn bos" style="padding:5px 10px;color:var(--mavi)" type="submit">Test Gönder</button></form>
          <form method="POST" action="/eposta-hesap-sil" style="display:inline" onsubmit="return rkOnayForm(this, 'Bu e-posta hesabı silinsin mi?', 'var(--kirmizi)', 'Evet, sil')">@csrf<input type="hidden" name="id" value="{{ $h->id }}"><button class="rk-btn bos" style="padding:5px 8px;color:var(--kirmizi)" type="submit">✕</button></form>
        </div>
        @endif
      </div>
    @empty
      <div class="rk-sub" style="grid-column:1/-1;padding:20px;text-align:center">Henüz e-posta hesabı yok. "Hesap Ekle" ile başla.</div>
    @endforelse
  </div>
</div>
</div>

@if ($duzenleyebilir)
<div id="hesapModal" class="rk-modal-arka" onclick="if(event.target===this)hesapKapat()">
  <div class="rk-modal">
    <form method="POST" action="/eposta-hesap-kaydet">
      @csrf
      <input type="hidden" name="id" id="h_id">
      <div class="rk-modal-head"><span class="b" id="hesapBaslik">Yeni E-posta Hesabı</span><button type="button" class="rk-modal-kapa" onclick="hesapKapat()">&times;</button></div>
      <div class="rk-modal-govde">
        <label class="rk-lbl">Etiket</label><input class="rk-inp" name="ad" id="h_ad" placeholder="Satın Alma Kurumsal">
        <div style="display:flex;gap:10px">
          <div style="flex:1"><label class="rk-lbl">E-posta Adresi</label><input class="rk-inp" name="email" id="h_email" type="email" placeholder="satinalma@firma.com" required></div>
          <div style="flex:1"><label class="rk-lbl">Gönderen Adı</label><input class="rk-inp" name="gonderen_ad" id="h_gad" placeholder="Firma Satın Alma"></div>
        </div>
        <div style="display:flex;gap:10px">
          <div style="flex:2"><label class="rk-lbl">SMTP Sunucu</label><input class="rk-inp" name="smtp_host" id="h_host" placeholder="smtp.gmail.com / smtp.office365.com"></div>
          <div style="width:90px"><label class="rk-lbl">Port</label><input class="rk-inp" name="smtp_port" id="h_port" type="number" value="587"></div>
          <div style="width:110px"><label class="rk-lbl">Güvenlik</label><select class="rk-inp" name="smtp_guvenlik" id="h_sec"><option value="tls">TLS</option><option value="ssl">SSL</option><option value="yok">Yok</option></select></div>
        </div>
        <div style="display:flex;gap:10px">
          <div style="flex:1"><label class="rk-lbl">Kullanıcı Adı</label><input class="rk-inp" name="smtp_user" id="h_user" placeholder="genelde e-posta adresi"></div>
          <div style="flex:1"><label class="rk-lbl">Şifre / Uygulama Şifresi</label><input class="rk-inp" name="smtp_sifre" id="h_pass" type="password" placeholder="değiştirmezsen boş bırak"></div>
        </div>
        <div style="display:flex;gap:16px;margin-top:10px">
          <label class="rk-yetki-satir"><input type="checkbox" name="varsayilan" id="h_vars" class="rk-chk"><span>Varsayılan hesap</span></label>
          <label class="rk-yetki-satir"><input type="checkbox" name="aktif" value="1" id="h_aktif" class="rk-chk" checked><span>Aktif</span></label>
        </div>
        <div class="rk-sub" style="font-size:11px;margin-top:8px">Hazır ayarlar: Gmail → smtp.gmail.com:587 TLS · Outlook/365 → smtp.office365.com:587 TLS</div>
      </div>
      <div class="rk-modal-foot"><button type="button" class="rk-btn bos" onclick="hesapKapat()">Vazgeç</button><button type="submit" class="rk-btn" style="background:var(--mor1)">Kaydet</button></div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function hesapAc(h){
  document.getElementById('h_id').value = h ? h.id : '';
  document.getElementById('hesapBaslik').textContent = h ? ('Düzenle — ' + h.ad) : 'Yeni E-posta Hesabı';
  document.getElementById('h_ad').value = h ? (h.ad||'') : '';
  document.getElementById('h_email').value = h ? (h.email||'') : '';
  document.getElementById('h_gad').value = h ? (h.gonderen_ad||'') : '';
  document.getElementById('h_host').value = h ? (h.smtp_host||'') : '';
  document.getElementById('h_port').value = h ? (h.smtp_port||587) : 587;
  document.getElementById('h_sec').value = h ? (h.smtp_guvenlik||'tls') : 'tls';
  document.getElementById('h_user').value = h ? (h.smtp_user||'') : '';
  document.getElementById('h_pass').value = '';
  document.getElementById('h_vars').checked = h ? (h.varsayilan===1) : false;
  document.getElementById('h_aktif').checked = h ? (h.aktif===1) : true;
  document.getElementById('hesapModal').classList.add('acik');
}
function hesapKapat(){ document.getElementById('hesapModal').classList.remove('acik'); }
</script>
@endpush

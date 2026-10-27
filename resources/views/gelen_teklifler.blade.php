@extends('layout.app')
@section('rk_ikon', 'mark_email_read')
@section('baslik', 'Gelen Teklifler')
@section('title', 'Gelen Teklifler (AI)')

@section('content')
<div class="rk-body">
<div>
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:8px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">AI'nın Okuduğu Fiyat Teklifleri</span>
    <span class="sayi">{{ count($teklifler) }}</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 14px">
    Tedarikçiler RFQ kodlu yanıt gönderince, AI gelen mail/WhatsApp kutusunu okuyup ürün + fiyatı buraya çıkarır.
    @if (!$acikRfq)<br><b>Not:</b> Henüz hiç RFQ göndermemişsin — önce bir departman raporundan "Fiyat Araştır" yap.@endif
  </div>

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;align-items:center">
    @if ($sahip)
    <form method="POST" action="/rfq-test-kur" style="display:inline" onsubmit="return rkOnayForm(this, 'Test için örnek bir malzeme + tedarikçi oluşturulsun mu? (sonra silebilirsin)', 'var(--mavi)', 'Evet, kur')">
      @csrf<button class="rk-btn bos" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">science</span> 1) Test Verisi Kur</button>
    </form>
    <form method="POST" action="/rfq-test-sifirla" style="display:inline" onsubmit="return rkOnayForm(this, 'TÜM test verileri silinsin mi? (gelen teklifler, RFQ&apos;lar, tekliften oluşan siparişler, TEST tedarikçi/malzeme) — gerçek veriler etkilenmez.', 'var(--kirmizi)', 'Evet, sıfırla')">
      @csrf<button class="rk-btn bos" style="color:var(--kirmizi)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">restart_alt</span> Test Verilerini Sıfırla</button>
    </form>
    @endif
    <a href="/fiyat-arastir-sec" class="rk-btn" style="background:var(--mavi)"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">request_quote</span> 2) Fiyat Araştır (Tedarikçi Seç)</a>
    <form method="POST" action="/gelen-teklifler/oku" style="display:inline">
      @csrf<button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">download</span> 3) Şimdi Teklifleri Oku</button>
    </form>
  </div>
  <div class="rk-sub" style="font-size:11.5px;margin:0 2px 16px">Test akışı: <b>1)</b> örnek veri kur → <b>2)</b> RFQ gönder (kritik ürünlerin tedarikçilerine mail gider) → Gmail'ine gelen RFQ mailine fiyatla yanıt ver → <b>3)</b> AI okusun.</div>

  @if (count($teklifler))
  <form method="POST" action="/gelen-teklifler/siparis">
    @csrf
    <div class="rk-sub" style="font-size:12px;margin:0 2px 8px">✅ <b>EN UCUZ</b> teklifler otomatik işaretli. İstediğini değiştir, sonra <b>sipariş oluştur</b>.</div>
    <div class="rk-card" style="padding:0;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead>
          <tr style="background:var(--card2);text-align:left">
            <th style="padding:10px 12px;width:34px"></th>
            <th style="padding:10px 12px">Tedarikçi</th>
            <th style="padding:10px 12px">Ürün</th>
            <th style="padding:10px 12px;text-align:right">Fiyat</th>
            <th style="padding:10px 12px">Kanal</th>
            <th style="padding:10px 12px">RFQ</th>
            <th style="padding:10px 12px">Zaman</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($teklifler as $t)
          <tr style="border-top:1px solid var(--line);{{ ($t->en_ucuz ?? false) ? 'background:rgba(16,185,129,.07)' : '' }}">
            <td style="padding:10px 12px"><input type="checkbox" name="teklif[]" value="{{ $t->id }}" class="rk-chk" @if($t->en_ucuz ?? false) checked @endif></td>
            <td style="padding:10px 12px;font-weight:600">{{ $t->tedarikci_ad }}</td>
            <td style="padding:10px 12px">{{ $t->malzeme_ad ?: $t->urun_ad }} @if($t->en_ucuz ?? false)<span class="rk-rozet" style="background:var(--yesil);margin-left:4px">EN UCUZ</span>@endif @if(!$t->malzeme_id)<span class="rk-rozet" style="background:var(--amber);margin-left:4px" title="Stok malzemesine bağlanamadı, siparişe eklenemez">eşleşmedi</span>@endif</td>
            <td style="padding:10px 12px;text-align:right;font-weight:800;color:var(--yesil)">{{ number_format((float)$t->fiyat, 2, ',', '.') }} ₺</td>
            <td style="padding:10px 12px"><span class="rk-rozet" style="background:{{ $t->kaynak==='wa' ? 'var(--yesil)' : 'var(--mavi)' }}">{{ $t->kaynak==='wa' ? 'WhatsApp' : 'E-posta' }}</span></td>
            <td style="padding:10px 12px;color:var(--sub)">RFQ-{{ $t->rfq_id }}</td>
            <td style="padding:10px 12px;color:var(--sub);font-size:11.5px">{{ \Carbon\Carbon::parse($t->created_at)->diffForHumans() }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <label class="rk-lbl" style="margin:0">İstenen teslim tarihi (ops.):</label>
      <input type="date" name="beklenen_teslim" class="rk-inp" style="width:auto;padding:7px 10px">
      <button class="rk-btn" style="background:var(--yesil)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">shopping_cart_checkout</span> Sipariş Oluştur ve Tedarikçiye Gönder</button>
    </div>
    <div class="rk-sub" style="font-size:11.5px;margin:6px 2px 0">Tek tıkta: işaretli teklifler tedarikçiye göre gruplanır, sipariş oluşur (KDV %10) ve <b>otomatik mail + WhatsApp</b> ile onay linki gönderilir. Detaya girmene gerek yok.</div>
  </form>
  @else
  <div class="rk-card" style="padding:28px;text-align:center">
    <span class="material-icons-outlined" style="font-size:40px;color:var(--sub)">inbox</span>
    <div style="font-weight:700;margin-top:8px">Henüz teklif okunmadı</div>
    <div class="rk-sub" style="font-size:12.5px;margin-top:6px;line-height:1.6">
      Test için: <b>"Test Verisi Kur"</b> → bir departman raporunda <b>"Fiyat Araştır"</b> (RFQ-N üretir) →
      bağlı mail kutuna RFQ kodlu bir fiyat yanıtı gelsin → <b>"Şimdi Teklifleri Oku"</b>.
    </div>
  </div>
  @endif
</div>
</div>
@endsection

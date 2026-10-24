@extends('layout.app')
@section('rk_ikon', 'mark_email_read')
@section('baslik', 'Gelen Teklifler')
@section('title', 'Gelen Teklifler (AI)')

@section('content')
<div class="rk-body">
<div style="max-width:960px">
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

  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <form method="POST" action="/gelen-teklifler/oku" style="display:inline">
      @csrf<button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">download</span> Şimdi Teklifleri Oku</button>
    </form>
    @if ($sahip)
    <form method="POST" action="/rfq-test-kur" style="display:inline" onsubmit="return rkOnayForm(this, 'Test için örnek bir malzeme + tedarikçi oluşturulsun mu? (sonra silebilirsin)', 'var(--mavi)', 'Evet, kur')">
      @csrf<button class="rk-btn bos" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">science</span> Test Verisi Kur</button>
    </form>
    @endif
  </div>

  @if (count($teklifler))
  <div class="rk-card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="background:var(--card2);text-align:left">
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
        <tr style="border-top:1px solid var(--line)">
          <td style="padding:10px 12px;font-weight:600">{{ $t->tedarikci_ad }}</td>
          <td style="padding:10px 12px">{{ $t->malzeme_ad ?: $t->urun_ad }}</td>
          <td style="padding:10px 12px;text-align:right;font-weight:800;color:var(--yesil)">{{ number_format((float)$t->fiyat, 2, ',', '.') }} ₺</td>
          <td style="padding:10px 12px"><span class="rk-rozet" style="background:{{ $t->kaynak==='wa' ? 'var(--yesil)' : 'var(--mavi)' }}">{{ $t->kaynak==='wa' ? 'WhatsApp' : 'E-posta' }}</span></td>
          <td style="padding:10px 12px;color:var(--sub)">RFQ-{{ $t->rfq_id }}</td>
          <td style="padding:10px 12px;color:var(--sub);font-size:11.5px">{{ \Carbon\Carbon::parse($t->created_at)->diffForHumans() }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
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

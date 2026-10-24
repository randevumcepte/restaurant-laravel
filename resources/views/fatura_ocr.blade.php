@extends('layout.app')
@section('rk_ikon', 'document_scanner')
@section('baslik', 'Fatura OCR')
@section('title', 'Fatura OCR (AI)')

@section('content')
<div class="rk-body">
<div style="max-width:820px">
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:8px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Fatura / İrsaliye Oku (AI)</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 14px">Faturanın fotoğrafını ya da PDF'ini yükle; AI kalemleri (ürün, miktar, fiyat) ve toplamları otomatik çıkarır.</div>

  <div class="rk-card" style="padding:16px;margin-bottom:16px">
    <form method="POST" action="/fatura-ocr/oku" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap" onsubmit="document.getElementById('ocrBtn').disabled=true;document.getElementById('ocrBtn').innerText='Okunuyor…'">
      @csrf
      <input type="file" name="fatura" accept="image/*,application/pdf" required class="rk-inp" style="flex:1;min-width:220px;padding:8px">
      <button id="ocrBtn" class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">document_scanner</span> Oku</button>
    </form>
    <div class="rk-sub" style="font-size:11px;margin-top:8px">JPG / PNG / PDF · en fazla 8MB. Net, iyi aydınlatılmış foto daha iyi okunur.</div>
  </div>

  @if ($sonuc)
    @php $k = $sonuc['kalemler'] ?? []; @endphp
    <div class="rk-bolum" style="margin:6px 0 8px"><span class="cubuk" style="background:var(--yesil)"></span><span class="b">Okunan Fatura</span></div>
    <div class="rk-card" style="padding:14px;margin-bottom:12px">
      <div style="display:flex;gap:24px;flex-wrap:wrap">
        <div><div class="rk-sub" style="font-size:11px">Tedarikçi</div><div style="font-weight:700">{{ $sonuc['tedarikci'] ?: '—' }}</div></div>
        <div><div class="rk-sub" style="font-size:11px">Tarih</div><div style="font-weight:700">{{ $sonuc['tarih'] ?: '—' }}</div></div>
        <div><div class="rk-sub" style="font-size:11px">Genel Toplam</div><div style="font-weight:800;color:var(--mor1)">{{ number_format((float)($sonuc['genel_toplam']??0),2,',','.') }} ₺</div></div>
      </div>
    </div>
    @if (count($k))
    <div class="rk-card" style="padding:0;overflow:hidden">
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr style="background:var(--card2);text-align:left">
          <th style="padding:10px 12px">Ürün</th><th style="padding:10px 12px;text-align:right">Miktar</th><th style="padding:10px 12px;text-align:right">Birim Fiyat</th><th style="padding:10px 12px;text-align:right">Tutar</th>
        </tr></thead>
        <tbody>
          @foreach ($k as $kalem)
          <tr style="border-top:1px solid var(--line)">
            <td style="padding:10px 12px;font-weight:600">{{ $kalem['urun'] ?? '—' }}</td>
            <td style="padding:10px 12px;text-align:right">{{ rtrim(rtrim(number_format((float)($kalem['miktar']??0),3,',','.'),'0'),',') }} {{ $kalem['birim'] ?? '' }}</td>
            <td style="padding:10px 12px;text-align:right">{{ number_format((float)($kalem['birim_fiyat']??0),2,',','.') }} ₺</td>
            <td style="padding:10px 12px;text-align:right;font-weight:700">{{ number_format((float)($kalem['tutar']??0),2,',','.') }} ₺</td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr style="border-top:2px solid var(--line)"><td colspan="3" style="padding:8px 12px;text-align:right;color:var(--sub)">Ara Toplam</td><td style="padding:8px 12px;text-align:right">{{ number_format((float)($sonuc['ara_toplam']??0),2,',','.') }} ₺</td></tr>
          <tr><td colspan="3" style="padding:4px 12px;text-align:right;color:var(--sub)">KDV</td><td style="padding:4px 12px;text-align:right">{{ number_format((float)($sonuc['kdv']??0),2,',','.') }} ₺</td></tr>
          <tr><td colspan="3" style="padding:8px 12px;text-align:right;font-weight:800">GENEL TOPLAM</td><td style="padding:8px 12px;text-align:right;font-weight:800;color:var(--mor1)">{{ number_format((float)($sonuc['genel_toplam']??0),2,',','.') }} ₺</td></tr>
        </tfoot>
      </table>
    </div>
    <div class="rk-sub" style="font-size:11.5px;margin-top:8px">💡 AI okuması; rakamları faturayla karşılaştır. (Sonraki adım: bu kalemleri sipariş/mal kabulle 3-way eşleştirme — eklenebilir.)</div>
    @else
    <div class="rk-card" style="padding:20px;text-align:center;color:var(--sub)">Kalem okunamadı. Daha net bir fotoğrafla tekrar dene.</div>
    @endif
  @endif
</div>
</div>
@endsection

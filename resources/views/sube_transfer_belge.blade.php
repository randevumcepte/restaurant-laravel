<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $t->transfer_no }} · Sevk İrsaliyesi</title>
<style>
  *{box-sizing:border-box}
  body{font-family:Arial,Helvetica,sans-serif;color:#0f172a;background:#f1f5f9;margin:0;padding:24px}
  .sayfa{background:#fff;max-width:820px;margin:0 auto;padding:30px 34px;border-radius:10px;box-shadow:0 6px 24px rgba(15,23,42,.08)}
  h1{font-size:20px;margin:0}
  .ust{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #0f172a;padding-bottom:12px;margin-bottom:16px;gap:16px}
  .ust .sag{text-align:right;font-size:12.5px;line-height:1.6}
  .tur{display:inline-block;margin-top:6px;font-size:11px;font-weight:700;border:1px solid #0f172a;border-radius:4px;padding:2px 8px}
  .taraf{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px}
  .taraf div{border:1px solid #cbd5e1;border-radius:6px;padding:10px 12px;font-size:12.5px;line-height:1.55}
  .taraf b{display:block;font-size:11px;color:#475569;letter-spacing:.04em;margin-bottom:3px}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  th,td{border:1px solid #cbd5e1;padding:7px 8px;text-align:left;vertical-align:top}
  th{background:#f1f5f9;font-size:11.5px}
  td.s{text-align:right;white-space:nowrap}
  .lot{font-size:11px;color:#475569}
  .top{margin-top:12px;display:flex;justify-content:flex-end}
  .top table{width:auto;min-width:300px}
  .imza{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:40px;font-size:12px}
  .imza div{border-top:1px solid #0f172a;padding-top:6px;text-align:center}
  .not{font-size:11.5px;color:#475569;margin-top:16px;line-height:1.5}
  .btn{display:block;max-width:820px;margin:0 auto 12px;text-align:right}
  .btn button{background:#0891B2;color:#fff;border:0;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer}
  @media print{body{background:#fff;padding:0}.sayfa{box-shadow:none;border-radius:0;padding:0}.btn{display:none}}
</style>
</head>
<body>
<div class="btn"><button onclick="window.print()">Yazdır / PDF</button></div>
<div class="sayfa">
  <div class="ust">
    <div>
      <h1>SEVK İRSALİYESİ</h1>
      <div class="tur">{{ $t->tur_ad }}</div>
    </div>
    <div class="sag">
      <div><b>Belge No:</b> {{ $t->transfer_no }}</div>
      <div><b>Sevk Tarihi:</b> {{ $t->sevk_at ? \Carbon\Carbon::parse($t->sevk_at)->format('d.m.Y H:i') : '—' }}</div>
      @if ($t->teslim_at)<div><b>Teslim:</b> {{ \Carbon\Carbon::parse($t->teslim_at)->format('d.m.Y H:i') }}</div>@endif
    </div>
  </div>
  <div class="taraf">
    <div><b>GÖNDEREN</b>{{ $gon['unvan'] }}<br>{{ $t->gonderen_ad }}@if ($gon['vkn'])<br>VKN: {{ $gon['vkn'] }}@endif @if ($gon['adres'])<br>{{ $gon['adres'] }}@endif</div>
    <div><b>ALICI</b>{{ $alan['unvan'] }}<br>{{ $t->alan_ad }}@if ($alan['vkn'])<br>VKN: {{ $alan['vkn'] }}@endif @if ($alan['adres'])<br>{{ $alan['adres'] }}@endif</div>
  </div>
  <table>
    <thead><tr><th>#</th><th>Ürün</th><th>Lot / SKT</th><th class="s">Gönderilen</th><th class="s">Teslim alınan</th>@if ($t->tur !== 'ic')<th class="s">Birim fiyat</th><th class="s">Tutar</th>@else<th class="s">Birim maliyet</th>@endif</tr></thead>
    <tbody>
    @foreach ($t->kalemler as $i => $k)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $k->ad }}</td>
        <td class="lot">@forelse ($k->lotlar as $l){{ $l['lot_no'] ?: 'Lotsuz' }}{{ !empty($l['skt']) ? ' · SKT ' . \Carbon\Carbon::parse($l['skt'])->format('d.m.Y') : '' }} ({{ rtrim(rtrim(number_format((float) $l['miktar'], 3, ',', '.'), '0'), ',') }})<br>@empty — @endforelse</td>
        <td class="s">{{ rtrim(rtrim(number_format((float) $k->gonderilen, 3, ',', '.'), '0'), ',') }} {{ $k->birim }}</td>
        <td class="s">{{ $k->teslim_alinan !== null ? rtrim(rtrim(number_format((float) $k->teslim_alinan, 3, ',', '.'), '0'), ',') . ' ' . $k->birim : '—' }}</td>
        @if ($t->tur !== 'ic')
          <td class="s">{{ number_format((float) $k->birim_fiyat, 4, ',', '.') }} ₺</td>
          <td class="s">{{ number_format((float) ($k->teslim_alinan ?? $k->gonderilen) * (float) $k->birim_fiyat, 2, ',', '.') }} ₺</td>
        @else
          <td class="s">{{ number_format((float) $k->birim_maliyet, 4, ',', '.') }} ₺</td>
        @endif
      </tr>
    @endforeach
    </tbody>
  </table>
  @if ($t->tur !== 'ic' && (float) $t->net_toplam > 0)
    <div class="top"><table>
      <tr><td>Ara toplam</td><td class="s">{{ number_format((float) $t->net_toplam, 2, ',', '.') }} ₺</td></tr>
      <tr><td>KDV %{{ rtrim(rtrim(number_format((float) $t->kdv_orani, 2, ',', '.'), '0'), ',') }}</td><td class="s">{{ number_format((float) $t->kdv_toplam, 2, ',', '.') }} ₺</td></tr>
      <tr><th>Genel toplam</th><th class="s">{{ number_format((float) $t->net_toplam + (float) $t->kdv_toplam, 2, ',', '.') }} ₺</th></tr>
    </table></div>
  @elseif ($t->tur === 'ic')
    <div class="top"><table><tr><th>Maliyet toplamı</th><th class="s">{{ number_format((float) $t->maliyet_toplam, 2, ',', '.') }} ₺</th></tr></table></div>
  @endif
  <div class="imza"><div>Teslim eden<br>{{ $t->sevk_eden_ad }}</div><div>Teslim alan<br>{{ $t->teslim_alan_ad }}</div></div>
  <div class="not">
    @if ($t->tur === 'ic')
      Aynı vergi numaralı şubeler arası iç sevktir; satış niteliği taşımaz, maliyet bedeliyle düzenlenmiştir.
    @else
      Farklı vergi numaralı firmalar arası satıştır. Yasal fatura e-Fatura / e-Arşiv olarak ayrıca düzenlenmelidir; bu belge sevk ve mutabakat içindir.
    @endif
    Belge ResteOS tarafından oluşturulmuştur.
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sipariş {{ $s->siparis_no }} — Onay</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
<style>
  :root{--mor:#7C3AED;--mor2:#4F46E5;--gold:#F6CE63;--ink:#1e293b;--sub:#64748b;--line:#e8e8f0;--yesil:#10B981;--amber:#F59E0B;--kirmizi:#F43F5E}
  *{box-sizing:border-box}
  body{margin:0;background:#f1f2f7;color:var(--ink);font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;padding:24px 12px}
  .kagit{max-width:780px;margin:0 auto;background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.12);overflow:hidden}
  .ust{background:linear-gradient(135deg,var(--mor),var(--mor2));color:#fff;padding:28px 32px;position:relative}
  .ust .marka{font-size:22px;font-weight:900;letter-spacing:.3px}
  .ust .tip{font-size:12px;letter-spacing:3px;text-transform:uppercase;opacity:.85;margin-top:2px}
  .ust .no{position:absolute;right:32px;top:28px;text-align:right}
  .ust .no .b{font-size:12px;opacity:.8}.ust .no .v{font-size:18px;font-weight:800}
  .gold{height:4px;background:linear-gradient(90deg,var(--gold),#e6b84f)}
  .govde{padding:26px 32px}
  .banner{border-radius:12px;padding:14px 16px;margin-bottom:20px;font-weight:700;display:flex;align-items:center;gap:10px;font-size:14px}
  .b-ok{background:rgba(16,185,129,.12);color:#0b805e;border:1px solid rgba(16,185,129,.35)}
  .b-deg{background:rgba(245,158,11,.12);color:#9a6a06;border:1px solid rgba(245,158,11,.35)}
  .b-bek{background:rgba(124,58,237,.08);color:var(--mor);border:1px solid rgba(124,58,237,.25)}
  .taraf{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:20px}
  .taraf .kart{flex:1;min-width:220px;background:#f8f8fc;border:1px solid var(--line);border-radius:12px;padding:14px 16px}
  .taraf .kart .et{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--sub);font-weight:700;margin-bottom:4px}
  .taraf .kart .ad{font-size:15px;font-weight:800}
  .taraf .kart .sat{font-size:12.5px;color:var(--sub);margin-top:2px}
  table{width:100%;border-collapse:collapse;margin:6px 0 2px}
  thead th{background:#f3f3fa;color:var(--sub);font-size:11px;text-transform:uppercase;letter-spacing:.4px;text-align:left;padding:10px 12px}
  thead th.sag{text-align:right}
  tbody td{padding:11px 12px;border-top:1px solid var(--line);font-size:13.5px}
  tbody td.sag{text-align:right}
  .toplamlar{margin-top:14px;margin-left:auto;width:280px}
  .toplamlar .r{display:flex;justify-content:space-between;padding:5px 0;font-size:13.5px;color:var(--sub)}
  .toplamlar .r.genel{border-top:2px solid var(--ink);margin-top:6px;padding-top:10px;font-size:18px;font-weight:900;color:var(--mor)}
  .teslim{margin-top:18px;background:rgba(124,58,237,.06);border:1px dashed rgba(124,58,237,.3);border-radius:10px;padding:12px 14px;font-size:13.5px}
  .aksiyon{margin-top:24px;border-top:1px solid var(--line);padding-top:20px}
  .btn{display:inline-flex;align-items:center;gap:7px;border:0;border-radius:11px;padding:13px 22px;font-size:15px;font-weight:800;cursor:pointer;color:#fff}
  .btn-onay{background:var(--yesil)}
  .btn-deg{background:#fff;color:var(--amber);border:2px solid var(--amber)}
  .btn-print{background:#fff;color:var(--mor);border:2px solid var(--mor)}
  textarea{width:100%;border:1px solid var(--line);border-radius:10px;padding:11px;font-size:14px;font-family:inherit;margin-top:8px;resize:vertical}
  .alt{text-align:center;color:var(--sub);font-size:11.5px;padding:18px}
  .flash{max-width:780px;margin:0 auto 14px;border-radius:12px;padding:13px 16px;font-weight:700;font-size:14px}
  .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
  .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
  @media print{body{background:#fff;padding:0}.kagit{box-shadow:none;max-width:100%}.aksiyon,.yazdir-bar,.flash{display:none!important}}
</style>
</head>
<body>
@php
  $tl = fn($v)=>number_format((float)$v,2,',','.').' ₺';
  $durum = $s->onay_durum ?? 'bekliyor';
@endphp

@if (session('ok')) <div class="flash ok">{{ session('ok') }}</div> @endif
@if (session('hata')) <div class="flash err">{{ session('hata') }}</div> @endif

<div class="yazdir-bar" style="max-width:780px;margin:0 auto 12px;text-align:right">
  <button class="btn btn-print" onclick="window.print()"><span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır / PDF</button>
</div>

<div class="kagit">
  <div class="ust">
    <div class="marka">{{ $sube->ad ?? 'İşletme' }}</div>
    <div class="tip">Satın Alma Siparişi</div>
    <div class="no"><div class="b">Sipariş No</div><div class="v">{{ $s->siparis_no }}</div><div class="b" style="margin-top:4px">{{ $s->tarih ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : '' }}</div></div>
  </div>
  <div class="gold"></div>
  <div class="govde">

    @if ($durum === 'onaylandi')
      <div class="banner b-ok"><span class="material-icons-outlined">check_circle</span> Bu siparişi ONAYLADINIZ{{ $s->onay_at ? ' · '.\Carbon\Carbon::parse($s->onay_at)->format('d.m.Y H:i') : '' }}. Teşekkürler!</div>
    @elseif ($durum === 'degisiklik')
      <div class="banner b-deg"><span class="material-icons-outlined">edit_note</span> Değişiklik talebiniz iletildi. Satın alma sizinle iletişime geçecek.</div>
    @else
      <div class="banner b-bek"><span class="material-icons-outlined">hourglass_top</span> Lütfen siparişi inceleyip <b>onaylayın</b> veya <b>değişiklik isteyin</b>.</div>
    @endif

    <div class="taraf">
      <div class="kart">
        <div class="et">Sipariş Veren (Alıcı)</div>
        <div class="ad">{{ $sube->ad ?? 'İşletme' }}</div>
        @if (!empty($sube->telefon))<div class="sat">{{ $sube->telefon }}</div>@endif
        @if (!empty($sube->adres))<div class="sat">{{ $sube->adres }}</div>@endif
      </div>
      <div class="kart">
        <div class="et">Tedarikçi</div>
        <div class="ad">{{ $ted->ad ?? '—' }}</div>
        @if (!empty($ted->yetkili))<div class="sat">Yetkili: {{ $ted->yetkili }}</div>@endif
        @if (!empty($ted->telefon))<div class="sat">{{ $ted->telefon }}</div>@endif
      </div>
    </div>

    <table>
      <thead><tr><th>Ürün</th><th class="sag">Miktar</th><th class="sag">Birim Fiyat</th><th class="sag">Tutar</th></tr></thead>
      <tbody>
        @foreach ($kalemler as $k)
        <tr>
          <td style="font-weight:600">{{ $k->malzeme }}</td>
          <td class="sag">{{ rtrim(rtrim(number_format((float)$k->miktar,3,',','.'),'0'),',') }} {{ $k->birim }}</td>
          <td class="sag">{{ $tl($k->birim_fiyat) }}</td>
          <td class="sag" style="font-weight:700">{{ $tl($k->satir_toplam) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="toplamlar">
      <div class="r"><span>Ara Toplam</span><span>{{ $tl($s->ara_toplam) }}</span></div>
      <div class="r"><span>KDV</span><span>{{ $tl($s->kdv_toplam) }}</span></div>
      <div class="r genel"><span>GENEL TOPLAM</span><span>{{ $tl($s->genel_toplam) }}</span></div>
    </div>

    @if (!empty($s->beklenen_teslim))
      <div class="teslim"><b>İstenen Teslim Tarihi:</b> {{ \Carbon\Carbon::parse($s->beklenen_teslim)->format('d.m.Y') }} <span style="color:var(--sub)">— farklı bir tarih uygunsa "Değişiklik İste" ile belirtin.</span></div>
    @endif

    @if ($durum !== 'onaylandi')
    <div class="aksiyon">
      <form method="POST" action="/siparis/onay/{{ $token }}/onayla" style="display:inline-block;margin-bottom:14px">
        @csrf
        <button class="btn btn-onay" type="submit"><span class="material-icons-outlined" style="font-size:20px">check_circle</span> Siparişi Onayla</button>
      </form>
      <div style="margin-top:6px">
        <form method="POST" action="/siparis/onay/{{ $token }}/degisiklik">
          @csrf
          <label style="font-weight:700;font-size:13.5px;color:var(--sub)">Değişiklik mi istiyorsun? (teslim tarihi, fiyat, miktar vb.)</label>
          <textarea name="not" rows="3" placeholder="Örn: Teslim tarihini 2 gün ötelemek istiyoruz / dana kıyma fiyatını güncelleyelim...">{{ $s->onay_not ?? '' }}</textarea>
          <button class="btn btn-deg" type="submit" style="margin-top:10px"><span class="material-icons-outlined" style="font-size:19px">edit_note</span> Değişiklik İste</button>
        </form>
      </div>
    </div>
    @endif

  </div>
  <div class="alt">Bu sayfa {{ $sube->ad ?? 'işletme' }} satın alma sistemi tarafından oluşturulmuştur · ResteOS</div>
</div>
</body>
</html>

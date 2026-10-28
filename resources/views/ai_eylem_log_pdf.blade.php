<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>AI Eylem Günlüğü · {{ $bas }} – {{ $bit }}</title>
<style>
*{box-sizing:border-box}
body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1e293b;line-height:1.5;margin:28px}
h1{font-size:22px;margin:0 0 4px}
h2{font-size:14px;margin:18px 0 8px;color:#475569;letter-spacing:.5px;text-transform:uppercase}
.ust{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #7C3AED;padding-bottom:14px;margin-bottom:16px}
.ust .sol .alt{font-size:12.5px;color:#64748b;margin-top:4px}
.ust .sag{text-align:right;font-size:11.5px;color:#64748b}
.ust .sag b{color:#1e293b}
.ozet-kutu{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:12px 16px;margin-bottom:16px;display:flex;gap:28px;flex-wrap:wrap}
.ozet-kutu .k{font-size:11px;color:#64748b;letter-spacing:.3px;text-transform:uppercase;margin-bottom:2px}
.ozet-kutu .v{font-size:18px;font-weight:800;color:#1e293b}
.dogru-ok{background:#dcfce7;border-color:#10B981;color:#047857}
.dogru-hasar{background:#fee2e2;border-color:#F43F5E;color:#9f1239}
table{width:100%;border-collapse:collapse;font-size:11.5px;margin-bottom:14px}
th{background:#f1f5f9;color:#475569;font-size:10.5px;font-weight:800;letter-spacing:.3px;text-align:left;padding:8px 10px;border:1px solid #e2e8f0}
td{padding:7px 10px;border:1px solid #e2e8f0;vertical-align:top}
tr{page-break-inside:avoid}
.mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:10.5px;color:#64748b}
.roz{display:inline-block;padding:2px 7px;border-radius:4px;font-size:10px;font-weight:700;color:#fff}
.r-ok{background:#10B981}.r-red{background:#F59E0B}.r-bek{background:#64748b}.r-zm{background:#94A3B8}.r-yok{background:#4F46E5}.r-yhata{background:#F43F5E}
.footer{margin-top:30px;padding-top:14px;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;display:flex;justify-content:space-between}
.imza-pass{word-break:break-all;max-width:260px}
.yazdir{position:fixed;top:10px;right:10px;padding:8px 16px;background:#7C3AED;color:#fff;border:0;border-radius:8px;font-weight:700;cursor:pointer}
@media print{.yazdir{display:none}body{margin:14mm}}
</style>
</head>
<body>
<button class="yazdir" onclick="window.print()">🖨️ Yazdır / PDF</button>

<div class="ust">
  <div class="sol">
    <h1>AI Eylem Günlüğü — Legal Audit Report</h1>
    <div class="alt">{{ $sube->ad ?? 'ResteOS' }} · {{ $bas }} – {{ $bit }} arası</div>
  </div>
  <div class="sag">
    Üretildi: <b>{{ $uretildiAt->format('d.m.Y H:i:s') }}</b><br>
    Hazırlayan: <b>{{ $bp->ad ?? '-' }}</b> ({{ $bp->rol ?? '' }})
  </div>
</div>

<div class="ozet-kutu {{ $dogrulama['saglam'] ? 'dogru-ok' : 'dogru-hasar' }}">
  <div><div class="k">Toplam Kayıt (dönem)</div><div class="v">{{ count($satirlar) }}</div></div>
  <div><div class="k">Zincir Durumu</div><div class="v">{{ $dogrulama['saglam'] ? '✓ SAĞLAM' : '⚠ HASARLI' }}</div></div>
  <div><div class="k">Toplam Zincir Satırı</div><div class="v">{{ $dogrulama['sayi'] }}</div></div>
  @if (!$dogrulama['saglam'])<div><div class="k">İlk Kırık Satır</div><div class="v">#{{ $dogrulama['ilk_kirik_id'] }}</div></div>@endif
</div>

<h2>Kayıt Listesi</h2>
<table>
  <thead>
    <tr>
      <th style="width:50px">#</th>
      <th style="width:120px">Tarih</th>
      <th style="width:70px">Modül</th>
      <th>Niyet · Özet</th>
      <th style="width:130px">Kullanıcı</th>
      <th style="width:80px">Karar</th>
      <th style="width:80px">Yürütme</th>
      <th style="width:80px">IP</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($satirlar as $s)
      @php
        $k = $kararlar[$s->id] ?? collect();
        $onay = $k->firstWhere('tip', 'onaylandi'); $red = $k->firstWhere('tip', 'reddedildi'); $zm = $k->firstWhere('tip', 'zamanasimi');
        $yurut = $k->firstWhere('tip', 'yurutuldu') ?: $k->firstWhere('tip', 'yurutme_hatasi');
        $kararT = $onay ? 'onaylandi' : ($red ? 'reddedildi' : ($zm ? 'zamanasimi' : 'bekliyor'));
      @endphp
      <tr>
        <td class="mono">#{{ $s->id }}</td>
        <td class="mono">{{ \Carbon\Carbon::parse($s->created_at)->format('d.m.Y H:i:s') }}</td>
        <td><b style="color:#7C3AED">{{ strtoupper($s->modul) }}</b></td>
        <td>
          <div><b>{{ $s->intent }}</b></div>
          <div style="font-size:11px;color:#475569">{{ $s->ozet }}</div>
        </td>
        <td>{{ $s->kullanici_ad }}<br><span class="mono" style="font-size:10px">{{ $s->kullanici_rol }} · ID {{ $s->kullanici_id }}</span></td>
        <td>
          @if ($kararT==='onaylandi')<span class="roz r-ok">ONAY</span>
          @elseif ($kararT==='reddedildi')<span class="roz r-red">RET</span>
          @elseif ($kararT==='zamanasimi')<span class="roz r-zm">Z.AŞIMI</span>
          @else<span class="roz r-bek">BEKL</span>@endif
          @if ($onay)<br><span class="mono" style="font-size:9.5px">{{ \Carbon\Carbon::parse($onay->created_at)->format('H:i:s') }}</span>@endif
        </td>
        <td>
          @if ($yurut)
            @if ($yurut->tip==='yurutuldu')<span class="roz r-yok">BAŞARILI</span>
            @else<span class="roz r-yhata">HATA</span>@endif
            @if ($yurut->sonuc_mesaj)<div style="font-size:10px;color:#64748b;margin-top:2px">{{ $yurut->sonuc_mesaj }}</div>@endif
          @else<span style="color:#94a3b8">—</span>@endif
        </td>
        <td class="mono" style="font-size:10px">{{ $s->ip_adres }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

<h2>Hash İmza Doğrulama</h2>
<p style="font-size:11.5px;color:#475569">Her satırın imzası (SHA256) bir önceki satırın imzasını içerir. Geçmiş bir satır değişirse zincir kırılır ve tespit edilir. Bu raporun oluşturulduğu an itibariyle zincir durumu: <b style="color:{{ $dogrulama['saglam'] ? '#047857' : '#9f1239' }}">{{ $dogrulama['saglam'] ? 'SAĞLAM (tüm satırlar doğrulandı)' : 'HASARLI (zincir bozulmuş)' }}</b>.</p>

@if (count($satirlar) > 0)
<table>
  <thead><tr><th style="width:50px">#</th><th>İmza (SHA256)</th></tr></thead>
  <tbody>
    @foreach ($satirlar as $s)
      <tr><td class="mono">#{{ $s->id }}</td><td class="mono imza-pass">{{ $s->imza }}</td></tr>
    @endforeach
  </tbody>
</table>
@endif

<div class="footer">
  <div>Bu rapor ResteOS tarafından otomatik üretilmiştir · AI eylem günlüğü v1</div>
  <div>Sayfa 1 / 1 · {{ $uretildiAt->format('d.m.Y H:i:s') }}</div>
</div>

</body>
</html>

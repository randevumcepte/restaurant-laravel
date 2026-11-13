<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $dosya }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
@php
  $hucre = function ($val, $tip) {
      if ($val === null || $val === '') return '';
      if ($tip === 'tl' && is_numeric($val)) return number_format((float) $val, 2, ',', '.') . ' ₺';
      if ($tip === 'y' && is_numeric($val)) return '%' . number_format((float) $val, 1, ',', '.');
      if ($tip === 's' && is_numeric($val)) return rtrim(rtrim(number_format((float) $val, 3, ',', '.'), '0'), ',');
      if ($tip === 't' && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $val)) return \Carbon\Carbon::parse($val)->format('d.m.Y');
      return (string) $val;
  };
  $kpi = $v['kpi'] ?? [];
  $tl0 = fn($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
@endphp
<style>
  @page{size:A4 landscape;margin:12mm 10mm}
  *{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1E293B;background:#F1F2F9;font-size:11px}
  .sayfa{max-width:1180px;margin:0 auto;padding:22px}
  .ust{border-radius:16px;padding:20px 24px;color:#fff;background:radial-gradient(120% 140% at 0% 0%,#8B5CF6 0%,#6D28D9 40%,#4338CA 75%,#1E1B4B 100%);display:flex;gap:16px;align-items:center}
  .ust .lg{width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.16);display:grid;place-items:center;font-size:24px}
  .ust h1{margin:0;font-size:20px;font-weight:900}.ust p{margin:3px 0 0;font-size:11.5px;color:rgba(255,255,255,.85)}
  .ust .mk{margin-left:auto;text-align:right;font-size:10.5px;color:rgba(255,255,255,.8)}.ust .mk b{display:block;font-size:14px;color:#fff}
  .arac{display:flex;gap:8px;justify-content:flex-end;margin:12px 0}
  .arac button{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;padding:9px 14px;font-weight:800;font-size:12.5px;cursor:pointer;color:#fff;background:linear-gradient(135deg,#BE123C,#E11D48)}
  .arac button.g{background:#fff;color:#334155;border:1px solid #E2E8F0}
  .kpi{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin:14px 0}
  .kpi div{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:10px 12px;border-left:4px solid var(--c)}
  .kpi span{font-size:10px;color:#64748B;font-weight:700}.kpi b{display:block;font-size:16px;font-weight:900;margin-top:2px;color:var(--c)}
  h2{font-size:14px;margin:20px 0 4px;font-weight:900;color:#4C1D95;display:flex;align-items:center;gap:8px}
  h2::before{content:'';width:4px;height:16px;border-radius:2px;background:linear-gradient(180deg,#EC4899,#7C3AED)}
  .not{font-size:10.5px;color:#64748B;margin:0 0 8px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #E4E7F2;border-radius:10px;overflow:hidden}
  thead{display:table-header-group}
  th{background:#6D28D9;color:#fff;font-size:9.5px;text-transform:uppercase;letter-spacing:.3px;text-align:left;padding:7px 8px;font-weight:800}
  td{padding:6px 8px;border-top:1px solid #EEF0F7;vertical-align:top}
  tr:nth-child(even) td{background:#FAFAFE}
  tr{page-break-inside:avoid}
  .r{text-align:right;white-space:nowrap}
  .bos{padding:16px;text-align:center;color:#64748B;background:#fff;border:1px dashed #CBD5E1;border-radius:10px}
  .ai{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .ai .k{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px;border-top:3px solid var(--c);page-break-inside:avoid}
  .ai .k h3{margin:0 0 6px;font-size:12px;color:var(--c)}
  .ai .m{padding:6px 0;border-top:1px solid #F1F5F9}.ai .m b{display:block;color:#0F172A}
  .ozet{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.55;margin-bottom:10px}
  .alt{margin-top:18px;font-size:9.5px;color:#94A3B8;text-align:center}
  @media print{body{background:#fff}.sayfa{padding:0;max-width:none}.arac{display:none}}
</style>
</head>
<body>
<div class="sayfa">
  <div class="ust">
    <div class="lg">📡</div>
    <div><h1>{{ $baslik }}</h1><p>{{ $alt }}</p></div>
    <div class="mk"><b>ResteOS</b>Fiyat Radarı</div>
  </div>
  <div class="arac">
    <button class="g" onclick="window.close()"><span class="material-icons-outlined" style="font-size:16px">close</span> Kapat</button>
    <button onclick="window.print()"><span class="material-icons-outlined" style="font-size:16px">picture_as_pdf</span> PDF olarak kaydet / Yazdır</button>
  </div>

  @if (in_array($rapor, ['tam', 'zam'], true))
  <div class="kpi">
    <div style="--c:#4F46E5"><span>Toplam alış</span><b>{{ $tl0($kpi['toplam'] ?? 0) }}</b></div>
    <div style="--c:#E11D48"><span>Gizli zam yıllık etkisi</span><b>~{{ $tl0($kpi['zam_etki'] ?? 0) }}</b></div>
    <div style="--c:#0284C7"><span>En ucuzdan alsaydın (yıllık)</span><b>~{{ $tl0($kpi['ted_kayip'] ?? 0) }}</b></div>
    <div style="--c:#059669"><span>Hal üstü fazla (yıllık)</span><b>~{{ $tl0($kpi['hal_fazla'] ?? 0) }}</b></div>
    <div style="--c:#D97706"><span>Fatura · tedarikçi · malzeme</span><b>{{ $kpi['fatura'] ?? 0 }} · {{ $kpi['tedarikci'] ?? 0 }} · {{ $kpi['malzeme'] ?? 0 }}</b></div>
  </div>
  @endif

  @if ($ai)
    <h2>AI Analiz</h2>
    <div class="ozet"><b>Satın alma disiplini puanı: {{ (int) ($ai['skor'] ?? 0) }}/100</b> · {{ $ai['_zaman'] ?? '' }}<br>{{ $ai['ozet'] ?? '' }}</div>
    <div class="ai">
      @foreach (['kritik' => ['Kritik bulgular', '#E11D48'], 'gecmis_hatalar' => ['Geçmiş hatalar', '#D97706'], 'firsatlar' => ['Tasarruf fırsatları', '#059669'], 'gelecek' => ['Gelecek önerileri', '#4F46E5']] as $an => [$bas, $renk])
        @if (!empty($ai[$an]))
          <div class="k" style="--c:{{ $renk }}"><h3>{{ $bas }}</h3>
            @foreach ($ai[$an] as $x)<div class="m"><b>{{ $x['baslik'] ?? '' }}@if (!empty($x['tutar']) && is_numeric($x['tutar'])) · ~{{ $tl0($x['tutar']) }}@endif</b>{{ $x['detay'] ?? '' }}</div>@endforeach
          </div>
        @endif
      @endforeach
      @if (!empty($ai['pazarlik']))
        <div class="k" style="--c:#0284C7"><h3>Pazarlık argümanları</h3>@foreach ($ai['pazarlik'] as $x)<div class="m"><b>{{ $x['tedarikci'] ?? '' }}</b>{{ $x['arguman'] ?? '' }}</div>@endforeach</div>
      @endif
      @if (!empty($ai['aksiyonlar']))
        <div class="k" style="--c:#9333EA"><h3>Yapılacaklar</h3>@foreach ($ai['aksiyonlar'] as $x)<div class="m"><b>[{{ mb_strtoupper($x['oncelik'] ?? '', 'UTF-8') }}] {{ $x['is'] ?? '' }}</b>{{ $x['sorumlu'] ?? '' }}</div>@endforeach</div>
      @endif
    </div>
  @endif

  @foreach ($sayfalar as $s)
    @continue($ai && str_starts_with($s['ad'], 'AI '))
    <h2>{{ $s['ad'] }}</h2>
    @if (!empty($s['not']))<p class="not">{{ $s['not'] }}</p>@endif
    @if (!count($s['sat']))
      <div class="bos">Bu bölümde kayıt yok.</div>
    @else
      <table>
        <thead><tr>@foreach ($s['kol'] as $k)<th class="{{ in_array($k[1], ['tl', 'y', 's'], true) ? 'r' : '' }}">{{ $k[0] }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach ($s['sat'] as $sat)
            <tr>@foreach ($s['kol'] as $i => $k)<td class="{{ in_array($k[1], ['tl', 'y', 's'], true) ? 'r' : '' }}">{{ $hucre($sat[$i] ?? null, $k[1]) }}</td>@endforeach</tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @endforeach
  <div class="alt">ResteOS · Fiyat Radarı · {{ now()->format('d.m.Y H:i') }}</div>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>

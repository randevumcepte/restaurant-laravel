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
  $tl0 = fn($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
  // yüzde rengi: yüksek = kırmızı (isi) ya da yeşil (isiy)
  $isiRenk = function ($val, $ters) {
      if (!is_numeric($val)) return '';
      $v = (float) $val; if ($ters) $v = -$v;
      return $v >= 15 ? '#BE123C' : ($v >= 5 ? '#B45309' : ($v < 0 ? '#047857' : '#334155'));
  };
  $palet = ['#7C3AED', '#F59E0B', '#0EA5E9', '#10B981', '#EC4899', '#4F46E5', '#F43F5E', '#64748B', '#14B8A6', '#A855F7'];
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
  .kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0}
  .kpi div{border-radius:12px;padding:11px 14px;color:#fff;background:var(--c)}
  .kpi span{font-size:10px;font-weight:700;opacity:.9}.kpi b{display:block;font-size:19px;font-weight:900;margin-top:3px}
  .grafikler{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:6px}
  .gk{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;page-break-inside:avoid}
  .gk h3{margin:0 0 9px;font-size:12px;color:#4C1D95}
  .gs{display:flex;align-items:center;gap:8px;margin-bottom:5px;font-size:10.5px}
  .gs .n{width:34%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700}
  .gs .t{flex:1;height:10px;border-radius:99px;background:#EEF0F7;overflow:hidden}.gs .t i{display:block;height:100%;border-radius:99px}
  .gs .v{width:92px;text-align:right;font-weight:800;color:#334155}
  .sutun{display:flex;align-items:flex-end;gap:8px;height:120px;padding-top:6px}
  .sutun .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px}
  .sutun .c{width:100%;max-width:40px;border-radius:6px 6px 2px 2px;background:linear-gradient(180deg,#EC4899,#7C3AED)}
  .sutun .v{font-size:9px;font-weight:800;color:#475569}.sutun .e{font-size:9px;color:#64748B;font-weight:700}
  h2{font-size:14px;margin:20px 0 4px;font-weight:900;color:#4C1D95;display:flex;align-items:center;gap:8px;page-break-after:avoid}
  h2::before{content:'';width:4px;height:16px;border-radius:2px;background:linear-gradient(180deg,#EC4899,#7C3AED)}
  .not{font-size:10.5px;color:#64748B;margin:0 0 8px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #E4E7F2;border-radius:10px;overflow:hidden}
  thead{display:table-header-group}
  th{background:#6D28D9;color:#fff;font-size:9.5px;text-transform:uppercase;letter-spacing:.3px;text-align:left;padding:7px 8px;font-weight:800}
  td{padding:6px 8px;border-top:1px solid #EEF0F7;vertical-align:top}
  tr:nth-child(even) td{background:#FAFAFE}
  tr{page-break-inside:avoid}
  table.genis{font-size:9px;table-layout:fixed}
  table.genis th{font-size:8px;padding:6px 4px;letter-spacing:0;word-break:break-word}
  table.genis td{padding:5px 4px;word-break:break-word}
  tr.top td{background:#F5F3FF;font-weight:900;color:#4C1D95;border-top:2px solid #6D28D9}
  .r{text-align:right;white-space:nowrap}
  td.cb{position:relative}
  td.cb .cbi{position:absolute;left:0;top:3px;bottom:3px;border-radius:0 4px 4px 0;background:rgba(139,92,246,.18)}
  td.cb span{position:relative}
  .onc{display:inline-block;padding:2px 7px;border-radius:99px;font-weight:900;font-size:9.5px}
  .bos{padding:16px;text-align:center;color:#64748B;background:#fff;border:1px dashed #CBD5E1;border-radius:10px}
  .ai{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .ai .k{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px;border-top:3px solid var(--c);page-break-inside:avoid}
  .ai .k h3{margin:0 0 6px;font-size:12px;color:var(--c)}
  .ai .m{padding:6px 0;border-top:1px solid #F1F5F9}.ai .m b{display:block;color:#0F172A}
  .ozet{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;font-size:12px;line-height:1.55;margin-bottom:10px;display:flex;gap:14px;align-items:center}
  .skor{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;flex:0 0 auto}
  .skor div{width:50px;height:50px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:18px;font-weight:900}
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

  @foreach ($sayfalar as $s)
    @if (($s['tip'] ?? '') === 'pano')
      {{-- ===== PANO: özet kartlar + grafikler ===== --}}
      <div class="kpi">
        @foreach ($s['kpi'] as [$et, $deg, $fmt, $renk])
          <div style="--c:#{{ $renk }}"><span>{{ $et }}</span><b>{{ $fmt === 'tl' ? $tl0($deg) : $deg }}</b></div>
        @endforeach
      </div>
      <div class="grafikler">
        @foreach ($s['bloklar'] as $bl)
          @php $mx = max(1, ...array_map(fn($r) => (float) $r[1], $bl['sat'])); @endphp
          <div class="gk">
            <h3>{{ $bl['baslik'] }}</h3>
            @if ($bl['grafik'] === 'col')
              <div class="sutun">
                @foreach ($bl['sat'] as $r)
                  <div class="b"><div class="v">{{ number_format($r[1] / 1000, 1, ',', '.') }}k</div><div class="c" style="height:{{ max(3, round($r[1] / $mx * 92)) }}px"></div><div class="e">{{ $r[0] }}</div></div>
                @endforeach
              </div>
            @else
              @foreach ($bl['sat'] as $i => $r)
                <div class="gs"><div class="n" title="{{ $r[0] }}">{{ $r[0] }}</div>
                  <div class="t"><i style="width:{{ max(2, $r[1] / $mx * 100) }}%;background:{{ $bl['grafik'] === 'pie' ? $palet[$i % 10] : '#' . $bl['renk'] }}"></i></div>
                  <div class="v">{{ $tl0($r[1]) }}@if ($bl['kol'][2][1] === 'y' && is_numeric($r[2] ?? null)) <span style="color:#94A3B8;font-weight:600">· %{{ number_format($r[2], 1, ',', '.') }}</span>@endif</div></div>
              @endforeach
            @endif
          </div>
        @endforeach
      </div>

      @if ($ai)
        {{-- ===== AI değerlendirme kartları (Pano'nun hemen altında) ===== --}}
        <h2>AI Analiz</h2>
        @php $sk = max(0, min(100, (int) ($ai['skor'] ?? 0))); $skr = $sk >= 70 ? '#10B981' : ($sk >= 45 ? '#F59E0B' : '#F43F5E'); @endphp
        <div class="ozet">
          <div class="skor" style="background:conic-gradient({{ $skr }} {{ $sk * 3.6 }}deg,#EEF0F7 0)"><div>{{ $sk }}</div></div>
          <div><b>Satın alma disiplini puanı: {{ $sk }}/100</b> · {{ $ai['_zaman'] ?? '' }}<br>{{ $ai['ozet'] ?? '' }}</div>
        </div>
        <div class="ai">
          @foreach (['kritik' => ['Kritik bulgular', '#E11D48'], 'gecmis_hatalar' => ['Geçmiş hatalar', '#D97706'], 'firsatlar' => ['Tasarruf fırsatları', '#059669'], 'gelecek' => ['Gelecek önerileri', '#4F46E5']] as $an => [$bas, $renk])
            @if (!empty($ai[$an]))
              <div class="k" style="--c:{{ $renk }}"><h3>{{ $bas }}</h3>
                @foreach ($ai[$an] as $xx)<div class="m"><b>{{ $xx['baslik'] ?? '' }}@if (!empty($xx['tutar']) && is_numeric($xx['tutar'])) · ~{{ $tl0($xx['tutar']) }}@endif</b>{{ $xx['detay'] ?? '' }}</div>@endforeach
              </div>
            @endif
          @endforeach
          @if (!empty($ai['pazarlik']))
            <div class="k" style="--c:#0284C7"><h3>Pazarlık argümanları</h3>@foreach ($ai['pazarlik'] as $xx)<div class="m"><b>{{ $xx['tedarikci'] ?? '' }}</b>{{ $xx['arguman'] ?? '' }}</div>@endforeach</div>
          @endif
          @if (!empty($ai['aksiyonlar']))
            <div class="k" style="--c:#9333EA"><h3>Yapılacaklar</h3>@foreach ($ai['aksiyonlar'] as $xx)<div class="m"><b>[{{ ['yuksek' => 'YÜKSEK', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$xx['oncelik'] ?? ''] ?? '' }}] {{ $xx['is'] ?? '' }}</b>{{ $xx['sorumlu'] ?? '' }}</div>@endforeach</div>
          @endif
        </div>
      @endif
      @continue
    @endif

    @continue($ai && !empty($s['ai']))
    {{-- ===== TABLO ===== --}}
    <h2>{{ $s['ad'] }}</h2>
    @if (!empty($s['not']))<p class="not">{{ $s['not'] }}</p>@endif
    @if (!count($s['sat']))
      <div class="bos">Bu bölümde kayıt yok.</div>
    @else
      @php
        $mxK = []; $topK = [];
        foreach ($s['kol'] as $i => $k) {
          $b = $k[3] ?? '';
          if (str_contains($b, 'bar')) $mxK[$i] = max(1, ...array_map(fn($r) => abs((float) ($r[$i] ?? 0)), $s['sat']));
          if (str_contains($b, 'top')) $topK[$i] = array_sum(array_map(fn($r) => (float) ($r[$i] ?? 0), $s['sat']));
        }
      @endphp
      <table class="{{ count($s['kol']) > 10 ? 'genis' : '' }}">
        @php $gTop = array_sum(array_map(fn($k) => $k[2] ?? 14, $s['kol'])); @endphp
        <colgroup>@foreach ($s['kol'] as $k)<col style="width:{{ round(($k[2] ?? 14) / $gTop * 100, 2) }}%">@endforeach</colgroup>
        <thead><tr>@foreach ($s['kol'] as $k)<th class="{{ in_array($k[1], ['tl', 'y', 's'], true) ? 'r' : '' }}">{{ $k[0] }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach ($s['sat'] as $sat)
            <tr>@foreach ($s['kol'] as $i => $k)
              @php $val = $sat[$i] ?? null; $b = $k[3] ?? ''; $sag = in_array($k[1], ['tl', 'y', 's'], true); @endphp
              @if (isset($mxK[$i]) && is_numeric($val))
                <td class="r cb"><div class="cbi" style="width:{{ round(abs($val) / $mxK[$i] * 100) }}%"></div><span>{{ $hucre($val, $k[1]) }}</span></td>
              @elseif (preg_match('/\bisiy?\b/', $b, $mm))
                <td class="{{ $sag ? 'r' : '' }}" style="font-weight:800;color:{{ $isiRenk($val, $mm[0] === 'isiy') }}">{{ $hucre($val, $k[1]) }}</td>
              @elseif (str_contains($b, 'onc'))
                @php $oc = ['YÜKSEK' => ['#FFE4E6', '#9F1239'], 'ORTA' => ['#FEF3C7', '#92400E'], 'DÜŞÜK' => ['#F1F5F9', '#475569']][$val] ?? ['#F1F5F9', '#475569']; @endphp
                <td><span class="onc" style="background:{{ $oc[0] }};color:{{ $oc[1] }}">{{ $val }}</span></td>
              @else
                <td class="{{ $sag ? 'r' : '' }}">{{ $hucre($val, $k[1]) }}</td>
              @endif
            @endforeach</tr>
          @endforeach
          @if ($topK)
            <tr class="top">@foreach ($s['kol'] as $i => $k)<td class="{{ isset($topK[$i]) ? 'r' : '' }}">{{ $i === 0 ? 'TOPLAM' : (isset($topK[$i]) ? $hucre($topK[$i], $k[1]) : '') }}</td>@endforeach</tr>
          @endif
        </tbody>
      </table>
    @endif
  @endforeach
  <div class="alt">ResteOS · Fiyat Radarı · {{ now()->format('d.m.Y H:i') }}</div>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>

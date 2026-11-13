<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $dosya }}</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined">
@php
  $tema = '#' . ltrim($tema ?? '7C3AED', '#');
  $aiRapor = ($rapor ?? '') === 'ai';
  $hucre = function ($val, $tip) {
      if ($val === null || $val === '') return '';
      if ($tip === 'tl' && is_numeric($val)) return number_format((float) $val, 2, ',', '.') . ' ₺';
      if ($tip === 'y' && is_numeric($val)) return ((float) $val < 0 ? '-%' : '%') . number_format(abs((float) $val), 1, ',', '.');
      if ($tip === 's' && is_numeric($val)) return rtrim(rtrim(number_format((float) $val, 3, ',', '.'), '0'), ',');
      if ($tip === 't' && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $val)) return \Carbon\Carbon::parse($val)->format('d.m.Y');
      return (string) $val;
  };
  $tl0 = fn($x) => number_format(round((float) $x), 0, ',', '.') . ' ₺';
  $kpiDeg = fn($deg, $fmt) => $fmt === 'tl' ? $tl0($deg) : ($fmt === 'y' ? $hucre($deg, 'y') : $deg);
  $blokDeg = fn($val, $tip) => $tip === 'tl' ? $tl0($val) : $hucre($val, $tip);
  $palet = ['#7C3AED', '#F59E0B', '#0EA5E9', '#10B981', '#EC4899', '#4F46E5', '#F43F5E', '#64748B', '#14B8A6', '#A855F7'];
  $hexA = fn($hex, $a) => 'rgba(' . hexdec(substr(ltrim($hex, '#'), 0, 2)) . ',' . hexdec(substr(ltrim($hex, '#'), 2, 2)) . ',' . hexdec(substr(ltrim($hex, '#'), 4, 2)) . ',' . $a . ')';
@endphp
<style>
  @page{size:A4 landscape;margin:12mm 10mm}
  *{box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  body{margin:0;font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1E293B;background:#F1F2F9;font-size:11px}
  .sayfa{max-width:1180px;margin:0 auto;padding:22px}
  .ust{border-radius:16px;padding:20px 24px;color:#fff;background:linear-gradient(120deg,{{ $tema }} 0%,{{ $tema }} 35%,#1E1B4B 100%);display:flex;gap:16px;align-items:center}
  .ust .lg{width:48px;height:48px;border-radius:13px;background:rgba(255,255,255,.18);display:grid;place-items:center}
  .ust .lg .material-icons-outlined{font-size:28px;color:#fff}
  .ust h1{margin:0;font-size:21px;font-weight:900}.ust p{margin:3px 0 0;font-size:11.5px;color:rgba(255,255,255,.88)}
  .ust .rozet{display:inline-flex;align-items:center;gap:5px;margin-top:7px;padding:3px 10px;border-radius:99px;font-size:10.5px;font-weight:800;background:rgba(255,255,255,.2);color:#fff}
  .ust .mk{margin-left:auto;text-align:right;font-size:10.5px;color:rgba(255,255,255,.8)}.ust .mk b{display:block;font-size:14px;color:#fff}
  .aciklama{margin:12px 2px 0;font-size:11.5px;color:#475569}
  .uyari{margin-top:12px;border-radius:12px;padding:11px 14px;background:#FAF5FF;border:1px solid #E9D5FF;color:#6B21A8;font-size:11.5px;display:flex;gap:9px;align-items:flex-start}
  .uyari .material-icons-outlined{font-size:18px}
  .arac{display:flex;gap:8px;justify-content:flex-end;margin:12px 0}
  .arac button{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;padding:9px 14px;font-weight:800;font-size:12.5px;cursor:pointer;color:#fff;background:{{ $tema }}}
  .arac button.g{background:#fff;color:#334155;border:1px solid #E2E8F0}
  .kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:14px 0}
  .kpi div{border-radius:12px;padding:11px 14px;color:#fff;background:var(--c)}
  .kpi span{font-size:10px;font-weight:700;opacity:.92}.kpi b{display:block;font-size:18px;font-weight:900;margin-top:3px;line-height:1.2}
  .kpi b.metin{font-size:14px}
  .grafikler{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:6px}
  .gk{background:#fff;border:1px solid #E4E7F2;border-radius:12px;padding:12px 14px;page-break-inside:avoid}
  .gk.tam{grid-column:1 / -1}
  .gk h3{margin:0 0 9px;font-size:12px;color:{{ $tema }}}
  .gs{display:flex;align-items:center;gap:8px;margin-bottom:5px;font-size:10.5px}
  .gs .n{width:34%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700}
  .gs .t{flex:1;height:10px;border-radius:99px;background:#EEF0F7;overflow:hidden}.gs .t i{display:block;height:100%;border-radius:99px}
  .gs .v{width:110px;text-align:right;font-weight:800;color:#334155}
  .sutun{display:flex;align-items:flex-end;gap:6px;height:120px;padding-top:6px}
  .sutun .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;min-width:0}
  .sutun .c{width:100%;max-width:40px;border-radius:6px 6px 2px 2px;background:{{ $tema }}}
  .sutun .v{font-size:9px;font-weight:800;color:#475569}.sutun .e{font-size:9px;color:#64748B;font-weight:700;white-space:nowrap}
  .seyir{display:grid;grid-template-columns:1fr 1fr;gap:6px 18px}
  .seyir div{display:flex;align-items:center;gap:8px;font-size:10.5px}
  .seyir .n{width:30%;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .seyir .y{width:56px;text-align:right;font-weight:800}
  .yy{margin-bottom:9px}.yy .n{font-weight:800;font-size:11px;margin-bottom:3px}
  .yy .s{display:flex;align-items:center;gap:8px;font-size:10px;margin-bottom:2px}
  .yy .s .t{width:30%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .yy .s .cb{flex:1;height:9px;border-radius:99px;background:#EEF0F7;overflow:hidden}.yy .s .cb i{display:block;height:100%;border-radius:99px}
  .yy .s .v{width:80px;text-align:right;font-weight:800}
  .karneler{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .kk{border:1px solid #E4E7F2;border-radius:12px;padding:12px;display:flex;gap:12px;align-items:center}
  .kk .harf{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;font-size:22px;font-weight:900;flex:0 0 auto}
  .kk .b{font-weight:900;font-size:12px}.kk .d{font-size:10px;color:#64748B;margin-top:2px;line-height:1.5}
  .aralik{display:flex;align-items:center;gap:10px;margin-bottom:7px;font-size:10.5px}
  .aralik .n{width:22%;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .aralik .ser{flex:1;position:relative;height:18px}
  .aralik .ser .cizgi{position:absolute;top:7px;height:4px;border-radius:99px;background:#BBF7D0}
  .aralik .ser .ort{position:absolute;top:3px;width:2px;height:12px;background:#059669}
  .aralik .ser .sen{position:absolute;top:3px;width:12px;height:12px;border-radius:50%;margin-left:-6px;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.15)}
  .aralik .v{width:150px;text-align:right;font-size:10px;color:#475569}
  .lejant{display:flex;gap:14px;font-size:9.5px;color:#64748B;margin-bottom:8px}
  .lejant i{display:inline-block;width:10px;height:10px;border-radius:50%;vertical-align:-1px;margin-right:4px}
  h2{font-size:14px;margin:20px 0 4px;font-weight:900;color:{{ $tema }};display:flex;align-items:center;gap:8px;page-break-after:avoid}
  h2::before{content:'';width:4px;height:16px;border-radius:2px;background:{{ $tema }}}
  .not{font-size:10.5px;color:#64748B;margin:0 0 8px}
  table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #E4E7F2;border-radius:10px;overflow:hidden}
  thead{display:table-header-group}
  th{background:{{ $tema }};color:#fff;font-size:9.5px;text-transform:uppercase;letter-spacing:.3px;text-align:left;padding:7px 8px;font-weight:800}
  td{padding:6px 8px;border-top:1px solid #EEF0F7;vertical-align:top}
  tr:nth-child(even) td{background:#FAFAFE}
  tr{page-break-inside:avoid}
  table.genis{font-size:9px;table-layout:fixed}
  table.genis th{font-size:7.5px;padding:6px 4px;letter-spacing:0;text-transform:none;overflow-wrap:anywhere;overflow:hidden;line-height:1.25;vertical-align:bottom}
  table.genis td{padding:5px 4px;word-break:break-word}
  tr.top td{background:{{ $hexA($tema, .08) }};font-weight:900;border-top:2px solid {{ $tema }}}
  tr.grup td{background:#F8FAFC !important;font-weight:900;color:{{ $tema }};border-top:2px solid #E2E8F0}
  tr.ara td{font-weight:800;color:#475569;background:#fff !important}
  .r{text-align:right;white-space:nowrap}
  th.r{white-space:normal}
  td.cb{position:relative}
  td.cb .cbi{position:absolute;left:0;top:3px;bottom:3px;border-radius:0 4px 4px 0;background:{{ $hexA($tema, .2) }}}
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
    <div class="lg"><span class="material-icons-outlined">{{ $ikon ?? 'radar' }}</span></div>
    <div>
      <h1>{{ $baslik }}</h1><p>{{ $alt }}</p>
      @if ($aiRapor)<span class="rozet">✨ Yapay zekâ tarafından hazırlanmıştır</span>@else<span class="rozet">Sistem verisi · yorum içermez</span>@endif
    </div>
    <div class="mk"><b>ResteOS</b>Fiyat Radarı</div>
  </div>
  @if (!empty($aciklama))<p class="aciklama">{{ $aciklama }}</p>@endif
  @if ($aiRapor)
    <div class="uyari"><span class="material-icons-outlined">auto_awesome</span><div>Bu rapordaki <b>yorum, değerlendirme ve öneriler yapay zekâya aittir</b>{{ !empty($ai['_zaman']) ? ' (' . $ai['_zaman'] . ')' : '' }}. Rakamlar sistemdeki alış faturalarından gelir ve raporun sonundaki <b>“Dayanak”</b> tablolarında doğrulanabilir. Öneriler karar desteğidir; son karar satın almacınındır.</div></div>
  @endif
  <div class="arac">
    <button class="g" onclick="window.close()"><span class="material-icons-outlined" style="font-size:16px">close</span> Kapat</button>
    <button onclick="window.print()"><span class="material-icons-outlined" style="font-size:16px">picture_as_pdf</span> PDF olarak kaydet / Yazdır</button>
  </div>

  @foreach ($sayfalar as $s)
    @if (($s['tip'] ?? '') === 'pano')
      {{-- ===== PANO: rapora özel kartlar + grafikler ===== --}}
      <div class="kpi">
        @foreach ($s['kpi'] as [$et, $deg, $fmt, $renk])
          <div style="--c:#{{ $renk }}"><span>{{ $et }}</span><b class="{{ $fmt === 'metin' ? 'metin' : '' }}">{{ $kpiDeg($deg, $fmt) }}</b></div>
        @endforeach
      </div>
      <div class="grafikler">
        @foreach ($s['bloklar'] as $bl)
          @php $vt = $bl['kol'][1][1]; $mx = max(1, ...array_map(fn($r) => abs((float) $r[1]), $bl['sat'])); $ozel = $bl['pdf'] ?? null; @endphp
          <div class="gk {{ $ozel || $bl['grafik'] === 'col' && count($bl['sat']) > 8 ? 'tam' : '' }}">
            <h3>{{ $bl['baslik'] }}</h3>
            @if ($ozel === 'seyir')
              {{-- alış alış fiyat çizgisi (sparkline) --}}
              <div class="seyir">
                @foreach ($bl['sat'] as $i => $r)
                  @php
                    $fy = $bl['ek'][$i] ?? []; $mn = $fy ? min($fy) : 0; $mxx = $fy ? max($fy) : 1; $ar = max(0.0001, $mxx - $mn); $nn = count($fy);
                    $pts = []; foreach ($fy as $j => $p) $pts[] = round($nn > 1 ? $j / ($nn - 1) * 150 + 4 : 79, 1) . ',' . round(26 - ($p - $mn) / $ar * 22, 1);
                  @endphp
                  <div><span class="n" title="{{ $r[0] }}">{{ $r[0] }}</span>
                    <svg width="158" height="30" viewBox="0 0 158 30"><polyline points="{{ implode(' ', $pts) }}" fill="none" stroke="{{ $tema }}" stroke-width="2" stroke-linejoin="round"/>@foreach ($pts as $j => $pt)@php [$px, $py] = explode(',', $pt); @endphp<circle cx="{{ $px }}" cy="{{ $py }}" r="{{ $j === $nn - 1 ? 3 : 1.8 }}" fill="{{ $j === $nn - 1 ? $tema : '#94A3B8' }}"/>@endforeach</svg>
                    <span style="color:#64748B;font-size:9.5px">{{ $fy ? number_format($fy[0], 2, ',', '.') . ' → ' . number_format(end($fy), 2, ',', '.') . ' ₺' : '' }}</span>
                    <span class="y" style="color:{{ $tema }}">%{{ number_format($r[1], 1, ',', '.') }}</span></div>
                @endforeach
              </div>
            @elseif ($ozel === 'yanyana')
              {{-- her malzemede tedarikçi fiyatları yan yana --}}
              @foreach ($bl['sat'] as $i => $r)
                @php $tl = $bl['ek'][$i] ?? []; $mt = max(0.01, ...array_map(fn($t) => (float) $t[1], $tl ?: [[0, 1]])); @endphp
                <div class="yy"><div class="n">{{ $r[0] }} <span style="color:#64748B;font-weight:600">· en ucuz ile en pahalı arası %{{ number_format($r[1], 1, ',', '.') }}</span></div>
                  @foreach ($tl as $j => [$tad, $tfy])
                    <div class="s"><span class="t">{{ $tad }}</span><span class="cb"><i style="width:{{ max(3, $tfy / $mt * 100) }}%;background:{{ $j === 0 ? '#10B981' : $hexA($tema, .55) }}"></i></span><span class="v">{{ number_format($tfy, 2, ',', '.') }} ₺</span></div>
                  @endforeach
                </div>
              @endforeach
            @elseif ($ozel === 'karne')
              {{-- tedarikçi karne kartları --}}
              <div class="karneler">
                @foreach ($bl['ek'] as $kx)
                  @php $hr = ['A' => '#059669', 'B' => '#0EA5E9', 'C' => '#F59E0B', 'D' => '#E11D48'][$kx['harf'] ?? 'D'] ?? '#64748B'; @endphp
                  <div class="kk"><div class="harf" style="background:{{ $hr }}">{{ $kx['harf'] ?? '-' }}</div>
                    <div><div class="b">{{ $kx['ted'] }} · {{ $kx['puan'] ?? 0 }}/100</div>
                      <div class="d">{{ $tl0($kx['harcama']) }} · pay %{{ number_format($kx['pay'], 1, ',', '.') }} · {{ $kx['fatura'] }} fatura<br>{{ $kx['malzeme'] }} malzeme · zamlı {{ $kx['zamli'] }} · pahalı kaldığı {{ $kx['pahali'] }}</div></div></div>
                @endforeach
              </div>
            @elseif ($ozel === 'aralik')
              {{-- hal asgari–azami şeridi üstünde senin fiyatın --}}
              <div class="lejant"><span><i style="background:#BBF7D0;border-radius:2px"></i>hal asgari–azami</span><span><i style="background:#059669;width:3px;border-radius:0"></i>hal ortalaması</span><span><i style="background:{{ $tema }}"></i>senin son alışın</span><span><i style="background:#E11D48"></i>%20 üstü</span></div>
              @foreach ($bl['sat'] as $i => $r)
                @php
                  $h = $bl['ek'][$i]; $mn0 = $h['min'] > 0 ? $h['min'] : $h['ort']; $mx0 = $h['max'] > 0 ? $h['max'] : $h['ort'];
                  $lo = min($mn0, $h['sen']) * 0.92; $hi = max($mx0, $h['sen']) * 1.08; $sp = max(0.0001, $hi - $lo); $pos = fn($val) => round(($val - $lo) / $sp * 100, 1);
                @endphp
                <div class="aralik"><span class="n">{{ $r[0] }}</span>
                  <div class="ser"><div class="cizgi" style="left:{{ $pos($mn0) }}%;width:{{ max(1, $pos($mx0) - $pos($mn0)) }}%"></div><div class="ort" style="left:{{ $pos($h['ort']) }}%"></div>
                    <div class="sen" style="left:{{ $pos($h['sen']) }}%;background:{{ $r[2] > 20 ? '#E11D48' : $tema }}"></div></div>
                  <span class="v">{{ number_format($h['sen'], 2, ',', '.') }} ₺ / hal {{ number_format($h['ort'], 2, ',', '.') }} ₺ · <b>%{{ number_format($r[2], 0, ',', '.') }}</b></span></div>
              @endforeach
            @elseif ($bl['grafik'] === 'col')
              <div class="sutun">
                @foreach ($bl['sat'] as $r)
                  <div class="b"><div class="v">{{ $vt === 'tl' ? number_format($r[1] / 1000, 1, ',', '.') . 'k' : $hucre($r[1], $vt) }}</div><div class="c" style="height:{{ max(3, round(abs($r[1]) / $mx * 92)) }}px"></div><div class="e">{{ $r[0] }}</div></div>
                @endforeach
              </div>
            @else
              @foreach ($bl['sat'] as $i => $r)
                <div class="gs"><div class="n" title="{{ $r[0] }}">{{ $r[0] }}</div>
                  <div class="t"><i style="width:{{ max(2, abs($r[1]) / $mx * 100) }}%;background:{{ $bl['grafik'] === 'pie' ? $palet[$i % 10] : '#' . $bl['renk'] }}"></i></div>
                  <div class="v">{{ $blokDeg($r[1], $vt) }}@if (isset($r[2]) && $r[2] !== '' && $bl['kol'][2][0] !== '') <span style="color:#94A3B8;font-weight:600">· {{ $hucre($r[2], $bl['kol'][2][1]) }}</span>@endif</div></div>
              @endforeach
            @endif
          </div>
        @endforeach
      </div>

      @if ($ai)
        {{-- ===== AI değerlendirmesi (yalnız AI raporu) ===== --}}
        <h2>Yapay zekâ değerlendirmesi</h2>
        @php $sk = max(0, min(100, (int) ($ai['skor'] ?? 0))); $skr = $sk >= 70 ? '#10B981' : ($sk >= 45 ? '#F59E0B' : '#F43F5E'); @endphp
        <div class="ozet">
          <div class="skor" style="background:conic-gradient({{ $skr }} {{ $sk * 3.6 }}deg,#EEF0F7 0)"><div>{{ $sk }}</div></div>
          <div><b>AI satın alma puanı: {{ $sk }}/100</b> · {{ $ai['_zaman'] ?? '' }}<br>{{ $ai['ozet'] ?? '' }}</div>
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
            <div class="k" style="--c:#9333EA"><h3>Önerilen aksiyonlar</h3>@foreach ($ai['aksiyonlar'] as $xx)<div class="m"><b>[{{ ['yuksek' => 'YÜKSEK', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$xx['oncelik'] ?? ''] ?? '' }}] {{ $xx['is'] ?? '' }}</b>{{ $xx['sorumlu'] ?? '' }}</div>@endforeach</div>
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
        $duz = array_values(array_filter($s['sat'], fn($r) => !isset($r['_baslik']) && !isset($r['_ara'])));
        $mxK = []; $topK = []; $matMx = 0;
        foreach ($s['kol'] as $i => $k) {
          $b = $k[3] ?? '';
          if (str_contains($b, 'bar')) $mxK[$i] = max(1, ...array_map(fn($r) => abs((float) ($r[$i] ?? 0)), $duz ?: [[0]]));
          if (str_contains($b, 'top')) $topK[$i] = array_sum(array_map(fn($r) => (float) ($r[$i] ?? 0), $duz));
          if (str_contains($b, 'mat')) foreach ($duz as $r) $matMx = max($matMx, (float) ($r[$i] ?? 0));
        }
        $gTop = array_sum(array_map(fn($k) => $k[2] ?? 14, $s['kol'])); $grupTop = [];
      @endphp
      <table class="{{ count($s['kol']) > 10 ? 'genis' : '' }}">
        <colgroup>@foreach ($s['kol'] as $k)<col style="width:{{ round(($k[2] ?? 14) / $gTop * 100, 2) }}%">@endforeach</colgroup>
        <thead><tr>@foreach ($s['kol'] as $k)<th class="{{ in_array($k[1], ['tl', 'y', 's'], true) ? 'r' : '' }}">{{ $k[0] }}</th>@endforeach</tr></thead>
        <tbody>
          @foreach ($s['sat'] as $sat)
            @if (isset($sat['_baslik']))
              @php $grupTop = []; @endphp
              <tr class="grup"><td colspan="{{ count($s['kol']) }}">{{ $sat['_baslik'] }}</td></tr>
              @continue
            @endif
            @if (isset($sat['_ara']))
              <tr class="ara">@foreach ($s['kol'] as $i => $k)<td class="{{ isset($topK[$i]) ? 'r' : '' }}">{{ $i === 0 ? $sat['_ara'] : (isset($topK[$i]) ? $hucre($grupTop[$i] ?? 0, $k[1]) : '') }}</td>@endforeach</tr>
              @continue
            @endif
            <tr>@foreach ($s['kol'] as $i => $k)
              @php $val = $sat[$i] ?? null; $b = $k[3] ?? ''; $sag = in_array($k[1], ['tl', 'y', 's'], true); if (isset($topK[$i])) $grupTop[$i] = ($grupTop[$i] ?? 0) + (float) $val; @endphp
              @if (str_contains($b, 'mat'))
                <td class="r" style="background:{{ is_numeric($val) && $matMx > 0 ? $hexA($tema, round(0.06 + (float) $val / $matMx * 0.6, 2)) : 'transparent' }}">{{ $hucre($val, $k[1]) }}</td>
              @elseif (isset($mxK[$i]) && is_numeric($val))
                <td class="r cb"><div class="cbi" style="width:{{ round(abs($val) / $mxK[$i] * 100) }}%"></div><span>{{ $hucre($val, $k[1]) }}</span></td>
              @elseif (preg_match('/esik:([\d.]+)/', $b, $mm))
                <td class="{{ $sag ? 'r' : '' }}" style="{{ is_numeric($val) && $val >= (float) $mm[1] ? 'font-weight:900;color:#BE123C' : '' }}">{{ $hucre($val, $k[1]) }}</td>
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
  <div class="alt">ResteOS · Fiyat Radarı · {{ $aiRapor ? 'Yapay zekâ analizi' : 'Sistem verisi (yorumsuz)' }} · {{ now()->format('d.m.Y H:i') }}</div>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 500); });</script>
</body>
</html>

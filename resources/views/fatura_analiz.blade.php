@extends('layout.app')
@section('title', 'Fatura Analizleri')
@section('rk_ikon', 'query_stats')
@section('baslik', 'Fatura Analizleri')
@section('rk_alt', 'Kaçan tasarruf · zamlar ve enflasyon · ürün fiyat izi · ödeme takvimi — alış faturalarından, ücretsiz')

@section('content')
@php
  $tl = fn($v) => $v === null ? '—' : number_format((float) $v, 2, ',', '.') . ' ₺';
  $yz = fn($v) => $v === null ? '—' : (($v > 0 ? '+' : '') . number_format((float) $v, 1, ',', '.') . '%');
  $sn = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
  $sekmeler = ['tasarruf' => ['Kaçan Tasarruf', 'savings'], 'enflasyon' => ['Zam ve Enflasyon', 'trending_up'], 'iz' => ['Ürün Fiyat İzi', 'timeline'], 'odeme' => ['Ödeme Takvimi', 'event']];
  $excel = '/fatura-analiz/' . $sekme . '?' . http_build_query(array_filter(['bas' => $bas, 'bit' => $bit, 'malzeme' => $sekme === 'iz' ? ($iz['mid'] ?? null) : null, 'format' => 'xlsx']));
@endphp
<style>
  .fa-sekme{display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 12px}
  .fa-sekme a{padding:8px 13px;border-radius:999px;border:1px solid var(--line);font-size:12.5px;font-weight:600;color:var(--ink);text-decoration:none;display:inline-flex;gap:6px;align-items:center}
  .fa-sekme a.on{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .fa-sekme .material-icons-outlined{font-size:16px}
  .fa-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
  @media (max-width:900px){.fa-kpi{grid-template-columns:repeat(2,1fr)}}
  .fa-kpi .rk-card{padding:14px 16px}.fa-kpi .e{font-size:12px;color:var(--sub);font-weight:600}.fa-kpi .d{font-size:21px;font-weight:800;margin-top:4px}
  .fa-not{font-size:12px;color:var(--sub);margin:-4px 0 12px}
  .fa-bos{text-align:center;padding:34px}
</style>

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="fa-sekme">
  @foreach ($sekmeler as $k => $s)<a href="/fatura-analiz/{{ $k }}" class="{{ $sekme === $k ? 'on' : '' }}"><span class="material-icons-outlined">{{ $s[1] }}</span>{{ $s[0] }}</a>@endforeach
  <a href="/fatura-denetim" style="margin-left:auto"><span class="material-icons-outlined">fact_check</span>Fatura Denetimi</a>
</div>

@if ($sekme !== 'odeme')
<form method="GET" class="rk-card" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px;padding:12px 14px">
  @if ($sekme === 'iz')
    <div style="flex:1;min-width:240px"><label class="rk-lbl" style="margin-top:0">Ürün (harcamaya göre sıralı)</label>
      <select name="malzeme" class="rk-inp" onchange="this.form.submit()">@foreach ($iz['secenek'] as $o)<option value="{{ $o['id'] }}" @selected($o['id'] == $iz['mid'])>{{ $o['ad'] }} — {{ $tl($o['tutar']) }}</option>@endforeach</select></div>
  @endif
  <div><label class="rk-lbl" style="margin-top:0">Başlangıç</label><input type="date" name="bas" value="{{ $bas }}" class="rk-inp"></div>
  <div><label class="rk-lbl" style="margin-top:0">Bitiş</label><input type="date" name="bit" value="{{ $bit }}" class="rk-inp"></div>
  <button class="rk-btn" style="background:var(--mor1)" type="submit">Göster</button>
  <a class="rk-btn bos" href="{{ $excel }}" style="margin-left:auto"><span class="material-icons-outlined" style="font-size:17px">table_view</span> Excel</a>
</form>
@endif

{{-- ================= KAÇAN TASARRUF ================= --}}
@if ($sekme === 'tasarruf')
  @php $kt = array_sum(array_column($t, 'kac_ted')); $kf = array_sum(array_column($t, 'kac_fiyat')); $harc = array_sum(array_column($t, 'tutar')); $cok = count(array_filter($t, fn($x) => $x['ted_say'] >= 2)); @endphp
  <div class="fa-kpi">
    <div class="rk-card"><div class="e">Dönem harcaması</div><div class="d">{{ $tl($harc) }}</div></div>
    <div class="rk-card"><div class="e">En ucuz tedarikçiden alınsaydı</div><div class="d" style="color:var(--kirmizi)">{{ $tl($kt) }}</div><div class="rk-sub2" style="font-size:11.5px">{{ $cok }} ürün birden çok tedarikçiden alınmış</div></div>
    <div class="rk-card"><div class="e">En düşük ödenen fiyattan alınsaydı</div><div class="d" style="color:var(--amber)">{{ $tl($kf) }}</div></div>
    <div class="rk-card"><div class="e">Kaçan / harcama</div><div class="d">{{ $harc > 0 ? number_format($kt / $harc * 100, 1, ',', '.') . '%' : '—' }}</div></div>
  </div>
  <div class="fa-not">Fiyatlar malzemenin temel birimine çevrilerek kıyaslandı. Kıyas her ay kendi içinde yapılır (zamlar kaçan tasarruf sayılmaz): tedarikçi farkı = o ay en ucuz ortalamayı veren tedarikçiden alınsaydı, fiyat farkı = o ay ödenen en düşük fiyattan alınsaydı. Kalite/teslim farkı varsa kararı satın almacı verir.</div>
  @if (!$t)
    <div class="rk-card fa-bos rk-sub">Bu dönemde alış faturası yok. Alış faturaları elle girildikçe ya da <a href="/efatura-gelen">Gelen e-Faturalar</a>'dan aktarıldıkça dolar.</div>
  @else
  <div class="rk-tablo"><table>
    <thead><tr><th>Ürün</th><th class="sag">Alım</th><th class="sag">Tutar</th><th class="sag">Ort. fiyat</th><th>En ucuz tedarikçi</th><th class="sag">En düşük ödenen</th><th class="sag">Tedarikçi farkı</th><th class="sag">Fiyat farkı</th></tr></thead>
    <tbody>@foreach (array_slice($t, 0, 300) as $x)
      <tr><td><a href="/fatura-analiz/iz?malzeme={{ $x['mid'] }}&bas={{ $bas }}&bit={{ $bit }}" style="font-weight:600">{{ $x['ad'] }}</a><div class="rk-sub2" style="font-size:11.5px">{{ $x['ted_say'] }} tedarikçi · birim: {{ $x['birim'] }}</div></td>
        <td class="sag">{{ $x['alim'] }}</td><td class="sag">{{ $tl($x['tutar']) }}</td><td class="sag">{{ $tl($x['ort']) }}</td>
        <td>@if ($x['en_iyi_ted']){{ $x['en_iyi_ted'] }}<div class="rk-sub2" style="font-size:11.5px">ort. {{ $tl($x['en_iyi']) }}</div>@else<span class="rk-sub2">tek tedarikçi</span>@endif</td>
        <td class="sag">{{ $tl($x['min']) }}<div class="rk-sub2" style="font-size:11.5px">{{ $x['min_ted'] }} · {{ \Carbon\Carbon::parse($x['min_tarih'])->format('d.m.Y') }}</div></td>
        <td class="sag" style="font-weight:700;color:{{ $x['kac_ted'] > 0 ? 'var(--kirmizi)' : 'var(--sub)' }}">{{ $x['kac_ted'] > 0 ? $tl($x['kac_ted']) : '—' }}</td>
        <td class="sag" style="color:{{ $x['kac_fiyat'] > 0 ? 'var(--amber)' : 'var(--sub)' }}">{{ $x['kac_fiyat'] > 0 ? $tl($x['kac_fiyat']) : '—' }}</td></tr>
    @endforeach</tbody></table></div>
  @endif

{{-- ================= ZAM ve ENFLASYON ================= --}}
@elseif ($sekme === 'enflasyon')
  @php $tufe = $e['tufe']; $aylar = []; $c = \Carbon\Carbon::parse($bas)->startOfMonth(); while ($c <= \Carbon\Carbon::parse($bit)) { $aylar[] = $c->format('Y-m'); $c->addMonth(); } @endphp
  <div class="rk-card" style="margin-bottom:12px;padding:12px 14px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px"><b>Aylık TÜFE (%)</b><span class="rk-sub2" style="font-size:12px">TÜİK her ayın 3'ünde açıklar; aylık değişimi girin. Boş ay kıyası engeller.</span></div>
    <form method="POST" action="/fatura-analiz/tufe" style="display:flex;gap:6px;flex-wrap:wrap;align-items:flex-end">@csrf
      @foreach ($aylar as $ay)<div style="width:86px"><label class="rk-lbl" style="margin-top:0;font-size:11px">{{ \Carbon\Carbon::parse($ay . '-01')->locale('tr')->translatedFormat('M Y') }}</label>
        <input type="text" inputmode="decimal" name="tufe[{{ $ay }}]" value="{{ isset($tufe[$ay]) ? $sn($tufe[$ay]) : '' }}" class="rk-inp" style="padding:6px 8px" @disabled(!$yonetici)></div>@endforeach
      @if ($yonetici)<button class="rk-btn bos" type="submit" style="padding:7px 12px">Kaydet</button>@endif
    </form>
  </div>
  @if ($e['eksik'])<div class="rk-flash err" style="margin-bottom:12px">Bazı ayların TÜFE değeri girilmemiş; o ürünlerde enflasyon kıyası boş kaldı.</div>@endif
  @if ($e['suphe'])<div class="fa-not"><b>{{ count($e['suphe']) }} ürün kıyasa katılmadı:</b> fiyatı 3 katından fazla değişmiş, büyük ihtimalle farklı ambalaj ya da birim (aynı stok kodu farklı paketlere verilmiş olabilir): {{ implode(' · ', array_slice($e['suphe'], 0, 8)) }}{{ count($e['suphe']) > 8 ? ' …' : '' }}. Fatura Denetimi'nde ayrıca uyarı olarak görünür.</div>@endif
  @if (!$e['urun'])
    <div class="rk-card fa-bos rk-sub">Kıyas için bir ürünün aynı tedarikçiden en az iki farklı ayda alınmış olması gerekir. Dönemi genişletin ya da alış faturaları biriktikçe tekrar bakın.</div>
  @else
  <div class="rk-bolum" style="margin:6px 0"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Tedarikçiler</span></div>
  <div class="rk-tablo" style="margin-bottom:16px"><table>
    <thead><tr><th>Tedarikçi</th><th class="sag">Ürün</th><th class="sag">Tutar</th><th class="sag">Ort. zam</th><th class="sag">TÜFE</th><th class="sag">Fark</th><th class="sag">Enflasyon üstü ürün</th></tr></thead>
    <tbody>@foreach ($e['tedarikci'] as $x)
      <tr><td style="font-weight:600">{{ $x['ted'] }}</td><td class="sag">{{ $x['urun'] }}</td><td class="sag">{{ $tl($x['tutar']) }}</td><td class="sag">{{ $yz($x['zam']) }}</td><td class="sag">{{ $yz($x['tufe']) }}</td>
        <td class="sag" style="font-weight:700;color:{{ ($x['fark'] ?? 0) > 0 ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $x['fark'] === null ? '—' : $yz($x['fark']) . ' puan' }}</td><td class="sag">{{ $x['ustu'] }}</td></tr>
    @endforeach</tbody></table></div>
  <div class="rk-bolum" style="margin:6px 0"><span class="cubuk" style="background:var(--kirmizi)"></span><span class="b">Ürünler (enflasyonun en çok üstünde olan başta)</span></div>
  <div class="rk-tablo"><table>
    <thead><tr><th>Ürün</th><th>Tedarikçi</th><th class="sag">İlk</th><th class="sag">Son</th><th class="sag">Zam</th><th class="sag">TÜFE</th><th class="sag">Fark</th></tr></thead>
    <tbody>@foreach (array_slice($e['urun'], 0, 300) as $x)
      <tr><td style="font-weight:600">{{ $x['ad'] }}<div class="rk-sub2" style="font-size:11.5px">birim: {{ $x['birim'] }}</div></td><td>{{ $x['ted'] }}</td>
        <td class="sag">{{ $tl($x['ilk']) }}<div class="rk-sub2" style="font-size:11.5px">{{ $x['ilk_ay'] }}</div></td><td class="sag">{{ $tl($x['son']) }}<div class="rk-sub2" style="font-size:11.5px">{{ $x['son_ay'] }}</div></td>
        <td class="sag">{{ $yz($x['zam']) }}</td><td class="sag">{{ $yz($x['tufe']) }}</td>
        <td class="sag" style="font-weight:700;color:{{ ($x['fark'] ?? 0) > 0 ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $x['fark'] === null ? '—' : $yz($x['fark']) . ' puan' }}</td></tr>
    @endforeach</tbody></table></div>
  @endif

{{-- ================= ÜRÜN FİYAT İZİ ================= --}}
@elseif ($sekme === 'iz')
  @if (!$iz['sat'])
    <div class="rk-card fa-bos rk-sub">Bu dönemde alış yok. Dönemi genişletin ya da alış faturaları girildikçe tekrar bakın.</div>
  @else
  @php
    $noktalar = array_values(array_filter($iz['sat'], fn($x) => $x['bilinen']));
    $palet = ['#7C3AED', '#0EA5E9', '#F59E0B', '#10B981', '#EC4899', '#EF4444', '#4F46E5', '#14B8A6'];
    $W = 860; $H = 260; $p = 44;
    $t0 = strtotime($noktalar[0]['tarih'] ?? $bas); $t1 = max($t0 + 86400, strtotime(end($noktalar)['tarih'] ?? $bit));
    $ys = array_column($noktalar, 'pt'); $y0 = $ys ? min($ys) * 0.95 : 0; $y1 = $ys ? max($ys) * 1.05 : 1; if ($y1 - $y0 < 0.01) { $y1 += 1; $y0 = max(0, $y0 - 1); }
    $X = fn($t) => $p + (strtotime($t) - $t0) / ($t1 - $t0) * ($W - 2 * $p); $Y = fn($v) => $H - 26 - ($v - $y0) / ($y1 - $y0) * ($H - 46);
    $seri = collect($noktalar)->groupBy('tid');
  @endphp
  <div class="rk-card" style="margin-bottom:12px">
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><b>{{ $iz['ad'] }}</b><span class="rk-sub2" style="font-size:12px">Fiyat {{ $iz['temel'] }} başına (temel birim)</span>
      <span style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap">@foreach ($seri as $tid => $l)<span style="font-size:12px;display:inline-flex;gap:5px;align-items:center"><span style="width:10px;height:10px;border-radius:50%;background:{{ $palet[$loop->index % 8] }}"></span>{{ $l->first()['ted'] }}</span>@endforeach</span></div>
    <svg viewBox="0 0 {{ $W }} {{ $H }}" style="width:100%;height:auto;margin-top:8px" role="img" aria-label="Fiyat izi grafiği">
      @for ($i = 0; $i <= 4; $i++) @php $v = $y0 + ($y1 - $y0) * $i / 4; @endphp
        <line x1="{{ $p }}" x2="{{ $W - $p }}" y1="{{ $Y($v) }}" y2="{{ $Y($v) }}" stroke="currentColor" stroke-opacity=".12"/>
        <text x="{{ $p - 6 }}" y="{{ $Y($v) + 4 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".6">{{ number_format($v, $v < 10 ? 2 : 0, ',', '.') }}</text>
      @endfor
      <text x="{{ $p }}" y="{{ $H - 6 }}" font-size="10" fill="currentColor" fill-opacity=".6">{{ date('d.m.Y', $t0) }}</text>
      <text x="{{ $W - $p }}" y="{{ $H - 6 }}" text-anchor="end" font-size="10" fill="currentColor" fill-opacity=".6">{{ date('d.m.Y', $t1) }}</text>
      @foreach ($seri as $tid => $l) @php $renk = $palet[$loop->index % 8]; @endphp
        <polyline fill="none" stroke="{{ $renk }}" stroke-width="2" points="{{ $l->map(fn($n) => round($X($n['tarih']), 1) . ',' . round($Y($n['pt']), 1))->implode(' ') }}"/>
        @foreach ($l as $n)<circle cx="{{ round($X($n['tarih']), 1) }}" cy="{{ round($Y($n['pt']), 1) }}" r="3.5" fill="{{ $renk }}"><title>{{ $n['ted'] }} · {{ \Carbon\Carbon::parse($n['tarih'])->format('d.m.Y') }} · {{ number_format($n['pt'], 2, ',', '.') }} ₺</title></circle>@endforeach
      @endforeach
    </svg>
  </div>
  <div class="rk-tablo"><table>
    <thead><tr><th>Tarih</th><th>Tedarikçi</th><th>Fatura</th><th class="sag">Miktar</th><th class="sag">Birim fiyat</th><th class="sag">{{ $iz['temel'] }} başına</th><th class="sag">Değişim</th></tr></thead>
    <tbody>@php $once = []; @endphp @foreach ($iz['sat'] as $x) @php $o = $once[$x['tid']] ?? null; $once[$x['tid']] = $x['pt']; @endphp
      <tr><td class="rk-sub2">{{ \Carbon\Carbon::parse($x['tarih'])->format('d.m.Y') }}</td><td>{{ $x['ted'] }}</td><td class="rk-sub2">{{ $x['fatura'] }}</td><td class="sag">{{ $sn($x['miktar']) }} {{ $x['birim'] }}</td>
        <td class="sag">{{ $tl($x['fiyat']) }}</td><td class="sag">{{ $x['bilinen'] ? $tl($x['pt']) : 'çevrim yok' }}</td>
        <td class="sag" style="color:{{ $o && $x['pt'] > $o * 1.001 ? 'var(--kirmizi)' : ($o && $x['pt'] < $o * 0.999 ? 'var(--yesil)' : 'var(--sub)') }}">{{ $o ? $yz(($x['pt'] - $o) / $o * 100) : '—' }}</td></tr>
    @endforeach</tbody></table></div>
  @endif

{{-- ================= ÖDEME TAKVİMİ ================= --}}
@else
  @php $top = array_sum(array_column($o['fatura'], 'kalan')); $gec = $o['hafta']['0|Gecikmiş']['tutar'] ?? 0; $buHafta = collect($o['hafta'])->filter(fn($x, $k) => str_starts_with($k, '1|'))->sum('tutar'); @endphp
  <div class="fa-kpi">
    <div class="rk-card"><div class="e">Açık fatura toplamı</div><div class="d">{{ $tl($top) }}</div><div class="rk-sub2" style="font-size:11.5px">{{ count($o['fatura']) }} fatura</div></div>
    <div class="rk-card"><div class="e">Vadesi geçmiş</div><div class="d" style="color:var(--kirmizi)">{{ $tl($gec) }}</div></div>
    <div class="rk-card"><div class="e">Bu hafta ödenecek</div><div class="d" style="color:var(--amber)">{{ $tl($buHafta) }}</div></div>
    <div class="rk-card"><div class="e">Sonraki 12 hafta</div><div class="d">{{ $tl($top - $gec) }}</div></div>
  </div>
  <div class="fa-not">Ödemeler tedarikçinin carisindeki ödeme kayıtlarından, en eski faturadan başlayarak düşüldü. @if ($o['cari_yok']) <b>{{ $o['cari_yok'] }} tedarikçinin carisi yok</b>; faturaları ödenmemiş sayıldı. @endif Vadesi yazmayan faturada tedarikçi kartındaki vade günü kullanıldı.
    <a href="/fatura-analiz/odeme?format=xlsx">Excel</a></div>
  @if (!$o['fatura'])
    <div class="rk-card fa-bos rk-sub">Açık fatura yok.</div>
  @else
  <div class="rk-tablo" style="margin-bottom:16px"><table>
    <thead><tr><th>Hafta</th><th class="sag">Fatura</th><th class="sag">Tutar</th><th>Tedarikçiler</th></tr></thead>
    <tbody>@foreach ($o['hafta'] as $k => $x)
      <tr><td style="font-weight:600;{{ str_starts_with($k, '0|') ? 'color:var(--kirmizi)' : '' }}">{{ explode('|', $k, 2)[1] }}</td><td class="sag">{{ $x['adet'] }}</td><td class="sag" style="font-weight:700">{{ $tl($x['tutar']) }}</td>
        <td class="rk-sub2" style="font-size:12px">{{ collect($x['ted'])->sortDesc()->take(4)->map(fn($t, $a) => $a . ' ' . number_format($t, 0, ',', '.') . ' ₺')->implode(' · ') }}{{ count($x['ted']) > 4 ? ' …' : '' }}</td></tr>
    @endforeach</tbody></table></div>
  <div class="rk-tablo"><table>
    <thead><tr><th>Vade</th><th>Tedarikçi</th><th>Fatura</th><th class="sag">Toplam</th><th class="sag">Kalan</th></tr></thead>
    <tbody>@foreach (array_slice($o['fatura'], 0, 300) as $x)
      <tr><td style="{{ $x['vade'] < today()->toDateString() ? 'color:var(--kirmizi);font-weight:600' : '' }}">{{ \Carbon\Carbon::parse($x['vade'])->format('d.m.Y') }}</td><td>{{ $x['ted'] }}@if (!$x['cari'])<div class="rk-sub2" style="font-size:11px">cari yok</div>@endif</td>
        <td class="rk-sub2">{{ $x['fatura'] }} · {{ \Carbon\Carbon::parse($x['tarih'])->format('d.m.Y') }}</td><td class="sag">{{ $tl($x['toplam']) }}</td><td class="sag" style="font-weight:700">{{ $tl($x['kalan']) }}</td></tr>
    @endforeach</tbody></table></div>
  @endif
@endif
@endsection

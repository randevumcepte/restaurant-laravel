@extends('layout.app')
@section('rk_ikon', 'insights')
@section('baslik', 'Anlaşma Raporları')
@section('rk_alt', 'Fiyat anlaşmaları ve sözleşmeler · tasarruf, uyum, taahhüt, yenileme')
@section('title', 'Anlaşma Raporları')

@push('head')
<style>
#far{--mor:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f6f7fb;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb}
body.koyu #far{--card:#161d2e;--card2:#1d2539;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#far{background:var(--bg);min-height:calc(100vh - 60px)}#far *{box-sizing:border-box}
#far .pano{max-width:1180px;margin:0 auto;padding:18px 18px 48px}
.ms{font-family:'Material Icons Outlined';font-weight:normal;font-style:normal;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased;text-transform:none;letter-spacing:normal;white-space:nowrap}
#far .ust{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
#far .geri{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:800;color:var(--sub);text-decoration:none;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:8px 12px}
#far .geri .ms{font-size:18px}
#far .filtre{flex:1;display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
#far .filtre input,#far .filtre select{border:1px solid var(--line);border-radius:11px;padding:8px 10px;background:var(--card);color:var(--ink);font-size:13px}
#far .hz{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:6px 11px;font-size:12px;font-weight:700;cursor:pointer;text-decoration:none}
#far .hz.on{background:var(--ink);border-color:var(--ink);color:var(--card)}
#far .btn{border:0;border-radius:11px;padding:9px 14px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:#fff;background:linear-gradient(135deg,var(--mavi),var(--mor))}
#far .btn.ik{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#far .btn .ms{font-size:17px}
#far .kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:4px 0 16px}
#far .k{min-width:0;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:15px;display:flex;gap:12px;align-items:center}
#far .k .ki{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;background:color-mix(in srgb,var(--kr) 14%,transparent);color:var(--kr);flex:none}
#far .k .ki .ms{font-size:23px}
#far .k .kd{font-size:21px;font-weight:900;color:var(--ink);line-height:1.15;white-space:nowrap}
#far .k .kl{font-size:11.5px;color:var(--sub);font-weight:600;line-height:1.3}
#far .sekmeler{display:flex;gap:4px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:4px;margin-bottom:12px;overflow-x:auto}
#far .sekme{border:0;background:none;border-radius:10px;padding:9px 13px;font-size:13px;font-weight:800;color:var(--sub);cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
#far .sekme .ms{font-size:17px}
#far .sekme.on{background:linear-gradient(135deg,var(--mavi),var(--mor));color:#fff}
#far .panel{display:none}#far .panel.on{display:block}
#far .kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:12px}
#far .kb{display:flex;align-items:center;gap:9px;margin-bottom:4px;flex-wrap:wrap}
#far .kb h3{margin:0;font-size:15px;font-weight:900;color:var(--ink);flex:1;min-width:180px}
#far .kb .ms{color:var(--mavi);font-size:20px}
#far .acik{font-size:12.5px;color:var(--sub);margin:0 0 12px;line-height:1.5}
#far .tablo{overflow-x:auto}
#far table{width:100%;border-collapse:collapse;font-size:12.5px}
#far th{text-align:left;color:var(--sub);font-weight:800;font-size:10.5px;padding:8px;border-bottom:2px solid var(--line);text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}
#far td{padding:9px 8px;border-bottom:1px solid var(--line);color:var(--ink);vertical-align:middle}
#far th.s,#far td.s{text-align:right;white-space:nowrap}
#far tr:last-child td{border-bottom:0}
#far .yes{color:#059669;font-weight:800}#far .kir{color:#e11d48;font-weight:800}#far .gri{color:var(--sub2)}
body.koyu #far .yes{color:#34d399}body.koyu #far .kir{color:#fb7185}
#far .roz{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;padding:2px 9px;border-radius:20px;white-space:nowrap}
#far .roz.k{background:color-mix(in srgb,var(--kirmizi) 13%,transparent);color:#e11d48}
#far .roz.y{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:#059669}
#far .roz.a{background:color-mix(in srgb,var(--amber) 16%,transparent);color:#B45309}
#far .roz.g{background:var(--card2);color:var(--sub)}
#far .bos{text-align:center;color:var(--sub);padding:26px 16px;font-size:13px;line-height:1.55}
#far .bos .ms{font-size:40px;color:var(--sub2);display:block;margin-bottom:6px}
/* aylık grafik */
#far .grafik{display:flex;align-items:stretch;gap:8px;height:190px;padding:10px 4px 0;border-bottom:1px solid var(--line);margin-bottom:6px;position:relative}
#far .sutun{flex:1;min-width:26px;display:flex;flex-direction:column;align-items:center;position:relative}
#far .sutun .ust-y{flex:1;display:flex;flex-direction:column;justify-content:flex-end;width:100%;align-items:center}
#far .sutun .alt-y{flex:var(--negOran,0);display:flex;flex-direction:column;justify-content:flex-start;width:100%;align-items:center}
#far .sutun i{display:block;width:70%;max-width:46px;border-radius:7px 7px 0 0;background:#10B981;min-height:2px}
#far .sutun .alt-y i{border-radius:0 0 7px 7px;background:#F43F5E}
#far .sutun .deg{font-size:10.5px;font-weight:800;color:var(--ink);margin-bottom:3px;white-space:nowrap}
#far .aylar{display:flex;gap:8px;padding:0 4px}
#far .aylar span{flex:1;min-width:26px;text-align:center;font-size:11px;color:var(--sub);font-weight:700}
/* kapsama */
#far .kapsama{display:flex;gap:20px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
#far .halka{width:130px;height:130px;border-radius:50%;background:conic-gradient(#4F46E5 calc(var(--o) * 1%),var(--card2) 0);display:grid;place-items:center;flex:none}
#far .halka div{width:96px;height:96px;border-radius:50%;background:var(--card);display:grid;place-items:center;text-align:center}
#far .halka b{font-size:24px;font-weight:900;color:var(--ink);line-height:1}
#far .halka span{font-size:10.5px;color:var(--sub);font-weight:700}
#far .kapsama .sat{font-size:13px;color:var(--sub);line-height:1.8}
#far .kapsama .sat b{color:var(--ink)}
/* fazla fatura tedarikçi kartı */
#far .ted-kart{border:1px solid var(--line);border-radius:14px;margin-bottom:10px;overflow:hidden}
#far .ted-bas{display:flex;align-items:center;gap:10px;padding:11px 13px;background:var(--card2);flex-wrap:wrap}
#far .ted-bas b{flex:1;font-size:14px;color:var(--ink);min-width:140px}
#far .wa{background:#25D366;color:#fff}
/* takvim */
#far .zaman{display:flex;flex-direction:column;gap:8px}
#far .olay{display:flex;gap:12px;align-items:center;border:1px solid var(--line);border-radius:13px;padding:11px 13px}
#far .olay .gun{width:54px;text-align:center;flex:none;border-radius:11px;padding:6px 0;background:var(--card2)}
#far .olay .gun b{display:block;font-size:18px;font-weight:900;color:var(--ink);line-height:1}
#far .olay .gun span{font-size:10.5px;color:var(--sub);font-weight:800;text-transform:uppercase}
#far .olay .ic{flex:1;min-width:0}
#far .olay .ic b{display:block;font-size:13.5px;color:var(--ink)}
#far .olay .ic span{font-size:12px;color:var(--sub)}
#far .adim{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;background:var(--card2);border-radius:8px;padding:3px 7px;margin:2px 2px 2px 0;white-space:nowrap}
#far .not-k{font-size:11.5px;color:var(--sub);margin-top:10px;line-height:1.5}
@media (max-width:860px){ #far .kpi{grid-template-columns:repeat(2,minmax(0,1fr))} #far .filtre{justify-content:flex-start} }
@media (max-width:520px){ #far .pano{padding:12px 12px 40px} #far .k{padding:12px} #far .k .kd{font-size:18px} }
@media print{
  body *{visibility:hidden !important}
  #far,#far *{visibility:visible !important}
  #far{position:absolute;left:0;top:0;width:100%;background:#fff}
  #far .panel{display:block !important;page-break-inside:auto}
  #far .yazdirma,#far .sekmeler,#far .filtre,#far .geri{display:none !important}
  #far .kart{break-inside:avoid;border-color:#ddd}
  #far .baski-bas{display:block !important}
}
#far .baski-bas{display:none;margin-bottom:12px}
/* ── Kurumsal renk katmanı (Fiyat Anlaşmaları ile aynı): lacivert üst bant, beyaz kartlar ── */
#far{--marka:#3730A3;--marka2:#4F46E5}
#far .ust{background:linear-gradient(115deg,#1E1B4B 0%,#312E81 55%,#3730A3 100%);border-radius:18px;padding:14px 16px;box-shadow:0 10px 26px rgba(30,27,75,.2);margin-bottom:14px}
body.koyu #far .ust{background:linear-gradient(115deg,#141233 0%,#24215f 60%,#2b2785 100%)}
#far .ust .geri,#far .ust .hz,#far .ust .btn.ik{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.26);color:#fff}
#far .ust .hz.on{background:#fff;border-color:#fff;color:var(--marka)}
#far .ust .filtre input,#far .ust .filtre select{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.26);color:#fff;color-scheme:dark}
#far .ust .filtre select option{color:#1e293b;background:#fff}
#far .ust .btn:not(.ik){background:#fff;color:var(--marka)}
#far .btn{background:var(--marka)}
#far .k{border-top:3px solid var(--kr)}
#far .k .ki{background:color-mix(in srgb,var(--kr) 12%,transparent)}
#far .sekme.on{background:var(--marka)}
#far .kart{border-top:3px solid var(--marka)}
#far .kb .ms{color:var(--marka);background:color-mix(in srgb,var(--marka2) 10%,transparent);border-radius:9px;padding:6px;font-size:18px}
body.koyu #far .kb .ms{color:#a5b4fc}
#far th{background:var(--card2);border-bottom:1px solid var(--line)}
#far th:first-child{border-radius:8px 0 0 8px}#far th:last-child{border-radius:0 8px 8px 0}
#far .bos .ms{color:var(--marka);background:color-mix(in srgb,var(--marka2) 9%,transparent);width:72px;height:72px;border-radius:20px;display:grid;place-items:center;margin:0 auto 10px}
body.koyu #far .bos .ms{color:#a5b4fc}
#far .sutun i{background:#059669}
#far .halka{background:conic-gradient(var(--marka) calc(var(--o) * 1%),var(--card2) 0)}
@media print{ #far .ust{background:none;box-shadow:none;padding:0} #far .kart{border-top-color:#ddd} }
</style>
@endpush

@section('content')
@php
  $tl = fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $tl0 = fn ($v) => number_format(round((float) $v), 0, ',', '.') . ' ₺';
  $mk = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $trh = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d.m.Y') : '—';
  $ayAd = fn ($ym) => \Carbon\Carbon::parse($ym . '-01')->locale('tr')->translatedFormat('M y');
  $q = ['bas' => $bas, 'bit' => $bit, 'ted' => $ted ?: null];
  $indir = fn ($r) => '/fiyat-anlasmalari/rapor-indir?' . http_build_query(array_filter($q + ['r' => $r]));
  $T = $rapor['tasarruf']; $F = $rapor['fazla']; $K = $rapor['kapsama'];
  $yaklasan = count($rapor['takvim']);
  $hzl = ['3' => '3 ay', '6' => '6 ay', '12' => '12 ay'];
  $gunFark = \Carbon\Carbon::parse($bas)->diffInMonths(\Carbon\Carbon::parse($bit));
  $tedAdSecili = $ted ? optional($tedarikciler->firstWhere('id', $ted))->ad : null;
  $telNorm = function ($t) { $d = preg_replace('/\D/', '', (string) $t); if ($d === '') return ''; if (str_starts_with($d, '0')) $d = '9' . $d; elseif (strlen($d) === 10) $d = '90' . $d; return $d; };
@endphp
<div id="far"><div class="pano">
  <div class="baski-bas"><h2 style="margin:0">Fiyat Anlaşmaları Raporu</h2><div style="color:#666;font-size:12px">{{ $trh($bas) }} – {{ $trh($bit) }}{{ $tedAdSecili ? ' · ' . $tedAdSecili : '' }}</div></div>
  <div class="ust">
    <a class="geri" href="/fiyat-anlasmalari"><span class="ms">arrow_back</span> Anlaşmalar</a>
    <form class="filtre" method="GET" action="/fiyat-anlasmalari/rapor">
      @foreach ($hzl as $ay => $ad)
        <a class="hz{{ $bit === today()->toDateString() && $gunFark == $ay ? ' on' : '' }}" href="/fiyat-anlasmalari/rapor?{{ http_build_query(array_filter(['bas' => today()->subMonths((int) $ay)->toDateString(), 'ted' => $ted ?: null])) }}">{{ $ad }}</a>
      @endforeach
      <input type="date" name="bas" value="{{ $bas }}" title="Başlangıç">
      <input type="date" name="bit" value="{{ $bit }}" title="Bitiş">
      <select name="ted"><option value="">Tüm tedarikçiler</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}" @selected($ted == $t->id)>{{ $t->ad }}</option>@endforeach</select>
      <button class="btn" type="submit"><span class="ms">filter_alt</span> Uygula</button>
      <button class="btn ik yazdirma" type="button" onclick="window.print()"><span class="ms">print</span> Yazdır / PDF</button>
    </form>
  </div>

  <div class="kpi">
    <div class="k" style="--kr:{{ $T['toplam'] >= 0 ? '#10B981' : '#F43F5E' }}"><span class="ki"><span class="ms">savings</span></span><div><div class="kd">{{ $tl0($T['toplam']) }}</div><div class="kl">Anlaşmaların net kazancı</div></div></div>
    <div class="k" style="--kr:#F43F5E"><span class="ki"><span class="ms">report</span></span><div><div class="kd">{{ $tl0($F['toplam']) }}</div><div class="kl">Anlaşma üstü fazla ödeme ({{ $F['adet'] }} kalem)</div></div></div>
    <div class="k" style="--kr:#4F46E5"><span class="ki"><span class="ms">donut_large</span></span><div><div class="kd">%{{ $K['oran'] }}</div><div class="kl">Alımların anlaşmalı fiyatla yapılan kısmı</div></div></div>
    <div class="k" style="--kr:#F59E0B"><span class="ki"><span class="ms">event</span></span><div><div class="kd">{{ $yaklasan }}</div><div class="kl">90 gün içinde bitecek / fesih kararı</div></div></div>
  </div>

  <div class="sekmeler">
    @foreach (['tasarruf' => ['savings', 'Tasarruf'], 'fazla' => ['report', 'Fazla Fatura'], 'taahhut' => ['flag', 'Taahhüt'], 'kapsama' => ['donut_large', 'Kapsama'], 'zarar' => ['trending_down', 'Zarara Döndü mü'], 'yenileme' => ['history', 'Yenileme Geçmişi'], 'tedarikci' => ['local_shipping', 'Tedarikçi Özeti'], 'takvim' => ['event', 'Yenileme Takvimi']] as $sk => [$si, $sa])
      <button type="button" class="sekme{{ $loop->first ? ' on' : '' }}" data-s="{{ $sk }}" onclick="farSekme('{{ $sk }}')"><span class="ms">{{ $si }}</span>{{ $sa }}</button>
    @endforeach
  </div>

  {{-- 1. TASARRUF --}}
  <div class="panel on" id="far-tasarruf"><div class="kart">
    <div class="kb"><span class="ms">savings</span><h3>Anlaşmalar ne kazandırdı?</h3><a class="btn ik yazdirma" href="{{ $indir('tasarruf') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Anlaşmalı fiyatla alınan her kalem, aynı malzemenin <b>±30 gün içinde başka tedarikçilerden alınan ortalama fiyatı</b> ile kıyaslanır. O dönemde başka alış yoksa anlaşma öncesi son fiyat kullanılır. Eksi değer: anlaşma o ay piyasadan pahalıya geldi.</p>
    @if (!count($T['aylik']))
      <div class="bos"><span class="ms">savings</span>Bu dönemde kıyaslanabilir anlaşmalı alış yok. Anlaşmalı malzemeyi başka tedarikçiden de aldıkça ya da anlaşma öncesi fiyat bilindikçe burası dolar.</div>
    @else
      @php
        $maxP = max(1, max(array_map(fn ($v) => max(0, $v), $T['aylik']))); $maxN = max(0, max(array_map(fn ($v) => max(0, -$v), $T['aylik'])));
        $negOran = $maxN > 0 ? $maxN / ($maxP + $maxN) : 0; $posOran = 1 - $negOran;
      @endphp
      <div class="grafik" role="img" aria-label="Aylık tasarruf grafiği">
        @foreach ($T['aylik'] as $ay => $v)
          <div class="sutun" title="{{ $ayAd($ay) }}: {{ $tl($v) }}">
            <div class="ust-y" style="flex:{{ $posOran }}">@if ($v >= 0)<span class="deg">{{ $tl0($v) }}</span><i style="height:{{ max(2, round($v / $maxP * 100)) }}%"></i>@endif</div>
            @if ($negOran > 0)<div class="alt-y" style="flex:{{ $negOran }}">@if ($v < 0)<i style="height:{{ max(2, round(-$v / $maxN * 100)) }}%"></i><span class="deg kir">{{ $tl0($v) }}</span>@endif</div>@endif
          </div>
        @endforeach
      </div>
      <div class="aylar">@foreach ($T['aylik'] as $ay => $v)<span>{{ $ayAd($ay) }}</span>@endforeach</div>
      @if ($T['kayip'] > 0)<p class="not-k">Brüt kazanç {{ $tl0($T['toplam'] + $T['kayip']) }}, anlaşmanın piyasadan pahalı kaldığı alışlarda kayıp {{ $tl0($T['kayip']) }}.</p>@endif
      <div class="tablo" style="margin-top:14px"><table>
        <thead><tr><th>Malzeme</th><th>Tedarikçi</th><th class="s">Anlaşma</th><th class="s">Kıyas fiyatı</th><th>Kıyas</th><th class="s">Alınan</th><th class="s">Kazanç</th></tr></thead>
        <tbody>
          @foreach ($T['satir'] as $s)
            <tr><td><b>{{ $s['mal'] }}</b></td><td>{{ $s['ted'] }}</td><td class="s">{{ $tl($s['anlasma']) }}</td><td class="s">{{ $s['ref'] !== null ? $tl($s['ref']) : '—' }}</td>
              <td><span class="roz g">{{ $s['kaynak'] }}</span></td><td class="s">{{ $mk($s['miktar']) }} {{ $s['birim'] }}</td>
              <td class="s {{ $s['tasarruf'] >= 0 ? 'yes' : 'kir' }}">{{ $tl($s['tasarruf']) }}</td></tr>
          @endforeach
        </tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 2. FAZLA FATURA --}}
  <div class="panel" id="far-fazla"><div class="kart">
    <div class="kb"><span class="ms">report</span><h3>Anlaşma üstü kesilen faturalar</h3><a class="btn ik yazdirma" href="{{ $indir('fazla') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Tedarikçi, anlaşma döneminde anlaşmalı fiyatın üstünde fatura kestiyse burada çıkar. Listeyi tedarikçiye gönderip <b>iade ya da sonraki faturadan mahsup</b> iste.</p>
    @forelse ($F['ted'] as $t)
      @php
        $wa = "Merhaba, " . $trh($bas) . " – " . $trh($bit) . " döneminde anlaşmalı fiyatın üstünde kesilen faturalar:\n";
        foreach ($t['satir'] as $s) $wa .= "• " . $trh($s['tarih']) . ($s['fatura_no'] ? " (Fatura " . $s['fatura_no'] . ")" : '') . ": " . $s['mal'] . " " . number_format($s['fatura'], 2, ',', '.') . " ₺ (anlaşma " . number_format($s['anlasma'], 2, ',', '.') . " ₺) × " . $mk($s['miktar']) . " " . $s['birim'] . " = " . number_format($s['fazla'], 2, ',', '.') . " ₺ fazla\n";
        $wa .= "Toplam: " . number_format($t['toplam'], 2, ',', '.') . " ₺. İade ya da sonraki faturadan mahsup edilmesini rica ederiz.";
        $tel = $telNorm($t['tel']);
      @endphp
      <div class="ted-kart">
        <div class="ted-bas"><b>{{ $t['ted'] }}</b><span class="roz k">{{ count($t['satir']) }} kalem · {{ $tl($t['toplam']) }} fazla</span>
          <a class="btn wa yazdirma" target="_blank" rel="noopener" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($wa) }}"><span class="ms">chat</span> WhatsApp'la gönder</a></div>
        <div class="tablo"><table>
          <thead><tr><th>Tarih</th><th>Fatura</th><th>Malzeme</th><th class="s">Anlaşma</th><th class="s">Faturada</th><th class="s">Miktar</th><th class="s">Fazla</th></tr></thead>
          <tbody>@foreach ($t['satir'] as $s)
            <tr><td>{{ $trh($s['tarih']) }}</td><td>{{ $s['fatura_no'] ?: '—' }}</td><td><b>{{ $s['mal'] }}</b></td><td class="s">{{ $tl($s['anlasma']) }}</td><td class="s kir">{{ $tl($s['fatura']) }}</td><td class="s">{{ $mk($s['miktar']) }} {{ $s['birim'] }}</td><td class="s kir">{{ $tl($s['fazla']) }}</td></tr>
          @endforeach</tbody>
        </table></div>
      </div>
    @empty
      <div class="bos"><span class="ms">task_alt</span>Bu dönemde anlaşma üstü fatura yok. Tedarikçiler anlaşmaya uymuş.</div>
    @endforelse
  </div></div>

  {{-- 3. TAAHHÜT --}}
  <div class="panel" id="far-taahhut"><div class="kart">
    <div class="kb"><span class="ms">flag</span><h3>Alım taahhüdü tutuyor mu?</h3><a class="btn ik yazdirma" href="{{ $indir('taahhut') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">"Ayda en az şu kadar alacağım" sözü verdiğin anlaşmalarda her ayın gerçekleşen alımı. <span class="roz k">%80 altı</span> ceza ya da fiyat kaybı riski, <span class="roz y">%100 üstü</span> bir sonraki pazarlıkta koz.</p>
    @if (!count($rapor['taahhut']))
      <div class="bos"><span class="ms">flag</span>Taahhüt girilmiş anlaşma yok. Sözleşmede ya da tekli fiyatta "aylık taahhüt" alanını doldurursan burada takip edilir.</div>
    @else
      @php $tumAy = collect($rapor['taahhut'])->flatMap(fn ($t) => array_keys($t['aylar']))->unique()->sort()->values(); @endphp
      <div class="tablo"><table>
        <thead><tr><th>Malzeme</th><th>Tedarikçi</th><th class="s">Taahhüt/ay</th>@foreach ($tumAy as $ay)<th class="s">{{ $ayAd($ay) }}</th>@endforeach</tr></thead>
        <tbody>@foreach ($rapor['taahhut'] as $t)
          <tr><td><b>{{ $t['mal'] }}</b></td><td>{{ $t['ted'] }}</td><td class="s">{{ $mk($t['taahhut']) }} {{ $t['birim'] }}</td>
            @foreach ($tumAy as $ay)
              @php $v = $t['aylar'][$ay] ?? null; @endphp
              <td class="s">@if ($v)<span class="roz {{ $v['oran'] >= 100 ? 'y' : ($v['oran'] >= 80 ? 'a' : 'k') }}" title="{{ $mk($v['miktar']) }} {{ $t['birim'] }}">%{{ $v['oran'] }}</span>@else<span class="gri">—</span>@endif</td>
            @endforeach</tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 4. KAPSAMA --}}
  <div class="panel" id="far-kapsama"><div class="kart">
    <div class="kb"><span class="ms">donut_large</span><h3>Alımların ne kadarı anlaşmalı?</h3><a class="btn ik yazdirma" href="{{ $indir('kapsama') }}"><span class="ms">download</span> Excel</a></div>
    <div class="kapsama">
      <div class="halka" style="--o:{{ $K['oran'] }}"><div><div><b>%{{ $K['oran'] }}</b><br><span>anlaşmalı</span></div></div></div>
      <div class="sat">Dönemdeki toplam alış: <b>{{ $tl0($K['toplam']) }}</b><br>Anlaşmalı fiyatla alınan: <b>{{ $tl0($K['anlasmali']) }}</b><br>Anlaşmasız alınan: <b>{{ $tl0($K['toplam'] - $K['anlasmali']) }}</b></div>
    </div>
    <p class="acik"><b>Anlaşma yapılmaya en değer malzemeler:</b> şu an yürürlükte anlaşması olmayan, en çok para harcanan 10 kalem. Fiyatı çok oynayanlarda sabit fiyat en çok işe yarar.</p>
    @if (!count($K['oneri']))
      <div class="bos"><span class="ms">verified</span>Anlaşmasız alım yok ya da bu dönemde fatura girilmemiş.</div>
    @else
      <div class="tablo"><table>
        <thead><tr><th>Malzeme</th><th class="s">Harcama</th><th class="s">Alış</th><th class="s">Tedarikçi</th><th class="s">Fiyat aralığı</th><th class="s">Oynaklık</th><th></th></tr></thead>
        <tbody>@foreach ($K['oneri'] as $s)
          <tr><td><b>{{ $s['mal'] }}</b></td><td class="s"><b>{{ $tl0($s['harcama']) }}</b></td><td class="s">{{ $s['alis'] }}</td><td class="s">{{ $s['ted_say'] }}</td>
            <td class="s">{{ $tl($s['min']) }} – {{ $tl($s['max']) }}{{ $s['birim'] ? ' /' . $s['birim'] : '' }}</td>
            <td class="s"><span class="roz {{ $s['oynaklik'] >= 20 ? 'k' : ($s['oynaklik'] >= 8 ? 'a' : 'g') }}">%{{ $s['oynaklik'] }}</span></td>
            <td class="s yazdirma"><a href="/fiyat-anlasmalari" class="roz g" style="text-decoration:none">Anlaşma yap →</a></td></tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 5. ZARAR --}}
  <div class="panel" id="far-zarar"><div class="kart">
    <div class="kb"><span class="ms">trending_down</span><h3>Anlaşma zarara döndü mü?</h3><a class="btn ik yazdirma" href="{{ $indir('zarar') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Yürürlükteki anlaşma fiyatı; son 30 günde başka tedarikçiden alınan <b>en düşük fiyat</b> ve güncel <b>hal ortalaması</b>@if ($rapor['hal_tarih']) ({{ $trh($rapor['hal_tarih']) }} bülteni)@endif ile kıyaslanır. Piyasanın %5, halin %25 üstündeyse <b>erken yeniden pazarlık</b> önerilir (hal toptan fiyattır, tedarikçinin biraz üstünde olması normal).</p>
    @if (!count($rapor['zarar']))
      <div class="bos"><span class="ms">balance</span>Kıyas yapılacak veri yok: yürürlükteki anlaşmalı malzemeler son 30 günde başka tedarikçiden alınmamış, halde de karşılığı yok.</div>
    @else
      <div class="tablo"><table>
        <thead><tr><th>Malzeme</th><th>Tedarikçi</th><th class="s">Anlaşma</th><th class="s">Piyasa (en düşük)</th><th class="s">Fark</th><th class="s">Hal ort.</th><th class="s">Fark</th><th>Durum</th></tr></thead>
        <tbody>@foreach ($rapor['zarar'] as $s)
          <tr><td><b>{{ $s['mal'] }}</b><div class="gri" style="font-size:11px">bitiş {{ $trh($s['bitis']) }}</div></td><td>{{ $s['ted'] }}</td><td class="s"><b>{{ $tl($s['anlasma']) }}</b>{{ $s['birim'] ? '/' . $s['birim'] : '' }}</td>
            <td class="s">@if ($s['piyasa'] !== null){{ $tl($s['piyasa']) }}<div class="gri" style="font-size:11px">{{ $s['piyasa_ted'] }}</div>@else<span class="gri">—</span>@endif</td>
            <td class="s">@if ($s['piyasa_fark'] !== null)<span class="{{ $s['piyasa_fark'] > 0 ? 'kir' : 'yes' }}">{{ $s['piyasa_fark'] > 0 ? '+' : '' }}{{ $s['piyasa_fark'] }}%</span>@else<span class="gri">—</span>@endif</td>
            <td class="s">{{ $s['hal'] !== null ? $tl($s['hal']) : '—' }}</td>
            <td class="s">@if ($s['hal_fark'] !== null)<span class="{{ $s['hal_fark'] > 25 ? 'kir' : 'gri' }}">{{ $s['hal_fark'] > 0 ? '+' : '' }}{{ $s['hal_fark'] }}%</span>@else<span class="gri">—</span>@endif</td>
            <td>@if ($s['zarar'])<span class="roz k"><span class="ms" style="font-size:13px">warning</span>Yeniden pazarlık</span>@else<span class="roz y">Uygun</span>@endif</td></tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 6. YENİLEME GEÇMİŞİ --}}
  <div class="panel" id="far-yenileme"><div class="kart">
    <div class="kb"><span class="ms">history</span><h3>Yenilemelerde fiyat nasıl değişti?</h3><a class="btn ik yazdirma" href="{{ $indir('yenileme') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Aynı tedarikçiyle aynı malzeme için yapılan ardışık anlaşmalar. Bir sonraki yenilemede "geçen sefer %X verdiniz" diye elinde veri olur.</p>
    @if (!count($rapor['yenileme']['satir']))
      <div class="bos"><span class="ms">history</span>Henüz yenilenmiş anlaşma yok. Aynı tedarikçi ve malzeme için ikinci anlaşma girildiğinde fiyat değişimi burada izlenir.</div>
    @else
      @if (count($rapor['yenileme']['ted']))
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px">
          @foreach ($rapor['yenileme']['ted'] as $t)<span class="roz {{ $t['ort'] > 15 ? 'k' : ($t['ort'] > 0 ? 'a' : 'y') }}" style="font-size:12px;padding:5px 11px">{{ $t['ted'] }}: yenilemede ort. {{ $t['ort'] > 0 ? '+' : '' }}{{ number_format($t['ort'], 1, ',', '.') }}% ({{ $t['say'] }})</span>@endforeach
        </div>
      @endif
      <div class="tablo"><table>
        <thead><tr><th>Malzeme</th><th>Tedarikçi</th><th>Anlaşmalar</th><th class="s">Toplam değişim</th></tr></thead>
        <tbody>@foreach ($rapor['yenileme']['satir'] as $s)
          <tr><td><b>{{ $s['mal'] }}</b></td><td>{{ $s['ted'] }}</td>
            <td>@foreach ($s['adimlar'] as $a)<span class="adim">{{ $trh($a['baslangic']) }} · <b>{{ $tl($a['fiyat']) }}</b>@if ($a['degisim'] !== null) <span class="{{ $a['degisim'] > 0 ? 'kir' : 'yes' }}">{{ $a['degisim'] > 0 ? '+' : '' }}{{ number_format($a['degisim'], 1, ',', '.') }}%</span>@endif</span>@endforeach</td>
            <td class="s">@if ($s['toplam'] !== null)<span class="{{ $s['toplam'] > 0 ? 'kir' : 'yes' }}">{{ $s['toplam'] > 0 ? '+' : '' }}{{ number_format($s['toplam'], 1, ',', '.') }}%</span>@endif</td></tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 7. TEDARİKÇİ ÖZETİ --}}
  <div class="panel" id="far-tedarikci"><div class="kart">
    <div class="kb"><span class="ms">local_shipping</span><h3>Tedarikçi bazında özet</h3><a class="btn ik yazdirma" href="{{ $indir('tedarikci') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Anlaşması olan her tedarikçi: faturada anlaşmaya ne kadar uyduğu, kazandırdığı, fazla kestiği ve siparişleri zamanında getirip getirmediği.</p>
    @if (!count($rapor['tedarikci']))
      <div class="bos"><span class="ms">local_shipping</span>Anlaşmalı tedarikçi yok.</div>
    @else
      <div class="tablo"><table>
        <thead><tr><th>Tedarikçi</th><th class="s">Yürürlükte</th><th class="s">Anlaşmalı fatura</th><th class="s">Fatura uyumu</th><th class="s">Kazanç</th><th class="s">Fazla kesilen</th><th class="s">Zamanında teslim</th></tr></thead>
        <tbody>@foreach ($rapor['tedarikci'] as $s)
          <tr><td><b>{{ $s['ted'] }}</b></td><td class="s">{{ $s['anlasma'] }}</td><td class="s">{{ $s['fatura'] }}</td>
            <td class="s">@if ($s['uyum'] !== null)<span class="roz {{ $s['uyum'] >= 95 ? 'y' : ($s['uyum'] >= 80 ? 'a' : 'k') }}">%{{ $s['uyum'] }}</span>@else<span class="gri">—</span>@endif</td>
            <td class="s {{ $s['tasarruf'] >= 0 ? 'yes' : 'kir' }}">{{ $s['fatura'] ? $tl0($s['tasarruf']) : '—' }}</td>
            <td class="s {{ $s['fazla'] > 0 ? 'kir' : 'gri' }}">{{ $s['fazla'] > 0 ? $tl0($s['fazla']) : '—' }}</td>
            <td class="s">@if ($s['teslim'] !== null)<span class="roz {{ $s['teslim'] >= 90 ? 'y' : ($s['teslim'] >= 70 ? 'a' : 'k') }}">%{{ $s['teslim'] }}</span>@else<span class="gri">—</span>@endif</td></tr>
        @endforeach</tbody>
      </table></div>
    @endif
  </div></div>

  {{-- 8. YENİLEME TAKVİMİ --}}
  <div class="panel" id="far-takvim"><div class="kart">
    <div class="kb"><span class="ms">event</span><h3>Önümüzdeki 90 gün</h3><a class="btn ik yazdirma" href="{{ $indir('takvim') }}"><span class="ms">download</span> Excel</a></div>
    <p class="acik">Biten anlaşmalar ve sözleşmelerde <b>fesih bildirimi için son gün</b>. Pazarlığa zamanında hazırlan; sistem ayrıca 30/15/7 gün kala bildirim gönderir.</p>
    @if (!count($rapor['takvim']))
      <div class="bos"><span class="ms">event_available</span>Önümüzdeki 90 günde biten anlaşma yok.</div>
    @else
      <div class="zaman">@foreach ($rapor['takvim'] as $s)
        @php $c = \Carbon\Carbon::parse($s['ilk']); $kalan = (int) today()->diffInDays($c, false); @endphp
        <div class="olay">
          <div class="gun"><b>{{ $c->format('d') }}</b><span>{{ $c->locale('tr')->translatedFormat('M') }}</span></div>
          <div class="ic"><b>{{ $s['ad'] }}</b><span>{{ $s['ted'] }} · {{ $s['kalem'] }} malzeme · bitiş {{ $trh($s['bitis']) }}@if ($s['fesih']) · fesih için son gün {{ $trh($s['fesih']) }}@endif</span></div>
          <span class="roz {{ $kalan <= 7 ? 'k' : ($kalan <= 30 ? 'a' : 'g') }}">{{ $s['fesih'] && $s['ilk'] === $s['fesih'] ? 'Fesih kararı: ' : '' }}{{ $kalan }} gün</span>
        </div>
      @endforeach</div>
    @endif
  </div></div>
</div></div>
<script>
function farSekme(s) {
  document.querySelectorAll('#far .sekme').forEach(b => b.classList.toggle('on', b.dataset.s === s));
  document.querySelectorAll('#far .panel').forEach(p => p.classList.toggle('on', p.id === 'far-' + s));
  try { history.replaceState(null, '', '#' + s); } catch (e) {}
}
if (location.hash && document.getElementById('far-' + location.hash.slice(1))) farSekme(location.hash.slice(1));
</script>
@endsection

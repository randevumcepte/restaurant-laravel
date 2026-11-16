@extends('layout.app')
@section('rk_ikon', 'hourglass_bottom')
@section('baslik', 'Tedarikçi Yaşlandırma')
@section('title', 'Tedarikçi Yaşlandırma')

@push('head')
<style>
#ty{--marka:#6366F1;--marka2:#8B5CF6;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--mavi:#3B82F6;--gri:#94A3B8;
  --card:#fff;--card2:#F5F5FF;--bg:#F8FAFC;--ink:#1E293B;--sub:#64748B;--sub2:#94A3B8;--line:#E2E8F0}
body.koyu #ty{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#ty{background:var(--bg);min-height:calc(100vh - 60px)}#ty *{box-sizing:border-box}
#ty .pano{max-width:1240px;margin:0 auto;padding:20px 20px 44px}
#ty .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}

/* üst bant */
#ty .hero{background:linear-gradient(120deg,#6366F1 0%,#7C6CF4 55%,#8B5CF6 100%);border-radius:18px;padding:20px 22px;color:#fff;display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:16px;box-shadow:0 8px 24px rgba(99,102,241,.22)}
#ty .hero .ik{width:46px;height:46px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center}
#ty .hero .ik .material-icons-outlined{font-size:26px}
#ty .hero h1{margin:0;font-size:19px;font-weight:800;color:#fff}
#ty .hero p{margin:3px 0 0;font-size:12.5px;opacity:.88}
#ty .hero .sag{margin-left:auto;text-align:right}
#ty .hero .sag .t{font-size:11px;opacity:.85;font-weight:600}
#ty .hero .sag .v{font-size:24px;font-weight:900;line-height:1.1}

/* KPI */
#ty .ust{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:16px}
@media(max-width:980px){#ty .ust{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){#ty .ust{grid-template-columns:1fr 1fr}}
#ty .kpi{background:var(--card);border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 4px 14px rgba(15,23,42,.04);padding:14px 15px;position:relative;overflow:hidden}
#ty .kpi::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--c)}
#ty .kpi .lbl{font-size:11.5px;color:var(--sub);font-weight:700;display:flex;align-items:center;gap:6px}
#ty .kpi .lbl i{width:8px;height:8px;border-radius:50%;background:var(--c);display:inline-block}
#ty .kpi .deg{font-size:21px;font-weight:900;color:var(--ink);margin-top:6px;line-height:1}
#ty .kpi .alt{font-size:11px;color:var(--sub2);margin-top:5px;font-weight:600}

/* ana ızgara */
#ty .izgara{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(0,1fr);gap:16px;margin-bottom:16px;align-items:start}
@media(max-width:900px){#ty .izgara{grid-template-columns:1fr}}
#ty .kart{background:var(--card);border-radius:16px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 4px 14px rgba(15,23,42,.04);padding:18px}
#ty .kb{display:flex;align-items:center;gap:9px;font-size:14.5px;font-weight:800;color:var(--ink);margin-bottom:14px}
#ty .kb .material-icons-outlined{color:var(--marka);font-size:20px}
#ty .kb .rozet{margin-left:auto;font-size:10.5px;font-weight:800;padding:4px 9px;border-radius:20px;background:#EEF0FF;color:#5B5BD6;display:inline-flex;align-items:center;gap:4px}
#ty .kb .rozet .material-icons-outlined{font-size:13px;color:inherit}
body.koyu #ty .kb .rozet{background:rgba(99,102,241,.18);color:#A5B4FC}
#ty .yan{display:flex;flex-direction:column;gap:16px}

/* ödeme planı */
#ty .ozet{background:var(--card2);border-radius:12px;padding:12px 14px;font-size:13px;color:var(--ink);line-height:1.55;margin-bottom:12px;display:flex;gap:10px}
#ty .ozet .material-icons-outlined{color:var(--marka);font-size:19px;flex-shrink:0}
#ty .adim{display:flex;gap:13px;padding:13px 0;border-top:1px solid var(--line)}
#ty .ozet + .adim{border-top:0}
#ty .adim .no{width:30px;height:30px;border-radius:10px;flex-shrink:0;background:linear-gradient(135deg,var(--marka),var(--marka2));color:#fff;font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center}
#ty .adim .gv{flex:1;min-width:0}
#ty .adim .ust2{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
#ty .adim .ad{font-weight:800;font-size:14px;color:var(--ink)}
#ty .adim .tut{margin-left:auto;font-weight:900;font-size:14.5px;color:var(--ink)}
#ty .zaman{font-size:10.5px;font-weight:800;padding:3px 8px;border-radius:20px;background:color-mix(in srgb,var(--zc) 13%,transparent);color:var(--zc)}
#ty .adim .satir{font-size:12.5px;color:var(--sub);margin-top:5px;line-height:1.5}
#ty .adim .satir b{color:var(--ink);font-weight:700}
#ty .adim .satir.aks{color:var(--ink)}
#ty .yukl{display:flex;align-items:center;gap:8px;font-size:11.5px;color:var(--sub);margin-top:10px}
#ty .yukl .nok{width:7px;height:7px;border-radius:50%;background:var(--marka);animation:tyNab 1s infinite alternate}
@keyframes tyNab{from{opacity:.25}to{opacity:1}}
#ty .not{font-size:11px;color:var(--sub2);margin-top:10px}

/* vade dağılımı */
#ty .serit{display:flex;height:14px;border-radius:8px;overflow:hidden;background:var(--line);margin-bottom:14px}
#ty .serit span{display:block;height:100%}
#ty .lej{display:flex;flex-direction:column;gap:9px}
#ty .lej .s{display:flex;align-items:center;gap:9px;font-size:12.5px;color:var(--ink)}
#ty .lej .s i{width:10px;height:10px;border-radius:3px;background:var(--c);flex-shrink:0}
#ty .lej .s .y{color:var(--sub);font-size:11.5px;margin-left:auto;font-weight:700;min-width:38px;text-align:right}
#ty .lej .s .t{font-weight:800;min-width:96px;text-align:right}

/* en büyük borçlar */
#ty .bb{display:flex;flex-direction:column;gap:11px}
#ty .bb .s .ust3{display:flex;font-size:12.5px;color:var(--ink);margin-bottom:5px}
#ty .bb .s .ust3 b{margin-left:auto;font-weight:800}
#ty .bb .bar{height:7px;border-radius:5px;background:var(--line);overflow:hidden}
#ty .bb .bar span{display:block;height:100%;border-radius:5px;background:linear-gradient(90deg,var(--marka),var(--marka2))}
#ty .bb .bar span.g{background:linear-gradient(90deg,#FB7185,#F43F5E)}

/* tablo */
#ty .tsar{overflow-x:auto}
#ty table{width:100%;border-collapse:collapse;font-size:12.5px;min-width:720px}
#ty th{text-align:right;color:var(--sub);font-weight:700;font-size:10.5px;padding:9px 10px;background:var(--card2);text-transform:uppercase;letter-spacing:.3px}
#ty th:first-child{text-align:left;border-radius:9px 0 0 9px}#ty th:last-child{border-radius:0 9px 9px 0}
#ty td{padding:11px 10px;border-bottom:1px solid var(--line);text-align:right;color:var(--ink);vertical-align:top}
#ty td:first-child{text-align:left;font-weight:700}
#ty tbody tr:hover td{background:color-mix(in srgb,var(--marka) 3%,transparent)}
#ty .gec{color:var(--kirmizi);font-weight:800}#ty .haf{color:#D97706;font-weight:700}
#ty .top{font-weight:900}
#ty .tire{color:var(--sub2)}
#ty .btnm{border:0;border-radius:8px;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;color:#047857;background:rgba(16,185,129,.13);display:inline-flex;align-items:center;gap:4px;white-space:nowrap}
#ty .btnm:hover{background:rgba(16,185,129,.22)}
#ty .btnm .material-icons-outlined{font-size:14px}
#ty details{margin-top:5px}
#ty summary{cursor:pointer;font-size:11px;color:var(--marka);font-weight:700;list-style:none;display:inline-flex;align-items:center;gap:2px}
#ty summary::-webkit-details-marker{display:none}
#ty summary .material-icons-outlined{font-size:16px;transition:transform .15s}
#ty details[open] summary .material-icons-outlined{transform:rotate(180deg)}
#ty .fat{display:flex;align-items:center;gap:12px;padding:7px 10px;border-radius:9px;background:var(--card2);margin:5px 0;font-size:11.5px;font-weight:600}
#ty .fat .no{flex:1;color:var(--sub)}
#ty .fat .vade{font-weight:700}
#ty .bilgi{font-size:11.5px;color:var(--sub);margin-top:12px;display:flex;gap:7px;align-items:flex-start;line-height:1.5}
#ty .bilgi .material-icons-outlined{font-size:16px;color:var(--marka)}
#ty .bos{text-align:center;color:var(--sub);padding:44px 20px;font-size:13px;line-height:1.6}
#ty .bos .ms{width:58px;height:58px;border-radius:18px;background:rgba(16,185,129,.12);color:var(--yesil);display:flex;align-items:center;justify-content:center;margin:0 auto 12px}
#ty .bos .ms .material-icons-outlined{font-size:30px}
#ty .bos b{color:var(--ink);font-size:14.5px;display:block;margin-bottom:4px}

/* ── Renk katmanı: lila zemin, tonlu kartlar ── */
#ty{background:linear-gradient(180deg,#EEF0FF 0%,#F5F3FF 45%,#F8FAFC 100%);border-radius:18px}
body.koyu #ty{background:var(--bg)}
#ty .kpi{background:linear-gradient(135deg,color-mix(in srgb,var(--c) 11%,var(--card)) 0%,var(--card) 75%);border:1px solid color-mix(in srgb,var(--c) 18%,transparent)}
#ty .kpi .deg{color:color-mix(in srgb,var(--c) 70%,var(--ink))}
#ty .kpi .ki{position:absolute;right:12px;top:12px;width:32px;height:32px;border-radius:10px;background:color-mix(in srgb,var(--c) 15%,transparent);color:var(--c);display:flex;align-items:center;justify-content:center}
#ty .kpi .ki .material-icons-outlined{font-size:18px}
#ty .kart{border:1px solid #E7E8FB}
body.koyu #ty .kart{border-color:var(--line)}
#ty .kart > .kb{margin:-18px -18px 14px;padding:13px 18px;border-radius:16px 16px 0 0;background:linear-gradient(90deg,#F1F0FF,#F8F7FF 70%,transparent);border-bottom:1px solid #ECEBFC}
body.koyu #ty .kart > .kb{background:rgba(99,102,241,.10);border-color:var(--line)}
#ty .kb .material-icons-outlined:first-child{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--marka),var(--marka2));color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:17px}
#ty .kb .rozet .material-icons-outlined:first-child{width:auto;height:auto;background:none;color:inherit;font-size:13px}
#ty .ozet{background:linear-gradient(135deg,#EEF0FF,#F5F0FF);border:1px solid #E0E1FC}
body.koyu #ty .ozet{background:rgba(99,102,241,.12);border-color:var(--line)}
#ty .adim{border-top:0;margin-top:9px;padding:13px;border-radius:13px;background:color-mix(in srgb,var(--zc,#6366F1) 5%,var(--card));border:1px solid color-mix(in srgb,var(--zc,#6366F1) 16%,transparent);border-left:4px solid var(--zc,#6366F1)}
#ty .adim .no{background:var(--zc,#6366F1)}
#ty .adim .tut{color:color-mix(in srgb,var(--zc,#6366F1) 75%,var(--ink))}
#ty tbody tr:nth-child(even) td{background:#FAFAFF}
body.koyu #ty tbody tr:nth-child(even) td{background:transparent}
#ty td.top{color:#4F46E5}

/* ── Responsive ── */
.rk-main,.rk-body{min-width:0}
#ty{overflow-x:hidden}
@media(max-width:700px){
  #ty .pano{padding:12px 12px 32px}
  #ty .hero{padding:16px;gap:12px}
  #ty .hero .ik{width:40px;height:40px}
  #ty .hero h1{font-size:17px}
  #ty .hero .sag{margin-left:0;text-align:left;width:100%;background:rgba(255,255,255,.14);border-radius:12px;padding:10px 12px}
  #ty .hero .sag .v{font-size:22px}
  #ty .ust{gap:9px}
  #ty .kpi{padding:12px}
  #ty .kpi .deg{font-size:17px}
  #ty .kpi .ki{display:none}
  #ty .ust .kpi:first-child{grid-column:1 / -1}
  #ty .kart{padding:14px}
  #ty .kart > .kb{margin:-14px -14px 12px;padding:11px 14px;flex-wrap:wrap}
  #ty .kb .rozet{margin-left:0}
  #ty .adim{gap:10px;padding:11px}
  #ty .adim .tut{margin-left:0;width:100%}
  #ty .lej .s .t{min-width:80px}
  /* tablo → kart */
  #ty .tsar{overflow:visible}
  #ty table,#ty tbody{display:block;width:100%;min-width:0}
  #ty .ust > *,#ty .izgara > *,#ty .hero > div{min-width:0}
  #ty .hero > div:nth-child(2){flex:1 1 200px}
  #ty thead{display:none}
  #ty tbody tr{display:block;border:1px solid #E7E8FB;border-radius:13px;margin-bottom:10px;padding:10px 12px;background:var(--card)}
  #ty tbody tr:nth-child(even) td,#ty tbody tr:hover td{background:transparent}
  #ty td{display:flex;justify-content:space-between;align-items:center;gap:10px;border:0;padding:5px 0;text-align:right}
  #ty td::before{content:attr(data-l);color:var(--sub);font-size:11px;font-weight:700;text-transform:uppercase}
  #ty td:first-child{display:block;font-size:14px;padding-bottom:8px;border-bottom:1px solid var(--line);margin-bottom:4px}
  #ty td:first-child::before,#ty td:last-child::before{display:none}
  #ty td.tire{display:none}
  #ty td:last-child{justify-content:flex-end;padding-top:8px}
  #ty .fat{flex-wrap:wrap;gap:6px 10px}
  #ty .fat .no{flex-basis:100%}
}
</style>
@endpush

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
  $T = max(1, (float) $y['toplam']);
  $yz = fn($v) => $y['toplam'] > 0 ? '%' . number_format(((float) $v / $T) * 100, 0) : '';
  $dilim = [
    ['Gecikmiş', $y['gecikmis'], '#F43F5E', 'Vadesi geçmiş', 'error_outline'],
    ['Bu hafta', $y['hafta'], '#F59E0B', '0–7 gün içinde', 'schedule'],
    ['8–30 gün', $y['ay'], '#3B82F6', 'Bu ay içinde', 'date_range'],
    ['31+ gün', $y['ileri'], '#10B981', 'Vadesi uzak', 'event_available'],
  ];
  $faturaSay = array_sum(array_map(fn($s) => count($s['faturalar']), $y['tedarikciler'] ?? []));
  $zRenk = ['Hemen' => '#F43F5E', 'Bu hafta' => '#D97706', '2 hafta içinde' => '#3B82F6', 'Vadesinde' => '#10B981'];
@endphp
<div id="ty"><div class="pano">

  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="hero">
    <div class="ik"><span class="material-icons-outlined">hourglass_bottom</span></div>
    <div>
      <h1>Tedarikçi Yaşlandırma</h1>
      <p>Ödenmemiş alış faturaları vadesine göre · {{ count($y['tedarikciler'] ?? []) }} tedarikçi, {{ $faturaSay }} fatura · {{ now()->format('d.m.Y') }}</p>
    </div>
    <div class="sag"><div class="t">Toplam borç</div><div class="v">{{ $tl($y['toplam']) }}</div></div>
  </div>

  <div class="ust">
    <div class="kpi" style="--c:#6366F1"><span class="ki"><span class="material-icons-outlined">account_balance_wallet</span></span><div class="lbl"><i></i>Toplam Borç</div><div class="deg">{{ $tl($y['toplam']) }}</div><div class="alt">{{ $faturaSay }} açık fatura</div></div>
    @foreach($dilim as $d)
      <div class="kpi" style="--c:{{ $d[2] }}"><span class="ki"><span class="material-icons-outlined">{{ $d[4] }}</span></span><div class="lbl"><i></i>{{ $d[0] }}</div><div class="deg">{{ $tl($d[1]) }}</div><div class="alt">{{ $d[3] }}@if($y['toplam']>0) · {{ $yz($d[1]) }}@endif</div></div>
    @endforeach
  </div>

  @if($plan)
  <div class="izgara">
    <div class="kart" id="tyPlan">
      <div class="kb"><span class="material-icons-outlined">auto_awesome</span> Ödeme Önceliği Planı
        <span class="rozet" id="tyRozet"><span class="material-icons-outlined">rule</span> Kural tabanlı</span></div>
      <div id="tyPlanGovde">
        <div class="ozet"><span class="material-icons-outlined">lightbulb</span><div>{{ $plan['ozet'] }}</div></div>
        @foreach($plan['adimlar'] as $i => $a)
          <div class="adim" style="--zc:{{ $zRenk[$a['zaman']] ?? '#6366F1' }}">
            <div class="no">{{ $i + 1 }}</div>
            <div class="gv">
              <div class="ust2"><span class="ad">{{ $a['tedarikci'] }}</span><span class="zaman" style="--zc:{{ $zRenk[$a['zaman']] ?? '#6366F1' }}">{{ $a['zaman'] }}</span><span class="tut">{{ $tl($a['tutar']) }}</span></div>
              <div class="satir"><b>Neden:</b> {{ $a['neden'] }}</div>
              <div class="satir aks"><b>Ne yapmalı:</b> {{ $a['aksiyon'] }}</div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="yukl" id="tyYukl"><span class="nok"></span> AI Nakit Danışmanı planı hazırlıyor…</div>
    </div>

    <div class="yan">
      <div class="kart">
        <div class="kb"><span class="material-icons-outlined">donut_large</span> Vade Dağılımı</div>
        <div class="serit">
          @foreach($dilim as $d) @if($d[1] > 0)<span style="width:{{ ($d[1] / $T) * 100 }}%;background:{{ $d[2] }}" title="{{ $d[0] }}: {{ $tl($d[1]) }}"></span>@endif @endforeach
        </div>
        <div class="lej">
          @foreach($dilim as $d)
            <div class="s" style="--c:{{ $d[2] }}"><i></i>{{ $d[0] }}<span class="y">{{ $yz($d[1]) }}</span><span class="t">{{ $tl($d[1]) }}</span></div>
          @endforeach
        </div>
      </div>
      <div class="kart">
        <div class="kb"><span class="material-icons-outlined">leaderboard</span> En Büyük Borçlar</div>
        <div class="bb">
          @php $enBuyuk = collect($y['tedarikciler'])->sortByDesc('toplam')->take(5); $mx = max(1, (float) ($enBuyuk->first()['toplam'] ?? 1)); @endphp
          @foreach($enBuyuk as $s)
            <div class="s">
              <div class="ust3">{{ $s['ad'] }}<b>{{ $tl($s['toplam']) }}</b></div>
              <div class="bar"><span class="{{ $s['gecikmis'] > 0 ? 'g' : '' }}" style="width:{{ ($s['toplam'] / $mx) * 100 }}%"></span></div>
            </div>
          @endforeach
        </div>
        <div class="not">Kırmızı çubuk: gecikmiş borcu olan tedarikçi.</div>
      </div>
    </div>
  </div>
  @endif

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">local_shipping</span> Tedarikçi Borçları</div>
    @if(empty($y['tedarikciler']))
      <div class="bos"><div class="ms"><span class="material-icons-outlined">check_circle</span></div><b>Ödenmemiş alış faturası yok</b>Borç görünmüyor. Alış faturaları Mal Kabul / Gelen e-Fatura ekranlarından girildikçe burada vadesine göre listelenir; "ödendi" işaretlenen fatura buradan düşer.</div>
    @else
      <div class="tsar"><table>
        <thead><tr><th>Tedarikçi</th><th>Gecikmiş</th><th>Bu hafta</th><th>8–30 gün</th><th>31+ gün</th><th>Toplam</th><th></th></tr></thead>
        <tbody>
          @foreach($y['tedarikciler'] as $s)
          <tr>
            <td>{{ $s['ad'] }}
              <details>
                <summary>{{ count($s['faturalar']) }} fatura <span class="material-icons-outlined">expand_more</span></summary>
                @foreach($s['faturalar'] as $f)
                  <div class="fat">
                    <span class="no">{{ $f['no'] ?: ('#'.$f['id']) }} · {{ \Carbon\Carbon::parse($f['tarih'])->format('d.m.Y') }}</span>
                    <span class="vade" style="color:{{ $f['gun']<0?'var(--kirmizi)':($f['gun']<=7?'#D97706':'var(--sub)') }}">{{ $f['gun']<0 ? abs($f['gun']).' gün gecikmiş' : ($f['gun']==0 ? 'vadesi bugün' : $f['gun'].' gün kaldı') }}</span>
                    <span style="font-weight:800">{{ $tl($f['tutar']) }}</span>
                    @if($patronMu)
                    <form method="POST" action="/tedarikci-yaslandirma/odendi" style="margin:0" onsubmit="return confirm('Bu fatura ödendi olarak işaretlensin mi?')">@csrf<input type="hidden" name="fatura_id" value="{{ $f['id'] }}"><button class="btnm" type="submit" title="Ödendi işaretle"><span class="material-icons-outlined">check</span></button></form>
                    @endif
                  </div>
                @endforeach
              </details>
            </td>
            <td data-l="Gecikmiş" class="{{ $s['gecikmis']>0?'gec':'tire' }}">{{ $s['gecikmis']>0?$tl($s['gecikmis']):'–' }}</td>
            <td data-l="Bu hafta" class="{{ $s['hafta']>0?'haf':'tire' }}">{{ $s['hafta']>0?$tl($s['hafta']):'–' }}</td>
            <td data-l="8–30 gün" class="{{ $s['ay']>0?'':'tire' }}">{{ $s['ay']>0?$tl($s['ay']):'–' }}</td>
            <td data-l="31+ gün" class="{{ $s['ileri']>0?'':'tire' }}">{{ $s['ileri']>0?$tl($s['ileri']):'–' }}</td>
            <td data-l="Toplam" class="top">{{ $tl($s['toplam']) }}</td>
            <td>@if($patronMu)<form method="POST" action="/tedarikci-yaslandirma/odendi" onsubmit="return confirm({{ json_encode($s['ad'] . ' için tüm açık faturalar ödendi olarak işaretlensin mi?', JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) }})" style="margin:0">@csrf<input type="hidden" name="tedarikci_id" value="{{ $s['id'] }}"><button class="btnm" type="submit"><span class="material-icons-outlined">done_all</span> Tümü ödendi</button></form>@endif</td>
          </tr>
          @endforeach
        </tbody>
      </table></div>
      <div class="bilgi"><span class="material-icons-outlined">info</span><span>Borç = ödenmemiş alış faturaları. Vade, faturadaki vade tarihinden; yoksa tedarikçinin ödeme vadesi gününden hesaplanır. Ödeme yaptığınızda faturayı "ödendi" işaretleyin, listeden düşer.</span></div>
    @endif
  </div>

</div></div>

@if($plan)
<script>
(function () {
  var govde = document.getElementById('tyPlanGovde'), yukl = document.getElementById('tyYukl'), rozet = document.getElementById('tyRozet');
  var renk = {!! json_encode($zRenk, JSON_UNESCAPED_UNICODE) !!};
  var hata = '<span class="material-icons-outlined" style="font-size:15px">info</span> AI planı şu an alınamadı, kural tabanlı plan gösteriliyor.';
  function es(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function tl(v) { return new Intl.NumberFormat('tr-TR', {maximumFractionDigits: 0}).format(Number(v) || 0) + ' ₺'; }
  fetch('/tedarikci-yaslandirma/ai', {headers: {'Accept': 'application/json'}, credentials: 'same-origin'})
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d || !d.ok || !d.adimlar || !d.adimlar.length) { yukl.innerHTML = hata; return; }
      var h = d.ozet ? '<div class="ozet"><span class="material-icons-outlined">lightbulb</span><div>' + es(d.ozet) + '</div></div>' : '';
      d.adimlar.forEach(function (a, i) {
        h += '<div class="adim" style="--zc:' + (renk[a.zaman] || '#6366F1') + '"><div class="no">' + (i + 1) + '</div><div class="gv">'
          + '<div class="ust2"><span class="ad">' + es(a.tedarikci) + '</span>'
          + (a.zaman ? '<span class="zaman" style="--zc:' + (renk[a.zaman] || '#6366F1') + '">' + es(a.zaman) + '</span>' : '')
          + (a.tutar ? '<span class="tut">' + tl(a.tutar) + '</span>' : '') + '</div>'
          + (a.neden ? '<div class="satir"><b>Neden:</b> ' + es(a.neden) + '</div>' : '')
          + (a.aksiyon ? '<div class="satir aks"><b>Ne yapmalı:</b> ' + es(a.aksiyon) + '</div>' : '')
          + '</div></div>';
      });
      govde.innerHTML = h;
      rozet.innerHTML = '<span class="material-icons-outlined">auto_awesome</span> AI Nakit Danışmanı';
      yukl.innerHTML = '<span class="material-icons-outlined" style="font-size:15px">info</span> AI önerisidir; ödeme kararını kontrol ederek verin.';
    })
    .catch(function () { yukl.innerHTML = hata; });
})();
</script>
@endif
@endsection

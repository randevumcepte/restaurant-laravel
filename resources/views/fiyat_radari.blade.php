@extends('layout.app')
@section('title', 'Fiyat Radarı')
@section('rk_ikon', 'radar')
@section('baslik', 'Fiyat Radarı')
@section('rk_alt', 'Satın alma rapor merkezi · kimden, kaça, neye göre · Excel/PDF · AI analiz')

@section('content')
@php
  $filtre = $filtre ?? ['gun' => 120, 'ted' => 0, 'esik' => 8];
  $kpi = $kpi ?? []; $zamlar = $zamlar ?? []; $malzemeler = $malzemeler ?? []; $karsilastirma = $karsilastirma ?? []; $katalog = $katalog ?? [];
  $karne = $karne ?? []; $hal = $hal ?? ['satirlar' => [], 'bulten' => null, 'urun_say' => 0, 'yillik_fazla' => 0]; $aylik = $aylik ?? []; $kategori = $kategori ?? [];
  $bos = $bos ?? [];
  $dokum = $dokum ?? []; $gecmis = $gecmis ?? []; $tedarikciler = $tedarikciler ?? []; $raporlar = $raporlar ?? []; $ai = $ai ?? null;
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $tl0 = fn($v) => number_format(round((float) $v), 0, ',', '.') . ' ₺';
  $tar = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('d.m.Y') : '—';
  $mik = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $qs = http_build_query(['gun' => $filtre['gun'], 'ted' => $filtre['ted'], 'esik' => $filtre['esik']]);
  $indir = fn($rapor, $fmt) => '/fiyat-radari/indir?rapor=' . $rapor . '&format=' . $fmt . '&' . $qs;
  $gecmisJs = []; foreach ($gecmis as $mid => $g) $gecmisJs[$mid] = array_slice(array_reverse($g), 0, 30);
  $halSat = $hal['satirlar'] ?? [];
  $maxAy = max(1, ...array_map(fn($a) => $a['tutar'], $aylik ?: [['tutar' => 1]]));
  $dokumGoster = array_slice($dokum, 0, 400);
@endphp

<style>
  .fr{--fr-mor:#7C3AED;--fr-pembe:#EC4899;--fr-mavi:#4F46E5;--fr-cyan:#0EA5E9;display:grid;grid-template-columns:minmax(0,1fr)} /* geniş tablo/sekme içerik alanını taşırmasın */
  .fr-hero{position:relative;overflow:hidden;border-radius:20px;padding:22px 24px;color:#fff;margin-bottom:18px;
    background:radial-gradient(120% 140% at 0% 0%,#8B5CF6 0%,#6D28D9 38%,#4338CA 70%,#1E1B4B 100%);box-shadow:0 18px 40px -18px rgba(76,29,149,.55)}
  .fr-hero::after{content:'';position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(236,72,153,.55),transparent 65%);pointer-events:none}
  .fr-hero h2{margin:0;font-size:20px;font-weight:900;letter-spacing:-.3px;color:#fff}
  .fr-hero p{margin:4px 0 0;font-size:12.5px;color:rgba(255,255,255,.82);max-width:720px}
  .fr-filtre{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-top:16px}
  .fr-filtre label{display:block;font-size:10.5px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:rgba(255,255,255,.75);margin-bottom:4px}
  .fr-filtre select{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.28);color:#fff;border-radius:10px;padding:9px 12px;font-size:13px;font-weight:600;min-width:150px}
  .fr-filtre select option{color:#1E293B}
  .fr-hbtn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;padding:9px 14px;font-size:13px;font-weight:800;cursor:pointer;border:0;text-decoration:none}
  .fr-hbtn.ak{background:#fff;color:#5B21B6}.fr-hbtn.cam{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.3)}
  .fr-hbtn .material-icons-outlined{font-size:18px}
  .fr-kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:18px}
  .fr-k{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;position:relative;overflow:hidden;box-shadow:var(--golge)}
  .fr-k::before{content:'';position:absolute;inset:0 auto 0 0;width:4px;background:var(--c)}
  .fr-k .ik{width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c);margin-bottom:8px}
  .fr-k .ik .material-icons-outlined{font-size:19px}
  .fr-k .l{font-size:11.5px;color:var(--sub);font-weight:600}
  .fr-k .d{font-size:21px;font-weight:900;color:var(--ink);letter-spacing:-.4px;margin-top:2px}
  .fr-k .a{font-size:11px;color:var(--sub);margin-top:2px}
  .fr-tabs{display:flex;gap:6px;overflow-x:auto;padding:5px;background:var(--card);border:1px solid var(--line);border-radius:14px;margin-bottom:16px;position:sticky;top:0;z-index:5;scrollbar-width:thin}
  .fr-tab{flex:0 0 auto;display:inline-flex;align-items:center;gap:6px;padding:9px 13px;border-radius:10px;font-size:13px;font-weight:700;color:var(--sub2);cursor:pointer;border:0;background:transparent;white-space:nowrap}
  .fr-tab .material-icons-outlined{font-size:17px}
  .fr-tab:hover{background:var(--card2)}
  .fr-tab.aktif{background:linear-gradient(135deg,var(--fr-mor),var(--fr-mavi));color:#fff;box-shadow:0 6px 14px -6px rgba(79,70,229,.6)}
  .fr-tab .say{font-size:10.5px;padding:1px 7px;border-radius:99px;background:color-mix(in srgb,currentColor 16%,transparent)}
  .fr-pan{display:none}.fr-pan.aktif{display:block;animation:frIn .2s ease}
  @keyframes frIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
  .fr-bas{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin:2px 0 10px}
  .fr-bas h3{margin:0;font-size:16px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px}
  .fr-bas h3 .material-icons-outlined{color:var(--c,var(--fr-mor))}
  .fr-bas .sag{margin-left:auto;display:flex;gap:6px;flex-wrap:wrap;align-items:center}
  .fr-nasil{background:color-mix(in srgb,var(--fr-mavi) 7%,var(--card));border:1px dashed color-mix(in srgb,var(--fr-mavi) 35%,transparent);border-radius:12px;padding:10px 13px;font-size:12px;color:var(--sub2);margin-bottom:12px;display:flex;gap:9px}
  .fr-nasil .material-icons-outlined{font-size:18px;color:var(--fr-mavi)}
  .fr-xbtn{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border-radius:9px;font-size:12px;font-weight:800;text-decoration:none;border:1px solid}
  .fr-xbtn .material-icons-outlined{font-size:16px}
  .fr-xbtn.x{color:#15803D;border-color:color-mix(in srgb,#16A34A 40%,transparent);background:color-mix(in srgb,#16A34A 8%,transparent)}
  .fr-xbtn.p{color:#BE123C;border-color:color-mix(in srgb,#E11D48 40%,transparent);background:color-mix(in srgb,#E11D48 7%,transparent)}
  body.koyu .fr-xbtn.x{color:#4ADE80} body.koyu .fr-xbtn.p{color:#FB7185}
  .fr-ara{background:var(--card);border:1px solid var(--line);border-radius:9px;padding:7px 11px;font-size:12.5px;color:var(--ink);min-width:190px}
  .fr-t{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:auto;box-shadow:var(--golge);max-height:70vh}
  .fr-t table{width:100%;border-collapse:collapse;font-size:12.5px}
  .fr-t th{position:sticky;top:0;background:var(--card2);color:var(--sub2);font-size:10.5px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;text-align:left;padding:10px 12px;white-space:nowrap;z-index:1}
  .fr-t td{padding:10px 12px;border-top:1px solid var(--line);color:var(--ink);vertical-align:top}
  .fr-t .s{text-align:right;white-space:nowrap}.fr-t .o{text-align:center}
  .fr-t tr.ac{cursor:pointer}.fr-t tr.ac:hover td{background:color-mix(in srgb,var(--fr-mor) 4%,transparent)}
  .fr-t tr.det > td{background:var(--card2);padding:12px 16px}
  .fr-t tr.det th{position:static}
  .fr-k2{font-size:10.5px;color:var(--sub)}
  .fr-ted{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;font-weight:700;color:var(--sub2)}
  .fr-ted .material-icons-outlined{font-size:14px;color:var(--sub)}
  .fr-r{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:800;color:var(--c);background:color-mix(in srgb,var(--c) 13%,transparent);border:1px solid color-mix(in srgb,var(--c) 35%,transparent);white-space:nowrap}
  .fr-bos{background:var(--card);border:1px dashed var(--line);border-radius:14px;padding:30px;text-align:center;color:var(--sub);font-size:13px}
  .fr-bos .material-icons-outlined{font-size:40px;display:block;margin-bottom:6px;color:var(--c,var(--yesil))}
  .fr-grid2{display:grid;grid-template-columns:1.4fr 1fr;gap:14px}
  @media(max-width:1000px){.fr-grid2{grid-template-columns:1fr}}
  .fr-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;box-shadow:var(--golge)}
  .fr-kart h4{margin:0 0 12px;font-size:13.5px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:7px}
  .fr-kart h4 .material-icons-outlined{font-size:18px;color:var(--c,var(--fr-mor))}
  .fr-bar{display:flex;align-items:flex-end;gap:8px;height:170px;padding-top:10px}
  .fr-bar .b{flex:1;display:flex;flex-direction:column;align-items:center;gap:5px;min-width:0}
  .fr-bar .c{width:100%;max-width:46px;border-radius:8px 8px 3px 3px;background:linear-gradient(180deg,var(--fr-pembe),var(--fr-mor));min-height:3px}
  .fr-bar .v{font-size:10px;font-weight:800;color:var(--sub2);white-space:nowrap}
  .fr-bar .e{font-size:10.5px;color:var(--sub);font-weight:700;white-space:nowrap}
  .fr-hb{display:flex;align-items:center;gap:10px;margin-bottom:9px;font-size:12.5px}
  .fr-hb .n{width:38%;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:700;color:var(--ink)}
  .fr-hb .tr{flex:1;height:9px;border-radius:99px;background:var(--card2);overflow:hidden}
  .fr-hb .tr i{display:block;height:100%;border-radius:99px;background:var(--c,linear-gradient(90deg,var(--fr-mavi),var(--fr-cyan)))}
  .fr-hb .t{width:92px;text-align:right;font-weight:800;color:var(--sub2);font-size:12px}
  .fr-lst{display:flex;flex-direction:column;gap:8px}
  .fr-lst .i{display:flex;gap:10px;align-items:center;padding:10px 12px;border-radius:12px;background:var(--card2);color:var(--ink);font-size:12.5px;cursor:pointer}
  .fr-lst .material-icons-outlined{font-size:18px;color:var(--c)}
  .fr-rap{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:12px}
  .fr-rk{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;display:flex;flex-direction:column;gap:10px;box-shadow:var(--golge);transition:transform .15s,box-shadow .15s}
  .fr-rk:hover{transform:translateY(-2px);box-shadow:0 14px 30px -16px rgba(49,46,129,.35)}
  .fr-rk .ik{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;color:#fff;background:var(--c)}
  .fr-rk .t{font-weight:900;font-size:14px;color:var(--ink)}.fr-rk .d{font-size:12px;color:var(--sub);flex:1}
  .fr-rk .bt{display:flex;gap:6px}.fr-rk .bt a{flex:1;justify-content:center}
  .fr-ai-hero{border-radius:18px;padding:20px;background:linear-gradient(135deg,color-mix(in srgb,#9333EA 14%,var(--card)),color-mix(in srgb,#EC4899 10%,var(--card)));border:1px solid color-mix(in srgb,#9333EA 30%,transparent);margin-bottom:14px}
  .fr-ai-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 18px;border-radius:12px;border:0;cursor:pointer;font-weight:900;font-size:13.5px;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899);box-shadow:0 10px 22px -10px rgba(147,51,234,.7)}
  .fr-ai-btn[disabled]{opacity:.7;cursor:wait}
  .fr-ai-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:12px;margin-top:12px}
  .fr-ai-k{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px;border-top:3px solid var(--c)}
  .fr-ai-k h5{margin:0 0 10px;font-size:13px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:6px}
  .fr-ai-k h5 .material-icons-outlined{font-size:18px;color:var(--c)}
  .fr-ai-m{padding:9px 0;border-top:1px solid var(--line);font-size:12.5px;color:var(--sub2)}
  .fr-ai-k h5 + .fr-ai-m{border-top:0}
  .fr-ai-m b{color:var(--ink);display:block;margin-bottom:2px}
  .fr-skor{width:84px;height:84px;border-radius:50%;display:grid;place-items:center;font-size:24px;font-weight:900;color:var(--ink);flex:0 0 auto}
  .fr-sor{display:flex;gap:8px;margin-top:12px}
  .fr-sor input{flex:1;min-width:0;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:10px 13px;font-size:13px;color:var(--ink)}
  .fr-cevap{white-space:pre-wrap;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:13px;font-size:13px;color:var(--ink);margin-top:10px;line-height:1.55}
  @keyframes frDon{to{transform:rotate(360deg)}}
</style>

<div class="fr">
{{-- ===== HERO + FİLTRE ===== --}}
<div class="fr-hero">
  <div style="position:relative;z-index:1;display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap">
    <div style="flex:1;min-width:260px">
      <h2>📡 Fiyat Radarı — Satın Alma Rapor Merkezi</h2>
      <p>Her malzemeyi <b>kimden, ne zaman, kaça</b> aldığını ve <b>neye göre</b> pahalı/ucuz sayıldığını gösterir. Satıra tıkla → alış geçmişi açılır. İstediğin raporu Excel ya da PDF al, AI'a analiz ettir.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a href="{{ $indir('tam', 'xlsx') }}" class="fr-hbtn ak"><span class="material-icons-outlined">table_view</span> Tam Rapor (Excel)</a>
      <a href="{{ $indir('tam', 'pdf') }}" target="_blank" class="fr-hbtn cam"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a>
    </div>
  </div>
  <form method="GET" action="/fiyat-radari" class="fr-filtre" onsubmit="this.action='/fiyat-radari'+location.hash">
    <div><label>Dönem</label>
      <select name="gun" onchange="this.form.requestSubmit()">
        @foreach ([30 => 'Son 30 gün', 60 => 'Son 60 gün', 90 => 'Son 3 ay', 120 => 'Son 4 ay', 180 => 'Son 6 ay', 365 => 'Son 1 yıl'] as $g => $ad)
          <option value="{{ $g }}" @selected($filtre['gun'] == $g)>{{ $ad }}</option>
        @endforeach
      </select></div>
    <div><label>Tedarikçi</label>
      <select name="ted" onchange="this.form.requestSubmit()">
        <option value="0">Tüm tedarikçiler</option>
        @foreach ($tedarikciler as $t)<option value="{{ $t['id'] }}" @selected($filtre['ted'] == $t['id'])>{{ $t['ad'] }}</option>@endforeach
      </select></div>
    <div><label>Zam eşiği</label>
      <select name="esik" onchange="this.form.requestSubmit()">
        @foreach ([3, 5, 8, 10, 15, 20, 30] as $e)<option value="{{ $e }}" @selected($filtre['esik'] == $e)>%{{ $e }} ve üstü</option>@endforeach
      </select></div>
    <div style="font-size:11.5px;color:rgba(255,255,255,.8);padding-bottom:9px">
      {{ $tar($kpi['baslangic'] ?? null) }} – {{ $tar($kpi['bitis'] ?? null) }} · {{ $kpi['fatura'] ?? 0 }} fatura · {{ $kpi['kalem'] ?? 0 }} alış satırı
    </div>
  </form>
</div>

{{-- ===== KPI ===== --}}
<div class="fr-kpi">
  <div class="fr-k" style="--c:#4F46E5"><div class="ik"><span class="material-icons-outlined">payments</span></div><div class="l">Toplam alış</div><div class="d">{{ $tl0($kpi['toplam'] ?? 0) }}</div><div class="a">{{ $kpi['tedarikci'] ?? 0 }} tedarikçi · {{ $kpi['malzeme'] ?? 0 }} malzeme</div></div>
  <div class="fr-k" style="--c:#F43F5E"><div class="ik"><span class="material-icons-outlined">trending_up</span></div><div class="l">Gizli zam yıllık etkisi</div><div class="d" style="color:#F43F5E">~{{ $tl0($kpi['zam_etki'] ?? 0) }}</div><div class="a">{{ $kpi['zam_say'] ?? 0 }} malzemede %{{ $filtre['esik'] }}+ artış</div></div>
  <div class="fr-k" style="--c:#0EA5E9"><div class="ik"><span class="material-icons-outlined">compare_arrows</span></div><div class="l">En ucuzdan alsaydın (yıllık)</div><div class="d" style="color:#0284C7">~{{ $tl0($kpi['ted_kayip'] ?? 0) }}</div><div class="a">{{ count($karsilastirma) }} malzemede tedarikçi farkı</div></div>
  <div class="fr-k" style="--c:#10B981"><div class="ik"><span class="material-icons-outlined">storefront</span></div><div class="l">Hal üstü fazla ödeme (yıllık)</div><div class="d" style="color:#059669">~{{ $tl0($kpi['hal_fazla'] ?? 0) }}</div><div class="a">{{ count($halSat) }} malzeme halde eşleşti</div></div>
  <div class="fr-k" style="--c:#F59E0B"><div class="ik"><span class="material-icons-outlined">savings</span></div><div class="l">Toplam tasarruf potansiyeli</div><div class="d" style="color:#D97706">~{{ $tl0(($kpi['zam_etki'] ?? 0) + ($kpi['ted_kayip'] ?? 0) + ($kpi['hal_fazla'] ?? 0)) }}</div><div class="a">zam + tedarikçi + hal (kesişebilir)</div></div>
</div>

{{-- ===== SEKMELER ===== --}}
<div class="fr-tabs">
  @foreach ([
    ['genel', 'dashboard', 'Genel Bakış', null],
    ['zam', 'trending_up', 'Gizli Zamlar', count($zamlar)],
    ['malzeme', 'inventory_2', 'Malzeme Fiyatları', count($malzemeler)],
    ['tedarikci', 'compare_arrows', 'Tedarikçi Karşılaştırma', count($karsilastirma)],
    ['karne', 'workspace_premium', 'Tedarikçi Karnesi', count($karne)],
    ['hal', 'storefront', 'Hal Kıyası', count($halSat)],
    ['dokum', 'receipt_long', 'Alış Dökümü', count($dokum)],
    ['ai', 'auto_awesome', 'AI Analiz', null],
    ['rapor', 'download', 'Raporlar', count($raporlar)],
  ] as [$k, $ik, $ad, $say])
    <button type="button" class="fr-tab" data-tab="{{ $k }}" onclick="frSekme('{{ $k }}')"><span class="material-icons-outlined">{{ $ik }}</span>{{ $ad }}@if ($say !== null)<span class="say">{{ $say }}</span>@endif</button>
  @endforeach
</div>

{{-- ===== GENEL BAKIŞ ===== --}}
<div class="fr-pan" id="pan-genel">
  <div class="fr-grid2" style="margin-bottom:14px">
    <div class="fr-kart" style="--c:#EC4899">
      <h4><span class="material-icons-outlined">bar_chart</span> Aylık alış</h4>
      @if (!$aylik)<div class="fr-k2">Bu dönemde alış yok.</div>@else
      <div class="fr-bar">
        @foreach ($aylik as $a)
          <div class="b"><div class="v">{{ number_format($a['tutar'] / 1000, 1, ',', '.') }}k</div><div class="c" style="height:{{ max(2, round($a['tutar'] / $maxAy * 130)) }}px" title="{{ $tl($a['tutar']) }}"></div>
            <div class="e">{{ \Carbon\Carbon::parse($a['ay'] . '-01')->locale('tr')->translatedFormat('M y') }}</div></div>
        @endforeach
      </div>@endif
    </div>
    <div class="fr-kart" style="--c:#4F46E5">
      <h4><span class="material-icons-outlined">donut_small</span> Kategori dağılımı</h4>
      @forelse (array_slice($kategori, 0, 7) as $k)
        <div class="fr-hb"><div class="n">{{ $k['ad'] }}</div><div class="tr"><i style="width:{{ max(2, $k['pay']) }}%"></i></div><div class="t">%{{ number_format($k['pay'], 1, ',', '.') }}</div></div>
      @empty <div class="fr-k2">Veri yok.</div> @endforelse
    </div>
  </div>
  <div class="fr-grid2">
    <div class="fr-kart" style="--c:#7C3AED">
      <h4><span class="material-icons-outlined">leaderboard</span> En çok para giden 8 malzeme</h4>
      @php $ilk8 = array_slice($malzemeler, 0, 8); $maxH = max(1, ...array_map(fn($m) => $m['harcama'], $ilk8 ?: [['harcama' => 1]])); @endphp
      @forelse ($ilk8 as $m)
        <div class="fr-hb"><div class="n" title="{{ $m['ad'] }} · {{ $m['son_ted'] }}">{{ $m['ad'] }} <span class="fr-k2">· {{ $m['son_ted'] }}</span></div><div class="tr"><i style="width:{{ max(2, $m['harcama'] / $maxH * 100) }}%;--c:linear-gradient(90deg,#7C3AED,#EC4899)"></i></div><div class="t">{{ $tl0($m['harcama']) }}</div></div>
      @empty <div class="fr-k2">Veri yok.</div> @endforelse
    </div>
    <div class="fr-kart" style="--c:#F43F5E">
      <h4><span class="material-icons-outlined">priority_high</span> Hemen bakılacaklar</h4>
      <div class="fr-lst">
        @foreach (array_slice($zamlar, 0, 3) as $z)
          <div class="i" style="--c:#F43F5E" onclick="frSekme('zam')"><span class="material-icons-outlined">trending_up</span><div><b>{{ $z['ad'] }}</b> %{{ number_format($z['yuzde'], 1, ',', '.') }} zamlandı <span class="fr-k2">({{ $z['ilk_ted'] }} {{ $tl($z['ilk']) }} → {{ $z['son_ted'] }} {{ $tl($z['son']) }})</span></div></div>
        @endforeach
        @foreach (array_slice($karsilastirma, 0, 3) as $k)
          <div class="i" style="--c:#0EA5E9" onclick="frSekme('tedarikci')"><span class="material-icons-outlined">compare_arrows</span><div><b>{{ $k['ad'] }}</b>: {{ $k['en_ucuz_ted'] }} {{ $tl($k['en_ucuz']) }}, {{ $k['en_pahali_ted'] }} {{ $tl($k['en_pahali']) }} <span class="fr-k2">(%{{ number_format($k['fark'], 0) }} fark)</span></div></div>
        @endforeach
        @foreach (array_slice(array_values(array_filter($halSat, fn($h) => $h['fark'] !== null && $h['fark'] > 30)), 0, 3) as $h)
          <div class="i" style="--c:#10B981" onclick="frSekme('hal')"><span class="material-icons-outlined">storefront</span><div><b>{{ $h['ad'] }}</b> halin %{{ $h['fark'] }} üstünde <span class="fr-k2">({{ $h['tedarikci'] ?? '' }})</span></div></div>
        @endforeach
        @if (!$zamlar && !$karsilastirma)<div class="fr-k2">Şu an acil bir şey görünmüyor 👍</div>@endif
      </div>
    </div>
  </div>
</div>

{{-- ===== GİZLİ ZAMLAR ===== --}}
<div class="fr-pan" id="pan-zam">
  <div class="fr-bas" style="--c:#F43F5E"><h3><span class="material-icons-outlined">trending_up</span> Gizli Zam Dedektörü</h3>
    <div class="sag"><input class="fr-ara" placeholder="Malzeme / tedarikçi ara…" oninput="frSuz(this,'tZam')">
      <a class="fr-xbtn x" href="{{ $indir('zam', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('zam', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div></div>
  <div class="fr-nasil"><span class="material-icons-outlined">info</span><div><b>Neye göre?</b> Seçili dönemdeki <b>ilk alış</b> ile <b>son alış</b> karşılaştırılır (hangi tedarikçiden, hangi faturadan olduğu yazılı). Tedarikçi değiştiyse ayrıca <b>aynı tedarikçinin kendi zammı</b> gösterilir; böylece "zam mı yaptı, yoksa pahalı tedarikçiye mi geçtik" ayrılır. Yıllık etki = (son − ilk) × dönemdeki alım miktarı, yıla oranlanmış.</div></div>
  @if (!$zamlar)
    <div class="fr-bos" style="--c:var(--amber);text-align:left"><span class="material-icons-outlined">info</span>{{ $bos['zam'] ?? '' }}</div>
  @else
  <div class="fr-t"><table id="tZam">
    <thead><tr><th>Malzeme</th><th>İlk alış (kimden)</th><th class="s">İlk fiyat</th><th>Son alış (kimden)</th><th class="s">Son fiyat</th><th class="o">Artış</th><th class="o">Aynı tedarikçide</th><th>Dönemin en ucuzu</th><th class="s">Yıllık etki</th></tr></thead>
    <tbody>
    @foreach ($zamlar as $z)
      @php $c = $z['yuzde'] >= 15 ? '#F43F5E' : '#F59E0B'; @endphp
      <tr class="ac" data-mid="{{ $z['id'] }}" onclick="frDetay(this)">
        <td><b>{{ $z['ad'] }}</b> <span class="fr-k2">/ {{ $z['birim'] }}</span><div class="fr-k2">{{ $z['adet'] }} alış · tıkla: geçmiş</div></td>
        <td><span class="fr-ted"><span class="material-icons-outlined">local_shipping</span>{{ $z['ilk_ted'] }}</span><div class="fr-k2">{{ $tar($z['ilk_tarih']) }} · fatura {{ $z['ilk_fatura'] }}</div></td>
        <td class="s">{{ $tl($z['ilk']) }}</td>
        <td><span class="fr-ted"><span class="material-icons-outlined">local_shipping</span>{{ $z['son_ted'] }}</span><div class="fr-k2">{{ $tar($z['son_tarih']) }} · fatura {{ $z['son_fatura'] }}</div></td>
        <td class="s"><b>{{ $tl($z['son']) }}</b></td>
        <td class="o"><span class="fr-r" style="--c:{{ $c }}">▲ %{{ number_format($z['yuzde'], 1, ',', '.') }}</span>@if ($z['ted_degisti'])<div class="fr-k2" style="margin-top:3px">tedarikçi değişti</div>@endif</td>
        <td class="o">@if ($z['ayni_ted_yuzde'] === null)<span class="fr-k2">tek alış</span>@else<span class="fr-r" style="--c:{{ $z['ayni_ted_yuzde'] >= $filtre['esik'] ? '#F43F5E' : '#10B981' }}">%{{ number_format($z['ayni_ted_yuzde'], 1, ',', '.') }}</span>@endif</td>
        <td>{{ $tl($z['en_dusuk']) }}<div class="fr-k2">{{ $z['en_dusuk_ted'] }}</div></td>
        <td class="s" style="color:#F43F5E;font-weight:900">~{{ $tl0($z['yillik_etki']) }}</td>
      </tr>
    @endforeach
    </tbody></table></div>
  @endif
</div>

{{-- ===== MALZEME FİYATLARI ===== --}}
<div class="fr-pan" id="pan-malzeme">
  <div class="fr-bas" style="--c:#4F46E5"><h3><span class="material-icons-outlined">inventory_2</span> Malzeme Fiyat Analizi</h3>
    <div class="sag"><input class="fr-ara" placeholder="Malzeme / kategori / tedarikçi ara…" oninput="frSuz(this,'tMal')">
      <a class="fr-xbtn x" href="{{ $indir('malzeme', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('malzeme', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div></div>
  <div class="fr-nasil"><span class="material-icons-outlined">info</span><div><b>Neye göre?</b> Ortalama fiyat <b>miktar ağırlıklıdır</b> (toplam tutar ÷ toplam miktar). En düşük / en yüksek / son fiyatın yanında <b>hangi tedarikçiden</b> alındığı yazılı. Oynaklık = (en yüksek − en düşük) ÷ en düşük. Tasarruf payı = hep en düşük fiyattan alsaydın kazancın.</div></div>
  @if (!$malzemeler)<div class="fr-bos" style="--c:var(--sub);text-align:left"><span class="material-icons-outlined">inbox</span>{{ $bos['genel'] ?? '' }}</div>@else
  <div class="fr-t"><table id="tMal">
    <thead><tr><th>Malzeme</th><th class="s">Harcama</th><th class="s">Ort. fiyat</th><th>En düşük (kimden)</th><th>En yüksek (kimden)</th><th>Son (kimden)</th><th class="o">Oynaklık</th><th class="s">Tasarruf payı</th></tr></thead>
    <tbody>
    @foreach ($malzemeler as $m)
      <tr class="ac" data-mid="{{ $m['id'] }}" onclick="frDetay(this)">
        <td><b>{{ $m['ad'] }}</b> <span class="fr-k2">/ {{ $m['birim'] }}</span><div class="fr-k2">{{ $m['kategori'] ?: 'Kategorisiz' }} · {{ $m['alis_say'] }} alış · {{ $m['ted_say'] }} tedarikçi · {{ $mik($m['miktar']) }} {{ $m['birim'] }}</div></td>
        <td class="s"><b>{{ $tl0($m['harcama']) }}</b></td>
        <td class="s">{{ $tl($m['ort']) }}</td>
        <td><span style="color:#059669;font-weight:800">{{ $tl($m['min']) }}</span><div class="fr-k2">{{ $m['min_ted'] }} · {{ $tar($m['min_tarih']) }}</div></td>
        <td><span style="color:#E11D48;font-weight:800">{{ $tl($m['max']) }}</span><div class="fr-k2">{{ $m['max_ted'] }} · {{ $tar($m['max_tarih']) }}</div></td>
        <td><b>{{ $tl($m['son']) }}</b><div class="fr-k2">{{ $m['son_ted'] }} · {{ $tar($m['son_tarih']) }}</div></td>
        <td class="o"><span class="fr-r" style="--c:{{ $m['oynaklik'] >= 25 ? '#F43F5E' : ($m['oynaklik'] >= 10 ? '#F59E0B' : '#10B981') }}">%{{ number_format($m['oynaklik'], 0) }}</span></td>
        <td class="s" style="font-weight:800;color:{{ $m['tasarruf'] > 0 ? '#D97706' : 'var(--sub)' }}">{{ $m['tasarruf'] > 0 ? $tl0($m['tasarruf']) : '—' }}</td>
      </tr>
    @endforeach
    </tbody></table></div>
  @endif
</div>

{{-- ===== TEDARİKÇİ KARŞILAŞTIRMA ===== --}}
<div class="fr-pan" id="pan-tedarikci">
  <div class="fr-bas" style="--c:#0EA5E9"><h3><span class="material-icons-outlined">compare_arrows</span> Tedarikçi Karşılaştırma</h3>
    <div class="sag"><input class="fr-ara" placeholder="Malzeme / tedarikçi ara…" oninput="frSuz(this,'tTed')">
      <a class="fr-xbtn x" href="{{ $indir('tedarikci', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('tedarikci', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div></div>
  <div class="fr-nasil"><span class="material-icons-outlined">info</span><div><b>Neye göre?</b> Aynı malzemeyi dönem içinde <b>gerçekten farklı tedarikçilerden aldığın</b> faturalar karşılaştırılır (miktar ağırlıklı ortalama). Kayıp = her tedarikçiden aldığın miktarı en ucuz tedarikçinin fiyatından alsaydın ödeyeceğin fark.</div></div>
  @if (!$karsilastirma)
    <div class="fr-bos" style="--c:#0EA5E9;text-align:left"><span class="material-icons-outlined">compare_arrows</span>{{ $bos['ted'] ?? '' }}</div>
  @else
  <div class="fr-t"><table id="tTed">
    <thead><tr><th>Malzeme</th><th>Tedarikçiler (ucuzdan pahalıya)</th><th class="o">Fark</th><th>Şu an kimden alıyorsun</th><th class="s">Dönem kaybı</th><th class="s">Yıllık</th></tr></thead>
    <tbody>
    @foreach ($karsilastirma as $k)
      <tr class="ac" data-mid="{{ $k['id'] }}" onclick="frDetay(this)">
        <td><b>{{ $k['ad'] }}</b> <span class="fr-k2">/ {{ $k['birim'] }}</span></td>
        <td>@foreach ($k['tedler'] as $i => $t)
            <div style="display:flex;gap:8px;align-items:center;margin-bottom:3px;flex-wrap:wrap"><span class="fr-r" style="--c:{{ $i === 0 ? '#10B981' : '#64748B' }};min-width:90px;justify-content:center">{{ $tl($t['ort']) }}</span>
              <span style="font-weight:{{ $i === 0 ? 800 : 600 }}">{{ $t['ted'] }}</span><span class="fr-k2">{{ $t['alis_say'] }} alış · son {{ $tl($t['son']) }} ({{ $tar($t['son_tarih']) }})</span></div>
          @endforeach</td>
        <td class="o"><span class="fr-r" style="--c:{{ $k['fark'] >= 15 ? '#F43F5E' : '#F59E0B' }}">%{{ number_format($k['fark'], 1, ',', '.') }}</span></td>
        <td>@if ($k['son_ted'] === $k['en_ucuz_ted'])<span class="fr-r" style="--c:#10B981">✓ {{ $k['son_ted'] }} (en ucuz)</span>@else<span class="fr-r" style="--c:#F43F5E">{{ $k['son_ted'] }}</span><div class="fr-k2" style="margin-top:3px">en ucuz: {{ $k['en_ucuz_ted'] }}</div>@endif</td>
        <td class="s" style="font-weight:800">{{ $k['kayip'] > 0 ? $tl0($k['kayip']) : '—' }}</td>
        <td class="s" style="font-weight:900;color:#0284C7">{{ $k['yillik_kayip'] > 0 ? '~' . $tl0($k['yillik_kayip']) : '—' }}</td>
      </tr>
    @endforeach
    </tbody></table></div>
  @endif
  @if ($katalog)
    <div class="fr-bas" style="margin-top:20px;--c:#10B981"><h3><span class="material-icons-outlined">menu_book</span> Katalog fırsatları <span class="fr-k2" style="font-weight:600">· tedarikçi kartlarındaki liste fiyatları</span></h3></div>
    <div class="fr-t"><table>
      <thead><tr><th>Malzeme</th><th>En ucuz tedarikçi</th><th class="s">En ucuz</th><th>En pahalı tedarikçi</th><th class="s">En pahalı</th><th class="o">Fark</th></tr></thead>
      <tbody>@foreach ($katalog as $k)<tr><td><b>{{ $k['ad'] }}</b> <span class="fr-k2">({{ $k['ted_say'] }} tedarikçi)</span></td><td style="color:#059669;font-weight:800">{{ $k['en_ucuz_ted'] }}</td><td class="s">{{ $tl($k['en_ucuz']) }}</td><td>{{ $k['en_pahali_ted'] }}</td><td class="s">{{ $tl($k['en_pahali']) }}</td><td class="o"><span class="fr-r" style="--c:#10B981">%{{ number_format($k['tasarruf'], 0) }} ucuz</span></td></tr>@endforeach</tbody>
    </table></div>
  @endif
</div>

{{-- ===== TEDARİKÇİ KARNESİ ===== --}}
<div class="fr-pan" id="pan-karne">
  <div class="fr-bas" style="--c:#F59E0B"><h3><span class="material-icons-outlined">workspace_premium</span> Tedarikçi Karnesi</h3>
    <div class="sag"><a class="fr-xbtn x" href="{{ $indir('karne', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('karne', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div></div>
  <div class="fr-nasil"><span class="material-icons-outlined">info</span><div><b>Zamlı kalem</b> = o tedarikçinin kendi fiyatını %{{ $filtre['esik'] }}+ artırdığı malzeme. <b>Pahalı kaldığı</b> = aynı malzemeyi başka tedarikçiden %5+ ucuza aldığın durum.</div></div>
  @if (!$karne)<div class="fr-bos" style="--c:var(--sub);text-align:left"><span class="material-icons-outlined">inbox</span>{{ $bos['genel'] ?? '' }}</div>@else
  <div class="fr-t"><table>
    <thead><tr><th>Tedarikçi</th><th class="s">Harcama</th><th>Pay</th><th class="s">Fatura</th><th class="s">Malzeme</th><th class="o">Zamlı kalem</th><th class="o">Ort. zam</th><th class="o">Pahalı kaldığı</th><th>Son alış</th></tr></thead>
    <tbody>@foreach ($karne as $k)
      <tr>
        <td><b>{{ $k['ted'] }}</b></td>
        <td class="s"><b>{{ $tl0($k['harcama']) }}</b></td>
        <td style="min-width:140px"><div class="fr-hb" style="margin:0"><div class="tr"><i style="width:{{ max(2, $k['pay']) }}%;--c:linear-gradient(90deg,#F59E0B,#F43F5E)"></i></div><div class="t" style="width:52px">%{{ number_format($k['pay'], 1, ',', '.') }}</div></div></td>
        <td class="s">{{ $k['fatura'] }}</td><td class="s">{{ $k['malzeme'] }}</td>
        <td class="o">@if ($k['zamli'])<span class="fr-r" style="--c:#F43F5E">{{ $k['zamli'] }}</span>@else<span class="fr-r" style="--c:#10B981">0</span>@endif</td>
        <td class="o">{{ $k['ort_zam'] ? '%' . number_format($k['ort_zam'], 1, ',', '.') : '—' }}</td>
        <td class="o">@if ($k['pahali'])<span class="fr-r" style="--c:#F59E0B">{{ $k['pahali'] }}</span>@else—@endif</td>
        <td class="fr-k2">{{ $tar($k['son_alis']) }}</td>
      </tr>@endforeach</tbody>
  </table></div>@endif
</div>

{{-- ===== HAL KIYASI ===== --}}
<div class="fr-pan" id="pan-hal">
  <div class="fr-bas" style="--c:#10B981"><h3><span class="material-icons-outlined">storefront</span> Hal Kıyası</h3>
    <div class="sag"><a class="fr-xbtn x" href="{{ $indir('hal', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('hal', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a>
      <a class="fr-xbtn" style="color:var(--sub2);border-color:var(--line)" href="/hal-fiyatlari"><span class="material-icons-outlined">open_in_new</span> Hal fiyatları</a></div></div>
  <div class="fr-nasil"><span class="material-icons-outlined">info</span><div><b>Neye göre?</b> Son alış fiyatın, <b>İzmir Büyükşehir hal bülteni</b>@if (!empty($hal['bulten'])) ({{ $tar($hal['bulten']) }})@endif ile karşılaştırılır. Hal toptan fiyattır; tedarikçinin <b>%10–20 üstü normaldir</b>. Fark yalnız birimler aynıysa (kg↔kg) hesaplanır; çuval/kasa alımlarında "birim farklı" yazar.</div></div>
  @if (!$halSat)
    <div class="fr-bos" style="--c:#10B981;text-align:left"><span class="material-icons-outlined">storefront</span>{{ $bos['hal'] ?? '' }}</div>
  @else
  <div class="fr-t"><table>
    <thead><tr><th>Malzeme</th><th>Son tedarikçi</th><th>Haldeki karşılığı</th><th class="s">Hal asgari</th><th class="s">Hal ortalama</th><th class="s">Hal azami</th><th class="s">Senin son alış</th><th class="o">Fark</th><th class="s">Yıllık fazla</th></tr></thead>
    <tbody>@foreach ($halSat as $h)
      @php $fk = $h['fark']; $c = $fk === null ? '#64748B' : ($fk > 30 ? '#F43F5E' : ($fk > 15 ? '#F59E0B' : '#10B981')); @endphp
      <tr>
        <td><b>{{ $h['ad'] }}</b> <span class="fr-k2">/ {{ $h['birim'] }}</span><div class="fr-k2">son alış {{ $tar($h['son_tarih']) }}</div></td>
        <td><span class="fr-ted"><span class="material-icons-outlined">local_shipping</span>{{ $h['tedarikci'] ?? '—' }}</span></td>
        <td class="fr-k2" style="font-size:12px">{{ $h['hal_ad'] }}</td>
        <td class="s">{{ $h['hal_min'] > 0 ? $tl($h['hal_min']) : '—' }}</td>
        <td class="s"><b>{{ $tl($h['hal_ort']) }}</b><span class="fr-k2">/{{ $h['hal_birim'] }}</span></td>
        <td class="s">{{ $h['hal_max'] > 0 ? $tl($h['hal_max']) : '—' }}</td>
        <td class="s"><b>{{ $tl($h['uyumlu'] ? $h['fiyat_hal_birim'] : $h['son_fiyat']) }}</b><span class="fr-k2">/{{ $h['uyumlu'] ? $h['hal_birim'] : $h['birim'] }}</span></td>
        <td class="o">@if ($fk === null)<span class="fr-k2">birim farklı</span>@else<span class="fr-r" style="--c:{{ $c }}">{{ $fk > 0 ? '▲ +' . $fk : ($fk < 0 ? '▼ ' . $fk : '0') }}%</span>@endif</td>
        <td class="s" style="font-weight:800;color:{{ $h['yillik'] > 0 && $fk > 15 ? '#F43F5E' : 'var(--sub)' }}">{{ $h['yillik'] > 0 && $fk > 15 ? '~' . $tl0($h['yillik']) : '—' }}</td>
      </tr>@endforeach</tbody>
  </table></div>@endif
</div>

{{-- ===== ALIŞ DÖKÜMÜ ===== --}}
<div class="fr-pan" id="pan-dokum">
  <div class="fr-bas" style="--c:#64748B"><h3><span class="material-icons-outlined">receipt_long</span> Detaylı Alış Dökümü</h3>
    <div class="sag"><input class="fr-ara" placeholder="Malzeme / tedarikçi / fatura ara…" oninput="frSuz(this,'tDok')">
      <a class="fr-xbtn x" href="{{ $indir('dokum', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
      <a class="fr-xbtn p" href="{{ $indir('dokum', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div></div>
  @if (count($dokum) > count($dokumGoster))<div class="fr-nasil"><span class="material-icons-outlined">info</span><div>Ekranda son {{ count($dokumGoster) }} satır gösteriliyor; <b>tamamı ({{ count($dokum) }} satır) Excel'de</b>.</div></div>@endif
  @if (!$dokum)<div class="fr-bos" style="--c:var(--sub);text-align:left"><span class="material-icons-outlined">inbox</span>{{ $bos['genel'] ?? '' }}</div>@else
  <div class="fr-t"><table id="tDok">
    <thead><tr><th>Tarih</th><th>Malzeme</th><th>Tedarikçi</th><th>Fatura</th><th class="s">Miktar</th><th class="s">Birim fiyat</th><th class="s">Tutar</th><th class="o">Önceki alışa göre</th></tr></thead>
    <tbody>@foreach ($dokumGoster as $d)
      <tr><td class="fr-k2" style="white-space:nowrap">{{ $tar($d['tarih']) }}</td><td><b>{{ $d['malzeme'] }}</b></td><td>{{ $d['tedarikci'] }}</td><td class="fr-k2">{{ $d['fatura_no'] }}</td>
        <td class="s">{{ $mik($d['miktar']) }} {{ $d['birim'] }}</td><td class="s">{{ $tl($d['birim_fiyat']) }}</td><td class="s"><b>{{ $tl($d['tutar']) }}</b></td>
        <td class="o">@if ($d['degisim'] === null)<span class="fr-k2">ilk alış</span>@elseif ($d['degisim'] == 0)<span class="fr-k2">aynı</span>@else<span class="fr-r" style="--c:{{ $d['degisim'] > 0 ? '#F43F5E' : '#10B981' }}">{{ $d['degisim'] > 0 ? '▲ +' : '▼ ' }}{{ number_format($d['degisim'], 1, ',', '.') }}%</span>@endif</td></tr>
    @endforeach</tbody>
  </table></div>@endif
</div>

{{-- ===== AI ANALİZ ===== --}}
<div class="fr-pan" id="pan-ai">
  <div class="fr-ai-hero">
    <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap">
      <div style="flex:1;min-width:260px">
        <div style="font-size:17px;font-weight:900;color:var(--ink)">✨ AI Satın Alma Analisti</div>
        <div style="font-size:12.5px;color:var(--sub2);margin-top:3px">Seçili dönemin verisini (zamlar, tedarikçi farkları, hal, karne, aylık harcama) inceler: <b>geçmiş hataları</b>, <b>tasarruf fırsatlarını</b>, <b>gelecek önerilerini</b>, tedarikçiyle <b>pazarlık argümanlarını</b> ve <b>yapılacaklar listesini</b> çıkarır.</div>
      </div>
      <button type="button" class="fr-ai-btn" id="frAiBtn" onclick="frAi(false)"><span class="material-icons-outlined">auto_awesome</span> Analiz Et</button>
      <button type="button" class="fr-xbtn" style="color:var(--sub2);border-color:var(--line);background:var(--card);cursor:pointer" onclick="frAi(true)" title="Veriyi baştan analiz et"><span class="material-icons-outlined">refresh</span> Yeniden</button>
    </div>
    <div class="fr-sor"><input id="frSoru" placeholder="Soru sor: ör. 'Soğanı kimden almalıyım?', 'Hangi tedarikçiyle pazarlık yapmalıyım?'" onkeydown="if(event.key==='Enter')frSor()">
      <button type="button" class="fr-ai-btn" style="padding:10px 14px" onclick="frSor()"><span class="material-icons-outlined">send</span></button></div>
    <div id="frCevap"></div>
  </div>
  <div id="frAiSonuc"><div class="fr-bos" style="--c:#9333EA"><span class="material-icons-outlined">auto_awesome</span>Henüz analiz yok. <b>Analiz Et</b>'e bas — yaklaşık 10 saniye sürer.</div></div>
  <div style="display:flex;gap:8px;margin-top:12px" id="frAiIndir" @if (!$ai) hidden @endif>
    <a class="fr-xbtn x" href="{{ $indir('ai', 'xlsx') }}"><span class="material-icons-outlined">table_view</span> AI raporu Excel</a>
    <a class="fr-xbtn p" href="{{ $indir('ai', 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> AI raporu PDF</a>
  </div>
</div>

{{-- ===== RAPORLAR ===== --}}
<div class="fr-pan" id="pan-rapor">
  <div class="fr-bas" style="--c:#7C3AED"><h3><span class="material-icons-outlined">download</span> Rapor Merkezi</h3><span class="fr-k2">Tüm raporlar üstteki filtreye göre (son {{ $filtre['gun'] }} gün{{ $filtre['ted'] ? ', seçili tedarikçi' : '' }}, zam eşiği %{{ $filtre['esik'] }}) hazırlanır.</span></div>
  <div class="fr-rap">
    @foreach ($raporlar as $k => [$ad, $acik, $ik, $renk])
      <div class="fr-rk" style="--c:{{ $renk }}">
        <div class="ik"><span class="material-icons-outlined">{{ $ik }}</span></div>
        <div class="t">{{ $ad }}</div><div class="d">{{ $acik }}</div>
        <div class="bt"><a class="fr-xbtn x" href="{{ $indir($k, 'xlsx') }}"><span class="material-icons-outlined">table_view</span> Excel</a>
          <a class="fr-xbtn p" href="{{ $indir($k, 'pdf') }}" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span> PDF</a></div>
      </div>
    @endforeach
  </div>
  <div class="fr-k2" style="margin-top:10px">PDF butonu raporu yazdırma görünümünde açar → <b>"PDF olarak kaydet"</b> seç. Excel dosyası filtreli, başlıkları sabit, ₺ ve % biçimli gelir.</div>
</div>
</div>

<script>
  var FR_GECMIS = {!! json_encode($gecmisJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var FR_AI = {!! json_encode($ai, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var FR_QS = {!! json_encode($qs) !!};
  function frSekme(k) {
    var var_ = false;
    document.querySelectorAll('.fr-tab').forEach(function (b) { var on = b.dataset.tab === k; b.classList.toggle('aktif', on); if (on) var_ = true; });
    if (!var_) return frSekme('genel');
    document.querySelectorAll('.fr-pan').forEach(function (p) { p.classList.toggle('aktif', p.id === 'pan-' + k); });
    if (history.replaceState) history.replaceState(null, '', location.pathname + location.search + '#' + k);
  }
  frSekme((location.hash || '#genel').slice(1).replace(/[^a-z]/g, '') || 'genel');

  function frNorm(s) { return (s || '').replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase(); }
  function frSuz(inp, id) {
    var q = frNorm(inp.value.trim());
    document.querySelectorAll('#' + id + ' > tbody > tr').forEach(function (tr) {
      if (tr.classList.contains('det')) { tr.remove(); return; }
      tr.style.display = !q || frNorm(tr.textContent).indexOf(q) > -1 ? '' : 'none';
    });
  }
  function frTl(v) { return Number(v).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺'; }
  function frEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function frTarih(t) { return t ? t.slice(8, 10) + '.' + t.slice(5, 7) + '.' + t.slice(0, 4) : ''; }
  // Satıra tıkla → o malzemenin alış geçmişi (kimden, hangi fatura, kaça, önceki alışa göre)
  function frDetay(tr) {
    if (tr.nextElementSibling && tr.nextElementSibling.classList.contains('det')) { tr.nextElementSibling.remove(); return; }
    var g = FR_GECMIS[tr.dataset.mid] || [];
    var h = '<div style="font-weight:800;font-size:12px;margin-bottom:8px;color:var(--ink)">Alış geçmişi (en yeni ' + g.length + ' alış)</div>';
    if (!g.length) h += '<div class="fr-k2">Kayıt yok.</div>';
    else {
      h += '<table style="width:100%;font-size:12px"><tr><th>Tarih</th><th>Tedarikçi</th><th>Fatura</th><th class="s">Miktar</th><th class="s">Birim fiyat</th><th class="s">Tutar</th><th class="o">Önceki alışa göre</th></tr>';
      g.forEach(function (r) {
        var d = r.degisim === null ? '<span class="fr-k2">ilk</span>' : (r.degisim == 0 ? '<span class="fr-k2">aynı</span>' :
          '<span class="fr-r" style="--c:' + (r.degisim > 0 ? '#F43F5E' : '#10B981') + '">' + (r.degisim > 0 ? '▲ +' : '▼ ') + String(r.degisim).replace('.', ',') + '%</span>');
        h += '<tr><td>' + frTarih(r.tarih) + '</td><td><b>' + frEsc(r.tedarikci) + '</b></td><td class="fr-k2">' + frEsc(r.fatura_no) + '</td><td class="s">' + Number(r.miktar).toLocaleString('tr-TR') + ' ' + frEsc(r.birim) +
          '</td><td class="s"><b>' + frTl(r.birim_fiyat) + '</b></td><td class="s">' + frTl(r.tutar) + '</td><td class="o">' + d + '</td></tr>';
      });
      h += '</table>';
    }
    var yeni = document.createElement('tr'); yeni.className = 'det';
    yeni.innerHTML = '<td colspan="' + tr.children.length + '">' + h + '</td>';
    tr.after(yeni);
  }

  // ---- AI ----
  function frAiCiz(a) {
    if (!a) return;
    var bolum = function (anahtar, baslik, ik, renk, fn) {
      var l = a[anahtar] || []; if (!l.length) return '';
      return '<div class="fr-ai-k" style="--c:' + renk + '"><h5><span class="material-icons-outlined">' + ik + '</span>' + baslik + '</h5>' + l.map(fn).join('') + '</div>';
    };
    var md = function (x) { return '<div class="fr-ai-m"><b>' + frEsc(x.baslik) + (x.tutar ? ' · ~' + Number(x.tutar).toLocaleString('tr-TR') + ' ₺' : '') + '</b>' + frEsc(x.detay) + '</div>'; };
    var skor = Math.max(0, Math.min(100, parseInt(a.skor) || 0)), sr = skor >= 70 ? '#10B981' : (skor >= 45 ? '#F59E0B' : '#F43F5E');
    var h = '<div class="fr-kart" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">' +
      '<div class="fr-skor" style="background:conic-gradient(' + sr + ' ' + (skor * 3.6) + 'deg,var(--card2) 0)"><div style="width:66px;height:66px;border-radius:50%;background:var(--card);display:grid;place-items:center">' + skor + '</div></div>' +
      '<div style="flex:1;min-width:240px"><div style="font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px">Satın alma disiplini puanı · ' + frEsc(a._zaman || '') + (a._donem ? ' · son ' + frEsc(a._donem) + ' gün' : '') + '</div>' +
      '<div style="font-size:13.5px;color:var(--ink);margin-top:4px;line-height:1.55">' + frEsc(a.ozet) + '</div></div></div>';
    h += '<div class="fr-ai-g">' +
      bolum('kritik', 'Kritik bulgular', 'report', '#F43F5E', md) +
      bolum('gecmis_hatalar', 'Geçmiş hatalar', 'history', '#F59E0B', md) +
      bolum('firsatlar', 'Tasarruf fırsatları', 'savings', '#10B981', md) +
      bolum('gelecek', 'Gelecek önerileri', 'insights', '#4F46E5', md) +
      bolum('pazarlik', 'Pazarlık argümanları', 'handshake', '#0EA5E9', function (x) { return '<div class="fr-ai-m"><b>' + frEsc(x.tedarikci) + '</b>' + frEsc(x.arguman) + '</div>'; }) +
      bolum('aksiyonlar', 'Yapılacaklar', 'checklist', '#9333EA', function (x) {
        var c = x.oncelik === 'yuksek' ? '#F43F5E' : (x.oncelik === 'orta' ? '#F59E0B' : '#64748B');
        return '<div class="fr-ai-m" style="display:flex;gap:8px;align-items:flex-start"><span class="fr-r" style="--c:' + c + '">' + frEsc((x.oncelik || '').toUpperCase()) + '</span><div style="flex:1;color:var(--ink)">' + frEsc(x.is) + '<div class="fr-k2">' + frEsc(x.sorumlu) + '</div></div></div>';
      }) + '</div>';
    document.getElementById('frAiSonuc').innerHTML = h;
    document.getElementById('frAiIndir').hidden = false;
  }
  function frYukleniyor(b, metin) { b.disabled = true; b.dataset.eski = b.innerHTML; b.innerHTML = '<span class="material-icons-outlined" style="animation:frDon 1s linear infinite">autorenew</span> ' + metin; }
  function frBitti(b) { b.disabled = false; if (b.dataset.eski) b.innerHTML = b.dataset.eski; }
  function frPost(govde) {
    return fetch('/fiyat-radari/ai?' + FR_QS, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(govde) })
      .then(function (r) { return r.json(); });
  }
  function frHata(m, ik) { document.getElementById('frAiSonuc').innerHTML = '<div class="fr-bos" style="--c:#F43F5E"><span class="material-icons-outlined">' + (ik || 'error_outline') + '</span>' + frEsc(m) + '</div>'; }
  function frAi(yenile) {
    var b = document.getElementById('frAiBtn'); if (b.disabled) return;
    frYukleniyor(b, 'Analiz ediliyor… (~10 sn)');
    frPost({ yenile: yenile ? 1 : 0 }).then(function (s) {
      frBitti(b);
      if (!s.ok) return frHata(s.hata || 'Analiz yapılamadı.');
      frAiCiz(s.analiz);
    }).catch(function () { frBitti(b); frHata('Bağlantı hatası, tekrar dene.', 'wifi_off'); });
  }
  function frSor() {
    var i = document.getElementById('frSoru'), q = i.value.trim(); if (!q) return;
    var c = document.getElementById('frCevap'); c.innerHTML = '<div class="fr-cevap"><span class="material-icons-outlined" style="animation:frDon 1s linear infinite;font-size:16px">autorenew</span> Düşünüyor…</div>';
    frPost({ soru: q }).then(function (s) {
      c.innerHTML = '<div class="fr-cevap"><b style="display:block;margin-bottom:6px;color:var(--sub)">❓ ' + frEsc(q) + '</b>' + frEsc(s.ok ? s.cevap : (s.hata || 'Cevap alınamadı.')) + '</div>';
    }).catch(function () { c.innerHTML = '<div class="fr-cevap">Bağlantı hatası.</div>'; });
  }
  if (FR_AI) frAiCiz(FR_AI);
</script>
@endsection

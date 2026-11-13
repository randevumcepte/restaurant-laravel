@extends('layout.app')
@section('rk_ikon', 'handshake')
@section('baslik', 'Fiyat Anlaşmaları')
@section('rk_alt', 'Tedarikçiyle sabitlenen fiyatlar · siparişte uygulanır, faturada denetlenir')
@section('title', 'Fiyat Anlaşmaları')

@push('head')
<style>
#fa{--mor:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gri:#94a3b8;
  --card:#fff;--card2:#f1f5f9;--bg:#f6f7fb;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb}
body.koyu #fa{--card:#161d2e;--card2:#1d2539;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#fa{background:var(--bg);min-height:calc(100vh - 60px)}#fa *{box-sizing:border-box}
#fa .pano{max-width:1180px;margin:0 auto;padding:18px 18px 48px}
.ms{font-family:'Material Icons Outlined';font-weight:normal;font-style:normal;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased;text-transform:none;letter-spacing:normal;white-space:nowrap}

/* Üst bant */
#fa .hero{position:relative;overflow:hidden;border-radius:20px;padding:22px 24px;background:linear-gradient(120deg,#EEF2FF 0%,#F5F3FF 55%,#ECFDF5 100%);border:1px solid #E0E7FF;display:flex;gap:18px;align-items:center;flex-wrap:wrap}
body.koyu #fa .hero{background:linear-gradient(120deg,#1e2142 0%,#231c3d 55%,#12302a 100%);border-color:#2c3650}
#fa .hero-ik{width:54px;height:54px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(135deg,var(--mavi),var(--mor));color:#fff;box-shadow:0 8px 20px rgba(79,70,229,.3);flex:none}
#fa .hero-ik .ms{font-size:28px}
#fa .hero h2{margin:0;font-size:19px;font-weight:900;color:var(--ink);letter-spacing:-.2px}
#fa .hero p{margin:4px 0 0;font-size:13px;color:var(--sub);line-height:1.5;max-width:640px}
#fa .hero .yazi{flex:1;min-width:240px}
#fa .ekle-btn{border:0;border-radius:13px;padding:12px 18px;font-weight:800;font-size:14px;cursor:pointer;color:#fff;background:linear-gradient(135deg,var(--mavi),var(--mor));display:inline-flex;align-items:center;gap:7px;box-shadow:0 6px 16px rgba(124,58,237,.3);transition:transform .15s}
#fa .ekle-btn:hover{transform:translateY(-1px)}
#fa .ekle-btn .ms{font-size:19px}

/* Nasıl çalışır */
#fa .adimlar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:14px}
#fa .adim{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 14px;display:flex;gap:11px;align-items:flex-start}
#fa .adim .no{width:30px;height:30px;border-radius:10px;display:grid;place-items:center;flex:none;font-weight:900;font-size:13px;color:#fff}
#fa .adim b{display:block;font-size:13px;color:var(--ink);margin-bottom:2px}
#fa .adim span.t{font-size:12px;color:var(--sub);line-height:1.45}

/* KPI */
#fa .kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}
#fa .k{min-width:0;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 15px;display:flex;gap:12px;align-items:center;cursor:pointer;transition:border-color .15s,box-shadow .15s}
#fa .k:hover{border-color:color-mix(in srgb,var(--kr) 45%,transparent);box-shadow:0 4px 14px rgba(0,0,0,.05)}
#fa .k .ki{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb,var(--kr) 14%,transparent);color:var(--kr);flex:none}
#fa .k .ki .ms{font-size:22px}
#fa .k .kd{font-size:22px;font-weight:900;color:var(--ink);line-height:1.1}
#fa .k .kl{font-size:11.5px;color:var(--sub);font-weight:600;line-height:1.3}
#fa .k .kx{font-size:11px;color:var(--kr);font-weight:800;margin-top:1px}

/* Araç çubuğu */
#fa .arac{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
#fa .ara{flex:1;min-width:200px;position:relative}
#fa .ara .ms{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sub2);font-size:19px}
#fa .ara input{width:100%;border:1px solid var(--line);border-radius:12px;padding:10px 12px 10px 38px;background:var(--card);color:var(--ink);font-size:13.5px}
#fa .cipler{display:flex;gap:6px;flex-wrap:wrap}
#fa .cip{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:7px 12px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;gap:6px;align-items:center}
#fa .cip i{font-style:normal;background:var(--card2);border-radius:10px;padding:0 6px;font-size:11px}
#fa .cip.on{background:var(--ink);border-color:var(--ink);color:var(--card)}
#fa .cip.on i{background:rgba(255,255,255,.18);color:inherit}

/* Liste */
#fa .liste{display:flex;flex-direction:column;gap:9px}
#fa .sat{--dr:var(--yesil);position:relative;background:var(--card);border:1px solid var(--line);border-radius:15px;padding:13px 14px 13px 18px;display:grid;grid-template-columns:minmax(200px,1.5fr) 140px minmax(170px,1.1fr) minmax(190px,1.3fr) auto;gap:14px;align-items:center;transition:box-shadow .15s}
#fa .sat:hover{box-shadow:0 6px 18px rgba(15,23,42,.06)}
#fa .sat::before{content:'';position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:0 4px 4px 0;background:var(--dr)}
#fa .sat.durdu{opacity:.62}
#fa .kim{display:flex;gap:11px;align-items:center;min-width:0}
#fa .av{width:40px;height:40px;border-radius:12px;flex:none;display:grid;place-items:center;font-weight:900;font-size:14px;color:#fff}
#fa .kim .mal{font-weight:800;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .kim .ted{font-size:12px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .kim .not{font-size:11px;color:var(--sub2);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .fiyat{font-size:18px;font-weight:900;color:var(--ink);white-space:nowrap}
#fa .fiyat small{font-size:11.5px;color:var(--sub);font-weight:700}
#fa .fiyat-alt{font-size:11px;font-weight:800;margin-top:2px}
#fa .donem .rozet{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;background:color-mix(in srgb,var(--dr) 14%,transparent);color:var(--dr)}
#fa .donem .rozet .ms{font-size:14px}
#fa .donem .tar{font-size:11.5px;color:var(--sub);margin-top:5px}
#fa .bar{height:5px;border-radius:4px;background:var(--card2);margin-top:5px;overflow:hidden}
#fa .bar i{display:block;height:100%;border-radius:4px}
#fa .uyum{font-size:12px;line-height:1.4;display:flex;gap:8px;align-items:flex-start}
#fa .uyum .ms{font-size:19px;flex:none}
#fa .uyum b{display:block;font-size:12.5px}
#fa .uyum span.a{color:var(--sub)}
#fa .uyum.iyi .ms,#fa .uyum.iyi b{color:var(--yesil)}
#fa .uyum.kotu .ms,#fa .uyum.kotu b{color:var(--kirmizi)}
#fa .uyum.yok .ms{color:var(--sub2)}#fa .uyum.yok b{color:var(--sub)}
#fa .islem{display:flex;gap:5px}
#fa .ib{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:var(--card);color:var(--sub);display:grid;place-items:center;cursor:pointer;transition:all .15s}
#fa .ib .ms{font-size:18px}
#fa .ib:hover{background:var(--card2);color:var(--ink)}
#fa .ib.sil:hover{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 35%,transparent)}
#fa .sonucyok{display:none;text-align:center;color:var(--sub);padding:28px;font-size:13px;background:var(--card);border:1px dashed var(--line);border-radius:15px}

/* Boş durum */
#fa .bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:34px 20px;text-align:center}
#fa .bos .ms.b{font-size:46px;color:var(--mor);background:color-mix(in srgb,var(--mor) 10%,transparent);border-radius:20px;padding:14px}
#fa .bos h3{margin:14px 0 6px;font-size:16px;color:var(--ink)}
#fa .bos p{margin:0 auto 16px;font-size:13px;color:var(--sub);max-width:520px;line-height:1.55}
#fa .ornek{display:inline-flex;gap:8px;align-items:center;background:var(--card2);border-radius:12px;padding:9px 14px;font-size:12.5px;color:var(--sub);margin-bottom:16px;flex-wrap:wrap;justify-content:center}
#fa .ornek b{color:var(--ink)}

/* Bildirim */
#fa .flash{display:flex;gap:9px;align-items:center;border-radius:13px;padding:11px 15px;margin-bottom:14px;font-weight:700;font-size:13.5px}
#fa .flash.ok{background:color-mix(in srgb,var(--yesil) 13%,transparent);color:#0b805e}
#fa .flash.hata{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:#be123c}
body.koyu #fa .flash.ok{color:#6ee7b7}body.koyu #fa .flash.hata{color:#fda4af}

/* Popup */
.fap-ort{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px}
.fap-ort.ac{display:flex}
.fap{--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb;background:var(--card);color:var(--ink);border-radius:20px;width:100%;max-width:560px;max-height:calc(100vh - 32px);overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,.3);animation:fapGir .18s ease-out;margin:0}
body.koyu .fap{--card:#161d2e;--card2:#1d2539;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
@keyframes fapGir{from{transform:translateY(10px) scale(.98);opacity:0}}
.fap .bas{display:flex;align-items:center;gap:10px;padding:18px 20px 6px}
.fap .bas .ms{color:#4F46E5;font-size:22px}
.fap .bas h3{margin:0;font-size:16.5px;font-weight:900;flex:1}
.fap .kapat{border:0;background:var(--card2);color:var(--sub);width:32px;height:32px;border-radius:10px;cursor:pointer;display:grid;place-items:center}
.fap .kapat .ms{color:var(--sub);font-size:18px}
.fap .gov{padding:10px 20px 6px}
.fap label{display:block;font-size:11.5px;font-weight:800;color:var(--sub);margin:11px 0 5px}
.fap input,.fap select{width:100%;border:1px solid var(--line);border-radius:11px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:14px;outline:none;box-sizing:border-box}
.fap input:focus,.fap select:focus{border-color:#818CF8;box-shadow:0 0 0 3px rgba(129,140,248,.2)}
.fap .iki{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fap .fiyatkutu{display:grid;grid-template-columns:1fr 110px;gap:10px}
.fap .ipucu{display:none;margin-top:8px;font-size:12.5px;background:#EEF2FF;color:#3730A3;border-radius:10px;padding:9px 11px;gap:7px;align-items:center}
body.koyu .fap .ipucu{background:#1e2142;color:#c7d2fe}
.fap .ipucu.ac{display:flex}
.fap .ipucu .ms{font-size:17px}
.fap .ipucu button{margin-left:auto;border:0;background:#4F46E5;color:#fff;border-radius:8px;padding:4px 9px;font-size:11.5px;font-weight:800;cursor:pointer;white-space:nowrap}
.fap .hizli{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
.fap .hizli button{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:5px 11px;font-size:12px;font-weight:700;cursor:pointer}
.fap .hizli button.on,.fap .hizli button:hover{background:#EEF2FF;border-color:#C7D2FE;color:#4338CA}
body.koyu .fap .hizli button.on,body.koyu .fap .hizli button:hover{background:#1e2142;border-color:#3730A3;color:#c7d2fe}
.fap .alt{display:flex;gap:9px;justify-content:flex-end;padding:16px 20px 20px}
.fap .b{border:0;border-radius:12px;padding:11px 18px;font-weight:800;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.fap .b.ikincil{background:var(--card2);color:var(--sub)}
.fap .b.ana{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff}
.fap .b.tehlike{background:#F43F5E;color:#fff}
.fap.kucuk{max-width:400px;text-align:center}
.fap.kucuk .gov{padding:24px 22px 4px}
.fap.kucuk .uyari-ik{width:56px;height:56px;border-radius:18px;display:grid;place-items:center;margin:0 auto 12px;background:rgba(244,63,94,.12);color:#F43F5E}
.fap.kucuk .uyari-ik .ms{font-size:28px}
.fap.kucuk h3{margin:0 0 6px;font-size:16.5px}
.fap.kucuk p{margin:0;font-size:13px;color:var(--sub);line-height:1.5}
.fap.kucuk .alt{justify-content:center}

@media (max-width:980px){
  #fa .sat{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px}
  #fa .sat > *{min-width:0}
  #fa .sat .kim{grid-column:1 / -1}
  #fa .sat .islem{grid-column:1 / -1;justify-content:flex-end}
}
@media (max-width:760px){
  #fa .kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
  #fa .adimlar{grid-template-columns:1fr}
}
@media (max-width:520px){
  #fa .pano{padding:12px 12px 40px}
  #fa .hero{padding:16px}
  #fa .ekle-btn{width:100%;justify-content:center}
  #fa .sat{grid-template-columns:minmax(0,1fr)}
  #fa .hero .yazi{min-width:0}
  #fa .hero h2{font-size:17px}
  #fa .k{padding:12px}
  #fa .k .kd{font-size:19px}
  .fap .iki{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
@php
  $tl = fn ($v) => number_format((float) $v, 2, ',', '.');
  $tl0 = fn ($v) => number_format(round((float) $v), 0, ',', '.');
  $kartlar = $kartlar ?? []; $kpi = $kpi ?? ['aktif' => 0, 'yakinda' => 0, 'bitti' => 0, 'ihlal' => 0, 'fazla' => 0];
  $durumBilgi = [
    'aktif' => ['Yürürlükte', 'verified', '#10B981'],
    'yakinda' => ['Bitiyor', 'hourglass_bottom', '#F59E0B'],
    'baslamadi' => ['Başlamadı', 'schedule', '#4F46E5'],
    'bitti' => ['Süresi doldu', 'event_busy', '#F43F5E'],
    'durdu' => ['Durduruldu', 'pause_circle', '#94a3b8'],
  ];
  $renkler = ['#4F46E5', '#0EA5E9', '#10B981', '#F59E0B', '#EC4899', '#8B5CF6', '#14B8A6', '#F97316'];
  $sayac = ['hepsi' => count($kartlar), 'aktif' => 0, 'yakinda' => 0, 'uyari' => 0, 'bitti' => 0, 'durdu' => 0];
  foreach ($kartlar as $k) {
    if (in_array($k['durum'], ['aktif', 'baslamadi', 'yakinda'], true)) $sayac['aktif']++;
    if (in_array($k['durum'], ['yakinda', 'bitti', 'durdu'], true)) $sayac[$k['durum']]++;
    if ($k['ihlal_say'] > 0) $sayac['uyari']++;
  }
  $trh = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d.m.Y') : null;
@endphp
<div id="fa"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ik"><span class="ms">handshake</span></div>
    <div class="yazi">
      <h2>Tedarikçiyle fiyatı sabitle, gerisini sistem takip etsin</h2>
      <p>Bir tedarikçiyle "şu malzemeyi şu tarihe kadar şu fiyattan veriyorsun" diye anlaştıysan buraya gir. Sipariş bu fiyattan çıkar, fatura gelince fiyat kontrol edilir.</p>
    </div>
    <button class="ekle-btn" type="button" onclick="faAc()"><span class="ms">add</span> Yeni Anlaşma</button>
  </div>

  <div class="adimlar">
    <div class="adim"><span class="no" style="background:#4F46E5">1</span><div><b>Anlaşmayı gir</b><span class="t">Tedarikçi, malzeme, fiyat ve geçerlilik süresi.</span></div></div>
    <div class="adim"><span class="no" style="background:#7C3AED">2</span><div><b>Sipariş bu fiyattan çıkar</b><span class="t">Otomatik ve acil siparişte anlaşmalı fiyat kullanılır.</span></div></div>
    <div class="adim"><span class="no" style="background:#10B981">3</span><div><b>Fatura kontrol edilir</b><span class="t">Tedarikçi anlaşmanın üstünde fatura keserse burada kırmızı uyarı çıkar.</span></div></div>
  </div>

  <div class="kpi">
    <div class="k" style="--kr:#10B981" onclick="faFiltre('aktif')"><span class="ki"><span class="ms">verified</span></span><div><div class="kd">{{ $kpi['aktif'] }}</div><div class="kl">Yürürlükteki anlaşma</div></div></div>
    <div class="k" style="--kr:#F59E0B" onclick="faFiltre('yakinda')"><span class="ki"><span class="ms">hourglass_bottom</span></span><div><div class="kd">{{ $kpi['yakinda'] }}</div><div class="kl">15 gün içinde bitiyor</div>@if ($kpi['yakinda'])<div class="kx">Tedarikçiyle yenile</div>@endif</div></div>
    <div class="k" style="--kr:#F43F5E" onclick="faFiltre('uyari')"><span class="ki"><span class="ms">report</span></span><div><div class="kd">{{ $kpi['ihlal'] }}</div><div class="kl">Anlaşma üstü fatura</div>@if ($kpi['fazla'] > 0)<div class="kx">{{ $tl0($kpi['fazla']) }} ₺ fazla ödendi</div>@endif</div></div>
    <div class="k" style="--kr:#94a3b8" onclick="faFiltre('bitti')"><span class="ki"><span class="ms">event_busy</span></span><div><div class="kd">{{ $kpi['bitti'] }}</div><div class="kl">Süresi dolan</div></div></div>
  </div>

  @if (!count($kartlar))
    <div class="bos">
      <span class="ms b">handshake</span>
      <h3>Henüz fiyat anlaşması yok</h3>
      <p>Sık aldığın bir malzeme için tedarikçinle sabit fiyatta anlaştıysan ekle. Fiyat dalgalansa da sipariş anlaşmalı fiyattan çıkar. Tedarikçi farklı fatura keserse fark burada görünür.</p>
      <div class="ornek"><span class="ms" style="font-size:17px;color:#4F46E5">lightbulb</span> Örnek: <b>Ayçiçek Yağı</b> · <b>Metro Gıda</b> · <b>1.950 ₺ / teneke</b> · 3 ay</div><br>
      <button class="ekle-btn" type="button" onclick="faAc()"><span class="ms">add</span> İlk anlaşmayı ekle</button>
    </div>
  @else
    <div class="arac">
      <div class="ara"><span class="ms">search</span><input id="faAra" type="search" placeholder="Malzeme veya tedarikçi ara…" oninput="faUygula()"></div>
      <div class="cipler" id="faCip">
        @foreach (['hepsi' => 'Tümü', 'aktif' => 'Yürürlükte', 'yakinda' => 'Bitiyor', 'uyari' => 'Fatura uyarısı', 'bitti' => 'Süresi doldu', 'durdu' => 'Durduruldu'] as $fk => $fa)
          @if ($fk === 'hepsi' || $sayac[$fk] > 0)
            <button type="button" class="cip{{ $fk === 'hepsi' ? ' on' : '' }}" data-f="{{ $fk }}" onclick="faFiltre('{{ $fk }}')">{{ $fa }} <i>{{ $sayac[$fk] }}</i></button>
          @endif
        @endforeach
      </div>
    </div>

    <div class="liste" id="faListe">
      @foreach ($kartlar as $k)
        @php
          [$dAd, $dIk, $dRenk] = $durumBilgi[$k['durum']];
          $av = mb_strtoupper(mb_substr($k['ted'], 0, 1, 'UTF-8'), 'UTF-8');
          $avRenk = $renkler[$k['ted_id'] % count($renkler)];
          $filtre = in_array($k['durum'], ['aktif', 'baslamadi'], true) ? 'aktif' : ($k['durum'] === 'yakinda' ? 'aktif yakinda' : $k['durum']);
          if ($k['durum'] === 'yakinda') $kalanYazi = $k['kalan'] === 0 ? 'Bugün bitiyor' : $k['kalan'] . ' gün kaldı';
          elseif ($k['durum'] === 'bitti') $kalanYazi = abs($k['kalan']) . ' gün önce bitti';
          elseif ($k['durum'] === 'baslamadi') $kalanYazi = $trh($k['baslangic']) . ' tarihinde başlar';
          elseif ($k['durum'] === 'durdu') $kalanYazi = 'Elle durduruldu';
          else $kalanYazi = $k['kalan'] !== null ? $k['kalan'] . ' gün kaldı' : 'Süresiz';
        @endphp
        <div class="sat {{ $k['durum'] }}" style="--dr:{{ $k['ihlal_say'] ? '#F43F5E' : $dRenk }}" data-f="{{ $filtre }}" data-u="{{ $k['ihlal_say'] ? 1 : 0 }}" data-ara="{{ mb_strtolower($k['mal'] . ' ' . $k['ted'] . ' ' . $k['not'], 'UTF-8') }}">
          <div class="kim">
            <span class="av" style="background:{{ $avRenk }}">{{ $av }}</span>
            <div style="min-width:0">
              <div class="mal">{{ $k['mal'] }}</div>
              <div class="ted">{{ $k['ted'] }}</div>
              @if ($k['not'])<div class="not" title="{{ $k['not'] }}">📝 {{ $k['not'] }}</div>@endif
            </div>
          </div>
          <div>
            <div class="fiyat">{{ $tl($k['fiyat']) }} ₺@if ($k['birim'])<small> / {{ $k['birim'] }}</small>@endif</div>
            @if ($k['piyasa_fark'] !== null)
              <div class="fiyat-alt" style="color:{{ $k['piyasa_fark'] <= 0 ? '#10B981' : '#F43F5E' }}" title="Diğer tedarikçilerden son 90 gündeki en düşük alış: {{ $tl($k['piyasa']) }} ₺">
                {{ $k['piyasa_fark'] < 0 ? 'Piyasadan %' . abs($k['piyasa_fark']) . ' ucuz' : ($k['piyasa_fark'] > 0 ? 'Piyasadan %' . $k['piyasa_fark'] . ' pahalı' : 'Piyasayla aynı') }}
              </div>
            @endif
          </div>
          <div class="donem">
            <span class="rozet" style="--dr:{{ $dRenk }}"><span class="ms">{{ $dIk }}</span>{{ $dAd }}</span>
            <div class="tar">
              @if ($k['baslangic'] || $k['bitis']){{ $trh($k['baslangic']) ?: 'Bugünden' }} → {{ $trh($k['bitis']) ?: 'süresiz' }} · @endif<b style="color:var(--ink)">{{ $kalanYazi }}</b>
            </div>
            @if ($k['ilerleme'] !== null && $k['durum'] !== 'durdu')<div class="bar"><i style="width:{{ $k['ilerleme'] }}%;background:{{ $dRenk }}"></i></div>@endif
          </div>
          @if ($k['ihlal_say'])
            <div class="uyum kotu"><span class="ms">report</span><div><b>{{ $k['ihlal_say'] }} fatura anlaşmanın üstünde</b><span class="a">Son: {{ $tl($k['son_fiyat']) }} ₺ · toplam {{ $tl($k['fazla']) }} ₺ fazla ödendi</span></div></div>
          @elseif ($k['fatura_say'])
            <div class="uyum iyi"><span class="ms">task_alt</span><div><b>Faturalar uyumlu</b><span class="a">{{ $k['fatura_say'] }} faturada anlaşmalı fiyat uygulandı</span></div></div>
          @else
            <div class="uyum yok"><span class="ms">receipt_long</span><div><b>Henüz fatura yok</b><span class="a">Fatura gelince fiyat kontrol edilecek</span></div></div>
          @endif
          <div class="islem">
            <button type="button" class="ib" title="Düzenle" onclick='faAc(@json($k))'><span class="ms">edit</span></button>
            <form method="POST" action="/fiyat-anlasmalari/durum" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $k['id'] }}">
              <button type="submit" class="ib" title="{{ $k['aktif'] ? 'Durdur' : 'Yeniden başlat' }}"><span class="ms">{{ $k['aktif'] ? 'pause' : 'play_arrow' }}</span></button></form>
            <button type="button" class="ib sil" title="Sil" onclick='faSilSor({{ $k['id'] }}, @json($k['mal'] . ' · ' . $k['ted']))'><span class="ms">delete</span></button>
          </div>
        </div>
      @endforeach
    </div>
    <div class="sonucyok" id="faYok">Bu filtreye uyan anlaşma yok.</div>
  @endif
</div></div>

{{-- Yeni / düzenle popup --}}
<div class="fap-ort" id="faPop" onclick="if (event.target === this) faKapat()">
  <form class="fap" method="POST" action="/fiyat-anlasmalari/ekle" onsubmit="return faKontrol()">
    @csrf
    <input type="hidden" name="id" id="faId">
    <div class="bas"><span class="ms">handshake</span><h3 id="faBaslik">Yeni Fiyat Anlaşması</h3><button type="button" class="kapat" onclick="faKapat()"><span class="ms">close</span></button></div>
    <div class="gov">
      <div class="iki">
        <div><label>Tedarikçi</label><select name="tedarikci_id" id="faTed" required onchange="faIpucu()"><option value="">Seçin…</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
        <div><label>Malzeme</label><select name="malzeme_id" id="faMal" required onchange="faBirimDoldur();faIpucu()"><option value="">Seçin…</option>@foreach ($malzemeler as $m)<option value="{{ $m->id }}">{{ $m->ad }}</option>@endforeach</select></div>
      </div>
      <label>Anlaşılan birim fiyat (KDV hariç)</label>
      <div class="fiyatkutu">
        <input name="birim_fiyat" id="faFiyat" inputmode="decimal" placeholder="0,00 ₺" required autocomplete="off">
        <input name="birim" id="faBirim" list="faBirimler" placeholder="birim" autocomplete="off">
        <datalist id="faBirimler">@foreach ($birimler as $b)<option value="{{ $b }}">@endforeach</datalist>
      </div>
      <div class="ipucu" id="faIpucu"><span class="ms">history</span><span id="faIpucuYazi"></span><button type="button" onclick="faIpucuKullan()">Bu fiyatı yaz</button></div>
      <label>Geçerlilik süresi</label>
      <div class="hizli" id="faHizli">
        <button type="button" data-ay="1">1 ay</button><button type="button" data-ay="3">3 ay</button><button type="button" data-ay="6">6 ay</button><button type="button" data-ay="12">1 yıl</button><button type="button" data-ay="0">Süresiz</button>
      </div>
      <div class="iki">
        <div><input type="date" name="baslangic" id="faBas" title="Başlangıç"></div>
        <div><input type="date" name="bitis" id="faBit" title="Bitiş (boş = süresiz)"></div>
      </div>
      <label>Not <span style="font-weight:600;color:var(--sub2)">(isteğe bağlı)</span></label>
      <input name="not" id="faNot" placeholder="Sözleşme no, minimum miktar, teslim şartı…" autocomplete="off">
    </div>
    <div class="alt">
      <button type="button" class="b ikincil" onclick="faKapat()">Vazgeç</button>
      <button type="submit" class="b ana"><span class="ms" style="font-size:18px">save</span><span id="faKaydet">Anlaşmayı Kaydet</span></button>
    </div>
  </form>
</div>

{{-- Silme onayı --}}
<div class="fap-ort" id="faSilPop" onclick="if (event.target === this) this.classList.remove('ac')">
  <form class="fap kucuk" method="POST" action="/fiyat-anlasmalari/durum">
    @csrf
    <input type="hidden" name="id" id="faSilId"><input type="hidden" name="sil" value="1">
    <div class="gov">
      <div class="uyari-ik"><span class="ms">delete</span></div>
      <h3>Anlaşma silinsin mi?</h3>
      <p><b id="faSilAd" style="color:var(--ink)"></b><br>Silinen anlaşma geri gelmez. Geçici olarak askıya almak için <b>Durdur</b>'u kullanabilirsin.</p>
    </div>
    <div class="alt">
      <button type="button" class="b ikincil" onclick="document.getElementById('faSilPop').classList.remove('ac')">Vazgeç</button>
      <button type="submit" class="b tehlike">Evet, sil</button>
    </div>
  </form>
</div>

<script>
(function () {
  const IPUCU = @json((object) ($ipucu ?? [])), MALBIRIM = @json((object) ($malBirim ?? []));
  const el = (x) => document.getElementById(x);
  const para = (v) => Number(v).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const yerel = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const bugun = () => yerel(new Date());
  let filtre = 'hepsi', ipucuKayit = null;

  window.faFiltre = function (f) {
    filtre = f;
    document.querySelectorAll('#faCip .cip').forEach(c => c.classList.toggle('on', c.dataset.f === f));
    faUygula();
    const l = el('faListe'); if (l) l.scrollIntoView({behavior: 'smooth', block: 'nearest'});
  };
  window.faUygula = function () {
    const q = (el('faAra') ? el('faAra').value : '').toLocaleLowerCase('tr-TR').trim();
    let gor = 0;
    document.querySelectorAll('#faListe .sat').forEach(s => {
      const ok = (filtre === 'hepsi' || (filtre === 'uyari' ? s.dataset.u === '1' : s.dataset.f.split(' ').includes(filtre))) && (!q || s.dataset.ara.includes(q));
      s.style.display = ok ? '' : 'none'; if (ok) gor++;
    });
    if (el('faYok')) el('faYok').style.display = gor ? 'none' : 'block';
  };

  window.faAc = function (k) {
    k = k || null;
    el('faId').value = k ? k.id : '';
    el('faBaslik').textContent = k ? 'Anlaşmayı Düzenle' : 'Yeni Fiyat Anlaşması';
    el('faKaydet').textContent = k ? 'Değişiklikleri Kaydet' : 'Anlaşmayı Kaydet';
    el('faTed').value = k ? k.ted_id : '';
    el('faMal').value = k ? k.mal_id : '';
    el('faFiyat').value = k ? String(k.fiyat).replace('.', ',') : '';
    el('faBirim').value = k ? (k.birim || '') : '';
    el('faBas').value = k ? (k.baslangic || '') : bugun();
    el('faBit').value = k ? (k.bitis || '') : '';
    el('faNot').value = k ? (k.not || '') : '';
    ['faFiyat', 'faBit'].forEach(i => el(i).style.borderColor = '');
    document.querySelectorAll('#faHizli button').forEach(b => b.classList.remove('on'));
    faIpucu();
    el('faPop').classList.add('ac');
    setTimeout(() => (k ? el('faFiyat') : el('faTed')).focus(), 60);
  };
  window.faKapat = () => el('faPop').classList.remove('ac');
  window.faBirimDoldur = function () { const b = MALBIRIM[el('faMal').value]; if (b && !el('faBirim').value) el('faBirim').value = b; };
  window.faIpucu = function () {
    const r = IPUCU[el('faTed').value + '-' + el('faMal').value] || null;
    ipucuKayit = r;
    el('faIpucu').classList.toggle('ac', !!r);
    if (r) el('faIpucuYazi').innerHTML = 'Bu tedarikçiden son alış: <b>' + para(r.f) + ' ₺' + (r.b ? ' / ' + r.b : '') + '</b> (' + r.t.split('-').reverse().join('.') + ')';
  };
  window.faIpucuKullan = function () { if (!ipucuKayit) return; el('faFiyat').value = String(ipucuKayit.f).replace('.', ','); if (ipucuKayit.b) el('faBirim').value = ipucuKayit.b; };
  document.querySelectorAll('#faHizli button').forEach(b => b.addEventListener('click', function () {
    document.querySelectorAll('#faHizli button').forEach(x => x.classList.toggle('on', x === b));
    const ay = +b.dataset.ay;
    if (!el('faBas').value) el('faBas').value = bugun();
    if (!ay) { el('faBit').value = ''; return; }
    const d = new Date(el('faBas').value + 'T12:00:00'); d.setMonth(d.getMonth() + ay); d.setDate(d.getDate() - 1);
    el('faBit').value = yerel(d);
  }));
  window.faKontrol = function () {
    let s = (el('faFiyat').value || '').trim().replace(/\s|₺/g, '');
    if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.');   // 1.950,50 → 1950.50
    const f = parseFloat(s);
    if (!(f > 0)) { el('faFiyat').focus(); el('faFiyat').style.borderColor = '#F43F5E'; return false; }
    el('faFiyat').value = String(f);
    if (el('faBas').value && el('faBit').value && el('faBit').value < el('faBas').value) { el('faBit').focus(); el('faBit').style.borderColor = '#F43F5E'; return false; }
    return true;
  };
  window.faSilSor = function (id, ad) { el('faSilId').value = id; el('faSilAd').textContent = ad; el('faSilPop').classList.add('ac'); };
  document.addEventListener('keydown', e => { if (e.key === 'Escape') { faKapat(); el('faSilPop').classList.remove('ac'); } });
  // popup'lar body'ye: sayfa kapsayıcısı içinde ortalanmaz
  ['faPop', 'faSilPop'].forEach(i => document.body.appendChild(el(i)));
})();
</script>
@endsection

@extends('layout.app')
@section('rk_ikon', 'space_dashboard')
@section('baslik', 'Satın Alma Özeti')
@section('title', 'Satın Alma Özeti')

@push('head')
<style>
#db{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #db{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#db{background:var(--bg);min-height:calc(100vh - 60px)}#db *{box-sizing:border-box}
#db .pano{max-width:1600px;margin:0 auto;padding:20px 28px 32px}
@media(min-width:1600px){#db .pano{padding:24px 40px 40px}#db .arac-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px}#db .arac{padding:16px 10px}#db .arac .ai{width:48px;height:48px}#db .arac .ai .material-icons-outlined{font-size:24px}#db .arac .al{font-size:12.5px}}
#db a{text-decoration:none;color:inherit}
#db .selam-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:16px}
#db .selam-ikon{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#FB923C,#F43F5E);display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .selam-ikon .material-icons-outlined{color:#fff;font-size:24px}
#db .selam-kart .ad{font-size:17px;font-weight:700;color:var(--ink);line-height:1.1}
#db .selam-kart .sube{font-size:12.5px;color:var(--sub)}
#db .uclu{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
#db .ciro-mini{background:var(--card);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;display:flex;flex-direction:row;transition:opacity .12s}
#db .ciro-mini:hover{opacity:.9}
#db .ciro-mini .cubuk{width:5px;flex-shrink:0}
#db .ciro-mini .ic{padding:14px;flex:1;min-width:0}
#db .ciro-baslik-row{display:flex;align-items:center;gap:8px;margin-bottom:10px}
#db .ciro-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .ciro-ikon .material-icons-outlined{font-size:17px}
#db .ciro-label{font-size:12.5px;font-weight:600;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#db .ciro-deger{font-size:27px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:8px}
#db .ciro-trend{display:flex;align-items:center;gap:3px;font-size:11px;font-weight:600}
#db .ciro-trend .material-icons-outlined{font-size:14px}
#db .hizli{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
#db .bilgi-kart{background:var(--card);border-radius:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:13px;transition:opacity .12s}
#db .bilgi-kart:hover{opacity:.9}
#db .bk-row{display:flex;align-items:center;gap:7px;margin-bottom:9px}
#db .bk-ikon{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .bk-ikon .material-icons-outlined{font-size:15px}
#db .bk-label{font-size:11.5px;font-weight:600;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#db .bk-deger{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:3px}
#db .bk-alt{font-size:11px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#db .kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px;margin-bottom:16px}
#db .ucgen-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
#db .ucgen-grid > .kart{margin-bottom:0}
@media(max-width:1100px){#db .ucgen-grid{grid-template-columns:1fr}}
#db .kart-baslik{display:flex;align-items:center;gap:8px;margin-bottom:14px}
#db .kb-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .kb-ikon .material-icons-outlined{font-size:17px}
#db .kb-ad{font-size:15px;font-weight:700;color:var(--ink)}
#db .butce-bar{height:10px;border-radius:6px;background:var(--card2);overflow:hidden;margin-top:8px}
#db .butce-bar div{height:100%;border-radius:6px;transition:width .7s cubic-bezier(.22,.68,0,1.2)}
#db .bek-satir{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line)}
#db .bek-satir:first-of-type{border-top:none}
#db .roz{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;flex-shrink:0}
#db .arac-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(116px,1fr));gap:10px;margin-bottom:16px}
#db .arac{background:var(--card);border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:14px 8px;text-align:center;transition:transform .12s,opacity .12s;display:block}
#db .arac:hover{transform:translateY(-2px);opacity:.92}
#db .arac .ai{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 7px}
#db .arac .ai .material-icons-outlined{font-size:22px}
#db .arac .al{font-size:11.5px;font-weight:600;color:var(--ink);line-height:1.25}
#db .ai-panel{flex:1;min-width:240px;margin:0 10px;padding:2px 0 2px 14px;border-left:2px solid color-mix(in srgb,var(--mor1) 28%,transparent);display:flex;flex-direction:column;gap:4px}
#db .ai-panel .aip-head{display:flex;align-items:center;gap:9px;margin-bottom:3px}
#db .ai-panel .aip-orb{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 0 color-mix(in srgb,var(--mor1) 55%,transparent);animation:aiorb 2.2s ease-out infinite}
#db .ai-panel .aip-orb .material-icons-outlined{font-size:18px;color:#fff}
@keyframes aiorb{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--mor1) 45%,transparent)}70%{box-shadow:0 0 0 9px transparent}100%{box-shadow:0 0 0 0 transparent}}
#db .ai-panel .aip-baslik{font-size:13.5px;font-weight:800;color:var(--ink);letter-spacing:-.1px}
#db .ai-panel .aip-body{display:flex;flex-direction:column}
#db .ai-panel .aip-oneri{display:flex;align-items:center;gap:9px;padding:5px 2px;text-decoration:none;transition:transform .14s ease,color .14s ease}
#db .ai-panel .aip-oneri:hover{transform:translateX(3px)}
#db .ai-panel .aip-oneri:hover .aip-mtn{color:var(--mor1)}
#db .ai-panel .aip-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
#db .ai-panel .aip-mtn{flex:1;font-size:12.5px;font-weight:600;color:var(--ink);line-height:1.35;transition:color .14s ease}
#db .ai-panel .aip-bos{font-size:12.5px;font-weight:600;color:var(--sub);padding:2px 0}
#db .ai-panel .aip-tumu{font-size:11.5px;font-weight:700;color:var(--mor1);text-decoration:none;align-self:flex-start;margin-top:2px;opacity:.9;transition:opacity .14s}
#db .ai-panel .aip-tumu:hover{opacity:1}
#db .selam-harcama{text-align:right;flex-shrink:0;background:linear-gradient(135deg,var(--mor1),var(--mavi));border-radius:16px;padding:12px 20px;color:#fff;box-shadow:0 6px 18px color-mix(in srgb,var(--mor1) 38%,transparent)}
#db .selam-harcama .sh-lbl{font-size:12px;font-weight:700;color:rgba(255,255,255,.85);margin-bottom:4px;display:flex;align-items:center;gap:5px;justify-content:flex-end}
#db .selam-harcama .sh-deger{font-size:34px;font-weight:900;color:#fff;line-height:1;letter-spacing:-.5px}
#db .selam-harcama .sh-alt{font-size:12px;font-weight:800;margin-top:6px;color:#fff !important;background:rgba(255,255,255,.2);border-radius:20px;padding:2px 10px;display:inline-block}
@media(max-width:900px){#db .selam-kart{flex-wrap:wrap}#db .ai-panel{order:3;flex-basis:100%;margin:6px 0 0}}
@media(max-width:620px){#db .selam-harcama{display:none}#db .hizli{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
@php
  $saat = (int) date('H');
  $selam = $saat < 12 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $selamIk = $saat < 18 ? 'wb_sunny' : 'nightlight';
  $tl0 = fn($v) => number_format((float)$v, 0, ',', '.') . ' ₺';
  $butceY = $ay_butce > 0 ? round($ay_harcama / $ay_butce * 100) : null;
  $butceC = $butceY === null ? 'var(--sub)' : ($butceY > 100 ? 'var(--kirmizi)' : ($butceY >= 85 ? 'var(--amber)' : 'var(--yesil)'));
  $dr = ['yeni'=>['Yeni','var(--mavi)'],'teklif'=>['Teklif','var(--mor1)'],'onayda'=>['Onayda','var(--amber)'],'onaylandi'=>['Onaylandı','var(--yesil)'],'siparis'=>['Siparişte','#0EA5E9'],'tamamlandi'=>['Tamam','var(--yesil)']];
  $araclar = [
    ['/mal-kabul','inventory','Mal Kabul','#10B981'],
    ['/fatura-eslestir','fact_check','Fatura Eşleştir','#F59E0B'],
    ['/fatura-ocr','document_scanner','Fatura OCR','#7C3AED'],
    ['/stok','inventory_2','Stok','#4F46E5'],
    ['/sayim','fact_check','Sayım','#0EA5E9'],
    ['/recete','menu_book','Reçeteler','#7C3AED'],
    ['/parti-takip','event_busy','Parti / SKT','#F43F5E'],
    ['/tedarikci','local_shipping','Tedarikçiler','#64748B'],
    ['/fiyat-radari','radar','Fiyat Radarı','#EC4899'],
    ['/hal-fiyatlari','storefront','Hal Fiyatları','#059669'],
    ['/satinalma-analiz','leaderboard','Analiz · Karne','#4F46E5'],
    ['/uretim-riski','warning_amber','Üretim Riski','#F59E0B'],
  ];
@endphp
<div id="db">
<div class="pano">

  {{-- Selam --}}
  <div class="selam-kart">
    <div class="selam-ikon"><span class="material-icons-outlined">{{ $selamIk }}</span></div>
    <div style="flex-shrink:0"><div class="ad">{{ $selam }}, {{ $ad ?: 'Satın Alma' }}! 🛒</div><div class="sube">Satın alma operasyonunun özeti</div></div>
    @php
      $oneriler = [];
      if (($kritik ?? 0) > 0)      $oneriler[] = ['ik' => 'warning_amber',     'renk' => 'var(--kirmizi)', 'mtn' => $kritik.' kritik stok var — hemen sipariş öner', 'link' => '/siparis-onerisi'];
      if (($geciken ?? 0) > 0)     $oneriler[] = ['ik' => 'local_shipping',    'renk' => 'var(--amber)',   'mtn' => $geciken.' sipariş gecikti — tedarikçiyi takip et', 'link' => '/satinalma-siparis'];
      if (($teklif_geldi ?? 0) > 0) $oneriler[] = ['ik' => 'mark_email_read',   'renk' => 'var(--mor1)',    'mtn' => $teklif_geldi.' teklif incelemeni bekliyor — en uygunu seç', 'link' => '/gelen-teklifler'];
      if (($talep_onayda ?? 0) > 0) $oneriler[] = ['ik' => 'how_to_reg',        'renk' => 'var(--mavi)',    'mtn' => $talep_onayda.' talep onayda bekliyor', 'link' => '/satinalma-talep'];
      if (($talep_yeni ?? 0) > 0)  $oneriler[] = ['ik' => 'search',             'renk' => 'var(--mor1)',    'mtn' => $talep_yeni.' yeni talep — fiyat araştır', 'link' => '/satinalma-talep'];
      $oneriler = array_slice($oneriler, 0, 3);
    @endphp
    <div class="ai-panel">
      <div class="aip-head"><span class="aip-orb"><span class="material-icons-outlined">auto_awesome</span></span> <span class="aip-baslik">AI Satın Alma Asistanı</span></div>
      <div class="aip-body">
        @forelse ($oneriler as $o)
          <a href="{{ $o['link'] }}" class="aip-oneri">
            <span class="aip-dot" style="background:{{ $o['renk'] }}"></span>
            <span class="aip-mtn">{{ $o['mtn'] }}</span>
          </a>
        @empty
          <div class="aip-bos"><span class="material-icons-outlined" style="color:var(--yesil);font-size:18px;vertical-align:-4px">check_circle</span> Stok ve siparişler yolunda — acil aksiyon yok.</div>
        @endforelse
      </div>
      <a href="/satinalma-asistan" class="aip-tumu">AI'ya soru sor · tüm öneriler <span class="material-icons-outlined" style="font-size:15px;vertical-align:-3px">arrow_forward</span></a>
    </div>
    <a href="/satinalma-butce" class="selam-harcama" style="text-decoration:none">
      <div class="sh-lbl"><span class="material-icons-outlined" style="font-size:14px;vertical-align:-2px">payments</span> Bu Ay Harcama</div>
      <div class="sh-deger">{{ $tl0($ay_harcama) }}</div>
      <div class="sh-alt" style="color:{{ $butceC }}">@if($butceY!==null)%{{ $butceY }} bütçe @if($butceY>100)· aşım!@endif @else bütçe tanımla @endif</div>
    </a>
  </div>

  {{-- 3 büyük kart --}}
  <div class="uclu">
    <a href="/satinalma-talep" class="ciro-mini">
      <div class="cubuk" style="background:var(--mor1)"></div>
      <div class="ic"><div class="ciro-baslik-row"><div class="ciro-ikon" style="background:color-mix(in srgb,var(--mor1) 15%,transparent);color:var(--mor1)"><span class="material-icons-outlined">assignment</span></div><span class="ciro-label">Yeni Talep</span></div>
        <div class="ciro-deger">{{ $talep_yeni }}</div>
        <div class="ciro-trend" style="color:var(--sub)">işlem bekliyor</div></div>
    </a>
    <a href="/gelen-teklifler" class="ciro-mini">
      <div class="cubuk" style="background:var(--yesil)"></div>
      <div class="ic"><div class="ciro-baslik-row"><div class="ciro-ikon" style="background:color-mix(in srgb,var(--yesil) 15%,transparent);color:var(--yesil)"><span class="material-icons-outlined">mark_email_read</span></div><span class="ciro-label">Teklif Geldi</span></div>
        <div class="ciro-deger">{{ $teklif_geldi }}</div>
        <div class="ciro-trend" style="color:var(--sub)">incelenecek</div></div>
    </a>
    <a href="/satinalma-siparis" class="ciro-mini">
      <div class="cubuk" style="background:var(--mavi)"></div>
      <div class="ic"><div class="ciro-baslik-row"><div class="ciro-ikon" style="background:color-mix(in srgb,var(--mavi) 15%,transparent);color:var(--mavi)"><span class="material-icons-outlined">shopping_cart_checkout</span></div><span class="ciro-label">Siparişler</span></div>
        <div class="ciro-deger">{{ $acik_siparis }}</div>
        <div class="ciro-trend" style="color:{{ ($geciken ?? 0) > 0 ? 'var(--kirmizi)' : 'var(--sub)' }}">@if(($geciken ?? 0) > 0){{ $geciken }} geciken ⚠️@else açık sipariş @endif</div></div>
    </a>
  </div>

  {{-- 4 küçük kart --}}
  <div class="hizli">
    <a href="/satinalma-talep" class="bilgi-kart"><div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">how_to_reg</span></div><span class="bk-label">Onay Bekleyen</span></div><div class="bk-deger">{{ $talep_onayda }}</div><div class="bk-alt">talep</div></a>
    <a href="/satinalma-siparis" class="bilgi-kart"><div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--mavi) 15%,transparent);color:var(--mavi)"><span class="material-icons-outlined">shopping_cart_checkout</span></div><span class="bk-label">Açık Sipariş</span></div><div class="bk-deger">{{ $acik_siparis }}</div><div class="bk-alt">devam eden</div></a>
    <a href="/satinalma-siparis" class="bilgi-kart"><div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--kirmizi) 15%,transparent);color:var(--kirmizi)"><span class="material-icons-outlined">running_with_errors</span></div><span class="bk-label">Geciken Teslim</span></div><div class="bk-deger" style="color:{{ $geciken>0?'var(--kirmizi)':'var(--ink)' }}">{{ $geciken }}</div><div class="bk-alt">sipariş</div></a>
    <a href="/siparis-onerisi" class="bilgi-kart"><div class="bk-row"><div class="bk-ikon" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)"><span class="material-icons-outlined">inventory_2</span></div><span class="bk-label">Kritik Stok</span></div><div class="bk-deger" style="color:{{ $kritik>0?'var(--amber)':'var(--ink)' }}">{{ $kritik }}</div><div class="bk-alt">malzeme</div></a>
  </div>

  {{-- Satın Alma Araçları --}}
  <div class="kart-baslik" style="margin:2px 2px 10px"><div class="kb-ikon" style="background:color-mix(in srgb,var(--mavi) 15%,transparent);color:var(--mavi)"><span class="material-icons-outlined">apps</span></div><span class="kb-ad">Satın Alma Araçları</span></div>
  <div class="arac-grid">
    @foreach ($araclar as [$u,$ik,$ad,$rk])
      <a href="{{ $u }}" class="arac"><div class="ai" style="background:color-mix(in srgb,{{ $rk }} 14%,transparent);color:{{ $rk }}"><span class="material-icons-outlined">{{ $ik }}</span></div><div class="al">{{ $ad }}</div></a>
    @endforeach
  </div>

  {{-- Bütçe --}}
  @if ($ay_butce > 0)
  <div class="kart">
    <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,var(--yesil) 15%,transparent);color:var(--yesil)"><span class="material-icons-outlined">savings</span></div><span class="kb-ad">Bu Ay Bütçe Kullanımı</span>
      <span style="margin-left:auto;font-weight:800;color:{{ $butceC }}">%{{ $butceY }}@if($butceY>100) · aşım!@endif</span></div>
    <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--sub)"><span>Harcama: {{ $tl0($ay_harcama) }}</span><span>Bütçe: {{ $tl0($ay_butce) }}</span></div>
    <div class="butce-bar"><div style="width:{{ min(100,$butceY) }}%;background:{{ $butceC }}"></div></div>
  </div>
  @endif

  {{-- Operasyonel üçgen: Son Talepler (iş geliyor) + Yaklaşan Teslimler (iş teslim) + Fiyat Hareketleri (sinyaller) --}}
  <div class="ucgen-grid">
    {{-- Son Talepler --}}
    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,var(--mor1) 15%,transparent);color:var(--mor1)"><span class="material-icons-outlined">history</span></div><span class="kb-ad">Son Talepler</span></div>
      @forelse ($son_talepler as $t)
        @php [$dm,$dc] = $dr[$t->durum] ?? [$t->durum,'var(--sub)']; @endphp
        <a href="/satinalma-talep" class="bek-satir">
          <span class="material-icons-outlined" style="color:var(--sub);font-size:19px">assignment</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $t->departman }}</div><div style="font-size:11.5px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $t->isteyen_ad }} · {{ $t->kalem }} kalem · {{ \Carbon\Carbon::parse($t->created_at)->diffForHumans() }}</div></div>
          <span class="roz" style="background:{{ $dc }}">{{ $dm }}</span>
        </a>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Henüz talep yok.</div>
      @endforelse
    </div>

    {{-- Yaklaşan Teslimler --}}
    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,#0EA5E9 15%,transparent);color:#0EA5E9"><span class="material-icons-outlined">local_shipping</span></div><span class="kb-ad">Yaklaşan Teslimler</span><span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">7 gün</span></div>
      @forelse ($yaklasan_teslimler as $s)
        @php
          $bt = \Carbon\Carbon::parse($s->beklenen_teslim)->startOfDay();
          $bg = today()->startOfDay()->diffInDays($bt, false);
          if ($bg < 0)      [$dm,$dc] = [abs($bg).' gün geciken','var(--kirmizi)'];
          elseif ($bg == 0) [$dm,$dc] = ['Bugün','var(--amber)'];
          elseif ($bg == 1) [$dm,$dc] = ['Yarın','var(--amber)'];
          else              [$dm,$dc] = [$bg.' gün sonra','var(--mavi)'];
        @endphp
        <a href="/satinalma-siparis" class="bek-satir">
          <span class="material-icons-outlined" style="color:var(--sub);font-size:19px">inventory_2</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $s->tedarikci ?: 'Tedarikçi —' }}</div><div style="font-size:11.5px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $s->siparis_no ?: '#'.$s->id }} · {{ $tl0($s->genel_toplam) }}</div></div>
          <span class="roz" style="background:{{ $dc }}">{{ $dm }}</span>
        </a>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Önümüzdeki 7 günde teslim yok.</div>
      @endforelse
    </div>

    {{-- Fiyat Hareketleri --}}
    <div class="kart">
      <div class="kart-baslik"><div class="kb-ikon" style="background:color-mix(in srgb,#EC4899 15%,transparent);color:#EC4899"><span class="material-icons-outlined">trending_up</span></div><span class="kb-ad">Fiyat Hareketleri</span><span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">30 gün</span></div>
      @forelse ($son_fiyat_hareketleri as $f)
        @php
          $dc = $f->uyari === 'kirmizi' ? 'var(--kirmizi)' : 'var(--amber)';
          $yz = (float) $f->fiyat_farki_yuzde;
          $isaret = $yz > 0 ? '+' : '';
        @endphp
        <a href="/fiyat-radari" class="bek-satir">
          <span class="material-icons-outlined" style="color:{{ $dc }};font-size:19px">{{ $yz > 0 ? 'north_east' : 'south_east' }}</span>
          <div style="flex:1;min-width:0"><div style="font-weight:600;font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $f->ad }}</div><div style="font-size:11.5px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ number_format((float) $f->birim_fiyat, 2, ',', '.') }} ₺ · {{ \Carbon\Carbon::parse($f->tarih)->diffForHumans() }}</div></div>
          <span class="roz" style="background:{{ $dc }}">{{ $isaret }}{{ number_format($yz, 1, ',', '.') }}%</span>
        </a>
      @empty
        <div style="color:var(--sub);text-align:center;padding:16px;font-size:12.5px">Fiyat uyarısı yok — sakin.</div>
      @endforelse
    </div>
  </div>

</div>
</div>
@endsection

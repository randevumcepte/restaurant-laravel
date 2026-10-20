@extends('layout.app')
@section('title', $sube->ad . ' · Dashboard')
@section('rk_ikon', 'dashboard')
@section('baslik', 'Patron Paneli')

@push('head')
<style>
/* ===== TEMA DEĞİŞKENLERİ ===== */
#db{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;
  --amber:#F59E0B;--gold:#F6CE63;--pembe:#EC4899;--turuncu:#F97316;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
}
body.koyu #db{
  --card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
}

/* ===== GENEL ===== */
#db{background:var(--bg);min-height:calc(100vh - 60px);font-family:inherit}
#db *{box-sizing:border-box}
#db .pano{max-width:1400px;margin:0 auto;padding:20px 22px 32px}

/* ===== DÖNEM SEÇİCİ ===== */
#db .donem-wrap{display:flex;background:var(--card);border-radius:14px;padding:4px;box-shadow:0 2px 8px rgba(0,0,0,.07);flex:1}
#db .donem-btn{flex:1;text-align:center;padding:9px 6px;border-radius:11px;cursor:pointer;font-size:13px;font-weight:600;color:var(--sub);transition:all .18s;border:none;background:none}
#db .donem-btn.akt{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;font-weight:700}

/* ===== SELAM KARTI ===== */
#db .selam-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:14px 16px;display:flex;align-items:center;gap:12px;margin-bottom:16px}
#db .selam-ikon{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#FB923C,#F43F5E);display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .selam-ikon .material-icons-outlined{color:#fff;font-size:24px}
#db .selam-kart .ad{font-size:17px;font-weight:700;color:var(--ink);line-height:1.1}
#db .selam-kart .sube{font-size:12.5px;color:var(--sub)}
#db .selam-cip{display:inline-flex;align-items:center;gap:5px;padding:6px 10px;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;flex-shrink:0}
#db .selam-cip .material-icons-outlined{font-size:14px}

/* ===== 3 CİRO KARTI ===== */
#db .uclu-ciro{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px}
#db .ciro-mini{background:var(--card);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;display:flex;flex-direction:row}
#db .ciro-mini .cubuk{width:5px;flex-shrink:0}
#db .ciro-mini .ic{padding:13px;flex:1;min-width:0}
#db .ciro-mini .ciro-baslik-row{display:flex;align-items:center;gap:8px;margin-bottom:10px}
#db .ciro-mini .ciro-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .ciro-mini .ciro-ikon .material-icons-outlined{font-size:17px}
#db .ciro-mini .ciro-label{font-size:12.5px;font-weight:600;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .ciro-mini .ciro-deger{font-size:27px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:8px}
#db .ciro-mini .ciro-trend{display:flex;align-items:center;gap:3px;font-size:11px;font-weight:600}
#db .ciro-mini .ciro-trend .material-icons-outlined{font-size:14px}

/* ===== HIZLI BİLGİ (4 kart) ===== */
#db .hizli-bilgi{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px}
#db .bilgi-kart{background:var(--card);border-radius:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:13px}
#db .bilgi-kart .bk-row{display:flex;align-items:center;gap:7px;margin-bottom:9px}
#db .bilgi-kart .bk-ikon{width:26px;height:26px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .bilgi-kart .bk-ikon .material-icons-outlined{font-size:15px}
#db .bilgi-kart .bk-label{font-size:11.5px;font-weight:600;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .bilgi-kart .bk-deger{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .bilgi-kart .bk-alt{font-size:11px;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ===== MASA + POS SATIRI ===== */
#db .masa-pos-row{display:grid;grid-template-columns:4fr 6fr;gap:16px;margin-bottom:16px}

/* ===== MASA KARTI ===== */
#db .masa-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px}
#db .kart-baslik{display:flex;align-items:center;gap:8px;margin-bottom:14px}
#db .kart-baslik .kb-ikon{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db .kart-baslik .kb-ikon .material-icons-outlined{font-size:17px}
#db .kart-baslik .kb-ad{font-size:15px;font-weight:700;color:var(--ink)}
#db .masa-sayaclar{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px}
#db .masa-sayac{border-radius:13px;padding:12px 6px;text-align:center}
#db .masa-sayac .ms-label{display:flex;align-items:center;justify-content:center;gap:4px;font-size:11px;font-weight:600;margin-bottom:6px}
#db .masa-sayac .ms-label .material-icons-outlined{font-size:12px}
#db .masa-sayac .ms-deger{font-size:26px;font-weight:800;line-height:1}
#db .masa-doluluk-row{display:flex;align-items:flex-end;gap:8px;margin-bottom:3px}
#db .masa-doluluk-row .yuzde{font-size:30px;font-weight:800;color:var(--ink);line-height:1}
#db .yogunluk-cip{display:inline-flex;align-items:center;gap:3px;padding:4px 8px;border-radius:10px;font-size:11px;font-weight:700;margin-bottom:5px}
#db .yogunluk-cip .material-icons-outlined{font-size:13px}
#db .doluluk-bar{height:8px;border-radius:6px;background:var(--card2);overflow:hidden;margin:10px 0 16px}
#db .doluluk-bar div{height:100%;border-radius:6px;transition:width .7s cubic-bezier(.22,.68,0,1.2)}
#db .bekleyen-baslik{font-size:13px;font-weight:700;color:var(--ink);margin-bottom:8px}
#db .bekleyen-satir{display:flex;align-items:center;gap:10px;padding:7px 0;border-top:1px solid var(--line)}
#db .bekleyen-satir:first-of-type{border-top:none}
#db .bek-nokta{width:8px;height:8px;border-radius:50%;flex-shrink:0}
#db .bek-ad{flex:1;font-size:12.5px;font-weight:500;color:var(--sub2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .bek-sure{font-size:12px;font-weight:700}

/* ===== POS KARTI ===== */
#db .pos-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px}
#db .pos-tahsilat{background:linear-gradient(135deg,var(--yesil),#059669);border-radius:14px;padding:16px;margin-bottom:12px;display:flex;align-items:center}
#db .pos-tahsilat .pt-sol{flex:1;min-width:0}
#db .pos-tahsilat .pt-label{color:rgba(255,255,255,.7);font-size:12.5px;margin-bottom:4px}
#db .pos-tahsilat .pt-deger{font-size:26px;font-weight:800;color:#fff;letter-spacing:-.5px;line-height:1}
#db .pos-tahsilat .pt-badge{display:inline-flex;align-items:center;gap:3px;background:rgba(255,255,255,.24);border-radius:20px;padding:6px 10px;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}
#db .pos-tahsilat .pt-badge .material-icons-outlined{font-size:14px}
#db .pos-mini-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}
#db .pos-mini{border-radius:13px;padding:12px;cursor:pointer;border:1px solid transparent;transition:opacity .12s}
#db .pos-mini:hover{opacity:.82}
#db .pos-mini .pm-label{font-size:11px;font-weight:600;color:var(--sub2);margin-bottom:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#db .pos-mini .pm-row{display:flex;align-items:flex-end;gap:6px}
#db .pos-mini .pm-buyuk{font-size:25px;font-weight:800;line-height:1}
#db .pos-mini .pm-alt{flex:1;font-size:11.5px;font-weight:600;color:var(--sub);text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .servis-baslik{font-size:13px;font-weight:700;color:var(--ink);margin-bottom:12px}
#db .servis-bar-satir{display:flex;align-items:center;gap:8px;margin-bottom:11px}
#db .servis-bar-satir:last-child{margin-bottom:0}
#db .servis-bar-satir .sb-ad{width:56px;font-size:12px;font-weight:600;color:var(--sub2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0}
#db .servis-bar-track{flex:1;height:9px;border-radius:6px;background:var(--card2);overflow:hidden}
#db .servis-bar-track div{height:100%;border-radius:6px;background:#FB7185;transition:width .7s cubic-bezier(.22,.68,0,1.2)}
#db .servis-bar-satir .sb-tutar{font-size:12px;font-weight:700;color:var(--ink);flex-shrink:0;min-width:72px;text-align:right}

/* ===== MALİYET KARTI ===== */
#db .maliyet-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px;display:flex;align-items:center;margin-bottom:16px;cursor:pointer;transition:opacity .12s}
#db .maliyet-kart:hover{opacity:.88}
#db .maliyet-kart .mk-sol{flex:1;min-width:0}
#db .maliyet-kart .mk-label{font-size:11px;font-weight:600;color:var(--sub);margin-bottom:6px}
#db .maliyet-kart .mk-deger{font-size:20px;font-weight:800;color:var(--ink);line-height:1;margin-bottom:2px}
#db .maliyet-kart .mk-alt{font-size:11px;color:var(--sub)}
#db .maliyet-halka{position:relative;width:66px;height:66px;flex-shrink:0}
#db .maliyet-halka svg{transform:rotate(-90deg)}
#db .maliyet-halka .mh-yuzde{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800}

/* ===== KAYIP RADARI ===== */
#db .kayip-baslik{padding-left:4px;margin-bottom:10px}
#db .kayip-baslik .kb-t{font-size:15px;font-weight:700;color:var(--ink)}
#db .kayip-baslik .kb-a{font-size:11px;color:var(--sub)}
#db .kayip-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px}
#db .kayip-kart{border-radius:16px;overflow:hidden;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.07);transition:opacity .12s;display:flex;flex-direction:row}
#db .kayip-kart:hover{opacity:.84}
#db .kayip-kart .kk-cubuk{width:5px;flex-shrink:0}
#db .kayip-kart .kk-ic{padding:13px;flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between}
#db .kayip-kart .kk-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
#db .kayip-kart .kk-ikon{border-radius:10px;display:flex;align-items:center;justify-content:center;width:34px;height:34px;flex-shrink:0}
#db .kayip-kart .kk-ikon .material-icons-outlined{font-size:19px}
#db .kayip-kart .kk-badge{border-radius:9px;padding:3px 8px;font-size:11.5px;font-weight:700}
#db .kayip-kart .kk-deger{font-size:26px;font-weight:800;line-height:1;margin-bottom:2px}
#db .kayip-kart .kk-ad{font-size:12.5px;font-weight:500;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
/* kritik: dolgun gradient */
#db .kayip-kart.kritik .kk-deger{color:#fff}
#db .kayip-kart.kritik .kk-ad{color:rgba(255,255,255,.85)}

/* ===== ALT SATIR: GRAFİK+SATIŞ (sol) + ÖDEME+SERVİS (sağ) ===== */
#db .alt-satir{display:grid;grid-template-columns:6fr 4fr;gap:16px;margin-bottom:16px}
#db .alt-sol{display:flex;flex-direction:column;gap:16px}
#db .alt-sag{display:flex;flex-direction:column;gap:12px}

/* ===== GRAFİK KARTI ===== */
#db .grafik-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:14px}
#db .grafik-kart .gk-baslik{font-size:13px;font-weight:700;color:var(--ink);margin-bottom:14px}
#db .grafik-cubuklar{display:flex;align-items:flex-end;gap:0;height:120px}
#db .grafik-cubuklar .gc-kolon{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;padding:0 3px}
#db .grafik-cubuklar .gc-deger{font-size:8px;color:var(--sub);margin-bottom:2px;text-align:center}
#db .grafik-cubuklar .gc-bar{width:100%;border-radius:4px;background:linear-gradient(to bottom,var(--mor2),var(--mor1));min-height:3px;transition:height .6s cubic-bezier(.22,.68,0,1.2)}
#db .grafik-cubuklar .gc-gun{font-size:8px;color:var(--sub);margin-top:4px;text-align:center}

/* ===== SATIŞ&MALİYET KARTI ===== */
#db .satis-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:14px}
#db .satis-kart .sk-baslik-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
#db .satis-kart .sk-baslik{font-size:13px;font-weight:700;color:var(--ink)}
#db .satis-kart .sk-alt{font-size:10px;color:var(--sub)}
#db .urun-satir{display:flex;align-items:center;gap:10px;padding:6px 0;border-top:1px solid var(--line);cursor:pointer;transition:opacity .1s}
#db .urun-satir:hover{opacity:.75}
#db .urun-satir:first-of-type{border-top:none}
#db .us-adet{width:40px;background:#334155;border-radius:6px;text-align:center;color:#fff;font-size:11.5px;font-weight:700;padding:2px 0;flex-shrink:0}
#db .us-ad{flex:1;font-size:12px;color:var(--sub2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#db .us-sag{text-align:right;flex-shrink:0}
#db .us-sag .us-satis{font-size:12px;font-weight:700;color:var(--ink)}
#db .us-sag .us-mlt{font-size:9px;color:var(--sub)}
#db .us-yuzde{width:38px;border-radius:6px;text-align:center;font-size:10px;font-weight:700;padding:3px 0;flex-shrink:0}

/* ===== DAĞILIM KARTLARI ===== */
#db .dagilim-kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:14px}
#db .dagilim-kart .dk-baslik{font-size:13px;font-weight:700;color:var(--ink);margin-bottom:10px}
#db .dagilim-satir{margin-bottom:9px}
#db .dagilim-satir:last-child{margin-bottom:0}
#db .dagilim-satir .ds-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:4px}
#db .dagilim-satir .ds-ad{font-size:11px;font-weight:600;color:var(--sub2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-transform:uppercase}
#db .dagilim-satir .ds-tutar{font-size:11px;font-weight:700;color:var(--ink)}
#db .dagilim-satir .ds-bar{height:5px;border-radius:4px;background:var(--card2);overflow:hidden;margin-bottom:2px}
#db .dagilim-satir .ds-bar div{height:100%;border-radius:4px;background:var(--mor2);transition:width .6s ease}
#db .dagilim-satir .ds-islem{font-size:9px;color:var(--sub)}

/* ===== FOOTER ===== */
#db .db-footer{text-align:center;font-size:11px;color:var(--sub);padding-top:6px}

/* ===== YUKLE GÖSTERGESİ ===== */
#db .yukleniyor-bar{height:2px;background:linear-gradient(90deg,var(--mor2),var(--mavi));position:fixed;top:0;left:0;right:0;z-index:9999;display:none}

/* ===== RESPONSIVE: dar ekran ===== */
@media(max-width:1100px){#db .kayip-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:860px){
  #db .uclu-ciro,#db .hizli-bilgi{grid-template-columns:1fr 1fr}
  #db .masa-pos-row,#db .alt-satir{grid-template-columns:1fr}
  #db .kayip-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:540px){
  #db .uclu-ciro,#db .hizli-bilgi,#db .kayip-grid{grid-template-columns:1fr}
  #db .pano{padding:12px 10px 24px}
}
</style>
@endpush

@section('content')
@php
/* Sunucudan gelen temel veriler (web route → view) */
$saat = now()->hour;
$selamStr = $saat < 6 ? 'İyi geceler' : ($saat < 12 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar'));
$selamIkon = ($saat < 6 || $saat >= 19) ? 'nightlight_round' : 'wb_sunny';
/* Para formatı */
$para = fn($v) => number_format((float)$v, 0, ',', '.') . ' ₺';
/* Adisyon ortalama: ort-adisyon zaten geçiyor ama günlük için bugunCiro kullan */
$bugunYuzde = $dunCiro > 0 ? round(($bugunCiro - $dunCiro) / $dunCiro * 100, 1) : null;
@endphp

<div id="db">
<div class="yukleniyor-bar" id="yukBar"></div>
<div class="pano">

  {{-- ===== SELAM KARTI ===== --}}
  <div class="selam-kart">
    <div class="selam-ikon"><span class="material-icons-outlined">{{ $selamIkon }}</span></div>
    <div style="flex:1;min-width:0">
      <div class="ad" id="selamAd">{{ $selamStr }}!</div>
      <div class="sube">{{ $sube->ad }}</div>
    </div>
    <a href="/menu-onizle" target="_blank" class="selam-cip" style="background:rgba(244,63,94,.12);color:#F43F5E">
      <span class="material-icons-outlined">qr_code_2</span> QR Menü
    </a>
    <a href="/rezervasyonlar" class="selam-cip" style="background:rgba(236,72,153,.12);color:#EC4899;margin-left:7px">
      <span class="material-icons-outlined">event_available</span> Rezervasyon
    </a>
    {{-- Dönem seçici selam kartının sağında --}}
    <div class="donem-wrap" style="max-width:340px;margin-left:16px" id="donemWrap">
      <button class="donem-btn" data-p="gunluk" onclick="donemSec('gunluk')">Günlük</button>
      <button class="donem-btn akt" data-p="haftalik" onclick="donemSec('haftalik')">Haftalık</button>
      <button class="donem-btn" data-p="aylik" onclick="donemSec('aylik')">Aylık</button>
      <button class="donem-btn" data-p="yillik" onclick="donemSec('yillik')">Yıllık</button>
    </div>
  </div>

  {{-- ===== 3 CİRO KARTI (Bugün/Hafta/Ay — sabit, dönemden bağımsız) ===== --}}
  <div class="uclu-ciro" id="ucluCiro">
    {{-- Bugün --}}
    <div class="ciro-mini">
      <div class="cubuk" style="background:#F43F5E"></div>
      <div class="ic">
        <div class="ciro-baslik-row">
          <div class="ciro-ikon" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:#F43F5E">today</span></div>
          <div class="ciro-label">Bugünkü Ciro</div>
        </div>
        <div class="ciro-deger" id="cBugun">{{ $para($bugunCiro) }}</div>
        @if($bugunYuzde !== null)
        <div class="ciro-trend" style="color:{{ $bugunYuzde >= 0 ? '#10B981' : '#F43F5E' }}">
          <span class="material-icons-outlined">{{ $bugunYuzde >= 0 ? 'trending_up' : 'trending_down' }}</span>
          %{{ abs($bugunYuzde) }} düne göre
        </div>
        @else
        <div class="ciro-trend" style="color:var(--sub)">kıyas yok</div>
        @endif
      </div>
    </div>
    {{-- Hafta --}}
    <div class="ciro-mini" id="ciroHafta">
      <div class="cubuk" style="background:#4F46E5"></div>
      <div class="ic">
        <div class="ciro-baslik-row">
          <div class="ciro-ikon" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:#4F46E5">date_range</span></div>
          <div class="ciro-label">Haftalık Ciro</div>
        </div>
        <div class="ciro-deger" id="cHafta">—</div>
        <div class="ciro-trend" id="cHaftaTrend" style="color:var(--sub)">yükleniyor...</div>
      </div>
    </div>
    {{-- Ay --}}
    <div class="ciro-mini" id="ciroAy">
      <div class="cubuk" style="background:#7C3AED"></div>
      <div class="ic">
        <div class="ciro-baslik-row">
          <div class="ciro-ikon" style="background:rgba(124,58,237,.14)"><span class="material-icons-outlined" style="color:#7C3AED">calendar_month</span></div>
          <div class="ciro-label">Bu Ay Cirosu</div>
        </div>
        <div class="ciro-deger" id="cAy">—</div>
        <div class="ciro-trend" id="cAyTrend" style="color:var(--sub)">yükleniyor...</div>
      </div>
    </div>
  </div>

  {{-- ===== HIZLI BİLGİ (Brüt Kâr / Ort. Adisyon / Food-Cost / En Çok Satan) ===== --}}
  <div class="hizli-bilgi" id="hizliBilgi">
    <div class="bilgi-kart">
      <div class="bk-row">
        <div class="bk-ikon" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" style="color:#10B981">savings</span></div>
        <div class="bk-label">Brüt Kâr</div>
      </div>
      <div class="bk-deger" id="brutKar">—</div>
      <div class="bk-alt">food-cost sonrası</div>
    </div>
    <div class="bilgi-kart">
      <div class="bk-row">
        <div class="bk-ikon" style="background:rgba(79,70,229,.14)"><span class="material-icons-outlined" style="color:#4F46E5">receipt_long</span></div>
        <div class="bk-label">Ort. Adisyon</div>
      </div>
      <div class="bk-deger" id="ortAdisyon">—</div>
      <div class="bk-alt">adisyon başı</div>
    </div>
    <div class="bilgi-kart">
      <div class="bk-row">
        <div class="bk-ikon" id="fcIkon" style="background:rgba(16,185,129,.14)"><span class="material-icons-outlined" id="fcIkonRenk" style="color:#10B981">local_fire_department</span></div>
        <div class="bk-label">Food-Cost</div>
      </div>
      <div class="bk-deger" id="foodCost">—</div>
      <div class="bk-alt" id="foodCostMlt">—</div>
    </div>
    <div class="bilgi-kart">
      <div class="bk-row">
        <div class="bk-ikon" style="background:rgba(124,58,237,.14)"><span class="material-icons-outlined" style="color:#7C3AED">star</span></div>
        <div class="bk-label">En Çok Satan</div>
      </div>
      <div class="bk-deger" id="enCok" style="font-size:17px">—</div>
      <div class="bk-alt" id="enCokAlt">satış yok</div>
    </div>
  </div>

  {{-- ===== MASA DURUMU (sol) + POS (sağ) ===== --}}
  <div class="masa-pos-row">
    {{-- MASA DURUMU --}}
    <div class="masa-kart">
      <div class="kart-baslik">
        <div class="kb-ikon" style="background:rgba(124,58,237,.14)"><span class="material-icons-outlined" style="color:#7C3AED">table_restaurant</span></div>
        <div class="kb-ad">Masa Durumu</div>
      </div>
      <div class="masa-sayaclar">
        <div class="masa-sayac" style="background:rgba(249,115,22,.12);border:1px solid rgba(249,115,22,.22)">
          <div class="ms-label" style="color:#F97316"><span class="material-icons-outlined">grid_view</span>Toplam</div>
          <div class="ms-deger" id="masaToplam" style="color:#F97316">{{ $masaSayisi }}</div>
        </div>
        <div class="masa-sayac" style="background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.22)">
          <div class="ms-label" style="color:#10B981"><span class="material-icons-outlined">check_circle</span>Müsait</div>
          <div class="ms-deger" id="masaMusait" style="color:#10B981">{{ max(0, $masaSayisi - $acikMasaSayisi) }}</div>
        </div>
        <div class="masa-sayac" style="background:rgba(244,63,94,.12);border:1px solid rgba(244,63,94,.22)">
          <div class="ms-label" style="color:#F43F5E"><span class="material-icons-outlined">circle</span>Dolu</div>
          <div class="ms-deger" id="masaDolu" style="color:#F43F5E">{{ $acikMasaSayisi }}</div>
        </div>
      </div>
      @php
        $doluluk = $masaSayisi > 0 ? min(100, (int)round($acikMasaSayisi / $masaSayisi * 100)) : 0;
        $yogunRenk = $doluluk >= 70 ? '#F43F5E' : ($doluluk >= 35 ? '#F59E0B' : '#10B981');
        $yogunlukAd = $doluluk >= 70 ? 'YOĞUN' : ($doluluk >= 35 ? 'NORMAL' : 'SAKİN');
      @endphp
      <div class="masa-doluluk-row">
        <div class="yuzde" id="masaDolulukYuzde">%{{ $doluluk }}</div>
        <div class="yogunluk-cip" id="yogunlukCip" style="background:{{ $yogunRenk }}22;color:{{ $yogunRenk }}">
          <span class="material-icons-outlined">trending_up</span>
          <span id="yogunlukAd">{{ $yogunlukAd }}</span>
        </div>
        <div style="flex:1"></div>
        <div style="font-size:11px;color:var(--sub);padding-bottom:4px" id="masaAciklamaText">{{ $masaSayisi }} masanın {{ $acikMasaSayisi }}'i dolu</div>
      </div>
      <div style="font-size:11px;color:var(--sub)">Doluluk</div>
      <div class="doluluk-bar"><div id="dolulukBar" style="width:{{ $doluluk }}%;background:{{ $yogunRenk }}"></div></div>
      <div class="bekleyen-baslik">Bekleyen Masalar</div>
      <div id="bekleyenListe">
        @forelse($acikAdisyonlar as $a)
          @php
            $dk = $a->acilis ? (int)\Carbon\Carbon::parse($a->acilis)->diffInMinutes() : 0;
            $bRenk = $dk >= 45 ? '#F43F5E' : ($dk >= 25 ? '#F59E0B' : '#10B981');
            $sure = $dk >= 1440 ? (intdiv($dk,1440).' gün') : ($dk >= 60 ? intdiv($dk,60).' sa '.($dk%60).' dk' : $dk.' dk');
          @endphp
          <div class="bekleyen-satir">
            <div class="bek-nokta" style="background:{{ $bRenk }}"></div>
            <div class="bek-ad">Masa {{ $a->masa }}</div>
            <div class="bek-sure" style="color:{{ $bRenk }}">{{ $sure }}</div>
          </div>
        @empty
          <div style="font-size:12px;color:var(--sub);padding:6px 0">Bekleyen masa yok</div>
        @endforelse
      </div>
    </div>

    {{-- POS KARTI --}}
    <div class="pos-kart">
      <div class="kart-baslik">
        <div class="kb-ikon" style="background:rgba(244,63,94,.14)"><span class="material-icons-outlined" style="color:#F43F5E">point_of_sale</span></div>
        <div class="kb-ad">POS</div>
      </div>
      {{-- Toplam Tahsilat (dönem ciro) --}}
      <div class="pos-tahsilat">
        <div class="pt-sol">
          <div class="pt-label">Toplam Tahsilat</div>
          <div class="pt-deger" id="posTahsilat">—</div>
        </div>
        <div id="posYuzdeBadge" style="display:none" class="pt-badge">
          <span class="material-icons-outlined" id="posYuzdeIk">trending_up</span>
          <span id="posYuzdeDeger">—</span>
        </div>
      </div>
      {{-- 4 mini kart --}}
      <div class="pos-mini-grid">
        <a href="/pos" class="pos-mini" style="background:rgba(245,158,11,.10);border-color:rgba(245,158,11,.22)">
          <div class="pm-label">Açık Adisyon</div>
          <div class="pm-row">
            <div class="pm-buyuk" style="color:#F59E0B" id="acikAdet">{{ $acikMasaSayisi }}</div>
            <div class="pm-alt" id="acikTutar">{{ $para($acikTutar) }}</div>
          </div>
        </a>
        <div class="pos-mini" id="kapaliKart" style="background:rgba(16,185,129,.10);border-color:rgba(16,185,129,.22);cursor:pointer" onclick="drillDown('kapali','Kapanan Adisyonlar')">
          <div class="pm-label">Kapalı Adisyon</div>
          <div class="pm-row">
            <div class="pm-buyuk" style="color:#10B981" id="kapaliAdet">—</div>
            <div class="pm-alt" id="kapaliTutar">—</div>
          </div>
        </div>
        <div class="pos-mini" style="background:rgba(79,70,229,.10);border-color:rgba(79,70,229,.22)">
          <div class="pm-label">Kişi Sayısı</div>
          <div class="pm-row">
            <div class="pm-buyuk" style="color:#4F46E5" id="kisiSayisi">—</div>
            <div class="pm-alt" id="kisiBasiTutar">—</div>
          </div>
        </div>
        <div class="pos-mini" style="background:rgba(236,72,153,.10);border-color:rgba(236,72,153,.22);cursor:pointer" onclick="drillDown('kapali','Adisyonlar')">
          <div class="pm-label">Toplam Adisyon</div>
          <div class="pm-row">
            <div class="pm-buyuk" style="color:#EC4899" id="toplamAdisyon">—</div>
            <div class="pm-alt" id="adisyonOrt">—</div>
          </div>
        </div>
      </div>
      {{-- Servis Türüne Göre Ciro --}}
      <div class="servis-baslik">Servis Türüne Göre Ciro</div>
      <div id="servisBarlar">
        <div style="font-size:12px;color:var(--sub)">Yükleniyor...</div>
      </div>
    </div>
  </div>

  {{-- ===== MALİYET (FOOD-COST) KARTI ===== --}}
  <div class="maliyet-kart" onclick="drillDown('maliyet','Food-Cost')">
    <div class="mk-sol">
      <div class="mk-label">Toplam Maliyet (Food-Cost)</div>
      <div class="mk-deger" id="maliyetDeger">—</div>
      <div class="mk-alt" id="maliyetAlt">Brüt kâr: —</div>
    </div>
    <div class="maliyet-halka">
      <svg width="66" height="66" viewBox="0 0 66 66">
        <circle cx="33" cy="33" r="27" fill="none" stroke="var(--card2)" stroke-width="7"/>
        <circle cx="33" cy="33" r="27" fill="none" id="halkaIz" stroke="#10B981" stroke-width="7"
          stroke-linecap="round" stroke-dasharray="169.6" stroke-dashoffset="169.6" style="transition:stroke-dashoffset .9s cubic-bezier(.22,.68,0,1.2)"/>
      </svg>
      <div class="mh-yuzde" id="halkaYuzde" style="color:#10B981">—</div>
    </div>
  </div>

  {{-- ===== KAYIP RADARI ===== --}}
  <div class="kayip-baslik">
    <div class="kb-t">🎯 Kayıp Radarı</div>
    <div class="kb-a">Ciroya oranla — sızıntı takibi</div>
  </div>
  <div class="kayip-grid" id="kayipGrid">
    <div style="grid-column:1/-1;font-size:13px;color:var(--sub);padding:12px 0">Yükleniyor...</div>
  </div>

  {{-- ===== ALT SATIR: Grafik + Satış&Maliyet (sol) | Ödeme + Servis Tipi (sağ) ===== --}}
  <div class="alt-satir">
    <div class="alt-sol">
      {{-- Grafik --}}
      <div class="grafik-kart">
        <div class="gk-baslik">📈 Son 10 Gün</div>
        <div class="grafik-cubuklar" id="grafikCubuklar">
          @php $maxTrend = max(1, max(array_column($trend, 'ciro'))); @endphp
          @foreach($trend as $t)
            @php
              $h = max(3, (int)($t['ciro'] / $maxTrend * 96));
              $degerKisa = $t['ciro'] >= 1000 ? round($t['ciro']/1000).'K' : (int)$t['ciro'];
            @endphp
            <div class="gc-kolon">
              <div class="gc-deger">{{ $degerKisa }}</div>
              <div class="gc-bar" style="height:{{ $h }}px" title="{{ number_format((float)$t['ciro'],0,',','.') }} ₺"></div>
              <div class="gc-gun">{{ $t['gun'] }}</div>
            </div>
          @endforeach
        </div>
      </div>
      {{-- Ürün Satış & Maliyet --}}
      <div class="satis-kart">
        <div class="sk-baslik-row">
          <div class="sk-baslik">🧾 Ürün Satış &amp; Maliyet</div>
          <div class="sk-alt">maliyet %</div>
        </div>
        <div id="urunSatirlar">
          <div style="font-size:12px;color:var(--sub);padding:8px 0">Yükleniyor...</div>
        </div>
      </div>
    </div>
    <div class="alt-sag">
      {{-- Ödeme Tipi --}}
      <div class="dagilim-kart">
        <div class="dk-baslik">💳 Ödeme Tipi</div>
        <div id="odemeDagilim">
          <div style="font-size:12px;color:var(--sub)">Yükleniyor...</div>
        </div>
      </div>
      {{-- Servis Tipi --}}
      <div class="dagilim-kart">
        <div class="dk-baslik">🍽️ Servis Tipi</div>
        <div id="servisDagilim">
          <div style="font-size:12px;color:var(--sub)">Yükleniyor...</div>
        </div>
      </div>
    </div>
  </div>

  <div class="db-footer">Tek bakışta, anlık ve doğru. · ResteOS</div>
</div>{{-- .pano --}}
</div>{{-- #db --}}

<script>
(function(){
'use strict';

/* ===== YARDIMCI FONKSİYONLAR ===== */
var period = localStorage.getItem('db_period') || 'haftalik';

function para(v){
  v = parseFloat(v)||0;
  return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(Math.round(v))+' ₺';
}
function kisaPara(v){
  var a = Math.abs(v);
  if(a>=1000000) return (v/1000000).toFixed(2)+'M ₺';
  if(a>=1000) return (v/1000).toFixed(2)+'K ₺';
  return para(v);
}
function el(id){ return document.getElementById(id); }
function txt(id,val){ var e=el(id); if(e) e.textContent=val; }
function html(id,val){ var e=el(id); if(e) e.innerHTML=val; }
function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])}); }

/* ===== DÖNEM SEÇİCİ ===== */
function donemSec(p){
  period = p;
  try{ localStorage.setItem('db_period',p); }catch(e){}
  document.querySelectorAll('#donemWrap .donem-btn').forEach(function(b){
    b.classList.toggle('akt', b.getAttribute('data-p')===p);
  });
  yukle();
}
window.donemSec = donemSec;

/* Başlangıçta kayıtlı dönemi uygula */
(function(){
  document.querySelectorAll('#donemWrap .donem-btn').forEach(function(b){
    b.classList.toggle('akt', b.getAttribute('data-p')===period);
  });
})();

/* ===== DRILL-DOWN (basit: /patron/detay sayfasına git) ===== */
function drillDown(tip, baslik){
  window.location.href = '/patron/detay?tip='+encodeURIComponent(tip)+'&period='+period;
}
window.drillDown = drillDown;

/* ===== KAYIP KARTI HTML ÜRETICI ===== */
var kayipRenkler = {
  'İskonto':            {renk:'#F59E0B',ikon:'local_offer'},
  'İkram':              {renk:'#8B5CF6',ikon:'card_giftcard'},
  'Silinen Ürün':       {renk:'#EF4444',ikon:'remove_circle'},
  'İptal Adisyon':      {renk:'#EC4899',ikon:'delete'},
  'Fire / Zayi':        {renk:'#F97316',ikon:'delete_sweep'},
  'Tahsil Edilemeyen':  {renk:'#F43F5E',ikon:'money_off'},
};
var kayipSiralama = [
  {key:'iskonto',  ad:'İskonto'},
  {key:'ikram',    ad:'İkram'},
  {key:'silinen',  ad:'Silinen Ürün'},
  {key:'iptal',    ad:'İptal Adisyon'},
  {key:'fire',     ad:'Fire / Zayi'},
  {key:'odenmez',  ad:'Tahsil Edilemeyen'},
];

function kayipKartHTML(ad, k){
  var m = k || {};
  var tutar = parseFloat(m.tutar)||0;
  var yuzde = parseFloat(m.yuzde)||0;
  var adet  = m.adet != null ? parseInt(m.adet) : null;
  var kritik = yuzde >= 5 || (ad === 'İskonto' && yuzde >= 3);
  var r = kayipRenkler[ad] || {renk:'#7C3AED',ikon:'warning'};
  var renk = r.renk;
  var yuzdeStr = '%' + (yuzde%1===0 ? Math.round(yuzde) : yuzde.toFixed(1).replace('.',','));

  if(kritik){
    var darkRenk = renk; // gradient via CSS filter
    return '<div class="kayip-kart kritik" style="background:linear-gradient(135deg,'+renk+','+renk+'bb)" onclick="drillDown(\'kayip\',\''+esc(ad)+'\')">'+
      '<div class="kk-cubuk" style="background:rgba(255,255,255,.35)"></div>'+
      '<div class="kk-ic">'+
      '<div class="kk-top">'+
        '<div class="kk-ikon" style="background:rgba(255,255,255,.22)"><span class="material-icons-outlined" style="color:#fff">'+esc(r.ikon)+'</span></div>'+
        '<div class="kk-badge" style="background:rgba(255,255,255,.22);color:#fff">'+esc(yuzdeStr)+'</div>'+
      '</div>'+
      '<div class="kk-deger">'+esc(kisaPara(tutar))+'</div>'+
      '<div class="kk-ad" style="color:rgba(255,255,255,.85)">'+(adet!=null ? esc(ad)+' · '+adet+' adet' : esc(ad))+'</div>'+
      '</div></div>';
  }
  return '<div class="kayip-kart" style="background:var(--card);border:1px solid var(--card2)" onclick="drillDown(\'kayip\',\''+esc(ad)+'\')">'+
    '<div class="kk-cubuk" style="background:'+renk+'"></div>'+
    '<div class="kk-ic">'+
    '<div class="kk-top">'+
      '<div class="kk-ikon" style="background:'+renk+'22"><span class="material-icons-outlined" style="color:'+renk+'">'+esc(r.ikon)+'</span></div>'+
      '<div class="kk-badge" style="background:'+renk+'22;color:'+renk+'">'+esc(yuzdeStr)+'</div>'+
    '</div>'+
    '<div class="kk-deger" style="color:var(--ink)">'+esc(kisaPara(tutar))+'</div>'+
    '<div class="kk-ad">'+(adet!=null ? esc(ad)+' · '+adet+' adet' : esc(ad))+'</div>'+
    '</div></div>';
}

/* ===== DAĞILIM SATIR HTML ===== */
function dagilimHTML(liste, adKey, toplamCiro){
  if(!liste || !liste.length) return '<div style="font-size:12px;color:var(--sub)">Kayıt yok</div>';
  var toplam = liste.reduce(function(a,e){return a+(parseFloat(e.tutar)||0);},0);
  return liste.slice(0,4).map(function(e){
    var ad = e[adKey] || '-';
    var tutar = parseFloat(e.tutar)||0;
    var adet  = parseInt(e.adet)||0;
    var oran  = toplam > 0 ? Math.min(100, tutar/toplam*100).toFixed(1) : 0;
    return '<div class="dagilim-satir">'+
      '<div class="ds-row"><div class="ds-ad">'+esc(ad)+'</div><div class="ds-tutar">'+esc(kisaPara(tutar))+'</div></div>'+
      '<div class="ds-bar"><div style="width:'+oran+'%"></div></div>'+
      '<div class="ds-islem">'+adet+' işlem</div>'+
      '</div>';
  }).join('');
}

/* ===== SERVIS BAR HTML ===== */
function servisBarHTML(servis){
  if(!servis || !servis.length) return '<div style="font-size:12px;color:var(--sub)">Bu dönemde satış yok.</div>';
  var maks = servis.reduce(function(a,s){return Math.max(a,parseFloat(s.tutar)||0);},1);
  return servis.map(function(s){
    var oran = maks > 0 ? Math.min(100, (parseFloat(s.tutar)||0)/maks*100).toFixed(1) : 0;
    return '<div class="servis-bar-satir">'+
      '<div class="sb-ad">'+esc(s.ad||'')+'</div>'+
      '<div class="servis-bar-track"><div style="width:'+oran+'%"></div></div>'+
      '<div class="sb-tutar">'+esc(para(parseFloat(s.tutar)||0))+'</div>'+
      '</div>';
  }).join('');
}

/* ===== ÜRÜN SATIR HTML ===== */
function urunSatirHTML(urunler){
  if(!urunler || !urunler.length) return '<div style="font-size:12px;color:var(--sub);padding:8px 0">Bu dönemde satış yok.</div>';
  return urunler.slice(0,15).map(function(u){
    var yuzde = parseInt(u.yuzde)||0;
    var renk  = yuzde>=35?'#F43F5E':(yuzde>=25?'#F59E0B':'#10B981');
    return '<div class="urun-satir" onclick="drillDown(\'urun\','+esc(u.ad)+')" title="'+esc(u.ad)+'">'+
      '<div class="us-adet">'+parseInt(u.adet)+'×</div>'+
      '<div class="us-ad">'+esc(u.ad)+'</div>'+
      '<div class="us-sag"><div class="us-satis">'+esc(kisaPara(parseFloat(u.satis)||0))+'</div><div class="us-mlt">mlt '+esc(kisaPara(parseFloat(u.maliyet)||0))+'</div></div>'+
      '<div class="us-yuzde" style="background:'+renk+'22;color:'+renk+'">%'+yuzde+'</div>'+
      '</div>';
  }).join('');
}

/* ===== HALKA GÜNCELLEMESI ===== */
function halkaGuncelle(yuzde){
  var renk = yuzde>=40?'#F43F5E':(yuzde>=30?'#F59E0B':'#10B981');
  var cember = 2*Math.PI*27; // 169.6
  var offset = cember*(1-Math.min(1,yuzde/100));
  var izEl = el('halkaIz');
  var yzEl = el('halkaYuzde');
  if(izEl){ izEl.setAttribute('stroke',renk); izEl.style.strokeDashoffset=offset; }
  if(yzEl){ yzEl.textContent='%'+Math.round(yuzde); yzEl.style.color=renk; }
  // food-cost kart ikon rengi
  var fcI = el('fcIkon'); var fcR = el('fcIkonRenk');
  if(fcI) fcI.style.background='rgba('+hexToRgb(renk)+',.14)';
  if(fcR) fcR.style.color=renk;
}
function hexToRgb(hex){
  var r=parseInt(hex.slice(1,3),16),g=parseInt(hex.slice(3,5),16),b=parseInt(hex.slice(5,7),16);
  return r+','+g+','+b;
}

/* ===== ANA YÜKLEME ===== */
var yukBar = el('yukBar');
function yukle(){
  if(yukBar) yukBar.style.display='block';
  fetch('/api/patron/ozet?period='+period, {
    headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
  })
  .then(function(r){return r.json();})
  .then(function(d){
    if(d.ok!==1){ console.warn('ozet hata',d); if(yukBar) yukBar.style.display='none'; return; }
    var ciro = parseFloat(d.ciro)||0;
    var maliyet = parseFloat(d.maliyet)||0;
    var maliyetY = parseInt(d.maliyetYuzde)||0;
    var kayip = d.kayip||{};
    var info = d.info||{};
    var comp = d.comp||{};
    var kartlar = d.kartlar||{};

    /* Patron adı */
    if(d.patronAd){ txt('selamAd','{{ $selamStr }}, '+d.patronAd+'!'); }

    /* 3 Ciro kartı */
    if(kartlar.bugun){ txt('cBugun', para(kartlar.bugun.ciro)); }
    if(kartlar.hafta){
      txt('cHafta', para(kartlar.hafta.ciro));
      var hy = kartlar.hafta.yuzde;
      el('cHaftaTrend').innerHTML = hy!=null
        ? '<span class="material-icons-outlined" style="font-size:14px;color:'+(hy>=0?'#10B981':'#F43F5E')+'">trending_'+(hy>=0?'up':'down')+'</span>'
          +'<span style="color:'+(hy>=0?'#10B981':'#F43F5E')+'">%'+Math.abs(hy).toFixed(1).replace('.',',')+'</span> geçen haftaya'
        : '<span style="color:var(--sub)">kıyas yok</span>';
    }
    if(kartlar.ay){
      txt('cAy', para(kartlar.ay.ciro));
      var ay = kartlar.ay.yuzde;
      el('cAyTrend').innerHTML = ay!=null
        ? '<span class="material-icons-outlined" style="font-size:14px;color:'+(ay>=0?'#10B981':'#F43F5E')+'">trending_'+(ay>=0?'up':'down')+'</span>'
          +'<span style="color:'+(ay>=0?'#10B981':'#F43F5E')+'">%'+Math.abs(ay).toFixed(1).replace('.',',')+'</span> geçen aya'
        : '<span style="color:var(--sub)">kıyas yok</span>';
    }

    /* Hızlı bilgi kartları */
    var brutKar = ciro - maliyet;
    txt('brutKar', kisaPara(brutKar));
    txt('ortAdisyon', para(parseFloat(d.adisyonOrt)||0));
    txt('foodCost', '%'+maliyetY);
    txt('foodCostMlt', kisaPara(maliyet));
    halkaGuncelle(maliyetY);

    var urunler = d.urunler||[];
    if(urunler.length){
      var en = urunler[0];
      txt('enCok', en.ad||'—');
      txt('enCokAlt', parseInt(en.adet)+'× · '+kisaPara(parseFloat(en.satis)||0));
    } else {
      txt('enCok','—'); txt('enCokAlt','satış yok');
    }

    /* Masa durumu (API'dan taze ver, ya da yükle) */
    var masa = d.masa||{};
    if(masa.toplam!==undefined){
      txt('masaToplam', masa.toplam);
      txt('masaMusait', masa.musait);
      txt('masaDolu', masa.dolu);
      var dl = parseInt(masa.doluluk)||0;
      var yRenk = dl>=70?'#F43F5E':(dl>=35?'#F59E0B':'#10B981');
      txt('masaDolulukYuzde','%'+dl);
      el('yogunlukCip').style.background=yRenk+'22'; el('yogunlukCip').style.color=yRenk;
      txt('yogunlukAd', masa.yogunluk||'');
      txt('masaAciklamaText', masa.toplam+" masanın "+masa.dolu+"'i dolu");
      var barEl = el('dolulukBar'); if(barEl){barEl.style.width=dl+'%'; barEl.style.background=yRenk;}
      /* Bekleyen masalar (API'dan) */
      var bekleyen = masa.bekleyen||[];
      if(bekleyen.length){
        html('bekleyenListe', bekleyen.map(function(b){
          var dk=parseInt(b.dk)||0;
          var br=dk>=45?'#F43F5E':(dk>=25?'#F59E0B':'#10B981');
          var sure=dk>=1440?(Math.floor(dk/1440)+' gün'):(dk>=60?(Math.floor(dk/60)+' sa '+(dk%60)+' dk'):(dk+' dk'));
          return '<div class="bekleyen-satir">'+
            '<div class="bek-nokta" style="background:'+br+'"></div>'+
            '<div class="bek-ad">'+esc(b.ad)+'</div>'+
            '<div class="bek-sure" style="color:'+br+'">'+esc(sure)+'</div>'+
            '</div>';
        }).join(''));
      }
    }

    /* POS Tahsilat */
    txt('posTahsilat', para(ciro));
    var cy = d.ciroYuzde;
    if(cy!=null){
      var pBadge=el('posYuzdeBadge'),pIk=el('posYuzdeIk'),pDeg=el('posYuzdeDeger');
      if(pBadge) pBadge.style.display='inline-flex';
      if(pIk) pIk.textContent=cy>=0?'trending_up':'trending_down';
      if(pDeg) pDeg.textContent='%'+Math.abs(cy).toFixed(1).replace('.',',');
    }
    txt('acikAdet', parseInt(d.acikAdet)||0);
    txt('acikTutar', kisaPara(parseFloat(d.acikTutar)||0));
    txt('kapaliAdet', parseInt(d.kapaliAdet)||0);
    txt('kapaliTutar', kisaPara(parseFloat(d.kapaliTutar)||0));
    txt('kisiSayisi', parseInt((info.misafir)||0));
    txt('kisiBasiTutar', para(parseFloat(info.kisi_basi)||0));
    txt('toplamAdisyon', parseInt(d.toplamAdisyon)||0);
    txt('adisyonOrt', para(parseFloat(d.adisyonOrt)||0));

    /* Servis barları (POS içi) */
    html('servisBarlar', servisBarHTML(d.servis||[]));

    /* Maliyet kartı */
    txt('maliyetDeger', para(maliyet));
    txt('maliyetAlt', 'Brüt kâr: '+para(brutKar));

    /* Kayıp Radarı */
    html('kayipGrid', kayipSiralama.map(function(ks){
      return kayipKartHTML(ks.ad, kayip[ks.key]);
    }).join(''));

    /* Grafik — sadece API'dan gelen gunluk varsa güncelle (yoksa sunucu render korunur) */
    if(d.gunluk && d.gunluk.length){
      var gl = d.gunluk;
      var maks = gl.reduce(function(a,g){return Math.max(a,parseFloat(g.ciro)||0);},1);
      html('grafikCubuklar', gl.map(function(g,i){
        var v=parseFloat(g.ciro)||0;
        var h=Math.max(3,Math.round(v/maks*96));
        var dk=v>=1000?Math.round(v/1000)+'K':Math.round(v);
        return '<div class="gc-kolon"><div class="gc-deger">'+dk+'</div>'+
          '<div class="gc-bar" style="height:'+h+'px" title="'+para(v)+'"></div>'+
          '<div class="gc-gun">'+esc(g.gun)+'</div></div>';
      }).join(''));
    }

    /* Ürün Satış & Maliyet */
    html('urunSatirlar', urunSatirHTML(urunler));

    /* Ödeme & Servis Tipi Dağılımı */
    html('odemeDagilim', dagilimHTML(d.odemeTipleri||[], 'tip', ciro));
    html('servisDagilim', dagilimHTML(d.servisTipleri||[], 'ad', ciro));

    if(yukBar) yukBar.style.display='none';
  })
  .catch(function(e){
    console.error('dashboard yukle hata', e);
    if(yukBar) yukBar.style.display='none';
  });
}

/* İlk yükleme */
yukle();

})();
</script>
@endsection

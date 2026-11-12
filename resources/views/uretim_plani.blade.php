@extends('layout.app')
@section('rk_ikon', 'calculate')
@section('baslik', 'Akıllı Üretim Planı')
@section('title', 'Üretim Planı')

@push('head')
<style>
#up{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #up{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#up{background:var(--bg);min-height:calc(100vh - 60px)}#up *{box-sizing:border-box}
#up .pano{max-width:1500px;margin:0 auto;padding:14px 16px 24px}
#up .material-icons-outlined{vertical-align:middle}
/* araç çubuğu */
#up .arac-cubuk{display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--card);border-radius:14px;padding:10px 12px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:12px}
#up .grp{display:flex;align-items:center;gap:6px}
#up .grp>span{font-size:11.5px;font-weight:700;color:var(--sub)}
#up .seg{display:inline-flex;background:var(--card2);border-radius:10px;padding:3px}
#up .seg button{border:0;background:none;padding:6px 12px;border-radius:8px;font-size:12.5px;font-weight:800;color:var(--sub);cursor:pointer}
#up .seg button.akt{background:var(--mavi);color:#fff}
#up .btn{border:0;border-radius:10px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
#up .btn .material-icons-outlined{font-size:18px}
#up .btn.ai{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 4px 12px color-mix(in srgb,var(--mavi) 30%,transparent)}
#up .btn.bos{background:var(--card2);color:var(--sub)}
#up .btn.yesil{background:var(--yesil);color:#fff}
#up .btn.cizgi{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#up .tahmin-bilgi{margin-left:auto;font-size:12px;color:var(--sub);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
#up .tahmin-bilgi b{color:var(--ink)}
#up .guven{white-space:nowrap;border-radius:20px;padding:2px 9px;font-weight:800;font-size:11px;color:#fff}
#up .guven.yuksek{background:#10B981}#up .guven.orta{background:#F59E0B}#up .guven.dusuk{background:#F43F5E}
#up .bilgi-i{border:0;background:none;color:var(--sub2);cursor:pointer;padding:0;display:inline-flex}
/* mod: günlük plan / özel gün */
#up .mod-seg button{display:inline-flex;align-items:center;gap:5px}
#up.ozel .mod-seg button.akt{background:linear-gradient(135deg,#EC4899,#F59E0B)}
#up .ayrac{width:1px;height:26px;background:var(--line)}
#up .sadece-ozel{display:none!important}
#up.ozel .sadece-ozel{display:flex!important}
#up.ozel .sadece-rutin{display:none!important}
#up .oz-inp{border:1px solid var(--line);border-radius:9px;padding:7px 10px;background:var(--card);color:var(--ink);font-size:13px;font-family:inherit}
#up .oz-chk{font-size:12.5px;font-weight:700;color:var(--ink);cursor:pointer;gap:6px}
#up .oz-chk input{width:16px;height:16px;accent-color:var(--mavi)}
#up .ozel-serit{display:none;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px;border-radius:12px;padding:10px 14px;font-size:12.5px;color:#9a3412;background:linear-gradient(135deg,#fdf2f8,#fff7ed);border:1px solid #fbcfe8}
body.koyu #up .ozel-serit{color:#fdba74;background:#2a1d2a;border-color:#4a2a42}
#up.ozel .ozel-serit{display:flex}
#up .ozel-serit b{color:inherit}
#up .menu-btn{border:1px dashed color-mix(in srgb,#EC4899 55%,var(--line));background:none;color:#db2777;border-radius:8px;padding:3px 8px;font-size:11.5px;font-weight:800;cursor:pointer;white-space:nowrap}
#up .menu-btn.var{border-style:solid;background:color-mix(in srgb,#EC4899 12%,transparent)}
#up .normal-ek{font-size:11px;font-weight:700;color:var(--sub);white-space:nowrap}
/* pencere butonları */
.up-modal .mbtn{border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:10px;padding:8px 13px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.up-modal .mbtn.yesil{background:#10B981;border-color:#10B981;color:#fff}
.up-modal .mbtn .material-icons-outlined{font-size:17px}
/* özel gün penceresi */
#ozModal .oz-govde{overflow-y:auto;padding:14px 18px;display:flex;flex-direction:column;gap:12px}
#ozModal .oz-izgara{display:grid;grid-template-columns:1fr 1fr;gap:10px}
#ozModal .oz-alan{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:800;color:var(--sub)}
#ozModal .oz-alan em{color:#F43F5E;font-style:normal}
#ozModal .oz-inp{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:14px;font-family:inherit;font-weight:600}
#ozModal .oz-inp:focus{outline:2px solid color-mix(in srgb,#EC4899 40%,transparent);border-color:#EC4899}
#ozModal .oz-inp.hata{border-color:#F43F5E;background:color-mix(in srgb,#F43F5E 6%,var(--card))}
#ozModal .oz-menu-bas{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:14px;color:var(--ink)}
#ozModal .oz-menu-bas span{font-size:12px;color:var(--sub);font-weight:700}
#ozModal .oz-menu{border:1px solid var(--line);border-radius:12px;max-height:300px;overflow-y:auto}
#ozModal .oz-sat{display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--line);cursor:pointer;font-size:13.5px;font-weight:700;color:var(--ink)}
#ozModal .oz-sat:last-child{border-bottom:0}
#ozModal .oz-sat:hover{background:var(--card2)}
#ozModal .oz-sat input[type=checkbox]{width:18px;height:18px;accent-color:#EC4899;flex:none}
#ozModal .oz-sat .ad{flex:1}
#ozModal .oz-sat.sec{background:color-mix(in srgb,#EC4899 7%,var(--card))}
#ozModal .oz-sat .oz-adet{width:86px;border:1px solid var(--line);border-radius:8px;padding:6px 8px;text-align:center;font-weight:800;font-size:13.5px;background:var(--card);color:var(--ink)}
#ozModal .oz-sat .oz-adet:disabled{opacity:.35}
#ozModal .oz-sat small{font-size:11px;color:var(--sub);font-weight:600;width:52px}
#ozModal .oz-chk{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--ink);cursor:pointer;flex-wrap:wrap}
#ozModal .oz-chk input{width:17px;height:17px;accent-color:#EC4899}
#ozModal .oz-chk small{color:var(--sub)}
#ozModal .oz-alt{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--line)}
@media(max-width:600px){#ozModal .oz-izgara{grid-template-columns:1fr}}
#up .oz-ozet{gap:8px;flex-wrap:wrap;font-size:13px;color:var(--ink)}
#up .oz-ozet .cip{display:inline-flex;align-items:center;gap:5px;background:var(--card2);border-radius:9px;padding:6px 10px;font-weight:700}
#up .oz-ozet .cip .material-icons-outlined{font-size:16px;color:#db2777}
#up .oz-ozet .cip.ad{background:linear-gradient(135deg,#fdf2f8,#fff7ed);color:#9d174d;font-weight:900}
body.koyu #up .oz-ozet .cip.ad{background:#2a1d2a;color:#f9a8d4}
/* ana ızgara */
#up .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px}
@media(max-width:1000px){#up .grid{grid-template-columns:1fr}}
#up .kart{background:var(--card);border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;flex-direction:column;height:calc(100vh - 190px);min-height:480px;overflow:hidden}
@media(max-width:1000px){#up .kart{height:auto;min-height:0}#up .kaydir{max-height:70vh}}
#up .kart-bas{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--line)}
#up .kart-bas h2{margin:0;font-size:15px;font-weight:900;color:var(--ink)}
#up .kart-bas .ozet{font-size:12.5px;color:var(--sub)}
#up .kart-bas .ozet b{color:var(--ink)}
#up .kart-bas .sag{margin-left:auto;display:flex;gap:6px;align-items:center}
#up .kaydir{flex:1;overflow-y:auto}
/* ürün satırları */
#up .filtre-sat{display:flex;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line);flex-wrap:wrap}
#up .urun-ara{flex:1;min-width:140px;padding:7px 11px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
#up .urun-sat{display:flex;align-items:center;gap:10px;padding:6px 14px;border-bottom:1px solid var(--line);min-height:46px}
#up .urun-sat:hover{background:var(--card2)}
#up .urun-sat.dolu{background:color-mix(in srgb,var(--mavi) 5%,var(--card))}
#up .urun-sat.dolu .ad{color:var(--mavi)}
#up .urun-sat .ad{flex:1;min-width:0;font-size:13.5px;font-weight:700;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#up .oneri-btn{display:inline-flex;align-items:center;gap:4px;border:1px solid transparent;cursor:pointer;border-radius:8px;padding:3px 8px;font-size:11.5px;font-weight:700;background:color-mix(in srgb,var(--mor1) 9%,transparent);color:var(--mor1);white-space:nowrap}
#up .oneri-btn:hover{border-color:var(--mor1)}
#up .oneri-btn .material-icons-outlined{font-size:13px}
#up .oneri-btn .t-up{color:#059669}#up .oneri-btn .t-dn{color:#e11d48}
#up .oneri-yok{font-size:11px;color:var(--sub2);white-space:nowrap}
#up .stepper{display:flex;align-items:center;border:1px solid var(--line);border-radius:9px;overflow:hidden;background:var(--card);flex:none}
#up .stepper button{border:0;background:var(--card2);color:var(--ink);width:26px;height:30px;cursor:pointer;font-size:16px;font-weight:700;line-height:1}
#up .stepper button:hover{color:var(--mavi)}
#up .stepper .adet{width:52px;height:30px;border:0;text-align:center;font-size:14px;font-weight:800;color:var(--ink);background:transparent;-moz-appearance:textfield}
#up .stepper .adet::-webkit-inner-spin-button,#up .stepper .adet::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
#up .birim{width:52px;font-size:11px;font-weight:700;color:var(--sub);border:1px solid var(--line);border-radius:8px;padding:5px 2px;background:var(--card);cursor:pointer;flex:none}
#up .bos-liste{text-align:center;color:var(--sub);padding:30px;font-size:13px}
@media(max-width:600px){#up .urun-sat{padding:8px 10px;gap:6px 7px;flex-wrap:wrap}#up .urun-sat .ad{flex:1 1 100%;white-space:normal;font-size:13.5px}#up .urun-sat [data-oneri-yer]{margin-right:auto}#up .ayrac{display:none}}
/* malzeme tablosu */
#up .talep-sat{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 14px;background:color-mix(in srgb,var(--yesil) 6%,var(--card));border-bottom:1px solid var(--line)}
#up .talep-sat input{border:1px solid var(--line);border-radius:9px;padding:7px 10px;background:var(--card);color:var(--ink);font-size:12.5px;font-family:inherit}
#up .talep-sat label{font-size:11.5px;font-weight:700;color:var(--sub)}
#up .mt{width:100%;border-collapse:collapse;font-size:13px}
#up .mt th{position:sticky;top:0;z-index:1;background:var(--card2);color:var(--sub);font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;text-align:right;padding:8px 14px}
#up .mt th:first-child,#up .mt td:first-child{text-align:left}
#up .mt td{padding:8px 14px;border-bottom:1px solid var(--line);text-align:right;color:var(--ink);white-space:nowrap}
#up .mt td:first-child{white-space:normal;font-weight:700;width:40%}
#up .mt .bar{height:4px;border-radius:4px;background:var(--card2);margin-top:5px;overflow:hidden;max-width:180px}
#up .mt .bar i{display:block;height:100%;border-radius:4px}
#up .mt .stok{color:var(--sub)}
#up .mt .al{display:inline-block;font-weight:900;color:#fff;background:var(--kirmizi);border-radius:7px;padding:3px 8px}
#up .mt .tl{color:var(--amber);font-weight:800}
#up .mt tr.yeter td{color:var(--sub);font-weight:600}
#up .mt tr.yeter .al{background:color-mix(in srgb,var(--yesil) 15%,transparent);color:#059669}
#up .ara-baslik td{background:var(--card2);font-size:11.5px;font-weight:800;color:var(--sub);cursor:pointer;text-align:left!important}
#up .bos-sonuc{text-align:center;color:var(--sub);padding:50px 20px;font-size:13px;line-height:1.6}
#up .bos-sonuc .material-icons-outlined{font-size:40px;color:var(--sub2);display:block;margin-bottom:8px}
#up .bos-sonuc b{color:var(--ink)}
#up .tamam-not{margin:14px;border-radius:11px;padding:11px 13px;font-size:12.5px;font-weight:700;color:#059669;background:color-mix(in srgb,var(--yesil) 9%,var(--card));display:flex;gap:8px;align-items:center}
#up .yukleniyor{opacity:.5;pointer-events:none}
#up .flash{border-radius:12px;padding:11px 14px;margin-bottom:12px;font-weight:600;font-size:13.5px}
#up .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
#up .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
#up .nasil{display:none;background:var(--card);border-radius:14px;padding:12px 16px;margin-bottom:12px;font-size:12.5px;color:var(--sub);line-height:1.6;box-shadow:0 2px 10px rgba(0,0,0,.05)}
#up .nasil.acik{display:block}
#up .nasil ul{margin:4px 0 0;padding-left:18px}
#up .nasil b{color:var(--ink)}
#mrpToast{position:fixed;left:50%;bottom:26px;transform:translateX(-50%) translateY(20px);background:#111827;color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;box-shadow:0 14px 34px rgba(0,0,0,.45);opacity:0;pointer-events:none;transition:.3s cubic-bezier(.22,.68,0,1.2);z-index:9999;max-width:90vw;text-align:center}
#mrpToast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#mrpToast.uyari{background:linear-gradient(135deg,#F59E0B,#d97706)}
/* Reçete gramaj popup */
.up-modal{--card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;--vurgu:#4F46E5;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px}
body.koyu .up-modal{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;--vurgu:#818cf8}
.up-modal.acik{display:flex}
.up-modal .rdm{background:var(--card);width:min(860px,100%);max-height:min(86vh,900px);border-radius:18px;box-shadow:0 24px 60px rgba(15,23,42,.35);display:flex;flex-direction:column;overflow:hidden}
.up-modal .rdm-bas{display:flex;align-items:flex-start;gap:12px;padding:16px 18px 12px;border-bottom:1px solid var(--line)}
.up-modal .rdm-ik{width:38px;height:38px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--vurgu) 12%,transparent);color:var(--vurgu)}
.up-modal .rdm-bas h3{margin:0;font-size:16px;font-weight:800;color:var(--ink)}
.up-modal .rdm-bas p{margin:3px 0 0;font-size:12.5px;color:var(--sub)}
.up-modal .rdm-kapat{margin-left:auto;border:0;background:var(--card2);color:var(--sub);width:34px;height:34px;border-radius:10px;cursor:pointer;flex:none;display:flex;align-items:center;justify-content:center}
.up-modal .rdm-kapat:hover{color:var(--ink)}
.up-modal .rdm-ara{margin:12px 18px 0;padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:13px}
.up-modal .rdm-govde{overflow-y:auto;padding:12px 18px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px;align-content:start}
.up-modal .rd-urun{background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px}
.up-modal .rd-bas{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13.5px;font-weight:800;color:var(--ink);margin-bottom:8px}
.up-modal .rd-por{font-size:11px;font-weight:800;color:var(--vurgu);background:color-mix(in srgb,var(--vurgu) 12%,transparent);border-radius:20px;padding:2px 9px;white-space:nowrap}
.up-modal .rd-k{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--sub);padding:5px 0;border-top:1px dashed var(--line)}
.up-modal .rd-k b{color:var(--ink);white-space:nowrap}
.up-modal .rd-yok{grid-column:1/-1;text-align:center;color:var(--sub);padding:30px;font-size:13px}
</style>
@endpush

@section('content')
<div id="up">
<div class="pano">

  @if (session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if (session('mrp_hata'))<div class="flash err">{{ session('mrp_hata') }}</div>@endif

  <div class="arac-cubuk">
    <div class="seg mod-seg" id="mod">
      <button data-m="rutin" class="akt" onclick="modSec('rutin')"><span class="material-icons-outlined" style="font-size:16px">calendar_view_week</span> Günlük plan</button>
      <button data-m="ozel" onclick="ozelTikla()"><span class="material-icons-outlined" style="font-size:16px">celebration</span> Özel gün / etkinlik</button>
    </div>
    <span class="ayrac"></span>
    <div class="grp sadece-rutin"><span>Dönem</span>
      <div class="seg" id="donem">
        <button data-g="1" onclick="donemSec(this)">Yarın</button>
        <button data-g="7" class="akt" onclick="donemSec(this)">7 Gün</button>
        <button data-g="30" onclick="donemSec(this)">30 Gün</button>
      </div>
    </div>
    <div class="grp sadece-rutin"><span>Güvenlik payı</span>
      <div class="seg" id="pay">
        <button data-p="0" onclick="paySec(this)">%0</button>
        <button data-p="10" class="akt" onclick="paySec(this)">%10</button>
        <button data-p="20" onclick="paySec(this)">%20</button>
      </div>
    </div>
    <button class="btn ai sadece-rutin" onclick="akilliDoldur()"><span class="material-icons-outlined">auto_awesome</span>Tahmini Uygula</button>
    <button class="btn bos sadece-rutin" onclick="temizle()"><span class="material-icons-outlined">restart_alt</span>Sıfırla</button>
    <div class="tahmin-bilgi sadece-rutin" id="tahminBilgi"></div>
    {{-- özel gün: tüm bilgiler pencereden girilir, burada yalnız özet + düzenle --}}
    <div class="grp sadece-ozel oz-ozet" id="ozOzet"></div>
    <button class="btn ai sadece-ozel" onclick="ozelPencereAc()" style="margin-left:auto"><span class="material-icons-outlined">edit_calendar</span>Etkinliği düzenle</button>
    <button class="btn bos sadece-ozel" onclick="ozelPencereAc(true)"><span class="material-icons-outlined">add</span>Yeni etkinlik</button>
  </div>

  <div class="ozel-serit">
    <span class="material-icons-outlined">info</span>
    <span>Menüye ürün eklemek/çıkarmak için soldaki <b>+ Menüye ekle</b>'yi de kullanabilirsin. Stok <b>bugünkü</b> stoktur; etkinliğe kadar normal satış da stoktan düşeceği için gerçek eksik biraz daha fazla olabilir.</span>
  </div>

  <div class="nasil" id="nasil">
    <b>Tahmin nasıl hesaplanıyor?</b>
    <ul>
      <li><b>Haftanın günü:</b> son 8 haftada her ürünün Pazartesi, Cuma, Cumartesi… satışları ayrı ortalanır; seçilen dönemin her günü kendi ortalamasıyla toplanır.</li>
      <li><b>Kapalı günler sayılmaz:</b> hiç satış olmayan günler ortalamayı düşürmez.</li>
      <li><b>Trend:</b> son 2 hafta önceki 2 haftaya göre arttıysa ya da düştüyse en fazla ±%30 ayarlanır.</li>
      <li><b>Güvenlik payı:</b> stok bitmesin diye seçilen oranda fazladan eklenir.</li>
      <li><b>Güven:</b> 28+ satış günü = yüksek, 10+ = orta, daha azı = düşük.</li>
    </ul>
  </div>

  <div class="grid">
    {{-- SOL: ürün + adet --}}
    <div class="kart">
      <div class="kart-bas"><h2>Ürünler</h2><span class="ozet" id="solOzet"></span></div>
      <div class="filtre-sat">
        <input class="urun-ara" placeholder="Ürün ara..." oninput="ara(this.value)">
        <div class="seg" id="filtre">
          <button data-f="hepsi" class="akt" onclick="filtreSec(this)">Tümü</button>
          <button data-f="plan" onclick="filtreSec(this)">Planlananlar</button>
        </div>
      </div>
      <div class="kaydir" id="urunListe">
        @forelse ($urunler as $u)
          @php $vbid = (int) ($u->verim_birim_id ?? 0); @endphp
          <div class="urun-sat" data-ad="{{ mb_strtolower($u->ad) }}" data-urun="{{ $u->id }}">
            <span class="ad" title="{{ $u->ad }}">{{ $u->ad }}</span>
            <span data-oneri-yer></span>
            <div class="stepper">
              <button type="button" onclick="adim(this,-1)" aria-label="Azalt">−</button>
              <input class="adet" type="number" min="0" step="any" value="0" inputmode="decimal"
                data-urun="{{ $u->id }}" data-recete="{{ $u->recete_id }}" data-vbirim="{{ $vbid }}" oninput="degisti(this)">
              <button type="button" onclick="adim(this,1)" aria-label="Artır">+</button>
            </div>
            <select class="birim" data-recete="{{ $u->recete_id }}" onchange="birimDegis(this)" title="Üretim birimi — seçince reçeteye kaydedilir">
              @foreach($birimAll as $b)
                <option value="{{ $b->id }}" @if($vbid === (int) $b->id) selected @endif>{{ $b->kisaltma }}</option>
              @endforeach
            </select>
          </div>
        @empty
          <div class="bos-liste">Reçetesi tanımlı aktif ürün yok.<br>Önce <b>Reçeteler</b> ekranından reçete tanımla.</div>
        @endforelse
      </div>
    </div>

    {{-- SAĞ: alınacaklar --}}
    <div class="kart">
      <div class="kart-bas"><h2>Alınacaklar</h2><span class="ozet" id="sagOzet"></span>
        <div class="sag"><span id="alBtn" style="display:none"><button type="button" class="btn cizgi" onclick="menuGorAc()" title="Plandaki güncel menüyü gör" style="padding:6px 10px;font-size:12px;margin-right:6px"><span class="material-icons-outlined" style="font-size:16px">restaurant_menu</span>Menüyü gör</button><button type="button" class="btn cizgi" onclick="alKopyala()" title="Alınacaklar listesini kopyala (WhatsApp'a yapıştır)" style="padding:6px 10px;font-size:12px;margin-right:6px"><span class="material-icons-outlined" style="font-size:16px">content_copy</span>Kopyala</button><button type="button" class="btn cizgi" onclick="alYazdir()" title="Alınacaklar listesini yazdır" style="padding:6px 10px;font-size:12px"><span class="material-icons-outlined" style="font-size:16px">print</span>Yazdır</button></span><button type="button" class="btn cizgi" id="rdBtn" onclick="receteDetayAc()" style="display:none;padding:6px 10px;font-size:12px"><span class="material-icons-outlined" style="font-size:16px">menu_book</span>Reçete detayı</button></div>
      </div>
      <div id="talepYer"></div>
      <div class="kaydir" id="sonuc"></div>
    </div>
  </div>

</div>
</div>

{{-- Özel gün / etkinlik oluşturma penceresi --}}
<div class="up-modal" id="ozModal" onclick="if(event.target===this)ozelPencereKapat()">
  <div class="rdm" role="dialog" aria-modal="true" style="width:min(720px,100%)">
    <div class="rdm-bas">
      <div class="rdm-ik" style="background:linear-gradient(135deg,#EC4899,#F59E0B);color:#fff"><span class="material-icons-outlined">celebration</span></div>
      <div><h3 id="ozBaslik">Özel gün / etkinlik oluştur</h3><p>Düğün, nişan, toplu yemek… menüyü seç, o günün malzeme listesi çıksın.</p></div>
      <button type="button" class="rdm-kapat" onclick="ozelPencereKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="oz-govde">
      <div class="oz-izgara">
        <label class="oz-alan" style="grid-column:1/-1">Etkinlik adı<input type="text" id="ozAd" class="oz-inp" placeholder="ör. Yılmaz düğünü"></label>
        <label class="oz-alan"><span>Tarih <em>*</em></span><input type="date" id="ozTarih" class="oz-inp" onchange="this.classList.remove('hata')"></label>
        <label class="oz-alan">Kişi sayısı<input type="number" id="ozKisi" class="oz-inp" min="1" placeholder="ör. 200" oninput="ozKisiDegisti()"></label>
      </div>
      <div class="oz-menu-bas"><b>Menü</b><span id="ozMenuSay"></span><input type="search" class="oz-inp" placeholder="Ürün ara..." oninput="ozMenuAra(this.value)" style="margin-left:auto;width:180px"></div>
      <div class="oz-menu" id="ozMenu"></div>
      <label class="oz-chk"><input type="checkbox" id="ozNormal"> O gün normal servis de var — <b>normal satışı da ekle</b> <small>(satış geçmişinden o günün tahmini)</small></label>
    </div>
    <div class="oz-alt">
      <button type="button" class="mbtn" onclick="ozelPencereKapat()">Vazgeç</button>
      <button type="button" class="mbtn yesil" onclick="ozelOlustur()"><span class="material-icons-outlined">check</span><span id="ozOlusturMetin">Oluştur ve malzemeleri hesapla</span></button>
    </div>
  </div>
</div>

<script>
const MRP_CSRF=document.querySelector('meta[name=csrf-token]').content;
let TAHMIN={!! json_encode((object) ($akilli ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
let sonPlan=[], sonDetay=[], sonSonuc=null, akilliDolu=false, hesapZaman=null, hesapNo=0, yeterAcik=false;
let MOD='rutin', NORMAL={}; const MOD_DEGER={rutin:{},ozel:{}}; // her modun adetleri ayrı tutulur
const GUN_AD=['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
const GUVEN_AD={yuksek:'güven yüksek',orta:'güven orta',dusuk:'güven düşük'};
function rdEsc(v){return String(v==null?'':v).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function tl(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(v)+' ₺';}
function sayi(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:2}).format(v);}
function aktifGun(){return +document.querySelector('#donem button.akt').dataset.g;}
function aktifPay(){return +document.querySelector('#pay button.akt').dataset.p;}
function donemAd(){const g=aktifGun();return g===1?'yarın':(g===7?'7 gün':'30 gün');}
function toast(msg,uyari){
  let t=document.getElementById('mrpToast');
  if(!t){t=document.createElement('div');t.id='mrpToast';document.body.appendChild(t);}
  t.textContent=msg; t.className='show'+(uyari?' uyari':'');
  clearTimeout(window._mtt); window._mtt=setTimeout(()=>t.className='',3600);
}

/* ---- tahmin ---- */
function oneriCiz(){
  if(MOD==='ozel'){ // özel gün: tahmin çipi yerine "Menüye ekle" + (isteğe bağlı) o günün normal satışı
    document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
      const v=+s.querySelector('.adet').value||0, n=NORMAL[s.dataset.urun];
      s.querySelector('[data-oneri-yer]').innerHTML=(n?'<span class="normal-ek" title="O günün normal satış tahmini, plana eklenir">+'+sayi(n.oneri)+' normal</span> ':'')+
        '<button type="button" class="menu-btn'+(v>0?' var':'')+'" onclick="menuToggle('+(+s.dataset.urun)+')">'+(v>0?'✓ Menüde':'+ Menüye ekle')+'</button>';
    });
    return;
  }
  let veriGun=0, guven=null;
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const t=TAHMIN[s.dataset.urun], yer=s.querySelector('[data-oneri-yer]');
    s.dataset.oneri=t?t.oneri:0;
    if(!t){yer.innerHTML='<span class="oneri-yok">satış yok</span>';return;}
    veriGun=Math.max(veriGun,t.veri_gun||0); guven=t.guven;
    let tr=''; if(t.trend>=5) tr=' <span class="t-up">↑%'+t.trend+'</span>'; else if(t.trend<=-5) tr=' <span class="t-dn">↓%'+Math.abs(t.trend)+'</span>';
    const ipucu='Günde ort. ~'+sayi(t.gunluk_ort)+(t.en_yogun?' · en yoğun gün '+t.en_yogun:'')+(t.trend?' · son 2 hafta trendi %'+t.trend:'')+' — uygulamak için tıkla';
    yer.innerHTML='<button type="button" class="oneri-btn" onclick="oneriUygula(this)" title="'+rdEsc(ipucu)+'"><span class="material-icons-outlined">auto_awesome</span>'+sayi(t.oneri)+tr+'</button>';
  });
  const b=document.getElementById('tahminBilgi');
  if(veriGun>0) b.innerHTML='Tahmin: son 8 haftada <b>'+veriGun+' satış günü</b> <span class="guven '+guven+'">'+GUVEN_AD[guven]+'</span><button class="bilgi-i" onclick="document.getElementById(\'nasil\').classList.toggle(\'acik\')" title="Nasıl hesaplanıyor?"><span class="material-icons-outlined" style="font-size:18px">info</span></button>';
  else b.innerHTML='Satış geçmişi yok — adetleri elle gir';
}
async function tahminYenile(){
  try{
    const r=await fetch('/uretim-plani/tahmin?gun='+aktifGun()+'&pay='+aktifPay()+'&_t='+Date.now(),{headers:{'Accept':'application/json'}});
    const j=await r.json(); if(j.ok){TAHMIN=j.tahmin||{}; oneriCiz(); if(akilliDolu) akilliDoldur(true);}
  }catch(e){toast('⚠️ Tahmin yenilenemedi',true);}
}
function donemSec(b){document.querySelectorAll('#donem button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');tahminYenile();}
function paySec(b){document.querySelectorAll('#pay button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');tahminYenile();}
function akilliDoldur(sessiz){
  let n=0;
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const t=TAHMIN[s.dataset.urun]; if(!t) return; // geçmişi yoksa elle gireni silme
    s.querySelector('.adet').value=t.oneri; n++;
  });
  if(!n){if(!sessiz) toast('⚠️ Son 8 haftada satış geçmişi yok. Adetleri elle gir.',true);return;}
  akilliDolu=true; satirlariBoya(); hesaplaGecikmeli(30);
  if(!sessiz) toast('✨ '+n+' ürüne '+donemAd()+' tahmini uygulandı');
}
function oneriUygula(b){const s=b.closest('.urun-sat');const inp=s.querySelector('.adet');inp.value=s.dataset.oneri;degisti(inp);}
function temizle(){document.querySelectorAll('#urunListe .adet').forEach(i=>i.value=0);akilliDolu=false;satirlariBoya();hesaplaGecikmeli(0);}

/* ---- özel gün / etkinlik ---- */
function adetleriAl(){const o={};document.querySelectorAll('#urunListe .adet').forEach(i=>{if(+i.value>0)o[i.dataset.urun]=i.value;});return o;}
function adetleriYaz(o){document.querySelectorAll('#urunListe .adet').forEach(i=>{i.value=o[i.dataset.urun]||0;});}
function modSec(m){
  if(m===MOD) return;
  MOD_DEGER[MOD]=adetleriAl(); MOD=m; adetleriYaz(MOD_DEGER[m]);
  document.getElementById('up').classList.toggle('ozel',m==='ozel');
  document.querySelectorAll('#mod button').forEach(b=>b.classList.toggle('akt',b.dataset.m===m));
  akilliDolu=false; oneriCiz(); satirlariBoya(); ozelFormDoldur(); hesaplaGecikmeli(0);
}
// "Özel gün" sekmesi: etkinlik yoksa önce oluşturma penceresi açılır
function ozelTikla(){if(!document.getElementById('ozTarih').value) ozelPencereAc(); else modSec('ozel');}
function ozelPencereAc(yeni){
  const m=document.getElementById('ozModal');
  if(yeni){['ozAd','ozTarih','ozKisi'].forEach(id=>document.getElementById(id).value='');document.getElementById('ozNormal').checked=false;}
  const mevcut=yeni?{}:(MOD==='ozel'?adetleriAl():MOD_DEGER.ozel);
  const kisi=+document.getElementById('ozKisi').value||0;
  let h='';
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const id=s.dataset.urun, v=+mevcut[id]||0, ad=s.querySelector('.ad').textContent, br=s.querySelector('.birim');
    h+='<label class="oz-sat'+(v>0?' sec':'')+'" data-ad="'+rdEsc(s.dataset.ad)+'"><input type="checkbox" data-urun="'+id+'"'+(v>0?' checked':'')+' onchange="ozSatSec(this)">'+
      '<span class="ad">'+rdEsc(ad)+'</span><input type="number" class="oz-adet" min="0" step="any" value="'+(v>0?v:'')+'" placeholder="'+(kisi||'adet')+'"'+(v>0?'':' disabled')+(v>0&&v!==kisi?' data-elle="1"':'')+' oninput="this.dataset.elle=\'1\'">'+
      '<small>'+rdEsc(br?br.options[br.selectedIndex].text:'')+'</small></label>';
  });
  document.getElementById('ozMenu').innerHTML=h||'<div style="padding:20px;text-align:center;color:var(--sub)">Reçetesi tanımlı ürün yok.</div>';
  const varMi=!yeni&&!!document.getElementById('ozTarih').value;
  document.getElementById('ozBaslik').textContent=varMi?'Etkinliği düzenle':'Özel gün / etkinlik oluştur';
  document.getElementById('ozOlusturMetin').textContent=varMi?'Kaydet ve yeniden hesapla':'Oluştur ve malzemeleri hesapla';
  document.getElementById('ozTarih').classList.remove('hata');
  ozMenuSay(); m.classList.add('acik'); document.body.style.overflow='hidden';
  setTimeout(()=>document.getElementById(document.getElementById('ozAd').value?'ozKisi':'ozAd').focus(),60);
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')ozelPencereKapat();});
function ozelPencereKapat(){const m=document.getElementById('ozModal');if(m.classList.contains('acik')){m.classList.remove('acik');document.body.style.overflow='';}}
function ozSatSec(cb){
  const sat=cb.closest('.oz-sat'), inp=sat.querySelector('.oz-adet');
  sat.classList.toggle('sec',cb.checked); inp.disabled=!cb.checked;
  if(cb.checked&&!(+inp.value>0)){inp.value=+document.getElementById('ozKisi').value||1;delete inp.dataset.elle;}
  ozMenuSay();
}
function ozKisiDegisti(){ // kişi sayısı değişince: elle değiştirilmemiş seçili ürünler kişi sayısını izler
  const k=+document.getElementById('ozKisi').value||0;
  document.querySelectorAll('#ozMenu .oz-sat').forEach(s=>{
    const cb=s.querySelector('input[type=checkbox]'), inp=s.querySelector('.oz-adet');
    inp.placeholder=k||'adet'; if(cb.checked&&inp.dataset.elle!=='1'&&k>0) inp.value=k;
  });
}
function ozMenuAra(q){q=(q||'').toLocaleLowerCase('tr');document.querySelectorAll('#ozMenu .oz-sat').forEach(s=>s.style.display=s.dataset.ad.includes(q)?'':'none');}
function ozMenuSay(){const n=document.querySelectorAll('#ozMenu input[type=checkbox]:checked').length;document.getElementById('ozMenuSay').textContent=n?n+' ürün seçildi':'menüdeki ürünleri işaretle';}
async function ozelOlustur(){
  const t=document.getElementById('ozTarih');
  if(!t.value){t.classList.add('hata');t.focus();toast('⚠️ Etkinlik tarihini seç',true);return;}
  const secim={};
  document.querySelectorAll('#ozMenu .oz-sat').forEach(s=>{const cb=s.querySelector('input[type=checkbox]'),v=+s.querySelector('.oz-adet').value||0;if(cb.checked&&v>0)secim[cb.dataset.urun]=v;});
  const normal=document.getElementById('ozNormal').checked;
  if(!Object.keys(secim).length&&!normal){toast('⚠️ Menüden en az bir ürün seç',true);return;}
  if(MOD!=='ozel'){MOD_DEGER[MOD]=adetleriAl();MOD='ozel';}
  document.getElementById('up').classList.add('ozel');
  document.querySelectorAll('#mod button').forEach(b=>b.classList.toggle('akt',b.dataset.m==='ozel'));
  adetleriYaz(secim); MOD_DEGER.ozel=secim; ozelPencereKapat();
  akilliDolu=false; await normalGetir(); oneriCiz(); satirlariBoya(); ozelFormDoldur(); hesaplaGecikmeli(0);
}
function menuToggle(urunId){ // butonlar her değişimde yeniden çizilir → elemana değil ürün id'sine bağlı
  const inp=document.querySelector('#urunListe .adet[data-urun="'+urunId+'"]'); if(!inp) return;
  inp.value=(+inp.value>0)?0:(+document.getElementById('ozKisi').value||1);
  degisti(inp);
}
async function normalGetir(){
  const t=document.getElementById('ozTarih').value;
  if(!document.getElementById('ozNormal').checked){NORMAL={};return;}
  if(!t){toast('⚠️ Normal satışı eklemek için önce tarihi seç',true);document.getElementById('ozNormal').checked=false;NORMAL={};return;}
  try{const r=await fetch('/uretim-plani/tahmin?gun=1&pay=0&tarih='+t+'&_t='+Date.now(),{headers:{'Accept':'application/json'}});const j=await r.json();NORMAL=j.ok?(j.tahmin||{}):{};}
  catch(e){NORMAL={};toast('⚠️ Normal satış tahmini alınamadı',true);}
}
function trTarih(t){if(!t)return '';const d=new Date(t+'T12:00:00');return d.toLocaleDateString('tr-TR',{day:'numeric',month:'long',year:'numeric'})+' '+GUN_AD[d.getDay()];}
function ozelFormDoldur(){
  const t=document.getElementById('ozTarih').value, ad=document.getElementById('ozAd').value.trim();
  const kisi=+document.getElementById('ozKisi').value||0, urunSay=Object.keys(adetleriAl()).length;
  document.getElementById('ozOzet').innerHTML=t?'<span class="cip ad"><span class="material-icons-outlined">celebration</span>'+rdEsc(ad||'Özel gün')+'</span>'+
    '<span class="cip"><span class="material-icons-outlined">event</span>'+rdEsc(trTarih(t))+'</span>'+
    (kisi?'<span class="cip"><span class="material-icons-outlined">groups</span>'+kisi+' kişi</span>':'')+
    '<span class="cip"><span class="material-icons-outlined">restaurant_menu</span>'+urunSay+' ürün</span>'+
    (document.getElementById('ozNormal').checked?'<span class="cip"><span class="material-icons-outlined">add_circle</span>normal satış dahil</span>':''):'';
  const f=document.querySelector('#talepYer form'); if(!f) return;
  // talep formu: özel günde teslim = etkinlikten 1 gün önce, not = etkinlik adı (kullanıcı elle değiştirdiyse dokunma)
  const otoYaz=(el,v)=>{if(el.value===''||el.value===el.dataset.oto){el.value=v;}el.dataset.oto=v;};
  let teslim='', not='';
  if(MOD==='ozel'&&t){const d=new Date(t+'T12:00:00');d.setDate(d.getDate()-1);const bugun=new Date();bugun.setHours(12,0,0,0);if(d<bugun)d.setTime(bugun.getTime());teslim=d.toISOString().slice(0,10);not='🎉 '+(ad||'Özel gün')+' ('+trTarih(t)+') özel gün menüsü';}
  otoYaz(f.teslim_tarihi,teslim); otoYaz(f.not,not);
}

/* ---- alışveriş listesi: kopyala / yazdır (eksik varsa eksikler, yoksa toplam ihtiyaç) ---- */
let alKip='eksik';
function alKipBelirle(){alKip=(sonSonuc&&(sonSonuc.rows||[]).some(x=>!x.yeterli))?'eksik':'tum';}
function alListe(){
  const rows=(sonSonuc&&sonSonuc.rows)||[];
  return alKip==='eksik'?rows.filter(x=>!x.yeterli).map(x=>({ad:x.ad,mk:x.net,br:x.birim,tl:x.tahmini_maliyet})):rows.map(x=>({ad:x.ad,mk:x.lazim,br:x.birim,tl:0}));
}
function alBaslikMetin(){
  if(MOD==='ozel'){const t=document.getElementById('ozTarih').value,ad=document.getElementById('ozAd').value.trim();return '🎉 '+(ad||'Özel gün')+(t?' · '+trTarih(t):'');}
  return 'Üretim planı · '+donemAd();
}
function alMetin(){
  const l=alListe();
  return '🛒 '+alBaslikMetin()+'\n'+(alKip==='eksik'?'(stok düşüldü, alınacaklar)':'(toplam ihtiyaç)')+'\n\n'+l.map(x=>'▫️ '+x.ad+' — '+sayi(x.mk)+' '+x.br).join('\n');
}
async function alKopyala(){
  alKipBelirle(); const s=alMetin();
  try{await navigator.clipboard.writeText(s);}catch(e){const ta=document.createElement('textarea');ta.value=s;document.body.appendChild(ta);ta.select();try{document.execCommand('copy');}catch(_){}ta.remove();}
  toast('✓ Liste kopyalandı — WhatsApp\'a yapıştırabilirsin');
}
function alYazdir(){
  alKipBelirle(); const l=alListe(), w=window.open('','_blank','width=720,height=900'); if(!w){toast('⚠️ Açılır pencere engellendi',true);return;}
  const satir=l.map(x=>'<tr><td style="width:28px">☐</td><td>'+rdEsc(x.ad)+'</td><td style="text-align:right;font-weight:700">'+sayi(x.mk)+' '+rdEsc(x.br)+'</td></tr>').join('');
  w.document.write('<!doctype html><meta charset="utf-8"><title>Alışveriş listesi</title><style>body{font-family:Arial,sans-serif;padding:24px;color:#111}h2{margin:0 0 4px}p{margin:0 0 16px;color:#555}table{width:100%;border-collapse:collapse;font-size:15px}td{padding:9px 6px;border-bottom:1px solid #ddd}</style>'+
    '<h2>'+rdEsc(alBaslikMetin())+'</h2><p>'+(alKip==='eksik'?'Stok düşüldü — alınacaklar':'Toplam ihtiyaç')+' · '+new Date().toLocaleDateString('tr-TR')+'</p><table>'+satir+'</table>');
  w.document.close(); w.focus(); setTimeout(()=>w.print(),250);
}

/* ---- sol liste ---- */
function adim(b,d){const inp=b.parentElement.querySelector('.adet');inp.value=Math.max(0,Math.round(((+inp.value||0)+d)*100)/100);degisti(inp);}
function degisti(inp){akilliDolu=false;satirlariBoya();hesaplaGecikmeli();}
function satirlariBoya(){
  let u=0,a=0;
  document.querySelectorAll('#urunListe .adet').forEach(i=>{const v=+i.value||0;i.closest('.urun-sat').classList.toggle('dolu',v>0);if(v>0){u++;a+=v;}});
  if(MOD==='ozel'){
    const nEk=Object.keys(NORMAL).length?' · <b>+ normal satış</b>':'';
    document.getElementById('solOzet').innerHTML=u?'<b>'+u+'</b> ürün menüde · <b>'+sayi(a)+'</b> porsiyon'+nEk:'menüye ürün ekle'+nEk;
    oneriCiz(); ozelFormDoldur();
  }else document.getElementById('solOzet').innerHTML=u?'<b>'+u+'</b> ürün planlandı · <b>'+sayi(a)+'</b> porsiyon':'adet gir ya da ✨ öneriye tıkla';
  listeFiltre();
}
let aktifFiltre='hepsi', aramaQ='';
function filtreSec(b){document.querySelectorAll('#filtre button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');aktifFiltre=b.dataset.f;listeFiltre();}
function ara(q){aramaQ=(q||'').toLocaleLowerCase('tr');listeFiltre();}
function listeFiltre(){
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    let g=s.dataset.ad.includes(aramaQ);
    if(g&&aktifFiltre==='plan') g=s.classList.contains('dolu');
    s.style.display=g?'':'none';
  });
}
async function birimDegis(sel){
  const recete=+sel.dataset.recete, birim_id=+sel.value;
  const ad=sel.closest('.urun-sat').querySelector('.ad').innerText;
  try{
    const r=await fetch('/uretim-plani/birim-kaydet',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MRP_CSRF,'Accept':'application/json'},body:JSON.stringify({recete_id:recete,birim_id})});
    const j=await r.json();
    if(j.ok){toast('✓ '+ad+' birimi "'+sel.options[sel.selectedIndex].text+'" olarak kaydedildi');hesaplaGecikmeli(0);}
    else toast('⚠️ Birim kaydedilemedi',true);
  }catch(e){toast('⚠️ Birim kaydedilemedi',true);}
}
function planTopla(){
  const plan=[];
  const normalEk=MOD==='ozel'; // özel günde "o günün normal satışı" seçiliyse etkinlik adedine eklenir
  document.querySelectorAll('#urunListe .adet').forEach(inp=>{
    const adet=(+inp.value||0)+(normalEk&&NORMAL[inp.dataset.urun]?(+NORMAL[inp.dataset.urun].oneri||0):0);
    if(adet>0){
      const sel=inp.closest('.urun-sat').querySelector('.birim');
      const birim_id=sel?(+sel.value):(+inp.dataset.vbirim||0);
      plan.push({urun_id:+inp.dataset.urun,recete_id:+inp.dataset.recete,adet:adet,birim_id:birim_id});
    }
  });
  return plan;
}

/* ---- hesap (otomatik) ---- */
function hesaplaGecikmeli(ms){clearTimeout(hesapZaman);hesapZaman=setTimeout(hesapla,ms==null?500:ms);}
async function hesapla(){
  const plan=planTopla(), kutu=document.getElementById('sonuc');
  if(!plan.length){
    hesapNo++; sonPlan=[];sonDetay=[];sonSonuc=null; // geç dönen eski hesap boş ekranı ezmesin
    document.getElementById('rdBtn').style.display='none';document.getElementById('alBtn').style.display='none';document.getElementById('talepYer').innerHTML='';
    document.getElementById('sagOzet').textContent='';
    kutu.innerHTML=MOD==='ozel'
      ?'<div class="bos-sonuc"><span class="material-icons-outlined">celebration</span><b>Özel gün menüsü boş.</b><br>Soldan ürünleri <b>+ Menüye ekle</b> ile seç, kişi sayısını yaz →<br>o günün malzeme listesi burada çıkar.</div>'
      :'<div class="bos-sonuc"><span class="material-icons-outlined">shopping_basket</span>Soldan adet gir ya da <b>Tahmini Uygula</b>\'ya bas —<br>reçeteler açılır, stok düşülür, <b>alınacak liste</b> burada çıkar.</div>';
    return;
  }
  sonPlan=plan; const no=++hesapNo; kutu.classList.add('yukleniyor');
  try{
    const r=await fetch('/uretim-plani/hesapla',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MRP_CSRF,'Accept':'application/json'},body:JSON.stringify({plan})});
    const j=await r.json();
    if(no!==hesapNo) return; // daha yeni bir hesap başladı
    sonSonuc=j; ciz();
  }catch(e){if(no===hesapNo) kutu.innerHTML='<div class="bos-sonuc">Hesaplanamadı, tekrar dene.</div>';}
  finally{if(no===hesapNo) kutu.classList.remove('yukleniyor');}
}
function malSatir(x){
  const lz=+x.lazim_temel||0, mv=+x.mevcut_temel||0, oran=lz>0?Math.min(100,Math.round(mv/lz*100)):100;
  const renk=oran>=100?'#10B981':(oran>=50?'#F59E0B':'#F43F5E');
  return '<tr class="'+(x.yeterli?'yeter':'')+'"><td>'+rdEsc(x.ad)+'<div class="bar" title="Stok ihtiyacın %'+oran+'\'ini karşılıyor"><i style="width:'+Math.max(3,oran)+'%;background:'+renk+'"></i></div></td>'+
    '<td>'+sayi(x.lazim)+' '+rdEsc(x.birim)+'</td><td class="stok">'+sayi(x.mevcut)+' '+rdEsc(x.mevcut_birim||x.birim)+'</td>'+
    '<td>'+(x.yeterli?'<span class="al">yeterli</span>':'<span class="al">'+sayi(x.net)+' '+rdEsc(x.birim)+'</span>')+'</td>'+
    '<td class="tl">'+(x.tahmini_maliyet>0?tl(x.tahmini_maliyet):'')+'</td></tr>';
}
function yeterAcKapa(){yeterAcik=!yeterAcik;ciz();}
function ciz(){
  const j=sonSonuc||{}, rows=j.rows||[], kutu=document.getElementById('sonuc'), talep=document.getElementById('talepYer');
  sonDetay=j.detay||[];
  document.getElementById('rdBtn').style.display=sonDetay.length?'':'none';
  document.getElementById('alBtn').style.display=rows.length?'':'none';
  const eksik=rows.filter(x=>!x.yeterli), yeter=rows.filter(x=>x.yeterli);
  document.getElementById('sagOzet').innerHTML=eksik.length?'<b style="color:var(--kirmizi)">'+eksik.length+' eksik</b> · ~<b>'+tl(j.toplam||0)+'</b>':(rows.length?'<b style="color:#059669">eksik yok</b>':'');
  if(!rows.length){talep.innerHTML='';kutu.innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">block</span>Bu ürünlerin reçetesinde stok takipli malzeme yok.</div>';return;}
  // talep satırı: listenin ÜSTÜNDE, hep görünür
  if(eksik.length){
    if(!talep.querySelector('form')) talep.innerHTML='<form method="POST" action="/uretim-plani/talep" class="talep-sat" onsubmit="return talepGonder(this)">'+
      '<input type="hidden" name="_token" value="'+MRP_CSRF+'"><input type="hidden" name="plan_json" value="">'+
      '<label>Teslim</label><input type="date" name="teslim_tarihi" title="Ne zaman elinde olsun?">'+
      '<input type="text" name="not" placeholder="Not (isteğe bağlı)" style="flex:1;min-width:120px">'+
      '<button type="submit" class="btn yesil" id="talepBtn"></button></form>';
    document.getElementById('talepBtn').innerHTML='<span class="material-icons-outlined">send</span>'+eksik.length+' kalemi talep et';
    ozelFormDoldur();
  }else talep.innerHTML='';
  let h='<table class="mt"><thead><tr><th>Malzeme</th><th>Lazım</th><th>Stokta</th><th>Alınacak</th><th>~Tutar</th></tr></thead><tbody>';
  eksik.forEach(x=>h+=malSatir(x));
  if(!eksik.length) h+='<tr><td colspan="5"><div class="tamam-not" style="margin:4px 0"><span class="material-icons-outlined">verified</span>Stok bu plan için yeterli — satın almaya gerek yok.</div></td></tr>';
  if(yeter.length){
    h+='<tr class="ara-baslik" onclick="yeterAcKapa()"><td colspan="5">'+(yeterAcik?'▾':'▸')+' Stokta yeterli olanlar ('+yeter.length+')</td></tr>';
    if(yeterAcik) yeter.forEach(x=>h+=malSatir(x));
  }
  kutu.innerHTML=h+'</tbody></table>';
}
function talepGonder(f){f.plan_json.value=JSON.stringify(sonPlan);return true;}

/* ---- reçete gramaj popup ---- */
function receteDetayAc(){
  let m=document.getElementById('rdModal');
  if(!m){
    m=document.createElement('div');m.id='rdModal';m.className='up-modal';
    m.innerHTML='<div class="rdm" role="dialog" aria-modal="true"><div class="rdm-bas"><div class="rdm-ik"><span class="material-icons-outlined">menu_book</span></div>'+
      '<div><h3>Reçete Gramaj Detayı</h3><p id="rdAlt"></p></div><button type="button" class="rdm-kapat" onclick="receteDetayKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>'+
      '<input class="rdm-ara" id="rdAra" placeholder="Ürün veya malzeme ara..." oninput="rdFiltre(this.value)"><div class="rdm-govde" id="rdGovde"></div></div>';
    m.addEventListener('click',e=>{if(e.target===m)receteDetayKapat();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')receteDetayKapat();});
    document.body.appendChild(m);
  }
  const porsiyon=sonDetay.reduce((t,d)=>t+(+d.adet||0),0);
  document.getElementById('rdAlt').textContent=sonDetay.length+' ürün · toplam '+sayi(porsiyon)+' porsiyon için malzeme miktarları';
  document.getElementById('rdGovde').innerHTML=sonDetay.map(d=>{
    const ara=(d.urun+' '+(d.kalemler||[]).map(k=>k.ad).join(' ')).toLocaleLowerCase('tr');
    return '<div class="rd-urun" data-ara="'+rdEsc(ara)+'"><div class="rd-bas"><span>'+rdEsc(d.urun)+'</span><span class="rd-por">× '+rdEsc(d.adet)+' porsiyon</span></div>'+
      (d.kalemler||[]).map(k=>'<div class="rd-k"><span>'+rdEsc(k.ad)+'</span><b>'+sayi(k.miktar)+' '+rdEsc(k.birim)+'</b></div>').join('')+'</div>';
  }).join('')+'<div class="rd-yok" id="rdYok" style="display:none">Eşleşen ürün yok.</div>';
  document.getElementById('rdAra').value='';
  m.classList.add('acik');document.body.style.overflow='hidden';
}
function receteDetayKapat(){const m=document.getElementById('rdModal');if(m&&m.classList.contains('acik')){m.classList.remove('acik');document.body.style.overflow='';}}

/* ---- güncel menü (plandaki ürünler + adetler) ---- */
function menuGorAc(){
  let m=document.getElementById('mgModal');
  if(!m){
    m=document.createElement('div');m.id='mgModal';m.className='up-modal';
    m.innerHTML='<div class="rdm" role="dialog" aria-modal="true" style="width:min(520px,100%)"><div class="rdm-bas"><div class="rdm-ik"><span class="material-icons-outlined">restaurant_menu</span></div>'+
      '<div><h3>Güncel menü</h3><p id="mgAlt"></p></div><button type="button" class="rdm-kapat" onclick="menuGorKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>'+
      '<div id="mgGovde" style="overflow-y:auto;padding:6px 18px 14px"></div></div>';
    m.addEventListener('click',e=>{if(e.target===m)menuGorKapat();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')menuGorKapat();});
    document.body.appendChild(m);
  }
  // özel günde etkinlik menüsü ve "o günün normal servisi" ayrı başlıklarla gösterilir
  const ozel=MOD==='ozel'; let top=0, hEt='', hNo='';
  const sat=(ad,v,br)=>'<div class="rd-k" style="font-size:14px;padding:9px 0"><span style="color:var(--ink);font-weight:700">'+rdEsc(ad)+'</span><b>'+sayi(v)+' '+rdEsc(br)+'</b></div>';
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const v=+s.querySelector('.adet').value||0, n=ozel&&NORMAL[s.dataset.urun]?(+NORMAL[s.dataset.urun].oneri||0):0;
    const ad=s.querySelector('.ad').textContent, b=s.querySelector('.birim'), br=b?b.options[b.selectedIndex].text:'';
    if(v>0){hEt+=sat(ad,v,br);top+=v;} if(n>0){hNo+=sat(ad,n,br);top+=n;}
  });
  const bas=t=>'<div style="font-size:11px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;margin:12px 0 2px">'+t+'</div>';
  let h=ozel&&hNo?(hEt?bas('Etkinlik menüsü')+hEt:'')+bas('O günün normal servisi (tahmin)')+hNo:hEt;
  document.getElementById('mgAlt').textContent=alBaslikMetin()+' · toplam '+sayi(top)+' porsiyon';
  document.getElementById('mgGovde').innerHTML=h||'<div class="rd-yok">Menüde ürün yok.</div>';
  m.classList.add('acik');document.body.style.overflow='hidden';
}
function menuGorKapat(){const m=document.getElementById('mgModal');if(m&&m.classList.contains('acik')){m.classList.remove('acik');document.body.style.overflow='';}}
function rdFiltre(q){q=(q||'').toLocaleLowerCase('tr');let n=0;document.querySelectorAll('#rdGovde .rd-urun').forEach(x=>{const g=x.dataset.ara.includes(q);x.style.display=g?'':'none';if(g)n++;});document.getElementById('rdYok').style.display=n?'none':'';}

// açılışta: geçmişi olan ürünlere 7 günlük tahmin hazır gelsin → liste hemen dolu
oneriCiz(); satirlariBoya();
if(Object.keys(TAHMIN).length) akilliDoldur(true); else hesapla();
</script>
@endsection

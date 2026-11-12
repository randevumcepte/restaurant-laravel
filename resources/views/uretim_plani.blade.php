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
#up .pano{max-width:1240px;margin:0 auto;padding:18px 18px 40px}
#up .material-icons-outlined{vertical-align:middle}
/* HERO */
#up .hero{position:relative;overflow:hidden;border-radius:20px;padding:22px 24px 18px;color:#fff;background:radial-gradient(120% 140% at 100% 0%,#7C3AED 0%,#4F46E5 45%,#1e1b4b 100%);box-shadow:0 14px 34px rgba(79,70,229,.28)}
#up .hero:after{content:'';position:absolute;right:-60px;top:-60px;width:260px;height:260px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.18),transparent 70%);pointer-events:none}
#up .hero-ust{display:flex;gap:18px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap}
#up .rozet-ai{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);border-radius:30px;padding:4px 11px;font-size:11.5px;font-weight:800;letter-spacing:.3px}
#up .rozet-ai .material-icons-outlined{font-size:15px}
#up .hero h1{margin:10px 0 5px;font-size:22px;font-weight:900;line-height:1.25;max-width:640px}
#up .hero p{margin:0;font-size:13px;opacity:.85;max-width:620px;line-height:1.5}
#up .adimlar{display:flex;gap:6px;flex-wrap:wrap}
#up .adim{display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);border-radius:12px;padding:7px 11px;font-size:12px;font-weight:700;opacity:.7;transition:.2s}
#up .adim i{font-style:normal;width:20px;height:20px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900}
#up .adim.akt{opacity:1;background:rgba(255,255,255,.22);border-color:rgba(255,255,255,.45)}
#up .adim.tamam i{background:#10B981}
#up .kontrol{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:18px;position:relative;z-index:1}
#up .kseg{display:inline-flex;background:rgba(15,23,42,.28);border-radius:12px;padding:3px}
#up .kseg button{border:0;background:none;color:rgba(255,255,255,.75);padding:8px 13px;border-radius:9px;font-size:12.5px;font-weight:800;cursor:pointer}
#up .kseg button.akt{background:#fff;color:#4F46E5}
#up .klabel{font-size:11px;font-weight:700;opacity:.75;margin-right:-4px}
#up .btn-ai{border:0;border-radius:12px;padding:10px 18px;font-weight:900;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:#fff;color:#4F46E5;box-shadow:0 6px 18px rgba(15,23,42,.25);transition:.15s}
#up .btn-ai:hover{transform:translateY(-1px)}
#up .btn-ai .material-icons-outlined{font-size:19px}
#up .btn-ghost{border:1px solid rgba(255,255,255,.35);background:none;color:#fff;border-radius:12px;padding:9px 14px;font-weight:700;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
#up .btn-ghost .material-icons-outlined{font-size:17px}
@media(max-width:600px){#up .hero{padding:18px 16px 16px}#up .hero h1{font-size:18px}#up .hero p{display:none}#up .adimlar{display:none}#up .klabel{width:100%;margin:0}}
#up .tahmin-bilgi{margin-top:12px;font-size:12px;opacity:.9;line-height:1.7;position:relative;z-index:1}
#up .guven{white-space:nowrap;border-radius:20px;padding:2px 9px;font-weight:800;font-size:11px;background:rgba(255,255,255,.18)}
#up .guven.yuksek{background:#10B981}#up .guven.orta{background:#F59E0B}#up .guven.dusuk{background:#F43F5E}
/* KPI */
#up .kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0}
@media(max-width:800px){#up .kpi{grid-template-columns:repeat(2,1fr)}}
#up .kpi-k{background:var(--card);border-radius:16px;padding:13px 15px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;gap:12px;align-items:center}
#up .kpi-ik{width:40px;height:40px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--c) 13%,transparent);color:var(--c)}
#up .kpi-ik .material-icons-outlined{font-size:22px}
#up .kpi-k b{display:block;font-size:20px;font-weight:900;color:var(--ink);line-height:1.1}
#up .kpi-k span{font-size:11.5px;color:var(--sub);font-weight:600}
/* GRID */
#up .grid{display:grid;grid-template-columns:1fr 1.1fr;gap:16px;align-items:start}
@media(max-width:960px){#up .grid{grid-template-columns:1fr}}
#up .kart{background:var(--card);border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:16px}
#up .sag-kart{position:sticky;top:76px}
@media(max-width:960px){#up .sag-kart{position:static}}
#up .kart-bas{display:flex;align-items:center;gap:9px;margin-bottom:12px}
#up .kart-bas .no{width:26px;height:26px;border-radius:8px;background:color-mix(in srgb,var(--mavi) 12%,transparent);color:var(--mavi);font-weight:900;font-size:13px;display:flex;align-items:center;justify-content:center;flex:none}
#up .kart-bas h2{margin:0;font-size:15px;font-weight:900;color:var(--ink)}
#up .kart-bas small{display:block;font-size:11.5px;color:var(--sub);font-weight:500;margin-top:1px}
#up .kart-bas .sag{margin-left:auto}
#up .arac{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
#up .urun-ara{flex:1;min-width:160px;padding:9px 12px;border:1px solid var(--line);border-radius:11px;background:var(--card2);color:var(--ink);font-size:13px}
#up .filtre{display:inline-flex;background:var(--card2);border-radius:11px;padding:3px}
#up .filtre button{border:0;background:none;padding:6px 10px;border-radius:8px;font-size:11.5px;font-weight:800;color:var(--sub);cursor:pointer;white-space:nowrap}
#up .filtre button.akt{background:var(--card);color:var(--mavi);box-shadow:0 1px 4px rgba(0,0,0,.08)}
#up .urun-liste{max-height:max(440px,calc(100vh - 420px));overflow-y:auto;display:flex;flex-direction:column;gap:7px;padding-right:3px}
#up .urun-sat{display:flex;align-items:center;gap:10px;padding:9px 10px 9px 12px;border-radius:13px;border:1px solid var(--line);background:var(--card);transition:.15s}
#up .urun-sat.dolu{border-color:color-mix(in srgb,var(--mavi) 55%,var(--line));background:color-mix(in srgb,var(--mavi) 5%,var(--card))}
#up .urun-sat .bilgi{flex:1;min-width:0}
#up .urun-sat .ad{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.25}
#up .oneri{margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:11px;color:var(--sub)}
#up .oneri-btn{display:inline-flex;align-items:center;gap:4px;border:0;cursor:pointer;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:800;background:color-mix(in srgb,var(--mor1) 11%,transparent);color:var(--mor1)}
#up .oneri-btn .material-icons-outlined{font-size:13px}
#up .oneri-btn:hover{background:var(--mor1);color:#fff}
#up .trend-up{color:#059669;font-weight:800}#up .trend-dn{color:#e11d48;font-weight:800}
#up .oneri-yok{font-size:11px;color:var(--sub2)}
#up .adet-kutu{display:flex;flex-direction:column;align-items:flex-end;gap:4px}
#up .stepper{display:flex;align-items:center;border:1px solid var(--line);border-radius:11px;overflow:hidden;background:var(--card)}
#up .stepper button{border:0;background:var(--card2);color:var(--ink);width:30px;height:34px;cursor:pointer;font-size:17px;font-weight:700;line-height:1}
#up .stepper button:hover{background:color-mix(in srgb,var(--mavi) 15%,var(--card2));color:var(--mavi)}
#up .stepper .adet{width:56px;height:34px;border:0;text-align:center;font-size:14.5px;font-weight:800;color:var(--ink);background:transparent;-moz-appearance:textfield}
#up .stepper .adet::-webkit-inner-spin-button,#up .stepper .adet::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
#up .birim{font-size:10.5px;font-weight:800;color:var(--sub);border:1px solid var(--line);border-radius:7px;padding:2px 3px;background:var(--card);cursor:pointer}
#up .bos-liste{text-align:center;color:var(--sub);padding:30px;font-size:13px}
/* SAĞ: ihtiyaç */
#up .rd-ac{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:10px;padding:6px 10px;font-size:12px;font-weight:800;cursor:pointer}
#up .rd-ac:hover{border-color:var(--mavi);color:var(--mavi)}
#up .rd-ac .material-icons-outlined{font-size:16px;color:var(--mavi)}
#up .sekme{display:flex;gap:6px;margin-bottom:10px}
#up .sekme button{flex:1;border:1px solid var(--line);background:var(--card);border-radius:11px;padding:8px;font-size:12.5px;font-weight:800;color:var(--sub);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
#up .sekme button em{font-style:normal;border-radius:20px;padding:0 8px;font-size:11px;background:var(--card2)}
#up .sekme button.akt.e{border-color:var(--kirmizi);color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 6%,var(--card))}
#up .sekme button.akt.y{border-color:var(--yesil);color:#059669;background:color-mix(in srgb,var(--yesil) 7%,var(--card))}
#up .mal-liste{display:flex;flex-direction:column;gap:7px;max-height:max(320px,calc(100vh - 560px));overflow-y:auto;padding-right:3px}
#up .mal{border:1px solid var(--line);border-radius:13px;padding:10px 12px;background:var(--card)}
#up .mal-ust{display:flex;align-items:center;gap:10px}
#up .mal-ad{flex:1;min-width:0;font-size:13.5px;font-weight:800;color:var(--ink)}
#up .mal-al{font-size:12.5px;font-weight:900;border-radius:20px;padding:3px 10px;white-space:nowrap}
#up .mal-al.e{background:linear-gradient(135deg,#F43F5E,#be1e3c);color:#fff}
#up .mal-al.y{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:#059669}
#up .bar{height:6px;border-radius:6px;background:var(--card2);margin:8px 0 6px;overflow:hidden}
#up .bar i{display:block;height:100%;border-radius:6px}
#up .mal-alt{display:flex;gap:12px;font-size:11.5px;color:var(--sub);flex-wrap:wrap}
#up .mal-alt b{color:var(--ink)}
#up .mal-alt .tl{margin-left:auto;font-weight:800;color:var(--amber)}
#up .bos-sonuc{text-align:center;color:var(--sub);padding:44px 20px;font-size:13px;line-height:1.6}
#up .bos-sonuc .ik{width:64px;height:64px;border-radius:20px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mavi) 10%,transparent);color:var(--mavi)}
#up .bos-sonuc .ik .material-icons-outlined{font-size:32px}
#up .bos-sonuc b{color:var(--ink)}
#up .yukleniyor{opacity:.55;pointer-events:none;transition:.15s}
/* talep CTA */
#up .talep-kart{margin-top:14px;border-radius:15px;padding:14px;background:color-mix(in srgb,var(--yesil) 7%,var(--card));border:1px solid color-mix(in srgb,var(--yesil) 35%,var(--line))}
#up .talep-kart h3{margin:0 0 3px;font-size:14px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:7px}
#up .talep-kart h3 .material-icons-outlined{color:var(--yesil);font-size:20px}
#up .talep-kart p{margin:0 0 10px;font-size:12px;color:var(--sub)}
#up .talep-form{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
#up .fld{display:flex;flex-direction:column;gap:4px}
#up .fld label{font-size:11px;font-weight:700;color:var(--sub)}
#up .fld input{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;font-family:inherit}
#up .btn-yesil{border:0;border-radius:11px;padding:10px 16px;font-weight:800;font-size:13.5px;cursor:pointer;color:#fff;background:var(--yesil);display:inline-flex;align-items:center;gap:7px;box-shadow:0 6px 16px color-mix(in srgb,var(--yesil) 35%,transparent)}
#up .btn-yesil .material-icons-outlined{font-size:18px}
#up .tamam-not{margin-top:14px;border-radius:13px;padding:12px 14px;font-size:12.5px;font-weight:700;color:#059669;background:color-mix(in srgb,var(--yesil) 9%,var(--card));display:flex;gap:8px;align-items:center}
#up .flash{border-radius:12px;padding:12px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px}
#up .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
#up .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
#up .nasil{margin-top:16px;background:var(--card);border-radius:16px;padding:14px 16px;font-size:12.5px;color:var(--sub);line-height:1.6;box-shadow:0 2px 10px rgba(0,0,0,.04)}
#up .nasil summary{cursor:pointer;font-weight:800;color:var(--ink);font-size:13px}
#up .nasil ul{margin:8px 0 0;padding-left:18px}
#mrpToast{position:fixed;left:50%;bottom:26px;transform:translateX(-50%) translateY(20px);background:#111827;color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;box-shadow:0 14px 34px rgba(0,0,0,.45);opacity:0;pointer-events:none;transition:.3s cubic-bezier(.22,.68,0,1.2);z-index:9999;max-width:90vw;text-align:center}
#mrpToast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#mrpToast.uyari{background:linear-gradient(135deg,#F59E0B,#d97706)}
/* Reçete gramaj popup */
#rdModal{--card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;--vurgu:#4F46E5;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px}
body.koyu #rdModal{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;--vurgu:#818cf8}
#rdModal.acik{display:flex}
#rdModal .rdm{background:var(--card);width:min(860px,100%);max-height:min(86vh,900px);border-radius:18px;box-shadow:0 24px 60px rgba(15,23,42,.35);display:flex;flex-direction:column;overflow:hidden}
#rdModal .rdm-bas{display:flex;align-items:flex-start;gap:12px;padding:16px 18px 12px;border-bottom:1px solid var(--line)}
#rdModal .rdm-ik{width:38px;height:38px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--vurgu) 12%,transparent);color:var(--vurgu)}
#rdModal .rdm-bas h3{margin:0;font-size:16px;font-weight:800;color:var(--ink)}
#rdModal .rdm-bas p{margin:3px 0 0;font-size:12.5px;color:var(--sub)}
#rdModal .rdm-kapat{margin-left:auto;border:0;background:var(--card2);color:var(--sub);width:34px;height:34px;border-radius:10px;cursor:pointer;flex:none;display:flex;align-items:center;justify-content:center}
#rdModal .rdm-kapat:hover{color:var(--ink)}
#rdModal .rdm-ara{margin:12px 18px 0;padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:13px}
#rdModal .rdm-govde{overflow-y:auto;padding:12px 18px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px;align-content:start}
#rdModal .rd-urun{background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px}
#rdModal .rd-bas{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13.5px;font-weight:800;color:var(--ink);margin-bottom:8px}
#rdModal .rd-por{font-size:11px;font-weight:800;color:var(--vurgu);background:color-mix(in srgb,var(--vurgu) 12%,transparent);border-radius:20px;padding:2px 9px;white-space:nowrap}
#rdModal .rd-k{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--sub);padding:5px 0;border-top:1px dashed var(--line)}
#rdModal .rd-k b{color:var(--ink);white-space:nowrap}
#rdModal .rd-yok{grid-column:1/-1;text-align:center;color:var(--sub);padding:30px;font-size:13px}
</style>
@endpush

@section('content')
<div id="up">
<div class="pano">

  @if (session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if (session('mrp_hata'))<div class="flash err">{{ session('mrp_hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ust">
      <div>
        <span class="rozet-ai"><span class="material-icons-outlined">auto_awesome</span> AKILLI ÜRETİM PLANI</span>
        <h1>Ne kadar satacağını tahmin et, eksik malzemeyi tek tıkla sipariş et.</h1>
        <p>Satış geçmişinden günlere göre talep tahmini yapılır, reçeteler gram gram açılır, depodaki stok düşülür ve yalnızca <b>gerçekten alınması gereken</b> malzeme kalır.</p>
      </div>
      <div class="adimlar">
        <div class="adim akt" id="adim1"><i>1</i> Adetleri belirle</div>
        <div class="adim" id="adim2"><i>2</i> Eksiği gör</div>
        <div class="adim" id="adim3"><i>3</i> Talep gönder</div>
      </div>
    </div>
    <div class="kontrol">
      <span class="klabel">Dönem</span>
      <div class="kseg" id="donem">
        <button data-g="1" onclick="donemSec(this)">Yarın</button>
        <button data-g="7" class="akt" onclick="donemSec(this)">7 Gün</button>
        <button data-g="30" onclick="donemSec(this)">30 Gün</button>
      </div>
      <span class="klabel">Güvenlik payı</span>
      <div class="kseg" id="pay">
        <button data-p="0" onclick="paySec(this)">%0</button>
        <button data-p="10" class="akt" onclick="paySec(this)">%10</button>
        <button data-p="20" onclick="paySec(this)">%20</button>
      </div>
      <button class="btn-ai" onclick="akilliDoldur()"><span class="material-icons-outlined">auto_awesome</span> Akıllı Tahminle Doldur</button>
      <button class="btn-ghost" onclick="temizle()"><span class="material-icons-outlined">restart_alt</span> Temizle</button>
    </div>
    <div class="tahmin-bilgi" id="tahminBilgi"></div>
  </div>

  <div class="kpi">
    <div class="kpi-k" style="--c:#4F46E5"><div class="kpi-ik"><span class="material-icons-outlined">restaurant_menu</span></div><div><b id="kUrun">0</b><span>planlanan ürün</span></div></div>
    <div class="kpi-k" style="--c:#7C3AED"><div class="kpi-ik"><span class="material-icons-outlined">ramen_dining</span></div><div><b id="kAdet">0</b><span>toplam porsiyon</span></div></div>
    <div class="kpi-k" style="--c:#F43F5E"><div class="kpi-ik"><span class="material-icons-outlined">shopping_basket</span></div><div><b id="kEksik">–</b><span>alınacak malzeme</span></div></div>
    <div class="kpi-k" style="--c:#F59E0B"><div class="kpi-ik"><span class="material-icons-outlined">payments</span></div><div><b id="kTutar">–</b><span>tahmini sipariş tutarı</span></div></div>
  </div>

  <div class="grid">
    {{-- SOL: ürün + adet --}}
    <div class="kart">
      <div class="kart-bas"><span class="no">1</span><div><h2>Ne kadar üreteceksin?</h2><small>Adet gir ya da ✨ öneriye dokun — sağ taraf anında güncellenir</small></div></div>
      <div class="arac">
        <input class="urun-ara" placeholder="Ürün ara..." oninput="ara(this.value)">
        <div class="filtre" id="filtre">
          <button data-f="hepsi" class="akt" onclick="filtreSec(this)">Tümü</button>
          <button data-f="plan" onclick="filtreSec(this)">Planlananlar</button>
          <button data-f="oneri" onclick="filtreSec(this)">Önerisi olanlar</button>
        </div>
      </div>
      <div class="urun-liste" id="urunListe">
        @forelse ($urunler as $u)
          @php $vbid = (int) ($u->verim_birim_id ?? 0); @endphp
          <div class="urun-sat" data-ad="{{ mb_strtolower($u->ad) }}" data-urun="{{ $u->id }}">
            <div class="bilgi">
              <div class="ad">{{ $u->ad }}</div>
              <div class="oneri" data-oneri-yer></div>
            </div>
            <div class="adet-kutu">
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
          </div>
        @empty
          <div class="bos-liste">Reçetesi tanımlı aktif ürün yok.<br>Önce <b>Reçeteler</b> ekranından reçete tanımla.</div>
        @endforelse
      </div>
    </div>

    {{-- SAĞ: malzeme ihtiyacı --}}
    <div class="kart sag-kart">
      <div class="kart-bas"><span class="no">2</span><div><h2>Malzeme ihtiyacı</h2><small>Reçeteler açıldı, depodaki stok düşüldü</small></div>
        <button type="button" class="rd-ac sag" id="rdBtn" onclick="receteDetayAc()" style="display:none"><span class="material-icons-outlined">menu_book</span>Reçete detayı</button></div>
      <div id="sonuc">
        <div class="bos-sonuc"><div class="ik"><span class="material-icons-outlined">insights</span></div>
          <b>Henüz plan yok.</b><br>Yukarıdaki <b>Akıllı Tahminle Doldur</b>'a bas<br>ya da soldan ürün adetlerini gir.</div>
      </div>
    </div>
  </div>

  <details class="nasil">
    <summary>Akıllı tahmin nasıl hesaplanıyor?</summary>
    <ul>
      <li><b>Haftanın günü etkisi:</b> son 8 haftada her ürünün Pazartesi, Cuma, Cumartesi… satışları ayrı ayrı ortalanır. "7 Gün" seçiliyse önümüzdeki 7 günün her biri kendi gününün ortalamasıyla toplanır.</li>
      <li><b>Kapalı günler sayılmaz:</b> hiç satış olmayan günler ortalamayı düşürmez.</li>
      <li><b>Trend:</b> son 2 haftanın satışı önceki 2 haftaya göre arttıysa ya da düştüyse tahmin buna göre (en fazla ±%30) ayarlanır.</li>
      <li><b>Güvenlik payı:</b> stok bitmesin diye seçtiğin oranda fazladan eklenir.</li>
      <li><b>Güven:</b> 28+ satış günü verisi = yüksek, 10+ = orta, daha azı = düşük. Düşük güvende öneriyi kontrol ederek kullan.</li>
    </ul>
  </details>

</div>
</div>

<script>
const MRP_CSRF=document.querySelector('meta[name=csrf-token]').content;
let TAHMIN={!! json_encode((object) ($akilli ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
let sonPlan=[], sonDetay=[], sonSonuc=null, sekme='e', akilliDolu=false, hesapZaman=null, hesapNo=0;
const GUVEN_AD={yuksek:'Güven: yüksek',orta:'Güven: orta',dusuk:'Güven: düşük'};
function rdEsc(v){return String(v==null?'':v).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function tl(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(v)+' ₺';}
function sayi(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:2}).format(v);}
function aktifGun(){return +document.querySelector('#donem button.akt').dataset.g;}
function aktifPay(){return +document.querySelector('#pay button.akt').dataset.p;}
function donemAd(){const g=aktifGun();return g===1?'yarın':(g===7?'önümüzdeki 7 gün':'önümüzdeki 30 gün');}
function toast(msg,uyari){
  let t=document.getElementById('mrpToast');
  if(!t){t=document.createElement('div');t.id='mrpToast';document.body.appendChild(t);}
  t.textContent=msg; t.className='show'+(uyari?' uyari':'');
  clearTimeout(window._mtt); window._mtt=setTimeout(()=>t.className='',3600);
}

/* ---- tahmin ---- */
function oneriCiz(){
  let veriGun=0, guven=null;
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const t=TAHMIN[s.dataset.urun], yer=s.querySelector('[data-oneri-yer]');
    s.dataset.oneri=t?t.oneri:0;
    if(!t){yer.innerHTML='<span class="oneri-yok">Satış geçmişi yok — elle gir</span>';return;}
    veriGun=Math.max(veriGun,t.veri_gun||0); guven=t.guven;
    let h='<button type="button" class="oneri-btn" onclick="oneriUygula(this)" title="Öneriyi uygula"><span class="material-icons-outlined">auto_awesome</span>Öneri '+sayi(t.oneri)+'</button>';
    if(t.trend>=5) h+='<span class="trend-up">↑ %'+t.trend+'</span>'; else if(t.trend<=-5) h+='<span class="trend-dn">↓ %'+Math.abs(t.trend)+'</span>';
    h+='<span>günde ~'+sayi(t.gunluk_ort)+'</span>';
    if(t.en_yogun) h+='<span>· en yoğun '+rdEsc(t.en_yogun)+'</span>';
    yer.innerHTML=h;
  });
  const b=document.getElementById('tahminBilgi');
  if(veriGun>0) b.innerHTML='<span class="material-icons-outlined" style="font-size:16px">query_stats</span> Son 8 haftada <b>'+veriGun+' satış günü</b> incelendi · öneriler <b>'+donemAd()+'</b> için, <b>%'+aktifPay()+'</b> güvenlik payıyla <span class="guven '+guven+'">'+GUVEN_AD[guven]+'</span>';
  else b.innerHTML='<span class="material-icons-outlined" style="font-size:16px">info</span> Son 8 haftada satış geçmişi bulunamadı — adetleri elle gir, malzeme hesabı yine çalışır.';
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
  if(!n){toast('⚠️ Son 8 haftada satış geçmişi yok. Adetleri elle gir.',true);return;}
  akilliDolu=true; satirlariBoya(); hesaplaGecikmeli(50);
  if(!sessiz) toast('✨ '+n+' ürün '+donemAd()+' tahminiyle dolduruldu');
}
function oneriUygula(b){const s=b.closest('.urun-sat');const inp=s.querySelector('.adet');inp.value=s.dataset.oneri;degisti(inp);}
function temizle(){document.querySelectorAll('#urunListe .adet').forEach(i=>i.value=0);akilliDolu=false;satirlariBoya();hesaplaGecikmeli(0);}

/* ---- sol liste ---- */
function adim(b,d){const inp=b.parentElement.querySelector('.adet');inp.value=Math.max(0,Math.round(((+inp.value||0)+d)*100)/100);degisti(inp);}
function degisti(inp){akilliDolu=false;satirlariBoya();hesaplaGecikmeli();}
function satirlariBoya(){
  let u=0,a=0;
  document.querySelectorAll('#urunListe .adet').forEach(i=>{const v=+i.value||0;i.closest('.urun-sat').classList.toggle('dolu',v>0);if(v>0){u++;a+=v;}});
  document.getElementById('kUrun').textContent=u; document.getElementById('kAdet').textContent=sayi(a);
  listeFiltre();
}
let aktifFiltre='hepsi', aramaQ='';
function filtreSec(b){document.querySelectorAll('#filtre button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');aktifFiltre=b.dataset.f;listeFiltre();}
function ara(q){aramaQ=(q||'').toLocaleLowerCase('tr');listeFiltre();}
function listeFiltre(){
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    let g=s.dataset.ad.includes(aramaQ);
    if(g&&aktifFiltre==='plan') g=s.classList.contains('dolu');
    if(g&&aktifFiltre==='oneri') g=(+s.dataset.oneri||0)>0;
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
  document.querySelectorAll('#urunListe .adet').forEach(inp=>{
    const adet=+inp.value||0;
    if(adet>0){
      const sel=inp.closest('.adet-kutu').querySelector('.birim');
      const birim_id=sel?(+sel.value):(+inp.dataset.vbirim||0);
      plan.push({urun_id:+inp.dataset.urun,recete_id:+inp.dataset.recete,adet:adet,birim_id:birim_id});
    }
  });
  return plan;
}

/* ---- hesap (otomatik) ---- */
function hesaplaGecikmeli(ms){clearTimeout(hesapZaman);hesapZaman=setTimeout(hesapla,ms==null?550:ms);}
function adimlar(n){[1,2,3].forEach(i=>{const e=document.getElementById('adim'+i);e.classList.toggle('akt',i<=n);e.classList.toggle('tamam',i<n);});}
async function hesapla(){
  const plan=planTopla(), kutu=document.getElementById('sonuc');
  if(!plan.length){
    hesapNo++; sonPlan=[];sonDetay=[];sonSonuc=null;adimlar(1); // geç dönen eski hesap boş ekranı ezmesin
    document.getElementById('rdBtn').style.display='none';
    document.getElementById('kEksik').textContent='–';document.getElementById('kTutar').textContent='–';
    kutu.innerHTML='<div class="bos-sonuc"><div class="ik"><span class="material-icons-outlined">insights</span></div><b>Henüz plan yok.</b><br>Yukarıdaki <b>Akıllı Tahminle Doldur</b>\'a bas<br>ya da soldan ürün adetlerini gir.</div>';
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
function sekmeSec(s){sekme=s;ciz();}
function ciz(){
  const j=sonSonuc||{}, rows=j.rows||[], kutu=document.getElementById('sonuc');
  sonDetay=j.detay||[];
  document.getElementById('rdBtn').style.display=sonDetay.length?'':'none';
  const eksik=rows.filter(x=>!x.yeterli), yeter=rows.filter(x=>x.yeterli);
  document.getElementById('kEksik').textContent=eksik.length;
  document.getElementById('kTutar').textContent=tl(j.toplam||0);
  adimlar(eksik.length?3:2);
  if(!rows.length){kutu.innerHTML='<div class="bos-sonuc"><div class="ik"><span class="material-icons-outlined">block</span></div>Bu ürünlerin reçetesinde stok takipli malzeme yok.</div>';return;}
  if(sekme==='e'&&!eksik.length) sekme='y';
  const liste=sekme==='e'?eksik:yeter;
  let h='<div class="sekme"><button class="'+(sekme==='e'?'akt e':'')+'" onclick="sekmeSec(\'e\')"><span class="material-icons-outlined" style="font-size:17px">shopping_cart</span>Alınacak <em>'+eksik.length+'</em></button>'+
    '<button class="'+(sekme==='y'?'akt y':'')+'" onclick="sekmeSec(\'y\')"><span class="material-icons-outlined" style="font-size:17px">check_circle</span>Stok yeterli <em>'+yeter.length+'</em></button></div>';
  h+='<div class="mal-liste">';
  liste.forEach(x=>{
    const lz=+x.lazim_temel||0, mv=+x.mevcut_temel||0, oran=lz>0?Math.min(100,Math.round(mv/lz*100)):100;
    const renk=oran>=100?'#10B981':(oran>=50?'#F59E0B':'#F43F5E');
    h+='<div class="mal"><div class="mal-ust"><div class="mal-ad">'+rdEsc(x.ad)+'</div>'+
      (x.yeterli?'<span class="mal-al y">✓ yeterli</span>':'<span class="mal-al e">'+sayi(x.net)+' '+rdEsc(x.birim)+' al</span>')+'</div>'+
      '<div class="bar" title="Stok ihtiyacın %'+oran+'\'ini karşılıyor"><i style="width:'+Math.max(3,oran)+'%;background:'+renk+'"></i></div>'+
      '<div class="mal-alt"><span>Lazım <b>'+sayi(x.lazim)+' '+rdEsc(x.birim)+'</b></span><span>Stokta <b>'+sayi(x.mevcut)+' '+rdEsc(x.mevcut_birim||x.birim)+'</b></span><span>%'+oran+' karşılanıyor</span>'+
      (x.tahmini_maliyet>0?'<span class="tl">~'+tl(x.tahmini_maliyet)+'</span>':'')+'</div></div>';
  });
  h+='</div>';
  if(eksik.length){
    h+='<div class="talep-kart"><h3><span class="material-icons-outlined">send</span>Satın alma talebine dönüştür</h3>'+
      '<p>'+eksik.length+' eksik malzeme, net miktarlarıyla tek talep olarak satın almaya gider (~'+tl(j.toplam||0)+').</p>'+
      '<form method="POST" action="/uretim-plani/talep" class="talep-form" onsubmit="return talepGonder(this)">'+
      '<input type="hidden" name="_token" value="'+MRP_CSRF+'"><input type="hidden" name="plan_json" value="">'+
      '<div class="fld"><label>Ne zaman elinde olsun?</label><input type="date" name="teslim_tarihi"></div>'+
      '<div class="fld" style="flex:1;min-width:160px"><label>Not (isteğe bağlı)</label><input type="text" name="not" placeholder="ör. hafta sonu menüsü için"></div>'+
      '<button type="submit" class="btn-yesil"><span class="material-icons-outlined">send</span> Talep Oluştur</button>'+
      '</form></div>';
  }else{
    h+='<div class="tamam-not"><span class="material-icons-outlined">verified</span>Stok bu plan için yeterli — satın almaya gerek yok.</div>';
  }
  kutu.innerHTML=h;
}
function talepGonder(f){f.plan_json.value=JSON.stringify(sonPlan);return true;}

/* ---- reçete gramaj popup ---- */
function receteDetayAc(){
  let m=document.getElementById('rdModal');
  if(!m){
    m=document.createElement('div');m.id='rdModal';
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
function rdFiltre(q){q=(q||'').toLocaleLowerCase('tr');let n=0;document.querySelectorAll('#rdGovde .rd-urun').forEach(x=>{const g=x.dataset.ara.includes(q);x.style.display=g?'':'none';if(g)n++;});document.getElementById('rdYok').style.display=n?'none':'';}

oneriCiz();
</script>
@endsection

@extends('layout.app')
@section('rk_ikon', 'mark_email_read')
@section('baslik', 'Gelen Teklifler')
@section('title', 'Gelen Teklifler')

@push('head')
<style>
#gt{--gt-yesil:#10B981;--gt-amber:#F59E0B;--gt-mor:#7C3AED;--gt-mavi:#4F46E5;--gt-kirmizi:#EF4444;--gt-pembe:#EC4899}
/* HERO */
#gt .gt-hero{position:relative;overflow:hidden;border-radius:20px;padding:20px 22px;color:#fff;background:linear-gradient(130deg,#6D28D9 0%,#4F46E5 48%,#0EA5E9 100%);box-shadow:0 14px 34px rgba(79,70,229,.25);margin-bottom:16px}
#gt .gt-hero:after{content:"";position:absolute;right:-60px;top:-80px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(255,255,255,.22),transparent 70%);pointer-events:none}
#gt .gt-hust{display:flex;gap:14px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;position:relative;z-index:1}
#gt .gt-hero h2{margin:0;font-size:20px;font-weight:800;display:flex;align-items:center;gap:8px}
#gt .gt-hero p{margin:5px 0 0;font-size:13px;opacity:.9;max-width:620px;line-height:1.5}
#gt .gt-aks{display:flex;gap:8px;flex-wrap:wrap}
#gt .gt-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:11px;padding:9px 14px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;color:#fff;background:var(--gt-mor);white-space:nowrap;font-family:inherit;transition:transform .12s,box-shadow .12s}
#gt .gt-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.12)}
#gt .gt-btn .material-icons-outlined{font-size:18px}
#gt .gt-btn.cam{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.35);backdrop-filter:blur(6px)}
#gt .gt-btn.beyaz{background:#fff;color:#4F46E5}
#gt .gt-btn.ikincil{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#gt .gt-btn.yesil{background:linear-gradient(135deg,#10B981,#059669)}
#gt .gt-btn.sm{padding:6px 10px;font-size:12px;border-radius:9px}
#gt .gt-btn.sm .material-icons-outlined{font-size:15px}
#gt .gt-btn:disabled{opacity:.55;cursor:default;transform:none}
#gt .gt-kpi{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:16px;position:relative;z-index:1}
#gt .gt-kpi div{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);border-radius:14px;padding:10px 12px}
#gt .gt-kpi small{display:flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;opacity:.9}
#gt .gt-kpi small .material-icons-outlined{font-size:16px}
#gt .gt-kpi b{display:block;font-size:20px;font-weight:800;margin-top:3px;letter-spacing:-.3px}
#gt .gt-nasil{display:none;position:relative;z-index:1;margin-top:14px}
#gt .gt-nasil.acik{display:block}
#gt .gt-adimlar{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
#gt .gt-adim{background:rgba(255,255,255,.95);color:#1e1b4b;border-radius:12px;padding:10px 12px;font-size:12.5px;line-height:1.5}
#gt .gt-adim b.n{display:inline-flex;min-width:20px;height:20px;border-radius:10px;padding:0 5px;background:var(--gt-mor);color:#fff;font-size:11px;align-items:center;justify-content:center;margin-right:6px}
#gt .gt-adim.ai{box-shadow:0 0 0 3px #FBBF24}
/* DÜZEN */
#gt .gt-duzen{display:grid;grid-template-columns:340px minmax(0,1fr);gap:16px;align-items:start}
#gt .gt-sol{position:sticky;top:10px;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:12px;max-height:calc(100vh - 110px);display:flex;flex-direction:column}
#gt .gt-filtre{display:flex;gap:6px;margin-bottom:8px}
#gt .gt-sekme{flex:1;border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:10px;padding:7px 10px;font-weight:700;font-size:12.5px;cursor:pointer;font-family:inherit}
#gt .gt-sekme.on{background:var(--gt-mor);border-color:var(--gt-mor);color:#fff}
#gt .gt-sekme span{display:inline-block;min-width:18px;border-radius:9px;background:rgba(0,0,0,.08);margin-left:4px;font-size:11px;padding:1px 5px}
#gt .gt-sekme.on span{background:rgba(255,255,255,.25)}
#gt .gt-ara{width:100%;box-sizing:border-box;border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:10px;padding:8px 12px;font-size:13px;margin-bottom:10px;font-family:inherit}
#gt .gt-liste{overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding-right:2px}
#gt .gt-li{all:unset;box-sizing:border-box;display:block;width:100%;cursor:pointer;border-radius:14px;padding:11px 12px 11px 14px;background:var(--card2);border:1px solid transparent;position:relative;transition:background .12s,border-color .12s}
#gt .gt-li:before{content:"";position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:4px;background:var(--lr,#94a3b8)}
#gt .gt-li:hover{border-color:rgba(124,58,237,.35)}
#gt .gt-li.on{background:linear-gradient(135deg,rgba(124,58,237,.14),rgba(14,165,233,.10));border-color:rgba(124,58,237,.55)}
#gt .gt-li .r1{display:flex;justify-content:space-between;gap:8px;align-items:center}
#gt .gt-li .r1 b{font-size:13.5px;color:var(--ink);font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#gt .gt-li .r2{font-size:11.5px;color:var(--sub);margin-top:3px}
#gt .gt-li .pr{height:5px;border-radius:5px;background:rgba(148,163,184,.25);margin-top:8px;overflow:hidden}
#gt .gt-li .pr i{display:block;height:100%;border-radius:5px;background:var(--lr)}
#gt .gt-li .r3{display:flex;justify-content:space-between;font-size:11.5px;color:var(--sub);margin-top:6px}
#gt .gt-li .r3 b{color:var(--ink)}
#gt .gt-li .etk{display:inline-block;margin-top:6px;font-size:10.5px;font-weight:800;color:#be185d;background:rgba(236,72,153,.12);border-radius:20px;padding:2px 8px}
#gt .gt-cip{font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;white-space:nowrap}
#gt .gt-cip.etk{background:rgba(236,72,153,.13);color:#be185d}
#gt .gt-cip.dur{background:rgba(79,70,229,.13);color:var(--gt-mavi)}
#gt .gt-cip.ok{background:rgba(16,185,129,.15);color:#047857}
#gt .gt-cip.bek{background:rgba(245,158,11,.17);color:#92400e}
#gt .gt-cip.mavi{background:rgba(14,165,233,.15);color:#0369a1}
/* DETAY */
#gt .gt-det{background:var(--card);border:1px solid var(--line);border-radius:18px;overflow:hidden}
#gt .gt-dbas{padding:18px 20px;background:linear-gradient(135deg,rgba(124,58,237,.10),rgba(14,165,233,.08));border-bottom:1px solid var(--line);display:flex;gap:16px;justify-content:space-between;flex-wrap:wrap;align-items:center}
#gt .gt-dbas h3{margin:0;font-size:18px;font-weight:800;color:var(--ink);display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#gt .gt-meta{font-size:12px;color:var(--sub);margin-top:6px;display:flex;gap:12px;flex-wrap:wrap}
#gt .gt-meta span{display:inline-flex;align-items:center;gap:4px}
#gt .gt-meta .material-icons-outlined{font-size:15px}
#gt .gt-ozet{display:flex;gap:10px;align-items:stretch;flex-wrap:wrap}
#gt .gt-oz{border-radius:14px;padding:8px 14px;background:var(--card);border:1px solid var(--line);min-width:120px}
#gt .gt-oz small{display:block;font-size:11px;font-weight:700;color:var(--sub)}
#gt .gt-oz b{font-size:17px;color:var(--ink)}
#gt .gt-oz.yesil{background:linear-gradient(135deg,#10B981,#059669);border:0}
#gt .gt-oz.yesil small,#gt .gt-oz.yesil b{color:#fff}
#gt .gt-bol{padding:16px 20px}
#gt .gt-bol + .gt-bol{border-top:1px solid var(--line)}
#gt .gt-bb{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;flex-wrap:wrap}
#gt .gt-bb h4{margin:0;font-size:13.5px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:6px}
#gt .gt-bb h4 .material-icons-outlined{font-size:18px;color:var(--gt-mor)}
#gt .gt-bb small{font-size:11.5px;color:var(--sub)}
#gt .gt-tedler{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
#gt .gt-tk{border-radius:16px;border:1px solid var(--line);background:var(--card);padding:14px;position:relative;overflow:hidden;display:flex;flex-direction:column;gap:10px}
#gt .gt-tk:before{content:"";position:absolute;left:0;right:0;top:0;height:4px;background:var(--c)}
#gt .gt-tk.enIyi{border-color:#10B981;box-shadow:0 0 0 2px rgba(16,185,129,.25)}
#gt .gt-tk .th{display:flex;gap:10px;align-items:center}
#gt .gt-av{flex:0 0 38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;background:var(--c);font-size:16px}
#gt .gt-tk .th b{display:block;font-size:14px;color:var(--ink);cursor:pointer}
#gt .gt-tk .th b:hover{text-decoration:underline}
#gt .gt-tk .th small{display:block;font-size:11px;color:var(--sub);margin-top:1px}
#gt .gt-tk .rozet{position:absolute;top:12px;right:12px;font-size:10px;font-weight:800;color:#fff;background:#10B981;border-radius:20px;padding:3px 8px}
#gt .gt-tk .tot{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-.3px}
#gt .gt-tk .tot small{font-size:11.5px;font-weight:700;color:var(--sub);margin-left:4px;letter-spacing:0}
#gt .gt-tk .bekl{font-size:13px;font-weight:700;color:#b45309;background:rgba(245,158,11,.12);border-radius:10px;padding:8px 10px}
#gt .gt-tk .tm{display:grid;grid-template-columns:1fr 1fr;gap:6px}
#gt .gt-tk .tm div{background:var(--card2);border-radius:10px;padding:6px 8px;font-size:12px;color:var(--ink);font-weight:700}
#gt .gt-tk .tm small{display:block;font-size:10.5px;color:var(--sub);font-weight:700}
#gt .gt-tk .ta{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto}
#gt .gt-urunler{display:flex;flex-direction:column;gap:10px}
#gt .gt-ur{display:grid;grid-template-columns:200px minmax(0,1fr);gap:14px;align-items:center;padding:12px 14px;border-radius:14px;background:var(--card2)}
#gt .gt-ur .ub b{display:block;font-size:14px;color:var(--ink)}
#gt .gt-ur .ub small{display:block;font-size:11.5px;color:var(--sub);margin-top:2px}
#gt .gt-ur .ub .tas{display:inline-block;margin-top:5px;font-size:10.5px;font-weight:800;color:#047857;background:rgba(16,185,129,.14);border-radius:20px;padding:2px 8px}
#gt .gt-bars{display:flex;flex-direction:column;gap:6px}
#gt .gt-bar{display:grid;grid-template-columns:140px minmax(0,1fr) 150px;gap:10px;align-items:center;border-radius:10px;padding:5px 8px;cursor:pointer}
#gt .gt-bar:hover{background:rgba(124,58,237,.07)}
#gt .gt-bar.sec{box-shadow:inset 0 0 0 2px var(--gt-mor);background:rgba(124,58,237,.06)}
#gt .gt-bar .tad{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--ink);overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
#gt .gt-bar .tad i{flex:0 0 10px;height:10px;border-radius:3px;background:var(--c)}
#gt .gt-bar .cub{height:14px;border-radius:7px;background:rgba(148,163,184,.18);overflow:hidden}
#gt .gt-bar .cub i{display:block;height:100%;border-radius:7px;background:var(--c);opacity:.85}
#gt .gt-bar.ucuz .cub i{background:linear-gradient(90deg,#34D399,#059669);opacity:1}
#gt .gt-bar .fy{text-align:right;font-size:13px;font-weight:800;color:var(--ink);white-space:nowrap}
#gt .gt-bar .fy small{display:block;font-size:10.5px;font-weight:600;color:var(--sub)}
#gt .gt-bar.ucuz .fy{color:#047857}
#gt .gt-bar .rz{font-size:9.5px;font-weight:800;background:#10B981;color:#fff;border-radius:6px;padding:1px 5px;margin-left:4px;vertical-align:middle}
#gt .gt-bar.yok{cursor:default}
#gt .gt-bar.yok:hover{background:none}
#gt .gt-bar.yok .cub{background:repeating-linear-gradient(90deg,rgba(148,163,184,.28) 0 8px,transparent 8px 14px)}
#gt .gt-bar.yok .fy{color:var(--sub);font-weight:600;font-size:12px}
#gt .gt-eslesmeyen{margin-top:12px;padding:10px 12px;border-radius:12px;background:rgba(245,158,11,.12);font-size:12.5px;color:var(--ink)}
#gt .gt-ipucu{font-size:12px;color:var(--sub);margin-top:10px}
#gt .gt-bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:40px 20px;text-align:center;color:var(--sub)}
#gt .gt-bos .material-icons-outlined{font-size:46px;color:var(--gt-mor)}
#gt .gt-lbos{font-size:12.5px;color:var(--sub);text-align:center;padding:20px 8px}
#gt .gt-yeni{position:sticky;top:8px;z-index:20;display:none;margin-bottom:12px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer;text-align:center;box-shadow:0 8px 20px rgba(16,185,129,.3)}
#gt details.gt-test{margin-top:18px;font-size:12.5px;color:var(--sub)}
#gt details.gt-test summary{cursor:pointer}
.gt-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.gt-modal .ic{background:var(--card);color:var(--ink);border-radius:16px;width:100%;max-width:560px;max-height:88vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.gt-modal .mb{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--line);font-weight:800;font-size:15px}
.gt-modal .mb button{border:0;background:none;color:var(--sub);cursor:pointer;font-size:24px;line-height:1}
.gt-modal .mg{padding:14px 18px;font-size:13px}
.gt-modal .bilgi{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}
.gt-modal .bilgi div{background:var(--card2);border-radius:10px;padding:8px 10px}
.gt-modal .bilgi small{display:block;color:var(--sub);font-size:11px;font-weight:700}
.gt-modal table{width:100%;border-collapse:collapse}
.gt-modal td{padding:8px 4px;border-bottom:1px solid var(--line);vertical-align:top}
.gt-modal .kay{font-size:10.5px;font-weight:800;border-radius:6px;padding:2px 6px;background:var(--card2);color:var(--sub);white-space:nowrap}
.gt-modal .kay.ai{background:rgba(124,58,237,.12);color:#6d28d9}
.gt-modal .ham{font-size:11.5px;color:var(--sub);background:var(--card2);border-radius:8px;padding:6px 8px;margin-top:4px;white-space:pre-line}
.gt-modal .ma{display:flex;gap:8px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid var(--line)}
.gt-modal .gt-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:8px;padding:7px 12px;font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:none;color:#fff;background:#7C3AED;font-family:inherit}
.gt-modal .gt-btn.ikincil{background:var(--card);color:var(--ink);border:1px solid var(--line)}
.gt-modal .gt-btn:disabled{opacity:.55}
#gtToast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:10000;color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);display:none;max-width:calc(100% - 32px)}
@media (max-width:1200px){
  #gt .gt-kpi{grid-template-columns:repeat(3,1fr)}
  #gt .gt-ur{grid-template-columns:1fr}
}
@media (max-width:980px){
  #gt .gt-duzen{grid-template-columns:1fr}
  #gt .gt-sol{position:static;max-height:none}
  #gt .gt-liste{max-height:300px}
  #gt .gt-adimlar{grid-template-columns:1fr 1fr}
}
@media (max-width:620px){
  #gt .gt-hero{padding:16px}
  #gt .gt-kpi{grid-template-columns:1fr 1fr}
  #gt .gt-adimlar{grid-template-columns:1fr}
  #gt .gt-bar{grid-template-columns:90px minmax(0,1fr) 105px;gap:6px}
  #gt .gt-dbas,#gt .gt-bol{padding:14px}
  .gt-modal .bilgi{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="rk-body"><div id="gt">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="gt-yeni" id="gtYeni" onclick="location.reload()">🔔 Yeni teklif geldi — görmek için dokun</div>

  <div class="gt-hero">
    <div class="gt-hust">
      <div>
        <h2><span class="material-icons-outlined">mark_email_read</span> Gelen Teklifler</h2>
        <p>Her fiyat isteği <b>kendi talebinin altında</b>: soldan talebi seç, sağda kim ne fiyat verdi, kim bekliyor, hangi ürün kimde en ucuz — hepsini gör.</p>
      </div>
      <div class="gt-aks">
        <button class="gt-btn cam" type="button" onclick="document.getElementById('gtNasil').classList.toggle('acik')"><span class="material-icons-outlined">help_outline</span> Nasıl çalışır?</button>
        <form method="POST" action="/gelen-teklifler/oku" style="margin:0" onsubmit="this.querySelector('button').disabled=true;this.querySelector('b').textContent='AI tarıyor…'">
          @csrf<button class="gt-btn cam" type="submit" title="Tedarikçinin WhatsApp / e-postaya YAZIYLA verdiği fiyatları AI okur ve ilgili talebe yerleştirir"><span class="material-icons-outlined">auto_awesome</span> <b>Yazılı cevapları AI ile tara</b></button>
        </form>
        <a href="/satinalma-talep" class="gt-btn beyaz"><span class="material-icons-outlined">request_quote</span> Taleplere git</a>
      </div>
    </div>
    <div class="gt-kpi" id="gtKpi"></div>
    <div class="gt-nasil" id="gtNasil">
      <div class="gt-adimlar">
        <div class="gt-adim"><b class="n">1</b><b>Fiyat isteği gider.</b> Satın Alma Talepleri'nde talebi açıp "Fiyat Araştır" → tedarikçilere WhatsApp + e-posta ile kişiye özel teklif linki gider.</div>
        <div class="gt-adim"><b class="n">2</b><b>Tedarikçi formu doldurur.</b> Linkten birim fiyat, teslim tarihi ve vadeyi girer → fiyat <b>anında</b> buraya ve talebin içine düşer.</div>
        <div class="gt-adim ai"><b class="n">AI</b><b>Yazıyla cevap verirse AI okur.</b> "Tuz 30, peynir 280" gibi mesajdan ürün + fiyatı AI çıkarır; sistem gönderen numaradan ve ürün adlarından <b>hangi talebe ait olduğunu kendisi bulur</b> — kimse kod yazmaz.</div>
        <div class="gt-adim"><b class="n">3</b><b>Karşılaştır, karar ver.</b> Yeşil çubuk = en ucuz. "Talepte aç" → teklifler orada hazır → onaya / siparişe gönder.</div>
      </div>
    </div>
  </div>

  <div class="gt-duzen">
    <aside class="gt-sol">
      <div class="gt-filtre">
        <button class="gt-sekme on" type="button" data-s="aktif" onclick="gtSekme(this)">Aktif <span id="gtSayA">0</span></button>
        <button class="gt-sekme" type="button" data-s="gecmis" onclick="gtSekme(this)">Geçmiş <span id="gtSayG">0</span></button>
      </div>
      <input class="gt-ara" id="gtAra" placeholder="🔎 Talep no, tedarikçi veya ürün…" oninput="gtCiz()">
      <div class="gt-liste" id="gtListe"></div>
    </aside>
    <section id="gtDetay"></section>
  </div>

  @if ($sahip)
  <details class="gt-test">
    <summary>Test araçları (yalnız yönetici)</summary>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
      <form method="POST" action="/rfq-test-kur" style="margin:0" onsubmit="return rkOnayForm(this, 'Test için örnek bir malzeme + tedarikçi oluşturulsun mu? (sonra silebilirsin)', 'var(--mavi)', 'Evet, kur')">@csrf<button class="gt-btn ikincil sm" type="submit">Test verisi kur</button></form>
      <form method="POST" action="/rfq-test-sifirla" style="margin:0" onsubmit="return rkOnayForm(this, 'TÜM test verileri silinsin mi? (gelen teklifler, RFQ&apos;lar, tekliften oluşan siparişler, TEST tedarikçi/malzeme) — gerçek veriler etkilenmez.', 'var(--kirmizi)', 'Evet, sıfırla')">@csrf<button class="gt-btn ikincil sm" type="submit" style="color:var(--gt-kirmizi)">Test verilerini sıfırla</button></form>
      <a href="/fiyat-arastir-sec" class="gt-btn ikincil sm">Talepsiz fiyat araştır (kritik stok)</a>
    </div>
  </details>
  @endif

  <form method="POST" action="/gelen-teklifler/siparis" id="gtSipForm" style="display:none">@csrf</form>
</div></div>
<div id="gtToast"></div>
@endsection

@push('scripts')
<script>
(function(){
const GT = {!! json_encode($gruplar, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!};
let gtSonId = {{ (int) $sonId }}, gtSekmeAktif = 'aktif', gtToastT = null, gtSecili = null;
const gtSecim = {}; // talepsiz gruplarda: ürün → seçili tedarikçi
const RENK = ['#7C3AED', '#0EA5E9', '#F59E0B', '#EC4899', '#14B8A6', '#6366F1', '#F97316', '#84CC16'];
const gtToast = (m, c) => { const t = document.getElementById('gtToast'); t.textContent = m; t.style.background = c || '#10B981'; t.style.display = 'block'; clearTimeout(gtToastT); gtToastT = setTimeout(() => t.style.display = 'none', 4200); };
const para = n => (Number(n) || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
const paraK = n => (Number(n) || 0).toLocaleString('tr-TR', {maximumFractionDigits: 0}) + ' ₺';
const mik = n => (Number(n) || 0).toLocaleString('tr-TR', {maximumFractionDigits: 3});
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const tarihD = s => new Date(String(s).replace(' ', 'T'));
const tarih = s => { if (!s) return ''; const d = tarihD(s); if (isNaN(d)) return s; return d.toLocaleDateString('tr-TR', {day:'2-digit', month:'short'}) + (String(s).length > 10 ? ' ' + d.toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'}) : ''); };
const once = s => { if (!s) return ''; const dk = Math.round((Date.now() - tarihD(s)) / 60000); if (isNaN(dk)) return ''; if (dk < 1) return 'az önce'; if (dk < 60) return dk + ' dk önce'; if (dk < 1440) return Math.round(dk / 60) + ' sa önce'; return Math.round(dk / 1440) + ' gün önce'; };
const kaynakAd = k => k === 'portal' ? ['Teklif formu', ''] : (k === 'wa' || k === 'whatsapp') ? ['WhatsApp · AI okudu', 'ai'] : ['E-posta · AI okudu', 'ai'];
const kanalAd = k => !k ? 'iletilemedi (link elle gönderilmeli)' : k.split('+').map(x => x === 'wa' ? 'WhatsApp' : x === 'mail' ? 'E-posta' : x).join(' + ');
const durumAd = {yeni:'Yeni', departman_onay:'Departman onayında', teklif:'Teklif toplanıyor', onayda:'Patron onayında', fiyat_onay:'Fiyat onayında', onaylandi:'Onaylandı', siparis:'Siparişe döndü', tamamlandi:'Tamamlandı', reddedildi:'Reddedildi'};
const hucreler = (g, mid) => (g.hucre || {})[mid] || {};
// Ürün başına (en pahalı − en ucuz) × miktar: en ucuzu seçince kazanılan
const tasarrufU = (g, u) => { const f = Object.values(hucreler(g, u.id)).map(c => Number(c.fiyat)).filter(x => x > 0); return f.length > 1 ? (Math.max(...f) - Math.min(...f)) * (Number(u.miktar) || 0) : 0; };
const tasarruf = g => g.urunler.reduce((s, u) => s + tasarrufU(g, u), 0);
const durumRenk = g => !g.tedarikciler.length ? '#94a3b8' : g.yanitlayan >= g.tedarikciler.length ? '#10B981' : g.yanitlayan ? '#0EA5E9' : '#F59E0B';

GT.forEach((g, gi) => {
  g.gi = gi;
  g.tedarikciler.forEach((t, ti) => t.renk = RENK[ti % RENK.length]);
  if (!g.talep_id) { gtSecim[gi] = {}; Object.entries(g.en_ucuz || {}).forEach(([mid, tid]) => { if (hucreler(g, mid)[tid]) gtSecim[gi][mid] = tid; }); }
});
try { gtSecili = sessionStorage.getItem('gtSecili'); } catch (e) {}

window.gtSekme = function(b){ document.querySelectorAll('#gt .gt-sekme').forEach(x => x.classList.toggle('on', x === b)); gtSekmeAktif = b.dataset.s; gtCiz(); };

function kpiCiz(){
  const akt = GT.filter(g => !g.gecmis);
  let bek = 0, fiyat = 0, ai = 0, tas = 0;
  akt.forEach(g => {
    g.tedarikciler.forEach(t => { if (t.durum !== 'geldi') bek++; fiyat += Number(t.fiyat_say) || 0; });
    Object.values(g.hucre || {}).forEach(s => Object.values(s).forEach(c => { if (c.kaynak && c.kaynak !== 'portal') ai++; }));
    tas += tasarruf(g);
  });
  const k = (ik, ad, v) => `<div><small><span class="material-icons-outlined">${ik}</span>${ad}</small><b>${v}</b></div>`;
  document.getElementById('gtKpi').innerHTML = k('pending_actions', 'Açık talep', akt.length) + k('hourglass_top', 'Yanıt bekleyen', bek) + k('sell', 'Gelen fiyat', fiyat) + k('auto_awesome', 'AI\'ın okuduğu', ai) + k('savings', 'En ucuzla kazanç', paraK(tas));
}

function liHtml(g){
  const n = g.tedarikciler.length, oran = n ? Math.round(100 * g.yanitlayan / n) : 0;
  return `<button type="button" class="gt-li ${g.anahtar === gtSecili ? 'on' : ''}" style="--lr:${durumRenk(g)}" onclick="gtSecTalep('${esc(g.anahtar)}')">
    <div class="r1"><b>${esc(g.baslik)}</b></div>
    <div class="r2">${g.urunler.length} ürün · ${n} tedarikçi${g.talep_durum ? ' · ' + esc(durumAd[g.talep_durum] || g.talep_durum) : ''}</div>
    ${g.etkinlik ? `<span class="etk">🎉 ${esc(g.etkinlik)}${g.etkinlik_tarih ? ' · ' + tarih(g.etkinlik_tarih) : ''}</span>` : ''}
    <div class="pr"><i style="width:${Math.max(oran, 3)}%"></i></div>
    <div class="r3"><span>${g.yanitlayan}/${n} yanıt${g.son_yanit ? ' · ' + once(g.son_yanit) : ''}</span>${g.en_iyi_toplam > 0 ? `<b>${paraK(g.en_iyi_toplam)}</b>` : ''}</div>
  </button>`;
}

function detayHtml(g){
  const ted = g.tedarikciler, ur = g.urunler, gi = g.gi, tas = tasarruf(g);
  const etk = g.etkinlik ? `<span class="gt-cip etk">🎉 ${esc(g.etkinlik)}${g.etkinlik_tarih ? ' · ' + tarih(g.etkinlik_tarih) : ''}</span>` : '';
  const dur = g.talep_durum ? `<span class="gt-cip dur">${esc(durumAd[g.talep_durum] || g.talep_durum)}</span>` : '';
  const yan = !ted.length ? '' : g.yanitlayan >= ted.length ? `<span class="gt-cip ok">✓ Herkes yanıt verdi</span>` : g.yanitlayan ? `<span class="gt-cip mavi">${g.yanitlayan}/${ted.length} yanıt</span>` : `<span class="gt-cip bek">⏳ Yanıt bekleniyor</span>`;
  let h = `<div class="gt-det"><div class="gt-dbas"><div style="min-width:0">
    <h3>${esc(g.baslik)} ${etk} ${dur} ${yan}</h3>
    <div class="gt-meta"><span><span class="material-icons-outlined">inventory_2</span>${ur.length} ürün</span><span><span class="material-icons-outlined">send</span>İstek: ${tarih(g.gonderim)}</span>${g.son_yanit ? `<span><span class="material-icons-outlined">schedule</span>Son yanıt: ${once(g.son_yanit)}</span>` : ''}${g.isteyen ? `<span><span class="material-icons-outlined">person</span>${esc(g.isteyen)}</span>` : ''}</div>
  </div><div class="gt-ozet">`;
  if (g.en_iyi_toplam > 0) h += `<div class="gt-oz"><small>En ucuzlarla toplam</small><b>${para(g.en_iyi_toplam)}</b></div>`;
  if (tas > 0) h += `<div class="gt-oz yesil"><small>En ucuzu seçince kazanç</small><b>${para(tas)}</b></div>`;
  h += g.talep_id
    ? `<a class="gt-btn" style="align-self:center" href="/satinalma-talep?ac=${g.talep_id}"><span class="material-icons-outlined">open_in_new</span> Talepte aç → onaya gönder</a>`
    : (g.yanitlayan ? `<button class="gt-btn yesil" style="align-self:center" type="button" onclick="gtSiparis(${gi})"><span class="material-icons-outlined">shopping_cart_checkout</span> Seçilenlerle sipariş ver</button>` : '');
  h += `</div></div>`;
  if (!ted.length || !ur.length) return h + `<div class="gt-bol"><div class="gt-lbos">Bu istekte ürün/tedarikçi bilgisi yok.</div></div></div>`;

  // Tedarikçi kartları
  const tamam = ted.filter(t => t.fiyat_say >= ur.length && t.toplam > 0);
  const enIyiT = ted.length > 1 && tamam.length ? tamam.reduce((a, b) => b.toplam < a.toplam ? b : a).id : null;
  h += `<div class="gt-bol"><div class="gt-bb"><h4><span class="material-icons-outlined">storefront</span> Tedarikçiler (${ted.length})</h4><small>İsme tıkla → teklifin tüm detayı</small></div><div class="gt-tedler">`;
  ted.forEach((t, ti) => {
    const geldi = t.durum === 'geldi';
    h += `<div class="gt-tk ${t.id === enIyiT ? 'enIyi' : ''}" style="--c:${t.renk}">
      ${t.id === enIyiT ? '<span class="rozet">★ EN İYİ TOPLAM</span>' : ''}
      <div class="th"><span class="gt-av">${esc((t.ad || '?').trim().charAt(0).toLocaleUpperCase('tr-TR'))}</span><div style="min-width:0"><b onclick="gtTed(${gi},${ti})">${t.anlasmali ? '⭐ ' : ''}${esc(t.ad)}</b><small>${esc(kanalAd(t.kanal))} ile gönderildi</small></div></div>
      ${geldi ? `<div class="tot">${para(t.toplam)}<small>${t.fiyat_say}/${ur.length} ürün</small></div>` : `<div class="bekl">⏳ Yanıt bekleniyor · ${once(t.gonderim)}</div>`}
      <div class="tm"><div><small>Teslim</small>${t.teslim ? tarih(t.teslim) : '—'}</div><div><small>Vade</small>${esc(t.vade || '—')}</div></div>
      <div class="ta">
        <button class="gt-btn ikincil sm" type="button" onclick="gtTed(${gi},${ti})"><span class="material-icons-outlined">visibility</span> Detay</button>
        ${!geldi && t.tel ? `<button class="gt-btn ikincil sm" type="button" onclick="gtHatirlat(${t.rfq_id},this)"><span class="material-icons-outlined">notifications_active</span> Hatırlat</button>` : ''}
        ${t.link ? `<button class="gt-btn ikincil sm" type="button" onclick="gtKopya(${gi},${t.id})"><span class="material-icons-outlined">link</span> Link</button>` : ''}
      </div></div>`;
  });
  h += `</div></div>`;

  // Ürün ürün karşılaştırma (çubuk grafik)
  h += `<div class="gt-bol"><div class="gt-bb"><h4><span class="material-icons-outlined">bar_chart</span> Ürün ürün fiyat karşılaştırma</h4><small>Çubuk uzunluğu = birim fiyat · yeşil = en ucuz${!g.talep_id ? ' · tıkla → o tedarikçiyi seç' : ''}</small></div><div class="gt-urunler">`;
  ur.forEach(u => {
    const satir = hucreler(g, u.id);
    const fiyatlar = Object.values(satir).map(c => Number(c.fiyat)).filter(x => x > 0);
    const maks = fiyatlar.length ? Math.max(...fiyatlar) : 0, cok = fiyatlar.length > 1, tu = tasarrufU(g, u);
    h += `<div class="gt-ur"><div class="ub"><b>${esc(u.ad)}</b><small>İhtiyaç: ${mik(u.miktar)} ${esc(u.birim)}</small>${tu > 0 ? `<span class="tas">↓ ${para(tu)} fark</span>` : ''}</div><div class="gt-bars">`;
    ted.forEach((t, ti) => {
      const c = satir[t.id];
      if (!c) { h += `<div class="gt-bar yok" style="--c:${t.renk}"><span class="tad"><i></i>${esc(t.ad)}</span><div class="cub"></div><span class="fy">${t.durum === 'geldi' ? 'fiyat vermedi' : 'bekleniyor'}</span></div>`; return; }
      const ucuz = cok && String((g.en_ucuz || {})[u.id]) === String(t.id);
      const sec = !g.talep_id && String((gtSecim[gi] || {})[u.id]) === String(t.id);
      const tik = g.talep_id ? `gtTed(${gi},${ti})` : `gtSec(${gi},${u.id},${t.id})`;
      const w = maks ? Math.max(6, Math.round(100 * c.fiyat / maks)) : 6;
      h += `<div class="gt-bar ${ucuz ? 'ucuz' : ''} ${sec ? 'sec' : ''}" style="--c:${t.renk}" onclick="${tik}" title="${g.talep_id ? 'Teklif detayı' : 'Bu teklifi seç'}"><span class="tad"><i></i>${esc(t.ad)}</span><div class="cub"><i style="width:${w}%"></i></div><span class="fy">${para(c.fiyat)}/${esc(u.birim)}${ucuz ? '<span class="rz">EN UCUZ</span>' : ''}<small>${para(c.fiyat * u.miktar)} toplam</small></span></div>`;
    });
    h += `</div></div>`;
  });
  h += `</div>`;
  if ((g.eslesmeyen || []).length) h += `<div class="gt-eslesmeyen">⚠ AI şu fiyatları okudu ama talepteki bir ürünle eşleştiremedi (elle kontrol et): ${g.eslesmeyen.map(e => `<b>${esc(e.ted)}</b>: ${esc(e.urun)} ${para(e.fiyat)}`).join(' · ')}</div>`;
  if (!g.talep_id && g.yanitlayan) h += `<div class="gt-ipucu">💡 En ucuzlar hazır seçili. Başka tedarikçi istersen o satırdaki fiyata tıkla, sonra "Seçilenlerle sipariş ver".</div>`;
  h += `</div></div>`;
  return h;
}

window.gtSecTalep = function(k){
  gtSecili = k; try { sessionStorage.setItem('gtSecili', k); } catch (e) {}
  gtCiz();
  if (window.innerWidth <= 980) { const d = document.getElementById('gtDetay'); if (d) d.scrollIntoView({behavior: 'smooth', block: 'start'}); }
};

window.gtCiz = function(){
  const q = (document.getElementById('gtAra').value || '').toLocaleLowerCase('tr-TR').trim();
  const eslesir = g => !q || [g.baslik, g.etkinlik, g.talep_id ? '#' + g.talep_id : '', ...g.tedarikciler.map(t => t.ad + ' ' + t.kod), ...g.urunler.map(u => u.ad)].join(' ').toLocaleLowerCase('tr-TR').includes(q);
  const akt = GT.filter(g => !g.gecmis), gec = GT.filter(g => g.gecmis);
  document.getElementById('gtSayA').textContent = akt.length; document.getElementById('gtSayG').textContent = gec.length;
  const liste = (gtSekmeAktif === 'aktif' ? akt : gec).filter(eslesir);
  if (!liste.some(g => g.anahtar === gtSecili)) gtSecili = liste.length ? liste[0].anahtar : null;
  document.getElementById('gtListe').innerHTML = liste.length ? liste.map(liHtml).join('') : `<div class="gt-lbos">${q ? 'Aramaya uyan talep yok' : (gtSekmeAktif === 'aktif' ? 'Açık fiyat isteği yok' : 'Geçmiş yok')}</div>`;
  const g = GT.find(x => x.anahtar === gtSecili);
  document.getElementById('gtDetay').innerHTML = g ? detayHtml(g) : `<div class="gt-bos"><span class="material-icons-outlined">mark_email_unread</span><div style="font-weight:800;color:var(--ink);margin-top:6px;font-size:15px">${q ? 'Aramaya uyan teklif yok' : (gtSekmeAktif === 'aktif' ? 'Şu an açık bir fiyat isteği yok' : 'Geçmiş teklif yok')}</div><div style="font-size:12.5px;margin-top:6px">Satın Alma Talepleri'nde bir talebi açıp <b>"Fiyat Araştır"</b>a bas; tedarikçilerin fiyatları buraya düşer.</div><a href="/satinalma-talep" class="gt-btn" style="margin-top:14px">Taleplere git</a></div>`;
};

window.gtSec = function(gi, mid, tid){ gtSecim[gi][mid] = tid; gtCiz(); };

window.gtSiparis = function(gi){
  const g = GT[gi], ids = [];
  Object.entries(gtSecim[gi] || {}).forEach(([mid, tid]) => { const c = hucreler(g, mid)[tid]; if (c) ids.push(c.id); });
  if (!ids.length) { gtToast('Önce en az bir fiyat seç.', '#F59E0B'); return; }
  const f = document.getElementById('gtSipForm');
  f.querySelectorAll('input[name="teklif[]"]').forEach(x => x.remove());
  ids.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'teklif[]'; i.value = id; f.appendChild(i); });
  rkOnay(ids.length + ' ürün, seçtiğin tedarikçilere göre siparişe dönüşsün ve tedarikçilere WhatsApp + e-posta ile gönderilsin mi?', () => HTMLFormElement.prototype.submit.call(f), 'var(--yesil)', 'Evet, sipariş ver');
};

window.gtTed = function(gi, ti){
  const g = GT[gi], t = g.tedarikciler[ti];
  let satir = '';
  g.urunler.forEach(u => {
    const c = hucreler(g, u.id)[t.id];
    if (!c) { satir += `<tr><td>${esc(u.ad)}<div style="font-size:11px;color:var(--sub)">${mik(u.miktar)} ${esc(u.birim)}</div></td><td style="text-align:right;color:var(--sub)">${t.durum === 'geldi' ? 'fiyat vermedi' : 'bekleniyor'}</td></tr>`; return; }
    const [kAd, kCls] = kaynakAd(c.kaynak);
    const ucuz = String((g.en_ucuz || {})[u.id]) === String(t.id) && Object.keys(hucreler(g, u.id)).length > 1;
    satir += `<tr><td>${esc(u.ad)}<div style="font-size:11px;color:var(--sub);margin-top:2px">${mik(u.miktar)} ${esc(u.birim)} · <span class="kay ${kCls}">${kAd}</span> ${once(c.zaman)}</div>${c.metin && c.kaynak !== 'portal' ? `<div class="ham">Tedarikçinin mesajı: ${esc(c.metin)}</div>` : ''}</td><td style="text-align:right;white-space:nowrap"><b style="${ucuz ? 'color:#047857' : ''}">${para(c.fiyat)}</b>/${esc(u.birim)}${ucuz ? ' ✓ en ucuz' : ''}<div style="font-size:11px;color:var(--sub)">${para(c.fiyat * u.miktar)}</div></td></tr>`;
  });
  const m = document.createElement('div'); m.className = 'gt-modal';
  m.innerHTML = `<div class="ic"><div class="mb"><span>${t.anlasmali ? '⭐ ' : ''}${esc(t.ad)}</span><button type="button" data-k aria-label="Kapat">×</button></div><div class="mg">
    <div style="font-size:12px;color:var(--sub);margin:-4px 0 10px">${esc(g.baslik)}${g.etkinlik ? ' · 🎉 ' + esc(g.etkinlik) : ''}</div>
    <div class="bilgi">
      <div><small>Durum</small>${t.durum === 'geldi' ? '✓ Teklif geldi' + (t.yanit ? ' · ' + once(t.yanit) : '') : '⏳ Yanıt bekleniyor'}</div>
      <div><small>İstek gönderildi</small>${tarih(t.gonderim)} · ${esc(kanalAd(t.kanal))}</div>
      <div><small>Teslim tarihi</small>${t.teslim ? tarih(t.teslim) : '—'}</div>
      <div><small>Vade / ödeme</small>${esc(t.vade || '—')}</div>
    </div>
    ${t.not ? `<div class="ham" style="margin:0 0 12px">Tedarikçinin notu: ${esc(t.not)}</div>` : ''}
    <table>${satir}</table>
    <div style="text-align:right;font-weight:800;margin-top:10px">Toplam: ${para(t.toplam)} <span style="font-weight:600;color:var(--sub);font-size:12px">(${t.fiyat_say}/${g.urunler.length} ürün)</span></div>
  </div><div class="ma">
    ${t.link ? `<button class="gt-btn ikincil" type="button" onclick="gtKopya(${gi},${t.id})">🔗 Teklif linkini kopyala</button>` : ''}
    ${t.durum !== 'geldi' && t.tel ? `<button class="gt-btn ikincil" type="button" onclick="gtHatirlat(${t.rfq_id},this)">🔔 WhatsApp'tan hatırlat</button>` : ''}
    ${g.talep_id ? `<a class="gt-btn" href="/satinalma-talep?ac=${g.talep_id}">Talepte aç →</a>` : ''}
  </div></div>`;
  m.addEventListener('click', e => { if (e.target === m || e.target.hasAttribute('data-k')) m.remove(); });
  document.body.appendChild(m);
};

window.gtKopya = function(gi, tid){
  const t = GT[gi].tedarikciler.find(x => x.id === tid); if (!t || !t.link) return;
  (navigator.clipboard ? navigator.clipboard.writeText(t.link) : Promise.reject()).then(() => gtToast('✓ Link kopyalandı — tedarikçiye iletebilirsin')).catch(() => prompt('Linki kopyala:', t.link));
};

window.gtHatirlat = async function(rfq, btn){
  if (btn) btn.disabled = true;
  try {
    const r = await fetch('/gelen-teklifler/hatirlat', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({rfq})});
    const j = await r.json();
    if (j.ok) gtToast('✓ Hatırlatma WhatsApp ile gönderildi');
    else { gtToast('⚠ ' + (j.hata || 'Gönderilemedi'), '#D97706'); if (btn) btn.disabled = false; }
  } catch (e) { gtToast('Bağlantı hatası', '#E11D48'); if (btn) btn.disabled = false; }
};

// Canlı: 30 sn'de bir yeni teklif var mı?
setInterval(async () => {
  try { const r = await fetch('/gelen-teklifler/son?_=' + Date.now(), {cache:'no-store'}); const j = await r.json(); if (j.ok && j.son > gtSonId) document.getElementById('gtYeni').style.display = 'block'; } catch (e) {}
}, 30000);

if (!GT.some(g => !g.gecmis) && GT.some(g => g.gecmis)) { gtSekmeAktif = 'gecmis'; document.querySelectorAll('#gt .gt-sekme').forEach(x => x.classList.toggle('on', x.dataset.s === 'gecmis')); }
kpiCiz();
gtCiz();
})();
</script>
@endpush

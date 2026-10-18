@extends('layout.app')
@section('title', 'Salon Şeması')
@section('rk_ikon', 'grid_on')
@section('baslik', 'Salon Şeması')
@section('rk_alt', 'Restoranın dijital ikizi — kat(lar), salon sınırı, bölge, sabit nokta ve masaları çiz; sürükle-bırak konumlandır. Mobil POS ile birebir aynı kroki.')

@section('rk_araclar')
  <span id="sema-durum" style="font-size:12.5px;color:var(--sub);margin-right:4px"></span>
  <button type="button" id="sema-geri" class="rk-btn bos" disabled>
    <span class="material-icons-outlined" style="font-size:18px">undo</span> Geri al
  </button>
  <button type="button" id="sema-kaydet" class="rk-btn" style="background:var(--mor1)">
    <span class="material-icons-outlined" style="font-size:18px">save</span> Kaydet
  </button>
@endsection

@section('content')
@php
  // Backend degiskenleri: $veri (JSON array|null), $masalar (collection: id,ad,kapasite,sekil)
  // JSON sema: {katlar:[], parsel:[{kat,pts:[[x,y]]}], bolgeler:[{kat,ad,x,y,w,h}],
  //            noktalar:[{kat,tip,ad,x,y,boy?}], masalar:{"id":{kat,x,y,boy}}}  — sanal uzay 0..1000
  $veri     = $veri ?? null;
  $masalar  = $masalar ?? collect();
  $katlar   = (is_array($veri) && !empty($veri['katlar']))   ? array_values($veri['katlar']) : ['Zemin Kat'];
  $parsel   = (is_array($veri) && !empty($veri['parsel']))    ? $veri['parsel']   : [];
  $bolgeler = (is_array($veri) && !empty($veri['bolgeler']))  ? $veri['bolgeler'] : [];
  $noktalar = (is_array($veri) && !empty($veri['noktalar']))  ? $veri['noktalar'] : [];
  $semaMasa = (is_array($veri) && !empty($veri['masalar']) && is_array($veri['masalar'])) ? $veri['masalar'] : [];

  $masaList = [];
  foreach ($masalar as $m) {
    $masaList[] = [
      'id'       => (int) $m->id,
      'ad'       => (string) $m->ad,
      'kapasite' => (int) ($m->kapasite ?? 0),
      'sekil'    => (string) ($m->sekil ?? 'kare'),
    ];
  }

  $bos = !is_array($veri) || (empty($bolgeler) && empty($noktalar) && empty($semaMasa) && empty($parsel));

  // tip => [emoji, renk, etiket]  (Flutter _tipler ile ayni renkler)
  $tipler = [
    'giris'   => ['🚪', '#14B8A6', 'Giriş'],
    'mutfak'  => ['🍳', '#F97316', 'Mutfak'],
    'bar'     => ['🍸', '#7C3AED', 'Bar'],
    'wc'      => ['🚻', '#3B82F6', 'WC'],
    'otopark' => ['🅿️', '#6366F1', 'Otopark'],
    'kasa'    => ['💳', '#10B981', 'Kasa'],
    'depo'    => ['📦', '#92694A', 'Depo'],
    'diger'   => ['📍', '#64748B', 'Diğer'],
  ];
@endphp

<style>
  .ss-wrap{display:grid;gap:16px;grid-template-columns:1fr 320px}
  @media(max-width:900px){.ss-wrap{grid-template-columns:1fr}}
  #kat-sekmeler{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px}
  .ss-kat{padding:6px 14px;border-radius:18px;font-size:12.5px;font-weight:700;cursor:pointer;border:1px solid var(--line);background:var(--card2);color:var(--sub2);display:inline-flex;align-items:center;gap:6px}
  .ss-kat.aktif{background:var(--mor1);color:#fff;border-color:var(--mor1)}
  .ss-kat .material-icons-outlined{font-size:15px}
  .ss-arac{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px}
  .ss-arac .rk-btn{padding:7px 12px}
  #sema-kanvas{position:relative;margin:0 auto;user-select:none;-webkit-user-select:none;touch-action:none;border-radius:14px;border:1px solid var(--line);background:var(--card2);overflow:hidden;width:100%;max-width:560px;aspect-ratio:1 / 1.35}
  #sema-kanvas .izgara{position:absolute;inset:0;pointer-events:none;background-image:linear-gradient(to right,color-mix(in srgb,var(--line) 55%,transparent) 1px,transparent 1px),linear-gradient(to bottom,color-mix(in srgb,var(--line) 55%,transparent) 1px,transparent 1px);background-size:10% 10%}
  .oge{position:absolute}
  .oge-kose{width:20px;height:20px;border-radius:50%;background:#0EA5E9;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.25);cursor:move}
  .oge-tutamak{position:absolute;display:flex;align-items:center;justify-content:center;cursor:nwse-resize}
  .oge-tutamak .material-icons-outlined{font-size:13px}
  .ss-panel-baslik{font-size:13px;font-weight:800;color:var(--ink);margin-bottom:8px}
  #oge-liste{display:flex;flex-direction:column;gap:6px;max-height:420px;overflow:auto;padding-right:2px}
  .ss-liste-sat{display:flex;align-items:center;gap:6px;padding:7px 10px;border-radius:9px;background:var(--card2);cursor:pointer;font-size:13px;color:var(--ink);border:1px solid transparent}
  .ss-liste-sat:hover{border-color:var(--mor1)}
  .ss-liste-sat.aktif{border-color:var(--mor1);background:color-mix(in srgb,var(--mor1) 10%,transparent)}
</style>

<div id="kat-sekmeler"></div>

<div class="ss-wrap">
  {{-- ===== KANVAS ===== --}}
  <div class="rk-card">
    <div class="ss-arac">
      <button type="button" data-ekle="masa"   class="rk-btn" style="background:var(--mor1)"><span class="material-icons-outlined" style="font-size:17px">table_bar</span> Masa Ekle</button>
      <button type="button" data-ekle="bolge"  class="rk-btn" style="background:#0EA5E9"><span class="material-icons-outlined" style="font-size:17px">crop_square</span> Bölge</button>
      <button type="button" data-ekle="nokta"  class="rk-btn" style="background:#F97316"><span class="material-icons-outlined" style="font-size:17px">add_location_alt</span> Nokta</button>
      <button type="button" data-ekle="parsel" class="rk-btn" style="background:#14B8A6"><span class="material-icons-outlined" style="font-size:17px">pentagon</span> Salon Sınırı</button>
      <button type="button" id="kat-temizle" class="rk-btn bos" title="Bu katı temizle"><span class="material-icons-outlined" style="font-size:17px">layers_clear</span></button>
      <button type="button" id="kat-ekle" class="rk-btn bos" style="margin-left:auto"><span class="material-icons-outlined" style="font-size:17px">add</span> Kat</button>
    </div>

    {{-- parsel cizgisi icin SVG overlay; viewBox 1000x1350 (oran 1.35, mobil ile ayni) --}}
    <div id="sema-kanvas">
      <div class="izgara"></div>
      <svg id="parsel-svg" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none" viewBox="0 0 1000 1350" preserveAspectRatio="none"></svg>
    </div>

    {{-- parsel araclari (aktif katta parsel varsa gorunur) --}}
    <div id="parsel-cubuk" style="display:none;align-items:center;gap:8px;flex-wrap:wrap;margin-top:12px;padding:8px 12px;border-radius:10px;background:color-mix(in srgb,#0EA5E9 12%,transparent)">
      <span class="material-icons-outlined" style="font-size:16px;color:#0EA5E9">open_with</span>
      <span style="font-size:12px;color:var(--ink);flex:1">Salon sınırı: köşeleri sürükle</span>
      <button type="button" id="kose-ekle"   class="rk-btn bos" style="padding:5px 10px;color:#0EA5E9">+ Köşe</button>
      <button type="button" id="parsel-sifir" class="rk-btn bos" style="padding:5px 10px;color:#F97316">Sıfırla</button>
      <button type="button" id="parsel-sil"   class="rk-btn bos" style="padding:5px 10px;color:var(--kirmizi)">Sil</button>
    </div>

    <div id="bos-uyari" style="{{ $bos ? '' : 'display:none' }}text-align:center;color:var(--sub);font-size:13px;margin-top:14px">
      Henüz şema çizilmemiş. Yukarıdan öğe ekleyerek başla.
    </div>
  </div>

  {{-- ===== YAN PANEL ===== --}}
  <div class="rk-card">
    <div class="rk-bolum"><span class="cubuk" style="background:var(--mor1)"></span><span class="b" style="font-size:14px">Seçili Öğe</span></div>
    <div id="secili-panel" style="font-size:13px;color:var(--sub)">Bir öğeye tıkla.</div>
    <hr style="border:none;border-top:1px solid var(--line);margin:16px 0">
    <div class="ss-panel-baslik">Öğe Listesi</div>
    <div id="oge-liste"></div>
  </div>
</div>

<script>
(function () {
  "use strict";
  try {
    var KAYDET_URL = "/salon-sema/kaydet";
    var CSRF = (window.csrf || "{{ csrf_token() }}");

    // ---- durum ----
    var state = {
      katlar:   @json($katlar),
      parsel:   @json($parsel),             // [{kat, pts:[[x,y]...]}]
      bolgeler: @json($bolgeler),           // [{kat,ad,x,y,w,h}]
      noktalar: @json($noktalar),           // [{kat,tip,ad,x,y,boy?}]
      masalar:  @json((object) $semaMasa),  // {"id":{kat,x,y,boy}}
    };
    var masaList = @json($masaList);
    var TIPLER   = @json($tipler);
    if (!Array.isArray(state.katlar) || !state.katlar.length) state.katlar = ['Zemin Kat'];
    if (!Array.isArray(state.parsel))   state.parsel = [];
    if (!Array.isArray(state.bolgeler)) state.bolgeler = [];
    if (!Array.isArray(state.noktalar)) state.noktalar = [];
    if (!state.masalar || typeof state.masalar !== 'object') state.masalar = {};

    var aktifKat = 0;
    var secili = null;            // {tur:'masa'|'bolge'|'nokta'|'kose', anahtar}
    var gecmis = [];              // geri al yiginini (JSON anlik goruntu)

    var kanvas  = document.getElementById('sema-kanvas');
    var svg     = document.getElementById('parsel-svg');
    var sekmeEl = document.getElementById('kat-sekmeler');
    var durumEl = document.getElementById('sema-durum');
    var geriBtn = document.getElementById('sema-geri');
    var parselCubuk = document.getElementById('parsel-cubuk');

    function n(v){ var x = parseFloat(v); return isNaN(x) ? 0 : x; }
    function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
    function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
    function masaBul(id){ for (var i=0;i<masaList.length;i++){ if (String(masaList[i].id)===String(id)) return masaList[i]; } return {id:id, ad:String(id), kapasite:0, sekil:'kare'}; }

    // sanal 0..1000 -> piksel / piksel delta -> sanal delta
    function px(){ return { w: kanvas.clientWidth, h: kanvas.clientHeight }; }
    function sx(v){ return n(v)/1000 * px().w; }
    function sy(v){ return n(v)/1000 * px().h; }
    function vdx(dpx){ return dpx / px().w * 1000; }
    function vdy(dpy){ return dpy / px().h * 1000; }

    // ---- geri al ----
    function anlik(){ return JSON.stringify(state); }
    function gecmisKaydet(){ gecmis.push(anlik()); if (gecmis.length > 50) gecmis.shift(); geriBtn.disabled = false; }
    function geriAl(){
      if (!gecmis.length) return;
      state = JSON.parse(gecmis.pop());
      if (!Array.isArray(state.katlar) || !state.katlar.length) state.katlar = ['Zemin Kat'];
      aktifKat = clamp(aktifKat, 0, state.katlar.length - 1);
      secili = null;
      geriBtn.disabled = gecmis.length === 0;
      ciz();
    }
    geriBtn.onclick = geriAl;

    // ---- surukleme (pointer) ----
    function dragYap(el, onMove, onStart){
      var sx0=0, sy0=0, aktif=false;
      el.addEventListener('pointerdown', function(e){
        e.stopPropagation();
        aktif = true; sx0 = e.clientX; sy0 = e.clientY;
        try{ el.setPointerCapture(e.pointerId); }catch(_){}
        if (onStart) onStart();
      });
      el.addEventListener('pointermove', function(e){
        if (!aktif) return;
        var ddx = e.clientX - sx0, ddy = e.clientY - sy0;
        sx0 = e.clientX; sy0 = e.clientY;
        onMove(ddx, ddy);
      });
      function bit(e){ if (aktif){ aktif=false; try{el.releasePointerCapture(e.pointerId);}catch(_){} } }
      el.addEventListener('pointerup', bit);
      el.addEventListener('pointercancel', bit);
    }

    function sc(tur, anahtar){ return secili && secili.tur===tur && secili.anahtar===anahtar; }

    // ---- aktif kat parsel kaydi ----
    function aktifParsel(){ for (var i=0;i<state.parsel.length;i++){ if (n(state.parsel[i].kat)===aktifKat) return state.parsel[i]; } return null; }

    // ---- cizim ----
    function ciz(){
      // kat sekmeleri
      sekmeEl.innerHTML = '';
      state.katlar.forEach(function(k, i){
        var b = document.createElement('div');
        b.className = 'ss-kat' + (i===aktifKat ? ' aktif' : '');
        b.innerHTML = esc(k) + (i===aktifKat && state.katlar.length>1 ? ' <span class="material-icons-outlined">edit</span>' : '');
        b.onclick = function(){ aktifKat = i; secili = null; ciz(); };
        if (i===aktifKat) b.querySelector('.material-icons-outlined') && (b.querySelector('.material-icons-outlined').onclick = function(e){ e.stopPropagation(); katMenu(i); });
        sekmeEl.appendChild(b);
      });

      // ogeleri temizle (svg + izgara haric)
      Array.prototype.slice.call(kanvas.querySelectorAll('.oge')).forEach(function(e){ e.remove(); });

      cizParsel();
      state.bolgeler.forEach(function(b, i){ if (n(b.kat)===aktifKat) cizBolge(b, i); });
      state.noktalar.forEach(function(nk, i){ if (n(nk.kat)===aktifKat) cizNokta(nk, i); });
      Object.keys(state.masalar).forEach(function(mid){ var yer=state.masalar[mid]; if (n(yer.kat)===aktifKat) cizMasa(mid, yer); });

      parselCubuk.style.display = aktifParsel() ? 'flex' : 'none';
      var dolu = state.bolgeler.length || state.noktalar.length || Object.keys(state.masalar).length || state.parsel.length;
      document.getElementById('bos-uyari').style.display = dolu ? 'none' : '';

      seciliPanel();
      ogeListe();
    }

    function cizParsel(){
      svg.innerHTML = '';
      var pr = aktifParsel();
      if (!pr || !pr.pts || pr.pts.length < 2) return;
      // sanal x 0..1000 ; y 0..1000 -> viewBox 1000 x 1350 (oran 1.35)
      var pts = pr.pts.map(function(p){ return n(p[0]) + ',' + (n(p[1])*1.35); }).join(' ');
      var poly = document.createElementNS('http://www.w3.org/2000/svg','polygon');
      poly.setAttribute('points', pts);
      poly.setAttribute('fill', 'rgba(14,165,233,0.10)');
      poly.setAttribute('stroke', '#0EA5E9');
      poly.setAttribute('stroke-width', '4');
      svg.appendChild(poly);
      // kose tutamaklari
      pr.pts.forEach(function(p, ki){
        var h = document.createElement('div');
        h.className = 'oge oge-kose';
        if (sc('kose', ki)){ h.style.width='26px'; h.style.height='26px'; h.style.borderWidth='3px'; }
        h.style.left = (sx(p[0]) - parseInt(h.style.width||'20')/2) + 'px';
        h.style.top  = (sy(p[1]) - parseInt(h.style.height||'20')/2) + 'px';
        h.title = 'Köşe ' + (ki+1);
        h.addEventListener('click', function(e){ e.stopPropagation(); secili = {tur:'kose', anahtar:ki}; ciz(); });
        dragYap(h, function(ddx, ddy){
          p[0] = clamp(n(p[0]) + vdx(ddx), 0, 1000);
          p[1] = clamp(n(p[1]) + vdy(ddy), 0, 1000);
          var w = parseInt(h.style.width||'20');
          h.style.left = (sx(p[0]) - w/2) + 'px';
          h.style.top  = (sy(p[1]) - w/2) + 'px';
          cizParsel();
        }, function(){ gecmisKaydet(); });
        kanvas.appendChild(h);
      });
    }

    function cizBolge(b, i){
      var d = document.createElement('div');
      var secli = sc('bolge', i);
      d.className = 'oge';
      d.style.cssText = 'left:'+sx(b.x)+'px;top:'+sy(b.y)+'px;width:'+sx(b.w)+'px;height:'+sy(b.h)+'px;' +
        'background:rgba(14,165,233,0.12);border-radius:10px;cursor:pointer;' +
        'border:'+(secli?'2px solid #0EA5E9':'1.2px solid rgba(14,165,233,0.5)')+';overflow:visible';
      d.innerHTML = '<span style="font-size:11px;font-weight:700;color:#0EA5E9;padding:5px;display:inline-block;pointer-events:none">'+esc(b.ad)+'</span>';
      d.addEventListener('click', function(e){ e.stopPropagation(); secili = {tur:'bolge', anahtar:i}; ciz(); });
      if (secli){
        // TASI tutamagi (sag ust)
        var mv = document.createElement('div');
        mv.className = 'oge-tutamak';
        mv.style.cssText = 'right:0;top:0;width:28px;height:28px;background:#0EA5E9;border-radius:8px;color:#fff';
        mv.innerHTML = '<span class="material-icons-outlined">open_with</span>';
        dragYap(mv, function(ddx, ddy){
          b.x = clamp(n(b.x)+vdx(ddx), 0, 1000); b.y = clamp(n(b.y)+vdy(ddy), 0, 1000);
          d.style.left = sx(b.x)+'px'; d.style.top = sy(b.y)+'px';
        }, function(){ gecmisKaydet(); });
        d.appendChild(mv);
        // BOYUT tutamagi (sag alt)
        var rs = document.createElement('div');
        rs.className = 'oge-tutamak';
        rs.style.cssText = 'right:0;bottom:0;width:26px;height:26px;background:#0EA5E9;border-radius:6px;color:#fff';
        rs.innerHTML = '<span class="material-icons-outlined">open_in_full</span>';
        dragYap(rs, function(ddx, ddy){
          b.w = clamp(n(b.w)+vdx(ddx), 90, 1000); b.h = clamp(n(b.h)+vdy(ddy), 70, 1000);
          d.style.width = sx(b.w)+'px'; d.style.height = sy(b.h)+'px';
        }, function(){ gecmisKaydet(); });
        d.appendChild(rs);
      }
      kanvas.appendChild(d);
    }

    function cizNokta(nk, i){
      var def = TIPLER[nk.tip] || TIPLER['diger'];
      var boy = n(nk.boy) > 0 ? n(nk.boy) : 58;
      var bpx = clamp(sx(boy), 24, 220);
      var d = document.createElement('div');
      var secli = sc('nokta', i);
      d.className = 'oge';
      d.style.cssText = 'left:'+(sx(nk.x)-bpx/2)+'px;top:'+(sy(nk.y)-bpx/2)+'px;display:flex;flex-direction:column;align-items:center;cursor:move';
      d.innerHTML =
        '<div style="width:'+bpx+'px;height:'+bpx+'px;display:flex;align-items:center;justify-content:center;border-radius:12px;'+
          'background:'+def[1]+';box-shadow:0 2px 6px '+def[1]+'66;'+(secli?'outline:2.5px solid #fff;outline-offset:1px;':'')+'">'+
          '<span style="font-size:'+Math.max(13,bpx*0.45)+'px">'+def[0]+'</span></div>'+
        '<span style="font-size:10px;font-weight:700;color:var(--ink);margin-top:2px;pointer-events:none">'+esc(nk.ad||def[2])+'</span>';
      d.addEventListener('click', function(e){ e.stopPropagation(); secili = {tur:'nokta', anahtar:i}; ciz(); });
      dragYap(d, function(ddx, ddy){
        nk.x = clamp(n(nk.x)+vdx(ddx), 0, 1000); nk.y = clamp(n(nk.y)+vdy(ddy), 0, 1000);
        d.style.left = (sx(nk.x)-bpx/2)+'px'; d.style.top = (sy(nk.y)-bpx/2)+'px';
      }, function(){ gecmisKaydet(); });
      kanvas.appendChild(d);
    }

    function cizMasa(mid, yer){
      var masa = masaBul(mid);
      var yuvarlak = (masa.sekil||'kare') === 'yuvarlak';
      var boy = n(yer.boy) > 0 ? n(yer.boy) : 170;
      var bpx = clamp(sx(boy), 28, px().w);
      var d = document.createElement('div');
      var secli = sc('masa', mid);
      d.className = 'oge';
      d.style.cssText = 'left:'+(sx(yer.x)-bpx/2)+'px;top:'+(sy(yer.y)-bpx/2)+'px;width:'+bpx+'px;height:'+bpx+'px;' +
        'display:flex;align-items:center;justify-content:center;cursor:move;color:#fff;font-weight:800;' +
        'background:var(--mor1);border-radius:'+(yuvarlak?'50%':'12px')+';box-shadow:0 2px 6px rgba(124,58,237,0.35);overflow:visible;' +
        (secli?'outline:2.5px solid #fff;outline-offset:1px;':'');
      d.innerHTML = '<span style="pointer-events:none;background:rgba(0,0,0,0.5);padding:1.5px 6px;border-radius:7px;font-size:'+Math.max(9,Math.min(13,bpx*0.2))+'px">'+esc(masa.ad)+'</span>';
      d.addEventListener('click', function(e){ e.stopPropagation(); secili = {tur:'masa', anahtar:mid}; ciz(); });
      dragYap(d, function(ddx, ddy){
        yer.x = clamp(n(yer.x)+vdx(ddx), 0, 1000); yer.y = clamp(n(yer.y)+vdy(ddy), 0, 1000);
        d.style.left = (sx(yer.x)-bpx/2)+'px'; d.style.top = (sy(yer.y)-bpx/2)+'px';
      }, function(){ gecmisKaydet(); });
      if (secli){
        var rs = document.createElement('div');
        rs.className = 'oge-tutamak';
        rs.style.cssText = 'right:-7px;bottom:-7px;width:22px;height:22px;border-radius:50%;background:#fff;border:2px solid var(--mor1);color:var(--mor1)';
        rs.innerHTML = '<span class="material-icons-outlined">open_in_full</span>';
        dragYap(rs, function(ddx){
          yer.boy = clamp((n(yer.boy)>0?n(yer.boy):170) + vdx(ddx)*1.4, 90, 700);
          ciz();
        }, function(){ gecmisKaydet(); });
        d.appendChild(rs);
      }
      kanvas.appendChild(d);
    }

    // zemine tikla -> secimi birak
    kanvas.addEventListener('click', function(){ secili = null; ciz(); });

    // ---- secili panel ----
    function seciliPanel(){
      var el = document.getElementById('secili-panel');
      if (!secili){ el.innerHTML = '<span style="color:var(--sub)">Bir öğeye tıkla.</span>'; return; }
      var html = '';
      if (secili.tur==='masa'){
        var m = masaBul(secili.anahtar);
        html = '<div style="font-weight:700;color:var(--ink);margin-bottom:8px">Masa: '+esc(m.ad)+'</div>' + boyutSatiri('masa');
      } else if (secili.tur==='bolge'){
        var b = state.bolgeler[secili.anahtar];
        html = '<div style="font-weight:700;color:var(--ink);margin-bottom:8px">Bölge</div>' +
          lblInp('d-ad','Ad','text',esc(b.ad||'')) + xySatiri(b) + whSatiri(b);
      } else if (secili.tur==='nokta'){
        var nk = state.noktalar[secili.anahtar];
        html = '<div style="font-weight:700;color:var(--ink);margin-bottom:8px">Nokta ('+esc((TIPLER[nk.tip]||['','',''])[2])+')</div>' +
          lblInp('d-ad','Ad','text',esc(nk.ad||'')) + xySatiri(nk) + boyutSatiri('nokta');
      } else if (secili.tur==='kose'){
        html = '<div style="font-weight:700;color:var(--ink);margin-bottom:8px">Salon sınırı köşesi</div>' +
          '<div style="font-size:12px;color:var(--sub);margin-bottom:8px">Köşeyi kanvasta sürükleyerek taşı.</div>';
      }
      html += '<button type="button" id="d-sil" class="rk-btn bos" style="margin-top:10px;width:100%;justify-content:center;color:var(--kirmizi)">' +
              '<span class="material-icons-outlined" style="font-size:17px">delete</span> Sil</button>';
      el.innerHTML = html;
      baglaPanel();
    }
    function lblInp(id,lbl,tip,val){ return '<label class="rk-lbl" style="margin-top:0">'+lbl+'</label><input id="'+id+'" type="'+tip+'" class="rk-inp" style="padding:7px 10px" value="'+val+'">'; }
    function xySatiri(o){
      return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">' +
        '<div>'+lblInp('d-x','X (0-1000)','number',Math.round(n(o.x)))+'</div>' +
        '<div>'+lblInp('d-y','Y (0-1000)','number',Math.round(n(o.y)))+'</div></div>';
    }
    function whSatiri(o){
      return '<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px">' +
        '<div>'+lblInp('d-w','Genişlik','number',Math.round(n(o.w)))+'</div>' +
        '<div>'+lblInp('d-h','Yükseklik','number',Math.round(n(o.h)))+'</div></div>';
    }
    function boyutSatiri(tur){
      var cur = (tur==='masa') ? (n(state.masalar[secili.anahtar].boy)||170) : (n(state.noktalar[secili.anahtar].boy)||58);
      return '<div style="margin-top:8px">'+lblInp('d-boy','Boyut','number',Math.round(cur))+'</div>';
    }
    function baglaPanel(){
      function onInp(id, fn){ var e=document.getElementById(id); if (e) e.addEventListener('input', function(){ fn(e.value); cizYenidenKonum(); }); }
      var o = null;
      if (secili.tur==='masa') o = state.masalar[secili.anahtar];
      else if (secili.tur==='bolge') o = state.bolgeler[secili.anahtar];
      else if (secili.tur==='nokta') o = state.noktalar[secili.anahtar];
      if (o){
        onInp('d-ad',  function(v){ o.ad = v; });
        onInp('d-x',   function(v){ o.x = clamp(n(v),0,1000); });
        onInp('d-y',   function(v){ o.y = clamp(n(v),0,1000); });
        onInp('d-w',   function(v){ o.w = clamp(n(v),90,1000); });
        onInp('d-h',   function(v){ o.h = clamp(n(v),70,1000); });
        onInp('d-boy', function(v){ o.boy = clamp(n(v),28,700); });
      }
      var sil = document.getElementById('d-sil');
      if (sil) sil.onclick = function(){ ogeSil(); };
    }
    // panel inputu yazarken odak kaybetmesin: sadece kanvas konumlarini tazele
    function cizYenidenKonum(){
      Array.prototype.slice.call(kanvas.querySelectorAll('.oge')).forEach(function(e){ e.remove(); });
      cizParsel();
      state.bolgeler.forEach(function(b, i){ if (n(b.kat)===aktifKat) cizBolge(b, i); });
      state.noktalar.forEach(function(nk, i){ if (n(nk.kat)===aktifKat) cizNokta(nk, i); });
      Object.keys(state.masalar).forEach(function(mid){ var yer=state.masalar[mid]; if (n(yer.kat)===aktifKat) cizMasa(mid, yer); });
      ogeListe();
    }

    function ogeSil(){
      if (!secili) return;
      gecmisKaydet();
      if (secili.tur==='masa') delete state.masalar[secili.anahtar];
      else if (secili.tur==='bolge') state.bolgeler.splice(secili.anahtar, 1);
      else if (secili.tur==='nokta') state.noktalar.splice(secili.anahtar, 1);
      else if (secili.tur==='kose'){ var pr=aktifParsel(); if (pr) pr.pts.splice(secili.anahtar,1); }
      secili = null; ciz();
    }

    // ---- oge listesi ----
    function ogeListe(){
      var el = document.getElementById('oge-liste');
      var h = '';
      Object.keys(state.masalar).forEach(function(mid){ if (n(state.masalar[mid].kat)===aktifKat){ var m=masaBul(mid); h += satir('masa', mid, '🪑 '+esc(m.ad)); }});
      state.bolgeler.forEach(function(b,i){ if (n(b.kat)===aktifKat) h += satir('bolge', i, '▢ '+esc(b.ad||'Bölge')); });
      state.noktalar.forEach(function(nk,i){ if (n(nk.kat)===aktifKat){ var d=TIPLER[nk.tip]||TIPLER['diger']; h += satir('nokta', i, d[0]+' '+esc(nk.ad||d[2])); }});
      if (!h) h = '<div style="color:var(--sub);font-size:13px">Bu katta öğe yok.</div>';
      el.innerHTML = h;
      Array.prototype.slice.call(el.querySelectorAll('[data-sec]')).forEach(function(b){
        b.onclick = function(){ var t=b.getAttribute('data-tur'); secili = {tur:t, anahtar: t==='masa' ? b.getAttribute('data-sec') : parseInt(b.getAttribute('data-sec'),10)}; ciz(); };
      });
    }
    function satir(tur, anahtar, label){
      var aktif = sc(tur, tur==='masa'?String(anahtar):anahtar) ? ' aktif' : '';
      return '<div data-sec="'+anahtar+'" data-tur="'+tur+'" class="ss-liste-sat'+aktif+'">'+label+'</div>';
    }

    // ---- ekleme araclari ----
    document.querySelectorAll('[data-ekle]').forEach(function(btn){
      btn.onclick = function(){
        var tur = btn.getAttribute('data-ekle');
        if (tur==='masa') masaEkleMenu();
        else if (tur==='bolge'){ var ad = prompt('Bölge adı', 'Bölge'); if (ad===null) return; gecmisKaydet(); state.bolgeler.push({kat:aktifKat, ad:ad||'Bölge', x:300, y:300, w:320, h:240}); secili={tur:'bolge',anahtar:state.bolgeler.length-1}; ciz(); }
        else if (tur==='nokta') noktaEkleMenu();
        else if (tur==='parsel'){ if (!aktifParsel()){ gecmisKaydet(); state.parsel.push({kat:aktifKat, pts:[[140,200],[860,200],[860,860],[140,860]]}); } ciz(); }
      };
    });

    function masaEkleMenu(){
      var yerlesmis = {};
      Object.keys(state.masalar).forEach(function(k){ yerlesmis[k]=1; });
      var bekleyen = masaList.filter(function(m){ return !yerlesmis[String(m.id)]; });
      if (!bekleyen.length){ alert('Tüm masalar yerleştirilmiş.'); return; }
      var liste = bekleyen.map(function(m, i){ return (i+1)+') '+m.ad; }).join('\n');
      var sec = prompt('Hangi masa? (numara gir)\n'+liste, '1');
      if (sec===null) return;
      var idx = parseInt(sec,10)-1;
      if (isNaN(idx) || idx<0 || idx>=bekleyen.length){ alert('Geçersiz seçim'); return; }
      var m = bekleyen[idx];
      gecmisKaydet();
      state.masalar[String(m.id)] = {kat:aktifKat, x:500, y:450, boy:170};
      secili = {tur:'masa', anahtar:String(m.id)};
      ciz();
    }
    function noktaEkleMenu(){
      var tipKeys = Object.keys(TIPLER);
      var liste = tipKeys.map(function(k,i){ return (i+1)+') '+TIPLER[k][0]+' '+TIPLER[k][2]; }).join('\n');
      var sec = prompt('Nokta tipi? (numara gir)\n'+liste, '1');
      if (sec===null) return;
      var idx = parseInt(sec,10)-1;
      if (isNaN(idx) || idx<0 || idx>=tipKeys.length){ alert('Geçersiz'); return; }
      var tip = tipKeys[idx];
      gecmisKaydet();
      state.noktalar.push({kat:aktifKat, tip:tip, ad:TIPLER[tip][2], x:500, y:500});
      secili = {tur:'nokta', anahtar:state.noktalar.length-1};
      ciz();
    }

    // ---- parsel araclari ----
    document.getElementById('kose-ekle').onclick = function(){
      var pr = aktifParsel();
      if (!pr || pr.pts.length < 2) return;
      // en uzun kenarin ortasina kose ekle
      var en=0, enU=-1;
      for (var i=0;i<pr.pts.length;i++){
        var a=pr.pts[i], b=pr.pts[(i+1)%pr.pts.length];
        var u=(a[0]-b[0])*(a[0]-b[0])+(a[1]-b[1])*(a[1]-b[1]);
        if (u>enU){ enU=u; en=i; }
      }
      var a=pr.pts[en], b=pr.pts[(en+1)%pr.pts.length];
      gecmisKaydet();
      pr.pts.splice(en+1, 0, [(a[0]+b[0])/2, (a[1]+b[1])/2]);
      ciz();
    };
    document.getElementById('parsel-sifir').onclick = function(){ var pr=aktifParsel(); if (!pr) return; gecmisKaydet(); pr.pts = [[140,200],[860,200],[860,860],[140,860]]; ciz(); };
    document.getElementById('parsel-sil').onclick = function(){ gecmisKaydet(); state.parsel = state.parsel.filter(function(p){ return n(p.kat)!==aktifKat; }); if (secili && secili.tur==='kose') secili=null; ciz(); };

    // ---- kat islemleri ----
    document.getElementById('kat-ekle').onclick = function(){
      var ad = prompt('Kat adı', 'Kat ' + state.katlar.length);
      if (ad===null) return;
      gecmisKaydet();
      state.katlar.push(ad || ('Kat '+state.katlar.length));
      aktifKat = state.katlar.length - 1; secili = null; ciz();
    };
    function katMenu(i){
      var ad = prompt('Kat adını değiştir (silmek için boş bırakıp Tamam)', state.katlar[i]);
      if (ad===null) return;
      gecmisKaydet();
      if (ad.trim()===''){ katSil(i); return; }
      state.katlar[i] = ad; ciz();
    }
    function katSil(i){
      if (state.katlar.length <= 1){ alert('Son kat silinemez.'); return; }
      state.bolgeler = state.bolgeler.filter(function(b){ return n(b.kat)!==i; });
      state.noktalar = state.noktalar.filter(function(nk){ return n(nk.kat)!==i; });
      Object.keys(state.masalar).forEach(function(k){ if (n(state.masalar[k].kat)===i) delete state.masalar[k]; });
      state.parsel = state.parsel.filter(function(p){ return n(p.kat)!==i; });
      // i'den buyuk katlari bir azalt
      state.bolgeler.forEach(function(b){ if (n(b.kat)>i) b.kat = n(b.kat)-1; });
      state.noktalar.forEach(function(nk){ if (n(nk.kat)>i) nk.kat = n(nk.kat)-1; });
      Object.keys(state.masalar).forEach(function(k){ if (n(state.masalar[k].kat)>i) state.masalar[k].kat = n(state.masalar[k].kat)-1; });
      state.parsel.forEach(function(p){ if (n(p.kat)>i) p.kat = n(p.kat)-1; });
      state.katlar.splice(i,1);
      aktifKat = clamp(aktifKat, 0, state.katlar.length-1);
      secili = null; ciz();
    }

    document.getElementById('kat-temizle').onclick = function(){
      if (!confirm('Bu kattaki masalar, bölgeler, noktalar ve salon sınırı kaldırılsın mı? (Kaydedene kadar Geri Al ile dönebilirsin.)')) return;
      gecmisKaydet();
      state.bolgeler = state.bolgeler.filter(function(b){ return n(b.kat)!==aktifKat; });
      state.noktalar = state.noktalar.filter(function(nk){ return n(nk.kat)!==aktifKat; });
      Object.keys(state.masalar).forEach(function(k){ if (n(state.masalar[k].kat)===aktifKat) delete state.masalar[k]; });
      state.parsel = state.parsel.filter(function(p){ return n(p.kat)!==aktifKat; });
      secili = null; ciz();
    };

    // ---- kaydet ----
    document.getElementById('sema-kaydet').onclick = function(){
      durumEl.textContent = 'Kaydediliyor...'; durumEl.style.color = 'var(--sub)';
      // JSON semasi backend ile birebir: katlar/parsel/bolgeler/noktalar/masalar(obje)
      var veri = { katlar: state.katlar, parsel: state.parsel, bolgeler: state.bolgeler, noktalar: state.noktalar, masalar: state.masalar };
      var fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('veri', JSON.stringify(veri));
      fetch(KAYDET_URL, { method:'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF} })
        .then(function(r){ return r.json().catch(function(){ return {ok:0}; }); })
        .then(function(res){
          if (res && res.ok === 1){ durumEl.textContent = 'Kaydedildi ✓'; durumEl.style.color = 'var(--yesil)'; }
          else { durumEl.textContent = (res && res.hata) ? res.hata : 'Kaydedilemedi'; durumEl.style.color = 'var(--kirmizi)'; }
          setTimeout(function(){ durumEl.textContent=''; }, 3000);
        })
        .catch(function(){ durumEl.textContent = 'Bağlantı hatası'; durumEl.style.color = 'var(--kirmizi)'; });
    };

    // ilk cizim + yeniden boyutlanınca tekrar ciz
    ciz();
    var tmr = null;
    window.addEventListener('resize', function(){ clearTimeout(tmr); tmr = setTimeout(ciz, 150); });
  } catch (e) {
    var k = document.getElementById('sema-kanvas');
    if (k) k.insertAdjacentHTML('afterend', '<div style="color:var(--kirmizi);font-size:13px;margin-top:12px">Şema yüklenemedi.</div>');
    if (window.console) console.error(e);
  }
})();
</script>
@endsection

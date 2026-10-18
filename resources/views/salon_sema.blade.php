@extends('layout.app')
@section('title', 'Salon Şema')
@section('baslik', '🗺️ Salon Şema')

@section('content')
@php
  // Backend'den gelen degiskenler: $veri (JSON array|null), $masalar (collection), $bolgelerSrc (collection)
  // JSON sema: {katlar:[], parsel:[{kat,pts:[[x,y]]}], bolgeler:[{kat,ad,x,y,w,h}],
  //            noktalar:[{kat,tip,ad,x,y,boy?}], masalar:{"id":{kat,x,y,boy}}}
  $veri     = $veri ?? null;
  $masalar  = $masalar ?? [];
  $katlar   = (is_array($veri) && !empty($veri['katlar'])) ? array_values($veri['katlar']) : ['Zemin Kat'];
  $parsel   = (is_array($veri) && !empty($veri['parsel']))   ? $veri['parsel']   : [];
  $bolgeler = (is_array($veri) && !empty($veri['bolgeler'])) ? $veri['bolgeler'] : [];
  $noktalar = (is_array($veri) && !empty($veri['noktalar'])) ? $veri['noktalar'] : [];
  $semaMasa = (is_array($veri) && !empty($veri['masalar']) && is_array($veri['masalar'])) ? $veri['masalar'] : [];

  // Masa listesini JS'in tüketebileceği sade diziye çevir
  $masaList = [];
  foreach ($masalar as $m) {
    $masaList[] = [
      'id' => (int) $m->id,
      'ad' => (string) $m->ad,
      'kapasite' => (int) ($m->kapasite ?? 0),
      'sekil' => (string) ($m->sekil ?? 'kare'),
    ];
  }

  $bos = !is_array($veri) || (empty($bolgeler) && empty($noktalar) && empty($semaMasa) && empty($parsel));

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

<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
  <div class="text-sm text-slate-500">
    Öğeleri <b class="text-slate-700">sürükleyerek</b> konumlandır, sonra <b class="text-slate-700">Kaydet</b>.
    Mobil POS uygulamasıyla aynı kroki.
  </div>
  <div class="flex items-center gap-2">
    <span id="sema-durum" class="text-sm text-slate-400"></span>
    <button id="sema-kaydet" type="button"
      class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm">
      💾 Kaydet
    </button>
  </div>
</div>

{{-- Kat sekmeleri --}}
<div id="kat-sekmeler" class="flex items-center gap-2 flex-wrap mb-3"></div>

<div class="grid gap-4 lg:grid-cols-[1fr_320px]">
  {{-- KANVAS: sanal uzay 0..1000, oran 1.35 (mobil ile birebir) --}}
  <div class="bg-white rounded-2xl p-4 shadow-sm">
    <div class="flex items-center gap-2 mb-3 flex-wrap">
      <button type="button" data-ekle="masa"   class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100">🪑 Masa Ekle</button>
      <button type="button" data-ekle="bolge"  class="px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 text-xs font-semibold hover:bg-sky-100">▢ Bölge</button>
      <button type="button" data-ekle="nokta"  class="px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 text-xs font-semibold hover:bg-orange-100">📍 Nokta</button>
      <button type="button" data-ekle="parsel" class="px-3 py-1.5 rounded-lg bg-teal-50 text-teal-700 text-xs font-semibold hover:bg-teal-100">⬠ Salon Sınırı</button>
      <button type="button" id="kat-ekle" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-semibold hover:bg-slate-200 ml-auto">＋ Kat</button>
    </div>
    <div id="sema-kanvas"
      class="relative mx-auto select-none rounded-xl border border-slate-200 bg-slate-50 overflow-hidden"
      style="width:100%; max-width:560px; aspect-ratio:1 / 1.35;">
      {{-- parsel çizgisi için SVG overlay --}}
      <svg id="parsel-svg" class="absolute inset-0 w-full h-full pointer-events-none" viewBox="0 0 1000 1350" preserveAspectRatio="none"></svg>
      {{-- öğeler JS ile absolute yerleştirilir --}}
    </div>
    <div id="bos-uyari" class="{{ $bos ? '' : 'hidden' }} text-center text-slate-400 text-sm mt-3">
      Henüz şema çizilmemiş. Yukarıdan öğe ekleyerek başla.
    </div>
  </div>

  {{-- Yan panel: seçili öğe düzenleme + x/y liste --}}
  <div class="bg-white rounded-2xl p-4 shadow-sm">
    <div class="text-sm font-bold text-slate-700 mb-2">Seçili Öğe</div>
    <div id="secili-panel" class="text-sm text-slate-400">Bir öğeye tıkla.</div>
    <hr class="my-4 border-slate-100">
    <div class="text-sm font-bold text-slate-700 mb-2">Öğe Listesi</div>
    <div id="oge-liste" class="space-y-1.5 max-h-[420px] overflow-auto pr-1"></div>
  </div>
</div>

<script>
(function () {
  "use strict";
  try {
    var KAYDET_URL = "/salon-sema/kaydet";
    var CSRF = "{{ csrf_token() }}";

    // ---- state ----
    var state = {
      katlar:   @json($katlar),
      parsel:   @json($parsel),      // [{kat, pts:[[x,y]...]}]
      bolgeler: @json($bolgeler),    // [{kat,ad,x,y,w,h}]
      noktalar: @json($noktalar),    // [{kat,tip,ad,x,y,boy?}]
      masalar:  @json((object) $semaMasa), // {"id":{kat,x,y,boy}}
    };
    var masaList = @json($masaList);            // tüm masalar (yerleştirilebilir)
    var TIPLER   = @json($tipler);              // {tip:[emoji,renk,ad]}
    if (!Array.isArray(state.katlar) || !state.katlar.length) state.katlar = ['Zemin Kat'];
    if (!Array.isArray(state.parsel))   state.parsel = [];
    if (!Array.isArray(state.bolgeler)) state.bolgeler = [];
    if (!Array.isArray(state.noktalar)) state.noktalar = [];
    if (!state.masalar || typeof state.masalar !== 'object') state.masalar = {};

    var aktifKat = 0;
    var secili = null; // {tur:'masa'|'bolge'|'nokta', anahtar}

    var kanvas  = document.getElementById('sema-kanvas');
    var svg     = document.getElementById('parsel-svg');
    var sekmeEl = document.getElementById('kat-sekmeler');
    var durumEl = document.getElementById('sema-durum');

    function n(v){ var x = parseFloat(v); return isNaN(x) ? 0 : x; }
    function masaBul(id){ for (var i=0;i<masaList.length;i++){ if (String(masaList[i].id)===String(id)) return masaList[i]; } return {id:id, ad:String(id), kapasite:0, sekil:'kare'}; }

    // sanal 0..1000 -> piksel
    function px(){ return { w: kanvas.clientWidth, h: kanvas.clientHeight }; }
    function sx(v){ return n(v)/1000 * px().w; }
    function sy(v){ return n(v)/1000 * px().h; }
    // piksel delta -> sanal delta
    function vdx(dpx){ return dpx / px().w * 1000; }
    function vdy(dpy){ return dpy / px().h * 1000; }
    function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }

    // ---- sürükleme (pointer) ----
    function dragYap(el, onMove, onStart){
      var sx0=0, sy0=0, aktif=false;
      el.addEventListener('pointerdown', function(e){
        e.stopPropagation();
        aktif = true; sx0 = e.clientX; sy0 = e.clientY;
        el.setPointerCapture(e.pointerId);
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

    function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }

    // ---- çizim ----
    function ciz(){
      // kat sekmeleri
      sekmeEl.innerHTML = '';
      state.katlar.forEach(function(k, i){
        var b = document.createElement('button');
        b.type = 'button';
        b.textContent = k;
        b.className = 'px-3 py-1.5 rounded-full text-xs font-semibold ' +
          (i===aktifKat ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-200 hover:bg-slate-50');
        b.onclick = function(){ aktifKat = i; secili = null; ciz(); };
        sekmeEl.appendChild(b);
      });

      // kanvası temizle (svg hariç)
      Array.prototype.slice.call(kanvas.querySelectorAll('.oge')).forEach(function(e){ e.remove(); });

      // parsel (aktif kat)
      cizParsel();

      // bölgeler
      state.bolgeler.forEach(function(b, i){ if (n(b.kat)===aktifKat) cizBolge(b, i); });
      // noktalar
      state.noktalar.forEach(function(nk, i){ if (n(nk.kat)===aktifKat) cizNokta(nk, i); });
      // masalar
      Object.keys(state.masalar).forEach(function(mid){
        var yer = state.masalar[mid];
        if (n(yer.kat)===aktifKat) cizMasa(mid, yer);
      });

      seciliPanel();
      ogeListe();
    }

    function cizParsel(){
      svg.innerHTML = '';
      var pr = null;
      for (var i=0;i<state.parsel.length;i++){ if (n(state.parsel[i].kat)===aktifKat){ pr = state.parsel[i]; break; } }
      if (!pr || !pr.pts || pr.pts.length < 2) return;
      // sanal 0..1000 (x) ve 0..1000 (y) -> viewBox 1000 x 1350 (oran 1.35)
      var pts = pr.pts.map(function(p){ return (n(p[0])) + ',' + (n(p[1])*1.35); }).join(' ');
      var poly = document.createElementNS('http://www.w3.org/2000/svg','polygon');
      poly.setAttribute('points', pts);
      poly.setAttribute('fill', 'rgba(14,165,233,0.10)');
      poly.setAttribute('stroke', '#0EA5E9');
      poly.setAttribute('stroke-width', '4');
      svg.appendChild(poly);
      // köşe tutamakları (sürüklenebilir)
      pr.pts.forEach(function(p, ki){
        var h = document.createElement('div');
        h.className = 'oge absolute rounded-full bg-sky-500 border-2 border-white shadow cursor-move';
        h.style.width = '18px'; h.style.height = '18px';
        h.style.left = (sx(p[0]) - 9) + 'px';
        h.style.top  = (sy(p[1]) - 9) + 'px';
        h.title = 'Köşe ' + (ki+1);
        dragYap(h, function(ddx, ddy){
          p[0] = clamp(n(p[0]) + vdx(ddx), 0, 1000);
          p[1] = clamp(n(p[1]) + vdy(ddy), 0, 1000);
          h.style.left = (sx(p[0]) - 9) + 'px';
          h.style.top  = (sy(p[1]) - 9) + 'px';
          cizParsel();
        });
        kanvas.appendChild(h);
      });
    }

    function cizBolge(b, i){
      var d = document.createElement('div');
      var sc = secili && secili.tur==='bolge' && secili.anahtar===i;
      d.className = 'oge absolute rounded-lg cursor-move flex items-start p-1.5';
      d.style.left = sx(b.x)+'px'; d.style.top = sy(b.y)+'px';
      d.style.width = sx(b.w)+'px'; d.style.height = sy(b.h)+'px';
      d.style.background = 'rgba(14,165,233,0.12)';
      d.style.border = (sc ? '2px solid #0EA5E9' : '1.2px solid rgba(14,165,233,0.5)');
      d.innerHTML = '<span class="text-[11px] font-bold text-sky-700 pointer-events-none">'+esc(b.ad)+'</span>';
      d.onclick = function(e){ e.stopPropagation(); secili = {tur:'bolge', anahtar:i}; ciz(); };
      dragYap(d, function(ddx, ddy){
        b.x = clamp(n(b.x)+vdx(ddx), 0, 1000);
        b.y = clamp(n(b.y)+vdy(ddy), 0, 1000);
        d.style.left = sx(b.x)+'px'; d.style.top = sy(b.y)+'px';
      }, function(){ secili = {tur:'bolge', anahtar:i}; });
      kanvas.appendChild(d);
    }

    function cizNokta(nk, i){
      var def = TIPLER[nk.tip] || TIPLER['diger'];
      var boy = n(nk.boy) > 0 ? n(nk.boy) : 58;
      var bpx = sx(boy);
      var d = document.createElement('div');
      var sc = secili && secili.tur==='nokta' && secili.anahtar===i;
      d.className = 'oge absolute flex flex-col items-center cursor-move';
      d.style.left = (sx(nk.x) - bpx/2)+'px'; d.style.top = (sy(nk.y) - bpx/2)+'px';
      var badge = '<div class="flex items-center justify-center rounded-xl shadow" style="width:'+bpx+'px;height:'+bpx+'px;background:'+def[1]+';'+(sc?'outline:2.5px solid #fff;outline-offset:1px;':'')+'">'+
                  '<span style="font-size:'+Math.max(12,bpx*0.45)+'px">'+def[0]+'</span></div>';
      d.innerHTML = badge + '<span class="text-[10px] font-bold text-slate-700 mt-0.5 pointer-events-none">'+esc(nk.ad||def[2])+'</span>';
      d.onclick = function(e){ e.stopPropagation(); secili = {tur:'nokta', anahtar:i}; ciz(); };
      dragYap(d, function(ddx, ddy){
        nk.x = clamp(n(nk.x)+vdx(ddx), 0, 1000);
        nk.y = clamp(n(nk.y)+vdy(ddy), 0, 1000);
        d.style.left = (sx(nk.x) - bpx/2)+'px'; d.style.top = (sy(nk.y) - bpx/2)+'px';
      }, function(){ secili = {tur:'nokta', anahtar:i}; });
      kanvas.appendChild(d);
    }

    function cizMasa(mid, yer){
      var masa = masaBul(mid);
      var yuvarlak = (masa.sekil||'kare') === 'yuvarlak';
      var boy = n(yer.boy) > 0 ? n(yer.boy) : 170;
      var bpx = clamp(sx(boy), 28, px().w);
      var d = document.createElement('div');
      var sc = secili && secili.tur==='masa' && secili.anahtar===mid;
      d.className = 'oge absolute flex items-center justify-center cursor-move text-white font-extrabold';
      d.style.left = (sx(yer.x) - bpx/2)+'px'; d.style.top = (sy(yer.y) - bpx/2)+'px';
      d.style.width = bpx+'px'; d.style.height = bpx+'px';
      d.style.background = '#6D28D9';
      d.style.borderRadius = yuvarlak ? '50%' : '12px';
      d.style.boxShadow = '0 2px 6px rgba(109,40,217,0.35)';
      if (sc) d.style.outline = '2.5px solid #fff';
      d.innerHTML = '<span class="pointer-events-none" style="font-size:'+Math.max(9,Math.min(14,bpx*0.22))+'px">'+esc(masa.ad)+'</span>';
      d.onclick = function(e){ e.stopPropagation(); secili = {tur:'masa', anahtar:mid}; ciz(); };
      dragYap(d, function(ddx, ddy){
        yer.x = clamp(n(yer.x)+vdx(ddx), 0, 1000);
        yer.y = clamp(n(yer.y)+vdy(ddy), 0, 1000);
        d.style.left = (sx(yer.x) - bpx/2)+'px'; d.style.top = (sy(yer.y) - bpx/2)+'px';
      }, function(){ secili = {tur:'masa', anahtar:mid}; });
      kanvas.appendChild(d);
    }

    kanvas.addEventListener('click', function(){ secili = null; ciz(); });

    // ---- seçili panel ----
    function seciliPanel(){
      var el = document.getElementById('secili-panel');
      if (!secili){ el.innerHTML = '<span class="text-slate-400">Bir öğeye tıkla.</span>'; return; }
      var html = '', ad = '';
      if (secili.tur==='masa'){
        ad = masaBul(secili.anahtar).ad;
        var y = state.masalar[secili.anahtar];
        html = '<div class="font-bold text-slate-700 mb-2">Masa: '+esc(ad)+'</div>' +
          boyutSatiri('masa');
      } else if (secili.tur==='bolge'){
        var b = state.bolgeler[secili.anahtar];
        ad = b.ad || 'Bölge';
        html = '<div class="font-bold text-slate-700 mb-2">Bölge</div>' +
          '<label class="block text-xs text-slate-500 mb-1">Ad</label>' +
          '<input id="d-ad" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm mb-2" value="'+esc(ad)+'">' +
          xySatiri(b) + whSatiri(b);
      } else if (secili.tur==='nokta'){
        var nk = state.noktalar[secili.anahtar];
        ad = nk.ad || '';
        html = '<div class="font-bold text-slate-700 mb-2">Nokta ('+esc((TIPLER[nk.tip]||['','', ''])[2])+')</div>' +
          '<label class="block text-xs text-slate-500 mb-1">Ad</label>' +
          '<input id="d-ad" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm mb-2" value="'+esc(ad)+'">' +
          xySatiri(nk) + boyutSatiri('nokta');
      }
      html += '<button id="d-sil" class="mt-3 w-full px-3 py-1.5 rounded-lg bg-red-50 text-red-600 text-sm font-semibold hover:bg-red-100">🗑 Sil</button>';
      el.innerHTML = html;
      baglaPanel();
    }

    function xySatiri(o){
      return '<div class="grid grid-cols-2 gap-2 mb-2">' +
        '<div><label class="block text-xs text-slate-500 mb-1">X (0-1000)</label><input id="d-x" type="number" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm" value="'+Math.round(n(o.x))+'"></div>' +
        '<div><label class="block text-xs text-slate-500 mb-1">Y (0-1000)</label><input id="d-y" type="number" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm" value="'+Math.round(n(o.y))+'"></div>' +
        '</div>';
    }
    function whSatiri(o){
      return '<div class="grid grid-cols-2 gap-2 mb-2">' +
        '<div><label class="block text-xs text-slate-500 mb-1">Genişlik</label><input id="d-w" type="number" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm" value="'+Math.round(n(o.w))+'"></div>' +
        '<div><label class="block text-xs text-slate-500 mb-1">Yükseklik</label><input id="d-h" type="number" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm" value="'+Math.round(n(o.h))+'"></div>' +
        '</div>';
    }
    function boyutSatiri(tur){
      var cur = 0;
      if (tur==='masa'){ cur = n(state.masalar[secili.anahtar].boy)||170; }
      else { cur = n(state.noktalar[secili.anahtar].boy)||58; }
      return '<label class="block text-xs text-slate-500 mb-1">Boyut</label>' +
        '<input id="d-boy" type="number" class="w-full border border-slate-200 rounded-lg px-2 py-1 text-sm mb-1" value="'+Math.round(cur)+'">';
    }

    function baglaPanel(){
      function val(id){ var e=document.getElementById(id); return e?e.value:null; }
      function onInp(id, fn){ var e=document.getElementById(id); if (e) e.addEventListener('input', function(){ fn(e.value); cizYenidenKonum(); }); }
      var o = null;
      if (secili.tur==='masa') o = state.masalar[secili.anahtar];
      else if (secili.tur==='bolge') o = state.bolgeler[secili.anahtar];
      else if (secili.tur==='nokta') o = state.noktalar[secili.anahtar];
      if (!o) return;
      onInp('d-ad', function(v){ o.ad = v; });
      onInp('d-x',  function(v){ o.x = clamp(n(v),0,1000); });
      onInp('d-y',  function(v){ o.y = clamp(n(v),0,1000); });
      onInp('d-w',  function(v){ o.w = clamp(n(v),30,1000); });
      onInp('d-h',  function(v){ o.h = clamp(n(v),30,1000); });
      onInp('d-boy',function(v){ o.boy = clamp(n(v),28,700); });
      var sil = document.getElementById('d-sil');
      if (sil) sil.onclick = function(){ ogeSil(); };
    }

    // panel input'ları yazarken sadece kanvas konumlarını tazele (panel inputları odak kaybetmesin)
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
      if (secili.tur==='masa') delete state.masalar[secili.anahtar];
      else if (secili.tur==='bolge') state.bolgeler.splice(secili.anahtar, 1);
      else if (secili.tur==='nokta') state.noktalar.splice(secili.anahtar, 1);
      secili = null; ciz();
    }

    // ---- öğe listesi (yan panel) ----
    function ogeListe(){
      var el = document.getElementById('oge-liste');
      var h = '';
      var yerlesmis = {};
      Object.keys(state.masalar).forEach(function(mid){ if (n(state.masalar[mid].kat)===aktifKat){
        var m = masaBul(mid); yerlesmis[mid]=1;
        h += satir('masa', mid, '🪑 '+esc(m.ad));
      }});
      state.bolgeler.forEach(function(b,i){ if (n(b.kat)===aktifKat) h += satir('bolge', i, '▢ '+esc(b.ad||'Bölge')); });
      state.noktalar.forEach(function(nk,i){ if (n(nk.kat)===aktifKat){ var d=TIPLER[nk.tip]||TIPLER['diger']; h += satir('nokta', i, d[0]+' '+esc(nk.ad||d[2])); }});
      if (!h) h = '<div class="text-slate-400 text-sm">Bu katta öğe yok.</div>';
      el.innerHTML = h;
      Array.prototype.slice.call(el.querySelectorAll('[data-sec]')).forEach(function(b){
        b.onclick = function(){ secili = {tur:b.getAttribute('data-tur'), anahtar: b.getAttribute('data-tur')==='masa'? b.getAttribute('data-sec') : parseInt(b.getAttribute('data-sec'),10)}; ciz(); };
      });
    }
    function satir(tur, anahtar, label){
      return '<div data-sec="'+anahtar+'" data-tur="'+tur+'" class="flex items-center px-2.5 py-1.5 rounded-lg bg-slate-50 hover:bg-slate-100 cursor-pointer text-sm text-slate-700">'+label+'</div>';
    }

    // ---- ekleme araçları ----
    document.querySelectorAll('[data-ekle]').forEach(function(btn){
      btn.onclick = function(){
        var tur = btn.getAttribute('data-ekle');
        if (tur==='masa') masaEkleMenu();
        else if (tur==='bolge'){ var ad = prompt('Bölge adı', 'Bölge'); if (ad===null) return; state.bolgeler.push({kat:aktifKat, ad:ad, x:300, y:300, w:320, h:240}); secili={tur:'bolge',anahtar:state.bolgeler.length-1}; document.getElementById('bos-uyari').classList.add('hidden'); ciz(); }
        else if (tur==='nokta') noktaEkleMenu();
        else if (tur==='parsel'){ if (!parselVarMi()) { state.parsel.push({kat:aktifKat, pts:[[140,200],[860,200],[860,860],[140,860]]}); } document.getElementById('bos-uyari').classList.add('hidden'); ciz(); }
      };
    });

    function parselVarMi(){ for (var i=0;i<state.parsel.length;i++){ if (n(state.parsel[i].kat)===aktifKat) return true; } return false; }

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
      state.masalar[String(m.id)] = {kat:aktifKat, x:500, y:450, boy:170};
      secili = {tur:'masa', anahtar:String(m.id)};
      document.getElementById('bos-uyari').classList.add('hidden');
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
      state.noktalar.push({kat:aktifKat, tip:tip, ad:TIPLER[tip][2], x:500, y:500});
      secili = {tur:'nokta', anahtar:state.noktalar.length-1};
      document.getElementById('bos-uyari').classList.add('hidden');
      ciz();
    }

    document.getElementById('kat-ekle').onclick = function(){
      var ad = prompt('Kat adı', 'Kat ' + state.katlar.length);
      if (ad===null) return;
      state.katlar.push(ad || ('Kat '+state.katlar.length));
      aktifKat = state.katlar.length - 1; secili = null; ciz();
    };

    // ---- kaydet ----
    document.getElementById('sema-kaydet').onclick = function(){
      durumEl.textContent = 'Kaydediliyor...';
      durumEl.className = 'text-sm text-slate-400';
      // JSON şeması backend ile birebir: katlar/parsel/bolgeler/noktalar/masalar(obje)
      var veri = {
        katlar: state.katlar,
        parsel: state.parsel,
        bolgeler: state.bolgeler,
        noktalar: state.noktalar,
        masalar: state.masalar
      };
      var fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('veri', JSON.stringify(veri));
      fetch(KAYDET_URL, { method:'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(function(r){ return r.json().catch(function(){ return {ok:0}; }); })
        .then(function(res){
          if (res && res.ok === 1){ durumEl.textContent = 'Kaydedildi ✓'; durumEl.className = 'text-sm text-green-600 font-semibold'; }
          else { durumEl.textContent = (res && res.hata) ? res.hata : 'Kaydedilemedi'; durumEl.className = 'text-sm text-red-600 font-semibold'; }
          setTimeout(function(){ durumEl.textContent=''; }, 3000);
        })
        .catch(function(){ durumEl.textContent = 'Bağlantı hatası'; durumEl.className = 'text-sm text-red-600 font-semibold'; });
    };

    // ilk çizim + ekran yeniden boyutlanınca tekrar çiz
    ciz();
    var tmr = null;
    window.addEventListener('resize', function(){ clearTimeout(tmr); tmr = setTimeout(ciz, 150); });
  } catch (e) {
    var k = document.getElementById('sema-kanvas');
    if (k) k.insertAdjacentHTML('afterend', '<div class="text-red-600 text-sm mt-3">Şema yüklenemedi.</div>');
    if (window.console) console.error(e);
  }
})();
</script>
@endsection

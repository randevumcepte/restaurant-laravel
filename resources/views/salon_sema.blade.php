@extends('layout.app')
@section('title', 'Salon Şeması')
@section('rk_ikon', 'grid_on')
@section('baslik', 'Salon Şeması')

@section('rk_araclar')
  <span id="sema-durum" style="font-size:12.5px;color:var(--sub);margin-right:4px"></span>
  <button type="button" id="sema-geri" class="rk-btn bos" disabled>
    <span class="material-icons-outlined" style="font-size:18px">undo</span> Geri al
  </button>
  <button type="button" id="kat-temizle-btn" class="rk-btn bos" title="Bu katı temizle">
    <span class="material-icons-outlined" style="font-size:18px">layers_clear</span>
  </button>
  <button type="button" id="zoom-out-btn" class="rk-btn bos" title="Uzaklaş">
    <span class="material-icons-outlined" style="font-size:18px">zoom_out</span>
  </button>
  <button type="button" id="zoom-in-btn" class="rk-btn bos" title="Yakınlaş">
    <span class="material-icons-outlined" style="font-size:18px">zoom_in</span>
  </button>
  <button type="button" id="sema-kaydet" class="rk-btn" style="background:var(--mor1)">
    <span class="material-icons-outlined" style="font-size:18px">save</span> Kaydet
  </button>
@endsection

@section('content')
@php
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

  $tipler = [
    'giris'   => ['login',           '#14B8A6', 'Giriş'],
    'mutfak'  => ['restaurant',      '#F97316', 'Mutfak'],
    'bar'     => ['local_bar',       '#7C3AED', 'Bar'],
    'wc'      => ['wc',              '#3B82F6', 'WC'],
    'otopark' => ['local_parking',   '#6366F1', 'Otopark'],
    'kasa'    => ['point_of_sale',   '#10B981', 'Kasa'],
    'depo'    => ['inventory_2',     '#92694A', 'Depo'],
    'diger'   => ['place',           '#64748B', 'Diğer'],
  ];
@endphp

@push('head')
<style>
/* ===== KAPSAYICI ===== */
#ss-root{display:flex;flex-direction:column;height:calc(100vh - 120px);min-height:400px;gap:0}
/* ===== KAT SEKMELERİ ===== */
#kat-sekmeler{display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--card);border-bottom:1px solid var(--line);flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none}
#kat-sekmeler::-webkit-scrollbar{display:none}
.ss-kat{padding:6px 14px;border-radius:18px;font-size:12.5px;font-weight:700;cursor:pointer;border:1px solid var(--line);background:var(--card2);color:var(--sub2);display:inline-flex;align-items:center;gap:4px;white-space:nowrap;flex-shrink:0}
.ss-kat.aktif{background:var(--mor1);color:#fff;border-color:var(--mor1)}
.ss-kat .material-icons-outlined{font-size:13px}
/* ===== PARSEL ÇUBUĞU ===== */
#parsel-cubuk{display:none;align-items:center;gap:8px;flex-wrap:wrap;padding:6px 12px;background:rgba(14,165,233,0.12);border-bottom:1px solid rgba(14,165,233,0.25)}
#parsel-cubuk span.material-icons-outlined{font-size:16px;color:#0EA5E9}
#parsel-cubuk .pc-lbl{font-size:12px;color:var(--ink);flex:1}
#parsel-cubuk .pc-btn{padding:4px 10px;border-radius:8px;font-size:12px;font-weight:700;background:transparent;border:1px solid;cursor:pointer}
/* ===== KANVAS ===== */
#kanvas-alani{flex:1;display:flex;align-items:center;justify-content:center;padding:12px;overflow:hidden;background:var(--card2)}
#sema-kanvas{position:relative;user-select:none;-webkit-user-select:none;touch-action:none;
  border-radius:14px;border:1px solid var(--line);overflow:hidden;
  background:var(--bg,#F1F5F9);
  aspect-ratio:1/1.35;max-height:100%;width:auto;max-width:100%}
body.koyu #sema-kanvas{background:#0E1526}
#sema-kanvas .izgara{position:absolute;inset:0;pointer-events:none;
  background-image:linear-gradient(to right,color-mix(in srgb,var(--line) 55%,transparent) 1px,transparent 1px),
                   linear-gradient(to bottom,color-mix(in srgb,var(--line) 55%,transparent) 1px,transparent 1px);
  background-size:10% 10%}
#parsel-svg{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}
/* ===== ÖĞELER ===== */
.oge{position:absolute}
.oge-kose{width:20px;height:20px;border-radius:50%;background:#0EA5E9;border:2px solid #fff;
  box-shadow:0 1px 4px rgba(0,0,0,.25);cursor:move;transition:none}
.oge-kose.sec{width:26px;height:26px;border-width:3px}
.oge-tutamak{position:absolute;display:flex;align-items:center;justify-content:center;cursor:nwse-resize}
.oge-tutamak .material-icons-outlined{font-size:13px}
/* ===== SEÇİLİ CUBUK (Flutter _seciliCubuk) ===== */
#secili-cubuk{display:none;align-items:center;gap:6px;padding:6px 14px;background:var(--card2);border-top:1px solid var(--line)}
#secili-cubuk .sc-ad{font-size:13px;font-weight:600;color:var(--ink);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#secili-cubuk .sc-btn{padding:5px 10px;border-radius:8px;font-size:12px;font-weight:700;background:transparent;border:1px solid;cursor:pointer}
/* ===== ALT ARAÇ ÇUBUĞU (Flutter _altAracCubugu) ===== */
#alt-arac{display:flex;align-items:center;background:var(--card);border-top:1px solid var(--line)}
#alt-arac .aa-btn{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:9px 4px;cursor:pointer;border:none;background:transparent;gap:3px}
#alt-arac .aa-btn:hover{background:rgba(0,0,0,.04)}
#alt-arac .aa-btn .material-icons-outlined{font-size:22px}
#alt-arac .aa-btn span.aa-lbl{font-size:11px;font-weight:700;color:var(--sub2)}
/* ===== MODAL ===== */
.ss-modal-bg{position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);display:flex;align-items:flex-end;justify-content:center}
.ss-modal{background:var(--card);border-radius:20px 20px 0 0;width:100%;max-width:560px;padding:20px 16px 32px;max-height:80vh;overflow-y:auto;box-shadow:0 -4px 24px rgba(0,0,0,.18)}
.ss-modal-baslik{font-size:16px;font-weight:800;color:var(--ink);margin-bottom:14px}
.ss-tip-grid{display:flex;flex-wrap:wrap;gap:10px}
.ss-tip-kart{display:flex;flex-direction:column;align-items:center;gap:6px;width:92px;padding:12px 6px;border-radius:12px;cursor:pointer;border:1px solid transparent;font-size:12px;font-weight:600;color:var(--ink);background:var(--card2)}
.ss-tip-kart:hover{border-color:var(--mor1)}
.ss-tip-kart .material-icons-outlined{font-size:26px}
.ss-masa-grid{display:flex;flex-wrap:wrap;gap:8px}
.ss-masa-btn{padding:10px 14px;border-radius:10px;border:1px solid rgba(124,58,237,.35);background:rgba(124,58,237,.1);color:var(--ink);font-weight:700;cursor:pointer;font-size:13px}
.ss-masa-btn:hover{background:rgba(124,58,237,.22)}
.ss-inp{width:100%;padding:12px;border-radius:12px;border:1px solid var(--line);background:var(--card2);color:var(--ink);font-size:15px;margin-bottom:12px;box-sizing:border-box}
.ss-inp:focus{outline:none;border-color:var(--mor1)}
.ss-tam-btn{width:100%;padding:14px;border-radius:12px;border:none;background:var(--mor1);color:#fff;font-size:15px;font-weight:800;cursor:pointer}
.ss-tam-btn:hover{opacity:.9}
.ss-kirmizi{color:var(--kirmizi,#F43F5E)!important;border-color:var(--kirmizi,#F43F5E)!important}
/* ===== BOS UYARI ===== */
#bos-uyari{text-align:center;color:var(--sub);font-size:13px;padding:24px 0;display:none}
</style>
@endpush

{{-- ===== KÖK ===== --}}
<div id="ss-root">

  {{-- Kat sekmeleri --}}
  <div id="kat-sekmeler">
    <div id="kat-sekme-listesi" style="display:flex;gap:8px;flex:1;overflow-x:auto;scrollbar-width:none"></div>
    <button type="button" id="kat-ekle" style="flex-shrink:0;background:transparent;border:none;cursor:pointer;color:var(--mor1);display:flex;align-items:center;gap:3px;font-size:12.5px;font-weight:700;padding:4px 6px">
      <span class="material-icons-outlined" style="font-size:20px">add_circle_outline</span>
    </button>
  </div>

  {{-- Parsel çubuğu --}}
  <div id="parsel-cubuk">
    <span class="material-icons-outlined">open_with</span>
    <span class="pc-lbl">Salon sınırı: köşeleri sürükle</span>
    <button type="button" id="kose-ekle" class="pc-btn" style="color:#0EA5E9;border-color:#0EA5E9">+ Köşe</button>
    <button type="button" id="parsel-sifir" class="pc-btn" style="color:#F97316;border-color:#F97316">Sıfırla</button>
    <button type="button" id="parsel-sil" class="pc-btn ss-kirmizi">Sil</button>
  </div>

  {{-- Kanvas alanı --}}
  <div id="kanvas-alani">
    <div id="sema-kanvas">
      <div class="izgara"></div>
      <svg id="parsel-svg" viewBox="0 0 1000 1350" preserveAspectRatio="none"></svg>
    </div>
  </div>

  {{-- Boş uyarı --}}
  <div id="bos-uyari">Henüz şema çizilmemiş. Aşağıdan öğe ekleyerek başla.</div>

  {{-- Seçili öğe çubuğu --}}
  <div id="secili-cubuk">
    <span class="material-icons-outlined" style="font-size:16px;color:var(--sub)">adjust</span>
    <span class="sc-ad" id="sc-ad-lbl">Seçili öğe</span>
    <span id="sc-boyut-alan" style="display:none;align-items:center;gap:2px">
      <span style="font-size:12px;color:var(--sub)">Boyut</span>
      <button type="button" id="sc-kucult" class="sc-btn" style="color:var(--mor1);border-color:rgba(124,58,237,.4)">
        <span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">remove_circle_outline</span>
      </button>
      <button type="button" id="sc-buyut" class="sc-btn" style="color:var(--mor1);border-color:rgba(124,58,237,.4)">
        <span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">add_circle_outline</span>
      </button>
    </span>
    <button type="button" id="sc-ad-degistir" class="sc-btn" style="display:none;color:var(--mor1);border-color:rgba(124,58,237,.4)">
      <span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">edit</span> Adı Değiştir
    </button>
    <button type="button" id="sc-sil" class="sc-btn ss-kirmizi">
      <span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">delete_outline</span> Sil
    </button>
  </div>

  {{-- Alt araç çubuğu (Flutter _altAracCubugu) --}}
  <div id="alt-arac">
    <button type="button" class="aa-btn" id="ekle-masa">
      <span class="material-icons-outlined" style="color:var(--mor1)">table_bar</span>
      <span class="aa-lbl">Masa Ekle</span>
    </button>
    <button type="button" class="aa-btn" id="ekle-bolge">
      <span class="material-icons-outlined" style="color:#0EA5E9">crop_square</span>
      <span class="aa-lbl">Bölge</span>
    </button>
    <button type="button" class="aa-btn" id="ekle-nokta">
      <span class="material-icons-outlined" style="color:#F97316">add_location_alt</span>
      <span class="aa-lbl">Nokta</span>
    </button>
    <button type="button" class="aa-btn" id="ekle-parsel">
      <span class="material-icons-outlined" style="color:#14B8A6">pentagon</span>
      <span class="aa-lbl">Salon Sınırı</span>
    </button>
  </div>

</div>{{-- #ss-root --}}

{{-- ===== MODAL KAPLARI ===== --}}
{{-- Nokta tipi seçimi --}}
<div id="modal-nokta" class="ss-modal-bg" style="display:none">
  <div class="ss-modal">
    <div class="ss-modal-baslik">Sabit nokta ekle</div>
    <div class="ss-tip-grid" id="nokta-tip-grid">
      @foreach($tipler as $key => $def)
      <div class="ss-tip-kart" data-tip="{{ $key }}" style="border-color:{{ $def[1] }}22;background:{{ $def[1] }}18">
        <span class="material-icons-outlined" style="color:{{ $def[1] }}">{{ $def[0] }}</span>
        {{ $def[2] }}
      </div>
      @endforeach
    </div>
  </div>
</div>

{{-- Masa ekleme --}}
<div id="modal-masa" class="ss-modal-bg" style="display:none">
  <div class="ss-modal">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
      <div class="ss-modal-baslik" style="margin-bottom:0;flex:1">Masa ekle</div>
      <button type="button" id="masa-tum-kaldir" class="sc-btn ss-kirmizi" style="display:none">Yerleşenleri kaldır</button>
    </div>
    <div style="font-size:12px;color:var(--sub);margin-bottom:12px">Masaya dokun → şemaya düşer, sonra sürükle.</div>
    <div class="ss-masa-grid" id="masa-bekleyen-grid"></div>
    <div id="masa-hepsi-lbl" style="display:none;color:var(--sub);font-size:13px">Tüm masalar yerleştirildi 👍 (yeniden dizmek için "Yerleşenleri kaldır")</div>
  </div>
</div>

{{-- Ad girişi --}}
<div id="modal-ad" class="ss-modal-bg" style="display:none">
  <div class="ss-modal">
    <div class="ss-modal-baslik" id="modal-ad-baslik">Ad</div>
    <input type="text" id="modal-ad-inp" class="ss-inp" placeholder="Ad girin">
    <button type="button" class="ss-tam-btn" id="modal-ad-tamam">Tamam</button>
  </div>
</div>

{{-- Onay (confirm) --}}
<div id="modal-onay" class="ss-modal-bg" style="display:none">
  <div class="ss-modal">
    <div class="ss-modal-baslik" id="modal-onay-baslik">Emin misin?</div>
    <div style="color:var(--sub);font-size:13px;margin-bottom:20px" id="modal-onay-icerik"></div>
    <div style="display:flex;gap:10px">
      <button type="button" id="modal-onay-vazgec" style="flex:1;padding:13px;border-radius:12px;border:1px solid var(--line);background:transparent;color:var(--ink);font-size:14px;font-weight:700;cursor:pointer">Vazgeç</button>
      <button type="button" id="modal-onay-ok" style="flex:1;padding:13px;border-radius:12px;border:none;background:var(--kirmizi,#F43F5E);color:#fff;font-size:14px;font-weight:700;cursor:pointer" id="modal-onay-ok-btn">Temizle</button>
    </div>
  </div>
</div>

{{-- Kat menüsü --}}
<div id="modal-kat" class="ss-modal-bg" style="display:none">
  <div class="ss-modal">
    <div class="ss-modal-baslik" id="modal-kat-baslik">Kat</div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <button type="button" id="modal-kat-yeniad" class="ss-masa-btn" style="text-align:left">
        <span class="material-icons-outlined" style="font-size:17px;vertical-align:middle;color:var(--mor1)">edit</span> Adı Değiştir
      </button>
      <button type="button" id="modal-kat-sil" class="ss-masa-btn ss-kirmizi" style="text-align:left;display:none">
        <span class="material-icons-outlined" style="font-size:17px;vertical-align:middle">delete_outline</span> Katı Sil
      </button>
      <button type="button" id="modal-kat-kapat" class="sc-btn" style="margin-top:6px;width:100%;text-align:center;padding:12px;border-radius:12px;border:1px solid var(--line);color:var(--sub)">Kapat</button>
    </div>
  </div>
</div>

<script>
(function () {
  "use strict";
  try {

  // ---- BACKEND VERİSİ ----
  var state = {
    katlar:   @json($katlar),
    parsel:   @json($parsel),
    bolgeler: @json($bolgeler),
    noktalar: @json($noktalar),
    masalar:  @json((object) $semaMasa),
  };
  var masaList  = @json($masaList);
  var TIPLER    = @json($tipler);
  // {tip: [ikon_str, renk, etiket]}
  if (!Array.isArray(state.katlar) || !state.katlar.length) state.katlar = ['Zemin Kat'];
  if (!Array.isArray(state.parsel))   state.parsel   = [];
  if (!Array.isArray(state.bolgeler)) state.bolgeler = [];
  if (!Array.isArray(state.noktalar)) state.noktalar = [];
  if (!state.masalar || typeof state.masalar !== 'object') state.masalar = {};

  var KAYDET_URL = "/salon-sema/kaydet";
  var CSRF = (window.csrf || "{{ csrf_token() }}");

  var aktifKat = 0;
  var secili   = null;   // {tur:'masa'|'bolge'|'nokta'|'kose', anahtar}
  var gecmis   = [];     // geri al yığını

  // DOM referansları
  var kanvas      = document.getElementById('sema-kanvas');
  var svg         = document.getElementById('parsel-svg');
  var sekmeListEl = document.getElementById('kat-sekme-listesi');
  var durumEl     = document.getElementById('sema-durum');
  var geriBtn     = document.getElementById('sema-geri');
  var parselCubuk = document.getElementById('parsel-cubuk');
  var seciliCubuk = document.getElementById('secili-cubuk');
  var bosUyari    = document.getElementById('bos-uyari');

  // ---- YARDIMCILAR ----
  function n(v)        { var x = parseFloat(v); return isNaN(x) ? 0 : x; }
  function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
  function esc(s)      { return String(s==null?'':s).replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  function masaBul(id) { for (var i=0;i<masaList.length;i++){ if (String(masaList[i].id)===String(id)) return masaList[i]; } return {id:id,ad:String(id),kapasite:0,sekil:'kare'}; }

  function px() { return { w: kanvas.clientWidth, h: kanvas.clientHeight }; }
  function sx(v){ return n(v)/1000 * px().w; }
  function sy(v){ return n(v)/1000 * px().h; }
  function vdx(d){ return d / px().w * 1000; }
  function vdy(d){ return d / px().h * 1000; }

  // Kanvasa KESİN piksel boyut ver (CSS aspect-ratio+max-height definite yükseklik vermiyordu -> 0 boyut -> boş görünüyordu)
  function boyutla(){
    var alan = kanvas.parentElement; // #kanvas-alani
    if (!alan) return;
    var aw = alan.clientWidth - 24, ah = alan.clientHeight - 24; // padding 12*2
    if (aw <= 10) aw = alan.clientWidth || 600;
    if (ah <= 10) ah = Math.round(aw * 1.35);
    var w = aw, h = w * 1.35;
    if (h > ah) { h = ah; w = h / 1.35; }
    kanvas.style.width  = Math.round(w) + 'px';
    kanvas.style.height = Math.round(h) + 'px';
  }

  // ---- GERİ AL ----
  function anlik()    { return JSON.stringify(state); }
  function gecmisKaydet(){
    gecmis.push(anlik());
    if (gecmis.length > 50) gecmis.shift();
    geriBtn.disabled = false;
  }
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

  // ---- SÜRÜKLEME (pointer events) ----
  function dragYap(el, onMove, onStart){
    var cx=0, cy=0, aktif=false;
    el.addEventListener('pointerdown', function(e){
      e.stopPropagation();
      aktif=true; cx=e.clientX; cy=e.clientY;
      try{ el.setPointerCapture(e.pointerId); }catch(_){}
      if (onStart) onStart();
    });
    el.addEventListener('pointermove', function(e){
      if (!aktif) return;
      var ddx=e.clientX-cx, ddy=e.clientY-cy;
      cx=e.clientX; cy=e.clientY;
      onMove(ddx, ddy);
    });
    function bit(e){ if(aktif){ aktif=false; try{el.releasePointerCapture(e.pointerId);}catch(_){} } }
    el.addEventListener('pointerup', bit);
    el.addEventListener('pointercancel', bit);
  }

  function sc(tur, anahtar){ return secili && secili.tur===tur && secili.anahtar===anahtar; }

  // ---- AKTİF KAT PARSEL ----
  function aktifParsel(){ for(var i=0;i<state.parsel.length;i++){ if(n(state.parsel[i].kat)===aktifKat) return state.parsel[i]; } return null; }

  // ---- ZOOM (iki düğme ile) ----
  var _zoom = 1.0;
  document.getElementById('zoom-in-btn').onclick  = function(){ zoomUygula(1.25); };
  document.getElementById('zoom-out-btn').onclick = function(){ zoomUygula(0.8); };
  function zoomUygula(f){
    var sonraki = clamp(_zoom * f, 0.4, 5);
    _zoom = sonraki;
    kanvas.style.transform = 'scale(' + _zoom + ')';
    kanvas.style.transformOrigin = 'center center';
  }

  // ---- ÇİZİM ----
  function ciz(){
    // Kat sekmeleri
    sekmeListEl.innerHTML = '';
    state.katlar.forEach(function(k, i){
      var b = document.createElement('div');
      b.className = 'ss-kat' + (i===aktifKat ? ' aktif' : '');
      b.innerHTML = esc(k);
      if (i === aktifKat && state.katlar.length > 1){
        var ikn = document.createElement('span');
        ikn.className = 'material-icons-outlined'; ikn.textContent = 'more_vert';
        ikn.onclick = function(e){ e.stopPropagation(); katMenu(i); };
        b.appendChild(ikn);
      }
      b.addEventListener('click', function(){ if(i!==aktifKat){ aktifKat=i; secili=null; ciz(); } });
      if (i===aktifKat && state.katlar.length > 1){
        b.addEventListener('dblclick', function(e){ e.stopPropagation(); katMenu(i); });
      }
      sekmeListEl.appendChild(b);
    });

    // Öğeleri temizle (svg + izgara hariç)
    Array.prototype.slice.call(kanvas.querySelectorAll('.oge')).forEach(function(e){ e.remove(); });

    cizParsel();
    state.bolgeler.forEach(function(b, i){ if(n(b.kat)===aktifKat) cizBolge(b,i); });
    state.noktalar.forEach(function(nk,i){ if(n(nk.kat)===aktifKat) cizNokta(nk,i); });
    Object.keys(state.masalar).forEach(function(mid){ if(n(state.masalar[mid].kat)===aktifKat) cizMasa(mid, state.masalar[mid]); });

    var dolu = state.bolgeler.length || state.noktalar.length || Object.keys(state.masalar).length || state.parsel.length;
    bosUyari.style.display = dolu ? 'none' : '';
    parselCubuk.style.display = aktifParsel() ? 'flex' : 'none';

    seciliCubukGuncelle();
  }

  // ---- PARSEL ----
  function cizParsel(){
    svg.innerHTML = '';
    var pr = aktifParsel();
    if (!pr || !pr.pts || pr.pts.length < 2) return;
    // sanal x:0..1000, y:0..1000  → viewBox 1000 x 1350 (oran 1.35, Flutter ile aynı)
    var pts = pr.pts.map(function(p){ return n(p[0])+','+(n(p[1])*1.35); }).join(' ');
    var poly = document.createElementNS('http://www.w3.org/2000/svg','polygon');
    poly.setAttribute('points', pts);
    poly.setAttribute('fill', 'rgba(14,165,233,0.10)');
    poly.setAttribute('stroke', '#0EA5E9');
    poly.setAttribute('stroke-width', '4');
    svg.appendChild(poly);

    pr.pts.forEach(function(p, ki){
      var h = document.createElement('div');
      h.className = 'oge oge-kose' + (sc('kose',ki) ? ' sec' : '');
      var w = sc('kose',ki) ? 26 : 20;
      h.style.left = (sx(p[0]) - w/2) + 'px';
      h.style.top  = (sy(p[1]) - w/2) + 'px';
      h.title = 'Köşe ' + (ki+1);
      h.addEventListener('click', function(e){ e.stopPropagation(); secili={tur:'kose',anahtar:ki}; ciz(); });
      dragYap(h, function(ddx, ddy){
        p[0] = clamp(n(p[0])+vdx(ddx), 0, 1000);
        p[1] = clamp(n(p[1])+vdy(ddy), 0, 1000);
        h.style.left = (sx(p[0])-w/2)+'px';
        h.style.top  = (sy(p[1])-w/2)+'px';
        cizParsel();
      }, function(){ gecmisKaydet(); });
      kanvas.appendChild(h);
    });
  }

  // ---- BÖLGE ----
  function cizBolge(b, i){
    var d = document.createElement('div');
    var secli = sc('bolge', i);
    d.className = 'oge';
    d.style.cssText = 'left:'+sx(b.x)+'px;top:'+sy(b.y)+'px;width:'+sx(b.w)+'px;height:'+sy(b.h)+'px;' +
      'background:rgba(14,165,233,0.12);border-radius:10px;cursor:pointer;overflow:visible;' +
      'border:'+(secli?'2px solid #0EA5E9':'1.2px solid rgba(14,165,233,0.5)')+';';
    d.innerHTML = '<span style="font-size:11px;font-weight:700;color:#0EA5E9;padding:5px;display:inline-block;pointer-events:none">'+esc(b.ad)+'</span>';
    d.addEventListener('click', function(e){ e.stopPropagation(); secili={tur:'bolge',anahtar:i}; ciz(); });
    if (secli){
      // TAŞI (sağ üst)
      var mv = document.createElement('div');
      mv.className = 'oge-tutamak';
      mv.style.cssText = 'right:0;top:0;width:28px;height:28px;background:#0EA5E9;border-radius:8px;color:#fff';
      mv.innerHTML = '<span class="material-icons-outlined">open_with</span>';
      dragYap(mv, function(ddx,ddy){
        b.x=clamp(n(b.x)+vdx(ddx),0,1000); b.y=clamp(n(b.y)+vdy(ddy),0,1000);
        d.style.left=sx(b.x)+'px'; d.style.top=sy(b.y)+'px';
      }, function(){ gecmisKaydet(); });
      d.appendChild(mv);
      // BOYUT (sağ alt)
      var rs = document.createElement('div');
      rs.className = 'oge-tutamak';
      rs.style.cssText = 'right:0;bottom:0;width:26px;height:26px;background:#0EA5E9;border-radius:6px;color:#fff';
      rs.innerHTML = '<span class="material-icons-outlined">open_in_full</span>';
      dragYap(rs, function(ddx,ddy){
        b.w=clamp(n(b.w)+vdx(ddx),90,1000); b.h=clamp(n(b.h)+vdy(ddy),70,1000);
        d.style.width=sx(b.w)+'px'; d.style.height=sy(b.h)+'px';
      }, function(){ gecmisKaydet(); });
      d.appendChild(rs);
    }
    kanvas.appendChild(d);
  }

  // ---- NOKTA ----
  function cizNokta(nk, i){
    var def = TIPLER[nk.tip] || TIPLER['diger'];
    var boy = n(nk.boy) > 0 ? n(nk.boy) : 58;
    var bpx = clamp(sx(boy), 24, px().w); // üst sınır kaldırıldı -> ısı haritasıyla birebir (sx(boy))
    var secli = sc('nokta', i);
    var d = document.createElement('div');
    d.className = 'oge';
    d.style.cssText = 'left:'+(sx(nk.x)-bpx/2)+'px;top:'+(sy(nk.y)-bpx/2)+'px;display:flex;flex-direction:column;align-items:center;cursor:move;';
    d.innerHTML =
      '<div style="width:'+bpx+'px;height:'+bpx+'px;display:flex;align-items:center;justify-content:center;border-radius:12px;' +
        'background:'+def[1]+';box-shadow:0 2px 6px '+def[1]+'66;' +
        (secli?'outline:2.5px solid #fff;outline-offset:1px;':'') + '">' +
        '<span class="material-icons-outlined" style="font-size:'+Math.max(13, Math.round(bpx*0.42))+'px;color:#fff">'+def[0]+'</span></div>' +
      '<span style="font-size:10px;font-weight:700;color:var(--ink);margin-top:2px;pointer-events:none">'+esc(nk.ad||def[2])+'</span>';
    d.addEventListener('click', function(e){ e.stopPropagation(); secili={tur:'nokta',anahtar:i}; ciz(); });
    dragYap(d, function(ddx,ddy){
      nk.x=clamp(n(nk.x)+vdx(ddx),0,1000); nk.y=clamp(n(nk.y)+vdy(ddy),0,1000);
      d.style.left=(sx(nk.x)-bpx/2)+'px'; d.style.top=(sy(nk.y)-bpx/2)+'px';
    }, function(){ gecmisKaydet(); });
    kanvas.appendChild(d);
  }

  // ---- MASA ----
  function cizMasa(mid, yer){
    var masa    = masaBul(mid);
    var yuvarlak = (masa.sekil||'kare') === 'yuvarlak';
    var boy  = n(yer.boy) > 0 ? n(yer.boy) : 170;
    var bpx  = clamp(sx(boy), 28, px().w);
    var secli = sc('masa', mid);
    var d = document.createElement('div');
    d.className = 'oge';
    d.style.cssText = 'left:'+(sx(yer.x)-bpx/2)+'px;top:'+(sy(yer.y)-bpx/2)+'px;' +
      'width:'+bpx+'px;height:'+bpx+'px;' +
      'display:flex;align-items:center;justify-content:center;cursor:move;overflow:visible;' +
      'background:var(--mor1);border-radius:'+(yuvarlak?'50%':'12px')+';' +
      'box-shadow:0 2px 6px rgba(124,58,237,0.35);' +
      (secli?'outline:2.5px solid #fff;outline-offset:1px;':'');
    d.innerHTML = '<span style="pointer-events:none;background:rgba(0,0,0,.55);padding:1.5px 6px;border-radius:7px;font-size:'+Math.max(9,Math.min(13,Math.round(bpx*0.2)))+'px;color:#fff;font-weight:900">'+esc(masa.ad)+'</span>';
    d.addEventListener('click', function(e){ e.stopPropagation(); secili={tur:'masa',anahtar:mid}; ciz(); });
    dragYap(d, function(ddx,ddy){
      yer.x=clamp(n(yer.x)+vdx(ddx),0,1000); yer.y=clamp(n(yer.y)+vdy(ddy),0,1000);
      d.style.left=(sx(yer.x)-bpx/2)+'px'; d.style.top=(sy(yer.y)-bpx/2)+'px';
    }, function(){ gecmisKaydet(); });
    if (secli){
      var rs = document.createElement('div');
      rs.className = 'oge-tutamak';
      rs.style.cssText = 'right:-7px;bottom:-7px;width:22px;height:22px;border-radius:50%;background:#fff;border:2px solid var(--mor1);color:var(--mor1)';
      rs.innerHTML = '<span class="material-icons-outlined">open_in_full</span>';
      dragYap(rs, function(ddx){
        yer.boy = clamp((n(yer.boy)>0?n(yer.boy):170)+vdx(ddx)*1.4, 90, 700);
        ciz();
      }, function(){ gecmisKaydet(); });
      d.appendChild(rs);
    }
    kanvas.appendChild(d);
  }

  // Zemin tıklayınca seçimi bırak
  kanvas.addEventListener('click', function(){ secili=null; ciz(); });

  // ---- SEÇİLİ CUBUK (Flutter _seciliCubuk + _seciliAdDegistir + _masaBoy + _noktaBoy) ----
  function seciliCubukGuncelle(){
    if (!secili){ seciliCubuk.style.display='none'; return; }
    seciliCubuk.style.display='flex';
    var tur = secili.tur, ak = secili.anahtar;
    var boyutAlan   = document.getElementById('sc-boyut-alan');
    var adDegBtn    = document.getElementById('sc-ad-degistir');
    var adLbl       = document.getElementById('sc-ad-lbl');
    boyutAlan.style.display = 'none';
    adDegBtn.style.display  = 'none';

    if (tur==='masa'){
      var m = masaBul(ak);
      adLbl.textContent = 'Seçili: ' + (m.ad||ak);
      boyutAlan.style.display = 'inline-flex';
      document.getElementById('sc-kucult').onclick = function(){ masaBoy(ak, 0.82); };
      document.getElementById('sc-buyut').onclick  = function(){ masaBoy(ak, 1.22); };
    } else if (tur==='bolge'){
      adLbl.textContent = 'Seçili: ' + (state.bolgeler[ak] && state.bolgeler[ak].ad ? state.bolgeler[ak].ad : 'Bölge');
      adDegBtn.style.display = 'inline-flex';
      adDegBtn.onclick = function(){ adGir('Bölge adı', state.bolgeler[ak].ad||'', function(v){ gecmisKaydet(); state.bolgeler[ak].ad=v; ciz(); }); };
    } else if (tur==='nokta'){
      var nk = state.noktalar[ak];
      var def = TIPLER[nk.tip]||TIPLER['diger'];
      adLbl.textContent = 'Seçili: ' + (nk.ad||def[2]);
      boyutAlan.style.display = 'inline-flex';
      adDegBtn.style.display  = 'inline-flex';
      document.getElementById('sc-kucult').onclick = function(){ noktaBoy(ak, 0.85); };
      document.getElementById('sc-buyut').onclick  = function(){ noktaBoy(ak, 1.18); };
      adDegBtn.onclick = function(){ adGir('Nokta adı', nk.ad||def[2], function(v){ gecmisKaydet(); state.noktalar[ak].ad=v; ciz(); }); };
    } else if (tur==='kose'){
      adLbl.textContent = 'Seçili: Köşe';
    }
    document.getElementById('sc-sil').onclick = function(){ ogeSil(); };
  }

  function masaBoy(mid, f){
    var yer = state.masalar[mid]; if(!yer) return;
    gecmisKaydet();
    yer.boy = clamp((n(yer.boy)>0?n(yer.boy):170)*f, 90, 700);
    ciz();
  }
  function noktaBoy(i, f){
    gecmisKaydet();
    var nk = state.noktalar[i];
    nk.boy = clamp((n(nk.boy)>0?n(nk.boy):58)*f, 28, 220);
    ciz();
  }

  function ogeSil(){
    if(!secili) return;
    gecmisKaydet();
    if (secili.tur==='masa') delete state.masalar[secili.anahtar];
    else if (secili.tur==='bolge') state.bolgeler.splice(secili.anahtar,1);
    else if (secili.tur==='nokta') state.noktalar.splice(secili.anahtar,1);
    else if (secili.tur==='kose'){ var pr=aktifParsel(); if(pr) pr.pts.splice(secili.anahtar,1); }
    secili=null; ciz();
  }

  // ---- PARSEL ARAÇLARI ----
  document.getElementById('kose-ekle').onclick = function(){
    var pr = aktifParsel();
    if (!pr || pr.pts.length < 2) return;
    var en=0, enU=-1;
    for (var i=0;i<pr.pts.length;i++){
      var a=pr.pts[i], b=pr.pts[(i+1)%pr.pts.length];
      var u=(a[0]-b[0])*(a[0]-b[0])+(a[1]-b[1])*(a[1]-b[1]);
      if(u>enU){enU=u;en=i;}
    }
    var a2=pr.pts[en], b2=pr.pts[(en+1)%pr.pts.length];
    gecmisKaydet();
    pr.pts.splice(en+1,0,[(a2[0]+b2[0])/2,(a2[1]+b2[1])/2]);
    ciz();
  };
  document.getElementById('parsel-sifir').onclick = function(){
    var pr=aktifParsel(); if(!pr) return;
    gecmisKaydet(); pr.pts=[[140,200],[860,200],[860,860],[140,860]]; ciz();
  };
  document.getElementById('parsel-sil').onclick = function(){
    gecmisKaydet();
    state.parsel=state.parsel.filter(function(p){ return n(p.kat)!==aktifKat; });
    if(secili && secili.tur==='kose') secili=null;
    ciz();
  };

  // ---- KAT EKLE ----
  document.getElementById('kat-ekle').onclick = function(){
    gecmisKaydet();
    var yeniAd = 'Kat ' + state.katlar.length;
    state.katlar.push(yeniAd);
    aktifKat = state.katlar.length - 1; secili = null; ciz();
    adGir('Kat adı', yeniAd, function(v){ state.katlar[aktifKat]=v; ciz(); });
  };

  // ---- KAT MENÜSÜ (long-press / dblclick → modal) ----
  var _katMenuIdx = -1;
  function katMenu(i){
    _katMenuIdx = i;
    document.getElementById('modal-kat-baslik').textContent = 'Kat: ' + state.katlar[i];
    var silBtn = document.getElementById('modal-kat-sil');
    silBtn.style.display = state.katlar.length > 1 ? '' : 'none';
    document.getElementById('modal-kat').style.display = 'flex';
  }
  document.getElementById('modal-kat-yeniad').onclick = function(){
    document.getElementById('modal-kat').style.display = 'none';
    adGir('Kat adı', state.katlar[_katMenuIdx], function(v){ state.katlar[_katMenuIdx]=v; ciz(); });
  };
  document.getElementById('modal-kat-sil').onclick = function(){
    document.getElementById('modal-kat').style.display = 'none';
    katSil(_katMenuIdx);
  };
  document.getElementById('modal-kat-kapat').onclick = function(){
    document.getElementById('modal-kat').style.display = 'none';
  };
  function katSil(i){
    if (state.katlar.length <= 1) return;
    gecmisKaydet();
    state.bolgeler = state.bolgeler.filter(function(b){ return n(b.kat)!==i; });
    state.noktalar = state.noktalar.filter(function(nk){ return n(nk.kat)!==i; });
    Object.keys(state.masalar).forEach(function(k){ if(n(state.masalar[k].kat)===i) delete state.masalar[k]; });
    state.parsel = state.parsel.filter(function(p){ return n(p.kat)!==i; });
    state.bolgeler.forEach(function(b){ if(n(b.kat)>i) b.kat=n(b.kat)-1; });
    state.noktalar.forEach(function(nk){ if(n(nk.kat)>i) nk.kat=n(nk.kat)-1; });
    Object.keys(state.masalar).forEach(function(k){ if(n(state.masalar[k].kat)>i) state.masalar[k].kat=n(state.masalar[k].kat)-1; });
    state.parsel.forEach(function(p){ if(n(p.kat)>i) p.kat=n(p.kat)-1; });
    state.katlar.splice(i,1);
    aktifKat=clamp(aktifKat,0,state.katlar.length-1);
    secili=null; ciz();
  }

  // ---- KAT TEMİZLE ----
  document.getElementById('kat-temizle-btn').onclick = function(){
    onayGoster(
      'Bu katı temizle?',
      'Bu kattaki masalar, bölgeler, noktalar ve salon sınırı kaldırılır (kaydedene kadar geri alınabilir).',
      'Temizle',
      function(){
        gecmisKaydet();
        state.bolgeler = state.bolgeler.filter(function(b){ return n(b.kat)!==aktifKat; });
        state.noktalar = state.noktalar.filter(function(nk){ return n(nk.kat)!==aktifKat; });
        Object.keys(state.masalar).forEach(function(k){ if(n(state.masalar[k].kat)===aktifKat) delete state.masalar[k]; });
        state.parsel = state.parsel.filter(function(p){ return n(p.kat)!==aktifKat; });
        secili=null; ciz();
      }
    );
  };

  // ---- ALT ARAÇ ÇUBUĞU ----
  document.getElementById('ekle-masa').onclick   = function(){ masaEkleModal(); };
  document.getElementById('ekle-nokta').onclick  = function(){ noktaEkleModal(); };
  document.getElementById('ekle-parsel').onclick = function(){
    if (!aktifParsel()){
      gecmisKaydet();
      state.parsel.push({kat:aktifKat, pts:[[140,200],[860,200],[860,860],[140,860]]});
    }
    ciz();
  };
  document.getElementById('ekle-bolge').onclick = function(){
    adGir('Bölge adı', 'Bölge', function(ad){
      gecmisKaydet();
      state.bolgeler.push({kat:aktifKat,ad:ad||'Bölge',x:300,y:300,w:320,h:240});
      secili={tur:'bolge',anahtar:state.bolgeler.length-1};
      ciz();
    });
  };

  // ---- NOKTA MODAL ----
  function noktaEkleModal(){
    document.getElementById('modal-nokta').style.display = 'flex';
  }
  document.querySelectorAll('.ss-tip-kart').forEach(function(kart){
    kart.addEventListener('click', function(){
      document.getElementById('modal-nokta').style.display = 'none';
      var tip = kart.getAttribute('data-tip');
      var def = TIPLER[tip] || TIPLER['diger'];
      gecmisKaydet();
      state.noktalar.push({kat:aktifKat,tip:tip,ad:def[2],x:500,y:500});
      secili={tur:'nokta',anahtar:state.noktalar.length-1};
      ciz();
    });
  });
  document.getElementById('modal-nokta').addEventListener('click', function(e){
    if(e.target===this) this.style.display='none';
  });

  // ---- MASA MODAL ----
  function masaEkleModal(){
    var yerlesmis = {};
    Object.keys(state.masalar).forEach(function(k){ yerlesmis[k]=1; });
    var bekleyen = masaList.filter(function(m){ return !yerlesmis[String(m.id)]; });
    var grid = document.getElementById('masa-bekleyen-grid');
    var hepsiLbl = document.getElementById('masa-hepsi-lbl');
    var kaldir = document.getElementById('masa-tum-kaldir');
    grid.innerHTML = '';
    if (bekleyen.length === 0){
      hepsiLbl.style.display = ''; grid.style.display = 'none';
    } else {
      hepsiLbl.style.display = 'none'; grid.style.display = 'flex';
      bekleyen.forEach(function(m){
        var btn = document.createElement('button');
        btn.type = 'button'; btn.className = 'ss-masa-btn';
        btn.textContent = m.ad;
        btn.onclick = function(){
          document.getElementById('modal-masa').style.display = 'none';
          gecmisKaydet();
          state.masalar[String(m.id)] = {kat:aktifKat,x:500,y:450,boy:170};
          secili={tur:'masa',anahtar:String(m.id)};
          ciz();
        };
        grid.appendChild(btn);
      });
    }
    kaldir.style.display = Object.keys(state.masalar).length > 0 ? '' : 'none';
    document.getElementById('modal-masa').style.display = 'flex';
  }
  document.getElementById('masa-tum-kaldir').onclick = function(){
    document.getElementById('modal-masa').style.display = 'none';
    gecmisKaydet(); state.masalar={}; secili=null; ciz();
  };
  document.getElementById('modal-masa').addEventListener('click', function(e){
    if(e.target===this) this.style.display='none';
  });

  // ---- AD GİRİŞİ MODAL ----
  var _adCb = null;
  function adGir(baslik, mevcut, cb){
    _adCb = cb;
    document.getElementById('modal-ad-baslik').textContent = baslik;
    var inp = document.getElementById('modal-ad-inp');
    inp.value = mevcut || '';
    inp.placeholder = baslik;
    document.getElementById('modal-ad').style.display = 'flex';
    setTimeout(function(){ inp.focus(); inp.select(); }, 60);
  }
  function adTamam(){
    var v = document.getElementById('modal-ad-inp').value.trim();
    document.getElementById('modal-ad').style.display = 'none';
    if (v && _adCb) _adCb(v);
    _adCb = null;
  }
  document.getElementById('modal-ad-tamam').onclick = adTamam;
  document.getElementById('modal-ad-inp').addEventListener('keydown', function(e){ if(e.key==='Enter') adTamam(); });
  document.getElementById('modal-ad').addEventListener('click', function(e){ if(e.target===this){ this.style.display='none'; _adCb=null; } });

  // ---- ONAY MODAL ----
  var _onayCb = null;
  function onayGoster(baslik, icerik, okLbl, cb){
    _onayCb = cb;
    document.getElementById('modal-onay-baslik').textContent = baslik;
    document.getElementById('modal-onay-icerik').textContent = icerik;
    document.getElementById('modal-onay-ok').textContent = okLbl || 'Tamam';
    document.getElementById('modal-onay').style.display = 'flex';
  }
  document.getElementById('modal-onay-vazgec').onclick = function(){
    document.getElementById('modal-onay').style.display='none'; _onayCb=null;
  };
  document.getElementById('modal-onay-ok').onclick = function(){
    document.getElementById('modal-onay').style.display='none';
    if (_onayCb) _onayCb(); _onayCb=null;
  };
  document.getElementById('modal-onay').addEventListener('click', function(e){ if(e.target===this){ this.style.display='none'; _onayCb=null; } });

  // ---- KAYDET ----
  document.getElementById('sema-kaydet').onclick = function(){
    durumEl.textContent='Kaydediliyor...'; durumEl.style.color='var(--sub)';
    var veri = {katlar:state.katlar, parsel:state.parsel, bolgeler:state.bolgeler, noktalar:state.noktalar, masalar:state.masalar};
    var fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('veri', JSON.stringify(veri));
    fetch(KAYDET_URL, {method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':CSRF}})
      .then(function(r){ return r.json().catch(function(){ return {ok:0}; }); })
      .then(function(res){
        if (res && res.ok===1){ durumEl.textContent='Kaydedildi ✓'; durumEl.style.color='var(--yesil)'; }
        else { durumEl.textContent=(res&&res.hata)?res.hata:'Kaydedilemedi'; durumEl.style.color='var(--kirmizi)'; }
        setTimeout(function(){ durumEl.textContent=''; },3000);
      })
      .catch(function(){ durumEl.textContent='Bağlantı hatası'; durumEl.style.color='var(--kirmizi)'; });
  };

  // ---- İLK ÇİZİM + RESIZE ----
  requestAnimationFrame(function(){ boyutla(); ciz(); });
  window.addEventListener('load', function(){ boyutla(); ciz(); });
  var tmr = null;
  window.addEventListener('resize', function(){ clearTimeout(tmr); tmr=setTimeout(function(){ boyutla(); ciz(); },150); });

  } catch(e) {
    var k = document.getElementById('sema-kanvas');
    if (k) k.insertAdjacentHTML('afterend','<div style="color:var(--kirmizi);font-size:13px;margin-top:12px">Şema yüklenemedi: '+String(e)+'</div>');
    if (window.console) console.error(e);
  }
})();
</script>
@endsection

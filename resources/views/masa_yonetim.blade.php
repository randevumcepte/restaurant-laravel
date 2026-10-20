@extends('layout.app')
@section('title', 'Masa & QR Yönetimi')
@section('rk_ikon', 'qr_code_2')
@section('baslik', 'Masa & QR Yönetimi')
@section('rk_alt', 'Her masa için KALICI QR kodu — asla değişmez. Silme = arşivle, geri getir aynı kod.')

@section('rk_araclar')
  <button type="button" class="rk-btn" style="background:var(--mor1)"
          onclick="masaYazdirHepsi()" @if(empty($aktif)) disabled @endif>
    <span class="material-icons-outlined" style="font-size:18px">print</span> Tüm QR afişlerini yazdır
  </button>
@endsection

@push('head')
<style>
#my-root{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f1f5f9}
body.koyu #my-root{--card:#161d2e;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#1b2235}
/* ---------- Tab şeridi ---------- */
#my-root .my-tabs{display:flex;border-bottom:2px solid var(--line);margin-bottom:20px;gap:0}
#my-root .my-tab{display:flex;align-items:center;gap:6px;padding:10px 20px;cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;font-size:14px;font-weight:700;color:var(--sub);transition:color .15s,border-color .15s;user-select:none}
#my-root .my-tab.akt{color:var(--mor1);border-bottom-color:var(--mor1)}
#my-root .my-tab:hover:not(.akt){color:var(--ink)}
/* ---------- Grid (Flutter: maxCrossAxisExtent 220, ratio 0.82) ---------- */
#my-root .my-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
/* ---------- Masa kartı ---------- */
#my-root .my-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:10px;display:flex;flex-direction:column;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:transform .1s,box-shadow .1s;cursor:pointer}
#my-root .my-kart:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.1)}
#my-root .my-qr-wrap{background:#fff;border-radius:10px;padding:8px;display:flex;align-items:center;justify-content:center;aspect-ratio:1/1;width:100%;overflow:hidden}
#my-root .my-qr-wrap img{width:100%;height:100%;object-fit:contain;display:block}
#my-root .my-qr-wrap .qr-ph{font-size:64px;color:#cbd5e1}
/* ---------- Mini buton satırı ---------- */
#my-root .my-kart-btns{display:flex;justify-content:space-evenly;margin-top:8px}
#my-root .my-icon-btn{display:inline-flex;align-items:center;justify-content:center;padding:5px;border-radius:8px;border:none;background:transparent;cursor:pointer;transition:background .12s}
#my-root .my-icon-btn:hover{background:color-mix(in srgb,currentColor 12%,transparent)}
#my-root .my-icon-btn .material-icons-outlined{font-size:20px}
/* ---------- Bölge başlık ---------- */
#my-root .bolge-baslik{display:flex;align-items:center;gap:8px;margin-bottom:10px}
#my-root .bolge-cubuk{width:4px;height:18px;border-radius:3px;background:var(--mavi);flex-shrink:0}
#my-root .bolge-ad{font-size:14px;font-weight:800;color:var(--ink)}
#my-root .bolge-sayi{font-size:11px;font-weight:700;color:var(--mavi);background:color-mix(in srgb,var(--mavi) 13%,transparent);border-radius:12px;padding:2px 8px}
/* ---------- Boş durum ---------- */
#my-root .my-bos{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;text-align:center}
#my-root .my-bos .material-icons-outlined{font-size:64px;color:var(--sub2)}
#my-root .my-bos .b{font-size:18px;font-weight:800;color:var(--ink);margin-top:14px}
#my-root .my-bos .s{font-size:13px;color:var(--sub);margin-top:8px;line-height:1.5}
/* ---------- Arşiv liste ---------- */
#my-root .arsiv-satir{display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:12px;background:var(--card)}
/* ---------- Masa ekle bölümü ---------- */
#my-root .ekle-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#my-root .ekle-card .rk-inp{background:var(--bg)}
/* ---------- Modal ---------- */
#afisModal{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px}
#afisModal .modal-box{background:var(--card);border-radius:20px;max-width:360px;width:100%;padding:24px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.25)}
/* ---------- Düzenle modal ---------- */
#duzenleModal{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px}
#duzenleModal .modal-box{background:var(--card);border-radius:20px;max-width:400px;width:100%;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
/* ---------- FAB ---------- */
#my-root .my-fab{position:fixed;bottom:28px;right:28px;z-index:90;display:flex;align-items:center;gap:8px;background:var(--mor1);color:#fff;border:none;border-radius:50px;padding:14px 22px;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 6px 20px rgba(124,58,237,.45);transition:transform .1s,box-shadow .1s}
#my-root .my-fab:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(124,58,237,.55)}
#my-root .my-fab .material-icons-outlined{font-size:22px}
/* ---------- Flash ---------- */
.rk-flash{padding:10px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:14px}
.rk-flash.ok{background:color-mix(in srgb,#10B981 15%,transparent);color:#065f46;border:1px solid color-mix(in srgb,#10B981 40%,transparent)}
.rk-flash.err{background:color-mix(in srgb,#F43F5E 12%,transparent);color:#9f1239;border:1px solid color-mix(in srgb,#F43F5E 35%,transparent)}
body.koyu .rk-flash.ok{color:#6ee7b7}
body.koyu .rk-flash.err{color:#fda4af}
</style>
@endpush

@section('content')
@php
  $aktif    = $aktif    ?? [];
  $arsiv    = $arsiv    ?? [];
  $bolgeler = $bolgeler ?? collect();

  // Aktif masaları bölgeye göre grupla
  $gruplar = [];
  foreach ($aktif as $m) {
      $bk = !empty($m['bolge']) ? $m['bolge'] : 'Bölgesiz';
      $gruplar[$bk][] = $m;
  }
  ksort($gruplar);
  $aktifSubeAd = $aktifSubeAd ?? session('sube_adi', 'Restoran');

  // Düzenle AJAX için: session'daki personelin api_token'ı
  $webPersonel = function_exists('_posPersonel') ? _posPersonel() : null;
  $webApiToken = $webPersonel->api_token ?? null;
@endphp

<div id="my-root">

@if (session('ok'))
  <div class="rk-flash ok">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="rk-flash err">{{ session('hata') }}</div>
@endif

{{-- ===== TAB ŞERİDİ (Flutter TabBar: Masalar / Arşiv) ===== --}}
<div class="my-tabs">
  <div class="my-tab akt" id="tab-aktif" onclick="sekmeGec('aktif')">
    <span class="material-icons-outlined" style="font-size:18px">table_restaurant</span>
    Masalar ({{ count($aktif) }})
  </div>
  <div class="my-tab" id="tab-arsiv" onclick="sekmeGec('arsiv')">
    <span class="material-icons-outlined" style="font-size:18px">inventory_2</span>
    Arşiv ({{ count($arsiv) }})
  </div>
</div>

{{-- ===== PANEL: AKTİF MASALAR ===== --}}
<div id="panel-aktif">

  {{-- Masa Ekle bölümü (Flutter: FAB + dialog → web: inline form) --}}
  <div class="ekle-card">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
      <span style="width:4px;height:18px;border-radius:3px;background:var(--mor1);display:inline-block;flex-shrink:0"></span>
      <span style="font-size:14px;font-weight:800;color:var(--ink)">Masa Ekle</span>
    </div>
    <form method="POST" action="/masa-yonetim/ekle">
      @csrf
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end">
        <div>
          <label class="rk-lbl" style="margin-top:0;font-size:12px">Masa adı <span style="color:var(--sub)">(boş = otomatik)</span></label>
          <input type="text" name="ad" class="rk-inp" placeholder="Örn. Masa 5 / Teras 2">
        </div>
        <div>
          <label class="rk-lbl" style="margin-top:0;font-size:12px">Kapasite (kişi)</label>
          <input type="number" name="kapasite" class="rk-inp" value="4" min="1" max="50">
        </div>
        <div>
          <label class="rk-lbl" style="margin-top:0;font-size:12px">Bölge</label>
          <select name="bolge_id" class="rk-inp">
            <option value="">Bölgesiz</option>
            @foreach ($bolgeler as $b)
              <option value="{{ $b->id }}">{{ $b->ad }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <button type="submit" class="rk-btn lg" style="background:var(--mor1);width:100%">
            <span class="material-icons-outlined" style="font-size:18px">add</span> Ekle (QR otomatik)
          </button>
        </div>
      </div>
    </form>
    <div style="font-size:11.5px;color:var(--sub);margin-top:10px">Her masa için kalıcı QR kodu otomatik oluşturulur ve asla değişmez.</div>
  </div>

  {{-- Aktif masa grid --}}
  @if (empty($aktif))
    <div class="my-bos">
      <span class="material-icons-outlined">table_restaurant</span>
      <div class="b">Henüz masa yok</div>
      <div class="s">Yukarıdan "Masa Ekle" ile başla.<br>Her masa için QR kod otomatik oluşur.</div>
    </div>
  @else
    @foreach ($gruplar as $bolge => $masalar)
      <div style="margin-bottom:24px">
        <div class="bolge-baslik">
          <div class="bolge-cubuk"></div>
          <span class="bolge-ad">{{ $bolge }}</span>
          <span class="bolge-sayi">{{ count($masalar) }}</span>
        </div>
        <div class="my-grid">
          @foreach ($masalar as $m)
            @php $qrOk = !empty($m['qr_url']); @endphp
            <div class="my-kart" onclick="masaAfis(@js($m))">
              {{-- QR görsel alanı (Flutter: Expanded + Container beyaz zemin + QrImageView) --}}
              <div class="my-qr-wrap">
                @if ($qrOk)
                  <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data={{ urlencode($m['qr_url']) }}"
                       alt="QR {{ $m['ad'] }}" loading="lazy">
                @else
                  <span class="material-icons-outlined qr-ph">qr_code_2</span>
                @endif
              </div>

              {{-- Ad + kapasite + bölge (Flutter: Text ad bold + Text sub) --}}
              <div style="margin-top:8px;text-align:center">
                <div style="font-size:15px;font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m['ad'] }}</div>
                <div style="font-size:11px;color:var(--sub)">{{ $m['kapasite'] }} kişi{{ !empty($m['bolge']) ? ' · ' . $m['bolge'] : '' }}</div>
              </div>

              {{-- Mini buton satırı (Flutter: QR / Düzenle / Sil) --}}
              <div class="my-kart-btns" onclick="event.stopPropagation()">
                {{-- QR görüntüle --}}
                <button type="button" class="my-icon-btn" title="QR Afişi" style="color:var(--mor1)" onclick="masaAfis(@js($m))">
                  <span class="material-icons-outlined">visibility</span>
                </button>
                {{-- Düzenle --}}
                <button type="button" class="my-icon-btn" title="Düzenle" style="color:var(--mavi)" onclick="masaDuzenleAc(@js($m))">
                  <span class="material-icons-outlined">edit</span>
                </button>
                {{-- Arşivle --}}
                <button type="button" class="my-icon-btn" title="Arşivle" style="color:var(--kirmizi)"
                        onclick="masaSilOnay(@js($m['id']), @js($m['ad']))">
                  <span class="material-icons-outlined">delete_outline</span>
                </button>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  @endif
</div>{{-- /panel-aktif --}}

{{-- ===== PANEL: ARŞİV ===== --}}
<div id="panel-arsiv" style="display:none">
  @if (empty($arsiv))
    <div class="my-bos">
      <span class="material-icons-outlined">inventory_2</span>
      <div class="b">Arşiv boş</div>
      <div class="s">Silinen masalar buraya düşer.<br>QR kodları korunur; tek tıkla aynı kodla geri gelir.</div>
    </div>
  @else
    <div style="display:flex;flex-direction:column;gap:8px">
      @foreach ($arsiv as $m)
        <div class="arsiv-satir">
          <span class="material-icons-outlined" style="font-size:30px;color:var(--sub2);flex-shrink:0">qr_code_2</span>
          <div style="min-width:0;flex:1">
            <div style="font-size:15px;font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m['ad'] }}</div>
            <div style="font-size:11.5px;color:var(--sub)">QR korunuyor · kalıcı kod</div>
          </div>
          <form method="POST" action="/masa-yonetim/geri" style="flex-shrink:0">
            @csrf
            <input type="hidden" name="id" value="{{ $m['id'] }}">
            <button type="submit" class="rk-btn" style="background:var(--yesil)">
              <span class="material-icons-outlined" style="font-size:16px">restore</span> Geri Getir
            </button>
          </form>
        </div>
      @endforeach
    </div>
  @endif
</div>{{-- /panel-arsiv --}}

{{-- ===== QR AFİŞ ÖNIZLEME MODALI ===== --}}
<div id="afisModal">
  <div class="modal-box">
    <div id="afisSube" style="font-size:16px;font-weight:800;color:var(--ink)">{{ $aktifSubeAd }}</div>
    <div style="font-size:12px;color:var(--sub);margin-top:2px">Karekodu telefonunuzla okutun</div>
    <div style="background:#fff;border-radius:16px;padding:14px;margin:14px auto 0;display:inline-flex">
      <img id="afisQr" src="" alt="QR" style="width:220px;height:220px;object-fit:contain">
    </div>
    <div id="afisAd" style="font-size:24px;font-weight:800;color:var(--ink);margin-top:12px">-</div>
    <div style="font-size:11px;color:var(--sub)">Menü · Sipariş · Garson Çağır · Öde</div>
    <div style="display:flex;gap:10px;margin-top:18px">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="afisKapat()">Kapat</button>
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="masaYazdirTek()">
        <span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır
      </button>
    </div>
  </div>
</div>

{{-- ===== DÜZENLE MODALI ===== --}}
<div id="duzenleModal">
  <div class="modal-box">
    <div style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:16px">Masa Düzenle</div>
    <form method="POST" id="duzenleForm" action="#">{{-- JS submit ile /api/patron/masa-guncelle fetch --}}
      @csrf
      <input type="hidden" name="_masa_id" id="duzenleMasaId">
      <div style="margin-bottom:12px">
        <label class="rk-lbl" style="margin-top:0;font-size:12px">Masa adı</label>
        <input type="text" name="ad" id="duzenleAd" class="rk-inp">
      </div>
      <div style="margin-bottom:12px">
        <label class="rk-lbl" style="margin-top:0;font-size:12px">Kapasite (kişi)</label>
        <input type="number" name="kapasite" id="duzenleKapasite" class="rk-inp" min="1" max="50">
      </div>
      <div style="font-size:11px;color:var(--sub);margin-bottom:16px">QR kodu değişmez.</div>
      <div style="display:flex;gap:10px">
        <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="duzenleKapat()">İptal</button>
        <button type="submit" class="rk-btn" style="flex:1;justify-content:center;background:var(--mavi)">
          <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== GİZLİ SİL FORMU ===== --}}
<form method="POST" id="silForm" action="/masa-yonetim/sil" style="display:none">
  @csrf
  <input type="hidden" name="id" id="silId">
</form>

</div>{{-- /#my-root --}}
@endsection

@push('scripts')
<script>
(function(){
  var SUBE = @js($aktifSubeAd ?? 'Restoran');
  var aktifMasa = null;

  // ---- Sekme geçişi (Flutter TabBar) ----
  window.sekmeGec = function(s){
    var aktif  = s === 'aktif';
    document.getElementById('panel-aktif').style.display = aktif ? '' : 'none';
    document.getElementById('panel-arsiv').style.display = aktif ? 'none' : '';
    document.getElementById('tab-aktif').classList.toggle('akt', aktif);
    document.getElementById('tab-arsiv').classList.toggle('akt', !aktif);
  };

  // ---- QR URL oluştur ----
  function qrImg(url, size){
    return 'https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&margin=0&data='+encodeURIComponent(url);
  }

  // ---- QR afiş önizleme ----
  var afisModal = document.getElementById('afisModal');
  window.masaAfis = function(m){
    if(!m || !m.qr_url){ alert('Bu masanın QR kodu henüz oluşmamış.'); return; }
    aktifMasa = m;
    document.getElementById('afisAd').textContent  = m.ad || '-';
    document.getElementById('afisSube').textContent = SUBE;
    document.getElementById('afisQr').src = qrImg(m.qr_url, 220);
    afisModal.style.display = 'flex';
  };
  window.afisKapat = function(){ afisModal.style.display = 'none'; aktifMasa = null; };
  afisModal.addEventListener('click', function(e){ if(e.target === afisModal) window.afisKapat(); });

  // ---- Masa düzenle modal (Flutter _masaDuzenleDialog) ----
  var API_TOKEN = @js($webApiToken ?? '');
  var duzenleModal  = document.getElementById('duzenleModal');
  var duzenleForm   = document.getElementById('duzenleForm');
  window.masaDuzenleAc = function(m){
    document.getElementById('duzenleMasaId').value   = m.id;
    document.getElementById('duzenleAd').value       = m.ad || '';
    document.getElementById('duzenleKapasite').value = m.kapasite || 4;
    duzenleModal.style.display = 'flex';
  };
  window.duzenleKapat = function(){ duzenleModal.style.display = 'none'; };
  // Düzenle formu: mevcut API ucu (/api/patron/masa-guncelle) fetch ile çağır (routes/web.php değişmez)
  duzenleForm.addEventListener('submit', function(e){
    e.preventDefault();
    if(!API_TOKEN){ alert('Oturum bilgisi bulunamadı. Lütfen yeniden giriş yapın.'); return; }
    var id  = document.getElementById('duzenleMasaId').value;
    var ad  = document.getElementById('duzenleAd').value.trim();
    var kap = document.getElementById('duzenleKapasite').value;
    fetch('/api/patron/masa-guncelle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + API_TOKEN, 'X-CSRF-TOKEN': (window.csrf || document.querySelector('meta[name=csrf-token]')?.content || '') },
      body: JSON.stringify({ id: parseInt(id), ad: ad, kapasite: parseInt(kap) })
    }).then(function(r){ return r.json(); }).then(function(d){
      window.duzenleKapat();
      if(d.ok == 1){ location.reload(); }
      else { alert(d.hata || 'Güncelleme başarısız.'); }
    }).catch(function(){ alert('Sunucuya ulaşılamadı.'); });
  });
  duzenleModal.addEventListener('click', function(e){ if(e.target === duzenleModal) window.duzenleKapat(); });

  // ---- Sil onayı (Flutter _silOnay AlertDialog) ----
  window.masaSilOnay = function(id, ad){
    var msg = (ad||'Bu masa') + ' arşive taşınsın mı?\n\n' +
      'Bu masanın QR kodu metal/ağaç gibi fiziksel bir yere basılmış olabilir.\n\n' +
      'Masa SİLİNMEZ, arşive taşınır — QR kodu KORUNUR ve istediğin an aynı kodla geri getirebilirsin. ' +
      'Böylece yeniden bastırma masrafı olmaz.';
    if(!confirm(msg)) return;
    document.getElementById('silId').value = id;
    document.getElementById('silForm').submit();
  };

  // ---- Yazdır (tarayıcı penceresi, A6 afiş, Flutter _yazdir eşdeğeri) ----
  function esc(s){ return String(s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
  function afisHtml(liste){
    var kart = liste.map(function(m){
      if(!m || !m.qr_url) return '';
      return '<div class="afis">'+
        '<div class="s">'+esc(SUBE)+'</div>'+
        '<div class="h">Karekodu telefonunuzla okutun</div>'+
        '<img src="'+qrImg(m.qr_url,300)+'">'+
        '<div class="ad">'+esc(m.ad||'-')+'</div>'+
        '<div class="alt">Menü · Sipariş · Garson Çağır · Öde</div>'+
        '</div>';
    }).join('');
    return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>QR Afiş</title><style>'+
      '@page{size:A6;margin:10mm}'+
      'body{margin:0;font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,sans-serif}'+
      '.afis{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;'+
      'height:100vh;page-break-after:always}'+
      '.afis:last-child{page-break-after:auto}'+
      '.s{font-size:18px;font-weight:800}.h{font-size:11px;color:#555;margin-top:4px}'+
      '.afis img{width:150px;height:150px;margin:14px 0}'+
      '.ad{font-size:26px;font-weight:800}.alt{font-size:10px;color:#555;margin-top:3px}'+
      '</style></head><body>'+kart+'<\/body></html>';
  }
  function yazdir(liste){
    var temiz = liste.filter(function(m){ return m && m.qr_url; });
    if(!temiz.length){ alert('Yazdırılacak QR kodu bulunamadı.'); return; }
    var w = window.open('','_blank');
    if(!w){ alert('Yazdırma penceresi açılamadı (açılır pencere engellenmiş olabilir).'); return; }
    w.document.write(afisHtml(temiz));
    w.document.close();
    setTimeout(function(){ try{ w.focus(); w.print(); }catch(e){} }, 600);
  }
  window.masaYazdirTek  = function(){ if(aktifMasa) yazdir([aktifMasa]); };
  window.masaYazdirHepsi = function(){ yazdir(@js(array_values($aktif))); };

  // ---- ESC kapat ----
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      if(afisModal.style.display==='flex') window.afisKapat();
      if(duzenleModal.style.display==='flex') window.duzenleKapat();
    }
  });
})();
</script>
@endpush

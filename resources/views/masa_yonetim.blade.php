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

@section('content')
@php
  $aktif    = $aktif    ?? [];
  $arsiv    = $arsiv    ?? [];
  $bolgeler = $bolgeler ?? collect();

  // Aktif masalari bolgeye gore grupla (Flutter: bolge basliklari altinda kartlar)
  $gruplar = [];
  foreach ($aktif as $m) {
      $bk = !empty($m['bolge']) ? $m['bolge'] : 'Bölgesiz';
      $gruplar[$bk][] = $m;
  }
  ksort($gruplar);
@endphp

@if (session('ok'))
  <div class="rk-flash ok">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="rk-flash err">{{ session('hata') }}</div>
@endif

{{-- ===== OZET SERIT ===== --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
  <div class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:16px">table_restaurant</span> Aktif masa: {{ count($aktif) }}
  </div>
  <div class="rk-rozet" style="color:var(--sub2);background:color-mix(in srgb,var(--sub2) 13%,transparent);border-color:color-mix(in srgb,var(--sub2) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:16px">inventory_2</span> Arşiv: {{ count($arsiv) }}
  </div>
  <div class="rk-rozet" style="color:var(--mavi);background:color-mix(in srgb,var(--mavi) 13%,transparent);border-color:color-mix(in srgb,var(--mavi) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:16px">location_on</span> Bölge: {{ count($bolgeler) }}
  </div>
</div>

{{-- ===== MASA EKLE ===== --}}
<div class="rk-card" style="margin-bottom:22px">
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--mor1)"></span>
    <span class="b">Masa Ekle</span>
  </div>
  <form method="POST" action="/masa-yonetim/ekle">
    @csrf
    <div class="rk-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));align-items:end">
      <div>
        <label class="rk-lbl" style="margin-top:0">Masa adı <span class="rk-sub">(boş = otomatik)</span></label>
        <input type="text" name="ad" class="rk-inp" placeholder="Örn. Masa 5 / Teras 2">
      </div>
      <div>
        <label class="rk-lbl" style="margin-top:0">Kapasite (kişi)</label>
        <input type="number" name="kapasite" class="rk-inp" value="4" min="1" max="50">
      </div>
      <div>
        <label class="rk-lbl" style="margin-top:0">Bölge</label>
        <select name="bolge_id" class="rk-inp">
          <option value="">Varsayılan (Salon)</option>
          @foreach ($bolgeler as $b)
            <option value="{{ $b->id }}">{{ $b->ad }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <button type="submit" class="rk-btn lg" style="background:var(--mor1)">
          <span class="material-icons-outlined" style="font-size:18px">add</span> Ekle (QR otomatik)
        </button>
      </div>
    </div>
  </form>
  <div class="rk-sub" style="font-size:11.5px;margin-top:10px">Her masa için kalıcı QR kodu otomatik oluşturulur ve asla değişmez.</div>
</div>

{{-- ===== AKTIF MASALAR (bolgeye gore) ===== --}}
@if (empty($aktif))
  <div class="rk-card" style="text-align:center;padding:48px 20px;margin-bottom:22px">
    <span class="material-icons-outlined" style="font-size:56px;color:var(--sub2)">table_restaurant</span>
    <div class="rk-ink" style="font-size:18px;font-weight:800;margin-top:12px">Henüz masa yok</div>
    <div class="rk-sub" style="font-size:13px;margin-top:6px;line-height:1.5">
      Yukarıdan "Masa Ekle" ile başla.<br>Her masa için QR kod otomatik oluşur.
    </div>
  </div>
@else
  @foreach ($gruplar as $bolge => $masalar)
    <div style="margin-bottom:22px">
      <div class="rk-bolum">
        <span class="cubuk" style="background:var(--mavi)"></span>
        <span class="b">{{ $bolge }}</span>
        <span class="sayi" style="color:var(--mavi);background:color-mix(in srgb,var(--mavi) 13%,transparent)">{{ count($masalar) }}</span>
      </div>
      <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(210px,1fr))">
        @foreach ($masalar as $m)
          @php $qrOk = !empty($m['qr_url']); @endphp
          <div class="rk-card" style="padding:12px;display:flex;flex-direction:column">
            {{-- QR gorseli (beyaz zemin) — tiklayinca afis onizleme --}}
            <div onclick="masaAfis(@js($m))" title="QR afişini göster"
                 style="background:#fff;border-radius:12px;padding:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;aspect-ratio:1/1">
              @if ($qrOk)
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data={{ urlencode($m['qr_url']) }}"
                     alt="QR" style="width:100%;height:100%;object-fit:contain" loading="lazy">
              @else
                <span class="material-icons-outlined" style="font-size:72px;color:#cbd5e1">qr_code_2</span>
              @endif
            </div>

            <div style="margin-top:10px;display:flex;align-items:flex-start;justify-content:space-between;gap:6px">
              <div style="min-width:0">
                <div class="rk-ink" style="font-size:15px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m['ad'] }}</div>
                <div class="rk-sub" style="font-size:11.5px">{{ $m['kapasite'] }} kişi</div>
              </div>
              @if ($qrOk)
                <span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">● QR hazır</span>
              @else
                <span class="rk-rozet" style="color:var(--amber);background:color-mix(in srgb,var(--amber) 13%,transparent);border-color:color-mix(in srgb,var(--amber) 40%,transparent)">QR yok</span>
              @endif
            </div>

            <div style="margin-top:12px;display:flex;gap:8px">
              <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="masaAfis(@js($m))">
                <span class="material-icons-outlined" style="font-size:16px">visibility</span> QR
              </button>
              <form method="POST" action="/masa-yonetim/sil" style="flex:1"
                    onsubmit="return confirm('{{ addslashes($m['ad']) }} arşive taşınsın mı?\n\nBu masanın QR kodu fiziksel bir yere basılmış olabilir. Masa SİLİNMEZ, arşive taşınır — QR kodu KORUNUR ve istediğin an aynı kodla geri getirebilirsin. Böylece yeniden bastırma masrafı olmaz.');">
                @csrf
                <input type="hidden" name="id" value="{{ $m['id'] }}">
                <button type="submit" class="rk-btn bos" style="width:100%;justify-content:center;color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)">
                  <span class="material-icons-outlined" style="font-size:16px">archive</span> Arşivle
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endforeach
@endif

{{-- ===== ARSIV ===== --}}
<div class="rk-card">
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--sub2)"></span>
    <span class="b">Arşiv</span>
    @if (!empty($arsiv))
      <span class="sayi" style="color:var(--sub2);background:color-mix(in srgb,var(--sub2) 13%,transparent)">{{ count($arsiv) }}</span>
    @endif
  </div>
  <div class="rk-sub" style="font-size:11.5px;margin-bottom:14px">Silinen masalar buraya düşer. QR kodları korunur; tek tıkla aynı kodla geri gelir.</div>
  @if (empty($arsiv))
    <div class="rk-sub" style="text-align:center;padding:24px 0;font-size:13px">Arşiv boş.</div>
  @else
    <div style="display:flex;flex-direction:column;gap:8px">
      @foreach ($arsiv as $m)
        <div style="display:flex;align-items:center;gap:12px;padding:12px;border:1px solid var(--line);border-radius:12px">
          <span class="material-icons-outlined" style="font-size:30px;color:var(--sub2)">qr_code_2</span>
          <div style="min-width:0;flex:1">
            <div class="rk-ink" style="font-size:15px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m['ad'] }}</div>
            <div class="rk-sub" style="font-size:11.5px">{{ $m['kapasite'] }} kişi · QR korunuyor (kalıcı kod)</div>
          </div>
          <form method="POST" action="/masa-yonetim/geri">
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
</div>

{{-- ===== QR AFIS ONIZLEME MODALI (uygulama ici — tam viewport ortasi) ===== --}}
<div id="afisModal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card" style="max-width:360px;width:100%;text-align:center;padding:24px">
    <div class="rk-sub" id="afisSube" style="font-size:16px;font-weight:800;color:var(--ink)">{{ $aktifSubeAd ?? 'Restoran' }}</div>
    <div class="rk-sub" style="font-size:12px;margin-top:2px">Karekodu telefonunuzla okutun</div>
    <div style="background:#fff;border-radius:16px;padding:14px;margin:14px auto 0;display:inline-flex">
      <img id="afisQr" src="" alt="QR" style="width:220px;height:220px;object-fit:contain">
    </div>
    <div class="rk-ink" id="afisAd" style="font-size:24px;font-weight:800;margin-top:12px">-</div>
    <div class="rk-sub" style="font-size:11px">Menü · Sipariş · Garson Çağır · Öde</div>
    <div style="display:flex;gap:10px;margin-top:18px">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="afisKapat()">Kapat</button>
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="masaYazdirTek()">
        <span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (function(){
    var SUBE = @js($aktifSubeAd ?? 'Restoran');
    var aktifMasa = null;
    var modal = document.getElementById('afisModal');

    function qrImg(url, size){
      return 'https://api.qrserver.com/v1/create-qr-code/?size=' + size + 'x' + size + '&margin=0&data=' + encodeURIComponent(url);
    }

    window.masaAfis = function(m){
      if(!m || !m.qr_url){ alert('Bu masanın QR kodu henüz oluşmamış.'); return; }
      aktifMasa = m;
      document.getElementById('afisAd').textContent = m.ad || '-';
      document.getElementById('afisSube').textContent = SUBE;
      document.getElementById('afisQr').src = qrImg(m.qr_url, 220);
      modal.style.display = 'flex';
    };
    window.afisKapat = function(){ modal.style.display = 'none'; aktifMasa = null; };
    modal.addEventListener('click', function(e){ if(e.target === modal) window.afisKapat(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && modal.style.display === 'flex') window.afisKapat(); });

    // ---- Yazdir: A6 afis(ler) icin yeni pencere (tarayici yazdir diyalogu) ----
    function afisHtml(liste){
      var kart = liste.map(function(m){
        if(!m.qr_url) return '';
        return '<div class="afis">' +
          '<div class="s">' + esc(SUBE) + '</div>' +
          '<div class="h">Karekodu telefonunuzla okutun</div>' +
          '<img src="' + qrImg(m.qr_url, 300) + '">' +
          '<div class="ad">' + esc(m.ad || '-') + '</div>' +
          '<div class="alt">Menü · Sipariş · Garson Çağır · Öde</div>' +
          '</div>';
      }).join('');
      return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>QR Afiş</title><style>' +
        '@page{size:A6;margin:10mm}' +
        'body{margin:0;font-family:ui-sans-serif,system-ui,"Segoe UI",Roboto,sans-serif}' +
        '.afis{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;' +
        'height:100vh;page-break-after:always}' +
        '.afis:last-child{page-break-after:auto}' +
        '.s{font-size:18px;font-weight:800}.h{font-size:11px;color:#555;margin-top:4px}' +
        '.afis img{width:150px;height:150px;margin:14px 0}' +
        '.ad{font-size:26px;font-weight:800}.alt{font-size:10px;color:#555;margin-top:3px}' +
        '</style></head><body>' + kart + '<' + '/body></html>';
    }
    function esc(s){ return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function yazdir(liste){
      var temiz = liste.filter(function(m){ return m && m.qr_url; });
      if(!temiz.length){ alert('Yazdırılacak QR kodu bulunamadı.'); return; }
      var w = window.open('', '_blank');
      if(!w){ alert('Yazdırma penceresi açılamadı (açılır pencere engellenmiş olabilir).'); return; }
      w.document.write(afisHtml(temiz));
      w.document.close();
      // QR gorselleri yuklensin diye kisa bekleme
      setTimeout(function(){ try{ w.focus(); w.print(); }catch(e){} }, 600);
    }
    window.masaYazdirTek = function(){ if(aktifMasa) yazdir([aktifMasa]); };
    window.masaYazdirHepsi = function(){ yazdir(@js(array_values($aktif))); };
  })();
</script>
@endpush

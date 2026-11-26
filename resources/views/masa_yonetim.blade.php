@extends('layout.app')
@section('title', 'Masa & QR Yönetimi')
@section('rk_ikon', 'qr_code_2')
@section('baslik', 'Masa & QR Yönetimi')
@section('rk_alt', 'Her masa için KALICI QR kodu — asla değişmez. Silme = arşivle, geri getir aynı kod.')

@section('rk_araclar')
  <button type="button" class="rk-btn" id="qrZipBtn" style="background:#10B981"
          onclick="masaIndirHepsi()" @if(empty($aktif)) disabled @endif>
    <span class="material-icons-outlined" style="font-size:18px">download</span> <span id="qrZipYazi">Tüm QR kodları indir</span>
  </button>
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
/* ---------- Bölge sekmeleri + bölge paneli ---------- */
#my-root .bs-serit{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
#my-root .bs{display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:12px;border:1px solid var(--line);background:var(--card);color:var(--ink);font-size:14px;font-weight:700;cursor:pointer;transition:background .12s,border-color .12s}
#my-root .bs:hover{border-color:var(--mor1)}
#my-root .bs .n{font-size:11.5px;font-weight:700;color:var(--sub);background:var(--bg);border-radius:10px;padding:1px 8px}
#my-root .bs.akt{background:var(--mor1);border-color:var(--mor1);color:#fff}
#my-root .bs.akt .n{background:rgba(255,255,255,.22);color:#fff}
#my-root .bs.ekle{border-style:dashed;color:var(--mor1);background:transparent}
#my-root .bp-bas{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}
#my-root .bp-ad{font-size:18px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
#my-root .bp-ad .onek{font-size:12px;font-weight:700;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 12%,transparent);border-radius:8px;padding:2px 8px}
#my-root .bp-alt{font-size:12.5px;color:var(--sub);margin-top:2px}
#my-root .ekle-satir{display:grid;grid-template-columns:110px 130px minmax(180px,1fr) 180px;gap:12px;align-items:end}
#my-root .ekle-satir .rk-lbl,.bolge-form .rk-lbl{margin-top:0;font-size:12px}
@media(max-width:760px){#my-root .ekle-satir{grid-template-columns:1fr 1fr}#my-root .ekle-satir .ad-kutu,#my-root .ekle-satir>div:last-child{grid-column:1/-1}}
.bolge-form .bf-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.bolge-form .rk-inp{background:var(--bg)}
.bolge-form .bf-onizle{font-size:12.5px;color:var(--mor1);font-weight:600;margin-top:10px;min-height:18px}
@media(max-width:560px){.bolge-form .bf-grid{grid-template-columns:1fr 1fr}}
#bolgeModal,#bolgeDuzModal{display:none;position:fixed;inset:0;z-index:200;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px}
#bolgeModal .modal-box,#bolgeDuzModal .modal-box{background:var(--card);border-radius:20px;max-width:480px;width:100%;padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
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
  $seciliBolge = (int) ($seciliBolge ?? 0);

  // Masaları bölge id'sine göre grupla (bölgesi silinmiş eski masalar → 0 = "Bölgesiz")
  $bolgeIdler = $bolgeler->pluck('id')->map(fn ($x) => (int) $x)->all();
  $gruplar = [];
  foreach ($aktif as $m) {
      $bk = in_array((int) ($m['bolge_id'] ?? 0), $bolgeIdler, true) ? (int) $m['bolge_id'] : 0;
      $gruplar[$bk][] = $m;
  }
  $sekmeler = $bolgeler->map(fn ($b) => ['id' => (int) $b->id, 'ad' => $b->ad, 'on_ek' => $b->on_ek ?: ''])->values()->all();
  if (!empty($gruplar[0])) $sekmeler[] = ['id' => 0, 'ad' => 'Bölgesiz', 'on_ek' => ''];
  if (!collect($sekmeler)->contains('id', $seciliBolge)) $seciliBolge = $sekmeler[0]['id'] ?? 0;
  // Sıradaki otomatik ad (arşivdekiler dahil en büyük numara + 1) — "Otomatik: T9" ipucu için
  $sonrakiAd = [];
  foreach ($sekmeler as $s) {
      if ($s['on_ek'] === '') continue;
      $max = 0;
      foreach (array_merge($aktif, $arsiv) as $m)
          if (preg_match('/^' . preg_quote($s['on_ek'], '/') . '(\d+)$/u', (string) $m['ad'], $mm)) $max = max($max, (int) $mm[1]);
      $sonrakiAd[$s['id']] = $s['on_ek'] . ($max + 1);
  }
  $sonKapasite = (int) session('son_kapasite', 4);
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

{{-- ===== PANEL: AKTİF MASALAR — önce bölge, masa bölgenin içinde eklenir (Toast/Square/Lightspeed düzeni) ===== --}}
<div id="panel-aktif">

  @if (empty($sekmeler))
    {{-- Hiç bölge yok: ilk bölgeyi masalarıyla birlikte oluştur --}}
    <div class="ekle-card" style="max-width:620px;margin:10px auto">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
        <span style="width:4px;height:18px;border-radius:3px;background:var(--mor1);display:inline-block;flex-shrink:0"></span>
        <span style="font-size:15px;font-weight:800;color:var(--ink)">İlk bölgenizi oluşturun</span>
      </div>
      <div style="font-size:12.5px;color:var(--sub);margin-bottom:14px;line-height:1.5">Bölge, masaların bulunduğu alan: Salon, Teras, Bahçe, Bar… Masalar bölgenin ön ekiyle otomatik adlanır (Salon → S1, S2…). Her masaya kalıcı QR kodu verilir.</div>
      @include('masa_yonetim_bolge_form', ['formId' => 'ilk'])
    </div>
  @else
    {{-- Bölge sekmeleri --}}
    <div class="bs-serit">
      @foreach ($sekmeler as $s)
        @php $say = count($gruplar[$s['id']] ?? []); @endphp
        <button type="button" class="bs{{ $s['id'] === $seciliBolge ? ' akt' : '' }}" data-b="{{ $s['id'] }}" onclick="bolgeSec({{ $s['id'] }})">
          {{ $s['ad'] }}<span class="n">{{ $say }}</span>
        </button>
      @endforeach
      <button type="button" class="bs ekle" onclick="bolgeEkleAc()">
        <span class="material-icons-outlined" style="font-size:17px">add</span> Bölge Ekle
      </button>
    </div>

    @foreach ($sekmeler as $s)
      @php $masalar = $gruplar[$s['id']] ?? []; $kisi = array_sum(array_column($masalar, 'kapasite')); @endphp
      <div class="bp" data-bp="{{ $s['id'] }}" style="{{ $s['id'] === $seciliBolge ? '' : 'display:none' }}">
        {{-- Bölge başlığı + işlemler --}}
        <div class="bp-bas">
          <div style="min-width:0">
            <div class="bp-ad">{{ $s['ad'] }}
              @if ($s['on_ek'] !== '')<span class="onek" title="Otomatik masa adı ön eki">Ön ek: {{ $s['on_ek'] }}</span>@endif
            </div>
            <div class="bp-alt">{{ count($masalar) }} masa · {{ $kisi }} kişilik oturma</div>
          </div>
          @if ($s['id'] > 0)
            <div style="display:flex;gap:6px;flex-shrink:0">
              <button type="button" class="rk-btn bos" onclick="bolgeDuzenleAc(@js($s))">
                <span class="material-icons-outlined" style="font-size:17px">edit</span> Düzenle
              </button>
              <button type="button" class="rk-btn bos" style="color:var(--kirmizi)" onclick="bolgeSilOnay(@js($s), {{ count($masalar) }})">
                <span class="material-icons-outlined" style="font-size:17px">delete_outline</span> Sil
              </button>
            </div>
          @endif
        </div>

        {{-- Bu bölgeye masa ekle (tek / toplu) --}}
        @if ($s['id'] > 0)
          <form method="POST" action="/masa-yonetim/ekle" class="ekle-card ekle-satir">
            @csrf
            <input type="hidden" name="bolge_id" value="{{ $s['id'] }}">
            <div>
              <label class="rk-lbl">Kaç masa</label>
              <input type="number" name="adet" class="rk-inp" value="1" min="1" max="100" oninput="adetDegisti(this)">
            </div>
            <div>
              <label class="rk-lbl">Kapasite (kişi)</label>
              <input type="number" name="kapasite" class="rk-inp" value="{{ $sonKapasite }}" min="1" max="50">
            </div>
            <div class="ad-kutu">
              <label class="rk-lbl">Masa adı <span style="color:var(--sub)">(boş = otomatik)</span></label>
              <input type="text" name="ad" class="rk-inp" maxlength="40" autocomplete="off" placeholder="Otomatik: {{ $sonrakiAd[$s['id']] ?? 'sıradaki numara' }}">
            </div>
            <div>
              <button type="submit" class="rk-btn lg" style="background:var(--mor1);width:100%;justify-content:center">
                <span class="material-icons-outlined" style="font-size:18px">add</span> <span class="ekle-yazi">Masa Ekle</span>
              </button>
            </div>
          </form>
        @endif

        @if (empty($masalar))
          <div class="my-bos" style="padding:40px 20px">
            <span class="material-icons-outlined">table_restaurant</span>
            <div class="b">{{ $s['ad'] }} bölgesinde masa yok</div>
            <div class="s">Yukarıdan "Kaç masa" yazıp ekleyin — örn. 8 yazarsanız {{ $s['on_ek'] ?: 'M' }}1 – {{ $s['on_ek'] ?: 'M' }}8 tek seferde açılır.</div>
          </div>
        @else
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

                <div style="margin-top:8px;text-align:center">
                  <div style="font-size:15px;font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $m['ad'] }}</div>
                  <div style="font-size:11px;color:var(--sub)">{{ $m['kapasite'] }} kişi</div>
                  @php $sonAd = collect($m['gecmis'] ?? [])->firstWhere('alan', 'ad'); @endphp
                  @if ($sonAd)
                    <div style="font-size:10.5px;color:var(--amber);margin-top:2px" title="Ad değişikliği: {{ $sonAd['tarih'] }}">eski adı: {{ $sonAd['eski'] }}</div>
                  @endif
                </div>

                {{-- Mini buton satırı (Flutter: QR / Düzenle / Sil) --}}
                <div class="my-kart-btns" onclick="event.stopPropagation()">
                  <button type="button" class="my-icon-btn" title="QR Afişi" style="color:var(--mor1)" onclick="masaAfis(@js($m))">
                    <span class="material-icons-outlined">visibility</span>
                  </button>
                  <button type="button" class="my-icon-btn" title="QR kodunu indir (PNG)" style="color:var(--yesil)" onclick="masaIndir(@js($m), this)">
                    <span class="material-icons-outlined">download</span>
                  </button>
                  <button type="button" class="my-icon-btn" title="Düzenle / bölge değiştir" style="color:var(--mavi)" onclick="masaDuzenleAc(@js($m))">
                    <span class="material-icons-outlined">edit</span>
                  </button>
                  <button type="button" class="my-icon-btn" title="Arşivle" style="color:var(--kirmizi)"
                          onclick="masaSilOnay(@js($m['id']), @js($m['ad']))">
                    <span class="material-icons-outlined">delete_outline</span>
                  </button>
                </div>
              </div>
            @endforeach
          </div>
        @endif
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
            <div style="font-size:11.5px;color:var(--sub)">{{ $m['bolge'] ?: 'Bölgesiz' }} · {{ $m['kapasite'] }} kişi · QR korunuyor</div>
          </div>
          <button type="button" class="rk-btn bos" style="flex-shrink:0" onclick="masaDuzenleAc(@js($m))">
            <span class="material-icons-outlined" style="font-size:16px">edit</span> Düzenle
          </button>
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
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--yesil)" onclick="masaIndirTek(this)">
        <span class="material-icons-outlined" style="font-size:18px">download</span> İndir
      </button>
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
      <div style="margin-bottom:12px">
        <label class="rk-lbl" style="margin-top:0;font-size:12px">Bölge <span style="color:var(--sub)">(başka bölgeye taşı)</span></label>
        <select id="duzenleBolge" class="rk-inp">
          @foreach ($bolgeler as $b)
            <option value="{{ $b->id }}">{{ $b->ad }}</option>
          @endforeach
        </select>
      </div>
      <div style="font-size:11px;color:var(--sub);margin-bottom:12px;line-height:1.5">QR kodu değişmez. Ad ya da bölge değişirse geçmiş raporlar siparişin verildiği andaki adı gösterir ("Masa 1 → S0").</div>
      <div id="duzenleGecmis" style="display:none;margin-bottom:16px;border-top:1px solid var(--line);padding-top:10px">
        <div style="font-size:12px;font-weight:700;color:var(--ink);margin-bottom:6px">Değişim geçmişi</div>
        <div id="duzenleGecmisListe" style="max-height:160px;overflow-y:auto;display:flex;flex-direction:column;gap:6px"></div>
      </div>
      <div style="display:flex;gap:10px">
        <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="duzenleKapat()">İptal</button>
        <button type="submit" class="rk-btn" style="flex:1;justify-content:center;background:var(--mavi)">
          <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ===== BÖLGE EKLE MODALI ===== --}}
<div id="bolgeModal">
  <div class="modal-box">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div style="font-size:16px;font-weight:800;color:var(--ink)">Yeni Bölge</div>
      <button type="button" class="my-icon-btn" style="color:var(--sub)" onclick="bolgeEkleKapat()"><span class="material-icons-outlined">close</span></button>
    </div>
    @include('masa_yonetim_bolge_form', ['formId' => 'modal'])
  </div>
</div>

{{-- ===== BÖLGE DÜZENLE MODALI ===== --}}
<div id="bolgeDuzModal">
  <div class="modal-box">
    <div style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:16px">Bölgeyi Düzenle</div>
    <form method="POST" action="/isletme-duzeni/bolge-duzenle">
      @csrf
      <input type="hidden" name="id" id="bdId">
      <div style="margin-bottom:12px">
        <label class="rk-lbl" style="margin-top:0;font-size:12px">Bölge adı</label>
        <input type="text" name="ad" id="bdAd" class="rk-inp" maxlength="60" required>
      </div>
      <div style="margin-bottom:8px">
        <label class="rk-lbl" style="margin-top:0;font-size:12px">Masa ön eki</label>
        <input type="text" name="on_ek" id="bdOnEk" class="rk-inp" maxlength="6" style="text-transform:uppercase">
      </div>
      <div style="font-size:11.5px;color:var(--sub);margin-bottom:16px;line-height:1.5">Ön ek yalnız bundan sonra eklenecek masaların otomatik adını belirler; mevcut masaların adı ve QR kodu değişmez.</div>
      <div style="display:flex;gap:10px">
        <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="bolgeDuzKapat()">İptal</button>
        <button type="submit" class="rk-btn" style="flex:1;justify-content:center;background:var(--mavi)">
          <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
        </button>
      </div>
    </form>
  </div>
</div>
<form method="POST" id="bolgeSilForm" action="/isletme-duzeni/bolge-sil" style="display:none">
  @csrf
  <input type="hidden" name="id" id="bolgeSilId">
</form>

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
  var TUM_MASALAR = @js(array_values($aktif));
  var aktifMasa = null;

  // ---- Sekme geçişi (Flutter TabBar) ----
  window.sekmeGec = function(s){
    var aktif  = s === 'aktif';
    document.getElementById('panel-aktif').style.display = aktif ? '' : 'none';
    document.getElementById('panel-arsiv').style.display = aktif ? 'none' : '';
    document.getElementById('tab-aktif').classList.toggle('akt', aktif);
    document.getElementById('tab-arsiv').classList.toggle('akt', !aktif);
  };
  if (location.hash === '#arsiv') window.sekmeGec('arsiv'); // arşivdeki masa düzenlenince arşiv sekmesine dön
  var duzenleArsiv = false;

  // ---- QR URL oluştur ----
  function qrImg(url, size, kenar){
    return 'https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&margin='+(kenar||0)+'&data='+encodeURIComponent(url);
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
    duzenleArsiv = parseInt(m.arsiv) === 1;
    document.getElementById('duzenleMasaId').value   = m.id;
    document.getElementById('duzenleAd').value       = m.ad || '';
    document.getElementById('duzenleKapasite').value = m.kapasite || 4;
    // Değişim geçmişi (kim, ne zaman, eski → yeni)
    var ALAN = {olusturuldu:'Oluşturuldu', ad:'Ad', bolge:'Bölge', bolge_adi:'Bölge adı', kapasite:'Kapasite', arsiv:'Arşive alındı', geri:'Arşivden geri getirildi'};
    var g = m.gecmis || [], kutu = document.getElementById('duzenleGecmisListe');
    kutu.innerHTML = g.map(function(x){
      var ne = (x.alan === 'olusturuldu') ? esc(x.yeni || '') : (x.eski != null || x.yeni != null ? esc(x.eski || '—') + ' → <b>' + esc(x.yeni || '—') + '</b>' : '');
      return '<div style="font-size:12px;color:var(--sub);line-height:1.45"><span style="color:var(--ink);font-weight:700">' + esc(ALAN[x.alan] || x.alan) + '</span>' + (ne ? ': ' + ne : '') +
        '<br><span style="font-size:11px">' + esc(x.tarih || '') + (x.kim ? ' · ' + esc(x.kim) : '') + '</span></div>';
    }).join('');
    document.getElementById('duzenleGecmis').style.display = g.length ? '' : 'none';
    if (m.bolge_id) document.getElementById('duzenleBolge').value = String(m.bolge_id);
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
      body: JSON.stringify({ id: parseInt(id), ad: ad, kapasite: parseInt(kap), bolge_id: parseInt(document.getElementById('duzenleBolge').value) || null })
    }).then(function(r){ return r.json(); }).then(function(d){
      window.duzenleKapat();
      if(d.ok == 1){
        var hedef = '/masa-yonetim?b=' + (document.getElementById('duzenleBolge').value || '') + (duzenleArsiv ? '#arsiv' : '');
        var ayniSayfa = location.pathname + location.search === hedef.split('#')[0];
        location.href = hedef;
        if (ayniSayfa) location.reload(); // yalnız # değişince tarayıcı yenilemez
      }
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
    rkOnay(msg, function(){
      document.getElementById('silId').value = id;
      document.getElementById('silForm').submit();
    }, 'var(--kirmizi)', 'Arşivle');
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
  window.masaYazdirHepsi = function(){ yazdir(TUM_MASALAR); };

  // ---- QR indir (baskıya uygun 1000px PNG; toplu indirme bölge klasörlü ZIP) ----
  function dosyaAdi(s){ return String(s||'Masa').replace(/[\\\/:*?"<>|]+/g,'-').trim() || 'Masa'; }
  function qrBlob(m){
    return fetch(qrImg(m.qr_url,1000,40)).then(function(r){
      if(!r.ok) throw new Error('qr');
      return r.blob();
    });
  }
  function blobKaydet(blob, ad){
    var url = URL.createObjectURL(blob), a = document.createElement('a');
    a.href = url; a.download = ad;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function(){ URL.revokeObjectURL(url); }, 2000);
  }
  window.masaIndir = function(m, btn){
    if(!m || !m.qr_url){ alert('Bu masanın QR kodu henüz oluşmamış.'); return; }
    if(btn) btn.disabled = true;
    qrBlob(m).then(function(b){ blobKaydet(b, 'QR-'+dosyaAdi(m.ad)+'.png'); })
      .catch(function(){ alert('QR kodu indirilemedi. İnternet bağlantınızı kontrol edip tekrar deneyin.'); })
      .then(function(){ if(btn) btn.disabled = false; });
  };
  window.masaIndirTek = function(btn){ if(aktifMasa) window.masaIndir(aktifMasa, btn); };

  function jszipYukle(){
    if(window.JSZip) return Promise.resolve(window.JSZip);
    return new Promise(function(ok, red){
      var s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
      s.onload = function(){ ok(window.JSZip); };
      s.onerror = red;
      document.head.appendChild(s);
    });
  }
  window.masaIndirHepsi = function(){
    var liste = TUM_MASALAR.filter(function(m){ return m && m.qr_url; });
    if(!liste.length){ alert('İndirilecek QR kodu bulunamadı.'); return; }
    var btn = document.getElementById('qrZipBtn'), yazi = document.getElementById('qrZipYazi'), ilkYazi = yazi.textContent;
    var biten = 0, hatali = 0, sira = 0, kullanilan = {};
    btn.disabled = true;
    yazi.textContent = 'Hazırlanıyor...';
    jszipYukle().then(function(JSZip){
      var zip = new JSZip();
      function isci(){
        if(sira >= liste.length) return Promise.resolve();
        var m = liste[sira++];
        return qrBlob(m).then(function(b){
          // Aynı bölgede aynı adlı masa varsa dosya ezilmesin
          var yol = dosyaAdi(m.bolge||'Bölgesiz')+'/QR-'+dosyaAdi(m.ad);
          if(kullanilan[yol]) yol += '-'+m.id;
          kullanilan[yol] = 1;
          zip.file(yol+'.png', b);
        }).catch(function(){ hatali++; }).then(function(){
          biten++;
          yazi.textContent = 'İndiriliyor '+biten+'/'+liste.length;
          return isci();
        });
      }
      return Promise.all([isci(),isci(),isci(),isci()]).then(function(){
        if(hatali === liste.length) throw new Error('qr');
        return zip.generateAsync({type:'blob'});
      }).then(function(z){
        blobKaydet(z, 'QR-Kodlari-'+dosyaAdi(SUBE)+'.zip');
        if(hatali) alert(hatali+' masanın QR kodu indirilemedi, ZIP dosyasında yok. Tekrar deneyebilirsiniz.');
      });
    }).catch(function(){
      alert('QR kodları indirilemedi. İnternet bağlantınızı kontrol edip tekrar deneyin.');
    }).then(function(){
      btn.disabled = false;
      yazi.textContent = ilkYazi;
    });
  };

  // ---- Bölge sekmesi seç (sayfa yenilenmeden; adres ?b= ile hatırlanır) ----
  window.bolgeSec = function(id){
    document.querySelectorAll('#my-root .bs[data-b]').forEach(function(b){ b.classList.toggle('akt', b.dataset.b == id); });
    document.querySelectorAll('#my-root .bp').forEach(function(p){ p.style.display = p.dataset.bp == id ? '' : 'none'; });
    try { history.replaceState(null, '', '/masa-yonetim?b=' + id); } catch(e){}
  };
  // Toplu eklemede ad kutusu kapanır (adlar otomatik: T1, T2…)
  window.adetDegisti = function(inp){
    var f = inp.form, n = parseInt(inp.value) || 1, ad = f.querySelector('input[name=ad]');
    ad.disabled = n > 1; if (n > 1) ad.value = '';
    f.querySelector('.ekle-yazi').textContent = n > 1 ? n + ' Masa Ekle' : 'Masa Ekle';
  };

  // ---- Bölge oluştur: ön ek önerisi + oluşacak masaların önizlemesi ----
  var KULLANILAN_ONEK = @js(collect($bolgeler)->pluck('on_ek')->filter()->map(fn ($x) => mb_strtoupper($x))->values());
  function onEkTemiz(s){ return String(s||'').replace(/i/g,'İ').replace(/ı/g,'I').toLocaleUpperCase('tr-TR').replace(/[^\p{L}\p{N}]/gu,'').slice(0,6); }
  function onEkOner(ad){
    var t = onEkTemiz(ad) || 'M';
    for (var n = 1; n <= t.length; n++) { var a = t.slice(0, n); if (KULLANILAN_ONEK.indexOf(a) < 0) return a; }
    return t;
  }
  window.bfOnizle = function(f, adDegisti){
    var ad = f.querySelector('[name=ad]').value.trim(), oe = f.querySelector('[name=on_ek]');
    if (adDegisti && !oe.dataset.elle) oe.value = ad ? onEkOner(ad) : '';
    var on = onEkTemiz(oe.value) || (ad ? onEkOner(ad) : ''), adet = parseInt(f.querySelector('[name=adet]').value) || 0, kutu = f.querySelector('.bf-onizle');
    if (!ad) { kutu.textContent = ''; return; }
    if (KULLANILAN_ONEK.indexOf(on) >= 0) { kutu.style.color = 'var(--kirmizi)'; kutu.textContent = '"' + on + '" ön eki başka bir bölgede kullanılıyor.'; return; }
    kutu.style.color = '';
    kutu.textContent = adet > 0
      ? 'Oluşacak masalar: ' + on + '1' + (adet > 1 ? ' – ' + on + adet : '') + ' (' + adet + ' masa, her birine kalıcı QR)'
      : 'Masalar ' + on + '1, ' + on + '2… diye adlanır. Masaları sonra ekleyebilirsiniz.';
  };
  var bolgeModal = document.getElementById('bolgeModal');
  window.bolgeEkleAc = function(){ bolgeModal.style.display = 'flex'; setTimeout(function(){ bolgeModal.querySelector('[name=ad]').focus(); }, 50); };
  window.bolgeEkleKapat = function(){ bolgeModal.style.display = 'none'; };
  bolgeModal.addEventListener('click', function(e){ if(e.target === bolgeModal) window.bolgeEkleKapat(); });

  // ---- Bölge düzenle / sil ----
  var bolgeDuzModal = document.getElementById('bolgeDuzModal');
  window.bolgeDuzenleAc = function(s){
    document.getElementById('bdId').value = s.id;
    document.getElementById('bdAd').value = s.ad || '';
    document.getElementById('bdOnEk').value = s.on_ek || '';
    bolgeDuzModal.style.display = 'flex';
  };
  window.bolgeDuzKapat = function(){ bolgeDuzModal.style.display = 'none'; };
  bolgeDuzModal.addEventListener('click', function(e){ if(e.target === bolgeDuzModal) window.bolgeDuzKapat(); });
  window.bolgeSilOnay = function(s, masaSay){
    if (masaSay > 0) { alert(s.ad + ' bölgesinde ' + masaSay + ' masa var.\n\nÖnce masaları başka bölgeye taşıyın (masa kartında kalem → Bölge) ya da arşivleyin.'); return; }
    rkOnay(s.ad + ' bölgesi silinsin mi?', function(){
      document.getElementById('bolgeSilId').value = s.id;
      document.getElementById('bolgeSilForm').submit();
    }, 'var(--kirmizi)', 'Sil');
  };

  // ---- ESC kapat ----
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape'){
      if(afisModal.style.display==='flex') window.afisKapat();
      if(duzenleModal.style.display==='flex') window.duzenleKapat();
      if(bolgeModal.style.display==='flex') window.bolgeEkleKapat();
      if(bolgeDuzModal.style.display==='flex') window.bolgeDuzKapat();
    }
  });
})();
</script>
@endpush

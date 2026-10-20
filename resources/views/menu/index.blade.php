@extends('layout.app')
@section('title', 'Menü Yönetimi')
@section('rk_ikon', 'restaurant_menu')
@section('baslik', 'Menü Yönetimi')

@php
    /* ── Oturum personeli + Bearer token ── */
    $_pp  = null;
    try { if (function_exists('_posPersonel')) $_pp = _posPersonel(); } catch (\Throwable $e) {}
    $webToken       = $_pp ? (\DB::table('personeller')->where('id', $_pp->id)->value('api_token') ?: '') : '';
    $duzenleyebilir = $_pp && in_array($_pp->rol ?? '', ['sahip', 'mudur']);

    /* ── Sayım ── */
    $urunFlat   = $urunler->flatten(1);
    $urunSayi   = $urunFlat->count();
    $katSayi    = $kategoriler->count();

    /* ── Kategorisiz ürünler ── */
    $kategorisizList = collect($urunler->get(null, []))->sortBy('ad')->values();
@endphp

@section('rk_alt', $urunSayi . ' ürün · ' . $katSayi . ' kategori')

@push('head')
<style>
/* ═══ Menü Yönetimi — Flutter MenuYonetimiScreen birebir ═══ */
#mny{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;
  --kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;
  --line:#e2e8f0;--bg:#f8fafc
}
body.koyu #mny{
  --card:#1b2235;--card2:#161c2e;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;
  --line:#2c3650;--bg:#0b1020
}

/* ── Araç çubuğu ── */
#mny .mny-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px}
/* NOT: bu butonlar header'da (rk_araclar, #mny DIŞINDA) render edilir → global tanım + panel-kit
   global değişkenleri (var(--card/line/ink/sub/mor1)) ile gece/gündüz otomatik uyar. */
.mny-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 15px;border-radius:11px;
  font-size:13px;font-weight:700;cursor:pointer;border:1.5px solid transparent;line-height:1;white-space:nowrap;
  transition:transform .13s ease,box-shadow .13s ease,background .13s ease,border-color .13s ease,filter .13s ease}
.mny-btn .material-icons-outlined{font-size:18px}
.mny-btn:active{transform:translateY(0)!important}
.mny-btn.bos{background:var(--card);color:var(--ink);border-color:var(--line);box-shadow:0 1px 2px rgba(16,24,40,.07)}
.mny-btn.bos .material-icons-outlined{color:var(--sub)}
.mny-btn.bos:hover{background:var(--card2);border-color:color-mix(in srgb,var(--mor1) 32%,var(--line));transform:translateY(-1px);box-shadow:0 4px 11px rgba(16,24,40,.11)}
.mny-btn.dolu{color:#fff;background:linear-gradient(135deg,#7C3AED,#6D28D9);border-color:transparent;box-shadow:0 4px 14px rgba(124,58,237,.38)}
.mny-btn.dolu .material-icons-outlined{color:#fff}
.mny-btn.dolu:hover{filter:brightness(1.07);transform:translateY(-1px);box-shadow:0 7px 20px rgba(124,58,237,.48)}

/* ── Bölüm başlığı ── */
#mny .mny-bolum{display:flex;align-items:center;gap:8px;padding:14px 0 6px}
#mny .mny-cubuk{width:4px;height:20px;border-radius:3px;background:var(--mor1);flex-shrink:0}
#mny .mny-kat-ad{font-size:15.5px;font-weight:800;color:var(--ink)}
#mny .mny-kat-sayi{font-size:11.5px;font-weight:700;color:var(--mor1);
  background:color-mix(in srgb,var(--mor1) 14%,transparent);border-radius:20px;padding:2px 9px}
#mny .mny-kat-duz{display:inline-flex;align-items:center;gap:4px;margin-left:auto;
  padding:6px 12px;border-radius:18px;border:1.5px solid var(--mavi);background:transparent;
  color:var(--mavi);font-size:12.5px;font-weight:700;cursor:pointer}
#mny .mny-bos-kat{font-size:13px;color:var(--sub);padding:2px 0 10px 4px}

/* ── Ürün kartları (liste satırı — Flutter _urunKart) ── */
#mny .mny-grid{display:flex;flex-direction:column;gap:9px;margin-bottom:6px}
#mny .mny-kart{display:flex;align-items:center;gap:11px;background:var(--card);
  border:1px solid var(--line);border-radius:14px;padding:9px;cursor:pointer;
  transition:transform .08s,box-shadow .08s}
#mny .mny-kart:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
#mny .mny-foto{width:58px;height:58px;border-radius:10px;object-fit:cover;flex-shrink:0;background:var(--card2);
  display:flex;align-items:center;justify-content:center;overflow:hidden}
#mny .mny-foto img{width:100%;height:100%;object-fit:cover;display:block}
#mny .mny-foto .ph{font-size:24px;color:var(--sub2)}
#mny .mny-bilgi{flex:1;min-width:0}
#mny .mny-bilgi .ad{font-size:14.5px;font-weight:700;color:var(--ink);white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis}
#mny .mny-bilgi .aciklama{font-size:11.5px;color:var(--sub);margin-top:2px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#mny .mny-bilgi .fiyat-sira{display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap}
#mny .mny-bilgi .fiyat{font-size:13.5px;font-weight:700;color:var(--gold)}
#mny .mny-rozet{display:inline-flex;align-items:center;font-size:10px;font-weight:700;
  border-radius:20px;padding:2px 7px}
#mny .mny-kart-sag{display:flex;align-items:center;gap:2px;flex-shrink:0}
#mny .mny-icon-btn{display:inline-flex;align-items:center;justify-content:center;
  width:34px;height:34px;border-radius:9px;border:none;background:transparent;cursor:pointer;
  color:var(--sub);transition:background .1s}
#mny .mny-icon-btn:hover{background:color-mix(in srgb,currentColor 12%,transparent)}
#mny .mny-icon-btn .material-icons-outlined{font-size:20px}

/* ── Masaüstü 4'lü ızgara (Flutter _masaustuUrunKart) ── */
@media(min-width:900px){
  #mny .mny-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  #mny .mny-kart{flex-direction:column;align-items:stretch;padding:0;gap:0}
  #mny .mny-foto{width:100%;height:120px;border-radius:13px 13px 0 0}
  #mny .mny-foto img{border-radius:13px 13px 0 0}
  #mny .mny-kart-govde{padding:11px 11px 10px;display:flex;flex-direction:column;flex:1}
  #mny .mny-bilgi .ad{font-size:14.5px;white-space:normal;-webkit-line-clamp:1;
    display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden}
  #mny .mny-bilgi .aciklama{-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;overflow:hidden;white-space:normal}
  #mny .mny-kart-sag{display:none}
  #mny .mny-kart-sag-mb{display:none}
}

/* ── Boş durum ── */
#mny .mny-bos{display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:80px 20px;text-align:center;color:var(--sub)}
#mny .mny-bos .material-icons-outlined{font-size:54px;margin-bottom:12px}
#mny .mny-bos p{font-size:14px;line-height:1.5}

/* ── Toast/snack ── */
#mny-toast{position:fixed;bottom:32px;left:50%;transform:translateX(-50%) translateY(80px);
  background:var(--card);color:var(--ink);border:1px solid var(--line);
  border-radius:12px;padding:11px 20px;font-size:14px;font-weight:600;
  box-shadow:0 6px 22px rgba(0,0,0,.13);z-index:2000;transition:transform .25s,opacity .25s;
  opacity:0;pointer-events:none;white-space:nowrap}
#mny-toast.goster{transform:translateX(-50%) translateY(0);opacity:1}

/* ── Modal zemin ── */
.mny-modal-bg{display:none;position:fixed;inset:0;z-index:500;
  background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:16px}
.mny-modal-bg.ac{display:flex}
.mny-modal-kart{background:var(--card);border-radius:20px;width:100%;max-width:480px;
  padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.25);overflow-y:auto;max-height:90vh}
.mny-modal-kart .m-baslik{font-size:17px;font-weight:800;color:var(--ink);margin-bottom:16px}
.mny-modal-kart label{display:block;font-size:12px;font-weight:700;color:var(--sub);
  text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;margin-top:12px}
.mny-modal-kart label:first-of-type{margin-top:0}
.mny-modal-kart input,.mny-modal-kart textarea,.mny-modal-kart select{
  display:block;width:100%;padding:11px 13px;border-radius:10px;
  border:1.5px solid var(--line);background:var(--card2);color:var(--ink);
  font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;
  transition:border-color .15s}
.mny-modal-kart input:focus,.mny-modal-kart textarea:focus,.mny-modal-kart select:focus{
  border-color:var(--mor1)}
.mny-modal-kart textarea{resize:vertical;min-height:80px}
.mny-switch-row{display:flex;align-items:center;gap:10px;padding:10px 14px;
  border-radius:12px;background:var(--card2);margin-top:10px}
.mny-switch-row .sw-bilgi{flex:1}
.mny-switch-row .sw-bilgi .sw-b{font-size:14.5px;font-weight:600;color:var(--ink)}
.mny-switch-row .sw-bilgi .sw-alt{font-size:11.5px;color:var(--sub)}
.mny-switch-row input[type=checkbox]{accent-color:var(--mor1);width:20px;height:20px;cursor:pointer;flex-shrink:0}
.mny-one-kutu{border:1.5px solid var(--line);border-radius:14px;padding:14px 14px 12px;margin-top:10px}
.mny-one-kutu.akt{border-color:var(--mor1)}
.mny-ai-preview{margin-top:10px;padding:11px;border-radius:10px;
  background:color-mix(in srgb,var(--mor1) 12%,transparent);
  border:1px solid color-mix(in srgb,var(--mor1) 35%,transparent)}
.mny-ai-preview .ai-ust{font-size:11.5px;font-weight:700;color:#C4B5FD;margin-bottom:4px}
.mny-ai-preview .ai-metin{font-size:13.5px;color:var(--ink);font-style:italic;line-height:1.4}
.mny-modal-actions{display:flex;align-items:center;gap:8px;margin-top:20px;flex-wrap:wrap}
.mny-modal-actions button{flex:1;min-width:80px;padding:11px 10px;border-radius:10px;
  font-size:14px;font-weight:700;cursor:pointer;border:none;transition:opacity .12s}
.mny-modal-actions button:hover{opacity:.82}

/* ── Foto yükleme ── */
.mny-foto-alan{position:relative;width:100%;height:190px;border-radius:18px;
  border:1.5px solid var(--line);background:var(--card2);overflow:hidden;cursor:pointer;
  display:flex;align-items:center;justify-content:center;flex-direction:column;
  margin-bottom:6px;transition:border-color .15s}
.mny-foto-alan:hover{border-color:var(--mor1)}
.mny-foto-alan img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.mny-foto-alan .mny-foto-deg{position:absolute;right:10px;bottom:10px;
  background:rgba(0,0,0,.55);color:#fff;border-radius:20px;padding:6px 12px;
  font-size:12.5px;font-weight:700}
.mny-foto-alan .ph-ikon{font-size:34px;color:var(--sub)}
.mny-foto-alan .ph-text{font-size:13px;color:var(--sub);margin-top:8px}
.mny-foto-spin{position:absolute;inset:0;background:rgba(0,0,0,.35);display:flex;
  align-items:center;justify-content:center}

/* ── Sırala sayfası ── */
#mny-sirala-panel{display:none;position:fixed;inset:0;z-index:600;
  background:var(--bg);flex-direction:column;overflow:hidden}
#mny-sirala-panel.ac{display:flex}
#mny-sirala-panel .sr-header{display:flex;align-items:center;gap:10px;padding:16px;
  border-bottom:1px solid var(--line);background:var(--card)}
#mny-sirala-panel .sr-header .sr-kapat{display:inline-flex;align-items:center;
  justify-content:center;width:36px;height:36px;border-radius:10px;border:none;
  background:transparent;color:var(--sub);cursor:pointer}
#mny-sirala-panel .sr-header .sr-baslik{flex:1;font-size:16px;font-weight:800;color:var(--ink)}
#mny-sirala-panel .sr-ipucu{font-size:13px;color:var(--sub);padding:12px 16px 4px}
#mny-sirala-panel .sr-liste{flex:1;overflow-y:auto;padding:8px 16px 100px}
#mny-sirala-panel .sr-kalem{display:flex;align-items:center;gap:10px;padding:9px;
  background:var(--card);border:1px solid var(--line);border-radius:14px;
  margin-bottom:9px;cursor:grab;user-select:none}
#mny-sirala-panel .sr-kalem.sr-dragging{opacity:.5}
#mny-sirala-panel .sr-no{width:26px;height:26px;display:flex;align-items:center;
  justify-content:center;border-radius:8px;font-size:13px;font-weight:700;
  background:color-mix(in srgb,var(--mor1) 18%,transparent);color:#C4B5FD;flex-shrink:0}
#mny-sirala-panel .sr-foto{width:46px;height:46px;border-radius:9px;object-fit:cover;flex-shrink:0;
  background:var(--card2);display:flex;align-items:center;justify-content:center;overflow:hidden}
#mny-sirala-panel .sr-foto img{width:100%;height:100%;object-fit:cover}
#mny-sirala-panel .sr-bilgi{flex:1;min-width:0}
#mny-sirala-panel .sr-bilgi .sr-ad{font-size:14.5px;font-weight:700;color:var(--ink);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#mny-sirala-panel .sr-bilgi .sr-sub{font-size:11.5px;color:var(--sub);margin-top:2px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#mny-sirala-panel .sr-handle .material-icons-outlined{font-size:22px;color:var(--sub2)}
#mny-sirala-panel .sr-kaydet-btn{position:fixed;bottom:24px;right:24px;display:flex;
  align-items:center;gap:8px;background:var(--mor1);color:#fff;border:none;border-radius:50px;
  padding:14px 22px;font-size:15px;font-weight:700;cursor:pointer;
  box-shadow:0 6px 20px rgba(124,58,237,.45);z-index:10}

.mny-loader{display:none;position:fixed;inset:0;z-index:999;background:rgba(15,23,42,.35);
  align-items:center;justify-content:center}
.mny-loader.ac{display:flex}
.mny-loader-box{background:var(--card);border-radius:16px;padding:28px 36px;text-align:center;font-size:14px;color:var(--sub)}
.mny-loader-box .spin{width:32px;height:32px;border:3px solid var(--line);border-top-color:var(--mor1);
  border-radius:50%;animation:mny-spin .7s linear infinite;margin:0 auto 12px}
@keyframes mny-spin{to{transform:rotate(360deg)}}
</style>
@endpush

@section('rk_araclar')
@if($duzenleyebilir)
<button class="mny-btn bos" onclick="mnyOneSiralaAc()">
  <span class="material-icons-outlined" style="color:var(--gold)">star</span> Öne Çıkanları Sırala
</button>
<button class="mny-btn bos" onclick="mnyKategoriAc(null)">
  <span class="material-icons-outlined">create_new_folder</span> Kategori
</button>
<button class="mny-btn dolu" onclick="mnyUrunAc(null)">
  <span class="material-icons-outlined">add</span> Yeni Ürün
</button>
@endif
@endsection

@section('content')
<div id="mny">

{{-- ────────────────────────────────────────────
     VERİ BLOK — Blade → JS
─────────────────────────────────────────────── --}}
<script>
const MNY_TOKEN   = '{{ $webToken }}';
const MNY_DUZENLE = {{ $duzenleyebilir ? 'true' : 'false' }};

/* Blade'den PHP → JS veri akışı */
let mnyKategoriler = {!! json_encode($kategoriler->values()->map(fn($k)=>[
    'id'   => (int)$k->id,
    'ad'   => $k->ad,
    'sira' => (int)($k->sira ?? 0),
    'aktif'=> (bool)($k->aktif ?? true),
])->all(), 15, 512) !!};

let mnyUrunler = {!! json_encode($urunFlat->map(fn($u)=>[
    'id'         => (int)$u->id,
    'ad'         => $u->ad,
    'aciklama'   => $u->aciklama ?? '',
    'fiyat'      => (float)$u->fiyat,
    'kategori_id'=> $u->kategori_id ? (int)$u->kategori_id : 0,
    'tukendi'    => (bool)$u->tukendi,
    'aktif'      => isset($u->aktif) ? (bool)$u->aktif : true,
    'gorsel'     => $u->gorsel ?? null,
    'one_cikan'  => isset($u->one_cikan) ? (bool)$u->one_cikan : false,
    'one_soz'    => $u->one_soz ?? '',
    'one_ai_soz' => $u->one_ai_soz ?? '',
    'one_sira'   => (int)($u->one_sira ?? 0),
])->values()->all(), 15, 512) !!};
</script>

{{-- ── Boş durum ── --}}
@if($kategoriler->isEmpty() && $urunFlat->isEmpty())
<div class="mny-bos">
  <span class="material-icons-outlined">restaurant_menu</span>
  <p>Henüz menü yok.<br>Önce kategori, sonra ürün ekleyin.</p>
</div>
@endif

{{-- ── İçerik kapsayıcı (JS ile yeniden çizilir) ── --}}
<div id="mny-icerik"></div>

{{-- ════════════════ KATEGORİ MODAL ════════════════ --}}
<div class="mny-modal-bg" id="katModal">
  <div class="mny-modal-kart" style="max-width:380px">
    <div class="m-baslik" id="katModalBaslik">Kategori Düzenle</div>
    <input type="hidden" id="katId">
    <label>Kategori adı</label>
    <input type="text" id="katAd" placeholder="Örn: Başlangıçlar">
    <label>Sıra (küçük önce)</label>
    <input type="number" id="katSira" value="0" min="0">
    <div class="mny-modal-actions">
      <button id="katSilBtn" style="background:color-mix(in srgb,var(--kirmizi) 14%,transparent);color:var(--kirmizi)" onclick="katSil()">Sil</button>
      <button style="background:var(--card2);color:var(--sub)" onclick="mnyModalKapat('katModal')">Vazgeç</button>
      <button style="background:var(--mor1);color:#fff" onclick="katKaydet()">Kaydet</button>
    </div>
  </div>
</div>

{{-- ════════════════ ÜRÜN MODAL ════════════════ --}}
<div class="mny-modal-bg" id="urunModal">
  <div class="mny-modal-kart">
    <div class="m-baslik" id="urunModalBaslik">Yeni Ürün</div>

    {{-- Fotoğraf --}}
    <div class="mny-foto-alan" id="fotoAlan" onclick="mnyFotoSec()">
      <span class="material-icons-outlined ph-ikon">add_a_photo_outlined</span>
      <span class="ph-text">Fotoğraf ekle</span>
    </div>
    <input type="file" id="fotoInput" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="mnyFotoYukle(this)">
    <p id="fotoIpucu" style="font-size:12px;color:var(--sub);text-align:center;margin:0 0 6px;display:none">
      İpucu: Fotoğraf eklemek için karta dokunun — ürün otomatik kaydedilip fotoğraf yüklenir.
    </p>

    <input type="hidden" id="urunId">
    <label>Ürün adı</label>
    <input type="text" id="urunAd" placeholder="Örn: Karışık Izgara">
    <label>Fiyat (TL)</label>
    <input type="number" id="urunFiyat" placeholder="0" min="0" step="any">
    <label>Kategori</label>
    <select id="urunKatSel">
      <option value="0">Kategorisiz</option>
    </select>
    <label>Açıklama (müşteriye tanıtım)</label>
    <textarea id="urunAciklama" placeholder="Kısa ve iştah açıcı..."></textarea>

    {{-- Tükendi / Aktif --}}
    <div class="mny-switch-row">
      <div class="sw-bilgi">
        <div class="sw-b">Tükendi (86)</div>
        <div class="sw-alt">Geçici olarak yok — menüde "tükendi" görünür</div>
      </div>
      <input type="checkbox" id="urunTukendi">
    </div>
    <div class="mny-switch-row">
      <div class="sw-bilgi">
        <div class="sw-b">Aktif</div>
        <div class="sw-alt">Kapalıysa menüde hiç görünmez</div>
      </div>
      <input type="checkbox" id="urunAktif" checked>
    </div>

    {{-- Öne Çıkar bölümü --}}
    <div class="mny-one-kutu" id="oneKutu">
      <div style="display:flex;align-items:flex-start;gap:10px">
        <div style="flex:1">
          <div style="font-size:14.5px;font-weight:600;color:var(--ink)">⭐ Öne Çıkar / Günün Önerisi</div>
          <div style="font-size:11.5px;color:var(--sub);margin-top:2px">Müşteri "ne önerirsin / içecek / günün yemeği" deyince asistan bunu önce anlatır</div>
        </div>
        <input type="checkbox" id="urunOneCikan" style="accent-color:var(--mor1);width:20px;height:20px;flex-shrink:0;margin-top:4px" onchange="mnyOneGoster()">
      </div>
      <div id="oneBolum" style="display:none;margin-top:10px">
        <label>Ürün notların — malzeme, pişirme, özellik (kısa yaz)</label>
        <textarea id="urunOneSoz" placeholder='Örn: "mangal köfte, yanında pilav ve közlenmiş biber, acı sos"' style="min-height:70px"></textarea>
        <p style="font-size:11.5px;color:var(--sub);line-height:1.4;margin:6px 0 0">
          Sen sadece notları yaz, süslemene gerek yok — yapay zekâ bunu iştah kabartıcı bir cümleye çevirir.
        </p>
        <div id="oneAiPreview" class="mny-ai-preview" style="display:none">
          <div class="ai-ust">✨ Yapay zekâ müşteriye şöyle anlatacak:</div>
          <div class="ai-metin" id="oneAiMetin"></div>
        </div>
        <p style="font-size:11px;color:var(--sub);margin-top:8px">
          Sırayı elle girmene gerek yok — üstteki ⭐ "Öne Çıkanları Sırala" ile sürükleyerek düzenlersin.
        </p>
      </div>
    </div>

    <div class="mny-modal-actions" style="margin-top:20px">
      <button id="urunSilBtn" style="background:color-mix(in srgb,var(--kirmizi) 14%,transparent);color:var(--kirmizi)" onclick="urunSilOnay()">Sil</button>
      <button style="background:var(--card2);color:var(--sub)" onclick="mnyModalKapat('urunModal')">Vazgeç</button>
      <button id="urunKaydetBtn" style="background:var(--mor1);color:#fff" onclick="urunKaydet(true)">Kaydet</button>
    </div>
    <div id="barkodBtn" style="display:none;margin-top:8px">
      <a id="barkodLink" href="#" style="display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:12px;border:1.5px solid var(--mor1);border-radius:12px;color:var(--mor1);font-size:14px;font-weight:700;text-decoration:none">
        <span class="material-icons-outlined" style="font-size:20px">qr_code_2</span> Barkodları Yönet
      </a>
    </div>
  </div>
</div>

{{-- ════════════════ ÖNE ÇIKAR SIRALA PANELİ ════════════════ --}}
<div id="mny-sirala-panel">
  <div class="sr-header">
    <button class="sr-kapat" onclick="mnySiralaKapat()">
      <span class="material-icons-outlined">arrow_back</span>
    </button>
    <div class="sr-baslik">Öne Çıkanları Sırala</div>
  </div>
  <div class="sr-ipucu">Sürükleyerek sırala — En üstteki, müşteriye ilk önerilir.</div>
  <div class="sr-liste" id="srListe"></div>
  <button class="sr-kaydet-btn" onclick="mnySiraKaydet()">
    <span class="material-icons-outlined">check</span> Kaydet
  </button>
</div>

{{-- ════════════════ LOADER + TOAST ════════════════ --}}
<div class="mny-loader" id="mnyLoader">
  <div class="mny-loader-box">
    <div class="spin"></div>
    Kaydediliyor…
  </div>
</div>
<div id="mny-toast"></div>

</div>{{-- #mny --}}

@push('scripts')
<script>
/* ═══════════════════════════════════════════════════
   Menü Yönetimi — Tek dosya JS
   Flutter MenuYonetimiScreen birebir web karşılığı
═══════════════════════════════════════════════════ */
'use strict';

/* ── Yardımcılar ── */
function mnyTl(v) {
    const n = (typeof v === 'number' ? v : (parseFloat(v) || 0));
    return new Intl.NumberFormat('tr-TR').format(Math.round(n)) + ' TL';
}
function mnyToast(m) {
    const t = document.getElementById('mny-toast');
    t.textContent = m; t.classList.add('goster');
    setTimeout(() => t.classList.remove('goster'), 2800);
}
function mnyModalAc(id)   { document.getElementById(id).classList.add('ac'); }
function mnyModalKapat(id){ document.getElementById(id).classList.remove('ac'); }
function mnyLoading(b)    { document.getElementById('mnyLoader').classList.toggle('ac', b); }

/* ── API çağrısı (Bearer token) ── */
async function mnyApi(url, data = null) {
    const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.csrf };
    let opt = { headers };
    if (data && data instanceof FormData) {
        headers['Authorization'] = 'Bearer ' + MNY_TOKEN;
        opt.method = 'POST'; opt.body = data;
    } else if (data) {
        headers['Authorization'] = 'Bearer ' + MNY_TOKEN;
        headers['Content-Type'] = 'application/json';
        opt.method = 'POST'; opt.body = JSON.stringify(data);
    } else {
        if (MNY_TOKEN) headers['Authorization'] = 'Bearer ' + MNY_TOKEN;
    }
    const r = await fetch(url, opt);
    return r.json();
}

/* ── Çizim ── */
function mnyKatUrunleri(katId) {
    return mnyUrunler.filter(u => (u.kategori_id || 0) === katId)
                     .sort((a, b) => a.ad.localeCompare(b.ad, 'tr'));
}
function mnyRozetHtml(u) {
    let h = '';
    if (u.one_cikan) h += `<span class="mny-rozet" style="background:rgba(124,58,237,.18);color:#C4B5FD">⭐ Öne çıkan</span>`;
    if (u.tukendi)   h += `<span class="mny-rozet" style="background:rgba(244,63,94,.18);color:var(--kirmizi);margin-left:4px">Tükendi</span>`;
    if (!u.aktif)    h += `<span class="mny-rozet" style="background:rgba(100,116,139,.18);color:var(--sub);margin-left:4px">Pasif</span>`;
    return h;
}
function mnyFotoHtml(gorsel, cls='mny-foto') {
    if (gorsel) return `<div class="${cls}"><img src="${gorsel}" onerror="this.parentElement.innerHTML='<span class=\\'material-icons-outlined ph\\'>image_outlined</span>'" loading="lazy"></div>`;
    return `<div class="${cls}"><span class="material-icons-outlined ph" style="font-size:24px;color:var(--sub2)">image_outlined</span></div>`;
}

function mnyRenderIcerik() {
    const wrap = document.getElementById('mny-icerik');
    if (!wrap) return;
    if (mnyKategoriler.length === 0 && mnyUrunler.length === 0) { wrap.innerHTML = ''; return; }

    let html = '';

    // Kategorili bölümler
    for (const k of mnyKategoriler) {
        const list = mnyKatUrunleri(k.id);
        html += mnyBolumHtml(k, list);
    }

    // Kategorisiz
    const siz = mnyUrunler.filter(u => (u.kategori_id || 0) === 0)
                           .sort((a,b)=>a.ad.localeCompare(b.ad,'tr'));
    if (siz.length > 0) html += mnyBolumHtml(null, siz);

    wrap.innerHTML = html;
}

function mnyBolumHtml(kat, list) {
    const ad    = kat ? kat.ad : 'Kategorisiz';
    const duzBtn = MNY_DUZENLE && kat
        ? `<button class="mny-kat-duz" onclick="event.stopPropagation();mnyKategoriAc(${JSON.stringify(kat)})">
             <span class="material-icons-outlined" style="font-size:16px">edit_outlined</span> Düzenle
           </button>`
        : '';
    const bosMsg = list.length === 0
        ? `<p class="mny-bos-kat">Bu kategoride ürün yok</p>`
        : '';
    const kartlar = list.map(u => mnyKartHtml(u)).join('');
    return `
<div class="mny-bolum">
  <div class="mny-cubuk"></div>
  <span class="mny-kat-ad">${escHtml(ad)}</span>
  <span class="mny-kat-sayi">${list.length}</span>
  ${duzBtn}
</div>
${bosMsg}
<div class="mny-grid">${kartlar}</div>`;
}

function mnyKartHtml(u) {
    const gorsel = u.gorsel || '';
    const fotoDiv = gorsel
        ? `<div class="mny-foto"><img src="${escHtml(gorsel)}" loading="lazy" onerror="this.parentElement.innerHTML='<span class=\\'material-icons-outlined\\' style=\\'font-size:24px;color:var(--sub2)\\'>image_outlined</span>'"></div>`
        : `<div class="mny-foto"><span class="material-icons-outlined" style="font-size:24px;color:var(--sub2)">image_outlined</span></div>`;
    const aciklamaHtml = u.aciklama
        ? `<div class="aciklama">${escHtml(u.aciklama)}</div>` : '';
    const barkodBtn = MNY_DUZENLE
        ? `<a href="/barkod?tur=urun&hedef_id=${u.id}" class="mny-icon-btn" title="Barkodlar" onclick="event.stopPropagation()">
             <span class="material-icons-outlined" style="color:var(--mor1)">qr_code_2</span>
           </a>` : '';
    const onTap = MNY_DUZENLE ? `onclick="mnyUrunAc(${u.id})"` : '';
    return `
<div class="mny-kart" ${onTap}>
  ${fotoDiv}
  <div class="mny-bilgi mny-kart-govde">
    <div class="ad">${escHtml(u.ad)}</div>
    ${aciklamaHtml}
    <div class="fiyat-sira">
      <span class="fiyat">${mnyTl(u.fiyat)}</span>
      ${mnyRozetHtml(u)}
    </div>
  </div>
  <div class="mny-kart-sag">
    ${barkodBtn}
    <span class="material-icons-outlined" style="color:var(--sub2);font-size:20px">chevron_right</span>
  </div>
</div>`;
}

function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Kategori modal ── */
function mnyKategoriAc(kat) {
    if (!MNY_DUZENLE) return;
    document.getElementById('katModalBaslik').textContent = kat ? 'Kategori Düzenle' : 'Yeni Kategori';
    document.getElementById('katId').value    = kat ? kat.id : '';
    document.getElementById('katAd').value    = kat ? kat.ad : '';
    document.getElementById('katSira').value  = kat ? kat.sira : mnyKategoriler.length;
    document.getElementById('katSilBtn').style.display = kat ? '' : 'none';
    mnyModalAc('katModal');
    setTimeout(() => document.getElementById('katAd').focus(), 80);
}

async function katKaydet() {
    const ad = document.getElementById('katAd').value.trim();
    if (!ad) { mnyToast('Kategori adı gerekli'); return; }
    const id   = parseInt(document.getElementById('katId').value) || 0;
    const sira = parseInt(document.getElementById('katSira').value) || 0;
    mnyLoading(true);
    try {
        const r = await mnyApi('/api/patron/kategori-kaydet', { id: id || undefined, ad, sira });
        if (r.ok === 1) {
            if (id) { const i = mnyKategoriler.findIndex(k=>k.id===id); if(i>=0) mnyKategoriler[i]={...mnyKategoriler[i],...r.kategori}; }
            else    { mnyKategoriler.push(r.kategori); mnyKategoriler.sort((a,b)=>a.sira-b.sira||a.ad.localeCompare(b.ad,'tr')); }
            mnyModalKapat('katModal'); mnyRenderIcerik(); mnyToast('Kategori kaydedildi');
        } else { mnyToast(r.hata || 'Kaydedilemedi'); }
    } catch(e){ mnyToast('Bağlantı hatası'); }
    mnyLoading(false);
}

async function katSil() {
    const id = parseInt(document.getElementById('katId').value);
    if (!id) return;
    if (!confirm('Bu kategoriyi silmek istiyor musunuz?')) return;
    mnyLoading(true);
    try {
        const r = await mnyApi('/api/patron/kategori-sil', { id });
        if (r.ok === 1) {
            mnyKategoriler = mnyKategoriler.filter(k=>k.id!==id);
            mnyModalKapat('katModal'); mnyRenderIcerik(); mnyToast('Kategori silindi');
        } else { mnyToast(r.hata || 'Silinemedi'); }
    } catch(e){ mnyToast('Bağlantı hatası'); }
    mnyLoading(false);
}

/* ── Ürün modal ── */
let mnyAktifUrunId = null;
let mnyAktifGorsel = null;

function mnyUrunAc(urunIdOrNull) {
    if (!MNY_DUZENLE) return;
    const u = urunIdOrNull ? mnyUrunler.find(x=>x.id===urunIdOrNull) : null;
    mnyAktifUrunId = u ? u.id : null;
    mnyAktifGorsel = u ? (u.gorsel || null) : null;

    document.getElementById('urunModalBaslik').textContent = u ? 'Ürünü Düzenle' : 'Yeni Ürün';
    document.getElementById('urunId').value        = u ? u.id : '';
    document.getElementById('urunAd').value        = u ? u.ad : '';
    document.getElementById('urunFiyat').value     = u ? u.fiyat : '';
    document.getElementById('urunAciklama').value  = u ? (u.aciklama || '') : '';
    document.getElementById('urunTukendi').checked = u ? u.tukendi : false;
    document.getElementById('urunAktif').checked   = u ? u.aktif : true;
    document.getElementById('urunOneCikan').checked= u ? u.one_cikan : false;
    document.getElementById('urunOneSoz').value    = u ? (u.one_soz || '') : '';

    // AI önizleme
    const aiPrev   = document.getElementById('oneAiPreview');
    const aiMetin  = document.getElementById('oneAiMetin');
    if (u && u.one_ai_soz) {
        aiMetin.textContent = '"' + u.one_ai_soz + '"';
        aiPrev.style.display = '';
    } else { aiPrev.style.display = 'none'; }

    mnyOneGoster();
    mnyFotoGoster(mnyAktifGorsel);

    // Sil butonu
    document.getElementById('urunSilBtn').style.display = u ? '' : 'none';
    // Barkod linki
    const bbtn = document.getElementById('barkodBtn');
    const blink= document.getElementById('barkodLink');
    if (u) { bbtn.style.display=''; blink.href='/barkod?tur=urun&hedef_id='+u.id; } else { bbtn.style.display='none'; }
    // İpucu (yeni)
    document.getElementById('fotoIpucu').style.display = u ? 'none' : '';

    // Kategori select doldur
    const sel = document.getElementById('urunKatSel');
    sel.innerHTML = '<option value="0">Kategorisiz</option>' +
        mnyKategoriler.map(k=>`<option value="${k.id}"${u && u.kategori_id===k.id?' selected':''}>${escHtml(k.ad)}</option>`).join('');
    if (!u) sel.value = '0';

    mnyModalAc('urunModal');
    setTimeout(()=>document.getElementById('urunAd').focus(), 80);
}

function mnyOneGoster() {
    const oc = document.getElementById('urunOneCikan').checked;
    document.getElementById('oneBolum').style.display = oc ? '' : 'none';
    document.getElementById('oneKutu').classList.toggle('akt', oc);
}

function mnyFotoGoster(gorsel) {
    const alan = document.getElementById('fotoAlan');
    if (gorsel) {
        alan.innerHTML = `<img src="${escHtml(gorsel)}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
            <span class="mny-foto-deg">📷 Değiştir</span>`;
    } else {
        alan.innerHTML = `<span class="material-icons-outlined ph-ikon">add_a_photo_outlined</span>
            <span class="ph-text">Fotoğraf ekle</span>`;
    }
}

function mnyFotoSec() {
    document.getElementById('fotoInput').click();
}

async function mnyFotoYukle(input) {
    if (!input.files || !input.files[0]) return;
    // Yeni ürünse önce kaydet
    if (!mnyAktifUrunId) {
        const ok = await urunKaydet(false);
        if (!ok) return;
    }
    const alan = document.getElementById('fotoAlan');
    alan.innerHTML = `<div class="mny-foto-spin"><div style="width:32px;height:32px;border:3px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:mny-spin .7s linear infinite"></div></div>`;
    const fd = new FormData();
    fd.append('urun_id', mnyAktifUrunId);
    fd.append('foto', input.files[0]);
    try {
        const r = await mnyApi('/api/patron/urun-foto', fd);
        if (r.ok === 1) {
            mnyAktifGorsel = r.url;
            // Güncelle lokal veriye
            const idx = mnyUrunler.findIndex(x=>x.id===mnyAktifUrunId);
            if (idx>=0) mnyUrunler[idx].gorsel = r.url;
            mnyFotoGoster(r.url);
            mnyRenderIcerik();
            mnyToast('Fotoğraf yüklendi');
        } else { mnyFotoGoster(mnyAktifGorsel); mnyToast(r.hata || 'Yüklenemedi'); }
    } catch(e) { mnyFotoGoster(mnyAktifGorsel); mnyToast('Fotoğraf yüklenemedi'); }
    input.value = '';
}

async function urunKaydet(kapat = true) {
    const ad = document.getElementById('urunAd').value.trim();
    if (!ad) { mnyToast('Ürün adı gerekli'); return false; }
    const id       = parseInt(document.getElementById('urunId').value) || 0;
    const fiyat    = parseFloat(document.getElementById('urunFiyat').value.replace(',','.')) || 0;
    const katId    = parseInt(document.getElementById('urunKatSel').value) || 0;
    const aciklama = document.getElementById('urunAciklama').value.trim();
    const tukendi  = document.getElementById('urunTukendi').checked;
    const aktif    = document.getElementById('urunAktif').checked;
    const oneCikan = document.getElementById('urunOneCikan').checked;
    const oneSoz   = document.getElementById('urunOneSoz').value.trim();

    const btn = document.getElementById('urunKaydetBtn');
    btn.disabled = true; btn.textContent = '…';
    try {
        const r = await mnyApi('/api/patron/urun-kaydet', {
            id: id || undefined, ad, fiyat, aciklama, kategori_id: katId,
            tukendi, aktif, one_cikan: oneCikan, one_soz: oneSoz
        });
        btn.disabled = false; btn.textContent = 'Kaydet';
        if (r.ok === 1) {
            const u = r.urun;
            mnyAktifUrunId = u.id;
            document.getElementById('urunId').value = u.id;
            // AI önizleme güncelle
            if (u.one_ai_soz) {
                document.getElementById('oneAiMetin').textContent = '"' + u.one_ai_soz + '"';
                document.getElementById('oneAiPreview').style.display = '';
            }
            // Barkod linki güncelle
            document.getElementById('barkodBtn').style.display = '';
            document.getElementById('barkodLink').href = '/barkod?tur=urun&hedef_id=' + u.id;
            document.getElementById('fotoIpucu').style.display = 'none';
            document.getElementById('urunSilBtn').style.display = '';
            // Lokal veriyi güncelle
            const idx = mnyUrunler.findIndex(x=>x.id===u.id);
            if (idx>=0) mnyUrunler[idx] = {...mnyUrunler[idx], ...u};
            else        mnyUrunler.push(u);
            mnyRenderIcerik();
            if (kapat) { mnyModalKapat('urunModal'); mnyToast('Ürün kaydedildi'); }
            else { mnyToast('Ürün kaydedildi'); }
            return true;
        } else { mnyToast(r.hata || 'Kaydedilemedi'); return false; }
    } catch(e) { btn.disabled=false; btn.textContent='Kaydet'; mnyToast('Bağlantı hatası'); return false; }
}

async function urunSilOnay() {
    const id = mnyAktifUrunId;
    if (!id) return;
    const ad = document.getElementById('urunAd').value.trim();
    if (!confirm(`"${ad}" menüden kalkacak. Silmek istiyor musunuz?`)) return;
    mnyLoading(true);
    try {
        const r = await mnyApi('/api/patron/urun-sil', { id });
        if (r.ok === 1) {
            mnyUrunler = mnyUrunler.filter(u=>u.id!==id);
            mnyModalKapat('urunModal'); mnyRenderIcerik(); mnyToast('Ürün silindi');
        } else { mnyToast(r.hata || 'Silinemedi'); }
    } catch(e){ mnyToast('Bağlantı hatası'); }
    mnyLoading(false);
}

/* ── Öne Çıkanları Sırala ── */
let srList = [];
let srDragIdx = null;

function mnyOneSiralaAc() {
    srList = mnyUrunler.filter(u=>u.one_cikan).sort((a,b)=>(a.one_sira||0)-(b.one_sira||0));
    if (srList.length === 0) {
        mnyToast('Henüz öne çıkan ürün yok. Bir ürünü açıp ⭐ Öne Çıkar\'ı açın.');
        return;
    }
    mnySiralaRender();
    document.getElementById('mny-sirala-panel').classList.add('ac');
}

function mnySiralaKapat() {
    document.getElementById('mny-sirala-panel').classList.remove('ac');
}

function mnySiralaRender() {
    const ul = document.getElementById('srListe');
    ul.innerHTML = srList.map((u,i) => {
        const gorsel = u.gorsel
            ? `<div class="sr-foto"><img src="${escHtml(u.gorsel)}" loading="lazy" onerror="this.parentElement.innerHTML='<span class=\\'material-icons-outlined\\' style=\\'font-size:20px;color:var(--sub2)\\'>image_outlined</span>'"></div>`
            : `<div class="sr-foto"><span class="material-icons-outlined" style="font-size:20px;color:var(--sub2)">image_outlined</span></div>`;
        const sub = u.one_ai_soz || u.one_soz || '';
        return `<div class="sr-kalem" draggable="true"
                    ondragstart="srDragStart(event,${i})"
                    ondragover="srDragOver(event,${i})"
                    ondrop="srDrop(event,${i})"
                    ondragend="srDragEnd(event)">
            <div class="sr-no">${i+1}</div>
            ${gorsel}
            <div class="sr-bilgi">
              <div class="sr-ad">${escHtml(u.ad)}</div>
              ${sub ? `<div class="sr-sub">${escHtml(sub)}</div>` : ''}
            </div>
            <span class="sr-handle"><span class="material-icons-outlined">drag_handle</span></span>
          </div>`;
    }).join('');
}

function srDragStart(e,i){ srDragIdx=i; e.currentTarget.classList.add('sr-dragging'); }
function srDragOver(e,i){ e.preventDefault(); }
function srDrop(e,i){
    e.preventDefault();
    if(srDragIdx===null||srDragIdx===i) return;
    const it=srList.splice(srDragIdx,1)[0];
    srList.splice(i,0,it);
    srDragIdx=null;
    mnySiralaRender();
}
function srDragEnd(e){ e.currentTarget.classList.remove('sr-dragging'); srDragIdx=null; }

async function mnySiraKaydet() {
    const ids = srList.map(u=>u.id);
    mnyLoading(true);
    try {
        const r = await mnyApi('/api/patron/one-sira-kaydet', { ids: JSON.stringify(ids) });
        if (r.ok === 1) {
            // one_sira güncelle
            ids.forEach((id,i) => {
                const idx = mnyUrunler.findIndex(u=>u.id===id);
                if(idx>=0){ mnyUrunler[idx].one_sira=i+1; mnyUrunler[idx].one_cikan=true; }
            });
            mnySiralaKapat(); mnyRenderIcerik(); mnyToast('Sıra kaydedildi');
        } else { mnyToast(r.hata || 'Kaydedilemedi'); }
    } catch(e){ mnyToast('Bağlantı hatası'); }
    mnyLoading(false);
}

/* ── Başlangıç çizimi ── */
mnyRenderIcerik();

/* ── Modal kapatma (dışına tıklama) ── */
['katModal','urunModal'].forEach(id=>{
    document.getElementById(id).addEventListener('click',function(e){
        if(e.target===this) mnyModalKapat(id);
    });
});
</script>
@endpush
@endsection

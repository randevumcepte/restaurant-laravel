@extends('layout.app')
@section('title', 'Reçeteler')
@section('rk_ikon', 'menu_book')
@section('baslik', 'Reçeteler')

@php
    // $urunler, $malzemeler, $yarimamuller, $birimler → route'dan gelir
    // her urun: id, ad, fiyat, maliyet, food_cost, receteli (bool)
    $recetesizSayi = collect($urunler)->filter(fn($u) => !($u->receteli ?? false))->count();
    $receteliler   = collect($urunler)->filter(fn($u) =>  ($u->receteli ?? false));
    $recetesizler  = collect($urunler)->filter(fn($u) => !($u->receteli ?? false));

    // Birim haritası: id => kisaltma
    $birimMap = [];
    foreach ($birimler as $b) $birimMap[(int)$b->id] = $b->kisaltma;

    // Food-cost renk sınıfı (Flutter _fcRenk: >=38 kırmızı, >=30 amber, else yeşil)
    $fcRenk = function ($fc) {
        $fc = (float)$fc;
        if ($fc >= 38) return '#F43F5E';
        if ($fc >= 30) return '#F59E0B';
        return '#10B981';
    };

    // Flutter _tl: tam sayı formatı
    $tl  = fn($v) => number_format((float)$v, 0, ',', '.') . 'TL';
    // Flutter _mik: ondalıksız tam sayı, değilse 2 hane
    $mik = function($v) {
        $v = (float)$v;
        return $v == round($v) ? number_format((int)$v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    };

    // Duzenleyebilir mi?
    $_pp2 = null;
    try { if (function_exists('_posPersonel')) $_pp2 = _posPersonel(); } catch (\Throwable $e) {}
    $duzenleyebilir = $_pp2 && in_array($_pp2->rol ?? '', ['sahip']);
@endphp

@push('head')
<style>
/* ===== REÇETELER — Flutter ReceteScreen birebir eşdeğeri ===== */
#rct {
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
}
body.koyu #rct{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#0B1020}

/* ---- Uyarı bandı (Flutter recetesiz > 0 sarı uyarı) ---- */
#rct .rct-uyari{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:12px;
  background:rgba(245,158,11,.10);border:1px solid rgba(245,158,11,.35);margin-bottom:14px}
#rct .rct-uyari .material-icons-outlined{color:var(--amber);font-size:20px;flex-shrink:0}
#rct .rct-uyari p{color:var(--amber);font-size:13px;font-weight:600;margin:0}

/* ---- Arama alanı ---- */
#rct .rct-ara-wrap{position:relative;margin-bottom:14px}
#rct .rct-ara-wrap .material-icons-outlined{position:absolute;left:10px;top:50%;transform:translateY(-50%);
  font-size:18px;color:var(--sub);pointer-events:none}
#rct #rctAra{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s}
#rct #rctAra:focus{border-color:var(--mor1)}
body.koyu #rct #rctAra{background:var(--card2)}

/* ---- Ürün kartı (Flutter _kart) ---- */
#rct .rct-kart{display:flex;align-items:center;gap:10px;padding:13px;border-radius:14px;
  background:var(--card);border:1px solid var(--line);margin-bottom:8px;cursor:pointer;
  transition:box-shadow .1s}
#rct .rct-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.08)}
#rct .rct-kart .sol{flex:1;min-width:0}
#rct .rct-kart .ad{color:var(--ink);font-size:15px;font-weight:700;display:flex;align-items:center;
  gap:6px;flex-wrap:wrap;margin-bottom:3px}
#rct .rct-kart .alt{color:var(--sub);font-size:12px}
#rct .rct-kart .rozet-rs{display:inline-block;padding:1px 7px;border-radius:6px;font-size:10px;
  font-weight:700;background:rgba(245,158,11,.16);color:var(--amber)}
#rct .rct-kart .fc-badge{padding:6px 10px;border-radius:10px;font-size:15px;font-weight:700;flex-shrink:0}
#rct .rct-kart .material-icons-outlined.cev{color:var(--sub);font-size:20px;flex-shrink:0;margin-left:2px}

/* ---- Masaüstü araç çubuğu ---- */
#rctAraBar{display:flex;align-items:center;gap:10px}
#rctAraBar .ara-ic{position:relative}
#rctAraBar .ara-ic .material-icons-outlined{position:absolute;left:8px;top:50%;transform:translateY(-50%);
  font-size:16px;color:var(--sub,#64748b);pointer-events:none}
#rctAraBar input{padding:8px 12px 8px 32px;border:1px solid var(--line,#e2e8f0);border-radius:10px;
  background:var(--card2,#f1f5f9);color:var(--ink,#1e293b);font-size:13px;outline:none;
  width:220px;transition:border-color .15s;box-sizing:border-box}
#rctAraBar input:focus{border-color:var(--mor1,#7C3AED)}

/* ---- Masaüstü ızgara kartı (Flutter _masaustuKart / MKart) ---- */
#rct .rct-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:18px}
#rct .rct-mkart{border-radius:14px;background:var(--card);border:1px solid var(--line);
  padding:14px;cursor:pointer;transition:box-shadow .1s;display:flex;flex-direction:column;
  justify-content:space-between;min-height:116px}
#rct .rct-mkart:hover{box-shadow:0 4px 14px rgba(0,0,0,.09)}
#rct .rct-mkart .mk-ust{display:flex;align-items:flex-start;gap:6px;margin-bottom:8px}
#rct .rct-mkart .mk-ad{color:var(--ink);font-size:15px;font-weight:700;flex:1;min-width:0;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
#rct .rct-mkart .mk-alt{display:flex;align-items:center;gap:12px}
#rct .rct-mkart .mk-fiyat{color:var(--sub2);font-size:12.5px;font-weight:600}
#rct .rct-mkart .mk-maliyet{color:var(--sub);font-size:12.5px}
#rct .rct-mkart .material-icons-outlined.cev-k{color:var(--sub);font-size:20px;margin-left:auto}

/* ---- Rozet (Flutter MRozet) ---- */
.rct-rozet{display:inline-block;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:700;
  color:#fff;flex-shrink:0}

/* ---- Bölüm başlığı (Flutter MBolumBaslik) ---- */
#rct .rct-bolum{display:flex;align-items:center;gap:8px;padding:12px 0 8px;margin-top:4px}
#rct .rct-bolum .cubuk{width:4px;height:18px;border-radius:3px;flex-shrink:0}
#rct .rct-bolum .boluad{color:var(--ink);font-size:15px;font-weight:700}
#rct .rct-bolum .sayi{border-radius:10px;padding:1px 8px;font-size:11px;font-weight:700}

/* ---- Boş durum ---- */
#rct .rct-bos{text-align:center;padding:56px 16px;color:var(--sub)}
#rct .rct-bos .material-icons-outlined{font-size:48px;margin-bottom:10px;display:block}
#rct .rct-bos p{font-size:14px;margin:0}

/* ---- Yükleniyor ---- */
.rct-spin{display:inline-block;width:26px;height:26px;border:3px solid var(--line,#e2e8f0);
  border-top-color:var(--mor1,#7C3AED);border-radius:50%;animation:rctSpin .7s linear infinite;vertical-align:middle}
@keyframes rctSpin{to{transform:rotate(360deg)}}

/* ====================================================================
   EDİTÖR MODAL (Flutter ReceteEditorScreen → BottomSheet / Sayfa)
   ==================================================================== */
.rct-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:700;align-items:flex-end;justify-content:center}
.rct-modal-bg.acik{display:flex}
.rct-modal-bg.merkez{align-items:center}
.rct-modal{background:var(--card);border-radius:20px 20px 0 0;width:100%;max-width:680px;
  max-height:92vh;overflow-y:auto;padding:18px 18px 28px}
.rct-modal-bg.merkez .rct-modal{border-radius:18px;margin:16px;border-radius:18px}
body.koyu .rct-modal,.rct-modal-koyu{background:#0B1020}
.rct-modal .cizgi{width:40px;height:4px;border-radius:2px;background:var(--line);margin:0 auto 14px}
.rct-modal .baslik{color:var(--ink);font-size:18px;font-weight:800;margin-bottom:4px}
.rct-modal .alt-baslik{color:var(--sub);font-size:12px;margin-bottom:16px}

/* Özet gradient (Flutter _ozet / _ozetMasaustu) */
.rct-ozet-grad{background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:16px;padding:18px 20px;
  display:flex;align-items:center;gap:16px;margin-bottom:18px}
.rct-ozet-grad .sol{flex:1;min-width:0}
.rct-ozet-grad .etiket{color:rgba(255,255,255,.75);font-size:13px}
.rct-ozet-grad .deger{color:#fff;font-size:26px;font-weight:800;line-height:1.15;margin:2px 0}
.rct-ozet-grad .satis{color:rgba(255,255,255,.7);font-size:13px}
.rct-ozet-grad .fc-kutu{background:rgba(255,255,255,.18);border-radius:12px;padding:10px 16px;
  text-align:center;flex-shrink:0}
.rct-ozet-grad .fc-sayi{color:#fff;font-size:26px;font-weight:800;line-height:1}
.rct-ozet-grad .fc-etiket{color:rgba(255,255,255,.7);font-size:11px}

/* Kalem kartları (Flutter _kalemKart) */
.rct-kalem{display:flex;align-items:center;gap:10px;padding:12px;border-radius:12px;
  background:var(--card2);border:1px solid var(--line);margin-bottom:8px}
body.koyu .rct-kalem{background:#161C2E}
.rct-kalem .sol{flex:1;min-width:0}
.rct-kalem .ym-rozet{display:inline-block;padding:1px 6px;border-radius:6px;font-size:9px;
  font-weight:700;background:rgba(79,70,229,.16);color:#4F46E5;margin-left:5px}
.rct-kalem .miktar{color:var(--sub);font-size:12px;margin-top:2px}
.rct-kalem .satir-mal{color:var(--sub);font-size:13px;font-weight:700;flex-shrink:0}
.rct-kalem .sil-btn{background:none;border:none;color:var(--sub);cursor:pointer;padding:4px;
  border-radius:6px;line-height:1;flex-shrink:0}
.rct-kalem .sil-btn:hover{color:var(--kirmizi,#F43F5E)}
.rct-kalem .sil-btn .material-icons-outlined{font-size:18px}

/* Form alanları (Flutter _dec) */
.rct-field{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
.rct-field label{color:var(--sub);font-size:12px;font-weight:600}
.rct-field input,.rct-field select{padding:10px 12px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s;
  width:100%;box-sizing:border-box}
body.koyu .rct-field input,body.koyu .rct-field select{background:#161C2E}
.rct-field input:focus,.rct-field select:focus{border-color:var(--mor1)}
.rct-row{display:flex;gap:8px}
.rct-row .rct-field{flex:1}

/* Segment seçim (Flutter seg — Malzeme / Yarı Mamül seçici) */
.rct-seg{display:flex;gap:8px;margin-bottom:14px}
.rct-seg button{flex:1;padding:10px 0;border-radius:10px;border:1.5px solid var(--line);
  background:transparent;color:var(--sub);font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.rct-seg button.aktif{background:#7C3AED;border-color:#7C3AED;color:#fff}
.rct-seg button:hover:not(.aktif){border-color:var(--mor1);color:var(--mor1)}

/* Aranabilir seçim alanı (Flutter _secField) */
.rct-sec-alan{display:flex;align-items:center;padding:10px 12px;border:1px solid var(--line);
  border-radius:10px;cursor:pointer;background:var(--card);transition:border-color .15s;margin-bottom:10px}
body.koyu .rct-sec-alan{background:#161C2E}
.rct-sec-alan:hover{border-color:var(--mor1)}
.rct-sec-alan .metin{flex:1;min-width:0;color:var(--ink);font-size:14px;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rct-sec-alan .metin.bos{color:var(--sub)}
.rct-sec-alan .material-icons-outlined{color:var(--sub);font-size:20px;flex-shrink:0}

/* Arama picker overlay (Flutter _secimPicker) */
.rct-picker-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:900;align-items:flex-end;justify-content:center}
.rct-picker-bg.acik{display:flex}
.rct-picker{background:var(--card);border-radius:20px 20px 0 0;width:100%;max-width:680px;
  max-height:72vh;display:flex;flex-direction:column;padding:12px 16px}
body.koyu .rct-picker{background:#0B1020}
.rct-picker .pick-baslik{display:flex;align-items:center;margin-bottom:10px}
.rct-picker .pick-baslik span{flex:1;color:var(--ink);font-size:16px;font-weight:700}
.rct-picker .pick-baslik button{background:none;border:none;cursor:pointer;color:var(--sub);line-height:1;padding:4px}
.rct-picker .pick-baslik button .material-icons-outlined{font-size:22px}
.rct-picker .pick-ara{padding:9px 12px 9px 34px;border:1px solid var(--line);border-radius:10px;
  background:var(--card2);color:var(--ink);font-size:14px;outline:none;width:100%;box-sizing:border-box;margin-bottom:8px}
body.koyu .rct-picker .pick-ara{background:#161C2E}
.rct-picker .pick-ara:focus{border-color:var(--mor1)}
.rct-picker-wrap{position:relative}
.rct-picker-wrap .material-icons-outlined{position:absolute;left:8px;top:50%;transform:translateY(-50%);
  font-size:16px;color:var(--sub);pointer-events:none}
.rct-picker .pick-liste{overflow-y:auto;flex:1}
.rct-picker .pick-item{padding:11px 6px;color:var(--ink);font-size:14px;cursor:pointer;
  border-bottom:1px solid var(--line);transition:background .1s}
.rct-picker .pick-item:last-child{border-bottom:none}
.rct-picker .pick-item:hover{background:rgba(124,58,237,.07)}
.rct-picker .pick-bos{text-align:center;padding:24px;color:var(--sub);font-size:13px}

/* Yarı mamül modal (üst modal içinde kalem ekleme) */
#rctEkleModal{z-index:800}

/* Kaydet butonu (Flutter FilledButton) */
.rct-kaydet{width:100%;padding:14px;border:none;border-radius:12px;background:var(--mor1);
  color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:opacity .15s;margin-top:4px}
.rct-kaydet:hover{opacity:.88}
.rct-kaydet:disabled{opacity:.5;cursor:not-allowed}

/* Alt bar sabit (Flutter _altBar) */
.rct-alt-bar{padding:12px 18px 20px;border-top:1px solid var(--line);margin-top:8px;
  position:sticky;bottom:0;background:var(--card)}
body.koyu .rct-alt-bar{background:#0B1020}

/* Kalemler başlığı satırı (Flutter Row: "Malzemeler" + "Malzeme Ekle") */
.rct-kalem-baslik{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.rct-kalem-baslik .kbaslik{color:var(--ink);font-size:15px;font-weight:700}
.rct-kalem-baslik button{display:flex;align-items:center;gap:4px;padding:7px 12px;
  background:transparent;border:1.5px solid var(--mavi);border-radius:10px;
  color:var(--mavi);font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
.rct-kalem-baslik button:hover{background:rgba(79,70,229,.09)}
.rct-kalem-baslik button .material-icons-outlined{font-size:16px}

/* Boş reçete metni */
.rct-bos-recete{color:var(--sub);font-size:13px;padding:16px 0}

/* Toast (Flutter SnackBar) */
#rctToast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%);
  background:#1e293b;color:#fff;padding:11px 18px;border-radius:12px;font-size:13.5px;font-weight:600;
  z-index:1100;opacity:0;transition:opacity .25s;pointer-events:none;white-space:nowrap;max-width:90vw}
body.koyu #rctToast{background:#e2e8f0;color:#1e293b}
#rctToast.goruli{opacity:1}

/* ---- Yarı Mamül Yönetim Sayfası (tab veya ayrı bölüm) ---- */
#rctYmSayfa{display:none}
#rctYmSayfa .ym-kart{display:flex;align-items:center;gap:12px;padding:13px;border-radius:14px;
  background:var(--card);border:1px solid var(--line);margin-bottom:8px;cursor:pointer;transition:box-shadow .1s}
#rctYmSayfa .ym-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.08)}
#rctYmSayfa .ym-ikon{width:38px;height:38px;border-radius:10px;background:rgba(79,70,229,.14);
  display:flex;align-items:center;justify-content:center;flex-shrink:0}
#rctYmSayfa .ym-ikon .material-icons-outlined{color:var(--mavi);font-size:20px}
#rctYmSayfa .ym-sol{flex:1;min-width:0}
#rctYmSayfa .ym-ad{color:var(--ink);font-size:15px;font-weight:700;margin-bottom:2px}
#rctYmSayfa .ym-alt{color:var(--sub);font-size:12px}

/* Sayfa geçiş animasyonu */
.rct-sayfa{transition:opacity .15s}
</style>
@endpush

@section('rk_araclar')
<div id="rctAraBar" style="display:none">
    <div class="ara-ic">
        <span class="material-icons-outlined">search</span>
        <input type="text" id="rctAraGenis" placeholder="Ürün ara" oninput="rctFiltre(this.value)">
    </div>
    <button onclick="rctYmGoster()" id="rctYmBtn"
        style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:transparent;
               border:1.5px solid var(--mavi,#4F46E5);border-radius:10px;color:var(--mavi,#4F46E5);
               font-size:13px;font-weight:600;cursor:pointer">
        <span class="material-icons-outlined" style="font-size:16px">blender</span>Yarı Mamüller
    </button>
</div>
@endsection

@section('rk_alt')
{{ count($urunler) }} ürün · {{ $recetesizSayi }} reçetesiz
@endsection

@section('content')
<div id="rct">

    {{-- ===== ÜRÜN LİSTESİ SAYFASI ===== --}}
    <div id="rctUrunSayfa" class="rct-sayfa">

        {{-- Amber uyarı bandı (Flutter recetesiz > 0) --}}
        @if ($recetesizSayi > 0)
        <div class="rct-uyari" id="rctUyari">
            <span class="material-icons-outlined">info_outline</span>
            <p>{{ $recetesizSayi }} ürünün reçetesi yok — food-cost hesaplanamıyor.</p>
        </div>
        @endif

        {{-- Arama (mobil) --}}
        <div class="rct-ara-wrap" id="rctAraMobilWrap">
            <span class="material-icons-outlined">search</span>
            <input type="text" id="rctAra" placeholder="Ürün ara" oninput="rctFiltre(this.value)">
        </div>

        {{-- Masaüstü: bölümler + ızgara (Flutter _masaustu) --}}
        <div id="rctGenis" style="display:none">
            @if ($receteliler->isNotEmpty())
            <div class="rct-bolum">
                <div class="cubuk" style="background:#10B981"></div>
                <span class="boluad">Reçeteli Ürünler</span>
                <span class="sayi" style="background:rgba(16,185,129,.12);color:#10B981">{{ $receteliler->count() }}</span>
            </div>
            <div class="rct-grid" id="rctGridReceteli">
                @foreach ($receteliler as $u)
                @php $fc = (float)($u->food_cost ?? 0); $renk = $fcRenk($fc); @endphp
                <div class="rct-mkart" data-ad="{{ strtolower($u->ad) }}" onclick="rctEditorAc({{ $u->id }}, '{{ addslashes($u->ad) }}')">
                    <div class="mk-ust">
                        <span class="mk-ad" title="{{ $u->ad }}">{{ $u->ad }}</span>
                        @if ($fc > 0)
                        <span class="rct-rozet" style="background:{{ $renk }}">%{{ round($fc) }}</span>
                        @else
                        <span class="rct-rozet" style="background:#F59E0B">reçetesiz</span>
                        @endif
                    </div>
                    <div class="mk-alt">
                        <span class="mk-fiyat">Fiyat {{ $tl($u->fiyat ?? 0) }}</span>
                        @if (($u->maliyet ?? 0) > 0)
                        <span class="mk-maliyet">Maliyet {{ $tl($u->maliyet ?? 0) }}</span>
                        @endif
                        <span class="material-icons-outlined cev-k">chevron_right</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if ($recetesizler->isNotEmpty())
            <div class="rct-bolum" style="margin-top:4px">
                <div class="cubuk" style="background:#F59E0B"></div>
                <span class="boluad">Reçetesiz Ürünler</span>
                <span class="sayi" style="background:rgba(245,158,11,.12);color:#F59E0B">{{ $recetesizler->count() }}</span>
            </div>
            <div class="rct-grid" id="rctGridRecetesiz">
                @foreach ($recetesizler as $u)
                <div class="rct-mkart" data-ad="{{ strtolower($u->ad) }}" onclick="rctEditorAc({{ $u->id }}, '{{ addslashes($u->ad) }}')">
                    <div class="mk-ust">
                        <span class="mk-ad" title="{{ $u->ad }}">{{ $u->ad }}</span>
                        <span class="rct-rozet" style="background:#F59E0B">reçetesiz</span>
                    </div>
                    <div class="mk-alt">
                        <span class="mk-fiyat">Fiyat {{ $tl($u->fiyat ?? 0) }}</span>
                        <span class="material-icons-outlined cev-k">chevron_right</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if ($receteliler->isEmpty() && $recetesizler->isEmpty())
            <div class="rct-bos">
                <span class="material-icons-outlined">menu_book</span>
                <p>Ürün yok.</p>
            </div>
            @endif
        </div>

        {{-- Mobil liste (Flutter _kart) --}}
        <div id="rctMobil">
            @foreach ($urunler as $u)
            @php $fc = (float)($u->food_cost ?? 0); $renk = $fcRenk($fc); $receteli = $u->receteli ?? false; @endphp
            <div class="rct-kart" data-ad="{{ strtolower($u->ad) }}" onclick="rctEditorAc({{ $u->id }}, '{{ addslashes($u->ad) }}')">
                <div class="sol">
                    <div class="ad">
                        <span>{{ $u->ad }}</span>
                        @if (!$receteli)
                        <span class="rozet-rs">reçetesiz</span>
                        @endif
                    </div>
                    <div class="alt">Fiyat {{ $tl($u->fiyat ?? 0) }}{{ $receteli ? ' · Maliyet ' . $tl($u->maliyet ?? 0) : '' }}</div>
                </div>
                @if ($receteli && $fc > 0)
                <div class="fc-badge" style="background:{{ $renk }}1a;color:{{ $renk }}">%{{ round($fc) }}</div>
                @endif
                <span class="material-icons-outlined cev">chevron_right</span>
            </div>
            @endforeach

            @if (count($urunler) === 0)
            <div class="rct-bos">
                <span class="material-icons-outlined">menu_book</span>
                <p>Ürün yok.</p>
            </div>
            @endif
        </div>
    </div>

    {{-- ===== YARI MAMÜL SAYFASI (Flutter YariMamulListScreen) ===== --}}
    <div id="rctYmSayfa" class="rct-sayfa">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <button onclick="rctYmGeri()" style="background:none;border:none;cursor:pointer;
                color:var(--ink);display:flex;align-items:center;gap:4px;font-size:14px;font-weight:600;padding:0">
                <span class="material-icons-outlined">arrow_back</span> Geri
            </button>
            <span style="color:var(--ink);font-size:17px;font-weight:700;flex:1">Yarı Mamüller</span>
            @if ($duzenleyebilir)
            <button onclick="rctYmEditorAc(null)"
                style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:var(--mor1);
                       border:none;border-radius:10px;color:#fff;font-size:13px;font-weight:700;cursor:pointer">
                <span class="material-icons-outlined" style="font-size:16px">add</span>Yeni
            </button>
            @endif
        </div>

        <div id="rctYmListe">
            @if ($yarimamuller->isEmpty())
            <div class="rct-bos">
                <span class="material-icons-outlined">blender</span>
                <p>Henüz yarı mamül yok.<br><br>Sos, hamur, marine et gibi ara ürünleri buradan tanımlayın;<br>sonra ürün reçetelerine ekleyin. Satışta hammaddesi otomatik düşer.</p>
            </div>
            @else
            @foreach ($yarimamuller as $ym)
            <div class="ym-kart" onclick="rctYmEditorAc({{ $ym->id }})">
                <div class="ym-ikon"><span class="material-icons-outlined">blender</span></div>
                <div class="ym-sol">
                    <div class="ym-ad">{{ $ym->ad }}</div>
                    <div class="ym-alt">Verim {{ $mik($ym->verim_miktar) }} {{ $ym->verim_birim ?? '' }} · {{ $ym->kalem_sayisi ?? 0 }} bileşen · {{ $tl($ym->toplam_maliyet ?? 0) }}</div>
                </div>
                @if ($duzenleyebilir)
                <button onclick="event.stopPropagation();rctYmSil({{ $ym->id }}, '{{ addslashes($ym->ad) }}')"
                    style="background:none;border:none;cursor:pointer;color:var(--sub);padding:4px;border-radius:6px">
                    <span class="material-icons-outlined" style="font-size:20px">delete_outline</span>
                </button>
                @endif
                <span class="material-icons-outlined" style="color:var(--sub);font-size:20px">chevron_right</span>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- REÇETE EDİTÖR MODALI (Flutter ReceteEditorScreen)           --}}
{{-- ============================================================ --}}
<div class="rct-modal-bg merkez" id="rctEditorBg" onclick="if(event.target===this)rctEditorKapat()">
  <div class="rct-modal rct-modal-koyu" style="background:var(--card)">
    <div class="cizgi"></div>
    <div class="baslik" id="rctEditorBaslik">Reçete Editörü</div>
    <div class="alt-baslik" id="rctEditorAltBaslik">Yükleniyor…</div>

    <div id="rctEditorIc">
      <div style="text-align:center;padding:40px 0"><span class="rct-spin"></span></div>
    </div>

    <div class="rct-alt-bar" id="rctEditorAltBar" style="display:none">
      <button class="rct-kaydet" id="rctKaydetBtn" onclick="rctReceteKaydet()">Reçeteyi Kaydet</button>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- KALEM EKLEME MODALI (Flutter _kalemEkle / showModalBottomSheet) --}}
{{-- ============================================================ --}}
<div class="rct-modal-bg" id="rctEkleBg" onclick="if(event.target===this)rctEkleBgKapat()">
  <div class="rct-modal" style="max-height:85vh" id="rctEkleMod">
    <div class="cizgi"></div>
    <div class="baslik">Reçeteye Ekle</div>

    <div class="rct-seg" id="rctSegWrap">
      <button id="rctSegMalzeme" class="aktif" onclick="rctSegSec(false)">Malzeme</button>
      <button id="rctSegYM" onclick="rctSegSec(true)">Yarı Mamül</button>
    </div>

    <div id="rctEkleMalzemeBlok">
      <div class="rct-sec-alan" id="rctMalzemeSecAlan" onclick="rctPickerAc('malzeme')">
        <span class="metin bos" id="rctMalzemeSecMetin">Seç</span>
        <span class="material-icons-outlined">arrow_drop_down</span>
      </div>
      <div class="rct-row">
        <div class="rct-field">
          <label>Miktar (1 porsiyon)</label>
          <input type="number" id="rctEkleMiktar" min="0.001" step="any" placeholder="0">
        </div>
        <div class="rct-field">
          <label>Birim</label>
          <select id="rctEkleBirim"></select>
        </div>
      </div>
    </div>

    <div id="rctEkleYMBlok" style="display:none">
      <div class="rct-sec-alan" id="rctYMSecAlan" onclick="rctPickerAc('yarimamul')">
        <span class="metin bos" id="rctYMSecMetin">Seç</span>
        <span class="material-icons-outlined">arrow_drop_down</span>
      </div>
      <div class="rct-row">
        <div class="rct-field">
          <label>Miktar</label>
          <input type="number" id="rctEkleYMMiktar" min="0.001" step="any" placeholder="0">
        </div>
        <div class="rct-field">
          <label>Birim</label>
          <input type="text" id="rctEkleYMBirim" readonly placeholder="—" style="background:var(--card2)">
        </div>
      </div>
    </div>

    <button class="rct-kaydet" onclick="rctKalemEkleOnayla()">Ekle</button>
  </div>
</div>

{{-- ============================================================ --}}
{{-- YARI MAMÜL EDİTÖR MODALI (Flutter YariMamulEditorScreen)    --}}
{{-- ============================================================ --}}
<div class="rct-modal-bg merkez" id="rctYmEdBg" onclick="if(event.target===this)rctYmEdKapat()">
  <div class="rct-modal" style="background:var(--card)">
    <div class="cizgi"></div>
    <div class="baslik" id="rctYmEdBaslik">Yeni Yarı Mamül</div>

    <div class="rct-field">
      <label>Yarı mamül adı (ör. Pizza Sosu)</label>
      <input type="text" id="rctYmEdAd" placeholder="Yarı mamül adı">
    </div>
    <div class="rct-row">
      <div class="rct-field">
        <label>Verim (kaç çıkıyor)</label>
        <input type="number" id="rctYmEdVerim" min="0.001" step="any" value="1">
      </div>
      <div class="rct-field">
        <label>Verim Birimi</label>
        <select id="rctYmEdVerimBirim"></select>
      </div>
    </div>
    <p style="color:var(--sub);font-size:11px;margin:0 0 14px">Örn: "5" + "lt" → bu reçete 5 litre çıkarır.</p>

    <div id="rctYmOzetGrad" class="rct-ozet-grad" style="margin-bottom:14px;padding:14px 18px">
      <span style="color:rgba(255,255,255,.85);font-size:14px">Toplam Maliyet</span>
      <span id="rctYmToplam" style="color:#fff;font-size:20px;font-weight:800;margin-left:auto">0TL</span>
    </div>

    <div class="rct-kalem-baslik">
      <span class="kbaslik">Bileşenler</span>
      <button onclick="rctYmKalemEkle()">
        <span class="material-icons-outlined">add</span>Bileşen Ekle
      </button>
    </div>

    <div id="rctYmKalemler"></div>
    <div id="rctYmBosRecete" class="rct-bos-recete" style="display:none">
      Bileşen ekleyin (malzeme veya başka bir yarı mamül).
    </div>

    <div class="rct-alt-bar">
      <button class="rct-kaydet" id="rctYmKaydetBtn" onclick="rctYmKaydet()">Yarı Mamülü Kaydet</button>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- ARANABILIR PICKER (Flutter _secimPicker)                     --}}
{{-- ============================================================ --}}
<div class="rct-picker-bg" id="rctPickerBg" onclick="if(event.target===this)rctPickerKapat()">
  <div class="rct-picker">
    <div class="pick-baslik">
      <span id="rctPickerBaslik">Seç</span>
      <button onclick="rctPickerKapat()"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="rct-picker-wrap" style="position:relative;margin-bottom:8px">
      <span class="material-icons-outlined">search</span>
      <input class="pick-ara" type="text" id="rctPickerAra" placeholder="Ara" oninput="rctPickerFiltre(this.value)" autofocus>
    </div>
    <div class="pick-liste" id="rctPickerListe"></div>
  </div>
</div>

{{-- Toast --}}
<div id="rctToast"></div>

@push('scripts')
<script>
// ===================================================================
// VERİ (server-side render'dan JS'e aktarım)
// ===================================================================
const RCT = {
    urunler:      @json($urunler->values()),
    malzemeler:   @json($malzemeler),
    yarimamuller: @json($yarimamuller->values()),
    birimler:     @json($birimler),
    duzenleyebilir: {{ $duzenleyebilir ? 'true' : 'false' }},
};

// İndeksler (hızlı erişim)
const malzMap  = {};  // id → malzeme
const ymMap    = {};  // id → yari mamul
const birimAd  = {};  // id → kisaltma
RCT.malzemeler.forEach(m => malzMap[m.id] = m);
RCT.yarimamuller.forEach(y => ymMap[y.id] = y);
RCT.birimler.forEach(b => birimAd[b.id] = b.kisaltma);

// ===================================================================
// YARDIMCI FONKSİYONLAR
// ===================================================================
const _n  = v => (typeof v === 'number' ? v : parseFloat(v) || 0);
const _tl = v => new Intl.NumberFormat('tr-TR').format(Math.round(_n(v))) + 'TL';
const _mik = v => {
    const n = _n(v);
    if (n === Math.round(n)) return new Intl.NumberFormat('tr-TR').format(Math.round(n));
    return n.toFixed(2).replace('.', ',');
};
const _fcRenk = fc => fc >= 38 ? '#F43F5E' : (fc >= 30 ? '#F59E0B' : '#10B981');

// Birim çevrimi (Flutter _cevrim)
const _cevrim = (malzemeId, birimId) => {
    const m = malzMap[malzemeId];
    if (!m) return 1;
    const temel = m.temel_birim_id;
    if (birimId == temel) return 1;
    const bk = birimAd[birimId] || '';
    const tk = birimAd[temel] || '';
    const tablo = {kg:{g:1000},lt:{ml:1000},g:{kg:0.001},ml:{lt:0.001}};
    return (tablo[bk] && tablo[bk][tk]) ? tablo[bk][tk] : 1;
};

// Malzeme birim maliyeti (Flutter _malzemeMaliyet)
const _malzemeMaliyet = id => malzMap[id] ? _n(malzMap[id].guncel_maliyet) : 0;

// Kalem satır maliyeti (Flutter _satir)
const _satirMaliyet = k => {
    if (k.yarimamul) {
        return _n(k.miktar) * _n(k.birim_maliyet);
    }
    return _n(k.miktar) * _cevrim(k.malzeme_id, k.birim_id) * _malzemeMaliyet(k.malzeme_id);
};

// Toplam maliyet (Flutter _maliyet getter)
const _toplamMaliyet = kalemler => kalemler.reduce((t, k) => t + _satirMaliyet(k), 0);

// Food-cost (Flutter _fc getter)
const _fc = (maliyet, fiyat) => fiyat > 0 && maliyet > 0 ? maliyet / fiyat * 100 : 0;

// Toast (Flutter SnackBar)
let _toastT;
function rctToast(msg, ms = 2800) {
    const el = document.getElementById('rctToast');
    el.textContent = msg; el.classList.add('goruli');
    clearTimeout(_toastT);
    _toastT = setTimeout(() => el.classList.remove('goruli'), ms);
}

// ===================================================================
// FİLTRE (arama)
// ===================================================================
function rctFiltre(q) {
    const f = (q || '').trim().toLowerCase();
    document.querySelectorAll('#rctMobil .rct-kart').forEach(el => {
        el.style.display = f === '' || el.dataset.ad.includes(f) ? '' : 'none';
    });
    document.querySelectorAll('#rctGenis .rct-mkart').forEach(el => {
        el.style.display = f === '' || el.dataset.ad.includes(f) ? '' : 'none';
    });
    // Eğer bölüm tamamen boşaldıysa başlık da gizle (basit kural)
    document.querySelectorAll('#rctGenis .rct-bolum').forEach((baslik, i) => {
        const grid = baslik.nextElementSibling;
        if (!grid) return;
        const gorunan = [...grid.querySelectorAll('.rct-mkart')].filter(e => e.style.display !== 'none');
        baslik.style.display = gorunan.length ? '' : 'none';
        grid.style.display   = gorunan.length ? '' : 'none';
    });
}

// ===================================================================
// RESPONSIVE (masaüstü / mobil geçiş)
// ===================================================================
function rctResponsive() {
    const genis = window.innerWidth >= 900;
    const mobil = document.getElementById('rctMobil');
    const grd   = document.getElementById('rctGenis');
    const araBar = document.getElementById('rctAraBar');
    const araMobWrap = document.getElementById('rctAraMobilWrap');
    if (mobil)    mobil.style.display    = genis ? 'none' : '';
    if (grd)      grd.style.display      = genis ? ''     : 'none';
    if (araBar)   araBar.style.display   = genis ? 'flex' : 'none';
    if (araMobWrap) araMobWrap.style.display = genis ? 'none' : '';
}
window.addEventListener('resize', rctResponsive);
rctResponsive();

// ===================================================================
// SAYFA GEÇİŞİ: Ürün listesi ↔ Yarı Mamüller
// ===================================================================
function rctYmGoster() {
    document.getElementById('rctUrunSayfa').style.display = 'none';
    document.getElementById('rctYmSayfa').style.display   = '';
}
function rctYmGeri() {
    document.getElementById('rctYmSayfa').style.display   = 'none';
    document.getElementById('rctUrunSayfa').style.display = '';
}

// ===================================================================
// REÇETE EDİTÖRÜ
// ===================================================================
let _rctUrunId   = null;  // açık ürün id
let _rctFiyat    = 0;
let _rctKalemler = [];    // JS kalem dizisi (state)
let _rctEdDuzen  = false;

function rctEditorAc(urunId, urunAd) {
    _rctUrunId = urunId;
    _rctKalemler = [];
    _rctEdDuzen  = false;

    document.getElementById('rctEditorBaslik').textContent = urunAd;
    document.getElementById('rctEditorAltBaslik').textContent = 'Yükleniyor…';
    document.getElementById('rctEditorIc').innerHTML = '<div style="text-align:center;padding:40px 0"><span class="rct-spin"></span></div>';
    document.getElementById('rctEditorAltBar').style.display = 'none';
    document.getElementById('rctEditorBg').classList.add('acik');

    api('/api/patron/urun-recete?urun_id=' + urunId).then(res => {
        if (!res || !res.ok) { rctToast(res?.hata || 'Yüklenemedi'); rctEditorKapat(); return; }
        _rctFiyat  = _n(res.fiyat);
        _rctEdDuzen = !!res.duzenleyebilir;
        // kalemler
        _rctKalemler = (res.kalemler || []).map(k => {
            if (k.yarimamul || k.alt_recete_id) {
                const ym = ymMap[k.alt_recete_id] || {};
                const verim = _n(ym.verim_miktar) > 0 ? _n(ym.verim_miktar) : 1;
                const bm = ym.id ? _n(ym.toplam_maliyet) / verim : (k.miktar > 0 ? _n(k.satir_maliyet) / _n(k.miktar) : 0);
                return { alt_recete_id: k.alt_recete_id, malzeme: k.malzeme, yarimamul: true,
                         miktar: _n(k.miktar), birim_id: k.birim_id, birim: birimAd[k.birim_id] || k.birim || '',
                         birim_maliyet: bm };
            }
            return { malzeme_id: k.malzeme_id, malzeme: k.malzeme, miktar: _n(k.miktar),
                     birim_id: k.birim_id, birim: birimAd[k.birim_id] || k.birim || '' };
        });
        _rctRenderEditor();
    }).catch(() => { rctToast('Bağlantı hatası'); rctEditorKapat(); });
}

function rctEditorKapat() {
    document.getElementById('rctEditorBg').classList.remove('acik');
    _rctUrunId = null;
}

function _rctRenderEditor() {
    const mal = _toplamMaliyet(_rctKalemler);
    const fc  = _fc(mal, _rctFiyat);
    const fcRenk = _fcRenk(fc);

    let html = '';

    // Özet gradient (Flutter _ozet)
    html += `<div class="rct-ozet-grad">
      <div class="sol">
        <div class="etiket">Tahmini Maliyet</div>
        <div class="deger">${_tl(mal)}</div>
        <div class="satis">Satış ${_tl(_rctFiyat)}</div>
      </div>
      <div class="fc-kutu">
        <div class="fc-sayi">${fc > 0 ? '%' + Math.round(fc) : '—'}</div>
        <div class="fc-etiket">food-cost</div>
      </div>
    </div>`;

    // Malzemeler başlık
    html += `<div class="rct-kalem-baslik">
      <span class="kbaslik">Malzemeler</span>`;
    if (_rctEdDuzen) {
        html += `<button onclick="rctKalemEkleAc()"><span class="material-icons-outlined">add</span>Malzeme Ekle</button>`;
    }
    html += `</div>`;

    if (_rctKalemler.length === 0) {
        html += `<div class="rct-bos-recete">Bu ürünün reçetesi boş. Malzeme ekleyin.</div>`;
    } else {
        _rctKalemler.forEach((k, i) => {
            const satir = _satirMaliyet(k);
            const ymRozet = k.yarimamul ? '<span class="ym-rozet">yarı mamül</span>' : '';
            const silBtn  = _rctEdDuzen
                ? `<button class="sil-btn" onclick="_rctKalemSil(${i})" title="Kaldır"><span class="material-icons-outlined">close</span></button>`
                : '';
            html += `<div class="rct-kalem">
              <div class="sol">
                <div style="display:flex;align-items:center;gap:4px;font-weight:600;color:var(--ink);font-size:14px">
                  <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(k.malzeme)}</span>${ymRozet}
                </div>
                <div class="miktar">${_mik(_n(k.miktar))} ${escHtml(k.birim || '')}</div>
              </div>
              <span class="satir-mal">${_tl(satir)}</span>
              ${silBtn}
            </div>`;
        });
    }

    document.getElementById('rctEditorIc').innerHTML = html;
    document.getElementById('rctEditorAltBaslik').textContent = 'Reçete editörü';

    if (_rctEdDuzen) {
        document.getElementById('rctEditorAltBar').style.display = '';
        document.getElementById('rctKaydetBtn').disabled = false;
        document.getElementById('rctKaydetBtn').textContent = 'Reçeteyi Kaydet';
    }
}

function _rctKalemSil(i) {
    _rctKalemler.splice(i, 1);
    _rctRenderEditor();
}

async function rctReceteKaydet() {
    const btn = document.getElementById('rctKaydetBtn');
    btn.disabled = true; btn.textContent = 'Kaydediliyor…';
    const gonder = _rctKalemler.map(k => k.yarimamul
        ? { alt_recete_id: k.alt_recete_id, miktar: k.miktar, birim_id: k.birim_id }
        : { malzeme_id: k.malzeme_id, miktar: k.miktar, birim_id: k.birim_id }
    );
    try {
        const res = await api('/api/patron/recete-kaydet', { urun_id: _rctUrunId, kalemler: JSON.stringify(gonder) });
        if (res && res.ok) {
            rctToast('Reçete kaydedildi');
            rctEditorKapat();
        } else {
            rctToast(res?.hata || 'Kaydedilemedi');
            btn.disabled = false; btn.textContent = 'Reçeteyi Kaydet';
        }
    } catch(_) {
        rctToast('Bağlantı hatası');
        btn.disabled = false; btn.textContent = 'Reçeteyi Kaydet';
    }
}

// ===================================================================
// KALEM EKLEME (Flutter _kalemEkle / showModalBottomSheet)
// ===================================================================
let _ekleYariMod = false;
let _ekleMalzemeId = 0;
let _ekleBirimId   = 0;
let _ekleYmId      = 0;

function rctKalemEkleAc() {
    _ekleYariMod   = false;
    _ekleMalzemeId = RCT.malzemeler.length ? RCT.malzemeler[0].id : 0;
    _ekleBirimId   = _ekleMalzemeId ? malzMap[_ekleMalzemeId].temel_birim_id : 0;
    _ekleYmId      = RCT.yarimamuller.length ? RCT.yarimamuller[0].id : 0;

    _rctSegRender();
    _rctEkleRender();
    document.getElementById('rctEkleMiktar').value  = '';
    document.getElementById('rctEkleYMMiktar').value = '';
    document.getElementById('rctEkleBg').classList.add('acik');
}

function rctEkleBgKapat() {
    document.getElementById('rctEkleBg').classList.remove('acik');
}

function _rctSegRender() {
    document.getElementById('rctSegMalzeme').classList.toggle('aktif', !_ekleYariMod);
    document.getElementById('rctSegYM').classList.toggle('aktif',  _ekleYariMod);
    document.getElementById('rctEkleMalzemeBlok').style.display = _ekleYariMod ? 'none' : '';
    document.getElementById('rctEkleYMBlok').style.display       = _ekleYariMod ? '' : 'none';
}

function rctSegSec(yariMod) {
    if (yariMod && RCT.yarimamuller.length === 0) { rctToast('Yarı mamül tanımlı değil'); return; }
    _ekleYariMod = yariMod;
    _rctSegRender();
    _rctEkleRender();
}

function _rctEkleRender() {
    if (!_ekleYariMod) {
        // Malzeme seçili
        const m = malzMap[_ekleMalzemeId];
        const ad = m ? m.ad : 'Seç';
        const metinEl = document.getElementById('rctMalzemeSecMetin');
        metinEl.textContent = ad;
        metinEl.classList.toggle('bos', !m);
        // Birim select
        const sel = document.getElementById('rctEkleBirim');
        sel.innerHTML = '';
        RCT.birimler.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id; opt.textContent = b.kisaltma;
            if (b.id == _ekleBirimId) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.onchange = e => { _ekleBirimId = parseInt(e.target.value); };
    } else {
        // Yarı mamül seçili
        const ym = ymMap[_ekleYmId];
        const ad = ym ? ym.ad : 'Seç';
        const metinEl = document.getElementById('rctYMSecMetin');
        metinEl.textContent = ad;
        metinEl.classList.toggle('bos', !ym);
        document.getElementById('rctEkleYMBirim').value = ym ? (ym.verim_birim || '—') : '—';
    }
}

function rctKalemEkleOnayla() {
    if (_ekleYariMod) {
        const ym = ymMap[_ekleYmId];
        if (!ym) { rctToast('Yarı mamül seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleYMMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_rctKalemler.some(k => k.yarimamul && k.alt_recete_id == _ekleYmId)) { rctToast('Bu yarı mamül zaten ekli'); return; }
        const verim = _n(ym.verim_miktar) > 0 ? _n(ym.verim_miktar) : 1;
        const bm = _n(ym.toplam_maliyet) / verim;
        _rctKalemler.push({ alt_recete_id: _ekleYmId, malzeme: ym.ad, yarimamul: true,
            miktar: miktar, birim_id: ym.verim_birim_id, birim: ym.verim_birim || '', birim_maliyet: bm });
    } else {
        const m = malzMap[_ekleMalzemeId];
        if (!m) { rctToast('Malzeme seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_rctKalemler.some(k => !k.yarimamul && k.malzeme_id == _ekleMalzemeId)) { rctToast('Bu malzeme zaten ekli'); return; }
        const birimId = parseInt(document.getElementById('rctEkleBirim').value) || _ekleBirimId;
        _rctKalemler.push({ malzeme_id: _ekleMalzemeId, malzeme: m.ad, miktar: miktar,
            birim_id: birimId, birim: birimAd[birimId] || '' });
    }
    rctEkleBgKapat();
    _rctRenderEditor();
}

// ===================================================================
// PICKER (Flutter _secimPicker — aranabilir seçim)
// ===================================================================
let _pickerHedeti = ''; // 'malzeme' | 'yarimamul' | 'ym_ed_malzeme' | 'ym_ed_ym'
let _pickerItems  = [];

function rctPickerAc(hedef) {
    _pickerHedeti = hedef;
    if (hedef === 'malzeme') {
        _pickerItems = RCT.malzemeler.map(m => ({ id: m.id, ad: m.ad }));
        document.getElementById('rctPickerBaslik').textContent = 'Malzeme seç';
    } else if (hedef === 'yarimamul') {
        // Hali hazırda eklenenler hariç (aynı yarı mamülü iki kez ekleme engeli)
        const ekli = _rctKalemler.filter(k => k.yarimamul).map(k => k.alt_recete_id);
        // Ayrıca ym editörde kendi kendini seçmeyi engelle
        _pickerItems = RCT.yarimamuller
            .filter(y => !ekli.includes(y.id) && y.id !== _rctYmEdId)
            .map(y => ({ id: y.id, ad: y.ad }));
        document.getElementById('rctPickerBaslik').textContent = 'Yarı mamül seç';
    } else if (hedef === 'ym_ed_malzeme') {
        _pickerItems = RCT.malzemeler.map(m => ({ id: m.id, ad: m.ad }));
        document.getElementById('rctPickerBaslik').textContent = 'Malzeme seç';
    } else if (hedef === 'ym_ed_ym') {
        const ekli = _ymEdKalemler.filter(k => k.yarimamul).map(k => k.alt_recete_id);
        _pickerItems = RCT.yarimamuller
            .filter(y => y.id !== _rctYmEdId && !ekli.includes(y.id))
            .map(y => ({ id: y.id, ad: y.ad }));
        document.getElementById('rctPickerBaslik').textContent = 'Yarı mamül seç';
    }
    document.getElementById('rctPickerAra').value = '';
    _rctPickerRender('');
    document.getElementById('rctPickerBg').classList.add('acik');
}

function rctPickerKapat() {
    document.getElementById('rctPickerBg').classList.remove('acik');
}

function rctPickerFiltre(q) { _rctPickerRender((q || '').trim().toLowerCase()); }

function _rctPickerRender(f) {
    const filt = f ? _pickerItems.filter(i => i.ad.toLowerCase().includes(f)) : _pickerItems;
    const ul = document.getElementById('rctPickerListe');
    if (!filt.length) { ul.innerHTML = '<div class="pick-bos">Eşleşme yok</div>'; return; }
    ul.innerHTML = filt.map(i =>
        `<div class="pick-item" onclick="rctPickerSec(${i.id})">${escHtml(i.ad)}</div>`
    ).join('');
}

function rctPickerSec(id) {
    rctPickerKapat();
    if (_pickerHedeti === 'malzeme') {
        _ekleMalzemeId = id;
        const m = malzMap[id];
        if (m) _ekleBirimId = m.temel_birim_id;
        _rctEkleRender();
    } else if (_pickerHedeti === 'yarimamul') {
        _ekleYmId = id;
        _rctEkleRender();
    } else if (_pickerHedeti === 'ym_ed_malzeme') {
        _ymEdMalzemeId = id;
        const m = malzMap[id];
        if (m) _ymEdBirimId = m.temel_birim_id;
        _rctYmEdEkleRender();
    } else if (_pickerHedeti === 'ym_ed_ym') {
        _ymEdYmId = id;
        _rctYmEdEkleRender();
    }
}

// ===================================================================
// YARI MAMÜL EDITÖR
// ===================================================================
let _rctYmEdId    = null;   // null = yeni, sayı = düzenleme
let _ymEdKalemler = [];
let _ymEdVerimBirimId = 0;
let _ymEdMalzemeId = 0;
let _ymEdBirimId   = 0;
let _ymEdYmId      = 0;
let _ymEdYariMod   = false;

function rctYmEditorAc(id) {
    _rctYmEdId    = id;
    _ymEdKalemler = [];
    _ymEdYariMod  = false;

    document.getElementById('rctYmEdBaslik').textContent = id ? 'Yarı Mamül' : 'Yeni Yarı Mamül';
    document.getElementById('rctYmEdAd').value    = '';
    document.getElementById('rctYmEdVerim').value = '1';

    // Birim select doldur
    const selB = document.getElementById('rctYmEdVerimBirim');
    selB.innerHTML = '';
    RCT.birimler.forEach(b => {
        const o = document.createElement('option');
        o.value = b.id; o.textContent = b.kisaltma;
        selB.appendChild(o);
    });
    _ymEdVerimBirimId = RCT.birimler.length ? RCT.birimler[0].id : 0;
    selB.onchange = e => { _ymEdVerimBirimId = parseInt(e.target.value); };

    // İlk malzeme
    _ymEdMalzemeId = RCT.malzemeler.length ? RCT.malzemeler[0].id : 0;
    _ymEdBirimId   = _ymEdMalzemeId ? malzMap[_ymEdMalzemeId].temel_birim_id : 0;
    _ymEdYmId      = RCT.yarimamuller.length ? RCT.yarimamuller.filter(y => y.id != id)[0]?.id ?? 0 : 0;

    if (id) {
        // Detay yükle
        api('/api/patron/yarimamul-detay?id=' + id).then(res => {
            if (!res || !res.ok) { rctToast(res?.hata || 'Yüklenemedi'); return; }
            document.getElementById('rctYmEdAd').value    = res.ad || '';
            document.getElementById('rctYmEdVerim').value = res.verim_miktar || 1;
            _ymEdVerimBirimId = res.verim_birim_id;
            // set select
            const sel = document.getElementById('rctYmEdVerimBirim');
            [...sel.options].forEach(o => { if (parseInt(o.value) == _ymEdVerimBirimId) o.selected = true; });
            _ymEdKalemler = (res.kalemler || []).map(k => {
                if (k.yarimamul || k.alt_recete_id) {
                    const ym = ymMap[k.alt_recete_id] || {};
                    const v  = _n(ym.verim_miktar) > 0 ? _n(ym.verim_miktar) : 1;
                    const bm = ym.id ? _n(ym.toplam_maliyet) / v : (k.miktar > 0 ? _n(k.satir_maliyet) / _n(k.miktar) : 0);
                    return { alt_recete_id: k.alt_recete_id, malzeme: k.malzeme, yarimamul: true,
                             miktar: _n(k.miktar), birim_id: k.birim_id, birim: birimAd[k.birim_id] || k.birim || '',
                             birim_maliyet: bm };
                }
                return { malzeme_id: k.malzeme_id, malzeme: k.malzeme, miktar: _n(k.miktar),
                         birim_id: k.birim_id, birim: birimAd[k.birim_id] || k.birim || '' };
            });
            _rctYmEdRender();
        });
    }

    _rctYmEdRender();
    document.getElementById('rctYmEdBg').classList.add('acik');
}

function rctYmEdKapat() {
    document.getElementById('rctYmEdBg').classList.remove('acik');
    _rctYmEdId = null;
}

function _rctYmEdRender() {
    const mal = _toplamMaliyet(_ymEdKalemler);
    document.getElementById('rctYmToplam').textContent = _tl(mal);

    const div = document.getElementById('rctYmKalemler');
    const bos = document.getElementById('rctYmBosRecete');
    if (_ymEdKalemler.length === 0) {
        div.innerHTML = '';
        bos.style.display = '';
        return;
    }
    bos.style.display = 'none';
    div.innerHTML = _ymEdKalemler.map((k, i) => {
        const ymRozet = k.yarimamul ? '<span class="ym-rozet">yarı mamül</span>' : '';
        return `<div class="rct-kalem">
          <div class="sol">
            <div style="display:flex;align-items:center;gap:4px;font-weight:600;color:var(--ink);font-size:14px">
              <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(k.malzeme)}</span>${ymRozet}
            </div>
            <div class="miktar">${_mik(_n(k.miktar))} ${escHtml(k.birim || '')}</div>
          </div>
          <span class="satir-mal">${_tl(_satirMaliyet(k))}</span>
          <button class="sil-btn" onclick="_ymEdKalemSil(${i})"><span class="material-icons-outlined">close</span></button>
        </div>`;
    }).join('');
}

function _ymEdKalemSil(i) {
    _ymEdKalemler.splice(i, 1);
    _rctYmEdRender();
}

// Bileşen ekle (yarı mamül editörü için — ayrı state)
let _ymEkleYariMod = false;
let _ymEkleMalzemeId = 0;
let _ymEkleBirimId   = 0;
let _ymEkleYmId      = 0;

function rctYmKalemEkle() {
    _ymEkleYariMod   = false;
    _ymEkleMalzemeId = RCT.malzemeler.length ? RCT.malzemeler[0].id : 0;
    _ymEkleBirimId   = _ymEkleMalzemeId ? malzMap[_ymEkleMalzemeId].temel_birim_id : 0;
    _ymEkleYmId      = RCT.yarimamuller.filter(y => y.id != _rctYmEdId)[0]?.id ?? 0;

    // Kalem ekleme modalını yarı mamül editörü için de kullan — state izole
    _ekleYariMod   = _ymEkleYariMod;
    _ekleMalzemeId = _ymEkleMalzemeId;
    _ekleBirimId   = _ymEkleBirimId;
    _ekleYmId      = _ymEkleYmId;

    // Hedef: ym_ed moduna geçeceğiz; picker hedeflerini özel set edelim
    _pickerHedeti  = 'ym_ed_malzeme'; // başlangıç durumu
    document.getElementById('rctSegMalzeme').onclick = () => rctYmSegSec(false);
    document.getElementById('rctSegYM').onclick      = () => rctYmSegSec(true);
    document.getElementById('rctEkleBg').dataset.mod = 'ym';

    _rctYmSegRender();
    _rctYmEkleRender();
    document.getElementById('rctEkleMiktar').value  = '';
    document.getElementById('rctEkleYMMiktar').value = '';
    document.getElementById('rctEkleBg').classList.add('acik');
}

function rctYmSegSec(yariMod) {
    if (yariMod && RCT.yarimamuller.filter(y => y.id != _rctYmEdId).length === 0) {
        rctToast('Başka yarı mamül tanımlı değil'); return;
    }
    _ekleYariMod = yariMod;
    _rctYmSegRender();
    _rctYmEkleRender();
}

function _rctYmSegRender() {
    document.getElementById('rctSegMalzeme').classList.toggle('aktif', !_ekleYariMod);
    document.getElementById('rctSegYM').classList.toggle('aktif',  _ekleYariMod);
    document.getElementById('rctEkleMalzemeBlok').style.display = _ekleYariMod ? 'none' : '';
    document.getElementById('rctEkleYMBlok').style.display       = _ekleYariMod ? '' : 'none';
}

function _rctYmEkleRender() {
    if (!_ekleYariMod) {
        const m = malzMap[_ekleMalzemeId];
        const metinEl = document.getElementById('rctMalzemeSecMetin');
        metinEl.textContent = m ? m.ad : 'Seç';
        metinEl.classList.toggle('bos', !m);
        document.getElementById('rctMalzemeSecAlan').onclick = () => rctPickerAc('ym_ed_malzeme');
        const sel = document.getElementById('rctEkleBirim');
        sel.innerHTML = '';
        RCT.birimler.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id; opt.textContent = b.kisaltma;
            if (b.id == _ekleBirimId) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.onchange = e => { _ekleBirimId = parseInt(e.target.value); };
    } else {
        const ym = RCT.yarimamuller.filter(y => y.id != _rctYmEdId).find(y => y.id == _ekleYmId);
        const metinEl = document.getElementById('rctYMSecMetin');
        metinEl.textContent = ym ? ym.ad : 'Seç';
        metinEl.classList.toggle('bos', !ym);
        document.getElementById('rctYMSecAlan').onclick = () => rctPickerAc('ym_ed_ym');
        document.getElementById('rctEkleYMBirim').value = ym ? (ym.verim_birim || '—') : '—';
        _ekleYmId = ym ? ym.id : 0;
    }
}

// Onayla düğmesi: ürün reçetesi mi, ym editörü mü?
const _rctOrigEkleOnayla = rctKalemEkleOnayla;
function rctKalemEkleOnayla() {
    const mod = document.getElementById('rctEkleBg').dataset.mod || 'urun';
    if (mod === 'ym') {
        _rctYmEkleOnayla();
    } else {
        _rctUrunEkleOnayla();
    }
}

function _rctUrunEkleOnayla() {
    if (_ekleYariMod) {
        const ym = ymMap[_ekleYmId];
        if (!ym) { rctToast('Yarı mamül seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleYMMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_rctKalemler.some(k => k.yarimamul && k.alt_recete_id == _ekleYmId)) { rctToast('Bu yarı mamül zaten ekli'); return; }
        const verim = _n(ym.verim_miktar) > 0 ? _n(ym.verim_miktar) : 1;
        _rctKalemler.push({ alt_recete_id: _ekleYmId, malzeme: ym.ad, yarimamul: true,
            miktar, birim_id: ym.verim_birim_id, birim: ym.verim_birim || '', birim_maliyet: _n(ym.toplam_maliyet) / verim });
    } else {
        const m = malzMap[_ekleMalzemeId];
        if (!m) { rctToast('Malzeme seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_rctKalemler.some(k => !k.yarimamul && k.malzeme_id == _ekleMalzemeId)) { rctToast('Bu malzeme zaten ekli'); return; }
        const birimId = parseInt(document.getElementById('rctEkleBirim').value) || _ekleBirimId;
        _rctKalemler.push({ malzeme_id: _ekleMalzemeId, malzeme: m.ad, miktar, birim_id: birimId, birim: birimAd[birimId] || '' });
    }
    rctEkleBgKapat();
    _rctRenderEditor();
}

function _rctYmEkleOnayla() {
    if (_ekleYariMod) {
        const ym = RCT.yarimamuller.filter(y => y.id != _rctYmEdId).find(y => y.id == _ekleYmId);
        if (!ym) { rctToast('Yarı mamül seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleYMMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_ymEdKalemler.some(k => k.yarimamul && k.alt_recete_id == ym.id)) { rctToast('Bu yarı mamül zaten ekli'); return; }
        const verim = _n(ym.verim_miktar) > 0 ? _n(ym.verim_miktar) : 1;
        _ymEdKalemler.push({ alt_recete_id: ym.id, malzeme: ym.ad, yarimamul: true,
            miktar, birim_id: ym.verim_birim_id, birim: ym.verim_birim || '', birim_maliyet: _n(ym.toplam_maliyet) / verim });
    } else {
        const m = malzMap[_ekleMalzemeId];
        if (!m) { rctToast('Malzeme seçin'); return; }
        const miktar = parseFloat(document.getElementById('rctEkleMiktar').value.replace(',', '.')) || 0;
        if (miktar <= 0) { rctToast('Geçerli miktar girin'); return; }
        if (_ymEdKalemler.some(k => !k.yarimamul && k.malzeme_id == m.id)) { rctToast('Bu malzeme zaten ekli'); return; }
        const birimId = parseInt(document.getElementById('rctEkleBirim').value) || _ekleBirimId;
        _ymEdKalemler.push({ malzeme_id: m.id, malzeme: m.ad, miktar, birim_id: birimId, birim: birimAd[birimId] || '' });
    }
    rctEkleBgKapat();
    _rctYmEdRender();
}

// Picker seçimi sonrası ym editörü güncelle
const _origPickerSec = rctPickerSec;
function rctPickerSec(id) {
    rctPickerKapat();
    if (_pickerHedeti === 'malzeme') {
        _ekleMalzemeId = id;
        const m = malzMap[id];
        if (m) _ekleBirimId = m.temel_birim_id;
        _rctEkleRender();
    } else if (_pickerHedeti === 'yarimamul') {
        _ekleYmId = id;
        _rctEkleRender();
    } else if (_pickerHedeti === 'ym_ed_malzeme') {
        _ekleMalzemeId = id;
        const m = malzMap[id];
        if (m) _ekleBirimId = m.temel_birim_id;
        _rctYmEkleRender();
    } else if (_pickerHedeti === 'ym_ed_ym') {
        _ekleYmId = id;
        _rctYmEkleRender();
    }
}

function _rctYmEkleRender() {
    if (!_ekleYariMod) {
        const m = malzMap[_ekleMalzemeId];
        const metinEl = document.getElementById('rctMalzemeSecMetin');
        metinEl.textContent = m ? m.ad : 'Seç';
        metinEl.classList.toggle('bos', !m);
        const sel = document.getElementById('rctEkleBirim');
        sel.innerHTML = '';
        RCT.birimler.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id; opt.textContent = b.kisaltma;
            if (b.id == _ekleBirimId) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.onchange = e => { _ekleBirimId = parseInt(e.target.value); };
    } else {
        const ym = RCT.yarimamuller.filter(y => y.id != _rctYmEdId).find(y => y.id == _ekleYmId);
        const metinEl = document.getElementById('rctYMSecMetin');
        metinEl.textContent = ym ? ym.ad : 'Seç';
        metinEl.classList.toggle('bos', !ym);
        document.getElementById('rctEkleYMBirim').value = ym ? (ym.verim_birim || '—') : '—';
    }
}

async function rctYmKaydet() {
    const ad = document.getElementById('rctYmEdAd').value.trim();
    if (!ad) { rctToast('Yarı mamül adı girin (ör. Pizza Sosu)'); return; }
    const verim = parseFloat(document.getElementById('rctYmEdVerim').value.replace(',', '.')) || 0;
    if (verim <= 0) { rctToast('Verim 0\'dan büyük olmalı'); return; }
    const verimBirimId = parseInt(document.getElementById('rctYmEdVerimBirim').value) || 0;
    if (!verimBirimId) { rctToast('Verim birimi seçin'); return; }
    if (_ymEdKalemler.length === 0) { rctToast('En az bir bileşen ekleyin'); return; }

    const btn = document.getElementById('rctYmKaydetBtn');
    btn.disabled = true; btn.textContent = 'Kaydediliyor…';

    const gonder = _ymEdKalemler.map(k => k.yarimamul
        ? { alt_recete_id: k.alt_recete_id, miktar: k.miktar, birim_id: k.birim_id }
        : { malzeme_id: k.malzeme_id, miktar: k.miktar, birim_id: k.birim_id }
    );
    try {
        const payload = { ad, verim_miktar: verim, verim_birim_id: verimBirimId, kalemler: JSON.stringify(gonder) };
        if (_rctYmEdId) payload.id = _rctYmEdId;
        const res = await api('/api/patron/yarimamul-kaydet', payload);
        if (res && res.ok) {
            rctToast('Yarı mamül kaydedildi');
            rctYmEdKapat();
            // Listeyi yenile (sayfa reload en basit yol; AJAX güncelleme de yapılabilir)
            location.reload();
        } else {
            rctToast(res?.hata || 'Kaydedilemedi');
            btn.disabled = false; btn.textContent = 'Yarı Mamülü Kaydet';
        }
    } catch(_) {
        rctToast('Bağlantı hatası');
        btn.disabled = false; btn.textContent = 'Yarı Mamülü Kaydet';
    }
}

// ===================================================================
// YARI MAMÜL SİL
// ===================================================================
async function rctYmSil(id, ad) {
    if (!confirm('"' + ad + '" silinsin mi?')) return;
    const res = await api('/api/patron/yarimamul-sil', { id });
    if (res && res.ok) {
        rctToast('Yarı mamül silindi');
        location.reload();
    } else {
        rctToast(res?.hata || 'Silinemedi');
    }
}

// ===================================================================
// KALEM EKLEME MODALi: "Reçeteye Ekle" — state'i ilk açılışta urun modu
// ===================================================================
// Modalı ilk kez açtığımızda (rctKalemEkleAc) dataset.mod'u temizle
const _origKalemEkleAc = rctKalemEkleAc;
function rctKalemEkleAc() {
    document.getElementById('rctEkleBg').dataset.mod = 'urun';
    document.getElementById('rctSegMalzeme').onclick = () => rctSegSec(false);
    document.getElementById('rctSegYM').onclick      = () => rctSegSec(true);
    document.getElementById('rctMalzemeSecAlan').onclick = () => rctPickerAc('malzeme');
    document.getElementById('rctYMSecAlan').onclick      = () => rctPickerAc('yarimamul');
    _ekleYariMod   = false;
    _ekleMalzemeId = RCT.malzemeler.length ? RCT.malzemeler[0].id : 0;
    _ekleBirimId   = _ekleMalzemeId ? malzMap[_ekleMalzemeId].temel_birim_id : 0;
    _ekleYmId      = RCT.yarimamuller.length ? RCT.yarimamuller[0].id : 0;
    _rctSegRender();
    _rctEkleRender();
    document.getElementById('rctEkleMiktar').value   = '';
    document.getElementById('rctEkleYMMiktar').value = '';
    document.getElementById('rctEkleBg').classList.add('acik');
}

// ===================================================================
// YARDIMCI: HTML escape
// ===================================================================
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
@endsection

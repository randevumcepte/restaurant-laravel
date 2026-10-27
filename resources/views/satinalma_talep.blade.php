@extends('layout.app')
@section('title', 'Satın Alma Talepleri')
@section('rk_ikon', 'assignment')
@section('baslik', 'Satın Alma Talepleri')

@php
    $talepler  = $talepler  ?? collect();
    $ozet      = $ozet      ?? (object)['yeni'=>0,'onayda'=>0,'tamamlanan'=>0];
    $malzemeler = $malzemeler ?? collect();
    $birimler   = $birimler   ?? collect();
    $kritikler  = $kritikler  ?? [];
    $waTel      = $waTel      ?? '';

    $durumRenk = [
        'yeni'       => '#4F46E5',
        'teklif'     => '#F59E0B',
        'onayda'     => '#F59E0B',
        'onaylandi'  => '#10B981',
        'siparis'    => '#0EA5E9',
        'tamamlandi' => '#10B981',
    ];
    $durumMetin = [
        'yeni'       => 'Yeni',
        'teklif'     => 'Teklif Bekleniyor',
        'onayda'     => 'Onayda',
        'onaylandi'  => 'Onaylandı',
        'siparis'    => 'Siparişte',
        'tamamlandi' => 'Tamamlandı',
    ];
@endphp

@push('head')
<style>
/* ===== SATIN ALMA TALEPLERİ ===================================== */
#sta {
  --mor1:#7C3AED; --mavi:#4F46E5; --yesil:#10B981; --kirmizi:#F43F5E; --amber:#F59E0B;
  --card:#fff; --card2:#f1f5f9; --ink:#1e293b; --sub:#64748b; --sub2:#94a3b8;
  --line:#e2e8f0; --bg:#f8fafc;
}
body.koyu #sta {
  --card:#1b2235; --card2:#161C2E; --ink:#e2e8f0; --sub:#94a3b8; --sub2:#64748b;
  --line:#2c3650; --bg:#0B1020;
}

/* ---- Özet rozet şeridi (Flutter _ozetBar) ---- */
#sta .sta-ozet-bar {
  display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px;
}
#sta .sta-rozet {
  display: flex; align-items: center; gap: 8px;
  background: var(--card); border: 1px solid var(--line); border-radius: 14px;
  padding: 12px 16px; cursor: default; flex: 1; min-width: 120px;
  transition: box-shadow .12s;
}
#sta .sta-rozet:hover { box-shadow: 0 3px 10px rgba(0,0,0,.08); }
#sta .sta-rozet .sr-sayi {
  font-size: 22px; font-weight: 900; color: var(--ink); line-height: 1;
}
#sta .sta-rozet .sr-etiket {
  font-size: 11.5px; color: var(--sub); font-weight: 600; margin-top: 2px;
}
#sta .sta-rozet .sr-ikon-circ {
  width: 38px; height: 38px; border-radius: 50%; display: flex;
  align-items: center; justify-content: center; flex-shrink: 0;
}
#sta .sta-rozet .sr-ikon-circ .material-icons-outlined { font-size: 20px; }

/* ---- Kritik stok bekçisi banner ---- */
#sta .sta-kritik-banner {
  display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
  background: color-mix(in srgb,var(--kirmizi) 9%,transparent);
  border: 1px solid color-mix(in srgb,var(--kirmizi) 35%,transparent);
  border-radius: 14px; padding: 13px 16px; margin-bottom: 14px;
}
#sta .sta-kritik-banner .skb-sol { display: flex; align-items: center; gap: 11px; min-width: 0; }
#sta .sta-kritik-banner .skb-sol > .material-icons-outlined { font-size: 26px; color: var(--kirmizi,#F43F5E); flex-shrink: 0; }
#sta .sta-kritik-banner .skb-bas { font-size: 14px; font-weight: 800; color: var(--ink,#1e293b); }
#sta .sta-kritik-banner .skb-alt { font-size: 12px; color: var(--sub,#64748b); margin-top: 2px; }
#sta .sta-kritik-banner .skb-btn {
  display: flex; align-items: center; gap: 6px; padding: 9px 15px; border: none; border-radius: 11px;
  background: var(--kirmizi,#F43F5E); color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap;
}
#sta .sta-kritik-banner .skb-btn:hover { opacity: .9; }

/* ---- WA no ayarı ---- */
#sta .sta-wa-ayar {
  display: flex; align-items: center; gap: 9px; flex-wrap: wrap;
  background: var(--card,#fff); border: 1px solid var(--line,#e2e8f0);
  border-radius: 12px; padding: 10px 14px; margin-bottom: 16px;
}
#sta .sta-wa-ayar input {
  padding: 8px 11px; border: 1px solid var(--line,#e2e8f0); border-radius: 9px;
  background: var(--card,#fff); color: var(--ink,#1e293b); font-size: 13px; outline: none;
}
body.koyu #sta .sta-wa-ayar input { background: #161C2E; }
#sta .sta-wa-ayar .sta-wa-btn {
  padding: 8px 14px; border: none; border-radius: 9px; background: #25D366; color: #fff;
  font-size: 12.5px; font-weight: 700; cursor: pointer;
}

/* ---- Gelen kutusu kart listesi ---- */
#sta .sta-liste { display: flex; flex-direction: column; gap: 9px; margin-bottom: 24px; }

#sta .sta-kart {
  display: flex; align-items: center; gap: 12px;
  background: var(--card); border: 1px solid var(--line); border-radius: 16px;
  padding: 14px 16px; cursor: pointer; transition: box-shadow .12s;
}
#sta .sta-kart:hover { box-shadow: 0 4px 14px rgba(0,0,0,.09); }
body.koyu #sta .sta-kart { border-color: var(--line); }

/* Sol renkli şerit (aciliyet) */
#sta .sta-kart .sta-serit {
  width: 4px; min-height: 44px; border-radius: 3px; flex-shrink: 0;
}

/* Sol bilgi bloğu */
#sta .sta-kart .sta-bilgi { flex: 1; min-width: 0; }
#sta .sta-kart .sta-bilgi .sta-dept {
  font-size: 14.5px; font-weight: 800; color: var(--ink);
  display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
}
#sta .sta-kart .sta-bilgi .sta-alt {
  font-size: 12px; color: var(--sub); margin-top: 3px;
}

/* Sağ meta bloğu */
#sta .sta-kart .sta-meta {
  text-align: right; flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;
}

/* ---- Rozet (genel) ---- */
#sta .roz {
  display: inline-block; padding: 2px 9px; border-radius: 20px;
  font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0;
}
#sta .roz.acil { background: var(--kirmizi); }
#sta .roz.normal { background: var(--card2); color: var(--sub); border: 1px solid var(--line); }

/* Durum rozeti */
#sta .sta-durum-roz {
  display: inline-block; padding: 2px 9px; border-radius: 20px;
  font-size: 11px; font-weight: 700; color: #fff; white-space: nowrap;
}

/* Kalem sayacı */
#sta .sta-kalem-cnt {
  font-size: 11.5px; color: var(--sub); display: flex; align-items: center; gap: 3px;
}
#sta .sta-kalem-cnt .material-icons-outlined { font-size: 14px; }

/* ---- Boş durum ---- */
#sta .sta-bos {
  text-align: center; padding: 56px 20px; color: var(--sub);
  background: var(--card); border: 1px solid var(--line); border-radius: 18px;
}
#sta .sta-bos .material-icons-outlined { font-size: 52px; display: block; margin-bottom: 10px; opacity: .45; }
#sta .sta-bos p { font-size: 14px; margin: 0; }

/* ---- MODAL OVERLAY (tüm modallar) ---- */
.sta-mbg {
  display: none; position: fixed; inset: 0; background: rgba(0,0,0,.50);
  z-index: 700; align-items: flex-end; justify-content: center;
}
.sta-mbg.acik { display: flex; }
.sta-mbg.merkez { align-items: center; }

.sta-modal {
  background: var(--card, #fff); border-radius: 22px 22px 0 0;
  width: 100%; max-width: 640px; max-height: 92vh;
  overflow-y: auto; padding: 16px 18px 24px;
}
.sta-mbg.merkez .sta-modal {
  border-radius: 20px; margin: 16px;
}
body.koyu .sta-modal { background: #0B1020; }

.sta-modal .sta-cizgi {
  width: 40px; height: 4px; border-radius: 2px;
  background: var(--line, #e2e8f0); margin: 0 auto 14px;
}
.sta-modal .sta-mod-baslik {
  color: var(--ink, #1e293b); font-size: 18px; font-weight: 800; margin-bottom: 16px;
  display: flex; align-items: center; gap: 8px;
}
.sta-modal .sta-mod-baslik .material-icons-outlined { font-size: 22px; }

/* Form alanları */
.sta-modal .sta-field { display: flex; flex-direction: column; gap: 4px; margin-bottom: 11px; }
.sta-modal .sta-field label { color: var(--sub, #64748b); font-size: 12px; font-weight: 700; letter-spacing: .2px; }
.sta-modal .sta-field input,
.sta-modal .sta-field select,
.sta-modal .sta-field textarea {
  padding: 11px 12px; border: 1px solid var(--line, #e2e8f0); border-radius: 11px;
  background: var(--card, #fff); color: var(--ink, #1e293b);
  font-size: 14px; outline: none; transition: border-color .15s;
  width: 100%; box-sizing: border-box;
}
body.koyu .sta-modal .sta-field input,
body.koyu .sta-modal .sta-field select,
body.koyu .sta-modal .sta-field textarea { background: #161C2E; }
.sta-modal .sta-field input:focus,
.sta-modal .sta-field select:focus,
.sta-modal .sta-field textarea:focus { border-color: var(--mor1, #7C3AED); }

.sta-modal .sta-row { display: flex; gap: 8px; }
.sta-modal .sta-row .sta-field { flex: 1; }

/* Aciliyet toggle */
.sta-acil-toggle { display: flex; gap: 8px; margin-bottom: 12px; }
.sta-acil-toggle button {
  flex: 1; padding: 10px 0; border-radius: 12px; border: 2px solid var(--line, #e2e8f0);
  background: transparent; color: var(--sub, #64748b);
  font-size: 13px; font-weight: 700; cursor: pointer; transition: all .15s;
  display: flex; align-items: center; justify-content: center; gap: 6px;
}
.sta-acil-toggle button.secili-normal {
  border-color: var(--mavi, #4F46E5); background: color-mix(in srgb,#4F46E5 12%,transparent);
  color: var(--mavi, #4F46E5);
}
.sta-acil-toggle button.secili-acil {
  border-color: var(--kirmizi, #F43F5E); background: color-mix(in srgb,#F43F5E 12%,transparent);
  color: var(--kirmizi, #F43F5E);
}

/* Kalem satırları */
.sta-kalem-satiri {
  display: flex; align-items: flex-end; gap: 8px; padding: 10px;
  background: var(--card2, #f1f5f9); border-radius: 12px; margin-bottom: 8px;
}
body.koyu .sta-kalem-satiri { background: #161C2E; }
.sta-kalem-satiri .sta-field { margin-bottom: 0; flex: 1; min-width: 0; }
.sta-kalem-satiri .sta-field-birim { width: 80px; flex: none; }
.sta-kalem-satiri .sta-sil-satir {
  width: 32px; height: 32px; border: none; background: transparent;
  color: var(--sub2, #94a3b8); border-radius: 8px; cursor: pointer; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center; transition: all .12s;
  margin-bottom: 4px;
}
.sta-kalem-satiri .sta-sil-satir:hover { background: color-mix(in srgb,var(--kirmizi) 12%,transparent); color: var(--kirmizi, #F43F5E); }
.sta-kalem-satiri .sta-sil-satir .material-icons-outlined { font-size: 17px; pointer-events: none; }

/* Satır ekle butonu */
.sta-satir-ekle {
  display: flex; align-items: center; gap: 6px; width: 100%;
  padding: 10px 14px; border: 1.5px dashed var(--line, #e2e8f0);
  border-radius: 12px; background: transparent; color: var(--sub, #64748b);
  font-size: 13px; font-weight: 700; cursor: pointer; transition: all .15s;
  margin-bottom: 14px;
}
.sta-satir-ekle:hover { border-color: var(--mor1, #7C3AED); color: var(--mor1, #7C3AED); }

/* Kaydet butonu */
.sta-kaydet-btn {
  width: 100%; padding: 14px; border: none; border-radius: 13px;
  background: var(--mor1, #7C3AED); color: #fff;
  font-size: 15px; font-weight: 700; cursor: pointer; transition: opacity .15s;
}
.sta-kaydet-btn:hover { opacity: .88; }
.sta-kaydet-btn:disabled { opacity: .55; cursor: not-allowed; }

/* Detay modal kalem listesi */
.sta-det-kalem {
  display: flex; align-items: center; gap: 10px;
  padding: 9px 0; border-bottom: 1px solid var(--line, #e2e8f0);
}
.sta-det-kalem:last-child { border-bottom: none; }
.sta-det-kalem .material-icons-outlined { font-size: 17px; color: var(--sub, #64748b); flex-shrink: 0; }
.sta-det-kalem .dk-ad { flex: 1; min-width: 0; }
.sta-det-kalem .dk-ad .dk-malzeme { font-size: 13.5px; font-weight: 700; color: var(--ink, #1e293b); }
.sta-det-kalem .dk-ad .dk-sub { font-size: 11.5px; color: var(--sub, #64748b); margin-top: 1px; }
.sta-det-kalem .dk-mik { font-size: 14px; font-weight: 800; color: var(--ink, #1e293b); white-space: nowrap; }

/* Not kutusu (detay) */
.sta-not-kutu {
  background: color-mix(in srgb,var(--amber) 10%,transparent);
  border: 1px solid color-mix(in srgb,var(--amber) 40%,transparent);
  border-radius: 12px; padding: 10px 14px; margin-bottom: 14px;
  font-size: 13px; color: var(--ink, #1e293b);
  display: flex; gap: 8px; align-items: flex-start;
}
.sta-not-kutu .material-icons-outlined { font-size: 17px; color: var(--amber, #F59E0B); flex-shrink: 0; margin-top: 1px; }

/* Durum güncelleme butonu (detayda) */
.sta-durum-guncelle {
  display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;
}
.sta-durum-guncelle button {
  flex: 1; min-width: 100px; padding: 9px 8px; border-radius: 11px;
  border: 1.5px solid var(--line, #e2e8f0); background: transparent;
  color: var(--sub, #64748b); font-size: 12px; font-weight: 700;
  cursor: pointer; transition: all .15s; display: flex; align-items: center;
  justify-content: center; gap: 5px;
}
.sta-durum-guncelle button:hover { opacity: .78; }

/* Toast */
#staTst {
  position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%);
  background: #1e293b; color: #fff; padding: 11px 20px; border-radius: 12px;
  font-size: 13.5px; font-weight: 600; z-index: 1200;
  opacity: 0; transition: opacity .25s; pointer-events: none; white-space: nowrap; max-width: 90vw;
}
body.koyu #staTst { background: #e2e8f0; color: #1e293b; }
#staTst.goruli { opacity: 1; }

/* ---- Akış adımı şeridi ---- */
#sta .sta-akis, .sta-akis {
  display: flex; align-items: center; gap: 4px; margin-bottom: 16px;
  padding: 10px 12px; background: var(--card2,#f1f5f9); border-radius: 12px;
  overflow-x: auto;
}
body.koyu .sta-akis { background: #161C2E; }
.sta-akis .sa-adim {
  display: flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700;
  color: var(--sub2,#94a3b8); white-space: nowrap;
}
.sta-akis .sa-adim.aktif { color: var(--mor1,#7C3AED); }
.sta-akis .sa-adim.tamam { color: var(--yesil,#10B981); }
.sta-akis .sa-adim .material-icons-outlined { font-size: 15px; }
.sta-akis .sa-ok { color: var(--sub2,#94a3b8); font-size: 13px; }

/* ---- Detay kalem kartı (RFQ) ---- */
.sta-kk {
  background: var(--card2,#f1f5f9); border: 1px solid var(--line,#e2e8f0);
  border-radius: 14px; padding: 12px; margin-bottom: 10px;
}
body.koyu .sta-kk { background: #161C2E; }
.sta-kk .kk-bas { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.sta-kk .kk-bas .material-icons-outlined { font-size: 18px; color: var(--mor1,#7C3AED); }
.sta-kk .kk-ad { font-size: 14px; font-weight: 800; color: var(--ink,#1e293b); flex: 1; }
.sta-kk .kk-mik { font-size: 13px; font-weight: 800; color: var(--ink,#1e293b); white-space: nowrap; }

/* teklif satırı */
.sta-tek {
  display: flex; align-items: center; gap: 8px; padding: 8px 10px; margin-bottom: 6px;
  background: var(--card,#fff); border: 1.5px solid var(--line,#e2e8f0); border-radius: 10px;
  cursor: pointer; transition: all .12s;
}
body.koyu .sta-tek { background: #1b2235; }
.sta-tek.oneri { border-color: var(--amber,#F59E0B); background: color-mix(in srgb,var(--amber) 9%,transparent); }
.sta-tek.secili { border-color: var(--yesil,#10B981); background: color-mix(in srgb,var(--yesil) 10%,transparent); }
.sta-tek .tk-yildiz { font-size: 17px; color: var(--sub2,#cbd5e1); flex-shrink: 0; cursor: pointer; }
.sta-tek .tk-yildiz.dolu { color: var(--amber,#F59E0B); }
.sta-tek .tk-ted { flex: 1; min-width: 0; }
.sta-tek .tk-ted .tk-ad { font-size: 13px; font-weight: 700; color: var(--ink,#1e293b); }
.sta-tek .tk-ted .tk-sub { font-size: 11px; color: var(--sub,#64748b); }
.sta-tek .tk-fiyat { font-size: 14px; font-weight: 800; color: var(--ink,#1e293b); white-space: nowrap; }
.sta-tek .tk-sil { color: var(--sub2,#94a3b8); font-size: 16px; flex-shrink: 0; }
.sta-tek .tk-ucuz { font-size: 9.5px; font-weight: 800; color: var(--yesil,#10B981); background: color-mix(in srgb,var(--yesil) 14%,transparent); padding: 1px 5px; border-radius: 6px; }

/* mini teklif ekleme formu */
.sta-tek-form { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
.sta-tek-form select, .sta-tek-form input {
  padding: 7px 9px; border: 1px solid var(--line,#e2e8f0); border-radius: 9px;
  background: var(--card,#fff); color: var(--ink,#1e293b); font-size: 12.5px; outline: none;
}
body.koyu .sta-tek-form select, body.koyu .sta-tek-form input { background: #1b2235; }
.sta-tek-form .tf-ted { flex: 2; min-width: 120px; }
.sta-tek-form .tf-fiyat { width: 90px; }
.sta-tek-form .tf-gun { width: 70px; }
.sta-tek-form .tf-ekle {
  border: none; background: var(--mor1,#7C3AED); color: #fff; border-radius: 9px;
  padding: 7px 12px; font-size: 12.5px; font-weight: 700; cursor: pointer;
}

/* gerekçe + patron not kutuları */
.sta-gerekce {
  margin-top: 8px; font-size: 12px; color: var(--ink,#1e293b);
  background: color-mix(in srgb,var(--amber) 8%,transparent); border-radius: 9px; padding: 7px 10px;
  display: flex; gap: 6px; align-items: flex-start;
}
.sta-gerekce .material-icons-outlined { font-size: 15px; color: var(--amber,#F59E0B); margin-top: 1px; }
.sta-gerekce textarea {
  width: 100%; border: 1px solid var(--line,#e2e8f0); border-radius: 8px; padding: 6px 8px;
  font-size: 12.5px; background: var(--card,#fff); color: var(--ink,#1e293b); resize: vertical; outline: none;
}
body.koyu .sta-gerekce textarea { background: #1b2235; }

/* aksiyon barı buton */
.sta-aks-btn {
  width: 100%; padding: 13px; border: none; border-radius: 13px; color: #fff;
  font-size: 15px; font-weight: 800; cursor: pointer; transition: opacity .15s;
  display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;
}
.sta-aks-btn:hover { opacity: .9; }
.sta-aks-btn:disabled { opacity: .5; cursor: not-allowed; }
.sta-aks-info { font-size: 12.5px; color: var(--sub,#64748b); text-align: center; margin-bottom: 10px; }

/* Spinner */
.sta-spin {
  display: inline-block; width: 18px; height: 18px;
  border: 2.5px solid rgba(255,255,255,.35); border-top-color: #fff;
  border-radius: 50%; animation: staSpin .7s linear infinite; vertical-align: middle;
}
@keyframes staSpin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('rk_araclar')
<button onclick="staYeniAc()"
    style="display:flex;align-items:center;gap:6px;padding:9px 16px;
           background:var(--mor1,#7C3AED);border:none;border-radius:11px;
           color:#fff;font-size:13.5px;font-weight:700;cursor:pointer">
    <span class="material-icons-outlined" style="font-size:18px">add</span>Yeni Talep
</button>
@endsection

@section('content')
<div id="sta">

{{-- ─── Özet rozet şeridi ─── --}}
<div class="sta-ozet-bar">
    {{-- Yeni --}}
    <div class="sta-rozet">
        <div class="sr-ikon-circ" style="background:color-mix(in srgb,#4F46E5 13%,transparent)">
            <span class="material-icons-outlined" style="color:#4F46E5">inbox</span>
        </div>
        <div>
            <div class="sr-sayi" id="staOzetYeni">{{ $ozet->yeni }}</div>
            <div class="sr-etiket">Yeni</div>
        </div>
    </div>
    {{-- Onayda --}}
    <div class="sta-rozet">
        <div class="sr-ikon-circ" style="background:color-mix(in srgb,#F59E0B 13%,transparent)">
            <span class="material-icons-outlined" style="color:#F59E0B">pending_actions</span>
        </div>
        <div>
            <div class="sr-sayi" id="staOzetOnayda">{{ $ozet->onayda }}</div>
            <div class="sr-etiket">Onayda</div>
        </div>
    </div>
    {{-- Tamamlanan --}}
    <div class="sta-rozet">
        <div class="sr-ikon-circ" style="background:color-mix(in srgb,#10B981 13%,transparent)">
            <span class="material-icons-outlined" style="color:#10B981">task_alt</span>
        </div>
        <div>
            <div class="sr-sayi" id="staOzetTamamlanan">{{ $ozet->tamamlanan }}</div>
            <div class="sr-etiket">Tamamlanan</div>
        </div>
    </div>
</div>

{{-- ─── Kritik stok bekçisi banner ─── --}}
@if (count($kritikler))
<div class="sta-kritik-banner">
    <div class="skb-sol">
        <span class="material-icons-outlined">warning_amber</span>
        <div>
            <div class="skb-bas">{{ count($kritikler) }} malzeme kritik seviyede</div>
            <div class="skb-alt">{{ implode(', ', array_slice(array_map(fn($k)=>$k['ad'], $kritikler), 0, 6)) }}{{ count($kritikler) > 6 ? '…' : '' }}</div>
        </div>
    </div>
    <button class="skb-btn" onclick="staKritiktenTalep()">
        <span class="material-icons-outlined" style="font-size:18px">playlist_add</span>Kritikten Talep Oluştur
    </button>
</div>
@endif

{{-- ─── Satın alma WhatsApp no ayarı (patron) ─── --}}
@if ($patronMu ?? false)
<div class="sta-wa-ayar">
    <span class="material-icons-outlined" style="color:#25D366">chat</span>
    <span style="font-size:12.5px;color:var(--sub);font-weight:600">Yeni talepte bildirim gidecek WhatsApp no:</span>
    <input id="staWaTel" type="tel" value="{{ $waTel }}" placeholder="5xxxxxxxxx" style="flex:1;min-width:120px;max-width:200px">
    <button onclick="staWaKaydet()" class="sta-wa-btn">Kaydet</button>
</div>
@endif

{{-- ─── Gelen kutusu listesi ─── --}}
<div class="sta-liste" id="staListe">
@forelse ($talepler as $talep)
    @php
        $renk = $durumRenk[$talep->durum] ?? '#64748b';
        $metin = $durumMetin[$talep->durum] ?? $talep->durum;
        $seritRenk = ($talep->aciliyet === 'acil') ? '#F43F5E' : '#4F46E5';
    @endphp
    <div class="sta-kart" onclick="staDetayAc({{ $talep->id }})">
        <div class="sta-serit" style="background:{{ $seritRenk }}"></div>
        <div class="sta-bilgi">
            <div class="sta-dept">
                <span class="material-icons-outlined" style="font-size:17px;color:{{ $seritRenk }}">{{ $talep->aciliyet === 'acil' ? 'priority_high' : 'storefront' }}</span>
                {{ $talep->departman }}
                @if ($talep->aciliyet === 'acil')
                    <span class="roz acil">ACİL</span>
                @endif
            </div>
            <div class="sta-alt">
                <span>{{ $talep->isteyen_ad }}</span>
                &nbsp;·&nbsp;
                <span>{{ \Carbon\Carbon::parse($talep->created_at)->diffForHumans() }}</span>
            </div>
        </div>
        <div class="sta-meta">
            <span class="sta-durum-roz" style="background:{{ $renk }}">{{ $metin }}</span>
            <span class="sta-kalem-cnt">
                <span class="material-icons-outlined">format_list_bulleted</span>
                {{ $talep->kalem_sayisi ?? 0 }} kalem
            </span>
        </div>
        <span class="material-icons-outlined" style="font-size:20px;color:var(--sub2);flex-shrink:0">chevron_right</span>
    </div>
@empty
    <div class="sta-bos">
        <span class="material-icons-outlined">assignment</span>
        <p>Henüz talep yok.</p>
    </div>
@endforelse
</div>

</div>{{-- #sta --}}

{{-- ═══════════════════════════════════════════════
     DETAY MODAL
═══════════════════════════════════════════════ --}}
<div class="sta-mbg merkez" id="staDetModal">
  <div class="sta-modal">
    <div class="sta-cizgi"></div>
    <div class="sta-mod-baslik" id="staDetBaslik">
        <span class="material-icons-outlined">assignment</span>
        <span id="staDetDept">Talep Detayı</span>
    </div>

    {{-- Özet satırı --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
        <span id="staDetAcilRoz" class="roz normal">Normal</span>
        <span id="staDetDurumRoz" class="sta-durum-roz" style="background:#4F46E5">Yeni</span>
        <span id="staDetIsteyenAlt" style="font-size:12.5px;color:var(--sub,#64748b)"></span>
    </div>

    {{-- Not --}}
    <div class="sta-not-kutu" id="staDetNotKutu" style="display:none">
        <span class="material-icons-outlined">sticky_note_2</span>
        <span id="staDetNot"></span>
    </div>

    {{-- Akış adımı şeridi --}}
    <div class="sta-akis" id="staDetAkis"></div>

    {{-- Kalem listesi (RFQ + öneri + seçim) --}}
    <div style="font-size:12px;font-weight:700;color:var(--sub,#64748b);letter-spacing:.3px;text-transform:uppercase;margin-bottom:8px">Kalemler · Teklifler</div>
    <div id="staDetKalemler" style="margin-bottom:14px"></div>

    {{-- Dinamik aksiyon barı (role + duruma göre) --}}
    <div id="staDetAksiyon" style="margin-bottom:10px"></div>

    <button class="sta-kaydet-btn" style="background:var(--sub,#64748b)" onclick="staDetKapat()">
        Kapat
    </button>
  </div>
</div>

{{-- ═══════════════════════════════════════════════
     YENİ TALEP MODAL
═══════════════════════════════════════════════ --}}
<div class="sta-mbg" id="staYeniModal">
  <div class="sta-modal">
    <div class="sta-cizgi"></div>
    <div class="sta-mod-baslik">
        <span class="material-icons-outlined" style="color:var(--mor1,#7C3AED)">add_shopping_cart</span>
        Yeni Satın Alma Talebi
    </div>

    <div class="sta-row">
        <div class="sta-field">
            <label>Departman</label>
            <select id="staDept">
                <option value="Mutfak">Mutfak</option>
                <option value="Bar">Bar</option>
                <option value="İK">İK</option>
                <option value="Temizlik">Temizlik</option>
                <option value="Diğer">Diğer</option>
            </select>
        </div>
        <div class="sta-field" style="flex:none;width:140px">
            <label>Aciliyet</label>
            <div class="sta-acil-toggle" style="margin-bottom:0">
                <button id="staBtnNormal" class="secili-normal"
                    onclick="staAcilSec('normal')">
                    <span class="material-icons-outlined" style="font-size:16px">schedule</span>Normal
                </button>
                <button id="staBtnAcil"
                    onclick="staAcilSec('acil')">
                    <span class="material-icons-outlined" style="font-size:16px">priority_high</span>Acil
                </button>
            </div>
        </div>
    </div>

    <div class="sta-field">
        <label>Not / Açıklama (opsiyonel)</label>
        <textarea id="staNot" rows="2" placeholder="Ör. Bu hafta içinde gerekli, stok kritik…" style="resize:none"></textarea>
    </div>

    {{-- ---- Kalemler ---- --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <span style="font-size:12px;font-weight:700;color:var(--sub,#64748b);letter-spacing:.3px;text-transform:uppercase">Malzeme Kalemleri</span>
        <span style="font-size:11px;color:var(--sub2,#94a3b8)">En az 1 kalem</span>
    </div>
    <div id="staKalemler"></div>

    <button class="sta-satir-ekle" onclick="staKalemEkle()">
        <span class="material-icons-outlined" style="font-size:18px">add</span>Satır Ekle
    </button>

    <div style="display:flex;gap:8px">
        <button onclick="staYeniKapat()"
            style="flex:none;padding:14px 20px;border-radius:13px;border:1.5px solid var(--line,#e2e8f0);
                   background:transparent;color:var(--sub,#64748b);font-size:14px;font-weight:700;cursor:pointer">
            İptal
        </button>
        <button class="sta-kaydet-btn" id="staKaydetBtn" onclick="staTalepKaydet()" style="flex:1">
            Talebi Gönder
        </button>
    </div>
  </div>
</div>

<div id="staTst"></div>

@push('scripts')
<script>
(function(){
/* ─── Veri ─────────────────────────────────────────────── */
const malzemeler = @json($malzemeler->values());
const birimler   = @json($birimler->values());
const patronMu   = {{ ($patronMu ?? false) ? 'true' : 'false' }};
const staKritikler = @json($kritikler);
let staTedarikciler = [];
let staAktifKalemler = [];
let staAktifDurum = null;
const durumRenkler = {
    yeni:'#4F46E5', teklif:'#F59E0B', onayda:'#F59E0B',
    onaylandi:'#10B981', siparis:'#0EA5E9', tamamlandi:'#10B981'
};
const durumMetinler = {
    yeni:'Yeni', teklif:'Teklif Bekleniyor', onayda:'Onayda',
    onaylandi:'Onaylandı', siparis:'Siparişte', tamamlandi:'Tamamlandı'
};

let seciliAciliyet = 'normal';
let aktifTalepId   = null;

/* ─── Yardımcılar ──────────────────────────────────────── */
function toast(msg, renk) {
    const el = document.getElementById('staTst');
    el.textContent = msg;
    el.style.background = renk || '';
    el.classList.add('goruli');
    setTimeout(() => el.classList.remove('goruli'), 2800);
}

/* ─── Yeni talep modal ─────────────────────────────────── */
window.staYeniAc = function() {
    seciliAciliyet = 'normal';
    staAcilSec('normal');
    document.getElementById('staDept').value = 'Mutfak';
    document.getElementById('staNot').value = '';
    document.getElementById('staKalemler').innerHTML = '';
    staKalemEkle();
    document.getElementById('staYeniModal').classList.add('acik');
};
window.staYeniKapat = function() {
    document.getElementById('staYeniModal').classList.remove('acik');
};

/* ─── Kritikten tek-tık talep (bekçi) ──────────────────── */
window.staKritiktenTalep = function() {
    if (!staKritikler.length) { toast('Kritik malzeme yok'); return; }
    seciliAciliyet = 'acil';
    staAcilSec('acil');
    document.getElementById('staDept').value = 'Mutfak';
    document.getElementById('staNot').value = 'Kritik stok — otomatik oluşturuldu.';
    document.getElementById('staKalemler').innerHTML = '';
    staKritikler.forEach(k => staKalemEkle(k.id, k.oneri, k.birim));
    document.getElementById('staYeniModal').classList.add('acik');
    toast('⚠ ' + staKritikler.length + ' kritik kalem forma eklendi', '#F59E0B');
};

/* ─── Satın alma WA no kaydet ──────────────────────────── */
window.staWaKaydet = async function() {
    const tel = document.getElementById('staWaTel').value.trim();
    const res = await api('/satinalma-talep/wa-tel-kaydet', { tel });
    if (res.ok) toast('✓ WhatsApp no kaydedildi', '#25D366');
};

window.staAcilSec = function(v) {
    seciliAciliyet = v;
    document.getElementById('staBtnNormal').className = (v === 'normal') ? 'secili-normal' : '';
    document.getElementById('staBtnAcil').className   = (v === 'acil')   ? 'secili-acil'   : '';
};

/* ─── Malzeme dropdown HTML ────────────────────────────── */
function malzemeDropdown(idx) {
    let html = `<option value="">— Malzeme seç —</option>`;
    malzemeler.forEach(m => {
        html += `<option value="${m.id}" data-ad="${escAttr(m.ad)}" data-birim="${escAttr(m.birim_kisaltma||m.temel_birim_kisaltma||'')}">${escHtml(m.ad)}</option>`;
    });
    return html;
}
function birimDropdown(secilenBirim) {
    let html = '';
    birimler.forEach(b => {
        const sel = (b.kisaltma === secilenBirim) ? ' selected' : '';
        html += `<option value="${escAttr(b.kisaltma)}"${sel}>${escHtml(b.kisaltma)}</option>`;
    });
    return html;
}

/* ─── Kalem satırı ekle ────────────────────────────────── */
window.staKalemEkle = function(malzeme_id, miktar, birim) {
    const wrap = document.getElementById('staKalemler');
    const idx  = wrap.children.length;
    const row  = document.createElement('div');
    row.className = 'sta-kalem-satiri';
    row.dataset.idx = idx;
    row.innerHTML = `
      <div class="sta-field" style="flex:2">
        <label>Malzeme</label>
        <select class="sta-mlz-sel" onchange="staMalzemeSec(this)">${malzemeDropdown(idx)}</select>
      </div>
      <div class="sta-field" style="flex:none;width:88px">
        <label>Miktar</label>
        <input type="number" class="sta-mik-inp" step="0.001" min="0.001" placeholder="0" value="${miktar||''}">
      </div>
      <div class="sta-field sta-field-birim">
        <label>Birim</label>
        <select class="sta-birim-sel">${birimDropdown(birim||'')}</select>
      </div>
      <button class="sta-sil-satir" onclick="staSatirSil(this)" title="Satırı sil">
        <span class="material-icons-outlined">close</span>
      </button>`;
    // Eğer malzeme_id verilmişse seç
    if (malzeme_id) {
        const sel = row.querySelector('.sta-mlz-sel');
        sel.value = malzeme_id;
        staMalzemeSec(sel);
    }
    wrap.appendChild(row);
};

window.staMalzemeSec = function(sel) {
    const opt = sel.options[sel.selectedIndex];
    const birim = opt ? (opt.dataset.birim || '') : '';
    const row = sel.closest('.sta-kalem-satiri');
    if (!row) return;
    const birimSel = row.querySelector('.sta-birim-sel');
    if (birimSel && birim) {
        for (let i = 0; i < birimSel.options.length; i++) {
            if (birimSel.options[i].value === birim) { birimSel.selectedIndex = i; break; }
        }
    }
};

window.staSatirSil = function(btn) {
    const row = btn.closest('.sta-kalem-satiri');
    if (row) row.remove();
};

/* ─── Talebi kaydet ────────────────────────────────────── */
window.staTalepKaydet = async function() {
    const dept = document.getElementById('staDept').value.trim();
    const not  = document.getElementById('staNot').value.trim();
    const satirlar = Array.from(document.querySelectorAll('#staKalemler .sta-kalem-satiri'));

    const kalemler = [];
    let hata = null;
    satirlar.forEach((row, i) => {
        const mlzSel = row.querySelector('.sta-mlz-sel');
        const mikInp = row.querySelector('.sta-mik-inp');
        const birimSel = row.querySelector('.sta-birim-sel');
        const mlzId = mlzSel ? mlzSel.value : '';
        const mlzAd = mlzSel ? (mlzSel.options[mlzSel.selectedIndex]?.dataset?.ad || '') : '';
        const miktar = parseFloat(mikInp ? mikInp.value : 0);
        const birim  = birimSel ? birimSel.value : '';
        if (!mlzId) { hata = `${i+1}. satırda malzeme seçilmedi.`; return; }
        if (!miktar || miktar <= 0) { hata = `${i+1}. satırda geçerli miktar girin.`; return; }
        kalemler.push({ malzeme_id: parseInt(mlzId), ad: mlzAd, miktar, birim });
    });

    if (kalemler.length === 0 && !hata) hata = 'En az bir kalem ekleyin.';
    if (hata) { toast('⚠ ' + hata, '#F43F5E'); return; }

    const btn = document.getElementById('staKaydetBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="sta-spin"></span> Gönderiliyor…';

    try {
        const res = await api('/satinalma-talep/olustur', {
            departman: dept,
            aciliyet: seciliAciliyet,
            not: not || null,
            kalemler
        });
        if (res.ok) {
            toast('✓ Talep oluşturuldu', '#10B981');
            staYeniKapat();
            setTimeout(() => location.reload(), 800);
        } else {
            toast('⚠ ' + (res.mesaj || 'Hata oluştu'), '#F43F5E');
        }
    } catch(e) {
        toast('⚠ Bağlantı hatası', '#F43F5E');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Talebi Gönder';
    }
};

/* ─── Detay modal ──────────────────────────────────────── */
window.staDetayAc = async function(id) {
    aktifTalepId = id;
    document.getElementById('staDetDept').textContent = 'Yükleniyor…';
    document.getElementById('staDetKalemler').innerHTML =
        '<div style="text-align:center;padding:24px;color:var(--sub)"><span class="sta-spin" style="border-color:rgba(100,116,139,.3);border-top-color:var(--mor1,#7C3AED)"></span></div>';
    document.getElementById('staDetModal').classList.add('acik');

    try {
        const res = await api('/api/patron/talep-detay?id=' + id);
        if (!res || !res.talep) { toast('⚠ Talep bulunamadı', '#F43F5E'); staDetKapat(); return; }
        const t = res.talep;
        const kalemler = res.kalemler || [];

        document.getElementById('staDetDept').textContent = t.departman + ' Talebi';
        document.getElementById('staDetIsteyenAlt').textContent = (t.isteyen_ad || '') + '  ·  ' + staRelative(t.created_at);

        const acilRoz = document.getElementById('staDetAcilRoz');
        acilRoz.textContent = t.aciliyet === 'acil' ? 'ACİL' : 'Normal';
        acilRoz.className = 'roz ' + (t.aciliyet === 'acil' ? 'acil' : 'normal');

        const durumRoz = document.getElementById('staDetDurumRoz');
        durumRoz.textContent = durumMetinler[t.durum] || t.durum;
        durumRoz.style.background = durumRenkler[t.durum] || '#64748b';

        const notKutu = document.getElementById('staDetNotKutu');
        if (t.not) {
            document.getElementById('staDetNot').textContent = t.not;
            notKutu.style.display = 'flex';
        } else {
            notKutu.style.display = 'none';
        }

        staTedarikciler = res.tedarikciler || [];
        staAktifKalemler = kalemler;
        staAktifDurum = t.durum;

        staAkisCiz(t.durum);
        staKalemleriCiz(kalemler, t.durum);
        staAksiyonCiz(t.durum);

    } catch(e) {
        toast('⚠ Bağlantı hatası', '#F43F5E');
        staDetKapat();
    }
};

/* ─── Akış şeridi ──────────────────────────────────────── */
const AKIS = [
    {k:'yeni', ad:'Talep', ik:'assignment'},
    {k:'teklif', ad:'Teklif', ik:'request_quote'},
    {k:'onayda', ad:'Onayda', ik:'rule'},
    {k:'onaylandi', ad:'Onaylandı', ik:'check_circle'},
    {k:'siparis', ad:'Sipariş', ik:'shopping_cart_checkout'},
];
function staAkisCiz(durum) {
    const sira = AKIS.map(a=>a.k);
    let aktifIdx = sira.indexOf(durum);
    if (durum === 'tamamlandi') aktifIdx = sira.length;
    let html = '';
    AKIS.forEach((a, i) => {
        let cls = '';
        if (i < aktifIdx) cls = 'tamam';
        else if (i === aktifIdx) cls = 'aktif';
        html += `<span class="sa-adim ${cls}"><span class="material-icons-outlined">${i<aktifIdx?'check':a.ik}</span>${a.ad}</span>`;
        if (i < AKIS.length-1) html += `<span class="sa-ok">›</span>`;
    });
    document.getElementById('staDetAkis').innerHTML = html;
}

/* ─── Tedarikçi dropdown ───────────────────────────────── */
function staTedDropdown(secili) {
    let h = `<option value="">— Tedarikçi —</option>`;
    staTedarikciler.forEach(t => {
        h += `<option value="${t.id}"${(secili==t.id)?' selected':''}>${escHtml(t.ad)}</option>`;
    });
    return h;
}

/* ─── Kalem + teklif kartlarını çiz ────────────────────── */
function staKalemleriCiz(kalemler, durum) {
    const wrap = document.getElementById('staDetKalemler');
    if (!kalemler.length) { wrap.innerHTML = '<div style="color:var(--sub);font-size:13px;padding:8px 0">Kalem yok.</div>'; return; }
    // satın almacı teklif/öneri girebilir mi? (onaylandı/sipariş sonrası kilit)
    const duzenle = ['yeni','teklif','onayda'].includes(durum);
    const patronSecebilir = patronMu && durum === 'onayda';
    let html = '';
    kalemler.forEach(k => {
        const teklifler = k.teklifler || [];
        const enUcuz = teklifler.length ? Math.min(...teklifler.map(x=>parseFloat(x.fiyat)||Infinity)) : null;
        html += `<div class="sta-kk" data-kid="${k.id}">
          <div class="kk-bas">
            <span class="material-icons-outlined">inventory_2</span>
            <span class="kk-ad">${escHtml(k.ad)}</span>
            <span class="kk-mik">${numFmt(k.miktar)} ${escHtml(k.birim||'')}</span>
          </div>`;
        // teklifler
        if (teklifler.length) {
            teklifler.forEach(tk => {
                const isOneri = (k.onerilen_tedarikci_id && tk.tedarikci_id == k.onerilen_tedarikci_id);
                const isSecili = (k.secilen_tedarikci_id && tk.tedarikci_id == k.secilen_tedarikci_id);
                const ucuz = (enUcuz !== null && parseFloat(tk.fiyat) === enUcuz);
                let cls = 'sta-tek'; if (isSecili) cls += ' secili'; else if (isOneri) cls += ' oneri';
                // yıldız: satın almacı düzenleyebilirse tıklanır
                const yildiz = duzenle
                    ? `<span class="material-icons-outlined tk-yildiz ${isOneri?'dolu':''}" onclick="event.stopPropagation();staOneriSec(${k.id},${tk.tedarikci_id})" title="Önerim">${isOneri?'star':'star_border'}</span>`
                    : (isOneri ? `<span class="material-icons-outlined tk-yildiz dolu">star</span>` : '');
                const altlar = [];
                if (tk.vade) altlar.push('Vade: '+escHtml(tk.vade));
                if (tk.teslim_gun) altlar.push(tk.teslim_gun+' gün');
                html += `<div class="${cls}" ${patronSecebilir?`onclick="staPatronSec(${k.id},${tk.tedarikci_id})"`:''} style="${patronSecebilir?'cursor:pointer':''}">
                    ${yildiz}
                    <div class="tk-ted">
                      <div class="tk-ad">${escHtml(tk.tedarikci_ad)} ${ucuz?'<span class="tk-ucuz">EN UCUZ</span>':''} ${isSecili?'<span class="tk-ucuz" style="color:#10B981;background:color-mix(in srgb,#10B981 14%,transparent)">SEÇİLDİ</span>':''}</div>
                      ${altlar.length?`<div class="tk-sub">${altlar.join(' · ')}</div>`:''}
                    </div>
                    <div class="tk-fiyat">${numFmt(tk.fiyat)} ₺</div>
                    ${duzenle?`<span class="material-icons-outlined tk-sil" onclick="event.stopPropagation();staTeklifSil(${tk.id})" title="Sil">close</span>`:''}
                  </div>`;
            });
        } else if (!duzenle) {
            html += `<div style="font-size:12px;color:var(--sub);padding:4px 0">Teklif girilmedi.</div>`;
        }
        // teklif ekleme formu (satın almacı, düzenlenebilir durumda)
        if (duzenle) {
            html += `<div class="sta-tek-form">
                <select class="tf-ted">${staTedDropdown()}</select>
                <input class="tf-fiyat" type="number" step="0.01" min="0" placeholder="Fiyat ₺">
                <input class="tf-gun" type="number" min="0" placeholder="gün">
                <input class="tf-vade" style="width:90px" placeholder="vade">
                <button class="tf-ekle" onclick="staTeklifEkle(${k.id}, this)">+ Teklif</button>
              </div>`;
            // ⭐ öneri gerekçesi
            html += `<div class="sta-gerekce">
                <span class="material-icons-outlined">tips_and_updates</span>
                <textarea rows="1" placeholder="Öneri gerekçesi (neden bu tedarikçi?)" onchange="staGerekceKaydet(${k.id}, this.value)">${escHtml(k.oneri_gerekce||'')}</textarea>
              </div>`;
        } else if (k.oneri_gerekce) {
            html += `<div class="sta-gerekce"><span class="material-icons-outlined">tips_and_updates</span><div><b>Satın almacı:</b> ${escHtml(k.oneri_gerekce)}</div></div>`;
        }
        if (k.patron_not) {
            html += `<div class="sta-gerekce" style="background:color-mix(in srgb,#10B981 8%,transparent)"><span class="material-icons-outlined" style="color:#10B981">verified</span><div><b>Yönetici:</b> ${escHtml(k.patron_not)}</div></div>`;
        }
        html += `</div>`;
    });
    wrap.innerHTML = html;
}

/* ─── Aksiyon barı (durum + role göre) ─────────────────── */
function staAksiyonCiz(durum) {
    const box = document.getElementById('staDetAksiyon');
    let html = '';
    if (['yeni','teklif','onayda'].includes(durum)) {
        // satın almacı: patrona gönder
        const notu = durum==='onayda' ? 'Patron onayı bekleniyor. Teklif/öneri hâlâ düzenlenebilir.' : 'Fiyat Araştır ile tüm tedarikçilere teklif iste (AI yanıtları buraya düşürür), ya da elle teklif gir → ⭐ ile öner → Patrona Gönder.';
        html += `<div class="sta-aks-info">${notu}</div>`;
        if (durum !== 'onayda') {
            html += `<button class="sta-aks-btn" style="background:#7C3AED" onclick="staFiyatArastir()"><span class="material-icons-outlined">request_quote</span>Fiyat Araştır (tüm tedarikçilere)</button>`;
            html += `<button class="sta-aks-btn" style="background:#F59E0B" onclick="staPatronaGonder()"><span class="material-icons-outlined">send</span>Patrona Gönder (Onaya)</button>`;
        }
        // Anlaşmalı tedarikçiye ⭐ önerilen + fiyatı olan kalemler → patron onayı atlanır, direkt sipariş + tedarikçiye WhatsApp
        html += `<button class="sta-aks-btn" style="background:#0EA5E9" onclick="staAnlasmaliDirekt()"><span class="material-icons-outlined">bolt</span>Anlaşmalıdan Direkt Sipariş</button>`;
        html += `<div class="sta-aks-info" style="font-size:11px;opacity:.75">⚡ <b>Anlaşmalı</b> tedarikçiye ⭐ önerdiğin + fiyatını girdiğin kalemler, patron onayı beklemeden direkt siparişe döner ve tedarikçiye WhatsApp sipariş gider.</div>`;
    }
    if (durum === 'onayda' && patronMu) {
        html += `<div class="sta-aks-info">Önerileri incele. İstersen bir teklife tıklayıp değiştir, sonra onayla.</div>`;
        html += `<button class="sta-aks-btn" style="background:#10B981" onclick="staOnayla()"><span class="material-icons-outlined">check_circle</span>Onayla (seçimleri kesinleştir)</button>`;
    }
    if (durum === 'onaylandi') {
        html += `<div class="sta-aks-info">Onaylandı. Satın almacı tedarikçiye-gruplu sipariş oluşturabilir.</div>`;
        html += `<button class="sta-aks-btn" style="background:#0EA5E9" onclick="staPOOlustur()"><span class="material-icons-outlined">shopping_cart_checkout</span>Sipariş Oluştur (PO)</button>`;
    }
    if (durum === 'siparis') {
        html += `<div class="sta-aks-info">✓ Sipariş oluşturuldu. Mal gelince <b>Mal Kabul</b> ekranından karşılaştır.</div>`;
        html += `<button class="sta-aks-btn" style="background:#10B981" onclick="staDurumGuncelle('tamamlandi')"><span class="material-icons-outlined">task_alt</span>Tamamlandı İşaretle</button>`;
    }
    box.innerHTML = html;
}

/* ─── Teklif ekle / sil ────────────────────────────────── */
window.staTeklifEkle = async function(kalemId, btn) {
    const form = btn.closest('.sta-tek-form');
    const tedId = form.querySelector('.tf-ted').value;
    const fiyat = form.querySelector('.tf-fiyat').value;
    const gun   = form.querySelector('.tf-gun').value;
    const vade  = form.querySelector('.tf-vade').value;
    if (!tedId) { toast('⚠ Tedarikçi seç', '#F43F5E'); return; }
    if (!fiyat || parseFloat(fiyat) <= 0) { toast('⚠ Fiyat gir', '#F43F5E'); return; }
    const res = await api('/satinalma-talep/teklif-ekle', { talep_kalem_id: kalemId, tedarikci_id: tedId, fiyat, teslim_gun: gun||null, vade: vade||null });
    if (res.ok) { toast('✓ Teklif eklendi', '#10B981'); staDetayAc(aktifTalepId); }
    else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
};
window.staTeklifSil = async function(teklifId) {
    const res = await api('/satinalma-talep/teklif-sil', { teklif_id: teklifId });
    if (res.ok) { toast('Teklif silindi'); staDetayAc(aktifTalepId); }
};

/* ─── ⭐ Öneri seç (satın almacı) ──────────────────────── */
window.staOneriSec = async function(kalemId, tedId) {
    const res = await api('/satinalma-talep/oneri-kaydet', { talep_kalem_id: kalemId, onerilen_tedarikci_id: tedId });
    if (res.ok) { toast('⭐ Önerin kaydedildi', '#F59E0B'); staDetayAc(aktifTalepId); }
    else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
};
window.staGerekceKaydet = async function(kalemId, metin) {
    // mevcut öneriyi koru, sadece gerekçeyi güncelle
    const k = staAktifKalemler.find(x => x.id == kalemId);
    await api('/satinalma-talep/oneri-kaydet', { talep_kalem_id: kalemId, onerilen_tedarikci_id: (k && k.onerilen_tedarikci_id) || null, oneri_gerekce: metin });
    toast('✓ Gerekçe kaydedildi', '#10B981');
};

/* ─── Patron: tedarikçi seç (öneriyi onayla/override) ──── */
window.staPatronSec = async function(kalemId, tedId) {
    const res = await api('/satinalma-talep/patron-sec', { talep_kalem_id: kalemId, secilen_tedarikci_id: tedId });
    if (res.ok) { toast('✓ Seçim kaydedildi', '#10B981'); staDetayAc(aktifTalepId); }
    else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
};

/* ─── Patrona gönder / onayla / PO ─────────────────────── */
window.staFiyatArastir = function() {
    const git = async () => {
        toast('Fiyat isteği gönderiliyor…', '#7C3AED');
        const res = await api('/satinalma-talep/fiyat-arastir', { id: aktifTalepId });
        if (res.ok) { toast(`✓ ${res.gonderilen} tedarikçiye fiyat isteği gönderildi (mail ${res.mail}, WhatsApp ${res.wa}). Yanıtlar geldikçe buraya düşecek.`, '#10B981'); staDetayAc(aktifTalepId); }
        else toast('⚠ ' + (res.hata || 'Gönderilemedi'), '#F43F5E');
    };
    if (typeof rkOnay === 'function') rkOnay('Bu talepteki ürünleri sağlayan TÜM tedarikçilere fiyat isteği (mail + WhatsApp) gönderilsin mi?', git, 'var(--mor1)', 'Evet, gönder');
    else git();
};
window.staPatronaGonder = async function() {
    const res = await api('/satinalma-talep/patrona-gonder', { id: aktifTalepId });
    if (res.ok) { toast('✓ Patrona gönderildi', '#10B981'); staDetListeYenile('onayda'); staDetayAc(aktifTalepId); }
    else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
};
window.staOnayla = async function() {
    const res = await api('/satinalma-talep/onayla', { id: aktifTalepId });
    if (res.ok) { toast('✓ Onaylandı', '#10B981'); staDetListeYenile('onaylandi'); staDetayAc(aktifTalepId); }
    else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
};
window.staPOOlustur = function() {
    rkOnay('Onaylı tedarikçilere satın alma siparişi (PO) oluşturulsun mu? Telefonu olan tedarikçilere WhatsApp sipariş gider.', async function(){
        const res = await api('/satinalma-talep/po-olustur', { id: aktifTalepId });
        if (res.ok) { const wa = res.wa ? ` · ${res.wa} tedarikçiye WhatsApp gitti` : ''; toast('✓ Sipariş oluşturuldu: ' + (res.siparisler||[]).join(', ') + wa, '#10B981'); staDetListeYenile('siparis'); staDetayAc(aktifTalepId); }
        else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
    }, 'var(--mor1)', 'Oluştur');
};
window.staAnlasmaliDirekt = function() {
    rkOnay('Anlaşmalı tedarikçiye önerdiğin + fiyatı girili kalemler patron onayı OLMADAN direkt siparişe dönsün mü? Tedarikçiye WhatsApp sipariş gider.', async function(){
        const res = await api('/satinalma-talep/anlasmali-direkt', { id: aktifTalepId });
        if (res.ok) {
            const wa = res.wa ? ` · ${res.wa} WhatsApp` : '';
            const kalan = res.kalan ? ` · ${res.kalan} kalem onaya kaldı` : '';
            toast('✓ Direkt sipariş: ' + (res.siparisler||[]).join(', ') + wa + kalan, '#10B981');
            staDetListeYenile(res.kalan ? 'teklif' : 'siparis'); staDetayAc(aktifTalepId);
        } else toast('⚠ ' + (res.mesaj||'Hata'), '#F43F5E');
    }, 'var(--mor1)', 'Onaylı');
};

function staDetListeYenile(yeniDurum) {
    const kart = document.querySelector(`#staListe .sta-kart[onclick*="(${aktifTalepId})"]`);
    if (kart) {
        const rz = kart.querySelector('.sta-durum-roz');
        if (rz) { rz.textContent = durumMetinler[yeniDurum] || yeniDurum; rz.style.background = durumRenkler[yeniDurum] || '#64748b'; }
    }
}

window.staDetKapat = function() {
    document.getElementById('staDetModal').classList.remove('acik');
    aktifTalepId = null;
};

/* ─── Durum güncelle ───────────────────────────────────── */
window.staDurumGuncelle = async function(yeniDurum) {
    if (!aktifTalepId) return;
    try {
        const res = await api('/satinalma-talep/durum-guncelle', {
            id: aktifTalepId,
            durum: yeniDurum
        });
        if (res.ok) {
            toast('✓ Durum güncellendi', '#10B981');
            const roz = document.getElementById('staDetDurumRoz');
            roz.textContent = durumMetinler[yeniDurum] || yeniDurum;
            roz.style.background = durumRenkler[yeniDurum] || '#64748b';
            // Listede de güncelle
            const kart = document.querySelector(`#staListe .sta-kart[onclick*="${aktifTalepId}"]`);
            if (kart) {
                const rz = kart.querySelector('.sta-durum-roz');
                if (rz) { rz.textContent = durumMetinler[yeniDurum] || yeniDurum; rz.style.background = durumRenkler[yeniDurum] || '#64748b'; }
            }
        } else {
            toast('⚠ ' + (res.mesaj || 'Hata'), '#F43F5E');
        }
    } catch(e) {
        toast('⚠ Bağlantı hatası', '#F43F5E');
    }
};

/* ─── Overlay tıkla → kapat ────────────────────────────── */
document.getElementById('staDetModal').addEventListener('click', function(e) {
    if (e.target === this) staDetKapat();
});
document.getElementById('staYeniModal').addEventListener('click', function(e) {
    if (e.target === this) staYeniKapat();
});

/* ─── Yardımcı: tarih ──────────────────────────────────── */
function staRelative(ts) {
    if (!ts) return '';
    const d = new Date(ts.replace(' ', 'T'));
    const diff = Math.floor((Date.now() - d.getTime()) / 1000);
    if (diff < 60)  return 'az önce';
    if (diff < 3600) return Math.floor(diff/60) + ' dk önce';
    if (diff < 86400) return Math.floor(diff/3600) + ' sa önce';
    return Math.floor(diff/86400) + ' gün önce';
}
function numFmt(v) {
    v = parseFloat(v);
    if (isNaN(v)) return '0';
    return v === Math.round(v) ? v.toLocaleString('tr-TR') : v.toLocaleString('tr-TR', {minimumFractionDigits:0,maximumFractionDigits:3});
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) {
    return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

})();
</script>
@endpush
@endsection

@extends('layout.app')
@section('title', 'Masa ' . $masa->ad)
@section('baslik', 'Masa ' . $masa->ad)

@section('content')
{{-- ========================================================
     POS Adisyon — Flutter satis_ekrani.dart birebir tasarım
     Mor #7C3AED · Mavi #4F46E5 · Yeşil #10B981 · Kırmızı #F43F5E · Turuncu #F59E0B
     ======================================================== --}}

<style>
/* ---- Renk değişkenleri ---- */
:root {
  --mor1:#7C3AED; --mavi:#4F46E5; --yesil:#10B981; --kirmizi:#F43F5E; --turuncu:#F59E0B;
  --kart:#fff; --kart2:#f1f5f9; --zemin:#f8fafc;
  --ink:#1e293b; --sub:#64748b; --sub2:#94a3b8; --cizgi:#e2e8f0;
}
body.koyu {
  --kart:#1b2235; --kart2:#242f47; --zemin:#151d30;
  --ink:#e2e8f0; --sub:#94a3b8; --sub2:#64748b; --cizgi:#2c3650;
}
/* ---- Layout (GRID: menü sütunu minmax(0,1fr) garantili küçülür, panel sabit 360 → taşma yok) ---- */
#posWrap { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:16px; height:calc(100vh - 112px); min-height:500px; max-width:100%; }
#posMenu { min-width:0; display:flex; flex-direction:column; background:var(--zemin); border-radius:16px; border:1px solid var(--cizgi); overflow:hidden; }
#posPanel { min-width:0; display:flex; flex-direction:column; background:var(--kart); border-radius:16px; border:1px solid var(--cizgi); overflow:hidden; }
@media(max-width:900px){ #posWrap{grid-template-columns:1fr;height:auto;} #posPanel{margin-top:12px;} #posMenu{min-height:400px;} }
/* ---- Arama ---- */
#posAra { width:100%; padding:10px 14px; box-sizing:border-box; border:none; border-bottom:1px solid var(--cizgi); background:var(--kart); color:var(--ink); font-size:14px; outline:none; }
#posAra::placeholder { color:var(--sub2); }
/* ---- Kategori çipleri ---- */
#posKatBar { display:flex; gap:9px; padding:10px 14px; overflow-x:auto; scrollbar-width:none; border-bottom:1px solid var(--cizgi); background:var(--kart); }
#posKatBar::-webkit-scrollbar{display:none}
.pos-kat { padding:7px 18px; border-radius:23px; font-size:13.5px; font-weight:700; cursor:pointer; border:1.3px solid var(--cizgi); color:var(--ink); background:var(--kart); white-space:nowrap; transition:all .15s; }
.pos-kat.aktif { background:linear-gradient(135deg,var(--mor1),var(--mavi)); color:#fff; border-color:transparent; box-shadow:0 5px 14px rgba(124,58,237,.4); }
/* ---- Ürün ızgarası ---- */
#posUrunGrid { flex:1; overflow-y:auto; padding:12px; display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; align-content:start; }
.pos-urun { background:var(--kart); border:1px solid var(--cizgi); border-radius:14px; overflow:hidden; cursor:pointer; transition:border-color .1s; }
.pos-urun:hover:not(.tukendi) { border-color:var(--mor1); }
.pos-urun.tukendi { opacity:.4; cursor:not-allowed; }
.pos-urun-img { position:relative; width:100%; height:96px; background:var(--kart2); display:flex; align-items:center; justify-content:center; overflow:hidden; }
.pos-urun-img img { width:100%; height:100%; object-fit:cover; }
.pos-urun-rozet { position:absolute; top:6px; right:6px; width:26px; height:26px; border-radius:50%; background:var(--mor1); color:#fff; font-size:12px; font-weight:bold; display:flex; align-items:center; justify-content:center; }
.pos-urun-tukendi-badge { position:absolute; bottom:6px; left:6px; background:var(--kirmizi); color:#fff; font-size:9px; font-weight:700; padding:2px 6px; border-radius:6px; }
.pos-urun-info { padding:8px 10px 10px; }
.pos-urun-ad { color:var(--ink); font-size:12.5px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pos-urun-fiyat { color:var(--yesil); font-size:13.5px; font-weight:700; margin-top:2px; }
/* ---- Panel başlık (gradient) ---- */
#posPanelBaslik { padding:14px 16px; background:linear-gradient(135deg,var(--mor1),var(--mavi)); display:flex; align-items:center; gap:10px; flex-shrink:0; }
#posPanelBaslik .ikon { color:#fff; font-size:22px; font-family:'Material Icons Outlined',sans-serif; }
#posPanelBaslik .ad { color:#fff; font-size:16px; font-weight:700; }
#posPanelBaslik .alt { color:#E9D5FF; font-size:11.5px; }
/* ---- Müşteri bağlama bant ---- */
#posMusteriBand { padding:8px 12px; border-bottom:1px solid var(--cizgi); flex-shrink:0; }
.pos-musteri-box { display:flex; align-items:center; justify-content:space-between; background:rgba(245,158,11,.10); border-radius:10px; padding:8px 12px; }
.pos-musteri-ekle { width:100%; padding:8px; border:1.5px dashed var(--cizgi); border-radius:10px; background:none; color:var(--sub); font-size:13px; cursor:pointer; }
.pos-musteri-ekle:hover { color:var(--mor1); border-color:var(--mor1); }
/* ---- Kalem listesi ---- */
#posKalemler { flex:1; overflow-y:auto; padding:10px 12px 4px; }
.pos-kalem { display:flex; align-items:center; gap:8px; background:var(--kart2); border-radius:10px; padding:9px 10px; margin-bottom:6px; border:1.4px solid transparent; }
.pos-kalem.ikram { background:rgba(245,158,11,.09); border-color:rgba(245,158,11,.4); }
.pos-kalem.iptal  { background:rgba(244,63,94,.08); border-color:rgba(244,63,94,.4); opacity:.65; }
.pos-kalem.odendi { opacity:.65; }
.pos-kalem-adet { width:26px; height:26px; border-radius:8px; background:rgba(124,58,237,.14); color:var(--mor1); font-size:12.5px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pos-kalem-ad { flex:1; color:var(--ink); font-size:13.5px; font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pos-kalem-ad.cizik { text-decoration:line-through; }
.pos-kalem-rozet { font-size:9.5px; font-weight:700; padding:2px 6px; border-radius:6px; white-space:nowrap; }
.pos-kalem-rozet.yeni    { background:rgba(245,158,11,.18); color:var(--turuncu); }
.pos-kalem-rozet.hazir   { background:rgba(16,185,129,.18); color:var(--yesil); }
.pos-kalem-rozet.gonder  { background:rgba(79,70,229,.14); color:var(--mavi); }
.pos-kalem-rozet.ikram-r { background:rgba(245,158,11,.18); color:var(--turuncu); }
.pos-kalem-rozet.iptal-r { background:rgba(244,63,94,.18); color:var(--kirmizi); }
.pos-kalem-fiyat { color:var(--ink); font-size:13.5px; font-weight:700; white-space:nowrap; }
.pos-kalem-fiyat.ikram-f { color:var(--turuncu); text-decoration:line-through; }
.pos-kalem-fiyat.iptal-f { color:var(--kirmizi); text-decoration:line-through; }
.pos-kalem-sil { background:none; border:none; color:var(--kirmizi); font-size:18px; cursor:pointer; padding:0 2px; line-height:1; }
.pos-bos { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:40px 0; color:var(--sub); font-size:14px; }
/* ---- Alt panel ---- */
#posAlt { padding:12px 14px 14px; border-top:1px solid var(--cizgi); background:var(--kart); flex-shrink:0; box-shadow:0 -3px 12px rgba(0,0,0,.06); }
.pos-toplam-satir { display:flex; align-items:baseline; justify-content:space-between; margin-bottom:10px; }
.pos-toplam-etiket { color:var(--ink); font-size:14px; font-weight:700; }
.pos-toplam-tutar { color:var(--mor1); font-size:23px; font-weight:700; }
.pos-dokum { display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:3px; }
/* ---- İkincil aksiyon butonları (4'lü sıra) ---- */
.pos-ikincil-row { display:flex; gap:7px; margin-bottom:10px; }
.pos-ikincil { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; padding:9px 4px; border-radius:11px; cursor:pointer; border:1.3px solid transparent; font-size:11px; font-weight:700; transition:opacity .1s; }
.pos-ikincil .material-icons-outlined { font-size:18px; }
.pos-ikincil:hover { opacity:.8; }
.pos-ikincil.yesil  { background:rgba(16,185,129,.10); color:var(--yesil);  border-color:rgba(16,185,129,.32); }
.pos-ikincil.turuncu{ background:rgba(245,158,11,.10); color:var(--turuncu);border-color:rgba(245,158,11,.32); }
.pos-ikincil.mavi   { background:rgba(79,70,229,.10);  color:var(--mavi);   border-color:rgba(79,70,229,.32); }
.pos-ikincil.kirmizi{ background:rgba(244,63,94,.10);  color:var(--kirmizi);border-color:rgba(244,63,94,.32); }
/* ---- Siparişi Kaydet butonu ---- */
.pos-btn-kaydet { width:100%; padding:13px; border:none; border-radius:13px; background:var(--mor1); color:#fff; font-size:14px; font-weight:700; cursor:pointer; margin-bottom:10px; display:flex; align-items:center; justify-content:center; gap:6px; }
.pos-btn-kaydet:disabled { opacity:.5; cursor:not-allowed; }
/* ---- Ödeme buton satırları ---- */
.pos-ode-row { display:flex; gap:9px; margin-bottom:9px; }
.pos-ode-btn { flex:1; height:62px; border:none; border-radius:14px; color:#fff; font-size:12.5px; font-weight:700; cursor:pointer; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; box-shadow:0 2px 8px rgba(0,0,0,.15); transition:opacity .1s; }
.pos-ode-btn:hover { opacity:.9; }
.pos-ode-btn.nakit      { background:var(--yesil); }
.pos-ode-btn.kart       { background:var(--mavi); }
.pos-ode-btn.yemekkarti { background:var(--turuncu); }
.pos-ode-btn.acik       { background:var(--mor1); }
.pos-ode-btn .material-icons-outlined { font-size:22px; }
/* ---- Kısmi / böl / para üstü butonu ---- */
.pos-btn-bol { width:100%; padding:11px; border:1.4px solid var(--cizgi); border-radius:12px; background:var(--kart2); color:var(--sub2); font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:7px; }
.pos-btn-bol:hover { border-color:var(--mor1); color:var(--mor1); }
/* ---- Modal zemin + kart ---- */
.pos-modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; z-index:9999; padding:16px; }
.pos-modal { background:var(--kart); border-radius:20px; padding:24px; width:100%; max-width:360px; box-shadow:0 20px 60px rgba(0,0,0,.25); }
.pos-modal-genis { max-width:440px; }
.pos-modal h3 { color:var(--ink); font-size:16px; font-weight:700; margin:0 0 14px; }
.pos-modal p  { color:var(--sub); font-size:13px; margin:0 0 14px; }
.pos-modal input, .pos-modal select, .pos-modal textarea {
    width:100%; box-sizing:border-box; padding:10px 12px; border:1.4px solid var(--cizgi); border-radius:10px;
    background:var(--kart2); color:var(--ink); font-size:13.5px; margin-bottom:10px; outline:none;
}
.pos-modal input:focus,.pos-modal select:focus,.pos-modal textarea:focus { border-color:var(--mor1); }
.pos-modal-row { display:flex; gap:8px; margin-top:4px; }
.pos-modal-row button { flex:1; padding:11px; border-radius:12px; font-size:13.5px; font-weight:600; cursor:pointer; border:none; }
.pos-modal-row .vazgec { background:var(--kart2); color:var(--sub); }
.pos-modal-row .tamam  { background:var(--mor1); color:#fff; }
.pos-modal-row .tamam.yesil   { background:var(--yesil); }
.pos-modal-row .tamam.kirmizi { background:var(--kirmizi); }
/* ---- Onay gradient başlık (ödeme onay modal) ---- */
.pos-modal-header { border-radius:12px 12px 0 0; margin:-24px -24px 16px; padding:20px 24px 16px; background:linear-gradient(135deg,var(--yesil),#059669); text-align:center; }
.pos-modal-header .ic { width:46px; height:46px; border-radius:50%; background:rgba(255,255,255,.22); display:flex; align-items:center; justify-content:center; margin:0 auto 8px; }
.pos-modal-header .ic .material-icons-outlined { color:#fff; font-size:24px; }
.pos-modal-header .baslik { color:#fff; font-size:15px; font-weight:700; }
.pos-modal-header.kart-header   { background:linear-gradient(135deg,#3B82F6,#2563EB); }
.pos-modal-header.yemek-header  { background:linear-gradient(135deg,var(--turuncu),#D97706); }
.pos-modal-header.acik-header   { background:linear-gradient(135deg,var(--mor1),var(--mavi)); }
.pos-modal-tutar { text-align:center; margin-bottom:4px; font-size:30px; font-weight:700; }
.pos-modal-alt   { text-align:center; color:var(--sub); font-size:11.5px; margin-bottom:16px; }
/* ---- İkram kalem seçim listesi ---- */
.pos-ikram-kalem { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--cizgi); cursor:pointer; }
.pos-ikram-kalem:last-child { border-bottom:none; }
.pos-check { width:22px; height:22px; border-radius:7px; border:1.6px solid var(--cizgi); background:transparent; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pos-check.aktif { background:var(--mor1); border-color:var(--mor1); }
.pos-check.aktif::after { content:'✓'; color:#fff; font-size:13px; }
/* ---- Para üstü numpad ---- */
.pos-numpad { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; margin-bottom:8px; }
.pos-numpad-tus { padding:11px; border:none; border-radius:12px; background:var(--kart2); color:var(--ink); font-size:18px; font-weight:700; cursor:pointer; text-align:center; }
.pos-numpad-tus:hover { opacity:.8; }
/* ---- Garson modalı ---- */
#posGarsonModal { display:none; position:fixed; inset:0; z-index:9999; align-items:center; justify-content:center; padding:16px; }
</style>

{{-- Header: geri + garson --}}
<div class="flex items-center justify-between mb-3">
    <a href="/pos" class="inline-flex items-center gap-1 text-sm" style="color:var(--sub)">
        <span class="material-icons-outlined" style="font-size:18px">arrow_back</span> Masa Haritası
    </a>
    <div style="font-size:13px;color:var(--sub)">
        <span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">person</span>
        <b id="posGarsonAd" style="color:var(--mor1)">—</b>
        <button onclick="posGarsonSec()" style="margin-left:8px;font-size:12px;font-weight:600;color:var(--mor1);border:1px solid rgba(124,58,237,.3);border-radius:8px;padding:3px 10px;background:none;cursor:pointer">Değiştir</button>
    </div>
</div>

@if (!$adisyon)
{{-- ===== BOŞ MASA: Adisyon Aç ===== --}}
<div x-data="{ misafir: 2, ac() { api('/pos/adisyon-ac', { masa_id: {{ $masa->id }}, misafir: this.misafir }).then(() => location.reload()); } }"
     style="max-width:400px;margin:40px auto;background:var(--kart);border-radius:20px;border:1px solid var(--cizgi);padding:32px;text-align:center">
    <div style="font-size:52px;margin-bottom:12px">🪑</div>
    <h2 style="color:var(--ink);font-size:18px;font-weight:700;margin-bottom:6px">Masa {{ $masa->ad }} boş</h2>
    <p style="color:var(--sub);font-size:13px;margin-bottom:20px">Kapasite: {{ $masa->kapasite }} kişi</p>
    <label style="display:block;color:var(--sub);font-size:13px;margin-bottom:8px">Misafir sayısı</label>
    <input type="number" x-model="misafir" min="1"
           style="width:80px;text-align:center;border:1.4px solid var(--cizgi);border-radius:10px;padding:8px 12px;background:var(--kart2);color:var(--ink);font-size:16px;margin-bottom:16px">
    <button @click="ac()" style="width:100%;background:var(--mor1);color:#fff;font-weight:700;border:none;border-radius:13px;padding:14px;font-size:15px;cursor:pointer">Adisyon Aç</button>
</div>

@else
{{-- ===== AKTİF ADISYON ===== --}}
<div id="posWrap">

  {{-- ===== SOL: Menü ===== --}}
  <div id="posMenu">
    {{-- Arama --}}
    <div style="padding:10px 14px;background:var(--kart);border-bottom:1px solid var(--cizgi);display:flex;align-items:center;gap:8px">
      <span class="material-icons-outlined" style="color:var(--sub2);font-size:20px">search</span>
      <input id="posAraInput" type="text" placeholder="Ürün ara…" autocomplete="off" name="pos_urun_ara_{{ $adisyon->id }}"
             style="flex:1;border:none;background:transparent;color:var(--ink);font-size:14px;outline:none"
             oninput="posAra(this.value)">
    </div>

    {{-- Kategori çipleri --}}
    <div id="posKatBar">
      @foreach($kategoriler as $k)
        <button class="pos-kat{{ $loop->first ? ' aktif' : '' }}"
                data-kid="{{ $k->id }}"
                onclick="posKatSec({{ $k->id }}, this)">{{ $k->ad }}</button>
      @endforeach
    </div>

    {{-- Ürün ızgarası --}}
    <div id="posUrunGrid">
      @foreach($kategoriler as $k)
        @foreach($urunler[$k->id] ?? [] as $u)
          <div class="pos-urun{{ $u->tukendi ? ' tukendi' : '' }}"
               data-kid="{{ $k->id }}"
               data-ad="{{ mb_strtolower($u->ad) }}"
               onclick="{{ $u->tukendi ? '' : 'posEkle(' . $u->id . ')' }}"
               style="{{ $k->id !== ($kategoriler->first()->id ?? 0) ? 'display:none' : '' }}">
            <div class="pos-urun-img">
              @if(!empty($u->gorsel))
                <img src="{{ $u->gorsel }}" alt="{{ $u->ad }}" loading="lazy"
                     onerror="this.onerror=null;this.parentElement.innerHTML='<span style=\'font-size:40px\'>🍽️</span>'">
              @else
                <span style="font-size:40px">🍽️</span>
              @endif
              <span class="pos-urun-rozet" id="urun-adet-{{ $u->id }}" style="display:none">0</span>
              @if($u->tukendi)<span class="pos-urun-tukendi-badge">TÜKENDİ</span>@endif
            </div>
            <div class="pos-urun-info">
              <div class="pos-urun-ad">{{ $u->ad }}</div>
              <div class="pos-urun-fiyat">{{ number_format((float)$u->fiyat, 0, ',', '.') }} TL</div>
            </div>
          </div>
        @endforeach
      @endforeach
    </div>
  </div>

  {{-- ===== SAĞ: Adisyon Paneli ===== --}}
  <div id="posPanel">

    {{-- Gradient başlık --}}
    <div id="posPanelBaslik">
      <span class="material-icons-outlined ikon">table_restaurant</span>
      <div style="flex:1">
        <div class="ad">{{ $masa->ad }}</div>
        <div class="alt" id="posKalemAlt">Adisyon #{{ $adisyon->id }} · {{ $kalemler->count() }} ürün</div>
      </div>
      <button onclick="posTasiModal()" title="Masa Taşı"
              style="background:rgba(255,255,255,.18);border:none;border-radius:8px;padding:6px 8px;cursor:pointer;display:flex;align-items:center;gap:4px;color:#fff;font-size:11px;font-weight:600">
        <span class="material-icons-outlined" style="font-size:16px;color:#fff">swap_horiz</span>Taşı
      </button>
    </div>

    {{-- Müşteri Bağla --}}
    <div id="posMusteriBand">
      @if($musteri)
        <div class="pos-musteri-box">
          <div>
            <span style="font-size:13px;font-weight:600;color:var(--ink)">👤 {{ $musteri->ad }}</span>
            <span style="font-size:11px;color:var(--sub);margin-left:6px">{{ $musteri->telefon }} · {{ $musteri->puan }} puan</span>
          </div>
          <button onclick="posMusteriModal()" style="font-size:12px;color:var(--mor1);background:none;border:none;cursor:pointer;font-weight:600">Değiştir</button>
        </div>
      @else
        <button class="pos-musteri-ekle" onclick="posMusteriModal()">
          + Müşteri Bağla <span style="font-size:11px;color:var(--sub2)">(sadakat puanı)</span>
        </button>
      @endif
    </div>

    {{-- Kalem listesi --}}
    <div id="posKalemler">
      @forelse($kalemler as $kalem)
        @php
          $iptal  = $kalem->durum === 'iptal';
          $odendi = ($kalem->odeme_durum ?? '') === 'odendi';
          $ikram  = (int)($kalem->ikram ?? 0) === 1;
        @endphp
        <div class="pos-kalem{{ $ikram ? ' ikram' : '' }}{{ $iptal ? ' iptal' : '' }}{{ $odendi ? ' odendi' : '' }}">
          @if($iptal)
            <span class="material-icons-outlined" style="color:var(--kirmizi);font-size:18px;flex-shrink:0">block</span>
          @elseif($odendi)
            <span class="material-icons-outlined" style="color:var(--yesil);font-size:20px;flex-shrink:0">check_circle</span>
          @endif
          <div class="pos-kalem-adet">{{ $kalem->adet }}</div>
          <div class="pos-kalem-ad{{ ($iptal||$odendi) ? ' cizik' : '' }}">{{ $kalem->urun_adi }}</div>
          @if($ikram) <span class="pos-kalem-rozet ikram-r">İKRAM</span> @endif
          @if($iptal) <span class="pos-kalem-rozet iptal-r">İPTAL</span> @endif
          @if(!$iptal && !$ikram && !$odendi)
            <span class="pos-kalem-rozet {{ $kalem->durum === 'yeni' ? 'yeni' : ($kalem->durum === 'hazir' ? 'hazir' : 'gonder') }}">{{ $kalem->durum }}</span>
          @endif
          <div class="pos-kalem-fiyat{{ $ikram ? ' ikram-f' : '' }}{{ $iptal ? ' iptal-f' : '' }}">{{ number_format((float)$kalem->tutar,0,',','.') }} TL</div>
          @if($kalem->durum === 'yeni' && !$iptal)
            <button class="pos-kalem-sil" onclick="posSil({{ $kalem->id }})" title="Sil">×</button>
          @endif
        </div>
      @empty
        <div class="pos-bos" id="posKalemBos">
          <span class="material-icons-outlined" style="font-size:40px;opacity:.3">receipt_long</span>
          Soldan ürün ekleyin
        </div>
      @endforelse
    </div>

    {{-- Alt panel --}}
    <div id="posAlt">
      {{-- Döküm --}}
      @php
        $araToplam = (float)($adisyon->ara_toplam ?? $adisyon->toplam);
        $iskonto   = (float)($adisyon->indirim ?? 0);
        $ikramTut  = (float)($adisyon->ikram ?? 0);
        $toplam    = (float)$adisyon->toplam;
      @endphp
      @if($iskonto > 0 || $ikramTut > 0)
        <div class="pos-dokum"><span style="color:var(--sub)">Ara Toplam</span><span style="color:var(--sub)">{{ number_format($araToplam,0,',','.') }} TL</span></div>
        @if($iskonto > 0)  <div class="pos-dokum"><span style="color:var(--sub)">İskonto</span><span style="color:var(--yesil)">-{{ number_format($iskonto,0,',','.') }} TL</span></div> @endif
        @if($ikramTut > 0) <div class="pos-dokum"><span style="color:var(--sub)">İkram</span><span style="color:var(--turuncu)">-{{ number_format($ikramTut,0,',','.') }} TL</span></div> @endif
      @endif

      {{-- Genel Toplam --}}
      <div class="pos-toplam-satir">
        <span class="pos-toplam-etiket">Genel Toplam</span>
        <span class="pos-toplam-tutar">{{ number_format($toplam,0,',','.') }} TL</span>
      </div>

      {{-- İkincil aksiyonlar --}}
      <div class="pos-ikincil-row">
        <button class="pos-ikincil yesil"  onclick="posIskontoModal()">
          <span class="material-icons-outlined">local_offer</span>İskonto
        </button>
        <button class="pos-ikincil turuncu" onclick="posIkramModal()">
          <span class="material-icons-outlined">card_giftcard</span>İkram
        </button>
        <button class="pos-ikincil mavi" onclick="window.open('/pos/fis/{{ $adisyon->id }}','_blank')">
          <span class="material-icons-outlined">print</span>Fiş
        </button>
        <button class="pos-ikincil kirmizi" onclick="posIptalModal()">
          <span class="material-icons-outlined">cancel</span>İptal
        </button>
      </div>

      {{-- Siparişi Kaydet: sadece yeni kalem varsa görünür --}}
      @if($kalemler->where('durum','yeni')->count() > 0)
        <button class="pos-btn-kaydet" onclick="posGonder()">
          <span class="material-icons-outlined" style="font-size:19px">send</span>
          Siparişi Kaydet ({{ $kalemler->where('durum','yeni')->count() }})
        </button>
      @endif

      {{-- Ödeme satır 1: Nakit + Kart --}}
      <div class="pos-ode-row">
        <button class="pos-ode-btn nakit" onclick="posOdeOnay('nakit','Nakit','payments')">
          <span class="material-icons-outlined">payments</span>Nakit
        </button>
        <button class="pos-ode-btn kart" onclick="posOdeOnay('kredi','Kart','credit_card')">
          <span class="material-icons-outlined">credit_card</span>Kart
        </button>
      </div>
      {{-- Ödeme satır 2: Yemek K. + Açık Hesap --}}
      <div class="pos-ode-row" style="margin-bottom:8px">
        <button class="pos-ode-btn yemekkarti" onclick="posOdeOnay('yemek_karti','Yemek K.','restaurant')">
          <span class="material-icons-outlined">restaurant</span>Yemek K.
        </button>
        <button class="pos-ode-btn acik" onclick="posAcikHesapModal()">
          <span class="material-icons-outlined">account_balance_wallet</span>Açık Hesap
        </button>
      </div>

      {{-- Kısmi / böl / para üstü --}}
      <button class="pos-btn-bol" onclick="posParaUstuModal()">
        <span class="material-icons-outlined" style="font-size:17px">call_split</span>
        Kısmi / Böl · Para Üstü
      </button>
    </div>

  </div>{{-- posPanel --}}
</div>{{-- posWrap --}}

{{-- ============================================================
     MODALLER
     ============================================================ --}}

{{-- Müşteri Bağla --}}
<div id="mModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal" x-data="{ ara:'', sonuc:[], mad:'', mtel:'',
      async searchM(){ this.sonuc = await api('/musteriler/ara?q='+encodeURIComponent(this.ara)); },
      async baglaM(id){ await api('/pos/musteri-bagla',{ adisyon_id: {{ $adisyon->id }}, musteri_id: id }); location.reload(); },
      async yeniM(){
        if(!this.mad.trim()) return;
        await api('/pos/musteri-bagla',{ adisyon_id: {{ $adisyon->id }}, ad: this.mad, telefon: this.mtel });
        location.reload(); } }">
    <h3>Müşteri Bağla</h3>
    <input x-model="ara" @input.debounce.300ms="searchM()" placeholder="Ara: ad veya telefon">
    <div style="max-height:160px;overflow-y:auto;margin-bottom:10px">
      <template x-for="m in sonuc" :key="m.id">
        <button @click="baglaM(m.id)"
                style="width:100%;text-align:left;padding:8px 12px;border-radius:8px;border:none;background:none;cursor:pointer;font-size:13px;color:var(--ink)"
                onmouseover="this.style.background='var(--kart2)'" onmouseout="this.style.background='none'">
          <span x-text="m.ad" style="font-weight:600"></span>
          <span x-text="' · '+m.telefon" style="color:var(--sub);font-size:12px"></span>
        </button>
      </template>
    </div>
    <div style="border-top:1px solid var(--cizgi);padding-top:10px">
      <p style="margin-bottom:8px">Yeni müşteri ekle</p>
      <input x-model="mad" placeholder="Ad Soyad">
      <input x-model="mtel" placeholder="Telefon">
      <div class="pos-modal-row">
        <button class="vazgec" onclick="document.getElementById('mModal').style.display='none'">Kapat</button>
        <button class="tamam" @click="yeniM()">Ekle & Bağla</button>
      </div>
    </div>
  </div>
</div>

{{-- Ödeme Onay Modal --}}
<div id="odeModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal" style="padding:0;overflow:hidden">
    <div id="odeModalHeader" class="pos-modal-header">
      <div class="ic"><span class="material-icons-outlined" id="odeModalIkon">payments</span></div>
      <div class="baslik" id="odeModalBaslik">Nakit ile Öde</div>
    </div>
    <div style="padding:0 24px 24px">
      <p style="text-align:center;color:var(--sub);font-size:12px;margin:12px 0 4px">Tahsil edilecek tutar</p>
      <div class="pos-modal-tutar" id="odeModalTutar" style="color:var(--yesil)">{{ number_format($toplam,0,',','.') }} TL</div>
      <p class="pos-modal-alt">Ödeme sonrası masa kapanır</p>
      <div class="pos-modal-row">
        <button class="vazgec" onclick="document.getElementById('odeModal').style.display='none'">Vazgeç</button>
        <button class="tamam" id="odeModalOnayla" onclick="posOdeOnayla()">Onayla</button>
      </div>
    </div>
  </div>
</div>

{{-- İskonto Modal --}}
<div id="iskontoModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>İskonto Uygula</h3>
    <p>Yüzde olarak iskonto oranını girin.</p>
    <input id="iskontoOran" type="number" min="1" max="100" placeholder="Yüzde (%)">
    <div class="pos-modal-row">
      <button class="vazgec" onclick="document.getElementById('iskontoModal').style.display='none'">Vazgeç</button>
      <button class="tamam" onclick="posIskontoUygula()">Uygula</button>
    </div>
  </div>
</div>

{{-- İkram Modal --}}
<div id="ikramModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal pos-modal-genis">
    <h3>İkram — Ürün Seç</h3>
    <div id="ikramKalemler" style="max-height:240px;overflow-y:auto;margin-bottom:10px">
      @foreach($kalemler->where('durum','!=','iptal') as $kalem)
        @php $oid = ($kalem->odeme_durum ?? '') === 'odendi'; @endphp
        @if(!$oid)
          <div class="pos-ikram-kalem" onclick="posIkramToggle({{ $kalem->id }},this)"
               data-id="{{ $kalem->id }}" data-tutar="{{ $kalem->tutar }}">
            <div class="pos-check" id="ikramChk-{{ $kalem->id }}"></div>
            <div style="flex:1;color:var(--ink);font-size:13.5px">{{ $kalem->adet }}× {{ $kalem->urun_adi }}</div>
            <div style="color:var(--sub);font-size:13px">{{ number_format((float)$kalem->tutar,0,',','.') }} TL</div>
          </div>
        @endif
      @endforeach
    </div>
    <div style="display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:10px;padding-top:6px;border-top:1px solid var(--cizgi)">
      <span style="color:var(--sub)">İkram Toplamı</span>
      <span style="color:var(--turuncu)" id="ikramToplam">0 TL</span>
    </div>
    <textarea id="ikramSebep" placeholder="İkram sebebi (zorunlu) — örn. müşteri şikayeti, doğum günü…" rows="2"
              style="resize:none"></textarea>
    <div class="pos-modal-row">
      <button class="vazgec" onclick="document.getElementById('ikramModal').style.display='none'">Vazgeç</button>
      <button class="tamam" style="background:var(--turuncu)" onclick="posIkramUygula()">Talebi Gönder</button>
    </div>
  </div>
</div>

{{-- İptal Onay Modal --}}
<div id="iptalModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>Adisyonu İptal Et</h3>
    <p>{{ $masa->ad }} adisyonu iptal edilsin mi? Bu işlem geri alınamaz.</p>
    <div class="pos-modal-row">
      <button class="vazgec" onclick="document.getElementById('iptalModal').style.display='none'">Vazgeç</button>
      <button class="tamam kirmizi" onclick="posIptalUygula()">İptal Et</button>
    </div>
  </div>
</div>

{{-- Açık Hesap Modal (cari seç) --}}
<div id="acikModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>Açık Hesap</h3>
    <p>Tutarı hangi cari hesaba yazalım?</p>
    <select id="acikCariSec">
      <option value="">Cari seç…</option>
      @if(Schema::hasTable('cari_hesaplar'))
        @foreach(DB::table('cari_hesaplar')->where('sube_id', $masa->sube_id)->where('aktif',1)->orderBy('ad')->get() as $c)
          <option value="{{ $c->id }}">{{ $c->ad }}{{ $c->telefon ? ' · '.$c->telefon : '' }}</option>
        @endforeach
      @endif
    </select>
    <div class="pos-modal-row">
      <button class="vazgec" onclick="document.getElementById('acikModal').style.display='none'">Vazgeç</button>
      <button class="tamam" style="background:var(--mor1)" onclick="posAcikHesapUygula()">Hesaba Yaz</button>
    </div>
  </div>
</div>

{{-- Kısmi / Para Üstü Modal --}}
<div id="paraModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>Kısmi Ödeme / Para Üstü</h3>
    <div style="display:flex;justify-content:space-between;margin-bottom:10px">
      <span style="color:var(--sub);font-size:13px">Kalan</span>
      <span id="paraKalan" style="color:var(--mor1);font-size:22px;font-weight:700">{{ number_format($toplam,0,',','.') }} TL</span>
    </div>
    <div style="background:var(--kart2);border-radius:12px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
      <span style="color:var(--sub);font-size:13px">Alınan</span>
      <span id="paraGirilen" style="color:var(--ink);font-size:22px;font-weight:700">—</span>
    </div>
    <div id="paraUstu" style="display:none;justify-content:space-between;margin-bottom:6px">
      <span style="color:var(--turuncu);font-size:13px;font-weight:700">Para Üstü</span>
      <span id="paraUstuTutar" style="color:var(--turuncu);font-size:18px;font-weight:700"></span>
    </div>
    <div class="pos-numpad">
      <button class="pos-numpad-tus" onclick="paraGir('1')">1</button>
      <button class="pos-numpad-tus" onclick="paraGir('2')">2</button>
      <button class="pos-numpad-tus" onclick="paraGir('3')">3</button>
      <button class="pos-numpad-tus" onclick="paraGir('4')">4</button>
      <button class="pos-numpad-tus" onclick="paraGir('5')">5</button>
      <button class="pos-numpad-tus" onclick="paraGir('6')">6</button>
      <button class="pos-numpad-tus" onclick="paraGir('7')">7</button>
      <button class="pos-numpad-tus" onclick="paraGir('8')">8</button>
      <button class="pos-numpad-tus" onclick="paraGir('9')">9</button>
      <button class="pos-numpad-tus" onclick="paraGir('.')">.</button>
      <button class="pos-numpad-tus" onclick="paraGir('0')">0</button>
      <button class="pos-numpad-tus" onclick="paraSil()"><span class="material-icons-outlined" style="font-size:20px;vertical-align:middle">backspace</span></button>
    </div>
    <div class="pos-ode-row">
      <button class="pos-ode-btn nakit" onclick="paraOde('nakit')">
        <span class="material-icons-outlined">payments</span>Nakit
      </button>
      <button class="pos-ode-btn kart" onclick="paraOde('kredi')">
        <span class="material-icons-outlined">credit_card</span>Kart
      </button>
      <button class="pos-ode-btn yemekkarti" onclick="paraOde('yemek_karti')">
        <span class="material-icons-outlined">restaurant</span>Yemek K.
      </button>
    </div>
  </div>
</div>

{{-- Masa Taşı Modal --}}
<div id="tasiModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>Masa Taşı</h3>
    <select id="tasiMasaSec">
      <option value="">Boş masa seç…</option>
      @foreach($bosMasalar as $bm)<option value="{{ $bm->id }}">{{ $bm->ad }}</option>@endforeach
    </select>
    <div class="pos-modal-row">
      <button class="vazgec" onclick="document.getElementById('tasiModal').style.display='none'">Vazgeç</button>
      <button class="tamam" onclick="posTasiUygula()">Taşı</button>
    </div>
  </div>
</div>

{{-- PIN Modal (iskonto/ikram limit aşımı) --}}
<div id="pinModal" class="pos-modal-bg" style="display:none" onclick="if(event.target===this)this.style.display='none'">
  <div class="pos-modal">
    <h3>🔒 Yetkili Onayı</h3>
    <p id="pinMesaj">Müdür/Sahip PIN gir</p>
    <input id="pinGiris" type="password" inputmode="numeric" placeholder="Müdür/Sahip PIN"
           style="text-align:center;letter-spacing:8px;font-size:18px">
    <div class="pos-modal-row">
      <button class="vazgec" onclick="pinVazgec()">Vazgeç</button>
      <button class="tamam" onclick="pinOnayla()">Onayla</button>
    </div>
  </div>
</div>

@endif {{-- $adisyon --}}

{{-- Garson Seçim Modalı --}}
<div id="posGarsonModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px">
  <div style="position:absolute;inset:0;background:rgba(0,0,0,.4)" onclick="document.getElementById('posGarsonModal').style.display='none'"></div>
  <div style="position:relative;background:var(--kart);border-radius:20px;padding:20px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.25)">
    <h3 style="color:var(--ink);font-weight:700;font-size:16px;margin-bottom:4px">Garson Seç</h3>
    <p style="color:var(--sub);font-size:12px;margin-bottom:14px">Aldığın siparişler sana yazılır. Bu cihaz seçimi hatırlar.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;max-height:320px;overflow-y:auto">
      @foreach($garsonlar as $g)
        <button class="pg-btn" data-id="{{ $g->id }}" data-ad="{{ $g->ad }}"
                style="border:1px solid var(--cizgi);border-radius:12px;padding:12px;text-align:left;background:none;cursor:pointer;color:var(--ink);font-size:13px;font-weight:600"
                onmouseover="this.style.borderColor='var(--mor1)';this.style.background='rgba(124,58,237,.06)'"
                onmouseout="this.style.borderColor='var(--cizgi)';this.style.background='none'">
          {{ $g->ad }}<span style="display:block;font-size:10px;color:var(--sub);font-weight:400">{{ $g->rol }}</span>
        </button>
      @endforeach
    </div>
  </div>
</div>

<style>[x-cloak]{display:none!important}</style>
@endsection

@push('scripts')
<script>
// ---- Sabitler ----
var POS_ADISYON_ID = {{ $adisyon ? $adisyon->id : 0 }};
var POS_TOPLAM     = {{ $adisyon ? (float)$adisyon->toplam : 0 }};

// ---- Garson ----
function posGarsonGoster(){ var e=document.getElementById('posGarsonAd'); if(e) e.textContent=localStorage.getItem('pos_garson_ad')||'—'; }
function posGarsonSec(){ var m=document.getElementById('posGarsonModal'); m.style.display='flex'; }
function posGarsonAta(id,ad){ localStorage.setItem('pos_garson_id',id); localStorage.setItem('pos_garson_ad',ad); document.getElementById('posGarsonModal').style.display='none'; posGarsonGoster(); }
document.addEventListener('DOMContentLoaded',function(){
  posGarsonGoster();
  document.querySelectorAll('.pg-btn').forEach(function(b){ b.onclick=function(){ posGarsonAta(b.dataset.id,b.dataset.ad); }; });
  if(!localStorage.getItem('pos_garson_id')) posGarsonSec();
  // Ürün ızgarası: başta birinci kategori göster
  var ilk = document.querySelector('.pos-kat.aktif');
  if(ilk) posKatSec(parseInt(ilk.dataset.kid), ilk);
});

// ---- Müşteri modal ----
function posMusteriModal(){ document.getElementById('mModal').style.display='flex'; }

// ---- Kategori seçimi ----
function posKatSec(kid, el){
  document.querySelectorAll('.pos-kat').forEach(function(b){ b.classList.remove('aktif'); });
  if(el) el.classList.add('aktif');
  document.querySelectorAll('#posUrunGrid .pos-urun').forEach(function(d){
    d.style.display = (parseInt(d.dataset.kid)===kid) ? '' : 'none';
  });
}

// ---- Ürün arama ----
function posAra(val){
  var v = val.trim().toLowerCase();
  document.getElementById('posKatBar').style.display = v ? 'none' : '';
  document.querySelectorAll('#posUrunGrid .pos-urun').forEach(function(d){
    d.style.display = (!v || d.dataset.ad.includes(v)) ? '' : 'none';
  });
}

// ---- Ürün ekle / gönder ----
function posEkle(urunId){
  api('/pos/kalem-ekle', { adisyon_id: POS_ADISYON_ID, urun_id: urunId }).then(function(r){
    if(r && r.hata) alert(r.hata); else location.reload();
  });
}
function posSil(kalemId){
  api('/pos/kalem-sil', { kalem_id: kalemId }).then(function(r){
    if(r && r.hata) alert(r.hata); else location.reload();
  });
}
function posGonder(){
  api('/pos/gonder', { adisyon_id: POS_ADISYON_ID }).then(function(r){
    if(r && r.hata) alert(r.hata); else location.reload();
  });
}

// ---- Ödeme onay modal ----
var _odeGecici = null;
var _odeRenkMap = { nakit:'var(--yesil)', kredi:'#3B82F6', yemek_karti:'var(--turuncu)' };
var _odeHdrMap  = { nakit:'', kredi:'kart-header', yemek_karti:'yemek-header' };
function posOdeOnay(tip, ad, ikon){
  _odeGecici = tip;
  var hdr  = document.getElementById('odeModalHeader');
  var renk = _odeRenkMap[tip]||'var(--mor1)';
  hdr.className = 'pos-modal-header ' + (_odeHdrMap[tip]||'');
  document.getElementById('odeModalIkon').textContent = ikon;
  document.getElementById('odeModalBaslik').textContent = ad + ' ile Öde';
  document.getElementById('odeModalTutar').textContent  = new Intl.NumberFormat('tr-TR').format(POS_TOPLAM) + ' TL';
  document.getElementById('odeModalTutar').style.color  = renk;
  document.getElementById('odeModal').style.display = 'flex';
}
function posOdeOnayla(){
  document.getElementById('odeModal').style.display='none';
  posAdisyonIslem('ode', { odeme_tip: _odeGecici }, function(r){
    if(r.ok===1){ if(r.kapandi) location.href='/pos'; else location.reload(); }
    else { alert(r.hata||'Ödeme alınamadı'); }
  });
}

// ---- İskonto ----
function posIskontoModal(){ document.getElementById('iskontoOran').value=''; document.getElementById('iskontoModal').style.display='flex'; }
function posIskontoUygula(doPin){
  var oran = parseFloat(document.getElementById('iskontoOran').value||'0');
  if(!oran||oran<=0){ alert('Geçerli bir iskonto oranı girin.'); return; }
  document.getElementById('iskontoModal').style.display='none';
  var data = { islem:'iskonto', adisyon_id: POS_ADISYON_ID, oran: oran };
  if(doPin) data.onay_pin = doPin;
  posAdisyonIslem('iskonto', { oran: oran, onay_pin: doPin||undefined }, function(r){
    if(r.ok===1) location.reload();
    else if(r.onay_gerek){ pinIste(r.hata||'İskonto için yetkili PIN gerekli', function(pin){ posIskontoUygula(pin); }); }
    else alert(r.hata||'İskonto uygulanamadı');
  });
}

// ---- İkram ----
var _ikramSecili = {};
function posIkramModal(){
  _ikramSecili={};
  document.querySelectorAll('#ikramKalemler .pos-ikram-kalem').forEach(function(d){
    d.querySelector('.pos-check').className='pos-check';
  });
  document.getElementById('ikramToplam').textContent='0 TL';
  document.getElementById('ikramSebep').value='';
  document.getElementById('ikramModal').style.display='flex';
}
function posIkramToggle(id, el){
  var chk = el.querySelector('.pos-check');
  if(_ikramSecili[id]){ delete _ikramSecili[id]; chk.className='pos-check'; }
  else { _ikramSecili[id]=parseFloat(el.dataset.tutar||0); chk.className='pos-check aktif'; }
  var top=Object.values(_ikramSecili).reduce(function(a,b){return a+b;},0);
  document.getElementById('ikramToplam').textContent=new Intl.NumberFormat('tr-TR').format(top)+' TL';
}
function posIkramUygula(doPin){
  var ids=Object.keys(_ikramSecili); if(!ids.length){alert('İkram edilecek ürün seçin.');return;}
  var sebep=document.getElementById('ikramSebep').value.trim(); if(!sebep){alert('İkram sebebini yazın.');return;}
  var top=Object.values(_ikramSecili).reduce(function(a,b){return a+b;},0);
  document.getElementById('ikramModal').style.display='none';
  posAdisyonIslem('ikram',{kalem_idler:ids.join(','),tutar:top,sebep:sebep,onay_pin:doPin||undefined},function(r){
    if(r.ok===1) location.reload();
    else if(r.onay_gerek){ pinIste(r.hata||'İkram için yetkili PIN gerekli',function(pin){ posIkramUygula(pin); }); }
    else alert(r.hata||'İkram uygulanamadı');
  });
}

// ---- İptal ----
function posIptalModal(){ document.getElementById('iptalModal').style.display='flex'; }
function posIptalUygula(doPin){
  document.getElementById('iptalModal').style.display='none';
  posAdisyonIslem('iptal',{onay_pin:doPin||undefined},function(r){
    if(r.ok===1) location.href='/pos';
    else if(r.onay_gerek){ pinIste(r.hata||'İptal için yetkili PIN gerekli',function(pin){ posIptalUygula(pin); }); }
    else alert(r.hata||'İptal edilemedi');
  });
}

// ---- Açık Hesap ----
function posAcikHesapModal(){ document.getElementById('acikModal').style.display='flex'; }
function posAcikHesapUygula(){
  var cariId=document.getElementById('acikCariSec').value; if(!cariId){alert('Bir cari hesap seçin.');return;}
  document.getElementById('acikModal').style.display='none';
  posAdisyonIslem('kapat',{odeme_tip:'acik_hesap',cari_id:cariId},function(r){
    if(r.ok===1) location.href='/pos';
    else alert(r.hata||'İşlem başarısız');
  });
}

// ---- Para üstü / kısmi ----
var _paraGiris='';
function posParaUstuModal(){ _paraGiris=''; paraGuncelle(); document.getElementById('paraModal').style.display='flex'; }
function paraGir(d){ if(d==='.'&&_paraGiris.includes('.')) return; if(_paraGiris.length>=9) return; _paraGiris+=d; paraGuncelle(); }
function paraSil(){ _paraGiris=_paraGiris.slice(0,-1); paraGuncelle(); }
function paraGuncelle(){
  var girilen=parseFloat(_paraGiris)||0;
  document.getElementById('paraGirilen').textContent=_paraGiris?new Intl.NumberFormat('tr-TR').format(girilen)+' TL':'—';
  var ust=girilen>POS_TOPLAM?girilen-POS_TOPLAM:0;
  var uDiv=document.getElementById('paraUstu');
  uDiv.style.display=ust>0?'flex':'none';
  document.getElementById('paraUstuTutar').textContent=new Intl.NumberFormat('tr-TR').format(ust)+' TL';
}
function paraOde(tip){
  var girilen=parseFloat(_paraGiris)||0;
  var tutar=girilen>0?Math.min(girilen,POS_TOPLAM):POS_TOPLAM;
  if(tutar<=0){alert('Sıfır tutar ödenemez.');return;}
  document.getElementById('paraModal').style.display='none';
  posAdisyonIslem('ode',{odeme_tip:tip,tutar:tutar},function(r){
    if(r.ok===1){ if(r.kapandi) location.href='/pos'; else location.reload(); }
    else alert(r.hata||'Ödeme alınamadı');
  });
}

// ---- Masa Taşı ----
function posTasiModal(){ document.getElementById('tasiModal').style.display='flex'; }
function posTasiUygula(){
  var mId=document.getElementById('tasiMasaSec').value; if(!mId){alert('Bir masa seçin.');return;}
  document.getElementById('tasiModal').style.display='none';
  api('/pos/tasi',{adisyon_id:POS_ADISYON_ID,yeni_masa_id:mId}).then(function(r){
    if(r&&r.hata) alert(r.hata); else location.href='/pos';
  });
}

// ---- PIN helper ----
var _pinCallback=null;
function pinIste(mesaj,cb){ _pinCallback=cb; document.getElementById('pinMesaj').textContent=mesaj; document.getElementById('pinGiris').value=''; document.getElementById('pinModal').style.display='flex'; document.getElementById('pinGiris').focus(); }
function pinVazgec(){ _pinCallback=null; document.getElementById('pinModal').style.display='none'; }
function pinOnayla(){ var pin=document.getElementById('pinGiris').value.trim(); if(!pin){alert('PIN girin.');return;} document.getElementById('pinModal').style.display='none'; if(_pinCallback){ var cb=_pinCallback; _pinCallback=null; cb(pin); } }

// ---- Merkezi adisyon-islem çağrıcı (session fallback, CSRF) ----
function posAdisyonIslem(islem, extra, cb){
  var data = Object.assign({ islem: islem, adisyon_id: POS_ADISYON_ID }, extra);
  // undefined değerleri temizle
  Object.keys(data).forEach(function(k){ if(data[k]===undefined||data[k]===null) delete data[k]; });
  fetch('/api/patron/adisyon-islem', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'},
    body: JSON.stringify(data)
  }).then(function(r){ return r.json(); }).then(cb).catch(function(){ alert('Bağlantı hatası'); });
}
</script>
@endpush

@extends('layout.app')
@section('title', 'Satın Alma Talepleri')
@section('rk_ikon', 'assignment')
@section('baslik', 'Satın Alma Talepleri')

@php
    $talepler  = $talepler  ?? collect();
    $ozet      = $ozet      ?? (object)['yeni'=>0,'onayda'=>0,'tamamlanan'=>0];
    $malzemeler = $malzemeler ?? collect();
    $birimler   = $birimler   ?? collect();

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
  max-width: 1100px;
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

    {{-- Durum güncelleme --}}
    <div style="font-size:12px;font-weight:700;color:var(--sub,#64748b);letter-spacing:.3px;text-transform:uppercase;margin-bottom:8px">Durum Değiştir</div>
    <div class="sta-durum-guncelle" id="staDetDurumBtnler">
        <button onclick="staDurumGuncelle('teklif')" style="border-color:#F59E0B;color:#F59E0B">
            <span class="material-icons-outlined" style="font-size:15px">request_quote</span>Teklif Bek.
        </button>
        <button onclick="staDurumGuncelle('onayda')" style="border-color:#F59E0B;color:#F59E0B">
            <span class="material-icons-outlined" style="font-size:15px">rule</span>Onaya Gönder
        </button>
        <button onclick="staDurumGuncelle('onaylandi')" style="border-color:#10B981;color:#10B981">
            <span class="material-icons-outlined" style="font-size:15px">check_circle</span>Onayla
        </button>
        <button onclick="staDurumGuncelle('siparis')" style="border-color:#0EA5E9;color:#0EA5E9">
            <span class="material-icons-outlined" style="font-size:15px">shopping_cart_checkout</span>Siparişe Al
        </button>
        <button onclick="staDurumGuncelle('tamamlandi')" style="border-color:#10B981;color:#10B981">
            <span class="material-icons-outlined" style="font-size:15px">task_alt</span>Tamamlandı
        </button>
    </div>

    {{-- Kalem listesi --}}
    <div style="font-size:12px;font-weight:700;color:var(--sub,#64748b);letter-spacing:.3px;text-transform:uppercase;margin-bottom:8px">Kalemler</div>
    <div id="staDetKalemler" style="margin-bottom:14px"></div>

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

        // Kalem listesi
        let kalemHtml = '';
        if (kalemler.length === 0) {
            kalemHtml = '<div style="color:var(--sub);font-size:13px;padding:8px 0">Kalem bulunamadı.</div>';
        } else {
            kalemler.forEach(k => {
                kalemHtml += `
                <div class="sta-det-kalem">
                  <span class="material-icons-outlined">inventory_2</span>
                  <div class="dk-ad">
                    <div class="dk-malzeme">${escHtml(k.ad || ('Malzeme #' + k.malzeme_id))}</div>
                  </div>
                  <div class="dk-mik">${numFmt(k.miktar)} ${escHtml(k.birim || '')}</div>
                </div>`;
            });
        }
        document.getElementById('staDetKalemler').innerHTML = kalemHtml;

    } catch(e) {
        toast('⚠ Bağlantı hatası', '#F43F5E');
        staDetKapat();
    }
};

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

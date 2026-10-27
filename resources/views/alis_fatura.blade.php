@extends('layout.app')
@section('title', 'Alış Faturaları')
@section('rk_ikon', 'receipt_long')
@section('rk_geri', '/stok')
@section('baslik', 'Alış Faturaları')

@php
// ── Veri hazırlığı (Flutter AlisFaturaScreen._yukle sunucu tarafı karşılığı) ──
// $faturalar, $tedarikciler, $malzemeler, $birimler, $ay, $duzenleyebilir → route'tan gelir.
// $ay: 'YYYY-MM' (string); $faturalar: koleksiyon (tedarikci, uyari sütunları dahil)
$ayParcalar = explode('-', $ay ?? date('Y-m'));
$ayYil  = (int) ($ayParcalar[0] ?? date('Y'));
$ayAy   = (int) ($ayParcalar[1] ?? date('n'));
$aylar  = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$ayMetin = ($aylar[$ayAy] ?? '') . ' ' . $ayYil;

$toplam = 0.0;
foreach ($faturalar as $f) { $toplam += (float) ($f->toplam ?? 0); }

$uyariRenk  = ['kirmizi' => '#F43F5E', 'sari' => '#F59E0B', 'yesil' => '#10B981'];
$uyariMetin = ['kirmizi' => 'Fiyat ↑', 'sari' => 'Fiyat ~', 'yesil' => 'Normal'];
$tl = fn($v) => number_format((float) round((float) $v), 0, ',', '.') . 'TL';
$mik = function ($v) {
    $v = (float) $v;
    return $v == round($v) ? number_format((int) $v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
};

// Web token (API çağrıları için Bearer)
$_pp = null;
try { if (function_exists('_posPersonel')) $_pp = _posPersonel(); } catch (\Throwable $e) {}
$webToken = $_pp ? (DB::table('personeller')->where('id', $_pp->id)->value('api_token') ?: '') : '';

// Gelecek aya geçiş engelleniyor (Flutter _ayDegis)
$buAy = (int) date('Ym');
$secAy = $ayYil * 100 + $ayAy;
$ileriKapali = ($secAy >= $buAy);

// Önceki / sonraki ay parametresi
$oncekiAy = date('Y-m', mktime(0, 0, 0, $ayAy - 1, 1, $ayYil));
$sonrakiAy = date('Y-m', mktime(0, 0, 0, $ayAy + 1, 1, $ayYil));
@endphp

@section('rk_alt', count($faturalar) . ' fatura · ' . $ayMetin)

@push('head')
<style>
/* ══════════════════════════════════════════════════════════════════════════
   ALIŞ FATURALARI — Flutter AlisFaturaScreen + AlisFaturaEkleScreen birebir
   ══════════════════════════════════════════════════════════════════════════ */
#af{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;
  --kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;
  --sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
}
body.koyu #af{
  --card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;
  --sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#0B1020;
}
#af{}

/* ── Ay seçici (Flutter IconButton chevron + Text) ─────────────────────── */
#af .af-ay{
  display:flex;align-items:center;gap:8px;margin-bottom:18px;
  flex-wrap:wrap;
}
#af .af-ay .ay-btn{
  display:inline-flex;align-items:center;justify-content:center;
  width:36px;height:36px;border-radius:10px;border:1.5px solid var(--line);
  background:var(--card);color:var(--mor1);cursor:pointer;text-decoration:none;
  transition:opacity .12s;
}
#af .af-ay .ay-btn:hover{opacity:.7}
#af .af-ay .ay-btn.kapali{color:var(--sub2);pointer-events:none;opacity:.5}
#af .af-ay .ay-btn .material-icons-outlined{font-size:20px}
#af .af-ay .ay-metin{
  font-size:15px;font-weight:800;color:var(--ink);min-width:120px;text-align:center;
}

/* ── Özet gradient kartı (Flutter MIstatKart / ozet konteyner) ─────────── */
#af .af-ozet{
  border-radius:18px;overflow:hidden;margin-bottom:20px;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));
  padding:20px 22px;
}
#af .af-ozet .sub { color:rgba(255,255,255,.70);font-size:12px;margin-bottom:4px; }
#af .af-ozet .buyuk{ color:#fff;font-size:28px;font-weight:900;letter-spacing:-.5px;line-height:1; }
#af .af-ozet .satirlar{ display:flex;flex-wrap:wrap;gap:6px 24px;margin-top:10px; }
#af .af-ozet .satir { color:rgba(255,255,255,.80);font-size:13px; }
#af .af-ozet .satir .e{ opacity:.75; }
#af .af-ozet .satir .d{ font-weight:700;margin-left:4px; }

/* ── Uyarı rozeti (Flutter MRozet / _uyariRenk) ────────────────────────── */
#af .af-rozet{
  display:inline-flex;align-items:center;
  padding:3px 10px;border-radius:20px;
  font-size:11.5px;font-weight:700;white-space:nowrap;
}

/* ── Tablo (Flutter MTablo, masaüstü) ───────────────────────────────────── */
#af .af-tablo{ background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;width:100%; }
#af .af-tablo table{ width:100%;border-collapse:collapse; }
#af .af-tablo thead th{
  background:var(--card2);color:var(--sub2);
  font-size:11.5px;font-weight:700;text-align:left;
  padding:11px 14px;letter-spacing:.5px;text-transform:uppercase;
}
#af .af-tablo thead th.sag{ text-align:right; }
#af .af-tablo thead th.ort{ text-align:center; }
#af .af-tablo tbody td{
  padding:10px 14px;font-size:13.5px;color:var(--ink);
  border-top:1px solid color-mix(in srgb,var(--line) 60%,transparent);
  vertical-align:middle;
}
#af .af-tablo tbody tr:first-child td{ border-top:none; }
#af .af-tablo tbody tr{ transition:background .1s; }
#af .af-tablo tbody tr:hover{ background:color-mix(in srgb,var(--mor1) 6%,transparent); }
#af .af-tablo .sag{ text-align:right; }
#af .af-tablo .ort{ text-align:center; }

/* ── "Aç" butonu satır aksiyon (Flutter MButon dolu:false) ───────────────── */
#af .af-ac-btn{
  display:inline-flex;align-items:center;gap:4px;
  height:30px;padding:0 10px;border-radius:8px;
  font-size:12px;font-weight:700;cursor:pointer;
  background:var(--card);border:1.5px solid var(--mor1);
  color:var(--mor1);transition:opacity .12s;
}
#af .af-ac-btn:hover{opacity:.75}
#af .af-ac-btn .material-icons-outlined{font-size:14px}

/* ── Mobil kart listesi (Flutter _kart) ─────────────────────────────────── */
#af .af-kartlar{ display:none; }
@media(max-width:720px){
  #af .af-tablo{ display:none; }
  #af .af-kartlar{ display:flex;flex-direction:column;gap:8px; }
}
#af .af-kart{
  background:var(--card);border:1px solid var(--line);
  border-radius:14px;padding:14px;cursor:pointer;
  display:flex;align-items:center;gap:12px;
  transition:opacity .1s;
}
#af .af-kart:hover{ opacity:.85; }
#af .af-kart .sol-serit{
  width:4px;height:40px;border-radius:2px;flex:none;
}
#af .af-kart .bilgi{ flex:1;min-width:0; }
#af .af-kart .bilgi .ad{ font-size:15px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
#af .af-kart .bilgi .alt{ font-size:12px;color:var(--sub);margin-top:2px; }
#af .af-kart .sag-alan{ text-align:right;flex-shrink:0; }
#af .af-kart .sag-alan .tutar{ font-size:15px;font-weight:800;color:var(--ink); }
#af .af-kart .chevron{ color:var(--sub2);font-size:20px;flex:none; }

/* ── Boş durum (Flutter Center text) ────────────────────────────────────── */
#af .af-bos{
  background:var(--card);border:1px solid var(--line);border-radius:14px;
  padding:48px 24px;text-align:center;color:var(--sub);
}

/* ── Modal arka plan (Flutter showModalBottomSheet overlay) ─────────────── */
.af-overlay{
  display:none;position:fixed;inset:0;z-index:60;
  align-items:flex-end;justify-content:center;
  background:rgba(10,14,25,.55);padding:0;
}
.af-overlay.acik{ display:flex; }
@media(min-width:720px){
  .af-overlay{ align-items:center;padding:16px; }
  .af-overlay .af-sheet{ border-radius:18px !important;max-height:92vh; }
}

/* ── Bottom sheet (Flutter DraggableScrollableSheet) ───────────────────── */
.af-sheet{
  background:var(--card);border-radius:24px 24px 0 0;
  width:100%;max-width:540px;
  max-height:90vh;overflow-y:auto;
  padding:20px 18px 28px;
}
.af-sheet .af-tutac{
  width:40px;height:4px;border-radius:2px;
  background:var(--line);margin:0 auto 16px;
}
.af-sheet .af-sh-baslik{ font-size:18px;font-weight:800;color:var(--ink);margin:0 0 2px; }
.af-sheet .af-sh-alt{ font-size:12px;color:var(--sub);margin-bottom:14px; }

/* ── Kalem satırı (Flutter _kalemSatir / _kalemKart) ────────────────────── */
.af-kalem{
  display:flex;align-items:flex-start;gap:10px;
  padding:10px 0;border-top:1px solid var(--line);
}
.af-kalem:first-child{ border-top:none; }
.af-kalem .af-km-sol{ flex:1;min-width:0; }
.af-kalem .af-km-ad{ font-size:14px;font-weight:600;color:var(--ink); }
.af-kalem .af-km-alt{ display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin-top:2px; }
.af-kalem .af-km-alt .det{ font-size:12px;color:var(--sub); }
.af-kalem .af-km-fark{
  display:inline-flex;align-items:center;
  padding:1px 6px;border-radius:6px;
  font-size:11px;font-weight:700;
}
.af-kalem .sag{ font-size:13.5px;font-weight:700;color:var(--ink);flex-shrink:0; }
.af-toplam{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 0 0;margin-top:8px;border-top:1.5px solid var(--line);
}
.af-toplam .et{ font-size:15px;font-weight:800;color:var(--ink); }
.af-toplam .ev{ font-size:18px;font-weight:900;color:var(--yesil); }

/* ── Sil butonu (Flutter OutlinedButton kırmızı) ────────────────────────── */
.af-sil-btn{
  width:100%;display:flex;align-items:center;justify-content:center;gap:8px;
  height:46px;border:1.5px solid var(--kirmizi);border-radius:12px;
  background:transparent;color:var(--kirmizi);
  font-size:14px;font-weight:700;cursor:pointer;margin-top:16px;
  transition:opacity .12s;
}
.af-sil-btn:hover{ opacity:.75; }
.af-sil-btn .material-icons-outlined{ font-size:18px; }

/* ═══════════════════════════════════════════════════════════════════
   YENİ FATURA GİRİŞİ (Flutter AlisFaturaEkleScreen)
   ═══════════════════════════════════════════════════════════════════ */
/* ── Form input (Flutter _dec) ──────────────────────────────────────────── */
.af-inp{
  width:100%;padding:10px 12px;font-size:14px;
  border:1.5px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);
  outline:none;box-sizing:border-box;
  transition:border-color .15s;
}
.af-inp:focus{ border-color:var(--mor1); }
body.koyu .af-inp::placeholder{ color:var(--sub2); }
.af-lbl{ font-size:12.5px;font-weight:600;color:var(--sub);margin:12px 0 4px;display:block; }

/* ── Kalem kart (Flutter _kalemKart) ────────────────────────────────────── */
.af-ekle-kalem{
  background:var(--card2);border:1px solid var(--line);border-radius:12px;
  padding:12px 14px;margin-top:8px;
  display:flex;align-items:center;gap:10px;
}
.af-ekle-kalem .af-km-ad{ font-size:14px;font-weight:600;color:var(--ink); }
.af-ekle-kalem .af-km-det{ font-size:12px;color:var(--sub);margin-top:2px; }
.af-ekle-kalem .af-km-tutar{ font-size:13.5px;font-weight:700;color:var(--ink);flex-shrink:0; }
.af-ekle-kalem .af-km-sil{
  background:none;border:none;color:var(--sub2);cursor:pointer;
  padding:4px;border-radius:6px;display:flex;align-items:center;
}
.af-ekle-kalem .af-km-sil:hover{ color:var(--kirmizi); }
.af-ekle-kalem .af-km-sil .material-icons-outlined{ font-size:18px; }

/* ── Alt çubuk (Flutter _altBar) ─────────────────────────────────────────── */
.af-alt-bar{
  position:sticky;bottom:0;
  background:var(--card);border-top:1px solid var(--line);
  padding:12px 18px 18px;
  display:flex;align-items:center;gap:16px;margin-top:16px;
}
.af-alt-bar .sol-bilgi .et{ font-size:12px;color:var(--sub); }
.af-alt-bar .sol-bilgi .ev{ font-size:20px;font-weight:800;color:var(--ink); }
.af-kaydet-btn{
  flex:1;display:flex;align-items:center;justify-content:center;gap:8px;
  height:50px;border:none;border-radius:12px;
  background:var(--mor1);color:#fff;
  font-size:15px;font-weight:800;cursor:pointer;
  transition:opacity .12s;
}
.af-kaydet-btn:hover:not(:disabled){ opacity:.85; }
.af-kaydet-btn:disabled{ opacity:.55;cursor:default; }

/* ── Kalem modal (Flutter showModalBottomSheet) ─────────────────────────── */
.af-kalem-bos{
  padding:16px 0;color:var(--sub);font-size:13px;text-align:center;
}

/* ── Yeni Fatura butonu (Flutter FAB / MButon amber) ────────────────────── */
#afYeniBtn{
  display:inline-flex;align-items:center;gap:8px;
  padding:0 18px;height:40px;border-radius:10px;border:none;
  background:var(--amber);color:#fff;font-size:14px;font-weight:800;cursor:pointer;
  transition:opacity .12s;
}
#afYeniBtn:hover{ opacity:.85; }
#afYeniBtn .material-icons-outlined{ font-size:18px; }

/* ── Toast (Flutter SnackBar) ───────────────────────────────────────────── */
#afToast{
  position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
  background:#1e293b;color:#fff;font-size:14px;font-weight:600;
  padding:12px 24px;border-radius:12px;z-index:999;
  opacity:0;pointer-events:none;transition:opacity .25s;
  white-space:nowrap;max-width:90vw;
}
body.koyu #afToast{ background:#e2e8f0;color:#1e293b; }
#afToast.goruli{ opacity:1;pointer-events:auto; }
</style>
@endpush

@section('content')
<div id="af">

{{-- ══════════════════════════════════════════════════════════════════════
     ÜST SATIR: Ay seçici + Yeni Fatura
     (Flutter Row: chevron_left + ay metni + chevron_right + MButon)
     ══════════════════════════════════════════════════════════════════════ --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px">

  {{-- Ay seçici --}}
  <div class="af-ay" style="margin-bottom:0">
    <a class="af-ay-btn" href="/alis-fatura?ay={{ $oncekiAy }}" title="Önceki ay">
      <span class="material-icons-outlined">chevron_left</span>
    </a>
    <span class="ay-metin">{{ $ayMetin }}</span>
    <a class="af-ay-btn{{ $ileriKapali ? ' kapali' : '' }}" href="{{ $ileriKapali ? '#' : '/alis-fatura?ay='.$sonrakiAy }}" title="Sonraki ay">
      <span class="material-icons-outlined">chevron_right</span>
    </a>
  </div>

  {{-- Yeni Fatura butonu (sadece sahip) --}}
  @if ($duzenleyebilir)
    <button id="afYeniBtn" type="button" onclick="afYeniAc()">
      <span class="material-icons-outlined">note_add</span> Yeni Fatura
    </button>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════════════════
     ÖZET KART (Flutter gradient container — "Bu Ay Alış Toplamı")
     ══════════════════════════════════════════════════════════════════════ --}}
<div class="af-ozet">
  <div class="sub">{{ $ayMetin }}</div>
  <div class="buyuk">{{ $tl($toplam) }}</div>
  <div class="satirlar">
    <span class="satir"><span class="e">Bu Ay Alış Toplamı</span><span class="d">{{ $tl($toplam) }}</span></span>
    <span class="satir"><span class="e">Fatura Sayısı</span><span class="d">{{ count($faturalar) }}</span></span>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BOŞ DURUM
     ══════════════════════════════════════════════════════════════════════ --}}
@if (count($faturalar) === 0)
  <div class="af-bos">Bu ay fatura yok.</div>

@else

  {{-- ── MASAÜSTÜ: Tablo (Flutter MTablo) ──────────────────────────────── --}}
  <div class="af-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:13%">TARİH</th>
          <th style="width:32%">TEDARİKÇİ</th>
          <th class="sag" style="width:18%">TUTAR</th>
          <th class="ort" style="width:22%">DURUM</th>
          <th class="sag" style="width:15%">DETAY</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($faturalar as $f)
          @php
            $u  = (string) ($f->uyari ?? 'yesil');
            $rk = $uyariRenk[$u]  ?? '#10B981';
            $rm = $uyariMetin[$u] ?? 'Normal';
            $no = (string) ($f->fatura_no ?? '');
            $id = (int) ($f->id ?? 0);
          @endphp
          <tr>
            {{-- Tarih --}}
            <td style="font-size:13px;color:var(--sub2)">{{ $f->tarih ?? '' }}</td>
            {{-- Tedarikçi + fatura no --}}
            <td>
              <div style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $f->tedarikci ?? 'Bilinmiyor' }}</div>
              @if ($no !== '')
                <div style="font-size:11.5px;color:var(--sub)">No: {{ $no }}</div>
              @endif
            </td>
            {{-- Tutar --}}
            <td class="sag" style="font-size:13.5px;font-weight:700;color:var(--ink)">{{ $tl($f->toplam) }}</td>
            {{-- Durum rozeti --}}
            <td class="ort">
              <span class="af-rozet" style="background:color-mix(in srgb,{{ $rk }} 18%,transparent);color:{{ $rk }}">
                {{ $rm }}
              </span>
            </td>
            {{-- Detay butonu --}}
            <td class="sag">
              <button type="button" class="af-ac-btn" onclick="afDetayAc({{ $id }})">
                <span class="material-icons-outlined">open_in_new</span> Aç
              </button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- ── MOBİL: Kart listesi (Flutter _kart) ──────────────────────────── --}}
  <div class="af-kartlar">
    @foreach ($faturalar as $f)
      @php
        $u  = (string) ($f->uyari ?? 'yesil');
        $rk = $uyariRenk[$u]  ?? '#10B981';
        $no = (string) ($f->fatura_no ?? '');
        $id = (int) ($f->id ?? 0);
      @endphp
      <div class="af-kart" onclick="afDetayAc({{ $id }})">
        {{-- Sol renkli şerit (Flutter sol-serit) --}}
        <div class="sol-serit" style="background:{{ $rk }}"></div>
        {{-- Bilgi --}}
        <div class="bilgi">
          <div class="ad">{{ $f->tedarikci ?? 'Bilinmiyor' }}</div>
          <div class="alt">{{ $f->tarih ?? '' }}{{ $no !== '' ? ' · No: '.$no : '' }}</div>
        </div>
        {{-- Sağ: tutar --}}
        <div class="sag-alan">
          <div class="tutar">{{ $tl($f->toplam) }}</div>
        </div>
        <span class="material-icons-outlined chevron">chevron_right</span>
      </div>
    @endforeach
  </div>

@endif

{{-- ══════════════════════════════════════════════════════════════════════
     DETAY MODAL (Flutter showModalBottomSheet — _detay)
     ══════════════════════════════════════════════════════════════════════ --}}
<div id="afDetayOverlay" class="af-overlay" onclick="if(event.target===this)afDetayKapat()">
  <div class="af-sheet" id="afDetaySheet">
    <div class="af-tutac"></div>
    <div id="afDetayIcerik">
      {{-- JS tarafından doldurulur --}}
      <div style="text-align:center;padding:24px;color:var(--sub)">Yükleniyor…</div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     YENİ FATURA GİRİŞİ (Flutter AlisFaturaEkleScreen — modal)
     ══════════════════════════════════════════════════════════════════════ --}}
<div id="afEkleOverlay" class="af-overlay" onclick="if(event.target===this)afEkleKapat()">
  <div class="af-sheet" style="max-width:600px;border-radius:24px 24px 0 0;padding:0">

    {{-- Üst alan: başlık + kapat --}}
    <div style="padding:20px 18px 0">
      <div class="af-tutac"></div>
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h3 style="margin:0;font-size:18px;font-weight:800;color:var(--ink)">Yeni Alış Faturası</h3>
        <button type="button" onclick="afEkleKapat()" style="background:none;border:none;color:var(--sub);font-size:24px;line-height:1;cursor:pointer">×</button>
      </div>

      {{-- Tedarikçi seçimi (Flutter _drop Tedarikçi) --}}
      <label class="af-lbl" style="margin-top:0">Tedarikçi</label>
      <select id="afTedId" class="af-inp">
        <option value="">Seçiniz (opsiyonel)</option>
        @foreach ($tedarikciler as $t)
          <option value="{{ (int) $t->id }}">{{ $t->ad }}</option>
        @endforeach
      </select>

      {{-- Fatura No (Flutter TextField) --}}
      <label class="af-lbl">Fatura No (opsiyonel)</label>
      <input type="text" id="afFaturaNo" class="af-inp" placeholder="Fatura No">

      {{-- Tarih (ek alan — web'e özgü iyileştirme, Flutter'da bugünkü tarih) --}}
      <label class="af-lbl">Tarih</label>
      <input type="date" id="afTarih" class="af-inp" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">

      {{-- Kalemler başlık + ekle (Flutter Row + barkod + "Kalem Ekle") --}}
      <div style="display:flex;align-items:center;justify-content:space-between;margin:18px 0 4px">
        <span style="font-size:15px;font-weight:800;color:var(--ink)">Kalemler</span>
        <button type="button" onclick="afKalemEkleAc()" style="
          display:inline-flex;align-items:center;gap:4px;
          background:none;border:1.5px solid var(--mavi);border-radius:9px;
          color:var(--mavi);font-size:13px;font-weight:700;padding:5px 12px;cursor:pointer;
        ">
          <span class="material-icons-outlined" style="font-size:16px">add</span> Kalem Ekle
        </button>
      </div>

      {{-- Kalem listesi (Flutter _kalemKart) --}}
      <div id="afKalemListesi">
        <div class="af-kalem-bos" id="afKalemBos">Henüz kalem yok. "Kalem Ekle" ile malzeme girin.</div>
      </div>
    </div>

    {{-- Alt çubuk: toplam + Kaydet (Flutter _altBar) --}}
    <div class="af-alt-bar">
      <div class="sol-bilgi">
        <div class="et">Toplam</div>
        <div class="ev" id="afEkleToplam">0TL</div>
      </div>
      <button id="afKaydetBtn" class="af-kaydet-btn" onclick="afKaydet()" disabled>
        Kaydet &amp; Stoğa Al
      </button>
    </div>

  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     KALEM EKLEME ALT MODAL (Flutter showModalBottomSheet — _kalemEkle)
     ══════════════════════════════════════════════════════════════════════ --}}
<div id="afKalemOverlay" class="af-overlay" onclick="if(event.target===this)afKalemKapat()">
  <div class="af-sheet" style="max-width:480px;border-radius:24px 24px 0 0">
    <div class="af-tutac"></div>
    <h3 style="margin:0 0 14px;font-size:18px;font-weight:800;color:var(--ink)">Kalem Ekle</h3>

    {{-- Malzeme seçimi (Flutter _drop Malzeme) --}}
    <label class="af-lbl" style="margin-top:0">Malzeme</label>
    <select id="afKalemMalzId" class="af-inp" onchange="afMalzDegis(this.value)">
      @foreach ($malzemeler as $m)
        <option value="{{ (int) $m->id }}" data-birim="{{ (int) $m->temel_birim_id }}">{{ $m->ad }}</option>
      @endforeach
    </select>

    {{-- Miktar + Birim (Flutter Row) --}}
    <div style="display:flex;gap:10px;margin-top:0">
      <div style="flex:1">
        <label class="af-lbl">Miktar</label>
        <input type="number" id="afKalemMiktar" class="af-inp" placeholder="0" min="0" step="any">
      </div>
      <div style="flex:1">
        <label class="af-lbl">Birim</label>
        <select id="afKalemBirimId" class="af-inp">
          @foreach ($birimler as $b)
            <option value="{{ (int) $b->id }}">{{ $b->kisaltma }}</option>
          @endforeach
        </select>
      </div>
    </div>

    {{-- Birim Fiyat (Flutter TextField) --}}
    <label class="af-lbl">Birim Fiyat (₺ / birim)</label>
    <input type="number" id="afKalemFiyat" class="af-inp" placeholder="0.00" min="0" step="any">

    {{-- Ekle butonu (Flutter FilledButton) --}}
    <button type="button" onclick="afKalemOnayla()" style="
      width:100%;height:48px;border:none;border-radius:12px;
      background:var(--mor1);color:#fff;font-size:15px;font-weight:800;
      cursor:pointer;margin-top:16px;transition:opacity .12s;
    ">Ekle</button>
  </div>
</div>

{{-- Toast --}}
<div id="afToast"></div>

</div>{{-- #af --}}

@push('scripts')
<script>
// ─────────────────────────────────────────────────────────────────────────────
// ALIŞ FATURALARI — Flutter AlisFaturaScreen + AlisFaturaEkleScreen web eşdeğeri
// ─────────────────────────────────────────────────────────────────────────────
const AF_TOKEN = '{{ $webToken }}'; // api_token (Bearer, Flutter AuthProvider.token)
const AF_DUZENLEYEBILIR = {{ $duzenleyebilir ? 'true' : 'false' }};

// Sunucu-render birimler (kalem ekle formunda)
const AF_BIRIMLER = {!! json_encode(
    $birimler->map(fn($b) => ['id' => (int) $b->id, 'ad' => $b->ad, 'kisaltma' => $b->kisaltma])->values()
) !!};
// Sunucu-render malzemeler (temel_birim_id içerir)
const AF_MALZEMELER = {!! json_encode(
    $malzemeler->map(fn($m) => ['id' => (int) $m->id, 'ad' => $m->ad, 'temel_birim_id' => (int) $m->temel_birim_id])->values()
) !!};

// Aktif yeni fatura kalemleri [{malzeme_id, malzeme_ad, birim_id, birim_ad, miktar, birim_fiyat}]
let _afKalemler = [];

// ── Yardımcılar ─────────────────────────────────────────────────────────────
function _afTl(v)  { return Math.round(parseFloat(v)||0).toLocaleString('tr-TR') + 'TL'; }
function _afMik(v) {
    v = parseFloat(v) || 0;
    return v === Math.round(v) ? Math.round(v).toLocaleString('tr-TR') : v.toFixed(2).replace('.', ',');
}
function _esc(s) {
    return (s == null ? '' : String(s))
        .replace(/[&<>"]/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; });
}
function afToast(msg) {
    var t = document.getElementById('afToast');
    t.textContent = msg; t.classList.add('goruli');
    clearTimeout(t._to);
    t._to = setTimeout(function(){ t.classList.remove('goruli'); }, 2800);
}

// ── API çağrısı (Bearer token — Flutter Api._post ile aynı form-encoded) ────
// Flutter _post: Map<String,String> body → application/x-www-form-urlencoded.
// Backend json_decode((string) $r->kalemler, true) bu formata göre yazılmış.
async function afApi(url, data) {
    var headers = { 'Accept': 'application/json', 'Authorization': 'Bearer ' + AF_TOKEN };
    var opt = { method: 'GET', headers: headers };
    if (data !== undefined) {
        opt.method = 'POST';
        headers['X-CSRF-TOKEN'] = window.csrf;
        // form-encoded (Flutter'ın _post ile aynı şekil; kalemler JSON string olarak gider)
        var parts = [];
        Object.keys(data).forEach(function(k) {
            var v = data[k];
            if (v === null || v === undefined) v = '';
            parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(v)));
        });
        headers['Content-Type'] = 'application/x-www-form-urlencoded';
        opt.body = parts.join('&');
    }
    var r = await fetch(url, opt);
    return r.json();
}

// ═══════════════════════════════════════════════════════════════════════════
// DETAY MODAL (Flutter _detay + showModalBottomSheet)
// GET /api/patron/alis-fatura-detay?id=X
// ═══════════════════════════════════════════════════════════════════════════
var _afDetayId = null;

async function afDetayAc(id) {
    _afDetayId = id;
    var icerik = document.getElementById('afDetayIcerik');
    icerik.innerHTML = '<div style="text-align:center;padding:32px;color:var(--sub)">Yükleniyor…</div>';
    document.getElementById('afDetayOverlay').classList.add('acik');
    try {
        var d = await afApi('/api/patron/alis-fatura-detay?id=' + id);
        if (d.ok !== 1) { icerik.innerHTML = '<p style="color:var(--kirmizi);padding:24px">Detay alınamadı.</p>'; return; }
        icerik.innerHTML = _afDetayHtml(d, id);
    } catch(e) {
        icerik.innerHTML = '<p style="color:var(--kirmizi);padding:24px">Bağlantı hatası.</p>';
    }
}
function afDetayKapat() { document.getElementById('afDetayOverlay').classList.remove('acik'); }

function _afDetayHtml(d, id) {
    var uyariRenk = { kirmizi: '#F43F5E', sari: '#F59E0B', yesil: '#10B981' };
    var no = d.fatura_no ? ' · No: ' + _esc(d.fatura_no) : '';
    var html = '<div class="af-sh-baslik">' + _esc(d.tedarikci || 'Fatura') + '</div>'
             + '<div class="af-sh-alt">' + _esc(d.tarih || '') + no + '</div>';

    // Kalemler (Flutter _kalemSatir)
    (d.kalemler || []).forEach(function(k) {
        var u   = k.uyari || 'yesil';
        var rk  = uyariRenk[u] || '#10B981';
        var fark = parseFloat(k.fiyat_farki_yuzde) || 0;
        var farkHtml = '';
        if (u !== 'yesil' && fark !== 0) {
            farkHtml = '<span class="af-km-fark" style="background:color-mix(in srgb,'
                + rk + ' 18%,transparent);color:' + rk + '">'
                + (fark > 0 ? '+' : '') + Math.round(fark) + '%</span>';
        }
        html += '<div class="af-kalem">'
            + '<div class="af-km-sol">'
            + '<div class="af-km-ad">' + _esc(k.malzeme) + '</div>'
            + '<div class="af-km-alt">'
            + '<span class="det">' + _afMik(k.miktar) + ' ' + _esc(k.birim) + ' × ' + _afTl(k.birim_fiyat) + '</span>'
            + farkHtml
            + '</div>'
            + '</div>'
            + '<div class="sag">' + _afTl(k.satir_toplam) + '</div>'
            + '</div>';
    });

    // Toplam satırı (Flutter Divider + Row)
    html += '<div class="af-toplam"><span class="et">TOPLAM</span><span class="ev">' + _afTl(d.toplam) + '</span></div>';

    // Sil butonu (sadece sahip — Flutter duzenleyebilir OutlinedButton)
    if (AF_DUZENLEYEBILIR) {
        html += '<button type="button" class="af-sil-btn" onclick="afSilOnayla(' + id + ')">'
            + '<span class="material-icons-outlined">delete_outline</span> Faturayı Sil (stok geri alınır)'
            + '</button>';
    }
    return html;
}

// ── Silme (Flutter _faturaSil → POST /api/patron/alis-fatura-sil) ───────────
async function afSilOnayla(id) {
    if (!confirm('Bu faturayı silmek istediğinizden emin misiniz?\nStok hareketi de geri alınır.')) return;
    afDetayKapat();
    try {
        var r = await afApi('/api/patron/alis-fatura-sil', { id: id });
        if (r.ok === 1) {
            afToast('Fatura silindi.');
            setTimeout(function(){ location.reload(); }, 700);
        } else {
            afToast(r.hata || 'Silinemedi');
        }
    } catch(e) {
        afToast('Bağlantı hatası');
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// YENİ FATURA (Flutter AlisFaturaEkleScreen)
// POST /api/patron/alis-fatura-kaydet
// ═══════════════════════════════════════════════════════════════════════════
function afYeniAc() {
    _afKalemler = [];
    document.getElementById('afTedId').value     = '';
    document.getElementById('afFaturaNo').value  = '';
    document.getElementById('afTarih').value     = '{{ date('Y-m-d') }}';
    _afKalemlerRender();
    document.getElementById('afEkleOverlay').classList.add('acik');
}
function afEkleKapat() {
    document.getElementById('afEkleOverlay').classList.remove('acik');
}

// ── Toplam hesaplama (Flutter get _toplam) ───────────────────────────────────
function _afEkleToplam() {
    return _afKalemler.reduce(function(s, k){ return s + parseFloat(k.miktar) * parseFloat(k.birim_fiyat); }, 0);
}

// ── Kalem listesini yeniden render et (Flutter _kalemKart) ──────────────────
function _afKalemlerRender() {
    var el = document.getElementById('afKalemListesi');
    var bos = document.getElementById('afKalemBos');
    var btn = document.getElementById('afKaydetBtn');
    document.getElementById('afEkleToplam').textContent = _afTl(_afEkleToplam());
    if (_afKalemler.length === 0) {
        el.innerHTML = '';
        el.appendChild(bos);
        btn.disabled = true;
        return;
    }
    bos.remove();
    var html = '';
    _afKalemler.forEach(function(k, i) {
        var satir = parseFloat(k.miktar) * parseFloat(k.birim_fiyat);
        html += '<div class="af-ekle-kalem">'
            + '<div style="flex:1;min-width:0">'
            + '<div class="af-km-ad">' + _esc(k.malzeme_ad) + '</div>'
            + '<div class="af-km-det">' + _afMik(k.miktar) + ' ' + _esc(k.birim_ad) + ' × ' + _afTl(k.birim_fiyat) + '</div>'
            + '</div>'
            + '<span class="af-km-tutar">' + _afTl(satir) + '</span>'
            + '<button type="button" class="af-km-sil" onclick="_afKalemSil(' + i + ')" title="Kaldır">'
            + '<span class="material-icons-outlined">close</span></button>'
            + '</div>';
    });
    el.innerHTML = html;
    btn.disabled = false;
}

function _afKalemSil(i) {
    _afKalemler.splice(i, 1);
    _afKalemlerRender();
}

// ── Kalem ekleme modalı (Flutter _kalemEkle showModalBottomSheet) ───────────
function afKalemEkleAc() {
    if (AF_MALZEMELER.length === 0) { afToast('Önce Stok ekranından malzeme ekleyin'); return; }
    // İlk malzemeyi varsayılan seç
    var sel = document.getElementById('afKalemMalzId');
    if (sel.options.length > 0) {
        sel.selectedIndex = 0;
        afMalzDegis(sel.value);
    }
    document.getElementById('afKalemMiktar').value = '';
    document.getElementById('afKalemFiyat').value  = '';
    document.getElementById('afKalemOverlay').classList.add('acik');
    setTimeout(function(){ document.getElementById('afKalemMiktar').focus(); }, 50);
}
function afKalemKapat() { document.getElementById('afKalemOverlay').classList.remove('acik'); }

// Malzeme değişince temel birimi seç (Flutter _kalemEkle birimId = temel_birim_id)
function afMalzDegis(malzId) {
    var m = AF_MALZEMELER.find(function(x){ return x.id == malzId; });
    if (!m) return;
    var birimSel = document.getElementById('afKalemBirimId');
    for (var i = 0; i < birimSel.options.length; i++) {
        if (parseInt(birimSel.options[i].value) === m.temel_birim_id) {
            birimSel.selectedIndex = i; break;
        }
    }
}

// Kalem onayla → listeye ekle (Flutter _kalemEkle onPressed)
function afKalemOnayla() {
    var malzId  = parseInt(document.getElementById('afKalemMalzId').value);
    var birimId = parseInt(document.getElementById('afKalemBirimId').value);
    var miktar  = parseFloat(document.getElementById('afKalemMiktar').value.replace(',', '.')) || 0;
    var fiyat   = parseFloat(document.getElementById('afKalemFiyat').value.replace(',', '.'));
    if (isNaN(fiyat)) fiyat = 0;
    if (miktar <= 0 || fiyat < 0) { afToast('Geçerli miktar ve fiyat girin'); return; }

    var m = AF_MALZEMELER.find(function(x){ return x.id === malzId; });
    var b = AF_BIRIMLER.find(function(x){ return x.id === birimId; });
    if (!m || !b) { afToast('Malzeme veya birim bulunamadı'); return; }

    _afKalemler.push({
        malzeme_id:  m.id,
        malzeme_ad:  m.ad,
        birim_id:    b.id,
        birim_ad:    b.kisaltma,
        miktar:      miktar,
        birim_fiyat: fiyat,
    });
    afKalemKapat();
    _afKalemlerRender();
}

// ── Kaydet (Flutter _kaydet → POST /api/patron/alis-fatura-kaydet) ──────────
async function afKaydet() {
    if (_afKalemler.length === 0) return;
    var btn = document.getElementById('afKaydetBtn');
    btn.disabled = true;
    btn.textContent = 'Kaydediliyor…';

    var gonder = _afKalemler.map(function(k){
        return { malzeme_id: k.malzeme_id, birim_id: k.birim_id, miktar: k.miktar, birim_fiyat: k.birim_fiyat };
    });

    try {
        var r = await afApi('/api/patron/alis-fatura-kaydet', {
            tedarikci_id: document.getElementById('afTedId').value || null,
            fatura_no:    document.getElementById('afFaturaNo').value.trim() || null,
            tarih:        document.getElementById('afTarih').value || '{{ date('Y-m-d') }}',
            kalemler:     JSON.stringify(gonder),
        });
        if (r.ok === 1) {
            afEkleKapat();
            afToast('Fatura kaydedildi, stok güncellendi.');
            setTimeout(function(){ location.reload(); }, 700);
        } else {
            afToast(r.hata || 'Kaydedilemedi');
            btn.disabled = false;
            btn.textContent = 'Kaydet & Stoğa Al';
        }
    } catch(e) {
        afToast('Bağlantı hatası');
        btn.disabled = false;
        btn.textContent = 'Kaydet & Stoğa Al';
    }
}

// (Ay geçiş linkleri server-side <a> etiketiyle render edildi, JS gerekmez.)
</script>
@endpush
@endsection

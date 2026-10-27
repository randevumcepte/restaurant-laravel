@extends('layout.app')
@section('title', 'Giderler')
@section('rk_ikon', 'receipt_long')
@section('rk_geri', '/stok')
@section('baslik', 'Giderler')

@push('head')
<style>
/* ================================================================
   GİDER SAYFASI — Flutter gider_screen.dart birebir
   ================================================================ */
#gdroot {}

/* --- Ay navigasyon çubuğu --- */
#gdroot .gd-nav {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
}
#gdroot .gd-nav-ay {
  display: inline-flex; align-items: center; gap: 2px;
}
#gdroot .gd-nav-ay .gd-nav-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e2e8f0;
  background: transparent; cursor: pointer; text-decoration: none; color: var(--mor1);
  transition: background .12s;
}
#gdroot .gd-nav-ay .gd-nav-btn:hover { background: color-mix(in srgb,var(--mor1) 10%,transparent); }
body.koyu #gdroot .gd-nav-ay .gd-nav-btn { border-color: #2c3650; }
#gdroot .gd-nav-ay .gd-nav-btn.devre { opacity: .35; pointer-events: none; }
#gdroot .gd-nav-ay .gd-ay-metin {
  min-width: 148px; text-align: center;
  font-weight: 700; font-size: 15px; color: var(--ink);
}

/* Gider Ekle butonu */
#gdroot .gd-ekle-btn {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--kirmizi); color: #fff; border: 0;
  border-radius: 12px; padding: 10px 18px; font-size: 13.5px; font-weight: 700;
  cursor: pointer; text-decoration: none;
}
#gdroot .gd-ekle-btn .material-icons-outlined { font-size: 18px; }

/* ----------------------------------------------------------------
   Toplam Gider kartı — Flutter _toplamKart:
   gradient LinearGradient(mor1→mavi), radius 18, beyaz chip'ler
   ---------------------------------------------------------------- */
#gdroot .gd-ozet {
  border-radius: 18px;
  background: linear-gradient(135deg, #7C3AED, #4F46E5);
  padding: 20px 22px;
  margin-bottom: 22px;
  box-shadow: 0 6px 18px rgba(124,58,237,.30);
}
#gdroot .gd-ozet .gd-ozet-lbl {
  color: rgba(255,255,255,.75); font-size: 12.5px; font-weight: 500; margin-bottom: 4px;
}
#gdroot .gd-ozet .gd-ozet-tutar {
  color: #fff; font-size: 30px; font-weight: 900; letter-spacing: -.5px; line-height: 1.15;
}
#gdroot .gd-ozet .gd-chips {
  display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px;
}
#gdroot .gd-ozet .gd-chip {
  display: inline-flex; align-items: center;
  background: rgba(255,255,255,.16); border-radius: 10px;
  padding: 5px 11px; font-size: 11.5px; font-weight: 600; color: #fff;
}

/* ----------------------------------------------------------------
   Gider listesi — Flutter _giderSatir / MTablo
   ---------------------------------------------------------------- */
#gdroot .gd-liste { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }

#gdroot .gd-satir {
  display: flex; align-items: center; gap: 12px;
  background: #fff; border: 1px solid #e2e8f0;
  border-radius: 14px; padding: 13px 14px;
  transition: box-shadow .12s;
}
body.koyu #gdroot .gd-satir { background: #1b2235; border-color: #2c3650; }
#gdroot .gd-satir:hover { box-shadow: 0 3px 10px rgba(0,0,0,.08); }

/* İkon çember — Flutter CircleAvatar radius:18 */
#gdroot .gd-ikon {
  width: 38px; height: 38px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
#gdroot .gd-ikon .material-icons-outlined { font-size: 18px; }

/* Açıklama + kategori · tarih */
#gdroot .gd-bilgi { flex: 1; min-width: 0; }
#gdroot .gd-bilgi .gd-aciklama {
  font-size: 14px; font-weight: 600; color: var(--ink);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#gdroot .gd-bilgi .gd-alt {
  font-size: 11px; color: var(--sub2); margin-top: 2px;
}

/* Tutar */
#gdroot .gd-tutar {
  font-size: 15px; font-weight: 800; color: var(--ink); white-space: nowrap;
}

/* Sil butonu */
#gdroot .gd-sil-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 30px; height: 30px; border: 0; background: transparent;
  border-radius: 8px; cursor: pointer; color: var(--sub2); flex-shrink: 0;
}
#gdroot .gd-sil-btn:hover { background: color-mix(in srgb,var(--kirmizi) 12%,transparent); color: var(--kirmizi); }
#gdroot .gd-sil-btn .material-icons-outlined { font-size: 17px; pointer-events: none; }

/* Boş durum */
#gdroot .gd-bos {
  text-align: center; padding: 48px 20px; color: var(--sub);
  font-size: 14px; font-weight: 500;
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
}
body.koyu #gdroot .gd-bos { background: #1b2235; border-color: #2c3650; }

/* ----------------------------------------------------------------
   Gider Ekle modal overlay — Flutter showModalBottomSheet
   ---------------------------------------------------------------- */
#gdModal {
  display: none; position: fixed; inset: 0; z-index: 1050;
  background: rgba(0,0,0,.48); align-items: flex-end; justify-content: center;
}
#gdModal.acik { display: flex; }
#gdModal .gd-sheet {
  background: #fff; border-radius: 20px 20px 0 0;
  padding: 20px 20px calc(20px + env(safe-area-inset-bottom));
  width: 100%; max-width: 600px; box-shadow: 0 -8px 30px rgba(0,0,0,.15);
}
body.koyu #gdModal .gd-sheet { background: #0b1020; }

#gdModal .gd-sheet .gd-handle {
  width: 40px; height: 4px; background: #2D3752; border-radius: 2px;
  margin: 0 auto 18px;
}
#gdModal .gd-sheet .gd-modal-baslik {
  font-size: 18px; font-weight: 700; color: var(--ink); margin-bottom: 16px;
}

#gdModal .gd-alan { margin-bottom: 12px; }
#gdModal .gd-alan label {
  display: block; font-size: 12px; font-weight: 600;
  color: var(--sub); margin-bottom: 5px;
}
#gdModal .gd-alan select,
#gdModal .gd-alan input[type="text"],
#gdModal .gd-alan input[type="number"],
#gdModal .gd-alan input[type="date"] {
  width: 100%; border: 1px solid #2D3752; border-radius: 10px;
  background: transparent; color: var(--ink); font-size: 14px;
  padding: 11px 13px; outline: none; box-sizing: border-box;
  transition: border-color .15s;
}
body:not(.koyu) #gdModal .gd-alan select,
body:not(.koyu) #gdModal .gd-alan input { border-color: #e2e8f0; }
#gdModal .gd-alan select:focus,
#gdModal .gd-alan input:focus { border-color: var(--mor1); }
#gdModal .gd-alan select option { background: #1b2235; color: #e2e8f0; }
body:not(.koyu) #gdModal .gd-alan select option { background: #fff; color: #1e293b; }

#gdModal .gd-kaydet-btn {
  width: 100%; border: 0; border-radius: 12px;
  background: var(--mor1); color: #fff;
  font-size: 15px; font-weight: 700;
  padding: 14px; cursor: pointer; margin-top: 4px;
  transition: opacity .12s;
}
#gdModal .gd-kaydet-btn:hover { opacity: .88; }

/* Flash mesaj */
.rk-flash.ok  { background:color-mix(in srgb,var(--yesil) 12%,transparent); color:var(--yesil); border-left:3px solid var(--yesil); border-radius:10px; padding:10px 14px; margin-bottom:14px; font-weight:600; font-size:13.5px; }
.rk-flash.err { background:color-mix(in srgb,var(--kirmizi) 12%,transparent); color:var(--kirmizi); border-left:3px solid var(--kirmizi); border-radius:10px; padding:10px 14px; margin-bottom:14px; font-weight:600; font-size:13.5px; }
</style>
@endpush

@section('content')
@php
  $katAd   = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme', 'maas' => 'Maaş / Personel', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $katIkon = ['kira' => 'home_work', 'fatura' => 'receipt_long', 'malzeme' => 'inventory_2', 'maas' => 'groups', 'vergi' => 'account_balance', 'diger' => 'more_horiz'];
  // Flutter _katRenk — CSS değişkeni yerine gerçek hex (chip bg hesabı için)
  $katHex  = ['kira' => '#D97706', 'fatura' => '#4F46E5', 'malzeme' => '#10B981', 'maas' => '#7C3AED', 'vergi' => '#F43F5E', 'diger' => '#94A3B8'];
  $aylar   = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

  $yil     = (int) substr($ay, 0, 4);
  $aySayi  = (int) substr($ay, 5, 2);
  $ayMetin = ($aylar[$aySayi] ?? '') . ' ' . $yil;

  $oncekiTs  = mktime(0, 0, 0, $aySayi - 1, 1, $yil);
  $sonrakiTs = mktime(0, 0, 0, $aySayi + 1, 1, $yil);
  $onceki    = date('Y-m', $oncekiTs);
  $sonraki   = date('Y-m', $sonrakiTs);
  $sonrakiVar = $sonrakiTs <= mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));

  // Kategorileri tutara göre azalan sıralı (Flutter .sort((a,b)=>b.compareTo(a)))
  $kats = collect($kategoriler)->sortDesc();

  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
@endphp

<div id="gdroot">

  {{-- Flash mesajları --}}
  @if (session('ok'))
    <div class="rk-flash ok">{{ session('ok') }}</div>
  @endif
  @if (session('hata'))
    <div class="rk-flash err">{{ session('hata') }}</div>
  @endif

  {{-- ── Navigasyon + Gider Ekle butonu ── --}}
  <div class="gd-nav">
    <div class="gd-nav-ay">
      {{-- Önceki ay --}}
      <a href="/gider?ay={{ $onceki }}" class="gd-nav-btn" title="Önceki ay">
        <span class="material-icons-outlined" style="font-size:21px">chevron_left</span>
      </a>

      <span class="gd-ay-metin">{{ $ayMetin }}</span>

      {{-- Sonraki ay — gelecek aya geçiş engellenir (Flutter _ayDegis) --}}
      <span class="gd-nav-btn {{ $sonrakiVar ? '' : 'devre' }}"
            onclick="{{ $sonrakiVar ? 'window.location=\"/gider?ay='.$sonraki.'\"' : '' }}"
            title="Sonraki ay" style="{{ $sonrakiVar ? 'cursor:pointer' : '' }}">
        <span class="material-icons-outlined" style="font-size:21px">chevron_right</span>
      </span>
    </div>

    {{-- Flutter FloatingActionButton / MButon "Gider Ekle" --}}
    <button type="button" class="gd-ekle-btn" onclick="gdModalAc()">
      <span class="material-icons-outlined">add</span>
      Gider Ekle
    </button>
  </div>

  {{-- ── Toplam Gider özet kartı (Flutter _toplamKart — gradient + chip'ler) ── --}}
  <div class="gd-ozet">
    <div class="gd-ozet-lbl">Bu Ay Toplam Gider</div>
    <div class="gd-ozet-tutar">{{ $tl($toplam) }}</div>
    @if ($kats->isNotEmpty())
      <div class="gd-chips">
        @foreach ($kats as $k => $tutarKat)
          <span class="gd-chip">{{ $katAd[$k] ?? ucfirst($k) }}: {{ $tl($tutarKat) }}</span>
        @endforeach
      </div>
    @endif
  </div>

  {{-- ── Gider listesi (Flutter _giderSatir / MTablo) ── --}}
  @if (empty($giderler) || count($giderler) === 0)
    <div class="gd-bos">
      <span class="material-icons-outlined" style="font-size:38px;display:block;margin-bottom:8px;opacity:.45">receipt_long</span>
      Bu ay gider kaydı yok.
    </div>
  @else
    <div class="gd-liste">
      @foreach ($giderler as $g)
        @php
          $kat  = $g->kategori;
          $hex  = $katHex[$kat]  ?? '#94A3B8';
          $ikon = $katIkon[$kat] ?? 'more_horiz';
          $ad   = $katAd[$kat]   ?? ucfirst($kat);
          $acik = $g->aciklama ? $g->aciklama : $ad;
        @endphp
        <div class="gd-satir">
          {{-- Flutter CircleAvatar: backgroundColor = renk.withValues(alpha:.18) --}}
          <div class="gd-ikon" style="background:{{ $hex }}29">
            <span class="material-icons-outlined" style="color:{{ $hex }}">{{ $ikon }}</span>
          </div>

          {{-- Flutter Column: aciklama + kategori·tarih --}}
          <div class="gd-bilgi">
            <div class="gd-aciklama">{{ $acik }}</div>
            <div class="gd-alt">{{ $ad }} · {{ $g->tarih }}</div>
          </div>

          {{-- Flutter tutar --}}
          <div class="gd-tutar">{{ $tl($g->tutar) }}</div>

          {{-- Flutter sil butonu (maas hariç) --}}
          @if ($kat !== 'maas')
            <form method="POST" action="/gider/sil"
                  onsubmit="return rkOnayForm(this, 'Bu gideri silmek istediğinize emin misiniz?', 'var(--kirmizi)', 'Sil')"
                  style="margin:0;padding:0">
              @csrf
              <input type="hidden" name="id" value="{{ $g->id }}">
              <input type="hidden" name="ay" value="{{ $ay }}">
              <button type="submit" class="gd-sil-btn" title="Sil">
                <span class="material-icons-outlined">close</span>
              </button>
            </form>
          @else
            <span style="width:30px;flex-shrink:0"></span>
          @endif
        </div>
      @endforeach
    </div>
  @endif

</div>{{-- #gdroot --}}

{{-- ── Gider Ekle Modal (Flutter showModalBottomSheet + StatefulBuilder) ── --}}
<div id="gdModal" onclick="if(event.target===this)gdModalKapat()">
  <div class="gd-sheet">
    {{-- Flutter drag handle --}}
    <div class="gd-handle"></div>

    <div class="gd-modal-baslik">Yeni Gider</div>

    <form method="POST" action="/gider/ekle" id="gdEkleForm">
      @csrf
      <input type="hidden" name="ay" value="{{ $ay }}">

      {{-- Kategori (Flutter DropdownButton — maaş seçeneği YOK) --}}
      <div class="gd-alan">
        <label>Kategori</label>
        <select name="kategori" id="gdKategori">
          <option value="kira">Kira</option>
          <option value="fatura">Fatura (elektrik/su/doğalgaz)</option>
          <option value="malzeme">Malzeme / Alım</option>
          <option value="vergi">Vergi / Resmi</option>
          <option value="diger">Diğer</option>
        </select>
      </div>

      {{-- Tutar (Flutter keyboardType numberWithOptions decimal, autofocus) --}}
      <div class="gd-alan">
        <label>Tutar (TL)</label>
        <input type="number" name="tutar" id="gdTutar" step="0.01" min="0.01"
               placeholder="0" required inputmode="decimal" autofocus>
      </div>

      {{-- Tarih (web'e özgü: Flutter'da tarih picker yok, bugün default) --}}
      <div class="gd-alan">
        <label>Tarih</label>
        <input type="date" name="tarih" id="gdTarih" value="{{ date('Y-m-d') }}">
      </div>

      {{-- Açıklama (Flutter TextField opsiyonel) --}}
      <div class="gd-alan">
        <label>Açıklama (opsiyonel)</label>
        <input type="text" name="aciklama" id="gdAciklama"
               maxlength="200" placeholder="Örn. Eylül kirası">
      </div>

      {{-- Flutter FilledButton Kaydet --}}
      <button type="submit" class="gd-kaydet-btn">Kaydet</button>
    </form>
  </div>
</div>

<script>
function gdModalAc() {
  document.getElementById('gdModal').classList.add('acik');
  // Flutter autofocus tutar
  setTimeout(function() {
    var t = document.getElementById('gdTutar');
    if (t) t.focus();
  }, 120);
}
function gdModalKapat() {
  document.getElementById('gdModal').classList.remove('acik');
}
// ESC ile kapat
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') gdModalKapat();
});
// Form gönderilmeden önce tutar kontrolü (Flutter tutar <= 0 guard)
document.getElementById('gdEkleForm').addEventListener('submit', function(e) {
  var t = parseFloat(document.getElementById('gdTutar').value.replace(',', '.'));
  if (!t || t <= 0) {
    e.preventDefault();
    alert('Geçerli bir tutar girin.');
    document.getElementById('gdTutar').focus();
  }
});
</script>

@endsection

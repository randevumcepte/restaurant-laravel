@extends('layout.app')
@section('title', 'Tedarikçiler')
@section('rk_ikon', 'local_shipping_outlined')
@section('baslik', 'Tedarikçiler')

@php
  // Flutter tedarikci_screen BİREBİR: cari bilgi + toplam alış; ekle/düzenle/sil.
  $list = $tedarikciler ?? [];
  $toplamAlis   = 0;
  $toplamFatura = 0;
  foreach ($list as $t) {
      $toplamAlis   += (float) ($t->toplam_alis   ?? 0);
      $toplamFatura += (int)   ($t->fatura_sayisi ?? 0);
  }
  $adet = count($list);
  $tl   = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
@endphp

@section('rk_alt', $adet . ' tedarikçi')

@push('head')
<style>
/* ── kök değişkenler (Flutter TemaProvider renkleriyle birebir) ───────── */
#ted{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;
  --kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;
  --sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
}
body.koyu #ted{
  --card:#1b2235;--card2:#0f1526;--ink:#e2e8f0;
  --sub:#94a3b8;--sub2:#64748b;--line:#2d3650;
}

#ted{max-width:1280px}

/* ── Gradient özet kartı (Flutter MIstatKart, masaüstü) ─────────────── */
#ted .ted-ozet{
  border-radius:18px;overflow:hidden;margin-bottom:20px;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));
  padding:20px 22px;
}
#ted .ted-ozet .sub  {color:rgba(255,255,255,.70);font-size:13px;margin-bottom:6px;font-weight:600}
#ted .ted-ozet .buyuk{color:#fff;font-size:30px;font-weight:900;letter-spacing:-.5px;line-height:1;margin-bottom:10px}
#ted .ted-ozet .satirlar{display:flex;flex-wrap:wrap;gap:6px 24px}
#ted .ted-ozet .satir {color:rgba(255,255,255,.80);font-size:13px}
#ted .ted-ozet .satir .e{opacity:.75}
#ted .ted-ozet .satir .d{font-weight:700;margin-left:4px}

/* ── Tablo (Flutter MTablo / masaüstü) ──────────────────────────────── */
#ted .ted-tablo{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;width:100%}
#ted .ted-tablo table{width:100%;border-collapse:collapse}
#ted .ted-tablo thead th{
  background:var(--card2);color:var(--sub2);
  font-size:11.5px;font-weight:700;text-align:left;
  padding:11px 14px;letter-spacing:.5px;text-transform:uppercase;
}
#ted .ted-tablo thead th.sag{text-align:right}
#ted .ted-tablo tbody td{
  padding:10px 14px;font-size:13.5px;color:var(--ink);
  border-top:1px solid color-mix(in srgb,var(--line) 60%,transparent);
  vertical-align:middle;
}
#ted .ted-tablo tbody tr:first-child td{border-top:none}
#ted .ted-tablo tbody tr{transition:background .1s}
#ted .ted-tablo tbody tr:hover{background:color-mix(in srgb,var(--mor1) 6%,transparent)}
#ted .ted-tablo .sag{text-align:right}

/* ── Avatar baş harf (Flutter CircleAvatar) ─────────────────────────── */
#ted .ted-av{
  width:32px;height:32px;border-radius:50%;flex:none;
  background:color-mix(in srgb,var(--mor1) 20%,transparent);
  color:var(--mor1);font-size:13px;font-weight:900;
  display:flex;align-items:center;justify-content:center;
}

/* ── Tablo aksiyon butonları ─────────────────────────────────────────── */
#ted .ted-aksiyon{display:flex;align-items:center;justify-content:flex-end;gap:6px}
#ted .ted-aksiyon .btn{
  display:inline-flex;align-items:center;gap:4px;
  height:32px;padding:0 10px;border-radius:8px;
  font-size:12px;font-weight:700;cursor:pointer;
  background:var(--card);border:1.5px solid var(--line);color:var(--sub2);
  transition:opacity .12s;
}
#ted .ted-aksiyon .btn:hover{opacity:.75}
#ted .ted-aksiyon .btn.sil{color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)}
#ted .ted-aksiyon .btn .material-icons-outlined{font-size:15px}

/* ── Mobil kart listesi (Flutter _kart, mobil view) ─────────────────── */
#ted .ted-kartlar{display:none}
@media(max-width:720px){
  #ted .ted-tablo{display:none}
  #ted .ted-kartlar{display:flex;flex-direction:column;gap:8px}
}
#ted .ted-kart{
  background:var(--card);border:1px solid var(--line);
  border-radius:14px;padding:14px;
  display:flex;align-items:center;gap:12px;
}
#ted .ted-kart .ted-av-mob{
  width:40px;height:40px;border-radius:50%;flex:none;
  background:color-mix(in srgb,var(--mor1) 18%,transparent);
  color:var(--mor1);font-size:18px;font-weight:900;
  display:flex;align-items:center;justify-content:center;
}
#ted .ted-kart .bilgi{flex:1;min-width:0}
#ted .ted-kart .bilgi .ad{font-size:15px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#ted .ted-kart .bilgi .alt{font-size:12px;color:var(--sub);margin-top:2px}
#ted .ted-kart .sag-alan{text-align:right;flex-shrink:0}
#ted .ted-kart .sag-alan .tutar{font-size:14px;font-weight:800;color:var(--ink)}
#ted .ted-kart .sag-alan .etiket{font-size:10px;color:var(--sub);margin-top:1px}

/* ── Menü dropdown (Flutter PopupMenuButton) ─────────────────────────── */
#ted .ted-menu-wrap{position:relative;flex-shrink:0}
#ted .ted-menu-btn{
  background:none;border:none;cursor:pointer;
  color:var(--sub2);padding:4px;border-radius:6px;
  display:flex;align-items:center;
}
#ted .ted-menu-btn:hover{background:var(--card2)}
#ted .ted-menu-btn .material-icons-outlined{font-size:20px}
#ted .ted-dropdown{
  display:none;position:absolute;right:0;top:100%;z-index:40;
  background:var(--card);border:1px solid var(--line);
  border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.18);
  min-width:130px;overflow:hidden;
}
#ted .ted-dropdown.acik{display:block}
#ted .ted-dropdown button{
  display:flex;align-items:center;gap:8px;
  width:100%;padding:10px 14px;background:none;border:none;
  font-size:13.5px;font-weight:600;cursor:pointer;color:var(--ink);text-align:left;
}
#ted .ted-dropdown button:hover{background:var(--card2)}
#ted .ted-dropdown button.sil{color:var(--kirmizi)}
#ted .ted-dropdown .material-icons-outlined{font-size:17px}

/* ── Boş durum (Flutter Center text) ────────────────────────────────── */
#ted .ted-bos{
  background:var(--card);border:1px solid var(--line);border-radius:14px;
  padding:48px 24px;text-align:center;color:var(--sub);
}
</style>
@endpush

@section('content')
<div id="ted">

{{-- ====== Flash mesajları ====== --}}
@if (session('ted_ok'))
  <div class="rk-flash ok" style="margin-bottom:12px">{{ session('ted_ok') }}</div>
@endif
@if (session('ted_hata'))
  <div class="rk-flash err" style="margin-bottom:12px">{{ session('ted_hata') }}</div>
@endif

{{-- ====== Üst araç: Yeni Tedarikçi (Flutter MasaustuSayfa araç butonu) ====== --}}
<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <button type="button" onclick="tedAc()" class="rk-btn" style="background:var(--mor1);display:inline-flex;width:auto">
    <span class="material-icons-outlined" style="font-size:18px">add</span> Yeni Tedarikçi
  </button>
</div>

{{-- ====== Toplam Alış özet kartı (Flutter MIstatKart — sadece veri varsa) ====== --}}
@if ($adet)
  <div class="ted-ozet">
    <div class="sub">Toplam Alış</div>
    <div class="buyuk">{{ $tl($toplamAlis) }}</div>
    <div class="satirlar">
      <span class="satir"><span class="e">Tedarikçi adedi</span><span class="d">{{ $adet }}</span></span>
      <span class="satir"><span class="e">Toplam fatura</span><span class="d">{{ $toplamFatura }}</span></span>
    </div>
  </div>
@endif

{{-- ====== Boş durum ====== --}}
@if (!$adet)
  <div class="ted-bos">Tedarikçi yok. Yukarıdan ekleyin.</div>

@else

  {{-- ====== MASAÜSTÜ: Tablo (Flutter masaüstü MTablo) ====== --}}
  <div class="ted-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:30%">TEDARİKÇİ</th>
          <th style="width:20%">TELEFON</th>
          <th class="sag" style="width:20%">TOPLAM ALIŞ</th>
          <th class="sag" style="width:14%">FATURA</th>
          <th class="sag" style="width:16%"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($list as $t)
          @php
            $ad  = (string) ($t->ad       ?? '');
            $tel = (string) ($t->telefon  ?? '');
            $not = (string) ($t->aciklama ?? '');
            $bas = $ad !== '' ? mb_strtoupper(mb_substr($ad, 0, 1)) : '?';
          @endphp
          <tr>
            {{-- TEDARİKÇİ: avatar baş harf + ad --}}
            <td>
              <div style="display:flex;align-items:center;gap:10px;min-width:0">
                <span class="ted-av">{{ $bas }}</span>
                <span style="font-size:13.5px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $ad }}</span>
              </div>
            </td>
            {{-- TELEFON --}}
            <td>
              <span style="font-size:13px;color:{{ $tel !== '' ? 'var(--sub2)' : 'var(--sub)' }}">{{ $tel !== '' ? $tel : '—' }}</span>
            </td>
            {{-- TOPLAM ALIŞ --}}
            <td class="sag">
              <span style="font-size:13.5px;font-weight:800;color:var(--ink)">{{ $tl($t->toplam_alis ?? 0) }}</span>
            </td>
            {{-- FATURA sayısı --}}
            <td class="sag">
              <span style="font-size:13px;color:var(--sub)">{{ (int) ($t->fatura_sayisi ?? 0) }}</span>
            </td>
            {{-- Düzenle / Sil --}}
            <td class="sag">
              <div class="ted-aksiyon">
                <a href="/tedarikci-detay?id={{ (int) $t->id }}" class="btn" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">
                  <span class="material-icons-outlined">insights</span> Detay
                </a>
                <button type="button" class="btn"
                        onclick="tedAc({{ \Illuminate\Support\Js::from($t) }})">
                  <span class="material-icons-outlined">edit_outlined</span> Düzenle
                </button>
                <form action="/tedarikci/sil" method="POST" style="display:inline"
                      onsubmit="return confirm('{{ $ad }} silinecek. Bu işlem geri alınamaz.')">
                  @csrf
                  <input type="hidden" name="id" value="{{ (int) $t->id }}">
                  <button type="submit" class="btn sil">
                    <span class="material-icons-outlined">delete_outline</span> Sil
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- ====== MOBİL: Kart listesi (Flutter _kart) ====== --}}
  <div class="ted-kartlar">
    @foreach ($list as $t)
      @php
        $ad  = (string) ($t->ad       ?? '');
        $tel = (string) ($t->telefon  ?? '');
        $not = (string) ($t->aciklama ?? '');
        $bas = $ad !== '' ? mb_strtoupper(mb_substr($ad, 0, 1)) : '?';
        $mid = (int) $t->id;
      @endphp
      <div class="ted-kart">
        {{-- CircleAvatar --}}
        <span class="ted-av-mob">{{ $bas }}</span>
        {{-- Bilgi sütunu --}}
        <div class="bilgi">
          <div class="ad">{{ $ad }}</div>
          <div class="alt">{{ $tel !== '' ? $tel . ' · ' : '' }}{{ (int) ($t->fatura_sayisi ?? 0) }} fatura</div>
        </div>
        {{-- Sağ: tutar + etiket --}}
        <div class="sag-alan">
          <div class="tutar">{{ $tl($t->toplam_alis ?? 0) }}</div>
          <div class="etiket">toplam alış</div>
        </div>
        {{-- PopupMenuButton: more_vert → Düzenle / Sil --}}
        <div class="ted-menu-wrap" id="wrap-{{ $mid }}">
          <button class="ted-menu-btn" type="button"
                  onclick="tedMenuToggle({{ $mid }})">
            <span class="material-icons-outlined">more_vert</span>
          </button>
          <div class="ted-dropdown" id="drop-{{ $mid }}">
            <a href="/tedarikci-detay?id={{ $mid }}" style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;font-size:13.5px;font-weight:600;color:var(--mor1);text-decoration:none">
              <span class="material-icons-outlined">insights</span> Detay
            </a>
            <button type="button"
                    onclick="tedMenuKapat({{ $mid }});tedAc({{ \Illuminate\Support\Js::from($t) }})">
              <span class="material-icons-outlined">edit_outlined</span> Düzenle
            </button>
            <form action="/tedarikci/sil" method="POST"
                  onsubmit="return confirm('{{ $ad }} silinecek. Bu işlem geri alınamaz.')">
              @csrf
              <input type="hidden" name="id" value="{{ $mid }}">
              <button type="submit" class="sil">
                <span class="material-icons-outlined">delete_outline</span> Sil
              </button>
            </form>
          </div>
        </div>
      </div>
    @endforeach
  </div>

@endif

{{-- ====== Modal: Ekle / Düzenle (Flutter _form bottom sheet) ====== --}}
<div id="tedModal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgba(10,14,25,.6);padding:16px">
  <div style="background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.4);width:100%;max-width:440px;padding:22px">
    {{-- Başlık çubuğu (Flutter handle + başlık) --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 id="tedModalBaslik" style="font-size:18px;font-weight:800;color:var(--ink);margin:0">Yeni Tedarikçi</h3>
      <button type="button" onclick="tedKapat()" style="background:none;border:none;color:var(--sub);font-size:22px;line-height:1;cursor:pointer;padding:0 2px">&times;</button>
    </div>
    <form action="/tedarikci/kaydet" method="POST">
      @csrf
      <input type="hidden" name="id" id="tedId" value="">
      {{-- Flutter: TextField Firma/Kişi adı --}}
      <label class="rk-lbl" style="margin-top:0">Firma / Kişi adı</label>
      <input type="text" name="ad" id="tedAd" required placeholder="Firma / Kişi adı" class="rk-inp">
      {{-- Flutter: TextField Telefon --}}
      <label class="rk-lbl">Telefon</label>
      <input type="text" name="telefon" id="tedTel" placeholder="Telefon" class="rk-inp">
      {{-- Flutter: TextField Not --}}
      <label class="rk-lbl">Not (opsiyonel)</label>
      <input type="text" name="aciklama" id="tedAciklama" placeholder="Not (opsiyonel)" class="rk-inp">

      {{-- ZENGİN KART: Fatura & Cari bilgileri (aç/kapa) --}}
      <div style="margin-top:14px;border-top:1px dashed var(--line);padding-top:12px">
        <div onclick="tedDetayTog()" style="display:flex;align-items:center;gap:6px;cursor:pointer;color:var(--mor1);font-weight:700;font-size:13px;user-select:none">
          <span class="material-icons-outlined" id="tedDetayOk" style="font-size:18px">expand_more</span> Fatura & Cari Bilgileri (opsiyonel)
        </div>
        <div id="tedDetay" style="display:none;margin-top:10px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label class="rk-lbl" style="margin-top:0">Unvan</label><input type="text" name="unvan" id="tedUnvan" placeholder="Resmî unvan" class="rk-inp"></div>
            <div><label class="rk-lbl" style="margin-top:0">VKN / TCKN</label><input type="text" name="vkn_tckn" id="tedVkn" placeholder="Vergi/TC no" class="rk-inp"></div>
            <div><label class="rk-lbl" style="margin-top:0">Vergi Dairesi</label><input type="text" name="vergi_dairesi" id="tedVd" placeholder="Vergi dairesi" class="rk-inp"></div>
            <div><label class="rk-lbl" style="margin-top:0">Yetkili Kişi</label><input type="text" name="yetkili" id="tedYetkili" placeholder="Yetkili" class="rk-inp"></div>
            <div><label class="rk-lbl" style="margin-top:0">E-posta</label><input type="text" name="email" id="tedEmail" placeholder="E-posta" class="rk-inp"></div>
            <div><label class="rk-lbl" style="margin-top:0">Ödeme Vadesi (gün)</label><input type="number" name="odeme_vade_gun" id="tedVade" min="0" placeholder="Örn. 30" class="rk-inp"></div>
          </div>
          <label class="rk-lbl">IBAN</label>
          <input type="text" name="iban" id="tedIban" placeholder="TR.." class="rk-inp">
          <label class="rk-lbl">Adres</label>
          <input type="text" name="adres" id="tedAdres" placeholder="Adres" class="rk-inp">
        </div>
      </div>

      {{-- Flutter: FilledButton Kaydet --}}
      <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:16px">
        <span class="material-icons-outlined" style="font-size:20px">save</span> Kaydet
      </button>
    </form>
  </div>
</div>

</div>{{-- #ted --}}

<script>
// ── Modal: Ekle / Düzenle ──────────────────────────────────────────────────
function tedAc(o) {
  o = o || {};
  var g = function(id){ return document.getElementById(id); };
  g('tedModalBaslik').textContent = o.id ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi';
  g('tedId').value       = o.id        || '';
  g('tedAd').value       = o.ad        || '';
  g('tedTel').value      = o.telefon   || '';
  g('tedAciklama').value = o.aciklama  || '';
  g('tedUnvan').value    = o.unvan     || '';
  g('tedVkn').value      = o.vkn_tckn  || '';
  g('tedVd').value       = o.vergi_dairesi || '';
  g('tedYetkili').value  = o.yetkili   || '';
  g('tedEmail').value    = o.email     || '';
  g('tedVade').value     = (o.odeme_vade_gun !== null && o.odeme_vade_gun !== undefined) ? o.odeme_vade_gun : '';
  g('tedIban').value     = o.iban      || '';
  g('tedAdres').value    = o.adres     || '';
  // düzenlemede detayda veri varsa aç
  var detayDolu = o.unvan || o.vkn_tckn || o.vergi_dairesi || o.yetkili || o.email || o.iban || o.adres || o.odeme_vade_gun;
  g('tedDetay').style.display = detayDolu ? 'block' : 'none';
  g('tedDetayOk').textContent = detayDolu ? 'expand_less' : 'expand_more';
  g('tedModal').style.display = 'flex';
  g('tedAd').focus();
}
function tedDetayTog() {
  var d = document.getElementById('tedDetay'), ok = document.getElementById('tedDetayOk');
  var ac = d.style.display === 'none';
  d.style.display = ac ? 'block' : 'none';
  ok.textContent = ac ? 'expand_less' : 'expand_more';
}
function tedKapat() { document.getElementById('tedModal').style.display = 'none'; }
document.getElementById('tedModal').addEventListener('click', function (e) { if (e.target === this) tedKapat(); });

// ── Mobil PopupMenuButton dropdown ────────────────────────────────────────
function tedMenuToggle(id) {
  var d = document.getElementById('drop-' + id);
  var acik = d.classList.contains('acik');
  // diğer açık menüleri kapat
  document.querySelectorAll('#ted .ted-dropdown.acik').forEach(function (x) { x.classList.remove('acik'); });
  if (!acik) d.classList.add('acik');
}
function tedMenuKapat(id) {
  var d = document.getElementById('drop-' + id);
  if (d) d.classList.remove('acik');
}
// Dışarı tıklayınca tüm dropdown'ları kapat
document.addEventListener('click', function (e) {
  if (!e.target.closest('.ted-menu-wrap')) {
    document.querySelectorAll('#ted .ted-dropdown.acik').forEach(function (x) { x.classList.remove('acik'); });
  }
});
</script>
@endsection

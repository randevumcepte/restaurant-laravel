@extends('layout.app')
@section('title', 'Tedarikçiler')
@section('rk_ikon', 'local_shipping')
@section('baslik', 'Tedarikçiler')

@php
  // Flutter tedarikci_screen BİREBİR: cari bilgi + toplam alış; ekle/düzenle/sil.
  $list = $tedarikciler ?? [];
  $toplamAlis = 0; $toplamFatura = 0;
  foreach ($list as $t) {
    $toplamAlis   += (float) ($t->toplam_alis ?? 0);
    $toplamFatura += (int) ($t->fatura_sayisi ?? 0);
  }
  $adet = count($list);
  $tl = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
@endphp

@section('rk_alt', $adet . ' tedarikçi')

@section('content')

{{-- ====== Flash (session ted_ok / ted_hata) ====== --}}
@if (session('ted_ok'))
  <div class="rk-flash ok">{{ session('ted_ok') }}</div>
@endif
@if (session('ted_hata'))
  <div class="rk-flash err">{{ session('ted_hata') }}</div>
@endif

{{-- ====== Üst araç: Yeni Tedarikçi (Flutter MasaustuSayfa araç butonu) ====== --}}
<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
  <button type="button" onclick="tedForm()" class="rk-btn" style="background:var(--mor1);display:inline-flex;width:auto">
    <span class="material-icons-outlined" style="font-size:18px">add</span> Yeni Tedarikçi
  </button>
</div>

{{-- ====== Toplam Alış özeti (Flutter MIstatKart) — sadece tedarikçi varsa ====== --}}
@if ($adet)
  <div class="rk-stat" style="margin-bottom:20px">
    <div class="head" style="background:var(--mor1)">Toplam Alış</div>
    <div class="body">
      <div class="buyuk">{{ $tl($toplamAlis) }}</div>
      <div class="satir"><span class="e">Tedarikçi adedi</span><span class="d">{{ $adet }}</span></div>
      <div class="satir"><span class="e">Toplam fatura</span><span class="d">{{ $toplamFatura }}</span></div>
    </div>
  </div>
@endif

{{-- ====== Tedarikçi listesi (Flutter masaüstü MTablo) ====== --}}
@if (!$adet)
  <div class="rk-card" style="padding:40px;text-align:center;color:var(--sub)">Tedarikçi yok. Yukarıdan ekleyin.</div>
@else
  <div class="rk-tablo">
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
            $ad  = (string) ($t->ad ?? '');
            $tel = (string) ($t->telefon ?? '');
            $bas = $ad !== '' ? mb_strtoupper(mb_substr($ad, 0, 1)) : '?';
          @endphp
          <tr>
            {{-- TEDARİKÇİ: avatar baş harf + ad --}}
            <td>
              <div style="display:flex;align-items:center;gap:10px;min-width:0">
                <span style="width:34px;height:34px;flex:0 0 34px;border-radius:50%;
                             background:color-mix(in srgb,var(--mor1) 20%,transparent);color:var(--mor1);
                             font-size:14px;font-weight:800;display:flex;align-items:center;justify-content:center">{{ $bas }}</span>
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
            {{-- İşlem: Düzenle / Sil --}}
            <td class="sag" style="white-space:nowrap">
              <button type="button"
                      onclick="tedForm({{ (int) $t->id }}, {{ \Illuminate\Support\Js::from($t->ad) }}, {{ \Illuminate\Support\Js::from($t->telefon) }}, {{ \Illuminate\Support\Js::from($t->aciklama) }})"
                      class="rk-btn bos" style="display:inline-flex;width:auto">
                <span class="material-icons-outlined" style="font-size:16px">edit</span> Düzenle
              </button>
              <form action="/tedarikci/sil" method="POST" style="display:inline-block;margin-left:6px"
                    onsubmit="return confirm('{{ $t->ad }} silinecek. Bu işlem geri alınamaz.')">
                @csrf
                <input type="hidden" name="id" value="{{ (int) $t->id }}">
                <button type="submit" class="rk-btn bos" style="display:inline-flex;width:auto;color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)">
                  <span class="material-icons-outlined" style="font-size:16px">delete</span> Sil
                </button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- ====== Ekle / Düzenle modal (Flutter _form bottom sheet) ====== --}}
<div id="tedModal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;background:rgba(10,14,25,.6);padding:16px">
  <div style="background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.4);width:100%;max-width:440px;padding:22px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
      <h3 id="tedModalBaslik" style="font-size:18px;font-weight:800;color:var(--ink);margin:0">Yeni Tedarikçi</h3>
      <button type="button" onclick="tedKapat()" style="background:none;border:none;color:var(--sub);font-size:22px;line-height:1;cursor:pointer">&times;</button>
    </div>
    <form action="/tedarikci/kaydet" method="POST">
      @csrf
      <input type="hidden" name="id" id="tedId" value="">
      <label class="rk-lbl" style="margin-top:0">Firma / Kişi adı</label>
      <input type="text" name="ad" id="tedAd" required placeholder="Firma / Kişi adı" class="rk-inp">
      <label class="rk-lbl">Telefon</label>
      <input type="text" name="telefon" id="tedTel" placeholder="Telefon" class="rk-inp">
      <label class="rk-lbl">Not (opsiyonel)</label>
      <input type="text" name="aciklama" id="tedAciklama" placeholder="Not (opsiyonel)" class="rk-inp">
      <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:16px">
        <span class="material-icons-outlined" style="font-size:20px">save</span> Kaydet
      </button>
    </form>
  </div>
</div>

<script>
  function tedForm(id, ad, tel, aciklama) {
    var m = document.getElementById('tedModal');
    document.getElementById('tedModalBaslik').textContent = id ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi';
    document.getElementById('tedId').value = id || '';
    document.getElementById('tedAd').value = ad || '';
    document.getElementById('tedTel').value = tel || '';
    document.getElementById('tedAciklama').value = aciklama || '';
    m.style.display = 'flex';
    document.getElementById('tedAd').focus();
  }
  function tedKapat() { document.getElementById('tedModal').style.display = 'none'; }
  document.getElementById('tedModal').addEventListener('click', function (e) { if (e.target === this) tedKapat(); });
</script>
@endsection

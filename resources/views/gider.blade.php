@extends('layout.app')
@section('title', 'Giderler')
@section('rk_ikon', 'receipt_long')
@section('baslik', 'Giderler')
@section('rk_alt', $ayMetin ?? '')

@section('content')
@php
  $katAd   = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme', 'maas' => 'Maaş / Personel', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $katIkon = ['kira' => 'home_work', 'fatura' => 'receipt_long', 'malzeme' => 'inventory_2', 'maas' => 'groups', 'vergi' => 'account_balance', 'diger' => 'more_horiz'];
  $katRenk = ['kira' => 'var(--amber)', 'fatura' => 'var(--mavi)', 'malzeme' => 'var(--yesil)', 'maas' => 'var(--mor1)', 'vergi' => 'var(--kirmizi)', 'diger' => 'var(--sub)'];
  $aylar   = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

  $yil    = (int) substr($ay, 0, 4);
  $aySayi = (int) substr($ay, 5, 2);
  $ayMetin = ($aylar[$aySayi] ?? '') . ' ' . $yil;

  $oncekiTs  = mktime(0, 0, 0, $aySayi - 1, 1, $yil);
  $sonrakiTs = mktime(0, 0, 0, $aySayi + 1, 1, $yil);
  $onceki    = date('Y-m', $oncekiTs);
  $sonraki   = date('Y-m', $sonrakiTs);
  $sonrakiVar = $sonrakiTs <= mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));

  // Flutter: kategoriler tutara göre azalan sıralı
  $kats = collect($kategoriler)->sortDesc();

  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
@endphp

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

{{-- Araç çubuğu: ay navigasyonu + Gider Ekle (Flutter masaüstü araclar) --}}
<div class="rk-bolum" style="justify-content:space-between;margin-bottom:18px">
  <div style="display:inline-flex;align-items:center;gap:4px">
    <a href="/gider?ay={{ $onceki }}" class="rk-btn bos" style="padding:7px 9px" title="Önceki ay">
      <span class="material-icons-outlined" style="font-size:20px">chevron_left</span>
    </a>
    <span class="b" style="min-width:140px;text-align:center">{{ $ayMetin }}</span>
    @if ($sonrakiVar)
      <a href="/gider?ay={{ $sonraki }}" class="rk-btn bos" style="padding:7px 9px" title="Sonraki ay">
        <span class="material-icons-outlined" style="font-size:20px">chevron_right</span>
      </a>
    @else
      <span class="rk-btn bos" style="padding:7px 9px;opacity:.35;pointer-events:none">
        <span class="material-icons-outlined" style="font-size:20px">chevron_right</span>
      </span>
    @endif
  </div>
  <button type="button" class="rk-btn" style="background:var(--kirmizi);padding:10px 16px"
          onclick="document.getElementById('giderEkleForm').classList.toggle('acik')">
    <span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Gider Ekle
  </button>
</div>

{{-- Gider ekleme formu (Flutter _ekleDialog — maaş HARİÇ) --}}
<div id="giderEkleForm" class="rk-card" style="margin-bottom:18px;display:none">
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--kirmizi)"></span>
    <span class="b">Yeni Gider</span>
  </div>
  <form method="POST" action="/gider/ekle" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px">
    @csrf
    <input type="hidden" name="ay" value="{{ $ay }}">
    <div style="min-width:200px">
      <label class="rk-lbl" style="margin-top:0">Kategori</label>
      <select name="kategori" class="rk-inp">
        <option value="kira">Kira</option>
        <option value="fatura">Fatura (elektrik/su/doğalgaz)</option>
        <option value="malzeme">Malzeme / Alım</option>
        <option value="vergi">Vergi / Resmi</option>
        <option value="diger">Diğer</option>
      </select>
    </div>
    <div style="width:150px">
      <label class="rk-lbl" style="margin-top:0">Tutar (TL)</label>
      <input type="number" name="tutar" step="0.01" min="0" required placeholder="0" class="rk-inp">
    </div>
    <div style="width:170px">
      <label class="rk-lbl" style="margin-top:0">Tarih</label>
      <input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="rk-inp">
    </div>
    <div style="flex:1;min-width:200px">
      <label class="rk-lbl" style="margin-top:0">Açıklama (opsiyonel)</label>
      <input type="text" name="aciklama" maxlength="200" placeholder="Örn. Eylül kirası" class="rk-inp">
    </div>
    <button type="submit" class="rk-btn" style="background:var(--mor1);padding:11px 18px">
      <span class="material-icons-outlined" style="color:#fff;font-size:18px">save</span> Kaydet
    </button>
  </form>
  <label style="display:flex;align-items:center;gap:8px;margin-top:12px;font-size:12.5px;color:var(--sub)">
    <input type="checkbox" form="" disabled checked style="accent-color:var(--mor1)">
    Nakit gider açık vardiya varsa kasadan düşülür.
  </label>
</div>

{{-- Toplam Gider özet kartı (Flutter MIstatKart: başlık kırmızı + büyük değer + kategori satırları) --}}
<div class="rk-stat" style="margin-bottom:20px">
  <div class="head" style="background:var(--kirmizi)"><span>Toplam Gider</span></div>
  <div class="body">
    <div class="buyuk">{{ $tl($toplam) }}</div>
    @foreach ($kats as $kat => $tutarKat)
      <div class="satir">
        <span class="e">{{ $katAd[$kat] ?? ucfirst($kat) }}</span>
        <span class="d" style="color:{{ $katRenk[$kat] ?? 'var(--ink)' }}">{{ $tl($tutarKat) }}</span>
      </div>
    @endforeach
  </div>
</div>

{{-- Gider listesi (Flutter MTablo) --}}
@if (empty($giderler) || count($giderler) === 0)
  <div class="rk-card" style="text-align:center;padding:40px">
    <div class="rk-sub">Bu ay gider kaydı yok.</div>
  </div>
@else
  <div class="rk-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:18%">Kategori</th>
          <th style="width:36%">Açıklama</th>
          <th style="width:18%">Tarih</th>
          <th class="sag" style="width:16%">Tutar</th>
          <th class="sag" style="width:12%"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($giderler as $g)
          @php $kat = $g->kategori; $renk = $katRenk[$kat] ?? 'var(--sub)'; @endphp
          <tr>
            <td>
              <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">
                <span class="material-icons-outlined" style="font-size:14px">{{ $katIkon[$kat] ?? 'more_horiz' }}</span>{{ $katAd[$kat] ?? ucfirst($kat) }}
              </span>
            </td>
            <td class="rk-ink">{{ $g->aciklama ?: '—' }}</td>
            <td class="rk-sub">{{ $g->tarih }}</td>
            <td class="sag" style="font-weight:800;color:var(--ink);white-space:nowrap">{{ $tl($g->tutar) }}</td>
            <td class="sag">
              @if ($kat !== 'maas')
                <form method="POST" action="/gider/sil" onsubmit="return confirm('Bu gideri silmek istediğinize emin misiniz?')" style="display:inline">
                  @csrf
                  <input type="hidden" name="id" value="{{ $g->id }}">
                  <input type="hidden" name="ay" value="{{ $ay }}">
                  <button type="submit" class="rk-btn bos" style="padding:6px 9px" title="Sil">
                    <span class="material-icons-outlined" style="font-size:16px;color:var(--sub)">close</span>
                  </button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<style>
  #giderEkleForm.acik { display:block !important; }
</style>
@endsection

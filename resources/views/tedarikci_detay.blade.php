@extends('layout.app')
@section('title', 'Tedarikçi Detay')
@section('rk_ikon', 'local_shipping')
@section('baslik', ($ted->ad ?? 'Tedarikçi'))
@section('rk_alt', 'Kart · Katalog · Cari (borç / ödeme / vade)')

@section('content')
@php
  $ted = $ted ?? null;
  $katalog = $katalog ?? collect();
  $ekstre = $ekstre ?? collect();
  $borc = (float) ($borc ?? 0); $odeme = (float) ($odeme ?? 0); $bakiye = (float) ($bakiye ?? 0);
  $tl = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $tl2 = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
@endphp

@if (session('ted_ok'))<div class="rk-flash ok">{{ session('ted_ok') }}</div>@endif
@if (session('ted_hata'))<div class="rk-flash err">{{ session('ted_hata') }}</div>@endif

<div style="display:flex;gap:8px;margin-bottom:14px">
  <a href="/tedarikci" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Tedarikçiler</a>
</div>

{{-- ÜST: Kart bilgileri + cari özet --}}
<div class="rk-grid" style="grid-template-columns:3fr 2fr;gap:14px">
  <div class="rk-card">
    <div class="rk-bolum"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Tedarikçi Kartı</span></div>
    <div class="rk-grid" style="grid-template-columns:1fr 1fr;gap:8px 18px;margin-top:4px">
      <div><div class="rk-sub2" style="font-size:11px">Unvan</div><div class="rk-ink" style="font-weight:600">{{ $ted->unvan ?? ($ted->ad ?? '—') }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">Telefon</div><div class="rk-ink">{{ $ted->telefon ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">VKN / TCKN</div><div class="rk-ink">{{ $ted->vkn_tckn ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">Vergi Dairesi</div><div class="rk-ink">{{ $ted->vergi_dairesi ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">Yetkili</div><div class="rk-ink">{{ $ted->yetkili ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">E-posta</div><div class="rk-ink">{{ $ted->email ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">IBAN</div><div class="rk-ink" style="font-size:12px">{{ $ted->iban ?? '—' }}</div></div>
      <div><div class="rk-sub2" style="font-size:11px">Ödeme Vadesi</div><div class="rk-ink">{{ isset($ted->odeme_vade_gun) && $ted->odeme_vade_gun !== null ? $ted->odeme_vade_gun . ' gün' : '—' }}</div></div>
    </div>
    @if (!empty($ted->adres))<div style="margin-top:8px"><div class="rk-sub2" style="font-size:11px">Adres</div><div class="rk-ink" style="font-size:12.5px">{{ $ted->adres }}</div></div>@endif
  </div>

  <div class="rk-card" style="display:flex;flex-direction:column">
    <div class="rk-bolum"><span class="cubuk" style="background:var(--amber)"></span><span class="b">Cari Durum</span></div>
    <div class="rk-grid" style="grid-template-columns:1fr 1fr;gap:10px;margin-top:4px">
      <div class="rk-stat" style="--c:var(--kirmizi)"><div class="head" style="background:var(--kirmizi)"><span>Toplam Alış (Borç)</span></div><div class="body"><div class="buyuk" style="font-size:22px">{{ $tl($borc) }}</div></div></div>
      <div class="rk-stat" style="--c:var(--yesil)"><div class="head" style="background:var(--yesil)"><span>Ödenen</span></div><div class="body"><div class="buyuk" style="font-size:22px;color:var(--yesil)">{{ $tl($odeme) }}</div></div></div>
    </div>
    <div style="margin-top:10px;text-align:center;background:{{ $bakiye > 0 ? 'color-mix(in srgb,var(--kirmizi) 12%,transparent)' : 'color-mix(in srgb,var(--yesil) 12%,transparent)' }};border-radius:12px;padding:12px">
      <div class="rk-sub2" style="font-size:11.5px">{{ $bakiye > 0 ? 'Borcumuz (ödenecek)' : 'Bakiye kapalı' }}</div>
      <div style="font-size:26px;font-weight:900;color:{{ $bakiye > 0 ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $tl2($bakiye) }}</div>
    </div>
    {{-- Ödeme ekle --}}
    <form method="POST" action="/tedarikci-detay/odeme" style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end">
      @csrf
      <input type="hidden" name="tedarikci_id" value="{{ $ted->id }}">
      <div style="width:120px"><label class="rk-lbl" style="margin-top:0">Ödeme</label><input type="number" name="tutar" step="0.01" min="0.01" required placeholder="0,00" class="rk-inp"></div>
      <div style="width:110px"><label class="rk-lbl" style="margin-top:0">Şekil</label><select name="odeme_sekli" class="rk-inp"><option value="nakit">Nakit</option><option value="havale">Havale/EFT</option><option value="kart">Kart</option></select></div>
      <button type="submit" class="rk-btn" style="background:var(--yesil);padding:11px 16px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">payments</span> Ödeme Yap</button>
    </form>
  </div>
</div>

{{-- KATALOG --}}
<div class="rk-bolum" style="margin-top:22px;justify-content:space-between">
  <div style="display:flex;align-items:center;gap:8px"><span class="cubuk" style="background:#0EA5E9"></span><span class="b">Getirdiği Ürünler & Fiyat Geçmişi</span>
    <span class="rk-rozet" style="color:#0EA5E9;background:color-mix(in srgb,#0EA5E9 13%,transparent);border-color:color-mix(in srgb,#0EA5E9 40%,transparent)">{{ $katalog->count() }}</span></div>
</div>
@if ($katalog->isEmpty())
  <div class="rk-card" style="text-align:center;padding:30px"><div class="rk-sub">Bu tedarikçiden henüz fatura girilmemiş. Alış faturası girince katalog + fiyat geçmişi otomatik oluşur.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:34%">Ürün</th><th class="sag">Son Fiyat</th><th class="sag">Ortalama</th><th class="sag">Bant (min–max)</th><th class="orta">Trend</th><th class="sag">Son Alış</th><th class="sag">Fatura</th></tr></thead>
      <tbody>
        @foreach ($katalog as $k)
          @php $tr = (float) ($k->trend ?? 0); $trRenk = $tr > 2 ? 'var(--kirmizi)' : ($tr < -2 ? 'var(--yesil)' : 'var(--sub)'); @endphp
          <tr>
            <td style="font-weight:600">{{ $k->ad }} <span class="rk-sub2" style="font-size:11px">/ {{ $k->birim }}</span></td>
            <td class="sag" style="font-weight:800">{{ $tl2($k->son_fiyat) }}</td>
            <td class="sag rk-sub">{{ $tl2($k->ort_fiyat) }}</td>
            <td class="sag rk-sub2" style="font-size:12px">{{ $tl2($k->min_fiyat) }} – {{ $tl2($k->max_fiyat) }}</td>
            <td class="orta"><span style="color:{{ $trRenk }};font-weight:800;font-size:12px">{{ $tr > 0 ? '▲ +' : ($tr < 0 ? '▼ ' : '') }}{{ $tr != 0 ? abs($tr).'%' : '—' }}</span></td>
            <td class="sag rk-sub2" style="font-size:12px">{{ \Carbon\Carbon::parse($k->son_tarih)->format('d.m.Y') }}</td>
            <td class="sag rk-sub2">{{ (int) $k->adet }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- EKSTRE --}}
<div class="rk-bolum" style="margin-top:22px"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Cari Ekstre</span>
  <span class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">{{ $ekstre->count() }}</span></div>
@if ($ekstre->isEmpty())
  <div class="rk-card" style="text-align:center;padding:24px"><div class="rk-sub">Hareket yok.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:16%">Tarih</th><th>Açıklama</th><th class="orta" style="width:14%">Tür</th><th class="sag" style="width:18%">Tutar</th></tr></thead>
      <tbody>
        @foreach ($ekstre as $h)
          @php $borcMu = ($h->tip ?? '') === 'borc'; $renk = $borcMu ? 'var(--kirmizi)' : 'var(--yesil)'; @endphp
          <tr>
            <td class="rk-sub2">{{ \Carbon\Carbon::parse($h->tarih)->format('d.m.Y') }}</td>
            <td>{{ $h->aciklama }}</td>
            <td class="orta"><span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">{{ $borcMu ? 'Borç' : 'Ödeme' }}</span></td>
            <td class="sag" style="font-weight:800;color:{{ $renk }}">{{ $borcMu ? '+' : '−' }}{{ $tl2($h->tutar) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

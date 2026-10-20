@extends('layout.app')
@section('title', 'Sipariş Detay')
@section('rk_ikon', 'shopping_cart_checkout')
@section('baslik', ($s->siparis_no ?? 'Sipariş'))
@section('rk_alt', 'Satınalma siparişi detayı')

@section('content')
@php
  $s = $s ?? null;
  $kalemler = $kalemler ?? collect();
  $tedarikci = $tedarikci ?? null;
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $durumRenk = ['taslak'=>'var(--sub)','gonderildi'=>'var(--mavi)','kismi'=>'var(--amber)','tamamlandi'=>'var(--yesil)','iptal'=>'var(--kirmizi)'];
  $durumAd = ['taslak'=>'Taslak','gonderildi'=>'Gönderildi','kismi'=>'Kısmi Teslim','tamamlandi'=>'Tamamlandı','iptal'=>'İptal'];
  $dr = $durumRenk[$s->durum ?? ''] ?? 'var(--sub)';
@endphp

@if (session('sa_ok'))<div class="rk-flash ok">{{ session('sa_ok') }}</div>@endif
@if (session('sa_hata'))<div class="rk-flash err">{{ session('sa_hata') }}</div>@endif

<div style="display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center">
  <a href="/satinalma-siparis" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Siparişler</a>
  <span class="rk-rozet" style="color:{{ $dr }};background:color-mix(in srgb,{{ $dr }} 13%,transparent);border-color:color-mix(in srgb,{{ $dr }} 40%,transparent)">{{ $durumAd[$s->durum] ?? $s->durum }}</span>
  <div style="margin-left:auto;display:flex;gap:6px">
    @foreach (['gonderildi'=>'Gönderildi İşaretle','tamamlandi'=>'Tamamlandı','iptal'=>'İptal Et'] as $dk => $et)
      @if (($s->durum ?? '') !== $dk)
        <form method="POST" action="/satinalma-siparis/durum" style="margin:0">@csrf
          <input type="hidden" name="id" value="{{ $s->id }}"><input type="hidden" name="durum" value="{{ $dk }}">
          <button class="rk-btn bos" style="padding:7px 11px;{{ $dk==='iptal' ? 'color:var(--kirmizi)' : '' }}">{{ $et }}</button>
        </form>
      @endif
    @endforeach
  </div>
</div>

<div class="rk-grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">Tedarikçi</div><div class="rk-ink" style="font-weight:700;font-size:15px">{{ $tedarikci->ad ?? '—' }}</div>@if($tedarikci && !empty($tedarikci->telefon))<div class="rk-sub2" style="font-size:12px;margin-top:2px">{{ $tedarikci->telefon }}</div>@endif</div>
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">Sipariş / Beklenen Teslim</div><div class="rk-ink" style="font-weight:600">{{ $s->tarih ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : '—' }}</div><div class="rk-sub2" style="font-size:12px">Teslim: {{ $s->beklenen_teslim ? \Carbon\Carbon::parse($s->beklenen_teslim)->format('d.m.Y') : '—' }}</div></div>
  <div class="rk-card"><div class="rk-sub2" style="font-size:11px">Genel Toplam</div><div style="font-weight:900;font-size:22px;color:var(--mor1)">{{ $tl($s->genel_toplam) }}</div><div class="rk-sub2" style="font-size:11.5px">Ara {{ $tl($s->ara_toplam) }} · KDV {{ $tl($s->kdv_toplam) }}@if(($s->iskonto??0)>0) · İsk {{ $tl($s->iskonto) }}@endif</div></div>
</div>

<div class="rk-bolum"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Kalemler</span>
  <span class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">{{ $kalemler->count() }}</span></div>
<div class="rk-tablo">
  <table>
    <thead><tr><th style="width:34%">Malzeme</th><th class="sag">Miktar</th><th class="sag">Birim Fiyat</th><th class="sag">İsk%</th><th class="sag">KDV%</th><th class="sag">Teslim</th><th class="sag">Satır (net)</th></tr></thead>
    <tbody>
      @foreach ($kalemler as $k)
        <tr>
          <td style="font-weight:600">{{ $k->malzeme }}</td>
          <td class="sag">{{ rtrim(rtrim(number_format((float)$k->miktar,3,',','.'),'0'),',') }} {{ $k->birim }}</td>
          <td class="sag">{{ $tl($k->birim_fiyat) }}</td>
          <td class="sag rk-sub2">{{ (float)$k->iskonto_yuzde>0 ? number_format((float)$k->iskonto_yuzde,0).'%' : '—' }}</td>
          <td class="sag rk-sub2">{{ (float)$k->kdv_yuzde>0 ? number_format((float)$k->kdv_yuzde,0).'%' : '—' }}</td>
          <td class="sag rk-sub2">{{ rtrim(rtrim(number_format((float)$k->teslim_edilen,3,',','.'),'0'),',') }}</td>
          <td class="sag" style="font-weight:800">{{ $tl($k->satir_toplam) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

@if (!empty($s->notlar))<div class="rk-card" style="margin-top:12px"><div class="rk-sub2" style="font-size:11px">Not</div><div class="rk-ink">{{ $s->notlar }}</div></div>@endif

<div class="rk-card" style="margin-top:16px;background:color-mix(in srgb,var(--yesil) 6%,transparent);border-color:color-mix(in srgb,var(--yesil) 25%,transparent)">
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span class="material-icons-outlined" style="color:var(--yesil);font-size:22px">local_shipping</span>
    <div style="flex:1;min-width:200px"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Mal geldiğinde</div><div class="rk-sub2" style="font-size:12px">Gelen/eksik kalemleri işaretle, lot/SKT gir → stok otomatik girer.</div></div>
    @if (in_array($s->durum ?? '', ['gonderildi', 'kismi', 'taslak']))
      <a href="/mal-kabul?siparis_id={{ $s->id }}" class="rk-btn" style="background:var(--yesil);padding:11px 16px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">inventory</span> Mal Kabul Oluştur</a>
    @endif
  </div>
</div>
@endsection

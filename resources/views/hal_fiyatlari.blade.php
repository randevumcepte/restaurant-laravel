@extends('layout.app')
@section('title', 'Hal Fiyatları')
@section('rk_ikon', 'storefront')
@section('baslik', 'Hal Fiyatları')
@section('rk_alt', 'Toptan hal fiyatı referansı · "sen vs hal" karşılaştırma')

@section('content')
@php
  $hal = $hal ?? collect();
  $kiyas = $kiyas ?? collect();
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
@endphp

@if (session('hal_ok'))<div class="rk-flash ok">{{ session('hal_ok') }}</div>@endif
@if (session('hal_hata'))<div class="rk-flash err">{{ session('hal_hata') }}</div>@endif

<div class="rk-card" style="margin-bottom:16px;display:flex;gap:12px;align-items:flex-start">
  <span class="material-icons-outlined" style="color:#059669;font-size:24px">info</span>
  <div style="flex:1">
    <div style="font-weight:800;color:var(--ink);font-size:14px">Hal fiyatı = pazarlık kozun</div>
    <div class="rk-sub2" style="font-size:12px">Hal fiyatı belediye açık-veri API'sinden <b>otomatik çekilir</b> (günde 1 kez). Sistem senin son alış fiyatınla kıyaslayıp <b>"hal X, sen Y → %Z fazla"</b> der. <b>Not:</b> hal fiyatı komisyoncu/toptan fiyatıdır; kapına teslimde marj+nakliye eklenir — kozu <i>trend ve bant</i> olarak kullan.</div>
  </div>
</div>

{{-- Otomatik çek --}}
<div class="rk-card" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;background:color-mix(in srgb,#059669 5%,transparent);border-color:color-mix(in srgb,#059669 25%,transparent)">
  <form method="POST" action="/hal-fiyatlari/cek" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;flex:1">
    @csrf
    <div><label class="rk-lbl" style="margin-top:0">Hal kaynağı</label>
      <select name="kaynak" class="rk-inp" style="min-width:200px">
        <option value="izmir">İzmir Büyükşehir (önerilen)</option>
        <option value="balikesir">Balıkesir Büyükşehir</option>
      </select></div>
    <div><label class="rk-lbl" style="margin-top:0">Tarih (ops.)</label><input type="date" name="tarih" class="rk-inp"></div>
    <button type="submit" class="rk-btn" style="background:#059669;padding:11px 16px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">cloud_download</span> Şimdi Çek</button>
  </form>
  <div class="rk-sub2" style="font-size:11px;max-width:300px">Antalya'nın açık API'si yok (sadece bülten). En yakın güvenilir referans İzmir/Balıkesir; fiyatı <b>trend/bant kozu</b> olarak kullan. Sayfayı her gün ilk açışta otomatik çekilir.</div>
</div>

{{-- "SEN vs HAL" karşılaştırma --}}
@if ($kiyas->count())
  <div class="rk-bolum"><span class="cubuk" style="background:var(--kirmizi)"></span><span class="b">Sen vs Hal</span>
    <span class="rk-rozet" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)">{{ $kiyas->where('fark','>',0)->count() }} fazla</span></div>
  <div class="rk-tablo" style="margin-bottom:22px">
    <table>
      <thead><tr><th style="width:30%">Malzeme</th><th>Hal Ürünü</th><th class="sag">Hal Ort.</th><th class="sag">Senin Son</th><th class="orta">Fark</th></tr></thead>
      <tbody>
        @foreach ($kiyas as $k)
          @php $fazla = $k->fark > 0; $renk = $k->fark >= 20 ? 'var(--kirmizi)' : ($k->fark > 0 ? 'var(--amber)' : 'var(--yesil)'); @endphp
          <tr style="{{ $k->fark >= 20 ? 'background:color-mix(in srgb,var(--kirmizi) 7%,transparent)' : '' }}">
            <td style="font-weight:600">{{ $k->malzeme }}</td>
            <td class="rk-sub2" style="font-size:12px">{{ $k->hal_ad }} <span style="font-size:10px">/ {{ $k->birim }}</span></td>
            <td class="sag rk-sub">{{ $tl($k->hal) }}</td>
            <td class="sag" style="font-weight:800">{{ $tl($k->senin) }}</td>
            <td class="orta"><span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">{{ $fazla ? '▲ +'.$k->fark.'%' : ($k->fark < 0 ? '▼ '.$k->fark.'%' : 'eşit') }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@elseif ($hal->count())
  <div class="rk-card" style="text-align:center;padding:22px;margin-bottom:22px"><div class="rk-sub">Hal fiyatı girdin ama ada göre eşleşen malzeme/alış bulunamadı. Hal ürün adını malzeme adına benzet (ör. "Domates").</div></div>
@endif

{{-- Hal fiyatı gir --}}
<div class="rk-card" style="margin-bottom:18px">
  <div class="rk-bolum" style="margin-bottom:10px"><span class="cubuk" style="background:#059669"></span><span class="b">Hal Fiyatı Gir</span></div>
  <form method="POST" action="/hal-fiyatlari/ekle" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end">
    @csrf
    <div style="flex:1;min-width:160px"><label class="rk-lbl" style="margin-top:0">Ürün</label><input type="text" name="urun_ad" required placeholder="Örn. Domates" class="rk-inp"></div>
    <div style="width:80px"><label class="rk-lbl" style="margin-top:0">Birim</label><input type="text" name="birim" value="kg" class="rk-inp"></div>
    <div style="width:110px"><label class="rk-lbl" style="margin-top:0">Asgari</label><input type="number" name="asgari" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
    <div style="width:110px"><label class="rk-lbl" style="margin-top:0">Azami</label><input type="number" name="azami" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
    <div style="width:110px"><label class="rk-lbl" style="margin-top:0">Ortalama</label><input type="number" name="ortalama" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
    <div style="width:140px"><label class="rk-lbl" style="margin-top:0">Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="rk-inp"></div>
    <button type="submit" class="rk-btn" style="background:#059669;padding:11px 16px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Ekle</button>
  </form>
  <div class="rk-sub2" style="font-size:11px;margin-top:8px">Ortalama boş bırakırsan asgari+azami ortalaması alınır.</div>
</div>

{{-- Girilen hal fiyatları --}}
<div class="rk-bolum"><span class="cubuk" style="background:#059669"></span><span class="b">Girilen Hal Fiyatları</span>
  <span class="rk-rozet" style="color:#059669;background:color-mix(in srgb,#059669 13%,transparent);border-color:color-mix(in srgb,#059669 40%,transparent)">{{ $hal->count() }}</span></div>
@if ($hal->isEmpty())
  <div class="rk-card" style="text-align:center;padding:26px"><div class="rk-sub">Henüz hal fiyatı yok. Yukarıdan bugünkü fiyatları gir.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th>Tarih</th><th>Ürün</th><th class="sag">Asgari</th><th class="sag">Ortalama</th><th class="sag">Azami</th><th class="sag"></th></tr></thead>
      <tbody>
        @foreach ($hal as $h)
          <tr>
            <td class="rk-sub2">{{ $h->tarih ? \Carbon\Carbon::parse($h->tarih)->format('d.m.Y') : '—' }}</td>
            <td style="font-weight:600">{{ $h->urun_ad }} <span class="rk-sub2" style="font-size:10px">/ {{ $h->birim }}</span></td>
            <td class="sag rk-sub2">{{ $h->asgari !== null ? $tl($h->asgari) : '—' }}</td>
            <td class="sag" style="font-weight:700">{{ $h->ortalama !== null ? $tl($h->ortalama) : '—' }}</td>
            <td class="sag rk-sub2">{{ $h->azami !== null ? $tl($h->azami) : '—' }}</td>
            <td class="sag">
              <form method="POST" action="/hal-fiyatlari/sil" onsubmit="return confirm('Silinsin mi?')" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $h->id }}">
                <button class="rk-btn bos" style="padding:5px 9px;color:var(--kirmizi)"><span class="material-icons-outlined" style="font-size:15px">delete_outline</span></button></form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<div class="rk-card" style="margin-top:18px;background:color-mix(in srgb,#059669 6%,transparent);border-color:color-mix(in srgb,#059669 25%,transparent)">
  <div style="display:flex;align-items:center;gap:10px">
    <span class="material-icons-outlined" style="color:#059669;font-size:22px">cloud_download</span>
    <div style="flex:1"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Otomatik hal çekimi AKTİF ✓</div><div class="rk-sub2" style="font-size:12px">İzmir Büyükşehir açık-veri API'sinden (openapi.izmir.bel.tr) günde 1 kez otomatik çekilir + "Şimdi Çek" ile manuel. Sunucu dış erişime kapalıysa manuel giriş her zaman çalışır.</div></div>
  </div>
</div>
@endsection

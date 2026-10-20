@extends('layout.app')
@section('title', 'Fiyat Radarı')
@section('rk_ikon', 'radar')
@section('baslik', 'Fiyat Radarı')
@section('rk_alt', 'Gizli zam dedektörü + daha ucuz tedarikçi (kendi alış geçmişinden)')

@section('content')
@php
  $zamlar = $zamlar ?? collect();
  $firsatlar = $firsatlar ?? collect();
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $tl0 = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $toplamEtki = $zamlar->sum('yillik_etki');
@endphp

<div class="rk-card" style="margin-bottom:16px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
  <span class="material-icons-outlined" style="color:#EC4899;font-size:26px">radar</span>
  <div style="flex:1;min-width:220px">
    <div style="font-weight:800;color:var(--ink);font-size:14.5px">Fiyatların sana ne yaptığını gösterir</div>
    <div class="rk-sub2" style="font-size:12px">Son 120 günlük alış faturalarından sinsi zamları + daha ucuz tedarikçileri yakalar. (Hal fiyatı + restoran ağı sonraki aşamada eklenecek.)</div>
  </div>
  @if ($toplamEtki > 0)
    <div style="text-align:center;background:color-mix(in srgb,var(--kirmizi) 10%,transparent);border-radius:12px;padding:10px 16px">
      <div class="rk-sub2" style="font-size:11px">Gizli zamların yıllık etkisi</div>
      <div style="font-size:22px;font-weight:900;color:var(--kirmizi)">~{{ $tl0($toplamEtki) }}</div>
    </div>
  @endif
</div>

{{-- ===== GİZLİ ZAM ===== --}}
<div class="rk-bolum"><span class="cubuk" style="background:var(--kirmizi)"></span><span class="b">🕵️ Gizli Zam Dedektörü</span>
  <span class="rk-rozet" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)">{{ $zamlar->count() }}</span></div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Son 120 günde fiyatı <b>%8+</b> tırmanan malzemeler — tek faturaya bakınca fark edilmez, ama birikince büyük para.</div>
@if ($zamlar->isEmpty())
  <div class="rk-card" style="text-align:center;padding:30px"><span class="material-icons-outlined" style="font-size:44px;color:var(--yesil)">verified</span><div class="rk-sub" style="margin-top:8px">Sinsi zam yok 👍 (En az 2 faturalı malzeme gerekir; alış girdikçe dolar.)</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:26%">Malzeme</th><th class="sag">İlk Fiyat</th><th class="sag">Son Fiyat</th><th class="orta">Artış</th><th class="sag">Fatura</th><th class="sag">Yıllık Etki</th></tr></thead>
      <tbody>
        @foreach ($zamlar as $z)
          @php $renk = $z->yuzde >= 15 ? 'var(--kirmizi)' : 'var(--amber)'; @endphp
          <tr>
            <td style="font-weight:600">{{ $z->ad }} <span class="rk-sub2" style="font-size:10px">/ {{ $z->birim }}</span>
              <div class="rk-sub2" style="font-size:10.5px">{{ \Carbon\Carbon::parse($z->ilk_tarih)->format('d.m') }} → {{ \Carbon\Carbon::parse($z->son_tarih)->format('d.m') }}</div></td>
            <td class="sag rk-sub">{{ $tl($z->ilk) }}</td>
            <td class="sag" style="font-weight:800">{{ $tl($z->son) }}</td>
            <td class="orta"><span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">▲ +{{ number_format($z->yuzde, 1) }}%</span></td>
            <td class="sag rk-sub2">{{ $z->adet }}</td>
            <td class="sag" style="font-weight:800;color:var(--kirmizi)">~{{ $tl0($z->yillik_etki) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Yıllık etki = (son fiyat − ilk fiyat) × tahmini yıllık alım (son 120 gün alımından). Bunları <b>pazarlık kozu</b> yap.</div>
@endif

{{-- ===== DAHA UCUZ TEDARİKÇİ ===== --}}
<div class="rk-bolum" style="margin-top:24px"><span class="cubuk" style="background:var(--yesil)"></span><span class="b">💰 Daha Ucuz Tedarikçi Var</span>
  <span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">{{ $firsatlar->count() }}</span></div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Aynı malzemeyi birden çok tedarikçiden aldın — en ucuzla en pahalı arasındaki fark.</div>
@if ($firsatlar->isEmpty())
  <div class="rk-card" style="text-align:center;padding:26px"><div class="rk-sub">Kıyas için bir malzemeyi <b>en az 2 farklı tedarikçiden</b> alman gerekir. Alış arttıkça fırsatlar burada çıkar.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:30%">Malzeme</th><th>En Ucuz Tedarikçi</th><th class="sag">En Ucuz</th><th class="sag">En Pahalı</th><th class="orta">Fark</th></tr></thead>
      <tbody>
        @foreach ($firsatlar as $f)
          <tr>
            <td style="font-weight:600">{{ $f->ad }} <span class="rk-sub2" style="font-size:10px">({{ $f->ted_say }} tedarikçi)</span></td>
            <td style="font-weight:600;color:var(--yesil)">{{ $f->en_ucuz_ted }}</td>
            <td class="sag" style="font-weight:800;color:var(--yesil)">{{ $tl($f->en_ucuz_fiyat) }}</td>
            <td class="sag rk-sub">{{ $tl($f->en_pahali_fiyat) }}</td>
            <td class="orta"><span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">%{{ number_format($f->tasarruf, 0) }} ucuz</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<div class="rk-card" style="margin-top:20px;background:color-mix(in srgb,#EC4899 6%,transparent);border-color:color-mix(in srgb,#EC4899 25%,transparent)">
  <div style="display:flex;align-items:center;gap:10px">
    <span class="material-icons-outlined" style="color:#EC4899;font-size:22px">hub</span>
    <div style="flex:1"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Sıradaki aşama: Hal + Ağ + AI</div><div class="rk-sub2" style="font-size:12px">Bu radar şimdilik <b>kendi alış geçmişinden</b> çalışıyor. Sonraki adımda belediye <b>hal fiyatları</b>, anonim <b>restoran ağı</b> benchmark'ı ve <b>AI pazarlık mesajı</b> eklenecek (hal API + Anthropic bakiyesi + WhatsApp bridge gerekir).</div></div>
  </div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Fiyat Radarı')
@section('rk_ikon', 'radar')
@section('baslik', 'Fiyat Radarı')
@section('rk_alt', 'Gizli zam dedektörü + hal kıyası + daha ucuz tedarikçi')

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
    <div class="rk-sub2" style="font-size:12px">Son 120 günlük alış faturalarından sinsi zamları, hal fiyatının ne kadar üstünde aldığını ve daha ucuz tedarikçileri yakalar.</div>
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

{{-- ===== HAL KIYASI (İzmir hali, otomatik) ===== --}}
@php
  $halK = $hal ?? ['satirlar' => [], 'bulten' => null, 'urun_say' => 0, 'yillik_fazla' => 0];
  $halSat = collect($halK['satirlar'] ?? []);
@endphp
<div class="rk-bolum" style="margin-top:24px"><span class="cubuk" style="background:#16A34A"></span><span class="b">🥬 Hal Kıyası</span>
  <span class="rk-rozet" style="color:#16A34A;background:color-mix(in srgb,#16A34A 13%,transparent);border-color:color-mix(in srgb,#16A34A 40%,transparent)">{{ $halSat->count() }}</span></div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Son alış fiyatın vs <b>İzmir Büyükşehir hal bülteni</b>@if ($halK['bulten']) ({{ \Carbon\Carbon::parse($halK['bulten'])->format('d.m.Y') }})@endif. Hal toptan fiyattır — tedarikçinin <b>%10–20 üstü normal</b>, daha fazlası pazarlık konusu.</div>
@if ($halSat->isEmpty())
  <div class="rk-card" style="text-align:center;padding:26px"><div class="rk-sub">
    @if (!$halK['bulten']) Hal bülteni henüz çekilemedi — sistem saatte bir otomatik deniyor.
    @else Alış faturalarındaki sebze-meyve malzemelerin halde eşleşince burada çıkar (ör. Domates, Soğan, Maydanoz).@endif
  </div></div>
@else
  @if ($halK['yillik_fazla'] > 0)
    <div class="rk-sub2" style="font-size:12.5px;margin:-4px 0 10px">Halin %15'ten fazla üstünde aldıkların yıllık <b style="color:var(--kirmizi)">~{{ $tl0($halK['yillik_fazla']) }}</b> fazla ödeme demek.</div>
  @endif
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:24%">Malzeme</th><th>Haldeki Karşılığı</th><th class="sag">Hal (min–max)</th><th class="sag">Hal Ort.</th><th class="sag">Senin Son Alış</th><th class="orta">Fark</th><th class="sag">Yıllık Fazla</th></tr></thead>
      <tbody>
        @foreach ($halSat as $h)
          @php
            $fk = $h['fark'];
            $renk = $fk === null ? 'var(--sub2)' : ($fk > 30 ? 'var(--kirmizi)' : ($fk > 15 ? 'var(--amber)' : 'var(--yesil)'));
          @endphp
          <tr>
            <td style="font-weight:600">{{ $h['ad'] }} <span class="rk-sub2" style="font-size:10px">/ {{ $h['birim'] }}</span>
              <div class="rk-sub2" style="font-size:10.5px">son alış {{ \Carbon\Carbon::parse($h['son_tarih'])->format('d.m') }}</div></td>
            <td class="rk-sub" style="font-size:12px">{{ $h['hal_ad'] }}</td>
            <td class="sag rk-sub2" style="font-size:12px">@if ($h['hal_min'] > 0){{ $tl($h['hal_min']) }} – {{ $tl($h['hal_max']) }}@else—@endif</td>
            <td class="sag" style="font-weight:800">{{ $tl($h['hal_ort']) }}<span class="rk-sub2" style="font-size:10px">/{{ $h['hal_birim'] }}</span></td>
            <td class="sag" style="font-weight:700">
              @if ($h['uyumlu']){{ $tl($h['fiyat_hal_birim']) }}<span class="rk-sub2" style="font-size:10px">/{{ $h['hal_birim'] }}</span>
              @else{{ $tl($h['son_fiyat']) }}<span class="rk-sub2" style="font-size:10px">/{{ $h['birim'] }}</span>@endif
            </td>
            <td class="orta">
              @if ($fk === null)
                <span class="rk-sub2" style="font-size:11px" title="Senin birimin ({{ $h['birim'] }}) ile haldeki birim ({{ $h['hal_birim'] }}) farklı">birim farklı</span>
              @else
                <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">{{ $fk > 0 ? '▲ +' . $fk : ($fk < 0 ? '▼ ' . $fk : '0') }}%</span>
              @endif
            </td>
            <td class="sag" style="font-weight:800;color:{{ $h['yillik'] > 0 && $fk > 15 ? 'var(--kirmizi)' : 'var(--sub2)' }}">{{ $h['yillik'] > 0 && $fk > 15 ? '~' . $tl0($h['yillik']) : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Fark yalnız birimler aynıysa hesaplanır (kg↔kg, adet↔adet, demet↔bağ). Çuval/kasa ile alınan ürünlerde malzeme kartına kg birimi girersen kıyas da çıkar.</div>
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

<div class="rk-card" style="margin-top:20px;background:color-mix(in srgb,#16A34A 6%,transparent);border-color:color-mix(in srgb,#16A34A 25%,transparent)">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span class="material-icons-outlined" style="color:#16A34A;font-size:22px">sync</span>
    <div style="flex:1;min-width:220px"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Hal fiyatları otomatik güncelleniyor</div><div class="rk-sub2" style="font-size:12px">İzmir Büyükşehir hal bülteni her gün sistem tarafından kendiliğinden çekilir{!! !empty($hal['bulten']) ? ' — son bülten <b>' . e(\Carbon\Carbon::parse($hal['bulten'])->format('d.m.Y')) . '</b>, ' . (int) $hal['urun_say'] . ' ürün' : '' !!}. Kullandığın bir üründe halde sert zam olursa bildirim gelir.</div></div>
    <a href="/hal-fiyatlari" class="rk-btn" style="text-decoration:none">Tüm hal fiyatları</a>
  </div>
</div>
@endsection

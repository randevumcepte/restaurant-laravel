@extends('layout.app')
@section('title', 'Finans / Kâr-Zarar')
@section('rk_ikon', 'analytics')
@section('baslik', 'Finans / Kâr-Zarar')
@section('rk_alt', $aralik_metin ?? '')

@push('head')
<style>
#fins{max-width:1280px}

/* ---- Dönem sekmeleri (Flutter: önceki/sonraki ay nav karşılığı) ---- */
#fins .fin-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center}
#fins .fin-tabs .sp{flex:1}
#fins .fin-tab{display:inline-flex;align-items:center;gap:6px;cursor:pointer;border:1px solid var(--line);
  background:var(--card);border-radius:20px;padding:7px 16px;font-size:13px;font-weight:700;color:var(--sub2);
  text-decoration:none;transition:background .15s,color .15s,border-color .15s}
#fins .fin-tab:hover{background:var(--card2)}
#fins .fin-tab.aktif{background:var(--mor1);border-color:var(--mor1);color:#fff}
#fins .fin-tab .material-icons-outlined{font-size:16px}

/* ---- Net Kâr / Zarar hero banner (Flutter mobile LinearGradient hero) ---- */
#fins .fin-hero{border-radius:20px;padding:22px 24px;margin-bottom:22px;overflow:hidden;position:relative}
#fins .fin-hero.kar{background:linear-gradient(135deg,var(--mor1),var(--mavi))}
#fins .fin-hero.zarar{background:linear-gradient(135deg,#7F1D1D,var(--kirmizi))}
#fins .fin-hero .etiket{font-size:13px;color:rgba(255,255,255,.75);font-weight:600;display:flex;align-items:center;gap:5px}
#fins .fin-hero .etiket .material-icons-outlined{font-size:17px}
#fins .fin-hero .deger{font-size:36px;font-weight:900;color:#fff;letter-spacing:-.5px;margin:6px 0 14px;line-height:1}
#fins .fin-hero .mini-row{display:flex;gap:0;flex-wrap:wrap}
#fins .fin-hero .mini{flex:1;min-width:100px;display:flex;flex-direction:column;gap:2px;padding-right:16px}
#fins .fin-hero .mini .ikon{color:rgba(255,255,255,.7)}
#fins .fin-hero .mini .ikon .material-icons-outlined{font-size:15px}
#fins .fin-hero .mini .v{font-size:15px;font-weight:800;color:#fff}
#fins .fin-hero .mini .k{font-size:11px;color:rgba(255,255,255,.65);font-weight:600}
#fins .fin-hero::after{content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;
  border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none}

/* ---- 3 büyük KPI kartı (Flutter MIstatKart) ---- */
#fins .fin-kpi{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}
@media(max-width:820px){#fins .fin-kpi{grid-template-columns:1fr}}
#fins .fin-kpi .rk-stat>.head{font-size:14px;font-weight:800;display:flex;align-items:center;gap:6px}
#fins .fin-kpi .rk-stat>.head .material-icons-outlined{font-size:18px;opacity:.85}
#fins .fin-kpi .rk-stat>.body .buyuk{font-size:26px;font-weight:900;color:var(--ink);margin-bottom:10px;letter-spacing:-.3px}

/* ---- Alt tablolar iki sütun (Flutter MTablo) ---- */
#fins .fin-alt{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;align-items:start}
@media(max-width:720px){#fins .fin-alt{grid-template-columns:1fr}}
#fins .fin-alt .blok{display:flex;flex-direction:column;gap:0}

/* ---- Boş durum kart ---- */
#fins .fin-bos{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:18px 16px;
  color:var(--sub);font-size:13px}
</style>
@endpush

@section('content')
@php
  $periodlar = [
    'gunluk'   => ['ad' => 'Bugün',    'ikon' => 'today'],
    'haftalik' => ['ad' => 'Bu Hafta', 'ikon' => 'date_range'],
    'aylik'    => ['ad' => 'Bu Ay',    'ikon' => 'calendar_month'],
  ];
  $period   = $period ?? 'aylik';
  $gelir    = (float) ($gelir    ?? 0);
  $gider    = (float) ($gider    ?? 0);
  $bahsis   = (float) ($bahsis   ?? 0);
  $net      = (float) ($net      ?? ($gelir - $gider));
  $alis     = (float) ($alis_toplam ?? 0);
  $gelirTip = $gelir_tip ?? [];
  $giderKat = $gider_kategori ?? [];
  $tipAd    = ['nakit' => 'Nakit', 'kredi' => 'Kredi Kartı', 'yemek_karti' => 'Yemek Kartı', 'acik_hesap' => 'Açık Hesap'];
  $katAd    = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme / Alış', 'maas' => 'Maaş / Personel', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $tl       = fn ($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $heroKls  = $net >= 0 ? 'kar' : 'zarar';
  $heroEtk  = $net >= 0 ? 'Net Kâr' : 'Net Zarar';
  $heroIkon = $net >= 0 ? 'trending_up' : 'trending_down';
  $netRenk  = $net >= 0 ? 'var(--mor1)' : 'var(--kirmizi)';
@endphp

<div id="fins">

  {{-- Dönem sekmeleri (Flutter: önceki/sonraki ay nav karşılığı — web günlük/haftalık/aylık) --}}
  <div class="fin-tabs">
    <span class="sp"></span>
    @foreach ($periodlar as $k => $item)
      <a href="/finans?period={{ $k }}"
         class="fin-tab {{ $period === $k ? 'aktif' : '' }}">
        <span class="material-icons-outlined">{{ $item['ikon'] }}</span>
        {{ $item['ad'] }}
      </a>
    @endforeach
  </div>

  {{-- Net Kâr / Zarar hero banner (Flutter mobile: LinearGradient hero kartı) --}}
  <div class="fin-hero {{ $heroKls }}">
    <div class="etiket">
      <span class="material-icons-outlined">{{ $heroIkon }}</span>
      {{ $heroEtk }} ({{ $aralik_metin ?? '' }})
    </div>
    <div class="deger">{{ $tl($net) }}</div>
    <div class="mini-row">
      <div class="mini">
        <span class="ikon"><span class="material-icons-outlined">arrow_downward</span></span>
        <span class="v">{{ $tl($gelir) }}</span>
        <span class="k">Gelir</span>
      </div>
      <div class="mini">
        <span class="ikon"><span class="material-icons-outlined">arrow_upward</span></span>
        <span class="v">{{ $tl($gider) }}</span>
        <span class="k">Gider</span>
      </div>
      @if ($bahsis > 0)
        <div class="mini">
          <span class="ikon"><span class="material-icons-outlined">volunteer_activism</span></span>
          <span class="v">{{ $tl($bahsis) }}</span>
          <span class="k">Bahşiş</span>
        </div>
      @endif
    </div>
  </div>

  {{-- 3 büyük KPI kartı (Flutter _masaustu MIstatKart) --}}
  <div class="fin-kpi">

    {{-- Gelir kartı (Flutter MIstatKart baslik='Gelir' renk=t.yesil) --}}
    <div class="rk-stat">
      <div class="head" style="background:var(--yesil)">
        <span class="material-icons-outlined">payments</span>
        Gelir
      </div>
      <div class="body">
        <div class="buyuk">{{ $tl($gelir) }}</div>
        @if ($bahsis > 0)
          <div class="satir">
            <span class="e">Bahşiş</span>
            <span class="d" style="color:var(--yesil)">{{ $tl($bahsis) }}</span>
          </div>
        @endif
        <div class="satir">
          <span class="e">Ödeme tipi</span>
          <span class="d">{{ count($gelirTip) }} kalem</span>
        </div>
      </div>
    </div>

    {{-- Gider kartı (Flutter MIstatKart baslik='Gider' renk=t.kirmizi) --}}
    <div class="rk-stat">
      <div class="head" style="background:var(--kirmizi)">
        <span class="material-icons-outlined">receipt_long</span>
        Gider
      </div>
      <div class="body">
        <div class="buyuk">{{ $tl($gider) }}</div>
        @if ($alis > 0)
          <div class="satir">
            <span class="e">Tedarikçi alışı</span>
            <span class="d" style="color:var(--kirmizi)">{{ $tl($alis) }}</span>
          </div>
        @endif
        <div class="satir">
          <span class="e">Kategori</span>
          <span class="d">{{ count($giderKat) }} kalem</span>
        </div>
      </div>
    </div>

    {{-- Net Kâr / Zarar kartı (Flutter MIstatKart baslik='Net Kâr'/'Net Zarar') --}}
    <div class="rk-stat">
      <div class="head" style="background:{{ $netRenk }}">
        <span class="material-icons-outlined">{{ $heroIkon }}</span>
        {{ $heroEtk }}
      </div>
      <div class="body">
        <div class="buyuk">{{ $tl($net) }}</div>
        <div class="satir">
          <span class="e">Gelir</span>
          <span class="d" style="color:var(--yesil)">{{ $tl($gelir) }}</span>
        </div>
        <div class="satir">
          <span class="e">Gider</span>
          <span class="d" style="color:var(--kirmizi)">{{ $tl($gider) }}</span>
        </div>
      </div>
    </div>

  </div>

  {{-- Alt: gelir/gider döküm tabloları yan yana (Flutter MTablo + MBolumBaslik) --}}
  <div class="fin-alt">

    {{-- Sol: Gelir — Ödeme Tipi (Flutter MBolumBaslik 'Gelir — Ödeme Tipi' + MTablo) --}}
    <div class="blok">
      <div class="rk-bolum">
        <span class="cubuk" style="background:var(--yesil)"></span>
        <span class="b">Gelir — Ödeme Tipi</span>
        <span class="sayi" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent)">
          {{ count($gelirTip) }}
        </span>
      </div>
      @if (empty($gelirTip))
        <div class="fin-bos">Bu dönem tahsilat yok.</div>
      @else
        <div class="rk-tablo">
          <table>
            <thead>
              <tr>
                <th>Ödeme Tipi</th>
                <th class="sag">Tutar</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($gelirTip as $g)
                <tr>
                  <td>{{ $tipAd[$g['tip']] ?? $g['tip'] }}</td>
                  <td class="sag" style="color:var(--yesil);font-weight:700">{{ $tl($g['tutar']) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- Sağ: Gider — Kategori + Tedarikçi Alışı (Flutter MBolumBaslik + MTablo) --}}
    <div class="blok">
      <div class="rk-bolum">
        <span class="cubuk" style="background:var(--kirmizi)"></span>
        <span class="b">Gider — Kategori</span>
        <span class="sayi" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent)">
          {{ count($giderKat) }}
        </span>
      </div>
      @if (empty($giderKat))
        <div class="fin-bos">Bu dönem gider yok.</div>
      @else
        <div class="rk-tablo">
          <table>
            <thead>
              <tr>
                <th>Kategori</th>
                <th class="sag">Tutar</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($giderKat as $g)
                <tr>
                  <td>{{ $katAd[$g['kategori']] ?? $g['kategori'] }}</td>
                  <td class="sag" style="color:var(--kirmizi);font-weight:700">{{ $tl($g['tutar']) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      {{-- Tedarikçi Alışı (Flutter: MBolumBaslik 'Tedarikçi Alışı' renk=t.amber + MTablo) --}}
      @if ($alis > 0)
        <div class="rk-bolum" style="margin-top:16px">
          <span class="cubuk" style="background:var(--amber)"></span>
          <span class="b">Tedarikçi Alışı</span>
        </div>
        <div class="rk-tablo">
          <table>
            <thead>
              <tr>
                <th>Kalem</th>
                <th class="sag">Tutar</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Toplam alış faturası</td>
                <td class="sag" style="color:var(--amber);font-weight:700">{{ $tl($alis) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      @endif
    </div>

  </div>

</div>
@endsection

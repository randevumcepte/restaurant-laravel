@extends('layout.app')
@section('title', 'Z Raporu')
@section('rk_ikon', 'summarize')
@section('baslik', 'Z Raporu')
@section('rk_alt', $z['tarih'] ?? '')

@section('rk_araclar')
  <button onclick="window.print()" class="rk-btn no-print" style="background:var(--mavi)">
    <span class="material-icons-outlined" style="font-size:18px">print</span> Z Raporunu Yazdır
  </button>
@endsection

@section('content')
@php
  $tl  = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
  $n   = fn($v) => (int) round((float) $v);
  $my  = (int) ($z['maliyet_yuzde'] ?? 0);
  // Flutter: >=38 kırmızı, >=30 amber, aksi yeşil
  $myRenk = $my >= 38 ? 'var(--kirmizi)' : ($my >= 30 ? 'var(--amber)' : 'var(--yesil)');
@endphp

<style>
  /* Z Raporu — Flutter z_raporu_screen düzeni, panel-kit renkleri */
  .z-wrap{max-width:960px;margin:0 auto}
  .z-nav{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:16px}
  .z-nav .gun{font-size:15px;font-weight:800;color:var(--ink);min-width:170px;text-align:center}
  .z-nav a,.z-nav span.pas{width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:22px;border:1px solid var(--line);background:var(--card);color:var(--mavi)}
  .z-nav a:hover{background:var(--card2)}
  .z-nav span.pas{color:var(--line);cursor:default}
  .z-hero{background:linear-gradient(135deg,var(--mavi),var(--mor1));border-radius:20px;padding:22px;color:#fff;margin-bottom:16px}
  .z-hero .et{font-size:13px;color:rgba(255,255,255,.72)}
  .z-hero .ciro{font-size:34px;font-weight:800;margin:2px 0 14px}
  .z-hero .mini{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  .z-hero .mini .d{font-size:17px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .z-hero .mini .e{font-size:11px;color:rgba(255,255,255,.6)}
  .z-uyari{display:flex;align-items:flex-start;gap:8px;background:color-mix(in srgb,var(--amber) 15%,transparent);border:1px solid color-mix(in srgb,var(--amber) 45%,transparent);color:var(--amber);border-radius:12px;padding:12px 14px;margin-bottom:16px;font-size:13px;font-weight:600}
  .z-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .z-kutu{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--golge);padding:16px}
  .z-kutu .bas{font-weight:800;color:var(--ink);margin-bottom:10px;font-size:14.5px}
  .z-satir{display:flex;align-items:center;justify-content:space-between;padding:6px 0;font-size:13.5px;gap:12px}
  .z-satir .e{color:var(--sub2);flex:1;min-width:0}
  .z-satir .e .adet{color:var(--sub);font-weight:400}
  .z-satir .d{font-weight:600;color:var(--ink);white-space:nowrap}
  .z-bos{color:var(--sub);font-size:13px;padding:6px 0}
  .z-genis{grid-column:1 / -1}
  .z-dip{text-align:center;font-size:11px;color:var(--sub);margin-top:22px;padding-top:16px;border-top:1px solid var(--line)}
  @media (max-width:760px){ .z-grid{grid-template-columns:1fr} .z-hero .mini{grid-template-columns:repeat(2,1fr)} }
  @media print{
    body{background:#fff !important}
    .rk-side,.rk-header,.no-print{display:none !important}
    .rk-main{margin-left:0 !important}
    .rk-body{padding:0 !important}
    .z-kutu{box-shadow:none !important}
  }
</style>

@if (!empty($z['hata']))
  <div class="rk-card" style="text-align:center;color:var(--kirmizi)">Rapor hesaplanamadı: {{ $z['hata'] }}</div>
@else
<div class="z-wrap">

  {{-- Tarih gezinme (Flutter: ‹ gün ›) --}}
  <div class="z-nav no-print">
    <a href="/z-raporu?tarih={{ $onceki }}" title="Önceki gün"><span class="material-icons-outlined">chevron_left</span></a>
    <div class="gun">{{ $z['tarih'] }}</div>
    @if ($sonraki)
      <a href="/z-raporu?tarih={{ $sonraki }}" title="Sonraki gün"><span class="material-icons-outlined">chevron_right</span></a>
    @else
      <span class="pas"><span class="material-icons-outlined">chevron_right</span></span>
    @endif
  </div>

  {{-- Ciro hero + mini kartlar --}}
  <div class="z-hero">
    <div class="et">{{ $z['isletme'] }} · Toplam Ciro</div>
    <div class="ciro">{{ $tl($z['ciro']) }}</div>
    <div class="mini">
      <div><div class="d">{{ $n($z['kapanan']) }}</div><div class="e">Adisyon</div></div>
      <div><div class="d">{{ $n($z['misafir']) }}</div><div class="e">Misafir</div></div>
      <div><div class="d">{{ $tl($z['ortalama']) }}</div><div class="e">Ort. Adisyon</div></div>
      <div><div class="d">{{ $tl($z['kisi_basi']) }}</div><div class="e">Kişi Başı</div></div>
    </div>
  </div>

  @if (($z['acik_kalan'] ?? 0) > 0)
    <div class="z-uyari">
      <span class="material-icons-outlined" style="font-size:18px">warning_amber</span>
      <span>{{ $n($z['acik_kalan']) }} adisyon hâlâ açık — gün kapanmadan kapatılmalı.</span>
    </div>
  @endif

  <div class="z-grid">

    {{-- Ödeme Dağılımı --}}
    <div class="z-kutu">
      <div class="bas">💳 Ödeme Dağılımı</div>
      @forelse ($z['odeme'] as $o)
        <div class="z-satir">
          <span class="e">{{ mb_strtoupper($o->tip ?? '-') }} <span class="adet">({{ $n($o->adet) }})</span></span>
          <span class="d">{{ $tl($o->tutar) }}</span>
        </div>
      @empty
        <div class="z-bos">Ödeme yok.</div>
      @endforelse
    </div>

    {{-- Servis Tipi --}}
    <div class="z-kutu">
      <div class="bas">🍽️ Servis Tipi</div>
      @forelse ($z['servis'] as $s)
        <div class="z-satir">
          <span class="e">{{ $s['ad'] }} <span class="adet">({{ $n($s['adet']) }})</span></span>
          <span class="d">{{ $tl($s['tutar']) }}</span>
        </div>
      @empty
        <div class="z-bos">Kayıt yok.</div>
      @endforelse
    </div>

    {{-- Maliyet / Kâr --}}
    <div class="z-kutu">
      <div class="bas">💰 Maliyet / Kâr</div>
      <div class="z-satir">
        <span class="e">Food-Cost</span>
        <span class="d" style="color:{{ $myRenk }}">{{ $tl($z['maliyet']) }} <span style="color:var(--sub)">(%{{ $my }})</span></span>
      </div>
      <div class="z-satir">
        <span class="e">Brüt Kâr</span>
        <span class="d" style="color:var(--yesil);font-weight:800">{{ $tl($z['brut_kar']) }}</span>
      </div>
    </div>

    {{-- Kayıp / Sızıntı --}}
    <div class="z-kutu">
      <div class="bas">🎯 Kayıp / Sızıntı</div>
      @php
        $sizinti = [
          ['İskonto', $z['iskonto']],
          ['İkram', $z['ikram']],
          ['Silinen Ürün', $z['void']],
          ['İptal Adisyon (' . $n($z['iptal_adet']) . ')', $z['iptal_tutar']],
          ['Fire', $z['fire']],
        ];
      @endphp
      @foreach ($sizinti as [$ad, $v])
        <div class="z-satir">
          <span class="e">{{ $ad }}</span>
          <span class="d" style="color:var(--kirmizi)">{{ $tl($v) }}</span>
        </div>
      @endforeach
    </div>

    {{-- En Çok Satan (geniş) --}}
    <div class="z-kutu z-genis">
      <div class="bas">🔥 En Çok Satan</div>
      @forelse ($z['top'] as $i => $t)
        <div class="z-satir">
          <span class="e">{{ $i + 1 }}. {{ $t->urun_adi }}</span>
          <span class="d">{{ $n($t->adet) }} adet</span>
        </div>
      @empty
        <div class="z-bos">Satış yok.</div>
      @endforelse
    </div>

  </div>

  <div class="z-dip">{{ $z['isletme'] }} · {{ $z['tarih'] }} · ResteOS Z Raporu · {{ now()->format('d.m.Y H:i') }}</div>

</div>
@endif
@endsection

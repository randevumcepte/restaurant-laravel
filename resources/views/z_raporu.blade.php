@extends('layout.app')
@section('title', 'Gün Sonu (Z Raporu)')
@section('rk_ikon', 'summarize')
@section('baslik', 'Gün Sonu (Z Raporu)')

@section('rk_araclar')
  @if (empty($z['hata']))
    <button onclick="window.print()" class="rk-btn no-print" style="background:var(--mavi)">
      <span class="material-icons-outlined" style="font-size:18px;vertical-align:-4px">print</span> Z Raporunu Yazdır
    </button>
  @endif
@endsection

@push('head')
<style>
/* ===== Z Raporu — Flutter z_raporu_screen BİREBİR ===== */
.z-wrap{max-width:640px;margin:0 auto;padding-bottom:32px}

/* Tarih gezinme */
.z-nav{display:flex;align-items:center;justify-content:center;gap:8px;
  background:var(--card);border-bottom:1px solid var(--line);
  padding:8px 0;margin:0 -1px 16px}
.z-nav .gun{font-size:15px;font-weight:800;color:var(--ink);min-width:180px;text-align:center}
.z-nav a,.z-nav span.pas{width:36px;height:36px;border-radius:10px;display:inline-flex;
  align-items:center;justify-content:center;font-size:24px;
  border:1px solid var(--line);background:var(--card);color:var(--mavi);text-decoration:none}
.z-nav a:hover{background:var(--card2,#f8f9fb)}
.z-nav span.pas{color:var(--line);cursor:default}
body.koyu .z-nav a:hover{background:#232c42}

/* Ciro hero (LinearGradient: mavi→mor1) */
.z-hero{background:linear-gradient(135deg,#4F46E5,#7C3AED);border-radius:20px;
  padding:22px 18px;color:#fff;margin-bottom:14px}
.z-hero .et{font-size:13px;color:rgba(255,255,255,.72)}
.z-hero .ciro{font-size:34px;font-weight:800;margin:2px 0 16px;line-height:1.1}
.z-hero .mini{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.z-hero .mini .mv{font-size:15px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#fff}
.z-hero .mini .me{font-size:10px;color:rgba(255,255,255,.6);margin-top:1px}
@media(max-width:520px){.z-hero .mini{grid-template-columns:repeat(2,1fr)}}

/* Uyarı kutusu (amber, Flutter _uyari) */
.z-uyari{display:flex;align-items:flex-start;gap:8px;
  background:#FFF7ED;border:1px solid #FECDA0;border-radius:12px;
  padding:12px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:#9A3412}
body.koyu .z-uyari{background:rgba(245,158,11,.13);border-color:rgba(245,158,11,.35);color:#FCD34D}

/* Bölüm kutusu (Flutter _kutu) */
.z-kutu{background:var(--card);border:1px solid var(--line);border-radius:18px;
  box-shadow:var(--golge,0 1px 4px rgba(0,0,0,.06));padding:16px;margin-bottom:14px}
.z-kutu .bas{font-weight:800;color:var(--ink);margin-bottom:10px;font-size:14.5px}

/* Satır (Flutter _satir) */
.z-satir{display:flex;align-items:center;justify-content:space-between;
  padding:5px 0;font-size:13.5px;gap:12px;border-bottom:1px solid transparent}
.z-satir .e{color:var(--sub2,#94a3b8);flex:1;min-width:0}
.z-satir .d{font-weight:600;color:var(--ink);white-space:nowrap}
.z-satir .d.kalin{font-weight:800}
.z-bos{color:var(--sub);font-size:13px;padding:5px 0}

/* Yazdır butonu (Flutter FilledButton) */
.z-yazdir-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;
  background:#4F46E5;color:#fff;border:none;border-radius:14px;
  padding:14px 24px;font-size:15px;font-weight:700;cursor:pointer;margin-top:8px;
  box-shadow:0 2px 8px rgba(79,70,229,.28)}
.z-yazdir-btn:hover{background:#4338CA}

/* Dip */
.z-dip{text-align:center;font-size:11px;color:var(--sub);
  margin-top:8px;padding-top:16px;border-top:1px solid var(--line)}

@media print{
  body{background:#fff !important}
  .rk-side,.rk-header,.no-print{display:none !important}
  .rk-main{margin-left:0 !important}
  .rk-body{padding:0 !important}
  .z-kutu{box-shadow:none !important}
  .z-nav{display:none !important}
}
</style>
@endpush

@section('content')
@php
  $tl  = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
  $n   = fn($v) => (int) round((float) $v);
  $my  = (int) ($z['maliyet_yuzde'] ?? 0);
  $myRenk = $my >= 38 ? 'var(--kirmizi,#F43F5E)' : ($my >= 30 ? 'var(--amber,#F59E0B)' : '#059669');
@endphp

@if (!empty($z['hata']))
  <div class="rk-card" style="text-align:center;color:var(--kirmizi)">
    Rapor hesaplanamadı: {{ $z['hata'] }}
  </div>
@else
<div class="z-wrap">

  {{-- Tarih gezinme (Flutter: ‹ gün ›, üst bar altında sabit) --}}
  <div class="z-nav no-print">
    <a href="/z-raporu?tarih={{ $onceki }}" title="Önceki gün">
      <span class="material-icons-outlined">chevron_left</span>
    </a>
    <div class="gun">{{ $z['tarih'] }}</div>
    @if ($sonraki)
      <a href="/z-raporu?tarih={{ $sonraki }}" title="Sonraki gün">
        <span class="material-icons-outlined">chevron_right</span>
      </a>
    @else
      <span class="pas"><span class="material-icons-outlined">chevron_right</span></span>
    @endif
  </div>

  {{-- Ciro hero (Flutter gradient Container) --}}
  <div class="z-hero">
    <div class="et">Toplam Ciro</div>
    <div class="ciro">{{ $tl($z['ciro']) }}</div>
    <div class="mini">
      <div>
        <div class="mv">{{ $n($z['kapanan']) }}</div>
        <div class="me">Adisyon</div>
      </div>
      <div>
        <div class="mv">{{ $n($z['misafir']) }}</div>
        <div class="me">Misafir</div>
      </div>
      <div>
        <div class="mv">{{ $tl($z['ortalama']) }}</div>
        <div class="me">Ort.</div>
      </div>
      <div>
        <div class="mv">{{ $tl($z['kisi_basi']) }}</div>
        <div class="me">Kişi Başı</div>
      </div>
    </div>
  </div>

  {{-- Açık adisyon uyarısı (Flutter _uyari) --}}
  @if (($z['acik_kalan'] ?? 0) > 0)
    <div class="z-uyari">
      <span>⚠️</span>
      <span>{{ $n($z['acik_kalan']) }} adisyon hâlâ açık — gün kapanmadan kapatılmalı.</span>
    </div>
  @endif

  {{-- 💳 Ödeme Dağılımı (Flutter _kutu) --}}
  <div class="z-kutu">
    <div class="bas">💳 Ödeme Dağılımı</div>
    @forelse ($z['odeme'] as $o)
      <div class="z-satir">
        <span class="e">{{ mb_strtoupper($o->tip ?? '-') }} <span style="color:var(--sub);font-weight:400">({{ $n($o->adet) }})</span></span>
        <span class="d">{{ $tl($o->tutar) }}</span>
      </div>
    @empty
      <div class="z-bos">Ödeme yok.</div>
    @endforelse
  </div>

  {{-- 🍽️ Servis Tipi (Flutter _kutu) --}}
  <div class="z-kutu">
    <div class="bas">🍽️ Servis Tipi</div>
    @forelse ($z['servis'] as $s)
      <div class="z-satir">
        <span class="e">{{ $s['ad'] }} <span style="color:var(--sub);font-weight:400">({{ $n($s['adet']) }})</span></span>
        <span class="d">{{ $tl($s['tutar']) }}</span>
      </div>
    @empty
      <div class="z-bos">Kayıt yok.</div>
    @endforelse
  </div>

  {{-- 💰 Maliyet / Kâr (Flutter _kutu) --}}
  <div class="z-kutu">
    <div class="bas">💰 Maliyet / Kâr</div>
    <div class="z-satir">
      <span class="e">Food-Cost</span>
      <span class="d" style="color:{{ $myRenk }}">{{ $tl($z['maliyet']) }} <span style="color:var(--sub);font-weight:400">(%{{ $my }})</span></span>
    </div>
    <div class="z-satir">
      <span class="e">Brüt Kâr</span>
      <span class="d kalin" style="color:#059669">{{ $tl($z['brut_kar']) }}</span>
    </div>
  </div>

  {{-- 🎯 Kayıp / Sızıntı (Flutter _kutu, kırmızı değerler) --}}
  <div class="z-kutu">
    <div class="bas">🎯 Kayıp / Sızıntı</div>
    @php
      $sizinti = [
        ['İskonto',                          $z['iskonto']],
        ['İkram',                             $z['ikram']],
        ['Silinen Ürün',                      $z['void']],
        ['İptal Adisyon (' . $n($z['iptal_adet']) . ')', $z['iptal_tutar']],
        ['Fire',                              $z['fire']],
      ];
    @endphp
    @foreach ($sizinti as [$ad, $v])
      <div class="z-satir">
        <span class="e">{{ $ad }}</span>
        <span class="d" style="color:var(--kirmizi,#F43F5E)">{{ $tl($v) }}</span>
      </div>
    @endforeach
  </div>

  {{-- 🔥 En Çok Satan (Flutter _kutu, tam genişlik) --}}
  <div class="z-kutu">
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

  {{-- Yazdır butonu (Flutter FilledButton.icon) --}}
  <button class="z-yazdir-btn no-print" onclick="window.print()">
    <span class="material-icons-outlined" style="font-size:20px">print</span>
    Z Raporunu Yazdır
  </button>

  <div class="z-dip">{{ $z['isletme'] }} · {{ $z['tarih'] }} · ResteOS Z Raporu · {{ now()->format('d.m.Y H:i') }}</div>

</div>
@endif
@endsection

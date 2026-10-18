@extends('layout.app')
@section('rk_ikon', 'warning_amber')
@section('baslik', 'Üretim Riski')
@section('title', 'Üretim Riski')

@section('content')
@php
  // Flutter uretim_riski_screen BİREBİR (salt-okunur).
  // kritik: ['id','ad','mevcut','kritik','birim']  |  riskli: ['urun_id','urun','yapilabilir','darbogaz']
  $kritik = $kritik ?? [];
  $riskli = $riskli ?? [];
  $esik   = $esik ?? 15;
  $huzur  = count($kritik) === 0 && count($riskli) === 0;
  $mik = function ($v) {
    $v = (float) $v;
    return $v == floor($v) ? number_format($v, 0, ',', '.') : number_format($v, 2, ',', '.');
  };
@endphp

{{-- Özet bant (Flutter gradient bandı) --}}
<div style="padding:16px;border-radius:16px;margin-bottom:18px;display:flex;align-items:center;gap:12px;color:#fff;
            background:{{ $huzur
              ? 'linear-gradient(135deg,var(--yesil),var(--mavi))'
              : 'linear-gradient(135deg,var(--kirmizi),var(--amber))' }}">
  <span class="material-icons-outlined" style="font-size:30px;color:#fff">{{ $huzur ? 'verified' : 'warning_amber' }}</span>
  <div style="font-size:14px;font-weight:600;line-height:1.3;flex:1">
    @if ($huzur)
      Her şey yolunda — kritik malzeme yok, tüm yemekler rahat çıkıyor.
    @else
      {{ count($kritik) }} kritik malzeme · {{ count($riskli) }} yemek riskte. Hemen stok girin.
    @endif
  </div>
  <div style="text-align:right;font-size:12px;opacity:.9">
    <div>Eşik</div>
    <div style="font-size:16px;font-weight:800">≤ {{ $esik }} porsiyon</div>
  </div>
</div>

@if ($huzur)
  <div class="rk-card" style="padding:48px;text-align:center;color:var(--sub)">Risk yok 🎉</div>
@else

  {{-- 📦 Kritik Malzemeler --}}
  @if (count($kritik))
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--kirmizi)"></span>
      <span class="b">📦 Kritik Malzemeler</span>
      <span class="sayi" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent)">{{ count($kritik) }}</span>
    </div>
    <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr));margin-bottom:20px">
      @foreach ($kritik as $k)
        <div class="rk-card" style="display:flex;align-items:center;gap:10px;padding:13px;border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent)">
          <span class="material-icons-outlined" style="color:var(--kirmizi);font-size:20px">inventory_2</span>
          <div style="font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">{{ $k['ad'] }}</div>
          <div style="text-align:right">
            <div style="color:var(--kirmizi);font-weight:700">{{ $mik($k['mevcut']) }} {{ $k['birim'] }}</div>
            <div style="font-size:11px;color:var(--sub)">kritik: {{ $mik($k['kritik']) }}</div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  {{-- 🍽️ Riskli Yemekler --}}
  @if (count($riskli))
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--amber)"></span>
      <span class="b">🍽️ Riskli Yemekler</span>
      <span class="sayi" style="color:var(--amber);background:color-mix(in srgb,var(--amber) 13%,transparent)">{{ count($riskli) }}</span>
    </div>
    <p class="rk-sub" style="font-size:12px;margin:-4px 0 12px">Kalan stokla en fazla kaç porsiyon çıkar (yarı mamül içindeki hammadde dahil).</p>
    <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
      @foreach ($riskli as $u)
        @php
          // Flutter renk mantığı: yapilabilir<=0 kırmızı (ÇIKMIYOR), <=5 amber, else yeşil.
          $yap = (int) $u['yapilabilir'];
          $cikmaz = $yap <= 0;
          $renk = $cikmaz ? 'var(--kirmizi)' : ($yap <= 5 ? 'var(--amber)' : 'var(--yesil)');
        @endphp
        <div class="rk-card" style="display:flex;align-items:center;gap:12px;padding:13px">
          <div style="min-width:0;flex:1">
            <div style="font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $u['urun'] }}</div>
            <div style="font-size:12px;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">darboğaz: {{ $u['darbogaz'] }}</div>
          </div>
          <div style="flex:0 0 auto;padding:8px 12px;border-radius:10px;font-size:13px;font-weight:700;
                      color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 16%,transparent)">
            {{ $cikmaz ? 'ÇIKMIYOR' : $yap . ' porsiyon' }}
          </div>
        </div>
      @endforeach
    </div>
  @endif

@endif
@endsection

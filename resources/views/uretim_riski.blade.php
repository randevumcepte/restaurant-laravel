@extends('layout.app')
@section('rk_ikon', 'warning_amber')
@section('baslik', 'Üretim Riski')
@section('title', 'Üretim Riski')

@push('head')
<style>
/* ── Üretim Riski — Flutter uretim_riski_screen BİREBİR ── */
#ur{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
     --gold:#F6CE63;--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #ur{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}

/* Özet bant */
#ur .bant{display:flex;align-items:center;gap:12px;padding:16px;border-radius:16px;color:#fff;margin-bottom:18px}
#ur .bant .material-icons-outlined{font-size:30px;color:#fff;flex:none}
#ur .bant .bant-mesaj{font-size:14px;font-weight:600;line-height:1.3;flex:1}
#ur .bant .bant-esik{text-align:right;font-size:12px;opacity:.9;flex:none}
#ur .bant .bant-esik .bant-esik-n{font-size:16px;font-weight:800}

/* Bölüm başlığı */
#ur .bolum{display:flex;align-items:center;gap:8px;margin-bottom:8px}
#ur .bolum .cubuk{width:4px;height:18px;border-radius:3px;flex:none}
#ur .bolum .b{font-size:15px;font-weight:700;color:var(--ink)}
#ur .bolum .sayi{padding:2px 8px;border-radius:10px;font-size:12px;font-weight:700}

/* Alt açıklama */
#ur .alt-acik{font-size:12px;color:var(--sub);margin:-2px 0 12px}

/* Izgara */
#ur .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:8px;margin-bottom:20px}

/* Kart (ortak) */
#ur .kart{background:var(--card);border-radius:12px;border:1px solid var(--line);padding:13px;display:flex;align-items:center;gap:10px}
#ur .kart.kirmizi-border{border-color:rgba(244,63,94,.5)}

/* Kritik malzeme kart */
#ur .kart .kart-ikon{font-size:20px;flex:none}
#ur .kart .kart-ad{font-size:14px;font-weight:600;color:var(--ink);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#ur .kart .kart-sag{text-align:right;flex:none}
#ur .kart .kart-mik{font-size:14px;font-weight:700}
#ur .kart .kart-kritik{font-size:11px;color:var(--sub)}

/* Riskli yemek kart */
#ur .kart .kart-info{min-width:0;flex:1}
#ur .kart .kart-urun{font-size:14px;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#ur .kart .kart-darbogaz{font-size:12px;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#ur .kart .kart-porsiyon{flex:none;padding:8px 12px;border-radius:10px;font-size:13px;font-weight:700}

/* Huzur boş durumu */
#ur .huzur{text-align:center;padding:48px;color:var(--sub);font-size:14px;background:var(--card);border-radius:12px;border:1px solid var(--line)}

/* Yenile butonu */
#ur .ust-bar{display:flex;align-items:center;justify-content:flex-end;margin-bottom:4px}
#ur .btn-yenile{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:20px;border:1px solid var(--line);background:var(--card);color:var(--sub);font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
#ur .btn-yenile:hover{background:var(--card2)}
#ur .btn-yenile .material-icons-outlined{font-size:18px}
</style>
@endpush

@section('content')
@php
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

<div id="ur">
  {{-- Yenile butonu (Flutter AppBar actions yenile ikonu) --}}
  <div class="ust-bar">
    <a href="/uretim-riski?esik={{ $esik }}" class="btn-yenile">
      <span class="material-icons-outlined">refresh</span>
      Yenile
    </a>
  </div>

  {{-- Özet bant — Flutter gradient bant --}}
  <div class="bant" style="background: {{ $huzur
      ? 'linear-gradient(135deg, var(--yesil), var(--mavi))'
      : 'linear-gradient(135deg, var(--kirmizi), var(--amber))' }}">
    <span class="material-icons-outlined">{{ $huzur ? 'verified' : 'warning_amber' }}</span>
    <div class="bant-mesaj">
      @if ($huzur)
        Her şey yolunda — kritik malzeme yok, tüm yemekler rahat çıkıyor.
      @else
        {{ count($kritik) }} kritik malzeme · {{ count($riskli) }} yemek riskte. Hemen stok girin.
      @endif
    </div>
    <div class="bant-esik">
      <div>Eşik</div>
      <div class="bant-esik-n">≤ {{ $esik }} porsiyon</div>
    </div>
  </div>

  @if ($huzur)
    {{-- Flutter: "Risk yok 🎉" merkez metin --}}
    <div class="huzur">Risk yok 🎉</div>

  @else

    {{-- 📦 Kritik Malzemeler --}}
    @if (count($kritik))
      <div class="bolum">
        <span class="cubuk" style="background:var(--kirmizi)"></span>
        <span class="b">📦 Kritik Malzemeler</span>
        <span class="sayi" style="color:var(--kirmizi);background:rgba(244,63,94,.13)">{{ count($kritik) }}</span>
      </div>
      <div class="grid">
        @foreach ($kritik as $k)
          <div class="kart kirmizi-border">
            <span class="material-icons-outlined kart-ikon" style="color:var(--kirmizi)">inventory_2</span>
            <div class="kart-ad">{{ $k['ad'] }}</div>
            <div class="kart-sag">
              <div class="kart-mik" style="color:var(--kirmizi)">{{ $mik($k['mevcut']) }} {{ $k['birim'] }}</div>
              <div class="kart-kritik">kritik: {{ $mik($k['kritik']) }}</div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    {{-- 🍽️ Riskli Yemekler --}}
    @if (count($riskli))
      <div class="bolum">
        <span class="cubuk" style="background:var(--amber)"></span>
        <span class="b">🍽️ Riskli Yemekler</span>
        <span class="sayi" style="color:var(--amber);background:rgba(245,158,11,.13)">{{ count($riskli) }}</span>
      </div>
      <p class="alt-acik">Kalan stokla en fazla kaç porsiyon çıkar (yarı mamül içindeki hammadde dahil).</p>
      <div class="grid">
        @foreach ($riskli as $u)
          @php
            // Flutter: yapilabilir<=0 kırmızı (ÇIKMIYOR), <=5 amber, else yeşil
            $yap   = (int) $u['yapilabilir'];
            $cikmaz = $yap <= 0;
            if ($cikmaz)    { $renk = 'var(--kirmizi)'; $bg = 'rgba(244,63,94,.16)'; }
            elseif ($yap <= 5) { $renk = 'var(--amber)';  $bg = 'rgba(245,158,11,.16)'; }
            else             { $renk = 'var(--yesil)';  $bg = 'rgba(16,185,129,.16)'; }
          @endphp
          <div class="kart">
            <div class="kart-info">
              <div class="kart-urun">{{ $u['urun'] }}</div>
              <div class="kart-darbogaz">darboğaz: {{ $u['darbogaz'] }}</div>
            </div>
            <div class="kart-porsiyon" style="color:{{ $renk }};background:{{ $bg }}">
              {{ $cikmaz ? 'ÇIKMIYOR' : $yap . ' porsiyon' }}
            </div>
          </div>
        @endforeach
      </div>
    @endif

  @endif
</div>
@endsection

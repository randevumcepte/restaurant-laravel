@extends('layout.app')
@php
    $tum = $gruplu->flatten(1);
    $toplam = $tum->count();
    $dolu = $tum->filter(fn ($m) => !empty($m['adisyon_id']))->count();
    // Flutter _masaDurum ile BİREBİR (öncelik: Ödendi > Hesap istedi > Sipariş bekliyor > Yeni > Durgun > Serviste)
    $durum = function ($m) {
        $kalemSay = (int) ($m['kalem_say'] ?? 0); $acilisDk = (int) ($m['acilis_dk'] ?? 0);
        $sonDk = (int) ($m['son_siparis_dk'] ?? -1); $odenen = (float) ($m['odenen'] ?? 0);
        $hesap = ($m['hesap_istendi'] ?? false) === true;
        $dk = fn ($d) => $d >= 60 ? (intdiv($d, 60) . ' sa ' . ($d % 60) . ' dk') : ($d . ' dk');
        if ($odenen > 0) return ['#2ECC71', 'Ödendi', 'check_circle'];
        if ($hesap) return ['#9E7CF0', 'Hesap istedi', 'request_quote'];
        if ($kalemSay == 0 && $acilisDk >= 12) return ['#EDA94A', 'Sipariş bekliyor · ' . $dk($acilisDk), 'hourglass_bottom'];
        if ($kalemSay == 0) return ['#5B92F0', 'Yeni açıldı', 'fiber_new'];
        if ($sonDk >= 45) return ['#F2864F', 'Durgun · ' . $dk($sonDk), 'notifications_active'];
        return ['#7E7BEF', 'Serviste', 'restaurant'];
    };
@endphp
@section('title', 'POS / Masalar')
@section('rk_ikon', 'point_of_sale')
@section('baslik', 'Masalar  (' . $dolu . ' / ' . $toplam . ' dolu)')

@push('head')
<style>
#posroot{max-width:1280px}
#posroot .pos-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
#posroot .pos-tab{display:inline-flex;align-items:center;gap:6px;cursor:pointer;border:1px solid #e2e8f0;background:transparent;
  border-radius:20px;padding:7px 16px;font-size:13px;font-weight:700;color:inherit}
body.koyu #posroot .pos-tab{border-color:#2c3650}
#posroot .pos-tab.aktif{background:#4F46E5;border-color:#4F46E5;color:#fff}
#posroot .pos-tab-badge{background:#4F46E5;color:#fff;border-radius:10px;padding:0 6px;font-size:10px;font-weight:700;min-width:16px;text-align:center}
#posroot .pos-tab.aktif .pos-tab-badge{background:rgba(255,255,255,.28)}
#posroot .pos-lejant{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:16px}
#posroot .pos-lejant span{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;color:#64748b}
#posroot .pos-lejant i{width:11px;height:11px;border-radius:3px;display:inline-block}
#posroot .masa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
#posroot .masa-kart{display:flex;flex-direction:column;justify-content:center;min-height:88px;border-radius:16px;
  padding:9px 11px 9px 13px;text-decoration:none;box-shadow:0 4px 10px rgba(0,0,0,.08);overflow:hidden;transition:transform .08s}
#posroot .masa-kart:hover{transform:translateY(-2px)}
#posroot .masa-kart.dolu{background:var(--mr)}
#posroot .masa-kart.dolu, #posroot .masa-kart.dolu *{color:#fff}
#posroot .masa-kart.bos{background:#fff;border:1px solid #e2e8f0}
body.koyu #posroot .masa-kart.bos{background:#1b2235;border-color:#2c3650}
#posroot .satir1{display:flex;align-items:baseline;gap:7px}
#posroot .masa-kart .ad{font-size:16px;font-weight:500;color:#1e293b}
body.koyu #posroot .masa-kart.bos .ad{color:#e2e8f0}
#posroot .masa-kart .kisi{font-size:10.5px;color:#94a3b8}
#posroot .masa-kart.dolu .kisi{color:rgba(255,255,255,.82)}
#posroot .tutar{font-size:21px;font-weight:900;letter-spacing:-.5px;margin-top:4px;line-height:1}
#posroot .musait{font-size:13px;font-weight:600;color:#10B981;margin-top:4px}
#posroot .cip{display:inline-flex;align-items:center;gap:4px;align-self:flex-start;margin-top:6px;
  background:rgba(255,255,255,.24);border-radius:20px;padding:3px 8px;font-size:9.5px;font-weight:600;max-width:100%}
#posroot .cip span.material-icons-outlined{font-size:12px}
#posroot .cip .et{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#posroot .masa-kart.birlesik{background:#f1f5f9;border:1.5px solid #e2e8f0;align-items:center;justify-content:center;text-align:center;color:#64748b}
body.koyu #posroot .masa-kart.birlesik{background:#1b2235;border-color:#2c3650}
#posroot .masa-kart.birlesik .ad2{font-weight:700;text-decoration:line-through;color:#94a3b8;font-size:13px}
#posroot .masa-kart.birlesik .birl{font-size:10px;font-weight:700;color:#4F46E5}
</style>
@endpush

@section('content')
<div id="posroot">
    {{-- Bölge sekmeleri --}}
    <div class="pos-tabs">
        @foreach ($gruplu as $bolge => $masalar)
            @php $bDolu = $masalar->filter(fn ($m) => !empty($m['adisyon_id']))->count(); @endphp
            <button class="pos-tab {{ $loop->first ? 'aktif' : '' }}" data-i="{{ $loop->index }}" onclick="posTab({{ $loop->index }})">
                {{ $bolge }}@if ($bDolu > 0)<span class="pos-tab-badge">{{ $bDolu }}</span>@endif
            </button>
        @endforeach
    </div>

    {{-- Renk açıklaması (lejant) --}}
    <div class="pos-lejant">
        <span><i style="background:#5B92F0"></i>Yeni</span>
        <span><i style="background:#EDA94A"></i>Sipariş bekliyor</span>
        <span><i style="background:#7E7BEF"></i>Serviste</span>
        <span><i style="background:#F2864F"></i>Durgun</span>
        <span><i style="background:#9E7CF0"></i>Hesap istedi</span>
        <span><i style="background:#2ECC71"></i>Ödendi</span>
        <span><i style="background:#9D5DC8"></i>Rezerve</span>
        <span><i style="background:#cbd5e1"></i>Boş</span>
    </div>

    {{-- Bölge bazlı masa ızgarası (sekme ile değişir) --}}
    @foreach ($gruplu as $bolge => $masalar)
        <div class="masa-grid" data-grid="{{ $loop->index }}" @if (!$loop->first) style="display:none" @endif>
            @foreach ($masalar as $m)
                @php
                    $acik = !empty($m['adisyon_id']);
                    $durumStr = $m['durum'] ?? 'bos';
                    $birlesik = $durumStr === 'birlesik';
                    $rez = $m['rezervasyon'] ?? null;
                    $rezerveBos = !$acik && !$birlesik && $rez;
                    $dr = null; $de = null; $di = null;
                    if ($acik) { [$dr, $de, $di] = $durum($m); }
                    elseif ($rezerveBos) { $dr = '#9D5DC8'; }
                    elseif ($durumStr === 'rezerve') { $dr = '#EDA94A'; }
                    elseif ($durumStr === 'kirli') { $dr = '#F2864F'; }
                    $renkli = $acik || $rezerveBos;
                @endphp
                @if ($birlesik)
                    <div class="masa-kart birlesik">
                        <div class="ad2">{{ $m['ad'] }}</div>
                        <span class="material-icons-outlined" style="color:#4F46E5;font-size:15px">merge_type</span>
                        <div class="birl">{{ $m['birlesik_hedef_ad'] ?? '' }} ile birleşik</div>
                    </div>
                @else
                    <a href="/pos/masa/{{ $m['id'] }}" class="masa-kart {{ $renkli ? 'dolu' : 'bos' }}" @if ($renkli) style="--mr:{{ $dr }}" @endif>
                        <div class="satir1"><span class="ad">{{ $m['ad'] }}</span><span class="kisi">{{ $m['kapasite'] }} kişi</span></div>
                        @if ($acik)
                            <div class="tutar">{{ number_format((float) $m['tutar'], 0, ',', '.') }} ₺</div>
                            <div class="cip"><span class="material-icons-outlined">{{ $di }}</span><span class="et">{{ $de }}</span></div>
                        @elseif ($rezerveBos)
                            <div class="tutar" style="font-size:13.5px">📅 {{ $rez['ad'] }}</div>
                            <div class="cip"><span class="material-icons-outlined">event_seat</span><span class="et">Rezerve · {{ $rez['saat'] }} · {{ $rez['kisi'] }} kişi</span></div>
                        @else
                            <div class="musait">Müsait</div>
                        @endif
                    </a>
                @endif
            @endforeach
        </div>
    @endforeach
</div>
<script>
    function posTab(i){
        document.querySelectorAll('#posroot .masa-grid').forEach((g,j)=>g.style.display=(j===i?'grid':'none'));
        document.querySelectorAll('#posroot .pos-tab').forEach((t,j)=>t.classList.toggle('aktif',j===i));
        try{ localStorage.setItem('pos_bolge', i); }catch(e){}
    }
    (function(){ try{ var i=parseInt(localStorage.getItem('pos_bolge')||'0'); var n=document.querySelectorAll('#posroot .masa-grid').length; if(i>0&&i<n) posTab(i); }catch(e){} })();
    setTimeout(()=>location.reload(), 20000); // canli: 20 sn'de bir yenile (aktif bolge korunur)
</script>
@endsection

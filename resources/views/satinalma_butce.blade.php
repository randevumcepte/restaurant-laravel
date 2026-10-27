@extends('layout.app')
@section('rk_ikon', 'savings')
@section('baslik', 'Satınalma Bütçe')
@section('title', 'Satınalma Bütçe')

@push('head')
<style>
#bt{
  --mor1:#7C3AED;--mor2:#A855F7;--mavi:#4F46E5;--mavi2:#6366F1;
  --yesil:#10B981;--yesil2:#34D399;--kirmizi:#F43F5E;--kirmizi2:#FB7185;
  --amber:#F59E0B;--amber2:#FBBF24;--cyan:#06B6D4;--cyan2:#22D3EE;
  --card:#fff;--card2:#f8fafc;--card3:#f1f5f9;--bg:#f6f7fb;
  --ink:#0f172a;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
  --sh1:0 2px 8px rgba(15,23,42,.06);--sh2:0 8px 24px rgba(15,23,42,.08);
  --sh3:0 12px 36px rgba(124,58,237,.18);
}
body.koyu #bt{
  --card:#161d2e;--card2:#1b2235;--card3:#222a40;--bg:#0f1623;
  --ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
  --sh1:0 2px 8px rgba(0,0,0,.3);--sh2:0 8px 24px rgba(0,0,0,.35);--sh3:0 12px 36px rgba(124,58,237,.3);
}
#bt{background:var(--bg);min-height:calc(100vh - 60px)}
#bt *{box-sizing:border-box}
#bt .pano{max-width:1180px;margin:0 auto;padding:22px 22px 40px}

#bt .flash{border-radius:14px;padding:14px 18px;margin-bottom:16px;font-weight:600;font-size:13.5px;display:flex;align-items:center;gap:10px;animation:bts .4s ease}
#bt .flash.ok{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(52,211,153,.08));color:#047857;border:1px solid rgba(16,185,129,.3)}
#bt .flash.err{background:linear-gradient(135deg,rgba(244,63,94,.14),rgba(251,113,133,.08));color:#be123c;border:1px solid rgba(244,63,94,.3)}
@keyframes bts{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

#bt .ipucu{background:var(--card);border-radius:16px;padding:14px 18px;margin-bottom:18px;font-size:13px;color:var(--sub);line-height:1.6;box-shadow:var(--sh1);border-left:4px solid var(--mor1);display:flex;align-items:flex-start;gap:12px}
#bt .ipucu .ikn{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--mor1),var(--mor2));display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
#bt .ipucu b{color:var(--ink)}

#bt .hero{background:var(--card);border-radius:22px;box-shadow:var(--sh2);padding:24px;margin-bottom:18px;position:relative;overflow:hidden}
#bt .hero::before{content:'';position:absolute;top:-80px;right:-80px;width:260px;height:260px;background:radial-gradient(circle,rgba(124,58,237,.12),transparent 70%);border-radius:50%;pointer-events:none}
#bt .hero .ust{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:18px;flex-wrap:wrap;position:relative}
#bt .hero .ay-rozet{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;padding:7px 14px;border-radius:999px;font-weight:700;font-size:12.5px;letter-spacing:.3px;box-shadow:0 4px 12px rgba(124,58,237,.3)}
#bt .hero .ay-rozet .material-icons-outlined{font-size:16px}
#bt .hero .durum{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:700}
#bt .hero .durum.d-ok{background:rgba(16,185,129,.14);color:var(--yesil)}
#bt .hero .durum.d-wn{background:rgba(245,158,11,.16);color:#B45309}
#bt .hero .durum.d-er{background:rgba(244,63,94,.14);color:var(--kirmizi)}
#bt .hero .durum.d-nn{background:var(--card3);color:var(--sub)}
#bt .hero .durum .nk{width:7px;height:7px;border-radius:50%;background:currentColor;animation:nabz 1.6s ease-in-out infinite}
@keyframes nabz{0%,100%{opacity:1}50%{opacity:.4}}

#bt .hero .orta{display:grid;grid-template-columns:150px 1fr;gap:28px;align-items:center;position:relative}
#bt .halka{position:relative;width:150px;height:150px;flex-shrink:0}
#bt .halka svg{transform:rotate(-90deg)}
#bt .halka .yz{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center}
#bt .halka .yz .p{font-size:30px;font-weight:900;line-height:1;letter-spacing:-.5px}
#bt .halka .yz .e{font-size:10.5px;color:var(--sub);margin-top:4px;text-transform:uppercase;letter-spacing:.8px;font-weight:600}

#bt .kpiler{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
#bt .kpi{background:var(--card2);border-radius:14px;padding:14px 16px;border:1px solid var(--line);transition:transform .18s ease,box-shadow .18s ease;position:relative;overflow:hidden}
#bt .kpi:hover{transform:translateY(-2px);box-shadow:var(--sh1)}
#bt .kpi .ust2{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
#bt .kpi .lbl{font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.6px}
#bt .kpi .ikn{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff}
#bt .kpi .ikn .material-icons-outlined{font-size:16px}
#bt .kpi .v{font-size:20px;font-weight:800;line-height:1.1;letter-spacing:-.3px}
#bt .kpi.b .ikn{background:linear-gradient(135deg,var(--mavi),var(--mavi2))}
#bt .kpi.h .ikn{background:linear-gradient(135deg,var(--mor1),var(--mor2))}
#bt .kpi.k .ikn{background:linear-gradient(135deg,var(--yesil),var(--yesil2))}
#bt .kpi.k.minus .ikn{background:linear-gradient(135deg,var(--kirmizi),var(--kirmizi2))}

#bt .hero .alt{margin-top:16px;padding-top:16px;border-top:1px dashed var(--line);display:flex;gap:16px;flex-wrap:wrap;align-items:center;position:relative}
#bt .hero .alt .mini{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--sub)}
#bt .hero .alt .mini b{color:var(--ink);font-weight:700}
#bt .uyari{background:linear-gradient(135deg,rgba(244,63,94,.14),rgba(251,113,133,.06));color:#be123c;padding:10px 14px;border-radius:10px;font-weight:700;font-size:13px;display:flex;align-items:center;gap:8px;border:1px solid rgba(244,63,94,.25);margin-top:14px;position:relative}

#bt .form-kart{background:linear-gradient(135deg,var(--card) 0%,var(--card2) 100%);border-radius:18px;box-shadow:var(--sh1);padding:18px 20px;margin-bottom:18px;border:1px solid var(--line)}
#bt .form-kart .baslik2{display:flex;align-items:center;gap:10px;margin-bottom:12px}
#bt .form-kart .baslik2 .ikn{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;display:flex;align-items:center;justify-content:center}
#bt .form-kart .baslik2 .ikn .material-icons-outlined{font-size:18px}
#bt .form-kart .baslik2 b{font-size:14.5px;color:var(--ink)}
#bt .form-kart .baslik2 small{font-size:11.5px;color:var(--sub);display:block;margin-top:2px}
#bt .lbl{font-size:11.5px;font-weight:700;color:var(--sub);margin-bottom:5px;display:block;text-transform:uppercase;letter-spacing:.4px}
#bt .inp{width:100%;padding:11px 13px;border:1.5px solid var(--line);border-radius:11px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;transition:border-color .15s,box-shadow .15s}
#bt .inp:focus{outline:none;border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.15)}
#bt .btn{border:0;border-radius:11px;padding:11px 22px;font-weight:700;color:#fff;cursor:pointer;background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 4px 12px rgba(124,58,237,.3);transition:transform .15s,box-shadow .15s;display:inline-flex;align-items:center;gap:6px;font-size:13.5px}
#bt .btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(124,58,237,.4)}
#bt .btn .material-icons-outlined{font-size:17px}

#bt .baslik{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:22px 2px 12px}
#bt .baslik .sol{display:flex;align-items:center;gap:10px}
#bt .baslik .ikn{width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,var(--cyan),var(--mavi2));color:#fff;display:flex;align-items:center;justify-content:center}
#bt .baslik .ikn .material-icons-outlined{font-size:18px}
#bt .baslik b{font-size:15px;color:var(--ink)}
#bt .baslik small{font-size:12px;color:var(--sub);margin-left:4px}

#bt .ay-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
#bt .ay-kart{background:var(--card);border-radius:16px;box-shadow:var(--sh1);padding:16px;border:1px solid var(--line);transition:transform .18s,box-shadow .18s;position:relative;overflow:hidden}
#bt .ay-kart:hover{transform:translateY(-3px);box-shadow:var(--sh2)}
#bt .ay-kart .strip{position:absolute;top:0;left:0;right:0;height:4px}
#bt .ay-kart .ust3{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
#bt .ay-kart .ay-ad{font-size:13.5px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:7px}
#bt .ay-kart .ay-ad .nk{width:8px;height:8px;border-radius:50%}
#bt .ay-kart .pct-rozet{font-size:12.5px;font-weight:800;padding:4px 10px;border-radius:999px}
#bt .ay-kart .bar{height:8px;border-radius:5px;background:var(--card3);overflow:hidden;margin-bottom:12px}
#bt .ay-kart .bar>div{height:100%;border-radius:5px;transition:width .6s cubic-bezier(.4,0,.2,1)}
#bt .ay-kart .ciftli{display:grid;grid-template-columns:1fr 1fr;gap:10px}
#bt .ay-kart .ciftli .cl{background:var(--card2);border-radius:10px;padding:9px 11px}
#bt .ay-kart .ciftli .cl .l{font-size:10.5px;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;font-weight:700;margin-bottom:3px}
#bt .ay-kart .ciftli .cl .v{font-size:13.5px;font-weight:800;color:var(--ink)}
#bt .ay-kart.bos .pct-rozet{background:var(--card3);color:var(--sub)}

#bt .legend{background:var(--card);border-radius:14px;padding:12px 16px;margin-top:14px;box-shadow:var(--sh1);display:flex;flex-wrap:wrap;gap:14px;align-items:center;font-size:12px;color:var(--sub)}
#bt .legend .lg{display:inline-flex;align-items:center;gap:6px}
#bt .legend .lg .d{width:10px;height:10px;border-radius:50%}
#bt .legend .lg b{color:var(--ink);font-weight:700}

@media(max-width:900px){
  #bt .hero .orta{grid-template-columns:1fr;justify-items:center;text-align:center}
  #bt .kpiler{width:100%}
  #bt .ay-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:560px){
  #bt .pano{padding:14px 12px 32px}
  #bt .kpiler{grid-template-columns:1fr}
  #bt .ay-grid{grid-template-columns:1fr}
  #bt .halka{width:130px;height:130px}
  #bt .halka svg{width:130px;height:130px}
}
</style>
@endpush

@section('content')
@php
  $aylarTr=[1=>'Ocak',2=>'Şubat',3=>'Mart',4=>'Nisan',5=>'Mayıs',6=>'Haziran',7=>'Temmuz',8=>'Ağustos',9=>'Eylül',10=>'Ekim',11=>'Kasım',12=>'Aralık'];
  $ayAdi=fn($ym)=>($aylarTr[(int)substr($ym,5,2)]??'').' '.substr($ym,0,4);
  $tl=fn($v)=>number_format((float)$v,0,',','.').' ₺';

  $buRow=collect($satirlar)->firstWhere('ay',$buAy);
  $buHarcama=$buRow->harcama ?? 0; $buButce=$mevcutButce;
  $buY=$buButce>0 ? round($buHarcama/$buButce*100) : null;
  $buKalan=$buButce-$buHarcama;

  if ($buY === null) { $c='var(--sub)'; $durumCls='d-nn'; $durumTxt='Bütçe yok'; }
  elseif ($buY > 100) { $c='var(--kirmizi)'; $durumCls='d-er'; $durumTxt='Bütçe aşıldı'; }
  elseif ($buY >= 85) { $c='var(--amber)'; $durumCls='d-wn'; $durumTxt='Sınıra yakın'; }
  else { $c='var(--yesil)'; $durumCls='d-ok'; $durumTxt='Kontrol altında'; }
  $dash=$buY===null?0:min(100,$buY);

  $ayGun=(int) date('t', strtotime($buAy.'-01'));
  $gecenGun = ($buAy===date('Y-m')) ? (int)date('j') : $ayGun;
  $gunlukOrt = $gecenGun>0 ? $buHarcama/$gecenGun : 0;
  $projTahmin = $gunlukOrt * $ayGun;
@endphp

<div id="bt">
<div class="pano">

  @if (session('ok'))
    <div class="flash ok"><span class="material-icons-outlined" style="font-size:20px">check_circle</span>{{ session('ok') }}</div>
  @endif
  @if (session('hata'))
    <div class="flash err"><span class="material-icons-outlined" style="font-size:20px">error</span>{{ session('hata') }}</div>
  @endif

  <div class="ipucu">
    <div class="ikn"><span class="material-icons-outlined" style="font-size:18px">lightbulb</span></div>
    <div>
      <b>Bu ekran ne işe yarar?</b> Satın almaya ayda ne kadar para harcanabileceğinin <b>sınırını (bütçe)</b> ve gerçekte ne kadar harcandığını karşılaştırır. Böylece <b>bütçe aşımı</b> olmadan satın alma yönetilir. Harcama = o ayki satın alma siparişlerinin toplamı (iptal/taslak hariç).
      @if(!$duzenle)<br><span style="color:var(--sub2)">Aylık bütçeyi <b>patron/müdür</b> belirler; sen harcamayı takip edersin.</span>@endif
    </div>
  </div>

  {{-- HERO: BU AY --}}
  <div class="hero">
    <div class="ust">
      <div class="ay-rozet"><span class="material-icons-outlined">calendar_month</span> {{ $ayAdi($buAy) }} · Bu Ay</div>
      <div class="durum {{ $durumCls }}"><span class="nk"></span>{{ $durumTxt }}</div>
    </div>

    <div class="orta">
      <div class="halka">
        <svg width="150" height="150" viewBox="0 0 36 36">
          <defs>
            <linearGradient id="grHalka" x1="0" x2="1" y1="0" y2="1">
              <stop offset="0%" stop-color="{{ $c }}" stop-opacity=".55"/>
              <stop offset="100%" stop-color="{{ $c }}"/>
            </linearGradient>
          </defs>
          <circle cx="18" cy="18" r="15.9155" fill="none" stroke="var(--card3)" stroke-width="3.4"/>
          <circle cx="18" cy="18" r="15.9155" fill="none" stroke="url(#grHalka)" stroke-width="3.4" stroke-linecap="round" stroke-dasharray="{{ $dash }} 100"/>
        </svg>
        <div class="yz">
          <div class="p" style="color:{{ $c }}">{{ $buY===null ? '—' : '%'.$buY }}</div>
          <div class="e">kullanım</div>
        </div>
      </div>

      <div class="kpiler">
        <div class="kpi b">
          <div class="ust2"><span class="lbl">Bütçe</span><div class="ikn"><span class="material-icons-outlined">account_balance_wallet</span></div></div>
          <div class="v">{{ $buButce>0 ? $tl($buButce) : '—' }}</div>
        </div>
        <div class="kpi h">
          <div class="ust2"><span class="lbl">Harcama</span><div class="ikn"><span class="material-icons-outlined">shopping_cart</span></div></div>
          <div class="v" style="color:{{ $c }}">{{ $tl($buHarcama) }}</div>
        </div>
        <div class="kpi k {{ $buKalan<0?'minus':'' }}">
          <div class="ust2"><span class="lbl">{{ $buKalan<0?'Aşım':'Kalan' }}</span><div class="ikn"><span class="material-icons-outlined">{{ $buKalan<0?'warning':'savings' }}</span></div></div>
          <div class="v" style="color:{{ $buKalan<0?'var(--kirmizi)':'var(--yesil)' }}">{{ $buY===null ? '—' : $tl(abs($buKalan)) }}</div>
        </div>
      </div>
    </div>

    @if($buY!==null)
      <div class="alt">
        <div class="mini"><span class="material-icons-outlined" style="font-size:16px;color:var(--cyan)">today</span>Günlük ort: <b>{{ $tl($gunlukOrt) }}</b></div>
        @if($buAy===date('Y-m'))
          <div class="mini"><span class="material-icons-outlined" style="font-size:16px;color:var(--mor2)">insights</span>Ay sonu tahmini: <b style="color:{{ $projTahmin>$buButce?'var(--kirmizi)':'var(--ink)' }}">{{ $tl($projTahmin) }}</b></div>
          <div class="mini"><span class="material-icons-outlined" style="font-size:16px;color:var(--amber)">schedule</span><b>{{ $gecenGun }}</b>/{{ $ayGun }} gün geçti</div>
        @endif
      </div>
      @if($buY>100)
        <div class="uyari"><span class="material-icons-outlined" style="font-size:18px">error</span>Bütçe aşıldı — {{ $tl(abs($buKalan)) }} fazla harcama yapıldı.</div>
      @endif
    @endif
  </div>

  {{-- BÜTÇE BELİRLE --}}
  @if ($duzenle)
  <div class="form-kart">
    <div class="baslik2">
      <div class="ikn"><span class="material-icons-outlined">edit_note</span></div>
      <div><b>Bütçe Belirle</b><small>Seçili ay için üst sınırı sen tayin et</small></div>
    </div>
    <form method="POST" action="/satinalma-butce/kaydet" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      @csrf
      <div><span class="lbl">Ay</span><input class="inp" type="month" name="yil_ay" value="{{ $buAy }}" style="width:auto"></div>
      <div style="flex:1;min-width:180px"><span class="lbl">Bütçe (₺)</span><input class="inp" name="tutar" value="{{ $mevcutButce>0 ? number_format($mevcutButce,0,'','') : '' }}" placeholder="örn. 50000" inputmode="numeric"></div>
      <button class="btn" type="submit"><span class="material-icons-outlined">save</span>Kaydet</button>
    </form>
  </div>
  @endif

  {{-- GEÇMİŞ: AY KARTLARI --}}
  <div class="baslik">
    <div class="sol">
      <div class="ikn"><span class="material-icons-outlined">history</span></div>
      <div><b>Son 6 Ay</b><small>bütçe vs harcama</small></div>
    </div>
  </div>

  <div class="ay-grid">
    @foreach ($satirlar as $s)
      @php
        $y=$s->yuzde;
        $bar=$y===null?0:min(100,$y);
        if ($y === null) { $sc='var(--sub2)'; $scBg='rgba(148,163,184,.14)'; $strip='linear-gradient(90deg,var(--sub2),var(--line))'; }
        elseif ($y > 100) { $sc='var(--kirmizi)'; $scBg='rgba(244,63,94,.14)'; $strip='linear-gradient(90deg,var(--kirmizi),var(--kirmizi2))'; }
        elseif ($y >= 85) { $sc='var(--amber)'; $scBg='rgba(245,158,11,.16)'; $strip='linear-gradient(90deg,var(--amber),var(--amber2))'; }
        else { $sc='var(--yesil)'; $scBg='rgba(16,185,129,.14)'; $strip='linear-gradient(90deg,var(--yesil),var(--yesil2))'; }
      @endphp
      <div class="ay-kart {{ $y===null?'bos':'' }}">
        <div class="strip" style="background:{{ $strip }}"></div>
        <div class="ust3">
          <div class="ay-ad"><span class="nk" style="background:{{ $sc }}"></span>{{ $ayAdi($s->ay) }}</div>
          <div class="pct-rozet" style="background:{{ $scBg }};color:{{ $sc }}">{{ $y===null ? '—' : '%'.$y }}</div>
        </div>
        <div class="bar"><div style="width:{{ $bar }}%;background:{{ $strip }}"></div></div>
        <div class="ciftli">
          <div class="cl"><div class="l">Harcama</div><div class="v">{{ $tl($s->harcama) }}</div></div>
          <div class="cl"><div class="l">Bütçe</div><div class="v">{{ $s->butce>0 ? $tl($s->butce) : '—' }}</div></div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="legend">
    <span class="lg"><span class="d" style="background:var(--yesil)"></span><b>≤%85</b> güvenli</span>
    <span class="lg"><span class="d" style="background:var(--amber)"></span><b>%85–100</b> dikkat</span>
    <span class="lg"><span class="d" style="background:var(--kirmizi)"></span><b>>%100</b> aşım</span>
    <span class="lg"><span class="d" style="background:var(--sub2)"></span><b>—</b> bütçe girilmemiş</span>
  </div>

</div>
</div>
@endsection

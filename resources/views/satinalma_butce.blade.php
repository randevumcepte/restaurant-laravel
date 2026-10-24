@extends('layout.app')
@section('rk_ikon', 'savings')
@section('baslik', 'Satınalma Bütçe')
@section('title', 'Satınalma Bütçe')

@push('head')
<style>
#bt{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #bt{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#bt{background:var(--bg);min-height:calc(100vh - 60px)}#bt *{box-sizing:border-box}
#bt .pano{max-width:960px;margin:0 auto;padding:20px 22px 32px}
#bt .aciklama{background:var(--card);border-radius:14px;padding:12px 15px;margin-bottom:16px;font-size:12.5px;color:var(--sub);line-height:1.55;box-shadow:0 2px 8px rgba(0,0,0,.05);border-left:4px solid var(--mor1)}
#bt .buay{background:var(--card);border-radius:18px;box-shadow:0 2px 12px rgba(0,0,0,.08);padding:20px;margin-bottom:16px;display:flex;align-items:center;gap:22px;flex-wrap:wrap}
#bt .buay .halka{position:relative;width:104px;height:104px;flex-shrink:0}
#bt .buay .halka .yz{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
#bt .buay .halka .yz .p{font-size:22px;font-weight:900;line-height:1}
#bt .buay .halka .yz .e{font-size:10px;color:var(--sub)}
#bt .buay .sag{flex:1;min-width:220px}
#bt .buay .ay{font-size:13px;color:var(--sub);font-weight:600;margin-bottom:8px}
#bt .buay .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
#bt .buay .g .v{font-size:19px;font-weight:800;line-height:1}
#bt .buay .g .l{font-size:11px;color:var(--sub);margin-top:3px}
#bt .form-kart{background:var(--card);border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);padding:14px 16px;margin-bottom:16px}
#bt .lbl{font-size:11.5px;font-weight:700;color:var(--sub);margin-bottom:4px;display:block}
#bt .inp{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:10px;background:var(--card);color:var(--ink);font-size:14px}
#bt .btn{border:0;border-radius:10px;padding:10px 18px;font-weight:700;color:#fff;cursor:pointer;background:var(--mor1)}
#bt .baslik{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:var(--ink);margin:4px 2px 10px}
#bt .gec{background:var(--card);border-radius:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);overflow:hidden}
#bt .gec .satir{display:flex;align-items:center;gap:12px;padding:12px 15px;border-top:1px solid var(--line)}
#bt .gec .satir:first-child{border-top:none}
#bt .gec .ay{width:120px;font-weight:600;font-size:13px;color:var(--ink);flex-shrink:0}
#bt .gec .bar{flex:1;height:10px;border-radius:6px;background:var(--card2);overflow:hidden}
#bt .gec .bar div{height:100%;border-radius:6px}
#bt .gec .rakam{width:150px;text-align:right;font-size:12.5px;color:var(--sub);flex-shrink:0}
#bt .gec .pct{width:70px;text-align:right;font-weight:800;font-size:13px;flex-shrink:0}
#bt .flash{border-radius:12px;padding:12px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px}
#bt .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
#bt .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
@media(max-width:640px){#bt .buay{justify-content:center;text-align:center}#bt .gec .rakam{display:none}}
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
  $c=$buY===null?'var(--sub)':($buY>100?'var(--kirmizi)':($buY>=85?'var(--amber)':'var(--yesil)'));
  $dash=$buY===null?0:min(100,$buY);
@endphp
<div id="bt">
<div class="pano">

  @if (session('ok'))   <div class="flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="flash err">{{ session('hata') }}</div> @endif

  <div class="aciklama">
    <b>Bu ekran ne işe yarar?</b> Satın almaya ayda ne kadar para harcanabileceğinin <b>sınırını (bütçe)</b> ve gerçekte ne kadar harcandığını karşılaştırır. Böylece <b>bütçe aşımı</b> olmadan satın alma yönetilir. Harcama = o ayki satın alma siparişlerinin toplamı (iptal/taslak hariç).
    @if(!$duzenle)<br>ℹ️ Aylık bütçeyi <b>patron/müdür</b> belirler; sen harcamayı takip edersin.@endif
  </div>

  {{-- BU AY --}}
  <div class="buay">
    <div class="halka">
      <svg width="104" height="104" viewBox="0 0 36 36">
        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="var(--card2)" stroke-width="3.2"/>
        <circle cx="18" cy="18" r="15.9155" fill="none" stroke="{{ $c }}" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="{{ $dash }} 100" transform="rotate(-90 18 18)"/>
      </svg>
      <div class="yz"><div class="p" style="color:{{ $c }}">{{ $buY===null ? '—' : '%'.$buY }}</div><div class="e">kullanım</div></div>
    </div>
    <div class="sag">
      <div class="ay">{{ $ayAdi($buAy) }} · bu ay</div>
      @if($buY===null)
        <div style="font-size:15px;font-weight:700;color:var(--ink);margin-bottom:4px">Bu ay için bütçe girilmemiş</div>
        <div style="font-size:12.5px;color:var(--sub)">Harcama: <b>{{ $tl($buHarcama) }}</b>. @if($duzenle)Aşağıdan bütçe belirle.@else Patron bütçe girince takip başlar.@endif</div>
      @else
        <div class="grid">
          <div class="g"><div class="v">{{ $tl($buButce) }}</div><div class="l">Bütçe</div></div>
          <div class="g"><div class="v" style="color:{{ $c }}">{{ $tl($buHarcama) }}</div><div class="l">Harcama</div></div>
          <div class="g"><div class="v" style="color:{{ $buKalan<0?'var(--kirmizi)':'var(--yesil)' }}">{{ $tl($buKalan) }}</div><div class="l">{{ $buKalan<0?'Aşım':'Kalan' }}</div></div>
        </div>
        @if($buY>100)<div style="margin-top:10px;color:var(--kirmizi);font-weight:700;font-size:12.5px">⚠️ Bütçe aşıldı! ({{ $tl(abs($buKalan)) }} fazla)</div>@endif
      @endif
    </div>
  </div>

  {{-- BÜTÇE BELİRLE (patron/müdür) --}}
  @if ($duzenle)
  <div class="form-kart">
    <form method="POST" action="/satinalma-butce/kaydet" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
      @csrf
      <div><span class="lbl">Ay</span><input class="inp" type="month" name="yil_ay" value="{{ $buAy }}" style="width:auto"></div>
      <div style="flex:1;min-width:160px"><span class="lbl">Bütçe (₺)</span><input class="inp" name="tutar" value="{{ $mevcutButce>0 ? number_format($mevcutButce,0,'','') : '' }}" placeholder="örn. 50000" inputmode="numeric"></div>
      <button class="btn" type="submit">Bütçe Kaydet</button>
    </form>
  </div>
  @endif

  {{-- GEÇMİŞ AYLAR --}}
  <div class="baslik"><span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">history</span> Son 6 Ay</div>
  <div class="gec">
    @foreach ($satirlar as $s)
      @php $y=$s->yuzde; $bar=$y===null?0:min(100,$y); $sc=$y===null?'var(--sub2)':($y>100?'var(--kirmizi)':($y>=85?'var(--amber)':'var(--yesil)')); @endphp
      <div class="satir">
        <div class="ay">{{ $ayAdi($s->ay) }}</div>
        <div class="bar"><div style="width:{{ $bar }}%;background:{{ $sc }}"></div></div>
        <div class="rakam">{{ $tl($s->harcama) }} @if($s->butce>0)/ {{ $tl($s->butce) }}@endif</div>
        <div class="pct" style="color:{{ $sc }}">{{ $y===null ? '—' : '%'.$y }}</div>
      </div>
    @endforeach
  </div>
  <div style="font-size:11px;color:var(--sub);margin-top:8px">🟢 ≤%85 · 🟡 %85-100 · 🔴 bütçe aşımı · "—" bütçe girilmemiş</div>

</div>
</div>
@endsection

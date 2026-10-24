@extends('layout.app')
@section('rk_ikon', 'savings')
@section('baslik', 'Satınalma Bütçe')
@section('title', 'Satınalma Bütçe')

@section('content')
<div class="rk-body">
<div style="max-width:820px">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:10px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Aylık Bütçe vs Gerçek Harcama</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 14px">Gerçek harcama = o ayki satın alma siparişlerinin toplamı (iptal/taslak hariç). Bütçeyi sahip/müdür belirler.</div>

  @if ($duzenle)
  <div class="rk-card" style="padding:14px;margin-bottom:16px">
    <form method="POST" action="/satinalma-butce/kaydet" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
      @csrf
      <div><label class="rk-lbl">Ay</label><input class="rk-inp" type="month" name="yil_ay" value="{{ $buAy }}" style="width:auto"></div>
      <div style="flex:1;min-width:160px"><label class="rk-lbl">Bütçe (₺)</label><input class="rk-inp" name="tutar" value="{{ $mevcutButce>0 ? number_format($mevcutButce,2,',','') : '' }}" placeholder="örn. 50000"></div>
      <button class="rk-btn" style="background:var(--mor1)" type="submit">Bütçe Kaydet</button>
    </form>
  </div>
  @endif

  <div class="rk-card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="background:var(--card2);text-align:left">
          <th style="padding:10px 12px">Ay</th>
          <th style="padding:10px 12px;text-align:right">Bütçe</th>
          <th style="padding:10px 12px;text-align:right">Harcama</th>
          <th style="padding:10px 12px;text-align:right">Kalan</th>
          <th style="padding:10px 12px;width:180px">Doluluk</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($satirlar as $s)
          @php
            $y = $s->yuzde; $bar = $y===null ? 0 : min(100,$y);
            $c = $y===null ? 'var(--sub)' : ($y>100 ? 'var(--kirmizi)' : ($y>=85 ? 'var(--amber)' : 'var(--yesil)'));
            $kalan = $s->butce - $s->harcama;
          @endphp
          <tr style="border-top:1px solid var(--line)">
            <td style="padding:10px 12px;font-weight:600">{{ \Carbon\Carbon::parse($s->ay.'-01')->translatedFormat('F Y') }}</td>
            <td style="padding:10px 12px;text-align:right">{{ $s->butce>0 ? number_format($s->butce,2,',','.').' ₺' : '—' }}</td>
            <td style="padding:10px 12px;text-align:right;font-weight:700">{{ number_format($s->harcama,2,',','.') }} ₺</td>
            <td style="padding:10px 12px;text-align:right;color:{{ $kalan<0 ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $s->butce>0 ? number_format($kalan,2,',','.').' ₺' : '—' }}</td>
            <td style="padding:10px 12px">
              @if ($y===null)<span class="rk-sub" style="font-size:11.5px">bütçe yok</span>
              @else
                <div style="background:var(--card2);border-radius:20px;height:16px;overflow:hidden;position:relative">
                  <div style="width:{{ $bar }}%;height:100%;background:{{ $c }}"></div>
                </div>
                <div style="font-size:11px;color:{{ $c }};font-weight:700;margin-top:2px">%{{ $y }}@if($y>100) (aşım!)@endif</div>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub" style="font-size:11px;margin-top:8px">🟢 ≤%85 · 🟡 %85-100 · 🔴 bütçe aşımı.</div>
</div>
</div>
@endsection

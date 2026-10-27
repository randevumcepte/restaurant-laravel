@extends('layout.app')
@section('rk_ikon', 'grading')
@section('baslik', 'Tedarikçi Karnesi')
@section('title', 'Tedarikçi Karnesi')

@section('content')
<div class="rk-body">
<div style="max-width:1600px">
  <div class="rk-bolum" style="margin-bottom:6px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Tedarikçi Performansı</span>
    <span class="sayi">{{ count($rows) }}</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 14px">Siparişler ve mal kabul tarihlerinden hesaplanır: <b>zamanında teslim %</b>, sipariş hacmi, ortalama gecikme. En çok harcanandan sıralı.</div>

  @if (count($rows))
  <div class="rk-card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:13px">
      <thead>
        <tr style="background:var(--card2);text-align:left">
          <th style="padding:10px 12px">Tedarikçi</th>
          <th style="padding:10px 12px;text-align:right">Sipariş</th>
          <th style="padding:10px 12px;text-align:right">Toplam Tutar</th>
          <th style="padding:10px 12px;text-align:center">Teslim</th>
          <th style="padding:10px 12px;text-align:center">Zamanında</th>
          <th style="padding:10px 12px;text-align:right">Ort. Gecikme</th>
          <th style="padding:10px 12px">Son Sipariş</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $r)
          @php $zr = $r->zamaninda_pct; $zc = $zr===null ? 'var(--sub)' : ($zr>=90 ? 'var(--yesil)' : ($zr>=70 ? 'var(--amber)' : 'var(--kirmizi)')); @endphp
          <tr style="border-top:1px solid var(--line)">
            <td style="padding:10px 12px;font-weight:600">{{ $r->ad }} @if($r->anlasmali)<span class="rk-rozet" style="background:var(--amber);margin-left:4px">⭐ anlaşmalı</span>@endif</td>
            <td style="padding:10px 12px;text-align:right">{{ $r->siparis }}</td>
            <td style="padding:10px 12px;text-align:right;font-weight:700">{{ number_format((float)$r->tutar, 2, ',', '.') }} ₺</td>
            <td style="padding:10px 12px;text-align:center;color:var(--sub)">{{ $r->teslim }}</td>
            <td style="padding:10px 12px;text-align:center"><span class="rk-rozet" style="background:{{ $zc }}">{{ $zr===null ? 'veri yok' : $zr.'%' }}</span></td>
            <td style="padding:10px 12px;text-align:right;color:{{ $r->ort_gecikme>0 ? 'var(--kirmizi)' : 'var(--sub)' }}">{{ $r->ort_gecikme>0 ? $r->ort_gecikme.' gün' : '—' }}</td>
            <td style="padding:10px 12px;color:var(--sub);font-size:11.5px">{{ $r->son ? \Carbon\Carbon::parse($r->son)->format('d.m.Y') : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub" style="font-size:11px;margin-top:8px">🟢 ≥%90 zamanında · 🟡 %70-89 · 🔴 &lt;%70. "Teslim" = mal kabulü yapılan ve teslim tarihi girilen sipariş sayısı.</div>
  @else
  <div class="rk-card" style="padding:28px;text-align:center">
    <span class="material-icons-outlined" style="font-size:40px;color:var(--sub)">grading</span>
    <div style="font-weight:700;margin-top:8px">Henüz karne verisi yok</div>
    <div class="rk-sub" style="font-size:12.5px;margin-top:6px">Tedarikçilere sipariş geçip mal kabul yaptıkça (teslim tarihli), zamanında teslim performansı burada birikir.</div>
  </div>
  @endif
</div>
</div>
@endsection

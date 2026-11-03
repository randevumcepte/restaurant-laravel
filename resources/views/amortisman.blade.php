@extends('layout.app')
@section('rk_ikon', 'trending_down')
@section('baslik', 'Amortisman')
@section('title', 'Amortisman')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺'; @endphp
<style>
  #am{max-width:960px;margin:0 auto}
  #am *{box-sizing:border-box}
  .am-ozet{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:14px}
  @media(max-width:680px){.am-ozet{grid-template-columns:1fr}}
  .am-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .am-k .l{font-size:12px;color:var(--sub);font-weight:700} .am-k .b{font-size:22px;font-weight:800;color:var(--ink);margin-top:4px;letter-spacing:-.5px}
  .am-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden;overflow-x:auto}
  table{width:100%;border-collapse:collapse;min-width:620px}
  th,td{padding:11px 14px;text-align:right;font-size:13px;border-top:1px solid var(--line)}
  th{font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;text-align:right;background:var(--card2)}
  th:first-child,td:first-child{text-align:left}
  td.ad{font-weight:700;color:var(--ink)} td{color:var(--ink);font-variant-numeric:tabular-nums}
  td .kat{font-size:10px;color:var(--sub);font-weight:600}
  .am-bos{text-align:center;padding:26px;color:var(--sub);font-size:13px}
  .am-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .am-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="am">
  <div class="am-ozet">
    <div class="am-k"><div class="l">Toplam demirbaş (brüt)</div><div class="b">{{ $tl($topBedel) }}</div></div>
    <div class="am-k"><div class="l">Birikmiş amortisman sonrası net</div><div class="b" style="color:#2FBE94">{{ $tl($topNet) }}</div></div>
    <div class="am-k"><div class="l">Bu yılki amortisman gideri</div><div class="b" style="color:#F43F5E">{{ $tl($topYillik) }}</div></div>
  </div>
  <div class="am-tab">
    @if($liste->count())
    <table>
      <thead><tr><th>Demirbaş</th><th>Alış tarihi</th><th>Bedel</th><th>Oran</th><th>Yıllık amort.</th><th>Geçen yıl</th><th>Birikmiş</th><th>Net değer</th></tr></thead>
      <tbody>
        @foreach($liste as $d)
        <tr>
          <td class="ad">{{ $d->ad }}<div class="kat">{{ $d->kategori }}</div></td>
          <td>{{ $d->tarih ? \Carbon\Carbon::parse($d->tarih)->format('d.m.Y') : '—' }}</td>
          <td>{{ $tl($d->bedel) }}</td>
          <td>%{{ $d->oran }}</td>
          <td>{{ $tl($d->yillik) }}</td>
          <td>{{ $d->gecen }}</td>
          <td>{{ $tl($d->birikmis) }}</td>
          <td style="font-weight:800;color:{{ $d->net>0?'#2FBE94':'#F43F5E' }}">{{ $tl($d->net) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <div class="am-bos">Amortismana tabi demirbaş yok. <a href="/demirbas" style="color:var(--mor1);font-weight:700;text-decoration:none">Demirbaş ekle →</a> (alış bedeli + tarihi gir).</div>
    @endif
  </div>
  <div class="am-not"><span class="material-icons-outlined">info</span><span>Oranlar standart VUK sınıflarına göre <b>yaklaşıktır</b> (normal/doğrusal amortisman). Kıst amortisman, yenileme fonu ve kesin oranlar için <b>mali müşavirinle</b> teyit et. Demirbaş kategorisi/bedeli <a href="/demirbas" style="color:var(--mor1);font-weight:700;text-decoration:none">Demirbaş</a> ekranından güncellenir.</span></div>
</div>
</div>
@endsection

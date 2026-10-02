@extends('resteos_yonetim.layout')
@section('baslik','Manuel Ödeme Linki')
@section('content')
@php $tl = fn($n) => number_format((float)$n,2,',','.').' ₺'; @endphp
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>💳 Ödeme Linki Oluştur</h3>
    <form method="post" action="/resteos-yonetim/odeme-linki">@csrf
      <div class="ry-field"><label>Restoran</label>
        <select class="ry-select" name="sube_id"><option value="">— (genel)</option>@foreach($restoranlar as $r)<option value="{{ $r->id }}">{{ $r->ad }}</option>@endforeach</select></div>
      <div class="ry-field"><label>Açıklama</label><input class="ry-input" name="aciklama" placeholder="ör. 1 aylık Standart lisans" required></div>
      <div class="ry-field"><label>Tutar (TL)</label><input class="ry-input" type="number" step="0.01" name="tutar" required></div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Link Oluştur</button>
    </form>
    <p class="ry-mut" style="margin-top:10px">Link restoran yetkilisine gönderilir; ödeme sağlayıcı (PayTR/İyzico) bağlandığında otomatik tahsilata döner.</p>
  </div>
  <div class="ry-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px"><h3 style="margin:0">Son Talepler</h3></div>
    <table class="ry-table">
      <thead><tr><th>Açıklama</th><th>Restoran</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
      <tbody>
        @forelse($talepler as $t)
          <tr>
            <td class="ry-restoran">{{ $t->aciklama }}<div class="zt ry-mut" style="word-break:break-all">{{ url('/odeme-talep/'.$t->token) }}</div></td>
            <td class="ry-mut">{{ $t->restoran ?? '—' }}</td>
            <td style="font-weight:800">{{ $tl($t->tutar) }}</td>
            <td>@if($t->durum==='odendi')<span class="ry-badge success">Ödendi</span>@else<span class="ry-badge warning">Bekliyor</span>@endif</td>
            <td style="text-align:right">@if($t->durum!=='odendi')<form method="post" action="/resteos-yonetim/odeme-linki/{{ $t->id }}/odendi">@csrf<button class="ry-btn ry-btn-ok ry-btn-sm" type="submit">Ödendi</button></form>@endif</td>
          </tr>
        @empty
          <tr><td colspan="5" class="ry-mut" style="text-align:center;padding:30px">Talep yok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@extends('resteos_yonetim.layout')
@section('baslik','Loglar')
@section('content')
<div class="ry-card">
  <div class="ry-flex" style="margin-bottom:14px">
    <a href="/resteos-yonetim/loglar?tab=islem" class="ry-btn {{ $tab==='islem'?'ry-btn-primary':'ry-btn-soft' }} ry-btn-sm">İşlem Logları</a>
    <a href="/resteos-yonetim/loglar?tab=giris" class="ry-btn {{ $tab==='giris'?'ry-btn-primary':'ry-btn-soft' }} ry-btn-sm">Giriş Logları</a>
  </div>
  <table class="ry-table">
    @if($tab==='giris')
      <thead><tr><th>E-posta</th><th>Sonuç</th><th>IP</th><th>Tarih</th></tr></thead>
      <tbody>
        @forelse($kayitlar as $k)
          <tr><td>{{ $k->email }}</td>
            <td>@if($k->basarili)<span class="ry-badge success">Başarılı</span>@else<span class="ry-badge danger">Başarısız</span>@endif</td>
            <td class="ry-mut">{{ $k->ip }}</td><td class="ry-mut">{{ \Carbon\Carbon::parse($k->created_at)->format('d.m.Y H:i') }}</td></tr>
        @empty<tr><td colspan="4" class="ry-mut" style="text-align:center;padding:30px">Kayıt yok.</td></tr>@endforelse
      </tbody>
    @else
      <thead><tr><th>Yönetici</th><th>İşlem</th><th>Detay</th><th>IP</th><th>Tarih</th></tr></thead>
      <tbody>
        @forelse($kayitlar as $k)
          <tr><td class="ry-restoran">{{ $k->yonetici_ad }}</td><td><span class="ry-badge mor">{{ $k->islem }}</span></td>
            <td class="ry-mut">{{ $k->detay }}</td><td class="ry-mut">{{ $k->ip }}</td>
            <td class="ry-mut">{{ \Carbon\Carbon::parse($k->created_at)->format('d.m.Y H:i') }}</td></tr>
        @empty<tr><td colspan="5" class="ry-mut" style="text-align:center;padding:30px">Kayıt yok.</td></tr>@endforelse
      </tbody>
    @endif
  </table>
</div>
@endsection

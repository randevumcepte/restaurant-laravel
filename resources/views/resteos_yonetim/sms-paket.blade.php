@extends('resteos_yonetim.layout')
@section('baslik','SMS Paketleri')
@section('content')
@php $tl = fn($n) => number_format((float)$n,0,',','.').' ₺'; @endphp
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>💬 Tanımlı Paketler</h3>
    @if($paketler->isEmpty())<p class="ry-mut">Henüz paket yok. Sağdan ekleyin.</p>@else
    <div class="ry-grid ry-g2">
      @foreach($paketler as $p)
        <div style="border:1.5px solid var(--ry-line);border-radius:12px;padding:14px">
          <div class="ry-between"><b>{{ $p->ad }}</b><span class="ry-badge {{ $p->renk }}">{{ number_format($p->adet,0,',','.') }} SMS</span></div>
          <div style="font-size:22px;font-weight:900;margin:8px 0">{{ $tl($p->ucret) }}</div>
          <form method="post" action="/resteos-yonetim/sms-paket/{{ $p->id }}/sil">@csrf<button class="ry-btn ry-btn-danger ry-btn-sm" type="submit">Sil</button></form>
        </div>
      @endforeach
    </div>
    @endif
  </div>
  <div class="ry-card">
    <h3>➕ Yeni Paket</h3>
    <form method="post" action="/resteos-yonetim/sms-paket">@csrf
      <div class="ry-field"><label>Paket Adı</label><input class="ry-input" name="ad" required placeholder="ör. Başlangıç"></div>
      <div class="ry-row">
        <div class="ry-field"><label>SMS Adedi</label><input class="ry-input" type="number" name="adet" required></div>
        <div class="ry-field"><label>Ücret (TL)</label><input class="ry-input" type="number" step="0.01" name="ucret" required></div>
      </div>
      <div class="ry-field"><label>Renk</label>
        <select class="ry-select" name="renk"><option value="mor">Mor</option><option value="success">Yeşil</option><option value="info">Mavi</option><option value="warning">Turuncu</option><option value="danger">Kırmızı</option></select>
      </div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Kaydet</button>
    </form>
  </div>
</div>
@endsection

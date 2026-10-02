@extends('resteos_yonetim.layout')
@section('baslik','Duyurular')
@section('content')
@php $tr=['bilgi'=>'info','uyari'=>'warning','kampanya'=>'mor']; @endphp
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>📢 Yayınlanan Duyurular</h3>
    @forelse($duyurular as $d)
      <div style="padding:12px 0;border-bottom:1px solid var(--ry-line)">
        <div class="ry-between">
          <b>{{ $d->baslik }}</b>
          <span class="ry-badge {{ $tr[$d->tip]??'info' }}">{{ ucfirst($d->tip) }}</span>
        </div>
        <div class="ry-mut" style="margin:4px 0">{{ $d->icerik }}</div>
        <div class="ry-between">
          <span class="zt ry-mut">{{ $d->hedef==='hepsi'?'Tüm restoranlar':'Tek restoran' }} · {{ \Carbon\Carbon::parse($d->created_at)->format('d.m.Y H:i') }}</span>
          <form method="post" action="/resteos-yonetim/duyurular/{{ $d->id }}/sil">@csrf<button class="ry-btn ry-btn-danger ry-btn-sm" type="submit">Sil</button></form>
        </div>
      </div>
    @empty
      <p class="ry-mut">Henüz duyuru yok.</p>
    @endforelse
  </div>
  <div class="ry-card">
    <h3>➕ Yeni Duyuru</h3>
    <form method="post" action="/resteos-yonetim/duyurular">@csrf
      <div class="ry-field"><label>Başlık</label><input class="ry-input" name="baslik" required></div>
      <div class="ry-field"><label>İçerik</label><textarea class="ry-input" name="icerik" rows="3" required></textarea></div>
      <div class="ry-row">
        <div class="ry-field"><label>Tip</label><select class="ry-select" name="tip"><option value="bilgi">Bilgi</option><option value="uyari">Uyarı</option><option value="kampanya">Kampanya</option></select></div>
        <div class="ry-field"><label>Hedef</label><select class="ry-select" name="hedef" id="hdf" onchange="document.getElementById('sb').style.display=this.value==='sube'?'block':'none'"><option value="hepsi">Tüm restoranlar</option><option value="sube">Tek restoran</option></select></div>
      </div>
      <div class="ry-field" id="sb" style="display:none"><label>Restoran</label><select class="ry-select" name="sube_id">@foreach($restoranlar as $r)<option value="{{ $r->id }}">{{ $r->ad }}</option>@endforeach</select></div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Yayınla</button>
    </form>
  </div>
</div>
@endsection

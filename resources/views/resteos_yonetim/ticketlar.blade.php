@extends('resteos_yonetim.layout')
@section('baslik','Destek Talepleri')
@section('content')
@php $renk=['acik'=>'danger','islemde'=>'warning','cozumlendi'=>'success','kapali'=>'info']; $onc=['dusuk'=>'info','orta'=>'mor','yuksek'=>'warning','acil'=>'danger']; @endphp
<div class="ry-grid ry-g2">
  <div class="ry-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px"><h3 style="margin:0">🎫 Talepler</h3></div>
    <table class="ry-table">
      <thead><tr><th>#</th><th>Konu</th><th>Restoran</th><th>Öncelik</th><th>Durum</th></tr></thead>
      <tbody>
        @forelse($ticketlar as $t)
          <tr style="cursor:pointer" onclick="location='/resteos-yonetim/ticket/{{ $t->id }}'">
            <td class="ry-mut">#{{ $t->id }}</td>
            <td class="ry-restoran"><a class="ry-satir" href="/resteos-yonetim/ticket/{{ $t->id }}">{{ $t->konu }}</a></td>
            <td class="ry-mut">{{ $t->restoran ?? '—' }}</td>
            <td><span class="ry-badge {{ $onc[$t->oncelik]??'mor' }}">{{ ucfirst($t->oncelik) }}</span></td>
            <td><span class="ry-badge {{ $renk[$t->durum]??'info' }}">{{ ucfirst($t->durum) }}</span></td>
          </tr>
        @empty<tr><td colspan="5" class="ry-mut" style="text-align:center;padding:30px">Talep yok.</td></tr>@endforelse
      </tbody>
    </table>
  </div>

  <div class="ry-card">
    <h3>➕ Yeni Talep</h3>
    <form method="post" action="/resteos-yonetim/ticket">@csrf
      <div class="ry-field"><label>Konu</label><input class="ry-input" name="konu" required></div>
      <div class="ry-row">
        <div class="ry-field"><label>Restoran</label>
          <select class="ry-select" name="sube_id"><option value="">— (genel)</option>
            @foreach($restoranlar as $r)<option value="{{ $r->id }}">{{ $r->ad }}</option>@endforeach
          </select></div>
        <div class="ry-field"><label>Öncelik</label>
          <select class="ry-select" name="oncelik"><option value="dusuk">Düşük</option><option value="orta" selected>Orta</option><option value="yuksek">Yüksek</option><option value="acil">Acil</option></select></div>
      </div>
      <div class="ry-field"><label>İlk Mesaj</label><textarea class="ry-input" name="mesaj" rows="3"></textarea></div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Talep Oluştur</button>
    </form>
  </div>
</div>
@endsection

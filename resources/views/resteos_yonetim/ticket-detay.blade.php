@extends('resteos_yonetim.layout')
@section('baslik','Talep #'.$ticket->id)
@section('content')
@php $renk=['acik'=>'danger','islemde'=>'warning','cozumlendi'=>'success','kapali'=>'info']; @endphp
<a href="/resteos-yonetim/ticket" class="ry-btn ry-btn-soft ry-btn-sm" style="margin-bottom:14px">← Talepler</a>
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>{{ $ticket->konu }} <span class="ry-badge {{ $renk[$ticket->durum]??'info' }}" style="margin-left:auto">{{ ucfirst($ticket->durum) }}</span></h3>
    <p class="ry-mut">Restoran: <b>{{ $ticket->restoran ?? '—' }}</b> · Öncelik: <b>{{ ucfirst($ticket->oncelik) }}</b> · {{ \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y H:i') }}</p>
    <hr class="ry-hr">
    @foreach($mesajlar as $m)
      <div style="padding:10px 0;border-bottom:1px solid var(--ry-line)">
        <div class="ry-between"><b>{{ $m->yazan }}</b><span class="zt ry-mut">{{ \Carbon\Carbon::parse($m->created_at)->format('d.m.Y H:i') }}</span></div>
        <div style="margin-top:4px">{{ $m->mesaj }}</div>
      </div>
    @endforeach
    <form method="post" action="/resteos-yonetim/ticket/{{ $ticket->id }}/yanit" style="margin-top:14px">@csrf
      <div class="ry-field"><textarea class="ry-input" name="mesaj" rows="3" placeholder="Yanıt yaz..." required></textarea></div>
      <button class="ry-btn ry-btn-primary" type="submit">Yanıtla</button>
    </form>
  </div>
  <div class="ry-card" style="align-self:start">
    <h3>⚙️ Durum</h3>
    <form method="post" action="/resteos-yonetim/ticket/{{ $ticket->id }}/durum">@csrf
      <div class="ry-field">
        <select class="ry-select" name="durum">
          @foreach(['acik'=>'Açık','islemde'=>'İşlemde','cozumlendi'=>'Çözümlendi','kapali'=>'Kapalı'] as $k=>$v)
            <option value="{{ $k }}" @selected($ticket->durum===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Güncelle</button>
    </form>
  </div>
</div>
@endsection

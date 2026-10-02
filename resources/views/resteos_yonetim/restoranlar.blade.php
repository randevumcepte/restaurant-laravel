@extends('resteos_yonetim.layout')
@section('baslik','Restoranlar')
@section('content')
@php $tl = fn($n) => number_format((float)$n,0,',','.').' ₺'; @endphp

<div class="ry-card">
  <form method="get" class="ry-row" style="margin-bottom:4px">
    <input class="ry-input" type="text" name="ara" value="{{ $ara }}" placeholder="🔍 Restoran, yetkili, telefon, şehir ara...">
    <select class="ry-select" name="durum" style="max-width:180px">
      <option value="">Tüm Durumlar</option>
      <option value="aktif" @selected($durum==='aktif')>Aktif (Lisanslı)</option>
      <option value="demo" @selected($durum==='demo')>Demo</option>
      <option value="askida" @selected($durum==='askida')>Askıda</option>
      <option value="bitti" @selected($durum==='bitti')>Süresi Bitmiş</option>
    </select>
    <button class="ry-btn ry-btn-primary" type="submit" style="flex:0 0 auto">Filtrele</button>
  </form>
</div>

<div class="ry-card" style="padding:0;overflow:hidden">
  <table class="ry-table">
    <thead>
      <tr>
        <th>Restoran</th><th>Yetkili</th><th>Şehir</th><th>Durum</th><th>Üyelik Bitiş</th><th style="text-align:right">30g Ciro</th><th></th>
      </tr>
    </thead>
    <tbody>
      @forelse($restoranlar as $s)
        @php $d=_ryDurum($s); $bitis=$s->uyelik_bitis?\Carbon\Carbon::parse($s->uyelik_bitis):null; @endphp
        <tr>
          <td class="ry-restoran"><a class="ry-satir" href="/resteos-yonetim/restoran/{{ $s->id }}">{{ $s->ad }}</a>
            <div class="ry-mut">{{ $s->telefon ?? '—' }}</div></td>
          <td>{{ $s->yetkili_ad ?? '—' }}<div class="ry-mut">{{ $s->yetkili_tel ?? '' }}</div></td>
          <td>{{ $s->sehir ?? '—' }}</td>
          <td><span class="ry-badge {{ $d['renk'] }}">{{ $d['ad'] }}</span></td>
          <td>{{ $bitis ? $bitis->format('d.m.Y') : '—' }}</td>
          <td style="text-align:right;font-weight:800">{{ $tl($s->son30) }}</td>
          <td style="text-align:right"><a class="ry-btn ry-btn-soft ry-btn-sm" href="/resteos-yonetim/restoran/{{ $s->id }}">Yönet →</a></td>
        </tr>
      @empty
        <tr><td colspan="7" style="text-align:center;padding:40px" class="ry-mut">Kayıt bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<p class="ry-mut">Toplam {{ $restoranlar->count() }} restoran listeleniyor.</p>
@endsection

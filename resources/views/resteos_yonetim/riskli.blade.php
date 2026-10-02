@extends('resteos_yonetim.layout')
@section('baslik','Riskli Restoranlar')
@section('content')
<div class="ry-card">
  <h3>⚠️ Sağlık Skoru 70 Altı Restoranlar</h3>
  @if(empty($riskli))
    <p class="ry-mut">Riskli restoran yok, hepsi sağlıklı. 👍</p>
  @else
    <table class="ry-table">
      <thead><tr><th>Restoran</th><th>Skor</th><th>Sebepler</th><th></th></tr></thead>
      <tbody>
        @foreach($riskli as $s)
          @php $renk=$s->saglik['skor']>=50?'var(--ry-turuncu)':'var(--ry-kirmizi)'; @endphp
          <tr>
            <td class="ry-restoran"><a class="ry-satir" href="/resteos-yonetim/restoran/{{ $s->id }}">{{ $s->ad }}</a></td>
            <td><b style="color:{{ $renk }};font-size:17px">{{ $s->saglik['skor'] }}</b></td>
            <td class="ry-mut">{{ implode(' · ', $s->saglik['sebep']) }}</td>
            <td style="text-align:right"><a class="ry-btn ry-btn-soft ry-btn-sm" href="/resteos-yonetim/restoran/{{ $s->id }}">İncele →</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</div>
@endsection

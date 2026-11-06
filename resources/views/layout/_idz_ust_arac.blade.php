{{-- Üst şerit: arama, garson çağrısı, tarih. Beklenen: $cagriSay --}}
<div class="idz-ara idz-ust-ara">
  <span class="material-icons-outlined">search</span>
  <input type="search" id="idzGenelAra" placeholder="Restoran içinde ara..." autocomplete="off" aria-label="Restoran içinde ara">
  <div class="idz-ara-sonuc" id="idzAraSonuc"></div>
</div>
<a href="/garson-cagrilari" class="idz-ust-ikon" title="Garson Çağrıları" aria-label="Garson Çağrıları">
  <span class="material-icons-outlined">notifications</span>
  @if (($cagriSay ?? 0) > 0)<b>{{ $cagriSay }}</b>@endif
</a>
<div class="idz-tarih">
  <span class="material-icons-outlined">calendar_month</span>
  <span>{{ now()->setTimezone('Europe/Istanbul')->locale('tr')->translatedFormat('j F Y, l') }}</span>
  <b id="idzSaat">{{ now()->setTimezone('Europe/Istanbul')->format('H:i') }}</b>
</div>

@php
  $denetimL = $denetim ?? [];
  $vd = ['iyi' => ['İYİ', 'var(--yesil)', 90], 'notr' => ['NÖTR', 'var(--sub)', 60], 'dikkat' => ['DİKKAT', 'var(--amber)', 42], 'risk' => ['RİSK', 'var(--kirmizi)', 24]];
@endphp
@if (count($denetimL))
<div class="pai-dep-grid">
  @foreach ($denetimL as $dn)
    @php [$det,$drk,$dpr] = $vd[$dn['durum']] ?? $vd['notr']; $ic = $dn['renk'] ?: 'var(--mavi)'; @endphp
    <div class="pai-dep">
      <div class="dust">
        <div class="dic" style="background:color-mix(in srgb,{{ $ic }} 13%,transparent);color:{{ $ic }}"><span class="material-icons-outlined">{{ $dn['ikon'] ?: 'badge' }}</span></div>
        <div style="min-width:0;flex:1">
          <div class="dad">{{ $dn['ad'] }}</div>
          <span class="droz" style="background:color-mix(in srgb,{{ $drk }} 14%,transparent);color:{{ $drk }}">{{ $det }}</span>
          @if (!empty($dn['uyari_say']))<span class="droz" style="background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)">{{ $dn['uyari_say'] }} ⚠</span>@endif
        </div>
      </div>
      <div class="doz">{{ $dn['ozet'] }}</div>
      <div class="dbar"><i style="width:{{ $dpr }}%;background:{{ $drk }}"></i></div>
      <a href="/departman-rapor?id={{ $dn['id'] }}" class="dlink">Detayı gör →</a>
    </div>
  @endforeach
</div>
@else
<div class="pai-blok" style="color:var(--sub);text-align:center;padding:16px">Tanımlı departman yok ya da denetim verisi bulunamadı.</div>
@endif

@php
  $denetimL = $denetim ?? [];
  $vd = ['iyi' => ['İYİ', 'var(--yesil)'], 'notr' => ['NÖTR', 'var(--sub)'], 'dikkat' => ['DİKKAT', 'var(--amber)'], 'risk' => ['RİSK', 'var(--kirmizi)']];
@endphp
@if (count($denetimL))
<div class="pai-blok">
  @php $sonGrup = null; @endphp
  @foreach ($denetimL as $dn)
    @php [$det,$drk] = $vd[$dn['durum']] ?? $vd['notr']; @endphp
    @if (($dn['grup'] ?? null) !== $sonGrup)
      <div style="font-size:11px;font-weight:800;color:var(--sub2);margin:12px 2px 4px;text-transform:uppercase;letter-spacing:.3px">{{ $dn['grup'] ?: 'Diğer' }}</div>
      @php $sonGrup = $dn['grup'] ?? null; @endphp
    @endif
    <div class="pai-sat">
      <div class="pai-an-ic" style="width:34px;height:34px;border-radius:10px;background:color-mix(in srgb,{{ $dn['renk']?:'var(--mavi)' }} 13%,transparent);color:{{ $dn['renk']?:'var(--mavi)' }}"><span class="material-icons-outlined" style="font-size:18px">{{ $dn['ikon']?:'badge' }}</span></div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:13.5px">{{ $dn['ad'] }}</div>
        <div class="rk-sub" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $dn['ozet'] }}</div>
      </div>
      @if (!empty($dn['uyari_say']))<span class="pai-vd" style="background:var(--amber);margin:0">{{ $dn['uyari_say'] }} UYARI</span>@endif
      <span class="pai-vd" style="background:{{ $drk }};margin:0">{{ $det }}</span>
      <a href="/departman-rapor?id={{ $dn['id'] }}" class="pai-btn-sm bos">Detay →</a>
    </div>
  @endforeach
</div>
@else
<div class="pai-blok" style="color:var(--sub);text-align:center;padding:16px">Tanımlı departman yok ya da denetim verisi bulunamadı.</div>
@endif

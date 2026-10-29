@php
  $analizL = $analiz ?? [];
  $bold = fn ($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
  $vd = ['iyi' => ['İYİ', 'var(--yesil)'], 'notr' => ['NÖTR', 'var(--sub)'], 'dikkat' => ['DİKKAT', 'var(--amber)'], 'risk' => ['RİSK', 'var(--kirmizi)']];
@endphp
@forelse ($analizL as $a)
  @php [$et,$rk] = $vd[$a['durum']] ?? $vd['notr']; @endphp
  <div class="pai-an" style="border-left-color:{{ $rk }}">
    <div class="pai-an-bas">
      <div class="pai-an-ic" style="background:color-mix(in srgb,{{ $rk }} 14%,transparent);color:{{ $rk }}"><span class="material-icons-outlined">{{ $a['ikon'] }}</span></div>
      <div style="min-width:0">
        <div class="t">{{ $a['baslik'] }}</div>
        <div class="d">{{ $a['deger'] }}</div>
      </div>
      <span class="pai-vd" style="background:{{ $rk }}">{{ $et }}</span>
    </div>
    <div class="pai-yorum">{!! $bold($a['yorum']) !!}</div>
    @if (!empty($a['risk']))
      <div class="pai-oz" style="background:color-mix(in srgb,var(--kirmizi) 9%,transparent);color:var(--kirmizi)"><span>🔴</span><span><b>Risk:</b> {{ $a['risk'] }}</span></div>
    @endif
    <div class="pai-oz" style="background:var(--card2)">
      <span>✅</span>
      <span style="flex:1;color:var(--sub2)"><b style="color:var(--ink)">Öneri:</b> {!! $bold($a['oneri']) !!}</span>
      @if (!empty($a['link']))<a href="{{ $a['link'] }}" class="pai-btn-sm" style="align-self:center">{{ $a['lbl'] }} →</a>@endif
    </div>
  </div>
@empty
  <div class="pai-blok" style="color:var(--sub);text-align:center;padding:16px">Analiz verisi yok.</div>
@endforelse

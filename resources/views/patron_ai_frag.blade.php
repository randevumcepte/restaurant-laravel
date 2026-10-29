@php
  $v = $brief['veri'] ?? [];
  $tl = fn ($n) => number_format((float) ($n ?? 0), 0, ',', '.') . '₺';
  $kayipTop = array_sum($v['kayip'] ?? []);
  $ad = $ad ?? null;
  $tip = $tip ?? 'sabah';
  $takvim = $v['takvim'] ?? [];
  $eposta = $v['eposta'] ?? [];
  $bold = fn ($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
  $analizL = $analiz ?? [];
  $aiVar = $aiVar ?? false;
  $aiOzet = $aiOzet ?? null;
  $demo = $demo ?? false;
  // durum -> [etiket, renk]
  $vd = ['iyi' => ['İYİ', 'var(--yesil)'], 'notr' => ['NÖTR', 'var(--sub)'], 'dikkat' => ['DİKKAT', 'var(--amber)'], 'risk' => ['RİSK', 'var(--kirmizi)']];
  // KPI durum belirleyiciler
  $ciroY = (float) ($v['ciro_yuzde'] ?? 0);
  $fc = (float) ($v['food_cost_yuzde'] ?? 0);
  $krit = (int) ($v['kritik_sayi'] ?? 0);
  $ciroR = $ciroY >= 5 ? 'var(--yesil)' : ($ciroY <= -10 ? 'var(--kirmizi)' : 'var(--mavi)');
  $fcR = $fc > 0 ? ($fc < 28 ? 'var(--yesil)' : ($fc <= 35 ? 'var(--amber)' : 'var(--kirmizi)')) : 'var(--sub)';
  $kayipR = $kayipTop > 0 ? 'var(--amber)' : 'var(--yesil)';
  $kritR = $krit > 0 ? 'var(--kirmizi)' : 'var(--yesil)';
  $kpiTile = function ($ikon, $renk, $buyuk, $etk, $delta = null, $deltaRenk = null) {
      $tint = "background:color-mix(in srgb, {$renk} 7%, var(--card));border-color:color-mix(in srgb, {$renk} 20%, var(--line))";
      $icBg = "background:color-mix(in srgb, {$renk} 15%, transparent);color:{$renk}";
      $h = '<div class="pai-kpi" style="'.$tint.'">';
      $h .= '<div class="ic" style="'.$icBg.'"><span class="material-icons-outlined">'.$ikon.'</span></div>';
      if ($delta !== null) $h .= '<span class="delta" style="background:color-mix(in srgb,'.($deltaRenk ?: $renk).' 15%,transparent);color:'.($deltaRenk ?: $renk).'">'.$delta.'</span>';
      $h .= '<div class="buyuk">'.$buyuk.'</div><div class="etk">'.$etk.'</div></div>';
      return $h;
  };
@endphp

{{-- kaynak işareti (shell rozeti bunu okur) --}}
<span data-kaynak="{{ $aiVar ? 'ai' : 'kural' }}" style="display:none"></span>

@if ($demo)
<div style="display:flex;align-items:center;gap:8px;background:color-mix(in srgb,var(--amber) 12%,var(--card));border:1px solid color-mix(in srgb,var(--amber) 40%,var(--line));border-radius:14px;padding:11px 15px;margin-bottom:14px;font-size:12.5px">
  <span class="material-icons-outlined" style="font-size:18px;color:var(--amber)">theater_comedy</span>
  <span style="flex:1;color:var(--sub2)"><b style="color:var(--amber)">DEMO VERİ</b> — bunlar gerçek değil; AI'ın sistemde veri olunca nasıl yorumlayacağını gösterir.</span>
  <a href="/patron-ai" class="pai-btn-sm bos">Gerçek veriye dön</a>
</div>
@endif

@if (!$brief)
  <div class="pai-blok" style="text-align:center;color:var(--sub);padding:28px">Bugün için henüz brifing üretilmedi.</div>
@else
  {{-- ============ KPI ŞERİDİ ============ --}}
  <div class="pai-kpiler">
    {!! $kpiTile('payments', $ciroR, $tl($v['dun_ciro'] ?? 0), ($tip==='aksam'?'Bugünkü ciro':'Dünkü ciro'),
        ($ciroY != 0 ? (($ciroY>0?'▲ +':'▼ ').number_format($ciroY,1,',','.').'%') : null), $ciroR) !!}
    {!! $kpiTile('restaurant', $fcR, ($fc>0?'%'.number_format($fc,1,',','.'):'—'), 'Food-Cost') !!}
    {!! $kpiTile('trending_down', $kayipR, $tl($kayipTop), 'Kayıp / Zayiat') !!}
    {!! $kpiTile('groups', 'var(--mor1)', (int)($v['misafir'] ?? 0), 'Misafir'.(!empty($v['kisi_basi'])?' · k.b. '.$tl($v['kisi_basi']):'')) !!}
    {!! $kpiTile('inventory_2', $kritR, $krit, 'Kritik stok', ($krit>0?'dikkat':'temiz'), $kritR) !!}
  </div>

  {{-- ============ AI DEĞERLENDİRMESİ ============ --}}
  @if ($aiVar && !empty($aiOzet))
  <div class="pai-ozet">
    <div class="lbl"><span class="material-icons-outlined" style="font-size:16px">auto_awesome</span> AI Değerlendirmesi</div>
    <div class="metin">{!! $bold($aiOzet) !!}</div>
  </div>
  @endif

  {{-- ============ AI TÜKETİM ============ --}}
  @if (!empty($tuketim))
    @php $yz=$tuketim['yuzde']??0; $br=$yz>=90?'var(--kirmizi)':($yz>=70?'var(--amber)':'var(--yesil)'); @endphp
    <div style="display:flex;align-items:center;gap:10px;margin:0 2px 16px;font-size:11.5px;color:var(--sub)">
      <span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">auto_awesome</span>
      <span>Bu ay dahil AI</span>
      <div style="flex:1;max-width:220px;height:7px;background:var(--card2);border-radius:5px;overflow:hidden"><div style="height:100%;width:{{ $yz }}%;background:{{ $br }};border-radius:5px;transition:width .5s"></div></div>
      <span style="font-weight:700;color:var(--sub2)">{{ $tuketim['kullanim'] }}/{{ $tuketim['hak'] }}</span>
    </div>
  @endif

  {{-- ============ ANALİZ BLOKLARI ============ --}}
  @foreach ($analizL as $a)
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
  @endforeach

  {{-- ============ DEPARTMAN DENETİMİ ============ --}}
  @php $denetimL = $denetim ?? []; @endphp
  @if (count($denetimL))
  <div class="pai-blok">
    <h4 style="color:var(--mor1)"><span class="material-icons-outlined">apartment</span> Departman Denetimi</h4>
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
  @endif

  {{-- ============ DÜNDEN DEVAM ============ --}}
  @php $sk = $sureklilik ?? ['acik'=>[],'kapanan'=>[]]; @endphp
  @if (count($sk['acik']) || count($sk['kapanan']))
  <div class="pai-blok">
    <h4 style="color:var(--mavi)"><span class="material-icons-outlined">history</span> Dünden Devam</h4>
    @foreach ($sk['kapanan'] as $k)
      <div class="pai-sat"><span class="material-icons-outlined" style="font-size:18px;color:var(--yesil)">check_circle</span><span style="flex:1">{{ $k['konu'] }}</span><span class="rk-sub" style="font-size:11px">{{ $k['durum']==='devredildi'?'devredildi':'çözüldü' }}</span></div>
    @endforeach
    @foreach ($sk['acik'] as $a)
      <div class="pai-sat">
        <span class="pai-dot" style="background:var(--amber)"></span>
        <span style="flex:1">{{ $a['konu'] }}</span>
        <span class="rk-sub" style="font-size:11px">{{ $a['gun']>0 ? $a['gun'].' gündür açık' : 'bugün' }}</span>
        <button class="pai-btn-sm bos" style="color:var(--yesil)" onclick="takipDurum({{ $a['id'] }},'cozuldu')">Çözüldü</button>
      </div>
    @endforeach
  </div>
  @endif

  {{-- ============ BUGÜNKÜ GÜNDEM ============ --}}
  @if (count($takvim))
  <div class="pai-blok">
    <h4 style="color:var(--mor1)"><span class="material-icons-outlined">event</span> Bugünkü Gündem</h4>
    @foreach ($takvim as $ev)
      <div class="pai-sat" style="align-items:flex-start">
        <span style="font-weight:800;color:var(--mavi);min-width:44px">{{ $ev['saat'] ?? '' }}</span>
        <div style="flex:1"><div style="font-weight:600">{{ $ev['baslik'] }}</div>@if(!empty($ev['not']))<div class="rk-sub" style="font-size:11.5px">📝 {{ $ev['not'] }}</div>@endif</div>
      </div>
    @endforeach
  </div>
  @endif

  {{-- ============ İLETİŞİM ============ --}}
  @php $wa = $whatsapp ?? ['giden'=>0,'gelen'=>[]]; @endphp
  @if (count($eposta) || $wa['giden'] || count($wa['gelen']))
  <div class="pai-blok">
    <h4 style="color:var(--yesil)"><span class="material-icons-outlined">forum</span> İletişim</h4>
    @if (count($eposta))
      <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:2px 0 6px">📨 Yanıt bekleyen e-posta ({{ count($eposta) }})</div>
      @foreach ($eposta as $e)
        <div class="pai-sat"><span class="material-icons-outlined" style="font-size:17px;color:var(--amber)">mail_outline</span><div style="flex:1"><div style="font-weight:600">{{ $e['subject'] ?? '' }}</div><div class="rk-sub" style="font-size:11px">{{ $e['from'] ?? '' }}</div></div>@if(!empty($e['link']))<a href="{{ $e['link'] }}" target="_blank" class="pai-link">Aç →</a>@endif</div>
      @endforeach
    @endif
    @if ($wa['giden'] || count($wa['gelen']))
      <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:12px 0 6px">💬 WhatsApp</div>
      <div class="rk-sub" style="font-size:12.5px;margin-bottom:4px">Bugün sistemden <b>{{ $wa['giden'] }}</b> mesaj gönderildi.</div>
      @foreach ($wa['gelen'] as $g)
        <div class="pai-sat"><span class="material-icons-outlined" style="font-size:17px;color:#25D366">chat</span><div style="flex:1"><div>{{ $g['mesaj'] }}</div><div class="rk-sub" style="font-size:11px">{{ $g['telefon'] }} · {{ $g['saat'] }}</div></div><a href="/whatsapp" class="pai-link">Yanıtla →</a></div>
      @endforeach
    @endif
    @if (!config('services.google_oauth.client_id') && !config('services.graph_oauth.client_id') && !count($wa['gelen']) && !$wa['giden'])
      <div class="rk-sub" style="font-size:11.5px">Mail/Takvim için <a href="/entegrasyonlar" style="color:var(--mavi)">Entegrasyonlar</a>'dan Gmail/Microsoft bağla; WhatsApp için köprüyü aç.</div>
    @endif
  </div>
  @endif

  {{-- ============ ALT ŞERİT ============ --}}
  <div class="pai-blok" style="display:flex;flex-wrap:wrap;align-items:center;gap:7px">
    <span class="pai-mini">💰 Ciro {{ $tl($v['dun_ciro']??0) }}</span>
    <span class="pai-mini">🍽 FC %{{ $v['food_cost_yuzde']??0 }}</span>
    <span class="pai-mini">📉 Kayıp {{ $tl($kayipTop) }}</span>
    <span class="pai-mini">👥 {{ $v['misafir']??0 }} misafir</span>
    <span class="pai-mini">⚠️ {{ $v['kritik_sayi']??0 }} kritik stok</span>
    <a href="/dashboard" class="pai-link" style="margin-left:auto">Detaylı özet →</a>
  </div>

  @if (config('services.resteos.patron_ai_v2', false) === false)
    <div class="rk-sub" style="font-size:11px;margin-top:4px">💡 AI doğal-dil anlatımı kapalı (kural motoru). Açmak: <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
  @endif
@endif

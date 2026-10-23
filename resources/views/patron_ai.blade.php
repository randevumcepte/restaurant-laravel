@extends('layout.app')
@section('rk_ikon', 'auto_awesome')
@section('baslik', 'Patron AI')
@section('title', 'Patron AI')

@push('head')
<style>
  #pai { max-width: 920px; }
  .pai-orb{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(124,58,237,.35);flex:none}
  .pai-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .pai-blok h4{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 10px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .pai-anlat{font-size:15.5px;line-height:1.65;color:var(--ink)}
  .pai-oneri{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--line)}
  .pai-oneri:last-child{border-bottom:0}
  .pai-no{width:24px;height:24px;border-radius:12px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex:none}
  .pai-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line);font-size:13.5px}
  .pai-sat:last-child{border-bottom:0}
  .pai-dot{width:9px;height:9px;border-radius:50%;flex:none}
  .pai-mini{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border-radius:20px;padding:5px 12px;font-size:12.5px;color:var(--sub2);margin:3px 6px 3px 0}
  .pai-link{color:var(--mavi);font-size:12px;text-decoration:none;white-space:nowrap}
  .pai-vd{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;letter-spacing:.4px}
  .pai-yorum{font-size:14px;line-height:1.62;color:var(--ink);margin:2px 0 8px}
  .pai-oz{display:flex;gap:8px;align-items:flex-start;font-size:13px;line-height:1.5;padding:9px 11px;border-radius:10px;margin-top:7px}
  .pai-ai{background:linear-gradient(135deg,rgba(124,58,237,.10),rgba(79,70,229,.10));border:1px solid rgba(124,58,237,.25)}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="pai">
  @php
    $v = $brief['veri'] ?? [];
    $tl = fn ($n) => number_format((float) ($n ?? 0), 0, ',', '.') . '₺';
    $kayipTop = array_sum($v['kayip'] ?? []);
    $kaynak = $brief['kaynak'] ?? 'kural';
    $adK = $ad ? trim(explode(' ', trim($ad))[0]) : null;
    $saat = (int) now()->format('H');
    $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
    $takvim = $v['takvim'] ?? [];
    $eposta = $v['eposta'] ?? [];
    // **bold** -> <b>bold</b> (önce güvenli kaçış, sonra vurgu)
    $bold = fn ($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
    $anlatimL = $anlatim ?? [];
    $analizL = $analiz ?? [];
    $aiVar = $aiVar ?? false;
    $aiOzet = $aiOzet ?? null;
    $demo = $demo ?? false;
    $vd = ['iyi' => ['İYİ', 'var(--yesil)'], 'notr' => ['NÖTR', 'var(--sub)'], 'dikkat' => ['DİKKAT', 'var(--amber)'], 'risk' => ['RİSK', 'var(--kirmizi)']];
    // öneriye akıllı buton
    $oneriLink = function ($o) {
        $o = mb_strtolower($o);
        if (str_contains($o, 'sipariş') || str_contains($o, 'kritik') || str_contains($o, 'malzeme') || str_contains($o, 'porsiyon')) return ['/siparis-onerisi', 'Sipariş'];
        if (str_contains($o, 'e-posta') || str_contains($o, 'mail')) return ['/entegrasyonlar', 'Mailler'];
        if (str_contains($o, 'tedarikçi') || str_contains($o, 'whatsapp')) return ['/whatsapp', 'WhatsApp'];
        if (str_contains($o, 'food-cost') || str_contains($o, 'ciro') || str_contains($o, 'kayıp')) return ['/dashboard', 'Özet'];
        return [null, null];
    };
  @endphp

  {{-- ÜST: orb + selam + sekme --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
    <div class="pai-orb"><span class="material-icons-outlined" style="color:#fff;font-size:26px">auto_awesome</span></div>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">{{ $selam }}{{ $adK ? ', '.$adK : '' }} 👋</div>
      <div class="rk-sub" style="font-size:12.5px">{{ \Carbon\Carbon::now()->translatedFormat('l, d F') }} · {{ ($tip ?? 'sabah') === 'aksam' ? 'akşam raporu' : 'sabah brifingi' }} @if($brief)· <span style="color:{{ $aiVar?'var(--mor1)':'var(--sub)' }}">{{ $aiVar?'✨ AI yazdı':'kural motoru' }}</span>@endif</div>
    </div>
    <div style="display:flex;gap:6px">
      <a href="/patron-ai?tip=sabah" class="rk-btn {{ (!$demo && ($tip??'sabah')==='sabah')?'':'bos' }}" style="padding:6px 12px;{{ (!$demo && ($tip??'sabah')==='sabah')?'background:var(--mor1)':'' }}">Sabah</a>
      <a href="/patron-ai?tip=aksam" class="rk-btn {{ (!$demo && ($tip??'')==='aksam')?'':'bos' }}" style="padding:6px 12px;{{ (!$demo && ($tip??'')==='aksam')?'background:var(--mor1)':'' }}">Akşam</a>
      <a href="/patron-ai?demo=1" class="rk-btn {{ $demo?'':'bos' }}" style="padding:6px 12px;{{ $demo?'background:var(--amber)':'' }}" title="Sahte veriyle önizleme">🎭 Demo</a>
      <a href="/patron-ai?{{ $demo?'demo=1':'tip='.($tip??'sabah').'&force=1' }}" class="rk-btn bos" style="padding:6px 10px" title="Yenile"><span class="material-icons-outlined" style="font-size:16px">refresh</span></a>
    </div>
  </div>

  @if ($demo)
  <div style="display:flex;align-items:center;gap:8px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);border-radius:12px;padding:10px 14px;margin:4px 2px 14px;font-size:12.5px;color:var(--amber)">
    <span class="material-icons-outlined" style="font-size:18px">theater_comedy</span>
    <span style="flex:1;color:var(--sub2)"><b style="color:var(--amber)">DEMO VERİ</b> — bunlar gerçek değil; AI'ın sistemde veri olunca nasıl yorumlayacağını göstermek içindir.</span>
    <a href="/patron-ai" class="rk-btn bos" style="padding:4px 12px">Gerçek veriye dön</a>
  </div>
  @endif

  @if (!empty($tuketim))
    @php $yz=$tuketim['yuzde']??0; $br=$yz>=90?'var(--kirmizi)':($yz>=70?'var(--amber)':'var(--yesil)'); @endphp
    <div style="display:flex;align-items:center;gap:10px;margin:4px 2px 14px;font-size:11.5px;color:var(--sub)">
      <span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">auto_awesome</span>
      <span>Bu ay dahil AI</span>
      <div style="flex:1;max-width:220px;height:6px;background:var(--card2);border-radius:4px;overflow:hidden"><div style="height:100%;width:{{ $yz }}%;background:{{ $br }}"></div></div>
      <span>{{ $tuketim['kullanim'] }}/{{ $tuketim['hak'] }}</span>
    </div>
  @endif

  @if (!$brief)
    <div class="pai-blok" style="text-align:center;color:var(--sub)">Brifing üretilemedi.</div>
  @else
    {{-- AI DEĞERLENDİRMESİ (anahtar açıkken Haiku'nun özgün genel yorumu) --}}
    @if ($aiVar && !empty($aiOzet))
    <div class="pai-blok pai-ai">
      <h4 style="color:var(--mor1)"><span class="material-icons-outlined" style="font-size:17px">auto_awesome</span> AI Değerlendirmesi</h4>
      <div style="font-size:14.5px;line-height:1.65;color:var(--ink);white-space:pre-line">{!! $bold($aiOzet) !!}</div>
    </div>
    @endif

    {{-- ANALİZ BLOKLARI (her başlık: durum + yorum + risk + öneri) --}}
    @foreach ($analizL as $a)
      @php [$et,$rk] = $vd[$a['durum']] ?? $vd['notr']; @endphp
      <div class="pai-blok">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
          <span class="material-icons-outlined" style="color:{{ $rk }};font-size:22px">{{ $a['ikon'] }}</span>
          <span style="font-weight:800;font-size:15px;color:var(--ink)">{{ $a['baslik'] }}</span>
          <span class="rk-sub" style="font-size:12.5px">· {{ $a['deger'] }}</span>
          <span class="pai-vd" style="background:{{ $rk }};margin-left:auto">{{ $et }}</span>
        </div>
        <div class="pai-yorum">{!! $bold($a['yorum']) !!}</div>
        @if (!empty($a['risk']))
          <div class="pai-oz" style="background:rgba(244,63,94,.10);color:var(--kirmizi)"><span>🔴</span><span><b>Risk:</b> {{ $a['risk'] }}</span></div>
        @endif
        <div class="pai-oz" style="background:var(--card2)">
          <span>✅</span>
          <span style="flex:1;color:var(--sub2)"><b style="color:var(--ink)">Öneri:</b> {!! $bold($a['oneri']) !!}</span>
          @if (!empty($a['link']))<a href="{{ $a['link'] }}" class="rk-btn" style="padding:4px 12px;background:var(--mor1);align-self:center;white-space:nowrap">{{ $a['lbl'] }} →</a>@endif
        </div>
      </div>
    @endforeach

    {{-- DEPARTMAN DENETİMİ — her departmanı tek tek incele --}}
    @php $denetimL = $denetim ?? []; @endphp
    @if (count($denetimL))
    <div class="pai-blok">
      <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">apartment</span> Departman Denetimi</h4>
      @php $sonGrup = null; @endphp
      @foreach ($denetimL as $dn)
        @php [$det,$drk] = $vd[$dn['durum']] ?? $vd['notr']; @endphp
        @if (($dn['grup'] ?? null) !== $sonGrup)
          <div style="font-size:11px;font-weight:700;color:var(--sub2);margin:10px 2px 4px;text-transform:uppercase">{{ $dn['grup'] ?: 'Diğer' }}</div>
          @php $sonGrup = $dn['grup'] ?? null; @endphp
        @endif
        <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line)">
          <span class="material-icons-outlined" style="color:{{ $dn['renk']?:'var(--mavi)' }};font-size:20px">{{ $dn['ikon']?:'badge' }}</span>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:13.5px">{{ $dn['ad'] }}</div>
            <div class="rk-sub" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $dn['ozet'] }}</div>
          </div>
          @if (!empty($dn['uyari_say']))<span class="rk-rozet" style="background:var(--amber)">{{ $dn['uyari_say'] }} uyarı</span>@endif
          <span class="pai-vd" style="background:{{ $drk }}">{{ $det }}</span>
          <a href="/departman-rapor?id={{ $dn['id'] }}" class="rk-btn bos" style="padding:4px 12px;white-space:nowrap">Detay →</a>
        </div>
      @endforeach
    </div>
    @endif

    {{-- DÜNDEN DEVAM (süreklilik) --}}
    @php $sk = $sureklilik ?? ['acik'=>[],'kapanan'=>[]]; @endphp
    @if (count($sk['acik']) || count($sk['kapanan']))
    <div class="pai-blok">
      <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">history</span> Dünden Devam</h4>
      @foreach ($sk['kapanan'] as $k)
        <div class="pai-sat"><span class="material-icons-outlined" style="font-size:18px;color:var(--yesil)">check_circle</span><span style="flex:1">{{ $k['konu'] }}</span><span class="rk-sub" style="font-size:11px">{{ $k['durum']==='devredildi'?'devredildi':'çözüldü' }}</span></div>
      @endforeach
      @foreach ($sk['acik'] as $a)
        <div class="pai-sat">
          <span class="pai-dot" style="background:var(--amber)"></span>
          <span style="flex:1">{{ $a['konu'] }}</span>
          <span class="rk-sub" style="font-size:11px">{{ $a['gun']>0 ? $a['gun'].' gündür açık' : 'bugün' }}</span>
          <button class="rk-btn bos" style="padding:3px 10px;color:var(--yesil)" onclick="takipDurum({{ $a['id'] }},'cozuldu')">Çözüldü</button>
        </div>
      @endforeach
    </div>
    @endif

    {{-- BUGÜNKÜ GÜNDEM (takvim) --}}
    @if (count($takvim))
    <div class="pai-blok">
      <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">event</span> Bugünkü Gündem</h4>
      @foreach ($takvim as $ev)
        <div class="pai-sat" style="align-items:flex-start">
          <span style="font-weight:800;color:var(--mavi);min-width:42px">{{ $ev['saat'] ?? '' }}</span>
          <div style="flex:1"><div style="font-weight:600">{{ $ev['baslik'] }}</div>@if(!empty($ev['not']))<div class="rk-sub" style="font-size:11.5px">📝 {{ $ev['not'] }}</div>@endif</div>
        </div>
      @endforeach
    </div>
    @endif

    {{-- İLETİŞİM (mail + whatsapp) --}}
    @php $wa = $whatsapp ?? ['giden'=>0,'gelen'=>[]]; @endphp
    @if (count($eposta) || $wa['giden'] || count($wa['gelen']))
    <div class="pai-blok">
      <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--yesil)">forum</span> İletişim</h4>
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

    {{-- RAKAM ŞERİDİ (küçük, link) --}}
    <div class="pai-blok" style="display:flex;flex-wrap:wrap;align-items:center;gap:4px">
      <span class="pai-mini">💰 Ciro {{ $tl($v['dun_ciro']??0) }}</span>
      <span class="pai-mini">🍽 FC %{{ $v['food_cost_yuzde']??0 }}</span>
      <span class="pai-mini">📉 Kayıp {{ $tl($kayipTop) }}</span>
      <span class="pai-mini">👥 {{ $v['misafir']??0 }} misafir</span>
      <span class="pai-mini">⚠️ {{ $v['kritik_sayi']??0 }} kritik stok</span>
      <a href="/dashboard" class="pai-link" style="margin-left:auto;font-size:12.5px">Detaylı özet →</a>
    </div>

    @if (config('services.resteos.patron_ai_v2', false) === false)
      <div class="rk-sub" style="font-size:11px;margin-top:4px">💡 AI doğal-dil anlatımı kapalı (kural motoru). Açmak: <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
    @endif
  @endif
</div>
</div>

@push('scripts')
<script>
function takipDurum(id, durum) {
  api('/api/patron-ai/takip-durum', { id: id, durum: durum }).then(function () { location.reload(); }).catch(function () { location.reload(); });
}
</script>
@endpush
@endsection

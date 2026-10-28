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
      <a href="/patron-ai?tip={{ $tip??'sabah' }}&force=1" class="rk-btn bos" style="padding:6px 10px" title="Yenile"><span class="material-icons-outlined" style="font-size:16px">refresh</span></a>
    </div>
  </div>

  {{-- Hazır AI eylem chip'leri — tıklanınca Yap/Vazgeç modal açılır, log zincire yazılır --}}
  <div class="pai-blok" style="padding:12px 14px;margin-top:10px;margin-bottom:14px">
    <div style="font-size:11.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin-bottom:9px;text-transform:uppercase;display:flex;align-items:center;gap:6px">
      <span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">bolt</span> Hazır AI Eylemleri — tıkla, onay iste, log'lanır
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:7px">
      <button class="pai-chip" onclick="paiEylemOner('kritik_siparis_olustur')">🛒 Kritikleri siparişe çevir</button>
      <button class="pai-chip" onclick="paiEylemOner('tedarikci_hatirlat')">📞 Geciken tedarikçileri hatırlat</button>
      <button class="pai-chip" onclick="paiEylemOner('eksik_bilgi_iste')">📝 Eksik özlük bilgisi iste</button>
      <button class="pai-chip" onclick="paiEylemOner('dogum_gunu_kutla')">🎂 Doğum günü kutla</button>
      <button class="pai-chip" onclick="paiEylemOner('gelmeyene_sor')">⏰ Vardiyada gelmeyene sor</button>
    </div>
  </div>

  <style>
  .pai-chip{background:var(--card2);border:1px solid var(--line);border-radius:20px;padding:7px 13px;font-size:12.5px;font-weight:700;color:var(--ink);cursor:pointer;transition:all .13s}
  .pai-chip:hover{background:color-mix(in srgb,var(--mor1) 10%,var(--card));border-color:var(--mor1);color:var(--mor1)}
  .pai-eylem-mod{display:none;position:fixed;inset:0;background:rgba(10,15,30,.55);z-index:9999;align-items:center;justify-content:center;padding:16px}
  .pai-eylem-mod.acik{display:flex}
  .pai-eylem-mod .ic{background:var(--card);border-radius:18px;max-width:520px;width:100%;padding:22px;box-shadow:0 24px 70px rgba(0,0,0,.35)}
  .pai-eylem-mod h3{font-size:17px;font-weight:800;color:var(--ink);margin:0 0 8px;display:flex;align-items:center;gap:9px}
  .pai-eylem-mod .ozet{background:var(--card2);border-left:3px solid var(--mor1);border-radius:10px;padding:12px 14px;font-size:13.5px;line-height:1.5;color:var(--ink);margin:10px 0 16px}
  .pai-eylem-mod .uyari{font-size:11.5px;color:var(--sub);margin-bottom:14px;display:flex;align-items:center;gap:6px}
  .pai-eylem-mod .uyari .material-icons-outlined{font-size:14px;color:var(--amber)}
  .pai-eylem-mod .btnler{display:flex;gap:9px;justify-content:flex-end}
  .pai-eylem-mod .btn-yap{background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:0;border-radius:10px;padding:11px 20px;font-weight:800;cursor:pointer;font-size:13.5px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 12px rgba(16,185,129,.3)}
  .pai-eylem-mod .btn-vaz{background:var(--card2);color:var(--sub);border:1px solid var(--line);border-radius:10px;padding:11px 18px;font-weight:700;cursor:pointer;font-size:13.5px}
  .pai-sonuc{font-size:13px;color:var(--ink);line-height:1.5;padding:12px 14px;border-radius:10px;background:var(--card2);margin-top:12px}
  .pai-sonuc.ok{border-left:3px solid #10B981;color:#047857}
  .pai-sonuc.hata{border-left:3px solid #F43F5E;color:#9f1239}
  </style>

  {{-- Eylem onay modali --}}
  <div class="pai-eylem-mod" id="paiEylemMod" onclick="if(event.target===this)this.classList.remove('acik')">
    <div class="ic">
      <h3><span class="material-icons-outlined" style="color:var(--mor1)">auto_awesome</span> AI eylem onayı</h3>
      <div class="ozet" id="paiEylemOzet"></div>
      <div class="uyari"><span class="material-icons-outlined">lock</span> Onayın IP'n (<b id="paiEylemIp">—</b>) ile birlikte imzalı log'a yazılacak. Geri alınamaz, yasal kanıt niteliğinde.</div>
      <div class="btnler">
        <button class="btn-vaz" onclick="paiEylemVazgec()">Vazgeç</button>
        <button class="btn-yap" id="paiEylemYapBtn" onclick="paiEylemYap()"><span class="material-icons-outlined" style="font-size:17px">check</span> Yap</button>
      </div>
      <div id="paiEylemSonuc" style="display:none" class="pai-sonuc"></div>
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

// AI eylem onay akışı — chip tıklanınca modal aç, Yap/Vazgeç
var _paiEylemLogId = null;
async function paiEylemOner(intent){
  try{
    const r = await fetch('/api/patron-ai/eylem-oner', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({intent})});
    const j = await r.json();
    if (!j.ok || !j.log_id) { alert(j.hata || 'Önerilemedi.'); return; }
    _paiEylemLogId = j.log_id;
    document.getElementById('paiEylemOzet').textContent = j.ozet;
    document.getElementById('paiEylemSonuc').style.display = 'none';
    document.getElementById('paiEylemYapBtn').disabled = false;
    document.getElementById('paiEylemYapBtn').style.opacity = '1';
    // IP göstergesi (basit)
    try { document.getElementById('paiEylemIp').textContent = (await (await fetch('https://api.ipify.org?format=json')).json()).ip || 'tespit edilemedi'; } catch(e){ document.getElementById('paiEylemIp').textContent = 'tespit edilemedi'; }
    document.getElementById('paiEylemMod').classList.add('acik');
  }catch(e){ alert('Bağlantı hatası'); }
}

async function paiEylemYap(){
  if (!_paiEylemLogId) return;
  const btn = document.getElementById('paiEylemYapBtn');
  btn.disabled = true; btn.style.opacity = '.6'; btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">hourglass_bottom</span> Yürütülüyor...';
  try{
    const r = await fetch('/api/ai-eylem/onayla', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({log_id: _paiEylemLogId})});
    const j = await r.json();
    const son = j.sonuc || {};
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block';
    el.className = 'pai-sonuc ' + (son.ok ? 'ok' : 'hata');
    el.textContent = son.mesaj || 'Yürütme sonucu alınamadı.';
    btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">check</span> Yap';
  }catch(e){
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block'; el.className = 'pai-sonuc hata'; el.textContent = 'Bağlantı hatası';
    btn.disabled = false; btn.style.opacity = '1';
  }
}

async function paiEylemVazgec(){
  if (!_paiEylemLogId) { document.getElementById('paiEylemMod').classList.remove('acik'); return; }
  try{
    await fetch('/api/ai-eylem/vazgec', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({log_id: _paiEylemLogId})});
  }catch(e){}
  document.getElementById('paiEylemMod').classList.remove('acik');
  _paiEylemLogId = null;
}
</script>
@endpush
@endsection

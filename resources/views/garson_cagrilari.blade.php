@extends('layout.app')
@section('title', 'Garson Çağrıları')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Garson Çağrıları')
@section('rk_alt', 'QR menüden gelen çağrılar buraya anında düşer')

@push('head')
<style>
/* ====== Garson Çağrıları — canlı çağrı panosu ====== */
#gcroot{--gc-r:16px}
/* Üst araç çubuğu: canlı rozet + ses + tümünü karşıla */
#gcroot .gc-arac{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px}
#gcroot .gc-canli{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;
  background:var(--card);border:1px solid var(--line);font-size:12.5px;font-weight:600;color:var(--sub2)}
#gcroot .gc-canli i{width:8px;height:8px;border-radius:50%;background:var(--yesil);position:relative}
#gcroot .gc-canli i::after{content:"";position:absolute;inset:-4px;border-radius:50%;border:2px solid var(--yesil);
  opacity:0;animation:gcNabiz 2s infinite}
#gcroot .gc-canli.kopuk i{background:var(--kirmizi)}
#gcroot .gc-canli.kopuk i::after{display:none}
@keyframes gcNabiz{0%{transform:scale(.6);opacity:.8}100%{transform:scale(1.6);opacity:0}}
#gcroot .gc-arac .bosluk{flex:1}
#gcroot .gc-dugme{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:12px;
  border:1px solid var(--line);background:var(--card);color:var(--ink);font-size:13.5px;font-weight:700;cursor:pointer;
  font-family:inherit;transition:background .15s,border-color .15s}
#gcroot .gc-dugme:hover{border-color:color-mix(in srgb,var(--mor1) 45%,var(--line))}
#gcroot .gc-dugme .material-icons-outlined{font-size:19px}
#gcroot .gc-dugme.ses-acik{color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 45%,transparent);
  background:color-mix(in srgb,var(--mor1) 9%,var(--card))}
#gcroot .gc-dugme.ana{background:#16A34A;border-color:#16A34A;color:#fff}
#gcroot .gc-dugme.ana:hover{background:#15803D}

/* Özet kutucukları */
#gcroot .gc-ozet{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:22px}
#gcroot .gc-oc{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--card);
  border:1px solid var(--line);border-radius:var(--gc-r);box-shadow:var(--golge);min-width:0}
#gcroot .gc-oc .ik{width:42px;height:42px;flex:0 0 42px;border-radius:12px;display:flex;align-items:center;justify-content:center}
#gcroot .gc-oc .ik .material-icons-outlined{font-size:22px}
#gcroot .gc-oc .d{min-width:0}
#gcroot .gc-oc .s{font-size:12px;color:var(--sub);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-oc .v{font-size:22px;font-weight:800;color:var(--ink);line-height:1.15;font-variant-numeric:tabular-nums}
#gcroot .gc-oc.alarm{border-color:color-mix(in srgb,var(--kirmizi) 45%,transparent);
  background:color-mix(in srgb,var(--kirmizi) 6%,var(--card))}
#gcroot .gc-oc.alarm .v{color:var(--kirmizi)}

/* Bölüm başlığı + filtre çipleri */
#gcroot .gc-bas{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 12px}
#gcroot .gc-bas h3{margin:0;font-size:16px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px}
#gcroot .gc-bas h3 .n{font-size:12px;font-weight:800;padding:2px 9px;border-radius:999px}
#gcroot .gc-cipler{display:flex;gap:6px;flex-wrap:wrap;margin-left:auto}
#gcroot .gc-cip{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:999px;cursor:pointer;
  border:1px solid var(--line);background:var(--card);color:var(--sub2);font-size:12.5px;font-weight:700;font-family:inherit}
#gcroot .gc-cip .material-icons-outlined{font-size:16px}
#gcroot .gc-cip b{font-weight:800;opacity:.75}
#gcroot .gc-cip.aktif{background:var(--ink);border-color:var(--ink);color:var(--card)}
body.koyu #gcroot .gc-cip.aktif{background:var(--mor1);border-color:var(--mor1);color:#fff}

/* Bekleyen çağrı kartları */
#gcroot .gc-izgara{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:14px;margin-bottom:28px}
#gcroot .gc-kart{position:relative;display:flex;flex-direction:column;background:var(--card);
  border:1px solid var(--line);border-radius:var(--gc-r);box-shadow:var(--golge);overflow:hidden;
  transition:transform .15s,box-shadow .15s,opacity .2s}
#gcroot .gc-kart::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--vurgu)}
#gcroot .gc-kart:hover{transform:translateY(-2px)}
#gcroot .gc-kart.geciken{border-color:color-mix(in srgb,var(--kirmizi) 55%,transparent);
  animation:gcAlarm 1.8s ease-in-out infinite}
@keyframes gcAlarm{0%,100%{box-shadow:var(--golge)}50%{box-shadow:0 0 0 4px color-mix(in srgb,var(--kirmizi) 18%,transparent)}}
#gcroot .gc-kart.yeni{animation:gcGir .45s ease-out}
@keyframes gcGir{from{transform:scale(.96);opacity:0}to{transform:none;opacity:1}}
#gcroot .gc-kart.gidiyor{opacity:.35;pointer-events:none}
#gcroot .gc-ust{display:flex;align-items:flex-start;gap:12px;padding:16px 16px 12px 20px}
#gcroot .gc-ikon{width:46px;height:46px;flex:0 0 46px;border-radius:13px;display:flex;align-items:center;justify-content:center;
  background:color-mix(in srgb,var(--vurgu) 14%,transparent);color:var(--vurgu)}
#gcroot .gc-ikon .material-icons-outlined{font-size:24px}
#gcroot .gc-meta{min-width:0;flex:1}
#gcroot .gc-masa{font-size:21px;font-weight:900;color:var(--ink);line-height:1.15;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-masa .ok{color:var(--sub);font-weight:700;margin:0 4px}
#gcroot .gc-tip{display:inline-flex;align-items:center;margin-top:5px;padding:2px 9px;border-radius:999px;
  font-size:12px;font-weight:700;color:var(--vurgu);background:color-mix(in srgb,var(--vurgu) 11%,transparent)}
#gcroot .gc-sure-col{text-align:right;flex:0 0 auto}
#gcroot .gc-sure{font-size:20px;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums;line-height:1.15}
#gcroot .gc-kart.geciken .gc-sure{color:var(--kirmizi)}
#gcroot .gc-saat{font-size:11.5px;color:var(--sub);margin-top:3px}
/* Bekleme çubuğu: 0→5 dk dolar */
#gcroot .gc-bar{height:4px;margin:0 16px 0 20px;border-radius:4px;background:var(--line);overflow:hidden}
#gcroot .gc-bar i{display:block;height:100%;width:0;border-radius:4px;background:var(--amber);transition:width 1s linear}
#gcroot .gc-kart.geciken .gc-bar i{background:var(--kirmizi)}
#gcroot .gc-alt{padding:12px 16px 14px 20px}
#gcroot .gc-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:7px;padding:12px 16px;
  border:none;border-radius:12px;font-size:14.5px;font-weight:800;cursor:pointer;color:#fff;font-family:inherit;
  transition:filter .15s}
#gcroot .gc-btn:hover{filter:brightness(1.07)}
#gcroot .gc-btn.yesil{background:#16A34A}
#gcroot .gc-btn.mor{background:var(--mor1)}
#gcroot .gc-btn .material-icons-outlined{font-size:20px}

/* Boş durum */
#gcroot .gc-bos{text-align:center;padding:48px 20px;margin-bottom:28px;background:var(--card);
  border:1px dashed var(--line);border-radius:var(--gc-r)}
#gcroot .gc-bos .hal{width:72px;height:72px;margin:0 auto;border-radius:50%;display:flex;align-items:center;justify-content:center;
  background:color-mix(in srgb,var(--yesil) 12%,transparent)}
#gcroot .gc-bos .hal .material-icons-outlined{font-size:36px;color:var(--yesil)}
#gcroot .gc-bos .b{font-size:17px;font-weight:800;color:var(--ink);margin-top:14px}
#gcroot .gc-bos .s{font-size:13px;color:var(--sub);margin-top:5px}
#gcroot .gc-filtre-bos{display:none;grid-column:1/-1;text-align:center;padding:26px;color:var(--sub);font-size:13px;
  border:1px dashed var(--line);border-radius:var(--gc-r)}

/* Son karşılananlar — sade liste */
#gcroot .gc-gecmis{background:var(--card);border:1px solid var(--line);border-radius:var(--gc-r);box-shadow:var(--golge);overflow:hidden}
#gcroot .gc-gecmis summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:10px;padding:14px 16px;
  font-size:15px;font-weight:800;color:var(--ink)}
#gcroot .gc-gecmis summary::-webkit-details-marker{display:none}
#gcroot .gc-gecmis summary .n{font-size:12px;font-weight:800;padding:2px 9px;border-radius:999px;
  color:var(--yesil);background:color-mix(in srgb,var(--yesil) 12%,transparent)}
#gcroot .gc-gecmis summary .ac{margin-left:auto;color:var(--sub);transition:transform .2s}
#gcroot .gc-gecmis[open] summary .ac{transform:rotate(180deg)}
#gcroot .gc-satir{display:grid;grid-template-columns:34px minmax(0,1.1fr) minmax(0,1.6fr) auto;align-items:center;gap:12px;
  padding:10px 16px;border-top:1px solid var(--line);font-size:13.5px}
#gcroot .gc-satir .ki{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;
  background:color-mix(in srgb,var(--vurgu) 11%,transparent);color:var(--vurgu)}
#gcroot .gc-satir .ki .material-icons-outlined{font-size:18px}
#gcroot .gc-satir .m{font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-satir .t{color:var(--sub2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-satir .z{text-align:right;white-space:nowrap;color:var(--sub);font-size:12.5px;font-variant-numeric:tabular-nums}
#gcroot .gc-satir .z b{color:var(--ink);font-weight:700;margin-right:6px}

@media (max-width:900px){#gcroot .gc-ozet{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:600px){
  #gcroot .gc-cipler{margin-left:0;width:100%;overflow-x:auto;flex-wrap:nowrap;padding-bottom:2px}
  #gcroot .gc-satir{grid-template-columns:34px minmax(0,1fr) auto}
  #gcroot .gc-satir .t{display:none}
  #gcroot .gc-arac .gc-dugme .yazi{display:none}
}
</style>
@endpush

@php
  /* Flutter _tipYazi ile aynı */
  $tipAdi = function ($t) {
    return $t === 'servise_hazir' ? 'Servise hazır (mutfak)'
      : ($t === 'tasima'         ? 'Taşıma talebi'
      : ($t === 'odeme'          ? 'Ödeme alınacak'
      : ($t === 'hesap'          ? 'Hesap istiyor'
      : ($t === 'siparis'        ? 'Sipariş verdi'
      : 'Garson çağırıyor'))));
  };
  /* Flutter _tipIkon ile aynı */
  $tipIkon = function ($t) {
    return $t === 'servise_hazir' ? 'room_service'
      : ($t === 'tasima'          ? 'swap_horiz'
      : ($t === 'odeme'           ? 'payments'
      : ($t === 'hesap'           ? 'credit_card'
      : ($t === 'siparis'         ? 'receipt_long'
      : 'notifications_active'))));
  };
  $tipRenkVar = function ($t) {
    return $t === 'hesap' ? 'var(--amber)'
      : ($t === 'tasima'           ? 'var(--mor1)'
      : ($t === 'servise_hazir'    ? 'var(--yesil)'
      : ($t === 'odeme'            ? 'var(--amber)'
      : 'var(--kirmizi)')));
  };
  /* Filtre çipleri için kısa adlar */
  $tipKisa = ['garson' => 'Garson', 'hesap' => 'Hesap', 'odeme' => 'Ödeme', 'servise_hazir' => 'Servise hazır', 'siparis' => 'Sipariş', 'tasima' => 'Taşıma'];
  $tipNorm = function ($t) use ($tipKisa) { return isset($tipKisa[$t]) ? $t : 'garson'; };
  $sureYazi = function ($sn) {
    $sn = max(0, (int) $sn);
    if ($sn < 60) return $sn . ' sn';
    if ($sn < 3600) return floor($sn / 60) . ' dk';
    return floor($sn / 3600) . ' sa ' . floor(($sn % 3600) / 60) . ' dk';
  };
  $oncesi = function ($sn) {
    $sn = max(0, (int) $sn);
    if ($sn < 60) return 'az önce';
    if ($sn < 3600) return floor($sn / 60) . ' dk önce';
    if ($sn < 86400) return floor($sn / 3600) . ' sa önce';
    return floor($sn / 86400) . ' gün önce';
  };
  $bekleyen = $bekleyen ?? []; $karsilanan = $karsilanan ?? [];
  $bekleyenSay = count($bekleyen);
  $karsilananSay = count($karsilanan);
  $gecikenSay = 0; $enUzun = 0; $tipSay = [];
  foreach ($bekleyen as $c) {
    $sn = (int) ($c['saniye'] ?? 0);
    if ($sn >= 60) $gecikenSay++;
    $enUzun = max($enUzun, $sn);
    $k = $tipNorm($c['tip'] ?? 'garson');
    $tipSay[$k] = ($tipSay[$k] ?? 0) + 1;
  }
@endphp

@section('content')
<div id="gcroot">

{{-- ===== FLASH ===== --}}
@if (session('ok'))
  <div class="rk-flash ok">✅ {{ session('ok') }}</div>
@endif
@if (session('hata') || !empty($hata))
  <div class="rk-flash err">⚠️ {{ session('hata') ?: $hata }}</div>
@endif

{{-- ===== ARAÇ ÇUBUĞU ===== --}}
<div class="gc-arac">
  <span class="gc-canli" id="gcCanli"><i></i><span id="gcCanliYazi">Canlı · her 5 sn güncellenir</span></span>
  <span class="bosluk"></span>
  <button type="button" class="gc-dugme" id="gcSesBtn" title="Yeni çağrıda sesli uyarı">
    <span class="material-icons-outlined">volume_off</span><span class="yazi">Ses kapalı</span>
  </button>
  @if ($bekleyenSay > 1)
    <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0" data-gc-form data-gc-hepsi>
      @csrf
      <input type="hidden" name="hepsi" value="1">
      <button type="submit" class="gc-dugme ana">
        <span class="material-icons-outlined">done_all</span><span class="yazi">Tümünü karşıla ({{ $bekleyenSay }})</span>
      </button>
    </form>
  @endif
</div>

{{-- ===== ÖZET ===== --}}
<div class="gc-ozet">
  <div class="gc-oc{{ $bekleyenSay ? ' alarm' : '' }}">
    <div class="ik" style="background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:var(--kirmizi)"><span class="material-icons-outlined">notifications_active</span></div>
    <div class="d"><div class="s">Bekleyen çağrı</div><div class="v">{{ $bekleyenSay }}</div></div>
  </div>
  <div class="gc-oc{{ $gecikenSay ? ' alarm' : '' }}">
    <div class="ik" style="background:color-mix(in srgb,var(--amber) 14%,transparent);color:var(--amber)"><span class="material-icons-outlined">timer</span></div>
    <div class="d"><div class="s">1 dk'yı geçen</div><div class="v">{{ $gecikenSay }}</div></div>
  </div>
  <div class="gc-oc">
    <div class="ik" style="background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)"><span class="material-icons-outlined">hourglass_top</span></div>
    <div class="d"><div class="s">En uzun bekleme</div><div class="v" @if($bekleyenSay) data-gc-sn="{{ $enUzun }}" @endif>{{ $bekleyenSay ? $sureYazi($enUzun) : '—' }}</div></div>
  </div>
  <div class="gc-oc">
    <div class="ik" style="background:color-mix(in srgb,var(--yesil) 12%,transparent);color:var(--yesil)"><span class="material-icons-outlined">done_all</span></div>
    <div class="d"><div class="s">Son karşılanan</div><div class="v">{{ $karsilananSay }}</div></div>
  </div>
</div>

{{-- ===== BEKLEYEN ÇAĞRILAR ===== --}}
<div class="gc-bas">
  <h3>Bekleyen Çağrılar
    @if ($bekleyenSay)
      <span class="n" style="background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:var(--kirmizi)">{{ $bekleyenSay }}</span>
    @endif
  </h3>
  @if (count($tipSay) > 1)
    <div class="gc-cipler" id="gcCipler">
      <button type="button" class="gc-cip aktif" data-gc-tip="">Tümü <b>{{ $bekleyenSay }}</b></button>
      @foreach ($tipKisa as $k => $ad)
        @if (!empty($tipSay[$k]))
          <button type="button" class="gc-cip" data-gc-tip="{{ $k }}">
            <span class="material-icons-outlined" style="color:{{ $tipRenkVar($k) }}">{{ $tipIkon($k) }}</span>{{ $ad }} <b>{{ $tipSay[$k] }}</b>
          </button>
        @endif
      @endforeach
    </div>
  @endif
</div>

@if (!$bekleyenSay)
  <div class="gc-bos">
    <div class="hal"><span class="material-icons-outlined">check_circle</span></div>
    <div class="b">Her şey yolunda, bekleyen çağrı yok</div>
    <div class="s">Masalardan QR ile gelen çağrılar burada anında belirir. Ses açıksa yeni çağrıda uyarı çalar.</div>
  </div>
@else
  <div class="gc-izgara" id="gcIzgara">
    @foreach ($bekleyen as $c)
      @php
        $tip      = $c['tip'] ?? 'garson';
        $sn       = (int) ($c['saniye'] ?? 0);
        $tasimaMi = ($tip === 'tasima');
      @endphp
      <div class="gc-kart{{ $sn >= 60 ? ' geciken' : '' }}" data-gc-id="{{ $c['id'] }}" data-gc-kart-tip="{{ $tipNorm($tip) }}" style="--vurgu:{{ $tipRenkVar($tip) }}">
        <div class="gc-ust">
          <div class="gc-ikon"><span class="material-icons-outlined">{{ $tipIkon($tip) }}</span></div>
          <div class="gc-meta">
            <div class="gc-masa">
              {{ $c['masa'] ?? '—' }}@if ($tasimaMi && !empty($c['hedef']))<span class="ok">→</span>{{ $c['hedef'] }}@endif
            </div>
            <span class="gc-tip">{{ $tipAdi($tip) }}</span>
          </div>
          <div class="gc-sure-col">
            <div class="gc-sure" data-gc-sn="{{ $sn }}">{{ $sureYazi($sn) }}</div>
            <div class="gc-saat">{{ $c['saat'] ?? '' }}'de geldi</div>
          </div>
        </div>
        <div class="gc-bar"><i data-gc-bar="{{ $sn }}" style="width:{{ min(100, round($sn / 3)) }}%"></i></div>
        <div class="gc-alt">
          <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0" data-gc-form>
            @csrf
            <input type="hidden" name="id" value="{{ $c['id'] }}">
            @if ($tasimaMi)
              <button type="submit" class="gc-btn mor"><span class="material-icons-outlined">swap_horiz</span> Taşımayı onayla</button>
            @else
              <button type="submit" class="gc-btn yesil"><span class="material-icons-outlined">check</span> Karşılandı</button>
            @endif
          </form>
        </div>
      </div>
    @endforeach
    <div class="gc-filtre-bos" id="gcFiltreBos">Bu tipte bekleyen çağrı yok.</div>
  </div>
@endif

{{-- ===== SON KARŞILANANLAR ===== --}}
@if ($karsilananSay)
  <details class="gc-gecmis" id="gcGecmis" {{ $bekleyenSay ? '' : 'open' }}>
    <summary>
      <span class="material-icons-outlined" style="color:var(--yesil)">history</span>
      Son Karşılananlar <span class="n">{{ $karsilananSay }}</span>
      <span class="material-icons-outlined ac">expand_more</span>
    </summary>
    @foreach ($karsilanan as $c)
      @php $tip = $c['tip'] ?? 'garson'; @endphp
      <div class="gc-satir" style="--vurgu:{{ $tipRenkVar($tip) }}">
        <span class="ki"><span class="material-icons-outlined">{{ $tipIkon($tip) }}</span></span>
        <span class="m">{{ $c['masa'] ?? '—' }}@if ($tip === 'tasima' && !empty($c['hedef'])) → {{ $c['hedef'] }}@endif</span>
        <span class="t">{{ $tipAdi($tip) }}</span>
        <span class="z"><b>{{ $c['saat'] ?? '' }}</b>{{ $oncesi($c['saniye'] ?? 0) }}</span>
      </div>
    @endforeach
  </details>
@endif

</div>{{-- /gcroot --}}

<script>
(function () {
  var KOK = 'gcroot', SES_ANAHTAR = 'gc_ses', filtre = '', gecmisAcik = null, bilinen = null;
  var sesAcik = false, ctx = null, sonCekim = Date.now(), yukluyor = false;
  try { sesAcik = localStorage.getItem(SES_ANAHTAR) === '1'; } catch (e) {}

  function kok() { return document.getElementById(KOK); }
  function sureYazi(sn) {
    if (sn < 60) return sn + ' sn';
    if (sn < 3600) return Math.floor(sn / 60) + ' dk';
    return Math.floor(sn / 3600) + ' sa ' + Math.floor((sn % 3600) / 60) + ' dk';
  }
  function idler() {
    var s = {}; kok().querySelectorAll('[data-gc-id]').forEach(function (k) { s[k.getAttribute('data-gc-id')] = 1; }); return s;
  }

  function bip() {
    if (!sesAcik) return;
    try {
      ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
      [0, 0.18].forEach(function (t) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.type = 'sine'; o.frequency.value = t ? 1175 : 880;
        g.gain.setValueAtTime(0.0001, ctx.currentTime + t);
        g.gain.exponentialRampToValueAtTime(0.35, ctx.currentTime + t + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + t + 0.16);
        o.connect(g); g.connect(ctx.destination); o.start(ctx.currentTime + t); o.stop(ctx.currentTime + t + 0.17);
      });
    } catch (e) {}
  }

  function sesCiz() {
    var b = document.getElementById('gcSesBtn'); if (!b) return;
    b.classList.toggle('ses-acik', sesAcik);
    b.querySelector('.material-icons-outlined').textContent = sesAcik ? 'volume_up' : 'volume_off';
    b.querySelector('.yazi').textContent = sesAcik ? 'Ses açık' : 'Ses kapalı';
  }

  function filtreUygula() {
    var r = kok(), gorunen = 0;
    r.querySelectorAll('[data-gc-tip]').forEach(function (c) { c.classList.toggle('aktif', c.getAttribute('data-gc-tip') === filtre); });
    if (filtre && !r.querySelector('[data-gc-tip="' + filtre + '"]')) { filtre = ''; return filtreUygula(); }
    r.querySelectorAll('[data-gc-kart-tip]').forEach(function (k) {
      var g = !filtre || k.getAttribute('data-gc-kart-tip') === filtre;
      k.style.display = g ? '' : 'none'; if (g) gorunen++;
    });
    var fb = document.getElementById('gcFiltreBos'); if (fb) fb.style.display = gorunen ? 'none' : 'block';
  }

  function bagla() {
    var r = kok(); if (!r) return;
    sesCiz(); filtreUygula();
    var g = document.getElementById('gcGecmis');
    if (g && gecmisAcik !== null) g.open = gecmisAcik;
    var simdi = idler();
    if (bilinen) {
      var yeniVar = false;
      Object.keys(simdi).forEach(function (id) {
        if (!bilinen[id]) { yeniVar = true; var k = r.querySelector('[data-gc-id="' + id + '"]'); if (k) k.classList.add('yeni'); }
      });
      if (yeniVar) bip();
    }
    bilinen = simdi;
    // Sekme başlığına bekleyen sayısı
    var n = Object.keys(simdi).length;
    document.title = (n ? '(' + n + ') ' : '') + 'Garson Çağrıları — ResteOS';
  }

  function yenile(html) {
    var d = new DOMParser().parseFromString(html, 'text/html'), yeni = d.getElementById(KOK);
    if (!yeni) return false;
    var g = document.getElementById('gcGecmis'); if (g) gecmisAcik = g.open;
    kok().innerHTML = yeni.innerHTML;
    sonCekim = Date.now(); bagla();
    var c = document.getElementById('gcCanli'); if (c) c.classList.remove('kopuk');
    return true;
  }

  function cek() {
    if (yukluyor || document.hidden) return;
    yukluyor = true;
    fetch(location.pathname, { cache: 'no-store', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (res) { return res.text(); })
      .then(function (h) { if (!yenile(h)) throw 0; })
      .catch(function () {
        var c = document.getElementById('gcCanli'), y = document.getElementById('gcCanliYazi');
        if (c) c.classList.add('kopuk'); if (y) y.textContent = 'Bağlantı yok · tekrar deneniyor';
      })
      .then(function () { yukluyor = false; });
  }

  // Süre sayaçlarını her saniye canlı ilerlet (sunucuya gitmeden)
  setInterval(function () {
    var r = kok(); if (!r) return;
    var gecen = Math.floor((Date.now() - sonCekim) / 1000);
    r.querySelectorAll('[data-gc-sn]').forEach(function (el) {
      var sn = parseInt(el.getAttribute('data-gc-sn'), 10) + gecen; el.textContent = sureYazi(sn);
      var k = el.closest('.gc-kart'); if (k && sn >= 60) k.classList.add('geciken');
    });
    r.querySelectorAll('[data-gc-bar]').forEach(function (el) {
      el.style.width = Math.min(100, (parseInt(el.getAttribute('data-gc-bar'), 10) + gecen) / 3) + '%';
    });
  }, 1000);
  setInterval(cek, 5000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) cek(); });

  // Tıklamalar (içerik yenilense de çalışsın diye delege)
  document.addEventListener('click', function (e) {
    var cip = e.target.closest('#' + KOK + ' [data-gc-tip]');
    if (cip) { filtre = cip.getAttribute('data-gc-tip'); filtreUygula(); return; }
    if (e.target.closest('#gcSesBtn')) {
      sesAcik = !sesAcik;
      try { localStorage.setItem(SES_ANAHTAR, sesAcik ? '1' : '0'); } catch (er) {}
      sesCiz(); if (sesAcik) bip();
    }
  });

  // Karşıla: sayfa yenilemeden gönder, dönen sayfayı yerine koy
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('#' + KOK + ' [data-gc-form]'); if (!f) return;
    e.preventDefault();
    var gonder = function () {
      var k = f.closest('.gc-kart'); if (k) k.classList.add('gidiyor');
      fetch(f.action, { method: 'POST', body: new FormData(f), credentials: 'same-origin' })
        .then(function (res) { return res.text(); })
        .then(function (h) { if (!yenile(h)) f.submit(); })
        .catch(function () { f.submit(); });
    };
    if (f.hasAttribute('data-gc-hepsi') && window.rkOnay) {
      window.rkOnay('Bekleyen tüm çağrılar karşılandı olarak işaretlensin mi?', gonder, null, 'Tümünü karşıla');
    } else gonder();
  });

  bagla();
})();
</script>
@endsection

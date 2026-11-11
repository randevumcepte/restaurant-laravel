@extends('layout.app')
@section('title', 'Garson Çağrıları')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Garson Çağrıları')
@section('rk_alt', 'QR menüden gelen çağrılar buraya anında düşer')

@push('head')
<style>
/* ====== Garson Çağrıları — renkli canlı çağrı panosu ====== */
#gcroot{--gc-r:20px}

/* ---- HERO: durum bandı (sakin=mor/mavi, bekleyen var=kırmızı/turuncu) ---- */
#gcroot .gc-hero{position:relative;overflow:hidden;border-radius:24px;padding:26px 28px;margin-bottom:18px;color:#fff;
  background:linear-gradient(120deg,#4F46E5 0%,#7C3AED 48%,#DB2777 100%);
  box-shadow:0 14px 34px -14px rgba(124,58,237,.55)}
#gcroot .gc-hero.alarm{background:linear-gradient(120deg,#E11D48 0%,#F43F5E 45%,#F97316 100%);
  box-shadow:0 14px 34px -14px rgba(225,29,72,.6)}
#gcroot .gc-hero::before,#gcroot .gc-hero::after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.12);pointer-events:none}
#gcroot .gc-hero::before{width:280px;height:280px;right:-60px;top:-120px}
#gcroot .gc-hero::after{width:180px;height:180px;right:180px;bottom:-110px;background:rgba(255,255,255,.08)}
#gcroot .gc-hero-ic{position:relative;z-index:1;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
#gcroot .gc-hero-ik{width:68px;height:68px;flex:0 0 68px;border-radius:20px;display:flex;align-items:center;justify-content:center;
  background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);backdrop-filter:blur(6px)}
#gcroot .gc-hero-ik .material-icons-outlined{font-size:36px;color:#fff}
#gcroot .gc-hero.alarm .gc-hero-ik{animation:gcZil 1.2s ease-in-out infinite}
@keyframes gcZil{0%,100%{transform:rotate(0)}10%{transform:rotate(-12deg)}20%{transform:rotate(10deg)}30%{transform:rotate(-6deg)}40%{transform:rotate(0)}}
#gcroot .gc-hero-yazi{flex:1;min-width:220px}
#gcroot .gc-hero-yazi h2{margin:0;font-size:26px;font-weight:900;letter-spacing:-.3px;line-height:1.2}
#gcroot .gc-hero-yazi p{margin:6px 0 0;font-size:14px;opacity:.9}
#gcroot .gc-hero-sag{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
#gcroot .gc-canli{display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:999px;
  background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.28);font-size:12.5px;font-weight:700;color:#fff}
#gcroot .gc-canli i{width:8px;height:8px;border-radius:50%;background:#4ADE80;position:relative;box-shadow:0 0 0 2px rgba(74,222,128,.35)}
#gcroot .gc-canli i::after{content:"";position:absolute;inset:-4px;border-radius:50%;border:2px solid #4ADE80;opacity:0;animation:gcNabiz 2s infinite}
#gcroot .gc-canli.kopuk i{background:#FCA5A5;box-shadow:none}
#gcroot .gc-canli.kopuk i::after{display:none}
@keyframes gcNabiz{0%{transform:scale(.6);opacity:.8}100%{transform:scale(1.7);opacity:0}}
#gcroot .gc-dugme{display:inline-flex;align-items:center;gap:7px;padding:10px 16px;border-radius:14px;cursor:pointer;font-family:inherit;
  border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.16);color:#fff;font-size:13.5px;font-weight:800;
  transition:background .15s,transform .15s}
#gcroot .gc-dugme:hover{background:rgba(255,255,255,.26);transform:translateY(-1px)}
#gcroot .gc-dugme .material-icons-outlined{font-size:19px}
#gcroot .gc-dugme.ses-acik{background:#fff;color:#7C3AED;border-color:#fff}
#gcroot .gc-hero.alarm .gc-dugme.ses-acik{color:#E11D48}
#gcroot .gc-dugme.ana{background:#fff;color:#16A34A;border-color:#fff;box-shadow:0 6px 16px -6px rgba(0,0,0,.35)}

/* ---- Renkli özet kutuları ---- */
#gcroot .gc-ozet{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:26px}
#gcroot .gc-oc{position:relative;overflow:hidden;padding:18px 18px 16px;border-radius:var(--gc-r);color:#fff;min-width:0;
  background:var(--gc-g);box-shadow:0 10px 24px -14px var(--gc-gl)}
#gcroot .gc-oc .ik{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.22)}
#gcroot .gc-oc .ik .material-icons-outlined{font-size:22px;color:#fff}
#gcroot .gc-oc .arka{position:absolute;right:-14px;bottom:-22px;font-size:110px;opacity:.14;color:#fff;pointer-events:none}
#gcroot .gc-oc .v{font-size:32px;font-weight:900;line-height:1;margin-top:14px;font-variant-numeric:tabular-nums;position:relative}
#gcroot .gc-oc .s{font-size:13px;font-weight:700;opacity:.92;margin-top:6px;position:relative}
#gcroot .gc-oc.k1{--gc-g:linear-gradient(135deg,#FB7185,#E11D48);--gc-gl:rgba(225,29,72,.7)}
#gcroot .gc-oc.k2{--gc-g:linear-gradient(135deg,#FBBF24,#F97316);--gc-gl:rgba(249,115,22,.7)}
#gcroot .gc-oc.k3{--gc-g:linear-gradient(135deg,#A78BFA,#6366F1);--gc-gl:rgba(99,102,241,.7)}
#gcroot .gc-oc.k4{--gc-g:linear-gradient(135deg,#34D399,#0D9488);--gc-gl:rgba(13,148,136,.7)}

/* ---- Bölüm başlığı + filtre çipleri ---- */
#gcroot .gc-bas{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 14px}
#gcroot .gc-bas h3{margin:0;font-size:18px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:10px}
#gcroot .gc-bas h3 .cb{width:6px;height:22px;border-radius:4px;background:linear-gradient(180deg,#F43F5E,#F97316)}
#gcroot .gc-bas h3 .cb.y{background:linear-gradient(180deg,#34D399,#0D9488)}
#gcroot .gc-bas h3 .n{font-size:12.5px;font-weight:800;padding:3px 10px;border-radius:999px;color:#fff;background:#E11D48}
#gcroot .gc-bas h3 .n.y{background:#0D9488}
#gcroot .gc-cipler{display:flex;gap:7px;flex-wrap:wrap;margin-left:auto}
#gcroot .gc-cip{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:999px;cursor:pointer;font-family:inherit;
  border:1.5px solid color-mix(in srgb,var(--cr,#64748B) 30%,transparent);background:color-mix(in srgb,var(--cr,#64748B) 9%,var(--card));
  color:var(--cr,var(--sub2));font-size:12.5px;font-weight:800;transition:all .15s}
#gcroot .gc-cip .material-icons-outlined{font-size:16px}
#gcroot .gc-cip b{font-weight:900;opacity:.8}
#gcroot .gc-cip.aktif{background:var(--cr,#334155);border-color:var(--cr,#334155);color:#fff;box-shadow:0 6px 14px -8px var(--cr,#334155)}

/* ---- Bekleyen çağrı kartları ---- */
#gcroot .gc-izgara{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:16px;margin-bottom:30px}
#gcroot .gc-kart{position:relative;display:flex;flex-direction:column;background:var(--card);border-radius:var(--gc-r);overflow:hidden;
  border:1px solid color-mix(in srgb,var(--vurgu) 25%,var(--line));box-shadow:0 12px 28px -18px var(--vurgu);
  transition:transform .15s,opacity .2s}
#gcroot .gc-kart:hover{transform:translateY(-3px)}
#gcroot .gc-kart.geciken{animation:gcAlarm 1.6s ease-in-out infinite}
@keyframes gcAlarm{0%,100%{box-shadow:0 12px 28px -18px #E11D48}50%{box-shadow:0 0 0 5px rgba(225,29,72,.22)}}
#gcroot .gc-kart.yeni{animation:gcGir .45s ease-out}
@keyframes gcGir{from{transform:scale(.94);opacity:0}to{transform:none;opacity:1}}
#gcroot .gc-kart.gidiyor{opacity:.35;pointer-events:none}
#gcroot .gc-ust{position:relative;display:flex;align-items:center;gap:12px;padding:16px 16px 18px;color:#fff;
  background:linear-gradient(130deg,var(--vurgu),color-mix(in srgb,var(--vurgu) 65%,#1E1B4B))}
#gcroot .gc-ust::after{content:"";position:absolute;width:120px;height:120px;border-radius:50%;right:-40px;top:-50px;background:rgba(255,255,255,.13)}
#gcroot .gc-ikon{width:46px;height:46px;flex:0 0 46px;border-radius:14px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.2)}
#gcroot .gc-ikon .material-icons-outlined{font-size:25px;color:#fff}
#gcroot .gc-meta{min-width:0;flex:1;position:relative;z-index:1}
#gcroot .gc-masa{font-size:22px;font-weight:900;line-height:1.15;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-masa .ok{opacity:.75;margin:0 4px}
#gcroot .gc-tip{font-size:12.5px;font-weight:700;opacity:.92;margin-top:3px}
#gcroot .gc-sure-col{position:relative;z-index:1;text-align:center;flex:0 0 auto;padding:6px 10px;border-radius:12px;background:rgba(255,255,255,.2)}
#gcroot .gc-sure{font-size:19px;font-weight:900;font-variant-numeric:tabular-nums;line-height:1.1}
#gcroot .gc-saat{font-size:10.5px;opacity:.9;margin-top:2px}
#gcroot .gc-kart.geciken .gc-sure-col{background:#fff;color:#E11D48}
#gcroot .gc-bar{height:6px;background:color-mix(in srgb,var(--vurgu) 14%,var(--card))}
#gcroot .gc-bar i{display:block;height:100%;width:0;background:linear-gradient(90deg,#FBBF24,#F97316);transition:width 1s linear}
#gcroot .gc-kart.geciken .gc-bar i{background:linear-gradient(90deg,#F97316,#E11D48)}
#gcroot .gc-alt{padding:14px 16px 16px}
#gcroot .gc-btn{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;padding:13px 16px;border:none;border-radius:14px;
  font-size:15px;font-weight:900;cursor:pointer;color:#fff;font-family:inherit;transition:transform .12s,filter .15s}
#gcroot .gc-btn:hover{filter:brightness(1.08);transform:translateY(-1px)}
#gcroot .gc-btn.yesil{background:linear-gradient(135deg,#22C55E,#15803D);box-shadow:0 8px 18px -10px #16A34A}
#gcroot .gc-btn.mor{background:linear-gradient(135deg,#8B5CF6,#6D28D9);box-shadow:0 8px 18px -10px #7C3AED}
#gcroot .gc-btn .material-icons-outlined{font-size:21px}

/* ---- Boş durum ---- */
#gcroot .gc-bos{position:relative;overflow:hidden;text-align:center;padding:44px 20px;margin-bottom:30px;border-radius:var(--gc-r);
  background:linear-gradient(135deg,color-mix(in srgb,#10B981 14%,var(--card)),color-mix(in srgb,#0EA5E9 12%,var(--card)));
  border:1px solid color-mix(in srgb,#10B981 30%,transparent)}
#gcroot .gc-bos .hal{width:84px;height:84px;margin:0 auto;border-radius:50%;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#34D399,#0D9488);box-shadow:0 0 0 10px color-mix(in srgb,#10B981 16%,transparent),0 14px 30px -12px #0D9488}
#gcroot .gc-bos .hal .material-icons-outlined{font-size:44px;color:#fff}
#gcroot .gc-bos .b{font-size:19px;font-weight:900;color:var(--ink);margin-top:22px}
#gcroot .gc-bos .s{font-size:13.5px;color:var(--sub2);margin-top:6px}
#gcroot .gc-filtre-bos{display:none;grid-column:1/-1;text-align:center;padding:26px;color:var(--sub);font-size:13px;
  border:1.5px dashed var(--line);border-radius:var(--gc-r)}

/* ---- Son karşılananlar: renkli mini kartlar ---- */
#gcroot .gc-gecmis summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:10px;margin-bottom:14px}
#gcroot .gc-gecmis summary::-webkit-details-marker{display:none}
#gcroot .gc-gecmis summary .ac{margin-left:auto;width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;
  background:var(--card);border:1px solid var(--line);color:var(--sub2);transition:transform .2s}
#gcroot .gc-gecmis[open] summary .ac{transform:rotate(180deg)}
#gcroot .gc-gizgara{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:12px}
#gcroot .gc-satir{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:16px;min-width:0;
  background:linear-gradient(135deg,color-mix(in srgb,var(--vurgu) 12%,var(--card)),var(--card));
  border:1px solid color-mix(in srgb,var(--vurgu) 28%,transparent)}
#gcroot .gc-satir .ki{width:40px;height:40px;flex:0 0 40px;border-radius:12px;display:flex;align-items:center;justify-content:center;
  background:var(--vurgu);color:#fff;box-shadow:0 6px 14px -8px var(--vurgu)}
#gcroot .gc-satir .ki .material-icons-outlined{font-size:20px}
#gcroot .gc-satir .d{min-width:0;flex:1}
#gcroot .gc-satir .m{font-weight:900;font-size:15px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#gcroot .gc-satir .t{font-size:12px;font-weight:700;color:var(--vurgu);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px}
#gcroot .gc-satir .z{text-align:right;flex:0 0 auto}
#gcroot .gc-satir .z b{display:block;font-size:14px;font-weight:900;color:var(--ink);font-variant-numeric:tabular-nums}
#gcroot .gc-satir .z small{font-size:11px;color:var(--sub)}

@media (max-width:1000px){#gcroot .gc-ozet{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:600px){
  #gcroot .gc-hero{padding:20px}
  #gcroot .gc-hero-yazi h2{font-size:21px}
  #gcroot .gc-cipler{margin-left:0;width:100%;overflow-x:auto;flex-wrap:nowrap;padding-bottom:2px}
  #gcroot .gc-dugme .yazi{display:none}
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
  /* Tip renkleri (koyu/açık temada aynı, beyaz yazıyla okunur tonlar) */
  $tipRenk = function ($t) {
    return $t === 'hesap' ? '#F59E0B'
      : ($t === 'tasima'           ? '#8B5CF6'
      : ($t === 'servise_hazir'    ? '#10B981'
      : ($t === 'odeme'            ? '#0EA5E9'
      : ($t === 'siparis'          ? '#6366F1'
      : '#F43F5E'))));
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

{{-- ===== HERO: anlık durum ===== --}}
<div class="gc-hero{{ $bekleyenSay ? ' alarm' : '' }}">
  <div class="gc-hero-ic">
    <div class="gc-hero-ik"><span class="material-icons-outlined">{{ $bekleyenSay ? 'notifications_active' : 'notifications' }}</span></div>
    <div class="gc-hero-yazi">
      @if ($bekleyenSay)
        <h2>{{ $bekleyenSay }} masa sizi bekliyor</h2>
        <p>{{ $gecikenSay ? $gecikenSay . ' çağrı 1 dakikayı geçti, öncelik onlarda.' : 'Çağrıları karşıladıkça listeden düşer.' }}</p>
      @else
        <h2>Salon sakin, bekleyen çağrı yok</h2>
        <p>Masalardan QR ile gelen çağrılar burada anında belirir. Ses açıksa uyarı çalar.</p>
      @endif
    </div>
    <div class="gc-hero-sag">
      <span class="gc-canli" id="gcCanli"><i></i><span id="gcCanliYazi">Canlı · 5 sn</span></span>
      <button type="button" class="gc-dugme" id="gcSesBtn" title="Yeni çağrıda sesli uyarı">
        <span class="material-icons-outlined">volume_off</span><span class="yazi">Ses kapalı</span>
      </button>
      @if ($bekleyenSay > 1)
        <form method="POST" action="/garson-cagrilari/karsilandi" style="margin:0" data-gc-form data-gc-hepsi>
          @csrf
          <input type="hidden" name="hepsi" value="1">
          <button type="submit" class="gc-dugme ana">
            <span class="material-icons-outlined">done_all</span><span class="yazi">Tümünü karşıla</span>
          </button>
        </form>
      @endif
    </div>
  </div>
</div>

{{-- ===== ÖZET ===== --}}
<div class="gc-ozet">
  <div class="gc-oc k1">
    <span class="material-icons-outlined arka">notifications_active</span>
    <div class="ik"><span class="material-icons-outlined">notifications_active</span></div>
    <div class="v">{{ $bekleyenSay }}</div><div class="s">Bekleyen çağrı</div>
  </div>
  <div class="gc-oc k2">
    <span class="material-icons-outlined arka">timer</span>
    <div class="ik"><span class="material-icons-outlined">timer</span></div>
    <div class="v">{{ $gecikenSay }}</div><div class="s">1 dk'yı geçen</div>
  </div>
  <div class="gc-oc k3">
    <span class="material-icons-outlined arka">hourglass_top</span>
    <div class="ik"><span class="material-icons-outlined">hourglass_top</span></div>
    <div class="v" @if($bekleyenSay) data-gc-sn="{{ $enUzun }}" @endif>{{ $bekleyenSay ? $sureYazi($enUzun) : '0 sn' }}</div><div class="s">En uzun bekleme</div>
  </div>
  <div class="gc-oc k4">
    <span class="material-icons-outlined arka">done_all</span>
    <div class="ik"><span class="material-icons-outlined">done_all</span></div>
    <div class="v">{{ $karsilananSay }}</div><div class="s">Son karşılanan</div>
  </div>
</div>

{{-- ===== BEKLEYEN ÇAĞRILAR (yoksa bölüm hiç çizilmez; durum zaten üst bantta) ===== --}}
@if ($bekleyenSay)
<div class="gc-bas">
  <h3><span class="cb"></span>Bekleyen Çağrılar
    @if ($bekleyenSay)<span class="n">{{ $bekleyenSay }}</span>@endif
  </h3>
  @if (count($tipSay) > 1)
    <div class="gc-cipler" id="gcCipler">
      <button type="button" class="gc-cip aktif" data-gc-tip="">Tümü <b>{{ $bekleyenSay }}</b></button>
      @foreach ($tipKisa as $k => $ad)
        @if (!empty($tipSay[$k]))
          <button type="button" class="gc-cip" data-gc-tip="{{ $k }}" style="--cr:{{ $tipRenk($k) }}">
            <span class="material-icons-outlined">{{ $tipIkon($k) }}</span>{{ $ad }} <b>{{ $tipSay[$k] }}</b>
          </button>
        @endif
      @endforeach
    </div>
  @endif
</div>

  <div class="gc-izgara" id="gcIzgara">
    @foreach ($bekleyen as $c)
      @php
        $tip      = $c['tip'] ?? 'garson';
        $sn       = (int) ($c['saniye'] ?? 0);
        $tasimaMi = ($tip === 'tasima');
      @endphp
      <div class="gc-kart{{ $sn >= 60 ? ' geciken' : '' }}" data-gc-id="{{ $c['id'] }}" data-gc-kart-tip="{{ $tipNorm($tip) }}" style="--vurgu:{{ $tipRenk($tip) }}">
        <div class="gc-ust">
          <div class="gc-ikon"><span class="material-icons-outlined">{{ $tipIkon($tip) }}</span></div>
          <div class="gc-meta">
            <div class="gc-masa">
              {{ $c['masa'] ?? '—' }}@if ($tasimaMi && !empty($c['hedef']))<span class="ok">→</span>{{ $c['hedef'] }}@endif
            </div>
            <div class="gc-tip">{{ $tipAdi($tip) }}</div>
          </div>
          <div class="gc-sure-col">
            <div class="gc-sure" data-gc-sn="{{ $sn }}">{{ $sureYazi($sn) }}</div>
            <div class="gc-saat">{{ $c['saat'] ?? '' }}</div>
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
              <button type="submit" class="gc-btn yesil"><span class="material-icons-outlined">check_circle</span> Karşılandı</button>
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
    <summary class="gc-bas">
      <h3><span class="cb y"></span>Son Karşılananlar <span class="n y">{{ $karsilananSay }}</span></h3>
      <span class="ac"><span class="material-icons-outlined">expand_more</span></span>
    </summary>
    <div class="gc-gizgara">
      @foreach ($karsilanan as $c)
        @php $tip = $c['tip'] ?? 'garson'; @endphp
        <div class="gc-satir" style="--vurgu:{{ $tipRenk($tip) }}">
          <span class="ki"><span class="material-icons-outlined">{{ $tipIkon($tip) }}</span></span>
          <div class="d">
            <div class="m">{{ $c['masa'] ?? '—' }}@if ($tip === 'tasima' && !empty($c['hedef'])) → {{ $c['hedef'] }}@endif</div>
            <div class="t">{{ $tipAdi($tip) }}</div>
          </div>
          <div class="z"><b>{{ $c['saat'] ?? '' }}</b><small>{{ $oncesi($c['saniye'] ?? 0) }}</small></div>
        </div>
      @endforeach
    </div>
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
        if (c) c.classList.add('kopuk'); if (y) y.textContent = 'Bağlantı yok';
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

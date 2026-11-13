@extends('layout.app')
@section('title', 'Raporlar & Analizler')
@section('rk_ikon', 'insights')
@section('baslik', 'Raporlar & Analizler')
@section('rk_alt', 'Satın alma · günlük / haftalık / aylık yönetim raporları · analiz kütüphanesi · AI')

@section('content')
@php
  $durum = $durum ?? []; $ai = $ai ?? null;
  $modAd = ['fatura' => 'Alış faturası', 'siparis' => 'Sipariş', 'malkabul' => 'Mal kabul', 'malkabul_sip' => 'Siparişe bağlı mal kabul', 'talep' => 'Satın alma talebi',
    'rfq' => 'Teklif (RFQ)', 'recete' => 'Reçete', 'parti' => 'Lot / SKT', 'fire' => 'Fire / iade', 'tuketim' => 'Reçeteli tüketim', 'sayim' => 'Sayım', 'cari' => 'Tedarikçi cari', 'butce' => 'Bütçe'];
  $modYol = ['fatura' => 'Mal Kabul / İrsaliye · Fatura OCR', 'siparis' => 'Sipariş İşlemleri', 'malkabul' => 'Mal Kabul / İrsaliye', 'malkabul_sip' => 'Mal Kabul\'de sipariş seçerek', 'talep' => 'Satın Alma Talepleri',
    'rfq' => 'Talepten teklif isteği', 'recete' => 'Menü > Reçeteler', 'parti' => 'Mal Kabul\'de SKT girişi', 'fire' => 'Stok > fire / iade', 'tuketim' => 'Reçeteli satış', 'sayim' => 'Sayım (Envanter)', 'cari' => 'Tedarikçi carisi', 'butce' => 'Satınalma Bütçe'];
  $donemler = ['bugun' => 'Bugün', 'dun' => 'Dün', 'bu_hafta' => 'Bu hafta', 'gecen_hafta' => 'Geçen hafta', 'bu_ay' => 'Bu ay', 'gecen_ay' => 'Geçen ay', 'son30' => 'Son 30 gün', 'son90' => 'Son 90 gün', 'bu_yil' => 'Bu yıl'];
  $donemQs = http_build_query(array_filter(['donem' => $dn['tip'], 'bas' => $dn['tip'] === 'ozel' ? $dn['bas'] : null, 'bit' => $dn['tip'] === 'ozel' ? $dn['bit'] : null]));
  $indir = fn($rapor, $fmt, $qs = null) => '/satinalma-raporlar/indir?rapor=' . $rapor . '&format=' . $fmt . '&' . ($qs ?? $donemQs);
  // Fiyat Radarı raporları gün tabanlı → seçili döneme en yakın gün
  $radarGun = collect([30, 60, 90, 120, 180, 365])->sortBy(fn($g) => abs($g - $dn['gun']))->first();
  $veriRozet = function ($gerekli) use ($durum, $modAd) {
      $eksik = array_values(array_filter($gerekli, fn($m) => empty($durum[$m])));
      if (!$eksik) return ['Veri hazır', '#059669', 'check_circle', ''];
      if (in_array('fatura', $eksik, true) || count($eksik) === count($gerekli)) return ['Veri yok', '#DC2626', 'error', 'Kullanılmayan: ' . implode(', ', array_map(fn($m) => $modAd[$m] ?? $m, $eksik))];
      return ['Kısmi veri', '#D97706', 'warning', 'Kullanılmayan: ' . implode(', ', array_map(fn($m) => $modAd[$m] ?? $m, $eksik))];
  };
  $periyot = ['gunluk' => ['dun' => 'Dün', 'bugun' => 'Bugün'], 'haftalik' => ['gecen_hafta' => 'Geçen hafta', 'bu_hafta' => 'Bu hafta'], 'aylik' => ['gecen_ay' => 'Geçen ay', 'bu_ay' => 'Bu ay']];
  $katSay = []; foreach ($katalog as $k => $r) $katSay[$r[0]] = ($katSay[$r[0]] ?? 0) + 1; $katSay['radar'] = count($radar); $katSay['ai'] = 1;
@endphp

<style>
  .sr{display:grid;grid-template-columns:minmax(0,1fr)}
  .sr-hero{position:relative;overflow:hidden;border-radius:20px;padding:22px 24px;color:#fff;margin-bottom:14px;background:radial-gradient(120% 140% at 0% 0%,#6366F1 0%,#4F46E5 35%,#4338CA 65%,#1E1B4B 100%);box-shadow:0 18px 40px -18px rgba(49,46,129,.55)}
  .sr-hero::after{content:'';position:absolute;right:-70px;top:-70px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(14,165,233,.5),transparent 65%);pointer-events:none}
  .sr-hero h2{margin:0;font-size:21px;font-weight:900;color:#fff;position:relative}
  .sr-hero p{margin:4px 0 0;font-size:12.5px;color:rgba(255,255,255,.85);max-width:780px;position:relative}
  .sr-donem{position:relative;z-index:1;display:flex;flex-wrap:wrap;gap:6px;margin-top:16px;align-items:center}
  .sr-chip{display:inline-flex;align-items:center;padding:7px 12px;border-radius:99px;font-size:12.5px;font-weight:700;color:#fff;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.25);text-decoration:none;white-space:nowrap}
  .sr-chip:hover{background:rgba(255,255,255,.22)}
  .sr-chip.aktif{background:#fff;color:#4338CA}
  .sr-ozel{display:inline-flex;gap:6px;align-items:center;padding:4px 6px 4px 12px;border-radius:99px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.25);font-size:12px;font-weight:700}
  .sr-ozel input{background:rgba(255,255,255,.9);border:0;border-radius:8px;padding:4px 6px;font-size:12px;color:#1E293B}
  .sr-ozel button{border:0;border-radius:99px;padding:6px 11px;font-weight:800;background:#fff;color:#4338CA;cursor:pointer;font-size:12px}
  .sr-etiket{position:relative;margin-top:10px;font-size:12px;color:rgba(255,255,255,.85)}
  .sr-etiket b{color:#fff}
  .sr-mod{display:flex;flex-wrap:wrap;gap:6px;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px 12px;margin-bottom:14px}
  .sr-mod .bas{font-size:11.5px;font-weight:800;color:var(--sub);margin-right:4px}
  .sr-m{display:inline-flex;align-items:center;gap:4px;padding:4px 9px;border-radius:99px;font-size:11.5px;font-weight:700;border:1px solid}
  .sr-m .material-icons-outlined{font-size:14px}
  .sr-m.var{color:#047857;border-color:color-mix(in srgb,#059669 35%,transparent);background:color-mix(in srgb,#059669 8%,transparent)}
  .sr-m.yok{color:var(--sub);border-color:var(--line);background:var(--card2)}
  body.koyu .sr-m.var{color:#34D399}
  .sr-ana{display:grid;grid-template-columns:220px minmax(0,1fr);gap:16px;align-items:start}
  @media(max-width:1000px){.sr-ana{grid-template-columns:minmax(0,1fr)}.sr-nav{position:static!important;display:flex!important;overflow-x:auto;gap:6px}.sr-nav a{flex:0 0 auto}}
  .sr-nav{position:sticky;top:10px;display:flex;flex-direction:column;gap:3px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:8px}
  .sr-nav a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;color:var(--sub2);font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap}
  .sr-nav a .material-icons-outlined{font-size:18px}
  .sr-nav a .s{margin-left:auto;font-size:10.5px;padding:1px 7px;border-radius:99px;background:var(--card2);color:var(--sub)}
  .sr-nav a:hover{background:var(--card2)}
  .sr-nav a.aktif{background:linear-gradient(135deg,#6366F1,#4338CA);color:#fff}
  .sr-nav a.aktif .s{background:rgba(255,255,255,.22);color:#fff}
  .sr-ara{width:100%;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 14px;font-size:13px;color:var(--ink);margin-bottom:14px}
  .sr-bol{margin-bottom:22px;scroll-margin-top:12px}
  .sr-bol h3{margin:0 0 10px;font-size:15.5px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px}
  .sr-bol h3 .material-icons-outlined{color:#6366F1}
  .sr-kartlar{display:grid;grid-template-columns:repeat(auto-fill,minmax(285px,1fr));gap:12px}
  .sr-k{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:15px;display:flex;flex-direction:column;gap:9px;box-shadow:var(--golge);transition:transform .15s,box-shadow .15s}
  .sr-k:hover{transform:translateY(-2px);box-shadow:0 14px 30px -16px rgba(49,46,129,.35)}
  .sr-k .ust{display:flex;gap:10px;align-items:flex-start}
  .sr-k .ik{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;color:#fff;background:var(--c);flex:0 0 auto}
  .sr-k .t{font-weight:900;font-size:14px;color:var(--ink);line-height:1.25}
  .sr-k .d{font-size:12px;color:var(--sub);line-height:1.45;flex:1}
  .sr-roz{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;color:var(--c);background:color-mix(in srgb,var(--c) 10%,transparent);border:1px solid color-mix(in srgb,var(--c) 30%,transparent);border-radius:99px;padding:2px 8px;width:max-content}
  .sr-roz .material-icons-outlined{font-size:13px}
  .sr-eksik{font-size:10.5px;color:var(--sub);margin-top:-4px}
  .sr-per{display:flex;gap:4px}
  .sr-per button{flex:1;border:1px solid var(--line);background:var(--card2);color:var(--sub2);border-radius:8px;padding:6px;font-size:11.5px;font-weight:800;cursor:pointer}
  .sr-per button.aktif{background:var(--c);color:#fff;border-color:var(--c)}
  .sr-bt{display:flex;gap:6px}
  .sr-b{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 6px;border-radius:9px;font-size:12px;font-weight:800;text-decoration:none;border:1px solid;cursor:pointer;background:none}
  .sr-b .material-icons-outlined{font-size:16px}
  .sr-b.o{color:#4338CA;border-color:color-mix(in srgb,#4F46E5 40%,transparent);background:color-mix(in srgb,#4F46E5 7%,transparent)}
  .sr-b.x{color:#15803D;border-color:color-mix(in srgb,#16A34A 40%,transparent);background:color-mix(in srgb,#16A34A 8%,transparent)}
  .sr-b.p{color:#BE123C;border-color:color-mix(in srgb,#E11D48 40%,transparent);background:color-mix(in srgb,#E11D48 7%,transparent)}
  body.koyu .sr-b.o{color:#A5B4FC} body.koyu .sr-b.x{color:#4ADE80} body.koyu .sr-b.p{color:#FB7185}
  .sr-not{font-size:11.5px;color:var(--sub);margin:-4px 0 10px}
  .sr-ai{border-radius:18px;padding:18px;background:linear-gradient(135deg,color-mix(in srgb,#9333EA 14%,var(--card)),color-mix(in srgb,#EC4899 10%,var(--card)));border:1px solid color-mix(in srgb,#9333EA 30%,transparent)}
  .sr-ai-btn{display:inline-flex;align-items:center;gap:7px;padding:11px 18px;border-radius:12px;border:0;cursor:pointer;font-weight:900;font-size:13.5px;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899)}
  .sr-ai-btn[disabled]{opacity:.7;cursor:wait}
  .sr-ai-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-top:12px}
  .sr-ai-k{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px;border-top:3px solid var(--c)}
  .sr-ai-k h5{margin:0 0 8px;font-size:13px;font-weight:900;color:var(--ink)}
  .sr-ai-m{padding:8px 0;border-top:1px solid var(--line);font-size:12.5px;color:var(--sub2)}
  .sr-ai-k h5 + .sr-ai-m{border-top:0}
  .sr-ai-m b{display:block;color:var(--ink);margin-bottom:2px}
  .sr-sor{display:flex;gap:8px;margin-top:12px}
  .sr-sor input{flex:1;min-width:0;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:10px 13px;font-size:13px;color:var(--ink)}
  .sr-cevap{white-space:pre-wrap;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:13px;font-size:13px;color:var(--ink);margin-top:10px;line-height:1.55}
  .sr-modal{position:fixed;inset:0;background:rgba(15,23,42,.6);z-index:1000;display:none;align-items:center;justify-content:center;padding:20px}
  .sr-modal.acik{display:flex}
  .sr-modal .kutu{background:var(--card);border-radius:16px;width:min(1240px,100%);height:min(92vh,100%);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 30px 60px -20px rgba(0,0,0,.5)}
  .sr-modal .bas{display:flex;align-items:center;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line)}
  .sr-modal .bas b{flex:1;color:var(--ink);font-size:14px}
  .sr-modal iframe{flex:1;border:0;width:100%;background:#F1F2F9}
  .sr-kapat{border:0;background:var(--card2);border-radius:9px;padding:7px;cursor:pointer;color:var(--sub2);display:grid;place-items:center}
  @keyframes srDon{to{transform:rotate(360deg)}}
</style>

<div class="sr">
  {{-- ===== HERO + DÖNEM ===== --}}
  <div class="sr-hero">
    <h2>📊 Satın Alma Raporlar & Analizler</h2>
    <p>Üst yönetime günlük, haftalık ve aylık raporlar; harcama, fiyat, maliyet, tedarikçi, süreç, stok ve finans analizleri. Raporlar <b>yorumsuz sistem verisidir</b>; boş kalan bölüm nedenini yazar. AI yorumu yalnız <b>AI Analiz</b> bölümündedir ve öyle etiketlenir.</p>
    <div class="sr-donem">
      @foreach ($donemler as $k => $ad)<a class="sr-chip {{ $dn['tip'] === $k ? 'aktif' : '' }}" href="?donem={{ $k }}" onclick="this.href += location.hash">{{ $ad }}</a>@endforeach
      <form class="sr-ozel" method="GET" action="/satinalma-raporlar" onsubmit="this.action += location.hash">
        <input type="hidden" name="donem" value="ozel"><span>Özel</span>
        <input type="date" name="bas" value="{{ $dn['bas'] }}" max="{{ date('Y-m-d') }}"><span>–</span><input type="date" name="bit" value="{{ $dn['bit'] }}" max="{{ date('Y-m-d') }}">
        <button type="submit">Uygula</button>
      </form>
    </div>
    <div class="sr-etiket">Seçili dönem: <b>{{ $dn['ad'] }}</b> · {{ $dn['etiket'] }} ({{ $dn['gun'] }} gün) · karşılaştırma: önceki {{ $dn['gun'] }} gün ({{ \Carbon\Carbon::parse($dn['obas'])->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($dn['obit'])->format('d.m.Y') }})</div>
  </div>

  {{-- ===== VERİ DURUMU ===== --}}
  <div class="sr-mod">
    <span class="bas">Kullanılan modüller (son 1 yıl):</span>
    @foreach (['fatura', 'siparis', 'malkabul_sip', 'talep', 'rfq', 'recete', 'parti', 'fire', 'sayim', 'cari', 'butce'] as $m)
      <span class="sr-m {{ !empty($durum[$m]) ? 'var' : 'yok' }}" title="{{ !empty($durum[$m]) ? ($durum[$m] . ' kayıt') : 'Kullanılmıyor — giriş: ' . ($modYol[$m] ?? '') }}"><span class="material-icons-outlined">{{ !empty($durum[$m]) ? 'check_circle' : 'radio_button_unchecked' }}</span>{{ $modAd[$m] }}</span>
    @endforeach
  </div>

  <div class="sr-ana">
    <nav class="sr-nav" id="srNav">
      @foreach ($kategoriler as $k => [$ad, $ik])
        <a href="#k-{{ $k }}" data-k="{{ $k }}"><span class="material-icons-outlined">{{ $ik }}</span>{{ $ad }}<span class="s">{{ $katSay[$k] ?? 0 }}</span></a>
      @endforeach
    </nav>
    <div style="min-width:0">
      <input class="sr-ara" placeholder="Rapor ara… (ör. tedarikçi, bütçe, fire, teslim)" oninput="srAra(this.value)">

      @foreach ($kategoriler as $kk => [$kad, $kik])
        @continue(in_array($kk, ['radar', 'ai'], true))
        <section class="sr-bol" id="k-{{ $kk }}">
          <h3><span class="material-icons-outlined">{{ $kik }}</span>{{ $kad }}</h3>
          @if ($kk === 'donemsel')<div class="sr-not">Yönetime sunulan hazır raporlar: her kart kendi dönemiyle çalışır (Günlük: dün/bugün · Haftalık: geçen/bu hafta · Aylık: geçen/bu ay).</div>
          @else<div class="sr-not">Üstte seçili dönemle hazırlanır: <b>{{ $dn['etiket'] }}</b>.</div>@endif
          <div class="sr-kartlar">
            @foreach ($katalog as $rk => [$kat, $ad, $acik, $ik, $renk, $gerekli, $vars])
              @continue($kat !== $kk)
              @php [$rozet, $rRenk, $rIk, $rEksik] = $veriRozet($gerekli); @endphp
              <div class="sr-k" style="--c:{{ $renk }}" data-ara="{{ mb_strtolower($ad . ' ' . $acik) }}" data-rapor="{{ $rk }}">
                <div class="ust"><div class="ik"><span class="material-icons-outlined">{{ $ik }}</span></div><div class="t">{{ $ad }}</div></div>
                <div class="d">{{ $acik }}</div>
                <span class="sr-roz" style="--c:{{ $rRenk }}"><span class="material-icons-outlined">{{ $rIk }}</span>{{ $rozet }}</span>
                @if ($rEksik)<div class="sr-eksik">{{ $rEksik }} — ilgili bölümler boş gelir, rapor nedenini yazar.</div>@endif
                @if (isset($periyot[$rk]))
                  <div class="sr-per" data-per>
                    @foreach ($periyot[$rk] as $pk => $pad)<button type="button" class="{{ $loop->first ? 'aktif' : '' }}" data-donem="{{ $pk }}" onclick="srPer(this)">{{ $pad }}</button>@endforeach
                  </div>
                @endif
                <div class="sr-bt">
                  <button type="button" class="sr-b o" onclick="srAc('{{ $rk }}', this)"><span class="material-icons-outlined">visibility</span>Önizle</button>
                  <a class="sr-b x" data-fmt="xlsx" href="{{ $indir($rk, 'xlsx', isset($periyot[$rk]) ? 'donem=' . array_key_first($periyot[$rk]) : null) }}"><span class="material-icons-outlined">table_view</span>Excel</a>
                  <a class="sr-b p" data-fmt="pdf" target="_blank" href="{{ $indir($rk, 'pdf', isset($periyot[$rk]) ? 'donem=' . array_key_first($periyot[$rk]) : null) }}"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
                </div>
              </div>
            @endforeach
          </div>
        </section>
      @endforeach

      {{-- ===== FİYAT RADARI RAPORLARI ===== --}}
      <section class="sr-bol" id="k-radar">
        <h3><span class="material-icons-outlined">radar</span>Fiyat Radarı</h3>
        <div class="sr-not">Gizli zam, tedarikçi kıyası, hal, karne… Fiyat Radarı gün tabanlıdır: seçili döneme en yakın <b>son {{ $radarGun }} gün</b> ile hazırlanır. <a href="/fiyat-radari" style="color:#4F46E5;font-weight:800">Fiyat Radarı sayfası →</a></div>
        <div class="sr-kartlar">
          @foreach ($radar as $rk => [$ad, $acik, $ik, $renk])
            <div class="sr-k" style="--c:{{ $renk }}" data-ara="{{ mb_strtolower($ad . ' ' . $acik) }}">
              <div class="ust"><div class="ik"><span class="material-icons-outlined">{{ $ik }}</span></div><div class="t">{{ $ad }}</div></div>
              <div class="d">{{ $acik }}</div>
              <span class="sr-roz" style="--c:{{ !empty($durum['fatura']) ? '#059669' : '#DC2626' }}"><span class="material-icons-outlined">{{ !empty($durum['fatura']) ? 'check_circle' : 'error' }}</span>{{ !empty($durum['fatura']) ? 'Veri hazır' : 'Veri yok' }}</span>
              <div class="sr-bt">
                <a class="sr-b x" href="/fiyat-radari/indir?rapor={{ $rk }}&format=xlsx&gun={{ $radarGun }}"><span class="material-icons-outlined">table_view</span>Excel</a>
                <a class="sr-b p" target="_blank" href="/fiyat-radari/indir?rapor={{ $rk }}&format=pdf&gun={{ $radarGun }}"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
              </div>
            </div>
          @endforeach
        </div>
      </section>

      {{-- ===== AI ===== --}}
      <section class="sr-bol" id="k-ai">
        <h3><span class="material-icons-outlined">auto_awesome</span>AI Analiz</h3>
        <div class="sr-ai" data-ara="ai yapay zeka özet yönetici analiz soru">
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <div style="flex:1;min-width:260px">
              <div style="font-size:16px;font-weight:900;color:var(--ink)">✨ AI Yönetici Özeti — {{ $dn['ad'] }} ({{ $dn['etiket'] }})</div>
              <div style="font-size:12.5px;color:var(--sub2);margin-top:3px">Seçili dönemin satın alma verisini (harcama, fiyat değişimleri, teslimat, talepler, stok, bütçe, fire, menü etkisi) okuyup üst yönetim için <b>özet, öne çıkanlar, riskler ve öneriler</b> yazar. Bu içerik <b>yapay zekâ tarafından hazırlanır</b>; rapor dosyasında dayandığı rakamlar da yer alır.</div>
            </div>
            <button type="button" class="sr-ai-btn" id="srAiBtn" onclick="srAi(false)"><span class="material-icons-outlined">auto_awesome</span> Özet Hazırla</button>
            <button type="button" class="sr-b o" style="flex:0 0 auto;padding:10px 12px" onclick="srAi(true)"><span class="material-icons-outlined">refresh</span>Yeniden</button>
          </div>
          <div class="sr-sor"><input id="srSoru" placeholder="Soru sor: ör. 'Bu ay en çok hangi tedarikçide harcama arttı?', 'Hangi siparişler gecikiyor?'" onkeydown="if(event.key==='Enter')srSor()">
            <button type="button" class="sr-ai-btn" style="padding:10px 14px" onclick="srSor()"><span class="material-icons-outlined">send</span></button></div>
          <div id="srCevap"></div>
          <div id="srAiSonuc"></div>
          <div class="sr-bt" id="srAiIndir" style="margin-top:12px;max-width:420px" @if (!$ai) hidden @endif>
            <a class="sr-b x" href="{{ $indir('ai', 'xlsx') }}"><span class="material-icons-outlined">table_view</span>AI özeti Excel</a>
            <a class="sr-b p" target="_blank" href="{{ $indir('ai', 'pdf') }}"><span class="material-icons-outlined">picture_as_pdf</span>AI özeti PDF</a>
          </div>
        </div>
      </section>
    </div>
  </div>
</div>

{{-- ===== ÖNİZLEME ===== --}}
<div class="sr-modal" id="srModal" onclick="if(event.target===this)srKapat()">
  <div class="kutu">
    <div class="bas"><b id="srModalBas">Önizleme</b>
      <a class="sr-b x" id="srModalX" style="flex:0 0 auto;padding:7px 11px"><span class="material-icons-outlined">table_view</span>Excel</a>
      <a class="sr-b p" id="srModalP" target="_blank" style="flex:0 0 auto;padding:7px 11px"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
      <button type="button" class="sr-kapat" onclick="srKapat()"><span class="material-icons-outlined">close</span></button></div>
    <iframe id="srFrame" title="Rapor önizleme"></iframe>
  </div>
</div>

<script>
  var SR_QS = {!! json_encode($donemQs) !!};
  var SR_AI = {!! json_encode($ai, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  function srEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
  function srNorm(s) { return (s || '').replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase(); }
  function srAra(q) {
    q = srNorm(q.trim());
    document.querySelectorAll('[data-ara]').forEach(function (k) { k.style.display = !q || srNorm(k.dataset.ara).indexOf(q) > -1 ? '' : 'none'; });
    document.querySelectorAll('.sr-bol').forEach(function (b) { var g = b.querySelectorAll('[data-ara]'), v = Array.prototype.some.call(g, function (k) { return k.style.display !== 'none'; }); b.style.display = v ? '' : 'none'; });
  }
  // kartın dönemi (dönemsel raporlarda kartın kendi seçimi; diğerlerinde sayfa dönemi)
  function srKartQs(kart) { var a = kart.querySelector('[data-per] .aktif'); return a ? 'donem=' + a.dataset.donem : SR_QS; }
  function srPer(btn) {
    var kart = btn.closest('.sr-k'); kart.querySelectorAll('[data-per] button').forEach(function (b) { b.classList.toggle('aktif', b === btn); });
    var rk = kart.dataset.rapor;
    kart.querySelectorAll('a[data-fmt]').forEach(function (a) { a.href = '/satinalma-raporlar/indir?rapor=' + rk + '&format=' + a.dataset.fmt + '&' + srKartQs(kart); });
  }
  function srAc(rk, btn) {
    var kart = btn.closest('.sr-k'), qs = srKartQs(kart), u = '/satinalma-raporlar/indir?rapor=' + rk + '&' + qs;
    document.getElementById('srModalBas').textContent = kart.querySelector('.t').textContent;
    document.getElementById('srModalX').href = u + '&format=xlsx';
    document.getElementById('srModalP').href = u + '&format=pdf';
    document.getElementById('srFrame').src = u + '&format=html&onizle=1';
    document.getElementById('srModal').classList.add('acik');
  }
  function srKapat() { document.getElementById('srModal').classList.remove('acik'); document.getElementById('srFrame').src = 'about:blank'; }
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') srKapat(); });
  // kategori menüsü: tıklanan / görünen bölümü vurgula
  (function () {
    var linkler = document.querySelectorAll('#srNav a');
    function isaretle(k) { linkler.forEach(function (a) { a.classList.toggle('aktif', a.dataset.k === k); }); }
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) isaretle(e.target.id.slice(2)); }); }, { rootMargin: '-20% 0px -70% 0px' });
      document.querySelectorAll('.sr-bol').forEach(function (b) { io.observe(b); });
    }
    isaretle((location.hash || '#k-donemsel').slice(3));
  })();

  // ---- AI ----
  function srAiCiz(a) {
    if (!a) return;
    var bol = function (anah, bas, renk) {
      var l = a[anah] || []; if (!l.length) return '';
      return '<div class="sr-ai-k" style="--c:' + renk + '"><h5>' + bas + '</h5>' + l.map(function (x) { return '<div class="sr-ai-m"><b>' + srEsc(x.baslik) + '</b>' + srEsc(x.detay) + '</div>'; }).join('') + '</div>';
    };
    document.getElementById('srAiSonuc').innerHTML =
      '<div class="sr-cevap" style="white-space:normal"><div style="font-size:10.5px;font-weight:800;color:var(--sub);letter-spacing:.4px;text-transform:uppercase">Yapay zekâ özeti · ' + srEsc(a._donem || '') + ' · ' + srEsc(a._zaman || '') + '</div><div style="margin-top:4px">' + srEsc(a.ozet) + '</div></div>' +
      '<div class="sr-ai-g">' + bol('one_cikanlar', 'Öne çıkanlar', '#4F46E5') + bol('riskler', 'Riskler', '#DC2626') + bol('oneriler', 'Öneriler', '#059669') + '</div>';
    document.getElementById('srAiIndir').hidden = false;
  }
  function srPost(g) {
    return fetch('/satinalma-raporlar/ai?' + SR_QS, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(g) }).then(function (r) { return r.json(); });
  }
  function srAi(yenile) {
    var b = document.getElementById('srAiBtn'); if (b.disabled) return; var eski = b.innerHTML;
    b.disabled = true; b.innerHTML = '<span class="material-icons-outlined" style="animation:srDon 1s linear infinite">autorenew</span> Hazırlanıyor… (~20 sn)';
    srPost({ yenile: yenile ? 1 : 0 }).then(function (s) {
      b.disabled = false; b.innerHTML = eski;
      if (!s.ok) { document.getElementById('srAiSonuc').innerHTML = '<div class="sr-cevap" style="color:#B91C1C">' + srEsc(s.hata || 'Özet hazırlanamadı.') + '</div>'; return; }
      srAiCiz(s.analiz);
    }).catch(function () { b.disabled = false; b.innerHTML = eski; document.getElementById('srAiSonuc').innerHTML = '<div class="sr-cevap" style="color:#B91C1C">Bağlantı hatası.</div>'; });
  }
  function srSor() {
    var q = document.getElementById('srSoru').value.trim(); if (!q) return; var c = document.getElementById('srCevap');
    c.innerHTML = '<div class="sr-cevap">Düşünüyor…</div>';
    srPost({ soru: q }).then(function (s) { c.innerHTML = '<div class="sr-cevap"><b style="display:block;margin-bottom:6px;color:var(--sub)">❓ ' + srEsc(q) + '</b>' + srEsc(s.ok ? s.cevap : (s.hata || 'Cevap alınamadı.')) + '</div>'; })
      .catch(function () { c.innerHTML = '<div class="sr-cevap">Bağlantı hatası.</div>'; });
  }
  if (SR_AI) srAiCiz(SR_AI);
</script>
@endsection

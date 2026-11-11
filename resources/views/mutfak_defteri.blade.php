@extends('layout.app')
@section('rk_ikon', 'menu_book')
@section('baslik', 'Mutfak Kontrol Defteri')
@section('title', 'Mutfak Kontrol Defteri')

@section('content')
@php
  $cfg = [
    'sicaklik' => ['ad'=>'Sıcaklık (HACCP)','kisa'=>'Sıcaklık','ikon'=>'thermostat','renk'=>'#0EA5E9','renk2'=>'#2563EB',
       'baslikLbl'=>'Nokta / Cihaz','baslikPh'=>'Örn: Buzdolabı 1 · Et dolabı · Bain-marie',
       'kategoriler'=>['Buzdolabı','Dondurucu','Sıcak bekletme','Pişirme','Soğutma','Teslim alma'],
       'degerLbl'=>'Ölçülen °C','deger2Lbl'=>'Üst limit °C','birim'=>false,
       'ipucu'=>'Soğuk zincirde ölçülen ≤ üst limit, sıcak bekletme ve pişirmede ölçülen ≥ alt limit ise UYGUN sayılır. Limitler Türk Gıda Kodeksi / HACCP planına göre.'],
    'fire' => ['ad'=>'Fire / İsraf','kisa'=>'Fire','ikon'=>'delete_outline','renk'=>'#F97316','renk2'=>'#DC2626',
       'baslikLbl'=>'Ürün / Malzeme','baslikPh'=>'Örn: Domates · Pişmiş pilav · Tavuk but',
       'kategoriler'=>['Hazırlık fire','Bozulma / SKT','Tabak / porsiyon','Pişirme hatası'],
       'degerLbl'=>'Miktar','deger2Lbl'=>'Tutar (₺)','birim'=>true,
       'ipucu'=>'Atılan her şeyi kategorisi ve TL değeriyle yaz. Haftalık bak: en çok nerede israf var, porsiyonu/siparişi ona göre ayarla.'],
    'checklist' => ['ad'=>'Açılış / Kapanış','kisa'=>'Açılış / Kapanış','ikon'=>'checklist','renk'=>'#10B981','renk2'=>'#0D9488',
       'baslikLbl'=>'Kontrol maddesi','baslikPh'=>'Örn: Fırın açıldı · Soğuk oda temiz · Gaz kapatıldı',
       'kategoriler'=>['Açılış','Servis öncesi','Kapanış'],
       'degerLbl'=>'','deger2Lbl'=>'','birim'=>false,
       'ipucu'=>'Açılış ve kapanış kontrollerini tek tek işaretle. Net madde yaz: "istasyon hazır" değil, "ızgara 180°C ve mise en place tam".'],
    'prep' => ['ad'=>'Prep / Par','kisa'=>'Prep / Par','ikon'=>'restaurant_menu','renk'=>'#8B5CF6','renk2'=>'#6366F1',
       'baslikLbl'=>'Ürün / Yarı mamül','baslikPh'=>'Örn: Köfte harcı · Sos · Doğranmış soğan',
       'kategoriler'=>[],
       'degerLbl'=>'Par (hedef)','deger2Lbl'=>'Mevcut','birim'=>true,
       'ipucu'=>'Günün beklenen yoğunluğuna göre PAR (hedef) ve mevcut miktarı yaz. Hazırlanacak miktar = Par − Mevcut otomatik hesaplanır.'],
  ];
  $a = $cfg[$tip] ?? $cfg['sicaklik'];
  // Route önbelleği/opcache eski kalırsa bu değişkenler gelmeyebilir → görünüm kendi hesaplar
  if (!isset($sayilar)) {
    $sayilar = [];
    try { $sayilar = DB::table('mutfak_kayitlari')->where('sube_id', _webSubeId())->where('tarih', now()->toDateString())->select('tip', DB::raw('count(*) as n'))->groupBy('tip')->pluck('n', 'tip')->toArray(); } catch (\Throwable $e) {}
  }
  if (!isset($ornekVar)) {
    $ornekVar = false;
    try { $ornekVar = DB::table('mutfak_kayitlari')->where('sube_id', _webSubeId())->where('not', 'Örnek kayıt')->exists(); } catch (\Throwable $e) {}
  }
  $bugun = now()->toDateString(); $dun = now()->subDay()->toDateString();
  $sayi = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','); };
  $hazirlaHesap = function ($k) { return (is_numeric($k->deger) && is_numeric($k->deger2)) ? max(0, (float) $k->deger - (float) $k->deger2) : null; };

  // Hero istatistikleri (bugün)
  $bugunList = collect($liste)->filter(function ($k) use ($bugun) { return (string) $k->tarih === $bugun; });
  if ($tip === 'sicaklik') {
    $ist = [['Bugün ölçüm', $bugunList->count()], ['Uygun', $bugunList->where('sonuc', 'uygun')->count()], ['Uygunsuz', $bugunList->where('sonuc', 'uygunsuz')->count()]];
  } elseif ($tip === 'fire') {
    $hafta = collect($liste)->filter(function ($k) { return $k->tarih && $k->tarih >= now()->subDays(6)->toDateString(); });
    $ist = [['Bugün kayıt', $bugunList->count()], ['Bugün kayıp', $sayi($bugunList->sum(function ($k) { return (float) $k->deger2; })) . ' ₺'], ['Son 7 gün', $sayi($hafta->sum(function ($k) { return (float) $k->deger2; })) . ' ₺']];
  } elseif ($tip === 'checklist') {
    $ist = [['Açılış', $bugunList->where('kategori', 'Açılış')->count()], ['Servis öncesi', $bugunList->where('kategori', 'Servis öncesi')->count()], ['Kapanış', $bugunList->where('kategori', 'Kapanış')->count()]];
  } else {
    $hz = $bugunList->filter(function ($k) use ($hazirlaHesap) { return ($hazirlaHesap($k) ?? 0) > 0; })->count();
    $ist = [['Bugün ürün', $bugunList->count()], ['Hazırlanacak', $hz], ['Hazır', $bugunList->count() - $hz]];
  }
  $alarm = $tip === 'sicaklik' && $bugunList->where('sonuc', 'uygunsuz')->count() > 0;

  // Tarihe göre grupla
  $gruplar = collect($liste)->groupBy(function ($k) { return (string) $k->tarih; });
  $gunAd = function ($t) use ($bugun, $dun) {
    if ($t === $bugun) return 'Bugün';
    if ($t === $dun) return 'Dün';
    try { return \Carbon\Carbon::parse($t)->locale('tr')->translatedFormat('d F l'); } catch (\Throwable $e) { return $t; }
  };
@endphp
<style>
  #md{width:100%;max-width:1680px;margin:0 auto;--c1:{{ $a['renk'] }};--c2:{{ $a['renk2'] }}}
  #md *{box-sizing:border-box}
  .md-msg{padding:11px 15px;border-radius:12px;margin-bottom:14px;font-size:13px;font-weight:700}
  .md-msg.ok{background:rgba(16,185,129,.13);color:#047857} .md-msg.er{background:rgba(244,63,94,.12);color:#be123c}
  body.koyu .md-msg.ok{color:#6EE7B7} body.koyu .md-msg.er{color:#FDA4AF}

  /* Sekmeler: renkli kartlar */
  .md-tabs{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
  .md-tab{position:relative;display:flex;align-items:center;gap:11px;padding:13px 14px;border-radius:16px;text-decoration:none;
    background:var(--card);border:1.5px solid color-mix(in srgb,var(--tc) 22%,var(--line));transition:transform .15s,box-shadow .15s}
  .md-tab:hover{transform:translateY(-2px);box-shadow:0 10px 22px -14px var(--tc)}
  .md-tab .ik{width:40px;height:40px;flex:0 0 40px;border-radius:12px;display:flex;align-items:center;justify-content:center;
    background:color-mix(in srgb,var(--tc) 14%,transparent);color:var(--tc)}
  .md-tab .ik .material-icons-outlined{font-size:22px}
  .md-tab .ad{font-size:13.5px;font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .md-tab .alt{font-size:11.5px;color:var(--sub);font-weight:600;margin-top:1px}
  .md-tab.on{background:linear-gradient(135deg,var(--tc),var(--tc2));border-color:transparent;box-shadow:0 12px 26px -14px var(--tc)}
  .md-tab.on .ik{background:rgba(255,255,255,.22);color:#fff}
  .md-tab.on .ad,.md-tab.on .alt{color:#fff}
  .md-tab.on .alt{opacity:.9}

  /* Hero */
  .md-hero{position:relative;overflow:hidden;border-radius:22px;padding:22px 24px;margin-bottom:16px;color:#fff;
    background:linear-gradient(120deg,var(--c1),var(--c2));box-shadow:0 14px 32px -16px var(--c1)}
  .md-hero.alarm{background:linear-gradient(120deg,#F43F5E,#E11D48 55%,#F97316)}
  .md-hero::before{content:"";position:absolute;width:260px;height:260px;border-radius:50%;right:-70px;top:-130px;background:rgba(255,255,255,.13)}
  .md-hero::after{content:"";position:absolute;width:160px;height:160px;border-radius:50%;right:220px;bottom:-110px;background:rgba(255,255,255,.08)}
  .md-hero-ic{position:relative;z-index:1;display:flex;align-items:center;gap:18px;flex-wrap:wrap}
  .md-hero .hik{width:60px;height:60px;flex:0 0 60px;border-radius:18px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3)}
  .md-hero .hik .material-icons-outlined{font-size:32px}
  .md-hero .hy{flex:1;min-width:240px}
  .md-hero h2{margin:0;color:#fff;font-size:23px;font-weight:900;letter-spacing:-.2px}
  .md-hero p{margin:5px 0 0;font-size:13px;opacity:.92;line-height:1.45;max-width:560px}
  .md-ist{display:flex;gap:10px;flex-wrap:wrap}
  .md-ist div{min-width:96px;padding:10px 14px;border-radius:14px;background:rgba(255,255,255,.17);border:1px solid rgba(255,255,255,.25);text-align:center}
  .md-ist b{display:block;font-size:22px;font-weight:900;line-height:1.1;font-variant-numeric:tabular-nums;white-space:nowrap}
  .md-ist span{font-size:11.5px;font-weight:700;opacity:.92}

  /* Form */
  .md-form{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:18px;margin-bottom:22px;box-shadow:var(--golge)}
  .md-form-bas{display:flex;align-items:center;gap:10px;margin-bottom:14px}
  .md-form-bas .material-icons-outlined{width:34px;height:34px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;background:linear-gradient(135deg,var(--c1),var(--c2))}
  .md-form-bas b{font-size:15px;font-weight:900;color:var(--ink)}
  .md-form label.l{font-size:11.5px;color:var(--sub2);font-weight:800;display:block;margin-bottom:5px}
  .md-alanlar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
  .md-alanlar>div{min-width:0}
  .md-form input[type=text],.md-form input[type=number],.md-form input[type=date],.md-form input:not([type]){width:100%;border:1.5px solid var(--line);background:var(--card2);color:var(--ink);
    border-radius:12px;padding:10px 12px;font-size:14px;font-family:inherit;outline:none;transition:border-color .15s,background .15s}
  .md-form input:focus{border-color:var(--c1);background:var(--card)}
  .md-katlar{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px}
  .md-kat{position:relative;cursor:pointer}
  .md-kat input{position:absolute;opacity:0;pointer-events:none}
  .md-kat span{display:inline-block;padding:7px 13px;border-radius:999px;font-size:12.5px;font-weight:800;color:var(--c1);
    background:color-mix(in srgb,var(--c1) 9%,var(--card));border:1.5px solid color-mix(in srgb,var(--c1) 28%,transparent);transition:all .15s}
  .md-kat input:checked+span{background:linear-gradient(135deg,var(--c1),var(--c2));color:#fff;border-color:transparent;box-shadow:0 6px 14px -8px var(--c1)}
  .md-add{display:inline-flex;align-items:center;gap:6px;color:#fff;border:0;border-radius:12px;padding:11px 22px;font-weight:900;cursor:pointer;font-size:14px;font-family:inherit;
    background:linear-gradient(135deg,var(--c1),var(--c2));box-shadow:0 8px 18px -10px var(--c1)}
  .md-add .material-icons-outlined{font-size:19px}
  .md-onizle{display:none;margin-top:12px;padding:9px 13px;border-radius:12px;font-size:13px;font-weight:800;align-items:center;gap:7px}
  .md-onizle .material-icons-outlined{font-size:18px}
  .md-onizle.uygun{display:flex;background:rgba(16,185,129,.13);color:#059669}
  .md-onizle.uygunsuz{display:flex;background:rgba(244,63,94,.12);color:#E11D48}
  .md-onizle.bilgi{display:flex;background:color-mix(in srgb,var(--c1) 11%,transparent);color:var(--c1)}

  /* Liste */
  .md-gun{display:flex;align-items:center;gap:10px;margin:0 0 10px;font-size:13px;font-weight:900;color:var(--ink)}
  .md-gun::before{content:"";width:6px;height:18px;border-radius:4px;background:linear-gradient(180deg,var(--c1),var(--c2))}
  .md-gun span{font-size:11.5px;font-weight:800;padding:2px 9px;border-radius:999px;color:var(--c1);background:color-mix(in srgb,var(--c1) 12%,transparent)}
  .md-grup{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px;margin-bottom:22px}
  .md-r{position:relative;display:flex;align-items:center;gap:12px;padding:13px 14px;border-radius:16px;min-width:0;
    background:linear-gradient(135deg,color-mix(in srgb,var(--rc) 10%,var(--card)),var(--card));
    border:1px solid color-mix(in srgb,var(--rc) 26%,var(--line))}
  .md-r .ik{width:42px;height:42px;flex:0 0 42px;border-radius:13px;display:flex;align-items:center;justify-content:center;color:#fff;
    background:var(--rc);box-shadow:0 6px 14px -8px var(--rc)}
  .md-r .ik .material-icons-outlined{font-size:21px}
  .md-r .ri{flex:1;min-width:0}
  .md-r .t{font-weight:900;font-size:14.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .md-r .d{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:4px;font-size:11.5px;color:var(--sub)}
  .md-chip{font-size:11px;font-weight:800;border-radius:999px;padding:2px 9px;color:var(--rc);background:color-mix(in srgb,var(--rc) 12%,transparent)}
  .md-badge{font-size:11px;font-weight:900;border-radius:999px;padding:2px 9px;color:#fff}
  .md-val{text-align:right;flex:0 0 auto}
  .md-val b{display:block;font-size:18px;font-weight:900;color:var(--ink);font-variant-numeric:tabular-nums;white-space:nowrap}
  .md-val small{display:block;font-size:11px;color:var(--sub);white-space:nowrap}
  .md-r form{margin:0}
  .md-sil{width:30px;height:30px;border:0;border-radius:9px;background:transparent;cursor:pointer;color:var(--sub);display:flex;align-items:center;justify-content:center}
  .md-sil:hover{background:rgba(244,63,94,.12);color:#E11D48}
  .md-sil .material-icons-outlined{font-size:18px}

  .md-bos{text-align:center;padding:34px 20px;border-radius:20px;background:linear-gradient(135deg,color-mix(in srgb,var(--c1) 10%,var(--card)),var(--card));
    border:1.5px dashed color-mix(in srgb,var(--c1) 35%,var(--line))}
  .md-bos .bi{width:66px;height:66px;margin:0 auto 12px;border-radius:20px;display:flex;align-items:center;justify-content:center;color:#fff;
    background:linear-gradient(135deg,var(--c1),var(--c2));box-shadow:0 10px 22px -12px var(--c1)}
  .md-bos .bi .material-icons-outlined{font-size:34px}
  .md-bos b{display:block;font-size:16px;font-weight:900;color:var(--ink)}
  .md-bos span{font-size:13px;color:var(--sub)}
  .md-ornek{display:inline-flex;align-items:center;gap:7px;margin-top:14px;padding:10px 18px;border-radius:12px;cursor:pointer;font-family:inherit;font-size:13.5px;font-weight:800;
    border:0;color:#fff;background:linear-gradient(135deg,var(--c1),var(--c2))}
  .md-ornek.sil{background:transparent;color:var(--sub2);border:1px dashed var(--line)}
  .md-ornek.sil:hover{color:#E11D48;border-color:#E11D48}
  .md-ornek .material-icons-outlined{font-size:18px}

  /* ---- Responsive ---- */
  @media (max-width:1200px){
    .md-ist{width:100%}.md-ist div{flex:1;min-width:0}
  }
  @media (max-width:900px){
    .md-tabs{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .md-alanlar{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
    .md-alanlar>div:first-child{grid-column:1/-1}
    .md-alanlar .md-add{grid-column:1/-1;justify-content:center;width:100%}
  }
  @media (max-width:600px){
    .md-tabs{gap:8px;margin-bottom:12px}
    .md-tab{padding:10px;gap:8px;border-radius:14px}
    .md-tab .ik{width:34px;height:34px;flex-basis:34px;border-radius:10px}
    .md-tab .ik .material-icons-outlined{font-size:19px}
    .md-tab .ad{font-size:12.5px}.md-tab .alt{font-size:10.5px}
    .md-hero{padding:16px;border-radius:18px}.md-hero-ic{gap:12px}
    .md-hero .hik{width:46px;height:46px;flex-basis:46px;border-radius:14px}
    .md-hero .hik .material-icons-outlined{font-size:26px}
    .md-hero h2{font-size:18px}.md-hero p{font-size:12px}
    .md-hero .hy{min-width:0;flex:1 1 200px}
    .md-ist{gap:8px}.md-ist div{padding:8px 6px;border-radius:12px}
    .md-ist b{font-size:18px}.md-ist span{font-size:10.5px}
    .md-form{padding:14px;border-radius:16px}
    .md-katlar{flex-wrap:nowrap;overflow-x:auto;margin:0 -14px 12px;padding:0 14px 4px;scrollbar-width:none}
    .md-katlar::-webkit-scrollbar{display:none}
    .md-kat span{white-space:nowrap}
    .md-grup{grid-template-columns:1fr}
    .md-r{padding:11px 12px;gap:10px}
    .md-r .ik{width:36px;height:36px;flex-basis:36px;border-radius:11px}
    .md-r .t{font-size:13.5px}
    .md-val b{font-size:15px}
    .md-bos{padding:26px 14px}
  }
  @media (max-width:380px){
    .md-alanlar{grid-template-columns:1fr}
    .md-tab .alt{display:none}
  }
</style>
<div class="rk-body">
<div id="md">
  @if(session('ok'))<div class="md-msg ok">✅ {{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="md-msg er">⚠️ {{ session('hata') }}</div>@endif

  {{-- ===== SEKMELER ===== --}}
  <div class="md-tabs">
    @foreach($tipler as $k=>$v)
      <a class="md-tab {{ $tip===$k?'on':'' }}" style="--tc:{{ $cfg[$k]['renk'] }};--tc2:{{ $cfg[$k]['renk2'] }}" href="/mutfak-defteri?tip={{ $k }}">
        <span class="ik"><span class="material-icons-outlined">{{ $cfg[$k]['ikon'] }}</span></span>
        <span style="min-width:0"><div class="ad">{{ $cfg[$k]['kisa'] }}</div><div class="alt">Bugün {{ (int) ($sayilar[$k] ?? 0) }} kayıt</div></span>
      </a>
    @endforeach
  </div>

  {{-- ===== HERO ===== --}}
  <div class="md-hero{{ $alarm ? ' alarm' : '' }}">
    <div class="md-hero-ic">
      <div class="hik"><span class="material-icons-outlined">{{ $alarm ? 'warning_amber' : $a['ikon'] }}</span></div>
      <div class="hy">
        <h2>{{ $alarm ? 'Uygunsuz ölçüm var, hemen müdahale et' : $a['ad'] }}</h2>
        <p>{{ $a['ipucu'] }}</p>
      </div>
      <div class="md-ist">
        @foreach($ist as $s)<div><b>{{ $s[1] }}</b><span>{{ $s[0] }}</span></div>@endforeach
      </div>
    </div>
  </div>

  {{-- ===== FORM ===== --}}
  <form class="md-form" method="POST" action="/mutfak-defteri/ekle" id="mdForm">
    @csrf<input type="hidden" name="tip" value="{{ $tip }}">
    <div class="md-form-bas"><span class="material-icons-outlined">add</span><b>Yeni kayıt</b></div>
    @if(count($a['kategoriler']))
      <label class="l">Kategori</label>
      <div class="md-katlar">
        @foreach($a['kategoriler'] as $i => $kat)
          <label class="md-kat"><input type="radio" name="kategori" value="{{ $kat }}" {{ $i === 0 ? 'checked' : '' }}><span>{{ $kat }}</span></label>
        @endforeach
      </div>
    @endif
    <div class="md-alanlar">
      <div style="flex:1 1 240px"><label class="l">{{ $a['baslikLbl'] }}</label><input type="text" name="baslik" placeholder="{{ $a['baslikPh'] }}" required maxlength="200"></div>
      @if($a['degerLbl'])<div style="flex:0 1 130px"><label class="l">{{ $a['degerLbl'] }}</label><input name="deger" id="mdDeger" @if($tip==='sicaklik' || $tip==='prep')type="number" step="any"@else type="text" @endif></div>@endif
      @if($a['birim'])<div style="flex:0 1 110px"><label class="l">Birim</label><input type="text" name="birim" placeholder="kg / adet / L"></div>@endif
      @if($a['deger2Lbl'])<div style="flex:0 1 130px"><label class="l" id="mdDeger2Lbl">{{ $a['deger2Lbl'] }}</label><input name="deger2" id="mdDeger2" type="number" step="any"></div>@endif
      <div style="flex:0 1 160px"><label class="l">Tarih</label><input name="tarih" type="date" value="{{ $bugun }}"></div>
      <button class="md-add" type="submit"><span class="material-icons-outlined">add_circle</span> Ekle</button>
    </div>
    <div class="md-onizle" id="mdOnizle"></div>
  </form>

  {{-- ===== LİSTE (tarihe göre) ===== --}}
  @forelse($gruplar as $tarih => $kayitlar)
    <div class="md-gun">{{ $gunAd($tarih) }} <span>{{ count($kayitlar) }}</span></div>
    <div class="md-grup">
      @foreach($kayitlar as $k)
        @php
          $hazirla = $tip === 'prep' ? $hazirlaHesap($k) : null;
          $rc = $a['renk'];
          if ($k->sonuc === 'uygunsuz') $rc = '#E11D48';
          elseif ($tip === 'sicaklik' && $k->sonuc === 'uygun') $rc = '#10B981';
          elseif ($tip === 'prep' && $hazirla !== null && $hazirla <= 0) $rc = '#10B981';
          $sicakKat = in_array($k->kategori, ['Sıcak bekletme', 'Pişirme'], true);
        @endphp
        <div class="md-r" style="--rc:{{ $rc }}">
          <span class="ik"><span class="material-icons-outlined">{{ $k->sonuc === 'uygunsuz' ? 'warning_amber' : $a['ikon'] }}</span></span>
          <div class="ri">
            <div class="t">{{ $k->baslik }}</div>
            <div class="d">
              @if($k->kategori)<span class="md-chip">{{ $k->kategori }}</span>@endif
              @if($k->sonuc === 'uygun')<span class="md-badge" style="background:#10B981">✓ Uygun</span>@endif
              @if($k->sonuc === 'uygunsuz')<span class="md-badge" style="background:#E11D48">✕ Uygunsuz</span>@endif
              @if($k->sonuc === 'yapildi')<span class="md-badge" style="background:#10B981">✓ Yapıldı</span>@endif
              <span>{{ $k->personel_ad ?: '' }}{{ $k->not ? ($k->personel_ad ? ' · ' : '') . $k->not : '' }}</span>
            </div>
          </div>
          <div class="md-val">
            @if($tip==='sicaklik' && $k->deger !== null)
              <b>{{ $k->deger }}°C</b>@if($k->deger2 !== null)<small>{{ $sicakKat ? 'alt' : 'üst' }} limit {{ $k->deger2 }}°C</small>@endif
            @elseif($tip==='fire')
              @if($k->deger2 !== null)<b>{{ $k->deger2 }} ₺</b>@endif
              @if($k->deger !== null)<small>{{ $k->deger }} {{ $k->birim }}</small>@endif
            @elseif($tip==='prep')
              @if($hazirla !== null)
                <b>{{ $hazirla > 0 ? '+' . $sayi($hazirla) . ' ' . $k->birim : 'Hazır' }}</b>
              @endif
              <small>Par {{ $k->deger }} · Mevcut {{ $k->deger2 }}</small>
            @endif
          </div>
          <form method="POST" action="/mutfak-defteri/sil" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><input type="hidden" name="tip" value="{{ $tip }}"><button type="submit" class="md-sil" title="Sil"><span class="material-icons-outlined">delete_outline</span></button></form>
        </div>
      @endforeach
    </div>
  @empty
    <div class="md-bos">
      <div class="bi"><span class="material-icons-outlined">{{ $a['ikon'] }}</span></div>
      <b>Henüz {{ mb_strtolower($a['ad']) }} kaydı yok</b>
      <span>Yukarıdaki formdan ilk kaydı ekle ya da örneklerle nasıl göründüğüne bak.</span>
      @if(!$ornekVar)
        <form method="POST" action="/mutfak-defteri/ornek" style="margin:0">@csrf<input type="hidden" name="tip" value="{{ $tip }}">
          <button type="submit" class="md-ornek"><span class="material-icons-outlined">auto_awesome</span> Örnek kayıtları yükle</button>
        </form>
      @endif
    </div>
  @endforelse

  @if($ornekVar)
    <form method="POST" action="/mutfak-defteri/ornek" style="margin:6px 0 0;text-align:center">@csrf<input type="hidden" name="tip" value="{{ $tip }}"><input type="hidden" name="islem" value="sil">
      <button type="submit" class="md-ornek sil"><span class="material-icons-outlined">delete_sweep</span> Örnek kayıtları sil (tüm sekmeler)</button>
    </form>
  @endif
</div>
</div>

<script>
(function () {
  var TIP = {!! json_encode($tip) !!}, f = document.getElementById('mdForm'), on = document.getElementById('mdOnizle');
  var d1 = document.getElementById('mdDeger'), d2 = document.getElementById('mdDeger2'), lbl = document.getElementById('mdDeger2Lbl');
  if (!f || !on) return;
  // Sunucu UTC; gece yarısından sonra varsayılan tarih dünde kalmasın → cihazın bugünü
  var tInp = f.querySelector('input[name=tarih]');
  if (tInp) { var n = new Date(); tInp.value = n.getFullYear() + '-' + String(n.getMonth() + 1).padStart(2, '0') + '-' + String(n.getDate()).padStart(2, '0'); }
  // Kategoriye göre önerilen limit (HACCP genel değerler; işletme planına göre değiştirilebilir)
  var LIMIT = {'Buzdolabı': 4, 'Dondurucu': -18, 'Sıcak bekletme': 63, 'Pişirme': 75, 'Soğutma': 5, 'Teslim alma': 4};
  var SICAK = {'Sıcak bekletme': 1, 'Pişirme': 1};
  var elleLimit = false;
  function kat() { var r = f.querySelector('input[name=kategori]:checked'); return r ? r.value : ''; }
  function sayi(v) { return Math.round(v * 100) / 100; }
  function guncelle() {
    on.className = 'md-onizle'; on.innerHTML = '';
    if (!d1 || !d2 || d1.value === '' || d2.value === '') return;
    var a = parseFloat(d1.value), b = parseFloat(d2.value);
    if (isNaN(a) || isNaN(b)) return;
    if (TIP === 'sicaklik') {
      var ok = SICAK[kat()] ? a >= b : a <= b;
      on.className = 'md-onizle ' + (ok ? 'uygun' : 'uygunsuz');
      on.innerHTML = '<span class="material-icons-outlined">' + (ok ? 'check_circle' : 'warning_amber') + '</span>' +
        (ok ? 'UYGUN olarak kaydedilecek' : 'UYGUNSUZ olarak kaydedilecek — düzeltici işlem yap');
    } else if (TIP === 'prep') {
      var h = Math.max(0, a - b);
      on.className = 'md-onizle ' + (h > 0 ? 'bilgi' : 'uygun');
      on.innerHTML = '<span class="material-icons-outlined">' + (h > 0 ? 'restaurant' : 'check_circle') + '</span>' +
        (h > 0 ? 'Hazırlanacak: ' + sayi(h) : 'Par tamam, hazırlık gerekmez');
    }
  }
  function katDegisti() {
    if (TIP !== 'sicaklik') return;
    if (lbl) lbl.textContent = SICAK[kat()] ? 'Alt limit °C' : 'Üst limit °C';
    if (d2 && !elleLimit && LIMIT.hasOwnProperty(kat())) d2.value = LIMIT[kat()];
    guncelle();
  }
  f.addEventListener('change', function (e) { if (e.target.name === 'kategori') katDegisti(); });
  if (d1) d1.addEventListener('input', guncelle);
  if (d2) d2.addEventListener('input', function () { elleLimit = true; guncelle(); });
  katDegisti();
})();
</script>
@endsection

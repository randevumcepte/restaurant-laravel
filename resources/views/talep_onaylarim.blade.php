@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Onay Bekleyen Talepler')
@section('title', 'Talep Onaylarım')

@section('content')
@php
    // Departman adına göre ikon/renk (istasyon talepleri tek bakışta ayrışsın)
    $_depStil = function ($ad) {
        $a = mb_strtolower((string) $ad);
        foreach ([['soğuk', 'kitchen', '#10B981'], ['garde', 'kitchen', '#10B981'], ['pasta', 'cake', '#EC4899'], ['tatlı', 'cake', '#EC4899'], ['kasap', 'kebab_dining', '#DC2626'],
                  ['bulaşık', 'wash', '#06B6D4'], ['steward', 'wash', '#06B6D4'], ['bar', 'local_bar', '#F59E0B'], ['mutfak', 'restaurant', '#F97316'], ['servis', 'room_service', '#3B82F6'], ['kasa', 'point_of_sale', '#8B5CF6']] as [$k, $ik, $rn])
            if (str_contains($a, $k)) return [$ik, $rn];
        return ['inventory_2', '#7C3AED'];
    };
    $_bas = fn ($ad) => mb_strtoupper(collect(preg_split('/\s+/u', trim((string) $ad)))->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('')) ?: '?';
    $_simdi = \Carbon\Carbon::now();
    $_acilSay = $talepler->where('aciliyet', 'acil')->count();
    $_kalemSay = $talepler->sum(fn ($t) => count($t->kalemDetay ?? []));
    $_enEski = $talepler->min('created_at');
    $_depler = $talepler->pluck('departman')->filter()->unique()->values();
    $_sure = function ($ts) use ($_simdi) {
        if (!$ts) return ['', 0];
        $dk = max(0, (int) \Carbon\Carbon::parse($ts)->diffInMinutes($_simdi));
        $s = $dk < 60 ? $dk . ' dk' : ($dk < 1440 ? floor($dk / 60) . ' sa' : floor($dk / 1440) . ' gün');
        return [$s, $dk];
    };
@endphp
<style>
  #to{max-width:1040px;margin:0 auto;--to-yesil:#10B981;--to-kirmizi:#F43F5E;--to-mor:#7C3AED;--to-turuncu:#F59E0B}
  #to *{box-sizing:border-box}
  /* Özet şeridi */
  .to-ozet{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
  .to-oz{position:relative;overflow:hidden;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:14px 16px;display:flex;align-items:center;gap:12px}
  .to-oz::after{content:'';position:absolute;right:-26px;top:-26px;width:84px;height:84px;border-radius:50%;background:var(--oz);opacity:.09}
  .to-oz-ik{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb,var(--oz) 14%,transparent);color:var(--oz);flex:none}
  .to-oz-ik .material-icons-outlined{font-size:21px}
  .to-oz b{display:block;font-size:22px;font-weight:800;color:var(--ink);line-height:1.1;letter-spacing:-.3px}
  .to-oz span.l{font-size:11.5px;color:var(--sub);font-weight:600}
  /* Araç çubuğu */
  .to-arac{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .to-filtre{display:flex;gap:6px;overflow-x:auto;scrollbar-width:none;flex:1;min-width:0;padding-bottom:2px}
  .to-filtre::-webkit-scrollbar{display:none}
  .to-chip{border:1px solid var(--line);background:var(--card);color:var(--sub2);border-radius:999px;padding:7px 14px;font-size:12.5px;font-weight:700;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:6px;transition:.15s;font-family:inherit}
  .to-chip:hover{border-color:var(--to-mor);color:var(--to-mor)}
  .to-chip.aktif{background:var(--to-mor);border-color:var(--to-mor);color:#fff;box-shadow:0 6px 16px -6px color-mix(in srgb,var(--to-mor) 70%,transparent)}
  .to-chip i{font-style:normal;font-size:11px;background:color-mix(in srgb,currentColor 16%,transparent);border-radius:999px;padding:1px 7px}
  .to-tumu{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;font-weight:700;color:var(--sub2);cursor:pointer;user-select:none;white-space:nowrap}
  .to-tumu input,.to-sec input{width:18px;height:18px;accent-color:var(--to-mor);cursor:pointer;margin:0}
  /* Kart */
  .to-liste{display:grid;gap:14px}
  .to-kart{position:relative;background:var(--card);border:1px solid var(--line);border-radius:20px;padding:18px 18px 16px 22px;overflow:hidden;transition:transform .2s,box-shadow .2s,opacity .35s,max-height .4s,margin .4s,padding .4s;max-height:1400px}
  .to-kart::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--dep)}
  .to-kart:hover{box-shadow:0 14px 34px -18px rgba(15,23,42,.35);transform:translateY(-1px)}
  .to-kart.acil{border-color:color-mix(in srgb,var(--to-kirmizi) 40%,var(--line));background:linear-gradient(180deg,color-mix(in srgb,var(--to-kirmizi) 5%,var(--card)),var(--card) 60%)}
  .to-kart.acil::before{background:var(--to-kirmizi)}
  .to-kart.secili{border-color:var(--to-mor);box-shadow:0 0 0 3px color-mix(in srgb,var(--to-mor) 18%,transparent)}
  .to-kart.gidiyor{opacity:0;transform:translateX(30px);max-height:0;padding-top:0;padding-bottom:0;margin:0;border-width:0}
  .to-kart.gidiyor.red{transform:translateX(-30px)}
  .to-ust{display:flex;align-items:flex-start;gap:12px}
  .to-sec{padding-top:10px}
  .to-dep-ik{width:44px;height:44px;border-radius:14px;display:grid;place-items:center;flex:none;background:color-mix(in srgb,var(--dep) 14%,transparent);color:var(--dep)}
  .to-dep-ik .material-icons-outlined{font-size:23px}
  .to-bas{flex:1;min-width:0}
  .to-dep{font-weight:800;font-size:15.5px;color:var(--ink);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .to-alt{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:5px;font-size:12px;color:var(--sub)}
  .to-av{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#A855F7);color:#fff;font-size:9.5px;font-weight:800;display:grid;place-items:center}
  .to-kisi{display:inline-flex;align-items:center;gap:6px;font-weight:600;color:var(--sub2)}
  .to-badge{font-size:10.5px;font-weight:800;border-radius:999px;padding:3px 10px;white-space:nowrap;display:inline-flex;align-items:center;gap:5px;letter-spacing:.2px}
  .to-acil-b{background:color-mix(in srgb,var(--to-kirmizi) 14%,transparent);color:var(--to-kirmizi)}
  .to-acil-b::before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor;animation:toNabiz 1.4s infinite}
  @keyframes toNabiz{0%{box-shadow:0 0 0 0 color-mix(in srgb,var(--to-kirmizi) 60%,transparent)}70%{box-shadow:0 0 0 7px transparent}100%{box-shadow:0 0 0 0 transparent}}
  .to-normal-b{background:color-mix(in srgb,var(--sub) 14%,transparent);color:var(--sub2)}
  .to-sure{margin-left:auto;flex:none;font-size:11.5px;font-weight:700;border-radius:10px;padding:5px 10px;display:inline-flex;align-items:center;gap:5px;background:var(--card2);color:var(--sub2)}
  .to-sure .material-icons-outlined{font-size:15px}
  .to-sure.gec{background:color-mix(in srgb,var(--to-turuncu) 15%,transparent);color:#B45309}
  .to-sure.cokgec{background:color-mix(in srgb,var(--to-kirmizi) 14%,transparent);color:var(--to-kirmizi)}
  /* Kalemler */
  .to-kalemler{margin:14px 0 0;display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px}
  .to-kalem{display:flex;align-items:center;gap:10px;background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:9px 12px;min-width:0}
  .to-kalem .material-icons-outlined{font-size:18px;color:var(--dep);flex:none}
  .to-kalem .ad{flex:1;min-width:0;font-size:13px;font-weight:600;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .to-kalem .mk{flex:none;font-size:12px;font-weight:800;color:var(--dep);background:color-mix(in srgb,var(--dep) 12%,transparent);border-radius:8px;padding:3px 8px}
  .to-not{margin-top:12px;display:flex;gap:8px;align-items:flex-start;font-size:12.5px;color:var(--sub2);background:color-mix(in srgb,var(--to-turuncu) 9%,transparent);border:1px dashed color-mix(in srgb,var(--to-turuncu) 45%,transparent);border-radius:12px;padding:9px 12px}
  .to-not .material-icons-outlined{font-size:17px;color:var(--to-turuncu)}
  /* Butonlar */
  .to-btns{display:flex;gap:10px;margin-top:14px;flex-wrap:wrap}
  .to-b{border:0;border-radius:12px;padding:11px 20px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;color:#fff;font-family:inherit;transition:transform .12s,box-shadow .2s,filter .2s}
  .to-b:active{transform:scale(.97)}
  .to-b:disabled{opacity:.6;cursor:wait}
  .to-b .material-icons-outlined{font-size:18px}
  .to-onay{background:linear-gradient(135deg,#10B981,#059669);box-shadow:0 8px 18px -10px #059669}
  .to-onay:hover{filter:brightness(1.06);box-shadow:0 12px 22px -10px #059669}
  .to-red{background:transparent;color:var(--to-kirmizi);border:1.5px solid color-mix(in srgb,var(--to-kirmizi) 45%,transparent)}
  .to-red:hover{background:color-mix(in srgb,var(--to-kirmizi) 9%,transparent)}
  .to-red-dolu{background:linear-gradient(135deg,#F43F5E,#E11D48);color:#fff;border:0;box-shadow:0 8px 18px -10px #E11D48}
  .to-vaz{background:var(--card2);color:var(--ink);border:1px solid var(--line)}
  /* Red kutusu */
  .to-redkutu{display:none;margin-top:14px;border-top:1px dashed var(--line);padding-top:14px}
  .to-redkutu.acik{display:block;animation:toAc .2s ease}
  @keyframes toAc{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:none}}
  .to-redkutu label{font-size:12px;font-weight:700;color:var(--sub);display:block;margin-bottom:8px}
  .to-hazir{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
  .to-hazir button{border:1px solid var(--line);background:var(--card2);color:var(--sub2);border-radius:999px;padding:6px 12px;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
  .to-hazir button:hover{border-color:var(--to-kirmizi);color:var(--to-kirmizi)}
  .to-redkutu textarea{width:100%;border:1.5px solid var(--line);background:var(--card2);color:var(--ink);border-radius:12px;padding:10px 12px;font-size:13px;font-family:inherit;resize:vertical;min-height:64px}
  .to-redkutu textarea:focus{outline:none;border-color:var(--to-kirmizi)}
  .to-redkutu textarea.hata{border-color:var(--to-kirmizi);animation:toSalla .3s}
  @keyframes toSalla{25%{transform:translateX(-4px)}75%{transform:translateX(4px)}}
  .to-redfoot{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap}
  /* Boş durum */
  .to-bos{text-align:center;padding:56px 20px;background:var(--card);border:1px solid var(--line);border-radius:22px}
  .to-bos-ik{width:84px;height:84px;margin:0 auto 16px;border-radius:26px;display:grid;place-items:center;background:linear-gradient(135deg,color-mix(in srgb,#10B981 18%,transparent),color-mix(in srgb,#7C3AED 14%,transparent))}
  .to-bos-ik .material-icons-outlined{font-size:42px;color:#10B981}
  .to-bos.kilit .to-bos-ik .material-icons-outlined{color:var(--sub2)}
  .to-bos h3{margin:0 0 6px;font-size:17px;font-weight:800;color:var(--ink)}
  .to-bos p{margin:0 auto;font-size:13px;color:var(--sub);max-width:420px;line-height:1.5}
  .to-filtre-bos{display:none;text-align:center;padding:30px;color:var(--sub);font-size:13px}
  /* Toplu işlem çubuğu */
  .to-toplu{position:fixed;left:50%;bottom:22px;transform:translate(-50%,160%);z-index:60;background:#1E1B4B;color:#fff;border-radius:18px;padding:10px 10px 10px 18px;display:flex;align-items:center;gap:14px;box-shadow:0 18px 40px -12px rgba(30,27,75,.6);transition:transform .25s cubic-bezier(.2,.9,.3,1.2);max-width:calc(100vw - 32px)}
  .to-toplu.acik{transform:translate(-50%,0)}
  .to-toplu b{font-size:13.5px;white-space:nowrap}
  .to-toplu .to-b{padding:10px 16px}
  .to-toplu .to-vaz{background:rgba(255,255,255,.1);color:#fff;border-color:rgba(255,255,255,.18)}
  /* Onay popup (tarayıcı confirm yerine) */
  .to-pop{position:fixed;inset:0;z-index:9000;display:none;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.45);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px)}
  .to-pop.acik{display:flex;animation:toPopArka .18s ease}
  @keyframes toPopArka{from{opacity:0}to{opacity:1}}
  .to-pop-kutu{width:100%;max-width:420px;background:var(--card);border:1px solid var(--line);border-radius:24px;padding:26px 24px 20px;box-shadow:0 30px 70px -20px rgba(15,23,42,.55);text-align:center;animation:toPopKutu .25s cubic-bezier(.2,.9,.3,1.25)}
  @keyframes toPopKutu{from{opacity:0;transform:translateY(14px) scale(.96)}to{opacity:1;transform:none}}
  .to-pop-ik{width:64px;height:64px;margin:0 auto 14px;border-radius:20px;display:grid;place-items:center;background:color-mix(in srgb,var(--pr) 14%,transparent);color:var(--pr);position:relative}
  .to-pop-ik::after{content:'';position:absolute;inset:-6px;border-radius:24px;border:2px solid color-mix(in srgb,var(--pr) 22%,transparent)}
  .to-pop-ik .material-icons-outlined{font-size:32px}
  .to-pop h3{margin:0 0 6px;font-size:18px;font-weight:800;color:var(--ink)}
  .to-pop p{margin:0;font-size:13.5px;color:var(--sub);line-height:1.5}
  .to-pop-ozet{margin:14px 0 0;background:var(--card2);border:1px solid var(--line);border-radius:14px;padding:10px 12px;text-align:left;display:flex;flex-direction:column;gap:6px;max-height:180px;overflow:auto}
  .to-pop-ozet div{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--ink)}
  .to-pop-ozet div span:last-child{font-weight:800;color:var(--sub2);white-space:nowrap}
  .to-pop-ozet:empty{display:none}
  .to-pop-btn{display:flex;gap:10px;margin-top:20px}
  .to-pop-btn .to-b{flex:1;padding:12px 16px}
  /* Mobil */
  @media (max-width:860px){ .to-ozet{grid-template-columns:repeat(2,minmax(0,1fr))} }
  @media (max-width:560px){
    .to-ozet{gap:8px;margin-bottom:14px}
    .to-oz{padding:11px 12px;border-radius:15px;gap:10px}
    .to-oz-ik{width:34px;height:34px;border-radius:10px}
    .to-oz b{font-size:19px}
    .to-kart{padding:15px 14px 14px 18px;border-radius:18px}
    .to-ust{flex-wrap:wrap;gap:10px}
    .to-dep-ik{width:40px;height:40px}
    .to-sure{margin-left:0;order:5}
    .to-kalemler{grid-template-columns:1fr}
    .to-btns .to-b,.to-redfoot .to-b{flex:1}
    .to-toplu{left:16px;right:16px;bottom:14px;transform:translateY(160%);max-width:none;padding:10px 10px 10px 14px;gap:8px}
    .to-toplu.acik{transform:none}
    .to-toplu b{flex:1}
    .to-toplu .to-b{padding:10px 12px}
    .to-toplu .to-b span.yazi{display:none}
    .to-pop{align-items:flex-end;padding:0}
    .to-pop-kutu{max-width:none;border-radius:24px 24px 0 0;padding:24px 18px calc(18px + env(safe-area-inset-bottom));animation:toPopAlt .25s ease}
    @keyframes toPopAlt{from{transform:translateY(100%)}to{transform:none}}
  }
</style>
<div class="rk-body">
<div id="to">
  @if($yetkisiz ?? false)
    <div class="to-bos kilit">
      <div class="to-bos-ik"><span class="material-icons-outlined">lock</span></div>
      <h3>Onaylayabileceğin bir departman yok</h3>
      <p>Bu sayfa, bir departmanın <b>sorumlusu</b> olan personele (ve sahip/müdüre) ekibinin satın alma taleplerini onaylaması için gösterilir.</p>
    </div>
  @else
    <div class="to-ozet">
      <div class="to-oz" style="--oz:#7C3AED"><div class="to-oz-ik"><span class="material-icons-outlined">pending_actions</span></div><div><b id="toSayBek">{{ $talepler->count() }}</b><span class="l">Onay bekleyen</span></div></div>
      <div class="to-oz" style="--oz:#F43F5E"><div class="to-oz-ik"><span class="material-icons-outlined">priority_high</span></div><div><b id="toSayAcil">{{ $_acilSay }}</b><span class="l">Acil</span></div></div>
      <div class="to-oz" style="--oz:#0EA5E9"><div class="to-oz-ik"><span class="material-icons-outlined">inventory_2</span></div><div><b id="toSayKalem">{{ $_kalemSay }}</b><span class="l">Toplam kalem</span></div></div>
      <div class="to-oz" style="--oz:#F59E0B"><div class="to-oz-ik"><span class="material-icons-outlined">schedule</span></div><div><b>{{ $_enEski ? $_sure($_enEski)[0] : '—' }}</b><span class="l">En uzun bekleyen</span></div></div>
    </div>

    @if($talepler->isEmpty())
      <div class="to-bos">
        <div class="to-bos-ik"><span class="material-icons-outlined">task_alt</span></div>
        <h3>Her şey yolunda</h3>
        <p>Onayını bekleyen talep yok. Ekibinden yeni bir talep gelince burada görünecek.</p>
      </div>
    @else
      <div class="to-arac">
        <div class="to-filtre" id="toFiltre">
          <button class="to-chip aktif" data-f="hepsi" onclick="toFiltrele(this)">Tümü <i>{{ $talepler->count() }}</i></button>
          @if($_acilSay)<button class="to-chip" data-f="acil" onclick="toFiltrele(this)"><span class="material-icons-outlined" style="font-size:15px">priority_high</span>Acil <i>{{ $_acilSay }}</i></button>@endif
          @if($_depler->count() > 1)
            @foreach($_depler as $_d)
              <button class="to-chip" data-f="dep:{{ $_d }}" onclick="toFiltrele(this)">{{ $_d }} <i>{{ $talepler->where('departman', $_d)->count() }}</i></button>
            @endforeach
          @endif
        </div>
        @if($talepler->count() > 1)
          <label class="to-tumu"><input type="checkbox" id="toTumunuSec" onchange="toTumunuSec(this.checked)">Tümünü seç</label>
        @endif
      </div>

      <div class="to-liste" id="toListe">
        @foreach($talepler as $t)
          @php [$_ik, $_rn] = $_depStil($t->departman); [$_ss, $_dk] = $_sure($t->created_at); $_acil = $t->aciliyet === 'acil'; @endphp
          <div class="to-kart{{ $_acil ? ' acil' : '' }}" id="talep{{ $t->id }}" data-id="{{ $t->id }}" data-acil="{{ $_acil ? 1 : 0 }}" data-dep="{{ $t->departman }}" data-kalem="{{ count($t->kalemDetay ?? []) }}" style="--dep:{{ $_rn }}">
            <div class="to-ust">
              @if($talepler->count() > 1)<label class="to-sec"><input type="checkbox" class="to-secim" value="{{ $t->id }}" onchange="toSecimDegisti()"></label>@endif
              <div class="to-dep-ik"><span class="material-icons-outlined">{{ $_ik }}</span></div>
              <div class="to-bas">
                <div class="to-dep">{{ $t->departman ?: 'Departman' }}
                  <span class="to-badge {{ $_acil ? 'to-acil-b' : 'to-normal-b' }}">{{ $_acil ? 'ACİL' : 'Normal' }}</span>
                </div>
                <div class="to-alt">
                  <span class="to-kisi"><span class="to-av">{{ $_bas($t->isteyen_ad) }}</span>{{ $t->isteyen_ad ?: '—' }}</span>
                  <span>·</span>
                  <span>{{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->format('d.m.Y H:i') : '' }}</span>
                  <span>·</span>
                  <span>{{ count($t->kalemDetay ?? []) }} kalem</span>
                </div>
              </div>
              @if($_ss)<span class="to-sure{{ $_dk >= 1440 ? ' cokgec' : ($_dk >= 240 ? ' gec' : '') }}" title="Onay için bekleme süresi"><span class="material-icons-outlined">hourglass_top</span>{{ $_ss }}</span>@endif
            </div>

            <div class="to-kalemler">
              @forelse($t->kalemDetay ?? [] as $k)
                <div class="to-kalem" data-ad="{{ $k['ad'] }}" data-mk="{{ $k['miktar'] }} {{ $k['birim'] }}"><span class="material-icons-outlined">shopping_basket</span><span class="ad" title="{{ $k['ad'] }}">{{ $k['ad'] }}</span><span class="mk">{{ $k['miktar'] }} {{ $k['birim'] }}</span></div>
              @empty
                <div class="to-kalem"><span class="ad" style="color:var(--sub)">Kalem yok</span></div>
              @endforelse
            </div>
            @if($t->not)<div class="to-not"><span class="material-icons-outlined">sticky_note_2</span><span>{{ $t->not }}</span></div>@endif

            <div class="to-btns">
              <button class="to-b to-onay" onclick="toOnay({{ $t->id }}, this)"><span class="material-icons-outlined">check_circle</span>Onayla ve Satın Almaya Gönder</button>
              <button class="to-b to-red" onclick="toRedAc({{ $t->id }})"><span class="material-icons-outlined">close</span>Reddet</button>
            </div>
            <div class="to-redkutu" id="red{{ $t->id }}">
              <label>Red sebebi (zorunlu) — talebi açan bu sebebi görecek</label>
              <div class="to-hazir">
                @foreach(['Stokta yeterli var', 'Miktar fazla, düzeltip tekrar gönder', 'Bütçe uygun değil', 'Başka tedarikçiden alınacak'] as $_hs)
                  <button type="button" onclick="toHazir({{ $t->id }}, this)">{{ $_hs }}</button>
                @endforeach
              </div>
              <textarea id="sebep{{ $t->id }}" placeholder="Kısa bir açıklama yaz…"></textarea>
              <div class="to-redfoot">
                <button class="to-b to-red-dolu" onclick="toRed({{ $t->id }}, this)"><span class="material-icons-outlined">block</span>Reddet ve Kapat</button>
                <button class="to-b to-vaz" onclick="toRedKapat({{ $t->id }})">Vazgeç</button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
      <div class="to-filtre-bos" id="toFiltreBos">Bu filtrede talep yok.</div>

      <div class="to-toplu" id="toToplu">
        <b id="toTopluSay">0 talep seçildi</b>
        <button class="to-b to-vaz" onclick="toTumunuSec(false)"><span class="material-icons-outlined">close</span><span class="yazi">Temizle</span></button>
        <button class="to-b to-onay" id="toTopluBtn" onclick="toTopluOnay(this)"><span class="material-icons-outlined">done_all</span><span class="yazi">Seçilenleri </span>Onayla</button>
      </div>
    @endif
  @endif
</div>
</div>

{{-- Onay popup: tarayıcının confirm penceresi yerine --}}
<div class="to-pop" id="toPop" onclick="if(event.target===this)toPopKapat(false)">
  <div class="to-pop-kutu" role="dialog" aria-modal="true" aria-labelledby="toPopBaslik">
    <div class="to-pop-ik" id="toPopIk"><span class="material-icons-outlined" id="toPopIkon">task_alt</span></div>
    <h3 id="toPopBaslik"></h3>
    <p id="toPopMetin"></p>
    <div class="to-pop-ozet" id="toPopOzet"></div>
    <div class="to-pop-btn">
      <button class="to-b to-vaz" onclick="toPopKapat(false)">Vazgeç</button>
      <button class="to-b to-onay" id="toPopOk" onclick="toPopKapat(true)"><span class="material-icons-outlined">check_circle</span><span id="toPopOkYazi">Onayla</span></button>
    </div>
  </div>
</div>
<script>
  function toBilgi(m, tip){ if (window.rkBilgi) rkBilgi(m, tip); }
  function toKartlar(){ return Array.prototype.slice.call(document.querySelectorAll('.to-kart:not(.gidiyor)')); }
  function toEsc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

  // ---- Onay popup ----
  var _toPopCb = null;
  function toPop(o){
    var p = document.getElementById('toPop');
    p.querySelector('.to-pop-kutu').style.setProperty('--pr', o.renk || '#10B981');
    document.getElementById('toPopIkon').textContent = o.ikon || 'task_alt';
    document.getElementById('toPopBaslik').textContent = o.baslik || '';
    document.getElementById('toPopMetin').textContent = o.metin || '';
    document.getElementById('toPopOzet').innerHTML = (o.satirlar || []).map(function(s){ return '<div><span>' + toEsc(s[0]) + '</span><span>' + toEsc(s[1]) + '</span></div>'; }).join('');
    document.getElementById('toPopOkYazi').textContent = o.ok || 'Onayla';
    _toPopCb = o.cb; p.classList.add('acik');
    setTimeout(function(){ document.getElementById('toPopOk').focus(); }, 30);
  }
  function toPopKapat(evet){
    document.getElementById('toPop').classList.remove('acik');
    var cb = _toPopCb; _toPopCb = null;
    if (evet && cb) cb();
  }
  document.addEventListener('keydown', function(e){
    if (!document.getElementById('toPop').classList.contains('acik')) return;
    if (e.key === 'Escape') toPopKapat(false);
    if (e.key === 'Enter') { e.preventDefault(); toPopKapat(true); }
  });

  // ---- Filtre ----
  function toFiltrele(btn){
    document.querySelectorAll('#toFiltre .to-chip').forEach(function(c){ c.classList.toggle('aktif', c === btn); });
    toFiltreUygula();
  }
  function toFiltreUygula(){
    var akt = document.querySelector('#toFiltre .to-chip.aktif'), f = akt ? akt.dataset.f : 'hepsi', gor = 0;
    toKartlar().forEach(function(k){
      var ok = f === 'hepsi' || (f === 'acil' && k.dataset.acil === '1') || (f.indexOf('dep:') === 0 && k.dataset.dep === f.slice(4));
      k.style.display = ok ? '' : 'none'; if (ok) gor++;
    });
    var b = document.getElementById('toFiltreBos'); if (b) b.style.display = gor ? 'none' : 'block';
  }

  // ---- Reddet ----
  function toRedAc(id){ document.getElementById('red'+id).classList.add('acik'); setTimeout(function(){ document.getElementById('sebep'+id).focus(); }, 50); }
  function toRedKapat(id){ document.getElementById('red'+id).classList.remove('acik'); }
  function toHazir(id, btn){ var t = document.getElementById('sebep'+id); t.value = btn.textContent.trim(); t.classList.remove('hata'); t.focus(); }
  async function toRed(id, btn){
    var t = document.getElementById('sebep'+id), sebep = (t.value || '').trim();
    if (sebep.length < 3) { t.classList.remove('hata'); void t.offsetWidth; t.classList.add('hata'); t.focus(); toBilgi('Red sebebi zorunlu (en az 3 karakter).', 'wn'); return; }
    btn.disabled = true;
    try {
      var res = await api('/satinalma-talep/dept-reddet', {id: id, sebep: sebep});
      if (res.ok) { toKaldir(id, true); toBilgi('Talep reddedildi, talebi açana bildirildi.', 'ok'); }
      else { btn.disabled = false; toBilgi(res.mesaj || 'İşlem başarısız.', 'er'); }
    } catch (e) { btn.disabled = false; toBilgi('Bağlantı hatası.', 'er'); }
  }

  // ---- Onayla ----
  async function toOnayIstek(id){
    try { var res = await api('/satinalma-talep/dept-onayla', {id: id}); return res && res.ok ? true : (res && res.mesaj) || 'İşlem başarısız.'; }
    catch (e) { return 'Bağlantı hatası.'; }
  }
  function toOnay(id, btn){
    var k = document.getElementById('talep'+id);
    var satir = Array.prototype.slice.call(k.querySelectorAll('.to-kalem[data-ad]')).map(function(x){ return [x.dataset.ad, x.dataset.mk]; });
    toPop({ ikon: 'shopping_cart_checkout', renk: '#10B981', baslik: 'Talep onaylansın mı?', ok: 'Onayla',
      metin: (k.dataset.dep || 'Talep') + ' talebi onaylanıp satın almaya iletilecek.', satirlar: satir,
      cb: async function(){
        btn.disabled = true;
        var s = await toOnayIstek(id);
        if (s === true) { toKaldir(id, false); toBilgi('Talep onaylandı, satın almaya iletildi.', 'ok'); }
        else { btn.disabled = false; toBilgi(s, 'er'); }
      } });
  }

  // ---- Toplu seçim ----
  function toSecilenler(){ return Array.prototype.slice.call(document.querySelectorAll('.to-secim:checked')).map(function(c){ return +c.value; }); }
  function toSecimDegisti(){
    var s = toSecilenler(), bar = document.getElementById('toToplu'); if (!bar) return;
    document.querySelectorAll('.to-kart').forEach(function(k){ var c = k.querySelector('.to-secim'); k.classList.toggle('secili', !!(c && c.checked)); });
    document.getElementById('toTopluSay').textContent = s.length + ' talep seçildi';
    bar.classList.toggle('acik', s.length > 0);
    var t = document.getElementById('toTumunuSec'), top = document.querySelectorAll('.to-secim').length;
    if (t) { t.checked = s.length > 0 && s.length === top; t.indeterminate = s.length > 0 && s.length < top; }
  }
  function toTumunuSec(v){
    document.querySelectorAll('.to-kart').forEach(function(k){ var c = k.querySelector('.to-secim'); if (c) c.checked = v && k.style.display !== 'none'; });
    toSecimDegisti();
  }
  function toTopluOnay(btn){
    var ids = toSecilenler(); if (!ids.length) return;
    var satir = ids.map(function(id){ var k = document.getElementById('talep'+id); return [k ? k.dataset.dep : '#' + id, (k ? k.dataset.kalem : 0) + ' kalem']; });
    toPop({ ikon: 'done_all', renk: '#10B981', baslik: ids.length + ' talep onaylansın mı?', ok: 'Hepsini Onayla',
      metin: 'Seçilen talepler onaylanıp satın almaya iletilecek.', satirlar: satir,
      cb: async function(){
        btn.disabled = true; var hata = 0;
        for (var i = 0; i < ids.length; i++) { var s = await toOnayIstek(ids[i]); if (s === true) toKaldir(ids[i], false); else hata++; }
        btn.disabled = false;
        toBilgi(hata ? (ids.length - hata) + ' talep onaylandı, ' + hata + ' talepte hata oldu.' : ids.length + ' talep onaylandı, satın almaya iletildi.', hata ? 'wn' : 'ok');
      } });
  }

  // ---- Kart kaldırma + sayaçlar ----
  function toKaldir(id, red){
    var el = document.getElementById('talep'+id); if (!el) return;
    el.classList.add('gidiyor'); if (red) el.classList.add('red');
    var cb = el.querySelector('.to-secim'); if (cb) cb.checked = false;
    setTimeout(function(){ el.remove(); toSayacGuncelle(); toSecimDegisti(); toFiltreUygula(); toBosKontrol(); }, 380);
  }
  function toSayacGuncelle(){
    var k = toKartlar(), acil = k.filter(function(x){ return x.dataset.acil === '1'; }).length, kalem = k.reduce(function(s, x){ return s + (+x.dataset.kalem || 0); }, 0);
    var set = function(id, v){ var e = document.getElementById(id); if (e) e.textContent = v; };
    set('toSayBek', k.length); set('toSayAcil', acil); set('toSayKalem', kalem);
    document.querySelectorAll('#toFiltre .to-chip').forEach(function(c){
      var f = c.dataset.f, n = f === 'hepsi' ? k.length : (f === 'acil' ? acil : k.filter(function(x){ return x.dataset.dep === f.slice(4); }).length);
      var i = c.querySelector('i'); if (i) i.textContent = n;
      if (f !== 'hepsi' && n === 0) { if (c.classList.contains('aktif')) document.querySelector('#toFiltre .to-chip[data-f="hepsi"]').classList.add('aktif'); c.remove(); }
    });
  }
  function toBosKontrol(){
    if (toKartlar().length) return;
    var l = document.getElementById('toListe'); if (!l) return;
    ['toFiltreBos', 'toToplu'].forEach(function(id){ var e = document.getElementById(id); if (e) e.remove(); });
    var a = document.querySelector('.to-arac'); if (a) a.remove();
    l.outerHTML = '<div class="to-bos"><div class="to-bos-ik"><span class="material-icons-outlined">task_alt</span></div><h3>Hepsi tamam</h3><p>Onayını bekleyen talep kalmadı.</p></div>';
  }
</script>
@endsection

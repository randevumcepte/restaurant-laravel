@extends('layout.app')
@section('rk_ikon', 'event_note')
@section('baslik', 'Ajanda & Hatırlatıcı')
@section('title', 'Ajanda')

@push('head')
<style>
  #aj{width:100%;max-width:none;margin:0;
      --t-hatirlatma:#F59E0B;--t-randevu:#4F46E5;--t-toplanti:#7C3AED;--t-not:#10B981;--t-dogumgunu:#EC4899}
  .rk-body.aj-tam{padding:0} /* layout zaten .rk-body ile sarıyor; çift boşluk olmasın */
  #aj *{box-sizing:border-box}
  #aj .material-icons-outlined{vertical-align:middle}

  /* ===== HERO ===== */
  .aj-hero{position:relative;overflow:hidden;border-radius:24px;padding:26px 28px;color:#fff;margin-bottom:18px;
    background:radial-gradient(circle at 85% 20%,rgba(255,255,255,.18),transparent 40%),linear-gradient(130deg,#6D28D9 0%,#4F46E5 55%,#2563EB 100%);
    box-shadow:0 18px 40px -18px rgba(79,70,229,.65)}
  .aj-hero::before,.aj-hero::after{content:'';position:absolute;border-radius:50%;background:rgba(255,255,255,.08);pointer-events:none}
  .aj-hero::before{width:280px;height:280px;right:-80px;top:-120px}
  .aj-hero::after{width:180px;height:180px;right:180px;bottom:-110px}
  .aj-hero-ust{position:relative;display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap}
  .aj-tarih{font-size:12.5px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;opacity:.8}
  .aj-selam{font-size:26px;font-weight:800;margin:4px 0 6px;letter-spacing:-.4px;line-height:1.15}
  .aj-ozet{font-size:14px;opacity:.92;max-width:520px;line-height:1.45}
  .aj-ozet b{font-weight:800}
  .aj-saat{font-size:40px;font-weight:800;letter-spacing:-1px;line-height:1;font-variant-numeric:tabular-nums;text-align:right}
  .aj-saat small{display:block;font-size:12px;font-weight:600;opacity:.75;letter-spacing:0;margin-top:6px}
  .aj-stat-sira{position:relative;display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:20px}
  .aj-stat{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(6px);border-radius:16px;padding:12px 14px;cursor:pointer;transition:transform .15s,background .15s;text-align:left;color:#fff;font-family:inherit}
  .aj-stat:hover{transform:translateY(-2px);background:rgba(255,255,255,.2)}
  .aj-stat .s-ust{display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;opacity:.85;text-transform:uppercase;letter-spacing:.3px}
  .aj-stat .s-ust .material-icons-outlined{font-size:16px}
  .aj-stat .s-say{font-size:26px;font-weight:800;margin-top:4px;font-variant-numeric:tabular-nums}
  .aj-stat.uyari{background:rgba(244,63,94,.28);border-color:rgba(255,180,190,.45)}
  @media (max-width:760px){
    .aj-hero{padding:20px 18px;border-radius:20px}
    .aj-selam{font-size:21px}
    .aj-saat{display:none}
    .aj-stat-sira{grid-template-columns:repeat(2,1fr)}
  }

  /* ===== GRID ===== */
  .aj-grid{display:grid;grid-template-columns:clamp(340px,26vw,420px) minmax(0,1fr);gap:18px;align-items:start}
  @media (max-width:1050px){.aj-grid{grid-template-columns:minmax(0,1fr)}}
  .aj-grid>*,.aj-hero-ust>div{min-width:0}
  .aj-kart{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--golge);padding:20px}
  .aj-kart-bas{display:flex;align-items:center;gap:10px;margin-bottom:16px}
  .aj-kart-bas .ik{width:34px;height:34px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .aj-kart-bas .ik .material-icons-outlined{font-size:19px}
  .aj-kart-bas h3{font-size:15px;font-weight:800;color:var(--ink);margin:0}
  .aj-kart-bas .alt{font-size:11.5px;color:var(--sub);font-weight:600}
  .aj-sol{position:sticky;top:84px}
  @media (max-width:1050px){.aj-sol{position:static}}

  /* ===== FORM ===== */
  .aj-tipler{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;background:var(--card2);padding:5px;border-radius:14px}
  .aj-tip{display:flex;flex-direction:column;align-items:center;gap:3px;padding:9px 2px 7px;border-radius:10px;font-size:10.5px;font-weight:700;color:var(--sub);cursor:pointer;transition:all .15s;border:0;background:transparent;font-family:inherit}
  .aj-tip .material-icons-outlined{font-size:20px}
  .aj-tip:hover{color:var(--tc)}
  .aj-tip.sec{background:var(--card);color:var(--tc);box-shadow:0 3px 10px -3px color-mix(in srgb,var(--tc) 45%,transparent)}
  .aj-lbl{display:flex;align-items:center;justify-content:space-between;font-size:11.5px;font-weight:800;color:var(--sub2);margin:16px 0 7px;text-transform:uppercase;letter-spacing:.35px}
  .aj-inp{width:100%;padding:11px 13px;border:1.5px solid var(--line);border-radius:12px;font-size:14px;background:var(--card);color:var(--ink);font-family:inherit;transition:border-color .15s,box-shadow .15s}
  .aj-inp:focus{outline:none;border-color:var(--mor1);box-shadow:0 0 0 4px color-mix(in srgb,var(--mor1) 14%,transparent)}
  .aj-inp.buyuk{font-size:15.5px;font-weight:600;padding:13px 14px}
  textarea.aj-inp{resize:vertical;min-height:62px}
  .aj-cipler{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px}
  .aj-cip{padding:6px 11px;border-radius:999px;border:1.5px solid var(--line);background:var(--card);color:var(--sub2);font-size:12px;font-weight:700;cursor:pointer;transition:all .13s;font-family:inherit;user-select:none}
  .aj-cip:hover{border-color:color-mix(in srgb,var(--mor1) 50%,var(--line));color:var(--mor1)}
  .aj-cip.sec{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .aj-cip input{display:none}
  .aj-zaman-sira{display:grid;grid-template-columns:1.25fr 1fr;gap:8px}
  .aj-onizle{display:flex;align-items:center;gap:8px;margin-top:16px;padding:10px 12px;border-radius:12px;font-size:12.5px;font-weight:600;color:var(--sub2);background:color-mix(in srgb,var(--tc,var(--mor1)) 8%,var(--card2));border:1px dashed color-mix(in srgb,var(--tc,var(--mor1)) 35%,var(--line))}
  .aj-onizle .material-icons-outlined{font-size:18px;color:var(--tc,var(--mor1))}
  .aj-switch{display:flex;align-items:center;gap:10px;margin-top:14px;cursor:pointer;font-size:12.5px;font-weight:600;color:var(--sub2)}
  .aj-switch input{display:none}
  .aj-switch .tr{width:38px;height:22px;border-radius:999px;background:var(--line);position:relative;transition:background .15s;flex:none}
  .aj-switch .tr::after{content:'';position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s}
  .aj-switch input:checked + .tr{background:var(--mor1)}
  .aj-switch input:checked + .tr::after{left:19px}
  .aj-kaydet{width:100%;margin-top:18px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:14px;padding:14px;font-size:14.5px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;font-family:inherit;box-shadow:0 10px 22px -10px var(--mor1);transition:transform .12s,box-shadow .12s}
  .aj-kaydet:hover{transform:translateY(-1px);box-shadow:0 14px 26px -10px var(--mor1)}
  .aj-kaydet kbd{font-family:inherit;font-size:10.5px;font-weight:700;background:rgba(255,255,255,.2);padding:2px 6px;border-radius:6px;margin-left:4px}
  @media (max-width:760px){.aj-kaydet kbd{display:none}}

  /* ===== GÜN ŞERİDİ ===== */
  .aj-serit{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 6px;scrollbar-width:thin;margin-bottom:14px}
  .aj-gun{flex:1 0 58px;border-radius:16px;padding:9px 0 8px;text-align:center;cursor:pointer;border:1.5px solid var(--line);background:var(--card);transition:all .15s;font-family:inherit;color:var(--ink)}
  .aj-gun:hover{border-color:color-mix(in srgb,var(--mor1) 45%,var(--line));transform:translateY(-1px)}
  .aj-gun .g-ad{font-size:10.5px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.3px}
  .aj-gun .g-no{font-size:19px;font-weight:800;margin:2px 0 4px}
  .aj-gun .g-nok{display:flex;justify-content:center;gap:3px;height:6px}
  .aj-gun .g-nok i{width:6px;height:6px;border-radius:50%;background:var(--mor1);display:block}
  .aj-gun.bugun{border-color:var(--mor1)}
  .aj-gun.bugun .g-ad{color:var(--mor1)}
  .aj-gun.hafta-sonu .g-ad{color:var(--kirmizi)}
  .aj-gun.sec{background:linear-gradient(160deg,var(--mor1),var(--mavi));border-color:transparent;color:#fff;box-shadow:0 8px 18px -8px var(--mor1)}
  .aj-gun.sec .g-ad{color:rgba(255,255,255,.85)}
  .aj-gun.sec .g-nok i{background:#fff}

  /* ===== ARAÇ ÇUBUĞU ===== */
  .aj-arac{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:6px}
  .aj-filtre{display:flex;gap:4px;background:var(--card2);padding:4px;border-radius:12px;overflow-x:auto;max-width:100%}
  .aj-f{padding:7px 12px;border-radius:9px;border:0;background:transparent;font-size:12.5px;font-weight:700;color:var(--sub);cursor:pointer;white-space:nowrap;font-family:inherit;display:flex;align-items:center;gap:5px}
  .aj-f .n{font-size:10.5px;min-width:18px;padding:1px 5px;border-radius:999px;background:color-mix(in srgb,var(--sub) 18%,transparent)}
  .aj-f.sec{background:var(--card);color:var(--mor1);box-shadow:0 2px 6px rgba(0,0,0,.07)}
  .aj-f.sec .n{background:color-mix(in srgb,var(--mor1) 15%,transparent)}
  .aj-f.kirmizi .n{background:var(--kirmizi);color:#fff}
  .aj-ara{flex:1;min-width:170px;position:relative}
  .aj-ara .material-icons-outlined{position:absolute;left:11px;top:50%;transform:translateY(-50%);font-size:19px;color:var(--sub)}
  .aj-ara input{width:100%;padding:9px 12px 9px 37px;border:1.5px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font-size:13.5px;font-family:inherit}
  .aj-ara input:focus{outline:none;border-color:var(--mor1)}

  /* ===== ZAMAN ÇİZELGESİ ===== */
  .aj-grup{margin-top:18px}
  .aj-grup-bas{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
  .aj-grup-bas::after{content:'';flex:1;height:1px;background:var(--line)}
  .aj-grup-bas .say{font-size:10.5px;padding:1px 7px;border-radius:999px;background:var(--card2);color:var(--sub)}
  .aj-grup.gecikmis .aj-grup-bas{color:var(--kirmizi)}
  .aj-ogeler{position:relative;padding-left:18px}
  .aj-ogeler::before{content:'';position:absolute;left:5px;top:10px;bottom:10px;width:2px;border-radius:2px;background:linear-gradient(180deg,var(--line),transparent)}
  .aj-oge{position:relative;display:flex;align-items:center;gap:12px;padding:12px 14px;margin-bottom:8px;border-radius:16px;background:var(--card);border:1px solid var(--line);transition:transform .15s,box-shadow .15s,opacity .3s,max-height .3s,margin .3s,padding .3s;max-height:240px}
  .aj-oge::before{content:'';position:absolute;left:-18px;top:50%;width:12px;height:12px;margin-top:-6px;border-radius:50%;background:var(--card);border:3px solid var(--tc)}
  .aj-oge:hover{transform:translateX(3px);box-shadow:0 8px 20px -12px rgba(30,41,59,.35)}
  .aj-oge.gecikti{border-color:color-mix(in srgb,var(--kirmizi) 35%,var(--line));background:color-mix(in srgb,var(--kirmizi) 4%,var(--card))}
  .aj-oge.cikiyor{opacity:0;transform:translateX(30px);max-height:0;margin-bottom:0;padding-top:0;padding-bottom:0;border-width:0}
  .aj-saatk{flex:none;width:56px;text-align:center}
  .aj-saatk .sa{font-size:16px;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  .aj-saatk .gs{font-size:10.5px;font-weight:700;color:var(--sub)}
  .aj-tic{width:40px;height:40px;border-radius:13px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--tc) 14%,transparent);color:var(--tc)}
  .aj-tic .material-icons-outlined{font-size:21px}
  .aj-govde{flex:1;min-width:0}
  .aj-govde .bs{font-weight:700;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .aj-govde .ac{font-size:12px;color:var(--sub);margin-top:2px;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
  .aj-etiketler{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px}
  .aj-et{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--card2);color:var(--sub2)}
  .aj-et .material-icons-outlined{font-size:13px}
  .aj-et.geri{background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .aj-et.yakin{background:color-mix(in srgb,var(--amber) 18%,transparent);color:#B45309}
  .aj-et.gec{background:color-mix(in srgb,var(--kirmizi) 14%,transparent);color:var(--kirmizi)}
  .aj-et.genel{background:color-mix(in srgb,var(--mavi) 12%,transparent);color:var(--mavi)}
  body.koyu .aj-et.yakin{color:var(--amber)}
  .aj-eylem{display:flex;gap:6px;flex:none}
  .aj-bx{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;cursor:pointer;border:1px solid var(--line);background:var(--card);color:var(--sub);transition:all .13s;padding:0}
  .aj-bx .material-icons-outlined{font-size:19px}
  .aj-bx.ok:hover{background:var(--yesil);border-color:var(--yesil);color:#fff}
  .aj-bx.sil:hover{background:var(--kirmizi);border-color:var(--kirmizi);color:#fff}
  .aj-kilit{color:var(--sub);opacity:.6;font-size:18px}
  @media (max-width:560px){
    .aj-oge{flex-wrap:wrap;padding:12px}
    .aj-saatk{width:auto;text-align:left;display:flex;gap:6px;align-items:baseline}
    .aj-govde{flex-basis:calc(100% - 56px)}
    .aj-eylem{margin-left:auto}
  }

  /* ===== NOT PANOSU ===== */
  .aj-notlar{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
  .aj-not{position:relative;border-radius:16px;padding:14px 14px 12px;background:color-mix(in srgb,var(--tc) 10%,var(--card));border:1px solid color-mix(in srgb,var(--tc) 25%,var(--line));transition:transform .15s,opacity .3s}
  .aj-not:hover{transform:rotate(-.6deg) translateY(-2px)}
  .aj-not.cikiyor{opacity:0;transform:scale(.9)}
  .aj-not .bs{font-weight:800;font-size:13.5px;color:var(--ink);padding-right:66px}
  .aj-not .ac{font-size:12.5px;color:var(--sub2);margin-top:6px;white-space:pre-line;line-height:1.45}
  .aj-not .aj-eylem{position:absolute;top:9px;right:9px;gap:4px}
  .aj-not .aj-bx{width:28px;height:28px;border-radius:9px}
  .aj-not .aj-bx .material-icons-outlined{font-size:16px}

  /* ===== BOŞ / GEÇMİŞ ===== */
  .aj-bos{text-align:center;padding:40px 20px;color:var(--sub)}
  .aj-bos .bi{width:74px;height:74px;margin:0 auto 14px;border-radius:24px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 16%,transparent),color-mix(in srgb,var(--mavi) 10%,transparent));color:var(--mor1)}
  .aj-bos .bi .material-icons-outlined{font-size:36px}
  .aj-bos b{display:block;color:var(--ink);font-size:15px;margin-bottom:4px}
  .aj-bos span{font-size:13px}
  .aj-gecmis{margin-top:20px;border-top:1px solid var(--line);padding-top:6px}
  .aj-gecmis summary{cursor:pointer;list-style:none;display:flex;align-items:center;gap:8px;padding:10px 0;font-size:12px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.4px}
  .aj-gecmis summary::-webkit-details-marker{display:none}
  .aj-gecmis summary .ok-ik{transition:transform .2s}
  .aj-gecmis[open] summary .ok-ik{transform:rotate(180deg)}
  .aj-gs{display:flex;align-items:center;gap:10px;padding:9px 4px;font-size:13px;color:var(--sub);border-bottom:1px dashed var(--line)}
  .aj-gs:last-child{border-bottom:0}
  .aj-gs .t{flex:1;text-decoration:line-through;opacity:.8;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .aj-gs button{border:0;background:none;color:var(--mor1);font-weight:700;font-size:12px;cursor:pointer;display:flex;align-items:center;gap:3px;font-family:inherit}

  /* ===== TOAST ===== */
  .aj-toast{position:fixed;left:50%;bottom:26px;transform:translate(-50%,30px);opacity:0;z-index:9999;display:flex;align-items:center;gap:9px;padding:12px 18px;border-radius:14px;background:#1E293B;color:#fff;font-size:13.5px;font-weight:600;box-shadow:0 14px 30px -10px rgba(0,0,0,.45);transition:all .25s;pointer-events:none}
  .aj-toast.goster{transform:translate(-50%,0);opacity:1}
  .aj-toast .material-icons-outlined{font-size:20px}
  .aj-toast.ok .material-icons-outlined{color:#34D399}
  .aj-toast.err .material-icons-outlined{color:#FB7185}
  #aj .aj-gizli{display:none !important}
</style>
@endpush

@section('content')
<div class="rk-body aj-tam">
<div id="aj">
  @php
    $rol = $personel->rol ?? '';
    $patronMu = in_array($rol, ['sahip', 'mudur']);
    $benimId = (int) ($personel->id ?? 0);
    $tipler = [
      'hatirlatma' => ['alarm', 'Hatırlatma'],
      'randevu'    => ['event', 'Randevu'],
      'toplanti'   => ['groups', 'Toplantı'],
      'not'        => ['sticky_note_2', 'Not'],
      'dogumgunu'  => ['cake', 'Doğum Günü'],
    ];
    $gunAd = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
    $ayAd = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $tz = 'Europe/Istanbul'; // app UTC ama kayıtlar TR saatiyle girilir
    $simdi = now($tz);
    $bugun = $simdi->copy()->startOfDay();
    $ilkAd = trim(explode(' ', trim((string) ($personel->ad ?? '')))[0] ?? '');

    // Zamanlı kayıtlar gün gruplarına, zamansızlar not panosuna
    $gruplar = []; $notlar = [];
    foreach ($liste as $a) {
      if (!$a->baslar_at) { $notlar[] = $a; continue; }
      $t = \Carbon\Carbon::parse($a->baslar_at, $tz);
      if ($t->lt($simdi)) { $k = 'gecikmis'; $bas = 'Gecikmiş'; }
      else {
        $fark = (int) round($bugun->diffInDays($t->copy()->startOfDay(), false));
        $k = $t->toDateString();
        $bas = $fark === 0 ? 'Bugün' : ($fark === 1 ? 'Yarın' : $gunAd[$t->dayOfWeek] . ', ' . $t->day . ' ' . $ayAd[$t->month]);
      }
      $gruplar[$k] = $gruplar[$k] ?? ['bas' => $bas, 'ogeler' => []];
      $gruplar[$k]['ogeler'][] = $a;
    }
    // Personel yalnız kendi kaydını değiştirir (_aiAjandaDuzenleSorgu ile aynı kural)
    $duzenler = function ($a) use ($patronMu, $benimId) { return $patronMu || (int) $a->personel_id === $benimId; };
  @endphp

  {{-- ===== HERO ===== --}}
  <div class="aj-hero">
    <div class="aj-hero-ust">
      <div>
        <div class="aj-tarih">{{ $gunAd[$simdi->dayOfWeek] }} · {{ $simdi->day }} {{ $ayAd[$simdi->month] }} {{ $simdi->year }}</div>
        <div class="aj-selam"><span id="ajSelam">Merhaba</span>{{ $ilkAd ? ', ' . $ilkAd : '' }} 👋</div>
        <div class="aj-ozet" id="ajOzet">&nbsp;</div>
      </div>
      <div class="aj-saat"><span id="ajSaat">{{ $simdi->format('H:i') }}</span><small>yerel saat</small></div>
    </div>
    <div class="aj-stat-sira">
      <button type="button" class="aj-stat" onclick="ajFiltre('bugun')"><div class="s-ust"><span class="material-icons-outlined">today</span> Bugün</div><div class="s-say" id="sBugun">0</div></button>
      <button type="button" class="aj-stat" onclick="ajFiltre('yarin')"><div class="s-ust"><span class="material-icons-outlined">wb_twilight</span> Yarın</div><div class="s-say" id="sYarin">0</div></button>
      <button type="button" class="aj-stat" onclick="ajFiltre('hafta')"><div class="s-ust"><span class="material-icons-outlined">date_range</span> 7 Gün</div><div class="s-say" id="sHafta">0</div></button>
      <button type="button" class="aj-stat" id="sGecKart" onclick="ajFiltre('gecikmis')"><div class="s-ust"><span class="material-icons-outlined">error_outline</span> Gecikmiş</div><div class="s-say" id="sGec">0</div></button>
    </div>
  </div>

  <div class="aj-grid">
    {{-- ===== EKLEME FORMU ===== --}}
    <div class="aj-sol">
    <div class="aj-kart" id="ajFormKart" style="--tc:var(--t-hatirlatma)">
      <div class="aj-kart-bas">
        <div class="ik"><span class="material-icons-outlined">add_task</span></div>
        <div><h3>Yeni Ekle</h3><div class="alt">Hızlıca planla, zamanı gelince hatırlatalım</div></div>
      </div>
      <form method="POST" action="/ajanda/ekle" id="ajForm">
        @csrf
        <div class="aj-tipler">
          @foreach ($tipler as $k => $t)
            <button type="button" class="aj-tip {{ $k === 'hatirlatma' ? 'sec' : '' }}" data-tip="{{ $k }}" style="--tc:var(--t-{{ $k }})">
              <span class="material-icons-outlined">{{ $t[0] }}</span>{{ $t[1] }}
            </button>
          @endforeach
        </div>
        <input type="hidden" name="tip" id="ajTip" value="hatirlatma">

        <label class="aj-lbl">Başlık</label>
        <input class="aj-inp buyuk" name="baslik" id="ajBaslik" placeholder="Örn: Tedarikçi toplantısı, doktor randevusu…" required maxlength="220" autocomplete="off">

        <div id="ajZaman">
          <label class="aj-lbl">Ne zaman?</label>
          <div class="aj-cipler" id="ajGunCip">
            <button type="button" class="aj-cip sec" data-gun="0">Bugün</button>
            <button type="button" class="aj-cip" data-gun="1">Yarın</button>
            <button type="button" class="aj-cip" data-gun="2">Öbür gün</button>
            <button type="button" class="aj-cip" data-gun="7">1 hafta sonra</button>
          </div>
          <div class="aj-zaman-sira">
            <input class="aj-inp" type="date" name="tarih" id="ajTarih" value="{{ $simdi->toDateString() }}">
            <input class="aj-inp" type="time" name="saat" id="ajSaatInp" value="09:00">
          </div>
          <div class="aj-cipler" id="ajSaatCip" style="margin-top:8px">
            @foreach (['09:00', '12:00', '14:00', '17:00', '19:00'] as $s)
              <button type="button" class="aj-cip {{ $s === '09:00' ? 'sec' : '' }}" data-saat="{{ $s }}">{{ $s }}</button>
            @endforeach
          </div>

          <label class="aj-lbl">Hatırlatma</label>
          <div class="aj-cipler" id="ajHatCip">
            @foreach ([0 => 'Yok', 15 => '15 dk', 30 => '30 dk', 60 => '1 saat', 120 => '2 saat', 1440 => '1 gün'] as $v => $l)
              <label class="aj-cip {{ $v === 30 ? 'sec' : '' }}"><input type="radio" name="hatirlat_dk" value="{{ $v }}" {{ $v === 30 ? 'checked' : '' }}>{{ $l }}</label>
            @endforeach
          </div>
        </div>

        <label class="aj-lbl">Açıklama <span style="text-transform:none;font-weight:600;color:var(--sub)">opsiyonel</span></label>
        <textarea class="aj-inp" name="aciklama" rows="2" placeholder="Detay, adres, telefon… Örn: Eşimin doğum günü, gül almayı unutma 🌹"></textarea>

        @if ($patronMu)
        <label class="aj-switch"><input type="checkbox" name="isletme" value="1" id="ajIsletme"><span class="tr"></span> İşletme geneli — tüm ekip görsün</label>
        @endif

        <div class="aj-onizle"><span class="material-icons-outlined">notifications_active</span><span id="ajOnizleMetin"></span></div>

        <button class="aj-kaydet" type="submit"><span class="material-icons-outlined" style="font-size:20px">add</span> Ajandaya Ekle <kbd>Ctrl ↵</kbd></button>
      </form>
    </div>
    </div>

    {{-- ===== LİSTE ===== --}}
    <div class="aj-kart">
      <div class="aj-serit" id="ajSerit"></div>

      <div class="aj-arac">
        <div class="aj-filtre" id="ajFiltreler">
          <button type="button" class="aj-f sec" data-f="tumu">Tümü <span class="n" data-n="tumu">0</span></button>
          <button type="button" class="aj-f" data-f="bugun">Bugün <span class="n" data-n="bugun">0</span></button>
          <button type="button" class="aj-f" data-f="hafta">7 Gün <span class="n" data-n="hafta">0</span></button>
          <button type="button" class="aj-f" data-f="gecikmis" id="fGec">Gecikmiş <span class="n" data-n="gecikmis">0</span></button>
          <button type="button" class="aj-f" data-f="not">Notlar <span class="n" data-n="not">0</span></button>
        </div>
        <div class="aj-ara"><span class="material-icons-outlined">search</span><input type="search" id="ajAra" placeholder="Ajandada ara…"></div>
      </div>

      <div id="ajListe">
        @foreach ($gruplar as $gk => $g)
          <div class="aj-grup {{ $gk === 'gecikmis' ? 'gecikmis' : '' }}">
            <div class="aj-grup-bas">
              @if ($gk === 'gecikmis')<span class="material-icons-outlined" style="font-size:16px">warning_amber</span>@endif
              {{ $g['bas'] }} <span class="say">{{ count($g['ogeler']) }}</span>
            </div>
            <div class="aj-ogeler">
              @foreach ($g['ogeler'] as $a)
                @php $t = \Carbon\Carbon::parse($a->baslar_at, $tz); $tk = isset($tipler[$a->tip]) ? $a->tip : 'randevu'; $tp = $tipler[$tk]; @endphp
                <div class="aj-oge {{ $gk === 'gecikmis' ? 'gecikti' : '' }}" style="--tc:var(--t-{{ $tk }})"
                     data-id="{{ $a->id }}" data-ts="{{ $t->timestamp }}" data-gun="{{ $t->toDateString() }}" data-tip="{{ $a->tip }}"
                     data-ara="{{ mb_strtolower($a->baslik . ' ' . $a->aciklama) }}">
                  <div class="aj-saatk"><div class="sa">{{ $t->format('H:i') }}</div><div class="gs">{{ $t->day }} {{ mb_substr($ayAd[$t->month], 0, 3) }}</div></div>
                  <div class="aj-tic"><span class="material-icons-outlined">{{ $tp[0] }}</span></div>
                  <div class="aj-govde">
                    <div class="bs" title="{{ $a->baslik }}">{{ $a->baslik }}</div>
                    @if ($a->aciklama)<div class="ac">{{ $a->aciklama }}</div>@endif
                    <div class="aj-etiketler">
                      <span class="aj-et geri" data-geri><span class="material-icons-outlined">schedule</span><span></span></span>
                      <span class="aj-et">{{ $tp[1] }}</span>
                      @if ((int) $a->hatirlat_dk > 0)<span class="aj-et"><span class="material-icons-outlined">notifications</span>{{ $a->hatirlat_dk >= 1440 ? intdiv($a->hatirlat_dk, 1440) . ' gün' : ($a->hatirlat_dk >= 60 ? intdiv($a->hatirlat_dk, 60) . ' sa' : $a->hatirlat_dk . ' dk') }} önce</span>@endif
                      @if (is_null($a->personel_id))<span class="aj-et genel"><span class="material-icons-outlined">campaign</span>İşletme geneli</span>@endif
                    </div>
                  </div>
                  <div class="aj-eylem">
                    @if ($duzenler($a))
                      <button type="button" class="aj-bx ok" title="Tamamlandı" onclick="ajIslem(this,'durum')"><span class="material-icons-outlined">check</span></button>
                      <button type="button" class="aj-bx sil" title="Sil" onclick="ajIslem(this,'sil')"><span class="material-icons-outlined">delete_outline</span></button>
                    @else
                      <span class="material-icons-outlined aj-kilit" title="İşletme geneli kayıt — yalnız yönetici değiştirebilir">lock</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach

        @if (count($notlar))
          <div class="aj-grup">
            <div class="aj-grup-bas"><span class="material-icons-outlined" style="font-size:16px">sticky_note_2</span> Not Panosu <span class="say">{{ count($notlar) }}</span></div>
            <div class="aj-notlar">
              @foreach ($notlar as $a)
                <div class="aj-not aj-oge-not" style="--tc:var(--t-{{ isset($tipler[$a->tip]) ? $a->tip : 'not' }})"
                     data-id="{{ $a->id }}" data-tip="not" data-ara="{{ mb_strtolower($a->baslik . ' ' . $a->aciklama) }}">
                  <div class="bs">{{ $a->baslik }}</div>
                  @if ($a->aciklama)<div class="ac">{{ $a->aciklama }}</div>@endif
                  @if (is_null($a->personel_id))<div class="aj-etiketler"><span class="aj-et genel"><span class="material-icons-outlined">campaign</span>İşletme geneli</span></div>@endif
                  <div class="aj-eylem">
                    @if ($duzenler($a))
                      <button type="button" class="aj-bx ok" title="Tamamlandı" onclick="ajIslem(this,'durum')"><span class="material-icons-outlined">check</span></button>
                      <button type="button" class="aj-bx sil" title="Sil" onclick="ajIslem(this,'sil')"><span class="material-icons-outlined">delete_outline</span></button>
                    @else
                      <span class="material-icons-outlined aj-kilit" title="Yalnız yönetici değiştirebilir">lock</span>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      <div class="aj-bos {{ count($liste) ? 'aj-gizli' : '' }}" id="ajBos">
        <div class="bi"><span class="material-icons-outlined">event_available</span></div>
        <b id="ajBosBas">Ajandan tertemiz ✨</b>
        <span id="ajBosAlt">Soldan bir hatırlatma, randevu ya da not ekleyerek başla.</span>
      </div>

      @if (count($gecmis))
      <details class="aj-gecmis">
        <summary><span class="material-icons-outlined" style="font-size:17px">history</span> Geçmiş ({{ count($gecmis) }}) <span class="material-icons-outlined ok-ik" style="font-size:18px;margin-left:auto">expand_more</span></summary>
        @foreach ($gecmis as $g)
          <div class="aj-gs">
            <span class="material-icons-outlined" style="font-size:17px;color:{{ $g->durum === 'tamam' ? 'var(--yesil)' : 'var(--sub)' }}">{{ $g->durum === 'tamam' ? 'check_circle' : 'cancel' }}</span>
            <span class="t" title="{{ $g->baslik }}">{{ $g->baslik }}</span>
            <span style="font-size:11px">{{ \Carbon\Carbon::parse($g->updated_at)->setTimezone($tz)->format('d.m H:i') }}</span>
            @if ($duzenler($g))
              <button type="button" onclick="ajGeriAl({{ (int) $g->id }})"><span class="material-icons-outlined" style="font-size:15px">undo</span>Geri al</button>
            @endif
          </div>
        @endforeach
      </details>
      @endif
    </div>
  </div>
</div>
</div>
<div class="aj-toast" id="ajToast"><span class="material-icons-outlined"></span><span></span></div>

@push('scripts')
<script>
(function(){
  var GUN_KISA = ['Paz','Pzt','Sal','Çar','Per','Cum','Cmt'];
  var AY_KISA = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
  var TIP_AD = {hatirlatma:'Hatırlatma',randevu:'Randevu',toplanti:'Toplantı',not:'Not',dogumgunu:'Doğum günü'};
  var aktifFiltre = 'tumu', aktifGun = null;

  function ymd(d){ return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
  function gunEkle(n){ var d = new Date(); d.setHours(0,0,0,0); d.setDate(d.getDate()+n); return d; }
  function ogeler(){ return Array.prototype.slice.call(document.querySelectorAll('#ajListe .aj-oge, #ajListe .aj-oge-not')); }
  function esc(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  function ajToast(msj, tur){
    var t = document.getElementById('ajToast');
    t.className = 'aj-toast ' + (tur || 'ok');
    t.children[0].textContent = tur === 'err' ? 'error_outline' : 'check_circle';
    t.children[1].textContent = msj;
    requestAnimationFrame(function(){ t.classList.add('goster'); });
    clearTimeout(t._z); t._z = setTimeout(function(){ t.classList.remove('goster'); }, 2600);
  }

  // ---- Göreli zaman ("2 sa sonra", "45 dk geçti") ----
  function goreli(ts){
    var fark = Math.round((ts*1000 - Date.now())/60000), m = Math.abs(fark), s;
    if (m < 1) return {metin:'şimdi', fark:0};
    if (m < 60) s = m + ' dk';
    else if (m < 1440) s = Math.floor(m/60) + ' sa' + (m%60 && m < 600 ? ' ' + (m%60) + ' dk' : '');
    else s = Math.round(m/1440) + ' gün';
    return {metin: fark >= 0 ? s + ' sonra' : s + ' geçti', fark: fark};
  }
  function saatGuncelle(){
    document.querySelectorAll('#ajListe .aj-oge').forEach(function(el){
      var g = goreli(+el.dataset.ts), et = el.querySelector('[data-geri]');
      if (!et) return;
      et.lastElementChild.textContent = g.metin;
      et.className = 'aj-et ' + (g.fark < 0 ? 'gec' : (g.fark <= 60 ? 'yakin' : 'geri'));
    });
    var n = new Date(), h = n.getHours();
    document.getElementById('ajSaat').textContent = String(h).padStart(2,'0') + ':' + String(n.getMinutes()).padStart(2,'0');
    document.getElementById('ajSelam').textContent = h < 5 ? 'İyi geceler' : h < 12 ? 'Günaydın' : h < 18 ? 'İyi günler' : 'İyi akşamlar';
  }

  // ---- Sayaçlar + özet + gün şeridi ----
  function sinif(el){
    if (!el.dataset.ts) return {not:1};
    var ts = +el.dataset.ts * 1000, g = el.dataset.gun;
    return {gec: ts < Date.now(), bugun: g === ymd(gunEkle(0)), yarin: g === ymd(gunEkle(1)), hafta: ts >= Date.now() && ts < gunEkle(7).getTime()};
  }
  function sayaclar(){
    var c = {tumu:0,bugun:0,yarin:0,hafta:0,gecikmis:0,not:0}, sirada = null, gunSay = {};
    ogeler().forEach(function(el){
      c.tumu++;
      var s = sinif(el);
      if (s.not){ c.not++; return; }
      if (s.gec) c.gecikmis++;
      if (s.bugun && !s.gec) c.bugun++;
      if (s.yarin) c.yarin++;
      if (s.hafta) c.hafta++;
      gunSay[el.dataset.gun] = (gunSay[el.dataset.gun] || 0) + 1;
      if (!s.gec && (!sirada || +el.dataset.ts < +sirada.dataset.ts)) sirada = el;
    });
    document.querySelectorAll('[data-n]').forEach(function(n){ n.textContent = c[n.dataset.n]; });
    document.getElementById('sBugun').textContent = c.bugun;
    document.getElementById('sYarin').textContent = c.yarin;
    document.getElementById('sHafta').textContent = c.hafta;
    document.getElementById('sGec').textContent = c.gecikmis;
    document.getElementById('sGecKart').classList.toggle('uyari', c.gecikmis > 0);
    document.getElementById('fGec').classList.toggle('kirmizi', c.gecikmis > 0);

    var oz;
    if (!c.tumu) oz = 'Ajandan boş. Aklındakini hemen ekle, zamanı gelince <b>hatırlatalım</b>.';
    else {
      oz = c.bugun ? 'Bugün <b>' + c.bugun + ' işin</b> var.' : 'Bugün için planlanmış iş yok.';
      if (sirada) oz += ' Sıradaki: <b>' + esc(sirada.querySelector('.bs').textContent) + '</b> — ' + goreli(+sirada.dataset.ts).metin + '.';
      if (c.gecikmis) oz += ' <b>' + c.gecikmis + ' gecikmiş</b> kayıt seni bekliyor.';
    }
    document.getElementById('ajOzet').innerHTML = oz;
    serit(gunSay);
  }
  function serit(gunSay){
    var h = '';
    for (var i = 0; i < 14; i++){
      var d = gunEkle(i), k = ymd(d), n = gunSay[k] || 0, nok = '';
      for (var j = 0; j < Math.min(n,3); j++) nok += '<i></i>';
      h += '<button type="button" class="aj-gun' + (i===0?' bugun':'') + (d.getDay()===0||d.getDay()===6?' hafta-sonu':'') + (aktifGun===k?' sec':'') + '" data-g="' + k + '" title="' + n + ' kayıt">'
        + '<div class="g-ad">' + (i===0 ? 'Bugün' : GUN_KISA[d.getDay()]) + '</div><div class="g-no">' + d.getDate() + '</div><div class="g-nok">' + nok + '</div></button>';
    }
    document.getElementById('ajSerit').innerHTML = h;
  }

  // ---- Filtre + arama ----
  function uygula(){
    var q = (document.getElementById('ajAra').value || '').toLocaleLowerCase('tr').trim(), gorunen = 0, toplam = 0;
    ogeler().forEach(function(el){
      toplam++;
      var s = sinif(el), ok;
      if (aktifGun) ok = el.dataset.gun === aktifGun;
      else ok = aktifFiltre === 'tumu' || (aktifFiltre === 'not' && s.not) || (aktifFiltre === 'gecikmis' && s.gec)
             || (aktifFiltre === 'bugun' && s.bugun) || (aktifFiltre === 'yarin' && s.yarin) || (aktifFiltre === 'hafta' && s.hafta);
      if (ok && q) ok = (el.dataset.ara || '').indexOf(q) !== -1;
      el.classList.toggle('aj-gizli', !ok);
      if (ok) gorunen++;
    });
    document.querySelectorAll('#ajListe .aj-grup').forEach(function(g){
      var gor = g.querySelectorAll('.aj-oge:not(.aj-gizli), .aj-oge-not:not(.aj-gizli)').length;
      g.classList.toggle('aj-gizli', !gor);
      g.querySelector('.say').textContent = gor;
    });
    var bos = document.getElementById('ajBos');
    bos.classList.toggle('aj-gizli', gorunen > 0);
    document.getElementById('ajBosBas').textContent = toplam ? 'Burada kayıt yok' : 'Ajandan tertemiz ✨';
    document.getElementById('ajBosAlt').textContent = !toplam ? 'Soldan bir hatırlatma, randevu ya da not ekleyerek başla.'
      : (q ? '“' + q + '” için sonuç bulunamadı.' : 'Bu filtrede gösterilecek bir şey yok.');
  }
  function filtreSec(f){
    aktifFiltre = f; aktifGun = null;
    document.querySelectorAll('.aj-f').forEach(function(b){ b.classList.toggle('sec', b.dataset.f === f); });
    document.querySelectorAll('.aj-gun').forEach(function(b){ b.classList.remove('sec'); });
    uygula();
  }
  window.ajFiltre = function(f){
    filtreSec(f);
    if (window.innerWidth < 1050) document.getElementById('ajSerit').scrollIntoView({behavior:'smooth', block:'start'});
  };
  document.getElementById('ajFiltreler').addEventListener('click', function(e){
    var b = e.target.closest('.aj-f'); if (b) filtreSec(b.dataset.f);
  });
  document.getElementById('ajSerit').addEventListener('click', function(e){
    var b = e.target.closest('.aj-gun'); if (!b) return;
    aktifGun = aktifGun === b.dataset.g ? null : b.dataset.g;
    document.querySelectorAll('.aj-gun').forEach(function(x){ x.classList.toggle('sec', x.dataset.g === aktifGun); });
    document.querySelectorAll('.aj-f').forEach(function(x){ x.classList.toggle('sec', !aktifGun && x.dataset.f === aktifFiltre); });
    if (aktifGun) tarihAyarla(aktifGun); // seçili güne hızlı ekleme
    uygula();
  });
  document.getElementById('ajAra').addEventListener('input', uygula);

  // ---- Bitti / Sil (sayfa yenilemeden) ----
  function gonder(url, veri){
    var fd = new FormData();
    fd.append('_token', document.querySelector('meta[name=csrf-token]').content);
    Object.keys(veri).forEach(function(k){ fd.append(k, veri[k]); });
    return fetch(url, {method:'POST', body:fd, credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
      .then(function(r){
        if (!r.ok) throw new Error(r.status);
        return (r.headers.get('content-type') || '').indexOf('json') !== -1 ? r.json() : {ok:1};
      });
  }
  window.ajIslem = function(btn, tur){
    var el = btn.closest('[data-id]'), id = el.dataset.id;
    if (tur === 'sil' && !confirm('Bu kayıt silinsin mi?')) return;
    btn.disabled = true;
    gonder(tur === 'sil' ? '/ajanda/sil' : '/ajanda/durum', tur === 'sil' ? {id:id} : {id:id, durum:'tamam'})
      .then(function(j){
        if (j && j.ok === 0) throw new Error('yetki');
        el.classList.add('cikiyor');
        setTimeout(function(){ el.remove(); sayaclar(); uygula(); }, 300);
        ajToast(tur === 'sil' ? 'Silindi' : 'Harika, tamamlandı! 🎉');
      })
      .catch(function(){ btn.disabled = false; ajToast('İşlem yapılamadı', 'err'); });
  };
  window.ajGeriAl = function(id){
    gonder('/ajanda/durum', {id:id, durum:'acik'}).then(function(){ location.reload(); }).catch(function(){ ajToast('Geri alınamadı', 'err'); });
  };

  // ---- Form: tip, hızlı tarih/saat, hatırlatma, önizleme ----
  var form = document.getElementById('ajForm'), tarihInp = document.getElementById('ajTarih'), saatInp = document.getElementById('ajSaatInp'), isl = document.getElementById('ajIsletme');
  window.ajTip = function(el){
    document.querySelectorAll('.aj-tip').forEach(function(t){ t.classList.remove('sec'); });
    el.classList.add('sec');
    document.getElementById('ajTip').value = el.dataset.tip;
    document.getElementById('ajZaman').style.display = (el.dataset.tip === 'not') ? 'none' : 'block';
    document.getElementById('ajFormKart').style.setProperty('--tc', 'var(--t-' + el.dataset.tip + ')');
    onizle();
  };
  // Inline onclick="ajTip()" formdaki id="ajTip" input'una çözülüyordu (form named access) → dinleyiciyle bağla
  document.querySelector('.aj-tipler').addEventListener('click', function(e){
    var b = e.target.closest('.aj-tip'); if (b) window.ajTip(b);
  });
  function tarihAyarla(k){
    tarihInp.value = k;
    document.querySelectorAll('#ajGunCip .aj-cip').forEach(function(c){ c.classList.toggle('sec', ymd(gunEkle(+c.dataset.gun)) === k); });
    onizle();
  }
  function saatCipSenk(){ document.querySelectorAll('#ajSaatCip .aj-cip').forEach(function(c){ c.classList.toggle('sec', c.dataset.saat === saatInp.value); }); }
  document.getElementById('ajGunCip').addEventListener('click', function(e){
    var c = e.target.closest('.aj-cip'); if (c) tarihAyarla(ymd(gunEkle(+c.dataset.gun)));
  });
  document.getElementById('ajSaatCip').addEventListener('click', function(e){
    var c = e.target.closest('.aj-cip'); if (!c) return;
    saatInp.value = c.dataset.saat; saatCipSenk(); onizle();
  });
  tarihInp.addEventListener('change', function(){ tarihAyarla(tarihInp.value); });
  saatInp.addEventListener('input', function(){ saatCipSenk(); onizle(); });
  document.getElementById('ajHatCip').addEventListener('change', function(){
    document.querySelectorAll('#ajHatCip .aj-cip').forEach(function(l){ l.classList.toggle('sec', l.querySelector('input').checked); });
    onizle();
  });
  if (isl) isl.addEventListener('change', onizle);

  function onizle(){
    var tip = document.getElementById('ajTip').value, m;
    if (tip === 'not') m = 'Not panosuna eklenecek — zaman ve hatırlatma yok.';
    else {
      var p = (tarihInp.value || '').split('-'), d = new Date(+p[0], +p[1]-1, +p[2]), fark = Math.round((d - gunEkle(0))/86400000);
      var gunMetin = isNaN(fark) ? '' : fark === 0 ? 'Bugün' : fark === 1 ? 'Yarın' : fark === -1 ? 'Dün' : d.getDate() + ' ' + AY_KISA[d.getMonth()] + ' ' + GUN_KISA[d.getDay()];
      var hat = form.querySelector('input[name=hatirlat_dk]:checked'), hv = hat ? +hat.value : 0;
      var hm = hv === 0 ? 'hatırlatma yok' : (hv >= 1440 ? '1 gün' : hv >= 60 ? (hv/60) + ' saat' : hv + ' dk') + ' önce bildirim';
      m = TIP_AD[tip] + ' · ' + gunMetin + ' ' + (saatInp.value || '09:00') + ' · ' + hm;
      if (fark < 0) m += ' (geçmiş tarih!)';
    }
    if (isl && isl.checked) m += ' · tüm ekip görür';
    document.getElementById('ajOnizleMetin').textContent = m;
  }

  form.addEventListener('keydown', function(e){
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter'){ e.preventDefault(); if (form.reportValidity()) form.submit(); }
  });

  // ---- Başlat ----
  tarihAyarla(ymd(gunEkle(0))); // varsayılan tarih: cihazın bugünü
  sayaclar(); uygula(); saatGuncelle();
  setInterval(function(){ saatGuncelle(); sayaclar(); uygula(); }, 30000);
  @if (session('ok')) ajToast({!! json_encode(session('ok'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}); @endif
  @if (session('hata')) ajToast({!! json_encode(session('hata'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}, 'err'); @endif
})();
</script>
@endpush
@endsection

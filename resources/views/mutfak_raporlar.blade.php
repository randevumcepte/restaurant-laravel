@php
  // Mutfak Raporları kategori sayfası: sol menüde "Mutfak Raporları" altındaki her kalem buraya {kat} ile gelir (satinalma_raporlar ile aynı tasarım)
  [$katAd, $katIk, $katAcik, $katRenk] = $kategoriler[$kat];
@endphp
@extends('layout.app')
@section('title', $katAd . ' · Mutfak Raporları')
@section('rk_ikon', $katIk)
@section('baslik', $katAd)
@section('rk_alt', 'Mutfak · Raporlar & Analizler')

@section('content')
@php
  $durum = $durum ?? []; $ai = $ai ?? null;
  $modAd = ['satis' => 'Satış', 'kds' => 'KDS süreleri', 'basla' => 'KDS başla', 'servis' => 'Servis zamanı', 'hazirlayan' => 'Hazırlayan aşçı', 'recete' => 'Reçete', 'tuketim' => 'Reçeteli tüketim',
    'fire' => 'Fire', 'sayim' => 'Sayım', 'parti' => 'Lot / SKT', 'uretim' => 'Yarı mamül üretimi', 'defter' => 'Kontrol Defteri', 'sicaklik' => 'Sıcaklık kaydı', 'kalite' => 'Kalite kaydı',
    'iptal' => 'İptal / ikram', 'mesai' => 'Mesai', 'talep' => 'Satın alma talebi', 'tukendi' => 'Tükendi geçmişi', 'fatura' => 'Alış faturası'];
  // Kullanılmayan modülün nereden girileceği (gri çip tıklanınca o ekran açılır)
  $modLink = ['satis' => ['/pos', 'POS / adisyon'], 'kds' => ['/mutfak', 'Mutfak / KDS Ekranı (Başla / Hazır)'], 'basla' => ['/mutfak', 'Mutfak / KDS Ekranı'], 'servis' => ['/mutfak', 'KDS / garson ekranında servis edildi'],
    'hazirlayan' => ['/mutfak', 'KDS\'ye kendi PIN\'inle girip Başla / Hazır'], 'recete' => ['/recete', 'Reçeteler / Yarı Mamül'], 'tuketim' => ['/recete', 'Reçeteli satış'], 'fire' => ['/stok', 'Stok > malzeme > Fire'],
    'sayim' => ['/sayim', 'Sayım (Envanter)'], 'parti' => ['/parti-takip', 'Mal kabulde SKT girerek'], 'uretim' => ['/recete', 'Yarı mamül "Üret"'], 'defter' => ['/mutfak-defteri', 'Kontrol Defteri'],
    'sicaklik' => ['/mutfak-defteri', 'Kontrol Defteri > Sıcaklık'], 'kalite' => ['/kalite-kayit', 'Kalite / HACCP'], 'iptal' => [null, 'POS\'ta iptal / ikram'], 'mesai' => [null, 'QR ile mesai'],
    'talep' => ['/satinalma-talep', 'Satın Alma Talebi'], 'tukendi' => ['/mutfak', 'KDS > tükendi (86)'], 'fatura' => ['/mal-kabul', 'Mal Kabul / İrsaliye']];
  $donemler = ['bugun' => 'Bugün', 'dun' => 'Dün', 'bu_hafta' => 'Bu hafta', 'gecen_hafta' => 'Geçen hafta', 'bu_ay' => 'Bu ay', 'gecen_ay' => 'Geçen ay', 'son30' => 'Son 30 gün', 'son90' => 'Son 90 gün', 'bu_yil' => 'Bu yıl'];
  $donemQs = http_build_query(array_filter(['donem' => $dn['tip'], 'bas' => $dn['tip'] === 'ozel' ? $dn['bas'] : null, 'bit' => $dn['tip'] === 'ozel' ? $dn['bit'] : null]));
  $periyot = ['gunluk' => ['dun' => 'Dün', 'bugun' => 'Bugün'], 'haftalik' => ['gecen_hafta' => 'Geçen hafta', 'bu_hafta' => 'Bu hafta'], 'aylik' => ['gecen_ay' => 'Geçen ay', 'bu_ay' => 'Bu ay']];
  $veriRozet = function ($gerekli) use ($durum, $modAd) {
      $eksik = array_values(array_filter($gerekli, fn($m) => empty($durum[$m])));
      if (!$eksik) return ['Veri hazır', '#059669', 'check_circle', ''];
      $ad = implode(', ', array_map(fn($m) => $modAd[$m] ?? $m, $eksik));
      if (count($eksik) === count($gerekli)) return ['Veri yok', '#DC2626', 'error', $ad];
      return ['Kısmi veri', '#D97706', 'warning', $ad];
  };
  // Bu kategorinin raporları (üst sekmeler + görüntüleyici)
  $liste = [];
  if ($kat !== 'ai') {
      $gerekliTum = [];
      foreach ($katalog as $rk => [$k, $ad, $acik, $ik, $renk, $gerekli, $vars]) {
          if ($k !== $kat) continue;
          $gerekliTum = array_merge($gerekliTum, $gerekli);
          $liste[] = ['k' => $rk, 'ad' => $ad, 'acik' => $acik, 'ik' => $ik, 'renk' => $renk, 'roz' => $veriRozet($gerekli), 'per' => $periyot[$rk] ?? null,
              'url' => '/mutfak-raporlar/indir?rapor=' . $rk];
      }
  } else $gerekliTum = ['satis', 'kds', 'recete', 'fire', 'defter', 'iptal'];
  $gerekliTum = array_values(array_unique($gerekliTum));
  $sayfaDonemli = $kat !== 'donemsel';
@endphp

<style>
  .sr{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;--kr:{{ $katRenk }}}
  .sr [hidden]{display:none!important}
  /* ---- rapor sekmeleri (üstte) ---- */
  .sr-sek{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
  @media(max-width:700px){.sr-sek{grid-auto-flow:column;grid-template-columns:none;grid-auto-columns:78%;overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:4px}.sr-sek .sr-r{scroll-snap-align:start}}
  .sr-r{position:relative;display:flex;gap:11px;align-items:flex-start;text-align:left;width:100%;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:13px 14px;cursor:pointer;transition:border-color .15s,box-shadow .15s,transform .15s;font:inherit;color:inherit;overflow:hidden}
  .sr-r::after{content:'';position:absolute;left:0;right:0;bottom:0;height:3px;background:var(--c);transform:scaleX(0);transform-origin:left;transition:transform .2s}
  .sr-r:hover{border-color:color-mix(in srgb,var(--c) 45%,transparent);transform:translateY(-2px);box-shadow:0 12px 26px -18px var(--c)}
  .sr-r.aktif{border-color:var(--c);background:linear-gradient(160deg,color-mix(in srgb,var(--c) 9%,var(--card)),var(--card) 70%);box-shadow:0 14px 30px -18px var(--c)}
  .sr-r.aktif::after{transform:scaleX(1)}
  .sr-r .ik{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;flex:0 0 auto;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent);transition:background .15s,color .15s}
  .sr-r.aktif .ik{color:#fff;background:linear-gradient(135deg,color-mix(in srgb,var(--c) 75%,#fff),var(--c))}
  .sr-r .ik .material-icons-outlined{font-size:21px}
  .sr-r .t{font-weight:800;font-size:13.5px;color:var(--ink);line-height:1.25;display:flex;align-items:center;gap:6px}
  .sr-r .d{font-size:11.5px;color:var(--sub);line-height:1.4;margin-top:3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .sr-nok{width:8px;height:8px;border-radius:50%;background:var(--n);flex:0 0 auto;box-shadow:0 0 0 3px color-mix(in srgb,var(--n) 18%,transparent)}
  /* ---- görüntüleyici ---- */
  .sr-gor{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--golge);overflow:hidden;display:flex;flex-direction:column;min-width:0}
  .sr-gor-bas{position:relative;display:flex;gap:14px;align-items:center;padding:16px 18px;flex-wrap:wrap}
  .sr-gor-bas::before{content:'';position:absolute;inset:0;background:radial-gradient(70% 160% at 0% 0%,color-mix(in srgb,var(--c,var(--kr)) 13%,transparent),transparent 65%);pointer-events:none}
  .sr-gor-bas > *{position:relative}
  .sr-gor-bas .ik{width:46px;height:46px;border-radius:13px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,color-mix(in srgb,var(--c) 72%,#fff),var(--c));box-shadow:0 10px 22px -12px var(--c);flex:0 0 auto}
  .sr-gor-bas .ik .material-icons-outlined{font-size:24px}
  .sr-gor-bas h3{margin:0;font-size:17px;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .sr-gor-bas .d{font-size:12.5px;color:var(--sub2);margin-top:3px;max-width:760px;line-height:1.45}
  .sr-roz{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;color:var(--c);background:color-mix(in srgb,var(--c) 10%,transparent);border:1px solid color-mix(in srgb,var(--c) 30%,transparent);border-radius:99px;padding:2px 8px}
  .sr-roz .material-icons-outlined{font-size:13px}
  .sr-eksik{font-size:11.5px;color:#B45309;margin-top:4px}
  body.koyu .sr-eksik{color:#FBBF24}
  .sr-arac{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto}
  .sr-per{display:inline-flex;background:var(--card2);border:1px solid var(--line);border-radius:11px;padding:3px}
  .sr-per button{border:0;background:none;color:var(--sub2);border-radius:8px;padding:7px 12px;font-size:12.5px;font-weight:800;cursor:pointer}
  .sr-per button.aktif{background:var(--c);color:#fff;box-shadow:0 4px 10px -4px var(--c)}
  .sr-b{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:9px 14px;border-radius:11px;font-size:12.5px;font-weight:800;text-decoration:none;border:1px solid;cursor:pointer;background:none;white-space:nowrap}
  .sr-b .material-icons-outlined{font-size:17px}
  .sr-b.x{color:#fff;border-color:#16A34A;background:linear-gradient(135deg,#22C55E,#15803D)}
  .sr-b.p{color:#fff;border-color:#E11D48;background:linear-gradient(135deg,#F43F5E,#BE123C)}
  .sr-b.o{color:var(--sub2);border-color:var(--line);background:var(--card2)}
  .sr-b.x:hover,.sr-b.p:hover{filter:brightness(1.07)}
  /* ---- araç satırı: dönem + kullanılan veri ---- */
  .sr-alt{display:flex;flex-wrap:wrap;gap:8px 14px;align-items:center;padding:10px 18px;border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:var(--card2)}
  .sr-alt .grp{display:flex;flex-wrap:wrap;gap:5px;align-items:center}
  .sr-alt .lbl{font-size:10.5px;font-weight:800;color:var(--sub);letter-spacing:.4px;text-transform:uppercase;margin-right:3px}
  .sr-alt .ayr{width:1px;align-self:stretch;background:var(--line)}
  .sr-chip{display:inline-flex;align-items:center;padding:5px 10px;border-radius:99px;font-size:12px;font-weight:700;color:var(--sub2);background:var(--card);border:1px solid var(--line);text-decoration:none;white-space:nowrap}
  .sr-chip:hover{border-color:var(--kr);color:var(--kr)}
  .sr-chip.aktif{background:var(--kr);border-color:var(--kr);color:#fff}
  .sr-ozel{display:inline-flex;gap:4px;align-items:center;padding:2px 3px 2px 9px;border-radius:99px;background:var(--card);border:1px solid var(--line);font-size:12px;font-weight:700;color:var(--sub2)}
  .sr-ozel input{background:var(--card2);border:1px solid var(--line);border-radius:7px;padding:2px 4px;font-size:12px;color:var(--ink)}
  .sr-ozel button{border:0;border-radius:99px;padding:5px 10px;font-weight:800;background:var(--kr);color:#fff;cursor:pointer;font-size:12px}
  .sr-etiket{font-size:11.5px;color:var(--sub)}
  .sr-etiket b{color:var(--ink)}
  .sr-m{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:99px;font-size:11.5px;font-weight:700;border:1px solid;text-decoration:none}
  .sr-m .material-icons-outlined{font-size:14px}
  .sr-m.var{color:#047857;border-color:color-mix(in srgb,#059669 35%,transparent);background:color-mix(in srgb,#059669 8%,transparent)}
  .sr-m.yok{color:var(--sub);border-color:var(--line);background:var(--card);border-style:dashed}
  a.sr-m.yok:hover{color:var(--kr);border-color:var(--kr)}
  body.koyu .sr-m.var{color:#34D399}
  /* ---- önizleme: raporun tam boyu, iç kaydırma yok ---- */
  .sr-cer{position:relative;min-height:520px;background:#F1F2F9}
  .sr-cer iframe{display:block;width:100%;height:900px;border:0;background:transparent}
  .sr-yuk{position:absolute;inset:0;display:grid;place-items:start center;padding-top:120px;color:var(--sub);font-size:13px;font-weight:700;pointer-events:none;transition:opacity .2s;background:color-mix(in srgb,#F1F2F9 70%,transparent)}
  .sr-yuk .material-icons-outlined{animation:srDon 1s linear infinite;font-size:30px;color:var(--kr)}
  @keyframes srDon{to{transform:rotate(360deg)}}
  /* ---- AI ---- */
  .sr-ai{border-radius:18px;padding:18px;background:linear-gradient(135deg,color-mix(in srgb,#9333EA 12%,var(--card)),color-mix(in srgb,#EC4899 8%,var(--card)));border:1px solid color-mix(in srgb,#9333EA 30%,transparent)}
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
  /* ---- AI sayfası (v2) ---- */
  .ai2-hero{position:relative;overflow:hidden;border-radius:24px;padding:24px 24px 16px;color:#fff;background:linear-gradient(125deg,#1E1B4B 0%,#4C1D95 45%,#86198F 80%,#BE185D 100%);box-shadow:0 24px 50px -28px #6D28D9}
  .ai2-orb{position:absolute;border-radius:50%;filter:blur(40px);opacity:.55;pointer-events:none;animation:ai2Yuz 9s ease-in-out infinite alternate}
  .ai2-orb.a{width:260px;height:260px;background:#A855F7;top:-90px;right:12%}
  .ai2-orb.b{width:200px;height:200px;background:#EC4899;bottom:-110px;left:30%;animation-delay:-4s}
  @keyframes ai2Yuz{to{transform:translate(40px,25px) scale(1.15)}}
  .ai2-hero > *{position:relative}
  .ai2-hero > .ai2-orb{position:absolute}
  .ai2-hero-ust{display:flex;gap:18px;align-items:center;flex-wrap:wrap}
  .ai2-logo{width:60px;height:60px;border-radius:18px;display:grid;place-items:center;flex:0 0 auto;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(6px);box-shadow:0 0 0 6px rgba(255,255,255,.05),0 0 40px rgba(236,72,153,.5)}
  .ai2-logo .material-icons-outlined{font-size:32px;animation:ai2Parla 2.6s ease-in-out infinite}
  @keyframes ai2Parla{50%{transform:scale(1.12) rotate(12deg);filter:drop-shadow(0 0 8px #fff)}}
  .ai2-hero-yazi{flex:1;min-width:240px}
  .ai2-ust-etiket{font-size:11px;font-weight:800;letter-spacing:.8px;text-transform:uppercase;opacity:.75}
  .ai2-hero h2{margin:4px 0 6px;font-size:24px;font-weight:900;line-height:1.2;letter-spacing:-.3px;color:#fff}
  .ai2-hero p{margin:0;font-size:13.5px;line-height:1.55;opacity:.88;max-width:720px;color:#fff}
  .ai2-cta{display:flex;flex-direction:column;align-items:flex-end;gap:8px}
  .ai2-btn{display:inline-flex;align-items:center;gap:9px;padding:14px 24px;border-radius:15px;border:0;cursor:pointer;font:inherit;font-weight:900;font-size:15px;color:#4C1D95;background:#fff;box-shadow:0 12px 30px -10px rgba(0,0,0,.45),0 0 0 4px rgba(255,255,255,.15);transition:transform .15s,box-shadow .15s}
  .ai2-btn:hover{transform:translateY(-2px);box-shadow:0 16px 34px -10px rgba(0,0,0,.5),0 0 0 6px rgba(255,255,255,.2)}
  .ai2-btn .material-icons-outlined{font-size:21px;color:#C026D3}
  .ai2-btn[disabled]{cursor:wait;opacity:.92;transform:none}
  .ai2-btn[disabled] .material-icons-outlined{animation:srDon 1.1s linear infinite}
  .ai2-hak{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;opacity:.85}
  .ai2-hak .material-icons-outlined{font-size:15px;color:#FDE047}
  .ai2-donem{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid rgba(255,255,255,.15)}
  .ai2-donem .lbl{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;opacity:.75;margin-right:4px}
  .ai2-donem .lbl .material-icons-outlined{font-size:16px}
  .ai2-chip{padding:6px 12px;border-radius:99px;font-size:12px;font-weight:700;color:#fff;text-decoration:none;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);white-space:nowrap;transition:background .15s}
  .ai2-chip:hover{background:rgba(255,255,255,.2);color:#fff}
  .ai2-chip.aktif{background:#fff;color:#5B21B6;border-color:#fff;box-shadow:0 6px 16px -6px rgba(0,0,0,.4)}
  .ai2-ozel{display:inline-flex;align-items:center;flex-wrap:wrap;gap:4px;padding:3px 3px 3px 11px;border-radius:99px;font-size:12px;font-weight:700;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18)}
  .ai2-ozel.aktif{border-color:#fff}
  .ai2-ozel input[type=date]{background:rgba(255,255,255,.92);border:0;border-radius:8px;padding:3px 5px;font-size:12px;color:#1E1B4B;max-width:132px}
  .ai2-ozel button{border:0;border-radius:99px;padding:5px 11px;font-weight:800;font-size:12px;cursor:pointer;background:#fff;color:#5B21B6}
  .ai2-donem-et{font-size:11.5px;opacity:.8;margin-left:auto}
  .ai2-bolum-bas{display:flex;align-items:center;gap:7px;flex-wrap:wrap;font-size:14px;font-weight:900;color:var(--ink);margin:0 0 10px}
  .ai2-bolum-bas > .material-icons-outlined{font-size:20px;color:#9333EA}
  .ai2-bolum-bas small{font-size:11.5px;font-weight:600;color:var(--sub)}
  .ai2-kpi-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:10px}
  .ai2-kpi{position:relative;display:flex;flex-direction:column;gap:3px;padding:14px 15px;border-radius:16px;background:var(--card);border:1px solid var(--line);text-decoration:none;overflow:hidden;min-width:0;transition:transform .15s,border-color .15s,box-shadow .15s}
  .ai2-kpi::before{content:'';position:absolute;inset:0 0 auto 0;height:3px;background:var(--c)}
  .ai2-kpi:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--c) 45%,transparent);box-shadow:0 14px 28px -18px var(--c)}
  .ai2-kpi .ik{position:absolute;top:12px;right:12px;width:34px;height:34px;border-radius:10px;display:grid;place-items:center;font-size:19px;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent)}
  .ai2-kpi .e{font-size:11.5px;font-weight:700;color:var(--sub);padding-right:40px}
  .ai2-kpi b{font-size:22px;font-weight:900;color:var(--ink);letter-spacing:-.4px;line-height:1.2}
  .ai2-kpi .alt{display:flex;align-items:center;gap:3px;font-size:11.5px;font-weight:600;color:var(--sub2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .ai2-kpi .alt .material-icons-outlined{font-size:15px}
  .ai2-kpi .alt.kirmizi{color:#DC2626}.ai2-kpi .alt.yesil{color:#059669}.ai2-kpi .alt.turuncu{color:#D97706}
  .ai2-izgara{display:grid;grid-template-columns:minmax(0,1.7fr) minmax(0,1fr);gap:14px;align-items:start}
  @media(max-width:1100px){.ai2-izgara{grid-template-columns:minmax(0,1fr)}}
  .ai2-panel{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:16px;box-shadow:var(--golge);min-width:0}
  .ai2-panel-bas{display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:space-between;margin-bottom:12px}
  .ai2-panel-arac{display:flex;gap:6px;flex-wrap:wrap}
  .ai2-mini{display:inline-flex;align-items:center;gap:4px;padding:6px 10px;border-radius:9px;font:inherit;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none;color:var(--sub2);background:var(--card2);border:1px solid var(--line)}
  .ai2-mini .material-icons-outlined{font-size:16px}
  .ai2-mini:hover{border-color:#9333EA;color:#9333EA}
  .ai2-mini.x{color:#15803D}.ai2-mini.p{color:#BE123C}
  .ai2-bos{text-align:center;padding:18px 8px 6px}
  .ai2-bos-ik{width:64px;height:64px;margin:0 auto 10px;border-radius:20px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899);box-shadow:0 14px 30px -14px #9333EA}
  .ai2-bos-ik .material-icons-outlined{font-size:32px}
  .ai2-bos h4{margin:0 0 4px;font-size:16px;font-weight:900;color:var(--ink)}
  .ai2-bos p{margin:0 auto 14px;font-size:12.5px;color:var(--sub2);max-width:460px}
  .ai2-bos-g{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,150px),1fr));gap:8px;text-align:left}
  .ai2-bos-g > div{padding:11px;border-radius:13px;border:1px dashed color-mix(in srgb,var(--c) 40%,var(--line));background:color-mix(in srgb,var(--c) 5%,transparent);font-size:11.5px;color:var(--sub2)}
  .ai2-bos-g .material-icons-outlined{display:block;font-size:20px;color:var(--c);margin-bottom:4px}
  .ai2-bos-g b{display:block;font-size:12.5px;color:var(--ink)}
  .ai2-adim{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:#9333EA;margin:6px 0 14px}
  .ai2-adim .material-icons-outlined{animation:srDon 1.1s linear infinite;font-size:20px}
  .ai2-isk{height:12px;border-radius:7px;margin-bottom:9px;background:linear-gradient(90deg,var(--card2) 25%,color-mix(in srgb,#9333EA 14%,var(--card2)) 50%,var(--card2) 75%);background-size:300% 100%;animation:ai2Isk 1.4s linear infinite}
  @keyframes ai2Isk{from{background-position:100% 0}to{background-position:-200% 0}}
  .ai2-ozet{padding:16px 16px 16px 18px;border-radius:16px;background:linear-gradient(135deg,color-mix(in srgb,#9333EA 9%,var(--card)),color-mix(in srgb,#EC4899 6%,var(--card)));border:1px solid color-mix(in srgb,#9333EA 25%,transparent)}
  .ai2-ozet .ust{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:8px}
  .ai2-rozet{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:99px;font-size:10.5px;font-weight:800;letter-spacing:.3px;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899)}
  .ai2-rozet .material-icons-outlined{font-size:13px}
  .ai2-ozet .zaman{font-size:11.5px;color:var(--sub)}
  .ai2-ozet .metin{font-size:14.5px;line-height:1.65;color:var(--ink);font-weight:500}
  .ai2-uyari{display:flex;gap:7px;align-items:flex-start;margin-bottom:10px;padding:10px 12px;border-radius:12px;font-size:12.5px;color:#92400E;background:#FEF3C7;border:1px solid #FDE68A}
  .ai2-uyari .material-icons-outlined{font-size:18px}
  body.koyu .ai2-uyari{background:color-mix(in srgb,#D97706 15%,var(--card));color:#FCD34D;border-color:color-mix(in srgb,#D97706 35%,transparent)}
  .ai2-sek{display:flex;gap:6px;margin:14px 0 10px;flex-wrap:wrap}
  .ai2-sek button{display:inline-flex;align-items:center;gap:5px;padding:8px 12px;border-radius:11px;font:inherit;font-size:12.5px;font-weight:800;cursor:pointer;color:var(--sub2);background:var(--card2);border:1px solid var(--line)}
  .ai2-sek button .material-icons-outlined{font-size:17px;color:var(--c)}
  .ai2-sek button i{font-style:normal;font-size:11px;padding:1px 7px;border-radius:99px;background:color-mix(in srgb,var(--c) 14%,transparent);color:var(--c)}
  .ai2-sek button.aktif{color:#fff;background:var(--c);border-color:var(--c);box-shadow:0 8px 18px -10px var(--c)}
  .ai2-sek button.aktif .material-icons-outlined{color:#fff}.ai2-sek button.aktif i{background:rgba(255,255,255,.25);color:#fff}
  .ai2-madde{display:flex;gap:11px;padding:12px;border-radius:14px;border:1px solid var(--line);margin-bottom:8px;background:var(--card);transition:border-color .15s;animation:ai2Gel .35s ease both}
  .ai2-madde:hover{border-color:color-mix(in srgb,var(--c) 40%,transparent)}
  @keyframes ai2Gel{from{opacity:0;transform:translateY(6px)}}
  .ai2-madde .no{width:28px;height:28px;border-radius:9px;flex:0 0 auto;display:grid;place-items:center;font-size:12.5px;font-weight:900;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent)}
  .ai2-madde b{display:block;font-size:13.5px;color:var(--ink);margin-bottom:2px}
  .ai2-madde .d{font-size:12.5px;color:var(--sub2);line-height:1.55}
  .ai2-madde .sor{display:inline-flex;align-items:center;gap:3px;margin-top:6px;padding:0;border:0;background:none;font:inherit;font-size:11.5px;font-weight:800;color:var(--c);cursor:pointer;opacity:.8}
  .ai2-madde .sor:hover{opacity:1;text-decoration:underline}
  .ai2-madde .sor .material-icons-outlined{font-size:14px}
  .ai2-hata{display:flex;gap:8px;align-items:flex-start;padding:13px;border-radius:13px;font-size:13px;color:#B91C1C;background:color-mix(in srgb,#DC2626 8%,var(--card));border:1px solid color-mix(in srgb,#DC2626 25%,transparent)}
  .ai2-sohbet{display:flex;flex-direction:column;position:sticky;top:14px}
  @media(max-width:1100px){.ai2-sohbet{position:static}}
  .ai2-mesajlar{display:flex;flex-direction:column;gap:10px;max-height:430px;min-height:60px;overflow-y:auto;padding:2px 2px 8px}
  .ai2-mesaj{display:flex;gap:8px;align-items:flex-end;animation:ai2Gel .3s ease both}
  .ai2-mesaj .av{width:28px;height:28px;border-radius:9px;flex:0 0 auto;display:grid;place-items:center;font-size:16px;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899)}
  .ai2-mesaj .balon{padding:10px 12px;border-radius:14px 14px 14px 4px;font-size:13px;line-height:1.55;color:var(--ink);background:var(--card2);border:1px solid var(--line);white-space:pre-wrap;word-break:break-word;min-width:0}
  .ai2-mesaj.ben{justify-content:flex-end}
  .ai2-mesaj.ben .balon{border-radius:14px 14px 4px 14px;color:#fff;background:linear-gradient(135deg,#7C3AED,#C026D3);border:0}
  .ai2-mesaj.hata .balon{color:#B91C1C}
  .ai2-yaziyor{display:inline-flex;gap:4px;padding:3px 0}
  .ai2-yaziyor i{width:7px;height:7px;border-radius:50%;background:#A855F7;animation:ai2Nok 1s ease-in-out infinite}
  .ai2-yaziyor i:nth-child(2){animation-delay:.15s}.ai2-yaziyor i:nth-child(3){animation-delay:.3s}
  @keyframes ai2Nok{50%{transform:translateY(-5px);opacity:.5}}
  .ai2-oneri{display:flex;flex-wrap:wrap;gap:6px;margin:10px 0}
  .ai2-oneri button{padding:6px 10px;border-radius:99px;font:inherit;font-size:11.5px;font-weight:700;cursor:pointer;text-align:left;color:#7E22CE;background:color-mix(in srgb,#9333EA 7%,var(--card));border:1px solid color-mix(in srgb,#9333EA 25%,transparent);transition:background .15s}
  .ai2-oneri button:hover{background:color-mix(in srgb,#9333EA 15%,var(--card))}
  body.koyu .ai2-oneri button{color:#D8B4FE}
  .ai2-yaz{display:flex;gap:6px;padding:5px;border-radius:15px;background:var(--card2);border:1px solid var(--line);transition:border-color .15s,box-shadow .15s}
  .ai2-yaz:focus-within{border-color:#A855F7;box-shadow:0 0 0 4px color-mix(in srgb,#A855F7 15%,transparent)}
  .ai2-yaz input{flex:1;min-width:0;border:0;background:none;outline:0;padding:8px 9px;font:inherit;font-size:13px;color:var(--ink)}
  .ai2-yaz button{width:40px;height:40px;border:0;border-radius:11px;cursor:pointer;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#9333EA,#EC4899)}
  .ai2-yaz button[disabled]{opacity:.6;cursor:wait}
  .ai2-not{display:flex;gap:5px;align-items:flex-start;margin-top:8px;font-size:11px;color:var(--sub)}
  .ai2-not .material-icons-outlined{font-size:14px}
  @media(max-width:640px){.ai2-hero{padding:18px 16px 14px;border-radius:20px}.ai2-hero h2{font-size:19px}.ai2-cta{align-items:stretch;width:100%}.ai2-btn{justify-content:center}.ai2-donem-et{margin-left:0}}
</style>

{{-- Dönem / süre seçimi (raporun araç satırında gösterilir) --}}
@php ob_start(); @endphp
@if ($sayfaDonemli)
  <div class="grp"><span class="lbl">Dönem</span>
    @foreach ($donemler as $k => $ad)<a class="sr-chip {{ $dn['tip'] === $k ? 'aktif' : '' }}" href="?donem={{ $k }}" onclick="this.href += location.hash">{{ $ad }}</a>@endforeach
    <form class="sr-ozel" method="GET" onsubmit="this.action = location.pathname + location.hash">
      <input type="hidden" name="donem" value="ozel"><span>Özel</span>
      <input type="date" name="bas" value="{{ $dn['bas'] }}" max="{{ date('Y-m-d') }}"><span>–</span><input type="date" name="bit" value="{{ $dn['bit'] }}" max="{{ date('Y-m-d') }}">
      <button type="submit">Uygula</button>
    </form>
    <span class="sr-etiket"><b>{{ $dn['etiket'] }}</b> · kıyas {{ \Carbon\Carbon::parse($dn['obas'])->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($dn['obit'])->format('d.m.Y') }}</span>
  </div>
@endif
@php $donemHtml = ob_get_clean(); @endphp

<div class="sr">
  @if ($kat === 'ai')
    @php
      $nb = $aiNabiz ?? null; $nv = $nb['veri'] ?? null; $aiKalan = $nb['kalan'] ?? null;
      $tl = fn ($x) => number_format((float) $x, 0, ',', '.') . ' ₺';
      $qsEk = $donemQs ? '?' . $donemQs : '';
    @endphp
    {{-- ===== AI: üst karşılama + dönem ===== --}}
    <section class="ai2-hero">
      <span class="ai2-orb a"></span><span class="ai2-orb b"></span>
      <div class="ai2-hero-ust">
        <div class="ai2-logo"><span class="material-icons-outlined">auto_awesome</span></div>
        <div class="ai2-hero-yazi">
          <div class="ai2-ust-etiket">Mutfak · Yapay Zekâ Asistanı</div>
          <h2>Mutfağının dönemini 20 saniyede özetleyeyim.</h2>
          <p>Mutfak süreleri, food cost, menü, fire, tüketim farkı, hijyen kayıtları ve iptalleri okur; sana <b>ne oldu, neye dikkat etmelisin, ne yapmalısın</b> diye yazar. İstersen şefe sorar gibi soru da sorabilirsin.</p>
        </div>
        <div class="ai2-cta">
          <button type="button" class="ai2-btn" id="srAiBtn" onclick="srAi(false)"><span class="material-icons-outlined">auto_awesome</span><span class="t">Özet Hazırla</span></button>
          @if ($aiKalan !== null)<div class="ai2-hak" title="AI kullanımı günlük sınırlıdır; veri değişmediyse aynı özet tekrar hak harcamadan gelir."><span class="material-icons-outlined">bolt</span>Bugün kalan AI hakkı: <b id="srHak">{{ $aiKalan }}</b>/40</div>@endif
        </div>
      </div>
      <div class="ai2-donem">
        <span class="lbl"><span class="material-icons-outlined">date_range</span>Dönem</span>
        @foreach ($donemler as $k => $ad)<a class="ai2-chip {{ $dn['tip'] === $k ? 'aktif' : '' }}" href="?donem={{ $k }}">{{ $ad }}</a>@endforeach
        <form class="ai2-ozel {{ $dn['tip'] === 'ozel' ? 'aktif' : '' }}" method="GET">
          <input type="hidden" name="donem" value="ozel"><span>Özel</span>
          <input type="date" name="bas" value="{{ $dn['bas'] }}" max="{{ date('Y-m-d') }}"><span>–</span><input type="date" name="bit" value="{{ $dn['bit'] }}" max="{{ date('Y-m-d') }}">
          <button type="submit">Uygula</button>
        </form>
        <span class="ai2-donem-et"><b>{{ $dn['etiket'] }}</b> · önceki dönemle kıyaslanır ({{ \Carbon\Carbon::parse($dn['obas'])->format('d.m') }} – {{ \Carbon\Carbon::parse($dn['obit'])->format('d.m.Y') }})</span>
      </div>
    </section>

    {{-- ===== Nabız: yapay zekâsız gerçek rakamlar ===== --}}
    @if ($nv)
      <section>
        <div class="ai2-bolum-bas"><span class="material-icons-outlined">monitor_heart</span>Dönemin nabzı <small>Gerçek rakamlar, yapay zekâ yorumu değil. Kutuya tıklayınca ilgili rapor açılır.</small></div>
        <div class="ai2-kpi-g">
          <a class="ai2-kpi" style="--c:#EA580C" href="/mutfak-raporlar/menu{{ $qsEk }}">
            <span class="ik material-icons-outlined">restaurant</span><span class="e">Satılan porsiyon</span><b>{{ number_format((float) $nv['porsiyon'], 0, ',', '.') }}</b>
            @if ($nv['degisim'] !== null)<span class="alt {{ $nv['degisim'] >= 0 ? 'yesil' : 'kirmizi' }}"><span class="material-icons-outlined">{{ $nv['degisim'] >= 0 ? 'trending_up' : 'trending_down' }}</span>%{{ number_format(abs($nv['degisim']), 1, ',', '.') }} önceki döneme göre</span>
            @else<span class="alt">Önceki dönemde satış yok</span>@endif
          </a>
          <a class="ai2-kpi" style="--c:#DC2626" href="/mutfak-raporlar/hiz{{ $qsEk }}">
            <span class="ik material-icons-outlined">timer</span><span class="e">Ort. mutfak süresi</span><b>{{ $nv['sure'] !== null ? number_format($nv['sure'], 1, ',', '.') . ' dk' : '—' }}</b>
            <span class="alt {{ $nv['uyum'] !== null && $nv['uyum'] < 80 ? 'kirmizi' : 'yesil' }}">{{ $nv['uyum'] !== null ? '%' . number_format($nv['uyum'], 0) . ' hedef süresinde · ' . $nv['geciken'] . ' geciken' : 'KDS ölçümü yok' }}</span>
          </a>
          <a class="ai2-kpi" style="--c:#059669" href="/mutfak-raporlar/maliyet{{ $qsEk }}">
            <span class="ik material-icons-outlined">percent</span><span class="e">Teorik food cost</span><b>{{ $nv['fc'] !== null ? '%' . number_format($nv['fc'], 1, ',', '.') : '—' }}</b>
            <span class="alt {{ $nv['recetesiz'] > 0 ? 'turuncu' : '' }}">{{ $nv['recetesiz'] > 0 ? 'Reçetesiz satış: ' . $tl($nv['recetesiz']) : ($nv['fc'] !== null ? 'Tüm satışlar reçeteli' : 'Reçete yok') }}</span>
          </a>
          <a class="ai2-kpi" style="--c:#C2410C" href="/mutfak-raporlar/fire{{ $qsEk }}">
            <span class="ik material-icons-outlined">delete_sweep</span><span class="e">Fire</span><b>{{ $tl($nv['fire']) }}</b>
            <span class="alt {{ $nv['fire_oran'] !== null && $nv['fire_oran'] > 3 ? 'kirmizi' : '' }}">{{ $nv['fire_oran'] !== null ? 'Alışın %' . number_format($nv['fire_oran'], 1, ',', '.') . '\'i' : 'Alış faturası yok' }}</span>
          </a>
          <a class="ai2-kpi" style="--c:#BE123C" href="/mutfak-raporlar/iptal{{ $qsEk }}">
            <span class="ik material-icons-outlined">cancel</span><span class="e">Mutfaktan sonra iptal</span><b>{{ $nv['iptal'] }}</b>
            <span class="alt {{ $nv['iptal'] ? 'turuncu' : 'yesil' }}">{{ $nv['iptal'] ? $tl($nv['iptal_tl']) . ' tutarında' : 'Pişmiş ürün iptali yok' }}</span>
          </a>
          <a class="ai2-kpi" style="--c:#0284C7" href="/mutfak-raporlar/hijyen{{ $qsEk }}">
            <span class="ik material-icons-outlined">thermostat</span><span class="e">Uygunsuz sıcaklık</span><b>{{ $nv['uygunsuz'] }}</b>
            <span class="alt {{ $nv['kritik'] || $nv['skt'] ? 'turuncu' : '' }}">{{ $nv['sicaklik'] ? $nv['sicaklik'] . ' ölçüm' : 'Sıcaklık kaydı yok' }} · {{ $nv['kritik'] }} kritik stok</span>
          </a>
        </div>
      </section>
    @endif

    {{-- ===== Özet + Sohbet ===== --}}
    <div class="ai2-izgara">
      <section class="ai2-panel">
        <div class="ai2-panel-bas">
          <div class="ai2-bolum-bas" style="margin:0"><span class="material-icons-outlined">summarize</span>Yönetici Özeti</div>
          <div class="ai2-panel-arac" id="srAiIndir" @if (!$ai) hidden @endif>
            <button type="button" class="ai2-mini" onclick="srAi(true)" title="Aynı dönemi yapay zekâya yeniden yazdır (1 hak harcar)"><span class="material-icons-outlined">refresh</span>Yeniden yaz</button>
            <a class="ai2-mini x" href="/mutfak-raporlar/indir?rapor=ai&format=xlsx&{{ $donemQs }}"><span class="material-icons-outlined">table_view</span>Excel</a>
            <a class="ai2-mini p" target="_blank" href="/mutfak-raporlar/indir?rapor=ai&format=pdf&{{ $donemQs }}"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
          </div>
        </div>
        <div id="srAiSonuc">
          <div class="ai2-bos">
            <div class="ai2-bos-ik"><span class="material-icons-outlined">auto_awesome</span></div>
            <h4>Bu dönem için henüz özet yok</h4>
            <p>Yukarıdaki <b>Özet Hazırla</b>'ya bas; yapay zekâ seçili dönemi okuyup dört bölüm halinde yazsın:</p>
            <div class="ai2-bos-g">
              <div style="--c:#7C3AED"><span class="material-icons-outlined">notes</span><b>Özet</b>Dönem kısaca nasıl geçti</div>
              <div style="--c:#4F46E5"><span class="material-icons-outlined">insights</span><b>Öne çıkanlar</b>Ne değişti, ne dikkat çekti</div>
              <div style="--c:#DC2626"><span class="material-icons-outlined">warning_amber</span><b>Riskler</b>Neye dikkat etmelisin</div>
              <div style="--c:#059669"><span class="material-icons-outlined">lightbulb</span><b>Öneriler</b>Somut yapılacak işler</div>
            </div>
          </div>
        </div>
      </section>

      <aside class="ai2-panel ai2-sohbet">
        <div class="ai2-panel-bas"><div class="ai2-bolum-bas" style="margin:0"><span class="material-icons-outlined">forum</span>AI'ya Sor</div>
          <button type="button" class="ai2-mini" id="srSohbetTemizle" onclick="srSohbetTemizle()" hidden><span class="material-icons-outlined">delete_sweep</span>Temizle</button></div>
        <div class="ai2-mesajlar" id="srCevap"></div>
        <div class="ai2-oneri" id="srOneri">
          @foreach (['Hangi saatlerde mutfak yavaşlıyor?', 'Hangi ürünler hep gecikiyor?', 'Fire en çok nereden geliyor?', 'Menüden hangi ürünü çıkarmalıyım?', 'Food cost neden yükseldi?', 'Hijyen kayıtlarında sorun var mı?'] as $q)
            <button type="button" onclick="srSor(this.textContent)">{{ $q }}</button>
          @endforeach
        </div>
        <div class="ai2-yaz"><input id="srSoru" placeholder="Bir soru yaz…" maxlength="500" onkeydown="if(event.key==='Enter')srSor()">
          <button type="button" id="srSorBtn" onclick="srSor()" aria-label="Gönder"><span class="material-icons-outlined">send</span></button></div>
        <div class="ai2-not"><span class="material-icons-outlined">info</span>Cevapları yapay zekâ yazar; karar vermeden önce rakamı ilgili raporda kontrol et.</div>
      </aside>
    </div>
  @else
    {{-- ===== RAPOR SEKMELERİ ===== --}}
    <div class="sr-sek" id="srListe">
      @foreach ($liste as $r)
        <button type="button" class="sr-r" style="--c:{{ $r['renk'] }}" data-k="{{ $r['k'] }}" onclick="srSec('{{ $r['k'] }}')">
          <span class="ik"><span class="material-icons-outlined">{{ $r['ik'] }}</span></span>
          <span style="min-width:0;flex:1">
            <span class="t">{{ $r['ad'] }}<span class="sr-nok" style="--n:{{ $r['roz'][1] }}" title="{{ $r['roz'][0] }}"></span></span>
            <span class="d">{{ $r['acik'] }}</span>
          </span>
        </button>
      @endforeach
    </div>

    {{-- ===== GÖRÜNTÜLEYİCİ ===== --}}
    <div class="sr-gor" id="srGor">
      <div class="sr-gor-bas">
        <div class="ik" id="gIk"><span class="material-icons-outlined"></span></div>
        <div style="flex:1;min-width:240px">
          <h3><span id="gAd"></span><span class="sr-roz" id="gRoz"><span class="material-icons-outlined"></span><b></b></span></h3>
          <div class="d" id="gAcik"></div>
          <div class="sr-eksik" id="gEksik"></div>
        </div>
        <div class="sr-arac">
          <div class="sr-per" id="gPer" hidden></div>
          <a class="sr-b x" id="gX"><span class="material-icons-outlined">table_view</span>Excel</a>
          <a class="sr-b p" id="gP" target="_blank"><span class="material-icons-outlined">picture_as_pdf</span>PDF</a>
        </div>
      </div>
      <div class="sr-alt">
        {!! $donemHtml !!}
        @if ($gerekliTum)
          @if (trim($donemHtml) !== '')<span class="ayr"></span>@endif
          <div class="grp"><span class="lbl">Kullanılan veri</span>
            @foreach ($gerekliTum as $m)
              @if (!empty($durum[$m]))
                <span class="sr-m var" title="Son 1 yılda {{ $durum[$m] }} kayıt"><span class="material-icons-outlined">check_circle</span>{{ $modAd[$m] ?? $m }}</span>
              @else
                @php [$mUrl, $mYol] = $modLink[$m] ?? [null, '']; @endphp
                @if ($mUrl)<a class="sr-m yok" href="{{ $mUrl }}" title="Son 1 yılda kayıt yok — girmek için: {{ $mYol }}"><span class="material-icons-outlined">add_circle_outline</span>{{ $modAd[$m] ?? $m }}</a>
                @else<span class="sr-m yok" title="{{ $mYol }}"><span class="material-icons-outlined">radio_button_unchecked</span>{{ $modAd[$m] ?? $m }}</span>@endif
              @endif
            @endforeach
          </div>
        @endif
      </div>
      <div class="sr-cer">
        <div class="sr-yuk" id="gYuk"><div style="text-align:center"><span class="material-icons-outlined">autorenew</span><div>Rapor hazırlanıyor…</div></div></div>
        <iframe id="gFrame" title="Rapor önizleme" scrolling="no" onload="srBoy()"></iframe>
      </div>
    </div>
  @endif
</div>

<script>
  var SR_QS = {!! json_encode($sayfaDonemli ? $donemQs : '') !!};
  var SR_AI_QS = {!! json_encode($donemQs) !!};
  var SR_LISTE = {!! json_encode((object) collect($liste)->keyBy('k')->all(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var SR_AI = {!! json_encode($ai, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
  var srPerSec = {}; // dönemsel raporlarda rapor başına seçili dönem
  function srEsc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function srUrl(r, fmt) {
    var qs = r.per ? 'donem=' + (srPerSec[r.k] || Object.keys(r.per)[0]) : SR_QS;
    return r.url + '&format=' + fmt + (qs ? '&' + qs : '');
  }
  // Önizleme raporun tam boyunda: iç kaydırma çubuğu yok, sayfa kayar
  function srBoy() {
    var f = document.getElementById('gFrame'); if (!f || !f.src || f.src === 'about:blank') return;
    try {
      var doc = f.contentDocument; if (!doc || !doc.body) return;
      f.style.height = Math.max(520, Math.ceil(doc.body.getBoundingClientRect().height)) + 'px';
      document.getElementById('gYuk').style.opacity = 0;
    } catch (e) { f.style.height = '1400px'; f.removeAttribute('scrolling'); document.getElementById('gYuk').style.opacity = 0; }
  }
  window.addEventListener('resize', function () { clearTimeout(window.srBoyT); window.srBoyT = setTimeout(srBoy, 150); });
  function srSec(k, ilk) {
    var r = SR_LISTE[k]; if (!r) return;
    document.querySelectorAll('#srListe .sr-r').forEach(function (b) { b.classList.toggle('aktif', b.dataset.k === k); });
    var g = document.getElementById('srGor'); g.style.setProperty('--c', r.renk);
    document.querySelector('#gIk span').textContent = r.ik;
    document.getElementById('gAd').textContent = r.ad;
    document.getElementById('gAcik').textContent = r.acik;
    var roz = document.getElementById('gRoz'); roz.style.setProperty('--c', r.roz[1]);
    roz.querySelector('span').textContent = r.roz[2]; roz.querySelector('b').textContent = r.roz[0];
    document.getElementById('gEksik').textContent = r.roz[3] ? 'Kullanılmayan: ' + r.roz[3] + ' — ilgili bölümler boş gelir, rapor nedenini yazar.' : '';
    var per = document.getElementById('gPer');
    if (r.per) {
      var sec = srPerSec[k] || Object.keys(r.per)[0];
      per.innerHTML = Object.keys(r.per).map(function (p) { return '<button type="button" class="' + (p === sec ? 'aktif' : '') + '" data-p="' + p + '">' + srEsc(r.per[p]) + '</button>'; }).join('');
      per.querySelectorAll('button').forEach(function (b) { b.onclick = function () { srPerSec[k] = b.dataset.p; srSec(k); }; });
      per.hidden = false;
    } else per.hidden = true;
    document.getElementById('gX').href = srUrl(r, 'xlsx');
    document.getElementById('gP').href = srUrl(r, 'pdf');
    document.getElementById('gYuk').style.opacity = 1;
    document.getElementById('gFrame').src = srUrl(r, 'html') + '&onizle=1';
    if (!ilk) { history.replaceState(null, '', '#' + k); if (window.innerWidth <= 1050) g.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  }
  (function () {
    if (!document.getElementById('srListe')) return;
    var h = location.hash.slice(1), ilk = SR_LISTE[h] ? h : Object.keys(SR_LISTE)[0];
    if (ilk) srSec(ilk, true);
  })();

  // ---- AI ----
  var SR_DONEM_ET = {!! json_encode($dn['etiket'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
  var SR_AI_BOL = [['one_cikanlar', 'Öne çıkanlar', '#4F46E5', 'insights'], ['riskler', 'Riskler', '#DC2626', 'warning_amber'], ['oneriler', 'Öneriler', '#059669', 'lightbulb']];
  var srAiSon = null, srAiSek = 'one_cikanlar';
  function srHakDus() { var h = document.getElementById('srHak'); if (h) h.textContent = Math.max(0, (parseInt(h.textContent, 10) || 0) - 1); }
  function srAiCiz(a, onbellek) {
    var kutu = document.getElementById('srAiSonuc'); if (!a || !kutu) return;
    srAiSon = a;
    var baskaDonem = a._donem && a._donem !== SR_DONEM_ET;
    var sek = SR_AI_BOL.filter(function (b) { return (a[b[0]] || []).length; });
    if (!sek.some(function (b) { return b[0] === srAiSek; }) && sek.length) srAiSek = sek[0][0];
    var aktif = SR_AI_BOL.filter(function (b) { return b[0] === srAiSek; })[0];
    kutu.innerHTML =
      (baskaDonem ? '<div class="ai2-uyari"><span class="material-icons-outlined">history</span><div>Bu özet <b>' + srEsc(a._donem) + '</b> dönemine ait (en son hazırlanan). Seçili dönem (<b>' + srEsc(SR_DONEM_ET) + '</b>) için yukarıdan <b>Özet Hazırla</b>\'ya bas.</div></div>' : '') +
      '<div class="ai2-ozet"><div class="ust"><span class="ai2-rozet"><span class="material-icons-outlined">auto_awesome</span>YAPAY ZEKÂ ÖZETİ</span>' +
      '<span class="zaman">' + srEsc(a._donem || '') + ' · ' + srEsc(a._zaman || '') + (onbellek ? ' · veri değişmediği için kayıtlı özet gösterildi' : '') + '</span></div>' +
      '<div class="metin">' + srEsc(a.ozet) + '</div></div>' +
      '<div class="ai2-sek">' + sek.map(function (b) {
        return '<button type="button" style="--c:' + b[2] + '" class="' + (b[0] === srAiSek ? 'aktif' : '') + '" onclick="srAiSek=\'' + b[0] + '\';srAiCiz(srAiSon)"><span class="material-icons-outlined">' + b[3] + '</span>' + b[1] + ' <i>' + a[b[0]].length + '</i></button>';
      }).join('') + '</div>' +
      (aktif ? (a[aktif[0]] || []).map(function (x, i) {
        return '<div class="ai2-madde" style="--c:' + aktif[2] + ';animation-delay:' + (i * 0.05) + 's"><span class="no">' + (i + 1) + '</span><div style="min-width:0"><b>' + srEsc(x.baslik) + '</b><div class="d">' + srEsc(x.detay) + '</div>' +
          '<button type="button" class="sor" data-b="' + srEsc(x.baslik).replace(/"/g, '&quot;') + '" onclick="srSor(\'Şunu biraz daha açar mısın, neye dayanıyor ve ne yapmalıyım: \' + this.dataset.b)"><span class="material-icons-outlined">chat_bubble_outline</span>AI\'ya detayını sor</button></div></div>';
      }).join('') : '');
    document.getElementById('srAiIndir').hidden = false;
  }
  function srPost(g) {
    return fetch('/mutfak-raporlar/ai?' + SR_AI_QS, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(g) }).then(function (r) { return r.json(); });
  }
  var srAdimlar = ['Siparişler ve mutfak süreleri okunuyor…', 'Önceki dönemle karşılaştırılıyor…', 'Food cost ve menü inceleniyor…', 'Fire ve tüketim farkı hesaplanıyor…', 'Hijyen kayıtları kontrol ediliyor…', 'Öneriler yazılıyor…'];
  function srAi(yenile) {
    var b = document.getElementById('srAiBtn'); if (b.disabled) return;
    var kutu = document.getElementById('srAiSonuc'), eski = b.querySelector('.t').textContent, i = 0;
    b.disabled = true; b.querySelector('.t').textContent = 'Hazırlanıyor…';
    kutu.innerHTML = '<div class="ai2-adim"><span class="material-icons-outlined">autorenew</span><span id="srAdim">' + srAdimlar[0] + '</span></div>' +
      [92, 100, 78, 0, 60, 88, 70].map(function (w) { return w ? '<div class="ai2-isk" style="width:' + w + '%"></div>' : '<div style="height:12px"></div>'; }).join('');
    var t = setInterval(function () { i = Math.min(i + 1, srAdimlar.length - 1); var e = document.getElementById('srAdim'); if (e) e.textContent = srAdimlar[i]; }, 3200);
    if (window.innerWidth <= 1100) kutu.scrollIntoView({ behavior: 'smooth', block: 'center' });
    var bitir = function () { clearInterval(t); b.disabled = false; b.querySelector('.t').textContent = eski; };
    srPost({ yenile: yenile ? 1 : 0 }).then(function (s) {
      bitir();
      if (!s.ok) { kutu.innerHTML = '<div class="ai2-hata"><span class="material-icons-outlined">error_outline</span>' + srEsc(s.hata || 'Özet hazırlanamadı.') + '</div>'; return; }
      if (!s.onbellek) srHakDus();
      srAiCiz(s.analiz, s.onbellek);
    }).catch(function () { bitir(); kutu.innerHTML = '<div class="ai2-hata"><span class="material-icons-outlined">wifi_off</span>Bağlantı hatası, tekrar dene.</div>'; });
  }
  // ---- sohbet ----
  function srMesaj(tur, html) {
    var m = document.getElementById('srCevap'), d = document.createElement('div');
    d.className = 'ai2-mesaj ' + tur;
    d.innerHTML = (tur === 'ben' ? '' : '<span class="av material-icons-outlined">auto_awesome</span>') + '<div class="balon">' + html + '</div>';
    m.appendChild(d); m.scrollTop = m.scrollHeight; return d;
  }
  function srSohbetIlk() {
    document.getElementById('srCevap').innerHTML = '';
    srMesaj('ai', 'Merhaba şef! Seçili dönemin (' + srEsc(SR_DONEM_ET) + ') mutfak verisine bakarak sorularını cevaplarım. Aşağıdan hazır bir soru seç ya da kendin yaz.');
  }
  function srSohbetTemizle() { srSohbetIlk(); document.getElementById('srSohbetTemizle').hidden = true; }
  function srSor(metin) {
    var inp = document.getElementById('srSoru'), btn = document.getElementById('srSorBtn');
    var q = (typeof metin === 'string' ? metin : inp.value).trim(); if (!q || btn.disabled) return;
    inp.value = ''; btn.disabled = true; document.getElementById('srSohbetTemizle').hidden = false;
    if (window.innerWidth <= 1100) document.querySelector('.ai2-sohbet').scrollIntoView({ behavior: 'smooth', block: 'start' });
    srMesaj('ben', srEsc(q));
    var bekle = srMesaj('ai', '<span class="ai2-yaziyor"><i></i><i></i><i></i></span>');
    srPost({ soru: q }).then(function (s) {
      btn.disabled = false;
      if (s.ok) srHakDus(); else bekle.classList.add('hata');
      bekle.querySelector('.balon').innerHTML = srEsc(s.ok ? s.cevap : (s.hata || 'Cevap alınamadı.'));
      var m = document.getElementById('srCevap'); m.scrollTop = m.scrollHeight;
    }).catch(function () { btn.disabled = false; bekle.classList.add('hata'); bekle.querySelector('.balon').textContent = 'Bağlantı hatası, tekrar dene.'; });
  }
  if (document.getElementById('srCevap')) srSohbetIlk();
  if (SR_AI) srAiCiz(SR_AI);
</script>
@endsection

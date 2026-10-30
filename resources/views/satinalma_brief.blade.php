@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', 'Satınalma Brifingi')
@section('title', 'Satınalma Brifingi')

@push('head')
<style>
  /* ====== Referans tasarım · kompakt · pastel (patron-ai ile tutarlı) ====== */
  #sb{max-width:1200px;margin:0 auto;--rose:#F56A8B;--pgreen:#2FBE94;--pamber:#F6A623;--pblue:#5B8DEF;--pred:#F5576C}
  #sb *{box-sizing:border-box}

  /* HERO — açık */
  .sb-hero{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:12px}
  .sb-hero-ic{display:flex;align-items:center;gap:13px;flex:1;min-width:200px}
  .sb-orb{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#FDB44B,#F6A623);display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 10px 22px rgba(246,166,35,.3)}
  .sb-orb .material-icons-outlined{color:#fff;font-size:24px}
  .sb-selam{font-size:21px;font-weight:800;color:var(--ink);line-height:1.1;letter-spacing:-.5px;margin-bottom:3px}
  .sb-alt{font-size:12px;color:var(--sub);font-weight:500}
  .sb-rozetler{display:flex;gap:7px;flex-wrap:wrap}
  .sb-roz{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border:1px solid var(--line);color:var(--sub);padding:7px 12px;border-radius:12px;font-size:12px;font-weight:700}
  .sb-roz .material-icons-outlined{font-size:15px}

  /* AI DEĞERLENDİRMESİ */
  .sb-genel{background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 6%,var(--card)),var(--card) 60%);border:1px solid color-mix(in srgb,var(--mor1) 18%,var(--line));border-radius:16px;padding:14px 16px;margin-bottom:13px}
  .sb-genel h4{font-size:11.5px;font-weight:800;color:var(--mor1);letter-spacing:.4px;text-transform:uppercase;margin:0 0 7px;display:flex;align-items:center;gap:7px}
  .sb-genel .metin{font-size:13.5px;line-height:1.6;color:var(--ink)}
  .sb-genel .boldv{font-weight:800;color:var(--mor1)}

  /* İKİ KOLON */
  .sb-ikili{display:grid;grid-template-columns:1fr 1.5fr;gap:13px;margin-bottom:13px}
  @media(max-width:900px){.sb-ikili{grid-template-columns:1fr}}
  .sb-dun-blok,.sb-bugun-blok{background:var(--card);border:1px solid var(--line);border-radius:17px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-dun-blok h3,.sb-bugun-blok h3{font-size:14px;font-weight:800;color:var(--ink);margin:0 0 12px;display:flex;align-items:center;gap:9px;letter-spacing:-.2px}
  .sb-dun-blok h3 .ikon-ct,.sb-bugun-blok h3 .ikon-ct{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;flex:none}
  .sb-dun-blok h3 .ikon-ct{background:linear-gradient(135deg,#2FBE94,#059669)}
  .sb-bugun-blok h3 .ikon-ct{background:linear-gradient(135deg,#7B5CFA,#4F46E5)}
  .sb-dun-blok h3 .ikon-ct .material-icons-outlined,.sb-bugun-blok h3 .ikon-ct .material-icons-outlined{font-size:17px}
  .sb-bugun-blok h3 .sayi{margin-left:auto;background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);padding:1px 9px;border-radius:20px;font-size:12px;font-weight:800}

  /* DÜN YAPILANLAR */
  .sb-dun-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px dashed var(--line);font-size:12.5px}
  .sb-dun-sat:first-of-type{border-top:0;padding-top:0}
  .sb-dun-sat .check{width:22px;height:22px;border-radius:50%;background:rgba(47,190,148,.15);color:var(--pgreen);display:flex;align-items:center;justify-content:center;flex:none}
  .sb-dun-sat .check .material-icons-outlined{font-size:14px}
  .sb-dun-sat .mtn{flex:1;min-width:0;color:var(--ink);font-weight:600}
  .sb-dun-sat .mtn .ozel{color:var(--sub);font-weight:500}
  .sb-dun-sat a{color:var(--mor1);font-size:11px;font-weight:700;text-decoration:none;white-space:nowrap}
  .sb-dun-bos{text-align:center;padding:22px 10px;color:var(--sub);font-size:12.5px}

  /* BUGÜN YAPILACAKLAR — görev kartları */
  .sb-yap-sat{position:relative;background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px;margin-bottom:8px}
  .sb-yap-sat:last-child{margin-bottom:0}
  .sb-yap-sat.oncelik-yuksek{border-left:3px solid var(--pred)}
  .sb-yap-sat.oncelik-orta{border-left:3px solid var(--pamber)}
  .sb-yap-sat.oncelik-dusuk{border-left:3px solid var(--pblue)}
  .sb-yap-head{display:flex;align-items:center;gap:11px}
  .sb-yap-ikon{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex:none;color:#fff}
  .sb-yap-ikon .material-icons-outlined{font-size:19px}
  .sb-yap-ic{flex:1;min-width:0}
  .sb-yap-bas{font-size:13px;font-weight:700;color:var(--ink);line-height:1.3}
  .sb-yap-aci{font-size:11px;color:var(--sub);line-height:1.4;margin-top:2px}
  .sb-yap-onc-roz{font-size:9px;font-weight:900;padding:2px 8px;border-radius:20px;letter-spacing:.3px;flex:none;color:#fff}
  .sb-yap-onc-roz.yuksek{background:var(--pred)}.sb-yap-onc-roz.orta{background:var(--pamber)}.sb-yap-onc-roz.dusuk{background:var(--pblue)}
  .sb-yap-btn-grup{display:flex;gap:8px;justify-content:flex-end;align-items:center;margin-top:9px}
  .sb-yap-btn-grup .kilit{font-size:10px;color:var(--sub2);display:inline-flex;align-items:center;gap:4px;margin-right:auto}
  .sb-yap-btn-grup .kilit .material-icons-outlined{font-size:12px;color:var(--pgreen)}
  .sb-btn-yap{background:linear-gradient(135deg,#7B5CFA,#6A49F2);color:#fff;border:0;padding:8px 15px;border-radius:9px;font-size:11.5px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:5px;box-shadow:0 4px 12px rgba(124,58,237,.28)}
  .sb-btn-yap .material-icons-outlined{font-size:15px}
  .sb-btn-yap:disabled{opacity:.6;cursor:wait}
  .sb-btn-yap.ok-tamam{background:linear-gradient(135deg,#2FBE94,#059669);cursor:default;opacity:1;box-shadow:none}
  .sb-yap-sat.tamam{opacity:.92}
  .sb-ac-link{display:inline-flex;align-items:center;gap:5px;margin-left:9px;color:var(--mor1);font-size:12px;font-weight:700;text-decoration:none}
  .sb-ac-link .material-icons-outlined{font-size:15px}
  .sb-btn-link{background:color-mix(in srgb,var(--pblue) 14%,transparent);color:var(--pblue);border:0;padding:8px 15px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;text-decoration:none}
  .sb-btn-link:hover{background:var(--pblue);color:#fff}
  .sb-btn-link .material-icons-outlined{font-size:15px}

  .sb-yap-sonuc{display:none;font-size:12.5px;line-height:1.5;padding:12px 14px;border-radius:11px;margin-top:10px;font-weight:600}
  .sb-yap-sonuc.ok{background:rgba(47,190,148,.12);color:#047857;border:1.5px solid rgba(47,190,148,.4);display:flex;align-items:center;gap:10px;animation:sbSonucIn .35s cubic-bezier(.34,1.56,.64,1)}
  .sb-yap-sonuc.ok .ok-ikon{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#2FBE94,#059669);color:#fff;display:flex;align-items:center;justify-content:center;flex:none}
  .sb-yap-sonuc.ok .ok-ikon .material-icons-outlined{font-size:19px;font-weight:900}
  .sb-yap-sonuc.ok .ok-metin b{color:#047857;font-weight:800;font-size:14px;display:block;margin-bottom:2px}
  .sb-yap-sonuc.hata{background:rgba(245,87,108,.1);color:var(--pred);border-left:3px solid var(--pred);display:block}
  .sb-yap-sonuc.vz{background:var(--card2);color:var(--sub2);border-left:3px solid var(--sub);display:block}
  @keyframes sbSonucIn{from{transform:translateY(-10px);opacity:0}to{transform:translateY(0);opacity:1}}

  /* TOAST */
  .sb-toast{position:fixed;top:20px;right:20px;background:linear-gradient(135deg,#2FBE94,#059669);color:#fff;padding:13px 18px;border-radius:14px;box-shadow:0 16px 40px rgba(16,185,129,.38);z-index:10000;display:none;align-items:center;gap:11px;font-size:13.5px;font-weight:700;max-width:380px}
  .sb-toast.goruli{display:flex}
  .sb-toast .material-icons-outlined{font-size:23px}
  .sb-toast .mt b{display:block;font-size:13px;font-weight:800;margin-bottom:2px}
  .sb-toast .mt span{font-size:12px;opacity:.9;font-weight:500}

  .sb-bugun-bos{text-align:center;padding:26px 10px;color:var(--sub);font-size:13px}
  .sb-bugun-bos .material-icons-outlined{font-size:40px;color:var(--pgreen);display:block;margin:0 auto 7px;opacity:.7}

  /* ALT BLOKLAR */
  .sb-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03);margin-bottom:12px}
  .sb-blok h4{font-size:13.5px;font-weight:800;color:var(--ink);margin:0 0 10px;display:flex;align-items:center;gap:8px;letter-spacing:-.2px}
  .sb-blok h4 .sage{margin-left:auto;font-size:11px;color:var(--sub);font-weight:600}
  .sb-sat{display:flex;align-items:center;gap:10px;padding:8px 0;border-top:1px solid var(--line);font-size:12.5px}
  .sb-sat:first-of-type{border-top:0}
  .sb-sat .ad{flex:1;min-width:0;color:var(--ink);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .sb-sat .alt{font-size:11px;color:var(--sub);margin-top:2px}
  .sb-sat .roz-k{font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;color:#fff;flex:none;letter-spacing:.3px}

  /* ALT ŞERİT */
  .sb-mini-bar{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:10px 13px;display:flex;flex-wrap:wrap;gap:6px;align-items:center;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-mini-bar .chip{display:inline-flex;align-items:center;gap:5px;background:var(--card2);border-radius:20px;padding:5px 11px;font-size:11.5px;color:var(--sub);font-weight:600}
  .sb-mini-bar .link{margin-left:auto;font-size:12px;font-weight:700;color:var(--mor1);text-decoration:none;display:inline-flex;align-items:center;gap:3px}

  /* ===== 5 KPI (referans gibi) ===== */
  .sb-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:11px;margin-bottom:12px}
  .sb-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:11px 12px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-k .kic{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
  .sb-k .kic .material-icons-outlined{font-size:19px}
  .sb-k .kv{font-size:19px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .sb-k .kv small{font-size:12px;font-weight:700;color:var(--sub)}
  .sb-k .kl{font-size:11.5px;color:var(--ink);font-weight:700;margin-top:5px}
  .sb-k .ks{font-size:10px;color:var(--sub);margin-top:2px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  #sb .t-rose{background:rgba(245,106,139,.13);color:var(--rose)} #sb .t-green{background:rgba(47,190,148,.14);color:var(--pgreen)}
  #sb .t-amber{background:rgba(246,166,35,.15);color:var(--pamber)} #sb .t-blue{background:rgba(91,141,239,.14);color:var(--pblue)}
  #sb .t-red{background:rgba(245,87,108,.13);color:var(--pred)} #sb .t-mor{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}

  /* ===== mid: yapılacaklar + AI panel ===== */
  .sb-mid{display:grid;grid-template-columns:1.6fr 1fr;gap:13px;margin-bottom:13px;align-items:start}
  @media(max-width:1100px){.sb-kpis{grid-template-columns:repeat(3,1fr)}}
  @media(max-width:900px){.sb-mid{grid-template-columns:1fr}}
  @media(max-width:560px){.sb-kpis{grid-template-columns:1fr 1fr}}
  .sb-aipanel{background:linear-gradient(170deg,color-mix(in srgb,var(--mor1) 7%,var(--card)),var(--card) 60%);border:1px solid color-mix(in srgb,var(--mor1) 18%,var(--line));border-radius:17px;padding:15px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .sb-aipanel .ust{display:flex;align-items:center;gap:10px;margin-bottom:10px}
  .sb-aipanel .rob{width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;flex:none;box-shadow:0 8px 18px rgba(124,58,237,.3)}
  .sb-aipanel .rob .material-icons-outlined{color:#fff;font-size:20px}
  .sb-aipanel .bas{font-size:15px;font-weight:800;color:var(--ink)}
  .sb-aipanel .verdict{font-size:12px;line-height:1.55;color:var(--sub);margin-bottom:12px}
  .sb-aipanel .verdict .boldv{font-weight:800;color:var(--mor1)}
  .sb-aipanel .yapar{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:var(--ink);margin-bottom:8px}
  .sb-aipanel .yapar .mini{background:var(--mor1);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:6px}
  .sb-aiact{display:flex;flex-direction:column;gap:7px}
  .sb-aiact a{display:flex;align-items:center;gap:10px;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:9px 11px;font-size:11.5px;font-weight:600;color:var(--ink);text-decoration:none;transition:.15s}
  .sb-aiact a:hover{border-color:var(--mor1)}
  .sb-aiact .aic{width:29px;height:29px;border-radius:9px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);display:flex;align-items:center;justify-content:center;flex:none}
  .sb-aiact .aic .material-icons-outlined{font-size:16px}
  .sb-ch{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:800;color:var(--ink);margin:0 0 12px;letter-spacing:-.2px}
  .sb-ch .ikon-ct{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;flex:none}
  .sb-ch .sayi{margin-left:auto;background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);padding:1px 9px;border-radius:20px;font-size:12px;font-weight:800}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="sb">
@php
  $saat = (int) now()->format('H');
  $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
  $adK = $ad ? trim(explode(' ', trim($ad))[0]) : 'Satın Almacı';
  $tl = fn($v) => number_format((float) round((float) $v), 0, ',', '.') . '₺';
  $butceC = $butceYuzde === null ? 'var(--sub)' : ($butceYuzde > 100 ? '#F43F5E' : ($butceYuzde >= 85 ? '#F59E0B' : '#10B981'));

  // Genel değerlendirme cümlesi (kural tabanlı)
  $degerleme = [];
  if ($kritikSayi > 0)   $degerleme[] = "<span class='boldv'>$kritikSayi kritik stok</span> acil siparişlik";
  if ($gecikenSip > 0)   $degerleme[] = "<span class='boldv'>$gecikenSip sipariş gecikti</span>";
  if ($yeniTalep > 0)    $degerleme[] = "<span class='boldv'>$yeniTalep yeni talep</span> inceleme bekliyor";
  if ($teklifBekleyen > 0) $degerleme[] = "<span class='boldv'>$teklifBekleyen teklif</span> karar bekliyor";
  if ($butceYuzde !== null && $butceYuzde > 100) $degerleme[] = "bütçe <span class='boldv'>%{$butceYuzde} aşıldı</span>";
  $aiOzet = count($degerleme)
    ? "Bugünün gündemi: " . implode(" · ", array_slice($degerleme, 0, 4)) . "."
    : "Satın alma operasyonu yolunda — acil aksiyon yok. Par-seviyelerini sürdür.";
@endphp

{{-- HERO --}}
<div class="sb-hero">
  <div class="sb-hero-ic">
    <div class="sb-orb"><span class="material-icons-outlined">wb_sunny</span></div>
    <div style="flex:1;min-width:0">
      <div class="sb-selam">{{ $selam }}, {{ $adK }}! ☕</div>
      <div class="sb-alt">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} · Satınalma Sabah Brifingi</div>
    </div>
  </div>
  <div class="sb-rozetler">
    <span class="sb-roz"><span class="material-icons-outlined">task_alt</span>{{ count($dunYapilanlar) }} dün yapıldı</span>
    <span class="sb-roz"><span class="material-icons-outlined">pending_actions</span>{{ count($bugunYapilacak) }} bugün yapılacak</span>
    @if ($kritikSayi > 0)<span class="sb-roz" style="background:rgba(245,87,108,.13);border-color:transparent;color:var(--pred)"><span class="material-icons-outlined">warning_amber</span>{{ $kritikSayi }} kritik</span>@endif
    @if ($gecikenSip > 0)<span class="sb-roz" style="background:rgba(246,166,35,.15);border-color:transparent;color:var(--pamber)"><span class="material-icons-outlined">local_shipping</span>{{ $gecikenSip }} geciken</span>@endif
  </div>
</div>

{{-- 5 KPI (referans gibi) --}}
<div class="sb-kpis">
  <div class="sb-k">
    <div class="kic t-rose"><span class="material-icons-outlined">payments</span></div>
    <div class="kv">{{ $tl($ayHarcama) }}</div><div class="kl">Bu ay harcama</div>
    @if ($ayButce > 0)<div class="ks" style="color:{{ $butceC }}">bütçe %{{ $butceYuzde }}</div>@else<div class="ks">(bütçe tanımsız)</div>@endif
  </div>
  <div class="sb-k">
    <div class="kic t-amber"><span class="material-icons-outlined">inventory_2</span></div>
    <div class="kv" style="color:{{ $kritikSayi>0?'var(--pred)':'var(--ink)' }}">{{ $kritikSayi }} <small>ürün</small></div><div class="kl">Kritik stok</div>
    <div class="ks">{{ $kritikSayi>0?'acil sipariş gerekli':'sorun yok 👍' }}</div>
  </div>
  <div class="sb-k">
    <div class="kic t-blue"><span class="material-icons-outlined">assignment</span></div>
    <div class="kv">{{ $yeniTalep }}</div><div class="kl">Yeni talep</div><div class="ks">inceleme bekliyor</div>
  </div>
  <div class="sb-k">
    <div class="kic t-mor"><span class="material-icons-outlined">request_quote</span></div>
    <div class="kv">{{ $teklifBekleyen }}</div><div class="kl">Teklif</div><div class="ks">karar bekliyor</div>
  </div>
  <div class="sb-k">
    <div class="kic t-red"><span class="material-icons-outlined">local_shipping</span></div>
    <div class="kv">{{ $gecikenSip }}</div><div class="kl">Geciken sipariş</div><div class="ks">{{ $acikSip }} açık sipariş</div>
  </div>
</div>

{{-- MID: Bugün Yapılacaklar (sol) + Patron AI ne öneriyor (sağ) --}}
<div class="sb-mid">
  <div class="sb-bugun-blok">
    <div class="sb-ch"><span class="ikon-ct" style="background:linear-gradient(135deg,#7B5CFA,#4F46E5)"><span class="material-icons-outlined">track_changes</span></span> Bugün Yapılacaklar <span class="sayi">{{ count($bugunYapilacak) }}</span></div>
    @forelse ($bugunYapilacak as $i => $y)
      <div class="sb-yap-sat oncelik-{{ $y['oncelik'] }}" data-ix="{{ $i }}">
        <div class="sb-yap-head">
          <div class="sb-yap-ikon" style="background:{{ $y['renk'] }}">
            <span class="material-icons-outlined">{{ $y['ikon'] }}</span>
          </div>
          <div class="sb-yap-ic">
            <div class="sb-yap-bas">{{ $y['baslik'] }}</div>
            @if (!empty($y['aciklama']))<div class="sb-yap-aci">{{ $y['aciklama'] }}</div>@endif
          </div>
          <span class="sb-yap-onc-roz {{ $y['oncelik'] }}">{{ ['yuksek' => 'ACİL', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$y['oncelik']] }}</span>
        </div>
        <div class="sb-yap-btn-grup">
          @if ($y['tip'] === 'eylem')
            <span class="kilit"><span class="material-icons-outlined">lock</span> Onay IP ile imzalı log'a yazılacak</span>
            <button class="sb-btn-yap" data-vars-metin="{{ $y['buton'] ?? 'İşlemi Yap' }}" onclick="briefYap(this, '{{ $y['intent'] }}', {{ json_encode($y['params'] ?? []) }})">
              <span class="material-icons-outlined">check</span> {{ $y['buton'] ?? 'İşlemi Yap' }}
            </button>
          @else
            <a href="{{ $y['url'] }}" class="sb-btn-link"><span class="material-icons-outlined">arrow_forward</span> Aç</a>
          @endif
        </div>
        <div class="sb-yap-sonuc"></div>
      </div>
    @empty
      <div class="sb-bugun-bos">
        <span class="material-icons-outlined">check_circle</span>
        <div>Her şey yolunda — bugün için acil aksiyon yok.</div>
        <div style="font-size:11.5px;margin-top:4px">Par-seviye kontrollerini ve fiyat takibini sürdür.</div>
      </div>
    @endforelse
  </div>

  {{-- PATRON AI NE ÖNERİYOR (sağ) --}}
  <div class="sb-aipanel">
    <div class="ust"><div class="rob"><span class="material-icons-outlined">smart_toy</span></div><div class="bas">Patron AI ne öneriyor?</div></div>
    <div class="verdict">{!! $aiOzet !!}</div>
    <div class="yapar"><span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">auto_awesome</span> Hızlı işlemler <span class="mini">AI</span></div>
    <div class="sb-aiact">
      <a href="/satinalma-talep"><span class="aic"><span class="material-icons-outlined">assignment</span></span>Yeni talepleri incele</a>
      <a href="/gelen-teklifler"><span class="aic"><span class="material-icons-outlined">request_quote</span></span>Gelen teklifleri karşılaştır</a>
      <a href="/satinalma-siparis"><span class="aic"><span class="material-icons-outlined">shopping_cart_checkout</span></span>Siparişleri yönet</a>
      <a href="/mal-kabul-liste"><span class="aic"><span class="material-icons-outlined">inventory</span></span>Mal kabulleri aç</a>
    </div>
  </div>
</div>

{{-- DÜN YAPILANLAR (altta) --}}
<div class="sb-dun-blok" style="margin-bottom:13px">
  <div class="sb-ch"><span class="ikon-ct" style="background:linear-gradient(135deg,#2FBE94,#059669)"><span class="material-icons-outlined">done_all</span></span> Dün Yapılanlar</div>
  @forelse ($dunYapilanlar as $y)
    <div class="sb-dun-sat"><div class="check"><span class="material-icons-outlined">check</span></div><div class="mtn">{{ $y['mtn'] }}</div>@if (!empty($y['link']))<a href="{{ $y['link'] }}">Aç →</a>@endif</div>
  @empty
    <div class="sb-dun-bos">Dün hareket yok. <span style="font-size:11.5px">Belki rapor günüdür?</span></div>
  @endforelse
</div>

{{-- YAKLAŞAN TESLİMLER --}}
@if ($yaklasanTeslimler->count())
<div class="sb-blok">
  <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">local_shipping</span> Yaklaşan Teslimler <span class="sage">önümüzdeki 7 gün</span></h4>
  @foreach ($yaklasanTeslimler as $s)
    @php
      $bt = \Carbon\Carbon::parse($s->beklenen_teslim)->startOfDay();
      $bg = (int) today()->startOfDay()->diffInDays($bt, false);
      if ($bg < 0)       { $dm = abs($bg) . ' gün geciken'; $dc = '#F43F5E'; }
      elseif ($bg === 0) { $dm = 'Bugün'; $dc = '#F59E0B'; }
      elseif ($bg === 1) { $dm = 'Yarın'; $dc = '#F59E0B'; }
      else               { $dm = $bg . ' gün sonra'; $dc = '#4F46E5'; }
    @endphp
    <div class="sb-sat">
      <span class="material-icons-outlined" style="font-size:18px;color:var(--sub);flex-shrink:0">inventory_2</span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600">{{ $s->tedarikci ?: 'Tedarikçi —' }}</div>
        <div style="font-size:11.5px;color:var(--sub)">{{ $s->siparis_no ?: '#'.$s->id }} · {{ $tl($s->genel_toplam) }}</div>
      </div>
      <span class="roz-k" style="background:{{ $dc }}">{{ $dm }}</span>
    </div>
  @endforeach
</div>
@endif

{{-- FİYAT HAREKETLERİ --}}
@if ($fiyatHareketleri->count())
<div class="sb-blok">
  <h4><span class="material-icons-outlined" style="font-size:17px;color:#EC4899">trending_up</span> Son Fiyat Hareketleri <span class="sage">30 gün</span></h4>
  @foreach ($fiyatHareketleri as $f)
    @php $rnk = $f->uyari === 'kirmizi' ? '#F43F5E' : '#F59E0B'; $yz = (float) $f->fiyat_farki_yuzde; @endphp
    <div class="sb-sat">
      <span class="material-icons-outlined" style="font-size:18px;color:{{ $rnk }}">{{ $yz > 0 ? 'north_east' : 'south_east' }}</span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600">{{ $f->ad }}</div>
        <div style="font-size:11.5px;color:var(--sub)">{{ number_format((float) $f->birim_fiyat, 2, ',', '.') }}₺ · {{ \Carbon\Carbon::parse($f->tarih)->diffForHumans() }}</div>
      </div>
      <span class="roz-k" style="background:{{ $rnk }}">{{ $yz > 0 ? '+' : '' }}{{ number_format($yz, 1, ',', '.') }}%</span>
    </div>
  @endforeach
</div>
@endif

{{-- Başarı Toast (sağ üst) --}}
<div class="sb-toast" id="sbToast">
  <span class="material-icons-outlined">check_circle</span>
  <div class="mt"><b id="sbToastBaslik">İşlem tamamlandı</b><span id="sbToastMetin"></span></div>
</div>

{{-- RAKAM ŞERİDİ --}}
<div class="sb-mini-bar">
  <span class="chip">💰 Bu ay {{ $tl($ayHarcama) }}</span>
  @if ($ayButce > 0)<span class="chip" style="color:{{ $butceC }}">🎯 Bütçe %{{ $butceYuzde }}</span>@endif
  <span class="chip">📄 Dün {{ $dunGelenFatura }} fatura</span>
  <span class="chip">📦 Dün {{ $dunMalKabul }} mal kabul</span>
  <span class="chip">⚠️ {{ $kritikSayi }} kritik</span>
  <span class="chip">🚚 {{ $gecikenSip }} geciken</span>
  <a href="/satinalma-ozet" class="link">Detaylı özet <span class="material-icons-outlined" style="font-size:14px;vertical-align:-3px">arrow_forward</span></a>
</div>

</div>
</div>

@push('scripts')
<script>
// intent -> yürütme sonrası kullanıcıyı yönlendirmek için ilgili ekran
const BRIEF_SONUC_LINK = {
  'kritik_siparis_olustur': {'url':'/satinalma-siparis', 'lbl':'Siparişleri Aç'},
  'tedarikci_hatirlat':     {'url':'/satinalma-siparis', 'lbl':'Siparişleri Aç'},
  'mal_kabul_onayla':       {'url':'/mal-kabul-liste', 'lbl':'Mal Kabulü Aç'},
};

// Toast — sağ üstte 4.5 sn görünür
let _sbToastTimer = null;
function sbToastGoster(baslik, metin){
  const t = document.getElementById('sbToast');
  document.getElementById('sbToastBaslik').textContent = baslik;
  document.getElementById('sbToastMetin').textContent = (metin || '').replace(/^✓\s*/, '');
  t.classList.remove('cikis'); t.classList.add('goruli');
  clearTimeout(_sbToastTimer);
  _sbToastTimer = setTimeout(() => {
    t.classList.add('cikis');
    setTimeout(() => { t.classList.remove('goruli', 'cikis'); }, 300);
  }, 4500);
}

async function briefYap(btn, intent, params){
  const row = btn.closest('.sb-yap-sat');
  const son = row.querySelector('.sb-yap-sonuc');
  btn.disabled = true; btn.innerHTML = '<span class="material-icons-outlined">hourglass_bottom</span> Yürütülüyor...';
  try {
    // 1) Eylem onerisi oluştur: brief-özel endpoint intent + params'ı doğrudan alır, log yazar
    const r1 = await fetch('/api/satinalma-brief/eylem-oner', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json'},
      body: JSON.stringify({intent, params})
    });
    const j1 = await r1.json();
    if (!j1 || !j1.ok || !j1.log_id) {
      son.className = 'sb-yap-sonuc hata';
      son.textContent = 'Öneri oluşturulamadı: ' + (j1.hata || 'bilinmeyen hata');
      btn.disabled = false; btn.innerHTML = '<span class="material-icons-outlined">check</span> ' + (btn.dataset.varsMetin || 'İşlemi Yap');
      return;
    }
    // 2) Onayla (ortak endpoint): dispatch satınalma modülüne gider, yürütür, zincire yazar
    const r2 = await fetch('/api/ai-eylem/onayla', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json'},
      body: JSON.stringify({log_id: j1.log_id})
    });
    const j2 = await r2.json();
    const s = (j2 && j2.sonuc) || {};
    const mesaj = s.mesaj || (s.ok ? 'Yürütüldü.' : 'Hata oluştu.');
    if (s.ok) {
      // Prominent success state - büyük ikon + bold başlık + mesaj
      son.className = 'sb-yap-sonuc ok';
      son.innerHTML = '<div class="ok-ikon"><span class="material-icons-outlined">check</span></div>' +
                      '<div class="ok-metin"><b>İŞLEM BAŞARIYLA TAMAMLANDI</b>' + mesaj.replace(/</g, '&lt;') + '</div>';
      // Toast (sağ üstte, 4.5 sn görünür)
      sbToastGoster('İşlem tamamlandı', mesaj);
    } else {
      son.className = 'sb-yap-sonuc hata';
      son.textContent = '⚠️ ' + mesaj;
    }
    if (s.ok) {
      // Basari: butonu "tamam" state'ine cevir, cursor:wait kalmasin
      btn.disabled = false;
      btn.classList.add('ok-tamam');
      btn.innerHTML = '<span class="material-icons-outlined">check_circle</span> Tamamlandı';
      btn.onclick = null;
      row.classList.add('tamam');
      row.querySelector('.kilit').style.display = 'none';
      // Ilgili sayfaya hizli link ekle
      const link = BRIEF_SONUC_LINK[intent];
      if (link && !row.querySelector('.sb-ac-link')) {
        const a = document.createElement('a');
        a.className = 'sb-ac-link'; a.href = link.url;
        a.innerHTML = link.lbl + ' <span class="material-icons-outlined">arrow_forward</span>';
        btn.insertAdjacentElement('afterend', a);
      }
    } else {
      btn.disabled = false;
      btn.innerHTML = '<span class="material-icons-outlined">check</span> İşlemi Yap';
    }
  } catch (e) {
    son.className = 'sb-yap-sonuc hata'; son.textContent = 'Bağlantı hatası.';
    btn.disabled = false; btn.innerHTML = '<span class="material-icons-outlined">check</span> İşlemi Yap';
  }
}
</script>
@endpush

@endsection

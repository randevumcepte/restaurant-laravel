@extends('layout.app')
@section('rk_ikon', 'groups')
@section('baslik', 'İnsan Kaynakları')
@section('title', 'İnsan Kaynakları — Özet')

@push('head')
<style>
/* ===== TEMA (dashboard dili) ===== */
#iko{
  --mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;
  --amber:#F59E0B;--gold:#F6CE63;--pembe:#EC4899;--turuncu:#F97316;--teal:#14B8A6;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
}
body.koyu #iko{
  --card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
}
#iko{background:var(--bg);min-height:calc(100vh - 60px)}
#iko *{box-sizing:border-box}
#iko .pano{max-width:1280px;margin:0 auto;padding:18px 20px 34px}

/* selam kartı */
#iko .selam{background:var(--card);border-radius:18px;box-shadow:0 2px 10px rgba(0,0,0,.07);padding:15px 18px;display:flex;align-items:center;gap:13px;margin-bottom:16px}
#iko .selam-ik{width:48px;height:48px;border-radius:15px;background:linear-gradient(135deg,#10B981,#059669);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(16,185,129,.35)}
#iko .selam-ik .material-icons-outlined{color:#fff;font-size:26px}
#iko .selam .ad{font-size:18px;font-weight:800;color:var(--ink);line-height:1.15}
#iko .selam .alt{font-size:12.5px;color:var(--sub)}
#iko .durum-rozet{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:800;color:#fff;letter-spacing:.3px}
#iko .selam-cip{display:inline-flex;align-items:center;gap:5px;padding:8px 11px;border-radius:11px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;background:var(--card2);color:var(--sub)}
#iko .selam-cip .material-icons-outlined{font-size:16px}

/* AI anlatı */
#iko .ai-kart{background:linear-gradient(135deg,rgba(124,58,237,.07),rgba(79,70,229,.05));border:1px solid rgba(124,58,237,.18);border-radius:18px;padding:16px 18px;margin-bottom:16px;position:relative;overflow:hidden}
#iko .ai-kart::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(180deg,var(--mor1),var(--mavi))}
#iko .ai-h{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:800;color:var(--mor1);margin-bottom:9px}
#iko .ai-yorum{font-size:14.5px;line-height:1.65;color:var(--ink)}
#iko .ai-blok{margin-top:10px;padding:10px 13px;border-radius:12px;font-size:13px;line-height:1.5}

/* 3 büyük kart */
#iko .uc{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
#iko .bk{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden;display:flex}
#iko .bk .serit{width:5px;flex-shrink:0}
#iko .bk .ic{padding:15px 16px;flex:1;min-width:0;display:flex;align-items:center;gap:14px}
#iko .bk .sol{flex:1;min-width:0}
#iko .bk .brow{display:flex;align-items:center;gap:8px;margin-bottom:9px}
#iko .bk .bik{width:31px;height:31px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .bk .bik .material-icons-outlined{font-size:18px}
#iko .bk .blbl{font-size:12.5px;font-weight:700;color:var(--sub)}
#iko .bk .bdeger{font-size:30px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1}
#iko .bk .balt{font-size:11.5px;color:var(--sub);margin-top:5px}
/* donut */
#iko .donut{position:relative;width:74px;height:74px;flex-shrink:0}
#iko .donut svg{transform:rotate(-90deg)}
#iko .donut .dy{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;font-weight:800}
#iko .donut .dy .b{font-size:17px;line-height:1}
#iko .donut .dy .s{font-size:8.5px;color:var(--sub);font-weight:600}

/* 4 mini kart */
#iko .dort{display:grid;grid-template-columns:repeat(4,1fr);gap:11px;margin-bottom:16px}
#iko .mk{background:var(--card);border-radius:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:13px 14px}
#iko .mk .mrow{display:flex;align-items:center;gap:8px;margin-bottom:9px}
#iko .mk .mik{width:27px;height:27px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .mk .mik .material-icons-outlined{font-size:16px}
#iko .mk .mlbl{font-size:11.5px;font-weight:700;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#iko .mk .mdeger{font-size:23px;font-weight:800;color:var(--ink);letter-spacing:-.5px;line-height:1}
#iko .mk .malt{font-size:11px;color:var(--sub);margin-top:3px}

/* genel kart */
#iko .kart{background:var(--card);border-radius:18px;box-shadow:0 2px 8px rgba(0,0,0,.07);padding:16px 18px;margin-bottom:16px}
#iko .kh{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:13px}
#iko .kh .material-icons-outlined{font-size:19px}
#iko .kh a{margin-left:auto;font-size:12px;font-weight:700;color:var(--mavi);text-decoration:none}

/* hatırlatıcı grid */
#iko .hat{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
#iko .hk{border-radius:16px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);display:flex}
#iko .hk .hserit{width:5px;flex-shrink:0}
#iko .hk .hic{padding:13px 14px;flex:1;min-width:0}
#iko .hk .ht{display:flex;align-items:center;gap:7px;font-size:12.5px;font-weight:800;margin-bottom:9px}
#iko .hk .ht .material-icons-outlined{font-size:16px}
#iko .hk .hsatir{display:flex;justify-content:space-between;gap:8px;font-size:12.5px;padding:3px 0}
#iko .hk .hsatir a{color:var(--ink);text-decoration:none}
#iko .hk .hsatir .s{color:var(--sub);font-size:11.5px;white-space:nowrap}

/* izin pill */
#iko .pill{display:inline-flex;align-items:center;gap:5px;padding:8px 14px;border-radius:12px;font-size:13px;font-weight:700}

/* aksiyon */
#iko .aks{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:11px}
#iko .ak{display:flex;align-items:center;gap:11px;padding:13px 15px;border-radius:14px;background:var(--card);box-shadow:0 2px 8px rgba(0,0,0,.06);text-decoration:none;transition:transform .12s,box-shadow .12s}
#iko .ak:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.12)}
#iko .ak .aik{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#iko .ak .aik .material-icons-outlined{font-size:21px;color:#fff}
#iko .ak .at{font-size:13.5px;font-weight:700;color:var(--ink)}
#iko .ak .ao{font-size:11px;color:var(--sub)}

@media(max-width:860px){#iko .uc,#iko .dort,#iko .hat{grid-template-columns:1fr 1fr}}
@media(max-width:560px){#iko .uc,#iko .dort,#iko .hat{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div id="iko">
<div class="pano">
  @php
    $tl = fn($n)=>number_format((float)($n??0),0,',','.').'₺';
    $vdMap = ['iyi'=>['İYİ','#10B981'],'notr'=>['NÖTR','#94a3b8'],'dikkat'=>['DİKKAT','#F59E0B'],'risk'=>['RİSK','#F43F5E']];
    [$durAd,$durRenk] = $vdMap[$veri['durum']] ?? $vdMap['iyi'];
    $aiVar = ($ai['kaynak'] ?? '')==='ai' && !empty($ai['yorum']);
    $iy = $veri['iscilik_yuzde'];
    $sagRenk = ['iyi'=>'#10B981','dikkat'=>'#F59E0B','risk'=>'#F43F5E'][$veri['iscilik_saglik']] ?? '#4F46E5';
    // donut: işçilik %0-50 skala
    $cevre = 169.646;
    $oran = $iy===null ? 0 : min(1, $iy/50);
    $offset = $cevre * (1-$oran);
    $b = $veri['bordro']; $kd = $veri['kadro']; $dv = $veri['devam']; $iz = $veri['izin'];
    $ayAd = \Carbon\Carbon::parse($veri['ay'].'-01')->translatedFormat('F Y');
    $renkMap = ['kirmizi'=>'#F43F5E','amber'=>'#F59E0B','mavi'=>'#4F46E5','yesil'=>'#10B981','mor'=>'#7C3AED'];
  @endphp

  {{-- SELAM --}}
  <div class="selam">
    <div class="selam-ik"><span class="material-icons-outlined">groups</span></div>
    <div style="flex:1;min-width:0">
      <div class="ad">İnsan Kaynakları</div>
      <div class="alt">{{ $ayAd }} özeti · İdari Ofis</div>
    </div>
    <span class="durum-rozet" style="background:{{ $durRenk }}">{{ $durAd }}</span>
    <a href="/ik-ozet?force=1" class="selam-cip" title="Yenile"><span class="material-icons-outlined">refresh</span></a>
  </div>

  @if (session('ok'))   <div class="kart" style="border-left:4px solid var(--yesil);color:var(--yesil);font-weight:600;padding:12px 16px">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="kart" style="border-left:4px solid var(--kirmizi);color:var(--kirmizi);font-weight:600;padding:12px 16px">{{ session('hata') }}</div> @endif

  {{-- AI / DURUM ANLATI --}}
  <div class="ai-kart">
    <div class="ai-h"><span class="material-icons-outlined" style="font-size:18px">{{ $aiVar ? 'auto_awesome' : 'insights' }}</span> {{ $aiVar ? 'İK Analizi' : 'Durum Özeti' }}</div>
    @if ($aiVar)
      <div class="ai-yorum">{{ $ai['yorum'] }}</div>
      @if (!empty($ai['sorunlar']))
        <div class="ai-blok" style="background:rgba(244,63,94,.10);color:var(--kirmizi)">
          <b>🔴 Dikkat</b>
          @foreach ($ai['sorunlar'] as $s)<div>• {{ $s }}</div>@endforeach
        </div>
      @endif
      @if (!empty($ai['oneriler']))
        <div class="ai-blok" style="background:var(--card2);color:var(--sub2)">
          <b style="color:var(--ink)">✅ Öneriler</b>
          @foreach ($ai['oneriler'] as $o)<div>• {{ $o }}</div>@endforeach
        </div>
      @endif
    @else
      <div class="ai-yorum">{{ $veri['ozet'] }}</div>
      @foreach (($veri['uyari'] ?? []) as $u)
        <div class="ai-blok" style="background:rgba(245,158,11,.12);color:var(--amber)">⚠️ {{ $u }}</div>
      @endforeach
      @if (config('services.resteos.patron_ai_v2', false) === false)
        <div style="font-size:11px;color:var(--sub);margin-top:9px">💡 Derin İK analizi için <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
      @endif
    @endif
  </div>

  {{-- 3 BÜYÜK KART --}}
  <div class="uc">
    {{-- İşçilik / Ciro (donut) --}}
    <div class="bk">
      <div class="serit" style="background:{{ $sagRenk }}"></div>
      <div class="ic">
        <div class="sol">
          <div class="brow"><div class="bik" style="background:{{ $sagRenk }}22;color:{{ $sagRenk }}"><span class="material-icons-outlined">payments</span></div><div class="blbl">İşçilik / Ciro</div></div>
          <div class="bdeger" style="color:{{ $sagRenk }}">{{ $iy===null ? '—' : '%'.$iy }}</div>
          <div class="balt">{{ $tl($b['hakedis']) }} bordro / {{ $tl($veri['ciro_ay']) }} ciro<br><span style="color:var(--yesil)">sağlıklı bant %25–35</span></div>
        </div>
        <div class="donut">
          <svg width="74" height="74" viewBox="0 0 74 74">
            <circle cx="37" cy="37" r="27" fill="none" stroke="var(--card2)" stroke-width="7"/>
            <circle cx="37" cy="37" r="27" fill="none" stroke="{{ $sagRenk }}" stroke-width="7" stroke-linecap="round"
              stroke-dasharray="{{ $cevre }}" stroke-dashoffset="{{ $offset }}"/>
          </svg>
          <div class="dy" style="color:{{ $sagRenk }}"><div class="b">{{ $iy===null ? '—' : '%'.$iy }}</div><div class="s">işçilik</div></div>
        </div>
      </div>
    </div>
    {{-- Bugün Devam --}}
    <div class="bk">
      <div class="serit" style="background:var(--mavi)"></div>
      <div class="ic"><div class="sol">
        <div class="brow"><div class="bik" style="background:rgba(79,70,229,.13);color:var(--mavi)"><span class="material-icons-outlined">how_to_reg</span></div><div class="blbl">Bugün Devam</div></div>
        <div class="bdeger">{{ $dv['gelen']===null ? '—' : $dv['gelen'] }}<span style="font-size:17px;color:var(--sub);font-weight:700"> / {{ $dv['beklenen'] }}</span></div>
        <div class="balt">
          @php $gelmeyen = $dv['gelen']===null ? null : max(0,$dv['beklenen']-$dv['gelen']); @endphp
          şu an mesaide <b style="color:var(--ink)">{{ $dv['mesaide'] ?? 0 }}</b>
          @if ($gelmeyen)<br><span style="color:var(--amber);font-weight:700">{{ $gelmeyen }} kişi henüz gelmedi</span>@endif
        </div>
      </div></div>
    </div>
    {{-- Bu Ay Bordro --}}
    <div class="bk">
      <div class="serit" style="background:var(--mor1)"></div>
      <div class="ic"><div class="sol">
        <div class="brow"><div class="bik" style="background:rgba(124,58,237,.13);color:var(--mor1)"><span class="material-icons-outlined">account_balance_wallet</span></div><div class="blbl">Bu Ay Bordro</div></div>
        <div class="bdeger">{{ $tl($b['hakedis']) }}</div>
        <div class="balt">Ödenen <b style="color:var(--yesil)">{{ $tl($b['odenen']) }}</b> · Kalan <b style="color:var(--kirmizi)">{{ $tl($b['net']) }}</b></div>
      </div></div>
    </div>
  </div>

  {{-- 4 MİNİ KART --}}
  <div class="dort">
    <div class="mk"><div class="mrow"><div class="mik" style="background:rgba(16,185,129,.13);color:var(--yesil)"><span class="material-icons-outlined">badge</span></div><div class="mlbl">Aktif Personel</div></div><div class="mdeger">{{ $kd['aktif'] }}</div><div class="malt">kadro</div></div>
    <div class="mk"><div class="mrow"><div class="mik" style="background:rgba(245,158,11,.13);color:var(--amber)"><span class="material-icons-outlined">event_available</span></div><div class="mlbl">Bekleyen İzin</div></div><div class="mdeger" style="color:{{ $iz['bekleyen']>0?'var(--amber)':'var(--ink)' }}">{{ $iz['bekleyen'] }}</div><div class="malt">onay bekliyor</div></div>
    <div class="mk"><div class="mrow"><div class="mik" style="background:rgba(16,185,129,.13);color:var(--yesil)"><span class="material-icons-outlined">person_add</span></div><div class="mlbl">Bu Ay İşe Giren</div></div><div class="mdeger" style="color:var(--yesil)">+{{ $kd['yeni_giren'] }}</div><div class="malt">yeni katılan</div></div>
    <div class="mk"><div class="mrow"><div class="mik" style="background:rgba(244,63,94,.13);color:var(--kirmizi)"><span class="material-icons-outlined">person_remove</span></div><div class="mlbl">Bu Ay Ayrılan</div></div><div class="mdeger" style="color:{{ $kd['ayrilan']>0?'var(--kirmizi)':'var(--ink)' }}">−{{ $kd['ayrilan'] }}</div><div class="malt">devir</div></div>
  </div>

  {{-- İZİN DURUMU --}}
  <div class="kart">
    <div class="kh"><span class="material-icons-outlined" style="color:var(--mavi)">event_available</span> İzin Durumu <a href="/ik-izin">İzin Yönetimi →</a></div>
    <div style="display:flex;gap:11px;flex-wrap:wrap;align-items:center">
      @if ($iz['bekleyen']>0)
        <a href="/ik-izin" class="pill" style="background:rgba(245,158,11,.14);color:var(--amber);text-decoration:none">⏳ {{ $iz['bekleyen'] }} talep onay bekliyor</a>
      @else
        <span class="pill" style="background:rgba(16,185,129,.12);color:var(--yesil)">✓ Bekleyen izin talebi yok</span>
      @endif
      @if (count($iz['bugun_izinli']))
        <span style="font-size:12.5px;color:var(--sub)">Bugün izinli: <b style="color:var(--ink)">{{ implode(', ', $iz['bugun_izinli']) }}</b></span>
      @endif
    </div>
  </div>

  {{-- HATIRLATICILAR --}}
  @if (count($veri['dogum_gunleri']) || count($veri['yildonumleri']) || count($veri['eksik_belge']))
  <div class="kart">
    <div class="kh"><span class="material-icons-outlined" style="color:var(--mor1)">notifications_active</span> Hatırlatıcılar</div>
    <div class="hat">
      @if (count($veri['dogum_gunleri']))
      <div class="hk"><div class="hserit" style="background:var(--pembe)"></div><div class="hic">
        <div class="ht" style="color:var(--pembe)"><span class="material-icons-outlined">cake</span> Yaklaşan Doğum Günleri</div>
        @foreach ($veri['dogum_gunleri'] as $d)<div class="hsatir"><span>{{ $d['ad'] }}</span><span class="s">{{ $d['tarih'] }} · {{ $d['gun'] }}</span></div>@endforeach
      </div></div>
      @endif
      @if (count($veri['yildonumleri']))
      <div class="hk"><div class="hserit" style="background:var(--mor1)"></div><div class="hic">
        <div class="ht" style="color:var(--mor1)"><span class="material-icons-outlined">celebration</span> İşe Giriş Yıldönümü</div>
        @foreach ($veri['yildonumleri'] as $y)<div class="hsatir"><span>{{ $y['ad'] }}</span><span class="s">{{ $y['yil'] }}. yıl · {{ $y['tarih'] }}</span></div>@endforeach
      </div></div>
      @endif
      @if (count($veri['eksik_belge']))
      <div class="hk"><div class="hserit" style="background:var(--amber)"></div><div class="hic">
        <div class="ht" style="color:var(--amber)"><span class="material-icons-outlined">assignment_late</span> Eksik Özlük ({{ count($veri['eksik_belge']) }})</div>
        @foreach (array_slice($veri['eksik_belge'],0,6) as $e)<div class="hsatir"><a href="/ik-sicil?id={{ $e['id'] }}">{{ $e['ad'] }}</a><span class="s">{{ implode(', ', $e['eksik']) }}</span></div>@endforeach
      </div></div>
      @endif
    </div>
  </div>
  @endif

  {{-- HIZLI AKSİYONLAR --}}
  @php $linkler = array_values(array_filter($veri['aksiyonlar'], fn($a)=>($a['tip']??'')==='link')); @endphp
  @if (count($linkler))
  <div class="kart">
    <div class="kh"><span class="material-icons-outlined" style="color:var(--mor1)">bolt</span> Hızlı Erişim</div>
    <div class="aks">
      @foreach ($linkler as $a)
        @php $rk = $renkMap[$a['renk']??'mavi'] ?? '#4F46E5'; @endphp
        <a href="{{ $a['url'] }}" class="ak">
          <div class="aik" style="background:linear-gradient(135deg,{{ $rk }},{{ $rk }}cc)"><span class="material-icons-outlined">{{ $a['ikon']??'arrow_forward' }}</span></div>
          <div><div class="at">{{ $a['lbl'] }}</div><div class="ao">aç →</div></div>
        </a>
      @endforeach
    </div>
  </div>
  @endif

</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'groups')
@section('baslik', 'İnsan Kaynakları')
@section('title', 'İnsan Kaynakları — Özet')

@push('head')
<style>
  #ik{max-width:960px}
  .ik-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .ik-h{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 12px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .ik-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
  .ik-kpi .k{background:var(--card2);border-radius:12px;padding:12px 14px}
  .ik-kpi .k .v{font-size:19px;font-weight:800;color:var(--ink)}
  .ik-kpi .k .l{font-size:11.5px;color:var(--sub);margin-top:2px}
  .ik-vd{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;letter-spacing:.4px}
  .ik-oz{display:flex;gap:8px;align-items:flex-start;font-size:13px;line-height:1.5;padding:9px 11px;border-radius:10px;margin-top:7px}
  .ik-gauge{height:12px;border-radius:8px;background:var(--card2);overflow:hidden;position:relative}
  .ik-gauge .fill{height:100%;border-radius:8px}
  .ik-mini{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px}
  .ik-card{background:var(--card2);border-radius:12px;padding:12px 14px}
  .ik-list .row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--line)}
  .ik-list .row:last-child{border-bottom:none}
  .ik-pill{font-size:11px;font-weight:700;padding:2px 9px;border-radius:20px}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="ik">
  @php
    $vd = ['iyi'=>['İYİ','var(--yesil)'],'notr'=>['NÖTR','var(--sub)'],'dikkat'=>['DİKKAT','var(--amber)'],'risk'=>['RİSK','var(--kirmizi)']];
    [$det,$drk] = $vd[$veri['durum']] ?? $vd['iyi'];
    $aiVar = ($ai['kaynak'] ?? '') === 'ai' && !empty($ai['yorum']);
    $iy = $veri['iscilik_yuzde'];
    $saglikRenk = ['iyi'=>'var(--yesil)','dikkat'=>'var(--amber)','risk'=>'var(--kirmizi)'][$veri['iscilik_saglik']] ?? 'var(--mavi)';
    $renkMap = ['kirmizi'=>'var(--kirmizi)','amber'=>'var(--amber)','mavi'=>'var(--mavi)','yesil'=>'var(--yesil)','mor'=>'var(--mor1)'];
    $b = $veri['bordro']; $kd = $veri['kadro']; $dv = $veri['devam'];
    $tl = fn($n)=>number_format((float)($n??0),0,',','.').'₺';
  @endphp

  {{-- Başlık --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <span class="material-icons-outlined" style="font-size:30px;color:#10B981">groups</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">İnsan Kaynakları</div>
      <div class="rk-sub" style="font-size:12px">{{ \Carbon\Carbon::parse($veri['ay'].'-01')->translatedFormat('F Y') }} özeti · İdari Ofis</div>
    </div>
    <span class="ik-vd" style="background:{{ $drk }}">{{ $det }}</span>
    <a href="/ik-ozet?force=1" class="rk-btn bos" style="padding:6px 10px" title="AI'yı yenile"><span class="material-icons-outlined" style="font-size:16px">refresh</span></a>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- AI / DURUM ÖZETİ --}}
  <div class="ik-blok" style="border-left:4px solid var(--mor1);{{ $aiVar ? 'background:linear-gradient(135deg,rgba(124,58,237,.06),rgba(79,70,229,.06))' : '' }}">
    <div class="ik-h" style="color:var(--mor1)"><span class="material-icons-outlined" style="font-size:17px">{{ $aiVar ? 'auto_awesome' : 'insights' }}</span> {{ $aiVar ? 'İK Analizi' : 'Durum Özeti' }}</div>
    @if ($aiVar)
      <div style="font-size:14.5px;line-height:1.65;color:var(--ink)">{{ $ai['yorum'] }}</div>
      @if (!empty($ai['sorunlar']))
        <div class="ik-oz" style="background:rgba(244,63,94,.10);color:var(--kirmizi);flex-direction:column;align-items:stretch">
          <b>🔴 Dikkat edilmesi gerekenler</b>
          @foreach ($ai['sorunlar'] as $s)<div>• {{ $s }}</div>@endforeach
        </div>
      @endif
      @if (!empty($ai['oneriler']))
        <div class="ik-oz" style="background:var(--card2);flex-direction:column;align-items:stretch">
          <b style="color:var(--ink)">✅ Öneriler</b>
          @foreach ($ai['oneriler'] as $o)<div style="color:var(--sub2)">• {{ $o }}</div>@endforeach
        </div>
      @endif
    @else
      <div style="font-size:14px;line-height:1.6;color:var(--ink)">{{ $veri['ozet'] }}</div>
      @foreach (($veri['uyari'] ?? []) as $u)
        <div class="ik-oz" style="background:rgba(245,158,11,.10);color:var(--amber)"><span>⚠️</span><span>{{ $u }}</span></div>
      @endforeach
      @if (config('services.resteos.patron_ai_v2', false) === false)
        <div class="rk-sub" style="font-size:11px;margin-top:8px">💡 Derin İK analizi için <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
      @endif
    @endif
  </div>

  {{-- İŞÇİLİK MALİYETİ (ana metrik) --}}
  <div class="ik-blok">
    <div class="ik-h"><span class="material-icons-outlined" style="font-size:17px;color:{{ $saglikRenk }}">payments</span> İşçilik Maliyeti / Ciro</div>
    @if ($iy !== null)
      <div style="display:flex;align-items:baseline;gap:10px;margin-bottom:8px">
        <span style="font-size:34px;font-weight:800;color:{{ $saglikRenk }}">%{{ $iy }}</span>
        <span class="rk-sub" style="font-size:12.5px">{{ $tl($b['hakedis']) }} bordro / {{ $tl($veri['ciro_ay']) }} ciro</span>
      </div>
      <div class="ik-gauge">
        <div class="fill" style="width:{{ min(100, round($iy/50*100)) }}%;background:{{ $saglikRenk }}"></div>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:10.5px;color:var(--sub);margin-top:5px">
        <span>%0</span><span style="color:var(--yesil)">sağlıklı bant %25–35</span><span>%50+</span>
      </div>
    @else
      <div class="rk-sub" style="font-size:13px">Bu ay henüz ciro yok — işçilik oranı hesaplanamıyor.</div>
    @endif
  </div>

  {{-- KPI --}}
  <div class="ik-blok">
    <div class="ik-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">assessment</span> Göstergeler · {{ \Carbon\Carbon::parse($veri['ay'].'-01')->translatedFormat('F Y') }}</div>
    <div class="ik-kpi">
      @foreach ($veri['kpi'] as $k)
        <div class="k"><div class="v">{{ $k[1] }}</div><div class="l">{{ $k[0] }}</div></div>
      @endforeach
    </div>
  </div>

  {{-- BUGÜN DEVAM + KADRO/DEVİR --}}
  <div class="ik-mini" style="margin-bottom:14px">
    <div class="ik-card">
      <div class="ik-h" style="margin-bottom:8px"><span class="material-icons-outlined" style="font-size:16px;color:var(--mavi)">how_to_reg</span> Bugün Devam</div>
      @if ($dv['gelen'] !== null)
        <div style="font-size:24px;font-weight:800;color:var(--ink)">{{ $dv['gelen'] }}<span style="font-size:14px;color:var(--sub)"> / {{ $dv['beklenen'] }}</span></div>
        <div class="rk-sub" style="font-size:12px">kişi bugün işe geldi · şu an mesaide {{ $dv['mesaide'] ?? 0 }}</div>
        @php $gelmeyen = max(0, $dv['beklenen'] - $dv['gelen']); @endphp
        @if ($gelmeyen > 0)<div class="ik-pill" style="background:rgba(245,158,11,.15);color:var(--amber);margin-top:6px;display:inline-block">{{ $gelmeyen }} kişi henüz gelmedi</div>@endif
      @else
        <div class="rk-sub" style="font-size:13px">Puantaj verisi yok. <a href="/puantaj" style="color:var(--mavi)">Puantajı aç →</a></div>
      @endif
    </div>
    <div class="ik-card">
      <div class="ik-h" style="margin-bottom:8px"><span class="material-icons-outlined" style="font-size:16px;color:var(--mor1)">diversity_3</span> Kadro & Devir (bu ay)</div>
      <div style="display:flex;gap:16px">
        <div><div style="font-size:22px;font-weight:800">{{ $kd['aktif'] }}</div><div class="rk-sub" style="font-size:11px">aktif</div></div>
        <div><div style="font-size:22px;font-weight:800;color:var(--yesil)">+{{ $kd['yeni_giren'] }}</div><div class="rk-sub" style="font-size:11px">işe giren</div></div>
        <div><div style="font-size:22px;font-weight:800;color:var(--kirmizi)">−{{ $kd['ayrilan'] }}</div><div class="rk-sub" style="font-size:11px">ayrılan</div></div>
      </div>
    </div>
    <div class="ik-card">
      <div class="ik-h" style="margin-bottom:8px"><span class="material-icons-outlined" style="font-size:16px;color:var(--yesil)">account_balance_wallet</span> Bordro (bu ay)</div>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:3px 0"><span class="rk-sub">Toplam hakediş</span><b>{{ $tl($b['hakedis']) }}</b></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:3px 0"><span class="rk-sub">Ödenen + avans</span><b style="color:var(--yesil)">{{ $tl($b['odenen']) }}</b></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;padding:3px 0;border-top:1px solid var(--line);margin-top:3px;padding-top:7px"><span class="rk-sub">Kalan ödeme</span><b style="color:var(--kirmizi)">{{ $tl($b['net']) }}</b></div>
    </div>
  </div>

  {{-- İZİN DURUMU --}}
  <div class="ik-blok">
    <div class="ik-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">event_available</span> İzin Durumu
      <a href="/ik-izin" class="rk-sub" style="margin-left:auto;font-size:11px;color:var(--mavi);text-transform:none;font-weight:600">İzin Yönetimi →</a>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
      @if ($veri['izin']['bekleyen'] > 0)
        <a href="/ik-izin" class="ik-pill" style="background:rgba(245,158,11,.15);color:var(--amber);font-size:13px;padding:6px 12px">⏳ {{ $veri['izin']['bekleyen'] }} talep onay bekliyor</a>
      @else
        <span class="ik-pill" style="background:rgba(16,185,129,.12);color:var(--yesil);font-size:13px;padding:6px 12px">✓ Bekleyen izin talebi yok</span>
      @endif
      @if (count($veri['izin']['bugun_izinli']))
        <span class="rk-sub" style="font-size:12.5px">Bugün izinli: <b style="color:var(--ink)">{{ implode(', ', $veri['izin']['bugun_izinli']) }}</b></span>
      @endif
    </div>
  </div>

  {{-- HATIRLATICILAR --}}
  @if (count($veri['dogum_gunleri']) || count($veri['yildonumleri']) || count($veri['eksik_belge']))
  <div class="ik-blok">
    <div class="ik-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">notifications</span> Hatırlatıcılar</div>
    <div class="ik-mini">
      @if (count($veri['dogum_gunleri']))
      <div class="ik-card">
        <div style="font-weight:700;font-size:13px;margin-bottom:6px">🎂 Yaklaşan doğum günleri</div>
        @foreach ($veri['dogum_gunleri'] as $d)
          <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:2px 0"><span>{{ $d['ad'] }}</span><span class="rk-sub">{{ $d['tarih'] }} · {{ $d['gun'] }}</span></div>
        @endforeach
      </div>
      @endif
      @if (count($veri['yildonumleri']))
      <div class="ik-card">
        <div style="font-weight:700;font-size:13px;margin-bottom:6px">🎉 İşe giriş yıldönümü</div>
        @foreach ($veri['yildonumleri'] as $y)
          <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:2px 0"><span>{{ $y['ad'] }}</span><span class="rk-sub">{{ $y['yil'] }}. yıl · {{ $y['tarih'] }}</span></div>
        @endforeach
      </div>
      @endif
      @if (count($veri['eksik_belge']))
      <div class="ik-card" style="border:1px solid rgba(245,158,11,.3)">
        <div style="font-weight:700;font-size:13px;margin-bottom:6px;color:var(--amber)">📋 Eksik özlük bilgisi ({{ count($veri['eksik_belge']) }})</div>
        @foreach (array_slice($veri['eksik_belge'], 0, 8) as $e)
          <div style="display:flex;justify-content:space-between;font-size:12.5px;padding:2px 0"><span>{{ $e['ad'] }}</span><span class="rk-sub">{{ implode(', ', $e['eksik']) }}</span></div>
        @endforeach
        <a href="/personel" class="rk-sub" style="font-size:11px;color:var(--mavi);font-weight:600">Tamamla →</a>
      </div>
      @endif
    </div>
  </div>
  @endif

  {{-- HIZLI AKSİYONLAR --}}
  @php $linkler = array_values(array_filter($veri['aksiyonlar'], fn($a)=>($a['tip']??'')==='link')); @endphp
  @if (count($linkler))
  <div class="ik-blok">
    <div class="ik-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">bolt</span> Hızlı Aksiyonlar</div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach ($linkler as $a)
        <a href="{{ $a['url'] }}" class="rk-btn bos" style="padding:7px 14px;color:{{ $renkMap[$a['renk']??'mavi']??'var(--mavi)' }}"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">{{ $a['ikon']??'arrow_forward' }}</span> {{ $a['lbl'] }} →</a>
      @endforeach
    </div>
  </div>
  @endif

</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'crisis_alert')
@section('baslik', 'Personel Risk Radarı')
@section('title', 'Risk Radarı — İK')

@push('head')
<style>
#ir{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--turuncu:#F97316;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;
}
body.koyu #ir{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;}
#ir{background:var(--bg);min-height:calc(100vh - 60px)}
#ir *{box-sizing:border-box}
#ir .pano{padding:0 0 28px}
#ir .ust{display:flex;align-items:center;gap:13px;margin-bottom:16px}
#ir .ust .ik{width:46px;height:46px;border-radius:14px;background:linear-gradient(135deg,#F97316,#F43F5E);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 6px 16px rgba(244,63,94,.3)}
#ir .ust .ik .material-icons-outlined{color:#fff;font-size:26px}
#ir .ust .ad{font-size:18px;font-weight:800;color:var(--ink)}
#ir .ust .alt{font-size:12px;color:var(--sub)}
#ir .ozet{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:18px}
#ir .oz{background:var(--card);border-radius:15px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:14px;text-align:center}
#ir .oz .v{font-size:28px;font-weight:800;line-height:1}
#ir .oz .l{font-size:12px;color:var(--sub);margin-top:4px}
#ir .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:11px;display:flex;overflow:hidden}
#ir .kart .serit{width:6px;flex-shrink:0}
#ir .kart .ic{padding:14px 16px;flex:1;min-width:0}
#ir .kart .brow{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-bottom:8px}
#ir .kart .nm{font-size:15px;font-weight:800;color:var(--ink)}
#ir .kart .rol{font-size:11.5px;color:var(--sub)}
#ir .rozet{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;letter-spacing:.3px}
#ir .neden{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--sub);padding:2px 0}
#ir .neden .material-icons-outlined{font-size:15px}
#ir .oneri{margin-top:9px;padding:9px 12px;border-radius:10px;background:var(--card2);font-size:12.5px;color:var(--ink);display:flex;gap:7px;align-items:flex-start}
#ir .link{display:inline-flex;align-items:center;gap:4px;margin-top:9px;font-size:12px;font-weight:700;color:var(--mavi);text-decoration:none}
#ir .bos{background:var(--card);border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:34px;text-align:center}
@media(max-width:600px){#ir .ozet{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div id="ir">
<div class="pano">
  @php
    $renk = ['yuksek'=>'var(--kirmizi)','orta'=>'var(--amber)','dusuk'=>'#F59E0B','yok'=>'var(--yesil)'];
    $etk = ['yuksek'=>'YÜKSEK RİSK','orta'=>'ORTA','dusuk'=>'DÜŞÜK','yok'=>'İZLE'];
    $sy = ['yuksek'=>0,'orta'=>0,'dusuk'=>0];
    foreach ($risk as $r) if (isset($sy[$r['seviye']])) $sy[$r['seviye']]++;
  @endphp

  <div class="ust">
    <a href="/ik-ozet" class="rk-btn bos" style="padding:6px 9px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <div class="ik"><span class="material-icons-outlined">crisis_alert</span></div>
    <div style="flex:1">
      <div class="ad">Personel Risk Radarı</div>
      <div class="alt">Devamsızlık, avans yoğunluğu ve kıdeme göre erken uyarı — ayrılma/sorun riskini önceden yakala</div>
    </div>
  </div>

  <div class="ozet">
    <div class="oz"><div class="v" style="color:var(--kirmizi)">{{ $sy['yuksek'] }}</div><div class="l">Yüksek risk</div></div>
    <div class="oz"><div class="v" style="color:var(--amber)">{{ $sy['orta'] }}</div><div class="l">Orta</div></div>
    <div class="oz"><div class="v" style="color:#F59E0B">{{ $sy['dusuk'] }}</div><div class="l">Düşük / izlenecek</div></div>
  </div>

  @forelse ($risk as $r)
  <div class="kart">
    <div class="serit" style="background:{{ $renk[$r['seviye']] ?? 'var(--sub)' }}"></div>
    <div class="ic">
      <div class="brow">
        <span class="nm">{{ $r['ad'] }}</span>
        <span class="rol">{{ $r['rol'] }}</span>
        <span class="rozet" style="background:{{ $renk[$r['seviye']] ?? 'var(--sub)' }};margin-left:auto">{{ $etk[$r['seviye']] ?? '—' }}</span>
      </div>
      @foreach ($r['nedenler'] as $n)
        <div class="neden"><span class="material-icons-outlined" style="color:{{ $renk[$r['seviye']] ?? 'var(--sub)' }}">chevron_right</span> {{ $n }}</div>
      @endforeach
      @if ($r['oneri'])
        <div class="oneri"><span>💡</span><span>{{ $r['oneri'] }}</span></div>
      @endif
      <a href="/ik-sicil?id={{ $r['id'] }}" class="link"><span class="material-icons-outlined" style="font-size:15px">folder_shared</span> Sicil kartını aç</a>
    </div>
  </div>
  @empty
  <div class="bos">
    <span class="material-icons-outlined" style="font-size:44px;color:var(--yesil)">verified_user</span>
    <div style="font-size:15px;font-weight:700;color:var(--ink);margin-top:10px">Risk sinyali yok 👍</div>
    <div style="font-size:13px;color:var(--sub);margin-top:4px">Şu an devamsızlık, avans yoğunluğu veya yeni personel kaynaklı dikkat gerektiren durum görünmüyor.</div>
  </div>
  @endforelse

  <div style="font-size:11px;color:var(--sub);margin-top:12px">💡 Risk skoru; son 30 gün vardiya-devamsızlığı, bu ayki avans sıklığı/tutarı ve kıdem bilgisinden hesaplanır. Daha isabetli sonuç için puantaj (QR mesai) ve vardiya planının kullanılması önerilir.</div>
</div>
</div>
@endsection

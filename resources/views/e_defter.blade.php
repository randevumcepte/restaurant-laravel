@extends('layout.app')
@section('rk_ikon', 'menu_book')
@section('baslik', 'e-Defter (hazırlık)')
@section('title', 'e-Defter')

@section('content')
<style>
  #ed{max-width:820px;margin:0 auto}
  #ed *{box-sizing:border-box}
  .ed-hero{background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;border-radius:16px;padding:18px 20px;margin-bottom:14px}
  .ed-hero h3{margin:0 0 6px;font-size:18px;font-weight:800} .ed-hero p{margin:0;font-size:13px;opacity:.92;line-height:1.5}
  .ed-steps{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:6px 18px;margin-bottom:14px}
  .ed-st{display:flex;gap:13px;padding:13px 0;border-top:1px solid var(--line)}
  .ed-st:first-child{border-top:0}
  .ed-no{width:28px;height:28px;border-radius:50%;flex:none;background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1);font-weight:800;display:flex;align-items:center;justify-content:center;font-size:13px}
  .ed-st .t{font-size:13.5px;font-weight:700;color:var(--ink)} .ed-st .d{font-size:12px;color:var(--sub);margin-top:2px;line-height:1.45}
  .ed-st .d a{color:var(--mor1);font-weight:700;text-decoration:none}
  .ed-sec{font-size:13px;font-weight:800;color:var(--ink);margin:16px 2px 10px}
  .ed-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .ed-row{display:flex;align-items:center;gap:12px;padding:11px 16px;border-top:1px solid var(--line);font-size:13px}
  .ed-row:first-child{border-top:0} .ed-row .a{flex:1;min-width:0;color:var(--ink);font-weight:600} .ed-row .t{font-size:11px;color:var(--sub)}
  .ed-bos{text-align:center;padding:22px;color:var(--sub);font-size:13px}
  .ed-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .ed-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="ed">
  <div class="ed-hero">
    <h3>e-Defter berat süreci</h3>
    <p>ResteOS mali hareketlerini <b>mahsup fişi</b> olarak üretir; e-Defter (yevmiye + kebir) ve GİB berat yüklemesi mali müşavirin/entegratör üzerinden yapılır. Bu ekran hazırlık ve takip içindir.</p>
  </div>

  <div class="ed-steps">
    <div class="ed-st"><div class="ed-no">1</div><div><div class="t">Mahsup fişini üret & aktar</div><div class="d">Satış + alış → dengeli mahsup fişi. <a href="/muhasebe-entegrasyon">Muhasebe Entegrasyonu →</a> (Logo/Netsis/Excel)</div></div></div>
    <div class="ed-st"><div class="ed-no">2</div><div><div class="t">Mali müşavir yevmiye/kebir oluşturur</div><div class="d">Fişler muhasebe programında e-Deftere dönüşür (aylık dönem).</div></div></div>
    <div class="ed-st"><div class="ed-no">3</div><div><div class="t">Berat üretimi ve GİB'e yükleme</div><div class="d">Dönem defteri + berat GİB'e yüklenir. Son tarih: 3 ay sonraki ayın sonu. <a href="/vergi-takvimi">Vergi Takvimi →</a></div></div></div>
    <div class="ed-st"><div class="ed-no">4</div><div><div class="t">Onay & arşiv</div><div class="d">GİB onaylı berat arşivlenir; KDV/Muhtasar beyanla tutarlı olmalı. <a href="/beyanname">Beyanname Hazırlık →</a></div></div></div>
  </div>

  <div class="ed-sec">Son muhasebe aktarımları</div>
  <div class="ed-tab">
    @forelse($gecmis as $g)
      <div class="ed-row"><span class="material-icons-outlined" style="color:var(--mavi);font-size:18px">sync_alt</span><div class="a">{{ $g->baglanti_tipi ?? 'aktarım' }}@isset($g->donem) · {{ $g->donem }}@endisset<div class="t">{{ \Carbon\Carbon::parse($g->created_at)->format('d.m.Y H:i') }}</div></div></div>
    @empty
      <div class="ed-bos">Henüz mahsup fişi aktarımı yok. <a href="/muhasebe-entegrasyon" style="color:var(--mor1);font-weight:700;text-decoration:none">İlk aktarımı yap →</a></div>
    @endforelse
  </div>

  <div class="ed-not"><span class="material-icons-outlined">info</span><span>Doğrudan GİB e-Defter/berat gönderimi bu sürümde <b>yoktur</b> (özel entegratör + mali mühür gerektirir). ResteOS mahsup fişini hazırlar, gerisini mali müşavirin yürütür. İleride entegratör bağlantısı eklenebilir.</span></div>
</div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Fatura Denetimi')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Fatura Denetimi')
@section('rk_alt', 'Her gelen fatura otomatik kontrol edilir: sözleşme/sipariş fiyatı, fazla faturalama, gizli zam, iskonto kaybı, mükerrer fatura')

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $rozet = fn($renk) => 'color:var(' . $renk . ');background:color-mix(in srgb,var(' . $renk . ') 13%,transparent);border-color:color-mix(in srgb,var(' . $renk . ') 40%,transparent)';
  $sev = ['kritik' => ['Yüksek', '--kirmizi'], 'uyari' => ['Orta', '--amber'], 'bilgi' => ['Bilgi', '--mavi']];
  $dur = ['acik' => ['Açık', '--amber'], 'itiraz' => ['İtiraz edildi', '--mavi'], 'cozuldu' => ['Çözüldü', '--yesil'], 'yoksay' => ['Yok sayıldı', '--sub'], 'tumu' => ['Tümü', '--mor1']];
  $qs = fn(array $ek) => '/fatura-denetim?' . http_build_query(array_filter(array_merge(['durum' => $f['durum'], 'tur' => $f['tur'], 'ted' => $f['ted'], 'bas' => $f['bas'], 'bit' => $f['bit'], 'bilgi' => $f['bilgi'] ? 1 : null], $ek), fn($v) => $v !== null && $v !== ''));
  $gruplar = $liste->groupBy(fn($d) => $d->satici_vkn ?: '—');
@endphp
<style>
  .fd-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
  @media (max-width:900px){.fd-kpi{grid-template-columns:repeat(2,1fr)}}
  .fd-kpi .rk-card{padding:14px 16px}
  .fd-kpi .e{font-size:12px;color:var(--sub);font-weight:600}
  .fd-kpi .d{font-size:22px;font-weight:800;margin-top:4px}
  .fd-kpi .a{font-size:11.5px;color:var(--sub);margin-top:2px}
  .fd-cip{display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 12px}
  .fd-cip a{padding:6px 11px;border-radius:999px;border:1px solid var(--line);font-size:12px;font-weight:600;color:var(--ink);text-decoration:none;display:inline-flex;gap:5px;align-items:center}
  .fd-cip a.on{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .fd-cip .material-icons-outlined{font-size:15px}
  .fd-grup{margin-bottom:14px}
  .fd-gbas{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--line)}
  .fd-acik{font-size:12px;color:var(--sub);max-width:620px;margin-top:2px}
  .fd-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;z-index:9999;padding:16px}
  .fd-modal .ic{background:var(--kart,#fff);border-radius:16px;max-width:720px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(15,23,42,.3)}
  .fd-modal textarea{width:100%;min-height:340px;font-family:inherit;font-size:13px;border:1px solid var(--line);border-radius:10px;padding:12px;background:transparent;color:var(--ink);resize:vertical}
</style>

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="fd-kpi">
  <div class="rk-card"><div class="e">Geri istenecek (açık)</div><div class="d" style="color:var(--kirmizi)">{{ $tl($ozet['acik_tl']) }}</div><div class="a">{{ $ozet['acik'] }} açık bulgu</div></div>
  <div class="rk-card"><div class="e">İtiraz edildi, bekleniyor</div><div class="d" style="color:var(--mavi)">{{ $tl($ozet['itiraz_tl']) }}</div><div class="a">{{ $ozet['itiraz'] }} bulgu</div></div>
  <div class="rk-card"><div class="e">Geri alındı</div><div class="d" style="color:var(--yesil)">{{ $tl($ozet['cozuldu_tl']) }}</div><div class="a">"Çözüldü" işaretlenen alacaklar</div></div>
  <div class="rk-card"><div class="e">Zamların maliyet etkisi</div><div class="d" style="color:var(--amber)">{{ $tl($ozet['etki_tl']) }}</div><div class="a">Zam + gizli zam (alacak değil)</div></div>
</div>

<div class="rk-card" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:12px;padding:12px 14px">
  <form method="GET" action="/fatura-denetim" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
    <input type="hidden" name="durum" value="{{ $f['durum'] }}">
    @if ($f['tur'])<input type="hidden" name="tur" value="{{ $f['tur'] }}">@endif
    <div><label class="rk-lbl" style="margin-top:0">Tedarikçi</label>
      <select name="ted" class="rk-inp" style="min-width:220px"><option value="">Tümü</option>
        @foreach ($tedler as $t)<option value="{{ $t->satici_vkn }}" @selected($f['ted'] === $t->satici_vkn)>{{ $t->ad ?: $t->satici_vkn }}</option>@endforeach</select></div>
    <div><label class="rk-lbl" style="margin-top:0">Başlangıç</label><input type="date" name="bas" value="{{ $f['bas'] }}" class="rk-inp"></div>
    <div><label class="rk-lbl" style="margin-top:0">Bitiş</label><input type="date" name="bit" value="{{ $f['bit'] }}" class="rk-inp"></div>
    <label style="display:flex;gap:6px;align-items:center;font-size:12.5px;padding-bottom:9px"><input type="checkbox" name="bilgi" value="1" @checked($f['bilgi'])> Küçük zamlar ve KDV bilgileri de</label>
    <button class="rk-btn" style="background:var(--mor1)" type="submit">Süz</button>
  </form>
  <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
    <a class="rk-btn bos" href="/efatura-gelen/excel?{{ http_build_query(array_filter(['ted' => $f['ted'], 'bas' => $f['bas'], 'bit' => $f['bit']])) }}"><span class="material-icons-outlined" style="font-size:17px">table_view</span> Fatura Excel'i</a>
    <form method="POST" action="/fatura-denetim/yenile" onsubmit="this.querySelector('button').disabled=true">@csrf
      <button class="rk-btn bos" type="submit" title="Sözleşme fiyatı ya da sipariş girdikten sonra eski faturaları da kontrol eder"><span class="material-icons-outlined" style="font-size:17px">refresh</span> Yeniden denetle</button></form>
  </div>
</div>

<div class="fd-cip">
  @foreach ($dur as $k => $d)<a href="{{ $qs(['durum' => $k]) }}" class="{{ $f['durum'] === $k ? 'on' : '' }}">{{ $d[0] }}</a>@endforeach
</div>
<div class="fd-cip">
  <a href="{{ $qs(['tur' => null]) }}" class="{{ !$f['tur'] ? 'on' : '' }}">Tüm türler</a>
  @foreach ($turler as $k => $t)@if (($turSay[$k] ?? 0) > 0)<a href="{{ $qs(['tur' => $k]) }}" class="{{ $f['tur'] === $k ? 'on' : '' }}"><span class="material-icons-outlined">{{ $t[1] }}</span>{{ $t[0] }} ({{ (int) $turSay[$k] }})</a>@endif @endforeach
</div>

@if ($liste->isEmpty())
  <div class="rk-card" style="text-align:center;padding:36px"><span class="material-icons-outlined" style="font-size:48px;color:var(--line)">fact_check</span>
    <div class="rk-sub" style="margin-top:10px">
      @if (!$ozet['fatura']) Henüz fatura yok. Faturalar <a href="/efatura-gelen">Gelen e-Faturalar</a> ekranından XML/ZIP/PDF yüklenince ya da entegratörden çekilince otomatik denetlenir.
      @else Bu filtrede bulgu yok. Sözleşme fiyatlarını (<a href="/fiyat-anlasmalari">Fiyat Anlaşmaları</a>) girip siparişleri sistemden verdikçe kontroller genişler; sonra "Yeniden denetle"ye basın. @endif
    </div></div>
@else
  @foreach ($gruplar as $vkn => $l)
    @php $gt = $l->sum('fark_tutar'); $ge = $l->sum('etki_tutar'); @endphp
    <div class="rk-card fd-grup" style="padding:0">
      <div class="fd-gbas">
        <div style="flex:1;min-width:220px"><div style="font-weight:700">{{ $l->first()->satici_ad ?: 'Tedarikçi' }}</div><div class="rk-sub2" style="font-size:12px">VKN {{ $vkn }} · {{ $l->count() }} bulgu · {{ $l->pluck('gelen_id')->unique()->count() }} fatura</div></div>
        @if ($gt > 0)<div style="text-align:right"><div class="rk-sub2" style="font-size:11.5px">Geri istenecek</div><div style="font-weight:800;color:var(--kirmizi)">{{ $tl($gt) }}</div></div>@endif
        @if ($ge > 0)<div style="text-align:right"><div class="rk-sub2" style="font-size:11.5px">Maliyet etkisi</div><div style="font-weight:700;color:var(--amber)">{{ $tl($ge) }}</div></div>@endif
        @if ($f['durum'] === 'acik' && $vkn !== '—')<button type="button" class="rk-btn" style="background:var(--mor1)" onclick="fdItiraz('{{ $vkn }}')"><span class="material-icons-outlined" style="font-size:17px;color:#fff">mail</span> İtiraz yazısı</button>@endif
      </div>
      <div class="rk-tablo" style="border:0;border-radius:0"><table>
        <thead><tr><th style="width:28px"><input type="checkbox" onclick="fdHepsi(this)"></th><th>Fatura</th><th>Bulgu</th><th class="sag">Geri istenecek</th><th class="sag">Etki</th><th>Durum</th></tr></thead>
        <tbody>
        @foreach ($l as $d)
          <tr>
            <td><input type="checkbox" class="fd-sec" value="{{ $d->id }}"></td>
            <td class="rk-sub2" style="white-space:nowrap"><a href="/efatura-gelen/{{ $d->gelen_id }}">{{ $d->fatura_no ?: '#' . $d->gelen_id }}</a><div style="font-size:11.5px">{{ $d->tarih ? \Carbon\Carbon::parse($d->tarih)->format('d.m.Y') : '' }}</div></td>
            <td><div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap"><span class="rk-rozet" style="{{ $rozet($sev[$d->seviye][1] ?? '--sub') }}">{{ $turler[$d->tur][0] ?? $d->tur }}</span><b style="font-size:13px">{{ $d->baslik }}</b></div>
              <div class="fd-acik">{{ $d->aciklama }}</div>@if ($d->not)<div class="fd-acik" style="font-style:italic">Not: {{ $d->not }}</div>@endif</td>
            <td class="sag" style="font-weight:700;color:var(--kirmizi)">{{ $d->fark_tutar > 0 ? $tl($d->fark_tutar) : '—' }}</td>
            <td class="sag" style="color:var(--amber)">{{ $d->etki_tutar != 0 ? $tl($d->etki_tutar) : '—' }}</td>
            <td><span class="rk-rozet" style="{{ $rozet($dur[$d->durum][1] ?? '--sub') }}">{{ $dur[$d->durum][0] ?? $d->durum }}</span></td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
    </div>
  @endforeach
  <form id="fdTopluForm" method="POST" action="/fatura-denetim/durum" class="rk-card" style="position:sticky;bottom:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 14px">
    @csrf
    <span class="rk-sub2" style="font-size:12.5px"><b id="fdSecSay">0</b> bulgu seçili →</span>
    <input type="text" name="not" class="rk-inp" placeholder="Not (isteğe bağlı)" style="max-width:240px;padding:7px 10px">
    <button class="rk-btn bos" name="durum" value="itiraz" type="submit">İtiraz edildi</button>
    <button class="rk-btn bos" name="durum" value="cozuldu" type="submit">Çözüldü (geri alındı)</button>
    <button class="rk-btn bos" name="durum" value="yoksay" type="submit">Yok say</button>
    <button class="rk-btn bos" name="durum" value="acik" type="submit">Açığa al</button>
  </form>
@endif

<div class="fd-modal" id="fdModal"><div class="ic">
  <div style="display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--line)"><span class="material-icons-outlined" style="color:var(--mor1)">mail</span><b style="flex:1">Tedarikçiye itiraz yazısı</b>
    <button type="button" class="rk-btn bos" style="padding:5px 9px" onclick="fdKapat()">Kapat</button></div>
  <div style="padding:14px 18px;overflow:auto"><textarea id="fdMetin"></textarea>
    <div class="rk-sub2" style="font-size:11.5px;margin-top:6px">Metni düzenleyebilirsiniz. Gönderdikten sonra "İtiraz edildi olarak işaretle"ye basın; alacak geri gelince bulguyu "Çözüldü" yapın.</div></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid var(--line)">
    <button type="button" class="rk-btn bos" onclick="fdKopyala()"><span class="material-icons-outlined" style="font-size:17px">content_copy</span> Kopyala</button>
    <a id="fdWa" class="rk-btn bos" target="_blank" rel="noopener"><span class="material-icons-outlined" style="font-size:17px">chat</span> WhatsApp</a>
    <a id="fdMail" class="rk-btn bos"><span class="material-icons-outlined" style="font-size:17px">alternate_email</span> E-posta</a>
    <button type="button" class="rk-btn" style="background:var(--mor1);margin-left:auto" onclick="fdItirazIsaretle()">İtiraz edildi olarak işaretle</button>
  </div>
</div></div>

<script>
(function () {
  var CSRF = '{{ csrf_token() }}', modal = document.getElementById('fdModal'), aktifIds = [];
  document.body.appendChild(modal);
  function say() { document.getElementById('fdSecSay') && (document.getElementById('fdSecSay').innerText = document.querySelectorAll('.fd-sec:checked').length); }
  document.addEventListener('change', function (e) { if (e.target.classList && e.target.classList.contains('fd-sec')) say(); });
  window.fdHepsi = function (cb) { cb.closest('table').querySelectorAll('.fd-sec').forEach(function (x) { x.checked = cb.checked; }); say(); };
  var form = document.getElementById('fdTopluForm');
  if (form) form.addEventListener('submit', function (e) {
    var sec = document.querySelectorAll('.fd-sec:checked');
    if (!sec.length) { e.preventDefault(); alert('Önce bulgu seçin.'); return; }
    form.querySelectorAll('input[name="ids[]"]').forEach(function (x) { x.remove(); });
    sec.forEach(function (x) { var i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = x.value; form.appendChild(i); });
  });
  window.fdItiraz = function (vkn) {
    fetch('/fatura-denetim/itiraz?ted=' + encodeURIComponent(vkn), { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) { alert(j.hata || 'Yazı hazırlanamadı'); return; }
      aktifIds = j.ids; document.getElementById('fdMetin').value = j.metin;
      var wa = document.getElementById('fdWa'), ml = document.getElementById('fdMail');
      wa.href = 'https://wa.me/' + (j.telefon || '') + '?text=' + encodeURIComponent(j.metin);
      ml.href = 'mailto:' + (j.email || '') + '?subject=' + encodeURIComponent('Fatura fiyat farkı talebi') + '&body=' + encodeURIComponent(j.metin);
      modal.style.display = 'flex';
    });
  };
  window.fdKapat = function () { modal.style.display = 'none'; };
  modal.addEventListener('click', function (e) { if (e.target === modal) fdKapat(); });
  window.fdKopyala = function () { var t = document.getElementById('fdMetin'); t.select(); try { navigator.clipboard.writeText(t.value); } catch (e) { document.execCommand('copy'); } };
  document.getElementById('fdMetin').addEventListener('input', function () {
    var m = this.value, wa = document.getElementById('fdWa'), ml = document.getElementById('fdMail');
    wa.href = wa.href.split('?')[0] + '?text=' + encodeURIComponent(m); ml.href = ml.href.split('&body=')[0] + '&body=' + encodeURIComponent(m);
  });
  window.fdItirazIsaretle = function () {
    fetch('/fatura-denetim/durum', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: JSON.stringify({ durum: 'itiraz', ids: aktifIds }) })
      .then(function () { location.reload(); });
  };
})();
</script>
@endsection

{{-- Hal bülteni tarayıcıdan: canlı sunucu İzmir API'sine bağlanamıyor, API tarayıcıya açık (CORS *).
     Satın alma yetkisi olan biri panelde herhangi bir sayfayı açınca, bugünün bülteni yoksa sessizce çekip sunucuya gönderir
     (saatte en fazla 1 deneme; bülten sabah henüz yayınlanmadıysa sonra tekrar dener). Sunucu tüm şubelere yazar. --}}
@php
  $_halYetki = false; $_halEksik = false; $_pp = $_pp ?? null;
  try {
    $_halYetki = !$_pp || in_array(($_pp->rol ?? ''), ['sahip', 'mudur']) || (function_exists('_panelYetkiVar') && _panelYetkiVar($_pp, 'gor_satinalma'));
    if ($_halYetki && function_exists('_aktifSubeId'))
      $_halEksik = !\DB::table('hal_fiyatlari')->where('sube_id', _aktifSubeId())->where('tarih', today()->toDateString())->where('kaynak', 'api')->exists();
  } catch (\Throwable $e) { $_halEksik = false; }
@endphp
@if ($_halYetki)
<script>
  window.rkHalTarayicidan = function (tarih, sessiz) {
    var ctl = new AbortController(); setTimeout(function () { ctl.abort(); }, 10000);
    return fetch('https://openapi.izmir.bel.tr/api/ibb/halfiyatlari/sebzemeyve/' + tarih, { signal: ctl.signal })
      .then(function (r) { if (!r.ok) throw 0; return r.json(); })
      .then(function (veri) {
        if (!veri || !veri.HalFiyatListesi || !veri.HalFiyatListesi.length) throw 0;
        return fetch('/hal-fiyatlari/yukle', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify({ kaynak: 'izmir', tarih: tarih, veri: veri, sessiz: sessiz ? 1 : 0 }) }).then(function (r) { return r.json(); });
      });
  };
  window.rkHalBugun = function () { var d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); };
  @if ($_halEksik)
  (function () {
    var son = 0; try { son = +localStorage.getItem('rkHalOtoSon') || 0; } catch (e) {}
    if (Date.now() - son < 3600000) return;
    try { localStorage.setItem('rkHalOtoSon', String(Date.now())); } catch (e) {}
    setTimeout(function () {
      window.rkHalTarayicidan(window.rkHalBugun(), true)
        .then(function (s) { if (s && s.ok && location.pathname === '/hal-fiyatlari') location.reload(); })
        .catch(function () {});
    }, 1500);
  })();
  @endif
</script>
@endif

@php
  // Şubeler arası transfer bandı (yalnız transfer ortağı olan şubede). $trfMod: 'mal_kabul' | 'oneri'
  $_tbMetin = null; $_tbLink = '/sube-transfer';
  try {
      if (function_exists('_transferOrtaklari') && function_exists('_webSubeId')) {
          $_tbSid = _webSubeId();
          if (_transferOrtaklari($_tbSid)) {
              if (($trfMod ?? '') === 'mal_kabul') {
                  $_tbYolda = \Illuminate\Support\Facades\DB::table('sube_transferleri')->where('alan_sube_id', $_tbSid)->where('durum', 'yolda')->count();
                  $_tbHaz = \Illuminate\Support\Facades\DB::table('sube_transferleri')->where('alan_sube_id', $_tbSid)->whereIn('durum', ['istendi', 'hazirlaniyor'])->count();
                  if ($_tbYolda) $_tbMetin = '🚚 Kardeş şubeden <b>' . $_tbYolda . ' transfer yolda</b> — gelince buradan değil Şubeler Arası Transfer ekranından teslim alın (lot/SKT ve maliyet otomatik taşınır).';
                  elseif ($_tbHaz) $_tbMetin = '🔄 ' . $_tbHaz . ' transfer isteğiniz gönderen şubede hazırlanıyor.';
              } else {
                  $_tbAl = cache()->remember('trf_bant_oneri_' . $_tbSid, 300, fn () => count((_trfOneriler($_tbSid))['al'] ?? []));
                  if ($_tbAl) { $_tbMetin = '🏪 Eksik ürünlerinizden <b>' . $_tbAl . ' tanesi kardeş şubelerde fazla var</b> — sipariş vermeden önce transferle karşılayabilirsiniz.'; }
              }
          }
      }
  } catch (\Throwable $e) { $_tbMetin = null; }
@endphp
@if ($_tbMetin)
<a href="{{ $_tbLink }}" style="display:flex;align-items:center;gap:10px;background:color-mix(in srgb,#0891B2 9%,var(--card));border:1px solid color-mix(in srgb,#0891B2 30%,var(--line));color:var(--ink);text-decoration:none;border-radius:14px;padding:12px 15px;margin-bottom:12px;font-size:13px">
  <span class="material-icons-outlined" style="color:#0891B2">swap_horiz</span>
  <span style="flex:1;line-height:1.45">{!! $_tbMetin !!}</span>
  <span class="material-icons-outlined" style="color:#0891B2">chevron_right</span>
</a>
@endif

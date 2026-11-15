@php
  [$boyAd, $w, $h] = $boyutlar[$ayar['boyut']];
  $a4 = str_starts_with($ayar['boyut'], 'a4_');
  $kucuk = $h < 30;
  $hepsi = [];
  foreach ($etiketler as $e) for ($i = 0; $i < $e['adet']; $i++) $hepsi[] = $e;
  $bar = max(8, $h - ($kucuk ? 13 : 19)); // barkod yüksekliği (mm)
  $diger = $ayar['dil'] === 'tspl' ? 'tspl' : 'zpl';
  $sorgu = fn ($ek) => $yol . '?' . http_build_query(array_merge(['ids' => $ids, 'adet' => $adet, 'boyut' => $ayar['boyut']], $ek));
@endphp<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Etiket Yazdır · {{ count($hepsi) }} adet</title>
<style>
@page { size: {{ $a4 ? 'A4' : $w . 'mm ' . $h . 'mm' }}; margin: {{ $a4 ? ($ayar['boyut'] === 'a4_3x8' ? '0.5mm 0 0 0' : '8.8mm 0 0 0') : '0' }}; }
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;color:#000;background:#eef0f6}
.arac{position:sticky;top:0;z-index:5;background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:13px;box-shadow:0 2px 10px rgba(15,23,42,.06)}
.arac a,.arac button,.arac select{border:1px solid #e5e7eb;background:#fff;color:#1e293b;border-radius:10px;padding:8px 12px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;font-family:inherit}
.arac .ana{background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:0}
.arac .not{flex-basis:100%;color:#64748b;font-size:12px;line-height:1.5}
.sayfa{padding:18px;display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.et{width:{{ $w }}mm;height:{{ $h }}mm;background:#fff;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:{{ $kucuk ? '1mm 2mm' : '1.5mm 2.5mm' }};box-shadow:0 1px 4px rgba(0,0,0,.18);border-radius:1.5mm}
.et .u{font-size:{{ $kucuk ? '6.5pt' : '7pt' }};line-height:1.1;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;letter-spacing:.2px}
.et .a{font-size:{{ $kucuk ? '8.5pt' : ($w >= 70 ? '11pt' : '9.5pt') }};font-weight:700;line-height:1.15;max-width:100%;overflow:hidden;display:-webkit-box;-webkit-line-clamp:{{ $kucuk ? 1 : 2 }};-webkit-box-orient:vertical;margin:.4mm 0 .6mm}
.et img{display:block;width:100%;height:{{ $bar }}mm;object-fit:fill}
.et .s{font-size:{{ $kucuk ? '6.5pt' : '7.5pt' }};line-height:1.15;color:#222;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;margin-top:.4mm}
@media print{
  body{background:#fff}
  .arac{display:none}
  .sayfa{padding:0;gap:0;display:{{ $a4 ? 'grid' : 'block' }};{{ $a4 ? 'grid-template-columns:repeat(3,' . $w . 'mm);' : '' }}}
  .et{box-shadow:none;border-radius:0;{{ $a4 ? '' : 'page-break-after:always;break-after:page;' }}}
  .et:last-child{page-break-after:auto;break-after:auto}
}
</style></head>
<body>
<div class="arac">
  <a href="{{ $geri }}">← Geri</a>
  <b style="margin:0 4px">{{ count($hepsi) }} etiket</b>
  <select onchange="location.href=this.value">
    @foreach ($boyutlar as $k => $b)<option value="{{ $sorgu(['boyut' => $k]) }}" {{ $ayar['boyut'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach
  </select>
  <button class="ana" onclick="window.print()">🖨 Yazdır</button>
  <a href="{{ $sorgu(['format' => $diger]) }}" title="Etiket yazıcısına doğrudan gönderilecek komut dosyası">{{ strtoupper($diger) }} dosyası indir</a>
  <div class="not">Yazdırma penceresinde: yazıcı olarak <b>etiket yazıcını</b> seç, <b>Kenar boşlukları: Yok</b>, <b>Ölçek: %100</b>, üst bilgi/alt bilgi kapalı.
    @if ($a4) A4 etiket yaprağı ({{ $boyAd }}) için normal yazıcıyı seç. @else Kâğıt boyutu yazıcı sürücüsünde de {{ $w }}×{{ $h }} mm olmalı. @endif</div>
</div>
<div class="sayfa">
@foreach ($hepsi as $e)
  <div class="et">
    @if ($e['ust'] && !$kucuk)<div class="u">{{ $e['ust'] }}</div>@endif
    <div class="a">{{ $e['ad'] }}</div>
    <img src="/etiket-barkod.svg?k={{ urlencode($e['barkod']) }}&h=50&m=2" alt="{{ $e['barkod'] }}">
    @if ($e['alt'])<div class="s">{{ $e['alt'] }}</div>@endif
  </div>
@endforeach
</div>
<script>
// Görseller yüklenince yazdırma penceresini aç (yüklenmeden basarsa barkod boş çıkar)
(function(){ var img=[].slice.call(document.images), kalan=img.length; function bitti(){ if(--kalan<=0) setTimeout(function(){ window.print(); },300); }
  if(!kalan) return; img.forEach(function(i){ if(i.complete) bitti(); else { i.onload=bitti; i.onerror=bitti; } }); })();
</script>
</body></html>

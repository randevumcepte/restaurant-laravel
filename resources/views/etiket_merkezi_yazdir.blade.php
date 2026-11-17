@php
  // $tip: paket | tarti | raf | serbest. $w/$h mm. $a4 = [sütun, satır] ya da null (rulo). $sablon: tasarım (paket + mod sablon)
  $hepsi = [];
  foreach ($etiketler as $e) for ($i = 0; $i < max(1, (int) ($e['adet'] ?? 1)); $i++) $hepsi[] = $e;
  if ($tip === 'raf') $hepsi = $etiketler; // raf kartları zaten adet kadar çoğaltıldı
  $kucuk = $h <= 30;
  $diger = ($ayar['dil'] ?? '') === 'tspl' ? 'tspl' : 'zpl';
  $sbAlan = $sablon ? (json_decode((string) $sablon->alanlar, true) ?: []) : [];
  $kodTip = $kodTip ?? null;
  $deger = function ($e, $k) {
      return match ($k) {
          'ad' => $e['ad'], 'fiyat' => $e['fiyat'], 'birim_fiyat' => $e['birim_fiyat'], 'net' => $e['net'] ? 'Net: ' . $e['net'] : null,
          'uretim' => $e['uretim'] ? 'Üretim: ' . $e['uretim'] : null, 'skt' => $e['skt'] ? 'SKT: ' . $e['skt'] : null, 'parti' => 'Parti: ' . $e['parti'],
          'icindekiler' => $e['icindekiler'] ? 'İçindekiler: ' . $e['icindekiler'] : null, 'alerjen' => $e['alerjen'], 'saklama' => $e['saklama'],
          'uretici' => $e['uretici'], 'uretim_yeri' => $e['uretim_yeri'] ? 'Üretim yeri: ' . $e['uretim_yeri'] : null, default => null };
  };
@endphp<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $baslik }} · Yazdır</title>
<style>
@page { size: {{ $a4 ? 'A4' : $w . 'mm ' . $h . 'mm' }}; margin: 0; }
*{box-sizing:border-box}
body{margin:0;font-family:Arial,Helvetica,sans-serif;color:#000;background:#eef0f6;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.arac{position:sticky;top:0;z-index:5;background:#fff;border-bottom:1px solid #e5e7eb;padding:10px 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:13px;box-shadow:0 2px 10px rgba(15,23,42,.06)}
.arac a,.arac button{border:1px solid #e5e7eb;background:#fff;color:#1e293b;border-radius:10px;padding:8px 12px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;font-family:inherit}
.arac .ana{background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:0}
.arac .not{flex-basis:100%;color:#64748b;font-size:12px;line-height:1.5}
.arac .tamam{color:#047857;font-weight:700}
.sayfa{padding:18px;display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.et{position:relative;width:{{ $w }}mm;height:{{ $h }}mm;background:#fff;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.18);border-radius:1.2mm}
.bk{display:block;max-width:100%}
/* paket - tam */
.pk{display:flex;flex-direction:column;height:100%;padding:{{ $kucuk ? '1mm 1.6mm' : '1.6mm 2.2mm' }};gap:.3mm}
.pk .ad{font-weight:700;font-size:{{ $kucuk ? '8pt' : ($w >= 70 ? '11.5pt' : '9.5pt') }};line-height:1.12;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.pk .sat{display:flex;justify-content:space-between;align-items:baseline;gap:2mm;font-size:{{ $kucuk ? '6.5pt' : '7.5pt' }};line-height:1.2}
.pk .fy{font-weight:700;font-size:{{ $kucuk ? '8.5pt' : '11pt' }};white-space:nowrap}
.pk .kc{font-size:{{ $kucuk ? '5.5pt' : '6.3pt' }};line-height:1.2;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pk .bar{flex:1;min-height:0;display:flex;align-items:flex-end;justify-content:center;padding-top:.5mm}
.pk .bar img{height:100%;max-height:{{ max(8, $h * 0.38) }}mm;width:auto;max-width:100%}
/* paket - küçük tarih etiketi */
.kt{display:flex;flex-direction:column;justify-content:center;height:100%;padding:1.2mm 2mm;gap:.6mm}
.kt .sk{font-weight:700;font-size:{{ $kucuk ? '9pt' : '12pt' }}}
.kt .s{font-size:{{ $kucuk ? '7pt' : '8.5pt' }}}
.kt img{height:{{ max(6, $h * 0.3) }}mm;width:auto;max-width:100%;margin-top:.4mm}
/* şablon */
.sb{position:absolute;inset:0;background-size:100% 100%;background-repeat:no-repeat}
.sb .f{position:absolute;display:flex;align-items:center;overflow:hidden;line-height:1.1;padding:0 .3mm}
.sb .f span{width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb .f img{height:100%;width:auto;max-width:100%;margin:0 auto}
/* tartı */
.tr{display:flex;flex-direction:column;height:100%;padding:1.5mm 2.2mm;gap:.4mm}
.tr .ad{font-weight:700;font-size:{{ $w >= 70 ? '12pt' : '10pt' }};line-height:1.1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.tr .iz{display:grid;grid-template-columns:1fr 1fr;gap:.4mm 2mm;font-size:7pt;line-height:1.2}
.tr .iz b{display:block;font-size:9pt}
.tr .tt{font-size:7pt}.tr .tt b{font-size:13pt;display:block;line-height:1}
.tr .bar{flex:1;min-height:0;display:flex;align-items:flex-end;justify-content:center}
.tr .bar img{height:100%;max-height:{{ max(9, $h * 0.36) }}mm;width:auto;max-width:100%}
/* raf kartı */
.rf{display:flex;flex-direction:column;height:100%;padding:{{ $h < 45 ? '1.6mm 2.4mm' : '3mm 3.5mm' }};gap:{{ $h < 45 ? '.4mm' : '1mm' }};border:{{ $a4 ? '0' : '0' }}}
.rf .ust{display:flex;justify-content:space-between;gap:2mm;align-items:flex-start}
.rf .ad{font-weight:700;font-size:{{ $h < 45 ? ($w >= 90 ? '11pt' : '10pt') : '15pt' }};line-height:1.12;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;flex:1}
.rf .yerli{flex:none;border:1.2px solid #000;border-radius:1mm;font-size:{{ $h < 45 ? '5.5pt' : '7pt' }};font-weight:700;padding:.3mm 1mm;letter-spacing:.2px;white-space:nowrap}
.rf .orta{flex:1;display:flex;align-items:center;justify-content:space-between;gap:2mm;min-height:0}
.rf .fy{font-weight:900;font-size:{{ $h < 45 ? ($w >= 90 ? '26pt' : '22pt') : ($w >= 100 ? '40pt' : '30pt') }};line-height:1;white-space:nowrap;letter-spacing:-.5px}
.rf .fy small{font-size:.42em;font-weight:700;letter-spacing:0}
.rf .eski{font-size:{{ $h < 45 ? '8pt' : '11pt' }};text-decoration:line-through;color:#333;white-space:nowrap}
.rf .ind{display:inline-block;background:#000;color:#fff;font-weight:900;font-size:{{ $h < 45 ? '6.5pt' : '8.5pt' }};padding:.3mm 1.2mm;border-radius:.8mm;margin-bottom:.4mm}
.rf .sag{flex:none;max-width:42%;white-space:nowrap;text-align:right;font-size:{{ $h < 45 ? '6.3pt' : '8pt' }};line-height:1.25}
.rf .alt{display:flex;justify-content:space-between;align-items:flex-end;gap:2mm;font-size:{{ $h < 45 ? '5.5pt' : '7pt' }};line-height:1.2;color:#111}
.rf .alt img{height:{{ $h < 45 ? '5.5mm' : '8mm' }};width:auto}
/* serbest */
.sr{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;padding:1.2mm 2mm;gap:.5mm;text-align:center}
.sr .u{font-size:7pt;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.sr img,.sr svg{max-width:100%;height:{{ max(8, $h * 0.62) }}mm;width:auto}
.sr .qr svg,.sr .qr img{height:{{ max(10, $h * 0.72) }}mm;width:{{ max(10, $h * 0.72) }}mm}
.sr .k{font-size:6.5pt;font-family:monospace;word-break:break-all;max-height:2.4em;overflow:hidden}
@if ($a4)
.sayfa.a4{display:grid;grid-template-columns:repeat({{ $a4[0] }},{{ $w }}mm);gap:0;justify-content:safe center;overflow-x:auto}
.sayfa.a4 .et{border-radius:0;box-shadow:none;outline:.2mm dashed #9ca3af;outline-offset:-.1mm}
@endif
@media print{
  body{background:#fff}
  .arac{display:none}
  .sayfa{padding:0;gap:0;display:block}
  .et{box-shadow:none;border-radius:0;page-break-after:always;break-after:page}
  .et:last-child{page-break-after:auto;break-after:auto}
  @if ($a4)
  .sayfa.a4{display:grid;grid-template-columns:repeat({{ $a4[0] }},{{ $w }}mm);justify-content:start}
  .sayfa.a4 .et{page-break-after:auto;break-after:auto;break-inside:avoid}
  .sayfa.a4 .et:nth-child({{ $a4[0] * $a4[1] }}n){break-after:page;page-break-after:always}
  @endif
}
</style></head>
<body>
<div class="arac">
  @if (!request()->boolean('gomulu'))<a href="{{ $geri }}">← Geri</a>@endif
  <b style="margin:0 4px">{{ $baslik }} · {{ count($hepsi) }} etiket</b>
  @if (!request()->boolean('gomulu'))<button class="ana" onclick="window.print()">🖨 Yazdır</button>@endif
  @if ($zplUrl)<a href="{{ $zplUrl }}{{ str_contains($zplUrl, '?') ? '&' : '?' }}format={{ $diger }}" title="Etiket yazıcısına doğrudan gönderilecek komut dosyası">{{ strtoupper($diger) }} dosyası indir</a>@endif
  @if ($tip === 'raf')<span id="rafDurum"></span>@endif
  <div class="not">Yazdırma penceresinde: yazıcı olarak
    @if ($a4) <b>normal yazıcıyı</b> seç, kâğıt <b>A4</b>, <b>Kenar boşlukları: Yok</b>, <b>Ölçek: %100</b>. Kesik çizgilerden kes. @else <b>etiket yazıcını</b> seç, <b>Kenar boşlukları: Yok</b>, <b>Ölçek: %100</b>; yazıcı sürücüsünde kâğıt boyutu {{ rtrim(rtrim(number_format($w, 1, ',', ''), '0'), ',') }}×{{ rtrim(rtrim(number_format($h, 1, ',', ''), '0'), ',') }} mm olmalı. @endif
    Üst bilgi/alt bilgi kapalı.</div>
  @if ($tip === 'paket' && empty($etiketler[0]['barkod']) && ($etiketler[0]['mod'] ?? '') !== 'kucuk')<div class="not" style="color:#B45309;font-weight:700">⚠ Bu ürünün barkodu yok, barkod alanı boş basılır. Ürünler → ürünü düzenle → Barkod alanına resmi (869…) barkodu yaz ya da "mağaza içi barkod üret"i işaretle.</div>@endif
</div>
<div class="sayfa {{ $a4 ? 'a4' : '' }}">
@foreach ($hepsi as $e)
  <div class="et">
  @if ($tip === 'paket' && $sablon)
    <div class="sb" style="{{ $sablon->arka ? 'background-image:url(' . $sablon->arka . ')' : '' }}">
      @foreach ($sbAlan as $f)
        @php $hz = $f['hz'] === 'c' ? 'center' : ($f['hz'] === 'r' ? 'right' : 'left'); @endphp
        <div class="f" style="left:{{ $f['x'] }}%;top:{{ $f['y'] }}%;width:{{ $f['w'] }}%;height:{{ $f['h'] }}%;font-size:{{ $f['fs'] }}pt;font-weight:{{ $f['b'] ? 700 : 400 }};justify-content:{{ $hz === 'left' ? 'flex-start' : ($hz === 'right' ? 'flex-end' : 'center') }}">
          @if ($f['k'] === 'barkod')
            @if ($e['barkod'])<img src="{{ _emBarkodUrl($e['barkod'], 60) }}" alt="{{ $e['barkod'] }}">@endif
          @else
            <span style="text-align:{{ $hz }}">{{ $f['k'] === 'serbest' ? $f['t'] : $deger($e, $f['k']) }}</span>
          @endif
        </div>
      @endforeach
    </div>
  @elseif ($tip === 'paket' && $e['mod'] === 'kucuk')
    <div class="kt">
      @if ($e['uretim'])<div class="s">Üretim: {{ $e['uretim'] }}</div>@endif
      @if ($e['skt'])<div class="sk">SKT: {{ $e['skt'] }}</div>@endif
      <div class="s">Parti: {{ $e['parti'] }}</div>
      @if ($e['barkod'] && !$kucuk)<img class="bk" src="{{ _emBarkodUrl($e['barkod'], 40) }}" alt="{{ $e['barkod'] }}">@endif
    </div>
  @elseif ($tip === 'paket')
    <div class="pk">
      <div class="ad">{{ $e['ad'] }}</div>
      <div class="sat"><span>{{ $e['net'] ? 'Net: ' . $e['net'] : '' }}</span><span class="fy">{{ $e['fiyat'] }}</span></div>
      @if ($e['birim_fiyat'] && !$kucuk)<div class="kc">{{ $e['birim_fiyat'] }}</div>@endif
      <div class="sat"><span>@if ($e['uretim'])ÜT: {{ $e['uretim'] }}@endif</span><b>@if ($e['skt'])SKT: {{ $e['skt'] }}@endif</b></div>
      <div class="kc">Parti: {{ $e['parti'] }}@if ($e['saklama'] && !$kucuk) · {{ $e['saklama'] }}@endif</div>
      @if ($e['icindekiler'] && $h >= 50)<div class="kc" style="white-space:normal;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">İçindekiler: {{ $e['icindekiler'] }}@if ($e['alerjen']) · {{ $e['alerjen'] }}@endif</div>@endif
      @if ($e['barkod'])<div class="bar"><img src="{{ _emBarkodUrl($e['barkod'], 50) }}" alt="{{ $e['barkod'] }}"></div>@endif
      @if ($e['uretici'] && !$kucuk)<div class="kc">{{ $e['uretici'] }}@if ($e['yerli']) · Yerli Üretim @endif</div>@endif
    </div>
  @elseif ($tip === 'tarti')
    <div class="tr">
      <div class="ad">{{ $e['ad'] }}</div>
      <div class="iz">
        <div>Net ağırlık<b>{{ $e['net'] }}</b></div>
        <div class="tt" style="text-align:right">Tutar<b>{{ $e['fiyat'] }}</b></div>
        <div>{{ $e['birim_fiyat'] }}</div>
        <div style="text-align:right">Paket: {{ $e['uretim'] }}@if ($e['skt'])<br><b style="font-size:7.5pt;display:inline">SKT: {{ $e['skt'] }}</b>@endif</div>
      </div>
      <div class="bar"><img src="{{ _emBarkodUrl($e['barkod'], 50) }}" alt="{{ $e['barkod'] }}"></div>
    </div>
  @elseif ($tip === 'raf')
    <div class="rf">
      <div class="ust"><div class="ad">{{ $e['ad'] }}</div>@if ($e['yerli'])<span class="yerli">YERLİ ÜRETİM</span>@endif</div>
      <div class="orta">
        <div>
          @if ($e['eski'])<span class="ind">İNDİRİM</span> <span class="eski">{{ $e['eski'] }} ₺</span><br>@endif
          <span class="fy">{{ $e['fiyat'] }}<small> ₺{{ $e['birim'] ? '/' . $e['birim'] : '' }}</small></span>
          @if ($e['indirim_bitis'])<div class="sag" style="text-align:left;max-width:none">{{ $e['indirim_bitis'] }} tarihine kadar</div>@endif
        </div>
        <div class="sag">
          @if ($e['net'])<div>{{ $e['net'] }}</div>@endif
          @if ($e['birim_fiyat'])<div><b>{{ $e['birim_fiyat'] }}</b></div>@endif
        </div>
      </div>
      <div class="alt">
        <div>@if ($e['uretim_yeri'])Üretim yeri: {{ $e['uretim_yeri'] }}<br>@endif Fiyat tarihi: {{ $e['tarih'] }} · KDV dahil</div>
        @if ($e['barkod'])<img src="{{ _emBarkodUrl($e['barkod'], 40) }}" alt="{{ $e['barkod'] }}">@endif
      </div>
    </div>
  @else
    <div class="sr">
      @if ($e['ust'])<div class="u">{{ $e['ust'] }}</div>@endif
      @if ($kodTip === 'qr')
        <div class="qr" data-qr="{{ $e['kod'] }}"></div>
        <div class="k">{{ \Illuminate\Support\Str::limit($e['kod'], 60) }}</div>
      @elseif ($kodTip === 'ean13')
        <img src="/etiket-ean13.svg?k={{ $e['kod'] }}&h=60" alt="{{ $e['kod'] }}">
      @else
        <img src="/etiket-barkod.svg?k={{ urlencode($e['kod']) }}&h=60&m=2" alt="{{ $e['kod'] }}">
      @endif
    </div>
  @endif
  </div>
@endforeach
</div>
@if ($kodTip === 'qr')<script src="/js/qrcode-gen.js?v=3"></script>@endif
<script>
(function(){
  var isaretle=function(){ return null; };
  document.querySelectorAll('[data-qr]').forEach(function(b){ try{ var q=qrcode(0,'M'); q.addData(unescape(encodeURIComponent(b.dataset.qr))); q.make(); b.innerHTML=q.createSvgTag(2,0); }catch(e){ b.textContent='QR çizilemedi (metin çok uzun)'; } });
  @if ($tip === 'raf' && !empty($rafIds))
  var isaretlendi=null;
  isaretle=function(){ if(isaretlendi) return isaretlendi;
    return isaretlendi=fetch('/barkod-etiket/raf-basildi',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:'ids={{ $rafIds }}'})
      .then(function(){ var d=document.getElementById('rafDurum'); if(d){ d.className='tamam'; d.textContent='✓ Basıldı olarak işaretlendi; "yenilenecek" listesinden düştü.'; } }).catch(function(){}); }
  @endif
  // Otomatik açılan yazdırma penceresi kapanınca (Yazdır ya da İptal) geldiği sayfaya döner; ?kal=1 = önizleme, otomatik yazdırma yok
  var kal=/[?&](kal|gomulu)=1/.test(location.search), oto=false;
  window.addEventListener('afterprint', function(){ var p=isaretle(); if(!oto) return; oto=false;
    Promise.resolve(p).catch(function(){}).then(function(){ location.replace({!! json_encode($geri) !!}); }); });
  function bas(){ if(kal) return; oto=true; window.print(); }
  var img=[].slice.call(document.images), kalan=img.length;
  function bitti(){ if(--kalan<=0) setTimeout(bas,300); }
  if(!kalan){ setTimeout(bas,300); return; }
  img.forEach(function(i){ if(i.complete) bitti(); else { i.onload=bitti; i.onerror=bitti; } });
})();
</script>
</body></html>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Hesap Fişi #{{ $a->id }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body { font-family: 'Courier New', monospace; width: 76mm; margin: 0 auto; padding: 8px 6px; color: #000; font-size: 12px; }
        .c { text-align: center; }
        .b { font-weight: bold; }
        .lg { font-size: 15px; }
        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .r { text-align: right; }
        .tot { font-size: 14px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
@php
    $ara = $kalemler->sum('tutar');
    $matrah = round($a->toplam / 1.10, 2);
    $kdv = round($a->toplam - $matrah, 2);
@endphp
<body onload="window.print()">
    <div class="c b lg">{{ $sube->ad ?? 'Restoran' }}</div>
    <div class="c" style="font-size:10px">{{ $ayar->adres ?? ($sube->adres ?? '') }}</div>
    @if ($ayar && $ayar->vkn_tckn)<div class="c" style="font-size:10px">VKN: {{ $ayar->vkn_tckn }} · {{ $ayar->vergi_dairesi }}</div>@endif
    <hr>
    <table style="font-size:10px">
        <tr><td>Tarih</td><td class="r">{{ now()->format('d.m.Y H:i') }}</td></tr>
        <tr><td>Belge</td><td class="r">HESAP FİŞİ (bilgi)</td></tr>
        @if ($masa)<tr><td>Masa</td><td class="r">{{ $masa }}</td></tr>@endif
        <tr><td>Adisyon</td><td class="r">#{{ $a->id }}</td></tr>
    </table>
    <hr>
    <table>
        @foreach ($kalemler as $k)
            <tr>
                <td>{{ $k->urun_adi }}<br><span style="font-size:10px">&nbsp;{{ rtrim(rtrim(number_format($k->adet, 2, ',', '.'), '0'), ',') }} x {{ number_format((float) $k->birim_fiyat, 2, ',', '.') }}</span></td>
                <td class="r b">{{ number_format((float) $k->tutar, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Ara Toplam</td><td class="r">{{ number_format((float) $ara, 2, ',', '.') }} ₺</td></tr>
        @if ($a->indirim > 0)<tr><td>İndirim</td><td class="r">-{{ number_format((float) $a->indirim, 2, ',', '.') }} ₺</td></tr>@endif
        @if ($a->ikram > 0)<tr><td>İkram</td><td class="r">-{{ number_format((float) $a->ikram, 2, ',', '.') }} ₺</td></tr>@endif
        <tr><td>Matrah</td><td class="r" style="font-size:10px">{{ number_format($matrah, 2, ',', '.') }} ₺</td></tr>
        <tr><td>KDV %10</td><td class="r" style="font-size:10px">{{ number_format($kdv, 2, ',', '.') }} ₺</td></tr>
        <tr class="b tot"><td>TOPLAM</td><td class="r">{{ number_format((float) $a->toplam, 2, ',', '.') }} ₺</td></tr>
    </table>
    <hr>
    <div class="c" style="font-size:10px">Bu belge mali değeri olmayan bilgi fişidir.<br>Mali belge: e-Arşiv / yazarkasa fişi.</div>
    <div class="c b" style="margin-top:6px">Afiyet olsun, teşekkürler! 🍽️</div>

    <div class="no-print c" style="margin-top:16px">
        <button onclick="window.print()" style="padding:8px 16px;font-size:14px">🖨️ Yazdır</button>
        <button onclick="window.close()" style="padding:8px 16px;font-size:14px">Kapat</button>
    </div>
</body>
</html>

@extends('resteos_yonetim.layout')
@section('baslik','AI / Santral Kredi')
@section('content')
@php $tl = fn($n) => number_format((float)$n,2,',','.').' ₺'; @endphp
@if($dusuk)<div class="ry-alert hata">⚠️ Kredi eşiğin altında! Kalan: {{ $tl($kalan) }}</div>@endif
<div class="ry-grid ry-g3" style="margin-bottom:18px">
  <div class="ry-metric"><div class="ry-mic mor">💳</div><div class="ry-mval">{{ $tl($k->toplam ?? 0) }}</div><div class="ry-mlbl">Yüklenen Toplam</div></div>
  <div class="ry-metric"><div class="ry-mic turuncu">🔥</div><div class="ry-mval">{{ $tl($k->harcanan ?? 0) }}</div><div class="ry-mlbl">Harcanan</div></div>
  <div class="ry-metric"><div class="ry-mic yesil">✅</div><div class="ry-mval">{{ $tl($kalan) }}</div><div class="ry-mlbl">Kalan Kredi</div></div>
</div>
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>➕ Kredi Ekle</h3>
    <form method="post" action="/resteos-yonetim/ai-kredi" class="ry-flex">@csrf
      <input type="hidden" name="islem" value="ekle">
      <input class="ry-input" type="number" step="0.01" name="tutar" placeholder="Eklenecek tutar (TL)" required>
      <button class="ry-btn ry-btn-ok" type="submit">Ekle</button>
    </form>
    <p class="ry-mut" style="margin-top:10px">AI santral/asistan çağrı maliyetleri bu havuzdan düşer. Harcama takibi santral logları bağlanınca otomatik işlenir.</p>
  </div>
  <div class="ry-card">
    <h3>⚙️ Ayarlar</h3>
    <form method="post" action="/resteos-yonetim/ai-kredi">@csrf
      <input type="hidden" name="islem" value="ayar">
      <div class="ry-field"><label>Toplam Kredi (TL)</label><input class="ry-input" type="number" step="0.01" name="toplam" value="{{ $k->toplam ?? 0 }}"></div>
      <div class="ry-row">
        <div class="ry-field"><label>USD→TRY Kur</label><input class="ry-input" type="number" step="0.01" name="kur" value="{{ $k->kur ?? 34 }}"></div>
        <div class="ry-field"><label>Düşük Kredi Eşiği</label><input class="ry-input" type="number" step="0.01" name="esik" value="{{ $k->esik ?? 100 }}"></div>
      </div>
      <button class="ry-btn ry-btn-primary" type="submit">Kaydet</button>
    </form>
  </div>
</div>
@endsection

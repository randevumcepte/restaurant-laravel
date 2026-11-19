@extends('layout.app')
@section('title', 'Test Sıfırlama')
@section('rk_ikon', 'restart_alt')
@section('baslik', 'Masaları Sıfırla (test)')
@section('rk_alt', 'Temiz baştan test için bütün açık hesapları kapatır')

@push('head')
<style>
#tsroot{max-width:640px}
#tsroot .ts-kart{background:var(--kart,#fff);border:1px solid var(--cizgi,#e5e7eb);border-radius:16px;padding:20px;margin-bottom:14px}
#tsroot h3{margin:0 0 12px;font-size:16px;font-weight:800;color:var(--ink)}
#tsroot .ts-ozet{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-bottom:6px}
#tsroot .ts-oz{background:var(--kart2,#f1f5f9);border-radius:12px;padding:12px}
#tsroot .ts-oz b{display:block;font-size:22px;font-weight:900;color:var(--ink)}
#tsroot .ts-oz span{font-size:12.5px;font-weight:700;color:var(--sub)}
#tsroot ul{margin:0;padding-left:20px;color:var(--ink);font-size:14px;line-height:1.7}
#tsroot .ts-uyari{background:#FEF3C7;color:#92400E;border-radius:12px;padding:12px 14px;font-size:13.5px;font-weight:700;margin:12px 0}
#tsroot input[type=text]{width:100%;box-sizing:border-box;border:1.5px solid var(--cizgi,#e5e7eb);border-radius:12px;padding:12px;font-size:15px;font-weight:700;letter-spacing:.08em;background:var(--kart,#fff);color:var(--ink)}
#tsroot .ts-btn{margin-top:12px;width:100%;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:800;color:#fff;background:#F43F5E;cursor:pointer}
#tsroot .ts-ok{background:#D1FAE5;color:#065F46;border-radius:12px;padding:14px;font-size:14px;font-weight:700;margin-bottom:14px}
@media (max-width:600px){#tsroot .ts-ozet{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<div id="tsroot">
  @if (session('hata'))<div class="rk-flash err">⚠️ {{ session('hata') }}</div>@endif
  @if ($sonuc)
    <div class="ts-ok">✅ Sıfırlandı: {{ $sonuc['adisyon'] }} açık hesap kapatıldı ({{ number_format($sonuc['tutar'], 0, ',', '.') }} ₺), {{ $sonuc['cagri'] }} çağrı temizlendi. Bütün masalar boş.
      <br>Müşteri telefonunda açık QR sekmesini kapatıp QR'ı yeniden okutun.</div>
  @endif

  <div class="ts-kart">
    <h3>Şu an</h3>
    <div class="ts-ozet">
      <div class="ts-oz"><b>{{ $ozet['adisyon'] }}</b><span>açık hesap</span></div>
      <div class="ts-oz"><b>{{ number_format($ozet['tutar'], 0, ',', '.') }} ₺</b><span>açık tutar</span></div>
      <div class="ts-oz"><b>{{ $ozet['cagri'] }}</b><span>garson çağrısı</span></div>
    </div>
  </div>

  <div class="ts-kart">
    <h3>Ne yapılır?</h3>
    <ul>
      <li>Bütün <b>açık hesaplar</b> ve içindeki ürünler <b>iptal</b> edilir (mutfak ekranından ve yazıcı sırasından düşer).</li>
      <li>Bütün masalar <b>boş</b> olur.</li>
      <li>Garson çağrıları listesi temizlenir.</li>
    </ul>
    <h3 style="margin-top:14px">Ne korunur?</h3>
    <ul>
      <li>Ödenmiş / kapanmış hesaplar ve raporlar</li>
      <li>Menü, masalar, masa QR'ları</li>
      <li>Müşteri AI konuşma kayıtları</li>
    </ul>
    <div class="ts-uyari">Gerçek müşteri varken kullanmayın: masadaki açık hesaplar geri getirilemez.</div>
    <form method="POST" action="/masalar-test-sifirla" onsubmit="return confirm('Bütün açık hesaplar iptal edilecek. Emin misiniz?')">
      @csrf
      <label style="font-size:13px;font-weight:700;color:var(--sub)">Onay için <b>SIFIRLA</b> yazın</label>
      <input type="text" name="onay" autocomplete="off" placeholder="SIFIRLA">
      <button type="submit" class="ts-btn" @if (!$ozet['adisyon'] && !$ozet['cagri']) disabled style="opacity:.5" @endif>Masaları sıfırla</button>
    </form>
  </div>
</div>
@endsection

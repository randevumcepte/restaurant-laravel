@extends('layout.app')
@section('rk_ikon', 'request_quote')
@section('baslik', 'Fiyat Araştır')
@section('title', 'Fiyat Araştır — Tedarikçi Seç')

@section('content')
<div class="rk-body">
<div style="max-width:860px">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px">
    <a href="/gelen-teklifler" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <div>
      <div style="font-size:18px;font-weight:800">Fiyat Araştır — Tedarikçi Seç</div>
      <div class="rk-sub" style="font-size:12px">İşaretlediğin tedarikçilere RFQ (fiyat isteği) maili gider. ⭐ = anlaşmalı.</div>
    </div>
  </div>

  @if (count($satirlar))
  <form method="POST" action="/fiyat-arastir-sec">
    @csrf
    @foreach ($satirlar as $s)
      <div class="rk-card" style="padding:14px;margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
          <span class="material-icons-outlined" style="color:var(--kirmizi);font-size:20px">inventory_2</span>
          <span style="font-weight:800">{{ $s['m']['ad'] }}</span>
          <span class="rk-sub" style="font-size:12px">mevcut {{ $s['m']['mevcut'] }} / kritik {{ $s['m']['kritik'] }} · öneri <b>{{ $s['m']['oneri'] }} {{ $s['m']['birim'] }}</b></span>
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;padding-left:4px">
          @foreach ($s['sups'] as $t)
            <label class="rk-yetki-satir">
              <input type="checkbox" name="sec[]" value="{{ $s['m']['id'] }}:{{ $t->id }}" class="rk-chk" @if($loop->first) checked @endif>
              <span>
                <b>{{ $t->ad }}</b>@if($t->anlasmali) ⭐@endif
                @if($t->guncel_fiyat) <span class="rk-sub">· son fiyat {{ number_format($t->guncel_fiyat, 2, ',', '.') }} ₺</span>@endif
                @if($t->email) <span style="color:var(--yesil)">· ✉️ mail var</span>@else <span style="color:var(--kirmizi)">· ✉️ mail YOK</span>@endif
                @if($t->telefon) <span style="color:var(--yesil)">📱</span>@endif
              </span>
            </label>
          @endforeach
        </div>
      </div>
    @endforeach
    <button class="rk-btn" style="background:var(--mavi)" type="submit"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">send</span> Seçilenlere Fiyat İste</button>
  </form>
  @else
  <div class="rk-card" style="padding:28px;text-align:center">
    <span class="material-icons-outlined" style="font-size:40px;color:var(--sub)">production_quantity_limits</span>
    <div style="font-weight:700;margin-top:8px">Kritik ürün yok veya ürünlere bağlı tedarikçi yok</div>
    <div class="rk-sub" style="font-size:12.5px;margin-top:6px">Bir malzemeyi kritik yap (Stok ekranından) ve tedarikçiye bağla; sonra burada görünür. Test için Gelen Teklifler → "Test Verisi Kur".</div>
  </div>
  @endif
</div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Otomatik Sipariş Önerisi')
@section('rk_ikon', 'auto_awesome')
@section('baslik', 'Otomatik Sipariş Önerisi')
@section('rk_alt', 'Par (hedef stok) + 30 günlük tüketime göre ne almalısın')

@section('content')
@php
  $oneriler = $oneriler ?? collect();
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $tahminiTutar = $oneriler->sum(fn($o) => $o->oneri * $o->ted['fiyat']);
@endphp

@if (session('so_ok'))<div class="rk-flash ok">{{ session('so_ok') }}</div>@endif
@if (session('so_hata'))<div class="rk-flash err">{{ session('so_hata') }}</div>@endif

<div class="rk-card" style="margin-bottom:16px;display:flex;gap:14px;align-items:center;flex-wrap:wrap">
  <span class="material-icons-outlined" style="color:var(--mor1);font-size:26px">auto_awesome</span>
  <div style="flex:1;min-width:220px"><div style="font-weight:800;color:var(--ink);font-size:14.5px">{{ $oneriler->count() }} malzeme sipariş bekliyor</div>
    <div class="rk-sub2" style="font-size:12px">Par (hedef stok) altına düşenler + 30 günlük tüketim hızına göre öneri. Tahmini tutar: <b class="rk-ink">{{ $tl($tahminiTutar) }}</b></div></div>
  <div class="rk-sub2" style="font-size:11.5px;max-width:320px">💡 <b>Par (hedef stok)</b> belirle → sistem o seviyeye tamamlayacak kadar önersin. Belirlemezsen kritik stoğun 2 katı hedeflenir.</div>
</div>

@if ($oneriler->isEmpty())
  <div class="rk-card" style="text-align:center;padding:40px">
    <span class="material-icons-outlined" style="font-size:52px;color:var(--yesil)">check_circle</span>
    <div class="rk-ink" style="font-weight:800;font-size:17px;margin-top:10px">Her şey yeterli 👍</div>
    <div class="rk-sub" style="margin-top:6px">Par/kritik seviyenin altına düşen malzeme yok. (Hedef stok belirlemediysen malzeme kartından belirleyebilirsin.)</div>
  </div>
@else
  <form method="POST" action="/siparis-onerisi/olustur" id="soForm">
    @csrf
    <input type="hidden" name="kalemler" id="soKalemler">
    <div class="rk-tablo">
      <table>
        <thead>
          <tr>
            <th style="width:30px"><input type="checkbox" id="soHepsi" onclick="soTumunu(this)" checked></th>
            <th>Malzeme</th><th class="sag">Mevcut</th><th class="sag">Kritik</th><th class="sag">Hedef (par)</th>
            <th class="sag">Günlük</th><th class="sag">Önerilen</th><th>Tedarikçi</th><th class="sag">Tahmini</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($oneriler as $o)
            <tr class="so-satir" data-id="{{ $o->id }}" data-ted="{{ $o->ted['id'] }}" data-birim="{{ $o->ted['birim_id'] }}" data-fiyat="{{ $o->ted['fiyat'] }}"
                style="{{ $o->kritikMi ? 'background:color-mix(in srgb,var(--kirmizi) 7%,transparent)' : '' }}">
              <td><input type="checkbox" class="so-chk" checked onchange="soTutar()"></td>
              <td style="font-weight:600">{{ $o->ad }} <span class="rk-sub2" style="font-size:10px">/ {{ $o->birim }}</span>@if($o->kritikMi)<span class="rk-rozet" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 13%,transparent);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent);margin-left:6px">kritik</span>@endif</td>
              <td class="sag {{ $o->kritikMi ? 'kir' : '' }}" style="font-weight:700">{{ $num($o->mevcut) }}</td>
              <td class="sag rk-sub2">{{ $num($o->kritik) }}</td>
              <td class="sag">
                <form method="POST" action="/siparis-onerisi/par" style="display:inline-flex;gap:4px;align-items:center" onclick="event.stopPropagation()">
                  @csrf<input type="hidden" name="malzeme_id" value="{{ $o->id }}">
                  <input type="number" name="hedef_stok" value="{{ $num($o->hedef) }}" step="0.001" min="0" class="rk-inp" style="width:72px;padding:5px 7px;font-size:12px">
                  <button class="rk-btn bos" style="padding:5px 7px" title="Par kaydet"><span class="material-icons-outlined" style="font-size:14px">save</span></button>
                </form>
              </td>
              <td class="sag rk-sub2">{{ $num($o->gunluk) }}</td>
              <td class="sag"><input type="number" class="so-mik rk-inp" value="{{ $num($o->oneri) }}" step="0.001" min="0" style="width:84px;padding:5px 7px;font-weight:800" oninput="soTutar()"></td>
              <td>{{ $o->ted['ad'] }} @if($o->ted['fiyat']>0)<span class="rk-sub2" style="font-size:11px">({{ $tl($o->ted['fiyat']) }})</span>@endif</td>
              <td class="sag so-ttr" style="font-weight:700">{{ $tl($o->oneri * $o->ted['fiyat']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px;flex-wrap:wrap;gap:10px">
      <div class="rk-sub2" style="font-size:13px">Seçili toplam: <b style="color:var(--mor1);font-size:15px" id="soToplam">{{ $tl($tahminiTutar) }}</b></div>
      <button type="submit" class="rk-btn lg" style="background:var(--mor1);max-width:380px"><span class="material-icons-outlined" style="color:#fff;font-size:20px">shopping_cart_checkout</span> Seçililerden Sipariş Taslağı Oluştur</button>
    </div>
    <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Siparişler <b>tedarikçiye göre gruplanıp</b> taslak olarak oluşturulur. Sonra Satınalma Siparişi'nden gözden geçirip gönderirsin.</div>
  </form>
@endif

<script>
function soTumunu(h){ document.querySelectorAll('.so-chk').forEach(c=>c.checked=h.checked); soTutar(); }
function soTutar(){
  let top=0;
  document.querySelectorAll('.so-satir').forEach(r=>{
    const chk=r.querySelector('.so-chk').checked, mik=parseFloat(r.querySelector('.so-mik').value)||0, f=parseFloat(r.dataset.fiyat)||0;
    const t=chk?mik*f:0; r.querySelector('.so-ttr').textContent=(mik*f).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
    top+=t;
  });
  document.getElementById('soToplam').textContent=top.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
}
document.getElementById('soForm')?.addEventListener('submit',function(e){
  const kal=[];
  document.querySelectorAll('.so-satir').forEach(r=>{
    if(!r.querySelector('.so-chk').checked) return;
    const mik=parseFloat(r.querySelector('.so-mik').value)||0; if(mik<=0) return;
    kal.push({malzeme_id:parseInt(r.dataset.id)||0,tedarikci_id:parseInt(r.dataset.ted)||0,birim_id:parseInt(r.dataset.birim)||0,miktar:mik,fiyat:parseFloat(r.dataset.fiyat)||0});
  });
  if(!kal.length){ e.preventDefault(); alert('En az bir ürün seç.'); return; }
  document.getElementById('soKalemler').value=JSON.stringify(kal);
});
</script>
@endsection

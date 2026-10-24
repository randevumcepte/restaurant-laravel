@extends('layout.app')
@section('rk_ikon', 'space_dashboard')
@section('baslik', 'Satın Alma Özeti')
@section('title', 'Satın Alma Özeti')

@push('head')
<style>
  #so-ozet{max-width:1040px}
  .so-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:18px}
  .so-k{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px;display:flex;flex-direction:column;gap:4px}
  .so-k .v{font-size:26px;font-weight:900;line-height:1}
  .so-k .l{font-size:12px;color:var(--sub);font-weight:600}
  .so-k .ik{font-size:20px}
  .durum-roz{font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;color:#fff}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="so-ozet">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
    <span class="material-icons-outlined" style="font-size:30px;color:var(--mor1)">shopping_cart</span>
    <div>
      <div style="font-size:19px;font-weight:800;color:var(--ink)">Merhaba {{ $ad ?: 'Satın Alma' }} 👋</div>
      <div class="rk-sub" style="font-size:12.5px">Satın alma operasyonunun özeti</div>
    </div>
  </div>

  {{-- İş yükü --}}
  <div class="so-kpi">
    <a href="/satinalma-talep" class="so-k" style="text-decoration:none;color:inherit;border-left:4px solid var(--mor1)">
      <span class="material-icons-outlined ik" style="color:var(--mor1)">assignment</span>
      <span class="v">{{ $talep_yeni }}</span><span class="l">Yeni talep</span>
    </a>
    <a href="/satinalma-talep" class="so-k" style="text-decoration:none;color:inherit">
      <span class="material-icons-outlined ik" style="color:var(--mavi)">request_quote</span>
      <span class="v">{{ $talep_teklif }}</span><span class="l">Teklif aşamasında</span>
    </a>
    <a href="/satinalma-talep" class="so-k" style="text-decoration:none;color:inherit">
      <span class="material-icons-outlined ik" style="color:var(--amber)">how_to_reg</span>
      <span class="v">{{ $talep_onayda }}</span><span class="l">Onay bekleyen</span>
    </a>
    <a href="/gelen-teklifler" class="so-k" style="text-decoration:none;color:inherit;border-left:4px solid var(--yesil)">
      <span class="material-icons-outlined ik" style="color:var(--yesil)">mark_email_read</span>
      <span class="v">{{ $teklif_geldi }}</span><span class="l">Teklif geldi</span>
    </a>
  </div>

  {{-- Durum --}}
  <div class="so-kpi">
    <a href="/satinalma-siparis" class="so-k" style="text-decoration:none;color:inherit">
      <span class="material-icons-outlined ik" style="color:var(--mavi)">shopping_cart_checkout</span>
      <span class="v">{{ $acik_siparis }}</span><span class="l">Açık sipariş</span>
    </a>
    <a href="/satinalma-siparis" class="so-k" style="text-decoration:none;color:inherit;{{ $geciken>0 ? 'border-left:4px solid var(--kirmizi)' : '' }}">
      <span class="material-icons-outlined ik" style="color:var(--kirmizi)">running_with_errors</span>
      <span class="v" style="{{ $geciken>0 ? 'color:var(--kirmizi)' : '' }}">{{ $geciken }}</span><span class="l">Geciken teslim</span>
    </a>
    <a href="/stok" class="so-k" style="text-decoration:none;color:inherit;{{ $kritik>0 ? 'border-left:4px solid var(--amber)' : '' }}">
      <span class="material-icons-outlined ik" style="color:var(--amber)">inventory_2</span>
      <span class="v" style="{{ $kritik>0 ? 'color:var(--amber)' : '' }}">{{ $kritik }}</span><span class="l">Kritik stok</span>
    </a>
    <a href="/satinalma-butce" class="so-k" style="text-decoration:none;color:inherit">
      <span class="material-icons-outlined ik" style="color:var(--yesil)">savings</span>
      <span class="v" style="font-size:19px">{{ number_format($ay_harcama,0,',','.') }} ₺</span>
      <span class="l">Bu ay harcama @if($ay_butce>0) / {{ number_format($ay_butce,0,',','.') }} ₺ bütçe @endif</span>
    </a>
  </div>

  {{-- Bütçe çubuğu --}}
  @if ($ay_butce>0)
    @php $y = round($ay_harcama/$ay_butce*100); $c = $y>100?'var(--kirmizi)':($y>=85?'var(--amber)':'var(--yesil)'); @endphp
    <div class="rk-card" style="padding:14px;margin-bottom:18px">
      <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:6px"><span>Bu ay bütçe kullanımı</span><span style="color:{{ $c }};font-weight:800">%{{ $y }}@if($y>100) · aşım!@endif</span></div>
      <div style="background:var(--card2);border-radius:20px;height:14px;overflow:hidden"><div style="width:{{ min(100,$y) }}%;height:100%;background:{{ $c }}"></div></div>
    </div>
  @endif

  {{-- Son talepler --}}
  <div class="rk-bolum" style="margin:6px 0 8px"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Son Talepler</span></div>
  @if (count($son_talepler))
  <div class="rk-card" style="padding:0;overflow:hidden">
    @php $dr = ['yeni'=>['Yeni','var(--mavi)'],'teklif'=>['Teklif','var(--mor1)'],'onayda'=>['Onayda','var(--amber)'],'onaylandi'=>['Onaylandı','var(--yesil)'],'siparis'=>['Siparişte','#0EA5E9'],'tamamlandi'=>['Tamam','var(--yesil)']]; @endphp
    @foreach ($son_talepler as $t)
      @php [$dm,$dc] = $dr[$t->durum] ?? [$t->durum,'var(--sub)']; @endphp
      <a href="/satinalma-talep" style="display:flex;align-items:center;gap:10px;padding:11px 14px;border-top:1px solid var(--line);text-decoration:none;color:inherit">
        <span class="material-icons-outlined" style="color:var(--sub);font-size:18px">assignment</span>
        <div style="flex:1"><div style="font-weight:600;font-size:13.5px">{{ $t->departman }}</div><div class="rk-sub" style="font-size:11.5px">{{ $t->isteyen_ad }} · {{ $t->kalem }} kalem · {{ \Carbon\Carbon::parse($t->created_at)->diffForHumans() }}</div></div>
        <span class="durum-roz" style="background:{{ $dc }}">{{ $dm }}</span>
      </a>
    @endforeach
  </div>
  @else
  <div class="rk-card" style="padding:24px;text-align:center;color:var(--sub)">Henüz talep yok.</div>
  @endif
</div>
</div>
@endsection

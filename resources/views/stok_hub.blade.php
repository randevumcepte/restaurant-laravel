@extends('layout.app')
@section('title', 'Stok & Satın Alma')
@section('rk_ikon', 'inventory_2')
@section('baslik', 'Stok & Satın Alma')
@section('rk_alt', 'Stok, reçete, alış ve gider yönetimi')

@push('head')
<style>
#hub-root{--card:#fff;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0}
body.koyu #hub-root{--card:#161c2e;--ink:#e2e8f0;--sub:#94a3b8;--line:#232b42}
#hub-root .hub-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
#hub-root .hub-kart{display:flex;align-items:center;gap:16px;background:var(--card);border:1px solid var(--line);border-radius:18px;
  padding:20px;text-decoration:none;box-shadow:0 2px 10px rgba(0,0,0,.05);transition:transform .1s,box-shadow .1s,border-color .12s}
#hub-root .hub-kart:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,0,0,.1)}
#hub-root .hub-ikon{display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:16px;flex-shrink:0}
#hub-root .hub-ikon .material-icons-outlined{font-size:30px}
#hub-root .hub-bilgi{min-width:0;flex:1}
#hub-root .hub-baslik{font-size:16px;font-weight:800;color:var(--ink)}
#hub-root .hub-alt{font-size:12px;color:var(--sub);margin-top:4px}
#hub-root .hub-ok{color:var(--sub);font-size:22px;flex-shrink:0}
</style>
@endpush

@section('content')
<div id="hub-root">
  <div class="hub-grid">
    @php
      $kartlar = [
        ['/stok/malzeme', 'Stok / Malzeme',   'Mevcut stok · kritik · fire',  'inventory_2',            '#4F46E5'],
        ['/recete',       'Reçeteler',         'Ürün maliyeti · food-cost',    'menu_book',              '#7C3AED'],
        ['/alis-fatura',  'Alış Faturaları',   'Stok girişi · fiyat uyarısı',  'receipt_long',           '#D97706'],
        ['/tedarikci',    'Tedarikçiler',      'Cari · toplam alış',           'local_shipping',         '#0EA5E9'],
        ['/gider',        'Giderler',          'Kira · fatura · maaş',         'account_balance_wallet', '#F43F5E'],
      ];
    @endphp
    @foreach ($kartlar as [$url, $baslik, $alt, $ikon, $renk])
      <a href="{{ $url }}" class="hub-kart">
        <div class="hub-ikon" style="background:{{ $renk }}26">
          <span class="material-icons-outlined" style="color:{{ $renk }}">{{ $ikon }}</span>
        </div>
        <div class="hub-bilgi">
          <div class="hub-baslik">{{ $baslik }}</div>
          <div class="hub-alt">{{ $alt }}</div>
        </div>
        <span class="material-icons-outlined hub-ok">chevron_right</span>
      </a>
    @endforeach
  </div>
</div>
@endsection

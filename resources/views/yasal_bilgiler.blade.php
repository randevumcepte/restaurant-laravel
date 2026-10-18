@extends('layout.app')
@section('title', 'Yasal Bilgiler')
@section('rk_ikon', 'gavel')
@section('baslik', 'Yasal Bilgiler & Sözleşmeler')
@section('rk_alt', 'Şirket bilgilerini bir kez doldurun — Gizlilik, KVKK, Mesafeli Satış vb. belgeler otomatik üretilsin')

@section('content')
@php
  $v = function($k) use ($row){ return $row && isset($row->$k) ? $row->$k : ''; };
  // Alan gruplama
  $gruplar = [
    'Şirket Bilgileri' => ['unvan','marka_adi','adres'],
    'Vergi / Resmi'    => ['vergi_dairesi','vergi_no','mersis_no'],
    'İletişim & KVKK'  => ['telefon','eposta','website','kep_adresi','veri_sorumlusu','kvkk_irtibat'],
    'Satış / Teslimat' => ['iade_suresi','teslimat_bilgi'],
  ];
@endphp
<div style="max-width:720px">

  @if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

  {{-- Durum kartı --}}
  <div class="rk-card" style="display:flex;align-items:flex-start;gap:12px">
    <span class="material-icons-outlined" style="font-size:30px;color:{{ empty($eksikler) ? '#16A34A' : '#F59E0B' }}">{{ empty($eksikler) ? 'verified' : 'error_outline' }}</span>
    <div style="flex:1">
      <div style="font-size:15px;font-weight:800;color:var(--ink)">{{ empty($eksikler) ? 'Bilgiler tam — sözleşmeler hazır' : 'Eksik bilgi var' }}</div>
      @if (empty($eksikler))
        <div class="rk-sub" style="font-size:12px;margin-top:2px">Tüm yasal belgeler şirket bilgilerinizle otomatik oluşturuluyor. Aşağıdan önizleyebilirsiniz.</div>
      @else
        <div class="rk-sub" style="font-size:12px;margin-top:2px">Şu zorunlu alanlar boş: <b>{{ implode(', ', $eksikler) }}</b>. Doldurmadan belgelerde köşeli parantezli uyarı görünür.</div>
      @endif
    </div>
  </div>

  <form method="post" action="/yasal-bilgiler/kaydet">
    @csrf
    @foreach ($gruplar as $baslik => $alanKeys)
      <div style="font-size:14px;font-weight:800;color:var(--ink);margin:20px 0 8px">{{ $baslik }}</div>
      <div class="rk-card" style="padding:14px">
        @foreach ($alanKeys as $k)
          @php [$etiket, $zorunlu, $ipucu] = $alanlar[$k]; @endphp
          <div style="margin-bottom:14px">
            <label style="display:block;font-size:12.5px;font-weight:700;color:var(--sub2);margin-bottom:5px">
              {{ $etiket }} @if($zorunlu)<span style="color:#F43F5E">*</span>@endif
            </label>
            @if ($k === 'adres' || $k === 'teslimat_bilgi')
              <textarea name="{{ $k }}" rows="2" placeholder="{{ $ipucu }}"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:14px;resize:vertical">{{ $v($k) }}</textarea>
            @else
              <input type="{{ $k === 'iade_suresi' ? 'number' : 'text' }}" name="{{ $k }}" value="{{ $v($k) }}" placeholder="{{ $ipucu }}"
                style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:14px">
            @endif
          </div>
        @endforeach
      </div>
    @endforeach

    <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:18px">
      <span class="material-icons-outlined" style="color:#fff">save</span>
      <span>Kaydet & Sözleşmeleri Üret</span>
    </button>
  </form>

  {{-- Üretilen belgeler --}}
  <div style="font-size:14px;font-weight:800;color:var(--ink);margin:26px 0 8px">Üretilen Belgeler (önizleme)</div>
  <div class="rk-card" style="padding:6px">
    @foreach ($tipler as $tip => $tbaslik)
      <a href="/yasal-onizle/{{ $tip }}" target="_blank" style="display:flex;align-items:center;gap:12px;padding:12px 10px;text-decoration:none;border-bottom:1px solid var(--line)">
        <span class="material-icons-outlined" style="font-size:20px;color:var(--mor1)">description</span>
        <span style="flex:1;color:var(--ink);font-size:14.5px;font-weight:600">{{ $tbaslik }}</span>
        <span class="material-icons-outlined" style="color:var(--sub);font-size:18px">open_in_new</span>
      </a>
    @endforeach
  </div>

  <div style="background:var(--card2);border-radius:12px;padding:12px;margin-top:16px;display:flex;gap:10px;align-items:flex-start">
    <span class="material-icons-outlined" style="font-size:18px;color:var(--sub)">info</span>
    <div class="rk-sub" style="font-size:12px;line-height:1.4">Belgeler standart şablonlardan üretilir ve müşteri uygulamasında (Ayarlar → Yasal) otomatik görünür. Yürürlüğe almadan önce bir hukuk danışmanına kontrol ettirmeniz önerilir.</div>
  </div>
</div>
@endsection

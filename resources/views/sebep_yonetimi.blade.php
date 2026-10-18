@extends('layout.app')
@section('rk_ikon', 'rule_folder')
@section('baslik', 'İptal / İkram Sebepleri')
@section('rk_alt', 'İptal, ürün silme (void), ikram ve iskonto işlemlerinde personele gösterilecek hazır sebepler — POS ve mobil uygulamada kullanılır')

@section('content')
@php
  $turler = ['void' => 'Ürün Silme', 'indirim' => 'İskonto', 'ikram' => 'İkram', 'iptal' => 'İptal'];
  $renk = ['void' => 'var(--kirmizi)', 'indirim' => 'var(--amber)', 'ikram' => 'var(--yesil)', 'iptal' => 'var(--mor1)'];
  $gruplar = $gruplar ?? [];
@endphp

@if (session('sebep_ok'))<div class="rk-flash ok">{{ session('sebep_ok') }}</div>@endif
@if (session('sebep_hata'))<div class="rk-flash err">{{ session('sebep_hata') }}</div>@endif

<div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(320px,1fr))">
  @foreach ($turler as $tur => $ad)
    @php
      $c = $renk[$tur];
      $liste = $gruplar[$tur] ?? [];
    @endphp
    <div class="rk-card" style="display:flex;flex-direction:column;gap:12px">

      {{-- Bölüm başlığı: renkli çubuk + ad + sayı rozeti --}}
      <div class="rk-bolum" style="margin:0">
        <span class="cubuk" style="background:{{ $c }}"></span>
        <span class="b">{{ $ad }}</span>
        <span class="sayi" style="margin-left:auto;color:{{ $c }};background:color-mix(in srgb,{{ $c }} 14%,transparent)">{{ count($liste) }} sebep</span>
      </div>

      {{-- Sebep satırları --}}
      <div style="display:flex;flex-direction:column;gap:8px">
        @if (empty($liste))
          <div style="text-align:center;color:var(--sub);font-size:13px;padding:18px 0">Bu türde henüz sebep yok.</div>
        @else
          @foreach ($liste as $s)
            <div style="display:flex;align-items:center;gap:8px;background:var(--card2);border:1px solid var(--line);border-radius:10px;padding:9px 12px">
              <span class="material-icons-outlined" style="font-size:16px;color:{{ $c }}">label</span>
              <span style="flex:1;font-size:14px;color:var(--ink)">{{ $s->metin }}</span>
              <form method="POST" action="/sebep-yonetimi/sil" onsubmit="return confirm('Bu sebep silinsin mi?');" style="margin:0;line-height:0">
                @csrf
                <input type="hidden" name="id" value="{{ $s->id }}">
                <button type="submit" title="Sil" style="background:none;border:none;cursor:pointer;padding:4px;color:var(--kirmizi);display:inline-flex">
                  <span class="material-icons-outlined" style="font-size:18px">delete_outline</span>
                </button>
              </form>
            </div>
          @endforeach
        @endif
      </div>

      {{-- Ekleme kutusu --}}
      <form method="POST" action="/sebep-yonetimi/ekle" style="display:flex;gap:8px;margin:0;padding-top:4px;border-top:1px solid var(--line)">
        @csrf
        <input type="hidden" name="tur" value="{{ $tur }}">
        <input type="text" name="metin" required maxlength="120" placeholder="Yeni sebep ekle..." class="rk-inp" style="flex:1">
        <button type="submit" class="rk-btn" style="background:{{ $c }};white-space:nowrap">
          <span class="material-icons-outlined" style="font-size:18px;color:#fff">add</span>
          <span>Ekle</span>
        </button>
      </form>

    </div>
  @endforeach
</div>
@endsection

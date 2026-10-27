@extends('layout.app')
@section('title', 'Son Mal Kabuller')
@section('rk_ikon', 'history')
@section('rk_geri', '/mal-kabul')
@section('baslik', 'Son Mal Kabuller')
@section('rk_alt', $kabuller->count() . ' kayıt')

@section('content')
@php
  $kabuller = $kabuller ?? collect();
@endphp

@if ($kabuller->isEmpty())
  <div class="rk-card" style="text-align:center;padding:48px 24px">
    <span class="material-icons-outlined" style="font-size:40px;color:var(--sub2);display:block;margin-bottom:10px">inventory_2</span>
    <div class="rk-sub">Henüz mal kabul yok.</div>
    <a href="/mal-kabul" class="rk-btn" style="background:var(--mor1);margin-top:14px;display:inline-flex;width:auto">
      <span class="material-icons-outlined" style="font-size:17px">add</span> Yeni Mal Kabul
    </a>
  </div>
@else
  {{-- Masaustu: tablo --}}
  <div class="rk-tablo mkl-tablo">
    <table>
      <thead>
        <tr>
          <th>Tarih</th>
          <th>Tedarikçi</th>
          <th>İrsaliye No</th>
          <th class="sag"></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($kabuller as $m)
          <tr>
            <td class="rk-sub2" style="white-space:nowrap">{{ $m->tarih ? \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') : '—' }}</td>
            <td style="font-weight:600">{{ $m->tedarikci }}</td>
            <td class="rk-sub">{{ $m->irsaliye_no ?: '—' }}</td>
            <td class="sag">
              <a href="/mal-kabul-detay?id={{ $m->id }}" class="rk-btn bos" style="padding:6px 11px">
                <span class="material-icons-outlined" style="font-size:15px">visibility</span> Detay
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Mobil: kart listesi --}}
  <div class="mkl-kartlar">
    @foreach ($kabuller as $m)
      <a href="/mal-kabul-detay?id={{ $m->id }}" class="mkl-kart">
        <div class="mkl-ikon"><span class="material-icons-outlined">receipt_long</span></div>
        <div class="mkl-bilgi">
          <div class="mkl-ad">{{ $m->tedarikci }}</div>
          <div class="mkl-alt">
            {{ $m->tarih ? \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') : '—' }}
            @if($m->irsaliye_no) · İrs: {{ $m->irsaliye_no }} @endif
          </div>
        </div>
        <span class="material-icons-outlined" style="color:var(--sub2);font-size:20px;flex-shrink:0">chevron_right</span>
      </a>
    @endforeach
  </div>
@endif

<style>
.mkl-kartlar{display:none;flex-direction:column;gap:8px}
.mkl-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 14px;display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;transition:box-shadow .14s,transform .14s}
.mkl-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.08);transform:translateY(-1px)}
.mkl-ikon{width:38px;height:38px;border-radius:11px;background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.mkl-ikon .material-icons-outlined{font-size:19px}
.mkl-bilgi{flex:1;min-width:0}
.mkl-ad{font-size:14.5px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mkl-alt{font-size:11.5px;color:var(--sub);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
@media(max-width:720px){
  .mkl-tablo{display:none}
  .mkl-kartlar{display:flex}
}
</style>
@endsection

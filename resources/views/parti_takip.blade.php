@extends('layout.app')
@section('title', 'Parti / SKT Takip')
@section('rk_ikon', 'event_busy')
@section('baslik', 'Parti / SKT Takip')
@section('rk_alt', 'FEFO · ilk bozulan ilk çıkar · yaklaşan son kullanma uyarısı')

@section('content')
@php
  $partiler = $partiler ?? collect();
  $yaklasanSay = (int) ($yaklasanSay ?? 0);
  $gecmisSay = (int) ($gecmisSay ?? 0);
  $esik = (int) ($esik ?? 7);
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
@endphp

{{-- Özet şerit --}}
<div class="rk-grid" style="grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:16px">
  <div class="rk-card" style="text-align:center"><div style="font-size:30px;font-weight:900;color:var(--kirmizi)">{{ $gecmisSay }}</div><div class="rk-sub2" style="font-size:12px">Son kullanma GEÇMİŞ</div></div>
  <div class="rk-card" style="text-align:center"><div style="font-size:30px;font-weight:900;color:var(--amber)">{{ $yaklasanSay }}</div><div class="rk-sub2" style="font-size:12px">{{ $esik }} güne kadar YAKLAŞAN</div></div>
  <div class="rk-card" style="text-align:center"><div style="font-size:30px;font-weight:900;color:var(--ink)">{{ $partiler->count() }}</div><div class="rk-sub2" style="font-size:12px">Aktif parti (kalan {{ '>' }} 0)</div></div>
</div>

@if ($gecmisSay > 0 || $yaklasanSay > 0)
  <div class="rk-card" style="margin-bottom:14px;background:color-mix(in srgb,var(--kirmizi) 7%,transparent);border-color:color-mix(in srgb,var(--kirmizi) 28%,transparent);display:flex;align-items:center;gap:12px">
    <span class="material-icons-outlined" style="color:var(--kirmizi);font-size:24px">warning</span>
    <div style="flex:1"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Dikkat gereken partiler var</div><div class="rk-sub2" style="font-size:12px">{{ $gecmisSay }} parti son kullanma geçti, {{ $yaklasanSay }} parti yaklaşıyor. FEFO sayesinde satışta <b>önce bunlar düşer</b> — ama geçmiş olanları fire/imha etmelisin.</div></div>
  </div>
@endif

<div class="rk-bolum"><span class="cubuk" style="background:var(--kirmizi)"></span><span class="b">Partiler (SKT sırasıyla)</span></div>
@if ($partiler->isEmpty())
  <div class="rk-card" style="text-align:center;padding:36px">
    <span class="material-icons-outlined" style="font-size:48px;color:var(--line)">inventory_2</span>
    <div class="rk-sub" style="margin-top:10px">Henüz parti yok. Mal kabulde <b>lot/SKT</b> girdikçe partiler burada oluşur ve satışta FEFO ile düşer.</div>
  </div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:24%">Malzeme</th><th>Lot</th><th class="sag">SKT</th><th class="orta">Durum</th><th class="sag">Kalan</th><th>Tedarikçi</th><th class="sag">Giriş</th></tr></thead>
      <tbody>
        @foreach ($partiler as $p)
          @php
            $satirBg = $p->durum === 'gecmis' ? 'color-mix(in srgb,var(--kirmizi) 8%,transparent)' : ($p->durum === 'yaklasan' ? 'color-mix(in srgb,var(--amber) 9%,transparent)' : '');
          @endphp
          <tr style="background:{{ $satirBg }}">
            <td style="font-weight:600">{{ $p->malzeme }}</td>
            <td class="rk-sub2" style="font-size:12px">{{ $p->lot_no ?: '—' }}</td>
            <td class="sag" style="font-weight:700;color:{{ $p->durum==='gecmis' ? 'var(--kirmizi)' : ($p->durum==='yaklasan' ? 'var(--amber)' : 'var(--ink)') }}">{{ $p->skt ? \Carbon\Carbon::parse($p->skt)->format('d.m.Y') : '—' }}</td>
            <td class="orta">
              @if ($p->durum === 'gecmis')
                <span class="rk-rozet" style="color:var(--kirmizi);background:color-mix(in srgb,var(--kirmizi) 14%,transparent);border-color:color-mix(in srgb,var(--kirmizi) 45%,transparent)">Geçti {{ $p->kalan_gun !== null ? '('.abs($p->kalan_gun).' gün)' : '' }}</span>
              @elseif ($p->durum === 'yaklasan')
                <span class="rk-rozet" style="color:var(--amber);background:color-mix(in srgb,var(--amber) 14%,transparent);border-color:color-mix(in srgb,var(--amber) 45%,transparent)">{{ $p->kalan_gun }} gün</span>
              @else
                <span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 12%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">Normal</span>
              @endif
            </td>
            <td class="sag" style="font-weight:800">{{ $num($p->kalan_miktar) }}</td>
            <td class="rk-sub2" style="font-size:12px">{{ $p->tedarikci ?: '—' }}</td>
            <td class="sag rk-sub2" style="font-size:12px">{{ $p->giris_tarihi ? \Carbon\Carbon::parse($p->giris_tarihi)->format('d.m.Y') : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="rk-sub2" style="font-size:11.5px;margin-top:10px">FEFO: Satış tüketiminde sistem <b>en yakın SKT'li partiden</b> otomatik düşer. Kalan miktar temel birimdedir (g/ml/adet).</div>
@endif
@endsection

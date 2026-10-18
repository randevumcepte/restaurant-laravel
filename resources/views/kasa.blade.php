@extends('layout.app')
@section('title', 'Kasa')
@section('rk_ikon', 'account_balance')
@section('baslik', 'Kasa (Vardiya)')
@section('rk_alt', ($acik ?? false) ? ('Açık · ' . ($acilis ?? '')) : 'Kapalı')

@section('content')
@php
  $periodlar = ['gun' => 'Bugün', 'hafta' => 'Hafta', 'ay' => 'Ay'];
  $period = $period ?? 'gun';
  $acik = $acik ?? false;
  $giris = (float) ($giris ?? 0);
  $cikis = (float) ($cikis ?? 0);
  $beklenen = (float) ($beklenen ?? 0);
  $devir = (float) ($devir ?? 0);
  $sonDevir = (float) ($son_devir ?? 0);
  $hareketler = $hareketler ?? [];
  $etiket = ['devir' => 'Devir', 'satis' => 'Nakit Satış', 'tahsilat' => 'Tahsilat', 'gider' => 'Gider', 'personel' => 'Personel', 'al' => 'Kasadan Al', 'koy' => 'Kasaya Koy'];
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';
@endphp

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

@if (!$acik)
  {{-- ===== KASA KAPALI ===== (Flutter _masaustuKapali) --}}
  <div style="max-width:460px;margin:40px auto 0">
    <div class="rk-card" style="padding:32px;text-align:center">
      <span class="material-icons-outlined" style="font-size:64px;color:var(--line)">account_balance</span>
      <div style="font-size:22px;font-weight:800;color:var(--ink);margin-top:14px">Kasa kapalı</div>
      <div class="rk-sub" style="font-size:13px;margin-top:8px;line-height:1.4">
        Vardiyayı başlatmak için kasayı açın.<br>Açılış devir = şu an çekmecedeki nakit.
      </div>
      <div class="rk-sub2" style="font-size:12.5px;margin-top:12px;line-height:1.4">
        Kasa, uygulamadan (POS) açılır. Açıldığında güncel durum burada görünür.
      </div>
      @if ($sonDevir > 0)
        <div class="rk-sub2" style="font-size:12px;margin-top:10px">Son kapanış: <b class="rk-ink">{{ $tl($sonDevir) }}</b></div>
      @endif
    </div>
  </div>
@else
  {{-- ===== KASA AÇIK ===== (Flutter _masaustuAcik) --}}
  {{-- Üst özet — 3 istatistik kartı --}}
  <div class="rk-grid" style="grid-template-columns:12fr 9fr 9fr">
    <div class="rk-stat">
      <div class="head" style="background:var(--mor1)">
        <span>Kasada Beklenen Nakit</span>
        <span class="rk-rozet" style="color:#fff;background:rgba(255,255,255,.22);border-color:rgba(255,255,255,.35)">Açık · {{ $acilis ?? '' }}</span>
      </div>
      <div class="body">
        <div class="buyuk">{{ $tl($beklenen) }}</div>
        <div class="satir"><span class="e">Açan</span><span class="d">{{ $acan ?? '-' }}</span></div>
        <div class="satir"><span class="e">Açılış devir</span><span class="d">{{ $tl($devir) }}</span></div>
      </div>
    </div>
    <div class="rk-stat">
      <div class="head" style="background:var(--yesil)"><span>Giriş (+)</span></div>
      <div class="body">
        <div class="buyuk" style="color:var(--yesil)">{{ $tl($giris) }}</div>
        <div class="satir"><span class="e">Devir dahil kasaya eklenen</span><span class="d"></span></div>
      </div>
    </div>
    <div class="rk-stat">
      <div class="head" style="background:var(--kirmizi)"><span>Çıkış (−)</span></div>
      <div class="body">
        <div class="buyuk" style="color:var(--kirmizi)">{{ $tl($cikis) }}</div>
        <div class="satir"><span class="e">Kasadan alınan</span><span class="d"></span></div>
      </div>
    </div>
  </div>

  {{-- Manuel kasa hareketi (Flutter: Kasaya Koy / Kasadan Al) --}}
  <div class="rk-card" style="margin-top:18px">
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Manuel Kasa Hareketi</span>
    </div>
    <form method="POST" action="/kasa/hareket" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px">
      @csrf
      <div style="min-width:160px">
        <label class="rk-lbl" style="margin-top:0">Yön</label>
        <select name="yon" class="rk-inp">
          <option value="giris">Kasaya Koy (+)</option>
          <option value="cikis">Kasadan Al (−)</option>
        </select>
      </div>
      <div style="width:140px">
        <label class="rk-lbl" style="margin-top:0">Tutar</label>
        <input type="number" name="tutar" step="0.01" min="0.01" required placeholder="0,00" class="rk-inp">
      </div>
      <div style="flex:1;min-width:200px">
        <label class="rk-lbl" style="margin-top:0">Açıklama (opsiyonel)</label>
        <input type="text" name="aciklama" maxlength="200" placeholder="Örn. bozuk para, kasa düzeltme" class="rk-inp">
      </div>
      <button type="submit" class="rk-btn" style="background:var(--mor1);padding:11px 18px">
        <span class="material-icons-outlined" style="color:#fff;font-size:18px">save</span> Kaydet
      </button>
    </form>
    <div class="rk-sub2" style="font-size:11.5px;margin-top:10px">Kasa açma / sayım-kapatma işlemleri uygulamadan (POS) yapılır.</div>
  </div>
@endif

{{-- Hareketler (dönem filtresi + tablo) --}}
<div class="rk-bolum" style="margin-top:22px;justify-content:space-between">
  <div style="display:flex;align-items:center;gap:8px">
    <span class="cubuk" style="background:var(--mor1)"></span>
    <span class="b">Hareketler</span>
    <span class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">{{ count($hareketler) }}</span>
  </div>
  <div style="display:inline-flex;background:var(--card2);border-radius:10px;padding:4px;gap:2px">
    @foreach ($periodlar as $k => $ad)
      <a href="/kasa?period={{ $k }}" class="rk-btn {{ $period === $k ? '' : 'bos' }}"
         style="{{ $period === $k ? 'background:var(--mor1)' : 'border-color:transparent' }};padding:7px 14px">{{ $ad }}</a>
    @endforeach
  </div>
</div>

@if (empty($hareketler))
  <div class="rk-card" style="text-align:center;padding:40px">
    <div class="rk-sub">Bu dönemde kasa hareketi yok.</div>
  </div>
@else
  <div class="rk-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:14%">Tip</th>
          <th style="width:30%">Açıklama</th>
          <th class="orta" style="width:12%">Yön</th>
          <th class="sag" style="width:16%">Tutar</th>
          <th class="sag" style="width:16%">Zaman</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($hareketler as $h)
          @php
            $girisMi = ($h['yon'] ?? '') === 'giris';
            $renk = $girisMi ? 'var(--yesil)' : 'var(--kirmizi)';
            $ikon = $girisMi ? 'south_west' : 'north_east';
          @endphp
          <tr>
            <td style="font-weight:600">{{ $etiket[$h['tip'] ?? ''] ?? ($h['tip'] ?? '-') }}</td>
            <td class="rk-sub">
              {{ $h['aciklama'] ?? '' }}@if (!empty($h['personel'])) · {{ $h['personel'] }}@endif
            </td>
            <td class="orta">
              <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 13%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">
                <span class="material-icons-outlined" style="font-size:14px">{{ $ikon }}</span>{{ $girisMi ? 'Giriş' : 'Çıkış' }}
              </span>
            </td>
            <td class="sag" style="font-weight:800;color:{{ $renk }};white-space:nowrap">{{ $girisMi ? '+' : '−' }}{{ $tl($h['tutar'] ?? 0) }}</td>
            <td class="sag rk-sub2" style="white-space:nowrap">{{ $h['zaman'] ?? '' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Sayım (Envanter)')

@section('content')
@php
  $malzemeler = $malzemeler ?? [];
  $sonSayimlar = $sonSayimlar ?? [];
  $toplamKalem = count($malzemeler);

  // Flutter _mik: tam sayıysa ondalıksız, değilse 2 hane
  $mik = function ($v) {
    $v = (float) $v;
    return $v == round($v) ? number_format($v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
  };
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . 'TL';
@endphp

{{-- Flutter _sonucGoster: kayıt sonrası sonuç kartı --}}
@if (session('sayim_sonuc'))
  @php
    $s = session('sayim_sonuc');
    $fm = (float) ($s['toplam_fark_maliyet'] ?? 0);
    $renk = $fm < 0 ? 'var(--kirmizi)' : ($fm > 0 ? 'var(--amber)' : 'var(--yesil)');
  @endphp
  <div class="rk-card" style="margin-bottom:18px;border-color:color-mix(in srgb,{{ $renk }} 45%,transparent)">
    <div class="b" style="font-size:15px;font-weight:800;color:var(--ink);margin-bottom:4px">Sayım kaydedildi</div>
    <div class="rk-sub" style="font-size:13px">{{ $s['sayilan_kalem'] ?? 0 }} kalem sayıldı · {{ $s['eksik'] ?? 0 }} eksik · {{ $s['fazla'] ?? 0 }} fazla</div>
    <div style="margin-top:6px;font-size:15px;font-weight:800;color:{{ $renk }}">
      {{ $fm < 0 ? 'Toplam açık: ' . $tl(abs($fm)) . ' (kayıp)' : ($fm > 0 ? 'Toplam fazla: ' . $tl($fm) : 'Fark yok — tertemiz.') }}
    </div>
  </div>
@endif

@if (session('sayim_hata'))
  <div class="rk-flash err">{{ session('sayim_hata') }}</div>
@endif

{{-- Flutter üstteki mavi bilgi kutusu --}}
<div class="rk-card" style="margin-bottom:16px;display:flex;align-items:flex-start;gap:8px;background:color-mix(in srgb,var(--mavi) 10%,transparent);border-color:color-mix(in srgb,var(--mavi) 35%,transparent)">
  <span class="material-icons-outlined" style="color:var(--mavi);font-size:18px">info</span>
  <span style="color:var(--sub2);font-size:12.5px;line-height:1.35">Fiili sayımı girin. Sistem, "olması gereken" ile karşılaştırıp farkı (kayıp/fazla) çıkarır. Sadece saydıklarınızı girin.</span>
</div>

@if (!$malzemeler)
  <div class="rk-card" style="text-align:center;padding:40px">
    <div class="rk-sub">Stok takipli malzeme yok.</div>
  </div>
@else
{{-- Flutter: malzeme tablosu bir FORM; canlı fark JS ile --}}
<form method="POST" action="/sayim/kaydet">
  @csrf

  {{-- Araç çubuğu: arama + kalem sayısı (Flutter arama alanı + "x kalem") --}}
  <div class="rk-bolum" style="justify-content:space-between;margin-bottom:12px">
    <div style="display:inline-flex;align-items:center;gap:10px;flex:1;max-width:360px">
      <input type="text" id="sayimAra" placeholder="Malzeme ara" class="rk-inp" style="max-width:300px" onkeyup="sayimFiltre()">
      <span class="rk-sub" style="font-size:12px;white-space:nowrap">{{ $toplamKalem }} kalem</span>
    </div>
  </div>

  <div class="rk-tablo" style="margin-bottom:18px">
    <table>
      <thead>
        <tr>
          <th style="width:34%">Malzeme</th>
          <th style="width:22%">Kategori</th>
          <th class="sag" style="width:20%">Sistemde (teorik)</th>
          <th class="orta" style="width:14%">Sayılan</th>
          <th class="sag" style="width:10%">Fark</th>
        </tr>
      </thead>
      <tbody id="sayimTablo">
        @foreach ($malzemeler as $m)
          @php $teorik = (float) ($m['teorik'] ?? 0); @endphp
          <tr class="sayim-satir" data-ad="{{ mb_strtolower($m['ad']) }}">
            <td class="rk-ink" style="font-weight:600">{{ $m['ad'] }}</td>
            <td class="rk-sub">{{ $m['kategori'] ?? '—' }}</td>
            <td class="sag rk-sub" style="white-space:nowrap">{{ $mik($teorik) }} <span style="font-size:11.5px;color:var(--sub)">{{ $m['birim'] }}</span></td>
            <td class="orta">
              <input type="number" step="any" min="0" name="sayilan[{{ $m['malzeme_id'] }}]"
                     class="rk-inp sayim-input" style="width:96px;padding:8px 6px;text-align:center;display:inline-block"
                     data-teorik="{{ $teorik }}" placeholder="Say" oninput="sayimFark(this)">
            </td>
            <td class="sag sayim-fark" style="font-weight:800;color:var(--sub)">—</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Flutter alt sabit Kaydet butonu: "Sayımı Kaydet (N kalem)" --}}
  <button type="submit" class="rk-btn lg" style="background:var(--mor1);max-width:520px;margin:0 auto 24px">
    <span class="material-icons-outlined" style="color:#fff;font-size:18px">save</span>
    Sayımı Kaydet (<span id="sayimGirilen">0</span> kalem)
  </button>
</form>
@endif

{{-- Flutter Sayım Geçmişi kartları (fark maliyet) --}}
@if ($sonSayimlar)
  <div class="rk-bolum" style="margin-top:4px">
    <span class="cubuk" style="background:var(--mor1)"></span>
    <span class="b">Son Sayımlar</span>
  </div>
  <div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
    @foreach ($sonSayimlar as $s)
      @php
        $fm = (float) $s['fark_maliyet'];
        $renk = $fm < 0 ? 'var(--kirmizi)' : ($fm > 0 ? 'var(--amber)' : 'var(--yesil)');
      @endphp
      <div class="rk-card" style="display:flex;align-items:center;justify-content:space-between;padding:13px">
        <div>
          <div class="rk-ink" style="font-weight:600;font-size:14px">{{ $s['tarih'] }}</div>
          <div class="rk-sub" style="font-size:12px">{{ $s['kalem'] }} kalem · {{ $s['eksik'] }} eksik</div>
        </div>
        <div style="font-weight:800;font-size:14px;color:{{ $renk }}">
          {{ $fm < 0 ? '-' . $tl(abs($fm)) : ($fm > 0 ? '+' . $tl($fm) : '0') }}
        </div>
      </div>
    @endforeach
  </div>
@endif

<script>
  function sayimFiltre() {
    var q = (document.getElementById('sayimAra').value || '').toLowerCase().trim();
    document.querySelectorAll('.sayim-satir').forEach(function (tr) {
      tr.style.display = (!q || (tr.getAttribute('data-ad') || '').indexOf(q) !== -1) ? '' : 'none';
    });
  }
  function sayimGirilenSay() {
    var n = 0;
    document.querySelectorAll('.sayim-input').forEach(function (inp) { if (inp.value.trim() !== '') n++; });
    var el = document.getElementById('sayimGirilen');
    if (el) el.textContent = n;
  }
  function sayimFark(inp) {
    var td = inp.closest('tr').querySelector('.sayim-fark');
    var t = parseFloat(inp.getAttribute('data-teorik')) || 0;
    var v = inp.value.trim();
    if (v === '') {
      td.textContent = '—';
      td.style.color = 'var(--sub)';
    } else {
      var f = (parseFloat(v.replace(',', '.')) || 0) - t;
      var r = Math.round(f * 10000) / 10000;
      // Flutter _mik: tam sayı → ondalıksız, değilse 2 hane
      var abs = Math.abs(r);
      var govde = (abs === Math.round(abs)) ? String(Math.round(abs)) : abs.toFixed(2);
      td.textContent = (r > 0 ? '+' : (r < 0 ? '-' : '')) + govde;
      // -0.0001 eşik: eksik kırmızı, fazla amber, eşit yeşil
      td.style.color = (r < -0.0001) ? 'var(--kirmizi)' : (r > 0.0001 ? 'var(--amber)' : 'var(--yesil)');
    }
    sayimGirilenSay();
  }
</script>
@endsection

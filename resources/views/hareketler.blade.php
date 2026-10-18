@extends('layout.app')
@section('rk_ikon', 'history')
@section('baslik', 'Hareketler')
@section('rk_alt', 'Tüm işletme hareketleri tek akışta')

@section('content')
@php
  // Flutter hareketler_screen._kat BİREBİR: kategori => [renk(css var), material-icon, etiket]
  $katMeta = [
    'satis'    => ['var(--yesil)',    'receipt_long',                  'Satış'],
    'odeme'    => ['var(--yesil)',    'payments',                      'Ödeme'],
    'kayip'    => ['var(--kirmizi)',  'remove_circle_outline',         'Kayıp'],
    'stok'     => ['#3B82F6',         'inventory_2',                   'Stok'],
    'kasa'     => ['#14B8A6',         'point_of_sale',                 'Kasa'],
    'cari'     => ['#6366F1',         'account_balance_wallet',        'Cari'],
    'personel' => ['var(--amber)',    'badge',                         'Personel'],
    'gider'    => ['#EA580C',         'money_off',                     'Gider'],
    'fis'      => ['#7C3AED',         'description',                   'Fiş'],
    'menu'     => ['#7C3AED',         'restaurant_menu',               'Menü'],
    'recete'   => ['#7C3AED',         'menu_book',                     'Reçete'],
    'ayar'     => ['#94A3B8',         'settings',                      'Ayar'],
    'giris'    => ['#94A3B8',         'login',                         'Giriş'],
    'mesai'    => ['#14B8A6',         'schedule',                      'Mesai'],
    'cagri'    => ['var(--kirmizi)',  'notifications_active',          'Çağrı'],
    'diger'    => ['#94A3B8',         'bolt',                          'Diğer'],
  ];
  $meta = fn($k) => $katMeta[$k] ?? $katMeta['diger'];

  // Flutter _kategori(): backend 'diger' ise başlık/açıklamadan anlamlı kategori türet.
  $kategoriBul = function ($h) use ($katMeta) {
    $k = (string) ($h['kategori'] ?? '');
    if ($k !== '' && $k !== 'diger' && isset($katMeta[$k])) return $k;
    $s = mb_strtolower(($h['baslik'] ?? '') . ' ' . ($h['aciklama'] ?? ''));
    $c = function (array $ks) use ($s) { foreach ($ks as $x) { if (mb_strpos($s, $x) !== false) return true; } return false; };
    if ($c(['iptal', 'geri alın', 'geri alind', 'void', 'iskonto', 'indirim', 'ikram', 'fire'])) return 'kayip';
    if ($c(['ödeme', 'odeme', 'tahsil', 'nakit', 'kredi', 'kart', 'yemek kart'])) return 'odeme';
    if ($c(['masa', 'adisyon', 'sipariş', 'siparis', 'satış', 'satis', 'birleştir', 'taşın', 'tasin', 'böl', 'bol'])) return 'satis';
    if ($c(['stok', 'malzeme', 'sayım', 'sayim', 'alış', 'alis', 'tedarik'])) return 'stok';
    if ($c(['kasa', 'çekmece', 'cekmece', 'vardiya'])) return 'kasa';
    if ($c(['cari', 'açık hesap', 'acik hesap', 'borç', 'borc'])) return 'cari';
    if ($c(['personel', 'maaş', 'maas', 'yetki', 'avans'])) return 'personel';
    if ($c(['mesai', 'puantaj'])) return 'mesai';
    if ($c(['gider', 'masraf'])) return 'gider';
    if ($c(['menü', 'menu', 'ürün', 'urun', 'kategori'])) return 'menu';
    if ($c(['reçete', 'recete', 'yarı mamül', 'yari mamul'])) return 'recete';
    if ($c(['fiş', 'fis', 'fatura'])) return 'fis';
    if ($c(['giriş', 'giris', 'login', 'çıkış', 'cikis', 'logout'])) return 'giris';
    if ($c(['ayar', 'tema', 'şema', 'sema'])) return 'ayar';
    if ($c(['çağrı', 'cagri', 'garson'])) return 'cagri';
    return $k !== '' ? $k : 'diger';
  };

  // Flutter _rolRenk()
  $rolRenk = [
    'Sahip'  => '#7C3AED',
    'Müdür'  => '#2563EB',
    'Garson' => '#0D9488',
    'Kasa'   => '#EA580C',
  ];

  $gunlar = [1 => 'Bugün', 7 => 'Hafta', 30 => 'Ay'];
  // Çipler: Hepsi + kategori sırası (Flutter ile aynı)
  $cipler = ['hepsi' => 'Hepsi'];
  foreach ($katMeta as $k => $m) $cipler[$k] = $m[2];

  // Mevcut filtreleri koruyan link
  $linkYap = function (array $degis) use ($gun, $kategori, $kim, $ara) {
    $q = array_merge(['gun' => $gun, 'kategori' => $kategori, 'kim' => $kim ?: null, 'ara' => $ara ?: null], $degis);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '' && $v !== 'hepsi');
    return '/hareketler' . (count($q) ? ('?' . http_build_query($q)) : '');
  };

  $tl = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $girisT = (float) ($ozet['giris'] ?? 0);
  $cikisT = (float) ($ozet['cikis'] ?? 0);
  $toplamH = (int) ($ozet['toplam'] ?? count($hareketler ?? []));
@endphp

{{-- ====== FİLTRE BAR (Flutter _filtreBar): gün sekmeleri + arama + personel ====== --}}
<form method="get" action="/hareketler" id="hrkFiltre"
      style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px">
  <input type="hidden" name="kategori" value="{{ $kategori }}">

  {{-- Gün sekmeleri --}}
  <div style="display:inline-flex;gap:6px">
    @foreach ($gunlar as $g => $ad)
      <a href="{{ $linkYap(['gun' => $g]) }}"
         class="rk-btn {{ $gun == $g ? '' : 'bos' }}"
         style="{{ $gun == $g ? 'background:var(--mor1)' : '' }}">{{ $ad }}</a>
    @endforeach
  </div>

  {{-- Arama --}}
  <div style="position:relative;flex:1;min-width:220px;max-width:360px">
    <span class="material-icons-outlined"
          style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--sub);font-size:18px">search</span>
    <input type="text" name="ara" value="{{ $ara }}" placeholder="Ara (başlık / açıklama / tutar)"
           class="rk-inp" style="padding-left:36px">
  </div>

  {{-- Personel seçici --}}
  @if (count($personeller))
    <select name="kim" class="rk-inp" style="width:auto;min-width:150px" onchange="document.getElementById('hrkFiltre').submit()">
      <option value="">👤 Herkes</option>
      @foreach ($personeller as $pp)
        <option value="{{ $pp->id }}" {{ (int) $kim === (int) $pp->id ? 'selected' : '' }}>{{ $pp->ad }}</option>
      @endforeach
    </select>
  @endif

  <button type="submit" class="rk-btn" style="background:var(--mor1)">
    <span class="material-icons-outlined" style="font-size:18px">filter_alt</span> Filtrele
  </button>
  @if ($ara !== '' || $kim || $kategori !== 'hepsi')
    <a href="/hareketler?gun={{ $gun }}" class="rk-btn bos">
      <span class="material-icons-outlined" style="font-size:18px">close</span> Temizle
    </a>
  @endif
</form>

{{-- ====== KATEGORİ ÇİPLERİ (Flutter renkli çipler) ====== --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
  @foreach ($cipler as $k => $ad)
    @php
      $aktif = $kategori === $k;
      if ($k === 'hepsi') {
        $renk = 'var(--mor1)'; $ikon = null;
      } else {
        [$renk, $ikon] = $meta($k);
      }
    @endphp
    <a href="{{ $linkYap(['kategori' => $k]) }}"
       style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:20px;
              font-size:12.5px;font-weight:700;border:1px solid;transition:all .15s;
              {{ $aktif
                  ? 'background:' . $renk . ';color:#fff;border-color:transparent'
                  : 'background:var(--card);color:var(--sub2);border-color:var(--line)' }}">
      @if ($ikon)
        <span class="material-icons-outlined"
              style="font-size:15px;color:{{ $aktif ? '#fff' : $renk }}">{{ $ikon }}</span>
      @endif
      {{ $ad }}
    </a>
  @endforeach
</div>

{{-- ====== ÖZET ŞERİT (Flutter _ozetSerit): toplam + giriş/çıkış renkli ====== --}}
<div class="rk-card" style="display:flex;align-items:center;gap:14px;padding:12px 16px;margin-bottom:14px">
  <b style="font-size:14px;color:var(--ink)">{{ number_format($toplamH, 0, ',', '.') }} hareket</b>
  <div style="flex:1"></div>
  @if ($girisT > 0)
    <span style="display:inline-flex;align-items:center;gap:3px;font-size:13px;font-weight:700;color:var(--yesil)">
      <span class="material-icons-outlined" style="font-size:16px">south_west</span>{{ $tl($girisT) }}
    </span>
  @endif
  @if ($cikisT > 0)
    <span style="display:inline-flex;align-items:center;gap:3px;font-size:13px;font-weight:700;color:var(--kirmizi)">
      <span class="material-icons-outlined" style="font-size:16px">north_east</span>{{ $tl($cikisT) }}
    </span>
  @endif
</div>

{{-- ====== LİSTE (Flutter masaüstü tablo: renk şeridi + sütunlar) ====== --}}
@if (empty($hareketler))
  <div class="rk-card" style="padding:40px;text-align:center;color:var(--sub)">Bu filtrede hareket yok.</div>
@else
  <div class="rk-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:15%">ZAMAN</th>
          <th style="width:17%">KULLANICI</th>
          <th style="width:26%">İŞLEM</th>
          <th style="width:12%">HEDEF</th>
          <th style="width:18%">KAYNAK / IP</th>
          <th class="sag" style="width:12%">TUTAR</th>
        </tr>
      </thead>
      <tbody>
        @php $oncekiGun = null; @endphp
        @foreach ($hareketler as $h)
          @php
            $kat = $kategoriBul($h);
            [$renk, $ikon, $etiket] = $meta($kat);
            $tutar = $h['tutar'] ?? null;
            $yon = $h['yon'] ?? null;
            $personel = $h['personel'] ?? null;
            $rol = $h['rol'] ?? null;
            $kaynak = $h['kaynak'] ?? null;
            $cihaz = $h['cihaz'] ?? null;
            $ip = $h['ip'] ?? null;
            $hedef = $h['hedef'] ?? null;
            $aciklama = $h['aciklama'] ?? null;
            $bas = $personel ? mb_strtoupper(mb_substr(trim($personel), 0, 1)) : 'S';
            $avatarRenk = $rolRenk[$rol] ?? '#64748B';
            $tutarRenk = $yon === 'cikis' ? 'var(--kirmizi)' : ($yon === 'giris' ? 'var(--yesil)' : 'var(--ink)');
            $tutarIsaret = $yon === 'cikis' ? '−' : ($yon === 'giris' ? '+' : '');
            // Flutter gün başlığı: gun_key yoksa tarihten türet
            $gunKey = $h['gun_key'] ?? ($h['tarih'] ?? null);
            $gunBasi = $gunKey !== $oncekiGun;
            $oncekiGun = $gunKey;
            $kaynakIk = $kaynak === 'Kasa' ? 'point_of_sale' : ($kaynak === 'Mobil' ? 'smartphone' : 'devices_other');
          @endphp

          @if ($gunBasi)
            <tr>
              <td colspan="6" style="padding:11px 14px 5px;background:var(--card);border-top:1px solid color-mix(in srgb,var(--line) 60%,transparent)">
                <span style="font-size:12px;font-weight:800;color:var(--sub);letter-spacing:.3px">{{ $h['tarih'] ?? '' }}</span>
              </td>
            </tr>
          @endif

          <tr>
            {{-- ZAMAN (sol renk şeridi + tarih/saat) --}}
            <td style="padding:0">
              <div style="display:flex;align-items:stretch">
                <span style="width:5px;align-self:stretch;background:{{ $renk }};border-radius:3px;margin:8px 10px 8px 0"></span>
                <div style="padding:8px 0">
                  <div style="font-size:13px;font-weight:700;color:var(--ink)">{{ $h['tarih'] ?? '' }}</div>
                  <div style="font-size:11.5px;color:var(--sub)">{{ $h['saat'] ?? '' }}</div>
                </div>
              </div>
            </td>

            {{-- KULLANICI (avatar harf + ad + rol renkli) --}}
            <td>
              <div style="display:flex;align-items:center;gap:8px;min-width:0">
                <span style="width:30px;height:30px;flex:0 0 30px;border-radius:50%;background:{{ $avatarRenk }};
                             color:#fff;font-size:12.5px;font-weight:800;display:flex;align-items:center;justify-content:center">{{ $bas }}</span>
                <div style="min-width:0">
                  <div style="font-size:12.5px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $personel ?? 'Sistem' }}</div>
                  @if ($rol)<div style="font-size:10.5px;font-weight:600;color:{{ $avatarRenk }}">{{ $rol }}</div>@endif
                </div>
              </div>
            </td>

            {{-- İŞLEM (ikon kutusu + başlık + kategori rozeti + açıklama) --}}
            <td>
              <div style="display:flex;align-items:flex-start;gap:9px;min-width:0">
                <span style="width:34px;height:34px;flex:0 0 34px;border-radius:10px;
                             background:color-mix(in srgb,{{ $renk }} 14%,transparent);
                             display:flex;align-items:center;justify-content:center">
                  <span class="material-icons-outlined" style="font-size:18px;color:{{ $renk }}">{{ $ikon }}</span>
                </span>
                <div style="min-width:0">
                  <div style="font-size:12.5px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $h['baslik'] ?? '—' }}</div>
                  <span style="display:inline-block;margin-top:2px;padding:1px 7px;border-radius:20px;font-size:10px;font-weight:800;
                               background:color-mix(in srgb,{{ $renk }} 13%,transparent);color:{{ $renk }}">{{ $etiket }}</span>
                  @if ($aciklama)
                    <div style="margin-top:3px;font-size:11px;color:var(--sub);line-height:1.3;
                                display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $aciklama }}</div>
                  @endif
                </div>
              </div>
            </td>

            {{-- HEDEF --}}
            <td style="font-size:11.5px;color:var(--sub2);font-weight:500">
              {{ $hedef ?: '—' }}
            </td>

            {{-- KAYNAK / IP --}}
            <td style="font-size:11.5px">
              @if ($kaynak)
                <div style="display:flex;align-items:center;gap:3px;color:var(--mavi);font-weight:600">
                  <span class="material-icons-outlined" style="font-size:13px">{{ $kaynakIk }}</span>
                  <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $kaynak }}@if ($cihaz) · {{ $cihaz }}@endif</span>
                </div>
              @endif
              @if ($ip)<div style="color:var(--sub);font-size:10.5px">{{ $ip }}</div>@endif
              @if (!$kaynak && !$ip)<span style="color:var(--sub)">—</span>@endif
            </td>

            {{-- TUTAR (yön renkli) --}}
            <td class="sag">
              @if ($tutar !== null && (float) $tutar != 0)
                <span style="font-size:13px;font-weight:800;color:{{ $tutarRenk }}">{{ $tutarIsaret }}{{ $tl($tutar) }}</span>
              @else
                <span style="color:var(--sub)">—</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- ====== SAYFALAMA ====== --}}
  @php $toplamSayfa = (int) ceil(($ozet['toplam'] ?? 0) / max(1, $boyut)); @endphp
  @if ($toplamSayfa > 1)
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:18px">
      @if ($sayfa > 1)
        <a href="{{ $linkYap(['sayfa' => $sayfa - 1]) }}" class="rk-btn bos">
          <span class="material-icons-outlined" style="font-size:18px">chevron_left</span> Önceki
        </a>
      @endif
      <span class="rk-btn" style="background:var(--mor1)">{{ $sayfa }} / {{ $toplamSayfa }}</span>
      @if ($sayfa < $toplamSayfa)
        <a href="{{ $linkYap(['sayfa' => $sayfa + 1]) }}" class="rk-btn bos">
          Sonraki <span class="material-icons-outlined" style="font-size:18px">chevron_right</span>
        </a>
      @endif
    </div>
  @endif
@endif
@endsection

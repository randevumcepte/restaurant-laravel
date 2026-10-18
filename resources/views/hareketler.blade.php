@extends('layout.app')
@section('title', 'Hareketler')
@section('baslik', '📜 Hareketler')

@section('content')
@php
  // Kategori -> [renk sinifi (metin), yumusak arkaplan, ikon, etiket]
  $katMeta = [
    'satis'    => ['text-emerald-600', 'bg-emerald-50',  '🧾', 'Satış'],
    'odeme'    => ['text-emerald-600', 'bg-emerald-50',  '💵', 'Ödeme'],
    'kayip'    => ['text-rose-600',    'bg-rose-50',      '➖', 'Kayıp'],
    'stok'     => ['text-blue-600',    'bg-blue-50',      '📦', 'Stok'],
    'kasa'     => ['text-teal-600',    'bg-teal-50',      '🧰', 'Kasa'],
    'cari'     => ['text-indigo-600',  'bg-indigo-50',    '👛', 'Cari'],
    'personel' => ['text-amber-600',   'bg-amber-50',     '🧑‍💼', 'Personel'],
    'gider'    => ['text-orange-600',  'bg-orange-50',    '💸', 'Gider'],
    'fis'      => ['text-violet-600',  'bg-violet-50',    '📄', 'Fiş'],
    'menu'     => ['text-violet-600',  'bg-violet-50',    '🍽️', 'Menü'],
    'recete'   => ['text-violet-600',  'bg-violet-50',    '📖', 'Reçete'],
    'ayar'     => ['text-slate-600',   'bg-slate-100',    '⚙️', 'Ayar'],
    'giris'    => ['text-slate-600',   'bg-slate-100',    '🔑', 'Giriş'],
    'mesai'    => ['text-teal-600',    'bg-teal-50',      '⏰', 'Mesai'],
    'cagri'    => ['text-rose-600',    'bg-rose-50',      '🔔', 'Çağrı'],
    'diger'    => ['text-slate-600',   'bg-slate-100',    '⚡', 'Diğer'],
  ];
  $meta = fn($k) => $katMeta[$k] ?? $katMeta['diger'];

  // Backend 'diger' dönerse başlık/açıklamadan anlamlı kategori türet (Flutter mantığı)
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

  $gunlar = [1 => 'Bugün', 7 => 'Hafta', 30 => 'Ay'];
  // Çipler: Hepsi + kategori sırası (Flutter ile aynı)
  $cipler = ['hepsi' => 'Hepsi'];
  foreach ($katMeta as $k => $m) $cipler[$k] = $m[3];

  // Mevcut filtre linklerini korumak için yardımcı
  $linkYap = function (array $degis) use ($gun, $kategori, $kim, $ara) {
    $q = array_merge([
      'gun' => $gun, 'kategori' => $kategori, 'kim' => $kim ?: null, 'ara' => $ara ?: null,
    ], $degis);
    $q = array_filter($q, fn($v) => $v !== null && $v !== '' && !($v === 'hepsi'));
    return '/hareketler' . (count($q) ? ('?' . http_build_query($q)) : '');
  };

  $rolRenk = [
    'Sahip' => 'bg-violet-500', 'Müdür' => 'bg-blue-500',
    'Garson' => 'bg-teal-500', 'Kasa' => 'bg-orange-500',
  ];
@endphp

{{-- ÜST: gün seçici + özet kartları --}}
<div class="flex items-center justify-between mb-4 flex-wrap gap-3">
  <div class="inline-flex bg-white rounded-xl p-1 shadow-sm">
    @foreach ($gunlar as $g => $ad)
      <a href="{{ $linkYap(['gun' => $g]) }}"
         class="px-4 py-2 rounded-lg text-sm font-semibold {{ $gun == $g ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:bg-slate-100' }}">{{ $ad }}</a>
    @endforeach
  </div>
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm">
      <span class="text-slate-400">Toplam hareket</span>
      <b class="text-slate-800 ml-1">{{ number_format($ozet['toplam'] ?? 0, 0, ',', '.') }}</b>
    </div>
    @if (($ozet['giris'] ?? 0) > 0)
      <div class="bg-white rounded-xl px-4 py-2 shadow-sm">
        <span class="text-emerald-500">▼ Giriş</span>
        <b class="text-emerald-600 ml-1">{{ number_format($ozet['giris'], 0, ',', '.') }}₺</b>
      </div>
    @endif
    @if (($ozet['cikis'] ?? 0) > 0)
      <div class="bg-white rounded-xl px-4 py-2 shadow-sm">
        <span class="text-rose-500">▲ Çıkış</span>
        <b class="text-rose-600 ml-1">{{ number_format($ozet['cikis'], 0, ',', '.') }}₺</b>
      </div>
    @endif
  </div>
</div>

{{-- FİLTRE: arama + personel --}}
<form method="get" action="/hareketler" class="flex items-center gap-2 mb-3 flex-wrap">
  <input type="hidden" name="gun" value="{{ $gun }}">
  <input type="hidden" name="kategori" value="{{ $kategori }}">
  <div class="relative">
    <input type="text" name="ara" value="{{ $ara }}" placeholder="Ara (başlık / açıklama / tutar)"
           class="w-72 max-w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
  </div>
  <select name="kim" onchange="this.form.submit()"
          class="py-2 px-3 rounded-xl border border-slate-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
    <option value="">👤 Herkes</option>
    @foreach ($personeller as $pp)
      <option value="{{ $pp->id }}" {{ (int) $kim === (int) $pp->id ? 'selected' : '' }}>{{ $pp->ad }}</option>
    @endforeach
  </select>
  <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">Filtrele</button>
  @if ($ara !== '' || $kim || $kategori !== 'hepsi')
    <a href="/hareketler?gun={{ $gun }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 text-sm font-semibold hover:bg-slate-200">Temizle</a>
  @endif
</form>

{{-- KATEGORİ ÇİPLERİ --}}
<div class="flex gap-2 mb-5 flex-wrap">
  @foreach ($cipler as $k => $ad)
    @php [$tx, $bg, $ikon] = $k === 'hepsi' ? ['', '', ''] : $meta($k); @endphp
    <a href="{{ $linkYap(['kategori' => $k]) }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-sm font-semibold border transition
              {{ $kategori === $k
                  ? ($k === 'hepsi' ? 'bg-indigo-600 text-white border-indigo-600' : "$bg $tx border-transparent ring-2 ring-offset-1 ring-current")
                  : 'bg-white text-slate-500 border-slate-200 hover:bg-slate-50' }}">
      @if ($k !== 'hepsi')<span>{{ $ikon }}</span>@endif{{ $ad }}
    </a>
  @endforeach
</div>

{{-- LİSTE --}}
@if (empty($hareketler))
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Bu filtrede hareket yok.</div>
@else
  <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    {{-- Masaüstü tablo başlığı --}}
    <div class="hidden lg:grid grid-cols-12 gap-3 px-5 py-3 bg-slate-50 border-b border-slate-100 text-[11px] font-bold text-slate-400 tracking-wide">
      <div class="col-span-2">ZAMAN</div>
      <div class="col-span-2">KULLANICI</div>
      <div class="col-span-3">İŞLEM</div>
      <div class="col-span-2">KAYNAK / IP</div>
      <div class="col-span-2">AÇIKLAMA</div>
      <div class="col-span-1 text-right">TUTAR</div>
    </div>

    @php $oncekiGun = null; @endphp
    @foreach ($hareketler as $h)
      @php
        $kat = $kategoriBul($h);
        [$tx, $bg, $ikon, $etiket] = $meta($kat);
        $tutar = $h['tutar'] ?? null;
        $yon = $h['yon'] ?? null;
        $personel = $h['personel'] ?? null;
        $rol = $h['rol'] ?? null;
        $kaynak = $h['kaynak'] ?? null;
        $cihaz = $h['cihaz'] ?? null;
        $ip = $h['ip'] ?? null;
        $hedef = $h['hedef'] ?? null;
        $bas = $personel ? mb_strtoupper(mb_substr(trim($personel), 0, 1)) : 'S';
        $avatarRenk = $rolRenk[$rol] ?? 'bg-slate-400';
        $tutarRenk = $yon === 'cikis' ? 'text-rose-600' : ($yon === 'giris' ? 'text-emerald-600' : 'text-slate-700');
        $tutarIsaret = $yon === 'cikis' ? '−' : ($yon === 'giris' ? '+' : '');
        $gunKey = $h['gun_key'] ?? null;
        $gunBasi = $gunKey !== $oncekiGun;
        $oncekiGun = $gunKey;
      @endphp

      @if ($gunBasi)
        <div class="px-5 pt-4 pb-1 text-xs font-bold text-slate-400 bg-white">{{ $h['tarih'] ?? '' }}</div>
      @endif

      <div class="grid grid-cols-12 gap-3 px-5 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50/60 transition items-center">
        {{-- Sol renk şeridi + ZAMAN --}}
        <div class="col-span-12 lg:col-span-2 flex items-center gap-2">
          <span class="w-1 h-10 rounded-full {{ str_replace('text-', 'bg-', $tx) }}"></span>
          <div>
            <div class="text-sm font-bold text-slate-800">{{ $h['tarih'] ?? '' }}</div>
            <div class="text-xs text-slate-400">{{ $h['saat'] ?? '' }}</div>
          </div>
        </div>

        {{-- KULLANICI --}}
        <div class="col-span-6 lg:col-span-2 flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 shrink-0 rounded-full flex items-center justify-center text-white text-xs font-bold {{ $avatarRenk }}">{{ $bas }}</div>
          <div class="min-w-0">
            <div class="text-sm font-semibold text-slate-700 truncate">{{ $personel ?? 'Sistem' }}</div>
            @if ($rol)<div class="text-[11px] text-slate-400">{{ $rol }}</div>@endif
          </div>
        </div>

        {{-- İŞLEM --}}
        <div class="col-span-6 lg:col-span-3 flex items-center gap-2.5 min-w-0">
          <div class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-lg {{ $bg }}">{{ $ikon }}</div>
          <div class="min-w-0">
            <div class="text-sm font-bold text-slate-800 truncate">{{ $h['baslik'] ?? '—' }}</div>
            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $bg }} {{ $tx }}">{{ $etiket }}</span>
          </div>
        </div>

        {{-- KAYNAK / IP (+ mobilde açıklama) --}}
        <div class="col-span-6 lg:col-span-2 text-xs min-w-0">
          @if ($kaynak)
            <div class="font-semibold text-blue-600 truncate">
              {{ $kaynak === 'Kasa' ? '🖥️' : ($kaynak === 'Mobil' ? '📱' : '💻') }} {{ $kaynak }}@if ($cihaz) · {{ $cihaz }}@endif
            </div>
          @endif
          @if ($ip)<div class="text-slate-400">{{ $ip }}</div>@endif
          @if ($hedef)<div class="text-slate-400 truncate">🎯 {{ $hedef }}</div>@endif
          @if (!$kaynak && !$ip && !$hedef)<span class="text-slate-300">—</span>@endif
        </div>

        {{-- AÇIKLAMA --}}
        <div class="col-span-6 lg:col-span-2 text-xs text-slate-500 min-w-0">
          <span class="line-clamp-2">{{ $h['aciklama'] ?? '—' }}</span>
        </div>

        {{-- TUTAR --}}
        <div class="col-span-6 lg:col-span-1 text-right">
          @if ($tutar !== null && (float) $tutar != 0)
            <span class="text-sm font-bold {{ $tutarRenk }}">{{ $tutarIsaret }}{{ number_format((float) $tutar, 0, ',', '.') }}₺</span>
          @else
            <span class="text-slate-300 text-sm">—</span>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  {{-- SAYFALAMA --}}
  @php
    $toplamSayfa = (int) ceil(($ozet['toplam'] ?? 0) / max(1, $boyut));
  @endphp
  @if ($toplamSayfa > 1)
    <div class="flex items-center justify-center gap-2 mt-5 text-sm">
      @if ($sayfa > 1)
        <a href="{{ $linkYap(['sayfa' => $sayfa - 1]) }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">← Önceki</a>
      @endif
      <span class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white font-semibold">{{ $sayfa }} / {{ $toplamSayfa }}</span>
      @if ($sayfa < $toplamSayfa)
        <a href="{{ $linkYap(['sayfa' => $sayfa + 1]) }}" class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50">Sonraki →</a>
      @endif
    </div>
  @endif
@endif
@endsection

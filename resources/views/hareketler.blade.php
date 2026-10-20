@extends('layout.app')
@section('rk_ikon', 'history')
@section('baslik', 'Hareketler')
@section('rk_alt', 'Tüm işletme hareketleri tek akışta')

@push('head')
<style>
/* ===== HAREKETLER – Flutter hareketler_screen birebir ===== */
#hrk{--mr1:#7C3AED;--mr2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
     --card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #hrk{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}

/* ---- Filtre Bar ---- */
#hrk .hrk-filtre{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:10px 0 4px}
#hrk .hrk-gun-grp{display:inline-flex;gap:6px}
#hrk .hrk-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:9px;
  font-size:12px;font-weight:700;cursor:pointer;border:1px solid var(--line);
  background:var(--card);color:var(--sub2);text-decoration:none;transition:all .13s}
#hrk .hrk-btn:hover{border-color:var(--mr1);color:var(--mr1)}
#hrk .hrk-btn.akt{background:var(--mr1);border-color:var(--mr1);color:#fff}
#hrk .hrk-ara-wrap{position:relative;flex:1;min-width:190px;max-width:340px}
#hrk .hrk-ara-wrap .material-icons-outlined{position:absolute;left:9px;top:50%;transform:translateY(-50%);
  font-size:17px;color:var(--sub);pointer-events:none}
#hrk .hrk-ara{width:100%;height:36px;padding:0 32px 0 33px;border-radius:9px;border:1px solid var(--line);
  background:var(--card);color:var(--ink);font-size:13px;box-sizing:border-box;outline:none;transition:border .12s}
#hrk .hrk-ara:focus{border-color:var(--mr1)}
#hrk .hrk-ara-sil{position:absolute;right:7px;top:50%;transform:translateY(-50%);
  background:none;border:none;cursor:pointer;color:var(--sub);font-size:16px;line-height:1;display:none}
#hrk .hrk-sel{height:36px;padding:0 10px;border-radius:9px;border:1px solid var(--line);
  background:var(--card);color:var(--ink);font-size:12px;cursor:pointer}

/* ---- Kategori Çipleri ---- */
#hrk .hrk-cipler{display:flex;gap:7px;overflow-x:auto;padding:6px 0 8px;scrollbar-width:none}
#hrk .hrk-cipler::-webkit-scrollbar{display:none}
#hrk .hrk-cip{display:inline-flex;align-items:center;gap:4px;padding:7px 13px;border-radius:20px;
  font-size:12px;font-weight:700;cursor:pointer;border:1px solid var(--line);
  background:var(--card);color:var(--sub2);white-space:nowrap;text-decoration:none;transition:all .13s;flex-shrink:0}
#hrk .hrk-cip .material-icons-outlined{font-size:13px}
#hrk .hrk-cip.akt{color:#fff;border-color:transparent}
#hrk .hrk-cip:not(.akt):hover{border-color:var(--mr1);color:var(--mr1)}

/* ---- Özet Şerit ---- */
#hrk .hrk-ozet{display:flex;align-items:center;gap:14px;padding:11px 16px;
  background:var(--card);border:1px solid var(--line);border-radius:12px;margin-bottom:14px}
#hrk .hrk-ozet b{font-size:13.5px;color:var(--ink)}
#hrk .hrk-ozet .sp{flex:1}
#hrk .ozet-giris{display:inline-flex;align-items:center;gap:3px;font-size:12.5px;font-weight:700;color:var(--yesil)}
#hrk .ozet-cikis{display:inline-flex;align-items:center;gap:3px;font-size:12.5px;font-weight:700;color:var(--kirmizi)}
#hrk .ozet-giris .material-icons-outlined,
#hrk .ozet-cikis .material-icons-outlined{font-size:15px}

/* ---- Yükleniyor / Boş ---- */
#hrk .hrk-spin{display:flex;justify-content:center;padding:48px 0}
#hrk .hrk-spin .spinner{width:28px;height:28px;border-radius:50%;
  border:3px solid color-mix(in srgb,var(--mr1) 25%,transparent);border-top-color:var(--mr1);
  animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
#hrk .hrk-bos{padding:48px 16px;text-align:center;color:var(--sub);font-size:14px}

/* ---- MASAÜSTÜ TABLO (≥ 900px) ---- */
#hrk .hrk-tablo-wrap{overflow-x:auto}
#hrk .hrk-tablo{width:100%;border-collapse:collapse;table-layout:fixed}
#hrk .hrk-tablo thead th{padding:10px 13px;font-size:11px;font-weight:800;color:var(--sub);
  letter-spacing:.4px;background:var(--card2);border:1px solid var(--line);text-align:left;white-space:nowrap}
#hrk .hrk-tablo thead th.sag{text-align:right}
#hrk .hrk-tablo tbody tr{transition:background .1s}
#hrk .hrk-tablo tbody tr:hover{background:color-mix(in srgb,var(--mr1) 4%,transparent)}
#hrk .hrk-tablo tbody td{padding:0;vertical-align:middle;border-bottom:1px solid var(--line)}
#hrk .hrk-tablo .gun-basi td{background:color-mix(in srgb,var(--line) 40%,transparent);padding:9px 14px 5px;
  font-size:12px;font-weight:800;color:var(--sub);letter-spacing:.3px;border-top:1px solid var(--line)}
/* Sol renk şeridi (tablo) */
#hrk .serit-wrap{display:flex;align-items:stretch;height:100%}
#hrk .serit-bar{width:5px;align-self:stretch;border-radius:3px;margin:7px 9px 7px 0;flex-shrink:0}
#hrk .zaman-col{padding:8px 0 8px 2px}
#hrk .zaman-col .z1{font-size:12.5px;font-weight:700;color:var(--ink)}
#hrk .zaman-col .z2{font-size:11.5px;color:var(--sub)}
/* Kullanıcı avatar */
#hrk .hrk-avatar{width:30px;height:30px;flex:0 0 30px;border-radius:50%;
  color:#fff;font-size:12.5px;font-weight:800;display:inline-flex;align-items:center;justify-content:center}
/* İkon kutu */
#hrk .ikon-kutu{width:34px;height:34px;flex:0 0 34px;border-radius:10px;
  display:inline-flex;align-items:center;justify-content:center}
#hrk .ikon-kutu .material-icons-outlined{font-size:18px}
/* Rozet */
#hrk .kat-rozet{display:inline-block;padding:1px 7px;border-radius:20px;font-size:10px;font-weight:800}
/* Tutar */
#hrk .t-giris{font-size:13px;font-weight:800;color:var(--yesil)}
#hrk .t-cikis{font-size:13px;font-weight:800;color:var(--kirmizi)}
#hrk .t-duz{font-size:13px;font-weight:800;color:var(--ink)}

/* ---- MOBİL KART (< 900px) ---- */
#hrk .hrk-karti{background:var(--card);border:1px solid var(--line);border-radius:14px;
  margin-bottom:9px;overflow:hidden;cursor:pointer;transition:border-color .12s;
  box-shadow:0 2px 8px rgba(0,0,0,.05)}
#hrk .hrk-karti.acik{border-color:color-mix(in srgb,var(--ak-renk,#7C3AED) 40%,transparent)}
#hrk .hrk-karti-ust{display:flex;align-items:stretch}
#hrk .hrk-k-serit{width:5px;flex-shrink:0}
#hrk .hrk-k-ic{flex:1;display:flex;align-items:flex-start;gap:11px;padding:11px 11px 11px 9px}
#hrk .hrk-k-ikon{width:44px;height:44px;flex:0 0 44px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;margin-top:1px}
#hrk .hrk-k-ikon .material-icons-outlined{font-size:22px}
#hrk .hrk-k-icerik{flex:1;min-width:0}
#hrk .hrk-k-icerik .bas{font-size:14px;font-weight:700;color:var(--ink);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#hrk .hrk-k-icerik .acik-txt{font-size:11.5px;color:var(--sub);line-height:1.3;margin-top:3px;
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
#hrk .hrk-k-icerik .meta{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-top:5px}
#hrk .hrk-k-icerik .meta .material-icons-outlined{font-size:11px;color:var(--sub2)}
#hrk .hrk-k-icerik .meta span{font-size:11px;color:var(--sub2)}
#hrk .hrk-k-sag{display:flex;flex-direction:column;align-items:flex-end;gap:3px;padding:11px 11px 11px 4px;flex-shrink:0}
#hrk .hrk-k-sag .tutar{font-size:14.5px;font-weight:800}
#hrk .hrk-k-sag .material-icons-outlined{font-size:17px;color:var(--sub)}
/* Detay paneli */
#hrk .hrk-detay{padding:10px 14px 13px;border-top:1px solid var(--line);background:var(--card2)}
#hrk .hrk-detay .d-sat{display:flex;gap:8px;padding:4px 0;align-items:flex-start}
#hrk .hrk-detay .d-sat .material-icons-outlined{font-size:14px;color:var(--sub2);flex-shrink:0;margin-top:1px}
#hrk .hrk-detay .d-et{width:90px;flex-shrink:0;font-size:12px;color:var(--sub)}
#hrk .hrk-detay .d-val{flex:1;font-size:12.5px;font-weight:600;color:var(--ink);word-break:break-word}
/* Gün başlığı (mobil) */
#hrk .hrk-gun-hdr{padding:13px 4px 5px;font-size:12px;font-weight:800;color:var(--sub)}

/* ---- Sonsuz Scroll göstergesi ---- */
#hrk .hrk-daha{display:flex;justify-content:center;padding:16px 0;display:none}
#hrk .hrk-daha .spinner{width:22px;height:22px;border-radius:50%;
  border:2px solid color-mix(in srgb,var(--mr1) 25%,transparent);border-top-color:var(--mr1);
  animation:spin .7s linear infinite}

/* Masaüstü/Mobil görünüm geçişi */
@media(min-width:900px){
  #hrk .hrk-mobil{display:none!important}
}
@media(max-width:899px){
  #hrk .hrk-masaustu{display:none!important}
  #hrk .hrk-tablo-wrap{display:none!important}
}

/* Tablo sütun genişlikleri */
#hrk .hrk-tablo col.c-zaman{width:14%}
#hrk .hrk-tablo col.c-kullanici{width:17%}
#hrk .hrk-tablo col.c-islem{width:26%}
#hrk .hrk-tablo col.c-hedef{width:11%}
#hrk .hrk-tablo col.c-kaynak{width:18%}
#hrk .hrk-tablo col.c-aciklama{width:auto}
#hrk .hrk-tablo col.c-tutar{width:11%}

/* truncate yardımcısı */
.hrk-trunc{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block}
</style>
@endpush

@section('content')
@php
/* ===== META: Flutter _kat birebir ===== */
$katMeta = [
  'satis'    => ['#10B981', 'receipt_long',              'Satış'],
  'odeme'    => ['#10B981', 'payments',                  'Ödeme'],
  'kayip'    => ['#F43F5E', 'remove_circle_outline',     'Kayıp'],
  'stok'     => ['#3B82F6', 'inventory_2',               'Stok'],
  'kasa'     => ['#14B8A6', 'point_of_sale',             'Kasa'],
  'cari'     => ['#6366F1', 'account_balance_wallet',    'Cari'],
  'personel' => ['#F59E0B', 'badge',                     'Personel'],
  'gider'    => ['#EA580C', 'money_off',                 'Gider'],
  'fis'      => ['#7C3AED', 'description',               'Fiş'],
  'menu'     => ['#7C3AED', 'restaurant_menu',           'Menü'],
  'recete'   => ['#7C3AED', 'menu_book',                 'Reçete'],
  'ayar'     => ['#94A3B8', 'settings',                  'Ayar'],
  'giris'    => ['#94A3B8', 'login',                     'Giriş'],
  'mesai'    => ['#14B8A6', 'schedule',                  'Mesai'],
  'cagri'    => ['#F43F5E', 'notifications_active',      'Çağrı'],
  'diger'    => ['#94A3B8', 'bolt',                      'Diğer'],
];
$getMeta = fn($k) => $katMeta[$k] ?? $katMeta['diger'];

/* Flutter _kategori() */
$kategoriBul = function($h) use ($katMeta) {
  $k = (string)($h['kategori'] ?? '');
  if ($k !== '' && $k !== 'diger' && isset($katMeta[$k])) return $k;
  $s = mb_strtolower(($h['baslik'] ?? '') . ' ' . ($h['aciklama'] ?? ''));
  $c = fn($ks) => count(array_filter($ks, fn($x) => mb_strpos($s,$x)!==false)) > 0;
  if ($c(['iptal','geri alın','geri alind','void','iskonto','indirim','ikram','fire'])) return 'kayip';
  if ($c(['ödeme','odeme','tahsil','nakit','kredi','kart','yemek kart']))             return 'odeme';
  if ($c(['masa','adisyon','sipariş','siparis','satış','satis','birleştir','taşın','tasin','böl','bol'])) return 'satis';
  if ($c(['stok','malzeme','sayım','sayim','alış','alis','tedarik']))                 return 'stok';
  if ($c(['kasa','çekmece','cekmece','vardiya']))                                      return 'kasa';
  if ($c(['cari','açık hesap','acik hesap','borç','borc']))                           return 'cari';
  if ($c(['personel','maaş','maas','yetki','avans']))                                 return 'personel';
  if ($c(['mesai','puantaj']))                                                         return 'mesai';
  if ($c(['gider','masraf']))                                                          return 'gider';
  if ($c(['menü','menu','ürün','urun','kategori']))                                   return 'menu';
  if ($c(['reçete','recete','yarı mamül','yari mamul']))                              return 'recete';
  if ($c(['fiş','fis','fatura']))                                                      return 'fis';
  if ($c(['giriş','giris','login','çıkış','cikis','logout']))                         return 'giris';
  if ($c(['ayar','tema','şema','sema']))                                               return 'ayar';
  if ($c(['çağrı','cagri','garson']))                                                  return 'cagri';
  return $k !== '' ? $k : 'diger';
};

/* Flutter _rolRenk() */
$rolRenk = ['Sahip'=>'#7C3AED','Müdür'=>'#2563EB','Garson'=>'#0D9488','Kasa'=>'#EA580C'];

$tl    = fn($v) => number_format(round((float)$v), 0, ',', '.') . '₺';
$girisT = (float)($ozet['giris'] ?? 0);
$cikisT = (float)($ozet['cikis'] ?? 0);
$toplamH = (int)($ozet['toplam'] ?? count($hareketler ?? []));

$gunlar  = [1=>'Bugün', 7=>'Hafta', 30=>'Ay'];
$cipler  = ['hepsi' => ['#7C3AED', null, 'Hepsi']];
foreach ($katMeta as $k => $m) $cipler[$k] = $m;

/* URL yardımcısı (sayfa parametresiz) */
$q = fn($extra=[]) => '?' . http_build_query(array_filter(
  array_merge(['gun'=>$gun,'kategori'=>$kategori,'kim'=>$kim??'','ara'=>$ara], $extra),
  fn($v) => $v !== null && $v !== '' && $v !== 'hepsi' && $v !== 0
));
@endphp

<div id="hrk">

{{-- ============================================================
     FILTRE BAR — Flutter _filtreBar()
     ============================================================ --}}
<div class="hrk-filtre">

  {{-- Gün sekmeleri --}}
  <div class="hrk-gun-grp">
    @foreach($gunlar as $g => $ad)
      <a href="/hareketler{{ $q(['gun'=>$g]) }}"
         class="hrk-btn {{ $gun == $g ? 'akt' : '' }}">{{ $ad }}</a>
    @endforeach
  </div>

  {{-- Arama --}}
  <div class="hrk-ara-wrap">
    <span class="material-icons-outlined">search</span>
    <input id="hrkAra" type="text" class="hrk-ara" placeholder="Ara…" value="{{ $ara }}"
           autocomplete="off">
    <button type="button" class="hrk-ara-sil" id="hrkAraSil"
            onclick="hrkAraSil()" title="Temizle">✕</button>
  </div>

  {{-- Personel seçici --}}
  @if(count($personeller))
  <select id="hrkKim" class="hrk-sel" onchange="hrkKimDegis(this.value)">
    <option value="">👤 Herkes</option>
    @foreach($personeller as $pp)
      <option value="{{ $pp->id }}" {{ (int)$kim === (int)$pp->id ? 'selected' : '' }}>
        {{ $pp->ad }}</option>
    @endforeach
  </select>
  @endif

</div>

{{-- ============================================================
     KATEGORİ ÇİPLERİ — Flutter renkli scroll
     ============================================================ --}}
<div class="hrk-cipler" id="hrkCipler">
  @foreach($cipler as $k => [$renk, $ikon, $ad])
    @php $aktif = $kategori === $k; @endphp
    <a href="/hareketler{{ $q(['kategori'=>$k]) }}"
       class="hrk-cip {{ $aktif ? 'akt' : '' }}"
       style="{{ $aktif ? 'background:'.$renk.';box-shadow:0 2px 8px color-mix(in srgb,'.$renk.' 30%,transparent)' : '' }}"
       data-kat="{{ $k }}" data-renk="{{ $renk }}">
      @if($ikon)
        <span class="material-icons-outlined"
              style="color:{{ $aktif ? '#fff' : $renk }}">{{ $ikon }}</span>
      @endif
      {{ $ad }}
    </a>
  @endforeach
</div>

{{-- ============================================================
     ÖZET ŞERİT — Flutter _ozetSerit()
     ============================================================ --}}
<div class="hrk-ozet" id="hrkOzet">
  <b id="hrkToplamTxt">{{ number_format($toplamH,0,',','.') }} hareket</b>
  <div class="sp"></div>
  <span class="ozet-giris" id="hrkGirisKutu" style="{{ $girisT > 0 ? '' : 'display:none' }}">
    <span class="material-icons-outlined">south_west</span>
    <span id="hrkGirisTutar">{{ $tl($girisT) }}</span>
  </span>
  <span class="ozet-cikis" id="hrkCikisKutu" style="{{ $cikisT > 0 ? '' : 'display:none' }}">
    <span class="material-icons-outlined">north_east</span>
    <span id="hrkCikisTutar">{{ $tl($cikisT) }}</span>
  </span>
</div>

{{-- ============================================================
     İÇERİK (liste yükleniyor / liste / boş)
     ============================================================ --}}
<div id="hrkYukleniyor" class="hrk-spin" style="display:none">
  <div class="spinner"></div>
</div>
<div id="hrkBos" class="hrk-bos" style="display:none">Bu filtrede hareket yok.</div>

{{-- MASAÜSTÜ TABLO --}}
<div class="hrk-masaustu hrk-tablo-wrap" id="hrkTabloWrap">
  <table class="hrk-tablo" id="hrkTablo">
    <colgroup>
      <col class="c-zaman"><col class="c-kullanici"><col class="c-islem">
      <col class="c-hedef"><col class="c-kaynak"><col class="c-aciklama"><col class="c-tutar">
    </colgroup>
    <thead>
      <tr>
        <th>ZAMAN</th><th>KULLANICI</th><th>İŞLEM</th>
        <th>HEDEF</th><th>KAYNAK / IP</th><th>AÇIKLAMA</th><th class="sag">TUTAR</th>
      </tr>
    </thead>
    <tbody id="hrkTbody">
      {{-- JS doldurur; SSR fallback aşağıda --}}
      @php $oncekiGun = null; @endphp
      @foreach($hareketler as $h)
        @php
          $kat = $kategoriBul($h);
          [$renk,$ikon,$etiket] = $getMeta($kat);
          $tutar   = $h['tutar']   ?? null;
          $yon     = $h['yon']     ?? null;
          $personel= $h['personel']?? null;
          $rol     = $h['rol']     ?? null;
          $kaynak  = $h['kaynak']  ?? null;
          $cihaz   = $h['cihaz']  ?? null;
          $ip      = $h['ip']     ?? null;
          $hedef   = $h['hedef']  ?? null;
          $aciklama= $h['aciklama']?? null;
          $bas     = $personel ? mb_strtoupper(mb_substr(trim($personel),0,1)) : 'S';
          $avRenk  = $rolRenk[$rol] ?? '#64748B';
          $gunKey  = $h['gun_key'] ?? ($h['tarih'] ?? null);
          $gunBasi = $gunKey !== $oncekiGun;
          $oncekiGun = $gunKey;
          $kaynakIk= $kaynak==='Kasa' ? 'point_of_sale' : ($kaynak==='Mobil' ? 'smartphone' : 'devices_other');
          $tRenk   = $yon==='cikis' ? '#F43F5E' : ($yon==='giris' ? '#10B981' : 'var(--ink)');
          $tIsaret = $yon==='cikis' ? '−' : ($yon==='giris' ? '+' : '');
        @endphp

        @if($gunBasi)
        <tr class="gun-basi"><td colspan="7">{{ $h['tarih'] ?? '' }}</td></tr>
        @endif

        <tr>
          <td style="padding:0;min-width:110px">
            <div class="serit-wrap" style="padding:7px 8px 7px 10px">
              <span class="serit-bar" style="background:{{ $renk }}"></span>
              <div class="zaman-col">
                <div class="z1">{{ $h['tarih'] ?? '' }}</div>
                <div class="z2">{{ $h['saat'] ?? '' }}</div>
              </div>
            </div>
          </td>
          <td style="padding:8px 10px">
            <div style="display:flex;align-items:center;gap:8px;min-width:0">
              <span class="hrk-avatar" style="background:{{ $avRenk }}">{{ $bas }}</span>
              <div style="min-width:0">
                <div class="hrk-trunc" style="font-size:12.5px;font-weight:600;color:var(--ink);max-width:130px">{{ $personel ?? 'Sistem' }}</div>
                @if($rol)<div style="font-size:10.5px;font-weight:600;color:{{ $avRenk }}">{{ $rol }}</div>@endif
              </div>
            </div>
          </td>
          <td style="padding:8px 10px">
            <div style="display:flex;align-items:flex-start;gap:8px;min-width:0">
              <span class="ikon-kutu" style="background:color-mix(in srgb,{{ $renk }} 14%,transparent)">
                <span class="material-icons-outlined" style="color:{{ $renk }}">{{ $ikon }}</span>
              </span>
              <div style="min-width:0">
                <div class="hrk-trunc" style="font-size:12.5px;font-weight:700;color:var(--ink);max-width:180px">{{ $h['baslik'] ?? '—' }}</div>
                <span class="kat-rozet" style="background:color-mix(in srgb,{{ $renk }} 13%,transparent);color:{{ $renk }}">{{ $etiket }}</span>
              </div>
            </div>
          </td>
          <td style="padding:8px 10px;font-size:11.5px;color:var(--sub2);font-weight:500">
            <span class="hrk-trunc" style="max-width:100px">{{ $hedef ?: '—' }}</span>
          </td>
          <td style="padding:8px 10px;font-size:11.5px">
            @if($kaynak)
              <div style="display:flex;align-items:center;gap:3px;color:var(--mavi);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <span class="material-icons-outlined" style="font-size:13px;color:#4F46E5">{{ $kaynakIk }}</span>
                <span style="color:#4F46E5">{{ $kaynak }}{{ $cihaz ? ' · '.$cihaz : '' }}</span>
              </div>
            @endif
            @if($ip)<div style="color:var(--sub);font-size:10.5px">{{ $ip }}</div>@endif
            @if(!$kaynak && !$ip)<span style="color:var(--sub)">—</span>@endif
          </td>
          <td style="padding:8px 10px;font-size:11.5px;color:var(--sub);line-height:1.3">
            <div style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
              {{ $aciklama ?: '—' }}
            </div>
          </td>
          <td style="padding:8px 12px;text-align:right">
            @if($tutar !== null && (float)$tutar != 0)
              <span style="font-size:13px;font-weight:800;color:{{ $tRenk }}">{{ $tIsaret }}{{ $tl($tutar) }}</span>
            @else
              <span style="color:var(--sub)">—</span>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>

{{-- MOBİL KARTLAR --}}
<div class="hrk-mobil" id="hrkMobil">
  @php $oncekiGunM = null; @endphp
  @foreach($hareketler as $idx => $h)
    @php
      $kat = $kategoriBul($h);
      [$renk,$ikon,$etiket] = $getMeta($kat);
      $tutar   = $h['tutar']   ?? null;
      $yon     = $h['yon']     ?? null;
      $personel= $h['personel']?? null;
      $aciklama= $h['aciklama']?? '';
      $kaynak  = $h['kaynak']  ?? null;
      $gunKey  = $h['gun_key'] ?? ($h['tarih'] ?? null);
      $gunBasiM= $gunKey !== $oncekiGunM;
      $oncekiGunM = $gunKey;
      $kaynakIk= $kaynak==='Kasa' ? 'point_of_sale' : ($kaynak==='Mobil' ? 'smartphone' : 'devices_other');
      $tRenk   = $yon==='cikis' ? '#F43F5E' : ($yon==='giris' ? '#10B981' : 'var(--ink)');
      $tIsaret = $yon==='cikis' ? '−' : ($yon==='giris' ? '+' : '');
    @endphp
    @if($gunBasiM)
      <div class="hrk-gun-hdr">{{ $h['tarih'] ?? '' }}</div>
    @endif
    <div class="hrk-karti" data-idx="{{ $idx }}" style="--ak-renk:{{ $renk }}"
         onclick="hrkKartiToggle(this)">
      <div class="hrk-karti-ust">
        <div class="hrk-k-serit" style="background:{{ $renk }}"></div>
        <div class="hrk-k-ic">
          <div class="hrk-k-ikon" style="background:color-mix(in srgb,{{ $renk }} 14%,transparent)">
            <span class="material-icons-outlined" style="color:{{ $renk }}">{{ $ikon }}</span>
          </div>
          <div class="hrk-k-icerik">
            <div style="display:flex;align-items:center;gap:7px">
              <span class="bas hrk-trunc" style="max-width:200px">{{ $h['baslik'] ?? '—' }}</span>
              <span class="kat-rozet" style="background:color-mix(in srgb,{{ $renk }} 13%,transparent);color:{{ $renk }};flex-shrink:0;font-size:10px;font-weight:800;padding:1px 7px;border-radius:20px">{{ $etiket }}</span>
            </div>
            @if($aciklama)
              <div class="acik-txt">{{ $aciklama }}</div>
            @endif
            <div class="meta">
              @if($personel)
                <span class="material-icons-outlined">person</span>
                <span>{{ $personel }}</span>
              @endif
              <span class="material-icons-outlined">access_time</span>
              <span>{{ $h['saat'] ?? '' }}</span>
              @if($kaynak)
                <span class="material-icons-outlined" style="color:#4F46E5">{{ $kaynakIk }}</span>
                <span style="color:#4F46E5;font-weight:600">{{ $kaynak }}</span>
              @endif
            </div>
          </div>
        </div>
        <div class="hrk-k-sag">
          @if($tutar !== null && (float)$tutar != 0)
            <span class="tutar" style="color:{{ $tRenk }}">{{ $tIsaret }}{{ $tl($tutar) }}</span>
          @endif
          <span class="material-icons-outlined hrk-ok-ikon">keyboard_arrow_down</span>
        </div>
      </div>
      {{-- Detay (başlangıçta gizli) --}}
      <div class="hrk-detay" style="display:none">
        @php
          $rol   = $h['rol']   ?? null;
          $cihaz = $h['cihaz'] ?? null;
          $ip    = $h['ip']    ?? null;
          $hedef = $h['hedef'] ?? null;
          $avRenk= $rolRenk[$rol] ?? '#64748B';
          $bas   = $personel ? mb_strtoupper(mb_substr(trim($personel),0,1)) : 'S';
        @endphp
        <div class="d-sat">
          <span class="material-icons-outlined">event</span>
          <span class="d-et">Tarih & Saat</span>
          <span class="d-val">{{ $h['zaman_tam'] ?? (($h['tarih']??'').' '.($h['saat']??'')) }}</span>
        </div>
        @if($personel)
        <div class="d-sat">
          <span class="material-icons-outlined">badge</span>
          <span class="d-et">Kim</span>
          <span class="d-val">{{ $personel }}{{ $rol ? ' · '.$rol : '' }}</span>
        </div>
        @endif
        @if($kaynak)
        <div class="d-sat">
          <span class="material-icons-outlined">devices_other</span>
          <span class="d-et">Kaynak</span>
          <span class="d-val">{{ $kaynak }}</span>
        </div>
        @endif
        @if($cihaz)
        <div class="d-sat">
          <span class="material-icons-outlined">print</span>
          <span class="d-et">Cihaz</span>
          <span class="d-val">{{ $cihaz }}</span>
        </div>
        @endif
        @if($ip)
        <div class="d-sat">
          <span class="material-icons-outlined">lan</span>
          <span class="d-et">IP Adresi</span>
          <span class="d-val">{{ $ip }}</span>
        </div>
        @endif
        @if($hedef)
        <div class="d-sat">
          <span class="material-icons-outlined">my_location</span>
          <span class="d-et">Hedef</span>
          <span class="d-val">{{ $hedef }}</span>
        </div>
        @endif
        <div class="d-sat">
          <span class="material-icons-outlined">category</span>
          <span class="d-et">Kategori</span>
          <span class="d-val">{{ $etiket }}</span>
        </div>
        @if(!empty($h['yol']))
        <div class="d-sat">
          <span class="material-icons-outlined">route</span>
          <span class="d-et">İşlem yolu</span>
          <span class="d-val">{{ $h['yol'] }}</span>
        </div>
        @endif
      </div>
    </div>
  @endforeach
</div>

{{-- Sonsuz scroll yükleniyor göstergesi --}}
<div class="hrk-daha" id="hrkDahaSpinner"><div class="spinner"></div></div>

{{-- SSR sayfalama (JS devre dışıysa) --}}
@php $toplamSayfa = (int)ceil(($ozet['toplam'] ?? 0) / max(1,$boyut)); @endphp
@if($toplamSayfa > 1)
<div id="hrkSsrSayfa" style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:18px">
  @if($sayfa > 1)
    <a href="/hareketler{{ $q(['sayfa'=>$sayfa-1]) }}" class="hrk-btn">
      <span class="material-icons-outlined" style="font-size:16px">chevron_left</span> Önceki
    </a>
  @endif
  <span class="hrk-btn akt">{{ $sayfa }} / {{ $toplamSayfa }}</span>
  @if($sayfa < $toplamSayfa)
    <a href="/hareketler{{ $q(['sayfa'=>$sayfa+1]) }}" class="hrk-btn">
      Sonraki <span class="material-icons-outlined" style="font-size:16px">chevron_right</span>
    </a>
  @endif
</div>
@endif

</div>{{-- #hrk --}}

<script>
/* =========================================================
   HAREKETLER – Flutter birebir: filtre + sonsuz scroll
   ========================================================= */
(function(){
  /* --- Durum --- */
  var state = {
    gun      : {{ (int)$gun }},
    kategori : @json($kategori),
    kim      : @json($kim ? (int)$kim : null),
    ara      : @json($ara),
    sayfa    : 1,
    boyut    : 40,
    toplam   : {{ (int)$toplamH }},
    yukleniyor: false,
    sonSayfa : false,
    ilkYukleme: true
  };
  /* İlk sayfayı SSR karşıladı; sonrasını JS çeker */
  state.sonSayfa = ({{ count($hareketler) }} >= state.toplam);

  /* --- Element referansları --- */
  var araEl     = document.getElementById('hrkAra');
  var araSilEl  = document.getElementById('hrkAraSil');
  var kimEl     = document.getElementById('hrkKim');
  var tbodyEl   = document.getElementById('hrkTbody');
  var mobilEl   = document.getElementById('hrkMobil');
  var ssrSayfa  = document.getElementById('hrkSsrSayfa');
  var yukEl     = document.getElementById('hrkYukleniyor');
  var bosEl     = document.getElementById('hrkBos');
  var dahaEl    = document.getElementById('hrkDahaSpinner');

  /* SSR sayfa linkleri gizle (JS devralıyor) */
  if(ssrSayfa) ssrSayfa.style.display = 'none';

  /* --- Kategori meta (JS'de de renk için) --- */
  var katMeta = {
    satis:    ['#10B981','receipt_long','Satış'],
    odeme:    ['#10B981','payments','Ödeme'],
    kayip:    ['#F43F5E','remove_circle_outline','Kayıp'],
    stok:     ['#3B82F6','inventory_2','Stok'],
    kasa:     ['#14B8A6','point_of_sale','Kasa'],
    cari:     ['#6366F1','account_balance_wallet','Cari'],
    personel: ['#F59E0B','badge','Personel'],
    gider:    ['#EA580C','money_off','Gider'],
    fis:      ['#7C3AED','description','Fiş'],
    menu:     ['#7C3AED','restaurant_menu','Menü'],
    recete:   ['#7C3AED','menu_book','Reçete'],
    ayar:     ['#94A3B8','settings','Ayar'],
    giris:    ['#94A3B8','login','Giriş'],
    mesai:    ['#14B8A6','schedule','Mesai'],
    cagri:    ['#F43F5E','notifications_active','Çağrı'],
    diger:    ['#94A3B8','bolt','Diğer']
  };
  var rolRenk = {Sahip:'#7C3AED',Müdür:'#2563EB',Garson:'#0D9488',Kasa:'#EA580C'};

  function getMeta(k){ return katMeta[k] || katMeta['diger']; }

  /* Flutter _kategori() */
  function kategoriBul(h){
    var k = h.kategori || '';
    if(k && k!=='diger' && katMeta[k]) return k;
    var s = ((h.baslik||'') + ' ' + (h.aciklama||'')).toLowerCase();
    var c = function(ks){ for(var i=0;i<ks.length;i++) if(s.indexOf(ks[i])>=0) return true; return false; };
    if(c(['iptal','geri alın','geri alind','void','iskonto','indirim','ikram','fire'])) return 'kayip';
    if(c(['ödeme','odeme','tahsil','nakit','kredi','kart','yemek kart']))              return 'odeme';
    if(c(['masa','adisyon','sipariş','siparis','satış','satis','birleştir','taşın','tasin','böl','bol'])) return 'satis';
    if(c(['stok','malzeme','sayım','sayim','alış','alis','tedarik']))                  return 'stok';
    if(c(['kasa','çekmece','cekmece','vardiya']))                                       return 'kasa';
    if(c(['cari','açık hesap','acik hesap','borç','borc']))                            return 'cari';
    if(c(['personel','maaş','maas','yetki','avans']))                                  return 'personel';
    if(c(['mesai','puantaj']))                                                          return 'mesai';
    if(c(['gider','masraf']))                                                           return 'gider';
    if(c(['menü','menu','ürün','urun','kategori']))                                    return 'menu';
    if(c(['reçete','recete','yarı mamül','yari mamul']))                               return 'recete';
    if(c(['fiş','fis','fatura']))                                                       return 'fis';
    if(c(['giriş','giris','login','çıkış','cikis','logout']))                          return 'giris';
    if(c(['ayar','tema','şema','sema']))                                                return 'ayar';
    if(c(['çağrı','cagri','garson']))                                                   return 'cagri';
    return k || 'diger';
  }

  function tl(v){ return new Intl.NumberFormat('tr-TR').format(Math.round(parseFloat(v)||0)) + '₺'; }

  /* --- Özet şerit güncelle --- */
  function ozetGuncelle(ozet){
    var t = parseInt(ozet.toplam || 0);
    document.getElementById('hrkToplamTxt').textContent = t.toLocaleString('tr-TR') + ' hareket';
    var g = parseFloat(ozet.giris || 0), c2 = parseFloat(ozet.cikis || 0);
    var gEl = document.getElementById('hrkGirisKutu');
    var cEl = document.getElementById('hrkCikisKutu');
    if(gEl){ gEl.style.display = g>0 ? '' : 'none'; document.getElementById('hrkGirisTutar').textContent = tl(g); }
    if(cEl){ cEl.style.display = c2>0 ? '' : 'none'; document.getElementById('hrkCikisTutar').textContent = tl(c2); }
  }

  /* --- Tablo satır HTML (masaüstü) --- */
  function tabloSatirHtml(h, gunBasi){
    var kat  = kategoriBul(h);
    var meta = getMeta(kat);
    var renk = meta[0], ikon = meta[1], etiket = meta[2];
    var personel = h.personel || null;
    var rol      = h.rol || null;
    var kaynak   = h.kaynak || null;
    var cihaz    = h.cihaz || null;
    var ip       = h.ip || null;
    var hedef    = h.hedef || null;
    var aciklama = h.aciklama || null;
    var tutar    = h.tutar != null ? parseFloat(h.tutar) : null;
    var yon      = h.yon || null;
    var bas      = personel ? personel.trim()[0].toUpperCase() : 'S';
    var avRenk   = rolRenk[rol] || '#64748B';
    var kaynakIk = kaynak==='Kasa' ? 'point_of_sale' : (kaynak==='Mobil' ? 'smartphone' : 'devices_other');
    var tRenk    = yon==='cikis' ? '#F43F5E' : (yon==='giris' ? '#10B981' : 'var(--ink)');
    var tIsaret  = yon==='cikis' ? '−' : (yon==='giris' ? '+' : '');
    var html     = '';

    if(gunBasi){
      html += '<tr class="gun-basi"><td colspan="7">'+esc(h.tarih||'')+'</td></tr>';
    }
    var tutarHtml = (tutar != null && tutar !== 0)
      ? '<span style="font-size:13px;font-weight:800;color:'+tRenk+'">'+tIsaret+tl(tutar)+'</span>'
      : '<span style="color:var(--sub)">—</span>';
    var kaynakHtml = '';
    if(kaynak){
      kaynakHtml += '<div style="display:flex;align-items:center;gap:3px;color:#4F46E5;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'
        +'<span class="material-icons-outlined" style="font-size:13px;color:#4F46E5">'+esc(kaynakIk)+'</span>'
        +'<span style="color:#4F46E5">'+esc(kaynak)+(cihaz ? ' · '+esc(cihaz) : '')+'</span></div>';
    }
    if(ip) kaynakHtml += '<div style="color:var(--sub);font-size:10.5px">'+esc(ip)+'</div>';
    if(!kaynak && !ip) kaynakHtml = '<span style="color:var(--sub)">—</span>';

    html +=
      '<tr>'+
      '<td style="padding:0;min-width:110px">'+
        '<div class="serit-wrap" style="padding:7px 8px 7px 10px">'+
          '<span class="serit-bar" style="background:'+renk+'"></span>'+
          '<div class="zaman-col"><div class="z1">'+esc(h.tarih||'')+'</div><div class="z2">'+esc(h.saat||'')+'</div></div>'+
        '</div>'+
      '</td>'+
      '<td style="padding:8px 10px">'+
        '<div style="display:flex;align-items:center;gap:8px;min-width:0">'+
          '<span class="hrk-avatar" style="background:'+avRenk+'">'+esc(bas)+'</span>'+
          '<div style="min-width:0">'+
            '<div class="hrk-trunc" style="font-size:12.5px;font-weight:600;color:var(--ink);max-width:130px">'+esc(personel||'Sistem')+'</div>'+
            (rol ? '<div style="font-size:10.5px;font-weight:600;color:'+avRenk+'">'+esc(rol)+'</div>' : '')+
          '</div>'+
        '</div>'+
      '</td>'+
      '<td style="padding:8px 10px">'+
        '<div style="display:flex;align-items:flex-start;gap:8px;min-width:0">'+
          '<span class="ikon-kutu" style="background:color-mix(in srgb,'+renk+' 14%,transparent)">'+
            '<span class="material-icons-outlined" style="color:'+renk+'">'+esc(ikon)+'</span>'+
          '</span>'+
          '<div style="min-width:0">'+
            '<div class="hrk-trunc" style="font-size:12.5px;font-weight:700;color:var(--ink);max-width:180px">'+esc(h.baslik||'—')+'</div>'+
            '<span class="kat-rozet" style="background:color-mix(in srgb,'+renk+' 13%,transparent);color:'+renk+'">'+esc(etiket)+'</span>'+
          '</div>'+
        '</div>'+
      '</td>'+
      '<td style="padding:8px 10px;font-size:11.5px;color:var(--sub2);font-weight:500">'+
        '<span class="hrk-trunc" style="max-width:100px">'+esc(hedef||'—')+'</span>'+
      '</td>'+
      '<td style="padding:8px 10px;font-size:11.5px">'+kaynakHtml+'</td>'+
      '<td style="padding:8px 10px;font-size:11.5px;color:var(--sub);line-height:1.3">'+
        '<div style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">'+
          esc(aciklama||'—')+'</div>'+
      '</td>'+
      '<td style="padding:8px 12px;text-align:right">'+tutarHtml+'</td>'+
      '</tr>';
    return html;
  }

  /* --- Mobil kart HTML --- */
  function mobilKartHtml(h, gunBasi, idx){
    var kat  = kategoriBul(h);
    var meta = getMeta(kat);
    var renk = meta[0], ikon = meta[1], etiket = meta[2];
    var personel = h.personel || null;
    var rol      = h.rol || null;
    var kaynak   = h.kaynak || null;
    var cihaz    = h.cihaz || null;
    var ip       = h.ip || null;
    var hedef    = h.hedef || null;
    var aciklama = h.aciklama || '';
    var tutar    = h.tutar != null ? parseFloat(h.tutar) : null;
    var yon      = h.yon || null;
    var kaynakIk = kaynak==='Kasa' ? 'point_of_sale' : (kaynak==='Mobil' ? 'smartphone' : 'devices_other');
    var tRenk    = yon==='cikis' ? '#F43F5E' : (yon==='giris' ? '#10B981' : 'var(--ink)');
    var tIsaret  = yon==='cikis' ? '−' : (yon==='giris' ? '+' : '');
    var html     = '';
    if(gunBasi) html += '<div class="hrk-gun-hdr">'+esc(h.tarih||'')+'</div>';

    var detayHtml = '';
    detayHtml += '<div class="d-sat"><span class="material-icons-outlined">event</span><span class="d-et">Tarih &amp; Saat</span><span class="d-val">'+esc(h.zaman_tam||(h.tarih+' '+h.saat))+'</span></div>';
    if(personel) detayHtml += '<div class="d-sat"><span class="material-icons-outlined">badge</span><span class="d-et">Kim</span><span class="d-val">'+esc(personel+(rol?' · '+rol:''))+'</span></div>';
    if(kaynak)   detayHtml += '<div class="d-sat"><span class="material-icons-outlined">devices_other</span><span class="d-et">Kaynak</span><span class="d-val">'+esc(kaynak)+'</span></div>';
    if(cihaz)    detayHtml += '<div class="d-sat"><span class="material-icons-outlined">print</span><span class="d-et">Cihaz</span><span class="d-val">'+esc(cihaz)+'</span></div>';
    if(ip)       detayHtml += '<div class="d-sat"><span class="material-icons-outlined">lan</span><span class="d-et">IP Adresi</span><span class="d-val">'+esc(ip)+'</span></div>';
    if(hedef)    detayHtml += '<div class="d-sat"><span class="material-icons-outlined">my_location</span><span class="d-et">Hedef</span><span class="d-val">'+esc(hedef)+'</span></div>';
    detayHtml += '<div class="d-sat"><span class="material-icons-outlined">category</span><span class="d-et">Kategori</span><span class="d-val">'+esc(etiket)+'</span></div>';
    if(h.yol) detayHtml += '<div class="d-sat"><span class="material-icons-outlined">route</span><span class="d-et">İşlem yolu</span><span class="d-val">'+esc(h.yol)+'</span></div>';

    var tutarHtml = (tutar != null && tutar !== 0)
      ? '<span class="tutar" style="color:'+tRenk+'">'+tIsaret+tl(tutar)+'</span>'
      : '';

    html +=
      '<div class="hrk-karti" data-idx="'+idx+'" style="--ak-renk:'+renk+'" onclick="hrkKartiToggle(this)">'+
        '<div class="hrk-karti-ust">'+
          '<div class="hrk-k-serit" style="background:'+renk+'"></div>'+
          '<div class="hrk-k-ic">'+
            '<div class="hrk-k-ikon" style="background:color-mix(in srgb,'+renk+' 14%,transparent)">'+
              '<span class="material-icons-outlined" style="color:'+renk+'">'+esc(ikon)+'</span>'+
            '</div>'+
            '<div class="hrk-k-icerik">'+
              '<div style="display:flex;align-items:center;gap:7px">'+
                '<span class="bas hrk-trunc" style="max-width:200px">'+esc(h.baslik||'—')+'</span>'+
                '<span class="kat-rozet" style="background:color-mix(in srgb,'+renk+' 13%,transparent);color:'+renk+';flex-shrink:0;font-size:10px;font-weight:800;padding:1px 7px;border-radius:20px">'+esc(etiket)+'</span>'+
              '</div>'+
              (aciklama ? '<div class="acik-txt">'+esc(aciklama)+'</div>' : '')+
              '<div class="meta">'+
                (personel ? '<span class="material-icons-outlined">person</span><span>'+esc(personel)+'</span>' : '')+
                '<span class="material-icons-outlined">access_time</span><span>'+esc(h.saat||'')+'</span>'+
                (kaynak ? '<span class="material-icons-outlined" style="color:#4F46E5">'+esc(kaynakIk)+'</span><span style="color:#4F46E5;font-weight:600">'+esc(kaynak)+'</span>' : '')+
              '</div>'+
            '</div>'+
          '</div>'+
          '<div class="hrk-k-sag">'+
            tutarHtml+
            '<span class="material-icons-outlined hrk-ok-ikon">keyboard_arrow_down</span>'+
          '</div>'+
        '</div>'+
        '<div class="hrk-detay" style="display:none">'+detayHtml+'</div>'+
      '</div>';
    return html;
  }

  /* --- HTML escape --- */
  function esc(s){ var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }

  /* --- Çipleri güncelle (filtre değişince) --- */
  function cipGuncelle(){
    var cipler = document.querySelectorAll('#hrkCipler .hrk-cip');
    cipler.forEach(function(el){
      var k = el.dataset.kat;
      var renk = el.dataset.renk;
      var aktif = (state.kategori === k) || (k==='hepsi' && state.kategori==='hepsi');
      el.classList.toggle('akt', aktif);
      el.style.background = aktif ? renk : '';
      el.style.boxShadow  = aktif ? '0 2px 8px color-mix(in srgb,'+renk+' 30%,transparent)' : '';
      var ikon = el.querySelector('.material-icons-outlined');
      if(ikon) ikon.style.color = aktif ? '#fff' : renk;
    });
  }

  /* --- Gün butonlarını güncelle --- */
  function gunGuncelle(){
    var btns = document.querySelectorAll('#hrk .hrk-gun-grp .hrk-btn');
    btns.forEach(function(el){
      var g = parseInt(el.href.match(/gun=(\d+)/)?.[1]||'0');
      el.classList.toggle('akt', g === state.gun);
    });
  }

  /* --- Veriyi yükle (sıfırdan veya ek sayfa) --- */
  function yukle(ekle){
    if(state.yukleniyor) return;
    state.yukleniyor = true;
    if(!ekle){ state.sayfa = 1; state.sonSayfa = false; }
    else { state.sayfa++; }

    if(!ekle){ yukEl.style.display=''; bosEl.style.display='none'; tbodyEl.innerHTML=''; mobilEl.innerHTML=''; }
    else { dahaEl.style.display=''; }

    var params = new URLSearchParams({
      gun     : state.gun,
      boyut   : state.boyut,
      sayfa   : state.sayfa
    });
    if(state.kategori && state.kategori !== 'hepsi') params.set('kategori', state.kategori);
    if(state.kim)  params.set('kim',  state.kim);
    if(state.ara)  params.set('ara',  state.ara);

    api('/api/patron/hareketler?' + params.toString(), null, function(res){
      yukEl.style.display = 'none';
      dahaEl.style.display = 'none';
      state.yukleniyor = false;
      if(!res || !res.ok){ bosEl.style.display=''; return; }
      var hlist = res.hareketler || [];
      if(!ekle){ ozetGuncelle(res.ozet||{}); state.toplam = parseInt(res.toplam||0); }
      state.sonSayfa = (hlist.length === 0) || ((state.sayfa * state.boyut) >= (res.toplam||0));

      if(hlist.length === 0 && !ekle){ bosEl.style.display=''; return; }

      /* Tablo & mobil ---sonki gun_key izleme--- */
      var prevGunTablo  = ekle ? (tbodyEl.dataset.lastGun||'')  : '';
      var prevGunMobil  = ekle ? (mobilEl.dataset.lastGun||'')  : '';
      var tHtml = '', mHtml = '';
      var baseIdx = ekle ? parseInt(mobilEl.dataset.count||'0') : 0;
      hlist.forEach(function(h, i){
        var gunBasiT = (h.gun_key || h.tarih) !== prevGunTablo;
        if(gunBasiT) prevGunTablo = h.gun_key || h.tarih;
        tHtml += tabloSatirHtml(h, gunBasiT);

        var gunBasiM = (h.gun_key || h.tarih) !== prevGunMobil;
        if(gunBasiM) prevGunMobil = h.gun_key || h.tarih;
        mHtml += mobilKartHtml(h, gunBasiM, baseIdx + i);
      });
      tbodyEl.dataset.lastGun = prevGunTablo;
      mobilEl.dataset.lastGun  = prevGunMobil;
      mobilEl.dataset.count    = String(baseIdx + hlist.length);

      tbodyEl.insertAdjacentHTML('beforeend', tHtml);
      mobilEl.insertAdjacentHTML('beforeend', mHtml);

      /* Kişi listesi (sadece ilk yükleme) */
      if(!ekle && res.personeller && res.personeller.length && kimEl){
        var optsHtml = '<option value="">👤 Herkes</option>';
        res.personeller.forEach(function(p){
          optsHtml += '<option value="'+parseInt(p.id)+'" '+(state.kim===parseInt(p.id)?'selected':'')+'>'+esc(p.ad)+'</option>';
        });
        kimEl.innerHTML = optsHtml;
      }
    });
  }

  /* --- Sonsuz Scroll --- */
  window.addEventListener('scroll', function(){
    if(state.sonSayfa || state.yukleniyor) return;
    if((window.scrollY + window.innerHeight) >= (document.body.scrollHeight - 500)){
      yukle(true);
    }
  }, {passive:true});

  /* --- Arama (debounce) --- */
  var araTimer;
  if(araEl){
    araEl.addEventListener('input', function(){
      if(araSilEl) araSilEl.style.display = araEl.value ? '' : 'none';
      clearTimeout(araTimer);
      araTimer = setTimeout(function(){ state.ara = araEl.value.trim(); yukle(false); }, 450);
    });
    araEl.addEventListener('keydown', function(e){ if(e.key==='Enter'){ clearTimeout(araTimer); state.ara=araEl.value.trim(); yukle(false); }});
    if(araSilEl) araSilEl.style.display = araEl.value ? '' : 'none';
  }

  /* --- Gün sekme tıkla --- */
  document.querySelectorAll('#hrk .hrk-gun-grp .hrk-btn').forEach(function(el){
    el.addEventListener('click', function(e){
      e.preventDefault();
      var m = el.href.match(/gun=(\d+)/);
      if(!m) return;
      state.gun = parseInt(m[1]);
      gunGuncelle();
      yukle(false);
    });
  });

  /* --- Kategori çip tıkla --- */
  document.querySelectorAll('#hrkCipler .hrk-cip').forEach(function(el){
    el.addEventListener('click', function(e){
      e.preventDefault();
      state.kategori = el.dataset.kat || 'hepsi';
      cipGuncelle();
      yukle(false);
    });
  });

  /* --- Personel seç --- */
  window.hrkKimDegis = function(v){
    state.kim = v ? parseInt(v) : null;
    yukle(false);
  };

  /* --- Arama temizle --- */
  window.hrkAraSil = function(){
    if(araEl){ araEl.value=''; if(araSilEl) araSilEl.style.display='none'; }
    state.ara = '';
    yukle(false);
  };

  /* --- Mobil kart detay aç/kapat --- */
  window.hrkKartiToggle = function(el){
    var detay = el.querySelector('.hrk-detay');
    var ok    = el.querySelector('.hrk-ok-ikon');
    if(!detay) return;
    var acik = detay.style.display !== 'none';
    detay.style.display = acik ? 'none' : '';
    el.classList.toggle('acik', !acik);
    if(ok) ok.textContent = acik ? 'keyboard_arrow_down' : 'keyboard_arrow_up';
  };

  /* --- İlk render: SSR veriyi sahiplen, sonra JS devam eder --- */
  /* Tablo ve mobil SSR ile dolu; lastGun ve count bilgilerini kur */
  (function initSsr(){
    var rows  = tbodyEl ? tbodyEl.querySelectorAll('tr') : [];
    var kartilar = mobilEl ? mobilEl.querySelectorAll('.hrk-karti') : [];
    mobilEl.dataset.count   = kartilar.length.toString();

    /* Son gün_key'i bul (son gun-basi satırından) */
    var gunBasilar = tbodyEl ? tbodyEl.querySelectorAll('tr.gun-basi td') : [];
    if(gunBasilar.length){
      tbodyEl.dataset.lastGun = gunBasilar[gunBasilar.length-1].textContent.trim();
    }
    var mGunHdr = mobilEl ? mobilEl.querySelectorAll('.hrk-gun-hdr') : [];
    if(mGunHdr.length){
      mobilEl.dataset.lastGun = mGunHdr[mGunHdr.length-1].textContent.trim();
    }
  })();

})();
</script>
@endsection

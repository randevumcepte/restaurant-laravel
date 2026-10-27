@extends('layout.app')
@section('title', 'Kasa (Vardiya)')
@section('rk_ikon', 'point_of_sale')
@section('baslik', 'Kasa (Vardiya)')
@section('rk_alt')
    @if($acik ?? false)
        <span style="display:inline-flex;align-items:center;gap:5px;font-size:13px;font-weight:700;color:#10B981">
            <span class="material-icons-outlined" style="font-size:14px">radio_button_checked</span>
            Açık · {{ $acilis ?? '' }}@if(!empty($kasa_ad)) — {{ $kasa_ad }}@endif
        </span>
    @else
        <span style="color:#94a3b8;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:4px">
            <span class="material-icons-outlined" style="font-size:14px">lock</span> Kapalı
        </span>
    @endif
@endsection

@section('rk_araclar')
    @if($acik ?? false)
        <button class="kasa-hbtn" style="--hc:#10B981" onclick="kasaHareket('giris')">
            <span class="material-icons-outlined">add</span> Kasaya Koy
        </button>
        <button class="kasa-hbtn" style="--hc:#F59E0B" onclick="kasaHareket('cikis')">
            <span class="material-icons-outlined">remove</span> Kasadan Al
        </button>
        <button class="kasa-hbtn kasa-hbtn-dolu" style="--hc:#F43F5E" onclick="kasaKapat()">
            <span class="material-icons-outlined">lock_outline</span> Kasa Kapat (Say)
        </button>
    @else
        <button class="kasa-hbtn kasa-hbtn-dolu" style="--hc:#10B981" onclick="kasaAc()">
            <span class="material-icons-outlined">lock_open</span> Kasa Aç
        </button>
    @endif
    <button title="Yenile" onclick="location.reload()"
        style="background:transparent;border:none;cursor:pointer;color:#94a3b8;display:inline-flex;align-items:center;margin-left:4px">
        <span class="material-icons-outlined" style="font-size:20px">refresh</span>
    </button>
@endsection

@push('head')
<style>
/* ===== KASA — Flutter kasa_screen.dart bire bir (masaüstü görünümü) ===== */
#ks{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;
  --line:#e2e8f0;
  margin:0
}
body.koyu #ks{
  --card:#1b2235;--card2:#161d2e;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;
  --line:#2c3650
}

/* --- header butonlar --- */
.kasa-hbtn{
  display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:10px;
  font-size:13px;font-weight:700;cursor:pointer;margin-left:6px;
  background:color-mix(in srgb,var(--hc) 10%,transparent);
  border:1.4px solid color-mix(in srgb,var(--hc) 55%,transparent);
  color:var(--hc);transition:opacity .12s
}
.kasa-hbtn:hover{opacity:.8}
.kasa-hbtn .material-icons-outlined{font-size:17px}
.kasa-hbtn-dolu{background:var(--hc)!important;border-color:var(--hc)!important;color:#fff!important}

/* --- Flash mesajları --- */
#ks .flash{
  display:flex;align-items:center;gap:8px;padding:11px 16px;border-radius:12px;
  margin-bottom:14px;font-size:13.5px;font-weight:600
}
#ks .flash.ok{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#059669}
body.koyu #ks .flash.ok{color:#34d399}
#ks .flash.err{background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.3);color:#e11d48}
body.koyu #ks .flash.err{color:#fb7185}

/* --- Kasa kapalı kartı (Flutter _masaustuKapali) --- */
#ks .kapali-wrap{max-width:460px;margin:50px auto 0}
#ks .kapali-kart{
  background:var(--card);border:1px solid var(--line);border-radius:20px;
  padding:36px 32px;text-align:center
}
#ks .kapali-kart .k-ikon{color:var(--line);font-size:64px}
#ks .kapali-kart .k-bas{font-size:22px;font-weight:800;color:var(--ink);margin:14px 0 8px}
#ks .kapali-kart .k-alt{font-size:13px;color:var(--sub);line-height:1.6}
#ks .kapali-kart .k-son{font-size:12px;color:var(--sub2);margin-top:10px}
#ks .kapali-kart .k-ac-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  margin-top:22px;width:220px;height:50px;border:none;border-radius:14px;
  background:#10B981;color:#fff;font-size:16px;font-weight:800;cursor:pointer
}
#ks .kapali-kart .k-ac-btn:hover{opacity:.88}
#ks .kapali-kart .k-ac-btn .material-icons-outlined{font-size:20px}

/* --- Özet 3'lü ızgara (Flutter MIstatKart) --- */
#ks .ozet3{display:grid;grid-template-columns:12fr 9fr 9fr;gap:14px;margin-bottom:18px;align-items:start}
@media(max-width:680px){#ks .ozet3{grid-template-columns:1fr}}
@media(min-width:681px) and (max-width:900px){#ks .ozet3{grid-template-columns:1fr 1fr 1fr}}

/* Büyük beklenen nakit kartı — gradient mor (Flutter MIstatKart + renk=mor1) */
#ks .hero-kart{
  background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:18px;
  padding:20px 22px;box-shadow:0 8px 22px rgba(124,58,237,.28)
}
#ks .hero-kart .h-ust{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
#ks .hero-kart .h-etiket{color:rgba(255,255,255,.75);font-size:13px;font-weight:600}
#ks .hero-kart .h-rozet{
  background:rgba(255,255,255,.22);border-radius:20px;padding:3px 10px;
  font-size:11.5px;font-weight:700;color:#fff;display:inline-flex;align-items:center;gap:4px
}
#ks .hero-kart .h-rozet .material-icons-outlined{font-size:13px}
#ks .hero-kart .h-buyuk{color:#fff;font-size:34px;font-weight:900;letter-spacing:-.5px;line-height:1.1;margin-bottom:5px}
#ks .hero-kart .h-alt{color:rgba(255,255,255,.68);font-size:12.5px}
#ks .hero-kart .h-satir{
  display:flex;justify-content:space-between;padding:4px 0;
  border-top:1px solid rgba(255,255,255,.18);margin-top:10px
}
#ks .hero-kart .h-satir .k{color:rgba(255,255,255,.68);font-size:12px}
#ks .hero-kart .h-satir .v{font-weight:700;color:#fff;font-size:12.5px}

/* Küçük stat kartı (Giriş / Çıkış) — Flutter MIstatKart */
#ks .stat-kart{
  background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px 20px
}
#ks .stat-kart .sk-ust{display:flex;align-items:center;gap:8px;margin-bottom:8px}
#ks .stat-kart .sk-ikon{
  width:34px;height:34px;border-radius:10px;
  display:flex;align-items:center;justify-content:center
}
#ks .stat-kart .sk-ikon .material-icons-outlined{font-size:18px;color:#fff}
#ks .stat-kart .sk-etiket{font-size:13px;font-weight:600;color:var(--sub)}
#ks .stat-kart .sk-buyuk{font-size:28px;font-weight:900;letter-spacing:-.5px;margin-bottom:8px}
#ks .stat-kart .sk-satir{
  display:flex;justify-content:space-between;font-size:12px;
  padding:4px 0;border-top:1px solid var(--line)
}
#ks .stat-kart .sk-satir .k{color:var(--sub)}
#ks .stat-kart .sk-satir .v{font-weight:600;color:var(--ink)}

/* --- Hareketler başlığı (Flutter MBolumBaslik) --- */
#ks .har-baslik{
  display:flex;align-items:center;gap:8px;
  font-size:15px;font-weight:800;color:var(--ink);margin:0 0 10px
}
#ks .har-baslik .material-icons-outlined{font-size:18px;color:var(--mor1)}
#ks .har-sayac{
  background:color-mix(in srgb,#7C3AED 13%,transparent);color:#7C3AED;
  border-radius:20px;padding:1px 8px;font-size:12px;font-weight:700
}

/* --- Hareketler tablosu (Flutter MTablo) --- */
#ks .har-tablo{background:var(--card);border:1px solid var(--line);border-radius:16px;overflow:hidden}
#ks .har-tablo table{width:100%;border-collapse:collapse}
#ks .har-tablo thead th{
  background:var(--card2);color:var(--sub2);font-size:11.5px;font-weight:700;
  text-transform:uppercase;letter-spacing:.4px;
  padding:9px 14px;text-align:left;border-bottom:1px solid var(--line)
}
#ks .har-tablo thead th.orta{text-align:center}
#ks .har-tablo thead th.sag{text-align:right}
#ks .har-tablo tbody tr:not(:last-child) td{border-bottom:1px solid var(--line)}
#ks .har-tablo tbody td{
  padding:10px 14px;font-size:13.5px;color:var(--ink);vertical-align:middle
}
#ks .har-tablo tbody td.orta{text-align:center}
#ks .har-tablo tbody td.sag{text-align:right}
#ks .har-tablo .tip-ad{font-weight:700;font-size:13.5px;color:var(--ink)}
#ks .har-tablo .acikl{color:var(--sub);font-size:12px;margin-top:2px;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px}
/* Giriş rozeti (Flutter MRozet yeşil) */
#ks .har-rozet{
  display:inline-flex;align-items:center;gap:4px;border-radius:20px;
  padding:3px 9px;font-size:11.5px;font-weight:700;color:#fff
}
#ks .har-rozet .material-icons-outlined{font-size:13px}
#ks .har-tutar{font-size:13.5px;font-weight:800}
#ks .har-zaman{font-size:12px;color:var(--sub2);white-space:nowrap}
#ks .har-bos{text-align:center;color:var(--sub);padding:40px 10px;font-size:14px}

/* --- Çoklu kasa bilgi bandı --- */
#ks .coklu-band{
  display:flex;align-items:center;gap:12px;
  background:var(--card);border:1px solid var(--line);border-radius:14px;
  padding:12px 16px;margin-bottom:14px
}
#ks .coklu-band .material-icons-outlined{color:#D97706;font-size:22px;flex:none}
#ks .coklu-band .c-ad{font-weight:800;color:var(--ink);font-size:14.5px}
#ks .coklu-band .c-alt{color:var(--sub2);font-size:11.5px}
#ks .coklu-band .c-btns{display:flex;gap:8px;margin-left:auto;flex-shrink:0}
#ks .coklu-band .c-link{
  display:inline-flex;align-items:center;gap:5px;padding:7px 12px;border-radius:9px;
  border:1px solid var(--line);background:transparent;font-size:12.5px;font-weight:700;
  color:var(--sub);text-decoration:none
}
#ks .coklu-band .c-link:hover{color:var(--ink)}
#ks .coklu-band .c-link .material-icons-outlined{font-size:15px}

/* ===== MODAL ===== */
#kasaMod{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
  z-index:9000;align-items:center;justify-content:center;padding:16px
}
#kasaMod.ac{display:flex}
#kasaMod .mk{
  background:var(--card,#fff);border-radius:20px;padding:28px 26px;
  width:100%;max-width:420px;box-shadow:0 20px 50px rgba(0,0,0,.25)
}
body.koyu #kasaMod .mk{background:#161d2e}
#kasaMod .mk-bas{
  font-size:17px;font-weight:800;color:var(--ink,#1e293b);
  margin-bottom:16px;display:flex;align-items:center;gap:8px
}
#kasaMod .mk-bas .material-icons-outlined{font-size:22px}
#kasaMod .mk-lbl{font-size:12.5px;font-weight:600;color:var(--sub,#64748b);margin-bottom:8px}
#kasaMod .mk-inp{
  width:100%;height:58px;border:1.5px solid var(--line,#e2e8f0);border-radius:12px;
  background:var(--card2,#f1f5f9);font-size:24px;font-weight:900;
  color:var(--ink,#1e293b);padding:0 56px 0 16px;text-align:right;outline:none;
  transition:border-color .15s;box-sizing:border-box
}
body.koyu #kasaMod .mk-inp{background:#1b2235;border-color:#2c3650;color:#e2e8f0}
#kasaMod .mk-inp:focus{border-color:var(--mc,#7C3AED)}
#kasaMod .mk-suffix{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  font-size:14px;font-weight:700;color:var(--sub2,#94a3b8);pointer-events:none
}
#kasaMod .mk-inp-wrap{position:relative}
#kasaMod .mk-btns{display:flex;gap:10px;margin-top:18px}
#kasaMod .mk-vaz{
  flex:1;height:48px;border:1px solid var(--line,#e2e8f0);background:transparent;
  border-radius:12px;font-size:15px;font-weight:700;color:var(--sub,#64748b);cursor:pointer
}
#kasaMod .mk-onay{
  flex:2;height:48px;border:none;border-radius:12px;
  background:var(--mc,#7C3AED);color:#fff;font-size:15px;font-weight:800;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px
}
#kasaMod .mk-onay:disabled{opacity:.5;cursor:default}
#kasaMod .mk-onay .material-icons-outlined{font-size:18px}
/* Kapatma özet */
#kasaMod .kap-ozet{display:none;margin-top:16px}
#kasaMod .kap-satir{
  display:flex;justify-content:space-between;padding:5px 0;font-size:14px
}
#kasaMod .kap-satir .k{color:var(--sub,#64748b)}
#kasaMod .kap-satir .v{font-weight:700;color:var(--ink,#1e293b)}
#kasaMod .kap-hr{border:0;border-top:1px solid var(--line,#e2e8f0);margin:4px 0}
</style>
@endpush

@section('content')
@php
  $acik     = $acik ?? false;
  $giris    = (float)($giris ?? 0);
  $cikis    = (float)($cikis ?? 0);
  $beklenen = (float)($beklenen ?? 0);
  $devir    = (float)($devir ?? 0);
  $sonDevir = (float)($son_devir ?? 0);
  $hareketler = $hareketler ?? [];
  $coklu    = $coklu ?? false;
  $kasaAd   = $kasa_ad ?? null;
  $etiket   = ['devir'=>'Devir','satis'=>'Nakit Satış','tahsilat'=>'Tahsilat','gider'=>'Gider','personel'=>'Personel','al'=>'Kasadan Al','koy'=>'Kasaya Koy'];
  $tl = fn($v) => number_format((float)$v, 0, ',', '.') . '₺';
@endphp

<div id="ks">

{{-- Flash --}}
@if(session('ok'))
  <div class="flash ok"><span class="material-icons-outlined">check_circle</span> {{ session('ok') }}</div>
@endif
@if(session('hata'))
  <div class="flash err"><span class="material-icons-outlined">error_outline</span> {{ session('hata') }}</div>
@endif

{{-- Çoklu kasa bandı --}}
@if($coklu)
  <div class="coklu-band">
    <span class="material-icons-outlined">storefront</span>
    <div>
      <div class="c-ad">{{ $kasaAd ?? 'Kasa seçilmedi' }}</div>
      <div class="c-alt">Çoklu kasa aktif — bu ekran yalnızca bu kasanın vardiyasını gösterir.</div>
    </div>
    <div class="c-btns">
      <a href="/pos-kasa-sec" class="c-link"><span class="material-icons-outlined">swap_horiz</span> Kasa Değiştir</a>
      <a href="/kasalar" class="c-link"><span class="material-icons-outlined">settings</span> Kasaları Yönet</a>
    </div>
  </div>
@endif

@if(!$acik)
{{-- ==================== KASA KAPALI (Flutter _masaustuKapali) ==================== --}}
<div class="kapali-wrap">
  <div class="kapali-kart">
    <span class="material-icons-outlined k-ikon">point_of_sale</span>
    <div class="k-bas">Kasa kapalı</div>
    <div class="k-alt">Vardiyayı başlatmak için kasayı açın.<br>Açılış devir = şu an çekmecedeki nakit.</div>
    @if($sonDevir > 0)
      <div class="k-son">Son kapanış: {{ $tl($sonDevir) }}</div>
    @endif
    <button class="k-ac-btn" onclick="kasaAc()">
      <span class="material-icons-outlined">lock_open</span> Kasa Aç
    </button>
  </div>
</div>

@else
{{-- ==================== KASA AÇIK (Flutter _masaustuAcik) ==================== --}}

{{-- 3'lü özet (Flutter: beklenenKart + girisKart + cikisKart) --}}
<div class="ozet3">

  {{-- Kasada Beklenen Nakit — gradient mor kart --}}
  <div class="hero-kart">
    <div class="h-ust">
      <span class="h-etiket">Kasada Beklenen Nakit</span>
      <span class="h-rozet">
        <span class="material-icons-outlined">radio_button_checked</span>
        Açık · {{ $acilis ?? '' }}
      </span>
    </div>
    <div class="h-buyuk">{{ $tl($beklenen) }}</div>
    <div class="h-satir">
      <span class="k">Açan</span><span class="v">{{ $acan ?? '-' }}</span>
    </div>
    <div class="h-satir">
      <span class="k">Açılış devir</span><span class="v">{{ $tl($devir) }}</span>
    </div>
  </div>

  {{-- Giriş (+) — yeşil --}}
  <div class="stat-kart">
    <div class="sk-ust">
      <span class="sk-ikon" style="background:rgba(16,185,129,.15)">
        <span class="material-icons-outlined" style="color:#10B981!important">south_west</span>
      </span>
      <span class="sk-etiket">Giriş (+)</span>
    </div>
    <div class="sk-buyuk" style="color:#10B981">{{ $tl($giris) }}</div>
    <div class="sk-satir">
      <span class="k">Devir dahil kasaya eklenen</span>
      <span class="v"></span>
    </div>
  </div>

  {{-- Çıkış (−) — kırmızı --}}
  <div class="stat-kart">
    <div class="sk-ust">
      <span class="sk-ikon" style="background:rgba(244,63,94,.15)">
        <span class="material-icons-outlined" style="color:#F43F5E!important">north_east</span>
      </span>
      <span class="sk-etiket">Çıkış (−)</span>
    </div>
    <div class="sk-buyuk" style="color:#F43F5E">{{ $tl($cikis) }}</div>
    <div class="sk-satir">
      <span class="k">Kasadan alınan</span>
      <span class="v"></span>
    </div>
  </div>

</div>{{-- /ozet3 --}}

{{-- Hareketler başlığı (Flutter MBolumBaslik) --}}
<div class="har-baslik">
  <span class="material-icons-outlined">receipt_long</span>
  Hareketler ({{ count($hareketler) }})
  <span class="har-sayac">{{ count($hareketler) }}</span>
</div>

@if(empty($hareketler))
  <div class="har-bos">Henüz hareket yok.</div>
@else
<div class="har-tablo">
  <table>
    <thead>
      <tr>
        <th style="width:15%">Tip</th>
        <th style="width:28%">Açıklama</th>
        <th class="orta" style="width:11%">Yön</th>
        <th class="sag" style="width:15%">Tutar</th>
        <th class="sag" style="width:13%">Zaman</th>
      </tr>
    </thead>
    <tbody>
      @foreach($hareketler as $h)
        @php
          $girisMi = ($h['yon'] ?? '') === 'giris';
          $hRenk   = $girisMi ? '#10B981' : '#F43F5E';
          $hIkon   = $girisMi ? 'south_west' : 'north_east';
          $tipAd   = $etiket[$h['tip'] ?? ''] ?? ($h['tip'] ?? '-');
          $aciklama = trim(($h['aciklama'] ?? '') . (!empty($h['personel']) ? ' · '.$h['personel'] : ''));
        @endphp
        <tr>
          <td>
            <div class="tip-ad">{{ $tipAd }}</div>
            @if($aciklama)<div class="acikl">{{ $aciklama }}</div>@endif
          </td>
          <td>
            @if($aciklama)
              <span style="color:var(--sub);font-size:12.5px;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px">{{ $aciklama }}</span>
            @else
              <span style="color:var(--sub2)">—</span>
            @endif
          </td>
          <td class="orta">
            <span class="har-rozet" style="background:{{ $hRenk }}">
              <span class="material-icons-outlined">{{ $hIkon }}</span>
              {{ $girisMi ? 'Giriş' : 'Çıkış' }}
            </span>
          </td>
          <td class="sag">
            <span class="har-tutar" style="color:{{ $hRenk }}">
              {{ $girisMi ? '+' : '−' }}{{ $tl($h['tutar'] ?? 0) }}
            </span>
          </td>
          <td class="sag har-zaman">{{ $h['zaman'] ?? '' }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif

@endif{{-- /acik --}}

</div>{{-- /#ks --}}

{{-- ==================== MODAL (Flutter dialog: _tutarSor / _kasaKapat) ==================== --}}
<div id="kasaMod">
  <div class="mk">
    <div class="mk-bas">
      <span class="material-icons-outlined" id="mkIkon" style="color:#7C3AED">point_of_sale</span>
      <span id="mkBaslik">Kasa Aç</span>
    </div>
    <div class="mk-lbl" id="mkLbl">Açılış devir (kasadaki mevcut nakit)</div>
    <div class="mk-inp-wrap">
      <input id="mkTutar" class="mk-inp" type="number" min="0" step="0.01" placeholder="0" inputmode="decimal">
      <span class="mk-suffix">₺</span>
    </div>
    {{-- Kasa kapatma özeti (ikinci tıkta gösterilir) --}}
    <div class="kap-ozet" id="mkKapOzet">
      <hr class="kap-hr">
      <div class="kap-satir"><span class="k">Beklenen</span><span class="v" id="mkKapBek">—</span></div>
      <div class="kap-satir"><span class="k">Sayılan</span><span class="v" id="mkKapSay">—</span></div>
      <hr class="kap-hr">
      <div class="kap-satir">
        <span class="k" style="font-weight:700">Fark</span>
        <span class="v" id="mkKapFark" style="font-size:18px">—</span>
      </div>
    </div>
    <div class="mk-btns">
      <button class="mk-vaz" onclick="mkKapat()">Vazgeç</button>
      <button class="mk-onay" id="mkOnayBtn" onclick="mkOnayla()">
        <span class="material-icons-outlined" id="mkOnayIkon">check</span>
        <span id="mkOnayYazi">Tamam</span>
      </button>
    </div>
  </div>
</div>

{{-- Gizli POST formları --}}
<form id="fKasaAc" method="POST" action="/kasa/ac" style="display:none">@csrf<input type="hidden" name="devir" id="fAcDevir"></form>
<form id="fKasaHar" method="POST" action="/kasa/hareket" style="display:none">@csrf
  <input type="hidden" name="yon" id="fHarYon">
  <input type="hidden" name="tutar" id="fHarTutar">
</form>
<form id="fKasaKap" method="POST" action="/kasa/kapat" style="display:none">@csrf<input type="hidden" name="sayilan" id="fKapSay"></form>

<script>
(function(){
var _tip = null;
var _beklenen = {{ (float)($beklenen ?? 0) }};

function tl(v){ return Number(v||0).toLocaleString('tr-TR',{maximumFractionDigits:0}) + '₺'; }

function mkAc(tip, baslik, lbl, renk, onayYazi, onayIkon){
  _tip = tip;
  var mc = renk||'#7C3AED';
  document.getElementById('mkIkon').textContent   = onayIkon||'point_of_sale';
  document.getElementById('mkIkon').style.color   = mc;
  document.getElementById('mkBaslik').textContent = baslik;
  document.getElementById('mkLbl').textContent    = lbl;
  document.getElementById('mkOnayYazi').textContent = onayYazi||'Tamam';
  document.getElementById('mkOnayIkon').textContent = onayIkon||'check';
  document.getElementById('mkOnayBtn').style.background = mc;
  document.getElementById('mkTutar').value = '';
  document.getElementById('mkTutar').style.setProperty('--mc', mc);
  document.getElementById('mkKapOzet').style.display = 'none';
  document.getElementById('kasaMod').classList.add('ac');
  setTimeout(function(){ document.getElementById('mkTutar').focus(); }, 60);
}

window.mkKapat = function(){
  document.getElementById('kasaMod').classList.remove('ac');
  _tip = null;
};

window.kasaAc = function(){
  mkAc('ac','Kasa Aç','Açılış devir (kasadaki mevcut nakit)','#10B981','Kasayı Aç','lock_open');
};
window.kasaHareket = function(yon){
  if(yon==='giris') mkAc('giris','Kasaya Koy','Eklenecek nakit','#10B981','Koy','add');
  else              mkAc('cikis','Kasadan Al','Çıkarılacak nakit','#F59E0B','Al','remove');
};
window.kasaKapat = function(){
  mkAc('kapat','Kasa Kapat — Sayım','Sayılan nakit (fiziksel)','#F43F5E','Kapat & Hesapla','lock_outline');
};

window.mkOnayla = function(){
  var tutar = parseFloat((document.getElementById('mkTutar').value||'0').replace(',','.'));
  if(isNaN(tutar)||tutar<0){ alert('Geçerli bir tutar girin.'); return; }

  if(_tip === 'ac'){
    document.getElementById('fAcDevir').value = tutar;
    document.getElementById('fKasaAc').submit();

  } else if(_tip === 'giris' || _tip === 'cikis'){
    if(tutar <= 0){ alert('Sıfırdan büyük bir tutar girin.'); return; }
    document.getElementById('fHarYon').value   = _tip;
    document.getElementById('fHarTutar').value = tutar;
    document.getElementById('fKasaHar').submit();

  } else if(_tip === 'kapat'){
    // İlk tık: farkı hesapla göster, onayı "Onayla & Kapat" yap
    var fark = tutar - _beklenen;
    document.getElementById('mkKapBek').textContent = tl(_beklenen);
    document.getElementById('mkKapSay').textContent = tl(tutar);
    var fEl = document.getElementById('mkKapFark');
    fEl.textContent = (fark >= 0 ? '+' : '') + tl(fark);
    fEl.style.color = fark === 0 ? '#10B981' : (fark > 0 ? '#4F46E5' : '#F43F5E');
    document.getElementById('mkKapOzet').style.display = 'block';
    document.getElementById('mkOnayYazi').textContent  = 'Onayla & Kapat';
    document.getElementById('mkOnayIkon').textContent  = 'lock';
    document.getElementById('fKapSay').value = tutar;
    _tip = 'kapat2';

  } else if(_tip === 'kapat2'){
    document.getElementById('fKasaKap').submit();
  }
};

// Enter ile onayla
document.getElementById('mkTutar').addEventListener('keydown', function(e){
  if(e.key === 'Enter') window.mkOnayla();
});
// Dışarı tıkla kapat
document.getElementById('kasaMod').addEventListener('click', function(e){
  if(e.target === this) window.mkKapat();
});
})();
</script>
@endsection

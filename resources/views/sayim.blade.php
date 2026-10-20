@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Sayım')
@section('title', 'Sayım (Envanter)')

@push('head')
<style>
/* ===== SAYIM – Flutter sayim_screen / sayim_gecmis_screen / sayim_detay_screen eşdeğeri ===== */
#sym{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc}
body.koyu #sym{--card:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#101624}

/* --- Bilgi kutusu (Flutter mavi info banner) --- */
#sym .sym-info{display:flex;align-items:flex-start;gap:8px;padding:12px 14px;border-radius:12px;
  background:color-mix(in srgb,var(--mavi) 10%,transparent);
  border:1px solid color-mix(in srgb,var(--mavi) 35%,transparent);margin-bottom:14px}
#sym .sym-info .material-icons-outlined{color:var(--mavi);font-size:18px;flex-shrink:0;margin-top:1px}
#sym .sym-info p{color:var(--sub2);font-size:12.5px;line-height:1.35;margin:0}

/* --- Arama alanı (Flutter TextField) --- */
#sym .sym-ara-wrap{position:relative;margin-bottom:10px}
#sym .sym-ara-wrap .material-icons-outlined{position:absolute;left:10px;top:50%;transform:translateY(-50%);
  font-size:18px;color:var(--sub);pointer-events:none}
#sym #symAra{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s}
#sym #symAra:focus{border-color:var(--mor1)}
body.koyu #sym #symAra{background:#1b2235}

/* --- Malzeme satırı (Flutter _satir widget) --- */
#sym .sym-satir{display:flex;align-items:center;gap:8px;padding:12px;border-radius:12px;
  background:var(--card);border:1px solid var(--line);margin-bottom:8px}
#sym .sym-satir .sym-ad{flex:4;min-width:0}
#sym .sym-satir .sym-ad .ad{color:var(--ink);font-size:14px;font-weight:600;white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis}
#sym .sym-satir .sym-ad .teorik{color:var(--sub);font-size:12px;margin-top:1px}
#sym .sym-satir .sym-inp{width:88px;flex-shrink:0}
#sym .sym-satir .sym-inp input{width:100%;padding:10px 4px;border:1px solid var(--line);border-radius:8px;
  background:var(--card);color:var(--ink);font-size:14px;text-align:center;outline:none;transition:border-color .15s}
body.koyu #sym .sym-satir .sym-inp input{background:#1b2235}
#sym .sym-satir .sym-inp input:focus{border-color:var(--mor1)}
#sym .sym-satir .sym-inp input:disabled{opacity:.55;cursor:not-allowed}
#sym .sym-satir .sym-fark{width:62px;flex-shrink:0;text-align:right;font-size:13px;font-weight:700;
  color:var(--sub)}

/* --- Sonuç kartı (Flutter _sonucGoster AlertDialog) --- */
#sym .sym-sonuc{padding:14px 16px;border-radius:12px;background:var(--card);border:1px solid var(--line);
  margin-bottom:16px}
#sym .sym-sonuc .baslik{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:4px}
#sym .sym-sonuc .alt{font-size:13px;color:var(--sub)}
#sym .sym-sonuc .fark{font-size:15px;font-weight:800;margin-top:6px}

/* --- Hata flash --- */
#sym .sym-hata{padding:12px 14px;border-radius:10px;background:color-mix(in srgb,var(--kirmizi) 10%,transparent);
  border:1px solid color-mix(in srgb,var(--kirmizi) 35%,transparent);color:var(--kirmizi);
  font-size:13px;font-weight:600;margin-bottom:14px}

/* --- Alt yapışık Kaydet butonu (Flutter bottomSheet) --- */
#sym .sym-alt{position:sticky;bottom:0;left:0;right:0;padding:12px 0 20px;
  background:var(--card);border-top:1px solid var(--line);margin-top:4px}
#sym .sym-alt button{width:100%;padding:14px;border:none;border-radius:12px;background:var(--mor1);
  color:#fff;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;
  justify-content:center;gap:8px;transition:opacity .15s}
#sym .sym-alt button:disabled{opacity:.55;cursor:not-allowed}

/* --- Geçmiş bölümü (Flutter SayimGecmisScreen) --- */
#sym .sym-sec-baslik{display:flex;align-items:center;gap:8px;margin:18px 0 10px}
#sym .sym-sec-baslik .cubuk{width:4px;height:18px;border-radius:3px;background:var(--mor1);flex-shrink:0}
#sym .sym-sec-baslik span{color:var(--ink);font-size:15px;font-weight:700}
#sym .sym-gecmis-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))}
#sym .sym-gecmis-kart{display:flex;align-items:center;padding:13px;border-radius:12px;
  background:var(--card);border:1px solid var(--line);cursor:pointer;transition:box-shadow .12s;text-decoration:none}
#sym .sym-gecmis-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.10)}
#sym .sym-gecmis-kart .sol{flex:1;min-width:0}
#sym .sym-gecmis-kart .tarih{color:var(--ink);font-size:14px;font-weight:600}
#sym .sym-gecmis-kart .alt{color:var(--sub);font-size:12px;margin-top:2px}
#sym .sym-gecmis-kart .tutar{font-size:14px;font-weight:700;margin-right:4px;flex-shrink:0}
#sym .sym-gecmis-kart .material-icons-outlined{color:var(--sub);font-size:20px;flex-shrink:0}

/* --- Detay Modal (Flutter SayimDetayScreen) --- */
#symModal{display:none;position:fixed;inset:0;z-index:900;align-items:flex-start;justify-content:center;
  background:rgba(0,0,0,.45);padding:16px;overflow-y:auto}
#symModal.acik{display:flex}
#symModal .sym-mod-wrap{background:var(--card,#fff);border-radius:18px;width:100%;max-width:600px;
  overflow:hidden;margin:auto}
body.koyu #symModal .sym-mod-wrap{background:#1b2235}
#symModal .sym-mod-header{display:flex;align-items:center;justify-content:space-between;
  padding:16px 16px 12px;border-bottom:1px solid var(--line,#e2e8f0)}
#symModal .sym-mod-title{color:var(--ink,#1e293b);font-size:16px;font-weight:700}
#symModal .sym-mod-kapat{width:32px;height:32px;border:none;background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;border-radius:8px;color:var(--sub,#64748b)}
#symModal .sym-mod-kapat:hover{background:var(--line,#e2e8f0)}
#symModal .sym-mod-body{padding:14px 16px 20px}

/* Detay özet gradient kartı (Flutter LinearGradient header) */
#symModal .sym-det-ozet{border-radius:16px;padding:16px;margin-bottom:14px}
#symModal .sym-det-ozet .etiket{color:rgba(255,255,255,.75);font-size:13px;margin-bottom:2px}
#symModal .sym-det-ozet .deger{color:#fff;font-size:26px;font-weight:800}
/* AI yorum satırı (Flutter ✨ kutu) */
#symModal .sym-ai{display:flex;gap:8px;align-items:flex-start;padding:12px;border-radius:12px;
  background:var(--card,#fff);border:1px solid color-mix(in srgb,#9D5DC8 40%,transparent);
  margin-bottom:8px}
body.koyu #symModal .sym-ai{background:#161d2e}
#symModal .sym-ai .emoji{font-size:14px;flex-shrink:0;margin-top:1px}
#symModal .sym-ai .metin{color:var(--ink,#1e293b);font-size:13px;line-height:1.35}
/* Detay kalem satırı (Flutter _kalemSatir) */
#symModal .sym-det-baslik{color:var(--ink,#1e293b);font-size:15px;font-weight:700;margin:10px 0 8px}
#symModal .sym-det-satir{display:flex;align-items:center;padding:12px;border-radius:12px;
  background:var(--card,#fff);border:1px solid var(--line,#e2e8f0);margin-bottom:8px}
body.koyu #symModal .sym-det-satir{background:#1b2235}
#symModal .sym-det-satir .sol{flex:4;min-width:0}
#symModal .sym-det-satir .malzeme{color:var(--ink,#1e293b);font-size:14px;font-weight:600}
#symModal .sym-det-satir .bilgi{color:var(--sub,#64748b);font-size:11.5px;margin-top:1px}
#symModal .sym-det-satir .sag{text-align:right;flex-shrink:0}
#symModal .sym-det-satir .fark-val{font-size:14px;font-weight:700}
#symModal .sym-det-satir .fark-tl{font-size:11px;margin-top:1px}
#symModal .sym-mod-loading{text-align:center;padding:40px;color:var(--sub,#64748b)}
</style>
@endpush

@section('content')
@php
  $malzemeler = $malzemeler ?? [];
  $sonSayimlar = $sonSayimlar ?? [];
  $duzenleyebilir = true; // web panelinde her zaman düzenlenebilir (API'de rol kontrolü yapılıyor)

  // Flutter _mik: tam sayıysa ondalıksız, değilse 2 hane
  $mik = function ($v) {
    $v = (float) $v;
    return $v == round($v) ? number_format($v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
  };
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . 'TL';
@endphp

<div id="sym">

  {{-- ===== Flutter _sonucGoster: kayıt sonrası özet dialog ===== --}}
  @if (session('sayim_sonuc'))
    @php
      $ss = session('sayim_sonuc');
      $sfm = (float) ($ss['toplam_fark_maliyet'] ?? 0);
      $srenk = $sfm < 0 ? 'var(--kirmizi)' : ($sfm > 0 ? 'var(--amber)' : 'var(--yesil)');
    @endphp
    <div class="sym-sonuc" style="border-color:color-mix(in srgb,{{ $srenk }} 45%,transparent)">
      <div class="baslik">Sayım kaydedildi</div>
      <div class="alt">{{ $ss['sayilan_kalem'] ?? 0 }} kalem sayıldı · {{ $ss['eksik'] ?? 0 }} eksik · {{ $ss['fazla'] ?? 0 }} fazla</div>
      <div class="fark" style="color:{{ $srenk }}">
        @if ($sfm < 0)
          Toplam açık: {{ $tl(abs($sfm)) }} (kayıp)
        @elseif ($sfm > 0)
          Toplam fazla: {{ $tl($sfm) }}
        @else
          Fark yok — tertemiz.
        @endif
      </div>
    </div>
  @endif

  {{-- ===== Hata flash ===== --}}
  @if (session('sayim_hata'))
    <div class="sym-hata">{{ session('sayim_hata') }}</div>
  @endif

  {{-- ===== Flutter üst mavi bilgi kutusu ===== --}}
  <div class="sym-info">
    <span class="material-icons-outlined">info</span>
    <p>Fiili sayımı girin. Sistem, "olması gereken" ile karşılaştırıp farkı (kayıp/fazla) çıkarır. Sadece saydıklarınızı girin.</p>
  </div>

  @if (!$malzemeler)
    <div style="padding:40px;text-align:center;color:var(--sub);background:var(--card);border-radius:12px;border:1px solid var(--line)">
      Stok takipli malzeme yok.
    </div>
  @else

    {{-- ===== Flutter arama TextField ===== --}}
    <div class="sym-ara-wrap">
      <span class="material-icons-outlined">search</span>
      <input id="symAra" type="text" placeholder="Malzeme ara" oninput="symFiltre()" autocomplete="off">
    </div>

    {{-- ===== Flutter malzeme listesi (form) ===== --}}
    <form method="POST" action="/sayim/kaydet" id="symForm">
      @csrf

      <div id="symListe">
        @foreach ($malzemeler as $m)
          @php $teorik = (float) ($m['teorik'] ?? 0); @endphp
          <div class="sym-satir" data-ad="{{ mb_strtolower($m['ad']) }}">
            {{-- Sol: ad + "Sistemde: X birim" --}}
            <div class="sym-ad">
              <div class="ad" title="{{ $m['ad'] }}">{{ $m['ad'] }}</div>
              <div class="teorik">Sistemde: {{ $mik($teorik) }} {{ $m['birim'] ?? '' }}</div>
            </div>
            {{-- Orta: sayılan giriş --}}
            <div class="sym-inp">
              <input type="number" step="any" min="0"
                     name="sayilan[{{ $m['malzeme_id'] }}]"
                     placeholder="Say"
                     data-teorik="{{ $teorik }}"
                     oninput="symFark(this)"
                     {{ $duzenleyebilir ? '' : 'disabled' }}>
            </div>
            {{-- Sağ: canlı fark —/+N/-N --}}
            <div class="sym-fark">—</div>
          </div>
        @endforeach
      </div>

      {{-- ===== Flutter bottomSheet: Sayımı Kaydet butonu ===== --}}
      <div class="sym-alt">
        <button type="submit" id="symKaydetBtn" {{ $duzenleyebilir ? '' : 'disabled' }}>
          <span class="material-icons-outlined" style="font-size:18px">save</span>
          Sayımı Kaydet (<span id="symGirilen">0</span> kalem)
        </button>
      </div>

    </form>

  @endif

  {{-- ===== Flutter SayimGecmisScreen: geçmiş kartları ===== --}}
  @if ($sonSayimlar)
    <div class="sym-sec-baslik">
      <div class="cubuk"></div>
      <span>Son Sayımlar</span>
    </div>
    <div class="sym-gecmis-grid" id="symGecmisGrid">
      @foreach ($sonSayimlar as $s)
        @php
          $gfm = (float) $s['fark_maliyet'];
          $grenk = $gfm < 0 ? 'var(--kirmizi)' : ($gfm > 0 ? 'var(--amber)' : 'var(--yesil)');
        @endphp
        <div class="sym-gecmis-kart" onclick="symDetayAc({{ $s['id'] }})">
          <div class="sol">
            <div class="tarih">{{ $s['tarih'] }}</div>
            <div class="alt">{{ $s['kalem'] }} kalem · {{ $s['eksik'] }} eksik</div>
          </div>
          <div class="tutar" style="color:{{ $grenk }}">
            {{ $gfm < 0 ? '-' . $tl(abs($gfm)) : ($gfm > 0 ? '+' . $tl($gfm) : '0') }}
          </div>
          <span class="material-icons-outlined">chevron_right</span>
        </div>
      @endforeach
    </div>
  @endif

</div>{{-- #sym --}}

{{-- ===== Flutter SayimDetayScreen: detay modal ===== --}}
<div id="symModal" onclick="if(event.target===this)symModalKapat()">
  <div class="sym-mod-wrap">
    <div class="sym-mod-header">
      <div class="sym-mod-title" id="symModTitle">Sayım Detayı</div>
      <button class="sym-mod-kapat" onclick="symModalKapat()" title="Kapat">
        <span class="material-icons-outlined">close</span>
      </button>
    </div>
    <div class="sym-mod-body" id="symModBody">
      <div class="sym-mod-loading">Yükleniyor…</div>
    </div>
  </div>
</div>

<script>
// ── Yardımcılar (Flutter _mik / _tl) ─────────────────────────────────────────
function _mik(v) {
  v = parseFloat(v) || 0;
  return Math.abs(v) === Math.round(Math.abs(v))
    ? String(Math.round(Math.abs(v)))
    : Math.abs(v).toFixed(2);
}
function _tlf(v) { return Math.round(Math.abs(parseFloat(v)||0)).toLocaleString('tr-TR') + 'TL'; }
function _farkRenk(f) {
  return f < -0.0001 ? 'var(--kirmizi)' : (f > 0.0001 ? 'var(--amber)' : 'var(--yesil)');
}

// ── Sayım satır fark hesabı (Flutter onChanged) ───────────────────────────────
function symFark(inp) {
  var satir = inp.closest('.sym-satir');
  if (!satir) return;
  var td    = satir.querySelector('.sym-fark');
  var t     = parseFloat(inp.getAttribute('data-teorik')) || 0;
  var v     = inp.value.trim();
  if (v === '') {
    td.textContent = '—';
    td.style.color = 'var(--sub)';
  } else {
    var f = (parseFloat(v.replace(',', '.')) || 0) - t;
    var r = Math.round(f * 10000) / 10000;
    var abs = Math.abs(r);
    var govde = (abs === Math.round(abs)) ? String(Math.round(abs)) : abs.toFixed(2);
    td.textContent = (r > 0.0001 ? '+' : (r < -0.0001 ? '-' : '')) + govde;
    td.style.color = _farkRenk(r);
  }
  symGirilenSay();
}

// ── Girilen kalem sayısını güncelle (Flutter _girilenSayi) ────────────────────
function symGirilenSay() {
  var n = 0;
  document.querySelectorAll('#symListe input[type=number]').forEach(function(inp) {
    if (inp.value.trim() !== '') n++;
  });
  var el = document.getElementById('symGirilen');
  if (el) el.textContent = n;
}

// ── Arama filtresi (Flutter onChanged ara) ────────────────────────────────────
function symFiltre() {
  var q = (document.getElementById('symAra').value || '').toLowerCase().trim();
  document.querySelectorAll('#symListe .sym-satir').forEach(function(satir) {
    satir.style.display = (!q || (satir.getAttribute('data-ad') || '').indexOf(q) !== -1) ? '' : 'none';
  });
}

// ── Detay modal aç (Flutter SayimDetayScreen) ─────────────────────────────────
function symDetayAc(id) {
  var modal = document.getElementById('symModal');
  var body  = document.getElementById('symModBody');
  var title = document.getElementById('symModTitle');
  title.textContent = 'Sayım Detayı';
  body.innerHTML    = '<div class="sym-mod-loading">Yükleniyor…</div>';
  modal.classList.add('acik');
  document.body.style.overflow = 'hidden';

  api('/api/patron/sayim-detay?id=' + id).then(function(res) {
    if (!res || res.ok !== 1) {
      body.innerHTML = '<div class="sym-mod-loading">Sayım bulunamadı.</div>';
      return;
    }
    title.textContent = 'Sayım — ' + (res.tarih || '');
    var fm   = parseFloat(res.toplam_fark_maliyet) || 0;
    var ozGrad = fm < 0
      ? 'linear-gradient(135deg,#F43F5E,#F59E0B)'
      : (fm > 0 ? 'linear-gradient(135deg,#F59E0B,#4F46E5)' : 'linear-gradient(135deg,#10B981,#4F46E5)');
    var ozEtiket = fm < 0 ? 'Toplam Açık (Kayıp)' : (fm > 0 ? 'Toplam Fazla' : 'Fark Yok');
    var ozDeger  = fm === 0 ? 'Tertemiz' : _tlf(Math.abs(fm));

    var html = '';

    // Flutter gradient özet kartı
    html += '<div class="sym-det-ozet" style="background:' + ozGrad + '">';
    html += '<div class="etiket">' + ozEtiket + '</div>';
    html += '<div class="deger">' + ozDeger + '</div>';
    html += '</div>';

    // Flutter AI yorumları
    var ai = res.ai || [];
    ai.forEach(function(a) {
      html += '<div class="sym-ai"><span class="emoji">✨</span><div class="metin">' + _esc(a.mesaj || '') + '</div></div>';
    });

    // Kalem listesi başlık
    html += '<div class="sym-det-baslik">Kalem Bazında Fark</div>';

    // Flutter _kalemSatir
    var kalemler = res.kalemler || [];
    kalemler.forEach(function(k) {
      var fark  = parseFloat(k.fark) || 0;
      var fmk   = parseFloat(k.fark_maliyet) || 0;
      var renk  = _farkRenk(fark);
      var farkStr = fark > 0.0001 ? '+' + _mik(fark) : (fark < -0.0001 ? '-' + _mik(fark) : _mik(fark));
      html += '<div class="sym-det-satir">';
      html += '<div class="sol"><div class="malzeme">' + _esc(k.malzeme || '') + '</div>';
      html += '<div class="bilgi">Sistem ' + _mik(k.teorik) + ' · Sayılan ' + _mik(k.sayilan) + ' ' + _esc(k.birim || '') + '</div></div>';
      html += '<div class="sag"><div class="fark-val" style="color:' + renk + '">' + farkStr + '</div>';
      if (Math.abs(fmk) >= 1) {
        html += '<div class="fark-tl" style="color:' + renk + '">' + (fmk < 0 ? '-' : '+') + _tlf(fmk) + '</div>';
      }
      html += '</div></div>';
    });

    body.innerHTML = html;
  }).catch(function() {
    body.innerHTML = '<div class="sym-mod-loading">Bağlantı hatası.</div>';
  });
}

function symModalKapat() {
  document.getElementById('symModal').classList.remove('acik');
  document.body.style.overflow = '';
}

// XSS koruması
function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ESC ile kapat
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') symModalKapat();
});
</script>
@endsection

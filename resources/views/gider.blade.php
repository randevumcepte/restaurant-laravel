@extends('layout.app')
@section('title', 'Giderler')
@section('rk_ikon', 'receipt_long')
@section('rk_geri', '/stok')
@section('baslik', 'Giderler')

@push('head')
<style>
#gd{
  --mor1:#7C3AED;--mor2:#A855F7;--mavi:#4F46E5;--mavi2:#6366F1;
  --yesil:#10B981;--yesil2:#34D399;--kirmizi:#F43F5E;--kirmizi2:#FB7185;
  --amber:#F59E0B;--amber2:#FBBF24;--cyan:#06B6D4;
  --card:#fff;--card2:#f8fafc;--card3:#f1f5f9;--bg:#f6f7fb;
  --ink:#0f172a;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;
  --sh1:0 2px 8px rgba(15,23,42,.06);--sh2:0 8px 24px rgba(15,23,42,.08);
}
body.koyu #gd{
  --card:#161d2e;--card2:#1b2235;--card3:#222a40;--bg:#0f1623;
  --ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;
  --sh1:0 2px 8px rgba(0,0,0,.3);--sh2:0 8px 24px rgba(0,0,0,.35);
}
#gd{background:var(--bg);min-height:calc(100vh - 60px)}
#gd *{box-sizing:border-box}
#gd .pano{width:100%;padding:20px 22px 36px}

/* Flash */
#gd .flash{border-radius:14px;padding:12px 16px;margin-bottom:14px;font-size:13.5px;font-weight:600;display:flex;align-items:center;gap:9px}
#gd .flash.ok{background:linear-gradient(135deg,rgba(16,185,129,.14),rgba(52,211,153,.08));color:#047857;border:1px solid rgba(16,185,129,.3)}
#gd .flash.err{background:linear-gradient(135deg,rgba(244,63,94,.14),rgba(251,113,133,.08));color:#be123c;border:1px solid rgba(244,63,94,.3)}

/* Ay navigasyon üst bar */
#gd .ustbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
#gd .ay-nav{display:inline-flex;align-items:center;gap:4px;background:var(--card);border:1.5px solid var(--line);border-radius:14px;padding:4px;box-shadow:var(--sh1)}
#gd .ay-btn{width:36px;height:36px;border-radius:10px;background:transparent;border:0;cursor:pointer;color:var(--mor1);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background .14s}
#gd .ay-btn:hover{background:color-mix(in srgb,var(--mor1) 10%,transparent)}
#gd .ay-btn.devre{opacity:.3;pointer-events:none;color:var(--sub2)}
#gd .ay-metin{min-width:150px;text-align:center;font-weight:800;font-size:14.5px;color:var(--ink);padding:0 6px}
#gd .sp{flex:1}
#gd .ekle-btn{display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--kirmizi),#E11D48);color:#fff;border:0;border-radius:12px;padding:11px 20px;font-size:14px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(244,63,94,.3);transition:transform .14s,box-shadow .14s}
#gd .ekle-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(244,63,94,.4)}
#gd .ekle-btn .material-icons-outlined{font-size:19px}

/* HERO */
#gd .hero{background:linear-gradient(135deg,#DC2626 0%,#9F1239 100%);border-radius:22px;padding:24px 26px;margin-bottom:16px;color:#fff;position:relative;overflow:hidden;box-shadow:0 12px 32px rgba(220,38,38,.25)}
#gd .hero::before{content:'';position:absolute;top:-100px;right:-80px;width:280px;height:280px;background:radial-gradient(circle,rgba(255,255,255,.15),transparent 70%);border-radius:50%;pointer-events:none}
#gd .hero::after{content:'';position:absolute;bottom:-60px;left:20%;width:220px;height:220px;background:radial-gradient(circle,rgba(255,255,255,.1),transparent 70%);border-radius:50%;pointer-events:none}
#gd .hero-grid{display:grid;grid-template-columns:1fr auto;gap:22px;align-items:center;position:relative}
#gd .hero .lbl{font-size:12.5px;font-weight:700;color:rgba(255,255,255,.85);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px}
#gd .hero .tutar{font-size:42px;font-weight:900;letter-spacing:-1px;line-height:1}
#gd .hero .alt{font-size:13px;color:rgba(255,255,255,.8);margin-top:7px;font-weight:600}
#gd .hero-chips{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;position:relative;z-index:1;min-width:280px}
#gd .hero-chip{background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:13px;padding:11px 14px;backdrop-filter:blur(10px)}
#gd .hero-chip .l{font-size:10.5px;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;gap:5px}
#gd .hero-chip .v{font-size:16px;font-weight:900;color:#fff;line-height:1;margin-top:4px;letter-spacing:-.3px}

/* Kategori filtre chip'leri */
#gd .katf{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:14px;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin}
#gd .katf::-webkit-scrollbar{height:3px}
#gd .katf::-webkit-scrollbar-thumb{background:var(--line);border-radius:3px}
#gd .katf .c{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:999px;background:var(--card);border:1.5px solid var(--line);font-size:12.5px;font-weight:700;color:var(--sub);cursor:pointer;transition:all .14s;user-select:none;white-space:nowrap}
#gd .katf .c:hover{color:var(--ink);border-color:var(--sub2)}
#gd .katf .c.aktif{border-color:transparent;color:#fff}
#gd .katf .c .material-icons-outlined{font-size:15px}

/* Liste */
#gd .liste{display:flex;flex-direction:column;gap:9px}
#gd .satir{display:grid;grid-template-columns:auto 1fr auto auto;gap:12px;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 15px;transition:transform .12s,box-shadow .12s,border-color .12s;position:relative;overflow:hidden}
#gd .satir:hover{transform:translateY(-1px);box-shadow:var(--sh2);border-color:color-mix(in srgb,var(--kirmizi) 20%,var(--line))}
#gd .satir .strip{position:absolute;left:0;top:0;bottom:0;width:3px}
#gd .ikon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#gd .ikon .material-icons-outlined{font-size:20px}
#gd .bilgi{min-width:0}
#gd .bilgi .ad{font-size:14px;font-weight:800;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2}
#gd .bilgi .alt2{font-size:11.5px;color:var(--sub);margin-top:4px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
#gd .bilgi .alt2 .katroz{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:700}
#gd .bilgi .alt2 .trh{display:inline-flex;align-items:center;gap:3px;font-size:11px;color:var(--sub2);font-weight:600}
#gd .bilgi .alt2 .trh .material-icons-outlined{font-size:13px}
#gd .tutar{font-size:16px;font-weight:900;color:var(--ink);white-space:nowrap;letter-spacing:-.3px}
#gd .sil{width:34px;height:34px;border-radius:10px;border:0;background:color-mix(in srgb,var(--kirmizi) 8%,transparent);cursor:pointer;color:var(--kirmizi);display:flex;align-items:center;justify-content:center;transition:background .14s,transform .14s}
#gd .sil:hover{background:var(--kirmizi);color:#fff;transform:scale(1.05)}
#gd .sil .material-icons-outlined{font-size:17px}
#gd .sil-ph{width:34px;flex-shrink:0}

/* Boş */
#gd .bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:60px 20px;text-align:center;color:var(--sub)}
#gd .bos .ic{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,color-mix(in srgb,var(--kirmizi) 15%,var(--card3)),color-mix(in srgb,var(--mavi) 10%,var(--card3)));margin:0 auto 14px;display:flex;align-items:center;justify-content:center}
#gd .bos .ic .material-icons-outlined{font-size:34px;color:var(--kirmizi)}
#gd .bos .b{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:5px}
#gd .bos .a{font-size:12.5px;color:var(--sub)}

/* Modal (bottom sheet masaüstünde ortalanmış) */
#gdModal{display:none;position:fixed;inset:0;z-index:9999;align-items:flex-end;justify-content:center;background:rgba(10,15,30,.55);backdrop-filter:blur(2px)}
#gdModal.acik{display:flex;animation:gdFi .2s ease}
@keyframes gdFi{from{opacity:0}to{opacity:1}}
#gdModal .sheet{background:var(--card);border-radius:22px 22px 0 0;padding:16px 20px calc(20px + env(safe-area-inset-bottom));width:100%;max-width:560px;box-shadow:0 -8px 30px rgba(0,0,0,.25);transform:translateY(20px);animation:gdUp .25s cubic-bezier(.34,1.56,.64,1) forwards;max-height:94vh;overflow-y:auto}
@keyframes gdUp{to{transform:translateY(0)}}
#gdModal .handle{width:42px;height:4px;background:var(--line);border-radius:2px;margin:0 auto 16px}
#gdModal .mb{display:flex;align-items:center;gap:12px;margin-bottom:18px}
#gdModal .mi{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--kirmizi),#E11D48);color:#fff;display:flex;align-items:center;justify-content:center}
#gdModal .mi .material-icons-outlined{font-size:22px}
#gdModal .mt{font-size:16px;font-weight:800;color:var(--ink)}
#gdModal .alan{margin-bottom:12px}
#gdModal .lbl{display:block;font-size:11.5px;font-weight:700;color:var(--sub);margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
#gdModal .inp,#gdModal .sel{width:100%;height:44px;border:1.5px solid var(--line);border-radius:11px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;padding:0 13px;outline:none;transition:border-color .15s,box-shadow .15s;box-sizing:border-box}
#gdModal .inp:focus,#gdModal .sel:focus{border-color:var(--mor1);box-shadow:0 0 0 3px rgba(124,58,237,.14)}
#gdModal .katgrid{display:grid;grid-template-columns:repeat(5,1fr);gap:7px;margin-bottom:4px}
#gdModal .katop{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;padding:11px 4px;border-radius:11px;background:var(--card2);border:1.5px solid var(--line);cursor:pointer;transition:all .14s;color:var(--sub);user-select:none}
#gdModal .katop .material-icons-outlined{font-size:19px}
#gdModal .katop .nm{font-size:11px;font-weight:700;line-height:1}
#gdModal .katop:hover{color:var(--ink);border-color:var(--sub2)}
#gdModal .katop.sec{border-color:transparent;color:#fff;box-shadow:0 4px 10px rgba(0,0,0,.1)}
#gdModal .kaydet{width:100%;border:0;border-radius:12px;background:linear-gradient(135deg,var(--kirmizi),#E11D48);color:#fff;font-size:15px;font-weight:800;padding:14px;cursor:pointer;transition:transform .14s,box-shadow .14s;margin-top:8px;display:flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 4px 12px rgba(244,63,94,.3)}
#gdModal .kaydet:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(244,63,94,.4)}
#gdModal .kaydet .material-icons-outlined{font-size:19px}

/* Responsive */
@media(max-width:900px){
  #gd .pano{padding:16px 14px 30px}
  #gd .hero-grid{grid-template-columns:1fr}
  #gd .hero .tutar{font-size:34px}
  #gd .hero-chips{min-width:0;width:100%}
}
@media(max-width:640px){
  #gd .ustbar .sp{display:none}
  #gd .ustbar{gap:8px}
  #gd .ekle-btn{flex:1;justify-content:center}
  #gd .hero .tutar{font-size:28px}
  #gd .satir{grid-template-columns:auto 1fr auto;row-gap:8px}
  #gd .satir .sil{grid-column:3}
  #gd .satir .tutar{grid-column:1 / -1;text-align:left;padding-left:52px}
  #gdModal .katgrid{grid-template-columns:repeat(3,1fr)}
}
@media(max-width:420px){
  #gd .ay-metin{min-width:120px;font-size:13px}
  #gd .ekle-btn{padding:10px 14px;font-size:13px}
}
</style>
@endpush

@section('content')
@php
  $katAd   = ['kira' => 'Kira', 'fatura' => 'Fatura', 'malzeme' => 'Malzeme', 'maas' => 'Maaş', 'vergi' => 'Vergi', 'diger' => 'Diğer'];
  $katIkon = ['kira' => 'home_work', 'fatura' => 'bolt', 'malzeme' => 'inventory_2', 'maas' => 'groups', 'vergi' => 'account_balance', 'diger' => 'more_horiz'];
  $katHex  = ['kira' => '#D97706', 'fatura' => '#4F46E5', 'malzeme' => '#10B981', 'maas' => '#7C3AED', 'vergi' => '#F43F5E', 'diger' => '#64748B'];
  $aylar   = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

  $yil     = (int) substr($ay, 0, 4);
  $aySayi  = (int) substr($ay, 5, 2);
  $ayMetin = ($aylar[$aySayi] ?? '') . ' ' . $yil;

  $oncekiTs  = mktime(0, 0, 0, $aySayi - 1, 1, $yil);
  $sonrakiTs = mktime(0, 0, 0, $aySayi + 1, 1, $yil);
  $onceki    = date('Y-m', $oncekiTs);
  $sonraki   = date('Y-m', $sonrakiTs);
  $sonrakiVar = $sonrakiTs <= mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));

  $kats = collect($kategoriler)->sortDesc();
  $tl   = fn($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
  $sayi = count($giderler);
  $enBuyukKat = $kats->keys()->first();
@endphp

<div id="gd">
<div class="pano">

  @if (session('ok'))
    <div class="flash ok"><span class="material-icons-outlined" style="font-size:20px">check_circle</span>{{ session('ok') }}</div>
  @endif
  @if (session('hata'))
    <div class="flash err"><span class="material-icons-outlined" style="font-size:20px">error</span>{{ session('hata') }}</div>
  @endif

  {{-- ÜST BAR: ay nav + ekle --}}
  <div class="ustbar">
    <div class="ay-nav">
      <a href="/gider?ay={{ $onceki }}" class="ay-btn" title="Önceki ay">
        <span class="material-icons-outlined" style="font-size:21px">chevron_left</span>
      </a>
      <span class="ay-metin">{{ $ayMetin }}</span>
      <a href="{{ $sonrakiVar ? '/gider?ay='.$sonraki : '#' }}" class="ay-btn {{ $sonrakiVar ? '' : 'devre' }}" title="Sonraki ay">
        <span class="material-icons-outlined" style="font-size:21px">chevron_right</span>
      </a>
    </div>
    <div class="sp"></div>
    <button type="button" class="ekle-btn" onclick="gdModalAc()">
      <span class="material-icons-outlined">add</span>Gider Ekle
    </button>
  </div>

  {{-- HERO ÖZET --}}
  <div class="hero">
    <div class="hero-grid">
      <div>
        <div class="lbl">Bu Ay Toplam Gider</div>
        <div class="tutar">{{ $tl($toplam) }}</div>
        <div class="alt">
          @if($sayi>0) {{ $sayi }} kayıt @else Henüz kayıt yok @endif
          @if($enBuyukKat) · en yüksek: <b style="color:#fff">{{ $katAd[$enBuyukKat] ?? $enBuyukKat }}</b> @endif
        </div>
      </div>
      @if($kats->isNotEmpty())
        <div class="hero-chips">
          @foreach($kats->take(5) as $k => $tutarKat)
            <div class="hero-chip">
              <div class="l"><span class="material-icons-outlined" style="font-size:13px">{{ $katIkon[$k] ?? 'more_horiz' }}</span>{{ $katAd[$k] ?? ucfirst($k) }}</div>
              <div class="v">{{ $tl($tutarKat) }}</div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- KATEGORİ FİLTRE --}}
  @if($kats->isNotEmpty())
    <div class="katf" id="gdKatF">
      <div class="c aktif" data-kat="hepsi" style="background:var(--ink);color:#fff;border-color:var(--ink)" onclick="gdFiltre(this)">
        <span class="material-icons-outlined">apps</span>Tümü <b style="margin-left:3px;opacity:.8">{{ $sayi }}</b>
      </div>
      @foreach($kats as $k => $tt)
        @php $h = $katHex[$k] ?? '#64748B'; $knt = collect($giderler)->where('kategori',$k)->count(); @endphp
        <div class="c" data-kat="{{ $k }}" style="--kh:{{ $h }}" onclick="gdFiltre(this)">
          <span class="material-icons-outlined" style="color:{{ $h }}">{{ $katIkon[$k] ?? 'more_horiz' }}</span>
          {{ $katAd[$k] ?? ucfirst($k) }} <b style="margin-left:3px;opacity:.65">{{ $knt }}</b>
        </div>
      @endforeach
    </div>
  @endif

  {{-- LİSTE --}}
  @if (empty($giderler) || count($giderler) === 0)
    <div class="bos">
      <div class="ic"><span class="material-icons-outlined">receipt_long</span></div>
      <div class="b">Bu ay gider kaydı yok</div>
      <div class="a">Yukarıdaki "Gider Ekle" butonuyla ekle.</div>
    </div>
  @else
    <div class="liste" id="gdListe">
      @foreach ($giderler as $g)
        @php
          $kat  = $g->kategori;
          $hex  = $katHex[$kat]  ?? '#94A3B8';
          $ikon = $katIkon[$kat] ?? 'more_horiz';
          $ad   = $katAd[$kat]   ?? ucfirst($kat);
          $acik = $g->aciklama ? $g->aciklama : $ad;
        @endphp
        <div class="satir" data-kat="{{ $kat }}">
          <div class="strip" style="background:{{ $hex }}"></div>
          <div class="ikon" style="background:color-mix(in srgb,{{ $hex }} 15%,var(--card3))">
            <span class="material-icons-outlined" style="color:{{ $hex }}">{{ $ikon }}</span>
          </div>
          <div class="bilgi">
            <div class="ad">{{ $acik }}</div>
            <div class="alt2">
              <span class="katroz" style="color:{{ $hex }};background:color-mix(in srgb,{{ $hex }} 12%,transparent)">
                {{ $ad }}
              </span>
              <span class="trh"><span class="material-icons-outlined">event</span>{{ $g->tarih }}</span>
            </div>
          </div>
          <div class="tutar">{{ $tl($g->tutar) }}</div>
          @if ($kat !== 'maas')
            <form method="POST" action="/gider/sil"
                  onsubmit="return rkOnayForm(this, 'Bu gideri silmek istediğinize emin misiniz?', 'var(--kirmizi)', 'Sil')"
                  style="margin:0">
              @csrf
              <input type="hidden" name="id" value="{{ $g->id }}">
              <input type="hidden" name="ay" value="{{ $ay }}">
              <button type="submit" class="sil" title="Sil"><span class="material-icons-outlined">delete</span></button>
            </form>
          @else
            <span class="sil-ph"></span>
          @endif
        </div>
      @endforeach
    </div>
    <div class="bos" id="gdBosFiltre" style="display:none;margin-top:10px">
      <div class="ic"><span class="material-icons-outlined">search_off</span></div>
      <div class="b">Bu kategoride kayıt yok</div>
      <div class="a">Tümü sekmesine dönebilirsin.</div>
    </div>
  @endif

</div>
</div>

{{-- GİDER EKLE MODAL --}}
<div id="gdModal" onclick="if(event.target===this)gdModalKapat()">
  <div class="sheet">
    <div class="handle"></div>
    <div class="mb">
      <div class="mi"><span class="material-icons-outlined">add</span></div>
      <div class="mt">Yeni Gider</div>
    </div>
    <form method="POST" action="/gider/ekle" id="gdEkleForm">
      @csrf
      <input type="hidden" name="ay" value="{{ $ay }}">
      <input type="hidden" name="kategori" id="gdKatInp" value="kira">

      <div class="alan">
        <label class="lbl">Kategori</label>
        <div class="katgrid">
          @php $katMod = ['kira'=>'Kira','fatura'=>'Fatura','malzeme'=>'Malzeme','vergi'=>'Vergi','diger'=>'Diğer']; @endphp
          @foreach($katMod as $kk => $kad)
            @php $kh = $katHex[$kk] ?? '#64748B'; $ki = $katIkon[$kk] ?? 'more_horiz'; @endphp
            <div class="katop" id="gdKat_{{ $kk }}" data-k="{{ $kk }}" data-h="{{ $kh }}" onclick="gdKatSec('{{ $kk }}')">
              <span class="material-icons-outlined" style="color:{{ $kh }}">{{ $ki }}</span>
              <span class="nm">{{ $kad }}</span>
            </div>
          @endforeach
        </div>
      </div>

      <div class="alan">
        <label class="lbl">Tutar (₺)</label>
        <input type="number" name="tutar" id="gdTutar" step="0.01" min="0.01" placeholder="örn. 1500" required inputmode="decimal" autofocus class="inp">
      </div>

      <div class="alan">
        <label class="lbl">Tarih</label>
        <input type="date" name="tarih" id="gdTarih" value="{{ date('Y-m-d') }}" class="inp">
      </div>

      <div class="alan">
        <label class="lbl">Açıklama (opsiyonel)</label>
        <input type="text" name="aciklama" id="gdAciklama" maxlength="200" placeholder="örn. Eylül kirası" class="inp">
      </div>

      <button type="submit" class="kaydet"><span class="material-icons-outlined">check_circle</span>Kaydet</button>
    </form>
  </div>
</div>

<script>
function gdModalAc(){
  document.getElementById('gdModal').classList.add('acik');
  setTimeout(()=>{ var t=document.getElementById('gdTutar'); if(t) t.focus(); }, 150);
  gdKatSec('kira');
}
function gdModalKapat(){ document.getElementById('gdModal').classList.remove('acik'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape') gdModalKapat(); });

function gdKatSec(k){
  document.getElementById('gdKatInp').value = k;
  document.querySelectorAll('.katop').forEach(el=>{
    var is = (el.dataset.k===k);
    el.classList.toggle('sec', is);
    if(is){ el.style.background = el.dataset.h; }
    else { el.style.background = ''; }
  });
}

function gdFiltre(el){
  document.querySelectorAll('#gdKatF .c').forEach(c=>{
    c.classList.remove('aktif');
    c.style.background = '';
    c.style.color = '';
    c.style.borderColor = '';
  });
  el.classList.add('aktif');
  var kat = el.dataset.kat;
  if (kat === 'hepsi') {
    el.style.background = 'var(--ink)'; el.style.color = '#fff'; el.style.borderColor = 'var(--ink)';
  } else {
    var h = el.style.getPropertyValue('--kh');
    el.style.background = h; el.style.color = '#fff'; el.style.borderColor = h;
  }
  var gorunen = 0, toplam = 0;
  document.querySelectorAll('#gdListe .satir').forEach(s=>{
    var ok = (kat==='hepsi' || s.dataset.kat===kat);
    s.style.display = ok ? '' : 'none';
    if (ok) gorunen++;
    toplam++;
  });
  var bos = document.getElementById('gdBosFiltre');
  if (bos) bos.style.display = (gorunen===0 && toplam>0) ? 'block' : 'none';
}

document.getElementById('gdEkleForm')?.addEventListener('submit', function(e){
  var t = parseFloat((document.getElementById('gdTutar').value||'').replace(',', '.'));
  if (!t || t <= 0) {
    e.preventDefault();
    rkBilgi('Geçerli bir tutar girin.', 'er');
    document.getElementById('gdTutar').focus();
  }
});
</script>
@endsection

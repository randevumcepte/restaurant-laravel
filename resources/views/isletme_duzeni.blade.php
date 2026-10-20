@extends('layout.app')
@section('title', 'İşletme Düzeni')
@section('rk_ikon', 'table_restaurant')
@section('baslik', 'İşletme Düzeni')
@section('rk_alt', 'Bölge → Masa → Garson → Kasa · hepsi tek yerden')

@section('content')
@php
  $bolgeler = $bolgeler ?? collect();
  $masalar = $masalar ?? collect();
  $garsonlar = $garsonlar ?? collect();
  $kasalar = $kasalar ?? collect();
  $personeller = $personeller ?? collect();
  $coklu = $coklu ?? false;
  $bolgeAd = $bolgeler->pluck('ad', 'id');
  $masaByBolge = $masalar->groupBy(fn($m) => $m->bolge_id ?? 0);
  $adimRenk = ['#0EA5E9', '#F6CE63', '#10B981', '#D97706'];
@endphp

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

{{-- Üst şerit: akış özeti + salon şeması linki --}}
<div class="rk-card" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:14px 16px;margin-bottom:18px">
  <span class="material-icons-outlined" style="color:var(--mor1);font-size:24px">insights</span>
  <div style="flex:1;min-width:220px">
    <div style="font-weight:800;color:var(--ink);font-size:14.5px">Kurulum akışı</div>
    <div class="rk-sub2" style="font-size:12px">Sırayla: önce bölgeleri oluştur, masaları bölgelere yerleştir, garsonları masalara/bölgelere ata, sonra (varsa) çoklu kasayı düzenle.</div>
  </div>
  <a href="/salon-sema" class="rk-btn bos" style="padding:9px 14px"><span class="material-icons-outlined" style="font-size:17px;color:#0EA5E9">grid_on</span> Salon Şeması (görsel kroki)</a>
</div>

{{-- ============ ADIM 1 — BÖLGELER ============ --}}
<div class="rk-bolum" style="margin-top:6px">
  <span class="rk-bolum-sayi" style="background:{{ $adimRenk[0] }}">1</span>
  <span class="b">Bölgeler</span>
  <span class="rk-rozet" style="color:{{ $adimRenk[0] }};background:color-mix(in srgb,{{ $adimRenk[0] }} 13%,transparent);border-color:color-mix(in srgb,{{ $adimRenk[0] }} 40%,transparent)">{{ $bolgeler->count() }}</span>
</div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Salonun alanları: Bahçe, Teras, İç Salon, Üst Kat… Masalar bu bölgelere yerleşir.</div>
<div class="rk-card" style="margin-bottom:10px">
  <form method="POST" action="/isletme-duzeni/bolge-ekle" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    @csrf
    <div style="flex:1;min-width:220px">
      <label class="rk-lbl" style="margin-top:0">Yeni bölge adı</label>
      <input type="text" name="ad" maxlength="60" required placeholder='Örn. "Bahçe", "Teras", "İç Salon"' class="rk-inp">
    </div>
    <button type="submit" class="rk-btn" style="background:{{ $adimRenk[0] }};padding:11px 18px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Bölge Ekle</button>
  </form>
</div>
@if ($bolgeler->count())
  <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;margin-bottom:22px">
    @foreach ($bolgeler as $b)
      @php $masaSay = ($masaByBolge[$b->id] ?? collect())->count(); @endphp
      <div class="rk-card" style="padding:14px">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
          <span class="material-icons-outlined" style="color:{{ $adimRenk[0] }};font-size:20px">deck</span>
          <div style="flex:1;font-weight:800;color:var(--ink)">{{ $b->ad }}</div>
          <span class="rk-rozet" style="color:var(--sub2);background:var(--card2)">{{ $masaSay }} masa</span>
        </div>
        <form method="POST" action="/isletme-duzeni/bolge-duzenle" style="display:flex;gap:6px;margin-bottom:6px">
          @csrf<input type="hidden" name="id" value="{{ $b->id }}">
          <input type="text" name="ad" value="{{ $b->ad }}" maxlength="60" class="rk-inp" style="flex:1;padding:7px 9px">
          <button class="rk-btn bos" style="padding:7px 10px" title="Kaydet"><span class="material-icons-outlined" style="font-size:15px">save</span></button>
        </form>
        <form method="POST" action="/isletme-duzeni/bolge-sil" onsubmit="return confirm('{{ $b->ad }} bölgesi silinsin mi?')">
          @csrf<input type="hidden" name="id" value="{{ $b->id }}">
          <button class="rk-btn bos" style="width:100%;padding:7px;color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 30%,transparent)"><span class="material-icons-outlined" style="font-size:15px">delete_outline</span> Sil</button>
        </form>
      </div>
    @endforeach
  </div>
@else
  <div class="rk-card" style="text-align:center;padding:26px;margin-bottom:22px"><div class="rk-sub">Henüz bölge yok. Yukarıdan ekleyin.</div></div>
@endif

{{-- ============ ADIM 2 — MASALAR ============ --}}
<div class="rk-bolum">
  <span class="rk-bolum-sayi" style="background:{{ $adimRenk[1] }}">2</span>
  <span class="b">Masalar</span>
  <span class="rk-rozet" style="color:#B45309;background:color-mix(in srgb,{{ $adimRenk[1] }} 22%,transparent);border-color:color-mix(in srgb,{{ $adimRenk[1] }} 55%,transparent)">{{ $masalar->count() }}</span>
</div>
<div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:2px 0 12px">
  <div class="rk-sub2" style="font-size:12px;flex:1;min-width:220px">Masa ekle ve bölgesini seç. Her masaya <b>kalıcı QR otomatik</b> oluşur (<span class="material-icons-outlined" style="font-size:13px;color:var(--yesil);vertical-align:-2px">qr_code_2</span> ile göster/yazdır). Silinen masa arşive gider, <b>QR korunur</b>.</div>
  <a href="/masa-yonetim" class="rk-btn bos" style="padding:7px 12px;font-size:12.5px"><span class="material-icons-outlined" style="font-size:16px;color:#B45309">qr_code_2</span> QR Afişleri / Arşiv</a>
</div>
<div class="rk-card" style="margin-bottom:10px">
  <form method="POST" action="/masa-yonetim/ekle" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    @csrf
    <div style="flex:1;min-width:150px">
      <label class="rk-lbl" style="margin-top:0">Masa adı</label>
      <input type="text" name="ad" maxlength="40" placeholder='Örn. "Masa 1", "B4"' class="rk-inp">
    </div>
    <div style="width:110px">
      <label class="rk-lbl" style="margin-top:0">Kapasite</label>
      <input type="number" name="kapasite" min="1" max="50" value="4" class="rk-inp">
    </div>
    <div style="min-width:160px">
      <label class="rk-lbl" style="margin-top:0">Bölge</label>
      <select name="bolge_id" class="rk-inp">
        @foreach ($bolgeler as $b)<option value="{{ $b->id }}">{{ $b->ad }}</option>@endforeach
      </select>
    </div>
    <button type="submit" class="rk-btn" style="background:#B45309;padding:11px 18px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Masa Ekle</button>
  </form>
  @if (!$bolgeler->count())<div class="rk-flash err" style="margin:10px 0 0">Önce en az bir bölge ekleyin.</div>@endif
</div>
@foreach ($masaByBolge as $bid => $grup)
  <div class="rk-card" style="margin-bottom:10px">
    <div class="rk-bolum" style="margin-bottom:10px"><span class="cubuk" style="background:{{ $adimRenk[1] }}"></span><span class="b">{{ $bolgeAd[$bid] ?? 'Bölgesiz' }}</span><span class="rk-rozet" style="color:var(--sub2);background:var(--card2)">{{ $grup->count() }}</span></div>
    <div style="display:flex;flex-wrap:wrap;gap:8px">
      @foreach ($grup as $m)
        @php $qrurl = !empty($m->qr_token) ? url('/m/' . $m->qr_token) : null; @endphp
        <div style="display:flex;align-items:center;gap:6px;background:var(--card2);border:1px solid var(--line);border-radius:10px;padding:6px 8px">
          <span class="material-icons-outlined" style="font-size:16px;color:#B45309">{{ ($m->sekil ?? 'kare')==='yuvarlak' ? 'circle' : 'crop_square' }}</span>
          <span style="font-weight:700;color:var(--ink);font-size:13px">{{ $m->ad }}</span>
          <span class="rk-sub2" style="font-size:11px">({{ (int)($m->kapasite ?? 0) }} kişi)</span>
          <button type="button" class="rk-btn bos" style="padding:3px 7px;margin-left:2px" onclick="masaQr(@js($m->ad), @js($qrurl))" title="{{ $qrurl ? 'QR kodu göster / yazdır' : 'QR henüz yok' }}">
            <span class="material-icons-outlined" style="font-size:15px;color:{{ $qrurl ? 'var(--yesil)' : 'var(--amber)' }}">qr_code_2</span>
          </button>
          <form method="POST" action="/isletme-duzeni/masa-bolge" style="margin:0 0 0 4px">
            @csrf<input type="hidden" name="id" value="{{ $m->id }}">
            <select name="bolge_id" class="rk-inp" style="padding:4px 6px;font-size:12px;min-width:92px" onchange="this.form.requestSubmit()" title="Bölge değiştir">
              @foreach ($bolgeler as $b)<option value="{{ $b->id }}" {{ (int)$bid===(int)$b->id?'selected':'' }}>{{ $b->ad }}</option>@endforeach
            </select>
          </form>
          <form method="POST" action="/masa-yonetim/sil" onsubmit="return confirm('{{ $m->ad }} arşive taşınsın mı? (QR korunur)')" style="margin:0">
            @csrf<input type="hidden" name="id" value="{{ $m->id }}">
            <button class="rk-btn bos" style="padding:3px 6px;color:var(--kirmizi);border:none" title="Arşive taşı"><span class="material-icons-outlined" style="font-size:15px">delete_outline</span></button>
          </form>
        </div>
      @endforeach
    </div>
  </div>
@endforeach
<div style="height:14px"></div>

{{-- ============ ADIM 3 — GARSON ATAMASI ============ --}}
<div class="rk-bolum">
  <span class="rk-bolum-sayi" style="background:{{ $adimRenk[2] }}">3</span>
  <span class="b">Garson Ataması</span>
</div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Her garsonun hangi <b>bölge</b> ve/veya <b>masalara</b> bakacağını seç. Bölge seçersen o bölgenin tüm masaları dahildir. Hiç seçmezsen tüm masalara bakar.</div>
@if ($garsonlar->count() && $masalar->count())
  @foreach ($garsonlar as $g)
    <div class="rk-card" style="margin-bottom:10px">
      <form method="POST" action="/isletme-duzeni/garson-ata">
        @csrf<input type="hidden" name="personel_id" value="{{ $g->id }}">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <span class="material-icons-outlined" style="color:{{ $adimRenk[2] }};font-size:22px">room_service</span>
          <div style="flex:1;font-weight:800;color:var(--ink)">{{ $g->ad }} <span class="rk-sub2" style="font-weight:500;font-size:12px">· {{ ucfirst($g->rol ?? '') }}</span></div>
          <button type="submit" class="rk-btn" style="background:{{ $adimRenk[2] }};padding:8px 14px"><span class="material-icons-outlined" style="color:#fff;font-size:16px">save</span> Kaydet</button>
        </div>
        @foreach ($bolgeler as $b)
          @php $grupM = $masaByBolge[$b->id] ?? collect(); $bSecili = in_array((int)$b->id, $g->bolge_idler ?? []); @endphp
          <div style="border:1px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;color:var(--ink);font-size:13.5px">
              <input type="checkbox" name="bolge_idler[]" value="{{ $b->id }}" {{ $bSecili ? 'checked' : '' }} onchange="rkBolgeTog(this)">
              <span class="material-icons-outlined" style="font-size:17px;color:{{ $adimRenk[0] }}">deck</span> {{ $b->ad }} <span class="rk-sub2" style="font-weight:500;font-size:11px">(tüm bölge)</span>
            </label>
            @if ($grupM->count())
              <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;padding-left:4px" data-bolge="{{ $b->id }}">
                @foreach ($grupM as $m)
                  <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px;{{ $bSecili ? 'opacity:.5' : '' }}">
                    <input type="checkbox" name="masa_idler[]" value="{{ $m->id }}" {{ in_array((int)$m->id, $g->masa_idler ?? []) ? 'checked' : '' }} {{ $bSecili ? 'disabled' : '' }}> {{ $m->ad }}
                  </label>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </form>
    </div>
  @endforeach
@else
  <div class="rk-card" style="text-align:center;padding:24px"><div class="rk-sub">Garson ataması için önce masa ve garson (personel) ekleyin.</div></div>
@endif
<div style="height:14px"></div>

{{-- ============ ADIM 4 — KASA ============ --}}
<div class="rk-bolum">
  <span class="rk-bolum-sayi" style="background:{{ $adimRenk[3] }}">4</span>
  <span class="b">Kasa Noktaları</span>
  <span class="rk-rozet" style="color:{{ $adimRenk[3] }};background:color-mix(in srgb,{{ $adimRenk[3] }} 13%,transparent);border-color:color-mix(in srgb,{{ $adimRenk[3] }} 40%,transparent)">{{ $kasalar->count() }}</span>
</div>
<div class="rk-sub2" style="font-size:12px;margin:2px 0 12px">Birden fazla kasan varsa ekle; her kasaya <b>personel</b> ve/veya <b>masa</b> ata. Kasa eklemezsen tek-havuz (tek çekmece) çalışır.</div>
<div class="rk-card" style="margin-bottom:10px">
  <form method="POST" action="/kasalar/ekle" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
    @csrf
    <div style="flex:1;min-width:200px">
      <label class="rk-lbl" style="margin-top:0">Yeni kasa adı</label>
      <input type="text" name="ad" maxlength="60" required placeholder='Örn. "Kasa 1", "Bar Kasa"' class="rk-inp">
    </div>
    <button type="submit" class="rk-btn" style="background:{{ $adimRenk[3] }};padding:11px 18px"><span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Kasa Ekle</button>
  </form>
</div>
@foreach ($kasalar as $k)
  <div class="rk-card" style="margin-bottom:10px">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <span class="material-icons-outlined" style="color:{{ $adimRenk[3] }};font-size:22px">storefront</span>
      <form method="POST" action="/kasalar/duzenle" style="display:flex;gap:6px;align-items:center;flex:1">
        @csrf<input type="hidden" name="id" value="{{ $k->id }}">
        <input type="text" name="ad" value="{{ $k->ad }}" maxlength="60" class="rk-inp" style="max-width:220px;padding:7px 9px;font-weight:700">
        <button class="rk-btn bos" style="padding:7px 10px" title="Adı kaydet"><span class="material-icons-outlined" style="font-size:15px">save</span></button>
      </form>
      <form method="POST" action="/kasalar/sil" onsubmit="return confirm('{{ $k->ad }} kapatılsın mı?')">
        @csrf<input type="hidden" name="id" value="{{ $k->id }}">
        <button class="rk-btn bos" style="padding:7px 10px;color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 30%,transparent)"><span class="material-icons-outlined" style="font-size:15px">delete_outline</span></button>
      </form>
    </div>
    {{-- Kasa -> personel --}}
    <div class="rk-sub2" style="font-size:11.5px;font-weight:700;margin-bottom:6px;color:var(--sub2)">PERSONEL (bu kasaya atanınca girişte otomatik burada çalışır)</div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
      @foreach ($personeller as $p)
        <form method="POST" action="/kasalar/atama" style="margin:0">
          @csrf<input type="hidden" name="personel_id" value="{{ $p->id }}">
          <input type="hidden" name="kasa_id" value="{{ (int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id ? 0 : $k->id }}">
          <button class="rk-btn {{ (int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id ? '' : 'bos' }}" style="padding:5px 10px;font-size:12.5px;{{ (int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id ? 'background:'.$adimRenk[3] : '' }}">
            @if ((int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id)<span class="material-icons-outlined" style="font-size:14px;color:#fff">check</span>@endif {{ $p->ad }}
          </button>
        </form>
      @endforeach
    </div>
    {{-- Kasa -> masa --}}
    <div class="rk-sub2" style="font-size:11.5px;font-weight:700;margin-bottom:6px;color:var(--sub2)">MASALAR (bu kasaya bağlı masalar — opsiyonel)</div>
    <form method="POST" action="/isletme-duzeni/kasa-masa">
      @csrf<input type="hidden" name="kasa_id" value="{{ $k->id }}">
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
        @forelse ($masalar as $m)
          <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px">
            <input type="checkbox" name="masa_idler[]" value="{{ $m->id }}" {{ in_array((int)$m->id, $k->masa_idler ?? []) ? 'checked' : '' }}>
            {{ $bolgeAd[$m->bolge_id] ?? '' }} · {{ $m->ad }}
          </label>
        @empty
          <span class="rk-sub2">Masa yok.</span>
        @endforelse
      </div>
      @if ($masalar->count())<button class="rk-btn" style="background:{{ $adimRenk[3] }};padding:7px 14px"><span class="material-icons-outlined" style="color:#fff;font-size:15px">save</span> Masaları Kaydet</button>@endif
    </form>
  </div>
@endforeach
@if (!$kasalar->count())
  <div class="rk-card" style="text-align:center;padding:24px;margin-bottom:10px">
    <div class="rk-sub">Kasa noktası yok — tek-havuz (tek çekmece) modunda çalışıyorsun.</div>
    <div class="rk-sub2" style="font-size:12px;margin-top:6px">Birden fazla fiziksel kasan varsa yukarıdan ekle.</div>
  </div>
@endif

{{-- QR afiş önizleme modalı (uygulama içi) --}}
<div id="mduzQrModal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card" style="max-width:360px;width:100%;text-align:center;padding:24px">
    <div style="font-size:16px;font-weight:800;color:var(--ink)">{{ $subeAd ?? 'Restoran' }}</div>
    <div class="rk-sub" style="font-size:12px;margin-top:2px">Karekodu telefonunuzla okutun</div>
    <div style="background:#fff;border-radius:16px;padding:14px;margin:14px auto 0;display:inline-flex">
      <img id="mduzQrImg" src="" alt="QR" style="width:220px;height:220px;object-fit:contain">
    </div>
    <div class="rk-ink" id="mduzQrAd" style="font-size:24px;font-weight:800;margin-top:12px;color:var(--ink)">-</div>
    <div class="rk-sub" style="font-size:11px">Menü · Sipariş · Garson Çağır · Öde</div>
    <div class="rk-sub2" id="mduzQrLink" style="font-size:10.5px;margin-top:8px;word-break:break-all;opacity:.8"></div>
    <div style="display:flex;gap:10px;margin-top:18px">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="mduzQrKapat()">Kapat</button>
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="mduzQrYazdir()">
        <span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır
      </button>
    </div>
  </div>
</div>

<script>
var MDUZ_SUBE = @js($subeAd ?? 'Restoran');
var mduzAktifQr = null, mduzAktifAd = null;
function mduzQrImgUrl(url, size){ return 'https://api.qrserver.com/v1/create-qr-code/?size='+size+'x'+size+'&margin=0&data='+encodeURIComponent(url); }
function masaQr(ad, url){
  if(!url){ alert('Bu masanın QR kodu henüz oluşmamış. Sayfayı yenileyince otomatik oluşur.'); return; }
  mduzAktifQr = url; mduzAktifAd = ad || '-';
  document.getElementById('mduzQrAd').textContent = mduzAktifAd;
  document.getElementById('mduzQrLink').textContent = url;
  document.getElementById('mduzQrImg').src = mduzQrImgUrl(url, 220);
  document.getElementById('mduzQrModal').style.display = 'flex';
}
function mduzQrKapat(){ document.getElementById('mduzQrModal').style.display='none'; }
(function(){ var m=document.getElementById('mduzQrModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) mduzQrKapat(); }); })();
function mduzEsc(s){ return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function mduzQrYazdir(){
  if(!mduzAktifQr){ return; }
  var w = window.open('', '_blank');
  if(!w){ alert('Yazdırma penceresi açılamadı (açılır pencere engellenmiş olabilir).'); return; }
  w.document.write('<!doctype html><html lang="tr"><head><meta charset="utf-8"><title>QR Afiş</title><style>'+
    '@page{size:A6;margin:10mm}body{margin:0;font-family:ui-sans-serif,system-ui,sans-serif}'+
    '.afis{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;height:100vh}'+
    '.s{font-size:18px;font-weight:800}.h{font-size:11px;color:#555;margin-top:4px}'+
    '.afis img{width:150px;height:150px;margin:14px 0}.ad{font-size:26px;font-weight:800}.alt{font-size:10px;color:#555;margin-top:3px}'+
    '</style></head><body><div class="afis"><div class="s">'+mduzEsc(MDUZ_SUBE)+'</div>'+
    '<div class="h">Karekodu telefonunuzla okutun</div><img src="'+mduzQrImgUrl(mduzAktifQr,300)+'">'+
    '<div class="ad">'+mduzEsc(mduzAktifAd)+'</div><div class="alt">Menü · Sipariş · Garson Çağır · Öde</div></div><'+'/body></html>');
  w.document.close();
  setTimeout(function(){ try{ w.focus(); w.print(); }catch(e){} }, 600);
}
function rkBolgeTog(cb){
  // Bölge "tümü" seçilince o bölgenin tek tek masa kutuları kapanır (zaten dahil)
  var box = cb.closest('div').querySelector('[data-bolge]');
  if(!box) return;
  box.querySelectorAll('input[type=checkbox]').forEach(function(x){
    x.disabled = cb.checked; if(cb.checked) x.checked = false;
    x.closest('label').style.opacity = cb.checked ? .5 : 1;
  });
}
</script>
@endsection
@push('head')<style>
.rk-bolum-sayi{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;color:#fff;font-weight:800;font-size:14px;margin-right:4px}
</style>@endpush

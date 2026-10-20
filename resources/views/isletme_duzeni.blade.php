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
  <a href="/salon-sema" class="rk-btn" style="background:linear-gradient(135deg,#0EA5E9,#2563EB);color:#fff;padding:12px 20px;font-size:14px;font-weight:800;text-decoration:none;box-shadow:0 5px 16px rgba(14,165,233,.4)"><span class="material-icons-outlined" style="font-size:19px;color:#fff">grid_on</span> Salon Şeması (görsel kroki)</a>
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
    <div class="mduz-masa-grid">
      @foreach ($grup as $m)
        @php $qrurl = !empty($m->qr_token) ? url('/m/' . $m->qr_token) : null; @endphp
        <div class="mduz-masa-kart">
          <div class="mduz-masa-ust">
            <span class="material-icons-outlined mduz-sekil">{{ ($m->sekil ?? 'kare')==='yuvarlak' ? 'circle' : 'crop_square' }}</span>
            <div class="mduz-masa-bilgi">
              <div class="mduz-masa-ad" title="{{ $m->ad }}">{{ $m->ad }}</div>
              <div class="mduz-masa-alt">{{ (int)($m->kapasite ?? 0) }} kişi · {{ $bolgeAd[$bid] ?? 'Bölgesiz' }}</div>
            </div>
          </div>
          <div class="mduz-masa-btns">
            <button type="button" class="mduz-ibtn" style="color:{{ $qrurl ? 'var(--yesil)' : 'var(--amber)' }}" onclick="masaQr(@js($m->ad), @js($qrurl))" title="{{ $qrurl ? 'QR göster / yazdır' : 'QR henüz yok' }}"><span class="material-icons-outlined">qr_code_2</span></button>
            <button type="button" class="mduz-ibtn" style="color:var(--mavi)" onclick="masaDuzenleAc(@js(['id'=>(int)$m->id,'ad'=>$m->ad,'kapasite'=>(int)($m->kapasite ?? 4),'bolge_id'=>(int)$bid]))" title="Düzenle (ad · kişi · bölge)"><span class="material-icons-outlined">edit</span></button>
            <form method="POST" action="/masa-yonetim/sil" onsubmit="return confirm('{{ $m->ad }} arşive taşınsın mı? (QR korunur)')" style="margin:0">
              @csrf<input type="hidden" name="id" value="{{ $m->id }}">
              <button class="mduz-ibtn" style="color:var(--kirmizi)" title="Arşive taşı"><span class="material-icons-outlined">delete_outline</span></button>
            </form>
          </div>
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
  {{-- Kompakt garson listesi (50 garson olsa bile tek tek değil, liste) --}}
  <div class="gata-list">
    @foreach ($garsonlar as $g)
      @php
        $aMasa = count($g->masa_idler ?? []);
        $aBolge = count($g->bolge_idler ?? []);
        if ($aBolge > 0) $ozet = $aBolge . ' bölge' . ($aMasa > 0 ? ' + ' . $aMasa . ' masa' : '');
        elseif ($aMasa > 0) $ozet = $aMasa . ' masa';
        else $ozet = 'Tüm masalar';
        $atandi = $aBolge > 0 || $aMasa > 0;
      @endphp
      <div class="gata-row">
        <span class="material-icons-outlined gata-ikon" style="color:{{ $adimRenk[2] }}">room_service</span>
        <div class="gata-bilgi">
          <div class="gata-ad">{{ $g->ad }} <span class="gata-rol">· {{ ucfirst($g->rol ?? '') }}</span></div>
          <div class="gata-ozet" style="color:{{ $atandi ? $adimRenk[2] : 'var(--sub2)' }}"><span class="material-icons-outlined" style="font-size:13px;vertical-align:-2px">{{ $atandi ? 'check_circle' : 'remove_circle_outline' }}</span> {{ $ozet }}</div>
        </div>
        <button type="button" class="rk-btn bos gata-ata-btn" onclick="garsonAtaAc({{ $g->id }})"><span class="material-icons-outlined" style="font-size:16px">tune</span> Ata / Düzenle</button>
      </div>
    @endforeach
  </div>

  {{-- Gizli atama panelleri — tıklanınca modala taşınır --}}
  <div id="gata-paneller" style="display:none">
    @foreach ($garsonlar as $g)
      <div id="gata-panel-{{ $g->id }}" data-ad="{{ $g->ad }}">
        <form method="POST" action="/isletme-duzeni/garson-ata">
          @csrf<input type="hidden" name="personel_id" value="{{ $g->id }}">
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
          <div style="display:flex;gap:10px;margin-top:4px">
            <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="garsonAtaKapat()">İptal</button>
            <button type="submit" class="rk-btn" style="flex:2;justify-content:center;background:{{ $adimRenk[2] }}"><span class="material-icons-outlined" style="color:#fff;font-size:16px">save</span> Kaydet</button>
          </div>
        </form>
      </div>
    @endforeach
  </div>

  {{-- Modal kabuğu --}}
  <div id="gataModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
    <div class="rk-card gata-modal-box">
      <div class="gata-modal-head">
        <span class="material-icons-outlined" style="color:{{ $adimRenk[2] }};font-size:20px">room_service</span>
        <span id="gataModalAd" style="font-weight:800;color:var(--ink);font-size:15px;flex:1">Garson</span>
        <button type="button" class="mduz-ibtn" style="color:var(--sub)" onclick="garsonAtaKapat()"><span class="material-icons-outlined">close</span></button>
      </div>
      <div class="rk-sub2" style="font-size:11.5px;margin:2px 0 12px">Bölge seçersen o bölgenin tüm masaları dahildir. Hiç seçmezsen bu garson tüm masalara bakar.</div>
      <div id="gataModalBody"></div>
    </div>
  </div>
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

{{-- QR afiş önizleme modalı (uygulama içi) — kaliteli afiş önizleme --}}
<div id="mduzQrModal" style="display:none;position:fixed;inset:0;z-index:100;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="mduz-qr-box">
    <div class="mduz-qr-head">
      <span class="material-icons-outlined" style="font-size:19px">qr_code_2</span>
      <span id="mduzQrAd" class="mduz-qr-masa">-</span>
    </div>
    <div class="mduz-qr-body">
      <div class="mduz-qr-sube">{{ $subeAd ?? 'Restoran' }}</div>
      <div class="mduz-qr-ipucu">Karekodu telefonunuzla okutun</div>
      <div class="mduz-qr-frame">
        <img id="mduzQrImg" src="" alt="QR" style="width:220px;height:220px;object-fit:contain;display:block">
      </div>
      <div class="mduz-qr-ozellik">
        <span><span class="material-icons-outlined">restaurant_menu</span>Menü</span>
        <span><span class="material-icons-outlined">add_shopping_cart</span>Sipariş</span>
        <span><span class="material-icons-outlined">room_service</span>Garson</span>
        <span><span class="material-icons-outlined">payments</span>Öde</span>
      </div>
      <div class="rk-sub2" id="mduzQrLink" style="font-size:10.5px;margin-top:10px;word-break:break-all;opacity:.75"></div>
    </div>
    <div class="mduz-qr-foot">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="mduzQrKapat()">Kapat</button>
      <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mor1)" onclick="mduzQrYazdir()">
        <span class="material-icons-outlined" style="font-size:18px">print</span> Yazdır
      </button>
    </div>
  </div>
</div>

{{-- Masa düzenle modalı (ad · kapasite · bölge) — tek uçtan /api/patron/masa-guncelle --}}
<div id="mduzEditModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card" style="max-width:400px;width:100%;padding:22px">
    <div style="font-size:16px;font-weight:800;color:var(--ink);margin-bottom:16px;display:flex;align-items:center;gap:8px"><span class="material-icons-outlined" style="color:var(--mavi)">edit</span> Masa Düzenle</div>
    <input type="hidden" id="mduzEditId">
    <label class="rk-lbl" style="margin-top:0;font-size:12px">Masa adı</label>
    <input type="text" id="mduzEditAd" class="rk-inp" maxlength="40" placeholder='Örn. "Masa 1", "B4"'>
    <label class="rk-lbl" style="font-size:12px;margin-top:12px">Kapasite (kişi)</label>
    <input type="number" id="mduzEditKap" class="rk-inp" min="1" max="50">
    <label class="rk-lbl" style="font-size:12px;margin-top:12px">Bölge</label>
    <select id="mduzEditBolge" class="rk-inp">
      @foreach ($bolgeler as $b)<option value="{{ $b->id }}">{{ $b->ad }}</option>@endforeach
    </select>
    <div class="rk-sub" style="font-size:11px;margin-top:12px">QR kodu <b>değişmez</b> — basılı QR geçerli kalır.</div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick="masaDuzenleKapat()">İptal</button>
      <button type="button" class="rk-btn" id="mduzEditKaydet" style="flex:1;justify-content:center;background:var(--mavi)" onclick="masaDuzenleKaydet()">
        <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
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
// ---- Masa düzenle (ad · kapasite · bölge) → /api/patron/masa-guncelle (session ile çalışır) ----
function masaDuzenleAc(m){
  document.getElementById('mduzEditId').value = m.id;
  document.getElementById('mduzEditAd').value = m.ad || '';
  document.getElementById('mduzEditKap').value = m.kapasite || 4;
  document.getElementById('mduzEditBolge').value = m.bolge_id || '';
  document.getElementById('mduzEditModal').style.display = 'flex';
  setTimeout(function(){ document.getElementById('mduzEditAd').focus(); }, 50);
}
function masaDuzenleKapat(){ document.getElementById('mduzEditModal').style.display='none'; }
(function(){ var m=document.getElementById('mduzEditModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) masaDuzenleKapat(); }); })();
function masaDuzenleKaydet(){
  var id = parseInt(document.getElementById('mduzEditId').value);
  var ad = document.getElementById('mduzEditAd').value.trim();
  var kap = parseInt(document.getElementById('mduzEditKap').value) || 1;
  var bolge = document.getElementById('mduzEditBolge').value;
  if(!ad){ alert('Masa adı boş olamaz.'); return; }
  var btn = document.getElementById('mduzEditKaydet'); if(btn){ btn.disabled = true; btn.style.opacity = .6; }
  fetch('/api/patron/masa-guncelle', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':(window.csrf||(document.querySelector('meta[name=csrf-token]')||{}).content||'')},
    body: JSON.stringify({ id:id, ad:ad, kapasite:kap, bolge_id: bolge })
  }).then(function(r){ return r.json(); }).then(function(d){
    if(d.ok==1){ location.reload(); }
    else { if(btn){ btn.disabled=false; btn.style.opacity=1; } alert(d.hata || 'Güncellenemedi.'); }
  }).catch(function(){ if(btn){ btn.disabled=false; btn.style.opacity=1; } alert('Sunucuya ulaşılamadı.'); });
}
// ---- Garson atama modalı (gizli paneli modala taşı) ----
function garsonAtaAc(id){
  var panel = document.getElementById('gata-panel-'+id);
  if(!panel) return;
  document.getElementById('gataModalAd').textContent = panel.getAttribute('data-ad') || 'Garson';
  document.getElementById('gataModalBody').appendChild(panel);
  panel.style.display = '';
  document.getElementById('gataModal').style.display = 'flex';
}
function garsonAtaKapat(){
  var body = document.getElementById('gataModalBody');
  var store = document.getElementById('gata-paneller');
  while(body.firstChild){ body.firstChild.style.display='none'; store.appendChild(body.firstChild); }
  document.getElementById('gataModal').style.display = 'none';
}
(function(){ var m=document.getElementById('gataModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) garsonAtaKapat(); }); })();
function rkBolgeTog(cb){
  // Bölge "tümü" seçilince o bölgenin tek tek masa kutuları kapanır (zaten dahil)
  var box = cb.closest('div').querySelector('[data-bolge]');
  if(!box) return;
  box.querySelectorAll('input[type=checkbox]').forEach(function(x){
    x.disabled = cb.checked; if(cb.checked) x.checked = false;
    x.closest('label').style.opacity = cb.checked ? .5 : 1;
  });
}

// ---- Kaydırma konumunu koru: bu sayfadaki atama formları POST→redirect ile sayfayı yeniler,
//      ana içerik en üste zıplamasın diye gönderimden önce kaydet, yüklenince geri yükle. ----
(function(){
  var KEY = 'idz_scroll';
  // Ana içeriğin kaydırıcısı window mı yoksa .rk-body/.rk-main mi olduğunu bilmiyoruz; hepsini ele.
  function adaylar(){ return [document.querySelector('.rk-body'), document.querySelector('.rk-main'), document.scrollingElement, document.documentElement, document.body].filter(Boolean); }
  function getY(){ var y=0; adaylar().forEach(function(el){ if(el.scrollTop>y) y=el.scrollTop; }); if(window.scrollY>y) y=window.scrollY; return y; }
  function setY(y){ adaylar().forEach(function(el){ try{ el.scrollTop=y; }catch(e){} }); try{ window.scrollTo(0,y); }catch(e){} }
  // Yüklenince geri yükle (içerik yüksekliği oturana kadar birkaç kez dene)
  try{ var s=sessionStorage.getItem(KEY); if(s!==null){ var y=parseInt(s,10)||0; sessionStorage.removeItem(KEY);
    setY(y); requestAnimationFrame(function(){ setY(y); }); setTimeout(function(){ setY(y); }, 60); setTimeout(function(){ setY(y); }, 200); } }catch(e){}
  // Herhangi bir form gönderiminden hemen önce konumu sakla
  document.addEventListener('submit', function(){ try{ sessionStorage.setItem(KEY, getY()); }catch(e){} }, true);
})();
</script>
@endsection
@push('head')<style>
.rk-bolum-sayi{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;color:#fff;font-weight:800;font-size:14px;margin-right:4px}
/* ---- İşletme Düzeni: eşit masa kartları ---- */
.mduz-masa-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:10px}
.mduz-masa-kart{background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px;display:flex;flex-direction:column;gap:9px;min-height:86px;transition:border-color .12s,box-shadow .12s}
.mduz-masa-kart:hover{border-color:color-mix(in srgb,var(--mavi) 45%,var(--line));box-shadow:0 3px 10px rgba(0,0,0,.07)}
.mduz-masa-ust{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
.mduz-sekil{font-size:23px;color:#B45309;flex-shrink:0}
.mduz-masa-bilgi{min-width:0;flex:1}
.mduz-masa-ad{font-weight:800;color:var(--ink);font-size:14.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mduz-masa-alt{font-size:11.5px;color:var(--sub);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mduz-masa-btns{display:flex;gap:4px;justify-content:flex-end;align-items:center;border-top:1px solid var(--line);padding-top:8px}
.mduz-masa-btns form{margin:0;display:inline-flex}
.mduz-ibtn{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;border-radius:8px;border:none;background:transparent;cursor:pointer;transition:background .12s}
.mduz-ibtn:hover{background:color-mix(in srgb,currentColor 15%,transparent)}
.mduz-ibtn .material-icons-outlined{font-size:19px}
/* ---- Kaliteli QR afiş popup ---- */
.mduz-qr-box{background:var(--card);border-radius:22px;max-width:360px;width:100%;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.4);animation:mduzPop .18s ease}
@keyframes mduzPop{from{transform:scale(.94);opacity:0}to{transform:scale(1);opacity:1}}
.mduz-qr-head{display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;padding:14px 18px}
.mduz-qr-masa{font-size:19px;font-weight:800;letter-spacing:.2px}
.mduz-qr-body{padding:20px 22px 4px;text-align:center}
.mduz-qr-sube{font-size:16px;font-weight:800;color:var(--ink)}
.mduz-qr-ipucu{font-size:12px;color:var(--sub);margin-top:2px}
.mduz-qr-frame{display:inline-flex;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:14px;margin:14px auto 0;box-shadow:0 6px 20px rgba(0,0,0,.1)}
.mduz-qr-ozellik{display:flex;justify-content:center;gap:14px;margin-top:16px;flex-wrap:wrap}
.mduz-qr-ozellik span{display:inline-flex;flex-direction:column;align-items:center;gap:3px;font-size:10.5px;font-weight:600;color:var(--sub)}
.mduz-qr-ozellik .material-icons-outlined{font-size:20px;color:var(--mor1)}
.mduz-qr-foot{display:flex;gap:10px;padding:16px 22px 22px}
/* ---- Garson ataması: kompakt liste + modal ---- */
.gata-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:8px;margin-bottom:12px}
.gata-row{display:flex;align-items:center;gap:11px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.gata-ikon{font-size:22px;flex-shrink:0}
.gata-bilgi{min-width:0;flex:1}
.gata-ad{font-weight:800;color:var(--ink);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-rol{font-weight:500;font-size:12px;color:var(--sub2)}
.gata-ozet{font-size:11.5px;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-ata-btn{flex-shrink:0;padding:7px 12px;font-size:12.5px}
.gata-modal-box{max-width:520px;width:100%;max-height:85vh;display:flex;flex-direction:column;padding:20px}
.gata-modal-head{display:flex;align-items:center;gap:8px}
#gataModalBody{overflow-y:auto;flex:1;margin:0 -4px;padding:0 4px}
</style>@endpush

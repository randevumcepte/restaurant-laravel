@extends('layout.app')
@section('title', 'App Yönetim')
@section('baslik', '🎨 App Kartları — Müşteri Uygulaması')

@push('head')
<style>
#ayroot{--line:#e4e4ee}
#ayroot .ay-wrap{display:grid;grid-template-columns:1fr 400px;gap:20px}
@media(max-width:980px){#ayroot .ay-wrap{grid-template-columns:1fr}}
#ayroot .ay-card{background:#fff;border-radius:16px;padding:18px;box-shadow:0 6px 20px rgba(40,30,80,.06)}
#ayroot h2{font-size:15px;margin:0 0 14px;color:#39304f;font-weight:800}
#ayroot .flash{background:#e7f8ef;color:#137a4a;padding:10px 14px;border-radius:10px;margin:0 0 14px;font-size:14px;font-weight:600}
#ayroot .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}@media(max-width:560px){#ayroot .grid{grid-template-columns:1fr}}
#ayroot .item{background:#fafafe;border:1px solid var(--line);border-radius:14px;padding:10px}
#ayroot .kart{position:relative;width:100%;height:150px;border-radius:14px;overflow:hidden;box-shadow:0 8px 18px rgba(0,0,0,.18)}
#ayroot .kart .bg{position:absolute;inset:0;background-size:cover;background-position:center}
#ayroot .kart .scrim{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.02) 32%,rgba(0,0,0,.62))}
#ayroot .kart .txt{position:absolute;left:14px;right:14px;bottom:12px;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.6)}
#ayroot .kart .txt h3{margin:0 0 3px;font-size:17px;font-weight:800}#ayroot .kart .txt p{margin:0;font-size:12.5px;opacity:.96;line-height:1.25}
#ayroot .meta{display:flex;gap:6px;align-items:center;margin:9px 2px 8px}
#ayroot .rz{font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px}
#ayroot .rz-h{background:#efe7ff;color:#6d28d9}#ayroot .rz-k{background:#fde8ef;color:#be1d55}#ayroot .rz-p{background:#eee;color:#888}
#ayroot .sira{margin-left:auto;color:#aab;font-size:12px;font-weight:700}
#ayroot .ops{display:flex;gap:6px}
#ayroot .btn{border:none;border-radius:9px;padding:7px 11px;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block}
#ayroot .btn-d{background:#eef;color:#4338ca}#ayroot .btn-t{background:#f3f3f8;color:#555}#ayroot .btn-s{background:#fde8e8;color:#c0392b}
#ayroot label{display:block;font-size:12.5px;color:#6a6a80;font-weight:600;margin:12px 0 5px}
#ayroot input[type=text],#ayroot input[type=number],#ayroot textarea,#ayroot select{width:100%;padding:10px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:14px;font-family:inherit;box-sizing:border-box}
#ayroot textarea{resize:vertical;min-height:54px}
#ayroot .row{display:flex;gap:10px}#ayroot .row>div{flex:1}
#ayroot input[type=color]{width:46px;height:38px;border:1px solid #ddd;border-radius:8px;padding:2px;vertical-align:middle}
#ayroot .save{width:100%;margin-top:16px;padding:13px;background:linear-gradient(135deg,#17171c,#2b2b33);color:#fff;border:none;border-radius:11px;font-size:15px;font-weight:800;cursor:pointer}
#ayroot .pvwrap{background:#eef0f4;border-radius:14px;padding:16px;margin-bottom:6px}
#ayroot .pvlbl{font-size:11px;color:#9a9ab0;font-weight:700;letter-spacing:.5px;margin-bottom:8px}
#ayroot #pv{position:relative;width:100%;height:170px;border-radius:18px;overflow:hidden;box-shadow:0 10px 24px rgba(0,0,0,.22)}
#ayroot #pv .bg{position:absolute;inset:0;background-size:cover;background-position:center}
#ayroot #pv .scrim{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.02) 35%,rgba(0,0,0,.62))}
#ayroot #pv .txt{position:absolute;left:16px;right:16px;bottom:14px;color:#fff;text-shadow:0 2px 8px rgba(0,0,0,.6)}
#ayroot #pv .txt h3{margin:0 0 4px;font-size:20px;font-weight:800}#ayroot #pv .txt p{margin:0;font-size:13px;opacity:.96}
#ayroot .hint{font-size:11.5px;color:#9a9ab0;margin:4px 0 0}
#ayroot .nf{display:flex;align-items:center;gap:8px;margin-top:6px}#ayroot .nf input{flex:1}
</style>
@endpush

@section('content')
@php
    $pid = $duz->id ?? '';
    $ptip = $duz->tip ?? 'hero';
    $pbaslik = $duz->baslik ?? '';
    $pmetin = $duz->metin ?? '';
    $pgorsel = $duz->gorsel ?? '';
    $pdetay = $duz->detay ?? '';
    $pbuton = $duz->buton ?? 'Kullan';
    $plink = $duz->link ?? '';
    $pkupon = $duz->kupon_kodu ?? '';
    $podul = (int) ($duz->odul_puan ?? 0);
    $prenk = $duz->renk ?? '#14342B';
    $prenk2 = $duz->renk2 ?? '';
    $psira = (int) ($duz->sira ?? 1);
    $paktif = (!$duz || $duz->aktif) ? 'checked' : '';
@endphp
<div id="ayroot">
<div class="ay-wrap">
  <div class="ay-card">
    <h2>Mevcut Kartlar</h2>
    @if (session('ay_ok'))<div class="flash">✓ {{ session('ay_ok') }}</div>@endif
    <div class="grid">
      @forelse ($kartlar as $k)
        <div class="item">
          <div class="kart" style="background:linear-gradient(135deg,{{ $k->renk ?: '#14342B' }},{{ $k->renk2 ?: ($k->renk ?: '#333') }})">
            <div class="bg" style="@if($k->gorsel)background-image:url('{{ $k->gorsel }}')@endif"></div>
            <div class="scrim"></div>
            <div class="txt"><h3>{{ $k->baslik }}</h3><p>{{ $k->metin }}</p></div>
          </div>
          <div class="meta">
            @if($k->tip==='kampanya')<span class="rz rz-k">Kampanya</span>@else<span class="rz rz-h">Hero</span>@endif
            @unless($k->aktif)<span class="rz rz-p">Pasif</span>@endunless
            <span class="sira">#{{ (int) $k->sira }}</span>
          </div>
          <div class="ops">
            <a class="btn btn-d" href="/app-yonetim?duzenle={{ $k->id }}">Düzenle</a>
            <form method="post" action="/app-yonetim/aktif" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><button class="btn btn-t">{{ $k->aktif ? 'Gizle' : 'Göster' }}</button></form>
            <form method="post" action="/app-yonetim/sil" style="display:inline" onsubmit="return rkOnayForm(this, 'Silinsin mi?', 'var(--kirmizi)', 'Sil')">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><button class="btn btn-s">Sil</button></form>
          </div>
        </div>
      @empty
        <p style="color:#8a8a9a">Henüz kart yok. Sağdaki formdan ekle.</p>
      @endforelse
    </div>
  </div>

  <div class="ay-card">
    <h2>{{ $duz ? 'Kartı Düzenle' : 'Yeni Kart Ekle' }}</h2>
    <div class="pvwrap"><div class="pvlbl">CANLI ÖNİZLEME</div>
      <div id="pv"><div class="bg"></div><div class="scrim"></div><div class="txt"><h3>Başlık</h3><p>Açıklama</p></div></div>
    </div>
    <form method="post" action="/app-yonetim/kaydet" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="id" value="{{ $pid }}">
      <div class="row">
        <div><label>Tür</label><select name="tip" id="f_tip"><option value="hero" @selected($ptip!=='kampanya')>Hero (üst büyük)</option><option value="kampanya" @selected($ptip==='kampanya')>Kampanya (yatay)</option></select></div>
        <div><label>Sıra</label><input type="number" name="sira" value="{{ $psira }}"></div>
      </div>
      <label>Başlık</label><input type="text" name="baslik" id="f_baslik" value="{{ $pbaslik }}" oninput="upd()" maxlength="120" placeholder="Örn: 2.ye %50 az öde! 🍂">
      <label>Kısa açıklama (kartta görünür)</label><textarea name="metin" id="f_metin" oninput="upd()" maxlength="240" placeholder="Örn: Grande ve Venti boylarda 2.ye %50 az öde!">{{ $pmetin }}</textarea>
      <label>Görsel — bağlantı (URL)</label><input type="text" name="gorsel" id="f_gorsel" value="{{ $pgorsel }}" oninput="upd()" placeholder="https://...jpg">
      <p class="hint">veya cihazdan yükle:</p>
      <input type="file" name="gorsel_dosya" id="f_dosya" accept="image/*">
      <hr style="border:none;border-top:1px solid var(--line);margin:16px 0 4px">
      <p class="hint" style="font-weight:700;color:#6a6a80">↓ Detay sayfası (karta tıklayınca açılır)</p>
      <label>Detay metni (uzun açıklama · her satır ayrı madde)</label><textarea name="detay" id="f_detay" maxlength="2000" style="min-height:90px" placeholder="Kampanya 15–18 Ekim 2026 tarihlerinde geçerlidir.&#10;Grande / Venti boy içeceklerde geçerlidir.&#10;Stoklarla sınırlıdır.">{{ $pdetay }}</textarea>
      <div class="row">
        <div><label>Buton yazısı</label><input type="text" name="buton" value="{{ $pbuton }}" maxlength="40" placeholder="Kullan"></div>
        <div><label>Kullanınca +Puan</label><input type="number" name="odul_puan" value="{{ $podul }}" placeholder="0"></div>
      </div>
      <label>Kupon kodu (ops. — Kullan'da müşteriye gösterilir)</label><input type="text" name="kupon_kodu" value="{{ $pkupon }}" maxlength="40" placeholder="Örn: SONBAHAR50">
      <label>Paylaşım / yönlendirme linki (ops.)</label><input type="text" name="link" value="{{ $plink }}" placeholder="https://... (paylaş butonu bu linki paylaşır)">
      <div class="row" style="margin-top:12px">
        <div><label>Yedek renk 1</label><div class="nf"><input type="color" id="f_renk" value="{{ $prenk }}" oninput="syncRenk()"><input type="text" id="f_renk_t" value="{{ $prenk }}" oninput="document.getElementById('f_renk').value=this.value;upd()" name="renk"></div></div>
        <div><label>Yedek renk 2</label><div class="nf"><input type="color" id="f_renk2" value="{{ $prenk2 ?: '#333333' }}" oninput="syncRenk2()"><input type="text" id="f_renk2_t" value="{{ $prenk2 }}" oninput="document.getElementById('f_renk2').value=this.value;upd()" name="renk2" placeholder="(ops.)"></div></div>
      </div>
      <p class="hint">Görsel yoksa bu renk geçişi kullanılır (her zaman şık görünür).</p>
      <label style="display:flex;align-items:center;gap:8px;margin-top:12px"><input type="checkbox" name="aktif" value="1" {{ $paktif }} style="width:auto"> Yayında (uygulamada görünsün)</label>
      <button class="save">Kaydet</button>
      <a href="/app-yonetim" style="display:block;text-align:center;margin-top:10px;color:#8a8a9a;text-decoration:none;font-size:13px">+ Yeni boş karta geç</a>
    </form>
  </div>
</div>
</div>
<script>
function upd(){var t=document.getElementById('f_baslik').value||'Başlık';var m=document.getElementById('f_metin').value||'Açıklama';
var g=document.getElementById('f_gorsel').value;var r=document.getElementById('f_renk').value||'#14342B';var r2=document.getElementById('f_renk2').value||r;
var k=document.getElementById('pv');k.style.background='linear-gradient(135deg,'+r+','+r2+')';
k.querySelector('.bg').style.backgroundImage=g?("url('"+g+"')"):'none';
k.querySelector('h3').textContent=t;k.querySelector('p').textContent=m;}
function syncRenk(){document.getElementById('f_renk_t').value=document.getElementById('f_renk').value;upd();}
function syncRenk2(){document.getElementById('f_renk2_t').value=document.getElementById('f_renk2').value;upd();}
document.getElementById('f_dosya').addEventListener('change',function(e){if(e.target.files[0]){var u=URL.createObjectURL(e.target.files[0]);document.querySelector('#pv .bg').style.backgroundImage="url('"+u+"')";}});
document.addEventListener('DOMContentLoaded',upd);
</script>
@endsection

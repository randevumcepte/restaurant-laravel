@extends('layout.app')
@section('rk_ikon', 'qr_code_scanner')
@section('baslik', 'Barkod & Etiket')
@section('rk_alt', 'Ürün etiketi · tartılı ürün · raf kartı · barkod · demirbaş')
@section('title', 'Barkod & Etiket')

@push('head')
@include('etiket_merkezi_stil')
@endpush

@section('content')
@php
  $tl = fn ($v) => number_format((float) $v, 2, ',', '.');
  $turRenk = ['paket' => '#6366F1', 'tarti' => '#10B981', 'raf' => '#F59E0B'];
  $turKisa = ['paket' => 'Paket etiketi', 'tarti' => 'Tartılı', 'raf' => 'Raf kartı'];
  $paketler = $liste->filter(fn ($u) => $u->aktif && in_array('paket', $u->tur_liste, true))->values();
  $tartililar = $liste->filter(fn ($u) => in_array('tarti', $u->tur_liste, true))->values();
  $raflar = $liste->filter(fn ($u) => $u->aktif && in_array('raf', $u->tur_liste, true))->values();
  $kisaAd = fn ($id) => optional($liste->firstWhere('id', $id))->ad ?? 'Silinmiş ürün';
@endphp
<div id="dm"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ik"><span class="ms">qr_code_scanner</span></div>
    <div class="yazi">
      <h2>Barkod & Etiket</h2>
      <p>Tezgâhta, kasapta, şarküteride ya da markete gönderdiğin ürünlerin etiketini buradan bas: paket etiketi (üretim, SKT, parti), terazi etiketi, raf fiyat kartı ve barkod. Demirbaş etiketleri de burada.</p>
    </div>
    @if ($duzenler && $sekme === 'urunler')
      <div class="hb"><button class="b beyaz" type="button" onclick="emYeni()"><span class="ms">add_box</span> Yeni Ürün</button></div>
    @endif
  </div>

  @include('etiket_sekmeler', ['aktif' => $sekme, 'rafSay' => $ozet['raf']])

  {{-- ===================== ÜRÜNLER ===================== --}}
  @if ($sekme === 'urunler')
    <div class="kpi">
      <div class="k" style="--kr:#6366F1"><span class="ki"><span class="ms">inventory</span></span><div><div class="kd">{{ $ozet['adet'] }}</div><div class="kl">Etiketli ürün</div></div></div>
      <div class="k" style="--kr:#0EA5E9"><span class="ki"><span class="ms">restaurant_menu</span></span><div><div class="kd">{{ $ozet['bagli'] }}</div><div class="kl">Menüye bağlı (kasada satılır)</div></div></div>
      <div class="k" style="--kr:#10B981" onclick="location.href='/barkod-etiket/tarti'"><span class="ki"><span class="ms">scale</span></span><div><div class="kd">{{ $ozet['tarti'] }}</div><div class="kl">Tartılı ürün (PLU)</div></div></div>
      <div class="k" style="--kr:#F43F5E" onclick="location.href='/barkod-etiket/raf'"><span class="ki"><span class="ms">style</span></span><div><div class="kd">{{ $ozet['raf'] }}</div><div class="kl">Raf kartı basılmayı bekliyor</div></div></div>
      <div class="k" style="--kr:#F59E0B" onclick="location.href='/barkod-etiket/urun-etiketi'"><span class="ki"><span class="ms">print</span></span><div><div class="kd">{{ $ozet['basim7'] }}</div><div class="kl">Son 7 günde basılan etiket</div></div></div>
    </div>

    @if ($liste->isEmpty())
      <div class="adimlar" style="margin-bottom:12px">
        <div class="adim"><span class="no">1</span><div><b>Ürünü ekle</b><span class="t">Menüdeki bir ürüne bağla ya da menüde olmayan (markete giden kavanoz, şişe) ürünü ayrı ekle. Fiyat, net miktar, raf ömrü, içindekiler.</span></div></div>
        <div class="adim"><span class="no">2</span><div><b>Etiket türünü seç</b><span class="t">Paketli ürün etiketi, tartılı (terazi) ya da raf kartı. Bir ürün birden çok türde olabilir.</span></div></div>
        <div class="adim"><span class="no">3</span><div><b>Bas, okut, sat</b><span class="t">Etikette üretim, SKT ve parti otomatik gelir. Menüye bağlı ürünün barkodu kasada okutulunca satışa düşer.</span></div></div>
      </div>
      <div class="bos">
        <span class="ms bik">local_offer</span>
        <h3>Henüz etiketli ürün yok</h3>
        <p>Kasap köftesi, şarküteri peyniri, kendi ürettiğin şişelenmiş ürün… Ürünü bir kez tanımla; etiketi, raf kartı ve barkodu buradan basılır.</p>
        @if ($duzenler)<button class="b ana" type="button" onclick="emYeni()"><span class="ms">add_box</span> İlk ürünü ekle</button>@endif
      </div>
    @else
      <form class="okut" onsubmit="return false" autocomplete="off">
        <span class="ms i">search</span>
        <input id="emAra" value="{{ $ara }}" placeholder="Ürün adı, barkod ya da PLU yaz / okut…" oninput="emFiltre()">
      </form>
      <div class="arac" style="margin-top:12px">
        <div class="cipler">
          <button type="button" class="cip on" data-t="" onclick="emTur('')">Tümü <i>{{ $liste->count() }}</i></button>
          @foreach ($turKisa as $k => $a)
            @php $n = $liste->filter(fn ($u) => in_array($k, $u->tur_liste, true))->count(); @endphp
            @if ($n)<button type="button" class="cip" data-t="{{ $k }}" onclick="emTur('{{ $k }}')">{{ $a }} <i>{{ $n }}</i></button>@endif
          @endforeach
        </div>
      </div>
      <div class="liste" id="emListe">
        @foreach ($liste as $u)
          <div class="em-sat {{ $u->aktif ? '' : 'pasif' }}" style="--sc:{{ $turRenk[$u->tur_liste[0] ?? 'paket'] ?? '#6366F1' }}" data-tur="{{ implode(',', $u->tur_liste) }}" data-ara="{{ mb_strtolower($u->ad . ' ' . $u->barkod . ' ' . ($u->plu ? 'plu' . $u->plu . ' ' . $u->plu : '') . ' ' . $u->kategori . ' ' . $u->menu_ad) }}">
            <div class="kim">
              <span class="av" style="{{ $u->urun_id ? '' : 'background:#FEF3C7;color:#B45309' }}"><span class="ms">{{ in_array('tarti', $u->tur_liste, true) ? 'scale' : ($u->urun_id ? 'restaurant_menu' : 'local_shipping') }}</span></span>
              <div style="min-width:0">
                <div class="ad">{{ $u->ad }}@if (!$u->aktif) <span class="tz" style="--tr:#94A3B8">Pasif</span>@endif</div>
                <div class="kod">{{ implode(' · ', array_filter([$u->barkod ? $u->barkod . ' (' . ($u->barkod_tip === 'resmi' ? 'resmi' : 'mağaza içi') . ')' : (in_array('tarti', $u->tur_liste, true) ? null : 'barkodsuz'), $u->plu ? 'PLU ' . $u->plu : null, $u->urun_id ? 'menü: ' . ($u->menu_ad ?? '—') : 'menüde değil'])) }}</div>
              </div>
            </div>
            <div class="fiy">
              @if ($u->fb['indirimde'])<s>{{ $tl($u->fb['eski']) }} ₺</s>@endif<b>{{ $tl($u->fb['gecerli']) }} ₺</b>@if ($u->birim !== 'adet')<span style="font-size:12px;color:var(--sub)"> / {{ $u->birim }}</span>@endif
              <small>@if ($u->net_miktar && $u->birim === 'adet'){{ rtrim(rtrim(number_format((float) $u->net_miktar, 3, ',', '.'), '0'), ',') }} {{ $u->net_birim }}@endif @if ($u->fb['birim_fiyat'] && $u->birim === 'adet') · 1 {{ $u->fb['birim_ad'] }} {{ $tl($u->fb['birim_fiyat']) }} ₺@endif @if ($u->raf_omru_gun) · raf ömrü {{ $u->raf_omru_gun }} gün @endif</small>
            </div>
            <div class="rozetler">
              @foreach ($u->tur_liste as $t)<span class="tz" style="--tr:{{ $turRenk[$t] ?? '#64748B' }}">{{ $turKisa[$t] ?? $t }}</span>@endforeach
              @if ($u->raf_bekliyor)<span class="tz" style="--tr:#F43F5E"><span class="ms" style="font-size:13px">sync</span>Raf kartı yenilenecek</span>@endif
              @if ($u->fb['indirimde'])<span class="tz" style="--tr:#DC2626">İndirimde</span>@endif
            </div>
            <div class="islem">
              @if (in_array('paket', $u->tur_liste, true))<a class="ib" href="/barkod-etiket/urun-etiketi?id={{ $u->id }}" title="Ürün etiketi bas"><span class="ms">local_offer</span></a>@endif
              @if (in_array('tarti', $u->tur_liste, true))<a class="ib" href="/barkod-etiket/tarti?id={{ $u->id }}" title="Tartı etiketi bas"><span class="ms">scale</span></a>@endif
              @if (in_array('raf', $u->tur_liste, true))<a class="ib" href="/barkod-etiket/raf-yazdir?ids={{ $u->id }}" title="Raf kartı bas"><span class="ms">style</span></a>@endif
              @if ($duzenler)<button class="ib" type="button" onclick="emDuzenle({{ $u->id }})" title="Düzenle"><span class="ms">edit</span></button>@endif
            </div>
          </div>
        @endforeach
        <div class="sonucyok" id="emYok">Aramana uyan ürün yok.</div>
      </div>
    @endif
  @endif

  {{-- ===================== ÜRÜN ETİKETİ ===================== --}}
  @if ($sekme === 'urun')
    <div class="em-iz" style="margin-top:12px">
      <div class="kart">
        <div class="kb"><span class="ms">local_offer</span><h3>Paketli ürün etiketi bas</h3></div>
        <p class="alt-yazi">Üretim tarihini gir; son tüketim tarihi ürünün raf ömründen, parti numarası otomatik gelir. Basılan her parti aşağıdaki geçmişe yazılır (geri çağırma gerekirse hangi gün kaç adet basıldığı bellidir).</p>
        @if ($paketler->isEmpty())
          <div class="bos-kucuk"><span class="ms">inventory</span>"Paketli ürün etiketi" seçili ürün yok. <a href="/barkod-etiket" style="color:#6366F1;font-weight:800">Ürünler</a> sekmesinden ürün ekle.</div>
        @else
        <form class="fm" method="POST" action="/barkod-etiket/bas" id="emBasForm">
          @csrf <input type="hidden" name="tur" value="paket">
          <label>Ürün</label>
          <select name="id" id="emBasUrun" onchange="emBasGuncelle()">
            @foreach ($paketler as $u)<option value="{{ $u->id }}" data-omur="{{ $u->raf_omru_gun }}" data-sablon="{{ $u->sablon_id ? 1 : 0 }}" {{ (int) old('id', $secili) === (int) $u->id ? 'selected' : '' }}>{{ $u->ad }} — {{ $tl($u->fb['gecerli']) }} ₺{{ $u->raf_omru_gun ? ' · ' . $u->raf_omru_gun . ' gün' : '' }}</option>@endforeach
          </select>
          <label>Etiket şekli</label>
          <div class="modlar">
            <label><input type="radio" name="mod" value="tam" {{ old('mod', request('mod', 'tam')) === 'tam' ? 'checked' : '' }}><span>Tam etiket</span><small>Ad, net miktar, fiyat, tarih, parti ve barkod. Boş ambalaja yapıştırılır.</small></label>
            <label><input type="radio" name="mod" value="kucuk" {{ old('mod', request('mod')) === 'kucuk' ? 'checked' : '' }}><span>Küçük tarih etiketi</span><small>Yalnız üretim, SKT, parti. Matbaada basılmış etiketin üstüne yapıştırılır.</small></label>
            <label><input type="radio" name="mod" value="sablon" id="emModSablon" {{ old('mod', request('mod')) === 'sablon' ? 'checked' : '' }}><span>Kendi tasarımım</span><small>Yüklediğin tasarımın üstüne tarih/parti/barkod basılır. <em id="emSbNot" style="font-style:normal;color:#D97706">Bu ürüne tasarım bağlı değil — <a href="#sablon" style="color:#6366F1">nasıl?</a></em></small></label>
          </div>
          <div class="dort">
            <div><label>Üretim tarihi</label><input type="date" name="uretim" id="emUretim" value="{{ old('uretim', date('Y-m-d')) }}" onchange="emBasGuncelle()"></div>
            <div><label>Son tüketim (SKT)</label><input type="date" name="skt" id="emSkt" value="{{ old('skt') }}"></div>
            <div><label>Adet</label><input type="number" name="adet" min="1" max="500" value="{{ old('adet', 1) }}"></div>
            <div><label>Etiket boyutu</label><select name="boyut">@foreach (_emUrunBoyutlar() as $k => $b)<option value="{{ $k }}" {{ $ayar['urun_boyut'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select></div>
          </div>
          <div class="ipucu" id="emSktIpucu">SKT boş bırakılırsa ürünün raf ömründen hesaplanır.</div>
          <div class="dugmeler">
            <button class="b ana" type="submit"><span class="ms">print</span> Etiketi hazırla ve yazdır</button>
            <button class="b ik" type="submit" name="format" value="{{ $ayar['dil'] === 'tspl' ? 'tspl' : 'zpl' }}" title="Etiket yazıcısına doğrudan gönderilecek komut dosyası"><span class="ms">download</span> {{ $ayar['dil'] === 'tspl' ? 'TSPL' : 'ZPL' }} dosyası</button>
          </div>
        </form>
        @endif
      </div>

      <div class="kart" id="sablon">
        <div class="kb"><span class="ms">palette</span><h3>Kendi etiket tasarımın</h3></div>
        <p class="alt-yazi"><b>Ne işe yarar?</b> Ürünün renkli, logolu etiketi zaten varsa (grafikere yaptırdıysan) sistem o etiketi aynen kullanır. Her basımda yalnız değişen bilgileri — <b>SKT, parti no, üretim tarihi, barkod</b> — tasarımdaki boş yerlere kendisi yazar. Tasarımı biz çizmiyoruz; sen resmini yüklüyorsun.</p>
        <div class="sb-tanit">
          <img src="/img/etiket-ornek-nar.svg" alt="Örnek etiket tasarımı">
          <div>
            <b>İlk kez mi kullanıyorsun?</b>
            <span>Hazır örnek tasarımla 1 dakikada dene: kutular yerleşmiş gelir, sürükleyip görebilirsin.</span>
            <div class="dugmeler" style="margin-top:8px">
              @if ($duzenler)<form method="POST" action="/barkod-etiket/sablon-ornek">@csrf<button class="b ana" type="submit"><span class="ms">auto_awesome</span> Örnek tasarımla dene</button></form>@endif
              <a class="b ik" href="#rehber" onclick="var d=document.getElementById('rehberAc'); if(d) d.open=true"><span class="ms">menu_book</span> Adım adım anlatım</a>
            </div>
          </div>
        </div>
        @if ($sablonlar->count())
          <label style="display:block;font-size:11.5px;font-weight:700;color:var(--sub);margin:14px 0 6px">Tasarımların (düzenlemek için tıkla)</label>
          <div class="sablon-l" style="margin-bottom:12px">
            @foreach ($sablonlar as $s)
              <a href="/barkod-etiket/urun-etiketi?sablon={{ $s->id }}#sablon" class="{{ isset($sablonDuz) && $sablonDuz && $sablonDuz->id === $s->id ? 'on' : '' }}">
                <span class="on-iz" style="{{ $s->arka ? 'background-image:url(' . $s->arka . ')' : '' }}"></span>{{ $s->ad }}<small>{{ rtrim(rtrim(number_format((float) $s->gen_mm, 1, ',', ''), '0'), ',') }} × {{ rtrim(rtrim(number_format((float) $s->yuk_mm, 1, ',', ''), '0'), ',') }} mm</small>
              </a>
            @endforeach
          </div>
        @endif
        @if ($duzenler)
        <form class="fm sb-yukle" method="POST" action="/barkod-etiket/sablon" enctype="multipart/form-data">
          @csrf
          <div class="sb-yukle-bas"><span class="ms">upload_file</span> Kendi tasarımını yükle</div>
          <div class="uc">
            <div><label>Tasarım adı</label><input name="ad" placeholder="örn. Nar Ekşisi 500 ml" required></div>
            <div><label>Etiketin eni (mm)</label><input name="gen_mm" inputmode="decimal" value="58"></div>
            <div><label>Etiketin boyu (mm)</label><input name="yuk_mm" inputmode="decimal" value="40"></div>
          </div>
          <div class="ipucu">En/boy: etiketin <b>gerçek ölçüsü</b>. Bilmiyorsan etiketi cetvelle ölç (örn. 58 × 40 mm).</div>
          <label>Tasarım resmi (PNG / JPG, en çok 4 MB)</label><input type="file" name="arka" accept="image/png,image/jpeg,image/webp" required>
          <div class="ipucu">Tasarımda SKT, parti ve barkod için <b>beyaz boş yer</b> bırakılmış olmalı. Grafiker PDF verdiyse "PNG olarak dışa aktar" demesi yeterli.</div>
          <div class="dugmeler"><button class="b ik" type="submit"><span class="ms">upload</span> Tasarımı yükle</button></div>
        </form>
        @endif
      </div>
    </div>

    @if (isset($sablonDuz) && $sablonDuz)
      @php $sbAl = json_decode((string) $sablonDuz->alanlar, true) ?: []; @endphp
      <div class="kart" id="sbDuzen">
        <div class="kb"><span class="ms">open_with</span><h3>{{ $sablonDuz->ad }} — alanları yerleştir</h3>
          @if ($duzenler)<form method="POST" action="/barkod-etiket/sablon-sil" onsubmit="return confirm('Tasarım silinsin mi? Bu tasarımı kullanan ürünler tam etikete döner.')">@csrf<input type="hidden" name="id" value="{{ $sablonDuz->id }}"><button class="b tehlike" type="submit" style="padding:8px 12px"><span class="ms">delete</span> Sil</button></form>@endif
        </div>
        <div class="sb-nasil">
          <div><i>1</i><b>Sürükle</b><span>Kesik çizgili kutuyu (SKT, Parti, Barkod) farenle tutup tasarımdaki <u>beyaz boş yere</u> taşı.</span></div>
          <div><i>2</i><b>Boyutlandır</b><span>Kutunun sağ alt köşesindeki <span class="sb-nokta"></span> mor noktadan çekerek büyüt/küçült.</span></div>
          <div><i>3</i><b>Yazıyı ayarla</b><span>Kutuya tıkla; aşağıdan yazı boyu, kalın, hiza. Fiyat, içindekiler gibi yeni alan da ekleyebilirsin.</span></div>
          <div><i>4</i><b>Kaydet ve bağla</b><span>"Yerleşimi kaydet", sonra en alttan tasarımı ürüne bağla ve bas.</span></div>
        </div>
        @php
          // Önizleme: tasarıma bağlı (yoksa herhangi bir paket) ürünün GERÇEK bilgileriyle — bugün basılsaydı ne çıkardı
          $onizSira = $paketler->sortByDesc(fn ($u) => (int) ((int) $u->sablon_id === (int) $sablonDuz->id))->values();
          $oniz = $onizSira->mapWithKeys(fn ($u) => [$u->id => [
            'ad' => $u->ad, 'fiyat' => _emTl($u->fb['gecerli']) . ' ₺', 'birim_fiyat' => $u->fb['birim_fiyat'] ? '1 ' . $u->fb['birim_ad'] . ': ' . _emTl($u->fb['birim_fiyat']) . ' ₺' : '',
            'net' => ($n = _emNetMetin($u)) ? 'Net: ' . $n : '', 'uretim' => 'ÜT: ' . date('d.m.Y'),
            'skt' => $u->raf_omru_gun ? 'SKT: ' . date('d.m.Y', strtotime('+' . (int) $u->raf_omru_gun . ' days')) : 'SKT: (raf ömrü yok)',
            'parti' => 'Parti: ' . _emPartiNo(_webSubeId(), $u->id, date('Y-m-d')), 'barkod_url' => $u->barkod ? _emBarkodUrl($u->barkod, 50) : '',
            'icindekiler' => $u->icindekiler ? 'İçindekiler: ' . $u->icindekiler : '', 'alerjen' => (string) $u->alerjen, 'saklama' => (string) $u->saklama,
            'uretici' => (string) ($u->uretici ?: $ayar['uretici_varsayilan']), 'uretim_yeri' => $u->uretim_yeri ? 'Üretim yeri: ' . $u->uretim_yeri : '',
            'omur' => (int) $u->raf_omru_gun, 'barkod' => (string) $u->barkod]]);
          $onizIlk = $onizSira->first();
        @endphp
        <div class="sb-oniz">
          <div class="sb-oniz-ust">
            <span class="ms">visibility</span>
            @if ($onizIlk)
              <label for="sbOnizSec">Önizlenen ürün</label>
              <select id="sbOnizSec" onchange="sbOnizDegis(this.value)">
                @foreach ($onizSira as $u)<option value="{{ $u->id }}">{{ $u->ad }}{{ (int) $u->sablon_id === (int) $sablonDuz->id ? ' (bu tasarıma bağlı)' : '' }}</option>@endforeach
                <option value="0">— Genel örnek değerler —</option>
              </select>
              <span class="sb-oniz-not" id="sbOnizNot"></span>
            @else
              <span class="sb-oniz-not">Kutularda <b>örnek değerler</b> var. Paketli ürün ekleyince o ürünün gerçek SKT'si ve barkoduyla önizlenir.</span>
            @endif
          </div>
          <details class="sb-kaynak">
            <summary><span class="ms">help_outline</span> Bu kutuların değeri nereden gelir?</summary>
            <table>
              <tr><th>Kutu</th><th>Değer nereden gelir</th><th>Ne zaman</th></tr>
              <tr><td><b>Barkod</b></td><td>Ürünler → ürün kartı → <b>Barkod</b> (869… resmi ya da mağaza içi)</td><td>Ürünü tanımlarken bir kez</td></tr>
              <tr><td><b>SKT</b></td><td>Basarken girilen <b>üretim tarihi + ürün kartındaki "Raf ömrü (gün)"</b>. Basarken elle de değiştirilebilir</td><td>Her basımda otomatik</td></tr>
              <tr><td><b>Üretim (ÜT)</b></td><td>Basma formundaki <b>Üretim tarihi</b></td><td>Her basımda</td></tr>
              <tr><td><b>Parti</b></td><td>Sistem verir: tarih + o günün sırası (261117-<b>01</b>, -<b>02</b>…)</td><td>Her basımda otomatik</td></tr>
              <tr><td>Fiyat, net, içindekiler, üretici…</td><td>Ürün kartından</td><td>Ürünü tanımlarken bir kez</td></tr>
            </table>
            <div class="ipucu">Bu ekran değerleri <b>oluşturmaz</b>; yalnız etiketin <b>neresine</b> yazılacağını belirler. Gerçek değerler "Paketli ürün etiketi bas" ile basarken dolar.</div>
          </details>
        </div>
        <div id="sbAlan" class="sb-alan" style="background-image:url('{{ $sablonDuz->arka }}')"></div>
        @if ($duzenler)
        <div class="fm" style="margin-top:14px">
          <div class="sb-ozellik">
            <div><label>Alan ekle</label><select id="sbYeniK">@foreach (_emSablonAlanlar() as $k => $a)<option value="{{ $k }}">{{ $a }}</option>@endforeach</select></div>
            <div><label>&nbsp;</label><button class="b ik" type="button" onclick="sbEkle()" style="width:100%;justify-content:center"><span class="ms">add</span> Ekle</button></div>
            <div><label>Yazı boyu (pt)</label><input type="number" id="sbFs" min="4" max="40" step="0.5" oninput="sbOz()"></div>
            <div><label>Hiza</label><select id="sbHz" onchange="sbOz()"><option value="l">Sola</option><option value="c">Ortaya</option><option value="r">Sağa</option></select></div>
            <div><label><input type="checkbox" id="sbB" onchange="sbOz()" style="width:auto;margin-right:6px">Kalın</label></div>
            <div style="grid-column:span 2"><label>Serbest yazı (yalnız "Serbest yazı" alanında)</label><input id="sbT" oninput="sbOz()" maxlength="120"></div>
            <div><label>&nbsp;</label><button class="b tehlike" type="button" onclick="sbSil()" style="width:100%;justify-content:center"><span class="ms">close</span> Alanı kaldır</button></div>
          </div>
          <form method="POST" action="/barkod-etiket/sablon" onsubmit="document.getElementById('sbJson').value=JSON.stringify(SB.alanlar)">
            @csrf <input type="hidden" name="id" value="{{ $sablonDuz->id }}"><input type="hidden" name="alanlar" id="sbJson">
            <div class="uc">
              <div><label>Tasarım adı</label><input name="ad" value="{{ $sablonDuz->ad }}"></div>
              <div><label>En (mm)</label><input name="gen_mm" value="{{ (float) $sablonDuz->gen_mm }}"></div>
              <div><label>Boy (mm)</label><input name="yuk_mm" value="{{ (float) $sablonDuz->yuk_mm }}"></div>
            </div>
            <div class="dugmeler"><button class="b ana" type="submit"><span class="ms">save</span> Yerleşimi kaydet</button></div>
          </form>
          @php $bagliUrunler = $liste->where('sablon_id', $sablonDuz->id); @endphp
          <div class="sb-bagla">
            <div class="sb-yukle-bas"><span class="ms">link</span> Bu tasarımı ürüne bağla</div>
            @if ($bagliUrunler->count())
              <div class="ipucu" style="margin:0 0 8px">Şu an bağlı: @foreach ($bagliUrunler as $bu)<a href="/barkod-etiket/urun-etiketi?id={{ $bu->id }}&mod=sablon#emBasForm" class="tz" style="--tr:#10B981;text-decoration:none">{{ $bu->ad }} → bas</a> @endforeach</div>
            @endif
            @if ($paketler->isEmpty())
              <div class="ipucu">Bağlanacak ürün yok. Önce <a href="/barkod-etiket" style="color:#6366F1;font-weight:700">Ürünler</a> sekmesinde "Paketli ürün etiketi" seçili bir ürün ekle.</div>
            @else
            <form method="POST" action="/barkod-etiket/sablon-bagla" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
              @csrf <input type="hidden" name="sablon_id" value="{{ $sablonDuz->id }}">
              <select name="eu_id" style="flex:1;min-width:220px">@foreach ($paketler as $u)<option value="{{ $u->id }}">{{ $u->ad }}{{ $u->sablon_id && (int) $u->sablon_id !== (int) $sablonDuz->id ? ' (başka tasarım bağlı)' : '' }}</option>@endforeach</select>
              <button class="b ana" type="submit"><span class="ms">link</span> Bağla ve basmaya geç</button>
            </form>
            <div class="ipucu">Bağladıktan sonra yukarıdaki "Paketli ürün etiketi bas" kısmında <b>Kendi tasarımım</b> seçili gelir; üretim tarihi ve adedi girip basarsın.</div>
            @endif
          </div>
        </div>
        @endif
      </div>
      <script>
      var SB = { alanlar: {!! json_encode($sbAl, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}, gen: {{ (float) $sablonDuz->gen_mm }}, yuk: {{ (float) $sablonDuz->yuk_mm }}, sec: -1, duz: {{ $duzenler ? 'true' : 'false' }},
        ad: {!! json_encode(_emSablonAlanlar(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!},
        ornek: { ad: 'Ürün Adı', fiyat: '129,90 ₺', birim_fiyat: '1 kg: 259,80 ₺', net: '500 g', uretim: 'ÜT: {{ date('d.m.Y') }}', skt: 'SKT: {{ date('d.m.Y', strtotime('+30 days')) }}',
          parti: 'Parti: {{ date('ymd') }}-01', barkod_url: '/etiket-ean13.svg?k=8691234567890&h=50', icindekiler: 'İçindekiler: …', alerjen: 'Alerjen: …', saklama: 'Serin yerde saklayın', uretici: 'Üretici firma, adres', uretim_yeri: 'Üretim yeri: …' },
        urunler: {!! json_encode($oniz, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}, genel: null };
      SB.genel = SB.ornek;
      function sbOnizDegis(id){
        var u = SB.urunler[id], n = document.getElementById('sbOnizNot');
        SB.ornek = u || SB.genel;
        if (n) n.innerHTML = !u ? 'Genel örnek değerler gösteriliyor.'
          : 'Bugün basılsaydı: <b>' + SB.ornek.skt.replace('SKT: ','SKT ') + '</b>' + (u.omur ? ' (bugün + ' + u.omur + ' gün raf ömrü)' : '') + ' · barkod: <b>' + (u.barkod || 'yok — ürün kartından gir') + '</b>';
        sbCiz();
      }
      function sbPx(){ var a=document.getElementById('sbAlan'); return a.clientWidth / SB.gen; }
      function sbCiz(){
        var a=document.getElementById('sbAlan'), w=Math.min(a.parentNode.clientWidth-4, 640), olc=w/SB.gen;
        a.style.width=w+'px'; a.style.height=(SB.yuk*olc)+'px'; a.innerHTML='';
        SB.alanlar.forEach(function(f,i){
          var d=document.createElement('div'); d.className='sb-k'+(i===SB.sec?' sec':'');
          d.style.left=f.x+'%'; d.style.top=f.y+'%'; d.style.width=f.w+'%'; d.style.height=f.h+'%';
          d.style.fontSize=(f.fs*0.3528*olc)+'px'; d.style.fontWeight=f.b?'700':'400'; d.style.justifyContent=f.hz==='c'?'center':(f.hz==='r'?'flex-end':'flex-start');
          var t=document.createElement('span'); t.className='et-ad'; t.style.textAlign=f.hz==='c'?'center':(f.hz==='r'?'right':'left');
          if (f.k==='barkod' && SB.ornek.barkod_url) { var im=document.createElement('img'); im.src=SB.ornek.barkod_url; im.style.cssText='height:100%;max-width:100%;margin:0 auto;display:block;pointer-events:none'; d.appendChild(im); }
          else if (f.k==='barkod') { t.textContent='Barkod yok — ürün kartından gir'; t.style.color='#B45309'; t.style.fontSize='11px'; t.style.whiteSpace='normal'; d.appendChild(t); }
          else { t.textContent = f.k==='serbest' ? (f.t||'Serbest yazı') : (SB.ornek[f.k]||('(' + SB.ad[f.k] + ' — ürün kartında boş)')); d.appendChild(t); }
          if (SB.duz) { var h=document.createElement('span'); h.className='tut'; d.appendChild(h); sbSurukle(d,h,i); }
          a.appendChild(d);
        });
        sbPanel();
      }
      function sbSurukle(d,h,i){
        d.addEventListener('pointerdown',function(e){
          var buyut = e.target===h, a=document.getElementById('sbAlan'), r=a.getBoundingClientRect(), f=SB.alanlar[i], x0=e.clientX, y0=e.clientY, s={x:f.x,y:f.y,w:f.w,h:f.h};
          SB.sec=i; document.querySelectorAll('#sbAlan .sb-k').forEach(function(k,j){ k.classList.toggle('sec', j===i); }); sbPanel();
          d.setPointerCapture(e.pointerId); e.preventDefault();
          function hareket(ev){ var dx=(ev.clientX-x0)/r.width*100, dy=(ev.clientY-y0)/r.height*100;
            if (buyut) { f.w=Math.max(3,Math.min(100-f.x,s.w+dx)); f.h=Math.max(2,Math.min(100-f.y,s.h+dy)); d.style.width=f.w+'%'; d.style.height=f.h+'%'; }
            else { f.x=Math.max(0,Math.min(100-f.w,s.x+dx)); f.y=Math.max(0,Math.min(100-f.h,s.y+dy)); d.style.left=f.x+'%'; d.style.top=f.y+'%'; } }
          function bit(){ d.removeEventListener('pointermove',hareket); d.removeEventListener('pointerup',bit); ['x','y','w','h'].forEach(function(k){ f[k]=Math.round(f[k]*100)/100; }); }
          d.addEventListener('pointermove',hareket); d.addEventListener('pointerup',bit);
        });
      }
      function sbPanel(){ var f=SB.alanlar[SB.sec]; ['sbFs','sbHz','sbB','sbT'].forEach(function(id){ var e=document.getElementById(id); if(e) e.disabled=!f; });
        if(!f||!document.getElementById('sbFs')) return; document.getElementById('sbFs').value=f.fs; document.getElementById('sbHz').value=f.hz; document.getElementById('sbB').checked=!!f.b; document.getElementById('sbT').value=f.t||''; }
      function sbOz(){ var f=SB.alanlar[SB.sec]; if(!f) return; f.fs=parseFloat(document.getElementById('sbFs').value)||8; f.hz=document.getElementById('sbHz').value; f.b=document.getElementById('sbB').checked?1:0; f.t=document.getElementById('sbT').value; sbCiz(); }
      function sbEkle(){ var k=document.getElementById('sbYeniK').value; SB.alanlar.push({k:k,x:5,y:5,w:k==='barkod'?45:50,h:k==='barkod'?28:10,fs:k==='ad'?10:7,b:k==='ad'?1:0,hz:k==='barkod'?'c':'l',t:''}); SB.sec=SB.alanlar.length-1; sbCiz(); }
      function sbSil(){ if(SB.sec<0) return; SB.alanlar.splice(SB.sec,1); SB.sec=-1; sbCiz(); }
      function sbBasla(){ var s=document.getElementById('sbOnizSec'); if (s) sbOnizDegis(s.value); else sbCiz(); }
      window.addEventListener('resize', sbCiz); if (document.readyState!=='loading') sbBasla(); else document.addEventListener('DOMContentLoaded', sbBasla);
      </script>
    @endif

    @php $ornekKod = '8691234567890'; $ornekBug = date('d.m.Y'); $ornekSkt = date('d.m.Y', strtotime('+1 year')); $ornekParti = date('ymd') . '-01'; @endphp
    <div class="kart rehber" id="rehber">
      <details id="rehberAc" {{ $sablonlar->isEmpty() ? 'open' : '' }}>
        <summary><span class="ms">menu_book</span><h3>Kendi tasarımınla etiket basmak — adım adım</h3><span class="ac-kapa">Aç / kapat</span></summary>
        <p class="alt-yazi" style="margin-top:10px">Örnek: Ev yapımı nar ekşisi satıyorsun, grafikerin sana renkli bir etiket çizdi. Her üretimde etikete <b>o günün tarihi, son tüketim tarihi ve parti numarası</b> yazılması lazım. Elle yazmak yerine sistem bunları tasarımın üstüne basar. 5 adım:</p>
        <div class="rh-adimlar">
          <div class="rh-adim" style="--rc:#6366F1">
            <div class="rh-no">1</div><b>Tasarımı hazırlat</b>
            <div class="rh-resim"><img src="/img/etiket-ornek-nar.svg" alt="">
              <span class="rh-ok" style="left:4%;top:63%;width:45%;height:33%">boş bırak</span><span class="rh-ok" style="left:52%;top:63%;width:45%;height:33%">boş bırak</span></div>
            <span>Grafikere etiketi çizdir; <b>tarih/parti ve barkod için beyaz boş yer</b> bırakmasını söyle. Dosyayı <b>PNG veya JPG</b> olarak al.</span>
          </div>
          <div class="rh-adim" style="--rc:#EC4899">
            <div class="rh-no">2</div><b>Sisteme yükle</b>
            <div class="rh-form">
              <div><small>Tasarım adı</small><i>Nar Ekşisi 500 ml</i></div>
              <div class="iki"><div><small>En (mm)</small><i>58</i></div><div><small>Boy (mm)</small><i>40</i></div></div>
              <div><small>Tasarım resmi</small><i class="dosya"><span class="ms">image</span> nar-etiket.png</i></div>
              <em><span class="ms">upload</span> Tasarımı yükle</em>
            </div>
            <span>Yukarıdaki <b>"Kendi tasarımını yükle"</b> formuna adını, <b>etiketin gerçek ölçüsünü</b> (cetvelle ölç) ve resmi gir.</span>
          </div>
          <div class="rh-adim" style="--rc:#10B981">
            <div class="rh-no">3</div><b>Kutuları yerleştir</b>
            <div class="rh-resim"><img src="/img/etiket-ornek-nar.svg" alt="">
              <span class="rh-kutu" style="left:5.5%;top:66%;width:43%;height:10%">SKT: {{ $ornekSkt }}</span><span class="rh-kutu" style="left:5.5%;top:78%;width:43%;height:9%">Parti: {{ $ornekParti }}</span>
              <span class="rh-kutu" style="left:53.5%;top:66%;width:42%;height:28%">▌▌▍▌▍▍▌▌▍▌▍▌</span><span class="ms rh-imlec">near_me</span></div>
            <span>Yükleyince tasarım büyük açılır. <b>SKT, Parti, Barkod</b> kutularını farenle boş yerlere sürükle, köşeden boyutlandır, <b>"Yerleşimi kaydet"</b>.</span>
          </div>
          <div class="rh-adim" style="--rc:#0EA5E9">
            <div class="rh-no">4</div><b>Ürüne bağla</b>
            <div class="rh-form">
              <div><small>Bu tasarımı ürüne bağla</small><i>Ev Yapımı Nar Ekşisi <span class="ms" style="float:right;font-size:15px">expand_more</span></i></div>
              <em><span class="ms">link</span> Bağla ve basmaya geç</em>
              <div style="margin-top:6px"><small>ya da Ürünler → düzenle → "Kendi tasarımı"</small></div>
            </div>
            <span>Hangi ürünün etiketi olduğunu seç. Bir tasarım birden çok ürüne bağlanabilir.</span>
          </div>
          <div class="rh-adim" style="--rc:#F59E0B">
            <div class="rh-no">5</div><b>Bas</b>
            <div class="rh-resim"><img src="/img/etiket-ornek-nar.svg" alt="">
              <span class="rh-dolu" style="left:6%;top:66%;font-weight:700">SKT: {{ $ornekSkt }}</span><span class="rh-dolu" style="left:6%;top:77%">Üretim: {{ $ornekBug }}</span><span class="rh-dolu" style="left:6%;top:86%">Parti: {{ $ornekParti }}</span>
              <img class="rh-bk" src="/etiket-ean13.svg?k={{ $ornekKod }}&h=50" alt="" style="left:55%;top:66%;width:38%;height:27%"></div>
            <span><b>Paketli ürün etiketi bas</b> → ürünü seç → <b>Kendi tasarımım</b> → üretim tarihi + adet → yazdır. SKT ve parti kendiliğinden yazılır.</span>
          </div>
        </div>
        <div class="em-iz esit" style="margin-top:14px">
          <div class="bilgi"><span class="ms">print</span><div><b>Hangi yazıcıyla basılır?</b> Tasarım renkli olduğu için <b>renkli yazıcı</b> gerekir: renkli etiket yazıcısı (ör. Epson ColorWorks) ya da normal renkli yazıcı + <b>A4 yapışkan etiket yaprağı</b>. Zebra/TSC gibi termal etiket yazıcıları renk basmaz; onlarla <b>"Küçük tarih etiketi"</b> seçeneğini kullan (matbaada basılmış etiketin üstüne yalnız SKT/parti basar).</div></div>
          <div class="bilgi uyari"><span class="ms">help</span><div><b>Sık sorulanlar</b><br>• <b>Tasarımım yok:</b> bu bölümü kullanmak zorunda değilsin; "Tam etiket" sistemin hazır siyah-beyaz etiketidir.<br>• <b>Etiketlerim matbaada basılı:</b> "Küçük tarih etiketi" ile yalnız tarih/parti basıp üstüne yapıştır.<br>• <b>Kutu yazıya sığmıyor:</b> kutuyu köşeden büyüt ya da yazı boyunu küçült.<br>• <b>Ölçü yanlış çıktı:</b> tasarımı aç, en/boy (mm) değerini düzelt, kaydet.</div></div>
        </div>
      </details>
    </div>

    <div class="kart">
      <div class="kb"><span class="ms">history</span><h3>Basım geçmişi (parti kayıtları)</h3></div>
      @if ($basimlar->isEmpty())
        <div class="bos-kucuk"><span class="ms">receipt_long</span>Henüz paket etiketi basılmadı. Bastığın her parti burada listelenir: hangi gün, hangi ürün, kaç adet, SKT ne.</div>
      @else
        <div class="tablo-sar"><table class="tablo">
          <tr><th>Tarih</th><th>Ürün</th><th>Parti</th><th>Üretim</th><th>SKT</th><th>Adet</th><th>Basan</th><th></th></tr>
          @foreach ($basimlar as $b)
            <tr><td>{{ date('d.m.Y H:i', strtotime($b->created_at)) }}</td><td><b>{{ $kisaAd($b->eu_id) }}</b></td><td class="mono">{{ $b->parti_no }}</td>
              <td>{{ $b->uretim_tarih ? date('d.m.Y', strtotime($b->uretim_tarih)) : '—' }}</td><td>{{ $b->skt ? date('d.m.Y', strtotime($b->skt)) : '—' }}</td><td>{{ $b->adet }}</td>
              <td>{{ $persAd[$b->personel_id] ?? '—' }}</td><td><a class="ib" href="/barkod-etiket/yazdir/{{ $b->id }}" title="Aynı partiyi tekrar bas"><span class="ms">print</span></a></td></tr>
          @endforeach
        </table></div>
      @endif
    </div>
  @endif

  {{-- ===================== TARTILI ÜRÜN ===================== --}}
  @if ($sekme === 'tarti')
    @php
      $ph = $ayar['terazi_plu_hane']; $dh = 10 - $ph;
      $ornekKg = 0.452; $ornekU = $tartililar->first();
      $ornekDeger = $ayar['terazi_deger'] === 'fiyat' ? ($ornekU ? $ornekKg * $ornekU->fb['gecerli'] : 171.71) : $ornekKg;
      $ornekKod = _emTeraziBarkod($ayar, $ornekU->plu ?? 1, $ornekDeger);
    @endphp
    <div class="em-iz esit" style="margin-top:12px">
      <div class="kart">
        <div class="kb"><span class="ms">tune</span><h3>Terazi barkod düzeni</h3></div>
        <p class="alt-yazi">Etiketli terazi, tartılan ürüne 13 haneli bir barkod basar: içinde ürünün PLU numarası ve ağırlığı (ya da tutarı) vardır. Kasada bu barkod okutulunca ürün doğru gramajla satışa düşer. Teraziniz hangi düzende basıyorsa burayı ona göre ayarlayın (terazi kurulum menüsünde "barkod formatı" diye geçer).</p>
        <div class="kalip" id="kalipGoster"></div>
        <div class="kalip-ack"><span style="--kc:#6366F1"><i></i>Önek</span><span style="--kc:#10B981"><i></i>PLU (ürün no)</span><span style="--kc:#F59E0B"><i></i><span id="kalipDegerAd">Ağırlık</span></span><span style="--kc:#94A3B8"><i></i>Kontrol hanesi</span></div>
        @if ($duzenler)
        <form class="fm" method="POST" action="/barkod-etiket/ayar">
          @csrf
          <div class="dort">
            <div><label>Önek (20–29)</label><input name="terazi_onek" id="tzOnek" value="{{ $ayar['terazi_onek'] }}" maxlength="2" inputmode="numeric" oninput="tzKalip()"></div>
            <div><label>PLU hanesi</label><select name="terazi_plu_hane" id="tzPlu" onchange="tzKalip()">@foreach ([4, 5, 6] as $n)<option value="{{ $n }}" {{ $ph === $n ? 'selected' : '' }}>{{ $n }} hane</option>@endforeach</select></div>
            <div><label>Barkodda ne var?</label><select name="terazi_deger" id="tzDeger" onchange="tzKalip(true)"><option value="agirlik" {{ $ayar['terazi_deger'] === 'agirlik' ? 'selected' : '' }}>Ağırlık</option><option value="fiyat" {{ $ayar['terazi_deger'] === 'fiyat' ? 'selected' : '' }}>Tutar (fiyat)</option></select></div>
            <div><label>Ondalık</label><select name="terazi_ondalik" id="tzOnd" onchange="tzKalip()">@foreach ([0, 1, 2, 3] as $n)<option value="{{ $n }}" {{ $ayar['terazi_ondalik'] === $n ? 'selected' : '' }}>{{ $n }}</option>@endforeach</select></div>
          </div>
          <div class="ipucu" id="tzAck"></div>
          <div class="hazir">
            <button type="button" onclick="tzHazir('27',5,'agirlik',3)">27 · 5 PLU · ağırlık (g) — en yaygın</button>
            <button type="button" onclick="tzHazir('28',5,'fiyat',2)">28 · 5 PLU · tutar (kuruş)</button>
            <button type="button" onclick="tzHazir('21',4,'agirlik',3)">21 · 4 PLU · ağırlık</button>
          </div>
          <div class="dugmeler"><button class="b ana" type="submit"><span class="ms">save</span> Düzeni kaydet</button></div>
        </form>
        @endif
      </div>

      <div class="kart">
        <div class="kb"><span class="ms">print</span><h3>Terazisiz tartı etiketi</h3></div>
        <p class="alt-yazi">Etiketli teraziniz yoksa: ürünü normal terazide tartın, gramı buraya yazın. Sistem aynı düzende barkodlu etiketi basar; kasada okutulunca gramajla satışa düşer.</p>
        @if ($tartililar->isEmpty())
          <div class="bos-kucuk"><span class="ms">scale</span>Tartılı ürün yok. <a href="/barkod-etiket" style="color:#6366F1;font-weight:800">Ürünler</a> sekmesinde ürün eklerken "Tartılı ürün (terazi)" seç; fiyat birimi kilogram olmalı.</div>
        @else
        <form class="fm" method="POST" action="/barkod-etiket/bas">
          @csrf <input type="hidden" name="tur" value="tarti">
          <label>Ürün</label>
          <select name="id">@foreach ($tartililar->where('aktif', 1) as $u)<option value="{{ $u->id }}" {{ (int) old('id', $secili) === (int) $u->id ? 'selected' : '' }}>{{ $u->ad }} · PLU {{ $u->plu }} · {{ $tl($u->fb['gecerli']) }} ₺/kg{{ (float) $u->dara_g > 0 ? ' · dara ' . (float) $u->dara_g . ' g' : '' }}</option>@endforeach</select>
          <div class="uc">
            <div><label>Ağırlık (gram)</label><input name="gram" inputmode="decimal" required placeholder="örn. 452" value="{{ old('gram') }}"></div>
            <div><label>Paketleme tarihi</label><input type="date" name="uretim" value="{{ old('uretim', date('Y-m-d')) }}"></div>
            <div><label>Adet</label><input type="number" name="adet" min="1" max="50" value="1"></div>
          </div>
          <label style="display:flex;gap:8px;align-items:center;margin-top:12px"><input type="checkbox" name="dara_dus" value="1" checked style="width:18px;height:18px;accent-color:#6366F1"> Ürün kartındaki darayı (kap/ambalaj) düş — 2025 yönetmeliği: açık ürünün net ağırlığı darasız yazılır</label>
          <div class="dugmeler"><button class="b ana" type="submit"><span class="ms">print</span> Tartı etiketi bas</button></div>
        </form>
        @endif
      </div>
    </div>

    <div class="em-iz esit">
      <div class="kart">
        <div class="kb"><span class="ms">list_alt</span><h3>PLU listesi</h3>
          @if ($tartililar->count())<a class="b ik" href="/barkod-etiket/plu.csv" style="padding:8px 12px"><span class="ms">download</span> PLU dosyası (Excel/CSV)</a>@endif
        </div>
        <p class="alt-yazi">Terazinin hangi ürünü hangi kg fiyatıyla tartacağını bu liste söyler. Dosyayı indirip terazinin bilgisayar programına (CAS CL-Works, Dibal, Bizerba… hepsinde "PLU içe aktar" vardır) yükleyin. Fiyat değişince dosyayı yeniden yükleyin.</p>
        @if ($tartililar->isEmpty())
          <div class="bos-kucuk">Henüz PLU yok.</div>
        @else
        <div class="tablo-sar"><table class="tablo">
          <tr><th>PLU</th><th>Ürün</th><th>Kg fiyatı</th><th>Kasada</th></tr>
          @foreach ($tartililar as $u)
            <tr><td class="mono"><b>{{ $u->plu }}</b></td><td>{{ $u->ad }}@if (!$u->aktif) <span class="tz" style="--tr:#94A3B8">Pasif</span>@endif</td><td>{{ $tl($u->fb['gecerli']) }} ₺</td>
              <td>@if ($u->urun_id)<span class="tz" style="--tr:#10B981">Satılır</span>@else<span class="tz" style="--tr:#F43F5E" title="Menü ürününe bağlanmadan kasada okutulamaz">Menüye bağla</span>@endif</td></tr>
          @endforeach
        </table></div>
        @endif
      </div>

      <div class="kart">
        <div class="kb"><span class="ms">qr_code_scanner</span><h3>Terazi barkodunu dene</h3></div>
        <p class="alt-yazi">Teraziden çıkan bir etiketi okutun ya da numarasını yazın. Sistem ürünü, ağırlığı ve tutarı doğru çözüyorsa kasada da doğru çalışır.</p>
        <form class="okut" method="GET" action="/barkod-etiket/tarti" style="margin-top:0">
          <span class="ms i">qr_code_scanner</span>
          <input name="coz" value="{{ $test['kod'] ?? '' }}" placeholder="{{ $ornekKod ?: '2700001004520' }}" inputmode="numeric">
          <button class="b ana" type="submit">Çöz</button>
        </form>
        @if ($test)
          @php $s = $test['sonuc']; @endphp
          @if (!$s)
            <div class="sonuc-kutu kotu"><b>Bu bir terazi barkodu değil.</b> 13 hane olmalı, "{{ $ayar['terazi_onek'] }}" ile başlamalı ve son hanesi tutmalı. Teraziniz başka önek kullanıyorsa yukarıdaki düzeni değiştirin.</div>
          @elseif (!$s['urun'])
            <div class="sonuc-kutu kotu">Barkod okundu: <b>PLU {{ $s['plu'] }}</b>, {{ $s['tip'] === 'fiyat' ? 'tutar' : 'ağırlık' }} {{ $s['deger'] }}. Ancak PLU {{ $s['plu'] }} hiçbir ürüne tanımlı değil.</div>
          @else
            <div class="sonuc-kutu iyi"><b>{{ $s['urun']->ad }}</b> (PLU {{ $s['plu'] }})<br>
              Ağırlık: <b>{{ $s['kg'] !== null ? rtrim(rtrim(number_format($s['kg'], 3, ',', '.'), '0'), ',') . ' kg' : '—' }}</b> · Kg fiyatı: {{ $tl($s['kg_fiyat']) }} ₺ · Tutar: <b>{{ $s['tutar'] !== null ? $tl($s['tutar']) . ' ₺' : '—' }}</b>
              @if (!$s['urun']->urun_id)<br><span style="color:#be123c">Kasada satılması için ürün kartından menü ürününe bağlayın.</span>@endif</div>
          @endif
        @endif
      </div>
    </div>

    <div class="kart">
      <div class="kb"><span class="ms">history</span><h3>Basılan tartı etiketleri</h3></div>
      @if ($basimlar->isEmpty())
        <div class="bos-kucuk">Henüz sistemden tartı etiketi basılmadı. (Etiketli terazinin bastıkları burada görünmez; onlar kasada okutulunca satışa yazılır.)</div>
      @else
        <div class="tablo-sar"><table class="tablo">
          <tr><th>Tarih</th><th>Ürün</th><th>Net</th><th>Tutar</th><th>Barkod</th><th>Basan</th><th></th></tr>
          @foreach ($basimlar as $b)
            <tr><td>{{ date('d.m.Y H:i', strtotime($b->created_at)) }}</td><td><b>{{ $kisaAd($b->eu_id) }}</b></td><td>{{ rtrim(rtrim(number_format((float) $b->miktar, 3, ',', '.'), '0'), ',') }} kg</td>
              <td>{{ $tl($b->tutar) }} ₺</td><td class="mono">{{ $b->barkod }}</td><td>{{ $persAd[$b->personel_id] ?? '—' }}</td>
              <td><a class="ib" href="/barkod-etiket/yazdir/{{ $b->id }}" title="Tekrar bas"><span class="ms">print</span></a></td></tr>
          @endforeach
        </table></div>
      @endif
    </div>
    <script>
    function tzHazir(o,p,d,n){ document.getElementById('tzOnek').value=o; document.getElementById('tzPlu').value=p; document.getElementById('tzDeger').value=d; document.getElementById('tzOnd').value=n; tzKalip(); }
    function tzKalip(degDegisti){
      var o=document.getElementById('tzOnek'), onek=o?o.value:'{{ $ayar['terazi_onek'] }}', plu=o?parseInt(document.getElementById('tzPlu').value):{{ $ph }},
          deg=o?document.getElementById('tzDeger').value:'{{ $ayar['terazi_deger'] }}', ondE=document.getElementById('tzOnd');
      if (degDegisti && ondE) ondE.value = deg==='fiyat' ? 2 : 3;
      var ond=ondE?parseInt(ondE.value):{{ $ayar['terazi_ondalik'] }}, dh=10-plu, h='';
      h+='<span style="--kc:#6366F1">'+(onek||'2_')+'</span><span style="--kc:#10B981">'+'P'.repeat(plu)+'</span><span style="--kc:#F59E0B">'+(deg==='fiyat'?'F':'A').repeat(dh)+'</span><span style="--kc:#94A3B8">K</span>';
      document.getElementById('kalipGoster').innerHTML=h; document.getElementById('kalipDegerAd').textContent = deg==='fiyat'?'Tutar':'Ağırlık';
      var a=document.getElementById('tzAck'); if(!a) return;
      var bir = deg==='fiyat' ? (ond===2?'kuruş (1 ₺ = 100)':ond===0?'tam lira':'1/'+Math.pow(10,ond)+' ₺') : (ond===3?'gram (1 kg = 1000)':ond===0?'tam kilogram':'1/'+Math.pow(10,ond)+' kg');
      var maks = Math.pow(10,dh)-1;
      a.innerHTML='Örnek: <b>'+onek+'0'.repeat(plu-1)+'1</b> PLU 1 · son '+dh+' hane '+(deg==='fiyat'?'tutarı':'ağırlığı')+' '+bir+' cinsinden taşır (en çok '+(deg==='fiyat'?(maks/Math.pow(10,ond)).toLocaleString('tr-TR')+' ₺':(maks/Math.pow(10,ond)).toLocaleString('tr-TR')+' kg')+'). En çok '+(Math.pow(10,plu)-1)+' ürün tanımlanabilir.';
    }
    tzKalip();
    </script>
  @endif

  {{-- ===================== RAF KARTI ===================== --}}
  @if ($sekme === 'raf')
    @php $bekleyen = $raflar->where('raf_bekliyor', true); @endphp
    <div class="bilgi" style="margin-top:12px"><span class="ms">gavel</span><div><b>Fiyat Etiketi Yönetmeliği:</b> rafta satış fiyatı (tüm vergiler dahil), birim fiyatı (kg/lt), üretim yeri ve tarih görünmelidir. İndirimde eski fiyat olarak son 30 günün en düşük fiyatı yazılır; sistem bunu fiyat geçmişinden kendisi hesaplar. Yerli üretim ürünlerde "Yerli Üretim" ibaresi basılır.</div></div>
    @if ($raflar->isEmpty())
      <div class="bos"><span class="ms bik">style</span><h3>Raf kartı seçili ürün yok</h3><p>Ürün eklerken "Raf kartı" türünü seç; fiyatı değiştiğinde kartı yeniden basman gerektiğini sistem burada hatırlatır.</p><a class="b ana" href="/barkod-etiket"><span class="ms">inventory</span> Ürünlere git</a></div>
    @else
    <form method="GET" action="/barkod-etiket/raf-yazdir" class="em-iz" id="rafForm" onsubmit="return rafGonder()">
      <div class="kart">
        <div class="kb"><span class="ms">checklist</span><h3>Basılacak kartlar</h3>
          <div class="cipler" style="flex:none">
            <button type="button" class="cip {{ $bekleyen->count() ? 'on' : '' }}" data-f="b" onclick="rafFiltre('b')">Yenilenecek <i>{{ $bekleyen->count() }}</i></button>
            <button type="button" class="cip {{ $bekleyen->count() ? '' : 'on' }}" data-f="" onclick="rafFiltre('')">Tümü <i>{{ $raflar->count() }}</i></button>
          </div>
        </div>
        <p class="alt-yazi">"Yenilenecek" listesi: fiyatı değişmiş, indirime girmiş/çıkmış ya da hiç kartı basılmamış ürünler. Bastıktan sonra listeden düşer.</p>
        <label style="display:flex;gap:8px;align-items:center;font-size:12.5px;font-weight:700;color:var(--sub);margin:0 0 8px 2px"><input type="checkbox" id="rafHepsi" onchange="rafHepsi(this.checked)" style="width:17px;height:17px;accent-color:#6366F1"> Görünenlerin hepsini seç</label>
        <div class="secim-l" id="rafListe">
          @foreach ($raflar as $u)
            <label data-b="{{ $u->raf_bekliyor ? 1 : 0 }}">
              <input type="checkbox" class="rafSec" value="{{ $u->id }}" {{ ($u->raf_bekliyor || (int) $secili === (int) $u->id) ? 'checked' : '' }}>
              <span class="ad">{{ $u->ad }}<small>@if ($u->raf_fiyat !== null && $u->raf_bekliyor)Raftaki kart: {{ $tl($u->raf_fiyat) }} ₺ → yeni: {{ $tl($u->fb['gecerli']) }} ₺@elseif ($u->raf_fiyat === null)Kartı hiç basılmadı @else Son basım: {{ $u->raf_basim ? date('d.m.Y', strtotime($u->raf_basim)) : '—' }}@endif</small></span>
              <span class="fiy" style="text-align:right">@if ($u->fb['indirimde'])<s>{{ $tl($u->fb['eski']) }}</s>@endif<b>{{ $tl($u->fb['gecerli']) }} ₺</b></span>
            </label>
          @endforeach
        </div>
      </div>
      <div class="kart fm">
        <div class="kb"><span class="ms">print</span><h3>Biçim</h3></div>
        <div class="secenek" style="display:flex;flex-direction:column;gap:7px">
          @foreach (_emRafBicimler() as $k => $b)
            <label style="display:flex;gap:10px;align-items:center;margin:0;padding:10px 12px;border:1px solid var(--line);border-radius:12px;cursor:pointer;font-size:13px;font-weight:700;color:var(--ink)"><input type="radio" name="bicim" value="{{ $k }}" {{ $ayar['raf_bicim'] === $k ? 'checked' : '' }} style="width:auto;accent-color:#6366F1"> {{ $b[0] }}</label>
          @endforeach
        </div>
        <div class="ipucu">Rulo: etiket yazıcısında basılır, rafın fiyat şeridine yapıştırılır. A4: normal yazıcıda kesim çizgili yaprak; makasla ya da kesme makinesiyle ayrılır. Varsayılanı <a href="/barkod-etiket/ayarlar" style="color:#6366F1;font-weight:700">Ayarlar</a>'dan değiştir.</div>
        <label>Her üründen kaç kart?</label><input type="number" name="adet" min="1" max="20" value="1">
        <input type="hidden" name="ids" id="rafIds">
        <div class="dugmeler"><button class="b ana" type="submit"><span class="ms">print</span> Seçilenleri bas</button></div>
      </div>
    </form>
    <script>
    function rafFiltre(f){ document.querySelectorAll('#rafForm .cip').forEach(function(c){ c.classList.toggle('on', c.dataset.f===f); });
      document.querySelectorAll('#rafListe label').forEach(function(l){ l.style.display = (!f || l.dataset.b==='1') ? '' : 'none'; }); }
    function rafHepsi(v){ document.querySelectorAll('#rafListe label').forEach(function(l){ if(l.style.display!=='none') l.querySelector('input').checked=v; }); }
    function rafGonder(){ var ids=[].map.call(document.querySelectorAll('.rafSec:checked'),function(c){return c.value;}); if(!ids.length){ alert('En az bir ürün seç.'); return false; } document.getElementById('rafIds').value=ids.join(','); return true; }
    rafFiltre('{{ $bekleyen->count() ? 'b' : '' }}');
    </script>
    @endif
  @endif

  {{-- ===================== BARKOD OLUŞTUR ===================== --}}
  @if ($sekme === 'barkod')
    <div class="em-iz esit" style="margin-top:12px">
      <div class="kart">
        <div class="kb"><span class="ms">qr_code</span><h3>Serbest barkod / QR bas</h3></div>
        <p class="alt-yazi">Ürüne bağlı olmayan etiketler için: raf adresi, kasa no, depo kutusu, web adresi, Wi-Fi bilgisi… Her satır ayrı bir etiket olur.</p>
        <form class="fm" method="GET" action="/barkod-etiket/serbest">
          <label>Barkoda dönüşecek metin (her satır bir etiket)</label>
          <textarea name="kod" rows="5" required placeholder="RAF-A-01&#10;RAF-A-02&#10;https://..."></textarea>
          <div class="dort">
            <div><label>Tür</label><select name="tip"><option value="c128">Barkod (Code 128)</option><option value="ean13">EAN-13 (13 rakam)</option><option value="qr">QR kod</option></select></div>
            <div><label>Etiket boyutu</label><select name="boyut">@foreach (_emUrunBoyutlar() as $k => $b)<option value="{{ $k }}" {{ $k === '50x30' ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select></div>
            <div><label>Her birinden</label><input type="number" name="adet" min="1" max="100" value="1"></div>
            <div><label>Üst yazı (isteğe bağlı)</label><input name="ust" maxlength="60" placeholder="Ana depo"></div>
          </div>
          <div class="dugmeler"><button class="b ana" type="submit"><span class="ms">print</span> Önizle ve yazdır</button></div>
        </form>
      </div>

      <div class="kart">
        <div class="kb"><span class="ms">pin</span><h3>Barkodsuz ürünlere mağaza içi barkod</h3></div>
        <p class="alt-yazi">Kendi hazırladığın paketli ürünlerin (ör. 500 g kıyma paketi) barkodu yoksa sistem 2 ile başlayan 13 haneli bir barkod verir. Bu barkod <b>yalnız senin kasanda</b> geçerlidir.</p>
        @php $barkodsuz = $liste->filter(fn ($u) => !$u->barkod && in_array('paket', $u->tur_liste, true)); @endphp
        @if ($barkodsuz->isEmpty())
          <div class="sonuc-kutu iyi" style="margin-top:0">Paketli ürünlerin hepsinin barkodu var.</div>
        @else
          <div class="secim-l" style="max-height:220px;margin-bottom:10px">@foreach ($barkodsuz as $u)<label><span class="ad">{{ $u->ad }}<small>{{ $u->urun_id ? 'menü: ' . $u->menu_ad : 'menüde değil' }}</small></span></label>@endforeach</div>
          @if ($duzenler)
          <form method="POST" action="/barkod-etiket/ic-barkod" onsubmit="return confirm('{{ $barkodsuz->count() }} ürüne mağaza içi barkod verilsin mi?')">@csrf
            <button class="b ana" type="submit"><span class="ms">auto_awesome</span> {{ $barkodsuz->count() }} ürüne barkod ver</button>
          </form>
          @endif
        @endif
      </div>
    </div>
    <div class="bilgi uyari"><span class="ms">storefront</span><div><b>Ürünün başka marketlerde satılacaksa:</b> markete giden her ürünün <b>869</b> ile başlayan resmi barkodu olmalı. Bu barkod GS1 Türkiye'den (TOBB) firma adına alınır; yıllık üyelik ücreti vardır. Biz bu numarayı üretemeyiz; aldığın numarayı ürün kartındaki "Barkod" alanına yazman yeterli, etikete o basılır ve kasada o okunur. 2 ile başlayan mağaza içi barkodlar başka marketin kasasında çalışmaz.</div></div>
    <div class="bilgi"><span class="ms">wine_bar</span><div><b>Alkollü içecek</b> üretiyor/satıyorsan bandrol ve TAPDK kuralları ayrıca geçerlidir; bu etiketler bandrolün yerini tutmaz.</div></div>
  @endif

  {{-- ===================== AYARLAR ===================== --}}
  @if ($sekme === 'ayar')
    @if (!$duzenler)
      <div class="flash hata" style="margin-top:12px"><span class="ms">lock</span>Ayarları değiştirmek için "Barkod & Etiket — ekle / düzenle" yetkisi gerekir.</div>
    @endif
    <form method="POST" action="/barkod-etiket/ayar" class="fm">
      @csrf
      <div class="em-iz esit" style="margin-top:12px">
        <div class="kart">
          <div class="kb"><span class="ms">print</span><h3>Etiket yazıcısı</h3></div>
          <div class="secenek" style="display:flex;flex-direction:column;gap:7px">
            @foreach (_etiketDiller() as $k => $a)
              <label style="display:flex;gap:10px;align-items:flex-start;margin:0;padding:10px 12px;border:1px solid var(--line);border-radius:12px;cursor:pointer;font-size:13px;font-weight:600;color:var(--ink)"><input type="radio" name="dil" value="{{ $k }}" {{ $ayar['dil'] === $k ? 'checked' : '' }} style="width:auto;margin-top:2px;accent-color:#6366F1"> {{ $a }}</label>
            @endforeach
          </div>
          <div class="iz">
            <div><label>Yazıcı çözünürlüğü</label><select name="dpi"><option value="203" {{ $ayar['dpi'] == 203 ? 'selected' : '' }}>203 dpi (çoğu yazıcı)</option><option value="300" {{ $ayar['dpi'] == 300 ? 'selected' : '' }}>300 dpi</option></select></div>
            <div><label>Demirbaş etiketi boyutu</label><select name="boyut">@foreach (_etiketBoyutlar() as $k => $b)<option value="{{ $k }}" {{ $ayar['boyut'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select></div>
            <div><label>Etiket yazıcısı IP</label><input name="ip" value="{{ $ayar['ip'] }}" placeholder="192.168.1.50"></div>
            <div><label>Port</label><input name="port" type="number" value="{{ $ayar['port'] }}"></div>
          </div>
          <div class="ipucu"><b>Bilgisayardan yazdır</b> her yazıcıyla çalışır: yazıcının Windows sürücüsünü kur, yazdırma penceresinde o yazıcıyı seç, kenar boşluğu "Yok". <b>ZPL/TSPL</b> seçilirse uygulama etiketi ağdaki yazıcıya (IP) doğrudan gönderir; web'de komut dosyası indirilir.</div>
        </div>
        <div class="kart">
          <div class="kb"><span class="ms">straighten</span><h3>Varsayılan ölçüler ve bilgiler</h3></div>
          <label>Ürün etiketi boyutu</label>
          <select name="urun_boyut">@foreach (_emUrunBoyutlar() as $k => $b)<option value="{{ $k }}" {{ $ayar['urun_boyut'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select>
          <div class="ipucu">Terazi ve paket etiketlerinde en yaygın ölçü 58 × 40 mm'dir.</div>
          <label>Raf kartı biçimi</label>
          <select name="raf_bicim">@foreach (_emRafBicimler() as $k => $b)<option value="{{ $k }}" {{ $ayar['raf_bicim'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select>
          <div class="ipucu">Rulo kart etiket yazıcısıyla, A4 yaprak normal yazıcıyla basılır. Basarken her seferinde değiştirilebilir.</div>
          <label>Üretici / firma bilgisi (etiketlerde)</label>
          <textarea name="uretici_varsayilan" rows="3" maxlength="255" placeholder="Firma ünvanı, adres, telefon — ürün kartında ayrıca yazılmamışsa bu basılır">{{ $ayar['uretici_varsayilan'] }}</textarea>
        </div>
      </div>
      @if ($duzenler)<div class="dugmeler" style="margin-top:0"><button class="b ana" type="submit"><span class="ms">save</span> Ayarları kaydet</button></div>@endif
    </form>
  @endif
</div></div>

@if ($duzenler && $sekme === 'urunler')
  @include('etiket_merkezi_form')
@endif

@if ($sekme === 'urun')
<script>
function emBasGuncelle(){
  var s=document.getElementById('emBasUrun'); if(!s) return; var o=s.options[s.selectedIndex], omur=parseInt(o.dataset.omur)||0, u=document.getElementById('emUretim').value, ip=document.getElementById('emSktIpucu');
  var sb=document.getElementById('emModSablon'); if(sb){ sb.disabled = o.dataset.sablon!=='1'; var sn=document.getElementById('emSbNot'); if(sn) sn.style.display=sb.disabled?'':'none'; if(sb.disabled && sb.checked) document.querySelector('#emBasForm input[name=mod][value=tam]').checked=true; }
  if(omur && u){ var d=new Date(u+'T12:00:00'); d.setDate(d.getDate()+omur); var z=d.toISOString().slice(0,10); document.getElementById('emSkt').placeholder=z; ip.textContent='SKT boş kalırsa '+z.split('-').reverse().join('.')+' basılır (raf ömrü '+omur+' gün).'; }
  else ip.textContent = omur ? '' : 'Bu üründe raf ömrü tanımlı değil; SKT basılacaksa elle gir ya da ürün kartına raf ömrü ekle.';
}
emBasGuncelle();
</script>
@endif
@endsection

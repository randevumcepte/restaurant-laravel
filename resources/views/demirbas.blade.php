@extends('layout.app')
@section('rk_ikon', 'qr_code_2')
@section('baslik', 'Demirbaş & Etiket')
@section('rk_alt', 'Ekipman ve demirbaş · barkod, etiket, zimmet, konum ve sayım')
@section('title', 'Demirbaş & Etiket')

@push('head')
@include('demirbas_stil')
@endpush

@section('content')
@php
  $tl0 = fn ($v) => number_format(round((float) $v), 0, ',', '.');
  $renk = ['aktif' => '#10B981', 'bakimda' => '#F59E0B', 'arizali' => '#F43F5E', 'kayip' => '#BE123C', 'hurda' => '#94A3B8'];
  $sayac = $liste->groupBy('durum')->map->count();
@endphp
<div id="dm"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ik"><span class="ms">qr_code_2</span></div>
    <div class="yazi">
      <h2>Her ekipmanın bir etiketi, her etiketin bir geçmişi</h2>
      <p>Demirbaşı kaydet, sistem barkodunu verir. Etiketi bas, yapıştır. Okutunca kart açılır: kimde, nerede, ne zaman bakım gördü. Depoyu barkodla say, eksik olanı sistem söylesin.</p>
    </div>
    <div class="hb">
      @if ($duzenler)<button class="b beyaz" type="button" onclick="dmYeni()"><span class="ms">add_box</span> Yeni Demirbaş</button>@endif
      <a class="b cam" href="/demirbas/sayim"><span class="ms">fact_check</span> {{ $aktifSayim ? 'Sayıma devam' : 'Sayım' }}</a>
      @if ($duzenler)<button class="b cam" type="button" onclick="dmPopAc('dmAyar')"><span class="ms">print</span> Etiket Ayarları</button>@endif
      <a class="b cam" href="/demirbas/excel"><span class="ms">download</span> Excel</a>
    </div>
  </div>

  <form class="okut" id="dmOkutForm" onsubmit="return dmOkut(event)" autocomplete="off">
    <span class="ms i">qr_code_scanner</span>
    <input id="dmAra" value="{{ $ara }}" placeholder="Barkodu okut ya da ad, konum, kişi yaz…" autofocus oninput="dmFiltre()">
    <small>El okuyucuyla okut → Enter</small>
    <button type="button" class="b ik" id="dmKamBtn" onclick="dmKamera()" style="display:none"><span class="ms">photo_camera</span> Kamera</button>
    <button type="submit" class="b ana"><span class="ms">search</span> Bul</button>
  </form>

  @if ($liste->isEmpty())
  <div class="adimlar">
    <div class="adim"><span class="no">1</span><div><b>Demirbaşı kaydet</b><span class="t">Ad, konum ve kimde olduğunu gir; barkod numarası otomatik verilir. Kendi barkodun varsa onu yaz.</span></div></div>
    <div class="adim"><span class="no">2</span><div><b>Etiketi bas, yapıştır</b><span class="t">Etiket yazıcısından (Zebra, TSC…) ya da A4 etiket yaprağından basılır. Toplu seçip tek seferde basabilirsin.</span></div></div>
    <div class="adim"><span class="no">3</span><div><b>Okut, takip et</b><span class="t">Okutunca kart açılır; zimmet, konum, bakım işlenir. Sayımda okutulmayanlar "bulunamadı" çıkar.</span></div></div>
  </div>
  @endif

  <div class="kpi">
    <div class="k" style="--kr:#6366F1" onclick="dmDurum('')"><span class="ki"><span class="ms">inventory_2</span></span><div><div class="kd">{{ $ozet['adet'] }}</div><div class="kl">Kayıtlı demirbaş · {{ $tl0($ozet['deger']) }} ₺</div></div></div>
    <div class="k" style="--kr:#0EA5E9" onclick="dmZimmetli()"><span class="ki"><span class="ms">badge</span></span><div><div class="kd">{{ $ozet['zimmetli'] }}</div><div class="kl">Personele zimmetli</div></div></div>
    <div class="k" style="--kr:#F43F5E" onclick="dmDurum('sorunlu')"><span class="ki"><span class="ms">build</span></span><div><div class="kd">{{ $ozet['sorunlu'] }}</div><div class="kl">Bakımda / arızalı / kayıp</div></div></div>
    <div class="k" style="--kr:#F59E0B" onclick="dmDurum('garanti')"><span class="ki"><span class="ms">verified_user</span></span><div><div class="kd">{{ $ozet['garanti'] }}</div><div class="kl">Garantisi 30 günde bitiyor</div></div></div>
    <div class="k" style="--kr:#8B5CF6" onclick="location.href='/demirbas/sayim'"><span class="ki"><span class="ms">fact_check</span></span><div><div class="kd">{{ $ozet['sayilmamis'] }}</div><div class="kl">90 gündür sayılmamış</div></div></div>
  </div>

  @if ($liste->isNotEmpty())
  <div class="arac">
    <div class="cipler">
      <button type="button" class="cip on" data-d="" onclick="dmDurum('')">Tümü <i>{{ $liste->count() }}</i></button>
      @foreach ($durumlar as $k => $a)
        @if (($sayac[$k] ?? 0) > 0)<button type="button" class="cip" data-d="{{ $k }}" onclick="dmDurum('{{ $k }}')">{{ $a }} <i>{{ $sayac[$k] }}</i></button>@endif
      @endforeach
    </div>
    @if ($lokasyonlar->count())<select id="dmLok" onchange="dmFiltre()"><option value="">Tüm konumlar</option>@foreach ($lokasyonlar as $l)<option>{{ $l }}</option>@endforeach</select>@endif
    @if ($kategoriler->count())<select id="dmKat" onchange="dmFiltre()"><option value="">Tüm kategoriler</option>@foreach ($kategoriler as $l)<option>{{ $l }}</option>@endforeach</select>@endif
    <label class="cip"><input type="checkbox" id="dmHepsi" onchange="dmHepsiSec(this.checked)" style="accent-color:#6366F1"> Görünenleri seç</label>
  </div>

  <div class="liste" id="dmListe">
    @foreach ($liste as $d)
      @php $gb = !empty($d->garanti_bitis) && $d->garanti_bitis >= date('Y-m-d') && $d->garanti_bitis <= date('Y-m-d', strtotime('+30 days')); @endphp
      <div class="sat" data-id="{{ $d->id }}" data-durum="{{ $d->durum }}" data-lok="{{ $d->lokasyon }}" data-kat="{{ $d->kategori }}" data-zim="{{ $d->zimmet_personel_id ? 1 : 0 }}" data-gar="{{ $gb ? 1 : 0 }}"
           data-barkod="{{ $d->barkod }}" data-ara="{{ mb_strtolower($d->ad . ' ' . $d->barkod . ' ' . $d->kod . ' ' . $d->seri_no . ' ' . $d->lokasyon . ' ' . $d->kategori . ' ' . $d->marka . ' ' . $d->model . ' ' . ($persAd[$d->zimmet_personel_id] ?? '')) }}">
        <input type="checkbox" class="dmSec" value="{{ $d->id }}" onchange="dmSecim()">
        <a class="kim" href="/demirbas/kart/{{ $d->id }}">
          <span class="av"><span class="ms">handyman</span></span>
          <div style="min-width:0"><div class="ad">{{ $d->ad }}</div><div class="kod">{{ $d->barkod }}@if ($d->kategori) · {{ $d->kategori }}@endif @if (($d->adet ?? 1) > 1) · {{ $d->adet }} adet @endif</div></div>
        </a>
        <div class="hc {{ $d->lokasyon ? '' : 'yok' }}"><span class="ms">place</span><span class="t">{{ $d->lokasyon ?: 'Konum yok' }}</span></div>
        <div class="hc {{ $d->zimmet_personel_id ? '' : 'yok' }}"><span class="ms">person</span><span class="t">{{ $d->zimmet_personel_id ? ($persAd[$d->zimmet_personel_id] ?? '—') : 'Zimmetsiz' }}</span></div>
        <div class="rz"><span class="roz" style="--dr:{{ $renk[$d->durum] ?? '#94A3B8' }}">{{ $durumlar[$d->durum] ?? $d->durum }}</span></div>
        <div class="islem">
          <a class="ib" href="/demirbas/etiket?ids={{ $d->id }}" title="Etiket bas"><span class="ms">print</span></a>
          <a class="ib" href="/demirbas/kart/{{ $d->id }}" title="Kartı aç"><span class="ms">chevron_right</span></a>
        </div>
      </div>
    @endforeach
    <div class="sonucyok" id="dmYok">Aramana uyan demirbaş yok. @if ($duzenler)<a href="#" onclick="dmYeni(document.getElementById('dmAra').value);return false" style="color:#6366F1;font-weight:800">Bu barkodla yeni kayıt aç</a>@endif</div>
  </div>
  @else
  <div class="bos">
    <span class="ms b">qr_code_2</span>
    <h3>Henüz demirbaş yok</h3>
    <p>Fırın, buzdolabı, kasa, tablet, mobilya, depo rafı… Kaydettiğin her ekipmana sistem barkod verir; etiketini basıp yapıştırırsın, okutunca kartı açılır.</p>
    @if ($duzenler)<button class="b ana" type="button" onclick="dmYeni()"><span class="ms">add_box</span> İlk demirbaşı ekle</button>@endif
  </div>
  @endif
</div></div>

<div id="dmToplu">
  <b><span id="dmSecSay">0</span> seçili</b>
  <label>Her birinden <input type="number" id="dmAdet" value="1" min="1" max="50"> etiket</label>
  <button class="tb ana" type="button" onclick="dmTopluBas()"><span class="ms">print</span> Etiket bas</button>
  <button class="tb ik" type="button" onclick="dmTopluIndir()" title="Etiket yazıcısına doğrudan gönderilecek komut dosyası"><span class="ms">download</span> {{ $ayar['dil'] === 'tspl' ? 'TSPL' : 'ZPL' }}</button>
  <button class="tb ik" type="button" onclick="dmHepsiSec(false)" title="Seçimi kaldır"><span class="ms">close</span></button>
</div>

@if ($duzenler)
<div class="dmp-ort" id="dmYeniPop" onclick="if(event.target===this)dmPopKapat(this.id)">
  <form class="dmp" method="POST" action="/demirbas/kaydet" autocomplete="off">
    @csrf
    <div class="bas"><span class="ms">add_box</span><h3>Yeni Demirbaş</h3><button type="button" class="kapat" onclick="dmPopKapat('dmYeniPop')"><span class="ms">close</span></button></div>
    <div class="gov">@include('demirbas_form', ['d' => null])</div>
    <div class="alt">
      <button type="button" class="db ikincil" onclick="dmPopKapat('dmYeniPop')">Vazgeç</button>
      <button type="submit" class="db ana2" name="sonra" value="etiket"><span class="ms">print</span> Kaydet + Etiket bas</button>
      <button type="submit" class="db ana"><span class="ms">save</span> Kaydet</button>
    </div>
  </form>
</div>

<div class="dmp-ort" id="dmAyar" onclick="if(event.target===this)dmPopKapat(this.id)">
  <form class="dmp dar" method="POST" action="/demirbas/etiket-ayar">
    @csrf
    <div class="bas"><span class="ms">print</span><h3>Etiket Ayarları</h3><button type="button" class="kapat" onclick="dmPopKapat('dmAyar')"><span class="ms">close</span></button></div>
    <div class="gov">
      <label>Nasıl basılacak?</label>
      <div class="secenek">
        @foreach ($diller as $k => $a)
          <label><input type="radio" name="dil" value="{{ $k }}" {{ $ayar['dil'] === $k ? 'checked' : '' }}><span>{{ $a }}</span></label>
        @endforeach
      </div>
      <div class="iz">
        <div><label>Etiket boyutu</label><select name="boyut">@foreach ($boyutlar as $k => $b)<option value="{{ $k }}" {{ $ayar['boyut'] === $k ? 'selected' : '' }}>{{ $b[0] }}</option>@endforeach</select></div>
        <div><label>Yazıcı çözünürlüğü</label><select name="dpi"><option value="203" {{ $ayar['dpi'] == 203 ? 'selected' : '' }}>203 dpi (çoğu yazıcı)</option><option value="300" {{ $ayar['dpi'] == 300 ? 'selected' : '' }}>300 dpi</option></select></div>
        <div><label>Etiket yazıcısı IP</label><input name="ip" value="{{ $ayar['ip'] }}" placeholder="192.168.1.50"></div>
        <div><label>Port</label><input name="port" type="number" value="{{ $ayar['port'] }}"></div>
      </div>
      <div class="ipucu" style="margin-top:12px"><b>Bilgisayardan yazdır</b> her yazıcıda çalışır: Zebra/TSC'nin Windows sürücüsünü kur, yazdırma penceresinde o yazıcıyı seç. <b>ZPL/TSPL</b> seçilirse telefon/tablet uygulaması etiketi ağdaki yazıcıya (IP) doğrudan gönderir; web'de aynı komut dosya olarak indirilir.</div>
    </div>
    <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('dmAyar')">Vazgeç</button><button type="submit" class="db ana"><span class="ms">save</span> Kaydet</button></div>
  </form>
</div>
@endif
@include('demirbas_kamera')

<script>
(function(){
  ['dmYeniPop','dmAyar','dmToplu'].forEach(function(id){ var e=document.getElementById(id); if(e) document.body.appendChild(e); });
  if (document.getElementById('dmAra').value) dmFiltre();
})();
var _dmDurum = '', _dmZim = false;
function dmPopAc(id){ document.getElementById(id).classList.add('ac'); }
function dmPopKapat(id){ document.getElementById(id).classList.remove('ac'); }
function dmYeni(barkod){ var p=document.getElementById('dmYeniPop'); if(!p) return; if(barkod) p.querySelector('input[name=barkod]').value=barkod; dmPopAc('dmYeniPop'); setTimeout(function(){ p.querySelector('input[name=ad]').focus(); },60); }
function dmDurum(d){ _dmDurum=d; _dmZim=false; document.querySelectorAll('#dm .cip[data-d]').forEach(function(c){ c.classList.toggle('on', c.dataset.d===d); }); dmFiltre(); }
function dmZimmetli(){ _dmDurum=''; _dmZim=true; document.querySelectorAll('#dm .cip[data-d]').forEach(function(c){ c.classList.remove('on'); }); dmFiltre(); }
function dmFiltre(){
  var q=(document.getElementById('dmAra').value||'').toLocaleLowerCase('tr'), lok=(document.getElementById('dmLok')||{}).value||'', kat=(document.getElementById('dmKat')||{}).value||'', n=0;
  document.querySelectorAll('#dmListe .sat').forEach(function(s){
    var d=s.dataset.durum, ok=(!q||s.dataset.ara.indexOf(q)>-1)&&(!lok||s.dataset.lok===lok)&&(!kat||s.dataset.kat===kat);
    if(_dmDurum==='sorunlu') ok=ok&&['bakimda','arizali','kayip'].indexOf(d)>-1; else if(_dmDurum==='garanti') ok=ok&&s.dataset.gar==='1'; else if(_dmDurum) ok=ok&&d===_dmDurum;
    if(_dmZim) ok=ok&&s.dataset.zim==='1';
    s.style.display=ok?'':'none'; if(ok) n++;
  });
  var y=document.getElementById('dmYok'); if(y) y.style.display=n?'none':'block';
}
function dmOkut(e){
  e.preventDefault(); var v=(document.getElementById('dmAra').value||'').trim(); if(!v) return false;
  var tam=null; document.querySelectorAll('#dmListe .sat').forEach(function(s){ if(s.dataset.barkod===v) tam=s.dataset.id; });
  location.href = tam ? '/demirbas/kart/'+tam : '/demirbas/bul?kod='+encodeURIComponent(v);
  return false;
}
function dmKameraSonuc(kod){ document.getElementById('dmAra').value=kod; document.getElementById('dmOkutForm').requestSubmit(); }
function dmSecili(){ return Array.prototype.map.call(document.querySelectorAll('.dmSec:checked'), function(c){ return c.value; }); }
function dmSecim(){
  var s=dmSecili(); document.getElementById('dmSecSay').textContent=s.length; document.getElementById('dmToplu').classList.toggle('ac', s.length>0);
  document.querySelectorAll('#dmListe .sat').forEach(function(r){ r.classList.toggle('secili', r.querySelector('.dmSec').checked); });
}
function dmHepsiSec(v){ document.querySelectorAll('#dmListe .sat').forEach(function(r){ if(!v || r.style.display!=='none') r.querySelector('.dmSec').checked=v; }); var h=document.getElementById('dmHepsi'); if(h) h.checked=v; dmSecim(); }
function dmTopluUrl(ek){ return '/demirbas/etiket?ids='+dmSecili().join(',')+'&adet='+Math.max(1,parseInt(document.getElementById('dmAdet').value)||1)+(ek||''); }
function dmTopluBas(){ if(dmSecili().length) location.href=dmTopluUrl(); }
function dmTopluIndir(){ if(dmSecili().length) location.href=dmTopluUrl('&format={{ $ayar['dil'] === 'tspl' ? 'tspl' : 'zpl' }}'); }
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ ['dmYeniPop','dmAyar'].forEach(function(id){ var x=document.getElementById(id); if(x) x.classList.remove('ac'); }); } });
</script>
@endsection

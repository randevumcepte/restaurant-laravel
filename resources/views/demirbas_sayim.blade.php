@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Demirbaş Sayımı')
@section('rk_alt', 'Etiketleri okut · yerinde olan, yanlış yerde olan ve bulunamayan')
@section('title', 'Demirbaş Sayımı')

@push('head')
@include('demirbas_stil')
<style>
#dm .sayac{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:14px 0}
#dm .geri-bildirim{margin-top:10px;border-radius:14px;padding:13px 16px;font-size:15px;font-weight:800;display:none;align-items:center;gap:10px}
#dm .geri-bildirim .ms{font-size:24px}
#dm .geri-bildirim.bulundu{display:flex;background:color-mix(in srgb,#10B981 14%,var(--card));color:#047857}
#dm .geri-bildirim.yanlis_yer{display:flex;background:color-mix(in srgb,#F59E0B 16%,var(--card));color:#B45309}
#dm .geri-bildirim.tekrar{display:flex;background:var(--card2);color:var(--sub)}
#dm .geri-bildirim.bilinmeyen{display:flex;background:color-mix(in srgb,#F43F5E 13%,var(--card));color:#be123c}
#dm .uc-liste{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
#dm .ls{max-height:420px;overflow:auto}
#dm .ls div{display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px dashed var(--line);font-size:13px;color:var(--ink)}
#dm .ls div:last-child{border-bottom:0}
#dm .ls small{color:var(--sub);font-size:11.5px;margin-left:auto;text-align:right}
#dm .ls .bos-l{color:var(--sub);justify-content:center;border:0}
#dm .gecmis td,#dm .gecmis th{padding:9px 8px;border-bottom:1px solid var(--line);font-size:12.5px;text-align:left;color:var(--ink)}
#dm .gecmis th{font-size:10.5px;color:var(--sub);text-transform:uppercase;font-weight:800;background:#F5F5FF}
body.koyu #dm .gecmis th{background:var(--card2)}
@media (max-width:860px){ #dm .uc-liste{grid-template-columns:minmax(0,1fr)} #dm .sayac{grid-template-columns:repeat(2,minmax(0,1fr))} }
</style>
@endpush

@section('content')
<div id="dm"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ik"><span class="ms">fact_check</span></div>
    <div class="yazi">
      @if ($aktif)
        <h2>Sayım sürüyor{{ $aktif->lokasyon ? ' · ' . $aktif->lokasyon : ' · tüm konumlar' }}</h2>
        <p>{{ \Carbon\Carbon::parse($aktif->baslangic)->format('d.m.Y H:i') }} başladı{{ $aktif->baslatan_id ? ' · ' . ($persAd[$aktif->baslatan_id] ?? '') : '' }}. Etiketleri tek tek okut; yanlış konumda olanları ve okutulmayanları sistem ayırır. Telefondan da aynı sayıma devam edilebilir.</p>
      @else
        <h2>Depoyu barkodla say</h2>
        <p>Bir konumu (örn. Ana depo) ya da her yeri seç, sayımı başlat, etiketleri okut. Bitirince: yerinde olanlar, başka yerde bulunanlar ve hiç okutulmayanlar listelenir.</p>
      @endif
    </div>
    <div class="hb"><a class="b cam" href="/demirbas"><span class="ms">arrow_back</span> Demirbaşlar</a></div>
  </div>

  @if (!$aktif)
    <form class="okut" method="POST" action="/demirbas/sayim/baslat">
      @csrf
      <span class="ms i">place</span>
      <select name="lokasyon"><option value="">Tüm konumlar (her şeyi say)</option>@foreach ($lokasyonlar as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach</select>
      <button class="b ana" type="submit"><span class="ms">play_arrow</span> Sayımı başlat</button>
    </form>
  @else
    <form class="okut" id="sOkut" onsubmit="return sOkut(event)" autocomplete="off">
      <span class="ms i">qr_code_scanner</span>
      <input id="sKod" placeholder="Etiketi okut…" autofocus>
      <input id="sLok" list="sLokL" value="{{ $aktif->lokasyon }}" placeholder="Bulunduğu yer" style="max-width:190px;border-left:1px solid var(--line);padding-left:10px;font-size:14px" title="Okuttuğun ürün şu an nerede?">
      <datalist id="sLokL">@foreach ($lokasyonlar as $l)<option value="{{ $l }}">@endforeach</datalist>
      <button type="button" class="b ik" id="dmKamBtn" onclick="dmKamera(true)" style="display:none"><span class="ms">photo_camera</span> Kamera</button>
      <button class="b ana" type="submit"><span class="ms">done</span> Okut</button>
    </form>
    <div class="geri-bildirim" id="sGb"><span class="ms" id="sGbIk">check_circle</span><span id="sGbT"></span></div>

    <div class="sayac">
      <div class="k" style="--kr:#10B981"><span class="ki"><span class="ms">check_circle</span></span><div><div class="kd" id="cBul">{{ count($rapor['bulundu']) }}</div><div class="kl">Yerinde bulundu</div></div></div>
      <div class="k" style="--kr:#F59E0B"><span class="ki"><span class="ms">wrong_location</span></span><div><div class="kd" id="cYan">{{ count($rapor['yanlis_yer']) }}</div><div class="kl">Başka yerde bulundu</div></div></div>
      <div class="k" style="--kr:#F43F5E"><span class="ki"><span class="ms">search_off</span></span><div><div class="kd" id="cEks">{{ count($rapor['eksik']) }}</div><div class="kl">Henüz okutulmadı / {{ $rapor['kapsam'] }}</div></div></div>
      <div class="k" style="--kr:#64748B"><span class="ki"><span class="ms">help</span></span><div><div class="kd" id="cBil">{{ count($rapor['bilinmeyen']) }}</div><div class="kl">Tanımsız barkod</div></div></div>
    </div>

    <div class="uc-liste">
      <div class="kart"><div class="kb"><span class="ms">search_off</span><h3>Okutulmayanlar</h3></div><div class="ls" id="lEks">
        @forelse ($rapor['eksik'] as $e)<div data-id="{{ $e['id'] }}"><span class="ms" style="color:#F43F5E;font-size:17px">radio_button_unchecked</span>{{ $e['ad'] }}<small>{{ $e['lokasyon'] ?: '—' }}<br>{{ $e['barkod'] }}</small></div>@empty<div class="bos-l">Hepsi okutuldu 🎉</div>@endforelse
      </div></div>
      <div class="kart"><div class="kb"><span class="ms">wrong_location</span><h3>Başka yerde bulunanlar</h3></div><div class="ls">
        @forelse ($rapor['yanlis_yer'] as $e)<div><span class="ms" style="color:#F59E0B;font-size:17px">swap_horiz</span>{{ $e['ad'] }}<small>Kayıtta: {{ $e['kayitli'] ?: '—' }}<br>Bulundu: <b>{{ $e['lokasyon'] }}</b></small></div>@empty<div class="bos-l">Yok</div>@endforelse
      </div></div>
      <div class="kart"><div class="kb"><span class="ms">check_circle</span><h3>Okutulanlar</h3></div><div class="ls">
        @forelse ($rapor['bulundu'] as $e)<div><span class="ms" style="color:#10B981;font-size:17px">check</span>{{ $e['ad'] }}<small>{{ $e['lokasyon'] ?: '' }}</small></div>@empty<div class="bos-l">Henüz okutulmadı</div>@endforelse
        @foreach ($rapor['bilinmeyen'] as $e)<div><span class="ms" style="color:#64748B;font-size:17px">help</span><span style="font-family:monospace">{{ $e['kod'] }}</span><small>tanımsız</small></div>@endforeach
      </div></div>
    </div>

    <form class="kart" method="POST" action="/demirbas/sayim/bitir" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap" onsubmit="return confirm('Sayım bitirilsin mi?')">
      @csrf
      <div style="flex:1;min-width:240px;display:flex;flex-direction:column;gap:6px;font-size:13px;color:var(--ink)">
        @if ($duzenler)
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="konum_guncelle" value="1" checked style="accent-color:#6366F1"> Başka yerde bulunanların konumunu bulunduğu yer olarak güncelle</label>
        <label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="eksik_kayip" value="1" style="accent-color:#6366F1"> Okutulmayanları <b>Kayıp</b> olarak işaretle</label>
        @else
        <span style="color:var(--sub)">Bitirince sonuç kaydedilir; konum/durum güncellemesini demirbaş düzenleme yetkisi olan yapar.</span>
        @endif
      </div>
      <button class="b ik" type="submit" name="iptal" value="1" onclick="return confirm('Sayım silinsin mi? Okutulanlar kaydedilmez.')"><span class="ms">close</span> İptal</button>
      <button class="b ana" type="submit"><span class="ms">task_alt</span> Sayımı bitir</button>
    </form>
  @endif

  @if ($gecmis->count())
  <div class="kart" style="margin-top:12px">
    <div class="kb"><span class="ms">history</span><h3>Geçmiş sayımlar</h3></div>
    <div style="overflow-x:auto"><table class="gecmis" style="width:100%;border-collapse:collapse">
      <tr><th>Tarih</th><th>Konum</th><th>Yerinde</th><th>Başka yerde</th><th>Bulunamadı</th><th>Tanımsız</th><th>Sayan</th></tr>
      @foreach ($gecmis as $g) @php $o = json_decode($g->ozet ?? '{}', true) ?: []; @endphp
        <tr><td>{{ \Carbon\Carbon::parse($g->bitis ?? $g->baslangic)->format('d.m.Y H:i') }}</td><td>{{ $g->lokasyon ?: 'Tümü' }}</td>
          <td style="color:#059669;font-weight:800">{{ $o['bulundu'] ?? 0 }}</td><td style="color:#B45309;font-weight:800">{{ $o['yanlis_yer'] ?? 0 }}</td>
          <td style="color:#e11d48;font-weight:800" title="{{ implode(', ', $o['eksik_liste'] ?? []) }}">{{ $o['eksik'] ?? 0 }}</td><td>{{ $o['bilinmeyen'] ?? 0 }}</td><td>{{ $persAd[$g->bitiren_id] ?? '' }}</td></tr>
      @endforeach
    </table></div>
  </div>
  @endif
</div></div>
@include('demirbas_kamera')
@if ($aktif)
<script>
var _sMesgul=false;
function dmKameraSonuc(kod){ document.getElementById('sKod').value=kod; sGonder(kod); }
function sOkut(e){ e.preventDefault(); var k=document.getElementById('sKod').value.trim(); if(k) sGonder(k); return false; }
function sGonder(kod){
  if(_sMesgul) return; _sMesgul=true;
  var fd=new FormData(); fd.append('kod',kod); fd.append('lokasyon',document.getElementById('sLok').value); fd.append('_token','{{ csrf_token() }}');
  fetch('/demirbas/sayim/okut',{method:'POST',body:fd,headers:{'Accept':'application/json'}}).then(function(r){ return r.json(); }).then(function(j){
    _sMesgul=false; var gb=document.getElementById('sGb'); gb.className='geri-bildirim';
    if(!j.ok){ gb.classList.add('bilinmeyen'); document.getElementById('sGbT').textContent=j.hata||'Hata'; return; }
    gb.classList.add(j.sonuc); document.getElementById('sGbIk').textContent={bulundu:'check_circle',yanlis_yer:'wrong_location',tekrar:'replay',bilinmeyen:'help'}[j.sonuc]||'info';
    document.getElementById('sGbT').textContent=j.mesaj;
    try { navigator.vibrate && navigator.vibrate(j.sonuc==='bulundu'?60:[80,60,80]); } catch(e){}
    if(j.sayac){ cBul.textContent=j.sayac.bulundu; cYan.textContent=j.sayac.yanlis_yer; cEks.textContent=j.sayac.eksik; cBil.textContent=j.sayac.bilinmeyen; }
    if(j.id){ document.querySelectorAll('#lEks div').forEach(function(d){ if(d.dataset.id==j.id) d.remove(); }); }
    var k=document.getElementById('sKod'); k.value=''; k.focus();
  }).catch(function(){ _sMesgul=false; alert('Bağlantı hatası, tekrar okut.'); });
}
</script>
@endif
@endsection

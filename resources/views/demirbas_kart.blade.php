@extends('layout.app')
@section('rk_ikon', 'qr_code_2')
@section('baslik', $d->ad)
@section('rk_alt', 'Demirbaş kartı · ' . $d->barkod)
@section('title', $d->ad . ' — Demirbaş')

@push('head')
@include('demirbas_stil')
<style>
#dm .ust{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:12px;margin-top:14px}
#dm .bilgi{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 16px}
#dm .bilgi div{min-width:0}
#dm .bilgi small{display:block;font-size:10.5px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.3px}
#dm .bilgi b{display:block;font-size:13.5px;color:var(--ink);margin-top:2px;overflow:hidden;text-overflow:ellipsis}
#dm .bilgi b.yok{color:var(--sub2);font-weight:600}
#dm .etiket-on{background:#fff;border:1px dashed #CBD5E1;border-radius:12px;padding:12px;text-align:center;color:#000}
#dm .etiket-on .u{font-size:11px;color:#475569}
#dm .etiket-on .a{font-size:15px;font-weight:800;margin:2px 0 6px}
#dm .etiket-on img{max-width:100%;height:64px}
#dm .hizli{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:12px}
#dm .hizli button,#dm .hizli a{border:1px solid var(--line);background:var(--card);border-radius:13px;padding:11px 8px;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:5px;font-size:12px;font-weight:800;color:var(--ink);text-decoration:none;font-family:inherit}
#dm .hizli .ms{font-size:22px;color:#6366F1}
#dm .hizli button:hover,#dm .hizli a:hover{border-color:#A5B4FC;background:color-mix(in srgb,#6366F1 5%,var(--card))}
#dm .zaman{display:flex;flex-direction:column}
#dm .z{display:flex;gap:11px;padding:9px 0;border-bottom:1px dashed var(--line);align-items:flex-start}
#dm .z:last-child{border-bottom:0}
#dm .z .zi{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;flex:none;background:color-mix(in srgb,var(--zr) 13%,transparent);color:var(--zr)}
#dm .z .zi .ms{font-size:17px}
#dm .z .zt{flex:1;min-width:0;font-size:13px;color:var(--ink);line-height:1.4}
#dm .z .zt span{display:block;font-size:11.5px;color:var(--sub)}
#dm .iki{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
#dm .bk{display:flex;gap:10px;align-items:center;padding:9px 0;border-bottom:1px dashed var(--line);font-size:12.5px}
#dm .bk:last-child{border-bottom:0}
#dm .bk .t{flex:1;color:var(--ink)}
#dm .bk .t span{display:block;font-size:11px;color:var(--sub)}
@media (max-width:860px){ #dm .ust,#dm .iki{grid-template-columns:minmax(0,1fr)} #dm .bilgi{grid-template-columns:repeat(2,minmax(0,1fr))} #dm .hizli{grid-template-columns:repeat(2,minmax(0,1fr))} }
</style>
@endpush

@section('content')
@php
  $renk = ['aktif' => '#10B981', 'bakimda' => '#F59E0B', 'arizali' => '#F43F5E', 'kayip' => '#BE123C', 'hurda' => '#94A3B8'];
  $tl = fn ($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $trh = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('d.m.Y') : null;
  $hIkon = ['olustur' => ['add_circle', '#6366F1', 'Kayıt açıldı'], 'duzenle' => ['edit', '#64748B', 'Düzenlendi'], 'zimmet' => ['badge', '#0EA5E9', 'Zimmet'], 'lokasyon' => ['place', '#8B5CF6', 'Konum'],
    'durum' => ['flag', '#F59E0B', 'Durum'], 'bakim' => ['build', '#F43F5E', 'Bakım / arıza'], 'sayim' => ['fact_check', '#10B981', 'Sayımda okutuldu'], 'etiket' => ['print', '#64748B', 'Etiket']];
  $garantiGun = !empty($d->garanti_bitis) ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($d->garanti_bitis), false) : null;
@endphp
<div id="dm"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <form class="okut" style="margin-top:0" action="/demirbas/bul" method="GET" autocomplete="off" id="dmOkutForm">
    <a href="/demirbas" class="b ik" style="padding:9px 12px"><span class="ms">arrow_back</span> Liste</a>
    <span class="ms i">qr_code_scanner</span>
    <input name="kod" id="dmAra" placeholder="Başka bir etiket okut…" autofocus>
    <button type="button" class="b ik" id="dmKamBtn" onclick="dmKamera()" style="display:none"><span class="ms">photo_camera</span></button>
    <button type="submit" class="b ana"><span class="ms">search</span></button>
  </form>

  <div class="ust">
    <div class="kart" style="margin:0">
      <div class="kb"><span class="ms">handyman</span><h3>{{ $d->ad }}</h3><span class="roz" style="--dr:{{ $renk[$d->durum] ?? '#94A3B8' }}">{{ $durumlar[$d->durum] ?? $d->durum }}</span></div>
      <div class="bilgi">
        <div><small>Konum</small><b class="{{ $d->lokasyon ? '' : 'yok' }}">{{ $d->lokasyon ?: 'Belirtilmemiş' }}</b></div>
        <div><small>Zimmet</small><b class="{{ $d->zimmet_personel_id ? '' : 'yok' }}">{{ $d->zimmet_personel_id ? ($persAd[$d->zimmet_personel_id] ?? '—') : 'Zimmetsiz' }}</b></div>
        <div><small>Kategori</small><b class="{{ $d->kategori ? '' : 'yok' }}">{{ $d->kategori ?: '—' }}</b></div>
        <div><small>Marka / model</small><b class="{{ ($d->marka || $d->model) ? '' : 'yok' }}">{{ trim(($d->marka ?? '') . ' ' . ($d->model ?? '')) ?: '—' }}</b></div>
        <div><small>Seri no</small><b class="{{ $d->seri_no ? '' : 'yok' }}">{{ $d->seri_no ?: '—' }}</b></div>
        <div><small>Adet</small><b>{{ $d->adet ?? 1 }}</b></div>
        <div><small>Tedarikçi</small><b class="{{ $d->tedarikci ? '' : 'yok' }}">{{ $d->tedarikci ?: '—' }}</b></div>
        <div><small>Alış</small><b class="{{ (float) $d->alis_bedel ? '' : 'yok' }}">{{ (float) $d->alis_bedel ? $tl($d->alis_bedel) : '—' }}@if ($d->alis_tarih) <span style="font-weight:600;color:var(--sub)">· {{ $trh($d->alis_tarih) }}</span>@endif</b></div>
        <div><small>Garanti</small><b class="{{ $d->garanti_bitis ? '' : 'yok' }}" @if ($garantiGun !== null && $garantiGun <= 30) style="color:{{ $garantiGun < 0 ? '#94A3B8' : '#D97706' }}" @endif>{{ $d->garanti_bitis ? $trh($d->garanti_bitis) . ($garantiGun < 0 ? ' (bitti)' : ' (' . $garantiGun . ' gün)') : '—' }}</b></div>
        <div><small>Son sayım</small><b class="{{ $d->son_sayim ? '' : 'yok' }}">{{ $d->son_sayim ? \Carbon\Carbon::parse($d->son_sayim)->format('d.m.Y H:i') : 'Hiç sayılmadı' }}</b></div>
        <div><small>İç kod</small><b class="{{ $d->kod ? '' : 'yok' }}">{{ $d->kod ?: '—' }}</b></div>
        <div><small>Kayıt</small><b>{{ $trh($d->created_at) }}</b></div>
      </div>
      @if ($d->not)<div style="margin-top:12px;font-size:12.5px;color:var(--sub);background:var(--card2);border-radius:10px;padding:9px 11px">{{ $d->not }}</div>@endif
      @if ($duzenler)
      <div class="hizli">
        <button type="button" onclick="dmPopAc('pZim')"><span class="ms">badge</span> Zimmet ver / al</button>
        <button type="button" onclick="dmPopAc('pLok')"><span class="ms">place</span> Konum değiştir</button>
        <button type="button" onclick="dmPopAc('pBak')"><span class="ms">build</span> Bakım / arıza</button>
        <button type="button" onclick="dmPopAc('pDur')"><span class="ms">flag</span> Durum</button>
      </div>
      @endif
    </div>
    <div class="kart" style="margin:0">
      <div class="kb"><span class="ms">qr_code_2</span><h3>Etiket</h3></div>
      <div class="etiket-on">
        <div class="u">DEMİRBAŞ</div>
        <div class="a">{{ $d->ad }}</div>
        <img src="/etiket-barkod.svg?k={{ urlencode($d->barkod) }}&h=60" alt="{{ $d->barkod }}">
      </div>
      <form action="/demirbas/etiket" method="GET" style="display:flex;gap:8px;margin-top:12px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="ids" value="{{ $d->id }}">
        <label style="font-size:12px;font-weight:700;color:var(--sub);display:flex;gap:6px;align-items:center">Adet <input name="adet" type="number" value="{{ max(1, (int) ($d->adet ?? 1)) }}" min="1" max="50" style="width:64px;border:1px solid var(--line);border-radius:10px;padding:8px;background:var(--card);color:var(--ink)"></label>
        <button class="b ana" type="submit" style="flex:1;justify-content:center"><span class="ms">print</span> Etiket bas</button>
        <button class="b ik" type="submit" name="format" value="{{ $ayar['dil'] === 'tspl' ? 'tspl' : 'zpl' }}" title="Etiket yazıcısına doğrudan gönderilecek dosya"><span class="ms">download</span> {{ $ayar['dil'] === 'tspl' ? 'TSPL' : 'ZPL' }}</button>
      </form>
      @if ($duzenler)
      <div style="display:flex;gap:8px;margin-top:12px">
        <button type="button" class="b ik" style="flex:1;justify-content:center" onclick="dmPopAc('pDuz')"><span class="ms">edit</span> Düzenle</button>
        <button type="button" class="b tehlike" onclick="dmPopAc('pSil')"><span class="ms">delete</span> Sil</button>
      </div>
      @endif
    </div>
  </div>

  <div class="iki" style="margin-top:12px">
    <div class="kart">
      <div class="kb"><span class="ms">history</span><h3>Geçmiş</h3></div>
      @if ($hareket->isEmpty())<div style="color:var(--sub);font-size:13px">Henüz hareket yok.</div>@endif
      <div class="zaman">
        @foreach ($hareket as $h)
          @php [$ik, $rk, $et] = $hIkon[$h->tip] ?? ['info', '#64748B', $h->tip]; @endphp
          <div class="z" style="--zr:{{ $rk }}">
            <span class="zi"><span class="ms">{{ $ik }}</span></span>
            <div class="zt"><b>{{ $et }}</b>@if ($h->eski || $h->yeni): {{ $h->eski ?: '—' }} → <b>{{ $h->yeni ?: '—' }}</b>@endif
              @if ($h->aciklama)<span>{{ $h->aciklama }}</span>@endif
              <span>{{ \Carbon\Carbon::parse($h->created_at)->format('d.m.Y H:i') }}@if ($h->personel_id) · {{ $persAd[$h->personel_id] ?? '' }}@endif</span></div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="kart">
      <div class="kb"><span class="ms">build</span><h3>Bakım / arıza</h3>@if ($bakim->sum('maliyet') > 0)<span style="font-size:12.5px;font-weight:800;color:var(--sub)">Toplam {{ $tl($bakim->sum('maliyet')) }}</span>@endif</div>
      @if ($bakim->isEmpty())<div style="color:var(--sub);font-size:13px">Bakım ya da arıza kaydı yok.</div>@endif
      @foreach ($bakim as $b)
        <div class="bk"><span class="roz" style="--dr:{{ $b->tip === 'ariza' ? '#F43F5E' : '#6366F1' }}">{{ $b->tip === 'ariza' ? 'Arıza' : 'Bakım' }}</span>
          <div class="t">{{ $b->aciklama ?: '—' }}<span>{{ $trh($b->tarih) }}</span></div>@if ($b->maliyet > 0)<b>{{ $tl($b->maliyet) }}</b>@endif</div>
      @endforeach
    </div>
  </div>
</div></div>

@if ($duzenler)
<div class="dmp-ort" id="pZim" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp dar" method="POST" action="/demirbas/islem">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="islem" value="zimmet">
  <div class="bas"><span class="ms">badge</span><h3>Zimmet ver / al</h3><button type="button" class="kapat" onclick="dmPopKapat('pZim')"><span class="ms">close</span></button></div>
  <div class="gov"><label>Kimde olacak?</label><select name="zimmet_personel_id"><option value="">— Zimmetten al (depoya) —</option>@foreach ($personeller as $p)<option value="{{ $p->id }}" {{ (int) $d->zimmet_personel_id === (int) $p->id ? 'selected' : '' }}>{{ $p->ad }}</option>@endforeach</select>
    <label>Not (isteğe bağlı)</label><input name="aciklama" placeholder="örn. sezon sonu iade edilecek"></div>
  <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('pZim')">Vazgeç</button><button class="db ana" type="submit"><span class="ms">save</span> Kaydet</button></div></form></div>

<div class="dmp-ort" id="pLok" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp dar" method="POST" action="/demirbas/islem">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="islem" value="lokasyon">
  <div class="bas"><span class="ms">place</span><h3>Konum değiştir</h3><button type="button" class="kapat" onclick="dmPopKapat('pLok')"><span class="ms">close</span></button></div>
  <div class="gov"><label>Yeni konum</label><input name="lokasyon" list="dmLokL" value="{{ $d->lokasyon }}" required>
    <label>Not (isteğe bağlı)</label><input name="aciklama"></div>
  <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('pLok')">Vazgeç</button><button class="db ana" type="submit"><span class="ms">save</span> Kaydet</button></div></form></div>

<div class="dmp-ort" id="pBak" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp dar" method="POST" action="/demirbas/islem">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="islem" value="bakim">
  <div class="bas"><span class="ms">build</span><h3>Bakım / arıza kaydı</h3><button type="button" class="kapat" onclick="dmPopKapat('pBak')"><span class="ms">close</span></button></div>
  <div class="gov"><div class="iz"><div><label>Tür</label><select name="tip"><option value="bakim">Bakım</option><option value="ariza">Arıza</option></select></div><div><label>Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}"></div></div>
    <label>Ne yapıldı?</label><input name="aciklama" placeholder="örn. conta değişti, servis geldi">
    <label>Maliyet (₺)</label><input name="maliyet" inputmode="decimal"></div>
  <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('pBak')">Vazgeç</button><button class="db ana" type="submit"><span class="ms">save</span> Kaydet</button></div></form></div>

<div class="dmp-ort" id="pDur" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp dar" method="POST" action="/demirbas/islem">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="islem" value="durum">
  <div class="bas"><span class="ms">flag</span><h3>Durum</h3><button type="button" class="kapat" onclick="dmPopKapat('pDur')"><span class="ms">close</span></button></div>
  <div class="gov"><div class="secenek">@foreach ($durumlar as $k => $a)<label><input type="radio" name="durum" value="{{ $k }}" {{ $d->durum === $k ? 'checked' : '' }}><span>{{ $a }}</span></label>@endforeach</div>
    <label>Not (isteğe bağlı)</label><input name="aciklama"></div>
  <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('pDur')">Vazgeç</button><button class="db ana" type="submit"><span class="ms">save</span> Kaydet</button></div></form></div>

<div class="dmp-ort" id="pDuz" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp" method="POST" action="/demirbas/kaydet" autocomplete="off">@csrf
  <div class="bas"><span class="ms">edit</span><h3>Demirbaşı düzenle</h3><button type="button" class="kapat" onclick="dmPopKapat('pDuz')"><span class="ms">close</span></button></div>
  <div class="gov">@include('demirbas_form', ['d' => $d])</div>
  <div class="alt"><button type="button" class="db ikincil" onclick="dmPopKapat('pDuz')">Vazgeç</button><button class="db ana" type="submit"><span class="ms">save</span> Kaydet</button></div></form></div>

<div class="dmp-ort" id="pSil" onclick="if(event.target===this)dmPopKapat(this.id)"><form class="dmp dar" method="POST" action="/demirbas/islem" style="text-align:center">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="islem" value="sil">
  <div class="gov" style="padding-top:24px"><span class="ms" style="font-size:30px;color:#F43F5E;background:rgba(244,63,94,.12);border-radius:16px;padding:12px">delete</span>
    <h3 style="margin:12px 0 6px">{{ $d->ad }} silinsin mi?</h3><p style="margin:0;font-size:13px;color:var(--sub)">Kart, bakım ve hareket geçmişi silinir. Elden çıktıysa silmek yerine durumunu <b>Hurda</b> yapmak geçmişi korur.</p></div>
  <div class="alt" style="justify-content:center;border:0"><button type="button" class="db ikincil" onclick="dmPopKapat('pSil')">Vazgeç</button><button class="db tehlike" type="submit">Sil</button></div></form></div>
@endif
@include('demirbas_kamera')
<script>
['pZim','pLok','pBak','pDur','pDuz','pSil'].forEach(function(id){ var e=document.getElementById(id); if(e) document.body.appendChild(e); });
function dmPopAc(id){ document.getElementById(id).classList.add('ac'); }
function dmPopKapat(id){ document.getElementById(id).classList.remove('ac'); }
function dmKameraSonuc(kod){ document.getElementById('dmAra').value=kod; document.getElementById('dmOkutForm').submit(); }
document.addEventListener('keydown',function(e){ if(e.key==='Escape') document.querySelectorAll('.dmp-ort.ac').forEach(function(x){ x.classList.remove('ac'); }); });
</script>
@endsection

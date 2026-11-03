@extends('layout.app')
@section('rk_ikon', 'request_quote')
@section('baslik', 'Çek / Senet')
@section('title', 'Çek / Senet')

@section('content')
@php
  $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺';
  $durRenk = ['portfoyde'=>'#5B8DEF','tahsil'=>'#2FBE94','odendi'=>'#2FBE94','ciro'=>'#8B5CF6','karsiliksiz'=>'#F43F5E'];
  $durAd = ['portfoyde'=>'Portföyde','tahsil'=>'Tahsil edildi','odendi'=>'Ödendi','ciro'=>'Ciro edildi','karsiliksiz'=>'Karşılıksız'];
@endphp
<style>
  #cs{max-width:960px;margin:0 auto}
  #cs *{box-sizing:border-box}
  .cs-ozet{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:560px){.cs-ozet{grid-template-columns:1fr}}
  .cs-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .cs-k .l{font-size:12px;color:var(--sub);font-weight:700} .cs-k .b{font-size:24px;font-weight:800;margin-top:4px;letter-spacing:-.5px}
  .cs-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:9px;flex-wrap:wrap;align-items:flex-end}
  .cs-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .cs-form input,.cs-form select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .cs-add{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .cs-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .cs-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .cs-row:first-child{border-top:0}
  .cs-row .yon{font-size:10px;font-weight:800;text-transform:uppercase;padding:2px 8px;border-radius:20px;color:#fff;flex:none}
  .cs-row .ki{flex:1;min-width:0;font-weight:700;color:var(--ink)} .cs-row .ki .mt{font-size:11px;color:var(--sub);font-weight:500}
  .cs-row .va{font-size:11.5px;color:var(--sub);white-space:nowrap} .cs-row .tt{font-weight:800;font-variant-numeric:tabular-nums}
  .cs-row .drz{font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;color:#fff}
  .cs-row select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:8px;padding:4px 7px;font-size:11px}
  .cs-row button{border:0;background:transparent;cursor:pointer;color:var(--sub2)} .cs-row button .material-icons-outlined{font-size:18px}
  .cs-bos{text-align:center;padding:26px;color:var(--sub);font-size:13px}
  .cs-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .cs-msg.ok{background:rgba(16,185,129,.12);color:#047857} .cs-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
</style>
<div class="rk-body">
<div id="cs">
  @if(session('ok'))<div class="cs-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="cs-msg er">{{ session('hata') }}</div>@endif

  <div class="cs-ozet">
    <div class="cs-k"><div class="l">Portföydeki alacak (alınan)</div><div class="b" style="color:#2FBE94">{{ $tl($alacak) }}</div></div>
    <div class="cs-k"><div class="l">Portföydeki borç (verilen)</div><div class="b" style="color:#F43F5E">{{ $tl($borc) }}</div></div>
  </div>

  <form class="cs-form" method="POST" action="/cek-senet/ekle">
    @csrf
    <div><label>Tür</label><select name="tip"><option value="cek">Çek</option><option value="senet">Senet</option></select></div>
    <div><label>Yön</label><select name="yon"><option value="alinan">Alınan (alacak)</option><option value="verilen">Verilen (borç)</option></select></div>
    <div style="flex:1;min-width:130px"><label>Kişi / Firma</label><input name="kisi" style="width:100%" placeholder="Keşideci / lehtar"></div>
    <div><label>Tutar</label><input name="tutar" type="number" step="0.01" style="width:120px" required></div>
    <div><label>Vade</label><input name="vade" type="date"></div>
    <div><label>Banka / No</label><input name="banka" style="width:110px" placeholder="Banka"></div>
    <button class="cs-add" type="submit">+ Ekle</button>
  </form>

  <div class="cs-tab">
    @forelse($liste as $c)
      @php $dr = $durRenk[$c->durum] ?? '#64748B'; $gecti = $c->durum==='portfoyde' && $c->vade && \Carbon\Carbon::parse($c->vade)->isPast(); @endphp
      <div class="cs-row">
        <span class="yon" style="background:{{ $c->yon==='alinan' ? '#2FBE94' : '#F43F5E' }}">{{ $c->tip==='senet'?'SENET':'ÇEK' }} · {{ $c->yon==='alinan'?'AL':'VER' }}</span>
        <div class="ki">{{ $c->kisi ?: '—' }}<div class="mt">{{ $c->banka }}{{ $c->no ? ' · '.$c->no : '' }}</div></div>
        <div class="va">{{ $c->vade ? \Carbon\Carbon::parse($c->vade)->format('d.m.Y') : 'vadesiz' }}@if($gecti)<br><b style="color:#F43F5E">vade geçti</b>@endif</div>
        <span class="tt">{{ $tl($c->tutar) }}</span>
        <span class="drz" style="background:{{ $dr }}">{{ $durAd[$c->durum] ?? $c->durum }}</span>
        <form method="POST" action="/cek-senet/durum" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $c->id }}">
          <select name="durum" onchange="this.form.submit()">
            @foreach(['portfoyde'=>'Portföyde','tahsil'=>'Tahsil','odendi'=>'Ödendi','ciro'=>'Ciro','karsiliksiz'=>'Karşılıksız'] as $k=>$v)
              <option value="{{ $k }}" {{ $c->durum==$k?'selected':'' }}>{{ $v }}</option>
            @endforeach
          </select>
        </form>
        <form method="POST" action="/cek-senet/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $c->id }}"><button type="submit"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="cs-bos">Henüz çek/senet yok. Aldığın ve verdiğin çek-senetleri ekle, vade takibi yap.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

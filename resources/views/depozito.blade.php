@extends('layout.app')
@section('rk_ikon', 'payments')
@section('baslik', 'Depozito / Kapora')
@section('title', 'Depozito / Kapora')

@section('content')
@php
  $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺';
  $durRenk = ['bekliyor'=>'#F59E0B','alindi'=>'#2FBE94','iade'=>'#64748B','tamam'=>'#5B8DEF'];
  $durAd = ['bekliyor'=>'Bekliyor','alindi'=>'Alındı','iade'=>'İade','tamam'=>'Tamamlandı'];
@endphp
<style>
  #dp{max-width:940px;margin:0 auto}
  #dp *{box-sizing:border-box}
  .dp-ozet{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:560px){.dp-ozet{grid-template-columns:1fr}}
  .dp-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .dp-k .l{font-size:12px;color:var(--sub);font-weight:700} .dp-k .b{font-size:24px;font-weight:800;margin-top:4px;letter-spacing:-.5px}
  .dp-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:9px;flex-wrap:wrap;align-items:flex-end}
  .dp-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .dp-form input{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .dp-add{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .dp-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .dp-r{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .dp-r:first-child{border-top:0}
  .dp-r .a{flex:1;min-width:0}.dp-r .a .t{font-weight:700;color:var(--ink)}.dp-r .a .d{font-size:11px;color:var(--sub)}
  .dp-r .tut{font-weight:800;font-variant-numeric:tabular-nums;text-align:right;min-width:90px} .dp-r .tut .al{font-size:10px;color:#2FBE94}
  .dp-r select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:8px;padding:4px 7px;font-size:11px}
  .dp-r button{border:0;background:transparent;cursor:pointer;color:var(--sub2)} .dp-r button .material-icons-outlined{font-size:18px}
  .dp-bos{text-align:center;padding:26px;color:var(--sub);font-size:13px}
  .dp-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .dp-msg.ok{background:rgba(16,185,129,.12);color:#047857} .dp-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .dp-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .dp-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="dp">
  @if(session('ok'))<div class="dp-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="dp-msg er">{{ session('hata') }}</div>@endif

  <div class="dp-ozet">
    <div class="dp-k"><div class="l">Bekleyen kapora (tahsil edilecek)</div><div class="b" style="color:#F59E0B">{{ $tl($bekleyen) }}</div></div>
    <div class="dp-k"><div class="l">Toplam alınan kapora</div><div class="b" style="color:#2FBE94">{{ $tl($alinan) }}</div></div>
  </div>

  <form class="dp-form" method="POST" action="/depozito/ekle">
    @csrf
    <div style="flex:1;min-width:130px"><label>Ad / Etkinlik</label><input name="ad" style="width:100%" placeholder="Yılmaz ailesi nişan" required></div>
    <div><label>Telefon</label><input name="telefon" style="width:120px"></div>
    <div><label>Tarih</label><input name="etkinlik_tarih" type="date"></div>
    <div><label>Kişi</label><input name="kisi" type="number" min="0" style="width:70px"></div>
    <div><label>Kapora tutarı</label><input name="tutar" type="number" step="0.01" style="width:110px"></div>
    <div><label>Alınan</label><input name="alinan" type="number" step="0.01" value="0" style="width:100px"></div>
    <button class="dp-add" type="submit">+ Ekle</button>
  </form>

  <div class="dp-tab">
    @forelse($liste as $d)
      <div class="dp-r">
        <div class="a"><div class="t">{{ $d->ad }}</div><div class="d">{{ $d->etkinlik_tarih ? \Carbon\Carbon::parse($d->etkinlik_tarih)->format('d.m.Y') : 'tarihsiz' }}{{ $d->kisi ? ' · '.$d->kisi.' kişi' : '' }}{{ $d->telefon ? ' · '.$d->telefon : '' }}</div></div>
        <div class="tut">{{ $tl($d->tutar) }}<div class="al">alınan {{ $tl($d->alinan) }}</div></div>
        <form method="POST" action="/depozito/durum" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $d->id }}">
          <select name="durum" onchange="this.form.submit()">
            @foreach(['bekliyor'=>'Bekliyor','alindi'=>'Alındı','iade'=>'İade','tamam'=>'Tamamlandı'] as $k=>$v)<option value="{{ $k }}" {{ $d->durum==$k?'selected':'' }}>{{ $v }}</option>@endforeach
          </select>
        </form>
        <form method="POST" action="/depozito/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><button type="submit"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="dp-bos">Henüz kapora kaydı yok. Grup/etkinlik rezervasyonlarında kapora al, no-show'u azalt.</div>
    @endforelse
  </div>
  <div class="dp-not"><span class="material-icons-outlined">info</span><span>Kapora, özellikle grup/etkinliklerde <b>no-show'u azaltır</b>. Alınan kaporayı kaydet, etkinlik gününde "Tamamlandı" yap. İadeyi "İade" olarak işaretle.</span></div>
</div>
</div>
@endsection

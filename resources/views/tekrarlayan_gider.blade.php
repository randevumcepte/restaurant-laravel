@extends('layout.app')
@section('rk_ikon', 'event_repeat')
@section('baslik', 'Tekrarlayan Giderler')
@section('title', 'Tekrarlayan Giderler')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺'; @endphp
<style>
  #tg{max-width:900px;margin:0 auto}
  #tg *{box-sizing:border-box}
  .tg-ozet{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:560px){.tg-ozet{grid-template-columns:1fr}}
  .tg-k{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .tg-k .l{font-size:12px;color:var(--sub);font-weight:700} .tg-k .b{font-size:24px;font-weight:800;color:var(--ink);margin-top:4px;letter-spacing:-.5px}
  .tg-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:9px;flex-wrap:wrap;align-items:flex-end}
  .tg-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .tg-form input,.tg-form select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .tg-form .f-ad{flex:2;min-width:140px} .tg-form .f-ad input{width:100%}
  .tg-ekle{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .tg-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .tg-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .tg-row:first-child{border-top:0} .tg-row.pasif{opacity:.5}
  .tg-row .ad{flex:1;min-width:0;font-weight:700;color:var(--ink)}
  .tg-row .kat{font-size:10px;font-weight:800;text-transform:uppercase;color:var(--sub);background:var(--card2);padding:2px 8px;border-radius:20px}
  .tg-row .per{font-size:11px;color:var(--sub)}
  .tg-row .tut{font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
  .tg-row button{border:0;background:transparent;cursor:pointer;color:var(--sub2);display:flex;align-items:center}
  .tg-row button .material-icons-outlined{font-size:18px}
  .tg-bos{text-align:center;padding:26px;color:var(--sub);font-size:13px}
  .tg-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .tg-msg.ok{background:rgba(16,185,129,.12);color:#047857} .tg-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
</style>
<div class="rk-body">
<div id="tg">
  @if(session('ok'))<div class="tg-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="tg-msg er">{{ session('hata') }}</div>@endif

  <div class="tg-ozet">
    <div class="tg-k"><div class="l">Aylık tekrarlayan yük</div><div class="b">{{ $tl($aylikTop) }}</div></div>
    <div class="tg-k"><div class="l">Yıllık tekrarlayan yük</div><div class="b">{{ $tl($yillikTop) }} <span style="font-size:13px;color:var(--sub);font-weight:700">+ aylık×12 = {{ $tl($aylikTop*12 + $yillikTop) }}</span></div></div>
  </div>

  <form class="tg-form" method="POST" action="/tekrarlayan-gider/ekle">
    @csrf
    <div class="f-ad"><label>Gider adı</label><input name="ad" placeholder="Kira, internet, muhasebe ücreti…" required></div>
    <div><label>Kategori</label><select name="kategori"><option value="kira">Kira</option><option value="fatura">Fatura/Abonelik</option><option value="maas">Maaş/SGK</option><option value="vergi">Vergi</option><option value="hizmet">Hizmet</option><option value="diger" selected>Diğer</option></select></div>
    <div><label>Tutar</label><input name="tutar" type="number" step="0.01" style="width:110px" required></div>
    <div><label>Periyot</label><select name="periyot"><option value="aylik">Aylık</option><option value="yillik">Yıllık</option></select></div>
    <div><label>Ayın günü</label><input name="gun" type="number" min="1" max="28" value="1" style="width:70px"></div>
    <button class="tg-ekle" type="submit">+ Ekle</button>
  </form>

  <div class="tg-tab">
    @forelse($liste as $g)
      <div class="tg-row {{ $g->aktif ? '' : 'pasif' }}">
        <span class="kat">{{ $g->kategori }}</span>
        <div class="ad">{{ $g->ad }}</div>
        <span class="per">her ayın {{ $g->gun }}’i · {{ $g->periyot === 'yillik' ? 'yıllık' : 'aylık' }}</span>
        <span class="tut">{{ $tl($g->tutar) }}</span>
        <form method="POST" action="/tekrarlayan-gider/durum" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $g->id }}"><input type="hidden" name="aktif" value="{{ $g->aktif ? 0 : 1 }}"><button type="submit" title="{{ $g->aktif ? 'Pasifleştir' : 'Aktifleştir' }}"><span class="material-icons-outlined">{{ $g->aktif ? 'toggle_on' : 'toggle_off' }}</span></button></form>
        <form method="POST" action="/tekrarlayan-gider/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $g->id }}"><button type="submit" title="Sil"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="tg-bos">Henüz tekrarlayan gider yok. Kira, abonelik, muhasebe ücreti gibi sabit giderleri ekle.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

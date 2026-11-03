@extends('layout.app')
@section('rk_ikon', 'account_balance')
@section('baslik', 'Banka Hesapları')
@section('title', 'Banka')

@section('content')
@php $tl = fn($n) => number_format((float) round((float) ($n ?? 0), 2), 2, ',', '.') . '₺'; @endphp
<style>
  #bk{max-width:960px;margin:0 auto}
  #bk *{box-sizing:border-box}
  .bk-top{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff;border-radius:16px;padding:16px 20px;margin-bottom:14px;display:flex;align-items:center;gap:14px}
  .bk-top .l{font-size:12.5px;font-weight:700;opacity:.9} .bk-top .b{font-size:28px;font-weight:800;letter-spacing:-.6px}
  .bk-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-bottom:14px}
  @media(max-width:720px){.bk-grid{grid-template-columns:1fr}}
  .bk-card{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .bk-card.sel{border-color:var(--mor1)}
  .bk-hd{display:flex;align-items:center;gap:10px;margin-bottom:8px}
  .bk-hd .material-icons-outlined{color:var(--mavi)} .bk-hd .ad{font-weight:800;color:var(--ink);font-size:14px}
  .bk-bk{font-size:20px;font-weight:800;color:var(--ink);letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .bk-ib{font-size:11px;color:var(--sub);margin-top:3px;letter-spacing:.3px}
  .bk-acts{display:flex;gap:8px;margin-top:10px}
  .bk-acts a,.bk-acts button{font-size:11.5px;font-weight:700;text-decoration:none;border:1px solid var(--line);background:var(--card2);color:var(--sub);border-radius:8px;padding:5px 11px;cursor:pointer}
  .bk-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;display:flex;gap:9px;flex-wrap:wrap;align-items:flex-end}
  .bk-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .bk-form input,.bk-form select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .bk-add{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .bk-sec{font-size:14px;font-weight:800;color:var(--ink);margin:18px 2px 10px}
  .bk-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .bk-row{display:flex;align-items:center;gap:12px;padding:11px 16px;border-top:1px solid var(--line);font-size:13px}
  .bk-row:first-child{border-top:0} .bk-row .ac{flex:1;min-width:0;color:var(--ink);font-weight:600}
  .bk-row .tr{font-size:11px;color:var(--sub)} .bk-row .tt{font-weight:800;font-variant-numeric:tabular-nums}
  .bk-row .tt.g{color:#2FBE94} .bk-row .tt.c{color:#F43F5E}
  .bk-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .bk-msg.ok{background:rgba(16,185,129,.12);color:#047857} .bk-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .bk-bos{text-align:center;padding:24px;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="bk">
  @if(session('ok'))<div class="bk-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="bk-msg er">{{ session('hata') }}</div>@endif

  <div class="bk-top"><span class="material-icons-outlined" style="font-size:30px">account_balance</span><div><div class="l">Toplam banka pozisyonu (aktif hesaplar)</div><div class="b">{{ $tl($toplam) }}</div></div></div>

  <div class="bk-grid">
    @forelse($hesaplar as $h)
      <div class="bk-card {{ $sec==$h->id ? 'sel' : '' }}">
        <div class="bk-hd"><span class="material-icons-outlined">account_balance</span><span class="ad">{{ $h->banka_adi }}{{ $h->hesap_adi ? ' · '.$h->hesap_adi : '' }}</span></div>
        <div class="bk-bk">{{ $tl($h->bakiye) }} <span style="font-size:12px;color:var(--sub)">{{ $h->para_birimi }}</span></div>
        @if($h->iban)<div class="bk-ib">{{ $h->iban }}</div>@endif
        <div class="bk-acts">
          <a href="/banka?hesap={{ $h->id }}">Hareketler</a>
          <form method="POST" action="/banka/sil" style="display:inline" onsubmit="return confirm('Hesap ve hareketleri silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $h->id }}"><button type="submit">Sil</button></form>
        </div>
      </div>
    @empty
      <div class="bk-bos" style="grid-column:1/-1">Henüz banka hesabı yok — aşağıdan ekle.</div>
    @endforelse
  </div>

  <form class="bk-form" method="POST" action="/banka/ekle">
    @csrf
    <div><label>Banka</label><input name="banka_adi" placeholder="Ziraat, İş Bankası…" required></div>
    <div><label>Hesap adı</label><input name="hesap_adi" placeholder="Ana hesap"></div>
    <div><label>IBAN</label><input name="iban" placeholder="TR.." style="width:200px"></div>
    <div><label>Açılış bakiyesi</label><input name="bakiye" type="number" step="0.01" value="0" style="width:120px"></div>
    <button class="bk-add" type="submit">+ Hesap Ekle</button>
  </form>

  @if($sec)
    <div class="bk-sec">Hareketler</div>
    <form class="bk-form" method="POST" action="/banka/hareket" style="margin-bottom:12px">
      @csrf<input type="hidden" name="hesap_id" value="{{ $sec }}">
      <div><label>Tarih</label><input name="tarih" type="date" value="{{ now()->toDateString() }}"></div>
      <div><label>Tip</label><select name="tip"><option value="giris">Para Girişi (+)</option><option value="cikis">Para Çıkışı (−)</option></select></div>
      <div><label>Tutar</label><input name="tutar" type="number" step="0.01" style="width:120px" required></div>
      <div style="flex:1;min-width:140px"><label>Açıklama</label><input name="aciklama" style="width:100%" placeholder="EFT, havale, ödeme…"></div>
      <button class="bk-add" type="submit">İşle</button>
    </form>
    <div class="bk-tab">
      @forelse($hareketler as $m)
        <div class="bk-row">
          <div class="ac">{{ $m->aciklama ?: ($m->tip==='giris'?'Para girişi':'Para çıkışı') }}<div class="tr">{{ \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') }}</div></div>
          <span class="tt {{ $m->tip==='giris'?'g':'c' }}">{{ $m->tip==='giris'?'+':'−' }} {{ $tl($m->tutar) }}</span>
        </div>
      @empty
        <div class="bk-bos">Bu hesapta hareket yok.</div>
      @endforelse
    </div>
  @endif
</div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Mal Kabul')
@section('rk_ikon', 'inventory')
@section('baslik', 'Mal Kabul / İrsaliye')
@section('rk_alt', 'Gelen malı kontrol et → stok otomatik girer (lot/SKT ile)')

@section('content')
@php
  $kabuller = $kabuller ?? collect();
  $siparis = $siparis ?? null;
  $siparisKalem = $siparisKalem ?? collect();
  $tedarikciler = $tedarikciler ?? collect();
  $malzemeler = $malzemeler ?? collect();
  $birimler = $birimler ?? collect();
  $acikSiparisler = $acikSiparisler ?? collect();
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
@endphp

@if (session('mk_ok'))<div class="rk-flash ok">{{ session('mk_ok') }}</div>@endif
@if (session('mk_hata'))<div class="rk-flash err">{{ session('mk_hata') }}</div>@endif

{{-- Açık siparişten çek --}}
@if ($acikSiparisler->count() && !$siparis)
  <div class="rk-card" style="margin-bottom:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <span class="material-icons-outlined" style="color:var(--mavi)">receipt_long</span>
    <div style="flex:1;min-width:200px"><div style="font-weight:700;color:var(--ink);font-size:13.5px">Açık siparişten mal kabul</div><div class="rk-sub2" style="font-size:12px">Bir siparişi seç, kalemleri otomatik gelsin.</div></div>
    <select class="rk-inp" style="max-width:260px" onchange="if(this.value)location.href='/mal-kabul?siparis_id='+this.value">
      <option value="">— Açık sipariş seç —</option>
      @foreach ($acikSiparisler as $s)<option value="{{ $s->id }}">{{ $s->siparis_no }} · {{ $s->tedarikci }}</option>@endforeach
    </select>
  </div>
@endif

{{-- ===== MAL KABUL FORMU ===== --}}
<div class="rk-card" style="margin-bottom:18px">
  <div class="rk-bolum" style="margin-bottom:12px"><span class="cubuk" style="background:var(--yesil)"></span><span class="b">{{ $siparis ? ('Mal Kabul · ' . $siparis->siparis_no) : 'Yeni Mal Kabul' }}</span>
    @if($siparis)<a href="/mal-kabul" class="rk-btn bos" style="margin-left:auto;padding:5px 10px">Serbest Giriş</a>@endif</div>
  <form method="POST" action="/mal-kabul/olustur" id="mkForm">
    @csrf
    <input type="hidden" name="kalemler" id="mkKalemler">
    @if ($siparis)<input type="hidden" name="siparis_id" value="{{ $siparis->id }}"><input type="hidden" name="tedarikci_id" value="{{ $siparis->tedarikci_id }}">@endif
    <div class="rk-grid" style="grid-template-columns:2fr 1fr 1fr;gap:10px">
      @unless ($siparis)
        <div><label class="rk-lbl" style="margin-top:0">Tedarikçi</label><select name="tedarikci_id" class="rk-inp"><option value="">— Seç —</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
      @else
        <div><label class="rk-lbl" style="margin-top:0">Sipariş</label><input class="rk-inp" value="{{ $siparis->siparis_no }}" disabled></div>
      @endunless
      <div><label class="rk-lbl" style="margin-top:0">İrsaliye No</label><input type="text" name="irsaliye_no" placeholder="İrsaliye no" class="rk-inp"></div>
      <div><label class="rk-lbl" style="margin-top:0">Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="rk-inp"></div>
    </div>

    <div class="rk-bolum" style="margin-top:14px"><span class="cubuk" style="background:var(--yesil)"></span><span class="b">Gelen Kalemler</span>
      @unless ($siparis)<button type="button" class="rk-btn bos" style="margin-left:auto;padding:6px 11px" onclick="mkSatirEkle()"><span class="material-icons-outlined" style="font-size:15px">add</span> Kalem Ekle</button>@endunless</div>

    <div style="overflow-x:auto">
    <div id="mkSatirlar" style="min-width:720px">
      <div style="display:grid;grid-template-columns:2.4fr 1fr 1fr 1fr 1.3fr 1.3fr 1.3fr;gap:6px;font-size:10.5px;font-weight:700;color:var(--sub2);text-transform:uppercase;padding:0 2px 4px">
        <span>Malzeme</span><span>Sipariş</span><span>Gelen</span><span>Kabul</span><span>Birim Fiyat</span><span>Lot No</span><span>SKT</span>
      </div>
      @forelse ($siparisKalem as $k)
        <div class="mk-satir" style="display:grid;grid-template-columns:2.4fr 1fr 1fr 1fr 1.3fr 1.3fr 1.3fr;gap:6px;align-items:center;margin-bottom:6px"
             data-malzeme="{{ $k->malzeme_id }}" data-birim="{{ $k->alis_birim_id }}" data-skid="{{ $k->id }}" data-sipmik="{{ $k->miktar }}">
          <div style="font-weight:600;font-size:12.5px">{{ $k->malzeme }} <span class="rk-sub2" style="font-size:10px">/ {{ $k->birim }}</span></div>
          <div class="rk-sub2" style="font-size:12px">{{ $num($k->miktar) }} <span style="font-size:10px">(kalan {{ $num($k->kalan) }})</span></div>
          <input type="number" class="rk-inp mk-gelen" step="0.001" min="0" value="{{ $num($k->kalan) }}" style="padding:6px">
          <input type="number" class="rk-inp mk-kabul" step="0.001" min="0" value="{{ $num($k->kalan) }}" style="padding:6px">
          <input type="number" class="rk-inp mk-fiyat" step="0.0001" min="0" value="{{ (float) $k->birim_fiyat }}" style="padding:6px">
          <input type="text" class="rk-inp mk-lot" placeholder="Lot" style="padding:6px">
          <input type="date" class="rk-inp mk-skt" style="padding:6px">
        </div>
      @empty
      @endforelse
    </div>
    </div>

    <label class="rk-lbl">Not (opsiyonel)</label>
    <input type="text" name="notlar" placeholder="Mal kabul notu" class="rk-inp">
    <button type="submit" class="rk-btn lg" style="background:var(--yesil);margin-top:14px"><span class="material-icons-outlined" style="color:#fff;font-size:20px">check_circle</span> Mal Kabulü Onayla (Stok Gir)</button>
  </form>
</div>

{{-- ===== SON MAL KABULLER ===== --}}
<div class="rk-bolum"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Son Mal Kabuller</span>
  <span class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">{{ $kabuller->count() }}</span></div>
@if ($kabuller->isEmpty())
  <div class="rk-card" style="text-align:center;padding:24px"><div class="rk-sub">Henüz mal kabul yok.</div></div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th>Tarih</th><th>Tedarikçi</th><th>İrsaliye</th><th class="sag"></th></tr></thead>
      <tbody>
        @foreach ($kabuller as $m)
          <tr>
            <td class="rk-sub2">{{ $m->tarih ? \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') : '—' }}</td>
            <td style="font-weight:600">{{ $m->tedarikci }}</td>
            <td class="rk-sub">{{ $m->irsaliye_no ?: '—' }}</td>
            <td class="sag"><a href="/mal-kabul-detay?id={{ $m->id }}" class="rk-btn bos" style="padding:6px 11px"><span class="material-icons-outlined" style="font-size:15px">visibility</span> Detay</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<script>
const MK_MALZ = @json($malzemeler->map(fn($m)=>['id'=>(int)$m->id,'ad'=>$m->ad,'birim'=>(int)($m->temel_birim_id ?? 0)])->values());
const MK_BIRIM = @json($birimler->map(fn($b)=>['id'=>(int)$b->id,'ks'=>$b->kisaltma])->values());
const MK_SIPARIS = {{ $siparis ? 'true' : 'false' }};
function mkBirimOpt(){ return MK_BIRIM.map(b=>`<option value="${b.id}">${b.ks}</option>`).join(''); }
function mkMalzOpt(){ return '<option value="">Malzeme…</option>'+MK_MALZ.map(m=>`<option value="${m.id}" data-birim="${m.birim}">${m.ad}</option>`).join(''); }
function mkSatirEkle(){
  const d=document.createElement('div'); d.className='mk-satir mk-serbest';
  d.style.cssText='display:grid;grid-template-columns:2.4fr 1fr 1fr 1fr 1.3fr 1.3fr 1.3fr;gap:6px;align-items:center;margin-bottom:6px';
  d.innerHTML=`<select class="rk-inp mk-m" style="padding:6px" onchange="const b=this.selectedOptions[0]?.dataset.birim;if(b)this.closest('.mk-satir').querySelector('.mk-b').value=b;">${mkMalzOpt()}</select>
    <select class="rk-inp mk-b" style="padding:6px">${mkBirimOpt()}</select>
    <input type="number" class="rk-inp mk-gelen" placeholder="Gelen" step="0.001" min="0" style="padding:6px">
    <input type="number" class="rk-inp mk-kabul" placeholder="Kabul" step="0.001" min="0" style="padding:6px">
    <input type="number" class="rk-inp mk-fiyat" placeholder="Fiyat" step="0.0001" min="0" style="padding:6px">
    <input type="text" class="rk-inp mk-lot" placeholder="Lot" style="padding:6px">
    <input type="date" class="rk-inp mk-skt" style="padding:6px">`;
  document.getElementById('mkSatirlar').appendChild(d);
}
if(!MK_SIPARIS){ mkSatirEkle(); }
document.getElementById('mkForm').addEventListener('submit',function(e){
  const kal=[];
  document.querySelectorAll('.mk-satir').forEach(r=>{
    let mid, birim, sipMik=null, skId=null;
    if(r.classList.contains('mk-serbest')){ mid=parseInt(r.querySelector('.mk-m').value)||0; birim=parseInt(r.querySelector('.mk-b').value)||0; }
    else { mid=parseInt(r.dataset.malzeme)||0; birim=parseInt(r.dataset.birim)||0; sipMik=parseFloat(r.dataset.sipmik)||null; skId=parseInt(r.dataset.skid)||null; }
    const gelen=parseFloat(r.querySelector('.mk-gelen').value)||0;
    const kabul=parseFloat(r.querySelector('.mk-kabul').value)|| gelen;
    if(!mid || kabul<=0) return;
    kal.push({malzeme_id:mid,alis_birim_id:birim,siparis_kalem_id:skId,siparis_miktar:sipMik,gelen:gelen,kabul:kabul,red:Math.max(0,gelen-kabul),birim_fiyat:parseFloat(r.querySelector('.mk-fiyat').value)||0,lot_no:r.querySelector('.mk-lot').value||'',skt:r.querySelector('.mk-skt').value||''});
  });
  if(!kal.length){ e.preventDefault(); alert('Kabul edilen en az bir kalem girin.'); return; }
  document.getElementById('mkKalemler').value=JSON.stringify(kal);
});
</script>
@endsection

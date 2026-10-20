@extends('layout.app')
@section('title', 'Satınalma Siparişi')
@section('rk_ikon', 'shopping_cart_checkout')
@section('baslik', 'Satınalma Siparişi')
@section('rk_alt', 'Sipariş ver → mal kabul → fatura · tedarikçiye gönder')

@section('rk_araclar')
  <button type="button" class="rk-btn" style="background:var(--mor1)" onclick="saAc()">
    <span class="material-icons-outlined" style="font-size:18px">add</span> Yeni Sipariş
  </button>
@endsection

@section('content')
@php
  $siparisler = $siparisler ?? collect();
  $tedarikciler = $tedarikciler ?? collect();
  $malzemeler = $malzemeler ?? collect();
  $birimler = $birimler ?? collect();
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $durumRenk = ['taslak'=>'var(--sub)','gonderildi'=>'var(--mavi)','kismi'=>'var(--amber)','tamamlandi'=>'var(--yesil)','iptal'=>'var(--kirmizi)'];
  $durumAd = ['taslak'=>'Taslak','gonderildi'=>'Gönderildi','kismi'=>'Kısmi Teslim','tamamlandi'=>'Tamamlandı','iptal'=>'İptal'];
@endphp

@if (session('sa_ok'))<div class="rk-flash ok">{{ session('sa_ok') }}</div>@endif
@if (session('sa_hata'))<div class="rk-flash err">{{ session('sa_hata') }}</div>@endif

@if ($siparisler->isEmpty())
  <div class="rk-card" style="text-align:center;padding:40px">
    <span class="material-icons-outlined" style="font-size:52px;color:var(--line)">shopping_cart</span>
    <div class="rk-sub" style="margin-top:10px">Henüz satınalma siparişi yok. "Yeni Sipariş" ile başla.</div>
  </div>
@else
  <div class="rk-tablo">
    <table>
      <thead><tr><th style="width:16%">Sipariş No</th><th>Tedarikçi</th><th class="sag">Tarih</th><th class="orta">Durum</th><th class="sag">Tutar</th><th class="sag"></th></tr></thead>
      <tbody>
        @foreach ($siparisler as $s)
          @php $dr = $durumRenk[$s->durum] ?? 'var(--sub)'; @endphp
          <tr>
            <td style="font-weight:700">{{ $s->siparis_no }}</td>
            <td>{{ $s->tedarikci }}</td>
            <td class="sag rk-sub2">{{ $s->tarih ? \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') : '—' }}</td>
            <td class="orta"><span class="rk-rozet" style="color:{{ $dr }};background:color-mix(in srgb,{{ $dr }} 13%,transparent);border-color:color-mix(in srgb,{{ $dr }} 40%,transparent)">{{ $durumAd[$s->durum] ?? $s->durum }}</span></td>
            <td class="sag" style="font-weight:800">{{ $tl($s->genel_toplam) }}</td>
            <td class="sag"><a href="/satinalma-siparis-detay?id={{ $s->id }}" class="rk-btn bos" style="padding:6px 11px"><span class="material-icons-outlined" style="font-size:15px">visibility</span> Detay</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

{{-- ===== YENİ SİPARİŞ MODAL ===== --}}
<div id="saModal" style="display:none;position:fixed;inset:0;z-index:60;background:rgba(15,23,42,.6);align-items:flex-start;justify-content:center;padding:24px;overflow:auto">
  <div class="rk-card" style="width:100%;max-width:760px;margin:auto">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div style="font-size:18px;font-weight:800;color:var(--ink)">Yeni Satınalma Siparişi</div>
      <button type="button" onclick="saKapat()" style="background:none;border:none;font-size:24px;color:var(--sub);cursor:pointer">&times;</button>
    </div>
    <form method="POST" action="/satinalma-siparis/ekle" id="saForm">
      @csrf
      <input type="hidden" name="kalemler" id="saKalemler">
      <input type="hidden" name="gonder" id="saGonder" value="">
      <div class="rk-grid" style="grid-template-columns:2fr 1fr 1fr;gap:10px">
        <div><label class="rk-lbl" style="margin-top:0">Tedarikçi</label>
          <select name="tedarikci_id" class="rk-inp"><option value="">— Seç —</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
        <div><label class="rk-lbl" style="margin-top:0">Sipariş Tarihi</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="rk-inp"></div>
        <div><label class="rk-lbl" style="margin-top:0">Beklenen Teslim</label><input type="date" name="beklenen_teslim" class="rk-inp"></div>
      </div>

      <div class="rk-bolum" style="margin-top:14px"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Kalemler</span>
        <button type="button" class="rk-btn bos" style="margin-left:auto;padding:6px 11px" onclick="saSatirEkle()"><span class="material-icons-outlined" style="font-size:15px">add</span> Kalem Ekle</button></div>
      <div id="saSatirlar"></div>

      <div style="display:flex;justify-content:flex-end;gap:24px;margin-top:10px;padding-top:10px;border-top:1px solid var(--line)">
        <div class="rk-sub2" style="font-size:12.5px">Ara Toplam: <b class="rk-ink" id="saAra">0,00₺</b></div>
        <div class="rk-sub2" style="font-size:12.5px">KDV: <b class="rk-ink" id="saKdv">0,00₺</b></div>
        <div style="font-size:14px;font-weight:800;color:var(--mor1)">Genel: <span id="saGenel">0,00₺</span></div>
      </div>

      <label class="rk-lbl">Not (opsiyonel)</label>
      <input type="text" name="notlar" placeholder="Sipariş notu" class="rk-inp">

      <div style="display:flex;gap:10px;margin-top:16px">
        <button type="submit" class="rk-btn bos" style="flex:1;justify-content:center;padding:12px" onclick="document.getElementById('saGonder').value=''">Taslak Kaydet</button>
        <button type="submit" class="rk-btn" style="flex:1;justify-content:center;padding:12px;background:var(--mor1)" onclick="document.getElementById('saGonder').value='1'">
          <span class="material-icons-outlined" style="color:#fff;font-size:18px">send</span> Oluştur & Gönder
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const SA_MALZ = @json($malzemeler->map(fn($m)=>['id'=>(int)$m->id,'ad'=>$m->ad,'birim'=>(int)($m->temel_birim_id ?? 0)])->values());
const SA_BIRIM = @json($birimler->map(fn($b)=>['id'=>(int)$b->id,'ad'=>$b->ad,'ks'=>$b->kisaltma])->values());
function saAc(){ document.getElementById('saModal').style.display='flex'; if(!document.querySelector('.sa-satir')) saSatirEkle(); }
function saKapat(){ document.getElementById('saModal').style.display='none'; }
function saMalzOpt(){ return '<option value="">Malzeme…</option>'+SA_MALZ.map(m=>`<option value="${m.id}" data-birim="${m.birim}">${m.ad}</option>`).join(''); }
function saBirimOpt(){ return SA_BIRIM.map(b=>`<option value="${b.id}">${b.ks||b.ad}</option>`).join(''); }
function saSatirEkle(){
  const d=document.createElement('div'); d.className='sa-satir';
  d.style.cssText='display:grid;grid-template-columns:3fr 1.2fr 1.3fr 1.3fr 1fr 1fr auto;gap:6px;align-items:center;margin-bottom:6px';
  d.innerHTML=`<select class="rk-inp sa-m" style="padding:7px" onchange="saMalzChg(this)">${saMalzOpt()}</select>
    <input type="number" class="rk-inp sa-mik" placeholder="Miktar" step="0.001" min="0" style="padding:7px" oninput="saHesap()">
    <select class="rk-inp sa-b" style="padding:7px">${saBirimOpt()}</select>
    <input type="number" class="rk-inp sa-f" placeholder="Birim fiyat" step="0.0001" min="0" style="padding:7px" oninput="saHesap()">
    <input type="number" class="rk-inp sa-i" placeholder="İsk%" step="0.01" min="0" style="padding:7px" oninput="saHesap()">
    <input type="number" class="rk-inp sa-k" placeholder="KDV%" step="0.01" min="0" value="10" style="padding:7px" oninput="saHesap()">
    <button type="button" class="rk-btn bos" style="padding:7px 9px;color:var(--kirmizi)" onclick="this.parentElement.remove();saHesap()"><span class="material-icons-outlined" style="font-size:16px">close</span></button>`;
  document.getElementById('saSatirlar').appendChild(d);
}
function saMalzChg(sel){ const b=sel.selectedOptions[0]?.dataset.birim; if(b){ const row=sel.closest('.sa-satir'); const bs=row.querySelector('.sa-b'); if(bs) bs.value=b; } }
function saHesap(){
  let ara=0,kdv=0;
  document.querySelectorAll('.sa-satir').forEach(r=>{
    const mik=parseFloat(r.querySelector('.sa-mik').value)||0, f=parseFloat(r.querySelector('.sa-f').value)||0;
    const iy=parseFloat(r.querySelector('.sa-i').value)||0, ky=parseFloat(r.querySelector('.sa-k').value)||0;
    const brut=mik*f, isk=brut*iy/100, net=brut-isk; ara+=net; kdv+=net*ky/100;
  });
  const f=v=>v.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
  document.getElementById('saAra').textContent=f(ara);
  document.getElementById('saKdv').textContent=f(kdv);
  document.getElementById('saGenel').textContent=f(ara+kdv);
}
document.getElementById('saForm').addEventListener('submit',function(e){
  const kal=[];
  document.querySelectorAll('.sa-satir').forEach(r=>{
    const mid=parseInt(r.querySelector('.sa-m').value)||0, mik=parseFloat(r.querySelector('.sa-mik').value)||0;
    if(!mid||mik<=0) return;
    kal.push({malzeme_id:mid,miktar:mik,alis_birim_id:parseInt(r.querySelector('.sa-b').value)||0,birim_fiyat:parseFloat(r.querySelector('.sa-f').value)||0,iskonto_yuzde:parseFloat(r.querySelector('.sa-i').value)||0,kdv_yuzde:parseFloat(r.querySelector('.sa-k').value)||0});
  });
  if(!kal.length){ e.preventDefault(); alert('En az bir geçerli kalem ekleyin.'); return; }
  document.getElementById('saKalemler').value=JSON.stringify(kal);
});
document.getElementById('saModal').addEventListener('click',function(e){ if(e.target===this) saKapat(); });
</script>
@endsection

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

    @unless ($siparis)
    <div class="rk-card" style="background:color-mix(in srgb,var(--mor1) 5%,transparent);border:1px dashed color-mix(in srgb,var(--mor1) 35%,transparent);margin:14px 0 4px;padding:12px">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <span class="material-icons-outlined" style="color:var(--mor1)">bolt</span>
        <div style="font-weight:800;color:var(--ink);font-size:13px">Hızlı doldur</div>
        <div class="rk-sub2" style="font-size:11.5px;flex:1;min-width:160px">Barkod okut ya da irsaliye/fatura fotoğrafı çek — kalemler kendiliğinden gelsin, sen sadece kontrol et.</div>
      </div>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;align-items:center">
        <input id="mkBarkodInp" class="rk-inp" placeholder="Barkod okut (okuyucu ya da elle yaz + Enter)" style="max-width:300px" autocomplete="off"
               onkeydown="if(event.key==='Enter'){event.preventDefault();mkBarkodCoz(this.value);this.value='';}">
        <button type="button" class="rk-btn bos" id="mkKameraBtn" style="padding:7px 12px" onclick="mkKameraAc()"><span class="material-icons-outlined" style="font-size:16px">qr_code_scanner</span> Kamera ile barkod</button>
        <label class="rk-btn bos" style="padding:7px 12px;cursor:pointer"><span class="material-icons-outlined" style="font-size:16px">photo_camera</span> İrsaliye/Fatura fotoğrafı
          <input type="file" accept="image/*" capture="environment" style="display:none" onchange="mkFotoSec(this)"></label>
        <span id="mkDurum" class="rk-sub2" style="font-size:12px"></span>
      </div>
      <video id="mkKamera" playsinline muted style="display:none;width:100%;max-width:360px;border-radius:12px;margin-top:10px;background:#000"></video>
    </div>
    @endunless

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

    {{-- ===== FATURA TOPLAMLARI ===== --}}
    <div class="rk-bolum" style="margin-top:14px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">Fatura Toplamları</span>
      <span class="rk-sub2" style="font-size:11px;margin-left:8px">İrsaliye/fatura fotoğrafından otomatik gelir; elle de düzeltebilirsin.</span></div>
    <div class="rk-grid" style="grid-template-columns:1fr 1fr 1fr;gap:10px">
      <div><label class="rk-lbl" style="margin-top:0">Ara Toplam (KDV hariç)</label><input type="number" name="ara_toplam" id="mkAraToplam" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
      <div><label class="rk-lbl" style="margin-top:0">KDV</label><input type="number" name="kdv_tutar" id="mkKdv" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
      <div><label class="rk-lbl" style="margin-top:0">Genel Toplam</label><input type="number" name="genel_toplam" id="mkGenelToplam" step="0.01" min="0" placeholder="0,00" class="rk-inp"></div>
    </div>
    <div id="mkToplamUyari" class="rk-sub2" style="font-size:12px;margin-top:8px">Kalem toplamı (otomatik): <b id="mkKalemToplam">0,00 ₺</b></div>

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
function mkSatirEkle(pre){
  const d=document.createElement('div'); d.className='mk-satir mk-serbest';
  d.style.cssText='display:grid;grid-template-columns:2.4fr 1fr 1fr 1fr 1.3fr 1.3fr 1.3fr;gap:6px;align-items:center;margin-bottom:6px';
  d.innerHTML=`<select class="rk-inp mk-m" style="padding:6px" onchange="const b=this.selectedOptions[0]?.dataset.birim;if(b)this.closest('.mk-satir').querySelector('.mk-b').value=b;this.style.outline='';">${mkMalzOpt()}</select>
    <select class="rk-inp mk-b" style="padding:6px">${mkBirimOpt()}</select>
    <input type="number" class="rk-inp mk-gelen" placeholder="Gelen" step="0.001" min="0" style="padding:6px">
    <input type="number" class="rk-inp mk-kabul" placeholder="Kabul" step="0.001" min="0" style="padding:6px">
    <input type="number" class="rk-inp mk-fiyat" placeholder="Fiyat" step="0.0001" min="0" style="padding:6px">
    <input type="text" class="rk-inp mk-lot" placeholder="Lot" style="padding:6px">
    <input type="date" class="rk-inp mk-skt" style="padding:6px">`;
  document.getElementById('mkSatirlar').appendChild(d);
  if(pre){
    if(pre.malzeme_id){ const ms=d.querySelector('.mk-m'); ms.value=pre.malzeme_id; const b=ms.selectedOptions[0]?.dataset.birim; if(b)d.querySelector('.mk-b').value=b; }
    if(pre.birim_id){ d.querySelector('.mk-b').value=pre.birim_id; }
    if(pre.miktar!=null){ d.querySelector('.mk-gelen').value=pre.miktar; d.querySelector('.mk-kabul').value=pre.miktar; }
    if(pre.fiyat!=null){ d.querySelector('.mk-fiyat').value=pre.fiyat; }
    if(pre.ad && !pre.malzeme_id){ const ms=d.querySelector('.mk-m'); ms.style.outline='2px solid var(--kirmizi)'; ms.title='Fotoğrafta "'+pre.ad+'" okundu ama eşleşen malzeme bulunamadı — elle seç'; }
  }
  return d;
}
if(!MK_SIPARIS){ mkSatirEkle(); }

// ---- CSRF + durum/bip yardimcilari ----
function mkCsrf(){ return document.querySelector('#mkForm input[name=_token]')?.value || ''; }
function mkDurumYaz(t,ok){ const el=document.getElementById('mkDurum'); if(!el)return; el.textContent=t; el.style.color = ok===false?'var(--kirmizi)':(ok===true?'var(--yesil)':'var(--sub2)'); }
function mkBip(ok){ try{ const A=window.AudioContext||window.webkitAudioContext; if(!A)return; const a=new A(),o=a.createOscillator(),g=a.createGain(); o.connect(g);g.connect(a.destination); o.frequency.value=ok?880:220; g.gain.value=0.05; o.start(); setTimeout(()=>{o.stop();a.close();},120);}catch(e){} }

// ---- BARKOD -> malzeme ----
async function mkBarkodCoz(kod){
  kod=(kod||'').trim(); if(!kod) return;
  mkDurumYaz('Barkod aranıyor…',null);
  try{
    const res=await fetch('/mal-kabul/barkod-coz?kod='+encodeURIComponent(kod));
    const j=await res.json();
    if(j.ok&&j.bulundu){
      let row=[...document.querySelectorAll('.mk-serbest')].find(r=>!r.querySelector('.mk-m').value);
      if(row){ const ms=row.querySelector('.mk-m'); ms.value=j.id; const b=ms.selectedOptions[0]?.dataset.birim; if(b)row.querySelector('.mk-b').value=b; ms.style.outline=''; }
      else row=mkSatirEkle({malzeme_id:j.id});
      if(j.maliyet>0 && !row.querySelector('.mk-fiyat').value) row.querySelector('.mk-fiyat').value=j.maliyet;
      row.querySelector('.mk-gelen').focus();
      mkBip(true); mkDurumYaz('Eklendi: '+j.ad,true);
    } else { mkBip(false); mkDurumYaz(j.hata||'Barkod bulunamadı',false); }
  }catch(e){ mkBip(false); mkDurumYaz('Barkod çözülemedi',false); }
}

// ---- KAMERA ile barkod (BarcodeDetector varsa) ----
let mkStream=null,mkDet=null,mkTaramaAcik=false;
async function mkKameraAc(){
  const v=document.getElementById('mkKamera');
  if(mkTaramaAcik){ mkKameraKapat(); return; }
  if(!('BarcodeDetector' in window)){ mkDurumYaz('Bu tarayıcı kamera-barkod desteklemiyor; USB/Bluetooth okuyucu ya da fotoğraf kullan.',false); return; }
  try{
    mkDet=new BarcodeDetector({formats:['ean_13','ean_8','code_128','code_39','upc_a','upc_e','itf','codabar','qr_code']});
    mkStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}});
    v.srcObject=mkStream; v.style.display='block'; await v.play(); mkTaramaAcik=true;
    const b=document.getElementById('mkKameraBtn'); if(b)b.innerHTML='<span class="material-icons-outlined" style="font-size:16px">close</span> Kamerayı kapat';
    mkDurumYaz('Barkodu kameraya göster…',null);
    const tik=async()=>{ if(!mkTaramaAcik)return;
      try{ const kods=await mkDet.detect(v); if(kods&&kods.length){ const kod=kods[0].rawValue; mkKameraKapat(); mkBarkodCoz(kod); return; } }catch(e){}
      requestAnimationFrame(tik); };
    requestAnimationFrame(tik);
  }catch(e){ mkDurumYaz('Kamera açılamadı: '+e.message,false); }
}
function mkKameraKapat(){ mkTaramaAcik=false; const v=document.getElementById('mkKamera'); if(mkStream){mkStream.getTracks().forEach(t=>t.stop());mkStream=null;} if(v)v.style.display='none'; const b=document.getElementById('mkKameraBtn'); if(b)b.innerHTML='<span class="material-icons-outlined" style="font-size:16px">qr_code_scanner</span> Kamera ile barkod'; }

// ---- IRSALIYE/FATURA FOTOGRAFI -> AI OCR ----
function mkKucult(file,max,q){ return new Promise((resolve,reject)=>{
  const img=new Image(), rd=new FileReader();
  rd.onload=()=>{ img.onload=()=>{ let w=img.naturalWidth,h=img.naturalHeight; const sc=Math.min(1,max/Math.max(w,h)); w=Math.round(w*sc);h=Math.round(h*sc);
    const c=document.createElement('canvas');c.width=w;c.height=h;c.getContext('2d').drawImage(img,0,0,w,h);
    try{ const durl=c.toDataURL('image/jpeg',q); resolve({data:durl.split(',')[1],mime:'image/jpeg'}); }catch(e){ reject(e); } };
    img.onerror=()=>reject(new Error('Görsel açılamadı')); img.src=rd.result; };
  rd.onerror=()=>reject(new Error('Dosya okunamadı')); rd.readAsDataURL(file);
}); }
async function mkFotoSec(input){
  const f=input.files&&input.files[0]; if(!f){ return; }
  mkDurumYaz('Fotoğraf okunuyor (AI)… birkaç saniye',null);
  try{
    const im=await mkKucult(f,1600,0.82);
    const res=await fetch('/mal-kabul/foto-oku',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':mkCsrf(),'Accept':'application/json'},body:JSON.stringify({resim:im.data,mime:im.mime})});
    const j=await res.json();
    if(!j.ok){ mkDurumYaz(j.hata||'Fotoğraf okunamadı',false); input.value=''; return; }
    if(j.irsaliye_no){ const el=document.querySelector('#mkForm [name=irsaliye_no]'); if(el)el.value=j.irsaliye_no; }
    if(j.tarih){ const el=document.querySelector('#mkForm [name=tarih]'); if(el)el.value=j.tarih; }
    if(j.tedarikci_id){ const el=document.querySelector('#mkForm [name=tedarikci_id]'); if(el)el.value=j.tedarikci_id; }
    const stl=(id,v)=>{ const el=document.getElementById(id); if(el&&v!=null) el.value=v; };
    stl('mkAraToplam',j.ara_toplam); stl('mkKdv',j.kdv_tutar); stl('mkGenelToplam',j.genel_toplam);
    // bos otomatik satiri temizle
    [...document.querySelectorAll('.mk-serbest')].forEach(r=>{ if(!r.querySelector('.mk-m').value && !r.querySelector('.mk-gelen').value) r.remove(); });
    let eksik=0; const kalemler=j.kalemler||[];
    kalemler.forEach(k=>{ mkSatirEkle({malzeme_id:k.malzeme_id,birim_id:k.birim_id,miktar:k.miktar,fiyat:k.birim_fiyat,ad:k.ad}); if(!k.malzeme_id) eksik++; });
    if(!document.querySelector('.mk-serbest')) mkSatirEkle();
    let msg=kalemler.length+' kalem okundu'+(eksik?(' · '+eksik+' tanesi eşleşmedi (kırmızı, elle seç)'):'')+' — miktar/fiyatı kontrol et.';
    mkDurumYaz(msg, eksik?null:true); mkBip(true); mkTopHesapla();
  }catch(e){ mkDurumYaz('Hata: '+(e.message||e),false); }
  input.value='';
}

// ---- KALEM TOPLAMI <-> FATURA ARA TOPLAMI canli karsilastirma ----
function mkTL(n){ return (n||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+' ₺'; }
function mkTopHesapla(){
  let t=0;
  document.querySelectorAll('.mk-satir').forEach(r=>{ const g=parseFloat(r.querySelector('.mk-gelen')?.value)||0, f=parseFloat(r.querySelector('.mk-fiyat')?.value)||0; t+=g*f; });
  const ara=parseFloat(document.getElementById('mkAraToplam')?.value)||0;
  const u=document.getElementById('mkToplamUyari'); if(!u) return;
  if(ara>0){
    const fark=t-ara, yuzde=Math.abs(fark)/ara*100;
    if(yuzde<=1){ u.innerHTML='Kalem toplamı: <b>'+mkTL(t)+'</b> · fatura ara toplamıyla uyuşuyor ✓'; u.style.color='var(--yesil)'; }
    else { u.innerHTML='Kalem toplamı <b>'+mkTL(t)+'</b> ≠ fatura ara toplamı <b>'+mkTL(ara)+'</b> (fark '+mkTL(fark)+') — kontrol et'; u.style.color='var(--kirmizi)'; }
  } else { u.innerHTML='Kalem toplamı (otomatik): <b>'+mkTL(t)+'</b>'; u.style.color='var(--sub2)'; }
}
document.getElementById('mkSatirlar')?.addEventListener('input', mkTopHesapla);
document.getElementById('mkAraToplam')?.addEventListener('input', mkTopHesapla);
mkTopHesapla();
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

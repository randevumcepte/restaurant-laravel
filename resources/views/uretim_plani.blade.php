@extends('layout.app')
@section('rk_ikon', 'calculate')
@section('baslik', 'Akıllı Üretim Planı')
@section('title', 'Üretim Planı')

@push('head')
<style>
#up{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #up{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#up{background:var(--bg);min-height:calc(100vh - 60px)}#up *{box-sizing:border-box}
#up .pano{max-width:1500px;margin:0 auto;padding:14px 16px 24px}
#up .material-icons-outlined{vertical-align:middle}
/* araç çubuğu */
#up .arac-cubuk{display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:var(--card);border-radius:14px;padding:10px 12px;box-shadow:0 2px 10px rgba(0,0,0,.05);margin-bottom:12px}
#up .grp{display:flex;align-items:center;gap:6px}
#up .grp>span{font-size:11.5px;font-weight:700;color:var(--sub)}
#up .seg{display:inline-flex;background:var(--card2);border-radius:10px;padding:3px}
#up .seg button{border:0;background:none;padding:6px 12px;border-radius:8px;font-size:12.5px;font-weight:800;color:var(--sub);cursor:pointer}
#up .seg button.akt{background:var(--mavi);color:#fff}
#up .btn{border:0;border-radius:10px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;white-space:nowrap}
#up .btn .material-icons-outlined{font-size:18px}
#up .btn.ai{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 4px 12px color-mix(in srgb,var(--mavi) 30%,transparent)}
#up .btn.bos{background:var(--card2);color:var(--sub)}
#up .btn.yesil{background:var(--yesil);color:#fff}
#up .btn.cizgi{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#up .tahmin-bilgi{margin-left:auto;font-size:12px;color:var(--sub);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
#up .tahmin-bilgi b{color:var(--ink)}
#up .guven{white-space:nowrap;border-radius:20px;padding:2px 9px;font-weight:800;font-size:11px;color:#fff}
#up .guven.yuksek{background:#10B981}#up .guven.orta{background:#F59E0B}#up .guven.dusuk{background:#F43F5E}
#up .bilgi-i{border:0;background:none;color:var(--sub2);cursor:pointer;padding:0;display:inline-flex}
/* ana ızgara */
#up .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:12px}
@media(max-width:1000px){#up .grid{grid-template-columns:1fr}}
#up .kart{background:var(--card);border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.05);display:flex;flex-direction:column;height:calc(100vh - 190px);min-height:480px;overflow:hidden}
@media(max-width:1000px){#up .kart{height:auto;min-height:0}#up .kaydir{max-height:70vh}}
#up .kart-bas{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 14px;border-bottom:1px solid var(--line)}
#up .kart-bas h2{margin:0;font-size:15px;font-weight:900;color:var(--ink)}
#up .kart-bas .ozet{font-size:12.5px;color:var(--sub)}
#up .kart-bas .ozet b{color:var(--ink)}
#up .kart-bas .sag{margin-left:auto;display:flex;gap:6px;align-items:center}
#up .kaydir{flex:1;overflow-y:auto}
/* ürün satırları */
#up .filtre-sat{display:flex;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line);flex-wrap:wrap}
#up .urun-ara{flex:1;min-width:140px;padding:7px 11px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
#up .urun-sat{display:flex;align-items:center;gap:10px;padding:6px 14px;border-bottom:1px solid var(--line);min-height:46px}
#up .urun-sat:hover{background:var(--card2)}
#up .urun-sat.dolu{background:color-mix(in srgb,var(--mavi) 5%,var(--card))}
#up .urun-sat.dolu .ad{color:var(--mavi)}
#up .urun-sat .ad{flex:1;min-width:0;font-size:13.5px;font-weight:700;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#up .oneri-btn{display:inline-flex;align-items:center;gap:4px;border:1px solid transparent;cursor:pointer;border-radius:8px;padding:3px 8px;font-size:11.5px;font-weight:700;background:color-mix(in srgb,var(--mor1) 9%,transparent);color:var(--mor1);white-space:nowrap}
#up .oneri-btn:hover{border-color:var(--mor1)}
#up .oneri-btn .material-icons-outlined{font-size:13px}
#up .oneri-btn .t-up{color:#059669}#up .oneri-btn .t-dn{color:#e11d48}
#up .oneri-yok{font-size:11px;color:var(--sub2);white-space:nowrap}
#up .stepper{display:flex;align-items:center;border:1px solid var(--line);border-radius:9px;overflow:hidden;background:var(--card);flex:none}
#up .stepper button{border:0;background:var(--card2);color:var(--ink);width:26px;height:30px;cursor:pointer;font-size:16px;font-weight:700;line-height:1}
#up .stepper button:hover{color:var(--mavi)}
#up .stepper .adet{width:52px;height:30px;border:0;text-align:center;font-size:14px;font-weight:800;color:var(--ink);background:transparent;-moz-appearance:textfield}
#up .stepper .adet::-webkit-inner-spin-button,#up .stepper .adet::-webkit-outer-spin-button{-webkit-appearance:none;margin:0}
#up .birim{width:52px;font-size:11px;font-weight:700;color:var(--sub);border:1px solid var(--line);border-radius:8px;padding:5px 2px;background:var(--card);cursor:pointer;flex:none}
#up .bos-liste{text-align:center;color:var(--sub);padding:30px;font-size:13px}
@media(max-width:600px){#up .urun-sat{padding:6px 10px;gap:7px}#up .urun-sat .ad{white-space:normal;font-size:13px}}
/* malzeme tablosu */
#up .talep-sat{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 14px;background:color-mix(in srgb,var(--yesil) 6%,var(--card));border-bottom:1px solid var(--line)}
#up .talep-sat input{border:1px solid var(--line);border-radius:9px;padding:7px 10px;background:var(--card);color:var(--ink);font-size:12.5px;font-family:inherit}
#up .talep-sat label{font-size:11.5px;font-weight:700;color:var(--sub)}
#up .mt{width:100%;border-collapse:collapse;font-size:13px}
#up .mt th{position:sticky;top:0;z-index:1;background:var(--card2);color:var(--sub);font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;text-align:right;padding:8px 14px}
#up .mt th:first-child,#up .mt td:first-child{text-align:left}
#up .mt td{padding:8px 14px;border-bottom:1px solid var(--line);text-align:right;color:var(--ink);white-space:nowrap}
#up .mt td:first-child{white-space:normal;font-weight:700;width:40%}
#up .mt .bar{height:4px;border-radius:4px;background:var(--card2);margin-top:5px;overflow:hidden;max-width:180px}
#up .mt .bar i{display:block;height:100%;border-radius:4px}
#up .mt .stok{color:var(--sub)}
#up .mt .al{display:inline-block;font-weight:900;color:#fff;background:var(--kirmizi);border-radius:7px;padding:3px 8px}
#up .mt .tl{color:var(--amber);font-weight:800}
#up .mt tr.yeter td{color:var(--sub);font-weight:600}
#up .mt tr.yeter .al{background:color-mix(in srgb,var(--yesil) 15%,transparent);color:#059669}
#up .ara-baslik td{background:var(--card2);font-size:11.5px;font-weight:800;color:var(--sub);cursor:pointer;text-align:left!important}
#up .bos-sonuc{text-align:center;color:var(--sub);padding:50px 20px;font-size:13px;line-height:1.6}
#up .bos-sonuc .material-icons-outlined{font-size:40px;color:var(--sub2);display:block;margin-bottom:8px}
#up .bos-sonuc b{color:var(--ink)}
#up .tamam-not{margin:14px;border-radius:11px;padding:11px 13px;font-size:12.5px;font-weight:700;color:#059669;background:color-mix(in srgb,var(--yesil) 9%,var(--card));display:flex;gap:8px;align-items:center}
#up .yukleniyor{opacity:.5;pointer-events:none}
#up .flash{border-radius:12px;padding:11px 14px;margin-bottom:12px;font-weight:600;font-size:13.5px}
#up .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
#up .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
#up .nasil{display:none;background:var(--card);border-radius:14px;padding:12px 16px;margin-bottom:12px;font-size:12.5px;color:var(--sub);line-height:1.6;box-shadow:0 2px 10px rgba(0,0,0,.05)}
#up .nasil.acik{display:block}
#up .nasil ul{margin:4px 0 0;padding-left:18px}
#up .nasil b{color:var(--ink)}
#mrpToast{position:fixed;left:50%;bottom:26px;transform:translateX(-50%) translateY(20px);background:#111827;color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;box-shadow:0 14px 34px rgba(0,0,0,.45);opacity:0;pointer-events:none;transition:.3s cubic-bezier(.22,.68,0,1.2);z-index:9999;max-width:90vw;text-align:center}
#mrpToast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#mrpToast.uyari{background:linear-gradient(135deg,#F59E0B,#d97706)}
/* Reçete gramaj popup */
#rdModal{--card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--line:#e2e8f0;--vurgu:#4F46E5;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px}
body.koyu #rdModal{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--line:#2c3650;--vurgu:#818cf8}
#rdModal.acik{display:flex}
#rdModal .rdm{background:var(--card);width:min(860px,100%);max-height:min(86vh,900px);border-radius:18px;box-shadow:0 24px 60px rgba(15,23,42,.35);display:flex;flex-direction:column;overflow:hidden}
#rdModal .rdm-bas{display:flex;align-items:flex-start;gap:12px;padding:16px 18px 12px;border-bottom:1px solid var(--line)}
#rdModal .rdm-ik{width:38px;height:38px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--vurgu) 12%,transparent);color:var(--vurgu)}
#rdModal .rdm-bas h3{margin:0;font-size:16px;font-weight:800;color:var(--ink)}
#rdModal .rdm-bas p{margin:3px 0 0;font-size:12.5px;color:var(--sub)}
#rdModal .rdm-kapat{margin-left:auto;border:0;background:var(--card2);color:var(--sub);width:34px;height:34px;border-radius:10px;cursor:pointer;flex:none;display:flex;align-items:center;justify-content:center}
#rdModal .rdm-kapat:hover{color:var(--ink)}
#rdModal .rdm-ara{margin:12px 18px 0;padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:13px}
#rdModal .rdm-govde{overflow-y:auto;padding:12px 18px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:10px;align-content:start}
#rdModal .rd-urun{background:var(--card2);border:1px solid var(--line);border-radius:13px;padding:11px 12px}
#rdModal .rd-bas{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:13.5px;font-weight:800;color:var(--ink);margin-bottom:8px}
#rdModal .rd-por{font-size:11px;font-weight:800;color:var(--vurgu);background:color-mix(in srgb,var(--vurgu) 12%,transparent);border-radius:20px;padding:2px 9px;white-space:nowrap}
#rdModal .rd-k{display:flex;justify-content:space-between;gap:10px;font-size:12.5px;color:var(--sub);padding:5px 0;border-top:1px dashed var(--line)}
#rdModal .rd-k b{color:var(--ink);white-space:nowrap}
#rdModal .rd-yok{grid-column:1/-1;text-align:center;color:var(--sub);padding:30px;font-size:13px}
</style>
@endpush

@section('content')
<div id="up">
<div class="pano">

  @if (session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if (session('mrp_hata'))<div class="flash err">{{ session('mrp_hata') }}</div>@endif

  <div class="arac-cubuk">
    <div class="grp"><span>Dönem</span>
      <div class="seg" id="donem">
        <button data-g="1" onclick="donemSec(this)">Yarın</button>
        <button data-g="7" class="akt" onclick="donemSec(this)">7 Gün</button>
        <button data-g="30" onclick="donemSec(this)">30 Gün</button>
      </div>
    </div>
    <div class="grp"><span>Güvenlik payı</span>
      <div class="seg" id="pay">
        <button data-p="0" onclick="paySec(this)">%0</button>
        <button data-p="10" class="akt" onclick="paySec(this)">%10</button>
        <button data-p="20" onclick="paySec(this)">%20</button>
      </div>
    </div>
    <button class="btn ai" onclick="akilliDoldur()"><span class="material-icons-outlined">auto_awesome</span>Tahmini Uygula</button>
    <button class="btn bos" onclick="temizle()"><span class="material-icons-outlined">restart_alt</span>Sıfırla</button>
    <div class="tahmin-bilgi" id="tahminBilgi"></div>
  </div>

  <div class="nasil" id="nasil">
    <b>Tahmin nasıl hesaplanıyor?</b>
    <ul>
      <li><b>Haftanın günü:</b> son 8 haftada her ürünün Pazartesi, Cuma, Cumartesi… satışları ayrı ortalanır; seçilen dönemin her günü kendi ortalamasıyla toplanır.</li>
      <li><b>Kapalı günler sayılmaz:</b> hiç satış olmayan günler ortalamayı düşürmez.</li>
      <li><b>Trend:</b> son 2 hafta önceki 2 haftaya göre arttıysa ya da düştüyse en fazla ±%30 ayarlanır.</li>
      <li><b>Güvenlik payı:</b> stok bitmesin diye seçilen oranda fazladan eklenir.</li>
      <li><b>Güven:</b> 28+ satış günü = yüksek, 10+ = orta, daha azı = düşük.</li>
    </ul>
  </div>

  <div class="grid">
    {{-- SOL: ürün + adet --}}
    <div class="kart">
      <div class="kart-bas"><h2>Ürünler</h2><span class="ozet" id="solOzet"></span></div>
      <div class="filtre-sat">
        <input class="urun-ara" placeholder="Ürün ara..." oninput="ara(this.value)">
        <div class="seg" id="filtre">
          <button data-f="hepsi" class="akt" onclick="filtreSec(this)">Tümü</button>
          <button data-f="plan" onclick="filtreSec(this)">Planlananlar</button>
        </div>
      </div>
      <div class="kaydir" id="urunListe">
        @forelse ($urunler as $u)
          @php $vbid = (int) ($u->verim_birim_id ?? 0); @endphp
          <div class="urun-sat" data-ad="{{ mb_strtolower($u->ad) }}" data-urun="{{ $u->id }}">
            <span class="ad" title="{{ $u->ad }}">{{ $u->ad }}</span>
            <span data-oneri-yer></span>
            <div class="stepper">
              <button type="button" onclick="adim(this,-1)" aria-label="Azalt">−</button>
              <input class="adet" type="number" min="0" step="any" value="0" inputmode="decimal"
                data-urun="{{ $u->id }}" data-recete="{{ $u->recete_id }}" data-vbirim="{{ $vbid }}" oninput="degisti(this)">
              <button type="button" onclick="adim(this,1)" aria-label="Artır">+</button>
            </div>
            <select class="birim" data-recete="{{ $u->recete_id }}" onchange="birimDegis(this)" title="Üretim birimi — seçince reçeteye kaydedilir">
              @foreach($birimAll as $b)
                <option value="{{ $b->id }}" @if($vbid === (int) $b->id) selected @endif>{{ $b->kisaltma }}</option>
              @endforeach
            </select>
          </div>
        @empty
          <div class="bos-liste">Reçetesi tanımlı aktif ürün yok.<br>Önce <b>Reçeteler</b> ekranından reçete tanımla.</div>
        @endforelse
      </div>
    </div>

    {{-- SAĞ: alınacaklar --}}
    <div class="kart">
      <div class="kart-bas"><h2>Alınacaklar</h2><span class="ozet" id="sagOzet"></span>
        <div class="sag"><button type="button" class="btn cizgi" id="rdBtn" onclick="receteDetayAc()" style="display:none;padding:6px 10px;font-size:12px"><span class="material-icons-outlined" style="font-size:16px">menu_book</span>Reçete detayı</button></div>
      </div>
      <div id="talepYer"></div>
      <div class="kaydir" id="sonuc"></div>
    </div>
  </div>

</div>
</div>

<script>
const MRP_CSRF=document.querySelector('meta[name=csrf-token]').content;
let TAHMIN={!! json_encode((object) ($akilli ?? []), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
let sonPlan=[], sonDetay=[], sonSonuc=null, akilliDolu=false, hesapZaman=null, hesapNo=0, yeterAcik=false;
const GUVEN_AD={yuksek:'güven yüksek',orta:'güven orta',dusuk:'güven düşük'};
function rdEsc(v){return String(v==null?'':v).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function tl(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(v)+' ₺';}
function sayi(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:2}).format(v);}
function aktifGun(){return +document.querySelector('#donem button.akt').dataset.g;}
function aktifPay(){return +document.querySelector('#pay button.akt').dataset.p;}
function donemAd(){const g=aktifGun();return g===1?'yarın':(g===7?'7 gün':'30 gün');}
function toast(msg,uyari){
  let t=document.getElementById('mrpToast');
  if(!t){t=document.createElement('div');t.id='mrpToast';document.body.appendChild(t);}
  t.textContent=msg; t.className='show'+(uyari?' uyari':'');
  clearTimeout(window._mtt); window._mtt=setTimeout(()=>t.className='',3600);
}

/* ---- tahmin ---- */
function oneriCiz(){
  let veriGun=0, guven=null;
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const t=TAHMIN[s.dataset.urun], yer=s.querySelector('[data-oneri-yer]');
    s.dataset.oneri=t?t.oneri:0;
    if(!t){yer.innerHTML='<span class="oneri-yok">satış yok</span>';return;}
    veriGun=Math.max(veriGun,t.veri_gun||0); guven=t.guven;
    let tr=''; if(t.trend>=5) tr=' <span class="t-up">↑%'+t.trend+'</span>'; else if(t.trend<=-5) tr=' <span class="t-dn">↓%'+Math.abs(t.trend)+'</span>';
    const ipucu='Günde ort. ~'+sayi(t.gunluk_ort)+(t.en_yogun?' · en yoğun gün '+t.en_yogun:'')+(t.trend?' · son 2 hafta trendi %'+t.trend:'')+' — uygulamak için tıkla';
    yer.innerHTML='<button type="button" class="oneri-btn" onclick="oneriUygula(this)" title="'+rdEsc(ipucu)+'"><span class="material-icons-outlined">auto_awesome</span>'+sayi(t.oneri)+tr+'</button>';
  });
  const b=document.getElementById('tahminBilgi');
  if(veriGun>0) b.innerHTML='Tahmin: son 8 haftada <b>'+veriGun+' satış günü</b> <span class="guven '+guven+'">'+GUVEN_AD[guven]+'</span><button class="bilgi-i" onclick="document.getElementById(\'nasil\').classList.toggle(\'acik\')" title="Nasıl hesaplanıyor?"><span class="material-icons-outlined" style="font-size:18px">info</span></button>';
  else b.innerHTML='Satış geçmişi yok — adetleri elle gir';
}
async function tahminYenile(){
  try{
    const r=await fetch('/uretim-plani/tahmin?gun='+aktifGun()+'&pay='+aktifPay()+'&_t='+Date.now(),{headers:{'Accept':'application/json'}});
    const j=await r.json(); if(j.ok){TAHMIN=j.tahmin||{}; oneriCiz(); if(akilliDolu) akilliDoldur(true);}
  }catch(e){toast('⚠️ Tahmin yenilenemedi',true);}
}
function donemSec(b){document.querySelectorAll('#donem button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');tahminYenile();}
function paySec(b){document.querySelectorAll('#pay button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');tahminYenile();}
function akilliDoldur(sessiz){
  let n=0;
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    const t=TAHMIN[s.dataset.urun]; if(!t) return; // geçmişi yoksa elle gireni silme
    s.querySelector('.adet').value=t.oneri; n++;
  });
  if(!n){if(!sessiz) toast('⚠️ Son 8 haftada satış geçmişi yok. Adetleri elle gir.',true);return;}
  akilliDolu=true; satirlariBoya(); hesaplaGecikmeli(30);
  if(!sessiz) toast('✨ '+n+' ürüne '+donemAd()+' tahmini uygulandı');
}
function oneriUygula(b){const s=b.closest('.urun-sat');const inp=s.querySelector('.adet');inp.value=s.dataset.oneri;degisti(inp);}
function temizle(){document.querySelectorAll('#urunListe .adet').forEach(i=>i.value=0);akilliDolu=false;satirlariBoya();hesaplaGecikmeli(0);}

/* ---- sol liste ---- */
function adim(b,d){const inp=b.parentElement.querySelector('.adet');inp.value=Math.max(0,Math.round(((+inp.value||0)+d)*100)/100);degisti(inp);}
function degisti(inp){akilliDolu=false;satirlariBoya();hesaplaGecikmeli();}
function satirlariBoya(){
  let u=0,a=0;
  document.querySelectorAll('#urunListe .adet').forEach(i=>{const v=+i.value||0;i.closest('.urun-sat').classList.toggle('dolu',v>0);if(v>0){u++;a+=v;}});
  document.getElementById('solOzet').innerHTML=u?'<b>'+u+'</b> ürün planlandı · <b>'+sayi(a)+'</b> porsiyon':'adet gir ya da ✨ öneriye tıkla';
  listeFiltre();
}
let aktifFiltre='hepsi', aramaQ='';
function filtreSec(b){document.querySelectorAll('#filtre button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');aktifFiltre=b.dataset.f;listeFiltre();}
function ara(q){aramaQ=(q||'').toLocaleLowerCase('tr');listeFiltre();}
function listeFiltre(){
  document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{
    let g=s.dataset.ad.includes(aramaQ);
    if(g&&aktifFiltre==='plan') g=s.classList.contains('dolu');
    s.style.display=g?'':'none';
  });
}
async function birimDegis(sel){
  const recete=+sel.dataset.recete, birim_id=+sel.value;
  const ad=sel.closest('.urun-sat').querySelector('.ad').innerText;
  try{
    const r=await fetch('/uretim-plani/birim-kaydet',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MRP_CSRF,'Accept':'application/json'},body:JSON.stringify({recete_id:recete,birim_id})});
    const j=await r.json();
    if(j.ok){toast('✓ '+ad+' birimi "'+sel.options[sel.selectedIndex].text+'" olarak kaydedildi');hesaplaGecikmeli(0);}
    else toast('⚠️ Birim kaydedilemedi',true);
  }catch(e){toast('⚠️ Birim kaydedilemedi',true);}
}
function planTopla(){
  const plan=[];
  document.querySelectorAll('#urunListe .adet').forEach(inp=>{
    const adet=+inp.value||0;
    if(adet>0){
      const sel=inp.closest('.urun-sat').querySelector('.birim');
      const birim_id=sel?(+sel.value):(+inp.dataset.vbirim||0);
      plan.push({urun_id:+inp.dataset.urun,recete_id:+inp.dataset.recete,adet:adet,birim_id:birim_id});
    }
  });
  return plan;
}

/* ---- hesap (otomatik) ---- */
function hesaplaGecikmeli(ms){clearTimeout(hesapZaman);hesapZaman=setTimeout(hesapla,ms==null?500:ms);}
async function hesapla(){
  const plan=planTopla(), kutu=document.getElementById('sonuc');
  if(!plan.length){
    hesapNo++; sonPlan=[];sonDetay=[];sonSonuc=null; // geç dönen eski hesap boş ekranı ezmesin
    document.getElementById('rdBtn').style.display='none';document.getElementById('talepYer').innerHTML='';
    document.getElementById('sagOzet').textContent='';
    kutu.innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">shopping_basket</span>Soldan adet gir ya da <b>Tahmini Uygula</b>\'ya bas —<br>reçeteler açılır, stok düşülür, <b>alınacak liste</b> burada çıkar.</div>';
    return;
  }
  sonPlan=plan; const no=++hesapNo; kutu.classList.add('yukleniyor');
  try{
    const r=await fetch('/uretim-plani/hesapla',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MRP_CSRF,'Accept':'application/json'},body:JSON.stringify({plan})});
    const j=await r.json();
    if(no!==hesapNo) return; // daha yeni bir hesap başladı
    sonSonuc=j; ciz();
  }catch(e){if(no===hesapNo) kutu.innerHTML='<div class="bos-sonuc">Hesaplanamadı, tekrar dene.</div>';}
  finally{if(no===hesapNo) kutu.classList.remove('yukleniyor');}
}
function malSatir(x){
  const lz=+x.lazim_temel||0, mv=+x.mevcut_temel||0, oran=lz>0?Math.min(100,Math.round(mv/lz*100)):100;
  const renk=oran>=100?'#10B981':(oran>=50?'#F59E0B':'#F43F5E');
  return '<tr class="'+(x.yeterli?'yeter':'')+'"><td>'+rdEsc(x.ad)+'<div class="bar" title="Stok ihtiyacın %'+oran+'\'ini karşılıyor"><i style="width:'+Math.max(3,oran)+'%;background:'+renk+'"></i></div></td>'+
    '<td>'+sayi(x.lazim)+' '+rdEsc(x.birim)+'</td><td class="stok">'+sayi(x.mevcut)+' '+rdEsc(x.mevcut_birim||x.birim)+'</td>'+
    '<td>'+(x.yeterli?'<span class="al">yeterli</span>':'<span class="al">'+sayi(x.net)+' '+rdEsc(x.birim)+'</span>')+'</td>'+
    '<td class="tl">'+(x.tahmini_maliyet>0?tl(x.tahmini_maliyet):'')+'</td></tr>';
}
function yeterAcKapa(){yeterAcik=!yeterAcik;ciz();}
function ciz(){
  const j=sonSonuc||{}, rows=j.rows||[], kutu=document.getElementById('sonuc'), talep=document.getElementById('talepYer');
  sonDetay=j.detay||[];
  document.getElementById('rdBtn').style.display=sonDetay.length?'':'none';
  const eksik=rows.filter(x=>!x.yeterli), yeter=rows.filter(x=>x.yeterli);
  document.getElementById('sagOzet').innerHTML=eksik.length?'<b style="color:var(--kirmizi)">'+eksik.length+' eksik</b> · ~<b>'+tl(j.toplam||0)+'</b>':(rows.length?'<b style="color:#059669">eksik yok</b>':'');
  if(!rows.length){talep.innerHTML='';kutu.innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">block</span>Bu ürünlerin reçetesinde stok takipli malzeme yok.</div>';return;}
  // talep satırı: listenin ÜSTÜNDE, hep görünür
  if(eksik.length){
    if(!talep.querySelector('form')) talep.innerHTML='<form method="POST" action="/uretim-plani/talep" class="talep-sat" onsubmit="return talepGonder(this)">'+
      '<input type="hidden" name="_token" value="'+MRP_CSRF+'"><input type="hidden" name="plan_json" value="">'+
      '<label>Teslim</label><input type="date" name="teslim_tarihi" title="Ne zaman elinde olsun?">'+
      '<input type="text" name="not" placeholder="Not (isteğe bağlı)" style="flex:1;min-width:120px">'+
      '<button type="submit" class="btn yesil" id="talepBtn"></button></form>';
    document.getElementById('talepBtn').innerHTML='<span class="material-icons-outlined">send</span>'+eksik.length+' kalemi talep et';
  }else talep.innerHTML='';
  let h='<table class="mt"><thead><tr><th>Malzeme</th><th>Lazım</th><th>Stokta</th><th>Alınacak</th><th>~Tutar</th></tr></thead><tbody>';
  eksik.forEach(x=>h+=malSatir(x));
  if(!eksik.length) h+='<tr><td colspan="5"><div class="tamam-not" style="margin:4px 0"><span class="material-icons-outlined">verified</span>Stok bu plan için yeterli — satın almaya gerek yok.</div></td></tr>';
  if(yeter.length){
    h+='<tr class="ara-baslik" onclick="yeterAcKapa()"><td colspan="5">'+(yeterAcik?'▾':'▸')+' Stokta yeterli olanlar ('+yeter.length+')</td></tr>';
    if(yeterAcik) yeter.forEach(x=>h+=malSatir(x));
  }
  kutu.innerHTML=h+'</tbody></table>';
}
function talepGonder(f){f.plan_json.value=JSON.stringify(sonPlan);return true;}

/* ---- reçete gramaj popup ---- */
function receteDetayAc(){
  let m=document.getElementById('rdModal');
  if(!m){
    m=document.createElement('div');m.id='rdModal';
    m.innerHTML='<div class="rdm" role="dialog" aria-modal="true"><div class="rdm-bas"><div class="rdm-ik"><span class="material-icons-outlined">menu_book</span></div>'+
      '<div><h3>Reçete Gramaj Detayı</h3><p id="rdAlt"></p></div><button type="button" class="rdm-kapat" onclick="receteDetayKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button></div>'+
      '<input class="rdm-ara" id="rdAra" placeholder="Ürün veya malzeme ara..." oninput="rdFiltre(this.value)"><div class="rdm-govde" id="rdGovde"></div></div>';
    m.addEventListener('click',e=>{if(e.target===m)receteDetayKapat();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')receteDetayKapat();});
    document.body.appendChild(m);
  }
  const porsiyon=sonDetay.reduce((t,d)=>t+(+d.adet||0),0);
  document.getElementById('rdAlt').textContent=sonDetay.length+' ürün · toplam '+sayi(porsiyon)+' porsiyon için malzeme miktarları';
  document.getElementById('rdGovde').innerHTML=sonDetay.map(d=>{
    const ara=(d.urun+' '+(d.kalemler||[]).map(k=>k.ad).join(' ')).toLocaleLowerCase('tr');
    return '<div class="rd-urun" data-ara="'+rdEsc(ara)+'"><div class="rd-bas"><span>'+rdEsc(d.urun)+'</span><span class="rd-por">× '+rdEsc(d.adet)+' porsiyon</span></div>'+
      (d.kalemler||[]).map(k=>'<div class="rd-k"><span>'+rdEsc(k.ad)+'</span><b>'+sayi(k.miktar)+' '+rdEsc(k.birim)+'</b></div>').join('')+'</div>';
  }).join('')+'<div class="rd-yok" id="rdYok" style="display:none">Eşleşen ürün yok.</div>';
  document.getElementById('rdAra').value='';
  m.classList.add('acik');document.body.style.overflow='hidden';
}
function receteDetayKapat(){const m=document.getElementById('rdModal');if(m&&m.classList.contains('acik')){m.classList.remove('acik');document.body.style.overflow='';}}
function rdFiltre(q){q=(q||'').toLocaleLowerCase('tr');let n=0;document.querySelectorAll('#rdGovde .rd-urun').forEach(x=>{const g=x.dataset.ara.includes(q);x.style.display=g?'':'none';if(g)n++;});document.getElementById('rdYok').style.display=n?'none':'';}

// açılışta: geçmişi olan ürünlere 7 günlük tahmin hazır gelsin → liste hemen dolu
oneriCiz(); satirlariBoya();
if(Object.keys(TAHMIN).length) akilliDoldur(true); else hesapla();
</script>
@endsection

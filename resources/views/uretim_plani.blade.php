@extends('layout.app')
@section('rk_ikon', 'calculate')
@section('baslik', 'Üretim Planı → Malzeme İhtiyacı')
@section('title', 'Üretim Planı')

@push('head')
<style>
#up{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #up{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#up{background:var(--bg);min-height:calc(100vh - 60px)}#up *{box-sizing:border-box}
#up .pano{max-width:1100px;margin:0 auto;padding:18px 18px 40px}
#up .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#up .ust{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
#up .seg{display:inline-flex;background:var(--card2);border-radius:12px;padding:3px}
#up .seg button{border:0;background:none;padding:8px 16px;border-radius:9px;font-size:13px;font-weight:700;color:var(--sub);cursor:pointer}
#up .seg button.akt{background:var(--mor1);color:#fff}
#up .btn{border:0;border-radius:11px;padding:10px 16px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;display:inline-flex;align-items:center;gap:7px}
#up .btn.mor{background:var(--mor1)}#up .btn.yesil{background:var(--yesil)}#up .btn.bos{background:var(--card);color:var(--mor1);border:1.5px solid var(--mor1)}
#up .btn .material-icons-outlined{font-size:18px}
#up .grid{display:grid;grid-template-columns:1fr 1.15fr;gap:16px}
@media(max-width:900px){#up .grid{grid-template-columns:1fr}}
#up .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:15px}
#up .kart-bas{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#up .kart-bas .material-icons-outlined{color:var(--mor1);font-size:19px}
#up .urun-ara{width:100%;padding:9px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:13px;margin-bottom:10px}
#up .urun-liste{max-height:460px;overflow-y:auto;display:flex;flex-direction:column;gap:6px}
#up .urun-sat{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:10px;border:1px solid var(--line)}
#up .urun-sat.dolu{border-color:var(--mor1);background:color-mix(in srgb,var(--mor1) 6%,var(--card))}
#up .urun-sat .ad{flex:1;font-size:13px;font-weight:600;color:var(--ink);line-height:1.2}
#up .urun-sat .adet{width:76px;padding:7px 8px;border:1px solid var(--line);border-radius:9px;text-align:center;font-size:14px;font-weight:700;color:var(--ink);background:var(--card)}
#up .urun-sat .bg{font-size:10px;color:var(--sub2)}
#up .bos-liste{text-align:center;color:var(--sub);padding:30px;font-size:13px}
#up .mal-tablo{width:100%;border-collapse:collapse;font-size:12.5px}
#up .mal-tablo th{text-align:left;color:var(--sub);font-weight:700;font-size:11px;padding:7px 8px;border-bottom:2px solid var(--line);text-transform:uppercase;letter-spacing:.3px}
#up .mal-tablo td{padding:8px;border-bottom:1px solid var(--line);color:var(--ink)}
#up .mal-tablo tr.yeter td{opacity:.5}
#up .mal-tablo .net{font-weight:800}
#up .mal-tablo .net.al{color:var(--kirmizi)}
#up .mal-tablo .sag{text-align:right}
#up .ozet-bar{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:12px;border-top:2px solid var(--line)}
#up .ozet-bar .tut{font-size:20px;font-weight:900;color:var(--mor1)}
#up .talep-form{margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
#up .fld{display:flex;flex-direction:column;gap:4px}
#up .fld label{font-size:11px;font-weight:700;color:var(--sub)}
#up .fld input,#up .fld textarea{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;font-family:inherit}
#up .bos-sonuc{text-align:center;color:var(--sub2);padding:50px 20px;font-size:13px}
#up .bos-sonuc .material-icons-outlined{font-size:42px;display:block;margin-bottom:10px;opacity:.5}
#up .flash{border-radius:12px;padding:12px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px}
#up .flash.ok{background:rgba(16,185,129,.14);color:#0b805e}
#up .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
</style>
@endpush

@section('content')
<div id="up">
<div class="pano">

  @if (session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if (session('mrp_hata'))<div class="flash err">{{ session('mrp_hata') }}</div>@endif

  <div class="aciklama">
    <b>Bu ekran ne yapar?</b> Hangi yemekten ne kadar üreteceğini (ya da satacağını) gir → sistem <b>reçeteleri patlatıp</b> tüm malzeme ihtiyacını hesaplar, <b>mevcut stoğu düşer</b> ve <b>net alınacak miktarı</b> çıkarır. Tek tıkla satın alma talebine (teslim tarihi + not) dönüştürebilirsin. 💡 <b>AI Öner</b> butonu geçmiş satışına göre adetleri doldurur.
  </div>

  <div class="ust">
    <div class="seg" id="donem">
      <button data-g="1" onclick="donemSec(this)">Günlük</button>
      <button data-g="7" class="akt" onclick="donemSec(this)">Haftalık</button>
      <button data-g="30" onclick="donemSec(this)">Aylık</button>
    </div>
    <button class="btn bos" onclick="aiOner()"><span class="material-icons-outlined">auto_awesome</span> AI Öner (geçmiş satıştan)</button>
    <button class="btn mor" onclick="hesapla()"><span class="material-icons-outlined">calculate</span> Malzeme İhtiyacını Hesapla</button>
  </div>

  <div class="grid">
    {{-- SOL: ürün + adet --}}
    <div class="kart">
      <div class="kart-bas"><span class="material-icons-outlined">restaurant_menu</span> Üretim / Satış Adetleri</div>
      <input class="urun-ara" placeholder="🔍 Ürün ara..." oninput="ara(this.value)">
      <div class="urun-liste" id="urunListe">
        @forelse ($urunler as $u)
          <div class="urun-sat" data-ad="{{ mb_strtolower($u->ad) }}">
            <span class="ad">{{ $u->ad }}</span>
            <div style="display:flex;flex-direction:column;align-items:center">
              <input class="adet" type="number" min="0" step="1" value="0"
                data-urun="{{ $u->id }}" data-recete="{{ $u->recete_id }}" data-haftalik="{{ $tahmin[$u->id] ?? 0 }}"
                oninput="satDolu(this)">
              <span class="bg">adet</span>
            </div>
          </div>
        @empty
          <div class="bos-liste">Reçetesi tanımlı aktif ürün yok.<br>Önce <b>Reçeteler</b> ekranından reçete tanımla.</div>
        @endforelse
      </div>
    </div>

    {{-- SAĞ: malzeme ihtiyacı --}}
    <div class="kart">
      <div class="kart-bas"><span class="material-icons-outlined">inventory_2</span> Malzeme İhtiyacı</div>
      <div id="sonuc">
        <div class="bos-sonuc"><span class="material-icons-outlined">calculate</span>Adetleri gir, <b>Hesapla</b>'ya bas →<br>malzeme ihtiyacın burada çıkacak</div>
      </div>
    </div>
  </div>

</div>
</div>

<script>
const MRP_CSRF=document.querySelector('meta[name=csrf-token]').content;
let sonPlan=[];
function donemSec(b){document.querySelectorAll('#donem button').forEach(x=>x.classList.remove('akt'));b.classList.add('akt');}
function aktifGun(){return +document.querySelector('#donem button.akt').dataset.g;}
function ara(q){q=(q||'').toLowerCase();document.querySelectorAll('#urunListe .urun-sat').forEach(s=>{s.style.display=s.dataset.ad.includes(q)?'':'none';});}
function satDolu(inp){inp.closest('.urun-sat').classList.toggle('dolu',(+inp.value)>0);}
function aiOner(){
  const g=aktifGun();
  document.querySelectorAll('#urunListe .adet').forEach(inp=>{
    const haftalik=+inp.dataset.haftalik||0;
    let v=0;
    if(g===7)v=haftalik; else if(g===1)v=Math.max(haftalik>0?1:0,Math.round(haftalik/7)); else v=Math.round(haftalik*30/7);
    inp.value=v; satDolu(inp);
  });
}
function planTopla(){
  const plan=[];
  document.querySelectorAll('#urunListe .adet').forEach(inp=>{
    const adet=+inp.value||0;
    if(adet>0)plan.push({urun_id:+inp.dataset.urun,recete_id:+inp.dataset.recete,adet:adet});
  });
  return plan;
}
async function hesapla(){
  const plan=planTopla();
  if(!plan.length){document.getElementById('sonuc').innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">info</span>Önce en az bir ürüne adet gir.</div>';return;}
  sonPlan=plan;
  document.getElementById('sonuc').innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">hourglass_top</span>Hesaplanıyor...</div>';
  try{
    const r=await fetch('/uretim-plani/hesapla',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':MRP_CSRF,'Accept':'application/json'},body:JSON.stringify({plan})});
    const j=await r.json();
    ciz(j);
  }catch(e){document.getElementById('sonuc').innerHTML='<div class="bos-sonuc">Hata, tekrar dene.</div>';}
}
function tl(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(v)+' ₺';}
function sayi(v){return new Intl.NumberFormat('tr-TR',{maximumFractionDigits:2}).format(v);}
function ciz(j){
  const rows=j.rows||[];
  if(!rows.length){document.getElementById('sonuc').innerHTML='<div class="bos-sonuc"><span class="material-icons-outlined">block</span>Bu ürünlerde stok-takipli malzeme yok.</div>';return;}
  const alinacak=(j.alinacak||[]).length;
  let h='<table class="mal-tablo"><thead><tr><th>Malzeme</th><th class="sag">Lazım</th><th class="sag">Stok</th><th class="sag">Net Al</th><th class="sag">~Maliyet</th></tr></thead><tbody>';
  rows.forEach(x=>{
    h+='<tr class="'+(x.yeterli?'yeter':'')+'"><td>'+x.ad+'</td>'+
      '<td class="sag">'+sayi(x.lazim)+' '+x.birim+'</td>'+
      '<td class="sag">'+sayi(x.mevcut)+'</td>'+
      '<td class="sag net '+(x.yeterli?'':'al')+'">'+(x.yeterli?'✓ yeter':sayi(x.net)+' '+x.birim)+'</td>'+
      '<td class="sag">'+(x.tahmini_maliyet>0?tl(x.tahmini_maliyet):'-')+'</td></tr>';
  });
  h+='</tbody></table>';
  h+='<div class="ozet-bar"><span style="font-size:12.5px;color:var(--sub)"><b>'+alinacak+'</b> kalem alınacak</span><span class="tut">~'+tl(j.toplam)+'</span></div>';
  if(alinacak>0){
    h+='<form method="POST" action="/uretim-plani/talep" class="talep-form" onsubmit="return talepGonder(this)">'+
      '<input type="hidden" name="_token" value="'+MRP_CSRF+'"><input type="hidden" name="plan_json" value="">'+
      '<div class="fld"><label>Teslim Tarihi (ele geçmesi)</label><input type="date" name="teslim_tarihi"></div>'+
      '<div class="fld" style="flex:1;min-width:180px"><label>Not (opsiyonel)</label><input type="text" name="not" placeholder="önemli bir ayrıntı..."></div>'+
      '<button type="submit" class="btn yesil"><span class="material-icons-outlined">send</span> Satınalma Talebi Oluştur</button>'+
      '</form>';
  }
  document.getElementById('sonuc').innerHTML=h;
}
function talepGonder(f){f.plan_json.value=JSON.stringify(sonPlan);return true;}
</script>
@endsection

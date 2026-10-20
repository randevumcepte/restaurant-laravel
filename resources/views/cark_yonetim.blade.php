@extends('layout.app')
@section('title', 'Çarkıfelek')
@section('rk_ikon', 'casino')
@section('baslik', 'Çarkıfelek Yönetimi')
@section('rk_alt', 'Dilimleri, ödülleri ve kazanma oranlarını ayarla — müşteri uygulamasında çark buna göre döner')

@section('content')
@php
  $dl = $dilimler->map(fn($d) => [
    'ad' => $d->ad, 'renk' => $d->renk, 'olasilik' => (int)$d->olasilik,
    'tip' => $d->tip, 'deger' => (float)$d->deger, 'aktif' => (int)($d->aktif ?? 1),
  ])->values();
@endphp
<div style="max-width:860px">

  @if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

  <form method="post" action="/cark-yonetim/kaydet" id="carkForm">
    @csrf
    <input type="hidden" name="dilimler_json" id="dilimler_json">

    {{-- Ayarlar --}}
    <div class="rk-card" style="padding:16px">
      <label style="display:flex;align-items:center;gap:10px;cursor:pointer;margin-bottom:14px">
        <input type="checkbox" name="aktif" value="1" {{ ($ayar->aktif ?? 1) ? 'checked' : '' }} style="width:18px;height:18px;accent-color:var(--mor1)">
        <span style="font-weight:800;color:var(--ink)">Çark aktif (müşteriler çevirebilsin)</span>
      </label>
      <div style="display:flex;gap:16px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
          <label style="display:block;font-size:12.5px;font-weight:700;color:var(--sub2);margin-bottom:5px">Çevirme aralığı (gün)</label>
          <input type="number" name="aralik" min="0" value="{{ $ayar->cevirme_araligi_gun ?? 7 }}" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink)">
          <div class="rk-sub" style="font-size:11px;margin-top:3px">Müşteri kaç günde bir çevirebilir (0 = her gün)</div>
        </div>
        <div style="flex:1;min-width:200px">
          <label style="display:block;font-size:12.5px;font-weight:700;color:var(--sub2);margin-bottom:5px">Kupon geçerlilik (gün)</label>
          <input type="number" name="gecerlilik" min="1" value="{{ $ayar->kupon_gecerlilik_gun ?? 7 }}" style="width:100%;box-sizing:border-box;padding:10px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink)">
          <div class="rk-sub" style="font-size:11px;margin-top:3px">Kazanılan indirim kuponu kaç gün geçerli</div>
        </div>
      </div>
    </div>

    {{-- Çark önizleme + dilim editörü --}}
    <div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:18px">
      <div style="flex:0 0 260px;display:flex;flex-direction:column;align-items:center">
        <div style="font-size:13px;font-weight:800;color:var(--ink);margin-bottom:8px">Önizleme</div>
        <div id="carkPreview" style="width:240px;height:240px"></div>
        <div class="rk-sub" style="font-size:11px;margin-top:8px;text-align:center">Oranlar otomatik normalize edilir — toplamın 100 olması şart değil.</div>
      </div>
      <div style="flex:1;min-width:300px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
          <div style="font-size:14px;font-weight:800;color:var(--ink)">Dilimler</div>
          <button type="button" class="rk-btn" style="background:var(--mor1);padding:7px 12px" onclick="dilimEkle()">
            <span class="material-icons-outlined" style="font-size:18px">add</span><span>Dilim</span>
          </button>
        </div>
        <div id="dilimList"></div>
      </div>
    </div>

    {{-- Kurallar --}}
    <div style="font-size:14px;font-weight:800;color:var(--ink);margin:22px 0 8px">Kullanım Kuralları (yasal)</div>
    <textarea name="kurallar" rows="4" style="width:100%;box-sizing:border-box;padding:12px;border:1px solid var(--line);border-radius:10px;background:var(--card2);color:var(--ink);font-size:13px;line-height:1.5;resize:vertical">{{ $ayar->kurallar ?? '' }}</textarea>

    <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:18px">
      <span class="material-icons-outlined" style="color:#fff">save</span><span>Kaydet</span>
    </button>
  </form>

  <div style="background:var(--card2);border-radius:12px;padding:12px;margin-top:16px;display:flex;gap:10px;align-items:flex-start">
    <span class="material-icons-outlined" style="font-size:18px;color:var(--sub)">info</span>
    <div class="rk-sub" style="font-size:12px;line-height:1.4">
      <b>Ödül tipleri:</b> Puan (anında hesaba eklenir) · % İndirim / ₺ İndirim (kupon kodu üretir, kasada <b>Ödül Doğrula</b>'dan tüketilir, puan düşmez) · Tekrar Dene (çevirme hakkı yanmaz) · Boş.
      Kazananlar <b>Ödül Raporu</b>'nda görünür.
    </div>
  </div>
</div>

<script>
const TIPLER = {puan:'Puan', yuzde:'% İndirim', tutar:'₺ İndirim', tekrar:'Tekrar Dene', bos:'Boş'};
let dilimler = @json($dl);
if (!dilimler.length) dilimler = [{ad:'10 Puan',renk:'#7C3AED',olasilik:30,tip:'puan',deger:10,aktif:1}];

function render(){
  const list = document.getElementById('dilimList');
  list.innerHTML = '';
  dilimler.forEach((d, i) => {
    const row = document.createElement('div');
    row.className = 'rk-card';
    row.style.cssText = 'padding:10px;margin-bottom:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap';
    row.innerHTML = `
      <input type="color" value="${d.renk}" title="Renk" onchange="upd(${i},'renk',this.value)" style="width:34px;height:34px;border:none;background:none;cursor:pointer">
      <input type="text" value="${esc(d.ad)}" placeholder="Dilim adı" onchange="upd(${i},'ad',this.value)" style="flex:1;min-width:110px;padding:8px;border:1px solid var(--line);border-radius:8px;background:var(--card2);color:var(--ink)">
      <select onchange="upd(${i},'tip',this.value)" style="padding:8px;border:1px solid var(--line);border-radius:8px;background:var(--card2);color:var(--ink)">
        ${Object.keys(TIPLER).map(t=>`<option value="${t}" ${d.tip===t?'selected':''}>${TIPLER[t]}</option>`).join('')}
      </select>
      <input type="number" value="${d.deger}" title="Değer (puan/%/₺)" min="0" onchange="upd(${i},'deger',this.value)" ${(d.tip==='tekrar'||d.tip==='bos')?'disabled':''} style="width:80px;padding:8px;border:1px solid var(--line);border-radius:8px;background:var(--card2);color:var(--ink)">
      <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:var(--sub)">oran
        <input type="number" value="${d.olasilik}" title="Kazanma ağırlığı" min="0" onchange="upd(${i},'olasilik',this.value)" style="width:60px;padding:8px;border:1px solid var(--line);border-radius:8px;background:var(--card2);color:var(--ink)">
      </label>
      <button type="button" onclick="dilimSil(${i})" title="Sil" style="border:none;background:none;cursor:pointer;color:#F43F5E"><span class="material-icons-outlined">delete</span></button>
    `;
    list.appendChild(row);
  });
  cizPreview();
}
function esc(s){ return (s||'').replace(/"/g,'&quot;'); }
function upd(i,k,v){ dilimler[i][k] = (k==='olasilik'||k==='deger') ? Number(v) : v; if(k==='tip') render(); else cizPreview(); }
function dilimEkle(){ const renkler=['#7C3AED','#F59E0B','#10B981','#E8541E','#4F46E5','#F43F5E','#0EA5E9','#64748B']; dilimler.push({ad:'Yeni',renk:renkler[dilimler.length%renkler.length],olasilik:10,tip:'puan',deger:5,aktif:1}); render(); }
function dilimSil(i){ dilimler.splice(i,1); render(); }

function cizPreview(){
  const box = document.getElementById('carkPreview');
  const n = dilimler.length; if(!n){ box.innerHTML=''; return; }
  const cx=120, cy=120, rad=115; let a0=-90;
  let svg = `<svg viewBox="0 0 240 240" width="240" height="240">`;
  dilimler.forEach(d=>{
    const a1 = a0 + 360/n;
    const r0=a0*Math.PI/180, r1=a1*Math.PI/180;
    const x0=cx+rad*Math.cos(r0), y0=cy+rad*Math.sin(r0);
    const x1=cx+rad*Math.cos(r1), y1=cy+rad*Math.sin(r1);
    svg += `<path d="M${cx},${cy} L${x0.toFixed(1)},${y0.toFixed(1)} A${rad},${rad} 0 0 1 ${x1.toFixed(1)},${y1.toFixed(1)} Z" fill="${d.renk}" stroke="#fff" stroke-width="2"/>`;
    const am=(a0+a1)/2*Math.PI/180, tx=cx+rad*0.62*Math.cos(am), ty=cy+rad*0.62*Math.sin(am);
    svg += `<text x="${tx.toFixed(1)}" y="${ty.toFixed(1)}" fill="#fff" font-size="9" font-weight="700" text-anchor="middle" dominant-baseline="middle" transform="rotate(${((a0+a1)/2).toFixed(1)},${tx.toFixed(1)},${ty.toFixed(1)})">${esc((d.ad||'').substring(0,10))}</text>`;
    a0=a1;
  });
  svg += `<circle cx="${cx}" cy="${cy}" r="18" fill="#fff" stroke="#e2e8f0" stroke-width="2"/><polygon points="${cx-8},8 ${cx+8},8 ${cx},26" fill="#1E293B"/></svg>`;
  box.innerHTML = svg;
}
document.getElementById('carkForm').addEventListener('submit', function(){
  document.getElementById('dilimler_json').value = JSON.stringify(dilimler);
});
render();
</script>
@endsection

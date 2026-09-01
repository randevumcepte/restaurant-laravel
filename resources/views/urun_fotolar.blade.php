<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Ürün Fotoğrafları · ResteOS</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --bg:#0B1020; --card:#151b2e; --yesil:#22C55E; }
  *{ box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  html,body{ margin:0; background:var(--bg); color:#fff; font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif; }
  .wrap{ max-width:960px; margin:0 auto; padding:18px 14px 48px; }
  h1{ font-size:20px; margin:4px 0 2px; }
  .alt{ color:#94A3B8; font-size:13px; margin:0 0 14px; }
  .ust{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
  select{ background:var(--card); color:#fff; border:1px solid #2D3752; border-radius:10px; padding:9px 12px; font-size:14px; }
  .say{ color:#94A3B8; font-size:12.5px; }
  .grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(168px,1fr)); gap:14px; }
  .k{ background:var(--card); border:1px solid #2D3752; border-radius:16px; overflow:hidden; display:flex; flex-direction:column; }
  .k .ph{ position:relative; height:130px; background:#0e1428; }
  .k .ph img{ width:100%; height:100%; object-fit:cover; }
  .k .rz{ position:absolute; top:8px; left:8px; font-size:10.5px; font-weight:800; padding:3px 9px; border-radius:20px; }
  .rz.auto{ background:rgba(148,163,184,.25); color:#CBD5E1; }
  .rz.yuk{ background:rgba(34,197,94,.22); color:#4ADE80; }
  .k .in{ padding:10px 11px 12px; display:flex; flex-direction:column; gap:2px; flex:1; }
  .k .ad{ font-weight:800; font-size:13.5px; line-height:1.2; }
  .k .kt{ color:#8698b5; font-size:11px; }
  .k .fi{ color:#FDE9B5; font-weight:800; font-size:12.5px; margin-top:2px; }
  .k .btns{ display:flex; gap:7px; margin-top:9px; }
  .k button{ flex:1; border:none; border-radius:9px; padding:9px; font-weight:700; font-size:12px; color:#fff; }
  .yukle{ background:linear-gradient(135deg,var(--mor),var(--mavi)); }
  .kaldir{ background:#3a2330; color:#FCA5A5; flex:0 0 auto; padding:9px 11px; }
  .k.bekle{ opacity:.55; pointer-events:none; }
  .toast{ position:fixed; left:50%; bottom:22px; transform:translateX(-50%); background:#1e293b; color:#fff;
    padding:11px 18px; border-radius:12px; font-size:13.5px; box-shadow:0 8px 24px rgba(0,0,0,.5); opacity:0; transition:.3s; z-index:20; }
  .toast.show{ opacity:1; }
</style>
</head>
<body>
<div class="wrap">
  <h1>📷 Ürün Fotoğrafları</h1>
  <p class="alt">Her ürüne kendi tabak fotoğrafını yükle. Yüklenen foto, müşteri menüsünde otomatik fotoğrafın yerine geçer.</p>
  <div class="ust">
    <label>Şube:
      <select onchange="location.href='?sube='+this.value">
        @foreach($subeler as $s)
          <option value="{{ $s->id }}" {{ $s->id==$subeId?'selected':'' }}>{{ $s->ad }}</option>
        @endforeach
      </select>
    </label>
    <span class="say">{{ count($liste) }} ürün · <span id="yuk-say">{{ collect($liste)->where('yuklendi',true)->count() }}</span> tanesi özel fotoğraflı</span>
  </div>

  <div class="grid">
    @foreach($liste as $u)
      <div class="k" id="k-{{ $u['id'] }}">
        <div class="ph">
          <img id="img-{{ $u['id'] }}" src="{{ $u['onizleme'] }}" alt="{{ $u['ad'] }}" loading="lazy">
          <span class="rz {{ $u['yuklendi']?'yuk':'auto' }}" id="rz-{{ $u['id'] }}">{{ $u['yuklendi']?'✓ Yüklendi':'Otomatik' }}</span>
        </div>
        <div class="in">
          <div class="ad">{{ $u['ad'] }}</div>
          <div class="kt">{{ $u['kat'] }}</div>
          <div class="fi">{{ $u['fiyat'] }}</div>
          <div class="btns">
            <button class="yukle" onclick="sec({{ $u['id'] }})">📷 Foto Yükle</button>
            <button class="kaldir" id="kaldir-{{ $u['id'] }}" onclick="kaldir({{ $u['id'] }})" style="{{ $u['yuklendi']?'':'display:none' }}">Kaldır</button>
          </div>
        </div>
        <input type="file" accept="image/jpeg,image/png,image/webp" id="file-{{ $u['id'] }}" style="display:none" onchange="yukle({{ $u['id'] }}, this)">
      </div>
    @endforeach
  </div>
</div>
<div class="toast" id="toast"></div>

<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
let yukSay = {{ collect($liste)->where('yuklendi',true)->count() }};
function bildir(m){ const t=document.getElementById('toast'); t.textContent=m; t.classList.add('show'); clearTimeout(t._t); t._t=setTimeout(()=>t.classList.remove('show'),2600); }
function sec(id){ document.getElementById('file-'+id).click(); }

async function yukle(id, inp){
  const f = inp.files && inp.files[0]; if(!f) return;
  if(f.size > 6*1024*1024){ bildir('Dosya çok büyük (en fazla 6 MB).'); inp.value=''; return; }
  const kart = document.getElementById('k-'+id); kart.classList.add('bekle');
  const fd = new FormData(); fd.append('urun_id', id); fd.append('foto', f);
  try{
    const r = await fetch('/urun-foto-yukle', { method:'POST', headers:{ 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' }, body:fd });
    const j = await r.json();
    if(j.ok){
      document.getElementById('img-'+id).src = j.url;
      const rz=document.getElementById('rz-'+id); rz.textContent='✓ Yüklendi'; rz.className='rz yuk';
      const kb=document.getElementById('kaldir-'+id); if(kb.style.display==='none'){ kb.style.display=''; yukSay++; document.getElementById('yuk-say').textContent=yukSay; }
      bildir('Fotoğraf yüklendi ✓');
    } else bildir(j.mesaj || 'Yüklenemedi.');
  }catch(e){ bildir('Bağlantı hatası.'); }
  inp.value=''; kart.classList.remove('bekle');
}

async function kaldir(id){
  const kart=document.getElementById('k-'+id); kart.classList.add('bekle');
  try{
    const r = await fetch('/urun-foto-sil', { method:'POST', headers:{ 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json', 'Content-Type':'application/x-www-form-urlencoded' }, body:'urun_id='+id });
    const j = await r.json();
    if(j.ok){
      const rz=document.getElementById('rz-'+id); rz.textContent='Otomatik'; rz.className='rz auto';
      document.getElementById('kaldir-'+id).style.display='none';
      yukSay=Math.max(0,yukSay-1); document.getElementById('yuk-say').textContent=yukSay;
      bildir('Otomatik fotoğrafa dönüldü.');
    }
  }catch(e){ bildir('Bağlantı hatası.'); }
  kart.classList.remove('bekle');
}
</script>
</body>
</html>

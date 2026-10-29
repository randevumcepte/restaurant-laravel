@extends('layout.app')
@section('rk_ikon', 'auto_awesome')
@section('baslik', 'Patron AI')
@section('title', 'Patron AI')

@push('head')
<style>
  #pai{max-width:980px;margin:0 auto}
  /* ---------- HERO ---------- */
  .pai-hero{position:relative;overflow:hidden;border-radius:24px;padding:22px 24px;margin-bottom:16px;
    background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 42%,#4F46E5 100%);
    box-shadow:0 14px 40px rgba(79,70,229,.28)}
  .pai-hero::before{content:'';position:absolute;width:280px;height:280px;border-radius:50%;top:-120px;right:-60px;
    background:radial-gradient(circle,rgba(255,255,255,.22),transparent 70%);animation:paiFloat 9s ease-in-out infinite}
  .pai-hero::after{content:'';position:absolute;width:220px;height:220px;border-radius:50%;bottom:-130px;left:30%;
    background:radial-gradient(circle,rgba(246,206,99,.18),transparent 70%);animation:paiFloat 11s ease-in-out infinite reverse}
  @keyframes paiFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,14px)}}
  .pai-hero-row{position:relative;z-index:2;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
  .pai-orb{width:54px;height:54px;border-radius:17px;flex:none;display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.16);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.28);
    box-shadow:0 8px 22px rgba(0,0,0,.18)}
  .pai-orb .material-icons-outlined{color:#fff;font-size:29px;animation:paiPulse 3.4s ease-in-out infinite}
  @keyframes paiPulse{0%,100%{transform:scale(1);opacity:.92}50%{transform:scale(1.14);opacity:1}}
  .pai-selam{font-size:23px;font-weight:800;color:#fff;line-height:1.2;letter-spacing:-.3px}
  .pai-alt{font-size:12.5px;color:rgba(255,255,255,.82);margin-top:3px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
  .pai-ai-rozet{display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.28);
    padding:2px 9px;border-radius:20px;font-weight:700;font-size:11px;color:#fff}
  /* segment tabs */
  .pai-seg{display:inline-flex;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);border-radius:13px;padding:3px}
  .pai-tab{border:0;background:transparent;color:rgba(255,255,255,.82);font-weight:700;font-size:13px;padding:7px 15px;border-radius:10px;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:5px}
  .pai-tab.aktif{background:#fff;color:#4F46E5;box-shadow:0 3px 10px rgba(0,0,0,.14)}
  .pai-ref{width:38px;height:38px;border-radius:11px;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.12);color:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s}
  .pai-ref:hover{background:rgba(255,255,255,.24)}
  .pai-ref.spin .material-icons-outlined{animation:paiSpin .7s linear infinite}
  @keyframes paiSpin{to{transform:rotate(360deg)}}

  /* ---------- ACTION CHIPS ---------- */
  .pai-chipler{display:flex;flex-wrap:wrap;gap:8px}
  .pai-chip{display:inline-flex;align-items:center;gap:7px;background:var(--card);border:1px solid var(--line);border-radius:13px;padding:9px 14px;font-size:13px;font-weight:700;color:var(--ink);cursor:pointer;transition:all .14s;box-shadow:var(--golge)}
  .pai-chip:hover{border-color:var(--mor1);color:var(--mor1);transform:translateY(-2px);box-shadow:0 8px 20px rgba(124,58,237,.16)}
  .pai-chip .material-icons-outlined{font-size:17px}
  .pai-grup-bas{font-size:11px;font-weight:800;color:var(--sub2);letter-spacing:.5px;text-transform:uppercase;display:flex;align-items:center;gap:7px;margin:0 2px 10px}
  .pai-grup-bas .material-icons-outlined{font-size:15px;color:var(--mor1)}

  /* ---------- KPI STRIP ---------- */
  .pai-kpiler{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:16px}
  .pai-kpi{position:relative;border-radius:18px;padding:15px 16px;border:1px solid var(--line);background:var(--card);box-shadow:var(--golge);overflow:hidden}
  .pai-kpi .ic{width:36px;height:36px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:11px}
  .pai-kpi .ic .material-icons-outlined{font-size:20px}
  .pai-kpi .buyuk{font-size:24px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px}
  .pai-kpi .etk{font-size:12px;color:var(--sub);margin-top:5px;font-weight:600}
  .pai-kpi .delta{position:absolute;top:14px;right:14px;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px}

  /* ---------- BLOKLAR ---------- */
  .pai-blok{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--golge);padding:17px 19px;margin-bottom:14px}
  .pai-blok h4{font-size:12px;font-weight:800;color:var(--sub2);letter-spacing:.4px;margin:0 0 12px;display:flex;align-items:center;gap:8px;text-transform:uppercase}
  .pai-blok h4 .material-icons-outlined{font-size:17px}

  /* AI değerlendirmesi — standout */
  .pai-ozet{position:relative;overflow:hidden;border-radius:20px;padding:18px 20px;margin-bottom:16px;
    background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 12%,var(--card)),color-mix(in srgb,var(--mavi) 10%,var(--card)));
    border:1px solid color-mix(in srgb,var(--mor1) 28%,var(--line))}
  .pai-ozet .lbl{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;color:var(--mor1);letter-spacing:.4px;text-transform:uppercase;margin-bottom:8px}
  .pai-ozet .metin{font-size:15px;line-height:1.68;color:var(--ink);white-space:pre-line}

  /* analiz kartı */
  .pai-an{border-radius:18px;padding:16px 18px;margin-bottom:12px;background:var(--card);border:1px solid var(--line);box-shadow:var(--golge);border-left:4px solid var(--line)}
  .pai-an-bas{display:flex;align-items:center;gap:11px;margin-bottom:9px}
  .pai-an-ic{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none}
  .pai-an-ic .material-icons-outlined{font-size:21px}
  .pai-an-bas .t{font-weight:800;font-size:15.5px;color:var(--ink)}
  .pai-an-bas .d{font-size:12.5px;color:var(--sub);font-weight:600}
  .pai-vd{font-size:10px;font-weight:800;padding:4px 11px;border-radius:20px;color:#fff;letter-spacing:.5px;margin-left:auto}
  .pai-yorum{font-size:14px;line-height:1.62;color:var(--ink);margin:2px 0 10px}
  .pai-oz{display:flex;gap:9px;align-items:flex-start;font-size:13px;line-height:1.55;padding:11px 13px;border-radius:12px;margin-top:8px}
  .pai-oz .material-icons-outlined{font-size:17px;flex:none;margin-top:1px}

  /* satır listeleri */
  .pai-sat{display:flex;align-items:center;gap:11px;padding:11px 0;border-bottom:1px solid var(--line);font-size:13.5px}
  .pai-sat:last-child{border-bottom:0}
  .pai-dot{width:9px;height:9px;border-radius:50%;flex:none}
  .pai-mini{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border-radius:20px;padding:6px 13px;font-size:12.5px;color:var(--sub2);font-weight:600}
  .pai-link{color:var(--mavi);font-size:12.5px;text-decoration:none;white-space:nowrap;font-weight:700}
  .pai-link:hover{text-decoration:underline}
  .pai-btn-sm{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:10px;background:var(--mor1);color:#fff;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap;border:0;cursor:pointer}
  .pai-btn-sm.bos{background:var(--card2);color:var(--sub2);border:1px solid var(--line)}

  /* ---------- SKELETON ---------- */
  .pai-sk{background:linear-gradient(90deg,var(--card2) 25%,color-mix(in srgb,var(--card2) 55%,var(--card)) 37%,var(--card2) 63%);background-size:400% 100%;animation:paiSh 1.3s ease-in-out infinite;border-radius:12px}
  @keyframes paiSh{0%{background-position:100% 0}100%{background-position:-100% 0}}

  /* ---------- EYLEM MODAL ---------- */
  .pai-eylem-mod{display:none;position:fixed;inset:0;background:rgba(10,15,30,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center;padding:16px}
  .pai-eylem-mod.acik{display:flex}
  .pai-eylem-mod .ic{background:var(--card);border-radius:20px;max-width:520px;width:100%;padding:24px;box-shadow:0 24px 70px rgba(0,0,0,.4)}
  .pai-eylem-mod h3{font-size:17px;font-weight:800;color:var(--ink);margin:0 0 8px;display:flex;align-items:center;gap:9px}
  .pai-eylem-mod .ozet{background:var(--card2);border-left:3px solid var(--mor1);border-radius:12px;padding:13px 15px;font-size:13.5px;line-height:1.55;color:var(--ink);margin:12px 0 16px}
  .pai-eylem-mod .uyari{font-size:11.5px;color:var(--sub);margin-bottom:16px;display:flex;align-items:center;gap:6px}
  .pai-eylem-mod .uyari .material-icons-outlined{font-size:14px;color:var(--amber)}
  .pai-eylem-mod .btnler{display:flex;gap:9px;justify-content:flex-end}
  .pai-eylem-mod .btn-yap{background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:0;border-radius:12px;padding:12px 22px;font-weight:800;cursor:pointer;font-size:13.5px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 14px rgba(16,185,129,.32)}
  .pai-eylem-mod .btn-vaz{background:var(--card2);color:var(--sub);border:1px solid var(--line);border-radius:12px;padding:12px 18px;font-weight:700;cursor:pointer;font-size:13.5px}
  .pai-sonuc{font-size:13px;color:var(--ink);line-height:1.5;padding:12px 14px;border-radius:12px;background:var(--card2);margin-top:12px}
  .pai-sonuc.ok{border-left:3px solid #10B981;color:#047857}
  .pai-sonuc.hata{border-left:3px solid #F43F5E;color:#9f1239}

  @media (max-width:560px){
    .pai-selam{font-size:20px}
    .pai-hero{padding:18px 18px}
  }
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="pai">
  @php
    $adK = $ad ? trim(explode(' ', trim($ad))[0]) : null;
    $saat = (int) now()->format('H');
    $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
    $tip = $tip ?? 'sabah';
    $demo = $demo ?? false;
  @endphp

  {{-- ============ HERO (anında render — veri beklemez) ============ --}}
  <div class="pai-hero">
    <div class="pai-hero-row">
      <div class="pai-orb"><span class="material-icons-outlined">auto_awesome</span></div>
      <div style="flex:1;min-width:180px">
        <div class="pai-selam">{{ $selam }}{{ $adK ? ', '.$adK : '' }} 👋</div>
        <div class="pai-alt">
          <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}</span>
          <span>·</span>
          <span id="pai-tip-etiket">{{ $tip === 'aksam' ? 'akşam raporu' : 'sabah brifingi' }}</span>
          <span class="pai-ai-rozet" id="pai-kaynak-rozet">✨ Patron AI</span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:9px">
        <div class="pai-seg">
          <button class="pai-tab {{ $tip==='sabah'?'aktif':'' }}" data-tip="sabah" onclick="paiYukle('sabah',false)">☀️ Sabah</button>
          <button class="pai-tab {{ $tip==='aksam'?'aktif':'' }}" data-tip="aksam" onclick="paiYukle('aksam',false)">🌙 Akşam</button>
        </div>
        <button class="pai-ref" id="pai-ref" onclick="paiYukle(paiTip,true)" title="Yenile"><span class="material-icons-outlined" style="font-size:19px">refresh</span></button>
      </div>
    </div>
  </div>

  {{-- ============ HAZIR AI EYLEMLERİ (statik) ============ --}}
  <div class="pai-blok" style="padding:15px 17px">
    <div class="pai-grup-bas"><span class="material-icons-outlined">bolt</span> Hazır AI Eylemleri — tıkla, onay iste, log'lanır</div>
    <div class="pai-chipler">
      <button class="pai-chip" onclick="paiEylemOner('kritik_siparis_olustur')"><span>🛒</span> Kritikleri siparişe çevir</button>
      <button class="pai-chip" onclick="paiEylemOner('tedarikci_hatirlat')"><span>📞</span> Geciken tedarikçileri hatırlat</button>
      <button class="pai-chip" onclick="paiEylemOner('eksik_bilgi_iste')"><span>📝</span> Eksik özlük bilgisi iste</button>
      <button class="pai-chip" onclick="paiEylemOner('dogum_gunu_kutla')"><span>🎂</span> Doğum günü kutla</button>
      <button class="pai-chip" onclick="paiEylemOner('gelmeyene_sor')"><span>⏰</span> Vardiyada gelmeyene sor</button>
    </div>
  </div>

  {{-- ============ İÇERİK (async yüklenir) ============ --}}
  <div id="pai-icerik" style="margin-top:16px">
    {{-- İlk görünüm: shimmer skeleton (JS verisiyle değişir) --}}
    <div class="pai-kpiler">
      @for ($i=0;$i<5;$i++)<div class="pai-kpi"><div class="pai-sk" style="width:36px;height:36px;border-radius:11px;margin-bottom:11px"></div><div class="pai-sk" style="width:70%;height:22px;margin-bottom:8px"></div><div class="pai-sk" style="width:50%;height:12px"></div></div>@endfor
    </div>
    <div class="pai-ozet"><div class="pai-sk" style="width:140px;height:13px;margin-bottom:12px"></div><div class="pai-sk" style="width:100%;height:14px;margin-bottom:8px"></div><div class="pai-sk" style="width:88%;height:14px"></div></div>
    @for ($i=0;$i<3;$i++)<div class="pai-blok"><div class="pai-sk" style="width:45%;height:16px;margin-bottom:12px"></div><div class="pai-sk" style="width:100%;height:13px;margin-bottom:7px"></div><div class="pai-sk" style="width:80%;height:13px"></div></div>@endfor
  </div>
</div>
</div>

{{-- Eylem onay modali --}}
<div class="pai-eylem-mod" id="paiEylemMod" onclick="if(event.target===this)this.classList.remove('acik')">
  <div class="ic">
    <h3><span class="material-icons-outlined" style="color:var(--mor1)">auto_awesome</span> AI eylem onayı</h3>
    <div class="ozet" id="paiEylemOzet"></div>
    <div class="uyari"><span class="material-icons-outlined">lock</span> Onayın IP'n (<b id="paiEylemIp">—</b>) ile birlikte imzalı log'a yazılacak. Geri alınamaz, yasal kanıt niteliğinde.</div>
    <div class="btnler">
      <button class="btn-vaz" onclick="paiEylemKapat()">Kapat</button>
      <button class="btn-yap" id="paiEylemYapBtn" onclick="paiEylemYap()"><span class="material-icons-outlined" style="font-size:17px">check</span> İşlemi Yap</button>
    </div>
    <div id="paiEylemSonuc" style="display:none" class="pai-sonuc"></div>
  </div>
</div>

@push('scripts')
<script>
var paiTip = @json($tip ?? 'sabah');
var paiDemo = @json((bool)($demo ?? false));
var paiYukluyor = false;

function paiYukle(tip, force){
  if (paiYukluyor) return;
  paiYukluyor = true;
  paiTip = tip;
  document.querySelectorAll('.pai-tab').forEach(function(b){ b.classList.toggle('aktif', b.dataset.tip===tip); });
  var etk = document.getElementById('pai-tip-etiket'); if (etk) etk.textContent = (tip==='aksam'?'akşam raporu':'sabah brifingi');
  var ref = document.getElementById('pai-ref'); if (ref && force) ref.classList.add('spin');
  var box = document.getElementById('pai-icerik');
  box.style.opacity = '.5'; box.style.transition = 'opacity .15s';
  var qs = new URLSearchParams({frag:'1', tip:tip});
  if (force) qs.set('force','1');
  if (paiDemo) qs.set('demo','1');
  fetch('/patron-ai?'+qs.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
    .then(function(r){ return r.text(); })
    .then(function(html){
      box.innerHTML = html; box.style.opacity = '1';
      // kaynak rozeti güncelle
      var k = box.querySelector('[data-kaynak]');
      var rz = document.getElementById('pai-kaynak-rozet');
      if (k && rz) rz.textContent = (k.dataset.kaynak==='ai') ? '✨ AI yazdı' : '⚙️ kural motoru';
    })
    .catch(function(){
      box.innerHTML = '<div class="pai-blok" style="text-align:center;color:var(--sub);padding:30px">İçerik yüklenemedi.<br><a href="#" onclick="paiYukle(paiTip,false);return false" style="color:var(--mavi);font-weight:700">Tekrar dene →</a></div>';
      box.style.opacity = '1';
    })
    .finally(function(){
      paiYukluyor = false;
      if (ref) ref.classList.remove('spin');
    });
}
document.addEventListener('DOMContentLoaded', function(){ paiYukle(paiTip, false); });

function takipDurum(id, durum) {
  api('/api/patron-ai/takip-durum', { id: id, durum: durum }).then(function () { paiYukle(paiTip,false); }).catch(function () { paiYukle(paiTip,false); });
}

// AI eylem onay akışı
var _paiEylemLogId = null;
async function paiEylemOner(intent){
  try{
    const r = await fetch('/api/patron-ai/eylem-oner', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({intent})});
    const j = await r.json();
    if (!j.ok || !j.log_id) { alert(j.hata || 'Önerilemedi.'); return; }
    _paiEylemLogId = j.log_id;
    document.getElementById('paiEylemOzet').textContent = j.ozet;
    document.getElementById('paiEylemSonuc').style.display = 'none';
    document.getElementById('paiEylemYapBtn').disabled = false;
    document.getElementById('paiEylemYapBtn').style.opacity = '1';
    try { document.getElementById('paiEylemIp').textContent = (await (await fetch('https://api.ipify.org?format=json')).json()).ip || 'tespit edilemedi'; } catch(e){ document.getElementById('paiEylemIp').textContent = 'tespit edilemedi'; }
    document.getElementById('paiEylemMod').classList.add('acik');
  }catch(e){ alert('Bağlantı hatası'); }
}

async function paiEylemYap(){
  if (!_paiEylemLogId) return;
  const btn = document.getElementById('paiEylemYapBtn');
  btn.disabled = true; btn.style.opacity = '.6'; btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">hourglass_bottom</span> Yürütülüyor...';
  try{
    const r = await fetch('/api/ai-eylem/onayla', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({log_id: _paiEylemLogId})});
    const j = await r.json();
    const son = j.sonuc || {};
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block';
    el.className = 'pai-sonuc ' + (son.ok ? 'ok' : 'hata');
    el.textContent = son.mesaj || 'Yürütme sonucu alınamadı.';
    btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">check</span> İşlemi Yap';
  }catch(e){
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block'; el.className = 'pai-sonuc hata'; el.textContent = 'Bağlantı hatası';
    btn.disabled = false; btn.style.opacity = '1';
  }
}

function paiEylemKapat(){
  document.getElementById('paiEylemMod').classList.remove('acik');
  _paiEylemLogId = null;
}
</script>
@endpush
@endsection

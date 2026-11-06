@extends('layout.app')
@section('title', 'AI Santral — Aktarma Ayarları')
@section('baslik', 'AI Santral — Aktarma & Teslimat')
@section('rk_ikon', 'phone_forwarded')
@section('rk_alt', 'AI "sizi yetkiliye bağlıyorum" dediğinde çağrı kime gitsin? Asterisk\'e dokunmanıza gerek yok.')
@push('head')
<style>
  .st-kok{--indigo:#4f46e5;--indigo2:#6366f1;--ok:#16a34a;--red:#ef4444;--acik:color-mix(in srgb,#6366f1 12%,transparent);color:var(--ink);max-width:680px;margin:0 auto}
  .st-kok *{box-sizing:border-box}
  .st-kok .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:22px;margin-bottom:16px}
  .st-kok .card h2{font-size:14px;color:var(--sub);text-transform:uppercase;letter-spacing:.04em;margin:0 0 14px}
  .st-kok label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px}
  .st-kok input[type=text],.st-kok input[type=number],.st-kok select,.st-kok textarea{width:100%;border:1px solid var(--line);border-radius:10px;padding:12px;font-size:15px;outline:none;background:var(--card2);color:var(--ink);font-family:inherit}
  .st-kok textarea{font-size:14px;resize:vertical}
  .st-kok input:focus,.st-kok select:focus,.st-kok textarea:focus{border-color:var(--indigo);background:var(--card)}
  .st-kok .hint{font-size:12px;color:var(--sub);margin-top:5px;line-height:1.5}
  .st-kok .switch{display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:600;font-size:15px;margin:0}
  .st-kok .switch input{width:44px;height:26px;appearance:none;background:#cbd5e1;border-radius:20px;position:relative;cursor:pointer;transition:.2s;flex:none}
  .st-kok .switch input:checked{background:var(--ok)}
  .st-kok .switch input::after{content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;background:#fff;border-radius:50%;transition:.2s}
  .st-kok .switch input:checked::after{left:21px}
  .st-kok .strat{display:flex;gap:10px;flex-wrap:wrap}
  .st-kok .strat label{flex:1;min-width:220px;margin:0;border:2px solid var(--line);border-radius:12px;padding:12px 14px;cursor:pointer;display:flex;gap:10px;align-items:flex-start;font-weight:600}
  .st-kok .strat label.sec{border-color:var(--indigo);background:var(--acik)}
  .st-kok .strat input{margin-top:3px}
  .st-kok .strat small{display:block;color:var(--sub);font-weight:400;font-size:12px;margin-top:3px}
  .st-kok .hrow{display:flex;gap:8px;align-items:flex-end;margin-bottom:10px;padding:12px;border:1px solid var(--line);border-radius:12px;background:var(--card2)}
  .st-kok .hrow .f{flex:1;min-width:0}.st-kok .hrow .f.k{max-width:120px}.st-kok .hrow label{margin:0 0 5px}
  .st-kok .hrow input,.st-kok .hrow select{background:var(--card)}
  .st-kok .hrow .sil{border:none;background:color-mix(in srgb,#ef4444 15%,transparent);color:var(--red);width:40px;height:44px;border-radius:10px;font-size:20px;cursor:pointer;flex:none}
  .st-kok .ekle{border:1px dashed var(--indigo);background:var(--acik);color:var(--ink);border-radius:10px;padding:11px;width:100%;font-weight:700;cursor:pointer;font-size:14px}
  .st-kok .onizle{background:var(--acik);border:1px dashed var(--line);border-radius:10px;padding:12px;font-family:monospace;font-size:14px;color:var(--ink);margin-top:6px;word-break:break-all;white-space:pre-wrap}
  .st-kok .kaydet{width:100%;border:none;border-radius:12px;padding:15px;font-size:16px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--indigo),var(--indigo2));cursor:pointer;margin:0 0 16px}
  .st-kok .basari{background:color-mix(in srgb,#22c55e 15%,transparent);border:1px solid #86efac;color:var(--ink);padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600}
  .st-kok .gizli{display:none}
</style>
@endpush
@section('content')
<div class="st-kok">
  @if(request('kaydedildi'))<div class="basari">✅ Ayarlar kaydedildi. Yeni çağrılarda geçerli.</div>@endif

  <form method="post" action="/santral-ayar-kaydet" id="frm">
    @csrf
    <input type="hidden" name="sube_id" value="{{ $subeId }}">
    <input type="hidden" name="hedefler" id="hedeflerJson">

    <div class="card">
      <h2>Aktarma</h2>
      <label class="switch">
        <input type="checkbox" name="aktarma_aktif" value="1" {{ (!$ay || $ay->aktarma_aktif) ? 'checked' : '' }}>
        Aktarma açık (AI gerektiğinde çağrıyı yönlendirsin)
      </label>
      <div class="hint">Kapalıysa AI aktarmaz; kendisi çözmeye çalışır.</div>
    </div>

    <div class="card">
      <h2>Çalma stratejisi</h2>
      <div class="strat" id="strat">
        <label id="l_hepsi">
          <input type="radio" name="strateji" value="hepsi" {{ (!$ay || $ay->strateji!=='sirali') ? 'checked' : '' }}>
          <span>Hepsi aynı anda çalsın<small>Tüm dahililer birlikte çalar, ilk açan alır. En hızlı.</small></span>
        </label>
        <label id="l_sirali">
          <input type="radio" name="strateji" value="sirali" {{ ($ay && $ay->strateji==='sirali') ? 'checked' : '' }}>
          <span>Sırayla çalsın<small>Önce 1., açmazsa 2., sonra 3.... Öncelik sırasıyla.</small></span>
        </label>
      </div>
    </div>

    <div class="card">
      <h2>Hedefler (dahili / cep)</h2>
      <div id="satirlar"></div>
      <button type="button" class="ekle" onclick="satirEkle()">+ Hedef ekle</button>
      <div class="hint" id="ziluyari"></div>

      <label>Zil süresi (saniye)</label>
      <input type="number" name="zil_sure" value="{{ $ay->zil_sure ?? 30 }}" min="5" max="120" style="max-width:150px" oninput="ciz()">

      <label style="margin-top:16px">Aranacak hedefler (Asterisk'e giden)</label>
      <div class="onizle" id="onizle">—</div>
    </div>

    <div class="card">
      <h2>🛵 Teslimat Bölgesi</h2>
      <label>Nereye teslimat yapıyorsunuz? (AI bunu uygular)</label>
      <textarea name="teslimat_bolge" rows="3" placeholder="Örn: Sadece Bayraklı, Karşıyaka ve Çiğli'ye teslimat yapıyoruz. Bu ilçeler dışına gönderim yok. Minimum sepet 150 TL." >{{ $ay->teslimat_bolge ?? '' }}</textarea>
      <div class="hint">Burayı kendi dilinizle doldurun (ilçeler/mahalleler/mesafe kuralı). AI, sipariş adresini buna göre değerlendirir; bölge dışına (ör. başka il, çok uzak) <b>nazikçe reddeder</b> ve gel-al önerir. Boş bırakırsanız her adrese sipariş alır.</div>
    </div>

    <button class="kaydet" type="submit">Kaydet</button>
  </form>

  <div class="card" style="margin-top:16px">
    <h2>Asterisk kurulumu</h2>
    <div class="hint">
      ✅ Aktarma için Asterisk'e <b>hiçbir şey yazmanıza gerek yok.</b> Köprü, buraya girdiğiniz hedefleri
      okuyup çağrıyı kendisi arar (seçtiğiniz stratejiye göre) ve açan hattı arayanla birleştirir.
      Hedefleri değiştirmek için sadece bu sayfayı kullanın.<br><br>
      Tek gereken (restoran başına bir kez), gelen çağrının AI'ya ulaşması için:
      <div class="onizle">exten =&gt; s,1,Answer()<br> same =&gt; n,Stasis(restaurant-santral-ai)<br> same =&gt; n,Hangup()</div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
  const BASLANGIC = {!! json_encode($hedefler ?: [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
  const satirlarEl = document.getElementById('satirlar');

  function satirEkle(h){
    h = h || {tip:'dahili', teknoloji:'SIP', numara:'', trunk:''};
    const row = document.createElement('div');
    row.className = 'hrow';
    row.innerHTML = `
      <div class="f" style="max-width:150px">
        <label>Tip</label>
        <select class="tip" onchange="ciz()">
          <option value="dahili" ${h.tip!=='dis'?'selected':''}>Dahili</option>
          <option value="dis" ${h.tip==='dis'?'selected':''}>Cep / dış</option>
        </select>
      </div>
      <div class="f">
        <label class="numLbl">Numara</label>
        <input type="text" class="numara" value="${(h.numara||'').replace(/"/g,'&quot;')}" placeholder="101" oninput="ciz()">
      </div>
      <div class="f k">
        <label>Teknoloji</label>
        <select class="teknoloji" onchange="ciz()">
          <option value="SIP" ${h.teknoloji!=='PJSIP'?'selected':''}>SIP</option>
          <option value="PJSIP" ${h.teknoloji==='PJSIP'?'selected':''}>PJSIP</option>
        </select>
      </div>
      <div class="f trunkAlan gizli">
        <label>Dış hat (trunk)</label>
        <input type="text" class="trunk" value="${(h.trunk||'').replace(/"/g,'&quot;')}" placeholder="ops." oninput="ciz()">
      </div>
      <button type="button" class="sil" onclick="this.closest('.hrow').remove();ciz()">×</button>`;
    satirlarEl.appendChild(row);
    ciz();
  }

  function topla(){
    const out = [];
    satirlarEl.querySelectorAll('.hrow').forEach(r=>{
      const tip = r.querySelector('.tip').value;
      const num = r.querySelector('.numara').value.trim();
      const tek = r.querySelector('.teknoloji').value;
      const trunk = r.querySelector('.trunk').value.trim();
      r.querySelector('.trunkAlan').className = 'f trunkAlan' + (tip==='dis'?'':' gizli');
      r.querySelector('.numLbl').textContent = (tip==='dis') ? 'Cep / sabit numara' : 'Dahili numara';
      r.querySelector('.numara').placeholder = (tip==='dis') ? '05xxxxxxxxx' : '101';
      if(num) out.push({tip, teknoloji:tek, numara:num, trunk:trunk||null});
    });
    return out;
  }

  function dial(h){
    if(!h.numara) return '';
    return (h.tip==='dis' && h.trunk) ? `${h.teknoloji}/${h.trunk}/${h.numara}` : `${h.teknoloji}/${h.numara}`;
  }

  function ciz(){
    const hedefler = topla();
    document.getElementById('hedeflerJson').value = JSON.stringify(hedefler);
    const strat = (document.querySelector('input[name=strateji]:checked')||{}).value || 'hepsi';
    document.getElementById('l_hepsi').className = strat==='hepsi' ? 'sec' : '';
    document.getElementById('l_sirali').className = strat==='sirali' ? 'sec' : '';
    const zil = document.querySelector('input[name=zil_sure]').value || 30;
    document.getElementById('ziluyari').textContent = strat==='sirali'
      ? `Sırayla: her hedef ${zil} sn çalar, açılmazsa sıradakine geçilir.`
      : `Hepsi birlikte ${zil} sn çalar; ilk açan alır.`;
    const dials = hedefler.map(dial).filter(Boolean);
    document.getElementById('onizle').textContent = dials.length
      ? (strat==='sirali' ? dials.join('  →  ') : dials.join('   +   '))
      : '—';
  }

  document.querySelectorAll('input[name=strateji]').forEach(el=>el.addEventListener('change',ciz));

  // baslangic satirlari
  if(BASLANGIC.length) BASLANGIC.forEach(satirEkle); else satirEkle();
  ciz();
</script>
@endpush

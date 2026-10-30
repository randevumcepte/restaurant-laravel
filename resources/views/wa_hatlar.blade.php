@extends('layout.app')
@section('rk_ikon', 'forum')
@section('baslik', 'WhatsApp Hatları')
@section('title', 'WhatsApp Hatları')

@push('head')
<script src="/js/qrcode-gen.js?v=3"></script>
<style>
#wh{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#25D366;--kirmizi:#F43F5E;--amber:#F59E0B;--pembe:#EC4899;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #wh{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#wh{background:var(--bg);min-height:calc(100vh - 60px)}#wh *{box-sizing:border-box}
#wh .pano{max-width:880px;margin:0 auto;padding:18px 18px 46px}
#wh .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px}
#wh .flash.ok{background:rgba(37,211,102,.15);color:#0b7a42}#wh .flash.err{background:rgba(244,63,94,.14);color:#be1e3c}
#wh .aciklama{background:var(--card);border-left:4px solid var(--yesil);border-radius:12px;padding:13px 15px;font-size:12.5px;color:var(--sub);line-height:1.6;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#wh .aciklama b{color:var(--ink)}
#wh .legend{display:flex;gap:7px;flex-wrap:wrap;margin-top:9px}
#wh .lg{font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px}
#wh .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:15px;margin-bottom:13px}
#wh .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#wh .kb .material-icons-outlined{color:var(--yesil);font-size:20px}
#wh label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#wh input,#wh select{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:13.5px;width:100%;font-family:inherit}
#wh .satir{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
#wh .btn{border:0;border-radius:11px;padding:10px 16px;font-weight:700;font-size:13px;cursor:pointer;color:#fff;display:inline-flex;align-items:center;gap:6px}
#wh .btn .material-icons-outlined{font-size:17px}
#wh .btn.yesil{background:var(--yesil)}#wh .btn.mor{background:var(--mor1)}#wh .btn.gri{background:var(--card2);color:var(--sub)}
#wh .btn.kir{background:transparent;color:var(--kirmizi);border:1px solid color-mix(in srgb,var(--kirmizi) 40%,transparent)}
#wh .btn.sm{padding:7px 12px;font-size:12px}
#wh .hat{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px;margin-bottom:11px;position:relative;overflow:hidden}
#wh .hat.bagli{border-color:color-mix(in srgb,var(--yesil) 45%,var(--line))}
#wh .hat-ust{display:flex;align-items:center;gap:11px;flex-wrap:wrap}
#wh .hat-ikon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:0 0 42px;font-size:21px}
#wh .hat-ad{font-size:15px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:7px}
#wh .hat-ad .yildiz{color:var(--amber);font-size:17px}
#wh .hat-meta{font-size:12px;color:var(--sub);margin-top:2px}
#wh .amac-roz{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px}
#wh .durum-roz{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px;margin-left:auto}
#wh .durum-roz.bagli{background:rgba(37,211,102,.16);color:#0b7a42}
#wh .durum-roz.bekliyor{background:rgba(245,158,11,.16);color:#9a6400}
#wh .hat-aks{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px;padding-top:11px;border-top:1px solid var(--line)}
#wh .duzen{margin-top:11px;display:none;background:var(--card2);border-radius:12px;padding:12px}
#wh .duzen.ac{display:block}
#wh .duzen .satir{margin-bottom:9px}
#wh .amacsec{display:flex;flex-direction:column}
#wh #qrModal{position:fixed;inset:0;background:rgba(15,23,42,.6);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px}
#wh #qrModal.ac{display:flex}
#wh .qrkart{background:#fff;border-radius:20px;padding:22px;width:320px;max-width:100%;text-align:center}
#wh .qrkart h3{margin:0 0 4px;font-size:17px;color:#0f172a;font-weight:800}
#wh .qrkart .alt{font-size:12px;color:#64748b;margin-bottom:12px}
#wh #qrbox{background:#fff;border-radius:14px;padding:10px;width:250px;margin:0 auto}
#wh #qrbox canvas,#wh #qrbox img{width:100%;height:auto}
#wh #qrDurum{font-size:13px;font-weight:700;margin-top:12px;color:#0b7a42}
#wh .qrkapat{margin-top:14px;background:#f1f5f9;color:#64748b;border:0;border-radius:11px;padding:10px 18px;font-weight:700;cursor:pointer;font-size:13px}
#wh .pair{margin-top:12px;border-top:1px dashed #e2e8f0;padding-top:12px}
#wh .pair input{border:1px solid #e2e8f0;border-radius:9px;padding:8px 10px;font-size:13px;width:100%;color:#0f172a}
#wh .bos{text-align:center;color:var(--sub2);padding:22px;font-size:13px}
</style>
@endpush

@section('content')
@php
  $amacLabel = ['musteri'=>'🧑‍🍳 Müşteri','satinalma'=>'🛒 Satın Alma','yonetim'=>'👔 Yönetim','genel'=>'📋 Genel'];
  $amacRenk  = ['musteri'=>'#4F46E5','satinalma'=>'#7C3AED','yonetim'=>'#0EA5E9','genel'=>'#64748b'];
@endphp
<div id="wh"><div class="pano">

  @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="flash err">{{ session('hata') }}</div>@endif

  <div class="aciklama">
    @if($isAdmin)
      <b>Çoklu WhatsApp hattı:</b> Müşteri yazışmaları (sipariş, hatırlatma, kampanya) <b>müşteri hattından</b>; tedarikçi teklifleri <b>satın alma hattından</b>; onay/yönetim bildirimleri <b>yönetim hattından</b> gider. Her hat ayrı bir WhatsApp numarasıdır — <b>sahibi (departman sorumlusu) kendi hesabından girip kendi şirket telefonuyla bağlar/yeniden bağlar</b>. Sen hattı oluşturup bir sorumluya atarsın; bağlamayı o kişi kendi yapar.
      <div class="legend">
        @foreach($amacLabel as $k=>$v)<span class="lg" style="background:color-mix(in srgb,{{ $amacRenk[$k] }} 14%,transparent);color:{{ $amacRenk[$k] }}">{{ $v }}</span>@endforeach
      </div>
    @else
      <b>Sana atanan WhatsApp hattı:</b> Aşağıdaki hattı <b>kendi şirket telefonunla</b> QR okutarak bağla. Bağlantı koparsa yine buradan <b>"Bağlan / QR"</b> ile yeniden bağlarsın — yöneticiyi beklemene gerek yok.
    @endif
  </div>

  @unless($sidecarVar)
    <div class="flash err">⚠️ WhatsApp sunucusu (bridge) ayarlı değil. Önce <b>/wa-yonetim</b> → sidecar URL + servis token gir. Hat bağlama onsuz çalışmaz.</div>
  @endunless

  {{-- YENİ HAT (sadece sahip/müdür) --}}
  @if($isAdmin)
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_link</span> Yeni Hat Ekle</div>
    <form method="POST" action="/wa-hatlar/ekle">@csrf
      <div class="satir">
        <div style="flex:1;min-width:160px"><label>Hat Adı</label><input name="ad" placeholder="örn. Satın Alma Hattı / Müdür Ahmet" required></div>
        <div style="flex:0 0 150px"><label>Amaç</label>
          <select name="amac">
            <option value="satinalma">🛒 Satın Alma</option>
            <option value="yonetim">👔 Yönetim</option>
            <option value="musteri">🧑‍🍳 Müşteri</option>
            <option value="genel">📋 Genel</option>
          </select>
        </div>
        <div style="flex:0 0 170px"><label>Sahip (opsiyonel)</label>
          <select name="personel_id">
            <option value="">— yok —</option>
            @foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }} ({{ $p->rol }})</option>@endforeach
          </select>
        </div>
        <button class="btn yesil" type="submit"><span class="material-icons-outlined">add</span> Ekle</button>
      </div>
    </form>
  </div>
  @endif

  {{-- HATLAR --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">dns</span> Hatlar ({{ count($hatlar) }})</div>
    @forelse($hatlar as $h)
      <div class="hat {{ $h->durum==='bagli'?'bagli':'' }}" id="hat{{ $h->id }}">
        <div class="hat-ust">
          <div class="hat-ikon" style="background:color-mix(in srgb,{{ $amacRenk[$h->amac]??'#64748b' }} 14%,transparent)">{{ \Illuminate\Support\Str::of($amacLabel[$h->amac]??'📋')->explode(' ')->first() }}</div>
          <div>
            <div class="hat-ad">{{ $h->ad }} @if($h->varsayilan)<span class="material-icons-outlined yildiz" title="Varsayılan">star</span>@endif @unless($h->aktif)<span style="font-size:11px;color:var(--kirmizi)">(pasif)</span>@endunless</div>
            <div class="hat-meta">
              <span class="amac-roz" style="background:color-mix(in srgb,{{ $amacRenk[$h->amac]??'#64748b' }} 14%,transparent);color:{{ $amacRenk[$h->amac]??'#64748b' }}">{{ $amacLabel[$h->amac]??$h->amac }}</span>
              @if($h->sahip_ad) · 👤 {{ $h->sahip_ad }}@endif
              @if($h->numara) · 📞 {{ $h->numara }}@endif
            </div>
          </div>
          <span class="durum-roz {{ $h->durum==='bagli'?'bagli':'bekliyor' }}" id="drz{{ $h->id }}">{{ $h->durum==='bagli'?'● Bağlı':'○ Bekliyor' }}</span>
        </div>
        <div class="hat-aks">
          <button class="btn yesil sm" onclick="qrAc({{ $h->id }},'{{ addslashes($h->ad) }}')"><span class="material-icons-outlined">qr_code_2</span> Bağlan / QR</button>
          <button class="btn gri sm" onclick="baglantiKes({{ $h->id }})"><span class="material-icons-outlined">link_off</span> Bağlantıyı kes</button>
          @if($isAdmin)
          <form method="POST" action="/wa-hatlar/guncelle" style="margin:0"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="id" value="{{ $h->id }}"><input type="hidden" name="varsayilan" value="1"><button class="btn gri sm" type="submit">⭐ Varsayılan yap</button></form>
          <form method="POST" action="/wa-hatlar/guncelle" style="margin:0"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="id" value="{{ $h->id }}"><input type="hidden" name="aktif" value="{{ $h->aktif?0:1 }}"><button class="btn gri sm" type="submit">{{ $h->aktif?'⏸ Pasifleştir':'▶ Aktifleştir' }}</button></form>
          <button class="btn gri sm" onclick="document.getElementById('dz{{ $h->id }}').classList.toggle('ac')"><span class="material-icons-outlined">edit</span> Düzenle</button>
          <form method="POST" action="/wa-hatlar/sil" style="margin:0" onsubmit="return confirm('Hat silinsin mi? Bağlantı da kesilir.')"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="id" value="{{ $h->id }}"><button class="btn kir sm" type="submit">🗑 Sil</button></form>
          @endif
        </div>
        @if($isAdmin)
        <div class="duzen" id="dz{{ $h->id }}">
          <form method="POST" action="/wa-hatlar/guncelle">@csrf<input type="hidden" name="id" value="{{ $h->id }}">
            <div class="satir">
              <div style="flex:1;min-width:150px"><label>Hat Adı</label><input name="ad" value="{{ $h->ad }}"></div>
              <div style="flex:0 0 140px"><label>Amaç</label>
                <select name="amac">
                  @foreach($amacLabel as $k=>$v)<option value="{{ $k }}" @if($h->amac===$k)selected @endif>{{ $v }}</option>@endforeach
                </select>
              </div>
              <div style="flex:0 0 160px"><label>Sahip</label>
                <select name="personel_id"><option value="">— yok —</option>
                  @foreach($personeller as $p)<option value="{{ $p->id }}" @if($h->personel_id==$p->id)selected @endif>{{ $p->ad }}</option>@endforeach
                </select>
              </div>
              <button class="btn mor sm" type="submit"><span class="material-icons-outlined">save</span> Kaydet</button>
            </div>
          </form>
        </div>
        @endif
      </div>
    @empty
      <div class="bos">
        @if($isAdmin)
          Henüz hat yok. Yukarıdan ekle.
        @else
          Sana atanmış bir WhatsApp hattı yok.<br>Yöneticinin bir hat oluşturup <b>sahip</b> olarak seni ataması gerekiyor.
        @endif
      </div>
    @endforelse
  </div>

</div></div>

{{-- QR MODAL --}}
<div id="qrModal">
  <div class="qrkart">
    <h3 id="qrBaslik">Hat Bağla</h3>
    <div class="alt">WhatsApp → Ayarlar → <b>Bağlı Cihazlar</b> → <b>Cihaz Bağla</b> ile tara</div>
    <div id="qrbox"><div id="qr"></div></div>
    <div id="qrDurum">QR hazırlanıyor…</div>
    <div class="pair">
      <label style="font-size:11px;color:#64748b;display:block;margin-bottom:5px">QR tarayamıyorsan telefon no ile eşleştir:</label>
      <div style="display:flex;gap:7px">
        <input id="pairNo" placeholder="905xxxxxxxxx">
        <button class="qrkapat" style="background:#7C3AED;color:#fff;margin:0" onclick="pairEt()">Kod Al</button>
      </div>
      <div id="pairKod" style="font-size:18px;font-weight:900;color:#0f172a;margin-top:8px"></div>
    </div>
    <button class="qrkapat" onclick="qrKapat()">Kapat</button>
  </div>
</div>

<script>
let whHat=0, qrTimer=null, durumTimer=null;
function $(s){return document.querySelector(s);}
async function qrAc(id,ad){
  whHat=id; $('#qrBaslik').textContent=ad+' — Bağla'; $('#pairKod').textContent=''; $('#pairNo').value='';
  $('#qr').innerHTML=''; $('#qrDurum').textContent='QR hazırlanıyor…'; $('#qrModal').classList.add('ac');
  try{ const r=await fetch('/api/wa-hat/baglan',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({hat:id})}); const j=await r.json(); const b=j.body||{}; if(b.qr) drawQR(b.qr);}catch(e){}
  if(qrTimer)clearInterval(qrTimer); if(durumTimer)clearInterval(durumTimer);
  setTimeout(qrGetir,900); qrTimer=setInterval(qrGetir,2500); durumTimer=setInterval(durumKontrol,3000);
}
function drawQR(text){
  const box=$('#qr'); if(!text){box.innerHTML='';return;}
  if(String(text).startsWith('data:')){box.innerHTML='<img src="'+text+'">';return;}
  try{const qr=qrcode(0,'L');qr.addData(String(text));qr.make();box.innerHTML=qr.createImgTag(4,4);}catch(e){box.textContent='QR çizilemedi';}
}
async function qrGetir(){
  try{const r=await fetch('/api/wa-hat/qr?hat='+whHat+'&_='+Date.now(),{cache:'no-store'});const j=await r.json();const b=j.body||{};const v=b.qr||b.qrcode||b.code||b.image||'';if(v)drawQR(v);}catch(e){}
}
async function durumKontrol(){
  try{const r=await fetch('/api/wa-hat/durum?hat='+whHat+'&_='+Date.now(),{cache:'no-store'});const j=await r.json();const b=j.body||{};
    const bagli=b.connected||b.loggedIn||b.ready||(b.status==='connected')||b.number;
    if(bagli){ $('#qrDurum').textContent='✓ Bağlandı!'; $('#qr').innerHTML='🎉'; if(qrTimer)clearInterval(qrTimer); if(durumTimer)clearInterval(durumTimer); setTimeout(()=>location.reload(),1300); }
  }catch(e){}
}
async function pairEt(){
  const phone=$('#pairNo').value.trim(); if(!phone){alert('Numara gir');return;}
  try{const r=await fetch('/api/wa-hat/pair',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({hat:whHat,phone})});const j=await r.json();const b=j.body||{};$('#pairKod').textContent=b.code||b.pairCode||b.pairingCode||'Kod alınamadı';}catch(e){$('#pairKod').textContent='Hata';}
}
function qrKapat(){$('#qrModal').classList.remove('ac');if(qrTimer)clearInterval(qrTimer);if(durumTimer)clearInterval(durumTimer);}
async function baglantiKes(id){
  if(!confirm('Bu hattın WhatsApp bağlantısı kesilsin mi? Tekrar bağlamak için QR okutman gerekir.'))return;
  try{await fetch('/api/wa-hat/cikis',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({hat:id})});}catch(e){}
  location.reload();
}
$('#qrModal').addEventListener('click',function(e){if(e.target===this)qrKapat();});
// açılışta her hattın durumunu bir kez tazele
@foreach($hatlar as $h)
(async()=>{try{const r=await fetch('/api/wa-hat/durum?hat={{ $h->id }}&_='+Date.now(),{cache:'no-store'});const j=await r.json();const b=j.body||{};const bagli=b.connected||b.loggedIn||b.ready||(b.status==='connected')||b.number;const rz=document.getElementById('drz{{ $h->id }}');if(rz){if(bagli){rz.textContent='● Bağlı';rz.className='durum-roz bagli';document.getElementById('hat{{ $h->id }}').classList.add('bagli');}else{rz.textContent='○ Bekliyor';rz.className='durum-roz bekliyor';}}}catch(e){}})();
@endforeach
</script>
@endsection

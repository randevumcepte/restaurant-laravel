@extends('layout.app')
@section('rk_ikon', 'campaign')
@section('baslik', 'AI Pazarlama / Kampanya')
@section('title', 'AI Kampanya')

@push('head')
<style>
#kp{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--pembe:#EC4899;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #kp{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#kp{background:var(--bg);min-height:calc(100vh - 60px)}#kp *{box-sizing:border-box}
#kp .pano{max-width:1000px;margin:0 auto;padding:18px 18px 40px}
#kp .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
#kp .kontor{background:linear-gradient(135deg,var(--yesil),#0b805e);color:#fff;border-radius:12px;padding:8px 14px;font-weight:800;font-size:13px;white-space:nowrap}
#kp .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin:4px 2px 12px}
#kp .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#kp .seg-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:18px}
#kp .seg{background:var(--card);border:2px solid var(--line);border-radius:16px;padding:15px;cursor:pointer;transition:.15s;text-align:left}
#kp .seg:hover{transform:translateY(-2px);box-shadow:0 8px 20px -8px rgba(0,0,0,.15)}
#kp .seg.sec{border-color:var(--c)}
#kp .seg .ik{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:10px;background:color-mix(in srgb,var(--c) 15%,transparent)}
#kp .seg .ik .material-icons-outlined{font-size:22px;color:var(--c)}
#kp .seg .ad{font-size:14px;font-weight:800;color:var(--ink)}
#kp .seg .ac{font-size:11px;color:var(--sub);margin-top:2px;line-height:1.3}
#kp .seg .say{font-size:22px;font-weight:900;color:var(--c);margin-top:7px}
#kp .seg .say small{font-size:11px;color:var(--sub);font-weight:600}
#kp .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#kp label{font-size:11.5px;font-weight:700;color:var(--sub);display:block;margin-bottom:5px}
#kp input[type=text],#kp textarea{width:100%;border:1px solid var(--line);border-radius:11px;padding:11px 13px;background:var(--card);color:var(--ink);font-size:13.5px;font-family:inherit}
#kp textarea{height:92px;resize:vertical;line-height:1.5}
#kp .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;display:inline-flex;align-items:center;gap:7px}
#kp .btn.mor{background:var(--mor1)}#kp .btn.yesil{background:var(--yesil)}#kp .btn.bos{background:var(--card);color:var(--mor1);border:1.5px solid var(--mor1)}
#kp .btn .material-icons-outlined{font-size:18px}
#kp .btn:disabled{opacity:.5;cursor:not-allowed}
#kp .satir{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:12px}
#kp .onizle{background:var(--card2);border-radius:12px;padding:12px 14px;font-size:13.5px;color:var(--ink);line-height:1.55;white-space:pre-wrap;margin-top:10px;border-left:3px solid var(--yesil)}
#kp .sonuc{border-radius:12px;padding:12px 15px;margin-top:12px;font-weight:600;font-size:13.5px}
#kp .sonuc.ok{background:rgba(16,185,129,.14);color:#0b805e}#kp .sonuc.err{background:rgba(244,63,94,.14);color:#be1e3c}
#kp .gec{font-size:12.5px}
#kp .gec .g{display:flex;gap:10px;padding:9px 0;border-top:1px solid var(--line);align-items:center}
#kp .gec .g:first-child{border-top:none}
#kp .gec .roz{font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)}
#kp .gec .gm{flex:1;color:var(--sub);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#kp .gec .gn{font-weight:800;color:var(--yesil)}
#kp .ipucu{font-size:11px;color:var(--sub2);margin-top:5px}
</style>
@endpush

@section('content')
@php $segRen=['winback'=>'#F59E0B','dogum'=>'#EC4899','vip'=>'#F6CE63','son30'=>'#10B981','tumu'=>'#4F46E5']; @endphp
<div id="kp"><div class="pano">

  <div class="aciklama">
    <div><b>AI Kampanya:</b> Hedef kitleyi seç → AI kişiselleştirilmiş WhatsApp mesajı yazsın → onayla → tek tıkla gönder. Mesajda <b>{ad}</b> otomatik müşteri adıyla değişir.</div>
    <div class="kontor">📱 {{ number_format($kontor,0,',','.') }} kontör</div>
  </div>

  <div class="kb"><span class="material-icons-outlined">groups</span> 1. Hedef Kitle Seç</div>
  <div class="seg-grid">
    @foreach($segmentler as $k=>$v)
      <div class="seg" data-seg="{{ $k }}" style="--c:{{ $segRen[$k] ?? '#7C3AED' }}" onclick="segSec(this)">
        <div class="ik"><span class="material-icons-outlined">{{ $v[2] }}</span></div>
        <div class="ad">{{ $v[0] }}</div>
        <div class="ac">{{ $v[1] }}</div>
        <div class="say">{{ $sayilar[$k] ?? 0 }} <small>müşteri</small></div>
      </div>
    @endforeach
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">auto_awesome</span> 2. Mesajı Oluştur</div>
    <label>Kampanya / teklif detayı (opsiyonel — AI buna göre yazar)</label>
    <input type="text" id="teklif" placeholder="örn. bu hafta sonu tüm tatlılarda %20 indirim">
    <div class="satir">
      <button class="btn mor" id="aiBtn" onclick="aiYaz()"><span class="material-icons-outlined">auto_awesome</span> AI Metin Yaz</button>
      <span class="ipucu" id="segBilgi">Önce hedef kitle seç</span>
    </div>
    <div id="mesajAlan" style="display:none;margin-top:12px">
      <label>Mesaj (düzenleyebilirsin — <b>{ad}</b> müşteri adıyla değişir)</label>
      <textarea id="mesaj"></textarea>
      <div class="satir">
        <button class="btn yesil" id="gonderBtn" onclick="gonder()"><span class="material-icons-outlined">send</span> Kampanyayı Gönder</button>
        <button class="btn bos" onclick="aiYaz()"><span class="material-icons-outlined">refresh</span> Yeniden Yaz</button>
      </div>
      <div class="ipucu">💡 Gönderim WhatsApp üzerinden; her mesaj 1 kontör. {ad} yer tutucusu her müşteride adıyla değişir.</div>
    </div>
    <div id="sonuc"></div>
  </div>

  @if($gecmis->count())
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">history</span> Son Kampanyalar</div>
    <div class="gec">
      @foreach($gecmis as $g)
        <div class="g">
          <span class="roz">{{ $segmentler[$g->segment][0] ?? $g->segment }}</span>
          <span class="gm">{{ \Illuminate\Support\Str::limit($g->mesaj, 60) }}</span>
          <span class="gn">{{ $g->gonderilen }}/{{ $g->hedef }}</span>
          <span style="color:var(--sub2);font-size:11px">{{ \Carbon\Carbon::parse($g->created_at)->diffForHumans() }}</span>
        </div>
      @endforeach
    </div>
  </div>
  @endif

</div></div>

<script>
const KP_CSRF=document.querySelector('meta[name=csrf-token]').content;
let segSecili='';
const SAY={@foreach($sayilar as $k=>$n)'{{ $k }}':{{ $n }},@endforeach};
const SEGAD={@foreach($segmentler as $k=>$v)'{{ $k }}':'{{ $v[0] }}',@endforeach};
function segSec(el){
  document.querySelectorAll('#kp .seg').forEach(s=>s.classList.remove('sec'));
  el.classList.add('sec'); segSecili=el.dataset.seg;
  document.getElementById('segBilgi').innerHTML='<b style="color:var(--mor1)">'+SEGAD[segSecili]+'</b> — '+(SAY[segSecili]||0)+' müşteriye gidecek';
}
async function aiYaz(){
  if(!segSecili){alert('Önce hedef kitle seç.');return;}
  const btn=document.getElementById('aiBtn'); btn.disabled=true; btn.innerHTML='<span class="material-icons-outlined">hourglass_top</span> AI yazıyor...';
  try{
    const r=await fetch('/kampanya/ai-metin',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':KP_CSRF,'Accept':'application/json'},body:JSON.stringify({segment:segSecili,teklif:document.getElementById('teklif').value})});
    const j=await r.json();
    document.getElementById('mesaj').value=j.metin||'';
    document.getElementById('mesajAlan').style.display='block';
  }catch(e){alert('AI metin alınamadı, tekrar dene.');}
  btn.disabled=false; btn.innerHTML='<span class="material-icons-outlined">auto_awesome</span> AI Metin Yaz';
}
async function gonder(){
  const mesaj=document.getElementById('mesaj').value.trim();
  if(!mesaj){alert('Mesaj boş.');return;}
  const n=SAY[segSecili]||0;
  if(!confirm(SEGAD[segSecili]+' segmentindeki '+n+' müşteriye WhatsApp gönderilecek ('+n+' kontör). Onaylıyor musun?'))return;
  const btn=document.getElementById('gonderBtn'); btn.disabled=true; btn.innerHTML='<span class="material-icons-outlined">hourglass_top</span> Gönderiliyor...';
  try{
    const r=await fetch('/kampanya/gonder',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':KP_CSRF,'Accept':'application/json'},body:JSON.stringify({segment:segSecili,mesaj:mesaj})});
    const j=await r.json();
    const s=document.getElementById('sonuc');
    if(j.ok){s.className='sonuc ok';s.textContent='✅ '+j.gonderilen+' müşteriye gönderildi'+(j.atlanan>0?' ('+j.atlanan+' atlandı — telefon/kontör)':'')+'. Sayfayı yenile.';}
    else{s.className='sonuc err';s.textContent='⚠️ '+(j.hata||'Gönderilemedi');}
  }catch(e){const s=document.getElementById('sonuc');s.className='sonuc err';s.textContent='Bağlantı hatası.';}
  btn.disabled=false; btn.innerHTML='<span class="material-icons-outlined">send</span> Kampanyayı Gönder';
}
</script>
@endsection

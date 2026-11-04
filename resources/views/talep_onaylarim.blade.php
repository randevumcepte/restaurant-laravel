@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Onay Bekleyen Talepler')
@section('title', 'Talep Onaylarım')

@section('content')
<style>
  #to{max-width:900px;margin:0 auto}
  #to *{box-sizing:border-box}
  .to-bos{text-align:center;padding:48px 20px;color:var(--sub);font-size:14px}
  .to-bos .material-icons-outlined{font-size:44px;color:#2FBE94;display:block;margin-bottom:10px}
  .to-kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-bottom:14px}
  .to-ust{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
  .to-dep{font-weight:800;font-size:15px;color:var(--ink)}
  .to-badge{font-size:10.5px;font-weight:800;border-radius:20px;padding:3px 10px;white-space:nowrap}
  .to-acil{background:rgba(244,63,94,.12);color:#e11d48}
  .to-normal{background:rgba(100,116,139,.14);color:var(--sub2)}
  .to-meta{font-size:12px;color:var(--sub);margin-left:auto}
  .to-kalem{display:flex;flex-direction:column;gap:4px;background:var(--card2);border-radius:11px;padding:11px 14px;margin-bottom:10px}
  .to-kalem div{font-size:13px;color:var(--ink);display:flex;gap:7px;align-items:center}
  .to-kalem div::before{content:'•';color:#7C3AED;font-weight:800}
  .to-not{font-size:12.5px;color:var(--sub2);font-style:italic;margin-bottom:10px}
  .to-btns{display:flex;gap:10px;flex-wrap:wrap}
  .to-b{border:0;border-radius:10px;padding:10px 20px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;color:#fff}
  .to-b .material-icons-outlined{font-size:18px}
  .to-onay{background:linear-gradient(135deg,#10B981,#059669)}
  .to-red{background:linear-gradient(135deg,#F43F5E,#E11D48)}
  .to-redkutu{display:none;margin-top:12px;border-top:1px dashed var(--line);padding-top:12px}
  .to-redkutu.acik{display:block}
  .to-redkutu label{font-size:12px;font-weight:700;color:var(--sub);display:block;margin-bottom:5px}
  .to-redkutu textarea{width:100%;border:1.5px solid var(--line);background:var(--card2);color:var(--ink);border-radius:10px;padding:10px 12px;font-size:13px;font-family:inherit;resize:vertical;min-height:62px}
  .to-redkutu textarea:focus{outline:none;border-color:#F43F5E}
  .to-redfoot{display:flex;gap:10px;margin-top:10px}
  .to-vaz{background:var(--card2);color:var(--ink);border:1px solid var(--line)}
</style>
<div class="rk-body">
<div id="to">
  @if($yetkisiz ?? false)
    <div class="to-bos"><span class="material-icons-outlined" style="color:var(--sub2)">lock</span>Onaylayabileceğin bir departman yok. Bu sayfa, bir departmanın <b>sorumlusu</b> olan personele (ve sahip/müdüre) taleplerin onayı için gösterilir.</div>
  @elseif($talepler->isEmpty())
    <div class="to-bos"><span class="material-icons-outlined">task_alt</span>Onayını bekleyen talep yok. 👍</div>
  @else
    @foreach($talepler as $t)
      <div class="to-kart" id="talep{{ $t->id }}">
        <div class="to-ust">
          <span class="to-dep">{{ $t->departman }}</span>
          <span class="to-badge {{ $t->aciliyet === 'acil' ? 'to-acil' : 'to-normal' }}">{{ $t->aciliyet === 'acil' ? '🔴 ACİL' : 'Normal' }}</span>
          <span class="to-meta">👤 {{ $t->isteyen_ad }} · {{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->format('d.m.Y H:i') : '' }}</span>
        </div>
        <div class="to-kalem">
          @forelse($t->kalemler as $k)<div>{{ $k }}</div>@empty<div>Kalem yok</div>@endforelse
        </div>
        @if($t->not)<div class="to-not">Not: {{ $t->not }}</div>@endif
        <div class="to-btns">
          <button class="to-b to-onay" onclick="toOnay({{ $t->id }})"><span class="material-icons-outlined">check</span>Onayla</button>
          <button class="to-b to-red" onclick="toRedAc({{ $t->id }})"><span class="material-icons-outlined">close</span>Reddet</button>
        </div>
        <div class="to-redkutu" id="red{{ $t->id }}">
          <label>Red sebebi (zorunlu) — isteyen bu sebebi görecek</label>
          <textarea id="sebep{{ $t->id }}" placeholder="Örn: Stokta yeterli var / bütçe uygun değil / farklı tedarikçiden alınacak..."></textarea>
          <div class="to-redfoot">
            <button class="to-b to-red" onclick="toRed({{ $t->id }})"><span class="material-icons-outlined">block</span>Reddet ve Kapat</button>
            <button class="to-b to-vaz" onclick="toRedKapat({{ $t->id }})">Vazgeç</button>
          </div>
        </div>
      </div>
    @endforeach
  @endif
</div>
</div>
<script>
  function toRedAc(id){ document.getElementById('red'+id).classList.add('acik'); }
  function toRedKapat(id){ document.getElementById('red'+id).classList.remove('acik'); }
  async function toOnay(id){
    if(!confirm('Talep onaylanıp satın almaya iletilecek. Onaylıyor musun?')) return;
    try{
      const res = await api('/satinalma-talep/dept-onayla', {id:id});
      if(res.ok){ var el=document.getElementById('talep'+id); if(el) el.remove(); toBosKontrol(); }
      else alert(res.mesaj || 'İşlem başarısız.');
    }catch(e){ alert('Bağlantı hatası.'); }
  }
  async function toRed(id){
    var sebep = (document.getElementById('sebep'+id).value || '').trim();
    if(sebep.length < 3){ alert('Red sebebi zorunlu (en az 3 karakter).'); return; }
    try{
      const res = await api('/satinalma-talep/dept-reddet', {id:id, sebep:sebep});
      if(res.ok){ var el=document.getElementById('talep'+id); if(el) el.remove(); toBosKontrol(); }
      else alert(res.mesaj || 'İşlem başarısız.');
    }catch(e){ alert('Bağlantı hatası.'); }
  }
  function toBosKontrol(){ if(!document.querySelector('.to-kart')){ var c=document.getElementById('to'); c.innerHTML='<div class="to-bos"><span class="material-icons-outlined">task_alt</span>Onayını bekleyen talep kalmadı. 👍</div>'; } }
</script>
@endsection

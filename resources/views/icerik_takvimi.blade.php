@extends('layout.app')
@section('rk_ikon', 'calendar_month')
@section('baslik', 'Sosyal İçerik Takvimi')
@section('title', 'İçerik Takvimi')

@section('content')
<style>
  #ic{max-width:860px;margin:0 auto}
  #ic *{box-sizing:border-box}
  .ic-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .ic-top .b{font-size:15px;font-weight:800;color:var(--ink)} .ic-top .badge{font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;color:#fff}
  .ic-uret{margin-left:auto;display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-radius:10px;padding:9px 16px;font-weight:800;text-decoration:none;font-size:13px}
  .ic-list{display:flex;flex-direction:column;gap:11px}
  .ic-c{background:var(--card);border:1px solid var(--line);border-left:4px solid var(--mor1);border-radius:14px;padding:14px 16px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .ic-gun{font-size:13px;font-weight:800;color:var(--mor1);display:flex;align-items:center;gap:7px;margin-bottom:8px}
  .ic-row{display:flex;gap:9px;align-items:flex-start;font-size:12.5px;padding:5px 0}
  .ic-row .material-icons-outlined{font-size:16px;color:var(--sub2);flex:none;margin-top:1px}
  .ic-row b{color:var(--ink)} .ic-cap{color:var(--ink);line-height:1.5} .ic-hash{color:var(--mavi);font-weight:700}
  .ic-kop{margin-top:9px;background:var(--card2);border:1px solid var(--line);color:var(--sub);border-radius:8px;padding:6px 12px;font-size:11.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px}
  .ic-not{margin-top:14px;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .ic-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="ic">
  <div class="ic-top">
    <span class="b">Haftalık içerik planı</span>
    <span class="badge" style="background:{{ $kaynak==='ai'?'#7C3AED':'#64748B' }}">{{ $kaynak==='ai'?'✨ AI üretti':'örnek plan' }}</span>
    <a href="/icerik-takvimi?uret=1" class="ic-uret"><span class="material-icons-outlined" style="font-size:17px">auto_awesome</span> AI ile üret</a>
  </div>
  <div class="ic-list">
    @foreach($plan as $i => $p)
      <div class="ic-c" id="plan-{{ $i }}">
        <div class="ic-gun"><span class="material-icons-outlined" style="font-size:17px">event</span> {{ $p['gun'] ?? '—' }}</div>
        <div class="ic-row"><span class="material-icons-outlined">photo_camera</span><div><b>Çekim:</b> {{ $p['fikir'] ?? '' }}</div></div>
        <div class="ic-row"><span class="material-icons-outlined">edit_note</span><div class="ic-cap" data-cap><b>Caption:</b> {{ $p['caption'] ?? '' }}</div></div>
        <div class="ic-row"><span class="material-icons-outlined">tag</span><div class="ic-hash" data-hash>{{ $p['hashtag'] ?? '' }}</div></div>
        <button class="ic-kop" onclick="icKopya({{ $i }})"><span class="material-icons-outlined" style="font-size:14px">content_copy</span> Caption + hashtag kopyala</button>
      </div>
    @endforeach
  </div>
  <div class="ic-not"><span class="material-icons-outlined">info</span><span>AI içeriği <b>hazırlar</b>; sen fotoğrafı çekip metni kopyala-yapıştır yaparak Instagram/TikTok'ta paylaşırsın. Otomatik paylaşım için platform API'si gerekir (ileride). "AI ile üret" için AI bakiyesi (ANTHROPIC key) gerekir; yoksa örnek plan gösterilir.</span></div>
</div>
</div>
<script>
  function icKopya(i){
    var c=document.querySelector('#plan-'+i+' [data-cap]').textContent.replace(/^Caption:\s*/,'');
    var h=document.querySelector('#plan-'+i+' [data-hash]').textContent;
    navigator.clipboard.writeText(c+'\n\n'+h).then(function(){var b=event.target.closest('button');var o=b.innerHTML;b.innerHTML='<span class="material-icons-outlined" style="font-size:14px">check</span> Kopyalandı';setTimeout(function(){b.innerHTML=o;},1500);}).catch(function(){});
  }
</script>
@endsection

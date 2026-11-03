@extends('layout.app')
@section('rk_ikon', 'campaign')
@section('baslik', 'Kampanya Stüdyosu')
@section('title', 'Kampanya Stüdyosu')

@section('content')
<style>
  #ks{max-width:900px;margin:0 auto}
  #ks *{box-sizing:border-box}
  .ks-uyari{display:flex;gap:9px;align-items:flex-start;background:color-mix(in srgb,#F43F5E 10%,var(--card));border:1px solid color-mix(in srgb,#F43F5E 30%,var(--line));border-radius:13px;padding:12px 15px;margin-bottom:14px;font-size:12.5px;color:var(--ink)}
  .ks-uyari .material-icons-outlined{color:#F43F5E;flex:none}
  .ks-grid{display:grid;grid-template-columns:1.3fr 1fr;gap:14px;margin-bottom:14px}
  @media(max-width:760px){.ks-grid{grid-template-columns:1fr}}
  .ks-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;box-shadow:0 1px 3px rgba(20,20,50,.03)}
  .ks-card h4{font-size:13px;font-weight:800;color:var(--ink);margin:0 0 12px}
  .ks-f label{font-size:11.5px;color:var(--sub);font-weight:700;display:block;margin:10px 0 4px}
  .ks-f input,.ks-f textarea,.ks-f select{width:100%;border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:9px 11px;font-size:13px;font-family:inherit}
  .ks-f textarea{min-height:70px;resize:vertical}
  .ks-send{margin-top:14px;width:100%;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:11px;padding:12px;font-weight:800;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;gap:7px}
  .ks-prev{background:var(--card2);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  .ks-prev .ph{aspect-ratio:16/9;background:linear-gradient(135deg,#1e1b4b,#4F46E5);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.6)}
  .ks-prev .ph img{width:100%;height:100%;object-fit:cover}
  .ks-prev .pb{padding:12px 14px} .ks-prev .pt{font-weight:800;color:var(--ink);font-size:14px} .ks-prev .pm{font-size:12.5px;color:var(--sub);margin-top:4px;line-height:1.5}
  .ks-sec{font-size:14px;font-weight:800;color:var(--ink);margin:18px 2px 10px}
  .ks-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .ks-row{display:flex;align-items:center;gap:12px;padding:11px 16px;border-top:1px solid var(--line);font-size:13px}
  .ks-row:first-child{border-top:0} .ks-row .a{flex:1;min-width:0} .ks-row .a .t{font-weight:700;color:var(--ink)} .ks-row .a .d{font-size:11px;color:var(--sub)}
  .ks-row .seg{font-size:10px;font-weight:800;text-transform:uppercase;background:var(--card2);color:var(--sub);padding:2px 8px;border-radius:20px}
  .ks-row .g{font-weight:800;color:#2FBE94;font-variant-numeric:tabular-nums}
  .ks-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .ks-msg.ok{background:rgba(16,185,129,.12);color:#047857} .ks-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
</style>
<div class="rk-body">
<div id="ks">
  @if(session('ok'))<div class="ks-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="ks-msg er">{{ session('hata') }}</div>@endif

  <div class="ks-uyari"><span class="material-icons-outlined">verified_user</span><span><b>Güvenli gönderim:</b> Kampanya yalnızca <b>uygulamayı indiren (push açık)</b> müşterilere <b>resimli bildirim</b> olarak gider. Toplu WhatsApp YAPILMAZ (ban riski). Görsel vermek istersen bir <b>görsel linki (URL)</b> yapıştır — Instagram reklam görselini bir yere yükleyip linkini koyabilirsin.</span></div>

  <form method="POST" action="/kampanya/gonder" onsubmit="return confirm('Seçili segmentteki müşterilere bildirim gönderilsin mi?')">
    @csrf
    <div class="ks-grid">
      <div class="ks-card ks-f">
        <h4>Kampanya içeriği</h4>
        <label>Başlık</label><input name="baslik" id="f-bas" maxlength="60" placeholder="Hafta sonu %20 kahvaltı!" oninput="ksPrev()" required>
        <label>Mesaj</label><textarea name="metin" id="f-met" maxlength="180" placeholder="Cumartesi-Pazar serpme kahvaltıda %20 indirim. Seni bekliyoruz!" oninput="ksPrev()" required></textarea>
        <label>Görsel linki (URL) — opsiyonel</label><input name="gorsel" id="f-gor" placeholder="https://... (jpg/png)" oninput="ksPrev()">
        <label>Hedef segment</label>
        <select name="segment" id="f-seg">
          @foreach($segmentler as $s)<option value="{{ $s['key'] }}" {{ request('segment')==$s['key']?'selected':'' }}>{{ $s['ad'] }} ({{ $s['sayi'] }})</option>@endforeach
        </select>
        <button class="ks-send" type="submit"><span class="material-icons-outlined">send</span> Segmente Gönder</button>
      </div>
      <div class="ks-card">
        <h4>Önizleme (müşteri telefonu)</h4>
        <div class="ks-prev">
          <div class="ph" id="p-img"><span class="material-icons-outlined" style="font-size:34px">image</span></div>
          <div class="pb"><div class="pt" id="p-bas">Başlık buraya</div><div class="pm" id="p-met">Mesaj metni buraya gelecek…</div></div>
        </div>
      </div>
    </div>
  </form>

  @if($gecmis->count())
  <div class="ks-sec">Son gönderimler</div>
  <div class="ks-tab">
    @foreach($gecmis as $g)
      <div class="ks-row">
        <div class="a"><div class="t">{{ $g->baslik }}</div><div class="d">{{ \Carbon\Carbon::parse($g->created_at)->format('d.m.Y H:i') }}</div></div>
        <span class="seg">{{ $g->segment }}</span>
        <span class="g">{{ $g->gonderilen }}/{{ $g->hedef }} kişi</span>
      </div>
    @endforeach
  </div>
  @endif
</div>
</div>
<script>
  function ksPrev(){
    document.getElementById('p-bas').textContent=document.getElementById('f-bas').value||'Başlık buraya';
    document.getElementById('p-met').textContent=document.getElementById('f-met').value||'Mesaj metni buraya gelecek…';
    var u=document.getElementById('f-gor').value.trim(), ph=document.getElementById('p-img');
    if(u){ph.innerHTML='<img src="'+u.replace(/"/g,'')+'" onerror="this.parentNode.innerHTML=\'<span class=&quot;material-icons-outlined&quot;>broken_image</span>\'">';}
    else{ph.innerHTML='<span class="material-icons-outlined" style="font-size:34px">image</span>';}
  }
  ksPrev();
</script>
@endsection

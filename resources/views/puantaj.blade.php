@extends('layout.app')
@section('rk_ikon', 'schedule')
@section('baslik', 'Puantaj (Mesai)')
@section('title', 'Puantaj')

@push('head')
<style>
#puan-root{max-width:860px}
/* Gün seçici — Flutter: Container(color:t.card, chevron_left + tarih + chevron_right) */
#puan-gun-bar{display:flex;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:6px 4px;margin-bottom:18px;gap:0}
#puan-gun-bar button{background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:10px;color:var(--ink);display:flex;align-items:center;transition:background .12s}
#puan-gun-bar button:hover{background:var(--card2)}
#puan-gun-bar button:disabled{opacity:.3;cursor:default}
#puan-gun-label{flex:1;text-align:center;font-weight:700;font-size:15px;color:var(--ink)}
/* Boş durum */
#puan-empty{text-align:center;padding:44px 20px;color:var(--sub)}
/* Yükleniyor spinner */
#puan-spin{display:none;justify-content:center;padding:48px 0}
#puan-spin.goster{display:flex}
/* Kartlar listesi */
#puan-liste{display:flex;flex-direction:column;gap:12px}
/* Kayıt kartı — Flutter: Container(margin bottom:10, padding 14, borderRadius 14) */
.puan-kart{background:var(--card);border-radius:14px;padding:14px 16px;box-shadow:0 2px 8px rgba(0,0,0,.07)}
body.koyu .puan-kart{box-shadow:0 2px 10px rgba(0,0,0,.22)}
/* Üst satır: avatar + ad + rozet */
.puan-kart-ust{display:flex;align-items:center;gap:10px;margin-bottom:10px}
/* Avatar — Flutter: CircleAvatar radius:15, mor1 bg 15% */
.puan-avatar{width:30px;height:30px;border-radius:50%;flex:0 0 30px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 15%,transparent)}
/* Ad */
.puan-ad{flex:1;font-size:15px;font-weight:800;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* Rozet — Flutter _rozet */
.puan-rozet{display:inline-flex;align-items:center;padding:3px 9px;border-radius:9px;font-size:11px;font-weight:800;border:1px solid transparent;white-space:nowrap}
.puan-rozet.mesaide{color:#16A34A;background:rgba(22,163,74,.14);border-color:rgba(22,163,74,.45)}
.puan-rozet.eksik{color:#F59E0B;background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.45)}
/* 3 kutu: giriş / çıkış / süre — Flutter _kutu */
.puan-kutular{display:flex;gap:0;margin-bottom:8px}
.puan-kutu{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px}
.puan-kutu .material-icons-outlined{font-size:16px;color:var(--sub)}
.puan-kutu-deger{font-size:14px;font-weight:900;color:var(--ink);line-height:1.2}
.puan-kutu-etiket{font-size:10.5px;color:var(--sub)}
/* Ayırıcı çizgiler kutu arasında */
.puan-kutu+.puan-kutu{border-left:1px solid var(--line)}
/* Adım satırı — Flutter: walk icon + adim + hedef/yüzde */
.puan-adim{display:flex;align-items:center;gap:6px;font-size:13px}
.puan-adim .material-icons-outlined{font-size:16px;color:var(--mor1)}
.puan-adim-sayi{font-weight:700;color:var(--ink)}
.puan-adim-hedef{font-size:12px}
.puan-adim-hedef.iyi{color:#16A34A}
.puan-adim-hedef.orta{color:var(--sub2)}
.puan-adim-hedef.dusuk{color:#F59E0B}
</style>
@endpush

@section('content')
<div id="puan-root">

  {{-- GÜN SEÇİCİ — Flutter: chevron_left + tarih + chevron_right --}}
  <div id="puan-gun-bar">
    <button id="puan-prev" onclick="gunDegis(-1)" title="Önceki gün">
      <span class="material-icons-outlined">chevron_left</span>
    </button>
    <div id="puan-gun-label">{{ now()->format('Y-m-d') }}</div>
    <button id="puan-next" onclick="gunDegis(1)" title="Sonraki gün" disabled>
      <span class="material-icons-outlined">chevron_right</span>
    </button>
  </div>

  {{-- Spinner --}}
  <div id="puan-spin">
    <div style="width:32px;height:32px;border-radius:50%;border:3px solid var(--line);border-top-color:var(--mor1);animation:spin .7s linear infinite"></div>
  </div>

  {{-- Kartlar --}}
  <div id="puan-liste">
    <div id="puan-empty" style="display:none">Bu gün mesai kaydı yok.</div>
  </div>

</div>

<style>@keyframes spin{to{transform:rotate(360deg)}}</style>

<script>
(function(){
  // Bugün
  var bugun = new Date(); bugun.setHours(0,0,0,0);
  var gun = new Date(bugun);

  function gunStr(d){
    var y=d.getFullYear(), m=String(d.getMonth()+1).padLeft?String(d.getMonth()+1).padStart(2,'0'):('0'+(d.getMonth()+1)).slice(-2);
    var g=String(d.getDate()).padStart?String(d.getDate()).padStart(2,'0'):('0'+d.getDate()).slice(-2);
    return y+'-'+m+'-'+g;
  }

  function yukle(){
    var gs=gunStr(gun);
    document.getElementById('puan-gun-label').textContent=gs;
    // Sonraki buton: bugünden ileri gidemez
    document.getElementById('puan-next').disabled=(gun.getTime()>=bugun.getTime());
    document.getElementById('puan-spin').classList.add('goster');
    document.getElementById('puan-liste').innerHTML='';
    document.getElementById('puan-empty').style.display='none';

    api('/api/mesai/liste?gun='+gs).then(function(r){
      document.getElementById('puan-spin').classList.remove('goster');
      if(!r||r.ok!=1){goster([]);return;}
      goster(r.kayitlar||[]);
    }).catch(function(){
      document.getElementById('puan-spin').classList.remove('goster');
      goster([]);
    });
  }

  function sure(dk){
    dk=parseInt(dk)||0;
    var s=Math.floor(dk/60),d=dk%60;
    return s>0?(s+'s '+d+'dk'):(d+'dk');
  }

  function bas(ad){
    ad=ad||'?';
    // first non-whitespace character
    for(var i=0;i<ad.length;i++){if(ad[i].trim())return ad[i].toUpperCase();}
    return '?';
  }

  function goster(kayitlar){
    var liste=document.getElementById('puan-liste');
    var empty=document.getElementById('puan-empty');
    if(!kayitlar.length){empty.style.display='';liste.innerHTML='';return;}
    empty.style.display='none';
    var html='';
    for(var i=0;i<kayitlar.length;i++){
      var m=kayitlar[i];
      var acik=(m.durum==='acik');
      var tip=m.cikis_tip||'';
      var eksik=!acik&&(tip==='geofence'||tip==='kapanis');
      var dk=parseInt(m.dakika)||0;
      var adim=parseInt(m.adim)||0;
      // Flutter hedef: (dk/60)*1750
      var hedef=dk>0?Math.round((dk/60)*1750):0;
      var yuzde=hedef>0?Math.round(adim/hedef*100):0;
      var hedefSinif=yuzde>=90?'iyi':(yuzde>=50?'orta':'dusuk');
      var cikisHtml;
      if(acik){
        cikisHtml='<span style="color:#16A34A;font-weight:900">—</span>';
      } else {
        cikisHtml='<span>'+(m.cikis||'?')+'</span>';
      }
      // rozet
      var rozetHtml='';
      if(acik){
        rozetHtml='<span class="puan-rozet mesaide">Mesaide</span>';
      } else if(eksik){
        rozetHtml='<span class="puan-rozet eksik">Eksik çıkış</span>';
      }
      html+='<div class="puan-kart">';
      // üst satır
      html+='<div class="puan-kart-ust">';
      html+='<div class="puan-avatar">'+bas(m.ad)+'</div>';
      html+='<div class="puan-ad">'+(m.ad||'-')+'</div>';
      html+=rozetHtml;
      html+='</div>';
      // 3 kutu: Giriş / Çıkış / Süre
      html+='<div class="puan-kutular">';
      html+='<div class="puan-kutu"><span class="material-icons-outlined">login</span><div class="puan-kutu-deger">'+(m.giris||'-')+'</div><div class="puan-kutu-etiket">Giriş</div></div>';
      html+='<div class="puan-kutu"><span class="material-icons-outlined">logout</span><div class="puan-kutu-deger">'+cikisHtml+'</div><div class="puan-kutu-etiket">Çıkış</div></div>';
      html+='<div class="puan-kutu"><span class="material-icons-outlined">timer</span><div class="puan-kutu-deger">'+sure(dk)+'</div><div class="puan-kutu-etiket">Süre</div></div>';
      html+='</div>';
      // adım satırı
      html+='<div class="puan-adim">';
      html+='<span class="material-icons-outlined">directions_walk</span>';
      html+='<span class="puan-adim-sayi">'+adim.toLocaleString('tr-TR')+' adım</span>';
      if(hedef>0){
        html+='<span class="puan-adim-hedef '+hedefSinif+'">hedef ~'+hedef+' (%'+yuzde+')</span>';
      }
      html+='</div>';
      html+='</div>'; // puan-kart
    }
    liste.innerHTML=html;
  }

  window.gunDegis=function(delta){
    var yeni=new Date(gun.getTime()+delta*86400000);
    if(yeni.getTime()>bugun.getTime()) return;
    gun=yeni;
    yukle();
  };

  // İlk yükleme
  yukle();
})();
</script>
@endsection

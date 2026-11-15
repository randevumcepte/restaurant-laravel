{{-- Kamerayla barkod okuma (tarayıcı BarcodeDetector destekliyorsa: Android Chrome vb.). Okununca sayfadaki dmKameraSonuc(kod) çağrılır. --}}
<style>
#dmKam{position:fixed;inset:0;background:rgba(0,0,0,.86);z-index:10000;display:none;flex-direction:column;align-items:center;justify-content:center;gap:12px;padding:16px}
#dmKam.ac{display:flex}
#dmKam video{width:100%;max-width:520px;border-radius:16px;background:#000}
#dmKam button{border:0;border-radius:12px;padding:11px 22px;font-weight:800;cursor:pointer;background:#fff;color:#1e293b;font-family:inherit}
</style>
<div id="dmKam"><video id="dmVid" playsinline muted></video><div style="color:#fff;font-size:13px">Barkodu kameraya göster</div><button type="button" onclick="dmKamKapat()">Kapat</button></div>
<script>
(function(){ document.body.appendChild(document.getElementById('dmKam')); var b=document.getElementById('dmKamBtn'); if(b && 'BarcodeDetector' in window) b.style.display=''; })();
var _dmAkis=null, _dmDur=false, _dmSurekli=false;
function dmKamera(surekli){
  var v=document.getElementById('dmVid'); _dmDur=false; _dmSurekli=!!surekli; var son='', sonT=0;
  navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(st){
    _dmAkis=st; v.srcObject=st; v.play(); document.getElementById('dmKam').classList.add('ac');
    var det=new BarcodeDetector({formats:['code_128','ean_13','ean_8','code_39','qr_code','upc_a','itf','data_matrix']});
    (function tara(){ if(_dmDur) return; det.detect(v).then(function(r){
      if(r.length){ var k=r[0].rawValue, t=Date.now();
        if(_dmSurekli){ if(k!==son||t-sonT>2500){ son=k; sonT=t; dmKameraSonuc(k); } setTimeout(tara,350); }
        else { dmKamKapat(); dmKameraSonuc(k); } }
      else setTimeout(tara,250); }).catch(function(){ setTimeout(tara,400); }); })();
  }).catch(function(){ alert('Kamera açılamadı. Tarayıcıya kamera izni ver.'); });
}
function dmKamKapat(){ _dmDur=true; if(_dmAkis) _dmAkis.getTracks().forEach(function(t){ t.stop(); }); document.getElementById('dmKam').classList.remove('ac'); }
document.addEventListener('keydown',function(e){ if(e.key==='Escape') dmKamKapat(); });
</script>

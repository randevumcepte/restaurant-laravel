{{-- Etiket yazdırma popup'ı: basım/önizleme sayfadan ayrılmadan iframe içinde açılır, yazdırma penceresi oradan çağrılır --}}
<style>
#emYaz{position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;padding:16px}
#emYaz.acik{display:flex}
#emYaz .ey-kutu{background:#fff;border-radius:18px;box-shadow:0 24px 60px rgba(15,23,42,.25);width:min(920px,100%);height:min(640px,calc(100vh - 32px));display:flex;flex-direction:column;overflow:hidden}
#emYaz .ey-ust{display:flex;align-items:center;gap:8px;padding:12px 14px;border-bottom:1px solid #E2E8F0;background:linear-gradient(135deg,#EEF2FF,#F5F3FF)}
#emYaz .ey-ust b{flex:1;min-width:0;font-size:14px;color:#1E293B;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#emYaz .ey-ust .ms{font-size:20px}
#emYaz .ey-ust button{display:inline-flex;align-items:center;gap:6px;border:1px solid #E2E8F0;background:#fff;color:#1E293B;border-radius:10px;padding:8px 12px;font-weight:700;font-size:13px;cursor:pointer;font-family:inherit}
#emYaz .ey-ust button.ana{background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;border:0}
#emYaz .ey-durum{padding:7px 14px;font-size:12px;color:#047857;background:#ECFDF5;border-bottom:1px solid #D1FAE5;display:none}
#emYaz iframe{flex:1;width:100%;border:0;background:#eef0f6}
body.koyu #emYaz .ey-kutu{background:var(--card)}
body.koyu #emYaz .ey-ust{background:var(--card2);border-color:var(--line)}
body.koyu #emYaz .ey-ust b{color:var(--ink)}
</style>
<div id="emYaz" onclick="if(event.target===this)emYazKapat()" role="dialog" aria-modal="true" aria-labelledby="emYazBaslik">
  <div class="ey-kutu">
    <div class="ey-ust">
      <span class="ms" style="color:#6366F1">print</span><b id="emYazBaslik">Etiket</b>
      <button type="button" class="ana" onclick="emYazBas()"><span class="ms">print</span> Yazdır</button>
      <button type="button" onclick="emYazKapat()"><span class="ms">close</span> Kapat</button>
    </div>
    <div class="ey-durum" id="emYazDurum"></div>
    <iframe id="emYazCerceve" title="Etiket önizleme"></iframe>
  </div>
</div>
<script>
(function(){
  var kutu=document.getElementById('emYaz'), cer=document.getElementById('emYazCerceve'), yenile=false, otoBas=false;
  document.body.appendChild(kutu);
  cer.addEventListener('load', function(){
    if(!kutu.classList.contains('acik')) return;
    try{ if(cer.contentWindow.location.href==='about:blank') return; document.getElementById('emYazBaslik').textContent=cer.contentDocument.title.replace(/ · Yazdır$/,''); }catch(e){}
    if(otoBas){ otoBas=false; setTimeout(emYazBas,250); }
  });
  window.emYazAc=function(url, bas, yeni){
    document.getElementById('emYazDurum').style.display='none';
    otoBas=!!bas; if(yeni) yenile=true;
    document.getElementById('emYazBaslik').textContent='Etiket hazırlanıyor…';
    cer.src=url+(url.indexOf('?')<0?'?':'&')+'gomulu=1';
    kutu.classList.add('acik');
  };
  window.emYazBas=function(){
    try{ cer.contentWindow.focus(); cer.contentWindow.print(); }catch(e){ return; }
    var d=document.getElementById('emYazDurum'); d.textContent='✓ Yazdırma penceresi açıldı. Tekrar basmak için "Yazdır"a, bitirdiysen "Kapat"a bas.'; d.style.display='block';
  };
  window.emYazKapat=function(){
    kutu.classList.remove('acik'); cer.src='about:blank';
    if(yenile){ yenile=false; location.reload(); }
  };
  document.addEventListener('keydown', function(e){ if(e.key==='Escape' && kutu.classList.contains('acik')) emYazKapat(); });
  // Basım formları: sayfadan ayrılmadan kaydet + popup'ta yazdır (ZPL/TSPL dosyası düğmesi normal indirme olarak kalır)
  document.addEventListener('submit', function(e){
    var f=e.target, a=f.getAttribute('action')||'';
    if(e.defaultPrevented || !/^\/barkod-etiket\/(bas|raf-yazdir|serbest)$/.test(a)) return;
    if(e.submitter && e.submitter.name==='format') return;
    e.preventDefault();
    if(f.method.toLowerCase()==='get'){ emYazAc(a+'?'+new URLSearchParams(new FormData(f)).toString(), true, a==='/barkod-etiket/raf-yazdir'); return; }
    var dg=e.submitter; if(dg) dg.disabled=true;
    fetch(a,{method:'POST',body:new FormData(f),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){ return r.json(); })
      .then(function(j){ if(!j.ok){ alert(j.msg||'Etiket basılamadı.'); return; } emYazAc(j.url, true, true); })
      .catch(function(){ alert('Bağlantı hatası, tekrar dene.'); })
      .then(function(){ if(dg) dg.disabled=false; });
  });
  // Tekrar bas / önizle / raf kartı bas linkleri de popup'ta açılır
  document.addEventListener('click', function(e){
    var l=e.target.closest && e.target.closest('a[href^="/barkod-etiket/yazdir/"],a[href^="/barkod-etiket/raf-yazdir"]');
    if(!l || e.ctrlKey || e.metaKey || e.shiftKey || /format=/.test(l.getAttribute('href'))) return;
    e.preventDefault();
    var h=l.getAttribute('href'), onizle=/[?&]kal=1/.test(h);
    emYazAc(h, !onizle, !onizle && h.indexOf('/raf-yazdir')===0);
  });
})();
</script>

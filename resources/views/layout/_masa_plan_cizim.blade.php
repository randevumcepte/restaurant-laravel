{{-- İşletme Düzeni + Garson Yönetimi ortak masa çizimi ve üst arama --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs>
  <linearGradient id="idzAhsap" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#E2AC73"/><stop offset="1" stop-color="#B9773F"/></linearGradient>
  <linearGradient id="idzMorG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#A06BF6"/><stop offset="1" stop-color="#6D28D9"/></linearGradient>
  <linearGradient id="idzTabla" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FCE7D6"/><stop offset="1" stop-color="#F3CFB2"/></linearGradient>
  <linearGradient id="idzYaprak" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6FA845"/><stop offset="1" stop-color="#2F6B2C"/></linearGradient>
  <linearGradient id="idzDesk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#B98A5F"/><stop offset="1" stop-color="#7C5536"/></linearGradient>
</defs></svg>
<script>
// Kayıtlı konumu olmayan masalar sıraya dizilir; alt ortadaki resepsiyon alanı boş bırakılır
function idzYerlesim(list){
  var n = list.length, C = Math.max(2, Math.ceil(Math.sqrt(n * 1.8)));
  var ayrilan = C % 2 ? 1 : 2, R = Math.max(1, Math.ceil((n + ayrilan) / C));
  var ust = C * (R - 1), kalan = Math.max(0, n - ust), xs = [];
  var solS = Math.ceil(kalan / 2), sagS = kalan - solS, i;
  for(i = 0; i < solS; i++) xs.push(60 + (i + .5) * 320 / solS);
  for(i = 0; i < sagS; i++) xs.push(620 + (i + .5) * 320 / sagS);
  var rowH = 920 / R, colW = 880 / C, out = {};
  list.forEach(function(m, k){
    if(k < ust) out[m.id] = {x: 60 + ((k % C) + .5) * colW, y: 40 + (Math.floor(k / C) + .5) * rowH};
    else out[m.id] = {x: xs[k - ust], y: 40 + (R - .5) * rowH};
  });
  return {poz: out, C: C, R: R};
}
function idzSandalye(x, y, rot, dolgu, koyu){
  return '<g transform="translate('+x+' '+y+') rotate('+rot+')">'+
    '<rect x="-17" y="-15" width="34" height="30" rx="9" fill="'+dolgu+'"/><rect x="-12" y="-10" width="24" height="13" rx="6" fill="#fff" opacity=".16"/>'+
    '<path d="M-20 -7Q-20 -24 0 -24Q20 -24 20 -7L14 -7Q14 -16 0 -16Q-14 -16 -14 -7Z" fill="'+koyu+'"/></g>';
}
// Kaydında seçilmiş şekil öncelikli; yoksa kapasiteye göre
function idzSekil(m, p){
  if(p && p.sekil) return p.sekil;
  if(m.sekil === 'yuvarlak' || m.kapasite <= 2) return 'yuvarlak';
  return m.kapasite >= 5 ? 'uzun' : 'kare';
}
function idzMasaSvg(m, sekil, rot){
  var dolu = m.durum === 'dolu' || m.durum === 'birlesik';
  var d = dolu ? 'url(#idzMorG)' : 'url(#idzAhsap)', k = dolu ? '#5B21B6' : '#8A5429', s = '', kap = m.kapasite, i;
  var ring = dolu ? '#E6D6FB' : '#E3B98E', st = rot ? ' style="transform:rotate('+rot+'deg)"' : '';
  sekil = sekil || idzSekil(m);
  if(sekil === 'yuvarlak'){
    var N = Math.max(2, Math.min(8, kap)), ofs = N === 2 ? 0 : 45;
    for(i = 0; i < N; i++){ var a = (ofs + i * 360 / N) * Math.PI / 180; s += idzSandalye((100 + Math.sin(a) * 74).toFixed(1), (100 - Math.cos(a) * 74).toFixed(1), (ofs + i * 360 / N).toFixed(0), d, k); }
    s += '<circle cx="100" cy="100" r="60" fill="'+ring+'"/><circle cx="100" cy="100" r="53" fill="#FFF9F0"/>';
    return {svg: '<svg viewBox="0 0 200 200"'+st+'>'+s+'</svg>', oran: 1, yuvarlak: true};
  }
  if(sekil === 'uzun'){
    var yan = Math.min(4, Math.max(1, Math.floor((kap - 2) / 2)));
    for(i = 0; i < yan; i++){ var x = 58 + (i + .5) * 184 / yan; s += idzSandalye(x, 30, 0, d, k) + idzSandalye(x, 170, 180, d, k); }
    s += idzSandalye(30, 100, 270, d, k) + idzSandalye(270, 100, 90, d, k);
    s += '<rect x="46" y="48" width="208" height="104" rx="9" fill="#D7A77B"/><rect x="51" y="53" width="198" height="94" rx="6" fill="url(#idzTabla)"/>';
    return {svg: '<svg viewBox="0 0 300 200"'+st+'>'+s+'</svg>', oran: 1.5};
  }
  s += idzSandalye(30, 100, 270, d, k) + idzSandalye(170, 100, 90, d, k);
  if(kap > 2) s += idzSandalye(100, 30, 0, d, k) + idzSandalye(100, 170, 180, d, k);
  s += '<rect x="46" y="46" width="108" height="108" rx="9" fill="#D7A77B"/><rect x="51" y="51" width="98" height="98" rx="6" fill="url(#idzTabla)"/>';
  return {svg: '<svg viewBox="0 0 200 200"'+st+'>'+s+'</svg>', oran: 1};
}
function idzBitkiSvg(adet){
  var b = '';
  [[adet, 49, 15, 'url(#idzYaprak)', 0], [adet - 3, 37, 13, '#4F8F38', 14], [adet - 6, 25, 10, '#86BC58', 7]].forEach(function(h){
    for(var i = 0; i < h[0]; i++){
      var a = i * 360 / h[0] + h[4], l = h[1] - (i % 2 ? 5 : 0);
      b += '<path transform="rotate('+a+' 50 50)" d="M50 50Q'+(50 - h[2])+' '+(50 - l * .6)+' 50 '+(50 - l)+'Q'+(50 + h[2])+' '+(50 - l * .6)+' 50 50Z" fill="'+h[3]+'"/>';
    }
  });
  return '<svg viewBox="0 0 100 100"><circle cx="50" cy="50" r="19" fill="#C9A684"/><circle cx="50" cy="50" r="14" fill="#6B4A2E"/>'+b+'</svg>';
}
function idzEsc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
// tip: [genişlik %, simge, ad, etiket yazılır mı, renk]
var IDZ_OBJE = {
  kasa: [20, 'point_of_sale', 'Resepsiyon', true, '#7C5536'], bar: [24, 'local_bar', 'Bar', true, '#7C3AED'],
  giris: [8.5, 'login', 'Giriş', true, '#14B8A6'], mutfak: [10, 'restaurant', 'Mutfak', true, '#F97316'], wc: [7, 'wc', 'WC', true, '#3B82F6'],
  otopark: [9, 'local_parking', 'Otopark', true, '#6366F1'], depo: [8, 'inventory_2', 'Depo', true, '#92694A'], diger: [9, 'place', 'Etiket', true, '#64748B'],
  bitki: [5.4, '', 'Bitki', false], duvar: [22, '', 'Duvar', false], isik: [2, '', 'Işık', false]
};
function idzObjeHtml(o, i, sec){
  var d = IDZ_OBJE[o.t] || IDZ_OBJE.diger, boy = o.boy || 1, g = d[0] * boy, ic = '', sinif = 'idz-obje o-' + o.t + (sec ? ' sec' : '');
  if(o.t === 'bitki') ic = idzBitkiSvg(18);
  else if(o.t === 'isik') ic = '<i class="idz-isik"></i>';
  else if(o.t === 'duvar') ic = '<span class="duvar"></span>';
  else if(o.t === 'kasa') ic = '<svg viewBox="0 0 220 110"><path d="M0 110Q14 66 62 66L158 66Q206 66 220 110Z" fill="#F4E7D6" opacity=".8"/><rect x="22" y="30" width="176" height="66" rx="9" fill="url(#idzDesk)"/><rect x="16" y="22" width="188" height="20" rx="7" fill="#D2A77C"/><rect x="86" y="2" width="48" height="26" rx="3" fill="#2B2B33"/><rect x="104" y="28" width="12" height="6" fill="#55555f"/></svg><span class="yazi">'+idzEsc(o.ad || 'ResteOS')+'</span>';
  else if(o.t === 'bar') ic = '<svg viewBox="0 0 260 90"><rect x="4" y="10" width="252" height="52" rx="12" fill="url(#idzDesk)"/><rect x="4" y="10" width="252" height="16" rx="8" fill="#D2A77C"/>'+[34,82,130,178,226].map(function(x){ return '<circle cx="'+x+'" cy="76" r="11" fill="url(#idzMorG)"/>'; }).join('')+'</svg><span class="yazi">'+idzEsc(o.ad || 'Bar')+'</span>';
  else ic = '<span class="kutu" style="--r:'+d[4]+'"><span class="material-icons-outlined">'+d[1]+'</span>'+idzEsc(o.ad || d[2])+'</span>';
  return '<div class="'+sinif+'" data-obje="'+i+'" style="left:'+(o.x / 10)+'%;top:'+(o.y / 10)+'%;width:'+g+'%;transform:translate(-50%,-50%) rotate('+(o.rot || 0)+'deg)">'+ic+'</div>';
}
function idzObjelerHtml(l, sec){ return l.map(function(o, i){ return idzObjeHtml(o, i, i === sec); }).join(''); }
// Henüz düzenlenmemiş alanın süsü; resepsiyon yalnız ilk alanda ve şemada yoksa
function idzVarsayilanDekor(ilk, kasaVar){
  var l = [];
  [[32,62,1],[968,62,1],[30,470,.95],[970,470,.95],[88,880,1],[968,900,1.05],[380,935,.75],[625,935,.75],[232,48,.65],[772,48,.65]].forEach(function(p){ l.push({t:'bitki', x:p[0], y:p[1], boy:p[2], rot:0}); });
  [[22,290],[22,700],[978,290],[978,700],[345,18],[655,18],[325,985],[675,985]].forEach(function(p){ l.push({t:'isik', x:p[0], y:p[1], boy:1, rot:0}); });
  if(ilk && !kasaVar) l.push({t:'kasa', ad:'ResteOS', x:500, y:878, boy:1, rot:0});
  return l;
}
function idzDekorListe(plan, bid, ilkBid){
  var a = plan && plan._alan && plan._alan[bid];
  if(a && a.kayitli) return a.dekor || [];
  var sema = (a && a.dekor) || [], kasa = sema.some(function(o){ return o.t === 'kasa'; });
  return idzVarsayilanDekor(bid === ilkBid, kasa).concat(sema);
}
// Hazır dizilimler: masa id => {x,y}
function idzSablon(tip, list, oran){
  oran = oran || 2.35;
  var n = list.length, out = {};
  function ortaIzgara(ml, x0, x1, y0, y1){
    var C = Math.max(1, Math.min(ml.length, Math.ceil(Math.sqrt(ml.length * oran * (x1 - x0) / Math.max(1, y1 - y0) * .6)))), R = Math.ceil(ml.length / C);
    ml.forEach(function(m, i){
      var sat = Math.floor(i / C), sut = i % C, say = sat === R - 1 ? ml.length - C * (R - 1) : C;
      out[m.id] = {x: x0 + (sut + .5) * (x1 - x0) / say, y: y0 + (sat + .5) * (y1 - y0) / R};
    });
  }
  function kenar(ml, ic){
    var W = 1000 - 2 * ic, H = 1000 - 2 * ic - 40, cevre = 2 * (W + H);
    ml.forEach(function(m, i){
      var d = (i + .5) * cevre / ml.length, x, y;
      if(d < W){ x = ic + d; y = ic; }
      else if(d < W + H){ x = ic + W; y = ic + (d - W); }
      else if(d < 2 * W + H){ x = ic + W - (d - W - H); y = ic + H; }
      else { x = ic; y = ic + H - (d - 2 * W - H); }
      out[m.id] = {x: x, y: y};
    });
  }
  if(tip === 'izgara' || tip === 'banket') ortaIzgara(list, 60, 940, 60, 940);
  else if(tip === 'kenar') kenar(list, 130);
  else if(tip === 'ada'){ var dis = Math.ceil(n * .6); kenar(list.slice(0, dis), 110); ortaIzgara(list.slice(dis), 260, 740, 270, 730); }
  else return idzYerlesim(list).poz;
  return out;
}
// Boyut: dünya genişliği, masa ölçüsü ve çerçeve yüksekliği
function idzDunyaOlcu(cer, dun, yer, olcek, oran){
  oran = oran || 2.35;
  var W = Math.max(cer.clientWidth, 720) * olcek, H = Math.max(W / oran, yer.R * 118 * olcek);
  var ts = Math.max(66 * olcek, Math.min(W * .88 / yer.C * .66, H * .92 / yer.R * .9));
  var kenar = cer.offsetHeight - cer.clientHeight;
  // Kaydırma çubuğu çıkmasın: çerçeve kenarı da yüksekliğe eklenir
  cer.style.height = Math.max(Math.min(H, Math.max(cer.clientWidth / oran, yer.R * 118)), 360) + (olcek === 1 ? Math.max(0, kenar) : 0) + 'px';
  dun.style.width = W + 'px'; dun.style.height = H + 'px';
  dun.style.setProperty('--ts', ts + 'px');
}
// Üst arama kutusu. kaynak(esle) -> [[ikon, ad, alt, tiklaFn], ...]
function idzAramaKur(kaynak){
  var inp = document.getElementById('idzGenelAra'), kutu = document.getElementById('idzAraSonuc'), hedef = [];
  if(!inp) return;
  function kac(s){ return String(s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  inp.addEventListener('input', function(){
    var q = inp.value.trim().toLocaleLowerCase('tr');
    hedef = q ? kaynak(function(ad){ return String(ad).toLocaleLowerCase('tr').indexOf(q) >= 0; }) : [];
    kutu.innerHTML = hedef.length ? hedef.map(function(h, i){ return '<button type="button" data-i="'+i+'"><span class="material-icons-outlined">'+h[0]+'</span><b>'+kac(h[1])+'</b><small>'+kac(h[2])+'</small></button>'; }).join('') : (q ? '<div class="yok">Sonuç bulunamadı</div>' : '');
    kutu.classList.toggle('acik', !!q);
  });
  kutu.addEventListener('click', function(e){ var b = e.target.closest('button'); if(!b) return; hedef[+b.getAttribute('data-i')][3](); kutu.classList.remove('acik'); inp.value = ''; });
  document.addEventListener('pointerdown', function(e){ if(!e.target.closest('.idz-ust-ara')) kutu.classList.remove('acik'); });
}
setInterval(function(){ var s = document.getElementById('idzSaat'), d = new Date(); if(s) s.textContent = String(d.getHours()).padStart(2,'0') + ':' + String(d.getMinutes()).padStart(2,'0'); }, 20000);
</script>

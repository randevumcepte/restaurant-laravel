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
function idzMasaSvg(m){
  var dolu = m.durum === 'dolu' || m.durum === 'birlesik';
  var d = dolu ? 'url(#idzMorG)' : 'url(#idzAhsap)', k = dolu ? '#5B21B6' : '#8A5429', s = '', kap = m.kapasite, i;
  var ring = dolu ? '#E6D6FB' : '#E3B98E';
  if(m.sekil === 'yuvarlak' || kap <= 2){
    var N = Math.max(2, Math.min(8, kap)), ofs = N === 2 ? 0 : 45;
    for(i = 0; i < N; i++){ var a = (ofs + i * 360 / N) * Math.PI / 180; s += idzSandalye((100 + Math.sin(a) * 74).toFixed(1), (100 - Math.cos(a) * 74).toFixed(1), (ofs + i * 360 / N).toFixed(0), d, k); }
    s += '<circle cx="100" cy="100" r="60" fill="'+ring+'"/><circle cx="100" cy="100" r="53" fill="#FFF9F0"/>';
    return {svg: '<svg viewBox="0 0 200 200">'+s+'</svg>', oran: 1, yuvarlak: true};
  }
  if(kap >= 5){
    var yan = Math.min(4, Math.max(2, Math.floor((kap - 2) / 2)));
    for(i = 0; i < yan; i++){ var x = 58 + (i + .5) * 184 / yan; s += idzSandalye(x, 30, 0, d, k) + idzSandalye(x, 170, 180, d, k); }
    s += idzSandalye(30, 100, 270, d, k) + idzSandalye(270, 100, 90, d, k);
    s += '<rect x="46" y="48" width="208" height="104" rx="9" fill="#D7A77B"/><rect x="51" y="53" width="198" height="94" rx="6" fill="url(#idzTabla)"/>';
    return {svg: '<svg viewBox="0 0 300 200">'+s+'</svg>', oran: 1.5};
  }
  s += idzSandalye(30, 100, 270, d, k) + idzSandalye(170, 100, 90, d, k);
  if(kap > 2) s += idzSandalye(100, 30, 0, d, k) + idzSandalye(100, 170, 180, d, k);
  s += '<rect x="46" y="46" width="108" height="108" rx="9" fill="#D7A77B"/><rect x="51" y="51" width="98" height="98" rx="6" fill="url(#idzTabla)"/>';
  return {svg: '<svg viewBox="0 0 200 200">'+s+'</svg>', oran: 1};
}
function idzBitki(x, y, boy, adet){
  var b = '';
  [[adet, 49, 15, 'url(#idzYaprak)', 0], [adet - 3, 37, 13, '#4F8F38', 14], [adet - 6, 25, 10, '#86BC58', 7]].forEach(function(h){
    for(var i = 0; i < h[0]; i++){
      var a = i * 360 / h[0] + h[4], l = h[1] - (i % 2 ? 5 : 0);
      b += '<path transform="rotate('+a+' 50 50)" d="M50 50Q'+(50 - h[2])+' '+(50 - l * .6)+' 50 '+(50 - l)+'Q'+(50 + h[2])+' '+(50 - l * .6)+' 50 50Z" fill="'+h[3]+'"/>';
    }
  });
  return '<svg class="idz-bitki" viewBox="0 0 100 100" style="left:'+x+'%;top:'+y+'%;width:'+boy+'%"><circle cx="50" cy="50" r="19" fill="#C9A684"/><circle cx="50" cy="50" r="14" fill="#6B4A2E"/>'+b+'</svg>';
}
var idzDekor = idzBitki(.6,1,5.4,18) + idzBitki(94,1,5.4,18) + idzBitki(.6,44,5,18) + idzBitki(94.4,44,5,18) + idzBitki(5.6,83,5.6,18) + idzBitki(93.8,84,5.8,18) + idzBitki(36,88,4,14) + idzBitki(60,88,4,14) + idzBitki(21,.5,3.4,12) + idzBitki(75.6,.5,3.4,12)
  + '<i class="idz-isik" style="left:2%;top:26%"></i><i class="idz-isik" style="left:2%;top:68%"></i><i class="idz-isik" style="right:2%;top:26%"></i><i class="idz-isik" style="right:2%;top:68%"></i><i class="idz-isik" style="left:34%;top:1.5%"></i><i class="idz-isik" style="right:34%;top:1.5%"></i><i class="idz-isik" style="left:32%;bottom:2%"></i><i class="idz-isik" style="right:32%;bottom:2%"></i>'
  + '<div class="idz-resepsiyon"><svg viewBox="0 0 220 110"><path d="M0 110Q14 66 62 66L158 66Q206 66 220 110Z" fill="#F4E7D6" opacity=".8"/><rect x="22" y="30" width="176" height="66" rx="9" fill="url(#idzDesk)"/><rect x="16" y="22" width="188" height="20" rx="7" fill="#D2A77C"/><rect x="86" y="2" width="48" height="26" rx="3" fill="#2B2B33"/><rect x="104" y="28" width="12" height="6" fill="#55555f"/></svg><span>ResteOS</span></div>';
// Boyut: dünya genişliği, masa ölçüsü ve çerçeve yüksekliği
function idzDunyaOlcu(cer, dun, yer, olcek){
  var W = Math.max(cer.clientWidth, 720) * olcek, H = Math.max(W / 2.35, yer.R * 118 * olcek);
  var ts = Math.max(66 * olcek, Math.min(W * .88 / yer.C * .66, H * .92 / yer.R * .9));
  cer.style.height = Math.max(Math.min(H, Math.max(cer.clientWidth / 2.35, yer.R * 118)), 360) + 'px';
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

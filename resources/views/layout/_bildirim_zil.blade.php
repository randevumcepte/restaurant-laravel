{{-- Bildirim zili: okunmamış sayısı + açılır liste (bildirim merkezi). 60 sn'de bir yenilenir. --}}
<style>
  .bz{position:relative;display:inline-flex}
  .bz-btn{position:relative;background:none;border:0;cursor:pointer;color:var(--sub2,#64748b);padding:6px;border-radius:10px;display:inline-flex;transition:background .15s,color .15s}
  .bz-btn:hover,.bz.acik .bz-btn{background:color-mix(in srgb,#7C3AED 10%,transparent);color:#7C3AED}
  .bz-btn .material-icons-outlined{font-size:23px}
  .bz-say{position:absolute;top:1px;right:0;min-width:17px;height:17px;padding:0 4px;border-radius:999px;background:#F43F5E;color:#fff;font-size:10px;font-weight:800;display:none;align-items:center;justify-content:center;border:2px solid var(--card,#fff);line-height:1}
  .bz-say.var{display:inline-flex}
  .bz.zil .bz-btn .material-icons-outlined{animation:bzSalla .9s ease}
  @keyframes bzSalla{0%,100%{transform:rotate(0)}20%{transform:rotate(16deg)}40%{transform:rotate(-14deg)}60%{transform:rotate(9deg)}80%{transform:rotate(-5deg)}}
  .bz-pano{position:absolute;right:0;top:calc(100% + 10px);width:380px;max-height:min(560px,calc(100vh - 110px));display:none;flex-direction:column;background:var(--card,#fff);border:1px solid var(--line,#e2e8f0);border-radius:18px;box-shadow:0 24px 60px -18px rgba(15,23,42,.45);z-index:900;overflow:hidden}
  .bz.acik .bz-pano{display:flex;animation:bzAc .18s ease}
  @keyframes bzAc{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}
  .bz-ust{display:flex;align-items:center;gap:8px;padding:13px 14px 11px 16px;border-bottom:1px solid var(--line,#e2e8f0)}
  .bz-ust b{flex:1;font-size:14.5px;color:var(--ink,#0f172a)}
  .bz-ust button,.bz-ust a{border:0;background:none;color:#7C3AED;font-size:12px;font-weight:700;cursor:pointer;padding:5px 7px;border-radius:8px;text-decoration:none;font-family:inherit;display:inline-flex;align-items:center;gap:3px}
  .bz-ust button:hover,.bz-ust a:hover{background:color-mix(in srgb,#7C3AED 9%,transparent)}
  .bz-ust .material-icons-outlined{font-size:17px}
  .bz-sekme{display:flex;gap:6px;padding:9px 14px 4px}
  .bz-sekme button{border:1px solid var(--line,#e2e8f0);background:none;border-radius:999px;padding:5px 12px;font-size:12px;font-weight:700;color:var(--sub,#64748b);cursor:pointer;font-family:inherit}
  .bz-sekme button.aktif{background:#7C3AED;border-color:#7C3AED;color:#fff}
  .bz-liste{overflow:auto;padding:6px 8px 10px;flex:1}
  .bz-oge{display:flex;gap:11px;padding:10px 10px;border-radius:13px;cursor:pointer;position:relative;text-decoration:none;color:inherit}
  .bz-oge:hover{background:var(--card2,#f8fafc)}
  .bz-oge.yeni{background:color-mix(in srgb,#7C3AED 6%,transparent)}
  .bz-oge.yeni::after{content:'';position:absolute;right:10px;top:15px;width:8px;height:8px;border-radius:50%;background:#7C3AED}
  .bz-ik{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;flex:none;background:color-mix(in srgb,var(--bzr) 14%,transparent);color:var(--bzr)}
  .bz-ik .material-icons-outlined{font-size:19px}
  .bz-metin{flex:1;min-width:0;padding-right:12px}
  .bz-b{font-size:13px;font-weight:700;color:var(--ink,#0f172a);line-height:1.3}
  .bz-g{font-size:12px;color:var(--sub,#64748b);margin-top:2px;line-height:1.35;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
  .bz-z{font-size:11px;color:var(--sub2,#94a3b8);margin-top:3px}
  .bz-bos{text-align:center;padding:36px 16px;color:var(--sub,#64748b);font-size:13px}
  .bz-bos .material-icons-outlined{display:block;font-size:40px;color:#10B981;margin-bottom:8px}
  @media (max-width:560px){
    .bz-pano{position:fixed;left:10px;right:10px;top:64px;width:auto;max-height:calc(100vh - 80px)}
  }
</style>
<div class="bz" id="bz">
  <button type="button" class="bz-btn" onclick="bzAcKapa(event)" title="Bildirimler" aria-label="Bildirimler"><span class="material-icons-outlined">notifications</span><span class="bz-say" id="bzSay"></span></button>
  <div class="bz-pano" id="bzPano" onclick="event.stopPropagation()">
    <div class="bz-ust"><b>Bildirimler</b><button type="button" onclick="bzHepsiOkundu()"><span class="material-icons-outlined">done_all</span>Tümü okundu</button><a href="/bildirim-ayar" title="Bildirim ayarları"><span class="material-icons-outlined">tune</span></a></div>
    <div class="bz-sekme"><button type="button" class="aktif" data-f="hepsi" onclick="bzFiltre(this)">Tümü</button><button type="button" data-f="yeni" onclick="bzFiltre(this)">Okunmamış</button></div>
    <div class="bz-liste" id="bzListe"><div class="bz-bos">Yükleniyor…</div></div>
  </div>
</div>
<script>
(function(){
  var veri = [], filtre = 'hepsi', sonSay = null;
  var renk = {2:'#F43F5E', 1:'#F59E0B', 0:'#7C3AED'};
  var ikon = {kds_gecikme:'timer', kds_bekleyen:'soup_kitchen', stok_kritik:'inventory_2', uretim_riski:'warning_amber', skt_gecti:'event_busy', skt_yaklasan:'event', kuver:'restaurant', rez_grup:'groups', urun_tukendi:'block', iptal_iade:'undo',
    mal_geldi:'move_to_inbox', sicaklik_limit:'device_thermostat', sicaklik_olcum:'thermostat', garson_cagri:'room_service', cagri_yanitsiz:'notifications_active', masa_uzun:'schedule', iskonto_ikram:'redeem', memnuniyet_dusuk:'sentiment_dissatisfied',
    yeni_rezervasyon:'event_available', rez_gelmedi:'hourglass_bottom', bekleme_uzun:'hourglass_top', vip_geliyor:'star', yeni_paket:'delivery_dining', paket_bekliyor:'takeout_dining', kurye_gecikti:'two_wheeler', tahsil_bekliyor:'payments',
    kasa_acik_kaldi:'point_of_sale', kasa_fark:'balance', talep_yeni:'assignment', ekip_talep_onay:'fact_check', talep_sonuc:'assignment_turned_in', teklif_geldi:'request_quote', siparis_gecikti:'local_shipping', teslimat_bugun:'local_shipping',
    butce:'savings', fiyat_zam:'trending_up', ekip_gelmedi:'person_off', ekip_gec_kaldi:'alarm', ekip_erken_cikti:'logout', ekip_izin_talebi:'beach_access', ekip_avans_talebi:'payments', yarin_ekip_eksik:'event_note', ekip_belge_doluyor:'badge',
    gorev_atandi:'push_pin', gorev_gecikti:'assignment_late', ariza_acik:'build', cihaz_offline:'wifi_off', mali_fis_hata:'receipt_long', ajanda:'event_note', happy_hour:'local_bar'};
  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function ciz(){
    var l = document.getElementById('bzListe'); if (!l) return;
    var g = veri.filter(function(b){ return filtre === 'hepsi' || !b.okundu; });
    if (!g.length) { l.innerHTML = '<div class="bz-bos"><span class="material-icons-outlined">task_alt</span>' + (filtre === 'yeni' ? 'Okunmamış bildirim yok.' : 'Henüz bildirim yok.') + '</div>'; return; }
    l.innerHTML = g.map(function(b){
      return '<a class="bz-oge' + (b.okundu ? '' : ' yeni') + '" href="' + esc(b.link || '#') + '" data-id="' + b.id + '" onclick="return bzTik(event,this)" style="--bzr:' + (renk[b.oncelik] || renk[0]) + '">'
        + '<span class="bz-ik"><span class="material-icons-outlined">' + (ikon[b.tip] || 'notifications') + '</span></span>'
        + '<span class="bz-metin"><div class="bz-b">' + esc(String(b.baslik || '').replace(/^[^\p{L}\p{N}]+/u, '')) + '</div>' + (b.govde ? '<div class="bz-g">' + esc(b.govde) + '</div>' : '') + '<div class="bz-z">' + esc(b.zaman) + '</div></span></a>';
    }).join('');
  }
  function sayac(n){
    var s = document.getElementById('bzSay'); if (!s) return;
    s.textContent = n > 99 ? '99+' : n; s.classList.toggle('var', n > 0);
    if (sonSay !== null && n > sonSay) { var z = document.getElementById('bz'); z.classList.remove('zil'); void z.offsetWidth; z.classList.add('zil'); }
    sonSay = n;
  }
  function yukle(){
    fetch('/api/bildirim/kutu', {headers: {'Accept': 'application/json'}, credentials: 'same-origin'}).then(function(r){ return r.ok ? r.json() : null; }).then(function(d){
      if (!d || !d.ok) return; veri = d.liste || []; sayac(d.okunmamis || 0); ciz();
    }).catch(function(){});
  }
  function okundu(id){
    return fetch('/api/bildirim/okundu', {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name=csrf-token]') || {}).content}, body: JSON.stringify(id ? {id: id} : {})}).catch(function(){});
  }
  window.bzAcKapa = function(e){ e.stopPropagation(); var z = document.getElementById('bz'); z.classList.toggle('acik'); if (z.classList.contains('acik')) yukle(); };
  window.bzFiltre = function(b){ document.querySelectorAll('.bz-sekme button').forEach(function(x){ x.classList.toggle('aktif', x === b); }); filtre = b.dataset.f; ciz(); };
  window.bzHepsiOkundu = function(){ okundu(null).then(function(){ veri.forEach(function(b){ b.okundu = true; }); sayac(0); ciz(); }); };
  window.bzTik = function(e, a){
    var id = +a.dataset.id, b = veri.find(function(x){ return x.id === id; });
    if (b && !b.okundu) { b.okundu = true; sayac(Math.max(0, (sonSay || 1) - 1)); okundu(id); }
    if (a.getAttribute('href') === '#') { e.preventDefault(); ciz(); return false; }
    return true;
  };
  document.addEventListener('click', function(){ var z = document.getElementById('bz'); if (z) z.classList.remove('acik'); });
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') { var z = document.getElementById('bz'); if (z) z.classList.remove('acik'); } });
  yukle(); setInterval(function(){ if (!document.hidden) yukle(); }, 60000);
})();
</script>

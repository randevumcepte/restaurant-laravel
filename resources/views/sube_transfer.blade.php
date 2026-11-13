@extends('layout.app')
@section('rk_ikon', 'swap_horiz')
@section('baslik', 'Şubeler Arası Transfer')
@section('title', 'Şubeler Arası Transfer')

@section('content')
<style>
  #trf{max-width:1280px;margin:0 auto}
  #trf *{box-sizing:border-box}
  .trf-hero{position:relative;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:20px 22px 18px;margin-bottom:16px;overflow:hidden}
  .trf-hero:before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,#0891B2,#10B981,#7C3AED)}
  .trf-hero h2{margin:0 0 4px;font-size:19px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px}
  .trf-hero h2 .material-icons-outlined{color:#0891B2}
  .trf-hero p{margin:0;font-size:13px;color:var(--sub);line-height:1.5;max-width:860px}
  .trf-kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:16px}
  .trf-k{--k:#0891B2;border-radius:14px;padding:12px 14px;background:color-mix(in srgb,var(--k) 9%,var(--card));border:1px solid color-mix(in srgb,var(--k) 25%,transparent);cursor:pointer}
  .trf-k .l{font-size:11.5px;font-weight:700;color:var(--sub);display:flex;align-items:center;gap:6px}
  .trf-k .l .material-icons-outlined{font-size:16px;color:var(--k)}
  .trf-k .v{font-size:22px;font-weight:800;color:var(--ink);margin-top:4px}
  .trf-k .a{font-size:11px;color:var(--sub);margin-top:2px}
  .trf-sek{display:flex;gap:4px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:4px;margin-bottom:14px;flex-wrap:wrap}
  .trf-sek button{border:0;background:transparent;color:var(--sub);font-weight:700;font-size:12.5px;padding:8px 13px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit}
  .trf-sek button.on{background:var(--ink);color:var(--card)}
  .trf-sek button .n{font-size:10.5px;background:#F43F5E;color:#fff;border-radius:20px;padding:1px 7px}
  .trf-sek button .material-icons-outlined{font-size:17px}
  .trf-panel{display:none}.trf-panel.on{display:block}
  .trf-kart{--c:#0891B2;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--c);border-radius:14px;padding:13px 16px;margin-bottom:10px}
  .trf-kart .ust{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .trf-kart .bas{font-weight:800;font-size:14px;color:var(--ink)}
  .trf-kart .alt{font-size:12.5px;color:var(--sub);margin-top:5px;line-height:1.5}
  .trf-kart .alt b{color:var(--ink)}
  .trf-rozet{font-size:10.5px;font-weight:800;border-radius:20px;padding:3px 9px;white-space:nowrap;background:color-mix(in srgb,var(--r,#64748B) 13%,transparent);color:var(--r,#64748B)}
  .trf-btnler{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px}
  .trf-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:10px;font-weight:700;font-size:12.5px;text-decoration:none;border:1px solid var(--line);color:var(--ink);background:var(--card);cursor:pointer;font-family:inherit}
  .trf-btn.ana{background:#0891B2;border-color:#0891B2;color:#fff}
  .trf-btn.yesil{background:#059669;border-color:#059669;color:#fff}
  .trf-btn.kirmizi{color:#e11d48;border-color:color-mix(in srgb,#e11d48 35%,transparent)}
  .trf-btn .material-icons-outlined{font-size:17px}
  .trf-btn:disabled{opacity:.55;cursor:wait}
  .trf-iki{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .trf-kutu{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px}
  .trf-kutu h4{margin:0 0 4px;font-size:14px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:7px}
  .trf-kutu .ac{font-size:12px;color:var(--sub);margin:0 0 12px}
  .trf-bos{text-align:center;padding:26px 14px;color:var(--sub);font-size:13px}
  .trf-bos .material-icons-outlined{font-size:36px;display:block;margin-bottom:6px;color:var(--sub2)}
  .trf-kaynak{display:flex;align-items:center;gap:8px;font-size:12.5px;padding:7px 10px;border-radius:10px;background:var(--card2);margin-top:6px;flex-wrap:wrap}
  .trf-kaynak b{color:var(--ink)}
  .trf-kaynak .sp{flex:1}
  .trf-tablo{width:100%;border-collapse:collapse;font-size:12.5px}
  .trf-tablo th{text-align:left;font-size:11px;color:var(--sub);font-weight:800;padding:8px 8px;border-bottom:1px solid var(--line);white-space:nowrap}
  .trf-tablo td{padding:9px 8px;border-bottom:1px solid var(--line);color:var(--ink);vertical-align:top}
  .trf-tablo tr.tik{cursor:pointer}.trf-tablo tr.tik:hover td{background:var(--card2)}
  .trf-arac{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
  .trf-in,.trf-sel{background:var(--card);border:1px solid var(--line);border-radius:10px;padding:9px 11px;color:var(--ink);font-size:13px;font-family:inherit}
  .trf-in.k{width:110px}
  .trf-satir{display:grid;grid-template-columns:minmax(0,1fr) 130px 36px;gap:8px;align-items:start;margin-bottom:8px}
  .trf-satir .bilgi{grid-column:1/-1;font-size:11.5px;color:var(--sub);margin-top:-4px}
  .trf-not{display:flex;gap:8px;align-items:flex-start;font-size:12px;color:var(--ink);background:color-mix(in srgb,#0891B2 8%,var(--card));border:1px solid color-mix(in srgb,#0891B2 22%,transparent);border-radius:12px;padding:10px 13px;margin-bottom:12px;line-height:1.5}
  .trf-not.uyari{background:color-mix(in srgb,#F59E0B 10%,var(--card));border-color:color-mix(in srgb,#F59E0B 30%,transparent)}
  .trf-not .material-icons-outlined{font-size:18px;color:#0891B2;flex:none}
  .trf-not.uyari .material-icons-outlined{color:#D97706}
  /* Modal */
  .trf-m{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;align-items:center;justify-content:center;z-index:200;padding:16px}
  .trf-m.acik{display:flex}
  .trf-mk{background:var(--card);border-radius:18px;max-width:860px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,.3)}
  .trf-mh{display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid var(--line);position:sticky;top:0;background:var(--card);z-index:1}
  .trf-mh .t{font-weight:800;font-size:16px;color:var(--ink)}
  .trf-mh .x{margin-left:auto;border:0;background:var(--card2);border-radius:10px;width:34px;height:34px;cursor:pointer;color:var(--ink)}
  .trf-mb{padding:16px 20px}
  .trf-adim{display:flex;gap:0;margin:4px 0 16px}
  .trf-adim div{flex:1;text-align:center;font-size:11px;font-weight:700;color:var(--sub);position:relative;padding-top:24px}
  .trf-adim div:before{content:"";position:absolute;top:6px;left:50%;transform:translateX(-50%);width:14px;height:14px;border-radius:50%;background:var(--line);z-index:1}
  .trf-adim div:after{content:"";position:absolute;top:12px;left:-50%;width:100%;height:2px;background:var(--line)}
  .trf-adim div:first-child:after{display:none}
  .trf-adim div.ok{color:#059669}.trf-adim div.ok:before{background:#059669}.trf-adim div.ok:after{background:#059669}
  .trf-adim div.su{color:#0891B2}.trf-adim div.su:before{background:#0891B2;box-shadow:0 0 0 4px color-mix(in srgb,#0891B2 25%,transparent)}
  .trf-taraf{display:grid;grid-template-columns:1fr 40px 1fr;gap:8px;align-items:center;margin-bottom:14px}
  .trf-taraf>div{background:var(--card2);border-radius:12px;padding:10px 12px;font-size:12.5px;color:var(--sub)}
  .trf-taraf b{display:block;color:var(--ink);font-size:14px}
  .trf-taraf .ok{text-align:center;color:#0891B2;background:transparent}
  .trf-lot{font-size:11px;color:var(--sub);margin-top:3px}
  @media (max-width:900px){.trf-iki{grid-template-columns:1fr}.trf-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width:560px){.trf-taraf{grid-template-columns:1fr}.trf-taraf .ok{display:none}.trf-satir{grid-template-columns:1fr 100px 36px}}
</style>
<div class="rk-body">
<div id="trf">
  <div class="trf-hero">
    <h2><span class="material-icons-outlined">swap_horiz</span> Şubeler Arası Transfer — {{ $subeAd }}</h2>
    <p>Yeni sipariş vermeden önce kardeş şubelere bak: fazlası olan ya da son kullanma tarihi yaklaşan ürün başka şubede kullanılsın. Her transfer <b>istek → onay → yolda → teslim</b> adımlarından geçer; stok, lot/SKT ve maliyet otomatik taşınır, "nereden geldi" her yerde görünür.</p>
    <div class="trf-kpi" id="trfKpi"></div>
  </div>

  @if (!$grupVar || !count($ortaklar))
    <div class="trf-kutu" style="text-align:center;padding:34px 20px">
      <span class="material-icons-outlined" style="font-size:44px;color:#0891B2">hub</span>
      <h3 style="margin:8px 0 6px;color:var(--ink)">Bu şube henüz bir şube grubuna bağlı değil</h3>
      <p style="margin:0 auto;max-width:560px;color:var(--sub);font-size:13px;line-height:1.55">Şubeler arası transfer, aynı patrona ait şubeler (veya franchise bağı olan işletmeler) arasında çalışır. Başka işletmelerin stoğu asla görünmez. Şubelerinizi gruplamak için ResteOS destek ekibine başvurun.</p>
    </div>
  @else
  <div class="trf-sek" id="trfSek">
    <button type="button" data-p="is"><span class="material-icons-outlined">pending_actions</span>Bekleyen İşler <span class="n" id="trfIsN" style="display:none"></span></button>
    <button type="button" data-p="oneri"><span class="material-icons-outlined">lightbulb</span>Öneriler</button>
    <button type="button" data-p="yeni"><span class="material-icons-outlined">add_circle</span>Yeni Transfer</button>
    <button type="button" data-p="liste"><span class="material-icons-outlined">list_alt</span>Tüm Transferler</button>
    @if ($yonetici)<button type="button" data-p="ayar"><span class="material-icons-outlined">tune</span>Ayarlar</button>@endif
  </div>

  <div class="trf-panel" id="trfP-is"><div id="trfIsler"></div></div>

  <div class="trf-panel" id="trfP-oneri">
    <div class="trf-iki">
      <div class="trf-kutu"><h4><span class="material-icons-outlined" style="color:#7C3AED">south_west</span>Bende eksik — kardeş şubede var</h4><p class="ac">Kritik seviyenin altındaki ürünleriniz başka şubede fazla. Yeni sipariş yerine transfer isteyin.</p><div id="trfOAl"><div class="trf-bos">Yükleniyor…</div></div></div>
      <div class="trf-kutu"><h4><span class="material-icons-outlined" style="color:#D97706">recycling</span>SKT riski — başka şube kullansın</h4><p class="ac">Son kullanma tarihine kadar sizde bitmeyecek ürünler. Tüketen şubeye gönderin, çöpe gitmesin.</p><div id="trfOVer"><div class="trf-bos">Yükleniyor…</div></div></div>
    </div>
  </div>

  <div class="trf-panel" id="trfP-yeni">
    <div class="trf-kutu">
      <div class="trf-arac">
        <select class="trf-sel" id="trfYon"><option value="iste">Ürün İSTE (karşı şube gönderir)</option><option value="oner">Ürün GÖNDER (karşı şube kabul eder)</option></select>
        <select class="trf-sel" id="trfOrtak" style="min-width:220px"></select>
      </div>
      <div class="trf-not" id="trfTurNot"><span class="material-icons-outlined">info</span><span></span></div>
      <div id="trfSatirlar"></div>
      <button type="button" class="trf-btn" onclick="trfSatirEkle()"><span class="material-icons-outlined">add</span>Ürün ekle</button>
      <textarea class="trf-in" id="trfNot" rows="2" placeholder="Not (isteğe bağlı)" style="width:100%;margin-top:12px;resize:vertical"></textarea>
      <div class="trf-btnler"><button type="button" class="trf-btn ana" id="trfGonderBtn" onclick="trfYeniGonder()"><span class="material-icons-outlined">send</span>Gönder</button></div>
    </div>
  </div>

  <div class="trf-panel" id="trfP-liste">
    <div class="trf-arac">
      <select class="trf-sel" id="trfLYon"><option value="">Gelen + giden</option><option value="gelen">Bana gelen</option><option value="giden">Benden giden</option></select>
      <select class="trf-sel" id="trfLDurum"><option value="">Tüm durumlar</option><option value="acik">Açık (bitmemiş)</option><option value="teslim">Teslim alındı</option><option value="kapali">Reddedildi / iptal</option></select>
      <input class="trf-in" id="trfLAra" placeholder="Ürün, şube veya TRF no ara…" style="flex:1;min-width:180px">
    </div>
    <div class="trf-kutu" style="padding:6px 8px;overflow-x:auto"><table class="trf-tablo"><thead><tr><th>No</th><th>Tarih</th><th>Yön</th><th>Karşı şube</th><th>Ürünler</th><th>Tür</th><th>Durum</th></tr></thead><tbody id="trfLTablo"></tbody></table></div>
  </div>

  @if ($yonetici)
  <div class="trf-panel" id="trfP-ayar">
    <div class="trf-iki">
      <div class="trf-kutu">
        <h4><span class="material-icons-outlined" style="color:#0891B2">tune</span>Öneri kuralları (grubun tamamı)</h4>
        <p class="ac">"Fazla stok" = mevcut − kritik stok − önümüzdeki N günlük tüketim. "SKT riski" = son kullanma tarihine kadar tüketilemeyecek kısım.</p>
        <label style="font-size:12.5px;color:var(--ink);display:block;margin-bottom:10px">Kaç günlük tüketim şubede kalsın? <input class="trf-in k" type="number" min="1" max="60" id="trfAGun" value="{{ $ayar['gun'] }}"> gün</label>
        <label style="font-size:12.5px;color:var(--ink);display:block;margin-bottom:12px">SKT'ye kaç gün kala uyarsın? <input class="trf-in k" type="number" min="1" max="60" id="trfASkt" value="{{ $ayar['skt'] }}"> gün</label>
        <button type="button" class="trf-btn ana" onclick="trfAyarKaydet()"><span class="material-icons-outlined">save</span>Kaydet</button>
        <h4 style="margin-top:18px"><span class="material-icons-outlined" style="color:#7C3AED">inventory</span>Bu şubenin kritik stokları</h4>
        <p class="ac">Boş bırakırsanız malzeme kartındaki genel kritik stok kullanılır. Yalnız bu şubeyi etkiler.</p>
        <input class="trf-in" id="trfKAra" placeholder="Malzeme ara…" style="width:100%;margin-bottom:8px">
        <div id="trfKritik" style="max-height:340px;overflow:auto"></div>
      </div>
      <div class="trf-kutu">
        <h4><span class="material-icons-outlined" style="color:#059669">badge</span>Personel şube erişimi</h4>
        <p class="ac">Kendi şubesi dışında hangi şubeye geçebileceğini siz belirlersiniz (ör. merkez satın almacı). Kimseye otomatik açılmaz. Transfer yapabilmek için ayrıca Personel sayfasından "Şubeler Arası Transfer" yetkisi verilmelidir.</p>
        <div id="trfErisim"><div class="trf-bos">Yalnız patron görür.</div></div>
      </div>
    </div>
  </div>
  @endif
  @endif
</div>
</div>

<div class="trf-m" id="trfModal" onclick="if(event.target===this)trfKapat()"><div class="trf-mk"><div class="trf-mh"><span class="material-icons-outlined" style="color:#0891B2">swap_horiz</span><span class="t" id="trfMBas"></span><button class="x" onclick="trfKapat()">✕</button></div><div class="trf-mb" id="trfMGovde"></div></div></div>

<script>
(function () {
  const LISTE = {!! json_encode($liste, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  const ORTAK = {!! json_encode($ortaklar, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  const MALZ = {!! json_encode($malzemeler, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  const KRITIK = {!! json_encode((object) ($kritik ?? []), JSON_HEX_TAG) !!};
  const AC_ID = {{ (int) $acId }};
  const YONETICI = {{ $yonetici ? 'true' : 'false' }};
  const el = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const sayi = v => { v = +v || 0; return (Math.round(v * 1000) / 1000).toLocaleString('tr-TR', { maximumFractionDigits: 3 }); };
  const tl = v => (+v || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺';
  const TUR_RENK = { ic: '#0891B2', firmalar_arasi: '#7C3AED', franchise: '#DB2777', vkn_eksik: '#DC2626' };
  const TUR_AD = { ic: 'İç transfer', firmalar_arasi: 'Firmalar arası satış', franchise: 'Franchise tedarik', vkn_eksik: 'Vergi no eksik' };
  const DUR_RENK = { oneri: '#D97706', istendi: '#D97706', hazirlaniyor: '#2563EB', yolda: '#0891B2', teslim: '#059669', reddedildi: '#DC2626', iptal: '#64748B' };
  const csrf = () => (document.querySelector('meta[name=csrf-token]') || {}).content || '';
  async function post(url, data) {
    const r = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(data || {}) });
    try { return await r.json(); } catch (e) { return { ok: false, mesaj: 'Sunucu hatası (' + r.status + ')' }; }
  }
  async function getJ(url) { const r = await fetch(url, { headers: { 'Accept': 'application/json' } }); try { return await r.json(); } catch (e) { return { ok: false }; } }
  const bilgi = (m, t) => { try { window.rkBilgi(m, t); } catch (e) { alert(m); } };
  const sor = (m, fn) => { if (window.rkIste) window.rkIste(m, fn, { baslik: 'Açıklama' }); else fn(prompt(m)); };
  const rozet = (metin, renk) => `<span class="trf-rozet" style="--r:${renk}">${esc(metin)}</span>`;

  if (!el('trfSek')) return;

  // ---------- Sekmeler
  let sekme = 'is';
  try { sekme = sessionStorage.getItem('trfSekme') || 'is'; } catch (e) {}
  function sekmeAc(s) {
    if (!el('trfP-' + s)) s = 'is';
    sekme = s; try { sessionStorage.setItem('trfSekme', s); } catch (e) {}
    document.querySelectorAll('#trfSek button').forEach(b => b.classList.toggle('on', b.dataset.p === s));
    document.querySelectorAll('.trf-panel').forEach(p => p.classList.toggle('on', p.id === 'trfP-' + s));
    if (s === 'oneri') onerileriYukle();
    if (s === 'ayar') ayarYukle();
  }
  document.querySelectorAll('#trfSek button').forEach(b => b.onclick = () => sekmeAc(b.dataset.p));

  // ---------- KPI + bekleyen işler
  function isler() {
    return LISTE.filter(t => (t.yon === 'giden' && ['istendi', 'hazirlaniyor'].includes(t.durum)) || (t.yon === 'gelen' && ['oneri', 'yolda'].includes(t.durum)));
  }
  function kpi() {
    const ay = new Date(); const buAy = LISTE.filter(t => { const p = (t.tarih || '').split(' ')[0].split('.'); return +p[1] === ay.getMonth() + 1 && +p[2] === ay.getFullYear() && t.durum === 'teslim'; });
    const k = (ik, l, v, a, c, s) => `<div class="trf-k" style="--k:${c}" onclick="trfSekmeAc('${s}')"><div class="l"><span class="material-icons-outlined">${ik}</span>${l}</div><div class="v">${v}</div><div class="a">${a}</div></div>`;
    const is = isler();
    el('trfKpi').innerHTML =
      k('pending_actions', 'Sizi bekleyen', is.length, is.length ? 'onay / yola çıkar / teslim al' : 'bekleyen iş yok 👍', '#F43F5E', 'is') +
      k('local_shipping', 'Yolda (size gelen)', LISTE.filter(t => t.yon === 'gelen' && t.durum === 'yolda').length, 'gelince teslim alın', '#0891B2', 'is') +
      k('task_alt', 'Bu ay tamamlanan', buAy.length, buAy.filter(t => t.yon === 'gelen').length + ' gelen · ' + buAy.filter(t => t.yon === 'giden').length + ' giden', '#059669', 'liste') +
      k('hub', 'Transfer ortağı', ORTAK.length, ORTAK.map(o => o.ad).slice(0, 3).join(', '), '#7C3AED', 'yeni');
    const n = el('trfIsN'); if (n) { n.textContent = is.length; n.style.display = is.length ? '' : 'none'; }
  }
  function satirKart(t, aksiyon) {
    const karsi = t.yon === 'giden' ? t.alan : t.gonderen;
    return `<div class="trf-kart" style="--c:${DUR_RENK[t.durum] || '#64748B'}">
      <div class="ust"><span class="bas">${esc(t.no)}</span>${rozet(t.yon === 'giden' ? '→ ' + karsi : '← ' + karsi, '#0891B2')}${rozet(TUR_AD[t.tur] || t.tur, TUR_RENK[t.tur])}${rozet(t.durum_ad, DUR_RENK[t.durum])}${t.talep_id ? rozet('Talepten', '#7C3AED') : ''}<span style="margin-left:auto;font-size:11.5px;color:var(--sub)">${esc(t.tarih)}</span></div>
      <div class="alt">📦 ${esc(t.ozet)}${t.kalem > 4 ? ' +' + (t.kalem - 4) : ''} · <b>${esc(t.isteyen || '')}</b></div>
      <div class="trf-btnler">${aksiyon}<button class="trf-btn" onclick="trfAc(${t.id})"><span class="material-icons-outlined">visibility</span>Detay</button></div></div>`;
  }
  function islerCiz() {
    const is = isler();
    if (!is.length) { el('trfIsler').innerHTML = '<div class="trf-kutu trf-bos"><span class="material-icons-outlined">task_alt</span>Sizi bekleyen transfer işi yok. Öneriler sekmesinden eksik ürünleri kardeş şubelerden isteyebilirsiniz.</div>'; return; }
    el('trfIsler').innerHTML = is.map(t => {
      let a = '';
      if (t.yon === 'giden' && t.durum === 'istendi') a = `<button class="trf-btn yesil" onclick="trfAc(${t.id})"><span class="material-icons-outlined">fact_check</span>İncele ve onayla</button>`;
      if (t.yon === 'giden' && t.durum === 'hazirlaniyor') a = `<button class="trf-btn ana" onclick="trfAc(${t.id})"><span class="material-icons-outlined">local_shipping</span>Yola çıkar</button>`;
      if (t.yon === 'gelen' && t.durum === 'yolda') a = `<button class="trf-btn yesil" onclick="trfAc(${t.id})"><span class="material-icons-outlined">inventory</span>Teslim al</button>`;
      if (t.yon === 'gelen' && t.durum === 'oneri') a = `<button class="trf-btn yesil" onclick="trfAc(${t.id})"><span class="material-icons-outlined">thumb_up</span>Öneriyi incele</button>`;
      return satirKart(t, a);
    }).join('');
  }

  // ---------- Liste
  function listeCiz() {
    const yon = el('trfLYon').value, d = el('trfLDurum').value, q = (el('trfLAra').value || '').toLocaleLowerCase('tr');
    const l = LISTE.filter(t => (!yon || t.yon === yon) && (!d || (d === 'acik' ? ['oneri', 'istendi', 'hazirlaniyor', 'yolda'].includes(t.durum) : d === 'teslim' ? t.durum === 'teslim' : ['reddedildi', 'iptal'].includes(t.durum)))
      && (!q || [t.no, t.gonderen, t.alan, t.ozet].join(' ').toLocaleLowerCase('tr').includes(q)));
    el('trfLTablo').innerHTML = l.length ? l.map(t => `<tr class="tik" onclick="trfAc(${t.id})"><td><b>${esc(t.no)}</b></td><td>${esc(t.tarih)}</td><td>${t.yon === 'gelen' ? '⬅ Gelen' : 'Giden ➡'}</td><td>${esc(t.yon === 'gelen' ? t.gonderen : t.alan)}</td><td>${esc(t.ozet)}</td><td>${rozet(TUR_AD[t.tur] || t.tur, TUR_RENK[t.tur])}</td><td>${rozet(t.durum_ad, DUR_RENK[t.durum])}</td></tr>`).join('')
      : '<tr><td colspan="7" class="trf-bos">Kayıt yok.</td></tr>';
  }
  ['trfLYon', 'trfLDurum'].forEach(i => el(i).addEventListener('change', listeCiz));
  el('trfLAra').addEventListener('input', listeCiz);

  // ---------- Öneriler
  let oneriYuklendi = false;
  async function onerileriYukle() {
    if (oneriYuklendi) return; oneriYuklendi = true;
    const j = await getJ('/api/sube-transfer/oneriler');
    if (!j.ok) { el('trfOAl').innerHTML = el('trfOVer').innerHTML = '<div class="trf-bos">Yüklenemedi.</div>'; oneriYuklendi = false; return; }
    el('trfOAl').innerHTML = (j.al || []).length ? j.al.map(o => `<div class="trf-kart" style="--c:#7C3AED"><div class="ust"><span class="bas">${esc(o.ad)}</span>${rozet('Sizde ' + sayi(o.mevcut) + ' ' + o.birim, '#DC2626')}${rozet('Kritik ' + sayi(o.kritik), '#64748B')}</div>
      ${o.kaynaklar.map(k => `<div class="trf-kaynak"><span class="material-icons-outlined" style="font-size:17px;color:#7C3AED">storefront</span><b>${esc(k.sube_ad)}</b> ${sayi(k.verilebilir)} ${esc(o.birim)} verebilir${k.skt_risk > 0 ? ' · <span style="color:#D97706">⚠️ ' + sayi(k.skt_risk) + ' SKT riski (' + esc(k.en_yakin_skt) + ')</span>' : ''}<span class="sp"></span>
        <button class="trf-btn ana" onclick='trfHizliIste(${k.sube_id}, ${o.malzeme_id}, ${JSON.stringify(Math.min(k.verilebilir, Math.max(o.ihtiyac, 0) || k.verilebilir))})'><span class="material-icons-outlined">south_west</span>İste</button></div>`).join('')}</div>`).join('')
      : '<div class="trf-bos"><span class="material-icons-outlined">check_circle</span>Eksik ürününüz kardeş şubelerde yok ya da eksik ürün yok.</div>';
    el('trfOVer').innerHTML = (j.ver || []).length ? j.ver.map(o => `<div class="trf-kart" style="--c:#D97706"><div class="ust"><span class="bas">${esc(o.ad)}</span>${rozet(sayi(o.skt_risk) + ' ' + o.birim + ' bozulabilir', '#D97706')}${rozet('SKT ' + (o.en_yakin_skt || ''), '#DC2626')}${o.maliyet ? rozet('≈ ' + tl(o.maliyet), '#64748B') : ''}</div>
      ${o.alicilar.length ? o.alicilar.map(a => `<div class="trf-kaynak"><span class="material-icons-outlined" style="font-size:17px;color:#D97706">storefront</span><b>${esc(a.sube_ad)}</b> günde ${sayi(a.gunluk)} ${esc(o.birim)} kullanıyor<span class="sp"></span>
        <button class="trf-btn" style="border-color:#D97706;color:#B45309" onclick='trfHizliOner(${a.sube_id}, ${o.malzeme_id}, ${JSON.stringify(o.skt_risk)})'><span class="material-icons-outlined">north_east</span>Gönder</button></div>`).join('') : '<div class="alt">Bu ürünü kullanan kardeş şube yok — menüde öne çıkarmayı düşünün.</div>'}</div>`).join('')
      : '<div class="trf-bos"><span class="material-icons-outlined">check_circle</span>SKT riski olan ürün yok.</div>';
  }

  // ---------- Yeni transfer
  const malMap = {}; MALZ.forEach(m => malMap[m.id] = m);
  let satirSay = 0;
  function ortakDoldur() {
    const yon = el('trfYon').value;
    const uygun = ORTAK.filter(o => yon === 'iste' ? ['iki', 'gelir'].includes(o.yon) : ['iki', 'gider'].includes(o.yon));
    el('trfOrtak').innerHTML = uygun.length ? uygun.map(o => `<option value="${o.id}">${esc(o.ad)} · ${esc(TUR_AD[o.tur] || o.tur)}</option>`).join('') : '<option value="">Uygun şube yok</option>';
    turNot(); stokYenile();
  }
  function turNot() {
    const o = ORTAK.find(x => String(x.id) === el('trfOrtak').value); const n = el('trfTurNot');
    if (!o) { n.style.display = 'none'; return; }
    n.style.display = ''; n.classList.toggle('uyari', o.tur !== 'ic');
    n.querySelector('span:last-child').innerHTML = {
      ic: '<b>İç transfer</b> — aynı vergi no. Maliyet bedeliyle sevk irsaliyesi; finansa kayıt düşmez, yalnız stok yer değiştirir.',
      firmalar_arasi: '<b>Firmalar arası satış</b> — farklı vergi no. Teslim alınınca alan firmaya otomatik <b>alış faturası</b>, gönderen firmaya <b>cari alacak</b> yazılır. Muhasebe çıktılarında her firma kendi kaydını görür.',
      franchise: '<b>Franchise tedarik</b> — fatura + cari; fiyat, merkezin belirlediği çarpanla (maliyet × ' + (o.carpan || 1) + ').',
      vkn_eksik: '<b>Vergi numarası eksik</b> — iki şubenin de Yasal Bilgiler sayfasında vergi no girilmeden transfer yapılamaz (iç mi, firmalar arası mı belirlenemiyor).'
    }[o.tur] || '';
  }
  window.trfSatirEkle = function (mid, mik) {
    const i = ++satirSay;
    const d = document.createElement('div'); d.className = 'trf-satir'; d.id = 'trfS' + i;
    d.innerHTML = `<select class="trf-sel" data-m="${i}"><option value="">Malzeme seçin…</option>${MALZ.map(m => `<option value="${m.id}" ${m.id === mid ? 'selected' : ''}>${esc(m.ad)}${m.birim ? ' (' + esc(m.birim) + ')' : ''}</option>`).join('')}</select>
      <input class="trf-in" type="number" step="any" min="0" placeholder="Miktar" data-q="${i}" value="${mik ? +(+mik).toFixed(3) : ''}">
      <button type="button" class="trf-btn kirmizi" style="padding:8px" onclick="document.getElementById('trfS${i}').remove()"><span class="material-icons-outlined">close</span></button>
      <div class="bilgi" id="trfB${i}"></div>`;
    el('trfSatirlar').appendChild(d);
    d.querySelector('select').addEventListener('change', stokYenile);
    stokYenile();
  };
  let stokZ = null;
  function stokYenile() {
    clearTimeout(stokZ);
    stokZ = setTimeout(async () => {
      const sel = [...document.querySelectorAll('#trfSatirlar select')]; const mids = sel.map(s => s.value).filter(Boolean);
      if (!mids.length) return;
      const j = await getJ('/api/sube-transfer/stok?m=' + mids.join(','));
      if (!j.ok) return;
      const o = el('trfOrtak').value; const yon = el('trfYon').value;
      sel.forEach(s => {
        const b = el('trfB' + s.dataset.m); if (!b || !s.value) { if (b) b.textContent = ''; return; }
        const m = malMap[s.value] || {}; const ben = (j.ben || {})[s.value];
        if (yon === 'iste') {
          const k = ((j.ortak || {})[s.value] || []).find(x => String(x.sube_id) === String(o));
          b.innerHTML = (ben ? 'Sizde ' + sayi(ben.mevcut) + ' ' + esc(m.birim) + ' · ' : '') + (k ? `<b style="color:#059669">Karşı şube ${sayi(k.verilebilir)} ${esc(m.birim)} verebilir</b>${k.skt_risk > 0 ? ' · ⚠️ SKT ' + esc(k.en_yakin_skt) : ''}` : '<span style="color:#D97706">Karşı şubede fazla görünmüyor (yine de isteyebilirsiniz; onları kendi stoklarına göre karar verir)</span>');
        } else b.innerHTML = ben ? `Sizde ${sayi(ben.mevcut)} ${esc(m.birim)}${ben.skt_risk > 0 ? ' · <b style="color:#D97706">' + sayi(ben.skt_risk) + ' SKT riski</b>' : ''} · fazla ${sayi(ben.fazla)}` : '';
      });
    }, 250);
  }
  el('trfYon').addEventListener('change', ortakDoldur);
  el('trfOrtak').addEventListener('change', () => { turNot(); stokYenile(); });
  window.trfYeniGonder = async function () {
    const yon = el('trfYon').value, ortak = +el('trfOrtak').value;
    if (!ortak) return bilgi('Önce şube seçin.', 'wn');
    const kalemler = [...document.querySelectorAll('#trfSatirlar .trf-satir')].map(d => ({ malzeme_id: +d.querySelector('select').value, miktar: d.querySelector('input').value })).filter(k => k.malzeme_id && +k.miktar > 0);
    if (!kalemler.length) return bilgi('En az bir ürün ve miktar girin.', 'wn');
    const b = el('trfGonderBtn'); b.disabled = true;
    const j = await post(yon === 'iste' ? '/sube-transfer/iste' : '/sube-transfer/oner', yon === 'iste' ? { gonderen_sube_id: ortak, kalemler, not: el('trfNot').value, kaynak: window._trfKaynak || 'elle' } : { alan_sube_id: ortak, kalemler, not: el('trfNot').value, kaynak: window._trfKaynak || 'oneri' });
    b.disabled = false;
    bilgi(j.mesaj || (j.ok ? 'Tamam' : 'Hata'), j.ok ? 'ok' : 'er');
    if (j.ok) setTimeout(() => location.href = '/sube-transfer?ac=' + j.id, 700);
  };
  window.trfHizliIste = function (sube, mid, mik) {
    el('trfYon').value = 'iste'; ortakDoldur(); el('trfOrtak').value = String(sube); turNot();
    el('trfSatirlar').innerHTML = ''; trfSatirEkle(mid, mik); window._trfKaynak = 'oneri'; sekmeAc('yeni');
  };
  window.trfHizliOner = function (sube, mid, mik) {
    el('trfYon').value = 'oner'; ortakDoldur(); el('trfOrtak').value = String(sube); turNot();
    el('trfSatirlar').innerHTML = ''; trfSatirEkle(mid, mik); window._trfKaynak = 'skt'; sekmeAc('yeni');
  };

  // ---------- Detay modal
  window.trfKapat = () => el('trfModal').classList.remove('acik');
  window.trfAc = async function (id) {
    el('trfMBas').textContent = 'Yükleniyor…'; el('trfMGovde').innerHTML = ''; el('trfModal').classList.add('acik');
    const j = await getJ('/api/sube-transfer/detay?id=' + id);
    if (!j.ok) { el('trfMGovde').innerHTML = '<div class="trf-bos">Transfer bulunamadı.</div>'; return; }
    const t = j.transfer; const ben = t.ben; const yet = t.yetkili;
    el('trfMBas').innerHTML = esc(t.transfer_no) + ' ' + rozet(t.tur_ad, TUR_RENK[t.tur]) + ' ' + rozet(t.durum_ad, DUR_RENK[t.durum]);
    const sira = ['istendi', 'hazirlaniyor', 'yolda', 'teslim']; const idx = t.durum === 'oneri' ? 0 : sira.indexOf(t.durum);
    const adim = ['İstek / öneri', 'Onay', 'Yolda', 'Teslim'].map((a, i) => `<div class="${['reddedildi', 'iptal'].includes(t.durum) ? '' : (i < idx || t.durum === 'teslim' ? 'ok' : (i === idx ? 'su' : ''))}">${a}</div>`).join('');
    const kim = (ad, z) => ad ? `<div style="font-size:11px">${esc(ad)}${z ? ' · ' + esc(String(z).slice(0, 16).replace('T', ' ')) : ''}</div>` : '';
    let h = `<div class="trf-adim">${adim}</div>
      <div class="trf-taraf"><div>Gönderen<b>${esc(t.gonderen_ad)}</b>${kim(t.onaylayan_ad ? 'Onay: ' + t.onaylayan_ad : '', t.onay_at)}${kim(t.sevk_eden_ad ? 'Sevk: ' + t.sevk_eden_ad : '', t.sevk_at)}</div><div class="ok"><span class="material-icons-outlined" style="font-size:30px">arrow_forward</span></div><div>Alan<b>${esc(t.alan_ad)}</b>${kim(t.isteyen_ad ? (t.durum === 'oneri' || t.kaynak === 'skt' ? 'Öneren: ' : 'İsteyen: ') + t.isteyen_ad : '', t.created_at)}${kim(t.teslim_alan_ad ? 'Teslim: ' + t.teslim_alan_ad : '', t.teslim_at)}</div></div>`;
    if (['reddedildi', 'iptal'].includes(t.durum) && t.red_sebep) h += `<div class="trf-not uyari"><span class="material-icons-outlined">block</span><span><b>${t.durum === 'iptal' ? 'İptal' : 'Red sebebi'}:</b> ${esc(t.red_sebep)}</span></div>`;
    if (t.not) h += `<div class="trf-not"><span class="material-icons-outlined">sticky_note_2</span><span>${esc(t.not)}</span></div>`;
    if (t.gonderen_not) h += `<div class="trf-not"><span class="material-icons-outlined">reply</span><span><b>Gönderen notu:</b> ${esc(t.gonderen_not)}</span></div>`;
    const onayModu = ben === 'gonderen' && t.durum === 'istendi' && yet;
    const teslimModu = ben === 'alan' && t.durum === 'yolda' && yet;
    h += `<div style="overflow-x:auto"><table class="trf-tablo"><thead><tr><th>Ürün</th><th>İstenen</th><th>Onaylanan</th><th>Gönderilen</th><th>Teslim</th>${t.tur !== 'ic' ? '<th>Birim fiyat</th>' : '<th>Birim maliyet</th>'}</tr></thead><tbody>`;
    t.kalemler.forEach(k => {
      const lot = (k.lotlar || []).map(l => (l.lot_no ? 'Lot ' + esc(l.lot_no) : 'Lotsuz') + (l.skt ? ' · SKT ' + esc(l.skt) : '') + ' · ' + sayi(l.miktar)).join(' | ');
      h += `<tr><td><b>${esc(k.ad)}</b>${lot ? `<div class="trf-lot">🏷️ ${lot}</div>` : ''}${k.fark_not ? `<div class="trf-lot" style="color:#DC2626">⚠️ ${esc(k.fark_not)}</div>` : ''}</td>
        <td>${sayi(k.istenen)} ${esc(k.birim)}</td>
        <td>${onayModu ? `<input class="trf-in k" type="number" step="any" min="0" max="${k.istenen}" value="${+k.istenen}" data-onay="${k.id}">` : (k.onaylanan != null ? sayi(k.onaylanan) : '—')}</td>
        <td>${k.gonderilen != null ? sayi(k.gonderilen) : '—'}</td>
        <td>${teslimModu ? `<input class="trf-in k" type="number" step="any" min="0" max="${k.gonderilen}" value="${+k.gonderilen}" data-teslim="${k.id}"><input class="trf-in" style="width:100%;margin-top:4px;font-size:11.5px;padding:6px 8px" placeholder="Eksikse not" data-fark="${k.id}">` : (k.teslim_alinan != null ? sayi(k.teslim_alinan) : '—')}</td>
        <td>${+k.birim_fiyat ? tl(k.birim_fiyat) : (+k.birim_maliyet ? tl(k.birim_maliyet) : '—')}</td></tr>`;
    });
    h += '</tbody></table></div>';
    if (+t.maliyet_toplam || +t.net_toplam) h += `<div style="display:flex;gap:18px;flex-wrap:wrap;margin-top:12px;font-size:12.5px;color:var(--sub)">${+t.maliyet_toplam ? '<span>Maliyet toplamı: <b style="color:var(--ink)">' + tl(t.maliyet_toplam) + '</b></span>' : ''}${+t.net_toplam ? '<span>Fatura: <b style="color:var(--ink)">' + tl(t.net_toplam) + ' + KDV %' + (+t.kdv_orani) + ' = ' + tl(+t.net_toplam + +t.kdv_toplam) + '</b></span>' : ''}</div>`;
    let a = '';
    if (onayModu) {
      if (t.tur !== 'ic') h += `<div style="margin-top:12px;font-size:12.5px;color:var(--ink)">Fatura KDV oranı: <select class="trf-sel" id="trfKdv">${[1, 10, 20, 0].map(v => `<option value="${v}" ${v === +t.kdv_orani ? 'selected' : ''}>%${v}</option>`).join('')}</select></div>`;
      h += `<textarea class="trf-in" id="trfGNot" rows="2" placeholder="Karşı şubeye not (isteğe bağlı)" style="width:100%;margin-top:10px"></textarea>`;
      a += `<button class="trf-btn yesil" onclick="trfKarar(${t.id},'onay')"><span class="material-icons-outlined">check</span>Onayla</button><button class="trf-btn kirmizi" onclick="trfKarar(${t.id},'red')"><span class="material-icons-outlined">close</span>Reddet</button>`;
    }
    if (ben === 'gonderen' && t.durum === 'hazirlaniyor' && yet) a += `<button class="trf-btn ana" onclick="trfSevk(${t.id})"><span class="material-icons-outlined">local_shipping</span>Yola çıkar (stoktan düş)</button>`;
    if (teslimModu) a += `<button class="trf-btn yesil" onclick="trfTeslim(${t.id})"><span class="material-icons-outlined">inventory</span>Teslim al (stoğa gir)</button>`;
    if (ben === 'alan' && t.durum === 'oneri' && yet) a += `<button class="trf-btn yesil" onclick="trfKabul(${t.id},'kabul')"><span class="material-icons-outlined">thumb_up</span>Kabul et</button><button class="trf-btn kirmizi" onclick="trfKabul(${t.id},'red')"><span class="material-icons-outlined">thumb_down</span>Reddet</button>`;
    if (['oneri', 'istendi', 'hazirlaniyor'].includes(t.durum) && yet) a += `<button class="trf-btn kirmizi" onclick="trfIptal(${t.id})"><span class="material-icons-outlined">cancel</span>İptal et</button>`;
    if (['yolda', 'teslim'].includes(t.durum)) a += `<a class="trf-btn" target="_blank" href="/sube-transfer/belge/${t.id}"><span class="material-icons-outlined">print</span>${t.tur === 'ic' ? 'Sevk irsaliyesi' : 'Sevk / fatura belgesi'}</a>`;
    if (t.talep_id) a += `<a class="trf-btn" href="/satinalma-talep?ac=${t.talep_id}"><span class="material-icons-outlined">assignment</span>Bağlı talep</a>`;
    h += `<div class="trf-btnler" style="margin-top:16px">${a}</div>`;
    el('trfMGovde').innerHTML = h;
  };
  async function sonuc(j) { bilgi(j.mesaj || (j.ok ? 'Tamam' : 'Hata'), j.ok ? 'ok' : 'er'); if (j.ok) setTimeout(() => location.reload(), 800); }
  window.trfKarar = async function (id, karar) {
    if (karar === 'red') return sor('Neden reddediyorsunuz? (isteyen şube görecek)', async s => { if (s) sonuc(await post('/sube-transfer/karar', { id, karar: 'red', sebep: s })); });
    const miktar = {}; document.querySelectorAll('[data-onay]').forEach(i => miktar[i.dataset.onay] = i.value);
    sonuc(await post('/sube-transfer/karar', { id, karar: 'onay', miktar, kdv_orani: el('trfKdv') ? el('trfKdv').value : 0, not: el('trfGNot') ? el('trfGNot').value : '' }));
  };
  window.trfSevk = async function (id) {
    const ok = window.rkOnayP ? await window.rkOnayP('Ürünler yola çıksın mı? Onaylanan miktarlar şimdi stoğunuzdan düşecek (SKT\'si en yakın lot önce gider).', null, 'Yola çıkar') : confirm('Yola çıksın mı?');
    if (ok) sonuc(await post('/sube-transfer/sevk', { id }));
  };
  window.trfTeslim = async function (id) {
    const miktar = {}, fark_not = {};
    document.querySelectorAll('[data-teslim]').forEach(i => miktar[i.dataset.teslim] = i.value);
    document.querySelectorAll('[data-fark]').forEach(i => fark_not[i.dataset.fark] = i.value);
    const ok = window.rkOnayP ? await window.rkOnayP('Girdiğiniz miktarlar stoğunuza girecek. Eksik gelen kısım "transfer firesi" olarak kaydedilir ve gönderen şubeye bildirilir.', null, 'Teslim al') : confirm('Teslim alınsın mı?');
    if (ok) sonuc(await post('/sube-transfer/teslim', { id, miktar, fark_not }));
  };
  window.trfKabul = async function (id, karar) {
    if (karar === 'red') return sor('Neden reddediyorsunuz?', async s => { if (s) sonuc(await post('/sube-transfer/kabul', { id, karar: 'red', sebep: s })); });
    sonuc(await post('/sube-transfer/kabul', { id, karar: 'kabul' }));
  };
  window.trfIptal = function (id) { sor('İptal sebebi?', async s => { if (s !== null) sonuc(await post('/sube-transfer/iptal', { id, sebep: s })); }); };
  window.trfSekmeAc = sekmeAc;

  // ---------- Ayarlar
  let ayarYuklendi = false;
  async function ayarYukle() {
    if (ayarYuklendi || !YONETICI) return; ayarYuklendi = true;
    kritikCiz();
    const j = await getJ('/api/sube-transfer/erisim');
    if (!j.ok) { el('trfErisim').innerHTML = '<div class="trf-bos">' + esc(j.mesaj || 'Yalnız patron görür.') + '</div>'; return; }
    const sb = j.subeler || {};
    el('trfErisim').innerHTML = (j.personel || []).length ? `<table class="trf-tablo"><thead><tr><th>Personel</th>${Object.keys(sb).map(s => `<th>${esc(sb[s])}</th>`).join('')}</tr></thead><tbody>` +
      j.personel.map(p => `<tr><td><b>${esc(p.ad)}</b><div class="trf-lot">${esc(p.rol)} · ${esc(p.sube_ad)}</div></td>${Object.keys(sb).map(s => +s === p.sube_id ? '<td style="color:var(--sub)">kendi</td>' : `<td><input type="checkbox" ${p.erisim.includes(+s) ? 'checked' : ''} onchange="trfErisim(${p.id},${s},this.checked)"></td>`).join('')}</tr>`).join('') + '</tbody></table>'
      : '<div class="trf-bos">Personel yok.</div>';
  }
  window.trfErisim = async function (pid, sid, ver) { const j = await post('/sube-transfer/erisim', { personel_id: pid, sube_id: sid, ver: ver ? 1 : 0 }); bilgi(j.ok ? (ver ? 'Erişim verildi.' : 'Erişim kaldırıldı.') : (j.mesaj || 'Hata'), j.ok ? 'ok' : 'er'); };
  window.trfAyarKaydet = async function () { const j = await post('/sube-transfer/ayar', { gun: el('trfAGun').value, skt: el('trfASkt').value }); bilgi(j.mesaj || 'Tamam', j.ok ? 'ok' : 'er'); oneriYuklendi = false; };
  function kritikCiz() {
    const q = (el('trfKAra').value || '').toLocaleLowerCase('tr');
    const l = MALZ.filter(m => !q || m.ad.toLocaleLowerCase('tr').includes(q)).slice(0, 80);
    el('trfKritik').innerHTML = l.map(m => `<div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid var(--line);font-size:12.5px"><span style="flex:1">${esc(m.ad)}</span><input class="trf-in k" type="number" step="any" min="0" placeholder="genel" data-kr="${m.id}" value="${KRITIK[m.id] != null ? KRITIK[m.id] : ''}"><span style="width:28px;color:var(--sub)">${esc(m.birim)}</span></div>`).join('') +
      '<button class="trf-btn ana" style="margin-top:10px" onclick="trfKritikKaydet()"><span class="material-icons-outlined">save</span>Kritik stokları kaydet</button>';
  }
  if (el('trfKAra')) el('trfKAra').addEventListener('input', kritikCiz);
  window.trfKritikKaydet = async function () {
    // Dolu olanlar kaydedilir; önceden kayıtlı olup boşaltılan genel değere döner
    const kritik = {}; document.querySelectorAll('[data-kr]').forEach(i => { if (i.value !== '' || KRITIK[i.dataset.kr] != null) kritik[i.dataset.kr] = i.value; });
    if (!Object.keys(kritik).length) return bilgi('Değer girmediniz.', 'wn');
    const j = await post('/sube-transfer/ayar', { kritik }); bilgi(j.mesaj || 'Tamam', j.ok ? 'ok' : 'er');
    if (j.ok) Object.keys(kritik).forEach(k => { if (kritik[k] === '') delete KRITIK[k]; else KRITIK[k] = +kritik[k]; });
  };

  // ---------- Başlat
  kpi(); islerCiz(); listeCiz(); ortakDoldur(); trfSatirEkle();
  sekmeAc(sekme);
  if (AC_ID) trfAc(AC_ID);
})();
</script>
@endsection

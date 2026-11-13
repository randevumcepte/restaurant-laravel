@extends('layout.app')
@section('rk_ikon', 'block')
@section('baslik', 'Reddedilen Talepler')
@section('title', 'Reddedilen Talepler')

@section('content')
<style>
  #rt{max-width:1280px;margin:0 auto}
  #rt *{box-sizing:border-box}
  /* Üst alan — açık zemin, koyu yazı (Windows'ta net okunur) */
  .rt-hero{position:relative;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:20px 22px 18px;margin-bottom:16px;overflow:hidden}
  .rt-hero:before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,#F43F5E,#F59E0B,#7C3AED)}
  .rt-hero h2{margin:0 0 4px;font-size:19px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:8px}
  .rt-hero h2 .material-icons-outlined{color:#F43F5E}
  .rt-hero p{margin:0;font-size:13px;color:var(--sub);line-height:1.5;max-width:820px}
  .rt-kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:16px}
  .rt-k{--k:#F43F5E;border-radius:14px;padding:12px 14px;background:color-mix(in srgb,var(--k) 9%,var(--card));border:1px solid color-mix(in srgb,var(--k) 25%,transparent)}
  .rt-k .l{font-size:11.5px;font-weight:700;color:var(--sub);display:flex;align-items:center;gap:6px}
  .rt-k .l .material-icons-outlined{font-size:16px;color:var(--k)}
  .rt-k .v{font-size:22px;font-weight:800;color:var(--ink);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .rt-k .v.kucuk{font-size:15px;margin-top:8px}
  .rt-k .a{font-size:11px;color:var(--sub);margin-top:2px}
  /* Araç çubuğu */
  .rt-arac{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .rt-sek{display:flex;gap:4px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:4px}
  .rt-sek button{border:0;background:transparent;color:var(--sub);font-weight:700;font-size:12.5px;padding:7px 12px;border-radius:9px;cursor:pointer;display:flex;align-items:center;gap:6px;font-family:inherit}
  .rt-sek button.on{background:var(--ink);color:var(--card)}
  .rt-sek button .n{font-size:10.5px;background:color-mix(in srgb,currentColor 18%,transparent);border-radius:20px;padding:1px 7px}
  .rt-ara{flex:1;min-width:200px;display:flex;align-items:center;gap:6px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:0 12px}
  .rt-ara input{flex:1;border:0;background:transparent;color:var(--ink);font-size:13px;padding:10px 0;outline:none;font-family:inherit}
  .rt-ara .material-icons-outlined{font-size:18px;color:var(--sub)}
  .rt-sel{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:9px 12px;color:var(--ink);font-size:12.5px;font-family:inherit}
  /* Düzen */
  .rt-duzen{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start}
  /* Kart */
  .rt-kart{--c:#F43F5E;background:var(--card);border:1px solid var(--line);border-radius:16px;margin-bottom:12px;overflow:hidden}
  .rt-kart.fiyat{--c:#F59E0B}
  .rt-kb{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;background:color-mix(in srgb,var(--c) 8%,var(--card));border-bottom:1px solid color-mix(in srgb,var(--c) 20%,transparent)}
  .rt-tip{font-size:10.5px;font-weight:800;color:#fff;background:var(--c);border-radius:20px;padding:3px 10px;white-space:nowrap}
  .rt-dep{font-weight:800;font-size:14.5px;color:var(--ink)}
  .rt-no{font-size:11.5px;font-weight:700;color:var(--sub)}
  .rt-badge{font-size:10px;font-weight:800;border-radius:20px;padding:2px 9px;white-space:nowrap}
  .rt-acil{background:rgba(244,63,94,.14);color:#e11d48}
  .rt-dur{background:color-mix(in srgb,#2563EB 12%,transparent);color:#2563EB}
  .rt-kb .sag{margin-left:auto;font-size:11.5px;color:var(--sub);white-space:nowrap}
  .rt-ic{padding:14px 16px}
  .rt-urunler{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px}
  .rt-ur{font-size:12px;background:var(--card2);border:1px solid var(--line);border-radius:9px;padding:4px 9px;color:var(--ink)}
  .rt-ur b{font-weight:700;color:var(--sub);margin-left:4px}
  .rt-sebep{display:flex;gap:9px;align-items:flex-start;background:color-mix(in srgb,var(--c) 9%,transparent);border-left:3px solid var(--c);border-radius:10px;padding:10px 12px;font-size:13px;color:var(--ink);line-height:1.45}
  .rt-sebep .material-icons-outlined{font-size:18px;color:var(--c);flex:none;margin-top:1px}
  .rt-zaman{display:flex;margin-top:12px;font-size:11.5px;color:var(--sub);flex-wrap:wrap;row-gap:6px}
  .rt-zaman div{display:flex;align-items:center;gap:6px;padding-right:14px;margin-right:14px;border-right:1px dashed var(--line)}
  .rt-zaman div:last-child{border-right:0}
  .rt-zaman .material-icons-outlined{font-size:15px}
  .rt-zaman b{color:var(--ink);font-weight:700}
  .rt-btnler{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
  .rt-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 13px;border-radius:10px;font-weight:700;font-size:12.5px;text-decoration:none;border:1px solid var(--line);color:var(--ink);background:var(--card)}
  .rt-btn.ana{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .rt-btn .material-icons-outlined{font-size:17px}
  /* Yan panel */
  .rt-yan{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
  .rt-kutu{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px}
  .rt-kutu h4{margin:0 0 10px;font-size:13px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:6px}
  .rt-kutu h4 .material-icons-outlined{font-size:17px}
  .rt-sat{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--ink);margin-bottom:8px}
  .rt-sat .ad{flex:0 0 110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .rt-sat .cb{flex:1;height:8px;background:var(--card2);border-radius:6px;overflow:hidden}
  .rt-sat .cb i{display:block;height:100%;border-radius:6px}
  .rt-sat .n{font-weight:800;min-width:20px;text-align:right}
  .rt-yok{font-size:12px;color:var(--sub)}
  .rt-adim{display:flex;gap:10px;margin-bottom:10px;font-size:12.5px;color:var(--ink);line-height:1.45}
  .rt-adim .s{flex:none;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:#fff}
  .rt-adim small{display:block;color:var(--sub);font-size:11.5px}
  /* Boş durum */
  .rt-bos{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:34px 24px;text-align:center}
  .rt-bos .ik{width:64px;height:64px;border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,#10B981 14%,transparent)}
  .rt-bos .ik .material-icons-outlined{font-size:34px;color:#10B981}
  .rt-bos h3{margin:0 0 6px;font-size:16px;color:var(--ink)}
  .rt-bos p{margin:0 auto;font-size:13px;color:var(--sub);max-width:520px;line-height:1.5}
  .rt-bos .rt-btnler{justify-content:center;margin-top:16px}
  .rt-iki{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:18px;text-align:left}
  .rt-iki div{--c:#F43F5E;border-radius:12px;padding:12px 14px;background:color-mix(in srgb,var(--c) 7%,var(--card));border:1px solid color-mix(in srgb,var(--c) 22%,transparent);font-size:12.5px;color:var(--ink);line-height:1.45}
  .rt-iki div b{display:block;margin-bottom:3px;color:var(--c)}
  @media (max-width:1000px){.rt-duzen{grid-template-columns:1fr}.rt-yan{position:static}.rt-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media (max-width:560px){.rt-iki{grid-template-columns:1fr}.rt-kb .sag{margin-left:0;width:100%}}
</style>
<div class="rk-body">
<div id="rt">
  <div class="rt-hero">
    <h2><span class="material-icons-outlined">block</span> Reddedilen Talepler</h2>
    <p>Geri çevrilen her satın alma talebi burada <b>sebebiyle birlikte</b> kalıcı olarak saklanır: kim istedi, ne istedi, kim reddetti, neden. "İstedim ama gelmedi" tartışmasının cevabı bu sayfada.</p>
    <div class="rt-kpi" id="rtKpi"></div>
  </div>

  <div class="rt-arac">
    <div class="rt-sek" id="rtSek"></div>
    <div class="rt-ara"><span class="material-icons-outlined">search</span><input id="rtAra" placeholder="Ürün, departman, kişi veya sebep ara…"></div>
    <select class="rt-sel" id="rtDep"></select>
    <select class="rt-sel" id="rtDonem">
      <option value="0">Tüm zamanlar</option>
      <option value="7">Son 7 gün</option>
      <option value="30">Son 30 gün</option>
      <option value="90">Son 3 ay</option>
    </select>
  </div>

  <div class="rt-duzen">
    <div id="rtListe"></div>
    <div class="rt-yan">
      <div class="rt-kutu"><h4><span class="material-icons-outlined" style="color:#F43F5E">inventory_2</span> En çok geri çevrilen ürünler</h4><div id="rtUrunTop"></div></div>
      <div class="rt-kutu"><h4><span class="material-icons-outlined" style="color:#7C3AED">groups</span> Departmanlara göre</h4><div id="rtDepTop"></div></div>
      <div class="rt-kutu">
        <h4><span class="material-icons-outlined" style="color:#2563EB">help_outline</span> Bir talep nasıl reddedilir?</h4>
        <div class="rt-adim"><span class="s" style="background:#7C3AED">1</span><div>Personel talep açar<small>Aşçı, barmen vb. ihtiyacını yazar.</small></div></div>
        <div class="rt-adim"><span class="s" style="background:#F43F5E">2</span><div>Departman sorumlusu reddeder<small>Sebep yazmak zorunlu. Talep kapanır ve buraya düşer.</small></div></div>
        <div class="rt-adim"><span class="s" style="background:#F59E0B">3</span><div>Ya da patron fiyatı reddeder<small>Talep silinmez, tekrar fiyat araştırmasına döner; iz burada kalır.</small></div></div>
        <div class="rt-adim" style="margin-bottom:0"><span class="s" style="background:#10B981">4</span><div>Düzelt ve tekrar gönder<small>Talebi açan kişi tek tıkla düzenleyip yeniden yollar.</small></div></div>
      </div>
    </div>
  </div>
</div>
</div>
<script>
(function () {
  const RT = {!! json_encode($kayitlar ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!};
  const rtBenId = {{ (int) ($benId ?? 0) }};
  const rtYonetici = {{ !empty($yonetici) ? 'true' : 'false' }};
  const RENK = ['#7C3AED', '#EA580C', '#0891B2', '#DB2777', '#2563EB', '#CA8A04', '#DC2626', '#0D9488'];
  let rtSekme = 'tum';
  try { rtSekme = sessionStorage.getItem('rtSekme') || 'tum'; } catch (e) {}

  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const el = id => document.getElementById(id);
  const simdi = Math.floor(Date.now() / 1000);

  function sayac(arr, f) { const m = {}; arr.forEach(x => f(x).forEach(k => { if (k) m[k] = (m[k] || 0) + 1; })); return Object.entries(m).sort((a, b) => b[1] - a[1]); }

  function kpiCiz() {
    const ay = RT.filter(r => r.red_ts && simdi - r.red_ts <= 30 * 86400).length;
    const dep = sayac(RT, r => [r.departman])[0];
    const urun = sayac(RT, r => r.kalemler.map(k => k.ad))[0];
    const k = (ik, l, v, a, c, kucuk) => `<div class="rt-k" style="--k:${c}"><div class="l"><span class="material-icons-outlined">${ik}</span>${l}</div><div class="v${kucuk ? ' kucuk' : ''}" title="${esc(v)}">${esc(v)}</div><div class="a">${esc(a)}</div></div>`;
    el('rtKpi').innerHTML =
      k('block', 'Toplam red', RT.length, RT.filter(r => r.tip === 'dept').length + ' departman · ' + RT.filter(r => r.tip === 'fiyat').length + ' patron fiyat', '#F43F5E') +
      k('event', 'Son 30 gün', ay, ay ? 'son ayda geri çevrilen' : 'son ayda red yok 👍', '#2563EB') +
      k('groups', 'En çok red alan', dep ? dep[0] : '—', dep ? dep[1] + ' talep' : 'henüz kayıt yok', '#7C3AED', true) +
      k('inventory_2', 'En çok reddedilen ürün', urun ? urun[0] : '—', urun ? urun[1] + ' kez' : 'henüz kayıt yok', '#D97706', true);
  }

  function filtreli(sekmeYok) {
    const q = (el('rtAra').value || '').toLocaleLowerCase('tr');
    const dep = el('rtDep').value; const gun = +el('rtDonem').value;
    return RT.filter(r => {
      if (!sekmeYok && rtSekme !== 'tum' && r.tip !== rtSekme) return false;
      if (dep && r.departman !== dep) return false;
      if (gun && (!r.red_ts || simdi - r.red_ts > gun * 86400)) return false;
      if (q) {
        const m = [r.departman, r.isteyen_ad, r.reddeden, r.sebep, '#' + r.id, r.kalemler.map(k => k.ad).join(' ')].join(' ').toLocaleLowerCase('tr');
        if (!m.includes(q)) return false;
      }
      return true;
    });
  }

  function sekmeCiz() {
    const b = filtreli(true);
    const s = [['tum', 'Tümü', 'list', b.length], ['dept', 'Departman reddi', 'person_off', b.filter(r => r.tip === 'dept').length], ['fiyat', 'Patron fiyat reddi', 'price_change', b.filter(r => r.tip === 'fiyat').length]];
    el('rtSek').innerHTML = s.map(x => `<button type="button" class="${rtSekme === x[0] ? 'on' : ''}" data-s="${x[0]}"><span class="material-icons-outlined" style="font-size:16px">${x[2]}</span>${x[1]}<span class="n">${x[3]}</span></button>`).join('');
    el('rtSek').querySelectorAll('button').forEach(bt => bt.onclick = () => { rtSekme = bt.dataset.s; try { sessionStorage.setItem('rtSekme', rtSekme); } catch (e) {} ciz(); });
  }

  function kartCiz(r) {
    const fiyat = r.tip === 'fiyat';
    const urunler = r.kalemler.length
      ? r.kalemler.slice(0, 12).map(k => `<span class="rt-ur">${esc(k.ad)}${k.miktar ? `<b>${esc(k.miktar)}</b>` : ''}</span>`).join('') + (r.kalemler.length > 12 ? `<span class="rt-ur">+${r.kalemler.length - 12} ürün</span>` : '')
      : '<span class="rt-ur">Ürün bilgisi yok</span>';
    const zaman = [
      `<div><span class="material-icons-outlined">person</span>İsteyen: <b>${esc(r.isteyen_ad)}</b></div>`,
      r.talep_tarih ? `<div><span class="material-icons-outlined">schedule</span>Talep: <b>${esc(r.talep_tarih)}</b></div>` : '',
      `<div><span class="material-icons-outlined">gavel</span>${fiyat ? 'Fiyatı reddeden' : 'Reddeden'}: <b>${esc(r.reddeden)}</b>${r.red_tarih ? ' · ' + esc(r.red_tarih) : ''}</div>`,
      r.sure ? `<div><span class="material-icons-outlined">timer</span>Karar süresi: <b>${esc(r.sure)}</b></div>` : ''
    ].join('');
    const kendi = r.isteyen_id === rtBenId || rtYonetici;
    const btn = (!fiyat && kendi ? `<a class="rt-btn ana" href="/satinalma-talep?kopya=${r.id}"><span class="material-icons-outlined">edit_note</span>Düzenle ve tekrar gönder</a>` : '') +
      `<a class="rt-btn" href="/satinalma-talep?ac=${r.id}"><span class="material-icons-outlined">open_in_new</span>Talebi aç</a>` +
      (fiyat ? `<a class="rt-btn" href="/gelen-teklifler"><span class="material-icons-outlined">mark_email_read</span>Gelen tekliflere bak</a>` : '');
    return `<div class="rt-kart${fiyat ? ' fiyat' : ''}">
      <div class="rt-kb">
        <span class="rt-tip">${fiyat ? '💰 Patron fiyat reddi' : '🚫 Departman reddi'}</span>
        <span class="rt-dep">${esc(r.departman)}</span><span class="rt-no">Talep #${r.id}</span>
        ${r.acil ? '<span class="rt-badge rt-acil">🔴 ACİL</span>' : ''}
        ${fiyat ? `<span class="rt-badge rt-dur">Şu an: ${esc(r.durum)}</span>` : ''}
        <span class="sag">${r.kalemler.length} ürün</span>
      </div>
      <div class="rt-ic">
        <div class="rt-urunler">${urunler}</div>
        <div class="rt-sebep"><span class="material-icons-outlined">${fiyat ? 'price_change' : 'block'}</span><span><b>${fiyat ? 'Neden:' : 'Red sebebi:'}</b> ${esc(r.sebep || '—')}</span></div>
        <div class="rt-zaman">${zaman}</div>
        <div class="rt-btnler">${btn}</div>
      </div>
    </div>`;
  }

  function bosCiz(filtreVar) {
    if (filtreVar) return `<div class="rt-bos"><div class="ik" style="background:color-mix(in srgb,#64748B 14%,transparent)"><span class="material-icons-outlined" style="color:#64748B">search_off</span></div><h3>Bu filtreye uyan kayıt yok</h3><p>Aramayı, departmanı veya dönemi değiştirip tekrar dene.</p><div class="rt-btnler"><a class="rt-btn" href="#" id="rtTemizle"><span class="material-icons-outlined">filter_alt_off</span>Filtreleri temizle</a></div></div>`;
    return `<div class="rt-bos">
      <div class="ik"><span class="material-icons-outlined">verified</span></div>
      <h3>Henüz reddedilen talep yok 🎉</h3>
      <p>Şu ana kadar hiçbir satın alma talebi geri çevrilmedi. Bir talep reddedildiğinde burada sebebiyle, isteyenle ve reddedenle birlikte görünür.</p>
      <div class="rt-iki">
        <div><b>🚫 Departman reddi</b>Sorumlu, ekibinden gelen talebi "stokta var", "gereksiz" gibi bir sebeple reddeder. Talep kapanır.</div>
        <div style="--c:#D97706"><b>💰 Patron fiyat reddi</b>Patron gelen fiyatları pahalı bulur. Talep silinmez, tekrar fiyat araştırmasına döner.</div>
      </div>
      <div class="rt-btnler">
        <a class="rt-btn ana" href="/satinalma-talep"><span class="material-icons-outlined">assignment</span>Satın Alma Taleplerine git</a>
        <a class="rt-btn" href="/gelen-teklifler"><span class="material-icons-outlined">mark_email_read</span>Gelen Teklifler</a>
      </div>
    </div>`;
  }

  function cubukCiz(id, liste, bosMetin) {
    const top = liste.slice(0, 6); const max = top.length ? top[0][1] : 1;
    el(id).innerHTML = top.length
      ? top.map((x, i) => `<div class="rt-sat"><span class="ad" title="${esc(x[0])}">${esc(x[0])}</span><span class="cb"><i style="width:${Math.max(8, x[1] / max * 100)}%;background:${RENK[i % RENK.length]}"></i></span><span class="n">${x[1]}</span></div>`).join('')
      : `<div class="rt-yok">${bosMetin}</div>`;
  }

  function ciz() {
    sekmeCiz();
    const l = filtreli(false);
    const filtreVar = !!(el('rtAra').value || el('rtDep').value || +el('rtDonem').value || rtSekme !== 'tum');
    el('rtListe').innerHTML = l.length ? l.map(kartCiz).join('') : bosCiz(RT.length > 0 && filtreVar);
    const t = el('rtTemizle');
    if (t) t.onclick = e => { e.preventDefault(); el('rtAra').value = ''; el('rtDep').value = ''; el('rtDonem').value = '0'; rtSekme = 'tum'; try { sessionStorage.setItem('rtSekme', 'tum'); } catch (x) {} ciz(); };
    const yan = filtreli(true);
    cubukCiz('rtUrunTop', sayac(yan, r => r.kalemler.map(k => k.ad)), 'Henüz reddedilen ürün yok.');
    cubukCiz('rtDepTop', sayac(yan, r => [r.departman]), 'Henüz departman kaydı yok.');
  }

  const deps = [...new Set(RT.map(r => r.departman))].sort((a, b) => a.localeCompare(b, 'tr'));
  el('rtDep').innerHTML = '<option value="">Tüm departmanlar</option>' + deps.map(d => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
  el('rtAra').addEventListener('input', ciz);
  el('rtDep').addEventListener('change', ciz);
  el('rtDonem').addEventListener('change', ciz);
  kpiCiz();
  ciz();
})();
</script>
@endsection

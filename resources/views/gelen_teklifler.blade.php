@extends('layout.app')
@section('rk_ikon', 'mark_email_read')
@section('baslik', 'Gelen Teklifler')
@section('title', 'Gelen Teklifler')

@push('head')
<style>
#gt{--gt-yesil:#10B981;--gt-amber:#F59E0B;--gt-mor:#7C3AED;--gt-mavi:#4F46E5;--gt-kirmizi:#EF4444}
#gt .gt-ust{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:14px}
#gt .gt-ust h2{margin:0;font-size:17px;font-weight:800;color:var(--ink)}
#gt .gt-ust p{margin:3px 0 0;font-size:12.5px;color:var(--sub);max-width:640px;line-height:1.5}
#gt .gt-aks{display:flex;gap:8px;flex-wrap:wrap}
#gt .gt-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:10px;padding:9px 14px;font-weight:700;font-size:13px;cursor:pointer;text-decoration:none;color:#fff;background:var(--gt-mor);white-space:nowrap;font-family:inherit}
#gt .gt-btn .material-icons-outlined{font-size:18px}
#gt .gt-btn.ikincil{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#gt .gt-btn.yesil{background:var(--gt-yesil)}
#gt .gt-btn.sm{padding:6px 10px;font-size:12px;border-radius:8px}
#gt .gt-btn.sm .material-icons-outlined{font-size:15px}
#gt .gt-btn:disabled{opacity:.55;cursor:default}
#gt .gt-nasil{display:none;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 16px;margin-bottom:14px}
#gt .gt-nasil.acik{display:block}
#gt .gt-adimlar{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
#gt .gt-adim{background:var(--card2);border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.5;color:var(--ink)}
#gt .gt-adim b.n{display:inline-flex;min-width:20px;height:20px;border-radius:10px;padding:0 4px;background:var(--gt-mor);color:#fff;font-size:11px;align-items:center;justify-content:center;margin-right:6px}
#gt .gt-adim.ai{outline:2px solid rgba(124,58,237,.35)}
#gt .gt-filtre{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
#gt .gt-sekme{border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:20px;padding:6px 14px;font-weight:700;font-size:12.5px;cursor:pointer;font-family:inherit}
#gt .gt-sekme.on{background:var(--gt-mor);border-color:var(--gt-mor);color:#fff}
#gt .gt-sekme span{opacity:.75;margin-left:4px}
#gt .gt-ara{margin-left:auto;border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:10px;padding:8px 12px;font-size:13px;min-width:220px;font-family:inherit}
#gt .gt-grup{background:var(--card);border:1px solid var(--line);border-radius:16px;margin-bottom:14px;overflow:hidden}
#gt .gt-gbas{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:14px 16px;flex-wrap:wrap;border-bottom:1px solid var(--line)}
#gt .gt-gad{font-weight:800;font-size:15px;color:var(--ink);display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#gt .gt-meta{font-size:12px;color:var(--sub);margin-top:4px;display:flex;gap:10px;flex-wrap:wrap}
#gt .gt-cip{font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;white-space:nowrap}
#gt .gt-cip.etk{background:rgba(236,72,153,.12);color:#be185d}
#gt .gt-cip.dur{background:rgba(79,70,229,.12);color:var(--gt-mavi)}
#gt .gt-cip.ok{background:rgba(16,185,129,.14);color:#047857}
#gt .gt-cip.bek{background:rgba(245,158,11,.16);color:#92400e}
#gt .gt-ilerle{height:6px;border-radius:6px;background:var(--card2);overflow:hidden;width:140px}
#gt .gt-ilerle i{display:block;height:100%;background:var(--gt-yesil)}
#gt .gt-gsag{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
#gt .gt-enIyi{text-align:right;font-size:11.5px;color:var(--sub)}
#gt .gt-enIyi b{display:block;font-size:16px;color:var(--ink)}
#gt .gt-tablo-kap{overflow-x:auto}
#gt table.gt-m{border-collapse:collapse;width:100%;font-size:13px;min-width:480px}
#gt .gt-m th,#gt .gt-m td{padding:9px 12px;border-bottom:1px solid var(--line);text-align:right;vertical-align:middle}
#gt .gt-m th:first-child,#gt .gt-m td:first-child{text-align:left;position:sticky;left:0;background:var(--card);z-index:1;min-width:140px}
#gt .gt-m thead th{background:var(--card2);font-size:12px;vertical-align:top}
#gt .gt-m thead th:first-child{background:var(--card2);color:var(--sub)}
#gt .gt-tbas{cursor:pointer;display:inline-block;text-align:right;border-radius:8px;padding:2px 6px}
#gt .gt-tbas:hover{background:rgba(124,58,237,.10)}
#gt .gt-tbas .ad{font-weight:800;color:var(--ink);font-size:13px;text-decoration:underline dotted rgba(124,58,237,.5);text-underline-offset:3px}
#gt .gt-tbas .alt{font-weight:600;color:var(--sub);font-size:11px;margin-top:3px}
#gt .gt-urun{font-weight:700;color:var(--ink)}
#gt .gt-urun small{display:block;font-weight:600;color:var(--sub);font-size:11.5px}
#gt .gt-h{cursor:pointer}
#gt .gt-h:hover{background:rgba(124,58,237,.06)}
#gt .gt-h .f{font-weight:800;color:var(--ink)}
#gt .gt-h .t{font-size:11px;color:var(--sub)}
#gt .gt-h.ucuz{background:rgba(16,185,129,.10)}
#gt .gt-h.ucuz .f{color:#047857}
#gt .gt-h .rz{display:inline-block;font-size:9.5px;font-weight:800;background:var(--gt-yesil);color:#fff;border-radius:6px;padding:1px 5px;margin-left:5px;vertical-align:middle}
#gt .gt-h.sec{box-shadow:inset 0 0 0 2px var(--gt-mor)}
#gt .gt-h.yok{color:var(--sub);font-size:12px;cursor:default}
#gt tfoot td{font-weight:800;background:var(--card2);border-bottom:0}
#gt tfoot td small{display:block;font-weight:600;color:var(--sub);font-size:11px}
#gt .gt-galt{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding:12px 16px}
#gt .gt-bekleyen{font-size:12.5px;color:var(--sub);display:flex;gap:8px;align-items:center;flex-wrap:wrap}
#gt .gt-eslesmeyen{margin:12px 16px 0;padding:10px 12px;border-radius:10px;background:rgba(245,158,11,.10);font-size:12.5px;color:var(--ink)}
#gt .gt-bos{background:var(--card);border:1px dashed var(--line);border-radius:16px;padding:34px;text-align:center;color:var(--sub)}
#gt .gt-bos .material-icons-outlined{font-size:42px}
#gt .gt-yeni{position:sticky;top:8px;z-index:20;display:none;margin-bottom:12px;background:var(--gt-yesil);color:#fff;border-radius:12px;padding:10px 14px;font-weight:700;cursor:pointer;text-align:center}
#gt details.gt-test{margin-top:18px;font-size:12.5px;color:var(--sub)}
#gt details.gt-test summary{cursor:pointer}
.gt-modal{position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px}
.gt-modal .ic{background:var(--card);color:var(--ink);border-radius:16px;width:100%;max-width:560px;max-height:88vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.gt-modal .mb{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--line);font-weight:800;font-size:15px}
.gt-modal .mb button{border:0;background:none;color:var(--sub);cursor:pointer;font-size:24px;line-height:1}
.gt-modal .mg{padding:14px 18px;font-size:13px}
.gt-modal .bilgi{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}
.gt-modal .bilgi div{background:var(--card2);border-radius:10px;padding:8px 10px}
.gt-modal .bilgi small{display:block;color:var(--sub);font-size:11px;font-weight:700}
.gt-modal table{width:100%;border-collapse:collapse}
.gt-modal td{padding:8px 4px;border-bottom:1px solid var(--line);vertical-align:top}
.gt-modal .kay{font-size:10.5px;font-weight:800;border-radius:6px;padding:2px 6px;background:var(--card2);color:var(--sub);white-space:nowrap}
.gt-modal .kay.ai{background:rgba(124,58,237,.12);color:#6d28d9}
.gt-modal .ham{font-size:11.5px;color:var(--sub);background:var(--card2);border-radius:8px;padding:6px 8px;margin-top:4px;white-space:pre-line}
.gt-modal .ma{display:flex;gap:8px;flex-wrap:wrap;padding:12px 18px;border-top:1px solid var(--line)}
.gt-modal .gt-btn{display:inline-flex;align-items:center;gap:6px;border:0;border-radius:8px;padding:7px 12px;font-weight:700;font-size:12.5px;cursor:pointer;text-decoration:none;color:#fff;background:#7C3AED;font-family:inherit}
.gt-modal .gt-btn.ikincil{background:var(--card);color:var(--ink);border:1px solid var(--line)}
.gt-modal .gt-btn:disabled{opacity:.55}
#gtToast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:10000;color:#fff;font-weight:700;font-size:13px;padding:10px 16px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.2);display:none;max-width:calc(100% - 32px)}
@media (max-width:760px){
  #gt .gt-adimlar{grid-template-columns:1fr}
  #gt .gt-ara{margin-left:0;min-width:0;width:100%}
  #gt .gt-gsag{width:100%;justify-content:space-between}
  #gt .gt-enIyi{text-align:left}
  .gt-modal .bilgi{grid-template-columns:1fr}
}
</style>
@endpush

@section('content')
<div class="rk-body"><div id="gt">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="gt-yeni" id="gtYeni" onclick="location.reload()">🔔 Yeni teklif geldi — görmek için dokun</div>

  <div class="gt-ust">
    <div>
      <h2>Gelen Teklifler</h2>
      <p>Tedarikçilere gönderilen her fiyat isteği burada <b>kendi talebinin altında</b> durur: kim fiyat verdi, kim bekliyor, hangi ürün kimde en ucuz — tek tabloda.</p>
    </div>
    <div class="gt-aks">
      <button class="gt-btn ikincil" type="button" onclick="document.getElementById('gtNasil').classList.toggle('acik')"><span class="material-icons-outlined">help_outline</span> Nasıl çalışır?</button>
      <form method="POST" action="/gelen-teklifler/oku" style="margin:0" onsubmit="this.querySelector('button').disabled=true;this.querySelector('b').textContent='AI tarıyor…'">
        @csrf<button class="gt-btn ikincil" type="submit" title="Tedarikçinin WhatsApp / e-postaya YAZIYLA verdiği fiyatları AI okur ve ilgili talebe yerleştirir"><span class="material-icons-outlined">auto_awesome</span> <b>Yazılı cevapları AI ile tara</b></button>
      </form>
      <a href="/satinalma-talep" class="gt-btn"><span class="material-icons-outlined">request_quote</span> Taleplere git</a>
    </div>
  </div>

  <div class="gt-nasil" id="gtNasil">
    <div class="gt-adimlar">
      <div class="gt-adim"><b class="n">1</b><b>Fiyat isteği gider.</b> Satın Alma Talepleri'nde bir talebi açıp "Fiyat Araştır" → o ürünleri sağlayan tedarikçilere WhatsApp + e-posta ile kişiye özel teklif linki gider.</div>
      <div class="gt-adim"><b class="n">2</b><b>Tedarikçi formu doldurur.</b> Linkten birim fiyat, teslim tarihi ve vadeyi girer → fiyat <b>anında</b> buraya ve talebin içine düşer, sana bildirim gelir.</div>
      <div class="gt-adim ai"><b class="n">AI</b><b>Yazıyla cevap verirse AI okur.</b> "Tuz 30, peynir 280 TL" gibi bir mesajdan ürün + fiyatı AI çıkarır, RFQ koduyla doğru talebe yazar. WhatsApp anında; e-posta bu sayfa açıldıkça (5 dk'da bir) ya da "AI ile tara" ile okunur.</div>
      <div class="gt-adim"><b class="n">3</b><b>Karşılaştır, karar ver.</b> Her ürünün en ucuzu yeşil. "Talepte aç" → teklifler orada hazır → patron onayına / siparişe gönder.</div>
    </div>
  </div>

  <div class="gt-filtre">
    <button class="gt-sekme on" type="button" data-s="aktif" onclick="gtSekme(this)">Aktif <span id="gtSayA">0</span></button>
    <button class="gt-sekme" type="button" data-s="gecmis" onclick="gtSekme(this)">Geçmiş <span id="gtSayG">0</span></button>
    <input class="gt-ara" id="gtAra" placeholder="Talep no, tedarikçi veya ürün ara…" oninput="gtCiz()">
  </div>

  <div id="gtListe"></div>

  @if ($sahip)
  <details class="gt-test">
    <summary>Test araçları (yalnız yönetici)</summary>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
      <form method="POST" action="/rfq-test-kur" style="margin:0" onsubmit="return rkOnayForm(this, 'Test için örnek bir malzeme + tedarikçi oluşturulsun mu? (sonra silebilirsin)', 'var(--mavi)', 'Evet, kur')">@csrf<button class="gt-btn ikincil sm" type="submit">Test verisi kur</button></form>
      <form method="POST" action="/rfq-test-sifirla" style="margin:0" onsubmit="return rkOnayForm(this, 'TÜM test verileri silinsin mi? (gelen teklifler, RFQ&apos;lar, tekliften oluşan siparişler, TEST tedarikçi/malzeme) — gerçek veriler etkilenmez.', 'var(--kirmizi)', 'Evet, sıfırla')">@csrf<button class="gt-btn ikincil sm" type="submit" style="color:var(--gt-kirmizi)">Test verilerini sıfırla</button></form>
      <a href="/fiyat-arastir-sec" class="gt-btn ikincil sm">Talepsiz fiyat araştır (kritik stok)</a>
    </div>
  </details>
  @endif

  <form method="POST" action="/gelen-teklifler/siparis" id="gtSipForm" style="display:none">@csrf</form>
</div></div>
<div id="gtToast"></div>
@endsection

@push('scripts')
<script>
(function(){
const GT = {!! json_encode($gruplar, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!};
let gtSonId = {{ (int) $sonId }}, gtSekmeAktif = 'aktif', gtToastT = null;
const gtSecim = {}; // talepsiz gruplarda: ürün → seçili tedarikçi
const gtToast = (m, c) => { const t = document.getElementById('gtToast'); t.textContent = m; t.style.background = c || '#10B981'; t.style.display = 'block'; clearTimeout(gtToastT); gtToastT = setTimeout(() => t.style.display = 'none', 4200); };
const para = n => (Number(n) || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ₺';
const mik = n => (Number(n) || 0).toLocaleString('tr-TR', {maximumFractionDigits: 3});
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const tarihD = s => new Date(String(s).replace(' ', 'T'));
const tarih = s => { if (!s) return ''; const d = tarihD(s); if (isNaN(d)) return s; return d.toLocaleDateString('tr-TR', {day:'2-digit', month:'short'}) + (String(s).length > 10 ? ' ' + d.toLocaleTimeString('tr-TR', {hour:'2-digit', minute:'2-digit'}) : ''); };
const once = s => { if (!s) return ''; const dk = Math.round((Date.now() - tarihD(s)) / 60000); if (isNaN(dk)) return ''; if (dk < 1) return 'az önce'; if (dk < 60) return dk + ' dk önce'; if (dk < 1440) return Math.round(dk / 60) + ' sa önce'; return Math.round(dk / 1440) + ' gün önce'; };
const kaynakAd = k => k === 'portal' ? ['Teklif formu', ''] : (k === 'wa' || k === 'whatsapp') ? ['WhatsApp · AI okudu', 'ai'] : ['E-posta · AI okudu', 'ai'];
const kanalAd = k => !k ? 'iletilemedi (link elle gönderilmeli)' : k.split('+').map(x => x === 'wa' ? 'WhatsApp' : x === 'mail' ? 'E-posta' : x).join(' + ');
const durumAd = {yeni:'Yeni', departman_onay:'Departman onayında', teklif:'Teklif toplanıyor', onayda:'Patron onayında', fiyat_onay:'Fiyat onayında', onaylandi:'Onaylandı', siparis:'Siparişe döndü', tamamlandi:'Tamamlandı', reddedildi:'Reddedildi'};

GT.forEach((g, gi) => { g.gi = gi; if (!g.talep_id) { gtSecim[gi] = {}; Object.entries(g.en_ucuz || {}).forEach(([mid, tid]) => { if ((g.hucre[mid] || {})[tid]) gtSecim[gi][mid] = tid; }); } });

window.gtSekme = function(b){ document.querySelectorAll('#gt .gt-sekme').forEach(x => x.classList.toggle('on', x === b)); gtSekmeAktif = b.dataset.s; gtCiz(); };

function grupHtml(g){
  const ted = g.tedarikciler, ur = g.urunler, gi = g.gi;
  const oran = ted.length ? Math.round(100 * g.yanitlayan / ted.length) : 0;
  const etk = g.etkinlik ? `<span class="gt-cip etk">🎉 ${esc(g.etkinlik)}${g.etkinlik_tarih ? ' · ' + tarih(g.etkinlik_tarih) : ''}</span>` : '';
  const dur = g.talep_durum ? `<span class="gt-cip dur">${esc(durumAd[g.talep_durum] || g.talep_durum)}</span>` : '';
  const yan = g.yanitlayan ? `<span class="gt-cip ok">✓ ${g.yanitlayan}/${ted.length} tedarikçi fiyat verdi</span>` : `<span class="gt-cip bek">⏳ ${ted.length} tedarikçiden yanıt bekleniyor</span>`;
  let h = `<div class="gt-grup"><div class="gt-gbas"><div>
    <div class="gt-gad">${esc(g.baslik)} ${etk} ${dur}</div>
    <div class="gt-meta"><span>${ur.length} ürün</span><span>İstek: ${tarih(g.gonderim)}</span>${g.son_yanit ? `<span>Son yanıt: ${once(g.son_yanit)}</span>` : ''}${g.isteyen ? `<span>İsteyen: ${esc(g.isteyen)}</span>` : ''}</div>
    <div style="display:flex;gap:8px;align-items:center;margin-top:7px;flex-wrap:wrap">${yan}<div class="gt-ilerle"><i style="width:${oran}%"></i></div></div>
  </div><div class="gt-gsag">`;
  if (g.en_iyi_toplam > 0) h += `<div class="gt-enIyi">En ucuzlarla toplam<b>${para(g.en_iyi_toplam)}</b></div>`;
  h += g.talep_id
    ? `<a class="gt-btn" href="/satinalma-talep?ac=${g.talep_id}"><span class="material-icons-outlined">open_in_new</span> Talepte aç → onaya gönder</a>`
    : (g.yanitlayan ? `<button class="gt-btn yesil" type="button" onclick="gtSiparis(${gi})"><span class="material-icons-outlined">shopping_cart_checkout</span> Seçilenlerle sipariş ver</button>` : '');
  h += `</div></div>`;
  if (!ted.length || !ur.length) return h + `<div class="gt-galt"><span class="gt-bekleyen">Bu istekte ürün/tedarikçi bilgisi yok.</span></div></div>`;
  h += `<div class="gt-tablo-kap"><table class="gt-m"><thead><tr><th>Ürün · ihtiyaç</th>`;
  ted.forEach((t, ti) => {
    const alt = t.durum === 'geldi' ? `✓ ${t.fiyat_say}/${ur.length} fiyat${t.teslim ? ' · teslim ' + tarih(t.teslim) : ''}` : `⏳ bekleniyor · ${once(t.gonderim)}`;
    h += `<th><span class="gt-tbas" onclick="gtTed(${gi},${ti})" title="Teklif detayı"><div class="ad">${t.anlasmali ? '⭐ ' : ''}${esc(t.ad)}</div><div class="alt">${alt}</div></span></th>`;
  });
  h += `</tr></thead><tbody>`;
  ur.forEach(u => {
    const satir = g.hucre[u.id] || {};
    const cokTeklif = Object.keys(satir).length > 1;
    h += `<tr><td><div class="gt-urun">${esc(u.ad)}<small>${mik(u.miktar)} ${esc(u.birim)}</small></div></td>`;
    ted.forEach((t, ti) => {
      const c = satir[t.id];
      if (!c) { h += `<td class="gt-h yok">${t.durum === 'geldi' ? 'vermedi' : '—'}</td>`; return; }
      const ucuz = String((g.en_ucuz || {})[u.id]) === String(t.id);
      const sec = !g.talep_id && String((gtSecim[gi] || {})[u.id]) === String(t.id);
      const tik = g.talep_id ? `gtTed(${gi},${ti})` : `gtSec(${gi},${u.id},${t.id})`;
      h += `<td class="gt-h ${ucuz ? 'ucuz' : ''} ${sec ? 'sec' : ''}" onclick="${tik}" title="${g.talep_id ? 'Teklif detayı' : 'Bu teklifi seç'}"><span class="f">${para(c.fiyat)}</span>${ucuz && cokTeklif ? '<span class="rz">EN UCUZ</span>' : ''}<div class="t">/${esc(u.birim)} · ${para(c.fiyat * u.miktar)}</div></td>`;
    });
    h += `</tr>`;
  });
  h += `</tbody><tfoot><tr><td>Toplam</td>`;
  ted.forEach(t => { h += `<td>${t.fiyat_say ? para(t.toplam) : '—'}<small>${t.fiyat_say}/${ur.length} ürün</small></td>`; });
  h += `</tr></tfoot></table></div>`;
  if ((g.eslesmeyen || []).length) h += `<div class="gt-eslesmeyen">⚠ AI şu fiyatları okudu ama talepteki bir ürünle eşleştiremedi (elle kontrol et): ${g.eslesmeyen.map(e => `<b>${esc(e.ted)}</b>: ${esc(e.urun)} ${para(e.fiyat)}`).join(' · ')}</div>`;
  const bek = ted.map((t, ti) => [t, ti]).filter(([t]) => t.durum !== 'geldi');
  h += `<div class="gt-galt"><div class="gt-bekleyen">`;
  if (bek.length) {
    h += `<span>Yanıt bekleniyor:</span>`;
    bek.forEach(([t]) => { h += `<b style="color:var(--ink)">${esc(t.ad)}</b>${t.tel ? `<button class="gt-btn ikincil sm" type="button" onclick="gtHatirlat(${t.rfq_id},this)"><span class="material-icons-outlined">notifications_active</span> WhatsApp'tan hatırlat</button>` : ''}${t.link ? `<button class="gt-btn ikincil sm" type="button" onclick="gtKopya(${gi},${t.id})"><span class="material-icons-outlined">link</span> Linki kopyala</button>` : ''}`; });
  } else h += `<span>✓ Sorulan tüm tedarikçiler yanıt verdi.</span>`;
  h += `</div>${!g.talep_id && g.yanitlayan ? '<span class="gt-bekleyen">Fiyata tıkla → o tedarikçiyi seç (en ucuzlar hazır seçili)</span>' : ''}</div></div>`;
  return h;
}

window.gtCiz = function(){
  const q = (document.getElementById('gtAra').value || '').toLocaleLowerCase('tr-TR').trim();
  const eslesir = g => !q || [g.baslik, g.etkinlik, g.talep_id ? '#' + g.talep_id : '', ...g.tedarikciler.map(t => t.ad + ' ' + t.kod), ...g.urunler.map(u => u.ad)].join(' ').toLocaleLowerCase('tr-TR').includes(q);
  const akt = GT.filter(g => !g.gecmis), gec = GT.filter(g => g.gecmis);
  document.getElementById('gtSayA').textContent = akt.length; document.getElementById('gtSayG').textContent = gec.length;
  const liste = (gtSekmeAktif === 'aktif' ? akt : gec).filter(eslesir);
  const el = document.getElementById('gtListe');
  if (!liste.length) {
    el.innerHTML = `<div class="gt-bos"><span class="material-icons-outlined">inbox</span><div style="font-weight:800;color:var(--ink);margin-top:6px">${q ? 'Aramaya uyan teklif yok' : (gtSekmeAktif === 'aktif' ? 'Şu an açık bir fiyat isteği yok' : 'Geçmiş teklif yok')}</div><div style="font-size:12.5px;margin-top:6px">Satın Alma Talepleri'nde bir talebi açıp <b>"Fiyat Araştır"</b>a bas; tedarikçilerin fiyatları buraya düşer.</div></div>`;
    return;
  }
  el.innerHTML = liste.map(grupHtml).join('');
};

window.gtSec = function(gi, mid, tid){ gtSecim[gi][mid] = tid; gtCiz(); };

window.gtSiparis = function(gi){
  const g = GT[gi], ids = [];
  Object.entries(gtSecim[gi] || {}).forEach(([mid, tid]) => { const c = (g.hucre[mid] || {})[tid]; if (c) ids.push(c.id); });
  if (!ids.length) { gtToast('Önce en az bir fiyat seç.', '#F59E0B'); return; }
  const f = document.getElementById('gtSipForm');
  f.querySelectorAll('input[name="teklif[]"]').forEach(x => x.remove());
  ids.forEach(id => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'teklif[]'; i.value = id; f.appendChild(i); });
  rkOnay(ids.length + ' ürün, seçtiğin tedarikçilere göre siparişe dönüşsün ve tedarikçilere WhatsApp + e-posta ile gönderilsin mi?', () => HTMLFormElement.prototype.submit.call(f), 'var(--yesil)', 'Evet, sipariş ver');
};

window.gtTed = function(gi, ti){
  const g = GT[gi], t = g.tedarikciler[ti];
  let satir = '';
  g.urunler.forEach(u => {
    const c = (g.hucre[u.id] || {})[t.id];
    if (!c) { satir += `<tr><td>${esc(u.ad)}<div style="font-size:11px;color:var(--sub)">${mik(u.miktar)} ${esc(u.birim)}</div></td><td style="text-align:right;color:var(--sub)">${t.durum === 'geldi' ? 'fiyat vermedi' : 'bekleniyor'}</td></tr>`; return; }
    const [kAd, kCls] = kaynakAd(c.kaynak);
    const ucuz = String((g.en_ucuz || {})[u.id]) === String(t.id) && Object.keys(g.hucre[u.id] || {}).length > 1;
    satir += `<tr><td>${esc(u.ad)}<div style="font-size:11px;color:var(--sub);margin-top:2px">${mik(u.miktar)} ${esc(u.birim)} · <span class="kay ${kCls}">${kAd}</span> ${once(c.zaman)}</div>${c.metin && c.kaynak !== 'portal' ? `<div class="ham">Tedarikçinin mesajı: ${esc(c.metin)}</div>` : ''}</td><td style="text-align:right;white-space:nowrap"><b style="${ucuz ? 'color:#047857' : ''}">${para(c.fiyat)}</b>/${esc(u.birim)}${ucuz ? ' ✓ en ucuz' : ''}<div style="font-size:11px;color:var(--sub)">${para(c.fiyat * u.miktar)}</div></td></tr>`;
  });
  const m = document.createElement('div'); m.className = 'gt-modal';
  m.innerHTML = `<div class="ic"><div class="mb"><span>${t.anlasmali ? '⭐ ' : ''}${esc(t.ad)} · ${esc(t.kod)}</span><button type="button" data-k aria-label="Kapat">×</button></div><div class="mg">
    <div style="font-size:12px;color:var(--sub);margin:-4px 0 10px">${esc(g.baslik)}${g.etkinlik ? ' · 🎉 ' + esc(g.etkinlik) : ''}</div>
    <div class="bilgi">
      <div><small>Durum</small>${t.durum === 'geldi' ? '✓ Teklif geldi' + (t.yanit ? ' · ' + once(t.yanit) : '') : '⏳ Yanıt bekleniyor'}</div>
      <div><small>İstek gönderildi</small>${tarih(t.gonderim)} · ${esc(kanalAd(t.kanal))}</div>
      <div><small>Teslim tarihi</small>${t.teslim ? tarih(t.teslim) : '—'}</div>
      <div><small>Vade / ödeme</small>${esc(t.vade || '—')}</div>
    </div>
    ${t.not ? `<div class="ham" style="margin:0 0 12px">Tedarikçinin notu: ${esc(t.not)}</div>` : ''}
    <table>${satir}</table>
    <div style="text-align:right;font-weight:800;margin-top:10px">Toplam: ${para(t.toplam)} <span style="font-weight:600;color:var(--sub);font-size:12px">(${t.fiyat_say}/${g.urunler.length} ürün)</span></div>
  </div><div class="ma">
    ${t.link ? `<button class="gt-btn ikincil" type="button" onclick="gtKopya(${gi},${t.id})">🔗 Teklif linkini kopyala</button>` : ''}
    ${t.durum !== 'geldi' && t.tel ? `<button class="gt-btn ikincil" type="button" onclick="gtHatirlat(${t.rfq_id},this)">🔔 WhatsApp'tan hatırlat</button>` : ''}
    ${g.talep_id ? `<a class="gt-btn" href="/satinalma-talep?ac=${g.talep_id}">Talepte aç →</a>` : ''}
  </div></div>`;
  m.addEventListener('click', e => { if (e.target === m || e.target.hasAttribute('data-k')) m.remove(); });
  document.body.appendChild(m);
};

window.gtKopya = function(gi, tid){
  const t = GT[gi].tedarikciler.find(x => x.id === tid); if (!t || !t.link) return;
  (navigator.clipboard ? navigator.clipboard.writeText(t.link) : Promise.reject()).then(() => gtToast('✓ Link kopyalandı — tedarikçiye iletebilirsin')).catch(() => prompt('Linki kopyala:', t.link));
};

window.gtHatirlat = async function(rfq, btn){
  if (btn) btn.disabled = true;
  try {
    const r = await fetch('/gelen-teklifler/hatirlat', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: JSON.stringify({rfq})});
    const j = await r.json();
    if (j.ok) gtToast('✓ Hatırlatma WhatsApp ile gönderildi');
    else { gtToast('⚠ ' + (j.hata || 'Gönderilemedi'), '#D97706'); if (btn) btn.disabled = false; }
  } catch (e) { gtToast('Bağlantı hatası', '#E11D48'); if (btn) btn.disabled = false; }
};

// Canlı: 30 sn'de bir yeni teklif var mı?
setInterval(async () => {
  try { const r = await fetch('/gelen-teklifler/son?_=' + Date.now(), {cache:'no-store'}); const j = await r.json(); if (j.ok && j.son > gtSonId) document.getElementById('gtYeni').style.display = 'block'; } catch (e) {}
}, 30000);

if (!GT.some(g => !g.gecmis) && GT.some(g => g.gecmis)) { gtSekmeAktif = 'gecmis'; document.querySelectorAll('#gt .gt-sekme').forEach(x => x.classList.toggle('on', x.dataset.s === 'gecmis')); }
gtCiz();
})();
</script>
@endpush

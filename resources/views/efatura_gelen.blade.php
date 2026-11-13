@extends('layout.app')
@section('title', 'Gelen e-Faturalar')
@section('rk_ikon', 'move_to_inbox')
@section('baslik', 'Gelen e-Faturalar')
@section('rk_alt', 'Entegratörden otomatik çekim · XML/ZIP/PDF/fotoğraf yükleme · kalem eşleştirme → alış faturası + denetim + Fiyat Radarı')

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $rozet = fn($renk) => 'color:var(' . $renk . ');background:color-mix(in srgb,var(' . $renk . ') 13%,transparent);border-color:color-mix(in srgb,var(' . $renk . ') 40%,transparent)';
  $durumlar = ['bekliyor' => ['Eşleştirme bekliyor', '--amber'], 'aktarildi' => ['Aktarıldı', '--yesil'], 'iade' => ['İade', '--mavi'], 'yok_sayildi' => ['Yok sayıldı', '--sub'], 'tumu' => ['Tümü', '--mor1']];
  $suruHazir = $b && $b->entegrator && ($entegratorler[$b->entegrator][1] ?? false);
@endphp
<style>
  .efg-sekme{display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 12px}
  .efg-sekme a{padding:7px 12px;border-radius:999px;border:1px solid var(--line);font-size:12.5px;font-weight:600;color:var(--ink);text-decoration:none}
  .efg-sekme a.on{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .efg-ust{display:grid;grid-template-columns:1.1fr 1fr;gap:14px;margin-bottom:16px}
  @media (max-width:900px){.efg-ust{grid-template-columns:1fr}}
  .efg-durum{display:flex;align-items:center;gap:8px;font-size:12.5px;margin-top:8px}
  .efg-nokta{width:9px;height:9px;border-radius:50%;flex:none}
  .efg-tablo td{vertical-align:middle}
  .efg-not{font-size:11.5px;color:var(--sub);margin-top:2px;max-width:420px}
</style>

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="efg-ust">
  {{-- Otomatik bağlantı --}}
  <div class="rk-card">
    <div class="rk-bolum" style="margin-bottom:6px"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Entegratör Bağlantısı</span>
      @if ($b && $b->aktif && $suruHazir)<span class="rk-rozet" style="{{ $rozet('--yesil') }}">Otomatik açık</span>@else<span class="rk-rozet" style="{{ $rozet('--sub') }}">Kapalı</span>@endif
    </div>
    <div class="rk-sub2" style="font-size:12.5px">Faturalarınız hangi e-fatura entegratörüne geliyorsa onu seçin. Gelen faturalar 15 dakikada bir otomatik çekilir; OCR ya da AI gerekmez, rakamlar faturadaki gibi birebir gelir.</div>
    @if ($b && ($b->son_sonuc || $b->son_hata))
      <div class="efg-durum"><span class="efg-nokta" style="background:var({{ $b->son_hata ? '--kirmizi' : '--yesil' }})"></span><span>{{ $b->son_hata ?: $b->son_sonuc }}</span></div>
    @endif
    @if ($yonetici)
    <form method="POST" action="/efatura-gelen/baglanti" style="margin-top:10px">
      @csrf
      <div class="rk-grid" style="grid-template-columns:1fr 1fr;gap:10px">
        <div><label class="rk-lbl" style="margin-top:0">Entegratör</label>
          <select name="entegrator" class="rk-inp">
            <option value="">— Seç —</option>
            @foreach ($entegratorler as $kod => $e)<option value="{{ $kod }}" @selected(($b->entegrator ?? '') === $kod)>{{ $e[0] }}{{ $e[1] ? '' : ' (yakında · XML yükle)' }}</option>@endforeach
          </select></div>
        <div><label class="rk-lbl" style="margin-top:0">API anahtarı {!! $anahtarVar ? '<span style="color:var(--yesil)">· kayıtlı ✓</span>' : '' !!}</label>
          <input type="password" name="api_anahtar" class="rk-inp" autocomplete="new-password" placeholder="{{ $anahtarVar ? 'Değiştirmek için yeni anahtarı yaz' : 'Entegratör portalından alınır' }}"></div>
        <div><label class="rk-lbl" style="margin-top:0">İlk çekim başlangıcı</label>
          <input type="date" name="baslangic" class="rk-inp" value="{{ $b->baslangic ?? now()->subDays(30)->toDateString() }}"></div>
        <div><label class="rk-lbl" style="margin-top:0">Stok girişi</label>
          <select name="stok_modu" class="rk-inp">
            <option value="" @selected(!($b->stok_modu ?? null))>Otomatik ({{ $stokModu === 'mal_kabul' ? 'mal kabulde' : 'faturadan' }})</option>
            <option value="mal_kabul" @selected(($b->stok_modu ?? null) === 'mal_kabul')>Mal kabulde yapılıyor (fatura yalnız finansal)</option>
            <option value="fatura" @selected(($b->stok_modu ?? null) === 'fatura')>Faturadan stok girişi yap</option>
          </select></div>
      </div>
      <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:10px;font-size:12.5px">
        <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="aktif" value="1" @checked($b->aktif ?? false)> Otomatik çek</label>
        <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="ortam" value="canli" @checked(($b->ortam ?? 'canli') === 'canli')> Canlı</label>
        <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="ortam" value="test" @checked(($b->ortam ?? '') === 'test')> Test</label>
        @if ($anahtarVar)<label style="display:flex;gap:6px;align-items:center;color:var(--kirmizi)"><input type="checkbox" name="anahtar_sil" value="1"> Anahtarı sil</label>@endif
        <button class="rk-btn" style="background:var(--mor1);margin-left:auto" type="submit"><span class="material-icons-outlined" style="font-size:17px;color:#fff">link</span> Kaydet ve test et</button>
      </div>
    </form>
    @else
      <div class="rk-sub2" style="font-size:12px;margin-top:8px">Bağlantı ayarını işletme sahibi ya da müdür yapar.</div>
    @endif
    @if ($b && $b->aktif && $suruHazir)
      <form method="POST" action="/efatura-gelen/cek" style="margin-top:8px">@csrf
        <button class="rk-btn bos" type="submit" style="padding:7px 12px"><span class="material-icons-outlined" style="font-size:16px">sync</span> Şimdi çek</button></form>
    @endif
  </div>

  {{-- Elle yükleme --}}
  <div class="rk-card">
    <div class="rk-bolum" style="margin-bottom:6px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">Fatura Yükle</span><span class="rk-rozet" style="{{ $rozet('--yesil') }}">Ücretsiz</span></div>
    <div class="rk-sub2" style="font-size:12.5px"><b>XML / ZIP</b> (en doğrusu), <b>PDF</b> ya da <b>fotoğraf</b> bırakın. PDF bu bilgisayarda okunur, tablo başlıklarından sütunlar bulunur; çok faturalı tek PDF de olur. Taranmış sayfa ve fotoğraflar cihazda OCR ile okunur.</div>
    <form id="efgYukle" method="POST" action="/efatura-gelen/yukle" enctype="multipart/form-data" style="margin-top:12px">
      @csrf
      <input type="file" id="efgDosya" name="dosyalar[]" multiple accept=".xml,.zip,.pdf,.jpg,.jpeg,.png,application/xml,text/xml,application/zip,application/pdf,image/*" required class="rk-inp" style="padding:8px">
      <button id="efgYukleBtn" class="rk-btn" style="background:var(--mavi);margin-top:10px" type="submit"><span class="material-icons-outlined" style="font-size:17px;color:#fff">upload_file</span> Yükle ve oku</button>
    </form>
    <div id="efgDurum" class="rk-sub2" style="font-size:12.5px;margin-top:8px;display:none"></div>
    <div id="efgAi" style="display:none;margin-top:8px"></div>
    <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Aynı fatura iki kez yüklenirse ETTN'den tanınır, tekrar kaydedilmez. Satırı ya da toplamı tutmayan faturalar otomatik aktarılmaz, kontrol için bekletilir.</div>
  </div>
</div>

<div class="efg-sekme">
  @foreach ($durumlar as $k => $d)
    <a href="/efatura-gelen?durum={{ $k }}" class="{{ $durum === $k ? 'on' : '' }}">{{ $d[0] }} @if($k !== 'tumu')({{ (int) ($say[$k] ?? 0) }})@else({{ (int) $say->sum() }})@endif</a>
  @endforeach
  <form method="GET" action="/efatura-gelen" style="margin-left:auto;display:flex;gap:6px">
    <input type="hidden" name="durum" value="{{ $durum }}">
    <input type="text" name="q" value="{{ $ara }}" class="rk-inp" placeholder="Fatura no / tedarikçi" style="width:200px;padding:7px 10px">
  </form>
  <a href="/efatura-gelen/excel" title="Kalemler, faturalar, fiyat değişimi, zam izi ve denetim bulguları"><span class="material-icons-outlined" style="font-size:15px;vertical-align:-3px">table_view</span> Excel</a>
  <a href="/fatura-denetim"><span class="material-icons-outlined" style="font-size:15px;vertical-align:-3px">fact_check</span> Denetim</a>
</div>

@if ($yonetici && (int) ($say['bekliyor'] ?? 0) >= 3)
  <div class="rk-card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px;padding:12px 14px">
    <span class="material-icons-outlined" style="color:var(--amber)">bolt</span>
    <div style="flex:1;min-width:240px;font-size:12.5px"><b>İlk kurulum mu?</b> Geçmiş faturaları tek tek eşleştirmek yerine, eşleşmeyen tüm ürünleri <b>yeni malzeme kartı</b> olarak açıp hepsini aktarabilirsiniz. Aynı ürün tek kart olur; sonradan gelen faturalar bu kartlarla otomatik eşleşir.</div>
    <form method="POST" action="/efatura-gelen/toplu-yeni" onsubmit="return confirm('{{ (int) ($say['bekliyor'] ?? 0) }} bekleyen faturadaki eşleşmeyen ürünler için yeni malzeme kartları açılacak ve faturalar alış faturasına aktarılacak. Devam edilsin mi?') && (this.querySelector('button').disabled=true, this.querySelector('button').innerText='İşleniyor…', true)">@csrf
      <button class="rk-btn" style="background:var(--amber)" type="submit">Tümünü kartla ve aktar</button></form>
  </div>
@endif

@if ($liste->isEmpty())
  <div class="rk-card" style="text-align:center;padding:36px"><span class="material-icons-outlined" style="font-size:48px;color:var(--line)">move_to_inbox</span>
    <div class="rk-sub" style="margin-top:10px">{{ $say->sum() ? 'Bu filtrede fatura yok.' : 'Henüz gelen e-fatura yok. Entegratörü bağlayın ya da XML/ZIP yükleyin.' }}</div></div>
@else
  <div class="rk-tablo efg-tablo"><table>
    <thead><tr><th>Tarih</th><th>Tedarikçi</th><th>Fatura No</th><th class="sag">Kalem</th><th class="sag">Tutar (KDV dahil)</th><th>Durum</th><th class="sag"></th></tr></thead>
    <tbody>
    @foreach ($liste as $f)
      @php $d = $durumlar[$f->durum] ?? [$f->durum, '--sub']; $ek = (int) ($eksik[$f->id] ?? 0); @endphp
      <tr>
        <td class="rk-sub2">{{ $f->tarih ? \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') : '—' }}</td>
        <td><div style="font-weight:600">{{ $f->satici_ad ?: '—' }}</div>@if ($f->not)<div class="efg-not">{{ $f->not }}</div>@endif</td>
        <td class="rk-sub">{{ $f->fatura_no ?: '—' }}@if ($f->profil === 'TICARIFATURA')<div class="efg-not">Ticari{{ $f->cevap ? ' · ' . ($f->cevap === 'kabul' ? 'kabul edildi' : 'reddedildi') : '' }}</div>@endif</td>
        <td class="sag">{{ (int) ($kalemSay[$f->id] ?? 0) }}@if ($ek && $f->durum === 'bekliyor')<div class="efg-not" style="color:var(--amber)">{{ $ek }} eşleşmedi</div>@endif
          @if (($birimBek[$f->id] ?? 0) && $f->durum === 'bekliyor')<div class="efg-not" style="color:var(--kirmizi)">{{ $birimBek[$f->id] }} birim çevrimi bekliyor</div>@endif</td>
        <td class="sag" style="font-weight:700">{{ $tl($f->genel_toplam) }}@if (!$f->tutarli)<div class="efg-not" style="color:var(--kirmizi)">toplam tutmuyor</div>@endif</td>
        <td><span class="rk-rozet" style="{{ $rozet($d[1]) }}">{{ $d[0] }}</span></td>
        <td class="sag"><a href="/efatura-gelen/{{ $f->id }}" class="rk-btn {{ $f->durum === 'bekliyor' ? '' : 'bos' }}" style="{{ $f->durum === 'bekliyor' ? 'background:var(--amber);' : '' }}padding:7px 12px">{{ $f->durum === 'bekliyor' ? 'Eşleştir' : 'Aç' }}</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @if ($liste->count() >= 300)<div class="rk-sub2" style="font-size:12px;margin-top:8px">İlk 300 fatura gösteriliyor; aramayı kullanın.</div>@endif
@endif

<script>
// PDF / fotoğraf okuma — metin bu tarayıcıda çıkarılır (pdf.js), taranmış sayfa/fotoğraf Tesseract.js ile (ikisi de ücretsiz).
// Sunucuya yalnız [metin, x, y, genişlik] listesi gider; sütunları sunucudaki kural motoru bulur.
(function () {
  var form = document.getElementById('efgYukle'), girdi = document.getElementById('efgDosya'), btn = document.getElementById('efgYukleBtn');
  var durum = document.getElementById('efgDurum'), aiKutu = document.getElementById('efgAi');
  var CSRF = '{{ csrf_token() }}';
  function yaz(t) { durum.style.display = ''; durum.innerHTML = t; }
  function betik(src) { return new Promise(function (ok, red) { var s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = red; document.head.appendChild(s); }); }
  var pdfHazir = null, ocrHazir = null;
  function pdfYukle() {
    if (!pdfHazir) pdfHazir = betik('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js').then(function () {
      window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    });
    return pdfHazir;
  }
  function ocrYukle() {
    if (!ocrHazir) ocrHazir = betik('https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js').then(function () { return window.Tesseract.createWorker('tur'); });
    return ocrHazir;
  }
  // OCR sözcüklerini satır içi öbeklere birleştir; koordinatları A4 genişliğine (595 pt) ölçekle
  function ocrOgeler(sonuc, genislik) {
    var k = 595 / genislik, o = [];
    (sonuc.data.lines || []).forEach(function (ln) {
      var cur = null;
      (ln.words || []).forEach(function (w) {
        var x0 = w.bbox.x0 * k, x1 = w.bbox.x1 * k, y = w.bbox.y1 * k, h = (w.bbox.y1 - w.bbox.y0) * k;
        if (cur && x0 - cur.x1 < Math.max(4, h * 0.6)) { cur.t += ' ' + w.text; cur.x1 = x1; }
        else { if (cur) o.push([cur.t, cur.x0, cur.y, cur.x1 - cur.x0]); cur = { t: w.text, x0: x0, x1: x1, y: y }; }
      });
      if (cur) o.push([cur.t, cur.x0, cur.y, cur.x1 - cur.x0]);
    });
    return o;
  }
  async function kanvasOcr(kanvas) { var w = await ocrYukle(); return ocrOgeler(await w.recognize(kanvas), kanvas.width); }
  async function pdfSayfalar(dosya, bilgi) {
    await pdfYukle();
    var doc = await window.pdfjsLib.getDocument({ data: new Uint8Array(await dosya.arrayBuffer()) }).promise, out = [], ocr = false;
    for (var p = 1; p <= doc.numPages; p++) {
      bilgi(dosya.name + ': sayfa ' + p + ' / ' + doc.numPages);
      var pg = await doc.getPage(p), vp = pg.getViewport({ scale: 1 }), tc = await pg.getTextContent();
      var o = tc.items.filter(function (i) { return i.str.trim() !== ''; }).map(function (i) { return [i.str, Math.round(i.transform[4] * 10) / 10, Math.round((vp.height - i.transform[5]) * 10) / 10, Math.round(i.width * 10) / 10]; });
      if (o.length < 15) { // metni olmayan (taranmış) sayfa → OCR
        bilgi(dosya.name + ': sayfa ' + p + ' taranmış, OCR ile okunuyor…'); ocr = true;
        var v2 = pg.getViewport({ scale: 2.2 }), c = document.createElement('canvas'); c.width = v2.width; c.height = v2.height;
        await pg.render({ canvasContext: c.getContext('2d'), viewport: v2 }).promise;
        o = await kanvasOcr(c);
      }
      out.push({ no: p, o: o });
    }
    return { sayfalar: out, ocr: ocr };
  }
  async function resimSayfa(dosya, no) {
    var img = new Image(), url = URL.createObjectURL(dosya);
    await new Promise(function (ok, red) { img.onload = ok; img.onerror = red; img.src = url; });
    var olc = Math.min(1, 2400 / Math.max(img.width, img.height)), c = document.createElement('canvas');
    c.width = Math.round(img.width * olc); c.height = Math.round(img.height * olc); c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
    URL.revokeObjectURL(url);
    return { no: no, o: await kanvasOcr(c) };
  }
  function gonder(adres, govde) {
    return fetch(adres, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: JSON.stringify(govde) })
      .then(function (r) { return r.json(); });
  }
  function ozet(r) {
    var s = r.yeni + ' yeni fatura · ' + r.aktarilan + ' tanesi otomatik aktarıldı';
    if (r.var) s += ' · ' + r.var + ' zaten kayıtlıydı';
    if (r.kontrol) s += ' · <b style="color:var(--amber)">' + r.kontrol + ' tanesi kontrol bekliyor</b>';
    if (r.hatalar && r.hatalar.length) s += '<br><span style="color:var(--kirmizi)">' + r.hatalar.slice(0, 3).join(' | ') + '</span>';
    return s;
  }
  form.addEventListener('submit', async function (e) {
    var dosyalar = Array.prototype.slice.call(girdi.files || []);
    var okunacak = dosyalar.filter(function (f) { return /\.(pdf|jpe?g|png)$/i.test(f.name) || /^image\//.test(f.type) || f.type === 'application/pdf'; });
    if (!okunacak.length) { btn.disabled = true; btn.innerText = 'Okunuyor…'; return; } // yalnız XML/ZIP: klasik gönderim
    e.preventDefault(); btn.disabled = true; aiKutu.style.display = 'none';
    var xmller = dosyalar.filter(function (f) { return okunacak.indexOf(f) < 0; }), toplam = { yeni: 0, aktarilan: 0, var: 0, kontrol: 0, hatalar: [] }, cozulemeyen = [], aiKalan = 0;
    try {
      if (xmller.length) { // XML/ZIP'ler normal yükleme ucuna
        yaz('XML/ZIP dosyaları yükleniyor…');
        var fd = new FormData(); fd.append('_token', CSRF); xmller.forEach(function (f) { fd.append('dosyalar[]', f); });
        await fetch('/efatura-gelen/yukle', { method: 'POST', body: fd });
      }
      var resimler = [], no = 0;
      for (var i = 0; i < okunacak.length; i++) {
        var f = okunacak[i];
        if (/\.pdf$/i.test(f.name) || f.type === 'application/pdf') {
          var p = await pdfSayfalar(f, yaz);
          for (var b = 0; b < p.sayfalar.length; b += 300) {
            yaz(f.name + ': faturalar çözümleniyor…');
            var parca = p.sayfalar.slice(b, b + 300), r = await gonder('/efatura-gelen/pdf-oku', { sayfalar: parca, kaynak: p.ocr ? 'ocr' : 'pdf' });
            if (!r.ok) throw new Error(r.hata || 'okunamadı');
            ['yeni', 'aktarilan', 'var', 'kontrol'].forEach(function (k) { toplam[k] += r[k] || 0; }); toplam.hatalar = toplam.hatalar.concat(r.hatalar || []);
            (r.cozulemeyen || []).forEach(function (n) { cozulemeyen.push(parca.filter(function (s) { return s.no === n; })[0]); });
            aiKalan = r.ai_kalan;
          }
        } else { yaz(f.name + ': fotoğraf OCR ile okunuyor…'); resimler.push(await resimSayfa(f, ++no)); }
      }
      for (var j = 0; j < resimler.length; j++) { // her fotoğraf ayrı fatura
        var r2 = await gonder('/efatura-gelen/pdf-oku', { sayfalar: [resimler[j]], kaynak: 'ocr' });
        if (r2.ok) { ['yeni', 'aktarilan', 'var', 'kontrol'].forEach(function (k) { toplam[k] += r2[k] || 0; }); toplam.hatalar = toplam.hatalar.concat(r2.hatalar || []); if (r2.cozulemeyen && r2.cozulemeyen.length) cozulemeyen.push(resimler[j]); aiKalan = r2.ai_kalan; }
      }
      yaz('✓ ' + ozet(toplam) + ' · <a href="/efatura-gelen">listeyi yenile</a>');
      if (cozulemeyen.length) aiTeklif(cozulemeyen.filter(Boolean), aiKalan);
      else if (!xmller.length || toplam.yeni) setTimeout(function () { location.href = '/efatura-gelen'; }, 2500);
    } catch (err) { yaz('<span style="color:var(--kirmizi)">Okunamadı: ' + (err.message || err) + '</span>'); }
    btn.disabled = false;
  });
  function aiTeklif(sayfalar, kalan) {
    aiKutu.style.display = '';
    if (!kalan) { aiKutu.innerHTML = '<div class="rk-sub2" style="font-size:12px;color:var(--amber)">' + sayfalar.length + ' sayfa otomatik okunamadı. Bu faturaların XML\'ini yüklemeniz en doğrusu.</div>'; return; }
    aiKutu.innerHTML = '<div class="rk-sub2" style="font-size:12px;color:var(--amber)">' + sayfalar.length + ' sayfanın düzeni tanınmadı.</div>'
      + '<button type="button" class="rk-btn bos" style="padding:7px 12px;margin-top:6px" id="efgAiBtn"><span class="material-icons-outlined" style="font-size:16px">auto_awesome</span> Yapay zekâ ile oku (günlük kalan hak: ' + kalan + ' sayfa)</button>'
      + '<div class="rk-sub2" style="font-size:11px;margin-top:4px">Tedarikçinin düzeni öğrenilir; sonraki faturaları yine ücretsiz okunur.</div>';
    document.getElementById('efgAiBtn').onclick = async function () {
      this.disabled = true; var t = { yeni: 0, aktarilan: 0, var: 0, kontrol: 0, hatalar: [] };
      for (var i = 0; i < sayfalar.length; i += 6) {
        yaz('Yapay zekâ okuyor… (' + Math.min(i + 6, sayfalar.length) + ' / ' + sayfalar.length + ' sayfa)');
        var r = await gonder('/efatura-gelen/pdf-ai', { sayfalar: sayfalar.slice(i, i + 6) });
        if (!r.ok) { yaz('<span style="color:var(--kirmizi)">' + r.hata + '</span>'); return; }
        ['yeni', 'aktarilan', 'var', 'kontrol'].forEach(function (k) { t[k] += r[k] || 0; }); t.hatalar = t.hatalar.concat(r.hatalar || []);
      }
      yaz('✓ Yapay zekâ: ' + ozet(t) + ' · <a href="/efatura-gelen">listeyi yenile</a>'); aiKutu.style.display = 'none';
    };
  }
})();
</script>
@endsection

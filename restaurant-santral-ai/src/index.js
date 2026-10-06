'use strict';
// KRITIK: Bazi sunucularda IPv6 ADRESI tanimli ama YOLU kirik. Google (STT gRPC + TTS REST)
// hostname'i once AAAA (IPv6) cozup ona baglanmaya calisir -> 'EHOSTUNREACH 2001:4860:...'
// -> ses Google'a gider ama cevap GELMEZ (STT sessiz) ve TTS hata verir (AI sessiz kalir).
// Cozum: tum DNS cozumlemesinde IPv4'u ONCE dene. (Node 18+; dns.lookup kullanan axios dahil.)
require('dns').setDefaultResultOrder('ipv4first');

// ResteOS AI Santral kopru — giris.
// Asterisk (ARI, Asterisk 16/17) -> externalMedia (RTP) <-> bu kopru <-> Google STT/TTS + Laravel beyin.
//
// Akis:
//  1) Dialplan (TEK satir, restoran basina bir kez): exten => s,1,Stasis(restaurant-santral-ai)
//  2) StasisStart -> kanali cevapla, mixing bridge kur
//  3) externalMedia kanali olustur (RTP'yi bu koprunun portuna yollar), bridge'e ekle
//  4) CagriOturumu: RTP<->STT<->beyin<->TTS
//  5) aksiyon 'aktar' -> hedef(ler)i PANELDEN oku, ARI originate ile ARA (hepsi/sirali), ayni bridge'de BIRLESTIR
//     (dialplan'e GEREK YOK -> her restoranda ekstra Asterisk ayari yok = otomasyon)
//  6) hangup -> temizle (tum bacaklar)
const ariClient = require('ari-client');
const cfg = require('./config');
const log = require('./log');
const brain = require('./brain');
const { CagriOturumu } = require('./session');

let client = null;

// --- RTP port havuzu (cift portlar; RTP gelenegi) ---
const kullanilan = new Set();
function portAl() {
  for (let i = 0; i < cfg.rtp.portCount; i++) {
    const p = cfg.rtp.portBase + i * 2;
    if (!kullanilan.has(p)) { kullanilan.add(p); return p; }
  }
  return null;
}
function portBirak(p) { if (p) kullanilan.delete(p); }

// --- SES KAYDI (ARI bridge recording) — hata olsa cagri bozulmaz ---
async function sesKayitBasla(kayit, channelId, tur) {
  if (!cfg.recording.aktif || !kayit || !kayit.bridge) return;
  try {
    const safe = String(channelId).replace(/[^a-zA-Z0-9]/g, '_');
    const ad = `santral_${safe}_${tur}`;
    await kayit.bridge.record({ name: ad, format: 'wav', ifExists: 'overwrite', beep: false, terminateOn: 'none', maxDurationSeconds: 3600, maxSilenceSeconds: 0 });
    kayit.rec = { ad, tur };
    log.info(`ses kaydi basladi (${tur}): ${ad}`);
  } catch (e) { log.warn('ses kaydi baslamadi:', e.message); }
}

async function sesKayitBitir(kayit) {
  if (!cfg.recording.aktif) return;
  if (!kayit || !kayit.rec) { log.debug('sesKayitBitir: aktif kayit yok'); return; }
  const { ad, tur } = kayit.rec;
  kayit.rec = null;
  log.info(`ses kaydi bitiriliyor (${tur}): ${ad}`);
  // recordings.stop HANG'a karsi 3sn timeout (bazi durumlarda cozulmeyebilir -> yuklemeyi bloklamasin)
  try {
    await Promise.race([
      client.recordings.stop({ recordingName: ad }),
      new Promise((_, rej) => setTimeout(() => rej(new Error('stop timeout')), 3000)),
    ]);
    log.debug('kayit durduruldu');
  } catch (e) { log.debug('kayit stop atlandi: ' + e.message); }
  await new Promise((r) => setTimeout(r, 1000)); // dosya finalize olsun
  const filePath = `${cfg.recording.dir}/${ad}.wav`;
  const oturumId = kayit.oturum ? kayit.oturum.oturumId : null;
  await brain.sesYukle(oturumId, kayit.subeId, tur, filePath);
}

// callerKanalId -> {oturum,bridge,extChan,port,telefon,subeId,aktarmaModu,hedefAdaylar:Set,hedefBagli,hedefChanId,aktarSira}
const aktif = new Map();
const extMediaKanallari = new Set();
const geriBasarili = new Set();     // callee acip oturum baslayan geri-arama id'leri (cevapsiz yanlis raporlanmasin)
let _geriDongudeMi = false;

async function main() {
  if (!cfg.laravel.baseUrl) { log.error('LARAVEL_BASE_URL bos — .env ayarlayin'); process.exit(1); }

  const sttKey = process.env.GOOGLE_APPLICATION_CREDENTIALS;
  if (!sttKey) log.warn('GOOGLE_APPLICATION_CREDENTIALS bos — STT (kulak) calismaz, musteri duyulmaz');
  else if (!require('fs').existsSync(sttKey)) log.warn(`STT kimlik dosyasi YOK: ${sttKey} — STT calismaz`);
  if (!cfg.tts.apiKey) log.warn('GOOGLE_TTS_API_KEY bos — TTS (agiz) calismaz, AI sessiz kalir');
  log.info(`SURUM: 2026-10-06d (terk edilen cagri kurtarma/otomatik geri arama: ${cfg.geriArama.aktif ? 'ACIK' : 'KAPALI'}; ARI keepalive; ambiyans ducking; ofis ambiyansi: ${cfg.ambiyans.aktif ? 'ACIK sev=' + cfg.ambiyans.seviye + ' dinleme=' + cfg.ambiyans.dinleme : 'KAPALI'}; StasisEnd temizle; bargeIn=${cfg.bargeIn ? 'ACIK' : 'KAPALI(!)'} kayit=${cfg.recording.aktif ? 'ACIK' : 'KAPALI'})`);
  log.info(`Ayar: format=${cfg.mediaFormat} bargeIn=${cfg.bargeIn ? 'acik(tam-dupleks)' : 'kapali(yari-dupleks)'} model=${cfg.stt.model} sube=${cfg.laravel.defaultSubeId}`);

  log.info(`ARI baglantisi: ${cfg.ari.url} (app=${cfg.ari.app})`);
  client = await ariClient.connect(cfg.ari.url, cfg.ari.user, cfg.ari.pass);
  ariHandlerKur(client);

  process.on('SIGINT', () => { log.info('kapaniyor…'); process.exit(0); });
  process.on('SIGTERM', () => process.exit(0));

  await client.start(cfg.ari.app);
  log.info('AI Santral kopru hazir. Cagri bekleniyor.');

  // BAGLANTIYI SICAK TUT + koptuysa yeniden baglan: idle'da WS bayatlayip ILK CAGRIYI
  // dusurmesini onler ("ilk arama kapaniyor, ikinci aciliyor" sorunu). 25sn'de bir saglik kontrolu.
  setInterval(ariSaglikKontrol, 25000);

  // TERK EDILEN CAGRI KURTARMA: kuyrugu periyodik yokla -> musteriyi otomatik geri ara.
  if (cfg.geriArama.aktif && cfg.geriArama.dial) {
    setInterval(geriAramaDongu, Math.max(10, cfg.geriArama.pollSn) * 1000);
    log.info(`Geri arama AKTIF (dial=${cfg.geriArama.dial}, poll=${cfg.geriArama.pollSn}sn, timeout=${cfg.geriArama.timeout}sn)`);
  } else {
    log.info('Geri arama KAPALI (GERI_ARAMA=1 + GERI_ARAMA_DIAL ile acilir).');
  }
}

// StasisStart/End + ChannelDestroyed handlerlarini bir client'a bagla (yeniden baglanmada tekrar kullanilir).
function ariHandlerKur(c) {
  c.on('StasisStart', async (event, channel) => {
    if (extMediaKanallari.has(channel.id) || /^UnicastRTP/.test(channel.name || '')) {
      log.debug(`externalMedia bacagi StasisStart, atlaniyor: ${channel.name}`);
      return;
    }
    if (event.args && event.args[0] === 'aktarma') {
      await aktarmaHedefiCevapladi(channel, event.args[1]);
      return;
    }
    // GERI ARAMA: disari aradigimiz musteri ACTI -> yarida kalan oturumla devam.
    if (event.args && event.args[0] === 'geri_arama') {
      const geriId = parseInt(event.args[1], 10) || 0;
      const geriAramaOturum = parseInt(event.args[2], 10) || 0;
      const num = event.args[3] || null;
      await cagriBasla(channel, event, { geriAramaId: geriId, geriAramaOturum, telefon: num });
      return;
    }
    await cagriBasla(channel, event);
  });
  c.on('ChannelDestroyed', async (event, channel) => { await kanalDustu(channel.id); });
  // Kanal Stasis'ten cikinca (hangup) StasisEnd gelir; ChannelDestroyed ULASMAYABILIR -> ikisini de dinle.
  c.on('StasisEnd', async (event, channel) => { log.debug(`StasisEnd: ${channel.id}`); await kanalDustu(channel.id); });
  c.on('WebSocketError', (err) => log.warn('ARI WS hata:', err && err.message));
}

let _ariYenileniyor = false;
async function ariSaglikKontrol() {
  try {
    await client.asterisk.getInfo(); // hafif ping: baglanti canli mi + WS'i sicak tutar
  } catch (e) {
    if (_ariYenileniyor) return;
    _ariYenileniyor = true;
    log.warn('ARI baglantisi kopuk olabilir, yeniden baglaniliyor:', e && e.message);
    try {
      client = await ariClient.connect(cfg.ari.url, cfg.ari.user, cfg.ari.pass);
      ariHandlerKur(client);
      await client.start(cfg.ari.app);
      log.info('ARI yeniden baglandi (kopru tekrar hazir).');
    } catch (e2) {
      log.error('ARI yeniden baglanamadi:', e2 && e2.message);
    }
    _ariYenileniyor = false;
  }
}

// ============================ GERI ARAMA (terk edilen cagri kurtarma) ============================
function geriNumFormat(tel) {
  const d = String(tel || '').replace(/\D/g, '');
  const son10 = d.slice(-10);
  return (cfg.geriArama.prefix || '') + son10;
}
async function geriAramaDongu() {
  if (!cfg.geriArama.aktif || !cfg.geriArama.dial) return;
  if (_geriDongudeMi || _ariYenileniyor) return;
  _geriDongudeMi = true;
  try {
    const r = await brain.geriAramaBekleyen();
    for (const row of ((r && r.liste) || [])) { await originateGeriArama(row); }
  } catch (e) { log.debug('geri arama dongu hatasi:', e.message); }
  _geriDongudeMi = false;
}
async function originateGeriArama(row) {
  const num = geriNumFormat(row.telefon);
  if (!num || num.length < 6) { await brain.geriAramaDurum(row.id, 'cevapsiz'); return; }
  const dial = cfg.geriArama.dial.replace('{num}', num);
  await brain.geriAramaDurum(row.id, 'araniyor');
  log.info(`GERI ARAMA baslatiliyor: ${row.telefon} -> ${dial} (id=${row.id}, oturum=${row.oturum_id})`);
  try {
    const ch = client.Channel();
    await ch.originate({
      endpoint: dial,
      app: cfg.ari.app,
      appArgs: `geri_arama,${row.id},${row.oturum_id},${num}`,
      callerId: cfg.geriArama.callerId || num,
      timeout: cfg.geriArama.timeout,
    });
  } catch (e) {
    log.warn('geri arama originate hatasi:', e.message);
    await brain.geriAramaDurum(row.id, 'cevapsiz');
    return;
  }
  // CEVAPSIZ yakalama: timeout+10sn icinde "basarili" raporlanmadiysa cevapsiz say.
  setTimeout(async () => {
    if (!geriBasarili.has(row.id)) { await brain.geriAramaDurum(row.id, 'cevapsiz'); }
    else { geriBasarili.delete(row.id); }
  }, (cfg.geriArama.timeout + 10) * 1000);
}

async function cagriBasla(channel, event, opts = {}) {
  const telefon = opts.telefon || (channel.caller && channel.caller.number ? channel.caller.number : null);
  let subeId = opts.subeId || cfg.laravel.defaultSubeId;
  const arg = (event.args || []).find((a) => /^SUBE=/i.test(a));
  if (arg) subeId = parseInt(arg.split('=')[1], 10) || subeId;

  const port = portAl();
  if (port === null) {
    log.error('Bos RTP portu yok — cagri reddediliyor');
    try { await channel.hangup(); } catch (_) {}
    return;
  }

  log.info(`${opts.geriAramaId ? 'GERI ARAMA (musteriye)' : 'Yeni cagri'}: kanal=${channel.id} tel=${telefon || '-'} sube=${subeId} rtpPort=${port}`);

  try {
    await channel.answer();

    const bridge = client.Bridge();
    await bridge.create({ type: 'mixing' });

    const extChan = client.Channel();
    await extChan.externalMedia({
      app: cfg.ari.app,
      external_host: `${cfg.rtp.host}:${port}`,
      format: cfg.mediaFormat === 'slin16' ? 'slin16' : 'ulaw',
    });
    extMediaKanallari.add(extChan.id);

    await bridge.addChannel({ channel: [channel.id, extChan.id] });

    const oturum = new CagriOturumu({
      kanalId: channel.id,
      telefon,
      subeId,
      rtpPort: port,
      geriAramaOturum: opts.geriAramaOturum || 0, // >0: yarida kalan oturumla devam
      onAktar: (kid) => insanaAktar(kid, subeId),
      onBitir: (kid) => { client.Channel(kid).hangup().catch(() => {}); },
    });

    const kayit = {
      oturum, bridge, extChan, port, telefon, subeId,
      aktarmaModu: false, hedefAdaylar: null, hedefBagli: false, hedefChanId: null, aktarSira: null,
      rec: null,
    };
    aktif.set(channel.id, kayit);
    // SES KAYDI (AI fazi): bridge'i kaydet (musteri + AI sesi). Hata olsa cagri bozulmaz.
    await sesKayitBasla(kayit, channel.id, 'ai');
    await oturum.basla();
    // GERI ARAMA basarili: callee acti + oturum basladi -> kuyruga bildir.
    if (opts.geriAramaId) { geriBasarili.add(opts.geriAramaId); brain.geriAramaDurum(opts.geriAramaId, 'basarili'); }
  } catch (e) {
    log.error('cagriBasla hatasi:', e.message);
    portBirak(port);
    try { await channel.hangup(); } catch (_) {}
  }
}

// "insana aktar" (dialplan'SIZ, coklu hedef + strateji)
async function insanaAktar(kanalId, subeId) {
  const kayit = aktif.get(kanalId);
  if (!kayit) return;
  log.info(`insana aktar: kanal=${kanalId}`);

  const h = await brain.aktarmaHedef(subeId);
  const hedefler = (h && Array.isArray(h.hedefler) && h.hedefler.length) ? h.hedefler : (h && h.dial ? [h.dial] : []);
  if (!h || !h.aktif || !hedefler.length) {
    log.warn(`aktarma hedefi panelde tanimli degil (sube ${subeId}) -> cagri kapaniyor. /santral-ayar'dan ayarlayin.`);
    try { await client.Channel(kanalId).hangup(); } catch (_) {}
    await temizle(kanalId, 'aktar');
    return;
  }

  // AI fazi ses kaydini kapat+yukle (aktarma oncesi)
  await sesKayitBitir(kayit);

  // AI ses bacagini + oturumu kapat AMA bridge + arayani KORU (insan devralacak)
  kayit.aktarmaModu = true;
  try { if (kayit.extChan) await kayit.extChan.hangup(); } catch (_) {}
  try { if (kayit.oturum) await kayit.oturum.kapat('aktar'); } catch (_) {}
  portBirak(kayit.port); kayit.port = null;

  kayit.hedefAdaylar = new Set();
  kayit.hedefBagli = false;
  kayit.hedefChanId = null;
  const zil = h.zil || 30;

  if (h.strateji === 'sirali') {
    kayit.aktarSira = { list: hedefler, idx: 0, zil };
    log.info(`aktarma (sirali): ${hedefler.join(' -> ')} (her biri ${zil}s)`);
    await siraliDenemesi(kanalId);
  } else {
    log.info(`aktarma (hepsi ayni anda): ${hedefler.join(', ')} (zil ${zil}s)`);
    for (const dial of hedefler) await originateHedef(kanalId, dial, zil);
  }
}

// Tek bir hedefi ara (aday olarak izle)
async function originateHedef(callerId, dial, zil) {
  const kayit = aktif.get(callerId);
  if (!kayit || kayit.hedefBagli) return;
  try {
    const hedefChan = client.Channel();
    kayit.hedefAdaylar.add(hedefChan.id);
    await hedefChan.originate({
      endpoint: dial,                    // or. SIP/101 veya SIP/trunk/05xx (panelden)
      app: cfg.ari.app,
      appArgs: `aktarma,${callerId}`,
      timeout: zil,
      callerId: kayit.telefon || undefined,
    });
  } catch (e) {
    log.warn(`originate hatasi (${dial}):`, e.message);
    // aday olusmadi; sirali ise sonrakine gecmek icin kanalDustu tetiklenmez -> burada dene
    if (kayit.aktarSira) await siraliDenemesi(callerId);
  }
}

// Sirali strateji: siradaki hedefi dene; liste biterse arayani kapat
async function siraliDenemesi(callerId) {
  const kayit = aktif.get(callerId);
  if (!kayit || kayit.hedefBagli) return;
  const s = kayit.aktarSira;
  if (!s || s.idx >= s.list.length) {
    log.info('sirali aktarma: kimse acmadi -> arayan kapaniyor');
    try { await client.Channel(callerId).hangup(); } catch (_) {}
    await temizle(callerId, 'aktar');
    return;
  }
  const dial = s.list[s.idx++];
  log.info(`sirali deneme ${s.idx}/${s.list.length}: ${dial} (${s.zil}s)`);
  await originateHedef(callerId, dial, s.zil);
}

// Hedef (dahili/cep) cevapladi -> arayanla birlestir; hepsi modunda digerlerini iptal et
async function aktarmaHedefiCevapladi(hedefChan, callerId) {
  const kayit = aktif.get(callerId);
  if (!kayit || !kayit.bridge) {
    log.warn('aktarma hedefi cevapladi ama arayan kaydi yok -> hedef kapaniyor');
    try { await hedefChan.hangup(); } catch (_) {}
    return;
  }
  if (kayit.hedefBagli) { // baska hedef daha once acti -> bu fazlalik
    try { await hedefChan.hangup(); } catch (_) {}
    return;
  }
  kayit.hedefBagli = true;
  kayit.hedefChanId = hedefChan.id;
  log.info(`aktarma hedefi cevapladi -> ${callerId} ile birlestiriliyor`);
  try { await kayit.bridge.addChannel({ channel: hedefChan.id }); }
  catch (e) { log.warn('bridge birlestirme hatasi:', e.message); }

  // Diger ringing adaylari iptal et (ring group kaybedenleri)
  for (const cid of Array.from(kayit.hedefAdaylar)) {
    if (cid !== hedefChan.id) { try { await client.Channel(cid).hangup(); } catch (_) {} }
  }
  kayit.hedefAdaylar = new Set([hedefChan.id]);

  // AKTARMA fazi ses kaydini baslat (musteri + yetkili)
  await sesKayitBasla(kayit, callerId, 'aktarma');
}

// Herhangi bir kanal yok oldu (cevapsiz/kapandi)
async function kanalDustu(chanId) {
  // 1) Aktarma aday bacagi mi?
  for (const [cid, k] of aktif) {
    if (k.hedefAdaylar && k.hedefAdaylar.has(chanId)) {
      k.hedefAdaylar.delete(chanId);
      if (k.hedefBagli) {
        // Bagli olan hedef mi dustu? -> gorusme bitti, arayani kapat
        if (chanId === k.hedefChanId) {
          log.info(`aktarma hedefi (bagli) kapandi -> arayan ${cid} kapaniyor`);
          try { await client.Channel(cid).hangup(); } catch (_) {}
          await temizle(cid, 'aktar');
        }
        return; // bagli degilse: iptal edilen kaybeden aday -> yok say
      }
      // Henuz kimse baglanmadi (cevapsiz/mesgul)
      if (k.aktarSira) { await siraliDenemesi(cid); }        // sirali -> sonraki
      else if (k.hedefAdaylar.size === 0) {                   // hepsi -> hepsi dustu
        log.info(`aktarma: kimse acmadi -> arayan ${cid} kapaniyor`);
        try { await client.Channel(cid).hangup(); } catch (_) {}
        await temizle(cid, 'aktar');
      }
      return;
    }
  }
  // 2) Arayan mi kapandi?
  if (aktif.has(chanId)) await temizle(chanId, 'kapandi');
}

async function temizle(kanalId, sonuc) {
  const kayit = aktif.get(kanalId);
  if (!kayit) { log.debug(`temizle: kayit yok (${kanalId})`); return; }
  log.info(`temizle: kanal=${kanalId} sonuc=${sonuc} rec=${kayit.rec ? kayit.rec.ad : 'YOK'}`);
  aktif.delete(kanalId);
  // Aktif ses kaydini kapat+yukle (bridge destroy'dan ONCE; ai veya aktarma fazi)
  await sesKayitBitir(kayit);
  if (kayit.hedefAdaylar) {
    for (const cid of kayit.hedefAdaylar) { try { await client.Channel(cid).hangup(); } catch (_) {} }
  }
  try { if (kayit.oturum) await kayit.oturum.kapat(sonuc); } catch (_) {}
  try { if (kayit.bridge) await kayit.bridge.destroy(); } catch (_) {}
  portBirak(kayit.port);
}

main().catch((e) => { log.error('KRITIK:', e.message); process.exit(1); });

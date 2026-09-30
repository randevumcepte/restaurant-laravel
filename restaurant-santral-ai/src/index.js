'use strict';
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

// callerKanalId -> {oturum,bridge,extChan,port,telefon,subeId,aktarmaModu,hedefAdaylar:Set,hedefBagli,hedefChanId,aktarSira}
const aktif = new Map();
const extMediaKanallari = new Set();

async function main() {
  if (!cfg.laravel.baseUrl) { log.error('LARAVEL_BASE_URL bos — .env ayarlayin'); process.exit(1); }

  const sttKey = process.env.GOOGLE_APPLICATION_CREDENTIALS;
  if (!sttKey) log.warn('GOOGLE_APPLICATION_CREDENTIALS bos — STT (kulak) calismaz, musteri duyulmaz');
  else if (!require('fs').existsSync(sttKey)) log.warn(`STT kimlik dosyasi YOK: ${sttKey} — STT calismaz`);
  if (!cfg.tts.apiKey) log.warn('GOOGLE_TTS_API_KEY bos — TTS (agiz) calismaz, AI sessiz kalir');
  log.info(`SURUM: 2026-09-30f (coklu hedef + strateji hepsi/sirali, ARI aktarma dialplan'siz, panelden yonetim, sessizlik ${cfg.sessizlikMs}ms)`);
  log.info(`Ayar: format=${cfg.mediaFormat} bargeIn=${cfg.bargeIn ? 'acik(tam-dupleks)' : 'kapali(yari-dupleks)'} model=${cfg.stt.model} sube=${cfg.laravel.defaultSubeId}`);

  log.info(`ARI baglantisi: ${cfg.ari.url} (app=${cfg.ari.app})`);
  client = await ariClient.connect(cfg.ari.url, cfg.ari.user, cfg.ari.pass);

  client.on('StasisStart', async (event, channel) => {
    if (extMediaKanallari.has(channel.id) || /^UnicastRTP/.test(channel.name || '')) {
      log.debug(`externalMedia bacagi StasisStart, atlaniyor: ${channel.name}`);
      return;
    }
    if (event.args && event.args[0] === 'aktarma') {
      await aktarmaHedefiCevapladi(channel, event.args[1]);
      return;
    }
    await cagriBasla(channel, event);
  });

  client.on('ChannelDestroyed', async (event, channel) => {
    await kanalDustu(channel.id);
  });

  process.on('SIGINT', () => { log.info('kapaniyor…'); process.exit(0); });
  process.on('SIGTERM', () => process.exit(0));

  await client.start(cfg.ari.app);
  log.info('AI Santral kopru hazir. Cagri bekleniyor.');
}

async function cagriBasla(channel, event) {
  const telefon = channel.caller && channel.caller.number ? channel.caller.number : null;
  let subeId = cfg.laravel.defaultSubeId;
  const arg = (event.args || []).find((a) => /^SUBE=/i.test(a));
  if (arg) subeId = parseInt(arg.split('=')[1], 10) || subeId;

  const port = portAl();
  if (port === null) {
    log.error('Bos RTP portu yok — cagri reddediliyor');
    try { await channel.hangup(); } catch (_) {}
    return;
  }

  log.info(`Yeni cagri: kanal=${channel.id} tel=${telefon || '-'} sube=${subeId} rtpPort=${port}`);

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
      onAktar: (kid) => insanaAktar(kid, subeId),
      onBitir: (kid) => { client.Channel(kid).hangup().catch(() => {}); },
    });

    aktif.set(channel.id, {
      oturum, bridge, extChan, port, telefon, subeId,
      aktarmaModu: false, hedefAdaylar: null, hedefBagli: false, hedefChanId: null, aktarSira: null,
    });
    await oturum.basla();
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
  if (!kayit) return;
  aktif.delete(kanalId);
  if (kayit.hedefAdaylar) {
    for (const cid of kayit.hedefAdaylar) { try { await client.Channel(cid).hangup(); } catch (_) {} }
  }
  try { if (kayit.oturum) await kayit.oturum.kapat(sonuc); } catch (_) {}
  try { if (kayit.bridge) await kayit.bridge.destroy(); } catch (_) {}
  portBirak(kayit.port);
}

main().catch((e) => { log.error('KRITIK:', e.message); process.exit(1); });

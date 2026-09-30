'use strict';
// ResteOS AI Santral kopru — giris.
// Asterisk (ARI, Asterisk 16/17) -> externalMedia (RTP) <-> bu kopru <-> Google STT/TTS + Laravel beyin.
//
// Akis:
//  1) Dialplan (TEK satir, restoran basina bir kez): exten => s,1,Stasis(restaurant-santral-ai)
//  2) StasisStart -> kanali cevapla, mixing bridge kur
//  3) externalMedia kanali olustur (RTP'yi bu koprunun portuna yollar), bridge'e ekle
//  4) CagriOturumu: RTP<->STT<->beyin<->TTS
//  5) aksiyon 'aktar' -> hedefi PANELDEN oku, ARI originate ile ARA, ayni bridge'de BIRLESTIR
//     (dialplan'e GEREK YOK -> her restoranda ekstra Asterisk ayari yok = otomasyon)
//  6) hangup -> temizle (iki bacagi da)
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

// callerKanalId -> {oturum, bridge, extChan, port, telefon, subeId, aktarmaModu, hedefChanId, hedefBagli}
const aktif = new Map();
const extMediaKanallari = new Set(); // externalMedia bacaklarinin StasisStart'ini yok saymak icin

async function main() {
  if (!cfg.laravel.baseUrl) { log.error('LARAVEL_BASE_URL bos — .env ayarlayin'); process.exit(1); }

  const sttKey = process.env.GOOGLE_APPLICATION_CREDENTIALS;
  if (!sttKey) log.warn('GOOGLE_APPLICATION_CREDENTIALS bos — STT (kulak) calismaz, musteri duyulmaz');
  else if (!require('fs').existsSync(sttKey)) log.warn(`STT kimlik dosyasi YOK: ${sttKey} — STT calismaz`);
  if (!cfg.tts.apiKey) log.warn('GOOGLE_TTS_API_KEY bos — TTS (agiz) calismaz, AI sessiz kalir');
  log.info(`SURUM: 2026-09-30e (ARI ile aktarma=dialplan'siz + panelden hedef + siparis->adisyon + kufur + menu zekasi, sessizlik ${cfg.sessizlikMs}ms)`);
  log.info(`Ayar: format=${cfg.mediaFormat} bargeIn=${cfg.bargeIn ? 'acik(tam-dupleks)' : 'kapali(yari-dupleks)'} model=${cfg.stt.model} sube=${cfg.laravel.defaultSubeId}`);

  log.info(`ARI baglantisi: ${cfg.ari.url} (app=${cfg.ari.app})`);
  client = await ariClient.connect(cfg.ari.url, cfg.ari.user, cfg.ari.pass);

  client.on('StasisStart', async (event, channel) => {
    // externalMedia bacagi bize geri girer -> yok say
    if (extMediaKanallari.has(channel.id) || /^UnicastRTP/.test(channel.name || '')) {
      log.debug(`externalMedia bacagi StasisStart, atlaniyor: ${channel.name}`);
      return;
    }
    // Aktarma icin originate ettigimiz HEDEF bacagi cevaplayinca buraya girer (appArgs: aktarma,<callerId>)
    if (event.args && event.args[0] === 'aktarma') {
      await aktarmaHedefiCevapladi(channel, event.args[1]);
      return;
    }
    await cagriBasla(channel, event);
  });

  // Tek teardown sinyali: kanal yok olunca (cevapsiz/kapandi, arayan veya hedef fark etmez)
  client.on('ChannelDestroyed', async (event, channel) => {
    // 1) Bu bir aktarma hedef bacagi mi? -> arayani da kapat
    for (const [cid, k] of aktif) {
      if (k.hedefChanId === channel.id) {
        log.info(`aktarma hedefi dustu (${k.hedefBagli ? 'kapandi' : 'cevapsiz'}) -> arayan ${cid} kapaniyor`);
        try { await client.Channel(cid).hangup(); } catch (_) {}
        await temizle(cid, 'aktar');
        return;
      }
    }
    // 2) Arayan kapandi mi?
    if (aktif.has(channel.id)) await temizle(channel.id, 'kapandi');
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

    aktif.set(channel.id, { oturum, bridge, extChan, port, telefon, subeId, aktarmaModu: false, hedefChanId: null, hedefBagli: false });
    await oturum.basla();
  } catch (e) {
    log.error('cagriBasla hatasi:', e.message);
    portBirak(port);
    try { await channel.hangup(); } catch (_) {}
  }
}

// "insana aktar" (dialplan'SIZ): hedefi panelden oku -> ARI originate ile ara -> ayni bridge'de birlestir.
async function insanaAktar(kanalId, subeId) {
  const kayit = aktif.get(kanalId);
  log.info(`insana aktar: kanal=${kanalId}`);
  if (!kayit) return;

  const hedef = await brain.aktarmaHedef(subeId);
  if (!hedef || !hedef.aktif || !hedef.dial) {
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

  log.info(`aktarma originate: ${hedef.dial} (zil ${hedef.zil || 30}s)`);
  try {
    const hedefChan = client.Channel();
    kayit.hedefChanId = hedefChan.id;
    await hedefChan.originate({
      endpoint: hedef.dial,               // or. SIP/101 veya SIP/trunk/05xx (panelden)
      app: cfg.ari.app,
      appArgs: `aktarma,${kanalId}`,       // cevaplayinca StasisStart'ta bunu yakalarız
      timeout: hedef.zil || 30,
      callerId: kayit.telefon || undefined, // personel arayanin numarasini gorsun
    });
  } catch (e) {
    log.warn('originate hatasi:', e.message);
    try { await client.Channel(kanalId).hangup(); } catch (_) {}
    await temizle(kanalId, 'aktar');
  }
}

// Aktarma hedefi (dahili/cep) cevapladi -> arayanla ayni bridge'e ekle (konusmaya baslasinlar)
async function aktarmaHedefiCevapladi(hedefChan, callerId) {
  const kayit = aktif.get(callerId);
  if (!kayit || !kayit.bridge) {
    log.warn('aktarma hedefi cevapladi ama arayan kaydi yok -> hedef kapaniyor');
    try { await hedefChan.hangup(); } catch (_) {}
    return;
  }
  log.info(`aktarma hedefi cevapladi -> ${callerId} ile birlestiriliyor`);
  kayit.hedefBagli = true;
  try { await kayit.bridge.addChannel({ channel: hedefChan.id }); }
  catch (e) { log.warn('bridge birlestirme hatasi:', e.message); }
}

async function temizle(kanalId, sonuc) {
  const kayit = aktif.get(kanalId);
  if (!kayit) return;
  aktif.delete(kanalId);
  // Aktarma hedef bacagi hala aciksa kapat
  try { if (kayit.hedefChanId) await client.Channel(kayit.hedefChanId).hangup(); } catch (_) {}
  try { if (kayit.oturum) await kayit.oturum.kapat(sonuc); } catch (_) {}
  try { if (kayit.bridge) await kayit.bridge.destroy(); } catch (_) {}
  portBirak(kayit.port);
}

main().catch((e) => { log.error('KRITIK:', e.message); process.exit(1); });

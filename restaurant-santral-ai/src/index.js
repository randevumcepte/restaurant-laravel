'use strict';
// ResteOS AI Santral kopru — giris.
// Asterisk (ARI, Asterisk 16/17) -> externalMedia (RTP) <-> bu kopru <-> Google STT/TTS + Laravel beyin.
//
// Akis:
//  1) Dialplan: exten => s,1,Stasis(restaurant-santral-ai)   (bkz. asterisk/extensions.conf.sample)
//  2) StasisStart -> kanali cevapla, mixing bridge kur
//  3) externalMedia kanali olustur (RTP'yi bu koprunun portuna yollar), bridge'e ekle
//  4) CagriOturumu: RTP<->STT<->beyin<->TTS
//  5) aksiyon 'aktar' -> kanali dialplan'e geri ver (Dial ile gercek dahiliye)
//  6) hangup / veda -> temizle
const ariClient = require('ari-client');
const cfg = require('./config');
const log = require('./log');
const brain = require('./brain');
const { CagriOturumu } = require('./session');

// --- RTP port havuzu (cift portlar; RTP gelenegi) ---
const kullanilan = new Set();
function portAl() {
  for (let i = 0; i < cfg.rtp.portCount; i++) {
    const p = cfg.rtp.portBase + i * 2;
    if (!kullanilan.has(p)) { kullanilan.add(p); return p; }
  }
  return null;
}
function portBirak(p) { kullanilan.delete(p); }

// kanalId -> {oturum, bridge, extChan, port}
const aktif = new Map();
const extMediaKanallari = new Set(); // externalMedia bacaklarinin StasisStart'ini yok saymak icin

async function main() {
  if (!cfg.laravel.baseUrl) { log.error('LARAVEL_BASE_URL bos — .env ayarlayin'); process.exit(1); }

  // Sik takilan iki nokta: erkenden uyar (calismaya devam eder)
  const sttKey = process.env.GOOGLE_APPLICATION_CREDENTIALS;
  if (!sttKey) log.warn('GOOGLE_APPLICATION_CREDENTIALS bos — STT (kulak) calismaz, musteri duyulmaz');
  else if (!require('fs').existsSync(sttKey)) log.warn(`STT kimlik dosyasi YOK: ${sttKey} — STT calismaz`);
  if (!cfg.tts.apiKey) log.warn('GOOGLE_TTS_API_KEY bos — TTS (agiz) calismaz, AI sessiz kalir');
  log.info(`SURUM: 2026-09-29d (panelden aktarma hedefi + siparis->adisyon + kufur kurali + menu zekasi, sessizlik ${cfg.sessizlikMs}ms)`);
  log.info(`Ayar: format=${cfg.mediaFormat} bargeIn=${cfg.bargeIn ? 'acik(tam-dupleks)' : 'kapali(yari-dupleks)'} model=${cfg.stt.model} sube=${cfg.laravel.defaultSubeId}`);

  log.info(`ARI baglantisi: ${cfg.ari.url} (app=${cfg.ari.app})`);
  const client = await ariClient.connect(cfg.ari.url, cfg.ari.user, cfg.ari.pass);

  client.on('StasisStart', async (event, channel) => {
    // externalMedia bacagi bize geri girer -> yok say
    if (extMediaKanallari.has(channel.id) || /^UnicastRTP/.test(channel.name || '')) {
      log.debug(`externalMedia bacagi StasisStart, atlaniyor: ${channel.name}`);
      return;
    }
    await cagriBasla(client, channel, event);
  });

  client.on('StasisEnd', async (event, channel) => {
    await cagriBitir(channel.id, 'kapandi');
  });

  process.on('SIGINT', async () => { log.info('kapaniyor…'); process.exit(0); });
  process.on('SIGTERM', async () => process.exit(0));

  await client.start(cfg.ari.app);
  log.info('AI Santral kopru hazir. Cagri bekleniyor.');
}

async function cagriBasla(client, channel, event) {
  const telefon = channel.caller && channel.caller.number ? channel.caller.number : null;
  // sube_id dialplan'den arg olarak gelebilir: Stasis(restaurant-santral-ai,SUBE=3)
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

    // externalMedia kanali: sesi bu koprunun RTP portuna yollar (ve geri alir)
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
      onAktar: (kid) => insanaAktar(client, kid, subeId),
      onBitir: (kid) => cagriBitir(kid, 'veda'),
    });

    aktif.set(channel.id, { oturum, bridge, extChan, port });
    await oturum.basla();
  } catch (e) {
    log.error('cagriBasla hatasi:', e.message);
    portBirak(port);
    try { await channel.hangup(); } catch (_) {}
  }
}

// "insana aktar": hedefi PANELDEN oku, Asterisk'e degisken olarak gecir, [santral-aktar]'a devret.
// Dialplan tek satir: exten => s,1,Dial(${SANTRAL_HEDEF},${SANTRAL_ZIL}). Hedef hep panelden yonetilir.
async function insanaAktar(client, kanalId, subeId) {
  const kayit = aktif.get(kanalId);
  log.info(`insana aktar: kanal=${kanalId}`);
  try {
    // AI ses bacagini kapat (insan devralacak)
    if (kayit && kayit.extChan) { try { await kayit.extChan.hangup(); } catch (_) {} }

    const hedef = await brain.aktarmaHedef(subeId);
    const ch = client.Channel(kanalId);

    if (!hedef || !hedef.aktif || !hedef.dial) {
      log.warn(`aktarma hedefi panelde tanimli degil (sube ${subeId}) -> cagri kapaniyor. /santral-ayar'dan ayarlayin.`);
      try { await ch.hangup(); } catch (_) {}
      await temizle(kanalId, 'aktar', false);
      return;
    }

    log.info(`aktarma hedefi: ${hedef.dial} (zil ${hedef.zil || 30}s)`);
    // Asterisk kanal degiskenlerini panelden gelen degerlerle set et
    try { await ch.setChannelVar({ variable: 'SANTRAL_HEDEF', value: String(hedef.dial) }); } catch (_) {}
    try { await ch.setChannelVar({ variable: 'SANTRAL_ZIL', value: String(hedef.zil || 30) }); } catch (_) {}
    await ch.continueInDialplan({ context: 'santral-aktar', extension: 's', priority: 1 });
  } catch (e) {
    log.warn('aktarim hatasi:', e.message);
  }
  // Oturumu temizle ama kanali kapatma (insan devam edecek)
  await temizle(kanalId, 'aktar', /*kanaliKapatma*/ true);
}

async function cagriBitir(kanalId, sonuc) {
  const kayit = aktif.get(kanalId);
  if (!kayit) return;
  try {
    const ch = kayit.oturum && kayit.oturum.kanalId;
    // kanal hala aciksa kapat
  } catch (_) {}
  await temizle(kanalId, sonuc, false);
}

async function temizle(kanalId, sonuc, kanaliKapatma) {
  const kayit = aktif.get(kanalId);
  if (!kayit) return;
  aktif.delete(kanalId);
  try { if (kayit.oturum) await kayit.oturum.kapat(sonuc); } catch (_) {}
  try { if (kayit.bridge) await kayit.bridge.destroy(); } catch (_) {}
  portBirak(kayit.port);
}

main().catch((e) => { log.error('KRITIK:', e.message); process.exit(1); });

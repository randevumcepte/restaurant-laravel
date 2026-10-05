'use strict';
// Ham RTP tasima katmani (dgram/UDP). Asterisk externalMedia bu porta RTP gonderir;
// biz de TTS sesini ayni uzak adrese RTP paketleri olarak geri akitiriz.
// Kod baglilik-suz: RTP header'i elle kurulur (12 bayt).
//
// AMBIYANS (comfort noise): cfg.ambiyans.aktif ise cagri boyunca SUREKLI 20ms'lik
// kareler gonderilir; her karede kisik ofis ambiyansi + (varsa) o anki TTS karesi
// KARISTIRILIR. Boylece olu sessizlik kalkar + "insan cagri merkezi" hissi olur.
// Ambiyans KAPALI ise eski davranis birebir korunur (sadece TTS varken gonder).
const dgram = require('dgram');
const cfg = require('./config');
const log = require('./log');
const { Ambiyans, ulawDecode, ulawEncode } = require('./ambiyans');

const FRAME_MS = 20;

class RtpOturumu {
  /**
   * @param {number} port  Bu oturumun dinledigi yerel UDP port
   * @param {(payload:Buffer)=>void} onSes  Gelen RTP payload'i (header ayiklanmis)
   */
  constructor(port, onSes) {
    this.port = port;
    this.onSes = onSes || (() => {});
    this.sock = dgram.createSocket('udp4');
    this.uzak = null;          // {address, port} — ilk gelen paketten ogrenilir
    this.seq = (Math.floor(port) & 0xffff);  // deterministik baslangic (Math.random yok)
    this.timestamp = 0;
    this.ssrc = (port * 2654435761) >>> 0;   // porttan turetilmis sabit SSRC
    this.oynatmaZ = null;
    this.kuyruk = [];          // gonderilecek TTS ses kareleri (Buffer[])
    this.oynatiyor = false;

    // Ambiyans (opsiyonel). slin16=16bit ornek, ulaw=8bit ornek/kare.
    this.ambAktif = !!(cfg.ambiyans && cfg.ambiyans.aktif);
    this.slin = cfg.mediaFormat === 'slin16';
    this.spf = this.slin ? (cfg.audio.bytesPerFrame / 2) : cfg.audio.bytesPerFrame; // ornek/kare
    this.amb = null;
    this.surekliZ = null;
    if (this.ambAktif) {
      try {
        this.amb = new Ambiyans(cfg.audio.sampleRate, cfg.ambiyans.seviye);
        this.amb.yukle(cfg.ambiyans.dosya);
      } catch (e) {
        log.warn('Ambiyans kurulamadi, devre disi:', e.message);
        this.ambAktif = false;
      }
    }

    this._kur();
  }

  _kur() {
    this.sock.on('message', (msg, rinfo) => {
      if (!this.uzak) {
        this.uzak = { address: rinfo.address, port: rinfo.port };
        log.debug(`RTP uzak uc ogrenildi: ${rinfo.address}:${rinfo.port} (yerel ${this.port})`);
        if (this.ambAktif) this._surekliBasla(); // uzak uc belli -> surekli ambiyans akisini baslat
      }
      // RTP header 12 bayt (uzanti yoksa) -> payload'i ayikla
      if (msg.length > 12) this.onSes(msg.slice(12));
    });
    this.sock.on('error', (e) => log.warn('RTP soket hata:', e.message));
    this.sock.bind(this.port, cfg.rtp.host);
  }

  // TTS ses tamponunu kareleyip oynatma kuyruguna koy
  oynat(buf) {
    if (!buf || !buf.length) return;
    const n = cfg.audio.bytesPerFrame;
    for (let i = 0; i < buf.length; i += n) {
      let kare = buf.slice(i, i + n);
      if (kare.length < n) {
        // son kareyi sessizlikle doldur (ulaw sessizlik=0xFF, linear=0x00)
        const dolgu = Buffer.alloc(n - kare.length, this.slin ? 0x00 : 0xff);
        kare = Buffer.concat([kare, dolgu]);
      }
      this.kuyruk.push(kare);
    }
    // Ambiyans acikken surekli zamanlayici kareleri zaten cekiyor; ayrica baslatma.
    if (!this.ambAktif && !this.oynatiyor) this._oynatmaBasla();
  }

  // ---- AMBIYANS ACIK: surekli 20ms akis (ambiyans + varsa TTS karisimi) ----
  _surekliBasla() {
    if (this.surekliZ) return;
    this.surekliZ = setInterval(() => {
      if (!this.uzak) return;
      try {
        const amb = this.amb.kare(this.spf);           // Int16Array (gain uygulanmis)
        const tts = this.kuyruk.shift();               // Buffer | undefined
        this._gonder(this._karistir(amb, tts));
      } catch (e) {
        log.debug('ambiyans kare hatasi:', e.message);
      }
    }, FRAME_MS);
  }

  // amb (Int16Array) + tts (Buffer, cikis formatinda) -> cikis payload Buffer
  _karistir(amb, ttsKare) {
    const spf = this.spf;
    let ttsLin = null;
    if (ttsKare && ttsKare.length) {
      if (this.slin) {
        ttsLin = new Int16Array(spf);
        for (let i = 0; i < spf; i++) ttsLin[i] = ttsKare.readInt16LE(i * 2);
      } else {
        ttsLin = ulawDecode(ttsKare); // Int16Array (len=spf)
      }
    }
    const mix = new Int16Array(spf);
    for (let i = 0; i < spf; i++) {
      let v = amb[i] + (ttsLin ? ttsLin[i] : 0);
      if (v > 32767) v = 32767; else if (v < -32768) v = -32768;
      mix[i] = v;
    }
    if (this.slin) {
      const out = Buffer.alloc(spf * 2);
      for (let i = 0; i < spf; i++) out.writeInt16LE(mix[i], i * 2);
      return out;
    }
    return ulawEncode(mix);
  }

  // ---- AMBIYANS KAPALI: eski davranis (sadece TTS varken gonder) ----
  _oynatmaBasla() {
    if (this.oynatiyor) return;              // cift interval'i onle (yoksa sesler ust uste biner)
    this.oynatiyor = true;
    this.oynatmaZ = setInterval(() => {
      const kare = this.kuyruk.shift();
      if (!kare) { this._oynatmaDur(); return; }  // kuyruk bosaldi -> interval'i KAPAT (sizinti yok)
      this._gonder(kare);
    }, FRAME_MS);
  }

  _oynatmaDur() {
    this.oynatiyor = false;
    if (this.oynatmaZ) { clearInterval(this.oynatmaZ); this.oynatmaZ = null; }
  }

  // Barge-in: musteri konusunca AI sesini aninda kes (ambiyans calmaya devam eder)
  sustur() {
    this.kuyruk.length = 0;
    if (!this.ambAktif) this._oynatmaDur();
  }

  _gonder(payload) {
    if (!this.uzak) return;
    const h = Buffer.alloc(12);
    h[0] = 0x80;                                   // v2, no pad/ext/cc
    h[1] = cfg.audio.rtpPayloadType & 0x7f;        // PT (0=PCMU)
    h.writeUInt16BE(this.seq & 0xffff, 2);
    h.writeUInt32BE(this.timestamp >>> 0, 4);
    h.writeUInt32BE(this.ssrc, 8);
    this.seq = (this.seq + 1) & 0xffff;
    this.timestamp = (this.timestamp + (this.slin ? payload.length / 2 : payload.length)) >>> 0;
    const pkt = Buffer.concat([h, payload]);
    this.sock.send(pkt, this.uzak.port, this.uzak.address, (e) => {
      if (e) log.debug('RTP gonder hatasi:', e.message);
    });
  }

  // "AI su an konusuyor mu?" -> SADECE TTS kuyruguna bak (ambiyans sayilmaz).
  // Barge-in / sessizlik / kapanis-drain bu getiriye guvenir.
  get sesVarMi() { return this.kuyruk.length > 0 || (!this.ambAktif && this.oynatiyor); }

  kapat() {
    this.sustur();
    if (this.surekliZ) { clearInterval(this.surekliZ); this.surekliZ = null; }
    try { this.sock.close(); } catch (_) {}
  }
}

module.exports = { RtpOturumu };

'use strict';
// Ham RTP tasima katmani (dgram/UDP). Asterisk externalMedia bu porta RTP gonderir;
// biz de TTS sesini ayni uzak adrese RTP paketleri olarak geri akitiriz.
// Kod baglilik-suz: RTP header'i elle kurulur (12 bayt).
const dgram = require('dgram');
const cfg = require('./config');
const log = require('./log');

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
    this.kuyruk = [];          // gonderilecek ses kareleri (Buffer[])
    this.oynatiyor = false;
    this._kur();
  }

  _kur() {
    this.sock.on('message', (msg, rinfo) => {
      if (!this.uzak) {
        this.uzak = { address: rinfo.address, port: rinfo.port };
        log.debug(`RTP uzak uc ogrenildi: ${rinfo.address}:${rinfo.port} (yerel ${this.port})`);
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
        const dolgu = Buffer.alloc(n - kare.length, cfg.mediaFormat === 'slin16' ? 0x00 : 0xff);
        kare = Buffer.concat([kare, dolgu]);
      }
      this.kuyruk.push(kare);
    }
    if (!this.oynatiyor) this._oynatmaBasla();
  }

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

  // Barge-in: musteri konusunca AI sesini aninda kes
  sustur() {
    this.kuyruk.length = 0;
    this._oynatmaDur();
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
    this.timestamp = (this.timestamp + (cfg.mediaFormat === 'slin16' ? payload.length / 2 : payload.length)) >>> 0;
    const pkt = Buffer.concat([h, payload]);
    this.sock.send(pkt, this.uzak.port, this.uzak.address, (e) => {
      if (e) log.debug('RTP gonder hatasi:', e.message);
    });
  }

  get sesVarMi() { return this.oynatiyor || this.kuyruk.length > 0; }

  kapat() {
    this.sustur();
    try { this.sock.close(); } catch (_) {}
  }
}

module.exports = { RtpOturumu };

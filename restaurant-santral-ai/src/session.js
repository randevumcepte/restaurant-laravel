'use strict';
// Cagri basina orkestrasyon: RTP(ses) <-> STT(metin) <-> Laravel beyin <-> TTS(ses) <-> RTP
// Barge-in, sira yonetimi ve sessizlik zamanlayicisi burada.
const cfg = require('./config');
const log = require('./log');
const brain = require('./brain');
const tts = require('./tts');
const { SttOturumu } = require('./stt');
const { RtpOturumu } = require('./rtp');

class CagriOturumu {
  constructor({ kanalId, telefon, subeId, rtpPort, onAktar, onBitir }) {
    this.kanalId = kanalId;
    this.telefon = telefon || null;
    this.subeId = subeId || cfg.laravel.defaultSubeId;
    this.onAktar = onAktar || (() => {});   // "insana aktar" -> index.js Asterisk transferi yapar
    this.onBitir = onBitir || (() => {});   // gorusme bitti -> index.js kanali kapatir
    this.oturumId = null;
    this.mesgul = false;         // beyin/tts turu devam ediyor mu
    this.kapali = false;
    this.sonSes = Date.now ? 0 : 0; // Date.now scriptte var; node runtime'da normal calisir
    this.sessizlikZ = null;

    this.rtp = new RtpOturumu(rtpPort, (payload) => this.stt.yaz(payload));
    this.stt = new SttOturumu(
      (final) => this._finalMetin(final),
      (interim) => this._interim(interim),
    );
  }

  async basla() {
    try {
      const d = await brain.baslat(this.subeId, this.telefon, 'santral');
      this.oturumId = d.oturum_id;
      log.info(`Oturum #${this.oturumId} basladi (kanal ${this.kanalId}, tel ${this.telefon || '-'})`);
      await this._seslendir(d.karsilama || 'Merhaba, size nasil yardimci olabilirim?');
    } catch (e) {
      log.error('Beyin baslat hatasi:', e.message);
      await this._seslendir('Sizi yetkiliye baglaniyorum, lutfen hatta kalin.');
      this.onAktar(this.kanalId);
    }
    this._sessizlikSifirla();
  }

  _interim(metin) {
    // Musteri konusmaya basladi -> barge-in: AI'nin sesini kes
    if (cfg.bargeIn && this.rtp.sesVarMi) {
      log.debug('barge-in: AI susturuluyor');
      this.rtp.sustur();
    }
    this._sessizlikSifirla();
  }

  async _finalMetin(metin) {
    if (this.kapali) return;
    log.info(`> musteri: ${metin}`);
    // Ust uste final gelirse sirala (beyin tek tur)
    if (this.mesgul) { this._bekleyen = metin; return; }
    await this._isle(metin);
    while (this._bekleyen && !this.kapali) {
      const m = this._bekleyen; this._bekleyen = null;
      await this._isle(m);
    }
  }

  async _isle(metin) {
    this.mesgul = true;
    try {
      const d = await brain.konus(this.oturumId, metin);
      if (!d || d.ok === false) {
        await this._seslendir('Kusura bakmayin, sizi yetkiliye aktariyorum.');
        this.onAktar(this.kanalId);
        return;
      }
      log.info(`< asistan: ${d.cevap}${d.aksiyon ? '  [aksiyon: ' + d.aksiyon + ']' : ''}`);
      if (d.cevap) await this._seslendir(d.cevap);

      if (d.aksiyon === 'aktar') { this.onAktar(this.kanalId); return; }
      if (d.aksiyon === 'veda' || d.bitir) { await this._kapatSirasi(); return; }
    } catch (e) {
      log.error('Beyin konus hatasi:', e.message);
      await this._seslendir('Bir sorun olustu, sizi yetkiliye bagliyorum.');
      this.onAktar(this.kanalId);
    } finally {
      this.mesgul = false;
      this._sessizlikSifirla();
    }
  }

  async _seslendir(metin) {
    const ses = await tts.seslendir(metin);
    if (ses && ses.length) this.rtp.oynat(ses);
  }

  _sessizlikSifirla() {
    clearTimeout(this.sessizlikZ);
    if (this.kapali) return;
    this.sessizlikZ = setTimeout(async () => {
      if (this.kapali || this.mesgul) return;
      log.debug('sessizlik zaman asimi');
      await this._seslendir('Orada misiniz? Yardimci olabilecegim baska bir sey var mi?');
      // ikinci sessizlikte kapat
      this.sessizlikZ = setTimeout(() => this._kapatSirasi(), cfg.sessizlikMs);
    }, cfg.sessizlikMs);
  }

  async _kapatSirasi() {
    // Son sesin akmasini kisa bir sure bekle, sonra kanali kapat
    setTimeout(() => this.onBitir(this.kanalId), 1500);
  }

  async kapat(sonuc) {
    if (this.kapali) return;
    this.kapali = true;
    clearTimeout(this.sessizlikZ);
    this.stt.kapat();
    this.rtp.kapat();
    if (this.oturumId) await brain.bitir(this.oturumId, sonuc || 'kapandi');
    log.info(`Oturum #${this.oturumId || '-'} kapandi (kanal ${this.kanalId})`);
  }
}

module.exports = { CagriOturumu };

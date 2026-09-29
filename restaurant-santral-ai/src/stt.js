'use strict';
// Google STREAMING Speech-to-Text sarmalayici.
// ONEMLI: streaming, API anahtari DEGIL servis hesabi (GOOGLE_APPLICATION_CREDENTIALS) ister.
// Her cagri icin bir StreamOturumu; ham ses (ulaw/slin16) yazilir, final cumle geri cagrilir.
const speech = require('@google-cloud/speech');
const cfg = require('./config');
const log = require('./log');

const client = new speech.SpeechClient();

// Google streaming tek akista ~305sn siniri koyar -> uzun cagrida akisi periyodik yenile.
const AKIS_YENILEME_MS = 240000;

class SttOturumu {
  /**
   * @param {(metin:string)=>void} onFinal  Cumle tamamlaninca (final transcript)
   * @param {(metin:string)=>void} onInterim Ara sonuc (barge-in tetigi icin)
   */
  constructor(onFinal, onInterim) {
    this.onFinal = onFinal || (() => {});
    this.onInterim = onInterim || (() => {});
    this.stream = null;
    this.kapali = false;
    this.yenilemeZ = null;
    this._baslat();
  }

  _istekConfig() {
    const conf = {
      encoding: cfg.audio.sttEncoding,        // MULAW veya LINEAR16
      sampleRateHertz: cfg.audio.sampleRate,  // 8000 / 16000
      languageCode: cfg.stt.language,         // tr-TR
      enableAutomaticPunctuation: true,
      maxAlternatives: 1,
    };
    // ONEMLI: 'phone_call'/'video' gibi enhanced modeller cogu dilde SADECE en-* destekli.
    // tr-TR ile model gonderirsek API hata verir -> hic sonuc donmez ("AI duymuyor").
    // Bu yuzden modeli yalnizca en-* dillerde ekle; digerlerinde varsayilan modele birak.
    const m = (cfg.stt.model || '').trim();
    if (m && m !== 'default' && m !== 'auto') {
      if (/^en/i.test(cfg.stt.language)) { conf.model = m; conf.useEnhanced = true; }
      else if (!this._modelUyari) {
        this._modelUyari = true;
        log.warn(`STT model '${m}' ${cfg.stt.language} icin ATLANDI (enhanced modeller genelde en-* destekli) -> varsayilan model kullaniliyor`);
      }
    }
    return { config: conf, interimResults: true };
  }

  _baslat() {
    if (this.kapali) return;
    log.debug('STT akisi aciliyor…');
    this.stream = client
      .streamingRecognize(this._istekConfig())
      .on('error', (err) => {
        // OutOfRange = sure/veri siniri -> hemen yenile (normal)
        if (String(err.message || '').includes('OUT_OF_RANGE') || err.code === 11) {
          log.debug('STT akisi yeniden baslatiliyor (sure siniri)');
          this._yenile(0);
          return;
        }
        // Gercek hata (kimlik/model/kota vb.) -> GORUNUR logla, sonsuz sikilmis dongu olmasin diye bekle
        log.warn('STT hata:', err.message);
        this._yenile(1000);
      })
      .on('data', (data) => {
        const r = data.results && data.results[0];
        if (!r || !r.alternatives || !r.alternatives[0]) return;
        const metin = (r.alternatives[0].transcript || '').trim();
        if (!metin) return;
        if (r.isFinal) this.onFinal(metin);
        else this.onInterim(metin);
      });

    clearTimeout(this.yenilemeZ);
    this.yenilemeZ = setTimeout(() => this._yenile(0), AKIS_YENILEME_MS);
  }

  _yenile(bekle) {
    const eski = this.stream;
    this.stream = null;
    if (eski) { try { eski.end(); } catch (_) {} }
    if (this.kapali) return;
    clearTimeout(this.yenilemeZ);
    if (bekle && bekle > 0) this.yenilemeZ = setTimeout(() => this._baslat(), bekle);
    else this._baslat();
  }

  // Asterisk'ten gelen ham ses karesi (RTP payload'i, header'siz)
  yaz(buf) {
    if (this.kapali || !this.stream) return;
    try { this.stream.write(buf); } catch (e) { log.debug('STT yaz hatasi:', e.message); }
  }

  kapat() {
    this.kapali = true;
    clearTimeout(this.yenilemeZ);
    if (this.stream) { try { this.stream.end(); } catch (_) {} this.stream = null; }
  }
}

module.exports = { SttOturumu };

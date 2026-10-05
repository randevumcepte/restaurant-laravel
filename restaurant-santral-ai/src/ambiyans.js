'use strict';
// Ofis ambiyansi (comfort noise): cagri boyunca TTS'in ALTINDA kisik, surekli bir
// arka plan sesi calar -> "insan cagri merkezi" hissi + olu sessizligi maskeler.
// UCRETSIZ: ses motoru degismez; sadece dar-bant arka ses karistirilir.
//
// Kaynak: assets/ofis-ambiyans.sln (ham s16le mono, cfg sampleRate'inde). Dosya yoksa
// sentetik "oda tonu" (yumusak filtreli gurultu) uretilir -> kutudan cikinca da calisir.
// Gercek ofis ugultusu icin: ffmpeg -i ofis.mp3 -ac 1 -ar 8000 -f s16le assets/ofis-ambiyans.sln
const fs = require('fs');
const log = require('./log');

// ---- G.711 mu-law codec (ulaw <-> linear16) ----
const ULAW_DECODE = (() => {
  const t = new Int16Array(256);
  for (let i = 0; i < 256; i++) {
    const u = ~i & 0xff;
    let x = ((u & 0x0f) << 3) + 0x84;
    x <<= (u & 0x70) >> 4;
    t[i] = (u & 0x80) ? (0x84 - x) : (x - 0x84);
  }
  return t;
})();
const ULAW_EXP = (() => {
  // Standart G.711 exp_lut: exponent = floor(log2(i)) (i<2 -> 0), tavan 7.
  const e = new Uint8Array(256);
  for (let i = 0; i < 256; i++) {
    let v = 0, b = i;
    while (b > 1) { v++; b >>= 1; }
    e[i] = v > 7 ? 7 : v;
  }
  return e;
})();
function ulawEncodeSample(s) {
  const BIAS = 0x84, CLIP = 32635;
  let sign = (s >> 8) & 0x80;
  if (sign) s = -s;
  if (s > CLIP) s = CLIP;
  s += BIAS;
  const exp = ULAW_EXP[(s >> 7) & 0xff];
  const man = (s >> (exp + 3)) & 0x0f;
  return (~(sign | (exp << 4) | man)) & 0xff;
}
function ulawDecode(buf) {
  const out = new Int16Array(buf.length);
  for (let i = 0; i < buf.length; i++) out[i] = ULAW_DECODE[buf[i]];
  return out;
}
function ulawEncode(samples) {
  const out = Buffer.alloc(samples.length);
  for (let i = 0; i < samples.length; i++) out[i] = ulawEncodeSample(samples[i] | 0);
  return out;
}

class Ambiyans {
  constructor(sampleRate, seviye) {
    this.sr = sampleRate;
    this.gain = Math.max(0, Math.min(0.5, seviye || 0.12)); // guvenlik: en fazla 0.5
    this.pos = 0;
    this.kaynak = null; // Int16Array (tam olcek)
  }

  yukle(dosya) {
    try {
      if (dosya && fs.existsSync(dosya)) {
        const b = fs.readFileSync(dosya);
        // s16le -> Int16Array. NOT: readFileSync Buffer'i havuzdan gelebilir ve byteOffset
        // 2'nin kati olmayabilir -> new Int16Array(b.buffer, byteOffset) RangeError atar.
        // Guvenli yol: baytlari TEK TEK oku (hizalama sorunu olmaz).
        const len = Math.floor(b.length / 2);
        const arr = new Int16Array(len);
        for (let i = 0; i < len; i++) arr[i] = b.readInt16LE(i * 2);
        this.kaynak = arr;
        log.info(`Ambiyans dosyasi yuklendi: ${dosya} (${(this.kaynak.length / this.sr).toFixed(1)}sn)`);
        return;
      }
    } catch (e) {
      log.warn('Ambiyans dosyasi okunamadi, sentetik oda tonu kullanilacak:', e.message);
    }
    this.kaynak = this._sentetik();
    log.info('Ambiyans: sentetik oda tonu (dosya yok). Gercek ofis sesi icin assets/ofis-ambiyans.sln ekleyin.');
  }

  // ~8sn yumusak filtreli gurultu (oda tonu). Deterministik LCG (Math.random yok).
  _sentetik() {
    const n = this.sr * 8;
    const buf = new Int16Array(n);
    let seed = 1234567;
    const rnd = () => { seed = (seed * 1103515245 + 12345) & 0x7fffffff; return (seed / 0x3fffffff) - 1; };
    let brown = 0, lp = 0;
    for (let i = 0; i < n; i++) {
      const white = rnd();
      brown = (brown + 0.02 * white) * 0.995;        // kahverengi gurultu (dusuk frekans agirlikli)
      lp = lp + 0.08 * (brown - lp);                 // ek alcak-geciren -> dar bant/yumusak
      // hafif "canli" dalgalanma (uzak ugultu hissi)
      const lfo = 0.85 + 0.15 * Math.sin((i / this.sr) * 0.7);
      buf[i] = Math.max(-32000, Math.min(32000, Math.round(lp * 9000 * lfo)));
    }
    return buf;
  }

  // Sonraki n ornegi (gain uygulanmis) dondur; kaynak donguye alinir.
  kare(n) {
    const out = new Int16Array(n);
    const src = this.kaynak;
    if (!src || !src.length) return out;
    for (let i = 0; i < n; i++) {
      out[i] = Math.round(src[this.pos] * this.gain);
      this.pos++;
      if (this.pos >= src.length) this.pos = 0;
    }
    return out;
  }
}

module.exports = { Ambiyans, ulawDecode, ulawEncode };

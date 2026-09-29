'use strict';
// Google Cloud TTS (REST + API anahtari — mevcut GOOGLE_TTS_API_KEY kullanilabilir).
// Cikti: telefon native ses (ulaw@8k) veya slin16@16k — RTP'ye dogrudan paketlenir.
const axios = require('axios');
const cfg = require('./config');
const log = require('./log');

// Basit metin -> ses onbellek (ayni cumle tekrar seslendirilmesin; ozellikle karsilama/sabit yanitlar)
const cache = new Map();
const CACHE_MAX = 200;

// Google TTS ulaw -> MULAW/8000, LINEAR16 -> LINEAR16/16000 (sampleRateHertz zorunlu degil ama netlik icin)
function audioConfig() {
  if (cfg.mediaFormat === 'slin16') {
    return { audioEncoding: 'LINEAR16', sampleRateHertz: 16000, speakingRate: cfg.tts.speakingRate };
  }
  return { audioEncoding: 'MULAW', sampleRateHertz: 8000, speakingRate: cfg.tts.speakingRate };
}

// LINEAR16 cikisinda 44 baytlik WAV header'i atlanmali (RTP ham PCM ister)
function stripWavHeader(buf) {
  if (buf.length > 44 && buf.slice(0, 4).toString('ascii') === 'RIFF') {
    // "data" chunk'ini bul
    const idx = buf.indexOf('data');
    if (idx > 0) return buf.slice(idx + 8);
    return buf.slice(44);
  }
  return buf;
}

async function seslendir(metin) {
  const t = (metin || '').trim();
  if (!t) return Buffer.alloc(0);
  if (cache.has(t)) return cache.get(t);

  if (!cfg.tts.apiKey) {
    log.error('GOOGLE_TTS_API_KEY yok — seslendirilemiyor');
    return Buffer.alloc(0);
  }

  const url = `https://texttospeech.googleapis.com/v1/text:synthesize?key=${cfg.tts.apiKey}`;
  const body = {
    input: { text: t },
    voice: { languageCode: 'tr-TR', name: cfg.tts.voice },
    audioConfig: audioConfig(),
  };

  try {
    const { data } = await axios.post(url, body, { timeout: 12000 });
    let buf = Buffer.from(data.audioContent, 'base64');
    if (cfg.mediaFormat === 'slin16') buf = stripWavHeader(buf);
    // ulaw ciktisi da WAV header'li gelebilir -> ayikla
    else buf = stripWavHeader(buf);

    if (cache.size >= CACHE_MAX) cache.delete(cache.keys().next().value);
    cache.set(t, buf);
    return buf;
  } catch (e) {
    log.error('TTS hatasi:', e.response?.data?.error?.message || e.message);
    return Buffer.alloc(0);
  }
}

module.exports = { seslendir };

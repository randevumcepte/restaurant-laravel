'use strict';
// .env'i HER ZAMAN proje kokunden oku (hangi dizinden calistirilirsa calistirilsin).
// Aksi halde `node /opt/.../src/index.js` /root'tan calisinca .env bulunamaz.
require('dotenv').config({ path: require('path').resolve(__dirname, '..', '.env') });

function bool(v, def) {
  if (v === undefined || v === null || v === '') return def;
  return v === '1' || String(v).toLowerCase() === 'true';
}
function num(v, def) {
  const n = parseInt(v, 10);
  return Number.isFinite(n) ? n : def;
}

const cfg = {
  ari: {
    url: process.env.ARI_URL || 'http://127.0.0.1:8088',
    user: process.env.ARI_USER || 'santral',
    pass: process.env.ARI_PASS || '',
    app: process.env.ARI_APP || 'restaurant-santral-ai',
  },
  rtp: {
    host: process.env.RTP_HOST || '127.0.0.1',
    portBase: num(process.env.RTP_PORT_BASE, 40000),
    portCount: num(process.env.RTP_PORT_COUNT, 100),
  },
  // ulaw = PCMU/8000 (telefon native), slin16 = LINEAR16/16000
  mediaFormat: (process.env.MEDIA_FORMAT || 'ulaw').toLowerCase(),
  laravel: {
    baseUrl: (process.env.LARAVEL_BASE_URL || '').replace(/\/+$/, ''),
    defaultSubeId: num(process.env.DEFAULT_SUBE_ID, 1),
    secret: process.env.SANTRAL_SECRET || '',
  },
  stt: {
    language: process.env.STT_LANGUAGE || 'tr-TR',
    model: process.env.STT_MODEL || 'latest_long', // tr-TR icin uygun; phone_call en-* disi dillerde calismaz
  },
  tts: {
    apiKey: process.env.GOOGLE_TTS_API_KEY || '',
    voice: process.env.TTS_VOICE || 'tr-TR-Wavenet-E',
    speakingRate: parseFloat(process.env.TTS_SPEAKING_RATE || '1.05'),
  },
  bargeIn: bool(process.env.BARGE_IN, true),
  sessizlikMs: num(process.env.SESSIZLIK_MS, 15000),
  logLevel: process.env.LOG_LEVEL || 'info',
  recording: {
    aktif: bool(process.env.KAYIT_AKTIF, true),
    dir: process.env.ASTERISK_RECORDING_DIR || '/var/spool/asterisk/recording',
  },
};

// Ses parametreleri (format'a gore)
cfg.audio = cfg.mediaFormat === 'slin16'
  ? { sampleRate: 16000, sttEncoding: 'LINEAR16', ttsEncoding: 'LINEAR16', rtpPayloadType: 118, bytesPerFrame: 640 } // 20ms @16k @16bit
  : { sampleRate: 8000, sttEncoding: 'MULAW', ttsEncoding: 'MULAW', rtpPayloadType: 0, bytesPerFrame: 160 };        // 20ms @8k ulaw

module.exports = cfg;

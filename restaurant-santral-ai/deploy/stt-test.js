'use strict';
// BAGIMSIZ Google STT testi — Asterisk/RTP OLMADAN.
// Bir WAV dosyasini (kaydedilmis cagri) dogrudan Google streaming STT'ye gonderir.
// Amac: "Google STT + kimlik + ag" bu sunucudan CALISIYOR MU? sorusunu kesin yanitlar.
//
// Kullanim:
//   cd /opt/restaurant-santral-ai
//   node deploy/stt-test.js /var/spool/asterisk/recording/santral_XXXX_ai.wav
//   (dosya vermezsen en yeni santral_*.wav otomatik secilir)

require('dns').setDefaultResultOrder('ipv4first');
const fs = require('fs');
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '..', '.env') });
const speech = require('@google-cloud/speech');

function enYeniKayit() {
  const dir = process.env.ASTERISK_RECORDING_DIR || '/var/spool/asterisk/recording';
  try {
    const f = fs.readdirSync(dir)
      .filter((x) => /^santral_.*\.wav$/.test(x))
      .map((x) => ({ x, t: fs.statSync(path.join(dir, x)).mtimeMs }))
      .sort((a, b) => b.t - a.t);
    return f.length ? path.join(dir, f[0].x) : null;
  } catch (e) { return null; }
}

// Basit WAV cozumleyici: fmt chunk'tan sampleRate/bits/kanal, data chunk'tan PCM.
function wavCoz(buf) {
  if (buf.toString('ascii', 0, 4) !== 'RIFF' || buf.toString('ascii', 8, 12) !== 'WAVE') {
    throw new Error('Gecerli WAV degil');
  }
  let p = 12, fmt = null, data = null;
  while (p + 8 <= buf.length) {
    const id = buf.toString('ascii', p, p + 4);
    const sz = buf.readUInt32LE(p + 4);
    const govde = p + 8;
    if (id === 'fmt ') {
      fmt = {
        audioFormat: buf.readUInt16LE(govde),
        channels: buf.readUInt16LE(govde + 2),
        sampleRate: buf.readUInt32LE(govde + 4),
        bitsPerSample: buf.readUInt16LE(govde + 14),
      };
    } else if (id === 'data') {
      data = buf.slice(govde, govde + sz);
    }
    p = govde + sz + (sz % 2); // chunk'lar cift hizalanir
  }
  if (!fmt || !data) throw new Error('fmt/data chunk bulunamadi');
  return { fmt, data };
}

async function main() {
  const dosya = process.argv[2] || enYeniKayit();
  if (!dosya) { console.error('WAV dosyasi bulunamadi. Yol ver: node deploy/stt-test.js /yol/dosya.wav'); process.exit(1); }
  console.log('Dosya      :', dosya);
  console.log('Kimlik     :', process.env.GOOGLE_APPLICATION_CREDENTIALS || '(BOS!)');
  console.log('Dil        :', process.env.STT_LANGUAGE || 'tr-TR');

  const { fmt, data } = wavCoz(fs.readFileSync(dosya));
  console.log('WAV format :', JSON.stringify(fmt), '| PCM bayt:', data.length,
    `(~${(data.length / (fmt.sampleRate * (fmt.bitsPerSample / 8) * fmt.channels)).toFixed(1)} sn)`);
  if (fmt.audioFormat !== 1) console.warn('UYARI: WAV PCM(1) degil (audioFormat=' + fmt.audioFormat + '); test yaniltici olabilir.');
  if (fmt.channels !== 1) console.warn('UYARI: ' + fmt.channels + ' kanal; Google tek kanal bekler.');

  const client = new speech.SpeechClient();
  console.log('\n--- Google streaming STT basliyor (IPv4 oncelikli) ---');

  let dataSay = 0, final = [];
  const t0 = Date.now();
  const stream = client.streamingRecognize({
    config: {
      encoding: 'LINEAR16',
      sampleRateHertz: fmt.sampleRate,
      languageCode: process.env.STT_LANGUAGE || 'tr-TR',
      enableAutomaticPunctuation: true,
    },
    interimResults: true,
  })
    .on('error', (err) => {
      console.error(`\n>>> STT HATA: code=${err.code} msg=${err.message}${err.details ? ' details=' + err.details : ''}`);
      console.error('    (code=7/16 kimlik/izin, code=14 UNAVAILABLE=ag/IPv6, code=3 config/encoding)');
      process.exit(1);
    })
    .on('data', (d) => {
      dataSay++;
      const r = d.results && d.results[0];
      const metin = r && r.alternatives && r.alternatives[0] ? r.alternatives[0].transcript : '';
      const son = r && r.isFinal;
      console.log(`  data #${dataSay} (+${Date.now() - t0}ms) ${son ? 'FINAL' : 'ara  '}: ${metin}`);
      if (son && metin) final.push(metin.trim());
    })
    .on('end', () => {
      console.log('\n--- Bitti ---');
      console.log('Toplam data olayi:', dataSay);
      console.log('FINAL metin      :', final.length ? final.join(' ') : '(BOS — Google hic kelime tanimadi)');
      if (!dataSay) console.log('\n>>> HIC data gelmedi: Google baglanamadi (IPv6/ag) YA DA ses tamamen sessiz.');
      else if (!final.length) console.log('\n>>> data geldi ama kelime yok: ses var ama anlasilmiyor (cok dusuk/gurultu/yanlis dil).');
      else console.log('\n>>> BASARILI: Google STT bu sunucudan CALISIYOR. Sorun canli ses hattinda (RTP->STT handoff).');
      process.exit(0);
    });

  // 20ms'lik parcalar halinde gercek zamanli gibi besle (streaming'i dogru taklit et)
  const bytesPer20ms = Math.round(fmt.sampleRate * (fmt.bitsPerSample / 8) * fmt.channels * 0.02);
  let off = 0;
  const tik = setInterval(() => {
    if (off >= data.length) { clearInterval(tik); stream.end(); return; }
    stream.write(data.slice(off, off + bytesPer20ms));
    off += bytesPer20ms;
  }, 20);
}

main().catch((e) => { console.error('KRITIK:', e.message); process.exit(1); });

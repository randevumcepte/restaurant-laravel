'use strict';
// Laravel beyin istemcisi — SantralAsistan uclariyla (metin girer, cevap+aksiyon doner).
// Bu dosya SES bilmez; sadece HTTP sozlesmesini konusur (mimari dokumandaki kontrat).
const axios = require('axios');
const cfg = require('./config');
const log = require('./log');

const http = axios.create({
  baseURL: cfg.laravel.baseUrl,
  timeout: 16000,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/x-www-form-urlencoded',
    ...(cfg.laravel.secret ? { 'X-Santral-Secret': cfg.laravel.secret } : {}),
  },
});

function form(obj) {
  const p = new URLSearchParams();
  for (const [k, v] of Object.entries(obj)) {
    if (v === undefined || v === null) continue;
    p.append(k, typeof v === 'object' ? JSON.stringify(v) : String(v));
  }
  return p.toString();
}

// POST /api/santral/baslat -> {oturum_id, karsilama}
async function baslat(subeId, telefon, hat) {
  const { data } = await http.post('/api/santral/baslat', form({ sube_id: subeId, telefon, hat }));
  return data;
}

// POST /api/santral/konus -> {ok, cevap, aksiyon, veri, bitir}
async function konus(oturumId, metin) {
  const { data } = await http.post('/api/santral/konus', form({ oturum_id: oturumId, metin }));
  return data;
}

// GET /api/santral/aktarma-hedef -> {aktif, dial, zil} (panelden yonetilen aktarma hedefi)
async function aktarmaHedef(subeId) {
  try {
    const { data } = await http.get('/api/santral/aktarma-hedef', { params: { sube_id: subeId } });
    return data;
  } catch (e) {
    log.warn('aktarma-hedef alinamadi:', e.message);
    return { aktif: 0, dial: '', zil: 30 };
  }
}

// POST /api/santral/bitir -> cagriyi cagri_loglari'na sonucla
async function bitir(oturumId, ozet) {
  try {
    const { data } = await http.post('/api/santral/bitir', form({ oturum_id: oturumId, ozet }));
    return data;
  } catch (e) {
    log.warn('bitir hatasi (yok sayiliyor):', e.message);
    return null;
  }
}

module.exports = { baslat, konus, bitir, aktarmaHedef };

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

// POST /api/santral/baslat -> {oturum_id, karsilama}. geriAramaOturum>0 ise: yarida kalan oturumla devam.
async function baslat(subeId, telefon, hat, geriAramaOturum) {
  const { data } = await http.post('/api/santral/baslat', form({ sube_id: subeId, telefon, hat, geri_arama_oturum: geriAramaOturum || 0 }));
  return data;
}

// GERI ARAMA kuyrugu: bekleyenleri al + sonuc bildir (araniyor|basarili|cevapsiz).
async function geriAramaBekleyen() {
  try { const { data } = await http.get('/api/santral/geri-arama-bekleyen'); return data; }
  catch (e) { log.debug('geri-arama-bekleyen hata:', e.message); return { ok: 0, liste: [] }; }
}
async function geriAramaDurum(id, durum) {
  try { await http.post('/api/santral/geri-arama-durum', form({ id, durum })); } catch (_) {}
}

// POST /api/santral/konus -> {ok, cevap, aksiyon, veri, bitir}
async function konus(oturumId, metin) {
  const { data } = await http.post('/api/santral/konus', form({ oturum_id: oturumId, metin }));
  return data;
}

// Ses kaydini Laravel'e yukler (ham wav govdesi; oturum_id/tur query ile)
async function sesYukle(oturumId, subeId, tur, filePath) {
  try {
    const fs = require('fs');
    if (!fs.existsSync(filePath)) { log.warn('ses dosyasi yok: ' + filePath); return; }
    const buf = fs.readFileSync(filePath);
    if (!buf || buf.length < 200) { log.warn('ses dosyasi cok kucuk/bos: ' + filePath); return; }
    await http.post('/api/santral/kayit-yukle', buf, {
      params: { oturum_id: oturumId || '', sube_id: subeId || '', tur: tur || 'ai', secret: cfg.laravel.secret || '' },
      headers: { 'Content-Type': 'audio/wav' },
      maxBodyLength: Infinity, maxContentLength: Infinity, timeout: 30000,
    });
    log.info(`ses kaydi yuklendi (${tur}, ${Math.round(buf.length / 1024)}KB)`);
  } catch (e) {
    log.warn('ses yukleme hata: ' + e.message);
  }
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

module.exports = { baslat, konus, bitir, aktarmaHedef, sesYukle, geriAramaBekleyen, geriAramaDurum };

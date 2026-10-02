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

    this._sesGeldi = false; // ilk ses karesi STT'ye ulasti mi (tani icin)

    this.rtp = new RtpOturumu(rtpPort, (payload) => {
      // YARI-DUPLEKS: barge-in kapaliyken AI konusurken mikrofonu STT'ye VERME.
      // Boylece hat yankisi (AI'nin kendi sesi) STT'ye dusup AI'yi kesmez / kendini dinlemez.
      if (!cfg.bargeIn && this.rtp && this.rtp.sesVarMi) return;
      if (!this._sesGeldi) { this._sesGeldi = true; log.info('ilk ses karesi STT ye ulasti (mikrofon calisiyor)'); }
      // ENERJI TESHISI: giden ses gercek mi sessizlik mi? (ulaw sessizlik ~0xFF/0x7F)
      this._ornek = (this._ornek || 0) + 1;
      if (this._ornek <= 250) {
        let dolu = 0;
        for (let i = 0; i < payload.length; i++) { const b = payload[i]; if (b !== 0xFF && b !== 0x7F && b !== 0xFE && b !== 0x7E && b !== 0x00) dolu++; }
        this._enerjiTop = (this._enerjiTop || 0) + (payload.length ? dolu / payload.length : 0);
        if (this._ornek === 250) log.info(`SES ENERJI: ort dolu-oran ${(this._enerjiTop / 250).toFixed(2)} (0'a yakin=SESSIZLIK/sesin gelmiyor, >0.3=gercek ses var)`);
      }
      this.stt.yaz(payload);
    });
    this.stt = new SttOturumu(
      (final) => this._finalMetin(final),
      (interim) => this._interim(interim),
    );
  }

  async basla() {
    try {
      const d = await brain.baslat(this.subeId, this.telefon, 'santral');
      this.oturumId = d.oturum_id;
      log.info(`Oturum #${this.oturumId} basladi (kanal ${this.kanalId}, tel ${this.telefon || '-'}, sube ${d.sube_id ?? this.subeId}, menu ${d.menu_adet ?? '?'} urun, musteri ${d.musteri || 'YENI'})`);
      if (d.menu_adet === 0) log.warn('DIKKAT: bu subede aktif urun YOK -> AI menuyu tanitamaz (DEFAULT_SUBE_ID dogru mu? urunler.aktif=1 mi?)');
      if (d.musteri) log.info(`musteri taninID: ${d.musteri}${d.son_siparis ? ' | gecen siparis: ' + d.son_siparis : ' | gecen siparis YOK'}`);
      await this._seslendir(d.karsilama || 'Merhaba, size nasıl yardımcı olabilirim?');
    } catch (e) {
      log.error('Beyin baslat hatasi:', e.message);
      await this._seslendir('Sizi yetkiliye bağlıyorum, lütfen hatta kalın.');
      this.onAktar(this.kanalId);
    }
    this._sessizlikSifirla();
  }

  _interim(metin) {
    if (this.kapali || this._kapaniyor) return; // kapanis sirasinda dinleme/barge-in yok
    log.debug(`ara-sonuc: ${metin}`);
    // BARGE-IN: musteri GERCEKTEN konusunca (>=2 kelime) AI'nin sesini kes.
    // >=2 kelime sarti: tek kelimelik gurultu/eko blip'i AI'yi bosuna kesmesin.
    const kelime = (metin || '').trim().split(/\s+/).filter(Boolean).length;
    if (cfg.bargeIn && this.rtp.sesVarMi && kelime >= 2) {
      log.debug('barge-in: AI susturuluyor (gercek konusma)');
      this.rtp.sustur();
    }
    this._sessizlikSifirla();
    // ISTEMCI-TARAFI ENDPOINTING (AKILLI): interim sabit kalinca zorla finalize et.
    //  - KISA cevap (<=3 kelime: 'kartla olsun','evet','margarita') -> 1.2sn (snappy)
    //  - UZUN cumle (adres/isim, cok kelime) -> 2.4sn SABIRLI (duraklamada kesme, musteri tamamlasin)
    // Her yeni interim timer'i sifirlar; yani sure, musteri SUSTUKTAN sonra sayilir.
    this._sonInterim = (metin || '').trim();
    const kelimeSayisi = this._sonInterim ? this._sonInterim.split(/\s+/).length : 0;
    const bekle = kelimeSayisi <= 3 ? 1200 : 2400;
    clearTimeout(this._interimZ);
    this._interimZ = setTimeout(() => this._interimZorla(), bekle);
  }

  _interimZorla() {
    if (this.kapali || this.mesgul || this._kapaniyor) return;
    const m = (this._sonInterim || '').trim();
    this._sonInterim = '';
    if (m.length >= 2) { log.debug(`endpointing: interim->final: ${m}`); this._finalMetin(m); }
  }

  async _finalMetin(metin) {
    if (this.kapali || this._kapaniyor) return; // kapanis sirasinda yeni tur baslatma
    clearTimeout(this._interimZ); this._sonInterim = '';
    const n = (metin || '').toLowerCase().trim();
    if (n.length < 1) return;
    // Yineleme onle: zorla-final ile islenen cumle, Google'in gercek final'iyle ikinci kez gelmesin
    const simdi = Date.now();
    if (this._sonIslenen && this._sonIslenen.n === n && (simdi - this._sonIslenen.t) < 5000) {
      log.debug('yinelenen final atlandi');
      return;
    }
    this._sonIslenen = { n, t: simdi };
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
        await this._seslendir('Kusura bakmayın, sizi yetkiliye aktarıyorum.');
        this.onAktar(this.kanalId);
        return;
      }
      log.info(`< asistan: ${d.cevap}${d.aksiyon ? '  [aksiyon: ' + d.aksiyon + ']' : ''}`);
      // KAPANIS ise: kapanis cumlesi barge-in ile KESILMESIN + yeni tur baslamasin (askida kalmasin)
      const kapanis = (d.aksiyon === 'veda' || d.bitir);
      if (kapanis) this._kapaniyor = true;
      if (d.cevap) await this._seslendir(d.cevap);

      if (d.aksiyon === 'aktar') { this.onAktar(this.kanalId); return; }
      if (kapanis) { await this._kapatSirasi(); return; }
    } catch (e) {
      log.error('Beyin konus hatasi:', e.message);
      await this._seslendir('Bir sorun oluştu, sizi yetkiliye bağlıyorum.');
      this.onAktar(this.kanalId);
    } finally {
      this.mesgul = false;
      this._sessizlikSifirla();
    }
  }

  async _seslendir(metin) {
    // Cumle-cumle seslendir: ilk cumle hemen calar, sonrakiler o calariken uretilir (algilanan gecikme duser).
    const cumleler = this._cumlelereBol(metin);
    for (const c of cumleler) {
      if (this.kapali) break;
      const ses = await tts.seslendir(c);
      if (ses && ses.length) this.rtp.oynat(ses);
    }
  }

  _cumlelereBol(metin) {
    const t = (metin || '').trim();
    if (!t) return [];
    // Cumle sonlarindan bol (. ! ?), noktalamayi koru; cok kisa parcalari birlestir.
    const parcalar = t.match(/[^.!?]+[.!?]*/g) || [t];
    const out = [];
    let tampon = '';
    for (const p of parcalar) {
      tampon += p;
      if (tampon.trim().length >= 20 || /[.!?]\s*$/.test(tampon)) { out.push(tampon.trim()); tampon = ''; }
    }
    if (tampon.trim()) out.push(tampon.trim());
    return out.length ? out : [t];
  }

  _sessizlikSifirla(ikinci) {
    clearTimeout(this.sessizlikZ);
    if (this.kapali || this._kapaniyor) return;
    this.sessizlikZ = setTimeout(async () => {
      if (this.kapali) return;
      // AI hala konusuyor ya da beyin dusunuyorsa: sessizlik SAYMA, pencereyi yeniden baslat
      if (this.mesgul || this.rtp.sesVarMi) { this._sessizlikSifirla(ikinci); return; }
      if (ikinci) { log.debug('ikinci sessizlik -> kapat'); this._kapatSirasi(); return; }
      log.debug('sessizlik zaman asimi -> orada misiniz');
      await this._seslendir('Orada mısınız? Yardımcı olabileceğim başka bir şey var mı?');
      this._sessizlikSifirla(true); // ikinci pencere; yine sessizse kapat
    }, cfg.sessizlikMs);
  }

  async _kapatSirasi() {
    // Kapanis cumlesi TAM calinip bitene kadar bekle (yarida kesme), sonra hatti kapat. Max ~18sn guvenlik.
    let gecen = 0;
    const tik = () => {
      if (this.kapali) return;
      gecen += 300;
      if (this.rtp && this.rtp.sesVarMi && gecen < 18000) { this._kapatZ = setTimeout(tik, 300); return; }
      this._kapatZ = setTimeout(() => this.onBitir(this.kanalId), 600); // son kareler de gitsin
    };
    // once seslendirmenin kuyruga girmesine firsat ver, sonra beklemeye basla
    this._kapatZ = setTimeout(tik, 700);
  }

  async kapat(sonuc) {
    if (this.kapali) return;
    this.kapali = true;
    clearTimeout(this.sessizlikZ);
    clearTimeout(this._kapatZ);
    clearTimeout(this._interimZ);
    this.stt.kapat();
    this.rtp.kapat();
    if (this.oturumId) await brain.bitir(this.oturumId, sonuc || 'kapandi');
    log.info(`Oturum #${this.oturumId || '-'} kapandi (kanal ${this.kanalId})`);
  }
}

module.exports = { CagriOturumu };

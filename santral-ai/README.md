# santral-ai — ResteOS AI Santral kopru (Faz 2)

Asterisk (ARI **externalMedia**, Asterisk 16/17) ile Laravel beyni (`SantralAsistan`) arasindaki **canli ses koprusu**. Telefonu AI acar, Turkce konusur, rezervasyon/paket siparis alir, gerekince insana aktarir.

```
Telefon -> Asterisk (Stasis/ARI) -> externalMedia (RTP) <-> [santral-ai] <-> Google STT
                                                              |                    |
                                          Google TTS <--------+------> Laravel /api/santral/* (beyin)
```

Ses zinciri: **Google streaming STT + Haiku (mevcut beyin) + Google TTS** (secilen "A" stack).

## Gereksinimler
- Node.js 18+ (Asterisk ile **ayni sunucuda**)
- Asterisk **16.6+ / 17** (externalMedia + `chan_rtp`/UnicastRTP modulu)
- Google **STT servis hesabi** JSON (streaming icin API anahtari YETMEZ)
- Google **TTS API anahtari** (mevcut `GOOGLE_TTS_API_KEY` kullanilabilir)
- Laravel tarafinda `ANTHROPIC_API_KEY` + bakiye (beyin icin)

## Kurulum
```bash
cd santral-ai
npm install
cp .env.example .env    # degerleri doldur
# Google STT servis hesabi:
#   export GOOGLE_APPLICATION_CREDENTIALS=/opt/santral-ai/google-stt.json  (veya .env)
node src/index.js       # ya da: npm start
```

## Asterisk ayari
1. `asterisk/ari-http.conf.sample` -> `http.conf` + `ari.conf` (ARI'yi ac, kullanici/sifre `.env` ile ayni).
2. `asterisk/extensions.conf.sample` -> gelen hatti `Stasis(santral-ai)` yap; `[santral-aktar]` context'inde gercek dahiliyeyi `Dial()` et.
3. `asterisk -rx "core reload"`.

Kontrol:
```bash
asterisk -rx "ari show apps"          # santral-ai gorunmeli (kopru calisirken)
asterisk -rx "module show like res_ari"
```

## Test (kademeli)
- **Beyin (Faz 1, ses yok):** tarayicida `/santral-test` — yazisarak rezervasyon/siparis/saat akisi.
- **Kopru (Faz 2):** softphone'dan gelen hatti ara; `LOG_LEVEL=debug` ile RTP/STT/beyin turunu izle.
- **Uctan uca (Faz 3):** gercek numaradan ara; rezervasyon `rezervasyonlar` tablosuna, cagri `cagri_loglari`'na dusmeli.

## Ayarlar (.env)
`ARI_*` baglanti · `RTP_*` port havuzu · `MEDIA_FORMAT` (ulaw/slin16) · `LARAVEL_BASE_URL` · `DEFAULT_SUBE_ID` · `GOOGLE_APPLICATION_CREDENTIALS` (STT) · `GOOGLE_TTS_API_KEY` · `TTS_VOICE` · `BARGE_IN` · `SESSIZLIK_MS`.

## Dosyalar
| Dosya | Gorev |
|---|---|
| `src/index.js` | ARI baglanti, Stasis, externalMedia kurulum, port havuzu, aktarim/kapanis |
| `src/session.js` | Cagri orkestrasyon: STT<->beyin<->TTS, barge-in, sessizlik |
| `src/stt.js` | Google streaming STT (servis hesabi), akis yenileme |
| `src/tts.js` | Google TTS (REST + anahtar), onbellek, WAV header ayikla |
| `src/rtp.js` | Ham RTP al/ver (dgram), 20ms kareleme, barge-in sustur |
| `src/brain.js` | Laravel `/api/santral/*` istemcisi (ses bilmez) |
| `src/config.js` | .env okuma + format'a gore ses parametreleri |

## Gecikme notlari
- `ulaw@8k` native -> yeniden ornekleme yok.
- TTS onbellegi + cumle-cumle seslendirme algilanan gecikmeyi dusurur.
- Daha dusuk gecikme istenirse (Faz "B") STT+LLM+TTS blogu Realtime saglayiciyla degistirilir; ARI/RTP katmani AYNI kalir.

## Yapilmadi / sonraki
- Outbound geri-arama (kacan cagriyi AI arar) — ARI `originate` + `[santral-outbound]` (Faz 4).
- Paket siparisin `adisyon`a donusmesi — Laravel `SantralAsistan` aksiyon tarafinda (Faz 4).
- Yuk/es-zamanli cagri testleri (Faz 5).

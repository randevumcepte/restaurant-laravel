# ResteOS AI Santral — Asterisk ile Konuşan Yapay Zekâ (Mimari & Yol Haritası)

Hedef: Restoranın telefonu çaldığında **yapay zekâ açar, doğal Türkçe konuşur, rezervasyon ve paket sipariş alır, saat/adres/menü sorularını yanıtlar, gerekirse insana aktarır.** Kaçan çağrı = kaçan ciro; AI bunları kurtarır.

İyi haber: AI'nın **beyni + kulağı + ağzı** projede zaten var. Eksik olan tek şey Asterisk ile bu üçlüyü birbirine bağlayan **canlı ses köprüsü**.

Elimizdekiler:
- 🧠 Beyin (LLM): `RestoAsistan` / `MusteriAsistan` → Anthropic Haiku çağrısı (`cagir()`), sipariş/rezervasyon mantığı
- 👂 Kulak (STT): Google Speech-to-Text (`/api/qr/stt`, teşhis `web.php:2188`)
- 🗣️ Ağız (TTS): Google Cloud TTS erkek Türkçe (`SeslendirmeServisi.php`, `/api/tts`)
- 📞 Telefon kimliği: CallerID webhook + `cagri_loglari` (arayanı tanıma zaten çalışıyor)

---

## 1) Mimari (akış)

```
                  ┌─────────────────────────────────────────────────────┐
   Telefon        │                  Asterisk PBX (sizde)               │
   çalar  ─────►  │  Dialplan: Answer() → AudioSocket(uuid, KÖPRÜ:port) │
                  └───────────────┬─────────────────────────────────────┘
                                  │  ham ses (slin16, 8kHz) TCP
                                  ▼
                  ┌─────────────────────────────────────────────────────┐
                  │   restaurant-santral-ai (yeni Node sidecar köprü)    │
                  │  1. AudioSocket sunucu (ses al/ver)                  │
                  │  2. VAD/barge-in (konuşunca AI'yı sustur)           │
                  │  3. Google STREAMING STT (tr-TR, telefon modeli 8k) │
                  │  4. cümle bitince → Laravel'e metin gönder          │
                  │  6. dönen cevabı Google TTS ile sese çevir          │
                  │  7. sesi Asterisk'e geri akıt                       │
                  └───────────────┬─────────────────────────────────────┘
                                  │  HTTP  (metin-giriş → cevap+aksiyon-çıkış)
                                  ▼
                  ┌─────────────────────────────────────────────────────┐
                  │  Laravel: SantralAsistan (telefon personası beyni)  │
                  │  • karşılama / saat / adres / menü                  │
                  │  • REZERVASYON al → rezervasyonlar tablosu          │
                  │  • PAKET SİPARİŞ al → adisyon (platform=telefon)    │
                  │  • müşteri eşleştir/oluştur (musteriler)            │
                  │  • "insana aktar" → Asterisk'e transfer sinyali     │
                  │  • her çağrı bir oturum → santral_oturumlari        │
                  └─────────────────────────────────────────────────────┘
```

**Neden bu tasarım:** Beyin (Laravel/Anthropic) ile ses köprüsü (Node) ayrık. Böylece STT/TTS/LLM sağlayıcısı ileride tek tek değiştirilebilir; beyin mevcut kodu yeniden kullanır.

---

## 2) Ses köprüsü seçenekleri (Asterisk tarafı)

| Yöntem | Asterisk sürümü | Not |
|---|---|---|
| **AudioSocket** (ÖNERİLEN) | 18+ | En temiz: dialplan'de tek satır, ham PCM TCP akışı. |
| **ARI externalMedia** | 16+ | REST/WebSocket; RTP media stream. AudioSocket yoksa. |
| AGI/EAGI | eski | Gerçek-zamanlı değil, önerilmez. |

### AudioSocket dialplan örneği (Asterisk 18+)
```asterisk
; extensions.conf — restoran gelen hattı
[gelen-restoran]
exten => s,1,Answer()
 same => n,Set(UUID=${SHELL(uuidgen | tr -d '\n')})
 same => n,AudioSocket(${UUID},127.0.0.1:8090)   ; santral-ai köprüsü
 same => n,Hangup()
```
> `127.0.0.1:8090` → Node köprüsünün dinlediği adres (Asterisk ile aynı sunucuda ise localhost = en düşük gecikme).

### İnsana aktarma
AI "sizi bağlıyorum" dediğinde köprü, çağrıyı Asterisk'te gerçek dahiliye yönlendirir (ARI `redirect` veya AudioSocket'i kapatıp dialplan'de `Dial(PJSIP/101)` adımına düşürme).

---

## 3) Laravel tarafı — API sözleşmesi (yeni)

Köprü ile beyin arasındaki kontrat sabittir (ses yöntemi ne olursa olsun değişmez):

- `POST /api/santral/baslat` → `{sube_id, telefon, hat}` → `{oturum_id, karsilama_metni}`
- `POST /api/santral/konus` → `{oturum_id, metin}` → `{cevap_metni, aksiyon, bitir?}`
  - `aksiyon`: `null | rezervasyon | siparis | aktar | kapat`
- `POST /api/santral/bitir` → `{oturum_id, ozet}` → çağrıyı `cagri_loglari`'na sonuçla işle (siparis/rezervasyon/kacan).

Yeni dosyalar:
- `app/Services/SantralAsistan.php` — telefon personası; `RestoAsistan::cagir()` + `MusteriAsistan` sipariş mantığını yeniden kullanır.
- `routes/web.php` (veya `routes/api.php`) — yukarıdaki 3 uç (CSRF muaf, `api/*`).
- migration: `santral_oturumlari` (oturum_id, sube_id, telefon, musteri_id, gecmis JSON, durum, sonuc, adisyon_id, created_at).

Telefon personası kuralları (sistem promptu): kısa/net/sıcak Türkçe; TTS için düz metin (emoji/madde yok); menü ve saat verisini UYDURMADAN kullanır; rezervasyon/sipariş için eksik bilgiyi tek tek sorar (tarih-saat-kişi / ürün-adet-adres); onay alır; "buyurun" demez.

---

## 4) Node köprü — restaurant-santral-ai (yeni sidecar)

Klasör: `restaurant-santral-ai/` (WhatsApp sidecar'larıyla aynı desen).
Sorumluluklar:
1. AudioSocket TCP sunucu (port 8090).
2. Google **streaming** STT: `languageCode=tr-TR`, `model=phone_call`, `sampleRateHertz=8000`, `enableAutomaticPunctuation`, interim results.
3. VAD / barge-in: müşteri konuşmaya başlayınca TTS oynatmayı KES.
4. Cümle bitince (final transcript) → `/api/santral/konus`.
5. Dönen cevabı Google TTS `LINEAR16 @ 8000Hz` ister → doğrudan Asterisk'e akıt (yeniden örnekleme yok).
6. Cümle-cümle TTS (ilk cümleyi hemen çal → algılanan gecikme düşer).
7. Sessizlik/zaman aşımı, hat kapanınca `/api/santral/bitir`.

Env: `GOOGLE_STT_KEY`, `GOOGLE_TTS_KEY` (mevcut anahtar), `LARAVEL_BASE_URL`, `AUDIOSOCKET_PORT=8090`.

---

## 5) Gecikme & sağlayıcı stratejisi

- **A) Mevcut stack (ÖNERİLEN başlangıç):** Google STT + Haiku + Google TTS. Beyin hazır, maliyet belli, tur başına ~1–1.5 sn. Cümle-cümle TTS + interim STT ile akıcı.
- **B) Realtime speech-to-speech (yükseltme):** OpenAI Realtime / Gemini Live → ~0.4 sn, en doğal, kesme (barge-in) yerleşik. Ayrı sağlayıcı + maliyet. Köprü aynı; sadece STT+LLM+TTS bloğu değişir.

Telefon sesi 8kHz dar bant → STT'de `phone_call` modeli, TTS'de 8kHz LINEAR16 kullan.

---

## 6) Aksiyonlar (beyin ne yapabilecek)

- **Rezervasyon:** tarih/saat/kişi al → uygunluk → `rezervasyonlar` kaydı → onay oku.
- **Paket sipariş:** ürün/adet (menü zekâsı `MusteriAsistan`'dan) → adres/telefon → `adisyon` (platform=telefon) + müşteri eşleştir → toplam/onay.
- **Bilgi:** çalışma saati, adres/yol tarifi, "bugün ne var", kampanya.
- **İnsana aktar:** yoğunsa / karmaşık istekte gerçek dahiliye.
- **Kaçan çağrı geri-arama (outbound):** kapanan/kaçan numarayı AI geri arar (Asterisk originate).
- **Sonuç:** her çağrı `cagri_loglari`'na `sonuc` (siparis/rezervasyon/bilgi/aktar/kacan) olarak yazılır; patron panelinde raporlanır.

---

## 7) Yol Haritası (fazlar)

> **Kullanıcı kararı (2026-09-29):** Asterisk **16/17** → ses köprüsü **ARI externalMedia** (AudioSocket değil). Köprü Asterisk ile **aynı sunucuda** (localhost RTP). Ses zinciri **A**: Google STT + Haiku + Google TTS.

- **✅ Faz 1 — Beyin:** `SantralAsistan` + `/api/santral/*` + `santral_oturumlari` + `/santral-test` ekranı. (Ses olmadan, metinle test edilebilir — YAPILDI.)
- **✅ Faz 2 — Köprü:** `restaurant-santral-ai/` Node sidecar — **ARI externalMedia** + Google streaming STT + Google TTS + barge-in + RTP (ulaw@8k). (YAPILDI; `npm install` + gerçek çağrıyla saha testi kaldı.)
- **Faz 3 — Asterisk:** `Stasis(restaurant-santral-ai)` dialplan (ARI app adı = ARI_APP), gelen hat, `[santral-aktar]` ile insana aktarma. (Örnek konf. `restaurant-santral-ai/asterisk/` altında hazır; sahada uygulanacak.)
- **Faz 4 — Aksiyonlar:** paket sipariş → `adisyon` dönüşümü + müşteri eşleştirme + outbound geri-arama (ARI originate). (Rezervasyon Faz 1'de bağlandı.)
- **Faz 5 — Test & ince ayar:** gecikme, kesme, Türkçe telaffuz, gürültü; canlı pilot.

## 8) Test planı
- Faz 1: `/santral-test` (veya Postman `/api/santral/konus`) — yazışarak rezervasyon/sipariş akışı.
- Faz 2: `LOG_LEVEL=debug` ile köprüyü çalıştır; softphone'dan gelen hattı ara, RTP→STT→beyin→TTS turunu izle.
- Faz 3: Asterisk `Stasis` ile gerçek çağrı; softphone'dan ara; `ari show apps` ile köprü görünür.
- Faz 5: 10 gerçek senaryo (rezervasyon, sipariş, saat sorma, aktar, gürültülü hat).

# whatsmeow-bridge

randevumcepte için **whatsmeow** tabanlı WhatsApp bridge. Mevcut Baileys bridge'in HTTP contract'iyle birebir uyumlu — Laravel hiçbir kod değişikliği istemez.

## Niye

- Baileys (Node.js) yerine whatsmeow (Go) — daha düşük hafıza, daha stabil uptime.
- Cihaz fingerprint'i "**Chrome on Desktop**" — Linked Devices'da "whatsmeow" değil görünür.
- Aynı `whatsapp_gonderim_loglari` + webhook altyapısı kullanılır (bizim Laravel tarafı zaten hazır).

## Kurulum

```bash
# Sunucuda
cd /var/www/www-root/data/www/randevumcepte/whatsmeow-bridge

# 1) Go 1.21+ yuklu olmali
go version

# 2) Bagimliliklari indir
go mod tidy

# 3) Config — .env yarat
cp .env.example .env
nano .env  # SERVICE_TOKEN, WEBHOOK_URL, WEBHOOK_SECRET doldur

# 4) Derle
go build -o whatsmeow-bridge .

# 5) Calistir (geliştirme)
./whatsmeow-bridge

# 5b) Calistir (production, pm2 ile)
pm2 start ./whatsmeow-bridge --name randevumcepte-whatsmeow \
  --update-env -- env $(cat .env | xargs)
```

## Config (`.env`)

| Anahtar | Ne işe yarar |
|---|---|
| `PORT` | HTTP port (default `3002` — Baileys 3001'de kalsın, çakışmasın) |
| `DB_DIR` | Salon başına SQLite dosyası nereye yazılsın (`./data`) |
| `SERVICE_TOKEN` | Laravel'in `X-Service-Token` header'ı (yetki) |
| `WEBHOOK_URL` | Laravel webhook endpoint (mevcut: `/api/wa-webhook`) |
| `WEBHOOK_SECRET` | Webhook `X-Webhook-Secret` header değeri |
| `DEVICE_OS` | Linked Devices'da görünecek isim (default `Chrome`) |
| `SEND_DELAY_MIN/MAX` | Anti-burst: mesaj arası bekleme aralığı (saniye) |

## HTTP Endpoint'leri

Baileys bridge ile birebir aynı:

```
POST /session/:salonId/start    → session başlat (QR gerekirse)
GET  /session/:salonId/qr       → mevcut QR kodu (base64 string)
GET  /session/:salonId/status   → connected / qr-pending / disconnected
POST /session/:salonId/send     → mesaj kuyruğa at (202 Accepted)
POST /session/:salonId/logout   → oturum kapat + DB temizle
GET  /health                    → ping
```

## Webhook Event'leri

Bridge → Laravel'e POST eder:

```json
{ "event": "connected",        "salonId": "278", "phone": "905..." }
{ "event": "disconnected",     "salonId": "278", "reason": "...", "banLikely": true }
{ "event": "qr.ready",         "salonId": "278" }
{ "event": "message.sent",     "salonId": "278", "logId": 5187, "messageId": "..." }
{ "event": "message.delivered","salonId": "278", "messageId": "..." }
{ "event": "message.read",     "salonId": "278", "messageId": "..." }
{ "event": "message.failed",   "salonId": "278", "logId": 5187, "error": "..." }
```

## Laravel tarafı

Bu bridge'i kullanmak için Laravel'de iki seçenek:

### A. Sadece test için (geçici)
`.env`'de:
```
WHATSAPP_SERVICE_URL=http://127.0.0.1:3002
```
Tüm trafik whatsmeow'a gider. Baileys atıl kalır.

### B. Salon bazında (önerilen — A/B pilot)
`salonlar` tablosuna kolon ekle:
```sql
ALTER TABLE salonlar ADD COLUMN whatsapp_bridge_tipi VARCHAR(20) DEFAULT 'baileys';
```

`WhatsAppService::request()` metodunda URL'i salon tipine göre seç:
```php
$bridgeTipi = $salon->whatsapp_bridge_tipi ?? 'baileys';
$baseUrl = $bridgeTipi === 'whatsmeow'
    ? config('whatsapp.whatsmeow_service_url')
    : config('whatsapp.service_url');
```

Sonra test salonu için:
```sql
UPDATE salonlar SET whatsapp_bridge_tipi='whatsmeow' WHERE id=278;
```

## Pilot Akışı

1. Bridge'i ayağa kaldır (`./whatsmeow-bridge` veya pm2)
2. Test salonu için: `POST /session/278/start`
3. `GET /session/278/qr` → dönen string'i QR'a çevir, telefonda tarat
4. Pair olduktan sonra `GET /session/278/status` → `connected` görür
5. Test mesajı: `POST /session/278/send` `{to: "+90...", message: "test", logId: 1}`
6. Telefonda mesajın gittiğini ve teslim/okundu webhook'larının düştüğünü doğrula
7. Baileys'le karşılaştır: aynı salon ile 1-2 hafta paralel kullan

## Bilinen Eksikler (V1 pilot)

- Cihaz props sadece `DEVICE_OS` üzerinden ayarlanıyor (daha detaylı browser/version stringleri eklenebilir)
- Bad MAC otomatik recovery yok (manuel logout/re-pair gerekebilir)
- Warm-up ramp yok (gunluk limit politikasi sadece Laravel tarafinda)
- Metric/Prometheus endpoint yok
- Multiple salon paralel scaling test edilmedi (~50 salon altında sorun olmamalı)

## Loglama

Tüm önemli olaylar stdout'a yazılır:
```
[278] connected (phone=905...)
[278] QR ready (len=...)
[278] send err to=905...: <hata>
[278] disconnected
```

pm2 kullanıyorsan `pm2 logs randevumcepte-whatsmeow` ile takip et.

## Güvenlik notu

`SERVICE_TOKEN` ayarlamayı **unutma**. Aksi halde bridge'in 3002 portu açıksa herkes salon session'larını ele geçirebilir. Production'da nginx reverse proxy + firewall ile ek koruma şart.

# ResteOS — Elektra Araştırması Yol Haritası (Yapılacaklar)

> ElektraWeb ekran-ekran analizinden (bkz. ELEKTRA-KARSILASTIRMA.md) çıkan, kurulacak/kurulmayacak her şeyin master listesi.
> Durum: ⬜ bekliyor · 🔄 devam · ✅ bitti

---

## 🔥 TIER A — YÜKSEK ÖNCELİK (önce bunlar)

### A1. İtibar/Yorum Yönetimi + Memnuniyet Nabzı 🔄 (başlıyoruz)
Değer: ÇOK YÜKSEK (Google restoranda kritik; Elektra bunu PARAYLA satıyor, biz dahil vereceğiz) · Efor: Orta · Maliyet: ~bedava
- **Faz 1 — Memnuniyet Nabzı** (Google'sız, masada yakala+çöz): memnuniyet tablosu · müşteri sayfası (masa QR, onaylı demo tasarımı) · düşük puan→anında yönetici/departman uyarısı (FCM+WA) · Google link ayarı · yönetici paneli (liste+ortalama+çözüldü) · menü/yetki
- **Faz 2 — İtibar Paneli**: Google Business Profile OAuth · yorum oto-çek+dedup · AI analiz (duygu+özet+departman+yanıt taslağı) · pano (skor/trend/departman/yanıt oranı) · yorum detay+onaylı yanıt · kötü yorum uyarı · manuel giriş · Google'a geri yanıt · haftalık AI özeti→patron brief
- **Faz 3**: ödeme sonrası WA davet (AI kişiselleştir) · dönüşüm takibi · Flutter

### A2. AI Pazarlama / Kampanya Motoru ⬜
Değer: ÇOK YÜKSEK (altyapı %70 var: WA+CRM+sadakat+doğum günü) · Efor: Düşük-Orta · Maliyet: düşük (metin AI)
- Hedefleme: win-back (gelmeyen), doğum günü, VIP, durgun-gün · AI kampanya metni üret · WA/SMS gönder · sonuç takibi. "Tek müşteri-büyütme beyni" (İtibar+sosyal+kampanya+sadakat aynı veriden).

### A3. Satıcı Yaşlandırma + AI Nakit Danışmanı ⬜
Değer: YÜKSEK (nakit yönetimi) · Efor: Düşük-Orta · AI değil (üstüne AI önceliklendirme)
- Cari yaşlandırma kovaları (0-15/16-30/31-45/46-60/61-90/91+ geçmiş+gelecek) · ödeme planı · **AI nakit önceliklendirme** (patron AI: "önce şunu öde") — onlar rapor verir, biz akıl verir.

---

## 🟡 TIER B — ORTA ÖNCELİK (sonra değerlendir)

### B1. Çok-Kademeli Satınalma Onayı + Vekil Atama ⬜
Değer: kurumsal/çok-şube · Efor: Orta · Flag'li (küçük restoranda kapalı)
- Yapılandırılabilir onay zinciri (seviye→onaylayan) · şube/depo bazlı · "fiyat anlaşması varsa onayı atla" · vekil atama (onaylayan tatildeyse).

### B2. AI Talep Tahmini (öngörücü satınalma) ⬜
Değer: YÜKSEK (Elektra'nın menü-forecast'inin restoran+AI versiyonu — onları geçeriz) · Efor: Orta
- POS satış geçmişi (+hafta günü/trend/etkinlik) → satış tahmini → reçeteyle malzeme → proaktif sipariş taslağı. Altyapı var (reçete+POS+tedarikçi), eksik=tahmin motoru.

### B3. Social Media Manager (yalın) ⬜
Değer: YÜKSEK pazarlama · Efor: Orta-Yüksek (paylaşım Meta/LinkedIn API; video opsiyonel/BYOK)
- Menü/fotoğraftan AI post+caption+hashtag → onay → IG/FB paylaş. Video gen sonra.

### B4. Happy Hour (zaman-bazlı fiyat) ⬜
Değer: bar/pub için yüksek · Efor: Düşük-Orta
- Ürün/departman + tarih/saat aralığı + geçerli günler + indirim%/happy fiyat → POS otomatik uygular.

### B5. Fix Menü / Tabldot (set/sabit menü) ⬜
Değer: iftar/etkinlik/prix-fixe · Efor: Orta
- Kurs grupları (zorunlu flag) + sabit fiyat → POS set menü + reçeteyle stok. Bizim üstünlük: rezervasyon/etkinlik + departman mutfağa bağla.

---

## ⚪ TIER C — DÜŞÜK/NİŞ (en son, ya da yalın)

### C1. Müstahsil Makbuzu ⬜
Değer: orta (TR mevzuat — hal/çiftçi alış vergi belgesi) · Efor: Düşük-Orta

### C2. Fiyat Anlaşmaları (sözleşmeli tedarikçi fiyatı) ⬜
Değer: orta · Efor: Orta — dönemsel/sabit tedarikçi fiyatı + satınalmada otomatik kullan.

### C3. Demirbaş (YALIN ekipman/bakım — vergi amortismanı DEĞİL) ⬜
Değer: düşük (muhasebeci işi) · Efor: Orta
- Ekipman kartı+barkod+basit amortisman + **bakım/arıza** (Grup B departman) + **zimmet** (İК) bağı. Vergi amortismanı/yeniden değerleme=muhasebeciye bırak.

### C4. Tabldot — Personel Yemeği dilimi ⬜
Değer: niş · Efor: Düşük-Orta — çalışan ne yedi→maliyet/maaş takibi (tam Tabldot modülü değil).

---

## 🚀 AYRI / GELECEK — AI Santral yükseltme
### D1. AI Santral → Gemini Live / OpenAI Realtime ⬜
Değer: yüksek (97 dil+canlı transkript+kayıt+görüşme-içi ödeme) · Efor: Yüksek · **Maliyet: EN PAHALI AI** (önbellek çalışmaz, dakika-başı)
- Not: HotelCall = Gemini Live üstünde. Bizde çekirdek var, ses katmanını realtime API'ye taşı. **Ayrı paketle** (aylık X dk + aşım).

---

## ❌ ATLA — Otel-özel (restorana uymaz)
Akıllı Dinamik Fiyat (RMS) · Online Booking · Kiosk Check-in/Smart ID · Rate Manager · Tur Operatörü (acenta.online) · Marina Yönetimi · otel raporları (rezervasyon/forecast/küp/kat hizmetleri/folyo)

## ✅ ZATEN BİZDE VAR / ÖNDE (yapma — konumlandır)
Akıllı WhatsApp · AI Santral (çekirdek) · SmartMenü/QR menü+müşteri AI · İК mobil+İК AI · Satınalma AI (onların "Akıllı Talep"=min-max, biz önde) · Salon Şema dijital ikiz · departman-bilinçli ekranlar · çoklu kasa · kurye takip · çark/sadakat · Patron AI · departman denetimi

---

## 💡 Stratejik hatırlatma
- Elektra AI'ı = **pahalı/gizli/à-la-carte eklenti**; biz = **aboneliğe dahil fair-use** (kural+önbellek ~bedava). Bu en büyük koz.
- Onların "19 AI modülü" karışık: bazısı etiket/kural (Akıllı Talep), bazısı otel-özel (Dinamik Fiyat), bazısı gerçek+bizde yok ucuz kurulur (İtibar, Social, Kampanya).

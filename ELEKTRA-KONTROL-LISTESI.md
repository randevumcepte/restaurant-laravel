# ✅ KONTROL LİSTESİ — Elektra Araştırmasından Kurulan Modüller

> Hepsi CANLIDA (restaurant.webfirmam.com.tr). Sahip (PIN 1001) veya müdür ile gir, her maddeyi test et.
> Durum: ⬜ kontrol edilecek · ✅ onaylandı · ⚠️ düzeltme gerek

---

## 🔥 TIER A

### A1 · Üretim Planı → Malzeme (MRP)  →  Menü: **Stok & Tedarik → Üretim Planı → Malzeme**
- ⬜ Ürünlere adet gir → **birim** doğru mu (porsiyon/kg/lt)?
- ⬜ **Malzeme İhtiyacını Hesapla** → renkli tablo (NET AL kırmızı pill, yeter yeşil) geliyor mu?
- ⬜ **📋 Reçete Gramaj Detayı** açılıyor, ürün bazında gramaj doğru mu?
- ⬜ **AI Öner** → geçmiş satış varsa adet dolduruyor / yoksa uyarı veriyor mu?
- ⬜ **Teslim tarihi + not** girip **Satınalma Talebi Oluştur** → talep oluşuyor mu?

### A2 · İtibar / Memnuniyet Nabzı  →  Menü: **Sadakat & Müşteri → İtibar / Memnuniyet**
- ⬜ **Ayarlar:** Google yorum linki + uyarı WhatsApp no gir, kaydet.
- ⬜ Masa değerlendirme linkini aç (Ayarlar → masa linkleri) → müşteri sayfası geliyor mu?
- ⬜ 😍 seç → Google butonu / 😣 seç → sebep+not → **gönder**.
- ⬜ Düşük puan sonrası yöneticiye **bildirim** düştü mü? (WA no + FCM gerekli)
- ⬜ Panel: ortalama puan, dağılım, bekleyen, "çözüldü" çalışıyor mu?
- 🔧 **Kurulum:** Google yorum linkini gir + masa QR'larına değerlendirme linkini koy.

### A3 · AI Pazarlama / Kampanya  →  Menü: **Sadakat & Müşteri → AI Kampanya**
- ⬜ Segment kartları **doğru müşteri sayısı** gösteriyor mu? (win-back/doğum günü/VIP/son30/tümü)
- ⬜ Segment seç + teklif yaz → **AI Metin Yaz** → {ad}'lı kişisel metin geldi mi?
- ⬜ **Gönder** → WhatsApp toplu gidiyor mu? (kontör + WA bridge gerekli)
- 🔧 **Kurulum:** WhatsApp bridge çalışıyor + kontör olmalı.

### A3-finans · Satıcı Yaşlandırma  →  Menü: **Stok & Tedarik → Satıcı Yaşlandırma**
- ⬜ Ödenmemiş faturalar vade kovalarında (gecikmiş/bu hafta/8-30/31+) doğru mu?
- ⬜ **AI Nakit Danışmanı** tavsiye veriyor mu?
- ⬜ **Ödendi işaretle** (fatura/tümü) → aging'den düşüyor mu?
- 🔧 **Not:** Borç = ödenmemiş alış faturaları; vade_tarihi veya tedarikçi vade gününe göre.

---

## 🟡 TIER B

### B1 · Çok-Kademeli Onay + Vekil (flag'li)  →  Menü: **Stok & Tedarik → Onay Seviyeleri**
- ⬜ **Aç/Kapa** toggle çalışıyor mu?
- ⬜ Kademe ekle (onaylayan + vekil + tarih) → zincir görünüyor mu?
- ⬜ Kademe sil → sıralama düzeliyor mu?
- 🔧 **Not:** Küçük işletmede KAPALI bırak. Config hazır; tam talep-akışı zorlaması sonraki faz.

### B3 · AI Sosyal Medya  →  Menü: **Sadakat & Müşteri → AI Sosyal Medya**
- ⬜ Konu yaz + platform seç → **AI Yaz** → caption+hashtag geldi mi?
- ⬜ Düzenle → **Kopyala** / **Taslak Kaydet** çalışıyor mu?
- 🔧 **Not:** Otomatik Instagram/Facebook paylaşımı Meta Graph API gerektirir (sen bağlarsın). Şimdilik AI üretir, kopyalayıp paylaşırsın.

### B4 · Happy Hour  →  Menü: **Menü & Ürünler → Happy Hour**
- ⬜ Kural ekle (ürün/kategori + saat + gün + indirim%/sabit fiyat).
- ⬜ POS'ta o saat/gün içinde ürün eklenince **fiyat otomatik düşüyor mu?** (gerçek test: kuralı şimdiki saate ayarla, POS'tan ürün ekle)
- ⬜ Durdur/Aç/Sil çalışıyor mu?

### B5 · Fix Menü / Set Menü  →  Menü: **Menü & Ürünler → Fix Menü**
- ⬜ Set menü oluştur (ad + fiyat) → kurs grupları + ürün ekle (zorunlu/opsiyonel).
- ⬜ Listede görünüyor, sil çalışıyor mu?

---

## ⚪ TIER C

### C1 · Müstahsil Makbuzu  →  Menü: **Stok & Tedarik → Müstahsil Makbuzu**
- ⬜ Makbuz ekle (üretici + ürün + miktar + b.fiyat + stopaj%) → **brüt/stopaj/net** doğru hesaplanıyor mu?
- ⬜ Liste + toplamlar doğru mu?
- 🔧 **Not:** Güncel stopaj oranını mali müşavirinle teyit et (varsayılan %2).

### C2 · Fiyat Anlaşmaları  →  Menü: **Stok & Tedarik → Fiyat Anlaşmaları**
- ⬜ Anlaşma ekle (tedarikçi + malzeme + fiyat + dönem).
- ⬜ Durdur/Aç/Sil çalışıyor mu?

### C3 · Demirbaş / Ekipman  →  Menü: **Stok & Tedarik → Demirbaş / Ekipman**
- ⬜ Demirbaş ekle (ad + bedel + lokasyon + zimmet).
- ⬜ Durum değiştir (aktif/bakımda/hurda).
- ⬜ **Bakım/Arıza** kaydı ekle → geçmiş görünüyor mu?
- ⬜ Toplam ekipman değeri doğru mu?
- 🔧 **Not:** Vergi amortismanı DEĞİL (mali müşavir işi) — operasyonel ekipman takibi.

### C4 · Personel Yemeği  →  Menü: **Sadakat & Müşteri → Personel Yemeği**
- ⬜ Kayıt ekle (personel + tutar + açıklama).
- ⬜ Aylık toplam + kişi bazlı özet doğru mu?
- ⬜ Ay filtresi çalışıyor mu?

---

## 🔑 GENEL
- Tüm AI özellikleri: **kural/şablon fallback** var (AI kapalıyken de çalışır). Tam AI metin için **RESTEOS_PATRON_AI_V2=true + Anthropic bakiye**.
- Maliyet: metin AI ucuz + önbellekli; **aboneliğe dahil** verilebilir (Elektra bunları parayla satıyor = koz).
- Flutter (mobil/masaüstü) karşılıkları henüz YOK — web'de doğruladıktan sonra taşınabilir.

## ⚙️ Kurulum gerektirenler (özet)
1. **İtibar:** Google yorum linki + uyarı WA no (Ayarlar'dan)
2. **Kampanya + İtibar WA uyarı:** WhatsApp bridge + kontör
3. **Sosyal Medya oto-paylaşım:** Meta Graph API (opsiyonel, sen bağlarsın)
4. **AI tam metin:** RESTEOS_PATRON_AI_V2=true + Anthropic bakiye (zaten sunucuda key var)

# ElektraWeb ↔ ResteOS — Rakip Karşılaştırma Raporu

> Kullanıcının gönderdiği ElektraWeb ekran görüntüleri incelenerek hazırlanıyor.
> Her grup geldikçe bu dosyaya eklenecek; en sonda final kıyas + stratejik özet.
> İncelenen müşteri: **Mistral Montiano Restoran** (HOTELID 29766), ElektraWeb v18.0.777

---

## ⚠️ KRİTİK TESPİT (Grup 1 — Genel Ekran)
**ElektraWeb aslında bir OTEL Yönetim Sistemi** ("elektraweb · Otel Yönetim Sistemi"), restorana uyarlanmış. Bu çok önemli çünkü modüllerin önemli kısmı OTEL odaklı (bizim için ilgisiz), restoran-ilgili kısmı ayıklamak gerekiyor.

---

## GRUP 1 — Genel / Modül Keşif Ekranı

### A) Sol Sidebar (ana modüller)
| Elektra Modülü | Açıklama | ResteOS Karşılığı |
|---|---|---|
| Menüde Ara | menü arama kutusu | ✅ var (panel arama) |
| **Fatura** | fatura yönetimi | 🟡 kısmi (e-Arşiv/e-Fatura altyapısı, mali fiş, muhasebe entegrasyon) |
| **Demirbaş** | sabit kıymet/ekipman takibi | ❌ YOK (demirbaş/amortisman modülü yok) |
| **Stok ve Envanter** | stok | ✅ GÜÇLÜ (stok_hareketleri, reçete patlatma, sayım, FEFO, yarı mamül, barkod) |
| **Satınalma** | satın alma | ✅ ÇOK GÜÇLÜ (RFQ+AI+PO+mal kabul+3-way+tedarikçi karne+AI asistan+Excel) |
| **POS** | satış noktası | ✅ GÜÇLÜ (masa/adisyon/mutfak/bar/QR) |
| Digital Concierge - Viofun | otel misafir concierge | 🟡 kısmi (resto-müşteri app + QR AI asistan) |
| Kullanıcı Bilgileri | kullanıcı yönetimi | ✅ var (personel+yetki+departman) |
| **Marina Yönetimi** | marina/tekne | ❌ YOK (restoran için ilgisiz — otel/marina özelliği) |
| Raporlar | raporlar | ✅ var (dashboard+çeşitli raporlar) |
| Kayıt Onaylama Yönetimi | kayıt onay akışı | ✅ var (yönetici onay + AI eylem onay zinciri) |

### B) Üst yatay bar (hızlı erişim)
Yedek · Muhasebe Yedek · Destek · Bize Bildir · Raporlar · Online · Ön Kasa · POS · Muhasebe · Stok · Filtre · Dil(TR) · Kilit · Çıkış
→ ResteOS: benzer hızlı erişim var; "Yedek/Muhasebe Yedek" ayrı buton olarak bizde yok (otomatik), "Bize Bildir/Destek" ticket sistemi bizde sistem-yönetim tarafında var.

### C) 19 "Yapay Zekâ Destekli Modül" (ana pazarlama kozu)
Kategoriler: Kontrol(4) · Entegrasyon(4) · Yönetim(6) · Marketing(4) · Mobil(2) · Operasyonel(5) · Satışlar(8) · Tasarruf(4)

| # | Elektra AI Modülü | Tip | ResteOS Karşılığı |
|---|---|---|---|
| 1 | Online Booking | otel rezervasyon | ❌ (otel) — bizde rezervasyon var ama otel-booking değil |
| 2 | Kiosk Check-in / Smart ID | otel kiosk | ❌ (otel) |
| 3 | **Akıllı WhatsApp** | WA kanal | ✅ VAR (WA sipariş + AI + devral) |
| 4 | Misafir Mobil Uygulama | otel misafir app | 🟡 resto-müşteri app (farklı amaç) |
| 5 | **Akıllı Santral** | AI telefon santrali | ✅ VAR (AI Santral Asterisk, rezervasyon+sipariş) |
| 6 | Acente Bonus / Sadakat | otel acente | 🟡 sadakat/çark bizde var (acente değil) |
| 7 | Tur Operatörü Entegrasyonu | otel | ❌ (otel) |
| 8 | **İtibar Yönetimi** | yorum/review yönetimi | ❌ YOK (eklenebilir — değerli) |
| 9 | **Yapay Zeka ile Kalite Yönetimi** | kalite/HACCP | 🟡 kısmi (kalite departman ekranı + HACCP ölçüm, AI yok) |
| 10 | Karbon Ayak İzi Sıfırlama | sürdürülebilirlik | ❌ YOK |
| 11 | **Şirketim · İK ve Kalite Mobil** | İК mobil | ✅ VAR (İК self-servis + İК AI asistan + risk radarı) |
| 12 | Banka Entegrasyonları | banka | 🟡 kısmi (muhasebe entegrasyon, ödeme) |
| 13 | Kurumsal SI | kurumsal | ❓ belirsiz |
| 14 | **Social Media Manager** | sosyal medya | ❌ YOK (eklenebilir) |
| 15 | **SmartMenü · Akıllı Restoran Menüsü** | QR menü | ✅ VAR (masa QR + müşteri AI asistan) |
| 16 | Rate Manager | otel fiyat yönetimi | ❌ (otel) |
| 17 | Elektraweb MCP | otomasyon/entegrasyon | 🟡 (AI çekirdek + API var, MCP değil) |
| 18 | **Akıllı Web Site Designer** | site üretici | ❌ YOK |
| 19 | **Akıllı Dinamik Fiyat** | dinamik fiyat | ❌ YOK (daha çok otel; restoranda happy-hour olabilir) |

### D) İlk İzlenim — Stratejik
- **Elektra'nın kozu:** 19 AI modül = GENİŞLİK (çok kanal/entegrasyon: WhatsApp, santral, sosyal medya, itibar, dinamik fiyat, web tasarım). Pazarlama dili güçlü ("tek ekosistem, 19 AI modül").
- **ResteOS'un kozu:** AI DERİNLİĞİ operasyonda (satınalma AI döngüsü, patron AI, İК AI, departman denetimi, kural+önbellek maliyet modeli). Onların AI'ı çoğunlukla kanal/entegrasyon; bizimki operasyonel zekâ.
- **Çoğu modül OTEL** (booking/kiosk/rate/marina/tur/acente) → restoran kıyasında bunlar düşer.
- **Bizde net eksik (restoran-ilgili):** İtibar/yorum yönetimi, Social Media Manager, Web Site Designer, Demirbaş takibi, Dinamik fiyat. Bunlar değerlendirilebilir.
- **Bizim net üstünlük:** Satınalma AI döngüsü, departman-bazlı AI, operasyonel AI derinliği, maliyet-kontrollü AI (onların fiyat/maliyet modeli belirsiz).

---

## GRUP 2 — Tam Menü Ağacı (tüm alt sayfalar)

### ⚠️ Genel gözlem
Menünün **~%60'ı OTEL raporları** (rezervasyon/doluluk/forecast/küp analizi/kat hizmetleri/folyo). Restoran kıyasında bunlar GÜRÜLTÜ — "feature sayısı" şişkin görünüyor ama restorana ilgisiz. Restoran-ilgili çekirdek: **Fatura, Demirbaş, Stok, Satınalma, POS** + bir kısım CRM/Görev/Muhasebe.

### 📁 FATURA (Invoice)
Tüm Faturalar · Stok Faturaları · **Hesap Planı** · Proforma Fatura · Hızlı Fatura(+Yeni) · Kasa Fişleri · **Müstahsil Makbuzu** · **Satış Fiyat Anlaşmaları(+Detayları)** · E-Fatura Tanımları
- ResteOS: e-Arşiv/e-Fatura altyapısı + muhasebe entegrasyon (Logo/Netsis CSV) var.
- **EKSİK bizde:** Müstahsil Makbuzu (çiftçiden/halden alışta vergi belgesi — TR restoranı için önemli), Proforma, Hesap Planı UI, Satış Fiyat Anlaşmaları.

### 📁 DEMİRBAŞ (Fixed Assets) — ❌ BİZDE KOMPLE YOK
Demirbaş Kartları · Ana Kart Tanımları · Barkod Listesi · Sayımları · Ana İşlemler · **Amortisman İşlemleri** · Raporlar · Tanımlar
- Tam bir sabit kıymet + amortisman modülü. ResteOS'ta hiç yok. **Gerçek eksik.**

### 📁 STOK VE ENVANTER — ✅ GÜÇLÜ PARİTE
Stoklar⭐ · Depo Toplamları · Satıcılar · İrsaliyeler⭐ · Stok Faturaları · Fişleri · **Şube Transfer** · Üretim İşlemleri · **Tüketim Proses İşlemleri** · Sayımlar · **Stok Bütçe Analizi** · Talep Fişleri · Satınalma Durum Listesi · Beklenen Teslimatlar · Kontrol Listeleri · Tanımlar · **Tedarik Portalı Fiyatları**
- ResteOS: stok_hareketleri, reçete patlatma, sayım, FEFO, yarı mamül üretim partisi, barkod, çok-şube → **güçlü parite.**
- 🟡 Netleştir: Şube Transfer (bizde var mı tam?), Tedarik Portalı Fiyatları (dış fiyat beslemesi — bizde Hal Fiyatları var, benzer).

### 📁 SATINALMA — ✅ PARİTE + bazı olgun eklentiler
**Operasyon:** Yeni Talep · Satınalma Yönetimi · Beklenen Teslimatlar · Alış Faturaları · **Satıcı Yaşlandırma** (borç yaşlandırma) · Benim Taleplerim · Benim Onaylarım · **Talep Soru ve Cevapları** · Satıcı Detaylı Sipariş Listesi · **Akıllı Talep Sistemi** · **Günlük Menülerden Otomatik Talep Yaratma**
**Analizler:** Fiyat Anlaşma Detayları(+Analiz) · Satınalma Durum Analizi · Teklif Analizi(1,2) · Satınalma Süreç Raporu
**Tanımlar:** Satıcılar · Stok Fiyat Anlaşmaları(+Detay Raporu) · **Satınalma Onay Seviye Tanımları** · Markalar · Satınalma Stok Grupları · **Satıcılar için Teklif Girişi** · Stok Satınalma Tipi · **Onaylara Vekil Atama**
- ResteOS: RFQ+AI+PO+mal kabul+3-way+tedarikçi karne+AI asistan+Excel → **çekirdek parite, AI'da biz öndeyiz.**
- **EKSİK/zayıf bizde:** (a) **Çok seviyeli onay tanımları + Onaylara Vekil Atama** (onaylayan tatildeyse vekil), (b) **Satıcı Yaşlandırma** (borç vadeye göre yaşlandırma raporu — bizde cari var, yaşlandırma netleştir), (c) **Fiyat Anlaşmaları** (sözleşmeli sabit fiyat/dönem), (d) **Günlük Menüden Otomatik Talep** (günün menü planından ihtiyaç → talep; bizimki kritik stoktan auto-fill, menü-planından değil), (e) **Talep Soru-Cevap** (talep üzerine yazışma).
- ❓ Görülecek: "Akıllı Talep Sistemi" ekranı — onların AI satınalması ne kadar derin?

### 📁 POS — ✅ GÜÇLÜ PARİTE + bazı olgun eklentiler
**Operasyon:** Hızlı Kurulum · Ürün Kartları · Ürün Grup · Adisyon Listesi · **Faturası Kesilmemiş Adisyonlar** · **E-Adisyon Kontrol** · Ürün Fişleri · Satış Detayları · İade Edilen Ürünler · Satış Reçete Detayları · Günlük Toplamlar · Rezervasyon Listesi · Günlük Döviz Kurları
**Üyeler:** Üye Kartları · Misafirler · Ek Kartlar · Üye Kart İşlemleri · İndirim Grup · Üye Raporları
**Gelişmiş Ürün:** **Fix Menü Grubu** · **Departmanlara Göre Ürünler**(+Analizi+Excel) · Ürün Ek Özellikleri · **Happy Hour Tanımları** · Pos Porsiyon · Reçete Detayları · #Barcodes#
**Yazıcı:** Adisyon/Sipariş Yazdırma Kuyruğu · Departman/Masa Grup Yazıcı Tanımları
**POS Logları (zengin denetim):** Masa No Değişenler · Error · Ürün Transfer · Masa Birleştirme · Masa Transfer · Kapalı Çek · Yazdırılamayan Sipariş · Silinen İşlemler · Printer Error
**Yazar Kasa (ÖKC) derin:** Model · Tanımlar · KDV · Gelir Grupları · Eşleştirme · İşlem Logları · İşlemleri · Cihazı
**Online Sipariş Entegrasyonu:** Ayarlar · Menüler · Siparişler
**POS Tabldot:** Satış · Rapor · Analiz · Görevli Personel (açık büfe/tabldot)
**Tanımlar (çok geniş):** Pos Ayarları · Yetki Grubu · Kasiyer/Garson · **Departman Bazında Çalışanlar** · Masa Tanımları(+Detaylı) · **Departmanlar ve Bölgeleri** · Pos Departman/Garson Grup Konfig · İndirim/Üyelik Grubu · **Fiyat Kodu** · **İndirim/Servis Yüzdesi** · **Aylık Bütçe** · Pos Masa Düzeni · **Ekstra Hizmetler** · Pos Yönlendirme
- ResteOS: masa/adisyon/mutfak/bar/QR/çoklu kasa/salon şema/departman ürün/barkod → **güçlü parite.**
- **EKSİK/zayıf bizde:** (a) **Happy Hour / zaman-bazlı fiyat**, (b) **Fix Menü / Tabldot** (açık büfe/sabit menü satışı — otel-restoran için), (c) **Üyelik/Üye Kartı** sistemi (bizde sadakat+çark var, kart-bazlı üyelik netleştir), (d) **Online Sipariş Entegrasyonu** (Yemeksepeti/Getir aggregator — bizde kendi WA/QR kanalı var, aggregator entegrasyonu?), (e) **ÖKC derin entegrasyon** (bizde GMP-3 stub; onlarda tam), (f) **E-Adisyon** kontrol.
- ✅ Bizde FAZLA/denk: Salon Şema dijital ikiz, departman-bilinçli ekranlar, çoklu kasa, masa renk/ısı haritası, kurye takip — bunlar onların menüsünde görünmüyor.

### 📁 KULLANICI BİLGİLERİ > Talep ve Onaylar
Yeni Talep · Benim Taleplerim/Onaylarım · **Direkt Transfer Edilen Taleplerim** · **Departmandan Şef Onayı Bekleyen** · **Satınalma Cost Kontrol Onay** · **Satınalma Ana Depo Onay** · Stok Transfer Fişi Onay · Depo-bazlı talepler
+ Yorum ve Aksiyonlar · Hatırlatmalar · **ElektraWeb Nasıl Kullanılır?** · **Genel Eğitimler** · Eğitim & Planlama İsteği
- **Önemli:** Çok-kademeli onay zinciri (şef → cost kontrol → ana depo). ResteOS onay var ama bu kadar katmanlı/rol-bazlı değil → **geliştirilebilir.**
- **Uygulama-içi Eğitim/Yardım** modülü (nasıl kullanılır + genel eğitimler) → ResteOS'ta yok, **satış/onboarding için değerli.**

### 📁 RAPORLAR (dev — çoğu OTEL, gürültü)
- **OTEL (ilgisiz):** Günlük Raporlar (misafir/konaklama/giriş-çıkış), Rezervasyon Raporları, Forecast, Küp Analizleri, Kat Hizmetleri, Günlük Kontrol (oda fiyat) → onlarca rapor ama restorana ait değil.
- **Restoran-ilgili:**
  - **Gelir Raporları:** Günlük Departman/Gelir Toplamları, Departman Gelir Analizi, Ön Kasa, Maliye Listesi → ResteOS dashboard+Z raporu+finans ile denk.
  - **Görev Yönetimi Raporları:** Görev Yönetimi, **Geciken İşler**, İş Masraf, Ortalama Bitiş Süreleri, Tekrarlanan İşler → ResteOS Grup B departman iş ekranları (görev/kontrol/olay/arıza) ile kısmi parite; rapor derinliği onlarda fazla.
  - **CRM Raporları:** Misafir Kartları, **Misafir Yorumları**, **ReviewPro/TrustYou** (itibar entegrasyonu), **IYS Onay** (SMS izin — yasal), Misafir Etiketleri, En Fazla Harcayan, Repeat analizleri → ResteOS rezervasyon-CRM+sadakat var; **itibar/yorum entegrasyonu ve IYS izin yönetimi eksik.**
  - **Muhasebe Raporları:** Fiş/Bakiye/Çek-Senet/Tahsilat/**AFAS Entegrasyonları** → ResteOS muhasebe entegrasyon (Logo/Netsis) ile denk.
  - **Stok/POS Raporları:** Ürün satış/maliyet/ciro raporları → ResteOS ile denk.

### 🔑 GRUP 2 — Net Çıkarımlar
**Bizde GERÇEK EKSİK (restoran-ilgili, öncelikli):**
1. **Demirbaş + Amortisman** modülü (komple yok)
2. **Çok-kademeli satınalma onayı + Onaylara Vekil Atama** (şef→cost→depo zinciri, vekil)
3. **Müstahsil Makbuzu** (TR vergi belgesi — hal/çiftçi alışı)
4. **Satıcı Yaşlandırma** (borç vade yaşlandırma raporu)
5. **Fiyat Anlaşmaları** (sözleşmeli sabit/dönemsel tedarikçi fiyatı)
6. **Happy Hour / zaman-bazlı fiyat** + **Fix Menü/Tabldot**
7. **İtibar/Yorum yönetimi** (Google/TripAdvisor review) + **IYS izin yönetimi**
8. **Online Sipariş Entegrasyonu** (Yemeksepeti/Getir aggregator)
9. **Uygulama-içi Eğitim/Yardım** modülü (onboarding — satış kozu)
10. **Günlük menü planından otomatik talep yaratma**

**Bizim ÜSTÜNLÜK:**
- AI operasyonel derinlik (satınalma AI döngüsü + asistan + Excel, patron/İК/departman AI, kural+önbellek maliyet modeli)
- Salon Şema dijital ikiz, departman-bilinçli ekranlar, çoklu kasa, masa ısı/renk, kurye canlı takip, çark/sadakat, AI Santral, WA sipariş
- Tek ürün restoran-odaklı (onlarınki otel-uyarlaması → restoran için şişkin/dağınık menü)

**Not:** Elektra'nın "Akıllı Talep Sistemi" + "Günlük Menülerden Otomatik Talep" + AI modülleri ekranlarını görmek lazım — AI derinliğini net kıyaslamak için.

---

## GRUP 3 — AI Santral (HotelCall) + FİYATLANDIRMA ARAŞTIRMASI

### A) Akıllı Santral = HotelCall (hotelcall.ai), **Google Gemini Live** üstüne kurulu
- 97 dil = "Gemini Live voice model'in her dili". Canlı transkript+çeviri, dil oto-algıla/değiştir, duygu analizi, niyet, özet — hepsi **Gemini Live**'dan geliyor (kendi yazmamışlar).
- Kanal: telefon + tarayıcı + WhatsApp + Telegram + SMS + web widget + oda QR.
- Görüşme içi ödeme linki (Stripe/Garanti BBVA, WA/SMS), Elektraweb PMS canlı fiyat/rezervasyon, insana tarayıcı-devir, ses kaydı (opsiyonel+bildirim), simüle misafir testi (QA).
- **Early access** (herkese açık ses örneği yok); kişiye özel demo.

**Bizim AI Santral (Asterisk + Google STT + Haiku + Google TTS) ile fark:** çekirdek yakın (AI açar, rezervasyon alır, devreder). Eksik: canlı transkript paneli, ses kaydı (aç), 97 dil oto-algıla, duygu, görüşme-içi ödeme linki, web devir konsolu.
**Nasıl yaparız:** santral sidecar'ında "STT→Haiku→TTS" hattını **realtime sesli API'ye** (Gemini Live/OpenAI Realtime) taşı → 97 dil+canlı transkript+duygu tek hamlede; DB mantığı tool-calling ile. + Asterisk MixMonitor (kayıt) + ödeme linki + web konsol.
**⚠️ MALİYET:** Sesli santral = kural+önbellek ÇALIŞMAZ (her arama benzersiz) + realtime audio token PAHALI → en pahalı AI kalemi. Dakika-başı metrele + süre limiti + sadece kaçan aramada. **Ayrı paketle.**

### B) 🎯 FİYATLANDIRMA — Elektra'nın AI'ı EKSTRA ÜCRETLİ (standart DEĞİL) — DOĞRULANDI
Deep-research (18 kaynak, 23 doğrulanmış iddia, 3-oylu çapraz doğrulama):
1. **AI modülleri = ücretli, lisanslı EKLENTİ.** Kanıt: İtibar Yönetimi resmi yardım dokümanı: *"lisanslamaya tabi ve ücretli bir eklenti"* (yardim.elektraweb.com/itibar-yonetimi). Boilerplate → tüm AI modülleri aynı.
2. **Modül-bazlı, oda-başı lisanslama:** Standart ₺240 taban+₺2/oda/ay, Medium ₺240+₺1, POS ₺480+₺1 (bayi destekelektra.com). Bayi listesinde AI YOK → AI ayrı pazarlıkla.
3. **Giriş PMS ucuz ama AI DAHİL DEĞİL:** Mini €360/yıl (≤40 oda, e-fatura bile hariç), €25/ay (Capterra). AI hiç yok.
4. **Ayrı €1/oda/yıl API-entegrasyon ücreti** (min €120/yıl).
5. **AI maliyet yapısı HİÇ açıklanmıyor** — LLM/kota/kredi/kullanım hiçbiri yok, hepsi satış-gated, opak. (elektraweb.com/yapay-zeka... fiyat yok; /fiyatlandirma 404.)
6. **Tek somut AI fiyatı:** Winnobot WhatsApp AI = **sabit $49/ay** (+$349/yıl/$1299 ömürlük) → metered kredi değil, sabit kanal ücreti. (3. parti olabilir, yönsel.)
7. **Akıllı WhatsApp = Meta WhatsApp Cloud API** (3. parti altyapı) üstünde.
8. **Zayıf nokta:** şikayetvar'da "entegrasyon sorunları, yetersiz destek, pişmanlık".

### C) 💥 BİZİM STRATEJİ ÇIKARIMI
- **Fırsat:** Elektra AI = her modül ayrı para + gizli fiyat + à-la-carte. Biz = **aboneliğe dahil, şeffaf, fair-use.** Satış kozu: "Onlar her AI için ayrı para ister ve fiyatı gizler; biz AI'ı dahil veriyoruz."
- **Fiyat modeli önerisi:** Metin AI (patron/İК/satınalma/departman) → **abonelige DAHİL fair-use** (kural+önbellek ~200 TL/ay tavan, bedava verebiliriz). **AI Santral (sesli)** → ayrı "AI Santral paketi" (aylık X dk dahil + aşım dk-başı). Elektra bile WA AI'ı ayrı $49 yapıyor; sesli en pahalısı, ayrı fiyatlamak adil+sürdürülebilir.
- Kaynaklar: yardim.elektraweb.com/itibar-yonetimi, destekelektra.com/fiyatlistesi, elektraweb.com/{yapay-zeka-destekli-otel-yonetimi, api-integrations, whatsapp-api, elektraweb-mini-yillik}, Capterra, hotelcall.ai.

---

## GRUP 4 — Ekran içerikleri (içi ne kadar dolu?)

### 🔍 "Akıllı Talep Sistemi" = AI DEĞİL, min/optimum par-reorder tablosu
- URL: `app/grid/stock-auto-order`. Kolonlar: Şube·Depo·Stok Grup·Stok·Birim·**Optimum·Minimum·Bakiye·Son Fiyat·Son Satıcı**.
- Mantık: bakiye Minimum altına düşünce Optimum'a tamamlayacak talep önerir. **LLM/tahmin/duygu YOK** — klasik min-max reorder. "Akıllı" = pazarlama etiketi.
- **Bizde zaten var + fazlası:** `kritik_stok`(min) + `hedef_stok`(optimum) + bakiye + son fiyat/satıcı = eşiti. ÜSTÜNE: gerçek AI asistan (LLM), AI auto-fill (/acil-siparis-hazirla), RFQ+AI teklif okuma, Excel. **Biz öndeyiz.**
- **KRİTİK ÇIKARIM:** Elektra "19 AI modülü" pazarlıyor ama bu "akıllı" ekran basit kural tablosu → muhtemelen diğer "akıllı"ların bir kısmı da böyle (etiket, gerçek LLM değil). Satış kozu: "onların 'akıllı'sı min-max tablosu; bizimki gerçekten konuşan/analiz eden AI."

### 🔍 "Günlük Menülerden Otomatik Talep Yaratma" (`app/grid/posmenuplan`)
- Kolonlar: Tarih·Şube·Depo·Menü Adı·Menü Açıklaması·Fiyat·**Toplam Maliyet·Forecast Kahvaltı·Forecast Öğlen·Forecast Akşam**.
- Ne yapar: günlük menü planı + öğün-başı kişi tahmini → reçete patlatma → malzeme ihtiyacı → OTOMATİK satın alma talebi. **İleriye dönük/öngörücü** planlama (min-max'in aksine).
- **Otel/pansiyon mantığı** (sabit günlük menü + doluluk-forecast). À la carte restorana birebir UYMAZ.
- **GERÇEK EKSİK bizde:** öngörücü ihtiyaç planlaması yok (reaktif kritik/hedef stok var).
- **FIRSAT (onları geçeriz):** restoran versiyonu = **AI Talep Tahmini** — POS satış geçmişi (+hafta günü/trend/etkinlik) → satış tahmini → reçeteyle malzeme → proaktif sipariş taslağı. Bu GERÇEK forecasting (onların doluluk-forecast'inin ötesi). Altyapı var (reçete patlatma+POS geçmişi+tedarikçi/fiyat); eksik= tahmin motoru. Restoran yazılımı vizyonundaki "talep tahmini" bu.

### 🔍 İtibar/Yorum Yönetimi (rm.elektraweb.com) — GERÇEK AI, olgun
- Çok platform yorum toplama (Google/TripAdvisor/Booking vb.) → AI duygu + **cümle cümle ayrıştırma** + **departmana atama** (kahvaltı→Mutfak, klima→Teknik) + çeviri + **AI yanıt taslağı (misafir dilinde)** + kritik filtre/öncelik + yanıt oranı. Analitik: 0-100 skor, trend, departman kırılım, **rakip benchmark**, 16 kriter, PDF rapor. Bildirim: günlük özet/yeni yorum/sadece-olumsuz. Rol-bazlı pano. Elektraweb SSO.
- **Bu min-max DEĞİL, hakiki AI + olgun.** Bizde HİÇ YOK (gerçek eksik).
- **AMA mimarimize mükemmel oturur:** departmana atama↔departman sistemimiz, duygu+yanıt↔AI çekirdek, kötü yorum uyarı↔FCM/WA, haftalık özet↔patron brief, rol pano↔yetki sistemi.
- **Maliyet ÇOK DÜŞÜK** (metin AI + düşük hacim ~200 yorum/ay ≈ $1-2) → **aboneliğe DAHİL edebiliriz** (Elektra parayla satıyor + çoğu müşteride YOK — ödeme yapan Mistral Montiano'da bile aktif değil!). **Büyük satış kozu.** Tek bağımlılık: Google Business Profile API (ücretsiz, OAuth).

### 🔍 AI Social Media Manager (ai-social-media-manager.ornek.online — DEMO site)
- Marka sesi öğren → içerik üret (caption/hashtag/carousel/reels) + onay + oto-paylaş (Instagram+LinkedIn). Marka hafızası, RAG bilgi bankası (PDF/web→uydurma engelle), Media Studio (foto→9:16 reels video), onay akışı, takvim/zamanlama.
- AI tech: Claude+OpenAI+Gemini (metin) + Replicate/Runway/Luma/Kling (video), **BYOK (müşteri kendi API key'i)** ← maliyet müşteriye yıkılıyor.
- `ornek.online` = demo/3.parti white-label olabilir (Elektra'nın kendi ürünü belirsiz).
- **Bizde yok.** Metin üretimi bize kolay/ucuz (Haiku); zor/pahalı: video gen (3.parti $) + paylaşım (Meta Graph+LinkedIn API). **Yalın plan:** menü/fotoğraftan AI post→onay→IG/FB paylaş; video sonra/BYOK. Orta-yüksek efor, yüksek pazarlama değeri.

### 🔍 Akıllı Dinamik Fiyat — ⚠️ %100 OTEL, restorana UYMAZ (ATLA)
- Otel gelir yönetimi (RMS): rakip otel fiyatı+doluluk+geçen yıl+destinasyon uçuş+festival/kongre → ağırlıklandır (model güveni %94) → oda tipi önerilen fiyat → OTA kanalları (Booking/Expedia/Tur Op). Gerçek AI ama **tamamen otel** (oda/doluluk/uçuş/OTA). Restoran karşılığı yok.
- **Verdict: restoran için ATLA.** Sliver=happy-hour/durgun-gün promo (düşük öncelik). Otel pazarına girilirse revisit.

### 🔍 SmartMenü (QR dijital menü) — bizde VAR/ÖNDE
- Bizde masa QR + müşteri AI asistanı + sesli zaten var. İçi görülürse derinleştirilir.

### 📌 GRUP 4 GENEL DERS
Elektra "19 AI modülü" karışık: **bazısı etiket/kural** (Akıllı Talep=min-max), **bazısı gerçek AI** (İtibar Yönetimi, Dinamik Fiyat, Social Media). Gerçek-AI olanların çoğu ya **otel-özel** (Dinamik Fiyat) ya da **bizde zaten var/yakın** (WA, Santral, SmartMenü) ya da **ucuz+kurabiliriz** (İtibar, Social Media). AI maliyet deseni: **BYOK veya sabit kanal ücreti** (müşteriye yık/yumuşat) — bizim embedded fair-use daha cömert ve koz.

### 🔍 Satıcı Yaşlandırma (`app/record/account-aging-forecast`) — finans, gerçek eksik, kolay
- Cari yaşlandırma (AR/AP aging): tedarikçi/müşteri bakiyesini vade gününe göre **15 günlük kovalara** böler (geçmiş: 0-15/16-30/31-45/46-60/61-90/91+ gecikmiş; gelecek: aynı kovalar). Alıcılar/Satıcılar filtresi, döviz, **"Ödeme Planı Oluştur"**. NOT AI.
- Bizde: yürüyen bakiye ekstresi VAR, **kova-bazlı yaşlandırma + ödeme planı YOK** → gerçek eksik.
- Kolay-orta efor (veri var: alış faturaları+vade+ödemeler, SQL gruplama). **Bizim fark:** üstüne AI nakit-akış önceliklendirmesi (patron AI) → "onlar rapor verir, biz akıl verir".

### 🔍 Demirbaş / Sabit Kıymet (`app/grid/fixasset-*`) — tam modül ama DÜŞÜK ÖNCELİK
- Tam kapsam: Demirbaş Kartları+Ana İşlemler (giriş/çıkış/transfer), Barkod, Sayımlar, **Amortisman**, Ömür Kodları, **Yeniden Değerleme** (TR enflasyon muhasebesi), Lokasyon Toplamları, Devirli, Oda Demirbaş (otel). NOT AI.
- Bizde KOMPLE YOK ama **önceliği düşük:** (1) muhasebe işi — çoğu restoran muhasebeciye Logo/Luca'da yaptırır; (2) restoranda demirbaş az (otelin aksine: her oda × onlarca); (3) tam muhasebe demirbaşı orta-yüksek efor, düşük farklılaşma.
- **Yaparsak YALIN:** ekipman kayıt defteri (kart+barkod+basit doğrusal amortisman) + **bakım/arıza kaydına bağla** (Grup B departman arıza/bakım zaten var) + fiziki sayım↔İК zimmet (personel_zimmet zaten var). Muhasebe demirbaşından çok **ekipman/bakım yönetimi** → bizim yapıya oturur.

### 🔍 Satınalma Onay Seviye Tanımları (`app/grid/purchase-approval-level`) — çok-kademeli onay
- Yapılandırılabilir N-kademeli onay zinciri: Seviye 1(mert)→2(Sedat, Seviye Yöneticisi). Her kademe=onaylayan kullanıcı, **şube/depo bazlı**, **"Anlaşması Olanlar İçin Onayı Atla"** (sözleşmeli fiyat→onay atla), Ön Onay/Onay aşamaları. Asıl onay "Benim Onaylarım/Cost Kontrol/Ana Depo" ekranlarında yürür.
- Bizde: tek-kademe onay + fiyat re-onayı (P5) VAR; **ayarlanabilir N-kademeli zincir + şube/depo onaylayan + anlaşma-atla + vekil atama YOK** → gerçek eksik.
- Orta efor (onay_seviyeleri tablosu + kademeli yönlendirme; onay_istekleri+FCM var). **Opsiyonel/flag'li** (tek-sahipli küçük restoranda gereksiz; çok-şube/kurumsal için şart). Kolay kazanç: anlaşma-atla + vekil atama. Öncelik #4.

### 🔍 Happy Hour Tanımları (`app/grid/pos-pos-happyhour`) — zaman-bazlı fiyat, restoran-ilgili
- Ürün/departman bazında otomatik promosyon: tarih aralığı + **saat aralığı** (17:00-19:00) + geçerli günler + indirim%/happy fiyat. POS o saatte otomatik uygular.
- Bizde: manuel indirim/kampanya VAR, **otomatik zaman-pencereli happy hour YOK** → küçük-orta eksik, restoran-ilgili (bar/pub için değerli).
- Düşük-orta efor: happy_hour tablosu + POS "şu an happy hour mu?" kontrolü. Departman(bar)+indirim sistemine oturur. Öncelik 🟡 orta.

### 🔍 acenta.online (Tur Operatörü Entegrasyonu) — %100 OTEL, ATLA
- 40+ tur operatöründen otel rezervasyonu otomatik çekme (voucher/oda envanteri/stop-sale/kontrat fiyat). Restoranla alakasız.

### 🔍 Fix Menü Grupları (`app/grid/pos-fix-menu-group`) — set/sabit menü, EKSİK
- Prix-fixe/set menü kurucusu: kurs grupları (İftar/Ana Yemek/Tatlı/İçecek...) + "Zorunlu mu?" flag. Müşteri her zorunlu gruptan seçer→sabit fiyat. Kullanım: iftar/set/banket/pansiyon. "POS Tabldot" ile aynı aile. (Pos Kart Fix Menü 2. ekran=yetki yok, görülemedi.)
- Bizde à la carte+reçete VAR, **set/combo menü kurucusu YOK** → eksik (iftar/etkinlik/prix-fixe senaryosu).
- Orta efor: fix_menu tablosu(kurs grup+zorunlu+ürünler+sabit fiyat)→POS set menü+reçeteyle stok. **Bizim üstünlük:** rezervasyon/etkinlik+departman mutfağa bağla→daha entegre. Öncelik 🟡 orta.

### 🔍 Tabldot (`tabledhote-sale`/`new-poscard`) — kart/hesap-bazlı set yemek, NİŞ
- Kart/sicil bazlı sabit menü yemek: personel yemeği + kurumsal hesap + yemekhane/kantin + otel board. Satış: sicil→departman+menü→personel/misafir sayısı→kaydet. Kartlar=firma+kişi hesapları. (Fix Menü + hesap muhasebesi birleşimi; 3 rapor ekranı yetki yok görülemedi.)
- Bizde: müşteri sadakat/çark + müşteri app dijital cüzdan VAR; **personel-yemeği/kantin/kurumsal-hesap modülü YOK**.
- Standart restoran için NİŞ → düşük öncelik. Değerli: yemekhane/kantin/kurumsal catering/otel. Kolay dilim: "personel yemeği takibi" (ne yedi→maliyet/maaş) İК+POS'a eklenebilir. Modern açı: dijital cüzdan = kart-hesabının modern hali.

### 🎯 ÖNCELİK SIRASI (şimdiye kadarki tüm eksiklerden)
1. 🔥 İtibar/Yorum Yönetimi (ucuz AI, dahil=koz, Google kritik)
2. 🔥 AI Pazarlama/Kampanya motoru (altyapı %70 var)
3. 🔥 Satıcı Yaşlandırma + AI nakit danışmanı (finans, kolay)
4. 🟡 Çok-kademeli satınalma onayı + vekil atama
5. 🟡 Social Media Manager (yalın: metin AI + IG/FB paylaşım)
6. 🟡 AI Talep Tahmini (POS geçmişten→malzeme→proaktif sipariş)
7. ⚪ Müstahsil Makbuzu, Fiyat Anlaşmaları, Happy Hour/Tabldot
8. ⚪ Demirbaş (en son — yalın ekipman/bakım versiyonu)
- ATLA: Akıllı Dinamik Fiyat (otel), otel raporları

---
*(Sonraki gruplar aşağıya eklenecek)*

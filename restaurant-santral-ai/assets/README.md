# Ofis Ambiyansı (comfort noise)

Çağrı boyunca AI sesinin **altında** kısık, sürekli bir arka plan sesi çalar:
ölü sessizliği kaldırır, "insan çağrı merkezi" hissi verir. **Ücretsiz** — ses
motoru (Google TTS) değişmez, sadece dar-bant arka ses karıştırılır.

## Nasıl çalışır
- `AMBIYANS=1` (varsayılan açık) ise köprü her 20ms karede ambiyans + (varsa) o anki
  TTS sesini karıştırıp gönderir. `AMBIYANS_SEVIYE` (0..0.5) sesini ayarlar.
- Bu klasörde `ofis-ambiyans.sln` **yoksa** yumuşak sentetik "oda tonu" üretilir
  (kutudan çıkınca da çalışır). Gerçek ofis uğultusu için aşağıdaki adımı yapın.

## Gerçek ofis sesi ekleme (opsiyonel, daha gerçekçi)
1. Telifsiz bir "office ambience / call center background" sesi bulun (mp3/wav),
   döngüye uygun ~10-60 sn.
2. 8 kHz mono ham s16le'e çevirin (ulaw modu için):
   ```
   ffmpeg -i ofis-ambiyans.mp3 -ac 1 -ar 8000 -f s16le assets/ofis-ambiyans.sln
   ```
   (slin16 modundaysanız `-ar 16000` kullanın.)
3. Köprüyü yeniden başlatın. Dosya otomatik yüklenir (log: "Ambiyans dosyasi yuklendi").

`.sln` ham PCM olduğu için git'e eklenebilir (büyükse `.gitignore`'a alıp sunucuya
elle koyabilirsiniz). Yol `AMBIYANS_DOSYA` ile de verilebilir.

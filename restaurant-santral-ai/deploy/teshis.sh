#!/usr/bin/env bash
# ResteOS AI Santral — TEK KOMUT TESHIS
# FreePBX sunucuda calistir:
#   cd /opt/restaurant-santral-ai && bash deploy/teshis.sh
# Ciktiyi KOMPLE kopyalayip paylas. (Sifre/anahtar SIZDIRMAZ — sadece var/yok gosterir.)

SERVIS="restaurant-santral-ai"
KOK="$(cd "$(dirname "$0")/.." && pwd)"
cd "$KOK" || exit 1

cizgi(){ echo "============================================================"; }
baslik(){ cizgi; echo "## $1"; cizgi; }

baslik "0) Genel"
echo "Tarih    : $(date)"
echo "Proje    : $KOK"
echo "Node     : $(command -v node || echo YOK)  $(node -v 2>/dev/null)"
echo "Kullanici: $(whoami)"

baslik "1) SERVIS DURUMU (systemd)"
if systemctl list-unit-files 2>/dev/null | grep -q "^${SERVIS}.service"; then
  systemctl status "$SERVIS" --no-pager -l 2>&1 | head -20
  echo "--- aktif mi: $(systemctl is-active $SERVIS 2>/dev/null) | acilista: $(systemctl is-enabled $SERVIS 2>/dev/null)"
else
  echo ">> '${SERVIS}.service' YUKLU DEGIL. Servis olarak kurulmamis."
  echo ">> O yuzden 'journalctl -u ${SERVIS}' BOS cikar (log alamama sebebi bu olabilir)."
  echo ">> Node elle mi calisiyor? Asagidaki 'process' bolumune bak."
fi

baslik "2) CALISAN NODE SURECI (elle/pm2/screen de olabilir)"
ps aux | grep -E "node .*src/index.js|restaurant-santral-ai" | grep -v grep || echo ">> Calisan node index.js sureci YOK -> kopru hic calismiyor olabilir."
command -v pm2 >/dev/null && { echo "--- pm2 listesi:"; pm2 list 2>/dev/null; }

baslik "3) SON 120 LOG SATIRI (journald)"
if systemctl list-unit-files 2>/dev/null | grep -q "^${SERVIS}.service"; then
  journalctl -u "$SERVIS" -n 120 --no-pager 2>&1 || echo ">> journalctl okunamadi (yetki? root ile dene)."
  echo
  echo ">>> CANLI LOG icin (ayri pencerede): journalctl -u ${SERVIS} -f"
else
  echo ">> Servis yok; journald logu yok. Elle calistiriyorsan logu su sekilde yakala:"
  echo "   node src/index.js 2>&1 | tee /tmp/santral.log"
fi

baslik "4) .env KRITIK AYARLAR (deger GIZLI, sadece var/yok)"
if [ -f .env ]; then
  while IFS='=' read -r k v; do
    case "$k" in ''|\#*) continue;; esac
    case "$k" in
      *KEY*|*PASS*|*SECRET*|*CREDENTIALS*) [ -n "$v" ] && echo "$k = (DOLU)" || echo "$k = (BOS!)";;
      *) echo "$k = $v";;
    esac
  done < .env
else
  echo ">> .env YOK! (cp .env.example .env yapilmamis) -> hicbir sey calismaz."
fi

baslik "5) GOOGLE STT KIMLIK (streaming icin ZORUNLU)"
CRED="$(grep -E '^GOOGLE_APPLICATION_CREDENTIALS=' .env 2>/dev/null | cut -d= -f2-)"
CRED="${CRED:-$GOOGLE_APPLICATION_CREDENTIALS}"
if [ -z "$CRED" ]; then
  echo ">> GOOGLE_APPLICATION_CREDENTIALS BOS -> STT HIC calismaz, musteri DUYULMAZ."
elif [ ! -f "$CRED" ]; then
  echo ">> Kimlik dosyasi YOK: $CRED -> STT calismaz."
else
  echo "Dosya    : $CRED  (VAR)"
  echo "Okunur mu: $( [ -r "$CRED" ] && echo EVET || echo HAYIR_yetki_sorunu )"
  if command -v python3 >/dev/null; then
    python3 - "$CRED" <<'PY' 2>/dev/null || echo ">> JSON GECERSIZ ya da okunamadi."
import json,sys
d=json.load(open(sys.argv[1]))
print("project_id:", d.get("project_id","?"))
print("client_email:", d.get("client_email","?"))
print("type:", d.get("type","?"))
PY
  fi
fi

baslik "6) GOOGLE SPEECH KUTUPHANESI YUKLU MU"
[ -d node_modules/@google-cloud/speech ] && echo "@google-cloud/speech: VAR" || echo ">> @google-cloud/speech YOK -> 'npm install' calistir."

baslik "7) ARI ERISIMI (Asterisk)"
AURL="$(grep -E '^ARI_URL=' .env 2>/dev/null | cut -d= -f2-)"; AURL="${AURL:-http://127.0.0.1:8088}"
AUSR="$(grep -E '^ARI_USER=' .env 2>/dev/null | cut -d= -f2-)"
APAS="$(grep -E '^ARI_PASS=' .env 2>/dev/null | cut -d= -f2-)"
echo "ARI_URL=$AURL ARI_USER=$AUSR"
if command -v curl >/dev/null; then
  KOD=$(curl -s -o /tmp/ari_test.json -w "%{http_code}" -u "$AUSR:$APAS" "$AURL/ari/asterisk/info" 2>/dev/null)
  echo "HTTP $KOD  (200=tamam, 401=sifre yanlis, 000=Asterisk/ARI kapali)"
fi
command -v asterisk >/dev/null && { echo "--- ari show apps:"; asterisk -rx "ari show apps" 2>/dev/null; echo "--- externalMedia modul:"; asterisk -rx "module show like res_ari" 2>/dev/null | head -5; }

baslik "8) SES KAYDI KLASORU"
RDIR="$(grep -E '^ASTERISK_RECORDING_DIR=' .env 2>/dev/null | cut -d= -f2-)"; RDIR="${RDIR:-/var/spool/asterisk/recording}"
echo "Klasor: $RDIR"
[ -d "$RDIR" ] && { echo "VAR | yazilabilir: $( [ -w "$RDIR" ] && echo EVET || echo HAYIR )"; ls -la "$RDIR" 2>/dev/null | head; } || echo ">> Klasor YOK -> kayitlar olusmaz/yuklenemez."

baslik "9) RTP PORTLARI (firewall/dinleme)"
PBASE="$(grep -E '^RTP_PORT_BASE=' .env 2>/dev/null | cut -d= -f2-)"; PBASE="${PBASE:-40000}"
echo "RTP taban port: $PBASE"
(ss -lunp 2>/dev/null || netstat -lunp 2>/dev/null) | grep -E ":${PBASE}|node" | head || echo "(aktif cagri yokken RTP portu acik olmayabilir, normal)"

cizgi
echo "TESHIS BITTI. Yukaridaki ciktiyi KOMPLE kopyalayip paylas."
cizgi

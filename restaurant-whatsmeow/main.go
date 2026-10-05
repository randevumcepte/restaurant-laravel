// whatsmeow-bridge — randevumcepte Laravel uygulamasi icin WhatsApp Web bridge.
//
// Baileys bridge'inin HTTP contract'iyle birebir uyumlu (ayni endpoint'ler,
// ayni webhook event isimleri). Laravel tarafinda hicbir kod degisikligi
// gerektirmez; sadece config'ten URL degistirilir ya da bir salonun
// whatsapp_bridge_tipi alani 'whatsmeow' yapilir (router'da iki bridge
// arasinda secim yapilir).
//
// Tek dosya, basit, audit edilebilir. Production'a tasinacak sey degil,
// pilot/test icin minimum viable bridge.
package main

import (
	"bytes"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"math/rand"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
	_ "github.com/mattn/go-sqlite3"
	"go.mau.fi/whatsmeow"
	waProto "go.mau.fi/whatsmeow/binary/proto"
	"go.mau.fi/whatsmeow/store"
	"go.mau.fi/whatsmeow/store/sqlstore"
	"go.mau.fi/whatsmeow/types"
	"go.mau.fi/whatsmeow/types/events"
	waLog "go.mau.fi/whatsmeow/util/log"
	"google.golang.org/protobuf/proto"
)

// ─────────────────────────────────────────────────────────────────────────────
// Konfigurasyon
// ─────────────────────────────────────────────────────────────────────────────

type Config struct {
	Port          string
	DBDir         string
	SharedSecret  string
	WebhookURL    string
	WebhookSecret string
	DeviceOS      string
	SendDelayMin  int
	SendDelayMax  int
}

func loadConfig() *Config {
	// SHARED_SECRET birincil isim (Baileys bridge ile tutarli).
	// SERVICE_TOKEN geriye uyumluluk icin fallback.
	shared := envStr("SHARED_SECRET", "")
	if shared == "" {
		shared = envStr("SERVICE_TOKEN", "")
	}
	return &Config{
		Port:          envStr("PORT", "3002"),
		DBDir:         envStr("DB_DIR", "./data"),
		SharedSecret:  shared,
		WebhookURL:    envStr("WEBHOOK_URL", ""),
		WebhookSecret: envStr("WEBHOOK_SECRET", ""),
		DeviceOS:      envStr("DEVICE_OS", "Chrome"),
		SendDelayMin:  envInt("SEND_DELAY_MIN", 12),
		SendDelayMax:  envInt("SEND_DELAY_MAX", 30),
	}
}

func envStr(k, def string) string {
	v := os.Getenv(k)
	if v == "" {
		return def
	}
	return v
}

func envInt(k string, def int) int {
	v := os.Getenv(k)
	if v == "" {
		return def
	}
	n, err := strconv.Atoi(v)
	if err != nil {
		return def
	}
	return n
}

// ─────────────────────────────────────────────────────────────────────────────
// Session — bir salonun WA oturumu (memory-resident)
// ─────────────────────────────────────────────────────────────────────────────

type Session struct {
	SalonID    string
	Client     *whatsmeow.Client
	Container  *sqlstore.Container
	Status     string // connecting / qr-pending / connected / disconnected / logged-out
	LastQR     string
	QRExpires  time.Time
	Phone      string
	LastError  string
	SendChan   chan SendJob // anti-burst kuyrugu (her salon kendi goroutine'i)
	cancelSend context.CancelFunc

	mu sync.RWMutex
}

type SendJob struct {
	To      string
	Message string
	LogID   int64
	Urgent  bool
}

// SessionStore - tum aktif salon oturumlarini RAM'de tutar
type SessionStore struct {
	mu       sync.RWMutex
	sessions map[string]*Session
	cfg      *Config
}

func newSessionStore(cfg *Config) *SessionStore {
	return &SessionStore{
		sessions: make(map[string]*Session),
		cfg:      cfg,
	}
}

func (s *SessionStore) get(salonID string) *Session {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.sessions[salonID]
}

func (s *SessionStore) put(salonID string, sess *Session) {
	s.mu.Lock()
	defer s.mu.Unlock()
	s.sessions[salonID] = sess
}

func (s *SessionStore) drop(salonID string) {
	s.mu.Lock()
	defer s.mu.Unlock()
	if sess, ok := s.sessions[salonID]; ok {
		if sess.cancelSend != nil {
			sess.cancelSend()
		}
		if sess.Client != nil {
			sess.Client.Disconnect()
		}
		delete(s.sessions, salonID)
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Webhook — Laravel'e event POST eder
// ─────────────────────────────────────────────────────────────────────────────

type WebhookPoster struct {
	cfg    *Config
	client *http.Client
}

func newWebhookPoster(cfg *Config) *WebhookPoster {
	return &WebhookPoster{
		cfg:    cfg,
		client: &http.Client{Timeout: 10 * time.Second},
	}
}

func (w *WebhookPoster) Post(event string, payload map[string]interface{}) {
	if w.cfg.WebhookURL == "" {
		return
	}
	payload["event"] = event
	body, _ := json.Marshal(payload)
	req, err := http.NewRequest("POST", w.cfg.WebhookURL, bytes.NewReader(body))
	if err != nil {
		log.Printf("[webhook] request build err: %v", err)
		return
	}
	req.Header.Set("Content-Type", "application/json")
	if w.cfg.WebhookSecret != "" {
		req.Header.Set("X-Webhook-Secret", w.cfg.WebhookSecret)
	}
	go func() {
		resp, err := w.client.Do(req)
		if err != nil {
			log.Printf("[webhook] %s POST hata: %v -> %s", event, err, w.cfg.WebhookURL)
			return
		}
		defer resp.Body.Close()
		io.Copy(io.Discard, resp.Body)
		// Her gonderim sonucunu logla (success dahil). Tanida fire-olmuyor mu
		// yoksa Laravel reddediyor mu netlestirmek icin.
		log.Printf("[webhook] %s status=%d -> %s", event, resp.StatusCode, w.cfg.WebhookURL)
	}()
}

// ─────────────────────────────────────────────────────────────────────────────
// Session lifecycle - start, event handler, send
// ─────────────────────────────────────────────────────────────────────────────

func (st *SessionStore) startSession(salonID string, hooks *WebhookPoster) (*Session, error) {
	if sess := st.get(salonID); sess != nil && sess.Client != nil {
		if sess.Client.IsConnected() {
			return sess, nil
		}
	}

	if err := os.MkdirAll(st.cfg.DBDir, 0755); err != nil {
		return nil, err
	}
	dbPath := filepath.Join(st.cfg.DBDir, fmt.Sprintf("salon_%s.db", salonID))
	dsn := fmt.Sprintf("file:%s?_foreign_keys=on", dbPath)

	dbLog := waLog.Stdout("Database", "WARN", true)
	container, err := sqlstore.New(context.Background(), "sqlite3", dsn, dbLog)
	if err != nil {
		return nil, fmt.Errorf("sqlstore: %w", err)
	}

	device, err := container.GetFirstDevice(context.Background())
	if err != nil {
		return nil, fmt.Errorf("get device: %w", err)
	}
	if device == nil {
		device = container.NewDevice()
	}

	clientLog := waLog.Stdout("Client", "INFO", true)
	client := whatsmeow.NewClient(device, clientLog)

	sess := &Session{
		SalonID:   salonID,
		Client:    client,
		Container: container,
		Status:    "connecting",
		SendChan:  make(chan SendJob, 256),
	}
	st.put(salonID, sess)

	// Event handler
	client.AddEventHandler(buildEventHandler(sess, hooks))

	// Anti-burst send loop (her salon icin kendi goroutine)
	sendCtx, cancel := context.WithCancel(context.Background())
	sess.cancelSend = cancel
	go sendLoop(sendCtx, sess, st.cfg, hooks)

	// Daha onceden pair edilmemis (yeni device) -> QR baslat
	if client.Store.ID == nil {
		qrChan, _ := client.GetQRChannel(context.Background())
		if err := client.Connect(); err != nil {
			st.drop(salonID)
			return nil, fmt.Errorf("connect: %w", err)
		}
		go func() {
			for evt := range qrChan {
				switch evt.Event {
				case "code":
					sess.mu.Lock()
					sess.LastQR = evt.Code
					sess.QRExpires = time.Now().Add(40 * time.Second)
					sess.Status = "qr-pending"
					sess.mu.Unlock()
					hooks.Post("qr.ready", map[string]interface{}{
						"salonId": salonID,
					})
					log.Printf("[%s] QR ready (len=%d)", salonID, len(evt.Code))
				case "success":
					log.Printf("[%s] QR taratildi, pairing tamam", salonID)
				case "timeout":
					sess.mu.Lock()
					sess.Status = "disconnected"
					sess.LastError = "qr-timeout"
					sess.mu.Unlock()
				}
			}
		}()
	} else {
		if err := client.Connect(); err != nil {
			st.drop(salonID)
			return nil, fmt.Errorf("reconnect: %w", err)
		}
	}

	return sess, nil
}

func buildEventHandler(sess *Session, hooks *WebhookPoster) func(interface{}) {
	return func(rawEvt interface{}) {
		switch evt := rawEvt.(type) {
		case *events.Connected:
			sess.mu.Lock()
			sess.Status = "connected"
			if sess.Client.Store.ID != nil {
				sess.Phone = sess.Client.Store.ID.User
			}
			sess.LastError = ""
			sess.mu.Unlock()
			log.Printf("[%s] connected (phone=%s)", sess.SalonID, sess.Phone)
			hooks.Post("connected", map[string]interface{}{
				"salonId": sess.SalonID,
				"phone":   sess.Phone,
			})

		case *events.Disconnected:
			sess.mu.Lock()
			sess.Status = "disconnected"
			sess.mu.Unlock()
			log.Printf("[%s] disconnected", sess.SalonID)
			hooks.Post("disconnected", map[string]interface{}{
				"salonId":   sess.SalonID,
				"reason":    "transport",
				"banLikely": false,
			})

		case *events.LoggedOut:
			sess.mu.Lock()
			sess.Status = "logged-out"
			sess.LastError = "logged-out"
			sess.mu.Unlock()
			log.Printf("[%s] logged out by user/server", sess.SalonID)
			hooks.Post("disconnected", map[string]interface{}{
				"salonId":    sess.SalonID,
				"reason":     "loggedOut",
				"statusCode": 401,
				"banLikely":  true,
			})

		case *events.StreamReplaced:
			sess.mu.Lock()
			sess.Status = "disconnected"
			sess.LastError = "stream-replaced"
			sess.mu.Unlock()
			log.Printf("[%s] stream replaced (baska bir cihaz pair etmis olabilir)", sess.SalonID)
			hooks.Post("disconnected", map[string]interface{}{
				"salonId": sess.SalonID,
				"reason":  "stream-replaced",
			})

		case *events.Receipt:
			// Teslimat ve okundu bildirimleri
			var eventName string
			switch evt.Type {
			case types.ReceiptTypeDelivered:
				eventName = "message.delivered"
			case types.ReceiptTypeRead, types.ReceiptTypeReadSelf:
				eventName = "message.read"
			default:
				return
			}
			for _, msgID := range evt.MessageIDs {
				hooks.Post(eventName, map[string]interface{}{
					"salonId":   sess.SalonID,
					"messageId": msgID,
				})
			}

		case *events.Message:
			// GELEN musteri mesaji (1:1 metin/konum). Kendi mesajlarimiz ve gruplar haric.
			if evt.Info.IsFromMe || evt.Info.IsGroup {
				return
			}
			text := evt.Message.GetConversation()
			if text == "" {
				if ext := evt.Message.GetExtendedTextMessage(); ext != nil {
					text = ext.GetText()
				}
			}
			payload := map[string]interface{}{
				"salonId":   sess.SalonID,
				"from":      evt.Info.Sender.User,
				"fromJid":   evt.Info.Chat.String(), // cevap hedefi (LID ise "<lid>@lid")
				"text":      text,
				"pushName":  evt.Info.PushName,
				"messageId": evt.Info.ID,
				"type":      "text",
			}
			if loc := evt.Message.GetLocationMessage(); loc != nil {
				payload["type"] = "location"
				payload["lat"] = loc.GetDegreesLatitude()
				payload["lng"] = loc.GetDegreesLongitude()
			}
			if text == "" && payload["type"] != "location" {
				return // medya/sticker vb. -> atla
			}
			log.Printf("[%s] gelen mesaj from=%s type=%v", sess.SalonID, evt.Info.Sender.User, payload["type"])
			hooks.Post("message.received", payload)
		}
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// Send loop — her salon icin kendi goroutine, anti-burst delay
// ─────────────────────────────────────────────────────────────────────────────

func sendLoop(ctx context.Context, sess *Session, cfg *Config, hooks *WebhookPoster) {
	defer log.Printf("[%s] send loop bitti", sess.SalonID)
	for {
		select {
		case <-ctx.Done():
			return
		case job, ok := <-sess.SendChan:
			if !ok {
				return
			}
			doSend(sess, job, hooks)
			if !job.Urgent {
				// Anti-burst: rastgele 12-30sn arasinda bekle
				delay := cfg.SendDelayMin
				if cfg.SendDelayMax > cfg.SendDelayMin {
					delay += rand.Intn(cfg.SendDelayMax - cfg.SendDelayMin)
				}
				select {
				case <-ctx.Done():
					return
				case <-time.After(time.Duration(delay) * time.Second):
				}
			}
		}
	}
}

func doSend(sess *Session, job SendJob, hooks *WebhookPoster) {
	if sess.Client == nil || !sess.Client.IsConnected() {
		hooks.Post("message.failed", map[string]interface{}{
			"salonId": sess.SalonID,
			"logId":   job.LogID,
			"error":   "not-connected",
		})
		return
	}

	jid, err := parseJID(job.To)
	if err != nil {
		hooks.Post("message.failed", map[string]interface{}{
			"salonId": sess.SalonID,
			"logId":   job.LogID,
			"error":   "invalid-phone:" + err.Error(),
		})
		return
	}

	// Anti-ban: insan-benzeri davranis simulasyonu (urgent OTP icin atlanir).
	// 1) Pre-send delay: ilk mesaj icin de 3-8sn isinma (yoksa "robotik anlik" gozukur)
	// 2) Typing presence: alici "yaziyor..." gorsun
	// 3) Typing suresi: mesaj uzunluguna gore (insan hizinda)
	if !job.Urgent {
		// 1) Pre-send delay 3-8sn
		preDelay := 3 + rand.Intn(6) // 3..8
		time.Sleep(time.Duration(preDelay) * time.Second)

		// 2-3) Typing simulation
		_ = sess.Client.SendChatPresence(context.Background(), jid, types.ChatPresenceComposing, types.ChatPresenceMediaText)
		// Mesaj uzunluguna gore typing suresi: 1 sn + uzunluk/40, min 1.5sn max 6sn
		typingMs := 1500 + (len(job.Message)*1000)/40
		if typingMs < 1500 {
			typingMs = 1500
		}
		if typingMs > 6000 {
			typingMs = 6000
		}
		time.Sleep(time.Duration(typingMs) * time.Millisecond)
		_ = sess.Client.SendChatPresence(context.Background(), jid, types.ChatPresencePaused, types.ChatPresenceMediaText)
	}

	msg := &waProto.Message{
		Conversation: proto.String(job.Message),
	}

	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()
	resp, err := sess.Client.SendMessage(ctx, jid, msg)
	if err != nil {
		errStr := err.Error()
		log.Printf("[%s] send err to=%s: %v", sess.SalonID, job.To, err)
		hooks.Post("message.failed", map[string]interface{}{
			"salonId": sess.SalonID,
			"logId":   job.LogID,
			"error":   errStr,
		})
		return
	}

	hooks.Post("message.sent", map[string]interface{}{
		"salonId":   sess.SalonID,
		"logId":     job.LogID,
		"messageId": resp.ID,
		"timestamp": resp.Timestamp.Unix(),
	})
}

// ─────────────────────────────────────────────────────────────────────────────
// Yardimcilar
// ─────────────────────────────────────────────────────────────────────────────

var nonDigit = regexp.MustCompile(`\D+`)

func parseJID(raw string) (types.JID, error) {
	// Tam JID verildiyse ("<lid>@lid" veya "<pn>@s.whatsapp.net") oldugu gibi coz.
	// WhatsApp LID (gizli numara) gonderenlere cevap verebilmek icin sart.
	if strings.Contains(raw, "@") {
		return types.ParseJID(raw)
	}
	n := nonDigit.ReplaceAllString(raw, "")
	if strings.HasPrefix(n, "00") {
		n = n[2:]
	}
	if len(n) == 10 && strings.HasPrefix(n, "5") {
		n = "90" + n
	}
	if len(n) == 11 && strings.HasPrefix(n, "0") {
		n = "90" + n[1:]
	}
	if len(n) < 11 {
		return types.EmptyJID, errors.New("number too short")
	}
	return types.NewJID(n, types.DefaultUserServer), nil
}

// ─────────────────────────────────────────────────────────────────────────────
// HTTP handlers
// ─────────────────────────────────────────────────────────────────────────────

func main() {
	rand.Seed(time.Now().UnixNano())

	cfg := loadConfig()
	hooks := newWebhookPoster(cfg)
	store_ := newSessionStore(cfg)

	// whatsmeow: cihaz fingerprint'i — linked devices'da "whatsmeow" yerine
	// daha gercekci bir tarayici/OS profili goster.
	//
	// PlatformType MUTLAKA tarayici tipi olmali (DESKTOP DEGIL): telefon
	// numarasiyla pair'de (link_code_companion_reg) sunucuya giden
	// companion_platform_id bir tarayici degeri (Chrome). Burasi DESKTOP
	// kalirsa iki bilgi celisir ve WhatsApp pairing'in ilk asamasini
	// "info query returned status 400: bad-request" ile reddeder. QR pairing
	// DESKTOP ile de calistigi icin bu hata sadece pair-phone'da gorunuyordu.
	store.DeviceProps.Os = proto.String(cfg.DeviceOS)
	store.DeviceProps.PlatformType = waProto.DeviceProps_CHROME.Enum()
	store.DeviceProps.RequireFullSync = proto.Bool(false)

	// Auto-restore: sunucu restart sonrasi mevcut oturumlari yeniden ac.
	// data/ altindaki her salon_X.db dosyasi icin sessionStore.startSession
	// cagrilir — device bilgisi SQLite'ta mevcutsa whatsmeow otomatik connect
	// eder (QR'a gerek yok). Salon tarafinda panel refresh olunca connected
	// gorunur, mesaj gonderimi kesintisiz devam eder.
	go func() {
		time.Sleep(500 * time.Millisecond) // HTTP server hazir olsun
		if entries, err := os.ReadDir(cfg.DBDir); err == nil {
			restored := 0
			for _, e := range entries {
				if e.IsDir() { continue }
				name := e.Name()
				// "salon_123.db" -> salonId=123
				if !strings.HasPrefix(name, "salon_") || !strings.HasSuffix(name, ".db") {
					continue
				}
				salonID := strings.TrimSuffix(strings.TrimPrefix(name, "salon_"), ".db")
				if salonID == "" { continue }
				if _, err := store_.startSession(salonID, hooks); err != nil {
					log.Printf("[boot] auto-restore %s hatasi: %v", salonID, err)
					continue
				}
				restored++
			}
			log.Printf("[boot] auto-restore tamamlandi: %d oturum yuklendi", restored)
		} else {
			log.Printf("[boot] data dizini okunamadi (%s): %v", cfg.DBDir, err)
		}
	}()

	gin.SetMode(gin.ReleaseMode)
	r := gin.New()
	r.Use(gin.Recovery())
	r.Use(gin.Logger())

	// Auth middleware — Baileys bridge ile ayni: X-Service-Token header
	// degeri config.SharedSecret ile esit olmali. /health endpoint'i hariç tutulur
	// ki monitoring kontrolu auth gerektirmesin.
	r.Use(func(c *gin.Context) {
		if c.Request.URL.Path == "/health" {
			c.Next()
			return
		}
		if cfg.SharedSecret == "" {
			c.Next()
			return
		}
		got := c.GetHeader("X-Service-Token")
		if got != cfg.SharedSecret {
			c.AbortWithStatusJSON(401, gin.H{"error": "unauthorized"})
			return
		}
		c.Next()
	})

	// POST /session/:salonId/start
	r.POST("/session/:salonId/start", func(c *gin.Context) {
		salonID := c.Param("salonId")
		sess, err := store_.startSession(salonID, hooks)
		if err != nil {
			c.JSON(500, gin.H{"error": err.Error()})
			return
		}
		c.JSON(200, gin.H{
			"status":  sess.Status,
			"qr":      sess.LastQR,
			"salonId": salonID,
		})
	})

	// GET /session/:salonId/qr — su anki QR kodu (varsa)
	r.GET("/session/:salonId/qr", func(c *gin.Context) {
		salonID := c.Param("salonId")
		sess := store_.get(salonID)
		if sess == nil {
			c.JSON(404, gin.H{"error": "session-not-found"})
			return
		}
		sess.mu.RLock()
		defer sess.mu.RUnlock()
		c.JSON(200, gin.H{
			"qr":      sess.LastQR,
			"status":  sess.Status,
			"expires": sess.QRExpires.Unix(),
		})
	})

	// GET /session/:salonId/status
	r.GET("/session/:salonId/status", func(c *gin.Context) {
		salonID := c.Param("salonId")
		sess := store_.get(salonID)
		if sess == nil {
			c.JSON(200, gin.H{"status": "no-session", "connected": false})
			return
		}
		sess.mu.RLock()
		defer sess.mu.RUnlock()
		connected := sess.Client != nil && sess.Client.IsConnected()
		c.JSON(200, gin.H{
			"status":    sess.Status,
			"connected": connected,
			"phone":     sess.Phone,
			"lastError": sess.LastError,
		})
	})

	// POST /session/:salonId/send
	r.POST("/session/:salonId/send", func(c *gin.Context) {
		salonID := c.Param("salonId")
		sess := store_.get(salonID)
		if sess == nil {
			c.JSON(409, gin.H{"error": "session-not-found"})
			return
		}
		sess.mu.RLock()
		connected := sess.Client != nil && sess.Client.IsConnected()
		sess.mu.RUnlock()
		if !connected {
			c.JSON(409, gin.H{"error": "not-connected"})
			return
		}

		var req struct {
			To      string `json:"to"`
			Message string `json:"message"`
			LogID   int64  `json:"logId"`
			Urgent  bool   `json:"urgent"`
		}
		if err := c.ShouldBindJSON(&req); err != nil {
			c.JSON(400, gin.H{"error": "bad-request: " + err.Error()})
			return
		}
		if req.To == "" || req.Message == "" {
			c.JSON(400, gin.H{"error": "to/message required"})
			return
		}

		// Kuyruga at, hemen 202 don
		select {
		case sess.SendChan <- SendJob{
			To: req.To, Message: req.Message, LogID: req.LogID, Urgent: req.Urgent,
		}:
			c.JSON(202, gin.H{"queued": true, "logId": req.LogID})
		default:
			c.JSON(503, gin.H{"error": "queue-full"})
		}
	})

	// POST /session/:salonId/logout
	r.POST("/session/:salonId/logout", func(c *gin.Context) {
		salonID := c.Param("salonId")
		sess := store_.get(salonID)
		if sess == nil {
			c.JSON(404, gin.H{"error": "session-not-found"})
			return
		}
		if sess.Client != nil && sess.Client.IsConnected() {
			if err := sess.Client.Logout(context.Background()); err != nil {
				log.Printf("[%s] logout err: %v", salonID, err)
			}
		}
		store_.drop(salonID)

		// DB dosyasini da temizle ki bir dahaki start temiz pair baslatsin
		dbPath := filepath.Join(cfg.DBDir, fmt.Sprintf("salon_%s.db", salonID))
		_ = os.Remove(dbPath)

		c.JSON(200, gin.H{"ok": true})
	})

	// POST /session/:salonId/pair-phone
	// Telefon numarasiyla pair (QR taratmaya alternatif — iPhone sorunlari icin).
	// Body: {"phone": "905XXXXXXXXX"}
	// Response: {"code":"ABCD-1234"} - salon telefonunda gireceklerin
	r.POST("/session/:salonId/pair-phone", func(c *gin.Context) {
		salonID := c.Param("salonId")
		var body struct {
			Phone string `json:"phone"`
		}
		if err := c.BindJSON(&body); err != nil || body.Phone == "" {
			c.JSON(400, gin.H{"error": "phone-required"})
			return
		}
		// Numarayi temizle: sadece rakamlar, 90 on eki garanti
		digits := nonDigit.ReplaceAllString(body.Phone, "")
		if strings.HasPrefix(digits, "00") { digits = digits[2:] }
		if len(digits) == 10 && strings.HasPrefix(digits, "5") { digits = "90" + digits }
		if len(digits) == 11 && strings.HasPrefix(digits, "0") { digits = "90" + digits[1:] }
		if len(digits) < 11 {
			c.JSON(400, gin.H{"error": "invalid-phone"})
			return
		}

		// Session ve client hazir olmali (start cagrilmis olmali)
		sess, err := store_.startSession(salonID, hooks)
		if err != nil {
			c.JSON(500, gin.H{"error": err.Error()})
			return
		}
		if sess.Client == nil {
			c.JSON(500, gin.H{"error": "client-not-ready"})
			return
		}
		if sess.Client.Store.ID != nil {
			c.JSON(409, gin.H{"error": "already-paired"})
			return
		}

		// whatsmeow docs: PairPhone cagirmadan once QR event'ini beklemek gerekiyor
		// (Connect + WhatsApp handshake tamamen bitmis olsun diye). Aksi halde
		// "info query returned status 400: bad-request" hatasi alinir.
		// LastQR dolduğunda WhatsApp ile ilk el sikismasi tamamlanmis demektir.
		ready := false
		deadline := time.Now().Add(20 * time.Second)
		for time.Now().Before(deadline) {
			sess.mu.RLock()
			hasQR := sess.LastQR != ""
			connected := sess.Client != nil && sess.Client.IsConnected()
			sess.mu.RUnlock()
			if hasQR && connected {
				ready = true
				break
			}
			time.Sleep(150 * time.Millisecond)
		}
		// Fail closed: hazir degilken PairPhone cagirmak garanti 400 bad-request
		// uretir ve numarayi bosuna rate-limit'e sokar. Panel tekrar denesin.
		if !ready {
			log.Printf("[%s] pair-phone iptal: pairing oturumu hazir degil (QR event gelmedi)", salonID)
			c.JSON(409, gin.H{"error": "pairing-not-ready"})
			return
		}

		// TEK deneme. Eskiden 5 farkli PairClientType sirayla denenirdi; hepsi
		// ayni nedenle (DeviceProps.PlatformType=DESKTOP celiskisi) patliyor,
		// ustelik 5 ardisik istek numarayi WhatsApp tarafinda rate-limit'e
		// sokup sonraki dogru denemeleri de bozuyordu.
		code, err := sess.Client.PairPhone(context.Background(), digits, true,
			whatsmeow.PairClientChrome, "Chrome (Linux)")
		if err != nil {
			log.Printf("[%s] PairPhone hatasi (phone=%s): %v", salonID, digits, err)
			c.JSON(500, gin.H{"error": err.Error()})
			return
		}
		log.Printf("[%s] pair code uretildi (phone=%s, code=%s)", salonID, digits, code)
		c.JSON(200, gin.H{"code": code, "phone": digits, "salonId": salonID})
	})

	// Saglik kontrolu (auth'tan once duzeltilebilir ama simdilik basit)
	r.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok", "sessions": len(store_.sessions)})
	})

	addr := ":" + cfg.Port
	log.Printf("whatsmeow-bridge baslatiliyor: %s (DB=%s, webhook=%s)",
		addr, cfg.DBDir, cfg.WebhookURL)
	if err := r.Run(addr); err != nil {
		log.Fatal(err)
	}
}

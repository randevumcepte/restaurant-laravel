{{-- Bölge oluştur (+ aynı anda N masa) — masa_yonetim: ilk kurulum kartı ve "Bölge Ekle" penceresi --}}
<form method="POST" action="/masa-yonetim/bolge-ekle" class="bolge-form" data-form="{{ $formId }}">
  @csrf
  <div class="bf-grid">
    <div style="grid-column:1/-1">
      <label class="rk-lbl">Bölge adı</label>
      <input type="text" name="ad" class="rk-inp" maxlength="60" required autocomplete="off" placeholder="Örn. Salon, Teras, Bahçe, Bar, VIP" oninput="bfOnizle(this.form, true)">
    </div>
    <div>
      <label class="rk-lbl">Ön ek <span style="color:var(--sub)">(masa adı)</span></label>
      <input type="text" name="on_ek" class="rk-inp" maxlength="6" autocomplete="off" placeholder="Otomatik" style="text-transform:uppercase" oninput="this.dataset.elle=this.value?1:'';bfOnizle(this.form)">
    </div>
    <div>
      <label class="rk-lbl">Kaç masa</label>
      <input type="number" name="adet" class="rk-inp" min="0" max="100" value="" placeholder="0 = sonra" oninput="bfOnizle(this.form)">
    </div>
    <div>
      <label class="rk-lbl">Kapasite (kişi)</label>
      <input type="number" name="kapasite" class="rk-inp" min="1" max="50" value="{{ (int) session('son_kapasite', 4) }}">
    </div>
  </div>
  <div class="bf-onizle"></div>
  <button type="submit" class="rk-btn lg" style="background:var(--mor1);width:100%;justify-content:center;margin-top:12px">
    <span class="material-icons-outlined" style="font-size:18px">add</span> Bölgeyi Oluştur
  </button>
</form>

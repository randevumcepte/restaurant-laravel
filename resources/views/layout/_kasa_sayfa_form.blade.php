{{-- Kasa noktasında kasiyere açık sayfalar. Beklenen: $k (kasa kaydı) --}}
@php $_ksAcik = _kasaKayitSayfalari($k); @endphp
<div class="rk-sub2" style="font-size:11.5px;font-weight:700;margin:0 0 6px;color:var(--sub2)">KASİYER BU KASADA HANGİ SAYFALARI GÖRSÜN</div>
<form method="POST" action="/kasalar/sayfalar" style="margin-bottom:12px">
  @csrf<input type="hidden" name="id" value="{{ $k->id }}">
  <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px">
    @foreach (_kasaSayfaSecenekleri() as $_ksUrl => $_ksSec)
      <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px">
        <input type="checkbox" name="sayfa[]" value="{{ $_ksUrl }}" {{ in_array($_ksUrl, $_ksAcik, true) ? 'checked' : '' }} {{ $_ksUrl === '/pos' ? 'disabled' : '' }}>
        {{ $_ksSec[0] }}
      </label>
    @endforeach
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:6px">
    <button type="button" class="rk-btn bos" style="padding:6px 10px;font-size:12.5px" onclick="this.form.querySelectorAll('input[type=checkbox]').forEach(function(c){c.checked=true})">Ana Kasa</button>
    <button type="button" class="rk-btn bos" style="padding:6px 10px;font-size:12.5px" onclick="this.form.querySelectorAll('input[type=checkbox]').forEach(function(c){c.checked=c.disabled})">Sadece Sipariş</button>
    <button class="rk-btn" style="background:#D97706;padding:6px 12px;font-size:12.5px"><span class="material-icons-outlined" style="color:#fff;font-size:15px">save</span> Sayfaları Kaydet</button>
  </div>
</form>

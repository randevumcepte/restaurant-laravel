@extends('layout.app')
@section('title', 'Cüzdan & Bonus')
@section('baslik', '💳 Cüzdan & Yükleme Bonusu')

@push('head')
<style>
#czroot{--line:#e4e4ee;max-width:720px}
#czroot .card{background:#fff;border-radius:16px;padding:20px;box-shadow:0 6px 20px rgba(40,30,80,.06);margin-bottom:18px}
#czroot h2{font-size:15px;margin:0 0 6px;color:#39304f;font-weight:800}
#czroot .alt{color:#8a8a9a;font-size:13px;margin:0 0 16px}
#czroot .flash{background:#e7f8ef;color:#137a4a;padding:10px 14px;border-radius:10px;margin:0 0 16px;font-size:14px;font-weight:600}
#czroot label{display:block;font-size:12.5px;color:#6a6a80;font-weight:700;margin:0 0 6px}
#czroot input{padding:10px 12px;border:1.5px solid var(--line);border-radius:10px;font-size:14px;width:140px}
#czroot .lad{display:flex;align-items:center;gap:10px;margin-bottom:10px;background:#fafafe;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
#czroot .lad .ar{color:#9a9ab0;font-size:18px}
#czroot .lad .g{font-size:12px;color:#9a9ab0;margin-bottom:3px}
#czroot .lad .rm{margin-left:auto;background:#fde8e8;color:#c0392b;border:none;border-radius:9px;padding:8px 12px;font-weight:700;cursor:pointer;font-size:13px}
#czroot .add{background:#eef;color:#4338ca;border:none;border-radius:10px;padding:10px 14px;font-weight:700;cursor:pointer;font-size:13.5px}
#czroot .save{margin-top:18px;padding:13px 22px;background:linear-gradient(135deg,#17171c,#2b2b33);color:#fff;border:none;border-radius:11px;font-size:15px;font-weight:800;cursor:pointer}
#czroot .ipucu{background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px;color:#9a3412;font-size:13px;line-height:1.5}
</style>
@endpush

@section('content')
<div id="czroot">
  @if (session('ay_ok'))<div class="flash">✓ {{ session('ay_ok') }}</div>@endif

  <div class="ipucu">
    💡 <b>Starbucks mantığı:</b> Büyük yüklemeye daha çok bonus ver → müşteri bir kere çok yükler, sen ödeme komisyonunu <b>tek seferde</b> ödersin (kasada cüzdanla ödemede komisyon yok). Bonus, komisyon kaybından ucuza gelir + para "içeride" kalır.
  </div>

  <form method="post" action="/cuzdan-ayar/kaydet">
    @csrf
    <div class="card">
      <h2>Minimum Yükleme</h2>
      <p class="alt">Müşteri cüzdana en az ne kadar yükleyebilsin? (Küçük tutarda komisyon canını yakmasın — Starbucks 30₺ civarı)</p>
      <label>Minimum tutar</label>
      <div style="display:flex;align-items:center;gap:8px"><input type="number" name="min" value="{{ rtrim(rtrim(number_format($min,2,'.',''), '0'), '.') }}" step="1" min="1"><span style="color:#6a6a80;font-weight:700">₺</span></div>
    </div>

    <div class="card">
      <h2>Yükleme Bonus Merdiveni</h2>
      <p class="alt">"Şu kadar yükle → şu kadar bonus kazan." En yüksek eşiği geçen tutar o bonusu alır. İstediğin kadar kademe ekle.</p>
      <div id="rows">
        @forelse ($ladder as $l)
          <div class="lad">
            <div><div class="g">Şu kadar yükle (₺)</div><input type="number" name="esik[]" value="{{ rtrim(rtrim(number_format($l->esik,2,'.',''), '0'), '.') }}" step="1" min="1"></div>
            <span class="ar">→</span>
            <div><div class="g">Bonus ver (₺)</div><input type="number" name="bonus[]" value="{{ rtrim(rtrim(number_format($l->bonus,2,'.',''), '0'), '.') }}" step="1" min="1"></div>
            <button type="button" class="rm" onclick="this.closest('.lad').remove()">Sil</button>
          </div>
        @empty
          <div class="lad">
            <div><div class="g">Şu kadar yükle (₺)</div><input type="number" name="esik[]" value="" step="1" min="1"></div>
            <span class="ar">→</span>
            <div><div class="g">Bonus ver (₺)</div><input type="number" name="bonus[]" value="" step="1" min="1"></div>
            <button type="button" class="rm" onclick="this.closest('.lad').remove()">Sil</button>
          </div>
        @endforelse
      </div>
      <button type="button" class="add" onclick="satirEkle()">+ Kademe Ekle</button>
    </div>

    <button class="save">Kaydet</button>
  </form>
</div>
<script>
function satirEkle(){
  var d=document.createElement('div');d.className='lad';
  d.innerHTML='<div><div class="g">Şu kadar yükle (₺)</div><input type="number" name="esik[]" step="1" min="1"></div>'
    +'<span class="ar">→</span>'
    +'<div><div class="g">Bonus ver (₺)</div><input type="number" name="bonus[]" step="1" min="1"></div>'
    +'<button type="button" class="rm" onclick="this.closest(\'.lad\').remove()">Sil</button>';
  document.getElementById('rows').appendChild(d);
}
</script>
@endsection

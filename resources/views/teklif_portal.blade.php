<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Fiyat Teklifi — {{ $sube->ad ?? 'İşletme' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --bg:#f1f5f9;--card:#fff;--ink:#0f172a;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
body{margin:0;font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--ink);line-height:1.5}
.wrap{max-width:560px;margin:0 auto;padding:0 0 60px}
.hero{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;padding:26px 20px 30px;border-radius:0 0 26px 26px;box-shadow:0 10px 30px rgba(79,70,229,.25)}
.hero .kod{display:inline-block;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);border-radius:20px;padding:4px 13px;font-size:12px;font-weight:800;letter-spacing:.5px;margin-bottom:12px}
.hero h1{margin:0 0 4px;font-size:21px;font-weight:900;letter-spacing:-.3px}
.hero p{margin:0;font-size:13.5px;opacity:.92}
.card{background:var(--card);border-radius:18px;box-shadow:0 3px 14px rgba(15,23,42,.07);padding:16px;margin:16px 16px 0}
.card.sel{margin-top:-18px;position:relative;z-index:2}
.greet{font-size:14px;font-weight:700;color:var(--ink);margin-bottom:3px}
.greet span{color:var(--mor1)}
.muted{font-size:12.5px;color:var(--sub)}
.kbas{font-size:12px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.5px;margin:0 16px 8px;padding-top:6px}
.kal{display:flex;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid var(--line)}
.kal:last-child{border-bottom:0}
.kal .ikon{width:38px;height:38px;border-radius:11px;background:color-mix(in srgb,var(--mor1) 10%,#fff);display:flex;align-items:center;justify-content:center;flex:0 0 38px}
.kal .ikon svg{width:20px;height:20px;stroke:var(--mor1)}
.kal .ki{flex:1;min-width:0}
.kal .ad{font-size:14.5px;font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.kal .mik{font-size:12.5px;color:var(--sub);font-weight:600}
.kal .fiyat-box{flex:0 0 118px;position:relative}
.kal .fiyat-box input{width:100%;padding:11px 30px 11px 12px;border:1.6px solid var(--line);border-radius:11px;font-size:15px;font-weight:800;color:var(--ink);text-align:right;font-family:inherit;background:#fff;outline:none}
.kal .fiyat-box input:focus{border-color:var(--mor1);box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 14%,transparent)}
.kal .fiyat-box .tl{position:absolute;right:11px;top:50%;transform:translateY(-50%);font-size:13px;font-weight:800;color:var(--sub2);pointer-events:none}
.flabel{font-size:12px;font-weight:800;color:var(--sub);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:6px}
.frow{margin-bottom:14px}
.frow:last-child{margin-bottom:0}
input[type=date],textarea,.vade{width:100%;padding:12px 13px;border:1.6px solid var(--line);border-radius:12px;font-size:14.5px;font-family:inherit;color:var(--ink);background:#fff;outline:none}
input[type=date]:focus,textarea:focus,.vade:focus{border-color:var(--mor1);box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 14%,transparent)}
textarea{height:74px;resize:vertical}
.gonder{position:sticky;bottom:0;margin:18px 16px 0;z-index:5}
.gonder button{width:100%;border:0;border-radius:15px;padding:17px;font-size:16px;font-weight:900;color:#fff;background:linear-gradient(135deg,var(--yesil),#059669);box-shadow:0 8px 24px rgba(16,185,129,.35);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px}
.gonder button:active{transform:scale(.98)}
.gonder button:disabled{opacity:.55;filter:grayscale(.3)}
.gonder button svg{width:21px;height:21px;stroke:#fff}
.note{text-align:center;font-size:12px;color:var(--sub2);margin:12px 20px 0;line-height:1.5}
.ok-wrap{text-align:center;padding:44px 24px}
.ok-ring{width:92px;height:92px;border-radius:50%;background:color-mix(in srgb,var(--yesil) 14%,#fff);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:pop .5s cubic-bezier(.2,.8,.2,1.4)}
.ok-ring svg{width:46px;height:46px;stroke:var(--yesil);stroke-width:3}
@keyframes pop{0%{transform:scale(.3);opacity:0}100%{transform:scale(1);opacity:1}}
.ok-wrap h2{font-size:22px;font-weight:900;margin:0 0 8px}
.ok-wrap p{font-size:14.5px;color:var(--sub);margin:0 0 4px}
.badge-ok{display:inline-block;margin-top:16px;background:color-mix(in srgb,var(--yesil) 12%,#fff);color:#047857;border-radius:20px;padding:7px 16px;font-size:13px;font-weight:800}
</style>
</head>
<body>
<div class="wrap">

  <div class="hero">
    <span class="kod">{{ $rfq->kod }}</span>
    <h1>{{ $sube->ad ?? 'İşletme' }}</h1>
    <p>Fiyat Teklifi İsteği — en uygun fiyatınızı girip gönderin</p>
  </div>

  @if(!empty($gonderildi))
    {{-- BAŞARI EKRANI --}}
    <div class="card sel ok-wrap">
      <div class="ok-ring"><svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg></div>
      <h2>Teklifiniz alındı ✓</h2>
      <p><b>{{ $gonderildi }}</b> ürün için fiyatınız {{ $sube->ad ?? 'işletmeye' }} iletildi.</p>
      <p class="muted">Yetkililer değerlendirip size dönecektir. Teşekkür ederiz!</p>
      <div class="badge-ok">{{ $rfq->kod }} · {{ now()->format('d.m.Y H:i') }}</div>
      <p class="note">Fiyatınızı güncellemek isterseniz bu sayfayı yenileyip tekrar gönderebilirsiniz.</p>
      <div style="margin-top:14px"><a href="{{ url('/teklif-ver/'.$rfq->token) }}" style="color:var(--mor1);font-weight:800;font-size:13.5px;text-decoration:none">← Teklifi düzenle</a></div>
    </div>
  @else
    <form method="POST" action="{{ url('/teklif-ver/'.$rfq->token) }}" id="tf">
      @csrf

      <div class="card sel">
        <div class="greet">Merhaba{{ $ted && $ted->yetkili ? ', ' : '' }}<span>{{ $ted->yetkili ?? ($ted->ad ?? '') }}</span></div>
        <div class="muted">Aşağıdaki ürünler için birim fiyatınızı ve teslim tarihinizi girin, <b>Gönder</b>'e basın. Tüm ürünlere fiyat vermek zorunda değilsiniz — veremediğinizi boş bırakın.</div>
      </div>

      <div class="kbas">Ürünler ({{ count($kalemler) }})</div>
      <div class="card" style="margin-top:0">
        @foreach($kalemler as $k)
          @php $mid=(int)($k['malzeme_id']??0); $mik=rtrim(rtrim(number_format((float)($k['miktar']??0),2,',','.'),'0'),','); @endphp
          <div class="kal">
            <div class="ikon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
            <div class="ki">
              <div class="ad">{{ $k['ad'] ?? '-' }}</div>
              <div class="mik">İhtiyaç: {{ $mik }} {{ $k['birim'] ?? '' }}</div>
            </div>
            <div class="fiyat-box">
              <input type="number" step="0.01" min="0" inputmode="decimal" name="fiyat[{{ $mid }}]"
                placeholder="0,00" value="{{ $mevcut[$mid] ?? '' }}">
              <span class="tl">₺</span>
            </div>
          </div>
        @endforeach
      </div>

      <div class="card">
        <div class="frow">
          <label class="flabel">Teslim Tarihi (ne zaman getirebilirsiniz?)</label>
          <input type="date" name="teslim_tarihi" value="{{ $rfq->teslim_tarihi ?? '' }}" min="{{ now()->toDateString() }}">
        </div>
        <div class="frow">
          <label class="flabel">Vade / Ödeme (opsiyonel)</label>
          <input class="vade" type="text" name="vade" placeholder="örn. 30 gün vadeli, peşin...">
        </div>
        <div class="frow">
          <label class="flabel">Not (opsiyonel)</label>
          <textarea name="not" placeholder="Eklemek istediğiniz bir şey varsa yazın...">{{ $rfq->teklif_notu ?? '' }}</textarea>
        </div>
      </div>

      <div class="gonder">
        <button type="submit" id="gonderBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
          Teklifi Gönder
        </button>
      </div>
      <p class="note">Bu bağlantı yalnızca size özeldir. Fiyatlarınız doğrudan {{ $sube->ad ?? 'işletmeye' }} iletilir.</p>
    </form>
  @endif

</div>

<script>
document.getElementById('tf')?.addEventListener('submit',function(e){
  const fiyatlar=[...document.querySelectorAll('input[name^="fiyat["]')].map(i=>parseFloat(i.value)||0);
  if(!fiyatlar.some(v=>v>0)){ e.preventDefault(); alert('En az bir ürüne fiyat girin.'); return; }
  const b=document.getElementById('gonderBtn'); b.disabled=true; b.innerHTML='Gönderiliyor...';
});
</script>
</body>
</html>

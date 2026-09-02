<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masa QR Afişleri · {{ $sube->ad ?? 'ResteOS' }}</title>
<style>
  :root{ --mor:#7C3AED; --mavi:#4F46E5; --ink:#14121A; --gri:#6B7280; --gold:#E9A23B; }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:var(--ink);background:#EEF0F6;padding:20px}
  .arac{max-width:900px;margin:0 auto 18px;display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .arac h1{font-size:18px;font-weight:800;margin-right:auto}
  .arac .say{color:var(--gri);font-size:14px}
  .btn{border:none;border-radius:12px;padding:12px 20px;font-size:15px;font-weight:800;color:#fff;background:linear-gradient(135deg,var(--mor),var(--mavi));cursor:pointer;text-decoration:none}
  .grid{max-width:900px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:16px}
  .kart{background:#fff;border-radius:22px;padding:24px 20px;text-align:center;border:2px solid #ECE7F7;break-inside:avoid;position:relative;overflow:hidden}
  .kart::before{content:'';position:absolute;top:0;left:0;right:0;height:8px;background:linear-gradient(135deg,var(--mor),var(--mavi))}
  .marka{display:flex;align-items:center;justify-content:center;gap:8px;margin:6px 0 4px}
  .marka .toque{font-size:22px}
  .marka b{font-size:18px;font-weight:800}
  .tanit{font-size:12.5px;color:var(--gri);margin-bottom:14px;line-height:1.5}
  .qr{width:220px;height:220px;margin:0 auto;padding:12px;background:#fff;border-radius:16px;border:1px solid #EEE9F5}
  .qr img,.qr canvas{display:block;margin:0 auto}
  .masa{margin-top:14px;display:inline-block;background:linear-gradient(135deg,var(--mor),var(--mavi));color:#fff;font-size:22px;font-weight:800;padding:8px 22px;border-radius:30px;letter-spacing:.5px}
  .adim{display:flex;justify-content:center;gap:14px;margin-top:14px;flex-wrap:wrap;font-size:11.5px;color:var(--gri);font-weight:600}
  .adim span{display:flex;align-items:center;gap:4px}
  @media print{
    body{background:#fff;padding:0}
    .arac{display:none}
    .grid{gap:0}
    .kart{border-radius:0;border:1px dashed #ccc;margin:0;padding:26px 18px;height:13.5cm}
    .kart::before{background:var(--mor)}
    .masa{background:var(--mor)}
  }
</style>
</head>
<body>
  <div class="arac">
    <h1>🍽️ {{ $sube->ad ?? 'Restoran' }} · Masa QR Afişleri</h1>
    <span class="say">{{ count($masalar) }} masa</span>
    <a class="btn" onclick="window.print()">🖨️ Yazdır / PDF İndir</a>
  </div>
  <div class="grid">
    @foreach ($masalar as $m)
      <div class="kart">
        <div class="marka"><span class="toque">👨‍🍳</span><b>{{ $sube->ad ?? 'Restoran' }}</b></div>
        <div class="tanit">📱 Karekodu telefonunuzla okutun<br>Menü · Sipariş · Garson Çağır · Öde</div>
        <div class="qr" id="qr-{{ $m->id }}" data-url="{{ url('/m/' . $m->qr_token) }}"></div>
        <div class="masa">MASA {{ $m->ad }}</div>
        <div class="adim"><span>🍔 Menü</span><span>🧾 Sipariş</span><span>🔔 Garson</span><span>💳 Öde</span></div>
      </div>
    @endforeach
  </div>

  <script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
  <script>
    document.querySelectorAll('.qr').forEach(function(el){
      new QRCode(el, {text: el.dataset.url, width:200, height:200, colorDark:'#14121A', colorLight:'#ffffff', correctLevel: QRCode.CorrectLevel.M});
    });
  </script>
</body>
</html>

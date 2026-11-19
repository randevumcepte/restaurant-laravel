<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Müşteri AI Konuşmaları</title>
<style>
  :root{ --bg:#f4f5f7; --kart:#fff; --cizgi:#e3e6ea; --yazi:#1d232b; --soluk:#6b7480; --mor:#6d28d9; --yesil:#0f7a55; --turuncu:#b45309; }
  *{ box-sizing:border-box; }
  body{ margin:0; background:var(--bg); color:var(--yazi); font:14px/1.5 "Segoe UI",system-ui,sans-serif; padding:20px 16px 60px; }
  .sar{ max-width:900px; margin:0 auto; display:flex; flex-direction:column; gap:14px; }
  h1{ font-size:22px; margin:0; }
  .ust{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
  .ust a{ padding:6px 12px; border-radius:8px; border:1px solid var(--cizgi); background:var(--kart); color:var(--yazi); text-decoration:none; font-weight:600; }
  .ust a.secili{ background:var(--mor); color:#fff; border-color:var(--mor); }
  .link{ background:var(--kart); border:1px solid var(--cizgi); border-radius:10px; padding:12px 14px; font-size:13px; }
  .link input{ width:100%; margin-top:6px; padding:8px; border:1px solid var(--cizgi); border-radius:6px; font:12px Consolas,monospace; }
  .oturum{ background:var(--kart); border:1px solid var(--cizgi); border-radius:10px; overflow:hidden; }
  .ob{ padding:10px 14px; border-bottom:1px solid var(--cizgi); font-weight:700; display:flex; gap:10px; flex-wrap:wrap; }
  .ob span{ color:var(--soluk); font-weight:600; }
  .tur{ padding:8px 14px; border-bottom:1px solid #f0f1f3; display:grid; grid-template-columns:62px 1fr; gap:2px 10px; }
  .tur:last-child{ border-bottom:0; }
  .saat{ color:var(--soluk); font:12px Consolas,monospace; grid-row:span 3; padding-top:2px; }
  .m{ font-weight:600; }
  .m i{ color:var(--soluk); font-style:normal; font-weight:600; font-size:12px; }
  .a{ color:#333; }
  .et{ font-size:11.5px; color:var(--soluk); }
  .et b{ padding:1px 6px; border-radius:99px; background:#eef0f3; color:var(--yazi); font-weight:600; }
  .et b.haiku,.et b.niyet{ background:#efe7fd; color:var(--mor); }
  .et b.stt{ background:#fdf0e1; color:var(--turuncu); }
  .et b.ekran{ background:#e3f4ec; color:var(--yesil); }
  .bos{ color:var(--soluk); padding:30px; text-align:center; background:var(--kart); border-radius:10px; border:1px solid var(--cizgi); }
</style>
</head>
<body>
<div class="sar">
  <h1>Müşteri AI Konuşmaları</h1>
  <div class="ust">
    @foreach ([1 => 'Son 24 saat', 3 => '3 gün', 7 => '7 gün', 30 => '30 gün'] as $g => $et)
      <a href="?gun={{ $g }}" class="{{ $gun == $g ? 'secili' : '' }}">{{ $et }}</a>
    @endforeach
    <span style="color:var(--soluk)">{{ count($oturumlar) }} konuşma · kayıtlar 30 gün tutulur</span>
  </div>
  <div class="link">İnceleme linki (salt okunur, Claude'a verilecek):
    <input id="link" type="text" readonly value="{{ $link }}" onclick="this.select()">
  </div>
  @forelse ($oturumlar as $o)
    <div class="oturum">
      <div class="ob">{{ $o['masa'] ?: 'Masa ?' }} <span>{{ $o['sube'] }}</span> <span>{{ substr($o['basladi'], 0, 16) }}</span> <span>{{ count($o['turlar']) }} tur</span></div>
      @foreach ($o['turlar'] as $t)
        <div class="tur">
          <div class="saat">{{ $t['saat'] }}</div>
          <div class="m">@if ($t['soru'] !== null && $t['soru'] !== '')<i>{{ $t['kanal'] === 'ses' ? '🎤' : '⌨️' }}</i> {{ $t['soru'] }}@else<i>(açılış)</i>@endif</div>
          <div class="a">🤖 {{ $t['cevap'] }}</div>
          <div class="et"><b class="{{ $t['kaynak'] }}">{{ $t['kaynak'] ?: '-' }}</b>@if ($t['aksiyon']) · {{ $t['aksiyon'] }}@endif @if ($t['sepet']) · sepet: {{ $t['sepet'] }}@endif @if ($t['dil'] && $t['dil'] !== 'tr') · dil: {{ $t['dil'] }}@endif</div>
        </div>
      @endforeach
    </div>
  @empty
    <div class="bos">Bu aralıkta konuşma yok. Müşteri QR'dan asistanla konuştukça burada görünür.</div>
  @endforelse
</div>
</body>
</html>

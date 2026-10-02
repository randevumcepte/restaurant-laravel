@extends('resteos_yonetim.layout')
@section('baslik','Dashboard')
@section('content')
@php $tl = fn($n) => number_format((float)$n,0,',','.').' ₺'; $maxTrend = max(1, collect($trend)->max('ciro')); @endphp

<div class="ry-grid ry-g4" style="margin-bottom:18px">
  <div class="ry-metric"><div class="ry-mic mor">🏪</div><div class="ry-mval">{{ $toplam }}</div><div class="ry-mlbl">Toplam Restoran</div></div>
  <div class="ry-metric"><div class="ry-mic yesil">✅</div><div class="ry-mval">{{ $aktif }}</div><div class="ry-mlbl">Aktif (Lisanslı)</div></div>
  <div class="ry-metric"><div class="ry-mic turuncu">🎁</div><div class="ry-mval">{{ $demo }}</div><div class="ry-mlbl">Demo Hesap</div></div>
  <div class="ry-metric"><div class="ry-mic kirmizi">⛔</div><div class="ry-mval">{{ $askida }}</div><div class="ry-mlbl">Askıda</div></div>
</div>

<div class="ry-grid ry-g4" style="margin-bottom:18px">
  <div class="ry-metric"><div class="ry-mic yesil">💰</div><div class="ry-mval">{{ $tl($bugunCiro) }}</div><div class="ry-mlbl">Bugün Ciro (tüm restoranlar)</div></div>
  <div class="ry-metric"><div class="ry-mic mavi">📈</div><div class="ry-mval">{{ $tl($son30Ciro) }}</div><div class="ry-mlbl">Son 30 Gün Ciro</div></div>
  <div class="ry-metric"><div class="ry-mic mor">🍽️</div><div class="ry-mval">{{ $acikAdisyon }}</div><div class="ry-mlbl">Şu An Açık Adisyon</div></div>
  <div class="ry-metric"><div class="ry-mic turuncu">🎫</div><div class="ry-mval">{{ $acikTicket }}</div><div class="ry-mlbl">Açık Destek Talebi</div></div>
</div>

<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>📊 Son 14 Gün Ciro (tüm restoranlar)</h3>
    <div class="ry-chart">
      @foreach($trend as $t)
        <div class="bar" style="height:{{ max(3, round($t['ciro']/$maxTrend*110)) }}px" title="{{ $t['gun'] }}: {{ $tl($t['ciro']) }}">
          <span>{{ $t['gun'] }}</span>
        </div>
      @endforeach
    </div>
    <div style="height:16px"></div>
  </div>

  <div class="ry-card">
    <h3>⏰ Üyeliği Yakında/Bitmiş Restoranlar</h3>
    @if($yakinda->isEmpty())
      <p class="ry-mut">Yakın zamanda biten üyelik yok. 👍</p>
    @else
      <table class="ry-table">
        <tbody>
        @foreach($yakinda as $s)
          @php $bitis=\Carbon\Carbon::parse($s->uyelik_bitis); $gecti=$bitis->isPast(); $kalan=now()->startOfDay()->diffInDays($bitis,false); @endphp
          <tr>
            <td class="ry-restoran"><a class="ry-satir" href="/resteos-yonetim/restoran/{{ $s->id }}">{{ $s->ad }}</a></td>
            <td style="text-align:right">
              @if($gecti)<span class="ry-badge danger">{{ abs($kalan) }} gün geçti</span>
              @else<span class="ry-badge warning">{{ $kalan }} gün kaldı</span>@endif
            </td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

<div class="ry-card">
  <h3>🕒 Son Sistem Hareketleri</h3>
  @if($sonLog->isEmpty())
    <p class="ry-mut">Henüz hareket yok.</p>
  @else
    <ul class="ry-timeline">
      @foreach($sonLog as $l)
        <li>
          <b>{{ $l->yonetici_ad ?? 'sistem' }}</b> — {{ $l->islem }}
          @if($l->detay)<span class="ry-mut">· {{ $l->detay }}</span>@endif
          <div class="zt">{{ \Carbon\Carbon::parse($l->created_at)->format('d.m.Y H:i') }}</div>
        </li>
      @endforeach
    </ul>
  @endif
</div>
@endsection

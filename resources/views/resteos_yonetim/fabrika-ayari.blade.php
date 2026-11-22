@extends('resteos_yonetim.layout')
@section('baslik','Fabrika Ayarı')
@section('content')
@php $silinecekler = array_filter($satirlar, fn ($s) => $s['sil'] > 0); $korunanlar = array_filter($satirlar, fn ($s) => $s['sil'] === 0 && $s['var'] > 0); @endphp
<div class="ry-card" style="max-width:860px">
  <h3>🧹 Fabrika Ayarı — sistemi temiz test ortamına döndür</h3>
  <p class="ry-mut" style="line-height:1.6">İşaretlediğiniz işletmenin <b>kaydı</b> ve <b>patron (sahip) girişi</b> kalır; satışlar, menü, ürünler, reçeteler, malzemeler, masalar,
    personel, stok, faturalar, talepler, bildirimler ve loglar silinir. İşaretlenmeyen işletmeler tamamen silinir.
    Departman, sebep, indirim gibi varsayılanlar yeni işletmede olduğu gibi ilk açılışta kendiliğinden kurulur.
    Silmeden önce her şey sunucuya <b>yedeklenir</b>; aşağıdan geri yüklenebilir.</p>

  <form method="GET" action="/resteos-yonetim/fabrika-ayari" style="margin:14px 0">
    <div style="font-weight:700;margin-bottom:6px">Korunacak işletme</div>
    @foreach($subeler as $s)
      <label style="display:block;margin:4px 0"><input type="checkbox" name="koru[]" value="{{ $s->id }}" @checked(in_array((int) $s->id, $koru, true)) onchange="this.form.submit()">
        #{{ $s->id }} · {{ $s->ad }} @if(!empty($s->demo_hesabi))<span class="ry-mut">(demo)</span>@endif</label>
    @endforeach
    <input type="hidden" name="baglanti" value="0">{{-- işaretsizken 0 gider; işaretliyse sonraki 1 geçerli olur --}}
    <label style="display:block;margin-top:10px"><input type="checkbox" name="baglanti" value="1" @checked($baglantiKoru) onchange="this.form.submit()">
      Bağlantı ayarlarını koru (WhatsApp hattı, e-posta, santral, e-Fatura / ödeme / muhasebe entegrasyonu, yasal bilgiler, yazıcı/cihazlar)</label>
  </form>

  <div class="ry-card" style="background:#fff7ed;border:1px solid #fed7aa;margin:10px 0">
    <b>Kalacak giriş:</b>
    @forelse($sahipler as $sp) {{ $sp->ad }} (işletme #{{ $sp->sube_id }})@if(!$loop->last), @endif
    @empty <span style="color:#b91c1c">Seçili işletmede patron girişi yok — sıfırlama yapılamaz.</span> @endforelse
    <br><b>Silinecek kayıt:</b> {{ number_format($toplam, 0, ',', '.') }} ({{ count($silinecekler) }} tablo)
  </div>

  <form method="POST" action="/resteos-yonetim/fabrika-ayari" onsubmit="return confirm('Sistem fabrika ayarına dönecek. Emin misiniz?')">
    @csrf
    @foreach($koru as $k)<input type="hidden" name="koru[]" value="{{ $k }}">@endforeach
    <input type="hidden" name="baglanti" value="{{ $baglantiKoru ? 1 : 0 }}">
    <label style="display:block;margin:8px 0">Onay için <b>FABRIKA AYARI</b> yazın:</label>
    <input type="text" name="onay" autocomplete="off" class="ry-input" style="max-width:260px">
    <button type="submit" class="ry-btn ry-btn-danger" @disabled(!$koru || $sahipler->isEmpty())>Yedekle ve sıfırla</button>
  </form>
</div>

<div class="ry-card" style="max-width:860px">
  <h3>Silinecekler</h3>
  <table class="ry-table">
    <thead><tr><th>Tablo</th><th style="text-align:right">Mevcut</th><th style="text-align:right">Silinecek</th><th>Not</th></tr></thead>
    <tbody>
    @forelse($silinecekler as $s)
      <tr><td>{{ $s['tablo'] }}</td><td style="text-align:right">{{ number_format($s['var'], 0, ',', '.') }}</td>
        <td style="text-align:right;font-weight:700">{{ number_format($s['sil'], 0, ',', '.') }}</td><td class="ry-mut">{{ $s['not'] }}</td></tr>
    @empty
      <tr><td colspan="4" class="ry-mut">Silinecek kayıt yok — sistem zaten temiz.</td></tr>
    @endforelse
    </tbody>
  </table>
  <details style="margin-top:12px"><summary class="ry-mut">Korunan dolu tablolar ({{ count($korunanlar) }})</summary>
    <table class="ry-table"><tbody>
    @foreach($korunanlar as $s)<tr><td>{{ $s['tablo'] }}</td><td style="text-align:right">{{ number_format($s['var'], 0, ',', '.') }}</td><td class="ry-mut">{{ $s['not'] }}</td></tr>@endforeach
    </tbody></table>
  </details>
</div>

<div class="ry-card" style="max-width:860px">
  <h3>Yedekler</h3>
  @forelse($yedekler as $y)
    <form method="POST" action="/resteos-yonetim/fabrika-ayari/geri-yukle" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:6px 0"
          onsubmit="return confirm('Bu yedekteki kayıtlar geri eklenecek. Emin misiniz?')">
      @csrf
      <input type="hidden" name="yedek" value="{{ $y['ad'] }}">
      <span style="min-width:280px">{{ $y['ad'] }} <span class="ry-mut">· {{ number_format($y['boyut'] / 1024, 0, ',', '.') }} KB · {{ date('d.m.Y H:i', $y['zaman']) }}</span></span>
      <input type="text" name="onay" placeholder="GERI YUKLE" autocomplete="off" class="ry-input" style="max-width:150px">
      <button type="submit" class="ry-btn ry-btn-sm">Geri yükle</button>
    </form>
  @empty
    <p class="ry-mut">Henüz yedek yok.</p>
  @endforelse
</div>
@endsection

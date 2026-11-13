@extends('resteos_yonetim.layout')
@section('baslik','Şube Grupları & Franchise')
@section('content')
@php
  $grupAd = $gruplar->pluck('ad', 'id');
  $bySube = $subeler->groupBy(fn ($s) => (int) $s->grup_id);
@endphp
<div class="ry-card">
  <h3>🔗 Nasıl çalışır?</h3>
  <p class="ry-mut" style="line-height:1.6;margin:0">
    <b>Grup</b> = bir patronun işletmeleri. Yalnız aynı gruptaki şubeler birbirinin stoğunu görür ve transfer yapar; başka gruplar <b>asla</b> görünmez.
    <b>Firma</b> vergi numarasından otomatik çıkar: aynı vergi no → <i>iç transfer</i> (sevk irsaliyesi, finans yok); farklı vergi no → <i>firmalar arası satış</i> (otomatik alış faturası + cari).
    <b>Franchise</b> alan işletme kendi grubunda durur; merkez grupla yalnız "franchise bağı" kurulur ve sadece merkezin <i>franchise deposu</i> işaretli şubeleri görünür.
    Vergi no, her şubenin panelindeki <b>Yasal Bilgiler</b> sayfasından gelir; eksikse transfer engellenir.
  </p>
</div>

<div class="ry-card">
  <h3>➕ Yeni grup</h3>
  <form method="post" action="/resteos-yonetim/sube-gruplari" class="ry-row">
    @csrf <input type="hidden" name="islem" value="grup_ekle">
    <input class="ry-input" name="ad" placeholder="Grup adı (ör. Ahmet Bey Restoranları)" required>
    <input class="ry-input" name="notlar" placeholder="Not (isteğe bağlı)">
    <button class="ry-btn ry-btn-primary" style="flex:0 0 auto">Grup oluştur</button>
  </form>
</div>

@foreach ($gruplar as $g)
<div class="ry-card">
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <h3 style="margin:0">🏢 {{ $g->ad }}</h3>
    <span class="ry-mut">#{{ $g->id }} · {{ count($bySube[(int) $g->id] ?? []) }} şube</span>
    <form method="post" action="/resteos-yonetim/sube-gruplari" style="margin-left:auto" onsubmit="return confirm('Grup silinsin mi? Şubeler gruptan çıkar, franchise bağları kalkar.')">
      @csrf <input type="hidden" name="islem" value="grup_sil"><input type="hidden" name="grup_id" value="{{ $g->id }}">
      <button class="ry-btn ry-btn-danger ry-btn-sm">Grubu sil</button>
    </form>
  </div>
  @if ($g->notlar)<p class="ry-mut">{{ $g->notlar }}</p>@endif
  <table class="ry-table" style="margin-top:10px">
    <thead><tr><th>Şube</th><th>Ünvan</th><th>Vergi no</th><th>Firma</th><th>Franchise deposu</th><th></th></tr></thead>
    <tbody>
    @php $vknRenk = []; $palet = ['#0891B2','#7C3AED','#DB2777','#059669','#D97706','#2563EB']; @endphp
    @forelse ($bySube[(int) $g->id] ?? [] as $s)
      @php if ($s->vkn && !isset($vknRenk[$s->vkn])) $vknRenk[$s->vkn] = $palet[count($vknRenk) % count($palet)]; @endphp
      <tr>
        <td><b>{{ $s->ad }}</b> <span class="ry-mut">#{{ $s->id }}</span></td>
        <td>{{ $s->unvan }}</td>
        <td>{!! $s->vkn ? e($s->vkn) : '<span style="color:var(--ry-kirmizi)">EKSİK</span>' !!}</td>
        <td>@if ($s->vkn)<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $vknRenk[$s->vkn] }}"></span> aynı renk = aynı firma @else — @endif</td>
        <td>
          <form method="post" action="/resteos-yonetim/sube-gruplari">@csrf <input type="hidden" name="islem" value="depo"><input type="hidden" name="sube_id" value="{{ $s->id }}">
            <input type="hidden" name="deger" value="{{ $s->franchise_depo ? 0 : 1 }}"><button class="ry-btn ry-btn-soft ry-btn-sm">{{ $s->franchise_depo ? '✅ Evet (kaldır)' : 'Hayır (işaretle)' }}</button></form>
        </td>
        <td style="text-align:right">
          <form method="post" action="/resteos-yonetim/sube-gruplari">@csrf <input type="hidden" name="islem" value="sube_ata"><input type="hidden" name="sube_id" value="{{ $s->id }}"><input type="hidden" name="grup_id" value="0">
            <button class="ry-btn ry-btn-soft ry-btn-sm">Gruptan çıkar</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="ry-mut">Henüz şube yok — aşağıdan ekleyin.</td></tr>
    @endforelse
    </tbody>
  </table>
  <form method="post" action="/resteos-yonetim/sube-gruplari" class="ry-row" style="margin-top:10px">
    @csrf <input type="hidden" name="islem" value="sube_ata"><input type="hidden" name="grup_id" value="{{ $g->id }}">
    <select class="ry-select" name="sube_id" required>
      <option value="">Gruba şube ekle…</option>
      @foreach ($bySube[0] ?? [] as $s)<option value="{{ $s->id }}">{{ $s->ad }} (#{{ $s->id }}){{ $s->vkn ? ' · VKN ' . $s->vkn : ' · vergi no yok' }}</option>@endforeach
    </select>
    <button class="ry-btn ry-btn-primary" style="flex:0 0 auto">Ekle</button>
  </form>
</div>
@endforeach

<div class="ry-card">
  <h3>🤝 Franchise bağları</h3>
  <p class="ry-mut">Merkez grubun "franchise deposu" işaretli şubeleri, franchise grubun şubelerine mal gönderebilir. Fiyat = maliyet × çarpan (ör. 1,15 = %15 üstü). Franchise şubeler birbirini ve merkezin diğer şubelerini görmez.</p>
  @if ($baglar->count())
  <table class="ry-table">
    <thead><tr><th>Merkez</th><th>Franchise</th><th>Fiyat çarpanı</th><th></th></tr></thead>
    <tbody>
    @foreach ($baglar as $b)
      <tr><td>{{ $grupAd[$b->merkez_grup_id] ?? '#' . $b->merkez_grup_id }}</td><td>{{ $grupAd[$b->franchise_grup_id] ?? '#' . $b->franchise_grup_id }}</td><td>× {{ rtrim(rtrim(number_format((float) $b->fiyat_carpani, 3, ',', '.'), '0'), ',') }}</td>
        <td style="text-align:right"><form method="post" action="/resteos-yonetim/sube-gruplari" onsubmit="return confirm('Bağ kaldırılsın mı?')">@csrf <input type="hidden" name="islem" value="franchise_kaldir"><input type="hidden" name="bag_id" value="{{ $b->id }}"><button class="ry-btn ry-btn-danger ry-btn-sm">Kaldır</button></form></td></tr>
    @endforeach
    </tbody>
  </table>
  @endif
  <form method="post" action="/resteos-yonetim/sube-gruplari" class="ry-row" style="margin-top:10px">
    @csrf <input type="hidden" name="islem" value="franchise_bagla">
    <select class="ry-select" name="merkez_grup_id" required><option value="">Merkez grup…</option>@foreach ($gruplar as $g)<option value="{{ $g->id }}">{{ $g->ad }}</option>@endforeach</select>
    <select class="ry-select" name="franchise_grup_id" required><option value="">Franchise grup…</option>@foreach ($gruplar as $g)<option value="{{ $g->id }}">{{ $g->ad }}</option>@endforeach</select>
    <input class="ry-input" name="carpan" value="1" style="max-width:110px" title="Fiyat çarpanı">
    <button class="ry-btn ry-btn-primary" style="flex:0 0 auto">Bağla</button>
  </form>
</div>
@endsection

@extends('resteos_yonetim.layout')
@section('baslik', $sube->ad)
@section('content')
@php
  $tl = fn($n) => number_format((float)$n,0,',','.').' ₺';
  $bitis = $sube->uyelik_bitis ? \Carbon\Carbon::parse($sube->uyelik_bitis) : null;
  $skorRenk = $saglik['skor']>=80 ? 'var(--ry-yesil)' : ($saglik['skor']>=50 ? 'var(--ry-turuncu)' : 'var(--ry-kirmizi)');
@endphp

<div class="ry-between" style="margin-bottom:16px">
  <a href="/resteos-yonetim/restoranlar" class="ry-btn ry-btn-soft ry-btn-sm">← Restoranlar</a>
  <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/hesabina-gir" onsubmit="return confirm('Bu restoranın paneline giriş yapılsın mı?')">
    @csrf <button class="ry-btn ry-btn-primary ry-btn-sm" type="submit">🔓 Restoran Paneline Gir</button>
  </form>
</div>

<!-- ÖZET METRİK -->
<div class="ry-grid ry-g4" style="margin-bottom:18px">
  <div class="ry-metric"><div class="ry-mic yesil">💰</div><div class="ry-mval">{{ $tl($bugunCiro) }}</div><div class="ry-mlbl">Bugün Ciro</div></div>
  <div class="ry-metric"><div class="ry-mic mavi">📈</div><div class="ry-mval">{{ $tl($son30Ciro) }}</div><div class="ry-mlbl">30 Gün Ciro</div></div>
  <div class="ry-metric"><div class="ry-mic mor">🍽️</div><div class="ry-mval">{{ $acikMasa }}</div><div class="ry-mlbl">Açık Masa</div></div>
  <div class="ry-metric"><div class="ry-mic turuncu">📋</div><div class="ry-mval">{{ $urunSay }}</div><div class="ry-mlbl">Menü Ürünü</div></div>
</div>

<div class="ry-grid ry-g2">
  <!-- SOL: Lisans + Sağlık -->
  <div>
    <div class="ry-card">
      <h3>🎫 Lisans & Üyelik <span class="ry-badge {{ $durum['renk'] }}" style="margin-left:auto">{{ $durum['ad'] }}</span></h3>
      <p class="ry-mut">Tür: <b>{{ ucfirst($sube->uyelik_turu ?? 'demo') }}</b> · Bitiş: <b>{{ $bitis ? $bitis->format('d.m.Y') : '—' }}</b>
        @if($bitis) ({{ $bitis->isPast() ? abs(now()->startOfDay()->diffInDays($bitis,false)).' gün geçti' : now()->startOfDay()->diffInDays($bitis,false).' gün kaldı' }})@endif
      </p>
      @if($sube->askiya_alindi)<div class="ry-alert hata" style="margin-top:10px">⛔ Askıda: {{ $sube->askiya_sebep ?? '-' }}</div>@endif
      <hr class="ry-hr">
      <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/sure-uzat">
        @csrf
        <label class="ry-mut" style="font-weight:700">Hızlı Süre Uzat</label>
        <div class="ry-flex" style="margin:8px 0;flex-wrap:wrap">
          @foreach([7,15,30,90] as $g)
            <button class="ry-btn ry-btn-soft ry-btn-sm" name="gun" value="{{ $g }}" type="submit">+{{ $g }} gün</button>
          @endforeach
        </div>
        <div class="ry-flex">
          <input class="ry-input" type="date" name="tarih">
          <button class="ry-btn ry-btn-primary ry-btn-sm" type="submit">Tarihe Ayarla</button>
        </div>
      </form>
      <hr class="ry-hr">
      <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/lisans-aktif" class="ry-flex" style="flex-wrap:wrap">
        @csrf
        <select class="ry-select" name="tur" style="flex:1">
          <option value="baslangic">Başlangıç</option><option value="standart" selected>Standart</option><option value="pro">Pro</option>
        </select>
        <select class="ry-select" name="ay" style="width:90px"><option value="1">1 ay</option><option value="3">3 ay</option><option value="6">6 ay</option><option value="12">12 ay</option></select>
        <button class="ry-btn ry-btn-ok ry-btn-sm" type="submit">Lisansı Aktif Et</button>
      </form>
      <hr class="ry-hr">
      @if($sube->askiya_alindi)
        <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/aktif-et">@csrf
          <button class="ry-btn ry-btn-ok ry-btn-blok" type="submit">✅ Askıdan Çıkar (Aktif Et)</button></form>
      @else
        <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/askiya-al" class="ry-flex">@csrf
          <input class="ry-input" name="sebep" placeholder="Askıya alma sebebi">
          <button class="ry-btn ry-btn-danger ry-btn-sm" type="submit">⛔ Askıya Al</button></form>
      @endif
    </div>

    <div class="ry-card">
      <h3>❤️ Sağlık Skoru</h3>
      <div class="ry-between" style="margin-bottom:10px">
        <span style="font-size:30px;font-weight:900;color:{{ $skorRenk }}">{{ $saglik['skor'] }}</span>
        <span class="ry-mut">/ 100</span>
      </div>
      <div class="ry-health"><span style="width:{{ $saglik['skor'] }}%;background:{{ $skorRenk }}"></span></div>
      @if(count($saglik['sebep']))
        <ul class="ry-mut" style="margin:12px 0 0;padding-left:18px;font-size:12.5px">
          @foreach($saglik['sebep'] as $sb)<li>{{ $sb }}</li>@endforeach
        </ul>
      @else
        <p class="ry-mut" style="margin-top:10px">Her şey yolunda görünüyor. 👍</p>
      @endif
    </div>
  </div>

  <!-- SAĞ: Bilgi + Personel + Notlar -->
  <div>
    <div class="ry-card">
      <h3>✏️ Restoran Bilgileri</h3>
      <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/bilgi">@csrf
        <div class="ry-field"><label>Restoran Adı</label><input class="ry-input" name="ad" value="{{ $sube->ad }}"></div>
        <div class="ry-row">
          <div class="ry-field"><label>Yetkili</label><input class="ry-input" name="yetkili_ad" value="{{ $sube->yetkili_ad }}"></div>
          <div class="ry-field"><label>Yetkili Tel</label><input class="ry-input" name="yetkili_tel" value="{{ $sube->yetkili_tel }}"></div>
        </div>
        <div class="ry-row">
          <div class="ry-field"><label>Telefon</label><input class="ry-input" name="telefon" value="{{ $sube->telefon }}"></div>
          <div class="ry-field"><label>Şehir</label><input class="ry-input" name="sehir" value="{{ $sube->sehir }}"></div>
        </div>
        <div class="ry-field"><label>Adres</label><input class="ry-input" name="adres" value="{{ $sube->adres }}"></div>
        <button class="ry-btn ry-btn-primary" type="submit">Kaydet</button>
      </form>
    </div>

    <div class="ry-card">
      <h3>👤 Personel ({{ $personeller->count() }})</h3>
      @if($personeller->isEmpty())<p class="ry-mut">Personel yok.</p>@else
      <table class="ry-table">
        <tbody>
        @foreach($personeller as $p)
          <tr><td class="ry-restoran">{{ $p->ad }}</td>
            <td><span class="ry-badge mor">{{ ucfirst($p->rol) }}</span></td>
            <td style="text-align:right">@if($p->aktif)<span class="ry-badge success">Aktif</span>@else<span class="ry-badge danger">Pasif</span>@endif</td>
          </tr>
        @endforeach
        </tbody>
      </table>
      @endif
    </div>

    <div class="ry-card">
      <h3>📝 Notlar</h3>
      <form method="post" action="/resteos-yonetim/restoran/{{ $sube->id }}/not" class="ry-flex" style="margin-bottom:12px">@csrf
        <input class="ry-input" name="icerik" placeholder="Bu restoran hakkında not...">
        <label class="ry-mut ry-flex" style="flex:0 0 auto;font-weight:700"><input type="checkbox" name="pinli" value="1"> Sabitle</label>
        <button class="ry-btn ry-btn-primary ry-btn-sm" type="submit">Ekle</button>
      </form>
      @forelse($notlar as $n)
        <div style="padding:10px 0;border-bottom:1px solid var(--ry-line)">
          <div class="ry-between">
            <span>@if($n->pinli)📌 @endif{{ $n->icerik }}</span>
            <form method="post" action="/resteos-yonetim/not/{{ $n->id }}/sil">@csrf<button class="ry-btn ry-btn-danger ry-btn-sm" type="submit">Sil</button></form>
          </div>
          <div class="zt ry-mut">{{ $n->yonetici_ad }} · {{ \Carbon\Carbon::parse($n->created_at)->format('d.m.Y H:i') }}</div>
        </div>
      @empty
        <p class="ry-mut">Henüz not yok.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection

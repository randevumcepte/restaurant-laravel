@extends('layout.app')
@section('rk_ikon', 'tune')
@section('baslik', 'İşletme Büyüklüğü')
@section('title', 'İşletme Büyüklüğü')

@push('head')
<style>
  .olc-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:14px 0}
  @media(max-width:860px){ .olc-grid{grid-template-columns:1fr} }
  .olc-kart{display:flex;flex-direction:column;gap:9px;background:var(--card);border:2px solid var(--line);border-radius:16px;padding:16px;cursor:pointer;transition:.15s}
  .olc-kart:hover{border-color:color-mix(in srgb,var(--mor1) 45%,var(--line))}
  .olc-kart:has(input:checked){border-color:var(--mor1);background:color-mix(in srgb,var(--mor1) 6%,var(--card))}
  .olc-kart input{position:absolute;opacity:0;pointer-events:none}
  .olc-ust{display:flex;align-items:center;gap:10px}
  .olc-ic{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);flex:none}
  .olc-ad{font-size:15px;font-weight:800;color:var(--ink)}
  .olc-kim{font-size:12px;color:var(--sub)}
  .olc-kart ul{margin:0;padding-left:18px;font-size:12.5px;color:var(--sub);line-height:1.6}
  .olc-kart h5{margin:4px 0 0;font-size:11px;font-weight:800;color:var(--sub2);letter-spacing:.3px;text-transform:uppercase}
  .olc-not{font-size:12.5px;color:var(--sub);line-height:1.6}
</style>
@endpush

@section('content')
<div class="rk-body">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:6px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">İşletmeniz ne büyüklükte?</span>
  </div>
  <div class="olc-not">Seçiminize göre departmanlar kurulur ve menünüz sadeleşir. İşletmeniz büyüdüğünde buradan değiştirebilirsiniz; hiçbir veriniz silinmez.</div>
  @if (!$durum['olcek'])
    <div class="olc-not" style="margin-top:6px"><b>Şu an tam menü kullanıyorsunuz.</b> Bir seçim yapmazsanız böyle kalır.</div>
  @endif

  <form method="POST" action="/isletme-olcegi">@csrf
    <div class="olc-grid">
      <label class="olc-kart">
        <input type="radio" name="olcek" value="kucuk" required @checked($durum['olcek'] === 'kucuk')>
        <div class="olc-ust"><div class="olc-ic"><span class="material-icons-outlined">storefront</span></div><div><div class="olc-ad">Küçük</div><div class="olc-kim">Örn. 1 aşçı + birkaç yardımcı</div></div></div>
        <h5>Kurulan departmanlar</h5>
        <ul>@foreach ($departmanAd['kucuk'] as $d)<li>{{ $d }}</li>@endforeach</ul>
        <h5>Menü</h5>
        <div class="olc-not">Günlük işler: satış, mutfak, stok, personel, kasa ve raporlar.</div>
      </label>
      <label class="olc-kart">
        <input type="radio" name="olcek" value="orta" @checked($durum['olcek'] === 'orta')>
        <div class="olc-ust"><div class="olc-ic"><span class="material-icons-outlined">restaurant</span></div><div><div class="olc-ad">Orta</div><div class="olc-kim">Ayrı bar ve satın alma sorumlusu olan</div></div></div>
        <h5>Kurulan departmanlar</h5>
        <ul>@foreach ($departmanAd['orta'] as $d)<li>{{ $d }}</li>@endforeach</ul>
        <h5>Menü</h5>
        <div class="olc-not">Küçük'ün hepsi + tam satın alma süreci, rezervasyon satış, müşteri takibi, vardiya ve izin.</div>
      </label>
      <label class="olc-kart">
        <input type="radio" name="olcek" value="buyuk" @checked($durum['olcek'] === 'buyuk')>
        <div class="olc-ust"><div class="olc-ic"><span class="material-icons-outlined">domain</span></div><div><div class="olc-ad">Büyük</div><div class="olc-kim">Mutfağı istasyonlara ayrılmış, ofisi olan</div></div></div>
        <h5>Kurulan departmanlar</h5>
        <div class="olc-not">{{ $departmanAd['buyuk'] }} hazır departmanın tamamı: Soğuk Mutfak, Pastane, Kasap, İK, Finans, Kalite, IT...</div>
        <h5>Menü</h5>
        <div class="olc-not">Programın tamamı.</div>
      </label>
    </div>
    <button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:18px">check</span> Kaydet</button>
  </form>

  <div class="rk-bolum" style="margin:22px 0 6px">
    <span class="cubuk" style="background:var(--mavi)"></span>
    <span class="b" style="font-size:14px">Sonraki adım</span>
  </div>
  <div class="olc-not">
    <a href="/personel">Personel</a> ekranında çalışanlarınıza departmanlarını seçin; şef veya baş aşçı için "Sorumlu" kutusunu işaretleyin. Yardımcıların açtığı satın alma talepleri önce sorumluya, onaydan sonra size düşer.<br>
    Tek bir departman daha mı lazım? <a href="/departman-yonetim">Departman & Yetki</a> ekranından ekleyin; menüsü kendiliğinden açılır.
  </div>
</div>
@endsection

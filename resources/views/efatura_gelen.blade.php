@extends('layout.app')
@section('title', 'Gelen e-Faturalar')
@section('rk_ikon', 'move_to_inbox')
@section('baslik', 'Gelen e-Faturalar')
@section('rk_alt', 'Entegratörden otomatik çekim · XML/ZIP yükleme · kalem eşleştirme → alış faturası + Fiyat Radarı')

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $rozet = fn($renk) => 'color:var(' . $renk . ');background:color-mix(in srgb,var(' . $renk . ') 13%,transparent);border-color:color-mix(in srgb,var(' . $renk . ') 40%,transparent)';
  $durumlar = ['bekliyor' => ['Eşleştirme bekliyor', '--amber'], 'aktarildi' => ['Aktarıldı', '--yesil'], 'iade' => ['İade', '--mavi'], 'yok_sayildi' => ['Yok sayıldı', '--sub'], 'tumu' => ['Tümü', '--mor1']];
  $suruHazir = $b && $b->entegrator && ($entegratorler[$b->entegrator][1] ?? false);
@endphp
<style>
  .efg-sekme{display:flex;gap:6px;flex-wrap:wrap;margin:4px 0 12px}
  .efg-sekme a{padding:7px 12px;border-radius:999px;border:1px solid var(--line);font-size:12.5px;font-weight:600;color:var(--ink);text-decoration:none}
  .efg-sekme a.on{background:var(--mor1);border-color:var(--mor1);color:#fff}
  .efg-ust{display:grid;grid-template-columns:1.1fr 1fr;gap:14px;margin-bottom:16px}
  @media (max-width:900px){.efg-ust{grid-template-columns:1fr}}
  .efg-durum{display:flex;align-items:center;gap:8px;font-size:12.5px;margin-top:8px}
  .efg-nokta{width:9px;height:9px;border-radius:50%;flex:none}
  .efg-tablo td{vertical-align:middle}
  .efg-not{font-size:11.5px;color:var(--sub);margin-top:2px;max-width:420px}
</style>

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="efg-ust">
  {{-- Otomatik bağlantı --}}
  <div class="rk-card">
    <div class="rk-bolum" style="margin-bottom:6px"><span class="cubuk" style="background:var(--mor1)"></span><span class="b">Entegratör Bağlantısı</span>
      @if ($b && $b->aktif && $suruHazir)<span class="rk-rozet" style="{{ $rozet('--yesil') }}">Otomatik açık</span>@else<span class="rk-rozet" style="{{ $rozet('--sub') }}">Kapalı</span>@endif
    </div>
    <div class="rk-sub2" style="font-size:12.5px">Faturalarınız hangi e-fatura entegratörüne geliyorsa onu seçin. Gelen faturalar 15 dakikada bir otomatik çekilir; OCR ya da AI gerekmez, rakamlar faturadaki gibi birebir gelir.</div>
    @if ($b && ($b->son_sonuc || $b->son_hata))
      <div class="efg-durum"><span class="efg-nokta" style="background:var({{ $b->son_hata ? '--kirmizi' : '--yesil' }})"></span><span>{{ $b->son_hata ?: $b->son_sonuc }}</span></div>
    @endif
    @if ($yonetici)
    <form method="POST" action="/efatura-gelen/baglanti" style="margin-top:10px">
      @csrf
      <div class="rk-grid" style="grid-template-columns:1fr 1fr;gap:10px">
        <div><label class="rk-lbl" style="margin-top:0">Entegratör</label>
          <select name="entegrator" class="rk-inp">
            <option value="">— Seç —</option>
            @foreach ($entegratorler as $kod => $e)<option value="{{ $kod }}" @selected(($b->entegrator ?? '') === $kod)>{{ $e[0] }}{{ $e[1] ? '' : ' (yakında · XML yükle)' }}</option>@endforeach
          </select></div>
        <div><label class="rk-lbl" style="margin-top:0">API anahtarı {!! $anahtarVar ? '<span style="color:var(--yesil)">· kayıtlı ✓</span>' : '' !!}</label>
          <input type="password" name="api_anahtar" class="rk-inp" autocomplete="new-password" placeholder="{{ $anahtarVar ? 'Değiştirmek için yeni anahtarı yaz' : 'Entegratör portalından alınır' }}"></div>
        <div><label class="rk-lbl" style="margin-top:0">İlk çekim başlangıcı</label>
          <input type="date" name="baslangic" class="rk-inp" value="{{ $b->baslangic ?? now()->subDays(30)->toDateString() }}"></div>
        <div><label class="rk-lbl" style="margin-top:0">Stok girişi</label>
          <select name="stok_modu" class="rk-inp">
            <option value="" @selected(!($b->stok_modu ?? null))>Otomatik ({{ $stokModu === 'mal_kabul' ? 'mal kabulde' : 'faturadan' }})</option>
            <option value="mal_kabul" @selected(($b->stok_modu ?? null) === 'mal_kabul')>Mal kabulde yapılıyor (fatura yalnız finansal)</option>
            <option value="fatura" @selected(($b->stok_modu ?? null) === 'fatura')>Faturadan stok girişi yap</option>
          </select></div>
      </div>
      <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:10px;font-size:12.5px">
        <label style="display:flex;gap:6px;align-items:center"><input type="checkbox" name="aktif" value="1" @checked($b->aktif ?? false)> Otomatik çek</label>
        <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="ortam" value="canli" @checked(($b->ortam ?? 'canli') === 'canli')> Canlı</label>
        <label style="display:flex;gap:6px;align-items:center"><input type="radio" name="ortam" value="test" @checked(($b->ortam ?? '') === 'test')> Test</label>
        @if ($anahtarVar)<label style="display:flex;gap:6px;align-items:center;color:var(--kirmizi)"><input type="checkbox" name="anahtar_sil" value="1"> Anahtarı sil</label>@endif
        <button class="rk-btn" style="background:var(--mor1);margin-left:auto" type="submit"><span class="material-icons-outlined" style="font-size:17px;color:#fff">link</span> Kaydet ve test et</button>
      </div>
    </form>
    @else
      <div class="rk-sub2" style="font-size:12px;margin-top:8px">Bağlantı ayarını işletme sahibi ya da müdür yapar.</div>
    @endif
    @if ($b && $b->aktif && $suruHazir)
      <form method="POST" action="/efatura-gelen/cek" style="margin-top:8px">@csrf
        <button class="rk-btn bos" type="submit" style="padding:7px 12px"><span class="material-icons-outlined" style="font-size:16px">sync</span> Şimdi çek</button></form>
    @endif
  </div>

  {{-- Elle yükleme --}}
  <div class="rk-card">
    <div class="rk-bolum" style="margin-bottom:6px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">XML / ZIP Yükle</span></div>
    <div class="rk-sub2" style="font-size:12.5px">Entegratör portalından (ya da GİB portalından) gelen faturaları <b>XML</b> olarak indirip buraya bırakın. Toplu indirilen <b>ZIP</b> de olur; geçmiş faturaları bir kerede içeri almak için idealdir.</div>
    <form method="POST" action="/efatura-gelen/yukle" enctype="multipart/form-data" style="margin-top:12px" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').innerText='Okunuyor…'">
      @csrf
      <input type="file" name="dosyalar[]" multiple accept=".xml,.zip,application/xml,text/xml,application/zip" required class="rk-inp" style="padding:8px">
      <button class="rk-btn" style="background:var(--mavi);margin-top:10px" type="submit"><span class="material-icons-outlined" style="font-size:17px;color:#fff">upload_file</span> Yükle ve oku</button>
    </form>
    <div class="rk-sub2" style="font-size:11.5px;margin-top:8px">Aynı fatura iki kez yüklenirse ETTN'den tanınır, tekrar kaydedilmez. Kalem toplamı fatura toplamını tutmayan faturalar otomatik aktarılmaz, kontrol için bekletilir.</div>
  </div>
</div>

<div class="efg-sekme">
  @foreach ($durumlar as $k => $d)
    <a href="/efatura-gelen?durum={{ $k }}" class="{{ $durum === $k ? 'on' : '' }}">{{ $d[0] }} @if($k !== 'tumu')({{ (int) ($say[$k] ?? 0) }})@else({{ (int) $say->sum() }})@endif</a>
  @endforeach
  <form method="GET" action="/efatura-gelen" style="margin-left:auto;display:flex;gap:6px">
    <input type="hidden" name="durum" value="{{ $durum }}">
    <input type="text" name="q" value="{{ $ara }}" class="rk-inp" placeholder="Fatura no / tedarikçi" style="width:200px;padding:7px 10px">
  </form>
</div>

@if ($yonetici && (int) ($say['bekliyor'] ?? 0) >= 3)
  <div class="rk-card" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:12px;padding:12px 14px">
    <span class="material-icons-outlined" style="color:var(--amber)">bolt</span>
    <div style="flex:1;min-width:240px;font-size:12.5px"><b>İlk kurulum mu?</b> Geçmiş faturaları tek tek eşleştirmek yerine, eşleşmeyen tüm ürünleri <b>yeni malzeme kartı</b> olarak açıp hepsini aktarabilirsiniz. Aynı ürün tek kart olur; sonradan gelen faturalar bu kartlarla otomatik eşleşir.</div>
    <form method="POST" action="/efatura-gelen/toplu-yeni" onsubmit="return confirm('{{ (int) ($say['bekliyor'] ?? 0) }} bekleyen faturadaki eşleşmeyen ürünler için yeni malzeme kartları açılacak ve faturalar alış faturasına aktarılacak. Devam edilsin mi?') && (this.querySelector('button').disabled=true, this.querySelector('button').innerText='İşleniyor…', true)">@csrf
      <button class="rk-btn" style="background:var(--amber)" type="submit">Tümünü kartla ve aktar</button></form>
  </div>
@endif

@if ($liste->isEmpty())
  <div class="rk-card" style="text-align:center;padding:36px"><span class="material-icons-outlined" style="font-size:48px;color:var(--line)">move_to_inbox</span>
    <div class="rk-sub" style="margin-top:10px">{{ $say->sum() ? 'Bu filtrede fatura yok.' : 'Henüz gelen e-fatura yok. Entegratörü bağlayın ya da XML/ZIP yükleyin.' }}</div></div>
@else
  <div class="rk-tablo efg-tablo"><table>
    <thead><tr><th>Tarih</th><th>Tedarikçi</th><th>Fatura No</th><th class="sag">Kalem</th><th class="sag">Tutar (KDV dahil)</th><th>Durum</th><th class="sag"></th></tr></thead>
    <tbody>
    @foreach ($liste as $f)
      @php $d = $durumlar[$f->durum] ?? [$f->durum, '--sub']; $ek = (int) ($eksik[$f->id] ?? 0); @endphp
      <tr>
        <td class="rk-sub2">{{ $f->tarih ? \Carbon\Carbon::parse($f->tarih)->format('d.m.Y') : '—' }}</td>
        <td><div style="font-weight:600">{{ $f->satici_ad ?: '—' }}</div>@if ($f->not)<div class="efg-not">{{ $f->not }}</div>@endif</td>
        <td class="rk-sub">{{ $f->fatura_no ?: '—' }}@if ($f->profil === 'TICARIFATURA')<div class="efg-not">Ticari{{ $f->cevap ? ' · ' . ($f->cevap === 'kabul' ? 'kabul edildi' : 'reddedildi') : '' }}</div>@endif</td>
        <td class="sag">{{ (int) ($kalemSay[$f->id] ?? 0) }}@if ($ek && $f->durum === 'bekliyor')<div class="efg-not" style="color:var(--amber)">{{ $ek }} eşleşmedi</div>@endif</td>
        <td class="sag" style="font-weight:700">{{ $tl($f->genel_toplam) }}@if (!$f->tutarli)<div class="efg-not" style="color:var(--kirmizi)">toplam tutmuyor</div>@endif</td>
        <td><span class="rk-rozet" style="{{ $rozet($d[1]) }}">{{ $d[0] }}</span></td>
        <td class="sag"><a href="/efatura-gelen/{{ $f->id }}" class="rk-btn {{ $f->durum === 'bekliyor' ? '' : 'bos' }}" style="{{ $f->durum === 'bekliyor' ? 'background:var(--amber);' : '' }}padding:7px 12px">{{ $f->durum === 'bekliyor' ? 'Eşleştir' : 'Aç' }}</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @if ($liste->count() >= 300)<div class="rk-sub2" style="font-size:12px;margin-top:8px">İlk 300 fatura gösteriliyor; aramayı kullanın.</div>@endif
@endif
@endsection

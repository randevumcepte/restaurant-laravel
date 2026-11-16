{{-- Barkod & Etiket Merkezi sekme çubuğu (Demirbaş sayfasında da görünür). $aktif = sekme anahtarı --}}
@php
  $emSek = [['urunler', '/barkod-etiket', 'inventory', 'Ürünler'], ['urun', '/barkod-etiket/urun-etiketi', 'local_offer', 'Ürün Etiketi'], ['tarti', '/barkod-etiket/tarti', 'scale', 'Tartılı Ürün'],
    ['raf', '/barkod-etiket/raf', 'style', 'Raf Kartı'], ['barkod', '/barkod-etiket/barkod', 'qr_code', 'Barkod Oluştur'], ['demirbas', '/demirbas', 'handyman', 'Demirbaş'], ['ayar', '/barkod-etiket/ayarlar', 'settings', 'Ayarlar']];
@endphp
<style>
#dm .em-sek{display:flex;gap:6px;overflow-x:auto;scrollbar-width:none;margin:14px 0 4px;padding:4px;background:var(--card);border:1px solid var(--line);border-radius:15px}
#dm .em-sek::-webkit-scrollbar{display:none}
#dm .em-sek a{flex:none;display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border-radius:11px;font-size:13px;font-weight:700;color:var(--sub);text-decoration:none;white-space:nowrap}
#dm .em-sek a .ms{font-size:18px}
#dm .em-sek a:hover{background:var(--card2);color:var(--ink)}
#dm .em-sek a.on{background:linear-gradient(135deg,var(--marka),var(--marka2));color:#fff;box-shadow:0 4px 12px rgba(99,102,241,.25)}
#dm .em-sek a i{font-style:normal;background:#F43F5E;color:#fff;border-radius:10px;padding:0 6px;font-size:11px;line-height:17px}
</style>
<nav class="em-sek" aria-label="Barkod ve etiket bölümleri">
  @foreach ($emSek as [$k, $u, $ik, $ad])
    <a href="{{ $u }}" class="{{ $aktif === $k ? 'on' : '' }}"><span class="ms">{{ $ik }}</span>{{ $ad }}@if ($k === 'raf' && !empty($rafSay))<i>{{ $rafSay }}</i>@endif</a>
  @endforeach
</nav>

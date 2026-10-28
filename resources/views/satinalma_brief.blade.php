@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', 'Satınalma Sabah Brifingi')
@section('title', 'Satınalma Sabah Brifingi')

@push('head')
<style>
#sb{max-width:1100px}
#sb .sb-hero{display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-radius:20px;padding:22px 26px;margin-bottom:18px;box-shadow:0 10px 30px rgba(124,58,237,.28)}
#sb .sb-orb{width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 0 0 0 rgba(255,255,255,.3);animation:sbPulse 2.4s ease-out infinite}
#sb .sb-orb .material-icons-outlined{color:#fff;font-size:30px}
@keyframes sbPulse{0%{box-shadow:0 0 0 0 rgba(255,255,255,.35)}70%{box-shadow:0 0 0 12px rgba(255,255,255,0)}100%{box-shadow:0 0 0 0 rgba(255,255,255,0)}}
#sb .sb-hero .selam{font-size:22px;font-weight:900;line-height:1.1;letter-spacing:-.4px;margin-bottom:4px}
#sb .sb-hero .alt{font-size:13px;color:rgba(255,255,255,.85);font-weight:600}
#sb .sb-hero .ozet-sat{display:flex;gap:18px;margin-top:12px;flex-wrap:wrap}
#sb .sb-hero .oz{display:flex;align-items:center;gap:7px;background:rgba(255,255,255,.16);padding:7px 13px;border-radius:14px;font-size:12.5px;font-weight:700}
#sb .sb-hero .oz .material-icons-outlined{font-size:16px}

#sb .sb-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
@media(max-width:860px){#sb .sb-grid{grid-template-columns:1fr}}

#sb .sb-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 18px;box-shadow:var(--golge)}
#sb .sb-blok h4{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:800;color:var(--sub2);letter-spacing:.5px;text-transform:uppercase;margin:0 0 12px}
#sb .sb-blok h4 .ikon-ct{width:28px;height:28px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#sb .sb-blok h4 .ikon-ct .material-icons-outlined{font-size:17px}
#sb .sb-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line);font-size:13.5px}
#sb .sb-sat:first-of-type{border-top:none}
#sb .sb-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
#sb .sb-mtn{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ink)}
#sb .sb-mtn b{color:var(--ink);font-weight:700}
#sb .sb-sub{font-size:11.5px;color:var(--sub);margin-top:2px}
#sb .sb-rozet{font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:20px;color:#fff;flex-shrink:0;letter-spacing:.3px}

/* Dün özeti kart */
#sb .sb-dun{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
#sb .sb-dun .kutu{background:var(--card2);border-radius:12px;padding:12px;text-align:center}
#sb .sb-dun .kutu .lbl{font-size:11px;color:var(--sub);font-weight:600;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px}
#sb .sb-dun .kutu .val{font-size:19px;font-weight:900;color:var(--ink);letter-spacing:-.3px;line-height:1}

/* Öneriler bloğu */
#sb .sb-oneri{display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-top:1px solid var(--line)}
#sb .sb-oneri:first-of-type{border-top:none}
#sb .sb-on-ikon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#sb .sb-on-ikon .material-icons-outlined{font-size:20px;color:#fff}
#sb .sb-on-ic{flex:1;min-width:0}
#sb .sb-on-mtn{font-size:13.5px;color:var(--ink);line-height:1.4;margin-bottom:7px}
#sb .sb-on-btn{display:inline-flex;align-items:center;gap:5px;background:var(--mor1);color:#fff;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;transition:opacity .14s}
#sb .sb-on-btn:hover{opacity:.88}
#sb .sb-on-btn .material-icons-outlined{font-size:14px}

/* Bütçe barı */
#sb .sb-butce-bar{height:9px;border-radius:5px;background:var(--card2);overflow:hidden;margin-top:8px}
#sb .sb-butce-bar div{height:100%;border-radius:5px;transition:width .7s cubic-bezier(.22,.68,0,1.2)}

/* Boş durum */
#sb .sb-bos{text-align:center;padding:16px;color:var(--sub);font-size:12.5px}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="sb">
  @php
    $saat = (int) now()->format('H');
    $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
    $adK = $ad ? trim(explode(' ', trim($ad))[0]) : 'Satın Almacı';
    $tl = fn($v) => number_format((float) round((float) $v), 0, ',', '.') . '₺';
    $butceC = $butceYuzde === null ? 'var(--sub)' : ($butceYuzde > 100 ? 'var(--kirmizi)' : ($butceYuzde >= 85 ? 'var(--amber)' : 'var(--yesil)'));
  @endphp

  {{-- HERO: selam + özet rakamlar --}}
  <div class="sb-hero">
    <div class="sb-orb"><span class="material-icons-outlined">wb_sunny</span></div>
    <div style="flex:1;min-width:0">
      <div class="selam">{{ $selam }}, {{ $adK }}! ☕</div>
      <div class="alt">Bugünkü satın alma gündemi — {{ now()->format('d.m.Y') }}</div>
      <div class="ozet-sat">
        @if ($kritikSayi > 0)<span class="oz"><span class="material-icons-outlined">warning_amber</span>{{ $kritikSayi }} kritik</span>@endif
        @if ($yeniTalep > 0)<span class="oz"><span class="material-icons-outlined">assignment</span>{{ $yeniTalep }} yeni talep</span>@endif
        @if ($teklifBekleyen > 0)<span class="oz"><span class="material-icons-outlined">mark_email_read</span>{{ $teklifBekleyen }} teklif</span>@endif
        @if ($gecikenSip > 0)<span class="oz"><span class="material-icons-outlined">local_shipping</span>{{ $gecikenSip }} geciken</span>@endif
        @if ($acikSip > 0)<span class="oz"><span class="material-icons-outlined">shopping_cart_checkout</span>{{ $acikSip }} açık sipariş</span>@endif
      </div>
    </div>
  </div>

  {{-- 1. ÖNERİLER (en üst - aksiyon odaklı) --}}
  <div class="sb-blok" style="margin-bottom:14px">
    <h4><span class="ikon-ct" style="background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)"><span class="material-icons-outlined">auto_awesome</span></span> Önerilen Aksiyonlar</h4>
    @foreach ($oneriler as $o)
      <div class="sb-oneri">
        <div class="sb-on-ikon" style="background:{{ $o['renk'] }}"><span class="material-icons-outlined">{{ $o['ikon'] }}</span></div>
        <div class="sb-on-ic">
          <div class="sb-on-mtn">{{ $o['mtn'] }}</div>
          <a href="{{ $o['link'] }}" class="sb-on-btn" style="background:{{ $o['renk'] }}">{{ $o['buton'] }} <span class="material-icons-outlined">arrow_forward</span></a>
        </div>
      </div>
    @endforeach
  </div>

  <div class="sb-grid">
    {{-- 2. DÜN ÖZETİ --}}
    <div class="sb-blok">
      <h4><span class="ikon-ct" style="background:color-mix(in srgb,#0EA5E9 14%,transparent);color:#0EA5E9"><span class="material-icons-outlined">history</span></span> Dün ({{ \Carbon\Carbon::parse($dun)->format('d.m') }})</h4>
      <div class="sb-dun">
        <div class="kutu"><div class="lbl">Fatura</div><div class="val">{{ $dunGelenFatura }}</div></div>
        <div class="kutu"><div class="lbl">Mal Kabul</div><div class="val">{{ $dunMalKabul }}</div></div>
        <div class="kutu"><div class="lbl">Harcama</div><div class="val">{{ $tl($dunHarcama) }}</div></div>
      </div>
    </div>

    {{-- 3. BU AY BÜTÇE --}}
    <div class="sb-blok">
      <h4><span class="ikon-ct" style="background:color-mix(in srgb,{{ $butceC }} 14%,transparent);color:{{ $butceC }}"><span class="material-icons-outlined">savings</span></span> Bu Ay Bütçe</h4>
      @if ($ayButce > 0)
        <div style="display:flex;justify-content:space-between;align-items:baseline">
          <span style="font-size:19px;font-weight:900;color:var(--ink);letter-spacing:-.3px">{{ $tl($ayHarcama) }}</span>
          <span style="font-size:12px;color:var(--sub);font-weight:600">/ {{ $tl($ayButce) }}</span>
        </div>
        <div class="sb-butce-bar"><div style="width:{{ min(100,$butceYuzde) }}%;background:{{ $butceC }}"></div></div>
        <div style="font-size:11.5px;color:{{ $butceC }};font-weight:700;margin-top:5px">%{{ $butceYuzde }} kullanıldı @if($butceYuzde > 100) · AŞIM!@endif</div>
      @else
        <div style="font-size:19px;font-weight:900;color:var(--ink);letter-spacing:-.3px">{{ $tl($ayHarcama) }}</div>
        <div style="font-size:11.5px;color:var(--sub);margin-top:5px">Bütçe tanımlı değil — <a href="/satinalma-butce" style="color:var(--mor1);font-weight:700">tanımla</a></div>
      @endif
    </div>
  </div>

  <div class="sb-grid">
    {{-- 4. KRİTİK STOKLAR --}}
    <div class="sb-blok">
      <h4><span class="ikon-ct" style="background:color-mix(in srgb,#F43F5E 14%,transparent);color:#F43F5E"><span class="material-icons-outlined">warning_amber</span></span> Kritik Stoklar <span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">{{ $kritikSayi }} malzeme</span></h4>
      @forelse (array_slice($kritikStoklar, 0, 6) as $k)
        <div class="sb-sat">
          <span class="sb-dot" style="background:#F43F5E"></span>
          <div class="sb-mtn"><b>{{ $k['ad'] }}</b> — mevcut {{ $k['mevcut'] }} {{ $k['birim'] }}</div>
          <span class="sb-rozet" style="background:#F43F5E">öner {{ $k['oneri'] }}</span>
        </div>
      @empty
        <div class="sb-bos"><span class="material-icons-outlined" style="color:#10B981;font-size:16px;vertical-align:-4px">check_circle</span> Kritik stok yok — iyi.</div>
      @endforelse
      @if ($kritikSayi > 6)<div style="text-align:right;margin-top:8px"><a href="/siparis-onerisi" style="color:var(--mor1);font-size:12px;font-weight:700;text-decoration:none">Tümünü gör ({{ $kritikSayi }}) →</a></div>@endif
    </div>

    {{-- 5. FİYAT HAREKETLERİ --}}
    <div class="sb-blok">
      <h4><span class="ikon-ct" style="background:color-mix(in srgb,#EC4899 14%,transparent);color:#EC4899"><span class="material-icons-outlined">trending_up</span></span> Fiyat Hareketleri <span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">30 gün</span></h4>
      @forelse ($fiyatHareketleri as $f)
        @php $rnk = $f->uyari === 'kirmizi' ? '#F43F5E' : '#F59E0B'; $yz = (float) $f->fiyat_farki_yuzde; @endphp
        <div class="sb-sat">
          <span class="material-icons-outlined" style="color:{{ $rnk }};font-size:17px;flex-shrink:0">{{ $yz > 0 ? 'north_east' : 'south_east' }}</span>
          <div class="sb-mtn"><b>{{ $f->ad }}</b> <span style="color:var(--sub);font-size:11.5px">· {{ number_format((float) $f->birim_fiyat, 2, ',', '.') }}₺</span></div>
          <span class="sb-rozet" style="background:{{ $rnk }}">{{ $yz > 0 ? '+' : '' }}{{ number_format($yz, 1, ',', '.') }}%</span>
        </div>
      @empty
        <div class="sb-bos"><span class="material-icons-outlined" style="color:#10B981;font-size:16px;vertical-align:-4px">check_circle</span> Fiyat uyarısı yok — sakin.</div>
      @endforelse
    </div>
  </div>

  {{-- 6. YAKLAŞAN TESLİMLER --}}
  <div class="sb-blok">
    <h4><span class="ikon-ct" style="background:color-mix(in srgb,#4F46E5 14%,transparent);color:#4F46E5"><span class="material-icons-outlined">local_shipping</span></span> Yaklaşan Teslimler <span style="margin-left:auto;font-size:11px;color:var(--sub);font-weight:600">7 gün</span></h4>
    @forelse ($yaklasanTeslimler as $s)
      @php
        $bt = \Carbon\Carbon::parse($s->beklenen_teslim)->startOfDay();
        $bg = (int) today()->startOfDay()->diffInDays($bt, false);
        if ($bg < 0)      { $dm = abs($bg) . ' gün geciken'; $dc = '#F43F5E'; }
        elseif ($bg === 0){ $dm = 'Bugün'; $dc = '#F59E0B'; }
        elseif ($bg === 1){ $dm = 'Yarın'; $dc = '#F59E0B'; }
        else              { $dm = $bg . ' gün sonra'; $dc = '#4F46E5'; }
      @endphp
      <div class="sb-sat">
        <span class="material-icons-outlined" style="color:var(--sub);font-size:17px;flex-shrink:0">inventory_2</span>
        <div class="sb-mtn"><b>{{ $s->tedarikci ?: 'Tedarikçi —' }}</b> <span style="color:var(--sub);font-size:11.5px">· {{ $s->siparis_no ?: '#'.$s->id }} · {{ $tl($s->genel_toplam) }}</span></div>
        <span class="sb-rozet" style="background:{{ $dc }}">{{ $dm }}</span>
      </div>
    @empty
      <div class="sb-bos">Önümüzdeki 7 günde teslim yok.</div>
    @endforelse
  </div>

</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', 'Satınalma Brifingi')
@section('title', 'Satınalma Brifingi')

@push('head')
<style>
  #sb { max-width: 920px; }
  .sb-orb{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(124,58,237,.35);flex:none}
  .sb-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .sb-blok h4{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 10px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .sb-anlat{font-size:15.5px;line-height:1.65;color:var(--ink)}
  .sb-sat{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line);font-size:13.5px}
  .sb-sat:last-child{border-bottom:0}
  .sb-dot{width:9px;height:9px;border-radius:50%;flex:none}
  .sb-mini{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border-radius:20px;padding:5px 12px;font-size:12.5px;color:var(--sub2);margin:3px 6px 3px 0}
  .sb-link{color:var(--mavi);font-size:12px;text-decoration:none;white-space:nowrap}
  .sb-vd{font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff;letter-spacing:.4px}
  .sb-yorum{font-size:14px;line-height:1.62;color:var(--ink);margin:2px 0 8px}
  .sb-oz{display:flex;gap:8px;align-items:flex-start;font-size:13px;line-height:1.5;padding:9px 11px;border-radius:10px;margin-top:7px}
  .sb-ai{background:linear-gradient(135deg,rgba(124,58,237,.10),rgba(79,70,229,.10));border:1px solid rgba(124,58,237,.25)}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="sb">
  @php
    $saat = (int) now()->format('H');
    $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
    $adK = $ad ? trim(explode(' ', trim($ad))[0]) : null;
    $tl = fn($v) => number_format((float) round((float) $v), 0, ',', '.') . '₺';
    $vd = ['iyi'=>['İYİ','var(--yesil)'],'notr'=>['NÖTR','var(--sub)'],'dikkat'=>['DİKKAT','var(--amber)'],'risk'=>['RİSK','var(--kirmizi)']];

    // Analiz blokları: her biri (ikon, başlık, değer, yorum, risk, öneri, link, lbl, durum)
    $analiz = [];

    // Stok Durumu
    $kritikAdlari = array_slice(array_map(fn($k) => $k['ad'], $kritikStoklar), 0, 3);
    if ($kritikSayi >= 5) {
        $analiz[] = ['ikon'=>'inventory_2','baslik'=>'Stok Durumu','deger'=>"$kritikSayi malzeme kritik",
            'yorum'=>"Deponda **$kritikSayi malzeme** kritik eşiğin altında" . (count($kritikAdlari) ? " (örn. ".implode(', ', $kritikAdlari).")" : "") . ". Mutfak gün içinde bu kalemlerden birini isteyince üretim durabilir.",
            'risk'=>"Üretim duruşu — menüde eksik ürün gözükür, müşteri kaybı",
            'oneri'=>"Sipariş Önerisi'nden kritikleri tek tıkla tedarikçiye RFQ olarak yollayabilirsin.",
            'link'=>'/siparis-onerisi','lbl'=>'Sipariş Öner','durum'=>'risk'];
    } elseif ($kritikSayi > 0) {
        $analiz[] = ['ikon'=>'inventory_2','baslik'=>'Stok Durumu','deger'=>"$kritikSayi kritik",
            'yorum'=>"**$kritikSayi malzeme** kritik seviyede" . (count($kritikAdlari) ? " ({$kritikAdlari[0]}" . (count($kritikAdlari) > 1 ? ", +" . (count($kritikAdlari)-1) : "") . ")" : "") . ". Henüz üretim riski oluşmadı ama yarına kalmasın.",
            'risk'=>null,
            'oneri'=>"Sipariş önerisi ekranından otomatik hesaplanan miktarları gözden geçir.",
            'link'=>'/siparis-onerisi','lbl'=>'Sipariş Öner','durum'=>'dikkat'];
    } else {
        $analiz[] = ['ikon'=>'inventory_2','baslik'=>'Stok Durumu','deger'=>'Yeterli',
            'yorum'=>"Kritik seviyede malzeme yok. Üretim rahat.",
            'risk'=>null,'oneri'=>"Par-seviye (hedef_stok) kontrolünü sürdür.",
            'link'=>'/stok','lbl'=>'Stok','durum'=>'iyi'];
    }

    // Talep Akışı
    if ($yeniTalep + $onaydaTalep > 0) {
        $durumT = $yeniTalep >= 5 ? 'dikkat' : 'notr';
        $analiz[] = ['ikon'=>'assignment','baslik'=>'Talep Akışı','deger'=>"$yeniTalep yeni · $onaydaTalep onayda",
            'yorum'=>($yeniTalep > 0 ? "Departmanlardan **$yeniTalep yeni talep** geldi — fiyat araştırılmayı bekliyor. " : "") . ($onaydaTalep > 0 ? "**$onaydaTalep talep** patron onayında." : ""),
            'risk'=>null,
            'oneri'=>"Yeni talepler için RFQ gönder; onaydakileri patron ekranından takip et.",
            'link'=>'/satinalma-talep','lbl'=>'Talepleri Aç','durum'=>$durumT];
    }

    // Teklif / Fiyat
    $fkKirmizi = $fiyatHareketleri->where('uyari','kirmizi')->count();
    $fkSari = $fiyatHareketleri->where('uyari','sari')->count();
    if ($teklifBekleyen > 0 || $fkKirmizi + $fkSari > 0) {
        $durumF = $fkKirmizi > 0 ? 'risk' : ($teklifBekleyen > 0 ? 'dikkat' : 'notr');
        $yorum = "";
        if ($teklifBekleyen > 0) $yorum .= "**$teklifBekleyen teklif** gelmiş, karşılaştırıp seçmen gerekiyor. ";
        if ($fkKirmizi > 0) $yorum .= "Son 30 günde **$fkKirmizi üründe sert fiyat artışı** sinyali var. ";
        if ($fkSari > 0 && $fkKirmizi == 0) $yorum .= "Son 30 günde $fkSari üründe hafif fiyat değişimi. ";
        $analiz[] = ['ikon'=>'trending_up','baslik'=>'Teklif & Fiyat','deger'=>"$teklifBekleyen teklif · $fkKirmizi kırmızı",
            'yorum'=>trim($yorum),
            'risk'=>$fkKirmizi > 0 ? "Gizli zam — maliyet şişer, kârsız satış riski" : null,
            'oneri'=>"Fiyat radarından alternatif tedarikçi karşılaştırması yap.",
            'link'=>$teklifBekleyen > 0 ? '/gelen-teklifler' : '/fiyat-radari','lbl'=>$teklifBekleyen > 0 ? 'Teklifleri İncele' : 'Fiyat Radarı','durum'=>$durumF];
    }

    // Tedarikçi Takibi
    if ($gecikenSip + $acikSip > 0) {
        $durumT2 = $gecikenSip > 0 ? 'risk' : 'notr';
        $yorum2 = "";
        if ($gecikenSip > 0) $yorum2 .= "**$gecikenSip sipariş** beklenen teslim tarihini geçti. ";
        if ($acikSip > 0) $yorum2 .= "Toplam **$acikSip açık sipariş** var.";
        $analiz[] = ['ikon'=>'local_shipping','baslik'=>'Tedarikçi Takibi','deger'=>"$acikSip açık · $gecikenSip geciken",
            'yorum'=>trim($yorum2),
            'risk'=>$gecikenSip > 0 ? "Geç gelen malzeme → mutfak eksik, menüde iptal" : null,
            'oneri'=>$gecikenSip > 0 ? "Geciken siparişlerin tedarikçilerini bugün WhatsApp'tan ara." : "Teslim takvimi düzenli.",
            'link'=>'/satinalma-siparis','lbl'=>'Siparişleri Aç','durum'=>$durumT2];
    }

    // Bütçe
    if ($ayButce > 0) {
        $durumB = $butceYuzde > 100 ? 'risk' : ($butceYuzde >= 85 ? 'dikkat' : 'iyi');
        $analiz[] = ['ikon'=>'savings','baslik'=>'Aylık Bütçe','deger'=>"%$butceYuzde kullanıldı",
            'yorum'=>"Bu ay bütçenin **%$butceYuzde**'i kullanıldı: " . $tl($ayHarcama) . " / " . $tl($ayButce) . "." . ($butceYuzde > 100 ? " Bütçe **aşıldı**." : ($butceYuzde >= 85 ? " Ay sonuna dikkat et." : "")),
            'risk'=>$butceYuzde > 100 ? "Hedef aşımı — mali kapanışta kârsız" : null,
            'oneri'=>$butceYuzde > 100 ? "Acil olmayan talepleri bir sonraki aya ertele." : "Harcama hızın sürdürülebilir.",
            'link'=>'/satinalma-butce','lbl'=>'Bütçeyi Aç','durum'=>$durumB];
    }

    // AI özet cümlesi (kural motorlu — en önemli 2-3 sinyali birleştir)
    $aiOzetSatirlari = [];
    if ($kritikSayi > 0) $aiOzetSatirlari[] = "**$kritikSayi kritik stok** acil siparişlik";
    if ($gecikenSip > 0) $aiOzetSatirlari[] = "**$gecikenSip sipariş gecikti**";
    if ($yeniTalep > 0) $aiOzetSatirlari[] = "**$yeniTalep yeni talep** inceleme bekliyor";
    if ($teklifBekleyen > 0) $aiOzetSatirlari[] = "**$teklifBekleyen teklif** karar bekliyor";
    if ($butceYuzde !== null && $butceYuzde > 100) $aiOzetSatirlari[] = "bütçe **%$butceYuzde** aşıldı";
    $aiOzet = count($aiOzetSatirlari)
        ? "Bugünün gündemi: " . implode(" · ", array_slice($aiOzetSatirlari, 0, 4)) . "."
        : "Satın alma operasyonu yolunda — acil aksiyon yok, par-seviyeleri sürdür.";

    // **bold** -> <b>bold</b>
    $bold = fn($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
  @endphp

  {{-- ÜST: orb + selam + yenile --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px">
    <div class="sb-orb"><span class="material-icons-outlined" style="color:#fff;font-size:26px">wb_sunny</span></div>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">{{ $selam }}{{ $adK ? ', '.$adK : '' }} ☕</div>
      <div class="rk-sub" style="font-size:12.5px">{{ \Carbon\Carbon::now()->translatedFormat('l, d F') }} · satınalma brifingi · <span style="color:var(--sub)">kural motoru</span></div>
    </div>
    <div style="display:flex;gap:6px">
      <a href="/satinalma-brief" class="rk-btn bos" style="padding:6px 10px" title="Yenile"><span class="material-icons-outlined" style="font-size:16px">refresh</span></a>
    </div>
  </div>

  {{-- GENEL DEĞERLENDİRME (gradient border — patron-ai'deki "AI Değerlendirmesi" eşdeğeri) --}}
  <div class="sb-blok sb-ai" style="margin-top:10px">
    <h4 style="color:var(--mor1)"><span class="material-icons-outlined" style="font-size:17px">auto_awesome</span> Genel Değerlendirme</h4>
    <div style="font-size:14.5px;line-height:1.65;color:var(--ink)">{!! $bold($aiOzet) !!}</div>
  </div>

  {{-- ANALİZ BLOKLARI (her başlık: durum + yorum + risk + öneri) --}}
  @foreach ($analiz as $a)
    @php [$et,$rk] = $vd[$a['durum']] ?? $vd['notr']; @endphp
    <div class="sb-blok">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
        <span class="material-icons-outlined" style="color:{{ $rk }};font-size:22px">{{ $a['ikon'] }}</span>
        <span style="font-weight:800;font-size:15px;color:var(--ink)">{{ $a['baslik'] }}</span>
        <span class="rk-sub" style="font-size:12.5px">· {{ $a['deger'] }}</span>
        <span class="sb-vd" style="background:{{ $rk }};margin-left:auto">{{ $et }}</span>
      </div>
      <div class="sb-yorum">{!! $bold($a['yorum']) !!}</div>
      @if (!empty($a['risk']))
        <div class="sb-oz" style="background:rgba(244,63,94,.10);color:var(--kirmizi)"><span>🔴</span><span><b>Risk:</b> {{ $a['risk'] }}</span></div>
      @endif
      <div class="sb-oz" style="background:var(--card2)">
        <span>✅</span>
        <span style="flex:1;color:var(--sub2)"><b style="color:var(--ink)">Öneri:</b> {!! $bold($a['oneri']) !!}</span>
        @if (!empty($a['link']))<a href="{{ $a['link'] }}" class="rk-btn" style="padding:4px 12px;background:var(--mor1);align-self:center;white-space:nowrap">{{ $a['lbl'] }} →</a>@endif
      </div>
    </div>
  @endforeach

  {{-- YAKLAŞAN TESLİMLER (patron-ai'deki "Bugünkü Gündem" eşdeğeri) --}}
  @if ($yaklasanTeslimler->count())
  <div class="sb-blok">
    <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">local_shipping</span> Yaklaşan Teslimler (7 gün)</h4>
    @foreach ($yaklasanTeslimler as $s)
      @php
        $bt = \Carbon\Carbon::parse($s->beklenen_teslim)->startOfDay();
        $bg = (int) today()->startOfDay()->diffInDays($bt, false);
        if ($bg < 0)      { $dm = abs($bg) . ' gün geciken'; $dc = 'var(--kirmizi)'; }
        elseif ($bg === 0){ $dm = 'Bugün'; $dc = 'var(--amber)'; }
        elseif ($bg === 1){ $dm = 'Yarın'; $dc = 'var(--amber)'; }
        else              { $dm = $bg . ' gün sonra'; $dc = 'var(--mavi)'; }
      @endphp
      <div class="sb-sat" style="align-items:flex-start">
        <span style="font-weight:800;color:{{ $dc }};min-width:94px;font-size:12.5px">{{ $dm }}</span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600">{{ $s->tedarikci ?: 'Tedarikçi —' }}</div>
          <div class="rk-sub" style="font-size:11.5px">{{ $s->siparis_no ?: '#'.$s->id }} · {{ $tl($s->genel_toplam) }}</div>
        </div>
        <a href="/satinalma-siparis" class="sb-link">Aç →</a>
      </div>
    @endforeach
  </div>
  @endif

  {{-- FİYAT HAREKETLERİ (patron-ai'deki "Dünden Devam" eşdeğeri) --}}
  @if ($fiyatHareketleri->count())
  <div class="sb-blok">
    <h4><span class="material-icons-outlined" style="font-size:17px;color:#EC4899">trending_up</span> Son Fiyat Hareketleri (30 gün)</h4>
    @foreach ($fiyatHareketleri as $f)
      @php $rnk = $f->uyari === 'kirmizi' ? 'var(--kirmizi)' : 'var(--amber)'; $yz = (float) $f->fiyat_farki_yuzde; @endphp
      <div class="sb-sat">
        <span class="material-icons-outlined" style="font-size:18px;color:{{ $rnk }}">{{ $yz > 0 ? 'north_east' : 'south_east' }}</span>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600">{{ $f->ad }}</div>
          <div class="rk-sub" style="font-size:11.5px">{{ number_format((float) $f->birim_fiyat, 2, ',', '.') }}₺ · {{ \Carbon\Carbon::parse($f->tarih)->diffForHumans() }}</div>
        </div>
        <span class="sb-vd" style="background:{{ $rnk }}">{{ $yz > 0 ? '+' : '' }}{{ number_format($yz, 1, ',', '.') }}%</span>
      </div>
    @endforeach
  </div>
  @endif

  {{-- RAKAM ŞERİDİ (küçük chip'ler — patron-ai'deki alt şerit eşdeğeri) --}}
  <div class="sb-blok" style="display:flex;flex-wrap:wrap;align-items:center;gap:4px">
    <span class="sb-mini">💰 Bu ay {{ $tl($ayHarcama) }}</span>
    @if ($ayButce > 0)<span class="sb-mini">🎯 Bütçe %{{ $butceYuzde }}</span>@endif
    <span class="sb-mini">📄 Dün {{ $dunGelenFatura }} fatura</span>
    <span class="sb-mini">📦 Dün {{ $dunMalKabul }} mal kabul</span>
    <span class="sb-mini">⚠️ {{ $kritikSayi }} kritik stok</span>
    <span class="sb-mini">🚚 {{ $gecikenSip }} geciken</span>
    <a href="/satinalma-ozet" class="sb-link" style="margin-left:auto;font-size:12.5px">Detaylı özet →</a>
  </div>

</div>
</div>
@endsection

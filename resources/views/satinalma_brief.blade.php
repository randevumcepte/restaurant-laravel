@extends('layout.app')
@section('rk_ikon', 'wb_sunny')
@section('baslik', 'Satınalma Brifingi')
@section('title', 'Satınalma Brifingi')

@push('head')
<style>
  #sb { max-width: 1100px; margin: 0 auto; }

  /* ─── HERO: selam + özet rozetler ─── */
  .sb-hero { background: linear-gradient(135deg, var(--mor1), var(--mavi)); border-radius: 24px; padding: 24px 28px; margin-bottom: 18px; color: #fff; box-shadow: 0 12px 36px rgba(124, 58, 237, .32); position: relative; overflow: hidden; }
  .sb-hero::before { content: ''; position: absolute; top: -80px; right: -60px; width: 240px; height: 240px; background: radial-gradient(circle, rgba(255, 255, 255, .18), transparent 70%); border-radius: 50%; pointer-events: none; }
  .sb-hero::after { content: ''; position: absolute; bottom: -60px; left: 20%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255, 255, 255, .1), transparent 70%); border-radius: 50%; pointer-events: none; }
  .sb-hero-ic { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }
  .sb-orb { width: 56px; height: 56px; border-radius: 50%; background: rgba(255, 255, 255, .22); backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 0 0 0 rgba(255, 255, 255, .35); animation: sbPulse 2.4s ease-out infinite; }
  .sb-orb .material-icons-outlined { color: #fff; font-size: 30px; }
  @keyframes sbPulse { 0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, .4); } 70% { box-shadow: 0 0 0 14px rgba(255, 255, 255, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); } }
  .sb-selam { font-size: 24px; font-weight: 900; line-height: 1.1; letter-spacing: -.5px; margin-bottom: 4px; }
  .sb-alt { font-size: 13px; color: rgba(255, 255, 255, .85); font-weight: 600; }
  .sb-rozetler { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; position: relative; z-index: 1; }
  .sb-roz { display: inline-flex; align-items: center; gap: 6px; background: rgba(255, 255, 255, .22); padding: 7px 13px; border-radius: 14px; font-size: 12.5px; font-weight: 700; backdrop-filter: blur(6px); border: 1px solid rgba(255, 255, 255, .15); }
  .sb-roz .material-icons-outlined { font-size: 15px; }

  /* ─── GENEL DEĞERLENDİRME ─── */
  .sb-genel { background: linear-gradient(135deg, rgba(124, 58, 237, .08), rgba(79, 70, 229, .04)); border: 1px solid rgba(124, 58, 237, .22); border-radius: 18px; padding: 16px 20px; margin-bottom: 18px; }
  .sb-genel h4 { font-size: 12px; font-weight: 800; color: var(--mor1); letter-spacing: .5px; text-transform: uppercase; margin: 0 0 8px; display: flex; align-items: center; gap: 7px; }
  .sb-genel .metin { font-size: 14.5px; line-height: 1.65; color: var(--ink); }

  /* ─── İKİ KOLONLU GRID: Dün Yapılanlar + Bugün Yapılacaklar ─── */
  .sb-ikili { display: grid; grid-template-columns: 1fr 1.4fr; gap: 16px; margin-bottom: 18px; }
  @media (max-width: 900px) { .sb-ikili { grid-template-columns: 1fr; } }

  /* ─── Dün Yapılanlar (sol) — minimal checklist ─── */
  .sb-dun-blok { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; box-shadow: var(--golge); }
  .sb-dun-blok h3 { font-size: 13px; font-weight: 800; color: var(--sub2); letter-spacing: .5px; text-transform: uppercase; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
  .sb-dun-blok h3 .ikon-ct { width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(135deg, #10B981, #059669); color: #fff; display: flex; align-items: center; justify-content: center; }
  .sb-dun-blok h3 .ikon-ct .material-icons-outlined { font-size: 17px; }
  .sb-dun-sat { display: flex; align-items: center; gap: 11px; padding: 10px 0; border-top: 1px dashed var(--line); font-size: 13.5px; }
  .sb-dun-sat:first-of-type { border-top: 0; padding-top: 0; }
  .sb-dun-sat .check { width: 24px; height: 24px; border-radius: 50%; background: color-mix(in srgb, #10B981 15%, transparent); color: #10B981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .sb-dun-sat .check .material-icons-outlined { font-size: 15px; }
  .sb-dun-sat .mtn { flex: 1; min-width: 0; color: var(--ink); font-weight: 600; }
  .sb-dun-sat .mtn .ozel { color: var(--sub); font-weight: 500; }
  .sb-dun-sat a { color: var(--mor1); font-size: 11.5px; font-weight: 700; text-decoration: none; white-space: nowrap; }
  .sb-dun-bos { text-align: center; padding: 24px 10px; color: var(--sub); font-size: 13px; }

  /* ─── Bugün Yapılacaklar (sağ) — her satır Yap/Vazgeç butonu ─── */
  .sb-bugun-blok { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 18px 20px; box-shadow: var(--golge); }
  .sb-bugun-blok h3 { font-size: 13px; font-weight: 800; color: var(--sub2); letter-spacing: .5px; text-transform: uppercase; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
  .sb-bugun-blok h3 .ikon-ct { width: 30px; height: 30px; border-radius: 10px; background: linear-gradient(135deg, #7C3AED, #4F46E5); color: #fff; display: flex; align-items: center; justify-content: center; }
  .sb-bugun-blok h3 .ikon-ct .material-icons-outlined { font-size: 17px; }
  .sb-bugun-blok h3 .sayi { margin-left: auto; background: color-mix(in srgb, var(--mor1) 15%, transparent); color: var(--mor1); padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 800; text-transform: none; letter-spacing: 0; }

  .sb-yap-sat { position: relative; background: var(--card2); border: 1px solid var(--line); border-radius: 14px; padding: 14px 16px; margin-bottom: 10px; transition: box-shadow .14s, transform .14s, border-color .14s; }
  .sb-yap-sat:last-child { margin-bottom: 0; }
  .sb-yap-sat:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, .07); transform: translateY(-1px); }
  .sb-yap-sat.oncelik-yuksek { border-left: 3px solid #F43F5E; }
  .sb-yap-sat.oncelik-orta { border-left: 3px solid #F59E0B; }
  .sb-yap-sat.oncelik-dusuk { border-left: 3px solid #4F46E5; }
  .sb-yap-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
  .sb-yap-ikon { width: 36px; height: 36px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #fff; }
  .sb-yap-ikon .material-icons-outlined { font-size: 20px; }
  .sb-yap-ic { flex: 1; min-width: 0; }
  .sb-yap-bas { font-size: 14px; font-weight: 700; color: var(--ink); line-height: 1.35; margin-bottom: 3px; }
  .sb-yap-aci { font-size: 12px; color: var(--sub); line-height: 1.45; }
  .sb-yap-onc-roz { font-size: 9.5px; font-weight: 900; padding: 3px 8px; border-radius: 20px; letter-spacing: .4px; flex-shrink: 0; color: #fff; }
  .sb-yap-onc-roz.yuksek { background: #F43F5E; }
  .sb-yap-onc-roz.orta { background: #F59E0B; }
  .sb-yap-onc-roz.dusuk { background: #4F46E5; }

  .sb-yap-btn-grup { display: flex; gap: 8px; justify-content: flex-end; align-items: center; margin-top: 10px; }
  .sb-yap-btn-grup .kilit { font-size: 10.5px; color: var(--sub2); display: inline-flex; align-items: center; gap: 4px; margin-right: auto; }
  .sb-yap-btn-grup .kilit .material-icons-outlined { font-size: 12px; color: #10B981; }
  .sb-btn-vaz { background: var(--card); border: 1px solid var(--line); color: var(--sub2); padding: 8px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 700; cursor: pointer; transition: all .13s; }
  .sb-btn-vaz:hover { color: var(--kirmizi); border-color: var(--kirmizi); }
  .sb-btn-yap { background: linear-gradient(135deg, #10B981, #059669); color: #fff; border: 0; padding: 9px 18px; border-radius: 9px; font-size: 12.5px; font-weight: 800; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 3px 10px rgba(16, 185, 129, .3); transition: transform .13s, box-shadow .13s; }
  .sb-btn-yap:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(16, 185, 129, .42); }
  .sb-btn-yap .material-icons-outlined { font-size: 15px; }
  .sb-btn-yap:disabled { opacity: .6; cursor: wait; }
  .sb-btn-link { background: color-mix(in srgb, var(--mavi) 13%, transparent); color: var(--mavi); border: 0; padding: 9px 18px; border-radius: 9px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; transition: all .13s; }
  .sb-btn-link:hover { background: var(--mavi); color: #fff; }
  .sb-btn-link .material-icons-outlined { font-size: 15px; }

  .sb-yap-sonuc { display: none; font-size: 12.5px; line-height: 1.5; padding: 10px 12px; border-radius: 9px; margin-top: 10px; }
  .sb-yap-sonuc.ok { background: color-mix(in srgb, #10B981 10%, transparent); color: #047857; border-left: 3px solid #10B981; display: block; }
  .sb-yap-sonuc.hata { background: color-mix(in srgb, var(--kirmizi) 10%, transparent); color: var(--kirmizi); border-left: 3px solid var(--kirmizi); display: block; }
  .sb-yap-sonuc.vz { background: var(--card2); color: var(--sub2); border-left: 3px solid var(--sub); display: block; }

  .sb-bugun-bos { text-align: center; padding: 28px 10px; color: var(--sub); font-size: 13.5px; }
  .sb-bugun-bos .material-icons-outlined { font-size: 44px; color: #10B981; display: block; margin: 0 auto 8px; opacity: .7; }

  /* ─── Alt bloklar: Yaklaşan Teslimler + Fiyat Hareketleri ─── */
  .sb-blok { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 16px 18px; box-shadow: var(--golge); margin-bottom: 14px; }
  .sb-blok h4 { font-size: 12.5px; font-weight: 800; color: var(--sub2); letter-spacing: .3px; margin: 0 0 10px; display: flex; align-items: center; gap: 7px; text-transform: uppercase; }
  .sb-blok h4 .sage { margin-left: auto; font-size: 11px; color: var(--sub); font-weight: 600; text-transform: none; letter-spacing: 0; }
  .sb-sat { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-top: 1px solid var(--line); font-size: 13.5px; }
  .sb-sat:first-of-type { border-top: 0; }
  .sb-sat .ad { flex: 1; min-width: 0; color: var(--ink); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .sb-sat .alt { font-size: 11.5px; color: var(--sub); margin-top: 2px; }
  .sb-sat .roz-k { font-size: 10.5px; font-weight: 800; padding: 2px 9px; border-radius: 20px; color: #fff; flex-shrink: 0; letter-spacing: .3px; }

  /* ─── Rakam şeridi (altta) ─── */
  .sb-mini-bar { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 11px 14px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; box-shadow: var(--golge); }
  .sb-mini-bar .chip { display: inline-flex; align-items: center; gap: 5px; background: var(--card2); border-radius: 20px; padding: 5px 11px; font-size: 12px; color: var(--sub2); font-weight: 600; }
  .sb-mini-bar .link { margin-left: auto; font-size: 12.5px; font-weight: 700; color: var(--mor1); text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }

  .sb-genel .boldv { font-weight: 800; color: var(--mor1); }
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
  $butceC = $butceYuzde === null ? 'var(--sub)' : ($butceYuzde > 100 ? '#F43F5E' : ($butceYuzde >= 85 ? '#F59E0B' : '#10B981'));

  // Genel değerlendirme cümlesi (kural tabanlı)
  $degerleme = [];
  if ($kritikSayi > 0)   $degerleme[] = "<span class='boldv'>$kritikSayi kritik stok</span> acil siparişlik";
  if ($gecikenSip > 0)   $degerleme[] = "<span class='boldv'>$gecikenSip sipariş gecikti</span>";
  if ($yeniTalep > 0)    $degerleme[] = "<span class='boldv'>$yeniTalep yeni talep</span> inceleme bekliyor";
  if ($teklifBekleyen > 0) $degerleme[] = "<span class='boldv'>$teklifBekleyen teklif</span> karar bekliyor";
  if ($butceYuzde !== null && $butceYuzde > 100) $degerleme[] = "bütçe <span class='boldv'>%{$butceYuzde} aşıldı</span>";
  $aiOzet = count($degerleme)
    ? "Bugünün gündemi: " . implode(" · ", array_slice($degerleme, 0, 4)) . "."
    : "Satın alma operasyonu yolunda — acil aksiyon yok. Par-seviyelerini sürdür.";
@endphp

{{-- HERO --}}
<div class="sb-hero">
  <div class="sb-hero-ic">
    <div class="sb-orb"><span class="material-icons-outlined">wb_sunny</span></div>
    <div style="flex:1;min-width:0">
      <div class="sb-selam">{{ $selam }}, {{ $adK }}! ☕</div>
      <div class="sb-alt">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }} · Satınalma Sabah Brifingi</div>
    </div>
  </div>
  <div class="sb-rozetler">
    <span class="sb-roz"><span class="material-icons-outlined">task_alt</span>{{ count($dunYapilanlar) }} dün yapıldı</span>
    <span class="sb-roz"><span class="material-icons-outlined">pending_actions</span>{{ count($bugunYapilacak) }} bugün yapılacak</span>
    @if ($kritikSayi > 0)<span class="sb-roz" style="background:rgba(244,63,94,.35)"><span class="material-icons-outlined">warning_amber</span>{{ $kritikSayi }} kritik</span>@endif
    @if ($gecikenSip > 0)<span class="sb-roz" style="background:rgba(234,88,12,.35)"><span class="material-icons-outlined">local_shipping</span>{{ $gecikenSip }} geciken</span>@endif
  </div>
</div>

{{-- GENEL DEĞERLENDİRME --}}
<div class="sb-genel">
  <h4><span class="material-icons-outlined" style="font-size:16px">auto_awesome</span> AI Değerlendirmesi</h4>
  <div class="metin">{!! $aiOzet !!}</div>
</div>

{{-- İKİ KOLONLU: Dün Yapılanlar + Bugün Yapılacaklar --}}
<div class="sb-ikili">

  {{-- DÜN YAPILANLAR --}}
  <div class="sb-dun-blok">
    <h3><span class="ikon-ct"><span class="material-icons-outlined">done_all</span></span> Dün Yapılanlar</h3>
    @forelse ($dunYapilanlar as $y)
      <div class="sb-dun-sat">
        <div class="check"><span class="material-icons-outlined">check</span></div>
        <div class="mtn">{{ $y['mtn'] }}</div>
        @if (!empty($y['link']))<a href="{{ $y['link'] }}">Aç →</a>@endif
      </div>
    @empty
      <div class="sb-dun-bos">Dün hareket yok.<br><span style="font-size:11.5px">Belki rapor günüdür?</span></div>
    @endforelse
  </div>

  {{-- BUGÜN YAPILACAKLAR --}}
  <div class="sb-bugun-blok">
    <h3><span class="ikon-ct"><span class="material-icons-outlined">pending_actions</span></span> Bugün Yapılacaklar <span class="sayi">{{ count($bugunYapilacak) }}</span></h3>
    @forelse ($bugunYapilacak as $i => $y)
      <div class="sb-yap-sat oncelik-{{ $y['oncelik'] }}" data-ix="{{ $i }}">
        <div class="sb-yap-head">
          <div class="sb-yap-ikon" style="background:{{ $y['renk'] }}">
            <span class="material-icons-outlined">{{ $y['ikon'] }}</span>
          </div>
          <div class="sb-yap-ic">
            <div class="sb-yap-bas">{{ $y['baslik'] }}</div>
            @if (!empty($y['aciklama']))<div class="sb-yap-aci">{{ $y['aciklama'] }}</div>@endif
          </div>
          <span class="sb-yap-onc-roz {{ $y['oncelik'] }}">{{ ['yuksek' => 'ACİL', 'orta' => 'ORTA', 'dusuk' => 'DÜŞÜK'][$y['oncelik']] }}</span>
        </div>
        <div class="sb-yap-btn-grup">
          @if ($y['tip'] === 'eylem')
            <span class="kilit"><span class="material-icons-outlined">lock</span> Onay IP ile imzalı log'a yazılacak</span>
            <button class="sb-btn-yap" onclick="briefYap(this, '{{ $y['intent'] }}', {{ json_encode($y['params'] ?? []) }})">
              <span class="material-icons-outlined">check</span> İşlemi Yap
            </button>
          @else
            <a href="{{ $y['url'] }}" class="sb-btn-link"><span class="material-icons-outlined">arrow_forward</span> Aç</a>
          @endif
        </div>
        <div class="sb-yap-sonuc"></div>
      </div>
    @empty
      <div class="sb-bugun-bos">
        <span class="material-icons-outlined">check_circle</span>
        <div>Her şey yolunda — bugün için acil aksiyon yok.</div>
        <div style="font-size:11.5px;margin-top:4px">Par-seviye kontrollerini ve fiyat takibini sürdür.</div>
      </div>
    @endforelse
  </div>

</div>

{{-- YAKLAŞAN TESLİMLER --}}
@if ($yaklasanTeslimler->count())
<div class="sb-blok">
  <h4><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">local_shipping</span> Yaklaşan Teslimler <span class="sage">önümüzdeki 7 gün</span></h4>
  @foreach ($yaklasanTeslimler as $s)
    @php
      $bt = \Carbon\Carbon::parse($s->beklenen_teslim)->startOfDay();
      $bg = (int) today()->startOfDay()->diffInDays($bt, false);
      if ($bg < 0)       { $dm = abs($bg) . ' gün geciken'; $dc = '#F43F5E'; }
      elseif ($bg === 0) { $dm = 'Bugün'; $dc = '#F59E0B'; }
      elseif ($bg === 1) { $dm = 'Yarın'; $dc = '#F59E0B'; }
      else               { $dm = $bg . ' gün sonra'; $dc = '#4F46E5'; }
    @endphp
    <div class="sb-sat">
      <span class="material-icons-outlined" style="font-size:18px;color:var(--sub);flex-shrink:0">inventory_2</span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600">{{ $s->tedarikci ?: 'Tedarikçi —' }}</div>
        <div style="font-size:11.5px;color:var(--sub)">{{ $s->siparis_no ?: '#'.$s->id }} · {{ $tl($s->genel_toplam) }}</div>
      </div>
      <span class="roz-k" style="background:{{ $dc }}">{{ $dm }}</span>
    </div>
  @endforeach
</div>
@endif

{{-- FİYAT HAREKETLERİ --}}
@if ($fiyatHareketleri->count())
<div class="sb-blok">
  <h4><span class="material-icons-outlined" style="font-size:17px;color:#EC4899">trending_up</span> Son Fiyat Hareketleri <span class="sage">30 gün</span></h4>
  @foreach ($fiyatHareketleri as $f)
    @php $rnk = $f->uyari === 'kirmizi' ? '#F43F5E' : '#F59E0B'; $yz = (float) $f->fiyat_farki_yuzde; @endphp
    <div class="sb-sat">
      <span class="material-icons-outlined" style="font-size:18px;color:{{ $rnk }}">{{ $yz > 0 ? 'north_east' : 'south_east' }}</span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600">{{ $f->ad }}</div>
        <div style="font-size:11.5px;color:var(--sub)">{{ number_format((float) $f->birim_fiyat, 2, ',', '.') }}₺ · {{ \Carbon\Carbon::parse($f->tarih)->diffForHumans() }}</div>
      </div>
      <span class="roz-k" style="background:{{ $rnk }}">{{ $yz > 0 ? '+' : '' }}{{ number_format($yz, 1, ',', '.') }}%</span>
    </div>
  @endforeach
</div>
@endif

{{-- RAKAM ŞERİDİ --}}
<div class="sb-mini-bar">
  <span class="chip">💰 Bu ay {{ $tl($ayHarcama) }}</span>
  @if ($ayButce > 0)<span class="chip" style="color:{{ $butceC }}">🎯 Bütçe %{{ $butceYuzde }}</span>@endif
  <span class="chip">📄 Dün {{ $dunGelenFatura }} fatura</span>
  <span class="chip">📦 Dün {{ $dunMalKabul }} mal kabul</span>
  <span class="chip">⚠️ {{ $kritikSayi }} kritik</span>
  <span class="chip">🚚 {{ $gecikenSip }} geciken</span>
  <a href="/satinalma-ozet" class="link">Detaylı özet <span class="material-icons-outlined" style="font-size:14px;vertical-align:-3px">arrow_forward</span></a>
</div>

</div>
</div>

@push('scripts')
<script>
async function briefYap(btn, intent, params){
  const row = btn.closest('.sb-yap-sat');
  const son = row.querySelector('.sb-yap-sonuc');
  btn.disabled = true; btn.innerHTML = '<span class="material-icons-outlined">hourglass_bottom</span> Yürütülüyor...';
  try {
    // 1) Eylem onerisi oluştur: brief-özel endpoint intent + params'ı doğrudan alır, log yazar
    const r1 = await fetch('/api/satinalma-brief/eylem-oner', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json'},
      body: JSON.stringify({intent, params})
    });
    const j1 = await r1.json();
    if (!j1 || !j1.ok || !j1.log_id) {
      son.className = 'sb-yap-sonuc hata';
      son.textContent = 'Öneri oluşturulamadı: ' + (j1.hata || 'bilinmeyen hata');
      btn.disabled = false; btn.innerHTML = '<span class="material-icons-outlined">check</span> İşlemi Yap';
      return;
    }
    // 2) Onayla (ortak endpoint): dispatch satınalma modülüne gider, yürütür, zincire yazar
    const r2 = await fetch('/api/ai-eylem/onayla', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': window.csrf, 'Accept': 'application/json'},
      body: JSON.stringify({log_id: j1.log_id})
    });
    const j2 = await r2.json();
    const s = (j2 && j2.sonuc) || {};
    son.className = 'sb-yap-sonuc ' + (s.ok ? 'ok' : 'hata');
    son.textContent = s.mesaj || (s.ok ? 'Yürütüldü.' : 'Hata oluştu.');
    btn.innerHTML = s.ok ? '<span class="material-icons-outlined">check_circle</span> Tamamlandı' : '<span class="material-icons-outlined">check</span> İşlemi Yap';
    if (s.ok) {
      row.querySelector('.kilit').style.display = 'none';
    } else {
      btn.disabled = false;
    }
  } catch (e) {
    son.className = 'sb-yap-sonuc hata'; son.textContent = 'Bağlantı hatası.';
    btn.disabled = false; btn.innerHTML = '<span class="material-icons-outlined">check</span> İşlemi Yap';
  }
}
</script>
@endpush

@endsection

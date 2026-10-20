@extends('layout.app')
@section('rk_ikon', 'event_available')
@section('baslik', 'Rezervasyonlar')
@section('title', 'Rezervasyonlar')
@section('rk_alt', \Carbon\Carbon::parse($tarih)->isoFormat('D MMMM YYYY'))

@push('head')
<style>
/* ===== REZERVASYONlar — Flutter rezervasyon_screen BİREBİR ===== */

/* Gün pill seçici */
.rez-gun-pill{
  position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
  width:66px;height:74px;border-radius:14px;border:1px solid var(--line);
  background:var(--card);text-decoration:none;flex:0 0 auto;transition:.15s;cursor:pointer
}
.rez-gun-pill.aktif{background:var(--mor1);border-color:var(--mor1)}
.rez-gun-pill:hover:not(.aktif){background:var(--card2)}
.rez-gun-pill .pg-etiket{font-size:12px;font-weight:600;color:var(--sub)}
.rez-gun-pill .pg-gun{font-size:18px;font-weight:800;line-height:1;margin-top:3px;color:var(--ink)}
.rez-gun-pill.aktif .pg-etiket,.rez-gun-pill.aktif .pg-gun{color:#fff}
.rez-gun-pill .pg-badge{
  position:absolute;top:5px;right:5px;min-width:18px;height:18px;padding:0 5px;
  border-radius:20px;font-size:10px;font-weight:700;display:flex;align-items:center;
  justify-content:center;color:#fff;background:var(--mavi)
}
.rez-gun-pill.aktif .pg-badge{background:rgba(255,255,255,.25)}

/* Stat kart (Flutter _pStat) */
.rez-stat{
  padding:14px;border-radius:14px;
}
.rez-stat .rs-ust{display:flex;align-items:center;gap:8px}
.rez-stat .rs-ikon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex:0 0 28px}
.rez-stat .rs-ikon .material-icons-outlined{font-size:16px}
.rez-stat .rs-etiket{font-size:11.5px;font-weight:600;color:var(--sub2);line-height:1.15}
.rez-stat .rs-deger{font-size:25px;font-weight:900;line-height:1;margin-top:10px}

/* Analitik kart kutusu (Flutter _pKartKutu) */
.rez-akart{
  background:var(--card);border:1px solid var(--line);border-radius:16px;
  box-shadow:var(--golge);padding:16px
}
.rez-akart .ak-baslik{display:flex;align-items:center;gap:8px;margin-bottom:14px}
.rez-akart .ak-ikon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex:0 0 28px}
.rez-akart .ak-ikon .material-icons-outlined{font-size:16px}
.rez-akart .ak-ad{font-size:14px;font-weight:700;color:var(--ink);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rez-akart .ak-sag{font-size:12px;font-weight:600;color:var(--sub);margin-left:auto;white-space:nowrap}

/* Durum çubuk + satırlar */
.rez-durum-bar{height:10px;border-radius:6px;display:flex;overflow:hidden;margin-bottom:14px}
.rez-durum-satir{display:flex;align-items:center;gap:8px;padding:5px 0}
.rez-durum-satir .ds-renk{width:8px;height:8px;border-radius:50%;flex:0 0 8px}
.rez-durum-satir .ds-ad{flex:1;font-size:12.5px;color:var(--sub2)}
.rez-durum-satir .ds-yuzde{font-size:12.5px;font-weight:700;min-width:40px;text-align:right}
.rez-durum-satir .ds-adet{font-size:12.5px;font-weight:700;color:var(--ink);min-width:22px;text-align:right}
.rez-toplam-satir{display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid var(--line);margin-top:4px}
.rez-toplam-satir .tad{font-size:13px;font-weight:600;color:var(--sub2)}
.rez-toplam-satir .tv{font-size:16px;font-weight:700;color:var(--ink)}

/* Kaynak ilerleme satırı */
.rez-kaynak-satir{margin-bottom:12px}
.rez-kaynak-satir .ks-ust{display:flex;justify-content:space-between;margin-bottom:5px}
.rez-kaynak-satir .ks-ad{font-size:12.5px;font-weight:600;color:var(--sub2)}
.rez-kaynak-satir .ks-bilgi{font-size:12px;color:var(--sub)}
.rez-kaynak-satir .ks-bar{height:7px;border-radius:5px;background:var(--card2);overflow:hidden}
.rez-kaynak-satir .ks-fill{height:100%;border-radius:5px}

/* Saat çubuk grafik */
.rez-saat-grafik{height:120px;display:flex;align-items:flex-end;gap:3px;margin-bottom:6px}
.rez-saat-grafik .sg-col{flex:1;display:flex;flex-direction:column;justify-content:flex-end}
.rez-saat-grafik .sg-bar{border-radius:4px;min-height:3px;background:var(--card2)}
.rez-saat-bar-aks{display:flex;justify-content:space-between;font-size:10px;color:var(--sub)}

/* Performans ilerleme */
.rez-perf-satir{margin-bottom:16px}
.rez-perf-satir .ps-ust{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.rez-perf-satir .ps-ik .material-icons-outlined{font-size:16px}
.rez-perf-satir .ps-ad{flex:1;font-size:13px;color:var(--sub2)}
.rez-perf-satir .ps-yuzde{font-size:15px;font-weight:700}
.rez-perf-satir .ps-bar{height:8px;border-radius:5px;background:var(--card2);overflow:hidden}
.rez-perf-satir .ps-fill{height:100%;border-radius:5px}

/* Aylık takvim grid */
.rez-aylik-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px}
.rez-aylik-grid .ag-hd{text-align:center;font-size:10px;font-weight:600;color:var(--sub);padding:2px 0}
.rez-aylik-grid .ag-gun{border-radius:8px;aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none}
.rez-aylik-grid .ag-gun.secili{border:2px solid var(--gold)}
.rez-aylik-grid .ag-gun.bos-hucre{pointer-events:none}

/* İki sütunlu analitik kart grubu */
.rez-iki{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start}

/* Rez kart sol çizgili (Flutter _kart mobil) — web'de tablo olduğu için bu sadece dar ekranda */
.rez-kart-sol{
  background:var(--card);border-radius:16px;border-left:4px solid;
  padding:14px 12px 12px 14px;border-right:1px solid var(--line);
  border-top:1px solid var(--line);border-bottom:1px solid var(--line);
  margin-bottom:10px
}

/* Dar ekran grid */
@media(max-width:900px){
  .rez-iki{grid-template-columns:1fr}
  .rez-stat-grid{grid-template-columns:repeat(3,1fr) !important}
}
@media(max-width:640px){
  .rez-stat-grid{grid-template-columns:repeat(2,1fr) !important}
  .rez-tablo-wrap{display:none}
  .rez-kart-list{display:block}
}
@media(min-width:641px){
  .rez-kart-list{display:none}
}
</style>
@endpush

@section('content')
@php
  // ── Temel sabitler ──────────────────────────────────────────────────────────
  $bugun  = \Carbon\Carbon::now()->format('Y-m-d');

  $durumAd   = ['bekliyor'=>'BEKLİYOR','onaylandi'=>'ONAYLI','geldi'=>'GELDİ','iptal'=>'İPTAL','gelmedi'=>'GELMEDİ'];
  $durumRenk = [
    'bekliyor'  => 'var(--amber)',
    'onaylandi' => 'var(--mavi)',
    'geldi'     => 'var(--yesil)',
    'iptal'     => '#64748B',
    'gelmedi'   => 'var(--kirmizi)',
  ];
  $kaynakIkon = ['web'=>'🌐','telefon'=>'📞','qr'=>'📱','walk_in'=>'🚶','walkin'=>'🚶','admin'=>'🖥️'];
  $kaynakAd   = ['web'=>'Web','telefon'=>'Telefon','qr'=>'QR','walk_in'=>'Walk-in','walkin'=>'Walk-in','admin'=>'Admin'];

  // ── Rezervasyon koleksiyonu ───────────────────────────────────────────────
  $rlist = collect($rezervasyonlar ?? []);
  $toplam  = $rlist->count();
  $bekleyen_s = $rlist->where('durum','bekliyor')->count();
  $onaylanan_s = $rlist->where('durum','onaylandi')->count();
  $misafir_s   = (int) $rlist->whereIn('durum',['bekliyor','onaylandi','geldi'])->sum('kisi');

  // Ortalama kişi / Rez.
  $ortKisi = $toplam > 0 ? round($misafir_s / $toplam, 1) : 0;

  // ── Durum dağılımı (Flutter _pDurumKart) ────────────────────────────────
  $durumlar = [
    ['durum'=>'bekliyor',  'ad'=>'Bekliyor',  'adet'=>$rlist->where('durum','bekliyor')->count()],
    ['durum'=>'onaylandi', 'ad'=>'Onaylı',    'adet'=>$rlist->where('durum','onaylandi')->count()],
    ['durum'=>'geldi',     'ad'=>'Geldi',     'adet'=>$rlist->where('durum','geldi')->count()],
    ['durum'=>'gelmedi',   'ad'=>'Gelmedi',   'adet'=>$rlist->where('durum','gelmedi')->count()],
    ['durum'=>'iptal',     'ad'=>'İptal',     'adet'=>$rlist->where('durum','iptal')->count()],
  ];
  foreach ($durumlar as &$dd) {
    $dd['yuzde'] = $toplam > 0 ? round($dd['adet'] / $toplam * 100) : 0;
  } unset($dd);

  // ── Kaynak dağılımı ───────────────────────────────────────────────────────
  $kaynakHam = ['web'=>0,'telefon'=>0,'qr'=>0,'walk_in'=>0,'admin'=>0];
  foreach ($rlist as $rv) {
    $k = strtolower((string)($rv->kaynak ?? 'web'));
    if ($k === 'walkin') $k = 'walk_in';
    if (!isset($kaynakHam[$k])) $kaynakHam[$k] = 0;
    $kaynakHam[$k]++;
  }
  $kaynaklar = [];
  foreach ($kaynakHam as $k => $a) {
    if ($a > 0) $kaynaklar[] = ['kaynak'=>$k,'ad'=>($kaynakAd[$k]??ucfirst($k)),'adet'=>$a,'yuzde'=>$toplam>0?round($a/$toplam*100):0];
  }
  usort($kaynaklar, fn($a,$b)=>$b['adet']<=>$a['adet']);
  $kaynakMaks = count($kaynaklar) ? max(array_column($kaynaklar,'adet')) : 1;

  // ── Saat dağılımı ─────────────────────────────────────────────────────────
  $saatHam = [];
  for ($h = 10; $h <= 23; $h++) $saatHam[$h] = 0;
  foreach ($rlist as $rv) {
    $h = (int) substr((string)($rv->saat ?? '00:00'), 0, 2);
    if (isset($saatHam[$h])) $saatHam[$h]++;
  }
  $saatMaks = max(1, max($saatHam));
  $enYogunSaat = '—';
  $enYogunAdet = 0;
  foreach ($saatHam as $h => $a) {
    if ($a > $enYogunAdet) { $enYogunAdet = $a; $enYogunSaat = $h . ':00'; }
  }

  // ── Performans oranları ────────────────────────────────────────────────────
  $tamamlanan = $rlist->where('durum','geldi')->count();
  $iptalAdet  = $rlist->whereIn('durum',['iptal','gelmedi'])->count();
  $toplamKararlasik = $tamamlanan + $iptalAdet;
  $perfTamamlanma = $toplamKararlasik > 0 ? round($tamamlanan / $toplamKararlasik * 100) : 0;
  $perfIptal      = $toplamKararlasik > 0 ? round($iptalAdet  / $toplamKararlasik * 100) : 0;

  // ── Aylık özet (ay içi her gün için rezervasyon sayısı) ───────────────────
  $ayBasi = \Carbon\Carbon::parse($tarih)->startOfMonth();
  $aySon  = \Carbon\Carbon::parse($tarih)->endOfMonth();
  $ayAdi  = \Carbon\Carbon::parse($tarih)->isoFormat('MMMM YYYY');
  $gunSayisi = $ayBasi->daysInMonth;
  $ilkGunHafta = (int)$ayBasi->dayOfWeekIso; // 1=Pzt
  // Aylık rezervasyon günleri: hesapla DB'den (mevcut veriyi kullan — aynı ay mı değil mi)
  // Web route sadece günlük data verir; aylik için DB'ye erişip sayı çekelim (try/catch ile)
  $aylikGunler = [];
  try {
    $sube = function_exists('_webSubeId') ? _webSubeId() : null;
    if ($sube) {
      $aylikRows = \DB::table('rezervasyonlar')
        ->where('sube_id', $sube)
        ->whereBetween('tarih', [$ayBasi->format('Y-m-d'), $aySon->format('Y-m-d')])
        ->selectRaw('tarih, COUNT(*) as adet')
        ->groupBy('tarih')
        ->pluck('adet','tarih');
      for ($d = 1; $d <= $gunSayisi; $d++) {
        $gt = $ayBasi->copy()->day($d)->format('Y-m-d');
        $aylikGunler[] = ['gun'=>$d,'tarih'=>$gt,'adet'=>(int)($aylikRows[$gt]??0)];
      }
    }
  } catch (\Throwable $e) {}
  if (empty($aylikGunler)) {
    for ($d = 1; $d <= $gunSayisi; $d++) {
      $gt = $ayBasi->copy()->day($d)->format('Y-m-d');
      $aylikGunler[] = ['gun'=>$d,'tarih'=>$gt,'adet'=>0];
    }
  }
  $aylikMaks = max(1, max(array_column($aylikGunler,'adet')));

  // ── Stat kartları (6 kart — Flutter _pStat) ──────────────────────────────
  $statlar = [
    ['icon'=>'event_note',           'etiket'=>'Bugünün Rezervasyonları','deger'=>$toplam,       'renk'=>'var(--mor1)'],
    ['icon'=>'hourglass_bottom',     'etiket'=>'Bekleyen',               'deger'=>$bekleyen_s,   'renk'=>'var(--amber)'],
    ['icon'=>'check_circle_outline', 'etiket'=>'Onaylanan',              'deger'=>$onaylanan_s,  'renk'=>'var(--yesil)'],
    ['icon'=>'groups',               'etiket'=>'Toplam Misafir',         'deger'=>$misafir_s,    'renk'=>'var(--mavi)'],
    ['icon'=>'person_outline',       'etiket'=>'Ort. Kişi / Rez.',       'deger'=>$ortKisi==0?'0':number_format($ortKisi,1,',','.'), 'renk'=>'#EC4899'],
    ['icon'=>'local_fire_department','etiket'=>'En Yoğun Saat',          'deger'=>$enYogunSaat,  'renk'=>'#FB7185'],
  ];
@endphp

{{-- ── Flash mesajları ──────────────────────────────────────────────── --}}
@if (session('rez_ok'))
  <div class="rk-flash ok">✅ {{ session('rez_ok') }}</div>
@endif
@if (session('rez_hata'))
  <div class="rk-flash err">⚠️ {{ session('rez_hata') }}</div>
@endif

{{-- =============== GÜN SEÇİCİ (Flutter _mGunSecici) =============== --}}
<div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;margin-bottom:18px">
  @foreach ($gunler as $g)
    @php
      $aktif = $g['tarih'] === $tarih;
      $gd    = \Carbon\Carbon::parse($g['tarih']);
      $fark  = (int) \Carbon\Carbon::now()->startOfDay()->diffInDays($gd->copy()->startOfDay(), false);
      $etiket = ($g['tarih'] === $bugun) ? 'Bugün'
              : ($fark === 1 ? 'Yarın'
              : (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'][$gd->dayOfWeekIso - 1]));
    @endphp
    <a href="/rezervasyon?tarih={{ $g['tarih'] }}"
       class="rez-gun-pill {{ $aktif ? 'aktif' : '' }}">
      <span class="pg-etiket">{{ $etiket }}</span>
      <span class="pg-gun">{{ $gd->day }}</span>
      @if ($g['adet'] > 0)
        <span class="pg-badge">{{ $g['adet'] }}</span>
      @endif
    </a>
  @endforeach
  {{-- Tarih girişi (ekstra kolaylık) --}}
  <form method="GET" action="/rezervasyon" style="margin:0;display:flex;align-items:center;margin-left:10px">
    <input type="date" name="tarih" value="{{ $tarih }}" class="rk-inp"
           style="width:auto;height:74px;border-radius:14px" onchange="this.form.submit()">
  </form>
</div>

{{-- =============== 6 STAT KARTI (Flutter _pStat — tek sıra geniş) =============== --}}
<div class="rez-stat-grid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;margin-bottom:18px">
  @foreach ($statlar as $s)
    <div class="rez-stat" style="background:color-mix(in srgb,{{ $s['renk'] }} 11%,transparent);border:1px solid color-mix(in srgb,{{ $s['renk'] }} 28%,transparent)">
      <div class="rs-ust">
        <span class="rs-ikon" style="background:color-mix(in srgb,{{ $s['renk'] }} 22%,transparent)">
          <span class="material-icons-outlined" style="color:{{ $s['renk'] }}">{{ $s['icon'] }}</span>
        </span>
        <span class="rs-etiket">{{ $s['etiket'] }}</span>
      </div>
      <div class="rs-deger" style="color:{{ $s['renk'] }}">{{ $s['deger'] }}</div>
    </div>
  @endforeach
</div>

{{-- =============== REZERVASYON LİSTESİ — bölüm başlığı =============== --}}
<div class="rk-bolum" style="margin-bottom:12px">
  <span class="cubuk" style="background:var(--mor1)"></span>
  <span class="b">Rezervasyonlar</span>
  <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($rezervasyonlar ?? []) }}</span>
</div>

{{-- TABLO (geniş ekran, Flutter _mTablo) --}}
<div class="rez-tablo-wrap" style="margin-bottom:18px">
  @if (!count($rezervasyonlar ?? []))
    {{-- Boş durum (Flutter _bosListeKart) --}}
    <div class="rk-card" style="text-align:center;padding:48px 20px">
      <span style="display:inline-flex;width:62px;height:62px;border-radius:18px;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 10%,transparent)">
        <span class="material-icons-outlined" style="font-size:30px;color:var(--mor1)">event_available</span>
      </span>
      <div style="font-size:15px;font-weight:700;color:var(--ink);margin-top:14px">Bu gün için rezervasyon yok</div>
      <div class="rk-sub" style="font-size:12.5px;margin-top:6px;line-height:1.35">Yeni rezervasyon eklemek için "+ Yeni Rezervasyon" butonunu kullanın.</div>
    </div>
  @else
    <div class="rk-tablo">
      <table>
        <thead>
          <tr>
            <th style="width:8%">Saat</th>
            <th style="width:22%">Müşteri</th>
            <th style="width:30%">Not</th>
            <th class="orta" style="width:5%">Kişi</th>
            <th class="orta" style="width:12%">Durum</th>
            <th class="sag"  style="width:23%">İşlem</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($rezervasyonlar as $r)
            @php
              $d     = $r->durum ?? 'bekliyor';
              $renk  = $durumRenk[$d] ?? 'var(--mor1)';
              $pasif = in_array($d, ['iptal','gelmedi']);
              $not   = trim((string)($r->not ?? ''));
              $tel   = trim((string)($r->telefon ?? ''));
              $masa  = trim((string)($r->masa_ad ?? ''));
              $kynk  = strtolower((string)($r->kaynak ?? ''));
              if ($kynk === 'walkin') $kynk = 'walk_in';
            @endphp
            <tr @if($pasif) style="opacity:.65" @endif>
              {{-- SAAT --}}
              <td>
                <span style="font-size:15px;font-weight:800;color:{{ $renk }}">{{ substr((string)$r->saat,0,5) }}</span>
              </td>
              {{-- MÜŞTERİ --}}
              <td>
                <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                  <span style="font-weight:700;color:{{ $pasif?'var(--sub)':'var(--ink)' }};{{ $pasif?'text-decoration:line-through':'' }}">{{ $r->ad }}</span>
                  <span style="font-size:12px">{{ $kaynakIkon[$kynk] ?? '•' }}</span>
                </div>
                @if ($tel !== '')
                  <div style="font-size:12px;color:var(--sub);margin-top:2px">📞 {{ $tel }}</div>
                @endif
                @if ($masa !== '')
                  <div style="margin-top:4px">
                    <span style="display:inline-block;padding:2px 7px;border-radius:20px;font-size:11px;font-weight:600;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 12%,transparent)">🪑 Masa {{ $masa }}</span>
                  </div>
                @endif
              </td>
              {{-- NOT (renkli kutu — Flutter kart benzer) --}}
              <td>
                @if ($not === '')
                  <span style="color:var(--sub2)">—</span>
                @else
                  @php $notRenk = $pasif ? 'var(--sub2)' : 'var(--amber)'; @endphp
                  <div style="display:flex;align-items:flex-start;gap:7px;padding:8px 10px;border-radius:10px;background:color-mix(in srgb,{{ $notRenk }} 12%,transparent);border:1px solid color-mix(in srgb,{{ $notRenk }} 35%,transparent)">
                    <span class="material-icons-outlined" style="font-size:15px;color:{{ $notRenk }};flex:0 0 auto">sticky_note_2</span>
                    <span style="font-size:13px;font-weight:500;color:{{ $pasif?'var(--sub)':'var(--ink)' }};line-height:1.3">{{ $not }}</span>
                  </div>
                @endif
              </td>
              {{-- KİŞİ --}}
              <td class="orta">
                <span style="font-weight:700;color:var(--ink)">{{ (int)$r->kisi }}</span>
              </td>
              {{-- DURUM rozeti --}}
              <td class="orta">
                <span class="rk-rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 40%,transparent);background:color-mix(in srgb,{{ $renk }} 13%,transparent)">
                  {{ $durumAd[$d] ?? strtoupper($d) }}
                </span>
              </td>
              {{-- İŞLEM (Flutter _mAksiyonlar) --}}
              <td class="sag">
                @if ($d === 'bekliyor')
                  <div style="display:inline-flex;gap:8px;justify-content:flex-end">
                    <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                      <input type="hidden" name="tarih"  value="{{ $tarih }}">
                      <input type="hidden" name="id"     value="{{ $r->id }}">
                      <input type="hidden" name="durum"  value="onaylandi">
                      <button class="rk-btn" style="background:var(--yesil)">
                        <span class="material-icons-outlined" style="font-size:15px">check</span>Onayla
                      </button>
                    </form>
                    <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                      <input type="hidden" name="tarih"  value="{{ $tarih }}">
                      <input type="hidden" name="id"     value="{{ $r->id }}">
                      <input type="hidden" name="durum"  value="iptal">
                      <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent)">
                        <span class="material-icons-outlined" style="font-size:15px">close</span>İptal
                      </button>
                    </form>
                  </div>
                @elseif ($d === 'onaylandi')
                  <div style="display:inline-flex;gap:8px;justify-content:flex-end">
                    <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                      <input type="hidden" name="tarih"  value="{{ $tarih }}">
                      <input type="hidden" name="id"     value="{{ $r->id }}">
                      <input type="hidden" name="durum"  value="geldi">
                      <button class="rk-btn" style="background:var(--mor1)">
                        <span class="material-icons-outlined" style="font-size:15px">login</span>Oturt
                      </button>
                    </form>
                    <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                      <input type="hidden" name="tarih"  value="{{ $tarih }}">
                      <input type="hidden" name="id"     value="{{ $r->id }}">
                      <input type="hidden" name="durum"  value="gelmedi">
                      <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent)">
                        <span class="material-icons-outlined" style="font-size:15px">person_off</span>Gelmedi
                      </button>
                    </form>
                  </div>
                @else
                  <span style="color:var(--sub)">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

{{-- KART LİSTESİ (dar ekran — Flutter _kart mobil) --}}
<div class="rez-kart-list" style="margin-bottom:18px">
  @if (!count($rezervasyonlar ?? []))
    <div class="rk-card" style="text-align:center;padding:48px 20px">
      <span style="display:inline-flex;width:62px;height:62px;border-radius:18px;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 10%,transparent)">
        <span class="material-icons-outlined" style="font-size:30px;color:var(--mor1)">event_available</span>
      </span>
      <div style="font-size:15px;font-weight:700;color:var(--ink);margin-top:14px">Bu gün için rezervasyon yok</div>
      <div class="rk-sub" style="font-size:12.5px;margin-top:6px;line-height:1.35">Yeni rezervasyon eklemek için "+ Yeni Rezervasyon" butonunu kullanın.</div>
    </div>
  @else
    @foreach ($rezervasyonlar as $r)
      @php
        $d    = $r->durum ?? 'bekliyor';
        $renk = $durumRenk[$d] ?? 'var(--mor1)';
        $pasif = in_array($d, ['iptal','gelmedi']);
        $not  = trim((string)($r->not ?? ''));
        $tel  = trim((string)($r->telefon ?? ''));
        $masa = trim((string)($r->masa_ad ?? ''));
        $kynk = strtolower((string)($r->kaynak ?? ''));
        if ($kynk === 'walkin') $kynk = 'walk_in';
      @endphp
      <div class="rez-kart-sol" style="border-left-color:{{ $renk }};{{ $pasif?'opacity:.65':'' }}">
        <div style="display:flex;align-items:flex-start;gap:14px">
          <span style="font-size:20px;font-weight:800;color:{{ $renk }};white-space:nowrap">{{ substr((string)$r->saat,0,5) }}</span>
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:15.5px;color:{{ $pasif?'var(--sub)':'var(--ink)' }};{{ $pasif?'text-decoration:line-through':'' }}">{{ $r->ad }}</div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:3px">
              <span style="color:var(--sub);font-size:12.5px;display:flex;align-items:center;gap:3px">
                <span class="material-icons-outlined" style="font-size:13px">people_outline</span>{{ (int)$r->kisi }} kişi
              </span>
              <span style="font-size:12px">{{ $kaynakIkon[$kynk] ?? '•' }}</span>
              @if ($masa !== '')
                <span style="color:var(--sub);font-size:12.5px;display:flex;align-items:center;gap:3px">
                  <span class="material-icons-outlined" style="font-size:13px">table_restaurant</span>Masa {{ $masa }}
                </span>
              @endif
            </div>
          </div>
          <span class="rk-rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 40%,transparent);background:color-mix(in srgb,{{ $renk }} 13%,transparent);font-size:10.5px">
            {{ $durumAd[$d] ?? strtoupper($d) }}
          </span>
        </div>
        @if ($not !== '')
          <div style="margin-top:8px;font-size:12.5px;color:var(--sub2)">📝 {{ $not }}</div>
        @endif
        @if ($tel !== '')
          <div style="margin-top:6px;font-size:12.5px;color:var(--sub)">📞 {{ $tel }}</div>
        @endif
        @if (!$pasif && $d !== 'geldi')
          <div style="display:flex;gap:8px;margin-top:12px">
            @if ($d === 'bekliyor')
              <form method="POST" action="/rezervasyon/durum" style="margin:0;flex:1">@csrf
                <input type="hidden" name="tarih" value="{{ $tarih }}">
                <input type="hidden" name="id"    value="{{ $r->id }}">
                <input type="hidden" name="durum" value="onaylandi">
                <button class="rk-btn" style="background:var(--yesil);width:100%;justify-content:center">
                  <span class="material-icons-outlined" style="font-size:15px">check</span>Onayla
                </button>
              </form>
              <form method="POST" action="/rezervasyon/durum" style="margin:0;flex:1">@csrf
                <input type="hidden" name="tarih" value="{{ $tarih }}">
                <input type="hidden" name="id"    value="{{ $r->id }}">
                <input type="hidden" name="durum" value="iptal">
                <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent);width:100%;justify-content:center">
                  <span class="material-icons-outlined" style="font-size:15px">close</span>İptal
                </button>
              </form>
            @elseif ($d === 'onaylandi')
              <form method="POST" action="/rezervasyon/durum" style="margin:0;flex:1">@csrf
                <input type="hidden" name="tarih" value="{{ $tarih }}">
                <input type="hidden" name="id"    value="{{ $r->id }}">
                <input type="hidden" name="durum" value="geldi">
                <button class="rk-btn" style="background:var(--mor1);width:100%;justify-content:center">
                  <span class="material-icons-outlined" style="font-size:15px">login</span>Oturt
                </button>
              </form>
              <form method="POST" action="/rezervasyon/durum" style="margin:0;flex:1">@csrf
                <input type="hidden" name="tarih" value="{{ $tarih }}">
                <input type="hidden" name="id"    value="{{ $r->id }}">
                <input type="hidden" name="durum" value="gelmedi">
                <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent);width:100%;justify-content:center">
                  <span class="material-icons-outlined" style="font-size:15px">person_off</span>Gelmedi
                </button>
              </form>
            @endif
          </div>
        @endif
      </div>
    @endforeach
  @endif
</div>

{{-- =============== DURUM DAĞILIMI + KAYNAK DAĞILIMI (Flutter yan yana) =============== --}}
<div class="rez-iki" style="margin-bottom:14px">

  {{-- Durum Dağılımı (Flutter _pDurumKart) --}}
  <div class="rez-akart">
    <div class="ak-baslik">
      <span class="ak-ikon" style="background:color-mix(in srgb,var(--mor1) 14%,transparent)">
        <span class="material-icons-outlined" style="color:var(--mor1)">donut_large</span>
      </span>
      <span class="ak-ad">Durum Dağılımı</span>
    </div>
    {{-- Stacked bar --}}
    <div class="rez-durum-bar">
      @if ($toplam === 0)
        <div style="flex:1;background:var(--card2)"></div>
      @else
        @foreach ($durumlar as $dd)
          @if ($dd['adet'] > 0)
            <div style="flex:{{ $dd['adet'] }};background:{{ $durumRenk[$dd['durum']] ?? 'var(--sub2)' }}"></div>
          @endif
        @endforeach
      @endif
    </div>
    {{-- Durum satırları --}}
    @foreach ($durumlar as $dd)
      @php $dr = $durumRenk[$dd['durum']] ?? 'var(--sub2)'; @endphp
      <div class="rez-durum-satir">
        <span class="ds-renk" style="background:{{ $dr }}"></span>
        <span class="ds-ad">{{ $dd['ad'] }}</span>
        <span class="ds-yuzde" style="color:{{ $dr }}">%{{ $dd['yuzde'] }}</span>
        <span class="ds-adet">{{ $dd['adet'] }}</span>
      </div>
    @endforeach
    <div class="rez-toplam-satir">
      <span class="tad">Toplam</span>
      <span class="tv">{{ $toplam }}</span>
    </div>
  </div>

  {{-- Kaynak Dağılımı (Flutter _pKaynakKart) --}}
  <div class="rez-akart">
    <div class="ak-baslik">
      <span class="ak-ikon" style="background:color-mix(in srgb,var(--mavi) 14%,transparent)">
        <span class="material-icons-outlined" style="color:var(--mavi)">layers</span>
      </span>
      <span class="ak-ad">Kaynak Dağılımı</span>
    </div>
    @php $kaynakRenkler = ['var(--mavi)','var(--yesil)','var(--amber)','var(--kirmizi)','var(--mor1)']; @endphp
    @if (empty($kaynaklar))
      <div style="padding:6px 0;font-size:12px;color:var(--sub)">Kayıt yok</div>
    @else
      @foreach ($kaynaklar as $ki => $k)
        @php $kr = $kaynakRenkler[$ki % count($kaynakRenkler)]; $oran = $kaynakMaks > 0 ? $k['adet']/$kaynakMaks : 0; @endphp
        <div class="rez-kaynak-satir">
          <div class="ks-ust">
            <span class="ks-ad">{{ $k['ad'] }}</span>
            <span class="ks-bilgi">{{ $k['adet'] }}  (%{{ $k['yuzde'] }})</span>
          </div>
          <div class="ks-bar">
            <div class="ks-fill" style="width:{{ round($oran*100) }}%;background:{{ $kr }}"></div>
          </div>
        </div>
      @endforeach
    @endif
  </div>
</div>

{{-- =============== PERFORMANS + AYLIK ÖZET (Flutter yan yana) =============== --}}
<div class="rez-iki" style="margin-bottom:14px">

  {{-- Performans Oranları (Flutter _pPerformansKart) --}}
  <div class="rez-akart">
    <div class="ak-baslik">
      <span class="ak-ikon" style="background:color-mix(in srgb,#FB7185 14%,transparent)">
        <span class="material-icons-outlined" style="color:#FB7185">speed</span>
      </span>
      <span class="ak-ad">Performans Oranları</span>
    </div>
    {{-- Tamamlanma --}}
    <div class="rez-perf-satir">
      <div class="ps-ust">
        <span class="ps-ik"><span class="material-icons-outlined" style="color:var(--yesil)">check_circle</span></span>
        <span class="ps-ad">Tamamlanma Oranı</span>
        <span class="ps-yuzde" style="color:var(--yesil)">%{{ $perfTamamlanma }}</span>
      </div>
      <div class="ps-bar">
        <div class="ps-fill" style="width:{{ min(100,$perfTamamlanma) }}%;background:var(--yesil)"></div>
      </div>
    </div>
    {{-- İptal --}}
    <div class="rez-perf-satir" style="margin-bottom:0">
      <div class="ps-ust">
        <span class="ps-ik"><span class="material-icons-outlined" style="color:var(--kirmizi)">cancel</span></span>
        <span class="ps-ad">İptal Oranı</span>
        <span class="ps-yuzde" style="color:var(--kirmizi)">%{{ $perfIptal }}</span>
      </div>
      <div class="ps-bar">
        <div class="ps-fill" style="width:{{ min(100,$perfIptal) }}%;background:var(--kirmizi)"></div>
      </div>
    </div>
  </div>

  {{-- Aylık Özet — mini takvim (Flutter _pAylikKart) --}}
  <div class="rez-akart">
    <div class="ak-baslik">
      <span class="ak-ikon" style="background:color-mix(in srgb,var(--mor1) 14%,transparent)">
        <span class="material-icons-outlined" style="color:var(--mor1)">calendar_month</span>
      </span>
      <span class="ak-ad">Aylık Özet</span>
      <span class="ak-sag">{{ $ayAdi }}</span>
    </div>
    <div class="rez-aylik-grid">
      {{-- Hafta başlıkları --}}
      @foreach (['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'] as $hd)
        <div class="ag-hd">{{ $hd }}</div>
      @endforeach
      {{-- Boş hücreler (ayın ilk günü haftanın kaçıncı günü) --}}
      @for ($bi = 1; $bi < $ilkGunHafta; $bi++)
        <div class="ag-gun bos-hucre"></div>
      @endfor
      {{-- Gün hücreleri --}}
      @foreach ($aylikGunler as $ag)
        @php
          $agTarih  = $ag['tarih'];
          $agAdet   = $ag['adet'];
          $agSecili = $agTarih === $tarih;
          // Renk: boşsa card2, doluysa mor1 ton
          if ($agAdet === 0) {
            $agBg   = 'var(--card2)';
            $agKlr  = 'var(--sub)';
          } else {
            $ton    = min(1, $agAdet / $aylikMaks);
            // opacity interpolation: 0.22 -> 1.0 (CSS çözümü)
            $op     = round(0.22 + $ton * 0.78, 2);
            $agBg   = 'color-mix(in srgb,var(--mor1) '.round($op*100).'%,var(--card2))';
            $agKlr  = '#fff';
          }
        @endphp
        <a href="/rezervasyon?tarih={{ $agTarih }}"
           class="ag-gun {{ $agSecili ? 'secili' : '' }}"
           style="background:{{ $agBg }};color:{{ $agKlr }};text-decoration:none">{{ $ag['gun'] }}</a>
      @endforeach
    </div>
  </div>
</div>

{{-- =============== SAAT DAĞILIMI (Flutter _pSaatKart — en altta) =============== --}}
<div class="rez-akart" style="margin-bottom:24px">
  <div class="ak-baslik">
    <span class="ak-ikon" style="background:color-mix(in srgb,#FB7185 14%,transparent)">
      <span class="material-icons-outlined" style="color:#FB7185">schedule</span>
    </span>
    <span class="ak-ad">Saat Dağılımı</span>
  </div>
  <div class="rez-saat-grafik">
    @foreach ($saatHam as $h => $a)
      @php $bar_h = $saatMaks > 0 ? max(3, round(($a/$saatMaks)*104)) : 3; $dolu = $a > 0; @endphp
      <div class="sg-col">
        <div class="sg-bar" style="height:{{ $bar_h }}px;background:{{ $dolu ? '#FB7185' : 'var(--card2)' }}"></div>
      </div>
    @endforeach
  </div>
  <div class="rez-saat-bar-aks">
    <span>{{ array_key_first($saatHam) }}:00</span>
    <span>{{ array_key_last($saatHam) }}:00</span>
  </div>
</div>

{{-- =============== + YENİ REZERVASYON (Flutter _ekleDialog → modal) =============== --}}
{{-- Sabit FAB butonu sağ altta (Flutter FAB gibi) --}}
<button onclick="document.getElementById('rez-modal').style.display='flex'"
        style="position:fixed;bottom:28px;right:28px;display:flex;align-items:center;gap:8px;
               padding:14px 22px;border-radius:16px;background:var(--mor1);color:#fff;
               font-size:14px;font-weight:700;border:none;cursor:pointer;
               box-shadow:0 8px 24px color-mix(in srgb,var(--mor1) 45%,transparent);z-index:50">
  <span class="material-icons-outlined">add</span>Yeni Rezervasyon
</button>

{{-- Modal overlay --}}
<div id="rez-modal" onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100;
            align-items:flex-end;justify-content:center">
  <div style="background:var(--card);border-radius:22px 22px 0 0;width:100%;max-width:680px;
              max-height:92vh;overflow-y:auto;padding:22px 22px 32px">
    {{-- Drag handle --}}
    <div style="display:flex;justify-content:center;margin-bottom:14px">
      <div style="width:40px;height:4px;border-radius:4px;background:var(--line)"></div>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
      <span style="font-size:18px;font-weight:800;color:var(--ink)">Yeni Rezervasyon</span>
      <button onclick="document.getElementById('rez-modal').style.display='none'"
              style="background:none;border:none;cursor:pointer;color:var(--sub);padding:4px">
        <span class="material-icons-outlined">close</span>
      </button>
    </div>

    <form method="POST" action="/rezervasyon/ekle" style="margin:0">
      @csrf
      <input type="hidden" name="geri_tarih" value="{{ $tarih }}">

      {{-- Telefon (CRM arama için öne çekiyoruz, Flutter ile aynı sıra) --}}
      <label class="rk-lbl" style="margin-top:0">Telefon</label>
      <input type="text" name="telefon" class="rk-inp" placeholder="05xx xxx xx xx">

      <label class="rk-lbl">Ad Soyad *</label>
      <input type="text" name="ad" required class="rk-inp" placeholder="Müşteri adı">

      {{-- Kişi sayısı --}}
      <label class="rk-lbl">Kişi Sayısı</label>
      <div style="display:flex;align-items:center;gap:10px">
        <button type="button" onclick="rkRezKisi(-1)"
                style="width:36px;height:36px;border-radius:10px;border:1px solid var(--line);background:var(--card2);cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;color:var(--sub2)">−</button>
        <input type="number" id="rez-kisi-inp" name="kisi" value="2" min="1" max="99"
               class="rk-inp" style="width:80px;text-align:center;font-weight:700;font-size:17px">
        <button type="button" onclick="rkRezKisi(1)"
                style="width:36px;height:36px;border-radius:10px;border:1px solid var(--mor1);background:color-mix(in srgb,var(--mor1) 12%,transparent);cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;color:var(--mor1)">+</button>
      </div>

      {{-- Tarih + Saat --}}
      <div class="rk-grid" style="grid-template-columns:1fr 1fr;margin-top:0">
        <div>
          <label class="rk-lbl">Tarih *</label>
          <input type="date" name="tarih" value="{{ $tarih }}" min="{{ $bugun }}" required class="rk-inp">
        </div>
        <div>
          <label class="rk-lbl">Saat *</label>
          <select name="saat" required class="rk-inp">
            @for ($h = 12; $h <= 23; $h++)
              @foreach (['00','30'] as $mm)
                @php $sv = str_pad($h,2,'0',STR_PAD_LEFT).':'.$mm; @endphp
                <option value="{{ $sv }}" {{ $sv === '19:30' ? 'selected' : '' }}>{{ $sv }}</option>
              @endforeach
            @endfor
          </select>
        </div>
      </div>

      {{-- Masa --}}
      <label class="rk-lbl">Masa (opsiyonel)</label>
      <select name="masa_id" class="rk-inp">
        <option value="">— Masa atama —</option>
        @foreach ($masalar as $m)
          <option value="{{ $m->id }}">Masa {{ $m->ad }}{{ !empty($m->kapasite) ? ' · '.(int)$m->kapasite.' kişilik' : '' }}</option>
        @endforeach
      </select>

      {{-- Özel istek etiketleri (Flutter etiketSecenek) --}}
      <label class="rk-lbl">Özel İstek</label>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        @foreach (['🎂 Doğum günü','💍 Yıldönümü','🪟 Pencere kenarı','👶 Bebek sandalyesi','♿ Tekerlekli sandalye','⚠️ Alerji','⭐ VIP','🤫 Sessiz köşe'] as $et)
          <label style="display:flex;align-items:center;gap:6px;padding:6px 10px;border-radius:20px;border:1px solid var(--line);background:var(--card2);cursor:pointer;font-size:12px;color:var(--sub2)">
            <input type="checkbox" name="etiketler[]" value="{{ $et }}" style="display:none" class="rez-et-chk" onchange="rkEtTog(this)">
            {{ $et }}
          </label>
        @endforeach
      </div>

      {{-- Serbest not --}}
      <label class="rk-lbl">Not (opsiyonel)</label>
      <textarea name="not" rows="2" class="rk-inp" placeholder="Özel not, istek..."></textarea>

      {{-- Müşteri notu / alerji --}}
      <label class="rk-lbl">Müşteri Notu / Alerji (kalıcı)</label>
      <input type="text" name="musteri_not" class="rk-inp" placeholder="Kaydedilir, sonraki rezervasyonda hatırlatılır">

      <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:18px">
        <span class="material-icons-outlined" style="font-size:18px">add</span>Kaydet
      </button>
    </form>
  </div>
</div>

@push('scripts')
<script>
// Kişi sayısı +/- butonları
function rkRezKisi(delta) {
  var inp = document.getElementById('rez-kisi-inp');
  var v = parseInt(inp.value, 10) + delta;
  inp.value = Math.max(1, Math.min(99, v));
}
// Özel istek etiket toggle (checkbox label rengi)
function rkEtTog(chk) {
  var lbl = chk.closest('label');
  if (chk.checked) {
    lbl.style.background = 'var(--mor1)';
    lbl.style.borderColor = 'var(--mor1)';
    lbl.style.color = '#fff';
  } else {
    lbl.style.background = 'var(--card2)';
    lbl.style.borderColor = 'var(--line)';
    lbl.style.color = 'var(--sub2)';
  }
}
// Flash otomatik kapat
(function(){
  var f = document.querySelectorAll('.rk-flash');
  f.forEach(function(el){ setTimeout(function(){ el.style.transition='opacity .5s'; el.style.opacity='0'; setTimeout(function(){ el.style.display='none'; },500); }, 4000); });
})();
</script>
@endpush

@endsection

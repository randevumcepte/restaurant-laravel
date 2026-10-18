@extends('layout.app')
@section('title', 'Bağlı Cihazlar')
@section('rk_ikon', 'devices_other')
@section('baslik', 'Bağlı Cihazlar')
@section('rk_alt', 'Tanımlı cihazlar (yazıcı/yazarkasa/terminal…) + ResteOS açık cihazlar (çevrimiçi) ve yazarkasa durumu.')

@section('content')
@php
  $cihazlar = $cihazlar ?? [];
  $esikSn   = (int) ($esikSn ?? 60);
  $hata     = $hata ?? false;

  // tip -> etiket + material ikon (Flutter bagli_cihazlar_screen _tipler ile birebir)
  $tipler = [
    'yazici'       => ['ad' => 'Yazıcı (fiş/mutfak)',    'ikon' => 'print'],
    'yazarkasa'    => ['ad' => 'Yazarkasa (ÖKC)',        'ikon' => 'point_of_sale'],
    'terminal'     => ['ad' => 'Ödeme Terminali (POS)',  'ikon' => 'credit_card'],
    'cekmece'      => ['ad' => 'Kasa Çekmecesi',          'ikon' => 'inbox'],
    'bilgisayar'   => ['ad' => 'Bilgisayar / Kasa',       'ikon' => 'computer'],
    'kds'          => ['ad' => 'Mutfak Ekranı (KDS)',     'ikon' => 'tv'],
    'barkod'       => ['ad' => 'Barkod Okuyucu',          'ikon' => 'qr_code_scanner'],
    'el_terminali' => ['ad' => 'El Terminali',            'ikon' => 'pan_tool'],
    'tablet'       => ['ad' => 'Tablet',                  'ikon' => 'tablet_android'],
    'telefon'      => ['ad' => 'Telefon',                 'ikon' => 'smartphone'],
    'diger'        => ['ad' => 'Diğer',                   'ikon' => 'devices_other'],
  ];
  $tipAd   = fn($k) => $tipler[$k]['ad']   ?? $tipler['diger']['ad'];
  $tipIkon = fn($k) => $tipler[$k]['ikon'] ?? $tipler['diger']['ikon'];

  $manuel = array_values(array_filter($cihazlar, fn($c) => !empty($c['manuel'])));
  $canli  = array_values(array_filter($cihazlar, fn($c) => empty($c['manuel'])));
  $onlineSayi = count(array_filter($canli, fn($c) => !empty($c['online'])));

  // Yazarkasa durum -> renk degiskeni + etiket (Flutter _ykRenk eslemesi)
  $ykHarita = [
    'online'   => ['renk' => 'var(--yesil)',  'et' => 'Bağlı'],
    'hata'     => ['renk' => 'var(--kirmizi)', 'et' => 'HATA'],
    'test'     => ['renk' => 'var(--amber)',   'et' => 'Test/Simülasyon'],
    'bekliyor' => ['renk' => 'var(--amber)',   'et' => 'Aktif · fiş yok'],
    'kapali'   => ['renk' => 'var(--sub2)',    'et' => 'Kapalı'],
  ];
@endphp

@if (session('bc_mesaj'))
  <div class="rk-flash ok">{{ session('bc_mesaj') }}</div>
@endif
@if (session('bc_hata'))
  <div class="rk-flash err">{{ session('bc_hata') }}</div>
@endif
@if ($hata)
  <div class="rk-flash err">Cihaz bilgileri yüklenemedi. (Tablo henüz oluşmamış olabilir.)</div>
@endif

{{-- ===== OZET SERIT (Flutter: '$onlineSayi cihaz çevrimiçi' + sayilar) ===== --}}
<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:18px">
  <div class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">
    <span style="width:8px;height:8px;border-radius:50%;background:var(--yesil);display:inline-block"></span>
    {{ $onlineSayi }} cihaz çevrimiçi
  </div>
  <div class="rk-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:16px">power</span> Tanımlı: {{ count($manuel) }}
  </div>
  <div class="rk-rozet" style="color:var(--sub2);background:color-mix(in srgb,var(--sub2) 13%,transparent);border-color:color-mix(in srgb,var(--sub2) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:16px">devices</span> Toplam: {{ count($cihazlar) }}
  </div>
  <div class="rk-sub" style="margin-left:auto;font-size:11.5px">Çevrimiçi eşiği: son {{ $esikSn }} sn</div>
</div>

{{-- ===== TANIMLI CIHAZLAR (manuel) ===== --}}
<div style="margin-bottom:24px">
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--mor1)"></span>
    <span class="b">🔌 Tanımlı Cihazlar</span>
    <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($manuel) }}</span>
  </div>

  @if (empty($manuel))
    <div class="rk-card" style="text-align:center;padding:40px 20px">
      <span class="material-icons-outlined" style="font-size:52px;color:var(--sub2)">devices_other</span>
      <div class="rk-ink" style="font-size:16px;font-weight:800;margin-top:10px">Henüz cihaz tanımlanmadı</div>
      <div class="rk-sub" style="font-size:13px;margin-top:6px;line-height:1.5">
        Yazıcı, yazarkasa, terminal vb. cihazlar uygulamadan eklenip IP/port girilebilir.
      </div>
    </div>
  @else
    <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
      @foreach ($manuel as $c)
        @php
          $aktif = !empty($c['aktif']);
          $renk  = $aktif ? 'var(--yesil)' : 'var(--sub2)';
          $tip   = $c['tip'] ?? 'diger';
        @endphp
        <div class="rk-card" style="padding:14px;display:flex;flex-direction:column">
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none;background:color-mix(in srgb,{{ $renk }} 14%,transparent)">
              <span class="material-icons-outlined" style="font-size:24px;color:{{ $renk }}">{{ $tipIkon($tip) }}</span>
            </div>
            <div style="min-width:0;flex:1">
              <div class="rk-ink" style="font-size:14.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['ad'] }}</div>
              <div class="rk-sub" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $tipAd($tip) }}{{ !empty($c['marka']) ? ' · ' . $c['marka'] : '' }}</div>
              @if (!empty($c['ip']))
                <div class="rk-sub" style="font-size:11.5px;font-weight:600;color:var(--ink)">IP: {{ $c['ip'] }}{{ !empty($c['port']) ? ':' . $c['port'] : '' }}</div>
              @endif
            </div>
            <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 15%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">{{ $aktif ? 'Aktif' : 'Pasif' }}</span>
          </div>

          @if (!empty($c['seri_no']))
            <div class="rk-sub" style="font-size:11px;margin-top:8px">Seri No: {{ $c['seri_no'] }}</div>
          @endif
          @if (!empty($c['notlar']))
            <div class="rk-sub" style="font-size:11.5px;margin-top:8px;line-height:1.3">{{ $c['notlar'] }}</div>
          @endif

          {{-- ad duzenle + sil (web'de desteklenen aksiyonlar; LAN testi/yazici-yap uygulamada) --}}
          <div style="display:flex;align-items:center;gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid var(--line)">
            <form method="POST" action="/bagli-cihazlar/ad" style="display:flex;align-items:center;gap:8px;flex:1;min-width:0"
                  onsubmit="var n=this.querySelector('[name=ad]'); if(!n.value.trim()){n.focus();return false;} return true;">
              @csrf
              <input type="hidden" name="id" value="{{ $c['id'] }}">
              <input name="ad" value="{{ $c['ad'] }}" maxlength="80" class="rk-inp" style="flex:1;min-width:0;padding:8px 10px;font-size:13px">
              <button type="submit" class="rk-btn" style="flex:none;background:var(--mor1)">
                <span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet
              </button>
            </form>
            <form method="POST" action="/bagli-cihazlar/sil" style="flex:none"
                  onsubmit="return confirm('“{{ addslashes($c['ad']) }}” silinsin mi?');">
              @csrf
              <input type="hidden" name="id" value="{{ $c['id'] }}">
              <button type="submit" class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 40%,transparent)">
                <span class="material-icons-outlined" style="font-size:16px">delete</span> Sil
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- ===== YAZARKASA (OKC durumu) ===== --}}
@if (!empty($yazarkasa))
  @php
    $durum = $yazarkasa['durum'] ?? 'kapali';
    $yk    = $ykHarita[$durum] ?? $ykHarita['kapali'];
    $ykRenk = $yk['renk'];
    $ykAktif = !empty($yazarkasa['aktif']);
  @endphp
  <div style="margin-bottom:24px">
    <div class="rk-bolum">
      <span class="cubuk" style="background:{{ $ykRenk }}"></span>
      <span class="b">🧾 Yazarkasa (ÖKC)</span>
    </div>
    <div class="rk-card" style="padding:16px;border:1px solid color-mix(in srgb,{{ $ykRenk }} 40%,transparent)">
      <div style="display:flex;align-items:flex-start;gap:12px">
        <div style="position:relative;width:44px;height:44px;flex:none">
          <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,{{ $ykRenk }} 15%,transparent)">
            <span class="material-icons-outlined" style="font-size:24px;color:{{ $ykRenk }}">point_of_sale</span>
          </div>
          <span style="position:absolute;right:-2px;bottom:-2px;width:13px;height:13px;border-radius:50%;background:{{ $ykRenk }};border:2px solid var(--card)"></span>
        </div>
        <div style="min-width:0;flex:1">
          <div class="rk-ink" style="font-size:14px;font-weight:800">Yazarkasa{{ !empty($yazarkasa['marka']) ? ' · ' . $yazarkasa['marka'] : '' }}</div>
          @if ($ykAktif && !empty($yazarkasa['ip']))
            <div class="rk-sub" style="font-size:11.5px">IP: {{ $yazarkasa['ip'] }}{{ !empty($yazarkasa['port']) ? ':' . $yazarkasa['port'] : '' }}</div>
          @endif
        </div>
        <span class="rk-rozet" style="color:{{ $ykRenk }};background:color-mix(in srgb,{{ $ykRenk }} 15%,transparent);border-color:color-mix(in srgb,{{ $ykRenk }} 40%,transparent)">{{ $yk['et'] }}</span>
      </div>
      @if (!empty($yazarkasa['mesaj']))
        <div class="rk-sub" style="font-size:12.5px;margin-top:10px;line-height:1.3;color:var(--ink)">{{ $yazarkasa['mesaj'] }}</div>
      @endif
      @if (!empty($yazarkasa['son_fis']))
        <div class="rk-sub" style="font-size:11px;margin-top:4px">Son mali fiş: {{ $yazarkasa['son_fis'] }}</div>
      @endif
    </div>
  </div>
@endif

{{-- ===== CEVRIMICI (heartbeat) CIHAZLAR ===== --}}
<div>
  <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
    <span style="width:8px;height:8px;border-radius:50%;background:var(--yesil);display:inline-block"></span>
    <span class="rk-sub" style="font-size:12px">{{ $onlineSayi }} cihaz çevrimiçi</span>
    <span class="rk-sub" style="margin-left:auto;font-size:11px">10 sn'de bir yenilenir</span>
  </div>
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--yesil)"></span>
    <span class="b">📱 Çevrimiçi Cihazlar (ResteOS)</span>
    <span class="sayi" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent)">{{ count($canli) }}</span>
  </div>

  @if (empty($canli))
    <div class="rk-card" style="text-align:center;padding:40px 20px">
      <span class="material-icons-outlined" style="font-size:52px;color:var(--sub2)">wifi_off</span>
      <div class="rk-ink" style="font-size:16px;font-weight:800;margin-top:10px">Sinyal gönderen cihaz yok</div>
      <div class="rk-sub" style="font-size:13px;margin-top:6px;line-height:1.5">Bir cihazda ResteOS açık olduğunda burada görünür.</div>
    </div>
  @else
    <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
      @foreach ($canli as $c)
        @php
          $online = !empty($c['online']);
          $renk   = $online ? 'var(--yesil)' : 'var(--kirmizi)';
          $tip    = $c['tip'] ?? 'diger';
        @endphp
        <div class="rk-card" style="padding:14px;border:1px solid color-mix(in srgb,{{ $renk }} 35%,transparent)">
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div style="position:relative;width:44px;height:44px;flex:none">
              <div style="width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--card2)">
                <span class="material-icons-outlined" style="font-size:24px;color:var(--sub2)">{{ $tipIkon($tip) }}</span>
              </div>
              <span style="position:absolute;right:-2px;bottom:-2px;width:13px;height:13px;border-radius:50%;background:{{ $renk }};border:2px solid var(--card)"></span>
            </div>
            <div style="min-width:0;flex:1">
              <div class="rk-ink" style="font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['ad'] }}</div>
              <div class="rk-sub" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['platform'] ?: $tipAd($tip) }}{{ !empty($c['ip']) ? ' · ' . $c['ip'] : '' }}</div>
              @if (!empty($c['rol']) || !empty($c['surum']))
                <div class="rk-sub" style="font-size:11px">{{ !empty($c['rol']) ? ucfirst($c['rol']) : '' }}{{ !empty($c['surum']) ? ' · v' . $c['surum'] : '' }}</div>
              @endif
            </div>
            <div style="text-align:right;flex:none">
              <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 15%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">{{ $online ? 'Bağlı' : 'Kopuk' }}</span>
              <div class="rk-sub" style="font-size:11px;margin-top:4px">{{ $c['son_gorulme'] }}</div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

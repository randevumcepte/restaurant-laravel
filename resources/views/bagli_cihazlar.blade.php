@extends('layout.app')
@section('title', 'Bağlı Cihazlar')
@section('rk_ikon', 'devices_other')
@section('baslik', 'Bağlı Cihazlar')
@section('rk_alt', 'Tanımlı cihazlar (yazıcı/yazarkasa/terminal…) + ResteOS açık cihazlar (çevrimiçi) ve yazarkasa durumu.')

@push('head')
<style>
/* ---- Bağlı Cihazlar renk/bileşen tanımları ---- */
#bc-root{--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gri:var(--sub2)}

/* Özet şerit rozetleri */
#bc-root .bc-rozet{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border-radius:20px;font-size:12.5px;font-weight:700;border:1px solid;white-space:nowrap}

/* Kart + grid */
#bc-root .bc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:12px}

/* Boş kutu */
#bc-root .bc-bos{text-align:center;padding:36px 20px;background:var(--card);border:1px solid var(--line);border-radius:14px}
#bc-root .bc-bos .material-icons-outlined{font-size:48px;color:var(--sub2)}
#bc-root .bc-bos .bas{font-size:15px;font-weight:800;color:var(--ink);margin-top:8px}
#bc-root .bc-bos .alt{font-size:13px;color:var(--sub);margin-top:5px;line-height:1.5}

/* Mini aksiyon butonları (Flutter _miniBtn) */
#bc-root .bc-btn{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:8px 6px;border-radius:9px;font-size:11.5px;font-weight:700;cursor:pointer;border:1px solid;text-align:center;background:none;transition:opacity .15s;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#bc-root .bc-btn:hover{opacity:.78}
#bc-root .bc-btn .material-icons-outlined{font-size:14px;vertical-align:middle}
#bc-root .bc-btn-row{display:flex;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid var(--line)}

/* Canlı cihaz kart border + status dot */
#bc-root .bc-canli-kart{background:var(--card);border-radius:14px;padding:13px;display:flex;align-items:center;gap:12px}

/* Ikon kutu */
#bc-root .bc-ikon-kutu{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none;position:relative}
#bc-root .bc-ikon-kutu .material-icons-outlined{font-size:24px}
#bc-root .bc-dot{position:absolute;right:-2px;bottom:-2px;width:13px;height:13px;border-radius:50%;border:2px solid var(--card)}

/* Test sonuç rozeti */
#bc-root .bc-test-ok{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:700;color:var(--yesil)}
#bc-root .bc-test-fail{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:700;color:var(--kirmizi)}
#bc-root .bc-test-ok .material-icons-outlined,
#bc-root .bc-test-fail .material-icons-outlined{font-size:12px}

/* Sayaç satırı (online / 10sn) */
#bc-root .bc-sayac-satir{display:flex;align-items:center;gap:6px;margin-bottom:8px}
#bc-root .bc-online-dot{width:8px;height:8px;border-radius:50%;background:var(--yesil);display:inline-block;flex:none}

/* Ekle FAB benzeri buton (header araçları) */
#bc-root .bc-ekle-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:24px;background:var(--mor1);color:#fff;font-size:13px;font-weight:700;cursor:pointer;border:none;box-shadow:0 4px 12px rgba(124,58,237,.28);transition:box-shadow .15s}
#bc-root .bc-ekle-btn:hover{box-shadow:0 6px 18px rgba(124,58,237,.38)}
#bc-root .bc-ekle-btn .material-icons-outlined{font-size:20px}

/* Modal overlay */
#bc-modal-arka{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;align-items:flex-end;justify-content:center}
#bc-modal-arka.acik{display:flex}
@media(min-width:640px){#bc-modal-arka{align-items:center}}
#bc-modal{background:var(--card);border-radius:22px 22px 0 0;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;padding:18px 20px 28px;box-shadow:0 -4px 24px rgba(0,0,0,.18)}
@media(min-width:640px){#bc-modal{border-radius:22px;max-height:85vh}}
#bc-modal .bc-tutamac{width:40px;height:4px;background:var(--line);border-radius:2px;margin:0 auto 16px}
#bc-modal .bc-modal-baslik{font-size:18px;font-weight:800;color:var(--ink);margin-bottom:16px}
#bc-modal .bc-lbl{display:block;font-size:12.5px;color:var(--sub2);font-weight:600;margin-bottom:5px}
#bc-modal .bc-sec{margin-bottom:14px}
#bc-modal .bc-row2{display:grid;grid-template-columns:3fr 2fr;gap:10px}

/* Tip seçici chips */
#bc-modal .tip-grid{display:flex;flex-wrap:wrap;gap:8px}
#bc-modal .tip-chip{display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border-radius:10px;border:1px solid var(--line);background:var(--bg);color:var(--sub2);font-size:12px;font-weight:600;cursor:pointer;transition:background .1s,color .1s}
#bc-modal .tip-chip.akt{background:var(--mor1);border-color:var(--mor1);color:#fff}
#bc-modal .tip-chip .material-icons-outlined{font-size:14px}

/* Aktif toggle satırı */
#bc-modal .bc-toggle-satir{display:flex;align-items:center;justify-content:space-between;padding:4px 0;margin-bottom:16px}
#bc-modal .bc-toggle-satir span{font-size:14px;font-weight:600;color:var(--ink)}
#bc-modal .bc-switch{position:relative;display:inline-block;width:46px;height:26px}
#bc-modal .bc-switch input{opacity:0;width:0;height:0}
#bc-modal .bc-switch .sl{position:absolute;cursor:pointer;inset:0;background:#cbd5e1;border-radius:13px;transition:.2s}
#bc-modal .bc-switch .sl:before{position:absolute;content:"";height:20px;width:20px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
#bc-modal .bc-switch input:checked + .sl{background:var(--mor1)}
#bc-modal .bc-switch input:checked + .sl:before{transform:translateX(20px)}

/* Kaydet butonu (büyük) */
#bc-modal .bc-kaydet-btn{width:100%;padding:14px;border-radius:13px;background:var(--mor1);color:#fff;font-size:15px;font-weight:800;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s}
#bc-modal .bc-kaydet-btn:disabled{opacity:.55;cursor:not-allowed}

/* Çevrimiçi eşik küçük yazı */
.bc-esik-not{color:var(--sub);font-size:11px}

/* Yazarkasa kart */
#bc-root .yk-kart{border-radius:16px;padding:16px;background:var(--card)}

/* Toast bildirimi */
#bc-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);padding:11px 22px;border-radius:12px;color:#fff;font-size:13.5px;font-weight:700;z-index:999;display:none;white-space:nowrap;box-shadow:0 4px 18px rgba(0,0,0,.22)}
#bc-toast.ok{background:var(--yesil,#10B981)}
#bc-toast.hata{background:var(--kirmizi,#F43F5E)}
</style>
@endpush

@section('rk_araclar')
<button id="bc-ekle-btn-top" class="bc-ekle-btn" onclick="bcFormAc()" style="display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:24px;background:var(--mor1);color:#fff;font-size:13px;font-weight:700;cursor:pointer;border:none;box-shadow:0 4px 12px rgba(124,58,237,.28)">
  <span class="material-icons-outlined" style="font-size:20px">add</span> Cihaz Ekle
</button>
@endsection

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

  // Yazarkasa durum -> renk + etiket (Flutter _ykRenk eslemesi)
  $ykHarita = [
    'online'   => ['renk' => 'var(--yesil)',   'et' => 'Bağlı'],
    'hata'     => ['renk' => 'var(--kirmizi)', 'et' => 'HATA'],
    'test'     => ['renk' => 'var(--amber)',   'et' => 'Test/Simülasyon'],
    'bekliyor' => ['renk' => 'var(--amber)',   'et' => 'Aktif · fiş yok'],
    'kapali'   => ['renk' => 'var(--gri)',     'et' => 'Kapalı'],
  ];
@endphp

<div id="bc-root">

{{-- Flash bildirimleri --}}
@if (session('bc_mesaj'))
  <div class="rk-flash ok">{{ session('bc_mesaj') }}</div>
@endif
@if (session('bc_hata'))
  <div class="rk-flash err">{{ session('bc_hata') }}</div>
@endif
@if ($hata)
  <div class="rk-flash err">Cihaz bilgileri yüklenemedi. (Tablo henüz oluşmamış olabilir.)</div>
@endif

{{-- ===== ÖZET ŞERİT (Flutter: onlineSayi + tanımlı + toplam) ===== --}}
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:20px">
  <span class="bc-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">
    <span class="bc-online-dot"></span>{{ $onlineSayi }} cihaz çevrimiçi
  </span>
  <span class="bc-rozet" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent);border-color:color-mix(in srgb,var(--mor1) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:15px">power</span> Tanımlı: {{ count($manuel) }}
  </span>
  <span class="bc-rozet" style="color:var(--sub2);background:color-mix(in srgb,var(--sub2) 13%,transparent);border-color:color-mix(in srgb,var(--sub2) 40%,transparent)">
    <span class="material-icons-outlined" style="font-size:15px">devices</span> Toplam: {{ count($cihazlar) }}
  </span>
  <span class="bc-esik-not" style="margin-left:auto">Çevrimiçi eşiği: son {{ $esikSn }} sn</span>
</div>

{{-- ===== TANIMLI CIHAZLAR (manuel) ===== --}}
<div style="margin-bottom:24px">
  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--mor1)"></span>
    <span class="b">🔌 Tanımlı Cihazlar</span>
    <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($manuel) }}</span>
  </div>

  @if (empty($manuel))
    <div class="bc-bos">
      <span class="material-icons-outlined">devices_other</span>
      <div class="bas">Henüz cihaz tanımlanmadı</div>
      <div class="alt">"Cihaz Ekle" ile yazıcı, yazarkasa, terminal vb. ekleyip IP/port girebilirsiniz.</div>
    </div>
  @else
    <div class="bc-grid" id="bc-manuel-grid">
      @foreach ($manuel as $c)
        @php
          $aktif  = !empty($c['aktif']);
          $tip    = $c['tip'] ?? 'diger';
          $renk   = $aktif ? 'var(--yesil)' : 'var(--gri)';
          $ip     = trim((string) ($c['ip'] ?? ''));
          $port   = trim((string) ($c['port'] ?? ''));
          $ipPort = $ip . ($port !== '' ? ':' . $port : '');
        @endphp
        <div class="rk-card" style="padding:13px;display:flex;flex-direction:column" data-id="{{ $c['id'] }}">

          {{-- Üst satır: ikon + bilgi + rozet --}}
          <div style="display:flex;align-items:flex-start;gap:12px">
            <div class="bc-ikon-kutu" style="background:color-mix(in srgb,{{ $renk }} 14%,transparent)">
              <span class="material-icons-outlined" style="color:{{ $renk }}">{{ $tipIkon($tip) }}</span>
            </div>
            <div style="flex:1;min-width:0">
              <div class="rk-ink" style="font-size:14.5px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['ad'] }}</div>
              <div class="rk-sub" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $tipAd($tip) }}{{ !empty($c['marka']) ? ' · ' . $c['marka'] : '' }}
              </div>
              @if ($ip !== '')
                <div style="font-size:11.5px;font-weight:600;color:var(--ink)">IP: {{ $ipPort }}</div>
              @endif
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex:none">
              <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 15%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">
                {{ $aktif ? 'Aktif' : 'Pasif' }}
              </span>
              {{-- Test sonuç yeri (JS tarafından doldurulur) --}}
              <span id="bc-test-{{ $c['id'] }}"></span>
            </div>
          </div>

          {{-- Seri no --}}
          @if (!empty($c['seri_no']))
            <div class="rk-sub" style="font-size:11px;margin-top:8px">Seri No: {{ $c['seri_no'] }}</div>
          @endif

          {{-- Notlar --}}
          @if (!empty($c['notlar']))
            <div class="rk-sub" style="font-size:11.5px;margin-top:8px;line-height:1.3">{{ $c['notlar'] }}</div>
          @endif

          {{-- Aksiyon buton satırı (Flutter _manuelKart butonları) --}}
          <div class="bc-btn-row">
            @if ($ip !== '')
              <button class="bc-btn"
                style="color:#3B82F6;border-color:color-mix(in srgb,#3B82F6 35%,transparent);background:color-mix(in srgb,#3B82F6 10%,transparent)"
                onclick="bcTest('{{ $c['id'] }}','{{ addslashes($ip) }}',{{ (int)($c['port'] ?? 9100) }})">
                <span class="material-icons-outlined">wifi_tethering</span> Test
              </button>
            @endif
            <button class="bc-btn"
              style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 35%,transparent);background:color-mix(in srgb,var(--mor1) 10%,transparent)"
              onclick="bcFormAc({{ json_encode($c) }})">
              <span class="material-icons-outlined">edit_outlined</span> Düzenle
            </button>
            <button class="bc-btn"
              style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 35%,transparent);background:color-mix(in srgb,var(--kirmizi) 10%,transparent)"
              onclick="bcSil({{ $c['id'] }},'{{ addslashes($c['ad']) }}')">
              <span class="material-icons-outlined">delete_outline</span> Sil
            </button>
          </div>

        </div>
      @endforeach
    </div>
  @endif
</div>

{{-- ===== YAZARKASA (OKC durumu) ===== --}}
@if (!empty($yazarkasa))
  @php
    $durum   = $yazarkasa['durum'] ?? 'kapali';
    $yk      = $ykHarita[$durum] ?? $ykHarita['kapali'];
    $ykRenk  = $yk['renk'];
    $ykAktif = !empty($yazarkasa['aktif']);
  @endphp
  <div style="margin-bottom:24px">
    <div class="rk-bolum">
      <span class="cubuk" style="background:{{ $ykRenk }}"></span>
      <span class="b">🧾 Yazarkasa (ÖKC)</span>
    </div>
    <div class="yk-kart" style="border:1px solid color-mix(in srgb,{{ $ykRenk }} 40%,transparent)">
      <div style="display:flex;align-items:flex-start;gap:12px">
        <div class="bc-ikon-kutu" style="background:color-mix(in srgb,{{ $ykRenk }} 15%,transparent)">
          <span class="material-icons-outlined" style="color:{{ $ykRenk }}">point_of_sale</span>
          <span class="bc-dot" style="background:{{ $ykRenk }}"></span>
        </div>
        <div style="flex:1;min-width:0">
          <div class="rk-ink" style="font-size:14px;font-weight:800">
            Yazarkasa{{ !empty($yazarkasa['marka']) ? ' · ' . $yazarkasa['marka'] : '' }}
          </div>
          @if ($ykAktif && !empty($yazarkasa['ip']))
            <div class="rk-sub" style="font-size:11.5px">
              IP: {{ $yazarkasa['ip'] }}{{ !empty($yazarkasa['port']) ? ':' . $yazarkasa['port'] : '' }}
            </div>
          @endif
        </div>
        <span class="rk-rozet" style="color:{{ $ykRenk }};background:color-mix(in srgb,{{ $ykRenk }} 15%,transparent);border-color:color-mix(in srgb,{{ $ykRenk }} 40%,transparent)">
          {{ $yk['et'] }}
        </span>
      </div>
      @if (!empty($yazarkasa['mesaj']))
        <div style="font-size:12.5px;margin-top:10px;line-height:1.3;color:var(--sub2)">{{ $yazarkasa['mesaj'] }}</div>
      @endif
      @if (!empty($yazarkasa['son_fis']))
        <div class="rk-sub" style="font-size:11px;margin-top:4px">Son mali fiş: {{ $yazarkasa['son_fis'] }}</div>
      @endif
    </div>
  </div>
@endif

{{-- ===== ÇEVRİMİÇİ (heartbeat) CIHAZLAR ===== --}}
<div>
  {{-- Sayaç satırı (Flutter Row: dot + "X cihaz çevrimiçi" + "10 sn'de bir yenilenir") --}}
  <div class="bc-sayac-satir">
    <span class="bc-online-dot"></span>
    <span id="bc-online-sayi" class="rk-sub" style="font-size:12px">{{ $onlineSayi }} cihaz çevrimiçi</span>
    <span style="margin-left:auto" class="bc-esik-not">10 sn'de bir yenilenir</span>
  </div>

  <div class="rk-bolum">
    <span class="cubuk" style="background:var(--yesil)"></span>
    <span class="b">📱 Çevrimiçi Cihazlar (ResteOS)</span>
    <span class="sayi" id="bc-canli-sayi" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent)">{{ count($canli) }}</span>
  </div>

  @if (empty($canli))
    <div class="bc-bos" id="bc-canli-bos">
      <span class="material-icons-outlined">wifi_off</span>
      <div class="bas">Sinyal gönderen cihaz yok</div>
      <div class="alt">Bir cihazda ResteOS açık olduğunda burada görünür.</div>
    </div>
    <div id="bc-canli-grid" style="display:none" class="bc-grid"></div>
  @else
    <div class="bc-bos" id="bc-canli-bos" style="display:none">
      <span class="material-icons-outlined">wifi_off</span>
      <div class="bas">Sinyal gönderen cihaz yok</div>
      <div class="alt">Bir cihazda ResteOS açık olduğunda burada görünür.</div>
    </div>
    <div id="bc-canli-grid" class="bc-grid">
      @foreach ($canli as $c)
        @php
          $online = !empty($c['online']);
          $tip    = $c['tip'] ?? 'diger';
          $renk   = $online ? 'var(--yesil)' : 'var(--kirmizi)';
        @endphp
        <div class="bc-canli-kart" style="border:1px solid color-mix(in srgb,{{ $renk }} 35%,transparent)">
          <div class="bc-ikon-kutu" style="background:var(--card2)">
            <span class="material-icons-outlined" style="color:var(--sub2)">{{ $tipIkon($tip) }}</span>
            <span class="bc-dot" style="background:{{ $renk }}"></span>
          </div>
          <div style="flex:1;min-width:0">
            <div class="rk-ink" style="font-size:14px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['ad'] }}</div>
            <div class="rk-sub" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              {{ $c['platform'] ?: $tipAd($tip) }}{{ !empty($c['ip']) ? ' · ' . $c['ip'] : '' }}
            </div>
            @if (!empty($c['rol']) || !empty($c['surum']))
              <div class="rk-sub" style="font-size:11px">
                {{ !empty($c['rol']) ? ucfirst($c['rol']) : '' }}{{ !empty($c['surum']) ? ' · v' . $c['surum'] : '' }}
              </div>
            @endif
          </div>
          <div style="text-align:right;flex:none">
            <span class="rk-rozet" style="color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 15%,transparent);border-color:color-mix(in srgb,{{ $renk }} 40%,transparent)">
              {{ $online ? 'Bağlı' : 'Kopuk' }}
            </span>
            <div class="rk-sub" style="font-size:11px;margin-top:4px">{{ $c['son_gorulme'] }}</div>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

</div>{{-- /#bc-root --}}

{{-- ===== CIHAZ EKLE / DÜZENLE MODAL (Flutter _formAc birebir) ===== --}}
<div id="bc-modal-arka" onclick="if(event.target===this)bcModalKapat()">
  <div id="bc-modal">
    <div class="bc-tutamac"></div>
    <div class="bc-modal-baslik" id="bc-modal-baslik">Cihaz Ekle</div>

    {{-- Tip seçici --}}
    <div class="bc-sec">
      <label class="bc-lbl">Cihaz Türü</label>
      <div class="tip-grid" id="bc-tip-grid">
        @foreach ($tipler as $k => $t)
          <div class="tip-chip{{ $k === 'yazici' ? ' akt' : '' }}" data-tip="{{ $k }}" onclick="bcTipSec(this)">
            <span class="material-icons-outlined">{{ $t['ikon'] }}</span>{{ $t['ad'] }}
          </div>
        @endforeach
      </div>
    </div>

    {{-- Cihaz adı --}}
    <div class="bc-sec">
      <label class="bc-lbl" for="bc-ad">Cihaz Adı</label>
      <input id="bc-ad" class="rk-inp" type="text" maxlength="80" placeholder="örn. Mutfak Yazıcısı">
    </div>

    {{-- IP + Port --}}
    <div class="bc-row2 bc-sec" style="margin-bottom:0">
      <div>
        <label class="bc-lbl" for="bc-ip">IP Adresi</label>
        <input id="bc-ip" class="rk-inp" type="text" maxlength="60" placeholder="192.168.1.50">
      </div>
      <div>
        <label class="bc-lbl" for="bc-port">Port</label>
        <input id="bc-port" class="rk-inp" type="number" min="1" max="65535" placeholder="9100">
      </div>
    </div>

    {{-- Marka + Seri No --}}
    <div class="bc-row2 bc-sec" style="margin-top:14px;margin-bottom:0">
      <div>
        <label class="bc-lbl" for="bc-marka">Marka/Model</label>
        <input id="bc-marka" class="rk-inp" type="text" maxlength="60" placeholder="opsiyonel">
      </div>
      <div>
        <label class="bc-lbl" for="bc-seri">Seri No</label>
        <input id="bc-seri" class="rk-inp" type="text" maxlength="60" placeholder="opsiyonel">
      </div>
    </div>

    {{-- Not --}}
    <div class="bc-sec" style="margin-top:14px">
      <label class="bc-lbl" for="bc-not">Not</label>
      <textarea id="bc-not" class="rk-inp" rows="2" maxlength="500" placeholder="opsiyonel açıklama"></textarea>
    </div>

    {{-- Aktif toggle --}}
    <div class="bc-toggle-satir">
      <span>Aktif (kullanımda)</span>
      <label class="bc-switch">
        <input type="checkbox" id="bc-aktif" checked>
        <span class="sl"></span>
      </label>
    </div>

    <input type="hidden" id="bc-id" value="">

    <button class="bc-kaydet-btn" id="bc-kaydet-btn" onclick="bcKaydet()">
      <span class="material-icons-outlined" style="font-size:20px">save</span>
      <span id="bc-kaydet-txt">Ekle</span>
    </button>
  </div>
</div>

{{-- Toast --}}
<div id="bc-toast"></div>

@push('scripts')
<script>
(function(){
  'use strict';

  var _seciliTip = 'yazici';
  var _otoInterval = null;

  /* ---- TOAST ---- */
  function toast(mesaj, tip) {
    var el = document.getElementById('bc-toast');
    el.textContent = mesaj;
    el.className = tip === 'hata' ? 'hata' : 'ok';
    el.style.display = 'block';
    clearTimeout(el._t);
    el._t = setTimeout(function(){ el.style.display = 'none'; }, tip === 'hata' ? 4000 : 2200);
  }

  /* ---- TİP SEÇ ---- */
  window.bcTipSec = function(el) {
    document.querySelectorAll('#bc-tip-grid .tip-chip').forEach(function(c){ c.classList.remove('akt'); });
    el.classList.add('akt');
    _seciliTip = el.getAttribute('data-tip');
  };

  /* ---- MODAL AÇ / KAPAT ---- */
  window.bcFormAc = function(mevcut) {
    var modal = document.getElementById('bc-modal-arka');
    var baslik = document.getElementById('bc-modal-baslik');
    var kaydetTxt = document.getElementById('bc-kaydet-txt');

    // Alanları sıfırla
    document.getElementById('bc-id').value   = mevcut ? mevcut.id : '';
    document.getElementById('bc-ad').value   = mevcut ? (mevcut.ad || '') : '';
    document.getElementById('bc-ip').value   = mevcut ? (mevcut.ip || '') : '';
    document.getElementById('bc-port').value = mevcut ? (mevcut.port || '') : '';
    document.getElementById('bc-marka').value= mevcut ? (mevcut.marka || '') : '';
    document.getElementById('bc-seri').value = mevcut ? (mevcut.seri_no || '') : '';
    document.getElementById('bc-not').value  = mevcut ? (mevcut.notlar || '') : '';
    document.getElementById('bc-aktif').checked = mevcut ? !!mevcut.aktif : true;

    // Tip chip seç
    var tip = mevcut ? (mevcut.tip || 'diger') : 'yazici';
    _seciliTip = tip;
    document.querySelectorAll('#bc-tip-grid .tip-chip').forEach(function(c){
      c.classList.toggle('akt', c.getAttribute('data-tip') === tip);
    });

    baslik.textContent  = mevcut ? 'Cihazı Düzenle' : 'Cihaz Ekle';
    kaydetTxt.textContent = mevcut ? 'Kaydet' : 'Ekle';
    modal.classList.add('acik');
    setTimeout(function(){ document.getElementById('bc-ad').focus(); }, 120);
  };

  window.bcModalKapat = function() {
    document.getElementById('bc-modal-arka').classList.remove('acik');
  };

  /* ---- KAYDET ----
     id varsa → mevcut renaming için /bagli-cihazlar/ad (form POST)
     id yoksa (yeni) + tam bilgi → form submit gizli form üzerinden /bagli-cihazlar/ekle
     Not: routes/web.php'de /bagli-cihazlar/ekle yoksa sayfa yenilenir (eksik rota notu).
  ---- */
  window.bcKaydet = function() {
    var ad = document.getElementById('bc-ad').value.trim();
    if (!ad) { toast('Cihaz adı gerekli', 'hata'); document.getElementById('bc-ad').focus(); return; }

    var id    = document.getElementById('bc-id').value.trim();
    var ip    = document.getElementById('bc-ip').value.trim();
    var port  = document.getElementById('bc-port').value.trim();
    var marka = document.getElementById('bc-marka').value.trim();
    var seri  = document.getElementById('bc-seri').value.trim();
    var notlar= document.getElementById('bc-not').value.trim();
    var aktif = document.getElementById('bc-aktif').checked ? '1' : '0';

    /* Gizli form oluştur ve gönder — CSRF otomatik eklenir */
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    var fields;
    if (id) {
      /* Mevcut cihaz: ad + tam bilgi → /bagli-cihazlar/ad (mevcut rota) */
      form.action = '/bagli-cihazlar/ad';
      fields = { id: id, ad: ad, tip: _seciliTip, ip: ip, port: port, marka: marka, seri_no: seri, notlar: notlar, aktif: aktif };
    } else {
      /* Yeni cihaz → /bagli-cihazlar/ekle (web.php'ye eklenmesi gereken rota — bkz. eksik rota notu) */
      form.action = '/bagli-cihazlar/ekle';
      fields = { ad: ad, tip: _seciliTip, ip: ip, port: port, marka: marka, seri_no: seri, notlar: notlar, aktif: aktif };
    }

    /* CSRF token */
    var csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = window.csrf;
    form.appendChild(csrf);

    Object.keys(fields).forEach(function(k) {
      var inp = document.createElement('input');
      inp.type = 'hidden'; inp.name = k; inp.value = fields[k];
      form.appendChild(inp);
    });

    document.body.appendChild(form);
    form.submit();
  };

  /* ---- SİL — form POST (redirect tabanlı mevcut rota) ---- */
  window.bcSil = function(id, ad) {
    if (!confirm('"' + ad + '" silinsin mi?')) return;
    var form = document.createElement('form');
    form.method = 'POST'; form.action = '/bagli-cihazlar/sil'; form.style.display = 'none';
    var csrf = document.createElement('input');
    csrf.type='hidden'; csrf.name='_token'; csrf.value=window.csrf;
    form.appendChild(csrf);
    var inp = document.createElement('input');
    inp.type='hidden'; inp.name='id'; inp.value=id;
    form.appendChild(inp);
    document.body.appendChild(form);
    form.submit();
  };

  /* ---- BAĞLANTI TESTİ (LAN — sadece web tarayıcısında açık port çeklemeye yakın deneme: img/fetch) ---- */
  window.bcTest = function(id, ip, port) {
    var el = document.getElementById('bc-test-' + id);
    el.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border:2px solid #e2e8f0;border-top-color:var(--mor1);border-radius:50%;animation:bc-spin .7s linear infinite"></span>';
    // Tarayıcıdan LAN TCP testi: Image ping (en geniş uyumluluk; CORS'suz; RST=hızlı hata ~ bağlı, timeout=ulaşılamıyor)
    var img = new Image();
    var bitti = false;
    var bitis = function(ok) {
      if (bitti) return; bitti = true;
      el.innerHTML = ok
        ? '<span class="bc-test-ok"><span class="material-icons-outlined">check_circle</span>Ulaşıldı</span>'
        : '<span class="bc-test-fail"><span class="material-icons-outlined">cancel</span>Ulaşılamadı</span>';
    };
    img.onload  = function(){ bitis(true); };
    img.onerror = function(){ bitis(true); }; // RST=hata ama ulaşıldı demek
    setTimeout(function(){ bitis(false); }, 4000);
    img.src = 'http://' + ip + ':' + port + '/?_bc=' + Date.now();
  };

  /* ---- OTO YENİLE (10 sn) — Flutter Timer.periodic(10sn, _yukle) karşılığı ---- */
  /* Sunucu GET rotası JSON döndürmediği için sayfayı sessizce yeniler (modal açıksa bekler) */
  function _doYukle() {
    var modalAcik = document.getElementById('bc-modal-arka').classList.contains('acik');
    if (!modalAcik) location.reload();
  }

  /* CSS animasyon (spinner) */
  var sty = document.createElement('style');
  sty.textContent = '@keyframes bc-spin{to{transform:rotate(360deg)}}';
  document.head.appendChild(sty);

  /* Oto yenile başlat */
  _otoInterval = setInterval(_doYukle, 10000);

})();
</script>
@endpush

@endsection

@extends('layout.app')
@section('rk_ikon', 'badge')
@section('baslik', 'Personel & Maaş')
@section('title', 'Personel & Maaş')

@section('content')
@php
  // ------------------------------------------------------------------
  // Flutter personel_screen.dart + personel_yetkileri_screen.dart BİREBİR.
  // Rol etiket + renk (Flutter _rolAd / _rolRenk ile aynı).
  // ------------------------------------------------------------------
  $rolAd = ['sahip' => 'Sahip', 'mudur' => 'Müdür', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak'];
  $rolRenk = [
    'sahip'  => 'var(--mor1)',
    'mudur'  => 'var(--mavi)',
    'kasa'   => 'var(--amber)',
    'garson' => 'var(--yesil)',
    'mutfak' => '#D97706',
  ];
  $renkAl = fn ($rol) => $rolRenk[$rol] ?? '#64748B';

  // Yetki etiketleri — Flutter _etiketler ile birebir.
  $etiketler = [
    'adisyon_ac' => 'Adisyon Aç',
    'adisyon_kapat' => 'Ödeme Al / Masa Kapat',
    'adisyon_iptal' => 'Adisyon İptal',
    'adisyon_bol' => 'Adisyon Böl',
    'adisyon_birlestir' => 'Masa Birleştir',
    'masa_tasima' => 'Masa Taşıma',
    'iskonto' => 'İskonto Uygula',
    'ikram' => 'İkram',
    'urun_sil' => 'Ürün Sil (Void)',
    'fatura_kes' => 'Fatura Kes',
    'geri_islem' => 'Geriye Dönük İşlem',
    'maliyet_gor' => 'Maliyet Görebilir',
    'rapor_gor' => 'Rapor Görebilir',
  ];
  // Flutter _gruplar — material-icons-outlined karşılıkları (emoji yerine).
  $gruplar = [
    'Adisyon'      => ['ikon' => 'receipt_long', 'keys' => ['adisyon_ac', 'adisyon_kapat', 'adisyon_iptal', 'adisyon_bol', 'adisyon_birlestir', 'masa_tasima']],
    'Para & Kayıp' => ['ikon' => 'payments',     'keys' => ['iskonto', 'ikram', 'urun_sil', 'fatura_kes']],
    'Yönetim'      => ['ikon' => 'insights',      'keys' => ['geri_islem', 'maliyet_gor', 'rapor_gor']],
  ];

  $personeller = $personeller ?? [];
  $duzenleyebilir = $duzenleyebilir ?? true;

  // Flutter masaüstü: role göre grupla, sabit sırayla göster.
  $rolGrup = [
    'sahip' => 'Yöneticiler', 'mudur' => 'Yöneticiler',
    'garson' => 'Garsonlar',
    'mutfak' => 'Mutfak / Ustalar', 'usta' => 'Mutfak / Ustalar',
    'kasa' => 'Kasiyerler',
  ];
  $grupSira = ['Yöneticiler', 'Garsonlar', 'Mutfak / Ustalar', 'Kasiyerler', 'Diğer'];
  $grupRenk = [
    'Yöneticiler' => 'var(--mor1)', 'Garsonlar' => 'var(--yesil)',
    'Mutfak / Ustalar' => '#D97706', 'Kasiyerler' => 'var(--mavi)', 'Diğer' => '#64748B',
  ];

  $siraliGruplar = [];
  foreach ($personeller as $p) {
    $g = $rolGrup[$p['rol'] ?? 'garson'] ?? 'Diğer';
    $siraliGruplar[$g][] = $p;
  }
  // Grup içinde aktifler önce.
  foreach ($siraliGruplar as &$liste) {
    usort($liste, fn ($a, $b) => (int)($b['aktif'] ?? 0) <=> (int)($a['aktif'] ?? 0));
  }
  unset($liste);

  $aktifSayi = count(array_filter($personeller, fn ($p) => (int)($p['aktif'] ?? 0) === 1));
@endphp

{{-- Flash (session ok / hata) --}}
@if (session('ok'))
  <div class="rk-flash ok">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="rk-flash err">{{ session('hata') }}</div>
@endif

{{-- Üst şerit: özet rozetler + Personel Ekle --}}
<div class="rk-bolum" style="justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <div style="display:inline-flex;gap:8px;flex-wrap:wrap">
    <span class="rk-rozet" style="color:var(--mor1);border-color:color-mix(in srgb,var(--mor1) 45%,transparent);background:color-mix(in srgb,var(--mor1) 12%,transparent)">
      <span class="material-icons-outlined" style="font-size:16px">groups</span>{{ count($personeller) }} personel
    </span>
    <span class="rk-rozet" style="color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 45%,transparent);background:color-mix(in srgb,var(--yesil) 12%,transparent)">
      <span class="material-icons-outlined" style="font-size:16px">check_circle</span>{{ $aktifSayi }} aktif
    </span>
  </div>
  @if ($duzenleyebilir)
    <button type="button" class="rk-btn" style="background:var(--mor1)" onclick="personelAc(null)">
      <span class="material-icons-outlined" style="font-size:18px">person_add_alt</span> Personel Ekle
    </button>
  @endif
</div>

@if (!$duzenleyebilir)
  <div class="rk-flash err" style="font-weight:600">
    Personel ve yetkileri yalnızca işletme sahibi düzenleyebilir. Görüntülüyorsunuz.
  </div>
@endif

@if (empty($personeller))
  <div class="rk-card" style="text-align:center;padding:38px">
    <span class="rk-sub">Henüz personel yok.</span>
  </div>
@else
  {{-- Role göre gruplu kart ızgarası (Flutter _masaustuGovde) --}}
  @foreach ($grupSira as $grup)
    @continue (empty($siraliGruplar[$grup]))
    @php $gRenk = $grupRenk[$grup] ?? '#64748B'; @endphp
    <div class="rk-bolum">
      <span class="cubuk" style="background:{{ $gRenk }}"></span>
      <span class="b">{{ $grup }}</span>
      <span class="sayi" style="color:{{ $gRenk }};background:color-mix(in srgb,{{ $gRenk }} 13%,transparent)">{{ count($siraliGruplar[$grup]) }}</span>
    </div>

    <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(330px,1fr));margin-bottom:22px">
      @foreach ($siraliGruplar[$grup] as $p)
        @php
          $rol = $p['rol'] ?? 'garson';
          $renk = $renkAl($rol);
          $aktif = (int)($p['aktif'] ?? 0) === 1;
          $y = (array)($p['yetkiler'] ?? []);
          $acik = count(array_filter($y, fn ($v) => $v === true));
          $harf = mb_strtoupper(mb_substr($p['ad'] ?? '?', 0, 1));
        @endphp
        <div class="rk-card" style="padding:16px;{{ $aktif ? '' : 'opacity:.55' }}">
          {{-- Avatar + ad + rol rozeti --}}
          <div style="display:flex;align-items:center;gap:12px">
            <div style="width:48px;height:48px;border-radius:50%;flex:0 0 48px;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:20px;color:{{ $renk }};background:color-mix(in srgb,{{ $renk }} 18%,transparent)">{{ $harf }}</div>
            <div style="min-width:0;flex:1">
              <div style="font-weight:800;font-size:15px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $p['ad'] ?? '—' }}</div>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;flex-wrap:wrap">
                <span class="rk-rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 45%,transparent);background:color-mix(in srgb,{{ $renk }} 16%,transparent)">{{ $rolAd[$rol] ?? $rol }}</span>
                @if (!$aktif)
                  <span class="rk-rozet" style="color:var(--sub);border-color:var(--line);background:var(--card2)">Pasif</span>
                @endif
                @if (!empty($p['telefon']))
                  <span class="rk-sub" style="font-size:12px">{{ $p['telefon'] }}</span>
                @endif
              </div>
            </div>
          </div>

          {{-- PIN + yetki sayısı --}}
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:14px 0">
            <div style="background:var(--card2);border-radius:10px;padding:9px;text-align:center">
              <div style="font-weight:800;font-size:15px;color:var(--ink);letter-spacing:1px">{{ !empty($p['pin']) ? $p['pin'] : '—' }}</div>
              <div class="rk-sub" style="font-size:10px;margin-top:2px">PIN</div>
            </div>
            <div style="background:var(--card2);border-radius:10px;padding:9px;text-align:center">
              <div style="font-weight:800;font-size:15px;color:var(--ink)">{{ $acik }}/{{ count($etiketler) }}</div>
              <div class="rk-sub" style="font-size:10px;margin-top:2px">Yetki</div>
            </div>
          </div>

          {{-- Yetki rozetleri (açık olanlar) --}}
          <div style="display:flex;flex-wrap:wrap;gap:6px;min-height:22px;margin-bottom:14px">
            @foreach ($etiketler as $k => $et)
              @if (!empty($y[$k]))
                <span class="rk-rozet" style="color:var(--mavi);border-color:color-mix(in srgb,var(--mavi) 35%,transparent);background:color-mix(in srgb,var(--mavi) 10%,transparent);font-size:10px;padding:3px 7px">{{ $et }}</span>
              @endif
            @endforeach
            @if ($acik === 0)
              <span class="rk-sub" style="font-size:11px">Yetki tanımlı değil</span>
            @endif
            @if (!empty($y['iskonto']))
              <span class="rk-rozet" style="color:var(--amber);border-color:color-mix(in srgb,var(--amber) 40%,transparent);background:color-mix(in srgb,var(--amber) 12%,transparent);font-size:10px;padding:3px 7px">iskonto %{{ (int)round($p['iskonto_limit'] ?? 0) }}</span>
            @endif
            @if (!empty($y['ikram']))
              <span class="rk-rozet" style="color:var(--amber);border-color:color-mix(in srgb,var(--amber) 40%,transparent);background:color-mix(in srgb,var(--amber) 12%,transparent);font-size:10px;padding:3px 7px">ikram {{ (int)round($p['ikram_limit'] ?? 0) }}₺</span>
            @endif
          </div>

          @if ($duzenleyebilir)
            <div style="display:flex;align-items:center;gap:8px">
              <button type="button" class="rk-btn bos" style="flex:1;justify-content:center" onclick='personelAc(@json($p))'>
                <span class="material-icons-outlined" style="font-size:16px">edit</span> Düzenle
              </button>
              <button type="button" class="rk-btn" style="flex:1;justify-content:center;background:var(--mavi)" onclick='yetkiAc(@json($p))'>
                <span class="material-icons-outlined" style="font-size:16px">shield</span> Yetkiler
              </button>
              {{-- Aktif/Pasif toggle --}}
              <form method="POST" action="/personel/kaydet" style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $p['id'] }}">
                <input type="hidden" name="ad" value="{{ $p['ad'] ?? '' }}">
                <input type="hidden" name="telefon" value="{{ $p['telefon'] ?? '' }}">
                <input type="hidden" name="rol" value="{{ $rol }}">
                <input type="hidden" name="pin" value="{{ $p['pin'] ?? '' }}">
                <input type="hidden" name="aktif" value="{{ $aktif ? '0' : '1' }}">
                <button type="submit" class="rk-btn {{ $aktif ? '' : 'bos' }}" style="{{ $aktif ? 'background:var(--yesil)' : '' }}" title="{{ $aktif ? 'Pasife al' : 'Aktif et' }}">
                  <span class="material-icons-outlined" style="font-size:16px">{{ $aktif ? 'toggle_on' : 'toggle_off' }}</span>
                </button>
              </form>
              {{-- Sil --}}
              <form method="POST" action="/personel/sil" style="display:inline" onsubmit="return confirm('{{ addslashes($p['ad'] ?? '') }} silinsin mi?')">
                @csrf
                <input type="hidden" name="id" value="{{ $p['id'] }}">
                <button type="submit" class="rk-btn" style="background:var(--kirmizi)" title="Sil">
                  <span class="material-icons-outlined" style="font-size:16px">delete_outline</span>
                </button>
              </form>
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach
@endif

@if ($duzenleyebilir)
{{-- ==================== PERSONEL EKLE / DÜZENLE MODAL ==================== --}}
<div id="personelModal" class="rk-modal-arka" onclick="if(event.target===this)personelKapat()">
  <div class="rk-modal">
    <form method="POST" action="/personel/kaydet">
      @csrf
      <input type="hidden" name="id" id="f_id" value="">
      <div class="rk-modal-head">
        <span class="b" id="personelModalBaslik">Yeni Personel</span>
        <button type="button" class="rk-modal-kapa" onclick="personelKapat()">&times;</button>
      </div>
      <div class="rk-modal-govde">
        <label class="rk-lbl">Ad Soyad</label>
        <input type="text" name="ad" id="f_ad" required class="rk-inp" placeholder="Ad Soyad">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="rk-lbl">Telefon</label>
            <input type="text" name="telefon" id="f_telefon" class="rk-inp" inputmode="tel">
          </div>
          <div>
            <label class="rk-lbl">PIN (en az 4 hane)</label>
            <input type="text" name="pin" id="f_pin" inputmode="numeric" maxlength="8" class="rk-inp" placeholder="Boş = değişmez">
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="rk-lbl">Rol</label>
            <select name="rol" id="f_rol" class="rk-inp">
              @foreach ($rolAd as $k => $ad)
                <option value="{{ $k }}">{{ $ad }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="rk-lbl">Durum</label>
            <select name="aktif" id="f_aktif" class="rk-inp">
              <option value="1">Aktif</option>
              <option value="0">Pasif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="rk-modal-foot">
        <button type="button" class="rk-btn bos" onclick="personelKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Kaydet</button>
      </div>
    </form>
  </div>
</div>

{{-- ==================== YETKİ DÜZENLE MODAL ==================== --}}
<div id="yetkiModal" class="rk-modal-arka" onclick="if(event.target===this)yetkiKapat()">
  <div class="rk-modal">
    <form method="POST" action="/personel/yetki">
      @csrf
      <input type="hidden" name="id" id="y_id" value="">
      <div class="rk-modal-head">
        <span class="b"><span id="yetkiModalAd">Personel</span> — Yetkiler</span>
        <button type="button" class="rk-modal-kapa" onclick="yetkiKapat()">&times;</button>
      </div>
      <div class="rk-modal-govde">
        @foreach ($gruplar as $baslik => $g)
          <div class="rk-bolum" style="margin:2px 0 8px">
            <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">{{ $g['ikon'] }}</span>
            <span class="b" style="font-size:13.5px;color:var(--mor1)">{{ $baslik }}</span>
          </div>
          <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:14px">
            @foreach ($g['keys'] as $k)
              @if (isset($etiketler[$k]))
                <label class="rk-yetki-satir">
                  <input type="checkbox" name="yetki_{{ $k }}" data-yetki="{{ $k }}" class="rk-chk"
                         @if ($k === 'iskonto' || $k === 'ikram') onchange="yetkiLimitGoster()" @endif>
                  <span>{{ $etiketler[$k] }}</span>
                </label>
                @if ($k === 'iskonto')
                  <div id="y_iskonto_satir" class="rk-yetki-limit" style="display:none">
                    <span class="rk-sub" style="font-size:12px">İskonto limiti</span>
                    <input type="number" name="iskonto_limit" id="y_iskonto_limit" min="0" max="100" step="1" class="rk-inp" style="width:80px;padding:6px 10px">
                    <span class="rk-sub" style="font-size:12px">% (üstü yönetici onayı)</span>
                  </div>
                @endif
                @if ($k === 'ikram')
                  <div id="y_ikram_satir" class="rk-yetki-limit" style="display:none">
                    <span class="rk-sub" style="font-size:12px">İkram limiti</span>
                    <input type="number" name="ikram_limit" id="y_ikram_limit" min="0" step="1" class="rk-inp" style="width:100px;padding:6px 10px">
                    <span class="rk-sub" style="font-size:12px">₺ (üstü yönetici onayı)</span>
                  </div>
                @endif
              @endif
            @endforeach
          </div>
        @endforeach
      </div>
      <div class="rk-modal-foot">
        <button type="button" class="rk-btn bos" onclick="yetkiKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Yetkileri Kaydet</button>
      </div>
    </form>
  </div>
</div>

@push('head')
<style>
  .rk-modal-arka{position:fixed;inset:0;z-index:50;background:rgba(15,23,42,.55);display:none;align-items:flex-start;justify-content:center;padding:28px 16px;overflow-y:auto}
  .rk-modal-arka.acik{display:flex}
  .rk-modal{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 20px 60px rgba(15,23,42,.35);width:100%;max-width:520px;margin:auto 0}
  .rk-modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--line)}
  .rk-modal-head .b{font-size:16px;font-weight:800;color:var(--ink)}
  .rk-modal-kapa{background:none;border:none;font-size:26px;line-height:1;color:var(--sub);cursor:pointer}
  .rk-modal-kapa:hover{color:var(--ink)}
  .rk-modal-govde{padding:16px 20px}
  .rk-modal-foot{padding:14px 20px;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:10px}
  .rk-yetki-satir{display:flex;align-items:center;gap:10px;padding:9px 2px;cursor:pointer;color:var(--ink);font-size:14px}
  .rk-chk{width:18px;height:18px;accent-color:var(--mor1);cursor:pointer}
  .rk-yetki-limit{display:flex;align-items:center;gap:8px;padding:0 0 10px 28px;flex-wrap:wrap}
</style>
@endpush

@push('scripts')
<script>
  function personelAc(p) {
    document.getElementById('personelModalBaslik').textContent = p ? 'Personeli Düzenle' : 'Yeni Personel';
    document.getElementById('f_id').value = p ? p.id : '';
    document.getElementById('f_ad').value = p ? (p.ad || '') : '';
    document.getElementById('f_telefon').value = p ? (p.telefon || '') : '';
    document.getElementById('f_pin').value = p ? (p.pin || '') : '';
    document.getElementById('f_rol').value = p ? (p.rol || 'garson') : 'garson';
    document.getElementById('f_aktif').value = p ? String(p.aktif) : '1';
    document.getElementById('personelModal').classList.add('acik');
  }
  function personelKapat() { document.getElementById('personelModal').classList.remove('acik'); }

  function yetkiAc(p) {
    document.getElementById('y_id').value = p.id;
    document.getElementById('yetkiModalAd').textContent = p.ad || 'Personel';
    var y = p.yetkiler || {};
    document.querySelectorAll('#yetkiModal input[data-yetki]').forEach(function (el) {
      el.checked = y[el.dataset.yetki] === true;
    });
    document.getElementById('y_iskonto_limit').value = Math.round(p.iskonto_limit || 0);
    document.getElementById('y_ikram_limit').value = Math.round(p.ikram_limit || 0);
    yetkiLimitGoster();
    document.getElementById('yetkiModal').classList.add('acik');
  }
  function yetkiKapat() { document.getElementById('yetkiModal').classList.remove('acik'); }

  // Flutter: iskonto/ikram açıkken limit alanı görünür.
  function yetkiLimitGoster() {
    var isk = document.querySelector('#yetkiModal input[data-yetki="iskonto"]');
    var ikr = document.querySelector('#yetkiModal input[data-yetki="ikram"]');
    document.getElementById('y_iskonto_satir').style.display = (isk && isk.checked) ? 'flex' : 'none';
    document.getElementById('y_ikram_satir').style.display = (ikr && ikr.checked) ? 'flex' : 'none';
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { personelKapat(); yetkiKapat(); }
  });
</script>
@endpush
@endif
@endsection

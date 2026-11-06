@extends('layout.app')
@section('rk_ikon', 'account_circle')
@section('baslik', 'Profilim')
@section('title', 'Profilim')

@php
    $rolAd = ['sahip'=>'Sahip','mudur'=>'Müdür','kasa'=>'Kasa','garson'=>'Garson','mutfak'=>'Mutfak','satinalmaci'=>'Satın Almacı','satinalma'=>'Satın Almacı'][$p->rol ?? ''] ?? ($p->rol ?? 'Personel');
    $kalanIzin = max(0, (float)$hak - (float)$kullanilan);
    $gunBicim = fn($g) => rtrim(rtrim(number_format((float)$g, 1, ',', ''), '0'), ',') ?: '0';
    $sureBicim = function ($dk) {
        $dk = (int) $dk; $s = intdiv($dk, 60); $m = $dk % 60;
        return $s > 0 ? "{$s}s {$m}dk" : "{$m}dk";
    };
    $para = fn($v) => '₺' . number_format((float)$v, 0, ',', '.');
    $zamanTam = fn($z) => $z ? \Carbon\Carbon::parse($z)->format('d.m.Y H:i') : '—';
    $zamanKisa = function ($z) {
        if (!$z) return '—';
        $c = \Carbon\Carbon::parse($z);
        return $c->isToday() ? $c->format('H:i') : $c->format('d.m H:i');
    };
    $tarihBicim = fn($t) => $t ? \Carbon\Carbon::parse($t)->format('d.m.Y') : '—';
    $rol = $p->rol ?? '';
    $isGarson = in_array($rol, ['garson','sahip','mudur']);
    $isPatron = in_array($rol, ['sahip','mudur']);
    $bugunToplam = ($bugun['ciro'] ?? 0) + ($bugun['masa'] ?? 0) + ($bugun['cagri'] ?? 0) + ($bugun['bahsis'] ?? 0);
    $fotoVar = !empty($p->foto) && str_starts_with((string)$p->foto, 'data:image');
    $fotoUrl = $fotoVar ? '/profilim-avatar?v=' . crc32($p->foto) : null;
    $alanMetin = $atamaOzet ? ($atamaOzet['bolge'] . ' Bölge - ' . $atamaOzet['masa'] . ' Masa') : 'Atama yok';
    $yetkiVar = fn($k) => $isPatron || _panelYetkiVar($p, $k);
    $yetkiSol = [
        ['shopping_cart', 'Sipariş Yönetimi', $yetkiVar('gor_pos')],
        ['storefront', 'Masa Yönetimi', $yetkiVar('gor_masa_yonetim')],
        ['sell', 'İndirim', $yetki['iskonto_limit'] > 0 ? '%' . number_format($yetki['iskonto_limit'], 0) : null],
        ['card_giftcard', 'İkram', $yetki['ikram_limit'] > 0 ? $para($yetki['ikram_limit']) : null],
    ];
    $yetkiSag = [
        ['bar_chart', 'Raporlar', $yetkiVar('gor_raporlar')],
        ['group', 'Personel Yönetimi', $yetkiVar('gor_personel')],
        ['insights', 'Finans', $yetkiVar('gor_finans')],
    ];
    $yesilTur = ['odeme', 'prim', 'ek_odeme'];
    $turEtiket = ['avans'=>'Avans', 'odeme'=>'Ödeme', 'prim'=>'Prim', 'kesinti'=>'Kesinti', 'ek_odeme'=>'Ek ödeme'];
@endphp

@section('content')
<style>
    .pm { display: flex; flex-direction: column; gap: 18px; color: var(--ink); }
    .pm-kart { background: var(--card); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 6px 22px rgba(79,70,229,.06); }

    /* ÜST KART */
    .pm-hero { position: relative; overflow: hidden; display: flex; flex-wrap: wrap; gap: 20px 44px; align-items: center; padding: 22px 36px 22px 60px;
        background: linear-gradient(115deg, var(--card) 45%, color-mix(in srgb, var(--mor1) 7%, var(--card))); }
    .pm-hero::before, .pm-hero::after { content: ''; position: absolute; pointer-events: none; border-radius: 50%; background: color-mix(in srgb, var(--mor1) 8%, transparent); }
    .pm-hero::before { width: 560px; height: 560px; right: -130px; top: 40px; }
    .pm-hero::after { width: 380px; height: 380px; right: 300px; top: 130px; }
    .pm-hero > * { position: relative; z-index: 1; }
    .pm-ava-wrap { position: relative; flex: none; }
    .pm-ava { width: 108px; height: 108px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer;
        background: linear-gradient(145deg, #8B5CF6, #6D28D9); color: #fff; box-shadow: 0 10px 24px rgba(109,40,217,.28); }
    .pm-ava img { width: 100%; height: 100%; object-fit: cover; }
    .pm-ava .material-icons-outlined { font-size: 66px; }
    .pm-ava-edit { position: absolute; right: -6px; bottom: 0; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        cursor: pointer; background: var(--card); color: var(--mor1); box-shadow: 0 4px 12px rgba(0,0,0,.16); }
    .pm-ava-edit .material-icons-outlined { font-size: 19px; }
    .pm-hero-bil { flex: 1; min-width: 280px; }
    .pm-hero-bil h2 { margin: 0; font-size: 24px; font-weight: 800; color: var(--ink); }
    .pm-altbaslik { margin-top: 3px; font-size: 15px; color: var(--sub); }
    .pm-cipler { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 9px; }
    .pm-cip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 11px; border-radius: 12px; font-size: 12.5px; font-weight: 600;
        background: var(--card2); color: var(--sub2); }
    .pm-cip .material-icons-outlined { font-size: 13px; }
    .pm-cip.yesil { background: color-mix(in srgb, #10B981 16%, var(--card)); color: #059669; }
    .pm-cip.yesil i { width: 8px; height: 8px; border-radius: 50%; background: #10B981; }
    body.koyu .pm-cip.yesil { color: #34D399; }
    .pm-meta { display: flex; flex-wrap: wrap; gap: 12px 0; margin-top: 14px; }
    .pm-meta-oge { display: flex; align-items: center; gap: 10px; padding-right: 28px; margin-right: 28px; border-right: 1px solid var(--line); }
    .pm-meta-oge:last-child { border-right: 0; margin-right: 0; padding-right: 0; }
    .pm-meta-oge > .material-icons-outlined { font-size: 23px; color: var(--sub2); }
    .pm-meta-oge small { display: block; font-size: 12px; color: var(--sub); }
    .pm-meta-oge b { font-size: 13.5px; font-weight: 600; color: var(--ink); }
    .pm-hero-btn { display: flex; flex-wrap: wrap; gap: 10px; align-self: flex-start; margin-top: 8px; }
    .pm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 10px 17px; border-radius: 12px; font-size: 13.5px; font-weight: 600; cursor: pointer;
        background: var(--card); color: var(--mor1); border: 1.5px solid color-mix(in srgb, var(--mor1) 45%, var(--line)); text-decoration: none; transition: all .14s; }
    .pm-btn:hover { background: color-mix(in srgb, var(--mor1) 8%, var(--card)); }
    .pm-btn .material-icons-outlined { font-size: 18px; }
    .pm-btn.ana { background: linear-gradient(135deg, #7C3AED, #5B21B6); color: #fff; border-color: transparent; box-shadow: 0 6px 16px rgba(91,33,182,.28); }
    .pm-btn.ana:hover { filter: brightness(1.08); }
    body.koyu .pm-btn:not(.ana) { color: #C4B5FD; }

    /* 5 ÖZET KART */
    .pm-kpi { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; }
    .pm-kpi-k { --c: #7C3AED; position: relative; overflow: hidden; display: block; width: 100%; text-align: left; padding: 15px 18px 16px 24px; cursor: pointer; border-radius: 18px; color: var(--ink);
        background: linear-gradient(135deg, var(--card) 40%, color-mix(in srgb, var(--c) 7%, var(--card)));
        border: 1px solid color-mix(in srgb, var(--c) 22%, var(--line)); box-shadow: 0 6px 18px color-mix(in srgb, var(--c) 9%, transparent); transition: transform .14s, box-shadow .14s; }
    .pm-kpi-k::after { content: ''; position: absolute; right: -24px; bottom: -34px; width: 66%; height: 70px; border-radius: 50%; transform: rotate(-16deg);
        background: linear-gradient(90deg, color-mix(in srgb, var(--c) 5%, transparent), color-mix(in srgb, var(--c) 22%, transparent)); pointer-events: none; }
    .pm-kpi-k:hover { transform: translateY(-2px); box-shadow: 0 10px 22px color-mix(in srgb, var(--c) 16%, transparent); }
    .pm-kpi-ust { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
    .pm-kpi-ik { font-size: 30px; color: var(--c); }
    .pm-kpi-ok { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--c) 16%, var(--card)); color: var(--c); }
    .pm-kpi-ok .material-icons-outlined { font-size: 18px; }
    .pm-kpi-d { position: relative; font-size: 18px; font-weight: 800; line-height: 1.25; }
    .pm-kpi-e { position: relative; font-size: 13px; color: var(--sub); margin-top: 2px; }

    /* KART GÖVDELERİ */
    .pm-iki { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 18px; }
    .pm-k { padding: 12px 22px 12px; }
    .pm-k-bas { display: flex; align-items: center; gap: 11px; padding: 4px 0 11px; border-bottom: 1px solid var(--line); }
    .pm-k-bas h3 { margin: 0; flex: 1; font-size: 17px; font-weight: 800; color: var(--ink); }
    .pm-k-bas > .material-icons-outlined { font-size: 25px; color: var(--mor1); }
    .pm-sayac { font-size: 12px; font-weight: 600; padding: 2px 9px; border-radius: 10px; background: var(--card2); color: var(--sub); }
    .pm-kucuk-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 16px; font-size: 13px; font-weight: 600; cursor: pointer;
        background: var(--card); color: var(--ink); border: 1px solid var(--line); box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    .pm-kucuk-btn:hover { border-color: var(--mor1); color: var(--mor1); }
    .pm-kucuk-btn .material-icons-outlined { font-size: 16px; color: var(--mor1); }
    .pm-link-btn { display: inline-flex; align-items: center; gap: 4px; background: none; border: 0; padding: 0; cursor: pointer; font-size: 12.5px; font-weight: 700; color: var(--mor1); }
    body.koyu .pm-link-btn { color: #C4B5FD; }
    .pm-link-btn .material-icons-outlined { font-size: 17px; }

    .pm-kisi { display: grid; grid-template-columns: 146px minmax(0, 1fr); gap: 20px; padding-top: 12px; }
    .pm-portre { min-height: 210px; border-radius: 22px; overflow: hidden; display: flex; align-items: center; justify-content: center; background: var(--card2); color: color-mix(in srgb, var(--mor1) 18%, var(--card2)); }
    .pm-portre img { width: 100%; height: 100%; object-fit: cover; }
    .pm-portre .material-icons-outlined { font-size: 130px; }
    .pm-bilgi { display: grid; grid-template-columns: 26px minmax(130px, .85fr) minmax(0, 1fr); align-items: center; column-gap: 8px; padding: 9px 2px; font-size: 13.5px; border-bottom: 1px solid var(--line); }
    .pm-bilgi:last-child { border-bottom: 0; }
    .pm-bilgi .material-icons-outlined { font-size: 18px; color: var(--mor1); }
    .pm-bilgi .e { color: var(--sub2); }
    .pm-bilgi .d { color: var(--ink); font-weight: 500; word-break: break-word; }

    .pm-gv { display: flex; width: 100%; align-items: center; gap: 16px; padding: 10px 2px; text-align: left; background: none; border: 0; border-bottom: 1px solid var(--line); cursor: pointer; text-decoration: none; color: inherit; }
    .pm-gv:last-child { border-bottom: 0; }
    .pm-gv:hover b { color: var(--mor1); }
    .pm-gv-ik { flex: none; width: 34px; text-align: center; font-size: 27px !important; }
    .pm-gv b { display: block; font-size: 13.5px; font-weight: 700; color: var(--ink); transition: color .12s; }
    .pm-gv small { display: block; font-size: 12px; color: var(--sub); }
    .pm-gv .govde { flex: 1; min-width: 0; }
    .pm-gv-ok { color: var(--sub2); }

    .pm-yetki { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0 34px; padding-top: 7px; }
    .pm-y-bas { display: flex; justify-content: space-between; padding: 0 2px 6px; border-bottom: 1px solid var(--line); font-size: 12.5px; font-weight: 700; color: var(--ink); }
    .pm-y-bas span:last-child { width: 72px; text-align: center; }
    .pm-y-s { display: flex; align-items: center; gap: 12px; padding: 7px 2px; font-size: 13.5px; border-bottom: 1px solid var(--line); }
    .pm-y-s:last-child { border-bottom: 0; }
    .pm-y-s .material-icons-outlined { font-size: 18px; color: var(--mor1); }
    .pm-y-s .a { flex: 1; min-width: 0; }
    .pm-y-s .b { width: 72px; text-align: center; font-weight: 700; font-size: 13px; }
    .pm-rozet-a { display: inline-block; padding: 1px 10px; border-radius: 10px; font-size: 12px; font-weight: 600; background: color-mix(in srgb, #10B981 18%, var(--card)); color: #059669; }
    body.koyu .pm-rozet-a { color: #34D399; }
    .pm-rozet-y { display: inline-block; padding: 1px 12px; border-radius: 10px; font-size: 12px; font-weight: 700; background: var(--card2); color: var(--sub); }

    .pm-akis { padding-top: 8px; }
    .pm-akis-s { display: flex; align-items: center; gap: 12px; padding: 7px 2px; font-size: 13.5px; }
    .pm-akis-s i { flex: none; width: 8px; height: 8px; border-radius: 50%; background: var(--mor1); }
    .pm-akis-s .sa { flex: none; width: 76px; color: var(--sub2); }
    .pm-akis-s .me { flex: 1; min-width: 0; color: var(--ink); }
    .pm-akis-s .ip { color: var(--sub); font-size: 11.5px; }
    .pm-bos { color: var(--sub); font-size: 13px; padding: 14px 2px; }

    /* ÇALIŞMA ÖZETİ */
    .pm-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; padding-top: 12px; }
    .pm-tile { background: var(--card2); border-radius: 12px; padding: 11px 13px; }
    .pm-tile .e { display: flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; }
    .pm-tile .e .material-icons-outlined { font-size: 15px; }
    .pm-tile .d { font-size: 16.5px; font-weight: 800; color: var(--ink); margin-top: 3px; }
    .pm-tile .a { font-size: 11.5px; color: var(--sub); margin-top: 1px; }
    .pm-rozet { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 22px; margin: 10px 6px 3px 0; font-size: 12.5px; font-weight: 700; }
    .pm-satir { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 2px; border-bottom: 1px solid var(--line); font-size: 13px; }
    .pm-satir:last-child { border-bottom: 0; }
    .pm-satir .k { color: var(--sub2); display: flex; align-items: center; gap: 7px; min-width: 0; }
    .pm-satir .k .material-icons-outlined { font-size: 16px; color: var(--sub); }
    .pm-satir .v { color: var(--ink); font-weight: 600; text-align: right; }
    .pm-kisayol { display: flex; flex-wrap: wrap; gap: 8px; padding-top: 12px; }
    .pm-kisayol a { text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 10px; font-size: 12.5px; font-weight: 600; color: var(--ink); background: var(--card); border: 1px solid var(--line); transition: all .15s; }
    .pm-kisayol a:hover { border-color: var(--mor1); color: var(--mor1); }
    .pm-kisayol a .material-icons-outlined { font-size: 15px; }

    /* PENCERELER */
    .pm-form-sat { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
    .pm-inp { width: 100%; padding: 9px 11px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--ink); font-size: 13px; box-sizing: border-box; }
    .pm-inp:focus { outline: none; border-color: var(--mor1); box-shadow: 0 0 0 3px rgba(124,58,237,0.12); }
    .pm-inp:disabled { background: var(--card2); color: var(--sub); }
    .pm-lbl { color: var(--sub); font-size: 11.5px; font-weight: 600; margin-bottom: 4px; display: block; }
    .pm-pencere { color: var(--ink); }
    .pm-pencere.genis { max-width: 560px; }
    .pm-parilti { animation: pmParilti 1.3s ease; }
    @keyframes pmParilti { 0%,100% { box-shadow: 0 6px 22px rgba(79,70,229,.06); } 30% { box-shadow: 0 0 0 3px color-mix(in srgb, var(--mor1) 45%, transparent); } }

    @media (max-width: 1250px) {
        .pm-kpi { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .pm-iki { grid-template-columns: minmax(0, 1fr); }
        .pm-hero { padding: 22px 26px; }
    }
    @media (max-width: 640px) {
        .pm-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .pm-kpi-k { padding: 12px 13px; }
        .pm-kpi-k:last-child { grid-column: 1 / -1; }
        .pm-kpi-d { font-size: 15.5px; }
        .pm-hero { padding: 18px 16px; }
        .pm-hero-bil { min-width: 100%; }
        .pm-hero-btn, .pm-hero-btn .pm-btn { width: 100%; }
        .pm-meta-oge { width: 100%; border-right: 0; margin-right: 0; padding-right: 0; }
        .pm-k { padding: 10px 14px; }
        .pm-kisi { grid-template-columns: minmax(0, 1fr); }
        .pm-portre { display: none; }
        .pm-bilgi { grid-template-columns: 24px minmax(0, 1fr) minmax(0, 1fr); font-size: 13px; }
        .pm-yetki { grid-template-columns: minmax(0, 1fr); }
        .pm-yetki > div + div { margin-top: 10px; }
        .pm-form-sat { grid-template-columns: 1fr; }
    }
</style>

<div class="pm">
    @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
    @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

    {{-- =============== ÜST KART =============== --}}
    <div class="pm-kart pm-hero">
        <form method="POST" action="/profilim-foto" enctype="multipart/form-data" class="pm-ava-wrap">
            @csrf
            <label for="pmFoto" class="pm-ava" title="Fotoğrafı değiştir">
                @if ($fotoVar)
                    <img src="{{ $fotoUrl }}" alt="">
                @else
                    <span class="material-icons-outlined">person</span>
                @endif
            </label>
            <label for="pmFoto" class="pm-ava-edit" title="Fotoğraf yükle"><span class="material-icons-outlined">edit</span></label>
            <input id="pmFoto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="this.form.submit()">
        </form>
        <div class="pm-hero-bil">
            <h2>{{ $p->ad ?: 'Personel' }}</h2>
            <div class="pm-altbaslik">{{ $rolAd }} &nbsp;•&nbsp; {{ $dep['ad'] ?? 'ResteOS' }}</div>
            <div class="pm-cipler">
                <span class="pm-cip yesil"><i></i>Aktif</span>
                @if ($mesai['acik'])
                    <span class="pm-cip yesil"><span class="material-icons-outlined">radio_button_checked</span>Mesaide · {{ $sureBicim($mesai['dakika']) }}</span>
                @endif
                @if ($dep && $dep['sorumlu'])
                    <span class="pm-cip"><span class="material-icons-outlined" style="color:#F59E0B">star</span>Departman sorumlusu</span>
                @endif
                @if (!empty($p->ise_baslama))
                    <span class="pm-cip"><span class="material-icons-outlined">event_available</span>{{ \Carbon\Carbon::parse($p->ise_baslama)->diffForHumans(['parts'=>1, 'short'=>true]) }} aramızda</span>
                @endif
                @if ($dahili)
                    <span class="pm-cip"><span class="material-icons-outlined">phone_in_talk</span>Dahili {{ $dahili }}</span>
                @endif
            </div>
            <div class="pm-meta">
                <div class="pm-meta-oge"><span class="material-icons-outlined">calendar_month</span><div><small>Son Giriş</small><b>{{ $zamanTam($sonGiris) }}</b></div></div>
                <div class="pm-meta-oge"><span class="material-icons-outlined">schedule</span><div><small>Son İşlem</small><b>{{ $zamanTam($sonIslem) }}</b></div></div>
                <div class="pm-meta-oge"><span class="material-icons-outlined">storefront</span><div><small>Şube</small><b>{{ $subeRow->ad ?? '—' }}</b></div></div>
            </div>
        </div>
        <div class="pm-hero-btn">
            <button type="button" class="pm-btn ana" onclick="pmAc('pmDuzenle')"><span class="material-icons-outlined">edit</span> Profili Düzenle</button>
            <button type="button" class="pm-btn" onclick="pmAc('pmPin')"><span class="material-icons-outlined">lock</span> PIN Değiştir</button>
            <button type="button" class="pm-btn" onclick="pmGit('pmGuvenlikKart')"><span class="material-icons-outlined">shield</span> Güvenlik</button>
        </div>
    </div>

    {{-- =============== 5 ÖZET KART =============== --}}
    <div class="pm-kpi">
        <button type="button" class="pm-kpi-k" style="--c:#7C3AED" onclick="pmAc('pmIzin')">
            <div class="pm-kpi-ust"><span class="material-icons-outlined pm-kpi-ik">calendar_month</span><span class="pm-kpi-ok"><span class="material-icons-outlined">arrow_forward</span></span></div>
            <div class="pm-kpi-d">{{ $gunBicim($kalanIzin) }} gün</div><div class="pm-kpi-e">İzin Bakiyesi</div>
        </button>
        <button type="button" class="pm-kpi-k" style="--c:#0EA5E9" onclick="pmAc('pmHakedis')">
            <div class="pm-kpi-ust"><span class="material-icons-outlined pm-kpi-ik">account_balance_wallet</span><span class="pm-kpi-ok"><span class="material-icons-outlined">arrow_forward</span></span></div>
            <div class="pm-kpi-d">{{ $para($hesap['hakedis']) }}</div><div class="pm-kpi-e">Hakediş</div>
        </button>
        <button type="button" class="pm-kpi-k" style="--c:#10B981" onclick="pmGit('pmYetkiKart')">
            <div class="pm-kpi-ust"><span class="material-icons-outlined pm-kpi-ik">location_on</span><span class="pm-kpi-ok"><span class="material-icons-outlined">arrow_forward</span></span></div>
            <div class="pm-kpi-d">{{ $alanMetin }}</div><div class="pm-kpi-e">Yetkili Olduğun Alan</div>
        </button>
        <button type="button" class="pm-kpi-k" style="--c:#F59E0B" onclick="pmAc('pmBelge')">
            <div class="pm-kpi-ust"><span class="material-icons-outlined pm-kpi-ik">description</span><span class="pm-kpi-ok"><span class="material-icons-outlined">arrow_forward</span></span></div>
            <div class="pm-kpi-d">{{ $sicil['belge'] }} / {{ $sicil['skt_yaklasan'] }}</div><div class="pm-kpi-e">Belgeler (toplam / SKT yakın)</div>
        </button>
        <button type="button" class="pm-kpi-k" style="--c:#F43F5E" onclick="pmAc('pmEgitim')">
            <div class="pm-kpi-ust"><span class="material-icons-outlined pm-kpi-ik">school</span><span class="pm-kpi-ok"><span class="material-icons-outlined">arrow_forward</span></span></div>
            <div class="pm-kpi-d">{{ $sicil['egitim'] }} Sertifika</div><div class="pm-kpi-e">Eğitimler</div>
        </button>
    </div>

    {{-- =============== KİŞİSEL BİLGİLER + HESAP & GÜVENLİK =============== --}}
    <div class="pm-iki">
        <div class="pm-kart pm-k">
            <div class="pm-k-bas">
                <span class="material-icons-outlined">person</span><h3>Kişisel Bilgiler</h3>
                <button type="button" class="pm-kucuk-btn" onclick="pmAc('pmDuzenle')"><span class="material-icons-outlined">edit</span> Düzenle</button>
            </div>
            <div class="pm-kisi">
                <div class="pm-portre">
                    @if ($fotoVar)
                        <img src="{{ $fotoUrl }}" alt="">
                    @else
                        <span class="material-icons-outlined">person</span>
                    @endif
                </div>
                <div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">call</span><span class="e">Telefon</span><span class="d">{{ $p->telefon ?: '—' }}</span></div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">event</span><span class="e">Doğum Tarihi</span><span class="d">{{ $tarihBicim($p->dogum_tarihi) }}</span></div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">credit_card</span><span class="e">IBAN (TR…)</span><span class="d">{{ $p->iban ?: '—' }}</span></div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">account_balance</span><span class="e">Mevcut IBAN</span><span class="d">{{ $ibanMask ?: '—' }}</span></div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">contact_emergency</span><span class="e">Acil durumda aranacak kişi</span><span class="d">{{ $p->acil_kisi ?: '—' }}</span></div>
                    <div class="pm-bilgi"><span class="material-icons-outlined">phone_in_talk</span><span class="e">Acil telefon</span><span class="d">{{ $p->acil_tel ?: '—' }}</span></div>
                </div>
            </div>
        </div>

        <div class="pm-kart pm-k" id="pmGuvenlikKart">
            <div class="pm-k-bas"><span class="material-icons-outlined">shield</span><h3>Hesap &amp; Güvenlik</h3></div>
            <button type="button" class="pm-gv" onclick="pmAc('pmPin')">
                <span class="material-icons-outlined pm-gv-ik" style="color:#8B5CF6">lock</span>
                <span class="govde"><b>PIN Değiştir</b><small>Hesabınızın PIN kodunu değiştirin</small></span>
                <span class="material-icons-outlined pm-gv-ok">chevron_right</span>
            </button>
            <button type="button" class="pm-gv" onclick="pmAc('pmGuvenlik')">
                <span class="material-icons-outlined pm-gv-ik" style="color:#10B981">verified_user</span>
                <span class="govde"><b>Son Güvenlik İşlemleri</b><small>Hesap hareketlerinizi görüntüleyin</small></span>
                <span class="material-icons-outlined pm-gv-ok">chevron_right</span>
            </button>
            <button type="button" class="pm-gv" onclick="pmAc('pmCihaz')">
                <span class="material-icons-outlined pm-gv-ik" style="color:#7C3AED">desktop_windows</span>
                <span class="govde"><b>Bağlı Cihazlar</b><small>Hesabınıza bağlı cihazları yönetin</small></span>
                <span class="material-icons-outlined pm-gv-ok">chevron_right</span>
            </button>
            <button type="button" class="pm-gv" onclick="pmAc('pmGirisler')">
                <span class="material-icons-outlined pm-gv-ik" style="color:#6D28D9">schedule</span>
                <span class="govde"><b>Son Girişler</b><small>Son giriş yaptığınız zamanları görün</small></span>
                <span class="material-icons-outlined pm-gv-ok">chevron_right</span>
            </button>
            <a href="/bildirim-ayar" class="pm-gv">
                <span class="material-icons-outlined pm-gv-ik" style="color:#7C3AED">notifications</span>
                <span class="govde"><b>Bildirim Tercihleri</b><small>Bildirim ayarlarınızı düzenleyin</small></span>
                <span class="material-icons-outlined pm-gv-ok">chevron_right</span>
            </a>
        </div>
    </div>

    {{-- =============== YETKİLERİM + HAREKETLER DEFTERİ =============== --}}
    <div class="pm-iki">
        <div class="pm-kart pm-k" id="pmYetkiKart">
            <div class="pm-k-bas"><span class="material-icons-outlined">star</span><h3>Yetkilerim</h3></div>
            <div class="pm-yetki">
                @foreach ([$yetkiSol, $yetkiSag] as $kolon)
                    <div>
                        <div class="pm-y-bas"><span>Yetki</span><span>Durum</span></div>
                        @foreach ($kolon as [$ik, $ad, $durum])
                            <div class="pm-y-s">
                                <span class="material-icons-outlined">{{ $ik }}</span>
                                <span class="a">{{ $ad }}</span>
                                <span class="b">
                                    @if ($durum === true)<span class="pm-rozet-a">Aktif</span>
                                    @elseif ($durum)<span>{{ $durum }}</span>
                                    @else<span class="pm-rozet-y">–</span>@endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pm-kart pm-k">
            <div class="pm-k-bas">
                <span class="material-icons-outlined">receipt_long</span><h3>Hareketler Defteri</h3>
                @if (count($akis) > 5)
                    <button type="button" class="pm-link-btn" onclick="pmAc('pmAkis')">Tümünü Gör <span class="material-icons-outlined">arrow_forward</span></button>
                @endif
            </div>
            <div class="pm-akis">
                @forelse (array_slice($akis, 0, 5) as $a)
                    <div class="pm-akis-s"><i></i><span class="sa">{{ $zamanKisa($a['zaman']) }}</span><span class="me">{{ $a['metin'] }}</span></div>
                @empty
                    <div class="pm-bos">Henüz kayıtlı hareket yok.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- =============== ÇALIŞMA ÖZETİM =============== --}}
    <div class="pm-kart pm-k">
        <div class="pm-k-bas"><span class="material-icons-outlined">insights</span><h3>Çalışma Özetim</h3><span class="pm-sayac">Bugün {{ \Carbon\Carbon::now()->format('d.m.Y') }}</span></div>
        <div class="pm-tiles">
            <div class="pm-tile">
                <div class="e" style="color:#10B981"><span class="material-icons-outlined">schedule</span> Mesai</div>
                <div class="d">{{ $mesai['acik'] ? $sureBicim($mesai['dakika']) : ($mesai['dakika'] ? $sureBicim($mesai['dakika']) : 'Kapalı') }}</div>
                <div class="a">{{ $mesai['giris'] ? ('Giriş '.$mesai['giris']) : 'Henüz açmadın' }}</div>
            </div>
            <div class="pm-tile">
                <div class="e" style="color:#4F46E5"><span class="material-icons-outlined">event</span> Sonraki Vardiya</div>
                <div class="d">{{ $sonraki ? ($sonraki['bas'].'–'.$sonraki['bit']) : '—' }}</div>
                <div class="a">{{ $sonraki ? ($sonraki['gun'].' '.$sonraki['tarih_kisa']) : 'Programda yok' }}</div>
            </div>
            <div class="pm-tile">
                <div class="e" style="color:#F59E0B"><span class="material-icons-outlined">account_balance_wallet</span> Bu Ay Net</div>
                <div class="d">{{ $para($hesap['net']) }}</div>
                <div class="a">Hakediş {{ $para($hesap['hakedis']) }}</div>
            </div>
            <div class="pm-tile">
                <div class="e" style="color:#64748B"><span class="material-icons-outlined">inventory_2</span> Zimmetim</div>
                <div class="d">{{ $sicil['zimmet'] }} öğe</div>
                <div class="a">Üzerimdeki demirbaş</div>
            </div>
            @if ($bugunToplam > 0)
                <div class="pm-tile">
                    <div class="e" style="color:#10B981"><span class="material-icons-outlined">payments</span> Satış</div>
                    <div class="d">{{ $para($bugun['ciro']) }}</div><div class="a">Bugün</div>
                </div>
                <div class="pm-tile">
                    <div class="e" style="color:#4F46E5"><span class="material-icons-outlined">table_restaurant</span> Masa</div>
                    <div class="d">{{ $bugun['masa'] }}</div><div class="a">Bugün</div>
                </div>
                <div class="pm-tile">
                    <div class="e" style="color:#8B5CF6"><span class="material-icons-outlined">notifications_active</span> Çağrı</div>
                    <div class="d">{{ $bugun['cagri'] }}</div><div class="a">Bugün</div>
                </div>
                <div class="pm-tile">
                    <div class="e" style="color:#F59E0B"><span class="material-icons-outlined">star</span> Bahşiş</div>
                    <div class="d">{{ $para($bugun['bahsis']) }}</div><div class="a">Bugün</div>
                </div>
            @endif
        </div>
        @if (count($rozetler) > 0)
            <div>
                @foreach ($rozetler as $r)
                    <span class="pm-rozet" style="background: {{ $r['renk'] }}22; color: {{ $r['renk'] }}; border: 1px solid {{ $r['renk'] }}66">
                        <span style="font-size:16px">{{ $r['ikon'] }}</span>{{ $r['ad'] }}
                    </span>
                @endforeach
            </div>
        @endif
        <div style="height:10px"></div>
    </div>

    <div class="pm-iki">
        {{-- Vardiyalarım --}}
        <div class="pm-kart pm-k">
            <div class="pm-k-bas"><span class="material-icons-outlined">event</span><h3>Vardiyalarım</h3><span class="pm-sayac">{{ count($vardiya) }}</span></div>
            @if (count($vardiya))
                <div style="padding-top:4px">
                    @foreach ($vardiya as $v)
                        <div class="pm-satir">
                            <span class="k"><span class="material-icons-outlined">today</span>{{ $v['gun'] }} {{ $v['tarih_kisa'] }}</span>
                            <span class="v">{{ $v['bas'] }}–{{ $v['bit'] }}@if($v['pozisyon']) · <span style="color:var(--sub); font-weight:500">{{ $v['pozisyon'] }}</span>@endif</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="pm-bos">Önümüzdeki 2 haftada vardiya programlanmamış.</div>
            @endif
            <div style="margin:10px 0 8px; display:flex; gap:8px; flex-wrap:wrap">
                <a href="/ik-izin" class="rk-btn" style="background:#14B8A6"><span class="material-icons-outlined" style="font-size:16px">beach_access</span> İzin iste</a>
                <a href="/puantaj" class="rk-btn" style="background:#4F46E5"><span class="material-icons-outlined" style="font-size:16px">history</span> Puantajım</a>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:18px; min-width:0">
            {{-- Yönettiğin bağlantılar — sadece sahip/müdür rolüne ve departman atamasına sahip olanlara --}}
            @if (!empty($dep['id']) && in_array($rol, ['sahip','mudur']))
                @php $drenk = $dep['renk'] ?? '#7C3AED'; @endphp
                <div class="pm-kart pm-k" style="background:color-mix(in srgb,{{ $drenk }} 5%,var(--card));border-color:color-mix(in srgb,{{ $drenk }} 28%,var(--line))">
                    <div class="pm-k-bas"><span class="material-icons-outlined" style="color:{{ $drenk }}">hub</span><h3>Yönettiğin Bağlantılar</h3></div>
                    <div style="color:var(--sub);font-size:12.5px;line-height:1.5;margin:10px 0 12px">
                        <b style="color:var(--ink)">{{ $dep['ad'] }}</b> departmanının mail (SMTP/IMAP) ve WhatsApp hesaplarını buradan yönet.
                        Bu ayarlar <b>kişisel değildir</b> — tüm departman adına kullanılır.
                    </div>
                    <a href="/departman-baglanti?dep={{ $dep['id'] }}" class="rk-btn" style="background:{{ $drenk }}; margin-bottom:8px">
                        <span class="material-icons-outlined" style="font-size:18px">settings</span>
                        {{ $dep['ad'] }} Bağlantı Ayarları
                    </a>
                </div>
            @endif

            {{-- Kısayollar (role-aware) --}}
            <div class="pm-kart pm-k" style="padding-bottom:18px">
                <div class="pm-k-bas"><span class="material-icons-outlined" style="color:#F59E0B">bolt</span><h3>Kısayollar</h3></div>
                <div class="pm-kisayol">
                    <a href="/bildirim-ayar"><span class="material-icons-outlined" style="color:#0EA5E9">notifications_active</span> Bildirim ayarları</a>
                    <a href="/puantaj"><span class="material-icons-outlined" style="color:#4F46E5">history</span> Puantajım</a>
                    <a href="/ik-izin"><span class="material-icons-outlined" style="color:#14B8A6">beach_access</span> İzin / Avans</a>
                    @if ($isGarson)
                        <a href="/garson-performans"><span class="material-icons-outlined" style="color:#10B981">emoji_events</span> Performansım</a>
                        <a href="/servis"><span class="material-icons-outlined" style="color:#4F46E5">room_service</span> İş Akışım</a>
                        <a href="/garson-cagrilari"><span class="material-icons-outlined" style="color:#F43F5E">notifications_active</span> Çağrılar</a>
                    @endif
                    @if ($isPatron)
                        <a href="/patron-ai"><span class="material-icons-outlined" style="color:#7C3AED">auto_awesome</span> Sabah Brifingi</a>
                        <a href="/ik-ozet"><span class="material-icons-outlined" style="color:#10B981">groups</span> İK Özet</a>
                        <a href="/ik-risk"><span class="material-icons-outlined" style="color:#F97316">crisis_alert</span> Risk Radarı</a>
                        <a href="/personel"><span class="material-icons-outlined" style="color:#4F46E5">badge</span> Personel yönetimi</a>
                        <a href="/bagli-cihazlar"><span class="material-icons-outlined" style="color:#0EA5E9">devices_other</span> Bağlı cihazlar</a>
                    @endif
                    @if (in_array($rol, ['satinalmaci','satinalma']))
                        <a href="/departman-brief?dep=satinalma"><span class="material-icons-outlined" style="color:#7C3AED">wb_sunny</span> Satınalma Brifingi</a>
                        <a href="/satinalma-asistan"><span class="material-icons-outlined" style="color:#7C3AED">smart_toy</span> AI Asistan</a>
                    @endif
                    @if ($rol === 'kasa')
                        <a href="/kasa"><span class="material-icons-outlined" style="color:#F59E0B">account_balance</span> Kasa (Vardiya)</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- =============== PENCERELER =============== --}}
<div class="rk-modal-arka" id="pmDuzenle">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Kişisel Bilgileri Düzenle<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <form method="POST" action="/profilim-kaydet">
            @csrf
            <div class="rk-modal-govde">
                <div class="pm-form-sat">
                    <div><span class="pm-lbl">Telefon</span><input class="pm-inp" name="telefon" value="{{ $p->telefon }}" placeholder="05xx…"></div>
                    <div><span class="pm-lbl">Doğum tarihi</span><input class="pm-inp" type="date" name="dogum_tarihi" value="{{ $p->dogum_tarihi }}"></div>
                </div>
                <div class="pm-form-sat">
                    <div><span class="pm-lbl">IBAN (TR…)</span><input class="pm-inp" name="iban" value="{{ $p->iban }}" placeholder="TR00 0000 …"></div>
                    <div><span class="pm-lbl">Mevcut IBAN</span><input class="pm-inp" value="{{ $ibanMask ?: '—' }}" disabled></div>
                </div>
                <div class="pm-form-sat" style="margin-bottom:0">
                    <div><span class="pm-lbl">Acil durumda aranacak kişi</span><input class="pm-inp" name="acil_kisi" value="{{ $p->acil_kisi }}"></div>
                    <div><span class="pm-lbl">Acil telefon</span><input class="pm-inp" name="acil_tel" value="{{ $p->acil_tel }}" placeholder="05xx…"></div>
                </div>
            </div>
            <div class="rk-modal-foot"><button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet</button></div>
        </form>
    </div>
</div>

<div class="rk-modal-arka" id="pmPin">
    <div class="rk-modal pm-pencere">
        <div class="rk-modal-head">PIN Değiştir<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <form method="POST" action="/profilim-pin-degistir">
            @csrf
            <div class="rk-modal-govde">
                <div style="margin-bottom:10px"><span class="pm-lbl">Mevcut PIN</span><input class="pm-inp" type="password" name="eski" maxlength="8" required inputmode="numeric" pattern="[0-9]*" autocomplete="off"></div>
                <div class="pm-form-sat" style="margin-bottom:0">
                    <div><span class="pm-lbl">Yeni PIN (4-8 hane)</span><input class="pm-inp" type="password" name="yeni" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off"></div>
                    <div><span class="pm-lbl">Yeni PIN (tekrar)</span><input class="pm-inp" type="password" name="yeni2" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}" autocomplete="off"></div>
                </div>
            </div>
            <div class="rk-modal-foot"><button class="rk-btn" style="background:#8B5CF6" type="submit"><span class="material-icons-outlined" style="font-size:16px">lock_reset</span> PIN'i değiştir</button></div>
        </form>
    </div>
</div>

<div class="rk-modal-arka" id="pmCihaz">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Bağlı Cihazlar<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @forelse ($cihazlar as $c)
                <div class="pm-satir">
                    <span class="k">
                        <span class="material-icons-outlined">{{ $c['platform']==='ios' ? 'phone_iphone' : ($c['platform']==='android' ? 'phone_android' : 'devices') }}</span>
                        <span><b style="color:var(--ink)">{{ strtoupper($c['platform'] ?: 'Cihaz') }}</b> · {{ $c['token_son'] }}<br><small style="color:var(--sub)">{{ $zamanTam($c['tarih']) }}</small></span>
                    </span>
                    <form method="POST" action="/profilim-cihaz-sil" onsubmit="return confirm('Bu cihazdan çıkış yapılsın mı?')">
                        @csrf
                        <input type="hidden" name="id" value="{{ $c['id'] }}">
                        <button type="submit" class="rk-btn" style="background:#F43F5E; padding:5px 10px; font-size:12px"><span class="material-icons-outlined" style="font-size:14px">logout</span> Çıkış</button>
                    </form>
                </div>
            @empty
                <div class="pm-bos">Kayıtlı cihaz yok.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmGirisler">
    <div class="rk-modal pm-pencere">
        <div class="rk-modal-head">Son Girişler<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @forelse ($girisler as $g)
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">login</span>{{ $g['metin'] }}</span><span class="v">{{ $zamanTam($g['zaman']) }}<br><small style="color:var(--sub); font-weight:500">{{ $g['ip'] }}</small></span></div>
            @empty
                <div class="pm-bos">Giriş kaydı bir sonraki girişten itibaren burada görünür.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmGuvenlik">
    <div class="rk-modal pm-pencere">
        <div class="rk-modal-head">Son Güvenlik İşlemleri<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @forelse ($guvenlikLog as $g)
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">verified_user</span>{{ $g['metin'] }}</span><span class="v">{{ $zamanTam($g['zaman']) }}</span></div>
            @empty
                <div class="pm-bos">Henüz güvenlik işlemi yok. PIN, kişisel bilgi ve cihaz değişiklikleri burada listelenir.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmIzin">
    <div class="rk-modal pm-pencere">
        <div class="rk-modal-head">İzin Bakiyesi<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            <div class="pm-satir"><span class="k">Yıllık hak</span><span class="v">{{ $gunBicim($hak) }} gün</span></div>
            <div class="pm-satir"><span class="k">Kullanılan</span><span class="v">{{ $gunBicim($kullanilan) }} gün</span></div>
            <div class="pm-satir"><span class="k"><b>Kalan</b></span><span class="v">{{ $gunBicim($kalanIzin) }} gün</span></div>
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmHakedis">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Bu Ay Hakediş<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            <div class="pm-satir"><span class="k">Maaş</span><span class="v">{{ $para($hesap['maas']) }}</span></div>
            <div class="pm-satir"><span class="k">Prim</span><span class="v">{{ $para($hesap['prim_hesap'] + $hesap['prim_manuel']) }}</span></div>
            <div class="pm-satir"><span class="k">Ek ödeme</span><span class="v">{{ $para($hesap['ek_odeme']) }}</span></div>
            <div class="pm-satir"><span class="k">Kesinti</span><span class="v">−{{ $para($hesap['kesinti']) }}</span></div>
            <div class="pm-satir"><span class="k"><b>Hakediş</b></span><span class="v">{{ $para($hesap['hakedis']) }}</span></div>
            <div class="pm-satir"><span class="k">Avans + Ödenen</span><span class="v">−{{ $para($hesap['avans'] + $hesap['odenen']) }}</span></div>
            <div class="pm-satir"><span class="k"><b>Net</b></span><span class="v">{{ $para($hesap['net']) }}</span></div>
            <div style="font-weight:800; font-size:13.5px; margin:14px 0 4px">Son maaş hareketleri</div>
            @forelse ($hareketler as $h)
                @php $yesil = in_array($h['tur'] ?? '', $yesilTur); @endphp
                <div class="pm-satir">
                    <span class="k"><span class="material-icons-outlined" style="color:{{ $yesil ? '#10B981' : '#EF4444' }}">{{ $yesil ? 'arrow_downward' : 'arrow_upward' }}</span>
                        <span><b style="color:var(--ink)">{{ $turEtiket[$h['tur'] ?? ''] ?? ($h['tur'] ?? '') }}</b><br><small style="color:var(--sub)">{{ $h['aciklama'] ?: $h['tarih'] }}</small></span></span>
                    <span class="v" style="color:{{ $yesil ? '#10B981' : '#EF4444' }}">{{ $yesil ? '+' : '−' }}{{ $para($h['tutar'] ?? 0) }}</span>
                </div>
            @empty
                <div class="pm-bos">Henüz kayıt yok.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmBelge">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Belgelerim<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @forelse ($belgeler as $b)
                @php $yakin = !empty($b->son_gecerlilik) && $b->son_gecerlilik <= now()->addDays(30)->toDateString(); @endphp
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">description</span>{{ $b->baslik }}</span><span class="v" @if($yakin) style="color:#EF4444" @endif>SKT {{ $tarihBicim($b->son_gecerlilik) }}</span></div>
            @empty
                <div class="pm-bos">Kayıtlı belge yok.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmEgitim">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Eğitimlerim<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @forelse ($egitimler as $e)
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">school</span><span>{{ $e->konu }}@if($e->veren)<br><small style="color:var(--sub)">{{ $e->veren }}</small>@endif</span></span><span class="v">{{ $tarihBicim($e->egitim_tarihi) }}</span></div>
            @empty
                <div class="pm-bos">Kayıtlı eğitim yok.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="rk-modal-arka" id="pmAkis">
    <div class="rk-modal pm-pencere genis">
        <div class="rk-modal-head">Hareketler Defteri<button type="button" class="rk-modal-kapa" onclick="pmKapa(this)">&times;</button></div>
        <div class="rk-modal-govde">
            @foreach ($akis as $a)
                <div class="pm-akis-s"><i></i><span class="sa">{{ $zamanKisa($a['zaman']) }}</span><span class="me">{{ $a['metin'] }}</span></div>
            @endforeach
        </div>
    </div>
</div>

<script>
    function pmAc(id) {
        var m = document.getElementById(id); if (!m) return;
        if (m.parentNode !== document.body) document.body.appendChild(m); // tam ekran ortası: üst katman bağlamından çıkar
        m.classList.add('acik');
    }
    function pmKapa(el) { var m = el.closest('.rk-modal-arka'); if (m) m.classList.remove('acik'); }
    function pmGit(id) {
        var k = document.getElementById(id); if (!k) return;
        k.scrollIntoView({ behavior: 'smooth', block: 'center' });
        k.classList.remove('pm-parilti'); void k.offsetWidth; k.classList.add('pm-parilti');
    }
    document.addEventListener('click', function (e) { if (e.target.classList && e.target.classList.contains('rk-modal-arka') && e.target.id.indexOf('pm') === 0) e.target.classList.remove('acik'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('.rk-modal-arka.acik[id^="pm"]').forEach(function (m) { m.classList.remove('acik'); }); });
</script>
@endsection

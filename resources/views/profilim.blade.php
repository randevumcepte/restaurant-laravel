@extends('layout.app')
@section('rk_ikon', 'account_circle')
@section('baslik', 'Profilim')
@section('title', 'Profilim')

@php
    $rolAd = ['sahip'=>'Sahip','mudur'=>'Müdür','kasa'=>'Kasa','garson'=>'Garson','mutfak'=>'Mutfak','satinalmaci'=>'Satın Almacı','satinalma'=>'Satın Almacı'][$p->rol ?? ''] ?? ($p->rol ?? 'Personel');
    $rolRenk = ['sahip'=>'#7C3AED','mudur'=>'#4F46E5','kasa'=>'#F59E0B','garson'=>'#10B981','mutfak'=>'#EF4444','satinalmaci'=>'#14B8A6','satinalma'=>'#14B8A6'][$p->rol ?? ''] ?? '#64748B';
    $bosHarf = '';
    foreach (preg_split('/\s+/', trim((string)($p->ad ?? ''))) as $parca) { if ($parca !== '' && mb_strlen($bosHarf) < 2) $bosHarf .= mb_strtoupper(mb_substr($parca, 0, 1)); }
    if ($bosHarf === '') $bosHarf = '?';
    $kalanIzin = max(0, (float)$hak - (float)$kullanilan);
    $sureBicim = function ($dk) {
        $dk = (int) $dk; $s = intdiv($dk, 60); $m = $dk % 60;
        return $s > 0 ? "{$s}s {$m}dk" : "{$m}dk";
    };
    $para = fn($v) => '₺' . number_format((float)$v, 0, ',', '.');
    $rol = $p->rol ?? '';
    $isGarson = in_array($rol, ['garson','sahip','mudur']);
    $isPatron = in_array($rol, ['sahip','mudur']);
    $bugunToplam = ($bugun['ciro'] ?? 0) + ($bugun['masa'] ?? 0) + ($bugun['cagri'] ?? 0) + ($bugun['bahsis'] ?? 0);
@endphp

@section('content')
<style>
    .pm-wrap { max-width: 1180px; margin: 0 auto; }
    .pm-head { background: linear-gradient(135deg, {{ $rolRenk }}, {{ $rolRenk }}bb); color: #fff; border-radius: 16px;
        padding: 20px 24px; display: flex; gap: 18px; align-items: center; margin-bottom: 18px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.1); flex-wrap: wrap; }
    .pm-ava-wrap { position: relative; flex-shrink: 0; }
    .pm-ava { width: 76px; height: 76px; border-radius: 50%; background: rgba(255,255,255,0.22); border: 3px solid #fff;
        display:flex; align-items:center; justify-content:center; font-size:30px; font-weight:bold; overflow:hidden; }
    .pm-ava img { width:100%; height:100%; object-fit:cover; }
    .pm-ava-edit { position: absolute; right: -4px; bottom: -4px; background: #fff; color: {{ $rolRenk }};
        width: 26px; height: 26px; border-radius: 50%; display:flex; align-items:center; justify-content:center;
        cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,0.2); border: 2px solid {{ $rolRenk }}; }
    .pm-ava-edit .material-icons-outlined { font-size: 14px; }
    .pm-head-info { flex: 1; min-width: 240px; }
    .pm-head-info h2 { margin: 0 0 8px; font-size: 24px; font-weight: 700; }
    .pm-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .pm-chip { background: rgba(255,255,255,0.22); color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px; backdrop-filter: blur(4px); }
    .pm-chip.live { background: rgba(16,185,129,0.65); animation: pmPulse 2s infinite; }
    @keyframes pmPulse { 0%,100%{opacity:1} 50%{opacity:.75} }

    .pm-grid-6 { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin-bottom: 18px; }
    .pm-ozet { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; transition: transform .15s; }
    .pm-ozet:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,0.06); }
    .pm-ozet .et { color: var(--sub); font-size: 11px; font-weight: 600; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .3px; }
    .pm-ozet .et .ikon { padding: 4px; border-radius: 6px; font-size: 13px; display: inline-flex; }
    .pm-ozet .dg { color: var(--ink); font-size: 18px; font-weight: 700; line-height: 1.15; }
    .pm-ozet .al { color: var(--sub2); font-size: 11px; margin-top: 3px; }
    .pm-ozet.live .dg { color: #10B981; }

    .pm-cols { display: grid; grid-template-columns: 1.1fr 1fr; gap: 18px; }
    @media (max-width: 900px) { .pm-cols { grid-template-columns: 1fr; } }
    .pm-blok { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 16px 18px; margin-bottom: 16px; }
    .pm-blok h3 { margin: 0 0 12px; font-size: 14.5px; color: var(--ink); font-weight: 700; display:flex; align-items:center; gap: 7px; }
    .pm-blok h3 .material-icons-outlined { font-size: 18px; color: var(--mor1); }
    .pm-blok h3 .sayac { margin-left: auto; font-size: 11px; color: var(--sub); font-weight: 500; background: var(--card2); padding: 2px 8px; border-radius: 10px; }

    .pm-satir { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px dashed var(--line); font-size: 13px; }
    .pm-satir:last-child { border-bottom: 0; }
    .pm-satir .k { color: var(--sub); display: flex; align-items: center; gap: 6px; }
    .pm-satir .k .material-icons-outlined { font-size: 15px; color: var(--sub2); }
    .pm-satir .v { color: var(--ink); font-weight: 600; text-align: right; }

    .pm-hareket { display: flex; align-items: center; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--line); }
    .pm-hareket:last-child { border-bottom: 0; }
    .pm-hareket .ok { padding: 6px; border-radius: 8px; display:flex; }
    .pm-hareket .ok .material-icons-outlined { font-size: 15px; }
    .pm-hareket .bil { flex: 1; min-width: 0; }
    .pm-hareket .bil b { color: var(--ink); font-size: 13px; }
    .pm-hareket .bil small { color: var(--sub); font-size: 11px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pm-hareket .tut { font-weight: 700; font-size: 14px; white-space: nowrap; }
    .pm-ok { color: #10B981; } .pm-ok .ok { background: rgba(16,185,129,0.14); color: #10B981; }
    .pm-no { color: #EF4444; } .pm-no .ok { background: rgba(239,68,68,0.14); color: #EF4444; }

    .pm-rozet { display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 22px; margin: 3px; font-size: 12.5px; font-weight: 700; }
    .pm-rozet .emo { font-size: 16px; }

    .pm-form-sat { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
    @media (max-width: 640px) { .pm-form-sat { grid-template-columns: 1fr; } }
    .pm-inp { width: 100%; padding: 9px 11px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--ink); font-size: 13px; box-sizing: border-box; }
    .pm-inp:focus { outline: none; border-color: var(--mor1); box-shadow: 0 0 0 3px rgba(124,58,237,0.12); }
    .pm-inp:disabled { background: var(--card2); color: var(--sub); }
    .pm-lbl { color: var(--sub); font-size: 11.5px; font-weight: 600; margin-bottom: 4px; display: block; }

    .pm-cihaz-sat { display: flex; align-items: center; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid var(--line); gap: 10px; }
    .pm-cihaz-sat:last-child { border-bottom: 0; }
    .pm-cihaz-sat .material-icons-outlined { color: var(--sub2); font-size: 20px; }

    .pm-mini-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 8px; }
    .pm-mini { background: var(--card2); border-radius: 10px; padding: 10px; text-align: left; }
    .pm-mini .mini-et { color: var(--sub); font-size: 10.5px; font-weight: 600; margin-bottom: 2px; display: flex; align-items: center; gap: 4px; }
    .pm-mini .mini-dg { color: var(--ink); font-size: 15px; font-weight: 700; }

    .pm-kisayol-grid { display: flex; flex-wrap: wrap; gap: 8px; }
    .pm-kisayol-grid a { text-decoration: none; display: inline-flex; align-items: center; gap: 6px; padding: 8px 12px;
        border-radius: 10px; font-size: 12.5px; font-weight: 600; color: var(--ink); background: var(--card);
        border: 1px solid var(--line); transition: all .15s; }
    .pm-kisayol-grid a:hover { border-color: var(--mor1); color: var(--mor1); }
    .pm-kisayol-grid a .material-icons-outlined { font-size: 15px; }

    @media (max-width: 640px) {
        .pm-head { padding: 16px 14px; gap: 12px; }
        .pm-ava { width: 60px; height: 60px; font-size: 22px; }
        .pm-head-info h2 { font-size: 18px; }
        .pm-blok { padding: 12px 14px; }
        .pm-ozet .dg { font-size: 16px; }
    }
</style>

<div class="rk-body pm-wrap">
    @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
    @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

    {{-- =============== ÜST HEADER =============== --}}
    <div class="pm-head">
        <form method="POST" action="/profilim-foto" enctype="multipart/form-data" class="pm-ava-wrap">
            @csrf
            <label for="pmFoto" class="pm-ava" style="cursor:pointer" title="Fotoğrafı değiştir">
                @if (!empty($p->foto) && str_starts_with((string)$p->foto, 'data:image'))
                    <img src="{{ $p->foto }}" alt="avatar">
                @else
                    {{ $bosHarf }}
                @endif
            </label>
            <label for="pmFoto" class="pm-ava-edit" title="Fotoğraf yükle">
                <span class="material-icons-outlined">photo_camera</span>
            </label>
            <input id="pmFoto" type="file" name="foto" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="this.form.submit()">
        </form>
        <div class="pm-head-info">
            <h2>{{ $p->ad ?: 'Personel' }}</h2>
            <div class="pm-chips">
                <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">badge</span>{{ $rolAd }}</span>
                @if (!empty($subeRow->ad))
                    <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">store</span>{{ $subeRow->ad }}</span>
                @endif
                @if ($dep)
                    <span class="pm-chip" style="background: {{ $dep['renk'] ?? '#7C3AED' }}cc">
                        <span class="material-icons-outlined" style="font-size:12px">diversity_3</span>
                        {{ $dep['ad'] }}
                        @if ($dep['sorumlu'])<span class="material-icons-outlined" style="font-size:11px; color:#FBBF24">star</span>@endif
                    </span>
                @endif
                @if (!empty($p->ise_baslama))
                    <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">event_available</span>{{ \Carbon\Carbon::parse($p->ise_baslama)->diffForHumans(['parts'=>1, 'short'=>true]) }} aramızda</span>
                @endif
                @if ($mesai['acik'])
                    <span class="pm-chip live"><span class="material-icons-outlined" style="font-size:12px">radio_button_checked</span>Mesaide · {{ $sureBicim($mesai['dakika']) }}</span>
                @endif
                @if ($dahili)
                    <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">phone_in_talk</span>Dahili {{ $dahili }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- =============== 6 ÖZET KART =============== --}}
    <div class="pm-grid-6">
        <div class="pm-ozet {{ $mesai['acik'] ? 'live' : '' }}">
            <div class="et"><span class="ikon" style="background:rgba(16,185,129,0.14); color:#10B981"><span class="material-icons-outlined" style="font-size:13px">schedule</span></span> Mesai</div>
            <div class="dg">{{ $mesai['acik'] ? $sureBicim($mesai['dakika']) : ($mesai['dakika'] ? $sureBicim($mesai['dakika']) : 'Kapalı') }}</div>
            <div class="al">{{ $mesai['giris'] ? ('Giriş '.$mesai['giris']) : 'Henüz açmadın' }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="ikon" style="background:rgba(79,70,229,0.14); color:#4F46E5"><span class="material-icons-outlined" style="font-size:13px">event</span></span> Sonraki Vardiya</div>
            <div class="dg">{{ $sonraki ? ($sonraki['bas'].'–'.$sonraki['bit']) : '—' }}</div>
            <div class="al">{{ $sonraki ? ($sonraki['gun'].' '.$sonraki['tarih_kisa']) : 'Programda yok' }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="ikon" style="background:rgba(20,184,166,0.14); color:#14B8A6"><span class="material-icons-outlined" style="font-size:13px">beach_access</span></span> İzin</div>
            <div class="dg">{{ rtrim(rtrim(number_format($kalanIzin, 1, ',', ''), '0'), ',') }} gün</div>
            <div class="al">Hak {{ (float)$hak }} · Kullanılan {{ (float)$kullanilan }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="ikon" style="background:rgba(245,158,11,0.14); color:#F59E0B"><span class="material-icons-outlined" style="font-size:13px">account_balance_wallet</span></span> Bu Ay Net</div>
            <div class="dg">{{ $para($hesap['net']) }}</div>
            <div class="al">Hakediş {{ $para($hesap['hakedis']) }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="ikon" style="background:rgba(14,165,233,0.14); color:#0EA5E9"><span class="material-icons-outlined" style="font-size:13px">devices</span></span> Cihaz</div>
            <div class="dg">{{ count($cihazlar) }}</div>
            <div class="al">Oturum sayısı</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="ikon" style="background:rgba(239,68,68,0.14); color:#EF4444"><span class="material-icons-outlined" style="font-size:13px">warning_amber</span></span> SKT Yakın</div>
            <div class="dg">{{ $sicil['skt_yaklasan'] }}</div>
            <div class="al">30 gün içinde biten belge</div>
        </div>
    </div>

    {{-- =============== BUGÜN BEN KARNESİ (role-aware) =============== --}}
    <div class="pm-blok">
        <h3><span class="material-icons-outlined">insights</span> Bugün ben <span class="sayac">{{ \Carbon\Carbon::now()->format('d.m.Y') }}</span></h3>
        @if ($bugunToplam == 0)
            <div style="color:var(--sub); font-size:13px; padding: 10px 0; display:flex; align-items:center; gap:10px">
                <span class="material-icons-outlined" style="color:var(--sub2)">coffee</span>
                Bugün henüz hareketin yok. Mesaini aç ve başla.
            </div>
        @else
            <div class="pm-mini-grid">
                <div class="pm-mini">
                    <div class="mini-et" style="color:#10B981"><span class="material-icons-outlined" style="font-size:13px">payments</span> Satış</div>
                    <div class="mini-dg">{{ $para($bugun['ciro']) }}</div>
                </div>
                <div class="pm-mini">
                    <div class="mini-et" style="color:#4F46E5"><span class="material-icons-outlined" style="font-size:13px">table_restaurant</span> Masa</div>
                    <div class="mini-dg">{{ $bugun['masa'] }}</div>
                </div>
                <div class="pm-mini">
                    <div class="mini-et" style="color:#8B5CF6"><span class="material-icons-outlined" style="font-size:13px">notifications_active</span> Çağrı</div>
                    <div class="mini-dg">{{ $bugun['cagri'] }}</div>
                </div>
                <div class="pm-mini">
                    <div class="mini-et" style="color:#F59E0B"><span class="material-icons-outlined" style="font-size:13px">star</span> Bahşiş</div>
                    <div class="mini-dg">{{ $para($bugun['bahsis']) }}</div>
                </div>
            </div>
        @endif
    </div>

    {{-- =============== ROZETLER + YETKİ =============== --}}
    @if (count($rozetler) > 0 || $yetki['iskonto_limit'] > 0 || $yetki['ikram_limit'] > 0 || $atamaOzet)
        <div class="pm-cols">
            @if (count($rozetler) > 0)
                <div class="pm-blok">
                    <h3><span class="material-icons-outlined" style="color:#F59E0B">emoji_events</span> Rozetlerim <span class="sayac">{{ count($rozetler) }}</span></h3>
                    <div>
                        @foreach ($rozetler as $r)
                            <span class="pm-rozet" style="background: {{ $r['renk'] }}22; color: {{ $r['renk'] }}; border: 1px solid {{ $r['renk'] }}66">
                                <span class="emo">{{ $r['ikon'] }}</span>{{ $r['ad'] }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="pm-blok">
                <h3><span class="material-icons-outlined" style="color:#8B5CF6">verified_user</span> Yetki özetim</h3>
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">percent</span>İskonto limiti</span><span class="v">{{ $yetki['iskonto_limit'] > 0 ? '%'.number_format($yetki['iskonto_limit'], 0) : '—' }}</span></div>
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">card_giftcard</span>İkram limiti</span><span class="v">{{ $yetki['ikram_limit'] > 0 ? $para($yetki['ikram_limit']) : '—' }}</span></div>
                @if ($atamaOzet)
                    <div class="pm-satir"><span class="k"><span class="material-icons-outlined">table_restaurant</span>Sorumlu olduğum</span><span class="v">{{ $atamaOzet['bolge'] }} bölge · {{ $atamaOzet['masa'] }} masa</span></div>
                @endif
                @if ($dahili)
                    <div class="pm-satir"><span class="k"><span class="material-icons-outlined">phone_in_talk</span>Dahili numaram</span><span class="v">{{ $dahili }}</span></div>
                @endif
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">inventory_2</span>Zimmetim</span><span class="v">{{ $sicil['zimmet'] }} öğe</span></div>
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">description</span>Belgem</span><span class="v">{{ $sicil['belge'] }} belge @if($sicil['skt_yaklasan'])· <span style="color:#EF4444">{{ $sicil['skt_yaklasan'] }} yakın SKT</span>@endif</span></div>
                <div class="pm-satir"><span class="k"><span class="material-icons-outlined">school</span>Eğitimim</span><span class="v">{{ $sicil['egitim'] }} sertifika</span></div>
            </div>
        </div>
    @endif

    <div class="pm-cols">
        {{-- SOL — KİŞİSEL BİLGİ + VARDİYA + CİHAZ --}}
        <div>
            {{-- Kişisel bilgi --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">person</span> Kişisel bilgiler</h3>
                <form method="POST" action="/profilim-kaydet">
                    @csrf
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">Telefon</span><input class="pm-inp" name="telefon" value="{{ $p->telefon }}" placeholder="05xx…"></div>
                        <div><span class="pm-lbl">Doğum tarihi</span><input class="pm-inp" type="date" name="dogum_tarihi" value="{{ $p->dogum_tarihi }}"></div>
                    </div>
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">IBAN (TR…)</span><input class="pm-inp" name="iban" value="{{ $p->iban }}" placeholder="TR00 0000 …"></div>
                        <div><span class="pm-lbl">Mevcut IBAN</span><input class="pm-inp" value="{{ $ibanMask ?: '—' }}" disabled></div>
                    </div>
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">Acil durumda aranacak kişi</span><input class="pm-inp" name="acil_kisi" value="{{ $p->acil_kisi }}"></div>
                        <div><span class="pm-lbl">Acil telefon</span><input class="pm-inp" name="acil_tel" value="{{ $p->acil_tel }}" placeholder="05xx…"></div>
                    </div>
                    <div style="margin-top:12px"><button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet</button></div>
                </form>
            </div>

            {{-- Vardiya haftalık --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">event</span> Vardiyalarım <span class="sayac">{{ count($vardiya) }}</span></h3>
                @if (count($vardiya))
                    @foreach ($vardiya as $v)
                        <div class="pm-satir">
                            <span class="k"><span class="material-icons-outlined">today</span>{{ $v['gun'] }} {{ $v['tarih_kisa'] }}</span>
                            <span class="v">{{ $v['bas'] }}–{{ $v['bit'] }}@if($v['pozisyon']) · <span style="color:var(--sub); font-weight:500">{{ $v['pozisyon'] }}</span>@endif</span>
                        </div>
                    @endforeach
                @else
                    <div style="color:var(--sub); font-size:13px; padding: 6px 0">Önümüzdeki 2 haftada vardiya programlanmamış.</div>
                @endif
                <div style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap">
                    <a href="/ik-izin" class="rk-btn" style="background:#14B8A6"><span class="material-icons-outlined" style="font-size:16px">beach_access</span> İzin iste</a>
                    <a href="/puantaj" class="rk-btn" style="background:#4F46E5"><span class="material-icons-outlined" style="font-size:16px">history</span> Puantajım</a>
                </div>
            </div>

            {{-- Oturumlar --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">devices</span> Oturumlarım <span class="sayac">{{ count($cihazlar) }}</span></h3>
                @if (count($cihazlar))
                    @foreach ($cihazlar as $c)
                        <div class="pm-cihaz-sat">
                            <div style="display:flex; align-items:center; gap:9px; min-width:0; flex:1">
                                <span class="material-icons-outlined">{{ $c['platform']==='ios' ? 'phone_iphone' : ($c['platform']==='android' ? 'phone_android' : 'devices') }}</span>
                                <div style="min-width:0">
                                    <div style="font-size:12.5px; color:var(--ink); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ strtoupper($c['platform'] ?: 'Cihaz') }} · <span style="color:var(--sub); font-weight:500">{{ $c['token_son'] }}</span></div>
                                    <div style="font-size:11px; color:var(--sub)">{{ $c['tarih'] }}</div>
                                </div>
                            </div>
                            <form method="POST" action="/profilim-cihaz-sil" onsubmit="return confirm('Bu cihazdan çıkış yapılsın mı?')">
                                @csrf
                                <input type="hidden" name="id" value="{{ $c['id'] }}">
                                <button type="submit" class="rk-btn" style="background:#F43F5E; padding:5px 10px; font-size:12px"><span class="material-icons-outlined" style="font-size:14px">logout</span> Çıkış</button>
                            </form>
                        </div>
                    @endforeach
                @else
                    <div style="color:var(--sub); font-size:13px">Kayıtlı cihaz yok.</div>
                @endif
            </div>
        </div>

        {{-- SAĞ — PIN + HAREKETLER + KISAYOLLAR --}}
        <div>
            {{-- PIN değiştir --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">pin</span> PIN değiştir</h3>
                <form method="POST" action="/profilim-pin-degistir">
                    @csrf
                    <div style="margin-bottom:10px"><span class="pm-lbl">Mevcut PIN</span><input class="pm-inp" type="password" name="eski" maxlength="8" required inputmode="numeric" pattern="[0-9]*"></div>
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">Yeni PIN (4-8 hane)</span><input class="pm-inp" type="password" name="yeni" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}"></div>
                        <div><span class="pm-lbl">Yeni PIN (tekrar)</span><input class="pm-inp" type="password" name="yeni2" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}"></div>
                    </div>
                    <div style="margin-top:12px"><button class="rk-btn" style="background:#8B5CF6" type="submit"><span class="material-icons-outlined" style="font-size:16px">lock_reset</span> PIN'i değiştir</button></div>
                </form>
            </div>

            {{-- Hareketler --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">receipt_long</span> Hareketler defteri <span class="sayac">{{ count($hareketler) }}</span></h3>
                @if (count($hareketler))
                    @php
                        $yesiller = ['odeme', 'prim', 'ek_odeme'];
                        $etiket = ['avans'=>'Avans', 'odeme'=>'Ödeme', 'prim'=>'Prim', 'kesinti'=>'Kesinti', 'ek_odeme'=>'Ek ödeme'];
                    @endphp
                    @foreach ($hareketler as $h)
                        @php $yesil = in_array($h['tur'] ?? '', $yesiller); @endphp
                        <div class="pm-hareket {{ $yesil ? 'pm-ok' : 'pm-no' }}">
                            <div class="ok"><span class="material-icons-outlined">{{ $yesil ? 'arrow_downward' : 'arrow_upward' }}</span></div>
                            <div class="bil">
                                <b>{{ $etiket[$h['tur'] ?? ''] ?? ($h['tur'] ?? '') }}</b>
                                <small>{{ $h['aciklama'] ?: $h['tarih'] }}</small>
                            </div>
                            <div class="tut">{{ $yesil ? '+' : '−' }}{{ $para($h['tutar'] ?? 0) }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="color:var(--sub); font-size:13px">Henüz kayıt yok.</div>
                @endif
            </div>

            {{-- Kısayollar (role-aware) --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined" style="color:#F59E0B">bolt</span> Kısayollar</h3>
                <div class="pm-kisayol-grid">
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
                        <a href="/satinalma-ozet"><span class="material-icons-outlined" style="color:#7C3AED">space_dashboard</span> Satınalma Özeti</a>
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
@endsection

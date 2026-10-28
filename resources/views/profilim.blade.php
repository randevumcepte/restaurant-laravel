@extends('layout.app')
@section('rk_ikon', 'account_circle')
@section('baslik', 'Profilim')
@section('title', 'Profilim')

@php
    $rolAd = ['sahip'=>'Sahip','mudur'=>'Müdür','kasa'=>'Kasa','garson'=>'Garson','mutfak'=>'Mutfak','satinalmaci'=>'Satın Almacı'][$p->rol ?? ''] ?? ($p->rol ?? 'Personel');
    $rolRenk = ['sahip'=>'#7C3AED','mudur'=>'#4F46E5','kasa'=>'#F59E0B','garson'=>'#10B981','mutfak'=>'#EF4444','satinalmaci'=>'#14B8A6'][$p->rol ?? ''] ?? '#64748B';
    $bosHarf = '';
    foreach (preg_split('/\s+/', trim((string)($p->ad ?? ''))) as $parca) { if ($parca !== '' && mb_strlen($bosHarf) < 2) $bosHarf .= mb_strtoupper(mb_substr($parca, 0, 1)); }
    if ($bosHarf === '') $bosHarf = '?';
    $kalanIzin = max(0, (float)$hak - (float)$kullanilan);
    $sureBicim = function ($dk) {
        $dk = (int) $dk; $s = intdiv($dk, 60); $m = $dk % 60;
        return $s > 0 ? "{$s}s {$m}dk" : "{$m}dk";
    };
    $para = fn($v) => '₺' . number_format((float)$v, 0, ',', '.');
@endphp

@section('content')
<style>
    .pm-wrap { max-width: 980px; }
    .pm-head { background: linear-gradient(135deg, {{ $rolRenk }}, {{ $rolRenk }}bb); color: #fff; border-radius: 14px;
        padding: 18px 20px; display: flex; gap: 16px; align-items: center; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .pm-ava { width: 68px; height: 68px; border-radius: 50%; background: rgba(255,255,255,0.22); border: 2px solid #fff;
        display:flex; align-items:center; justify-content:center; font-size:26px; font-weight:bold; overflow:hidden; }
    .pm-ava img { width:100%; height:100%; object-fit:cover; }
    .pm-head h2 { margin: 0 0 6px; font-size: 22px; font-weight: 700; }
    .pm-chips { display: flex; flex-wrap: wrap; gap: 6px; }
    .pm-chip { background: rgba(255,255,255,0.22); color: #fff; padding: 3px 9px; border-radius: 10px; font-size: 11.5px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 4px; }
    .pm-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 16px; }
    .pm-ozet { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 12px 14px; }
    .pm-ozet .et { color: var(--sub); font-size: 11.5px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
    .pm-ozet .dg { color: var(--ink); font-size: 20px; font-weight: 700; margin-top: 4px; }
    .pm-ozet .al { color: var(--sub2); font-size: 11px; margin-top: 2px; }
    .pm-ozet .ikon { padding: 4px; border-radius: 6px; font-size: 13px; }
    .pm-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 820px) { .pm-cols { grid-template-columns: 1fr; } }
    .pm-blok { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; }
    .pm-blok h3 { margin: 0 0 10px; font-size: 14px; color: var(--ink); font-weight: 700; display:flex; align-items:center; gap: 6px; }
    .pm-blok h3 .material-icons-outlined { font-size: 17px; color: var(--mor1); }
    .pm-satir { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; border-bottom: 1px dashed var(--line); font-size: 13px; }
    .pm-satir:last-child { border-bottom: 0; }
    .pm-satir .k { color: var(--sub); display: flex; align-items: center; gap: 6px; }
    .pm-satir .k .material-icons-outlined { font-size: 15px; color: var(--sub2); }
    .pm-satir .v { color: var(--ink); font-weight: 600; }
    .pm-hareket { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--line); }
    .pm-hareket:last-child { border-bottom: 0; }
    .pm-hareket .ok { padding: 5px; border-radius: 7px; display:flex; }
    .pm-hareket .ok .material-icons-outlined { font-size: 14px; }
    .pm-hareket .bil { flex: 1; }
    .pm-hareket .bil b { color: var(--ink); font-size: 13px; }
    .pm-hareket .bil small { color: var(--sub); font-size: 11px; display: block; }
    .pm-hareket .tut { font-weight: 700; font-size: 14px; }
    .pm-ok { color: #10B981; } .pm-ok .ok { background: rgba(16,185,129,0.14); color: #10B981; }
    .pm-no { color: #EF4444; } .pm-no .ok { background: rgba(239,68,68,0.14); color: #EF4444; }
    .pm-rozet { display: inline-flex; align-items: center; gap: 5px; padding: 6px 10px; border-radius: 20px; margin: 2px; font-size: 12px; font-weight: 700; }
    .pm-form-sat { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 8px; }
    @media (max-width: 620px) { .pm-form-sat { grid-template-columns: 1fr; } }
    .pm-inp { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--ink); font-size: 13px; }
    .pm-inp:focus { outline: none; border-color: var(--mor1); }
    .pm-lbl { color: var(--sub); font-size: 11.5px; font-weight: 600; margin-bottom: 3px; display: block; }
    .pm-cihaz-sat { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--line); }
    .pm-cihaz-sat:last-child { border-bottom: 0; }
    .pm-cihaz-sat .material-icons-outlined { color: var(--sub2); font-size: 18px; }
    .pm-cikis-btn { background: #F43F5E; }
</style>

<div class="rk-body pm-wrap">
    @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
    @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

    {{-- ÜST HEADER --}}
    <div class="pm-head">
        <div class="pm-ava">
            @if (!empty($p->foto) && str_starts_with((string)$p->foto, 'data:image'))
                <img src="{{ $p->foto }}" alt="avatar">
            @else
                {{ $bosHarf }}
            @endif
        </div>
        <div style="flex:1; min-width:0">
            <h2>{{ $p->ad ?: 'Personel' }}</h2>
            <div class="pm-chips">
                <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">badge</span>{{ $rolAd }}</span>
                @if (!empty($subeRow->ad))
                    <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">store</span>{{ $subeRow->ad }}</span>
                @endif
                @if (!empty($p->ise_baslama))
                    <span class="pm-chip"><span class="material-icons-outlined" style="font-size:12px">event_available</span>{{ \Carbon\Carbon::parse($p->ise_baslama)->isoFormat('DD MMM YYYY') }}'den beri</span>
                @endif
                @if ($mesai['acik'])
                    <span class="pm-chip" style="background:rgba(16,185,129,0.5)"><span class="material-icons-outlined" style="font-size:12px">radio_button_checked</span>Mesai açık · {{ $sureBicim($mesai['dakika']) }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- 4 ÖZET KART --}}
    <div class="pm-grid">
        <div class="pm-ozet">
            <div class="et"><span class="material-icons-outlined ikon" style="background:rgba(79,70,229,0.14); color:#4F46E5">calendar_month</span> İzin bakiye</div>
            <div class="dg">{{ number_format($kalanIzin, 1, ',', '') }} gün</div>
            <div class="al">Hak {{ number_format((float)$hak, 1, ',', '') }} · Kullanılan {{ number_format((float)$kullanilan, 1, ',', '') }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="material-icons-outlined ikon" style="background:rgba(245,158,11,0.14); color:#F59E0B">account_balance_wallet</span> Bu ay net</div>
            <div class="dg">{{ $para($hesap['net']) }}</div>
            <div class="al">Hakediş {{ $para($hesap['hakedis']) }} · Avans {{ $para($hesap['avans']) }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="material-icons-outlined ikon" style="background:rgba(14,184,166,0.14); color:#14B8A6">inventory_2</span> Zimmet</div>
            <div class="dg">{{ $sicil['zimmet'] }} öğe</div>
            <div class="al">Belge {{ $sicil['belge'] }} · Eğitim {{ $sicil['egitim'] }}</div>
        </div>
        <div class="pm-ozet">
            <div class="et"><span class="material-icons-outlined ikon" style="background:rgba(239,68,68,0.14); color:#EF4444">warning_amber</span> SKT yaklaşan</div>
            <div class="dg">{{ $sicil['skt_yaklasan'] }}</div>
            <div class="al">30 gün içinde biten belge</div>
        </div>
    </div>

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
                        <div><span class="pm-lbl">IBAN</span><input class="pm-inp" name="iban" value="{{ $p->iban }}" placeholder="TR…"></div>
                        <div><span class="pm-lbl">Mevcut IBAN</span><input class="pm-inp" value="{{ $ibanMask ?: '—' }}" disabled></div>
                    </div>
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">Acil durumda aranacak kişi</span><input class="pm-inp" name="acil_kisi" value="{{ $p->acil_kisi }}"></div>
                        <div><span class="pm-lbl">Acil telefon</span><input class="pm-inp" name="acil_tel" value="{{ $p->acil_tel }}" placeholder="05xx…"></div>
                    </div>
                    <div style="margin-top:10px"><button class="rk-btn" style="background:var(--mor1)" type="submit"><span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet</button></div>
                </form>
            </div>

            {{-- Vardiya programı --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">event</span> Bu hafta vardiyam</h3>
                @if (count($vardiya))
                    @foreach ($vardiya as $v)
                        <div class="pm-satir">
                            <span class="k"><span class="material-icons-outlined">today</span>{{ $v['gun'] }} {{ $v['tarih_kisa'] }}</span>
                            <span class="v">{{ $v['bas'] }}–{{ $v['bit'] }}@if($v['pozisyon']) · <span style="color:var(--sub); font-weight:500">{{ $v['pozisyon'] }}</span>@endif</span>
                        </div>
                    @endforeach
                @else
                    <div style="color:var(--sub); font-size:13px; padding: 6px 0">Programda vardiya yok.</div>
                @endif
                <div style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap">
                    <a href="/ik-izin" class="rk-btn" style="background:#14B8A6"><span class="material-icons-outlined" style="font-size:16px">beach_access</span> İzin iste</a>
                    <a href="/puantaj" class="rk-btn" style="background:#4F46E5"><span class="material-icons-outlined" style="font-size:16px">history</span> Puantajım</a>
                </div>
            </div>

            {{-- Bağlı cihazlar --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">devices</span> Oturumlarım ({{ count($cihazlar) }})</h3>
                @if (count($cihazlar))
                    @foreach ($cihazlar as $c)
                        <div class="pm-cihaz-sat">
                            <div style="display:flex; align-items:center; gap:9px">
                                <span class="material-icons-outlined">{{ $c['platform']==='ios' ? 'phone_iphone' : ($c['platform']==='android' ? 'phone_android' : 'devices') }}</span>
                                <div>
                                    <div style="font-size:12.5px; color:var(--ink); font-weight:600">{{ strtoupper($c['platform'] ?: 'Cihaz') }} · <span style="color:var(--sub); font-weight:500">{{ $c['token_son'] }}</span></div>
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
                    <div style="margin-bottom:8px"><span class="pm-lbl">Mevcut PIN</span><input class="pm-inp" type="password" name="eski" maxlength="8" required inputmode="numeric" pattern="[0-9]*"></div>
                    <div class="pm-form-sat">
                        <div><span class="pm-lbl">Yeni PIN (4-8 hane)</span><input class="pm-inp" type="password" name="yeni" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}"></div>
                        <div><span class="pm-lbl">Yeni PIN (tekrar)</span><input class="pm-inp" type="password" name="yeni2" maxlength="8" required inputmode="numeric" pattern="[0-9]{4,8}"></div>
                    </div>
                    <div style="margin-top:10px"><button class="rk-btn" style="background:#8B5CF6" type="submit"><span class="material-icons-outlined" style="font-size:16px">lock_reset</span> PIN'i değiştir</button></div>
                </form>
            </div>

            {{-- Hareketler defteri --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">receipt_long</span> Hareketler defteri</h3>
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

            {{-- Kısayollar --}}
            <div class="pm-blok">
                <h3><span class="material-icons-outlined">bolt</span> Kısayollar</h3>
                <div style="display:flex; flex-wrap:wrap; gap:8px">
                    <a href="/bildirim-ayar" class="rk-btn" style="background:#0EA5E9"><span class="material-icons-outlined" style="font-size:16px">notifications_active</span> Bildirim ayarları</a>
                    @if (in_array(($p->rol ?? ''), ['garson','sahip','mudur']))
                        <a href="/garson-performans" class="rk-btn" style="background:#10B981"><span class="material-icons-outlined" style="font-size:16px">emoji_events</span> Performansım</a>
                    @endif
                    @if (in_array(($p->rol ?? ''), ['sahip','mudur']))
                        <a href="/personel" class="rk-btn" style="background:#4F46E5"><span class="material-icons-outlined" style="font-size:16px">badge</span> Personel yönetimi</a>
                    @endif
                    <a href="/bagli-cihazlar" class="rk-btn"><span class="material-icons-outlined" style="font-size:16px">devices_other</span> Bağlı cihazlar</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

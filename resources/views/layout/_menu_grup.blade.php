@php
    $gorunur = array_values(array_filter($items, fn ($it) => _menuOgeGorunur($_pp, $_harita, $it))); // routes/panel_menu.php
    $grupAktif = !empty($_kasiyerMenu) || collect($gorunur)->contains(fn ($it) => $aktifMi($it[0]));
    // Başlık ikonu ve kapalı gruptaki bekleyen iş sayısı (grup kapalıyken başlıkta görünür)
    $grpIkon = _menuGrupIkon($grupAd); // routes/panel_menu.php
    $grpSay = 0; foreach ($gorunur as $_gi) $grpSay += (int) ($_menuBadge[$_gi[0]] ?? 0);
@endphp
@if ($grupAd === '__PIN__' && count($gorunur))
    <div class="rk-pin-pano">
        @foreach ($gorunur as [$url, $ikon, $ad, $renk])
            @php $aktif = $aktifMi($url); $badge = $_menuBadge[$url] ?? 0; @endphp
            <a href="{{ $url }}" class="rk-pin{{ $aktif ? ' aktif' : '' }}">
                <span class="pi"><span class="material-icons-outlined">{{ $ikon }}</span>@if ($badge > 0)<span class="rk-badge{{ $badge > 99 ? ' cok' : '' }}">{{ $_badgeMetin($badge) }}</span>@endif</span>
                <span class="pt">{{ $ad }}</span>
                <span class="material-icons-outlined pok">chevron_right</span>
            </a>
        @endforeach
    </div>
@elseif (count($gorunur))
    @if (empty($basliksiz))
    <div class="rk-grp{{ $grupAktif ? '' : ' kapali' }}" data-grp="{{ $grupAd }}" onclick="rkGrpTog(this)"><span class="material-icons-outlined rk-grp-ik">{{ $grpIkon }}</span><span class="rk-grp-ad">{{ ['Departman Operasyon' => 'Operasyon'][$grupAd] ?? $grupAd }}</span>@if ($grpSay > 0)<span class="rk-grp-say">{{ $_badgeMetin($grpSay) }}</span>@endif<span class="material-icons-outlined rk-grp-ok">expand_more</span></div>
    @endif
    @php
        // Rapor sayfaları (satın alma / mutfak) grubun içinde tıklanınca açılan "Raporlar" alt başlığında toplanır; işlem sayfalarıyla karışmasın
        $_rprMi = fn ($u) => _menuRaporMu($u); // satın alma / mutfak / departman raporları (routes/panel_menu.php)
        $_rpr = array_values(array_filter($gorunur, fn ($it) => $_rprMi($it[0])));
        $_rprAktif = collect($_rpr)->contains(fn ($it) => $aktifMi($it[0]));
        $_rprKondu = false;
    @endphp
    <div class="rk-grp-body">
        @foreach ($gorunur as [$url, $ikon, $ad, $renk])
            @if (count($_rpr) > 1 && $_rprMi($url))
                @continue($_rprKondu)
                @php $_rprKondu = true; @endphp
                <div class="rk-alt{{ $_rprAktif ? '' : ' kapali' }}" onclick="this.classList.toggle('kapali')"><span class="material-icons-outlined">insights</span><span class="rk-alt-ad">Raporlar</span><span class="rk-alt-say">{{ count($_rpr) }}</span><span class="material-icons-outlined rk-alt-ok">expand_more</span></div>
                <div class="rk-alt-body">
                    @foreach ($_rpr as [$url, $ikon, $ad, $renk])
                        <a href="{{ $url }}" class="{{ $aktifMi($url) ? 'aktif' : '' }}"><span class="rk-ikon-wrap"><span class="material-icons-outlined">{{ $ikon }}</span></span>{{ $ad }}</a>
                    @endforeach
                </div>
                @continue
            @endif
            @php $aktif = $aktifMi($url); $badge = $_menuBadge[$url] ?? 0; @endphp
            <a href="{{ $url }}" class="{{ $aktif ? 'aktif' : '' }}">
                <span class="rk-ikon-wrap"><span class="material-icons-outlined">{{ $ikon }}</span>@if ($badge > 0)<span class="rk-badge{{ $badge > 99 ? ' cok' : '' }}">{{ $_badgeMetin($badge) }}</span>@endif</span>
                {{ $ad }}
            </a>
        @endforeach
    </div>
@endif

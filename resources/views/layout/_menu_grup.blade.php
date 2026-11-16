@php
    $gorunur = array_values(array_filter($items, function ($it) use ($_pp, $_harita) {
        if (!$_pp) return true;
        if (in_array(($_pp->rol ?? ''), ['sahip', 'mudur'])) return true;     // yöneticiler her şeyi görür
        if (\Illuminate\Support\Str::startsWith($it[0], '/departman-brief')) return _departmanBriefLinkAcik($_pp, $it[0]); // başka departmanın brifingi görünmez
        if ($it[0] === '/wa-hatlar') return _waHatGorur($_pp); // hattı olmayana boş sayfa göstermesin
        if (($_ai = _aiAsistanLinkAcik($_pp, $it[0])) !== null) return $_ai; // AI izni + departman yetkisi (şefe İK AI görünmesin)
        if ($it[0] === '/talep-onaylarim') return _talepUstYonetimMu($_pp); // sorumlu onayları Satın Alma Talepleri sayfasında (tek sayfa) yönetir
        if ($it[0] === '/demirbas' && function_exists('_dmYetki')) return (bool) _dmYetki($_pp); if ($it[0] === '/fiyat-anlasmalari' && function_exists('_faYetki')) return (bool) _faYetki($_pp); // görüntüle/düzenle yetkisi ya da sorumlu salt okur
        if (str_starts_with($it[0], '/satinalma-raporlar/')) return _panelYetkiVar($_pp, 'gor_satinalma'); // rapor kategorileri tek yetki
        if (str_starts_with($it[0], '/mutfak-raporlar/')) return function_exists('_mrErisim') && _mrErisim($_pp); // şef / sous şef / müdür / patron
        if (function_exists('_drYolErisim') && ($_dr = _drYolErisim($it[0], $_pp)) !== null) return $_dr; // departman raporları (İK, Finans, ...): yönetici / departman sorumlusu
        if ($it[0] === '/patron-ai?gm=1') return _panelYetkiVar($_pp, 'gor_copilot'); // Genel Müdür brifingi: /patron-ai ile aynı yetki
        if (in_array($it[0], ['/bildirim-ayar', '/profilim', '/ajanda'])) return true; // kişisel ajanda herkese açık
        if (isset($_harita[$it[0]])) return _panelYetkiVar($_pp, $_harita[$it[0]]); // haritadaki = yetkiye göre
        return false;                                                          // haritasız = kısıtlı role gizle
    }));
    $grupAktif = !empty($_kasiyerMenu) || collect($gorunur)->contains(fn ($it) => $aktifMi($it[0]));
    // Başlık ikonu ve kapalı gruptaki bekleyen iş sayısı (grup kapalıyken başlıkta görünür)
    $grpIkon = ['Panel' => 'dashboard', 'Satış & Servis' => 'point_of_sale', 'Departman Operasyon' => 'apartment', 'Menü & Ürünler' => 'restaurant_menu',
        'Satın Alma' => 'shopping_cart', 'Fatura & Fiyat' => 'receipt_long', 'Tedarikçi & Analiz' => 'local_shipping', 'Stok & Üretim' => 'inventory_2',
        'Raporlar & Analizler' => 'insights', 'İnsan Kaynakları' => 'badge', 'Finans / Muhasebe' => 'account_balance', 'Mutfak / BOH' => 'soup_kitchen',
        'Pazarlama' => 'campaign', 'Rezervasyon Satış' => 'event_seat', 'AI Santral' => 'support_agent', 'CRM / Müşteri İlişkileri' => 'diversity_3',
        'Kalite / HACCP' => 'verified', 'IT / Bilgi İşlem' => 'computer', 'Ajanda' => 'event_note', 'Patron AI' => 'auto_awesome', 'Ayarlar & Entegrasyon' => 'settings'][$grupAd] ?? 'folder_open';
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
        $_rprMi = fn ($u) => str_starts_with($u, '/satinalma-raporlar/') || str_starts_with($u, '/mutfak-raporlar/');
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

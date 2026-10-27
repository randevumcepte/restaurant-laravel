@extends('layout.app')
@section('title', 'Paket Siparişler')
@section('rk_ikon', 'two_wheeler')
@section('baslik', 'Paket Siparişler (' . $aktif->count() . ')')

@section('rk_araclar')
<a href="/kurye" class="rk-btn" style="background:#10B981;text-decoration:none;color:#fff">
    <span class="material-icons-outlined" style="font-size:18px">map</span> Canlı Kurye Haritası
</a>
@endsection

@push('head')
<style>
#pk{--mor:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --gold:#F6CE63;--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #pk{--card:#161d2e;--card2:#1b2235;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}

#pk .grup-baslik{display:flex;align-items:center;gap:8px;padding:4px 0 8px;margin-top:4px}
#pk .grup-serit{width:4px;height:18px;border-radius:2px;flex:none}
#pk .grup-ad{font-size:15px;font-weight:700;color:var(--ink)}
#pk .grup-say{padding:2px 10px;border-radius:20px;font-size:12px;font-weight:700}

#pk table{width:100%;border-collapse:collapse}
#pk .tbl-wrap{background:var(--card);border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-bottom:18px}
#pk thead tr{background:var(--card2)}
#pk thead th{padding:11px 14px;font-size:12px;font-weight:700;color:var(--sub);text-align:left;white-space:nowrap}
#pk thead th.r{text-align:right}
#pk tbody tr{border-top:1px solid var(--line)}
#pk tbody td{padding:10px 14px;font-size:13px;color:var(--ink);vertical-align:middle}

#pk .rozet{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;color:#fff;white-space:nowrap}
#pk .rozet-outline{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;border:1px solid var(--line);color:var(--sub2);background:transparent;white-space:nowrap}

#pk .odeme-rozet{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:8px;font-size:11.5px;font-weight:700;border-width:1px;border-style:solid;white-space:nowrap}
#pk .sure-rozet{display:inline-flex;align-items:center;gap:3px;padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;white-space:nowrap}

#pk .btn{display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;border:none;white-space:nowrap;line-height:1}
#pk .btn-dolu{color:#fff}
#pk .btn-outline{background:var(--card);border:1px solid var(--line);color:var(--sub2)}
#pk .btn-outline:hover{background:var(--card2)}

#pk .bos{text-align:center;color:var(--sub);padding:48px;font-size:14px}
#pk .material-icons-outlined{font-size:inherit;vertical-align:middle;line-height:1}

#pk .musteri-ad{font-size:13px;font-weight:600;color:var(--ink)}
#pk .kurye-str{font-size:12px;color:var(--mavi);margin-top:2px}
#pk .adres-txt{font-size:12px;color:var(--sub);max-width:200px}
#pk .iade-sebep{font-size:11px;color:var(--kirmizi);font-weight:600}
#pk .tutar-txt{font-size:13px;font-weight:700;color:var(--ink);text-align:right;white-space:nowrap}

#pk .isl-btns{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end}

#pk .ozet-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:20px}
#pk .ozet-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 16px}
#pk .ozet-kart .v{font-size:24px;font-weight:900;color:var(--ink)}
#pk .ozet-kart .l{font-size:12px;color:var(--sub);margin-top:2px}

#pk .yenile-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--sub);font-size:13px;font-weight:600;cursor:pointer}
#pk .yenile-btn:hover{background:var(--card2)}
</style>
@endpush

@section('content')
@php
    $simdi = \Carbon\Carbon::now();
    // Aşama hesapla ve süre ekle (API endpoint mantığıyla birebir)
    $liste = $aktif->map(function ($s) use ($simdi) {
        $dk = $s->acilis ? (int) round(\Carbon\Carbon::parse($s->acilis)->diffInMinutes($simdi)) : 0;
        $s->gecen_dk = $dk;
        $s->gecen_metin = $dk < 60 ? ($dk . ' dk') : (intdiv($dk, 60) . ' sa ' . ($dk % 60) . ' dk');
        $th = $s->tahsilat_durumu ?? null;
        if ($th === 'kuryede') { $s->asama = 'tahsil_bekliyor'; }
        elseif ($th === 'iade') { $s->asama = 'iade'; }
        else { $s->asama = $s->teslimat_durumu ?? 'hazirlaniyor'; }
        return $s;
    });

    // Gruplar (Flutter _masaustuTablo ile birebir)
    $gruplar = [
        ['baslik' => 'Yeni Sipariş',                       'asamalar' => ['hazirlaniyor', '', 'yeni'],    'renk' => '#2563EB'],
        ['baslik' => 'Yola Çıkarılması Gereken',           'asamalar' => ['hazir'],                       'renk' => '#EA580C'],
        ['baslik' => 'Yolda (Kurye Teslim Edecek)',        'asamalar' => ['yolda'],                       'renk' => '#16A34A'],
        ['baslik' => '💰 Tahsil Edilecek (Para Kuryede)',  'asamalar' => ['tahsil_bekliyor'],             'renk' => '#059669'],
        ['baslik' => '⚠️ Teslim Edilemedi',                'asamalar' => ['iade'],                        'renk' => '#DC2626'],
    ];

    // Platform renkleri (Flutter _platRenk ile birebir)
    function pkPlatRenk($p) {
        return match ($p) {
            'getir'        => '#7C3AED',
            'yemeksepeti'  => '#E11D48',
            'trendyol'     => '#EA580C',
            'migros'       => '#16A34A',
            'whatsapp'     => '#059669',
            default        => '#64748B',
        };
    }

    // Ödeme stili (Flutter odemeStil ile birebir)
    function pkOdemeStil($y) {
        return match ($y) {
            'online' => ['ad' => 'Online Ödendi',   'renk' => '#10B981', 'ikon' => 'check_circle',   'tahsilat' => false],
            'kredi'  => ['ad' => 'Kart · Kapıda',   'renk' => '#2563EB', 'ikon' => 'credit_card',    'tahsilat' => true],
            default  => ['ad' => 'Nakit · Kapıda',  'renk' => '#D97706', 'ikon' => 'payments',        'tahsilat' => true],
        };
    }

    // Süre rengi (Flutter _sureRenk ile birebir)
    function pkSureRenk($dk) {
        if ($dk >= 45) return '#DC2626';
        if ($dk >= 25) return '#EA580C';
        return '#16A34A';
    }

    // Hex rengi rgba'ya çevir opacity ile
    function pkHex2Rgba($hex, $a) {
        $hex = ltrim($hex, '#');
        [$r, $g, $b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
        return "rgba($r,$g,$b,$a)";
    }
@endphp

<div id="pk">

    {{-- Üst özet kartları --}}
    <div class="ozet-bar">
        <div class="ozet-kart">
            <div class="v" style="color:#4F46E5">{{ $aktif->count() }}</div>
            <div class="l">Aktif Sipariş</div>
        </div>
        <div class="ozet-kart">
            <div class="v">{{ $bugunPaket }}</div>
            <div class="l">Bugün Toplam</div>
        </div>
        <div class="ozet-kart">
            <div class="v" style="color:#D97706">{{ $aktif->where('teslimat_durumu', 'yolda')->count() }}</div>
            <div class="l">Yolda</div>
        </div>
        <div class="ozet-kart">
            <div class="v" style="color:#DC2626">{{ $liste->where('asama', 'tahsil_bekliyor')->count() }}</div>
            <div class="l">Tahsil Bekliyor</div>
        </div>
        <div class="ozet-kart" style="align-self:center">
            <button class="yenile-btn" onclick="location.reload()">
                <span class="material-icons-outlined" style="font-size:16px">refresh</span>Yenile
            </button>
        </div>
    </div>

    {{-- Boş durum --}}
    @if ($liste->isEmpty())
        <div class="tbl-wrap"><div class="bos">Şu an aktif paket sipariş yok.</div></div>
    @endif

    {{-- Her grup için tablo --}}
    @foreach ($gruplar as $grup)
        @php
            $grp = $liste->filter(fn ($s) => in_array($s->asama, $grup['asamalar']))->values();
        @endphp
        @if ($grp->isEmpty()) @continue @endif

        <div class="grup-baslik">
            <div class="grup-serit" style="background:{{ $grup['renk'] }}"></div>
            <span class="grup-ad">{{ $grup['baslik'] }}</span>
            <span class="grup-say" style="background:{{ pkHex2Rgba($grup['renk'], 0.12) }};color:{{ $grup['renk'] }}">{{ $grp->count() }}</span>
        </div>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:10%">Platform</th>
                        <th style="width:14%">Müşteri</th>
                        <th style="width:20%">Adres</th>
                        <th class="r" style="width:8%">Tutar</th>
                        <th style="width:14%">Ödeme</th>
                        <th style="width:8%">Süre</th>
                        <th class="r" style="width:26%">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grp as $s)
                        @php
                            $plat     = trim((string) ($s->platform ?? ''));
                            $platVar  = $plat !== '' && $plat !== '-';
                            $platRenk = pkPlatRenk($plat);
                            $oy       = pkOdemeStil($s->odeme_yontemi ?? null);
                            $odeRenk  = $oy['renk'];
                            $sureRenk = pkSureRenk((int) $s->gecen_dk);
                            $asama    = $s->asama;
                            $id       = (int) $s->id;
                        @endphp
                        <tr>
                            {{-- Platform rozeti --}}
                            <td>
                                @if ($platVar)
                                    <span class="rozet" style="background:{{ $platRenk }}">{{ strtoupper($plat) }}</span>
                                @else
                                    <span class="rozet-outline">
                                        <span class="material-icons-outlined" style="font-size:11px">shopping_bag</span>Paket
                                    </span>
                                @endif
                            </td>

                            {{-- Müşteri + kurye --}}
                            <td>
                                <div class="musteri-ad">{{ $s->musteri_ad ?? 'Müşteri' }}</div>
                                @if (!empty($s->kurye_ad))
                                    <div class="kurye-str">🛵 {{ $s->kurye_ad }}</div>
                                @endif
                            </td>

                            {{-- Adres + iade notu --}}
                            <td>
                                <div class="adres-txt">{{ $s->teslimat_adres ?? '-' }}</div>
                                @if ($asama === 'iade' && !empty($s->teslim_notu))
                                    <div class="iade-sebep">Sebep: {{ $s->teslim_notu }}</div>
                                @endif
                            </td>

                            {{-- Tutar --}}
                            <td>
                                <div class="tutar-txt">{{ number_format((float) $s->toplam, 0, ',', '.') }}TL</div>
                            </td>

                            {{-- Ödeme rozeti --}}
                            <td>
                                <span class="odeme-rozet"
                                      style="background:{{ pkHex2Rgba($odeRenk, 0.12) }};border-color:{{ pkHex2Rgba($odeRenk, 0.35) }};color:{{ $odeRenk }}">
                                    <span class="material-icons-outlined" style="font-size:13px">{{ $oy['ikon'] }}</span>
                                    {{ $oy['ad'] }}
                                </span>
                            </td>

                            {{-- Süre rozeti --}}
                            <td>
                                <span class="sure-rozet"
                                      style="background:{{ pkHex2Rgba($sureRenk, 0.12) }};color:{{ $sureRenk }}">
                                    <span class="material-icons-outlined" style="font-size:12px">schedule</span>
                                    {{ $s->gecen_metin }}
                                </span>
                            </td>

                            {{-- İşlem butonları (Flutter _aksiyonButonlari ile birebir) --}}
                            <td>
                                <div class="isl-btns"
                                     x-data="{
                                        call(ak, extra){
                                            const d={adisyon_id:{{ $id }}, durum:ak}; if(extra) Object.assign(d,extra);
                                            api('/paket/durum', d).then(r=>{ if(r&&r.ok===1) location.reload(); else alert('İşlem başarısız'); });
                                        },
                                        iptalOnay(){ var self=this; rkOnay('Bu paket siparişi iptal edilecek. Emin misiniz?', function(){ self.call('iptal'); }, 'var(--kirmizi)', 'İptal Et'); }
                                     }">

                                    @if ($asama === 'hazirlaniyor' || $asama === '' || $asama === 'yeni')
                                        <button class="btn btn-dolu" style="background:#16A34A" @click="call('kabul')">Kabul Et</button>
                                        <button class="btn btn-outline" @click="iptalOnay()">İptal</button>

                                    @elseif ($asama === 'hazir')
                                        <button class="btn btn-dolu" style="background:#EA580C" @click="call('yola')">Yola Çıkar</button>
                                        <button class="btn btn-outline" @click="iptalOnay()">İptal</button>

                                    @elseif ($asama === 'yolda')
                                        <button class="btn btn-dolu" style="background:#16A34A" @click="call('teslim')">Teslim + Tahsil</button>

                                    @elseif ($asama === 'tahsil_bekliyor')
                                        <button class="btn btn-dolu" style="background:#059669" @click="call('tahsil')">💰 Tahsil Et</button>

                                    @elseif ($asama === 'iade')
                                        <button class="btn btn-dolu" style="background:#EA580C" @click="call('yeniden')">Yeniden Gönder</button>
                                        <button class="btn btn-outline" @click="iptalOnay()">İptal</button>
                                    @endif

                                    <button class="btn btn-outline" onclick="pkDetay({{ $id }})">Detay</button>
                                    <button class="btn btn-outline" onclick="pkYazdir({{ $id }})">Yazdır</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    {{-- Platform dağılımı (30 gün) alt bilgi --}}
    @if ($platformDagilim->isNotEmpty())
        <div style="margin-top:8px;padding:16px;background:var(--card);border:1px solid var(--line);border-radius:14px">
            <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:10px">Platform Dağılımı
                <span style="font-size:12px;font-weight:400;color:var(--sub)">(son 30 gün)</span></div>
            <div style="display:flex;flex-wrap:wrap;gap:10px">
                @foreach ($platformDagilim as $p)
                    @php $pr = pkPlatRenk($p->platform ?? ''); @endphp
                    <span style="display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:8px;background:var(--card2);border:1px solid var(--line);font-size:12.5px">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $pr }};flex:none"></span>
                        <b style="color:{{ $pr }}">{{ strtoupper($p->platform ?? '-') }}</b>
                        <span style="color:var(--sub)">{{ $p->adet }} sipariş ·</span>
                        <b style="color:var(--yesil)">{{ number_format((float) $p->ciro, 0, ',', '.') }}TL</b>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

</div>

<script>
// Detay modalı
async function pkDetay(id) {
    const res = await api('/api/paket/' + id);
    const d = res?.siparis;
    if (!d) { alert('Sipariş bulunamadı.'); return; }
    const odeStil = {'online':['Online Ödendi','#10B981'],'kredi':['Kart · Kapıda','#2563EB']};
    const [odeAd, odeRenk] = odeStil[d.odeme_yontemi] || ['Nakit · Kapıda','#D97706'];
    const kalemler = (d.kalemler || []).map(k =>
        `<div style="display:flex;gap:10px;padding:6px 0;border-bottom:1px solid #e2e8f0;align-items:flex-start">
          <span style="background:#EEF2FF;color:#4F46E5;border-radius:6px;padding:1px 7px;font-size:12px;font-weight:700;flex:none">${Math.round(Number(k.adet))}×</span>
          <span style="flex:1;font-size:14px;font-weight:600">${k.urun_adi||'Ürün'}${k.not?`<div style="font-size:11px;color:#EA580C">Not: ${k.not}</div>`:''}</span>
          <span style="font-size:14px;font-weight:600">${Number(k.tutar||0).toLocaleString('tr')}TL</span>
        </div>`).join('');
    const html = `
      <div id="pkModal" onclick="if(event.target===this)this.remove()" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px">
        <div style="background:#fff;border-radius:16px;max-width:480px;width:100%;max-height:90vh;overflow-y:auto;padding:20px;position:relative">
          <button onclick="document.getElementById('pkModal').remove()" style="position:absolute;top:12px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;color:#64748b">✕</button>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;flex-wrap:wrap">
            ${d.platform&&d.platform!=='-'?`<span style="background:var(--platRenk,#64748b);color:#fff;border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700">${(d.platform||'').toUpperCase()}</span>`:''}
            <span style="margin-left:auto;font-size:13px;color:#64748b">${d.gecen_metin||''}</span>
          </div>
          <div style="font-size:18px;font-weight:700;margin-bottom:4px">${d.musteri||'Müşteri'}</div>
          ${d.platform_siparis_no?`<div style="font-size:12px;color:#64748b;margin-bottom:8px">Sipariş No: ${d.platform_siparis_no}</div>`:''}
          <div style="background:${odeRenk}18;border:1.5px solid ${odeRenk}66;border-radius:12px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:center;gap:12px">
            <span style="font-size:16px;font-weight:700;color:${odeRenk}">${odeAd}</span>
            <span style="margin-left:auto;font-size:16px;font-weight:700;color:${odeRenk}">${Number(d.toplam||0).toLocaleString('tr')}TL</span>
          </div>
          ${d.teslimat_adres?`<div style="font-size:13px;color:#64748b;margin-bottom:10px">📍 ${d.teslimat_adres}</div>`:''}
          ${d.telefon?`<div style="font-size:13px;color:#64748b;margin-bottom:10px">📞 ${d.telefon}</div>`:''}
          ${d.kurye?`<div style="font-size:13px;color:#4F46E5;margin-bottom:10px">🛵 ${d.kurye}</div>`:''}
          <div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px">Ürünler (${(d.kalemler||[]).length})</div>
          <div style="margin-bottom:12px">${kalemler||'<div style="color:#64748b;font-size:13px">Ürün bilgisi yok.</div>'}</div>
          <div style="border-top:1px solid #e2e8f0;padding-top:10px">
            <div style="display:flex;justify-content:space-between;font-size:14px;padding:3px 0"><span style="color:#94a3b8">Ara Toplam</span><span>${Number(d.ara_toplam||0).toLocaleString('tr')}TL</span></div>
            ${Number(d.indirim||0)>0?`<div style="display:flex;justify-content:space-between;font-size:14px;padding:3px 0"><span style="color:#94a3b8">İndirim</span><span style="color:#10B981">-${Number(d.indirim).toLocaleString('tr')}TL</span></div>`:''}
            <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:700;padding:6px 0"><span>Toplam</span><span>${Number(d.toplam||0).toLocaleString('tr')}TL</span></div>
          </div>
        </div>
      </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
}

// Yazdır: /api/paket/{id} uç noktasından veri çek
async function pkYazdir(id) {
    try {
        const res = await api('/api/paket/' + id);
        const d = res?.siparis;
        if (!d) { alert('Sipariş bulunamadı.'); return; }
        const w = window.open('', '_blank', 'width=400,height=600');
        const kalemler = (d.kalemler || []).map(k => `<tr><td>${Math.round(Number(k.adet))}×</td><td>${k.urun_adi||'Ürün'}</td><td align="right">${Number(k.tutar||0).toLocaleString('tr')}TL</td></tr>`).join('');
        w.document.write(`<html><head><title>Paket Fişi</title><style>body{font-family:monospace;font-size:13px;padding:16px}table{width:100%;border-collapse:collapse}td{padding:3px 4px}hr{border:1px dashed #999}.t{font-weight:bold}</style></head><body>
            <div style="text-align:center"><b>PAKET · ${(d.platform||'').toUpperCase()}</b><br>${d.acilis||''}</div><hr>
            <div>${d.musteri||''}</div><div style="color:#555;font-size:12px">${d.teslimat_adres||''}</div><hr>
            <table>${kalemler}</table><hr>
            <table><tr><td>Ara Toplam</td><td align="right">${Number(d.ara_toplam||0).toLocaleString('tr')}TL</td></tr>
            ${Number(d.indirim||0)>0?`<tr><td>İndirim</td><td align="right">-${Number(d.indirim).toLocaleString('tr')}TL</td></tr>`:''}
            <tr class="t"><td>TOPLAM</td><td align="right">${Number(d.toplam||0).toLocaleString('tr')}TL</td></tr></table><hr>
            <div style="text-align:center;font-size:12px">Afiyet olsun!</div>
        </body></html>`);
        w.document.close(); w.print();
    } catch(e) { alert('Yazdırma hatası'); }
}
</script>
@endsection

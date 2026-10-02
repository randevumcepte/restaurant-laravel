@extends('layout.app')
@section('title', 'ERP / Muhasebe Entegrasyonu')
@section('baslik', '📑 ERP / Muhasebe Entegrasyonu')

@section('content')
@php
    $tipler = [
        'excel' => "Genel / Muhasebeci Excel'i",
        'logo_dosya' => 'Logo — Aktarım Dosyası',
        'logo_api' => 'Logo — REST API (Tiger/GO)',
        'netsis_dosya' => 'Netsis — Aktarım Dosyası',
        'netsis_api' => 'Netsis — REST API (NetOpenX)',
        'entegrator' => 'e-Dönüşüm Entegratörü (Paraşüt vb.)',
    ];
    $seciliTip = $ayar->baglanti_tipi ?? 'excel';
    $bagli = $ayar && $ayar->aktif;
@endphp

<div x-data="{
        modal: false,
        tipler: @js($tipler),
        get tiplerArr() { return Object.entries(this.tipler); },
        f: {
            baglanti_tipi: '{{ $seciliTip }}',
            firma_kodu: @js($ayar->firma_kodu ?? ''),
            api_url: @js($ayar->api_url ?? ''),
            api_kullanici: @js($ayar->api_kullanici ?? ''),
            api_sifre: '',
            kdv_orani: {{ (int) ($ayar->kdv_orani ?? 10) }},
            aktif: {{ ($ayar->aktif ?? false) ? 'true' : 'false' }},
            hesap_plani: @js($hp)
        },
        period: 'ay', bas: '', bit: '',
        get isApi() { return this.f.baglanti_tipi.endsWith('_api'); },
        kaydet() { api('/muhasebe-entegrasyon/ayar-kaydet', this.f).then(() => location.reload()); },
        indir() {
            let u = '/muhasebe-entegrasyon/disa-aktar?';
            if (this.bas && this.bit) u += 'bas=' + this.bas + '&bit=' + this.bit;
            else u += 'period=' + this.period;
            window.location.href = u;
        }
    }">

    {{-- Baglanti durumu --}}
    <div class="rounded-2xl border p-5 mb-6 {{ $bagli ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }}">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="font-semibold text-slate-800 mb-1">
                    @if ($bagli) ✅ Aktif bağlantı: <span class="font-bold">{{ $tipler[$seciliTip] ?? $seciliTip }}</span>
                    @else ⚠️ Muhasebe bağlantısı seçilmedi — varsayılan <b>Genel Excel</b> ile dışa aktarabilirsiniz @endif
                </div>
                <div class="text-sm text-slate-500">
                    Satış + alış verisi <b>mahsup fişi</b> (CSV) olarak üretilir; muhasebeciniz Logo/Netsis/Excel'e tek tıkla aktarır.
                    @if ($ayar) · Satış KDV: %{{ (int) ($ayar->kdv_orani ?? 10) }}@endif
                </div>
            </div>
            <button @click="modal = true" class="shrink-0 bg-indigo-600 text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-indigo-700">Bağlantı Ayarları</button>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-6">

        {{-- Disa aktarim --}}
        <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 p-5">
            <div class="font-semibold text-slate-800 mb-1">Veri Aktarımı</div>
            <div class="text-sm text-slate-500 mb-4">Dönem seçin, mahsup fişini indirin (Logo / Netsis / Excel uyumlu CSV).</div>

            <div class="flex flex-wrap gap-2 mb-4">
                <template x-for="p in [['gun','Bugün'],['hafta','Son 7 Gün'],['ay','Son 30 Gün']]" :key="p[0]">
                    <button @click="period = p[0]; bas=''; bit=''"
                            class="px-4 py-2 rounded-xl text-sm font-semibold border transition"
                            :class="(period === p[0] && !bas) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'"
                            x-text="p[1]"></button>
                </template>
            </div>

            <div class="flex flex-wrap items-end gap-3 mb-5">
                <div>
                    <label class="block text-xs text-slate-400 font-semibold mb-1">Özel: Başlangıç</label>
                    <input type="date" x-model="bas" class="border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-400 font-semibold mb-1">Bitiş</label>
                    <input type="date" x-model="bit" class="border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button @click="indir()" class="bg-emerald-600 text-white text-sm font-semibold rounded-xl px-5 py-3 hover:bg-emerald-700">
                    ⬇️ Aktarım Dosyası İndir (CSV)
                </button>
                <span x-show="isApi" class="text-xs text-amber-600 font-medium">
                    REST API ile otomatik gönderim <b>Faz 2</b>'de; şimdilik dosyayı indirip aktarabilirsiniz.
                </span>
            </div>

            <div class="mt-5 text-xs text-slate-400 leading-relaxed">
                Fiş çift taraflıdır (Borç = Alacak): satış tahsilatı ödeme tipine göre Kasa/POS/Banka'ya,
                matrah {{ $hp['satis'] }} Yurtiçi Satışlar + {{ $hp['hesaplanan_kdv'] }} Hesaplanan KDV'ye;
                alışlar {{ $hp['mal'] }} Mal / {{ $hp['tedarikci'] }} Satıcılar hesaplarına yazılır.
            </div>
        </div>

        {{-- Hesap plani ozeti --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <div class="font-semibold text-slate-800 mb-3">Hesap Planı Eşlemesi</div>
            <div class="space-y-2 text-sm">
                @foreach ([
                    'kasa' => 'Kasa (Nakit)', 'pos' => 'Kredi Kartı (POS)', 'banka' => 'Banka / Online',
                    'satis' => 'Yurtiçi Satışlar', 'hesaplanan_kdv' => 'Hesaplanan KDV',
                    'mal' => 'İlk Madde / Mal', 'tedarikci' => 'Satıcılar (Tedarikçi)',
                ] as $k => $ad)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">{{ $ad }}</span>
                        <span class="font-mono font-semibold text-slate-800">{{ $hp[$k] ?? '—' }}</span>
                    </div>
                @endforeach
            </div>
            <button @click="modal = true" class="mt-4 w-full text-indigo-600 text-sm font-semibold border border-indigo-200 rounded-xl py-2 hover:bg-indigo-50">Düzenle</button>
        </div>
    </div>

    {{-- Aktarim gecmisi --}}
    <div class="mt-6 bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 font-semibold text-slate-800">Aktarım Geçmişi</div>
        @if ($gecmis->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-slate-400">Henüz aktarım yapılmadı.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-400 text-xs uppercase">
                    <tr>
                        <th class="text-left font-semibold px-5 py-2.5">Tarih</th>
                        <th class="text-left font-semibold px-5 py-2.5">Dönem</th>
                        <th class="text-left font-semibold px-5 py-2.5">Bağlantı</th>
                        <th class="text-right font-semibold px-5 py-2.5">Satır</th>
                        <th class="text-right font-semibold px-5 py-2.5">Borç</th>
                        <th class="text-right font-semibold px-5 py-2.5">Alacak</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gecmis as $g)
                        <tr class="border-t border-slate-50">
                            <td class="px-5 py-2.5 text-slate-500">{{ \Carbon\Carbon::parse($g->created_at)->format('d.m.Y H:i') }}</td>
                            <td class="px-5 py-2.5 text-slate-700">{{ $g->donem }}</td>
                            <td class="px-5 py-2.5 text-slate-500">{{ $tipler[$g->baglanti_tipi] ?? $g->baglanti_tipi }}</td>
                            <td class="px-5 py-2.5 text-right text-slate-700">{{ $g->satir }}</td>
                            <td class="px-5 py-2.5 text-right font-semibold text-slate-800">{{ number_format($g->borc_toplam, 0, ',', '.') }} ₺</td>
                            <td class="px-5 py-2.5 text-right font-semibold text-slate-800">{{ number_format($g->alacak_toplam, 0, ',', '.') }} ₺</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Ayar modali --}}
    <div x-show="modal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="modal = false">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="font-bold text-lg text-slate-800">Bağlantı Ayarları</div>
                <button @click="modal = false" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
            </div>

            <label class="block text-sm font-semibold text-slate-600 mb-1">Muhasebe Bağlantı Tipi</label>
            <select x-model="f.baglanti_tipi" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm mb-4">
                <template x-for="t in tiplerArr" :key="t[0]">
                    <option :value="t[0]" x-text="t[1]"></option>
                </template>
            </select>

            <div x-show="isApi" class="mb-4 space-y-3 rounded-xl bg-slate-50 border border-slate-200 p-3">
                <div class="text-xs text-slate-500">ERP REST API bilgileri (Faz 2'de otomatik gönderim için):</div>
                <input x-model="f.api_url" placeholder="API adresi (ör. http://sunucu:port/api)" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <input x-model="f.api_kullanici" placeholder="API kullanıcı" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <input x-model="f.api_sifre" type="password" placeholder="API şifre (boş bırakılırsa değişmez)" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Firma/Şube Kodu</label>
                    <input x-model="f.firma_kodu" placeholder="(opsiyonel)" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Satış KDV %</label>
                    <input x-model.number="f.kdv_orani" type="number" min="0" max="99" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm">
                </div>
            </div>

            <div class="font-semibold text-slate-600 text-sm mb-2 mt-5">Hesap Planı Eşlemesi</div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <template x-for="hk in [['kasa','Kasa (Nakit)'],['pos','Kredi Kartı (POS)'],['banka','Banka / Online'],['satis','Yurtiçi Satışlar'],['hesaplanan_kdv','Hesaplanan KDV'],['mal','İlk Madde / Mal'],['tedarikci','Satıcılar']]" :key="hk[0]">
                    <div>
                        <label class="block text-xs text-slate-400 font-semibold mb-1" x-text="hk[1]"></label>
                        <input x-model="f.hesap_plani[hk[0]]" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono">
                    </div>
                </template>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600 mb-5">
                <input type="checkbox" x-model="f.aktif" class="rounded"> Bu bağlantıyı aktif olarak işaretle
            </label>

            <div class="flex gap-3">
                <button @click="modal = false" class="flex-1 border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl py-2.5 hover:bg-slate-50">Vazgeç</button>
                <button @click="kaydet()" class="flex-1 bg-indigo-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-indigo-700">Kaydet</button>
            </div>
        </div>
    </div>
</div>
@endsection

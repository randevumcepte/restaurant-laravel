@extends('layout.app')
@section('title', 'E-Dönüşüm')
@section('baslik', '🧾 E-Dönüşüm (E-Arşiv / E-Fatura)')

@section('content')
@php $bagli = $ayar && $ayar->aktif && $ayar->api_key; @endphp

<div x-data="{ modal: false, f: {
        entegrator: '{{ $ayar->entegrator ?? 'parasut' }}', api_key: '{{ $ayar->api_key ?? '' }}', api_secret: '',
        firma_unvan: @js($ayar->firma_unvan ?? ''), vkn_tckn: '{{ $ayar->vkn_tckn ?? '' }}',
        vergi_dairesi: '{{ $ayar->vergi_dairesi ?? '' }}', adres: @js($ayar->adres ?? ''),
        mali_muhur_yuklu: {{ ($ayar->mali_muhur_yuklu ?? false) ? 'true' : 'false' }}, aktif: {{ ($ayar->aktif ?? false) ? 'true' : 'false' }},
        fis_modu: '{{ $ayar->fis_modu ?? 'earsiv' }}', okc_marka: '{{ $ayar->okc_marka ?? 'ingenico' }}',
        okc_ip: '{{ $ayar->okc_ip ?? '' }}', okc_port: '{{ $ayar->okc_port ?? '' }}', okc_aktif: {{ ($ayar->okc_aktif ?? false) ? 'true' : 'false' }} },
        kaydet() { api('/edonusum/ayar-kaydet', this.f).then(() => location.reload()); } }">

    {{-- Baglanti durumu --}}
    <div class="rounded-2xl border p-5 mb-6 {{ $bagli ? 'bg-emerald-50 border-emerald-200' : 'bg-amber-50 border-amber-200' }}">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="font-semibold text-slate-800 mb-1">
                    @if ($bagli) ✅ Entegratör bağlı: <span class="uppercase">{{ $ayar->entegrator }}</span>
                    @else ⚠️ Entegratör bağlı değil — faturalar <b>simülasyon</b> olarak kaydedilir @endif
                </div>
                <div class="text-sm text-slate-500">
                    @if ($ayar) {{ $ayar->firma_unvan }} · VKN: {{ $ayar->vkn_tckn }} · Mali mühür: {{ $ayar->mali_muhur_yuklu ? 'yüklü' : 'yok' }} · Fiş modu: <b>{{ ($ayar->fis_modu ?? 'earsiv') === 'okc' ? 'Yazarkasa (ÖKC)' : 'e-Arşiv' }}</b>
                    @else Firma bilgisi girilmedi @endif
                </div>
            </div>
            <button @click="modal = true" class="shrink-0 bg-indigo-600 text-white text-sm font-semibold rounded-xl px-4 py-2.5 hover:bg-indigo-700">Ayarları Düzenle</button>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Belge</div><div class="text-2xl font-bold text-indigo-600 mt-1">{{ $bugunAdet }}</div></div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Bugün Tutar</div><div class="text-xl font-bold text-emerald-600 mt-1">{{ number_format($bugunTutar, 0, ',', '.') }} ₺</div></div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">30 Gün Belge</div><div class="text-2xl font-bold text-slate-700 mt-1">{{ $ayAdet }}</div></div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4"><div class="text-xs text-slate-400 uppercase font-semibold">Yazarkasa (ÖKC)</div><div class="text-sm font-bold text-emerald-600 mt-2">● Bağlı (demo)</div></div>
    </div>

    <p class="text-sm text-slate-500 mb-3">Kesilen e-Belgeler (ödeme + adisyondan "Fatura Oluştur" ile):</p>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr><th class="text-left px-4 py-2">Belge No</th><th class="text-left px-4 py-2">Tip</th><th class="text-left px-4 py-2">Alıcı</th>
                    <th class="text-left px-4 py-2">Tarih</th><th class="text-right px-4 py-2">Matrah</th><th class="text-right px-4 py-2">KDV</th>
                    <th class="text-right px-4 py-2">Toplam</th><th class="text-center px-4 py-2">Durum</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($belgeler as $b)
                    <tr>
                        <td class="px-4 py-2 font-mono text-xs text-indigo-700">{{ $b->belge_no }}</td>
                        @php
                            $tipMap = ['e_fatura' => ['e-Fatura', 'bg-violet-100 text-violet-700'], 'e_arsiv' => ['e-Arşiv', 'bg-sky-100 text-sky-700'], 'okc_fis' => ['Yazarkasa', 'bg-amber-100 text-amber-700']];
                            [$tipEt, $tipRenk] = $tipMap[$b->tip] ?? ['e-Arşiv', 'bg-sky-100 text-sky-700'];
                        @endphp
                        <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded {{ $tipRenk }}">{{ $tipEt }}</span></td>
                        <td class="px-4 py-2 text-slate-600">{{ $b->alici_unvan }}</td>
                        <td class="px-4 py-2 text-slate-400 text-xs">{{ \Illuminate\Support\Carbon::parse($b->created_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-2 text-right text-slate-500">{{ number_format((float) $b->matrah, 0, ',', '.') }} ₺</td>
                        <td class="px-4 py-2 text-right text-slate-500">{{ number_format((float) $b->kdv, 0, ',', '.') }} ₺</td>
                        <td class="px-4 py-2 text-right font-semibold text-slate-700">{{ number_format((float) $b->toplam, 0, ',', '.') }} ₺</td>
                        <td class="px-4 py-2 text-center">
                            @php $dr = ['onaylandi' => 'bg-emerald-100 text-emerald-700', 'gonderildi' => 'bg-sky-100 text-sky-700', 'simulasyon' => 'bg-slate-100 text-slate-500', 'hata' => 'bg-rose-100 text-rose-700']; @endphp
                            <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $dr[$b->durum] ?? 'bg-slate-100 text-slate-500' }}">{{ $b->durum }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Henüz belge yok. POS'ta bir adisyondan "Fatura Oluştur" deneyin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-400 mt-4">ℹ️ Model A: Restoran kendi entegratör (Paraşüt/İzibiz) anahtarını yukarıdan girer. Anahtar aktifken belgeler <b>gerçek</b> kesilir; boşken simülasyon. Belgeler restoranın <b>mali mührüyle</b> imzalanır. KDV %10 (yeme-içme) varsayılır.</p>

    {{-- Ayar modal --}}
    <div x-show="modal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4" @click.self="modal = false">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <h3 class="font-bold text-slate-800 mb-4">E-Dönüşüm Ayarları</h3>
            <label class="block text-xs text-slate-500 mb-1">Entegratör</label>
            <select x-model="f.entegrator" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
                <option value="parasut">Paraşüt</option><option value="izibiz">İzibiz</option>
                <option value="uyumsoft">Uyumsoft</option><option value="nes">Nes Bilgi</option><option value="foriba">Foriba/Sovos</option>
            </select>
            <label class="block text-xs text-slate-500 mb-1">API Anahtarı</label>
            <input x-model="f.api_key" placeholder="Entegratörden aldığınız API anahtarı" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
            <label class="block text-xs text-slate-500 mb-1">Firma Ünvanı</label>
            <input x-model="f.firma_unvan" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="block text-xs text-slate-500 mb-1">VKN / TCKN</label><input x-model="f.vkn_tckn" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                <div><label class="block text-xs text-slate-500 mb-1">Vergi Dairesi</label><input x-model="f.vergi_dairesi" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
            </div>
            <label class="block text-xs text-slate-500 mb-1">Adres</label>
            <input x-model="f.adres" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
            <label class="flex items-center gap-2 text-sm text-slate-600 mb-2"><input type="checkbox" x-model="f.mali_muhur_yuklu"> Mali mühür yüklü</label>
            <label class="flex items-center gap-2 text-sm text-slate-600 mb-3"><input type="checkbox" x-model="f.aktif"> Entegrasyon aktif (gerçek belge kes)</label>

            <hr class="my-3 border-slate-100">
            <label class="block text-xs text-slate-500 mb-1">Normal Fiş Modu (fatura istenmeyen satış)</label>
            <select x-model="f.fis_modu" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
                <option value="earsiv">e-Arşiv modu — ÖKC'siz, her satış otomatik e-Arşiv</option>
                <option value="okc">Yazarkasa (ÖKC) modu — cihazdan mali fiş</option>
            </select>
            <div x-show="f.fis_modu === 'okc'" x-cloak class="bg-slate-50 rounded-xl p-3 mb-4">
                <label class="block text-xs text-slate-500 mb-1">Yazarkasa Markası</label>
                <select x-model="f.okc_marka" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
                    <option value="ingenico">Ingenico</option><option value="hugin">Hugin</option>
                    <option value="pavo">Pavo</option><option value="beko">Beko</option><option value="inpos">Inpos</option>
                </select>
                <div class="grid grid-cols-2 gap-2 mb-2">
                    <input x-model="f.okc_ip" placeholder="Cihaz IP (ör. 192.168.1.50)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                    <input x-model="f.okc_port" placeholder="Port" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" x-model="f.okc_aktif"> Yazarkasa bağlı (gerçek fiş bas)</label>
            </div>

            <div class="flex gap-2">
                <button @click="modal = false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">İptal</button>
                <button @click="kaydet()" class="flex-1 bg-indigo-600 text-white rounded-xl py-2.5 text-sm font-semibold">Kaydet</button>
            </div>
        </div>
    </div>
</div>
<style>[x-cloak]{display:none!important}</style>
@endsection

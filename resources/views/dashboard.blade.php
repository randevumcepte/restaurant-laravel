@extends('layout.app')
@section('title', $sube->ad)
@section('baslik', 'Patron Dashboard')

@section('content')
@php
    $para = fn ($v) => number_format((float) $v, 0, ',', '.') . ' ₺';
    $maxTrend = max(1, max(array_map(fn ($t) => $t['ciro'], $trend)));
@endphp

{{-- Ozet kartlar --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
    @php
        $kartlar = [
            ['Bugün Ciro', $para($bugunCiro), 'text-indigo-600'],
            ['Dün Ciro', $para($dunCiro), 'text-slate-700'],
            ['Son 30 Gün', $para($son30Ciro), 'text-emerald-600'],
            ['Ort. Adisyon', $para($ortAdisyon), 'text-sky-600'],
            ['Açık Masa', $acikMasaSayisi.' / '.$masaSayisi, 'text-amber-600'],
            ['Kritik Stok', $kritikSayisi.' kalem', 'text-rose-600'],
        ];
    @endphp
    @foreach ($kartlar as [$baslik, $deger, $renk])
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400 font-semibold">{{ $baslik }}</div>
            <div class="mt-2 text-xl font-bold {{ $renk }}">{{ $deger }}</div>
        </div>
    @endforeach
</div>

{{-- Ciro trendi --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-slate-800">Ciro Trendi <span class="text-slate-400 font-normal text-sm">(son 14 gün)</span></h2>
        <span class="text-sm text-slate-500">Açık masa tutarı: <b class="text-amber-600">{{ $para($acikTutar) }}</b></span>
    </div>
    <div class="flex items-end gap-2 h-40">
        @foreach ($trend as $t)
            <div class="flex-1 flex flex-col items-center justify-end h-full">
                <div class="w-full rounded-t-md bg-gradient-to-t from-indigo-500 to-indigo-400" style="height: {{ max(3, (int) ($t['ciro'] / $maxTrend * 100)) }}%" title="{{ $para($t['ciro']) }}"></div>
                <div class="text-[10px] text-slate-400 mt-1">{{ $t['gun'] }}</div>
            </div>
        @endforeach
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">🍽️ Açık Masalar ({{ $acikMasaSayisi }})</h2>
        <div class="space-y-2">
            @forelse ($acikAdisyonlar as $a)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                    <div><span class="font-semibold text-slate-800">Masa {{ $a->masa }}</span>
                        <span class="text-xs text-slate-500 ml-2">{{ $a->garson }} · {{ $a->misafir_sayisi }} kişi</span></div>
                    <div class="text-right"><div class="font-bold text-indigo-600">{{ $para($a->toplam) }}</div>
                        <div class="text-[11px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($a->acilis)->diffForHumans() }}</div></div>
                </div>
            @empty <p class="text-sm text-slate-400">Şu an açık masa yok.</p> @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">⚠️ Kritik Stok Seviyeleri</h2>
        <div class="space-y-2">
            @forelse ($kritikStoklar as $k)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-rose-50">
                    <span class="font-medium text-slate-700">{{ $k->ad }}</span>
                    <span class="text-sm font-semibold text-rose-600">{{ number_format((float) $k->stok, 0, ',', '.') }} / {{ number_format((float) $k->kritik_stok, 0, ',', '.') }} {{ $birimler[$k->temel_birim_id] ?? '' }}</span>
                </div>
            @empty <p class="text-sm text-slate-400">Kritik seviyede stok yok.</p> @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">📈 Alış Fiyat Uyarıları</h2>
        <div class="space-y-2">
            @forelse ($fiyatUyarilari as $u)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl {{ $u->uyari === 'kirmizi' ? 'bg-rose-50' : 'bg-amber-50' }}">
                    <div><span class="font-medium text-slate-700">{{ $u->ad }}</span>
                        <span class="text-[11px] text-slate-400 ml-1">{{ \Illuminate\Support\Carbon::parse($u->tarih)->format('d.m.Y') }}</span></div>
                    <span class="text-sm font-bold {{ $u->uyari === 'kirmizi' ? 'text-rose-600' : 'text-amber-600' }}">%{{ number_format((float) $u->fiyat_farki_yuzde, 1, ',', '.') }} (+{{ $para($u->fiyat_farki_tutar) }})</span>
                </div>
            @empty <p class="text-sm text-slate-400">Fiyat uyarısı yok.</p> @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">🛡️ İkram / İndirim / İptal Radarı</h2>
        <div class="space-y-2">
            @php $renkTip = ['void' => 'text-rose-600', 'indirim' => 'text-amber-600', 'ikram' => 'text-sky-600']; @endphp
            @forelse ($sonLoglar as $l)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                    <div><span class="font-semibold uppercase text-xs {{ $renkTip[$l->tip] ?? 'text-slate-600' }}">{{ $l->tip }}</span>
                        <span class="text-sm text-slate-600 ml-2">{{ $l->ad ?? '-' }}</span>
                        <span class="text-[11px] text-slate-400 ml-1">· {{ $l->sebep }}</span></div>
                    <span class="font-semibold text-slate-700">{{ $para($l->tutar) }}</span>
                </div>
            @empty <p class="text-sm text-slate-400">Kayıt yok.</p> @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">🔥 En Çok Satan <span class="text-slate-400 font-normal text-sm">(30 gün)</span></h2>
        <div class="space-y-2">
            @foreach ($enCokSatan as $i => $u)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                    <span class="font-medium text-slate-700"><span class="text-slate-400 mr-2">{{ $i + 1 }}.</span>{{ $u->urun_adi }}</span>
                    <span class="text-sm text-slate-600">{{ (int) $u->adet }} adet · <b class="text-emerald-600">{{ $para($u->ciro) }}</b></span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4">👤 Personel Satış <span class="text-slate-400 font-normal text-sm">(30 gün)</span></h2>
        <div class="space-y-2">
            @foreach ($personelSatis as $p)
                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-50">
                    <span class="font-medium text-slate-700">{{ $p->ad }}</span>
                    <span class="text-sm text-slate-600">{{ (int) $p->adisyon }} adisyon · <b class="text-indigo-600">{{ $para($p->ciro) }}</b></span>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

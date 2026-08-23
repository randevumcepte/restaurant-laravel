@extends('layout.app')
@section('title', 'Masa ' . $masa->ad)
@section('baslik', 'Masa ' . $masa->ad)

@section('content')
<a href="/pos" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-indigo-600 mb-4">← Masa Haritası</a>

@if (!$adisyon)
    {{-- Bos masa: adisyon ac --}}
    <div x-data="{ misafir: 2, ac() { api('/pos/adisyon-ac', { masa_id: {{ $masa->id }}, misafir: this.misafir }).then(() => location.reload()); } }"
         class="max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center mt-10">
        <div class="text-5xl mb-3">🪑</div>
        <h2 class="text-lg font-bold text-slate-800 mb-1">Masa {{ $masa->ad }} boş</h2>
        <p class="text-sm text-slate-500 mb-5">Kapasite: {{ $masa->kapasite }} kişi</p>
        <label class="block text-sm text-slate-600 mb-2">Misafir sayısı</label>
        <input type="number" x-model="misafir" min="1" class="w-24 text-center border border-slate-300 rounded-lg px-3 py-2 mb-4">
        <button @click="ac()" class="w-full bg-indigo-600 text-white font-semibold rounded-xl py-3 hover:bg-indigo-700">Adisyon Aç</button>
    </div>
@else
    <div x-data="{ kategori: {{ $kategoriler->first()->id ?? 0 }}, odeModal: false, tasiModal: false, bolModal: false, faturaModal: false, tip: 'nakit', yeniMasa: '', kisi: {{ max(1, $adisyon->misafir_sayisi) }}, fUnvan: '', fVkn: '', fSonuc: '',
        ekle(id) { api('/pos/kalem-ekle', { adisyon_id: {{ $adisyon->id }}, urun_id: id }).then(() => location.reload()); },
        sil(id) { api('/pos/kalem-sil', { kalem_id: id }).then(() => location.reload()); },
        gonder() { api('/pos/gonder', { adisyon_id: {{ $adisyon->id }} }).then(() => location.reload()); },
        ode() { api('/pos/ode', { adisyon_id: {{ $adisyon->id }}, tip: this.tip }).then(() => location.href = '/pos'); },
        tasi() { if(!this.yeniMasa) return; api('/pos/tasi', { adisyon_id: {{ $adisyon->id }}, yeni_masa_id: this.yeniMasa }).then(() => location.href = '/pos'); },
        faturaKes() { api('/pos/fatura-olustur', { adisyon_id: {{ $adisyon->id }}, alici_unvan: this.fUnvan, alici_vkn: this.fVkn }).then(r => { this.fSonuc = (r.tip === 'e_fatura' ? 'e-Fatura' : 'e-Arşiv') + ' kesildi ✓ ' + r.belge_no; }); } }"
         class="grid lg:grid-cols-3 gap-6">

        {{-- Menu --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
            <div class="flex flex-wrap gap-2 mb-4 border-b border-slate-100 pb-3">
                @foreach ($kategoriler as $k)
                    <button @click="kategori = {{ $k->id }}"
                        :class="kategori === {{ $k->id }} ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium">{{ $k->ad }}</button>
                @endforeach
            </div>
            @foreach ($kategoriler as $k)
                <div x-show="kategori === {{ $k->id }}" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @forelse ($urunler[$k->id] ?? [] as $u)
                        <button @click="ekle({{ $u->id }})" {{ $u->tukendi ? 'disabled' : '' }}
                            class="text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 transition {{ $u->tukendi ? 'opacity-40 cursor-not-allowed' : '' }}">
                            <div class="font-semibold text-slate-800 text-sm">{{ $u->ad }}</div>
                            <div class="text-indigo-600 font-bold text-sm mt-1">{{ number_format((float) $u->fiyat, 0, ',', '.') }} ₺</div>
                            @if ($u->tukendi)<div class="text-[10px] text-rose-500 font-semibold">TÜKENDİ</div>@endif
                        </button>
                    @empty
                        <p class="text-sm text-slate-400 col-span-full">Bu kategoride ürün yok.</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        {{-- Adisyon --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 flex flex-col" style="max-height: 78vh">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-bold text-slate-800">Adisyon</h2>
                <span class="text-xs text-slate-400">{{ $adisyon->misafir_sayisi }} kişi · {{ \Illuminate\Support\Carbon::parse($adisyon->acilis)->diffForHumans(null, true) }}</span>
            </div>

            {{-- Musteri baglama (sadakat) --}}
            <div x-data="{ mmodal: false, ara: '', sonuc: [], mad: '', mtel: '', madres: '',
                    async searchM() { this.sonuc = await api('/musteriler/ara?q=' + encodeURIComponent(this.ara)); },
                    async baglaM(id) { await api('/pos/musteri-bagla', { adisyon_id: {{ $adisyon->id }}, musteri_id: id }); location.reload(); },
                    async yeniM() { await api('/pos/musteri-bagla', { adisyon_id: {{ $adisyon->id }}, ad: this.mad, telefon: this.mtel, adres: this.madres }); location.reload(); } }"
                 class="mb-3 pb-3 border-b border-slate-100">
                @if ($musteri)
                    <div class="flex items-center justify-between bg-amber-50 rounded-lg px-3 py-2">
                        <div><span class="text-sm font-semibold text-slate-800">👤 {{ $musteri->ad }}</span>
                            <span class="text-xs text-slate-400 ml-1">{{ $musteri->telefon }} · {{ $musteri->puan }} puan</span></div>
                        <button @click="mmodal = true" class="text-xs text-indigo-600 font-medium">Değiştir</button>
                    </div>
                @else
                    <button @click="mmodal = true" class="w-full text-sm text-slate-500 hover:text-indigo-600 border border-dashed border-slate-300 rounded-lg py-2">+ Müşteri Bağla <span class="text-xs text-slate-400">(sadakat puanı)</span></button>
                @endif

                <div x-show="mmodal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="mmodal = false">
                    <div class="bg-white rounded-2xl p-5 w-96">
                        <h3 class="font-bold text-slate-800 mb-3">Müşteri Bağla</h3>
                        <input x-model="ara" @input.debounce.300ms="searchM()" placeholder="Ara: ad veya telefon" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
                        <div class="max-h-40 overflow-y-auto mb-3">
                            <template x-for="m in sonuc" :key="m.id">
                                <button @click="baglaM(m.id)" class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 text-sm border-b border-slate-50">
                                    <span class="font-medium" x-text="m.ad"></span> <span class="text-slate-400 text-xs" x-text="m.telefon"></span>
                                </button>
                            </template>
                        </div>
                        <div class="border-t border-slate-100 pt-3">
                            <p class="text-xs text-slate-400 mb-2">Yeni müşteri</p>
                            <input x-model="mad" placeholder="Ad Soyad" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
                            <input x-model="mtel" placeholder="Telefon" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
                            <div class="flex gap-2">
                                <button @click="mmodal = false" class="flex-1 bg-slate-100 rounded-lg py-2 text-sm">Kapat</button>
                                <button @click="yeniM()" class="flex-1 bg-indigo-600 text-white rounded-lg py-2 text-sm font-semibold">Ekle & Bağla</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto space-y-2 mb-3">
                @forelse ($kalemler as $kalem)
                    <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-slate-50">
                        <div class="min-w-0">
                            <div class="font-medium text-slate-800 text-sm truncate">{{ $kalem->adet }}× {{ $kalem->urun_adi }}</div>
                            <span class="text-[10px] px-1.5 py-0.5 rounded {{ $kalem->durum === 'yeni' ? 'bg-amber-100 text-amber-700' : ($kalem->durum === 'hazir' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700') }}">{{ $kalem->durum }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-700 text-sm">{{ number_format((float) $kalem->tutar, 0, ',', '.') }} ₺</span>
                            @if ($kalem->durum === 'yeni')
                                <button @click="sil({{ $kalem->id }})" class="text-rose-400 hover:text-rose-600 text-lg leading-none">×</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-8">Henüz ürün eklenmedi.<br>Soldan seçin.</p>
                @endforelse
            </div>
            <div class="border-t border-slate-100 pt-3">
                <div class="flex justify-between text-lg font-bold text-slate-900 mb-3">
                    <span>Toplam</span><span>{{ number_format((float) $adisyon->toplam, 0, ',', '.') }} ₺</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button @click="gonder()" class="bg-sky-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-sky-700">👨‍🍳 Mutfağa Gönder</button>
                    <button @click="odeModal = true" class="bg-emerald-600 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-emerald-700">💳 Öde</button>
                    <button @click="tasiModal = true" class="bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl py-2.5 hover:bg-slate-200">↔️ Masa Taşı</button>
                    <button @click="bolModal = true" class="bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl py-2.5 hover:bg-slate-200">🧮 Hesap Böl</button>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <button @click="faturaModal = true; fSonuc = ''" class="bg-slate-800 text-white text-sm font-semibold rounded-xl py-2.5 hover:bg-slate-900">🧾 Fatura</button>
                    <a href="/pos/fis/{{ $adisyon->id }}" target="_blank" class="bg-slate-100 text-slate-700 text-sm font-semibold rounded-xl py-2.5 hover:bg-slate-200 text-center">🖨️ Hesap Fişi</a>
                </div>
            </div>

            {{-- Ode modal --}}
            <div x-show="odeModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="odeModal=false">
                <div class="bg-white rounded-2xl p-6 w-80">
                    <h3 class="font-bold text-slate-800 mb-1">Ödeme Al</h3>
                    <p class="text-sm text-slate-500 mb-4">Tutar: <b>{{ number_format((float) $adisyon->toplam, 0, ',', '.') }} ₺</b></p>
                    <select x-model="tip" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-4">
                        <option value="nakit">Nakit</option><option value="kredi">Kredi Kartı</option>
                        <option value="yemek_karti">Yemek Kartı</option>
                    </select>
                    <div class="flex gap-2">
                        <button @click="odeModal=false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">İptal</button>
                        <button @click="ode()" class="flex-1 bg-emerald-600 text-white rounded-xl py-2.5 text-sm font-semibold">Öde & Kapat</button>
                    </div>
                </div>
            </div>

            {{-- Tasi modal --}}
            <div x-show="tasiModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="tasiModal=false">
                <div class="bg-white rounded-2xl p-6 w-80">
                    <h3 class="font-bold text-slate-800 mb-4">Masa Taşı</h3>
                    <select x-model="yeniMasa" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-4">
                        <option value="">Boş masa seç...</option>
                        @foreach ($bosMasalar as $bm)<option value="{{ $bm->id }}">{{ $bm->ad }}</option>@endforeach
                    </select>
                    <div class="flex gap-2">
                        <button @click="tasiModal=false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">İptal</button>
                        <button @click="tasi()" class="flex-1 bg-indigo-600 text-white rounded-xl py-2.5 text-sm font-semibold">Taşı</button>
                    </div>
                </div>
            </div>

            {{-- Hesap bol modal --}}
            <div x-show="bolModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="bolModal=false">
                <div class="bg-white rounded-2xl p-6 w-80">
                    <h3 class="font-bold text-slate-800 mb-3">Hesap Böl (eşit)</h3>
                    <label class="block text-sm text-slate-600 mb-2">Kişi sayısı</label>
                    <input type="number" x-model="kisi" min="1" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-4">
                    <div class="bg-indigo-50 rounded-xl p-4 text-center mb-4">
                        <div class="text-xs text-slate-500">Kişi başı</div>
                        <div class="text-2xl font-bold text-indigo-600" x-text="new Intl.NumberFormat('tr-TR').format(Math.ceil({{ (float) $adisyon->toplam }} / Math.max(1, kisi))) + ' ₺'"></div>
                    </div>
                    <button @click="bolModal=false" class="w-full bg-slate-100 rounded-xl py-2.5 text-sm">Kapat</button>
                </div>
            </div>

            {{-- Fatura (e-Arsiv/e-Fatura) modal --}}
            <div x-show="faturaModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50" @click.self="faturaModal=false">
                <div class="bg-white rounded-2xl p-6 w-96">
                    <h3 class="font-bold text-slate-800 mb-1">e-Arşiv / e-Fatura Kes</h3>
                    <p class="text-sm text-slate-500 mb-3">Tutar: <b>{{ number_format((float) $adisyon->toplam, 0, ',', '.') }} ₺</b> <span class="text-xs text-slate-400">(KDV %10 dahil)</span></p>
                    <p class="text-xs text-slate-400 mb-2">Boş bırakırsan "Son Tüketici" e-Arşiv kesilir. 10 haneli VKN girilirse e-Fatura olur.</p>
                    <input x-model="fUnvan" placeholder="Alıcı ünvan (opsiyonel)" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-2 text-sm">
                    <input x-model="fVkn" placeholder="VKN / TCKN (opsiyonel)" class="w-full border border-slate-300 rounded-lg px-3 py-2 mb-3 text-sm">
                    <div class="text-sm text-emerald-600 font-semibold mb-3 min-h-5" x-text="fSonuc"></div>
                    <div class="flex gap-2">
                        <button @click="faturaModal=false" class="flex-1 bg-slate-100 rounded-xl py-2.5 text-sm">Kapat</button>
                        <button @click="faturaKes()" class="flex-1 bg-slate-800 text-white rounded-xl py-2.5 text-sm font-semibold">Kes</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
<style>[x-cloak]{display:none!important}</style>
@endsection

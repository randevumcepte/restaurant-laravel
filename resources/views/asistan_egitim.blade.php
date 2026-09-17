@extends('layout.app')
@section('title', 'Asistan Eğitimi')
@section('baslik', '🎓 Müşteri AI Eğitimi')
@section('content')
<div x-data="egitim()">

    @if(session('ok'))
        <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm px-4 py-3">{{ session('ok') }}</div>
    @endif
    @if(session('hata'))
        <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm px-4 py-3">{{ session('hata') }}</div>
    @endif

    <p class="text-sm text-slate-500 mb-4 max-w-3xl">
        Müşteri masadaki QR asistanına soru sorunca <b>önce buradaki kalıplarda aranır (bedava)</b>, bulunamazsa AI’ya (Haiku) düşer.
        Amaç: soruların çoğunu kalıpla karşılayıp AI maliyetini düşürmek. Aşağıdaki 3 yolla eğitirsin:
        <b>elle kalıp ekle</b>, <b>çözülemeyen soruları tek tıkla kalıba çevir</b>, ya da <b>PDF (menü/SSS) yükle → otomatik çıkar.</b>
    </p>

    {{-- Özet --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Kalıp sayısı</div>
            <div class="text-2xl font-extrabold text-indigo-600">{{ $ozet['kalip'] }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Bedava cevaplanan</div>
            <div class="text-2xl font-extrabold text-emerald-600">{{ number_format($ozet['bedava'], 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="text-xs text-slate-500">Bekleyen (çözülemeyen)</div>
            <div class="text-2xl font-extrabold text-amber-600">{{ $ozet['bekleyen'] }}</div>
        </div>
    </div>

    {{-- PDF yükle --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
        <div class="font-bold text-slate-900 mb-1">📄 PDF’ten Otomatik Kalıp Çıkar</div>
        <p class="text-sm text-slate-500 mb-3">Menü / SSS / kurumsal bilgi PDF’i yükle → AI belgeyi okuyup soru-cevap kalıpları önerir (tek seferlik AI). Kontrol edip kaydet.</p>
        <form method="POST" action="/asistan-egitim/pdf-coz" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
            @csrf
            <input type="file" name="pdf" accept="application/pdf" required class="text-sm">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Yükle & Çıkar</button>
        </form>

        @if(session('pdf_onizleme'))
            <form method="POST" action="/asistan-egitim/pdf-kaydet" class="mt-5 border-t border-slate-100 pt-4">
                @csrf
                <div class="text-sm font-semibold text-slate-700 mb-2">Önizleme — kaydetmek istemediğinin işaretini kaldır:</div>
                <div class="space-y-3">
                    @foreach(session('pdf_onizleme') as $i => $o)
                        <div class="border border-slate-200 rounded-xl p-3">
                            <label class="flex items-center gap-2 mb-2 text-sm font-medium text-slate-700">
                                <input type="checkbox" name="sec[]" value="{{ $i }}" checked class="w-4 h-4 rounded">
                                Bu maddeyi kaydet
                            </label>
                            <input name="tetikleyiciler[{{ $i }}]" value="{{ $o['tetikleyiciler'] ?? '' }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-2" placeholder="tetikleyiciler (virgülle)">
                            <textarea name="cevap[{{ $i }}]" rows="2" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm mb-2" placeholder="cevap">{{ $o['cevap'] ?? '' }}</textarea>
                            <input name="kategori[{{ $i }}]" value="{{ $o['kategori'] ?? 'pdf' }}" class="w-40 border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="kategori">
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="mt-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">Seçilenleri Kalıba Kaydet</button>
            </form>
        @endif
    </div>

    {{-- Çözülemeyen sorular --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6">
        <div class="font-bold text-slate-900 mb-1">❓ Çözülemeyen Sorular <span class="text-sm font-normal text-slate-400">(müşteriler sordu, cevabın yoktu)</span></div>
        <p class="text-sm text-slate-500 mb-3">En çok sorulanlar üstte. “Kalıba Çevir” ile tek tıkla cevap ekle — o soru artık bedava karşılanır.</p>
        @if($cozulmeyenler->isEmpty())
            <div class="text-sm text-slate-400 py-3">Bekleyen çözülemeyen soru yok. 👍</div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($cozulmeyenler as $c)
                    <div class="flex items-center gap-3 py-2.5">
                        <span class="shrink-0 inline-flex items-center justify-center min-w-8 h-6 px-2 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">{{ $c->adet }}×</span>
                        <div class="flex-1 text-sm text-slate-800">{{ $c->ham ?: $c->soru_norm }}</div>
                        <button @click="kalibaCevir(@js($c->ham ?: $c->soru_norm), {{ $c->id }})" class="text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg px-3 py-1.5">Kalıba Çevir</button>
                        <button @click="cozSil({{ $c->id }})" class="text-xs text-slate-400 hover:text-rose-600">Sil</button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Kalıp listesi --}}
    <div class="flex items-center justify-between mb-3">
        <div class="font-bold text-slate-900">🗂️ Kalıp Kütüphanesi</div>
        <button @click="yeni()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-xl">+ Yeni Kalıp</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <template x-for="k in kaliplar" :key="k.id">
            <div class="bg-white rounded-2xl border border-slate-200 p-4" :class="(k.aktif==1||k.aktif===true)?'':'opacity-60'">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex flex-wrap gap-1">
                        <template x-for="t in String(k.tetikleyiciler).split(/[\n,;]+/).map(s=>s.trim()).filter(Boolean)" :key="t">
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs" x-text="t"></span>
                        </template>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400" x-text="(k.kullanim_sayisi||0)+'×'"></span>
                </div>
                <div class="mt-2 text-sm text-slate-800" x-text="k.cevap"></div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500" x-text="k.kategori||'genel'"></span>
                    <span class="text-xs px-2 py-0.5 rounded" :class="(k.aktif==1||k.aktif===true)?'bg-emerald-100 text-emerald-700':'bg-slate-200 text-slate-500'" x-text="(k.aktif==1||k.aktif===true)?'aktif':'pasif'"></span>
                    <div class="flex-1"></div>
                    <button @click="duzenle(k)" class="text-xs font-medium text-slate-600 hover:text-indigo-600">Düzenle</button>
                    <button @click="sil(k.id)" class="text-xs font-medium text-rose-500 hover:text-rose-700">Sil</button>
                </div>
            </div>
        </template>
        <template x-if="!kaliplar.length">
            <div class="text-slate-400 text-sm">Henüz kalıp yok. “+ Yeni Kalıp” ile ekle ya da PDF yükle.</div>
        </template>
    </div>

    {{-- Modal --}}
    <div x-show="acik" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4 bg-slate-900/50" @click.self="acik=false">
        <div class="bg-white rounded-2xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900" x-text="f.id ? 'Kalıbı Düzenle' : 'Yeni Kalıp'"></h2>
                <button @click="acik=false" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="text-xs font-medium text-slate-500">Tetikleyiciler (virgülle eş anlamlılar)</label>
                    <input x-model="f.tetikleyiciler" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="wifi şifresi, internet şifresi, kablosuz">
                </div>
                <div>
                    <label class="text-xs font-medium text-slate-500">Cevap</label>
                    <textarea x-model="f.cevap" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Kısa, net müşteri cevabı"></textarea>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="text-xs font-medium text-slate-500">Kategori</label>
                        <input x-model="f.kategori" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="genel">
                    </div>
                    <label class="flex items-end gap-2 text-sm text-slate-700 pb-2">
                        <input type="checkbox" x-model="f.aktif" class="w-4 h-4 rounded"> Aktif
                    </label>
                </div>
            </div>
            <div class="mt-5 flex gap-2">
                <button @click="acik=false" class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium">Vazgeç</button>
                <button @click="kaydet()" :disabled="kaydediyor" class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Kaydet</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>[x-cloak]{display:none!important}</style>
<script>
function egitim(){
  return {
    kaliplar: @json($kaliplar),
    acik:false, kaydediyor:false,
    f:{},
    bos(){ return {id:null, tetikleyiciler:'', cevap:'', kategori:'genel', aktif:true, cozulmeyen_id:null}; },
    yeni(){ this.f=this.bos(); this.acik=true; },
    duzenle(k){ this.f={id:k.id, tetikleyiciler:k.tetikleyiciler, cevap:k.cevap, kategori:k.kategori||'genel', aktif:(k.aktif==1||k.aktif===true), cozulmeyen_id:null}; this.acik=true; },
    kalibaCevir(soru, cid){ this.f={...this.bos(), tetikleyiciler:soru, cozulmeyen_id:cid}; this.acik=true; },
    async kaydet(){
      if(!this.f.tetikleyiciler || !this.f.cevap){ alert('Tetikleyici ve cevap gerekli'); return; }
      this.kaydediyor=true;
      try{ const r=await api('/asistan-egitim/kalip-kaydet', this.f); if(r.ok){ location.reload(); return; } alert(r.hata||'Kaydedilemedi'); }
      finally{ this.kaydediyor=false; }
    },
    async sil(id){ if(!confirm('Kalıp silinsin mi?')) return; const r=await api('/asistan-egitim/kalip-sil',{id}); if(r.ok) location.reload(); },
    async cozSil(id){ const r=await api('/asistan-egitim/cozulmeyen-sil',{id}); if(r.ok) location.reload(); },
  };
}
</script>
@endpush

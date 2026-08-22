@extends('layout.app')
@section('title', 'AI Copilot')
@section('baslik', '🤖 AI Copilot — Restoranınla Konuş')

@section('content')
<div class="max-w-3xl mx-auto" x-data="copilot()">
    <div class="bg-white rounded-2xl border border-slate-200 flex flex-col" style="height: 70vh">
        <div class="flex-1 overflow-y-auto p-5 space-y-4" x-ref="feed">
            <template x-for="(m, i) in mesajlar" :key="i">
                <div :class="m.rol === 'ben' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="m.rol === 'ben' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-800'"
                         class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm" x-html="m.metin"></div>
                </div>
            </template>
            <div x-show="yaziyor" class="flex justify-start"><div class="bg-slate-100 text-slate-400 rounded-2xl px-4 py-2.5 text-sm">yazıyor…</div></div>
        </div>
        <div class="border-t border-slate-100 p-3">
            <div class="flex flex-wrap gap-2 mb-2">
                <template x-for="o in oneriler" :key="o">
                    <button @click="sor(o)" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full px-3 py-1" x-text="o"></button>
                </template>
            </div>
            <form @submit.prevent="sor(giris)" class="flex gap-2">
                <input x-model="giris" placeholder="Restoranına bir şey sor... (bugünkü ciro, en çok satan, kritik stok)"
                       class="flex-1 border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-indigo-400">
                <button class="bg-indigo-600 text-white rounded-xl px-5 py-2.5 text-sm font-semibold hover:bg-indigo-700">Gönder</button>
            </form>
        </div>
    </div>
    <p class="text-xs text-slate-400 mt-3 text-center">Şu an kural-tabanlı (gerçek veriden hesaplar, ücretsiz). Anthropic anahtarı bağlanınca serbest diyalog + sesli yanıt aktif olur.</p>
</div>

<script>
function copilot() {
    return {
        giris: '', yaziyor: false,
        oneriler: ['Bugünkü ciro ne?', 'En çok satan ürünler', 'En iyi personel', 'Kritik stoklar', 'Paket/kurye durumu', 'Son 30 gün cirosu'],
        mesajlar: [{ rol: 'ai', metin: 'Merhaba patron 👋 Ben restoranının asistanıyım. Aşağıdaki sorulardan birine tıkla ya da kendi sorunu yaz.' }],
        async sor(soru) {
            soru = (soru || '').trim(); if (!soru) return;
            this.mesajlar.push({ rol: 'ben', metin: soru }); this.giris = ''; this.yaziyor = true; this.kaydir();
            const r = await api('/copilot/sor', { soru });
            this.yaziyor = false;
            let m = (r.cevap || '').replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
            this.mesajlar.push({ rol: 'ai', metin: m }); this.kaydir();
        },
        kaydir() { this.$nextTick(() => { this.$refs.feed.scrollTop = this.$refs.feed.scrollHeight; }); }
    };
}
</script>
@endsection

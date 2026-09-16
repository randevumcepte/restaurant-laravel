@extends('layout.app')
@section('title', 'İndirimler')
@section('baslik', '🎫 İndirimler')
@section('content')
<div x-data="indirimApp()" x-init="init()">

    <div class="mb-5 bg-white rounded-2xl border border-slate-200 p-5 flex items-start justify-between gap-4">
        <div>
            <div class="font-bold text-slate-900">İndirim Kuralları</div>
            <p class="text-sm text-slate-500 mt-1 max-w-2xl">
                Her indirim türü ayrı bir kuraldır ve <b>tek tek açıp kapatabilirsin</b>. Ödeme anında uygun olan
                <b>en yüksek</b> indirim otomatik uygulanır (kuponsa müşteri kodu girer). Online ödeme indirimi, müşteriyi
                kart ile ödemeye teşvik eder — para anında hesabına geçer.
            </p>
        </div>
        <button @click="yeni()" class="shrink-0 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl">+ Yeni İndirim</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <template x-for="k in kurallar" :key="k.id">
            <div class="bg-white rounded-2xl border border-slate-200 p-5" :class="k.aktif ? 'ring-1 ring-emerald-200' : 'opacity-80'">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl" x-text="tipMeta(k.tip).ik"></span>
                        <div>
                            <div class="font-semibold text-slate-900 leading-tight" x-text="k.ad"></div>
                            <div class="text-xs text-slate-400" x-text="tipMeta(k.tip).ad"></div>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" class="sr-only peer" :checked="k.aktif" @change="toggle(k)">
                        <div class="w-11 h-6 bg-slate-300 peer-checked:bg-emerald-500 rounded-full transition"></div>
                        <div class="absolute left-0.5 top-0.5 bg-white w-5 h-5 rounded-full transition peer-checked:translate-x-5"></div>
                    </label>
                </div>

                <div class="mt-3 text-2xl font-extrabold text-indigo-600" x-text="k.deger_tipi==='yuzde' ? ('%'+(+k.deger)) : (para(k.deger))"></div>

                <div class="mt-2 flex flex-wrap gap-1.5 text-xs">
                    <template x-if="k.kupon_kodu"><span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full font-mono font-bold" x-text="k.kupon_kodu"></span></template>
                    <template x-if="+k.min_tutar"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="'min '+para(k.min_tutar)"></span></template>
                    <template x-if="+k.max_indirim"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="'tavan '+para(k.max_indirim)"></span></template>
                    <template x-if="k.saat_bas && k.saat_bit"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="k.saat_bas+'–'+k.saat_bit"></span></template>
                    <template x-if="k.gun_maskesi"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="gunAd(k.gun_maskesi)"></span></template>
                    <template x-if="k.bitis"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="'son '+k.bitis"></span></template>
                    <template x-if="k.kullanim_limiti"><span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full" x-text="'kullanım '+(k.kullanim_sayisi||0)+'/'+k.kullanim_limiti"></span></template>
                </div>

                <div class="mt-4 flex gap-2">
                    <button @click="duzenle(k)" class="flex-1 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg py-2">Düzenle</button>
                    <button @click="sil(k)" class="text-sm font-medium text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg py-2 px-3">Sil</button>
                </div>
            </div>
        </template>
        <template x-if="!kurallar.length">
            <div class="text-slate-400 text-sm">Henüz indirim yok. “+ Yeni İndirim” ile ekleyebilirsin.</div>
        </template>
    </div>

    {{-- Modal --}}
    <div x-show="acik" x-cloak class="fixed inset-0 z-30 flex items-center justify-center p-4 bg-slate-900/50" @click.self="acik=false">
        <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-slate-900" x-text="f.id ? 'İndirimi Düzenle' : 'Yeni İndirim'"></h2>
                <button @click="acik=false" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="text-xs font-medium text-slate-500">Tür</label>
                    <select x-model="f.tip" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                        <template x-for="(m,t) in TIPLER" :key="t"><option :value="t" x-text="m.ik+' '+m.ad"></option></template>
                    </select>
                    <p class="text-xs text-slate-400 mt-1" x-text="tipMeta(f.tip).ipucu"></p>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-500">Ad (müşteriye/panelde görünür)</label>
                    <input x-model="f.ad" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="Örn: Online Ödeme İndirimi">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-slate-500">İndirim tipi</label>
                        <select x-model="f.deger_tipi" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
                            <option value="yuzde">Yüzde (%)</option>
                            <option value="tutar">Tutar (₺)</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-slate-500" x-text="f.deger_tipi==='yuzde' ? 'Yüzde' : 'Tutar (₺)'"></label>
                        <input type="number" x-model="f.deger" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" min="0" step="0.01">
                    </div>
                </div>

                <template x-if="f.tip==='kupon'">
                    <div>
                        <label class="text-xs font-medium text-slate-500">Kupon Kodu</label>
                        <input x-model="f.kupon_kodu" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono uppercase" placeholder="HOSGELDIN">
                    </div>
                </template>

                <template x-if="f.tip==='happy_hour'">
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="text-xs font-medium text-slate-500">Başlangıç saati</label><input type="time" x-model="f.saat_bas" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                        <div><label class="text-xs font-medium text-slate-500">Bitiş saati</label><input type="time" x-model="f.saat_bit" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                    </div>
                </template>

                <template x-if="f.tip==='gun'">
                    <div>
                        <label class="text-xs font-medium text-slate-500">Günler</label>
                        <div class="flex gap-1.5 mt-1">
                            <template x-for="(g,i) in ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz']" :key="i">
                                <button type="button" @click="gunTikla(i+1)" class="flex-1 text-xs py-2 rounded-lg border"
                                        :class="gunSecili(i+1) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-300'" x-text="g"></button>
                            </template>
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs font-medium text-slate-500">Min. sepet tutarı (₺)</label><input type="number" x-model="f.min_tutar" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="opsiyonel"></div>
                    <div><label class="text-xs font-medium text-slate-500">Maks. indirim (₺, %'de tavan)</label><input type="number" x-model="f.max_indirim" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="opsiyonel"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs font-medium text-slate-500">Başlangıç tarihi</label><input type="date" x-model="f.baslangic" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                    <div><label class="text-xs font-medium text-slate-500">Bitiş tarihi</label><input type="date" x-model="f.bitis" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"></div>
                </div>

                <div>
                    <label class="text-xs font-medium text-slate-500">Toplam kullanım limiti</label>
                    <input type="number" x-model="f.kullanim_limiti" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" placeholder="opsiyonel (boş = sınırsız)">
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" x-model="f.aktif" class="w-4 h-4 rounded"> Kayıttan sonra aktif olsun
                </label>
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
const TIPLER = {
    online_odeme:{ad:'Online Ödeme', ik:'💳', ipucu:'Müşteri kartla online öderken otomatik uygulanır. Kasa/nakit ödemede uygulanmaz.'},
    kupon:{ad:'Kupon Kodu', ik:'🎫', ipucu:'Müşteri ödeme ekranında kodu girer. Kupon kodu zorunlu.'},
    uygulama:{ad:'Uygulama Siparişi', ik:'📱', ipucu:'Sipariş mobil uygulamadan gelince uygulanır. (QR-web’de pasiftir; müşteri uygulaması gelince çalışır.)'},
    ilk_siparis:{ad:'İlk Sipariş', ik:'🥇', ipucu:'Müşterinin ilk siparişinde. (Müşteri tanınıyorsa; anonim QR’da pasif.)'},
    tutar_ustu:{ad:'Tutar Üstü', ik:'💰', ipucu:'Sepet, girilen min. tutarın üstündeyse uygulanır.'},
    happy_hour:{ad:'Happy Hour', ik:'🕐', ipucu:'Belirlenen saat aralığında uygulanır.'},
    gun:{ad:'Haftanın Günü', ik:'📅', ipucu:'Seçili günlerde uygulanır.'},
    dogum_gunu:{ad:'Doğum Günü', ik:'🎂', ipucu:'Müşterinin doğum gününde. (Müşteri tanınıyorsa; anonim QR’da pasif.)'},
};
function indirimApp(){
  return {
    TIPLER,
    kurallar: @json($kurallar),
    acik:false, kaydediyor:false,
    f:{},
    init(){},
    tipMeta(t){ return TIPLER[t] || {ad:t, ik:'🏷️', ipucu:''}; },
    para(v){ return new Intl.NumberFormat('tr-TR').format(Math.round(v||0)) + ' ₺'; },
    gunAd(mask){ const g=['','Pzt','Sal','Çar','Per','Cum','Cmt','Paz']; return String(mask).split(',').filter(Boolean).map(n=>g[+n]||'').join(', '); },
    bosForm(){ return {id:null, tip:'online_odeme', ad:'', deger_tipi:'yuzde', deger:10, kupon_kodu:'', saat_bas:'', saat_bit:'', gun_maskesi:'', min_tutar:'', max_indirim:'', baslangic:'', bitis:'', kullanim_limiti:'', aktif:true}; },
    yeni(){ this.f=this.bosForm(); this.acik=true; },
    duzenle(k){ this.f={...this.bosForm(), ...k, aktif:!!k.aktif}; this.acik=true; },
    gunSecili(n){ return String(this.f.gun_maskesi||'').split(',').filter(Boolean).includes(String(n)); },
    gunTikla(n){ let arr=String(this.f.gun_maskesi||'').split(',').filter(Boolean); n=String(n); if(arr.includes(n)) arr=arr.filter(x=>x!==n); else arr.push(n); arr.sort(); this.f.gun_maskesi=arr.join(','); },
    async toggle(k){ const r=await api('/indirimler/toggle',{id:k.id}); if(r.ok) k.aktif=r.aktif; },
    async sil(k){ if(!confirm('“'+k.ad+'” silinsin mi?')) return; const r=await api('/indirimler/sil',{id:k.id}); if(r.ok) this.kurallar=this.kurallar.filter(x=>x.id!==k.id); },
    async kaydet(){
      this.kaydediyor=true;
      try{ const r=await api('/indirimler/kaydet', this.f); if(r.ok){ location.reload(); return; } alert(r.hata||'Kaydedilemedi'); }
      finally{ this.kaydediyor=false; }
    },
  };
}
</script>
@endpush

@extends('layout.app')
@section('title', 'Ödül Doğrula')
@section('baslik', '🎁 Ödül / Kupon Doğrula')

@section('content')
<div class="max-w-lg mx-auto">
  <div class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-base font-bold text-slate-800 mb-1">Müşteri ödülünü doğrula</h2>
    <p class="text-sm text-slate-500 mb-5">Müşterinin uygulamasındaki <b>6 haneli kodu</b> gir (ya da QR'ı okut). Onaylayınca puan otomatik düşer.</p>

    <input id="kod" maxlength="6" autocomplete="off" autofocus
      class="w-full text-center uppercase tracking-[0.5em] font-extrabold text-3xl text-slate-800 border-2 border-slate-200 rounded-xl py-4 focus:border-indigo-500 focus:outline-none"
      placeholder="ABC123" oninput="this.value=this.value.toUpperCase()">
    <button onclick="dogrula()" id="btnDogrula"
      class="w-full mt-4 py-3.5 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white font-bold text-base hover:opacity-95">
      Doğrula
    </button>

    <div id="sonuc" class="mt-5"></div>
  </div>
</div>

<script>
const CSRF = document.querySelector('meta[name=csrf-token]').content;
async function post(u, b) {
  const r = await fetch(u, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: JSON.stringify(b) });
  return r.json();
}
let _kod = '';
let _adId = 0;
document.getElementById('kod').addEventListener('keydown', e => { if (e.key === 'Enter') dogrula(); });

async function dogrula() {
  const k = document.getElementById('kod').value.trim().toUpperCase();
  const el = document.getElementById('sonuc');
  if (k.length < 4) { el.innerHTML = cardErr('Kodu gir'); return; }
  el.innerHTML = '<p class="text-slate-400 text-sm">Kontrol ediliyor…</p>';
  const r = await post('/api/pos/odul-dogrula', { kod: k });
  if (!r.ok) { el.innerHTML = cardErr(r.hata || 'Geçersiz'); return; }
  _kod = r.kod; _adId = r.adisyon ? r.adisyon.id : 0;
  const yeterRozet = r.yeterli
    ? '<span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Puan yeterli</span>'
    : '<span class="text-xs font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-full">Puan yetersiz!</span>';
  const aciklama = r.aciklama ? `<span class="text-xs font-semibold text-indigo-600 bg-indigo-100 px-2 py-0.5 rounded-full ml-1">${esc(r.aciklama)}</span>` : '';
  const hesap = r.adisyon
    ? `<div class="flex items-center gap-2 mb-3 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-2">🧾 Açık hesap <b>#${r.adisyon.id}</b> · ${Math.round(r.adisyon.toplam)}₺ <span class="ml-auto text-xs text-emerald-600 font-semibold">ödül bu hesaba işlenecek</span></div>`
    : `<div class="mb-3 text-xs text-slate-400">Bu müşteriye bağlı açık hesap yok — sadece puan düşülür (ürünü garson ikram geçer).</div>`;
  el.innerHTML = `
    <div class="border border-indigo-200 bg-indigo-50/40 rounded-xl p-4">
      <div class="flex items-center gap-2 mb-2"><span class="text-2xl">🎁</span>
        <div><div class="font-bold text-slate-800">${esc(r.odul_ad)} ${aciklama}</div>
        <div class="text-xs text-slate-500">${esc(r.musteri)} · mevcut ${r.musteri_puan} puan</div></div>
        <div class="ml-auto text-right"><div class="text-lg font-extrabold text-indigo-700">${r.puan}</div><div class="text-[11px] text-slate-400">puan düşülecek</div></div>
      </div>
      <div class="mb-3">${yeterRozet}</div>
      ${hesap}
      <button onclick="onayla()" ${r.yeterli ? '' : 'disabled'}
        class="w-full py-3 rounded-xl ${r.yeterli ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-300 cursor-not-allowed'} text-white font-bold">
        ${r.adisyon ? 'Onayla & Hesaba Uygula' : 'Onayla & Puan Düş'}
      </button>
    </div>`;
}
async function onayla() {
  const el = document.getElementById('sonuc');
  const r = await post('/api/pos/odul-onayla', { kod: _kod, adisyon_id: _adId });
  if (r.ok) {
    el.innerHTML = `<div class="border border-emerald-200 bg-emerald-50 rounded-xl p-5 text-center">
      <div class="text-4xl mb-2">✅</div><div class="font-bold text-emerald-700">${esc(r.mesaj)}</div></div>`;
    document.getElementById('kod').value = ''; _kod = '';
    setTimeout(() => document.getElementById('kod').focus(), 300);
  } else { el.innerHTML = cardErr(r.hata || 'Hata'); }
}
function cardErr(m) { return `<div class="border border-rose-200 bg-rose-50 rounded-xl p-4 text-rose-700 font-semibold text-sm">⚠ ${esc(m)}</div>`; }
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
</script>
@endsection

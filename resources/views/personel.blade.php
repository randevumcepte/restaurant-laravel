@extends('layout.app')
@section('title', 'Personel')
@section('baslik', '🧑‍🍳 Personel & Yetki')

@section('content')
@php
  // Rol etiket + renk (Flutter personel_screen ile ayni)
  $rolAd = ['sahip' => 'Sahip', 'mudur' => 'Müdür', 'kasa' => 'Kasa', 'garson' => 'Garson', 'mutfak' => 'Mutfak'];
  $rolRenk = [
    'sahip'  => 'bg-violet-100 text-violet-700',
    'mudur'  => 'bg-indigo-100 text-indigo-700',
    'kasa'   => 'bg-amber-100 text-amber-700',
    'garson' => 'bg-emerald-100 text-emerald-700',
    'mutfak' => 'bg-orange-100 text-orange-700',
  ];
  // Yetki etiketleri (Flutter personel_yetkileri_screen ile birebir)
  $etiketler = $etiketler ?? [
    'adisyon_ac' => 'Adisyon Aç',
    'adisyon_kapat' => 'Ödeme Al / Masa Kapat',
    'adisyon_iptal' => 'Adisyon İptal',
    'adisyon_bol' => 'Adisyon Böl',
    'adisyon_birlestir' => 'Masa Birleştir',
    'masa_tasima' => 'Masa Taşıma',
    'iskonto' => 'İskonto Uygula',
    'ikram' => 'İkram',
    'urun_sil' => 'Ürün Sil (Void)',
    'fatura_kes' => 'Fatura Kes',
    'geri_islem' => 'Geriye Dönük İşlem',
    'maliyet_gor' => 'Maliyet Görebilir',
    'rapor_gor' => 'Rapor Görebilir',
  ];
  $gruplar = $gruplar ?? [
    '🧾 Adisyon' => ['adisyon_ac', 'adisyon_kapat', 'adisyon_iptal', 'adisyon_bol', 'adisyon_birlestir', 'masa_tasima'],
    '💸 Para & Kayıp' => ['iskonto', 'ikram', 'urun_sil', 'fatura_kes'],
    '📊 Yönetim' => ['geri_islem', 'maliyet_gor', 'rapor_gor'],
  ];
  $duzenleyebilir = $duzenleyebilir ?? true;
  $aktifler = array_values(array_filter($personeller, fn($p) => (int)($p['aktif'] ?? 0) === 1));
  $pasifler = array_values(array_filter($personeller, fn($p) => (int)($p['aktif'] ?? 0) !== 1));
@endphp

@if (session('ok'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('ok') }}</div>
@endif
@if (session('hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">{{ session('hata') }}</div>
@endif

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="flex gap-3 text-sm">
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Personel</span> <b class="text-slate-800 ml-1">{{ count($personeller) }}</b></div>
    <div class="bg-white rounded-xl px-4 py-2 shadow-sm"><span class="text-slate-400">Aktif</span> <b class="text-emerald-600 ml-1">{{ count($aktifler) }}</b></div>
  </div>
  @if ($duzenleyebilir)
    <button onclick="personelAc(null)" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold shadow-sm">
      <span>＋</span> Personel Ekle
    </button>
  @endif
</div>

@if (!$duzenleyebilir)
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
    Personel ve yetkileri yalnızca işletme sahibi düzenleyebilir. Görüntülüyorsunuz.
  </div>
@endif

@if (!$personeller)
  <div class="bg-white rounded-2xl p-10 text-center text-slate-400 shadow-sm">Henüz personel yok.</div>
@else
  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach (array_merge($aktifler, $pasifler) as $p)
      @php
        $rol = $p['rol'] ?? 'garson';
        $aktif = (int)($p['aktif'] ?? 0) === 1;
        $y = (array)($p['yetkiler'] ?? []);
        $acik = count(array_filter($y, fn($v) => $v === true));
      @endphp
      <div class="bg-white rounded-2xl p-5 shadow-sm {{ $aktif ? '' : 'opacity-60' }}">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-slate-600 bg-slate-100">
            {{ mb_substr($p['ad'] ?? '?', 0, 1) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-slate-800 truncate">{{ $p['ad'] ?? '—' }}</div>
            <div class="flex items-center gap-2 mt-0.5">
              <span class="text-[11px] font-semibold px-2 py-0.5 rounded {{ $rolRenk[$rol] ?? 'bg-slate-100 text-slate-600' }}">{{ $rolAd[$rol] ?? $rol }}</span>
              @if (!$aktif)<span class="text-[11px] px-2 py-0.5 rounded bg-slate-200 text-slate-500">Pasif</span>@endif
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 text-center mb-3">
          <div class="bg-slate-50 rounded-lg py-2">
            <div class="text-sm font-bold text-slate-700">{{ $p['pin'] ? $p['pin'] : '—' }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">PIN</div>
          </div>
          <div class="bg-slate-50 rounded-lg py-2">
            <div class="text-sm font-bold text-slate-700">{{ $acik }}/{{ count($etiketler) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Yetki</div>
          </div>
        </div>

        {{-- Yetki rozetleri --}}
        <div class="flex flex-wrap gap-1.5 mb-4 min-h-[1.5rem]">
          @forelse ($etiketler as $k => $et)
            @if (!empty($y[$k]))
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">{{ $et }}</span>
            @endif
          @empty
          @endforelse
          @if ($acik === 0)
            <span class="text-[11px] text-slate-400">Yetki tanımlı değil</span>
          @endif
          @if (!empty($y['iskonto']))
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-100">iskonto %{{ (int)round($p['iskonto_limit'] ?? 0) }}</span>
          @endif
          @if (!empty($y['ikram']))
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-600 border border-amber-100">ikram {{ (int)round($p['ikram_limit'] ?? 0) }}₺</span>
          @endif
        </div>

        @if ($duzenleyebilir)
          <div class="flex items-center gap-2">
            <button onclick='personelAc(@json($p))' class="flex-1 px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">Düzenle</button>
            <button onclick='yetkiAc(@json($p))' class="flex-1 px-3 py-2 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold">Yetkiler</button>
            {{-- Aktif/Pasif toggle --}}
            <form method="POST" action="/personel/kaydet" class="inline">
              @csrf
              <input type="hidden" name="id" value="{{ $p['id'] }}">
              <input type="hidden" name="aktif" value="{{ $aktif ? '0' : '1' }}">
              <button type="submit" title="{{ $aktif ? 'Pasife al' : 'Aktif et' }}"
                class="px-3 py-2 rounded-lg text-xs font-semibold {{ $aktif ? 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700' : 'bg-slate-100 hover:bg-slate-200 text-slate-500' }}">
                {{ $aktif ? 'Aktif' : 'Pasif' }}
              </button>
            </form>
            {{-- Sil --}}
            <form method="POST" action="/personel/sil" class="inline" onsubmit="return confirm('{{ addslashes($p['ad'] ?? '') }} silinsin mi?')">
              @csrf
              <input type="hidden" name="id" value="{{ $p['id'] }}">
              <button type="submit" title="Sil" class="px-3 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-semibold">✕</button>
            </form>
          </div>
        @endif
      </div>
    @endforeach
  </div>
@endif

@if ($duzenleyebilir)
{{-- ============ PERSONEL EKLE/DÜZENLE MODAL ============ --}}
<div id="personelModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
    <form method="POST" action="/personel/kaydet">
      @csrf
      <input type="hidden" name="id" id="f_id" value="">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800" id="personelModalBaslik">Yeni Personel</h3>
        <button type="button" onclick="personelKapat()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
      </div>
      <div class="px-6 py-4 space-y-3">
        <div>
          <label class="block text-xs font-medium text-slate-500 mb-1">Ad Soyad</label>
          <input type="text" name="ad" id="f_ad" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Telefon</label>
            <input type="text" name="telefon" id="f_telefon" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none">
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">PIN (en az 4 hane)</label>
            <input type="text" name="pin" id="f_pin" inputmode="numeric" maxlength="8" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none" placeholder="Boş = değişmez">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Rol</label>
            <select name="rol" id="f_rol" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-violet-300 focus:outline-none">
              @foreach ($rolAd as $k => $ad)
                <option value="{{ $k }}">{{ $ad }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
            <select name="aktif" id="f_aktif" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-violet-300 focus:outline-none">
              <option value="1">Aktif</option>
              <option value="0">Pasif</option>
            </select>
          </div>
        </div>
      </div>
      <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
        <button type="button" onclick="personelKapat()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold">Vazgeç</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold">Kaydet</button>
      </div>
    </form>
  </div>
</div>

{{-- ============ YETKİ DÜZENLE MODAL ============ --}}
<div id="yetkiModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
  <div class="bg-white rounded-2xl w-full max-w-lg shadow-xl max-h-[90vh] overflow-y-auto">
    <form method="POST" action="/personel/yetki">
      @csrf
      <input type="hidden" name="id" id="y_id" value="">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="font-bold text-slate-800"><span id="yetkiModalAd">Personel</span> — Yetkiler</h3>
        <button type="button" onclick="yetkiKapat()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">&times;</button>
      </div>
      <div class="px-6 py-4 space-y-5">
        @foreach ($gruplar as $baslik => $keys)
          <div>
            <div class="text-xs font-bold text-slate-400 mb-2">{{ $baslik }}</div>
            <div class="space-y-1.5">
              @foreach ($keys as $k)
                @if (isset($etiketler[$k]))
                  <label class="flex items-center gap-2.5 py-1 cursor-pointer">
                    <input type="checkbox" name="yetki_{{ $k }}" data-yetki="{{ $k }}" class="w-4 h-4 rounded text-violet-600 focus:ring-violet-400">
                    <span class="text-sm text-slate-700">{{ $etiketler[$k] }}</span>
                  </label>
                  @if ($k === 'iskonto')
                    <div class="pl-7 pb-1 flex items-center gap-2">
                      <span class="text-xs text-slate-400">İskonto limiti</span>
                      <input type="number" name="iskonto_limit" id="y_iskonto_limit" min="0" max="100" step="1" class="w-20 border border-slate-200 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none">
                      <span class="text-xs text-slate-400">%</span>
                    </div>
                  @endif
                  @if ($k === 'ikram')
                    <div class="pl-7 pb-1 flex items-center gap-2">
                      <span class="text-xs text-slate-400">İkram limiti</span>
                      <input type="number" name="ikram_limit" id="y_ikram_limit" min="0" step="1" class="w-24 border border-slate-200 rounded px-2 py-1 text-sm focus:ring-2 focus:ring-violet-300 focus:outline-none">
                      <span class="text-xs text-slate-400">₺ (üstü yönetici onayı)</span>
                    </div>
                  @endif
                @endif
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
      <div class="px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
        <button type="button" onclick="yetkiKapat()" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold">Vazgeç</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold">Yetkileri Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
  function personelAc(p) {
    document.getElementById('personelModalBaslik').textContent = p ? 'Personeli Düzenle' : 'Yeni Personel';
    document.getElementById('f_id').value = p ? p.id : '';
    document.getElementById('f_ad').value = p ? (p.ad || '') : '';
    document.getElementById('f_telefon').value = p ? (p.telefon || '') : '';
    document.getElementById('f_pin').value = p ? (p.pin || '') : '';
    document.getElementById('f_rol').value = p ? (p.rol || 'garson') : 'garson';
    document.getElementById('f_aktif').value = p ? String(p.aktif) : '1';
    var m = document.getElementById('personelModal');
    m.classList.remove('hidden'); m.classList.add('flex');
  }
  function personelKapat() {
    var m = document.getElementById('personelModal');
    m.classList.add('hidden'); m.classList.remove('flex');
  }
  function yetkiAc(p) {
    document.getElementById('y_id').value = p.id;
    document.getElementById('yetkiModalAd').textContent = p.ad || 'Personel';
    var y = p.yetkiler || {};
    document.querySelectorAll('#yetkiModal input[data-yetki]').forEach(function (el) {
      el.checked = y[el.dataset.yetki] === true;
    });
    document.getElementById('y_iskonto_limit').value = Math.round(p.iskonto_limit || 0);
    document.getElementById('y_ikram_limit').value = Math.round(p.ikram_limit || 0);
    var m = document.getElementById('yetkiModal');
    m.classList.remove('hidden'); m.classList.add('flex');
  }
  function yetkiKapat() {
    var m = document.getElementById('yetkiModal');
    m.classList.add('hidden'); m.classList.remove('flex');
  }
</script>
@endif
@endsection

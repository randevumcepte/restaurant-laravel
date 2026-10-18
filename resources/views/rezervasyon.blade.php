@extends('layout.app')
@section('title', 'Rezervasyon')
@section('baslik', '📅 Rezervasyon')

@section('content')
@php
  // --- Durum stili (Flutter rezervasyon_screen ile ayni) ---
  $durumAd = ['bekliyor' => 'BEKLİYOR', 'onaylandi' => 'ONAYLI', 'geldi' => 'GELDİ', 'iptal' => 'İPTAL', 'gelmedi' => 'GELMEDİ'];
  $durumCls = [
    'bekliyor'  => 'bg-amber-100 text-amber-700 border-amber-200',
    'onaylandi' => 'bg-blue-100 text-blue-700 border-blue-200',
    'geldi'     => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'iptal'     => 'bg-slate-100 text-slate-500 border-slate-200',
    'gelmedi'   => 'bg-rose-100 text-rose-700 border-rose-200',
  ];
  $kaynakIkon = ['web' => '🌐', 'telefon' => '📞', 'qr' => '📱', 'walk_in' => '🚶', 'walkin' => '🚶', 'admin' => '🖥️'];
  $bugun = \Carbon\Carbon::now()->format('Y-m-d');
@endphp

@if (session('rez_ok'))
  <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm font-semibold">✅ {{ session('rez_ok') }}</div>
@endif
@if (session('rez_hata'))
  <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-sm font-semibold">⚠️ {{ session('rez_hata') }}</div>
@endif

{{-- ================= GUN SECICI + OZET ================= --}}
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <div class="flex items-center gap-2 overflow-x-auto">
    @foreach ($gunler as $g)
      @php
        $aktif = $g['tarih'] === $tarih;
        $gd = \Carbon\Carbon::parse($g['tarih']);
        $fark = $gd->copy()->startOfDay()->diffInDays(\Carbon\Carbon::now()->startOfDay(), false);
        $etiket = $g['tarih'] === $bugun ? 'Bugün' : ($fark === -1 ? 'Yarın' : ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'][$gd->dayOfWeekIso - 1]);
      @endphp
      <a href="/rezervasyon?tarih={{ $g['tarih'] }}"
         class="relative flex flex-col items-center justify-center w-16 h-16 rounded-2xl border shrink-0 {{ $aktif ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50' }}">
        <span class="text-[11px] font-semibold {{ $aktif ? 'text-indigo-100' : 'text-slate-400' }}">{{ $etiket }}</span>
        <span class="text-lg font-extrabold leading-none mt-0.5">{{ $gd->day }}</span>
        @if ($g['adet'] > 0)
          <span class="absolute top-1 right-1 min-w-[18px] px-1 h-[18px] rounded-full text-[10px] font-bold flex items-center justify-center {{ $aktif ? 'bg-white/25 text-white' : 'bg-blue-500 text-white' }}">{{ $g['adet'] }}</span>
        @endif
      </a>
    @endforeach
  </div>
  <form method="GET" action="/rezervasyon" class="flex items-center gap-2">
    <input type="date" name="tarih" value="{{ $tarih }}"
           class="rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 shadow-sm"
           onchange="this.form.submit()">
  </form>
</div>

{{-- ================= OZET KARTLARI ================= --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
  @php
    $ozetKartlar = [
      ['Bugünün Rezervasyonları', $ozet['toplam'], 'text-indigo-700', 'bg-indigo-50', '📋'],
      ['Bekleyen', $ozet['bekleyen'], 'text-amber-700', 'bg-amber-50', '⏳'],
      ['Onaylanan', $ozet['onaylanan'], 'text-emerald-700', 'bg-emerald-50', '✅'],
      ['Toplam Misafir', $ozet['misafir'], 'text-blue-700', 'bg-blue-50', '👥'],
    ];
  @endphp
  @foreach ($ozetKartlar as [$ad, $v, $txt, $bg, $ik])
    <div class="rounded-2xl p-4 shadow-sm {{ $bg }}">
      <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">{{ $ik }} <span>{{ $ad }}</span></div>
      <div class="text-3xl font-extrabold {{ $txt }} mt-1">{{ $v }}</div>
    </div>
  @endforeach
</div>

<div class="grid gap-5 lg:grid-cols-3">
  {{-- ================= REZERVASYON LISTESI ================= --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
      <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold text-slate-800">Rezervasyonlar <span class="text-slate-400 font-medium">({{ count($rezervasyonlar) }})</span></h2>
        <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($tarih)->format('d.m.Y') }}</span>
      </div>

      @if (!count($rezervasyonlar))
        <div class="p-12 text-center">
          <div class="text-4xl mb-3">📅</div>
          <div class="font-bold text-slate-700">Bu gün için rezervasyon yok</div>
          <div class="text-sm text-slate-400 mt-1">Sağdaki formdan yeni rezervasyon ekleyebilirsiniz.</div>
        </div>
      @else
        <div class="divide-y divide-slate-100">
          @foreach ($rezervasyonlar as $r)
            @php
              $d = $r->durum;
              $pasif = in_array($d, ['iptal', 'gelmedi']);
              $not = trim((string) $r->not);
              $etiketler = array_filter(array_map('trim', explode(',', (string) ($r->etiketler ?? ''))));
            @endphp
            <div class="px-5 py-4 flex items-start gap-4 {{ $pasif ? 'opacity-60' : '' }}">
              <div class="w-14 shrink-0 text-center">
                <div class="text-lg font-extrabold {{ $pasif ? 'text-slate-400' : 'text-indigo-700' }}">{{ substr($r->saat, 0, 5) }}</div>
                <div class="text-[11px] text-slate-400">{{ (int) $r->kisi }} kişi</div>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-800 truncate {{ $pasif ? 'line-through' : '' }}">{{ $r->ad }}</span>
                  <span>{{ $kaynakIkon[strtolower((string) $r->kaynak)] ?? '•' }}</span>
                  @if (!empty($r->masa_ad))
                    <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-semibold">🪑 Masa {{ $r->masa_ad }}</span>
                  @endif
                </div>
                @if (!empty($r->telefon))
                  <div class="text-xs text-slate-400 mt-0.5">📞 {{ $r->telefon }}</div>
                @endif
                @if ($not !== '')
                  <div class="mt-1.5 text-xs text-slate-600 bg-amber-50 border border-amber-100 rounded-lg px-2.5 py-1.5">📝 {{ $not }}</div>
                @endif
                @if ($etiketler)
                  <div class="flex flex-wrap gap-1 mt-1.5">
                    @foreach ($etiketler as $e)
                      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[11px]">{{ $e }}</span>
                    @endforeach
                  </div>
                @endif
              </div>
              <div class="shrink-0 flex flex-col items-end gap-2">
                <span class="px-2.5 py-1 rounded-full border text-[11px] font-bold {{ $durumCls[$d] ?? 'bg-slate-100 text-slate-500 border-slate-200' }}">{{ $durumAd[$d] ?? strtoupper($d) }}</span>
                @if ($d === 'bekliyor')
                  <div class="flex gap-1.5">
                    <form method="POST" action="/rezervasyon/durum">@csrf
                      <input type="hidden" name="tarih" value="{{ $tarih }}">
                      <input type="hidden" name="id" value="{{ $r->id }}">
                      <input type="hidden" name="durum" value="onaylandi">
                      <button class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">Onayla</button>
                    </form>
                    <form method="POST" action="/rezervasyon/durum">@csrf
                      <input type="hidden" name="tarih" value="{{ $tarih }}">
                      <input type="hidden" name="id" value="{{ $r->id }}">
                      <input type="hidden" name="durum" value="iptal">
                      <button class="px-2.5 py-1 rounded-lg border border-rose-300 text-rose-600 text-xs font-semibold hover:bg-rose-50">İptal</button>
                    </form>
                  </div>
                @elseif ($d === 'onaylandi')
                  <div class="flex gap-1.5">
                    <form method="POST" action="/rezervasyon/durum">@csrf
                      <input type="hidden" name="tarih" value="{{ $tarih }}">
                      <input type="hidden" name="id" value="{{ $r->id }}">
                      <input type="hidden" name="durum" value="geldi">
                      <button class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Geldi</button>
                    </form>
                    <form method="POST" action="/rezervasyon/durum">@csrf
                      <input type="hidden" name="tarih" value="{{ $tarih }}">
                      <input type="hidden" name="id" value="{{ $r->id }}">
                      <input type="hidden" name="durum" value="gelmedi">
                      <button class="px-2.5 py-1 rounded-lg border border-rose-300 text-rose-600 text-xs font-semibold hover:bg-rose-50">Gelmedi</button>
                    </form>
                  </div>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- ================= YENI REZERVASYON FORMU ================= --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-2xl shadow-sm p-5 sticky top-4">
      <h2 class="font-bold text-slate-800 mb-4">➕ Yeni Rezervasyon</h2>
      <form method="POST" action="/rezervasyon/ekle" class="space-y-3">
        @csrf
        <input type="hidden" name="geri_tarih" value="{{ $tarih }}">
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Ad Soyad *</label>
          <input type="text" name="ad" required
                 class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Telefon</label>
          <input type="text" name="telefon"
                 class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Tarih *</label>
            <input type="date" name="tarih" value="{{ $tarih }}" min="{{ $bugun }}" required
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Saat *</label>
            <input type="time" name="saat" value="19:30" required
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Kişi</label>
            <input type="number" name="kisi" value="2" min="1" max="99"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-500 mb-1">Masa (opsiyonel)</label>
            <select name="masa_id" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700">
              <option value="">— Masa atama —</option>
              @foreach ($masalar as $m)
                <option value="{{ $m->id }}">Masa {{ $m->ad }}{{ !empty($m->kapasite) ? ' · ' . (int) $m->kapasite . ' kişilik' : '' }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-500 mb-1">Not (opsiyonel)</label>
          <textarea name="not" rows="2"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700"></textarea>
        </div>
        <button type="submit"
                class="w-full rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold py-2.5 text-sm hover:opacity-90">Kaydet</button>
      </form>
    </div>
  </div>
</div>
@endsection

@extends('layout.app')
@section('title', 'Kasa Noktaları')
@section('rk_ikon', 'storefront')
@section('baslik', 'Kasa Noktaları')
@section('rk_alt', 'Çoklu kasa — her kasanın ayrı vardiyası/çekmecesi + personel ataması')

@section('content')
@php
  $kasalar = $kasalar ?? collect();
  $personeller = $personeller ?? collect();
  $aktifSay = $kasalar->where('aktif', 1)->count();
@endphp

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

{{-- Bilgi kartı — iki model --}}
<div class="rk-card" style="display:flex;gap:14px;align-items:flex-start;padding:16px 18px;margin-bottom:16px">
  <span class="material-icons-outlined" style="color:#D97706;font-size:26px">info</span>
  <div style="flex:1;line-height:1.5">
    <div style="font-weight:800;color:var(--ink);font-size:14.5px;margin-bottom:4px">Kasa noktası nedir?</div>
    <div class="rk-sub" style="font-size:13px">
      Her fiziksel kasa (Kasa 1, Bar, Üst Kat…) bir <b>kasa noktasıdır</b> — kendi vardiyası ve nakit çekmecesi olur.
      Personeli bir kasaya <b>atarsan</b> giriş yapınca otomatik o kasada çalışır. Atamazsan ve birden fazla kasa varsa,
      girişte <b>hangi kasada olduğunu seçer</b>.
    </div>
    <div class="rk-sub2" style="font-size:12px;margin-top:6px">
      Hiç kasa noktası tanımlamazsan sistem <b>tek-havuz</b> (tek çekmece) çalışır — küçük işletme için yeterlidir.
    </div>
  </div>
</div>

{{-- Kasa ekle --}}
<div class="rk-card" style="margin-bottom:16px">
  <div class="rk-bolum"><span class="cubuk" style="background:#D97706"></span><span class="b">Yeni Kasa Noktası</span></div>
  <form method="POST" action="/kasalar/ekle" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px">
    @csrf
    <div style="flex:1;min-width:220px">
      <label class="rk-lbl" style="margin-top:0">Kasa adı</label>
      <input type="text" name="ad" maxlength="60" required placeholder='Örn. "Kasa 1", "Bar Kasa", "Üst Kat"' class="rk-inp">
    </div>
    <button type="submit" class="rk-btn" style="background:#D97706;padding:11px 18px">
      <span class="material-icons-outlined" style="color:#fff;font-size:18px">add</span> Ekle
    </button>
  </form>
</div>

{{-- Kasa listesi --}}
<div class="rk-bolum" style="justify-content:space-between">
  <div style="display:flex;align-items:center;gap:8px">
    <span class="cubuk" style="background:#D97706"></span><span class="b">Kasalar</span>
    <span class="rk-rozet" style="color:#D97706;background:color-mix(in srgb,#D97706 13%,transparent);border-color:color-mix(in srgb,#D97706 40%,transparent)">{{ $aktifSay }} aktif</span>
  </div>
</div>

@if ($kasalar->isEmpty())
  <div class="rk-card" style="text-align:center;padding:36px">
    <span class="material-icons-outlined" style="font-size:52px;color:var(--line)">storefront</span>
    <div class="rk-sub" style="margin-top:10px">Henüz kasa noktası yok. Tek-havuz modunda çalışıyorsunuz.</div>
    <div class="rk-sub2" style="font-size:12px;margin-top:6px">Birden fazla kasanız varsa yukarıdan ekleyin.</div>
  </div>
@else
  <div class="rk-grid" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
    @foreach ($kasalar as $k)
      @php $pasif = !($k->aktif ?? 1); @endphp
      <div class="rk-card" style="padding:16px;{{ $pasif ? 'opacity:.55' : '' }}">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
          <span class="material-icons-outlined" style="color:#D97706;font-size:24px">storefront</span>
          <div style="flex:1;font-weight:800;color:var(--ink);font-size:15.5px">{{ $k->ad }}</div>
          @if (!empty($k->acik))
            <span class="rk-rozet" style="color:var(--yesil);background:color-mix(in srgb,var(--yesil) 13%,transparent);border-color:color-mix(in srgb,var(--yesil) 40%,transparent)">● Vardiya açık</span>
          @elseif ($pasif)
            <span class="rk-rozet" style="color:var(--sub);background:var(--card2)">Pasif</span>
          @else
            <span class="rk-rozet" style="color:var(--sub2);background:var(--card2)">Kapalı</span>
          @endif
        </div>
        <form method="POST" action="/kasalar/duzenle" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          @csrf
          <input type="hidden" name="id" value="{{ $k->id }}">
          <input type="text" name="ad" value="{{ $k->ad }}" maxlength="60" class="rk-inp" style="flex:1;padding:8px 10px">
          <button type="submit" class="rk-btn bos" style="padding:8px 12px" title="Adı kaydet">
            <span class="material-icons-outlined" style="font-size:16px">save</span>
          </button>
        </form>
        <div style="display:flex;gap:8px">
          <form method="POST" action="/kasalar/duzenle" style="flex:1">
            @csrf
            <input type="hidden" name="id" value="{{ $k->id }}">
            <input type="hidden" name="aktif" value="{{ $pasif ? '1' : '0' }}">
            <button type="submit" class="rk-btn bos" style="width:100%;padding:8px">
              <span class="material-icons-outlined" style="font-size:16px">{{ $pasif ? 'toggle_on' : 'toggle_off' }}</span> {{ $pasif ? 'Aktif et' : 'Pasife al' }}
            </button>
          </form>
          <form method="POST" action="/kasalar/sil" onsubmit="return rkOnayForm(this, '{{ $k->ad }} kapatılsın mı? (Geçmiş kayıtlar korunur, pasife alınır)', 'var(--kirmizi)', 'Kapat')">
            @csrf
            <input type="hidden" name="id" value="{{ $k->id }}">
            <button type="submit" class="rk-btn bos" style="padding:8px 12px;color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 35%,transparent)" title="Kapat">
              <span class="material-icons-outlined" style="font-size:16px">delete_outline</span>
            </button>
          </form>
        </div>
      </div>
    @endforeach
  </div>
@endif

{{-- Personel ataması --}}
@if ($kasalar->where('aktif', 1)->count() && $personeller->count())
  <div class="rk-bolum" style="margin-top:24px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">Personel Ataması</span></div>
  <div class="rk-sub2" style="font-size:12px;margin-bottom:12px">
    Personeli bir kasaya atarsan giriş yapınca <b>otomatik o kasada</b> çalışır (küçük işletme / sabit kasiyer modeli).
    Boş bırakırsan, birden fazla kasa varsa girişte seçer (büyük işletme / dönüşümlü personel).
  </div>
  <div class="rk-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:40%">Personel</th>
          <th style="width:20%">Rol</th>
          <th style="width:40%">Atanmış Kasa</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($personeller as $p)
          <tr>
            <td style="font-weight:600">{{ $p->ad }}</td>
            <td class="rk-sub">{{ ucfirst($p->rol ?? '-') }}</td>
            <td>
              <form method="POST" action="/kasalar/atama" style="display:flex;gap:8px;align-items:center">
                @csrf
                <input type="hidden" name="personel_id" value="{{ $p->id }}">
                <select name="kasa_id" class="rk-inp" style="flex:1;padding:7px 10px" onchange="this.form.requestSubmit()">
                  <option value="0" {{ empty($p->varsayilan_kasa_id) ? 'selected' : '' }}>— Girişte seçsin —</option>
                  @foreach ($kasalar->where('aktif', 1) as $k)
                    <option value="{{ $k->id }}" {{ (int)($p->varsayilan_kasa_id ?? 0) === (int)$k->id ? 'selected' : '' }}>{{ $k->ad }}</option>
                  @endforeach
                </select>
                <noscript><button type="submit" class="rk-btn bos" style="padding:7px 12px">Kaydet</button></noscript>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
@endsection

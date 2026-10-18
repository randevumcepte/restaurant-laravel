@extends('layout.app')
@section('rk_ikon', 'qr_code')
@section('baslik', 'Barkod Yönetim')
@section('rk_alt', 'Bir ürüne ya da malzemeye barkod ekle/sil — çoklu barkod destekli (aynı öğenin birden çok barkodu olabilir)')

@section('content')
@php
  $urunler = $urunler ?? [];
  $malzemeler = $malzemeler ?? [];
  $barkodlar = $barkodlar ?? [];
@endphp

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div class="rk-grid" style="grid-template-columns:minmax(300px,380px) 1fr;align-items:start">

  {{-- ── Yeni Barkod ekleme formu ───────────────────────── --}}
  <div class="rk-card" style="display:flex;flex-direction:column;gap:2px">
    <div class="rk-bolum" style="margin:0 0 6px">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Yeni Barkod</span>
    </div>

    <form method="POST" action="/barkod/ekle" id="barkodForm" style="margin:0">
      @csrf

      <label class="rk-lbl" style="margin-top:4px">Barkod</label>
      <input type="text" name="barkod" required autocomplete="off" placeholder="Barkod numarası" class="rk-inp">

      <label class="rk-lbl">Tür</label>
      <div style="display:flex;gap:8px">
        <label style="flex:1;cursor:pointer">
          <input type="radio" name="tur" value="urun" class="brk-radio" checked onchange="barkodTurDegis()" style="position:absolute;opacity:0;pointer-events:none">
          <span class="brk-seg" data-tur="urun">Ürün</span>
        </label>
        <label style="flex:1;cursor:pointer">
          <input type="radio" name="tur" value="malzeme" class="brk-radio" onchange="barkodTurDegis()" style="position:absolute;opacity:0;pointer-events:none">
          <span class="brk-seg" data-tur="malzeme">Malzeme</span>
        </label>
      </div>

      <label class="rk-lbl">Bağlanacak öğe</label>
      <select name="hedef_id_urun" id="selUrun" class="rk-inp">
        <option value="">— Ürün seç —</option>
        @foreach ($urunler as $u)
          <option value="{{ $u->id }}">{{ $u->ad }}</option>
        @endforeach
      </select>
      <select name="hedef_id_malzeme" id="selMalzeme" class="rk-inp" disabled style="display:none">
        <option value="">— Malzeme seç —</option>
        @foreach ($malzemeler as $m)
          <option value="{{ $m->id }}">{{ $m->ad }}</option>
        @endforeach
      </select>

      <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:14px">
        <span class="material-icons-outlined" style="font-size:18px;color:#fff">add</span>
        <span>Barkod Ekle</span>
      </button>

      <p style="font-size:11.5px;color:var(--sub);line-height:1.5;margin:10px 0 0">
        Aynı barkod başka bir öğede tanımlıysa eklenmez. Bir öğenin birden çok barkodu olabilir.
      </p>
    </form>
  </div>

  {{-- ── Tanımlı barkodlar tablosu ──────────────────────── --}}
  <div style="display:flex;flex-direction:column;gap:10px">
    <div class="rk-bolum" style="margin:0">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Tanımlı Barkodlar</span>
      <span class="sayi" style="margin-left:auto;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 14%,transparent)">{{ count($barkodlar) }} barkod</span>
    </div>

    @if (!count($barkodlar))
      <div class="rk-card" style="text-align:center;color:var(--sub);font-size:14px;padding:40px 16px">
        Henüz barkod yok.<br>Soldaki formdan ekle.
      </div>
    @else
      <div class="rk-tablo">
        <table>
          <thead>
            <tr>
              <th>Barkod</th>
              <th>Bağlı öğe</th>
              <th>Tür</th>
              <th class="sag">İşlem</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($barkodlar as $b)
              <tr>
                <td style="font-weight:800;letter-spacing:.5px;font-variant-numeric:tabular-nums">
                  <span class="material-icons-outlined" style="font-size:16px;color:var(--mor1);vertical-align:-3px;margin-right:6px">qr_code_2</span>{{ $b->barkod }}
                </td>
                <td>{{ $b->hedef_ad ?? ('#' . $b->hedef_id . ' (silinmiş)') }}</td>
                <td>
                  @if ($b->tur === 'urun')
                    <span class="rk-rozet" style="color:#2563EB;border-color:color-mix(in srgb,#2563EB 40%,transparent);background:color-mix(in srgb,#2563EB 12%,transparent)">Ürün</span>
                  @else
                    <span class="rk-rozet" style="color:var(--amber);border-color:color-mix(in srgb,var(--amber) 40%,transparent);background:color-mix(in srgb,var(--amber) 12%,transparent)">Malzeme</span>
                  @endif
                </td>
                <td class="sag">
                  <form method="POST" action="/barkod/sil" onsubmit="return confirm('Bu barkodu sil?');" style="margin:0;line-height:0;display:inline">
                    @csrf
                    <input type="hidden" name="id" value="{{ $b->id }}">
                    <button type="submit" title="Sil" style="background:none;border:none;cursor:pointer;padding:4px;color:var(--kirmizi);display:inline-flex">
                      <span class="material-icons-outlined" style="font-size:18px">delete_outline</span>
                    </button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

</div>

<style>
  .brk-seg{display:block;text-align:center;padding:10px 8px;border:1.5px solid var(--line);border-radius:10px;font-size:14px;font-weight:700;color:var(--sub2);background:var(--card);transition:.15s}
  .brk-radio:checked + .brk-seg{background:var(--mor1);border-color:var(--mor1);color:#fff}
  @media (max-width:860px){ .rk-grid{grid-template-columns:1fr !important} }
</style>

<script>
  function barkodTurDegis() {
    var tur = document.querySelector('input[name="tur"]:checked').value;
    var su = document.getElementById('selUrun');
    var sm = document.getElementById('selMalzeme');
    if (tur === 'urun') {
      su.style.display = ''; su.disabled = false;
      sm.style.display = 'none'; sm.disabled = true;
    } else {
      sm.style.display = ''; sm.disabled = false;
      su.style.display = 'none'; su.disabled = true;
    }
  }
</script>
@endsection

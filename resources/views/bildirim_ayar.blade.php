@extends('layout.app')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Bildirim Ayarları')
@section('title', 'Bildirim Ayarları')

@section('content')
<div class="rk-body">

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:12px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Hangi bildirimler gelsin?</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-4px 2px 16px">
    Buradaki anahtarlar yalnızca <b>{{ $personel->ad ?? 'bu kullanıcı' }}</b> içindir. Kapattığın bildirim türü sana push olarak gönderilmez.
    Açık bıraktıkların telefon ve masaüstü uygulamasına gelir.
  </div>

  @php
    $rol = $personel->rol ?? '';
  @endphp

  <form method="POST" action="/bildirim-ayar-kaydet">
    @csrf
    <div class="rk-card" style="padding:6px 16px;max-width:640px">
      @foreach ($tipler as $tip => $info)
        @php
          $roller = $info[2] ?? null;
          if ($roller && !in_array($rol, $roller)) continue;
          $acik = array_key_exists($tip, $mevcut) ? ((int) $mevcut[$tip] === 1) : ((int) ($info[1] ?? 1) === 1);
        @endphp
        <label class="rk-yetki-satir" style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--line)">
          <span style="font-size:14px">{{ $info[0] }}</span>
          <input type="checkbox" name="tip_{{ $tip }}" class="rk-chk" @if($acik) checked @endif>
        </label>
      @endforeach
    </div>
    <div style="margin-top:16px">
      <button type="submit" class="rk-btn" style="background:var(--mor1)"><span class="material-icons-outlined" style="font-size:18px">save</span> Kaydet</button>
    </div>
  </form>

  <div class="rk-sub" style="font-size:11.5px;margin-top:16px;max-width:640px">
    Not: Garsona masa çağrısı, mutfağa yeni sipariş gibi <b>operasyonel</b> bildirimler departman varsayılanıdır — alt personelin ayrı ayar yapmasına gerek yoktur.
  </div>
</div>
@endsection

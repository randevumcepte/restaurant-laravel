@extends('layout.app')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Bildirim Ayarları')
@section('title', 'Bildirim Ayarları')

@section('content')
@php
  $bolumler = $bolumler ?? [];
  $bolumIkon = ['Ekip Yönetimi' => 'groups', 'Kişisel' => 'person', 'Yönetim Özetleri' => 'insights', 'Genel' => 'notifications'];
  $toplam = array_sum(array_map('count', $bolumler));
@endphp
<style>
  #ba{max-width:760px}
  #ba *{box-sizing:border-box}
  .ba-ust{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
  .ba-ust .bilgi{flex:1;min-width:220px;font-size:12.5px;color:var(--sub);line-height:1.5}
  .ba-hepsi{display:inline-flex;gap:6px}
  .ba-hepsi button{border:1px solid var(--line);background:var(--card);color:var(--sub2);border-radius:999px;padding:7px 13px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit}
  .ba-hepsi button:hover{border-color:var(--mor1);color:var(--mor1)}
  .ba-bolum{background:var(--card);border:1px solid var(--line);border-radius:18px;margin-bottom:14px;overflow:hidden}
  .ba-bas{display:flex;align-items:center;gap:10px;padding:13px 16px;border-bottom:1px solid var(--line);background:var(--card2)}
  .ba-bas .ik{width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}
  .ba-bas .ik .material-icons-outlined{font-size:18px}
  .ba-bas b{font-size:14px;color:var(--ink);flex:1}
  .ba-bas span.say{font-size:11.5px;color:var(--sub);font-weight:700}
  .ba-satir{display:flex;align-items:center;gap:12px;padding:11px 16px;border-bottom:1px solid var(--line);cursor:pointer}
  .ba-satir:last-child{border-bottom:0}
  .ba-satir:hover{background:color-mix(in srgb,var(--mor1) 4%,transparent)}
  .ba-satir .ad{flex:1;font-size:13.5px;color:var(--ink)}
  .ba-kritik{font-size:10px;font-weight:800;color:#E11D48;background:color-mix(in srgb,#F43F5E 12%,transparent);border-radius:999px;padding:2px 8px;margin-left:6px;white-space:nowrap}
  .ba-sw{position:relative;width:40px;height:23px;flex:none}
  .ba-sw input{opacity:0;width:0;height:0;position:absolute}
  .ba-sw i{position:absolute;inset:0;border-radius:999px;background:var(--line);transition:.18s}
  .ba-sw i::after{content:'';position:absolute;left:3px;top:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:.18s}
  .ba-sw input:checked + i{background:var(--mor1)}
  .ba-sw input:checked + i::after{transform:translateX(17px)}
  .ba-sw input:disabled + i{opacity:.55}
  .ba-kaydet{position:sticky;bottom:12px;display:flex;justify-content:flex-end;margin-top:6px}
  .ba-bos{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:36px 20px;text-align:center;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="ba">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="ba-ust">
    <div class="bilgi">Bu ayarlar yalnız <b>{{ $personel->ad ?? 'bu kullanıcı' }}</b> içindir. Açık olanlar panelde 🔔 zile ve telefona gelir. <b>Kapatılamaz</b> işaretliler acil durumlardır.</div>
    @if ($toplam)
      <div class="ba-hepsi"><button type="button" onclick="baHepsi(true)">Hepsini aç</button><button type="button" onclick="baHepsi(false)">Hepsini kapat</button></div>
    @endif
  </div>

  @if (!$toplam)
    <div class="ba-bos">Senin işinle ilgili ayarlanabilir bir bildirim yok.</div>
  @else
  <form method="POST" action="/bildirim-ayar-kaydet">
    @csrf
    @foreach ($bolumler as $bolum => $tipler)
      <div class="ba-bolum">
        <div class="ba-bas"><span class="ik"><span class="material-icons-outlined">{{ $bolumIkon[$bolum] ?? 'apartment' }}</span></span><b>{{ $bolum }}</b><span class="say">{{ count($tipler) }} bildirim</span></div>
        @foreach ($tipler as $tip => $t)
          <label class="ba-satir">
            <span class="ad">{{ $t['ad'] }}@if ($t['kritik'])<span class="ba-kritik">Kapatılamaz</span>@endif</span>
            <span class="ba-sw"><input type="checkbox" name="tip_{{ $tip }}" @if ($t['acik']) checked @endif @if ($t['kritik']) disabled @endif><i></i></span>
          </label>
        @endforeach
      </div>
    @endforeach
    <div class="ba-kaydet"><button type="submit" class="rk-btn" style="background:var(--mor1)"><span class="material-icons-outlined" style="font-size:18px">save</span> Kaydet</button></div>
  </form>
  @endif
</div>
</div>
<script>
  function baHepsi(v){ document.querySelectorAll('#ba input[type=checkbox]:not(:disabled)').forEach(function(c){ c.checked = v; }); }
</script>
@endsection

@extends('layout.app')
@section('rk_ikon', 'celebration')
@section('baslik', 'Etkinlik / Grup Takvimi')
@section('title', 'Etkinlik Takvimi')

@section('content')
@php
  $durRenk = ['bekliyor'=>'#F59E0B','onaylandi'=>'#2FBE94','geldi'=>'#5B8DEF','gelmedi'=>'#F43F5E'];
  $durAd = ['bekliyor'=>'Bekliyor','onaylandi'=>'Onaylı','geldi'=>'Geldi','gelmedi'=>'Gelmedi'];
@endphp
<style>
  #et{max-width:860px;margin:0 auto}
  #et *{box-sizing:border-box}
  .et-bas{font-size:13px;color:var(--sub);margin:0 2px 14px}
  .et-gun{margin-bottom:16px}
  .et-gbas{display:flex;align-items:center;gap:9px;font-size:14px;font-weight:800;color:var(--ink);margin:0 2px 9px}
  .et-gbas .say{margin-left:auto;font-size:11.5px;color:var(--sub);font-weight:700}
  .et-list{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  .et-r{display:flex;align-items:center;gap:12px;padding:11px 15px;border-top:1px solid var(--line);font-size:13px}
  .et-r:first-child{border-top:0}
  .et-saat{font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums;width:48px;flex:none}
  .et-ad{flex:1;min-width:0;font-weight:700;color:var(--ink)} .et-ad .mt{font-size:11px;color:var(--sub);font-weight:500}
  .et-kisi{display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--sub);font-weight:700}
  .et-grup{font-size:9px;font-weight:800;background:#A855F7;color:#fff;padding:2px 7px;border-radius:20px}
  .et-dur{font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;color:#fff}
  .et-bos{text-align:center;padding:40px 20px;color:var(--sub);font-size:13.5px}
  .et-bos .material-icons-outlined{font-size:42px;display:block;margin:0 auto 8px;opacity:.5}
</style>
<div class="rk-body">
<div id="et">
  <div class="et-bas">Önümüzdeki 14 günün rezervasyon ve grup/etkinlikleri. 8+ kişi <b style="color:#A855F7">GRUP</b> olarak işaretlenir.</div>
  @forelse($gunler as $tarih => $rezler)
    <div class="et-gun">
      <div class="et-gbas"><span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">event</span>{{ \Carbon\Carbon::parse($tarih)->translatedFormat('d F, l') }}<span class="say">{{ count($rezler) }} rezervasyon · {{ collect($rezler)->sum('kisi') }} kişi</span></div>
      <div class="et-list">
        @foreach($rezler as $rz)
          <div class="et-r">
            <span class="et-saat">{{ $rz->saat }}</span>
            <div class="et-ad">{{ $rz->ad }}@if($rz->kisi>=8) <span class="et-grup">GRUP</span>@endif<div class="mt">{{ $rz->telefon }}{{ $rz->not ? ' · '.$rz->not : '' }}</div></div>
            <span class="et-kisi"><span class="material-icons-outlined" style="font-size:15px">group</span>{{ $rz->kisi }}</span>
            <span class="et-dur" style="background:{{ $durRenk[$rz->durum] ?? '#64748B' }}">{{ $durAd[$rz->durum] ?? $rz->durum }}</span>
          </div>
        @endforeach
      </div>
    </div>
  @empty
    <div class="et-bos"><span class="material-icons-outlined">event_available</span>Önümüzdeki 14 günde rezervasyon yok. <a href="/rezervasyon" style="color:var(--mor1);font-weight:700;text-decoration:none">Rezervasyon ekle →</a></div>
  @endforelse
</div>
</div>
@endsection

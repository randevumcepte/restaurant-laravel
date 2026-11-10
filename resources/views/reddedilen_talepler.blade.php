@extends('layout.app')
@section('rk_ikon', 'block')
@section('baslik', 'Reddedilen Talepler')
@section('title', 'Reddedilen Talepler')

@section('content')
<style>
  #rt{max-width:960px;margin:0 auto}
  #rt *{box-sizing:border-box}
  .rt-bos{text-align:center;padding:48px 20px;color:var(--sub);font-size:14px}
  .rt-bos .material-icons-outlined{font-size:44px;color:var(--sub2);display:block;margin-bottom:10px}
  .rt-not{display:flex;gap:8px;align-items:flex-start;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .rt-not .material-icons-outlined{font-size:16px;color:var(--sub2);flex:none;margin-top:1px}
  .rt-kart{background:var(--card);border:1px solid var(--line);border-left:4px solid #F43F5E;border-radius:14px;padding:14px 16px;margin-bottom:12px}
  .rt-ust{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px}
  .rt-dep{font-weight:800;font-size:14.5px;color:var(--ink)}
  .rt-badge{font-size:10px;font-weight:800;border-radius:20px;padding:2px 9px;white-space:nowrap}
  .rt-acil{background:rgba(244,63,94,.12);color:#e11d48}
  .rt-meta{font-size:11.5px;color:var(--sub);margin-left:auto}
  .rt-kalem{font-size:12.5px;color:var(--sub2);margin-bottom:8px}
  .rt-sebep{display:flex;gap:8px;align-items:flex-start;background:rgba(244,63,94,.08);border-radius:10px;padding:9px 12px;font-size:13px;color:var(--ink)}
  .rt-sebep .material-icons-outlined{font-size:17px;color:#e11d48;flex:none;margin-top:1px}
  .rt-kim{font-size:11px;color:var(--sub);margin-top:7px;text-align:right}
  .rt-tekrar{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:8px 13px;border-radius:10px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);font-weight:700;font-size:12.5px;text-decoration:none}
  .rt-tekrar .material-icons-outlined{font-size:17px}
</style>
<div class="rk-body">
<div id="rt">
  <div class="rt-not"><span class="material-icons-outlined">info</span><span>Departman sorumlusu tarafından reddedilen talepler burada kalıcı olarak tutulur. Her reddin bir sebebi vardır.</span></div>
  @if($talepler->isEmpty())
    <div class="rt-bos"><span class="material-icons-outlined">inventory_2</span>Reddedilen talep yok.</div>
  @else
    @foreach($talepler as $t)
      <div class="rt-kart">
        <div class="rt-ust">
          <span class="rt-dep">{{ $t->departman }}</span>
          @if($t->aciliyet === 'acil')<span class="rt-badge rt-acil">🔴 ACİL</span>@endif
          <span class="rt-meta">👤 {{ $t->isteyen_ad }} · talep {{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->format('d.m.Y') : '' }}</span>
        </div>
        @if($t->kalem_ozet)<div class="rt-kalem">📦 {{ $t->kalem_ozet }}</div>@endif
        <div class="rt-sebep"><span class="material-icons-outlined">block</span><span><b>Red sebebi:</b> {{ $t->red_sebep ?: '—' }}</span></div>
        <div class="rt-kim">Reddeden: {{ $t->karar_veren_ad ?: '—' }}{{ $t->karar_at ? ' · ' . \Carbon\Carbon::parse($t->karar_at)->format('d.m.Y H:i') : '' }}</div>
        @if((int) $t->isteyen_id === ($benId ?? 0) || !empty($yonetici))
          <a class="rt-tekrar" href="/satinalma-talep?kopya={{ $t->id }}"><span class="material-icons-outlined">edit_note</span> Düzenle ve tekrar gönder</a>
        @endif
      </div>
    @endforeach
  @endif
</div>
</div>
@endsection

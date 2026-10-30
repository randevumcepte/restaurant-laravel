@extends('layout.app')
@section('rk_ikon', 'approval')
@section('baslik', 'Satınalma Onay Seviyeleri')
@section('title', 'Onay Seviyeleri')

@push('head')
<style>
#os{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #os{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#os{background:var(--bg);min-height:calc(100vh - 60px)}#os *{box-sizing:border-box}
#os .pano{max-width:860px;margin:0 auto;padding:18px 18px 40px}
#os .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#os .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#os .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#os .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#os .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#os .flag{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
#os .sw{position:relative;display:inline-block;width:50px;height:27px}
#os .sw input{opacity:0;width:0;height:0}
#os .sl{position:absolute;inset:0;background:var(--card2);border-radius:20px;transition:.2s;cursor:pointer}
#os .sl::before{content:'';position:absolute;height:21px;width:21px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.3)}
#os .sw input:checked + .sl{background:var(--yesil)}
#os .sw input:checked + .sl::before{transform:translateX(23px)}
#os .zincir{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:12px}
#os .kade{display:flex;align-items:center;gap:9px;background:var(--card2);border-radius:12px;padding:9px 13px;position:relative}
#os .kade .no{width:24px;height:24px;border-radius:50%;background:var(--mor1);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
#os .kade .ic .ad{font-size:13px;font-weight:700;color:var(--ink)}
#os .kade .ic .vk{font-size:10.5px;color:var(--sub)}
#os .kade .sil{border:0;background:none;color:var(--sub2);cursor:pointer;padding:0 2px}
#os .kade .sil:hover{color:var(--kirmizi)}
#os .ok{color:var(--sub2);font-size:20px}
#os .roz{font-size:9.5px;font-weight:800;padding:1px 7px;border-radius:20px;background:color-mix(in srgb,var(--amber) 16%,transparent);color:#92600a}
#os label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#os select,#os input[type=date]{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#os .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end}
@media(max-width:620px){#os .form-grid{grid-template-columns:1fr}}
#os .chk{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--ink);font-weight:600;margin-top:8px}
#os .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#os .btn .material-icons-outlined{font-size:18px}
#os .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
</style>
@endpush

@section('content')
<div id="os"><div class="pano">

  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Çok-kademeli onay:</b> Satın alma talebi onaylanmadan önce sırayla belirlediğin kişilerden geçsin (ör. Şef → Maliyet Kontrol → Ana Depo). Her kademeye bir <b>onaylayan</b> + tatildeyse <b>vekil</b> atayabilirsin. <b>Küçük işletmede gerek yok</b> → kapalı bırak (patron direkt onaylar). Kurumsal/çok-şubede aç.
  </div>

  {{-- Flag --}}
  <div class="kart">
    <form method="POST" action="/satinalma-onay-seviye/flag" class="flag">
      @csrf
      <div><div style="font-size:14px;font-weight:800;color:var(--ink)">Çok-Kademeli Onay</div>
        <div style="font-size:12px;color:var(--sub)">{{ $acik ? 'Açık — talepler kademelerden geçer' : 'Kapalı — patron/satınalmacı direkt onaylar' }}</div></div>
      <label class="sw"><input type="checkbox" name="acik" {{ $acik ? 'checked' : '' }} onchange="this.form.submit()"><span class="sl"></span></label>
    </form>
  </div>

  {{-- Zincir --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">account_tree</span> Onay Zinciri</div>
    @if($seviyeler->count())
      <div class="zincir">
        <div class="kade"><div class="no" style="background:var(--sub)">📋</div><div class="ic"><div class="ad">Talep</div></div></div>
        @foreach($seviyeler as $s)
          <span class="ok material-icons-outlined">arrow_forward</span>
          <div class="kade">
            <div class="no">{{ $s->seviye }}</div>
            <div class="ic">
              <div class="ad">{{ $adMap[$s->onaylayan_id] ?? '—' }}</div>
              @if($s->vekil_id)<div class="vk">vekil: {{ $adMap[$s->vekil_id] ?? '—' }}{{ $s->vekil_bitis ? ' ('.$s->vekil_bitis.' tarihine dek)' : '' }}</div>@endif
              @if($s->anlasma_atla)<span class="roz">anlaşmalı atla</span>@endif
            </div>
            <form method="POST" action="/satinalma-onay-seviye/sil" style="margin:0" onsubmit="return confirm('Bu kademeyi sil?')">@csrf<input type="hidden" name="id" value="{{ $s->id }}"><button class="sil" type="submit"><span class="material-icons-outlined" style="font-size:17px">close</span></button></form>
          </div>
        @endforeach
        <span class="ok material-icons-outlined">arrow_forward</span>
        <div class="kade"><div class="no" style="background:var(--yesil)">✓</div><div class="ic"><div class="ad">Onaylandı → Sipariş</div></div></div>
      </div>
    @else
      <div class="bos">Henüz onay kademesi yok. Aşağıdan ekle (sırayla eklediğin kişiler kademe olur).</div>
    @endif
  </div>

  {{-- Ekle --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Kademe Ekle</div>
    <form method="POST" action="/satinalma-onay-seviye/ekle">
      @csrf
      <div class="form-grid">
        <div><label>Onaylayan *</label>
          <select name="onaylayan_id" required>
            <option value="">— seç —</option>
            @foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }} ({{ $p->rol }})</option>@endforeach
          </select></div>
        <div><label>Vekil (tatildeyse — opsiyonel)</label>
          <select name="vekil_id">
            <option value="">— yok —</option>
            @foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }}</option>@endforeach
          </select></div>
        <div><label>Vekil geçerlilik bitişi (opsiyonel)</label><input type="date" name="vekil_bitis"></div>
        <div><label class="chk"><input type="checkbox" name="anlasma_atla" value="1"> Fiyat anlaşması olan taleplerde bu onayı atla</label></div>
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">add</span> Kademe Ekle</button>
    </form>
  </div>

</div></div>
@endsection

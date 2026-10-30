@extends('layout.app')
@section('rk_ikon', 'dinner_dining')
@section('baslik', 'Fix Menü / Set Menü')
@section('title', 'Fix Menü')

@push('head')
<style>
#fm{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #fm{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#fm{background:var(--bg);min-height:calc(100vh - 60px)}#fm *{box-sizing:border-box}
#fm .pano{max-width:880px;margin:0 auto;padding:18px 18px 40px}
#fm .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#fm .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#fm .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#fm .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#fm .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#fm .mh{display:flex;align-items:center;gap:12px;margin-bottom:10px}
#fm .mh .tt{flex:1}
#fm .mh .ad{font-size:15px;font-weight:800;color:var(--ink)}
#fm .mh .fi{font-size:18px;font-weight:900;color:var(--yesil)}
#fm .grp{font-size:11px;font-weight:800;color:var(--mor1);text-transform:uppercase;letter-spacing:.3px;margin:8px 0 4px}
#fm .kal{display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--card2);border-radius:9px;margin-bottom:4px;font-size:12.5px}
#fm .kal .ad{flex:1;color:var(--ink);font-weight:600}
#fm .kal .z{font-size:9.5px;font-weight:800;padding:1px 7px;border-radius:20px}
#fm .kal .z.zor{background:color-mix(in srgb,var(--kirmizi) 14%,transparent);color:var(--kirmizi)}
#fm .kal .z.ops{background:var(--card);color:var(--sub2)}
#fm label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#fm input,#fm select{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#fm .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;align-items:end}
@media(max-width:620px){#fm .grid{grid-template-columns:1fr}}
#fm .btn{border:0;border-radius:10px;padding:9px 15px;font-weight:700;font-size:12.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:6px}
#fm .btn.k{background:var(--card2);color:var(--sub);padding:5px 10px;font-size:11px}
#fm .btn .material-icons-outlined{font-size:16px}
#fm .kalem-form{display:flex;gap:8px;flex-wrap:wrap;align-items:end;margin-top:10px;padding-top:10px;border-top:1px dashed var(--line)}
#fm .kalem-form>div{flex:1;min-width:120px}
#fm .chk{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--ink);font-weight:600}
#fm .chk input{width:auto}
#fm .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
</style>
@endpush

@section('content')
<div id="fm"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Fix / Set Menü:</b> Sabit fiyatlı menü oluştur (ör. İftar Menüsü, Düğün Menüsü). İçine kurs grupları (Ara Sıcak, Ana Yemek, Tatlı…) ve ürünler ekle. İftar/etkinlik/banket için idealdir.
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Yeni Set Menü</div>
    <form method="POST" action="/fix-menu/ekle">
      @csrf
      <div class="grid">
        <div><label>Menü adı</label><input name="ad" placeholder="örn. İftar Menüsü" required></div>
        <div><label>Fiyat (₺)</label><input name="fiyat" inputmode="decimal" placeholder="örn. 450" required></div>
      </div>
      <div style="margin-top:8px"><label>Açıklama (opsiyonel)</label><input name="aciklama" placeholder="örn. 4 kap, çay dahil"></div>
      <button class="btn" type="submit" style="margin-top:10px"><span class="material-icons-outlined">add</span> Set Menü Oluştur</button>
    </form>
  </div>

  @forelse($menuler as $m)
  <div class="kart">
    <div class="mh">
      <div class="tt"><div class="ad">{{ $m->ad }} @if(!$m->aktif)<span style="font-size:10px;color:var(--sub2)">(pasif)</span>@endif</div>
        @if($m->aciklama)<div style="font-size:11.5px;color:var(--sub)">{{ $m->aciklama }}</div>@endif</div>
      <div class="fi">{{ number_format($m->fiyat,0,',','.') }} ₺</div>
      <form method="POST" action="/fix-menu/sil" onsubmit="return confirm('{{ $m->ad }} set menüsünü sil?')" style="margin:0">@csrf<input type="hidden" name="menu_id" value="{{ $m->id }}"><button class="btn k" type="submit"><span class="material-icons-outlined">delete</span></button></form>
    </div>

    @php $kl = ($kalemler[$m->id] ?? collect())->groupBy(fn($x)=>$x->grup ?: 'Diğer'); @endphp
    @forelse($kl as $grup=>$items)
      <div class="grp">{{ $grup }}</div>
      @foreach($items as $it)
        <div class="kal">
          <span class="ad">{{ $it->urun_ad }}</span>
          <span class="z {{ $it->zorunlu?'zor':'ops' }}">{{ $it->zorunlu?'zorunlu':'seçime bağlı' }}</span>
          <form method="POST" action="/fix-menu/sil" style="margin:0">@csrf<input type="hidden" name="kalem_id" value="{{ $it->id }}"><button class="btn k" type="submit" style="padding:3px 7px"><span class="material-icons-outlined" style="font-size:14px">close</span></button></form>
        </div>
      @endforeach
    @empty
      <div class="bos" style="padding:12px">Henüz ürün yok — aşağıdan ekle.</div>
    @endforelse

    <form method="POST" action="/fix-menu/kalem-ekle" class="kalem-form">
      @csrf<input type="hidden" name="fix_menu_id" value="{{ $m->id }}">
      <div><label>Grup/Kurs</label><input name="grup" placeholder="örn. Ana Yemek"></div>
      <div><label>Ürün</label><select name="urun_id"><option value="">— serbest yaz —</option>@foreach($urunler as $u)<option value="{{ $u->id }}">{{ $u->ad }}</option>@endforeach</select></div>
      <div><label>veya ürün adı</label><input name="urun_ad" placeholder="menüde yoksa yaz"></div>
      <div style="flex:0 0 auto"><label class="chk"><input type="checkbox" name="zorunlu" value="1" checked> Zorunlu</label></div>
      <button class="btn" type="submit"><span class="material-icons-outlined">add</span> Ekle</button>
    </form>
  </div>
  @empty
    <div class="kart"><div class="bos">Henüz set menü yok. Yukarıdan oluştur.</div></div>
  @endforelse

</div></div>
@endsection

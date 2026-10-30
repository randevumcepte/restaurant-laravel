@extends('layout.app')
@section('rk_ikon', 'chair')
@section('baslik', 'Demirbaş / Ekipman')
@section('title', 'Demirbaş')

@push('head')
<style>
#db2{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #db2{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#db2{background:var(--bg);min-height:calc(100vh - 60px)}#db2 *{box-sizing:border-box}
#db2 .pano{max-width:960px;margin:0 auto;padding:18px 18px 40px}
#db2 .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#db2 .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#db2 .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#db2 .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#db2 .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#db2 label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#db2 input,#db2 select{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#db2 .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
@media(max-width:700px){#db2 .grid{grid-template-columns:1fr 1fr}}
#db2 .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#db2 .btn .material-icons-outlined{font-size:18px}
#db2 .ozet{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-radius:14px;padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
#db2 .ozet .d{font-size:22px;font-weight:900}
#db2 .dem{border:1px solid var(--line);border-radius:14px;padding:13px;margin-bottom:10px}
#db2 .dh{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
#db2 .dh .ik{width:42px;height:42px;border-radius:12px;background:color-mix(in srgb,var(--mor1) 12%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0}
#db2 .dh .ik .material-icons-outlined{color:var(--mor1);font-size:22px}
#db2 .dh .ic{flex:1;min-width:150px}
#db2 .dh .ad{font-size:14px;font-weight:800;color:var(--ink)}
#db2 .dh .alt{font-size:11.5px;color:var(--sub);margin-top:2px;line-height:1.4}
#db2 .dh .bd{font-size:15px;font-weight:800;color:var(--ink)}
#db2 .roz{font-size:9.5px;font-weight:800;padding:2px 8px;border-radius:20px}
#db2 .roz.aktif{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:#0b805e}
#db2 .roz.bakimda{background:color-mix(in srgb,var(--amber) 16%,transparent);color:#92600a}
#db2 .roz.hurda{background:var(--card2);color:var(--sub2)}
#db2 summary{cursor:pointer;font-size:11.5px;color:var(--mor1);font-weight:700;margin-top:8px;list-style:none}
#db2 .bk{display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--card2);border-radius:9px;margin:4px 0;font-size:11.5px}
#db2 .bk .t{flex:1;color:var(--sub)}
#db2 .bk .tip{font-size:9px;font-weight:800;padding:1px 6px;border-radius:12px}
#db2 .bk .tip.ariza{background:rgba(244,63,94,.14);color:var(--kirmizi)}#db2 .bk .tip.bakim{background:color-mix(in srgb,var(--mavi) 12%,transparent);color:var(--mavi)}
#db2 .bform{display:flex;gap:7px;flex-wrap:wrap;align-items:end;margin-top:8px;padding-top:8px;border-top:1px dashed var(--line)}
#db2 .bform>div{flex:1;min-width:90px}
#db2 .btnk{border:0;border-radius:8px;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;background:var(--card2);color:var(--sub)}
#db2 .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
</style>
@endpush

@section('content')
@php $tl=fn($v)=>number_format((float)$v,0,',','.'); $durAd=['aktif'=>'Aktif','bakimda'=>'Bakımda','hurda'=>'Hurda']; @endphp
<div id="db2"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Demirbaş / Ekipman:</b> Fırın, buzdolabı, mobilya, POS cihazı gibi ekipmanları kaydet; <b>zimmet</b> (kimde), <b>lokasyon</b> ve <b>bakım/arıza geçmişi</b> tut. Not: Bu <b>vergi amortismanı değil</b> — amortisman/yeniden değerlemeyi mali müşavirin yapar; burası operasyonel takip.
  </div>

  <div class="ozet"><span>Toplam ekipman değeri (hurda hariç)</span><span class="d">{{ $tl($toplamDeger) }} ₺</span></div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Yeni Demirbaş</div>
    <form method="POST" action="/demirbas/ekle">
      @csrf
      <div class="grid">
        <div style="grid-column:span 2"><label>Ad *</label><input name="ad" placeholder="örn. Sanayi Tipi Fırın" required></div>
        <div><label>Kod / Barkod</label><input name="kod"></div>
        <div><label>Kategori</label><input name="kategori" placeholder="mutfak/mobilya/IT"></div>
        <div><label>Alış Tarihi</label><input type="date" name="alis_tarih"></div>
        <div><label>Alış Bedeli (₺)</label><input name="alis_bedel" inputmode="decimal"></div>
        <div><label>Lokasyon</label><input name="lokasyon" placeholder="mutfak/salon/depo"></div>
        <div><label>Zimmet (kimde)</label><select name="zimmet_personel_id"><option value="">— yok —</option>@foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }}</option>@endforeach</select></div>
        <div style="grid-column:span 1"><label>Not</label><input name="not"></div>
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">save</span> Demirbaş Ekle</button>
    </form>
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">inventory_2</span> Ekipmanlar</div>
    @forelse($liste as $d)
      <div class="dem">
        <div class="dh">
          <div class="ik"><span class="material-icons-outlined">handyman</span></div>
          <div class="ic">
            <div class="ad">{{ $d->ad }} <span class="roz {{ $d->durum }}">{{ $durAd[$d->durum] ?? $d->durum }}</span></div>
            <div class="alt">
              @if($d->kategori){{ $d->kategori }} · @endif{{ $d->kod ? ('#'.$d->kod.' · ') : '' }}{{ $d->lokasyon ?: 'lokasyon yok' }}
              @if($d->zimmet_personel_id) · 👤 {{ $persAd[$d->zimmet_personel_id] ?? '—' }}@endif
              @if($d->alis_tarih) · 📅 {{ \Carbon\Carbon::parse($d->alis_tarih)->format('d.m.Y') }}@endif
            </div>
          </div>
          <div class="bd">{{ $tl($d->alis_bedel) }} ₺</div>
          <form method="POST" action="/demirbas/durum" style="margin:0"><select name="yeni_durum" onchange="this.form.submit()" style="width:auto;font-size:11px;padding:4px 6px">@csrf<option disabled selected>durum</option><option value="aktif">Aktif</option><option value="bakimda">Bakımda</option><option value="hurda">Hurda</option></select></form>
          <form method="POST" action="/demirbas/durum" onsubmit="return confirm('{{ $d->ad }} sil?')" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="sil" value="1"><button class="btnk" type="submit" style="color:var(--kirmizi)">Sil</button></form>
        </div>
        <details>
          <summary>🔧 Bakım/Arıza geçmişi ({{ ($bakimlar[$d->id] ?? collect())->count() }})</summary>
          @foreach(($bakimlar[$d->id] ?? collect()) as $b)
            <div class="bk"><span class="tip {{ $b->tip }}">{{ $b->tip=='ariza'?'ARIZA':'BAKIM' }}</span><span class="t">{{ \Carbon\Carbon::parse($b->tarih)->format('d.m.y') }} · {{ $b->aciklama }}</span>@if($b->maliyet>0)<span style="font-weight:800">{{ $tl($b->maliyet) }}₺</span>@endif</div>
          @endforeach
          <form method="POST" action="/demirbas/bakim-ekle" class="bform">
            @csrf<input type="hidden" name="demirbas_id" value="{{ $d->id }}">
            <div style="flex:0 0 100px"><label>Tip</label><select name="tip"><option value="bakim">Bakım</option><option value="ariza">Arıza</option></select></div>
            <div style="flex:0 0 130px"><label>Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}"></div>
            <div style="flex:2"><label>Açıklama</label><input name="aciklama" placeholder="ne yapıldı"></div>
            <div style="flex:0 0 90px"><label>Maliyet</label><input name="maliyet" inputmode="decimal"></div>
            <button class="btnk" type="submit" style="background:var(--mor1);color:#fff">Ekle</button>
          </form>
        </details>
      </div>
    @empty
      <div class="bos">Henüz demirbaş yok. Yukarıdan ekle.</div>
    @endforelse
  </div>

</div></div>
@endsection

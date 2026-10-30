@extends('layout.app')
@section('rk_ikon', 'local_bar')
@section('baslik', 'Happy Hour')
@section('title', 'Happy Hour')

@push('head')
<style>
#hh{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #hh{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#hh{background:var(--bg);min-height:calc(100vh - 60px)}#hh *{box-sizing:border-box}
#hh .pano{max-width:900px;margin:0 auto;padding:18px 18px 40px}
#hh .aciklama{background:var(--card);border-left:4px solid var(--amber);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#hh .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#hh .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#hh .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#hh .kb .material-icons-outlined{color:var(--amber);font-size:19px}
#hh .kural{display:flex;align-items:center;gap:12px;padding:12px;border-radius:12px;border:1px solid var(--line);margin-bottom:8px;flex-wrap:wrap}
#hh .kural.pasif{opacity:.5}
#hh .kural .ik{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,var(--amber),#d97706);display:flex;align-items:center;justify-content:center;flex-shrink:0}
#hh .kural .ik .material-icons-outlined{color:#fff;font-size:21px}
#hh .kural .ic{flex:1;min-width:150px}
#hh .kural .ad{font-size:13.5px;font-weight:800;color:var(--ink)}
#hh .kural .det{font-size:11.5px;color:var(--sub);margin-top:2px;line-height:1.4}
#hh .kural .ind{font-size:17px;font-weight:900;color:var(--kirmizi)}
#hh .kural .aks{display:flex;gap:6px}
#hh .btnm{border:0;border-radius:8px;padding:6px 11px;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px}
#hh .btnm.t{background:var(--card2);color:var(--sub)}#hh .btnm.s{background:rgba(244,63,94,.12);color:var(--kirmizi)}
#hh .btnm .material-icons-outlined{font-size:15px}
#hh label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#hh input,#hh select{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#hh .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:620px){#hh .grid{grid-template-columns:1fr}}
#hh .gunler{display:flex;gap:5px;flex-wrap:wrap;margin-top:4px}
#hh .gun{display:flex;align-items:center;gap:4px;font-size:12px;color:var(--ink);background:var(--card2);border-radius:8px;padding:6px 9px;cursor:pointer;user-select:none}
#hh .gun input{width:auto}
#hh .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--amber);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#hh .btn .material-icons-outlined{font-size:18px}
#hh .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
</style>
@endpush

@section('content')
@php
  $gunAd=[1=>'Pzt',2=>'Sal',3=>'Çar',4=>'Per',5=>'Cum',6=>'Cmt',7=>'Paz'];
@endphp
<div id="hh"><div class="pano">

  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Happy Hour:</b> Belirli ürün/kategoride, seçtiğin <b>gün + saat</b> aralığında POS'ta otomatik indirim/özel fiyat uygulanır. Örn: "Hafta içi 17:00-19:00 bira %20". Garson ürünü eklerken fiyat otomatik düşer — ekstra işlem yok.
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">list</span> Tanımlı Kurallar</div>
    @forelse($kurallar as $h)
      <div class="kural {{ $h->aktif ? '' : 'pasif' }}">
        <div class="ik"><span class="material-icons-outlined">local_bar</span></div>
        <div class="ic">
          <div class="ad">{{ $h->ad }} @if(!$h->aktif)<span style="font-size:10px;color:var(--sub2)">(pasif)</span>@endif</div>
          <div class="det">
            {{ $h->urun_id ? ($urunAd[$h->urun_id] ?? 'ürün') : ($h->kategori_id ? ('Kategori: '.($katAd[$h->kategori_id] ?? '')) : 'Tüm ürünler') }}
            @if($h->baslangic_saat) · ⏰ {{ $h->baslangic_saat }}-{{ $h->bitis_saat }}@endif
            @if($h->gunler) · {{ collect(explode(',',$h->gunler))->map(fn($g)=>$gunAd[(int)$g]??'')->implode(', ') }}@endif
            @if($h->baslangic_tarih) · 📅 {{ $h->baslangic_tarih }}→{{ $h->bitis_tarih ?: '…' }}@endif
          </div>
        </div>
        <div class="ind">{{ $h->happy_fiyat>0 ? number_format($h->happy_fiyat,0,',','.').'₺' : '%'.rtrim(rtrim(number_format($h->indirim_yuzde,2,',','.'),'0'),',') }}</div>
        <div class="aks">
          <form method="POST" action="/happy-hour/durum" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $h->id }}"><button class="btnm t" type="submit"><span class="material-icons-outlined">{{ $h->aktif?'pause':'play_arrow' }}</span>{{ $h->aktif?'Durdur':'Aç' }}</button></form>
          <form method="POST" action="/happy-hour/durum" style="margin:0" onsubmit="return confirm('Sil?')">@csrf<input type="hidden" name="id" value="{{ $h->id }}"><input type="hidden" name="sil" value="1"><button class="btnm s" type="submit"><span class="material-icons-outlined">delete</span></button></form>
        </div>
      </div>
    @empty
      <div class="bos">Henüz Happy Hour kuralı yok. Aşağıdan ekle.</div>
    @endforelse
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Yeni Kural</div>
    <form method="POST" action="/happy-hour/ekle">
      @csrf
      <div class="grid">
        <div><label>Kural adı</label><input name="ad" placeholder="örn. Akşam Bira Saati"></div>
        <div><label>Ürün (belirli)</label><select name="urun_id"><option value="">— tüm ürünler / kategori —</option>@foreach($urunler as $u)<option value="{{ $u->id }}">{{ $u->ad }}</option>@endforeach</select></div>
        <div><label>Veya Kategori (ürün boşsa)</label><select name="kategori_id"><option value="">— yok —</option>@foreach($kategoriler as $k)<option value="{{ $k->id }}">{{ $k->ad }}</option>@endforeach</select></div>
        <div></div>
        <div><label>Başlangıç saati</label><input type="time" name="baslangic_saat"></div>
        <div><label>Bitiş saati</label><input type="time" name="bitis_saat"></div>
        <div><label>İndirim %</label><input name="indirim_yuzde" inputmode="decimal" placeholder="örn. 20"></div>
        <div><label>veya Sabit Happy Fiyat (₺)</label><input name="happy_fiyat" inputmode="decimal" placeholder="örn. 50"></div>
        <div><label>Başlangıç tarihi (ops.)</label><input type="date" name="baslangic_tarih"></div>
        <div><label>Bitiş tarihi (ops.)</label><input type="date" name="bitis_tarih"></div>
      </div>
      <label style="margin-top:10px">Geçerli günler</label>
      <div class="gunler">
        @foreach($gunAd as $g=>$ad)<label class="gun"><input type="checkbox" name="gunler[]" value="{{ $g }}" checked> {{ $ad }}</label>@endforeach
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">add</span> Kural Ekle</button>
      <div style="font-size:11px;color:var(--sub2);margin-top:8px">💡 İndirim% VEYA sabit fiyat gir (biri yeterli). Saat boşsa tüm gün, gün seçili değilse hiç uygulanmaz.</div>
    </form>
  </div>

</div></div>
@endsection

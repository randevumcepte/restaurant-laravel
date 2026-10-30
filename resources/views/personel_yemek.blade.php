@extends('layout.app')
@section('rk_ikon', 'lunch_dining')
@section('baslik', 'Personel Yemeği')
@section('title', 'Personel Yemeği')

@push('head')
<style>
#py{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #py{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#py{background:var(--bg);min-height:calc(100vh - 60px)}#py *{box-sizing:border-box}
#py .pano{max-width:900px;margin:0 auto;padding:18px 18px 40px}
#py .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#py .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#py .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#py .kb{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px;flex-wrap:wrap}
#py .kb .material-icons-outlined{color:var(--mor1);font-size:19px;vertical-align:-4px}
#py .ozet{background:linear-gradient(135deg,var(--amber),#d97706);color:#fff;border-radius:14px;padding:12px 16px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
#py .ozet .d{font-size:22px;font-weight:900}
#py label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#py input,#py select{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#py .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
@media(max-width:700px){#py .grid{grid-template-columns:1fr 1fr}}
#py .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#py .btn .material-icons-outlined{font-size:18px}
#py table{width:100%;border-collapse:collapse;font-size:12.5px}
#py th{text-align:left;color:var(--sub);font-weight:700;font-size:10.5px;padding:8px;border-bottom:2px solid var(--line);text-transform:uppercase}
#py td{padding:9px 8px;border-bottom:1px solid var(--line);color:var(--ink)}
#py .tut{font-weight:800;text-align:right}
#py .kisi{display:flex;flex-wrap:wrap;gap:8px}
#py .kc{background:var(--card2);border-radius:10px;padding:8px 12px;font-size:12px}
#py .kc b{color:var(--ink)}#py .kc .t{color:var(--amber);font-weight:800}
#py .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
#py .sil{border:0;background:none;color:var(--sub2);cursor:pointer}#py .sil:hover{color:var(--kirmizi)}
#py input[type=month]{width:auto}
</style>
@endpush

@section('content')
@php $tl=fn($v)=>number_format((float)$v,2,',','.'); @endphp
<div id="py"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Personel Yemeği:</b> Çalışanların işte yediği yemeklerin maliyetini takip et. Aylık toplam + kişi bazlı özet görürsün (gider/maaş değerlendirmesi için). Basit kayıt — tabldot/kart sisteminin dilimi.
  </div>

  <div class="ozet"><span>{{ \Carbon\Carbon::parse($ay.'-01')->translatedFormat('F Y') }} personel yemeği</span><span class="d">{{ $tl($ayToplam) }} ₺</span></div>

  <div class="kart">
    <div class="kb"><span><span class="material-icons-outlined">add_circle</span> Kayıt Ekle</span></div>
    <form method="POST" action="/personel-yemek/ekle">
      @csrf
      <div class="grid">
        <div><label>Personel</label><select name="personel_id"><option value="">— genel —</option>@foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }}</option>@endforeach</select></div>
        <div><label>Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}"></div>
        <div><label>Açıklama</label><input name="aciklama" placeholder="öğle yemeği"></div>
        <div><label>Tutar (₺)</label><input name="tutar" inputmode="decimal" required></div>
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">save</span> Kaydet</button>
    </form>
  </div>

  @if($kisiOzet->count())
  <div class="kart">
    <div class="kb"><span><span class="material-icons-outlined">groups</span> Kişi Bazlı ({{ \Carbon\Carbon::parse($ay.'-01')->translatedFormat('F') }})</span></div>
    <div class="kisi">
      @foreach($kisiOzet as $pid=>$o)
        <div class="kc"><b>{{ $pid ? ($persAd[$pid] ?? 'Personel') : 'Genel' }}</b> · {{ $o['adet'] }} öğün · <span class="t">{{ $tl($o['tutar']) }}₺</span></div>
      @endforeach
    </div>
  </div>
  @endif

  <div class="kart">
    <div class="kb">
      <span><span class="material-icons-outlined">list</span> Kayıtlar</span>
      <form method="GET" style="margin:0"><input type="month" name="ay" value="{{ $ay }}" onchange="this.form.submit()"></form>
    </div>
    @if($liste->count())
      <table>
        <thead><tr><th>Tarih</th><th>Personel</th><th>Açıklama</th><th style="text-align:right">Tutar</th><th></th></tr></thead>
        <tbody>
          @foreach($liste as $y)
          <tr>
            <td>{{ \Carbon\Carbon::parse($y->tarih)->format('d.m.y') }}</td>
            <td>{{ $y->personel_id ? ($persAd[$y->personel_id] ?? 'Personel') : 'Genel' }}</td>
            <td style="color:var(--sub)">{{ $y->aciklama }}</td>
            <td class="tut">{{ $tl($y->tutar) }} ₺</td>
            <td><form method="POST" action="/personel-yemek/sil" onsubmit="return confirm('Sil?')" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $y->id }}"><button class="sil" type="submit"><span class="material-icons-outlined" style="font-size:17px">delete</span></button></form></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <div class="bos">Bu ay kayıt yok.</div>
    @endif
  </div>

</div></div>
@endsection

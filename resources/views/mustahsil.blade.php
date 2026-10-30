@extends('layout.app')
@section('rk_ikon', 'receipt_long')
@section('baslik', 'Müstahsil Makbuzu')
@section('title', 'Müstahsil Makbuzu')

@push('head')
<style>
#mk{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #mk{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#mk{background:var(--bg);min-height:calc(100vh - 60px)}#mk *{box-sizing:border-box}
#mk .pano{max-width:980px;margin:0 auto;padding:18px 18px 40px}
#mk .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#mk .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#mk .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#mk .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#mk .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#mk label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#mk input{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#mk .grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
@media(max-width:760px){#mk .grid{grid-template-columns:1fr 1fr}}
#mk .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#mk .btn .material-icons-outlined{font-size:18px}
#mk table{width:100%;border-collapse:collapse;font-size:12px}
#mk th{text-align:right;color:var(--sub);font-weight:700;font-size:10px;padding:7px 6px;border-bottom:2px solid var(--line);text-transform:uppercase}
#mk th:first-child,#mk th:nth-child(2){text-align:left}
#mk td{padding:8px 6px;border-bottom:1px solid var(--line);text-align:right;color:var(--ink)}
#mk td:first-child,#mk td:nth-child(2){text-align:left}
#mk .net{font-weight:800;color:var(--yesil)}
#mk .ozet{display:flex;gap:20px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:2px solid var(--line);font-size:13px}
#mk .ozet b{color:var(--ink)}
#mk .bos{text-align:center;color:var(--sub2);padding:30px;font-size:12.5px}
#mk .sil{border:0;background:none;color:var(--sub2);cursor:pointer}#mk .sil:hover{color:var(--kirmizi)}
</style>
@endpush

@section('content')
@php $tl=fn($v)=>number_format((float)$v,2,',','.'); @endphp
<div id="mk"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Müstahsil Makbuzu:</b> Vergi mükellefi olmayan üreticiden (çiftçi/halden) alışta kesilen yasal belge. Brüt tutardan <b>gelir vergisi stopajı</b> (genelde %2) ve varsa <b>Bağ-Kur</b> kesilir → <b>net ödenen</b> hesaplanır. (Güncel stopaj oranını mali müşavirinle teyit et.)
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Yeni Makbuz</div>
    <form method="POST" action="/mustahsil/ekle">
      @csrf
      <div class="grid">
        <div><label>Tarih</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}"></div>
        <div><label>Makbuz No (ops.)</label><input name="makbuz_no" placeholder="otomatik"></div>
        <div style="grid-column:span 2"><label>Müstahsil (üretici) adı *</label><input name="mustahsil_ad" required></div>
        <div><label>TC Kimlik (ops.)</label><input name="mustahsil_tc"></div>
        <div style="grid-column:span 3"><label>Adres (ops.)</label><input name="mustahsil_adres"></div>
        <div style="grid-column:span 2"><label>Ürün *</label><input name="urun_ad" placeholder="örn. domates" required></div>
        <div><label>Miktar</label><input name="miktar" inputmode="decimal" placeholder="örn. 50"></div>
        <div><label>Birim</label><input name="birim" placeholder="kg"></div>
        <div><label>Birim Fiyat (₺)</label><input name="birim_fiyat" inputmode="decimal" placeholder="örn. 18"></div>
        <div><label>Stopaj %</label><input name="stopaj_oran" inputmode="decimal" value="2"></div>
        <div><label>Bağ-Kur %</label><input name="bagkur_oran" inputmode="decimal" value="0"></div>
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">save</span> Makbuzu Kaydet</button>
    </form>
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">list</span> Makbuzlar</div>
    @if($liste->count())
      <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Tarih</th><th>Müstahsil / Ürün</th><th>Miktar</th><th>B.Fiyat</th><th>Brüt</th><th>Stopaj</th><th>Bağkur</th><th>Net</th><th></th></tr></thead>
        <tbody>
          @foreach($liste as $m)
          <tr>
            <td>{{ \Carbon\Carbon::parse($m->tarih)->format('d.m.y') }}<br><span style="font-size:9.5px;color:var(--sub2)">{{ $m->makbuz_no }}</span></td>
            <td><b>{{ $m->mustahsil_ad }}</b><br><span style="font-size:11px;color:var(--sub)">{{ $m->urun_ad }}</span></td>
            <td>{{ rtrim(rtrim(number_format($m->miktar,3,',','.'),'0'),',') }} {{ $m->birim }}</td>
            <td>{{ $tl($m->birim_fiyat) }}</td>
            <td>{{ $tl($m->brut) }}</td>
            <td>{{ $tl($m->stopaj) }}<br><span style="font-size:9.5px;color:var(--sub2)">%{{ rtrim(rtrim(number_format($m->stopaj_oran,2,',','.'),'0'),',') }}</span></td>
            <td>{{ $m->bagkur>0?$tl($m->bagkur):'–' }}</td>
            <td class="net">{{ $tl($m->net) }}</td>
            <td><form method="POST" action="/mustahsil/sil" onsubmit="return confirm('Sil?')" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $m->id }}"><button class="sil" type="submit"><span class="material-icons-outlined" style="font-size:17px">delete</span></button></form></td>
          </tr>
          @endforeach
        </tbody>
      </table>
      </div>
      <div class="ozet"><span>Brüt: <b>{{ $tl($toplam['brut']) }} ₺</b></span><span>Stopaj: <b>{{ $tl($toplam['stopaj']) }} ₺</b></span><span>Net Ödenen: <b style="color:var(--yesil)">{{ $tl($toplam['net']) }} ₺</b></span></div>
    @else
      <div class="bos">Henüz makbuz yok. Yukarıdan ekle.</div>
    @endif
  </div>

</div></div>
@endsection

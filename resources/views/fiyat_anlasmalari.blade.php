@extends('layout.app')
@section('rk_ikon', 'handshake')
@section('baslik', 'Fiyat Anlaşmaları')
@section('title', 'Fiyat Anlaşmaları')

@push('head')
<style>
#fa{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #fa{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#fa{background:var(--bg);min-height:calc(100vh - 60px)}#fa *{box-sizing:border-box}
#fa .pano{max-width:940px;margin:0 auto;padding:18px 18px 40px}
#fa .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#fa .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#fa .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#fa .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#fa .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#fa label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#fa input,#fa select{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#fa .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
@media(max-width:700px){#fa .grid{grid-template-columns:1fr 1fr}}
#fa .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#fa .btn .material-icons-outlined{font-size:18px}
#fa table{width:100%;border-collapse:collapse;font-size:12.5px}
#fa th{text-align:left;color:var(--sub);font-weight:700;font-size:10.5px;padding:8px;border-bottom:2px solid var(--line);text-transform:uppercase}
#fa td{padding:9px 8px;border-bottom:1px solid var(--line);color:var(--ink)}
#fa .fi{font-weight:800;color:var(--yesil)}
#fa .roz{font-size:9.5px;font-weight:800;padding:1px 8px;border-radius:20px}
#fa .roz.ak{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:#0b805e}
#fa .roz.pa{background:var(--card2);color:var(--sub2)}
#fa .btnm{border:0;border-radius:8px;padding:5px 9px;font-size:11px;font-weight:700;cursor:pointer}
#fa .btnm.t{background:var(--card2);color:var(--sub)}#fa .btnm.s{background:rgba(244,63,94,.12);color:var(--kirmizi)}
#fa .bos{text-align:center;color:var(--sub2);padding:30px;font-size:12.5px}
</style>
@endpush

@section('content')
@php $tl=fn($v)=>number_format((float)$v,2,',','.'); @endphp
<div id="fa"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Fiyat Anlaşmaları:</b> Tedarikçiyle bir malzeme için <b>dönemsel sabit fiyat</b> belirle. Fiyat dalgalanmasından korunursun; satın almada bu fiyat otomatik kullanılabilir ve (çok-kademeli onayda) "anlaşmalı atla" ile onay kısaltılır.
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Yeni Anlaşma</div>
    <form method="POST" action="/fiyat-anlasmalari/ekle">
      @csrf
      <div class="grid">
        <div><label>Tedarikçi *</label><select name="tedarikci_id" required><option value="">— seç —</option>@foreach($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
        <div><label>Malzeme *</label><select name="malzeme_id" required><option value="">— seç —</option>@foreach($malzemeler as $m)<option value="{{ $m->id }}">{{ $m->ad }}</option>@endforeach</select></div>
        <div><label>Birim Fiyat (₺) *</label><input name="birim_fiyat" inputmode="decimal" required></div>
        <div><label>Birim</label><input name="birim" placeholder="kg/lt/adet"></div>
        <div><label>Başlangıç (ops.)</label><input type="date" name="baslangic"></div>
        <div><label>Bitiş (ops.)</label><input type="date" name="bitis"></div>
      </div>
      <div style="margin-top:8px"><label>Not (ops.)</label><input name="not" placeholder="sözleşme no, şart vb."></div>
      <button class="btn" type="submit"><span class="material-icons-outlined">save</span> Anlaşma Ekle</button>
    </form>
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">list</span> Anlaşmalar</div>
    @if($liste->count())
      <div style="overflow-x:auto"><table>
        <thead><tr><th>Tedarikçi</th><th>Malzeme</th><th>Fiyat</th><th>Dönem</th><th>Durum</th><th></th></tr></thead>
        <tbody>
          @foreach($liste as $a)
          <tr>
            <td><b>{{ $tedAd[$a->tedarikci_id] ?? '—' }}</b></td>
            <td>{{ $malAd[$a->malzeme_id] ?? '—' }}</td>
            <td class="fi">{{ $tl($a->birim_fiyat) }} ₺{{ $a->birim?(' / '.$a->birim):'' }}</td>
            <td style="font-size:11px;color:var(--sub)">{{ $a->baslangic ?: '…' }} → {{ $a->bitis ?: '…' }}</td>
            <td><span class="roz {{ $a->aktif?'ak':'pa' }}">{{ $a->aktif?'aktif':'pasif' }}</span></td>
            <td style="display:flex;gap:5px">
              <form method="POST" action="/fiyat-anlasmalari/durum" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $a->id }}"><button class="btnm t" type="submit">{{ $a->aktif?'Durdur':'Aç' }}</button></form>
              <form method="POST" action="/fiyat-anlasmalari/durum" style="margin:0" onsubmit="return confirm('Sil?')">@csrf<input type="hidden" name="id" value="{{ $a->id }}"><input type="hidden" name="sil" value="1"><button class="btnm s" type="submit">Sil</button></form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table></div>
    @else
      <div class="bos">Henüz fiyat anlaşması yok. Yukarıdan ekle.</div>
    @endif
  </div>

</div></div>
@endsection

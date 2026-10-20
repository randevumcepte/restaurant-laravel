@extends('layout.app')
@section('title', 'Fatura Eşleştir')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Fatura Eşleştir (3-way)')
@section('rk_alt', 'Sipariş ↔ İrsaliye ↔ Fatura · fazla ödeme / eksik yakala')

@section('content')
@php
  $mk = $mk ?? null;
  $kalemler = $kalemler ?? collect();
  $tedarikci = $tedarikci ?? null;
  $bekleyen = $bekleyen ?? collect();
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . '₺';
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
@endphp

@if (session('fe_ok'))<div class="rk-flash ok">{{ session('fe_ok') }}</div>@endif
@if (session('fe_hata'))<div class="rk-flash err">{{ session('fe_hata') }}</div>@endif

@if (!$mk)
  {{-- Faturası bekleyen mal kabuller --}}
  <div class="rk-bolum"><span class="cubuk" style="background:var(--amber)"></span><span class="b">Faturası Bekleyen Mal Kabuller</span>
    <span class="rk-rozet" style="color:var(--amber);background:color-mix(in srgb,var(--amber) 13%,transparent);border-color:color-mix(in srgb,var(--amber) 40%,transparent)">{{ $bekleyen->count() }}</span></div>
  @if ($bekleyen->isEmpty())
    <div class="rk-card" style="text-align:center;padding:36px"><span class="material-icons-outlined" style="font-size:48px;color:var(--line)">fact_check</span><div class="rk-sub" style="margin-top:10px">Bekleyen yok. Mal kabul yaptıkça buraya düşer.</div></div>
  @else
    <div class="rk-tablo">
      <table>
        <thead><tr><th>Tarih</th><th>Tedarikçi</th><th>İrsaliye</th><th class="sag"></th></tr></thead>
        <tbody>
          @foreach ($bekleyen as $m)
            <tr>
              <td class="rk-sub2">{{ $m->tarih ? \Carbon\Carbon::parse($m->tarih)->format('d.m.Y') : '—' }}</td>
              <td style="font-weight:600">{{ $m->tedarikci }}</td>
              <td class="rk-sub">{{ $m->irsaliye_no ?: '—' }}</td>
              <td class="sag"><a href="/fatura-eslestir?mal_kabul_id={{ $m->id }}" class="rk-btn" style="background:var(--amber);padding:7px 12px"><span class="material-icons-outlined" style="color:#fff;font-size:15px">fact_check</span> Fatura Eşleştir</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
@else
  {{-- Eşleştirme formu --}}
  <div style="display:flex;gap:8px;margin-bottom:12px"><a href="/fatura-eslestir" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Geri</a></div>
  <div class="rk-card">
    <div class="rk-bolum" style="margin-bottom:12px"><span class="cubuk" style="background:var(--amber)"></span><span class="b">{{ $tedarikci->ad ?? 'Tedarikçi' }} · İrsaliye {{ $mk->irsaliye_no ?: '—' }}</span></div>
    <form method="POST" action="/fatura-eslestir/kaydet" id="feForm">
      @csrf
      <input type="hidden" name="mal_kabul_id" value="{{ $mk->id }}">
      <input type="hidden" name="kalemler" id="feKalemler">
      <div class="rk-grid" style="grid-template-columns:1fr 1fr 1fr 1fr;gap:10px">
        <div><label class="rk-lbl" style="margin-top:0">Fatura No</label><input type="text" name="fatura_no" placeholder="Fatura no" class="rk-inp"></div>
        <div><label class="rk-lbl" style="margin-top:0">Fatura Tarihi</label><input type="date" name="tarih" value="{{ date('Y-m-d') }}" class="rk-inp"></div>
        <div><label class="rk-lbl" style="margin-top:0">Vade Tarihi</label><input type="date" name="vade_tarihi" class="rk-inp"></div>
        <div><label class="rk-lbl" style="margin-top:0">Genel İskonto (₺)</label><input type="number" name="iskonto" id="feIsk" step="0.01" min="0" value="0" class="rk-inp" oninput="feHesap()"></div>
      </div>

      <div class="rk-bolum" style="margin-top:14px"><span class="cubuk" style="background:var(--amber)"></span><span class="b">Kalemler · fatura ↔ irsaliye ↔ sipariş</span></div>
      <div style="overflow-x:auto"><div style="min-width:760px">
        <div style="display:grid;grid-template-columns:2.2fr 1fr 1.1fr 1.2fr 1fr 1.2fr;gap:6px;font-size:10.5px;font-weight:700;color:var(--sub2);text-transform:uppercase;padding:0 2px 4px">
          <span>Malzeme</span><span>İrsaliye (kabul)</span><span>Fatura Miktar</span><span>Fatura Fiyat</span><span>KDV%</span><span>Sipariş Fiyatı</span>
        </div>
        @foreach ($kalemler as $k)
          <div class="fe-satir" style="display:grid;grid-template-columns:2.2fr 1fr 1.1fr 1.2fr 1fr 1.2fr;gap:6px;align-items:center;margin-bottom:6px"
               data-malzeme="{{ $k->malzeme_id }}" data-birim="{{ $k->alis_birim_id }}" data-kabul="{{ (float) $k->kabul_miktar }}" data-po="{{ (float) $k->po_fiyat }}">
            <div style="font-weight:600;font-size:12.5px">{{ $k->malzeme }} <span class="rk-sub2" style="font-size:10px">/ {{ $k->birim }}</span></div>
            <div class="rk-sub2" style="font-size:12.5px">{{ $num($k->kabul_miktar) }}</div>
            <input type="number" class="fe-mik rk-inp" step="0.001" min="0" value="{{ $num($k->kabul_miktar) }}" style="padding:6px" oninput="feHesap()">
            <input type="number" class="fe-fiyat rk-inp" step="0.0001" min="0" value="{{ (float) $k->birim_fiyat }}" style="padding:6px" oninput="feHesap()">
            <input type="number" class="fe-kdv rk-inp" step="0.01" min="0" value="10" style="padding:6px" oninput="feHesap()">
            <div class="fe-po rk-sub2" style="font-size:12px">{{ (float) $k->po_fiyat > 0 ? $tl($k->po_fiyat) : '—' }}</div>
          </div>
        @endforeach
      </div></div>

      <div style="display:flex;justify-content:flex-end;gap:24px;margin-top:10px;padding-top:10px;border-top:1px solid var(--line)">
        <div class="rk-sub2" style="font-size:12.5px">Ara: <b class="rk-ink" id="feAra">0,00₺</b></div>
        <div class="rk-sub2" style="font-size:12.5px">KDV: <b class="rk-ink" id="feKdv">0,00₺</b></div>
        <div style="font-size:14px;font-weight:800;color:var(--amber)">Genel: <span id="feGenel">0,00₺</span></div>
      </div>
      <div id="feUyari" style="display:none;background:var(--kirmizi);color:#fff;border-radius:10px;padding:9px 13px;margin-top:10px;font-size:12.5px;font-weight:600"></div>

      <button type="submit" class="rk-btn lg" style="background:var(--amber);margin-top:14px"><span class="material-icons-outlined" style="color:#fff;font-size:20px">fact_check</span> Faturayı Eşleştir & Kaydet</button>
    </form>
  </div>
@endif

@if ($mk)
<script>
function feHesap(){
  let ara=0,kdv=0,uyari=[];
  document.querySelectorAll('.fe-satir').forEach(r=>{
    const mik=parseFloat(r.querySelector('.fe-mik').value)||0, f=parseFloat(r.querySelector('.fe-fiyat').value)||0, ky=parseFloat(r.querySelector('.fe-kdv').value)||0;
    const kabul=parseFloat(r.dataset.kabul)||0, po=parseFloat(r.dataset.po)||0;
    const net=mik*f; ara+=net; kdv+=net*ky/100;
    const poEl=r.querySelector('.fe-po');
    let fark=false;
    if(kabul>0 && Math.abs(kabul-mik)>0.001){ fark=true; }
    if(po>0 && f>po+0.0001){ fark=true; poEl.style.color='var(--kirmizi)'; poEl.style.fontWeight='800'; } else { poEl.style.color=''; poEl.style.fontWeight=''; }
    r.style.background = fark ? 'color-mix(in srgb,var(--kirmizi) 8%,transparent)' : '';
  });
  const isk=parseFloat(document.getElementById('feIsk').value)||0;
  const araNet=Math.max(0,ara-isk);
  const fmt=v=>v.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
  document.getElementById('feAra').textContent=fmt(araNet);
  document.getElementById('feKdv').textContent=fmt(kdv);
  document.getElementById('feGenel').textContent=fmt(araNet+kdv);
}
document.getElementById('feForm').addEventListener('submit',function(e){
  const kal=[];
  document.querySelectorAll('.fe-satir').forEach(r=>{
    kal.push({malzeme_id:parseInt(r.dataset.malzeme)||0,alis_birim_id:parseInt(r.dataset.birim)||0,kabul_miktar:parseFloat(r.dataset.kabul)||0,po_fiyat:parseFloat(r.dataset.po)||0,miktar:parseFloat(r.querySelector('.fe-mik').value)||0,birim_fiyat:parseFloat(r.querySelector('.fe-fiyat').value)||0,kdv_yuzde:parseFloat(r.querySelector('.fe-kdv').value)||0});
  });
  if(!kal.length){ e.preventDefault(); return; }
  document.getElementById('feKalemler').value=JSON.stringify(kal);
});
feHesap();
</script>
@endif
@endsection

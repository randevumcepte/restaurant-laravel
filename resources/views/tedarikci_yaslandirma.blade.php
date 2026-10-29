@extends('layout.app')
@section('rk_ikon', 'account_balance_wallet')
@section('baslik', 'Satıcı Yaşlandırma')
@section('title', 'Satıcı Yaşlandırma')

@push('head')
<style>
#ty{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #ty{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#ty{background:var(--bg);min-height:calc(100vh - 60px)}#ty *{box-sizing:border-box}
#ty .pano{max-width:1050px;margin:0 auto;padding:18px 18px 40px}
#ty .ust{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:14px}
@media(max-width:760px){#ty .ust{grid-template-columns:1fr 1fr}}
#ty .kpi{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:15px;border-left:4px solid var(--c)}
#ty .kpi .lbl{font-size:11.5px;color:var(--sub);font-weight:700}
#ty .kpi .deg{font-size:22px;font-weight:900;color:var(--c);margin-top:4px;line-height:1}
#ty .ai{background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 9%,var(--card)),color-mix(in srgb,var(--mavi) 7%,var(--card)));border:1.5px solid color-mix(in srgb,var(--mor1) 22%,transparent);border-radius:16px;padding:15px;margin-bottom:14px}
#ty .ai .bas{display:flex;align-items:center;gap:9px;font-size:13.5px;font-weight:800;color:var(--ink);margin-bottom:8px}
#ty .ai .orb{width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--mor1),var(--mavi));display:flex;align-items:center;justify-content:center}
#ty .ai .orb .material-icons-outlined{font-size:16px;color:#fff}
#ty .ai .metin{font-size:13px;color:var(--ink);line-height:1.6;white-space:pre-wrap}
#ty .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px}
#ty .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#ty .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#ty table{width:100%;border-collapse:collapse;font-size:12.5px}
#ty th{text-align:right;color:var(--sub);font-weight:700;font-size:10.5px;padding:8px;border-bottom:2px solid var(--line);text-transform:uppercase}
#ty th:first-child{text-align:left}
#ty td{padding:9px 8px;border-bottom:1px solid var(--line);text-align:right;color:var(--ink)}
#ty td:first-child{text-align:left;font-weight:700}
#ty .gec{color:var(--kirmizi);font-weight:800}#ty .haf{color:var(--amber);font-weight:700}
#ty .top{font-weight:900}
#ty .btnm{border:0;border-radius:8px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;color:#fff;background:var(--yesil);display:inline-flex;align-items:center;gap:4px}
#ty .btnm .material-icons-outlined{font-size:14px}
#ty details{margin-top:4px}
#ty summary{cursor:pointer;font-size:11px;color:var(--mor1);font-weight:700;list-style:none}
#ty .fat{display:flex;align-items:center;gap:10px;padding:6px 10px;border-radius:8px;background:var(--card2);margin:4px 0;font-size:11.5px}
#ty .fat .no{flex:1;color:var(--sub)}
#ty .fat .vade{font-weight:700}
#ty .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#ty .bos{text-align:center;color:var(--sub2);padding:40px;font-size:13px}
</style>
@endpush

@section('content')
@php $tl=fn($v)=>number_format((float)$v,0,',','.').' ₺'; @endphp
<div id="ty"><div class="pano">

  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="ust">
    <div class="kpi" style="--c:var(--mor1)"><div class="lbl">Toplam Borç</div><div class="deg">{{ $tl($y['toplam']) }}</div></div>
    <div class="kpi" style="--c:var(--kirmizi)"><div class="lbl">🔴 Gecikmiş</div><div class="deg">{{ $tl($y['gecikmis']) }}</div></div>
    <div class="kpi" style="--c:var(--amber)"><div class="lbl">🟠 Bu Hafta (0-7g)</div><div class="deg">{{ $tl($y['hafta']) }}</div></div>
    <div class="kpi" style="--c:var(--mavi)"><div class="lbl">8-30 Gün</div><div class="deg">{{ $tl($y['ay']) }}</div></div>
  </div>

  @if($tavsiye)
  <div class="ai">
    <div class="bas"><span class="orb"><span class="material-icons-outlined">auto_awesome</span></span> AI Nakit Danışmanı</div>
    <div class="metin">{{ $tavsiye }}</div>
  </div>
  @endif

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">local_shipping</span> Tedarikçi Borçları (vade yaşlandırma)</div>
    @if(empty($y['tedarikciler']))
      <div class="bos"><span class="material-icons-outlined" style="font-size:40px;display:block;opacity:.5;margin-bottom:8px">check_circle</span>Ödenmemiş alış faturası yok — borç görünmüyor. 👍<br><span style="font-size:11px">(Fatura "ödendi" işaretlendikçe buradan düşer)</span></div>
    @else
      <table>
        <thead><tr><th>Tedarikçi</th><th>Gecikmiş</th><th>Bu Hafta</th><th>8-30g</th><th>31+g</th><th>Toplam</th><th></th></tr></thead>
        <tbody>
          @foreach($y['tedarikciler'] as $s)
          <tr>
            <td>{{ $s['ad'] }}
              <details>
                <summary>{{ count($s['faturalar']) }} fatura ▾</summary>
                @foreach($s['faturalar'] as $f)
                  <div class="fat">
                    <span class="no">{{ $f['no'] ?: ('#'.$f['id']) }} · {{ $f['tarih'] }}</span>
                    <span class="vade" style="color:{{ $f['gun']<0?'var(--kirmizi)':($f['gun']<=7?'var(--amber)':'var(--sub)') }}">{{ $f['gun']<0 ? abs($f['gun']).'g gecikmiş' : 'vade '.$f['gun'].'g' }}</span>
                    <span style="font-weight:800">{{ $tl($f['tutar']) }}</span>
                    @if($patronMu)
                    <form method="POST" action="/tedarikci-yaslandirma/odendi" style="margin:0">@csrf<input type="hidden" name="fatura_id" value="{{ $f['id'] }}"><button class="btnm" type="submit"><span class="material-icons-outlined">check</span></button></form>
                    @endif
                  </div>
                @endforeach
              </details>
            </td>
            <td class="{{ $s['gecikmis']>0?'gec':'' }}">{{ $s['gecikmis']>0?$tl($s['gecikmis']):'–' }}</td>
            <td class="{{ $s['hafta']>0?'haf':'' }}">{{ $s['hafta']>0?$tl($s['hafta']):'–' }}</td>
            <td>{{ $s['ay']>0?$tl($s['ay']):'–' }}</td>
            <td>{{ $s['ileri']>0?$tl($s['ileri']):'–' }}</td>
            <td class="top">{{ $tl($s['toplam']) }}</td>
            <td>@if($patronMu)<form method="POST" action="/tedarikci-yaslandirma/odendi" onsubmit="return confirm('{{ $s['ad'] }} tüm borcunu ödendi işaretle?')" style="margin:0">@csrf<input type="hidden" name="tedarikci_id" value="{{ $s['id'] }}"><button class="btnm" type="submit"><span class="material-icons-outlined">done_all</span> Tümü</button></form>@endif</td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <div style="font-size:11px;color:var(--sub);margin-top:10px">💡 Borç = ödenmemiş alış faturaları (vade_tarihi / tedarikçi vade gününe göre). Ödeme yapınca "ödendi" işaretle → aging'den düşer. Gecikmiş = vadesi geçmiş.</div>
    @endif
  </div>

</div></div>
@endsection

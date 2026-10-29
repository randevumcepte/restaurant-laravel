@extends('layout.app')
@section('rk_ikon', 'reviews')
@section('baslik', 'İtibar / Memnuniyet')
@section('title', 'İtibar / Memnuniyet')

@push('head')
<style>
#it{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #it{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#it{background:var(--bg);min-height:calc(100vh - 60px)}#it *{box-sizing:border-box}
#it .pano{max-width:1000px;margin:0 auto;padding:18px 18px 40px}
#it .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#it .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#it .ust{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:14px}
@media(max-width:700px){#it .ust{grid-template-columns:1fr}}
#it .kpi{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;display:flex;align-items:center;gap:14px}
#it .kpi .ik{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#it .kpi .ik .material-icons-outlined{font-size:24px;color:#fff}
#it .kpi .deg{font-size:26px;font-weight:900;color:var(--ink);line-height:1}
#it .kpi .lbl{font-size:12px;color:var(--sub);font-weight:600}
#it .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#it .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#it .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#it .dag{display:flex;flex-direction:column;gap:6px}
#it .dag .sat{display:flex;align-items:center;gap:10px}
#it .dag .y{width:46px;font-size:12px;color:var(--sub);font-weight:700}
#it .dag .bar{flex:1;height:9px;border-radius:6px;background:var(--card2);overflow:hidden}
#it .dag .bar div{height:100%;border-radius:6px}
#it .dag .n{width:34px;text-align:right;font-size:12px;font-weight:700;color:var(--ink)}
#it .form-sat{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
#it .fld{display:flex;flex-direction:column;gap:4px;flex:1;min-width:200px}
#it .fld label{font-size:11px;font-weight:700;color:var(--sub)}
#it .fld input{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px}
#it .btn{border:0;border-radius:10px;padding:10px 16px;font-weight:700;font-size:13px;color:#fff;cursor:pointer;background:var(--mor1);display:inline-flex;align-items:center;gap:6px}
#it .btn.yesil{background:var(--yesil)}#it .btn.kucuk{padding:6px 12px;font-size:12px}
#it .yorum{display:flex;gap:12px;padding:12px 0;border-top:1px solid var(--line)}
#it .yorum:first-child{border-top:none}
#it .yorum .pz{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
#it .yorum .ic{flex:1;min-width:0}
#it .yorum .bas{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
#it .yorum .masa{font-weight:800;font-size:13.5px;color:var(--ink)}
#it .yorum .roz{font-size:10px;font-weight:800;padding:2px 8px;border-radius:20px;color:#fff}
#it .yorum .sebep{font-size:12px;color:var(--sub);margin-top:2px}
#it .yorum .notu{font-size:12.5px;color:var(--ink);margin-top:3px;font-style:italic}
#it .yorum .zaman{font-size:10.5px;color:var(--sub2);margin-top:3px}
#it .yorum .sag{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
#it .bos{text-align:center;color:var(--sub2);padding:40px;font-size:13px}
#it details{margin-bottom:14px}
#it summary{cursor:pointer;font-size:12.5px;color:var(--mor1);font-weight:700;padding:6px 0}
#it .masa-link{display:flex;justify-content:space-between;align-items:center;padding:7px 10px;border:1px solid var(--line);border-radius:9px;margin-bottom:5px;font-size:12px}
#it .masa-link a{color:var(--mavi);text-decoration:none;font-size:11px;word-break:break-all}
</style>
@endpush

@section('content')
@php
  $puanRenk=fn($p)=>$p>=4?'var(--yesil)':($p==3?'var(--amber)':'var(--kirmizi)');
  $puanEmoji=[1=>'😣',2=>'😕',3=>'😐',4=>'🙂',5=>'😍'];
  $maxDag=max(1,max($dagilim));
@endphp
<div id="it"><div class="pano">

  @if (session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>Memnuniyet Nabzı:</b> Müşteri masada QR okutup deneyimini puanlar. <b>Mutlu (4-5)</b> → Google'a davet edilir. <b>Mutsuz (1-3)</b> → Google'a gitmez, <b>anında size bildirim düşer</b> → masaya gidip <b>oracıkta çözersiniz</b> (kötü yorum kaynağında önlenir). Aşağıdan Google yorum linkinizi ve uyarı WhatsApp numaranızı girin.
  </div>

  {{-- KPI --}}
  <div class="ust">
    <div class="kpi"><div class="ik" style="background:linear-gradient(135deg,#FBBF24,#F59E0B)"><span class="material-icons-outlined">star</span></div>
      <div><div class="deg">{{ $ort ?: '—' }}</div><div class="lbl">Ortalama Puan</div></div></div>
    <div class="kpi"><div class="ik" style="background:linear-gradient(135deg,#F43F5E,#be1e3c)"><span class="material-icons-outlined">priority_high</span></div>
      <div><div class="deg" style="color:{{ $bekleyen>0?'var(--kirmizi)':'var(--ink)' }}">{{ $bekleyen }}</div><div class="lbl">Bekleyen (çözülmedi)</div></div></div>
    <div class="kpi"><div class="ik" style="background:linear-gradient(135deg,#7C3AED,#4F46E5)"><span class="material-icons-outlined">reviews</span></div>
      <div><div class="deg">{{ $liste->count() }}</div><div class="lbl">Toplam Değerlendirme</div></div></div>
  </div>

  {{-- Dağılım --}}
  @if($liste->count())
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">bar_chart</span> Puan Dağılımı</div>
    <div class="dag">
      @foreach($dagilim as $p=>$n)
        <div class="sat"><span class="y">{{ $puanEmoji[$p] }} {{ $p }}</span>
          <div class="bar"><div style="width:{{ round($n/$maxDag*100) }}%;background:{{ $puanRenk($p) }}"></div></div>
          <span class="n">{{ $n }}</span></div>
      @endforeach
    </div>
  </div>
  @endif

  {{-- Ayar (sahip/müdür) --}}
  @if($patronMu)
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">settings</span> Ayarlar</div>
    <form method="POST" action="/itibar-ayar" class="form-sat">
      @csrf
      <div class="fld"><label>Google Yorum Linki (mutlu müşteri buraya yönlenir)</label>
        <input name="google_link" value="{{ $googleLink }}" placeholder="https://g.page/r/.../review veya search.google.com/local/writereview?placeid=..."></div>
      <div class="fld" style="flex:0 0 190px;min-width:160px"><label>Uyarı WhatsApp No</label>
        <input name="wa_no" value="{{ $waNo }}" placeholder="05xx..."></div>
      <button class="btn" type="submit"><span class="material-icons-outlined" style="font-size:16px">save</span> Kaydet</button>
    </form>
    <details>
      <summary>📱 Masa değerlendirme linkleri (QR için)</summary>
      <div style="margin-top:8px">
        @foreach($masalar as $m)
          @php $mad=$m->ad ?? ($m->no ?? ($m->masa_no ?? ('Masa '.$m->id))); @endphp
          <div class="masa-link"><b>{{ $mad }}</b><a href="{{ url('/memnuniyet/'.$m->qr_token) }}" target="_blank">{{ url('/memnuniyet/'.$m->qr_token) }}</a></div>
        @endforeach
        <div style="font-size:11px;color:var(--sub);margin-top:6px">💡 Bu linklerin QR'ını masa kartına/fişe koyun. Mevcut masa QR afişlerinizle aynı tokeni kullanır.</div>
      </div>
    </details>
  </div>
  @endif

  {{-- Liste --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">list</span> Değerlendirmeler</div>
    @forelse($liste as $y)
      <div class="yorum">
        <div class="pz" style="background:color-mix(in srgb,{{ $puanRenk($y->puan) }} 15%,transparent)">{{ $puanEmoji[$y->puan] ?? '•' }}</div>
        <div class="ic">
          <div class="bas">
            <span class="masa">{{ $y->masa_ad ?: 'Masa' }}</span>
            <span class="roz" style="background:{{ $puanRenk($y->puan) }}">{{ $y->puan }}/5</span>
            @if($y->durum=='yeni' && $y->puan<=3)<span class="roz" style="background:var(--kirmizi)">BEKLİYOR</span>
            @elseif($y->durum=='cozuldu' && $y->puan<=3)<span class="roz" style="background:var(--yesil)">ÇÖZÜLDÜ</span>@endif
          </div>
          @if($y->sebepler)<div class="sebep">⚠️ {{ $y->sebepler }}</div>@endif
          @if($y->not)<div class="notu">"{{ $y->not }}"</div>@endif
          <div class="zaman">{{ \Carbon\Carbon::parse($y->created_at)->diffForHumans() }}</div>
        </div>
        <div class="sag">
          @if($y->durum=='yeni' && $y->puan<=3)
            <form method="POST" action="/itibar/coz">@csrf<input type="hidden" name="id" value="{{ $y->id }}">
              <button class="btn yesil kucuk" type="submit"><span class="material-icons-outlined" style="font-size:15px">check</span> Çözüldü</button></form>
          @endif
        </div>
      </div>
    @empty
      <div class="bos"><span class="material-icons-outlined" style="font-size:40px;display:block;opacity:.5;margin-bottom:8px">rate_review</span>Henüz değerlendirme yok.<br>Masa QR'larına değerlendirme linkini ekleyince gelmeye başlar.</div>
    @endforelse
  </div>

</div></div>
@endsection

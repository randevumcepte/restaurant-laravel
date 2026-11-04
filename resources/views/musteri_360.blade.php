@extends('layout.app')
@section('rk_ikon', 'support_agent')
@section('baslik', isset($detay) ? 'Müşteri 360' : 'Müşteri 360 / CRM')
@section('title', 'Müşteri 360')

@section('content')
@php
  $tl = fn($n) => number_format((float) round((float) ($n ?? 0)), 0, ',', '.') . '₺';
  $durRenk = ['vip'=>'#F6A623','aktif'=>'#2FBE94','risk'=>'#F59E0B','kayip'=>'#F5576C','uyuyan'=>'#64748B','yeni'=>'#5B8DEF'];
  $durAd = ['vip'=>'VIP','aktif'=>'Aktif','risk'=>'Riskli','kayip'=>'Kayıp','uyuyan'=>'Uyuyan','yeni'=>'Yeni'];
  $durIkon = ['vip'=>'workspace_premium','aktif'=>'how_to_reg','risk'=>'warning_amber','kayip'=>'person_off','uyuyan'=>'bedtime','yeni'=>'person_add'];
@endphp
<style>
  #cr{max-width:1000px;margin:0 auto}
  #cr *{box-sizing:border-box}
  .cr-bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:14px}
  .cr-search{flex:1;min-width:200px;display:flex;gap:8px}
  .cr-search input{flex:1;min-width:0;border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:11px;padding:10px 14px;font-size:14px;font-family:inherit}
  .cr-search button{border:0;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border-radius:11px;padding:0 18px;font-weight:800;cursor:pointer;font-size:13px}
  .cr-ai{display:inline-flex;align-items:center;gap:6px;background:var(--card);border:1px solid var(--line);color:var(--mor1);border-radius:11px;padding:10px 15px;font-weight:800;text-decoration:none;font-size:13px}
  .cr-segs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:14px}
  .cr-seg{flex:none;display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:30px;padding:7px 14px;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap}
  .cr-seg.on{color:#fff;border-color:transparent}
  .cr-seg .material-icons-outlined{font-size:15px}
  .cr-tab{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .cr-r{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);text-decoration:none;color:inherit}
  .cr-r:first-child{border-top:0} .cr-r:hover{background:var(--card2)}
  .cr-av{width:40px;height:40px;border-radius:50%;flex:none;display:grid;place-items:center;font-weight:800;color:#fff;font-size:15px}
  .cr-ri{flex:1;min-width:0} .cr-ri .t{font-weight:700;color:var(--ink);font-size:14px} .cr-ri .d{font-size:11.5px;color:var(--sub);margin-top:1px}
  .cr-rh{text-align:right;flex:none} .cr-rh .h{font-weight:800;font-variant-numeric:tabular-nums;font-size:14px;color:var(--ink)} .cr-rh .s{font-size:10.5px;color:var(--sub)}
  .cr-badge{flex:none;font-size:10px;font-weight:800;padding:3px 9px;border-radius:20px;color:#fff;display:inline-flex;align-items:center;gap:3px}
  .cr-badge .material-icons-outlined{font-size:12px}
  .cr-bos{text-align:center;padding:40px 20px;color:var(--sub);font-size:13.5px}
  .cr-bos .material-icons-outlined{font-size:42px;display:block;margin:0 auto 8px;opacity:.5}
  /* detay */
  .cr-back{display:inline-flex;align-items:center;gap:5px;color:var(--sub);text-decoration:none;font-size:13px;font-weight:700;margin-bottom:12px}
  .cr-hero{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:14px}
  .cr-hav{width:62px;height:62px;border-radius:50%;flex:none;display:grid;place-items:center;font-weight:800;color:#fff;font-size:24px}
  .cr-hi{flex:1;min-width:0} .cr-hi .n{font-size:20px;font-weight:800;color:var(--ink);letter-spacing:-.4px} .cr-hi .m{font-size:12.5px;color:var(--sub);margin-top:2px}
  .cr-mk{display:grid;grid-template-columns:repeat(4,1fr);gap:11px;margin-bottom:14px}
  @media(max-width:620px){.cr-mk{grid-template-columns:repeat(2,1fr)}}
  .cr-m{background:var(--card);border:1px solid var(--line);border-radius:13px;padding:13px 15px}
  .cr-m .l{font-size:11px;color:var(--sub);font-weight:700} .cr-m .b{font-size:20px;font-weight:800;margin-top:3px;letter-spacing:-.4px;color:var(--ink)}
  .cr-sec{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 17px;margin-bottom:14px}
  .cr-sec h3{margin:0 0 11px;font-size:13px;font-weight:800;color:var(--ink);display:flex;align-items:center;gap:7px}
  .cr-sec h3 .material-icons-outlined{font-size:17px;color:var(--mor1)}
  .cr-fav{display:flex;gap:7px;flex-wrap:wrap} .cr-fav span{background:var(--card2);border:1px solid var(--line);border-radius:20px;padding:5px 12px;font-size:12px;font-weight:600;color:var(--ink)}
  .cr-sip{display:flex;align-items:center;gap:10px;padding:9px 0;border-top:1px solid var(--line);font-size:13px} .cr-sip:first-child{border-top:0}
  .cr-sip .dt{color:var(--sub);flex:1;min-width:0} .cr-sip .tt{font-weight:800;font-variant-numeric:tabular-nums}
  .cr-act{display:flex;gap:9px;flex-wrap:wrap}
  .cr-act a{flex:1;min-width:140px;display:inline-flex;align-items:center;justify-content:center;gap:7px;border-radius:11px;padding:11px;font-weight:800;font-size:13px;text-decoration:none}
  .cr-act .p{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff} .cr-act .g{background:var(--card2);border:1px solid var(--line);color:var(--ink)}
  .cr-not{font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;display:flex;gap:8px;align-items:flex-start}
  .cr-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
</style>
<div class="rk-body">
<div id="cr">
@if(isset($detay))
  @php $d=$detay; $dr=$durRenk[$d['durum']]??'#64748B'; @endphp
  <a href="/musteri-360" class="cr-back"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Müşteri listesi</a>
  <div class="cr-hero">
    <div class="cr-hav" style="background:{{ $dr }}">{{ mb_strtoupper(mb_substr($m->ad ?? 'M',0,1)) }}</div>
    <div class="cr-hi">
      <div class="n">{{ $m->ad ?? 'Müşteri' }}</div>
      <div class="m">{{ $m->telefon ?? '—' }}{{ !empty($m->eposta) ? ' · '.$m->eposta : '' }} · {{ $m->created_at ? 'üyelik '.\Carbon\Carbon::parse($m->created_at)->format('m.Y') : '' }}</div>
    </div>
    <span class="cr-badge" style="background:{{ $dr }}"><span class="material-icons-outlined">{{ $durIkon[$d['durum']]??'person' }}</span>{{ $durAd[$d['durum']]??$d['durum'] }}</span>
  </div>
  <div class="cr-mk">
    <div class="cr-m"><div class="l">Toplam harcama</div><div class="b" style="color:#2FBE94">{{ $tl($m->toplam_harcama ?? 0) }}</div></div>
    <div class="cr-m"><div class="l">Sipariş sayısı</div><div class="b">{{ (int)($m->siparis_sayisi ?? 0) }}</div></div>
    <div class="cr-m"><div class="l">Ortalama fiş</div><div class="b" style="color:#7C3AED">{{ $tl($d['ort_fis']) }}</div></div>
    <div class="cr-m"><div class="l">Son ziyaret</div><div class="b" style="color:{{ $dr }}">{{ $d['son_gun']===null ? '—' : $d['son_gun'].' <small style=font-size:12px>gün önce</small>' }}</div></div>
  </div>
  @if(!empty($d['favori']))
  <div class="cr-sec"><h3><span class="material-icons-outlined">restaurant</span> Favori ürünler</h3>
    <div class="cr-fav">@foreach($d['favori'] as $f)<span>{{ $f }}</span>@endforeach</div>
  </div>
  @endif
  <div class="cr-sec"><h3><span class="material-icons-outlined">history</span> Son siparişler</h3>
    @forelse($d['siparisler'] as $s)
      <div class="cr-sip"><span class="material-icons-outlined" style="font-size:16px;color:var(--sub2)">receipt_long</span><div class="dt">{{ $s->kapanis ? \Carbon\Carbon::parse($s->kapanis)->format('d.m.Y H:i') : '—' }}{{ !empty($s->kisi_sayisi) ? ' · '.$s->kisi_sayisi.' kişi' : '' }}</div><div class="tt">{{ $tl($s->toplam) }}</div></div>
    @empty
      <div style="color:var(--sub);font-size:13px;padding:6px 0">Kapanmış sipariş kaydı yok.</div>
    @endforelse
  </div>
  @if(!empty($m->notlar))
  <div class="cr-sec"><h3><span class="material-icons-outlined">sticky_note_2</span> Not</h3><div style="font-size:13px;color:var(--ink);line-height:1.6">{{ $m->notlar }}</div></div>
  @endif
  <div class="cr-sec"><h3><span class="material-icons-outlined">bolt</span> Hızlı aksiyon</h3>
    <div class="cr-act">
      <a class="p" href="/crm-sohbet"><span class="material-icons-outlined" style="font-size:17px">smart_toy</span> CRM AI ile işlem yap</a>
      @if(!empty($m->telefon))<a class="g" href="tel:{{ $m->telefon }}"><span class="material-icons-outlined" style="font-size:17px">call</span> Ara</a>@endif
      <a class="g" href="/sadakat"><span class="material-icons-outlined" style="font-size:17px">loyalty</span> Sadakat / Puan</a>
    </div>
  </div>
@else
  @php
    $segler = [''=>['Tümü','groups'],'vip'=>['VIP','workspace_premium'],'aktif'=>['Aktif','how_to_reg'],'risk'=>['Riskli','warning_amber'],'kayip'=>['Kayıp','person_off'],'uyuyan'=>['Uyuyan','bedtime'],'yeni'=>['Yeni','person_add']];
  @endphp
  <form class="cr-bar" method="GET" action="/musteri-360">
    @if($seg)<input type="hidden" name="seg" value="{{ $seg }}">@endif
    <div class="cr-search"><input name="q" value="{{ $q }}" placeholder="İsim veya telefonla müşteri ara…"><button type="submit">Ara</button></div>
    <a href="/crm-sohbet" class="cr-ai"><span class="material-icons-outlined" style="font-size:17px">smart_toy</span> CRM AI</a>
  </form>
  <div class="cr-segs">
    @foreach($segler as $sk=>$sv)
      @php $on = $seg===$sk; $c = $durRenk[$sk] ?? '#7C3AED'; @endphp
      <a class="cr-seg {{ $on?'on':'' }}" @if($on)style="background:{{ $c }}"@endif href="/musteri-360?seg={{ $sk }}{{ $q?'&q='.urlencode($q):'' }}"><span class="material-icons-outlined">{{ $sv[1] }}</span>{{ $sv[0] }}</a>
    @endforeach
  </div>
  <div class="cr-tab">
    @forelse($liste as $c)
      @php $cr = $durRenk[$c->durum]??'#64748B'; @endphp
      <a class="cr-r" href="/musteri-360?id={{ $c->id }}">
        <div class="cr-av" style="background:{{ $cr }}">{{ mb_strtoupper(mb_substr($c->ad ?? 'M',0,1)) }}</div>
        <div class="cr-ri"><div class="t">{{ $c->ad ?: 'İsimsiz müşteri' }}</div><div class="d">{{ $c->telefon ?: '—' }} · {{ (int)($c->siparis_sayisi ?? 0) }} sipariş{{ $c->son_gun!==null ? ' · son '.$c->son_gun.'g önce' : '' }}</div></div>
        <div class="cr-rh"><div class="h">{{ $tl($c->toplam_harcama ?? 0) }}</div><div class="s">toplam</div></div>
        <span class="cr-badge" style="background:{{ $cr }}"><span class="material-icons-outlined">{{ $durIkon[$c->durum]??'person' }}</span>{{ $durAd[$c->durum]??$c->durum }}</span>
      </a>
    @empty
      <div class="cr-bos"><span class="material-icons-outlined">person_search</span>{{ $q||$seg ? 'Bu filtreyle müşteri bulunamadı.' : 'Henüz müşteri kaydı yok. Sadakat programı ve QR menüyle müşteri topla.' }}</div>
    @endforelse
  </div>
  <div style="margin-top:12px" class="cr-not"><span class="material-icons-outlined">info</span><span>Müşteri sağlık durumu son ziyaret sıklığından hesaplanır: <b>Aktif</b> 30 gün içinde, <b>Riskli</b> 30-60g, <b>Kayıp</b> 60-120g geldi. <b>VIP</b> = 5.000₺+ harcayan. Kayıp/riskli müşterileri <a href="/crm-sohbet" style="color:var(--mor1);font-weight:700;text-decoration:none">CRM AI</a> ile geri kazanabilirsin (toplu WhatsApp YOK — sadece uygulama bildirimi).</span></div>
@endif
</div>
</div>
@endsection

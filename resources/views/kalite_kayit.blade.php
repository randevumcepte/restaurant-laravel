@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Kalite Kayıt Defteri')
@section('title', 'Kalite Kayıt Defteri')

@section('content')
@php
  $alanlar = [
    'numune'          => ['ad'=>'Şahit Numune', 'ikon'=>'science', 'renk'=>'#7C3AED', 'baslikPh'=>'Örn: Akşam menüsü – tavuk sote', 'degerLbl'=>'Saklama yeri / saat', 'sonLbl'=>'İmha tarihi (72 saat sonra)', 'ipucu'=>'Her öğün servis edilen yemeklerden 100-150g numune alınır, 72 saat +4°C\'de saklanır. Yasal zorunluluk.'],
    'ilaclama'        => ['ad'=>'Haşere / İlaçlama', 'ikon'=>'pest_control', 'renk'=>'#EA580C', 'baslikPh'=>'Örn: Aylık genel ilaçlama', 'degerLbl'=>'İlaçlama firması', 'sonLbl'=>'Sonraki ilaçlama tarihi', 'ipucu'=>'Lisanslı firma ile periyodik ilaçlama + uygunluk belgesi. Denetimde sorulur.'],
    'kalibrasyon'     => ['ad'=>'Kalibrasyon', 'ikon'=>'straighten', 'renk'=>'#0EA5E9', 'baslikPh'=>'Örn: Buzdolabı termometresi', 'degerLbl'=>'Sonuç / sapma', 'sonLbl'=>'Sonraki kalibrasyon tarihi', 'ipucu'=>'Termometre ve teraziler periyodik kalibre edilmeli; ölçümlerin güvenilirliği için.'],
    'tedarikci_belge' => ['ad'=>'Tedarikçi Belgesi', 'ikon'=>'description', 'renk'=>'#16A34A', 'baslikPh'=>'Örn: ABC Gıda – İşletme Kayıt Belgesi', 'degerLbl'=>'Belge no / firma', 'sonLbl'=>'Belge geçerlilik sonu', 'ipucu'=>'Tedarikçilerin işletme kayıt / gıda güvenliği (ISO 22000) belgelerini izle; süresi dolanı yenilet.'],
    'denetim'         => ['ad'=>'Denetim / Teftiş', 'ikon'=>'verified_user', 'renk'=>'#16A34A', 'baslikPh'=>'Örn: Tarım Bakanlığı rutin denetim', 'degerLbl'=>'Sonuç / puan / denetçi', 'sonLbl'=>'Sonraki denetim (planlı)', 'ipucu'=>'Resmi denetim ve iç teftiş geçmişi; bulgular ve kapanışları kaydet.'],
  ];
  $a = $alanlar[$tip] ?? $alanlar['numune'];
@endphp
<style>
  #kk{max-width:960px;margin:0 auto}
  #kk *{box-sizing:border-box}
  .kk-tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:14px}
  .kk-tab{flex:none;display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:30px;padding:8px 15px;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap}
  .kk-tab.on{color:#fff;border-color:transparent}
  .kk-tab .material-icons-outlined{font-size:16px}
  .kk-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .kk-msg.ok{background:rgba(16,185,129,.12);color:#047857} .kk-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .kk-ipucu{display:flex;gap:8px;align-items:flex-start;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .kk-ipucu .material-icons-outlined{font-size:16px;flex:none;margin-top:1px}
  .kk-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
  .kk-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .kk-form input{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .kk-add{color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .kk-tab2{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .kk-r{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .kk-r:first-child{border-top:0}
  .kk-r .ri{flex:1;min-width:0}.kk-r .ri .t{font-weight:700;color:var(--ink)}.kk-r .ri .d{font-size:11px;color:var(--sub);margin-top:1px}
  .kk-r .son{font-size:11.5px;font-weight:800;white-space:nowrap}
  .kk-r button{border:0;background:transparent;cursor:pointer;color:var(--sub2)} .kk-r button .material-icons-outlined{font-size:18px}
  .kk-bos{text-align:center;padding:30px;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="kk">
  @if(session('ok'))<div class="kk-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="kk-msg er">{{ session('hata') }}</div>@endif

  <div class="kk-tabs">
    @foreach($tipler as $k=>$v)
      <a class="kk-tab {{ $tip===$k?'on':'' }}" @if($tip===$k)style="background:{{ $alanlar[$k]['renk'] }}"@endif href="/kalite-kayit?tip={{ $k }}"><span class="material-icons-outlined">{{ $alanlar[$k]['ikon'] }}</span>{{ $v }}</a>
    @endforeach
  </div>

  <div class="kk-ipucu"><span class="material-icons-outlined" style="color:{{ $a['renk'] }}">lightbulb</span><span>{{ $a['ipucu'] }}</span></div>

  <form class="kk-form" method="POST" action="/kalite-kayit/ekle">
    @csrf<input type="hidden" name="tip" value="{{ $tip }}">
    <div style="flex:1;min-width:180px"><label>Kayıt / başlık</label><input name="baslik" style="width:100%" placeholder="{{ $a['baslikPh'] }}" required></div>
    <div><label>{{ $a['degerLbl'] }}</label><input name="deger" style="width:150px"></div>
    <div><label>Tarih</label><input name="tarih" type="date" value="{{ now()->toDateString() }}"></div>
    <div><label>{{ $a['sonLbl'] }}</label><input name="son_tarih" type="date"></div>
    <button class="kk-add" type="submit" style="background:{{ $a['renk'] }}">+ Ekle</button>
  </form>

  <div class="kk-tab2">
    @forelse($liste as $k)
      @php
        $son = $k->son_tarih ? \Carbon\Carbon::parse($k->son_tarih) : null;
        $gecti = $son && $son->isPast(); $yaklasti = $son && !$gecti && $son->diffInDays(now()) <= 15;
        $sonRenk = $gecti ? '#F43F5E' : ($yaklasti ? '#F59E0B' : '#2FBE94');
      @endphp
      <div class="kk-r">
        <span class="material-icons-outlined" style="font-size:18px;color:{{ $a['renk'] }}">{{ $a['ikon'] }}</span>
        <div class="ri"><div class="t">{{ $k->baslik }}</div><div class="d">{{ $k->tarih ? \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') : '' }}{{ $k->deger ? ' · '.$k->deger : '' }}{{ $k->personel_ad ? ' · '.$k->personel_ad : '' }}{{ $k->not ? ' · '.$k->not : '' }}</div></div>
        @if($son)<span class="son" style="color:{{ $sonRenk }}">{{ $gecti ? 'Doldu' : ($yaklasti?'Yaklaştı':'Geçerli') }}<br>{{ $son->format('d.m.Y') }}</span>@endif
        <form method="POST" action="/kalite-kayit/sil" onsubmit="return confirm('Silinsin mi?')" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><input type="hidden" name="tip" value="{{ $tip }}"><button type="submit"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="kk-bos">Henüz {{ mb_strtolower($a['ad']) }} kaydı yok. Yukarıdan ekle.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'menu_book')
@section('baslik', 'Mutfak Kontrol Defteri')
@section('title', 'Mutfak Kontrol Defteri')

@section('content')
@php
  $cfg = [
    'sicaklik' => ['ad'=>'Sıcaklık (HACCP)','ikon'=>'thermostat','renk'=>'#0EA5E9',
       'baslikLbl'=>'Nokta / Cihaz','baslikPh'=>'Örn: Buzdolabı 1 · Et dolabı · Bain-marie',
       'kategoriler'=>['Buzdolabı','Dondurucu','Sıcak bekletme','Pişirme','Soğutma','Teslim alma'],
       'degerLbl'=>'Ölçülen °C','deger2Lbl'=>'Üst limit °C','birim'=>false,
       'ipucu'=>'Her kontrolde ölçülen sıcaklığı ve kabul limitini yaz. Ölçülen ≤ limit ise UYGUN, değilse UYGUNSUZ işaretlenir. Limitler Türk Gıda Kodeksi / işletme HACCP planına göre girilir.'],
    'fire' => ['ad'=>'Fire / İsraf','ikon'=>'delete_outline','renk'=>'#EA580C',
       'baslikLbl'=>'Ürün / Malzeme','baslikPh'=>'Örn: Domates · Pişmiş pilav · Tavuk but',
       'kategoriler'=>['Hazırlık fire','Bozulma / SKT','Tabak / porsiyon','Pişirme hatası'],
       'degerLbl'=>'Miktar','deger2Lbl'=>'Tutar (₺)','birim'=>true,
       'ipucu'=>'Atılan her şeyi kategorisiyle ve TL değeriyle yaz. Haftalık gözden geçir: en çok nerede israf var, ona göre porsiyon/sipariş ayarla.'],
    'checklist' => ['ad'=>'Açılış / Kapanış','ikon'=>'checklist','renk'=>'#16A34A',
       'baslikLbl'=>'Kontrol maddesi','baslikPh'=>'Örn: Fırın açıldı · Soğuk oda temiz · Gaz kapatıldı',
       'kategoriler'=>['Açılış','Servis öncesi','Kapanış'],
       'degerLbl'=>'','deger2Lbl'=>'','birim'=>false,
       'ipucu'=>'Açılış ve kapanışta yapılan kontrolleri tek tek işaretle. Muğlak değil net madde yaz (ör. "istasyon hazır" değil "ızgara 180°C ve mise en place tam").'],
    'prep' => ['ad'=>'Prep / Par','ikon'=>'restaurant_menu','renk'=>'#7C3AED',
       'baslikLbl'=>'Ürün / Yarı mamül','baslikPh'=>'Örn: Köfte harcı · Sos · Doğranmış soğan',
       'kategoriler'=>[],
       'degerLbl'=>'Par (hedef)','deger2Lbl'=>'Mevcut','birim'=>true,
       'ipucu'=>'Her ürün için günün beklenen yoğunluğuna göre PAR (hedef) ve mevcut miktarı yaz. Hazırlanacak = Par − Mevcut otomatik çıkar.'],
  ];
  $a = $cfg[$tip] ?? $cfg['sicaklik'];
  $sonucRenk = ['uygun'=>['#047857','rgba(16,185,129,.12)','✓ Uygun'],'uygunsuz'=>['#9f1239','rgba(244,63,94,.12)','✕ Uygunsuz'],'yapildi'=>['#047857','rgba(16,185,129,.12)','✓ Yapıldı']];
@endphp
<style>
  #md{max-width:1000px;margin:0 auto}
  #md *{box-sizing:border-box}
  .md-tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:14px}
  .md-tab{flex:none;display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:30px;padding:8px 15px;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap}
  .md-tab.on{color:#fff;border-color:transparent}
  .md-tab .material-icons-outlined{font-size:16px}
  .md-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .md-msg.ok{background:rgba(16,185,129,.12);color:#047857} .md-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .md-ipucu{display:flex;gap:8px;align-items:flex-start;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .md-ipucu .material-icons-outlined{font-size:16px;flex:none;margin-top:1px}
  .md-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
  .md-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .md-form input,.md-form select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .md-add{color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .md-list{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .md-r{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .md-r:first-child{border-top:0}
  .md-r .ri{flex:1;min-width:0}.md-r .ri .t{font-weight:700;color:var(--ink)}.md-r .ri .d{font-size:11px;color:var(--sub);margin-top:1px}
  .md-chip{font-size:10px;font-weight:800;border-radius:20px;padding:2px 8px;background:var(--card2);color:var(--sub2);white-space:nowrap}
  .md-badge{font-size:10px;font-weight:800;border-radius:20px;padding:2px 9px;white-space:nowrap}
  .md-val{font-size:12.5px;font-weight:800;color:var(--ink);white-space:nowrap;text-align:right}
  .md-r form{display:inline;margin:0}
  .md-r button.ic{border:0;background:transparent;cursor:pointer;color:var(--sub2)} .md-r button.ic .material-icons-outlined{font-size:18px}
  .md-bos{text-align:center;padding:30px;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="md">
  @if(session('ok'))<div class="md-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="md-msg er">{{ session('hata') }}</div>@endif

  <div class="md-tabs">
    @foreach($tipler as $k=>$v)
      <a class="md-tab {{ $tip===$k?'on':'' }}" @if($tip===$k)style="background:{{ $cfg[$k]['renk'] }}"@endif href="/mutfak-defteri?tip={{ $k }}"><span class="material-icons-outlined">{{ $cfg[$k]['ikon'] }}</span>{{ $v }}</a>
    @endforeach
  </div>

  <div class="md-ipucu"><span class="material-icons-outlined" style="color:{{ $a['renk'] }}">lightbulb</span><span>{{ $a['ipucu'] }}</span></div>

  <form class="md-form" method="POST" action="/mutfak-defteri/ekle">
    @csrf<input type="hidden" name="tip" value="{{ $tip }}">
    <div style="flex:1;min-width:170px"><label>{{ $a['baslikLbl'] }}</label><input name="baslik" style="width:100%" placeholder="{{ $a['baslikPh'] }}" required></div>
    @if(count($a['kategoriler']))
      <div><label>Kategori</label><select name="kategori">@foreach($a['kategoriler'] as $kat)<option value="{{ $kat }}">{{ $kat }}</option>@endforeach</select></div>
    @endif
    @if($a['degerLbl'])<div><label>{{ $a['degerLbl'] }}</label><input name="deger" style="width:110px" @if($tip==='sicaklik' || $tip==='prep')type="number" step="any"@endif></div>@endif
    @if($a['birim'])<div><label>Birim</label><input name="birim" style="width:80px" placeholder="kg/adet/L"></div>@endif
    @if($a['deger2Lbl'])<div><label>{{ $a['deger2Lbl'] }}</label><input name="deger2" style="width:110px" type="number" step="any"></div>@endif
    <div><label>Tarih</label><input name="tarih" type="date" value="{{ now()->toDateString() }}"></div>
    <button class="md-add" type="submit" style="background:{{ $a['renk'] }}">+ Ekle</button>
  </form>

  <div class="md-list">
    @forelse($liste as $k)
      @php
        $sr = $k->sonuc ? ($sonucRenk[$k->sonuc] ?? null) : null;
        $hazirla = ($tip==='prep' && is_numeric($k->deger) && is_numeric($k->deger2)) ? max(0, (float)$k->deger - (float)$k->deger2) : null;
      @endphp
      <div class="md-r">
        <span class="material-icons-outlined" style="font-size:18px;color:{{ $a['renk'] }}">{{ $a['ikon'] }}</span>
        <div class="ri">
          <div class="t">{{ $k->baslik }}
            @if($k->kategori)<span class="md-chip">{{ $k->kategori }}</span>@endif
            @if($sr)<span class="md-badge" style="background:{{ $sr[1] }};color:{{ $sr[0] }}">{{ $sr[2] }}</span>@endif
          </div>
          <div class="d">
            {{ $k->tarih ? \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') : '' }}{{ $k->personel_ad ? ' · '.$k->personel_ad : '' }}{{ $k->not ? ' · '.$k->not : '' }}
          </div>
        </div>
        <span class="md-val">
          @if($tip==='sicaklik'){{ $k->deger!==null ? $k->deger.'°C' : '' }}{{ $k->deger2!==null ? ' / limit '.$k->deger2.'°C' : '' }}
          @elseif($tip==='fire'){{ $k->deger!==null ? $k->deger.' '.$k->birim : '' }}{{ $k->deger2!==null ? ' · '.$k->deger2.'₺' : '' }}
          @elseif($tip==='prep')Par {{ $k->deger }} / Mevcut {{ $k->deger2 }}{{ $hazirla!==null ? ' → hazırla '.rtrim(rtrim(number_format($hazirla,2,'.',''),'0'),'.').' '.$k->birim : '' }}
          @endif
        </span>
        <form method="POST" action="/mutfak-defteri/sil" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><input type="hidden" name="tip" value="{{ $tip }}"><button type="submit" class="ic"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="md-bos">Henüz {{ mb_strtolower($a['ad']) }} kaydı yok. Yukarıdan ekle.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

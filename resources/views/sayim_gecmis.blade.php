@extends('layout.app')
@section('rk_ikon', 'history')
@section('baslik', 'Sayım Geçmişi')
@section('title', 'Sayım Geçmişi — ResteOS')
@section('rk_geri', '/sayim')

@push('head')
<style>
#symG{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #symG{--card:#1b2235;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650}

#symG .arama{position:relative;margin-bottom:14px}
#symG .arama .material-icons-outlined{position:absolute;left:11px;top:50%;transform:translateY(-50%);
  font-size:18px;color:var(--sub);pointer-events:none}
#symG .arama input{width:100%;padding:10px 12px 10px 38px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s}
#symG .arama input:focus{border-color:var(--mor1);box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 12%,transparent)}

#symG .ozet-strip{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
#symG .ozet-kart{flex:1;min-width:140px;padding:12px 14px;border-radius:12px;background:var(--card);
  border:1px solid var(--line)}
#symG .ozet-kart .et{color:var(--sub);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
#symG .ozet-kart .deg{color:var(--ink);font-size:19px;font-weight:800;margin-top:2px;font-variant-numeric:tabular-nums}
#symG .ozet-kart.minus .deg{color:var(--kirmizi)}
#symG .ozet-kart.plus .deg{color:var(--amber)}

#symG .liste{display:grid;gap:10px}
#symG .kart{display:flex;align-items:center;gap:12px;padding:14px;border-radius:14px;background:var(--card);
  border:1px solid var(--line);cursor:pointer;transition:all .15s;text-decoration:none}
#symG .kart:hover{border-color:color-mix(in srgb,var(--mor1) 35%,var(--line));
  box-shadow:0 6px 20px -4px rgba(0,0,0,.08);transform:translateY(-1px)}
#symG .kart .tarih-kutu{width:52px;height:52px;border-radius:12px;flex-shrink:0;display:flex;
  flex-direction:column;align-items:center;justify-content:center;color:#fff;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));font-weight:800;line-height:1}
#symG .kart .tarih-kutu .gun{font-size:17px}
#symG .kart .tarih-kutu .ay{font-size:9.5px;text-transform:uppercase;opacity:.9;margin-top:2px;letter-spacing:.3px}
#symG .kart .orta{flex:1;min-width:0}
#symG .kart .baslik{color:var(--ink);font-size:14.5px;font-weight:700}
#symG .kart .alt{color:var(--sub);font-size:12.5px;margin-top:3px;display:flex;gap:6px;flex-wrap:wrap}
#symG .kart .alt .mikro{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:999px;
  background:color-mix(in srgb,var(--sub) 10%,transparent);font-weight:600;font-size:11.5px}
#symG .kart .alt .mikro.e{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:var(--kirmizi)}
#symG .kart .alt .mikro.f{background:color-mix(in srgb,var(--amber) 15%,transparent);color:var(--amber)}
#symG .kart .alt .mikro.n{background:color-mix(in srgb,var(--mor1) 10%,transparent);color:var(--mor1)}
#symG .kart .tutar{text-align:right;flex-shrink:0}
#symG .kart .tutar .v{font-size:16px;font-weight:800;font-variant-numeric:tabular-nums}
#symG .kart .tutar .et{font-size:10.5px;color:var(--sub2);margin-top:1px;text-transform:uppercase;letter-spacing:.3px}
#symG .kart .ok{color:var(--sub2);flex-shrink:0}

#symG .bos{padding:60px 20px;text-align:center;color:var(--sub);background:var(--card);
  border:1px dashed var(--line);border-radius:14px}
#symG .bos .material-icons-outlined{font-size:48px;color:var(--sub2);display:block;margin-bottom:10px}
#symG .bos .ac{color:var(--mor1);font-weight:700;text-decoration:none;display:inline-block;margin-top:10px;
  padding:9px 16px;border-radius:10px;border:1px solid color-mix(in srgb,var(--mor1) 30%,var(--line));
  background:color-mix(in srgb,var(--mor1) 8%,var(--card))}
#symG .bos .ac:hover{background:var(--mor1);color:#fff}

/* Detay modal */
#symGMod{display:none;position:fixed;inset:0;z-index:900;align-items:flex-start;justify-content:center;
  background:rgba(0,0,0,.45);padding:16px;overflow-y:auto}
#symGMod.acik{display:flex}
#symGMod .wrap{background:var(--card);border-radius:18px;width:100%;max-width:600px;overflow:hidden;margin:auto}
#symGMod .head{display:flex;align-items:center;justify-content:space-between;padding:16px 16px 12px;
  border-bottom:1px solid var(--line)}
#symGMod .head .t{color:var(--ink);font-size:16px;font-weight:700}
#symGMod .head .k{width:32px;height:32px;border:none;background:transparent;cursor:pointer;display:flex;
  align-items:center;justify-content:center;border-radius:8px;color:var(--sub)}
#symGMod .head .k:hover{background:var(--line)}
#symGMod .body{padding:14px 16px 20px}
#symGMod .det-ozet{border-radius:16px;padding:16px;margin-bottom:14px}
#symGMod .det-ozet .et{color:rgba(255,255,255,.75);font-size:13px;margin-bottom:2px}
#symGMod .det-ozet .dg{color:#fff;font-size:26px;font-weight:800}
#symGMod .ai{display:flex;gap:8px;align-items:flex-start;padding:12px;border-radius:12px;background:var(--card);
  border:1px solid color-mix(in srgb,#9D5DC8 40%,transparent);margin-bottom:8px}
#symGMod .ai .em{font-size:14px;flex-shrink:0;margin-top:1px}
#symGMod .ai .mt{color:var(--ink);font-size:13px;line-height:1.35}
#symGMod .k-bas{color:var(--ink);font-size:15px;font-weight:700;margin:10px 0 8px}
#symGMod .k-sat{display:flex;align-items:center;padding:12px;border-radius:12px;background:var(--card);
  border:1px solid var(--line);margin-bottom:8px}
#symGMod .k-sat .s{flex:4;min-width:0}
#symGMod .k-sat .m{color:var(--ink);font-size:14px;font-weight:600}
#symGMod .k-sat .b{color:var(--sub);font-size:11.5px;margin-top:1px}
#symGMod .k-sat .r{text-align:right;flex-shrink:0}
#symGMod .k-sat .fv{font-size:14px;font-weight:700}
#symGMod .k-sat .ft{font-size:11px;margin-top:1px}
#symGMod .yuk{text-align:center;padding:40px;color:var(--sub)}
</style>
@endpush

@section('content')
@php
  $sayimlar = $sayimlar ?? [];
  $toplam = count($sayimlar);
  $toplamKayip = 0; $toplamFazla = 0;
  foreach ($sayimlar as $s) {
    $f = (float) $s['fark_maliyet'];
    if ($f < 0) $toplamKayip += abs($f);
    else $toplamFazla += $f;
  }
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . '₺';

  // ay kısaltmaları
  $aylar = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
  $ayKis = function ($tarih) use ($aylar) {
    $p = explode('.', $tarih); // d.m.Y
    if (count($p) !== 3) return '';
    $ay = (int) $p[1];
    return $ay >= 1 && $ay <= 12 ? $aylar[$ay - 1] : '';
  };
@endphp

<div id="symG">

  @if (!$sayimlar)
    <div class="bos">
      <span class="material-icons-outlined">inventory_2</span>
      Henüz sayım yapılmamış.
      <br><a class="ac" href="/sayim">Yeni Sayım Başlat</a>
    </div>
  @else

    {{-- Özet şerit --}}
    <div class="ozet-strip">
      <div class="ozet-kart">
        <div class="et">Toplam Sayım</div>
        <div class="deg">{{ $toplam }}</div>
      </div>
      <div class="ozet-kart minus">
        <div class="et">Toplam Kayıp</div>
        <div class="deg">{{ $tl($toplamKayip) }}</div>
      </div>
      <div class="ozet-kart plus">
        <div class="et">Toplam Fazla</div>
        <div class="deg">{{ $tl($toplamFazla) }}</div>
      </div>
      <div class="ozet-kart">
        <div class="et">Net</div>
        <div class="deg" style="color:{{ $toplamFazla - $toplamKayip < 0 ? 'var(--kirmizi)' : (($toplamFazla - $toplamKayip) > 0 ? 'var(--amber)' : 'var(--yesil)') }}">
          {{ ($toplamFazla - $toplamKayip) < 0 ? '-' : '' }}{{ $tl(abs($toplamFazla - $toplamKayip)) }}
        </div>
      </div>
    </div>

    {{-- Arama --}}
    <div class="arama">
      <span class="material-icons-outlined">search</span>
      <input id="symGAra" type="text" placeholder="Tarih ile ara (örn: 10.2026)" oninput="symGFiltre()" autocomplete="off">
    </div>

    {{-- Liste --}}
    <div class="liste" id="symGListe">
      @foreach ($sayimlar as $s)
        @php
          $fm = (float) $s['fark_maliyet'];
          $renk = $fm < 0 ? 'var(--kirmizi)' : ($fm > 0 ? 'var(--amber)' : 'var(--yesil)');
          $p = explode('.', $s['tarih']);
          $gun = $p[0] ?? '';
          $ay = $ayKis($s['tarih']);
        @endphp
        <div class="kart" data-tarih="{{ $s['tarih'] }}" onclick="symGDetay({{ $s['id'] }})">
          <div class="tarih-kutu">
            <div class="gun">{{ $gun }}</div>
            <div class="ay">{{ $ay }}</div>
          </div>
          <div class="orta">
            <div class="baslik">{{ $s['tarih_uzun'] ?? $s['tarih'] }}</div>
            <div class="alt">
              <span class="mikro n">{{ $s['kalem'] }} kalem</span>
              @if ($s['eksik'])<span class="mikro e">{{ $s['eksik'] }} eksik</span>@endif
              @if ($s['fazla'])<span class="mikro f">{{ $s['fazla'] }} fazla</span>@endif
            </div>
          </div>
          <div class="tutar">
            <div class="v" style="color:{{ $renk }}">
              {{ $fm < 0 ? '-' . $tl(abs($fm)) : ($fm > 0 ? '+' . $tl($fm) : '0') }}
            </div>
            <div class="et">{{ $fm < 0 ? 'Kayıp' : ($fm > 0 ? 'Fazla' : 'Fark yok') }}</div>
          </div>
          <span class="material-icons-outlined ok">chevron_right</span>
        </div>
      @endforeach
    </div>

  @endif

</div>

{{-- Detay modal --}}
<div id="symGMod" onclick="if(event.target===this)symGKapat()">
  <div class="wrap">
    <div class="head">
      <div class="t" id="symGModT">Sayım Detayı</div>
      <button class="k" onclick="symGKapat()" title="Kapat"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="body" id="symGModB">
      <div class="yuk">Yükleniyor…</div>
    </div>
  </div>
</div>

<script>
function _symG_mik(v){v=parseFloat(v)||0;return Math.abs(v)===Math.round(Math.abs(v))?String(Math.round(Math.abs(v))):Math.abs(v).toFixed(2);}
function _symG_tl(v){return Math.round(Math.abs(parseFloat(v)||0)).toLocaleString('tr-TR')+'₺';}
function _symG_renk(f){return f<-0.0001?'var(--kirmizi)':(f>0.0001?'var(--amber)':'var(--yesil)');}
function _symG_esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}

function symGFiltre(){
  var q=(document.getElementById('symGAra').value||'').toLowerCase().trim();
  document.querySelectorAll('#symGListe .kart').forEach(function(k){
    var t=(k.getAttribute('data-tarih')||'').toLowerCase();
    k.style.display=(!q||t.indexOf(q)!==-1)?'':'none';
  });
}

function symGDetay(id){
  var m=document.getElementById('symGMod'),b=document.getElementById('symGModB'),t=document.getElementById('symGModT');
  t.textContent='Sayım Detayı';b.innerHTML='<div class="yuk">Yükleniyor…</div>';
  m.classList.add('acik');document.body.style.overflow='hidden';
  api('/api/patron/sayim-detay?id='+id).then(function(res){
    if(!res||res.ok!==1){b.innerHTML='<div class="yuk">Sayım bulunamadı.</div>';return;}
    t.textContent='Sayım — '+(res.tarih||'');
    var fm=parseFloat(res.toplam_fark_maliyet)||0;
    var g=fm<0?'linear-gradient(135deg,#F43F5E,#F59E0B)':(fm>0?'linear-gradient(135deg,#F59E0B,#4F46E5)':'linear-gradient(135deg,#10B981,#4F46E5)');
    var e=fm<0?'Toplam Açık (Kayıp)':(fm>0?'Toplam Fazla':'Fark Yok');
    var d=fm===0?'Tertemiz':_symG_tl(Math.abs(fm));
    var h='<div class="det-ozet" style="background:'+g+'"><div class="et">'+e+'</div><div class="dg">'+d+'</div></div>';
    (res.ai||[]).forEach(function(a){h+='<div class="ai"><span class="em">✨</span><div class="mt">'+_symG_esc(a.mesaj||'')+'</div></div>';});
    h+='<div class="k-bas">Kalem Bazında Fark</div>';
    (res.kalemler||[]).forEach(function(k){
      var fk=parseFloat(k.fark)||0,fmk=parseFloat(k.fark_maliyet)||0,r=_symG_renk(fk);
      var fs=fk>0.0001?'+'+_symG_mik(fk):(fk<-0.0001?'-'+_symG_mik(fk):_symG_mik(fk));
      h+='<div class="k-sat"><div class="s"><div class="m">'+_symG_esc(k.malzeme||'')+'</div>';
      h+='<div class="b">Sistem '+_symG_mik(k.teorik)+' · Sayılan '+_symG_mik(k.sayilan)+' '+_symG_esc(k.birim||'')+'</div></div>';
      h+='<div class="r"><div class="fv" style="color:'+r+'">'+fs+'</div>';
      if(Math.abs(fmk)>=1)h+='<div class="ft" style="color:'+r+'">'+(fmk<0?'-':'+')+_symG_tl(fmk)+'</div>';
      h+='</div></div>';
    });
    b.innerHTML=h;
  }).catch(function(){b.innerHTML='<div class="yuk">Bağlantı hatası.</div>';});
}
function symGKapat(){document.getElementById('symGMod').classList.remove('acik');document.body.style.overflow='';}
document.addEventListener('keydown',function(e){if(e.key==='Escape')symGKapat();});
</script>
@endsection

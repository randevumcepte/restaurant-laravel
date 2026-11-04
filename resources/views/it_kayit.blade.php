@extends('layout.app')
@section('rk_ikon', 'dns')
@section('baslik', 'IT Kayıt / Arıza Defteri')
@section('title', 'IT Kayıt Defteri')

@section('content')
@php
  $alanlar = [
    'ariza'  => ['ad'=>'Arıza / Destek', 'ikon'=>'build', 'renk'=>'#F97316', 'baslikPh'=>'Örn: Kasa yazıcısı çıktı vermiyor', 'degerLbl'=>'Cihaz / konum', 'sonLbl'=>'Hedef çözüm tarihi', 'ipucu'=>'Arıza/talep açıldığında kaydet; çözülünce "Kapat" ile kapat. Tekrarlayan arıza kök neden analizi gerektirir.', 'oncelik'=>true, 'kapat'=>true],
    'lisans' => ['ad'=>'Lisans / Domain / SSL', 'ikon'=>'verified', 'renk'=>'#4F46E5', 'baslikPh'=>'Örn: restoran.com SSL sertifikası', 'degerLbl'=>'Sağlayıcı / no', 'sonLbl'=>'Bitiş / yenileme tarihi', 'ipucu'=>'Domain, SSL sertifikası ve yazılım lisanslarını bitiş tarihiyle izle; süresi dolmadan yenile (kesinti/erişim kaybı olmasın).', 'oncelik'=>false, 'kapat'=>false],
    'yedek'  => ['ad'=>'Yedekleme', 'ikon'=>'backup', 'renk'=>'#A855F7', 'baslikPh'=>'Örn: Günlük veritabanı yedeği', 'degerLbl'=>'Konum / boyut', 'sonLbl'=>'Sonraki yedek (planlı)', 'ipucu'=>'Her yedekten sonra kaydet (tarih = alındığı gün). 7 günden eski yedek brifingde uyarı verir. Ara sıra geri yükleme testi yap.', 'oncelik'=>false, 'kapat'=>false],
    'kvkk'   => ['ad'=>'KVKK / Veri Güvenliği', 'ikon'=>'policy', 'renk'=>'#14B8A6', 'baslikPh'=>'Örn: Aydınlatma metni yayınlandı', 'degerLbl'=>'Sorumlu / referans', 'sonLbl'=>'Gözden geçirme tarihi', 'ipucu'=>'Aydınlatma metni, veri işleme envanteri, saklama/imha ve varsa ihlal kayıtları. Kişisel veri (müşteri/personel) işleyen her işletme için zorunlu.', 'oncelik'=>false, 'kapat'=>false],
    'bakim'  => ['ad'=>'Periyodik Bakım', 'ikon'=>'handyman', 'renk'=>'#0EA5E9', 'baslikPh'=>'Örn: POS cihazları genel temizlik/bakım', 'degerLbl'=>'Firma / teknisyen', 'sonLbl'=>'Sonraki bakım tarihi', 'ipucu'=>'Donanım (POS/yazıcı/ağ) periyodik bakım ve güncelleme kayıtları; arızayı önceden önler.', 'oncelik'=>false, 'kapat'=>false],
  ];
  $a = $alanlar[$tip] ?? $alanlar['ariza'];
  $oncRenk = ['yuksek'=>'#F43F5E','orta'=>'#F59E0B','dusuk'=>'#64748B'];
  $oncAd = ['yuksek'=>'Yüksek','orta'=>'Orta','dusuk'=>'Düşük'];
@endphp
<style>
  #itk{max-width:980px;margin:0 auto}
  #itk *{box-sizing:border-box}
  .itk-tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;margin-bottom:14px}
  .itk-tab{flex:none;display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:30px;padding:8px 15px;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap}
  .itk-tab.on{color:#fff;border-color:transparent}
  .itk-tab .material-icons-outlined{font-size:16px}
  .itk-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .itk-msg.ok{background:rgba(16,185,129,.12);color:#047857} .itk-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .itk-ipucu{display:flex;gap:8px;align-items:flex-start;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .itk-ipucu .material-icons-outlined{font-size:16px;flex:none;margin-top:1px}
  .itk-form{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:14px 16px;margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
  .itk-form label{font-size:11px;color:var(--sub);font-weight:700;display:block;margin-bottom:3px}
  .itk-form input,.itk-form select{border:1px solid var(--line);background:var(--card2);color:var(--ink);border-radius:9px;padding:8px 10px;font-size:13px;font-family:inherit}
  .itk-add{color:#fff;border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
  .itk-tab2{background:var(--card);border:1px solid var(--line);border-radius:15px;overflow:hidden}
  .itk-r{display:flex;align-items:center;gap:12px;padding:12px 16px;border-top:1px solid var(--line);font-size:13px}
  .itk-r:first-child{border-top:0}
  .itk-r.kapali{opacity:.55}
  .itk-r .ri{flex:1;min-width:0}.itk-r .ri .t{font-weight:700;color:var(--ink)}.itk-r .ri .d{font-size:11px;color:var(--sub);margin-top:1px}
  .itk-badge{font-size:10px;font-weight:800;border-radius:20px;padding:2px 8px;white-space:nowrap}
  .itk-son{font-size:11.5px;font-weight:800;white-space:nowrap;text-align:right}
  .itk-r form{display:inline;margin:0}
  .itk-r button.ic{border:0;background:transparent;cursor:pointer;color:var(--sub2)} .itk-r button.ic .material-icons-outlined{font-size:18px}
  .itk-kapat{border:1px solid var(--line);background:var(--card2);color:#047857;border-radius:8px;padding:5px 11px;font-weight:800;font-size:11.5px;cursor:pointer}
  .itk-bos{text-align:center;padding:30px;color:var(--sub);font-size:13px}
</style>
<div class="rk-body">
<div id="itk">
  @if(session('ok'))<div class="itk-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="itk-msg er">{{ session('hata') }}</div>@endif

  <div class="itk-tabs">
    @foreach($tipler as $k=>$v)
      <a class="itk-tab {{ $tip===$k?'on':'' }}" @if($tip===$k)style="background:{{ $alanlar[$k]['renk'] }}"@endif href="/it-destek?tip={{ $k }}"><span class="material-icons-outlined">{{ $alanlar[$k]['ikon'] }}</span>{{ $v }}</a>
    @endforeach
  </div>

  <div class="itk-ipucu"><span class="material-icons-outlined" style="color:{{ $a['renk'] }}">lightbulb</span><span>{{ $a['ipucu'] }}</span></div>

  <form class="itk-form" method="POST" action="/it-destek/ekle">
    @csrf<input type="hidden" name="tip" value="{{ $tip }}">
    <div style="flex:1;min-width:180px"><label>Kayıt / başlık</label><input name="baslik" style="width:100%" placeholder="{{ $a['baslikPh'] }}" required></div>
    <div><label>{{ $a['degerLbl'] }}</label><input name="deger" style="width:140px"></div>
    @if($a['oncelik'])
      <div><label>Öncelik</label><select name="oncelik"><option value="orta">Orta</option><option value="yuksek">Yüksek</option><option value="dusuk">Düşük</option></select></div>
    @endif
    <div><label>Tarih</label><input name="tarih" type="date" value="{{ now()->toDateString() }}"></div>
    <div><label>{{ $a['sonLbl'] }}</label><input name="son_tarih" type="date"></div>
    <button class="itk-add" type="submit" style="background:{{ $a['renk'] }}">+ Ekle</button>
  </form>

  <div class="itk-tab2">
    @forelse($liste as $k)
      @php
        $son = $k->son_tarih ? \Carbon\Carbon::parse($k->son_tarih) : null;
        $gecti = $son && $son->isPast(); $yaklasti = $son && !$gecti && $son->diffInDays(now()) <= 15;
        $sonRenk = $gecti ? '#F43F5E' : ($yaklasti ? '#F59E0B' : '#2FBE94');
        $kapali = ($k->durum ?? '') === 'kapandi';
      @endphp
      <div class="itk-r {{ $kapali?'kapali':'' }}">
        <span class="material-icons-outlined" style="font-size:18px;color:{{ $a['renk'] }}">{{ $a['ikon'] }}</span>
        <div class="ri">
          <div class="t">{{ $k->baslik }}
            @if($a['oncelik'] && !$kapali && ($k->oncelik ?? '')!=='')<span class="itk-badge" style="background:color-mix(in srgb,{{ $oncRenk[$k->oncelik]??'#64748B' }} 15%,transparent);color:{{ $oncRenk[$k->oncelik]??'#64748B' }}">{{ $oncAd[$k->oncelik]??$k->oncelik }}</span>@endif
            @if($kapali)<span class="itk-badge" style="background:rgba(16,185,129,.12);color:#047857">✓ Kapandı</span>@endif
          </div>
          <div class="d">{{ $k->tarih ? \Carbon\Carbon::parse($k->tarih)->format('d.m.Y') : '' }}{{ $k->deger ? ' · '.$k->deger : '' }}{{ $k->personel_ad ? ' · '.$k->personel_ad : '' }}{{ $k->not ? ' · '.$k->not : '' }}</div>
        </div>
        @if($son)<span class="itk-son" style="color:{{ $sonRenk }}">{{ $gecti ? 'Doldu' : ($yaklasti?'Yaklaştı':'Geçerli') }}<br>{{ $son->format('d.m.Y') }}</span>@endif
        @if(($a['kapat']??false) && !$kapali)
          <form method="POST" action="/it-destek/durum"><input type="hidden" name="id" value="{{ $k->id }}"><input type="hidden" name="tip" value="{{ $tip }}">@csrf<button type="submit" class="itk-kapat">Kapat</button></form>
        @endif
        <form method="POST" action="/it-destek/sil" onsubmit="return confirm('Silinsin mi?')">@csrf<input type="hidden" name="id" value="{{ $k->id }}"><input type="hidden" name="tip" value="{{ $tip }}"><button type="submit" class="ic"><span class="material-icons-outlined">delete_outline</span></button></form>
      </div>
    @empty
      <div class="itk-bos">Henüz {{ mb_strtolower($a['ad']) }} kaydı yok. Yukarıdan ekle.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'calendar_month')
@section('baslik', 'Vardiya Planlama')
@section('title', 'Vardiya / Shift — İK')

@push('head')
<style>
  #vd{}
  .vd-ust{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .vd-nav{display:flex;align-items:center;gap:6px}
  .vd-ozet{display:flex;gap:10px;margin-left:auto;flex-wrap:wrap}
  .vd-ozet .k{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:8px 14px;box-shadow:var(--golge)}
  .vd-ozet .k .v{font-size:17px;font-weight:800;color:var(--ink)}
  .vd-ozet .k .l{font-size:10.5px;color:var(--sub)}
  .vd-tablo-sar{overflow-x:auto;background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge)}
  .vd-tablo{border-collapse:collapse;width:100%;min-width:900px}
  .vd-tablo th,.vd-tablo td{border:1px solid var(--line);vertical-align:top}
  .vd-tablo thead th{background:var(--card2);font-size:11.5px;font-weight:800;color:var(--sub2);padding:9px 8px;text-align:center;position:sticky;top:0}
  .vd-tablo thead th.bugun{color:var(--mor1)}
  .vd-per{width:150px;min-width:150px;padding:10px;font-weight:700;font-size:13px;background:var(--card2);position:sticky;left:0;z-index:2}
  .vd-per .r{font-size:10.5px;color:var(--sub);font-weight:500}
  .vd-hucre{padding:5px;min-width:112px;height:58px;cursor:pointer;transition:background .12s}
  .vd-hucre:hover{background:color-mix(in srgb,var(--mavi) 7%,transparent)}
  .vd-hucre.bugun{background:color-mix(in srgb,var(--mor1) 4%,transparent)}
  .vd-cip{display:block;background:color-mix(in srgb,var(--mavi) 14%,transparent);border-left:3px solid var(--mavi);border-radius:6px;padding:3px 7px;margin-bottom:3px;font-size:11.5px;font-weight:700;color:var(--ink)}
  .vd-cip .p{font-size:10px;color:var(--sub);font-weight:500}
  .vd-cip.ger{background:color-mix(in srgb,var(--yesil) 15%,transparent);border-left-color:var(--yesil)}
  .vd-oneri{display:block;background:color-mix(in srgb,var(--yesil) 12%,transparent);border:1.5px dashed var(--yesil);border-radius:6px;padding:2px 6px;margin-bottom:3px;font-size:11px;font-weight:700;color:var(--yesil)}
  .vd-ekle{font-size:11px;color:var(--sub);opacity:0;transition:opacity .12s}
  .vd-hucre:hover .vd-ekle{opacity:.7}
  .vd-inp{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="vd">
  @php
    $gunAd = ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
    $hb = $veri['hafta_basi'];
    $onceki = \Carbon\Carbon::parse($hb)->subDays(7)->toDateString();
    $sonraki = \Carbon\Carbon::parse($hb)->addDays(7)->toDateString();
    $buHafta = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->toDateString();
    $bugunStr = \Carbon\Carbon::now()->toDateString();
    $tl = fn($n)=>number_format((float)($n??0),0,',','.').'₺';
    $oneri = $oneri ?? null;
    $oneriIdx = [];
    foreach (($oneri ?? []) as $o) { $oneriIdx[$o['personel_id']][$o['tarih']] = $o; }
    $oneriVar = $oneri !== null && count($oneri);
  @endphp

  {{-- Başlık --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
    <a href="/ik-ozet" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:28px;color:#10B981">calendar_month</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">Vardiya Planlama</div>
      <div class="rk-sub" style="font-size:12px">Haftalık çalışma çizelgesi · ✓ işareti o gün puantajda giriş yapıldığını gösterir</div>
    </div>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="vd-ust">
    <div class="vd-nav">
      <a href="/ik-vardiya?hafta={{ $onceki }}" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">chevron_left</span></a>
      <a href="/ik-vardiya?hafta={{ $buHafta }}" class="rk-btn bos" style="padding:6px 12px;font-size:12.5px">Bu hafta</a>
      <a href="/ik-vardiya?hafta={{ $sonraki }}" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">chevron_right</span></a>
      <span style="font-weight:700;font-size:13.5px;margin-left:6px">{{ \Carbon\Carbon::parse($hb)->format('d.m') }} – {{ \Carbon\Carbon::parse($hb)->addDays(6)->format('d.m.Y') }}</span>
    </div>
    <div class="vd-ozet">
      <div class="k"><div class="v">{{ rtrim(rtrim(number_format($veri['toplam_saat'],1,',','.'),'0'),',') }} s</div><div class="l">Planlanan toplam saat</div></div>
      <div class="k"><div class="v">{{ $tl($veri['toplam_maliyet']) }}</div><div class="l">Tahmini işçilik (bu hafta)</div></div>
      <form method="POST" action="/ik-vardiya/kopyala" style="display:flex;align-items:center" onsubmit="return rkOnayForm(this,'Geçen haftanın tüm vardiyaları bu haftaya kopyalansın mı? (mevcut olanlar korunur)','var(--mavi)','Kopyala')">
        @csrf<input type="hidden" name="hafta" value="{{ $hb }}">
        <button class="rk-btn bos" style="padding:8px 12px;font-size:12.5px"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">content_copy</span> Geçen haftayı kopyala</button>
      </form>
      @unless ($oneriVar)
      <form method="POST" action="/ik-vardiya/ai-planla" style="display:flex;align-items:center">
        @csrf<input type="hidden" name="hafta" value="{{ $hb }}">
        <button class="rk-btn" style="padding:8px 13px;font-size:12.5px;background:linear-gradient(135deg,#7C3AED,#4F46E5)"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">auto_awesome</span> AI ile Planla</button>
      </form>
      @endunless
    </div>
  </div>

  @if ($oneriVar)
  {{-- AI ÖNERİSİ ÖNİZLEME (onay bekliyor) --}}
  <div style="background:linear-gradient(135deg,rgba(124,58,237,.08),rgba(79,70,229,.04));border:1.5px solid rgba(124,58,237,.35);border-radius:14px;padding:14px 16px;margin-bottom:14px">
    <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
      <span class="material-icons-outlined" style="color:var(--mor1)">auto_awesome</span>
      <div style="flex:1;min-width:200px">
        <div style="font-weight:800;font-size:14px;color:var(--ink)">AI {{ count($oneri) }} vardiya önerdi</div>
        <div class="rk-sub" style="font-size:12px">İzinli günler atlandı, herkese 1 gün izin günü verildi, hafta sonu tam kadro. <b>Mevcut vardiyaların korunur.</b> Aşağıda kesikli yeşil hücreler önerilerdir — onaylarsan eklenir.</div>
      </div>
      <form method="POST" action="/ik-vardiya/ai-uygula" style="display:inline" onsubmit="return rkOnayForm(this,'AI planı uygulansın mı? {{ count($oneri) }} vardiya eklenecek (mevcutlar korunur).','var(--yesil)','Evet, uygula')">
        @csrf<input type="hidden" name="hafta" value="{{ $hb }}"><input type="hidden" name="oneri" value='@json($oneri)'>
        <button class="rk-btn" style="background:var(--yesil)"><span class="material-icons-outlined" style="font-size:17px;vertical-align:middle">check</span> Planı Uygula</button>
      </form>
      <a href="/ik-vardiya?hafta={{ $hb }}" class="rk-btn bos">Vazgeç</a>
    </div>
  </div>
  @endif

  <div class="vd-tablo-sar">
    <table class="vd-tablo">
      <thead>
        <tr>
          <th class="vd-per" style="text-align:left">Personel</th>
          @foreach ($veri['gunler'] as $i=>$g)
            @php $gs=$g->toDateString(); @endphp
            <th class="{{ $gs===$bugunStr?'bugun':'' }}">{{ $gunAd[$i] }}<br><span style="font-weight:500">{{ $g->format('d.m') }}</span></th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @forelse ($veri['personeller'] as $p)
        <tr>
          <td class="vd-per">{{ $p->ad }}<div class="r">{{ $p->rol }}</div></td>
          @foreach ($veri['gunler'] as $g)
            @php $gs=$g->toDateString(); $liste=$veri['idx'][$p->id][$gs]??[]; @endphp
            <td class="vd-hucre {{ $gs===$bugunStr?'bugun':'' }}" onclick="vdAc({{ $p->id }},'{{ addslashes($p->ad) }}','{{ $gs }}')">
              @foreach ($liste as $v)
                <span class="vd-cip {{ $v['gercek']?'ger':'' }}" onclick="event.stopPropagation();vdDuzenle({{ $p->id }},'{{ addslashes($p->ad) }}','{{ $gs }}',{{ json_encode($v) }})">
                  {{ $v['gercek']?'✓ ':'' }}{{ $v['bas'] }}–{{ $v['bit'] }}
                  @if($v['pozisyon'])<span class="p">{{ $v['pozisyon'] }}</span>@endif
                </span>
              @endforeach
              @if (!empty($oneriIdx[$p->id][$gs]))
                @php $o=$oneriIdx[$p->id][$gs]; @endphp
                <span class="vd-oneri" title="AI önerisi">✨ {{ $o['bas'] }}–{{ $o['bit'] }}</span>
              @endif
              <span class="vd-ekle">+ ekle</span>
            </td>
          @endforeach
        </tr>
        @empty
        <tr><td colspan="8" style="padding:20px;text-align:center" class="rk-sub">Aktif personel yok. <a href="/personel" style="color:var(--mavi)">Personel ekle →</a></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="rk-sub" style="font-size:11.5px;margin-top:8px">💡 Bir hücreye tıkla → vardiya ekle. Vardiyaya tıkla → düzenle/sil. Tahmini işçilik, personelin maaşından yaklaşık saatlik ücretle hesaplanır (maaş girilmemişse 0).</div>
</div>
</div>

{{-- Vardiya modalı --}}
<div id="vdModal" class="rk-modal-arka" onclick="if(event.target===this)vdKapat()">
  <div class="rk-modal" style="max-width:420px">
    <form method="POST" action="/ik-vardiya/kaydet">
      @csrf
      <input type="hidden" name="hafta" value="{{ $hb }}">
      <input type="hidden" name="id" id="vd_id">
      <input type="hidden" name="personel_id" id="vd_pid">
      <input type="hidden" name="tarih" id="vd_tarih">
      <div class="rk-modal-head"><span class="b" id="vdBaslik">Vardiya Ekle</span><button type="button" class="rk-modal-kapa" onclick="vdKapat()">&times;</button></div>
      <div class="rk-modal-govde">
        <div id="vdKimNe" class="rk-sub" style="font-size:12.5px;margin-bottom:12px"></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div><label class="rk-sub" style="font-size:11px">Başlangıç</label><input type="time" name="baslangic" id="vd_bas" class="vd-inp" required></div>
          <div><label class="rk-sub" style="font-size:11px">Bitiş</label><input type="time" name="bitis" id="vd_bit" class="vd-inp" required></div>
        </div>
        <div style="margin-top:10px"><label class="rk-sub" style="font-size:11px">Pozisyon (opsiyonel)</label>
          <input type="text" name="pozisyon" id="vd_poz" class="vd-inp" list="vdPozList" placeholder="garson / mutfak / kasa ...">
          <datalist id="vdPozList"><option value="Garson"><option value="Mutfak"><option value="Kasa"><option value="Bar"><option value="Komi"><option value="Bulaşık"><option value="Host"></datalist>
        </div>
        <div style="margin-top:10px"><label class="rk-sub" style="font-size:11px">Not (opsiyonel)</label><input type="text" name="not" id="vd_not" class="vd-inp" placeholder="ör. açılış / kapanış"></div>
      </div>
      <div class="rk-modal-foot">
        <button type="button" id="vdSilBtn" class="rk-btn bos" style="color:var(--kirmizi);margin-right:auto;display:none" onclick="vdSil()"><span class="material-icons-outlined" style="font-size:17px;vertical-align:middle">delete</span> Sil</button>
        <button type="button" class="rk-btn bos" onclick="vdKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Kaydet</button>
      </div>
    </form>
  </div>
</div>
{{-- sil formu --}}
<form method="POST" action="/ik-vardiya/sil" id="vdSilForm" style="display:none">@csrf<input type="hidden" name="hafta" value="{{ $hb }}"><input type="hidden" name="id" id="vdSilId"></form>

<script>
  function vdAc(pid, pad, tarih){
    document.getElementById('vdBaslik').textContent='Vardiya Ekle';
    document.getElementById('vd_id').value='';
    document.getElementById('vd_pid').value=pid;
    document.getElementById('vd_tarih').value=tarih;
    document.getElementById('vd_bas').value='09:00';
    document.getElementById('vd_bit').value='17:00';
    document.getElementById('vd_poz').value='';
    document.getElementById('vd_not').value='';
    document.getElementById('vdKimNe').innerHTML='<b>'+pad+'</b> · '+tarih;
    document.getElementById('vdSilBtn').style.display='none';
    document.getElementById('vdModal').classList.add('acik');
  }
  function vdDuzenle(pid, pad, tarih, v){
    document.getElementById('vdBaslik').textContent='Vardiyayı Düzenle';
    document.getElementById('vd_id').value=v.id;
    document.getElementById('vd_pid').value=pid;
    document.getElementById('vd_tarih').value=tarih;
    document.getElementById('vd_bas').value=v.bas;
    document.getElementById('vd_bit').value=v.bit;
    document.getElementById('vd_poz').value=v.pozisyon||'';
    document.getElementById('vd_not').value=v.not||'';
    document.getElementById('vdKimNe').innerHTML='<b>'+pad+'</b> · '+tarih;
    document.getElementById('vdSilId').value=v.id;
    document.getElementById('vdSilBtn').style.display='inline-flex';
    document.getElementById('vdModal').classList.add('acik');
  }
  function vdKapat(){ document.getElementById('vdModal').classList.remove('acik'); }
  function vdSil(){ rkOnay('Bu vardiya silinsin mi?', function(){ document.getElementById('vdSilForm').submit(); }, 'var(--kirmizi)', 'Sil'); }
</script>
@endsection

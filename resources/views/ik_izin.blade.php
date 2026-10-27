@extends('layout.app')
@section('rk_ikon', 'event_available')
@section('baslik', 'İzin Yönetimi')
@section('title', 'İzin Yönetimi — İK')

@push('head')
<style>
  #iz{max-width:1500px}
  .iz-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .iz-h{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 12px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .iz-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;align-items:end}
  .iz-form label{font-size:11px;color:var(--sub);display:block;margin-bottom:3px}
  .iz-form input,.iz-form select{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
  .iz-tablo{width:100%;border-collapse:collapse;font-size:13px}
  .iz-tablo th{text-align:left;font-size:11px;color:var(--sub);font-weight:700;padding:7px 8px;border-bottom:1px solid var(--line);text-transform:uppercase}
  .iz-tablo td{padding:9px 8px;border-bottom:1px solid var(--line)}
  .iz-bar{height:7px;border-radius:5px;background:var(--card2);overflow:hidden;min-width:70px}
  .iz-bar .f{height:100%;background:var(--mavi)}
  .iz-rozet{font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:20px;color:#fff;letter-spacing:.3px}
  .iz-tip{font-size:11px;font-weight:700;padding:2px 8px;border-radius:7px;background:var(--card2);color:var(--sub2)}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="iz">
  @php
    $tipAd = ['yillik'=>'Yıllık','hastalik'=>'Hastalık (rapor)','ucretsiz'=>'Ücretsiz','mazeret'=>'Mazeret','dogum'=>'Doğum/babalık','diger'=>'Diğer'];
    $durumR = ['talep'=>['BEKLİYOR','var(--amber)'],'onay'=>['ONAYLI','var(--yesil)'],'ret'=>['RET','var(--kirmizi)']];
    $pp = null; try { $pp = _posPersonel(); } catch (\Throwable $e) {}
    $yonetici = in_array(($pp->rol ?? ''), ['sahip','mudur']);
  @endphp

  {{-- Başlık --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <a href="/ik-ozet" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:28px;color:#10B981">event_available</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">İzin Yönetimi</div>
      <div class="rk-sub" style="font-size:12px">{{ $buYil }} yılı · yıllık izin hakları İş Kanunu md.53'e göre kıdemden hesaplanır</div>
    </div>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- YENİ İZİN --}}
  <div class="iz-blok">
    <div class="iz-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">add_circle</span> İzin Ekle / Talep Oluştur</div>
    <form method="POST" action="/ik-izin/kaydet" id="izForm">
      @csrf
      <div class="iz-form">
        <div style="grid-column:span 2">
          <label>Personel</label>
          <select name="personel_id" required>
            <option value="">— Seç —</option>
            @foreach ($personeller as $p)
              <option value="{{ $p->id }}">{{ $p->ad }} ({{ $p->rol }})</option>
            @endforeach
          </select>
        </div>
        <div>
          <label>İzin tipi</label>
          <select name="tip">
            @foreach ($tipAd as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
          </select>
        </div>
        <div>
          <label>Başlangıç</label>
          <input type="date" name="baslangic" id="izBas" required>
        </div>
        <div>
          <label>Bitiş</label>
          <input type="date" name="bitis" id="izBit" required>
        </div>
        <div>
          <label>Gün</label>
          <input type="number" step="0.5" min="0.5" name="gun" id="izGun" placeholder="oto">
        </div>
        <div style="grid-column:span 2">
          <label>Açıklama (opsiyonel)</label>
          <input type="text" name="aciklama" placeholder="ör. yıllık izin">
        </div>
        @if ($yonetici)
        <div>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-top:18px"><input type="checkbox" name="durum" value="onay" style="width:auto"> Doğrudan onayla</label>
        </div>
        @endif
        <div>
          <button type="submit" class="rk-btn" style="width:100%"><span class="material-icons-outlined" style="font-size:17px;vertical-align:middle">save</span> Kaydet</button>
        </div>
      </div>
    </form>
  </div>

  {{-- YILLIK İZİN HAKLARI --}}
  <div class="iz-blok">
    <div class="iz-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">beach_access</span> {{ $buYil }} Yıllık İzin Hakları</div>
    @if (count($personeller))
    <table class="iz-tablo">
      <thead><tr><th>Personel</th><th>Hak</th><th>Kullanılan</th><th>Kalan</th><th style="width:40%">Durum</th></tr></thead>
      <tbody>
        @foreach ($personeller as $p)
          @php $h = $haklar[$p->id] ?? ['hak'=>0,'kullanilan'=>0,'kalan'=>0]; $oran = $h['hak']>0 ? min(100,round($h['kullanilan']/$h['hak']*100)) : 0; @endphp
          <tr>
            <td style="font-weight:600"><a href="/ik-sicil?id={{ $p->id }}" style="color:var(--ink)" title="Sicil kartı">{{ $p->ad }}</a></td>
            <td>{{ $h['hak'] ? $h['hak'].' gün' : '—' }}</td>
            <td>{{ rtrim(rtrim(number_format($h['kullanilan'],1,',','.'),'0'),',') }}</td>
            <td><b style="color:{{ $h['kalan']>0?'var(--yesil)':'var(--sub)' }}">{{ $h['hak'] ? rtrim(rtrim(number_format($h['kalan'],1,',','.'),'0'),',').' gün' : '—' }}</b></td>
            <td>
              @if ($h['hak'])
                <div style="display:flex;align-items:center;gap:8px"><div class="iz-bar"><div class="f" style="width:{{ $oran }}%;background:{{ $oran>=90?'var(--kirmizi)':($oran>=70?'var(--amber)':'var(--mavi)') }}"></div></div><span class="rk-sub" style="font-size:11px">%{{ $oran }}</span></div>
              @else
                <span class="rk-sub" style="font-size:11px">1 yılı dolmadı / işe başlama girilmemiş</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <div class="rk-sub" style="font-size:13px">Aktif personel yok.</div>
    @endif
  </div>

  {{-- İZİN KAYITLARI --}}
  <div class="iz-blok">
    <div class="iz-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">list_alt</span> İzin Kayıtları</div>
    @if (count($izinler))
    <table class="iz-tablo">
      <thead><tr><th>Personel</th><th>Tip</th><th>Tarih</th><th>Gün</th><th>Durum</th><th></th></tr></thead>
      <tbody>
        @foreach ($izinler as $iz)
          @php [$dr,$drk] = $durumR[$iz->durum] ?? $durumR['talep']; @endphp
          <tr>
            <td style="font-weight:600">{{ $iz->personel_ad }}</td>
            <td><span class="iz-tip">{{ $tipAd[$iz->tip] ?? $iz->tip }}</span></td>
            <td>{{ \Carbon\Carbon::parse($iz->baslangic)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($iz->bitis)->format('d.m.Y') }}
              @if ($iz->aciklama)<div class="rk-sub" style="font-size:11px">{{ $iz->aciklama }}</div>@endif
              @if ($iz->durum==='ret' && $iz->ret_sebep)<div class="rk-sub" style="font-size:11px;color:var(--kirmizi)">Ret: {{ $iz->ret_sebep }}</div>@endif
            </td>
            <td>{{ rtrim(rtrim(number_format($iz->gun,1,',','.'),'0'),',') }}</td>
            <td><span class="iz-rozet" style="background:{{ $drk }}">{{ $dr }}</span></td>
            <td style="white-space:nowrap;text-align:right">
              @if ($yonetici && $iz->durum==='talep')
                <form method="POST" action="/ik-izin/karar" style="display:inline" onsubmit="return rkOnayForm(this,'{{ $iz->personel_ad }} için izin onaylansın mı?','var(--yesil)','Onayla')">
                  @csrf<input type="hidden" name="id" value="{{ $iz->id }}"><input type="hidden" name="karar" value="onay">
                  <button class="rk-btn bos" style="padding:4px 10px;color:var(--yesil)" title="Onayla"><span class="material-icons-outlined" style="font-size:17px">check</span></button>
                </form>
                <button class="rk-btn bos" style="padding:4px 10px;color:var(--kirmizi)" title="Reddet" onclick="izRet({{ $iz->id }},'{{ addslashes($iz->personel_ad) }}')"><span class="material-icons-outlined" style="font-size:17px">close</span></button>
              @endif
              <form method="POST" action="/ik-izin/sil" style="display:inline" onsubmit="return rkOnayForm(this,'Bu izin kaydı silinsin mi?','var(--kirmizi)','Sil')">
                @csrf<input type="hidden" name="id" value="{{ $iz->id }}">
                <button class="rk-btn bos" style="padding:4px 10px;color:var(--sub)" title="Sil"><span class="material-icons-outlined" style="font-size:16px">delete</span></button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <div class="rk-sub" style="font-size:13px">Henüz izin kaydı yok. Yukarıdan ekleyebilirsin.</div>
    @endif
  </div>

  {{-- AVANS TALEPLERİ (mobil self-servis) --}}
  <div class="iz-blok">
    <div class="iz-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--amber)">payments</span> Avans Talepleri <span class="rk-sub" style="text-transform:none;font-weight:500;margin-left:6px">— personelin uygulamadan gönderdiği talepler</span></div>
    @if (count($avansTalepleri ?? []))
    <table class="iz-tablo">
      <thead><tr><th>Personel</th><th>Tutar</th><th>Açıklama</th><th>Tarih</th><th>Durum</th><th></th></tr></thead>
      <tbody>
        @foreach ($avansTalepleri as $at)
          @php [$dr,$drk] = $durumR[$at->durum] ?? $durumR['talep']; @endphp
          <tr>
            <td style="font-weight:600">{{ $at->personel_ad }}</td>
            <td><b>{{ number_format($at->tutar,0,',','.') }}₺</b></td>
            <td class="rk-sub" style="font-size:12px">{{ $at->aciklama ?: '—' }}</td>
            <td>{{ \Carbon\Carbon::parse($at->created_at)->format('d.m.Y') }}</td>
            <td><span class="iz-rozet" style="background:{{ $drk }}">{{ $dr }}</span></td>
            <td style="white-space:nowrap;text-align:right">
              @if ($yonetici && $at->durum==='talep')
                <form method="POST" action="/ik-avans-karar" style="display:inline" onsubmit="return rkOnayForm(this,'{{ $at->personel_ad }} için {{ number_format($at->tutar,0,',','.') }}₺ avans onaylansın mı? (kasadan çıkış + gider işlenir)','var(--yesil)','Onayla')">
                  @csrf<input type="hidden" name="id" value="{{ $at->id }}"><input type="hidden" name="karar" value="onay">
                  <button class="rk-btn bos" style="padding:4px 10px;color:var(--yesil)" title="Onayla"><span class="material-icons-outlined" style="font-size:17px">check</span></button>
                </form>
                <form method="POST" action="/ik-avans-karar" style="display:inline" onsubmit="return rkOnayForm(this,'Avans talebi reddedilsin mi?','var(--kirmizi)','Reddet')">
                  @csrf<input type="hidden" name="id" value="{{ $at->id }}"><input type="hidden" name="karar" value="ret">
                  <button class="rk-btn bos" style="padding:4px 10px;color:var(--kirmizi)" title="Reddet"><span class="material-icons-outlined" style="font-size:17px">close</span></button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @else
      <div class="rk-sub" style="font-size:13px">Bekleyen avans talebi yok. Personel, mobil uygulamadan avans talebi gönderebilir.</div>
    @endif
  </div>
</div>
</div>

{{-- RET formu (gizli) --}}
<form method="POST" action="/ik-izin/karar" id="izRetForm" style="display:none">@csrf<input type="hidden" name="id" id="izRetId"><input type="hidden" name="karar" value="ret"><input type="hidden" name="ret_sebep" id="izRetSebep"></form>

<script>
  // gün otomatik hesabı (takvim günü)
  (function(){
    var b=document.getElementById('izBas'), s=document.getElementById('izBit'), g=document.getElementById('izGun');
    function hesap(){ if(!b.value||!s.value) return; var d1=new Date(b.value), d2=new Date(s.value); if(d2<d1){g.value='';return;} var fark=Math.round((d2-d1)/86400000)+1; if(fark>0) g.placeholder=fark+' gün (oto)'; }
    b.addEventListener('change',hesap); s.addEventListener('change',hesap);
  })();
  function izRet(id, ad){
    var sebep = prompt(ad+' için izin reddediliyor. Sebep (opsiyonel):', '');
    if(sebep===null) return; // iptal
    document.getElementById('izRetId').value=id;
    document.getElementById('izRetSebep').value=sebep;
    document.getElementById('izRetForm').submit();
  }
</script>
@endsection

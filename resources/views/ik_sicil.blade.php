@extends('layout.app')
@section('rk_ikon', 'folder_shared')
@section('baslik', 'Sicil Kartı')
@section('title', $per->ad . ' — Sicil Kartı')

@push('head')
<style>
  #sc{max-width:1000px}
  .sc-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .sc-h{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;margin:0 0 12px;display:flex;align-items:center;gap:7px;text-transform:uppercase}
  .sc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
  .sc-grid .k{background:var(--card2);border-radius:10px;padding:9px 12px}
  .sc-grid .k .v{font-size:14px;font-weight:700;color:var(--ink)}
  .sc-grid .k .l{font-size:10.5px;color:var(--sub)}
  .sc-row{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid var(--line)}
  .sc-row:last-child{border-bottom:none}
  .sc-inp{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
  .sc-form{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:9px;align-items:end;margin-top:6px}
  .sc-form label{font-size:10.5px;color:var(--sub);display:block;margin-bottom:3px}
  .sc-pill{font-size:10.5px;font-weight:800;padding:2px 9px;border-radius:20px;color:#fff}
  .sc-tip{font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:7px;background:var(--card2);color:var(--sub2)}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="sc">
  @php
    $bugun = \Carbon\Carbon::now()->startOfDay();
    $sktDurum = function($d) use ($bugun) {
      if (!$d) return [null,null];
      $t = \Carbon\Carbon::parse($d)->startOfDay();
      $fark = $bugun->diffInDays($t, false);
      if ($fark < 0) return ['geçti','var(--kirmizi)'];
      if ($fark <= 30) return [$fark.' gün kaldı','var(--amber)'];
      return [null,null];
    };
    $belgeTip = ['sozlesme'=>'Sözleşme','kimlik'=>'Kimlik','diploma'=>'Diploma','saglik'=>'Sağlık Raporu','hijyen'=>'Hijyen/HACCP','sertifika'=>'Sertifika','ehliyet'=>'Ehliyet','adli_sicil'=>'Adli Sicil','diger'=>'Diğer'];
    $harTur = ['avans'=>['Avans','var(--amber)'],'odeme'=>['Ödeme','var(--yesil)'],'prim'=>['Prim','var(--mavi)'],'kesinti'=>['Kesinti / İcra','var(--kirmizi)'],'ek_odeme'=>['Ek Ödeme','var(--mor1)']];
    $durumR = ['talep'=>['Bekliyor','var(--amber)'],'onay'=>['Onaylı','var(--yesil)'],'ret'=>['Ret','var(--kirmizi)']];
    $tl = fn($n)=>number_format((float)($n??0),0,',','.').'₺';
  @endphp

  {{-- Başlık --}}
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <a href="/personel" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:30px;color:#10B981">folder_shared</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">{{ $per->ad }}</div>
      <div class="rk-sub" style="font-size:12px">{{ $per->rol }}{{ $per->departman_unvan ? ' · '.$per->departman_unvan : '' }} · Sicil Kartı</div>
    </div>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- ÖZLÜK ÖZET --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">badge</span> Özlük
      <a href="/personel" class="rk-sub" style="margin-left:auto;font-size:11px;color:var(--mavi);text-transform:none;font-weight:600">Düzenle →</a>
    </div>
    <div class="sc-grid">
      <div class="k"><div class="v">{{ $per->telefon ?: '—' }}</div><div class="l">Telefon</div></div>
      <div class="k"><div class="v">{{ !empty($per->ise_baslama) ? \Carbon\Carbon::parse($per->ise_baslama)->format('d.m.Y') : '—' }}</div><div class="l">İşe Başlama</div></div>
      <div class="k"><div class="v">{{ $kidem ?: '—' }}</div><div class="l">Kıdem</div></div>
      <div class="k"><div class="v">{{ $yillikHak ? $yillikHak.' gün' : '—' }}</div><div class="l">Yıllık İzin Hakkı</div></div>
      <div class="k"><div class="v">{{ !empty($per->dogum_tarihi) ? \Carbon\Carbon::parse($per->dogum_tarihi)->format('d.m.Y') : '—' }}</div><div class="l">Doğum Tarihi</div></div>
      <div class="k"><div class="v">{{ $per->tc ?: '—' }}</div><div class="l">TC Kimlik</div></div>
      <div class="k"><div class="v" style="font-size:12px">{{ $per->iban ?: '—' }}</div><div class="l">IBAN</div></div>
      <div class="k"><div class="v">{{ ($per->acil_kisi ?? '') ?: '—' }}{{ !empty($per->acil_tel) ? ' ('.$per->acil_tel.')' : '' }}</div><div class="l">Acil Durum</div></div>
    </div>
  </div>

  {{-- ZİMMET --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mor1)">inventory_2</span> Zimmet ({{ count($zimmet) }})</div>
    @foreach ($zimmet as $z)
      <div class="sc-row">
        <span class="material-icons-outlined" style="color:var(--sub);font-size:19px">{{ $z->durum==='iade'?'assignment_return':'assignment_ind' }}</span>
        <div style="flex:1"><div style="font-weight:600;font-size:13.5px">{{ $z->urun }} @if($z->adet>1)<span class="sc-tip">×{{ $z->adet }}</span>@endif</div>
          <div class="rk-sub" style="font-size:11px">{{ $z->teslim_tarihi ? \Carbon\Carbon::parse($z->teslim_tarihi)->format('d.m.Y').' teslim' : '' }}{{ $z->durum==='iade' && $z->iade_tarihi ? ' · '.\Carbon\Carbon::parse($z->iade_tarihi)->format('d.m.Y').' iade' : '' }}{{ $z->not ? ' · '.$z->not : '' }}</div>
        </div>
        <span class="sc-pill" style="background:{{ $z->durum==='iade'?'var(--sub)':'var(--yesil)' }}">{{ $z->durum==='iade'?'İADE':'ZİMMETTE' }}</span>
        <form method="POST" action="/ik-sicil/zimmet-durum" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $z->id }}"><input type="hidden" name="personel_id" value="{{ $per->id }}"><button class="rk-btn bos" style="padding:3px 8px;font-size:11px" title="Durumu değiştir"><span class="material-icons-outlined" style="font-size:16px">swap_horiz</span></button></form>
        <form method="POST" action="/ik-sicil/zimmet-sil" style="display:inline" onsubmit="return rkOnayForm(this,'Zimmet kaydı silinsin mi?','var(--kirmizi)','Sil')">@csrf<input type="hidden" name="id" value="{{ $z->id }}"><input type="hidden" name="personel_id" value="{{ $per->id }}"><button class="rk-btn bos" style="padding:3px 8px;color:var(--sub)"><span class="material-icons-outlined" style="font-size:15px">delete</span></button></form>
      </div>
    @endforeach
    <form method="POST" action="/ik-sicil/zimmet" class="sc-form">
      @csrf<input type="hidden" name="personel_id" value="{{ $per->id }}">
      <div style="flex:2"><label>Zimmet (üniforma/telefon/anahtar...)</label><input type="text" name="urun" class="sc-inp" list="zimmetList" required><datalist id="zimmetList"><option value="Üniforma"><option value="Telefon"><option value="Anahtar"><option value="Dolap"><option value="POS cihazı"><option value="Önlük"></datalist></div>
      <div><label>Adet</label><input type="number" name="adet" class="sc-inp" value="1" min="1"></div>
      <div><label>Teslim</label><input type="date" name="teslim_tarihi" class="sc-inp"></div>
      <div><label>Not</label><input type="text" name="not" class="sc-inp"></div>
      <div><button class="rk-btn" style="width:100%"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span> Ekle</button></div>
    </form>
  </div>

  {{-- BELGE & SERTİFİKA (SKT) --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--amber)">description</span> Belge & Sertifika ({{ count($belge) }}) <span class="rk-sub" style="text-transform:none;font-weight:500;margin-left:6px">— son geçerlilik tarihi takip edilir</span></div>
    @foreach ($belge as $b)
      @php [$skt,$sktR] = $sktDurum($b->son_gecerlilik); @endphp
      <div class="sc-row">
        <span class="sc-tip">{{ $belgeTip[$b->tip] ?? $b->tip }}</span>
        <div style="flex:1"><div style="font-weight:600;font-size:13.5px">{{ $b->baslik }}</div>
          <div class="rk-sub" style="font-size:11px">{{ $b->belge_tarihi ? \Carbon\Carbon::parse($b->belge_tarihi)->format('d.m.Y') : '' }}{{ $b->son_gecerlilik ? ' → son: '.\Carbon\Carbon::parse($b->son_gecerlilik)->format('d.m.Y') : '' }}{{ $b->not ? ' · '.$b->not : '' }}</div>
        </div>
        @if ($skt)<span class="sc-pill" style="background:{{ $sktR }}">{{ $skt }}</span>@endif
        @if ($b->dosya)<a href="/ik-belge-indir?id={{ $b->id }}" class="rk-btn bos" style="padding:3px 8px" title="İndir"><span class="material-icons-outlined" style="font-size:16px;color:var(--mavi)">download</span></a>@endif
        <form method="POST" action="/ik-sicil/belge-sil" style="display:inline" onsubmit="return rkOnayForm(this,'Belge silinsin mi?','var(--kirmizi)','Sil')">@csrf<input type="hidden" name="id" value="{{ $b->id }}"><input type="hidden" name="personel_id" value="{{ $per->id }}"><button class="rk-btn bos" style="padding:3px 8px;color:var(--sub)"><span class="material-icons-outlined" style="font-size:15px">delete</span></button></form>
      </div>
    @endforeach
    <form method="POST" action="/ik-sicil/belge" class="sc-form" enctype="multipart/form-data">
      @csrf<input type="hidden" name="personel_id" value="{{ $per->id }}">
      <div><label>Tip</label><select name="tip" class="sc-inp">@foreach($belgeTip as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
      <div style="flex:2"><label>Başlık</label><input type="text" name="baslik" class="sc-inp" placeholder="ör. Hijyen Eğitim Sertifikası" required></div>
      <div><label>Belge tarihi</label><input type="date" name="belge_tarihi" class="sc-inp"></div>
      <div><label>Son geçerlilik</label><input type="date" name="son_gecerlilik" class="sc-inp"></div>
      <div><label>Dosya (ops.)</label><input type="file" name="dosya" class="sc-inp" style="padding:5px"></div>
      <div><button class="rk-btn" style="width:100%"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">upload_file</span> Ekle</button></div>
    </form>
  </div>

  {{-- EĞİTİM --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--yesil)">school</span> Eğitim ({{ count($egitim) }})</div>
    @foreach ($egitim as $e)
      @php [$skt,$sktR] = $sktDurum($e->son_gecerlilik); @endphp
      <div class="sc-row">
        <span class="material-icons-outlined" style="color:var(--sub);font-size:19px">menu_book</span>
        <div style="flex:1"><div style="font-weight:600;font-size:13.5px">{{ $e->konu }}</div>
          <div class="rk-sub" style="font-size:11px">{{ $e->egitim_tarihi ? \Carbon\Carbon::parse($e->egitim_tarihi)->format('d.m.Y') : '' }}{{ $e->veren ? ' · '.$e->veren : '' }}{{ $e->son_gecerlilik ? ' → yenileme: '.\Carbon\Carbon::parse($e->son_gecerlilik)->format('d.m.Y') : '' }}</div>
        </div>
        @if ($skt)<span class="sc-pill" style="background:{{ $sktR }}">{{ $skt }}</span>@endif
        <form method="POST" action="/ik-sicil/egitim-sil" style="display:inline" onsubmit="return rkOnayForm(this,'Eğitim kaydı silinsin mi?','var(--kirmizi)','Sil')">@csrf<input type="hidden" name="id" value="{{ $e->id }}"><input type="hidden" name="personel_id" value="{{ $per->id }}"><button class="rk-btn bos" style="padding:3px 8px;color:var(--sub)"><span class="material-icons-outlined" style="font-size:15px">delete</span></button></form>
      </div>
    @endforeach
    <form method="POST" action="/ik-sicil/egitim" class="sc-form">
      @csrf<input type="hidden" name="personel_id" value="{{ $per->id }}">
      <div style="flex:2"><label>Eğitim konusu</label><input type="text" name="konu" class="sc-inp" placeholder="ör. İş Sağlığı ve Güvenliği" required></div>
      <div><label>Tarih</label><input type="date" name="egitim_tarihi" class="sc-inp"></div>
      <div><label>Veren</label><input type="text" name="veren" class="sc-inp"></div>
      <div><label>Yenileme tarihi</label><input type="date" name="son_gecerlilik" class="sc-inp"></div>
      <div><button class="rk-btn" style="width:100%"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span> Ekle</button></div>
    </form>
  </div>

  {{-- AVANS / İCRA / KESİNTİ GEÇMİŞİ --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--kirmizi)">payments</span> Avans / İcra / Kesinti Geçmişi
      <a href="/personel" class="rk-sub" style="margin-left:auto;font-size:11px;color:var(--mavi);text-transform:none;font-weight:600">Yeni işlem (Personel & Maaş) →</a>
    </div>
    @forelse ($hareket as $h)
      @php [$ht,$hr] = $harTur[$h->tur] ?? [$h->tur,'var(--sub)']; @endphp
      <div class="sc-row">
        <span class="sc-pill" style="background:{{ $hr }}">{{ $ht }}</span>
        <div style="flex:1"><div class="rk-sub" style="font-size:12px">{{ $h->aciklama ?: '—' }}</div></div>
        <span class="rk-sub" style="font-size:11.5px">{{ \Carbon\Carbon::parse($h->tarih)->format('d.m.Y') }}</span>
        <b style="font-size:13.5px">{{ $tl($h->tutar) }}</b>
      </div>
    @empty
      <div class="rk-sub" style="font-size:12.5px">Henüz avans/kesinti kaydı yok. İcra kesintisi için Personel & Maaş'tan "Kesinti" ekleyip açıklamaya "İcra" yazabilirsiniz.</div>
    @endforelse
  </div>

  {{-- İZİN GEÇMİŞİ --}}
  <div class="sc-blok">
    <div class="sc-h"><span class="material-icons-outlined" style="font-size:17px;color:var(--mavi)">event_available</span> İzin Geçmişi
      <a href="/ik-izin" class="rk-sub" style="margin-left:auto;font-size:11px;color:var(--mavi);text-transform:none;font-weight:600">İzin Yönetimi →</a>
    </div>
    @forelse ($izinler as $iz)
      @php [$dr,$drk] = $durumR[$iz->durum] ?? $durumR['talep']; @endphp
      <div class="sc-row">
        <span class="sc-tip">{{ ucfirst($iz->tip) }}</span>
        <div style="flex:1"><div class="rk-sub" style="font-size:12px">{{ \Carbon\Carbon::parse($iz->baslangic)->format('d.m.Y') }} – {{ \Carbon\Carbon::parse($iz->bitis)->format('d.m.Y') }} · {{ rtrim(rtrim(number_format($iz->gun,1,',','.'),'0'),',') }} gün</div></div>
        <span class="sc-pill" style="background:{{ $drk }}">{{ $dr }}</span>
      </div>
    @empty
      <div class="rk-sub" style="font-size:12.5px">İzin kaydı yok.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

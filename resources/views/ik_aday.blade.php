@extends('layout.app')
@section('rk_ikon', 'how_to_reg')
@section('baslik', 'İşe Alım / Aday Havuzu')
@section('title', 'Aday Havuzu — İK')

@push('head')
<style>
  #ad{}
  .ad-blok{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--golge);padding:16px 18px;margin-bottom:14px}
  .ad-cip{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:20px;font-size:12.5px;font-weight:700;background:var(--card2);color:var(--sub2);border:1px solid var(--line);text-decoration:none}
  .ad-cip.aktif{background:var(--mor1);color:#fff;border-color:var(--mor1)}
  .ad-cip .n{font-size:11px;opacity:.85}
  .ad-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:var(--golge);padding:14px 16px;margin-bottom:10px;display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap}
  .ad-inp{width:100%;padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px}
  .ad-lbl{font-size:11px;color:var(--sub);display:block;margin-bottom:3px}
  .ad-durum{font-size:10.5px;font-weight:800;padding:3px 10px;border-radius:20px;color:#fff}
  .ad-link{display:flex;align-items:center;gap:8px;background:var(--card2);border:1px dashed var(--line);border-radius:10px;padding:9px 12px;font-size:13px}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="ad">
  @php
    $durumRenk = ['yeni'=>'var(--mavi)','inceleme'=>'var(--amber)','mulakat'=>'var(--mor1)','teklif'=>'#0EA5E9','islendi'=>'var(--yesil)','red'=>'var(--sub)'];
    $basvuruUrl = url('/kariyer/'.$subeId);
    $pozList = ['Garson','Komi','Mutfak','Aşçı','Kasa','Bar','Host','Bulaşık','Temizlik','Müdür'];
  @endphp

  <div style="display:flex;align-items:center;gap:14px;margin-bottom:14px">
    <a href="/ik-ozet" class="rk-btn bos" style="padding:6px 10px"><span class="material-icons-outlined" style="font-size:18px">arrow_back</span></a>
    <span class="material-icons-outlined" style="font-size:28px;color:#10B981">how_to_reg</span>
    <div style="flex:1">
      <div style="font-size:20px;font-weight:800;color:var(--ink)">İşe Alım / Aday Havuzu</div>
      <div class="rk-sub" style="font-size:12px">Başvuru linkini paylaş, adaylar otomatik havuza düşsün</div>
    </div>
    <button class="rk-btn" style="background:var(--mor1)" onclick="document.getElementById('adEkleModal').classList.add('acik')"><span class="material-icons-outlined" style="font-size:17px;vertical-align:middle">person_add</span> Aday Ekle</button>
  </div>

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  {{-- Başvuru linki --}}
  <div class="ad-blok">
    <div style="font-size:12px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.3px;margin-bottom:8px">📣 Genel Başvuru Linki</div>
    <div class="ad-link">
      <span class="material-icons-outlined" style="color:var(--mavi);font-size:18px">link</span>
      <span style="flex:1;word-break:break-all" id="basvuruUrl">{{ $basvuruUrl }}</span>
      <button class="rk-btn bos" style="padding:5px 10px;font-size:12px" onclick="navigator.clipboard.writeText('{{ $basvuruUrl }}').then(()=>{this.innerHTML='✓ Kopyalandı'})"><span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">content_copy</span> Kopyala</button>
      <a href="{{ $basvuruUrl }}" target="_blank" class="rk-btn bos" style="padding:5px 10px;font-size:12px"><span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">open_in_new</span> Önizle</a>
    </div>
    <div class="rk-sub" style="font-size:11px;margin-top:6px">Bu linki iş ilanlarında, sosyal medyada veya kapıdaki QR'da paylaş. Dolduran herkes aşağıdaki havuza "Yeni" olarak düşer.</div>
  </div>

  {{-- Durum filtreleri --}}
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
    <a href="/ik-aday" class="ad-cip {{ !$filtre?'aktif':'' }}">Tümü <span class="n">{{ array_sum($sayac->toArray()) }}</span></a>
    @foreach ($durumlar as $k=>$v)
      <a href="/ik-aday?durum={{ $k }}" class="ad-cip {{ $filtre===$k?'aktif':'' }}">{{ $v }} <span class="n">{{ $sayac[$k] ?? 0 }}</span></a>
    @endforeach
  </div>

  {{-- Aday listesi --}}
  @forelse ($adaylar as $a)
  <div class="ad-kart">
    <div style="flex:1;min-width:200px">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span style="font-weight:800;font-size:15px">{{ $a->ad }}</span>
        <span class="ad-durum" style="background:{{ $durumRenk[$a->durum]??'var(--sub)' }}">{{ $durumlar[$a->durum]??$a->durum }}</span>
        @if ($a->kaynak==='web')<span class="rk-sub" style="font-size:10.5px">🌐 web</span>@else<span class="rk-sub" style="font-size:10.5px">✍️ elden</span>@endif
      </div>
      <div class="rk-sub" style="font-size:12px;margin-top:4px">
        {{ $a->pozisyon ?: 'Pozisyon belirtilmemiş' }}
        @if($a->telefon) · 📞 {{ $a->telefon }}@endif
        @if($a->email) · ✉️ {{ $a->email }}@endif
        · {{ \Carbon\Carbon::parse($a->created_at)->format('d.m.Y') }}
      </div>
      @if ($a->notlar)<div class="rk-sub" style="font-size:12px;margin-top:5px;padding:7px 10px;background:var(--card2);border-radius:8px">{{ $a->notlar }}</div>@endif
      <div style="display:flex;gap:7px;margin-top:9px;flex-wrap:wrap;align-items:center">
        {{-- durum değiştir --}}
        <form method="POST" action="/ik-aday/guncelle" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $a->id }}">
          <select name="durum" class="ad-inp" style="width:auto;padding:5px 8px;font-size:12px" onchange="this.form.submit()">
            @foreach ($durumlar as $k=>$v)<option value="{{ $k }}" {{ $a->durum===$k?'selected':'' }}>{{ $v }}</option>@endforeach
          </select>
        </form>
        {{-- puan --}}
        <form method="POST" action="/ik-aday/guncelle" style="display:inline">@csrf<input type="hidden" name="id" value="{{ $a->id }}">
          <select name="puan" class="ad-inp" style="width:auto;padding:5px 8px;font-size:12px" onchange="this.form.submit()" title="Değerlendirme">
            <option value="">★ puan</option>
            @for ($i=1;$i<=5;$i++)<option value="{{ $i }}" {{ (int)$a->puan===$i?'selected':'' }}>{{ str_repeat('★',$i) }}</option>@endfor
          </select>
        </form>
        @if ($a->cv_dosya)<a href="/ik-cv-indir?id={{ $a->id }}" class="rk-btn bos" style="padding:5px 10px;font-size:12px"><span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">description</span> CV</a>@endif
        @if ($a->durum!=='islendi')
          <button class="rk-btn" style="padding:5px 12px;font-size:12px;background:var(--yesil)" onclick="iseAlAc({{ $a->id }},'{{ addslashes($a->ad) }}')"><span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">how_to_reg</span> İşe Al</button>
        @else
          <a href="/ik-sicil?id={{ $a->personel_id }}" class="rk-btn bos" style="padding:5px 12px;font-size:12px;color:var(--yesil)"><span class="material-icons-outlined" style="font-size:15px;vertical-align:middle">folder_shared</span> Sicil Kartı</a>
        @endif
        <form method="POST" action="/ik-aday/sil" style="display:inline;margin-left:auto" onsubmit="return rkOnayForm(this,'{{ addslashes($a->ad) }} adayı silinsin mi?','var(--kirmizi)','Sil')">@csrf<input type="hidden" name="id" value="{{ $a->id }}"><button class="rk-btn bos" style="padding:5px 9px;color:var(--sub)"><span class="material-icons-outlined" style="font-size:15px">delete</span></button></form>
      </div>
    </div>
  </div>
  @empty
  <div class="ad-blok" style="text-align:center;padding:30px">
    <span class="material-icons-outlined" style="font-size:40px;color:var(--sub);opacity:.5">inbox</span>
    <div class="rk-sub" style="font-size:13.5px;margin-top:8px">{{ $filtre ? 'Bu durumda aday yok.' : 'Henüz aday yok. Başvuru linkini paylaş veya "Aday Ekle" ile elle ekle.' }}</div>
  </div>
  @endforelse
</div>
</div>

{{-- Aday Ekle modalı --}}
<div id="adEkleModal" class="rk-modal-arka" onclick="if(event.target===this)this.classList.remove('acik')">
  <div class="rk-modal" style="max-width:440px">
    <form method="POST" action="/ik-aday/ekle">@csrf
      <div class="rk-modal-head"><span class="b">Aday Ekle (elden)</span><button type="button" class="rk-modal-kapa" onclick="document.getElementById('adEkleModal').classList.remove('acik')">&times;</button></div>
      <div class="rk-modal-govde">
        <label class="ad-lbl">Ad Soyad</label><input type="text" name="ad" class="ad-inp" required>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">
          <div><label class="ad-lbl">Telefon</label><input type="text" name="telefon" class="ad-inp" inputmode="tel"></div>
          <div><label class="ad-lbl">Pozisyon</label><input type="text" name="pozisyon" class="ad-inp" list="pozL"><datalist id="pozL">@foreach($pozList as $p)<option value="{{ $p }}">@endforeach</datalist></div>
        </div>
        <label class="ad-lbl" style="margin-top:8px">E-posta</label><input type="email" name="email" class="ad-inp">
        <label class="ad-lbl" style="margin-top:8px">Not</label><input type="text" name="notlar" class="ad-inp" placeholder="ör. referans: ...">
      </div>
      <div class="rk-modal-foot"><button type="button" class="rk-btn bos" onclick="document.getElementById('adEkleModal').classList.remove('acik')">Vazgeç</button><button type="submit" class="rk-btn" style="background:var(--mor1)">Ekle</button></div>
    </form>
  </div>
</div>

{{-- İşe Al modalı --}}
<div id="iseAlModal" class="rk-modal-arka" onclick="if(event.target===this)this.classList.remove('acik')">
  <div class="rk-modal" style="max-width:420px">
    <form method="POST" action="/ik-aday/ise-al">@csrf<input type="hidden" name="id" id="ia_id">
      <div class="rk-modal-head"><span class="b" id="iaBaslik">İşe Al</span><button type="button" class="rk-modal-kapa" onclick="document.getElementById('iseAlModal').classList.remove('acik')">&times;</button></div>
      <div class="rk-modal-govde">
        <div class="rk-sub" style="font-size:12.5px;margin-bottom:12px">Adaydan bir <b>personel kartı</b> oluşturulacak. POS'a bu PIN ile giriş yapacak; sonra sicil kartından özlük bilgilerini tamamlarsın.</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div><label class="ad-lbl">Rol</label><select name="rol" class="ad-inp"><option value="garson">Garson</option><option value="mutfak">Mutfak</option><option value="kasa">Kasa</option><option value="satinalmaci">Satın Almacı</option><option value="mudur">Müdür</option></select></div>
          <div><label class="ad-lbl">PIN (en az 4 hane)</label><input type="text" name="pin" class="ad-inp" inputmode="numeric" maxlength="8" required></div>
        </div>
        <label class="ad-lbl" style="margin-top:8px">İşe başlama tarihi</label><input type="date" name="ise_baslama" class="ad-inp" value="{{ date('Y-m-d') }}">
      </div>
      <div class="rk-modal-foot"><button type="button" class="rk-btn bos" onclick="document.getElementById('iseAlModal').classList.remove('acik')">Vazgeç</button><button type="submit" class="rk-btn" style="background:var(--yesil)">İşe Al</button></div>
    </form>
  </div>
</div>

<script>
  function iseAlAc(id, ad){
    document.getElementById('ia_id').value=id;
    document.getElementById('iaBaslik').textContent=ad+' — İşe Al';
    document.getElementById('iseAlModal').classList.add('acik');
  }
</script>
@endsection

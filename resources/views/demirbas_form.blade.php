{{-- Demirbaş yeni/düzenle alanları. $d = mevcut kayıt ya da null; $personeller, $lokasyonlar, $kategoriler, $durumlar --}}
@php $v = fn ($k) => old($k, $d->$k ?? ''); @endphp
@if ($d)<input type="hidden" name="id" value="{{ $d->id }}"><input type="hidden" name="donus" value="kart">@endif
<div class="bolum"><span class="ms">inventory_2</span> Ne?</div>
<label>Ad *</label><input name="ad" required value="{{ $v('ad') }}" placeholder="örn. Sanayi tipi konveksiyonel fırın">
<div class="uc">
  <div><label>Kategori</label><input name="kategori" list="dmKatL" value="{{ $v('kategori') }}" placeholder="Mutfak ekipmanı"></div>
  <div><label>Marka</label><input name="marka" value="{{ $v('marka') }}"></div>
  <div><label>Model</label><input name="model" value="{{ $v('model') }}"></div>
</div>
<div class="uc">
  <div><label>Seri no</label><input name="seri_no" value="{{ $v('seri_no') }}"></div>
  <div><label>Adet</label><input name="adet" type="number" min="1" value="{{ $d ? ($d->adet ?? 1) : old('adet', 1) }}"></div>
  <div><label>Barkod</label><input name="barkod" value="{{ $v('barkod') }}" placeholder="Boş bırak, sistem versin"></div>
</div>
<div class="ipucu">Üzerinde eski bir etiket ya da üretici barkodu varsa okutup "Barkod"a yazabilirsin. Boş bırakırsan <b>DM000123</b> gibi bir numara verilir. Aynı türden birden çok parçayı ayrı ayrı takip etmek istiyorsan her birini ayrı kaydet.</div>
<div class="bolum"><span class="ms">place</span> Nerede, kimde?</div>
<div class="iz">
  <div><label>Konum</label><input name="lokasyon" list="dmLokL" value="{{ $v('lokasyon') }}" placeholder="Ana depo, mutfak, oda 4…"></div>
  <div><label>Zimmet (kimde)</label><select name="zimmet_personel_id"><option value="">— Zimmetsiz —</option>@foreach ($personeller as $p)<option value="{{ $p->id }}" {{ (int) $v('zimmet_personel_id') === (int) $p->id ? 'selected' : '' }}>{{ $p->ad }}</option>@endforeach</select></div>
</div>
@if ($d)
<label>Durum</label><select name="durum">@foreach ($durumlar as $k => $a)<option value="{{ $k }}" {{ $d->durum === $k ? 'selected' : '' }}>{{ $a }}</option>@endforeach</select>
@endif
<div class="bolum"><span class="ms">receipt_long</span> Alış ve garanti</div>
<div class="iz">
  <div><label>Tedarikçi</label><input name="tedarikci" value="{{ $v('tedarikci') }}"></div>
  <div><label>Alış bedeli (₺, birim)</label><input name="alis_bedel" inputmode="decimal" value="{{ $d && (float) $d->alis_bedel ? rtrim(rtrim(number_format((float) $d->alis_bedel, 2, '.', ''), '0'), '.') : old('alis_bedel') }}"></div>
  <div><label>Alış tarihi</label><input type="date" name="alis_tarih" value="{{ $v('alis_tarih') }}"></div>
  <div><label>Garanti bitişi</label><input type="date" name="garanti_bitis" value="{{ $v('garanti_bitis') }}"></div>
</div>
<label>Not</label><input name="not" value="{{ $v('not') }}">
<datalist id="dmLokL">@foreach ($lokasyonlar as $l)<option value="{{ $l }}">@endforeach</datalist>
<datalist id="dmKatL">@foreach ($kategoriler as $l)<option value="{{ $l }}">@endforeach @foreach (['Mutfak ekipmanı', 'Soğutma', 'Mobilya', 'Elektronik / IT', 'Tekstil', 'Bahçe / dış alan', 'Temizlik ekipmanı'] as $l)@if (!$kategoriler->contains($l))<option value="{{ $l }}">@endif @endforeach</datalist>

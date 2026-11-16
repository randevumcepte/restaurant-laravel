{{-- Etiketli ürün ekle / düzenle penceresi. $liste, $menu, $sablonlar, $ayar --}}
@php
  $emVeri = $liste->mapWithKeys(fn ($u) => [$u->id => ['id' => $u->id, 'urun_id' => $u->urun_id, 'ad' => $u->ad, 'kategori' => $u->kategori, 'birim' => $u->birim,
    'fiyat' => $u->fiyat, 'net_miktar' => $u->net_miktar !== null ? rtrim(rtrim(number_format((float) $u->net_miktar, 3, '.', ''), '0'), '.') : null, 'net_birim' => $u->net_birim, 'dara_g' => (float) $u->dara_g ?: null,
    'barkod' => $u->barkod, 'plu' => $u->plu, 'raf_omru_gun' => $u->raf_omru_gun, 'icindekiler' => $u->icindekiler, 'alerjen' => $u->alerjen, 'saklama' => $u->saklama,
    'uretici' => $u->uretici, 'uretim_yeri' => $u->uretim_yeri, 'yerli' => (bool) $u->yerli, 'turler' => $u->tur_liste, 'sablon_id' => $u->sablon_id,
    'indirimli_fiyat' => $u->indirimli_fiyat, 'indirim_bitis' => $u->indirim_bitis, 'aktif' => (bool) $u->aktif]]);
  $menuVeri = $menu->mapWithKeys(fn ($m) => [$m->id => ['ad' => $m->ad, 'fiyat' => (float) $m->fiyat]]);
  $eskiPop = session('em_pop');
@endphp
<div class="dmp-ort" id="emPop" onclick="if(event.target===this)emKapat()">
  <form class="dmp" method="POST" action="/barkod-etiket/kaydet" autocomplete="off" id="emForm">
    @csrf <input type="hidden" name="id" id="emId">
    <div class="bas"><span class="ms">local_offer</span><h3 id="emBaslik">Yeni Ürün</h3><button type="button" class="kapat" onclick="emKapat()"><span class="ms">close</span></button></div>
    <div class="gov">
      <div class="bolum"><span class="ms">inventory</span> Ürün</div>
      <label>Menüdeki bir ürüne bağla (kasada satılacaksa)</label>
      <select name="urun_id" id="emUrunId" onchange="emMenuSec()">
        <option value="">— Menüde değil (yalnız etiket: markete giden ürün vb.) —</option>
        @foreach ($menu as $m)<option value="{{ $m->id }}">{{ $m->ad }} — {{ number_format((float) $m->fiyat, 2, ',', '.') }} ₺</option>@endforeach
      </select>
      <div class="ipucu" id="emBagIpucu">Bağlarsan fiyat menüden gelir, barkod kasada okutulunca bu ürün satılır.</div>
      <div class="iz">
        <div><label>Etikette görünecek ad *</label><input name="ad" id="emAd" required maxlength="150" placeholder="örn. Dana Kıyma / Ev Yapımı Nar Ekşisi"></div>
        <div><label>Kategori</label><input name="kategori" id="emKategori" maxlength="60" placeholder="Kasap, şarküteri, konserve…"></div>
      </div>

      <div class="bolum"><span class="ms">local_offer</span> Etiket türü</div>
      <div class="secenek" style="flex-direction:row;flex-wrap:wrap">
        @foreach (_emTurler() as $k => $a)
          <label style="flex:1;min-width:150px"><input type="checkbox" name="turler[]" value="{{ $k }}" class="emTur" onchange="emTurDegisti()"><span>{{ $a }}</span></label>
        @endforeach
      </div>

      <div class="bolum"><span class="ms">payments</span> Fiyat</div>
      <div class="uc">
        <div><label>Fiyat neye göre?</label><select name="birim" id="emBirim" onchange="emTurDegisti()">@foreach (_emBirimler() as $k => $a)<option value="{{ $k }}">{{ $a }}</option>@endforeach</select></div>
        <div><label id="emFiyatEt">Satış fiyatı (₺, KDV dahil)</label><input name="fiyat" id="emFiyat" inputmode="decimal" placeholder="Menüden gelir"></div>
        <div><label>İndirimli fiyat (₺)</label><input name="indirimli_fiyat" id="emIndirim" inputmode="decimal" placeholder="Boş = indirim yok"></div>
      </div>
      <div class="iz">
        <div><label>İndirim bitişi</label><input type="date" name="indirim_bitis" id="emIndBitis"></div>
        <div id="emNetKutu"><label>Paketteki net miktar</label><div style="display:flex;gap:6px"><input name="net_miktar" id="emNet" inputmode="decimal" placeholder="500"><select name="net_birim" id="emNetBirim" style="width:90px">@foreach (_emNetBirimler() as $k => $a)<option value="{{ $k }}">{{ $a }}</option>@endforeach</select></div></div>
      </div>
      <div class="ipucu">Raf kartında birim fiyat (1 kg / 1 lt) net miktardan otomatik hesaplanır. İndirimde eski fiyat olarak son 30 günün en düşük fiyatı basılır.</div>

      <div class="bolum"><span class="ms">qr_code</span> Barkod</div>
      <div class="iz">
        <div><label>Barkod</label><input name="barkod" id="emBarkod" maxlength="32" placeholder="869… resmi barkodu okut ya da boş bırak"></div>
        <div id="emPluKutu"><label>PLU (terazideki ürün no)</label><input name="plu" id="emPlu" type="number" min="1" placeholder="Boş = sıradaki numara"></div>
      </div>
      <label style="display:flex;gap:8px;align-items:center" id="emIcUretKutu"><input type="checkbox" name="barkod_islem" value="ic_uret" id="emIcUret" style="width:18px;height:18px;accent-color:#6366F1"> Barkodu yok — mağaza içi barkod (2 ile başlayan) üret</label>
      <div class="ipucu">Markete giden ürünün GS1 Türkiye'den alınmış <b>869</b> barkodunu yaz. Mağaza içi barkod yalnız senin kasanda geçerlidir. Tartılı ürünün barkodunu terazi basar; burada yalnız PLU gerekir.</div>

      <div class="bolum"><span class="ms">event</span> Tarih, içerik, üretici</div>
      <div class="uc">
        <div><label>Raf ömrü (gün)</label><input name="raf_omru_gun" id="emOmur" type="number" min="1" max="3650" placeholder="SKT = üretim + bu gün"></div>
        <div id="emDaraKutu"><label>Dara (g)</label><input name="dara_g" id="emDara" inputmode="decimal" placeholder="Kap/ambalaj ağırlığı"></div>
        <div><label>Üretim yeri</label><input name="uretim_yeri" id="emUretimYeri" maxlength="100" placeholder="İzmir / Türkiye"></div>
      </div>
      <label>İçindekiler</label><textarea name="icindekiler" id="emIcindekiler" rows="2" maxlength="3000" placeholder="Nar suyu, tuz…"></textarea>
      <div class="iz">
        <div><label>Alerjen uyarısı</label><input name="alerjen" id="emAlerjen" maxlength="255" placeholder="Gluten, süt içerir"></div>
        <div><label>Saklama koşulu</label><input name="saklama" id="emSaklama" maxlength="150" placeholder="+4 °C'de saklayınız"></div>
      </div>
      <label>Üretici (firma ve adres)</label><input name="uretici" id="emUretici" maxlength="255" placeholder="{{ $ayar['uretici_varsayilan'] ?: 'Boş = Ayarlar\'daki firma bilgisi' }}">
      <div class="iz">
        <div><label>Kendi tasarımı (isteğe bağlı)</label><select name="sablon_id" id="emSablon"><option value="">— Yok —</option>@foreach ($sablonlar as $s)<option value="{{ $s->id }}">{{ $s->ad }}</option>@endforeach</select></div>
        <div style="display:flex;flex-direction:column;justify-content:flex-end;gap:8px;padding-bottom:4px">
          <label style="display:flex;gap:8px;align-items:center;margin:0"><input type="checkbox" name="yerli" value="1" id="emYerli" style="width:18px;height:18px;accent-color:#6366F1"> Yerli üretim</label>
          <label style="display:flex;gap:8px;align-items:center;margin:0"><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" id="emAktif" checked style="width:18px;height:18px;accent-color:#6366F1"> Aktif (listelerde görünür)</label>
        </div>
      </div>
    </div>
    <div class="alt">
      <button type="button" class="db tehlike" id="emSilBtn" onclick="emSil()" style="margin-right:auto;display:none"><span class="ms">delete</span> Sil</button>
      <button type="button" class="db ikincil" onclick="emKapat()">Vazgeç</button>
      <button type="submit" class="db ana"><span class="ms">save</span> Kaydet</button>
    </div>
  </form>
  <form method="POST" action="/barkod-etiket/sil" id="emSilForm" style="display:none">@csrf<input type="hidden" name="id" id="emSilId"></form>
</div>
<script>
var EM = {!! json_encode($emVeri, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}, EM_MENU = {!! json_encode($menuVeri, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!};
(function(){ var p=document.getElementById('emPop'); if(p) document.body.appendChild(p); })();
function emDeger(id,v){ var e=document.getElementById(id); if(!e) return; if(e.type==='checkbox') e.checked=!!v; else e.value = (v===null||v===undefined) ? '' : v; }
function emDoldur(u){
  u = u || {};
  emDeger('emId', u.id); emDeger('emUrunId', u.urun_id); emDeger('emAd', u.ad); emDeger('emKategori', u.kategori); emDeger('emBirim', u.birim || 'adet');
  emDeger('emFiyat', u.fiyat); emDeger('emIndirim', u.indirimli_fiyat); emDeger('emIndBitis', u.indirim_bitis); emDeger('emNet', u.net_miktar); emDeger('emNetBirim', u.net_birim || 'g');
  emDeger('emBarkod', u.barkod); emDeger('emPlu', u.plu); emDeger('emOmur', u.raf_omru_gun); emDeger('emDara', u.dara_g); emDeger('emUretimYeri', u.uretim_yeri);
  emDeger('emIcindekiler', u.icindekiler); emDeger('emAlerjen', u.alerjen); emDeger('emSaklama', u.saklama); emDeger('emUretici', u.uretici); emDeger('emSablon', u.sablon_id);
  emDeger('emYerli', u.yerli); emDeger('emAktif', u.id ? u.aktif : true); emDeger('emIcUret', false);
  var t = u.turler || ['paket','raf']; document.querySelectorAll('.emTur').forEach(function(c){ c.checked = t.indexOf(c.value) > -1; });
  document.getElementById('emBaslik').textContent = u.id ? (u.ad + ' — düzenle') : 'Yeni Ürün';
  document.getElementById('emSilBtn').style.display = u.id ? '' : 'none';
  emMenuSec(true); emTurDegisti();
}
function emYeni(){ emDoldur(null); document.getElementById('emPop').classList.add('ac'); setTimeout(function(){ document.getElementById('emUrunId').focus(); }, 60); }
function emDuzenle(id){ emDoldur(EM[id]); document.getElementById('emPop').classList.add('ac'); }
function emKapat(){ document.getElementById('emPop').classList.remove('ac'); }
function emSil(){ var id=document.getElementById('emId').value; if(!id || !confirm('Bu ürün etiket listesinden silinsin mi? Menüdeki ürün silinmez; basım geçmişi korunur.')) return; document.getElementById('emSilId').value=id; document.getElementById('emSilForm').submit(); }
function emMenuSec(ilk){
  var id=document.getElementById('emUrunId').value, m=EM_MENU[id], f=document.getElementById('emFiyat'), ad=document.getElementById('emAd');
  f.placeholder = m ? ('Boş = menü fiyatı (' + m.fiyat.toLocaleString('tr-TR',{minimumFractionDigits:2}) + ' ₺)') : 'Zorunlu';
  if (m && !ilk && !ad.value) ad.value = m.ad;
  document.getElementById('emBagIpucu').textContent = m ? 'Fiyat boş kalırsa menü fiyatı kullanılır; menüde fiyat değişince raf kartı "yenilenecek" listesine düşer.' : 'Menüde olmayan ürün kasada satılamaz; yalnız etiketi basılır (ör. markete teslim edilen ürün).';
}
function emTurDegisti(){
  var tarti = document.querySelector('.emTur[value=tarti]').checked, b=document.getElementById('emBirim');
  if (tarti && b.value !== 'kg') b.value = 'kg';
  document.getElementById('emPluKutu').style.display = tarti ? '' : 'none';
  document.getElementById('emDaraKutu').style.display = (tarti || b.value !== 'adet') ? '' : 'none';
  document.getElementById('emNetKutu').style.display = b.value === 'adet' ? '' : 'none';
  document.getElementById('emFiyatEt').textContent = b.value === 'kg' ? 'Kilogram fiyatı (₺, KDV dahil)' : (b.value === 'lt' ? 'Litre fiyatı (₺, KDV dahil)' : 'Satış fiyatı (₺, KDV dahil)');
}
function emTur(t){ document.querySelectorAll('#dm .cip[data-t]').forEach(function(c){ c.classList.toggle('on', c.dataset.t===t); }); window._emT=t; emFiltre(); }
function emFiltre(){
  var q=((document.getElementById('emAra')||{}).value||'').toLocaleLowerCase('tr'), t=window._emT||'', n=0;
  document.querySelectorAll('#emListe .em-sat').forEach(function(s){ var ok=(!q||s.dataset.ara.indexOf(q)>-1)&&(!t||s.dataset.tur.split(',').indexOf(t)>-1); s.style.display=ok?'':'none'; if(ok) n++; });
  var y=document.getElementById('emYok'); if(y) y.style.display=n?'none':'block';
}
document.addEventListener('keydown',function(e){ if(e.key==='Escape') emKapat(); });
@if ($eskiPop)
  (function(){ var k={!! json_encode((string) $eskiPop) !!}; var id=k.indexOf('duz')===0?k.slice(3):null; if(id&&EM[id]) emDuzenle(id); else emYeni();
    var o={!! json_encode(old(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}; ['ad','kategori','birim','fiyat','indirimli_fiyat','indirim_bitis','net_miktar','net_birim','barkod','plu','raf_omru_gun','dara_g','uretim_yeri','icindekiler','alerjen','saklama','uretici','sablon_id','urun_id'].forEach(function(k){ var e=document.querySelector('#emForm [name='+k+']'); if(e && o[k]!==undefined && o[k]!==null) e.value=o[k]; });
    if(o.turler) document.querySelectorAll('.emTur').forEach(function(c){ c.checked=o.turler.indexOf(c.value)>-1; }); emMenuSec(true); emTurDegisti(); })();
@endif
if (document.getElementById('emAra') && document.getElementById('emAra').value) emFiltre();
</script>

@extends('layout.app')
@section('rk_ikon', 'shopping_cart_checkout')
@section('rk_geri', '/satinalma-siparis')
@section('baslik', $s ? ('Sipariş ' . $s->siparis_no) : 'Sipariş Detayı')
@section('title', $s ? ('Sipariş ' . $s->siparis_no) : 'Sipariş Detayı')

@push('head')
<style>
#sd{}
.sd-ust{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.sd-durum{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;font-size:11.5px;font-weight:800;letter-spacing:.3px;color:#fff}
.sd-durum.taslak{background:#64748b}.sd-durum.gonderildi{background:#4F46E5}.sd-durum.onaylandi{background:#10B981}.sd-durum.kismi{background:#F59E0B}.sd-durum.tamamlandi{background:#059669}.sd-durum.iptal{background:#F43F5E}
.sd-aksi{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap}
.sd-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:11px;border:0;cursor:pointer;font-size:13px;font-weight:700;transition:transform .14s,box-shadow .14s;text-decoration:none}
.sd-btn:hover{transform:translateY(-1px)}
.sd-btn .material-icons-outlined{font-size:16px}
.sd-kaydet{background:var(--mor1);color:#fff;box-shadow:0 4px 12px rgba(124,58,237,.3)}
.sd-gonder{background:linear-gradient(135deg,#10B981,#059669);color:#fff;box-shadow:0 4px 12px rgba(16,185,129,.3)}
.sd-iptal{background:var(--card);color:var(--kirmizi);border:1.5px solid color-mix(in srgb,var(--kirmizi) 40%,transparent)}
.sd-bos{background:var(--card);color:var(--sub2);border:1.5px solid var(--line)}

/* Hero özet şerit */
.sd-hero{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:12px;margin-bottom:16px}
@media(max-width:860px){.sd-hero{grid-template-columns:1fr}}
.sd-kart{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 16px;box-shadow:var(--golge)}
.sd-kart h4{font-size:11px;font-weight:800;color:var(--sub2);letter-spacing:.5px;margin:0 0 8px;text-transform:uppercase;display:flex;align-items:center;gap:6px}
.sd-kart h4 .material-icons-outlined{font-size:14px}
.sd-ted-ad{font-size:16px;font-weight:800;color:var(--ink);line-height:1.2}
.sd-ted-alt{font-size:12px;color:var(--sub);margin-top:4px;display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.sd-istat{font-size:11.5px;color:var(--sub2);margin-top:6px;display:flex;gap:14px;flex-wrap:wrap;padding-top:6px;border-top:1px dashed var(--line)}
.sd-istat b{color:var(--mor1);font-weight:800}
.sd-select,.sd-inp{width:100%;padding:9px 12px;border:1.5px solid var(--line);border-radius:10px;background:var(--card);color:var(--ink);font-size:14px;font-weight:600;transition:border-color .13s;box-sizing:border-box}
.sd-select:focus,.sd-inp:focus{outline:0;border-color:var(--mor1)}

/* Kalemler tablosu */
.sd-kalemler{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;box-shadow:var(--golge);margin-bottom:14px}
.sd-kalem-head{display:flex;align-items:center;gap:10px;margin-bottom:10px}
.sd-kalem-head h3{flex:1;font-size:14px;font-weight:800;color:var(--ink);margin:0;display:flex;align-items:center;gap:8px}
.sd-kalem-head .say{background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1);padding:2px 11px;border-radius:999px;font-size:11.5px;font-weight:800}
.sd-kalem-head button.ekle{background:var(--mor1);color:#fff;border:0;padding:8px 14px;border-radius:10px;font-size:12.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;box-shadow:0 3px 10px rgba(124,58,237,.25)}
.sd-kalem-head button.ekle:hover{opacity:.9}
.sd-kalem-head button.ekle .material-icons-outlined{font-size:16px}

.sd-sat{background:var(--card2);border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:9px;transition:box-shadow .13s,border-color .13s}
.sd-sat:hover{border-color:color-mix(in srgb,var(--mor1) 25%,var(--line))}
.sd-sat .ust{display:grid;grid-template-columns:2.2fr 1fr 1.2fr 1fr 36px;gap:10px;align-items:end}
@media(max-width:720px){.sd-sat .ust{grid-template-columns:1fr 1fr;gap:8px}}
.sd-sat .alan label{font-size:10.5px;color:var(--sub);font-weight:600;display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px}
.sd-sat .sil-btn{background:color-mix(in srgb,var(--kirmizi) 10%,transparent);color:var(--kirmizi);border:1px solid color-mix(in srgb,var(--kirmizi) 30%,transparent);width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background .13s}
.sd-sat .sil-btn:hover{background:var(--kirmizi);color:#fff}
.sd-sat .toplam-metin{text-align:right;font-weight:800;color:var(--ink);font-size:15px;letter-spacing:-.3px}
.sd-sat .toplam-metin .lbl{font-size:10.5px;color:var(--sub);font-weight:600;display:block;margin-bottom:1px;text-transform:uppercase}

/* Bilgi şeridi (son alış + mevcut stok) */
.sd-sat .bilgi-serit{display:flex;gap:14px;flex-wrap:wrap;margin-top:9px;padding-top:9px;border-top:1px dashed var(--line);font-size:11.5px;color:var(--sub2)}
.sd-sat .bilgi-serit .ch{display:inline-flex;align-items:center;gap:5px}
.sd-sat .bilgi-serit .ch .material-icons-outlined{font-size:14px;color:var(--sub)}
.sd-sat .bilgi-serit .ch b{color:var(--ink);font-weight:700}
.sd-sat .bilgi-serit .yakin{color:#F43F5E}
.sd-sat .bilgi-serit .yakin b{color:#F43F5E}
.sd-sat .bilgi-serit .ucuz-sinyal{background:color-mix(in srgb,#10B981 10%,transparent);padding:3px 9px;border-radius:999px}
.sd-sat .bilgi-serit .ucuz-sinyal b{color:#10B981}

/* Not + teslim */
.sd-notlar{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:14px 16px;box-shadow:var(--golge);margin-bottom:14px;display:grid;grid-template-columns:1fr 2fr;gap:12px}
@media(max-width:720px){.sd-notlar{grid-template-columns:1fr}}
.sd-notlar label{font-size:10.5px;color:var(--sub);font-weight:600;display:block;margin-bottom:4px;text-transform:uppercase;letter-spacing:.3px}

.sd-sonuc{padding:12px 16px;border-radius:12px;font-size:13.5px;font-weight:600;line-height:1.5;margin-top:12px;display:none}
.sd-sonuc.ok{background:color-mix(in srgb,#10B981 12%,transparent);color:#047857;border-left:3px solid #10B981;display:block}
.sd-sonuc.hata{background:color-mix(in srgb,var(--kirmizi) 10%,transparent);color:var(--kirmizi);border-left:3px solid var(--kirmizi);display:block}

.sd-salt-okunur{font-size:12.5px;color:var(--sub);padding:10px 14px;background:var(--card2);border-left:3px solid var(--amber);border-radius:9px;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.sd-salt-okunur .material-icons-outlined{color:var(--amber);font-size:17px}
</style>
@endpush

@section('content')
<div class="rk-body">
@php
  $tl = fn($v) => number_format((float) round((float) $v, 2), 2, ',', '.') . '₺';
  $mik = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $durumAd = ['taslak' => 'Taslak', 'gonderildi' => 'Gönderildi', 'onaylandi' => 'Onaylandı', 'kismi' => 'Kısmi Teslim', 'tamamlandi' => 'Tamamlandı', 'iptal' => 'İptal'];
  $duzenlenebilir = $s && $s->durum === 'taslak';
@endphp

@if (!$s)
  <div style="text-align:center;padding:50px;color:var(--sub)">Sipariş bulunamadı.</div>
@else
<div id="sd" data-id="{{ $s->id }}">

  {{-- Üst şerit: durum + aksiyonlar --}}
  <div class="sd-ust">
    <span class="sd-durum {{ $s->durum }}">
      <span class="material-icons-outlined" style="font-size:14px">
        @if ($s->durum === 'taslak')edit
        @elseif($s->durum === 'gonderildi')send
        @elseif(in_array($s->durum, ['onaylandi', 'tamamlandi']))check_circle
        @elseif($s->durum === 'kismi')pending
        @else cancel
        @endif
      </span>
      {{ $durumAd[$s->durum] ?? $s->durum }}
    </span>

    <div class="sd-aksi">
      @if ($duzenlenebilir)
        <button class="sd-btn sd-kaydet" onclick="sdKaydet()"><span class="material-icons-outlined">save</span> Kaydet</button>
        <button class="sd-btn sd-gonder" onclick="sdGonder()"><span class="material-icons-outlined">send</span> Tedarikçiye Gönder (WA + Mail)</button>
        <button class="sd-btn sd-iptal" onclick="sdDurum('iptal')"><span class="material-icons-outlined">cancel</span> İptal Et</button>
      @elseif ($s->durum === 'gonderildi')
        <button class="sd-btn sd-bos" onclick="sdDurum('tamamlandi')"><span class="material-icons-outlined">check_circle</span> Tamamlandı İşaretle</button>
        <a href="/mal-kabul?siparis_id={{ $s->id }}" class="sd-btn sd-kaydet"><span class="material-icons-outlined">inventory</span> Mal Kabul Oluştur</a>
      @endif
    </div>
  </div>

  @if (!$duzenlenebilir)
    <div class="sd-salt-okunur"><span class="material-icons-outlined">lock</span> Bu sipariş {{ strtolower($durumAd[$s->durum] ?? '') }} durumunda — artık düzenlenemez. Sadece durumunu değiştirebilirsin.</div>
  @endif

  <div class="sd-sonuc" id="sdSonuc"></div>

  {{-- HERO: 3 kart --}}
  <div class="sd-hero">
    <div class="sd-kart">
      <h4><span class="material-icons-outlined">local_shipping</span> Tedarikçi</h4>
      @if ($duzenlenebilir)
        <select id="sdTedarikci" class="sd-select">
          <option value="">— Tedarikçi seç —</option>
          @foreach ($tedarikciler as $t)
            <option value="{{ $t->id }}" @if($s->tedarikci_id==$t->id) selected @endif>{{ $t->ad }}</option>
          @endforeach
        </select>
      @else
        <div class="sd-ted-ad">{{ $tedarikci->ad ?? '—' }}</div>
      @endif
      <div class="sd-ted-alt">
        @if ($tedarikci)
          @if ($tedarikci->telefon)<span>📞 {{ $tedarikci->telefon }}</span>@endif
          @if (!empty($tedarikci->eposta))<span>📧 {{ $tedarikci->eposta }}</span>@endif
        @endif
      </div>
      @if ($tedarikciIstat)
        <div class="sd-istat">
          <span>Son 30 gün: <b>{{ $tedarikciIstat['siparis_say'] }}</b> sipariş</span>
          <span>Harcama: <b>{{ $tl($tedarikciIstat['toplam_tutar']) }}</b></span>
        </div>
      @endif
    </div>

    <div class="sd-kart">
      <h4><span class="material-icons-outlined">event</span> Beklenen Teslim</h4>
      @if ($duzenlenebilir)
        <input type="date" id="sdTeslim" class="sd-inp" value="{{ $s->beklenen_teslim ? \Carbon\Carbon::parse($s->beklenen_teslim)->format('Y-m-d') : '' }}">
      @else
        <div style="font-size:16px;font-weight:800;color:var(--ink)">{{ $s->beklenen_teslim ? \Carbon\Carbon::parse($s->beklenen_teslim)->format('d.m.Y') : 'Belirtilmemiş' }}</div>
      @endif
      <div style="font-size:11.5px;color:var(--sub);margin-top:6px">Oluşturma: {{ \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') }}</div>
    </div>

    <div class="sd-kart" style="background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0">
      <h4 style="color:rgba(255,255,255,.85)"><span class="material-icons-outlined">payments</span> Genel Toplam</h4>
      <div style="font-size:28px;font-weight:900;color:#fff;letter-spacing:-.5px;line-height:1" id="sdGenelTop">{{ $tl($s->genel_toplam) }}</div>
      <div style="font-size:11.5px;color:rgba(255,255,255,.78);margin-top:6px">{{ $kalemler->count() }} kalem</div>
    </div>
  </div>

  {{-- KALEMLER (editable) --}}
  <div class="sd-kalemler">
    <div class="sd-kalem-head">
      <h3><span class="material-icons-outlined" style="font-size:19px;color:var(--mor1)">list_alt</span> Kalemler <span class="say" id="sdKalemSay2">{{ $kalemler->count() }}</span></h3>
      @if ($duzenlenebilir)
        <button class="ekle" onclick="sdKalemEkle()"><span class="material-icons-outlined">add</span> Kalem Ekle</button>
      @endif
    </div>

    <div id="sdKalemList">
      @foreach ($kalemler as $k)
        <div class="sd-sat" data-ix="{{ $loop->index }}">
          <div class="ust">
            <div class="alan">
              <label>Malzeme</label>
              @if ($duzenlenebilir)
                <select class="sd-select sd-malzeme" onchange="sdHesapla(this)">
                  <option value="">— seç —</option>
                  @foreach ($malzemeler as $m)
                    <option value="{{ $m->id }}" data-birim="{{ $m->temel_birim_id }}" @if($k->malzeme_id==$m->id) selected @endif>{{ $m->ad }}</option>
                  @endforeach
                </select>
              @else
                <div style="font-weight:700;color:var(--ink);padding:9px 0">{{ $k->malzeme }}</div>
              @endif
            </div>
            <div class="alan">
              <label>Miktar</label>
              @if ($duzenlenebilir)
                <input type="number" step="0.001" min="0" class="sd-inp sd-miktar" value="{{ (float) $k->miktar }}" oninput="sdHesapla(this)">
              @else
                <div style="font-weight:700;padding:9px 0">{{ $mik($k->miktar) }} {{ $k->birim }}</div>
              @endif
            </div>
            <div class="alan">
              <label>Birim Fiyat (₺)</label>
              @if ($duzenlenebilir)
                <input type="number" step="0.01" min="0" class="sd-inp sd-fiyat" value="{{ (float) $k->birim_fiyat }}" oninput="sdHesapla(this)">
              @else
                <div style="font-weight:700;padding:9px 0">{{ $tl($k->birim_fiyat) }}</div>
              @endif
            </div>
            <div class="toplam-metin">
              <span class="lbl">Satır Toplam</span>
              <span class="deger">{{ $tl($k->satir_toplam ?? 0) }}</span>
            </div>
            @if ($duzenlenebilir)
              <button class="sil-btn" title="Sil" onclick="sdKalemSil(this)"><span class="material-icons-outlined" style="font-size:17px">delete_outline</span></button>
            @else<div></div>@endif
          </div>

          <div class="bilgi-serit">
            @if ($k->son_alis)
              <span class="ch ucuz-sinyal"><span class="material-icons-outlined">history</span>Son alış: <b>{{ $mik($k->son_alis['miktar']) }} {{ $k->birim }}</b> · <b>{{ $tl($k->son_alis['fiyat']) }}</b> · {{ \Carbon\Carbon::parse($k->son_alis['tarih'])->diffForHumans() }}@if($k->son_alis['tedarikci']) ({{ $k->son_alis['tedarikci'] }})@endif</span>
            @else
              <span class="ch"><span class="material-icons-outlined">history</span>Son alış kaydı yok</span>
            @endif
            @php $kritikDurum = $k->kritik_stok > 0 && $k->mevcut_stok <= $k->kritik_stok; @endphp
            <span class="ch @if($kritikDurum)yakin @endif">
              <span class="material-icons-outlined">inventory_2</span>
              Mevcut: <b>{{ $mik($k->mevcut_stok) }}</b> · Kritik: <b>{{ $mik($k->kritik_stok) }}</b>
              @if ($kritikDurum)<span style="font-weight:800;color:#F43F5E">⚠ kritik</span>@endif
            </span>
          </div>
        </div>
      @endforeach
    </div>

    @if ($kalemler->isEmpty())
      <div style="text-align:center;padding:30px 20px;color:var(--sub);font-size:13.5px">
        <span class="material-icons-outlined" style="font-size:36px;color:var(--sub2);display:block;margin-bottom:6px">inbox</span>
        Henüz kalem yok. Yukarıdan "Kalem Ekle" ile başla.
        @if (($duzenlenebilir ?? false) && $s->durum === 'taslak')
          <div style="margin-top:14px">
            <form method="POST" action="/satinalma-siparis/ai-doldur" style="display:inline"
                  onsubmit="return rkOnayForm(this, 'Kritik stok listesindeki tüm malzemeler bu siparişe kalem olarak eklenecek. Devam?', 'var(--mor1)', 'Yükle')">
              @csrf
              <input type="hidden" name="id" value="{{ $s->id }}">
              <button type="submit"
                      style="display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;border:0;border-radius:11px;padding:10px 18px;font-size:13.5px;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(124,58,237,.3)">
                <span class="material-icons-outlined" style="font-size:17px">auto_awesome</span>
                Kritik Stoktan Yükle
              </button>
            </form>
            <div style="font-size:11.5px;color:var(--sub2);margin-top:7px">AI kritik stok listesindeki malzemeleri önerilerle (ve varsa tedarikçi fiyatlarıyla) ekler</div>
          </div>
        @endif
      </div>
    @endif
  </div>

  <div class="sd-notlar">
    <div>
      <label>Oluşturulma</label>
      <div style="font-size:13.5px;color:var(--ink);line-height:1.4;padding:9px 0">
        {{ \Carbon\Carbon::parse($s->created_at)->format('d.m.Y H:i') }}
        <div style="font-size:11.5px;color:var(--sub);margin-top:4px">Sipariş No: <b>{{ $s->siparis_no }}</b></div>
      </div>
    </div>
    <div>
      <label>Not (opsiyonel)</label>
      @if ($duzenlenebilir)
        <textarea id="sdNotlar" class="sd-inp" rows="2" placeholder="Örn: Acil, bu hafta içinde teslim lütfen" style="resize:vertical">{{ $s->notlar }}</textarea>
      @else
        <div style="font-size:13.5px;color:var(--ink);padding:9px 0;line-height:1.5">{{ $s->notlar ?: '—' }}</div>
      @endif
    </div>
  </div>

</div>
@endif
</div>

@push('scripts')
<script>
const SD_ID = {{ $s->id ?? 0 }};
const SD_DUZ = {{ $duzenlenebilir ? 'true' : 'false' }};
const SD_MALZ = {!! $duzenlenebilir ? $malzemeler->map(fn($m)=>['id'=>$m->id,'ad'=>$m->ad,'birim'=>$m->temel_birim_id])->toJson() : '[]' !!};

function sdSonuc(txt, ok){
  const el=document.getElementById('sdSonuc');
  el.textContent=txt; el.className='sd-sonuc '+(ok?'ok':'hata');
  if(ok) setTimeout(()=>{el.className='sd-sonuc';}, 4500);
}

function sdHesapla(input){
  const sat=input.closest('.sd-sat'); if(!sat) return;
  const mik=parseFloat(sat.querySelector('.sd-miktar')?.value)||0;
  const fiy=parseFloat(sat.querySelector('.sd-fiyat')?.value)||0;
  const top=mik*fiy;
  const el=sat.querySelector('.toplam-metin .deger');
  if(el) el.textContent=top.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
  let gt=0;
  document.querySelectorAll('.sd-sat').forEach(s=>{
    const m=parseFloat(s.querySelector('.sd-miktar')?.value)||0;
    const f=parseFloat(s.querySelector('.sd-fiyat')?.value)||0;
    gt+=m*f;
  });
  const gtEl=document.getElementById('sdGenelTop');
  if(gtEl) gtEl.textContent=gt.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
}

function sdKalemEkle(){
  const list=document.getElementById('sdKalemList');
  const bos=list.querySelector('[style*="text-align:center"]'); if(bos) bos.remove();
  const opts=SD_MALZ.map(m=>'<option value="'+m.id+'" data-birim="'+m.birim+'">'+m.ad.replace(/</g,'&lt;')+'</option>').join('');
  const html = `<div class="sd-sat">
    <div class="ust">
      <div class="alan"><label>Malzeme</label><select class="sd-select sd-malzeme" onchange="sdHesapla(this)"><option value="">— seç —</option>${opts}</select></div>
      <div class="alan"><label>Miktar</label><input type="number" step="0.001" min="0" class="sd-inp sd-miktar" value="0" oninput="sdHesapla(this)"></div>
      <div class="alan"><label>Birim Fiyat (₺)</label><input type="number" step="0.01" min="0" class="sd-inp sd-fiyat" value="0" oninput="sdHesapla(this)"></div>
      <div class="toplam-metin"><span class="lbl">Satır Toplam</span><span class="deger">0,00₺</span></div>
      <button class="sil-btn" onclick="sdKalemSil(this)"><span class="material-icons-outlined" style="font-size:17px">delete_outline</span></button>
    </div>
    <div class="bilgi-serit"><span class="ch">Yeni kalem — kaydettikten sonra son alış ve mevcut stok bilgisi görünür.</span></div>
  </div>`;
  list.insertAdjacentHTML('beforeend', html);
  const say=list.querySelectorAll('.sd-sat').length;
  const sayEl=document.getElementById('sdKalemSay2'); if(sayEl) sayEl.textContent=say;
}

function sdKalemSil(btn){
  if(!confirm('Bu kalemi sil?')) return;
  const sat=btn.closest('.sd-sat');
  sat.remove();
  const list=document.getElementById('sdKalemList');
  const say=list.querySelectorAll('.sd-sat').length;
  const sayEl=document.getElementById('sdKalemSay2'); if(sayEl) sayEl.textContent=say;
  // Toplamı güncelle
  let gt=0;
  document.querySelectorAll('.sd-sat').forEach(s=>{
    const m=parseFloat(s.querySelector('.sd-miktar')?.value)||0;
    const f=parseFloat(s.querySelector('.sd-fiyat')?.value)||0;
    gt+=m*f;
  });
  const gtEl=document.getElementById('sdGenelTop');
  if(gtEl) gtEl.textContent=gt.toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2})+'₺';
}

async function sdKaydet(){
  const ted=parseInt(document.getElementById('sdTedarikci')?.value)||0;
  const teslim=document.getElementById('sdTeslim')?.value||null;
  const notlar=document.getElementById('sdNotlar')?.value||'';
  const kalemler=[];
  document.querySelectorAll('.sd-sat').forEach(s=>{
    const mSel=s.querySelector('.sd-malzeme');
    const mid=parseInt(mSel?.value)||0;
    const birim=parseInt(mSel?.selectedOptions[0]?.dataset.birim)||0;
    const mik=parseFloat(s.querySelector('.sd-miktar')?.value)||0;
    const fiy=parseFloat(s.querySelector('.sd-fiyat')?.value)||0;
    if(mid && mik>0) kalemler.push({malzeme_id:mid, alis_birim_id:birim, miktar:mik, birim_fiyat:fiy});
  });
  try{
    const r=await fetch('/api/satinalma-siparis/kaydet',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'},body:JSON.stringify({id:SD_ID,tedarikci_id:ted,beklenen_teslim:teslim,notlar,kalemler})});
    const j=await r.json();
    if(j.ok){ sdSonuc('✓ '+j.mesaj,true); } else { sdSonuc('⚠ '+(j.hata||'Hata'),false); }
    return j.ok;
  }catch(e){ sdSonuc('⚠ Bağlantı hatası',false); return false; }
}

async function sdGonder(){
  if(!confirm('Sipariş tedarikçiye WhatsApp + Mail ile gönderilecek. Devam et?')) return;
  const kayitTamam = await sdKaydet();
  if(!kayitTamam) return;
  try{
    const r=await fetch('/api/satinalma-siparis/gonder',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'},body:JSON.stringify({id:SD_ID})});
    const j=await r.json();
    if(j.ok){ sdSonuc(j.mesaj,true); setTimeout(()=>location.reload(),1800); } else { sdSonuc('⚠ '+(j.hata||'Gönderilemedi'),false); }
  }catch(e){ sdSonuc('⚠ Bağlantı hatası',false); }
}

async function sdDurum(d){
  const ad={taslak:'taslağa döndür',gonderildi:'gönderildi işaretle',onaylandi:'onayla',tamamlandi:'tamamlandı işaretle',iptal:'iptal et',kismi:'kısmi teslim olarak işaretle'};
  if(!confirm('Siparişi '+(ad[d]||d)+'?')) return;
  try{
    const r=await fetch('/api/satinalma-siparis/durum',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'},body:JSON.stringify({id:SD_ID,durum:d})});
    const j=await r.json();
    if(j.ok) location.reload(); else sdSonuc('⚠ '+(j.hata||'Hata'),false);
  }catch(e){ sdSonuc('⚠ Bağlantı hatası',false); }
}
</script>
@endpush

@endsection

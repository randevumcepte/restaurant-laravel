@extends('layout.app')
@section('title', 'İndirimler')
@section('rk_ikon', 'local_offer')
@section('baslik', '🎫 İndirimler')

@push('head')
<style>
#ind{--mor:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #ind{--card:#1b2235;--card2:#161d2e;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}

/* ---- Bilgi bandı ---- */
#ind .info-band{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 16px;
  color:var(--sub);font-size:12.5px;line-height:1.45;margin-bottom:14px}

/* ---- Kart ---- */
#ind .k-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:10px}
#ind .kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 10px 12px 14px;position:relative}
#ind .kart.aktif{border-color:rgba(34,197,94,.55)}
#ind .kart.pasif{opacity:.88}

/* ---- Kart üst satır ---- */
#ind .kart-ust{display:flex;align-items:flex-start;gap:10px}
#ind .kart-ikon{font-size:24px;line-height:1.3;flex-shrink:0}
#ind .kart-bilgi{flex:1;min-width:0}
#ind .kart-ad{color:var(--ink);font-size:15px;font-weight:700;line-height:1.25}
#ind .kart-tip{color:var(--sub2);font-size:11.5px}

/* ---- Toggle (checkbox) ---- */
#ind .tog-wrap{position:relative;display:inline-flex;align-items:center;cursor:pointer;flex-shrink:0;margin-top:2px}
#ind .tog-wrap input{position:absolute;opacity:0;width:0;height:0}
#ind .tog-track{width:44px;height:24px;border-radius:12px;background:#cbd5e1;transition:background .2s}
#ind .tog-wrap input:checked~.tog-track{background:#22c55e}
#ind .tog-dot{position:absolute;left:2px;top:2px;width:20px;height:20px;background:#fff;border-radius:50%;transition:transform .2s;pointer-events:none}
#ind .tog-wrap input:checked~.tog-track~.tog-dot,
#ind .tog-wrap input:checked+.tog-track+.tog-dot{transform:translateX(20px)}

/* ---- Değer ---- */
#ind .kart-deger{color:#7C6CF0;font-size:22px;font-weight:800;margin:4px 0 0}

/* ---- Çip ---- */
#ind .cips{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
#ind .cip{border:1px solid var(--line);background:var(--card2);border-radius:20px;padding:3px 8px;color:var(--sub2);font-size:11.5px}

/* ---- Butonlar ---- */
#ind .kart-alt{display:flex;align-items:center;margin-top:6px}
#ind .btn-duzenle{display:flex;align-items:center;gap:5px;background:none;border:0;color:var(--sub);font-size:13.5px;cursor:pointer;padding:6px 4px}
#ind .btn-duzenle .material-icons-outlined{font-size:17px}
#ind .btn-sil{margin-left:auto;background:none;border:0;color:#F43F5E;cursor:pointer;padding:6px 6px}
#ind .btn-sil .material-icons-outlined{font-size:20px}

/* ---- Yeni İndirim FAB ---- */
#ind .fab{position:fixed;bottom:24px;right:24px;display:flex;align-items:center;gap:8px;
  background:#4F46E5;color:#fff;border:0;border-radius:18px;padding:14px 20px;font-size:15px;font-weight:700;
  cursor:pointer;box-shadow:0 4px 16px rgba(79,70,229,.4);z-index:10}
#ind .fab .material-icons-outlined{font-size:22px}

/* ---- Boş durum ---- */
#ind .bos{padding:32px 24px;color:var(--sub);font-size:14px;text-align:center}

/* ---- Modal ---- */
#ind .modal-bg{position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:40;display:flex;align-items:center;justify-content:center;padding:16px}
#ind .modal-bg[hidden]{display:none}
#ind .modal-kutu{background:var(--card);border-radius:18px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;padding:24px}
#ind .modal-bas{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
#ind .modal-bas h2{color:var(--ink);font-size:18px;font-weight:700}
#ind .modal-bas button{background:none;border:0;color:var(--sub2);font-size:22px;cursor:pointer;line-height:1}
#ind .alan-etiket{color:var(--sub);font-size:12.5px;font-weight:600;margin:16px 0 6px 2px;display:block}
#ind .alan-kutu{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:0 12px;display:flex;align-items:center}
body.koyu #ind .alan-kutu{background:var(--card2)}
#ind .alan-kutu input,#ind .alan-kutu select{width:100%;background:none;border:0;outline:none;color:var(--ink);font-size:15px;padding:11px 0;font-family:inherit}
#ind .alan-kutu select option{background:var(--card);color:var(--ink)}
#ind .alan-ipucu{color:var(--sub2);font-size:12px;margin-top:4px}

/* ---- Segment (Yüzde / Tutar) ---- */
#ind .seg-wrap{display:flex;gap:8px}
#ind .seg-btn{flex:1;padding:11px;border-radius:10px;border:1px solid var(--line);background:var(--card);
  color:var(--sub2);font-size:13px;font-weight:700;cursor:pointer;text-align:center}
#ind .seg-btn.akt{background:#4F46E5;border-color:#4F46E5;color:#fff}

/* ---- Gün seçici ---- */
#ind .gun-wrap{display:flex;gap:4px}
#ind .gun-btn{flex:1;padding:10px 4px;border-radius:9px;border:1px solid var(--line);background:var(--card);
  color:var(--sub2);font-size:12px;font-weight:700;cursor:pointer;text-align:center}
#ind .gun-btn.akt{background:#4F46E5;border-color:#4F46E5;color:#fff}

/* ---- Ürün liste ---- */
#ind .urun-liste{max-height:220px;overflow-y:auto;border:1px solid var(--line);border-radius:12px;margin-top:8px}
#ind .urun-satir{display:flex;align-items:center;gap:8px;padding:10px 14px;border-bottom:1px solid var(--line);cursor:pointer}
#ind .urun-satir:last-child{border-bottom:0}
#ind .urun-satir:hover{background:var(--card2)}
#ind .urun-satir input[type=checkbox]{width:16px;height:16px;flex-shrink:0;accent-color:#4F46E5}
#ind .urun-satir span{color:var(--ink);font-size:14px}

/* ---- İki kolon ---- */
#ind .iki-kol{display:grid;grid-template-columns:1fr 1fr;gap:10px}

/* ---- Aktif switch ---- */
#ind .aktif-satir{display:flex;align-items:center;justify-content:space-between;padding:4px 0}
#ind .aktif-satir .ak-baslik{color:var(--ink);font-size:15px}
#ind .aktif-satir .ak-alt{color:var(--sub2);font-size:12px}

/* ---- Modal butonlar ---- */
#ind .modal-alt{display:flex;gap:10px;margin-top:18px}
#ind .modal-vazgec{flex:1;padding:13px;border-radius:14px;background:var(--card2);border:0;color:var(--ink);font-size:14px;font-weight:600;cursor:pointer}
#ind .modal-kaydet{flex:1;padding:13px;border-radius:14px;background:#4F46E5;border:0;color:#fff;font-size:14px;font-weight:700;cursor:pointer}
#ind .modal-kaydet:disabled{opacity:.6;cursor:default}

/* koyu input placeholder */
body.koyu #ind .alan-kutu input::placeholder{color:var(--sub2)}
</style>
@endpush

@section('content')
<div id="ind">

  {{-- Bilgi bandı --}}
  <div class="info-band">
    Her indirim türü ayrı bir kuraldır, tek tek açıp kapatabilirsin. Ödeme anında uygun olan en yüksek indirim otomatik uygulanır (kuponsa müşteri kodu girer). Online ödeme indirimi müşteriyi karta teşvik eder.
  </div>

  {{-- Kart ızgarası --}}
  <div class="k-grid" id="ind-liste">
    @forelse($kurallar as $k)
      @php
        $meta = [
          'online_odeme' => ['💳','Online Ödeme'],
          'kupon'        => ['🎫','Kupon Kodu'],
          'uygulama'     => ['📱','Uygulama Siparişi'],
          'ilk_siparis'  => ['🥇','İlk Sipariş'],
          'tutar_ustu'   => ['💰','Tutar Üstü'],
          'happy_hour'   => ['🕐','Happy Hour'],
          'gun'          => ['📅','Haftanın Günü'],
          'dogum_gunu'   => ['🎂','Doğum Günü'],
          'urun'         => ['🍽️','Seçili Ürünler'],
        ][$k->tip] ?? ['🏷️', $k->tip];
        $aktif  = (bool)$k->aktif;
        $deger  = (float)$k->deger;
        $degerY = $k->deger_tipi === 'yuzde'
          ? '%'.( $deger == (int)$deger ? (int)$deger : $deger )
          : number_format($deger, 0, ',', '.').' ₺';

        /* chip'ler */
        $cips = [];
        if (!empty($k->kupon_kodu)) $cips[] = $k->kupon_kodu;
        if (!empty($k->min_tutar) && (float)$k->min_tutar > 0) $cips[] = 'min '.(int)$k->min_tutar.'₺';
        if (!empty($k->max_indirim) && (float)$k->max_indirim > 0) $cips[] = 'tavan '.(int)$k->max_indirim.'₺';
        if (!empty($k->saat_bas) && !empty($k->saat_bit)) $cips[] = $k->saat_bas.'–'.$k->saat_bit;
        if (!empty($k->gun_maskesi)) {
          $gAd = ['','Pzt','Sal','Çar','Per','Cum','Cmt','Paz'];
          $gunler = array_filter(explode(',', $k->gun_maskesi), fn($s) => $s !== '');
          $cips[] = implode(', ', array_map(fn($n) => $gAd[(int)$n] ?? '', $gunler));
        }
        if ($k->tip === 'urun') {
          try { $nc = count(json_decode($k->urun_ids ?? '[]', true) ?? []); } catch(\Throwable $e) { $nc = 0; }
          $cips[] = $nc.' ürün';
        }
        if (!empty($k->kullanim_limiti)) $cips[] = 'kullanım '.($k->kullanim_sayisi ?? 0).'/'.(int)$k->kullanim_limiti;
      @endphp
      <div class="kart {{ $aktif ? 'aktif' : 'pasif' }}" data-id="{{ $k->id }}">
        <div class="kart-ust">
          <div class="kart-ikon">{{ $meta[0] }}</div>
          <div class="kart-bilgi">
            <div class="kart-ad">{{ $k->ad }}</div>
            <div class="kart-tip">{{ $meta[1] }}</div>
          </div>
          <label class="tog-wrap" title="{{ $aktif ? 'Kapat' : 'Aç' }}">
            <input type="checkbox" class="tog-input" data-id="{{ $k->id }}" {{ $aktif ? 'checked' : '' }}>
            <div class="tog-track"></div>
            <div class="tog-dot"></div>
          </label>
        </div>

        <div class="kart-deger">{{ $degerY }}</div>

        @if(count($cips))
        <div class="cips">
          @foreach($cips as $c)
            <span class="cip">{{ $c }}</span>
          @endforeach
        </div>
        @endif

        <div class="kart-alt">
          <button class="btn-duzenle" data-id="{{ $k->id }}">
            <span class="material-icons-outlined">edit</span> Düzenle
          </button>
          <button class="btn-sil" data-id="{{ $k->id }}" data-ad="{{ $k->ad }}">
            <span class="material-icons-outlined">delete_outline</span>
          </button>
        </div>
      </div>
    @empty
      <div class="bos">Henüz indirim yok. "Yeni İndirim" ile ekle.</div>
    @endforelse
  </div>

  {{-- FAB --}}
  <button class="fab" id="fab-yeni">
    <span class="material-icons-outlined">add</span> Yeni İndirim
  </button>

  {{-- Modal --}}
  <div class="modal-bg" id="modal" hidden>
    <div class="modal-kutu">
      <div class="modal-bas">
        <h2 id="modal-baslik">Yeni İndirim</h2>
        <button id="modal-kapat">✕</button>
      </div>

      <input type="hidden" id="f-id">

      {{-- Tür --}}
      <span class="alan-etiket">Tür</span>
      <div class="alan-kutu">
        <select id="f-tip">
          <option value="online_odeme">💳  Online Ödeme</option>
          <option value="kupon">🎫  Kupon Kodu</option>
          <option value="uygulama">📱  Uygulama Siparişi</option>
          <option value="ilk_siparis">🥇  İlk Sipariş</option>
          <option value="tutar_ustu">💰  Tutar Üstü</option>
          <option value="happy_hour">🕐  Happy Hour</option>
          <option value="gun">📅  Haftanın Günü</option>
          <option value="dogum_gunu">🎂  Doğum Günü</option>
          <option value="urun">🍽️  Seçili Ürünler</option>
        </select>
      </div>
      <div class="alan-ipucu" id="f-ipucu"></div>

      {{-- Ad --}}
      <span class="alan-etiket">Ad</span>
      <div class="alan-kutu">
        <input type="text" id="f-ad" placeholder="İndirim adı">
      </div>

      {{-- İndirim --}}
      <span class="alan-etiket">İndirim</span>
      <div class="seg-wrap">
        <button type="button" class="seg-btn akt" data-v="yuzde" id="seg-yuzde">Yüzde %</button>
        <button type="button" class="seg-btn" data-v="tutar" id="seg-tutar">Tutar ₺</button>
      </div>
      <div style="height:8px"></div>
      <div class="alan-kutu">
        <input type="number" id="f-deger" placeholder="Örn: 10" min="0" step="0.01">
      </div>

      {{-- Kupon --}}
      <div id="alan-kupon" style="display:none">
        <span class="alan-etiket">Kupon Kodu</span>
        <div class="alan-kutu">
          <input type="text" id="f-kupon" placeholder="HOSGELDIN" style="text-transform:uppercase;letter-spacing:2px">
        </div>
      </div>

      {{-- Happy Hour --}}
      <div id="alan-happy" style="display:none">
        <span class="alan-etiket">Saat Aralığı</span>
        <div class="iki-kol">
          <div class="alan-kutu"><input type="time" id="f-saat-bas"></div>
          <div class="alan-kutu"><input type="time" id="f-saat-bit"></div>
        </div>
      </div>

      {{-- Gün --}}
      <div id="alan-gun" style="display:none">
        <span class="alan-etiket">Günler</span>
        <div class="gun-wrap">
          <button type="button" class="gun-btn" data-g="1">Pt</button>
          <button type="button" class="gun-btn" data-g="2">Sa</button>
          <button type="button" class="gun-btn" data-g="3">Ça</button>
          <button type="button" class="gun-btn" data-g="4">Pe</button>
          <button type="button" class="gun-btn" data-g="5">Cu</button>
          <button type="button" class="gun-btn" data-g="6">Ct</button>
          <button type="button" class="gun-btn" data-g="7">Pz</button>
        </div>
      </div>

      {{-- Ürün --}}
      <div id="alan-urun" style="display:none">
        <span class="alan-etiket" id="urun-etiket">İndirim uygulanacak ürünler (0 seçili)</span>
        <div class="alan-kutu"><input type="text" id="urun-ara" placeholder="Ürün ara…"></div>
        <div class="urun-liste" id="urun-liste">
          @foreach($urunler as $u)
            <label class="urun-satir" data-id="{{ $u->id }}">
              <input type="checkbox" class="urun-cb" value="{{ $u->id }}">
              <span>{{ $u->ad }}</span>
            </label>
          @endforeach
          @if($urunler->isEmpty())
            <div style="padding:14px;color:var(--sub);font-size:13px">Ürün bulunamadı.</div>
          @endif
        </div>
      </div>

      {{-- Koşullar --}}
      <span class="alan-etiket">Koşullar (opsiyonel)</span>
      <div class="iki-kol">
        <div class="alan-kutu"><input type="number" id="f-min" placeholder="Min. tutar ₺" min="0"></div>
        <div class="alan-kutu"><input type="number" id="f-max" placeholder="Maks. indirim ₺" min="0"></div>
      </div>
      <div style="height:10px"></div>
      <div class="alan-kutu">
        <input type="number" id="f-limit" placeholder="Toplam kullanım limiti (boş = sınırsız)" min="0">
      </div>

      {{-- Aktif toggle --}}
      <div style="height:10px"></div>
      <div class="aktif-satir">
        <div>
          <div class="ak-baslik">Aktif</div>
          <div class="ak-alt">Kapalıyken uygulanmaz</div>
        </div>
        <label class="tog-wrap">
          <input type="checkbox" id="f-aktif" checked>
          <div class="tog-track"></div>
          <div class="tog-dot"></div>
        </label>
      </div>

      <div class="modal-alt">
        <button type="button" class="modal-vazgec" id="modal-vazgec">Vazgeç</button>
        <button type="button" class="modal-kaydet" id="modal-kaydet">Kaydet</button>
      </div>
    </div>
  </div>

</div><!-- #ind -->
@endsection

@push('scripts')
<script>
/* ---- Tip metadata ---- */
const TIPLER = {
  online_odeme:{ad:'Online Ödeme',ik:'💳',ipucu:'Müşteri kartla online öderken otomatik. Kasa/nakitte uygulanmaz.'},
  kupon:{ad:'Kupon Kodu',ik:'🎫',ipucu:'Müşteri ödeme ekranında kodu girer. Kupon kodu zorunlu.'},
  uygulama:{ad:'Uygulama Siparişi',ik:'📱',ipucu:'Uygulamadan gelen siparişte. (QR-web\'de pasif.)'},
  ilk_siparis:{ad:'İlk Sipariş',ik:'🥇',ipucu:'Müşterinin ilk siparişinde. (Müşteri tanınıyorsa.)'},
  tutar_ustu:{ad:'Tutar Üstü',ik:'💰',ipucu:'Sepet, min. tutarın üstündeyse. Min. tutarı gir.'},
  happy_hour:{ad:'Happy Hour',ik:'🕐',ipucu:'Belirlenen saat aralığında.'},
  gun:{ad:'Haftanın Günü',ik:'📅',ipucu:'Seçili günlerde.'},
  dogum_gunu:{ad:'Doğum Günü',ik:'🎂',ipucu:'Müşterinin doğum gününde. (Müşteri tanınıyorsa.)'},
  urun:{ad:'Seçili Ürünler',ik:'🍽️',ipucu:'İndirim yalnızca seçtiğin ürünlere (online ödemede).'},
};

/* ---- Toggle switch ---- */
document.querySelectorAll('.tog-input').forEach(inp => {
  inp.addEventListener('change', async function(){
    const id = this.dataset.id;
    const kart = document.querySelector('.kart[data-id="'+id+'"]');
    try {
      const r = await api('/indirimler/toggle', {id});
      if (r.ok) {
        kart.classList.toggle('aktif', !!r.aktif);
        kart.classList.toggle('pasif', !r.aktif);
        this.checked = !!r.aktif;
      } else { this.checked = !this.checked; }
    } catch(e){ this.checked = !this.checked; }
  });
});

/* ---- Sil ---- */
document.querySelectorAll('.btn-sil').forEach(btn => {
  btn.addEventListener('click', function(){
    const id   = this.dataset.id;
    const ad   = this.dataset.ad;
    rkOnay('"'+ad+'" indirimi silinsin mi?', async function(){
      const r = await api('/indirimler/sil', {id});
      if (r.ok) document.querySelector('.kart[data-id="'+id+'"]')?.remove();
    }, 'var(--kirmizi)', 'Sil');
  });
});

/* ---- Modal ---- */
const modal     = document.getElementById('modal');
const fId       = document.getElementById('f-id');
const fTip      = document.getElementById('f-tip');
const fAd       = document.getElementById('f-ad');
const fDeger    = document.getElementById('f-deger');
const fKupon    = document.getElementById('f-kupon');
const fSaatBas  = document.getElementById('f-saat-bas');
const fSaatBit  = document.getElementById('f-saat-bit');
const fMin      = document.getElementById('f-min');
const fMax      = document.getElementById('f-max');
const fLimit    = document.getElementById('f-limit');
const fAktif    = document.getElementById('f-aktif');
const fIpucu    = document.getElementById('f-ipucu');
const segYuzde  = document.getElementById('seg-yuzde');
const segTutar  = document.getElementById('seg-tutar');
const urunAra   = document.getElementById('urun-ara');
const urunListe = document.getElementById('urun-liste');
const urunEtiket= document.getElementById('urun-etiket');

let degerTipi = 'yuzde';
let secilenGunler = new Set();
let secilenUrunler = new Set();

function tipGuncelle(){
  const t = fTip.value;
  fIpucu.textContent = TIPLER[t]?.ipucu ?? '';
  document.getElementById('alan-kupon').style.display  = t==='kupon'      ? '' : 'none';
  document.getElementById('alan-happy').style.display  = t==='happy_hour' ? '' : 'none';
  document.getElementById('alan-gun').style.display    = t==='gun'        ? '' : 'none';
  document.getElementById('alan-urun').style.display   = t==='urun'       ? '' : 'none';
}

function segGuncelle(v){
  degerTipi = v;
  segYuzde.classList.toggle('akt', v==='yuzde');
  segTutar.classList.toggle('akt', v==='tutar');
  fDeger.placeholder = v==='yuzde' ? 'Örn: 10' : 'Örn: 50';
}

function urunCbGuncelle(){
  urunEtiket.textContent = 'İndirim uygulanacak ürünler ('+secilenUrunler.size+' seçili)';
  urunListe.querySelectorAll('.urun-cb').forEach(cb => {
    cb.checked = secilenUrunler.has(Number(cb.value));
  });
}

function urunFiltrele(){
  const q = (urunAra.value||'').toLocaleLowerCase('tr');
  urunListe.querySelectorAll('.urun-satir').forEach(row => {
    const ad = row.querySelector('span').textContent.toLocaleLowerCase('tr');
    row.style.display = (!q || ad.includes(q)) ? '' : 'none';
  });
}

function modalAc(k){
  fId.value     = k?.id ?? '';
  fTip.value    = k?.tip ?? 'online_odeme';
  fAd.value     = k?.ad ?? '';
  fDeger.value  = k?.deger ?? '';
  fKupon.value  = k?.kupon_kodu ?? '';
  fSaatBas.value= k?.saat_bas ?? '';
  fSaatBit.value= k?.saat_bit ?? '';
  fMin.value    = (k?.min_tutar  && parseFloat(k.min_tutar)  > 0) ? parseFloat(k.min_tutar)  : '';
  fMax.value    = (k?.max_indirim&& parseFloat(k.max_indirim)> 0) ? parseFloat(k.max_indirim): '';
  fLimit.value  = k?.kullanim_limiti ?? '';
  fAktif.checked= k ? !!k.aktif : true;

  segGuncelle(k?.deger_tipi ?? 'yuzde');

  secilenGunler.clear();
  if (k?.gun_maskesi) String(k.gun_maskesi).split(',').filter(Boolean).forEach(n => secilenGunler.add(Number(n)));
  document.querySelectorAll('.gun-btn').forEach(btn => {
    btn.classList.toggle('akt', secilenGunler.has(Number(btn.dataset.g)));
  });

  secilenUrunler.clear();
  if (k?.urun_ids){ try { (JSON.parse(k.urun_ids)||[]).forEach(id => secilenUrunler.add(Number(id))); }catch(e){} }
  urunAra.value = '';
  urunFiltrele();
  urunCbGuncelle();

  tipGuncelle();
  document.getElementById('modal-baslik').textContent = k?.id ? 'İndirimi Düzenle' : 'Yeni İndirim';
  document.getElementById('modal-kaydet').disabled = false;
  modal.hidden = false;
}

function modalKapat(){ modal.hidden = true; }

document.getElementById('fab-yeni').addEventListener('click', () => modalAc(null));
document.getElementById('modal-kapat').addEventListener('click', modalKapat);
document.getElementById('modal-vazgec').addEventListener('click', modalKapat);
modal.addEventListener('click', e => { if (e.target === modal) modalKapat(); });

fTip.addEventListener('change', tipGuncelle);
segYuzde.addEventListener('click', () => segGuncelle('yuzde'));
segTutar.addEventListener('click', () => segGuncelle('tutar'));
urunAra.addEventListener('input', urunFiltrele);

/* Düzenle butonları — server-side kartlar için kuralları PHP'den alıyoruz */
const kurallarMap = {};
@foreach($kurallar as $k)
kurallarMap[{{ $k->id }}] = @json($k);
@endforeach

document.querySelectorAll('.btn-duzenle').forEach(btn => {
  btn.addEventListener('click', function(){ modalAc(kurallarMap[Number(this.dataset.id)]); });
});

/* Gün butonları */
document.querySelectorAll('.gun-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const n = Number(this.dataset.g);
    secilenGunler.has(n) ? secilenGunler.delete(n) : secilenGunler.add(n);
    this.classList.toggle('akt', secilenGunler.has(n));
  });
});

/* Ürün checkbox */
urunListe.querySelectorAll('.urun-cb').forEach(cb => {
  cb.addEventListener('change', function(){
    const id = Number(this.value);
    secilenUrunler.has(id) ? secilenUrunler.delete(id) : secilenUrunler.add(id);
    urunCbGuncelle();
  });
});

/* Kaydet */
document.getElementById('modal-kaydet').addEventListener('click', async function(){
  const dv = (fDeger.value||'').trim();
  if (!dv){ alert('Bir indirim değeri gir'); return; }
  if (fTip.value==='kupon' && !fKupon.value.trim()){ alert('Kupon kodu gir'); return; }
  if (fTip.value==='urun' && !secilenUrunler.size){ alert('En az bir ürün seç'); return; }
  if (fTip.value==='happy_hour' && (!fSaatBas.value||!fSaatBit.value)){ alert('Saat aralığını gir'); return; }
  if (fTip.value==='gun' && !secilenGunler.size){ alert('En az bir gün seç'); return; }

  const payload = {
    id: fId.value || null,
    tip: fTip.value,
    ad: fAd.value.trim() || (TIPLER[fTip.value]?.ad ?? 'İndirim'),
    deger_tipi: degerTipi,
    deger: dv,
    aktif: fAktif.checked ? 1 : 0,
    kupon_kodu: fKupon.value.trim(),
    saat_bas: fSaatBas.value,
    saat_bit: fSaatBit.value,
    gun_maskesi: [...secilenGunler].sort((a,b)=>a-b).join(','),
    min_tutar: fMin.value.trim(),
    max_indirim: fMax.value.trim(),
    kullanim_limiti: fLimit.value.trim(),
    urun_ids: fTip.value==='urun' ? JSON.stringify([...secilenUrunler]) : '',
  };

  this.disabled = true;
  try {
    const r = await api('/indirimler/kaydet', payload);
    if (r.ok){ location.reload(); return; }
    alert(r.hata || 'Kaydedilemedi');
  } catch(e){ alert('Kaydedilemedi, tekrar dene'); }
  this.disabled = false;
});
</script>
@endpush

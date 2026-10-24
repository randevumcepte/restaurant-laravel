@extends('layout.app')
@section('rk_ikon', 'groups')
@section('baslik', 'Departman & Yetki')
@section('title', 'Departman Yönetimi')

@section('content')
<div class="rk-body">

  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  <div class="rk-bolum" style="margin-bottom:12px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Departmanlar</span>
    <span class="sayi">{{ count($departmanlar) }}</span>
  </div>

  <div class="rk-sub" style="font-size:12.5px;margin:-4px 2px 14px">
    Departman = sayfa yetkilerinin yeniden kullanılabilir şablonu. Personel ekranında bir kişiye departman atayınca
    o departmanın sayfaları otomatik açılır. İki departman <b>aynı sayfayı paylaşabilir</b> (örn. Stok hem Mutfak hem Satın Alma'da).
  </div>

  @if ($duzenleyebilir)
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
    <button class="rk-btn" style="background:var(--mor1)" onclick="depAc(null)"><span class="material-icons-outlined" style="font-size:18px">add</span> Yeni Departman</button>
    @if (count($departmanlar) === 0)
    <form method="POST" action="/departman-sablon-kur" style="display:inline">@csrf
      <button class="rk-btn bos" type="submit"><span class="material-icons-outlined" style="font-size:18px">auto_awesome</span> Hazır departmanları oluştur</button>
    </form>
    @endif
  </div>
  @endif

  @php $gruplu = collect($departmanlar)->groupBy(fn ($d) => $d->grup ?: 'Diğer'); @endphp
  @foreach ($gruplu as $grupAd => $items)
    <div class="rk-bolum" style="margin:18px 0 10px">
      <span class="cubuk" style="background:var(--mavi)"></span>
      <span class="b" style="font-size:14px">{{ $grupAd }}</span>
      <span class="sayi">{{ count($items) }}</span>
    </div>
    <div class="rk-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px">
      @foreach ($items as $d)
        @php
          $acikKeys = [];
          $sy = $d->sayfa_yetki ? json_decode($d->sayfa_yetki, true) : [];
          if (is_array($sy)) foreach ($sy as $k => $v) if ($v) $acikKeys[] = $k;
          $ekKeys = $d->sorumlu_ek ? (json_decode($d->sorumlu_ek, true) ?: []) : [];
          $unvanlar = $d->unvanlar ? (json_decode($d->unvanlar, true) ?: []) : [];
          $aiEt = ['tam' => 'AI: Tam', 'kisitli' => 'AI: Kısıtlı', 'kapali' => 'AI: Kapalı'][$d->ai_erisim] ?? 'AI: Kapalı';
          $aiRenk = ['tam' => 'var(--yesil)', 'kisitli' => 'var(--amber)', 'kapali' => 'var(--sub)'][$d->ai_erisim] ?? 'var(--sub)';
          $dkat = collect($kategoriler ?? [])->where('departman_id', $d->id)->pluck('id')->map(fn ($x) => (int) $x)->values()->all();
          $kartJson = ['id' => (int) $d->id, 'ad' => $d->ad, 'kod' => $d->kod, 'grup' => $d->grup, 'renk' => $d->renk, 'ikon' => $d->ikon, 'ai_erisim' => $d->ai_erisim, 'ai_gunluk_limit' => (int) $d->ai_gunluk_limit, 'aktif' => (int) $d->aktif, 'sayfa' => $acikKeys, 'ek' => $ekKeys, 'unvanlar' => $unvanlar, 'katIds' => $dkat];
        @endphp
        <div class="rk-card" style="padding:14px">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span class="material-icons-outlined" style="font-size:26px;color:{{ $d->renk ?: 'var(--mavi)' }}">{{ $d->ikon ?: 'badge' }}</span>
            <div style="flex:1">
              <div style="font-weight:800;font-size:15px">{{ $d->ad }}</div>
              <div class="rk-sub" style="font-size:11px">{{ $unvanlar ? '↑ '.$unvanlar[0] : $d->kod }}</div>
            </div>
            @if (!(int)$d->aktif) <span class="rk-rozet" style="background:var(--kirmizi)">Pasif</span> @endif
          </div>
          <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px">
            <span class="rk-rozet" style="background:{{ $aiRenk }}">{{ $aiEt }}</span>
            <span class="rk-rozet" style="background:var(--card2);color:var(--sub2)">{{ count($acikKeys) }} sayfa</span>
            @if (count($unvanlar)) <span class="rk-rozet" style="background:var(--card2);color:var(--sub2)">{{ count($unvanlar) }} ünvan</span> @endif
          </div>
          @if ($duzenleyebilir)
          <div style="display:flex;gap:8px">
            <button class="rk-btn bos" style="flex:1;padding:7px" onclick='depAc(@json($kartJson))'>
              <span class="material-icons-outlined" style="font-size:16px">edit</span> Düzenle
            </button>
            <a href="/departman-baglanti?dep={{ $d->id }}" class="rk-btn bos" style="padding:7px;color:var(--mor1)" title="Bağlantılar (Gmail/Outlook/SMTP/WhatsApp)"><span class="material-icons-outlined" style="font-size:16px">hub</span></a>
            <form method="POST" action="/departman-sil" onsubmit="return rkOnayForm(this, '{{ addslashes($d->ad) }} departmanı silinsin mi? (Bağlı personelin erişimi mevcut yetkileriyle devam eder)', 'var(--kirmizi)', 'Evet, sil')" style="display:inline">
              @csrf <input type="hidden" name="id" value="{{ $d->id }}">
              <button class="rk-btn bos" style="padding:7px;color:var(--kirmizi)" type="submit"><span class="material-icons-outlined" style="font-size:16px">delete</span></button>
            </form>
          </div>
          @endif
        </div>
      @endforeach
    </div>
  @endforeach
  @if (count($departmanlar) === 0)
    <div class="rk-sub" style="padding:24px;text-align:center">Henüz departman yok. "Hazır departmanları oluştur" ile başlayabilirsin.</div>
  @endif
</div>

@if ($duzenleyebilir)
{{-- ==================== DEPARTMAN DÜZENLE MODAL ==================== --}}
<div id="depModal" class="rk-modal-arka" onclick="if(event.target===this)depKapat()">
  <div class="rk-modal">
    <form method="POST" action="/departman-kaydet">
      @csrf
      <input type="hidden" name="id" id="d_id" value="">
      <div class="rk-modal-head">
        <span class="b"><span id="depModalAd">Yeni Departman</span></span>
        <button type="button" class="rk-modal-kapa" onclick="depKapat()">&times;</button>
      </div>
      <div class="rk-modal-govde">
        <label class="rk-lbl">Departman Adı</label>
        <input type="text" name="ad" id="d_ad" class="rk-inp" placeholder="örn. Satın Alma" required>

        <div style="display:flex;gap:10px">
          <div style="flex:1">
            <label class="rk-lbl">Kısa Kod</label>
            <input type="text" name="kod" id="d_kod" class="rk-inp" placeholder="satinalma">
          </div>
          <div style="width:110px">
            <label class="rk-lbl">Renk</label>
            <input type="color" name="renk" id="d_renk" class="rk-inp" value="#4F46E5" style="height:40px;padding:3px">
          </div>
        </div>

        <label class="rk-lbl">İkon (Material)</label>
        <input type="text" name="ikon" id="d_ikon" class="rk-inp" placeholder="inventory_2">

        <label class="rk-lbl">Üst Grup (ağaç) — örn. Mutfak (BOH), İdari Ofis</label>
        <input type="text" name="grup" id="d_grup" class="rk-inp" placeholder="Mutfak (BOH)">

        <label class="rk-lbl">Ünvanlar (en üst SORUMLU en başta; her satıra bir ünvan)</label>
        <textarea name="unvanlar" id="d_unvanlar" class="rk-inp" rows="4" placeholder="Mutfak Şefi&#10;Sous Chef&#10;Aşçı&#10;Komi"></textarea>

        <div class="rk-bolum" style="margin:14px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mor1)">smart_toy</span>
          <span class="b" style="font-size:13.5px;color:var(--mor1)">AI Erişimi</span>
        </div>
        <select name="ai_erisim" id="d_ai" class="rk-inp">
          <option value="kapali">Kapalı — bu departman AI kullanamaz</option>
          <option value="kisitli">Kısıtlı — günlük soru sınırı</option>
          <option value="tam">Tam — sınırsız (üst yönetim)</option>
        </select>
        <div style="margin-top:8px">
          <label class="rk-lbl">Kısıtlı ise kişi başı günlük soru tavanı</label>
          <input type="number" name="ai_gunluk_limit" id="d_ai_limit" class="rk-inp" min="0" step="1" value="30" style="width:120px">
        </div>

        <div class="rk-bolum" style="margin:16px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--mavi)">visibility</span>
          <span class="b" style="font-size:13.5px;color:var(--mavi)">Görebileceği Sayfalar</span>
        </div>
        @foreach ($sayfaKategori as $kat => $sayfalar)
          <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:8px 2px 4px">{{ $kat }}</div>
          <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:10px">
            @foreach ($sayfalar as $sk => $et)
              <label class="rk-yetki-satir">
                <input type="checkbox" name="sayfa_{{ $sk }}" data-sayfa="{{ $sk }}" class="rk-chk">
                <span>{{ $et }}</span>
              </label>
            @endforeach
          </div>
        @endforeach

        <div class="rk-bolum" style="margin:16px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--amber)">workspace_premium</span>
          <span class="b" style="font-size:13.5px;color:var(--amber)">Sorumlu'ya (en üst kademe) Ek Sayfalar</span>
        </div>
        <div class="rk-sub" style="font-size:11.5px;margin:-2px 2px 8px">Bu departmanın sorumlusu, üyelere ek olarak aşağıdaki sayfaları da görür (örn. Raporlar, Puantaj).</div>
        @foreach ($sayfaKategori as $kat => $sayfalar)
          <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:8px">
            @foreach ($sayfalar as $sk => $et)
              <label class="rk-yetki-satir">
                <input type="checkbox" name="sorumlu_{{ $sk }}" data-ek="{{ $sk }}" class="rk-chk">
                <span>{{ $et }}</span>
              </label>
            @endforeach
          </div>
        @endforeach

        @if (!empty($kategoriler) && count($kategoriler))
        <div class="rk-bolum" style="margin:16px 0 8px">
          <span class="material-icons-outlined" style="font-size:18px;color:var(--yesil)">restaurant_menu</span>
          <span class="b" style="font-size:13.5px;color:var(--yesil)">Menü Kategorileri (istasyon ayrımı)</span>
        </div>
        <div class="rk-sub" style="font-size:11.5px;margin:-2px 2px 8px">Mutfak istasyonları için: bu departmanın hazırladığı kategorileri seç (örn. Pastane → Tatlılar). Böylece denetim raporunda sadece kendi ürünleri görünür.</div>
        <div style="background:var(--card2);border-radius:12px;padding:4px 12px;margin-bottom:8px">
          @foreach ($kategoriler as $kat)
            <label class="rk-yetki-satir">
              <input type="checkbox" name="kat_{{ $kat->id }}" data-kat="{{ $kat->id }}" class="rk-chk">
              <span>{{ $kat->ad }}</span>
            </label>
          @endforeach
        </div>
        @endif

        <label class="rk-yetki-satir" style="margin-top:8px">
          <input type="checkbox" name="aktif" value="1" id="d_aktif" class="rk-chk" checked><span>Aktif</span>
        </label>
      </div>
      <div class="rk-modal-foot">
        <button type="button" class="rk-btn bos" onclick="depKapat()">Vazgeç</button>
        <button type="submit" class="rk-btn" style="background:var(--mor1)">Kaydet</button>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function depAc(d) {
  document.getElementById('d_id').value = d ? d.id : '';
  document.getElementById('depModalAd').textContent = d ? ('Düzenle — ' + d.ad) : 'Yeni Departman';
  document.getElementById('d_ad').value = d ? (d.ad || '') : '';
  document.getElementById('d_kod').value = d ? (d.kod || '') : '';
  document.getElementById('d_renk').value = (d && d.renk) ? d.renk : '#4F46E5';
  document.getElementById('d_ikon').value = d ? (d.ikon || '') : '';
  document.getElementById('d_grup').value = d ? (d.grup || '') : '';
  document.getElementById('d_unvanlar').value = (d && d.unvanlar) ? d.unvanlar.join('\n') : '';
  document.getElementById('d_ai').value = (d && d.ai_erisim) ? d.ai_erisim : 'kapali';
  document.getElementById('d_ai_limit').value = d ? (d.ai_gunluk_limit || 0) : 30;
  document.getElementById('d_aktif').checked = d ? (d.aktif === 1) : true;
  var acik = (d && d.sayfa) ? d.sayfa : [];
  document.querySelectorAll('#depModal input[data-sayfa]').forEach(function (c) {
    c.checked = acik.indexOf(c.dataset.sayfa) >= 0;
  });
  var ek = (d && d.ek) ? d.ek : [];
  document.querySelectorAll('#depModal input[data-ek]').forEach(function (c) {
    c.checked = ek.indexOf(c.dataset.ek) >= 0;
  });
  var kat = (d && d.katIds) ? d.katIds : [];
  document.querySelectorAll('#depModal input[data-kat]').forEach(function (c) {
    c.checked = kat.indexOf(parseInt(c.dataset.kat)) >= 0;
  });
  document.getElementById('depModal').classList.add('acik');
}
function depKapat() { document.getElementById('depModal').classList.remove('acik'); }
</script>
@endpush

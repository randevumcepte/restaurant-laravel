@extends('layout.app')
@section('title', 'Gelen e-Fatura')
@section('rk_ikon', 'move_to_inbox')
@section('baslik', 'Gelen e-Fatura · ' . ($g->fatura_no ?: 'ETTN'))
@section('rk_alt', ($g->satici_ad ?: 'Tedarikçi') . ' · kalemleri malzemelerinizle eşleştirin; bir kez eşlenen kalem bir daha sorulmaz')

@section('content')
@php
  $tl = fn($v) => number_format((float) $v, 2, ',', '.') . ' ₺';
  $tl4 = fn($v) => rtrim(rtrim(number_format((float) $v, 4, ',', '.'), '0'), ',') . ' ₺';
  $num = fn($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $rozet = fn($renk) => 'color:var(' . $renk . ');background:color-mix(in srgb,var(' . $renk . ') 13%,transparent);border-color:color-mix(in srgb,var(' . $renk . ') 40%,transparent)';
  $durumAd = ['bekliyor' => ['Eşleştirme bekliyor', '--amber'], 'aktarildi' => ['Alış faturasına aktarıldı', '--yesil'], 'iade' => ['İade faturası', '--mavi'], 'yok_sayildi' => ['Yok sayıldı', '--sub']][$g->durum] ?? [$g->durum, '--sub'];
  $birimAd = $birimler->pluck('kisaltma', 'id');
  $acik = in_array($g->durum, ['bekliyor', 'iade'], true);
  $eksik = $kalemler->whereNull('malzeme_id')->count();
  $kalemNet = $kalemler->sum('satir_tutar');
@endphp
<style>
  .efd-ozet{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:14px}
  @media (max-width:900px){.efd-ozet{grid-template-columns:repeat(2,1fr)}}
  .efd-ozet .rk-card{padding:12px}
  .efd-ozet .e{font-size:11px;color:var(--sub)} .efd-ozet .d{font-weight:800;font-size:15px;margin-top:3px}
  .efd-tablo td{vertical-align:top}
  .efd-tablo select,.efd-tablo input{padding:6px 8px;font-size:12.5px}
  .efd-kod{font-size:11px;color:var(--sub)}
  .efd-esl{font-size:11px;margin-top:3px}
</style>

@if (session('ok'))<div class="rk-flash ok">{{ session('ok') }}</div>@endif
@if (session('hata'))<div class="rk-flash err">{{ session('hata') }}</div>@endif

<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center">
  <a href="/efatura-gelen" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">arrow_back</span> Gelen faturalar</a>
  @if ($g->xml_yol)<a href="/efatura-gelen/{{ $g->id }}/xml" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">code</span> XML indir</a>@endif
  @if ($g->durum === 'aktarildi')<a href="/fiyat-radari" class="rk-btn bos" style="padding:8px 13px"><span class="material-icons-outlined" style="font-size:17px">radar</span> Fiyat Radarı</a>@endif
  <span class="rk-rozet" style="{{ $rozet($durumAd[1]) }};margin-left:auto">{{ $durumAd[0] }}</span>
</div>

<div class="efd-ozet">
  <div class="rk-card"><div class="e">Tedarikçi</div><div class="d" style="font-size:13.5px">{{ $tedarikci->ad ?? ($g->satici_ad ?: '—') }}</div><div class="e">VKN {{ $g->satici_vkn ?: '—' }}</div></div>
  <div class="rk-card"><div class="e">Fatura</div><div class="d" style="font-size:13.5px">{{ $g->fatura_no ?: '—' }}</div><div class="e">{{ $g->tarih ? \Carbon\Carbon::parse($g->tarih)->format('d.m.Y') : '—' }} · {{ $g->profil === 'TICARIFATURA' ? 'Ticari' : ($g->profil === 'TEMELFATURA' ? 'Temel' : ($g->profil ?: '')) }} · {{ $g->tip }}</div></div>
  <div class="rk-card"><div class="e">Mal / hizmet (KDV hariç)</div><div class="d">{{ $tl($g->ara_toplam) }}</div>@if ($g->iskonto > 0)<div class="e">İskonto {{ $tl($g->iskonto) }}</div>@endif</div>
  <div class="rk-card"><div class="e">KDV</div><div class="d">{{ $tl($g->kdv_toplam) }}</div></div>
  <div class="rk-card"><div class="e">Ödenecek</div><div class="d" style="color:var(--mor1)">{{ $tl($g->genel_toplam) }}</div>@if ($g->para_birimi !== 'TRY')<div class="e">{{ $g->para_birimi }} · kur {{ $num($g->kur) }}</div>@endif</div>
</div>

@if (!$g->tutarli)
  <div class="rk-flash err">Kalemlerin toplamı ({{ $tl($kalemNet) }}) faturadaki mal/hizmet toplamını ({{ $tl($g->ara_toplam) }}) tutmuyor. Bu yüzden otomatik aktarılmadı; kalemleri faturayla karşılaştırın.</div>
@endif
@if ($g->not)<div class="rk-card" style="padding:10px 14px;margin-bottom:12px;font-size:12.5px"><span class="material-icons-outlined" style="font-size:16px;vertical-align:-3px;color:var(--amber)">info</span> {{ $g->not }}</div>@endif

<form method="POST" action="/efatura-gelen/{{ $g->id }}/eslestir">
  @csrf
  <div class="rk-bolum"><span class="cubuk" style="background:var(--amber)"></span><span class="b">Kalemler</span>
    @if ($acik && $eksik)<span class="rk-rozet" style="{{ $rozet('--amber') }}">{{ $eksik }} kalem eşleşmedi</span>@endif
  </div>
  <div style="overflow-x:auto"><div class="rk-tablo efd-tablo" style="min-width:980px"><table>
    <thead><tr><th>#</th><th>Faturadaki ürün</th><th class="sag">Miktar</th><th class="sag">Net birim fiyat</th><th class="sag">KDV</th><th class="sag">Tutar</th><th style="width:300px">Bizim malzeme</th><th style="width:170px">Alış birimi / çevrim</th></tr></thead>
    <tbody>
    @foreach ($kalemler as $k)
      @php
        $secili = $k->malzeme_id ?: ($k->oneri->id ?? null);
        $farkli = $secili && $k->temel_birim_id && $k->alis_birim_id && (int) $k->temel_birim_id !== (int) $k->alis_birim_id;
      @endphp
      <tr>
        <td class="rk-sub2">{{ $k->sira }}</td>
        <td><div style="font-weight:600">{{ $k->ad }}</div>@if ($k->stok_kodu)<div class="efd-kod">Stok kodu: {{ $k->stok_kodu }}</div>@endif</td>
        <td class="sag">{{ $num($k->miktar) }} {{ $birimAd[$k->alis_birim_id] ?? $k->birim_kodu }}</td>
        <td class="sag">{{ $tl4($k->birim_fiyat) }}@if ($k->iskonto > 0)<div class="efd-kod">liste {{ $tl4($k->liste_fiyat) }}</div>@endif</td>
        <td class="sag">%{{ $num($k->kdv_yuzde) }}</td>
        <td class="sag" style="font-weight:700">{{ $tl($k->satir_tutar) }}</td>
        <td>
          @if ($acik)
            <select name="kalem[{{ $k->id }}][malzeme]" class="rk-inp" onchange="efdSecim(this, {{ $k->id }})">
              <option value="">— Eşleştirilmedi —</option>
              <option value="yeni">＋ Yeni malzeme oluştur</option>
              @foreach ($malzemeler as $m)<option value="{{ $m->id }}" data-temel="{{ $m->temel_birim_id }}" @selected((int) $secili === (int) $m->id)>{{ $m->ad }}</option>@endforeach
            </select>
            <input type="text" name="kalem[{{ $k->id }}][yeni_ad]" id="efdYeni{{ $k->id }}" class="rk-inp" value="{{ $k->ad }}" placeholder="Yeni malzeme adı" style="display:none;margin-top:5px">
            <div class="efd-esl">
              @if ($k->eslesme === 'hafiza')<span style="color:var(--yesil)">✓ hafızadan eşleşti</span>
              @elseif ($k->eslesme === 'ad')<span style="color:var(--yesil)">✓ aynı adla eşleşti</span>
              @elseif ($k->eslesme === 'elle')<span style="color:var(--yesil)">✓ elle eşleştirildi</span>
              @elseif ($k->oneri)<span style="color:var(--amber)">Öneri: {{ $k->oneri->ad }} (kaydedince onaylanır)</span>
              @else<span style="color:var(--sub)">Listede yoksa “Yeni malzeme oluştur”</span>@endif
            </div>
          @else
            <span style="font-weight:600">{{ $k->malzeme_ad ?: '—' }}</span>
          @endif
        </td>
        <td>
          @if ($acik)
            <select name="kalem[{{ $k->id }}][birim]" class="rk-inp" onchange="efdBirim({{ $k->id }})" id="efdBirim{{ $k->id }}">
              @foreach ($birimler as $bi)<option value="{{ $bi->id }}" @selected((int) $k->alis_birim_id === (int) $bi->id)>{{ $bi->ad }}</option>@endforeach
            </select>
            <div id="efdCevrim{{ $k->id }}" style="display:{{ $farkli ? 'flex' : 'none' }};gap:5px;align-items:center;margin-top:5px;font-size:11.5px">
              1 {{ $birimAd[$k->alis_birim_id] ?? '' }} =
              <input type="text" inputmode="decimal" name="kalem[{{ $k->id }}][cevrim]" class="rk-inp" style="width:70px" value="{{ $k->cevrim ? $num($k->cevrim) : '' }}" placeholder="?">
              <span id="efdTemel{{ $k->id }}">{{ $k->temel_birim_id ? ($birimAd[$k->temel_birim_id] ?? '') : '' }}</span>
            </div>
          @else
            {{ $birimAd[$k->alis_birim_id] ?? '—' }}
          @endif
        </td>
      </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="5" class="sag" style="font-weight:700">Kalem toplamı (KDV hariç)</td><td class="sag" style="font-weight:800">{{ $tl($kalemNet) }}</td><td colspan="2"></td></tr></tfoot>
  </table></div></div>

  @if ($acik)
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px">
      <button class="rk-btn" style="background:var(--yesil)" type="submit"><span class="material-icons-outlined" style="font-size:17px;color:#fff">done_all</span>
        {{ $g->durum === 'iade' ? 'Eşleştirmeyi kaydet' : ($g->tutarli ? 'Kaydet ve alış faturasına aktar' : 'Eşleştirmeyi kaydet') }}</button>
      <span class="rk-sub2" style="font-size:12px">Stok: {{ $stokModu === 'mal_kabul' ? 'mal kabulde yazılır; bu fatura finansal kayıt olur ve mal kabulle karşılaştırılır.' : 'bu faturadan stok girişi yapılır.' }}
        Alış birimi temel birimden farklıysa çevrimi girin (ör. 1 Koli = 12 Adet).</span>
    </div>
  @endif
</form>

@if ($acik && !$g->tutarli && !$eksik && $g->durum === 'bekliyor')
  <form method="POST" action="/efatura-gelen/{{ $g->id }}/aktar" style="margin-top:10px" onsubmit="return confirm('Toplam tutmuyor. Yine de aktarılsın mı?')">@csrf
    <button class="rk-btn bos" type="submit" style="padding:7px 12px;color:var(--kirmizi)">Kontrol ettim, yine de aktar</button></form>
@endif

<div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
  @if ($cevapVerilir)
    <div class="rk-card" style="flex:1;min-width:300px">
      <div class="rk-bolum" style="margin-bottom:6px"><span class="cubuk" style="background:var(--mavi)"></span><span class="b">Ticari fatura yanıtı</span></div>
      <div class="rk-sub2" style="font-size:12px">Ticari faturada 8 gün içinde kabul ya da red yanıtı verebilirsiniz. Miktar ya da fiyat siparişle uyuşmuyorsa reddedin.</div>
      <form method="POST" action="/efatura-gelen/{{ $g->id }}/cevap" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">@csrf
        <input type="text" name="not" class="rk-inp" placeholder="Red gerekçesi (red için zorunlu)" style="flex:1;min-width:180px">
        <button class="rk-btn" name="islem" value="kabul" style="background:var(--yesil)" type="submit">Kabul et</button>
        <button class="rk-btn" name="islem" value="red" style="background:var(--kirmizi)" type="submit" onclick="return confirm('Fatura reddedilecek ve tedarikçiye bildirilecek. Emin misiniz?')">Reddet</button>
      </form>
    </div>
  @endif
  @if ($g->durum === 'bekliyor' || $g->durum === 'iade')
    <form method="POST" action="/efatura-gelen/{{ $g->id }}/yoksay" onsubmit="return confirm('Bu fatura alış faturasına aktarılmayacak. Emin misiniz?')">@csrf
      <button class="rk-btn bos" type="submit" style="padding:7px 12px"><span class="material-icons-outlined" style="font-size:16px">visibility_off</span> Yok say</button></form>
  @elseif ($g->durum === 'yok_sayildi')
    <form method="POST" action="/efatura-gelen/{{ $g->id }}/yoksay">@csrf<input type="hidden" name="geri" value="1">
      <button class="rk-btn bos" type="submit" style="padding:7px 12px"><span class="material-icons-outlined" style="font-size:16px">undo</span> Tekrar beklemeye al</button></form>
  @endif
</div>

<script>
  var efdBirimAd = {!! json_encode($birimAd, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!};
  function efdSecim(sel, id) {
    document.getElementById('efdYeni' + id).style.display = sel.value === 'yeni' ? 'block' : 'none';
    efdBirim(id);
  }
  function efdBirim(id) {
    var sel = document.querySelector('select[name="kalem[' + id + '][malzeme]"]');
    var birim = document.getElementById('efdBirim' + id);
    var kutu = document.getElementById('efdCevrim' + id);
    if (!sel || !birim || !kutu) return;
    var opt = sel.options[sel.selectedIndex];
    var temel = opt && opt.dataset ? opt.dataset.temel : null;
    var farkli = sel.value && sel.value !== 'yeni' && temel && String(temel) !== String(birim.value);
    kutu.style.display = farkli ? 'flex' : 'none';
    if (farkli) {
      kutu.firstChild.nodeValue = '1 ' + (efdBirimAd[birim.value] || '') + ' = ';
      document.getElementById('efdTemel' + id).innerText = efdBirimAd[temel] || '';
    }
  }
</script>
@endsection

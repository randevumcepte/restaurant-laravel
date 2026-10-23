@extends('layout.app')
@section('rk_ikon', 'shopping_cart_checkout')
@section('baslik', 'Hemen Sipariş Hazırla')
@section('title', 'Hemen Sipariş Hazırla')

@section('content')
<div class="rk-body">
<div style="max-width:860px">
  @if (session('ok'))   <div class="rk-flash ok">{{ session('ok') }}</div> @endif
  @if (session('hata')) <div class="rk-flash err">{{ session('hata') }}</div> @endif

  @php
    $malzOpt = '';
    foreach ($tumMalzeme as $m) $malzOpt .= '<option value="' . $m->id . '" data-birim="' . e($birimler[$m->temel_birim_id] ?? '') . '">' . e($m->ad) . '</option>';
    $tedOpt = '';
    foreach ($tumTedarikci as $t) $tedOpt .= '<option value="' . $t->id . '">' . ($t->anlasmali ? '⭐ ' : '') . e($t->ad) . '</option>';
  @endphp

  <div class="rk-bolum" style="margin-bottom:8px">
    <span class="cubuk" style="background:linear-gradient(135deg,var(--mor1),var(--mavi))"></span>
    <span class="b">Sipariş Listesi</span>
  </div>
  <div class="rk-sub" style="font-size:12.5px;margin:-2px 2px 14px">
    Her ürünün karşısında tedarikçi seçili gelir (<b>⭐ anlaşmalılar üstte</b>). İstediğini değiştir, miktarı düzenle, yeni ürün ekle.
    En altta <b>Siparişi Geç</b> dersen sipariş tedarikçilere <b>gruplanarak WhatsApp ile</b> iletilir.
  </div>

  <form id="spForm" method="POST" action="/acil-siparis-gec">
    @csrf
    <input type="hidden" name="talep" value="{{ $talepId }}">
    <input type="hidden" name="lines" id="linesInput">
    <div class="rk-card" style="padding:4px 14px">
      <table class="rk-tablo" style="width:100%">
        <thead><tr><th style="text-align:left">Ürün</th><th style="text-align:left">Tedarikçi</th><th>Miktar</th><th>Birim</th><th></th></tr></thead>
        <tbody id="satirlar">
          @foreach ($satirlar as $s)
          <tr class="satir">
            <td><select class="rk-inp l-malz" data-sel="{{ $s['malzeme_id'] }}" onchange="birimGuncelle(this)">{!! $malzOpt !!}</select></td>
            <td><select class="rk-inp l-ted" data-sel="{{ $s['secili'] }}">{!! $tedOpt !!}</select></td>
            <td><input class="rk-inp l-miktar" type="text" value="{{ rtrim(rtrim(number_format($s['miktar'], 2, '.', ''), '0'), '.') }}" style="width:80px;text-align:center"></td>
            <td style="text-align:center"><span class="l-birim rk-sub">{{ $s['birim'] }}</span></td>
            <td style="text-align:center"><button type="button" class="rk-btn bos" style="padding:4px 10px;color:var(--kirmizi)" onclick="this.closest('tr').remove()">✕</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <template id="rowTpl">
      <tr class="satir">
        <td><select class="rk-inp l-malz" data-sel="" onchange="birimGuncelle(this)">{!! $malzOpt !!}</select></td>
        <td><select class="rk-inp l-ted" data-sel="">{!! $tedOpt !!}</select></td>
        <td><input class="rk-inp l-miktar" type="text" value="1" style="width:80px;text-align:center"></td>
        <td style="text-align:center"><span class="l-birim rk-sub"></span></td>
        <td style="text-align:center"><button type="button" class="rk-btn bos" style="padding:4px 10px;color:var(--kirmizi)" onclick="this.closest('tr').remove()">✕</button></td>
      </tr>
    </template>

    <button type="button" class="rk-btn bos" style="margin-top:10px" onclick="addRow()"><span class="material-icons-outlined" style="font-size:16px;vertical-align:middle">add</span> Satır / Ürün Ekle</button>

    <div id="spUyari" class="rk-sub" style="color:var(--kirmizi);font-size:12.5px;margin-top:8px;display:none"></div>

    <div style="display:flex;gap:10px;margin-top:18px">
      <a href="/patron-ai" class="rk-btn bos">İptal</a>
      <button type="button" class="rk-btn" style="flex:1;background:var(--mor1)" onclick="siparisGec()"><span class="material-icons-outlined" style="font-size:18px;vertical-align:middle">shopping_cart_checkout</span> Siparişi Geç → Tedarikçilere Gönder</button>
    </div>
  </form>
</div>
</div>

@push('scripts')
<script>
function birimGuncelle(sel){
  var o = sel.options[sel.selectedIndex];
  var sp = sel.closest('tr').querySelector('.l-birim');
  if (sp && o) sp.textContent = o.getAttribute('data-birim') || '';
}
function setSel(sel){ var v = sel.getAttribute('data-sel'); if (v) sel.value = v; }
function initRow(tr){ tr.querySelectorAll('select').forEach(setSel); var m = tr.querySelector('.l-malz'); if (m) birimGuncelle(m); }
function addRow(){
  var tpl = document.getElementById('rowTpl');
  var tr = tpl.content.firstElementChild.cloneNode(true);
  document.getElementById('satirlar').appendChild(tr);
  initRow(tr);
}
function siparisGec(){
  var rows = [];
  document.querySelectorAll('#satirlar tr.satir').forEach(function(tr){
    var m = tr.querySelector('.l-malz').value, t = tr.querySelector('.l-ted').value, q = (tr.querySelector('.l-miktar').value || '').trim();
    if (m && t && q && parseFloat(q.replace(',','.')) > 0) rows.push({malzeme_id:m, tedarikci_id:t, miktar:q});
  });
  var u = document.getElementById('spUyari');
  if (!rows.length){ u.style.display='block'; u.textContent='En az bir geçerli satır gerekli (ürün + tedarikçi + miktar).'; return; }
  u.style.display='none';
  document.getElementById('linesInput').value = JSON.stringify(rows);
  var tedSay = new Set(rows.map(function(r){return r.tedarikci_id;})).size;
  rkOnay(rows.length + ' kalem, ' + tedSay + ' tedarikçiye sipariş geçilsin mi? (WhatsApp ile iletilecek)', function(){ HTMLFormElement.prototype.submit.call(document.getElementById('spForm')); }, 'var(--mor1)', 'Evet, siparişi geç');
}
document.querySelectorAll('#satirlar tr.satir').forEach(initRow);
</script>
@endpush
@endsection

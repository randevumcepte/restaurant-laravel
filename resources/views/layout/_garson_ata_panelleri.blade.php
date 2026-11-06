{{-- Garson atama modalı. Beklenen: $garsonlar, $bolgeler, $masalar, $masaByBolge --}}
@if ($garsonlar->count() && $masalar->count())
  {{-- Gizli atama panelleri: tıklanınca modala taşınır --}}
  <div id="gata-paneller" style="display:none">
    @foreach ($garsonlar as $g)
      <div id="gata-panel-{{ $g->id }}" data-ad="{{ $g->ad }}">
        <form method="POST" action="/isletme-duzeni/garson-ata">
          @csrf<input type="hidden" name="personel_id" value="{{ $g->id }}">
          @foreach ($bolgeler as $b)
            @php $grupM = $masaByBolge[$b->id] ?? collect(); $bSecili = in_array((int)$b->id, $g->bolge_idler ?? []); @endphp
            <div style="border:1px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:700;color:var(--ink);font-size:13.5px">
                <input type="checkbox" name="bolge_idler[]" value="{{ $b->id }}" {{ $bSecili ? 'checked' : '' }} onchange="rkBolgeTog(this)">
                <span class="material-icons-outlined idz-mor" style="font-size:17px">deck</span> {{ $b->ad }} <span class="rk-sub2" style="font-weight:500;font-size:11px">(tüm bölge)</span>
              </label>
              @if ($grupM->count())
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px;padding-left:4px" data-bolge="{{ $b->id }}">
                  @foreach ($grupM as $m)
                    <label style="display:inline-flex;align-items:center;gap:5px;background:var(--card2);border:1px solid var(--line);border-radius:8px;padding:4px 8px;cursor:pointer;font-size:12.5px;{{ $bSecili ? 'opacity:.5' : '' }}">
                      <input type="checkbox" name="masa_idler[]" value="{{ $m->id }}" {{ in_array((int)$m->id, $g->masa_idler ?? []) ? 'checked' : '' }} {{ $bSecili ? 'disabled' : '' }}> {{ $m->ad }}
                    </label>
                  @endforeach
                </div>
              @endif
            </div>
          @endforeach
          <div style="display:flex;gap:10px;margin-top:4px">
            <button type="button" class="idz-btn" style="flex:1;justify-content:center" onclick="garsonAtaKapat()">İptal</button>
            <button type="submit" class="idz-btn dolu" style="flex:2;justify-content:center"><span class="material-icons-outlined">save</span>Kaydet</button>
          </div>
        </form>
      </div>
    @endforeach
  </div>
@endif
{{-- Garson atama modalı --}}
<div id="gataModal" style="display:none;position:fixed;inset:0;z-index:120;background:rgba(15,23,42,.6);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:20px">
  <div class="rk-card gata-modal-box">
    <div class="gata-modal-head">
      <span class="material-icons-outlined idz-mor" style="font-size:20px">room_service</span>
      <span id="gataModalAd" style="font-weight:800;color:var(--ink);font-size:15px;flex:1">Garson</span>
      <button type="button" class="mduz-ibtn" style="color:var(--sub)" onclick="garsonAtaKapat()" aria-label="Kapat"><span class="material-icons-outlined">close</span></button>
    </div>
    <div class="rk-sub2" style="font-size:11.5px;margin:2px 0 12px">Bölge seçersen o bölgenin tüm masaları dahildir. Hiç seçmezsen bu garson tüm masalara bakar.</div>
    <div id="gataModalBody"></div>
  </div>
</div>

<script>
// ---- Garson atama modalı (gizli paneli modala taşı) ----
function garsonAtaAc(id){
  var panel = document.getElementById('gata-panel-'+id);
  if(!panel){ window.rkBilgi && rkBilgi('Atama için önce masa ekleyin.', 'wn'); return; }
  document.getElementById('gataModalAd').textContent = panel.getAttribute('data-ad') || 'Garson';
  document.getElementById('gataModalBody').appendChild(panel);
  panel.style.display = '';
  document.getElementById('gataModal').style.display = 'flex';
}
function garsonAtaKapat(){
  var body = document.getElementById('gataModalBody');
  var store = document.getElementById('gata-paneller');
  while(body.firstChild){ if(body.firstChild.style) body.firstChild.style.display='none'; store.appendChild(body.firstChild); }
  document.getElementById('gataModal').style.display = 'none';
}
(function(){ var m=document.getElementById('gataModal'); if(m) m.addEventListener('click',function(e){ if(e.target===m) garsonAtaKapat(); }); })();
function rkBolgeTog(cb){
  // Bölge "tümü" seçilince o bölgenin tek tek masa kutuları kapanır (zaten dahil)
  var box = cb.closest('div').querySelector('[data-bolge]');
  if(!box) return;
  box.querySelectorAll('input[type=checkbox]').forEach(function(x){
    x.disabled = cb.checked; if(cb.checked) x.checked = false;
    x.closest('label').style.opacity = cb.checked ? .5 : 1;
  });
}
</script>

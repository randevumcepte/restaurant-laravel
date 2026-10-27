@extends('layout.app')
@section('rk_ikon', 'rule_folder')
@section('baslik', 'Sebep Yönetimi')
@section('rk_alt', 'İptal, ürün silme, ikram ve iskonto işlemlerinde personele gösterilecek hazır sebepler')

@push('head')
<style>
#sbroot{max-width:700px;margin:0 auto}

/* ---- Tab şeridi (Flutter horizontal chip row) ---- */
#sbroot .sb-tabs{display:flex;gap:8px;padding:12px 0 10px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none}
#sbroot .sb-tabs::-webkit-scrollbar{display:none}
#sbroot .sb-tab{display:inline-flex;align-items:center;padding:7px 18px;border-radius:20px;border:1.5px solid var(--line);
  background:transparent;font-size:13px;font-weight:700;color:var(--sub2);cursor:pointer;white-space:nowrap;transition:background .12s,color .12s,border-color .12s}
#sbroot .sb-tab.aktif{background:#4F46E5;border-color:#4F46E5;color:#fff}
body.koyu #sbroot .sb-tab:not(.aktif){background:var(--card2)}

/* ---- Uyarı (düzenleyemez) ---- */
#sbroot .sb-uyari{font-size:13px;color:var(--sub);padding:8px 0 4px}

/* ---- Sebep kartları ---- */
#sbroot .sb-liste{display:flex;flex-direction:column;gap:8px;margin-top:4px}
#sbroot .sb-bos{text-align:center;color:var(--sub);font-size:14px;padding:40px 0}

#sbroot .sb-kart{display:flex;align-items:center;gap:10px;
  background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 14px;transition:box-shadow .1s}
#sbroot .sb-kart:hover{box-shadow:0 2px 8px rgba(0,0,0,.07)}
#sbroot .sb-kart .sb-metin{flex:1;font-size:14px;color:var(--ink)}
#sbroot .sb-kart .sb-sil{background:none;border:none;cursor:pointer;padding:4px;display:inline-flex;color:#F43F5E;line-height:0}
#sbroot .sb-kart .sb-sil .material-icons-outlined{font-size:20px}

/* ---- FAB (Ekle butonu sağ alt) ---- */
#sbFab{position:fixed;right:28px;bottom:28px;z-index:100;display:flex;align-items:center;gap:8px;
  height:52px;padding:0 22px;border:none;border-radius:26px;background:#4F46E5;
  color:#fff;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 6px 20px rgba(79,70,229,.4);
  transition:box-shadow .1s,transform .08s}
#sbFab:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(79,70,229,.5)}
#sbFab .material-icons-outlined{font-size:20px}

/* ---- Modal (Ekle dialog) ---- */
#sbModal{display:none;position:fixed;inset:0;z-index:200;align-items:center;justify-content:center;
  background:rgba(0,0,0,.45)}
#sbModal.ac{display:flex}
#sbModal .sb-dlg{background:var(--card);border-radius:18px;padding:24px;width:100%;max-width:420px;
  box-shadow:0 16px 48px rgba(0,0,0,.18);margin:16px}
#sbModal .sb-dlg .sb-dlg-bas{font-size:17px;font-weight:800;color:var(--ink);margin-bottom:18px}
#sbModal .sb-dlg input[type=text]{width:100%;height:48px;border:1.5px solid var(--line);border-radius:12px;
  background:var(--card2);color:var(--ink);font-size:14px;padding:0 14px;outline:none;box-sizing:border-box}
#sbModal .sb-dlg input[type=text]:focus{border-color:#4F46E5}
body.koyu #sbModal .sb-dlg input[type=text]{background:#151c2e}
#sbModal .sb-dlg .sb-dlg-alt{display:flex;gap:10px;margin-top:16px;justify-content:flex-end}
#sbModal .sb-dlg .sb-vaz{height:42px;padding:0 18px;border:1.5px solid var(--line);border-radius:10px;
  background:transparent;color:var(--sub);font-size:14px;font-weight:600;cursor:pointer}
#sbModal .sb-dlg .sb-ekle{height:42px;padding:0 22px;border:none;border-radius:10px;
  background:#4F46E5;color:#fff;font-size:14px;font-weight:700;cursor:pointer}
#sbModal .sb-dlg .sb-ekle:disabled{opacity:.6;cursor:default}

/* ---- Flash mesajları ---- */
.rk-flash{padding:10px 16px;border-radius:10px;font-size:13.5px;font-weight:600;margin-bottom:14px}
.rk-flash.ok{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.rk-flash.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
body.koyu .rk-flash.ok{background:#064e3b;color:#a7f3d0;border-color:#065f46}
body.koyu .rk-flash.err{background:#7f1d1d;color:#fca5a5;border-color:#991b1b}
</style>
@endpush

@section('content')
@php
  $turler  = ['void' => 'Ürün Silme', 'indirim' => 'İskonto', 'ikram' => 'İkram', 'iptal' => 'İptal'];
  $gruplar = $gruplar ?? [];
  // duzenleyebilir: sahip veya müdür — POS session personelinden alınır
  $webP    = function_exists('_posPersonel') ? _posPersonel() : null;
  $rol     = $webP->rol ?? '';
  $duz     = in_array($rol, ['sahip', 'mudur']);
@endphp

<div id="sbroot">

  {{-- Flash --}}
  @if (session('sebep_ok'))
    <div class="rk-flash ok">{{ session('sebep_ok') }}</div>
  @endif
  @if (session('sebep_hata'))
    <div class="rk-flash err">{{ session('sebep_hata') }}</div>
  @endif

  {{-- Tab şeridi (Flutter chip row) --}}
  <div class="sb-tabs" id="sbTabs">
    @foreach ($turler as $tur => $ad)
      <button class="sb-tab{{ $loop->first ? ' aktif' : '' }}"
              data-tur="{{ $tur }}"
              onclick="sbSec(this,'{{ $tur }}')">{{ $ad }}</button>
    @endforeach
  </div>

  {{-- Düzenleme uyarısı --}}
  @if (!$duz)
    <div class="sb-uyari">Sebepleri sadece işletme sahibi / müdürü düzenleyebilir.</div>
  @endif

  {{-- Sebep listeleri (her tur ayrı bölüm, JS ile göster/gizle) --}}
  @foreach ($turler as $tur => $ad)
    @php $liste = $gruplar[$tur] ?? []; @endphp
    <div class="sb-pane" data-tur="{{ $tur }}" style="{{ $loop->first ? '' : 'display:none' }}">
      <div class="sb-liste">
        @if (empty($liste))
          <div class="sb-bos">Bu türde sebep yok.</div>
        @else
          @foreach ($liste as $s)
            <div class="sb-kart">
              <span class="sb-metin">{{ $s->metin }}</span>
              @if ($duz)
                <form method="POST" action="/sebep-yonetimi/sil" style="margin:0;line-height:0"
                      onsubmit="return rkOnayForm(this, 'Bu sebep silinsin mi?', 'var(--kirmizi)', 'Sil')">
                  @csrf
                  <input type="hidden" name="id" value="{{ $s->id }}">
                  <button type="submit" class="sb-sil" title="Sil">
                    <span class="material-icons-outlined">delete_outline</span>
                  </button>
                </form>
              @endif
            </div>
          @endforeach
        @endif
      </div>
    </div>
  @endforeach

</div>{{-- /sbroot --}}

{{-- FAB — sadece düzenleyebilirse --}}
@if ($duz)
  <button id="sbFab" onclick="sbModalAc()">
    <span class="material-icons-outlined">add</span>
    <span>Sebep Ekle</span>
  </button>
@endif

{{-- Modal (Flutter AlertDialog karşılığı) --}}
@if ($duz)
<div id="sbModal" role="dialog" aria-modal="true" aria-labelledby="sbDlgBas">
  <div class="sb-dlg">
    <div class="sb-dlg-bas" id="sbDlgBas">
      <span id="sbDlgTurAd">Sebep</span> ekle
    </div>
    <form id="sbEkleForm" method="POST" action="/sebep-yonetimi/ekle" onsubmit="return sbEkleGonder(event)">
      @csrf
      <input type="hidden" name="tur" id="sbEkleTur" value="void">
      <input type="text" name="metin" id="sbEkleMetin" maxlength="120"
             placeholder="Örn: Müşteri beğenmedi" autocomplete="off"
             onkeydown="if(event.key==='Escape')sbModalKapat();if(event.key==='Enter')sbEkleGonder()">
      <div class="sb-dlg-alt">
        <button type="button" class="sb-vaz" onclick="sbModalKapat()">Vazgeç</button>
        <button type="submit" class="sb-ekle" id="sbEkleBtn">Ekle</button>
      </div>
    </form>
  </div>
</div>
@endif

<script>
var _sbTur = 'void';
var _turAdlar = @json($turler);

function sbSec(btn, tur) {
  _sbTur = tur;
  document.querySelectorAll('.sb-tab').forEach(function(t){ t.classList.remove('aktif'); });
  btn.classList.add('aktif');
  document.querySelectorAll('.sb-pane').forEach(function(p){
    p.style.display = p.dataset.tur === tur ? '' : 'none';
  });
}

function sbModalAc() {
  var modal = document.getElementById('sbModal');
  if (!modal) return;
  document.getElementById('sbEkleTur').value = _sbTur;
  document.getElementById('sbDlgTurAd').textContent = _turAdlar[_sbTur] || 'Sebep';
  document.getElementById('sbEkleMetin').value = '';
  modal.classList.add('ac');
  setTimeout(function(){ document.getElementById('sbEkleMetin').focus(); }, 60);
}

function sbModalKapat() {
  var modal = document.getElementById('sbModal');
  if (modal) modal.classList.remove('ac');
}

function sbEkleGonder(e) {
  if (e) e.preventDefault();
  var metin = (document.getElementById('sbEkleMetin').value || '').trim();
  if (!metin) { document.getElementById('sbEkleMetin').focus(); return false; }
  var btn = document.getElementById('sbEkleBtn');
  if (btn) btn.disabled = true;
  document.getElementById('sbEkleForm').submit();
  return false;
}

// Modal dışına tıklayınca kapat
document.addEventListener('click', function(e){
  var modal = document.getElementById('sbModal');
  if (modal && e.target === modal) sbModalKapat();
});
</script>
@endsection

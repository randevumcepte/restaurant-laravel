@extends('layout.app')
@section('rk_ikon', 'warning')
@section('baslik', 'Alerjen Matrisi')
@section('title', 'Alerjen Matrisi')

@section('content')
@php
  $ikonMap = ['Gluten'=>'🌾','Kabuklu deniz ürünleri'=>'🦐','Yumurta'=>'🥚','Balık'=>'🐟','Yer fıstığı'=>'🥜','Soya'=>'🫘','Süt (laktoz)'=>'🥛','Sert kabuklu yemiş'=>'🌰','Kereviz'=>'🥬','Hardal'=>'🌶️','Susam'=>'⚪','Sülfit'=>'🍷','Acı bakla (lupin)'=>'🌱','Yumuşakça'=>'🐌'];
  $eksik = $urunler->filter(fn($u)=>empty($u->alerjenler))->count();
@endphp
<style>
  #al{max-width:1000px;margin:0 auto}
  #al *{box-sizing:border-box}
  .al-msg{padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:13px;font-weight:600}
  .al-msg.ok{background:rgba(16,185,129,.12);color:#047857} .al-msg.er{background:rgba(244,63,94,.1);color:#9f1239}
  .al-ozet{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .al-k{flex:1;min-width:150px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 16px}
  .al-k .l{font-size:11px;color:var(--sub);font-weight:700}.al-k .b{font-size:22px;font-weight:800;margin-top:3px}
  .al-not{display:flex;gap:8px;align-items:flex-start;font-size:11.5px;color:var(--sub2);background:var(--card2);border-radius:12px;padding:11px 14px;margin-bottom:14px}
  .al-not .material-icons-outlined{font-size:16px;color:var(--amber);flex:none;margin-top:1px}
  .al-list{display:flex;flex-direction:column;gap:10px}
  .al-urun{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  .al-sum{list-style:none;cursor:pointer;display:flex;align-items:center;gap:12px;padding:13px 16px}
  .al-sum::-webkit-details-marker{display:none}
  .al-sum .ad{flex:1;min-width:0;font-weight:700;color:var(--ink);font-size:14px}
  .al-tags{display:flex;gap:5px;flex-wrap:wrap;justify-content:flex-end;max-width:60%}
  .al-tag{font-size:11px;background:color-mix(in srgb,#EF4444 12%,transparent);color:#DC2626;border-radius:20px;padding:2px 9px;font-weight:700;white-space:nowrap}
  .al-tag.yok{background:rgba(16,185,129,.12);color:#047857}
  .al-sum .material-icons-outlined{font-size:20px;color:var(--sub2)}
  .al-body{padding:0 16px 14px;border-top:1px solid var(--line)}
  .al-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:8px;margin:12px 0}
  .al-chk{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--ink);background:var(--card2);border:1px solid var(--line);border-radius:9px;padding:7px 10px;cursor:pointer}
  .al-chk input{width:16px;height:16px;accent-color:#EF4444}
  .al-save{color:#fff;background:linear-gradient(135deg,#EF4444,#DC2626);border:0;border-radius:9px;padding:9px 18px;font-weight:800;cursor:pointer;font-size:13px}
</style>
<div class="rk-body">
<div id="al">
  @if(session('ok'))<div class="al-msg ok">{{ session('ok') }}</div>@endif
  @if(session('hata'))<div class="al-msg er">{{ session('hata') }}</div>@endif

  <div class="al-ozet">
    <div class="al-k"><div class="l">Toplam ürün</div><div class="b">{{ $urunler->count() }}</div></div>
    <div class="al-k"><div class="l">Alerjen tanımlı</div><div class="b" style="color:#2FBE94">{{ $urunler->count() - $eksik }}</div></div>
    <div class="al-k"><div class="l">Eksik (tanımsız)</div><div class="b" style="color:{{ $eksik>0?'#F59E0B':'#2FBE94' }}">{{ $eksik }}</div></div>
  </div>

  <div class="al-not"><span class="material-icons-outlined">info</span><span>Her ürün için içerdiği alerjenleri işaretle. Müşteri sorduğunda ve denetimde bu bilgi zorunludur. "Alerjen içermiyor" demek için en az bir kez kaydet (boşsa <b>tanımsız</b> sayılır).</span></div>

  <div class="al-list">
    @forelse($urunler as $u)
      @php $sec = array_filter(array_map('trim', explode(',', (string)$u->alerjenler))); @endphp
      <details class="al-urun">
        <summary class="al-sum">
          <span class="ad">{{ $u->ad }}</span>
          <span class="al-tags">
            @if(count($sec))
              @foreach($sec as $s)<span class="al-tag">{{ $ikonMap[$s] ?? '⚠️' }} {{ $s }}</span>@endforeach
            @elseif($u->alerjenler !== null && $u->alerjenler === '')
              <span class="al-tag yok">✓ Alerjen yok</span>
            @else
              <span class="al-tag" style="background:color-mix(in srgb,#F59E0B 14%,transparent);color:#B45309">tanımsız</span>
            @endif
          </span>
          <span class="material-icons-outlined">expand_more</span>
        </summary>
        <div class="al-body">
          <form method="POST" action="/alerjen/kaydet">
            @csrf<input type="hidden" name="urun_id" value="{{ $u->id }}">
            <div class="al-grid">
              @foreach($alerjenler as $alr)
                <label class="al-chk"><input type="checkbox" name="alerjenler[]" value="{{ $alr }}" {{ in_array($alr,$sec)?'checked':'' }}>{{ $ikonMap[$alr] ?? '⚠️' }} {{ $alr }}</label>
              @endforeach
            </div>
            <button class="al-save" type="submit">Kaydet</button>
          </form>
        </div>
      </details>
    @empty
      <div style="text-align:center;padding:30px;color:var(--sub);font-size:13px">Ürün bulunamadı. Önce menü yönetiminden ürün ekle.</div>
    @endforelse
  </div>
</div>
</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'event_note')
@section('baslik', 'Ajanda & Hatırlatıcı')
@section('title', 'Ajanda')

@push('head')
<style>
  #aj{max-width:860px;margin:0 auto}
  #aj *{box-sizing:border-box}
  .aj-grid{display:grid;grid-template-columns:1fr 1.3fr;gap:16px;align-items:start}
  @media (max-width:760px){.aj-grid{grid-template-columns:1fr}}
  .aj-kart{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--golge);padding:18px 20px}
  .aj-kart h3{font-size:14px;font-weight:800;color:var(--ink);margin:0 0 14px;display:flex;align-items:center;gap:8px}
  .aj-kart h3 .material-icons-outlined{font-size:19px;color:var(--mor1)}
  .aj-lbl{display:block;font-size:12px;font-weight:700;color:var(--sub2);margin:12px 0 5px}
  .aj-inp,.aj-sel,textarea.aj-inp{width:100%;padding:10px 12px;border:1.5px solid var(--line);border-radius:11px;font-size:14px;background:var(--card);color:var(--ink);font-family:inherit}
  .aj-inp:focus,.aj-sel:focus{outline:none;border-color:var(--mor1)}
  .aj-tipler{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:4px}
  .aj-tip{flex:1;min-width:84px;text-align:center;padding:9px 6px;border:1.5px solid var(--line);border-radius:12px;font-size:12.5px;font-weight:700;color:var(--sub2);cursor:pointer;transition:all .13s;background:var(--card)}
  .aj-tip.sec{border-color:var(--mor1);color:var(--mor1);background:color-mix(in srgb,var(--mor1) 8%,var(--card))}
  .aj-kaydet{width:100%;margin-top:16px;background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;border:0;border-radius:12px;padding:12px;font-size:14px;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px}
  .aj-satir{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--line)}
  .aj-satir:last-child{border-bottom:0}
  .aj-ic{width:38px;height:38px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .aj-ic .material-icons-outlined{font-size:20px}
  .aj-sa{font-weight:800;color:var(--mavi);font-size:13px;min-width:46px}
  .aj-bx{display:inline-flex;align-items:center;gap:6px;padding:7px 11px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;border:1px solid var(--line);background:var(--card2);color:var(--sub2);text-decoration:none}
  .aj-bx.ok{color:var(--yesil);border-color:color-mix(in srgb,var(--yesil) 35%,var(--line))}
  .aj-bx.sil{color:var(--kirmizi)}
  .aj-bos{text-align:center;color:var(--sub);padding:26px;font-size:13.5px}
  .aj-flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-size:13px;font-weight:600}
  .aj-flash.ok{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:var(--yesil)}
  .aj-flash.err{background:color-mix(in srgb,var(--kirmizi) 14%,transparent);color:var(--kirmizi)}
  .aj-chk{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--sub2);margin-top:12px;cursor:pointer}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="aj">
  @php
    $rol = $personel->rol ?? '';
    $patronMu = in_array($rol, ['sahip', 'mudur']);
    $ikon = ['randevu' => 'event', 'toplanti' => 'groups', 'hatirlatma' => 'alarm', 'not' => 'sticky_note_2', 'dogumgunu' => 'cake'];
  @endphp

  @if (session('ok'))<div class="aj-flash ok">{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="aj-flash err">{{ session('hata') }}</div>@endif

  <div class="aj-grid">
    {{-- EKLEME FORMU --}}
    <div class="aj-kart">
      <h3><span class="material-icons-outlined">add_circle</span> Yeni Ekle</h3>
      <form method="POST" action="/ajanda/ekle" id="ajForm">
        @csrf
        <div class="aj-tipler">
          <div class="aj-tip sec" data-tip="hatirlatma" onclick="ajTip(this)">⏰ Hatırlatma</div>
          <div class="aj-tip" data-tip="randevu" onclick="ajTip(this)">📅 Randevu</div>
          <div class="aj-tip" data-tip="toplanti" onclick="ajTip(this)">👥 Toplantı</div>
          <div class="aj-tip" data-tip="not" onclick="ajTip(this)">📝 Not</div>
        </div>
        <input type="hidden" name="tip" id="ajTip" value="hatirlatma">

        <label class="aj-lbl">Başlık</label>
        <input class="aj-inp" name="baslik" placeholder="Örn: Doktor randevusu / Tedarikçi toplantısı" required maxlength="220">

        <div id="ajZaman">
          <div style="display:flex;gap:10px">
            <div style="flex:1.3"><label class="aj-lbl">Tarih</label><input class="aj-inp" type="date" name="tarih" value="{{ now()->toDateString() }}"></div>
            <div style="flex:1"><label class="aj-lbl">Saat</label><input class="aj-inp" type="time" name="saat" value="09:00"></div>
          </div>
          <label class="aj-lbl">Ne zaman hatırlatayım?</label>
          <select class="aj-sel" name="hatirlat_dk">
            <option value="0">Hatırlatma</option>
            <option value="15">15 dk önce</option>
            <option value="30" selected>30 dk önce</option>
            <option value="60">1 saat önce</option>
            <option value="120">2 saat önce</option>
            <option value="1440">1 gün önce</option>
          </select>
        </div>

        <label class="aj-lbl">Açıklama / Not (opsiyonel)</label>
        <textarea class="aj-inp" name="aciklama" rows="2" placeholder="Örn: Eşimin doğum günü, gül almayı unutma 🌹"></textarea>

        @if ($patronMu)
        <label class="aj-chk"><input type="checkbox" name="isletme" value="1"> Bu işletme geneli olsun (tüm yöneticiler görsün)</label>
        @endif

        <button class="aj-kaydet" type="submit"><span class="material-icons-outlined" style="font-size:19px">add</span> Ajandaya Ekle</button>
      </form>
    </div>

    {{-- LİSTE --}}
    <div class="aj-kart">
      <h3><span class="material-icons-outlined">upcoming</span> Yaklaşanlar</h3>
      @forelse ($liste as $a)
        <div class="aj-satir">
          <div class="aj-ic"><span class="material-icons-outlined">{{ $ikon[$a->tip] ?? 'event' }}</span></div>
          @if ($a->baslar_at)<span class="aj-sa">{{ \Carbon\Carbon::parse($a->baslar_at)->format('d.m H:i') }}</span>@endif
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;font-size:13.5px;color:var(--ink)">{{ $a->baslik }}</div>
            @if ($a->aciklama)<div style="font-size:11.5px;color:var(--sub)">{{ \Illuminate\Support\Str::limit($a->aciklama, 70) }}</div>@endif
            @if (is_null($a->personel_id))<span style="font-size:10px;font-weight:700;color:var(--mavi)">· işletme geneli</span>@endif
          </div>
          <form method="POST" action="/ajanda/durum" style="display:inline"><!--
            -->@csrf<input type="hidden" name="id" value="{{ $a->id }}"><input type="hidden" name="durum" value="tamam"><button class="aj-bx ok" type="submit">✓ Bitti</button></form>
          <form method="POST" action="/ajanda/sil" style="display:inline" onsubmit="return confirm('Silinsin mi?')"><!--
            -->@csrf<input type="hidden" name="id" value="{{ $a->id }}"><button class="aj-bx sil" type="submit"><span class="material-icons-outlined" style="font-size:16px">delete</span></button></form>
        </div>
      @empty
        <div class="aj-bos">Henüz bir şey yok. Soldan randevu, not veya hatırlatma ekle.</div>
      @endforelse

      @if (count($gecmis))
      <div style="font-size:11px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.3px;margin:16px 0 4px">Geçmiş</div>
      @foreach ($gecmis as $g)
        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;font-size:12.5px;color:var(--sub);border-bottom:1px solid var(--line)">
          <span class="material-icons-outlined" style="font-size:16px;color:{{ $g->durum==='tamam'?'var(--yesil)':'var(--sub)' }}">{{ $g->durum==='tamam'?'check_circle':'cancel' }}</span>
          <span style="flex:1;text-decoration:line-through;opacity:.8">{{ $g->baslik }}</span>
          <span style="font-size:10.5px">{{ $g->durum==='tamam'?'bitti':'iptal' }}</span>
        </div>
      @endforeach
      @endif
    </div>
  </div>
</div>
</div>

@push('scripts')
<script>
function ajTip(el){
  document.querySelectorAll('.aj-tip').forEach(function(t){ t.classList.remove('sec'); });
  el.classList.add('sec');
  document.getElementById('ajTip').value = el.dataset.tip;
  // Not tipinde zaman alanlarını gizle
  document.getElementById('ajZaman').style.display = (el.dataset.tip === 'not') ? 'none' : 'block';
}
</script>
@endpush
@endsection

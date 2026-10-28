@extends('layout.app')
@section('rk_ikon', 'verified_user')
@section('baslik', 'AI Eylem Günlüğü')
@section('title', 'AI Eylem Günlüğü')
@section('rk_alt', 'Legal defense log · hash chain korunur · ' . $toplamSatir . ' kayıt')

@push('head')
<style>
#ael{}
.ael-ust{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.ael-dogrula{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:12px;background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:0;cursor:pointer;font-size:13px;font-weight:800;box-shadow:0 4px 14px rgba(16,185,129,.28);transition:transform .14s}
.ael-dogrula:hover{transform:translateY(-1px)}
.ael-dogrula.hasarli{background:linear-gradient(135deg,#F43F5E,#E11D48);box-shadow:0 4px 14px rgba(244,63,94,.3)}
.ael-pdf{display:inline-flex;align-items:center;gap:6px;padding:10px 15px;border-radius:12px;background:var(--card);border:1.5px solid var(--line);color:var(--ink);font-size:13px;font-weight:700;cursor:pointer;text-decoration:none}
.ael-pdf:hover{border-color:var(--mor1);color:var(--mor1)}
.ael-filtre{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:12px;display:flex;gap:9px;flex-wrap:wrap;align-items:end}
.ael-filtre .alan label{font-size:11px;color:var(--sub);font-weight:600;display:block;margin-bottom:3px}
.ael-filtre input,.ael-filtre select{padding:8px 10px;border:1px solid var(--line);border-radius:9px;background:var(--card2);color:var(--ink);font-size:13px;min-width:150px}
.ael-filtre button{padding:9px 14px;border-radius:10px;background:var(--mor1);color:#fff;border:0;cursor:pointer;font-size:12.5px;font-weight:700}
.ael-tablo{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:var(--golge)}
.ael-tablo table{width:100%;border-collapse:collapse;font-size:13px}
.ael-tablo thead th{background:var(--card2);color:var(--sub2);font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;text-align:left;padding:11px 13px;position:sticky;top:0}
.ael-tablo tbody td{padding:10px 13px;border-top:1px solid color-mix(in srgb,var(--line) 60%,transparent);color:var(--ink);vertical-align:middle}
.ael-tablo tbody tr:first-child td{border-top:0}
.ael-tablo tbody tr{cursor:pointer;transition:background .1s}
.ael-tablo tbody tr:hover{background:color-mix(in srgb,var(--mor1) 5%,transparent)}
.ael-roz{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;color:#fff}
.ael-roz.bekliyor{background:#64748b}
.ael-roz.onaylandi{background:#10B981}
.ael-roz.reddedildi{background:#F59E0B}
.ael-roz.zamanasimi{background:#94A3B8}
.ael-roz.yurut-ok{background:#4F46E5}
.ael-roz.yurut-hata{background:#F43F5E}
.ael-modul{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10.5px;font-weight:800;letter-spacing:.3px;text-transform:uppercase;background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)}
.ael-ozet{max-width:420px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ael-imza{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;color:var(--sub2);letter-spacing:-.3px}

/* Detay modal */
.ael-mod-bg{display:none;position:fixed;inset:0;background:rgba(10,15,30,.55);z-index:9999;align-items:center;justify-content:center;padding:16px}
.ael-mod-bg.acik{display:flex}
.ael-mod{background:var(--card);border-radius:18px;max-width:720px;width:100%;max-height:90vh;overflow-y:auto;padding:22px}
.ael-mod h3{font-size:17px;font-weight:800;margin:0 0 12px;color:var(--ink);display:flex;align-items:center;gap:9px}
.ael-mod .kapa{margin-left:auto;background:none;border:0;cursor:pointer;color:var(--sub);font-size:22px;line-height:1}
.ael-detay-row{display:grid;grid-template-columns:140px 1fr;gap:12px;padding:8px 0;border-top:1px solid var(--line);font-size:13px}
.ael-detay-row:first-of-type{border-top:0}
.ael-detay-row .lbl{color:var(--sub);font-weight:600;font-size:12px}
.ael-detay-row .val{color:var(--ink);word-break:break-word}
.ael-detay-row .val.mono{font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11.5px;color:var(--sub2)}
.ael-olay-liste{margin-top:14px;padding-top:14px;border-top:2px solid var(--line)}
.ael-olay-liste h4{font-size:12.5px;font-weight:800;color:var(--sub2);letter-spacing:.3px;text-transform:uppercase;margin:0 0 10px;display:flex;align-items:center;gap:6px}
.ael-olay-sat{display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-top:1px solid var(--line);font-size:12.5px}
.ael-olay-sat:first-child{border-top:0}
.ael-olay-sat .zaman{font-family:ui-monospace,Menlo,Consolas,monospace;color:var(--sub);font-size:11px;min-width:110px}
.ael-olay-sat .tipR{font-weight:800;font-size:11px;padding:2px 8px;border-radius:12px;color:#fff;letter-spacing:.3px}
.ael-olay-sat .aci{color:var(--sub);flex:1}
.ael-dogru-sonuc{margin-left:10px;font-size:12.5px;font-weight:700}
.ael-dogru-sonuc.ok{color:#10B981}
.ael-dogru-sonuc.hasar{color:#F43F5E}
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="ael">

  <div class="ael-ust">
    <button class="ael-dogrula" id="ael-dogrula-btn" onclick="aelZincirDogrula()">
      <span class="material-icons-outlined" style="font-size:18px">verified</span> Zinciri Doğrula
    </button>
    <a class="ael-pdf" href="/ai-eylem-log/pdf?bas={{ $basTarih }}&bit={{ $bitTarih }}" target="_blank">
      <span class="material-icons-outlined" style="font-size:17px">picture_as_pdf</span> PDF olarak indir
    </a>
    <span class="ael-dogru-sonuc" id="ael-dogru-sonuc"></span>
    <div style="margin-left:auto;color:var(--sub);font-size:12px">
      <span class="material-icons-outlined" style="font-size:14px;vertical-align:-2px;color:var(--yesil)">lock</span>
      Her satır SHA256 ile zincirlenir · immutable
    </div>
  </div>

  <form method="GET" class="ael-filtre">
    <div class="alan"><label>Modül</label>
      <select name="modul">
        <option value="">Hepsi</option>
        <option value="ik" @if($modulFil==='ik') selected @endif>İK</option>
        <option value="satinalma" @if($modulFil==='satinalma') selected @endif>Satınalma</option>
        <option value="patron" @if($modulFil==='patron') selected @endif>Patron</option>
      </select>
    </div>
    <div class="alan"><label>Karar</label>
      <select name="karar">
        <option value="">Hepsi</option>
        <option value="onaylandi" @if($kararFil==='onaylandi') selected @endif>Onaylandı</option>
        <option value="reddedildi" @if($kararFil==='reddedildi') selected @endif>Vazgeçildi</option>
        <option value="bekliyor" @if($kararFil==='bekliyor') selected @endif>Bekliyor</option>
        <option value="zamanasimi" @if($kararFil==='zamanasimi') selected @endif>Zaman Aşımı</option>
      </select>
    </div>
    <div class="alan"><label>Kullanıcı</label>
      <select name="kullanici_id">
        <option value="0">Hepsi</option>
        @foreach ($kullanicilar as $k)
          <option value="{{ $k->kullanici_id }}" @if($kulFil==$k->kullanici_id) selected @endif>{{ $k->kullanici_ad }}</option>
        @endforeach
      </select>
    </div>
    <div class="alan"><label>Başlangıç</label><input type="date" name="bas" value="{{ $basTarih }}"></div>
    <div class="alan"><label>Bitiş</label><input type="date" name="bit" value="{{ $bitTarih }}"></div>
    <button type="submit">Filtrele</button>
  </form>

  <div class="ael-tablo">
    <table>
      <thead>
        <tr>
          <th style="width:120px">TARİH</th>
          <th style="width:90px">MODÜL</th>
          <th>ÖZET</th>
          <th style="width:130px">KULLANICI</th>
          <th style="width:110px">KARAR</th>
          <th style="width:110px">SONUÇ</th>
          <th style="width:120px">IP</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($satirlar as $s)
          <tr onclick="aelDetay({{ $s->id }})">
            <td style="color:var(--sub);font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11.5px">{{ \Carbon\Carbon::parse($s->created_at)->format('d.m H:i:s') }}</td>
            <td><span class="ael-modul">{{ $s->modul }}</span></td>
            <td>
              <div style="font-weight:600;color:var(--ink)">{{ $s->intent }}</div>
              <div class="ael-ozet" style="color:var(--sub);font-size:12px;margin-top:2px">{{ $s->ozet }}</div>
            </td>
            <td style="font-size:12px">{{ $s->kullanici_ad }}<br><span style="color:var(--sub2)">{{ $s->kullanici_rol }}</span></td>
            <td>
              <span class="ael-roz {{ $s->karar }}">
                @if($s->karar==='onaylandi'){{ '✓ ONAY' }}@elseif($s->karar==='reddedildi'){{ '✕ VAZGEÇ' }}@elseif($s->karar==='zamanasimi'){{ '⌛ ZAMANAŞIMI' }}@else{{ '… BEKLİYOR' }}@endif
              </span>
            </td>
            <td>
              @if ($s->yurut_ok === true)<span class="ael-roz yurut-ok">başarılı</span>
              @elseif ($s->yurut_ok === false)<span class="ael-roz yurut-hata" title="{{ $s->yurut_mesaj }}">HATA</span>
              @else<span style="color:var(--sub2);font-size:11px">—</span>
              @endif
            </td>
            <td style="font-family:ui-monospace,Menlo,Consolas,monospace;font-size:11px;color:var(--sub2)">{{ $s->ip_adres }}</td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--sub)">Bu kriterlere uygun kayıt bulunamadı.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
</div>

{{-- Detay modal --}}
<div class="ael-mod-bg" id="aelMod" onclick="if(event.target===this)this.classList.remove('acik')">
  <div class="ael-mod">
    <h3><span class="material-icons-outlined" style="color:var(--mor1)">receipt_long</span> AI Eylem Detayı <button class="kapa" onclick="document.getElementById('aelMod').classList.remove('acik')">×</button></h3>
    <div id="aelModIc"><div style="text-align:center;color:var(--sub);padding:30px">Yükleniyor...</div></div>
  </div>
</div>

@push('scripts')
<script>
async function aelZincirDogrula(){
  const btn = document.getElementById('ael-dogrula-btn');
  const sonuc = document.getElementById('ael-dogru-sonuc');
  btn.disabled = true;
  sonuc.textContent = 'Kontrol ediliyor...';
  sonuc.className = 'ael-dogru-sonuc';
  try{
    const r = await fetch('/api/ai-eylem/zincir-dogrula');
    const j = await r.json();
    if (j.ok && j.durum.saglam) {
      sonuc.textContent = '✓ Zincir sağlam · '+j.durum.sayi+' satır doğrulandı';
      sonuc.className = 'ael-dogru-sonuc ok';
      btn.classList.remove('hasarli');
    } else {
      sonuc.textContent = '⚠ HASAR TESPİT EDİLDİ · İlk kırık satır ID: '+(j.durum.ilk_kirik_id||'?');
      sonuc.className = 'ael-dogru-sonuc hasar';
      btn.classList.add('hasarli');
    }
  } catch(e){
    sonuc.textContent = 'Hata: '+(e.message||'bağlantı');
    sonuc.className = 'ael-dogru-sonuc hasar';
  }
  btn.disabled = false;
}

async function aelDetay(id){
  const m = document.getElementById('aelMod');
  const ic = document.getElementById('aelModIc');
  ic.innerHTML = '<div style="text-align:center;color:var(--sub);padding:30px">Yükleniyor...</div>';
  m.classList.add('acik');
  try{
    const r = await fetch('/api/ai-eylem/detay?id='+id);
    const j = await r.json();
    if (!j.ok) { ic.innerHTML = '<div style="color:var(--kirmizi)">Yüklenemedi.</div>'; return; }
    const o = j.oneri;
    let params = {};
    try { params = JSON.parse(o.params||'{}'); } catch(e){}
    let h = '';
    h += drow('Tarih', o.created_at);
    h += drow('Modül', '<span class="ael-modul">'+esc(o.modul)+'</span>');
    h += drow('Niyet (intent)', '<code>'+esc(o.intent)+'</code>');
    h += drow('Özet', esc(o.ozet||''));
    h += drow('Parametreler', '<pre style="margin:0;padding:8px;background:var(--card2);border-radius:8px;font-size:11.5px;overflow-x:auto">'+esc(JSON.stringify(params, null, 2))+'</pre>');
    if (o.ai_yaniti) h += drow('AI Yanıtı', esc(o.ai_yaniti));
    h += drow('Kullanıcı', esc(o.kullanici_ad)+' ('+esc(o.kullanici_rol)+') · ID '+o.kullanici_id);
    h += drow('IP · UA', '<div class="val mono">'+esc(o.ip_adres||'-')+'</div><div class="val mono" style="font-size:10.5px;margin-top:3px">'+esc(o.user_agent||'')+'</div>');
    h += drow('Önceki İmza', '<div class="val mono">'+o.onceki_imza+'</div>');
    h += drow('Bu Satır İmza', '<div class="val mono" style="color:var(--mor1)"><b>'+o.imza+'</b></div>');
    // Olaylar
    h += '<div class="ael-olay-liste"><h4><span class="material-icons-outlined" style="font-size:15px">timeline</span> Olay Zinciri</h4>';
    h += '<div class="ael-olay-sat"><span class="zaman">'+o.created_at+'</span><span class="tipR" style="background:#4F46E5">önerildi</span><span class="aci">AI öneri oluşturuldu</span></div>';
    for (const ev of j.olaylar) {
      let rnk = '#64748b', txt = ev.tip;
      if (ev.tip==='onaylandi'){ rnk='#10B981'; txt='onaylandı'; }
      else if (ev.tip==='reddedildi'){ rnk='#F59E0B'; txt='vazgeçildi'; }
      else if (ev.tip==='zamanasimi'){ rnk='#94A3B8'; txt='zaman aşımı'; }
      else if (ev.tip==='yurutuldu'){ rnk='#059669'; txt='yürütüldü ✓'; }
      else if (ev.tip==='yurutme_hatasi'){ rnk='#F43F5E'; txt='yürütme hatası'; }
      h += '<div class="ael-olay-sat"><span class="zaman">'+ev.created_at+'</span><span class="tipR" style="background:'+rnk+'">'+txt+'</span><span class="aci">'+esc(ev.kullanici_ad||'')+' ('+esc(ev.kullanici_rol||'')+')'+(ev.sonuc_mesaj?' · '+esc(ev.sonuc_mesaj):'')+'</span></div>';
    }
    h += '</div>';
    ic.innerHTML = h;
  } catch(e){ ic.innerHTML = '<div style="color:var(--kirmizi)">Hata: '+(e.message||'bağlantı')+'</div>'; }
}

function drow(lbl, val){ return '<div class="ael-detay-row"><div class="lbl">'+esc(lbl)+'</div><div class="val">'+val+'</div></div>'; }
function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
</script>
@endpush

@endsection

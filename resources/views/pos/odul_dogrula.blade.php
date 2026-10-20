@extends('layout.app')
@section('title', 'Ödül Doğrula')
@section('rk_ikon', 'qr_code_scanner')
@section('baslik', 'Ödül Doğrula')

@push('head')
<style>
#odroot{max-width:1180px;margin:0 auto}
/* ---- Giriş kartı (Flutter _girisKart) ---- */
#odroot .od-scan{display:block;width:100%;padding:34px 20px;border-radius:18px;text-align:center;cursor:default;
  background:linear-gradient(135deg,#7C3AED,#4F46E5);box-shadow:0 8px 18px rgba(124,58,237,.35)}
#odroot .od-scan .material-icons-outlined{color:#fff;font-size:54px}
#odroot .od-scan .b{color:#fff;font-size:18px;font-weight:800;margin-top:10px}
#odroot .od-scan .s{color:rgba(255,255,255,.72);font-size:12.5px;margin-top:4px;max-width:360px;margin-left:auto;margin-right:auto}
#odroot .od-ayr{display:flex;align-items:center;gap:10px;margin:18px 0 14px}
#odroot .od-ayr hr{flex:1;border:0;border-top:1px solid #e2e8f0;margin:0}
body.koyu #odroot .od-ayr hr{border-top-color:#2c3650}
#odroot .od-ayr span{font-size:12px;color:#64748b}
#odroot .od-kod{width:100%;height:60px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;
  text-align:center;font-size:20px;font-weight:800;letter-spacing:4px;color:#1e293b;text-transform:uppercase;outline:none}
#odroot .od-kod::placeholder{color:#94a3b8;letter-spacing:2px;font-weight:700}
body.koyu #odroot .od-kod{background:#1b2235;border-color:#2c3650;color:#e2e8f0}
#odroot .od-btn{width:100%;height:52px;border:0;border-radius:14px;background:#4F46E5;color:#fff;font-size:16px;font-weight:800;
  cursor:pointer;margin-top:14px;display:flex;align-items:center;justify-content:center;gap:8px}
#odroot .od-btn:disabled{opacity:.6;cursor:default}
/* ---- Bilgi kartı (Flutter _bilgiKart) ---- */
#odroot .od-bilgi{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:20px;text-align:center}
body.koyu #odroot .od-bilgi{background:#1b2235;border-color:#2c3650}
#odroot .od-bilgi .gift{color:#F6CE63;font-size:44px}
#odroot .od-bilgi .ad{color:#1e293b;font-size:20px;font-weight:800;margin-top:10px}
body.koyu #odroot .od-bilgi .ad{color:#e2e8f0}
#odroot .od-bilgi .puan{color:#64748b;font-size:14px;margin-top:4px}
#odroot .od-bilgi .acik{color:#94a3b8;font-size:13px;margin-top:8px}
#odroot .od-bilgi .cizgi{border:0;border-top:1px solid #e2e8f0;margin:18px 0}
body.koyu #odroot .od-bilgi .cizgi{border-top-color:#2c3650}
#odroot .od-satir{display:flex;align-items:center;gap:10px;padding:5px 0;text-align:left}
#odroot .od-satir .material-icons-outlined{color:#64748b;font-size:18px}
#odroot .od-satir .k{color:#64748b;font-size:13.5px}
#odroot .od-satir .v{margin-left:auto;font-weight:600;font-size:14px;color:#1e293b}
body.koyu #odroot .od-satir .v{color:#e2e8f0}
#odroot .od-yetersiz{color:#F43F5E;font-size:12.5px;font-weight:700;margin-top:10px}
#odroot .od-info{display:flex;gap:8px;align-items:flex-start;padding:12px;border-radius:12px;margin-top:10px;text-align:left}
#odroot .od-info .material-icons-outlined{font-size:18px}
#odroot .od-info .t{color:#475569;font-size:12.5px}
body.koyu #odroot .od-info .t{color:#94a3b8}
#odroot .od-alt{display:flex;gap:12px;margin-top:16px}
#odroot .od-vaz{flex:1;height:52px;border:1px solid #e2e8f0;background:transparent;border-radius:14px;color:#64748b;font-size:15px;cursor:pointer}
body.koyu #odroot .od-vaz{border-color:#2c3650}
#odroot .od-onay{flex:2;height:52px;border:0;border-radius:14px;color:#fff;font-size:16px;font-weight:800;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:8px}
#odroot .od-onay:disabled{cursor:default}
</style>
@endpush

@section('content')
<div id="odroot">
    {{-- Giriş durumu --}}
    <div id="odGiris">
        <div class="od-scan">
            <span class="material-icons-outlined">qr_code_scanner</span>
            <div class="b">Ödül Kodunu Okut</div>
            <div class="s">Müşterinin ekranındaki QR kodu kameraya göster</div>
        </div>
        <div class="od-ayr"><hr><span>veya kodu elle gir</span><hr></div>
        <input id="odKod" class="od-kod" placeholder="ÖRN: ZXLAGE" autocomplete="off"
               oninput="this.value=this.value.toUpperCase()" onkeydown="if(event.key==='Enter')odDogrula()">
        <button id="odDogBtn" class="od-btn" onclick="odDogrula()">
            <span id="odDogYazi">Doğrula</span>
        </button>
    </div>

    {{-- Bilgi durumu (doğrulama sonrası) --}}
    <div id="odBilgi" style="display:none"></div>
</div>

<script>
let _od = null, _odLoading = false, _odOnay = false;

function odTemizle(raw){
    let k = (raw||'').trim();
    if (k.indexOf('/') >= 0) k = k.split('/').pop();
    return k.trim().toUpperCase();
}
function odUyar(m){ alert(m); }

async function odDogrula(){
    if (_odLoading) return;
    const kod = odTemizle(document.getElementById('odKod').value);
    if (!kod){ odUyar('Kod gir veya QR okut'); return; }
    _odLoading = true;
    const btn = document.getElementById('odDogBtn'); btn.disabled = true;
    document.getElementById('odDogYazi').textContent = 'Doğrulanıyor…';
    try {
        const r = await api('/api/pos/odul-dogrula', { kod });
        if (r.ok === 1){ _od = r; odBilgiCiz(); }
        else { odUyar(r.hata || 'Kod doğrulanamadı'); }
    } catch(e){ odUyar('Bağlantı hatası'); }
    finally { _odLoading = false; btn.disabled = false; document.getElementById('odDogYazi').textContent = 'Doğrula'; }
}

function odBilgiCiz(){
    const b = _od, yeterli = b.yeterli === true, ad = b.adisyon || null;
    const esc = s => (s==null?'':String(s)).replace(/[&<>]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
    let h = '<div class="od-bilgi">';
    h += '<span class="material-icons-outlined gift">card_giftcard</span>';
    h += '<div class="ad">'+esc(b.odul_ad||'Ödül')+'</div>';
    h += '<div class="puan">'+(b.puan||0)+' puan</div>';
    if ((b.aciklama||'').trim()) h += '<div class="acik">'+esc(b.aciklama)+'</div>';
    h += '<hr class="cizgi">';
    h += odSatir('person', 'Müşteri', esc(b.musteri||'-'));
    h += odSatir('stars', 'Mevcut Puanı', (b.musteri_puan||0)+' puan', yeterli ? '#10B981' : '#F43F5E');
    if (ad) h += odSatir('receipt_long', 'Açık Hesap', '#'+ad.id+' · '+Math.round(ad.toplam)+'₺');
    if (!yeterli) h += '<div class="od-yetersiz">Müşterinin puanı yetersiz, onaylanamaz.</div>';
    h += '</div>';
    // info kutusu
    const renk = ad ? '#10B981' : '#F59E0B';
    const infoTxt = ad
        ? 'Onaylayınca ödül bu açık hesaba indirim/ikram olarak işlenir + puan düşülür.'
        : 'Açık hesap yok. Onaylayınca sadece puan düşülür (ödülü elle verin).';
    h += '<div class="od-info" style="background:'+renk+'1f"><span class="material-icons-outlined" style="color:'+renk+'">info</span><div class="t">'+infoTxt+'</div></div>';
    // alt butonlar
    h += '<div class="od-alt">';
    h += '<button class="od-vaz" onclick="odVazgec()">Vazgeç</button>';
    h += '<button id="odOnayBtn" class="od-onay" style="background:'+(yeterli?'#10B981':'#94a3b8')+'" '+(yeterli?'':'disabled')+' onclick="odOnayla()"><span class="material-icons-outlined">check_circle</span><span id="odOnayYazi">Onayla</span></button>';
    h += '</div>';
    document.getElementById('odBilgi').innerHTML = h;
    document.getElementById('odGiris').style.display = 'none';
    document.getElementById('odBilgi').style.display = 'block';
}
function odSatir(ikon, k, v, renk){
    return '<div class="od-satir"><span class="material-icons-outlined">'+ikon+'</span><span class="k">'+k+'</span><span class="v"'+(renk?' style="color:'+renk+'"':'')+'>'+v+'</span></div>';
}

function odVazgec(){
    _od = null;
    document.getElementById('odKod').value = '';
    document.getElementById('odBilgi').style.display = 'none';
    document.getElementById('odGiris').style.display = 'block';
}

async function odOnayla(){
    const b = _od; if (!b) return;
    if (b.yeterli !== true){ odUyar('Müşterinin puanı yetersiz'); return; }
    if (_odOnay) return;
    _odOnay = true;
    const btn = document.getElementById('odOnayBtn'); if (btn) btn.disabled = true;
    const yazi = document.getElementById('odOnayYazi'); if (yazi) yazi.textContent = 'Onaylanıyor…';
    try {
        const adId = (b.adisyon && b.adisyon.id) ? b.adisyon.id : null;
        const r = await api('/api/pos/odul-onayla', { kod: b.kod, adisyon_id: adId });
        if (r.ok === 1){ alert(r.mesaj || 'Ödül uygulandı'); odVazgec(); }
        else { odUyar(r.hata || 'Onaylanamadı'); }
    } catch(e){ odUyar('Bağlantı hatası'); }
    finally { _odOnay = false; if (btn) btn.disabled = false; if (yazi) yazi.textContent = 'Onayla'; }
}
</script>
@endsection

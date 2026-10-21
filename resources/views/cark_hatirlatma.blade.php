@extends('layout.app')
@section('title', 'Çarkıfelek Hatırlatma')
@section('rk_ikon', 'notifications_active')
@section('baslik', 'Çarkıfelek Hatırlatma')
@section('rk_alt', 'Müşterilere çarkı çevirmelerini hatırlatan otomatik bildirimler')
@section('content')

<style>
.hat-wrap {
    --hat-purple: #5C008E; --hat-purple-mid: #7B2FB8; --hat-purple-light: #9D5DC8;
    padding: 20px 4px;
}
.hat-wrap * { box-sizing: border-box; }
.hat-tabs { display:flex; gap:6px; background:#fff; border-radius:16px; padding:8px; box-shadow:0 4px 24px rgba(0,0,0,.08); margin-bottom:20px; flex-wrap:wrap; }
.hat-tab { flex:1; min-width:150px; text-align:center; padding:13px 10px; border-radius:12px; font-size:15px; font-weight:700; color:#636e72; text-decoration:none; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:8px; }
.hat-tab:hover { background:rgba(108,92,231,.07); color:#6c5ce7; }
.hat-tab.active { background:linear-gradient(135deg,#6c5ce7,#a29bfe); color:#fff; box-shadow:0 6px 18px rgba(108,92,231,.3); }
.hat-hero { background: linear-gradient(135deg, #5C008E 0%, #7B2FB8 50%, #9D5DC8 100%); color: #fff; border-radius: 16px; padding: 26px 28px; margin-bottom: 22px; box-shadow: 0 14px 36px rgba(92, 0, 142, 0.28); position: relative; overflow: hidden; }
.hat-hero::before { content:''; position:absolute; top:-60px; right:-60px; width:200px; height:200px; border-radius:50%; background: radial-gradient(circle, rgba(255,255,255,.12), transparent 70%); }
.hat-hero h1 { margin:0 0 6px; font-size:22px; font-weight:800; letter-spacing:-.3px; position:relative; z-index:1; }
.hat-hero p { margin:0; opacity:.92; font-size:13px; max-width: 720px; position:relative; z-index:1; }
.hat-master { background: #fff; border-radius: 14px; padding: 18px 22px; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(0,0,0,.05); border: 1px solid #ede5f5; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
.hat-master-info b { font-size: 15px; color: #2d1b4e; }
.hat-master-info p { margin: 4px 0 0; font-size: 12px; color: #6b7280; }
.hat-master-info .stat { display: inline-block; margin-top: 6px; padding: 4px 12px; background: #ede5f5; color: var(--hat-purple); border-radius: 20px; font-size: 12px; font-weight: 700; }
.hat-toggle { position: relative; width: 56px; height: 30px; cursor: pointer; flex-shrink: 0; }
.hat-toggle input { opacity: 0; width: 0; height: 0; }
.hat-toggle .slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 30px; transition: .25s; }
.hat-toggle .slider::before { content: ''; position: absolute; width: 24px; height: 24px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: .25s; box-shadow: 0 2px 6px rgba(0,0,0,.2); }
.hat-toggle input:checked + .slider { background: linear-gradient(135deg, var(--hat-purple), var(--hat-purple-light)); }
.hat-toggle input:checked + .slider::before { transform: translateX(26px); }
.hat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; margin-bottom: 18px; }
.hat-card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 4px 14px rgba(0,0,0,.05); border: 1px solid #ede5f5; transition: .2s; }
.hat-card:hover { box-shadow: 0 8px 22px rgba(92, 0, 142, .12); }
.hat-card__head { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid #f3f0fa; }
.hat-card__head > .hat-card__txt { flex: 1; min-width: 0; }
.hat-card.is-gizli { display: none; }
.hat-baslik-input { width: 100%; border: none; outline: none; font-size: 14px; font-weight: 700; color: #2d1b4e; background: transparent; padding: 2px 4px; border-radius: 6px; transition: background .15s; }
.hat-baslik-input:hover, .hat-baslik-input:focus { background: #f5f1fc; }
.hat-altyazi-input { width: 100%; border: none; outline: none; font-size: 12px; color: #7c6f97; font-style: italic; background: transparent; padding: 2px 4px; border-radius: 6px; transition: background .15s; margin-top: 2px; }
.hat-altyazi-input:hover, .hat-altyazi-input:focus { background: #f5f1fc; font-style: normal; }
.hat-sil-btn { width: 30px; height: 30px; border: none; cursor: pointer; background: #fee2e2; color: #991b1b; border-radius: 8px; font-size: 18px; font-weight: 700; line-height: 1; flex-shrink: 0; display: flex; align-items: center; justify-content: center; transition: .15s; }
.hat-sil-btn:hover { background: #fecaca; transform: scale(1.05); }
.hat-ekle-btn { width: 100%; padding: 16px; background: linear-gradient(135deg, #ede9fe, #fce7f3); color: #6b21a8; border: 2px dashed #c4b5fd; border-radius: 14px; font-size: 15px; font-weight: 700; cursor: pointer; margin-bottom: 18px; transition: .2s; }
.hat-ekle-btn:hover { background: linear-gradient(135deg, #ddd6fe, #fbcfe8); transform: translateY(-2px); }
.hat-card__icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--hat-purple), var(--hat-purple-light)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.hat-field { margin-bottom: 10px; }
.hat-field label { display: block; font-size: 11px; font-weight: 700; color: #6b7280; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .3px; }
.hat-field input[type="time"], .hat-field textarea { width: 100%; padding: 10px 12px; border: 2px solid #ede5f5; border-radius: 9px; background: #fff; font-size: 13px; transition: .2s; }
.hat-field input[type="time"]:focus, .hat-field textarea:focus { outline: none; border-color: var(--hat-purple-mid); box-shadow: 0 0 0 4px rgba(123, 47, 184, .12); }
.hat-field textarea { resize: vertical; min-height: 60px; }
.hat-days { background: #fff; border-radius: 14px; padding: 18px 22px; margin-bottom: 18px; box-shadow: 0 4px 14px rgba(0,0,0,.05); border: 1px solid #ede5f5; }
.hat-days h4 { margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #2d1b4e; }
.hat-days p { margin: 0 0 12px; font-size: 12px; color: #9ca3af; }
.hat-days__list { display: flex; gap: 8px; flex-wrap: wrap; }
.hat-days__chip { display: flex; align-items: center; gap: 6px; padding: 8px 14px; background: #f5f0fc; border: 2px solid transparent; border-radius: 10px; font-size: 13px; cursor: pointer; transition: .15s; color: #5b3a8c; font-weight: 600; }
.hat-days__chip input { display: none; }
.hat-days__chip.is-checked { background: linear-gradient(135deg, var(--hat-purple), var(--hat-purple-light)); color: #fff; border-color: var(--hat-purple); }
.hat-save { padding: 14px 32px; background: linear-gradient(135deg, var(--hat-purple), var(--hat-purple-mid)); color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; transition: .25s; letter-spacing: .3px; box-shadow: 0 8px 20px rgba(92, 0, 142, .35); }
.hat-save:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(92, 0, 142, .45); }
.hat-save:disabled { opacity: .55; cursor: not-allowed; transform: none; }
#hatToast { position: fixed; top: 20px; right: 20px; z-index: 10000; padding: 14px 22px; background: linear-gradient(135deg, #10b981, #059669); color: #fff; border-radius: 12px; font-weight: 700; box-shadow: 0 10px 30px rgba(0,0,0,.2); display: none; }
#hatToast.show { display: block; }
#hatToast.err { background: linear-gradient(135deg, #ef4444, #dc2626); }
</style>

<div class="hat-wrap">
    <div class="hat-tabs">
        <a href="/cark-yonetim" class="hat-tab"><span class="material-icons-outlined">casino</span> Çarkıfelek</a>
        <a href="/cark-kazananlar" class="hat-tab"><span class="material-icons-outlined">emoji_events</span> Çark Kazananlar</a>
        <a href="/cark-hatirlatma" class="hat-tab active"><span class="material-icons-outlined">notifications</span> Hatırlatma</a>
    </div>

    <div class="hat-hero">
        <h1>⏰ Çarkıfelek Hatırlatma Sistemi</h1>
        <p>Müşterilerinize gün içinde en fazla 3 bildirim gönderir, çarkı çevirmelerini hatırlatır. Her hatırlatmanın başlığını, saatini ve mesajını siz belirlersiniz. (Bildirim müşteri uygulamasına push olarak gider.)</p>
    </div>

    <div class="hat-master">
        <div class="hat-master-info">
            <b>Hatırlatma Sistemi</b>
            <p>Aktif olduğunda, açtığınız hatırlatmalar belirlenen saatlerde gönderilir.</p>
            <span class="stat">Bugün gönderilen: <b id="hat-bugun">{{ $bugun }}</b></span>
        </div>
        <label class="hat-toggle">
            <input type="checkbox" id="hat-aktif" {{ $ayar->aktif ? 'checked' : '' }}>
            <span class="slider"></span>
        </label>
    </div>

    <div class="hat-grid">
        @php
            $defaultBasliklar = [
                1 => ['baslik' => 'Sabah Hatırlatması', 'altyazi' => 'Güne hediyeyle başla', 'icon' => '🎡', 'saat' => '10:00', 'mesaj' => '🎡 Bugün çark hakkınız var, hediyeler sizi bekliyor!'],
                2 => ['baslik' => 'Öğleden Sonra',      'altyazi' => 'Çark hâlâ açık',       'icon' => '⏰', 'saat' => '15:00', 'mesaj' => '⏰ Çark hakkınız hâlâ duruyor — son birkaç saat!'],
                3 => ['baslik' => 'Akşam Hatırlatması', 'altyazi' => 'Bugünün son şansı',    'icon' => '🚨', 'saat' => '20:00', 'mesaj' => '🚨 Son saatler! Çarkı çevirmeyi unutmayın'],
            ];
            $slotlar = [];
            for ($i = 1; $i <= 3; $i++) {
                $aktif = isset($ayar->{"aktif_$i"}) ? (int) $ayar->{"aktif_$i"} : 1;
                $slotlar[$i] = [
                    'aktif'   => $aktif,
                    'baslik'  => $ayar->{"baslik_$i"}  ?? null,
                    'altyazi' => $ayar->{"altyazi_$i"} ?? null,
                    'saat'    => isset($ayar->{"saat_$i"}) ? substr($ayar->{"saat_$i"}, 0, 5) : $defaultBasliklar[$i]['saat'],
                    'mesaj'   => $ayar->{"mesaj_$i"} ?? $defaultBasliklar[$i]['mesaj'],
                    'def'     => $defaultBasliklar[$i],
                ];
            }
            $aktifSayisi = collect($slotlar)->where('aktif', 1)->count();
        @endphp

        @foreach($slotlar as $no => $sl)
        <div class="hat-card {{ $sl['aktif'] ? '' : 'is-gizli' }}" id="hat-card-{{ $no }}" data-slot="{{ $no }}">
            <div class="hat-card__head">
                <div class="hat-card__icon">{{ $sl['def']['icon'] }}</div>
                <div class="hat-card__txt">
                    <input type="text" class="hat-baslik-input" id="hat-baslik-{{ $no }}"
                           value="{{ $sl['baslik'] ?: $sl['def']['baslik'] }}"
                           placeholder="Başlık (ör. {{ $sl['def']['baslik'] }})" maxlength="80">
                    <input type="text" class="hat-altyazi-input" id="hat-altyazi-{{ $no }}"
                           value="{{ $sl['altyazi'] ?: $sl['def']['altyazi'] }}"
                           placeholder="Altyazı (ör. {{ $sl['def']['altyazi'] }})" maxlength="120">
                </div>
                <button class="hat-sil-btn" type="button" data-slot="{{ $no }}" title="Bu hatırlatmayı kaldır">×</button>
                <input type="hidden" class="hat-asama-aktif" data-asama="{{ $no }}" id="hat-aktif-{{ $no }}" value="{{ $sl['aktif'] }}">
            </div>
            <div class="hat-field">
                <label>Saat</label>
                <input type="time" id="hat-saat-{{ $no }}" value="{{ $sl['saat'] }}">
            </div>
            <div class="hat-field">
                <label>Mesaj</label>
                <textarea id="hat-mesaj-{{ $no }}" rows="2" maxlength="200">{{ $sl['mesaj'] }}</textarea>
            </div>
        </div>
        @endforeach
    </div>

    <button class="hat-ekle-btn" id="hat-ekle-btn" type="button" style="{{ $aktifSayisi >= 3 ? 'display:none;' : '' }}">
        + Yeni Hatırlatma Ekle
    </button>

    <div class="hat-days">
        <h4>Hangi günler gönderilmesin?</h4>
        <p>İşaretlediğiniz günler bildirim gönderilmez (örn. işletmenin kapalı olduğu günler).</p>
        <div class="hat-days__list">
            @php
                $haftaGunleri = [1=>'Pzt', 2=>'Sal', 3=>'Çar', 4=>'Per', 5=>'Cum', 6=>'Cmt', 7=>'Paz'];
                $skipDays = is_array($ayar->gonderim_gunleri) ? $ayar->gonderim_gunleri : [];
            @endphp
            @foreach($haftaGunleri as $g => $isim)
                <label class="hat-days__chip {{ in_array($g, $skipDays) ? 'is-checked' : '' }}">
                    <input type="checkbox" class="hat-gun" value="{{ $g }}" {{ in_array($g, $skipDays) ? 'checked' : '' }}>
                    {{ $isim }}
                </label>
            @endforeach
        </div>
    </div>

    <button class="hat-save" onclick="hatKaydet()">💾 Hatırlatma Ayarlarını Kaydet</button>
</div>

<div id="hatToast"></div>

<script>
const HAT_KAYDET_URL = '/cark-hatirlatma/kaydet';
const CSRF = (document.querySelector('meta[name="csrf-token"]')||{}).content || '';

function showHatToast(msg, err) {
    const t = document.getElementById('hatToast');
    t.textContent = msg; t.className = err ? 'err show' : 'show';
    setTimeout(() => t.classList.remove('show'), 2800);
}
document.querySelectorAll('.hat-days__chip input').forEach(cb => {
    cb.addEventListener('change', e => {
        const lbl = e.target.closest('label');
        if (e.target.checked) lbl.classList.add('is-checked'); else lbl.classList.remove('is-checked');
    });
});
document.getElementById('hat-ekle-btn').addEventListener('click', () => {
    for (let i = 1; i <= 3; i++) {
        const inp = document.getElementById('hat-aktif-' + i);
        if (parseInt(inp.value) !== 1) {
            inp.value = '1';
            document.getElementById('hat-card-' + i).classList.remove('is-gizli');
            guncelleEkleButonu(); return;
        }
    }
});
document.querySelectorAll('.hat-sil-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const slot = btn.dataset.slot;
        document.getElementById('hat-aktif-' + slot).value = '0';
        document.getElementById('hat-card-' + slot).classList.add('is-gizli');
        guncelleEkleButonu();
    });
});
function guncelleEkleButonu() {
    let aktifSayi = 0;
    for (let i = 1; i <= 3; i++) if (parseInt(document.getElementById('hat-aktif-' + i).value) === 1) aktifSayi++;
    document.getElementById('hat-ekle-btn').style.display = aktifSayi >= 3 ? 'none' : 'block';
}
async function hatKaydet() {
    const gunler = Array.from(document.querySelectorAll('.hat-gun:checked')).map(cb => parseInt(cb.value));
    const data = {
        aktif: document.getElementById('hat-aktif').checked ? 1 : 0,
        saat_1: document.getElementById('hat-saat-1').value,
        saat_2: document.getElementById('hat-saat-2').value,
        saat_3: document.getElementById('hat-saat-3').value,
        mesaj_1: document.getElementById('hat-mesaj-1').value.trim(),
        mesaj_2: document.getElementById('hat-mesaj-2').value.trim(),
        mesaj_3: document.getElementById('hat-mesaj-3').value.trim(),
        aktif_1: parseInt(document.getElementById('hat-aktif-1').value) || 0,
        aktif_2: parseInt(document.getElementById('hat-aktif-2').value) || 0,
        aktif_3: parseInt(document.getElementById('hat-aktif-3').value) || 0,
        baslik_1: document.getElementById('hat-baslik-1').value.trim(),
        baslik_2: document.getElementById('hat-baslik-2').value.trim(),
        baslik_3: document.getElementById('hat-baslik-3').value.trim(),
        altyazi_1: document.getElementById('hat-altyazi-1').value.trim(),
        altyazi_2: document.getElementById('hat-altyazi-2').value.trim(),
        altyazi_3: document.getElementById('hat-altyazi-3').value.trim(),
        gonderim_gunleri: gunler,
    };
    const btn = document.querySelector('.hat-save');
    btn.disabled = true; btn.textContent = '⏳ Kaydediliyor...';
    try {
        const r = await fetch(HAT_KAYDET_URL, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' },
            body: JSON.stringify(data)
        });
        const d = await r.json();
        btn.disabled = false; btn.textContent = '💾 Hatırlatma Ayarlarını Kaydet';
        if (d.success) showHatToast('✓ Hatırlatma ayarları kaydedildi');
        else showHatToast(d.message || 'Hata', true);
    } catch(e) {
        btn.disabled = false; btn.textContent = '💾 Hatırlatma Ayarlarını Kaydet';
        showHatToast('Bağlantı hatası', true);
    }
}
</script>
@endsection

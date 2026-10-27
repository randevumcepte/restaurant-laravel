@extends('layout.app')
@section('title', 'Stok / Malzeme')
@section('rk_ikon', 'inventory_2')
@section('baslik', 'Stok / Malzeme')

@php
    // ---- Veri hazırlığı (Flutter _yukle'nin server-side karşılığı) ----
    // $malzemeler, $birimler, $receteler → route'dan gelir (malzemeler.stok sub-query dahil)
    $birimMap     = $birimler;                                                    // route: birimler pluck(kisaltma, id)
    $katMap       = DB::table('malzeme_kategorileri')->pluck('ad', 'id');
    $katListesi   = DB::table('malzeme_kategorileri')->orderBy('ad')->get(['id', 'ad']);
    $birimListesi = DB::table('birimler')->orderBy('id')->get(['id', 'ad', 'kisaltma']);

    $toplamDeger = 0.0;
    $kritikSayi  = 0;
    $azSayi      = 0;
    $negatifSayi = 0;
    $takipsizSayi = 0;
    // $malzemeler already has `stok` and `son_hareket` sub-query columns (from route); use directly.
    $rows = $malzemeler->map(function ($m) use ($birimMap, $katMap, &$toplamDeger, &$kritikSayi, &$azSayi, &$negatifSayi, &$takipsizSayi) {
        $stok  = (float) ($m->stok ?? 0);
        $deger = max(0, $stok) * (float) $m->guncel_maliyet; // negatif stoğu değerlemeye katma
        $toplamDeger += $deger;
        $esik  = (float) $m->kritik_stok;
        $takipli = (int) ($m->stok_takipli ?? 1);
        if (!$takipli) $takipsizSayi++;
        // Durum: negatif = veri hatası (koyu kırmızı), kritik = kırmızı, az = turuncu, yeterli = yeşil
        if ($stok < 0) { $durum = 'negatif'; $negatifSayi++; }
        elseif ($esik > 0 && $stok <= $esik) { $durum = 'kirmizi'; $kritikSayi++; }
        elseif ($esik > 0 && $stok <= $esik * 1.5) { $durum = 'turuncu'; $azSayi++; }
        else { $durum = 'yesil'; }
        $durumMetin = ['negatif' => 'Veri Hatası', 'kirmizi' => 'Kritik', 'turuncu' => 'Az', 'yesil' => 'Yeterli'][$durum];
        // Progress bar yüzdesi: hedef = max(kritik*2, 1); negatif stokta 0, aşımda 100
        $hedef = $esik > 0 ? $esik * 2 : max(1, $stok);
        $yuzde = $hedef > 0 ? max(0, min(100, round($stok / $hedef * 100))) : 0;
        // Son hareket tarihi — insan okunabilir
        $sonHareket = null; $sonHareketMetin = 'Hareket yok'; $sonHareketGun = null;
        if (!empty($m->son_hareket)) {
            try {
                $sonHareket = \Carbon\Carbon::parse($m->son_hareket);
                $sonHareketGun = (int) floor($sonHareket->diffInDays(now()));
                $sonHareketMetin = $sonHareketGun === 0 ? 'Bugün' : ($sonHareketGun === 1 ? 'Dün' : $sonHareketGun . ' gün önce');
            } catch (\Throwable $e) {}
        }
        return (object) [
            'id'           => (int) $m->id,
            'ad'           => $m->ad,
            'kategori'     => $katMap[$m->kategori_id] ?? '—',
            'kategori_id'  => (int) ($m->kategori_id ?? 0),
            'birim'        => $birimMap[$m->temel_birim_id] ?? '',
            'temel_birim_id' => (int) $m->temel_birim_id,
            'guncel_maliyet' => (float) $m->guncel_maliyet,
            'kritik_stok'  => (float) $m->kritik_stok,
            'stok_takipli' => $takipli,
            'mevcut'       => round($stok, 3),
            'deger'        => round($deger, 2),
            'kritik'       => $durum === 'kirmizi',
            'durum'        => $durum,
            'durum_metin'  => $durumMetin,
            'yuzde'        => $yuzde,
            'son_hareket_metin' => $sonHareketMetin,
            'son_hareket_gun'   => $sonHareketGun,
        ];
    });

    // Kategoriye göre grupla (masaüstü ızgara için)
    $gruplar = $rows->groupBy('kategori')->sortKeys();

    // Personel token (JS Bearer için)
    $_pp = null;
    try { if (function_exists('_posPersonel')) $_pp = _posPersonel(); } catch (\Throwable $e) {}
    $webToken = $_pp ? (DB::table('personeller')->where('id', $_pp->id)->value('api_token') ?: '') : '';
    $duzenleyebilir = $_pp && in_array($_pp->rol ?? '', ['sahip', 'mudur']);

    // Yardımcı: Flutter _mik (tam sayıysa ondalıksız, değilse 2 hane)
    $mik = function ($v) {
        $v = (float) $v;
        return $v == round($v) ? number_format((int) $v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
    };
    $tl = fn($v) => number_format((float) $v, 0, ',', '.') . 'TL';

    // Renk hex'leri (durum → renk)
    $durumRenk = ['negatif' => '#991B1B', 'kirmizi' => '#DC2626', 'turuncu' => '#EA580C', 'yesil' => '#16A34A'];
@endphp

@push('head')
<style>
/* ===== STOK / MALZEME – Flutter StokScreen birebir eşdeğeri ===== */
#stk{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gold:#F6CE63;
  --dk:#DC2626;--dt:#EA580C;--dy:#16A34A;/* durum renkleri */
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc
}
body.koyu #stk{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650;--bg:#0B1020}

/* ---- Metrik şeridi (4 kompakt kutu) ---- */
#stk .stk-metrik{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
#stk .stk-mk{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px 14px;display:flex;align-items:center;gap:11px;transition:box-shadow .14s ease,transform .14s ease;cursor:default}
#stk .stk-mk.tiklanabilir{cursor:pointer}
#stk .stk-mk.tiklanabilir:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.08)}
#stk .stk-mk .mk-ikon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
#stk .stk-mk .mk-ikon .material-icons-outlined{font-size:20px}
#stk .stk-mk .mk-bilgi{flex:1;min-width:0}
#stk .stk-mk .mk-deger{font-size:20px;font-weight:800;color:var(--ink);line-height:1.05;letter-spacing:-.3px}
#stk .stk-mk .mk-etiket{font-size:11.5px;color:var(--sub);font-weight:600;margin-top:2px}
#stk .stk-mk.vurgu{background:linear-gradient(135deg,var(--mor1),var(--mavi));border-color:transparent}
#stk .stk-mk.vurgu .mk-deger,#stk .stk-mk.vurgu .mk-etiket{color:#fff}
#stk .stk-mk.vurgu .mk-etiket{opacity:.85}
#stk .stk-mk.vurgu .mk-ikon{background:rgba(255,255,255,.22);color:#fff}
@media(max-width:900px){#stk .stk-metrik{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){#stk .stk-mk .mk-deger{font-size:17px}#stk .stk-mk{padding:10px 11px}}

/* ---- Filtre çipleri ---- */
#stk .stk-cip-sat{display:flex;align-items:center;gap:7px;flex-wrap:wrap;margin-bottom:12px}
#stk .stk-cip{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:20px;background:var(--card);border:1px solid var(--line);color:var(--sub2);font-size:12.5px;font-weight:700;cursor:pointer;transition:all .13s ease;user-select:none}
#stk .stk-cip:hover{background:var(--card2)}
#stk .stk-cip.aktif{background:var(--mor1);border-color:var(--mor1);color:#fff}
#stk .stk-cip .sayi{font-size:11px;padding:1px 7px;border-radius:12px;background:var(--card2);color:var(--sub);font-weight:800;line-height:1.4}
#stk .stk-cip.aktif .sayi{background:rgba(255,255,255,.25);color:#fff}
#stk .stk-cip.kirmizi.aktif{background:#DC2626;border-color:#DC2626}
#stk .stk-cip.turuncu.aktif{background:#EA580C;border-color:#EA580C}
#stk .stk-cip.negatif.aktif{background:#991B1B;border-color:#991B1B}

/* ---- Arama alanı (Flutter TextField + prefixIcon) ---- */
#stk .stk-ara-wrap{position:relative;margin-bottom:14px}
#stk .stk-ara-wrap .material-icons-outlined{position:absolute;left:10px;top:50%;transform:translateY(-50%);
  font-size:18px;color:var(--sub);pointer-events:none}
#stk #stkAra{width:100%;padding:10px 12px 10px 36px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;box-sizing:border-box;transition:border-color .15s}
#stk #stkAra:focus{border-color:var(--mor1)}
body.koyu #stk #stkAra{background:var(--card2)}

/* ---- Masaüstü araç çubuğu (Flutter MasaustuSayfa araclar) ---- */
#stkAraBar{display:flex;align-items:center;gap:10px}
#stkAraBar input{padding:8px 12px 8px 32px;border:1px solid var(--line,#e2e8f0);border-radius:10px;
  background:var(--card2,#f1f5f9);color:var(--ink,#1e293b);font-size:13px;outline:none;
  width:220px;transition:border-color .15s;box-sizing:border-box}
#stkAraBar input:focus{border-color:var(--mor1,#7C3AED)}
#stkAraBar .ara-ic{position:relative}
#stkAraBar .ara-ic .material-icons-outlined{position:absolute;left:8px;top:50%;transform:translateY(-50%);
  font-size:16px;color:var(--sub,#64748b);pointer-events:none}

/* ---- Bölüm başlığı (Flutter MBolumBaslik) ---- */
#stk .stk-kat-baslik{display:flex;align-items:center;gap:8px;padding:12px 0 6px}
#stk .stk-kat-baslik .cubuk{width:4px;height:18px;border-radius:3px;background:var(--mor1);flex-shrink:0}
#stk .stk-kat-baslik .ad{color:var(--ink);font-size:15px;font-weight:700}
#stk .stk-kat-baslik .sayi{background:rgba(124,58,237,.12);color:var(--mor1);border-radius:10px;
  padding:1px 8px;font-size:11px;font-weight:700}

/* ---- Malzeme kartı (mobil — Flutter _malzemeKart) ---- */
#stk .stk-kart{display:flex;align-items:center;gap:10px;padding:13px;border-radius:14px;
  background:var(--card);border:1px solid var(--line);margin-bottom:8px;cursor:pointer;
  transition:box-shadow .1s;text-decoration:none}
#stk .stk-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.10)}
#stk .stk-kart.kritik-sinir{border-color:rgba(244,63,94,.5)}
#stk .stk-kart .sol{flex:1;min-width:0}
#stk .stk-kart .ad{color:var(--ink);font-size:15px;font-weight:700;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
#stk .stk-kart .alt{color:var(--sub);font-size:12px;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
#stk .stk-kart .sag{text-align:right;flex-shrink:0}
#stk .stk-kart .miktar{font-size:15px;font-weight:700}
#stk .stk-kart .deger-txt{font-size:11px;color:var(--sub);margin-top:1px}
#stk .stk-kart .material-icons-outlined.cev{color:var(--sub);font-size:20px;margin-left:4px;flex-shrink:0}

/* ---- Masaüstü ızgara kartı (modern) ---- */
#stk .stk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:12px;margin-bottom:18px}
#stk .stk-mkart{border-radius:14px;background:var(--card);border:1px solid var(--line);
  overflow:hidden;cursor:pointer;transition:box-shadow .14s ease,transform .14s ease,border-color .14s ease;position:relative}
#stk .stk-mkart:hover{box-shadow:0 6px 20px rgba(0,0,0,.09);transform:translateY(-2px)}
#stk .stk-mkart.d-negatif{border-color:rgba(153,27,27,.55)}
#stk .stk-mkart.d-kirmizi{border-color:rgba(220,38,38,.42)}
#stk .stk-mkart.d-turuncu{border-color:rgba(234,88,12,.35)}
#stk .stk-mkart .serit{height:5px}
#stk .stk-mkart .ic{padding:12px 14px 12px}
#stk .stk-mkart .satir-ust{display:flex;align-items:center;gap:6px;margin-bottom:8px}
#stk .stk-mkart .mkad{color:var(--ink);font-size:14.5px;font-weight:700;flex:1;min-width:0;
  overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* Progress bar */
#stk .stk-mkart .pbar-wrap{margin:6px 0 8px}
#stk .stk-mkart .pbar{height:6px;border-radius:4px;background:var(--card2);overflow:hidden;position:relative}
#stk .stk-mkart .pbar>div{height:100%;border-radius:4px;transition:width .6s cubic-bezier(.4,0,.2,1)}
#stk .stk-mkart .pbar-label{display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--sub);margin-top:4px;font-weight:600}
#stk .stk-mkart .pbar-label .mv{color:var(--ink);font-weight:800;font-size:13.5px}

/* Alt bilgi: değer + son hareket */
#stk .stk-mkart .alt-bilgi{display:flex;align-items:center;justify-content:space-between;gap:6px;font-size:11px;color:var(--sub);font-weight:600;padding-top:7px;border-top:1px dashed var(--line);margin-top:4px}
#stk .stk-mkart .alt-bilgi .material-icons-outlined{font-size:13px;vertical-align:-2px;opacity:.75;margin-right:2px}
#stk .stk-mkart .alt-bilgi .uyari{color:#EA580C}

/* Hover aksiyonları (hover butonları) */
#stk .stk-mkart .hover-aks{position:absolute;top:9px;right:9px;display:flex;gap:4px;opacity:0;transform:translateX(4px);transition:opacity .14s ease,transform .14s ease;z-index:3}
#stk .stk-mkart:hover .hover-aks{opacity:1;transform:translateX(0)}
#stk .stk-mkart .hover-aks button{width:30px;height:30px;border-radius:8px;border:none;background:var(--card2);color:var(--mor1);cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.08);transition:background .13s}
#stk .stk-mkart .hover-aks button:hover{background:var(--mor1);color:#fff}
#stk .stk-mkart .hover-aks button .material-icons-outlined{font-size:16px}
@media(hover:none){#stk .stk-mkart .hover-aks{display:none}} /* dokunmatikte gizle */

/* ---- Rozet (Flutter MRozet) ---- */
#stk .rozet{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}

/* ---- Miktar/kritik/değer satırları (Flutter _mSatir) ---- */
#stk .stk-msat{display:flex;justify-content:space-between;align-items:center;padding:1.5px 0}
#stk .stk-msat .etiket{color:var(--sub);font-size:12px}
#stk .stk-msat .val{font-size:12.5px;font-weight:700}

/* ---- Boş durum (Flutter Center / Text 'Malzeme yok') ---- */
#stk .stk-bos{text-align:center;padding:48px 16px;color:var(--sub)}
#stk .stk-bos .material-icons-outlined{font-size:48px;margin-bottom:10px;display:block}
#stk .stk-bos p{font-size:14px;margin:0}

/* ---- FAB (Flutter FloatingActionButton.extended) ---- */
#stkFab{position:fixed;bottom:24px;right:24px;display:flex;align-items:center;gap:8px;
  background:var(--mor1,#7C3AED);color:#fff;border:none;border-radius:20px;padding:14px 20px;
  font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 6px 20px rgba(124,58,237,.40);
  z-index:400;transition:opacity .15s}
#stkFab:hover{opacity:.88}

/* ---- Modal (Flutter showModalBottomSheet / detay + form) ---- */
.stk-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.48);z-index:700;
  align-items:flex-end;justify-content:center}
.stk-modal-bg.acik{display:flex}
.stk-modal-bg.merkez{align-items:center}
.stk-modal{background:var(--card);border-radius:20px 20px 0 0;width:100%;max-width:620px;
  max-height:92vh;overflow-y:auto;padding:16px}
.stk-modal-bg.merkez .stk-modal{border-radius:18px;margin:16px}
/* Masaustunde modal merkeze alinsin, bottom-sheet mobile'a kalsin */
@media(min-width:900px){
  .stk-modal-bg{align-items:center}
  .stk-modal{border-radius:18px;margin:16px;box-shadow:0 24px 70px rgba(0,0,0,.35)}
  .stk-modal .stk-mod-cizgi{display:none} /* mobil tutamagi (drag handle) desktopta gereksiz */
}
body.koyu .stk-modal{background:#0B1020}
.stk-modal .stk-mod-cizgi{width:40px;height:4px;border-radius:2px;background:var(--line);margin:0 auto 14px}
.stk-modal .stk-mod-baslik{color:var(--ink);font-size:18px;font-weight:800;margin-bottom:16px}
/* Detay bilgi satırı */
.stk-modal .det-ozet{color:var(--sub);font-size:13px;margin-bottom:12px}
/* Eylem butonları (Flutter _kbtn) */
.stk-modal .stk-btnler{display:flex;gap:8px;margin-bottom:12px}
.stk-modal .stk-btn-eylem{flex:1;padding:10px 0;border-radius:12px;border:1.5px solid transparent;
  display:flex;flex-direction:column;align-items:center;gap:2px;cursor:pointer;
  font-size:12px;font-weight:700;background:transparent;transition:opacity .12s}
.stk-modal .stk-btn-eylem .material-icons-outlined{font-size:20px}
.stk-modal .stk-btn-eylem:hover{opacity:.78}
/* Hareketler listesi */
.stk-modal .stk-hareketler h4{color:var(--ink);font-size:14px;font-weight:700;margin:0 0 6px}
.stk-modal .stk-hareket-satir{display:flex;align-items:flex-start;gap:8px;padding:5px 0}
.stk-modal .stk-hareket-satir .material-icons-outlined{font-size:16px;flex-shrink:0;margin-top:1px}
.stk-modal .stk-hareket-satir .h-bilgi{flex:1;min-width:0}
.stk-modal .stk-hareket-satir .h-ad{color:var(--ink);font-size:13px}
.stk-modal .stk-hareket-satir .h-alt{color:var(--sub);font-size:11px;margin-top:1px}
.stk-modal .stk-hareket-satir .h-mik{font-size:13px;font-weight:700;flex-shrink:0}
/* Form alanları (Flutter TextField _dec) */
.stk-modal .stk-field{display:flex;flex-direction:column;gap:3px;margin-bottom:10px}
.stk-modal .stk-field label{color:var(--sub);font-size:12px;font-weight:600}
.stk-modal .stk-field input,.stk-modal .stk-field select{
  padding:11px 12px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s;
  width:100%;box-sizing:border-box}
body.koyu .stk-modal .stk-field input,body.koyu .stk-modal .stk-field select{background:#161C2E}
.stk-modal .stk-field input:focus,.stk-modal .stk-field select:focus{border-color:var(--mor1)}
.stk-modal .stk-row{display:flex;gap:8px}
.stk-modal .stk-row .stk-field{flex:1}
/* Switch (Flutter SwitchListTile) */
.stk-modal .stk-switch{display:flex;align-items:center;justify-content:space-between;padding:8px 0}
.stk-modal .stk-switch label{color:var(--ink);font-size:14px}
.stk-modal .stk-switch input[type=checkbox]{width:38px;height:20px;cursor:pointer;accent-color:var(--yesil)}
/* Kaydet butonu (Flutter FilledButton) */
.stk-modal .stk-kaydet{width:100%;padding:14px;border:none;border-radius:12px;background:var(--mor1);
  color:#fff;font-size:15px;font-weight:700;cursor:pointer;transition:opacity .15s;margin-top:4px}
.stk-modal .stk-kaydet:hover{opacity:.88}
/* AlertDialog (Flutter haraket/kategori dialog) */
#stkHareketDialog,#stkKatDialog{display:none;position:fixed;inset:0;background:rgba(0,0,0,.48);z-index:900;
  align-items:center;justify-content:center}
#stkHareketDialog.acik,#stkKatDialog.acik{display:flex}
#stkHareketDialog .dlg,#stkKatDialog .dlg{background:var(--card);border-radius:18px;padding:20px;
  width:100%;max-width:360px;margin:16px}
body.koyu #stkHareketDialog .dlg,body.koyu #stkKatDialog .dlg{background:#161C2E}
.dlg .dlg-baslik{color:var(--ink);font-size:17px;font-weight:700;margin-bottom:14px}
.dlg .dlg-btns{display:flex;justify-content:flex-end;gap:8px;margin-top:14px}
.dlg .dlg-vazgec{background:transparent;border:none;color:var(--sub);font-size:14px;
  font-weight:600;cursor:pointer;padding:8px 12px;border-radius:8px}
.dlg .dlg-kaydet{background:var(--mor1);border:none;color:#fff;font-size:14px;
  font-weight:700;cursor:pointer;padding:8px 18px;border-radius:8px}
/* Toast benzeri hata (Flutter SnackBar) */
#stkToast{position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1e293b;color:#fff;
  padding:11px 18px;border-radius:12px;font-size:13.5px;font-weight:600;z-index:1000;
  opacity:0;transition:opacity .25s;pointer-events:none;white-space:nowrap;max-width:90vw}
body.koyu #stkToast{background:#e2e8f0;color:#1e293b}
#stkToast.goruli{opacity:1}

/* ---- Yükleniyor (Flutter CircularProgressIndicator) ---- */
#stk .stk-yukl{text-align:center;padding:60px 0;color:var(--sub);font-size:14px}
#stk .stk-yukl .spin{display:inline-block;width:28px;height:28px;border:3px solid var(--line);
  border-top-color:var(--mor1);border-radius:50%;animation:stkSpin .7s linear infinite;vertical-align:middle;margin-right:8px}
@keyframes stkSpin{to{transform:rotate(360deg)}}
</style>
@endpush

@section('rk_araclar')
<div id="stkAraBar" style="display:none">
    <div class="ara-ic">
        <span class="material-icons-outlined">search</span>
        <input type="text" id="stkAraGenis" placeholder="Malzeme ara" oninput="stkFiltre(this.value)">
    </div>
    @if ($duzenleyebilir)
    <button onclick="stkMalzemeFormuAc(null)"
        style="display:flex;align-items:center;gap:6px;padding:8px 14px;background:var(--mor1,#7C3AED);border:none;
               border-radius:10px;color:#fff;font-size:13px;font-weight:700;cursor:pointer">
        <span class="material-icons-outlined" style="font-size:16px">add</span>Malzeme
    </button>
    @endif
</div>
@endsection

@section('rk_alt')
Toplam değer {{ $tl($toplamDeger) }}{{ $kritikSayi > 0 ? '  ·  ' . $kritikSayi . ' kritik' : '' }}
@endsection

@section('content')
<div id="stk">

    {{-- ===== METRİK ŞERİDİ (4 kompakt kutu) ===== --}}
    <div class="stk-metrik">
        <div class="stk-mk vurgu">
            <div class="mk-ikon"><span class="material-icons-outlined">payments</span></div>
            <div class="mk-bilgi">
                <div class="mk-deger">{{ $tl($toplamDeger) }}</div>
                <div class="mk-etiket">Toplam Stok Değeri</div>
            </div>
        </div>
        <div class="stk-mk tiklanabilir" onclick="stkCipSec('tumu')" title="Tümünü göster">
            <div class="mk-ikon" style="background:color-mix(in srgb,var(--mor1) 14%,transparent);color:var(--mor1)"><span class="material-icons-outlined">inventory_2</span></div>
            <div class="mk-bilgi">
                <div class="mk-deger">{{ $rows->count() }}</div>
                <div class="mk-etiket">Malzeme Sayısı</div>
            </div>
        </div>
        <div class="stk-mk tiklanabilir" onclick="stkCipSec('kirmizi')" title="Kritik stoğu filtrele">
            <div class="mk-ikon" style="background:color-mix(in srgb,#DC2626 14%,transparent);color:#DC2626"><span class="material-icons-outlined">warning_amber</span></div>
            <div class="mk-bilgi">
                <div class="mk-deger" style="color:{{ $kritikSayi>0?'#DC2626':'var(--ink)' }}">{{ $kritikSayi }}</div>
                <div class="mk-etiket">Kritik Stok</div>
            </div>
        </div>
        <div class="stk-mk tiklanabilir" onclick="stkCipSec('negatif')" title="Negatif stoğu filtrele">
            <div class="mk-ikon" style="background:color-mix(in srgb,#991B1B 14%,transparent);color:#991B1B"><span class="material-icons-outlined">error_outline</span></div>
            <div class="mk-bilgi">
                <div class="mk-deger" style="color:{{ $negatifSayi>0?'#991B1B':'var(--ink)' }}">{{ $negatifSayi }}</div>
                <div class="mk-etiket">Veri Hatası (Negatif)</div>
            </div>
        </div>
    </div>

    {{-- ===== ARAMA + FİLTRE ÇİPLERİ ===== --}}
    <div class="stk-ara-wrap" id="stkAraWrapMobil">
        <span class="material-icons-outlined">search</span>
        <input type="text" id="stkAraMobil" placeholder="Malzeme ara" oninput="stkFiltre(this.value)">
    </div>
    <div class="stk-cip-sat" id="stkCipSat">
        <span class="stk-cip aktif" data-fil="tumu" onclick="stkCipSec('tumu')">Tümü <span class="sayi">{{ $rows->count() }}</span></span>
        <span class="stk-cip negatif" data-fil="negatif" onclick="stkCipSec('negatif')">⚠ Veri Hatası <span class="sayi">{{ $negatifSayi }}</span></span>
        <span class="stk-cip kirmizi" data-fil="kirmizi" onclick="stkCipSec('kirmizi')">Kritik <span class="sayi">{{ $kritikSayi }}</span></span>
        <span class="stk-cip turuncu" data-fil="turuncu" onclick="stkCipSec('turuncu')">Az <span class="sayi">{{ $azSayi }}</span></span>
        <span class="stk-cip" data-fil="takipsiz" onclick="stkCipSec('takipsiz')">Takipsiz <span class="sayi">{{ $takipsizSayi }}</span></span>
    </div>

    {{-- ===== LİSTE / IZGARA ===== --}}
    <div id="stkListeWrap">

        @if ($rows->isEmpty())
            <div class="stk-bos">
                <span class="material-icons-outlined">inventory_2</span>
                <p>Malzeme yok. Sağ alttan ekleyin.</p>
            </div>
        @else

            {{-- -- Masaüstü ızgara (Flutter _masaustu → _mKart 4 sütun kategorili) -- --}}
            <div id="stkGenis" style="display:none">
                @foreach ($gruplar as $katAd => $katRows)
                <div class="stk-kat-baslik">
                    <div class="cubuk"></div>
                    <span class="ad">{{ $katAd }}</span>
                    <span class="sayi">{{ $katRows->count() }}</span>
                </div>
                <div class="stk-grid">
                    @foreach ($katRows as $m)
                    @php $renk = $durumRenk[$m->durum]; @endphp
                    <div class="stk-mkart d-{{ $m->durum }}" data-ad="{{ strtolower($m->ad) }}" data-dur="{{ $m->durum }}" data-takip="{{ $m->stok_takipli }}" onclick="stkDetayAc({{ $m->id }})">
                        <div class="serit" style="background:{{ $renk }}"></div>
                        @if ($duzenleyebilir)
                        <div class="hover-aks">
                            <button onclick="event.stopPropagation();stkHareketAc({{ $m->id }},'giris')" title="Stok Girişi"><span class="material-icons-outlined">add</span></button>
                            <button onclick="event.stopPropagation();stkHareketAc({{ $m->id }},'duzeltme')" title="Sayım Düzelt"><span class="material-icons-outlined">tune</span></button>
                        </div>
                        @endif
                        <div class="ic">
                            <div class="satir-ust">
                                <span class="mkad" title="{{ $m->ad }}">{{ $m->ad }}</span>
                                <span class="rozet" style="background:{{ $renk }}">{{ $m->durum_metin }}</span>
                            </div>
                            <div class="pbar-wrap">
                                <div class="pbar"><div style="width:{{ $m->yuzde }}%;background:{{ $renk }}"></div></div>
                                <div class="pbar-label">
                                    <span class="mv" style="color:{{ in_array($m->durum,['kirmizi','negatif','turuncu'])?$renk:'var(--ink)' }}">{{ $mik($m->mevcut) }} {{ $m->birim }}</span>
                                    <span>Kritik: {{ $mik($m->kritik_stok) }}</span>
                                </div>
                            </div>
                            <div class="alt-bilgi">
                                <span><span class="material-icons-outlined">payments</span>{{ $tl($m->deger) }}</span>
                                <span @if($m->son_hareket_gun !== null && $m->son_hareket_gun >= 7) class="uyari" @endif>
                                    <span class="material-icons-outlined">schedule</span>{{ $m->son_hareket_metin }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            {{-- -- Mobil liste -- --}}
            <div id="stkMobil">
                @foreach ($rows as $m)
                @php $renk = $durumRenk[$m->durum]; @endphp
                <div class="stk-kart {{ in_array($m->durum,['kirmizi','negatif']) ? 'kritik-sinir' : '' }}"
                     data-ad="{{ strtolower($m->ad) }}" data-dur="{{ $m->durum }}" data-takip="{{ $m->stok_takipli }}" onclick="stkDetayAc({{ $m->id }})">
                    <div class="sol">
                        <div class="ad">
                            <span>{{ $m->ad }}</span>
                            @if (in_array($m->durum, ['negatif','kirmizi']))
                            <span class="material-icons-outlined" style="color:{{ $renk }};font-size:16px">{{ $m->durum==='negatif'?'error_outline':'warning_amber' }}</span>
                            @endif
                        </div>
                        <div class="alt">{{ $m->kategori }} · {{ $tl($m->guncel_maliyet) }}/{{ $m->birim }} · {{ $m->son_hareket_metin }}</div>
                    </div>
                    <div class="sag">
                        <div class="miktar" style="color:{{ in_array($m->durum,['kirmizi','negatif']) ? $renk : 'var(--ink)' }}">
                            {{ $mik($m->mevcut) }} {{ $m->birim }}
                        </div>
                        <div class="deger-txt">{{ $tl($m->deger) }}</div>
                    </div>
                    <span class="material-icons-outlined cev">chevron_right</span>
                </div>
                @endforeach
            </div>

        @endif

    </div>{{-- #stkListeWrap --}}

</div>{{-- #stk --}}

{{-- ===== FAB (Flutter FloatingActionButton.extended) ===== --}}
@if ($duzenleyebilir)
<button id="stkFab" onclick="stkMalzemeFormuAc(null)">
    <span class="material-icons-outlined" style="font-size:20px">add</span>Malzeme
</button>
@endif

{{-- ===== DETAY MODAL (Flutter showModalBottomSheet → _detay) ===== --}}
<div class="stk-modal-bg" id="stkDetayModal" onclick="if(event.target===this)stkModKapat('stkDetayModal')">
    <div class="stk-modal" id="stkDetayIc">
        <div class="stk-mod-cizgi"></div>
        <div id="stkDetayBody">
            <div class="stk-yukl"><span class="spin"></span>Yükleniyor…</div>
        </div>
    </div>
</div>

{{-- ===== MALZEME FORM MODAL (Flutter _malzemeForm) ===== --}}
<div class="stk-modal-bg" id="stkFormModal" onclick="if(event.target===this)stkModKapat('stkFormModal')">
    <div class="stk-modal">
        <div class="stk-mod-cizgi"></div>
        <div class="stk-mod-baslik" id="stkFormBaslik">Yeni Malzeme</div>
        <div class="stk-field">
            <label>Malzeme adı <span style="font-weight:400;opacity:.7;font-size:11px">— yazmaya başla, listeden seç</span></label>
            <input type="text" id="stkFAd" placeholder="Malzeme adı (hazır listeden seç ya da yaz)" list="stkUrunKatalog" autocomplete="off">
            <datalist id="stkUrunKatalog"></datalist>
        </div>
        <div class="stk-row">
            <div class="stk-field">
                <label>Kategori</label>
                <select id="stkFKat">
                    <option value="">— seçiniz —</option>
                    @foreach ($katListesi as $k)
                    <option value="{{ $k->id }}">{{ $k->ad }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;align-items:flex-end;padding-bottom:10px">
                <button onclick="stkKatEkleAc()" title="Yeni kategori ekle"
                    style="background:rgba(79,70,229,.14);border:none;border-radius:8px;width:36px;height:36px;
                           cursor:pointer;color:#4F46E5;display:flex;align-items:center;justify-content:center">
                    <span class="material-icons-outlined" style="font-size:20px">add_circle_outline</span>
                </button>
            </div>
        </div>
        <div class="stk-field">
            <label>Temel / Stok Birimi</label>
            <select id="stkFBirim">
                @foreach ($birimListesi as $b)
                <option value="{{ $b->id }}">{{ $b->ad }} ({{ $b->kisaltma }})</option>
                @endforeach
            </select>
        </div>
        <div class="stk-row">
            <div class="stk-field">
                <label>Kritik stok</label>
                <input type="number" id="stkFKritik" placeholder="0" step="0.01" min="0">
            </div>
            <div class="stk-field" id="stkFMaliyetWrap">
                <label>Başlangıç ₺/birim</label>
                <input type="number" id="stkFMaliyet" placeholder="0.00" step="0.01" min="0">
            </div>
        </div>
        {{-- TEK EKRAN: yeni malzeme eklerken başlangıç stok + barkod da buradan (sadece eklemede görünür) --}}
        <div class="stk-row" id="stkFYeniAlanlar">
            <div class="stk-field">
                <label>Başlangıç stok <span style="color:var(--sub2,#94a3b8);font-weight:400">(ürettiğin/eldeki miktar)</span></label>
                <input type="number" id="stkFBaslangic" placeholder="0" step="0.01" min="0">
            </div>
            <div class="stk-field">
                <label>Barkod <span style="color:var(--sub2,#94a3b8);font-weight:400">(varsa — opsiyonel)</span></label>
                <input type="text" id="stkFBarkod" placeholder="Okut veya elle yaz" autocomplete="off">
            </div>
        </div>
        <div class="stk-switch">
            <label for="stkFTakip">Stok takibi yapılsın</label>
            <input type="checkbox" id="stkFTakip" checked>
        </div>
        <button class="stk-kaydet" onclick="stkFormKaydet()">Kaydet</button>
    </div>
</div>

{{-- ===== STOK HAREKET DIALOG (Flutter _hareketDialog — AlertDialog) ===== --}}
<div id="stkHareketDialog" onclick="if(event.target===this)stkHareketKapat()">
    <div class="dlg">
        <div class="dlg-baslik" id="stkHareketBaslik">Stok Girişi</div>
        <div class="stk-field">
            <label>Miktar (temel birim)</label>
            <input type="number" id="stkHMiktar" placeholder="Miktar" step="0.001">
        </div>
        <div class="stk-field">
            <label>Açıklama (opsiyonel)</label>
            <input type="text" id="stkHAciklama" placeholder="Açıklama">
        </div>
        <div class="dlg-btns">
            <button class="dlg-vazgec" onclick="stkHareketKapat()">Vazgeç</button>
            <button class="dlg-kaydet" onclick="stkHareketKaydet()">Kaydet</button>
        </div>
    </div>
</div>

{{-- ===== KATEGORİ EKLE DIALOG (Flutter _kategoriEkle) ===== --}}
<div id="stkKatDialog" onclick="if(event.target===this)this.classList.remove('acik')">
    <div class="dlg">
        <div class="dlg-baslik">Yeni Kategori</div>
        <div class="stk-field">
            <label>Kategori adı</label>
            <input type="text" id="stkKatAd" placeholder="Kategori adı">
        </div>
        <div class="dlg-btns">
            <button class="dlg-vazgec" onclick="document.getElementById('stkKatDialog').classList.remove('acik')">Vazgeç</button>
            <button class="dlg-kaydet" onclick="stkKatKaydet()">Ekle</button>
        </div>
    </div>
</div>

{{-- ===== TOAST ===== --}}
<div id="stkToast"></div>

@push('scripts')
<script>
// ──────────────────────────────────────────────
// STOK / MALZEME — Flutter StokScreen web eşdeğeri
// ──────────────────────────────────────────────
const STK_TOKEN = '{{ $webToken }}'; // personel api_token (Bearer)

// SİSTEM GENELİ ürün kataloğu -> malzeme adı için hazır öneri (datalist)
let STK_KATALOG = {}; // ad_lower -> {ad, kategori}
(function stkKatalogYukle(){
  fetch('/urun-katalog').then(r=>r.json()).then(j=>{
    if(!j || !j.urunler) return;
    const dl=document.getElementById('stkUrunKatalog'); if(!dl) return;
    dl.innerHTML = j.urunler.map(u=>`<option value="${(u.ad||'').replace(/"/g,'&quot;')}">${u.kategori||''}</option>`).join('');
    j.urunler.forEach(u=>{ if(u.ad) STK_KATALOG[u.ad.toLocaleLowerCase('tr')]={ad:u.ad,kategori:u.kategori}; });
  }).catch(()=>{});
})();
// Katalogdan ad seçilince, o ürünün kategorisini de otomatik seç (kategori adı eşleşirse)
document.addEventListener('input', function(e){
  if(e.target && e.target.id==='stkFAd'){
    const k = STK_KATALOG[(e.target.value||'').trim().toLocaleLowerCase('tr')];
    if(k && k.kategori){
      const sel=document.getElementById('stkFKat');
      if(sel){ for(const o of sel.options){ if((o.textContent||'').toLocaleLowerCase('tr')===k.kategori.toLocaleLowerCase('tr')){ sel.value=o.value; break; } } }
    }
  }
});
let stkDuzenleyebilir = {{ $duzenleyebilir ? 'true' : 'false' }};

// Server-render edilen veri (JS'de detay/hareket/formlarda kullanmak için)
let stkVeriler = {!! json_encode($rows->values()->map(fn($m) => [
    'id'            => $m->id,
    'ad'            => $m->ad,
    'kategori'      => $m->kategori,
    'kategori_id'   => $m->kategori_id,
    'birim'         => $m->birim,
    'temel_birim_id'=> $m->temel_birim_id,
    'guncel_maliyet'=> $m->guncel_maliyet,
    'kritik_stok'   => $m->kritik_stok,
    'stok_takipli'  => $m->stok_takipli,
    'mevcut'        => $m->mevcut,
    'deger'         => $m->deger,
    'kritik'        => $m->kritik,
]), 15, 512) !!};
let stkBirimler = {!! json_encode($birimListesi->map(fn($b) => ['id' => $b->id, 'ad' => $b->ad, 'kisaltma' => $b->kisaltma]), 15, 512) !!};
let stkKategoriler = {!! json_encode($katListesi->map(fn($k) => ['id' => $k->id, 'ad' => $k->ad]), 15, 512) !!};

// Hareket dialog state
let _stkHareketMalzemeId = null;
let _stkHareketTip = null;
// Form state
let _stkFormId = null;  // null=yeni, int=düzenle

// ── Yardımcılar ──────────────────────────────
function _stkMik(v) {
    v = parseFloat(v) || 0;
    var a = Math.abs(v);
    return a === Math.round(a) ? Math.round(a).toLocaleString('tr-TR') : a.toFixed(2).replace('.', ',');
}
function _stkTl(v) { return Math.round(parseFloat(v)||0).toLocaleString('tr-TR') + 'TL'; }
function _esc(s) {
    return (s == null ? '' : String(s)).replace(/[&<>"]/g, function(c){
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];
    });
}

// ── API çağrısı (Bearer token ile) ──────────────────────────────
async function stkApi(url, data) {
    const headers = {'Accept':'application/json','Authorization':'Bearer '+STK_TOKEN};
    let opt = {method:'GET', headers};
    if (data) {
        opt.method = 'POST';
        headers['Content-Type'] = 'application/json';
        headers['X-CSRF-TOKEN'] = window.csrf;
        opt.body = JSON.stringify(data);
    }
    const r = await fetch(url, opt);
    return r.json();
}

// ── Toast (Flutter SnackBar) ──────────────────────────────
function stkUyar(msg) {
    var t = document.getElementById('stkToast');
    t.textContent = msg;
    t.classList.add('goruli');
    clearTimeout(t._to);
    t._to = setTimeout(function(){ t.classList.remove('goruli'); }, 2800);
}

// ── Modal aç/kapat ──────────────────────────────
function stkModAc(id) { document.getElementById(id).classList.add('acik'); }
function stkModKapat(id) { document.getElementById(id).classList.remove('acik'); }

// ── Responsive: geniş ekranda ızgara göster ──────────────────────────────
function stkResponsive() {
    var genis = window.innerWidth >= 900;
    var g = document.getElementById('stkGenis');
    var m = document.getElementById('stkMobil');
    var fab = document.getElementById('stkFab');
    var araBar = document.getElementById('stkAraBar');
    var araWrap = document.getElementById('stkAraWrapMobil');
    var altBaslik = document.querySelector('.rk-header .alt');
    if (g) g.style.display  = genis ? '' : 'none';
    if (m) m.style.display  = genis ? 'none' : '';
    if (fab) fab.style.display   = genis ? 'none' : (stkDuzenleyebilir ? '' : 'none');
    if (araBar) araBar.style.display  = genis ? 'flex' : 'none';
    if (araWrap) araWrap.style.display = genis ? 'none' : '';
}
stkResponsive();
window.addEventListener('resize', stkResponsive);

// ── Arama + çip filtresi ──────────────────────────────
let _stkAktifCip = 'tumu';
function stkCipSec(fil){
    _stkAktifCip = fil;
    document.querySelectorAll('#stkCipSat .stk-cip').forEach(function(c){
        c.classList.toggle('aktif', c.getAttribute('data-fil') === fil);
    });
    stkFiltre();
}
function _stkCipGecer(el){
    if (_stkAktifCip === 'tumu') return true;
    var d = el.getAttribute('data-dur') || '';
    var t = el.getAttribute('data-takip') || '';
    if (_stkAktifCip === 'takipsiz') return t === '0';
    return d === _stkAktifCip;
}
function stkFiltre(q) {
    // Query ya argümandan ya aktif input'tan
    if (q === undefined) {
        var ag = document.getElementById('stkAraGenis');
        var am = document.getElementById('stkAraMobil');
        q = (ag && ag.value) || (am && am.value) || '';
    }
    q = (q || '').toLowerCase().trim();
    function uygula(el){
        var adOk = !q || (el.getAttribute('data-ad')||'').indexOf(q) !== -1;
        var cipOk = _stkCipGecer(el);
        el.style.display = (adOk && cipOk) ? '' : 'none';
    }
    document.querySelectorAll('#stkMobil .stk-kart').forEach(uygula);
    document.querySelectorAll('#stkGenis .stk-mkart').forEach(uygula);
    // Boş kategori başlıklarını gizle (geniş görünümde)
    document.querySelectorAll('#stkGenis .stk-grid').forEach(function(grid){
        var gorulen = Array.from(grid.children).some(function(c){ return c.style.display !== 'none'; });
        grid.style.display = gorulen ? '' : 'none';
        var baslik = grid.previousElementSibling;
        if (baslik && baslik.classList.contains('stk-kat-baslik')) baslik.style.display = gorulen ? '' : 'none';
    });
    // Arama kutuları senkron
    var mAra = document.getElementById('stkAraMobil');
    var gAra = document.getElementById('stkAraGenis');
    if (mAra && document.activeElement !== mAra) mAra.value = q;
    if (gAra && document.activeElement !== gAra) gAra.value = q;
}

// ── DETAY MODAL (Flutter _detay → DraggableScrollableSheet) ──────────────────
function stkDetayAc(id) {
    document.getElementById('stkDetayBody').innerHTML = '<div class="stk-yukl"><span class="spin"></span>Yükleniyor…</div>';
    stkModAc('stkDetayModal');

    stkApi('/api/patron/malzeme-detay?id=' + id).then(function(d) {
        if (!d || d.ok !== 1) {
            document.getElementById('stkDetayBody').innerHTML = '<p style="color:var(--kirmizi)">Detay alınamadı.</p>';
            return;
        }
        var h = '';
        h += '<div class="stk-mod-baslik">' + _esc(d.ad) + '</div>';
        h += '<div class="det-ozet">Mevcut: <strong>' + _stkMik(d.mevcut) + ' ' + _esc(d.birim) + '</strong>';
        h += '  ·  Maliyet <strong>' + _stkTl(d.guncel_maliyet) + '/' + _esc(d.birim) + '</strong>';
        h += '  ·  Değer <strong>' + _stkTl(d.deger) + '</strong></div>';

        // Eylem butonları (Giriş / Fire / Düzelt) — sadece duzenleyebilir
        if (stkDuzenleyebilir) {
            h += '<div class="stk-btnler">';
            h += _stkEylemBtn('Giriş',  'add',            '#10B981', 'giris',     id);
            h += _stkEylemBtn('Fire',   'delete_outline',  '#F43F5E', 'fire',      id);
            h += _stkEylemBtn('Düzelt', 'tune',            '#4F46E5', 'duzeltme',  id);
            h += '</div>';
            // Düzenle butonu
            h += '<button onclick="stkDetayDuzenle('+id+')" style="width:100%;margin-bottom:8px;padding:11px;border:1.5px solid var(--mor1);background:transparent;border-radius:12px;color:var(--mor1);font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px"><span class="material-icons-outlined" style="font-size:17px">edit</span>Malzemeyi Düzenle</button>';
        }

        // Son hareketler
        var hareketler = d.hareketler || [];
        h += '<div class="stk-hareketler"><h4>Son Hareketler</h4>';
        if (!hareketler.length) {
            h += '<p style="color:var(--sub);font-size:13px">Hareket yok.</p>';
        } else {
            var tipAd = {alis:'Alış', tuketim:'Satış tüketimi', fire:'Fire', sayim:'Sayım', iade:'Giriş', transfer:'Transfer'};
            hareketler.forEach(function(hv) {
                var poz = parseFloat(hv.miktar) >= 0;
                var ren = poz ? '#10B981' : '#F43F5E';
                var ik  = poz ? 'arrow_downward' : 'arrow_upward';
                var ad  = tipAd[hv.tip] || hv.tip;
                var acik = (hv.aciklama || '').trim();
                h += '<div class="stk-hareket-satir">';
                h += '<span class="material-icons-outlined" style="color:'+ren+'">'+ik+'</span>';
                h += '<div class="h-bilgi"><div class="h-ad">'+_esc(ad)+'</div>';
                h += '<div class="h-alt">'+_esc(hv.tarih)+(acik ? ' · '+_esc(acik) : '')+'</div></div>';
                h += '<div class="h-mik" style="color:'+ren+'">'+(poz?'+':'')+_stkMik(hv.miktar)+'</div>';
                h += '</div>';
            });
        }
        h += '</div>';

        document.getElementById('stkDetayBody').innerHTML = h;
    }).catch(function() {
        document.getElementById('stkDetayBody').innerHTML = '<p style="color:var(--kirmizi)">Bağlantı hatası.</p>';
    });
}

function _stkEylemBtn(etiket, ikon, renk, tip, malzemeId) {
    return '<button class="stk-btn-eylem" onclick="stkHareketAc('+malzemeId+',\''+tip+'\')"'
        + ' style="color:'+renk+';background:'+renk+'22;border-color:'+renk+'66">'
        + '<span class="material-icons-outlined">'+ikon+'</span>'+_esc(etiket)+'</button>';
}

function stkDetayDuzenle(id) {
    stkModKapat('stkDetayModal');
    stkMalzemeFormuAc(id);
}

// ── HAREKET DIALOG (Flutter _hareketDialog → AlertDialog) ──────────────────
var _hareketBasliklar = {giris:'Stok Girişi', fire:'Fire / Zayi', duzeltme:'Sayım Düzeltme (±)'};

function stkHareketAc(malzemeId, tip) {
    _stkHareketMalzemeId = malzemeId;
    _stkHareketTip = tip;
    document.getElementById('stkHareketBaslik').textContent = _hareketBasliklar[tip] || 'Hareket';
    document.getElementById('stkHMiktar').value = '';
    document.getElementById('stkHAciklama').value = '';
    document.getElementById('stkHareketDialog').classList.add('acik');
    setTimeout(function(){ document.getElementById('stkHMiktar').focus(); }, 100);
}

function stkHareketKapat() {
    document.getElementById('stkHareketDialog').classList.remove('acik');
    _stkHareketMalzemeId = null;
    _stkHareketTip = null;
}

function stkHareketKaydet() {
    var miktarStr = (document.getElementById('stkHMiktar').value || '').replace(',', '.');
    var miktar = parseFloat(miktarStr) || 0;
    if (miktar === 0) { stkUyar('Geçerli miktar girin'); return; }
    var aciklama = document.getElementById('stkHAciklama').value.trim();

    stkApi('/api/patron/stok-hareket', {
        malzeme_id: _stkHareketMalzemeId,
        tip: _stkHareketTip,
        miktar: miktar,
        aciklama: aciklama
    }).then(function(r) {
        if (r.ok === 1) {
            stkHareketKapat();
            stkModKapat('stkDetayModal');
            stkUyar('Hareket kaydedildi');
            setTimeout(function(){ window.location.reload(); }, 700);
        } else {
            stkUyar(r.hata || 'Kaydedilemedi');
        }
    }).catch(function(){ stkUyar('Bağlantı hatası'); });
}

// ── MALZEME FORM (Flutter _malzemeForm → showModalBottomSheet) ──────────────
function stkMalzemeFormuAc(id) {
    _stkFormId = id;
    var yeni = id === null;
    document.getElementById('stkFormBaslik').textContent = yeni ? 'Yeni Malzeme' : 'Malzeme Düzenle';
    document.getElementById('stkFMaliyetWrap').style.display = yeni ? '' : 'none';
    document.getElementById('stkFYeniAlanlar').style.display = yeni ? '' : 'none'; // başlangıç stok + barkod sadece eklemede

    if (!yeni) {
        var m = stkVeriler.find(function(x){ return x.id === id; });
        if (m) {
            document.getElementById('stkFAd').value       = m.ad || '';
            document.getElementById('stkFKat').value      = m.kategori_id || '';
            document.getElementById('stkFBirim').value    = m.temel_birim_id || '';
            document.getElementById('stkFKritik').value   = m.kritik_stok > 0 ? m.kritik_stok : '';
            document.getElementById('stkFMaliyet').value  = '';
            document.getElementById('stkFTakip').checked  = m.stok_takipli === 1;
        }
    } else {
        document.getElementById('stkFAd').value      = '';
        document.getElementById('stkFKat').value     = stkKategoriler.length ? stkKategoriler[0].id : '';
        document.getElementById('stkFBirim').value   = stkBirimler.length ? stkBirimler[0].id : '';
        document.getElementById('stkFKritik').value  = '';
        document.getElementById('stkFMaliyet').value = '';
        document.getElementById('stkFTakip').checked = true;
        document.getElementById('stkFBaslangic').value = '';
        document.getElementById('stkFBarkod').value = '';
    }
    stkModAc('stkFormModal');
}

function stkFormKaydet() {
    var ad = document.getElementById('stkFAd').value.trim();
    if (!ad) { stkUyar('Ad boş olamaz'); return; }

    var veri = {
        ad:             ad,
        id:             _stkFormId || 0,
        kategori_id:    parseInt(document.getElementById('stkFKat').value) || 0,
        temel_birim_id: parseInt(document.getElementById('stkFBirim').value) || 0,
        kritik_stok:    parseFloat((document.getElementById('stkFKritik').value || '0').replace(',','.')) || 0,
        stok_takipli:   document.getElementById('stkFTakip').checked ? 1 : 0,
        guncel_maliyet: parseFloat((document.getElementById('stkFMaliyet').value || '0').replace(',','.')) || 0,
    };
    // Yeni malzemede başlangıç stok + barkod aynı formdan (tek ekran)
    if (!_stkFormId) {
        veri.baslangic_stok = parseFloat((document.getElementById('stkFBaslangic').value || '0').replace(',','.')) || 0;
        veri.barkod = document.getElementById('stkFBarkod').value.trim();
    }

    stkApi('/api/patron/malzeme-kaydet', veri).then(function(r) {
        if (r.ok === 1) {
            stkModKapat('stkFormModal');
            stkUyar(_stkFormId ? 'Güncellendi' : 'Malzeme + stok + barkod eklendi');
            setTimeout(function(){ window.location.reload(); }, 700);
        } else {
            stkUyar(r.hata || 'Kaydedilemedi');
        }
    }).catch(function(){ stkUyar('Bağlantı hatası'); });
}

// ── KATEGORİ EKLE DIALOG (Flutter _kategoriEkle) ──────────────────
function stkKatEkleAc() {
    document.getElementById('stkKatAd').value = '';
    document.getElementById('stkKatDialog').classList.add('acik');
    setTimeout(function(){ document.getElementById('stkKatAd').focus(); }, 100);
}

function stkKatKaydet() {
    var ad = document.getElementById('stkKatAd').value.trim();
    if (!ad) { stkUyar('Boş olamaz'); return; }

    stkApi('/api/patron/malzeme-kategori-ekle', { ad: ad }).then(function(r) {
        if (r.ok === 1) {
            document.getElementById('stkKatDialog').classList.remove('acik');
            // Select'e yeni seçenek ekle ve seç
            var sel = document.getElementById('stkFKat');
            var opt = new Option(ad, r.id, true, true);
            sel.add(opt);
            stkKategoriler.push({id: r.id, ad: ad});
            stkUyar('Kategori eklendi');
        } else {
            stkUyar(r.hata || 'Eklenemedi');
        }
    }).catch(function(){ stkUyar('Bağlantı hatası'); });
}
</script>
@endpush
@endsection

@extends('layout.app')
@section('rk_ikon', 'handshake')
@section('baslik', 'Fiyat Anlaşmaları')
@section('rk_alt', 'Sözleşmeler ve anlaşmalı fiyatlar · siparişte uygulanır, faturada denetlenir')
@section('title', 'Fiyat Anlaşmaları')

@push('head')
<style>
#fa{--mor:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--gri:#94a3b8;
  --card:#fff;--card2:#f1f5f9;--bg:#f6f7fb;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb}
body.koyu #fa{--card:#161d2e;--card2:#1d2539;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#fa{background:var(--bg);min-height:calc(100vh - 60px)}#fa *{box-sizing:border-box}
#fa .pano{max-width:1180px;margin:0 auto;padding:18px 18px 48px}
.ms{font-family:'Material Icons Outlined';font-weight:normal;font-style:normal;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased;text-transform:none;letter-spacing:normal;white-space:nowrap}

/* Üst bant */
#fa .hero{position:relative;overflow:hidden;border-radius:20px;padding:22px 24px;background:linear-gradient(120deg,#EEF2FF 0%,#F5F3FF 55%,#ECFDF5 100%);border:1px solid #E0E7FF;display:flex;gap:18px;align-items:center;flex-wrap:wrap}
body.koyu #fa .hero{background:linear-gradient(120deg,#1e2142 0%,#231c3d 55%,#12302a 100%);border-color:#2c3650}
#fa .hero-ik{width:54px;height:54px;border-radius:16px;display:grid;place-items:center;background:linear-gradient(135deg,var(--mavi),var(--mor));color:#fff;box-shadow:0 8px 20px rgba(79,70,229,.3);flex:none}
#fa .hero-ik .ms{font-size:28px}
#fa .hero h2{margin:0;font-size:19px;font-weight:900;color:var(--ink);letter-spacing:-.2px}
#fa .hero p{margin:4px 0 0;font-size:13px;color:var(--sub);line-height:1.5;max-width:640px}
#fa .hero .yazi{flex:1;min-width:240px}
#fa .hero-btn{display:flex;gap:8px;flex-wrap:wrap}
#fa .ekle-btn{border:0;border-radius:13px;padding:12px 18px;font-weight:800;font-size:14px;cursor:pointer;color:#fff;background:linear-gradient(135deg,var(--mavi),var(--mor));display:inline-flex;align-items:center;gap:7px;box-shadow:0 6px 16px rgba(124,58,237,.3);transition:transform .15s;text-decoration:none}
#fa .ekle-btn:hover{transform:translateY(-1px)}
#fa .ekle-btn .ms{font-size:19px}
#fa .ekle-btn.ik{background:var(--card);color:var(--ink);border:1px solid var(--line);box-shadow:none}
#fa .salt-rozet{display:inline-flex;align-items:center;gap:6px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:9px 13px;font-size:12.5px;font-weight:700;color:var(--sub)}

/* Nasıl çalışır */
#fa .adimlar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:14px}
#fa .adim{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:13px 14px;display:flex;gap:11px;align-items:flex-start}
#fa .adim .no{width:30px;height:30px;border-radius:10px;display:grid;place-items:center;flex:none;font-weight:900;font-size:13px;color:#fff}
#fa .adim b{display:block;font-size:13px;color:var(--ink);margin-bottom:2px}
#fa .adim span.t{font-size:12px;color:var(--sub);line-height:1.45}

/* KPI */
#fa .kpi{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0}
#fa .k{min-width:0;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 15px;display:flex;gap:12px;align-items:center;cursor:pointer;transition:border-color .15s,box-shadow .15s}
#fa .k:hover{border-color:color-mix(in srgb,var(--kr) 45%,transparent);box-shadow:0 4px 14px rgba(0,0,0,.05)}
#fa .k .ki{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb,var(--kr) 14%,transparent);color:var(--kr);flex:none}
#fa .k .ki .ms{font-size:22px}
#fa .k .kd{font-size:22px;font-weight:900;color:var(--ink);line-height:1.1}
#fa .k .kl{font-size:11.5px;color:var(--sub);font-weight:600;line-height:1.3}
#fa .k .kx{font-size:11px;color:var(--kr);font-weight:800;margin-top:1px}

/* Sekmeler */
#fa .sekmeler{display:flex;gap:4px;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:4px;margin-bottom:12px;width:fit-content;max-width:100%;overflow-x:auto}
#fa .sekme{border:0;background:none;border-radius:10px;padding:9px 16px;font-size:13.5px;font-weight:800;color:var(--sub);cursor:pointer;display:inline-flex;align-items:center;gap:7px;white-space:nowrap}
#fa .sekme .ms{font-size:18px}
#fa .sekme i{font-style:normal;background:var(--card2);border-radius:10px;padding:1px 7px;font-size:11px}
#fa .sekme.on{background:linear-gradient(135deg,var(--mavi),var(--mor));color:#fff}
#fa .sekme.on i{background:rgba(255,255,255,.2)}
#fa .panel{display:none}#fa .panel.on{display:block}

/* Araç çubuğu */
#fa .arac{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
#fa .ara{flex:1;min-width:200px;position:relative}
#fa .ara .ms{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--sub2);font-size:19px}
#fa .ara input{width:100%;border:1px solid var(--line);border-radius:12px;padding:10px 12px 10px 38px;background:var(--card);color:var(--ink);font-size:13.5px}
#fa .cipler{display:flex;gap:6px;flex-wrap:wrap}
#fa .cip{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:7px 12px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;gap:6px;align-items:center}
#fa .cip i{font-style:normal;background:var(--card2);border-radius:10px;padding:0 6px;font-size:11px}
#fa .cip.on{background:var(--ink);border-color:var(--ink);color:var(--card)}
#fa .cip.on i{background:rgba(255,255,255,.18);color:inherit}

/* Liste */
#fa .liste{display:flex;flex-direction:column;gap:9px}
#fa .sat{--dr:var(--yesil);position:relative;background:var(--card);border:1px solid var(--line);border-radius:15px;padding:13px 14px 13px 18px;display:grid;grid-template-columns:minmax(200px,1.5fr) 140px minmax(170px,1.1fr) minmax(190px,1.3fr) auto;gap:14px;align-items:center;transition:box-shadow .15s}
#fa .sat:hover{box-shadow:0 6px 18px rgba(15,23,42,.06)}
#fa .sat::before{content:'';position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:0 4px 4px 0;background:var(--dr)}
#fa .sat.durdu{opacity:.62}
#fa .kim{display:flex;gap:11px;align-items:center;min-width:0}
#fa .av{width:40px;height:40px;border-radius:12px;flex:none;display:grid;place-items:center;font-weight:900;font-size:14px;color:#fff}
#fa .kim .mal{font-weight:800;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .kim .ted{font-size:12px;color:var(--sub);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .kim .not{font-size:11px;color:var(--sub2);margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .etiketler{display:flex;gap:5px;flex-wrap:wrap;margin-top:4px}
#fa .etk{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:20px;background:var(--card2);color:var(--sub);border:0;cursor:default;font-family:inherit}
#fa .etk .ms{font-size:13px}
#fa button.etk{cursor:pointer}
#fa .etk.soz{background:#EEF2FF;color:#4338CA}
body.koyu #fa .etk.soz{background:#1e2142;color:#c7d2fe}
#fa .etk.belge{background:#ECFDF5;color:#047857}
body.koyu #fa .etk.belge{background:#12302a;color:#6ee7b7}
#fa .fiyat{font-size:18px;font-weight:900;color:var(--ink);white-space:nowrap}
#fa .fiyat small{font-size:11.5px;color:var(--sub);font-weight:700}
#fa .fiyat-alt{font-size:11px;font-weight:800;margin-top:2px}
#fa .donem .rozet{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;background:color-mix(in srgb,var(--dr) 14%,transparent);color:var(--dr)}
#fa .donem .rozet .ms{font-size:14px}
#fa .donem .tar{font-size:11.5px;color:var(--sub);margin-top:5px}
#fa .bar{height:5px;border-radius:4px;background:var(--card2);margin-top:5px;overflow:hidden}
#fa .bar i{display:block;height:100%;border-radius:4px}
#fa .uyum{font-size:12px;line-height:1.4;display:flex;gap:8px;align-items:flex-start}
#fa .uyum .ms{font-size:19px;flex:none}
#fa .uyum b{display:block;font-size:12.5px}
#fa .uyum span.a{color:var(--sub)}
#fa .uyum.iyi .ms,#fa .uyum.iyi b{color:var(--yesil)}
#fa .uyum.kotu .ms,#fa .uyum.kotu b{color:var(--kirmizi)}
#fa .uyum.yok .ms{color:var(--sub2)}#fa .uyum.yok b{color:var(--sub)}
#fa .islem{display:flex;gap:5px}
#fa .ib{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:var(--card);color:var(--sub);display:grid;place-items:center;cursor:pointer;transition:all .15s}
#fa .ib .ms{font-size:18px}
#fa .ib:hover{background:var(--card2);color:var(--ink)}
#fa .ib.sil:hover{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 35%,transparent)}
#fa .sonucyok{display:none;text-align:center;color:var(--sub);padding:28px;font-size:13px;background:var(--card);border:1px dashed var(--line);border-radius:15px}

/* Sözleşme kartı */
#fa .soz-liste{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
#fa .soz{--dr:var(--yesil);position:relative;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px 16px 14px;display:flex;flex-direction:column;gap:11px;overflow:hidden}
#fa .soz::before{content:'';position:absolute;left:0;right:0;top:0;height:4px;background:var(--dr)}
#fa .soz.durdu{opacity:.65}
#fa .soz-ust{display:flex;gap:11px;align-items:center}
#fa .soz-ust .bas{flex:1;min-width:0}
#fa .soz-ust .bas b{display:block;font-size:14.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#fa .soz-ust .bas span{font-size:12px;color:var(--sub)}
#fa .soz-kalem{display:flex;flex-wrap:wrap;gap:5px}
#fa .soz-kalem span{font-size:11.5px;background:var(--card2);border-radius:8px;padding:4px 8px;color:var(--ink);font-weight:600}
#fa .soz-kalem span b{color:var(--mavi);font-weight:800}
#fa .soz-kalem span.ihl{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:#be123c}
#fa .sartlar{display:flex;flex-wrap:wrap;gap:6px}
#fa .sart{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--sub);font-weight:600}
#fa .sart .ms{font-size:15px;color:var(--sub2)}
#fa .fesih-uyari{display:flex;gap:8px;align-items:center;font-size:12px;font-weight:700;border-radius:10px;padding:8px 10px;background:color-mix(in srgb,var(--amber) 14%,transparent);color:#B45309}
body.koyu #fa .fesih-uyari{color:#fcd34d}
#fa .fesih-uyari .ms{font-size:17px}
#fa .soz-alt{display:flex;gap:8px;align-items:center;justify-content:space-between;border-top:1px dashed var(--line);padding-top:10px;flex-wrap:wrap}
#fa .belgeler{display:flex;gap:5px;flex-wrap:wrap;min-width:0}

/* Boş durum */
#fa .bos{background:var(--card);border:1px dashed var(--line);border-radius:18px;padding:34px 20px;text-align:center}
#fa .bos .ms.b{font-size:46px;color:var(--mor);background:color-mix(in srgb,var(--mor) 10%,transparent);border-radius:20px;padding:14px}
#fa .bos h3{margin:14px 0 6px;font-size:16px;color:var(--ink)}
#fa .bos p{margin:0 auto 16px;font-size:13px;color:var(--sub);max-width:540px;line-height:1.55}
#fa .ornek{display:inline-flex;gap:8px;align-items:center;background:var(--card2);border-radius:12px;padding:9px 14px;font-size:12.5px;color:var(--sub);margin-bottom:16px;flex-wrap:wrap;justify-content:center}
#fa .ornek b{color:var(--ink)}

/* Bildirim */
#fa .flash{display:flex;gap:9px;align-items:center;border-radius:13px;padding:11px 15px;margin-bottom:14px;font-weight:700;font-size:13.5px}
#fa .flash.ok{background:color-mix(in srgb,var(--yesil) 13%,transparent);color:#0b805e}
#fa .flash.hata{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:#be123c}
body.koyu #fa .flash.ok{color:#6ee7b7}body.koyu #fa .flash.hata{color:#fda4af}

/* Popup */
.fap-ort{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px}
.fap-ort.ac{display:flex}
.fap{--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb;background:var(--card);color:var(--ink);border-radius:20px;width:100%;max-width:560px;max-height:calc(100vh - 32px);overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,.3);animation:fapGir .18s ease-out;margin:0}
.fap.genis{max-width:860px}
body.koyu .fap{--card:#161d2e;--card2:#1d2539;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
@keyframes fapGir{from{transform:translateY(10px) scale(.98);opacity:0}}
.fap .bas{display:flex;align-items:center;gap:10px;padding:18px 20px 6px;position:sticky;top:0;background:var(--card);z-index:2}
.fap .bas .ms{color:#4F46E5;font-size:22px}
.fap .bas h3{margin:0;font-size:16.5px;font-weight:900;flex:1}
.fap .kapat{border:0;background:var(--card2);color:var(--sub);width:32px;height:32px;border-radius:10px;cursor:pointer;display:grid;place-items:center}
.fap .kapat .ms{color:var(--sub);font-size:18px}
.fap .gov{padding:10px 20px 6px}
.fap .bolum{font-size:12px;font-weight:900;color:#4F46E5;text-transform:uppercase;letter-spacing:.4px;margin:18px 0 2px;display:flex;align-items:center;gap:6px}
.fap .bolum:first-child{margin-top:4px}
.fap .bolum .ms{font-size:17px}
.fap .bolum small{font-weight:600;color:var(--sub2);text-transform:none;letter-spacing:0}
.fap label{display:block;font-size:11.5px;font-weight:800;color:var(--sub);margin:11px 0 5px}
.fap input,.fap select,.fap textarea{width:100%;border:1px solid var(--line);border-radius:11px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:14px;outline:none;box-sizing:border-box;font-family:inherit}
.fap input:focus,.fap select:focus,.fap textarea:focus{border-color:#818CF8;box-shadow:0 0 0 3px rgba(129,140,248,.2)}
.fap .iki{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.fap .uc{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.fap .fiyatkutu{display:grid;grid-template-columns:minmax(0,1fr) 110px;gap:10px}
.fap .ipucu{display:none;margin-top:8px;font-size:12.5px;background:#EEF2FF;color:#3730A3;border-radius:10px;padding:9px 11px;gap:7px;align-items:center}
body.koyu .fap .ipucu{background:#1e2142;color:#c7d2fe}
.fap .ipucu.ac{display:flex}
.fap .ipucu .ms{font-size:17px}
.fap .ipucu button{margin-left:auto;border:0;background:#4F46E5;color:#fff;border-radius:8px;padding:4px 9px;font-size:11.5px;font-weight:800;cursor:pointer;white-space:nowrap}
.fap .hizli{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px}
.fap .hizli button{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:5px 11px;font-size:12px;font-weight:700;cursor:pointer}
.fap .hizli button.on,.fap .hizli button:hover{background:#EEF2FF;border-color:#C7D2FE;color:#4338CA}
body.koyu .fap .hizli button.on,body.koyu .fap .hizli button:hover{background:#1e2142;border-color:#3730A3;color:#c7d2fe}
.fap .alt{display:flex;gap:9px;justify-content:flex-end;padding:16px 20px 20px;position:sticky;bottom:0;background:var(--card);border-top:1px solid var(--line)}
.fap .b{border:0;border-radius:12px;padding:11px 18px;font-weight:800;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.fap .b.ikincil{background:var(--card2);color:var(--sub)}
.fap .b.ana{background:linear-gradient(135deg,#4F46E5,#7C3AED);color:#fff}
.fap .b.tehlike{background:#F43F5E;color:#fff}
.fap.kucuk{max-width:400px;text-align:center}
.fap.kucuk .gov{padding:24px 22px 4px}
.fap.kucuk .uyari-ik{width:56px;height:56px;border-radius:18px;display:grid;place-items:center;margin:0 auto 12px;background:rgba(244,63,94,.12);color:#F43F5E}
.fap.kucuk .uyari-ik .ms{font-size:28px}
.fap.kucuk h3{margin:0 0 6px;font-size:16.5px}
.fap.kucuk p{margin:0;font-size:13px;color:var(--sub);line-height:1.5}
.fap.kucuk .alt{justify-content:center;border-top:0;position:static}
/* Kalem tablosu */
.fap .kalemler{display:flex;flex-direction:column;gap:7px;margin-top:8px}
.fap .kalem{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr) 90px minmax(0,1fr) 34px;gap:7px;align-items:center}
.fap .kalem-bas{display:grid;grid-template-columns:minmax(0,2fr) minmax(0,1fr) 90px minmax(0,1fr) 34px;gap:7px;font-size:11px;font-weight:800;color:var(--sub);padding:0 2px}
.fap .kalem input,.fap .kalem select{padding:9px 10px;font-size:13.5px}
.fap .kalem .ks{border:0;background:var(--card2);color:var(--sub);width:34px;height:38px;border-radius:10px;cursor:pointer;display:grid;place-items:center}
.fap .kalem .ks:hover{background:rgba(244,63,94,.12);color:#F43F5E}
.fap .kalem-ekle{border:1.5px dashed #C7D2FE;background:none;color:#4F46E5;border-radius:11px;padding:9px;font-weight:800;font-size:13px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;margin-top:2px}
.fap .kalem .ip{grid-column:1 / -1;font-size:11.5px;color:#4338CA;margin:-3px 0 2px 2px;display:none}
body.koyu .fap .kalem .ip{color:#c7d2fe}
.fap .kalem .ip.ac{display:block}
.fap .kalem .ip a{color:inherit;font-weight:800;cursor:pointer;text-decoration:underline}
/* Belge alanı */
.fap .yukle{border:1.5px dashed var(--line);border-radius:14px;padding:14px;text-align:center;background:var(--card2)}
.fap .yukle.surukle{border-color:#818CF8;background:#EEF2FF}
.fap .yukle .dugmeler{display:flex;gap:8px;justify-content:center;flex-wrap:wrap}
.fap .yukle .dugmeler button{border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:10px;padding:8px 13px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.fap .yukle .dugmeler .ms{font-size:18px;color:#4F46E5}
.fap .yukle small{display:block;margin-top:8px;font-size:11.5px;color:var(--sub)}
.fap .dosyalar{display:flex;flex-direction:column;gap:6px;margin-top:8px}
.fap .dosya{display:flex;align-items:center;gap:9px;background:var(--card);border:1px solid var(--line);border-radius:11px;padding:7px 9px;font-size:12.5px}
.fap .dosya .ms{font-size:20px;color:#4F46E5}
.fap .dosya .ad{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700}
.fap .dosya .bt{font-size:11px;color:var(--sub)}
.fap .dosya.yeni{border-color:#A7F3D0;background:#F0FDF4}
body.koyu .fap .dosya.yeni{background:#12302a;border-color:#065f46}
.fap .dosya.hata{border-color:#FECDD3;background:#FFF1F2;color:#be123c}
.fap .dosya.silinecek{opacity:.45;text-decoration:line-through}
.fap .dosya button{border:0;background:none;color:var(--sub);cursor:pointer;display:grid;place-items:center;padding:2px}
.fap .dosya a{color:inherit;display:grid;place-items:center}
/* Görüntüleyici */
.fap.goruntule{max-width:980px;height:calc(100vh - 32px);display:flex;flex-direction:column}
.fap.goruntule .icerik{flex:1;min-height:0;background:var(--card2);display:grid;place-items:center;overflow:auto}
.fap.goruntule iframe{width:100%;height:100%;border:0;background:#fff}
.fap.goruntule img{max-width:100%;max-height:100%;object-fit:contain}
.fap.goruntule .bos-on{padding:30px;text-align:center;color:var(--sub);font-size:13.5px}

@media (max-width:980px){
  #fa .sat{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px}
  #fa .sat > *{min-width:0}
  #fa .sat .kim{grid-column:1 / -1}
  #fa .sat .islem{grid-column:1 / -1;justify-content:flex-end}
  #fa .soz-liste{grid-template-columns:minmax(0,1fr)}
}
@media (max-width:760px){
  #fa .kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
  #fa .adimlar{grid-template-columns:minmax(0,1fr)}
  .fap .uc{grid-template-columns:repeat(2,minmax(0,1fr))}
  .fap .kalem-bas{display:none}
  .fap .kalem{grid-template-columns:minmax(0,1fr) minmax(0,1fr) 34px;background:var(--card2);padding:8px;border-radius:12px}
  .fap .kalem select{grid-column:1 / 3}
  .fap .kalem .ks{grid-column:3;grid-row:1}
}
@media (max-width:520px){
  #fa .pano{padding:12px 12px 40px}
  #fa .hero{padding:16px}
  #fa .hero .yazi{min-width:0}
  #fa .hero h2{font-size:17px}
  #fa .hero-btn,#fa .ekle-btn{width:100%;justify-content:center}
  #fa .sat{grid-template-columns:minmax(0,1fr)}
  #fa .k{padding:12px}
  #fa .k .kd{font-size:19px}
  .fap .iki,.fap .uc{grid-template-columns:minmax(0,1fr)}
}
</style>
@endpush

@section('content')
@php
  $salt = $salt ?? false;
  $tl = fn ($v) => number_format((float) $v, 2, ',', '.');
  $tl0 = fn ($v) => number_format(round((float) $v), 0, ',', '.');
  $mk = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
  $kartlar = $kartlar ?? []; $sozKart = $sozKart ?? [];
  $kpi = $kpi ?? ['aktif' => 0, 'yakinda' => 0, 'bitti' => 0, 'ihlal' => 0, 'fazla' => 0];
  $durumBilgi = [
    'aktif' => ['Yürürlükte', 'verified', '#10B981'],
    'yakinda' => ['Bitiyor', 'hourglass_bottom', '#F59E0B'],
    'baslamadi' => ['Başlamadı', 'schedule', '#4F46E5'],
    'bitti' => ['Süresi doldu', 'event_busy', '#F43F5E'],
    'durdu' => ['Durduruldu', 'pause_circle', '#94a3b8'],
  ];
  $fiyatMaddesi = ['sabit' => 'Fiyat sabit', 'tavan' => 'Artış tavanlı', 'endeks' => 'Enflasyona bağlı', 'serbest' => 'Serbest (bildirimle)'];
  $renkler = ['#4F46E5', '#0EA5E9', '#10B981', '#F59E0B', '#EC4899', '#8B5CF6', '#14B8A6', '#F97316'];
  $sayac = ['hepsi' => count($kartlar), 'aktif' => 0, 'yakinda' => 0, 'uyari' => 0, 'bitti' => 0, 'durdu' => 0];
  foreach ($kartlar as $k) {
    if (in_array($k['durum'], ['aktif', 'baslamadi', 'yakinda'], true)) $sayac['aktif']++;
    if (in_array($k['durum'], ['yakinda', 'bitti', 'durdu'], true)) $sayac[$k['durum']]++;
    if ($k['ihlal_say'] > 0) $sayac['uyari']++;
  }
  $trh = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d.m.Y') : null;
  $kalanYazi = function ($k) use ($trh) {
    if ($k['durum'] === 'yakinda') return $k['kalan'] === 0 ? 'Bugün bitiyor' : $k['kalan'] . ' gün kaldı';
    if ($k['durum'] === 'bitti') return abs($k['kalan']) . ' gün önce bitti';
    if ($k['durum'] === 'baslamadi') return $trh($k['baslangic']) . ' tarihinde başlar';
    if ($k['durum'] === 'durdu') return 'Elle durduruldu';
    return $k['kalan'] !== null ? $k['kalan'] . ' gün kaldı' : 'Süresiz';
  };
  $baytaCevir = function ($v) { $v = trim((string) $v); $n = (float) $v; $s = strtolower(substr($v, -1)); return (int) ($s === 'g' ? $n * 1073741824 : ($s === 'm' ? $n * 1048576 : ($s === 'k' ? $n * 1024 : $n))); };
  $limitBayt = $baytaCevir($yukLimit ?? '2M') ?: 2097152;
  $acikSekme = session('fa_sekme') === 'sozlesme' ? 'sozlesme' : 'fiyat';
@endphp
<div id="fa"><div class="pano">
  @if (session('ok'))<div class="flash ok"><span class="ms">check_circle</span>{{ session('ok') }}</div>@endif
  @if (session('hata'))<div class="flash hata"><span class="ms">error</span>{{ session('hata') }}</div>@endif

  <div class="hero">
    <div class="hero-ik"><span class="ms">handshake</span></div>
    <div class="yazi">
      <h2>Tedarikçiyle fiyatı sabitle, gerisini sistem takip etsin</h2>
      <p>Tedarikçiyle yaptığın sözleşmeyi belgesiyle birlikte buraya ekle. Sipariş anlaşmalı fiyattan çıkar, fatura gelince fiyat kontrol edilir, bitişten önce hatırlatılırsın.</p>
    </div>
    @if ($salt)
      <span class="salt-rozet"><span class="ms" style="font-size:18px">visibility</span> Salt okunur — anlaşmaları satın alma yönetir</span>
    @else
      <div class="hero-btn">
        <button class="ekle-btn" type="button" onclick="szAc()"><span class="ms">post_add</span> Yeni Sözleşme</button>
        <button class="ekle-btn ik" type="button" onclick="faAc()"><span class="ms">add</span> Tekli Fiyat</button>
        <a class="ekle-btn ik" href="/fiyat-anlasmalari/rapor"><span class="ms">insights</span> Raporlar</a>
      </div>
    @endif
  </div>

  @if (!$salt)
  <div class="adimlar">
    <div class="adim"><span class="no" style="background:#4F46E5">1</span><div><b>Sözleşmeyi ekle</b><span class="t">Belgeyi yükle ya da fotoğrafını çek, malzemeleri ve fiyatları gir.</span></div></div>
    <div class="adim"><span class="no" style="background:#7C3AED">2</span><div><b>Sipariş bu fiyattan çıkar</b><span class="t">Otomatik ve acil siparişte anlaşmalı fiyat kullanılır.</span></div></div>
    <div class="adim"><span class="no" style="background:#10B981">3</span><div><b>Fatura ve süre kontrol edilir</b><span class="t">Fazla fatura kırmızı uyarı verir; bitişe 30/15/7 gün kala bildirim gelir.</span></div></div>
  </div>
  @endif

  <div class="kpi">
    <div class="k" style="--kr:#10B981" onclick="faSekme('fiyat');faFiltre('aktif')"><span class="ki"><span class="ms">verified</span></span><div><div class="kd">{{ $kpi['aktif'] }}</div><div class="kl">Yürürlükteki anlaşmalı fiyat</div></div></div>
    <div class="k" style="--kr:#F59E0B" onclick="faSekme('fiyat');faFiltre('yakinda')"><span class="ki"><span class="ms">hourglass_bottom</span></span><div><div class="kd">{{ $kpi['yakinda'] }}</div><div class="kl">15 gün içinde bitiyor</div>@if ($kpi['yakinda'])<div class="kx">Tedarikçiyle yenile</div>@endif</div></div>
    <div class="k" style="--kr:#F43F5E" onclick="faSekme('fiyat');faFiltre('uyari')"><span class="ki"><span class="ms">report</span></span><div><div class="kd">{{ $kpi['ihlal'] }}</div><div class="kl">Anlaşma üstü fatura</div>@if ($kpi['fazla'] > 0)<div class="kx">{{ $tl0($kpi['fazla']) }} ₺ fazla ödendi</div>@endif</div></div>
    <div class="k" style="--kr:#4F46E5" onclick="faSekme('sozlesme')"><span class="ki"><span class="ms">description</span></span><div><div class="kd">{{ count($sozKart) }}</div><div class="kl">Sözleşme</div></div></div>
  </div>

  <div class="sekmeler">
    <button type="button" class="sekme{{ $acikSekme === 'fiyat' ? ' on' : '' }}" data-s="fiyat" onclick="faSekme('fiyat')"><span class="ms">sell</span> Anlaşmalı Fiyatlar <i>{{ count($kartlar) }}</i></button>
    <button type="button" class="sekme{{ $acikSekme === 'sozlesme' ? ' on' : '' }}" data-s="sozlesme" onclick="faSekme('sozlesme')"><span class="ms">description</span> Sözleşmeler <i>{{ count($sozKart) }}</i></button>
  </div>

  {{-- ===== ANLAŞMALI FİYATLAR ===== --}}
  <div class="panel{{ $acikSekme === 'fiyat' ? ' on' : '' }}" id="pnl-fiyat">
  @if (!count($kartlar))
    <div class="bos">
      <span class="ms b">handshake</span>
      <h3>Henüz anlaşmalı fiyat yok</h3>
      <p>Tedarikçinle yaptığın sözleşmeyi ekle: belgeyi yükle, kapsadığı malzemeleri ve fiyatları gir. Fiyat dalgalansa da sipariş anlaşmalı fiyattan çıkar; tedarikçi farklı fatura keserse fark burada görünür.</p>
      <div class="ornek"><span class="ms" style="font-size:17px;color:#4F46E5">lightbulb</span> Örnek: <b>Metro Gıda yıllık sözleşmesi</b> · Ayçiçek yağı, un, şeker · <b>12 ay</b></div><br>
      @unless ($salt)<button class="ekle-btn" type="button" onclick="szAc()"><span class="ms">post_add</span> İlk sözleşmeyi ekle</button>@endunless
    </div>
  @else
    <div class="arac">
      <div class="ara"><span class="ms">search</span><input id="faAra" type="search" placeholder="Malzeme veya tedarikçi ara…" oninput="faUygula()"></div>
      <div class="cipler" id="faCip">
        @foreach (['hepsi' => 'Tümü', 'aktif' => 'Yürürlükte', 'yakinda' => 'Bitiyor', 'uyari' => 'Fatura uyarısı', 'bitti' => 'Süresi doldu', 'durdu' => 'Durduruldu'] as $fk => $fa)
          @if ($fk === 'hepsi' || $sayac[$fk] > 0)
            <button type="button" class="cip{{ $fk === 'hepsi' ? ' on' : '' }}" data-f="{{ $fk }}" onclick="faFiltre('{{ $fk }}')">{{ $fa }} <i>{{ $sayac[$fk] }}</i></button>
          @endif
        @endforeach
      </div>
    </div>

    <div class="liste" id="faListe">
      @foreach ($kartlar as $k)
        @php
          [$dAd, $dIk, $dRenk] = $durumBilgi[$k['durum']];
          $av = mb_strtoupper(mb_substr($k['ted'], 0, 1, 'UTF-8'), 'UTF-8');
          $avRenk = $renkler[$k['ted_id'] % count($renkler)];
          $filtre = in_array($k['durum'], ['aktif', 'baslamadi'], true) ? 'aktif' : ($k['durum'] === 'yakinda' ? 'aktif yakinda' : $k['durum']);
        @endphp
        <div class="sat {{ $k['durum'] }}" style="--dr:{{ $k['ihlal_say'] ? '#F43F5E' : $dRenk }}" data-f="{{ $filtre }}" data-u="{{ $k['ihlal_say'] ? 1 : 0 }}" data-ara="{{ mb_strtolower($k['mal'] . ' ' . $k['ted'] . ' ' . $k['not'] . ' ' . $k['sozlesme_ad'], 'UTF-8') }}">
          <div class="kim">
            <span class="av" style="background:{{ $avRenk }}">{{ $av }}</span>
            <div style="min-width:0">
              <div class="mal">{{ $k['mal'] }}</div>
              <div class="ted">{{ $k['ted'] }}</div>
              @if ($k['not'])<div class="not" title="{{ $k['not'] }}">📝 {{ $k['not'] }}</div>@endif
              @if ($k['sozlesme_id'] || $k['taahhut'] || count($k['belgeler']))
                <div class="etiketler">
                  @if ($k['sozlesme_id'])
                    @if ($salt)<span class="etk soz"><span class="ms">description</span>{{ $k['sozlesme_ad'] }}</span>
                    @else<button type="button" class="etk soz" onclick="szAcId({{ $k['sozlesme_id'] }})" title="Sözleşmeyi aç"><span class="ms">description</span>{{ $k['sozlesme_ad'] }}</button>@endif
                  @endif
                  @if ($k['taahhut'])<span class="etk"><span class="ms">flag</span>Taahhüt {{ $mk($k['taahhut']) }} {{ $k['birim'] }}/ay</span>@endif
                  @if (count($k['belgeler']))<button type="button" class="etk belge" onclick='bgAc(@json($k['belgeler']), 0)'><span class="ms">attach_file</span>{{ count($k['belgeler']) }} belge</button>@endif
                </div>
              @endif
            </div>
          </div>
          <div>
            <div class="fiyat">{{ $tl($k['fiyat']) }} ₺@if ($k['birim'])<small> / {{ $k['birim'] }}</small>@endif</div>
            @if ($k['piyasa_fark'] !== null)
              <div class="fiyat-alt" style="color:{{ $k['piyasa_fark'] <= 0 ? '#10B981' : '#F43F5E' }}" title="Diğer tedarikçilerden son 90 gündeki en düşük alış: {{ $tl($k['piyasa']) }} ₺">
                {{ $k['piyasa_fark'] < 0 ? 'Piyasadan %' . abs($k['piyasa_fark']) . ' ucuz' : ($k['piyasa_fark'] > 0 ? 'Piyasadan %' . $k['piyasa_fark'] . ' pahalı' : 'Piyasayla aynı') }}
              </div>
            @endif
          </div>
          <div class="donem">
            <span class="rozet" style="--dr:{{ $dRenk }}"><span class="ms">{{ $dIk }}</span>{{ $dAd }}</span>
            <div class="tar">
              @if ($k['baslangic'] || $k['bitis']){{ $trh($k['baslangic']) ?: 'Bugünden' }} → {{ $trh($k['bitis']) ?: 'süresiz' }} · @endif<b style="color:var(--ink)">{{ $kalanYazi($k) }}</b>
            </div>
            @if ($k['ilerleme'] !== null && $k['durum'] !== 'durdu')<div class="bar"><i style="width:{{ $k['ilerleme'] }}%;background:{{ $dRenk }}"></i></div>@endif
          </div>
          @if ($k['ihlal_say'])
            <div class="uyum kotu"><span class="ms">report</span><div><b>{{ $k['ihlal_say'] }} fatura anlaşmanın üstünde</b><span class="a">Son: {{ $tl($k['son_fiyat']) }} ₺ · toplam {{ $tl($k['fazla']) }} ₺ fazla ödendi</span></div></div>
          @elseif ($k['fatura_say'])
            <div class="uyum iyi"><span class="ms">task_alt</span><div><b>Faturalar uyumlu</b><span class="a">{{ $k['fatura_say'] }} faturada anlaşmalı fiyat uygulandı</span></div></div>
          @else
            <div class="uyum yok"><span class="ms">receipt_long</span><div><b>Henüz fatura yok</b><span class="a">Fatura gelince fiyat kontrol edilecek</span></div></div>
          @endif
          @if ($salt)
            <div></div>
          @else
            <div class="islem">
              @if ($k['sozlesme_id'])
                <button type="button" class="ib" title="Sözleşmede düzenle" onclick="szAcId({{ $k['sozlesme_id'] }})"><span class="ms">edit</span></button>
              @else
                <button type="button" class="ib" title="Düzenle" onclick='faAc(@json($k))'><span class="ms">edit</span></button>
                <form method="POST" action="/fiyat-anlasmalari/durum" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $k['id'] }}">
                  <button type="submit" class="ib" title="{{ $k['aktif'] ? 'Durdur' : 'Yeniden başlat' }}"><span class="ms">{{ $k['aktif'] ? 'pause' : 'play_arrow' }}</span></button></form>
                <button type="button" class="ib sil" title="Sil" onclick='silSor("/fiyat-anlasmalari/durum", {{ $k['id'] }}, @json($k['mal'] . ' · ' . $k['ted']), "Anlaşma silinsin mi?")'><span class="ms">delete</span></button>
              @endif
            </div>
          @endif
        </div>
      @endforeach
    </div>
    <div class="sonucyok" id="faYok">Bu filtreye uyan anlaşma yok.</div>
  @endif
  </div>

  {{-- ===== SÖZLEŞMELER ===== --}}
  <div class="panel{{ $acikSekme === 'sozlesme' ? ' on' : '' }}" id="pnl-sozlesme">
  @if (!count($sozKart))
    <div class="bos">
      <span class="ms b">description</span>
      <h3>Henüz sözleşme yok</h3>
      <p>Tek sözleşme birçok malzemeyi kapsar: belgeyi bir kez yükle, malzemeleri ve fiyatları altına ekle. Ödeme vadesi, teslim süresi, fiyat artış maddesi ve fesih bildirim süresi de burada durur.</p>
      @unless ($salt)<button class="ekle-btn" type="button" onclick="szAc()"><span class="ms">post_add</span> Sözleşme ekle</button>@endunless
    </div>
  @else
    <div class="soz-liste">
      @foreach ($sozKart as $s)
        @php
          [$dAd, $dIk, $dRenk] = $durumBilgi[$s['durum']];
          $av = mb_strtoupper(mb_substr($s['ted'], 0, 1, 'UTF-8'), 'UTF-8');
          $sAd = $s['baslik'] ?: ($s['sozlesme_no'] ? 'Sözleşme ' . $s['sozlesme_no'] : 'Sözleşme #' . $s['id']);
        @endphp
        <div class="soz {{ $s['durum'] }}" style="--dr:{{ $s['ihlal'] ? '#F43F5E' : $dRenk }}">
          <div class="soz-ust">
            <span class="av" style="background:{{ $renkler[$s['ted_id'] % count($renkler)] }}">{{ $av }}</span>
            <div class="bas"><b title="{{ $sAd }}">{{ $sAd }}</b><span>{{ $s['ted'] }}@if ($s['sozlesme_no'] && $s['baslik']) · No {{ $s['sozlesme_no'] }}@endif</span></div>
            <span class="donem"><span class="rozet" style="--dr:{{ $dRenk }}"><span class="ms">{{ $dIk }}</span>{{ $dAd }}</span></span>
          </div>
          <div class="donem">
            <div class="tar" style="margin-top:0">{{ $trh($s['baslangic']) ?: 'Bugünden' }} → {{ $trh($s['bitis']) ?: 'süresiz' }} · <b style="color:var(--ink)">{{ $kalanYazi($s) }}</b></div>
            @if ($s['ilerleme'] !== null && $s['durum'] !== 'durdu')<div class="bar"><i style="width:{{ $s['ilerleme'] }}%;background:{{ $dRenk }}"></i></div>@endif
          </div>
          <div class="soz-kalem">
            @foreach (array_slice($s['kalemler'], 0, 8) as $kl)
              <span class="{{ $kl['ihlal'] ? 'ihl' : '' }}" title="{{ $kl['ihlal'] ? 'Anlaşma üstü fatura var' : '' }}">{{ $kl['ad'] }} <b>{{ $tl($kl['fiyat']) }} ₺</b>{{ $kl['birim'] ? '/' . $kl['birim'] : '' }}</span>
            @endforeach
            @if (count($s['kalemler']) > 8)<span>+{{ count($s['kalemler']) - 8 }} malzeme</span>@endif
          </div>
          <div class="sartlar">
            <span class="sart"><span class="ms">inventory_2</span>{{ count($s['kalemler']) }} malzeme</span>
            @if ($s['odeme_vade_gun'] !== null)<span class="sart"><span class="ms">payments</span>{{ $s['odeme_vade_gun'] }} gün vade</span>@endif
            @if ($s['teslim_suresi'])<span class="sart"><span class="ms">local_shipping</span>{{ $s['teslim_suresi'] }}</span>@endif
            <span class="sart"><span class="ms">trending_up</span>{{ $fiyatMaddesi[$s['fiyat_maddesi']] ?? 'Fiyat sabit' }}@if ($s['fiyat_maddesi'] === 'tavan' && $s['artis_tavan'] !== null) (en fazla %{{ $mk($s['artis_tavan']) }})@endif</span>
            @if ($s['imza_tarihi'])<span class="sart"><span class="ms">draw</span>İmza {{ $trh($s['imza_tarihi']) }}{{ $s['imzalayan'] ? ' · ' . $s['imzalayan'] : '' }}</span>@endif
          </div>
          @if ($s['ihlal'])
            <div class="uyum kotu"><span class="ms">report</span><div><b>{{ $s['ihlal'] }} fatura anlaşmanın üstünde</b><span class="a">Toplam {{ $tl($s['fazla']) }} ₺ fazla ödendi</span></div></div>
          @endif
          @if ($s['fesih_son'] && $s['fesih_kalan'] !== null && $s['fesih_kalan'] >= 0 && $s['fesih_kalan'] <= 45 && $s['aktif'])
            <div class="fesih-uyari"><span class="ms">alarm</span>Sürdürmeyeceksen {{ $trh($s['fesih_son']) }} tarihine kadar ({{ $s['fesih_kalan'] }} gün) tedarikçiye bildir.</div>
          @endif
          <div class="soz-alt">
            <div class="belgeler">
              @if ($salt)
                <span class="etk"><span class="ms">lock</span>Belgeleri satın alma görür</span>
              @elseif (count($s['belgeler']))
                @foreach ($s['belgeler'] as $bi => $b)
                  <button type="button" class="etk belge" onclick='bgAc(@json($s['belgeler']), {{ $bi }})' title="{{ $b['ad'] }}"><span class="ms">{{ str_contains((string) $b['mime'], 'pdf') ? 'picture_as_pdf' : (str_starts_with((string) $b['mime'], 'image/') ? 'image' : 'description') }}</span>{{ \Illuminate\Support\Str::limit($b['ad'], 22) }}</button>
                @endforeach
              @else
                <button type="button" class="etk" style="color:#B45309" onclick="szAcId({{ $s['id'] }})"><span class="ms">upload_file</span>Belge eklenmemiş</button>
              @endif
            </div>
            @unless ($salt)
              <div class="islem">
                <button type="button" class="ib" title="Düzenle" onclick="szAcId({{ $s['id'] }})"><span class="ms">edit</span></button>
                <form method="POST" action="/fiyat-anlasmalari/sozlesme-durum" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $s['id'] }}">
                  <button type="submit" class="ib" title="{{ $s['aktif'] ? 'Durdur' : 'Yeniden başlat' }}"><span class="ms">{{ $s['aktif'] ? 'pause' : 'play_arrow' }}</span></button></form>
                <button type="button" class="ib sil" title="Sil" onclick='silSor("/fiyat-anlasmalari/sozlesme-durum", {{ $s['id'] }}, @json($sAd . ' · ' . $s['ted']), "Sözleşme silinsin mi?", "Sözleşme, içindeki tüm malzeme fiyatları ve belgeleri silinir. Geri gelmez; askıya almak için Durdur kullan.")'><span class="ms">delete</span></button>
              </div>
            @endunless
          </div>
        </div>
      @endforeach
    </div>
  @endif
  </div>
</div></div>

@unless ($salt)
{{-- ===== Sözleşme popup ===== --}}
<div class="fap-ort" id="szPop">
  <form class="fap genis" method="POST" action="/fiyat-anlasmalari/sozlesme" enctype="multipart/form-data" onsubmit="return szKontrol()">
    @csrf
    <input type="hidden" name="id" id="szId">
    <div class="bas"><span class="ms">description</span><h3 id="szBaslik">Yeni Sözleşme</h3><button type="button" class="kapat" onclick="szKapat()"><span class="ms">close</span></button></div>
    <div class="gov">
      <div class="bolum"><span class="ms">badge</span> Genel</div>
      <div class="iki">
        <div><label>Tedarikçi</label><select name="tedarikci_id" id="szTed" required onchange="szIpucuYenile()"><option value="">Seçin…</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
        <div><label>Sözleşme adı</label><input name="baslik" id="szAdi" placeholder="ör. 2027 yıllık kuru gıda" autocomplete="off"></div>
      </div>
      <div class="uc">
        <div><label>Sözleşme no</label><input name="sozlesme_no" id="szNo" placeholder="ör. MTR-2026/14" autocomplete="off"></div>
        <div><label>İmza tarihi</label><input type="date" name="imza_tarihi" id="szImza"></div>
        <div><label>İmzalayan</label><input name="imzalayan" id="szImzalayan" placeholder="Ad soyad" autocomplete="off"></div>
      </div>

      <div class="bolum"><span class="ms">date_range</span> Geçerlilik</div>
      <div class="hizli" id="szHizli" style="margin-top:8px">
        <button type="button" data-ay="3">3 ay</button><button type="button" data-ay="6">6 ay</button><button type="button" data-ay="12">1 yıl</button><button type="button" data-ay="24">2 yıl</button><button type="button" data-ay="0">Süresiz</button>
      </div>
      <div class="iki">
        <div><label style="margin-top:2px">Başlangıç</label><input type="date" name="baslangic" id="szBas"></div>
        <div><label style="margin-top:2px">Bitiş</label><input type="date" name="bitis" id="szBit"></div>
      </div>

      <div class="bolum"><span class="ms">gavel</span> Şartlar <small>(isteğe bağlı)</small></div>
      <div class="uc">
        <div><label>Ödeme vadesi (gün)</label><input name="odeme_vade_gun" id="szVade" inputmode="numeric" placeholder="ör. 30"></div>
        <div><label>Teslim süresi</label><input name="teslim_suresi" id="szTeslim" placeholder="ör. Sipariş + 1 gün" autocomplete="off"></div>
        <div><label>Fesih bildirimi (gün önce)</label><input name="fesih_ihbar_gun" id="szFesih" inputmode="numeric" placeholder="ör. 30"></div>
      </div>
      <div class="uc">
        <div><label>Fiyat maddesi</label><select name="fiyat_maddesi" id="szFm" onchange="szFmDegis()">@foreach ($fiyatMaddesi as $fk => $fa)<option value="{{ $fk }}">{{ $fa }}</option>@endforeach</select></div>
        <div id="szTavanK"><label>Artış tavanı (%)</label><input name="artis_tavan" id="szTavan" inputmode="decimal" placeholder="ör. 10"></div>
        <div><label>Madde notu</label><input name="fiyat_maddesi_not" id="szFmNot" placeholder="ör. 6 ayda bir TÜFE" autocomplete="off"></div>
      </div>

      <div class="bolum"><span class="ms">inventory_2</span> Malzemeler ve fiyatlar</div>
      <div class="kalemler">
        <div class="kalem-bas"><span>Malzeme</span><span>Birim fiyat (KDV hariç)</span><span>Birim</span><span>Aylık taahhüt</span><span></span></div>
        <div id="szKalemler"></div>
        <button type="button" class="kalem-ekle" onclick="szKalemEkle()"><span class="ms" style="font-size:18px">add</span> Malzeme ekle</button>
      </div>

      <div class="bolum"><span class="ms">attach_file</span> Sözleşme belgesi</div>
      <div id="szMevcut" class="dosyalar"></div>
      <div id="szBelgeSil"></div>
      <div class="yukle" id="szYukle" style="margin-top:8px">
        <div class="dugmeler">
          <button type="button" onclick="document.getElementById('szSec').click()"><span class="ms">upload_file</span> Dosya seç</button>
          <button type="button" onclick="document.getElementById('szKamera').click()"><span class="ms">photo_camera</span> Fotoğrafını çek</button>
        </div>
        <small>PDF, Word, Excel veya fotoğraf · dosya başına en fazla {{ $yukLimit }} · fotoğraflar otomatik küçültülür · buraya da sürükleyebilirsin</small>
      </div>
      <input type="file" id="szSec" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" hidden>
      <input type="file" id="szKamera" accept="image/*" capture="environment" hidden>
      <input type="file" name="belgeler[]" id="szGercek" multiple hidden>
      <div id="szYeni" class="dosyalar"></div>

      <label>Not <span style="font-weight:600;color:var(--sub2)">(isteğe bağlı)</span></label>
      <textarea name="not" id="szNot" rows="2" placeholder="Minimum sipariş, iade şartı, özel koşullar…"></textarea>
    </div>
    <div class="alt">
      <button type="button" class="b ikincil" onclick="szKapat()">Vazgeç</button>
      <button type="submit" class="b ana"><span class="ms" style="font-size:18px">save</span><span id="szKaydet">Sözleşmeyi Kaydet</span></button>
    </div>
  </form>
</div>

{{-- ===== Tekli fiyat popup ===== --}}
<div class="fap-ort" id="faPop">
  <form class="fap" method="POST" action="/fiyat-anlasmalari/ekle" enctype="multipart/form-data" onsubmit="return faKontrol()">
    @csrf
    <input type="hidden" name="id" id="faId">
    <div class="bas"><span class="ms">sell</span><h3 id="faBaslik">Tekli Fiyat Anlaşması</h3><button type="button" class="kapat" onclick="faKapat()"><span class="ms">close</span></button></div>
    <div class="gov">
      <div class="iki">
        <div><label>Tedarikçi</label><select name="tedarikci_id" id="faTed" required onchange="faIpucu()"><option value="">Seçin…</option>@foreach ($tedarikciler as $t)<option value="{{ $t->id }}">{{ $t->ad }}</option>@endforeach</select></div>
        <div><label>Malzeme</label><select name="malzeme_id" id="faMal" required onchange="faBirimDoldur();faIpucu()"><option value="">Seçin…</option>@foreach ($malzemeler as $m)<option value="{{ $m->id }}">{{ $m->ad }}</option>@endforeach</select></div>
      </div>
      <label>Anlaşılan birim fiyat (KDV hariç)</label>
      <div class="fiyatkutu">
        <input name="birim_fiyat" id="faFiyat" inputmode="decimal" placeholder="0,00 ₺" required autocomplete="off">
        <input name="birim" id="faBirim" list="faBirimler" placeholder="birim" autocomplete="off">
      </div>
      <div class="ipucu" id="faIpucu"><span class="ms">history</span><span id="faIpucuYazi"></span><button type="button" onclick="faIpucuKullan()">Bu fiyatı yaz</button></div>
      <label>Geçerlilik süresi</label>
      <div class="hizli" id="faHizli">
        <button type="button" data-ay="1">1 ay</button><button type="button" data-ay="3">3 ay</button><button type="button" data-ay="6">6 ay</button><button type="button" data-ay="12">1 yıl</button><button type="button" data-ay="0">Süresiz</button>
      </div>
      <div class="iki">
        <div><input type="date" name="baslangic" id="faBas" title="Başlangıç"></div>
        <div><input type="date" name="bitis" id="faBit" title="Bitiş (boş = süresiz)"></div>
      </div>
      <div class="iki">
        <div><label>Aylık alım taahhüdü <span style="font-weight:600;color:var(--sub2)">(isteğe bağlı)</span></label><input name="taahhut_miktar" id="faTaah" inputmode="decimal" placeholder="ör. 200" autocomplete="off"></div>
        <div><label>Not <span style="font-weight:600;color:var(--sub2)">(isteğe bağlı)</span></label><input name="not" id="faNot" placeholder="Sözleşme no, teslim şartı…" autocomplete="off"></div>
      </div>
      <label>Belge <span style="font-weight:600;color:var(--sub2)">(teklif mektubu, fiyat listesi…)</span></label>
      <div id="faMevcut" class="dosyalar" style="margin-top:0"></div>
      <div class="yukle" id="faYukle" style="margin-top:6px">
        <div class="dugmeler">
          <button type="button" onclick="document.getElementById('faSec').click()"><span class="ms">upload_file</span> Dosya seç</button>
          <button type="button" onclick="document.getElementById('faKamera').click()"><span class="ms">photo_camera</span> Fotoğraf çek</button>
        </div>
      </div>
      <input type="file" id="faSec" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,image/*" hidden>
      <input type="file" id="faKamera" accept="image/*" capture="environment" hidden>
      <input type="file" name="belgeler[]" id="faGercek" multiple hidden>
      <div id="faYeni" class="dosyalar"></div>
      <div style="font-size:11.5px;color:var(--sub);margin-top:10px">Birden çok malzemeyi kapsayan bir sözleşmen varsa <a href="#" onclick="faKapat();szAc();return false" style="color:#4F46E5;font-weight:800">Yeni Sözleşme</a> ile tek seferde ekle.</div>
    </div>
    <div class="alt">
      <button type="button" class="b ikincil" onclick="faKapat()">Vazgeç</button>
      <button type="submit" class="b ana"><span class="ms" style="font-size:18px">save</span><span id="faKaydet">Kaydet</span></button>
    </div>
  </form>
</div>
<datalist id="faBirimler">@foreach ($birimler as $b)<option value="{{ $b }}">@endforeach</datalist>

{{-- ===== Silme onayı ===== --}}
<div class="fap-ort" id="silPop" onclick="if (event.target === this) this.classList.remove('ac')">
  <form class="fap kucuk" method="POST" id="silForm">
    @csrf
    <input type="hidden" name="id" id="silId"><input type="hidden" name="sil" value="1">
    <div class="gov">
      <div class="uyari-ik"><span class="ms">delete</span></div>
      <h3 id="silBaslik">Silinsin mi?</h3>
      <p><b id="silAd" style="color:var(--ink)"></b><br><span id="silAcik">Silinen kayıt geri gelmez. Geçici olarak askıya almak için Durdur'u kullanabilirsin.</span></p>
    </div>
    <div class="alt">
      <button type="button" class="b ikincil" onclick="document.getElementById('silPop').classList.remove('ac')">Vazgeç</button>
      <button type="submit" class="b tehlike">Evet, sil</button>
    </div>
  </form>
</div>

{{-- ===== Belge görüntüleyici ===== --}}
<div class="fap-ort" id="bgPop" onclick="if (event.target === this) bgKapat()">
  <div class="fap goruntule">
    <div class="bas"><span class="ms">attach_file</span><h3 id="bgAd" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></h3>
      <button type="button" class="kapat" id="bgOnceki" title="Önceki" onclick="bgGit(-1)"><span class="ms">chevron_left</span></button>
      <button type="button" class="kapat" id="bgSonraki" title="Sonraki" onclick="bgGit(1)"><span class="ms">chevron_right</span></button>
      <a class="kapat" id="bgIndir" title="İndir" href="#"><span class="ms">download</span></a>
      <a class="kapat" id="bgYeni" title="Yeni sekmede aç" href="#" target="_blank" rel="noopener"><span class="ms">open_in_new</span></a>
      <button type="button" class="kapat" onclick="bgKapat()"><span class="ms">close</span></button></div>
    <div class="icerik" id="bgIcerik"></div>
  </div>
</div>
@endunless

<script>
(function () {
  const SALT = @json($salt);
  const el = (x) => document.getElementById(x);
  const para = (v) => Number(v).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  const yerel = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  const bugun = () => yerel(new Date());
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
  const boyutYaz = (b) => b > 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
  const sayiOku = (v) => { let s = String(v || '').trim().replace(/\s|₺/g, ''); if (s.includes(',')) s = s.replace(/\./g, '').replace(',', '.'); return parseFloat(s); };
  let filtre = 'hepsi';

  window.faSekme = function (s) {
    document.querySelectorAll('#fa .sekme').forEach(b => b.classList.toggle('on', b.dataset.s === s));
    document.querySelectorAll('#fa .panel').forEach(p => p.classList.toggle('on', p.id === 'pnl-' + s));
  };
  window.faFiltre = function (f) {
    filtre = f;
    document.querySelectorAll('#faCip .cip').forEach(c => c.classList.toggle('on', c.dataset.f === f));
    faUygula();
    const l = el('faListe'); if (l) l.scrollIntoView({behavior: 'smooth', block: 'nearest'});
  };
  window.faUygula = function () {
    const q = (el('faAra') ? el('faAra').value : '').toLocaleLowerCase('tr-TR').trim();
    let gor = 0;
    document.querySelectorAll('#faListe .sat').forEach(s => {
      const ok = (filtre === 'hepsi' || (filtre === 'uyari' ? s.dataset.u === '1' : s.dataset.f.split(' ').includes(filtre))) && (!q || s.dataset.ara.includes(q));
      s.style.display = ok ? '' : 'none'; if (ok) gor++;
    });
    if (el('faYok')) el('faYok').style.display = gor ? 'none' : 'block';
  };
  if (SALT) return;

  const IPUCU = @json((object) ($ipucu ?? [])), MALBIRIM = @json((object) ($malBirim ?? []));
  const SOZ = @json(collect($sozKart)->keyBy('id'));
  const MALZ = @json($malzemeler->map(fn ($m) => ['id' => (int) $m->id, 'ad' => $m->ad])->values());
  const LIMIT = {{ (int) $limitBayt }};

  // ---------- Belge seçici (dosya + kamera + sürükle) — fotoğraflar küçültülür ----------
  function belgeAlani(on) {
    let liste = [];
    const gercek = el(on + 'Gercek'), kutu = el(on + 'Yeni'), alan = el(on + 'Yukle');
    const kucult = (f) => new Promise(res => {
      if (!/^image\/(jpe?g|png|webp)$/i.test(f.type) || f.size < 900 * 1024) return res(f);
      const img = new Image(), url = URL.createObjectURL(f);
      img.onload = () => {
        const max = 2000, o = Math.min(1, max / Math.max(img.width, img.height));
        const c = document.createElement('canvas'); c.width = Math.round(img.width * o); c.height = Math.round(img.height * o);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height); URL.revokeObjectURL(url);
        c.toBlob(b => res(b && b.size < f.size ? new File([b], f.name.replace(/\.\w+$/, '') + '.jpg', {type: 'image/jpeg'}) : f), 'image/jpeg', .82);
      };
      img.onerror = () => { URL.revokeObjectURL(url); res(f); };
      img.src = url;
    });
    const ciz = () => {
      const dt = new DataTransfer();
      liste.forEach(x => { if (!x.hata) dt.items.add(x.f); });
      gercek.files = dt.files;
      kutu.innerHTML = liste.map((x, i) => `<div class="dosya ${x.hata ? 'hata' : 'yeni'}"><span class="ms">${x.hata ? 'error' : (x.f.type.startsWith('image/') ? 'image' : (/pdf/.test(x.f.type) ? 'picture_as_pdf' : 'description'))}</span>
        <span class="ad" title="${esc(x.f.name)}">${esc(x.f.name)}</span><span class="bt">${x.hata ? esc(x.hata) : boyutYaz(x.f.size) + ' · eklenecek'}</span>
        <button type="button" data-i="${i}" title="Kaldır"><span class="ms" style="font-size:18px;color:inherit">close</span></button></div>`).join('');
      kutu.querySelectorAll('button').forEach(b => b.onclick = () => { liste.splice(+b.dataset.i, 1); ciz(); });
    };
    const ekle = async (dosyalar) => {
      for (const f0 of dosyalar) {
        const f = await kucult(f0);
        liste.push({f, hata: f.size > LIMIT ? 'Çok büyük (en fazla ' + boyutYaz(LIMIT) + ')' : null});
      }
      ciz();
    };
    [el(on + 'Sec'), el(on + 'Kamera')].forEach(inp => inp.addEventListener('change', () => { ekle([...inp.files]); inp.value = ''; }));
    ['dragenter', 'dragover'].forEach(ev => alan.addEventListener(ev, e => { e.preventDefault(); alan.classList.add('surukle'); }));
    ['dragleave', 'drop'].forEach(ev => alan.addEventListener(ev, e => { e.preventDefault(); alan.classList.remove('surukle'); }));
    alan.addEventListener('drop', e => ekle([...e.dataTransfer.files]));
    return {sifirla() { liste = []; ciz(); }, bekleyenHata() { return liste.some(x => x.hata); }};
  }
  const szBelge = belgeAlani('sz'), faBelge = belgeAlani('fa');
  const mevcutCiz = (kutuId, belgeler, silKutuId) => {
    const kutu = el(kutuId);
    kutu.innerHTML = (belgeler || []).map((b, i) => `<div class="dosya" data-id="${b.id}"><span class="ms">${/pdf/.test(b.mime || '') ? 'picture_as_pdf' : ((b.mime || '').startsWith('image/') ? 'image' : 'description')}</span>
      <span class="ad" title="${esc(b.ad)}">${esc(b.ad)}</span><span class="bt">${boyutYaz(b.boyut || 0)}</span>
      <a href="#" title="Görüntüle" data-i="${i}"><span class="ms" style="font-size:18px">visibility</span></a>
      ${silKutuId ? `<button type="button" title="Kaldır" data-id="${b.id}"><span class="ms" style="font-size:18px;color:inherit">delete</span></button>` : ''}</div>`).join('');
    kutu.querySelectorAll('a[data-i]').forEach(a => a.onclick = (e) => { e.preventDefault(); bgAc(belgeler, +a.dataset.i); });
    if (silKutuId) {
      el(silKutuId).innerHTML = '';
      kutu.querySelectorAll('button[data-id]').forEach(b => b.onclick = () => {
        const satir = b.closest('.dosya'), id = b.dataset.id, var_ = el(silKutuId).querySelector('input[value="' + id + '"]');
        if (var_) { var_.remove(); satir.classList.remove('silinecek'); }
        else { el(silKutuId).insertAdjacentHTML('beforeend', '<input type="hidden" name="belge_sil[]" value="' + id + '">'); satir.classList.add('silinecek'); }
      });
    }
  };

  // ---------- Süre düğmeleri ----------
  const sureBagla = (kutuId, basId, bitId) => document.querySelectorAll('#' + kutuId + ' button').forEach(b => b.addEventListener('click', function () {
    document.querySelectorAll('#' + kutuId + ' button').forEach(x => x.classList.toggle('on', x === b));
    const ay = +b.dataset.ay;
    if (!el(basId).value) el(basId).value = bugun();
    if (!ay) { el(bitId).value = ''; return; }
    const d = new Date(el(basId).value + 'T12:00:00'); d.setMonth(d.getMonth() + ay); d.setDate(d.getDate() - 1);
    el(bitId).value = yerel(d);
  }));
  sureBagla('szHizli', 'szBas', 'szBit'); sureBagla('faHizli', 'faBas', 'faBit');
  const tarihKontrol = (basId, bitId) => {
    if (el(basId).value && el(bitId).value && el(bitId).value < el(basId).value) { el(bitId).focus(); el(bitId).style.borderColor = '#F43F5E'; return false; }
    return true;
  };

  // ---------- Sözleşme ----------
  const malSecenek = '<option value="">Malzeme seçin…</option>' + MALZ.map(m => `<option value="${m.id}">${esc(m.ad)}</option>`).join('');
  window.szKalemEkle = function (k) {
    k = k || {};
    const d = document.createElement('div'); d.className = 'kalem';
    d.innerHTML = `<input type="hidden" name="kalem_id[]" value="${k.id || ''}">
      <select name="kalem_malzeme[]">${malSecenek}</select>
      <input name="kalem_fiyat[]" inputmode="decimal" placeholder="Fiyat ₺" autocomplete="off" value="${k.fiyat != null ? String(k.fiyat).replace('.', ',') : ''}">
      <input name="kalem_birim[]" list="faBirimler" placeholder="birim" autocomplete="off" value="${esc(k.birim || '')}">
      <input name="kalem_taahhut[]" inputmode="decimal" placeholder="Taahhüt/ay" autocomplete="off" value="${k.taahhut != null ? String(k.taahhut).replace('.', ',') : ''}">
      <button type="button" class="ks" title="Kaldır"><span class="ms" style="font-size:18px">close</span></button>
      <div class="ip"></div>`;
    const sec = d.querySelector('select'); sec.value = k.malzeme_id || '';
    sec.addEventListener('change', () => { const b = MALBIRIM[sec.value]; const bi = d.querySelector('input[name="kalem_birim[]"]'); if (b && !bi.value) bi.value = b; kalemIpucu(d); });
    d.querySelector('.ks').onclick = () => { d.remove(); if (!el('szKalemler').children.length) szKalemEkle(); };
    el('szKalemler').appendChild(d);
    kalemIpucu(d);
    return d;
  };
  const kalemIpucu = (d) => {
    const r = IPUCU[el('szTed').value + '-' + d.querySelector('select').value], ip = d.querySelector('.ip');
    ip.classList.toggle('ac', !!r);
    if (!r) return;
    ip.innerHTML = 'Son alış: <b>' + para(r.f) + ' ₺' + (r.b ? '/' + esc(r.b) : '') + '</b> (' + r.t.split('-').reverse().join('.') + ') · <a>bu fiyatı yaz</a>';
    ip.querySelector('a').onclick = () => { d.querySelector('input[name="kalem_fiyat[]"]').value = String(r.f).replace('.', ','); if (r.b) d.querySelector('input[name="kalem_birim[]"]').value = r.b; };
  };
  window.szIpucuYenile = () => el('szKalemler').querySelectorAll('.kalem').forEach(kalemIpucu);
  window.szFmDegis = () => { el('szTavanK').style.visibility = el('szFm').value === 'tavan' ? 'visible' : 'hidden'; };
  window.szAc = function (s) {
    s = s || null;
    el('szId').value = s ? s.id : '';
    el('szBaslik').textContent = s ? 'Sözleşmeyi Düzenle' : 'Yeni Sözleşme';
    el('szKaydet').textContent = s ? 'Değişiklikleri Kaydet' : 'Sözleşmeyi Kaydet';
    const set = (id, v) => { el(id).value = v == null ? '' : String(v); };
    set('szTed', s ? s.ted_id : ''); set('szAdi', s && s.baslik); set('szNo', s && s.sozlesme_no); set('szImza', s && s.imza_tarihi); set('szImzalayan', s && s.imzalayan);
    set('szBas', s ? s.baslangic : bugun()); set('szBit', s && s.bitis); set('szVade', s && s.odeme_vade_gun); set('szTeslim', s && s.teslim_suresi); set('szFesih', s && s.fesih_ihbar_gun);
    set('szFm', s ? s.fiyat_maddesi : 'sabit'); set('szTavan', s && s.artis_tavan != null ? String(s.artis_tavan).replace('.', ',') : ''); set('szFmNot', s && s.fiyat_maddesi_not); set('szNot', s && s.not);
    el('szBit').style.borderColor = '';
    document.querySelectorAll('#szHizli button').forEach(b => b.classList.remove('on'));
    szFmDegis();
    el('szKalemler').innerHTML = '';
    (s && s.kalemler.length ? s.kalemler : [{}]).forEach(k => szKalemEkle(k));
    mevcutCiz('szMevcut', s ? s.belgeler : [], 'szBelgeSil');
    szBelge.sifirla();
    el('szPop').classList.add('ac');
    el('szPop').querySelector('.fap').scrollTop = 0;
  };
  window.szAcId = (id) => SOZ[id] && szAc(SOZ[id]);
  window.szKapat = () => el('szPop').classList.remove('ac');
  window.szKontrol = function () {
    let gecerli = 0, hata = false;
    el('szKalemler').querySelectorAll('.kalem').forEach(d => {
      const sec = d.querySelector('select'), fi = d.querySelector('input[name="kalem_fiyat[]"]');
      sec.style.borderColor = fi.style.borderColor = '';
      if (!sec.value && !fi.value) return;
      const f = sayiOku(fi.value);
      if (!sec.value) { sec.style.borderColor = '#F43F5E'; hata = true; }
      if (!(f > 0)) { fi.style.borderColor = '#F43F5E'; hata = true; } else fi.value = String(f);
      const ta = d.querySelector('input[name="kalem_taahhut[]"]'); if (ta.value) { const t = sayiOku(ta.value); ta.value = t > 0 ? String(t) : ''; }
      if (sec.value && f > 0) gecerli++;
    });
    if (hata) return false;
    if (!gecerli) { const d = el('szKalemler').querySelector('.kalem select'); if (d) { d.focus(); d.style.borderColor = '#F43F5E'; } return false; }
    if (el('szTavan').value) { const t = sayiOku(el('szTavan').value); el('szTavan').value = t >= 0 ? String(t) : ''; }
    return tarihKontrol('szBas', 'szBit');
  };

  // ---------- Tekli fiyat ----------
  let ipucuKayit = null;
  window.faAc = function (k) {
    k = k || null;
    el('faId').value = k ? k.id : '';
    el('faBaslik').textContent = k ? 'Anlaşmayı Düzenle' : 'Tekli Fiyat Anlaşması';
    el('faKaydet').textContent = k ? 'Değişiklikleri Kaydet' : 'Kaydet';
    el('faTed').value = k ? k.ted_id : '';
    el('faMal').value = k ? k.mal_id : '';
    el('faFiyat').value = k ? String(k.fiyat).replace('.', ',') : '';
    el('faBirim').value = k ? (k.birim || '') : '';
    el('faBas').value = k ? (k.baslangic || '') : bugun();
    el('faBit').value = k ? (k.bitis || '') : '';
    el('faNot').value = k ? (k.not || '') : '';
    el('faTaah').value = k && k.taahhut ? String(k.taahhut).replace('.', ',') : '';
    ['faFiyat', 'faBit'].forEach(i => el(i).style.borderColor = '');
    document.querySelectorAll('#faHizli button').forEach(b => b.classList.remove('on'));
    mevcutCiz('faMevcut', k ? k.belgeler : [], null);
    faBelge.sifirla();
    faIpucu();
    el('faPop').classList.add('ac');
    setTimeout(() => (k ? el('faFiyat') : el('faTed')).focus(), 60);
  };
  window.faKapat = () => el('faPop').classList.remove('ac');
  window.faBirimDoldur = function () { const b = MALBIRIM[el('faMal').value]; if (b && !el('faBirim').value) el('faBirim').value = b; };
  window.faIpucu = function () {
    const r = IPUCU[el('faTed').value + '-' + el('faMal').value] || null;
    ipucuKayit = r;
    el('faIpucu').classList.toggle('ac', !!r);
    if (r) el('faIpucuYazi').innerHTML = 'Bu tedarikçiden son alış: <b>' + para(r.f) + ' ₺' + (r.b ? ' / ' + esc(r.b) : '') + '</b> (' + r.t.split('-').reverse().join('.') + ')';
  };
  window.faIpucuKullan = function () { if (!ipucuKayit) return; el('faFiyat').value = String(ipucuKayit.f).replace('.', ','); if (ipucuKayit.b) el('faBirim').value = ipucuKayit.b; };
  window.faKontrol = function () {
    const f = sayiOku(el('faFiyat').value);
    if (!(f > 0)) { el('faFiyat').focus(); el('faFiyat').style.borderColor = '#F43F5E'; return false; }
    el('faFiyat').value = String(f);
    if (el('faTaah').value) { const t = sayiOku(el('faTaah').value); el('faTaah').value = t > 0 ? String(t) : ''; }
    return tarihKontrol('faBas', 'faBit');
  };

  // ---------- Silme ----------
  window.silSor = function (adres, id, ad, baslik, acik) {
    el('silForm').action = adres; el('silId').value = id; el('silAd').textContent = ad; el('silBaslik').textContent = baslik;
    el('silAcik').textContent = acik || 'Silinen kayıt geri gelmez. Geçici olarak askıya almak için Durdur\'u kullanabilirsin.';
    el('silPop').classList.add('ac');
  };

  // ---------- Belge görüntüleyici ----------
  let bgListe = [], bgSira = 0;
  window.bgAc = function (liste, i) { bgListe = liste || []; bgSira = i || 0; bgGoster(); el('bgPop').classList.add('ac'); };
  window.bgGit = function (y) { if (!bgListe.length) return; bgSira = (bgSira + y + bgListe.length) % bgListe.length; bgGoster(); };
  window.bgKapat = function () { el('bgPop').classList.remove('ac'); el('bgIcerik').innerHTML = ''; };
  function bgGoster() {
    const b = bgListe[bgSira]; if (!b) return;
    const url = '/fiyat-anlasmalari/belge/' + b.id;
    el('bgAd').textContent = b.ad + (bgListe.length > 1 ? ' (' + (bgSira + 1) + '/' + bgListe.length + ')' : '');
    el('bgIndir').href = url + '?indir=1'; el('bgYeni').href = url;
    el('bgOnceki').style.display = el('bgSonraki').style.display = bgListe.length > 1 ? '' : 'none';
    const m = b.mime || '';
    el('bgIcerik').innerHTML = m.startsWith('image/') && !/hei[cf]/.test(m) ? `<img src="${url}" alt="">`
      : (/pdf/.test(m) ? `<iframe src="${url}"></iframe>`
      : `<div class="bos-on"><span class="ms" style="font-size:44px;color:#4F46E5">description</span><br>Bu dosya türü tarayıcıda önizlenemiyor.<br><br><a class="b ana" href="${url}?indir=1" style="display:inline-flex">İndir</a></div>`);
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { szKapat(); faKapat(); bgKapat(); el('silPop').classList.remove('ac'); }
    if (el('bgPop').classList.contains('ac') && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) bgGit(e.key === 'ArrowRight' ? 1 : -1);
  });
  // popup'lar body'ye: sayfa kapsayıcısı içinde ortalanmaz
  ['szPop', 'faPop', 'silPop', 'bgPop'].forEach(i => document.body.appendChild(el(i)));
})();
</script>
@endsection

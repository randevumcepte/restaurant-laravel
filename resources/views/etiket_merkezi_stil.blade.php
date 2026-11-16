@include('demirbas_stil')
<style>
#dm .pano{max-width:none;margin:0;padding:18px 22px 90px}
#dm .em-iz{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(0,1fr);gap:14px;align-items:start}
#dm .em-iz.esit{grid-template-columns:repeat(2,minmax(0,1fr))}
#dm .kart .alt-yazi{font-size:12.5px;color:var(--sub);line-height:1.55;margin:-4px 0 12px}
#dm .fm label{display:block;font-size:11.5px;font-weight:700;color:var(--sub);margin:10px 0 5px}
#dm .fm input:not([type=checkbox]):not([type=radio]),#dm .fm select,#dm .fm textarea{width:100%;border:1px solid var(--line);border-radius:11px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:14px;outline:none;font-family:inherit;box-sizing:border-box}
#dm .fm input:focus,#dm .fm select:focus,#dm .fm textarea:focus{border-color:#818CF8;box-shadow:0 0 0 3px rgba(129,140,248,.2)}
#dm .fm .iz{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
#dm .fm .uc{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
#dm .fm .dort{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
#dm .fm .ipucu,#dm .ipucu{font-size:11.5px;color:var(--sub);margin-top:6px;line-height:1.5}
#dm .fm .dugmeler{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
#dm .modlar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:4px}
#dm .modlar label{margin:0;display:flex;flex-direction:column;gap:3px;padding:11px 12px;border:1px solid var(--line);border-radius:13px;cursor:pointer;font-size:13px;font-weight:700;color:var(--ink);text-transform:none}
#dm .modlar label small{font-size:11.5px;font-weight:600;color:var(--sub);line-height:1.4}
#dm .modlar input{position:absolute;opacity:0;pointer-events:none}
#dm .modlar label:has(input:checked){border-color:#A5B4FC;background:color-mix(in srgb,#6366F1 7%,var(--card));box-shadow:0 0 0 2px rgba(129,140,248,.25)}
#dm .modlar label:has(input:disabled){opacity:.45;cursor:not-allowed}
#dm .rozetler{display:flex;gap:4px;flex-wrap:wrap}
#dm .tz{display:inline-flex;align-items:center;gap:3px;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;background:color-mix(in srgb,var(--tr,#6366F1) 12%,transparent);color:var(--tr,#6366F1);white-space:nowrap}
#dm .em-sat{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:11px 13px;display:grid;grid-template-columns:minmax(0,1.5fr) minmax(170px,.7fr) minmax(0,1.1fr) 132px;gap:12px;align-items:center}
#dm .em-sat .islem{justify-content:flex-end}
#dm .em-sat:hover{box-shadow:0 6px 18px rgba(15,23,42,.06)}
#dm .em-sat.pasif{opacity:.55}
#dm .fiy b{font-size:15px;color:var(--ink);font-weight:900}
#dm .fiy s{font-size:11.5px;color:var(--sub2);margin-right:5px}
#dm .fiy small{display:block;font-size:11px;color:var(--sub)}
#dm .tablo{width:100%;border-collapse:collapse;font-size:13px}
#dm .tablo th{text-align:left;font-size:11px;font-weight:700;color:var(--sub);text-transform:uppercase;letter-spacing:.3px;padding:8px 10px;border-bottom:1px solid var(--line);white-space:nowrap}
#dm .tablo td{padding:9px 10px;border-bottom:1px solid var(--line);color:var(--ink);vertical-align:middle}
#dm .tablo tr:last-child td{border-bottom:0}
#dm .tablo .mono{font-family:ui-monospace,monospace;font-size:12px;letter-spacing:.3px}
#dm .tablo-sar{overflow-x:auto}
#dm .bilgi{display:flex;gap:10px;align-items:flex-start;background:color-mix(in srgb,#0EA5E9 8%,var(--card));border:1px solid color-mix(in srgb,#0EA5E9 22%,var(--line));border-radius:13px;padding:11px 13px;font-size:12.5px;color:var(--ink);line-height:1.55;margin-bottom:12px}
#dm .bilgi .ms{color:#0EA5E9;font-size:20px;flex:none}
#dm .bilgi.uyari{background:color-mix(in srgb,#F59E0B 9%,var(--card));border-color:color-mix(in srgb,#F59E0B 28%,var(--line))}
#dm .bilgi.uyari .ms{color:#D97706}
#dm .kalip{display:flex;gap:3px;flex-wrap:wrap;font-family:ui-monospace,monospace;font-size:15px;font-weight:700;margin:6px 0 2px}
#dm .kalip span{padding:5px 7px;border-radius:7px;background:color-mix(in srgb,var(--kc) 14%,transparent);color:var(--kc)}
#dm .kalip-ack{display:flex;gap:10px;flex-wrap:wrap;font-size:11.5px;color:var(--sub)}
#dm .kalip-ack i{display:inline-block;width:9px;height:9px;border-radius:3px;margin-right:4px;vertical-align:middle;background:var(--kc)}
#dm .hazir{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
#dm .hazir button{border:1px dashed #A5B4FC;background:none;color:#4F46E5;border-radius:10px;padding:6px 10px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit}
body.koyu #dm .hazir button{color:#c7d2fe;border-color:#4c4fa0}
#dm .sonuc-kutu{border-radius:13px;padding:12px 14px;margin-top:10px;font-size:13px;line-height:1.6}
#dm .sonuc-kutu.iyi{background:color-mix(in srgb,#10B981 10%,var(--card));border:1px solid color-mix(in srgb,#10B981 28%,var(--line))}
#dm .sonuc-kutu.kotu{background:color-mix(in srgb,#F43F5E 9%,var(--card));border:1px solid color-mix(in srgb,#F43F5E 26%,var(--line))}
#dm .bos-kucuk{text-align:center;color:var(--sub);padding:22px 14px;font-size:13px;line-height:1.55}
#dm .bos-kucuk .ms{display:block;font-size:34px;color:var(--sub2);margin-bottom:6px}
/* Şablon düzenleyici */
#dm .sb-alan{position:relative;margin:6px auto 0;background:#fff;border:1px solid var(--line);box-shadow:0 4px 18px rgba(15,23,42,.1);background-size:100% 100%;background-repeat:no-repeat;touch-action:none;user-select:none}
#dm .sb-k{position:absolute;border:1.5px dashed #6366F1;background:rgba(99,102,241,.08);color:#111;cursor:move;overflow:hidden;font-family:Arial,sans-serif;line-height:1.1;display:flex;align-items:center;padding:0 2px;box-sizing:border-box}
#dm .sb-k.sec{border-style:solid;background:rgba(99,102,241,.16);box-shadow:0 0 0 2px rgba(99,102,241,.25)}
#dm .sb-k .tut{position:absolute;right:-1px;bottom:-1px;width:12px;height:12px;background:#6366F1;cursor:nwse-resize;border-radius:3px 0 0 0}
#dm .sb-k .et-ad{pointer-events:none;width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#dm .sb-ozellik{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;align-items:end}
#dm .sablon-l{display:flex;gap:10px;flex-wrap:wrap}
#dm .sablon-l a{display:flex;flex-direction:column;gap:6px;width:150px;padding:8px;border:1px solid var(--line);border-radius:13px;text-decoration:none;color:var(--ink);font-size:12.5px;font-weight:700;background:var(--card)}
#dm .sablon-l a.on{border-color:#A5B4FC;box-shadow:0 0 0 2px rgba(129,140,248,.25)}
#dm .sablon-l .on-iz{height:84px;border-radius:8px;background:var(--card2) center/contain no-repeat}
#dm .sablon-l small{color:var(--sub);font-weight:600}
#dm .secim-l{max-height:420px;overflow:auto;border:1px solid var(--line);border-radius:13px}
#dm .secim-l label{display:flex;gap:10px;align-items:center;padding:9px 12px;border-bottom:1px solid var(--line);cursor:pointer;font-size:13px;color:var(--ink);margin:0}
#dm .secim-l label:last-child{border-bottom:0}
#dm .secim-l input{width:18px;height:18px;accent-color:#6366F1;flex:none}
#dm .secim-l .ad{flex:1;min-width:0;font-weight:700}
#dm .secim-l .ad small{display:block;font-weight:600;color:var(--sub);font-size:11.5px}
@media (max-width:1100px){ #dm .em-iz,#dm .em-iz.esit{grid-template-columns:minmax(0,1fr)} }
@media (max-width:980px){
  #dm .em-sat{grid-template-columns:minmax(0,1fr) auto;gap:6px 10px}
  #dm .em-sat .fiy,#dm .em-sat .rozetler{grid-column:1 / 2}
  #dm .em-sat .islem{grid-column:2;grid-row:1 / span 3;flex-direction:column}
}
@media (max-width:760px){
  #dm .fm .iz,#dm .fm .uc{grid-template-columns:minmax(0,1fr)}
  #dm .fm .dort,#dm .sb-ozellik{grid-template-columns:repeat(2,minmax(0,1fr))}
  #dm .modlar{grid-template-columns:minmax(0,1fr)}
  .rk-main,.rk-body{min-width:0}
}
@media (max-width:520px){ #dm .pano{padding:12px 12px 90px} }
</style>
<style>
/* Renk katmanı: sayfa zemini, adım kartları, boş durum, kartlar, ürün satırı şeridi */
#dm{background:radial-gradient(1200px 420px at 15% -80px,rgba(99,102,241,.13),transparent 70%),radial-gradient(900px 380px at 100% 0,rgba(236,72,153,.08),transparent 70%),linear-gradient(180deg,#EEF0FF 0,#F4F5FB 420px)}
body.koyu #dm{background:radial-gradient(1200px 420px at 15% -80px,rgba(99,102,241,.18),transparent 70%),var(--bg)}
#dm nav.em-sek{background:linear-gradient(135deg,#EDE9FE,#E0E7FF 50%,#FCE7F3);border-color:#DDD6FE}
body.koyu #dm nav.em-sek{background:var(--card);border-color:var(--line)}
#dm .adim{--ac:#6366F1;background:linear-gradient(135deg,color-mix(in srgb,var(--ac) 11%,#fff),#fff 75%);border-color:color-mix(in srgb,var(--ac) 25%,#fff)}
#dm .adim:nth-child(2){--ac:#10B981}
#dm .adim:nth-child(3){--ac:#F59E0B}
#dm .adim .no{background:var(--ac);color:#fff;box-shadow:0 4px 10px color-mix(in srgb,var(--ac) 35%,transparent)}
body.koyu #dm .adim{background:var(--card);border-color:color-mix(in srgb,var(--ac) 35%,var(--line))}
#dm .bos{background:linear-gradient(135deg,#EEF2FF 0%,#FDF2F8 55%,#FFFBEB 100%);border:1.5px dashed #C7D2FE}
body.koyu #dm .bos{background:var(--card);border-color:var(--line)}
#dm .bos .ms.bik{background:linear-gradient(135deg,#6366F1,#EC4899);color:#fff;box-shadow:0 10px 24px rgba(99,102,241,.3)}
#dm .kart{background:linear-gradient(180deg,color-mix(in srgb,#6366F1 4%,var(--card)) 0,var(--card) 110px);border-color:color-mix(in srgb,#6366F1 14%,var(--line))}
#dm .em-sat{border-left:4px solid var(--sc,#6366F1);background:linear-gradient(90deg,color-mix(in srgb,var(--sc,#6366F1) 5%,var(--card)) 0,var(--card) 260px)}
#dm .okut{border-color:#C7D2FE;background:linear-gradient(90deg,#F5F3FF,var(--card) 40%)}
body.koyu #dm .okut{background:var(--card);border-color:var(--line)}
#dm .k{background:linear-gradient(135deg,color-mix(in srgb,var(--kr) 15%,#fff),#fff 85%);border-color:color-mix(in srgb,var(--kr) 30%,#fff)}
#dm .k .kd{color:var(--kr)}
#dm .k .ki{background:var(--kr);color:#fff;box-shadow:0 4px 12px color-mix(in srgb,var(--kr) 35%,transparent)}
body.koyu #dm .k{background:var(--card);border-color:color-mix(in srgb,var(--kr) 35%,var(--line))}
</style>

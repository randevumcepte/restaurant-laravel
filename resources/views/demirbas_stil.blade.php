<style>
#dm{--marka:#6366F1;--marka2:#8B5CF6;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--mavi:#0EA5E9;
  --card:#fff;--card2:#f1f5f9;--bg:#f6f7fb;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb}
body.koyu #dm{--card:#161d2e;--card2:#1d2539;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#dm{background:var(--bg);min-height:calc(100vh - 60px)}#dm *{box-sizing:border-box}
#dm .pano{max-width:1180px;margin:0 auto;padding:18px 18px 90px}
.ms{font-family:'Material Icons Outlined';font-weight:normal;font-style:normal;line-height:1;display:inline-block;-webkit-font-smoothing:antialiased;text-transform:none;letter-spacing:normal;white-space:nowrap}
#dm .hero{position:relative;overflow:hidden;border-radius:20px;padding:20px 22px;background:linear-gradient(120deg,#6366F1 0%,#7C6CF4 55%,#8B5CF6 100%);box-shadow:0 10px 26px rgba(99,102,241,.22);display:flex;gap:16px;align-items:center;flex-wrap:wrap;color:#fff}
body.koyu #dm .hero{background:linear-gradient(120deg,#3f3f9e 0%,#4c3f9e 100%)}
#dm .hero::after{content:'';position:absolute;right:-70px;top:-90px;width:240px;height:240px;border-radius:50%;background:rgba(255,255,255,.08);pointer-events:none}
#dm .hero>*{position:relative;z-index:1}
#dm .hero-ik{width:52px;height:52px;border-radius:15px;display:grid;place-items:center;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);flex:none}
#dm .hero-ik .ms{font-size:28px}
#dm .hero .yazi{flex:1;min-width:240px}
#dm .hero h2{margin:0;font-size:19px;font-weight:900}
#dm .hero p{margin:4px 0 0;font-size:13px;color:rgba(255,255,255,.88);line-height:1.5;max-width:620px}
#dm .hb{display:flex;gap:8px;flex-wrap:wrap}
#dm .b{border:0;border-radius:12px;padding:11px 16px;font-weight:800;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;font-family:inherit;white-space:nowrap}
#dm .b .ms{font-size:19px}
#dm .b.beyaz{background:#fff;color:#4F46E5;box-shadow:0 4px 12px rgba(49,46,129,.18)}
#dm .b.cam{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.32);color:#fff}
#dm .b.ana{background:linear-gradient(135deg,var(--marka),var(--marka2));color:#fff;box-shadow:0 4px 12px rgba(99,102,241,.25)}
#dm .b.ik{background:var(--card);color:var(--ink);border:1px solid var(--line)}
#dm .b.tehlike{background:color-mix(in srgb,var(--kirmizi) 10%,var(--card));color:#be123c;border:1px solid color-mix(in srgb,var(--kirmizi) 25%,transparent)}
#dm .flash{display:flex;gap:9px;align-items:center;border-radius:13px;padding:11px 15px;margin-bottom:14px;font-weight:700;font-size:13.5px}
#dm .flash.ok{background:color-mix(in srgb,var(--yesil) 13%,transparent);color:#0b805e}
#dm .flash.hata{background:color-mix(in srgb,var(--kirmizi) 12%,transparent);color:#be123c}
body.koyu #dm .flash.ok{color:#6ee7b7}body.koyu #dm .flash.hata{color:#fda4af}
#dm .okut{margin-top:14px;background:var(--card);border:1.5px solid #E4E4FB;border-radius:16px;padding:10px;display:flex;gap:8px;align-items:center;box-shadow:0 4px 14px rgba(99,102,241,.07)}
body.koyu #dm .okut{border-color:var(--line)}
#dm .okut .ms.i{font-size:26px;color:var(--marka);margin-left:6px}
#dm .okut input,#dm .okut select{flex:1;min-width:0;border:0;outline:none;background:none;color:var(--ink);font-size:16px;font-weight:600;padding:10px 4px;font-family:inherit}
#dm .okut small{font-size:11.5px;color:var(--sub2);white-space:nowrap}
#dm .adimlar{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:12px}
#dm .adim{background:linear-gradient(135deg,#F5F5FF,var(--card) 70%);border:1px solid #E4E4FB;border-radius:14px;padding:12px 14px;display:flex;gap:11px;align-items:flex-start}
body.koyu #dm .adim{background:var(--card);border-color:var(--line)}
#dm .adim .no{width:30px;height:30px;border-radius:10px;display:grid;place-items:center;flex:none;font-weight:900;font-size:13px;background:#EEF0FF;color:#5B5BD6}
body.koyu #dm .adim .no{background:#26285a;color:#c7d2fe}
#dm .adim b{display:block;font-size:13px;color:var(--ink);margin-bottom:2px}
#dm .adim span.t{font-size:12px;color:var(--sub);line-height:1.45}
#dm .kpi{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:14px 0}
#dm .k{min-width:0;background:linear-gradient(135deg,color-mix(in srgb,var(--kr) 6%,var(--card)),var(--card) 70%);border:1px solid color-mix(in srgb,var(--kr) 18%,var(--line));border-radius:16px;padding:13px 14px;display:flex;gap:11px;align-items:center;cursor:pointer;transition:box-shadow .15s}
#dm .k:hover{box-shadow:0 6px 16px rgba(15,23,42,.06)}
#dm .k .ki{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:color-mix(in srgb,var(--kr) 13%,transparent);color:var(--kr);flex:none}
#dm .k .ki .ms{font-size:21px}
#dm .k .kd{font-size:21px;font-weight:900;color:var(--ink);line-height:1.1}
#dm .k .kl{font-size:11.5px;color:var(--sub);font-weight:600;line-height:1.3}
#dm .arac{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
#dm .cipler{display:flex;gap:6px;flex-wrap:wrap;flex:1}
#dm .cip{border:1px solid var(--line);background:var(--card);color:var(--sub);border-radius:20px;padding:7px 12px;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;gap:6px;align-items:center;font-family:inherit}
#dm .cip i{font-style:normal;background:var(--card2);border-radius:10px;padding:0 6px;font-size:11px}
#dm .cip.on{background:linear-gradient(135deg,var(--marka),var(--marka2));border-color:transparent;color:#fff}
#dm .cip.on i{background:rgba(255,255,255,.22)}
#dm .arac select{border:1px solid var(--line);border-radius:11px;padding:8px 10px;background:var(--card);color:var(--ink);font-size:13px;font-family:inherit}
#dm .liste{display:flex;flex-direction:column;gap:8px}
#dm .sat{background:var(--card);border:1px solid var(--line);border-radius:15px;padding:11px 13px;display:grid;grid-template-columns:28px minmax(220px,1.6fr) minmax(130px,1fr) minmax(130px,1fr) 110px auto;gap:12px;align-items:center;transition:box-shadow .15s,border-color .15s}
#dm .sat:hover{box-shadow:0 6px 18px rgba(15,23,42,.06)}
#dm .sat.secili{border-color:#A5B4FC;background:color-mix(in srgb,#6366F1 4%,var(--card))}
#dm .sat input[type=checkbox]{width:18px;height:18px;accent-color:#6366F1;cursor:pointer}
#dm .kim{display:flex;gap:11px;align-items:center;min-width:0;text-decoration:none}
#dm .av{width:40px;height:40px;border-radius:12px;flex:none;display:grid;place-items:center;background:#EEF0FF;color:#5B5BD6}
body.koyu #dm .av{background:#26285a;color:#c7d2fe}
#dm .av .ms{font-size:21px}
#dm .kim .ad{font-weight:800;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#dm .kim .kod{font-size:11.5px;color:var(--sub);font-family:ui-monospace,monospace;letter-spacing:.3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#dm .hc{font-size:12.5px;color:var(--ink);display:flex;gap:6px;align-items:center;min-width:0}
#dm .hc .ms{font-size:17px;color:var(--sub2);flex:none}
#dm .hc span.t{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
#dm .hc.yok{color:var(--sub2)}
#dm .roz{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;white-space:nowrap;background:color-mix(in srgb,var(--dr) 13%,transparent);color:var(--dr)}
#dm .islem{display:flex;gap:5px}
#dm .ib{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:var(--card);color:var(--sub);display:grid;place-items:center;cursor:pointer;text-decoration:none}
#dm .ib .ms{font-size:18px}
#dm .ib:hover{background:var(--card2);color:var(--ink)}
#dm .bos{background:var(--card);border:1.5px dashed var(--line);border-radius:18px;padding:34px 20px;text-align:center}
#dm .bos .ms.bik{font-size:44px;color:var(--marka);background:#EEF0FF;border-radius:20px;padding:14px}
body.koyu #dm .bos .ms.bik{background:#26285a;color:#c7d2fe}
#dm .bos h3{margin:14px 0 6px;font-size:16px;color:var(--ink);font-weight:900}
#dm .bos p{margin:0 auto 16px;font-size:13px;color:var(--sub);max-width:540px;line-height:1.55}
#dm .sonucyok{display:none;text-align:center;color:var(--sub);padding:24px;font-size:13px;background:var(--card);border:1px dashed var(--line);border-radius:15px}
#dm .kart{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:12px}
#dm .kb{display:flex;align-items:center;gap:9px;margin-bottom:10px;flex-wrap:wrap}
#dm .kb h3{margin:0;font-size:15px;font-weight:900;color:var(--ink);flex:1}
#dm .kb .ms{color:#5B5BD6;background:#EEF0FF;border-radius:9px;padding:6px;font-size:18px}
body.koyu #dm .kb .ms{color:#c7d2fe;background:#26285a}
#dmToplu{position:fixed;left:50%;bottom:18px;transform:translate(-50%,160%);transition:transform .2s;z-index:900;background:#312E81;color:#fff;border-radius:16px;padding:10px 12px 10px 16px;display:flex;gap:10px;align-items:center;box-shadow:0 14px 34px rgba(49,46,129,.35);flex-wrap:wrap;max-width:calc(100vw - 24px)}
#dmToplu.ac{transform:translate(-50%,0)}
#dmToplu b{font-size:14px}
#dmToplu label{font-size:12px;opacity:.9;display:flex;gap:6px;align-items:center}
#dmToplu input{width:58px;border:0;border-radius:9px;padding:7px 8px;font-size:13px;font-weight:800;text-align:center}
#dmToplu .tb{border:0;border-radius:10px;padding:9px 13px;font-weight:800;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
#dmToplu .tb .ms{font-size:18px}
#dmToplu .tb.ana{background:#fff;color:#4338CA}
#dmToplu .tb.ik{background:rgba(255,255,255,.14);color:#fff}
.dmp-ort{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);z-index:9999;display:none;align-items:center;justify-content:center;padding:16px}
.dmp-ort.ac{display:flex}
.dmp{--card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e5e7eb;background:var(--card);color:var(--ink);border-radius:20px;width:100%;max-width:720px;max-height:calc(100vh - 32px);overflow:auto;box-shadow:0 24px 60px rgba(15,23,42,.3);animation:dmpGir .18s ease-out;margin:0}
.dmp.dar{max-width:520px}
body.koyu .dmp{--card:#161d2e;--card2:#1d2539;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
@keyframes dmpGir{from{transform:translateY(10px) scale(.98);opacity:0}}
.dmp .bas{display:flex;align-items:center;gap:10px;padding:18px 20px 6px;position:sticky;top:0;background:var(--card);z-index:2}
.dmp .bas .ms{color:#6366F1;font-size:22px}
.dmp .bas h3{margin:0;font-size:16.5px;font-weight:900;flex:1}
.dmp .kapat{border:0;background:var(--card2);color:var(--sub);width:32px;height:32px;border-radius:10px;cursor:pointer;display:grid;place-items:center}
.dmp .gov{padding:6px 20px 8px}
.dmp .bolum{font-size:12px;font-weight:900;color:#6366F1;text-transform:uppercase;letter-spacing:.4px;margin:16px 0 2px;display:flex;align-items:center;gap:6px}
.dmp .bolum .ms{font-size:17px}
.dmp label{display:block;font-size:11.5px;font-weight:800;color:var(--sub);margin:10px 0 5px}
.dmp input,.dmp select,.dmp textarea{width:100%;border:1px solid var(--line);border-radius:11px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:14px;outline:none;font-family:inherit;box-sizing:border-box}
.dmp input:focus,.dmp select:focus,.dmp textarea:focus{border-color:#818CF8;box-shadow:0 0 0 3px rgba(129,140,248,.2)}
.dmp .iz{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.dmp .uc{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.dmp .ipucu{font-size:11.5px;color:var(--sub);margin-top:5px;line-height:1.45}
.dmp .alt{display:flex;gap:9px;justify-content:flex-end;padding:14px 20px 18px;position:sticky;bottom:0;background:var(--card);border-top:1px solid var(--line);flex-wrap:wrap}
.dmp .db{border:0;border-radius:12px;padding:11px 16px;font-weight:800;font-size:13.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
.dmp .db.ikincil{background:var(--card2);color:var(--sub)}
.dmp .db.ana{background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff}
.dmp .db.ana2{background:#EEF0FF;color:#4338CA}
.dmp .db.tehlike{background:#F43F5E;color:#fff}
.dmp .secenek{display:flex;flex-direction:column;gap:7px;margin-top:6px}
.dmp .secenek label{display:flex;gap:10px;align-items:flex-start;margin:0;padding:10px 12px;border:1px solid var(--line);border-radius:12px;cursor:pointer;font-size:13px;font-weight:600;color:var(--ink)}
.dmp .secenek input{width:auto;margin-top:2px;accent-color:#6366F1}
.dmp .secenek label:has(input:checked){border-color:#A5B4FC;background:color-mix(in srgb,#6366F1 6%,var(--card))}
@media (max-width:980px){
  #dm .kpi{grid-template-columns:repeat(3,minmax(0,1fr))}
  #dm .sat{grid-template-columns:28px minmax(0,1fr) auto;gap:6px 10px}
  #dm .sat .hc,#dm .sat .rz{grid-column:2 / 3}
  #dm .sat .islem{grid-column:3;grid-row:1}
}
@media (max-width:760px){
  #dm .adimlar{grid-template-columns:minmax(0,1fr)}
  #dm .kpi{grid-template-columns:repeat(2,minmax(0,1fr))}
  .dmp .iz,.dmp .uc{grid-template-columns:minmax(0,1fr)}
  #dm .okut small{display:none}
}
@media (max-width:520px){
  #dm .pano{padding:12px 12px 90px}
  #dm .hero{padding:16px}
  #dm .hero .yazi{min-width:0}
  #dm .hb,#dm .hb .b{width:100%;justify-content:center}
  #dm .okut{flex-wrap:wrap}
}
</style>

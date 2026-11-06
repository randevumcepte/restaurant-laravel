{{-- İşletme Düzeni + Garson Yönetimi ortak görünümü --}}
<style>
.rk-body{background:radial-gradient(1000px 420px at 88% -8%,color-mix(in srgb,var(--mor1) 13%,transparent),transparent 70%),var(--bg)}
.rk-main{min-width:0}
.idz{--idz-kart:var(--card);--idz-mor-a:color-mix(in srgb,var(--mor1) 12%,transparent);--idz-golge:0 6px 22px rgba(76,52,160,.08)}
.idz-mor{color:var(--mor1)}
.idz-esnek{flex:1}
.idz-panel{display:none}
.idz-panel.aktif{display:block}
/* Üst şerit */
.idz-ara{position:relative;display:flex;align-items:center;gap:8px;background:var(--card);border:1px solid var(--line);border-radius:999px;padding:0 14px;height:40px}
.idz-ara>.material-icons-outlined{font-size:20px;color:var(--sub2)}
.idz-ara input{border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-size:13px;width:100%;min-width:0}
.idz-ust-ara{width:clamp(180px,24vw,420px);margin-right:10px;background:color-mix(in srgb,var(--mor1) 7%,var(--card))}
.idz-ara-sonuc{display:none;position:absolute;top:46px;left:0;right:0;z-index:60;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 16px 40px rgba(30,20,80,.2);padding:6px;max-height:340px;overflow:auto}
.idz-ara-sonuc.acik{display:block}
.idz-ara-sonuc button{display:flex;align-items:center;gap:9px;width:100%;padding:9px 10px;border:0;background:transparent;border-radius:10px;cursor:pointer;color:var(--ink);font:inherit;font-size:13px;text-align:left}
.idz-ara-sonuc button:hover{background:var(--idz-mor-a,rgba(124,58,237,.1))}
.idz-ara-sonuc button .material-icons-outlined{font-size:18px;color:var(--mor1)}
.idz-ara-sonuc small{margin-left:auto;color:var(--sub);font-size:11.5px}
.idz-ara-sonuc .yok{padding:12px;color:var(--sub);font-size:13px;text-align:center}
.idz-ust-ikon{position:relative;display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;background:color-mix(in srgb,var(--mor1) 7%,var(--card));color:var(--ink);text-decoration:none;margin-right:8px}
.idz-ust-ikon b{position:absolute;top:-3px;right:-3px;min-width:18px;height:18px;padding:0 4px;border-radius:999px;background:#EF4444;color:#fff;font-size:10.5px;line-height:18px;text-align:center}
.idz-tarih{display:flex;align-items:center;gap:8px;height:40px;padding:0 14px;border-radius:999px;background:color-mix(in srgb,var(--mor1) 7%,var(--card));color:var(--ink);font-size:12.5px;font-weight:600;white-space:nowrap;margin-right:8px}
.idz-tarih .material-icons-outlined{font-size:19px;color:var(--mor1)}
.idz-tarih b{margin-left:6px}
/* Sekmeler */
.idz-sekmeler{display:flex;gap:10px;overflow-x:auto;padding:2px 2px 12px;scrollbar-width:none}
.idz-sekmeler::-webkit-scrollbar{display:none}
.idz-sekme{flex:0 0 auto;text-decoration:none;display:inline-flex;align-items:center;gap:12px;height:46px;padding:0 24px;border:1px solid var(--line);border-radius:15px;background:var(--card);color:var(--ink);font:inherit;font-size:14px;font-weight:600;cursor:pointer;transition:transform .12s,box-shadow .15s}
.idz-sekme .material-icons-outlined{font-size:23px;color:var(--mor1)}
.idz-sekme:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(76,52,160,.12)}
.idz-sekme.aktif{background:linear-gradient(135deg,#8B5CF6,#7C3AED);border-color:transparent;color:#fff;box-shadow:0 8px 20px rgba(124,58,237,.33)}
.idz-sekme.aktif .material-icons-outlined{color:#fff}
/* Özet */
.idz-ozet{display:grid;grid-template-columns:repeat(2,minmax(190px,1fr)) minmax(190px,1fr) minmax(220px,1.2fr) minmax(320px,1.95fr);gap:12px;margin-bottom:14px}
.idz-stat{display:flex;align-items:center;gap:14px;min-height:78px;padding:12px 16px;background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--idz-golge)}
.idz-stat-ikon{flex:none;display:flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:50%;background:color-mix(in srgb,var(--r) 13%,transparent);color:var(--r)}
.idz-stat-ikon .material-icons-outlined{font-size:30px}
.idz-stat .e{font-size:12.5px;font-weight:600;color:var(--sub2)}
.idz-stat .d{font-size:24px;line-height:1.25;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums}
.idz-trend{margin-left:auto;align-self:flex-end;display:inline-flex;align-items:center;gap:2px;padding:4px 11px;border-radius:999px;background:color-mix(in srgb,#10B981 14%,transparent);color:#059669;font-size:12.5px;font-weight:700}
.idz-trend .material-icons-outlined{font-size:14px}
.idz-afis{position:relative;overflow:hidden;display:flex;align-items:center;gap:14px;min-height:78px;padding:12px 22px;border-radius:18px;color:#fff;font-size:14.5px;font-weight:700;line-height:1.45;background:linear-gradient(90deg,#6D28D9,#7C3AED);box-shadow:var(--idz-golge)}
.idz-afis:before{content:"";position:absolute;top:0;right:0;bottom:0;width:min(52%,340px);background:url('/img/isletme-duzeni-banner.jpg') center/cover no-repeat;-webkit-mask-image:linear-gradient(90deg,transparent,#000 62%);mask-image:linear-gradient(90deg,transparent,#000 62%)}
.idz-afis>*{position:relative}
.idz-afis>.material-icons-outlined{font-size:38px}
/* Plan kartı */
.idz-plan-kart,.idz-kart{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--idz-golge)}
.idz-plan-kart{padding:12px;margin-bottom:14px}
.idz-kart{padding:14px 16px;margin-bottom:12px}
.idz-plan-bas{display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:2px 4px 12px}
.idz-plan-bas h2{margin:0 0 0 4px;font-size:17px;font-weight:800;color:var(--ink)}
.idz-sec-kutu{display:flex;align-items:center;gap:6px;height:38px;padding:0 10px 0 12px;border:1px solid var(--line);border-radius:999px;background:var(--card)}
.idz-sec-kutu .material-icons-outlined{font-size:19px;color:var(--sub2)}
.idz-sec-kutu select{border:0;outline:0;background:transparent;color:var(--ink);font:inherit;font-size:13px;font-weight:600;min-width:76px;max-width:150px}
.idz-sec-kutu select option{color:#1E293B}
.idz-masa-ara{width:clamp(170px,20vw,290px);height:38px}
.idz-btn{display:inline-flex;align-items:center;gap:7px;min-height:38px;padding:0 16px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap;transition:transform .12s,box-shadow .15s,background .12s}
.idz-btn .material-icons-outlined{font-size:18px;color:var(--mor1)}
.idz-btn:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(76,52,160,.13)}
.idz-btn.dolu{background:linear-gradient(135deg,#8B5CF6,#7C3AED);border-color:transparent;color:#fff}
.idz-btn.dolu .material-icons-outlined{color:#fff}
.idz-btn.ikon{padding:0 9px}
.idz-btn.kucuk{min-height:32px;padding:0 11px;font-size:12.5px;font-weight:600}
.idz-btn.kucuk .material-icons-outlined{font-size:15px}
.idz-btn.tehlike{color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 30%,var(--line))}
.idz-btn.tehlike .material-icons-outlined{color:var(--kirmizi)}
.idz-btn.bekliyor{box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 30%,transparent);animation:idzNabiz 1.6s ease-in-out infinite}
.idz-btn:disabled{opacity:.6;cursor:default}
@keyframes idzNabiz{50%{box-shadow:0 0 0 6px color-mix(in srgb,var(--mor1) 14%,transparent)}}
.idz-plan-govde{display:grid;grid-template-columns:142px minmax(0,1fr) 332px;gap:12px;align-items:start}
.idz-alanlar{display:flex;flex-direction:column;gap:8px;padding:12px 10px;border:1px solid var(--line);border-radius:16px;align-self:stretch}
.idz-alan{display:flex;align-items:center;justify-content:space-between;gap:6px;min-height:46px;padding:0 10px;border:1px solid var(--line);border-radius:12px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer;text-align:left}
.idz-alan .ad{display:flex;align-items:center;gap:7px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-alan .ad .material-icons-outlined{display:none;font-size:22px;color:var(--mor1)}
.idz-alan b{flex:none;font-size:12.5px}
.idz-alan.aktif{background:color-mix(in srgb,var(--mor1) 12%,var(--card));border-color:color-mix(in srgb,var(--mor1) 28%,var(--line))}
.idz-alan.aktif .ad .material-icons-outlined{display:inline}
.idz-alan.aktif b{min-width:26px;height:26px;line-height:26px;text-align:center;border-radius:50%;background:color-mix(in srgb,var(--mor1) 22%,transparent);color:var(--mor1)}
.idz-alan.yeni{justify-content:flex-start;gap:4px;margin-top:6px;padding:0 7px;font-size:11.5px;white-space:nowrap}
.idz-alan.yeni .material-icons-outlined{font-size:18px}
/* Kroki */
.idz-cerceve{position:relative;min-width:0;overflow:auto;border-radius:16px;border:9px solid #EADCC8;box-shadow:inset 0 0 0 2px #fff,inset 0 0 26px rgba(120,84,48,.28),0 4px 14px rgba(76,52,160,.13);background:#F3ECE2}
.idz-dunya{--ts:110px;position:relative;min-height:100%;background-color:#F6F0E7;background-image:
  linear-gradient(rgba(150,122,92,.16) 1px,transparent 1px),
  linear-gradient(90deg,rgba(150,122,92,.16) 1px,transparent 1px),
  url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='600'%3E%3Cfilter id='m' x='0' y='0' width='100%25' height='100%25'%3E%3CfeTurbulence type='turbulence' baseFrequency='.005 .011' numOctaves='5' seed='11' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .66 0 0 0 0 .58 0 0 0 0 .49 -3.4 0 0 0 .62'/%3E%3C/filter%3E%3Crect width='600' height='600' filter='url(%23m)'/%3E%3C/svg%3E");
  background-size:12.5% 25%,12.5% 25%,600px 600px}
body.koyu .idz-dunya{filter:brightness(.86)}
.idz-bitki{position:absolute;height:auto;pointer-events:none;filter:drop-shadow(0 7px 5px rgba(60,40,20,.34))}
.idz-isik{position:absolute;width:26px;height:26px;border-radius:50%;pointer-events:none;background:radial-gradient(circle,#fff 0,#FFE9B8 30%,rgba(255,226,160,0) 72%);box-shadow:0 0 40px 20px rgba(255,222,150,.62)}
.idz-resepsiyon{position:absolute;left:50%;bottom:0;width:20%;min-width:130px;transform:translateX(-50%);pointer-events:none;filter:drop-shadow(0 7px 7px rgba(60,40,20,.38))}
.idz-resepsiyon svg{display:block;width:100%}
.idz-resepsiyon span{position:absolute;left:0;right:0;top:58%;text-align:center;color:#FFF3DF;font-size:clamp(11px,1vw,15px);font-weight:800;letter-spacing:.2px}
.idz-masa{position:absolute;display:block;padding:0;border:0;background:transparent;transform:translate(-50%,-50%);cursor:grab;touch-action:none;user-select:none;color:#2B1F3F;transition:opacity .15s,filter .15s}
.idz-masa svg{display:block;width:100%;height:auto;overflow:visible;filter:drop-shadow(0 8px 7px rgba(70,44,20,.36))}
.idz-masa:hover svg{filter:drop-shadow(0 11px 10px rgba(70,44,20,.46))}
.idz-masa:focus-visible{outline:3px solid var(--mor1);outline-offset:3px;border-radius:12px}
.idz-masa.sonuk{opacity:.24}
.idz-masa.tutuldu{cursor:grabbing;z-index:4}
.idz-masa.vurgu svg{filter:drop-shadow(0 0 13px #7C3AED)}
.idz-masa .et{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:2px;pointer-events:none;max-width:calc(var(--ts) * .5)}
.idz-masa .et b{font-size:calc(var(--ts) * .2);line-height:1.05;font-weight:800;color:#1E1633;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-masa .et em{display:inline-flex;align-items:center;gap:3px;padding:1px 7px;border-radius:999px;background:rgba(255,255,255,.92);font-style:normal;font-weight:700;font-size:calc(var(--ts) * .095);color:#2B1F3F;white-space:nowrap}
.idz-masa .et em i{width:7px;height:7px;border-radius:50%}
.idz-plan-bos{position:absolute;left:50%;top:40%;transform:translate(-50%,-50%);display:flex;flex-direction:column;align-items:center;gap:6px;padding:20px 26px;border-radius:16px;background:rgba(255,255,255,.92);color:#334155;font-size:13px;text-align:center;box-shadow:0 8px 26px rgba(60,40,20,.2)}
.idz-plan-bos b{font-size:15px;color:#1E1633}
.idz-plan-bos .idz-btn{margin-top:6px}
.idz-zoom{position:sticky;float:left;left:10px;bottom:10px;margin:-146px 0 0 10px;z-index:5;display:flex;flex-direction:column;width:40px;background:rgba(255,255,255,.95);border-radius:12px;box-shadow:0 5px 16px rgba(60,40,20,.22);overflow:hidden}
.idz-zoom button{height:44px;border:0;background:transparent;color:#334155;cursor:pointer}
.idz-zoom button:hover{background:#F1EDFD;color:#7C3AED}
.idz-zoom .material-icons-outlined{font-size:21px;display:block;margin:auto}
/* Sağ sütun */
.idz-sag{display:flex;flex-direction:column;gap:12px;align-self:stretch}
.idz-sag .idz-kart{margin:0}
.idz-sag .idz-kart:last-child{flex:1;display:flex;flex-direction:column}
.idz-sag .idz-kart:last-child .idz-hizli{flex:1;grid-auto-rows:1fr}
.idz-kart-bas{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.idz-kart-bas h3{margin:0;font-size:15px;font-weight:800;color:var(--ink)}
.idz-kart-bas>.material-icons-outlined{font-size:22px}
.idz-link{display:inline-flex;align-items:center;gap:4px;margin-left:auto;padding:0;border:0;background:transparent;color:var(--mor1);font:inherit;font-size:12.5px;font-weight:700;cursor:pointer;text-decoration:none;white-space:nowrap}
.idz-link .material-icons-outlined{font-size:17px}
.idz-gsatir{display:flex;align-items:center;gap:9px;width:100%;padding:7px 2px;border:0;background:transparent;color:var(--ink);font:inherit;cursor:pointer;text-align:left;border-radius:10px}
.idz-gsatir:hover{background:var(--idz-mor-a)}
.idz-gsatir .ad{flex:1;min-width:0;font-size:12.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-avatar{flex:none;display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;object-fit:cover;background:linear-gradient(135deg,#A78BFA,#7C3AED);color:#fff;font-size:13px;font-weight:800}
.idz-chip{flex:none;max-width:112px;padding:4px 10px;border-radius:999px;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1);font-size:11.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-aktif{flex:none;display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:999px;background:color-mix(in srgb,#10B981 13%,transparent);color:#059669;font-size:11px;font-weight:700}
.idz-aktif i{width:7px;height:7px;border-radius:50%;background:#10B981}
.idz-hizli{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}
.idz-hizli button{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;min-height:62px;padding:6px 2px;border:1px solid var(--line);border-radius:13px;background:var(--card);color:var(--ink);font:inherit;font-size:11.5px;font-weight:600;cursor:pointer;transition:transform .12s,box-shadow .15s,border-color .12s}
.idz-hizli button:hover{transform:translateY(-2px);border-color:color-mix(in srgb,var(--mor1) 42%,var(--line));box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-hizli .material-icons-outlined{font-size:24px;color:var(--mor1)}
/* Alt kartlar */
.idz-alt{display:grid;grid-template-columns:minmax(0,2.1fr) minmax(320px,1fr);gap:12px}
.idz-bkartlar{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
.idz-bkart{position:relative;display:grid;grid-template-columns:48px minmax(0,1fr);gap:4px 10px;padding:12px;border:1px solid var(--line);border-radius:14px;background:color-mix(in srgb,var(--r) 4%,var(--card));color:var(--ink);font:inherit;cursor:pointer;text-align:left;transition:transform .12s,box-shadow .15s}
.idz-bkart:hover{transform:translateY(-2px);box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-bkart .ic{display:flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:50%;background:color-mix(in srgb,var(--r) 14%,transparent);color:var(--r)}
.idz-bkart .ic .material-icons-outlined{font-size:27px}
.idz-bkart .me{display:flex;flex-direction:column;gap:3px;min-width:0;font-size:12.5px;color:var(--sub2)}
.idz-bkart .me b{font-size:13.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-bkart .cubuk{grid-column:1/-1;height:9px;margin-top:8px;border-radius:99px;background:color-mix(in srgb,var(--r) 12%,transparent);overflow:hidden}
.idz-bkart .cubuk i{display:block;height:100%;border-radius:99px;background:var(--r);min-width:0}
.idz-hareket>div:not(.idz-bos){position:relative;display:flex;align-items:center;gap:14px;padding:6px 2px;font-size:12.5px;font-weight:600;color:var(--ink)}
.idz-hareket i{flex:none;width:9px;height:9px;border-radius:50%;background:var(--mor1)}
.idz-hareket time{flex:none;color:var(--sub2);font-variant-numeric:tabular-nums;font-weight:700}
.idz-hareket span{min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.idz-bos{padding:18px 10px;color:var(--sub);font-size:13px;text-align:center}
.idz-bos a{color:var(--mor1);font-weight:700}
/* Sekme içerikleri */
.idz-acik{margin:-2px 0 12px;font-size:12.5px;color:var(--sub2);line-height:1.5}
.idz-rozet{padding:2px 10px;border-radius:999px;background:var(--idz-mor-a);color:var(--mor1);font-size:12px;font-weight:700;white-space:nowrap}
.idz-form-satir{display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end}
.idz-bolge-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;margin-bottom:12px}
.idz-kasa-bas{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.idz-kbaslik{margin-bottom:6px;font-size:11.5px;font-weight:700;color:var(--sub2)}
.idz-kasa form[action="/kasalar/sayfalar"] .rk-btn:not(.bos){background:var(--mor1)!important}
.idz-rol-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}
.idz-rol{padding:12px 14px;border:1px solid var(--line);border-radius:14px;background:var(--card2)}
.idz-rol .b{display:flex;justify-content:space-between;font-size:13.5px;font-weight:700;color:var(--ink)}
.idz-rol .b b{color:var(--mor1)}
.idz-rol .k{margin-top:4px;font-size:12px;color:var(--sub2);line-height:1.45}
.idz-ayar-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:10px}
.idz-ayar{display:flex;align-items:center;gap:12px;padding:13px 14px;border:1px solid var(--line);border-radius:14px;background:var(--card);text-decoration:none;color:var(--ink);transition:transform .12s,box-shadow .15s}
.idz-ayar:hover{transform:translateY(-2px);box-shadow:0 7px 16px rgba(76,52,160,.13)}
.idz-ayar>.material-icons-outlined{font-size:26px;color:var(--mor1)}
.idz-ayar b{display:block;font-size:13.5px}
.idz-ayar small{display:block;margin-top:1px;font-size:11.5px;color:var(--sub)}
.gata-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:8px}
.gata-row{display:flex;align-items:center;gap:11px;background:var(--card);border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.gata-bilgi{min-width:0;flex:1}
.gata-ad{font-weight:800;color:var(--ink);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-rol{font-weight:500;font-size:12px;color:var(--sub2)}
.gata-ozet{font-size:11.5px;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gata-ata-btn{flex-shrink:0;min-height:34px;padding:0 11px;font-size:12.5px}
.gata-modal-box{max-width:520px;width:100%;max-height:85vh;display:flex;flex-direction:column;padding:20px}
.gata-modal-head{display:flex;align-items:center;gap:8px}
#gataModalBody{overflow-y:auto;flex:1;margin:0 -4px;padding:0 4px}
/* Masa kartı */
.idz-pop{display:none;position:fixed;z-index:70;width:290px;padding:13px;background:var(--card);border:1px solid var(--line);border-radius:15px;box-shadow:0 16px 40px rgba(30,20,80,.27)}
.idz-pop.acik{display:block}
.idz-pop .b{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:5px}
.idz-pop .b b{font-size:15px;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.idz-pop .d{flex:none;display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:99px;background:color-mix(in srgb,var(--r) 13%,transparent);color:var(--r);font-size:11.5px;font-weight:700}
.idz-pop .d i{width:7px;height:7px;border-radius:50%;background:var(--r)}
.idz-pop .s{font-size:12px;color:var(--sub2);line-height:1.5}
.idz-pop .a{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:10px}
.idz-pop .a button{display:flex;flex-direction:column;align-items:center;gap:3px;padding:7px 0;border:1px solid var(--line);border-radius:10px;background:var(--card);color:var(--ink);font:inherit;font-size:11px;font-weight:600;cursor:pointer}
.idz-pop .a button:hover{background:var(--card2)}
.idz-pop .a .material-icons-outlined{font-size:19px;color:var(--mor1)}
.idz-pop .a .k,.idz-pop .a .k .material-icons-outlined{color:var(--kirmizi)}
/* Modallar */
.idz-modal{display:none;position:fixed;inset:0;z-index:110;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.6);backdrop-filter:blur(3px)}
.idz-modal.acik{display:flex}
.idz-modal-kutu{width:100%;max-width:420px;padding:20px}
.idz-modal-bas{display:flex;align-items:center;gap:8px;margin-bottom:14px}
.idz-modal-bas b{flex:1;font-size:16px;color:var(--ink)}
.idz-modal-alt{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
.mduz-ibtn{display:inline-flex;align-items:center;justify-content:center;width:31px;height:31px;border-radius:8px;border:none;background:transparent;color:var(--sub);cursor:pointer;transition:background .12s}
.mduz-ibtn:hover{background:color-mix(in srgb,currentColor 15%,transparent)}
.mduz-ibtn .material-icons-outlined{font-size:19px}
.mduz-qr-box{background:var(--card);border-radius:22px;max-width:360px;width:100%;overflow:hidden;box-shadow:0 24px 70px rgba(0,0,0,.4);animation:mduzPop .18s ease}
@keyframes mduzPop{from{transform:scale(.94);opacity:0}to{transform:scale(1);opacity:1}}
.mduz-qr-head{display:flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#7C3AED,#4F46E5);color:#fff;padding:14px 18px}
.mduz-qr-masa{font-size:19px;font-weight:800;letter-spacing:.2px}
.mduz-qr-body{padding:20px 22px 4px;text-align:center}
.mduz-qr-sube{font-size:16px;font-weight:800;color:var(--ink)}
.mduz-qr-ipucu{font-size:12px;color:var(--sub);margin-top:2px}
.mduz-qr-frame{display:inline-flex;background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:14px;margin:14px auto 0;box-shadow:0 6px 20px rgba(0,0,0,.1)}
.mduz-qr-ozellik{display:flex;justify-content:center;gap:14px;margin-top:16px;flex-wrap:wrap}
.mduz-qr-ozellik span{display:inline-flex;flex-direction:column;align-items:center;gap:3px;font-size:10.5px;font-weight:600;color:var(--sub)}
.mduz-qr-ozellik .material-icons-outlined{font-size:20px;color:var(--mor1)}
.mduz-qr-foot{display:flex;gap:10px;padding:16px 22px 22px}
/* Duyarlı */
@media(max-width:1500px){.idz-ust-ara{width:190px}.idz-ozet{grid-template-columns:repeat(4,minmax(0,1fr))}.idz-afis{grid-column:1/-1}}
@media(max-width:1280px){.idz-plan-govde{grid-template-columns:132px minmax(0,1fr)}.idz-sag{grid-column:1/-1;display:grid;grid-template-columns:1fr 1fr;gap:12px}.idz-sag .idz-kart{margin:0}.idz-alt{grid-template-columns:1fr}.idz-tarih span:not(.material-icons-outlined){display:none}.idz-tarih b{margin-left:0}}
@media(max-width:860px){
  .idz-ust-ara,.idz-tarih,.rk-header .alt{display:none}
  .rk-header .ikon,.idz-ust-ikon{flex:none}
  .idz-ozet{grid-template-columns:repeat(2,minmax(0,1fr))}
  .idz-plan-govde{grid-template-columns:minmax(0,1fr)}
  .idz-alanlar{flex-direction:row;overflow-x:auto;padding:8px}
  .idz-alan{flex:0 0 auto;min-height:40px}
  .idz-alan.yeni{margin-top:0}
  .idz-sag{grid-template-columns:minmax(0,1fr)}
  .idz-esnek{flex-basis:100%;height:0}
  .idz-masa-ara{flex:1 1 100%;width:auto}
  .idz-plan-bas .idz-btn{flex:1;justify-content:center}
}
@media(max-width:480px){
  .idz-stat{gap:9px;padding:10px;min-height:66px}
  .idz-stat-ikon{width:40px;height:40px}
  .idz-stat-ikon .material-icons-outlined{font-size:23px}
  .idz-stat .d{font-size:20px}
  .idz-trend{display:none}
  .idz-sekme{height:42px;padding:0 15px;font-size:13px;gap:8px}
  .idz-bkartlar,.gata-list{grid-template-columns:minmax(0,1fr)}
  .gata-row{flex-wrap:wrap}
}
@media(prefers-reduced-motion:reduce){.idz-btn.bekliyor{animation:none}.idz *{transition:none!important}}
</style>

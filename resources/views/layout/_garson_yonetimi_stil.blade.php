{{-- İşletme Düzeni > Garson Yönetimi görünümü (_idz_stil'den sonra yüklenir) --}}
<style>
.gy-panel{display:none}
.gy-panel.aktif{display:block}
.gy-ust{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:4px;flex-wrap:wrap}
.gy-sekmeler{flex:1;min-width:0;gap:8px;padding-bottom:10px}
.gy-sekme{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border:1px solid var(--line);border-radius:999px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer;transition:background .12s,border-color .12s}
.gy-sekme .material-icons-outlined{font-size:19px;color:var(--mor1)}
.gy-sekme:hover{border-color:color-mix(in srgb,var(--mor1) 40%,var(--line))}
.gy-sekme.aktif{background:color-mix(in srgb,var(--mor1) 13%,var(--card));border-color:var(--mor1);color:var(--mor1);font-weight:700}
.gy-yeni{min-height:42px;padding:0 22px;border-radius:999px;font-size:14px}
.idz-trend.kotu{background:color-mix(in srgb,#EF4444 13%,transparent);color:#DC2626}
.gy-kartlar{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1.25fr) minmax(0,1.1fr) minmax(0,1.1fr);gap:12px;margin-bottom:12px}
.gy-kartlar .idz-stat{min-width:0}
.gy-kartlar .idz-stat>div{min-width:0}
.gy-kartlar .e{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-kartlar .d{white-space:nowrap}
.gy-canli{display:inline-flex;align-items:center;gap:5px;padding:2px 9px;border-radius:999px;background:color-mix(in srgb,#10B981 13%,transparent);color:#059669;font-size:11.5px;font-weight:700}
.gy-canli i{width:7px;height:7px;border-radius:50%;background:#10B981;animation:gyNabiz 1.8s ease-in-out infinite}
@keyframes gyNabiz{50%{opacity:.35}}
.gy-orta{display:grid;grid-template-columns:minmax(0,1.9fr) minmax(400px,1fr);gap:12px;align-items:stretch;margin-bottom:12px}
.gy-orta>.idz-kart{margin:0}
.gy-isi-kart .idz-kart-bas,[data-gy-panel=isi] .idz-kart-bas{flex-wrap:wrap}
.gy-isi-govde{display:grid;grid-template-columns:minmax(0,1fr) 138px;gap:10px;align-items:stretch}
.gy-cerceve{height:auto}
.gy-efsane{display:flex;flex-direction:column;gap:9px}
.gy-ef-bas{display:flex;align-items:center;gap:5px;font-size:12.5px;font-weight:800;color:var(--ink)}
.gy-ef-bas .material-icons-outlined{font-size:18px}
.gy-ef{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;color:var(--ink)}
.gy-ef i{flex:none;width:28px;height:28px;border-radius:50%;background:radial-gradient(circle,var(--r),color-mix(in srgb,var(--r) 40%,transparent))}
.gy-ef small{display:block;font-size:11px;font-weight:500;color:var(--sub2)}
.gy-oneri{margin-top:auto;padding:10px;border-radius:13px;background:color-mix(in srgb,#F59E0B 11%,var(--card));font-size:11.5px;color:var(--ink);line-height:1.4}
.gy-oneri .b{display:flex;align-items:center;gap:4px;font-weight:800}
.gy-oneri .b .material-icons-outlined{font-size:17px;color:#F59E0B}
.gy-oneri p{margin:5px 0 8px}
.gy-oneri .idz-btn{width:100%;justify-content:center}
.gy-m{cursor:pointer;touch-action:auto}
.gy-parlama{position:absolute;left:50%;top:50%;width:72%;height:72%;transform:translate(-50%,-50%);border-radius:50%;background:radial-gradient(circle,var(--r) 0,color-mix(in srgb,var(--r) 45%,transparent) 55%,transparent 75%);opacity:.72;pointer-events:none}
.gy-m .et small{display:block;font-size:calc(var(--ts) * .115);font-weight:800;color:#1E1633;line-height:1.1}
.gy-gsat{display:grid;grid-template-columns:38px minmax(80px,1fr) 78px minmax(70px,1fr) 36px 58px;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid var(--line)}
.gy-gsat:last-child{border-bottom:0}
.gy-gsat .idz-avatar{width:38px;height:38px;font-size:15px}
.gy-gsat .ad{min-width:0}
.gy-gsat .ad b{display:block;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gsat .ad small{font-size:11px;color:var(--sub)}
.gy-gsat .me{min-width:0;font-size:12px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gsat .dk{font-size:12px;font-weight:700;color:var(--ink);text-align:right;white-space:nowrap}
.gy-durum{display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:700;color:var(--r);white-space:nowrap}
.gy-durum i{width:8px;height:8px;border-radius:50%;background:var(--r)}
.gy-spark{display:flex;align-items:flex-end;gap:2px;height:26px}
.gy-spark i{flex:1;min-width:2px;border-radius:1px;background:var(--mor1)}
.gy-spark.soluk i{opacity:.28}
.gy-alt{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(0,1fr) minmax(0,1.03fr);gap:12px}
.gy-alt>.idz-kart{margin:0}
.gy-mini-sec{margin-left:auto;border:0;outline:0;padding:5px 8px;border-radius:9px;background:var(--idz-mor-a);color:var(--mor1);font:inherit;font-size:12.5px;font-weight:700;cursor:pointer}
.gy-mini-sec option{color:#1E293B}
.gy .idz-sec-kutu select{min-width:92px}
.gy-halkalar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.gy-halka{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center}
.gy-halka svg{width:min(100%,88px);height:auto}
.gy-halka b{position:absolute;top:0;left:0;right:0;height:min(100%,88px);max-height:88px;aspect-ratio:auto;display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--ink)}
.gy-halka span{margin-top:6px;font-size:11.5px;font-weight:600;color:var(--ink);line-height:1.25}
.gy-perf{display:grid;grid-template-columns:28px minmax(80px,1fr) minmax(70px,1.5fr) 38px;align-items:center;gap:10px;padding:8px 0}
.gy-perf .s{display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;color:#3B2A12;font-size:12.5px;font-weight:800}
.gy-perf b{min-width:0;font-size:13px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-perf .c,.gy-iliste .c{display:block;height:9px;border-radius:99px;background:var(--idz-mor-a);overflow:hidden}
.gy-perf .c i,.gy-iliste .c i{display:block;height:100%;border-radius:99px;background:var(--mor1)}
.gy-perf em,.gy-iliste em{font-style:normal;font-size:12.5px;font-weight:700;color:var(--ink);text-align:right}
.gy-garson-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:12px}
.gy-gkart{padding:14px;border:1px solid var(--line);border-radius:15px;background:var(--card)}
.gy-gkart .u{display:flex;align-items:center;gap:10px}
.gy-gkart .idz-avatar{width:44px;height:44px;font-size:17px}
.gy-gkart .ad{flex:1;min-width:0}
.gy-gkart .ad b{display:block;margin-bottom:3px;font-size:14px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-gkart .ad .idz-chip{display:inline-block;max-width:100%}
.gy-gkart .r{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin:12px 0;padding:10px 4px;border-radius:12px;background:var(--card2);text-align:center;font-size:11px;color:var(--sub2)}
.gy-gkart .r b{display:block;font-size:13.5px;color:var(--ink);white-space:nowrap}
.gy-gkart .a{display:flex;gap:8px}
.gy-gkart .a .idz-btn{flex:1;justify-content:center}
.gy-olay-tam{max-height:640px;overflow:auto}
.gy-olay{display:grid!important;grid-template-columns:24px 44px minmax(90px,170px) minmax(0,1fr);align-items:center;gap:10px;padding:9px 4px!important;border-bottom:1px solid var(--line)}
.gy-olay .material-icons-outlined{font-size:20px;color:var(--mor1)}
.gy-olay b{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-isi-buyuk{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:16px;align-items:start}
.gy-iliste{display:grid;grid-template-columns:minmax(60px,90px) minmax(0,1fr) 30px;align-items:center;gap:8px;padding:7px 0}
.gy-iliste b{font-size:12.5px;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gy-alan-serit{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 10px;scrollbar-width:none}
.gy-alan-serit::-webkit-scrollbar{display:none}
.gy-alan-btn{flex:none;display:inline-flex;align-items:center;gap:6px;height:36px;padding:0 15px;border:1px solid var(--line);border-radius:999px;background:var(--card);color:var(--ink);font:inherit;font-size:13px;font-weight:600;cursor:pointer}
.gy-alan-btn .material-icons-outlined{font-size:17px;color:var(--mor1)}
.gy-alan-btn.aktif{background:color-mix(in srgb,var(--mor1) 13%,var(--card));border-color:var(--mor1);color:var(--mor1);font-weight:700}
.gy-uyum{display:flex;align-items:center;gap:14px;margin-bottom:12px;padding:12px 16px;border:1px solid var(--line);border-radius:14px;background:var(--card2)}
.gy-uyum strong{flex:none;font-size:30px;font-weight:800;line-height:1}
.gy-uyum .m{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px;font-size:12.5px;color:var(--sub)}
.gy-uyum .m b{font-size:14px;color:var(--ink)}
.gy-uyum .c{display:block;height:8px;margin-top:2px;border-radius:99px;background:var(--idz-mor-a);overflow:hidden}
.gy-uyum .c i{display:block;height:100%;border-radius:99px}
.gy-uyum .ef{flex:none;display:flex;flex-direction:column;gap:5px;font-size:11.5px;font-weight:600;color:var(--sub)}
.gy-uyum .ef span{display:flex;align-items:center;gap:6px}
.gy-uyum .ef i{width:11px;height:11px;border-radius:50%}
.gy-uyum .ef i.s{background:var(--sub2);opacity:.45}
.gy-uyum.bilgi{font-size:13px;color:var(--ink)}
.gy-uyum.bilgi>.material-icons-outlined{color:var(--mor1)}
.gy-uyum.bilgi p{flex:1;margin:0;line-height:1.4}
.gy-salon{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.gy-bolge{min-width:0;padding:8px;border:1px solid var(--line);border-radius:16px;background:var(--card);transition:opacity .15s}
.gy-bolge.kendi{border-color:var(--mor1);box-shadow:0 0 0 2px color-mix(in srgb,var(--mor1) 30%,transparent)}
.gy-bolge-bas{display:flex;flex-wrap:wrap;align-items:center;gap:6px;width:100%;min-height:30px;margin-bottom:7px;padding:0 2px;border:0;background:transparent;color:var(--ink);font:inherit;text-align:left;cursor:pointer}
.gy-bolge-bas b{font-size:13.5px;font-weight:800}
.gy-bolge-bas em{margin-left:auto;font-style:normal;font-size:12px;font-weight:600;color:var(--sub)}
.gy-bolge-bas .material-icons-outlined{font-size:16px;color:var(--mor1)}
.gy-rozet{padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.gy-rozet.kendi{background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1)}
.gy-rozet.dis{background:color-mix(in srgb,#EF4444 13%,transparent);color:#DC2626}
.gy-mini{box-sizing:border-box;overflow:hidden;border-width:5px;border-radius:12px;cursor:zoom-in}
.gy-mini .idz-dunya{min-height:0}
.gy-mini .idz-isik{display:none}
.gy-mini .idz-masa .et{gap:0}
.gy-mini .idz-masa .et b{font-size:max(10px,calc(var(--ts) * .2 * var(--mb,1)))}
.gy-mini .gy-m .et small{font-size:max(8px,calc(var(--ts) * .115))}
.gy-mini-bos{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);padding:6px 12px;border-radius:10px;background:rgba(255,255,255,.92);color:#334155;font-size:12px;font-weight:600;white-space:nowrap}
.gy-soluk{opacity:.32}
.gy-dis svg{filter:drop-shadow(0 0 2px #EF4444) drop-shadow(0 0 8px #EF4444)}
.gy-dis-rozet{position:absolute;top:2%;right:2%;display:flex;align-items:center;justify-content:center;min-width:19px;height:19px;padding:0 4px;border-radius:999px;background:#EF4444;color:#fff;font-size:11px;font-weight:800;line-height:1;box-shadow:0 0 0 2px #fff}
.gy-yan{margin-bottom:16px}
.gy-yan:last-child{margin-bottom:0}
.gy-uyum-sat{grid-template-columns:minmax(60px,90px) minmax(0,1fr) 38px;width:100%;border:0;background:transparent;font:inherit;text-align:left;cursor:pointer;border-radius:8px}
.gy-uyum-sat:hover{background:var(--idz-mor-a)}
.gy-yan-not{margin:6px 0 0;font-size:11px;color:var(--sub)}
.gy-tablo-kap{overflow-x:auto}
.gy-tablo{width:100%;border-collapse:collapse;font-size:13px;color:var(--ink)}
.gy-tablo th{padding:9px 10px;text-align:left;font-size:12px;font-weight:700;color:var(--sub2);border-bottom:1px solid var(--line);white-space:nowrap}
.gy-tablo td{padding:10px;border-bottom:1px solid var(--line);white-space:nowrap}
@media(max-width:1500px){.gy-kartlar{grid-template-columns:repeat(3,minmax(0,1fr))}.gy-alt{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.gy-alt>.idz-kart:first-child{grid-column:1/-1}}
@media(max-width:1280px){.gy-orta{grid-template-columns:minmax(0,1fr)}.gy-gsat{grid-template-columns:38px minmax(100px,1.2fr) 80px minmax(100px,1.4fr) 40px 70px}}
@media(max-width:860px){
  .gy-kartlar{grid-template-columns:repeat(2,minmax(0,1fr))}
  .gy-alt,.gy-isi-buyuk,.gy-isi-govde,.gy-salon{grid-template-columns:minmax(0,1fr)}
  .gy-uyum{flex-wrap:wrap}
  .gy-uyum .m{flex-basis:calc(100% - 90px)}
  .gy-uyum .ef{flex-direction:row;flex-wrap:wrap}
  .gy-efsane{flex-direction:row;flex-wrap:wrap}
  .gy-ef-bas{flex-basis:100%}
  .gy-oneri{flex-basis:100%}
  .gy-gsat{grid-template-columns:38px minmax(0,1fr) auto;row-gap:2px}
  .gy-gsat .me{grid-column:2/3}
  .gy-gsat .dk{grid-column:3}
  .gy-gsat .gy-spark{display:none}
  .gy-yeni{flex:1 1 100%;justify-content:center}
}
@media(max-width:520px){.gy-halkalar{grid-template-columns:repeat(2,minmax(0,1fr))}.gy-garson-grid{grid-template-columns:minmax(0,1fr)}.gy-olay{grid-template-columns:22px 40px minmax(0,1fr)}.gy-olay>span:last-child{grid-column:3}}
@media(max-width:480px){.gy-sekme{height:36px;padding:0 12px;font-size:12.5px}}
</style>

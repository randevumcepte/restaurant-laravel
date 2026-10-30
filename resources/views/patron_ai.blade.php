@extends('layout.app')
@section('rk_ikon', 'auto_awesome')
@section('baslik', 'Patron AI')
@section('title', 'Patron AI')

@push('head')
<style>
  #pai{max-width:1000px;margin:0 auto}
  #pai *{box-sizing:border-box}

  /* ---------- ÜST BAR ---------- */
  .pai-top{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px}
  .pai-orb{width:48px;height:48px;border-radius:15px;flex:none;display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 8px 20px rgba(124,58,237,.3)}
  .pai-orb .material-icons-outlined{color:#fff;font-size:26px;animation:paiPulse 3.4s ease-in-out infinite}
  @keyframes paiPulse{0%,100%{transform:scale(1);opacity:.92}50%{transform:scale(1.12);opacity:1}}
  .pai-selam{font-size:21px;font-weight:800;color:var(--ink);line-height:1.2;letter-spacing:-.3px}
  .pai-alt{font-size:12.5px;color:var(--sub);margin-top:2px;display:flex;align-items:center;gap:7px;flex-wrap:wrap}
  .pai-badge{display:inline-flex;align-items:center;gap:4px;background:color-mix(in srgb,var(--mor1) 12%,var(--card));border:1px solid color-mix(in srgb,var(--mor1) 25%,var(--line));padding:2px 9px;border-radius:20px;font-weight:700;font-size:11px;color:var(--mor1)}
  .pai-seg{display:inline-flex;background:var(--card);border:1px solid var(--line);border-radius:13px;padding:3px;box-shadow:var(--golge)}
  .pai-tab{border:0;background:transparent;color:var(--sub2);font-weight:700;font-size:13px;padding:7px 14px;border-radius:10px;cursor:pointer;transition:all .15s;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
  .pai-tab.aktif{background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 3px 10px rgba(124,58,237,.3)}
  .pai-ref{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:var(--card);color:var(--sub2);cursor:pointer;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;box-shadow:var(--golge);text-decoration:none}
  .pai-ref:hover{color:var(--mor1);border-color:var(--mor1)}

  /* ---------- BENTO ---------- */
  .pai-bento{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:minmax(96px,auto);gap:13px;margin-bottom:18px;
    grid-template-areas:"ai ai ciro fc" "ai ai mis kay" "krit krit act act"}
  .b-ai{grid-area:ai} .b-ciro{grid-area:ciro} .b-fc{grid-area:fc} .b-mis{grid-area:mis} .b-kay{grid-area:kay} .b-krit{grid-area:krit} .b-act{grid-area:act}
  @media (max-width:900px){
    .pai-bento{grid-template-columns:repeat(2,1fr);grid-template-areas:"ai ai" "ciro fc" "mis kay" "krit krit" "act act"}
  }
  @media (max-width:540px){
    .pai-bento{grid-template-columns:1fr;grid-template-areas:"ai" "ciro" "fc" "mis" "kay" "krit" "act"}
    .pai-selam{font-size:19px}
  }

  /* AI STAR TILE */
  .pai-star{position:relative;overflow:hidden;border-radius:22px;padding:20px 22px;color:#fff;display:flex;flex-direction:column;
    background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 45%,#4F46E5 100%);box-shadow:0 14px 38px rgba(79,70,229,.3)}
  .pai-star::before{content:'';position:absolute;width:240px;height:240px;border-radius:50%;top:-110px;right:-50px;background:radial-gradient(circle,rgba(255,255,255,.2),transparent 70%);animation:paiFloat 9s ease-in-out infinite}
  .pai-star::after{content:'';position:absolute;width:180px;height:180px;border-radius:50%;bottom:-110px;left:20%;background:radial-gradient(circle,rgba(246,206,99,.16),transparent 70%);animation:paiFloat 12s ease-in-out infinite reverse}
  @keyframes paiFloat{0%,100%{transform:translate(0,0)}50%{transform:translate(-16px,12px)}}
  .pai-star-lbl{position:relative;z-index:2;display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:rgba(255,255,255,.9);margin-bottom:10px}
  .pai-star-metin{position:relative;z-index:2;font-size:16px;line-height:1.62;font-weight:600;color:#fff;white-space:pre-line;flex:1}
  .pai-oncelik{position:relative;z-index:2;margin-top:14px;background:rgba(255,255,255,.14);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.2);border-radius:15px;padding:12px 14px}
  .pai-oncelik .ust{display:flex;align-items:center;gap:7px;font-size:11px;font-weight:800;letter-spacing:.4px;text-transform:uppercase;color:#FCD9A8;margin-bottom:5px}
  .pai-oncelik .txt{font-size:13.5px;line-height:1.5;color:#fff;margin-bottom:10px}
  .pai-oncelik-btn{display:inline-flex;align-items:center;gap:6px;background:#fff;color:#4F46E5;border:0;border-radius:11px;padding:9px 16px;font-size:13px;font-weight:800;cursor:pointer;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,.16)}
  .pai-oncelik-btn:hover{transform:translateY(-1px)}

  /* BUGÜNÜN İŞLERİ (GM kokpiti) */
  .pai-cockpit{position:relative;overflow:hidden;border-radius:22px;padding:20px 22px;margin-bottom:16px;color:#fff;
    background:linear-gradient(135deg,#7C3AED 0%,#6D28D9 45%,#4F46E5 100%);box-shadow:0 14px 38px rgba(79,70,229,.3)}
  .pai-cockpit::before{content:'';position:absolute;width:260px;height:260px;border-radius:50%;top:-120px;right:-60px;background:radial-gradient(circle,rgba(255,255,255,.2),transparent 70%);animation:paiFloat 9s ease-in-out infinite}
  .pai-cockpit::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;bottom:-120px;left:15%;background:radial-gradient(circle,rgba(246,206,99,.15),transparent 70%);animation:paiFloat 12s ease-in-out infinite reverse}
  .pai-ck-head{position:relative;z-index:2;display:flex;align-items:center;gap:9px;margin-bottom:9px}
  .pai-ck-lbl{display:flex;align-items:center;gap:7px;font-size:12px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;color:rgba(255,255,255,.92)}
  .pai-ck-say{background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.26);border-radius:20px;padding:2px 10px;font-size:12px;font-weight:800}
  .pai-ck-verdict{position:relative;z-index:2;font-size:15px;line-height:1.6;font-weight:600;color:#fff;white-space:pre-line;margin-bottom:14px}
  .pai-is-list{position:relative;z-index:2}
  .pai-is{display:flex;align-items:center;gap:11px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.14)}
  .pai-is:last-child{border-bottom:0}
  .pai-is-ic{width:34px;height:34px;border-radius:10px;flex:none;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.2)}
  .pai-is-ic .material-icons-outlined{font-size:18px;color:#fff}
  .pai-is-t{font-size:14px;font-weight:700;color:#fff;line-height:1.3}
  .pai-is-a{font-size:11.5px;color:rgba(255,255,255,.78);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .pai-is-btn{flex:none;display:inline-flex;align-items:center;gap:5px;background:#fff;color:#4F46E5;border:0;border-radius:10px;padding:8px 14px;font-size:12.5px;font-weight:800;cursor:pointer;text-decoration:none;box-shadow:0 3px 10px rgba(0,0,0,.14)}
  .pai-is-btn:hover{transform:translateY(-1px)}
  .pai-is-btn.sade{background:rgba(255,255,255,.14);color:#fff;border:1px solid rgba(255,255,255,.24);box-shadow:none}
  .pai-ck-bitti{position:relative;z-index:2;margin-top:13px;display:flex;flex-wrap:wrap;gap:7px;align-items:center}
  .pai-bitti-chip{background:rgba(16,185,129,.26);border:1px solid rgba(255,255,255,.18);color:#fff;border-radius:20px;padding:4px 11px;font-size:11.5px;font-weight:600}
  .pai-ck-bos{position:relative;z-index:2;font-size:14.5px;color:#fff;font-weight:600;padding:6px 0}

  .pai-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px}
  .pai-alt-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-top:13px;margin-bottom:4px}
  @media (max-width:900px){.pai-kpi-grid{grid-template-columns:repeat(2,1fr)}.pai-alt-grid{grid-template-columns:1fr}}
  @media (max-width:540px){.pai-kpi-grid{grid-template-columns:1fr}}

  /* KPI TILE */
  .pai-kpi{position:relative;border-radius:18px;padding:15px 16px;border:1px solid var(--line);background:var(--card);box-shadow:var(--golge);display:flex;flex-direction:column;justify-content:center}
  .pai-kpi .ic{width:34px;height:34px;border-radius:11px;display:flex;align-items:center;justify-content:center;margin-bottom:10px}
  .pai-kpi .ic .material-icons-outlined{font-size:19px}
  .pai-kpi .buyuk{font-size:23px;font-weight:800;color:var(--ink);line-height:1;letter-spacing:-.5px;font-variant-numeric:tabular-nums}
  .pai-kpi .etk{font-size:11.5px;color:var(--sub);margin-top:5px;font-weight:600}
  .pai-kpi .delta{position:absolute;top:13px;right:13px;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px;font-variant-numeric:tabular-nums}

  /* KRİTİK / EYLEM geniş kutular */
  .pai-wide{border-radius:18px;padding:15px 17px;border:1px solid var(--line);background:var(--card);box-shadow:var(--golge);display:flex;align-items:center;gap:13px}
  .pai-wide .ic{width:40px;height:40px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex:none}
  .pai-wide .ic .material-icons-outlined{font-size:22px}
  .pai-chipler{display:flex;flex-wrap:wrap;gap:7px}
  .pai-chip{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border:1px solid var(--line);border-radius:11px;padding:8px 12px;font-size:12.5px;font-weight:700;color:var(--ink);cursor:pointer;transition:all .14s}
  .pai-chip:hover{border-color:var(--mor1);color:var(--mor1);transform:translateY(-1px)}

  /* ---------- DETAY BÖLÜM ---------- */
  .pai-bolum-bas{font-size:12px;font-weight:800;color:var(--sub2);letter-spacing:.5px;text-transform:uppercase;display:flex;align-items:center;gap:8px;margin:22px 2px 12px}
  .pai-bolum-bas .material-icons-outlined{font-size:17px;color:var(--mor1)}
  .pai-blok{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:var(--golge);padding:17px 19px;margin-bottom:14px}
  .pai-blok h4{font-size:12px;font-weight:800;color:var(--sub2);letter-spacing:.4px;margin:0 0 12px;display:flex;align-items:center;gap:8px;text-transform:uppercase}
  .pai-blok h4 .material-icons-outlined{font-size:17px}

  /* analiz kartı */
  .pai-an{border-radius:18px;padding:16px 18px;margin-bottom:12px;background:var(--card);border:1px solid var(--line);box-shadow:var(--golge);border-left:4px solid var(--line)}
  .pai-an-bas{display:flex;align-items:center;gap:11px;margin-bottom:9px}
  .pai-an-ic{width:38px;height:38px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:none}
  .pai-an-ic .material-icons-outlined{font-size:21px}
  .pai-an-bas .t{font-weight:800;font-size:15.5px;color:var(--ink)}
  .pai-an-bas .d{font-size:12.5px;color:var(--sub);font-weight:600}
  .pai-vd{font-size:10px;font-weight:800;padding:4px 11px;border-radius:20px;color:#fff;letter-spacing:.5px;margin-left:auto}
  .pai-yorum{font-size:14px;line-height:1.62;color:var(--ink);margin:2px 0 10px}
  .pai-oz{display:flex;gap:9px;align-items:flex-start;font-size:13px;line-height:1.55;padding:11px 13px;border-radius:12px;margin-top:8px}
  .pai-oz .material-icons-outlined{font-size:17px;flex:none;margin-top:1px}

  .pai-sat{display:flex;align-items:center;gap:11px;padding:11px 0;border-bottom:1px solid var(--line);font-size:13.5px}
  .pai-sat:last-child{border-bottom:0}
  .pai-dot{width:9px;height:9px;border-radius:50%;flex:none}
  .pai-mini{display:inline-flex;align-items:center;gap:6px;background:var(--card2);border-radius:20px;padding:6px 13px;font-size:12.5px;color:var(--sub2);font-weight:600}
  .pai-link{color:var(--mavi);font-size:12.5px;text-decoration:none;white-space:nowrap;font-weight:700}
  .pai-link:hover{text-decoration:underline}
  .pai-btn-sm{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:10px;background:var(--mor1);color:#fff;font-size:12.5px;font-weight:700;text-decoration:none;white-space:nowrap;border:0;cursor:pointer}
  .pai-btn-sm.bos{background:var(--card2);color:var(--sub2);border:1px solid var(--line)}

  /* SKELETON */
  .pai-sk{background:linear-gradient(90deg,var(--card2) 25%,color-mix(in srgb,var(--card2) 55%,var(--card)) 37%,var(--card2) 63%);background-size:400% 100%;animation:paiSh 1.3s ease-in-out infinite;border-radius:10px}
  @keyframes paiSh{0%{background-position:100% 0}100%{background-position:-100% 0}}

  /* EYLEM MODAL */
  .pai-eylem-mod{display:none;position:fixed;inset:0;background:rgba(10,15,30,.55);backdrop-filter:blur(3px);z-index:9999;align-items:center;justify-content:center;padding:16px}
  .pai-eylem-mod.acik{display:flex}
  .pai-eylem-mod .ic{background:var(--card);border-radius:20px;max-width:520px;width:100%;padding:24px;box-shadow:0 24px 70px rgba(0,0,0,.4)}
  .pai-eylem-mod h3{font-size:17px;font-weight:800;color:var(--ink);margin:0 0 8px;display:flex;align-items:center;gap:9px}
  .pai-eylem-mod .ozet{background:var(--card2);border-left:3px solid var(--mor1);border-radius:12px;padding:13px 15px;font-size:13.5px;line-height:1.55;color:var(--ink);margin:12px 0 16px}
  .pai-eylem-mod .uyari{font-size:11.5px;color:var(--sub);margin-bottom:16px;display:flex;align-items:center;gap:6px}
  .pai-eylem-mod .uyari .material-icons-outlined{font-size:14px;color:var(--amber)}
  .pai-eylem-mod .btnler{display:flex;gap:9px;justify-content:flex-end}
  .pai-eylem-mod .btn-yap{background:linear-gradient(135deg,#10B981,#059669);color:#fff;border:0;border-radius:12px;padding:12px 22px;font-weight:800;cursor:pointer;font-size:13.5px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 4px 14px rgba(16,185,129,.32)}
  .pai-eylem-mod .btn-vaz{background:var(--card2);color:var(--sub);border:1px solid var(--line);border-radius:12px;padding:12px 18px;font-weight:700;cursor:pointer;font-size:13.5px}
  .pai-sonuc{font-size:13px;color:var(--ink);line-height:1.5;padding:12px 14px;border-radius:12px;background:var(--card2);margin-top:12px}
  .pai-sonuc.ok{border-left:3px solid #10B981;color:#047857}
  .pai-sonuc.hata{border-left:3px solid #F43F5E;color:#9f1239}

  /* ============ YENİ DASHBOARD (referans tasarım) ============ */
  #pai{max-width:1240px}
  .pai-hero{display:flex;align-items:center;gap:16px;flex-wrap:wrap;background:var(--card);border:1px solid var(--line);border-radius:20px;padding:18px 20px;margin-bottom:18px;box-shadow:0 2px 10px rgba(16,24,40,.04)}
  .pai-hero-ic{width:56px;height:56px;border-radius:16px;flex:none;display:flex;align-items:center;justify-content:center;font-size:28px;background:linear-gradient(135deg,#FDBA74,#F59E0B);box-shadow:0 8px 20px rgba(245,158,11,.28)}
  .pai-hero-ic.aksam{background:linear-gradient(135deg,#6366F1,#4F46E5);box-shadow:0 8px 20px rgba(79,70,229,.28)}
  .pai-hero-selam{font-size:24px;font-weight:900;color:var(--ink);line-height:1.15;letter-spacing:-.4px}
  .pai-hero-alt{font-size:13px;color:var(--sub);margin-top:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
  .pai-hero-sag{margin-left:auto;display:flex;align-items:center;gap:9px}

  .pai-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:18px}
  .pai-k{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:16px 17px;box-shadow:0 2px 10px rgba(16,24,40,.04);position:relative}
  .pai-k .kic{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;margin-bottom:11px}
  .pai-k .kic .material-icons-outlined{font-size:23px}
  .pai-k .kbuyuk{font-size:24px;font-weight:900;color:var(--ink);line-height:1;font-variant-numeric:tabular-nums}
  .pai-k .ketk{font-size:12.5px;color:var(--sub);font-weight:600;margin-top:5px}
  .pai-k .kalt{font-size:11px;color:var(--sub2);margin-top:3px}
  .pai-k .kdelta{position:absolute;top:16px;right:16px;font-size:11px;font-weight:800;padding:3px 8px;border-radius:20px}

  .pai-mid{display:grid;grid-template-columns:1.75fr 1fr;gap:16px;margin-bottom:18px;align-items:start}
  .pai-card{background:var(--card);border:1px solid var(--line);border-radius:20px;padding:18px 20px;box-shadow:0 2px 10px rgba(16,24,40,.04)}
  .pai-card-head{display:flex;align-items:center;gap:9px;margin-bottom:15px}
  .pai-card-head .material-icons-outlined{font-size:20px;color:var(--mor1)}
  .pai-card-head .b{font-size:16px;font-weight:800;color:var(--ink)}
  .pai-card-head .link{margin-left:auto;font-size:12.5px;font-weight:700;color:var(--mor1);text-decoration:none}

  .pai-task-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .pai-task{display:flex;gap:12px;align-items:flex-start;background:var(--card2);border:1px solid var(--line);border-radius:15px;padding:13px 14px}
  .pai-task .tic{width:40px;height:40px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center}
  .pai-task .tic .material-icons-outlined{font-size:20px}
  .pai-task .trot{display:inline-block;font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 8px;border-radius:20px;margin-bottom:5px}
  .pai-task .tbas{font-size:13.5px;font-weight:700;color:var(--ink);line-height:1.3}
  .pai-task .talt{font-size:11.5px;color:var(--sub);margin-top:3px;line-height:1.4}
  .pai-task .tbtn{margin-top:9px;display:inline-flex;align-items:center;gap:5px;border:0;border-radius:10px;padding:8px 13px;font-size:12px;font-weight:700;cursor:pointer;background:var(--mor1);color:#fff;text-decoration:none}
  .pai-task .tbtn.sade{background:var(--card);color:var(--sub);border:1px solid var(--line)}

  .pai-aipanel{background:linear-gradient(160deg,color-mix(in srgb,var(--mor1) 7%,var(--card)),var(--card));border:1px solid color-mix(in srgb,var(--mor1) 20%,var(--line));border-radius:20px;padding:18px 19px;box-shadow:0 2px 10px rgba(124,58,237,.06)}
  .pai-aipanel .ust{display:flex;align-items:center;gap:10px;margin-bottom:11px}
  .pai-aipanel .rob{width:40px;height:40px;border-radius:12px;flex:none;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--mor1),var(--mavi));box-shadow:0 6px 16px rgba(124,58,237,.3)}
  .pai-aipanel .rob .material-icons-outlined{color:#fff;font-size:22px}
  .pai-aipanel .bas{font-size:15.5px;font-weight:800;color:var(--ink)}
  .pai-aipanel .verdict{font-size:13px;line-height:1.6;color:var(--sub);white-space:pre-line;margin-bottom:15px}
  .pai-aipanel .yapar-bas{display:flex;align-items:center;gap:7px;font-size:11.5px;font-weight:800;color:var(--mor1);text-transform:uppercase;letter-spacing:.3px;margin-bottom:9px}
  .pai-aiact{display:flex;flex-direction:column;gap:8px}
  .pai-aiact a,.pai-aiact button{display:flex;align-items:center;gap:11px;width:100%;text-align:left;background:var(--card);border:1px solid var(--line);border-radius:13px;padding:11px 13px;font-size:12.5px;font-weight:600;color:var(--ink);cursor:pointer;text-decoration:none;transition:.15s;font-family:inherit}
  .pai-aiact a:hover,.pai-aiact button:hover{border-color:var(--mor1);background:color-mix(in srgb,var(--mor1) 5%,var(--card))}
  .pai-aiact .aic{width:30px;height:30px;border-radius:9px;flex:none;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 12%,transparent);color:var(--mor1)}
  .pai-aiact .aic .material-icons-outlined{font-size:17px}
  .pai-aiact .material-icons-outlined.ok{margin-left:auto;color:var(--sub2);font-size:18px}

  .pai-sec-bas{display:flex;align-items:center;gap:9px;font-size:16px;font-weight:800;color:var(--ink);margin:4px 2px 13px}
  .pai-sec-bas .material-icons-outlined{font-size:20px;color:var(--mor1)}
  .pai-sec-bas .link{margin-left:auto;font-size:12.5px;font-weight:700;color:var(--mor1);text-decoration:none}

  /* Departman kartları (denetim fragment grid) */
  .pai-dep-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:13px;margin-bottom:18px}
  .pai-dep{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:15px;box-shadow:0 2px 10px rgba(16,24,40,.04)}
  .pai-dep .dust{display:flex;align-items:center;gap:9px;margin-bottom:9px}
  .pai-dep .dic{width:36px;height:36px;border-radius:11px;flex:none;display:flex;align-items:center;justify-content:center}
  .pai-dep .dic .material-icons-outlined{font-size:19px}
  .pai-dep .dad{font-size:13.5px;font-weight:800;color:var(--ink)}
  .pai-dep .droz{font-size:9.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:2px 8px;border-radius:20px;display:inline-block;margin-top:2px}
  .pai-dep .doz{font-size:11.5px;color:var(--sub);line-height:1.45;min-height:32px;margin-bottom:10px}
  .pai-dep .dbar{height:6px;border-radius:5px;background:var(--card2);overflow:hidden;margin-bottom:9px}
  .pai-dep .dbar i{display:block;height:100%;border-radius:5px}
  .pai-dep .dlink{font-size:11.5px;font-weight:700;color:var(--mor1);text-decoration:none}

  /* Detaylı analiz grid */
  #pai-analiz.pai-analiz-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:16px}
  #pai-analiz.pai-analiz-grid .pai-an{background:var(--card);border:1px solid var(--line);border-left-width:4px;border-radius:16px;padding:15px;margin:0}

  @media(max-width:1100px){ .pai-kpis{grid-template-columns:repeat(3,1fr)} .pai-dep-grid{grid-template-columns:repeat(3,1fr)} }
  @media(max-width:900px){ .pai-mid{grid-template-columns:1fr} .pai-task-grid{grid-template-columns:1fr} #pai-analiz.pai-analiz-grid{grid-template-columns:1fr} }
  @media(max-width:640px){ .pai-kpis{grid-template-columns:1fr 1fr} .pai-dep-grid{grid-template-columns:1fr 1fr} .pai-hero-selam{font-size:20px} .pai-hero-sag{margin-left:0;width:100%} }
  @media(max-width:420px){ .pai-kpis{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
<div class="rk-body">
<div id="pai">
  @php
    $adK = $ad ? trim(explode(' ', trim($ad))[0]) : null;
    $saat = (int) now()->format('H');
    $selam = $saat < 11 ? 'Günaydın' : ($saat < 18 ? 'İyi günler' : 'İyi akşamlar');
    $tip = $tip ?? 'sabah';
    $demo = $demo ?? false;
    $v = $brief['veri'] ?? [];
    $tl = fn ($n) => number_format((float) ($n ?? 0), 0, ',', '.') . '₺';
    $bold = fn ($s) => preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', e($s));
    $analizL = $analiz ?? [];
    $aiVar = $aiVar ?? false;
    $aiOzet = $aiOzet ?? null;
    $aiSoguk = $aiSoguk ?? false;
    $kayipTop = array_sum($v['kayip'] ?? []);
    // KPI durum renkleri
    $ciroY = (float) ($v['ciro_yuzde'] ?? 0);
    $fc = (float) ($v['food_cost_yuzde'] ?? 0);
    $krit = (int) ($v['kritik_sayi'] ?? 0);
    $ciroR = !empty($v['kiyas_etiket']) ? ($ciroY >= 5 ? 'var(--yesil)' : ($ciroY <= -10 ? 'var(--kirmizi)' : ($ciroY < 0 ? 'var(--amber)' : 'var(--mavi)'))) : 'var(--mavi)';
    $fcR = $fc > 0 ? ($fc < 28 ? 'var(--yesil)' : ($fc <= 35 ? 'var(--amber)' : 'var(--kirmizi)')) : 'var(--sub)';
    $kayipR = $kayipTop > 0 ? ($kayipTop / max($v['dun_ciro'] ?? 1, 1) * 100 >= 10 ? 'var(--kirmizi)' : 'var(--amber)') : 'var(--yesil)';
    $kritR = $krit > 0 ? 'var(--kirmizi)' : 'var(--yesil)';
    // Verdict + #1 öncelik
    $verdict = $aiOzet ?: ($brief['metin'] ?? ($aiSoguk ? 'AI değerlendirmen hazırlanıyor…' : 'Bugün için özet bulunmuyor.'));
    $oncelik = null;
    foreach (['risk', 'dikkat'] as $sev) { foreach ($analizL as $a) { if (($a['durum'] ?? '') === $sev) { $oncelik = $a; break 2; } } }
  @endphp

  {{-- ============ HERO (selamlama) ============ --}}
  <div class="pai-hero">
    <div class="pai-hero-ic {{ $tip==='aksam'?'aksam':'' }}">{{ $tip==='aksam'?'🌙':'☀️' }}</div>
    <div style="min-width:160px">
      <div class="pai-hero-selam">{{ $selam }}{{ $adK ? ' '.$adK : '' }} 👋</div>
      <div class="pai-hero-alt">
        <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F') }}</span><span>·</span>
        <span>{{ $tip === 'aksam' ? 'akşam raporu' : 'sabah brifingi' }}</span>
        <span class="pai-badge" id="pai-kaynak">{!! $aiVar ? '✨ AI yazdı' : ($aiSoguk ? '⏳ AI hazırlanıyor' : '⚙️ kural motoru') !!}</span>
      </div>
    </div>
    <div class="pai-hero-sag">
      <div class="pai-seg">
        <a href="/patron-ai?tip=sabah{{ $demo?'&demo=1':'' }}" class="pai-tab {{ $tip==='sabah'?'aktif':'' }}">☀️ Sabah</a>
        <a href="/patron-ai?tip=aksam{{ $demo?'&demo=1':'' }}" class="pai-tab {{ $tip==='aksam'?'aktif':'' }}">🌙 Akşam</a>
      </div>
      <a href="/patron-ai?tip={{ $tip }}&force=1{{ $demo?'&demo=1':'' }}" class="pai-ref" title="Yenile"><span class="material-icons-outlined" style="font-size:19px">refresh</span></a>
    </div>
  </div>

  @if ($demo)
  <div style="display:flex;align-items:center;gap:8px;background:color-mix(in srgb,var(--amber) 12%,var(--card));border:1px solid color-mix(in srgb,var(--amber) 40%,var(--line));border-radius:14px;padding:11px 15px;margin-bottom:14px;font-size:12.5px">
    <span class="material-icons-outlined" style="font-size:18px;color:var(--amber)">theater_comedy</span>
    <span style="flex:1;color:var(--sub2)"><b style="color:var(--amber)">DEMO VERİ</b> — gerçek değil; AI'ın veri olunca nasıl yorumladığını gösterir.</span>
    <a href="/patron-ai" class="pai-btn-sm bos">Gerçek veriye dön</a>
  </div>
  @endif

  @if (!$brief)
    <div class="pai-blok" style="text-align:center;color:var(--sub);padding:34px">Bugün için henüz brifing üretilmedi. <a href="/patron-ai?force=1" class="pai-link">Şimdi üret →</a></div>
  @else
  @php $islerL = $isler['isler'] ?? []; $bitenlerL = $isler['bitenler'] ?? []; $onayTop = (int)($onayOp ?? 0) + (int)($onaySa ?? 0); @endphp

  {{-- ============ 5 KPI ============ --}}
  <div class="pai-kpis">
    <div class="pai-k">
      @if (!empty($v['kiyas_etiket']) && $ciroY != 0)<span class="kdelta" style="background:color-mix(in srgb,{{ $ciroR }} 15%,transparent);color:{{ $ciroR }}">{{ $ciroY>0?'▲ +':'▼ ' }}{{ number_format(abs($ciroY),1,',','.') }}%</span>@endif
      <div class="kic" style="background:color-mix(in srgb,{{ $ciroR }} 13%,transparent);color:{{ $ciroR }}"><span class="material-icons-outlined">payments</span></div>
      <div class="kbuyuk">{{ $tl($v['dun_ciro'] ?? 0) }}</div>
      <div class="ketk">{{ $tip==='aksam'?'Bugünkü ciro':'Dünkü ciro' }}</div>
      @if (($v['dun_ciro'] ?? 0) == 0)<div class="kalt" style="color:var(--kirmizi)">veri kontrolü gerekli</div>@elseif (!empty($v['kisi_basi']))<div class="kalt">kişi başı {{ $tl($v['kisi_basi']) }}</div>@endif
    </div>
    <div class="pai-k">
      <div class="kic" style="background:color-mix(in srgb,{{ $kayipR }} 13%,transparent);color:{{ $kayipR }}"><span class="material-icons-outlined">delete_outline</span></div>
      <div class="kbuyuk">{{ $tl($kayipTop) }}</div>
      <div class="ketk">Kayıp / Zayiat</div>
      <div class="kalt">{{ $tip==='aksam'?'(bugün)':'(dün)' }}</div>
    </div>
    <div class="pai-k">
      <div class="kic" style="background:color-mix(in srgb,{{ $kritR }} 13%,transparent);color:{{ $kritR }}"><span class="material-icons-outlined">inventory_2</span></div>
      <div class="kbuyuk" style="color:{{ $krit>0?'var(--kirmizi)':'var(--ink)' }}">{{ $krit }} <span style="font-size:13px;font-weight:700;color:var(--sub)">ürün</span></div>
      <div class="ketk">Kritik stok</div>
      <div class="kalt">{{ $krit>0 ? Illuminate\Support\Str::limit(implode(', ', array_slice($v['kritik'] ?? [], 0, 3)), 34) : 'sorun yok 👍' }}</div>
    </div>
    <div class="pai-k">
      <div class="kic" style="background:color-mix(in srgb,var(--mavi) 13%,transparent);color:var(--mavi)"><span class="material-icons-outlined">receipt_long</span></div>
      <div class="kbuyuk">{{ (int)($acikAdisyon ?? 0) }}</div>
      <div class="ketk">Açık adisyon</div>
      <div class="kalt">{{ ($acikAdisyon ?? 0)>0 ? 'incelenmesi gerekiyor' : 'hepsi kapalı' }}</div>
    </div>
    <div class="pai-k">
      <div class="kic" style="background:color-mix(in srgb,var(--amber) 13%,transparent);color:var(--amber)"><span class="material-icons-outlined">fact_check</span></div>
      <div class="kbuyuk">{{ $onayTop }} <span style="font-size:13px;font-weight:700;color:var(--sub)">işlem</span></div>
      <div class="ketk">Onay bekleyen</div>
      <div class="kalt">{{ (int)($onayOp ?? 0) }} operasyon + {{ (int)($onaySa ?? 0) }} satın alma</div>
    </div>
  </div>

  {{-- ============ ORTA: Öncelikler (sol) + AI öneri (sağ) ============ --}}
  <div class="pai-mid">
    <div class="pai-card">
      <div class="pai-card-head"><span class="material-icons-outlined">track_changes</span><span class="b">Bugün önceliklerin</span>@if (count($islerL))<span class="pai-ck-say" style="background:color-mix(in srgb,var(--mor1) 13%,transparent);color:var(--mor1);border:0;margin:0">{{ count($islerL) }}</span>@endif<a href="/dashboard" class="link">Tümünü gör →</a></div>
      @if (count($islerL))
      <div class="pai-task-grid">
        @foreach ($islerL as $is)
          @php $ak = $is['aksiyon'] ?? ['tip'=>'info']; $rk = $is['renk'] ?? 'var(--mor1)'; @endphp
          <div class="pai-task">
            <div class="tic" style="background:color-mix(in srgb,{{ $rk }} 13%,transparent);color:{{ $rk }}"><span class="material-icons-outlined">{{ $is['ikon'] ?? 'task_alt' }}</span></div>
            <div style="flex:1;min-width:0">
              @if (!empty($is['grup']))<span class="trot" style="background:color-mix(in srgb,{{ $rk }} 14%,transparent);color:{{ $rk }}">{{ $is['grup'] }}</span>@endif
              <div class="tbas">{{ $is['baslik'] ?? '' }}</div>
              @if (!empty($is['aciklama']))<div class="talt">{{ $is['aciklama'] }}</div>@endif
              @if (($ak['tip'] ?? '') === 'eylem')<button class="tbtn" onclick="paiEylemOner('{{ $ak['intent'] }}')">{{ $ak['lbl'] ?? 'Yap' }}</button>
              @elseif (($ak['tip'] ?? '') === 'link')<a class="tbtn" href="{{ $ak['url'] }}">{{ $ak['lbl'] ?? 'Aç' }} →</a>
              @elseif (($ak['tip'] ?? '') === 'ajanda')<button class="tbtn sade" onclick="paiAjandaBitti({{ $ak['id'] }}, this)">{{ $ak['lbl'] ?? 'Bitti' }}</button>@endif
            </div>
          </div>
        @endforeach
      </div>
      @else<div style="color:var(--sub);padding:18px;text-align:center;font-size:13.5px">✅ Bekleyen iş yok — her şey yolunda 👍</div>@endif
      @if (count($bitenlerL))<div style="margin-top:13px;display:flex;gap:7px;flex-wrap:wrap;align-items:center"><span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;color:var(--sub2)">Bugün çözülenler:</span>@foreach ($bitenlerL as $bt)<span class="pai-mini" style="color:var(--yesil)">✓ {{ $bt }}</span>@endforeach</div>@endif
    </div>

    <div class="pai-aipanel">
      <div class="ust"><div class="rob"><span class="material-icons-outlined">smart_toy</span></div><div class="bas">Patron AI ne öneriyor?</div></div>
      <div class="verdict" id="pai-verdict">{!! $bold($verdict) !!}</div>
      <div class="yapar-bas"><span class="material-icons-outlined" style="font-size:15px">auto_awesome</span> Senin için şunları yapabilirim</div>
      <div class="pai-aiact">
        <button onclick="paiEylemOner('kritik_siparis_olustur')"><span class="aic"><span class="material-icons-outlined">inventory_2</span></span>Kritik stok için sipariş taslağı hazırla<span class="material-icons-outlined ok">chevron_right</span></button>
        <button onclick="paiEylemOner('tedarikci_hatirlat')"><span class="aic"><span class="material-icons-outlined">local_shipping</span></span>Tedarikçiye hatırlatma gönder<span class="material-icons-outlined ok">chevron_right</span></button>
        <a href="/dashboard"><span class="aic"><span class="material-icons-outlined">receipt_long</span></span>Açık adisyonları listele<span class="material-icons-outlined ok">chevron_right</span></a>
        <a href="/satinalma-talep"><span class="aic"><span class="material-icons-outlined">fact_check</span></span>Onay bekleyen işlemleri aç<span class="material-icons-outlined ok">chevron_right</span></a>
      </div>
    </div>
  </div>

  {{-- AI TÜKETİM --}}
  @if (!empty($tuketim))
    @php $yz=$tuketim['yuzde']??0; $br=$yz>=90?'var(--kirmizi)':($yz>=70?'var(--amber)':'var(--yesil)'); @endphp
    <div style="display:flex;align-items:center;gap:10px;margin:0 2px 4px;font-size:11.5px;color:var(--sub)">
      <span class="material-icons-outlined" style="font-size:15px;color:var(--mor1)">auto_awesome</span>
      <span>Bu ay dahil AI</span>
      <div style="flex:1;max-width:220px;height:7px;background:var(--card2);border-radius:5px;overflow:hidden"><div style="height:100%;width:{{ $yz }}%;background:{{ $br }};border-radius:5px;transition:width .5s"></div></div>
      <span style="font-weight:700;color:var(--sub2)">{{ $tuketim['kullanim'] }}/{{ $tuketim['hak'] }}</span>
    </div>
  @endif

  {{-- ============ DEPARTMANLARIN DURUMU (lazy) ============ --}}
  @unless ($demo)
  <div class="pai-sec-bas"><span class="material-icons-outlined">apartment</span> Departmanların durumu <a href="/departman-rapor" class="link">Tüm departmanlar →</a></div>
  <div id="pai-denetim">
    <div class="pai-dep-grid">
      @for ($i=0;$i<5;$i++)<div class="pai-dep"><div class="pai-sk" style="width:60%;height:14px;margin-bottom:11px;border-radius:7px"></div><div class="pai-sk" style="width:90%;height:22px;margin-bottom:11px;border-radius:7px"></div><div class="pai-sk" style="width:100%;height:6px;border-radius:5px"></div></div>@endfor
    </div>
  </div>
  @endunless

  {{-- ============ DETAYLI AI ANALİZİ ============ --}}
  <div class="pai-sec-bas"><span class="material-icons-outlined">insights</span> Detaylı AI analizi</div>
  <div id="pai-analiz" class="pai-analiz-grid">@include('patron_ai_analiz', ['analiz' => $analizL])</div>

  {{-- ============ DÜNDEN DEVAM ============ --}}
  @php $sk = $sureklilik ?? ['acik'=>[],'kapanan'=>[]]; @endphp
  @if (count($sk['acik']) || count($sk['kapanan']))
  <div class="pai-blok">
    <h4 style="color:var(--mavi)"><span class="material-icons-outlined">history</span> Dünden Devam</h4>
    @foreach ($sk['kapanan'] as $k)
      <div class="pai-sat"><span class="material-icons-outlined" style="font-size:18px;color:var(--yesil)">check_circle</span><span style="flex:1">{{ $k['konu'] }}</span><span class="rk-sub" style="font-size:11px">{{ $k['durum']==='devredildi'?'devredildi':'çözüldü' }}</span></div>
    @endforeach
    @foreach ($sk['acik'] as $a)
      <div class="pai-sat">
        <span class="pai-dot" style="background:var(--amber)"></span>
        <span style="flex:1">{{ $a['konu'] }}</span>
        <span class="rk-sub" style="font-size:11px">{{ $a['gun']>0 ? $a['gun'].' gündür açık' : 'bugün' }}</span>
        <button class="pai-btn-sm bos" style="color:var(--yesil)" onclick="takipDurum({{ $a['id'] }},'cozuldu')">Çözüldü</button>
      </div>
    @endforeach
  </div>
  @endif

  {{-- ============ BUGÜNKÜ GÜNDEM ============ --}}
  @php $takvim = $v['takvim'] ?? []; @endphp
  @if (count($takvim))
  <div class="pai-blok">
    <h4 style="color:var(--mor1)"><span class="material-icons-outlined">event</span> Bugünkü Gündem</h4>
    @foreach ($takvim as $ev)
      <div class="pai-sat" style="align-items:flex-start">
        <span style="font-weight:800;color:var(--mavi);min-width:44px">{{ $ev['saat'] ?? '' }}</span>
        <div style="flex:1"><div style="font-weight:600">{{ $ev['baslik'] }}</div>@if(!empty($ev['not']))<div class="rk-sub" style="font-size:11.5px">📝 {{ $ev['not'] }}</div>@endif</div>
      </div>
    @endforeach
  </div>
  @endif

  {{-- ============ İLETİŞİM ============ --}}
  @php $wa = $whatsapp ?? ['giden'=>0,'gelen'=>[]]; $eposta = $v['eposta'] ?? []; @endphp
  @if (count($eposta) || $wa['giden'] || count($wa['gelen']))
  <div class="pai-blok">
    <h4 style="color:var(--yesil)"><span class="material-icons-outlined">forum</span> İletişim</h4>
    @if (count($eposta))
      <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:2px 0 6px">📨 Yanıt bekleyen e-posta ({{ count($eposta) }})</div>
      @foreach ($eposta as $e)
        <div class="pai-sat"><span class="material-icons-outlined" style="font-size:17px;color:var(--amber)">mail_outline</span><div style="flex:1"><div style="font-weight:600">{{ $e['subject'] ?? '' }}</div><div class="rk-sub" style="font-size:11px">{{ $e['from'] ?? '' }}</div></div>@if(!empty($e['link']))<a href="{{ $e['link'] }}" target="_blank" class="pai-link">Aç →</a>@endif</div>
      @endforeach
    @endif
    @if ($wa['giden'] || count($wa['gelen']))
      <div style="font-size:12px;font-weight:700;color:var(--sub2);margin:12px 0 6px">💬 WhatsApp</div>
      <div class="rk-sub" style="font-size:12.5px;margin-bottom:4px">Bugün sistemden <b>{{ $wa['giden'] }}</b> mesaj gönderildi.</div>
      @foreach ($wa['gelen'] as $g)
        <div class="pai-sat"><span class="material-icons-outlined" style="font-size:17px;color:#25D366">chat</span><div style="flex:1"><div>{{ $g['mesaj'] }}</div><div class="rk-sub" style="font-size:11px">{{ $g['telefon'] }} · {{ $g['saat'] }}</div></div><a href="/whatsapp" class="pai-link">Yanıtla →</a></div>
      @endforeach
    @endif
    @if (!config('services.google_oauth.client_id') && !config('services.graph_oauth.client_id') && !count($wa['gelen']) && !$wa['giden'])
      <div class="rk-sub" style="font-size:11.5px">Mail/Takvim için <a href="/entegrasyonlar" style="color:var(--mavi)">Entegrasyonlar</a>'dan Gmail/Microsoft bağla; WhatsApp için köprüyü aç.</div>
    @endif
  </div>
  @endif

  {{-- ALT ŞERİT --}}
  <div class="pai-blok" style="display:flex;flex-wrap:wrap;align-items:center;gap:7px">
    <span class="pai-mini">💰 Ciro {{ $tl($v['dun_ciro']??0) }}</span>
    <span class="pai-mini">🍽 FC %{{ $v['food_cost_yuzde']??0 }}</span>
    <span class="pai-mini">📉 Kayıp {{ $tl($kayipTop) }}</span>
    <span class="pai-mini">👥 {{ $v['misafir']??0 }} misafir</span>
    <span class="pai-mini">⚠️ {{ $v['kritik_sayi']??0 }} kritik stok</span>
    <a href="/dashboard" class="pai-link" style="margin-left:auto">Detaylı özet →</a>
  </div>

  @if (config('services.resteos.patron_ai_v2', false) === false)
    <div class="rk-sub" style="font-size:11px;margin-top:4px">💡 AI doğal-dil anlatımı kapalı (kural motoru). Açmak: <code>RESTEOS_PATRON_AI_V2=true</code> + Anthropic anahtarı.</div>
  @endif
  @endif
</div>
</div>

{{-- Eylem onay modali --}}
<div class="pai-eylem-mod" id="paiEylemMod" onclick="if(event.target===this)this.classList.remove('acik')">
  <div class="ic">
    <h3><span class="material-icons-outlined" style="color:var(--mor1)">auto_awesome</span> AI eylem onayı</h3>
    <div class="ozet" id="paiEylemOzet"></div>
    <div class="uyari"><span class="material-icons-outlined">lock</span> Onayın IP'n (<b id="paiEylemIp">—</b>) ile birlikte imzalı log'a yazılacak. Geri alınamaz, yasal kanıt niteliğinde.</div>
    <div class="btnler">
      <button class="btn-vaz" onclick="paiEylemKapat()">Kapat</button>
      <button class="btn-yap" id="paiEylemYapBtn" onclick="paiEylemYap()"><span class="material-icons-outlined" style="font-size:17px">check</span> İşlemi Yap</button>
    </div>
    <div id="paiEylemSonuc" style="display:none" class="pai-sonuc"></div>
  </div>
</div>

@push('scripts')
<script>
var paiTip = @json($tip ?? 'sabah');
var paiSoguk = @json((bool)($aiSoguk ?? false));
var paiDemo = @json((bool)($demo ?? false));
var paiVarBrief = @json((bool)($brief ?? false));

// Lazy: departman denetimi (TEK yavaş parça)
function paiDenetimYukle(){
  var box = document.getElementById('pai-denetim');
  if (!box) return;
  fetch('/patron-ai?frag=denetim&tip='+encodeURIComponent(paiTip), {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
    .then(function(r){ return r.text(); })
    .then(function(html){ box.innerHTML = html; })
    .catch(function(){ box.innerHTML = '<div class="pai-blok" style="color:var(--sub);text-align:center;padding:16px">Departman denetimi yüklenemedi.</div>'; });
}

// Lazy: AI analiz soğuksa üretip tazele (sayfa bloklanmadan)
function paiAnalizUret(){
  var qs = new URLSearchParams({frag:'analiz', tip:paiTip});
  fetch('/patron-ai?'+qs.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
    .then(function(r){ return r.json(); })
    .then(function(j){
      if (!j || !j.ok) return;
      if (j.html){ var el=document.getElementById('pai-analiz'); if(el) el.innerHTML=j.html; }
      if (j.ai_var && j.ozet){
        var vd=document.getElementById('pai-verdict'); if(vd) vd.innerHTML = j.ozet.replace(/\*\*(.+?)\*\*/g,'<b>$1</b>');
        var rz=document.getElementById('pai-kaynak'); if(rz) rz.textContent = '✨ AI yazdı';
      } else {
        var rz2=document.getElementById('pai-kaynak'); if(rz2) rz2.textContent = '⚙️ kural motoru';
      }
    })
    .catch(function(){});
}

document.addEventListener('DOMContentLoaded', function(){
  if (paiVarBrief && !paiDemo) paiDenetimYukle();
  if (paiVarBrief && paiSoguk && !paiDemo) paiAnalizUret();
});

function takipDurum(id, durum) {
  api('/api/patron-ai/takip-durum', { id: id, durum: durum }).then(function () { location.reload(); }).catch(function () { location.reload(); });
}

// Ajanda işini "bitti" yap — satırı anında kaldır, say'ı güncelle
function paiAjandaBitti(id, btn){
  var sat = btn.closest('.pai-is');
  btn.disabled = true; btn.textContent = '...';
  fetch('/ajanda/durum', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', body: JSON.stringify({id:id, durum:'tamam'})})
    .then(function(r){ return r.json(); })
    .then(function(j){
      if (sat){ sat.style.transition='opacity .2s'; sat.style.opacity='0'; setTimeout(function(){ sat.remove(); var s=document.querySelector('.pai-ck-say'); if(s){ var n=parseInt(s.textContent)-1; if(n>0) s.textContent=n; else s.remove(); } }, 200); }
    })
    .catch(function(){ btn.disabled=false; btn.textContent='Bitti'; });
}

// AI eylem onay akışı
var _paiEylemLogId = null;
async function paiEylemOner(intent){
  try{
    const r = await fetch('/api/patron-ai/eylem-oner', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({intent})});
    const j = await r.json();
    if (!j.ok || !j.log_id) { alert(j.hata || 'Önerilemedi.'); return; }
    _paiEylemLogId = j.log_id;
    document.getElementById('paiEylemOzet').textContent = j.ozet;
    document.getElementById('paiEylemSonuc').style.display = 'none';
    document.getElementById('paiEylemYapBtn').disabled = false;
    document.getElementById('paiEylemYapBtn').style.opacity = '1';
    try { document.getElementById('paiEylemIp').textContent = (await (await fetch('https://api.ipify.org?format=json')).json()).ip || 'tespit edilemedi'; } catch(e){ document.getElementById('paiEylemIp').textContent = 'tespit edilemedi'; }
    document.getElementById('paiEylemMod').classList.add('acik');
  }catch(e){ alert('Bağlantı hatası'); }
}

async function paiEylemYap(){
  if (!_paiEylemLogId) return;
  const btn = document.getElementById('paiEylemYapBtn');
  btn.disabled = true; btn.style.opacity = '.6'; btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">hourglass_bottom</span> Yürütülüyor...';
  try{
    const r = await fetch('/api/ai-eylem/onayla', {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.csrf,'Accept':'application/json'}, body: JSON.stringify({log_id: _paiEylemLogId})});
    const j = await r.json();
    const son = j.sonuc || {};
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block';
    el.className = 'pai-sonuc ' + (son.ok ? 'ok' : 'hata');
    el.textContent = son.mesaj || 'Yürütme sonucu alınamadı.';
    btn.innerHTML = '<span class="material-icons-outlined" style="font-size:17px">check</span> İşlemi Yap';
  }catch(e){
    const el = document.getElementById('paiEylemSonuc');
    el.style.display = 'block'; el.className = 'pai-sonuc hata'; el.textContent = 'Bağlantı hatası';
    btn.disabled = false; btn.style.opacity = '1';
  }
}

function paiEylemKapat(){
  document.getElementById('paiEylemMod').classList.remove('acik');
  _paiEylemLogId = null;
}
</script>
@endpush
@endsection

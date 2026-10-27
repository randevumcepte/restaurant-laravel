@extends('layout.app')
@section('rk_ikon', 'fact_check')
@section('baslik', 'Sayım')
@section('title', 'Sayım (Envanter)')

@push('head')
<style>
/* ===== SAYIM – modern redesign ===== */
#sym{--mor1:#7C3AED;--mor2:#9D5DC8;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc}
body.koyu #sym{--card:#1b2235;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650;--bg:#101624}

/* --- Üst sticky özet paneli --- */
#sym .sym-ozet{position:sticky;top:0;z-index:50;padding:14px 16px;border-radius:14px;
  background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 10%,var(--card)),var(--card));
  border:1px solid color-mix(in srgb,var(--mor1) 25%,var(--line));margin-bottom:12px;
  backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}
#sym .sym-ozet-top{display:flex;align-items:center;gap:12px;margin-bottom:10px}
#sym .sym-ozet-sayac{font-size:12.5px;color:var(--sub);font-weight:600;white-space:nowrap}
#sym .sym-ozet-sayac b{color:var(--ink);font-size:15px;font-weight:800}
#sym .sym-ozet-bar{height:6px;background:color-mix(in srgb,var(--line) 70%,transparent);border-radius:3px;overflow:hidden;flex:1;min-width:60px}
#sym .sym-ozet-fill{height:100%;width:0%;background:linear-gradient(90deg,var(--mor1),var(--mavi));border-radius:3px;transition:width .3s ease}
#sym .sym-ozet-chips{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
#sym .ozet-chip{display:inline-flex;align-items:center;gap:5px;padding:6px 11px;border-radius:999px;
  font-size:12px;font-weight:700;background:color-mix(in srgb,var(--sub) 10%,var(--card));
  border:1px solid color-mix(in srgb,var(--sub) 25%,var(--line));color:var(--sub)}
#sym .ozet-chip b{font-weight:800;font-variant-numeric:tabular-nums}
#sym .ozet-chip .dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
#sym .ozet-chip.c-bek{background:color-mix(in srgb,var(--mavi) 14%,var(--card));
  border-color:color-mix(in srgb,var(--mavi) 45%,transparent);color:var(--mavi)}
#sym .ozet-chip.c-bek .dot{background:var(--mavi)}
#sym .ozet-chip.c-eksik{background:color-mix(in srgb,var(--kirmizi) 14%,var(--card));
  border-color:color-mix(in srgb,var(--kirmizi) 45%,transparent);color:var(--kirmizi)}
#sym .ozet-chip.c-eksik .dot{background:var(--kirmizi)}
#sym .ozet-chip.c-fazla{background:color-mix(in srgb,var(--amber) 16%,var(--card));
  border-color:color-mix(in srgb,var(--amber) 50%,transparent);color:#C27803}
#sym .ozet-chip.c-fazla .dot{background:var(--amber)}
#sym .ozet-chip.c-tam{background:color-mix(in srgb,var(--yesil) 14%,var(--card));
  border-color:color-mix(in srgb,var(--yesil) 45%,transparent);color:var(--yesil)}
#sym .ozet-chip.c-tam .dot{background:var(--yesil)}
body.koyu #sym .ozet-chip.c-fazla{color:#FBBF24}
#sym .ozet-chip-tutar{margin-left:auto;background:color-mix(in srgb,var(--mor1) 12%,var(--card));
  border-color:color-mix(in srgb,var(--mor1) 40%,transparent);color:var(--mor1);font-weight:800}
#sym .ozet-chip-tutar.minus{background:color-mix(in srgb,var(--kirmizi) 14%,var(--card));
  border-color:color-mix(in srgb,var(--kirmizi) 45%,transparent);color:var(--kirmizi)}
#sym .ozet-chip-tutar.plus{background:color-mix(in srgb,var(--amber) 16%,var(--card));
  border-color:color-mix(in srgb,var(--amber) 50%,transparent);color:#C27803}
body.koyu #sym .ozet-chip-tutar.plus{color:#FBBF24}

/* --- Küçük bilgi banner --- */
#sym .sym-info{display:flex;align-items:flex-start;gap:8px;padding:10px 12px;border-radius:10px;
  background:color-mix(in srgb,var(--mavi) 6%,transparent);
  border:1px solid color-mix(in srgb,var(--mavi) 20%,transparent);margin-bottom:12px}
#sym .sym-info .material-icons-outlined{color:var(--mavi);font-size:16px;flex-shrink:0;margin-top:1px}
#sym .sym-info p{color:var(--sub);font-size:12px;line-height:1.4;margin:0}

/* --- Araç çubuğu --- */
#sym .sym-araclar{display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
#sym .sym-ara-wrap{position:relative;flex:1;min-width:200px}
#sym .sym-ara-wrap .material-icons-outlined{position:absolute;left:11px;top:50%;transform:translateY(-50%);
  font-size:18px;color:var(--sub);pointer-events:none}
#sym #symAra{width:100%;padding:10px 12px 10px 38px;border:1px solid var(--line);border-radius:10px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s,box-shadow .15s}
#sym #symAra:focus{border-color:var(--mor1);box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 12%,transparent)}
body.koyu #sym #symAra{background:#1b2235}
#sym .sym-toggle{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:10px;
  background:color-mix(in srgb,var(--mavi) 8%,var(--card));
  border:1px solid color-mix(in srgb,var(--mavi) 30%,var(--line));
  cursor:pointer;font-size:13px;color:var(--mavi);font-weight:600;
  user-select:none;transition:all .15s;white-space:nowrap}
#sym .sym-toggle:hover{background:color-mix(in srgb,var(--mavi) 15%,var(--card));
  border-color:color-mix(in srgb,var(--mavi) 50%,transparent)}
#sym .sym-toggle input{accent-color:var(--mavi);margin:0;width:14px;height:14px;cursor:pointer}
#sym .sym-toggle.aktif{color:#fff;border-color:var(--mavi);background:var(--mavi);
  box-shadow:0 4px 12px color-mix(in srgb,var(--mavi) 30%,transparent)}
#sym .sym-toggle.aktif input{accent-color:#fff;filter:brightness(2)}

/* --- Kategori chip bar --- */
#sym .sym-kat-chips{display:flex;gap:7px;overflow-x:auto;padding:2px 0 6px;margin-bottom:12px;
  scrollbar-width:thin}
#sym .sym-kat-chips::-webkit-scrollbar{height:4px}
#sym .sym-kat-chips::-webkit-scrollbar-thumb{background:var(--line);border-radius:2px}
#sym .kat-chip{flex-shrink:0;padding:7px 13px;border-radius:999px;
  border:1px solid color-mix(in srgb,var(--mor1) 25%,var(--line));
  background:color-mix(in srgb,var(--mor1) 7%,var(--card));
  color:var(--mor1);font-size:12.5px;font-weight:700;cursor:pointer;
  transition:all .15s;white-space:nowrap;display:inline-flex;align-items:center;gap:6px}
#sym .kat-chip:hover{background:color-mix(in srgb,var(--mor1) 15%,var(--card));
  border-color:color-mix(in srgb,var(--mor1) 45%,transparent)}
#sym .kat-chip.aktif{background:var(--mor1);color:#fff;border-color:var(--mor1);
  box-shadow:0 4px 12px color-mix(in srgb,var(--mor1) 30%,transparent)}
#sym .kat-chip .n{display:inline-flex;align-items:center;justify-content:center;
  min-width:20px;height:18px;padding:0 6px;border-radius:999px;
  background:color-mix(in srgb,var(--mor1) 18%,transparent);color:var(--mor1);
  font-weight:800;font-size:11px;font-variant-numeric:tabular-nums}
#sym .kat-chip.aktif .n{background:rgba(255,255,255,.28);color:#fff}

/* --- Son sayımlar kompakt strip (üstte) --- */
#sym .son-sayim-strip{display:flex;align-items:stretch;gap:8px;margin-bottom:14px;
  padding:10px;border-radius:14px;background:var(--card);border:1px solid var(--line)}
#sym .son-sayim-strip .ss-bas{display:flex;align-items:center;gap:8px;padding:4px 6px 4px 8px;
  min-width:0;flex-shrink:0}
#sym .son-sayim-strip .ss-ikon{width:30px;height:30px;border-radius:9px;flex-shrink:0;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;
  display:flex;align-items:center;justify-content:center}
#sym .son-sayim-strip .ss-ikon .material-icons-outlined{font-size:17px}
#sym .son-sayim-strip .ss-bas-metin{color:var(--ink);font-size:13.5px;font-weight:700;line-height:1.2}
#sym .son-sayim-strip .ss-bas-metin small{display:block;color:var(--sub);font-size:11px;font-weight:500;margin-top:1px}
#sym .son-sayim-strip .ss-liste{display:flex;gap:6px;overflow-x:auto;flex:1;min-width:0;scrollbar-width:thin;padding-bottom:2px}
#sym .son-sayim-strip .ss-liste::-webkit-scrollbar{height:4px}
#sym .son-sayim-strip .ss-liste::-webkit-scrollbar-thumb{background:var(--line);border-radius:2px}
#sym .son-sayim-strip .ss-mini{flex-shrink:0;padding:7px 11px;border-radius:10px;
  background:color-mix(in srgb,var(--sub) 8%,var(--card));
  border:1px solid var(--line);cursor:pointer;text-decoration:none;
  display:inline-flex;align-items:center;gap:7px;transition:all .15s;min-width:0}
#sym .son-sayim-strip .ss-mini:hover{border-color:var(--mor1);transform:translateY(-1px);
  box-shadow:0 4px 10px rgba(0,0,0,.08)}
#sym .son-sayim-strip .ss-mini-tar{color:var(--ink);font-size:12px;font-weight:700;white-space:nowrap;font-variant-numeric:tabular-nums}
#sym .son-sayim-strip .ss-mini-tut{font-size:12px;font-weight:800;white-space:nowrap;font-variant-numeric:tabular-nums}
#sym .son-sayim-strip .ss-mini-tut.minus{color:var(--kirmizi)}
#sym .son-sayim-strip .ss-mini-tut.plus{color:var(--amber)}
#sym .son-sayim-strip .ss-mini-tut.zero{color:var(--yesil)}
#sym .son-sayim-strip .ss-tum{flex-shrink:0;padding:7px 12px;border-radius:10px;text-decoration:none;
  display:inline-flex;align-items:center;gap:4px;font-size:12.5px;font-weight:700;
  background:color-mix(in srgb,var(--mor1) 10%,var(--card));color:var(--mor1);
  border:1px solid color-mix(in srgb,var(--mor1) 30%,var(--line));transition:all .15s;white-space:nowrap}
#sym .son-sayim-strip .ss-tum:hover{background:var(--mor1);color:#fff}
#sym .son-sayim-strip .ss-tum .material-icons-outlined{font-size:16px}
@media (max-width:640px){
  #sym .son-sayim-strip{flex-wrap:wrap}
  #sym .son-sayim-strip .ss-bas{width:100%}
  #sym .son-sayim-strip .ss-tum{margin-left:auto}
}

/* --- Malzeme satırı (yeni kart) --- */
#sym .sym-satir{display:flex;align-items:center;gap:12px;padding:12px;border-radius:14px;
  background:var(--card);border:1px solid var(--line);margin-bottom:8px;
  transition:border-color .15s,background .15s,box-shadow .15s}
#sym .sym-satir:hover{border-color:color-mix(in srgb,var(--mor1) 25%,var(--line));
  box-shadow:0 2px 8px rgba(0,0,0,.04)}
#sym .sym-satir.sayildi{border-color:color-mix(in srgb,var(--yesil) 35%,var(--line));
  background:color-mix(in srgb,var(--yesil) 4%,var(--card))}
#sym .sym-satir.uyari{border-color:color-mix(in srgb,var(--amber) 55%,transparent);
  background:color-mix(in srgb,var(--amber) 6%,var(--card))}

#sym .sym-ikon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;
  justify-content:center;flex-shrink:0;color:#fff;font-weight:800;font-size:15px;letter-spacing:-.3px}

#sym .sym-ad{flex:1;min-width:0}
#sym .sym-ad-row{display:flex;align-items:center;gap:8px;min-width:0}
#sym .sym-ad .ad{color:var(--ink);font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;
  text-overflow:ellipsis;min-width:0;flex:1}
#sym .sym-rozet{flex-shrink:0;padding:2px 7px;border-radius:999px;font-size:10px;font-weight:700;
  letter-spacing:.3px;text-transform:uppercase}
#sym .sym-rozet.r-bek{background:color-mix(in srgb,var(--mavi) 12%,transparent);color:var(--mavi)}
#sym .sym-rozet.r-ok{background:color-mix(in srgb,var(--yesil) 14%,transparent);color:var(--yesil)}
#sym .sym-rozet.r-uyari{background:color-mix(in srgb,var(--amber) 18%,transparent);color:var(--amber);
  display:inline-flex;align-items:center;gap:3px}
#sym .sym-rozet.r-uyari .material-icons-outlined{font-size:11px}
#sym .sym-ad .teorik{color:var(--sub);font-size:12px;margin-top:2px;display:flex;align-items:center;
  gap:6px;flex-wrap:wrap}
#sym .sym-ad .teorik .kat{color:var(--sub2);font-size:10.5px;padding:1px 6px;border-radius:4px;
  background:color-mix(in srgb,var(--sub2) 12%,transparent);font-weight:600}

/* Stepper giriş */
#sym .sym-stepper{display:flex;align-items:center;gap:4px;flex-shrink:0}
#sym .sym-stepper button{width:30px;height:36px;border:1px solid var(--line);background:var(--card);
  color:var(--sub);border-radius:8px;cursor:pointer;font-size:17px;font-weight:700;line-height:1;
  display:flex;align-items:center;justify-content:center;transition:all .12s;padding:0}
#sym .sym-stepper button:hover:not(:disabled){border-color:var(--mor1);color:var(--mor1);
  background:color-mix(in srgb,var(--mor1) 5%,var(--card))}
#sym .sym-stepper button:active:not(:disabled){transform:scale(.95)}
#sym .sym-stepper button:disabled{opacity:.4;cursor:not-allowed}
#sym .sym-stepper input{width:72px;height:36px;padding:0 4px;border:1px solid var(--line);
  border-radius:8px;background:var(--card);color:var(--ink);font-size:14px;text-align:center;
  outline:none;transition:border-color .15s,box-shadow .15s;font-weight:600;-moz-appearance:textfield}
#sym .sym-stepper input::-webkit-outer-spin-button,
#sym .sym-stepper input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
body.koyu #sym .sym-stepper input{background:#1b2235}
#sym .sym-stepper input:focus{border-color:var(--mor1);
  box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 15%,transparent)}
#sym .sym-stepper input:disabled{opacity:.55;cursor:not-allowed}

/* Fark pill */
#sym .sym-fark-wrap{width:90px;flex-shrink:0;text-align:right;display:flex;flex-direction:column;
  align-items:flex-end;gap:3px}
#sym .sym-fark-pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;
  font-size:12px;font-weight:700;background:color-mix(in srgb,var(--sub) 10%,transparent);
  color:var(--sub);min-width:44px;justify-content:center;font-variant-numeric:tabular-nums}
#sym .sym-fark-pill.minus{background:color-mix(in srgb,var(--kirmizi) 13%,transparent);color:var(--kirmizi)}
#sym .sym-fark-pill.plus{background:color-mix(in srgb,var(--amber) 16%,transparent);color:var(--amber)}
#sym .sym-fark-pill.zero{background:color-mix(in srgb,var(--yesil) 13%,transparent);color:var(--yesil)}
#sym .sym-fark-tl{font-size:10.5px;color:var(--sub2);font-weight:600;font-variant-numeric:tabular-nums}
#sym .sym-fark-tl.minus{color:var(--kirmizi)}
#sym .sym-fark-tl.plus{color:var(--amber)}

/* Boş durum (filtre sonrası) */
#sym .sym-bos{padding:40px 20px;text-align:center;color:var(--sub);background:var(--card);
  border:1px dashed var(--line);border-radius:12px;margin:10px 0}
#sym .sym-bos .material-icons-outlined{font-size:36px;color:var(--sub2);display:block;margin-bottom:8px}

/* Sonuç kartı */
#sym .sym-sonuc{padding:14px 16px;border-radius:12px;background:var(--card);border:1px solid var(--line);
  margin-bottom:12px}
#sym .sym-sonuc .baslik{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:4px}
#sym .sym-sonuc .alt{font-size:13px;color:var(--sub)}
#sym .sym-sonuc .fark{font-size:15px;font-weight:800;margin-top:6px}

#sym .sym-hata{padding:12px 14px;border-radius:10px;
  background:color-mix(in srgb,var(--kirmizi) 10%,transparent);
  border:1px solid color-mix(in srgb,var(--kirmizi) 35%,transparent);color:var(--kirmizi);
  font-size:13px;font-weight:600;margin-bottom:14px}

/* Alt yüzen pill Kaydet butonu (FAB tarzı) */
#sym .sym-alt{position:sticky;bottom:16px;z-index:40;display:flex;justify-content:center;
  padding:14px 0 6px;margin-top:16px;pointer-events:none}
#sym .sym-alt button{pointer-events:auto;display:inline-flex;align-items:stretch;padding:0;
  border:none;border-radius:999px;cursor:pointer;overflow:hidden;position:relative;
  background:linear-gradient(135deg,var(--mavi) 0%,#6366F1 55%,var(--mor1) 100%);
  color:#fff;min-width:280px;max-width:92vw;
  box-shadow:0 12px 32px -6px color-mix(in srgb,var(--mavi) 55%,transparent),
             0 4px 14px rgba(0,0,0,.14),
             inset 0 1px 0 rgba(255,255,255,.18);
  transition:transform .18s cubic-bezier(.34,1.56,.64,1),box-shadow .22s,filter .2s,opacity .2s}
#sym .sym-alt button:hover:not(:disabled){transform:translateY(-3px) scale(1.015);
  box-shadow:0 18px 44px -6px color-mix(in srgb,var(--mavi) 65%,transparent),
             0 8px 20px rgba(0,0,0,.18),
             inset 0 1px 0 rgba(255,255,255,.25)}
#sym .sym-alt button:active:not(:disabled){transform:translateY(-1px) scale(1)}
#sym .sym-alt button:disabled{opacity:.72;cursor:not-allowed;
  box-shadow:0 6px 18px -4px color-mix(in srgb,var(--mavi) 35%,transparent),
             0 2px 8px rgba(0,0,0,.08)}
#sym .sym-alt .btn-ic{display:inline-flex;align-items:center;gap:10px;padding:11px 18px 11px 10px;
  position:relative;z-index:2;flex:1}
#sym .sym-alt .btn-ikon-kutu{width:30px;height:30px;border-radius:50%;flex-shrink:0;
  background:rgba(255,255,255,.22);display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(4px)}
#sym .sym-alt .btn-ikon-kutu .material-icons-outlined{font-size:17px}
#sym .sym-alt .btn-metin{flex:1;text-align:left;font-size:14px;font-weight:700;letter-spacing:.2px;
  white-space:nowrap;text-shadow:0 1px 2px rgba(0,0,0,.1)}
#sym .sym-alt .btn-rozet{display:inline-flex;align-items:center;padding:4px 11px;border-radius:999px;
  background:rgba(255,255,255,.24);font-size:12px;font-weight:800;letter-spacing:.2px;
  font-variant-numeric:tabular-nums;flex-shrink:0;backdrop-filter:blur(4px)}
#sym .sym-alt .btn-rozet b{font-weight:800}
#sym .sym-alt .btn-progress{position:absolute;left:0;bottom:0;height:3px;width:0%;
  background:linear-gradient(90deg,rgba(255,255,255,.4),rgba(255,255,255,.95));
  transition:width .35s cubic-bezier(.4,0,.2,1);z-index:1;border-radius:0 2px 2px 0;
  box-shadow:0 0 8px rgba(255,255,255,.6)}
#sym .sym-alt .btn-shine{position:absolute;top:0;left:-100%;width:60%;height:100%;z-index:1;
  background:linear-gradient(100deg,transparent 20%,rgba(255,255,255,.25) 50%,transparent 80%);
  pointer-events:none}
#sym .sym-alt button:not(:disabled):hover .btn-shine{animation:symShine 1.1s ease-out}
@keyframes symShine{0%{left:-100%}100%{left:200%}}

/* Geçmiş bölümü */
#sym .sym-sec-baslik{display:flex;align-items:center;gap:8px;margin:18px 0 10px}
#sym .sym-sec-baslik .cubuk{width:4px;height:18px;border-radius:3px;background:var(--mor1);flex-shrink:0}
#sym .sym-sec-baslik span{color:var(--ink);font-size:15px;font-weight:700}
#sym .sym-gecmis-grid{display:grid;gap:10px;grid-template-columns:repeat(auto-fill,minmax(260px,1fr))}
#sym .sym-gecmis-kart{display:flex;align-items:center;padding:13px;border-radius:12px;
  background:var(--card);border:1px solid var(--line);cursor:pointer;
  transition:box-shadow .12s,border-color .12s;text-decoration:none}
#sym .sym-gecmis-kart:hover{box-shadow:0 4px 14px rgba(0,0,0,.08);
  border-color:color-mix(in srgb,var(--mor1) 25%,var(--line))}
#sym .sym-gecmis-kart .sol{flex:1;min-width:0}
#sym .sym-gecmis-kart .tarih{color:var(--ink);font-size:14px;font-weight:600}
#sym .sym-gecmis-kart .alt{color:var(--sub);font-size:12px;margin-top:2px}
#sym .sym-gecmis-kart .tutar{font-size:14px;font-weight:700;margin-right:4px;flex-shrink:0}
#sym .sym-gecmis-kart .material-icons-outlined{color:var(--sub);font-size:20px;flex-shrink:0}

/* Detay Modal */
#symModal{display:none;position:fixed;inset:0;z-index:900;align-items:flex-start;
  justify-content:center;background:rgba(0,0,0,.45);padding:16px;overflow-y:auto}
#symModal.acik{display:flex}
#symModal .sym-mod-wrap{background:var(--card,#fff);border-radius:18px;width:100%;max-width:600px;
  overflow:hidden;margin:auto}
body.koyu #symModal .sym-mod-wrap{background:#1b2235}
#symModal .sym-mod-header{display:flex;align-items:center;justify-content:space-between;
  padding:16px 16px 12px;border-bottom:1px solid var(--line,#e2e8f0)}
#symModal .sym-mod-title{color:var(--ink,#1e293b);font-size:16px;font-weight:700}
#symModal .sym-mod-kapat{width:32px;height:32px;border:none;background:transparent;cursor:pointer;
  display:flex;align-items:center;justify-content:center;border-radius:8px;color:var(--sub,#64748b)}
#symModal .sym-mod-kapat:hover{background:var(--line,#e2e8f0)}
#symModal .sym-mod-body{padding:14px 16px 20px}
#symModal .sym-det-ozet{border-radius:16px;padding:16px;margin-bottom:14px}
#symModal .sym-det-ozet .etiket{color:rgba(255,255,255,.75);font-size:13px;margin-bottom:2px}
#symModal .sym-det-ozet .deger{color:#fff;font-size:26px;font-weight:800}
#symModal .sym-ai{display:flex;gap:8px;align-items:flex-start;padding:12px;border-radius:12px;
  background:var(--card,#fff);border:1px solid color-mix(in srgb,#9D5DC8 40%,transparent);
  margin-bottom:8px}
body.koyu #symModal .sym-ai{background:#161d2e}
#symModal .sym-ai .emoji{font-size:14px;flex-shrink:0;margin-top:1px}
#symModal .sym-ai .metin{color:var(--ink,#1e293b);font-size:13px;line-height:1.35}
#symModal .sym-det-baslik{color:var(--ink,#1e293b);font-size:15px;font-weight:700;margin:10px 0 8px}
#symModal .sym-det-satir{display:flex;align-items:center;padding:12px;border-radius:12px;
  background:var(--card,#fff);border:1px solid var(--line,#e2e8f0);margin-bottom:8px}
body.koyu #symModal .sym-det-satir{background:#1b2235}
#symModal .sym-det-satir .sol{flex:4;min-width:0}
#symModal .sym-det-satir .malzeme{color:var(--ink,#1e293b);font-size:14px;font-weight:600}
#symModal .sym-det-satir .bilgi{color:var(--sub,#64748b);font-size:11.5px;margin-top:1px}
#symModal .sym-det-satir .sag{text-align:right;flex-shrink:0}
#symModal .sym-det-satir .fark-val{font-size:14px;font-weight:700}
#symModal .sym-det-satir .fark-tl{font-size:11px;margin-top:1px}
#symModal .sym-mod-loading{text-align:center;padding:40px;color:var(--sub,#64748b)}

/* Mobil */
@media (max-width:640px){
  #sym .sym-satir{flex-wrap:wrap;row-gap:10px}
  #sym .sym-ikon{order:0}
  #sym .sym-ad{flex:1 1 calc(100% - 50px);order:1;min-width:0}
  #sym .sym-stepper{order:2;flex:1 1 auto}
  #sym .sym-stepper input{flex:1;width:auto;min-width:60px}
  #sym .sym-fark-wrap{order:3;width:auto;flex-direction:row;gap:8px;align-items:center}
  #sym .sym-ozet-sayac{font-size:11.5px}
  #sym .sym-ozet-sayac b{font-size:14px}
}
</style>
@endpush

@section('content')
@php
  $malzemeler = $malzemeler ?? [];
  $sonSayimlar = $sonSayimlar ?? [];
  $duzenleyebilir = true;

  // Flutter _mik: tam sayıysa ondalıksız, değilse 2 hane
  $mik = function ($v) {
    $v = (float) $v;
    return $v == round($v) ? number_format($v, 0, ',', '.') : rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',');
  };
  $tl = fn($v) => number_format((float) $v, 0, ',', '.') . 'TL';

  // Kategori listesi + sayıları
  $katSayilari = [];
  foreach ($malzemeler as $m) {
    $k = $m['kategori'] ?? '—';
    $katSayilari[$k] = ($katSayilari[$k] ?? 0) + 1;
  }
  ksort($katSayilari);

  // Kategori → stabil renk (crc32 ile)
  $katRenkleri = ['#7C3AED','#4F46E5','#10B981','#F59E0B','#EC4899','#06B6D4','#8B5CF6','#F43F5E','#14B8A6','#6366F1'];
  $katRenk = function ($k) use ($katRenkleri) {
    if (!$k || $k === '—') return '#64748b';
    return $katRenkleri[abs(crc32((string) $k)) % count($katRenkleri)];
  };
  $ilkHarf = function ($s) {
    $s = trim((string) $s);
    if ($s === '') return '•';
    return mb_strtoupper(mb_substr($s, 0, 1));
  };
@endphp

<div id="sym">

  {{-- Kayıt sonrası özet --}}
  @if (session('sayim_sonuc'))
    @php
      $ss = session('sayim_sonuc');
      $sfm = (float) ($ss['toplam_fark_maliyet'] ?? 0);
      $srenk = $sfm < 0 ? 'var(--kirmizi)' : ($sfm > 0 ? 'var(--amber)' : 'var(--yesil)');
    @endphp
    <div class="sym-sonuc" style="border-color:color-mix(in srgb,{{ $srenk }} 45%,transparent)">
      <div class="baslik">Sayım kaydedildi</div>
      <div class="alt">{{ $ss['sayilan_kalem'] ?? 0 }} kalem sayıldı · {{ $ss['eksik'] ?? 0 }} eksik · {{ $ss['fazla'] ?? 0 }} fazla</div>
      <div class="fark" style="color:{{ $srenk }}">
        @if ($sfm < 0)
          Toplam açık: {{ $tl(abs($sfm)) }} (kayıp)
        @elseif ($sfm > 0)
          Toplam fazla: {{ $tl($sfm) }}
        @else
          Fark yok — tertemiz.
        @endif
      </div>
    </div>
  @endif

  @if (session('sayim_hata'))
    <div class="sym-hata">{{ session('sayim_hata') }}</div>
  @endif

  @if (!$malzemeler)
    <div class="sym-info">
      <span class="material-icons-outlined">info</span>
      <p>Fiili sayımı girin. Sistem, "olması gereken" ile karşılaştırıp farkı (kayıp/fazla) çıkarır. Sadece saydıklarınızı girin.</p>
    </div>
    <div class="sym-bos">
      <span class="material-icons-outlined">inventory_2</span>
      Stok takipli malzeme yok.
    </div>
  @else

    {{-- ===== CANLI ÖZET PANELİ ===== --}}
    <div class="sym-ozet" id="symOzet">
      <div class="sym-ozet-top">
        <div class="sym-ozet-sayac"><b id="symSayac">0</b>/<span id="symToplam">{{ count($malzemeler) }}</span> kalem sayıldı</div>
        <div class="sym-ozet-bar"><div class="sym-ozet-fill" id="symFill"></div></div>
      </div>
      <div class="sym-ozet-chips">
        <div class="ozet-chip c-bek" title="Henüz sayılmayan"><span class="dot"></span><b id="symBek">{{ count($malzemeler) }}</b> bekliyor</div>
        <div class="ozet-chip c-eksik" title="Sistemden az"><span class="dot"></span><b id="symEksik">0</b> eksik</div>
        <div class="ozet-chip c-fazla" title="Sistemden fazla"><span class="dot"></span><b id="symFazla">0</b> fazla</div>
        <div class="ozet-chip c-tam" title="Sistemle eşit"><span class="dot"></span><b id="symTam">0</b> tam</div>
        <div class="ozet-chip ozet-chip-tutar" id="symTutarChip">
          <span class="material-icons-outlined" style="font-size:14px">payments</span>
          Fark: <span id="symTutar">0 ₺</span>
        </div>
      </div>
    </div>

    {{-- Son Sayımlar kompakt strip (üstte, "Tümünü gör" ile ayrı sayfaya) --}}
    @if ($sonSayimlar)
      <div class="son-sayim-strip">
        <div class="ss-bas">
          <div class="ss-ikon"><span class="material-icons-outlined">history</span></div>
          <div class="ss-bas-metin">Son Sayımlar<small>Hızlı erişim</small></div>
        </div>
        <div class="ss-liste">
          @foreach (array_slice($sonSayimlar, 0, 5) as $s)
            @php
              $gfm = (float) $s['fark_maliyet'];
              $gcls = $gfm < 0 ? 'minus' : ($gfm > 0 ? 'plus' : 'zero');
              $gmetin = $gfm < 0 ? '-' . $tl(abs($gfm)) : ($gfm > 0 ? '+' . $tl($gfm) : '0');
            @endphp
            <a class="ss-mini" href="/sayim/gecmis" title="{{ $s['tarih'] }} — {{ $s['kalem'] }} kalem">
              <span class="ss-mini-tar">{{ $s['tarih'] }}</span>
              <span class="ss-mini-tut {{ $gcls }}">{{ $gmetin }}</span>
            </a>
          @endforeach
        </div>
        <a class="ss-tum" href="/sayim/gecmis">
          Tümünü Gör
          <span class="material-icons-outlined">arrow_forward</span>
        </a>
      </div>
    @endif

    <div class="sym-info">
      <span class="material-icons-outlined">info</span>
      <p>Fiili sayımı girin. Sistem, "olması gereken" ile karşılaştırıp farkı (kayıp/fazla) çıkarır. Sadece saydıklarınızı girin · <b>Enter</b> ile sonraki satıra geçin.</p>
    </div>

    {{-- ===== Araç çubuğu ===== --}}
    <div class="sym-araclar">
      <div class="sym-ara-wrap">
        <span class="material-icons-outlined">search</span>
        <input id="symAra" type="text" placeholder="Malzeme ara" oninput="symFiltre()" autocomplete="off">
      </div>
      <label class="sym-toggle" id="symBekToggle">
        <input type="checkbox" id="symSadeceBek" onchange="symBekToggle()">
        <span>Sadece bekleyenler</span>
      </label>
    </div>

    {{-- ===== Kategori chip bar ===== --}}
    @if (count($katSayilari) > 1)
      <div class="sym-kat-chips" id="symKatChips">
        <button type="button" class="kat-chip aktif" data-kat="__tum__" onclick="symKatSec(this)">
          Tümü <span class="n">{{ count($malzemeler) }}</span>
        </button>
        @foreach ($katSayilari as $kat => $sayi)
          <button type="button" class="kat-chip" data-kat="{{ $kat }}" onclick="symKatSec(this)">
            {{ $kat }} <span class="n">{{ $sayi }}</span>
          </button>
        @endforeach
      </div>
    @endif

    {{-- ===== Malzeme listesi ===== --}}
    <form method="POST" action="/sayim/kaydet" id="symForm">
      @csrf

      <div id="symListe">
        @foreach ($malzemeler as $m)
          @php
            $teorik = (float) ($m['teorik'] ?? 0);
            $kat = $m['kategori'] ?? '—';
            $renk = $katRenk($kat);
            $mal = (float) ($m['guncel_maliyet'] ?? 0);
          @endphp
          <div class="sym-satir"
               data-ad="{{ mb_strtolower($m['ad']) }}"
               data-kat="{{ $kat }}"
               data-maliyet="{{ $mal }}"
               data-durum="bekliyor">

            {{-- Kategori renkli ikon --}}
            <div class="sym-ikon" style="background:{{ $renk }}">{{ $ilkHarf($m['ad']) }}</div>

            {{-- Ad + sistemde + kategori --}}
            <div class="sym-ad">
              <div class="sym-ad-row">
                <div class="ad" title="{{ $m['ad'] }}">{{ $m['ad'] }}</div>
                <span class="sym-rozet r-bek">Bekliyor</span>
              </div>
              <div class="teorik">
                Sistemde: {{ $mik($teorik) }} {{ $m['birim'] ?? '' }}
                @if ($kat && $kat !== '—')
                  <span class="kat">{{ $kat }}</span>
                @endif
              </div>
            </div>

            {{-- Stepper giriş --}}
            <div class="sym-stepper">
              <button type="button" onclick="symAdim(this,-1)" aria-label="Azalt" {{ $duzenleyebilir ? '' : 'disabled' }}>−</button>
              <input type="number" step="any" min="0"
                     name="sayilan[{{ $m['malzeme_id'] }}]"
                     placeholder="0"
                     data-teorik="{{ $teorik }}"
                     oninput="symFark(this)"
                     onkeydown="symKlavye(event,this)"
                     {{ $duzenleyebilir ? '' : 'disabled' }}>
              <button type="button" onclick="symAdim(this,1)" aria-label="Arttır" {{ $duzenleyebilir ? '' : 'disabled' }}>+</button>
            </div>

            {{-- Fark pill + ₺ --}}
            <div class="sym-fark-wrap">
              <span class="sym-fark-pill">—</span>
              <span class="sym-fark-tl"></span>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Filtre sonrası boş --}}
      <div id="symFiltreBos" class="sym-bos" style="display:none">
        <span class="material-icons-outlined">search_off</span>
        Bu filtreyle eşleşen malzeme yok.
      </div>

      {{-- Yüzen pill Kaydet butonu --}}
      <div class="sym-alt">
        <button type="submit" id="symKaydetBtn" disabled>
          <span class="btn-shine"></span>
          <span class="btn-progress" id="symBtnProgress"></span>
          <span class="btn-ic">
            <span class="btn-ikon-kutu"><span class="material-icons-outlined">save</span></span>
            <span class="btn-metin">Sayımı Kaydet</span>
            <span class="btn-rozet"><span id="symGirilen">0</span>&nbsp;kalem</span>
          </span>
        </button>
      </div>

    </form>

  @endif

</div>{{-- #sym --}}

{{-- Detay Modal --}}
<div id="symModal" onclick="if(event.target===this)symModalKapat()">
  <div class="sym-mod-wrap">
    <div class="sym-mod-header">
      <div class="sym-mod-title" id="symModTitle">Sayım Detayı</div>
      <button class="sym-mod-kapat" onclick="symModalKapat()" title="Kapat">
        <span class="material-icons-outlined">close</span>
      </button>
    </div>
    <div class="sym-mod-body" id="symModBody">
      <div class="sym-mod-loading">Yükleniyor…</div>
    </div>
  </div>
</div>

<script>
// ── Yardımcılar ───────────────────────────────────────────────────────────────
function _mik(v) {
  v = parseFloat(v) || 0;
  return Math.abs(v) === Math.round(Math.abs(v))
    ? String(Math.round(Math.abs(v)))
    : Math.abs(v).toFixed(2);
}
function _tlf(v) { return Math.round(Math.abs(parseFloat(v)||0)).toLocaleString('tr-TR') + '₺'; }
function _farkRenk(f) {
  return f < -0.0001 ? 'var(--kirmizi)' : (f > 0.0001 ? 'var(--amber)' : 'var(--yesil)');
}

// ── Sayım satır: fark + rozet + sapma uyarısı ─────────────────────────────────
function symFark(inp) {
  var satir = inp.closest('.sym-satir');
  if (!satir) return;
  var pill  = satir.querySelector('.sym-fark-pill');
  var tl    = satir.querySelector('.sym-fark-tl');
  var rozet = satir.querySelector('.sym-rozet');
  var t     = parseFloat(inp.getAttribute('data-teorik')) || 0;
  var mal   = parseFloat(satir.getAttribute('data-maliyet')) || 0;
  var v     = inp.value.trim();

  // sınıfları sıfırla
  pill.classList.remove('minus','plus','zero');
  tl.classList.remove('minus','plus');
  satir.classList.remove('sayildi','uyari');
  rozet.classList.remove('r-bek','r-ok','r-uyari');
  rozet.innerHTML = '';

  if (v === '') {
    pill.textContent = '—';
    tl.textContent = '';
    rozet.classList.add('r-bek');
    rozet.textContent = 'Bekliyor';
    satir.setAttribute('data-durum', 'bekliyor');
    satir.setAttribute('data-fark', '');
  } else {
    var sayilan = parseFloat(v.replace(',', '.')) || 0;
    var f = sayilan - t;
    var r = Math.round(f * 10000) / 10000;
    var abs = Math.abs(r);
    var govde = (abs === Math.round(abs)) ? String(Math.round(abs)) : abs.toFixed(2);
    satir.classList.add('sayildi');
    satir.setAttribute('data-fark', String(r));

    if (r > 0.0001) {
      pill.textContent = '+' + govde;
      pill.classList.add('plus');
      satir.setAttribute('data-durum', 'fazla');
    } else if (r < -0.0001) {
      pill.textContent = '-' + govde;
      pill.classList.add('minus');
      satir.setAttribute('data-durum', 'eksik');
    } else {
      pill.textContent = '0';
      pill.classList.add('zero');
      satir.setAttribute('data-durum', 'tam');
    }

    // ₺ fark
    var fm = r * mal;
    if (Math.abs(fm) >= 1) {
      tl.textContent = (fm < 0 ? '-' : '+') + _tlf(fm);
      tl.classList.add(fm < 0 ? 'minus' : 'plus');
    }

    // Sapma uyarısı: teorik≥1 ve |fark|/teorik > %20 ise amber kenarlık + rozet
    var sapma = t > 0 ? Math.abs(r) / t : 0;
    if (t >= 1 && sapma > 0.2) {
      satir.classList.remove('sayildi');
      satir.classList.add('uyari');
      rozet.classList.add('r-uyari');
      rozet.innerHTML = '<span class="material-icons-outlined">warning</span>Yüksek sapma';
    } else {
      rozet.classList.add('r-ok');
      rozet.textContent = 'Sayıldı';
    }
  }
  symOzetGuncelle();
}

// ── Stepper (+/- butonu) ──────────────────────────────────────────────────────
function symAdim(btn, delta) {
  var inp = btn.closest('.sym-stepper').querySelector('input');
  var cur = parseFloat((inp.value || '0').replace(',', '.')) || 0;
  var yeni = cur + delta;
  if (yeni < 0) yeni = 0;
  // tam sayı olarak göster, 1'den küçük adımlar bozulmasın diye integer ise integer
  inp.value = (yeni === Math.round(yeni)) ? String(yeni) : yeni.toFixed(2);
  symFark(inp);
}

// ── Enter → sonraki görünür bekleyen satır ────────────────────────────────────
function symKlavye(e, inp) {
  if (e.key !== 'Enter') return;
  e.preventDefault();
  var tum = Array.from(document.querySelectorAll('#symListe .sym-satir'));
  var mevcut = inp.closest('.sym-satir');
  var basla = tum.indexOf(mevcut);
  // önce bir sonraki görünür + bekleyen input
  for (var i = basla + 1; i < tum.length; i++) {
    if (tum[i].style.display === 'none') continue;
    if (tum[i].getAttribute('data-durum') === 'bekliyor') {
      var ni = tum[i].querySelector('input[type=number]');
      if (ni) { ni.focus(); ni.select(); return; }
    }
  }
  // yoksa listenin başından ara
  for (var j = 0; j < basla; j++) {
    if (tum[j].style.display === 'none') continue;
    if (tum[j].getAttribute('data-durum') === 'bekliyor') {
      var ni2 = tum[j].querySelector('input[type=number]');
      if (ni2) { ni2.focus(); ni2.select(); return; }
    }
  }
  inp.blur();
}

// ── Üst özet paneli canlı güncelle ────────────────────────────────────────────
function symOzetGuncelle() {
  var toplam = document.querySelectorAll('#symListe .sym-satir').length;
  var sayac = 0, bek = 0, eksik = 0, fazla = 0, tam = 0, tutar = 0;
  document.querySelectorAll('#symListe .sym-satir').forEach(function (s) {
    var d = s.getAttribute('data-durum');
    if (d === 'bekliyor') { bek++; return; }
    sayac++;
    if (d === 'eksik') eksik++;
    else if (d === 'fazla') fazla++;
    else if (d === 'tam') tam++;
    var f = parseFloat(s.getAttribute('data-fark')) || 0;
    var m = parseFloat(s.getAttribute('data-maliyet')) || 0;
    tutar += f * m;
  });

  var set = function (id, val) { var el = document.getElementById(id); if (el) el.textContent = val; };
  set('symSayac', sayac);
  set('symToplam', toplam);
  set('symBek', bek);
  set('symEksik', eksik);
  set('symFazla', fazla);
  set('symTam', tam);
  set('symGirilen', sayac);

  var yuzde = toplam > 0 ? Math.round((sayac / toplam) * 100) : 0;
  var fill = document.getElementById('symFill');
  if (fill) fill.style.width = yuzde + '%';

  // Kaydet butonu: 0 kalemde disable, ilerledikçe iç progress çubuğu dolsun
  var btn = document.getElementById('symKaydetBtn');
  if (btn) btn.disabled = sayac === 0;
  var btnProg = document.getElementById('symBtnProgress');
  if (btnProg) btnProg.style.width = yuzde + '%';

  var tutarEl = document.getElementById('symTutar');
  var tutarChip = document.getElementById('symTutarChip');
  if (tutarEl && tutarChip) {
    tutarChip.classList.remove('minus','plus');
    if (Math.abs(tutar) < 0.5) {
      tutarEl.textContent = '0 ₺';
    } else {
      tutarEl.textContent = (tutar < 0 ? '-' : '+') + _tlf(tutar);
      tutarChip.classList.add(tutar < 0 ? 'minus' : 'plus');
    }
  }
}

// ── Filtre: arama + kategori + sadece bekleyenler ─────────────────────────────
var _symKatAktif = '__tum__';
var _symSadeceBek = false;

function symFiltre() {
  var q = (document.getElementById('symAra').value || '').toLowerCase().trim();
  var gorunur = 0;
  document.querySelectorAll('#symListe .sym-satir').forEach(function (satir) {
    var adOk = !q || (satir.getAttribute('data-ad') || '').indexOf(q) !== -1;
    var katOk = _symKatAktif === '__tum__' || satir.getAttribute('data-kat') === _symKatAktif;
    var bekOk = !_symSadeceBek || satir.getAttribute('data-durum') === 'bekliyor';
    var g = adOk && katOk && bekOk;
    satir.style.display = g ? '' : 'none';
    if (g) gorunur++;
  });
  var bos = document.getElementById('symFiltreBos');
  if (bos) bos.style.display = gorunur === 0 ? 'block' : 'none';
}

function symKatSec(btn) {
  document.querySelectorAll('#symKatChips .kat-chip').forEach(function (c) { c.classList.remove('aktif'); });
  btn.classList.add('aktif');
  _symKatAktif = btn.getAttribute('data-kat') || '__tum__';
  symFiltre();
}

function symBekToggle() {
  var cb = document.getElementById('symSadeceBek');
  _symSadeceBek = !!(cb && cb.checked);
  var lbl = document.getElementById('symBekToggle');
  if (lbl) lbl.classList.toggle('aktif', _symSadeceBek);
  symFiltre();
}

// ── Detay modal ───────────────────────────────────────────────────────────────
function symDetayAc(id) {
  var modal = document.getElementById('symModal');
  var body  = document.getElementById('symModBody');
  var title = document.getElementById('symModTitle');
  title.textContent = 'Sayım Detayı';
  body.innerHTML    = '<div class="sym-mod-loading">Yükleniyor…</div>';
  modal.classList.add('acik');
  document.body.style.overflow = 'hidden';

  api('/api/patron/sayim-detay?id=' + id).then(function(res) {
    if (!res || res.ok !== 1) {
      body.innerHTML = '<div class="sym-mod-loading">Sayım bulunamadı.</div>';
      return;
    }
    title.textContent = 'Sayım — ' + (res.tarih || '');
    var fm   = parseFloat(res.toplam_fark_maliyet) || 0;
    var ozGrad = fm < 0
      ? 'linear-gradient(135deg,#F43F5E,#F59E0B)'
      : (fm > 0 ? 'linear-gradient(135deg,#F59E0B,#4F46E5)' : 'linear-gradient(135deg,#10B981,#4F46E5)');
    var ozEtiket = fm < 0 ? 'Toplam Açık (Kayıp)' : (fm > 0 ? 'Toplam Fazla' : 'Fark Yok');
    var ozDeger  = fm === 0 ? 'Tertemiz' : _tlf(Math.abs(fm));

    var html = '';
    html += '<div class="sym-det-ozet" style="background:' + ozGrad + '">';
    html += '<div class="etiket">' + ozEtiket + '</div>';
    html += '<div class="deger">' + ozDeger + '</div>';
    html += '</div>';

    var ai = res.ai || [];
    ai.forEach(function(a) {
      html += '<div class="sym-ai"><span class="emoji">✨</span><div class="metin">' + _esc(a.mesaj || '') + '</div></div>';
    });

    html += '<div class="sym-det-baslik">Kalem Bazında Fark</div>';
    var kalemler = res.kalemler || [];
    kalemler.forEach(function(k) {
      var fark  = parseFloat(k.fark) || 0;
      var fmk   = parseFloat(k.fark_maliyet) || 0;
      var renk  = _farkRenk(fark);
      var farkStr = fark > 0.0001 ? '+' + _mik(fark) : (fark < -0.0001 ? '-' + _mik(fark) : _mik(fark));
      html += '<div class="sym-det-satir">';
      html += '<div class="sol"><div class="malzeme">' + _esc(k.malzeme || '') + '</div>';
      html += '<div class="bilgi">Sistem ' + _mik(k.teorik) + ' · Sayılan ' + _mik(k.sayilan) + ' ' + _esc(k.birim || '') + '</div></div>';
      html += '<div class="sag"><div class="fark-val" style="color:' + renk + '">' + farkStr + '</div>';
      if (Math.abs(fmk) >= 1) {
        html += '<div class="fark-tl" style="color:' + renk + '">' + (fmk < 0 ? '-' : '+') + _tlf(fmk) + '</div>';
      }
      html += '</div></div>';
    });

    body.innerHTML = html;
  }).catch(function() {
    body.innerHTML = '<div class="sym-mod-loading">Bağlantı hatası.</div>';
  });
}

function symModalKapat() {
  document.getElementById('symModal').classList.remove('acik');
  document.body.style.overflow = '';
}

function _esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') symModalKapat();
});

// İlk yükleme: özet paneli sıfırla
document.addEventListener('DOMContentLoaded', symOzetGuncelle);
</script>
@endsection

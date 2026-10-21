@extends('layout.app')
@section('title', 'Çarkıfelek')
@section('rk_ikon', 'casino')
@section('baslik', 'Çarkıfelek')
@section('rk_alt', 'Sadakat çarkı — dilimleri ve ödülleri yönetin')
@section('content')

<style>
.ck-page, .ck-page ~ .modal-ov, .ck-page ~ .toast, .ck-page ~ .ckh-ov {
    --purple:   #6c5ce7;
    --purple-l: #a29bfe;
    --pink:     #fd79a8;
    --gold:     #fdcb6e;
    --green:    #00b894;
    --teal:     #00cec9;
    --red:      #d63031;
    --orange:   #e17055;
    --dark:     #1a1a2e;
    --mid:      #636e72;
    --bg:       #f0f2f8;
    --white:    #ffffff;
    --card-r:   20px;
    --sh:       0 4px 24px rgba(0,0,0,.08);
    --sh-lg:    0 16px 56px rgba(0,0,0,.14);
}
.ck-page * { box-sizing: border-box; margin: 0; padding: 0; }
.ck-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    background: var(--bg);
    padding: 20px 4px;
}

/* ── Tab bar ─────────────────────────────────── */
.ck-tabs {
    display: flex; gap: 6px; background: var(--white);
    border-radius: 16px; padding: 8px; box-shadow: var(--sh);
    margin-bottom: 22px; flex-wrap: wrap;
}
.ck-tab {
    flex: 1; min-width: 150px; text-align: center;
    padding: 13px 10px; border-radius: 12px;
    font-size: 15px; font-weight: 700; color: var(--mid);
    text-decoration: none; cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.ck-tab:hover { background: rgba(108,92,231,.07); color: var(--purple); }
.ck-tab.active { background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: #fff; box-shadow: 0 6px 18px rgba(108,92,231,.3); }
.ck-tab.soon { opacity: .55; }

/* ── Header ─────────────────────────────────── */
.ck-header {
    display: flex; align-items: center; justify-content: space-between;
    background: var(--white); border-radius: var(--card-r);
    padding: 20px 28px; box-shadow: var(--sh); margin-bottom: 24px;
    flex-wrap: wrap; gap: 12px;
}
.ck-header-left { display: flex; align-items: center; gap: 16px; }
.ck-header-icon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, var(--purple), var(--purple-l));
    border-radius: 14px; display: flex; align-items: center; justify-content: center;
    font-size: 26px; box-shadow: 0 6px 16px rgba(108,92,231,.35); flex-shrink: 0;
}
.ck-header h1 { font-size: 20px; font-weight: 800; color: var(--dark); margin-bottom: 3px; }
.ck-header p  { font-size: 13px; color: var(--mid); }
.status-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 50px; font-size: 14px; font-weight: 600; transition: all .3s;
}
.status-pill.on  { background: rgba(0,184,148,.12); color: var(--green); }
.status-pill.off { background: rgba(214,48,49,.12);  color: var(--red); }
.status-dot { width: 9px; height: 9px; border-radius: 50%; background: currentColor; animation: blink 2s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.35} }

/* ── Grid ────────────────────────────────────── */
.ck-grid { display: grid; grid-template-columns: 460px 1fr; gap: 24px; align-items: start; }
@media(max-width:1100px){ .ck-grid{ grid-template-columns:1fr; } }

/* ── Wheel Panel ─────────────────────────────── */
.wheel-panel {
    background: linear-gradient(145deg, #160630 0%, #2b1a68 55%, #0e1b33 100%);
    border-radius: var(--card-r); padding: 28px 24px 24px; text-align: center;
    box-shadow: 0 24px 64px rgba(43,26,104,.45); position: relative; overflow: hidden;
}
.wheel-panel::before {
    content: ''; position: absolute; top: -60%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle at 50% 28%, rgba(108,92,231,.18) 0%, transparent 60%); pointer-events: none;
}
.wp-label { font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.45); margin-bottom: 20px; }
.pointer-wrap { display: flex; justify-content: center; margin-bottom: -16px; position: relative; z-index: 10; }
.pointer { display: block; transform-origin: 50% 20%; filter: drop-shadow(0 4px 10px rgba(0,0,0,.55)); }
@keyframes pinBounce { 0%{transform:rotate(0)} 25%{transform:rotate(24deg)} 55%{transform:rotate(-7deg)} 75%{transform:rotate(10deg)} 90%{transform:rotate(-3deg)} 100%{transform:rotate(0)} }
.pointer.tick { animation: pinBounce .28s cubic-bezier(.36,.07,.19,.97); }
.wheel-glow {
    display: inline-block; padding: 7px;
    background: conic-gradient(from 0deg, var(--purple), var(--purple-l), var(--pink), var(--gold), var(--green), var(--purple));
    border-radius: 50%; box-shadow: 0 0 32px rgba(108,92,231,.5), 0 0 72px rgba(108,92,231,.25); animation: spinRing 10s linear infinite;
}
@keyframes spinRing { to { filter: hue-rotate(360deg); } }
.wheel-wrap { position: relative; width: 360px; height: 360px; border-radius: 50%; overflow: hidden; background: #160630; display: inline-block; vertical-align: top; }
@media(max-width:520px){ .wheel-wrap { width: 280px; height: 280px; } .ck-grid { gap: 16px; } }
#wheel { width: 100%; height: 100%; transform-origin: 50% 50%; transition: transform 5.5s cubic-bezier(0.17, 0.67, 0.12, 0.99); }
.spin-btn {
    margin-top: 22px; padding: 14px 44px; background: linear-gradient(135deg, var(--gold), var(--orange));
    color: white; border: none; border-radius: 50px; font-size: 16px; font-weight: 700; cursor: pointer;
    box-shadow: 0 8px 28px rgba(225,112,85,.45); transition: all .25s; letter-spacing: .5px;
}
.spin-btn:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(225,112,85,.55); }
.spin-btn:active:not(:disabled) { transform: translateY(0); }
.spin-btn:disabled { opacity: .6; cursor: not-allowed; }
.wp-row { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: rgba(255,255,255,.06); border-radius: 12px; margin-top: 14px; }
.wp-row-label { color: rgba(255,255,255,.7); font-size: 14px; font-weight: 500; }
.toggle { position: relative; width: 52px; height: 28px; cursor: pointer; }
.toggle input { opacity: 0; width: 0; height: 0; }
.tslider { position: absolute; inset: 0; background: rgba(255,255,255,.18); border-radius: 28px; transition: .3s; }
.tslider::before { content: ''; position: absolute; width: 22px; height: 22px; left: 3px; top: 3px; background: white; border-radius: 50%; transition: .3s; box-shadow: 0 2px 6px rgba(0,0,0,.2); }
.toggle input:checked + .tslider { background: var(--green); }
.toggle input:checked + .tslider::before { transform: translateX(24px); }

/* ── Management Panel ────────────────────────── */
.mgmt-panel { background: var(--white); border-radius: var(--card-r); box-shadow: var(--sh); overflow: hidden; display: flex; flex-direction: column; }
.mgmt-head { display: flex; align-items: center; justify-content: space-between; padding: 22px 28px 18px; border-bottom: 1px solid #f0f2f8; }
.mgmt-head h2 { font-size: 17px; font-weight: 800; color: var(--dark); }
.count-ctrl { display: flex; align-items: center; background: #f0f2f8; border-radius: 10px; overflow: hidden; }
.cnt-btn { width: 36px; height: 36px; background: none; border: none; font-size: 20px; font-weight: 700; color: var(--purple); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background .2s; }
.cnt-btn:hover { background: rgba(108,92,231,.12); }
.cnt-val { min-width: 38px; text-align: center; font-size: 15px; font-weight: 700; color: var(--dark); }
.mgmt-hint { padding: 10px 28px 6px; font-size: 12px; color: var(--mid); }
.slices-body { padding: 10px 28px 4px; max-height: 440px; overflow-y: auto; flex: 1; }
.slices-body::-webkit-scrollbar { width: 4px; }
.slices-body::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.slice-item { display: flex; flex-direction: column; align-items: stretch; padding: 9px 12px; background: #f8f9ff; border: 1.5px solid #ececff; border-radius: 12px; margin-bottom: 7px; transition: all .2s; }
.slice-item:hover { border-color: var(--purple-l); box-shadow: 0 2px 14px rgba(108,92,231,.1); }
.slice-top { display: flex; align-items: center; gap: 10px; }
.slice-bot { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
.tip-pills { display: flex; gap: 5px; flex-wrap: wrap; flex: 1; }
.tip-pill { padding: 4px 10px; border-radius: 20px; border: 1.5px solid #e0e0e0; background: white; color: var(--mid); font-size: 11px; font-weight: 600; cursor: pointer; transition: all .15s; white-space: nowrap; }
.tip-pill:hover { border-color: var(--purple-l); color: var(--purple); }
.tip-pill.active { background: linear-gradient(135deg, var(--purple), var(--purple-l)); border-color: transparent; color: white; }
.deger-wrap { display: none; align-items: center; gap: 4px; }
.deger-wrap.show { display: flex; }
.s-deger { width: 90px; padding: 5px 8px; background: white; border: 1.5px solid #e9ecef; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--dark); transition: border-color .2s; }
.s-deger:focus { outline: none; border-color: var(--purple); }
.deger-unit { font-size: 12px; font-weight: 700; color: var(--mid); }
.unit-toggle { display: inline-flex; border: 1.5px solid #e0e0e0; border-radius: 8px; overflow: hidden; }
.unit-toggle button { padding: 5px 10px; border: none; background: white; color: var(--mid); font-size: 13px; font-weight: 800; cursor: pointer; transition: all .15s; line-height: 1; }
.unit-toggle button:hover { color: var(--purple); }
.unit-toggle button.active { background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: white; }
.unit-toggle button + button { border-left: 1.5px solid #e0e0e0; }
.s-num { width: 24px; height: 24px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: white; border-radius: 7px; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.s-color { width: 32px; height: 32px; border-radius: 8px; border: 2.5px solid white; box-shadow: 0 2px 8px rgba(0,0,0,.15); cursor: pointer; flex-shrink: 0; position: relative; overflow: hidden; }
.s-color input[type="color"] { position: absolute; inset: -6px; width: calc(100% + 12px); height: calc(100% + 12px); opacity: 0; cursor: pointer; border: none; padding: 0; }
.s-name { flex: 1; min-width: 0; padding: 8px 12px; background: white; border: 1.5px solid #e9ecef; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--dark); transition: border-color .2s; }
.s-name:focus { outline: none; border-color: var(--purple); }
.pick-btn { flex-shrink: 0; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1.5px solid #e0e0e0; background: white; color: var(--mid); transition: all .2s; white-space: nowrap; }
.pick-btn:hover { border-color: var(--green); color: var(--green); }
.pick-btn.winner { background: linear-gradient(135deg, var(--green), var(--teal)); border-color: transparent; color: white; box-shadow: 0 3px 10px rgba(0,184,148,.35); }
.slice-item.winner-row { border-color: var(--green); background: rgba(0,184,148,.06); box-shadow: 0 2px 14px rgba(0,184,148,.12); }
.mgmt-coupon { padding: 18px 28px; border-top: 1px solid #f0f2f8; background: rgba(108,92,231,.035); }
.mgmt-coupon-head { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 14px; }
.mgmt-coupon-icon { font-size: 20px; line-height: 1.2; }
.mgmt-coupon-head h3 { font-size: 15px; font-weight: 800; color: var(--dark); margin-bottom: 2px; }
.mgmt-coupon-head p  { font-size: 12.5px; color: var(--mid); line-height: 1.45; }
.mgmt-coupon-grid { display: flex; gap: 16px; flex-wrap: wrap; }
.mgmt-coupon-field { flex: 1; min-width: 200px; }
.mgmt-coupon-field label { display: block; font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 6px; }
.mgmt-coupon-field input { width: 100%; padding: 10px 12px; background: white; border: 1.5px solid #e9ecef; border-radius: 8px; font-size: 14px; color: var(--dark); transition: border-color .2s; }
.mgmt-coupon-field input:focus { outline: none; border-color: var(--purple); }
.mgmt-coupon-field small { display: block; margin-top: 5px; font-size: 11.5px; color: var(--mid); }
.mgmt-foot { padding: 16px 28px 24px; border-top: 1px solid #f0f2f8; }
.winner-info { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: rgba(0,184,148,.08); border: 1.5px solid rgba(0,184,148,.25); border-radius: 12px; margin-bottom: 14px; }
.winner-icon { font-size: 20px; }
.winner-label { font-size: 12px; color: var(--mid); font-weight: 500; }
.winner-name  { font-size: 15px; font-weight: 800; color: var(--green); }
.save-btn { width: 100%; padding: 14px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: white; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 22px rgba(108,92,231,.35); transition: all .25s; letter-spacing: .3px; }
.save-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(108,92,231,.45); }
.save-btn:active:not(:disabled) { transform: translateY(0); }
.save-btn:disabled { opacity: .6; cursor: not-allowed; }
.rules-card { background: var(--white); border-radius: var(--card-r); box-shadow: var(--sh); padding: 22px 28px 24px; margin-top: 24px; }
.rules-head { display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
.rules-head-icon { width: 42px; height: 42px; flex-shrink: 0; background: linear-gradient(135deg, var(--orange), var(--gold)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 6px 16px rgba(225,112,85,.3); }
.rules-head h2 { font-size: 16px; font-weight: 800; color: var(--dark); }
.rules-head p  { font-size: 12px; color: var(--mid); margin-top: 2px; }
.rules-textarea { width: 100%; min-height: 120px; margin-top: 14px; padding: 14px 16px; background: #f8f9ff; border: 1.5px solid #ececff; border-radius: 12px; font-size: 14px; line-height: 1.6; color: var(--dark); font-family: inherit; resize: vertical; transition: border-color .2s; }
.rules-textarea:focus { outline: none; border-color: var(--purple); background: #fff; }
.rules-foot { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; flex-wrap: wrap; gap: 10px; }
.rules-hint { font-size: 12px; color: var(--mid); }
.rules-hint b { color: var(--orange); }
.rules-save-btn { padding: 11px 26px; background: linear-gradient(135deg, var(--orange), var(--gold)); color: white; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; box-shadow: 0 6px 18px rgba(225,112,85,.3); transition: all .25s; }
.rules-save-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(225,112,85,.4); }
.rules-save-btn:disabled { opacity: .6; cursor: not-allowed; }
.modal-ov { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); backdrop-filter: blur(6px); z-index: 10000; align-items: center; justify-content: center; }
.modal-ov.show { display: flex; }
.modal-box { background: white; border-radius: 24px; padding: 40px 32px; position: relative; z-index: 10002; text-align: center; max-width: 360px; width: 90%; box-shadow: 0 32px 80px rgba(0,0,0,.25); animation: popIn .4s cubic-bezier(.34,1.56,.64,1); }
@keyframes popIn { from{transform:scale(.7);opacity:0} to{transform:scale(1);opacity:1} }
.modal-emoji { font-size: 58px; margin-bottom: 14px; display: block; }
.modal-box h2 { font-size: 22px; font-weight: 800; color: var(--dark); margin-bottom: 6px; }
.modal-sub { font-size: 14px; color: var(--mid); margin-bottom: 10px; }
.modal-result { font-size: 28px; font-weight: 800; color: var(--purple); padding: 12px 24px; background: rgba(108,92,231,.08); border-radius: 12px; margin: 10px 0 22px; word-break: break-word; }
.modal-close { padding: 12px 36px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: white; border: none; border-radius: 50px; font-size: 15px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(108,92,231,.3); transition: .2s; }
.modal-close:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(108,92,231,.4); }
.toast { position: fixed; top: 24px; right: 24px; padding: 14px 22px; border-radius: 12px; font-size: 14px; font-weight: 600; color: white; z-index: 99999; box-shadow: 0 8px 24px rgba(0,0,0,.15); transform: translateX(140%); transition: transform .4s cubic-bezier(.34,1.56,.64,1); display: flex; align-items: center; gap: 10px; max-width: 340px; }
.toast.show { transform: translateX(0); }
.toast.success { background: linear-gradient(135deg, var(--green), var(--teal)); }
.toast.error   { background: linear-gradient(135deg, var(--red), var(--orange)); }

/* ── Nasıl Çalışır? butonu ─────────────────────── */
.ck-help-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: 50px; border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--purple), var(--purple-l));
    color: #fff; font-size: 13.5px; font-weight: 700;
    box-shadow: 0 6px 18px rgba(108,92,231,.32); transition: all .2s;
}
.ck-help-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(108,92,231,.42); }
.ck-head-right { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

/* ── Nasıl Çalışır? modal ──────────────────────── */
.ckh-ov { display: none; position: fixed; inset: 0; background: rgba(15,8,35,.6); backdrop-filter: blur(6px); z-index: 10050; align-items: flex-start; justify-content: center; padding: 30px 16px; overflow-y: auto; }
.ckh-ov.show { display: flex; }
.ckh-box { background: #fff; border-radius: 22px; max-width: 760px; width: 100%; box-shadow: 0 32px 90px rgba(0,0,0,.3); animation: popIn .35s cubic-bezier(.34,1.56,.64,1); overflow: hidden; margin: auto; }
.ckh-top { background: linear-gradient(135deg, #160630, #2b1a68 60%, #4c2a9e); color: #fff; padding: 26px 28px; position: relative; }
.ckh-top h2 { font-size: 22px; font-weight: 800; margin: 0 0 4px; }
.ckh-top p { font-size: 13.5px; opacity: .85; margin: 0; max-width: 560px; }
.ckh-x { position: absolute; top: 18px; right: 18px; width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer; background: rgba(255,255,255,.15); color: #fff; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: .2s; }
.ckh-x:hover { background: rgba(255,255,255,.28); }
.ckh-body { padding: 24px 28px 28px; }
.ckh-h { font-size: 15px; font-weight: 800; color: var(--dark); margin: 22px 0 12px; display: flex; align-items: center; gap: 8px; }
.ckh-h:first-child { margin-top: 0; }
.ckh-h .num { width: 24px; height: 24px; border-radius: 7px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: #fff; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }

/* Adım kartları */
.ckh-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
@media(max-width:620px){ .ckh-steps { grid-template-columns: 1fr; } }
.ckh-step { background: #f8f7ff; border: 1.5px solid #ece9ff; border-radius: 14px; padding: 16px; text-align: center; }
.ckh-step .ic { width: 48px; height: 48px; margin: 0 auto 10px; border-radius: 14px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: #fff; font-size: 24px; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 16px rgba(108,92,231,.3); }
.ckh-step b { display: block; font-size: 14px; color: var(--dark); margin-bottom: 4px; }
.ckh-step span { font-size: 12.5px; color: var(--mid); line-height: 1.4; }

/* Kazananı seç vurgusu */
.ckh-pick { display: flex; gap: 16px; align-items: center; background: linear-gradient(135deg, rgba(0,184,148,.08), rgba(108,92,231,.06)); border: 1.5px solid rgba(0,184,148,.25); border-radius: 16px; padding: 18px; }
@media(max-width:560px){ .ckh-pick { flex-direction: column; text-align: center; } }
.ckh-pick .txt b { color: var(--green); font-size: 15px; }
.ckh-pick .txt p { font-size: 13px; color: #555; line-height: 1.5; margin: 6px 0 0; }

/* Ödül tipi kartları */
.ckh-types { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
@media(max-width:560px){ .ckh-types { grid-template-columns: 1fr; } }
.ckh-type { display: flex; gap: 11px; align-items: flex-start; background: #fff; border: 1.5px solid #eee; border-radius: 12px; padding: 12px 14px; }
.ckh-type .e { font-size: 22px; line-height: 1; }
.ckh-type b { font-size: 13.5px; color: var(--dark); }
.ckh-type span { display: block; font-size: 12px; color: var(--mid); margin-top: 2px; line-height: 1.4; }

/* Akış */
.ckh-flow { display: flex; align-items: stretch; gap: 8px; flex-wrap: wrap; }
.ckh-flow .node { flex: 1; min-width: 120px; background: #f8f7ff; border: 1.5px solid #ece9ff; border-radius: 12px; padding: 12px; text-align: center; font-size: 12.5px; color: var(--dark); font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 6px; }
.ckh-flow .node .e { font-size: 22px; }
.ckh-flow .arr { display: flex; align-items: center; color: var(--purple); font-size: 22px; font-weight: 800; }
@media(max-width:620px){ .ckh-flow { flex-direction: column; } .ckh-flow .arr { transform: rotate(90deg); align-self: center; } }

/* İpuçları */
.ckh-tip { display: flex; gap: 10px; align-items: flex-start; padding: 9px 0; border-bottom: 1px dashed #eee; font-size: 13px; color: #444; line-height: 1.5; }
.ckh-tip:last-child { border-bottom: none; }
.ckh-tip .e { font-size: 17px; flex-shrink: 0; }
.ckh-tip b { color: var(--dark); }
.ckh-done { width: 100%; margin-top: 22px; padding: 14px; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--purple), var(--purple-l)); color: #fff; font-size: 15px; font-weight: 800; cursor: pointer; box-shadow: 0 8px 22px rgba(108,92,231,.35); transition: .2s; }
.ckh-done:hover { transform: translateY(-2px); }
</style>

<div class="ck-page">

    {{-- Tab bar --}}
    <div class="ck-tabs">
        <a href="/cark-yonetim" class="ck-tab active"><span class="material-icons-outlined">casino</span> Çarkıfelek</a>
        <a href="/cark-kazananlar" class="ck-tab"><span class="material-icons-outlined">emoji_events</span> Çark Kazananlar</a>
        <a href="/cark-hatirlatma" class="ck-tab"><span class="material-icons-outlined">notifications</span> Hatırlatma</a>
    </div>

    {{-- Header --}}
    <div class="ck-header">
        <div class="ck-header-left">
            <div class="ck-header-icon">🎡</div>
            <div>
                <h1>Çarkıfelek Sistemi</h1>
                <p>Dilimlerinizi düzenleyin, müşterilerinize özel ödüller sunun</p>
            </div>
        </div>
        <div class="ck-head-right">
            <button class="ck-help-btn" type="button" onclick="ckHelpAc()">
                <span class="material-icons-outlined" style="font-size:18px">help_outline</span> Nasıl Çalışır?
            </button>
            <button class="ck-help-btn" type="button" style="background:linear-gradient(135deg,var(--green),var(--teal));box-shadow:0 6px 18px rgba(0,184,148,.3)" onclick="onerilenUygula()">
                <span class="material-icons-outlined" style="font-size:18px">auto_fix_high</span> Önerilen Düzeni Uygula
            </button>
            <div class="status-pill on" id="status-pill">
                <span class="status-dot"></span>
                <span id="status-text">Aktif</span>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="ck-grid">

        {{-- Wheel Panel --}}
        <div class="wheel-panel">
            <p class="wp-label">Önizleme</p>
            <div class="pointer-wrap">
                <svg id="pointer-pin" class="pointer" viewBox="0 0 32 54" width="32" height="54" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="pg" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#7f1d1d"/><stop offset="40%" stop-color="#ef4444"/>
                            <stop offset="70%" stop-color="#fca5a5"/><stop offset="100%" stop-color="#7f1d1d"/>
                        </linearGradient>
                        <linearGradient id="pg2" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#92400e"/><stop offset="50%" stop-color="#fbbf24"/><stop offset="100%" stop-color="#92400e"/>
                        </linearGradient>
                    </defs>
                    <polygon points="16,54 2,10 30,10" fill="url(#pg)" stroke="#7f1d1d" stroke-width="1"/>
                    <rect x="2" y="0" width="28" height="12" rx="5" fill="url(#pg)" stroke="#7f1d1d" stroke-width="1"/>
                    <polygon points="16,48 8,14 13,14" fill="rgba(255,255,255,0.18)"/>
                    <circle cx="16" cy="54" r="4" fill="url(#pg2)" stroke="#78350f" stroke-width="1"/>
                </svg>
            </div>
            <div class="wheel-glow">
                <div class="wheel-wrap"><svg id="wheel" viewBox="0 0 300 300"></svg></div>
            </div>
            <div><button class="spin-btn" id="spin-btn" onclick="testSpin()">🎲 Test Et</button></div>
            <div class="wp-row" style="margin-top:20px">
                <span class="wp-row-label">Çark Durumu</span>
                <label class="toggle">
                    <input type="checkbox" id="status-toggle" checked onchange="toggleStatus()">
                    <span class="tslider"></span>
                </label>
            </div>
        </div>

        {{-- Management Panel --}}
        <div class="mgmt-panel">
            <div class="mgmt-head">
                <h2>Dilim Yönetimi</h2>
                <div class="count-ctrl">
                    <button class="cnt-btn" onclick="changeCount(-1)">−</button>
                    <span class="cnt-val" id="cnt-val">6</span>
                    <button class="cnt-btn" onclick="changeCount(1)">+</button>
                </div>
            </div>
            <p class="mgmt-hint">Kazandırmak istediğiniz dilimi seçin — müşteri çarkı ne kadar çevirirse çevirsin o dilim çıkar. (Min 6 · Maks 12 dilim)</p>
            <div class="slices-body" id="slices-list"></div>

            <div class="mgmt-coupon">
                <div class="mgmt-coupon-head">
                    <span class="mgmt-coupon-icon">🔁</span>
                    <div>
                        <h3>Çevirme Sıklığı</h3>
                        <p>Bir müşteri çarkı kaç <b>günde bir</b> çevirebilsin? <b>1</b> = her gün. Örn. <b>7</b> → müşteri son çevirmeden 7 gün sonra tekrar çevirebilir.</p>
                    </div>
                </div>
                <div class="mgmt-coupon-grid">
                    <div class="mgmt-coupon-field">
                        <label>Çevirme aralığı (gün)</label>
                        <input type="number" min="1" step="1" id="cevirme-araligi-gun" placeholder="1 = her gün" />
                        <small>En az 1. Değer büyüdükçe müşteri daha seyrek çevirir.</small>
                    </div>
                </div>
            </div>

            <div class="mgmt-coupon">
                <div class="mgmt-coupon-head">
                    <span class="mgmt-coupon-icon">⏳</span>
                    <div>
                        <h3>Kupon Geçerlilik Süreleri</h3>
                        <p>Müşteriye verilen kuponların kaç gün geçerli olacağını belirleyin. <b>0</b> veya boş = <b>süresiz</b>.</p>
                    </div>
                </div>
                <div class="mgmt-coupon-grid">
                    <div class="mgmt-coupon-field">
                        <label>Çark ödülü kuponu (gün)</label>
                        <input type="number" min="0" step="1" id="kupon-cark-gun" placeholder="0 = süresiz" />
                        <small>Örn. 30 → çark döndürünce kazanılan kupon 30 gün geçerli.</small>
                    </div>
                    <div class="mgmt-coupon-field">
                        <label>Puan ödülü kuponu (gün)</label>
                        <input type="number" min="0" step="1" id="kupon-puan-gun" placeholder="0 = süresiz" />
                        <small>Örn. 60 → puan biriktirip talep edilen ödül 60 gün geçerli.</small>
                    </div>
                </div>
            </div>

            <div class="mgmt-foot">
                <div class="winner-info" id="winner-info">
                    <span class="winner-icon">🏆</span>
                    <div>
                        <div class="winner-label">Çıkacak dilim:</div>
                        <div class="winner-name" id="winner-name">Seçilmedi</div>
                    </div>
                </div>
                <button class="save-btn" id="save-btn" onclick="saveSlices()">💾 Ayarı Kaydet</button>
            </div>
        </div>
    </div>

    {{-- Kullanım Kuralları --}}
    <div class="rules-card">
        <div class="rules-head">
            <div class="rules-head-icon">📋</div>
            <div>
                <h2>Çarkıfelek Kullanım Kuralları</h2>
                <p>Bu metin, çarkı çeviren müşterilere bilgilendirme olarak gösterilir.</p>
            </div>
        </div>
        <textarea class="rules-textarea" id="rules-text"
            placeholder="Örnek:&#10;• Kazandığınız ödüller 30 gün içinde geçerlidir.&#10;• Her müşteri günde 1 kez çevirebilir.&#10;• Kuponlar başka kampanyalarla birleştirilemez.&#10;• Ödüller nakde çevrilemez."></textarea>
        <div class="rules-foot">
            <span class="rules-hint">Boş bırakırsanız müşterilere <b>koruyucu varsayılan kurallar</b> gösterilir.</span>
            <button class="rules-save-btn" id="rules-save-btn" onclick="saveKurallar()">📋 Kuralları Kaydet</button>
        </div>
    </div>
</div>

{{-- Result Modal --}}
<div class="modal-ov" id="result-modal">
    <div class="modal-box">
        <span class="modal-emoji">🎉</span>
        <h2>Sonuç!</h2>
        <p class="modal-sub">Test çevirme sonucu:</p>
        <div class="modal-result" id="modal-result-text"></div>
        <button class="modal-close" onclick="closeModal()">Kapat</button>
    </div>
</div>
<div class="toast" id="toast"></div>

{{-- Nasıl Çalışır? Modal --}}
<div class="ckh-ov" id="ckh-ov">
    <div class="ckh-box">
        <div class="ckh-top">
            <button class="ckh-x" type="button" onclick="ckHelpKapat()">×</button>
            <h2>🎡 Çarkıfelek Nasıl Çalışır?</h2>
            <p>Müşterileriniz çarkı çevirir, hangi dilimi kazanacaklarını siz belirlersiniz. Puan biriktirir, indirim kazanır ve tekrar gelir. Maliyeti tamamen siz kontrol edersiniz.</p>
        </div>
        <div class="ckh-body">

            <div class="ckh-h"><span class="num">1</span> 3 Adımda Kurulum</div>
            <div class="ckh-steps">
                <div class="ckh-step">
                    <div class="ic">🎨</div>
                    <b>Dilimleri düzenle</b>
                    <span>Her dilime renk, ad, ödül tipi (Puan/Hesap/Ürün/Paket) ve değer ver. − / + ile 6–12 dilim.</span>
                </div>
                <div class="ckh-step">
                    <div class="ic">⭐</div>
                    <b>Kazanan dilimi seç</b>
                    <span>“★ Kazanan” işaretlediğin dilim, müşteri ne kadar döndürürse döndürsün her zaman çıkar.</span>
                </div>
                <div class="ckh-step">
                    <div class="ic">💾</div>
                    <b>Kaydet</b>
                    <span>“Ayarı Kaydet”e bas — çark müşteri uygulamasında anında güncellenir.</span>
                </div>
            </div>

            <div class="ckh-h"><span class="num">2</span> “Kazanan Dilimi Seç” Mantığı (en önemli)</div>
            <div class="ckh-pick">
                <svg width="110" height="110" viewBox="0 0 120 120" style="flex-shrink:0">
                    <g>
                        <path d="M60 60 L60 6 A54 54 0 0 1 106 33 Z" fill="#51CF66"/>
                        <path d="M60 60 L106 33 A54 54 0 0 1 106 87 Z" fill="#FFC107"/>
                        <path d="M60 60 L106 87 A54 54 0 0 1 60 114 Z" fill="#FF8E53"/>
                        <path d="M60 60 L60 114 A54 54 0 0 1 14 87 Z" fill="#CC5DE8"/>
                        <path d="M60 60 L14 87 A54 54 0 0 1 14 33 Z" fill="#339AF0"/>
                        <path d="M60 60 L14 33 A54 54 0 0 1 60 6 Z" fill="#F06595"/>
                        <path d="M60 6 A54 54 0 0 1 106 33" fill="none" stroke="#FFD700" stroke-width="5"/>
                        <circle cx="60" cy="60" r="12" fill="#fff" stroke="#e2e8f0" stroke-width="2"/>
                        <polygon points="54,2 66,2 60,16" fill="#d63031"/>
                    </g>
                </svg>
                <div class="txt">
                    <b>Kazanan dilimi siz belirlersiniz.</b>
                    <p>Çark ekranda gerçekten döner (müşteriye heyecanlı bir şov) ama hangi dilimin çıkacağını <b>siz seçersiniz</b>. Böylece “bugün ne kazandıracağım?” kararı ve maliyet tamamen sizin kontrolünüzde olur. İsterseniz her gün kazananı değiştirebilirsiniz.</p>
                </div>
            </div>

            <div class="ckh-h"><span class="num">3</span> Ödül Tipleri</div>
            <div class="ckh-types">
                <div class="ckh-type"><span class="e">💰</span><div><b>Puan</b><span>Müşterinin puanına eklenir. Puan Ödülleri menüsünde harcanır.</span></div></div>
                <div class="ckh-type"><span class="e">🧾</span><div><b>Hesap İndirimi</b><span>Tüm adisyona % veya ₺ indirim (masada yeme-içme).</span></div></div>
                <div class="ckh-type"><span class="e">🍽️</span><div><b>Ürün İndirimi</b><span>Bir ürüne/ikrama indirim (ör. “Bedava İçecek” = 40₺).</span></div></div>
                <div class="ckh-type"><span class="e">🛵</span><div><b>Paket İndirimi</b><span>Paket/gel-al siparişlere indirim.</span></div></div>
                <div class="ckh-type"><span class="e">🔄</span><div><b>Tekrar Dene</b><span>Hak yanmaz, müşteri tekrar çevirir.</span></div></div>
                <div class="ckh-type"><span class="e">—</span><div><b>Boş</b><span>Hiçbir şey. Restoranda önerilmez (moral bozar).</span></div></div>
            </div>

            <div class="ckh-h"><span class="num">4</span> Müşteri → Kasa Akışı</div>
            <div class="ckh-flow">
                <div class="node"><span class="e">🎡</span>Müşteri çarkı çevirir</div>
                <div class="arr">→</div>
                <div class="node"><span class="e">🎉</span>Ödülü kazanır</div>
                <div class="arr">→</div>
                <div class="node"><span class="e">💰/🎟️</span>Puan anında hesaba<br>veya kupon kodu</div>
                <div class="arr">→</div>
                <div class="node"><span class="e">🧾</span>Kasada “Ödül Doğrula” →<br>adisyona işlenir</div>
            </div>
            <p style="font-size:12.5px;color:var(--mid);margin-top:10px;line-height:1.5">Puan ödülü <b>anında</b> müşterinin hesabına eklenir. İndirim ödülleri bir <b>kupon kodu</b> üretir; müşteri kasada gösterir, personel <b>Ödül Doğrula</b> ekranından okutur ve indirim açık adisyona işlenir (puan düşmez).</p>

            <div class="ckh-h"><span class="num">5</span> İpuçları</div>
            <div class="ckh-tip"><span class="e">📅</span><div><b>Çevirme aralığı:</b> 7 gün önerilir — haftada 1 çevirme özel hissettirir, her gün olursa sıradanlaşır.</div></div>
            <div class="ckh-tip"><span class="e">⏳</span><div><b>Kupon geçerlilik:</b> 7–14 gün — müşteriyi yakın zamanda geri getirir.</div></div>
            <div class="ckh-tip"><span class="e">🎯</span><div><b>Kampanya günü:</b> kazananı cazip bir ödüle çek (ör. “Bedava İçecek”), normal günler ucuz tut (10–25 Puan).</div></div>
            <div class="ckh-tip"><span class="e">%</span><div><b>% mi ₺ mi:</b> yüzde büyük hesabı teşvik eder (upsell); sabit ₺ maliyeti öngörülebilir kılar. Paket/içecek için ₺ idealdir.</div></div>

            <button class="ckh-done" type="button" onclick="ckHelpKapat()">Anladım, başlayalım! 🚀</button>
        </div>
    </div>
</div>

<script>
(function () {
    const CX = 150, CY = 150, R = 130;
    const COLORS = ['#FF6B6B','#FF8E53','#FFC107','#51CF66','#339AF0','#CC5DE8','#F06595','#74C0FC','#63E6BE','#FFD43B','#FF922B','#20C997','#4DABF7','#DA77F2','#F783AC','#E64980','#7950F2','#4C6EF5','#228BE6','#099268'];
    const TIPS = [
        { id: 'puan',            label: '💰 Puan',   hasDeger: true,  unit: 'puan' },
        { id: 'hizmet_indirimi', label: '🧾 Hesap',  hasDeger: true,  unit: '%'    },
        { id: 'urun_indirimi',   label: '🍽️ Ürün',   hasDeger: true,  unit: '%'    },
        { id: 'paket_indirimi',  label: '🛵 Paket',  hasDeger: true,  unit: '%'    },
        { id: 'tekrar_dene',     label: '🔄 Tekrar', hasDeger: false, unit: ''     },
        { id: 'bos',             label: '— Boş',      hasDeger: false, unit: ''     },
    ];
    function isIndirim(tip) { return tip === 'hizmet_indirimi' || tip === 'urun_indirimi' || tip === 'paket_indirimi'; }
    function sliceUnit(sl) { if (sl.tip === 'puan') return 'puan'; if (isIndirim(sl.tip)) return (sl.indirim_tipi === 'tutar') ? '₺' : '%'; return ''; }
    const HEADERS = { 'Content-Type':'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]')||{}).content || '', 'Accept':'application/json' };

    let slices = [
        { name: '100 Puan',   color: COLORS[0], tip: 'puan',            deger: 100 },
        { name: '%20 Hizmet', color: COLORS[1], tip: 'hizmet_indirimi', deger: 20, indirim_tipi:'yuzde' },
        { name: '%10 Ürün',   color: COLORS[2], tip: 'urun_indirimi',   deger: 10, indirim_tipi:'yuzde' },
        { name: '50 Puan',    color: COLORS[3], tip: 'puan',            deger: 50 },
        { name: 'Tekrar Dene',color: COLORS[4], tip: 'tekrar_dene',     deger: null },
        { name: 'Boş',        color: COLORS[5], tip: 'bos',             deger: null }
    ];
    let selectedIdx = 0, isActive = true, currentRot = 0, spinning = false, kurallar = '';

    const wheelEl = document.getElementById('wheel'), slicesList = document.getElementById('slices-list'),
          cntVal = document.getElementById('cnt-val'), winnerName = document.getElementById('winner-name'),
          spinBtn = document.getElementById('spin-btn'), statusToggle = document.getElementById('status-toggle'),
          statusPill = document.getElementById('status-pill'), statusText = document.getElementById('status-text'),
          toast = document.getElementById('toast'), resultModal = document.getElementById('result-modal'),
          resultText = document.getElementById('modal-result-text'), rulesText = document.getElementById('rules-text');

    document.addEventListener('DOMContentLoaded', () => {
        loadData();
        resultModal.addEventListener('click', e => { if (e.target === resultModal) closeModal(); });
        setupKurallarBullet();
    });

    function setupKurallarBullet() {
        if (!rulesText) return;
        rulesText.addEventListener('focus', () => { if (rulesText.value.trim() === '') { rulesText.value = '• '; rulesText.selectionStart = rulesText.selectionEnd = rulesText.value.length; } });
        rulesText.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const s = rulesText.selectionStart, en = rulesText.selectionEnd, ins = '\n• ', val = rulesText.value;
                rulesText.value = val.slice(0, s) + ins + val.slice(en);
                const pos = s + ins.length; rulesText.selectionStart = rulesText.selectionEnd = pos;
            }
        });
    }
    function temizleKurallar(text) { return (text||'').split('\n').map(l => l.replace(/\s+$/, '')).filter(l => l !== '' && l !== '•' && l !== '-').join('\n'); }

    async function loadData() {
        try {
            const res  = await fetch('/cark-yonetim-veri', { headers: HEADERS });
            const data = await res.json();
            if (data.success && data.data) {
                isActive = data.data.aktifmi == 1;
                kurallar = data.data.kurallar || '';
                if (rulesText) rulesText.value = kurallar;
                const _carkGun = parseInt(data.data.kupon_cark_gecerlilik_gun) || 0;
                const _puanGun = parseInt(data.data.kupon_puan_gecerlilik_gun) || 0;
                const _carkEl = document.getElementById('kupon-cark-gun'), _puanEl = document.getElementById('kupon-puan-gun');
                if (_carkEl) _carkEl.value = _carkGun > 0 ? _carkGun : '';
                if (_puanEl) _puanEl.value = _puanGun > 0 ? _puanGun : '';
                const _aralik = parseInt(data.data.cevirme_araligi_gun) || 1;
                const _aralikEl = document.getElementById('cevirme-araligi-gun');
                if (_aralikEl) _aralikEl.value = _aralik > 1 ? _aralik : '';
                if (data.data.dilimler && data.data.dilimler.length > 0) {
                    slices = data.data.dilimler.map(d => ({
                        name: d.name, color: d.color || COLORS[0],
                        probability: parseInt(d.probability) || 0,
                        tip: d.tip || 'bos',
                        deger: d.deger != null ? parseFloat(d.deger) : null,
                        indirim_tipi: d.indirim_tipi === 'tutar' ? 'tutar' : 'yuzde',
                        _named: !!(d.name && String(d.name).trim().length > 0),
                    }));
                    const found = slices.findIndex(s => s.probability === 100);
                    selectedIdx = found >= 0 ? found : 0;
                    cntVal.textContent = slices.length;
                }
            }
        } catch (e) { console.error('Veri yüklenemedi:', e); }
        render();
    }

    function render() { renderWheel(); renderList(); updateWinnerUI(); updateStatusUI(); }

    function renderWheel() {
        wheelEl.innerHTML = '';
        if (!slices.length) return;
        const n = slices.length, ang = 360 / n;
        slices.forEach((sl, i) => {
            const sa = i * ang, ea = (i + 1) * ang;
            const sr = (sa - 90) * Math.PI / 180, er = (ea - 90) * Math.PI / 180;
            const x1 = CX + R * Math.cos(sr), y1 = CY + R * Math.sin(sr);
            const x2 = CX + R * Math.cos(er), y2 = CY + R * Math.sin(er);
            const lg = ang > 180 ? 1 : 0;
            const path = svgEl('path');
            path.setAttribute('d', `M ${CX} ${CY} L ${x1} ${y1} A ${R} ${R} 0 ${lg} 1 ${x2} ${y2} Z`);
            path.setAttribute('fill', sl.color); path.setAttribute('stroke', 'rgba(255,255,255,.7)'); path.setAttribute('stroke-width', '2');
            wheelEl.appendChild(path);
            const tAng = sa + ang / 2, tRad = (tAng - 90) * Math.PI / 180;
            const textRot = tAng <= 180 ? tAng - 90 : tAng - 270;
            const isWin = i === selectedIdx;
            const tipObj = TIPS.find(t => t.id === (sl.tip || 'bos')) || TIPS[4];
            if (tipObj.hasDeger && sl.deger != null) {
                const numStr = isIndirim(sl.tip) ? (sl.indirim_tipi === 'tutar' ? sl.deger + '₺' : '%' + sl.deger) : String(sl.deger);
                const catStr = sl.tip === 'puan' ? 'Puan' : sl.tip === 'hizmet_indirimi' ? 'Hesap' : sl.tip === 'paket_indirimi' ? 'Paket' : 'Ürün';
                const numFs = n <= 8 ? 17 : 14, catFs = n <= 8 ? 11 : 9;
                const numDist = R - (n <= 8 ? 16 : 13);
                const nx = CX + numDist * Math.cos(tRad), ny = CY + numDist * Math.sin(tRad);
                const ng = svgEl('g'); ng.setAttribute('transform', `rotate(${tAng}, ${nx}, ${ny})`);
                const nt = svgEl('text'); nt.setAttribute('x', nx); nt.setAttribute('y', ny);
                nt.setAttribute('text-anchor','middle'); nt.setAttribute('dominant-baseline','middle');
                nt.setAttribute('font-size', numFs); nt.setAttribute('font-weight','900'); nt.setAttribute('fill','white');
                nt.setAttribute('paint-order','stroke'); nt.setAttribute('stroke','rgba(0,0,0,.75)'); nt.setAttribute('stroke-width','3.5'); nt.setAttribute('stroke-linejoin','round');
                nt.textContent = numStr; ng.appendChild(nt); wheelEl.appendChild(ng);
                const innerLabel = sl.name || catStr;
                const catDist = n <= 8 ? 68 : 60;
                const cx2 = CX + catDist * Math.cos(tRad), cy2 = CY + catDist * Math.sin(tRad);
                const catMaxCh = Math.max(4, Math.floor(55 / (catFs * 0.62)));
                let catLines = wrapText(innerLabel, catMaxCh);
                if (catLines.length > 2) catLines = [innerLabel.slice(0, catMaxCh - 1) + '…'];
                const catLH = catFs + 2, catSY = cy2 - ((catLines.length - 1) * catLH / 2);
                const catG = svgEl('g'); catG.setAttribute('transform', `rotate(${textRot}, ${cx2}, ${cy2})`);
                catLines.forEach((ln, li) => {
                    const t = svgEl('text'); t.setAttribute('x', cx2); t.setAttribute('y', catSY + li * catLH);
                    t.setAttribute('text-anchor','middle'); t.setAttribute('dominant-baseline','middle');
                    t.setAttribute('font-size', catFs); t.setAttribute('font-weight','700'); t.setAttribute('fill','rgba(255,255,255,.92)');
                    t.setAttribute('paint-order','stroke'); t.setAttribute('stroke','rgba(0,0,0,.5)'); t.setAttribute('stroke-width','2'); t.setAttribute('stroke-linejoin','round');
                    t.textContent = ln; catG.appendChild(t);
                });
                wheelEl.appendChild(catG);
            } else {
                const dist = n <= 8 ? 76 : 68, fs = n <= 8 ? 12 : 10;
                const maxCh = Math.max(6, Math.floor(80 / (fs * 0.60))), lh = fs + 3;
                const label = buildAutoName(sl);
                let lines = wrapText(label, maxCh);
                if (lines.length > 2) lines = [label.length > maxCh ? label.slice(0, maxCh - 1) + '…' : label];
                const tx = CX + dist * Math.cos(tRad), ty = CY + dist * Math.sin(tRad);
                const sy = ty - ((lines.length - 1) * lh / 2);
                const fill = isWin ? '#FFD700' : 'white', strk = isWin ? 'rgba(100,55,0,.9)' : 'rgba(0,0,0,.55)';
                const g = svgEl('g'); g.setAttribute('transform', `rotate(${textRot}, ${tx}, ${ty})`);
                lines.forEach((ln, li) => {
                    const t = svgEl('text'); t.setAttribute('x', tx); t.setAttribute('y', sy + li * lh);
                    t.setAttribute('text-anchor','middle'); t.setAttribute('dominant-baseline','middle');
                    t.setAttribute('font-size', fs); t.setAttribute('font-weight','700'); t.setAttribute('fill', fill);
                    t.setAttribute('paint-order','stroke'); t.setAttribute('stroke', strk); t.setAttribute('stroke-width','2.5'); t.setAttribute('stroke-linejoin','round');
                    t.textContent = ln; g.appendChild(t);
                });
                wheelEl.appendChild(g);
            }
            if (isWin) {
                const arc = svgEl('path');
                arc.setAttribute('d', `M ${x1} ${y1} A ${R} ${R} 0 ${lg} 1 ${x2} ${y2}`);
                arc.setAttribute('fill','none'); arc.setAttribute('stroke','#FFD700'); arc.setAttribute('stroke-width','5');
                arc.setAttribute('filter','drop-shadow(0 0 3px rgba(255,215,0,.8))');
                wheelEl.appendChild(arc);
            }
        });
    }

    function renderList() {
        slicesList.innerHTML = '';
        slices.forEach((sl, i) => {
            const isWinner = i === selectedIdx;
            const tipObj = TIPS.find(t => t.id === (sl.tip || 'bos')) || TIPS[4];
            const degerVal = sl.deger != null ? sl.deger : '';
            const placeholder = sl.tip === 'puan' ? 'Puan miktarı' : (sl.indirim_tipi === 'tutar' ? 'Tutar (₺)' : 'Yüzde (%)');
            const unitCell = isIndirim(sl.tip)
                ? `<div class="unit-toggle" title="İndirim birimi">
                       <button class="${sl.indirim_tipi !== 'tutar' ? 'active' : ''}" onclick="window.CK.setBirim(${i},'yuzde')" title="Yüzde indirim">%</button>
                       <button class="${sl.indirim_tipi === 'tutar' ? 'active' : ''}" onclick="window.CK.setBirim(${i},'tutar')" title="Sabit ₺ tutar">₺</button>
                   </div>`
                : `<span class="deger-unit">${sliceUnit(sl)}</span>`;
            const div = document.createElement('div');
            div.className = 'slice-item' + (isWinner ? ' winner-row' : '');
            div.innerHTML = `
                <div class="slice-top">
                    <div class="s-num">${i + 1}</div>
                    <div class="s-color" style="background:${esc(sl.color)}" title="Renk seç">
                        <input type="color" value="${esc(sl.color)}" data-i="${i}" oninput="window.CK.color(this)" onchange="window.CK.color(this)">
                    </div>
                    <input type="text" class="s-name" value="${esc(sl.name)}" data-i="${i}" placeholder="Dilim adı" oninput="window.CK.name(this)" onblur="window.CK.name(this)">
                    <button class="pick-btn${isWinner ? ' winner' : ''}" onclick="window.CK.pick(${i})">${isWinner ? '★ Kazanan' : 'Seç'}</button>
                </div>
                <div class="slice-bot">
                    <div class="tip-pills">
                        ${TIPS.map(t => `<button class="tip-pill${sl.tip === t.id ? ' active' : ''}" onclick="window.CK.setTip(${i},'${t.id}')">${t.label}</button>`).join('')}
                    </div>
                    <div class="deger-wrap${tipObj.hasDeger ? ' show' : ''}" data-i="${i}">
                        <input type="number" class="s-deger" data-i="${i}" min="0" step="any" value="${degerVal}" placeholder="${placeholder}" oninput="window.CK.setDeger(this)">
                        ${unitCell}
                    </div>
                </div>`;
            slicesList.appendChild(div);
        });
    }

    function buildAutoName(sl) {
        const d = sl.deger;
        switch (sl.tip) {
            case 'puan': return d != null ? 'Puan' : (sl.name || 'Puan');
            case 'hizmet_indirimi': return d != null ? 'Hesap İnd.' : (sl.name || 'Hesap İnd.');
            case 'urun_indirimi': return d != null ? 'Ürün İnd.' : (sl.name || 'Ürün İnd.');
            case 'paket_indirimi': return d != null ? 'Paket İnd.' : (sl.name || 'Paket İnd.');
            case 'tekrar_dene': return 'Tekrar Dene';
            case 'bos': return 'Boş';
            default: return sl.name || 'Ödül';
        }
    }
    function buildFullName(sl) {
        const tipObj = TIPS.find(t => t.id === (sl.tip || 'bos')) || TIPS[4];
        if (tipObj.hasDeger && sl.deger != null) {
            const numStr = isIndirim(sl.tip) ? (sl.indirim_tipi === 'tutar' ? sl.deger + '₺' : '%' + sl.deger) : sl.deger;
            return numStr + ' ' + (sl.name || buildAutoName(sl));
        }
        return sl.name || buildAutoName(sl);
    }
    function updateWinnerUI() { const sl = slices[selectedIdx]; winnerName.textContent = sl ? buildFullName(sl) : 'Seçilmedi'; }
    function updateStatusUI() {
        statusToggle.checked = isActive;
        if (isActive) { statusPill.className = 'status-pill on'; statusText.textContent = 'Aktif'; }
        else { statusPill.className = 'status-pill off'; statusText.textContent = 'Pasif'; }
    }

    window.CK = {
        color(input) { const i = +input.dataset.i; slices[i].color = input.value; input.closest('.s-color').style.background = input.value; renderWheel(); },
        name(input) { const i = +input.dataset.i; const v = (input.value||'').trim(); slices[i].name = v || `Ödül ${i+1}`; slices[i]._named = v.length > 0; renderWheel(); updateWinnerUI(); },
        pick(i) { selectedIdx = i; render(); },
        setTip(i, tip) {
            slices[i].tip = tip;
            const tipObj = TIPS.find(t => t.id === tip) || TIPS[4];
            if (!tipObj.hasDeger) slices[i].deger = null;
            if (isIndirim(tip) && slices[i].indirim_tipi !== 'tutar') slices[i].indirim_tipi = 'yuzde';
            if (!slices[i]._named) slices[i].name = buildAutoName(slices[i]);
            renderList(); renderWheel(); updateWinnerUI();
        },
        setBirim(i, birim) { slices[i].indirim_tipi = (birim === 'tutar') ? 'tutar' : 'yuzde'; renderList(); renderWheel(); updateWinnerUI(); },
        setDeger(input) { const i = +input.dataset.i; const val = parseFloat(input.value); slices[i].deger = isNaN(val) ? null : val; renderWheel(); updateWinnerUI(); }
    };

    window.changeCount = function (delta) {
        const next = slices.length + delta;
        if (next < 6) { showToast('En az 6 dilim olmalıdır.', 'error'); return; }
        if (next > 12) { showToast('En fazla 12 dilim eklenebilir.', 'error'); return; }
        if (delta < 0) { slices = slices.slice(0, next); if (selectedIdx >= slices.length) selectedIdx = slices.length - 1; }
        else { const idx = slices.length % COLORS.length; slices.push({ name: `Ödül ${slices.length+1}`, color: COLORS[idx], tip: 'bos', deger: null }); }
        cntVal.textContent = slices.length; render();
    };

    let _audioCtx = null;
    function getAudioCtx() { if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)(); return _audioCtx; }
    function playTick(vol) {
        try {
            const ctx = getAudioCtx(); if (ctx.state === 'suspended') ctx.resume();
            const now = ctx.currentTime, len = Math.floor(ctx.sampleRate * 0.055);
            const buf = ctx.createBuffer(1, len, ctx.sampleRate), d = buf.getChannelData(0);
            for (let i = 0; i < len; i++) { const t = i / len; d[i] = (Math.random()*2-1) * Math.pow(1-t,4) * (vol||0.45); }
            const src = ctx.createBufferSource(); src.buffer = buf;
            const gain = ctx.createGain(); gain.gain.setValueAtTime(1, now);
            src.connect(gain); gain.connect(ctx.destination); src.start(now);
        } catch(e) {}
    }
    function startTickLoop() {
        const pinEl = document.getElementById('pointer-pin'), n = slices.length, sliceAng = 360 / n;
        let lastTick = -1;
        function frame() {
            if (!spinning) return;
            const mat = new DOMMatrix(window.getComputedStyle(wheelEl).transform);
            const angle = (Math.atan2(mat.b, mat.a) * 180 / Math.PI + 360) % 360;
            const idx = Math.floor(((360 - angle) % 360) / sliceAng) % n;
            if (idx !== lastTick) {
                lastTick = idx; playTick(0.45);
                if (pinEl) { pinEl.classList.remove('tick'); void pinEl.offsetWidth; pinEl.classList.add('tick'); }
            }
            requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    window.testSpin = function () {
        if (spinning || slices.length < 2) return;
        spinning = true; spinBtn.disabled = true; spinBtn.textContent = '⏳ Çevriliyor...';
        try { getAudioCtx().resume(); } catch(e) {}
        const ang = 360 / slices.length;
        const jitter = (Math.random() - 0.5) * ang * 0.6;
        const stopAt = (selectedIdx + 0.5) * ang + jitter;
        const offset = ((360 - stopAt) % 360 + 360) % 360;
        const nSpins = (12 + Math.floor(Math.random() * 5)) * 360;
        const curMod = ((currentRot % 360) + 360) % 360;
        let diff = offset - curMod; if (diff < 0) diff += 360;
        currentRot += nSpins + diff;
        wheelEl.style.transition = 'transform 9s cubic-bezier(0.17, 0.67, 0.12, 0.99)';
        wheelEl.style.transform = `rotate(${currentRot}deg)`;
        startTickLoop();
        setTimeout(() => { spinning = false; spinBtn.disabled = false; spinBtn.textContent = '🎲 Test Et'; showResultModal(selectedIdx); }, 9200);
    };

    let celebCanvas = null, celebCtx2d = null, celebRAF = null, celebParticles = [];
    function startCelebration() {
        if (!celebCanvas) {
            celebCanvas = document.createElement('canvas');
            celebCanvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:10001;pointer-events:none';
            document.body.appendChild(celebCanvas); celebCtx2d = celebCanvas.getContext('2d');
        }
        celebCanvas.width = window.innerWidth; celebCanvas.height = window.innerHeight;
        celebCanvas.style.display = 'block'; celebParticles = [];
        const colors = ['#FF6B6B','#FFE66D','#4ECDC4','#A29BFE','#FD79A8','#6C5CE7','#00B894','#FDCB6E','#E17055','#74B9FF'];
        for (let i = 0; i < 120; i++) celebParticles.push({ type:'confetti', x:Math.random()*celebCanvas.width, y:-20-Math.random()*300, vx:(Math.random()-0.5)*3, vy:3+Math.random()*5, rot:Math.random()*360, rotS:(Math.random()-0.5)*9, w:6+Math.random()*8, h:3+Math.random()*5, color:colors[Math.floor(Math.random()*colors.length)], alpha:1 });
        for (let i = 0; i < 14; i++) celebParticles.push({ type:'balloon', x:40+Math.random()*(celebCanvas.width-80), y:celebCanvas.height+30+Math.random()*120, vx:(Math.random()-0.5)*1.4, vy:-(2+Math.random()*2.8), sway:Math.random()*Math.PI*2, swayS:0.02+Math.random()*0.02, r:18+Math.random()*14, color:colors[Math.floor(Math.random()*colors.length)], alpha:1 });
        for (let b = 0; b < 5; b++) {
            const bx = 80+Math.random()*(celebCanvas.width-160), by = 60+Math.random()*(celebCanvas.height*0.45), bc = colors[Math.floor(Math.random()*colors.length)];
            setTimeout(() => { for (let p = 0; p < 32; p++) { const a=(p/32)*Math.PI*2, s=3+Math.random()*6; celebParticles.push({ type:'spark', x:bx, y:by, vx:Math.cos(a)*s, vy:Math.sin(a)*s, r:3+Math.random()*3, color:bc, alpha:1, life:1 }); } }, b*500);
        }
        if (celebRAF) cancelAnimationFrame(celebRAF);
        animateCelebration(); playCheer();
    }
    function animateCelebration() {
        if (!celebCtx2d) return;
        const W = celebCanvas.width, H = celebCanvas.height;
        celebCtx2d.clearRect(0, 0, W, H);
        celebParticles.forEach(p => {
            if (p.alpha <= 0.01) return;
            celebCtx2d.save(); celebCtx2d.globalAlpha = p.alpha;
            if (p.type === 'confetti') {
                p.x += p.vx; p.y += p.vy; p.vy += 0.09; p.rot += p.rotS;
                if (p.y > H + 20) { p.alpha = 0; celebCtx2d.restore(); return; }
                celebCtx2d.translate(p.x, p.y); celebCtx2d.rotate(p.rot*Math.PI/180);
                celebCtx2d.fillStyle = p.color; celebCtx2d.fillRect(-p.w/2, -p.h/2, p.w, p.h);
            } else if (p.type === 'balloon') {
                p.sway += p.swayS; p.x += p.vx + Math.sin(p.sway)*0.6; p.y += p.vy;
                if (p.y < -70) { p.alpha = 0; celebCtx2d.restore(); return; }
                celebCtx2d.translate(p.x, p.y);
                celebCtx2d.beginPath(); celebCtx2d.arc(0,0,p.r,0,Math.PI*2); celebCtx2d.fillStyle = p.color; celebCtx2d.fill();
                celebCtx2d.beginPath(); celebCtx2d.arc(-p.r*.3,-p.r*.3,p.r*.27,0,Math.PI*2); celebCtx2d.fillStyle = 'rgba(255,255,255,0.38)'; celebCtx2d.fill();
                celebCtx2d.beginPath(); celebCtx2d.moveTo(0,p.r); celebCtx2d.quadraticCurveTo(5,p.r+14,-3,p.r+28); celebCtx2d.strokeStyle='rgba(255,255,255,0.55)'; celebCtx2d.lineWidth=1.2; celebCtx2d.stroke();
            } else if (p.type === 'spark') {
                p.x += p.vx; p.y += p.vy; p.vy += 0.18; p.life -= 0.022; p.alpha = Math.max(0, p.life);
                celebCtx2d.beginPath(); celebCtx2d.arc(p.x, p.y, Math.max(0.5, p.r*p.life), 0, Math.PI*2); celebCtx2d.fillStyle = p.color; celebCtx2d.fill();
            }
            celebCtx2d.restore();
        });
        celebRAF = requestAnimationFrame(animateCelebration);
    }
    function stopCelebration() { if (celebRAF) { cancelAnimationFrame(celebRAF); celebRAF = null; } if (celebCanvas) celebCanvas.style.display = 'none'; celebParticles = []; }
    function playCheer() {
        try {
            const ctx = getAudioCtx(); if (ctx.state === 'suspended') ctx.resume();
            const now = ctx.currentTime, dur = 4, sr = ctx.sampleRate, buf = ctx.createBuffer(2, sr*dur, sr);
            for (let ch = 0; ch < 2; ch++) { const d = buf.getChannelData(ch); for (let i = 0; i < d.length; i++) { const t=i/sr, env=t<0.5?t/0.5:t<3?1:Math.pow(1-(t-3)/1,1.5), mod=0.5+0.5*Math.abs(Math.sin(t*7.5+ch*0.4+Math.random()*.05)); d[i]=(Math.random()*2-1)*mod*env*0.3; } }
            const src = ctx.createBufferSource(); src.buffer = buf;
            const bp = ctx.createBiquadFilter(); bp.type='bandpass'; bp.frequency.value=1600; bp.Q.value=0.7;
            const gv = ctx.createGain(); gv.gain.value=2.2;
            src.connect(bp); bp.connect(gv); gv.connect(ctx.destination); src.start(now);
            [[0,523],[280,659],[520,784],[720,880],[880,1047]].forEach(([ms, freq]) => {
                const osc = ctx.createOscillator(), g = ctx.createGain(); osc.type='sine'; osc.frequency.value=freq;
                const t0 = now + ms/1000; g.gain.setValueAtTime(0,t0); g.gain.linearRampToValueAtTime(0.2,t0+0.06); g.gain.exponentialRampToValueAtTime(0.001,t0+0.5);
                osc.connect(g); g.connect(ctx.destination); osc.start(t0); osc.stop(t0+0.6);
            });
        } catch(e) {}
    }

    function showResultModal(idx) { resultText.textContent = buildFullName(slices[idx]); resultModal.classList.add('show'); startCelebration(); }
    window.closeModal = function () { resultModal.classList.remove('show'); stopCelebration(); };
    window.toggleStatus = function () { isActive = statusToggle.checked; updateStatusUI(); saveToServer(true); };

    window.saveSlices = async function () {
        document.querySelectorAll('.s-name').forEach(el => { const i = +el.dataset.i; if (slices[i]) slices[i].name = el.value || buildAutoName(slices[i]); });
        document.querySelectorAll('.s-deger').forEach(el => { const i = +el.dataset.i; if (!slices[i]) return; const v = parseFloat(el.value); slices[i].deger = isNaN(v) ? null : v; });
        if (selectedIdx < 0 || selectedIdx >= slices.length) { showToast('Lütfen önce bir kazanan dilim seçin.', 'error'); return; }
        await saveToServer(false);
    };
    window.saveKurallar = async function () {
        if (selectedIdx < 0 || selectedIdx >= slices.length) { showToast('Lütfen önce bir kazanan dilim seçin.', 'error'); return; }
        const btn = document.getElementById('rules-save-btn');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Kaydediliyor...'; }
        await saveToServer(false);
        if (btn) { btn.disabled = false; btn.textContent = '📋 Kuralları Kaydet'; }
    };

    async function saveToServer(statusOnly) {
        const saveBtn = document.getElementById('save-btn');
        if (!statusOnly) { saveBtn.disabled = true; saveBtn.textContent = '⏳ Kaydediliyor...'; }
        const payload = slices.map((sl, i) => ({
            name: (sl.name && String(sl.name).trim()) ? String(sl.name).trim() : buildAutoName(sl),
            color: sl.color,
            probability: i === selectedIdx ? 100 : 0,
            tip: sl.tip || 'bos',
            deger: sl.deger != null ? sl.deger : null,
            indirim_tipi: isIndirim(sl.tip) && sl.indirim_tipi === 'tutar' ? 'tutar' : 'yuzde',
        }));
        if (rulesText) { kurallar = temizleKurallar(rulesText.value); rulesText.value = kurallar; }
        try {
            const _carkGunEl = document.getElementById('kupon-cark-gun'), _puanGunEl = document.getElementById('kupon-puan-gun');
            const _carkGunVal = _carkGunEl ? Math.max(0, parseInt(_carkGunEl.value)||0) : 0;
            const _puanGunVal = _puanGunEl ? Math.max(0, parseInt(_puanGunEl.value)||0) : 0;
            const _aralikEl = document.getElementById('cevirme-araligi-gun');
            const _aralikVal = _aralikEl ? Math.max(1, parseInt(_aralikEl.value)||1) : 1;
            const res = await fetch('/cark-yonetim/kaydet', {
                method: 'POST', headers: HEADERS,
                body: JSON.stringify({
                    dilimler: payload, aktifmi: isActive ? 1 : 0, kurallar: kurallar,
                    kupon_cark_gecerlilik_gun: _carkGunVal, kupon_puan_gecerlilik_gun: _puanGunVal, cevirme_araligi_gun: _aralikVal,
                })
            });
            const data = await res.json();
            if (data.success) { if (!statusOnly) { showToast('Ayar kaydedildi! 🎉', 'success'); render(); } }
            else { showToast(data.message || 'Bir hata oluştu!', 'error'); }
        } catch (e) { showToast('Bağlantı hatası!', 'error'); }
        if (!statusOnly) { saveBtn.disabled = false; saveBtn.textContent = '💾 Ayarı Kaydet'; }
    }

    let toastTimer;
    function showToast(msg, type) { clearTimeout(toastTimer); toast.className = `toast ${type} show`; toast.textContent = msg; toastTimer = setTimeout(() => toast.classList.remove('show'), 3500); }
    window.ckToast = showToast;

    // Nasıl Çalışır? modal
    const ckhOv = document.getElementById('ckh-ov');
    window.ckHelpAc = function () { ckhOv.classList.add('show'); };
    window.ckHelpKapat = function () { ckhOv.classList.remove('show'); };
    if (ckhOv) ckhOv.addEventListener('click', e => { if (e.target === ckhOv) ckHelpKapat(); });

    // Önerilen restoran çarkını tek tıkla uygula
    window.onerilenUygula = async function () {
        if (!confirm('Mevcut çark dilimleriniz önerilen restoran düzeniyle değiştirilecek. Onaylıyor musunuz?')) return;
        try {
            const res = await fetch('/cark-yonetim/onerilen', { method: 'POST', headers: HEADERS, body: '{}' });
            const d = await res.json();
            if (d.success) { showToast('Önerilen restoran çarkı uygulandı! 🎉', 'success'); loadData(); }
            else { showToast(d.message || 'Uygulanamadı', 'error'); }
        } catch (e) { showToast('Bağlantı hatası!', 'error'); }
    };
    function svgEl(tag) { return document.createElementNS('http://www.w3.org/2000/svg', tag); }
    function wrapText(str, max) {
        const words = String(str).split(' '); let lines = [], line = '';
        for (const w of words) {
            if (w.length > max) { if (line) { lines.push(line); line = ''; } for (let j = 0; j < w.length; j += max) lines.push(w.slice(j, j+max)); }
            else if ((line ? line + ' ' + w : w).length <= max) { line = line ? line + ' ' + w : w; }
            else { if (line) lines.push(line); line = w; }
        }
        if (line) lines.push(line);
        if (lines.length > 3) lines = [lines[0], lines[1], lines[2].slice(0, max-1) + '…'];
        return lines;
    }
    function esc(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
})();
</script>
@endsection

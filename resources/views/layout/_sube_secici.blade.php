@php
    // Grup patronu / erişim verilen personel için şube değiştirici. Tek şubede hiç görünmez.
    $_ssListe = [];
    try { if (function_exists('_personelErisebilirSubeler') && $_pp) $_ssListe = _personelErisebilirSubeler($_pp); } catch (\Throwable $e) {}
@endphp
@if (count($_ssListe) > 1)
@php
    $_ssAd = \Illuminate\Support\Facades\DB::table('subeler')->whereIn('id', $_ssListe)->pluck('ad', 'id');
    $_ssAktif = (int) $_pp->sube_id;
    $_ssAna = (int) ($_pp->ana_sube_id ?? $_pp->sube_id);
@endphp
<style>
  .rk-ss{position:relative;margin-right:6px}
  .rk-ss>button{display:flex;align-items:center;gap:6px;border:1px solid var(--line);background:var(--card);color:var(--ink);border-radius:12px;padding:7px 11px;font-weight:700;font-size:12.5px;cursor:pointer;font-family:inherit;max-width:230px}
  .rk-ss>button.baska{border-color:#0891B2;background:color-mix(in srgb,#0891B2 10%,var(--card))}
  .rk-ss>button .material-icons-outlined{font-size:18px;color:#0891B2}
  .rk-ss>button span.ad{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .rk-ss-menu{display:none;position:absolute;right:0;top:calc(100% + 6px);min-width:240px;background:var(--card);border:1px solid var(--line);border-radius:14px;box-shadow:0 12px 32px rgba(15,23,42,.16);padding:6px;z-index:60}
  .rk-ss.acik .rk-ss-menu{display:block}
  .rk-ss-menu a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;color:var(--ink);text-decoration:none;font-size:13px;font-weight:600}
  .rk-ss-menu a:hover{background:var(--card2)}
  .rk-ss-menu a.on{background:color-mix(in srgb,#0891B2 12%,transparent);color:#0891B2}
  .rk-ss-menu small{display:block;font-size:10.5px;color:var(--sub);font-weight:600}
  .rk-ss-menu .bas{font-size:10.5px;font-weight:800;color:var(--sub);letter-spacing:.04em;padding:6px 10px 4px}
  @media (max-width:640px){.rk-ss>button span.ad{max-width:90px}}
</style>
<div class="rk-ss" id="rkSs">
  <button type="button" class="{{ $_ssAktif !== $_ssAna ? 'baska' : '' }}" onclick="document.getElementById('rkSs').classList.toggle('acik')" title="Şube değiştir">
    <span class="material-icons-outlined">storefront</span><span class="ad">{{ $_ssAd[$_ssAktif] ?? 'Şube' }}</span><span class="material-icons-outlined" style="font-size:16px;color:var(--sub)">expand_more</span>
  </button>
  <div class="rk-ss-menu">
    <div class="bas">ŞUBE SEÇ</div>
    @foreach ($_ssListe as $_ssId)
      <a href="/sube-sec?id={{ $_ssId }}" class="{{ $_ssId === $_ssAktif ? 'on' : '' }}">
        <span class="material-icons-outlined" style="font-size:18px">{{ $_ssId === $_ssAktif ? 'radio_button_checked' : 'radio_button_unchecked' }}</span>
        <span>{{ $_ssAd[$_ssId] ?? ('Şube #' . $_ssId) }}@if ($_ssId === $_ssAna)<small>Kendi şubeniz</small>@endif</span>
      </a>
    @endforeach
  </div>
</div>
<script>document.addEventListener('click', function (e) { var s = document.getElementById('rkSs'); if (s && !s.contains(e.target)) s.classList.remove('acik'); });</script>
@endif

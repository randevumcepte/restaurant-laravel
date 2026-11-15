@extends('layout.app')
@section('rk_ikon', 'approval')
@section('baslik', 'Satınalma Onay Limitleri & Seviyeleri')
@section('title', 'Onay Limitleri')

@push('head')
<style>
#os{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #os{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#os{background:var(--bg);min-height:calc(100vh - 60px)}#os *{box-sizing:border-box}
#os .pano{max-width:860px;margin:0 auto;padding:18px 18px 40px}
#os .aciklama{background:var(--card);border-left:4px solid var(--mor1);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#os .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#os .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#os .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#os .kb .material-icons-outlined{color:var(--mor1);font-size:19px}
#os .flag{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
#os .sw{position:relative;display:inline-block;width:50px;height:27px}
#os .sw input{opacity:0;width:0;height:0}
#os .sl{position:absolute;inset:0;background:var(--card2);border-radius:20px;transition:.2s;cursor:pointer}
#os .sl::before{content:'';position:absolute;height:21px;width:21px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.3)}
#os .sw input:checked + .sl{background:var(--yesil)}
#os .sw input:checked + .sl::before{transform:translateX(23px)}
#os .zincir{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:12px}
#os .kade{display:flex;align-items:center;gap:9px;background:var(--card2);border-radius:12px;padding:9px 13px;position:relative}
#os .kade .no{width:24px;height:24px;border-radius:50%;background:var(--mor1);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0}
#os .kade .ic .ad{font-size:13px;font-weight:700;color:var(--ink)}
#os .kade .ic .vk{font-size:10.5px;color:var(--sub)}
#os .kade .sil{border:0;background:none;color:var(--sub2);cursor:pointer;padding:0 2px}
#os .kade .sil:hover{color:var(--kirmizi)}
#os .ok{color:var(--sub2);font-size:20px}
#os .roz{font-size:9.5px;font-weight:800;padding:1px 7px;border-radius:20px;background:color-mix(in srgb,var(--amber) 16%,transparent);color:#92600a}
#os label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#os select,#os input[type=date]{border:1px solid var(--line);border-radius:10px;padding:9px 11px;background:var(--card);color:var(--ink);font-size:13px;width:100%}
#os .form-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:10px;align-items:end}
@media(max-width:620px){#os .form-grid{grid-template-columns:minmax(0,1fr)}}
#os select,#os input{min-width:0;max-width:100%}
#os .kb{flex-wrap:wrap}
#os .chk{display:flex;align-items:center;gap:7px;font-size:12.5px;color:var(--ink);font-weight:600;margin-top:8px}
#os .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;background:var(--mor1);display:inline-flex;align-items:center;gap:7px;margin-top:12px}
#os .btn .material-icons-outlined{font-size:18px}
#os .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
#os .kb .alt{font-size:11.5px;font-weight:600;color:var(--sub);margin-left:auto}
#os .lim-satir{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1fr) minmax(0,1fr) minmax(0,1fr);gap:10px;align-items:center;padding:10px 0;border-top:1px solid var(--line)}
#os .lim-satir.bas{border-top:0;padding-top:0;font-size:10.5px;font-weight:800;color:var(--sub2);text-transform:uppercase;letter-spacing:.04em}
#os .lim-ad{font-size:13.5px;font-weight:700;color:var(--ink)}
#os .tl{position:relative}
#os .tl input{width:100%;min-width:0;border:1px solid var(--line);border-radius:10px;padding:9px 30px 9px 11px;background:var(--card);color:var(--ink);font-size:13.5px;font-weight:600}
#os .tl::after{content:'₺';position:absolute;right:11px;top:50%;transform:translateY(-50%);color:var(--sub2);font-weight:700;font-size:13px}
#os .bar{height:7px;border-radius:6px;background:var(--card2);overflow:hidden;margin-top:5px}
#os .bar i{display:block;height:100%;background:var(--yesil);border-radius:6px}
#os .kul{font-size:11.5px;color:var(--sub);font-weight:600}
#os .dep-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,230px),1fr));gap:10px}
#os .dep{display:flex;align-items:center;flex-wrap:wrap;gap:8px 10px;background:var(--card2);border-radius:12px;padding:10px 12px;min-width:0}
#os .dep .di{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
#os .dep .di .material-icons-outlined{font-size:18px}
#os .dep .da{font-size:13px;font-weight:700;color:var(--ink);flex:1;min-width:0}
#os .dep select{flex:1 0 100%;min-width:0;font-size:12px;padding:7px 9px;font-weight:700}
#os .dep select.onay{color:#b45309;border-color:rgba(245,158,11,.5)}
#os .tablo{width:100%;border-collapse:collapse;font-size:12.5px}
#os .tablo th{text-align:left;font-size:10.5px;color:var(--sub2);text-transform:uppercase;letter-spacing:.04em;padding:0 8px 8px}
#os .tablo td{padding:9px 8px;border-top:1px solid var(--line);color:var(--ink)}
#os .tablo td.tt{font-weight:800;text-align:right;white-space:nowrap}
@media(max-width:620px){#os .lim-satir{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}#os .lim-satir .lim-ad{grid-column:1/-1}#os .lim-satir.bas{display:none}#os .tablo .gz{display:none}}
</style>
@endpush

@section('content')
<div id="os"><div class="pano">

  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  @php $tl = fn ($x) => number_format((float) $x, 0, ',', '.'); @endphp
  <form method="POST" action="/satinalma-limit/kaydet" id="limit">
    @csrf
    <div class="aciklama" style="border-left-color:var(--yesil)">
      <b>Satın almacı onay limiti:</b> Satın almacı, kartına yazdığın tutara kadar olan siparişleri <b>patron onayı beklemeden</b> kendisi onaylayıp geçer; sen engellemezsin ama her birinden bildirim alırsın ve aşağıdaki listede görürsün.
      Limiti boş bırakılan satın almacı eskisi gibi her talebi patrona gönderir. <b>"Her zaman patron onayı"</b> seçilen departmanın (ör. Teknik) talepleri tutar ne olursa olsun onaya gider.
      Aynı gün aynı tedarikçiye yapılan siparişler toplanır — sipariş bölünerek limit aşılamaz.
    </div>

    <div class="kart">
      <div class="kb"><span class="material-icons-outlined">account_balance_wallet</span> Satın Almacı Limitleri <span class="alt">boş = kendi onayı yok</span></div>
      @if($satinalmacilar->count())
        <div class="lim-satir bas"><div>Satın almacı</div><div>Sipariş başı limit</div><div>Aylık limit (boş = sınırsız)</div><div>Bu ay kendi onayıyla</div></div>
        @foreach($satinalmacilar as $s)
          @php $ay = (float) ($s->sa_aylik_limit ?? 0); $oran = $ay > 0 ? min(100, round($s->kullanilan / $ay * 100)) : 0; @endphp
          <div class="lim-satir">
            <div class="lim-ad">{{ $s->ad }}</div>
            <div class="tl"><input name="limit[{{ $s->id }}][siparis]" inputmode="decimal" placeholder="ör. 15.000" value="{{ (float) $s->sa_siparis_limit > 0 ? $tl($s->sa_siparis_limit) : '' }}"></div>
            <div class="tl"><input name="limit[{{ $s->id }}][aylik]" inputmode="decimal" placeholder="sınırsız" value="{{ $ay > 0 ? $tl($ay) : '' }}"></div>
            <div>
              <div class="kul">{{ $tl($s->kullanilan) }} ₺{{ $ay > 0 ? ' / ' . $tl($ay) . ' ₺' : '' }}</div>
              @if($ay > 0)<div class="bar"><i style="width:{{ $oran }}%;{{ $oran >= 90 ? 'background:var(--kirmizi)' : ($oran >= 70 ? 'background:var(--amber)' : '') }}"></i></div>@endif
            </div>
          </div>
        @endforeach
      @else
        <div class="bos">Rolü <b>Satın Almacı</b> olan aktif personel yok. Personel kartında rolü "Satın Almacı" yapınca burada listelenir.</div>
      @endif
    </div>

    <div class="kart">
      <div class="kb"><span class="material-icons-outlined">rule</span> Departman Kuralları <span class="alt">talebi açan departmana göre</span></div>
      @if($departmanlar->count())
        <div class="dep-grid">
          @foreach($departmanlar as $d)
            <div class="dep">
              <div class="di" style="background:{{ $d->renk ?: '#7C3AED' }}"><span class="material-icons-outlined">{{ $d->ikon ?: 'apartment' }}</span></div>
              <div class="da">{{ $d->ad }}</div>
              <select name="dep[{{ $d->id }}]" class="{{ $d->kural === 'onay' ? 'onay' : '' }}" onchange="this.className = this.value === 'onay' ? 'onay' : ''">
                <option value="limit" {{ $d->kural === 'limit' ? 'selected' : '' }}>Limit geçerli</option>
                <option value="onay" {{ $d->kural === 'onay' ? 'selected' : '' }}>Her zaman patron onayı</option>
              </select>
            </div>
          @endforeach
        </div>
      @else
        <div class="bos">Departman tanımlı değil — tüm talepler için satın almacı limiti geçerli.</div>
      @endif
      <button class="btn" type="submit" style="background:var(--yesil)"><span class="material-icons-outlined">save</span> Limitleri Kaydet</button>
    </div>
  </form>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">verified</span> Kendi Onayıyla Geçilen Son Siparişler</div>
    @if($sonSiparisler->count())
      <div style="overflow-x:auto"><table class="tablo">
        <tr><th>Tarih</th><th>Sipariş</th><th>Satın almacı</th><th class="gz">Tedarikçi</th><th class="gz">Talep</th><th style="text-align:right">Tutar</th></tr>
        @foreach($sonSiparisler as $s)
          <tr><td>{{ \Carbon\Carbon::parse($s->tarih)->format('d.m.Y') }}</td><td><b>{{ $s->siparis_no }}</b></td><td>{{ $s->satinalmaci ?: '—' }}</td>
            <td class="gz">{{ $s->tedarikci ?: '—' }}</td><td class="gz">{{ $s->notlar }}</td><td class="tt">{{ number_format((float) $s->genel_toplam, 2, ',', '.') }} ₺</td></tr>
        @endforeach
      </table></div>
    @else
      <div class="bos">Henüz satın almacının kendi limitiyle geçtiği sipariş yok.</div>
    @endif
  </div>

  <div class="aciklama">
    <b>Çok-kademeli onay:</b> Satın alma talebi onaylanmadan önce sırayla belirlediğin kişilerden geçsin (ör. Şef → Maliyet Kontrol → Ana Depo). Her kademeye bir <b>onaylayan</b> + tatildeyse <b>vekil</b> atayabilirsin. <b>Küçük işletmede gerek yok</b> → kapalı bırak (patron direkt onaylar). Kurumsal/çok-şubede aç.
  </div>

  {{-- Flag --}}
  <div class="kart">
    <form method="POST" action="/satinalma-onay-seviye/flag" class="flag">
      @csrf
      <div><div style="font-size:14px;font-weight:800;color:var(--ink)">Çok-Kademeli Onay</div>
        <div style="font-size:12px;color:var(--sub)">{{ $acik ? 'Açık — talepler kademelerden geçer' : 'Kapalı — patron/satınalmacı direkt onaylar' }}</div></div>
      <label class="sw"><input type="checkbox" name="acik" {{ $acik ? 'checked' : '' }} onchange="this.form.submit()"><span class="sl"></span></label>
    </form>
  </div>

  {{-- Zincir --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">account_tree</span> Onay Zinciri</div>
    @if($seviyeler->count())
      <div class="zincir">
        <div class="kade"><div class="no" style="background:var(--sub)">📋</div><div class="ic"><div class="ad">Talep</div></div></div>
        @foreach($seviyeler as $s)
          <span class="ok material-icons-outlined">arrow_forward</span>
          <div class="kade">
            <div class="no">{{ $s->seviye }}</div>
            <div class="ic">
              <div class="ad">{{ $adMap[$s->onaylayan_id] ?? '—' }}</div>
              @if($s->vekil_id)<div class="vk">vekil: {{ $adMap[$s->vekil_id] ?? '—' }}{{ $s->vekil_bitis ? ' ('.$s->vekil_bitis.' tarihine dek)' : '' }}</div>@endif
              @if($s->anlasma_atla)<span class="roz">anlaşmalı atla</span>@endif
            </div>
            <form method="POST" action="/satinalma-onay-seviye/sil" style="margin:0" onsubmit="return confirm('Bu kademeyi sil?')">@csrf<input type="hidden" name="id" value="{{ $s->id }}"><button class="sil" type="submit"><span class="material-icons-outlined" style="font-size:17px">close</span></button></form>
          </div>
        @endforeach
        <span class="ok material-icons-outlined">arrow_forward</span>
        <div class="kade"><div class="no" style="background:var(--yesil)">✓</div><div class="ic"><div class="ad">Onaylandı → Sipariş</div></div></div>
      </div>
    @else
      <div class="bos">Henüz onay kademesi yok. Aşağıdan ekle (sırayla eklediğin kişiler kademe olur).</div>
    @endif
  </div>

  {{-- Ekle --}}
  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">add_circle</span> Kademe Ekle</div>
    <form method="POST" action="/satinalma-onay-seviye/ekle">
      @csrf
      <div class="form-grid">
        <div><label>Onaylayan *</label>
          <select name="onaylayan_id" required>
            <option value="">— seç —</option>
            @foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }} ({{ $p->rol }})</option>@endforeach
          </select></div>
        <div><label>Vekil (tatildeyse — opsiyonel)</label>
          <select name="vekil_id">
            <option value="">— yok —</option>
            @foreach($personeller as $p)<option value="{{ $p->id }}">{{ $p->ad }}</option>@endforeach
          </select></div>
        <div><label>Vekil geçerlilik bitişi (opsiyonel)</label><input type="date" name="vekil_bitis"></div>
        <div><label class="chk"><input type="checkbox" name="anlasma_atla" value="1"> Fiyat anlaşması olan taleplerde bu onayı atla</label></div>
      </div>
      <button class="btn" type="submit"><span class="material-icons-outlined">add</span> Kademe Ekle</button>
    </form>
  </div>

</div></div>
@endsection

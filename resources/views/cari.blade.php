@extends('layout.app')
@section('title', 'Cari / Açık Hesaplar')
@section('rk_ikon', 'account_balance_wallet')
@section('baslik', 'Cari / Açık Hesaplar')

@php
  /* Flutter cari_hesaplar_screen BİREBİR */
  $tipAd  = ['patron' => 'Patron', 'firma' => 'Firma', 'musteri' => 'Müşteri', 'personel' => 'Personel'];
  $tipRnk = ['patron' => '#7C3AED', 'firma' => '#4F46E5', 'musteri' => '#10B981', 'personel' => '#D97706'];

  $cariler    = $cariler    ?? [];
  $ozet       = $ozet       ?? ['toplam_alacak' => 0, 'adet' => 0];
  $detay      = $detay      ?? null;
  $hareketler = $hareketler ?? [];
  $aktifId    = $aktifId    ?? 0;

  $adet = $ozet['adet'] ?? count($cariler);
  $tl   = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $rnk  = fn($t) => $tipRnk[$t] ?? '#64748B';
@endphp

@section('rk_alt', $adet . ' cari hesap')

@push('head')
<style>
/* ── köklü CSS değişkenler (koyu/açık) ──────────────────── */
#cr{
  --mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;
  --amber:#F59E0B;--gold:#F6CE63;
  --card:#fff;--card2:#f1f5f9;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;
  --line:#e2e8f0;
}
body.koyu #cr{
  --card:#1b2235;--card2:#0f1526;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;
  --line:#2c3650;
}

/* ── layout ───────────────────────────────────────────────── */
#cr{}
#cr .cr-grid{display:grid;grid-template-columns:minmax(0,1.9fr) minmax(0,1fr);gap:20px;align-items:start}
@media(max-width:900px){#cr .cr-grid{grid-template-columns:1fr}}

/* ── Flutter gradient özet kartı (MIstatKart) ──────────────── */
#cr .cr-ozet{
  border-radius:18px;overflow:hidden;margin-bottom:20px;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));
  padding:20px 22px;
}
#cr .cr-ozet .sub{color:rgba(255,255,255,.70);font-size:13px;margin-bottom:4px}
#cr .cr-ozet .buyuk{color:#fff;font-size:30px;font-weight:900;letter-spacing:-.5px;line-height:1}
#cr .cr-ozet .alt{color:rgba(255,255,255,.60);font-size:12px;margin-top:6px}

/* ── kart sarmalayıcı ───────────────────────────────────────── */
#cr .cr-kart{
  background:var(--card);border:1px solid var(--line);
  border-radius:16px;padding:18px;margin-bottom:16px;
}

/* ── bölüm başlığı (cubuk+bold) ─────────────────────────────── */
#cr .cr-bolum{display:flex;align-items:center;gap:9px;margin-bottom:14px}
#cr .cr-bolum .cubuk{width:4px;height:18px;border-radius:3px;flex:none}
#cr .cr-bolum .b{font-size:14px;font-weight:700;color:var(--ink)}

/* ── form elemanları ────────────────────────────────────────── */
#cr .cr-inp{
  width:100%;height:42px;border:1px solid var(--line);border-radius:10px;
  background:var(--card2);color:var(--ink);font-size:13.5px;padding:0 12px;
  outline:none;box-sizing:border-box;transition:border-color .15s;
}
#cr .cr-inp:focus{border-color:var(--mor1)}
body.koyu #cr .cr-inp{background:var(--card2);border-color:var(--line)}
#cr .cr-inp::placeholder{color:var(--sub2)}
#cr .cr-lbl{display:block;font-size:12px;font-weight:600;color:var(--sub);margin:10px 0 4px}

/* ── Tip seçici (Flutter Row/GestureDetector kutuları) ──────── */
#cr .cr-tipler{display:flex;gap:8px;margin-top:4px}
#cr .cr-tip{
  flex:1;padding:9px 4px;border-radius:10px;border:1.5px solid transparent;
  background:var(--card2);cursor:pointer;text-align:center;
  font-size:12px;font-weight:700;color:var(--sub);transition:all .15s;user-select:none;
}

/* ── butonlar ────────────────────────────────────────────────── */
#cr .cr-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:7px;
  height:44px;padding:0 18px;border:none;border-radius:12px;
  font-size:14px;font-weight:700;color:#fff;cursor:pointer;
  transition:opacity .15s;
}
#cr .cr-btn:disabled{opacity:.6;cursor:default}
#cr .cr-btn.tam{width:100%}
#cr .cr-btn.bos{
  background:transparent;border:1.5px solid var(--line);
  color:var(--sub);height:36px;padding:0 12px;font-size:13px;border-radius:9px;
}
#cr .cr-btn .material-icons-outlined{font-size:18px}

/* ── tablo (Flutter MTablo) ──────────────────────────────────── */
#cr .cr-tablo{overflow-x:auto}
#cr .cr-tablo table{width:100%;border-collapse:collapse}
#cr .cr-tablo th{
  font-size:11.5px;font-weight:700;color:var(--sub2);
  padding:9px 10px;border-bottom:1.5px solid var(--line);
  text-transform:uppercase;letter-spacing:.5px;
}
#cr .cr-tablo td{
  padding:10px;border-bottom:1px solid var(--line);vertical-align:middle;
}
#cr .cr-tablo tbody tr:last-child td{border-bottom:0}
#cr .cr-tablo tbody tr{transition:background .1s}
#cr .cr-tablo tbody tr:hover{background:color-mix(in srgb,var(--mor1) 6%,transparent)}
#cr .cr-tablo .sag{text-align:right}

/* ── avatar (CircleAvatar) ───────────────────────────────────── */
#cr .cr-av{
  width:36px;height:36px;border-radius:50%;flex:none;
  display:flex;align-items:center;justify-content:center;
  font-size:14px;font-weight:900;
}

/* ── rozet (MRozet / Flutter tip rozeti) ─────────────────────── */
#cr .cr-roz{
  display:inline-flex;align-items:center;
  padding:3px 9px;border-radius:20px;border:1.5px solid;
  font-size:11px;font-weight:700;
}

/* ── detay panel ─────────────────────────────────────────────── */
/* Flutter _CariDetayScreen: gradient özet + tahsilat + hareketler */
#cr .cr-det-ozet{
  border-radius:18px;overflow:hidden;margin-bottom:14px;
  padding:20px 22px;
}
#cr .cr-det-ozet .sub{color:rgba(255,255,255,.70);font-size:13px;margin-bottom:4px}
#cr .cr-det-ozet .buyuk{color:#fff;font-size:28px;font-weight:900;letter-spacing:-.5px}
#cr .cr-det-ozet .alt{color:rgba(255,255,255,.60);font-size:11.5px;margin-top:6px}
#cr .cr-det-bas{
  display:flex;align-items:center;flex-wrap:wrap;gap:8px;
  margin-bottom:6px;
}
#cr .cr-det-bas .ad{color:#fff;font-size:15px;font-weight:800}

/* ── hareket satırı (Flutter _hareketSatir) ─────────────────── */
#cr .cr-h{
  display:flex;align-items:center;gap:10px;
  border:1px solid var(--line);border-radius:12px;
  padding:10px 12px;margin-bottom:8px;
}
#cr .cr-h:last-child{margin-bottom:0}
#cr .cr-h .ico{font-size:18px}
#cr .cr-h .bilgi{flex:1;min-width:0}
#cr .cr-h .bilgi .ad{font-size:13px;font-weight:600;color:var(--sub2)}
#cr .cr-h .bilgi .zaman{font-size:11px;color:var(--sub)}
#cr .cr-h .tutar{font-size:14px;font-weight:800;white-space:nowrap}

/* ── boş detay ön yüzü ───────────────────────────────────────── */
#cr .cr-bos-det{
  background:var(--card);border:1px solid var(--line);border-radius:16px;
  padding:44px 20px;text-align:center;color:var(--sub);
}
#cr .cr-bos-det .material-icons-outlined{font-size:44px;color:var(--mor1);display:block;margin-bottom:10px}

/* ── flash ────────────────────────────────────────────────────── */
#cr .cr-flash{
  padding:12px 16px;border-radius:12px;margin-bottom:14px;font-size:13.5px;font-weight:600;
  display:flex;align-items:center;gap:8px;
}
#cr .cr-flash.ok{background:rgba(16,185,129,.12);color:#047857;border:1px solid rgba(16,185,129,.35)}
body.koyu #cr .cr-flash.ok{color:#34d399;border-color:rgba(16,185,129,.25)}
#cr .cr-flash.err{background:rgba(244,63,94,.10);color:#be123c;border:1px solid rgba(244,63,94,.3)}
body.koyu #cr .cr-flash.err{color:#fb7185;border-color:rgba(244,63,94,.25)}

/* ── yeni cari modal ─────────────────────────────────────────── */
#crModal{display:none;position:fixed;inset:0;z-index:1200;align-items:center;justify-content:center;padding:20px}
#crModal.ac{display:flex}
#crModal .overlay{position:absolute;inset:0;background:rgba(0,0,0,.5)}
#crModal .kutu{
  position:relative;z-index:1;width:100%;max-width:420px;
  background:var(--card);border-radius:18px;padding:26px 22px;
  box-shadow:0 24px 64px rgba(0,0,0,.22);
}
#crModal .kutu .baslik{font-size:16px;font-weight:700;color:var(--ink);margin-bottom:18px}
#crModal .kutu .alt-btn{
  display:flex;justify-content:flex-end;gap:10px;margin-top:20px;
}
#crModal .cr-vaz{
  height:40px;padding:0 16px;border:1px solid var(--line);background:transparent;
  border-radius:10px;font-size:14px;color:var(--sub);cursor:pointer;
}
</style>
@endpush

@section('content')
<div id="cr">

{{-- ── Flash bildirimleri ─────────────────────────────────────── --}}
@if (session('cari_ok'))
  <div class="cr-flash ok">
    <span class="material-icons-outlined" style="font-size:18px">check_circle</span>
    {{ session('cari_ok') }}
  </div>
@endif
@if (session('cari_hata'))
  <div class="cr-flash err">
    <span class="material-icons-outlined" style="font-size:18px">error_outline</span>
    {{ session('cari_hata') }}
  </div>
@endif

{{-- ── Flutter gradient özet kartı: Toplam Açık Alacak ─────── --}}
<div class="cr-ozet">
  <div class="sub">Toplam Açık Alacak</div>
  <div class="buyuk">{{ $tl($ozet['toplam_alacak'] ?? 0) }}</div>
  <div class="alt">{{ $adet }} cari hesap</div>
</div>

{{-- ── İki sütun: sol liste · sağ detay ──────────────────────── --}}
<div class="cr-grid">

  {{-- ════ SOL: liste + Yeni Cari butonu ════ --}}
  <div>

    {{-- FAB ↔ "Yeni Cari" butonu (Flutter FloatingActionButton.extended) --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:14px">
      <button class="cr-btn" style="background:var(--mor1)" onclick="crYeniAc()">
        <span class="material-icons-outlined">person_add_alt</span>
        Yeni Cari
      </button>
    </div>

    {{-- ── Cari listesi (Flutter masaüstü MTablo) ──────────────── --}}
    @if (!count($cariler))
      <div class="cr-kart" style="padding:44px;text-align:center;color:var(--sub)">Cari hesap yok.</div>
    @else
      <div class="cr-kart" style="padding:0;overflow:hidden">
        <div class="cr-tablo">
          <table>
            <thead>
              <tr>
                <th style="width:42%">Cari</th>
                <th style="width:18%">Tip</th>
                <th class="sag" style="width:24%">Bakiye</th>
                <th class="sag" style="width:16%"></th>
              </tr>
            </thead>
            <tbody>
              @foreach ($cariler as $c)
                @php
                  $bk   = (float) ($c['bakiye'] ?? 0);
                  $tip  = $c['tip'] ?? '';
                  $renk = $rnk($tip);
                  $aktif = ($aktifId == ($c['id'] ?? 0));
                  $borc = $bk > 0;
                  $ilk  = mb_strtoupper(mb_substr($c['ad'] ?? '?', 0, 1));
                @endphp
                <tr @if($aktif) style="background:color-mix(in srgb,#7C3AED 10%,transparent)" @endif>

                  {{-- Cari: avatar baş harf + ad (Flutter CircleAvatar + Text) --}}
                  <td>
                    <div style="display:flex;align-items:center;gap:10px;min-width:0">
                      <div class="cr-av"
                        style="background:color-mix(in srgb,{{ $renk }} 22%,transparent);color:{{ $renk }}">
                        {{ $ilk }}
                      </div>
                      <div style="min-width:0">
                        <div style="font-size:13.5px;font-weight:600;color:var(--ink);
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                          {{ $c['ad'] ?? '' }}
                        </div>
                        @if (!empty($c['telefon']))
                          <div style="font-size:11.5px;color:var(--sub)">{{ $c['telefon'] }}</div>
                        @endif
                      </div>
                    </div>
                  </td>

                  {{-- Tip rozeti (Flutter MRozet / _tipRenk) --}}
                  <td>
                    <span class="cr-roz"
                      style="color:{{ $renk }};
                             border-color:color-mix(in srgb,{{ $renk }} 40%,transparent);
                             background:color-mix(in srgb,{{ $renk }} 13%,transparent)">
                      {{ $tipAd[$tip] ?? $tip }}
                    </span>
                  </td>

                  {{-- Bakiye: borç=kırmızı / temiz=yeşil --}}
                  <td class="sag">
                    <div style="font-size:13.5px;font-weight:800;
                                color:{{ $borc ? 'var(--kirmizi)' : 'var(--yesil)' }}">
                      {{ $tl($bk) }}
                    </div>
                    <div style="font-size:10.5px;color:var(--sub)">
                      {{ $borc ? 'borç' : 'temiz' }}
                    </div>
                  </td>

                  {{-- Detay butonu (Flutter MButon bos + chevron_right) --}}
                  <td class="sag">
                    <a href="/cari?id={{ $c['id'] }}" class="cr-btn bos">
                      Detay
                      <span class="material-icons-outlined" style="font-size:16px">chevron_right</span>
                    </a>
                  </td>

                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endif
  </div>{{-- /sol --}}

  {{-- ════ SAĞ: seçili cari detayı + tahsilat + hareketler ════ --}}
  <div>
    @if ($detay)
      @php
        $bk   = (float) ($detay['bakiye'] ?? 0);
        $borc = $bk > 0;
        $tip  = $detay['tip'] ?? '';
        $renk = $rnk($tip);
        $gbg  = $borc
          ? 'linear-gradient(135deg,#7F1D1D,#9F1239)'
          : 'linear-gradient(135deg,#7C3AED,#4F46E5)';
      @endphp

      {{-- Flutter _CariDetayScreen: gradient bakiye kartı --}}
      <div class="cr-det-ozet" style="background:{{ $gbg }}">
        <div class="cr-det-bas">
          <span class="ad">{{ $detay['ad'] ?? '' }}</span>
          <span class="cr-roz" style="color:#fff;border-color:rgba(255,255,255,.4);background:rgba(255,255,255,.2)">
            {{ $tipAd[$tip] ?? $tip }}
          </span>
        </div>
        <div class="sub">Güncel Bakiye (Borç)</div>
        <div class="buyuk">{{ $tl($bk) }}</div>
        <div class="alt">
          Toplam satış {{ $tl($detay['toplam_borc'] ?? 0) }}
          @if (isset($detay['toplam_tahsilat']))
            &nbsp;·&nbsp; Tahsilat {{ $tl($detay['toplam_tahsilat']) }}
          @endif
        </div>
      </div>

      {{-- Flutter FilledButton.icon "Tahsil Et" — geniş yeşil buton --}}
      {{-- Webde inline form olarak açılır --}}
      <div class="cr-kart" style="margin-bottom:14px">
        <div class="cr-bolum">
          <span class="cubuk" style="background:var(--yesil)"></span>
          <span class="b">Tahsilat (Borç Kapat)</span>
        </div>
        <form method="POST" action="/cari/tahsilat">
          @csrf
          <input type="hidden" name="cari_id" value="{{ $aktifId }}">

          <label class="cr-lbl" style="margin-top:0">Tutar (₺)</label>
          <input type="number" name="tutar" step="0.01" min="0.01" required
                 class="cr-inp"
                 value="{{ $borc ? round($bk) : '' }}"
                 placeholder="Tutar (TL)">

          {{-- Flutter Row: nakit / havale / kredi seçici --}}
          <label class="cr-lbl">Ödeme Şekli</label>
          <div class="cr-tipler" id="tahSekilKutu">
            @foreach (['nakit' => 'Nakit', 'havale' => 'Havale', 'kredi' => 'Kredi Kartı'] as $sk => $sad)
              <label class="cr-tip" id="tahTip_{{ $sk }}"
                     style="--rc:var(--yesil)"
                     onclick="crTahSekil('{{ $sk }}')">
                <input type="radio" name="odeme_sekli" value="{{ $sk }}"
                       {{ $sk === 'nakit' ? 'checked' : '' }}
                       style="display:none">
                {{ $sad }}
              </label>
            @endforeach
          </div>

          {{-- Flutter FilledButton.icon payments_outlined --}}
          <button type="submit" class="cr-btn tam" style="background:var(--yesil);margin-top:14px">
            <span class="material-icons-outlined">payments</span>
            Tahsil Et
          </button>
        </form>
      </div>

      {{-- Flutter _hareketSatir listesi --}}
      <div class="cr-kart">
        <div class="cr-bolum">
          <span class="cubuk" style="background:var(--mor1)"></span>
          <span class="b">Hesap Hareketleri</span>
        </div>
        @if (!count($hareketler))
          <div style="text-align:center;color:var(--sub);padding:16px 0">Hareket yok.</div>
        @else
          @foreach ($hareketler as $h)
            @php
              $hBorc = (($h['tip'] ?? '') === 'borc');
              $hRnk  = $hBorc ? 'var(--kirmizi)' : 'var(--yesil)';
              $hAd   = $hBorc
                ? 'Satış (Borç)'
                : 'Tahsilat' . (!empty($h['odeme_sekli']) ? ' · ' . $h['odeme_sekli'] : '');
            @endphp
            <div class="cr-h">
              {{-- Flutter arrow_upward (borç=kırmızı) / arrow_downward (tahsilat=yeşil) --}}
              <span class="material-icons-outlined ico" style="color:{{ $hRnk }}">
                {{ $hBorc ? 'arrow_upward' : 'arrow_downward' }}
              </span>
              <div class="bilgi">
                <div class="ad">{{ $hAd }}</div>
                <div class="zaman">{{ $h['zaman'] ?? '' }}</div>
              </div>
              <div class="tutar" style="color:{{ $hRnk }}">
                {{ $hBorc ? '+' : '-' }}{{ $tl($h['tutar'] ?? 0) }}
              </div>
            </div>
          @endforeach
        @endif
      </div>

    @else
      {{-- Detay seçilmedi: Flutter account_balance_wallet_outlined ön yüzü --}}
      <div class="cr-bos-det">
        <span class="material-icons-outlined">account_balance_wallet</span>
        Detay ve tahsilat için listeden bir cari seçin.
      </div>
    @endif
  </div>{{-- /sağ --}}

</div>{{-- /cr-grid --}}

{{-- ════════════════════════════════════════════════════════════
     Yeni Cari Hesap Modalı
     Flutter _yeniCari diyaloguna BİREBİR: İsim / Tip / Telefon
     ════════════════════════════════════════════════════════════ --}}
<div id="crModal" role="dialog" aria-modal="true">
  <div class="overlay" onclick="crYeniKapat()"></div>
  <div class="kutu">
    <div class="baslik">Yeni Cari Hesap</div>
    <form method="POST" action="/cari/ekle" id="crYeniForm">
      @csrf

      <label class="cr-lbl" style="margin-top:0">İsim / Ünvan</label>
      <input name="ad" required autofocus placeholder="İsim / Ünvan" class="cr-inp" id="crAdInp">

      <label class="cr-lbl">Telefon (opsiyonel)</label>
      <input name="telefon" placeholder="Telefon" class="cr-inp" inputmode="tel">

      {{-- Flutter Row/GestureDetector tip kutuları: musteri / firma / personel --}}
      <label class="cr-lbl">Tip</label>
      <div class="cr-tipler" id="crTipKutu">
        @foreach (['musteri' => ['Müşteri', '#10B981'], 'firma' => ['Firma', '#4F46E5'], 'personel' => ['Personel', '#D97706']] as $tk => [$tad, $trc])
          <label class="cr-tip" id="crTip_{{ $tk }}"
                 style="--rc:{{ $trc }}"
                 onclick="crTip('{{ $tk }}')">
            <input type="radio" name="tip" value="{{ $tk }}"
                   {{ $tk === 'musteri' ? 'checked' : '' }}
                   style="display:none">
            {{ $tad }}
          </label>
        @endforeach
      </div>

      <div class="alt-btn">
        <button type="button" class="cr-vaz" onclick="crYeniKapat()">Vazgeç</button>
        <button type="submit" class="cr-btn" style="background:var(--mor1)">Ekle</button>
      </div>
    </form>
  </div>
</div>

</div>{{-- /cr --}}

<script>
/* ── modal aç/kapat ─────────────────────────── */
function crYeniAc(){
  document.getElementById('crModal').classList.add('ac');
  setTimeout(()=>document.getElementById('crAdInp')?.focus(),80);
}
function crYeniKapat(){document.getElementById('crModal').classList.remove('ac')}
document.addEventListener('keydown',e=>{if(e.key==='Escape')crYeniKapat()});

/* ── Yeni Cari tip seçici ──────────────────── */
function crTip(seç){
  ['musteri','firma','personel'].forEach(k=>{
    const el=document.getElementById('crTip_'+k);
    if(!el)return;
    const inp=el.querySelector('input');
    if(k===seç){
      inp.checked=true;
      el.style.background='color-mix(in srgb,'+getComputedStyle(el).getPropertyValue('--rc')+' 25%,transparent)';
      el.style.borderColor=getComputedStyle(el).getPropertyValue('--rc');
      el.style.color=getComputedStyle(el).getPropertyValue('--rc');
    }else{
      inp.checked=false;
      el.style.background='';el.style.borderColor='transparent';el.style.color='';
    }
  });
}

/* ── Tahsilat ödeme şekli seçici ─────────────── */
function crTahSekil(seç){
  ['nakit','havale','kredi'].forEach(k=>{
    const el=document.getElementById('tahTip_'+k);
    if(!el)return;
    const inp=el.querySelector('input');
    if(k===seç){
      inp.checked=true;
      el.style.background='color-mix(in srgb,var(--yesil) 25%,transparent)';
      el.style.borderColor='var(--yesil)';
      el.style.color='var(--yesil)';
    }else{
      inp.checked=false;
      el.style.background='';el.style.borderColor='transparent';el.style.color='';
    }
  });
}

/* ── sayfa yüklenince başlangıç durumunu boyayalım ─ */
(function(){
  crTip('musteri');
  crTahSekil('nakit');
})();
</script>
@endsection

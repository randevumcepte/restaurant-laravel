@extends('layout.app')
@section('title', 'Cari / Açık Hesaplar')
@section('rk_ikon', 'account_balance_wallet')
@section('baslik', 'Cari / Açık Hesaplar')

@php
  // Flutter cari_hesaplar_screen BİREBİR: tip -> [etiket, css renk değişkeni]
  $tipAd  = ['patron' => 'Patron', 'firma' => 'Firma', 'musteri' => 'Müşteri', 'personel' => 'Personel'];
  $tipRnk = ['patron' => 'var(--mor1)', 'firma' => 'var(--mavi)', 'musteri' => 'var(--yesil)', 'personel' => 'var(--amber)'];

  $cariler    = $cariler    ?? [];
  $ozet       = $ozet       ?? ['toplam_alacak' => 0, 'adet' => 0];
  $detay      = $detay      ?? null;   // seçili cari (GET ?id=)
  $hareketler = $hareketler ?? [];
  $aktifId    = $aktifId    ?? 0;

  $adet = $ozet['adet'] ?? count($cariler);
  $tl = fn($v) => number_format(round((float) $v), 0, ',', '.') . '₺';
  $rnk = fn($t) => $tipRnk[$t] ?? 'var(--sub)';
@endphp

@section('rk_alt', $adet . ' cari hesap')

@section('content')

{{-- ====== Flash (session cari_ok / cari_hata) ====== --}}
@if (session('cari_ok'))
  <div class="rk-flash ok">{{ session('cari_ok') }}</div>
@endif
@if (session('cari_hata'))
  <div class="rk-flash err">{{ session('cari_hata') }}</div>
@endif

{{-- ====== Toplam Açık Alacak özeti (Flutter MIstatKart) ====== --}}
<div class="rk-stat" style="margin-bottom:20px">
  <div class="head" style="background:var(--mor1)">Toplam Açık Alacak</div>
  <div class="body">
    <div class="buyuk">{{ $tl($ozet['toplam_alacak'] ?? 0) }}</div>
    <div class="satir"><span class="e">Cari hesap adedi</span><span class="d">{{ $adet }}</span></div>
  </div>
</div>

{{-- ====== İki sütun: sol liste+ekle · sağ seçili detay ====== --}}
<div class="rk-grid" style="grid-template-columns:minmax(0,1.9fr) minmax(0,1fr);align-items:start">

  {{-- ---- SOL: yeni cari + liste ---- --}}
  <div>
    {{-- Yeni Cari Hesap (Flutter _yeniCari diyaloğu → inline form) --}}
    <div class="rk-card" style="margin-bottom:16px">
      <div class="rk-bolum">
        <span class="cubuk" style="background:var(--mor1)"></span>
        <span class="b">Yeni Cari Hesap</span>
      </div>
      <form method="POST" action="/cari/ekle">
        @csrf
        <div class="rk-grid" style="grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="rk-lbl" style="margin-top:0">İsim / Ünvan</label>
            <input name="ad" required placeholder="İsim / Ünvan" class="rk-inp">
          </div>
          <div>
            <label class="rk-lbl" style="margin-top:0">Telefon (opsiyonel)</label>
            <input name="telefon" placeholder="Telefon" class="rk-inp">
          </div>
        </div>
        <label class="rk-lbl">Tip</label>
        <select name="tip" class="rk-inp">
          <option value="musteri">Müşteri</option>
          <option value="firma">Firma</option>
          <option value="personel">Personel</option>
        </select>
        <button type="submit" class="rk-btn" style="background:var(--mor1);margin-top:14px">
          <span class="material-icons-outlined" style="font-size:18px">person_add_alt</span> Ekle
        </button>
      </form>
    </div>

    {{-- Cari listesi (Flutter masaüstü tablo: avatar+ad / tip rozeti / bakiye renkli / Detay) --}}
    @if (!count($cariler))
      <div class="rk-card" style="padding:40px;text-align:center;color:var(--sub)">Cari hesap yok.</div>
    @else
      <div class="rk-tablo">
        <table>
          <thead>
            <tr>
              <th style="width:40%">CARİ</th>
              <th style="width:18%">TİP</th>
              <th class="sag" style="width:24%">BAKİYE</th>
              <th class="sag" style="width:18%"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($cariler as $c)
              @php
                $bk = (float) ($c['bakiye'] ?? 0);
                $tip = $c['tip'] ?? '';
                $renk = $rnk($tip);
                $aktif = ($aktifId == ($c['id'] ?? 0));
                $borc = $bk > 0;
              @endphp
              <tr @if($aktif) style="background:color-mix(in srgb,var(--mor1) 10%,transparent)" @endif>
                {{-- CARİ: avatar baş harf + ad + telefon --}}
                <td>
                  <div style="display:flex;align-items:center;gap:10px;min-width:0">
                    <span style="width:34px;height:34px;flex:0 0 34px;border-radius:50%;
                                 background:color-mix(in srgb,{{ $renk }} 22%,transparent);color:{{ $renk }};
                                 font-size:14px;font-weight:800;display:flex;align-items:center;justify-content:center">{{ mb_strtoupper(mb_substr($c['ad'] ?? '?', 0, 1)) }}</span>
                    <div style="min-width:0">
                      <div style="font-size:13.5px;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['ad'] ?? '' }}</div>
                      @if (!empty($c['telefon']))<div style="font-size:11.5px;color:var(--sub)">{{ $c['telefon'] }}</div>@endif
                    </div>
                  </div>
                </td>
                {{-- TİP rozeti --}}
                <td>
                  <span class="rk-rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 40%,transparent);background:color-mix(in srgb,{{ $renk }} 13%,transparent)">{{ $tipAd[$tip] ?? $tip }}</span>
                </td>
                {{-- BAKİYE (borç kırmızı / temiz yeşil) --}}
                <td class="sag">
                  <div style="font-size:13.5px;font-weight:800;color:{{ $borc ? 'var(--kirmizi)' : 'var(--yesil)' }}">{{ $tl($bk) }}</div>
                  <div style="font-size:10.5px;color:var(--sub)">{{ $borc ? 'borç' : 'temiz' }}</div>
                </td>
                {{-- Detay butonu --}}
                <td class="sag">
                  <a href="/cari?id={{ $c['id'] }}" class="rk-btn bos" style="display:inline-flex">
                    Detay <span class="material-icons-outlined" style="font-size:16px">chevron_right</span>
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ---- SAĞ: seçili cari detayı + tahsilat + hareketler (Flutter _CariDetayScreen) ---- --}}
  <div>
    @if ($detay)
      @php
        $bk = (float) ($detay['bakiye'] ?? 0);
        $borc = $bk > 0;
        $tip = $detay['tip'] ?? '';
        $renk = $rnk($tip);
        $tahsilat = $detay['toplam_tahsilat'] ?? null;
      @endphp

      {{-- Güncel bakiye kartı: borç kırmızı / temiz mor başlık --}}
      <div class="rk-stat" style="margin-bottom:14px">
        <div class="head" style="background:{{ $borc ? 'var(--kirmizi)' : 'var(--mor1)' }}">
          <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $detay['ad'] ?? '' }}</span>
          <span class="rk-rozet" style="color:#fff;border-color:rgba(255,255,255,.4);background:rgba(255,255,255,.2)">{{ $tipAd[$tip] ?? $tip }}</span>
        </div>
        <div class="body">
          <div style="font-size:12px;color:var(--sub)">Güncel Bakiye (Borç)</div>
          <div class="buyuk" style="margin-bottom:4px">{{ $tl($bk) }}</div>
          <div style="font-size:12px;color:var(--sub)">
            Toplam satış {{ $tl($detay['toplam_borc'] ?? 0) }}
            @if ($tahsilat !== null) · Tahsilat {{ $tl($tahsilat) }} @endif
          </div>
        </div>
      </div>

      {{-- Tahsilat formu (Flutter _tahsilat diyaloğu → inline) --}}
      <div class="rk-card" style="margin-bottom:14px">
        <div class="rk-bolum">
          <span class="cubuk" style="background:var(--yesil)"></span>
          <span class="b">Tahsilat (Borç Kapat)</span>
        </div>
        <form method="POST" action="/cari/tahsilat">
          @csrf
          <input type="hidden" name="cari_id" value="{{ $aktifId }}">
          <label class="rk-lbl" style="margin-top:0">Tutar (₺)</label>
          <input type="number" name="tutar" step="0.01" min="0.01" required
                 value="{{ $borc ? round($bk) : '' }}" placeholder="Tutar (TL)" class="rk-inp">
          <label class="rk-lbl">Ödeme Şekli</label>
          <select name="odeme_sekli" class="rk-inp">
            <option value="nakit">Nakit</option>
            <option value="havale">Havale</option>
            <option value="kredi">Kredi Kartı</option>
          </select>
          <button type="submit" class="rk-btn lg" style="background:var(--yesil);margin-top:14px">
            <span class="material-icons-outlined" style="font-size:20px">payments</span> Tahsil Et
          </button>
        </form>
      </div>

      {{-- Hesap Hareketleri (Flutter _hareketSatir: ok yönü + etiket + tutar renkli) --}}
      <div class="rk-card">
        <div class="rk-bolum">
          <span class="cubuk" style="background:var(--mor1)"></span>
          <span class="b">Hesap Hareketleri</span>
        </div>
        @if (!count($hareketler))
          <div style="text-align:center;color:var(--sub);padding:14px 0">Hareket yok.</div>
        @else
          <div style="display:flex;flex-direction:column;gap:8px">
            @foreach ($hareketler as $h)
              @php
                $hBorc = (($h['tip'] ?? '') === 'borc');
                $hRenk = $hBorc ? 'var(--kirmizi)' : 'var(--yesil)';
                $hAd = $hBorc
                  ? 'Satış (Borç)'
                  : 'Tahsilat' . (!empty($h['odeme_sekli']) ? ' · ' . $h['odeme_sekli'] : '');
              @endphp
              <div style="display:flex;align-items:center;gap:10px;border:1px solid var(--line);border-radius:12px;padding:10px 12px">
                <span class="material-icons-outlined" style="font-size:18px;color:{{ $hRenk }}">{{ $hBorc ? 'arrow_upward' : 'arrow_downward' }}</span>
                <div style="min-width:0;flex:1">
                  <div style="font-size:13px;font-weight:600;color:var(--sub2)">{{ $hAd }}</div>
                  <div style="font-size:11px;color:var(--sub)">{{ $h['zaman'] ?? '' }}</div>
                </div>
                <div style="font-size:14px;font-weight:800;color:{{ $hRenk }}">{{ $hBorc ? '+' : '-' }}{{ $tl($h['tutar'] ?? 0) }}</div>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    @else
      <div class="rk-card" style="padding:32px;text-align:center;color:var(--sub)">
        <span class="material-icons-outlined" style="font-size:38px;color:var(--mor1);display:block;margin-bottom:8px">account_balance_wallet</span>
        Detay ve tahsilat için soldaki listeden bir cari seçin.
      </div>
    @endif
  </div>

</div>
@endsection

@extends('layout.app')
@section('rk_ikon', 'event_available')
@section('baslik', 'Rezervasyonlar')
@section('title', 'Rezervasyonlar')
@section('rk_alt', \Carbon\Carbon::parse($tarih)->format('d.m.Y'))

@section('content')
@php
  // Flutter rezervasyon_screen (masaüstü) BİREBİR: gün seçici + özet + liste (rozet+aksiyon) + yeni form.
  $bugun = \Carbon\Carbon::now()->format('Y-m-d');

  // Durum stili (Flutter _renkT / _durumAd ile aynı)
  $durumAd = ['bekliyor' => 'BEKLİYOR', 'onaylandi' => 'ONAYLI', 'geldi' => 'GELDİ', 'iptal' => 'İPTAL', 'gelmedi' => 'GELMEDİ'];
  $durumRenk = [
    'bekliyor'  => 'var(--amber)',
    'onaylandi' => 'var(--mavi)',
    'geldi'     => 'var(--yesil)',
    'iptal'     => '#64748B',
    'gelmedi'   => 'var(--kirmizi)',
  ];
  $kaynakIkon = ['web' => '🌐', 'telefon' => '📞', 'qr' => '📱', 'walk_in' => '🚶', 'walkin' => '🚶', 'admin' => '🖥️'];

  // Özet kartları (Flutter _pStat: Bugünün Rez. / Bekleyen / Onaylanan / Toplam Misafir)
  $statlar = [
    ['event_note',           'Bugünün Rezervasyonları', (int) ($ozet['toplam'] ?? 0),    'var(--mor1)'],
    ['hourglass_bottom',     'Bekleyen',                (int) ($ozet['bekleyen'] ?? 0),  'var(--amber)'],
    ['check_circle_outline', 'Onaylanan',               (int) ($ozet['onaylanan'] ?? 0), 'var(--yesil)'],
    ['groups',               'Toplam Misafir',          (int) ($ozet['misafir'] ?? 0),   'var(--mavi)'],
  ];
@endphp

@if (session('rez_ok'))
  <div class="rk-flash ok">✅ {{ session('rez_ok') }}</div>
@endif
@if (session('rez_hata'))
  <div class="rk-flash err">⚠️ {{ session('rez_hata') }}</div>
@endif

{{-- ================= GÜN SEÇİCİ (7 günlük mini takvim — Flutter _mGunSecici) ================= --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:18px">
  <div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px">
    @foreach ($gunler as $g)
      @php
        $aktif = $g['tarih'] === $tarih;
        $gd = \Carbon\Carbon::parse($g['tarih']);
        $fark = $gd->copy()->startOfDay()->diffInDays(\Carbon\Carbon::now()->startOfDay(), false);
        $etiket = $g['tarih'] === $bugun ? 'Bugün' : ($fark === -1 ? 'Yarın' : ['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'][$gd->dayOfWeekIso - 1]);
      @endphp
      <a href="/rezervasyon?tarih={{ $g['tarih'] }}"
         style="position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;width:66px;height:72px;border-radius:14px;border:1px solid {{ $aktif ? 'var(--mor1)' : 'var(--line)' }};background:{{ $aktif ? 'var(--mor1)' : 'var(--card)' }};text-decoration:none;flex:0 0 auto">
        <span style="font-size:12px;font-weight:600;color:{{ $aktif ? '#fff' : 'var(--sub)' }}">{{ $etiket }}</span>
        <span style="font-size:18px;font-weight:800;line-height:1;margin-top:3px;color:{{ $aktif ? '#fff' : 'var(--ink)' }}">{{ $gd->day }}</span>
        @if ($g['adet'] > 0)
          <span style="position:absolute;top:5px;right:5px;min-width:18px;height:18px;padding:0 5px;border-radius:20px;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;color:#fff;background:{{ $aktif ? 'rgba(255,255,255,.25)' : 'var(--mavi)' }}">{{ $g['adet'] }}</span>
        @endif
      </a>
    @endforeach
  </div>
  <form method="GET" action="/rezervasyon" style="margin:0">
    <input type="date" name="tarih" value="{{ $tarih }}" class="rk-inp" style="width:auto" onchange="this.form.submit()">
  </form>
</div>

{{-- ================= ÖZET KARTLARI (Flutter _pStat renkli kutular) ================= --}}
<div class="rk-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:18px">
  @foreach ($statlar as [$ik, $ad, $v, $renk])
    <div style="padding:14px;border-radius:14px;background:color-mix(in srgb,{{ $renk }} 11%,transparent);border:1px solid color-mix(in srgb,{{ $renk }} 28%,transparent)">
      <div style="display:flex;align-items:center;gap:8px">
        <span style="width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,{{ $renk }} 22%,transparent)">
          <span class="material-icons-outlined" style="font-size:16px;color:{{ $renk }}">{{ $ik }}</span>
        </span>
        <span style="font-size:11.5px;font-weight:600;color:var(--sub2);line-height:1.15">{{ $ad }}</span>
      </div>
      <div style="font-size:25px;font-weight:900;line-height:1;margin-top:10px;color:{{ $renk }}">{{ number_format($v, 0, ',', '.') }}</div>
    </div>
  @endforeach
</div>

<div class="rk-grid" style="grid-template-columns:2fr 1fr;align-items:start">
  {{-- ================= REZERVASYON LİSTESİ (Flutter _mTablo) ================= --}}
  <div>
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Rezervasyonlar</span>
      <span class="sayi" style="color:var(--mor1);background:color-mix(in srgb,var(--mor1) 13%,transparent)">{{ count($rezervasyonlar) }}</span>
    </div>

    @if (!count($rezervasyonlar))
      <div class="rk-card" style="text-align:center;padding:48px 20px">
        <span style="display:inline-flex;width:62px;height:62px;border-radius:18px;align-items:center;justify-content:center;background:color-mix(in srgb,var(--mor1) 10%,transparent)">
          <span class="material-icons-outlined" style="font-size:30px;color:var(--mor1)">event_available</span>
        </span>
        <div style="font-size:15px;font-weight:700;color:var(--ink);margin-top:14px">Bu gün için rezervasyon yok</div>
        <div class="rk-sub" style="font-size:12.5px;margin-top:6px">Sağdaki formdan yeni rezervasyon ekleyebilirsiniz.</div>
      </div>
    @else
      <div class="rk-tablo">
        <table>
          <thead>
            <tr>
              <th style="width:8%">Saat</th>
              <th style="width:24%">Müşteri</th>
              <th style="width:30%">Not</th>
              <th class="orta" style="width:7%">Kişi</th>
              <th class="orta" style="width:13%">Durum</th>
              <th class="sag" style="width:18%">İşlem</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($rezervasyonlar as $r)
              @php
                $d = $r->durum;
                $renk = $durumRenk[$d] ?? 'var(--mor1)';
                $pasif = in_array($d, ['iptal', 'gelmedi']);
                $not = trim((string) $r->not);
                $tel = trim((string) ($r->telefon ?? ''));
                $masa = trim((string) ($r->masa_ad ?? ''));
              @endphp
              <tr @if($pasif) style="opacity:.6" @endif>
                {{-- SAAT --}}
                <td><span style="font-size:15px;font-weight:800;color:{{ $renk }}">{{ substr((string) $r->saat, 0, 5) }}</span></td>
                {{-- MÜŞTERİ (+ kaynak ikon + tel + masa çipi) --}}
                <td>
                  <div style="display:flex;align-items:center;gap:6px">
                    <span style="font-weight:700;color:{{ $pasif ? 'var(--sub)' : 'var(--ink)' }};{{ $pasif ? 'text-decoration:line-through' : '' }}">{{ $r->ad }}</span>
                    <span style="font-size:12px">{{ $kaynakIkon[strtolower((string) $r->kaynak)] ?? '•' }}</span>
                  </div>
                  @if ($tel !== '')
                    <div class="rk-sub" style="font-size:12px;margin-top:2px">📞 {{ $tel }}</div>
                  @endif
                  @if ($masa !== '')
                    <div style="margin-top:4px"><span style="display:inline-block;padding:2px 7px;border-radius:20px;font-size:11px;font-weight:600;color:var(--mor1);background:color-mix(in srgb,var(--mor1) 12%,transparent)">🪑 Masa {{ $masa }}</span></div>
                  @endif
                </td>
                {{-- NOT (renkli kutu) --}}
                <td>
                  @if ($not === '')
                    <span class="rk-sub2">—</span>
                  @else
                    @php $notRenk = $pasif ? 'var(--sub2)' : 'var(--amber)'; @endphp
                    <div style="display:flex;align-items:flex-start;gap:7px;padding:8px 10px;border-radius:10px;background:color-mix(in srgb,{{ $notRenk }} 12%,transparent);border:1px solid color-mix(in srgb,{{ $notRenk }} 35%,transparent)">
                      <span class="material-icons-outlined" style="font-size:15px;color:{{ $notRenk }}">sticky_note_2</span>
                      <span style="font-size:13px;font-weight:500;color:{{ $pasif ? 'var(--sub)' : 'var(--ink)' }};line-height:1.3">{{ $not }}</span>
                    </div>
                  @endif
                </td>
                {{-- KİŞİ --}}
                <td class="orta"><span style="font-weight:700;color:var(--ink)">{{ (int) $r->kisi }}</span></td>
                {{-- DURUM (rozet) --}}
                <td class="orta">
                  <span class="rk-rozet" style="color:{{ $renk }};border-color:color-mix(in srgb,{{ $renk }} 40%,transparent);background:color-mix(in srgb,{{ $renk }} 13%,transparent)">{{ $durumAd[$d] ?? strtoupper($d) }}</span>
                </td>
                {{-- İŞLEM --}}
                <td class="sag">
                  @if ($d === 'bekliyor')
                    <div style="display:inline-flex;gap:8px;justify-content:flex-end">
                      <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                        <input type="hidden" name="tarih" value="{{ $tarih }}">
                        <input type="hidden" name="id" value="{{ $r->id }}">
                        <input type="hidden" name="durum" value="onaylandi">
                        <button class="rk-btn" style="background:var(--yesil)"><span class="material-icons-outlined" style="font-size:15px">check</span>Onayla</button>
                      </form>
                      <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                        <input type="hidden" name="tarih" value="{{ $tarih }}">
                        <input type="hidden" name="id" value="{{ $r->id }}">
                        <input type="hidden" name="durum" value="iptal">
                        <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent)"><span class="material-icons-outlined" style="font-size:15px">close</span>İptal</button>
                      </form>
                    </div>
                  @elseif ($d === 'onaylandi')
                    <div style="display:inline-flex;gap:8px;justify-content:flex-end">
                      <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                        <input type="hidden" name="tarih" value="{{ $tarih }}">
                        <input type="hidden" name="id" value="{{ $r->id }}">
                        <input type="hidden" name="durum" value="geldi">
                        <button class="rk-btn" style="background:var(--mor1)"><span class="material-icons-outlined" style="font-size:15px">login</span>Geldi</button>
                      </form>
                      <form method="POST" action="/rezervasyon/durum" style="margin:0">@csrf
                        <input type="hidden" name="tarih" value="{{ $tarih }}">
                        <input type="hidden" name="id" value="{{ $r->id }}">
                        <input type="hidden" name="durum" value="gelmedi">
                        <button class="rk-btn bos" style="color:var(--kirmizi);border-color:color-mix(in srgb,var(--kirmizi) 50%,transparent)"><span class="material-icons-outlined" style="font-size:15px">person_off</span>Gelmedi</button>
                      </form>
                    </div>
                  @else
                    <span class="rk-sub">—</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- ================= YENİ REZERVASYON FORMU (Flutter _ekleDialog) ================= --}}
  <div>
    <div class="rk-bolum">
      <span class="cubuk" style="background:var(--mor1)"></span>
      <span class="b">Yeni Rezervasyon</span>
    </div>
    <div class="rk-card">
      <form method="POST" action="/rezervasyon/ekle" style="margin:0">
        @csrf
        <input type="hidden" name="geri_tarih" value="{{ $tarih }}">
        <label class="rk-lbl" style="margin-top:0">Ad Soyad *</label>
        <input type="text" name="ad" required class="rk-inp">

        <label class="rk-lbl">Telefon</label>
        <input type="text" name="telefon" class="rk-inp">

        <div class="rk-grid" style="grid-template-columns:1fr 1fr">
          <div>
            <label class="rk-lbl">Tarih *</label>
            <input type="date" name="tarih" value="{{ $tarih }}" min="{{ $bugun }}" required class="rk-inp">
          </div>
          <div>
            <label class="rk-lbl">Saat *</label>
            <input type="time" name="saat" value="19:30" required class="rk-inp">
          </div>
        </div>

        <div class="rk-grid" style="grid-template-columns:1fr 1fr">
          <div>
            <label class="rk-lbl">Kişi</label>
            <input type="number" name="kisi" value="2" min="1" max="99" class="rk-inp">
          </div>
          <div>
            <label class="rk-lbl">Masa (opsiyonel)</label>
            <select name="masa_id" class="rk-inp">
              <option value="">— Masa atama —</option>
              @foreach ($masalar as $m)
                <option value="{{ $m->id }}">Masa {{ $m->ad }}{{ !empty($m->kapasite) ? ' · ' . (int) $m->kapasite . ' kişilik' : '' }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <label class="rk-lbl">Not (opsiyonel)</label>
        <textarea name="not" rows="2" class="rk-inp"></textarea>

        <button type="submit" class="rk-btn lg" style="background:var(--mor1);margin-top:16px">
          <span class="material-icons-outlined" style="font-size:18px">add</span>Kaydet
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

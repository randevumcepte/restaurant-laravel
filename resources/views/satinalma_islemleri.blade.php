@extends('layout.app')
@section('title', 'Satın Alma İşlemleri — ResteOS')
@section('rk_ikon', 'apps')
@section('baslik', 'Satın Alma İşlemleri')
@section('rk_alt', 'Tüm satın alma işlemleri tek yerde')

@php
  // Her kart: [url, ikon, baslik, aciklama, renk]
  $gruplar = [
    [
      'ad' => 'Günlük Süreç',
      'ikon' => 'play_circle',
      'kartlar' => [
        ['/satinalma-ozet',    'space_dashboard',         'Satın Alma Özeti',          'Kritik stok, bugünkü işler, hızlı aksiyonlar',           '#7C3AED'],
        ['/siparis-onerisi',   'auto_awesome',            'Otomatik Sipariş Önerisi',  'AI, geçmiş tüketime göre ne sipariş et söyler',          '#8B5CF6'],
        ['/satinalma-talep',   'assignment',              'Satın Alma Talebi',          'Mutfak/depo talebi oluştur, onaya gönder',               '#6366F1'],
        ['/satinalma-siparis', 'shopping_cart_checkout',  'Satınalma Siparişi',        'Tedarikçiye sipariş geç, PO oluştur',                    '#4F46E5'],
        ['/mal-kabul',         'inventory',               'Mal Kabul / İrsaliye',       'Gelen malı tart, say, stoğa düş',                        '#10B981'],
        ['/fatura-eslestir',   'fact_check',              'Fatura Eşleştir (3-way)',    'PO ↔ İrsaliye ↔ Fatura üçlü karşılaştırma',             '#F59E0B'],
        ['/fatura-ocr',        'document_scanner',        'Fatura OCR (AI)',            'Faturayı fotoğrafla, AI kalemleri çıkarsın',             '#7C3AED'],
      ],
    ],
    [
      'ad' => 'Stok & Takip',
      'ikon' => 'inventory_2',
      'kartlar' => [
        ['/stok',          'inventory_2',    'Stok & Satın Alma',  'Malzeme stoğu, par-seviye, kritikler',             '#0EA5E9'],
        ['/sayim',         'fact_check',     'Sayım (Envanter)',   'Fiili sayım gir, kayıp/fazla çıkarsın',            '#06B6D4'],
        ['/parti-takip',   'event_busy',     'Parti / SKT Takip',  'Son kullanma tarihi yaklaşanları gör',             '#F43F5E'],
        ['/uretim-riski',  'warning_amber',  'Üretim Riski',       'Yarın hangi yemeği pişiremezsin — şimdi gör',      '#F59E0B'],
        ['/barkod',        'qr_code',        'Barkod Yönetim',     'Ürün/malzeme barkodları ekle, tara',               '#64748b'],
      ],
    ],
    [
      'ad' => 'Fiyat & Analiz',
      'ikon' => 'trending_up',
      'kartlar' => [
        ['/fiyat-radari',    'radar',        'Fiyat Radarı · Gizli Zam',   'Sessiz fiyat artışlarını anında yakala',   '#EC4899'],
        ['/hal-fiyatlari',   'storefront',   'Hal Fiyatları (sen vs hal)', 'Hal rayici ile aldığın fiyatı karşılaştır', '#059669'],
        ['/satinalma-analiz','leaderboard',  'Satınalma Analizi · Karne',  'Kalem/tedarikçi/kategori performans karnesi','#4F46E5'],
        ['/satinalma-butce', 'savings',      'Satınalma Bütçe',            'Aylık bütçe takibi, aşımları önce gör',    '#10B981'],
        ['/satinalma-brief', 'wb_sunny',     'Sabah Brifingi',             'Günün özeti: ne acil, ne pazarlık, ne risk','#F59E0B'],
      ],
    ],
    [
      'ad' => 'Tedarikçiler',
      'ikon' => 'handshake',
      'kartlar' => [
        ['/tedarikci',         'local_shipping',    'Tedarikçiler',            'Firma listesi, iletişim, kalemler',               '#0EA5E9'],
        ['/tedarikci-karne',   'grading',           'Tedarikçi Karnesi',       'Zamanında teslim, fiyat, kalite skorları',        '#8B5CF6'],
        ['/eposta-hesaplari',  'alternate_email',   'E-posta Hesapları (RFQ)', 'Fiyat talebi için e-posta hesaplarını yönet',     '#7C3AED'],
        ['/gelen-teklifler',   'mark_email_read',   'Gelen Teklifler (AI)',    'Tedarikçiden gelen teklifleri AI ile oku-karşılaştır','#10B981'],
      ],
    ],
    [
      'ad' => 'Akıllı Asistan',
      'ikon' => 'auto_awesome',
      'kartlar' => [
        ['/satinalma-asistan', 'smart_toy', 'Satınalma AI Asistanı', 'Sor, veriden anında cevap al — rapor üret', '#7C3AED'],
      ],
    ],
  ];
@endphp

@push('head')
<style>
#siWrap{--mor1:#7C3AED;--mavi:#4F46E5;--card:#fff;--card2:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0;--bg:#f8fafc;
  max-width:1200px;margin:0 auto}
body.koyu #siWrap{--card:#1b2235;--card2:#161C2E;--ink:#e2e8f0;--sub:#a4b1c7;--sub2:#8694ad;--line:#2c3650;--bg:#0B1020}

/* Üst hero (küçük tanıtım şeridi) */
#siWrap .si-hero{display:flex;align-items:center;gap:14px;padding:16px 18px;margin-bottom:18px;
  border-radius:16px;background:linear-gradient(135deg,color-mix(in srgb,var(--mor1) 10%,var(--card)),color-mix(in srgb,var(--mavi) 7%,var(--card)));
  border:1px solid color-mix(in srgb,var(--mor1) 20%,var(--line))}
#siWrap .si-hero .ikon{width:46px;height:46px;border-radius:12px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,var(--mor1),var(--mavi));color:#fff;box-shadow:0 6px 16px -4px color-mix(in srgb,var(--mor1) 50%,transparent)}
#siWrap .si-hero .ikon .material-icons-outlined{font-size:24px}
#siWrap .si-hero h2{font-size:16px;font-weight:800;color:var(--ink);margin:0 0 3px}
#siWrap .si-hero p{font-size:12.5px;color:var(--sub);margin:0;line-height:1.4}

/* Arama */
#siWrap .si-ara{position:relative;margin-bottom:18px}
#siWrap .si-ara .material-icons-outlined{position:absolute;left:12px;top:50%;transform:translateY(-50%);
  font-size:19px;color:var(--sub);pointer-events:none}
#siWrap .si-ara input{width:100%;padding:11px 14px 11px 40px;border:1px solid var(--line);border-radius:12px;
  background:var(--card);color:var(--ink);font-size:14px;outline:none;transition:border-color .15s, box-shadow .15s;font-family:inherit}
#siWrap .si-ara input:focus{border-color:var(--mor1);box-shadow:0 0 0 3px color-mix(in srgb,var(--mor1) 12%,transparent)}

/* Grup başlığı */
#siWrap .si-grup{margin-bottom:22px}
#siWrap .si-grup-bas{display:flex;align-items:center;gap:10px;margin:0 0 12px;padding:0 4px}
#siWrap .si-grup-bas .cubuk{width:4px;height:22px;border-radius:3px;background:var(--mor1);flex-shrink:0}
#siWrap .si-grup-bas .material-icons-outlined{color:var(--mor1);font-size:20px}
#siWrap .si-grup-bas h3{margin:0;font-size:15px;font-weight:800;color:var(--ink);letter-spacing:-.2px}
#siWrap .si-grup-bas .n{font-size:11px;font-weight:700;color:var(--sub2);padding:2px 8px;border-radius:999px;background:color-mix(in srgb,var(--mor1) 10%,transparent);margin-left:4px}

/* Kart grid */
#siWrap .si-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}

/* Kart */
.si-kart{display:flex;align-items:flex-start;gap:12px;padding:14px;border-radius:14px;
  background:var(--card);border:1px solid var(--line);text-decoration:none;color:inherit;
  transition:all .2s cubic-bezier(.4,0,.2,1);position:relative;overflow:hidden}
.si-kart::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--kart-renk,var(--mor1));
  opacity:0;transition:opacity .2s}
.si-kart:hover{border-color:var(--kart-renk,var(--mor1));transform:translateY(-3px);
  box-shadow:0 10px 28px -8px color-mix(in srgb,var(--kart-renk,var(--mor1)) 30%,transparent),
             0 2px 6px rgba(0,0,0,.04)}
.si-kart:hover::before{opacity:1}
.si-kart .ikon{width:42px;height:42px;border-radius:11px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
  color:#fff;transition:transform .2s}
.si-kart:hover .ikon{transform:scale(1.08) rotate(-3deg)}
.si-kart .ikon .material-icons-outlined{font-size:22px}
.si-kart .icerik{flex:1;min-width:0}
.si-kart .bas{font-size:14px;font-weight:700;color:var(--ink);line-height:1.3;margin:0 0 4px;
  display:flex;align-items:center;gap:5px}
.si-kart .ac{font-size:12px;color:var(--sub);line-height:1.45;margin:0}
.si-kart .ok{color:var(--sub2);flex-shrink:0;align-self:center;transition:transform .2s, color .2s}
.si-kart:hover .ok{color:var(--kart-renk,var(--mor1));transform:translateX(3px)}
.si-kart .ok .material-icons-outlined{font-size:18px}

/* Filtre sonrası boş */
#siWrap .si-bos{padding:40px;text-align:center;color:var(--sub);background:var(--card);
  border:1px dashed var(--line);border-radius:14px}
#siWrap .si-bos .material-icons-outlined{font-size:38px;color:var(--sub2);display:block;margin-bottom:8px}

/* Mobile */
@media (max-width:640px){
  #siWrap .si-grid{grid-template-columns:1fr}
  #siWrap .si-hero{padding:12px 14px}
  #siWrap .si-hero h2{font-size:15px}
}
</style>
@endpush

@section('content')
<div id="siWrap">

  <div class="si-hero">
    <div class="ikon"><span class="material-icons-outlined">apps</span></div>
    <div>
      <h2>Satın Alma İşlemleri</h2>
      <p>Tüm satın alma, stok, fiyat ve tedarikçi işlemleri tek merkezden. Kartlardan birine tıkla, ilgili sayfaya git.</p>
    </div>
  </div>

  <div class="si-ara">
    <span class="material-icons-outlined">search</span>
    <input type="text" id="siAra" placeholder="İşlem ara (ör: fatura, stok, zam, tedarikçi…)" oninput="siFiltre()" autocomplete="off">
  </div>

  @foreach ($gruplar as $g)
    <div class="si-grup" data-grup="{{ $g['ad'] }}">
      <div class="si-grup-bas">
        <div class="cubuk"></div>
        <span class="material-icons-outlined">{{ $g['ikon'] }}</span>
        <h3>{{ $g['ad'] }}</h3>
        <span class="n">{{ count($g['kartlar']) }}</span>
      </div>
      <div class="si-grid">
        @foreach ($g['kartlar'] as $k)
          @php [$url, $ikon, $bas, $ac, $renk] = $k; @endphp
          <a class="si-kart" href="{{ $url }}" style="--kart-renk:{{ $renk }}"
             data-ara="{{ mb_strtolower($bas . ' ' . $ac) }}">
            <div class="ikon" style="background:linear-gradient(135deg,{{ $renk }},color-mix(in srgb,{{ $renk }} 70%,#000))">
              <span class="material-icons-outlined">{{ $ikon }}</span>
            </div>
            <div class="icerik">
              <h4 class="bas">{{ $bas }}</h4>
              <p class="ac">{{ $ac }}</p>
            </div>
            <span class="ok"><span class="material-icons-outlined">arrow_forward</span></span>
          </a>
        @endforeach
      </div>
    </div>
  @endforeach

  <div id="siBos" class="si-bos" style="display:none">
    <span class="material-icons-outlined">search_off</span>
    Aradığın işleme uygun kart bulunamadı.
  </div>

</div>

<script>
function siFiltre(){
  var q = (document.getElementById('siAra').value || '').toLowerCase().trim();
  var toplamGorunen = 0;
  document.querySelectorAll('#siWrap .si-grup').forEach(function(g){
    var gorunen = 0;
    g.querySelectorAll('.si-kart').forEach(function(k){
      var ok = !q || (k.getAttribute('data-ara') || '').indexOf(q) !== -1;
      k.style.display = ok ? '' : 'none';
      if (ok) gorunen++;
    });
    g.style.display = gorunen > 0 ? '' : 'none';
    toplamGorunen += gorunen;
  });
  var bos = document.getElementById('siBos');
  if (bos) bos.style.display = toplamGorunen === 0 ? 'block' : 'none';
}
</script>
@endsection

@extends('layout.app')
@section('rk_ikon', 'share')
@section('baslik', 'AI Sosyal Medya')
@section('title', 'AI Sosyal Medya')

@push('head')
<style>
#sm{--mor1:#7C3AED;--mavi:#4F46E5;--yesil:#10B981;--kirmizi:#F43F5E;--amber:#F59E0B;--pembe:#EC4899;
  --card:#fff;--card2:#f1f5f9;--bg:#f8fafc;--ink:#1e293b;--sub:#64748b;--sub2:#94a3b8;--line:#e2e8f0}
body.koyu #sm{--card:#161d2e;--card2:#1b2235;--bg:#0f1623;--ink:#e2e8f0;--sub:#94a3b8;--sub2:#64748b;--line:#2c3650}
#sm{background:var(--bg);min-height:calc(100vh - 60px)}#sm *{box-sizing:border-box}
#sm .pano{max-width:860px;margin:0 auto;padding:18px 18px 40px}
#sm .aciklama{background:var(--card);border-left:4px solid var(--pembe);border-radius:12px;padding:12px 15px;font-size:12.5px;color:var(--sub);line-height:1.55;margin-bottom:14px;box-shadow:0 2px 8px rgba(0,0,0,.05)}
#sm .flash{border-radius:12px;padding:11px 15px;margin-bottom:14px;font-weight:600;font-size:13.5px;background:rgba(16,185,129,.14);color:#0b805e}
#sm .kart{background:var(--card);border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.06);padding:16px;margin-bottom:14px}
#sm .kb{display:flex;align-items:center;gap:8px;font-size:14px;font-weight:800;color:var(--ink);margin-bottom:12px}
#sm .kb .material-icons-outlined{color:var(--pembe);font-size:19px}
#sm label{font-size:11px;font-weight:700;color:var(--sub);display:block;margin-bottom:4px}
#sm input,#sm select,#sm textarea{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:var(--card);color:var(--ink);font-size:13.5px;width:100%;font-family:inherit}
#sm textarea{height:120px;resize:vertical;line-height:1.5}
#sm .satir{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-top:4px}
#sm .btn{border:0;border-radius:11px;padding:11px 18px;font-weight:700;font-size:13.5px;cursor:pointer;color:#fff;display:inline-flex;align-items:center;gap:7px}
#sm .btn.pem{background:var(--pembe)}#sm .btn.yesil{background:var(--yesil)}#sm .btn.bos{background:var(--card);color:var(--mor1);border:1.5px solid var(--mor1)}
#sm .btn .material-icons-outlined{font-size:18px}
#sm .btn:disabled{opacity:.5}
#sm .ts{border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:8px}
#sm .ts .pf{font-size:10px;font-weight:800;padding:2px 9px;border-radius:20px;background:color-mix(in srgb,var(--pembe) 14%,transparent);color:var(--pembe)}
#sm .ts .mt{font-size:12.5px;color:var(--ink);white-space:pre-wrap;line-height:1.5;margin-top:7px}
#sm .tsak{display:flex;gap:6px;margin-top:8px}
#sm .btnk{border:0;border-radius:8px;padding:5px 10px;font-size:11px;font-weight:700;cursor:pointer;background:var(--card2);color:var(--sub)}
#sm .bos{text-align:center;color:var(--sub2);padding:24px;font-size:12.5px}
#sm .meta{background:color-mix(in srgb,var(--amber) 9%,var(--card));border:1px solid color-mix(in srgb,var(--amber) 30%,transparent);border-radius:12px;padding:11px 14px;font-size:12px;color:#92600a;margin-top:12px;line-height:1.5}
</style>
@endpush

@section('content')
<div id="sm"><div class="pano">
  @if(session('ok'))<div class="flash">{{ session('ok') }}</div>@endif

  <div class="aciklama">
    <b>AI Sosyal Medya:</b> Konu/ürün/kampanya yaz → AI sana <b>Instagram/Facebook gönderi metni + hashtag</b> hazırlasın. Düzenle, kopyala, taslak olarak sakla. Zaman kazandırır, tutarlı marka dili.
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">auto_awesome</span> Gönderi Oluştur</div>
    <div class="satir">
      <div style="flex:0 0 150px"><label>Platform</label><select id="platform"><option value="instagram">Instagram</option><option value="facebook">Facebook</option></select></div>
      <div style="flex:1;min-width:200px"><label>Konu / ürün / kampanya</label><input id="konu" placeholder="örn. yeni künefe tatlımız, hafta sonu brunch"></div>
      <button class="btn pem" id="aiBtn" onclick="aiYaz()"><span class="material-icons-outlined">auto_awesome</span> AI Yaz</button>
    </div>
    <div id="sonucAlan" style="display:none;margin-top:12px">
      <label>Gönderi metni (düzenleyebilirsin)</label>
      <textarea id="metin"></textarea>
      <div class="satir">
        <button class="btn yesil" onclick="kopyala()"><span class="material-icons-outlined">content_copy</span> Kopyala</button>
        <form method="POST" action="/social-media/kaydet" style="margin:0" id="kaydetForm">@csrf
          <input type="hidden" name="platform" id="fp"><input type="hidden" name="konu" id="fk"><input type="hidden" name="metin" id="fm">
          <button class="btn bos" type="submit" onclick="dolu()"><span class="material-icons-outlined">bookmark</span> Taslak Kaydet</button>
        </form>
        <button class="btn bos" onclick="aiYaz()"><span class="material-icons-outlined">refresh</span> Yeniden</button>
      </div>
    </div>
    <div class="meta">💡 <b>Otomatik paylaşım</b> (Instagram/Facebook'a direkt gönderme) Meta Graph API bağlantısı gerektirir — işletme hesabını bağladığında eklenebilir. Şimdilik AI metni üretir, sen kopyalayıp paylaşırsın.</div>
  </div>

  <div class="kart">
    <div class="kb"><span class="material-icons-outlined">bookmarks</span> Taslaklar</div>
    @forelse($taslaklar as $t)
      <div class="ts">
        <span class="pf">{{ ucfirst($t->platform) }}</span> @if($t->konu)<span style="font-size:11px;color:var(--sub)">· {{ $t->konu }}</span>@endif
        <div class="mt">{{ $t->metin }}</div>
        <div class="tsak">
          <button class="btnk" onclick="navigator.clipboard.writeText(this.closest('.ts').querySelector('.mt').innerText);this.textContent='✓ kopyalandı'">Kopyala</button>
          <form method="POST" action="/social-media/kaydet" style="margin:0" onsubmit="return confirm('Sil?')">@csrf<input type="hidden" name="sil" value="{{ $t->id }}"><button class="btnk" type="submit" style="color:var(--kirmizi)">Sil</button></form>
        </div>
      </div>
    @empty
      <div class="bos">Henüz taslak yok. Yukarıdan AI ile oluştur.</div>
    @endforelse
  </div>

</div></div>

<script>
const SM_CSRF=document.querySelector('meta[name=csrf-token]').content;
async function aiYaz(){
  const konu=document.getElementById('konu').value.trim();
  const platform=document.getElementById('platform').value;
  const btn=document.getElementById('aiBtn'); btn.disabled=true; btn.innerHTML='<span class="material-icons-outlined">hourglass_top</span> Yazıyor...';
  try{
    const r=await fetch('/social-media/ai',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':SM_CSRF,'Accept':'application/json'},body:JSON.stringify({konu,platform})});
    const j=await r.json();
    document.getElementById('metin').value=j.metin||'';
    document.getElementById('sonucAlan').style.display='block';
  }catch(e){alert('AI metin alınamadı, tekrar dene.');}
  btn.disabled=false; btn.innerHTML='<span class="material-icons-outlined">auto_awesome</span> AI Yaz';
}
function kopyala(){navigator.clipboard.writeText(document.getElementById('metin').value);alert('Kopyalandı! Instagram/Facebook\'a yapıştır.');}
function dolu(){document.getElementById('fp').value=document.getElementById('platform').value;document.getElementById('fk').value=document.getElementById('konu').value;document.getElementById('fm').value=document.getElementById('metin').value;}
</script>
@endsection

@extends('resteos_yonetim.layout')
@section('baslik','Güvenlik Duvarı')
@section('content')
<div class="ry-grid ry-g2">
  <div class="ry-card">
    <h3>🛡️ IP Kuralları</h3>
    @if($kurallar->isEmpty())<p class="ry-mut">Kural yok.</p>@else
    <table class="ry-table">
      <thead><tr><th>IP</th><th>Tip</th><th>Sebep</th><th></th></tr></thead>
      <tbody>
        @foreach($kurallar as $k)
          <tr><td class="ry-restoran">{{ $k->ip }}</td>
            <td><span class="ry-badge {{ $k->tip==='whitelist'?'success':'danger' }}">{{ $k->tip }}</span></td>
            <td class="ry-mut">{{ $k->sebep }}</td>
            <td style="text-align:right"><form method="post" action="/resteos-yonetim/guvenlik/{{ $k->id }}/sil">@csrf<button class="ry-btn ry-btn-danger ry-btn-sm" type="submit">Sil</button></form></td></tr>
        @endforeach
      </tbody>
    </table>
    @endif
    <hr class="ry-hr">
    <h3>🚨 Son 7 Gün Başarısız Girişler</h3>
    @if($basarisiz->isEmpty())<p class="ry-mut">Şüpheli giriş denemesi yok. 👍</p>@else
    <table class="ry-table">
      <tbody>
        @foreach($basarisiz as $b)
          <tr><td class="ry-restoran">{{ $b->ip }}</td><td><span class="ry-badge danger">{{ $b->adet }} başarısız</span></td>
            <td style="text-align:right">
              <form method="post" action="/resteos-yonetim/guvenlik" style="display:inline">@csrf
                <input type="hidden" name="ip" value="{{ $b->ip }}"><input type="hidden" name="tip" value="blacklist"><input type="hidden" name="sebep" value="Brute-force denemesi">
                <button class="ry-btn ry-btn-soft ry-btn-sm" type="submit">Engelle</button></form>
            </td></tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
  <div class="ry-card" style="align-self:start">
    <h3>➕ Kural Ekle</h3>
    <form method="post" action="/resteos-yonetim/guvenlik">@csrf
      <div class="ry-field"><label>IP Adresi</label><input class="ry-input" name="ip" required placeholder="ör. 1.2.3.4"></div>
      <div class="ry-field"><label>Tip</label><select class="ry-select" name="tip"><option value="blacklist">Kara Liste (engelle)</option><option value="whitelist">Beyaz Liste (güven)</option></select></div>
      <div class="ry-field"><label>Sebep</label><input class="ry-input" name="sebep"></div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Ekle</button>
    </form>
    <p class="ry-mut" style="margin-top:10px">Not: Kural listesi tutulur; sunucu düzeyinde engelleme (iptables/watchdog) Faz 2'de bağlanır.</p>
  </div>
</div>
@endsection

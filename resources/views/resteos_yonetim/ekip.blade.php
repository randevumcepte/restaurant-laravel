@extends('resteos_yonetim.layout')
@section('baslik','Ekip & Roller')
@section('content')
<div class="ry-grid ry-g2">
  <div class="ry-card" style="padding:0;overflow:hidden">
    <div style="padding:16px 20px"><h3 style="margin:0">👥 Yöneticiler</h3></div>
    <table class="ry-table">
      <thead><tr><th>Ad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th></th></tr></thead>
      <tbody>
        @foreach($ekip as $e)
          <tr>
            <td class="ry-restoran">{{ $e->ad }}</td>
            <td class="ry-mut">{{ $e->email }}</td>
            <td><span class="ry-badge mor">{{ $e->rol }}</span></td>
            <td>@if($e->aktif)<span class="ry-badge success">Aktif</span>@else<span class="ry-badge danger">Pasif</span>@endif</td>
            <td style="text-align:right">
              <form method="post" action="/resteos-yonetim/ekip/{{ $e->id }}/pasif" style="display:inline">@csrf
                <button class="ry-btn ry-btn-soft ry-btn-sm" type="submit">{{ $e->aktif?'Pasif Et':'Aktif Et' }}</button></form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="ry-card">
    <h3>➕ Yeni / Düzenle</h3>
    <form method="post" action="/resteos-yonetim/ekip">@csrf
      <div class="ry-field"><label>Ad Soyad</label><input class="ry-input" name="ad" required></div>
      <div class="ry-field"><label>E-posta</label><input class="ry-input" type="email" name="email" required></div>
      <div class="ry-field"><label>Şifre (yeni için)</label><input class="ry-input" type="password" name="sifre" placeholder="Boş bırakılırsa: resteos2026"></div>
      <div class="ry-field"><label>Rol</label>
        <select class="ry-select" name="rol">
          <option value="yonetici">Yönetici</option>
          <option value="destek">Destek</option>
          <option value="super_admin">Süper Admin</option>
        </select>
      </div>
      <button class="ry-btn ry-btn-primary ry-btn-blok" type="submit">Kaydet</button>
    </form>
    <p class="ry-mut" style="margin-top:12px">Sadece süper-admin ekip yönetebilir.</p>
  </div>
</div>
@endsection

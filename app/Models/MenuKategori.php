<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuKategori extends Model
{
    protected $table = 'menu_kategorileri';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean'];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function urunler() { return $this->hasMany(Urun::class, 'kategori_id'); }
}

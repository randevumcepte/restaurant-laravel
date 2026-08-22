<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Urun extends Model
{
    protected $table = 'urunler';
    protected $guarded = [];
    protected $casts = [
        'stok_takipli' => 'boolean',
        'tukendi' => 'boolean',
        'aktif' => 'boolean',
    ];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function kategori() { return $this->belongsTo(MenuKategori::class, 'kategori_id'); }
    public function recete() { return $this->hasOne(Recete::class); }
    public function modifierGruplari()
    {
        return $this->belongsToMany(ModifierGrup::class, 'urun_modifier_gruplari', 'urun_id', 'grup_id');
    }
}

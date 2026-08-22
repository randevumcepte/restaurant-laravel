<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sube extends Model
{
    protected $table = 'subeler';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean'];

    public function personeller() { return $this->hasMany(Personel::class); }
    public function bolgeler() { return $this->hasMany(Bolge::class); }
    public function masalar() { return $this->hasMany(Masa::class); }
    public function urunler() { return $this->hasMany(Urun::class); }
    public function adisyonlar() { return $this->hasMany(Adisyon::class); }
}

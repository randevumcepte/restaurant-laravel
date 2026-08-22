<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Musteri extends Model
{
    protected $table = 'musteriler';
    protected $guarded = [];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function adisyonlar() { return $this->hasMany(Adisyon::class); }
}

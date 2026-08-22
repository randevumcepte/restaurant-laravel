<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kurye extends Model
{
    protected $table = 'kuryeler';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean'];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function adisyonlar() { return $this->hasMany(Adisyon::class); }
}

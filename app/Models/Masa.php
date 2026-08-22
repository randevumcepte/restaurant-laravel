<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Masa extends Model
{
    protected $table = 'masalar';
    protected $guarded = [];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function bolge() { return $this->belongsTo(Bolge::class); }
    public function adisyonlar() { return $this->hasMany(Adisyon::class); }
    public function acikAdisyon() { return $this->hasOne(Adisyon::class)->where('durum', 'acik'); }
}

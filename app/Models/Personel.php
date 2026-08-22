<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personel extends Model
{
    protected $table = 'personeller';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean'];

    public function sube() { return $this->belongsTo(Sube::class); }
}

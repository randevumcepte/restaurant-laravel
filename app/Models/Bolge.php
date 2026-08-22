<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bolge extends Model
{
    protected $table = 'bolgeler';
    protected $guarded = [];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function masalar() { return $this->hasMany(Masa::class); }
}

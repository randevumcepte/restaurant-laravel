<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kampanya extends Model
{
    protected $table = 'kampanyalar';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean', 'baslangic' => 'date', 'bitis' => 'date'];

    public function sube() { return $this->belongsTo(Sube::class); }
}

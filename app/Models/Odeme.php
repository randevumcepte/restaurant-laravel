<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odeme extends Model
{
    protected $table = 'odemeler';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['created_at' => 'datetime'];

    public function adisyon() { return $this->belongsTo(Adisyon::class); }
    public function personel() { return $this->belongsTo(Personel::class); }
}

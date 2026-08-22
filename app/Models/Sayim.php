<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sayim extends Model
{
    protected $table = 'sayimlar';
    protected $guarded = [];
    protected $casts = ['tarih' => 'date'];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function kalemler() { return $this->hasMany(SayimKalemi::class, 'sayim_id'); }
}

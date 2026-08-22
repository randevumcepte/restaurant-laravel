<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recete extends Model
{
    protected $table = 'receteler';
    protected $guarded = [];

    public function urun() { return $this->belongsTo(Urun::class); }
    public function uretilenMalzeme() { return $this->belongsTo(Malzeme::class, 'uretilen_malzeme_id'); }
    public function verimBirim() { return $this->belongsTo(Birim::class, 'verim_birim_id'); }
    public function kalemler() { return $this->hasMany(ReceteKalemi::class); }
}

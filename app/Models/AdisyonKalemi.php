<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdisyonKalemi extends Model
{
    protected $table = 'adisyon_kalemleri';
    protected $guarded = [];
    protected $casts = ['gonderim_zamani' => 'datetime'];

    public function adisyon() { return $this->belongsTo(Adisyon::class); }
    public function urun() { return $this->belongsTo(Urun::class); }
    public function personel() { return $this->belongsTo(Personel::class); }
    public function secenekler() { return $this->hasMany(AdisyonKalemSecenek::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adisyon extends Model
{
    protected $table = 'adisyonlar';
    protected $guarded = [];
    protected $casts = [
        'acilis' => 'datetime',
        'kapanis' => 'datetime',
    ];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function masa() { return $this->belongsTo(Masa::class); }
    public function acanPersonel() { return $this->belongsTo(Personel::class, 'acan_personel_id'); }
    public function kalemler() { return $this->hasMany(AdisyonKalemi::class); }
    public function odemeler() { return $this->hasMany(Odeme::class); }
}

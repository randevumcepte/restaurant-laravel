<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdisyonMasaLog extends Model
{
    protected $table = 'adisyon_masa_loglari';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['created_at' => 'datetime'];

    public function adisyon() { return $this->belongsTo(Adisyon::class); }
    public function eskiMasa() { return $this->belongsTo(Masa::class, 'eski_masa_id'); }
    public function yeniMasa() { return $this->belongsTo(Masa::class, 'yeni_masa_id'); }
    public function personel() { return $this->belongsTo(Personel::class); }
}

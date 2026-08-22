<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IptalIndirimLog extends Model
{
    protected $table = 'iptal_indirim_loglari';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['created_at' => 'datetime'];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function adisyon() { return $this->belongsTo(Adisyon::class); }
    public function personel() { return $this->belongsTo(Personel::class); }
}

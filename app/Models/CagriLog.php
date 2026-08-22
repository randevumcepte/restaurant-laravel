<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CagriLog extends Model
{
    protected $table = 'cagri_loglari';
    protected $guarded = [];
    public $timestamps = false;
    protected $casts = ['created_at' => 'datetime'];

    public function musteri() { return $this->belongsTo(Musteri::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlisFaturasi extends Model
{
    protected $table = 'alis_faturalari';
    protected $guarded = [];
    protected $casts = ['tarih' => 'date'];

    public function sube() { return $this->belongsTo(Sube::class); }
    public function tedarikci() { return $this->belongsTo(Tedarikci::class); }
    public function kalemler() { return $this->hasMany(AlisFaturaKalemi::class, 'fatura_id'); }
}

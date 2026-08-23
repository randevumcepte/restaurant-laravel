<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Teklif extends Model
{
    protected $table = 'teklifler';
    protected $guarded = [];
    protected $casts = ['tarih' => 'date'];

    public function malzeme() { return $this->belongsTo(Malzeme::class); }
    public function tedarikci() { return $this->belongsTo(Tedarikci::class); }
}

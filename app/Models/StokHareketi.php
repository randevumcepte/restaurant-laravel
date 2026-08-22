<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokHareketi extends Model
{
    protected $table = 'stok_hareketleri';
    protected $guarded = [];
    public $timestamps = false; // immutable defter: sadece created_at
    protected $casts = ['created_at' => 'datetime'];

    public function malzeme() { return $this->belongsTo(Malzeme::class); }
    public function personel() { return $this->belongsTo(Personel::class); }
}

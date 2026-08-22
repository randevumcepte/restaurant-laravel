<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirimCevrim extends Model
{
    protected $table = 'birim_cevrimleri';
    protected $guarded = [];

    public function malzeme() { return $this->belongsTo(Malzeme::class); }
    public function birim() { return $this->belongsTo(Birim::class); }
}

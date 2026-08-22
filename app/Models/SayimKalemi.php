<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SayimKalemi extends Model
{
    protected $table = 'sayim_kalemleri';
    protected $guarded = [];

    public function sayim() { return $this->belongsTo(Sayim::class, 'sayim_id'); }
    public function malzeme() { return $this->belongsTo(Malzeme::class); }
}

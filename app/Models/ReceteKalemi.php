<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceteKalemi extends Model
{
    protected $table = 'recete_kalemleri';
    protected $guarded = [];

    public function recete() { return $this->belongsTo(Recete::class); }
    public function malzeme() { return $this->belongsTo(Malzeme::class); }
    public function altRecete() { return $this->belongsTo(Recete::class, 'alt_recete_id'); }
    public function birim() { return $this->belongsTo(Birim::class); }
}

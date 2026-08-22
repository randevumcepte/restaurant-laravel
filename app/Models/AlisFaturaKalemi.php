<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlisFaturaKalemi extends Model
{
    protected $table = 'alis_fatura_kalemleri';
    protected $guarded = [];

    public function fatura() { return $this->belongsTo(AlisFaturasi::class, 'fatura_id'); }
    public function malzeme() { return $this->belongsTo(Malzeme::class); }
    public function alisBirim() { return $this->belongsTo(Birim::class, 'alis_birim_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdisyonKalemSecenek extends Model
{
    protected $table = 'adisyon_kalem_secenekleri';
    protected $guarded = [];
    public $timestamps = false;

    public function kalem() { return $this->belongsTo(AdisyonKalemi::class, 'adisyon_kalem_id'); }
    public function modifier() { return $this->belongsTo(Modifier::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entegrasyon extends Model
{
    protected $table = 'entegrasyonlar';
    protected $guarded = [];
    protected $casts = ['aktif' => 'boolean', 'otomatik_onay' => 'boolean', 'son_siparis_at' => 'datetime'];

    public function sube() { return $this->belongsTo(Sube::class); }
}

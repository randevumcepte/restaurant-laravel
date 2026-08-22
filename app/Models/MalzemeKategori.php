<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MalzemeKategori extends Model
{
    protected $table = 'malzeme_kategorileri';
    protected $guarded = [];

    public function malzemeler() { return $this->hasMany(Malzeme::class, 'kategori_id'); }
}

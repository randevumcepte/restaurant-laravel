<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tedarikci extends Model
{
    protected $table = 'tedarikciler';
    protected $guarded = [];

    public function faturalar() { return $this->hasMany(AlisFaturasi::class); }
}

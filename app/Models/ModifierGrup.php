<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModifierGrup extends Model
{
    protected $table = 'modifier_gruplari';
    protected $guarded = [];

    public function modifierlar() { return $this->hasMany(Modifier::class, 'grup_id'); }
}

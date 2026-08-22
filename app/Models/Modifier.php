<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    protected $table = 'modifierlar';
    protected $guarded = [];

    public function grup() { return $this->belongsTo(ModifierGrup::class, 'grup_id'); }
}

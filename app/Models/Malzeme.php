<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Malzeme extends Model
{
    protected $table = 'malzemeler';
    protected $guarded = [];
    protected $casts = ['stok_takipli' => 'boolean'];

    public function kategori() { return $this->belongsTo(MalzemeKategori::class, 'kategori_id'); }
    public function temelBirim() { return $this->belongsTo(Birim::class, 'temel_birim_id'); }
    public function birimCevrimleri() { return $this->hasMany(BirimCevrim::class); }
    public function stokHareketleri() { return $this->hasMany(StokHareketi::class); }

    /** Defterden anlik stok (temel birimde) = tum hareketlerin toplami */
    public function stokMiktari()
    {
        return (float) $this->stokHareketleri()->sum('miktar');
    }
}

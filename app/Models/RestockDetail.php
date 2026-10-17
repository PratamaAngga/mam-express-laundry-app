<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestockDetail extends Model
{
    protected $table = 'detail_restock';
    protected $primaryKey = 'id_detail';
    public $timestamps = false;

    protected $fillable = ['id_restock', 'id_bahan', 'jumlah', 'harga_satuan', 'status'];

    protected function casts(): array
    {
        return [
            'jumlah'       => 'float',
            'harga_satuan' => 'float',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'id_bahan', 'id_bahan');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->jumlah * $this->harga_satuan;
    }
}
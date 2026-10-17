<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    protected $table = 'log_stok';
    protected $primaryKey = 'id_log';
    public $timestamps = false;

    protected $fillable = ['id_bahan', 'id_restock', 'tipe', 'jumlah', 'waktu'];

    protected function casts(): array
    {
        return [
            'jumlah' => 'float',
            'waktu'  => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'id_bahan', 'id_bahan');
    }
}
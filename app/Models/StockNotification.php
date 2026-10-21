<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockNotification extends Model
{
    protected $table = 'notifikasi_stok';
    protected $primaryKey = 'id_notif_stok';
    const UPDATED_AT = null;

    protected $fillable = ['id_bahan', 'id_user_owner', 'pesan', 'sudah_dibaca'];

    protected function casts(): array
    {
        return [
            'sudah_dibaca' => 'boolean',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class, 'id_bahan', 'id_bahan');
    }
}
